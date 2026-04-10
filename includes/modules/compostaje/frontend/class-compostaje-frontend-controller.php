<?php
/**
 * Frontend Controller para Compostaje Comunitario
 *
 * Maneja todas las vistas frontend, shortcodes, AJAX y dashboard tabs
 * para el sistema de compostaje comunitario.
 *
 * @package FlavorPlatform
 * @since 3.1.1
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Clase controladora del frontend de Compostaje
 */
class Flavor_Compostaje_Frontend_Controller {

    /**
     * Instancia singleton
     * @var Flavor_Compostaje_Frontend_Controller|null
     */
    private static $instancia = null;

    /**
     * ID del módulo
     */
    const MODULO_ID = 'compostaje';

    /**
     * Versión del controlador
     */
    const VERSION = '1.0.0';

    /**
     * Niveles de gamificación
     */
    private $niveles_gamificacion = [
        1 => ['nombre' => 'Semilla', 'kg_minimo' => 0, 'icono' => '🌱'],
        2 => ['nombre' => 'Brote', 'kg_minimo' => 10, 'icono' => '🌿'],
        3 => ['nombre' => 'Planta', 'kg_minimo' => 50, 'icono' => '🌾'],
        4 => ['nombre' => 'Árbol', 'kg_minimo' => 150, 'icono' => '🌳'],
        5 => ['nombre' => 'Bosque', 'kg_minimo' => 500, 'icono' => '🌲'],
        6 => ['nombre' => 'Ecosistema', 'kg_minimo' => 1000, 'icono' => '🌍'],
    ];

    /**
     * Materiales compostables
     */
    private $materiales = [
        'verde' => [
            'frutas_verduras' => ['nombre' => 'Frutas y verduras', 'puntos' => 5],
            'posos_cafe' => ['nombre' => 'Posos de café', 'puntos' => 6],
            'cesped_fresco' => ['nombre' => 'Césped fresco', 'puntos' => 4],
            'restos_cocina' => ['nombre' => 'Restos de cocina', 'puntos' => 5],
            'plantas_verdes' => ['nombre' => 'Plantas verdes', 'puntos' => 4],
        ],
        'marron' => [
            'hojas_secas' => ['nombre' => 'Hojas secas', 'puntos' => 6],
            'papel_carton' => ['nombre' => 'Papel y cartón', 'puntos' => 7],
            'ramas_poda' => ['nombre' => 'Ramas y poda', 'puntos' => 5],
            'serrin' => ['nombre' => 'Serrín', 'puntos' => 4],
            'paja' => ['nombre' => 'Paja', 'puntos' => 5],
        ],
        'especial' => [
            'cascaras_huevo' => ['nombre' => 'Cáscaras de huevo', 'puntos' => 8],
            'bolsas_te' => ['nombre' => 'Bolsas de té', 'puntos' => 6],
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
     * @return Flavor_Compostaje_Frontend_Controller
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
        // Shortcodes adicionales del frontend
        add_action('init', [$this, 'registrar_shortcodes']);

        // Assets
        add_action('wp_enqueue_scripts', [$this, 'registrar_assets']);

        // AJAX handlers
        add_action('wp_ajax_flavor_compostaje_dashboard', [$this, 'ajax_dashboard_data']);
        add_action('wp_ajax_flavor_compostaje_registrar', [$this, 'ajax_registrar_aportacion']);
        add_action('wp_ajax_flavor_compostaje_inscribir_turno', [$this, 'ajax_inscribir_turno']);
        add_action('wp_ajax_flavor_compostaje_cancelar_inscripcion', [$this, 'ajax_cancelar_inscripcion']);
        add_action('wp_ajax_flavor_compostaje_buscar_puntos', [$this, 'ajax_buscar_puntos']);
        add_action('wp_ajax_flavor_compostaje_solicitar_compost', [$this, 'ajax_solicitar_compost']);
        add_action('wp_ajax_nopriv_flavor_compostaje_buscar_puntos', [$this, 'ajax_buscar_puntos']);

        // Dashboard tab
        add_filter('flavor_user_dashboard_tabs', [$this, 'registrar_dashboard_tab'], 10, 1);
    }

    /**
     * Registra shortcodes del frontend
     */
    public function registrar_shortcodes() {
        $shortcodes = [
            'flavor_compostaje_mapa' => 'shortcode_mapa',
            'flavor_compostaje_puntos' => 'shortcode_lista_puntos',
            'flavor_compostaje_registrar' => 'shortcode_registrar',
            'flavor_compostaje_mis_aportaciones' => 'shortcode_mis_aportaciones',
            'flavor_compostaje_turnos' => 'shortcode_turnos',
            'flavor_compostaje_guia' => 'shortcode_guia',
            'flavor_compostaje_ranking' => 'shortcode_ranking',
            'flavor_compostaje_mi_balance' => 'shortcode_mi_balance',
            'flavor_compostaje_dashboard' => 'shortcode_dashboard',
        ];

        foreach ($shortcodes as $tag => $method) {
            if (!shortcode_exists($tag)) {
                add_shortcode($tag, [$this, $method]);
            }
        }
    }

    /**
     * Registra assets del frontend
     */
    public function registrar_assets() {
        $modulo_url = plugin_dir_url(dirname(__FILE__));

        wp_register_style(
            'flavor-compostaje-frontend',
            $modulo_url . 'assets/css/compostaje-frontend.css',
            [],
            self::VERSION
        );

        wp_register_script(
            'flavor-compostaje-frontend',
            $modulo_url . 'assets/js/compostaje-frontend.js',
            ['jquery'],
            self::VERSION,
            true
        );

        // Leaflet para mapas
        wp_register_style('leaflet', 'https://unpkg.com/leaflet@1.9.4/dist/leaflet.css', [], '1.9.4');
        wp_register_script('leaflet', 'https://unpkg.com/leaflet@1.9.4/dist/leaflet.js', [], '1.9.4', true);
    }

    /**
     * Encola assets cuando se necesitan
     */
    private function enqueue_assets($con_mapa = false) {
        wp_enqueue_style('flavor-compostaje-frontend');
        wp_enqueue_script('flavor-compostaje-frontend');

        if ($con_mapa) {
            wp_enqueue_style('leaflet');
            wp_enqueue_script('leaflet');
        }

        wp_localize_script('flavor-compostaje-frontend', 'flavorCompostajeConfig', [
            'ajaxUrl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('flavor_compostaje_nonce'),
            'usuarioId' => get_current_user_id(),
            'materiales' => $this->materiales,
            'niveles' => $this->niveles_gamificacion,
            'strings' => [
                'cargando' => __('Cargando...', 'flavor-platform'),
                'error' => __('Ha ocurrido un error', 'flavor-platform'),
                'exito' => __('Operación realizada con éxito', 'flavor-platform'),
                'confirmar_inscripcion' => __('¿Confirmas inscripción en este turno?', 'flavor-platform'),
                'confirmar_cancelar' => __('¿Cancelar inscripción?', 'flavor-platform'),
                'kg_registrados' => __('kg registrados', 'flavor-platform'),
                'puntos_obtenidos' => __('puntos obtenidos', 'flavor-platform'),
            ],
        ]);
    }

    /**
     * Registra tab en dashboard del usuario
     */
    public function registrar_dashboard_tab($tabs) {
        if (!is_user_logged_in()) {
            return $tabs;
        }

        $tabs['compostaje'] = [
            'titulo' => __('Compostaje', 'flavor-platform'),
            'icono' => 'dashicons-carrot',
            'callback' => [$this, 'render_dashboard_tab'],
            'orden' => 55,
        ];

        return $tabs;
    }

    /**
     * Renderiza el contenido del dashboard tab
     */
    public function render_dashboard_tab() {
        $this->enqueue_assets(true);
        $usuario_id = get_current_user_id();
        $estadisticas = $this->obtener_estadisticas_usuario($usuario_id);
        $aportaciones_recientes = $this->obtener_aportaciones_recientes($usuario_id, 5);
        $proximos_turnos = $this->obtener_proximos_turnos($usuario_id);
        $nivel_actual = $this->calcular_nivel($estadisticas['total_kg']);
        ?>
        <div class="flavor-compostaje-dashboard">
            <!-- KPIs -->
            <div class="flavor-kpi-grid flavor-grid-4">
                <div class="flavor-kpi-card">
                    <div class="flavor-kpi-icon verde">
                        <span class="dashicons dashicons-carrot"></span>
                    </div>
                    <div class="flavor-kpi-content">
                        <span class="flavor-kpi-valor"><?php echo number_format($estadisticas['total_kg'], 1); ?> kg</span>
                        <span class="flavor-kpi-label"><?php _e('Total Aportado', 'flavor-platform'); ?></span>
                    </div>
                </div>

                <div class="flavor-kpi-card">
                    <div class="flavor-kpi-icon azul">
                        <span class="dashicons dashicons-star-filled"></span>
                    </div>
                    <div class="flavor-kpi-content">
                        <span class="flavor-kpi-valor"><?php echo number_format($estadisticas['total_puntos']); ?></span>
                        <span class="flavor-kpi-label"><?php _e('Puntos Acumulados', 'flavor-platform'); ?></span>
                    </div>
                </div>

                <div class="flavor-kpi-card">
                    <div class="flavor-kpi-icon verde-claro">
                        <span class="dashicons dashicons-cloud"></span>
                    </div>
                    <div class="flavor-kpi-content">
                        <span class="flavor-kpi-valor"><?php echo number_format($estadisticas['co2_evitado'], 1); ?> kg</span>
                        <span class="flavor-kpi-label"><?php _e('CO₂ Evitado', 'flavor-platform'); ?></span>
                    </div>
                </div>

                <div class="flavor-kpi-card">
                    <div class="flavor-kpi-icon naranja">
                        <span class="dashicons dashicons-groups"></span>
                    </div>
                    <div class="flavor-kpi-content">
                        <span class="flavor-kpi-valor"><?php echo intval($estadisticas['turnos_completados']); ?></span>
                        <span class="flavor-kpi-label"><?php _e('Turnos Completados', 'flavor-platform'); ?></span>
                    </div>
                </div>
            </div>

            <!-- Nivel y Progreso -->
            <div class="flavor-panel">
                <div class="flavor-panel-header">
                    <h3><?php _e('Mi Nivel de Compostaje', 'flavor-platform'); ?></h3>
                </div>
                <div class="flavor-panel-body">
                    <div class="flavor-nivel-card">
                        <div class="flavor-nivel-icono"><?php echo $nivel_actual['icono']; ?></div>
                        <div class="flavor-nivel-info">
                            <span class="flavor-nivel-nombre"><?php echo esc_html($nivel_actual['nombre']); ?></span>
                            <span class="flavor-nivel-nivel"><?php printf(__('Nivel %d', 'flavor-platform'), $nivel_actual['nivel']); ?></span>
                        </div>
                        <div class="flavor-nivel-progreso">
                            <?php
                            $progreso = $this->calcular_progreso_nivel($estadisticas['total_kg'], $nivel_actual['nivel']);
                            ?>
                            <div class="flavor-progress-bar">
                                <div class="flavor-progress-fill" style="width: <?php echo $progreso['porcentaje']; ?>%"></div>
                            </div>
                            <span class="flavor-progreso-texto">
                                <?php echo number_format($estadisticas['total_kg'], 1); ?> / <?php echo $progreso['siguiente_kg']; ?> kg
                            </span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Acciones Rápidas -->
            <div class="flavor-acciones-grid flavor-grid-3">
                <a href="<?php echo esc_url(add_query_arg('seccion', 'registrar')); ?>" class="flavor-accion-card">
                    <span class="dashicons dashicons-plus-alt2"></span>
                    <span><?php _e('Registrar Aportación', 'flavor-platform'); ?></span>
                </a>
                <a href="<?php echo esc_url(add_query_arg('seccion', 'turnos')); ?>" class="flavor-accion-card">
                    <span class="dashicons dashicons-calendar-alt"></span>
                    <span><?php _e('Ver Turnos', 'flavor-platform'); ?></span>
                </a>
                <a href="<?php echo esc_url(add_query_arg('seccion', 'mapa')); ?>" class="flavor-accion-card">
                    <span class="dashicons dashicons-location"></span>
                    <span><?php _e('Mapa de Puntos', 'flavor-platform'); ?></span>
                </a>
            </div>

            <!-- Aportaciones Recientes -->
            <div class="flavor-panel">
                <div class="flavor-panel-header">
                    <h3><?php _e('Mis Aportaciones Recientes', 'flavor-platform'); ?></h3>
                    <a href="<?php echo esc_url(add_query_arg('seccion', 'historial')); ?>" class="flavor-link">
                        <?php _e('Ver todas', 'flavor-platform'); ?>
                    </a>
                </div>
                <div class="flavor-panel-body">
                    <?php if (!empty($aportaciones_recientes)): ?>
                        <div class="flavor-lista-aportaciones">
                            <?php foreach ($aportaciones_recientes as $aportacion): ?>
                                <div class="flavor-aportacion-row">
                                    <div class="flavor-aportacion-icono categoria-<?php echo esc_attr($aportacion->categoria_material); ?>">
                                        <span class="dashicons dashicons-<?php echo $aportacion->categoria_material === 'verde' ? 'carrot' : ($aportacion->categoria_material === 'marron' ? 'admin-page' : 'star-filled'); ?>"></span>
                                    </div>
                                    <div class="flavor-aportacion-info">
                                        <span class="flavor-aportacion-tipo">
                                            <?php echo esc_html($this->obtener_nombre_material($aportacion->tipo_material)); ?>
                                        </span>
                                        <span class="flavor-aportacion-meta">
                                            <?php echo esc_html($aportacion->nombre_punto); ?> ·
                                            <?php echo human_time_diff(strtotime($aportacion->fecha_aportacion)); ?>
                                        </span>
                                    </div>
                                    <div class="flavor-aportacion-datos">
                                        <span class="flavor-aportacion-kg"><?php echo number_format($aportacion->cantidad_kg, 1); ?> kg</span>
                                        <span class="flavor-aportacion-puntos">+<?php echo intval($aportacion->puntos_obtenidos); ?> pts</span>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php else: ?>
                        <div class="flavor-empty-state">
                            <span class="dashicons dashicons-carrot"></span>
                            <p><?php _e('No tienes aportaciones registradas aún.', 'flavor-platform'); ?></p>
                            <a href="<?php echo esc_url(add_query_arg('seccion', 'registrar')); ?>" class="flavor-btn flavor-btn-primary">
                                <?php _e('Registrar Primera Aportación', 'flavor-platform'); ?>
                            </a>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Próximos Turnos -->
            <div class="flavor-panel">
                <div class="flavor-panel-header">
                    <h3><?php _e('Mis Próximos Turnos', 'flavor-platform'); ?></h3>
                    <a href="<?php echo esc_url(add_query_arg('seccion', 'turnos')); ?>" class="flavor-link">
                        <?php _e('Ver todos', 'flavor-platform'); ?>
                    </a>
                </div>
                <div class="flavor-panel-body">
                    <?php if (!empty($proximos_turnos)): ?>
                        <div class="flavor-lista-turnos">
                            <?php foreach ($proximos_turnos as $turno): ?>
                                <div class="flavor-turno-card">
                                    <div class="flavor-turno-fecha">
                                        <span class="flavor-turno-dia"><?php echo date_i18n('d', strtotime($turno->fecha_turno)); ?></span>
                                        <span class="flavor-turno-mes"><?php echo date_i18n('M', strtotime($turno->fecha_turno)); ?></span>
                                    </div>
                                    <div class="flavor-turno-info">
                                        <span class="flavor-turno-tipo">
                                            <?php echo esc_html(ucfirst($turno->tipo_tarea)); ?>
                                        </span>
                                        <span class="flavor-turno-ubicacion">
                                            <span class="dashicons dashicons-location"></span>
                                            <?php echo esc_html($turno->nombre_punto); ?>
                                        </span>
                                        <span class="flavor-turno-hora">
                                            <span class="dashicons dashicons-clock"></span>
                                            <?php echo esc_html(substr($turno->hora_inicio, 0, 5)); ?> - <?php echo esc_html(substr($turno->hora_fin, 0, 5)); ?>
                                        </span>
                                    </div>
                                    <div class="flavor-turno-estado">
                                        <span class="flavor-badge flavor-badge-<?php echo $turno->estado_inscripcion === 'confirmado' ? 'success' : 'info'; ?>">
                                            <?php echo esc_html(ucfirst($turno->estado_inscripcion)); ?>
                                        </span>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php else: ?>
                        <div class="flavor-empty-state">
                            <span class="dashicons dashicons-calendar-alt"></span>
                            <p><?php _e('No tienes turnos asignados.', 'flavor-platform'); ?></p>
                            <a href="<?php echo esc_url(add_query_arg('seccion', 'turnos')); ?>" class="flavor-btn flavor-btn-outline">
                                <?php _e('Ver Turnos Disponibles', 'flavor-platform'); ?>
                            </a>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Mapa de puntos cercanos -->
            <div class="flavor-panel">
                <div class="flavor-panel-header">
                    <h3><?php _e('Puntos de Compostaje Cercanos', 'flavor-platform'); ?></h3>
                </div>
                <div class="flavor-panel-body">
                    <div id="flavor-compostaje-mapa" class="flavor-mapa-container" data-tipo="cercanos"></div>
                </div>
            </div>
        </div>
        <?php
    }

    /**
     * Shortcode: Mapa de puntos de compostaje
     */
    public function shortcode_mapa($atts) {
        $atts = shortcode_atts([
            'altura' => '400px',
            'tipo' => '',
            'estado' => 'activo',
        ], $atts);

        $this->enqueue_assets(true);
        $puntos = $this->obtener_puntos_compostaje($atts);

        ob_start();
        ?>
        <div class="flavor-compostaje-mapa-wrapper">
            <div id="flavor-compostaje-mapa-<?php echo uniqid(); ?>"
                 class="flavor-mapa-container"
                 style="height: <?php echo esc_attr($atts['altura']); ?>"
                 data-puntos='<?php echo json_encode($puntos); ?>'>
            </div>

            <div class="flavor-mapa-leyenda">
                <span class="flavor-leyenda-item">
                    <span class="flavor-marker verde"></span> <?php _e('Recibiendo', 'flavor-platform'); ?>
                </span>
                <span class="flavor-leyenda-item">
                    <span class="flavor-marker naranja"></span> <?php _e('En proceso', 'flavor-platform'); ?>
                </span>
                <span class="flavor-leyenda-item">
                    <span class="flavor-marker azul"></span> <?php _e('Compost listo', 'flavor-platform'); ?>
                </span>
            </div>
        </div>
        <?php
        return ob_get_clean();
    }

    /**
     * Shortcode: Lista de puntos de compostaje
     */
    public function shortcode_lista_puntos($atts) {
        $atts = shortcode_atts([
            'tipo' => '',
            'limite' => 10,
            'ordenar' => 'nombre',
        ], $atts);

        $this->enqueue_assets();
        $puntos = $this->obtener_puntos_compostaje($atts);

        ob_start();
        ?>
        <div class="flavor-compostaje-lista">
            <?php if (!empty($puntos)): ?>
                <div class="flavor-puntos-grid">
                    <?php foreach ($puntos as $punto): ?>
                        <div class="flavor-punto-card" data-id="<?php echo esc_attr($punto->id); ?>">
                            <?php if ($punto->foto_url): ?>
                                <div class="flavor-punto-imagen">
                                    <img src="<?php echo esc_url($punto->foto_url); ?>" alt="">
                                </div>
                            <?php endif; ?>

                            <div class="flavor-punto-content">
                                <div class="flavor-punto-header">
                                    <h4><?php echo esc_html($punto->nombre); ?></h4>
                                    <span class="flavor-badge flavor-badge-<?php echo $punto->fase_actual === 'listo' ? 'success' : ($punto->fase_actual === 'recepcion' ? 'info' : 'warning'); ?>">
                                        <?php echo esc_html(ucfirst($punto->fase_actual)); ?>
                                    </span>
                                </div>

                                <p class="flavor-punto-direccion">
                                    <span class="dashicons dashicons-location"></span>
                                    <?php echo esc_html($punto->direccion); ?>
                                </p>

                                <div class="flavor-punto-stats">
                                    <span class="flavor-stat">
                                        <span class="dashicons dashicons-chart-bar"></span>
                                        <?php echo intval($punto->nivel_llenado_pct); ?>% lleno
                                    </span>
                                    <span class="flavor-stat">
                                        <span class="dashicons dashicons-groups"></span>
                                        <?php echo esc_html(ucfirst($punto->tipo)); ?>
                                    </span>
                                </div>

                                <?php if ($punto->horario_apertura): ?>
                                    <p class="flavor-punto-horario">
                                        <span class="dashicons dashicons-clock"></span>
                                        <?php echo esc_html($punto->horario_apertura); ?>
                                    </p>
                                <?php endif; ?>

                                <div class="flavor-punto-actions">
                                    <button class="flavor-btn flavor-btn-sm flavor-btn-primary flavor-btn-aportar"
                                            data-punto-id="<?php echo esc_attr($punto->id); ?>">
                                        <span class="dashicons dashicons-plus-alt2"></span>
                                        <?php _e('Aportar', 'flavor-platform'); ?>
                                    </button>
                                    <button class="flavor-btn flavor-btn-sm flavor-btn-outline flavor-btn-ver-mapa"
                                            data-lat="<?php echo esc_attr($punto->latitud); ?>"
                                            data-lng="<?php echo esc_attr($punto->longitud); ?>">
                                        <span class="dashicons dashicons-location"></span>
                                        <?php _e('Ver en mapa', 'flavor-platform'); ?>
                                    </button>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                <div class="flavor-empty-state">
                    <span class="dashicons dashicons-location-alt"></span>
                    <p><?php _e('No hay puntos de compostaje disponibles.', 'flavor-platform'); ?></p>
                </div>
            <?php endif; ?>
        </div>
        <?php
        return ob_get_clean();
    }

    /**
     * Shortcode: Formulario de registro de aportación
     */
    public function shortcode_registrar($atts) {
        if (!is_user_logged_in()) {
            return '<div class="flavor-notice flavor-notice-warning">' .
                   __('Debes iniciar sesión para registrar aportaciones.', 'flavor-platform') .
                   '</div>';
        }

        $atts = shortcode_atts([
            'punto_id' => 0,
        ], $atts);

        $this->enqueue_assets();
        $puntos = $this->obtener_puntos_compostaje(['estado' => 'activo', 'fase' => 'recepcion']);

        ob_start();
        ?>
        <div class="flavor-compostaje-registrar">
            <form id="flavor-form-aportacion" class="flavor-form">
                <?php wp_nonce_field('flavor_compostaje_nonce', 'compostaje_nonce'); ?>

                <div class="flavor-form-group">
                    <label for="punto_id"><?php _e('Punto de Compostaje', 'flavor-platform'); ?></label>
                    <select name="punto_id" id="punto_id" required class="flavor-select">
                        <option value=""><?php _e('Selecciona un punto...', 'flavor-platform'); ?></option>
                        <?php foreach ($puntos as $punto): ?>
                            <option value="<?php echo esc_attr($punto->id); ?>"
                                    <?php selected($atts['punto_id'], $punto->id); ?>>
                                <?php echo esc_html($punto->nombre); ?> - <?php echo esc_html($punto->direccion); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="flavor-form-group">
                    <label><?php _e('Categoría del Material', 'flavor-platform'); ?></label>
                    <div class="flavor-radio-cards">
                        <label class="flavor-radio-card">
                            <input type="radio" name="categoria" value="verde" checked>
                            <span class="flavor-radio-content verde">
                                <span class="dashicons dashicons-carrot"></span>
                                <span><?php _e('Verde', 'flavor-platform'); ?></span>
                                <small><?php _e('Restos frescos', 'flavor-platform'); ?></small>
                            </span>
                        </label>
                        <label class="flavor-radio-card">
                            <input type="radio" name="categoria" value="marron">
                            <span class="flavor-radio-content marron">
                                <span class="dashicons dashicons-admin-page"></span>
                                <span><?php _e('Marrón', 'flavor-platform'); ?></span>
                                <small><?php _e('Secos y leñosos', 'flavor-platform'); ?></small>
                            </span>
                        </label>
                        <label class="flavor-radio-card">
                            <input type="radio" name="categoria" value="especial">
                            <span class="flavor-radio-content especial">
                                <span class="dashicons dashicons-star-filled"></span>
                                <span><?php _e('Especial', 'flavor-platform'); ?></span>
                                <small><?php _e('Cáscaras, té...', 'flavor-platform'); ?></small>
                            </span>
                        </label>
                    </div>
                </div>

                <div class="flavor-form-group">
                    <label for="tipo_material"><?php _e('Tipo de Material', 'flavor-platform'); ?></label>
                    <select name="tipo_material" id="tipo_material" required class="flavor-select">
                        <option value=""><?php _e('Selecciona el tipo...', 'flavor-platform'); ?></option>
                    </select>
                    <div id="material-info" class="flavor-form-hint"></div>
                </div>

                <div class="flavor-form-group">
                    <label for="cantidad_kg"><?php _e('Cantidad (kg)', 'flavor-platform'); ?></label>
                    <div class="flavor-input-group">
                        <input type="number" name="cantidad_kg" id="cantidad_kg"
                               min="0.1" max="50" step="0.1" required
                               class="flavor-input" placeholder="0.0">
                        <span class="flavor-input-addon">kg</span>
                    </div>
                    <div class="flavor-form-hint">
                        <?php _e('Peso aproximado del material que aportas', 'flavor-platform'); ?>
                    </div>
                </div>

                <div class="flavor-form-group">
                    <label for="foto"><?php _e('Foto (opcional)', 'flavor-platform'); ?></label>
                    <input type="file" name="foto" id="foto" accept="image/*" class="flavor-input-file">
                    <div id="foto-preview" class="flavor-foto-preview"></div>
                </div>

                <div class="flavor-form-group">
                    <label for="notas"><?php _e('Notas (opcional)', 'flavor-platform'); ?></label>
                    <textarea name="notas" id="notas" rows="2" class="flavor-textarea"
                              placeholder="<?php _e('Añade comentarios si lo deseas...', 'flavor-platform'); ?>"></textarea>
                </div>

                <!-- Preview de puntos -->
                <div id="puntos-preview" class="flavor-puntos-preview" style="display: none;">
                    <div class="flavor-preview-header">
                        <span class="dashicons dashicons-star-filled"></span>
                        <?php _e('Puntos a obtener:', 'flavor-platform'); ?>
                    </div>
                    <div class="flavor-preview-value">
                        <span id="puntos-estimados">0</span> <?php _e('puntos', 'flavor-platform'); ?>
                    </div>
                </div>

                <button type="submit" class="flavor-btn flavor-btn-primary flavor-btn-lg flavor-btn-block">
                    <span class="dashicons dashicons-plus-alt2"></span>
                    <?php _e('Registrar Aportación', 'flavor-platform'); ?>
                </button>
            </form>
        </div>
        <?php
        return ob_get_clean();
    }

    /**
     * Shortcode: Mis aportaciones
     */
    public function shortcode_mis_aportaciones($atts) {
        if (!is_user_logged_in()) {
            return '<div class="flavor-notice flavor-notice-warning">' .
                   __('Debes iniciar sesión para ver tus aportaciones.', 'flavor-platform') .
                   '</div>';
        }

        $atts = shortcode_atts([
            'limite' => 20,
        ], $atts);

        $this->enqueue_assets();
        $usuario_id = get_current_user_id();
        $aportaciones = $this->obtener_aportaciones_recientes($usuario_id, $atts['limite']);
        $estadisticas = $this->obtener_estadisticas_usuario($usuario_id);

        ob_start();
        ?>
        <div class="flavor-compostaje-historial">
            <!-- Resumen -->
            <div class="flavor-resumen-grid flavor-grid-4">
                <div class="flavor-resumen-item">
                    <span class="flavor-resumen-valor"><?php echo count($aportaciones); ?></span>
                    <span class="flavor-resumen-label"><?php _e('Aportaciones', 'flavor-platform'); ?></span>
                </div>
                <div class="flavor-resumen-item">
                    <span class="flavor-resumen-valor"><?php echo number_format($estadisticas['total_kg'], 1); ?> kg</span>
                    <span class="flavor-resumen-label"><?php _e('Total Aportado', 'flavor-platform'); ?></span>
                </div>
                <div class="flavor-resumen-item">
                    <span class="flavor-resumen-valor"><?php echo number_format($estadisticas['total_puntos']); ?></span>
                    <span class="flavor-resumen-label"><?php _e('Puntos', 'flavor-platform'); ?></span>
                </div>
                <div class="flavor-resumen-item">
                    <span class="flavor-resumen-valor"><?php echo number_format($estadisticas['co2_evitado'], 1); ?> kg</span>
                    <span class="flavor-resumen-label"><?php _e('CO₂ Evitado', 'flavor-platform'); ?></span>
                </div>
            </div>

            <!-- Lista de aportaciones -->
            <?php if (!empty($aportaciones)): ?>
                <div class="flavor-tabla-responsive">
                    <table class="flavor-tabla">
                        <thead>
                            <tr>
                                <th><?php _e('Fecha', 'flavor-platform'); ?></th>
                                <th><?php _e('Punto', 'flavor-platform'); ?></th>
                                <th><?php _e('Material', 'flavor-platform'); ?></th>
                                <th><?php _e('Cantidad', 'flavor-platform'); ?></th>
                                <th><?php _e('Puntos', 'flavor-platform'); ?></th>
                                <th><?php _e('Estado', 'flavor-platform'); ?></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($aportaciones as $aportacion): ?>
                                <tr>
                                    <td><?php echo date_i18n('d/m/Y H:i', strtotime($aportacion->fecha_aportacion)); ?></td>
                                    <td><?php echo esc_html($aportacion->nombre_punto); ?></td>
                                    <td>
                                        <span class="flavor-badge flavor-badge-<?php echo esc_attr($aportacion->categoria_material); ?>">
                                            <?php echo esc_html($this->obtener_nombre_material($aportacion->tipo_material)); ?>
                                        </span>
                                    </td>
                                    <td><?php echo number_format($aportacion->cantidad_kg, 1); ?> kg</td>
                                    <td>
                                        <span class="flavor-puntos">+<?php echo intval($aportacion->puntos_obtenidos); ?></span>
                                    </td>
                                    <td>
                                        <?php if ($aportacion->validado): ?>
                                            <span class="flavor-badge flavor-badge-success">
                                                <span class="dashicons dashicons-yes"></span>
                                            </span>
                                        <?php else: ?>
                                            <span class="flavor-badge flavor-badge-warning">
                                                <?php _e('Pendiente', 'flavor-platform'); ?>
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
                    <p><?php _e('No tienes aportaciones registradas.', 'flavor-platform'); ?></p>
                </div>
            <?php endif; ?>
        </div>
        <?php
        return ob_get_clean();
    }

    /**
     * Shortcode: Turnos de mantenimiento
     */
    public function shortcode_turnos($atts) {
        $atts = shortcode_atts([
            'punto_id' => 0,
            'dias' => 30,
            'mostrar_pasados' => false,
        ], $atts);

        $this->enqueue_assets();
        $turnos = $this->obtener_turnos_disponibles($atts);
        $usuario_id = get_current_user_id();

        ob_start();
        ?>
        <div class="flavor-compostaje-turnos">
            <?php if (!empty($turnos)): ?>
                <div class="flavor-turnos-grid">
                    <?php foreach ($turnos as $turno): ?>
                        <?php
                        $esta_inscrito = $usuario_id ? $this->usuario_inscrito_turno($usuario_id, $turno->id) : false;
                        $plazas_libres = $turno->plazas_disponibles - $turno->plazas_ocupadas;
                        ?>
                        <div class="flavor-turno-card <?php echo $esta_inscrito ? 'inscrito' : ''; ?>"
                             data-turno-id="<?php echo esc_attr($turno->id); ?>">

                            <div class="flavor-turno-fecha-block">
                                <span class="flavor-turno-dia"><?php echo date_i18n('d', strtotime($turno->fecha_turno)); ?></span>
                                <span class="flavor-turno-mes"><?php echo date_i18n('M', strtotime($turno->fecha_turno)); ?></span>
                                <span class="flavor-turno-anio"><?php echo date_i18n('Y', strtotime($turno->fecha_turno)); ?></span>
                            </div>

                            <div class="flavor-turno-content">
                                <div class="flavor-turno-header">
                                    <span class="flavor-turno-tipo-badge tipo-<?php echo esc_attr($turno->tipo_tarea); ?>">
                                        <?php echo esc_html(ucfirst($turno->tipo_tarea)); ?>
                                    </span>
                                    <span class="flavor-turno-puntos">
                                        <span class="dashicons dashicons-star-filled"></span>
                                        +<?php echo intval($turno->puntos_recompensa); ?>
                                    </span>
                                </div>

                                <h4 class="flavor-turno-ubicacion">
                                    <span class="dashicons dashicons-location"></span>
                                    <?php echo esc_html($turno->nombre_punto); ?>
                                </h4>

                                <div class="flavor-turno-detalles">
                                    <span class="flavor-turno-hora">
                                        <span class="dashicons dashicons-clock"></span>
                                        <?php echo esc_html(substr($turno->hora_inicio, 0, 5)); ?> - <?php echo esc_html(substr($turno->hora_fin, 0, 5)); ?>
                                    </span>
                                    <span class="flavor-turno-plazas">
                                        <span class="dashicons dashicons-groups"></span>
                                        <?php echo intval($plazas_libres); ?>/<?php echo intval($turno->plazas_disponibles); ?> <?php _e('plazas', 'flavor-platform'); ?>
                                    </span>
                                </div>

                                <?php if ($turno->descripcion): ?>
                                    <p class="flavor-turno-descripcion"><?php echo esc_html($turno->descripcion); ?></p>
                                <?php endif; ?>

                                <div class="flavor-turno-actions">
                                    <?php if (!is_user_logged_in()): ?>
                                        <span class="flavor-notice-inline">
                                            <?php _e('Inicia sesión para inscribirte', 'flavor-platform'); ?>
                                        </span>
                                    <?php elseif ($esta_inscrito): ?>
                                        <button class="flavor-btn flavor-btn-danger flavor-btn-cancelar-turno"
                                                data-turno-id="<?php echo esc_attr($turno->id); ?>">
                                            <span class="dashicons dashicons-no-alt"></span>
                                            <?php _e('Cancelar Inscripción', 'flavor-platform'); ?>
                                        </button>
                                    <?php elseif ($plazas_libres > 0): ?>
                                        <button class="flavor-btn flavor-btn-primary flavor-btn-inscribir-turno"
                                                data-turno-id="<?php echo esc_attr($turno->id); ?>">
                                            <span class="dashicons dashicons-yes-alt"></span>
                                            <?php _e('Inscribirme', 'flavor-platform'); ?>
                                        </button>
                                    <?php else: ?>
                                        <span class="flavor-badge flavor-badge-danger">
                                            <?php _e('Sin plazas', 'flavor-platform'); ?>
                                        </span>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                <div class="flavor-empty-state">
                    <span class="dashicons dashicons-calendar-alt"></span>
                    <p><?php _e('No hay turnos programados próximamente.', 'flavor-platform'); ?></p>
                </div>
            <?php endif; ?>
        </div>
        <?php
        return ob_get_clean();
    }

    /**
     * Shortcode: Guía de compostaje
     */
    public function shortcode_guia($atts) {
        $this->enqueue_assets();

        ob_start();
        ?>
        <div class="flavor-compostaje-guia">
            <!-- Sección: Qué compostar -->
            <div class="flavor-guia-seccion">
                <h3><span class="dashicons dashicons-yes-alt"></span> <?php _e('¿Qué puedo compostar?', 'flavor-platform'); ?></h3>

                <div class="flavor-guia-grid">
                    <!-- Material Verde -->
                    <div class="flavor-guia-card verde">
                        <h4><?php _e('Material Verde', 'flavor-platform'); ?></h4>
                        <p><?php _e('Rico en nitrógeno, aporta humedad', 'flavor-platform'); ?></p>
                        <ul>
                            <?php foreach ($this->materiales['verde'] as $codigo => $material): ?>
                                <li><?php echo esc_html($material['nombre']); ?></li>
                            <?php endforeach; ?>
                        </ul>
                    </div>

                    <!-- Material Marrón -->
                    <div class="flavor-guia-card marron">
                        <h4><?php _e('Material Marrón', 'flavor-platform'); ?></h4>
                        <p><?php _e('Rico en carbono, da estructura', 'flavor-platform'); ?></p>
                        <ul>
                            <?php foreach ($this->materiales['marron'] as $codigo => $material): ?>
                                <li><?php echo esc_html($material['nombre']); ?></li>
                            <?php endforeach; ?>
                        </ul>
                    </div>

                    <!-- Material Especial -->
                    <div class="flavor-guia-card especial">
                        <h4><?php _e('Material Especial', 'flavor-platform'); ?></h4>
                        <p><?php _e('Aporta minerales específicos', 'flavor-platform'); ?></p>
                        <ul>
                            <?php foreach ($this->materiales['especial'] as $codigo => $material): ?>
                                <li><?php echo esc_html($material['nombre']); ?></li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                </div>
            </div>

            <!-- Sección: NO compostar -->
            <div class="flavor-guia-seccion no-compostar">
                <h3><span class="dashicons dashicons-dismiss"></span> <?php _e('¿Qué NO debo compostar?', 'flavor-platform'); ?></h3>
                <div class="flavor-guia-lista-no">
                    <ul>
                        <li><?php _e('Carne, pescado o huesos', 'flavor-platform'); ?></li>
                        <li><?php _e('Lácteos y grasas', 'flavor-platform'); ?></li>
                        <li><?php _e('Plantas enfermas', 'flavor-platform'); ?></li>
                        <li><?php _e('Excrementos de mascotas', 'flavor-platform'); ?></li>
                        <li><?php _e('Cenizas de carbón', 'flavor-platform'); ?></li>
                        <li><?php _e('Pañales o compresas', 'flavor-platform'); ?></li>
                    </ul>
                </div>
            </div>

            <!-- Consejos -->
            <div class="flavor-guia-seccion consejos">
                <h3><span class="dashicons dashicons-lightbulb"></span> <?php _e('Consejos Prácticos', 'flavor-platform'); ?></h3>
                <div class="flavor-consejos-grid">
                    <div class="flavor-consejo">
                        <span class="dashicons dashicons-chart-pie"></span>
                        <p><?php _e('Mantén una proporción 2:1 de material marrón y verde', 'flavor-platform'); ?></p>
                    </div>
                    <div class="flavor-consejo">
                        <span class="dashicons dashicons-image-filter"></span>
                        <p><?php _e('Trocea los materiales grandes para acelerar el proceso', 'flavor-platform'); ?></p>
                    </div>
                    <div class="flavor-consejo">
                        <span class="dashicons dashicons-backup"></span>
                        <p><?php _e('Voltea la pila regularmente para oxigenar', 'flavor-platform'); ?></p>
                    </div>
                    <div class="flavor-consejo">
                        <span class="dashicons dashicons-admin-site-alt3"></span>
                        <p><?php _e('Mantén la humedad como una esponja escurrida', 'flavor-platform'); ?></p>
                    </div>
                </div>
            </div>
        </div>
        <?php
        return ob_get_clean();
    }

    /**
     * Shortcode: Ranking
     */
    public function shortcode_ranking($atts) {
        $atts = shortcode_atts([
            'limite' => 10,
            'periodo' => 'total', // total, mes, semana
        ], $atts);

        $this->enqueue_assets();
        $ranking = $this->obtener_ranking($atts);
        $usuario_id = get_current_user_id();

        ob_start();
        ?>
        <div class="flavor-compostaje-ranking">
            <!-- Filtros -->
            <div class="flavor-ranking-filtros">
                <button class="flavor-btn flavor-btn-sm <?php echo $atts['periodo'] === 'total' ? 'active' : ''; ?>"
                        data-periodo="total"><?php _e('Total', 'flavor-platform'); ?></button>
                <button class="flavor-btn flavor-btn-sm <?php echo $atts['periodo'] === 'mes' ? 'active' : ''; ?>"
                        data-periodo="mes"><?php _e('Este Mes', 'flavor-platform'); ?></button>
                <button class="flavor-btn flavor-btn-sm <?php echo $atts['periodo'] === 'semana' ? 'active' : ''; ?>"
                        data-periodo="semana"><?php _e('Esta Semana', 'flavor-platform'); ?></button>
            </div>

            <!-- Top 3 -->
            <?php if (count($ranking) >= 3): ?>
                <div class="flavor-ranking-podio">
                    <?php foreach (array_slice($ranking, 0, 3) as $posicion => $usuario): ?>
                        <div class="flavor-podio-item posicion-<?php echo ($posicion + 1); ?>">
                            <div class="flavor-podio-medalla"><?php echo ($posicion + 1); ?></div>
                            <div class="flavor-podio-avatar">
                                <?php echo get_avatar($usuario->usuario_id, 60); ?>
                            </div>
                            <div class="flavor-podio-nombre">
                                <?php echo esc_html($usuario->display_name); ?>
                            </div>
                            <div class="flavor-podio-stats">
                                <span class="flavor-podio-kg"><?php echo number_format($usuario->total_kg, 1); ?> kg</span>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>

            <!-- Resto del ranking -->
            <?php if (count($ranking) > 3): ?>
                <div class="flavor-ranking-lista">
                    <table class="flavor-tabla">
                        <thead>
                            <tr>
                                <th>#</th>
                                <th><?php _e('Usuario', 'flavor-platform'); ?></th>
                                <th><?php _e('Nivel', 'flavor-platform'); ?></th>
                                <th><?php _e('Kg Aportados', 'flavor-platform'); ?></th>
                                <th><?php _e('Puntos', 'flavor-platform'); ?></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach (array_slice($ranking, 3) as $posicion => $usuario): ?>
                                <?php
                                $nivel = $this->calcular_nivel($usuario->total_kg);
                                $es_actual = $usuario_id == $usuario->usuario_id;
                                ?>
                                <tr class="<?php echo $es_actual ? 'destacado' : ''; ?>">
                                    <td><?php echo ($posicion + 4); ?></td>
                                    <td>
                                        <div class="flavor-usuario-mini">
                                            <?php echo get_avatar($usuario->usuario_id, 32); ?>
                                            <span><?php echo esc_html($usuario->display_name); ?></span>
                                        </div>
                                    </td>
                                    <td>
                                        <span class="flavor-nivel-badge">
                                            <?php echo $nivel['icono']; ?> <?php echo esc_html($nivel['nombre']); ?>
                                        </span>
                                    </td>
                                    <td><?php echo number_format($usuario->total_kg, 1); ?> kg</td>
                                    <td><?php echo number_format($usuario->total_puntos); ?></td>
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
     * Shortcode: Mi balance
     */
    public function shortcode_mi_balance($atts) {
        if (!is_user_logged_in()) {
            return '';
        }

        $this->enqueue_assets();
        $usuario_id = get_current_user_id();
        $estadisticas = $this->obtener_estadisticas_usuario($usuario_id);
        $nivel = $this->calcular_nivel($estadisticas['total_kg']);

        ob_start();
        ?>
        <div class="flavor-compostaje-balance-mini">
            <div class="flavor-balance-nivel">
                <span class="flavor-nivel-icono"><?php echo $nivel['icono']; ?></span>
                <span class="flavor-nivel-nombre"><?php echo esc_html($nivel['nombre']); ?></span>
            </div>
            <div class="flavor-balance-stats">
                <span class="flavor-stat">
                    <strong><?php echo number_format($estadisticas['total_kg'], 1); ?></strong> kg
                </span>
                <span class="flavor-stat">
                    <strong><?php echo number_format($estadisticas['total_puntos']); ?></strong> pts
                </span>
            </div>
        </div>
        <?php
        return ob_get_clean();
    }

    /**
     * Shortcode: Dashboard completo
     */
    public function shortcode_dashboard($atts) {
        if (!is_user_logged_in()) {
            return '<div class="flavor-notice flavor-notice-warning">' .
                   __('Debes iniciar sesión para acceder al dashboard.', 'flavor-platform') .
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
     * AJAX: Obtener datos del dashboard
     */
    public function ajax_dashboard_data() {
        check_ajax_referer('flavor_compostaje_nonce', 'nonce');

        $usuario_id = get_current_user_id();
        if (!$usuario_id) {
            wp_send_json_error(['message' => __('No autorizado', 'flavor-platform')]);
        }

        $estadisticas = $this->obtener_estadisticas_usuario($usuario_id);
        $nivel = $this->calcular_nivel($estadisticas['total_kg']);

        wp_send_json_success([
            'estadisticas' => $estadisticas,
            'nivel' => $nivel,
        ]);
    }

    /**
     * AJAX: Registrar aportación
     */
    public function ajax_registrar_aportacion() {
        check_ajax_referer('flavor_compostaje_nonce', 'nonce');

        $usuario_id = get_current_user_id();
        if (!$usuario_id) {
            wp_send_json_error(['message' => __('Debes iniciar sesión', 'flavor-platform')]);
        }

        global $wpdb;
        $tabla_aportaciones = $wpdb->prefix . 'flavor_aportaciones_compost';

        $punto_id = intval($_POST['punto_id'] ?? 0);
        $tipo_material = sanitize_text_field($_POST['tipo_material'] ?? '');
        $categoria = sanitize_text_field($_POST['categoria'] ?? 'verde');
        $cantidad_kg = floatval($_POST['cantidad_kg'] ?? 0);
        $notas = sanitize_textarea_field($_POST['notas'] ?? '');

        // Validaciones
        if (!$punto_id || !$tipo_material || $cantidad_kg <= 0) {
            wp_send_json_error(['message' => __('Datos incompletos', 'flavor-platform')]);
        }

        if ($cantidad_kg > 50) {
            wp_send_json_error(['message' => __('Cantidad máxima: 50 kg', 'flavor-platform')]);
        }

        // Calcular puntos
        $puntos_base = $this->obtener_puntos_material($tipo_material, $categoria);
        $puntos_obtenidos = round($puntos_base * $cantidad_kg);

        // Bonus por nivel
        $stats = $this->obtener_estadisticas_usuario($usuario_id);
        $nivel = $this->calcular_nivel($stats['total_kg']);
        $bonus = isset($this->niveles_gamificacion[$nivel['nivel']]) ?
                 ($this->niveles_gamificacion[$nivel['nivel']]['puntos_bonus'] ?? 0) : 0;
        $puntos_obtenidos += $bonus;

        // CO2 evitado (0.5 kg CO2 por kg de materia orgánica)
        $co2_evitado = $cantidad_kg * 0.5;

        // Manejar foto
        $foto_url = '';
        if (!empty($_FILES['foto']['tmp_name'])) {
            require_once(ABSPATH . 'wp-admin/includes/image.php');
            require_once(ABSPATH . 'wp-admin/includes/file.php');
            require_once(ABSPATH . 'wp-admin/includes/media.php');

            $attachment_id = media_handle_upload('foto', 0);
            if (!is_wp_error($attachment_id)) {
                $foto_url = wp_get_attachment_url($attachment_id);
            }
        }

        // Insertar
        $resultado = $wpdb->insert($tabla_aportaciones, [
            'punto_id' => $punto_id,
            'usuario_id' => $usuario_id,
            'tipo_material' => $tipo_material,
            'categoria_material' => $categoria,
            'cantidad_kg' => $cantidad_kg,
            'puntos_obtenidos' => $puntos_obtenidos,
            'bonus_nivel' => $bonus,
            'foto_url' => $foto_url,
            'notas' => $notas,
            'validado' => 1,
            'co2_evitado_kg' => $co2_evitado,
            'fecha_aportacion' => current_time('mysql'),
        ], ['%d', '%d', '%s', '%s', '%f', '%d', '%d', '%s', '%s', '%d', '%f', '%s']);

        if ($resultado) {
            // Actualizar nivel de llenado del punto
            $this->actualizar_nivel_punto($punto_id, $cantidad_kg);

            wp_send_json_success([
                'message' => __('¡Aportación registrada!', 'flavor-platform'),
                'puntos' => $puntos_obtenidos,
                'co2_evitado' => $co2_evitado,
            ]);
        } else {
            wp_send_json_error(['message' => __('Error al guardar', 'flavor-platform')]);
        }
    }

    /**
     * AJAX: Inscribirse a turno
     */
    public function ajax_inscribir_turno() {
        check_ajax_referer('flavor_compostaje_nonce', 'nonce');

        $usuario_id = get_current_user_id();
        if (!$usuario_id) {
            wp_send_json_error(['message' => __('Debes iniciar sesión', 'flavor-platform')]);
        }

        global $wpdb;
        $turno_id = intval($_POST['turno_id'] ?? 0);

        if (!$turno_id) {
            wp_send_json_error(['message' => __('Turno no válido', 'flavor-platform')]);
        }

        // Verificar que hay plazas
        $turno = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$wpdb->prefix}flavor_turnos_compostaje WHERE id = %d",
            $turno_id
        ));

        if (!$turno || $turno->plazas_ocupadas >= $turno->plazas_disponibles) {
            wp_send_json_error(['message' => __('No hay plazas disponibles', 'flavor-platform')]);
        }

        // Verificar que no está inscrito ya
        if ($this->usuario_inscrito_turno($usuario_id, $turno_id)) {
            wp_send_json_error(['message' => __('Ya estás inscrito en este turno', 'flavor-platform')]);
        }

        // Inscribir
        $tabla_inscripciones = $wpdb->prefix . 'flavor_inscripciones_turno';
        $resultado = $wpdb->insert($tabla_inscripciones, [
            'turno_id' => $turno_id,
            'usuario_id' => $usuario_id,
            'estado' => 'inscrito',
            'fecha_inscripcion' => current_time('mysql'),
        ], ['%d', '%d', '%s', '%s']);

        if ($resultado) {
            // Incrementar plazas ocupadas
            $wpdb->query($wpdb->prepare(
                "UPDATE {$wpdb->prefix}flavor_turnos_compostaje SET plazas_ocupadas = plazas_ocupadas + 1 WHERE id = %d",
                $turno_id
            ));

            wp_send_json_success([
                'message' => __('¡Inscripción realizada!', 'flavor-platform'),
            ]);
        } else {
            wp_send_json_error(['message' => __('Error al inscribirse', 'flavor-platform')]);
        }
    }

    /**
     * AJAX: Cancelar inscripción a turno
     */
    public function ajax_cancelar_inscripcion() {
        check_ajax_referer('flavor_compostaje_nonce', 'nonce');

        $usuario_id = get_current_user_id();
        if (!$usuario_id) {
            wp_send_json_error(['message' => __('No autorizado', 'flavor-platform')]);
        }

        global $wpdb;
        $turno_id = intval($_POST['turno_id'] ?? 0);

        $tabla_inscripciones = $wpdb->prefix . 'flavor_inscripciones_turno';

        $resultado = $wpdb->update(
            $tabla_inscripciones,
            ['estado' => 'cancelado'],
            ['turno_id' => $turno_id, 'usuario_id' => $usuario_id],
            ['%s'],
            ['%d', '%d']
        );

        if ($resultado !== false) {
            // Decrementar plazas ocupadas
            $wpdb->query($wpdb->prepare(
                "UPDATE {$wpdb->prefix}flavor_turnos_compostaje SET plazas_ocupadas = GREATEST(0, plazas_ocupadas - 1) WHERE id = %d",
                $turno_id
            ));

            wp_send_json_success([
                'message' => __('Inscripción cancelada', 'flavor-platform'),
            ]);
        } else {
            wp_send_json_error(['message' => __('Error al cancelar', 'flavor-platform')]);
        }
    }

    /**
     * AJAX: Buscar puntos de compostaje
     */
    public function ajax_buscar_puntos() {
        $lat = floatval($_POST['lat'] ?? 0);
        $lng = floatval($_POST['lng'] ?? 0);
        $radio = intval($_POST['radio'] ?? 5); // km

        global $wpdb;
        $tabla = $wpdb->prefix . 'flavor_puntos_compostaje';

        // Búsqueda por proximidad usando Haversine
        $puntos = $wpdb->get_results($wpdb->prepare(
            "SELECT *,
             (6371 * acos(cos(radians(%f)) * cos(radians(latitud)) * cos(radians(longitud) - radians(%f)) + sin(radians(%f)) * sin(radians(latitud)))) AS distancia
             FROM $tabla
             WHERE estado = 'activo'
             HAVING distancia < %d
             ORDER BY distancia
             LIMIT 20",
            $lat, $lng, $lat, $radio
        ));

        wp_send_json_success([
            'puntos' => $puntos,
        ]);
    }

    /**
     * AJAX: Solicitar compost maduro
     */
    public function ajax_solicitar_compost() {
        check_ajax_referer('flavor_compostaje_nonce', 'nonce');

        global $wpdb;

        $usuario_id = get_current_user_id();
        if (!$usuario_id) {
            wp_send_json_error(['message' => __('Debes iniciar sesión', 'flavor-platform')]);
        }

        $punto_id = intval($_POST['punto_id'] ?? 0);
        $cantidad_kg = floatval($_POST['cantidad_kg'] ?? 0);
        $notas = sanitize_textarea_field($_POST['notas'] ?? '');

        if ($punto_id <= 0 || $cantidad_kg <= 0) {
            wp_send_json_error(['message' => __('Debes indicar un punto y una cantidad válidos.', 'flavor-platform')]);
        }

        $tabla_puntos = $wpdb->prefix . 'flavor_puntos_compostaje';
        $tabla_solicitudes = $wpdb->prefix . 'flavor_solicitudes_compost';

        $punto = $wpdb->get_row($wpdb->prepare(
            "SELECT id, nombre, estado FROM {$tabla_puntos} WHERE id = %d",
            $punto_id
        ));

        if (!$punto || $punto->estado !== 'activo') {
            wp_send_json_error(['message' => __('El punto de compostaje no está disponible.', 'flavor-platform')]);
        }

        // Verificar que el usuario tiene suficientes kg aportados
        $stats = $this->obtener_estadisticas_usuario($usuario_id);
        $kg_disponibles = $stats['total_kg'] - ($stats['kg_recogidos'] ?? 0);

        if ($cantidad_kg > $kg_disponibles) {
            wp_send_json_error([
                'message' => sprintf(
                    __('Solo puedes solicitar hasta %.1f kg', 'flavor-platform'),
                    $kg_disponibles
                ),
            ]);
        }

        $solicitud_existente = $wpdb->get_var($wpdb->prepare(
            "SELECT id
             FROM {$tabla_solicitudes}
             WHERE usuario_id = %d
               AND punto_id = %d
               AND estado IN ('pendiente', 'aprobada')
             ORDER BY fecha_solicitud DESC
             LIMIT 1",
            $usuario_id,
            $punto_id
        ));

        if ($solicitud_existente) {
            wp_send_json_error([
                'message' => __('Ya tienes una solicitud activa para este punto de compostaje.', 'flavor-platform'),
            ]);
        }

        $inserted = $wpdb->insert(
            $tabla_solicitudes,
            [
                'punto_id' => $punto_id,
                'usuario_id' => $usuario_id,
                'cantidad_kg' => $cantidad_kg,
                'estado' => 'pendiente',
                'notas_usuario' => $notas,
                'fecha_solicitud' => current_time('mysql'),
            ],
            ['%d', '%d', '%f', '%s', '%s', '%s']
        );

        if (!$inserted) {
            wp_send_json_error([
                'message' => __('No se pudo registrar la solicitud de compost.', 'flavor-platform'),
            ]);
        }

        do_action('flavor_compostaje_solicitud_creada', $wpdb->insert_id, [
            'punto_id' => $punto_id,
            'punto_nombre' => $punto->nombre,
            'usuario_id' => $usuario_id,
            'cantidad_kg' => $cantidad_kg,
            'notas' => $notas,
        ]);

        wp_send_json_success([
            'message' => __('Solicitud enviada. Quedó registrada para revisión del punto de compostaje.', 'flavor-platform'),
        ]);
    }

    // ==========================================
    // MÉTODOS AUXILIARES
    // ==========================================

    /**
     * Obtiene estadísticas de un usuario
     */
    private function obtener_estadisticas_usuario($usuario_id) {
        global $wpdb;

        $tabla_aportaciones = $wpdb->prefix . 'flavor_aportaciones_compost';
        $tabla_inscripciones = $wpdb->prefix . 'flavor_inscripciones_turno';
        $tabla_solicitudes = $wpdb->prefix . 'flavor_solicitudes_compost';

        $stats = $wpdb->get_row($wpdb->prepare(
            "SELECT
                COALESCE(SUM(cantidad_kg), 0) as total_kg,
                COALESCE(SUM(puntos_obtenidos), 0) as total_puntos,
                COALESCE(SUM(co2_evitado_kg), 0) as co2_evitado,
                COUNT(*) as num_aportaciones
             FROM $tabla_aportaciones
             WHERE usuario_id = %d AND validado = 1",
            $usuario_id
        ));

        $turnos = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM $tabla_inscripciones WHERE usuario_id = %d AND estado = 'asistio'",
            $usuario_id
        ));

        $kg_recogidos = 0;
        if ($wpdb->get_var($wpdb->prepare("SHOW TABLES LIKE %s", $tabla_solicitudes)) === $tabla_solicitudes) {
            $kg_recogidos = $wpdb->get_var($wpdb->prepare(
                "SELECT COALESCE(SUM(cantidad_kg), 0)
                 FROM $tabla_solicitudes
                 WHERE usuario_id = %d
                   AND estado IN ('pendiente', 'aprobada', 'entregada')",
                $usuario_id
            ));
        }

        return [
            'total_kg' => floatval($stats->total_kg ?? 0),
            'kg_recogidos' => floatval($kg_recogidos ?? 0),
            'total_puntos' => intval($stats->total_puntos ?? 0),
            'co2_evitado' => floatval($stats->co2_evitado ?? 0),
            'num_aportaciones' => intval($stats->num_aportaciones ?? 0),
            'turnos_completados' => intval($turnos ?? 0),
        ];
    }

    /**
     * Obtiene aportaciones recientes de un usuario
     */
    private function obtener_aportaciones_recientes($usuario_id, $limite = 10) {
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
     * Obtiene próximos turnos del usuario
     */
    private function obtener_proximos_turnos($usuario_id) {
        global $wpdb;

        $tabla_turnos = $wpdb->prefix . 'flavor_turnos_compostaje';
        $tabla_inscripciones = $wpdb->prefix . 'flavor_inscripciones_turno';
        $tabla_puntos = $wpdb->prefix . 'flavor_puntos_compostaje';

        return $wpdb->get_results($wpdb->prepare(
            "SELECT t.*, p.nombre as nombre_punto, i.estado as estado_inscripcion
             FROM $tabla_turnos t
             INNER JOIN $tabla_inscripciones i ON t.id = i.turno_id
             LEFT JOIN $tabla_puntos p ON t.punto_id = p.id
             WHERE i.usuario_id = %d
               AND i.estado IN ('inscrito', 'confirmado')
               AND t.fecha_turno >= CURDATE()
             ORDER BY t.fecha_turno ASC
             LIMIT 5",
            $usuario_id
        ));
    }

    /**
     * Obtiene puntos de compostaje
     */
    private function obtener_puntos_compostaje($filtros = []) {
        global $wpdb;
        $tabla = $wpdb->prefix . 'flavor_puntos_compostaje';

        $where = ['1=1'];
        $params = [];

        if (!empty($filtros['estado'])) {
            $where[] = 'estado = %s';
            $params[] = $filtros['estado'];
        }

        if (!empty($filtros['tipo'])) {
            $where[] = 'tipo = %s';
            $params[] = $filtros['tipo'];
        }

        if (!empty($filtros['fase'])) {
            $where[] = 'fase_actual = %s';
            $params[] = $filtros['fase'];
        }

        $sql = "SELECT * FROM $tabla WHERE " . implode(' AND ', $where) . " ORDER BY nombre ASC";

        if (!empty($params)) {
            $sql = $wpdb->prepare($sql, $params);
        }

        return $wpdb->get_results($sql);
    }

    /**
     * Obtiene turnos disponibles
     */
    private function obtener_turnos_disponibles($filtros = []) {
        global $wpdb;

        $tabla_turnos = $wpdb->prefix . 'flavor_turnos_compostaje';
        $tabla_puntos = $wpdb->prefix . 'flavor_puntos_compostaje';

        $where = ["t.estado IN ('abierto', 'completo')"];
        $params = [];

        if (empty($filtros['mostrar_pasados'])) {
            $where[] = 't.fecha_turno >= CURDATE()';
        }

        if (!empty($filtros['punto_id'])) {
            $where[] = 't.punto_id = %d';
            $params[] = $filtros['punto_id'];
        }

        if (!empty($filtros['dias'])) {
            $where[] = 't.fecha_turno <= DATE_ADD(CURDATE(), INTERVAL %d DAY)';
            $params[] = $filtros['dias'];
        }

        $sql = "SELECT t.*, p.nombre as nombre_punto, p.direccion
                FROM $tabla_turnos t
                LEFT JOIN $tabla_puntos p ON t.punto_id = p.id
                WHERE " . implode(' AND ', $where) . "
                ORDER BY t.fecha_turno ASC, t.hora_inicio ASC";

        if (!empty($params)) {
            $sql = $wpdb->prepare($sql, $params);
        }

        return $wpdb->get_results($sql);
    }

    /**
     * Obtiene ranking de compostadores
     */
    private function obtener_ranking($filtros = []) {
        global $wpdb;

        $tabla_aportaciones = $wpdb->prefix . 'flavor_aportaciones_compost';

        $where = ['validado = 1'];

        if ($filtros['periodo'] === 'mes') {
            $where[] = 'MONTH(fecha_aportacion) = MONTH(CURDATE()) AND YEAR(fecha_aportacion) = YEAR(CURDATE())';
        } elseif ($filtros['periodo'] === 'semana') {
            $where[] = 'YEARWEEK(fecha_aportacion) = YEARWEEK(CURDATE())';
        }

        $limite = intval($filtros['limite'] ?? 10);

        return $wpdb->get_results($wpdb->prepare(
            "SELECT
                a.usuario_id,
                u.display_name,
                SUM(a.cantidad_kg) as total_kg,
                SUM(a.puntos_obtenidos) as total_puntos,
                COUNT(*) as num_aportaciones
             FROM $tabla_aportaciones a
             INNER JOIN {$wpdb->users} u ON a.usuario_id = u.ID
             WHERE " . implode(' AND ', $where) . "
             GROUP BY a.usuario_id
             ORDER BY total_kg DESC
             LIMIT %d",
            $limite
        ));
    }

    /**
     * Calcula el nivel de un usuario basado en kg aportados
     */
    private function calcular_nivel($total_kg) {
        $nivel_actual = 1;

        foreach ($this->niveles_gamificacion as $nivel => $data) {
            if ($total_kg >= $data['kg_minimo']) {
                $nivel_actual = $nivel;
            }
        }

        return array_merge(
            $this->niveles_gamificacion[$nivel_actual],
            ['nivel' => $nivel_actual]
        );
    }

    /**
     * Calcula progreso hacia siguiente nivel
     */
    private function calcular_progreso_nivel($total_kg, $nivel_actual) {
        $siguiente = isset($this->niveles_gamificacion[$nivel_actual + 1]) ?
                     $this->niveles_gamificacion[$nivel_actual + 1]['kg_minimo'] :
                     $this->niveles_gamificacion[$nivel_actual]['kg_minimo'] * 2;

        $actual_min = $this->niveles_gamificacion[$nivel_actual]['kg_minimo'];
        $rango = $siguiente - $actual_min;
        $progreso = $total_kg - $actual_min;
        $porcentaje = $rango > 0 ? min(100, ($progreso / $rango) * 100) : 100;

        return [
            'porcentaje' => round($porcentaje),
            'siguiente_kg' => $siguiente,
        ];
    }

    /**
     * Verifica si usuario está inscrito en un turno
     */
    private function usuario_inscrito_turno($usuario_id, $turno_id) {
        global $wpdb;

        return (bool) $wpdb->get_var($wpdb->prepare(
            "SELECT id FROM {$wpdb->prefix}flavor_inscripciones_turno
             WHERE turno_id = %d AND usuario_id = %d AND estado NOT IN ('cancelado', 'no_asistio')",
            $turno_id, $usuario_id
        ));
    }

    /**
     * Obtiene nombre legible de un material
     */
    private function obtener_nombre_material($codigo) {
        foreach ($this->materiales as $categoria => $materiales) {
            if (isset($materiales[$codigo])) {
                return $materiales[$codigo]['nombre'];
            }
        }
        return ucfirst(str_replace('_', ' ', $codigo));
    }

    /**
     * Obtiene puntos por material
     */
    private function obtener_puntos_material($codigo, $categoria) {
        if (isset($this->materiales[$categoria][$codigo])) {
            return $this->materiales[$categoria][$codigo]['puntos'];
        }
        return 5; // Default
    }

    /**
     * Actualiza nivel de llenado de un punto
     */
    private function actualizar_nivel_punto($punto_id, $kg_anadidos) {
        global $wpdb;

        // Estimar: 1 kg ≈ 2 litros de material
        $litros = $kg_anadidos * 2;

        $wpdb->query($wpdb->prepare(
            "UPDATE {$wpdb->prefix}flavor_puntos_compostaje
             SET nivel_llenado_pct = LEAST(100, nivel_llenado_pct + ROUND(%f / capacidad_litros * 100))
             WHERE id = %d",
            $litros, $punto_id
        ));
    }
}

// Inicializar
Flavor_Compostaje_Frontend_Controller::get_instance();

<?php
/**
 * Frontend Controller para Avisos Municipales
 *
 * Maneja todas las vistas frontend, shortcodes, AJAX y dashboard tabs
 * para el sistema de avisos y comunicados municipales.
 *
 * @package FlavorPlatform
 * @since 3.1.1
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Clase controladora del frontend de Avisos Municipales
 */
class Flavor_Avisos_Municipales_Frontend_Controller {

    /**
     * Instancia singleton
     * @var Flavor_Avisos_Municipales_Frontend_Controller|null
     */
    private static $instancia = null;

    /**
     * ID del módulo
     */
    const MODULO_ID = 'avisos_municipales';

    /**
     * Versión del controlador
     */
    const VERSION = '1.0.0';

    /**
     * Nombres de tablas
     */
    private $tablas = [];

    /**
     * Prioridades con colores
     */
    private $prioridades = [
        'urgente' => ['label' => 'Urgente', 'color' => '#dc2626', 'icon' => 'warning'],
        'alta' => ['label' => 'Alta', 'color' => '#f97316', 'icon' => 'flag'],
        'media' => ['label' => 'Media', 'color' => '#eab308', 'icon' => 'info'],
        'baja' => ['label' => 'Baja', 'color' => '#22c55e', 'icon' => 'check'],
    ];

    /**
     * Constructor privado (singleton)
     */
    private function __construct() {
        global $wpdb;
        $this->tablas = [
            'avisos' => $wpdb->prefix . 'flavor_avisos_municipales',
            'categorias' => $wpdb->prefix . 'flavor_avisos_categorias',
            'zonas' => $wpdb->prefix . 'flavor_avisos_zonas',
            'suscripciones' => $wpdb->prefix . 'flavor_avisos_suscripciones',
            'lecturas' => $wpdb->prefix . 'flavor_avisos_lecturas',
            'confirmaciones' => $wpdb->prefix . 'flavor_avisos_confirmaciones',
        ];

        $this->init_hooks();
    }

    /**
     * Obtiene la instancia singleton
     *
     * @return Flavor_Avisos_Municipales_Frontend_Controller
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
        // Shortcodes
        add_action('init', [$this, 'registrar_shortcodes']);

        // Assets
        add_action('wp_enqueue_scripts', [$this, 'registrar_assets']);

        // AJAX handlers
        add_action('wp_ajax_flavor_avisos_marcar_leido', [$this, 'ajax_marcar_leido']);
        add_action('wp_ajax_flavor_avisos_confirmar', [$this, 'ajax_confirmar_lectura']);
        add_action('wp_ajax_flavor_avisos_suscribir', [$this, 'ajax_suscribir']);
        add_action('wp_ajax_flavor_avisos_desuscribir', [$this, 'ajax_desuscribir']);
        add_action('wp_ajax_flavor_avisos_obtener', [$this, 'ajax_obtener_avisos']);
        add_action('wp_ajax_nopriv_flavor_avisos_obtener', [$this, 'ajax_obtener_avisos']);

        // Dashboard tab
        add_filter('flavor_user_dashboard_tabs', [$this, 'registrar_dashboard_tab'], 10, 1);
    }

    /**
     * Registra shortcodes del frontend
     */
    public function registrar_shortcodes() {
        $shortcodes = [
            'flavor_avisos_listado' => 'shortcode_listado',
            'flavor_avisos_urgentes' => 'shortcode_urgentes',
            'flavor_avisos_detalle' => 'shortcode_detalle',
            'flavor_avisos_suscripciones' => 'shortcode_suscripciones',
            'flavor_avisos_buscador' => 'shortcode_buscador',
            'flavor_avisos_categorias' => 'shortcode_categorias',
            'flavor_avisos_banner' => 'shortcode_banner',
            'flavor_avisos_dashboard' => 'shortcode_dashboard',
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
            'flavor-avisos-frontend',
            $modulo_url . 'assets/css/avisos-frontend.css',
            [],
            self::VERSION
        );

        wp_register_script(
            'flavor-avisos-frontend',
            $modulo_url . 'assets/js/avisos-frontend.js',
            ['jquery'],
            self::VERSION,
            true
        );
    }

    /**
     * Encola assets cuando se necesitan
     */
    private function enqueue_assets() {
        wp_enqueue_style('flavor-avisos-frontend');
        wp_enqueue_script('flavor-avisos-frontend');

        wp_localize_script('flavor-avisos-frontend', 'flavorAvisosConfig', [
            'ajaxUrl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('flavor_avisos_nonce'),
            'usuarioId' => get_current_user_id(),
            'prioridades' => $this->prioridades,
            'strings' => [
                'cargando' => __('Cargando...', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'error' => __('Ha ocurrido un error', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'confirmado' => __('Lectura confirmada', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'suscrito' => __('Suscripción activada', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'desuscrito' => __('Suscripción cancelada', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'sin_avisos' => __('No hay avisos para mostrar', FLAVOR_PLATFORM_TEXT_DOMAIN),
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

        // Contar avisos no leídos
        $no_leidos = $this->contar_avisos_no_leidos(get_current_user_id());

        $tabs['avisos'] = [
            'titulo' => __('Avisos', FLAVOR_PLATFORM_TEXT_DOMAIN),
            'icono' => 'dashicons-megaphone',
            'callback' => [$this, 'render_dashboard_tab'],
            'orden' => 10,
            'badge' => $no_leidos > 0 ? $no_leidos : null,
        ];

        return $tabs;
    }

    /**
     * Renderiza el contenido del dashboard tab
     */
    public function render_dashboard_tab() {
        $this->enqueue_assets();
        $usuario_id = get_current_user_id();

        $avisos_recientes = $this->obtener_avisos(['limite' => 10, 'estado' => 'publicado']);
        $avisos_urgentes = $this->obtener_avisos(['limite' => 5, 'prioridad' => 'urgente', 'estado' => 'publicado']);
        $estadisticas = $this->obtener_estadisticas_usuario($usuario_id);
        $suscripciones = $this->obtener_suscripciones($usuario_id);
        ?>
        <div class="flavor-avisos-dashboard">
            <!-- KPIs -->
            <div class="flavor-kpi-grid flavor-grid-4">
                <div class="flavor-kpi-card">
                    <div class="flavor-kpi-icon rojo">
                        <span class="dashicons dashicons-warning"></span>
                    </div>
                    <div class="flavor-kpi-content">
                        <span class="flavor-kpi-valor"><?php echo intval($estadisticas['urgentes']); ?></span>
                        <span class="flavor-kpi-label"><?php _e('Avisos Urgentes', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></span>
                    </div>
                </div>

                <div class="flavor-kpi-card">
                    <div class="flavor-kpi-icon azul">
                        <span class="dashicons dashicons-email-alt"></span>
                    </div>
                    <div class="flavor-kpi-content">
                        <span class="flavor-kpi-valor"><?php echo intval($estadisticas['no_leidos']); ?></span>
                        <span class="flavor-kpi-label"><?php _e('Sin Leer', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></span>
                    </div>
                </div>

                <div class="flavor-kpi-card">
                    <div class="flavor-kpi-icon verde">
                        <span class="dashicons dashicons-yes-alt"></span>
                    </div>
                    <div class="flavor-kpi-content">
                        <span class="flavor-kpi-valor"><?php echo intval($estadisticas['confirmados']); ?></span>
                        <span class="flavor-kpi-label"><?php _e('Confirmados', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></span>
                    </div>
                </div>

                <div class="flavor-kpi-card">
                    <div class="flavor-kpi-icon morado">
                        <span class="dashicons dashicons-bell"></span>
                    </div>
                    <div class="flavor-kpi-content">
                        <span class="flavor-kpi-valor"><?php echo count($suscripciones); ?></span>
                        <span class="flavor-kpi-label"><?php _e('Suscripciones', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></span>
                    </div>
                </div>
            </div>

            <!-- Avisos Urgentes -->
            <?php if (!empty($avisos_urgentes)): ?>
                <div class="flavor-panel flavor-panel-urgente">
                    <div class="flavor-panel-header">
                        <h3>
                            <span class="dashicons dashicons-warning"></span>
                            <?php _e('Avisos Urgentes', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                        </h3>
                    </div>
                    <div class="flavor-panel-body">
                        <div class="flavor-avisos-urgentes-list">
                            <?php foreach ($avisos_urgentes as $aviso): ?>
                                <?php $this->render_aviso_card($aviso, 'urgente'); ?>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>
            <?php endif; ?>

            <!-- Avisos Recientes -->
            <div class="flavor-panel">
                <div class="flavor-panel-header">
                    <h3><?php _e('Avisos Recientes', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></h3>
                    <a href="<?php echo esc_url(add_query_arg('seccion', 'todos')); ?>" class="flavor-link">
                        <?php _e('Ver todos', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                    </a>
                </div>
                <div class="flavor-panel-body">
                    <?php if (!empty($avisos_recientes)): ?>
                        <div class="flavor-avisos-list">
                            <?php foreach ($avisos_recientes as $aviso): ?>
                                <?php $this->render_aviso_row($aviso, $usuario_id); ?>
                            <?php endforeach; ?>
                        </div>
                    <?php else: ?>
                        <div class="flavor-empty-state">
                            <span class="dashicons dashicons-megaphone"></span>
                            <p><?php _e('No hay avisos recientes.', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Mis Suscripciones -->
            <div class="flavor-panel">
                <div class="flavor-panel-header">
                    <h3><?php _e('Mis Suscripciones', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></h3>
                    <a href="<?php echo esc_url(add_query_arg('seccion', 'suscripciones')); ?>" class="flavor-link">
                        <?php _e('Gestionar', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                    </a>
                </div>
                <div class="flavor-panel-body">
                    <?php if (!empty($suscripciones)): ?>
                        <div class="flavor-suscripciones-tags">
                            <?php foreach ($suscripciones as $suscripcion): ?>
                                <span class="flavor-tag">
                                    <span class="dashicons dashicons-<?php echo $suscripcion->canal === 'email' ? 'email' : ($suscripcion->canal === 'push' ? 'bell' : 'smartphone'); ?>"></span>
                                    <?php echo esc_html($suscripcion->nombre_categoria ?: $suscripcion->nombre_zona ?: __('General', FLAVOR_PLATFORM_TEXT_DOMAIN)); ?>
                                </span>
                            <?php endforeach; ?>
                        </div>
                    <?php else: ?>
                        <p class="flavor-text-muted">
                            <?php _e('No tienes suscripciones activas.', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                            <a href="<?php echo esc_url(add_query_arg('seccion', 'suscripciones')); ?>">
                                <?php _e('Suscríbete ahora', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                            </a>
                        </p>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        <?php
    }

    /**
     * Renderiza una tarjeta de aviso (estilo urgente)
     */
    private function render_aviso_card($aviso, $tipo = '') {
        $prioridad = $this->prioridades[$aviso->prioridad] ?? $this->prioridades['media'];
        ?>
        <div class="flavor-aviso-card <?php echo esc_attr($tipo); ?>" data-aviso-id="<?php echo esc_attr($aviso->id); ?>">
            <div class="flavor-aviso-prioridad" style="background: <?php echo esc_attr($prioridad['color']); ?>">
                <span class="dashicons dashicons-<?php echo esc_attr($prioridad['icon']); ?>"></span>
            </div>
            <div class="flavor-aviso-content">
                <h4><?php echo esc_html($aviso->titulo); ?></h4>
                <p><?php echo wp_trim_words(strip_tags($aviso->contenido), 20); ?></p>
                <div class="flavor-aviso-meta">
                    <span class="flavor-aviso-fecha">
                        <span class="dashicons dashicons-calendar-alt"></span>
                        <?php echo date_i18n('d M Y, H:i', strtotime($aviso->fecha_publicacion)); ?>
                    </span>
                    <?php if ($aviso->categoria): ?>
                        <span class="flavor-aviso-categoria">
                            <?php echo esc_html($aviso->categoria); ?>
                        </span>
                    <?php endif; ?>
                </div>
            </div>
            <div class="flavor-aviso-actions">
                <a href="<?php echo esc_url(add_query_arg(['seccion' => 'detalle', 'aviso_id' => $aviso->id])); ?>"
                   class="flavor-btn flavor-btn-sm flavor-btn-primary">
                    <?php _e('Ver', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                </a>
            </div>
        </div>
        <?php
    }

    /**
     * Renderiza una fila de aviso
     */
    private function render_aviso_row($aviso, $usuario_id) {
        $leido = $this->aviso_leido($aviso->id, $usuario_id);
        $prioridad = $this->prioridades[$aviso->prioridad] ?? $this->prioridades['media'];
        ?>
        <div class="flavor-aviso-row <?php echo $leido ? 'leido' : 'no-leido'; ?>"
             data-aviso-id="<?php echo esc_attr($aviso->id); ?>">
            <div class="flavor-aviso-indicator" style="background: <?php echo esc_attr($prioridad['color']); ?>"></div>
            <div class="flavor-aviso-info">
                <span class="flavor-aviso-titulo">
                    <?php if (!$leido): ?>
                        <span class="flavor-badge-nuevo"><?php _e('Nuevo', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></span>
                    <?php endif; ?>
                    <?php echo esc_html($aviso->titulo); ?>
                </span>
                <span class="flavor-aviso-resumen">
                    <?php echo wp_trim_words(strip_tags($aviso->contenido), 15); ?>
                </span>
            </div>
            <div class="flavor-aviso-fecha-col">
                <?php echo human_time_diff(strtotime($aviso->fecha_publicacion)); ?>
            </div>
            <div class="flavor-aviso-actions">
                <a href="<?php echo esc_url(add_query_arg(['seccion' => 'detalle', 'aviso_id' => $aviso->id])); ?>"
                   class="flavor-btn flavor-btn-sm flavor-btn-outline">
                    <span class="dashicons dashicons-visibility"></span>
                </a>
            </div>
        </div>
        <?php
    }

    /**
     * Shortcode: Listado de avisos
     */
    public function shortcode_listado($atts) {
        $atts = shortcode_atts([
            'limite' => 10,
            'categoria' => '',
            'prioridad' => '',
            'paginacion' => true,
        ], $atts);

        $this->enqueue_assets();

        $pagina = isset($_GET['pag']) ? max(1, intval($_GET['pag'])) : 1;
        $avisos = $this->obtener_avisos([
            'limite' => $atts['limite'],
            'offset' => ($pagina - 1) * $atts['limite'],
            'categoria' => $atts['categoria'],
            'prioridad' => $atts['prioridad'],
            'estado' => 'publicado',
        ]);
        $total = $this->contar_avisos([
            'categoria' => $atts['categoria'],
            'prioridad' => $atts['prioridad'],
            'estado' => 'publicado',
        ]);
        $usuario_id = get_current_user_id();

        ob_start();
        ?>
        <div class="flavor-avisos-listado">
            <!-- Filtros -->
            <div class="flavor-filtros-bar">
                <select id="filtro-categoria" class="flavor-select">
                    <option value=""><?php _e('Todas las categorías', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></option>
                    <?php foreach ($this->obtener_categorias() as $cat): ?>
                        <option value="<?php echo esc_attr($cat->slug); ?>"
                                <?php selected($atts['categoria'], $cat->slug); ?>>
                            <?php echo esc_html($cat->nombre); ?>
                        </option>
                    <?php endforeach; ?>
                </select>

                <select id="filtro-prioridad" class="flavor-select">
                    <option value=""><?php _e('Todas las prioridades', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></option>
                    <?php foreach ($this->prioridades as $key => $prio): ?>
                        <option value="<?php echo esc_attr($key); ?>"
                                <?php selected($atts['prioridad'], $key); ?>>
                            <?php echo esc_html($prio['label']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <!-- Lista de avisos -->
            <?php if (!empty($avisos)): ?>
                <div class="flavor-avisos-grid">
                    <?php foreach ($avisos as $aviso): ?>
                        <?php $this->render_aviso_card_full($aviso, $usuario_id); ?>
                    <?php endforeach; ?>
                </div>

                <!-- Paginación -->
                <?php if ($atts['paginacion'] && $total > $atts['limite']): ?>
                    <div class="flavor-paginacion">
                        <?php
                        $total_paginas = ceil($total / $atts['limite']);
                        for ($i = 1; $i <= $total_paginas; $i++):
                            ?>
                            <a href="<?php echo esc_url(add_query_arg('pag', $i)); ?>"
                               class="flavor-btn flavor-btn-sm <?php echo $i === $pagina ? 'active' : ''; ?>">
                                <?php echo $i; ?>
                            </a>
                        <?php endfor; ?>
                    </div>
                <?php endif; ?>
            <?php else: ?>
                <div class="flavor-empty-state">
                    <span class="dashicons dashicons-megaphone"></span>
                    <p><?php _e('No hay avisos para mostrar.', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></p>
                </div>
            <?php endif; ?>
        </div>
        <?php
        return ob_get_clean();
    }

    /**
     * Renderiza tarjeta de aviso completa
     */
    private function render_aviso_card_full($aviso, $usuario_id) {
        $leido = $usuario_id ? $this->aviso_leido($aviso->id, $usuario_id) : true;
        $prioridad = $this->prioridades[$aviso->prioridad] ?? $this->prioridades['media'];
        ?>
        <div class="flavor-aviso-card-full <?php echo $leido ? '' : 'no-leido'; ?>"
             data-aviso-id="<?php echo esc_attr($aviso->id); ?>">

            <div class="flavor-aviso-header" style="border-left-color: <?php echo esc_attr($prioridad['color']); ?>">
                <div class="flavor-aviso-badges">
                    <span class="flavor-badge" style="background: <?php echo esc_attr($prioridad['color']); ?>">
                        <?php echo esc_html($prioridad['label']); ?>
                    </span>
                    <?php if ($aviso->categoria): ?>
                        <span class="flavor-badge flavor-badge-outline">
                            <?php echo esc_html($aviso->categoria); ?>
                        </span>
                    <?php endif; ?>
                    <?php if (!$leido): ?>
                        <span class="flavor-badge flavor-badge-nuevo">
                            <?php _e('Nuevo', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                        </span>
                    <?php endif; ?>
                </div>
                <span class="flavor-aviso-fecha">
                    <?php echo date_i18n('d M Y, H:i', strtotime($aviso->fecha_publicacion)); ?>
                </span>
            </div>

            <h3 class="flavor-aviso-titulo"><?php echo esc_html($aviso->titulo); ?></h3>

            <div class="flavor-aviso-extracto">
                <?php echo wp_trim_words(strip_tags($aviso->contenido), 30); ?>
            </div>

            <div class="flavor-aviso-footer">
                <div class="flavor-aviso-stats">
                    <span>
                        <span class="dashicons dashicons-visibility"></span>
                        <?php echo intval($aviso->total_visualizaciones); ?>
                    </span>
                    <?php if ($aviso->requiere_confirmacion): ?>
                        <span>
                            <span class="dashicons dashicons-yes-alt"></span>
                            <?php echo intval($aviso->total_confirmaciones); ?>
                        </span>
                    <?php endif; ?>
                </div>

                <a href="<?php echo esc_url(add_query_arg(['seccion' => 'detalle', 'aviso_id' => $aviso->id])); ?>"
                   class="flavor-btn flavor-btn-primary">
                    <?php _e('Leer más', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                </a>
            </div>
        </div>
        <?php
    }

    /**
     * Shortcode: Avisos urgentes
     */
    public function shortcode_urgentes($atts) {
        $atts = shortcode_atts([
            'limite' => 5,
        ], $atts);

        $this->enqueue_assets();
        $avisos = $this->obtener_avisos([
            'limite' => $atts['limite'],
            'prioridad' => 'urgente',
            'estado' => 'publicado',
        ]);

        if (empty($avisos)) {
            return '';
        }

        ob_start();
        ?>
        <div class="flavor-avisos-urgentes">
            <div class="flavor-urgentes-header">
                <span class="dashicons dashicons-warning"></span>
                <?php _e('Avisos Urgentes', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
            </div>
            <div class="flavor-urgentes-list">
                <?php foreach ($avisos as $aviso): ?>
                    <div class="flavor-urgente-item">
                        <a href="<?php echo esc_url(add_query_arg(['seccion' => 'detalle', 'aviso_id' => $aviso->id])); ?>">
                            <?php echo esc_html($aviso->titulo); ?>
                        </a>
                        <span class="flavor-urgente-fecha">
                            <?php echo human_time_diff(strtotime($aviso->fecha_publicacion)); ?>
                        </span>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
        <?php
        return ob_get_clean();
    }

    /**
     * Shortcode: Detalle de aviso
     */
    public function shortcode_detalle($atts) {
        $atts = shortcode_atts([
            'id' => 0,
        ], $atts);

        $aviso_id = $atts['id'] ?: (isset($_GET['aviso_id']) ? intval($_GET['aviso_id']) : 0);

        if (!$aviso_id) {
            return '<div class="flavor-notice flavor-notice-warning">' .
                   __('Aviso no encontrado.', FLAVOR_PLATFORM_TEXT_DOMAIN) .
                   '</div>';
        }

        $this->enqueue_assets();
        $aviso = $this->obtener_aviso($aviso_id);
        $usuario_id = get_current_user_id();

        if (!$aviso || $aviso->estado !== 'publicado') {
            return '<div class="flavor-notice flavor-notice-warning">' .
                   __('Este aviso no está disponible.', FLAVOR_PLATFORM_TEXT_DOMAIN) .
                   '</div>';
        }

        // Marcar como leído
        if ($usuario_id) {
            $this->marcar_como_leido($aviso_id, $usuario_id);
        }

        $prioridad = $this->prioridades[$aviso->prioridad] ?? $this->prioridades['media'];
        $confirmado = $usuario_id ? $this->aviso_confirmado($aviso_id, $usuario_id) : false;

        ob_start();
        ?>
        <div class="flavor-aviso-detalle">
            <div class="flavor-aviso-detalle-header" style="border-left-color: <?php echo esc_attr($prioridad['color']); ?>">
                <div class="flavor-aviso-badges">
                    <span class="flavor-badge" style="background: <?php echo esc_attr($prioridad['color']); ?>">
                        <?php echo esc_html($prioridad['label']); ?>
                    </span>
                    <?php if ($aviso->categoria): ?>
                        <span class="flavor-badge flavor-badge-outline">
                            <?php echo esc_html($aviso->categoria); ?>
                        </span>
                    <?php endif; ?>
                </div>

                <h1><?php echo esc_html($aviso->titulo); ?></h1>

                <div class="flavor-aviso-meta-row">
                    <span class="flavor-aviso-fecha">
                        <span class="dashicons dashicons-calendar-alt"></span>
                        <?php echo date_i18n('d M Y, H:i', strtotime($aviso->fecha_publicacion)); ?>
                    </span>
                    <span class="flavor-aviso-vistas">
                        <span class="dashicons dashicons-visibility"></span>
                        <?php printf(__('%d visualizaciones', FLAVOR_PLATFORM_TEXT_DOMAIN), $aviso->total_visualizaciones); ?>
                    </span>
                </div>
            </div>

            <div class="flavor-aviso-detalle-body">
                <?php echo wp_kses_post($aviso->contenido); ?>
            </div>

            <?php if ($aviso->requiere_confirmacion && $usuario_id): ?>
                <div class="flavor-aviso-confirmacion">
                    <?php if ($confirmado): ?>
                        <div class="flavor-confirmacion-estado confirmado">
                            <span class="dashicons dashicons-yes-alt"></span>
                            <?php _e('Has confirmado la lectura de este aviso', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                        </div>
                    <?php else: ?>
                        <div class="flavor-confirmacion-box">
                            <p><?php _e('Este aviso requiere confirmación de lectura.', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></p>
                            <button class="flavor-btn flavor-btn-primary flavor-btn-confirmar"
                                    data-aviso-id="<?php echo esc_attr($aviso->id); ?>">
                                <span class="dashicons dashicons-yes"></span>
                                <?php _e('Confirmar Lectura', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                            </button>
                        </div>
                    <?php endif; ?>
                </div>
            <?php endif; ?>

            <div class="flavor-aviso-detalle-footer">
                <a href="javascript:history.back()" class="flavor-btn flavor-btn-outline">
                    <span class="dashicons dashicons-arrow-left-alt2"></span>
                    <?php _e('Volver', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                </a>

                <div class="flavor-share-buttons">
                    <button class="flavor-btn flavor-btn-sm flavor-btn-share"
                            data-share="twitter"
                            title="<?php _e('Compartir en Twitter', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>">
                        <span class="dashicons dashicons-twitter"></span>
                    </button>
                    <button class="flavor-btn flavor-btn-sm flavor-btn-share"
                            data-share="facebook"
                            title="<?php _e('Compartir en Facebook', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>">
                        <span class="dashicons dashicons-facebook"></span>
                    </button>
                    <button class="flavor-btn flavor-btn-sm flavor-btn-share"
                            data-share="whatsapp"
                            title="<?php _e('Compartir en WhatsApp', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>">
                        <span class="dashicons dashicons-whatsapp"></span>
                    </button>
                </div>
            </div>
        </div>
        <?php
        return ob_get_clean();
    }

    /**
     * Shortcode: Gestión de suscripciones
     */
    public function shortcode_suscripciones($atts) {
        if (!is_user_logged_in()) {
            return '<div class="flavor-notice flavor-notice-warning">' .
                   __('Debes iniciar sesión para gestionar tus suscripciones.', FLAVOR_PLATFORM_TEXT_DOMAIN) .
                   '</div>';
        }

        $this->enqueue_assets();
        $usuario_id = get_current_user_id();
        $suscripciones = $this->obtener_suscripciones($usuario_id);
        $categorias = $this->obtener_categorias();
        $zonas = $this->obtener_zonas();

        ob_start();
        ?>
        <div class="flavor-avisos-suscripciones">
            <h3><?php _e('Gestiona tus Suscripciones', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></h3>
            <p class="flavor-text-muted">
                <?php _e('Recibe notificaciones de los avisos que te interesan.', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
            </p>

            <form id="form-suscripciones" class="flavor-form">
                <?php wp_nonce_field('flavor_avisos_nonce', 'avisos_nonce'); ?>

                <!-- Por categoría -->
                <div class="flavor-form-section">
                    <h4><?php _e('Por Categoría', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></h4>
                    <div class="flavor-checkbox-grid">
                        <?php foreach ($categorias as $cat): ?>
                            <?php
                            $suscrito = $this->tiene_suscripcion($suscripciones, 'categoria', $cat->id);
                            ?>
                            <label class="flavor-checkbox-card">
                                <input type="checkbox" name="categorias[]"
                                       value="<?php echo esc_attr($cat->id); ?>"
                                       <?php checked($suscrito); ?>>
                                <span class="flavor-checkbox-content">
                                    <span class="dashicons dashicons-<?php echo esc_attr($cat->icono ?: 'info'); ?>"
                                          style="color: <?php echo esc_attr($cat->color ?: '#6b7280'); ?>"></span>
                                    <span><?php echo esc_html($cat->nombre); ?></span>
                                </span>
                            </label>
                        <?php endforeach; ?>
                    </div>
                </div>

                <!-- Por zona -->
                <?php if (!empty($zonas)): ?>
                    <div class="flavor-form-section">
                        <h4><?php _e('Por Zona', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></h4>
                        <div class="flavor-checkbox-grid">
                            <?php foreach ($zonas as $zona): ?>
                                <?php $suscrito = $this->tiene_suscripcion($suscripciones, 'zona', $zona->id); ?>
                                <label class="flavor-checkbox-card">
                                    <input type="checkbox" name="zonas[]"
                                           value="<?php echo esc_attr($zona->id); ?>"
                                           <?php checked($suscrito); ?>>
                                    <span class="flavor-checkbox-content">
                                        <span class="dashicons dashicons-location"></span>
                                        <span><?php echo esc_html($zona->nombre); ?></span>
                                    </span>
                                </label>
                            <?php endforeach; ?>
                        </div>
                    </div>
                <?php endif; ?>

                <!-- Canal de notificación -->
                <div class="flavor-form-section">
                    <h4><?php _e('Canal de Notificación', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></h4>
                    <div class="flavor-radio-group">
                        <label class="flavor-radio-card">
                            <input type="radio" name="canal" value="email" checked>
                            <span class="flavor-radio-content">
                                <span class="dashicons dashicons-email"></span>
                                <span><?php _e('Email', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></span>
                            </span>
                        </label>
                        <label class="flavor-radio-card">
                            <input type="radio" name="canal" value="push">
                            <span class="flavor-radio-content">
                                <span class="dashicons dashicons-bell"></span>
                                <span><?php _e('Push', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></span>
                            </span>
                        </label>
                    </div>
                </div>

                <button type="submit" class="flavor-btn flavor-btn-primary flavor-btn-lg">
                    <span class="dashicons dashicons-saved"></span>
                    <?php _e('Guardar Preferencias', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                </button>
            </form>
        </div>
        <?php
        return ob_get_clean();
    }

    /**
     * Shortcode: Banner de avisos urgentes
     */
    public function shortcode_banner($atts) {
        $avisos = $this->obtener_avisos([
            'limite' => 3,
            'prioridad' => 'urgente',
            'estado' => 'publicado',
        ]);

        if (empty($avisos)) {
            return '';
        }

        $this->enqueue_assets();

        ob_start();
        ?>
        <div class="flavor-avisos-banner">
            <div class="flavor-banner-icon">
                <span class="dashicons dashicons-warning"></span>
            </div>
            <div class="flavor-banner-content">
                <div class="flavor-banner-marquee">
                    <?php foreach ($avisos as $aviso): ?>
                        <a href="<?php echo esc_url(add_query_arg(['seccion' => 'detalle', 'aviso_id' => $aviso->id])); ?>">
                            <?php echo esc_html($aviso->titulo); ?>
                        </a>
                        <span class="flavor-banner-separator">•</span>
                    <?php endforeach; ?>
                </div>
            </div>
            <button class="flavor-banner-close" aria-label="<?php _e('Cerrar', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>">
                <span class="dashicons dashicons-no-alt"></span>
            </button>
        </div>
        <?php
        return ob_get_clean();
    }

    /**
     * Shortcode: Categorías de avisos
     */
    public function shortcode_categorias($atts) {
        $this->enqueue_assets();
        $categorias = $this->obtener_categorias();

        ob_start();
        ?>
        <div class="flavor-avisos-categorias">
            <?php foreach ($categorias as $cat): ?>
                <a href="<?php echo esc_url(add_query_arg('categoria', $cat->slug)); ?>"
                   class="flavor-categoria-card"
                   style="--cat-color: <?php echo esc_attr($cat->color ?: '#6b7280'); ?>">
                    <span class="dashicons dashicons-<?php echo esc_attr($cat->icono ?: 'info'); ?>"></span>
                    <span><?php echo esc_html($cat->nombre); ?></span>
                </a>
            <?php endforeach; ?>
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
                   __('Debes iniciar sesión para acceder al dashboard.', FLAVOR_PLATFORM_TEXT_DOMAIN) .
                   '</div>';
        }

        ob_start();
        $this->render_dashboard_tab();
        return ob_get_clean();
    }

    /**
     * Shortcode: Buscador de avisos
     */
    public function shortcode_buscador($atts) {
        $this->enqueue_assets();

        ob_start();
        ?>
        <div class="flavor-avisos-buscador">
            <form class="flavor-search-form" method="get">
                <div class="flavor-search-input-group">
                    <input type="text" name="buscar" class="flavor-input"
                           placeholder="<?php _e('Buscar avisos...', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>"
                           value="<?php echo esc_attr($_GET['buscar'] ?? ''); ?>">
                    <button type="submit" class="flavor-btn flavor-btn-primary">
                        <span class="dashicons dashicons-search"></span>
                    </button>
                </div>
            </form>
        </div>
        <?php
        return ob_get_clean();
    }

    // ==========================================
    // AJAX HANDLERS
    // ==========================================

    /**
     * AJAX: Marcar aviso como leído
     */
    public function ajax_marcar_leido() {
        check_ajax_referer('flavor_avisos_nonce', 'nonce');

        $usuario_id = get_current_user_id();
        if (!$usuario_id) {
            wp_send_json_error(['message' => __('No autorizado', FLAVOR_PLATFORM_TEXT_DOMAIN)]);
        }

        $aviso_id = intval($_POST['aviso_id'] ?? 0);
        if (!$aviso_id) {
            wp_send_json_error(['message' => __('Aviso no válido', FLAVOR_PLATFORM_TEXT_DOMAIN)]);
        }

        $this->marcar_como_leido($aviso_id, $usuario_id);

        wp_send_json_success(['message' => __('Marcado como leído', FLAVOR_PLATFORM_TEXT_DOMAIN)]);
    }

    /**
     * AJAX: Confirmar lectura
     */
    public function ajax_confirmar_lectura() {
        check_ajax_referer('flavor_avisos_nonce', 'nonce');

        $usuario_id = get_current_user_id();
        if (!$usuario_id) {
            wp_send_json_error(['message' => __('No autorizado', FLAVOR_PLATFORM_TEXT_DOMAIN)]);
        }

        $aviso_id = intval($_POST['aviso_id'] ?? 0);
        if (!$aviso_id) {
            wp_send_json_error(['message' => __('Aviso no válido', FLAVOR_PLATFORM_TEXT_DOMAIN)]);
        }

        global $wpdb;

        // Verificar si ya confirmó
        $existe = $wpdb->get_var($wpdb->prepare(
            "SELECT id FROM {$this->tablas['confirmaciones']} WHERE aviso_id = %d AND usuario_id = %d",
            $aviso_id, $usuario_id
        ));

        if ($existe) {
            wp_send_json_error(['message' => __('Ya has confirmado este aviso', FLAVOR_PLATFORM_TEXT_DOMAIN)]);
        }

        // Insertar confirmación
        $wpdb->insert($this->tablas['confirmaciones'], [
            'aviso_id' => $aviso_id,
            'usuario_id' => $usuario_id,
            'fecha_confirmacion' => current_time('mysql'),
        ], ['%d', '%d', '%s']);

        // Incrementar contador
        $wpdb->query($wpdb->prepare(
            "UPDATE {$this->tablas['avisos']} SET total_confirmaciones = total_confirmaciones + 1 WHERE id = %d",
            $aviso_id
        ));

        wp_send_json_success(['message' => __('Lectura confirmada', FLAVOR_PLATFORM_TEXT_DOMAIN)]);
    }

    /**
     * AJAX: Suscribirse
     */
    public function ajax_suscribir() {
        check_ajax_referer('flavor_avisos_nonce', 'nonce');

        $usuario_id = get_current_user_id();
        if (!$usuario_id) {
            wp_send_json_error(['message' => __('No autorizado', FLAVOR_PLATFORM_TEXT_DOMAIN)]);
        }

        global $wpdb;

        $categorias = isset($_POST['categorias']) ? array_map('intval', $_POST['categorias']) : [];
        $zonas = isset($_POST['zonas']) ? array_map('intval', $_POST['zonas']) : [];
        $canal = sanitize_text_field($_POST['canal'] ?? 'email');

        // Eliminar suscripciones anteriores
        $wpdb->delete($this->tablas['suscripciones'], ['usuario_id' => $usuario_id], ['%d']);

        // Insertar nuevas
        foreach ($categorias as $cat_id) {
            $wpdb->insert($this->tablas['suscripciones'], [
                'usuario_id' => $usuario_id,
                'categoria_id' => $cat_id,
                'canal' => $canal,
                'activa' => 1,
                'created_at' => current_time('mysql'),
            ], ['%d', '%d', '%s', '%d', '%s']);
        }

        foreach ($zonas as $zona_id) {
            $wpdb->insert($this->tablas['suscripciones'], [
                'usuario_id' => $usuario_id,
                'zona_id' => $zona_id,
                'canal' => $canal,
                'activa' => 1,
                'created_at' => current_time('mysql'),
            ], ['%d', '%d', '%s', '%d', '%s']);
        }

        wp_send_json_success(['message' => __('Preferencias guardadas', FLAVOR_PLATFORM_TEXT_DOMAIN)]);
    }

    /**
     * AJAX: Obtener avisos
     */
    public function ajax_obtener_avisos() {
        $limite = intval($_POST['limite'] ?? 10);
        $offset = intval($_POST['offset'] ?? 0);
        $categoria = sanitize_text_field($_POST['categoria'] ?? '');
        $prioridad = sanitize_text_field($_POST['prioridad'] ?? '');

        $avisos = $this->obtener_avisos([
            'limite' => $limite,
            'offset' => $offset,
            'categoria' => $categoria,
            'prioridad' => $prioridad,
            'estado' => 'publicado',
        ]);

        wp_send_json_success([
            'avisos' => $avisos,
            'total' => $this->contar_avisos(['categoria' => $categoria, 'prioridad' => $prioridad, 'estado' => 'publicado']),
        ]);
    }

    // ==========================================
    // MÉTODOS AUXILIARES
    // ==========================================

    /**
     * Obtiene avisos
     */
    private function obtener_avisos($filtros = []) {
        global $wpdb;

        $where = ['1=1'];
        $params = [];

        if (!empty($filtros['estado'])) {
            $where[] = 'estado = %s';
            $params[] = $filtros['estado'];
        }

        if (!empty($filtros['prioridad'])) {
            $where[] = 'prioridad = %s';
            $params[] = $filtros['prioridad'];
        }

        if (!empty($filtros['categoria'])) {
            $where[] = 'categoria = %s';
            $params[] = $filtros['categoria'];
        }

        $limite = intval($filtros['limite'] ?? 10);
        $offset = intval($filtros['offset'] ?? 0);

        $sql = "SELECT * FROM {$this->tablas['avisos']}
                WHERE " . implode(' AND ', $where) . "
                  AND (fecha_expiracion IS NULL OR fecha_expiracion > NOW())
                ORDER BY
                    CASE prioridad
                        WHEN 'urgente' THEN 1
                        WHEN 'alta' THEN 2
                        WHEN 'media' THEN 3
                        WHEN 'baja' THEN 4
                    END,
                    fecha_publicacion DESC
                LIMIT %d OFFSET %d";

        $params[] = $limite;
        $params[] = $offset;

        return $wpdb->get_results($wpdb->prepare($sql, $params));
    }

    /**
     * Obtiene un aviso por ID
     */
    private function obtener_aviso($id) {
        global $wpdb;

        $aviso = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$this->tablas['avisos']} WHERE id = %d",
            $id
        ));

        if ($aviso) {
            // Incrementar visualizaciones
            $wpdb->query($wpdb->prepare(
                "UPDATE {$this->tablas['avisos']} SET total_visualizaciones = total_visualizaciones + 1 WHERE id = %d",
                $id
            ));
        }

        return $aviso;
    }

    /**
     * Cuenta avisos
     */
    private function contar_avisos($filtros = []) {
        global $wpdb;

        $where = ['1=1'];
        $params = [];

        if (!empty($filtros['estado'])) {
            $where[] = 'estado = %s';
            $params[] = $filtros['estado'];
        }

        if (!empty($filtros['prioridad'])) {
            $where[] = 'prioridad = %s';
            $params[] = $filtros['prioridad'];
        }

        if (!empty($filtros['categoria'])) {
            $where[] = 'categoria = %s';
            $params[] = $filtros['categoria'];
        }

        $sql = "SELECT COUNT(*) FROM {$this->tablas['avisos']}
                WHERE " . implode(' AND ', $where) . "
                  AND (fecha_expiracion IS NULL OR fecha_expiracion > NOW())";

        if (!empty($params)) {
            return $wpdb->get_var($wpdb->prepare($sql, $params));
        }

        return $wpdb->get_var($sql);
    }

    /**
     * Cuenta avisos no leídos de un usuario
     */
    private function contar_avisos_no_leidos($usuario_id) {
        global $wpdb;

        return $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM {$this->tablas['avisos']} a
             WHERE a.estado = 'publicado'
               AND (a.fecha_expiracion IS NULL OR a.fecha_expiracion > NOW())
               AND a.id NOT IN (
                   SELECT aviso_id FROM {$this->tablas['lecturas']} WHERE usuario_id = %d
               )",
            $usuario_id
        ));
    }

    /**
     * Obtiene estadísticas de un usuario
     */
    private function obtener_estadisticas_usuario($usuario_id) {
        global $wpdb;

        $urgentes = $wpdb->get_var(
            "SELECT COUNT(*) FROM {$this->tablas['avisos']}
             WHERE estado = 'publicado' AND prioridad = 'urgente'
               AND (fecha_expiracion IS NULL OR fecha_expiracion > NOW())"
        );

        $no_leidos = $this->contar_avisos_no_leidos($usuario_id);

        $confirmados = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM {$this->tablas['confirmaciones']} WHERE usuario_id = %d",
            $usuario_id
        ));

        return [
            'urgentes' => intval($urgentes),
            'no_leidos' => intval($no_leidos),
            'confirmados' => intval($confirmados),
        ];
    }

    /**
     * Obtiene categorías
     */
    private function obtener_categorias() {
        global $wpdb;

        if (!Flavor_Platform_Helpers::tabla_existe($this->tablas['categorias'])) {
            return [];
        }

        return $wpdb->get_results(
            "SELECT * FROM {$this->tablas['categorias']} WHERE activa = 1 ORDER BY orden, nombre"
        );
    }

    /**
     * Obtiene zonas
     */
    private function obtener_zonas() {
        global $wpdb;

        if (!Flavor_Platform_Helpers::tabla_existe($this->tablas['zonas'])) {
            return [];
        }

        return $wpdb->get_results(
            "SELECT * FROM {$this->tablas['zonas']} WHERE activa = 1 ORDER BY nombre"
        );
    }

    /**
     * Obtiene suscripciones de un usuario
     */
    private function obtener_suscripciones($usuario_id) {
        global $wpdb;

        if (!Flavor_Platform_Helpers::tabla_existe($this->tablas['suscripciones'])) {
            return [];
        }

        return $wpdb->get_results($wpdb->prepare(
            "SELECT s.*, c.nombre as nombre_categoria, z.nombre as nombre_zona
             FROM {$this->tablas['suscripciones']} s
             LEFT JOIN {$this->tablas['categorias']} c ON s.categoria_id = c.id
             LEFT JOIN {$this->tablas['zonas']} z ON s.zona_id = z.id
             WHERE s.usuario_id = %d AND s.activa = 1",
            $usuario_id
        ));
    }

    /**
     * Verifica si tiene suscripción
     */
    private function tiene_suscripcion($suscripciones, $tipo, $id) {
        foreach ($suscripciones as $s) {
            if ($tipo === 'categoria' && $s->categoria_id == $id) {
                return true;
            }
            if ($tipo === 'zona' && $s->zona_id == $id) {
                return true;
            }
        }
        return false;
    }

    /**
     * Verifica si un aviso fue leído
     */
    private function aviso_leido($aviso_id, $usuario_id) {
        global $wpdb;

        if (!Flavor_Platform_Helpers::tabla_existe($this->tablas['lecturas'])) {
            return false;
        }

        return (bool) $wpdb->get_var($wpdb->prepare(
            "SELECT id FROM {$this->tablas['lecturas']} WHERE aviso_id = %d AND usuario_id = %d",
            $aviso_id, $usuario_id
        ));
    }

    /**
     * Verifica si un aviso fue confirmado
     */
    private function aviso_confirmado($aviso_id, $usuario_id) {
        global $wpdb;

        if (!Flavor_Platform_Helpers::tabla_existe($this->tablas['confirmaciones'])) {
            return false;
        }

        return (bool) $wpdb->get_var($wpdb->prepare(
            "SELECT id FROM {$this->tablas['confirmaciones']} WHERE aviso_id = %d AND usuario_id = %d",
            $aviso_id, $usuario_id
        ));
    }

    /**
     * Marca un aviso como leído
     */
    private function marcar_como_leido($aviso_id, $usuario_id) {
        global $wpdb;

        if (!Flavor_Platform_Helpers::tabla_existe($this->tablas['lecturas'])) {
            return false;
        }

        // Verificar si ya existe
        $existe = $this->aviso_leido($aviso_id, $usuario_id);
        if ($existe) {
            return true;
        }

        return $wpdb->insert($this->tablas['lecturas'], [
            'aviso_id' => $aviso_id,
            'usuario_id' => $usuario_id,
            'fecha_lectura' => current_time('mysql'),
        ], ['%d', '%d', '%s']);
    }
}

// Inicializar
Flavor_Avisos_Municipales_Frontend_Controller::get_instance();

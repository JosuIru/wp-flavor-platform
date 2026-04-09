<?php
/**
 * Frontend Controller para Portal de Transparencia
 *
 * Gestiona el dashboard tab y funcionalidades frontend del portal de transparencia.
 *
 * @package FlavorChatIA
 * @since 3.1.1
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Clase controladora del frontend de Transparencia
 */
class Flavor_Transparencia_Frontend_Controller {

    /**
     * Instancia singleton
     * @var Flavor_Transparencia_Frontend_Controller|null
     */
    private static $instancia = null;

    /**
     * ID del módulo
     * @var string
     */
    private $modulo_id = 'transparencia';

    /**
     * Prefijo de tablas
     * @var string
     */
    private $prefijo_tabla;

    /**
     * Estados de solicitudes
     * @var array
     */
    private $estados_solicitud = [
        'recibida' => [
            'nombre' => 'Recibida',
            'icono' => 'email',
            'color' => '#6b7280'
        ],
        'en_tramite' => [
            'nombre' => 'En trámite',
            'icono' => 'update',
            'color' => '#3b82f6'
        ],
        'resuelta' => [
            'nombre' => 'Resuelta',
            'icono' => 'yes-alt',
            'color' => '#10b981'
        ],
        'denegada' => [
            'nombre' => 'Denegada',
            'icono' => 'no-alt',
            'color' => '#ef4444'
        ],
        'archivada' => [
            'nombre' => 'Archivada',
            'icono' => 'archive',
            'color' => '#9ca3af'
        ]
    ];

    /**
     * Categorías de documentos
     * @var array
     */
    private $categorias = [
        'presupuestos' => [
            'nombre' => 'Presupuestos',
            'icono' => 'chart-pie',
            'color' => '#3b82f6'
        ],
        'contratos' => [
            'nombre' => 'Contratos',
            'icono' => 'media-document',
            'color' => '#8b5cf6'
        ],
        'subvenciones' => [
            'nombre' => 'Subvenciones',
            'icono' => 'money-alt',
            'color' => '#10b981'
        ],
        'normativa' => [
            'nombre' => 'Normativa',
            'icono' => 'clipboard',
            'color' => '#f59e0b'
        ],
        'actas' => [
            'nombre' => 'Actas',
            'icono' => 'text-page',
            'color' => '#6366f1'
        ],
        'personal' => [
            'nombre' => 'Personal',
            'icono' => 'groups',
            'color' => '#ec4899'
        ],
        'indicadores' => [
            'nombre' => 'Indicadores',
            'icono' => 'chart-bar',
            'color' => '#14b8a6'
        ],
        'patrimonio' => [
            'nombre' => 'Patrimonio',
            'icono' => 'building',
            'color' => '#f97316'
        ]
    ];

    /**
     * Constructor privado
     */
    private function __construct() {
        global $wpdb;
        $this->prefijo_tabla = $wpdb->prefix . 'flavor_transparencia_';

        $this->init_hooks();
    }

    /**
     * Obtiene la instancia singleton
     *
     * @return Flavor_Transparencia_Frontend_Controller
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

        // Dashboard tabs
        add_filter('flavor_user_dashboard_tabs', [$this, 'registrar_dashboard_tab']);

        // Shortcodes adicionales
        if (!shortcode_exists('flavor_transparencia_dashboard')) {
            add_shortcode('flavor_transparencia_dashboard', [$this, 'shortcode_dashboard']);
        }
        if (!shortcode_exists('flavor_transparencia_mis_solicitudes')) {
            add_shortcode('flavor_transparencia_mis_solicitudes', [$this, 'shortcode_mis_solicitudes']);
        }

        // AJAX handlers
        add_action('wp_ajax_flavor_transparencia_dashboard_data', [$this, 'ajax_dashboard_data']);
        add_action('wp_ajax_flavor_transparencia_mis_solicitudes', [$this, 'ajax_mis_solicitudes']);
        add_action('wp_ajax_nopriv_flavor_transparencia_documentos_recientes', [$this, 'ajax_documentos_recientes']);
        add_action('wp_ajax_flavor_transparencia_documentos_recientes', [$this, 'ajax_documentos_recientes']);
    }

    /**
     * Registra assets del frontend
     */
    public function register_assets() {
        $modulo_url = plugins_url('', dirname(dirname(__FILE__)));
        $css_file = dirname(dirname(__FILE__)) . '/assets/css/transparencia.css';
        $css_version = file_exists($css_file) ? (string) filemtime($css_file) : FLAVOR_CHAT_IA_VERSION;

        wp_register_style(
            'flavor-transparencia-frontend',
            $modulo_url . '/assets/css/transparencia.css',
            [],
            $css_version
        );

        wp_register_script(
            'flavor-transparencia-frontend',
            $modulo_url . '/assets/js/transparencia.js',
            ['jquery'],
            FLAVOR_CHAT_IA_VERSION,
            true
        );

        wp_localize_script('flavor-transparencia-frontend', 'flavorTransparencia', [
            'ajaxUrl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('transparencia_nonce'),
            'estados' => $this->estados_solicitud,
            'categorias' => $this->categorias,
            'strings' => [
                'cargando' => __('Cargando...', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'error' => __('Error al procesar la solicitud', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'sin_resultados' => __('No se encontraron documentos', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'solicitud_enviada' => __('Solicitud enviada correctamente', FLAVOR_PLATFORM_TEXT_DOMAIN),
            ],
            'usuarioId' => get_current_user_id()
        ]);
    }

    /**
     * Encola assets
     */
    private function enqueue_assets() {
        wp_enqueue_style('dashicons');
        wp_enqueue_style('flavor-transparencia-frontend');
        wp_enqueue_script('flavor-transparencia-frontend');
    }

    /**
     * Registra el tab en el dashboard del usuario
     */
    public function registrar_dashboard_tab($tabs) {
        if (!is_user_logged_in()) {
            return $tabs;
        }

        $tabla_solicitudes = $this->prefijo_tabla . 'solicitudes_info';
        if (!Flavor_Chat_Helpers::tabla_existe($tabla_solicitudes)) {
            return $tabs;
        }

        $solicitudes_pendientes = $this->contar_solicitudes_en_tramite();

        $tabs['transparencia'] = [
            'titulo' => 'Transparencia',
            'icono' => 'visibility',
            'orden' => 78,
            'badge' => $solicitudes_pendientes > 0 ? $solicitudes_pendientes : 0,
            'content' => [$this, 'render_dashboard_tab'],
            'visible' => true,
        ];

        return $tabs;
    }

    /**
     * Renderiza el contenido del tab
     */
    public function render_dashboard_tab() {
        $this->enqueue_assets();

        $user_id = get_current_user_id();
        $estadisticas = $this->obtener_estadisticas();
        $mis_solicitudes = $this->obtener_solicitudes_usuario($user_id, 5);
        $documentos_recientes = $this->obtener_documentos_recientes(6);

        ob_start();
        ?>
        <div class="flavor-transparencia-panel">
            <div class="flavor-panel-header">
                <h2><span class="dashicons dashicons-visibility"></span> Portal de Transparencia</h2>
            </div>

            <!-- KPIs del portal -->
            <div class="flavor-kpi-grid">
                <div class="flavor-kpi-card flavor-kpi-accent">
                    <span class="flavor-kpi-icon dashicons dashicons-media-document"></span>
                    <div class="flavor-kpi-content">
                        <span class="flavor-kpi-value"><?php echo esc_html($estadisticas['documentos_publicos']); ?></span>
                        <span class="flavor-kpi-label">Documentos públicos</span>
                    </div>
                </div>
                <div class="flavor-kpi-card">
                    <span class="flavor-kpi-icon dashicons dashicons-chart-pie"></span>
                    <div class="flavor-kpi-content">
                        <span class="flavor-kpi-value"><?php echo esc_html($estadisticas['presupuestos']); ?></span>
                        <span class="flavor-kpi-label">Presupuestos</span>
                    </div>
                </div>
                <div class="flavor-kpi-card">
                    <span class="flavor-kpi-icon dashicons dashicons-text-page"></span>
                    <div class="flavor-kpi-content">
                        <span class="flavor-kpi-value"><?php echo esc_html($estadisticas['actas']); ?></span>
                        <span class="flavor-kpi-label">Actas publicadas</span>
                    </div>
                </div>
                <div class="flavor-kpi-card">
                    <span class="flavor-kpi-icon dashicons dashicons-email-alt"></span>
                    <div class="flavor-kpi-content">
                        <span class="flavor-kpi-value"><?php echo esc_html($estadisticas['solicitudes_atendidas']); ?>%</span>
                        <span class="flavor-kpi-label">Tasa respuesta</span>
                    </div>
                </div>
            </div>

            <?php if (!empty($mis_solicitudes)): ?>
            <!-- Mis solicitudes de información -->
            <div class="flavor-panel-section">
                <h3><span class="dashicons dashicons-email"></span> Mis solicitudes de información</h3>
                <div class="flavor-solicitudes-lista">
                    <?php foreach ($mis_solicitudes as $solicitud): ?>
                    <?php $estado_info = $this->estados_solicitud[$solicitud->estado] ?? $this->estados_solicitud['recibida']; ?>
                    <div class="flavor-solicitud-item" data-id="<?php echo esc_attr($solicitud->id); ?>">
                        <div class="flavor-solicitud-icono" style="background-color: <?php echo esc_attr($estado_info['color']); ?>">
                            <span class="dashicons dashicons-<?php echo esc_attr($estado_info['icono']); ?>"></span>
                        </div>
                        <div class="flavor-solicitud-info">
                            <h4><?php echo esc_html($solicitud->titulo ?? $solicitud->asunto ?? ''); ?></h4>
                            <div class="flavor-solicitud-meta">
                                <span><?php echo esc_html(date_i18n('d/m/Y', strtotime($solicitud->fecha_solicitud))); ?></span>
                                <?php if ($solicitud->fecha_limite): ?>
                                <span>Plazo: <?php echo esc_html(date_i18n('d/m/Y', strtotime($solicitud->fecha_limite))); ?></span>
                                <?php endif; ?>
                            </div>
                        </div>
                        <div class="flavor-solicitud-estado">
                            <span class="flavor-badge" style="background-color: <?php echo esc_attr($estado_info['color']); ?>">
                                <?php echo esc_html($estado_info['nombre']); ?>
                            </span>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
            <?php endif; ?>

            <?php if (!empty($documentos_recientes)): ?>
            <!-- Documentos recientes -->
            <div class="flavor-panel-section">
                <h3><span class="dashicons dashicons-media-document"></span> Documentos recientes</h3>
                <div class="flavor-documentos-grid">
                    <?php foreach ($documentos_recientes as $documento): ?>
                    <?php $cat_info = $this->categorias[$documento->categoria] ?? $this->categorias['normativa']; ?>
                    <div class="flavor-documento-card">
                        <div class="flavor-documento-icono" style="background-color: <?php echo esc_attr($cat_info['color']); ?>">
                            <span class="dashicons dashicons-<?php echo esc_attr($cat_info['icono']); ?>"></span>
                        </div>
                        <div class="flavor-documento-info">
                            <h5><?php echo esc_html($documento->titulo); ?></h5>
                            <span class="flavor-documento-fecha"><?php echo esc_html(date_i18n('d/m/Y', strtotime($documento->fecha_publicacion))); ?></span>
                        </div>
                        <?php if ($documento->archivo_url): ?>
                        <a href="<?php echo esc_url($documento->archivo_url); ?>" class="flavor-btn flavor-btn-sm flavor-btn-outline" target="_blank">
                            <span class="dashicons dashicons-download"></span>
                        </a>
                        <?php endif; ?>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
            <?php endif; ?>

            <!-- Acciones -->
            <div class="flavor-panel-actions">
                <a href="<?php echo esc_url(home_url('/mi-portal/transparencia/')); ?>" class="flavor-btn flavor-btn-primary">
                    <span class="dashicons dashicons-visibility"></span> Portal de Transparencia
                </a>
                <a href="<?php echo esc_url(home_url('/mi-portal/transparencia/solicitar/')); ?>" class="flavor-btn flavor-btn-secondary">
                    <span class="dashicons dashicons-email-alt"></span> Solicitar información
                </a>
                <a href="<?php echo esc_url(home_url('/mi-portal/transparencia/presupuestos/')); ?>" class="flavor-btn flavor-btn-outline">
                    <span class="dashicons dashicons-chart-pie"></span> Presupuestos
                </a>
            </div>
        </div>
        <?php
        return ob_get_clean();
    }

    /**
     * Shortcode para dashboard widget
     */
    public function shortcode_dashboard($atts) {
        $this->enqueue_assets();
        return $this->render_dashboard_tab();
    }

    /**
     * Shortcode para mis solicitudes
     */
    public function shortcode_mis_solicitudes($atts) {
        if (!is_user_logged_in()) {
            return '<div class="flavor-login-required">Inicia sesión para ver tus solicitudes.</div>';
        }

        $atts = shortcode_atts([
            'limite' => 10,
            'estado' => ''
        ], $atts);

        $this->enqueue_assets();

        $user_id = get_current_user_id();
        $solicitudes = $this->obtener_solicitudes_usuario($user_id, $atts['limite'], $atts['estado']);

        ob_start();
        ?>
        <div class="flavor-mis-solicitudes-panel">
            <div class="flavor-panel-header">
                <h2><span class="dashicons dashicons-email"></span> Mis solicitudes de información</h2>
                <a href="<?php echo esc_url(home_url('/mi-portal/transparencia/solicitar/')); ?>" class="flavor-btn flavor-btn-primary">
                    <span class="dashicons dashicons-plus-alt"></span> Nueva solicitud
                </a>
            </div>

            <?php if (!empty($solicitudes)): ?>
            <div class="flavor-solicitudes-lista flavor-solicitudes-completa">
                <?php foreach ($solicitudes as $solicitud): ?>
                <?php $estado_info = $this->estados_solicitud[$solicitud->estado] ?? $this->estados_solicitud['recibida']; ?>
                <?php
                    $solicitud_titulo = '';
                    if (isset($solicitud->titulo) && $solicitud->titulo !== '') {
                        $solicitud_titulo = (string) $solicitud->titulo;
                    } elseif (isset($solicitud->asunto) && $solicitud->asunto !== '') {
                        $solicitud_titulo = (string) $solicitud->asunto;
                    }
                ?>
                <div class="flavor-solicitud-item-completo" data-id="<?php echo esc_attr($solicitud->id); ?>">
                    <div class="flavor-solicitud-header">
                        <div class="flavor-solicitud-icono" style="background-color: <?php echo esc_attr($estado_info['color']); ?>">
                            <span class="dashicons dashicons-<?php echo esc_attr($estado_info['icono']); ?>"></span>
                        </div>
                        <div class="flavor-solicitud-titulo">
                            <h4><?php echo esc_html($solicitud_titulo); ?></h4>
                            <span class="flavor-badge" style="background-color: <?php echo esc_attr($estado_info['color']); ?>">
                                <?php echo esc_html($estado_info['nombre']); ?>
                            </span>
                        </div>
                    </div>
                    <div class="flavor-solicitud-contenido">
                        <p><?php echo esc_html(wp_trim_words($solicitud->descripcion, 30)); ?></p>
                    </div>
                    <div class="flavor-solicitud-footer">
                        <span class="flavor-fecha">
                            <span class="dashicons dashicons-calendar"></span>
                            <?php echo esc_html(date_i18n('d/m/Y', strtotime($solicitud->fecha_solicitud))); ?>
                        </span>
                        <?php if ($solicitud->fecha_limite): ?>
                        <span class="flavor-plazo <?php echo strtotime($solicitud->fecha_limite) < time() ? 'vencido' : ''; ?>">
                            <span class="dashicons dashicons-clock"></span>
                            Plazo: <?php echo esc_html(date_i18n('d/m/Y', strtotime($solicitud->fecha_limite))); ?>
                        </span>
                        <?php endif; ?>
                        <?php if ($solicitud->respuesta): ?>
                        <button class="flavor-btn flavor-btn-sm flavor-btn-ver-respuesta" data-id="<?php echo esc_attr($solicitud->id); ?>">
                            Ver respuesta
                        </button>
                        <?php endif; ?>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
            <?php else: ?>
            <div class="flavor-empty-state">
                <span class="dashicons dashicons-email"></span>
                <p>No tienes solicitudes de información</p>
                <a href="<?php echo esc_url(home_url('/mi-portal/transparencia/solicitar/')); ?>" class="flavor-btn flavor-btn-primary">
                    Realizar primera solicitud
                </a>
            </div>
            <?php endif; ?>
        </div>
        <?php
        return ob_get_clean();
    }

    /**
     * AJAX: Datos del dashboard
     */
    public function ajax_dashboard_data() {
        check_ajax_referer('flavor_transparencia_nonce', 'nonce');

        if (!is_user_logged_in()) {
            wp_send_json_error(['message' => 'Debes iniciar sesión']);
        }

        $user_id = get_current_user_id();

        wp_send_json_success([
            'estadisticas' => $this->obtener_estadisticas(),
            'mis_solicitudes' => $this->obtener_solicitudes_usuario($user_id, 5),
            'documentos_recientes' => $this->obtener_documentos_recientes(6)
        ]);
    }

    /**
     * AJAX: Mis solicitudes
     */
    public function ajax_mis_solicitudes() {
        check_ajax_referer('flavor_transparencia_nonce', 'nonce');

        if (!is_user_logged_in()) {
            wp_send_json_error(['message' => 'Debes iniciar sesión']);
        }

        $user_id = get_current_user_id();
        $pagina = max(1, intval($_POST['pagina'] ?? 1));
        $estado = sanitize_text_field($_POST['estado'] ?? '');

        wp_send_json_success([
            'solicitudes' => $this->obtener_solicitudes_usuario($user_id, 10, $estado, $pagina)
        ]);
    }

    /**
     * AJAX: Documentos recientes
     */
    public function ajax_documentos_recientes() {
        $limite = min(20, intval($_POST['limite'] ?? 6));
        $categoria = sanitize_text_field($_POST['categoria'] ?? '');

        $documentos = $this->obtener_documentos_recientes($limite, $categoria);

        // Enriquecer con info de categoría
        foreach ($documentos as &$doc) {
            $doc->categoria_info = $this->categorias[$doc->categoria] ?? $this->categorias['normativa'];
        }

        wp_send_json_success([
            'documentos' => $documentos
        ]);
    }

    /**
     * Obtiene estadísticas del portal
     */
    private function obtener_estadisticas() {
        global $wpdb;

        $tabla_documentos = $this->prefijo_tabla . 'documentos_publicos';
        $tabla_presupuestos = $this->prefijo_tabla . 'presupuestos';
        $tabla_actas = $this->prefijo_tabla . 'actas';
        $tabla_solicitudes = $this->prefijo_tabla . 'solicitudes_info';

        // Documentos públicos
        $documentos = 0;
        if (Flavor_Chat_Helpers::tabla_existe($tabla_documentos)) {
            $documentos = $wpdb->get_var("SELECT COUNT(*) FROM $tabla_documentos WHERE estado = 'publicado'");
        }

        // Presupuestos
        $presupuestos = 0;
        if (Flavor_Chat_Helpers::tabla_existe($tabla_presupuestos)) {
            $presupuestos = $wpdb->get_var("SELECT COUNT(*) FROM $tabla_presupuestos");
        }

        // Actas
        $actas = 0;
        if (Flavor_Chat_Helpers::tabla_existe($tabla_actas)) {
            $actas = $wpdb->get_var("SELECT COUNT(*) FROM $tabla_actas WHERE estado = 'aprobada'");
        }

        // Tasa de respuesta
        $tasa_respuesta = 0;
        if (Flavor_Chat_Helpers::tabla_existe($tabla_solicitudes)) {
            $total_solicitudes = $wpdb->get_var("SELECT COUNT(*) FROM $tabla_solicitudes");
            $resueltas = $wpdb->get_var("SELECT COUNT(*) FROM $tabla_solicitudes WHERE estado = 'resuelta'");
            if ($total_solicitudes > 0) {
                $tasa_respuesta = round(($resueltas / $total_solicitudes) * 100);
            }
        }

        return [
            'documentos_publicos' => intval($documentos),
            'presupuestos' => intval($presupuestos),
            'actas' => intval($actas),
            'solicitudes_atendidas' => $tasa_respuesta
        ];
    }

    /**
     * Cuenta solicitudes en trámite del usuario
     */
    private function contar_solicitudes_en_tramite() {
        global $wpdb;
        $tabla = $this->prefijo_tabla . 'solicitudes_info';
        $user_id = get_current_user_id();

        if (!$user_id || !Flavor_Chat_Helpers::tabla_existe($tabla)) {
            return 0;
        }

        return intval($wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM $tabla WHERE user_id = %d AND estado IN ('recibida', 'en_tramite')",
            $user_id
        )));
    }

    /**
     * Obtiene solicitudes del usuario
     */
    private function obtener_solicitudes_usuario($user_id, $limite = 10, $estado = '', $pagina = 1) {
        global $wpdb;
        $tabla = $this->prefijo_tabla . 'solicitudes_info';

        if (!Flavor_Chat_Helpers::tabla_existe($tabla)) {
            return [];
        }

        $offset = ($pagina - 1) * $limite;

        $where = "user_id = %d";
        $params = [$user_id];

        if ($estado && isset($this->estados_solicitud[$estado])) {
            $where .= " AND estado = %s";
            $params[] = $estado;
        }

        $params[] = $limite;
        $params[] = $offset;

        return $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM $tabla WHERE $where ORDER BY fecha_solicitud DESC LIMIT %d OFFSET %d",
            $params
        ));
    }

    /**
     * Obtiene documentos recientes
     */
    private function obtener_documentos_recientes($limite = 6, $categoria = '') {
        global $wpdb;
        $tabla = $this->prefijo_tabla . 'documentos_publicos';

        if (!Flavor_Chat_Helpers::tabla_existe($tabla)) {
            return [];
        }

        $where = "estado = 'publicado'";
        $params = [];

        if ($categoria && isset($this->categorias[$categoria])) {
            $where .= " AND categoria = %s";
            $params[] = $categoria;
        }

        $params[] = $limite;

        $sql = "SELECT * FROM $tabla WHERE $where ORDER BY fecha_publicacion DESC LIMIT %d";

        return $wpdb->get_results($wpdb->prepare($sql, $params));
    }
}

// Inicializar
Flavor_Transparencia_Frontend_Controller::get_instance();

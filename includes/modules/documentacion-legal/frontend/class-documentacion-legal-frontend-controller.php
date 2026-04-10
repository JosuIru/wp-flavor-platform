<?php
/**
 * Frontend Controller para Documentación Legal
 *
 * Gestiona el dashboard tab y funcionalidades frontend del repositorio legal.
 *
 * @package FlavorPlatform
 * @since 3.1.1
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Clase controladora del frontend de Documentación Legal
 */
class Flavor_Documentacion_Legal_Frontend_Controller {

    /**
     * Instancia singleton
     * @var Flavor_Documentacion_Legal_Frontend_Controller|null
     */
    private static $instancia = null;

    /**
     * ID del módulo
     * @var string
     */
    private $modulo_id = 'documentacion_legal';

    /**
     * Tipos de documento
     * @var array
     */
    private $tipos = [
        'ley' => [
            'nombre' => 'Ley',
            'icono' => 'clipboard',
            'color' => '#3b82f6'
        ],
        'decreto' => [
            'nombre' => 'Decreto',
            'icono' => 'media-document',
            'color' => '#6366f1'
        ],
        'ordenanza' => [
            'nombre' => 'Ordenanza',
            'icono' => 'building',
            'color' => '#8b5cf6'
        ],
        'sentencia' => [
            'nombre' => 'Sentencia',
            'icono' => 'businessman',
            'color' => '#10b981'
        ],
        'modelo_denuncia' => [
            'nombre' => 'Modelo de denuncia',
            'icono' => 'warning',
            'color' => '#ef4444'
        ],
        'modelo_recurso' => [
            'nombre' => 'Modelo de recurso',
            'icono' => 'redo',
            'color' => '#f97316'
        ],
        'guia' => [
            'nombre' => 'Guía',
            'icono' => 'book',
            'color' => '#eab308'
        ],
        'informe' => [
            'nombre' => 'Informe',
            'icono' => 'analytics',
            'color' => '#14b8a6'
        ],
        'otro' => [
            'nombre' => 'Otro',
            'icono' => 'media-default',
            'color' => '#6b7280'
        ]
    ];

    /**
     * Ámbitos
     * @var array
     */
    private $ambitos = [
        'estatal' => 'Estatal',
        'autonomico' => 'Autonómico',
        'provincial' => 'Provincial',
        'municipal' => 'Municipal',
        'europeo' => 'Europeo'
    ];

    /**
     * Constructor privado
     */
    private function __construct() {
        $this->init_hooks();
    }

    /**
     * Obtiene la instancia singleton
     *
     * @return Flavor_Documentacion_Legal_Frontend_Controller
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

        // Shortcode adicional sin sobrescribir uno ya registrado.
        if (!shortcode_exists('flavor_documentacion_legal_dashboard')) {
            add_shortcode('flavor_documentacion_legal_dashboard', [$this, 'shortcode_dashboard']);
        }

        // AJAX handlers
        add_action('wp_ajax_flavor_documentacion_legal_dashboard_data', [$this, 'ajax_dashboard_data']);
    }

    /**
     * Registra assets del frontend
     */
    public function register_assets() {
        $modulo_url = plugins_url('', dirname(dirname(__FILE__)));

        wp_register_style(
            'flavor-documentacion-legal',
            $modulo_url . '/assets/css/documentacion-legal.css',
            [],
            FLAVOR_PLATFORM_VERSION
        );

        wp_register_script(
            'flavor-documentacion-legal',
            $modulo_url . '/assets/js/documentacion-legal.js',
            ['jquery'],
            FLAVOR_PLATFORM_VERSION,
            true
        );

        wp_localize_script('flavor-documentacion-legal', 'flavorDocLegalConfig', [
            'ajaxUrl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('flavor_documentacion_legal_nonce'),
            'tipos' => $this->tipos,
            'ambitos' => $this->ambitos,
            'strings' => [
                'cargando' => __('Cargando...', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'error' => __('Error al procesar la solicitud', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'guardado' => __('Documento guardado', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'eliminado_guardado' => __('Documento eliminado de guardados', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'sin_resultados' => __('No se encontraron documentos', FLAVOR_PLATFORM_TEXT_DOMAIN),
            ],
            'usuarioId' => get_current_user_id()
        ]);
    }

    /**
     * Encola assets
     */
    private function enqueue_assets() {
        wp_enqueue_style('dashicons');
        wp_enqueue_style('flavor-documentacion-legal');
        wp_enqueue_script('flavor-documentacion-legal');
    }

    /**
     * Registra el tab en el dashboard del usuario
     */
    public function registrar_dashboard_tab($tabs) {
        if (!is_user_logged_in()) {
            return $tabs;
        }

        global $wpdb;
        if (!Flavor_Platform_Helpers::tabla_existe($wpdb->prefix . 'flavor_documentacion_legal')) {
            return $tabs;
        }

        $guardados = $this->contar_documentos_guardados();

        $tabs['documentacion-legal'] = [
            'titulo' => 'Doc. Legal',
            'icono' => 'clipboard',
            'orden' => 76,
            'badge' => $guardados > 0 ? $guardados : 0,
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
        $mis_guardados = $this->obtener_documentos_guardados($user_id, 5);
        $recientes = $this->obtener_documentos_recientes(6);

        ob_start();
        ?>
        <div class="flavor-doclegal-panel">
            <div class="flavor-panel-header">
                <h2><span class="dashicons dashicons-clipboard"></span> Documentación Legal</h2>
            </div>

            <!-- KPIs -->
            <div class="flavor-kpi-grid">
                <div class="flavor-kpi-card flavor-kpi-accent">
                    <span class="flavor-kpi-icon dashicons dashicons-media-document"></span>
                    <div class="flavor-kpi-content">
                        <span class="flavor-kpi-value"><?php echo esc_html($estadisticas['total']); ?></span>
                        <span class="flavor-kpi-label">Documentos</span>
                    </div>
                </div>
                <div class="flavor-kpi-card">
                    <span class="flavor-kpi-icon dashicons dashicons-clipboard"></span>
                    <div class="flavor-kpi-content">
                        <span class="flavor-kpi-value"><?php echo esc_html($estadisticas['leyes']); ?></span>
                        <span class="flavor-kpi-label">Leyes</span>
                    </div>
                </div>
                <div class="flavor-kpi-card">
                    <span class="flavor-kpi-icon dashicons dashicons-warning"></span>
                    <div class="flavor-kpi-content">
                        <span class="flavor-kpi-value"><?php echo esc_html($estadisticas['modelos']); ?></span>
                        <span class="flavor-kpi-label">Modelos</span>
                    </div>
                </div>
                <div class="flavor-kpi-card">
                    <span class="flavor-kpi-icon dashicons dashicons-heart"></span>
                    <div class="flavor-kpi-content">
                        <span class="flavor-kpi-value"><?php echo esc_html(count($mis_guardados)); ?></span>
                        <span class="flavor-kpi-label">Guardados</span>
                    </div>
                </div>
            </div>

            <?php if (!empty($mis_guardados)): ?>
            <!-- Mis documentos guardados -->
            <div class="flavor-panel-section">
                <h3><span class="dashicons dashicons-heart"></span> Mis documentos guardados</h3>
                <div class="flavor-docs-lista">
                    <?php foreach ($mis_guardados as $doc): ?>
                    <?php $tipo_info = $this->tipos[$doc->tipo] ?? $this->tipos['otro']; ?>
                    <div class="flavor-doc-item" data-id="<?php echo esc_attr($doc->id); ?>">
                        <div class="flavor-doc-icono" style="background-color: <?php echo esc_attr($tipo_info['color']); ?>">
                            <span class="dashicons dashicons-<?php echo esc_attr($tipo_info['icono']); ?>"></span>
                        </div>
                        <div class="flavor-doc-info">
                            <h4>
                                <a href="<?php echo esc_url(home_url('/documentacion-legal/?doc_id=' . $doc->id)); ?>">
                                    <?php echo esc_html($doc->titulo); ?>
                                </a>
                            </h4>
                            <span class="flavor-doc-tipo"><?php echo esc_html($tipo_info['nombre']); ?></span>
                        </div>
                        <?php if ($doc->archivo_adjunto): ?>
                        <a href="<?php echo esc_url($doc->archivo_adjunto); ?>" class="flavor-btn flavor-btn-sm flavor-btn-outline" target="_blank">
                            <span class="dashicons dashicons-download"></span>
                        </a>
                        <?php endif; ?>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
            <?php endif; ?>

            <?php if (!empty($recientes)): ?>
            <!-- Documentos recientes -->
            <div class="flavor-panel-section">
                <h3><span class="dashicons dashicons-clock"></span> Añadidos recientemente</h3>
                <div class="flavor-docs-grid">
                    <?php foreach ($recientes as $doc): ?>
                    <?php $tipo_info = $this->tipos[$doc->tipo] ?? $this->tipos['otro']; ?>
                    <div class="flavor-doc-card" data-id="<?php echo esc_attr($doc->id); ?>">
                        <div class="flavor-doc-card-header" style="border-left-color: <?php echo esc_attr($tipo_info['color']); ?>">
                            <span class="flavor-badge" style="background-color: <?php echo esc_attr($tipo_info['color']); ?>">
                                <?php echo esc_html($tipo_info['nombre']); ?>
                            </span>
                            <button class="flavor-btn-guardar <?php echo $this->esta_guardado($doc->id) ? 'guardado' : ''; ?>" data-id="<?php echo esc_attr($doc->id); ?>">
                                <span class="dashicons dashicons-<?php echo $this->esta_guardado($doc->id) ? 'heart' : 'heart'; ?>"></span>
                            </button>
                        </div>
                        <h5>
                            <a href="<?php echo esc_url(home_url('/documentacion-legal/?doc_id=' . $doc->id)); ?>">
                                <?php echo esc_html($doc->titulo); ?>
                            </a>
                        </h5>
                        <?php if ($doc->fecha_publicacion): ?>
                        <span class="flavor-doc-fecha"><?php echo esc_html(date_i18n('d/m/Y', strtotime($doc->fecha_publicacion))); ?></span>
                        <?php endif; ?>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
            <?php endif; ?>

            <!-- Acciones -->
            <div class="flavor-panel-actions">
                <a href="<?php echo esc_url(home_url('/documentacion-legal/')); ?>" class="flavor-btn flavor-btn-primary">
                    <span class="dashicons dashicons-search"></span> Explorar documentos
                </a>
                <a href="<?php echo esc_url(home_url('/documentacion-legal/categorias/')); ?>" class="flavor-btn flavor-btn-secondary">
                    <span class="dashicons dashicons-category"></span> Por categoría
                </a>
                <a href="<?php echo esc_url(home_url('/documentacion-legal/mis-guardados/')); ?>" class="flavor-btn flavor-btn-outline">
                    <span class="dashicons dashicons-heart"></span> Mis guardados
                </a>
            </div>
        </div>
        <?php
        return ob_get_clean();
    }

    /**
     * Shortcode para dashboard
     */
    public function shortcode_dashboard($atts) {
        $this->enqueue_assets();
        return $this->render_dashboard_tab();
    }

    /**
     * AJAX: Datos del dashboard
     */
    public function ajax_dashboard_data() {
        check_ajax_referer('flavor_documentacion_legal_nonce', 'nonce');

        if (!is_user_logged_in()) {
            wp_send_json_error(['message' => 'Debes iniciar sesión']);
        }

        $user_id = get_current_user_id();

        wp_send_json_success([
            'estadisticas' => $this->obtener_estadisticas(),
            'mis_guardados' => $this->obtener_documentos_guardados($user_id, 5),
            'recientes' => $this->obtener_documentos_recientes(6)
        ]);
    }

    /**
     * Obtiene estadísticas del repositorio
     */
    private function obtener_estadisticas() {
        global $wpdb;
        $tabla = $wpdb->prefix . 'flavor_documentacion_legal';

        if (!Flavor_Platform_Helpers::tabla_existe($tabla)) {
            return ['total' => 0, 'leyes' => 0, 'modelos' => 0];
        }

        // Consolidar 3 queries en 1 usando CASE WHEN
        $stats = $wpdb->get_row(
            "SELECT
                COUNT(*) AS total,
                SUM(CASE WHEN tipo IN ('ley', 'decreto', 'ordenanza') THEN 1 ELSE 0 END) AS leyes,
                SUM(CASE WHEN tipo IN ('modelo_denuncia', 'modelo_recurso') THEN 1 ELSE 0 END) AS modelos
            FROM $tabla
            WHERE estado = 'publicado'"
        );

        return [
            'total' => intval($stats->total ?? 0),
            'leyes' => intval($stats->leyes ?? 0),
            'modelos' => intval($stats->modelos ?? 0)
        ];
    }

    /**
     * Cuenta documentos guardados por el usuario
     */
    private function contar_documentos_guardados() {
        global $wpdb;
        $tabla = $wpdb->prefix . 'flavor_documentacion_legal_guardados';
        $user_id = get_current_user_id();

        if (!$user_id || !Flavor_Platform_Helpers::tabla_existe($tabla)) {
            return 0;
        }

        return intval($wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM $tabla WHERE user_id = %d",
            $user_id
        )));
    }

    /**
     * Verifica si un documento está guardado
     */
    private function esta_guardado($documento_id) {
        global $wpdb;
        $tabla = $wpdb->prefix . 'flavor_documentacion_legal_guardados';
        $user_id = get_current_user_id();

        if (!$user_id || !Flavor_Platform_Helpers::tabla_existe($tabla)) {
            return false;
        }

        return (bool) $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM $tabla WHERE user_id = %d AND documento_id = %d",
            $user_id,
            $documento_id
        ));
    }

    /**
     * Obtiene documentos guardados por el usuario
     */
    private function obtener_documentos_guardados($user_id, $limite = 5) {
        global $wpdb;
        $tabla = $wpdb->prefix . 'flavor_documentacion_legal';
        $tabla_guardados = $wpdb->prefix . 'flavor_documentacion_legal_guardados';

        if (!Flavor_Platform_Helpers::tabla_existe($tabla_guardados)) {
            return [];
        }

        return $wpdb->get_results($wpdb->prepare(
            "SELECT d.*
             FROM $tabla d
             JOIN $tabla_guardados g ON d.id = g.documento_id
             WHERE g.user_id = %d
             ORDER BY g.created_at DESC
             LIMIT %d",
            $user_id,
            $limite
        ));
    }

    /**
     * Obtiene documentos recientes
     */
    private function obtener_documentos_recientes($limite = 6) {
        global $wpdb;
        $tabla = $wpdb->prefix . 'flavor_documentacion_legal';

        if (!Flavor_Platform_Helpers::tabla_existe($tabla)) {
            return [];
        }

        return $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM $tabla WHERE estado = 'publicado' ORDER BY created_at DESC LIMIT %d",
            $limite
        ));
    }
}

// Inicializar
Flavor_Documentacion_Legal_Frontend_Controller::get_instance();

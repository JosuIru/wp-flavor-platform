<?php
/**
 * Frontend Controller para Campañas y Acciones Colectivas
 *
 * Gestiona el dashboard tab y funcionalidades frontend de campañas ciudadanas.
 *
 * @package FlavorPlatform
 * @since 3.1.1
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Clase controladora del frontend de Campañas
 */
class Flavor_Campanias_Frontend_Controller {

    /**
     * Instancia singleton
     * @var Flavor_Campanias_Frontend_Controller|null
     */
    private static $instancia = null;

    /**
     * ID del módulo
     * @var string
     */
    private $modulo_id = 'campanias';

    /**
     * Tipos de campaña
     * @var array
     */
    private $tipos = [
        'protesta' => [
            'nombre' => 'Protesta',
            'icono' => 'megaphone',
            'color' => '#ef4444'
        ],
        'recogida_firmas' => [
            'nombre' => 'Recogida de firmas',
            'icono' => 'edit',
            'color' => '#3b82f6'
        ],
        'concentracion' => [
            'nombre' => 'Concentración',
            'icono' => 'groups',
            'color' => '#8b5cf6'
        ],
        'boicot' => [
            'nombre' => 'Boicot',
            'icono' => 'no',
            'color' => '#f97316'
        ],
        'denuncia_publica' => [
            'nombre' => 'Denuncia pública',
            'icono' => 'warning',
            'color' => '#eab308'
        ],
        'sensibilizacion' => [
            'nombre' => 'Sensibilización',
            'icono' => 'lightbulb',
            'color' => '#10b981'
        ],
        'accion_legal' => [
            'nombre' => 'Acción legal',
            'icono' => 'clipboard',
            'color' => '#6366f1'
        ],
        'otra' => [
            'nombre' => 'Otra',
            'icono' => 'star-filled',
            'color' => '#6b7280'
        ]
    ];

    /**
     * Estados de campaña
     * @var array
     */
    private $estados = [
        'planificada' => [
            'nombre' => 'Planificada',
            'icono' => 'calendar',
            'color' => '#6b7280'
        ],
        'activa' => [
            'nombre' => 'Activa',
            'icono' => 'yes-alt',
            'color' => '#10b981'
        ],
        'pausada' => [
            'nombre' => 'Pausada',
            'icono' => 'controls-pause',
            'color' => '#f59e0b'
        ],
        'completada' => [
            'nombre' => 'Completada',
            'icono' => 'flag',
            'color' => '#3b82f6'
        ],
        'cancelada' => [
            'nombre' => 'Cancelada',
            'icono' => 'no-alt',
            'color' => '#ef4444'
        ]
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
     * @return Flavor_Campanias_Frontend_Controller
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

        // Shortcodes adicionales (sin sobrescribir uno ya registrado)
        if (!shortcode_exists('flavor_campanias_dashboard')) {
            add_shortcode('flavor_campanias_dashboard', [$this, 'shortcode_dashboard']);
        }
        if (!shortcode_exists('flavor_campanias_destacadas')) {
            add_shortcode('flavor_campanias_destacadas', [$this, 'shortcode_destacadas']);
        }

        // AJAX handlers adicionales
        add_action('wp_ajax_flavor_campanias_dashboard_data', [$this, 'ajax_dashboard_data']);
    }

    /**
     * Registra assets del frontend
     */
    public function register_assets() {
        $modulo_url = plugins_url('', dirname(dirname(__FILE__)));

        wp_register_style(
            'flavor-campanias-frontend',
            $modulo_url . '/assets/css/campanias.css',
            [],
            FLAVOR_PLATFORM_VERSION
        );

        wp_register_script(
            'flavor-campanias-frontend',
            $modulo_url . '/assets/js/campanias.js',
            ['jquery'],
            FLAVOR_PLATFORM_VERSION,
            true
        );

        wp_localize_script('flavor-campanias-frontend', 'flavorCampaniasConfig', [
            'ajaxUrl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('flavor_campanias_nonce'),
            'tipos' => $this->tipos,
            'estados' => $this->estados,
            'strings' => [
                'cargando' => __('Cargando...', 'flavor-platform'),
                'error' => __('Error al procesar la solicitud', 'flavor-platform'),
                'firma_registrada' => __('Tu firma ha sido registrada', 'flavor-platform'),
                'ya_firmado' => __('Ya has firmado esta campaña', 'flavor-platform'),
                'participando' => __('Ya estás participando', 'flavor-platform'),
                'sin_campanias' => __('No hay campañas activas', 'flavor-platform'),
            ],
            'usuarioId' => get_current_user_id()
        ]);
    }

    /**
     * Encola assets
     */
    private function enqueue_assets() {
        wp_enqueue_style('dashicons');
        wp_enqueue_style('flavor-campanias-frontend');
        wp_enqueue_script('flavor-campanias-frontend');
    }

    /**
     * Registra el tab en el dashboard del usuario
     */
    public function registrar_dashboard_tab($tabs) {
        if (!is_user_logged_in()) {
            return $tabs;
        }

        global $wpdb;
        if (!Flavor_Platform_Helpers::tabla_existe($wpdb->prefix . 'flavor_campanias')) {
            return $tabs;
        }

        $estadisticas = $this->obtener_estadisticas_usuario();
        $tiene_actividad = $estadisticas['participando'] > 0 || $estadisticas['creadas'] > 0;
        $campanias_activas = $this->contar_campanias_activas_usuario();

        $tabs['campanias'] = [
            'titulo' => 'Campañas',
            'icono' => 'megaphone',
            'orden' => 45,
            'badge' => $campanias_activas > 0 ? $campanias_activas : 0,
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
        $estadisticas = $this->obtener_estadisticas_usuario();
        $mis_campanias = $this->obtener_campanias_usuario($user_id, 4);
        $campanias_activas = $this->obtener_campanias_activas(4);
        $proximas_acciones = $this->obtener_proximas_acciones($user_id, 3);

        ob_start();
        ?>
        <div class="flavor-campanias-panel">
            <div class="flavor-panel-header">
                <h2><span class="dashicons dashicons-megaphone"></span> Campañas y Acciones</h2>
            </div>

            <!-- KPIs -->
            <div class="flavor-kpi-grid">
                <div class="flavor-kpi-card flavor-kpi-accent">
                    <span class="flavor-kpi-icon dashicons dashicons-groups"></span>
                    <div class="flavor-kpi-content">
                        <span class="flavor-kpi-value"><?php echo esc_html($estadisticas['participando']); ?></span>
                        <span class="flavor-kpi-label">Participando</span>
                    </div>
                </div>
                <div class="flavor-kpi-card">
                    <span class="flavor-kpi-icon dashicons dashicons-edit"></span>
                    <div class="flavor-kpi-content">
                        <span class="flavor-kpi-value"><?php echo esc_html($estadisticas['firmas']); ?></span>
                        <span class="flavor-kpi-label">Firmas</span>
                    </div>
                </div>
                <div class="flavor-kpi-card">
                    <span class="flavor-kpi-icon dashicons dashicons-flag"></span>
                    <div class="flavor-kpi-content">
                        <span class="flavor-kpi-value"><?php echo esc_html($estadisticas['creadas']); ?></span>
                        <span class="flavor-kpi-label">Creadas</span>
                    </div>
                </div>
                <div class="flavor-kpi-card">
                    <span class="flavor-kpi-icon dashicons dashicons-yes-alt"></span>
                    <div class="flavor-kpi-content">
                        <span class="flavor-kpi-value"><?php echo esc_html($estadisticas['completadas']); ?></span>
                        <span class="flavor-kpi-label">Completadas</span>
                    </div>
                </div>
            </div>

            <?php if (!empty($proximas_acciones)): ?>
            <!-- Próximas acciones -->
            <div class="flavor-panel-section">
                <h3><span class="dashicons dashicons-calendar-alt"></span> Próximas acciones</h3>
                <div class="flavor-acciones-lista">
                    <?php foreach ($proximas_acciones as $accion): ?>
                    <div class="flavor-accion-item">
                        <div class="flavor-accion-fecha">
                            <span class="flavor-dia"><?php echo esc_html(date_i18n('d', strtotime($accion->fecha))); ?></span>
                            <span class="flavor-mes"><?php echo esc_html(date_i18n('M', strtotime($accion->fecha))); ?></span>
                        </div>
                        <div class="flavor-accion-info">
                            <h4><?php echo esc_html($accion->titulo); ?></h4>
                            <div class="flavor-accion-meta">
                                <span><span class="dashicons dashicons-clock"></span> <?php echo esc_html($accion->hora ?: 'Por definir'); ?></span>
                                <?php if ($accion->ubicacion): ?>
                                <span><span class="dashicons dashicons-location"></span> <?php echo esc_html($accion->ubicacion); ?></span>
                                <?php endif; ?>
                            </div>
                        </div>
                        <a href="<?php echo esc_url(home_url('/campanias/?campania_id=' . $accion->campania_id)); ?>" class="flavor-btn flavor-btn-sm">Ver</a>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
            <?php endif; ?>

            <?php if (!empty($mis_campanias)): ?>
            <!-- Mis campañas -->
            <div class="flavor-panel-section">
                <h3><span class="dashicons dashicons-flag"></span> Mis campañas</h3>
                <div class="flavor-campanias-grid-mini">
                    <?php foreach ($mis_campanias as $campania): ?>
                    <?php
                        $tipo_info = $this->tipos[$campania->tipo] ?? $this->tipos['otra'];
                        $estado_info = $this->estados[$campania->estado] ?? $this->estados['planificada'];
                    ?>
                    <div class="flavor-campania-card-mini" data-id="<?php echo esc_attr($campania->id); ?>">
                        <div class="flavor-campania-tipo" style="background-color: <?php echo esc_attr($tipo_info['color']); ?>">
                            <span class="dashicons dashicons-<?php echo esc_attr($tipo_info['icono']); ?>"></span>
                        </div>
                        <div class="flavor-campania-info">
                            <h4>
                                <a href="<?php echo esc_url(home_url('/campanias/?campania_id=' . $campania->id)); ?>">
                                    <?php echo esc_html($campania->titulo); ?>
                                </a>
                            </h4>
                            <?php if ($campania->tipo === 'recogida_firmas' && $campania->objetivo_firmas > 0): ?>
                            <div class="flavor-progreso-firmas">
                                <div class="flavor-progreso-barra">
                                    <div class="flavor-progreso-valor" style="width: <?php echo min(100, ($campania->firmas_actuales / $campania->objetivo_firmas) * 100); ?>%"></div>
                                </div>
                                <span class="flavor-progreso-texto"><?php echo esc_html($campania->firmas_actuales); ?>/<?php echo esc_html($campania->objetivo_firmas); ?></span>
                            </div>
                            <?php endif; ?>
                        </div>
                        <span class="flavor-badge flavor-badge-sm" style="background-color: <?php echo esc_attr($estado_info['color']); ?>">
                            <?php echo esc_html($estado_info['nombre']); ?>
                        </span>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
            <?php endif; ?>

            <?php if (!empty($campanias_activas)): ?>
            <!-- Campañas activas destacadas -->
            <div class="flavor-panel-section">
                <h3><span class="dashicons dashicons-star-filled"></span> Campañas activas</h3>
                <div class="flavor-campanias-grid-mini">
                    <?php foreach ($campanias_activas as $campania): ?>
                    <?php $tipo_info = $this->tipos[$campania->tipo] ?? $this->tipos['otra']; ?>
                    <div class="flavor-campania-card-mini" data-id="<?php echo esc_attr($campania->id); ?>">
                        <div class="flavor-campania-tipo" style="background-color: <?php echo esc_attr($tipo_info['color']); ?>">
                            <span class="dashicons dashicons-<?php echo esc_attr($tipo_info['icono']); ?>"></span>
                        </div>
                        <div class="flavor-campania-info">
                            <h4>
                                <a href="<?php echo esc_url(home_url('/campanias/?campania_id=' . $campania->id)); ?>">
                                    <?php echo esc_html($campania->titulo); ?>
                                </a>
                            </h4>
                            <span class="flavor-campania-participantes">
                                <span class="dashicons dashicons-groups"></span> <?php echo esc_html($campania->participantes ?: 0); ?> participantes
                            </span>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
            <?php endif; ?>

            <!-- Acciones -->
            <div class="flavor-panel-actions">
                <a href="<?php echo esc_url(home_url('/campanias/crear/')); ?>" class="flavor-btn flavor-btn-primary">
                    <span class="dashicons dashicons-plus-alt"></span> Crear campaña
                </a>
                <a href="<?php echo esc_url(home_url('/campanias/')); ?>" class="flavor-btn flavor-btn-secondary">
                    <span class="dashicons dashicons-list-view"></span> Ver todas
                </a>
                <a href="<?php echo esc_url(home_url('/campanias/mapa/')); ?>" class="flavor-btn flavor-btn-outline">
                    <span class="dashicons dashicons-location-alt"></span> Mapa
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
        if (!is_user_logged_in()) {
            return '<div class="flavor-login-required">Inicia sesión para ver tus campañas.</div>';
        }

        $this->enqueue_assets();
        return $this->render_dashboard_tab();
    }

    /**
     * Shortcode para campañas destacadas
     */
    public function shortcode_destacadas($atts) {
        $atts = shortcode_atts([
            'limite' => 6,
            'tipo' => ''
        ], $atts);

        $this->enqueue_assets();

        $campanias = $this->obtener_campanias_destacadas($atts['limite'], $atts['tipo']);

        ob_start();
        ?>
        <div class="flavor-campanias-destacadas">
            <?php if (!empty($campanias)): ?>
            <div class="flavor-campanias-grid">
                <?php foreach ($campanias as $campania): ?>
                <?php
                    $tipo_info = $this->tipos[$campania->tipo] ?? $this->tipos['otra'];
                    $estado_info = $this->estados[$campania->estado] ?? $this->estados['activa'];
                ?>
                <article class="flavor-campania-card" data-id="<?php echo esc_attr($campania->id); ?>">
                    <div class="flavor-campania-header" style="background-color: <?php echo esc_attr($tipo_info['color']); ?>">
                        <?php if ($campania->imagen): ?>
                        <img src="<?php echo esc_url($campania->imagen); ?>" alt="<?php echo esc_attr($campania->titulo); ?>">
                        <?php else: ?>
                        <span class="dashicons dashicons-<?php echo esc_attr($tipo_info['icono']); ?>"></span>
                        <?php endif; ?>
                        <span class="flavor-campania-tipo-badge"><?php echo esc_html($tipo_info['nombre']); ?></span>
                    </div>
                    <div class="flavor-campania-body">
                        <h4><a href="<?php echo esc_url(home_url('/campanias/?campania_id=' . $campania->id)); ?>"><?php echo esc_html($campania->titulo); ?></a></h4>
                        <p><?php echo esc_html(wp_trim_words($campania->descripcion, 15)); ?></p>

                        <?php if ($campania->tipo === 'recogida_firmas' && $campania->objetivo_firmas > 0): ?>
                        <div class="flavor-progreso-firmas">
                            <div class="flavor-progreso-barra">
                                <div class="flavor-progreso-valor" style="width: <?php echo min(100, ($campania->firmas_actuales / $campania->objetivo_firmas) * 100); ?>%"></div>
                            </div>
                            <span class="flavor-progreso-texto"><?php echo esc_html($campania->firmas_actuales); ?> de <?php echo esc_html($campania->objetivo_firmas); ?> firmas</span>
                        </div>
                        <?php endif; ?>
                    </div>
                    <div class="flavor-campania-footer">
                        <span class="flavor-campania-participantes">
                            <span class="dashicons dashicons-groups"></span> <?php echo esc_html($campania->participantes ?: 0); ?>
                        </span>
                        <?php if ($campania->tipo === 'recogida_firmas'): ?>
                        <button class="flavor-btn flavor-btn-primary flavor-btn-firmar" data-id="<?php echo esc_attr($campania->id); ?>">
                            Firmar
                        </button>
                        <?php else: ?>
                        <a href="<?php echo esc_url(home_url('/campanias/?campania_id=' . $campania->id)); ?>" class="flavor-btn flavor-btn-primary">
                            Participar
                        </a>
                        <?php endif; ?>
                    </div>
                </article>
                <?php endforeach; ?>
            </div>
            <?php else: ?>
            <div class="flavor-empty-state">
                <span class="dashicons dashicons-megaphone"></span>
                <p>No hay campañas activas en este momento</p>
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
        check_ajax_referer('flavor_campanias_nonce', 'nonce');

        if (!is_user_logged_in()) {
            wp_send_json_error(['message' => 'Debes iniciar sesión']);
        }

        $user_id = get_current_user_id();

        wp_send_json_success([
            'estadisticas' => $this->obtener_estadisticas_usuario(),
            'mis_campanias' => $this->obtener_campanias_usuario($user_id, 4),
            'campanias_activas' => $this->obtener_campanias_activas(4),
            'proximas_acciones' => $this->obtener_proximas_acciones($user_id, 3)
        ]);
    }

    /**
     * Obtiene estadísticas del usuario
     */
    private function obtener_estadisticas_usuario() {
        global $wpdb;
        $user_id = get_current_user_id();

        if (!$user_id) {
            return ['participando' => 0, 'firmas' => 0, 'creadas' => 0, 'completadas' => 0];
        }

        $tabla_campanias = $wpdb->prefix . 'flavor_campanias';
        $tabla_participantes = $wpdb->prefix . 'flavor_campanias_participantes';
        $tabla_firmas = $wpdb->prefix . 'flavor_campanias_firmas';

        // Participando
        $participando = 0;
        if (Flavor_Platform_Helpers::tabla_existe($tabla_participantes)) {
            $participando = $wpdb->get_var($wpdb->prepare(
                "SELECT COUNT(DISTINCT campania_id) FROM $tabla_participantes WHERE user_id = %d",
                $user_id
            ));
        }

        // Firmas
        $firmas = 0;
        if (Flavor_Platform_Helpers::tabla_existe($tabla_firmas)) {
            $firmas = $wpdb->get_var($wpdb->prepare(
                "SELECT COUNT(*) FROM $tabla_firmas WHERE user_id = %d",
                $user_id
            ));
        }

        // Creadas
        $creadas = 0;
        if (Flavor_Platform_Helpers::tabla_existe($tabla_campanias)) {
            $creadas = $wpdb->get_var($wpdb->prepare(
                "SELECT COUNT(*) FROM $tabla_campanias WHERE creador_id = %d",
                $user_id
            ));
        }

        // Completadas
        $completadas = 0;
        if (Flavor_Platform_Helpers::tabla_existe($tabla_campanias)) {
            $completadas = $wpdb->get_var($wpdb->prepare(
                "SELECT COUNT(*) FROM $tabla_campanias WHERE creador_id = %d AND estado = 'completada'",
                $user_id
            ));
        }

        return [
            'participando' => intval($participando),
            'firmas' => intval($firmas),
            'creadas' => intval($creadas),
            'completadas' => intval($completadas)
        ];
    }

    /**
     * Cuenta campañas activas del usuario
     */
    private function contar_campanias_activas_usuario() {
        global $wpdb;
        $user_id = get_current_user_id();

        if (!$user_id) {
            return 0;
        }

        $tabla_participantes = $wpdb->prefix . 'flavor_campanias_participantes';
        $tabla_campanias = $wpdb->prefix . 'flavor_campanias';

        if (!Flavor_Platform_Helpers::tabla_existe($tabla_participantes)) {
            return 0;
        }

        return intval($wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(DISTINCT p.campania_id)
             FROM $tabla_participantes p
             JOIN $tabla_campanias c ON p.campania_id = c.id
             WHERE p.user_id = %d AND c.estado = 'activa'",
            $user_id
        )));
    }

    /**
     * Obtiene campañas del usuario
     */
    private function obtener_campanias_usuario($user_id, $limite = 4) {
        global $wpdb;
        $tabla_campanias = $wpdb->prefix . 'flavor_campanias';
        $tabla_participantes = $wpdb->prefix . 'flavor_campanias_participantes';

        if (!Flavor_Platform_Helpers::tabla_existe($tabla_campanias)) {
            return [];
        }

        return $wpdb->get_results($wpdb->prepare(
            "SELECT DISTINCT c.*
             FROM $tabla_campanias c
             LEFT JOIN $tabla_participantes p ON c.id = p.campania_id AND p.user_id = %d
             WHERE c.creador_id = %d OR p.user_id = %d
             ORDER BY c.updated_at DESC
             LIMIT %d",
            $user_id,
            $user_id,
            $user_id,
            $limite
        ));
    }

    /**
     * Obtiene campañas activas
     */
    private function obtener_campanias_activas($limite = 4) {
        global $wpdb;
        $tabla_campanias = $wpdb->prefix . 'flavor_campanias';
        $tabla_participantes = $wpdb->prefix . 'flavor_campanias_participantes';

        if (!Flavor_Platform_Helpers::tabla_existe($tabla_campanias)) {
            return [];
        }

        return $wpdb->get_results($wpdb->prepare(
            "SELECT c.*, COUNT(DISTINCT p.user_id) as participantes
             FROM $tabla_campanias c
             LEFT JOIN $tabla_participantes p ON c.id = p.campania_id
             WHERE c.estado = 'activa' AND c.visibilidad IN ('publica', 'miembros')
             GROUP BY c.id
             ORDER BY c.destacada DESC, participantes DESC
             LIMIT %d",
            $limite
        ));
    }

    /**
     * Obtiene próximas acciones del usuario
     */
    private function obtener_proximas_acciones($user_id, $limite = 3) {
        global $wpdb;
        $tabla_acciones = $wpdb->prefix . 'flavor_campanias_acciones';
        $tabla_participantes = $wpdb->prefix . 'flavor_campanias_participantes';

        if (!Flavor_Platform_Helpers::tabla_existe($tabla_acciones)) {
            return [];
        }

        return $wpdb->get_results($wpdb->prepare(
            "SELECT a.*
             FROM $tabla_acciones a
             JOIN $tabla_participantes p ON a.campania_id = p.campania_id
             WHERE p.user_id = %d AND a.fecha >= CURDATE()
             ORDER BY a.fecha ASC, a.hora ASC
             LIMIT %d",
            $user_id,
            $limite
        ));
    }

    /**
     * Obtiene campañas destacadas
     */
    private function obtener_campanias_destacadas($limite = 6, $tipo = '') {
        global $wpdb;
        $tabla_campanias = $wpdb->prefix . 'flavor_campanias';
        $tabla_participantes = $wpdb->prefix . 'flavor_campanias_participantes';

        if (!Flavor_Platform_Helpers::tabla_existe($tabla_campanias)) {
            return [];
        }

        $where = "c.estado = 'activa' AND c.visibilidad IN ('publica', 'miembros')";
        $params = [];

        if ($tipo && isset($this->tipos[$tipo])) {
            $where .= " AND c.tipo = %s";
            $params[] = $tipo;
        }

        $params[] = $limite;

        return $wpdb->get_results($wpdb->prepare(
            "SELECT c.*, COUNT(DISTINCT p.user_id) as participantes
             FROM $tabla_campanias c
             LEFT JOIN $tabla_participantes p ON c.id = p.campania_id
             WHERE $where
             GROUP BY c.id
             ORDER BY c.destacada DESC, participantes DESC
             LIMIT %d",
            $params
        ));
    }
}

// Inicializar
Flavor_Campanias_Frontend_Controller::get_instance();

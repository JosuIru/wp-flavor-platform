<?php
/**
 * Frontend Controller para Seguimiento de Denuncias
 *
 * Gestiona shortcodes, AJAX handlers y dashboard tabs del módulo.
 *
 * @package FlavorChatIA
 * @since 3.1.1
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Clase controladora del frontend de Seguimiento de Denuncias
 */
class Flavor_Seguimiento_Denuncias_Frontend_Controller {

    /**
     * Instancia singleton
     * @var Flavor_Seguimiento_Denuncias_Frontend_Controller|null
     */
    private static $instancia = null;

    /**
     * ID del módulo
     * @var string
     */
    private $modulo_id = 'seguimiento_denuncias';

    /**
     * Tipos de denuncia
     * @var array
     */
    private $tipos = [
        'denuncia' => [
            'nombre' => 'Denuncia',
            'icono' => 'warning',
            'color' => '#ef4444'
        ],
        'queja' => [
            'nombre' => 'Queja',
            'icono' => 'megaphone',
            'color' => '#f59e0b'
        ],
        'recurso' => [
            'nombre' => 'Recurso',
            'icono' => 'media-document',
            'color' => '#8b5cf6'
        ],
        'solicitud' => [
            'nombre' => 'Solicitud',
            'icono' => 'editor-help',
            'color' => '#3b82f6'
        ],
        'peticion' => [
            'nombre' => 'Petición',
            'icono' => 'groups',
            'color' => '#10b981'
        ]
    ];

    /**
     * Estados de denuncia
     * @var array
     */
    private $estados = [
        'presentada' => [
            'nombre' => 'Presentada',
            'icono' => 'clock',
            'color' => '#6b7280'
        ],
        'en_tramite' => [
            'nombre' => 'En trámite',
            'icono' => 'update',
            'color' => '#3b82f6'
        ],
        'requerimiento' => [
            'nombre' => 'Requerimiento',
            'icono' => 'info',
            'color' => '#f59e0b'
        ],
        'silencio' => [
            'nombre' => 'Silencio administrativo',
            'icono' => 'hidden',
            'color' => '#9ca3af'
        ],
        'resuelta_favorable' => [
            'nombre' => 'Resuelta favorable',
            'icono' => 'yes-alt',
            'color' => '#10b981'
        ],
        'resuelta_desfavorable' => [
            'nombre' => 'Resuelta desfavorable',
            'icono' => 'no-alt',
            'color' => '#ef4444'
        ],
        'archivada' => [
            'nombre' => 'Archivada',
            'icono' => 'archive',
            'color' => '#6b7280'
        ],
        'recurrida' => [
            'nombre' => 'Recurrida',
            'icono' => 'redo',
            'color' => '#8b5cf6'
        ]
    ];

    /**
     * Ámbitos
     * @var array
     */
    private $ambitos = [
        'municipal' => 'Municipal',
        'provincial' => 'Provincial',
        'autonomico' => 'Autonómico',
        'estatal' => 'Estatal',
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
     * @return Flavor_Seguimiento_Denuncias_Frontend_Controller
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
        if (!shortcode_exists('flavor_seguimiento_denuncias_dashboard')) {
            add_shortcode('flavor_seguimiento_denuncias_dashboard', [$this, 'shortcode_dashboard']);
        }
        if (!shortcode_exists('flavor_denuncias_buscador')) {
            add_shortcode('flavor_denuncias_buscador', [$this, 'shortcode_buscador']);
        }
        if (!shortcode_exists('flavor_denuncias_plantillas')) {
            add_shortcode('flavor_denuncias_plantillas', [$this, 'shortcode_plantillas']);
        }

        // AJAX handlers adicionales
        add_action('wp_ajax_flavor_seguimiento_denuncias_dashboard_data', [$this, 'ajax_dashboard_data']);
        add_action('wp_ajax_flavor_seguimiento_denuncias_buscar', [$this, 'ajax_buscar']);
        add_action('wp_ajax_nopriv_flavor_seguimiento_denuncias_buscar', [$this, 'ajax_buscar']);
        add_action('wp_ajax_flavor_seguimiento_denuncias_obtener_plantilla', [$this, 'ajax_obtener_plantilla']);
    }

    /**
     * Registra assets del frontend
     */
    public function register_assets() {
        $modulo_url = plugins_url('', dirname(dirname(__FILE__)));

        wp_register_style(
            'flavor-seguimiento-denuncias',
            $modulo_url . '/assets/css/seguimiento-denuncias.css',
            [],
            FLAVOR_CHAT_IA_VERSION
        );

        wp_register_script(
            'flavor-seguimiento-denuncias',
            $modulo_url . '/assets/js/seguimiento-denuncias.js',
            ['jquery'],
            FLAVOR_CHAT_IA_VERSION,
            true
        );

        wp_localize_script('flavor-seguimiento-denuncias', 'flavorSeguimientoDenunciasConfig', [
            'ajaxUrl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('flavor_denuncias_nonce'),
            'tipos' => $this->tipos,
            'estados' => $this->estados,
            'ambitos' => $this->ambitos,
            'strings' => [
                'cargando' => __('Cargando...', 'flavor-chat-ia'),
                'error' => __('Error al procesar la solicitud', 'flavor-chat-ia'),
                'exito' => __('Operación completada', 'flavor-chat-ia'),
                'confirmar_eliminar' => __('¿Estás seguro de que deseas eliminar esta denuncia?', 'flavor-chat-ia'),
                'sin_resultados' => __('No se encontraron denuncias', 'flavor-chat-ia'),
                'plazo_vencido' => __('Plazo vencido', 'flavor-chat-ia'),
                'dias_restantes' => __('días restantes', 'flavor-chat-ia'),
            ],
            'usuarioId' => get_current_user_id()
        ]);
    }

    /**
     * Encola assets
     */
    private function enqueue_assets() {
        wp_enqueue_style('dashicons');
        wp_enqueue_style('flavor-seguimiento-denuncias');
        wp_enqueue_script('flavor-seguimiento-denuncias');
    }

    /**
     * Registra el tab en el dashboard del usuario
     */
    public function registrar_dashboard_tab($tabs) {
        if (!is_user_logged_in()) {
            return $tabs;
        }

        global $wpdb;
        if (!Flavor_Chat_Helpers::tabla_existe($wpdb->prefix . 'flavor_seguimiento_denuncias')) {
            return $tabs;
        }

        $estadisticas = $this->obtener_estadisticas_usuario();
        $tiene_denuncias = ($estadisticas['activas'] + $estadisticas['resueltas'] + $estadisticas['silencio']) > 0;
        $tiene_alertas = $estadisticas['plazos_proximos'] > 0 || $estadisticas['silencio'] > 0;

        $tabs['seguimiento-denuncias'] = [
            'titulo' => 'Denuncias',
            'icono' => 'clipboard',
            'orden' => 72,
            'badge' => $tiene_alertas ? $estadisticas['plazos_proximos'] + $estadisticas['silencio'] : 0,
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
        $denuncias_recientes = $this->obtener_denuncias_usuario($user_id, 5);
        $plazos_proximos = $this->obtener_plazos_proximos($user_id, 5);

        ob_start();
        ?>
        <div class="flavor-denuncias-panel">
            <div class="flavor-panel-header">
                <h2><span class="dashicons dashicons-clipboard"></span> Seguimiento de Denuncias</h2>
            </div>

            <!-- KPIs -->
            <div class="flavor-kpi-grid">
                <div class="flavor-kpi-card flavor-kpi-accent">
                    <span class="flavor-kpi-icon dashicons dashicons-update"></span>
                    <div class="flavor-kpi-content">
                        <span class="flavor-kpi-value"><?php echo esc_html($estadisticas['activas']); ?></span>
                        <span class="flavor-kpi-label">En trámite</span>
                    </div>
                </div>
                <div class="flavor-kpi-card">
                    <span class="flavor-kpi-icon dashicons dashicons-clock"></span>
                    <div class="flavor-kpi-content">
                        <span class="flavor-kpi-value"><?php echo esc_html($estadisticas['plazos_proximos']); ?></span>
                        <span class="flavor-kpi-label">Plazos próximos</span>
                    </div>
                </div>
                <div class="flavor-kpi-card">
                    <span class="flavor-kpi-icon dashicons dashicons-hidden"></span>
                    <div class="flavor-kpi-content">
                        <span class="flavor-kpi-value"><?php echo esc_html($estadisticas['silencio']); ?></span>
                        <span class="flavor-kpi-label">Silencio adm.</span>
                    </div>
                </div>
                <div class="flavor-kpi-card">
                    <span class="flavor-kpi-icon dashicons dashicons-yes-alt"></span>
                    <div class="flavor-kpi-content">
                        <span class="flavor-kpi-value"><?php echo esc_html($estadisticas['resueltas']); ?></span>
                        <span class="flavor-kpi-label">Resueltas</span>
                    </div>
                </div>
            </div>

            <?php if (!empty($plazos_proximos)): ?>
            <!-- Alertas de plazos -->
            <div class="flavor-panel-section">
                <h3><span class="dashicons dashicons-warning"></span> Plazos próximos a vencer</h3>
                <div class="flavor-alertas-lista">
                    <?php foreach ($plazos_proximos as $denuncia): ?>
                    <div class="flavor-alerta-item <?php echo $denuncia->dias_restantes <= 0 ? 'vencido' : ($denuncia->dias_restantes <= 3 ? 'urgente' : ''); ?>">
                        <div class="flavor-alerta-info">
                            <strong><?php echo esc_html($denuncia->titulo); ?></strong>
                            <span class="flavor-alerta-organismo"><?php echo esc_html($denuncia->organismo_destino); ?></span>
                        </div>
                        <div class="flavor-alerta-plazo">
                            <?php if ($denuncia->dias_restantes <= 0): ?>
                                <span class="flavor-badge flavor-badge-danger">Vencido</span>
                            <?php else: ?>
                                <span class="flavor-badge flavor-badge-warning"><?php echo $denuncia->dias_restantes; ?> días</span>
                            <?php endif; ?>
                        </div>
                        <a href="<?php echo esc_url(home_url('/denuncias/?denuncia_id=' . $denuncia->id)); ?>" class="flavor-btn flavor-btn-sm">Ver</a>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
            <?php endif; ?>

            <?php if (!empty($denuncias_recientes)): ?>
            <!-- Denuncias recientes -->
            <div class="flavor-panel-section">
                <h3><span class="dashicons dashicons-list-view"></span> Mis denuncias recientes</h3>
                <div class="flavor-denuncias-lista">
                    <?php foreach ($denuncias_recientes as $denuncia): ?>
                    <?php
                        $tipo_info = $this->tipos[$denuncia->tipo] ?? $this->tipos['denuncia'];
                        $estado_info = $this->estados[$denuncia->estado] ?? $this->estados['presentada'];
                    ?>
                    <div class="flavor-denuncia-item" data-id="<?php echo esc_attr($denuncia->id); ?>">
                        <div class="flavor-denuncia-icono" style="background-color: <?php echo esc_attr($tipo_info['color']); ?>">
                            <span class="dashicons dashicons-<?php echo esc_attr($tipo_info['icono']); ?>"></span>
                        </div>
                        <div class="flavor-denuncia-info">
                            <h4>
                                <a href="<?php echo esc_url(home_url('/denuncias/?denuncia_id=' . $denuncia->id)); ?>">
                                    <?php echo esc_html($denuncia->titulo); ?>
                                </a>
                            </h4>
                            <div class="flavor-denuncia-meta">
                                <span><?php echo esc_html($denuncia->organismo_destino); ?></span>
                                <span><?php echo esc_html(date_i18n('d/m/Y', strtotime($denuncia->fecha_presentacion))); ?></span>
                            </div>
                        </div>
                        <div class="flavor-denuncia-estado">
                            <span class="flavor-badge" style="background-color: <?php echo esc_attr($estado_info['color']); ?>">
                                <?php echo esc_html($estado_info['nombre']); ?>
                            </span>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
            <?php else: ?>
            <div class="flavor-empty-state">
                <span class="dashicons dashicons-clipboard"></span>
                <p>No tienes denuncias registradas</p>
            </div>
            <?php endif; ?>

            <!-- Acciones -->
            <div class="flavor-panel-actions">
                <a href="<?php echo esc_url(home_url('/denuncias/crear/')); ?>" class="flavor-btn flavor-btn-primary">
                    <span class="dashicons dashicons-plus-alt"></span> Nueva denuncia
                </a>
                <a href="<?php echo esc_url(home_url('/denuncias/')); ?>" class="flavor-btn flavor-btn-secondary">
                    <span class="dashicons dashicons-list-view"></span> Ver todas
                </a>
                <a href="<?php echo esc_url(home_url('/denuncias/plantillas/')); ?>" class="flavor-btn flavor-btn-outline">
                    <span class="dashicons dashicons-media-document"></span> Plantillas
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
        if (!is_user_logged_in()) {
            return '<div class="flavor-login-required">Inicia sesión para ver tus denuncias.</div>';
        }

        $this->enqueue_assets();
        return $this->render_dashboard_tab();
    }

    /**
     * Shortcode para buscador de denuncias
     */
    public function shortcode_buscador($atts) {
        $atts = shortcode_atts([
            'mostrar_filtros' => true,
            'mostrar_mapa' => false
        ], $atts);

        $this->enqueue_assets();

        ob_start();
        ?>
        <div class="flavor-denuncias-buscador">
            <form id="form-buscar-denuncias" class="flavor-form-inline">
                <div class="flavor-search-box">
                    <span class="dashicons dashicons-search"></span>
                    <input type="text" name="busqueda" class="flavor-input" placeholder="Buscar denuncias...">
                </div>

                <?php if ($atts['mostrar_filtros']): ?>
                <select name="tipo" class="flavor-select">
                    <option value="">Todos los tipos</option>
                    <?php foreach ($this->tipos as $key => $tipo): ?>
                    <option value="<?php echo esc_attr($key); ?>"><?php echo esc_html($tipo['nombre']); ?></option>
                    <?php endforeach; ?>
                </select>

                <select name="estado" class="flavor-select">
                    <option value="">Todos los estados</option>
                    <?php foreach ($this->estados as $key => $estado): ?>
                    <option value="<?php echo esc_attr($key); ?>"><?php echo esc_html($estado['nombre']); ?></option>
                    <?php endforeach; ?>
                </select>

                <select name="ambito" class="flavor-select">
                    <option value="">Todos los ámbitos</option>
                    <?php foreach ($this->ambitos as $key => $nombre): ?>
                    <option value="<?php echo esc_attr($key); ?>"><?php echo esc_html($nombre); ?></option>
                    <?php endforeach; ?>
                </select>
                <?php endif; ?>

                <button type="submit" class="flavor-btn flavor-btn-primary">
                    <span class="dashicons dashicons-search"></span> Buscar
                </button>
            </form>

            <div id="resultados-denuncias" class="flavor-denuncias-resultados"></div>
        </div>
        <?php
        return ob_get_clean();
    }

    /**
     * Shortcode para plantillas de denuncia
     */
    public function shortcode_plantillas($atts) {
        $atts = shortcode_atts([
            'categoria' => '',
            'limite' => 12
        ], $atts);

        $this->enqueue_assets();

        $plantillas = $this->obtener_plantillas($atts['categoria'], $atts['limite']);

        ob_start();
        ?>
        <div class="flavor-plantillas-panel">
            <div class="flavor-panel-header">
                <h2><span class="dashicons dashicons-media-document"></span> Plantillas de Denuncia</h2>
                <p class="flavor-panel-descripcion">Usa nuestras plantillas para redactar tus denuncias de forma efectiva</p>
            </div>

            <?php if (!empty($plantillas)): ?>
            <div class="flavor-plantillas-grid">
                <?php foreach ($plantillas as $plantilla): ?>
                <div class="flavor-plantilla-card" data-id="<?php echo esc_attr($plantilla->id); ?>">
                    <div class="flavor-plantilla-header">
                        <h4><?php echo esc_html($plantilla->nombre); ?></h4>
                        <span class="flavor-badge"><?php echo esc_html(ucfirst($plantilla->tipo)); ?></span>
                    </div>
                    <p class="flavor-plantilla-descripcion"><?php echo esc_html($plantilla->descripcion); ?></p>
                    <div class="flavor-plantilla-meta">
                        <span><span class="dashicons dashicons-clock"></span> <?php echo $plantilla->plazo_respuesta_dias; ?> días de plazo</span>
                        <span><span class="dashicons dashicons-chart-bar"></span> <?php echo $plantilla->usos; ?> usos</span>
                    </div>
                    <div class="flavor-plantilla-actions">
                        <button class="flavor-btn flavor-btn-primary flavor-btn-usar-plantilla" data-id="<?php echo esc_attr($plantilla->id); ?>">
                            Usar plantilla
                        </button>
                        <button class="flavor-btn flavor-btn-outline flavor-btn-preview-plantilla" data-id="<?php echo esc_attr($plantilla->id); ?>">
                            Vista previa
                        </button>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
            <?php else: ?>
            <div class="flavor-empty-state">
                <span class="dashicons dashicons-media-document"></span>
                <p>No hay plantillas disponibles</p>
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
        check_ajax_referer('flavor_denuncias_nonce', 'nonce');

        if (!is_user_logged_in()) {
            wp_send_json_error(['message' => 'Debes iniciar sesión']);
        }

        $user_id = get_current_user_id();

        wp_send_json_success([
            'estadisticas' => $this->obtener_estadisticas_usuario(),
            'denuncias_recientes' => $this->obtener_denuncias_usuario($user_id, 5),
            'plazos_proximos' => $this->obtener_plazos_proximos($user_id, 5)
        ]);
    }

    /**
     * AJAX: Buscar denuncias
     */
    public function ajax_buscar() {
        check_ajax_referer('flavor_denuncias_nonce', 'nonce');

        global $wpdb;
        $tabla = $wpdb->prefix . 'flavor_seguimiento_denuncias';

        $busqueda = sanitize_text_field($_POST['busqueda'] ?? '');
        $tipo = sanitize_text_field($_POST['tipo'] ?? '');
        $estado = sanitize_text_field($_POST['estado'] ?? '');
        $ambito = sanitize_text_field($_POST['ambito'] ?? '');
        $pagina = max(1, intval($_POST['pagina'] ?? 1));
        $por_pagina = 12;
        $offset = ($pagina - 1) * $por_pagina;

        $where = ["visibilidad IN ('publica', 'miembros')"];
        $params = [];

        if ($busqueda) {
            $where[] = "(titulo LIKE %s OR descripcion LIKE %s OR organismo_destino LIKE %s)";
            $like = '%' . $wpdb->esc_like($busqueda) . '%';
            $params[] = $like;
            $params[] = $like;
            $params[] = $like;
        }

        if ($tipo) {
            $where[] = "tipo = %s";
            $params[] = $tipo;
        }

        if ($estado) {
            $where[] = "estado = %s";
            $params[] = $estado;
        }

        if ($ambito) {
            $where[] = "ambito = %s";
            $params[] = $ambito;
        }

        $where_sql = implode(' AND ', $where);

        $sql = "SELECT * FROM $tabla WHERE $where_sql ORDER BY created_at DESC LIMIT %d OFFSET %d";
        $params[] = $por_pagina;
        $params[] = $offset;

        $denuncias = $wpdb->get_results($wpdb->prepare($sql, $params));

        // Total
        $sql_count = "SELECT COUNT(*) FROM $tabla WHERE $where_sql";
        $total = $wpdb->get_var($wpdb->prepare($sql_count, array_slice($params, 0, -2)));

        // Enriquecer datos
        foreach ($denuncias as &$denuncia) {
            $denuncia->tipo_info = $this->tipos[$denuncia->tipo] ?? $this->tipos['denuncia'];
            $denuncia->estado_info = $this->estados[$denuncia->estado] ?? $this->estados['presentada'];
            $denuncia->ambito_nombre = $this->ambitos[$denuncia->ambito] ?? $denuncia->ambito;
            $denuncia->url = home_url('/denuncias/?denuncia_id=' . $denuncia->id);

            // Calcular días restantes
            if ($denuncia->fecha_limite_respuesta) {
                $limite = strtotime($denuncia->fecha_limite_respuesta);
                $hoy = strtotime('today');
                $denuncia->dias_restantes = floor(($limite - $hoy) / 86400);
            } else {
                $denuncia->dias_restantes = null;
            }
        }

        wp_send_json_success([
            'denuncias' => $denuncias,
            'total' => intval($total),
            'paginas' => ceil($total / $por_pagina),
            'pagina_actual' => $pagina
        ]);
    }

    /**
     * AJAX: Obtener plantilla
     */
    public function ajax_obtener_plantilla() {
        check_ajax_referer('flavor_denuncias_nonce', 'nonce');

        global $wpdb;
        $tabla = $wpdb->prefix . 'flavor_seguimiento_denuncias_plantillas';

        $plantilla_id = intval($_POST['plantilla_id'] ?? 0);

        if (!$plantilla_id) {
            wp_send_json_error(['message' => 'ID de plantilla no válido']);
        }

        $plantilla = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM $tabla WHERE id = %d AND activa = 1",
            $plantilla_id
        ));

        if (!$plantilla) {
            wp_send_json_error(['message' => 'Plantilla no encontrada']);
        }

        // Incrementar contador de usos
        $wpdb->update($tabla, ['usos' => $plantilla->usos + 1], ['id' => $plantilla_id]);

        wp_send_json_success([
            'plantilla' => $plantilla
        ]);
    }

    /**
     * Obtiene estadísticas del usuario
     */
    private function obtener_estadisticas_usuario() {
        global $wpdb;
        $tabla = $wpdb->prefix . 'flavor_seguimiento_denuncias';
        $user_id = get_current_user_id();

        if (!$user_id) {
            return [
                'activas' => 0,
                'resueltas' => 0,
                'silencio' => 0,
                'plazos_proximos' => 0,
                'total' => 0
            ];
        }

        $fecha_limite = date('Y-m-d', strtotime('+7 days'));

        // Activas (en trámite)
        $activas = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM $tabla WHERE denunciante_id = %d AND estado IN ('presentada', 'en_tramite', 'requerimiento')",
            $user_id
        ));

        // Silencio administrativo
        $silencio = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM $tabla WHERE denunciante_id = %d AND estado = 'silencio'",
            $user_id
        ));

        // Resueltas
        $resueltas = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM $tabla WHERE denunciante_id = %d AND estado IN ('resuelta_favorable', 'resuelta_desfavorable')",
            $user_id
        ));

        // Plazos próximos (7 días)
        $plazos_proximos = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM $tabla
             WHERE denunciante_id = %d
             AND estado IN ('presentada', 'en_tramite', 'requerimiento')
             AND fecha_limite_respuesta IS NOT NULL
             AND fecha_limite_respuesta <= %s",
            $user_id,
            $fecha_limite
        ));

        // Total
        $total = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM $tabla WHERE denunciante_id = %d",
            $user_id
        ));

        return [
            'activas' => intval($activas),
            'resueltas' => intval($resueltas),
            'silencio' => intval($silencio),
            'plazos_proximos' => intval($plazos_proximos),
            'total' => intval($total)
        ];
    }

    /**
     * Obtiene denuncias del usuario
     */
    private function obtener_denuncias_usuario($user_id, $limite = 10) {
        global $wpdb;
        $tabla = $wpdb->prefix . 'flavor_seguimiento_denuncias';

        return $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM $tabla
             WHERE denunciante_id = %d
             ORDER BY created_at DESC
             LIMIT %d",
            $user_id,
            $limite
        ));
    }

    /**
     * Obtiene denuncias con plazos próximos
     */
    private function obtener_plazos_proximos($user_id, $limite = 5) {
        global $wpdb;
        $tabla = $wpdb->prefix . 'flavor_seguimiento_denuncias';
        $fecha_limite = date('Y-m-d', strtotime('+14 days'));

        $denuncias = $wpdb->get_results($wpdb->prepare(
            "SELECT *, DATEDIFF(fecha_limite_respuesta, CURDATE()) as dias_restantes
             FROM $tabla
             WHERE denunciante_id = %d
             AND estado IN ('presentada', 'en_tramite', 'requerimiento')
             AND fecha_limite_respuesta IS NOT NULL
             AND fecha_limite_respuesta <= %s
             ORDER BY fecha_limite_respuesta ASC
             LIMIT %d",
            $user_id,
            $fecha_limite,
            $limite
        ));

        return $denuncias;
    }

    /**
     * Obtiene plantillas disponibles
     */
    private function obtener_plantillas($categoria = '', $limite = 12) {
        global $wpdb;
        $tabla = $wpdb->prefix . 'flavor_seguimiento_denuncias_plantillas';

        $where = "activa = 1";
        $params = [];

        if ($categoria) {
            $where .= " AND categoria = %s";
            $params[] = $categoria;
        }

        $params[] = $limite;

        return $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM $tabla WHERE $where ORDER BY usos DESC LIMIT %d",
            $params
        ));
    }
}

// Inicializar
Flavor_Seguimiento_Denuncias_Frontend_Controller::get_instance();

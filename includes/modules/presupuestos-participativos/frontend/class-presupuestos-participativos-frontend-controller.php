<?php
/**
 * Frontend Controller para Presupuestos Participativos
 *
 * @package FlavorChatIA
 * @subpackage Modules\PresupuestosParticipativos
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Controlador Frontend para el módulo de Presupuestos Participativos
 */
class Flavor_Presupuestos_Participativos_Frontend_Controller {

    /**
     * Instancia singleton
     */
    private static $instance = null;

    /**
     * Obtener instancia
     */
    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Constructor
     */
    private function __construct() {
        $this->init();
    }

    /**
     * Inicializar hooks
     */
    private function init() {
        add_action('wp_enqueue_scripts', [$this, 'registrar_assets']);
        add_action('init', [$this, 'registrar_shortcodes']);

        // AJAX handlers
        add_action('wp_ajax_pp_crear_propuesta', [$this, 'ajax_crear_propuesta']);
        add_action('wp_ajax_pp_votar_propuesta', [$this, 'ajax_votar_propuesta']);
        add_action('wp_ajax_pp_apoyar_propuesta', [$this, 'ajax_apoyar_propuesta']);
        add_action('wp_ajax_pp_comentar', [$this, 'ajax_comentar']);
        add_action('wp_ajax_pp_filtrar_propuestas', [$this, 'ajax_filtrar_propuestas']);
        add_action('wp_ajax_nopriv_pp_filtrar_propuestas', [$this, 'ajax_filtrar_propuestas']);
        add_action('wp_ajax_pp_obtener_procesos', [$this, 'ajax_obtener_procesos']);
        add_action('wp_ajax_nopriv_pp_obtener_procesos', [$this, 'ajax_obtener_procesos']);
    }

    /**
     * Registrar assets
     */
    public function registrar_assets() {
        $base_url = plugins_url('assets/', dirname(__FILE__));
        $version = FLAVOR_CHAT_IA_VERSION ?? '1.0.0';

        wp_register_style(
            'flavor-presupuestos',
            $base_url . 'css/presupuestos-participativos.css',
            [],
            $version
        );

        wp_register_script(
            'flavor-presupuestos',
            $base_url . 'js/presupuestos-participativos.js',
            ['jquery'],
            $version,
            true
        );

        wp_localize_script('flavor-presupuestos', 'flavorPP', [
            'ajaxUrl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('pp_nonce'),
            'i18n' => [
                'voto_registrado' => __('Voto registrado correctamente', 'flavor-chat-ia'),
                'apoyo_registrado' => __('Apoyo registrado', 'flavor-chat-ia'),
                'propuesta_creada' => __('Propuesta creada correctamente', 'flavor-chat-ia'),
                'error' => __('Ha ocurrido un error', 'flavor-chat-ia'),
                'cargando' => __('Cargando...', 'flavor-chat-ia'),
                'confirmar_voto' => __('¿Confirmas tu voto para esta propuesta?', 'flavor-chat-ia'),
                'sin_votos' => __('No te quedan votos disponibles', 'flavor-chat-ia'),
            ],
        ]);
    }

    /**
     * Encolar assets
     */
    public function encolar_assets() {
        wp_enqueue_style('flavor-presupuestos');
        wp_enqueue_script('flavor-presupuestos');
    }

    /**
     * Registrar shortcodes
     */
    public function registrar_shortcodes() {
        add_shortcode('pp_proceso_activo', [$this, 'shortcode_proceso_activo']);
        add_shortcode('pp_listado_procesos', [$this, 'shortcode_listado_procesos']);
        add_shortcode('pp_propuestas', [$this, 'shortcode_propuestas']);
        add_shortcode('pp_detalle_propuesta', [$this, 'shortcode_detalle_propuesta']);
        add_shortcode('pp_crear_propuesta', [$this, 'shortcode_crear_propuesta']);
        add_shortcode('pp_mis_propuestas', [$this, 'shortcode_mis_propuestas']);
        add_shortcode('pp_resultados', [$this, 'shortcode_resultados']);
        add_shortcode('pp_votacion', [$this, 'shortcode_votacion']);
    }

    /**
     * Shortcode: Proceso activo
     */
    public function shortcode_proceso_activo($atts) {
        $this->encolar_assets();

        global $wpdb;
        $tabla_procesos = $wpdb->prefix . 'flavor_pp_procesos';

        if (!Flavor_Chat_Helpers::tabla_existe($tabla_procesos)) {
            return '<p class="flavor-error">' . __('El módulo no está configurado.', 'flavor-chat-ia') . '</p>';
        }

        $proceso = $wpdb->get_row(
            "SELECT * FROM {$tabla_procesos}
             WHERE estado IN ('propuestas', 'votacion')
             ORDER BY fecha_inicio DESC LIMIT 1"
        );

        if (!$proceso) {
            ob_start();
            ?>
            <div class="flavor-pp-no-proceso">
                <div class="flavor-empty-state">
                    <span class="dashicons dashicons-chart-pie"></span>
                    <h3><?php esc_html_e('Sin procesos activos', 'flavor-chat-ia'); ?></h3>
                    <p><?php esc_html_e('No hay procesos de presupuestos participativos activos en este momento.', 'flavor-chat-ia'); ?></p>
                    <a href="<?php echo esc_url(home_url('/presupuestos-participativos/historico/')); ?>" class="flavor-btn flavor-btn-secondary">
                        <?php esc_html_e('Ver procesos anteriores', 'flavor-chat-ia'); ?>
                    </a>
                </div>
            </div>
            <?php
            return ob_get_clean();
        }

        $tabla_propuestas = $wpdb->prefix . 'flavor_pp_propuestas';
        $total_propuestas = (int) $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM {$tabla_propuestas}
             WHERE proceso_id = %d AND estado NOT IN ('borrador', 'rechazada')",
            $proceso->id
        ));

        ob_start();
        ?>
        <div class="flavor-pp-proceso-activo">
            <div class="proceso-hero">
                <?php if (!empty($proceso->imagen_destacada)): ?>
                    <div class="proceso-imagen" style="background-image: url('<?php echo esc_url($proceso->imagen_destacada); ?>');">
                <?php else: ?>
                    <div class="proceso-imagen proceso-imagen-default">
                <?php endif; ?>
                    <div class="proceso-overlay">
                        <span class="proceso-fase flavor-badge flavor-badge-<?php echo $proceso->estado === 'votacion' ? 'success' : 'primary'; ?>">
                            <?php echo $proceso->estado === 'votacion'
                                ? esc_html__('Fase de Votación', 'flavor-chat-ia')
                                : esc_html__('Fase de Propuestas', 'flavor-chat-ia'); ?>
                        </span>
                        <h1><?php echo esc_html($proceso->titulo); ?></h1>
                        <p class="proceso-periodo">
                            <?php echo date_i18n('d M Y', strtotime($proceso->fecha_inicio)); ?> -
                            <?php echo date_i18n('d M Y', strtotime($proceso->fecha_fin)); ?>
                        </p>
                    </div>
                </div>
            </div>

            <div class="proceso-stats">
                <div class="stat-card">
                    <span class="stat-value"><?php echo number_format_i18n($proceso->presupuesto_total, 0); ?> €</span>
                    <span class="stat-label"><?php esc_html_e('Presupuesto Total', 'flavor-chat-ia'); ?></span>
                </div>
                <div class="stat-card">
                    <span class="stat-value"><?php echo number_format_i18n($total_propuestas); ?></span>
                    <span class="stat-label"><?php esc_html_e('Propuestas', 'flavor-chat-ia'); ?></span>
                </div>
                <div class="stat-card">
                    <span class="stat-value"><?php echo number_format_i18n($proceso->total_votantes); ?></span>
                    <span class="stat-label"><?php esc_html_e('Participantes', 'flavor-chat-ia'); ?></span>
                </div>
            </div>

            <?php if ($proceso->descripcion): ?>
                <div class="proceso-descripcion">
                    <?php echo wp_kses_post($proceso->descripcion); ?>
                </div>
            <?php endif; ?>

            <div class="proceso-timeline">
                <h3><?php esc_html_e('Fases del Proceso', 'flavor-chat-ia'); ?></h3>
                <div class="timeline">
                    <div class="timeline-item <?php echo $proceso->estado === 'propuestas' ? 'active' : ($proceso->estado === 'votacion' ? 'completed' : ''); ?>">
                        <span class="timeline-icon"><span class="dashicons dashicons-lightbulb"></span></span>
                        <div class="timeline-content">
                            <h4><?php esc_html_e('Propuestas', 'flavor-chat-ia'); ?></h4>
                            <?php if ($proceso->fecha_inicio_propuestas && $proceso->fecha_fin_propuestas): ?>
                                <p><?php echo date_i18n('d/m', strtotime($proceso->fecha_inicio_propuestas)); ?> - <?php echo date_i18n('d/m', strtotime($proceso->fecha_fin_propuestas)); ?></p>
                            <?php endif; ?>
                        </div>
                    </div>
                    <div class="timeline-item <?php echo $proceso->estado === 'votacion' ? 'active' : ''; ?>">
                        <span class="timeline-icon"><span class="dashicons dashicons-yes-alt"></span></span>
                        <div class="timeline-content">
                            <h4><?php esc_html_e('Votación', 'flavor-chat-ia'); ?></h4>
                            <?php if ($proceso->fecha_inicio_votacion && $proceso->fecha_fin_votacion): ?>
                                <p><?php echo date_i18n('d/m', strtotime($proceso->fecha_inicio_votacion)); ?> - <?php echo date_i18n('d/m', strtotime($proceso->fecha_fin_votacion)); ?></p>
                            <?php endif; ?>
                        </div>
                    </div>
                    <div class="timeline-item">
                        <span class="timeline-icon"><span class="dashicons dashicons-chart-bar"></span></span>
                        <div class="timeline-content">
                            <h4><?php esc_html_e('Resultados', 'flavor-chat-ia'); ?></h4>
                        </div>
                    </div>
                </div>
            </div>

            <div class="proceso-cta">
                <?php if ($proceso->estado === 'propuestas' && is_user_logged_in()): ?>
                    <a href="<?php echo esc_url(home_url('/presupuestos/' . $proceso->slug . '/nueva-propuesta')); ?>" class="flavor-btn flavor-btn-primary flavor-btn-lg">
                        <span class="dashicons dashicons-plus-alt2"></span>
                        <?php esc_html_e('Presentar Propuesta', 'flavor-chat-ia'); ?>
                    </a>
                <?php endif; ?>
                <a href="<?php echo esc_url(home_url('/presupuestos/' . $proceso->slug . '/propuestas')); ?>" class="flavor-btn flavor-btn-secondary flavor-btn-lg">
                    <?php esc_html_e('Ver Propuestas', 'flavor-chat-ia'); ?>
                </a>
            </div>
        </div>
        <?php
        return ob_get_clean();
    }

    /**
     * Shortcode: Listado de procesos
     */
    public function shortcode_listado_procesos($atts) {
        $this->encolar_assets();

        $atts = shortcode_atts([
            'estado' => '',
            'limite' => 10,
        ], $atts);

        global $wpdb;
        $tabla_procesos = $wpdb->prefix . 'flavor_pp_procesos';

        $where = "estado != 'borrador'";
        $params = [];

        if (!empty($atts['estado'])) {
            $where .= " AND estado = %s";
            $params[] = $atts['estado'];
        }

        $sql = "SELECT * FROM {$tabla_procesos} WHERE {$where} ORDER BY anio DESC, fecha_inicio DESC LIMIT %d";
        $params[] = intval($atts['limite']);

        $procesos = $wpdb->get_results($wpdb->prepare($sql, ...$params));

        $estados_labels = [
            'propuestas' => __('Fase de Propuestas', 'flavor-chat-ia'),
            'votacion' => __('En Votación', 'flavor-chat-ia'),
            'resultados' => __('Resultados', 'flavor-chat-ia'),
            'ejecucion' => __('En Ejecución', 'flavor-chat-ia'),
            'finalizado' => __('Finalizado', 'flavor-chat-ia'),
        ];

        ob_start();
        ?>
        <div class="flavor-pp-listado-procesos">
            <?php if (empty($procesos)): ?>
                <div class="flavor-empty-state">
                    <p><?php esc_html_e('No hay procesos disponibles.', 'flavor-chat-ia'); ?></p>
                </div>
            <?php else: ?>
                <div class="procesos-grid">
                    <?php foreach ($procesos as $proceso): ?>
                        <div class="proceso-card">
                            <div class="proceso-card-header">
                                <span class="proceso-anio"><?php echo esc_html($proceso->anio); ?></span>
                                <span class="flavor-badge flavor-badge-<?php echo $proceso->estado === 'votacion' ? 'success' : ($proceso->estado === 'propuestas' ? 'primary' : 'secondary'); ?>">
                                    <?php echo esc_html($estados_labels[$proceso->estado] ?? ucfirst($proceso->estado)); ?>
                                </span>
                            </div>
                            <h3><?php echo esc_html($proceso->titulo); ?></h3>
                            <p class="proceso-presupuesto">
                                <strong><?php echo number_format_i18n($proceso->presupuesto_total, 0); ?> €</strong>
                            </p>
                            <p class="proceso-stats-mini">
                                <span><?php echo number_format_i18n($proceso->total_propuestas); ?> <?php esc_html_e('propuestas', 'flavor-chat-ia'); ?></span>
                                <span><?php echo number_format_i18n($proceso->total_votantes); ?> <?php esc_html_e('participantes', 'flavor-chat-ia'); ?></span>
                            </p>
                            <a href="<?php echo esc_url(home_url('/presupuestos/' . $proceso->slug)); ?>" class="flavor-btn flavor-btn-outline flavor-btn-block">
                                <?php esc_html_e('Ver Proceso', 'flavor-chat-ia'); ?>
                            </a>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
        <?php
        return ob_get_clean();
    }

    /**
     * Shortcode: Propuestas
     */
    public function shortcode_propuestas($atts) {
        $this->encolar_assets();

        $atts = shortcode_atts([
            'proceso_id' => 0,
            'proceso_slug' => '',
            'categoria' => '',
            'estado' => '',
            'limite' => 20,
            'orden' => 'recientes',
            'mostrar_filtros' => 'true',
        ], $atts);

        global $wpdb;
        $tabla_procesos = $wpdb->prefix . 'flavor_pp_procesos';
        $tabla_propuestas = $wpdb->prefix . 'flavor_pp_propuestas';
        $tabla_categorias = $wpdb->prefix . 'flavor_pp_categorias';

        // Obtener proceso
        $proceso = null;
        if ($atts['proceso_id']) {
            $proceso = $wpdb->get_row($wpdb->prepare(
                "SELECT * FROM {$tabla_procesos} WHERE id = %d", $atts['proceso_id']
            ));
        } elseif ($atts['proceso_slug']) {
            $proceso = $wpdb->get_row($wpdb->prepare(
                "SELECT * FROM {$tabla_procesos} WHERE slug = %s", $atts['proceso_slug']
            ));
        } else {
            // Proceso activo
            $proceso = $wpdb->get_row(
                "SELECT * FROM {$tabla_procesos} WHERE estado IN ('propuestas', 'votacion') ORDER BY fecha_inicio DESC LIMIT 1"
            );
        }

        if (!$proceso) {
            return '<p class="flavor-error">' . __('Proceso no encontrado.', 'flavor-chat-ia') . '</p>';
        }

        // Obtener categorías
        $categorias = $wpdb->get_results("SELECT * FROM {$tabla_categorias} WHERE activa = 1 ORDER BY orden");

        // Construir consulta
        $where = ["proceso_id = %d", "estado NOT IN ('borrador', 'rechazada')"];
        $params = [$proceso->id];

        if (!empty($atts['categoria'])) {
            $where[] = "categoria_id = %d";
            $params[] = intval($atts['categoria']);
        }

        if (!empty($atts['estado'])) {
            $where[] = "estado = %s";
            $params[] = $atts['estado'];
        }

        $orden_sql = match ($atts['orden']) {
            'votos' => 'votos_total DESC',
            'apoyos' => 'apoyos_count DESC',
            'presupuesto' => 'presupuesto_estimado DESC',
            default => 'created_at DESC',
        };

        $sql = "SELECT p.*, c.nombre as categoria_nombre, c.color as categoria_color
                FROM {$tabla_propuestas} p
                LEFT JOIN {$tabla_categorias} c ON p.categoria_id = c.id
                WHERE " . implode(' AND ', $where) . "
                ORDER BY {$orden_sql}
                LIMIT %d";
        $params[] = intval($atts['limite']);

        $propuestas = $wpdb->get_results($wpdb->prepare($sql, ...$params));

        ob_start();
        ?>
        <div class="flavor-pp-propuestas" data-proceso="<?php echo esc_attr($proceso->id); ?>">
            <?php if ($atts['mostrar_filtros'] === 'true'): ?>
                <div class="propuestas-filtros">
                    <div class="filtros-row">
                        <select id="filtro-categoria" class="filtro-select">
                            <option value=""><?php esc_html_e('Todas las categorías', 'flavor-chat-ia'); ?></option>
                            <?php foreach ($categorias as $cat): ?>
                                <option value="<?php echo esc_attr($cat->id); ?>" <?php selected($atts['categoria'], $cat->id); ?>>
                                    <?php echo esc_html($cat->nombre); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <select id="filtro-orden" class="filtro-select">
                            <option value="recientes" <?php selected($atts['orden'], 'recientes'); ?>><?php esc_html_e('Más recientes', 'flavor-chat-ia'); ?></option>
                            <option value="votos" <?php selected($atts['orden'], 'votos'); ?>><?php esc_html_e('Más votadas', 'flavor-chat-ia'); ?></option>
                            <option value="apoyos" <?php selected($atts['orden'], 'apoyos'); ?>><?php esc_html_e('Más apoyadas', 'flavor-chat-ia'); ?></option>
                            <option value="presupuesto" <?php selected($atts['orden'], 'presupuesto'); ?>><?php esc_html_e('Mayor presupuesto', 'flavor-chat-ia'); ?></option>
                        </select>
                        <input type="search" id="filtro-busqueda" placeholder="<?php esc_attr_e('Buscar...', 'flavor-chat-ia'); ?>" class="filtro-input">
                    </div>
                </div>
            <?php endif; ?>

            <div class="propuestas-grid" id="propuestas-container">
                <?php if (empty($propuestas)): ?>
                    <div class="flavor-empty-state">
                        <p><?php esc_html_e('No hay propuestas que mostrar.', 'flavor-chat-ia'); ?></p>
                    </div>
                <?php else: ?>
                    <?php foreach ($propuestas as $propuesta): ?>
                        <?php $this->render_propuesta_card($propuesta, $proceso); ?>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>
        <?php
        return ob_get_clean();
    }

    /**
     * Renderizar tarjeta de propuesta
     */
    private function render_propuesta_card($propuesta, $proceso) {
        $en_votacion = $proceso->estado === 'votacion' && $propuesta->estado === 'en_votacion';
        ?>
        <div class="propuesta-card" data-id="<?php echo esc_attr($propuesta->id); ?>">
            <?php if (!empty($propuesta->imagen_principal)): ?>
                <div class="propuesta-imagen">
                    <img src="<?php echo esc_url($propuesta->imagen_principal); ?>" alt="<?php echo esc_attr($propuesta->titulo); ?>">
                </div>
            <?php endif; ?>

            <div class="propuesta-body">
                <?php if (!empty($propuesta->categoria_nombre)): ?>
                    <span class="propuesta-categoria" style="background-color: <?php echo esc_attr($propuesta->categoria_color ?? '#6b7280'); ?>">
                        <?php echo esc_html($propuesta->categoria_nombre); ?>
                    </span>
                <?php endif; ?>

                <h3><?php echo esc_html($propuesta->titulo); ?></h3>

                <?php if ($propuesta->descripcion_corta): ?>
                    <p class="propuesta-descripcion"><?php echo esc_html($propuesta->descripcion_corta); ?></p>
                <?php else: ?>
                    <p class="propuesta-descripcion"><?php echo esc_html(wp_trim_words($propuesta->descripcion, 25)); ?></p>
                <?php endif; ?>

                <div class="propuesta-presupuesto">
                    <span class="presupuesto-valor"><?php echo number_format_i18n($propuesta->presupuesto_estimado, 0); ?> €</span>
                </div>

                <div class="propuesta-stats">
                    <?php if ($en_votacion): ?>
                        <span class="stat">
                            <span class="dashicons dashicons-yes-alt"></span>
                            <?php echo number_format_i18n($propuesta->votos_total); ?> <?php esc_html_e('votos', 'flavor-chat-ia'); ?>
                        </span>
                    <?php else: ?>
                        <span class="stat">
                            <span class="dashicons dashicons-heart"></span>
                            <?php echo number_format_i18n($propuesta->apoyos_count); ?> <?php esc_html_e('apoyos', 'flavor-chat-ia'); ?>
                        </span>
                    <?php endif; ?>
                    <span class="stat">
                        <span class="dashicons dashicons-admin-comments"></span>
                        <?php echo number_format_i18n($propuesta->comentarios_count); ?>
                    </span>
                </div>
            </div>

            <div class="propuesta-footer">
                <a href="<?php echo esc_url(home_url('/presupuestos/' . $proceso->slug . '/propuesta/' . $propuesta->slug)); ?>" class="flavor-btn flavor-btn-outline">
                    <?php esc_html_e('Ver Detalles', 'flavor-chat-ia'); ?>
                </a>
                <?php if ($en_votacion && is_user_logged_in()): ?>
                    <button type="button" class="flavor-btn flavor-btn-primary btn-votar" data-propuesta="<?php echo esc_attr($propuesta->id); ?>">
                        <span class="dashicons dashicons-yes-alt"></span>
                        <?php esc_html_e('Votar', 'flavor-chat-ia'); ?>
                    </button>
                <?php elseif ($proceso->estado === 'propuestas' && is_user_logged_in()): ?>
                    <button type="button" class="flavor-btn flavor-btn-secondary btn-apoyar" data-propuesta="<?php echo esc_attr($propuesta->id); ?>">
                        <span class="dashicons dashicons-heart"></span>
                        <?php esc_html_e('Apoyar', 'flavor-chat-ia'); ?>
                    </button>
                <?php endif; ?>
            </div>
        </div>
        <?php
    }

    /**
     * Shortcode: Detalle de propuesta
     */
    public function shortcode_detalle_propuesta($atts) {
        $this->encolar_assets();

        $atts = shortcode_atts([
            'id' => 0,
            'slug' => '',
        ], $atts);

        global $wpdb;
        $tabla_propuestas = $wpdb->prefix . 'flavor_pp_propuestas';
        $tabla_procesos = $wpdb->prefix . 'flavor_pp_procesos';
        $tabla_categorias = $wpdb->prefix . 'flavor_pp_categorias';
        $tabla_comentarios = $wpdb->prefix . 'flavor_pp_comentarios';

        $propuesta = null;
        if ($atts['id']) {
            $propuesta = $wpdb->get_row($wpdb->prepare(
                "SELECT p.*, c.nombre as categoria_nombre, c.color as categoria_color, c.icono as categoria_icono
                 FROM {$tabla_propuestas} p
                 LEFT JOIN {$tabla_categorias} c ON p.categoria_id = c.id
                 WHERE p.id = %d",
                $atts['id']
            ));
        } elseif ($atts['slug']) {
            $propuesta = $wpdb->get_row($wpdb->prepare(
                "SELECT p.*, c.nombre as categoria_nombre, c.color as categoria_color, c.icono as categoria_icono
                 FROM {$tabla_propuestas} p
                 LEFT JOIN {$tabla_categorias} c ON p.categoria_id = c.id
                 WHERE p.slug = %s",
                $atts['slug']
            ));
        }

        if (!$propuesta) {
            return '<p class="flavor-error">' . __('Propuesta no encontrada.', 'flavor-chat-ia') . '</p>';
        }

        $proceso = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$tabla_procesos} WHERE id = %d",
            $propuesta->proceso_id
        ));

        $comentarios = $wpdb->get_results($wpdb->prepare(
            "SELECT c.*, u.display_name as autor_nombre
             FROM {$tabla_comentarios} c
             LEFT JOIN {$wpdb->users} u ON c.usuario_id = u.ID
             WHERE c.propuesta_id = %d AND c.estado = 'aprobado' AND c.parent_id = 0
             ORDER BY c.created_at DESC
             LIMIT 50",
            $propuesta->id
        ));

        $autor = get_userdata($propuesta->usuario_id);
        $en_votacion = $proceso && $proceso->estado === 'votacion' && $propuesta->estado === 'en_votacion';

        ob_start();
        ?>
        <div class="flavor-pp-detalle-propuesta">
            <div class="propuesta-header">
                <?php if (!empty($propuesta->imagen_principal)): ?>
                    <div class="propuesta-imagen-principal">
                        <img src="<?php echo esc_url($propuesta->imagen_principal); ?>" alt="<?php echo esc_attr($propuesta->titulo); ?>">
                    </div>
                <?php endif; ?>

                <div class="propuesta-info-header">
                    <?php if (!empty($propuesta->categoria_nombre)): ?>
                        <span class="propuesta-categoria" style="background-color: <?php echo esc_attr($propuesta->categoria_color ?? '#6b7280'); ?>">
                            <?php echo esc_html($propuesta->categoria_nombre); ?>
                        </span>
                    <?php endif; ?>

                    <h1><?php echo esc_html($propuesta->titulo); ?></h1>

                    <div class="propuesta-meta">
                        <span class="meta-autor">
                            <span class="dashicons dashicons-admin-users"></span>
                            <?php echo esc_html($autor ? $autor->display_name : __('Anónimo', 'flavor-chat-ia')); ?>
                        </span>
                        <span class="meta-fecha">
                            <span class="dashicons dashicons-calendar-alt"></span>
                            <?php echo date_i18n('d/m/Y', strtotime($propuesta->created_at)); ?>
                        </span>
                    </div>
                </div>
            </div>

            <div class="propuesta-stats-detalle">
                <div class="stat-card stat-presupuesto">
                    <span class="stat-value"><?php echo number_format_i18n($propuesta->presupuesto_estimado, 0); ?> €</span>
                    <span class="stat-label"><?php esc_html_e('Presupuesto Estimado', 'flavor-chat-ia'); ?></span>
                </div>
                <?php if ($en_votacion): ?>
                    <div class="stat-card stat-votos">
                        <span class="stat-value"><?php echo number_format_i18n($propuesta->votos_total); ?></span>
                        <span class="stat-label"><?php esc_html_e('Votos', 'flavor-chat-ia'); ?></span>
                    </div>
                <?php else: ?>
                    <div class="stat-card stat-apoyos">
                        <span class="stat-value"><?php echo number_format_i18n($propuesta->apoyos_count); ?></span>
                        <span class="stat-label"><?php esc_html_e('Apoyos', 'flavor-chat-ia'); ?></span>
                    </div>
                <?php endif; ?>
                <div class="stat-card stat-comentarios">
                    <span class="stat-value"><?php echo number_format_i18n($propuesta->comentarios_count); ?></span>
                    <span class="stat-label"><?php esc_html_e('Comentarios', 'flavor-chat-ia'); ?></span>
                </div>
            </div>

            <?php if (is_user_logged_in() && $proceso): ?>
                <div class="propuesta-acciones">
                    <?php if ($en_votacion): ?>
                        <button type="button" class="flavor-btn flavor-btn-primary flavor-btn-lg btn-votar" data-propuesta="<?php echo esc_attr($propuesta->id); ?>">
                            <span class="dashicons dashicons-yes-alt"></span>
                            <?php esc_html_e('Votar esta Propuesta', 'flavor-chat-ia'); ?>
                        </button>
                    <?php elseif ($proceso->estado === 'propuestas'): ?>
                        <button type="button" class="flavor-btn flavor-btn-secondary flavor-btn-lg btn-apoyar" data-propuesta="<?php echo esc_attr($propuesta->id); ?>">
                            <span class="dashicons dashicons-heart"></span>
                            <?php esc_html_e('Apoyar esta Propuesta', 'flavor-chat-ia'); ?>
                        </button>
                    <?php endif; ?>
                </div>
            <?php endif; ?>

            <div class="propuesta-contenido">
                <h2><?php esc_html_e('Descripción', 'flavor-chat-ia'); ?></h2>
                <div class="contenido-texto">
                    <?php echo wp_kses_post($propuesta->descripcion); ?>
                </div>

                <?php if ($propuesta->justificacion): ?>
                    <h3><?php esc_html_e('Justificación', 'flavor-chat-ia'); ?></h3>
                    <div class="contenido-texto">
                        <?php echo wp_kses_post($propuesta->justificacion); ?>
                    </div>
                <?php endif; ?>

                <?php if ($propuesta->beneficiarios): ?>
                    <h3><?php esc_html_e('Beneficiarios', 'flavor-chat-ia'); ?></h3>
                    <div class="contenido-texto">
                        <?php echo wp_kses_post($propuesta->beneficiarios); ?>
                    </div>
                <?php endif; ?>

                <?php if ($propuesta->ubicacion): ?>
                    <h3><?php esc_html_e('Ubicación', 'flavor-chat-ia'); ?></h3>
                    <p><span class="dashicons dashicons-location"></span> <?php echo esc_html($propuesta->ubicacion); ?></p>
                <?php endif; ?>
            </div>

            <div class="propuesta-comentarios" id="comentarios">
                <h2><?php esc_html_e('Comentarios', 'flavor-chat-ia'); ?> (<?php echo count($comentarios); ?>)</h2>

                <?php if (is_user_logged_in()): ?>
                    <form id="form-comentario" class="form-comentario">
                        <?php wp_nonce_field('pp_nonce', 'pp_nonce_field'); ?>
                        <input type="hidden" name="propuesta_id" value="<?php echo esc_attr($propuesta->id); ?>">
                        <textarea name="contenido" rows="3" placeholder="<?php esc_attr_e('Escribe tu comentario...', 'flavor-chat-ia'); ?>" required></textarea>
                        <button type="submit" class="flavor-btn flavor-btn-primary">
                            <?php esc_html_e('Comentar', 'flavor-chat-ia'); ?>
                        </button>
                    </form>
                <?php else: ?>
                    <p class="login-prompt"><?php esc_html_e('Inicia sesión para comentar.', 'flavor-chat-ia'); ?></p>
                <?php endif; ?>

                <div class="comentarios-lista">
                    <?php if (empty($comentarios)): ?>
                        <p class="sin-comentarios"><?php esc_html_e('Sé el primero en comentar.', 'flavor-chat-ia'); ?></p>
                    <?php else: ?>
                        <?php foreach ($comentarios as $comentario): ?>
                            <div class="comentario-item">
                                <div class="comentario-header">
                                    <span class="comentario-autor"><?php echo esc_html($comentario->autor_nombre); ?></span>
                                    <span class="comentario-fecha"><?php echo date_i18n('d/m/Y H:i', strtotime($comentario->created_at)); ?></span>
                                </div>
                                <div class="comentario-contenido">
                                    <?php echo esc_html($comentario->contenido); ?>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        <?php
        return ob_get_clean();
    }

    /**
     * Shortcode: Crear propuesta
     */
    public function shortcode_crear_propuesta($atts) {
        if (!is_user_logged_in()) {
            return '<p class="flavor-login-required">' . __('Debes iniciar sesión para presentar una propuesta.', 'flavor-chat-ia') . '</p>';
        }

        $this->encolar_assets();

        $atts = shortcode_atts([
            'proceso_id' => 0,
            'proceso_slug' => '',
        ], $atts);

        global $wpdb;
        $tabla_procesos = $wpdb->prefix . 'flavor_pp_procesos';
        $tabla_categorias = $wpdb->prefix . 'flavor_pp_categorias';

        $proceso = null;
        if ($atts['proceso_id']) {
            $proceso = $wpdb->get_row($wpdb->prepare(
                "SELECT * FROM {$tabla_procesos} WHERE id = %d AND estado = 'propuestas'",
                $atts['proceso_id']
            ));
        } elseif ($atts['proceso_slug']) {
            $proceso = $wpdb->get_row($wpdb->prepare(
                "SELECT * FROM {$tabla_procesos} WHERE slug = %s AND estado = 'propuestas'",
                $atts['proceso_slug']
            ));
        } else {
            $proceso = $wpdb->get_row(
                "SELECT * FROM {$tabla_procesos} WHERE estado = 'propuestas' ORDER BY fecha_inicio DESC LIMIT 1"
            );
        }

        if (!$proceso) {
            return '<div class="flavor-error">' . __('No hay un proceso activo para propuestas en este momento.', 'flavor-chat-ia') . '</div>';
        }

        $categorias = $wpdb->get_results("SELECT * FROM {$tabla_categorias} WHERE activa = 1 ORDER BY orden");

        ob_start();
        ?>
        <div class="flavor-pp-crear-propuesta">
            <h2><?php esc_html_e('Presentar Propuesta', 'flavor-chat-ia'); ?></h2>
            <p class="proceso-info"><?php printf(esc_html__('Para el proceso: %s', 'flavor-chat-ia'), '<strong>' . esc_html($proceso->titulo) . '</strong>'); ?></p>

            <form id="form-crear-propuesta" class="flavor-form" method="post" enctype="multipart/form-data">
                <?php wp_nonce_field('pp_nonce', 'pp_nonce_field'); ?>
                <input type="hidden" name="proceso_id" value="<?php echo esc_attr($proceso->id); ?>">

                <div class="flavor-form-group">
                    <label for="titulo"><?php esc_html_e('Título de la propuesta', 'flavor-chat-ia'); ?> *</label>
                    <input type="text" name="titulo" id="titulo" required maxlength="255">
                    <p class="flavor-form-help"><?php esc_html_e('Máximo 255 caracteres', 'flavor-chat-ia'); ?></p>
                </div>

                <div class="flavor-form-group">
                    <label for="categoria_id"><?php esc_html_e('Categoría', 'flavor-chat-ia'); ?> *</label>
                    <select name="categoria_id" id="categoria_id" required>
                        <option value=""><?php esc_html_e('Selecciona...', 'flavor-chat-ia'); ?></option>
                        <?php foreach ($categorias as $cat): ?>
                            <option value="<?php echo esc_attr($cat->id); ?>"><?php echo esc_html($cat->nombre); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="flavor-form-group">
                    <label for="descripcion_corta"><?php esc_html_e('Resumen', 'flavor-chat-ia'); ?></label>
                    <textarea name="descripcion_corta" id="descripcion_corta" rows="2" maxlength="500"></textarea>
                    <p class="flavor-form-help"><?php esc_html_e('Breve descripción (máx. 500 caracteres)', 'flavor-chat-ia'); ?></p>
                </div>

                <div class="flavor-form-group">
                    <label for="descripcion"><?php esc_html_e('Descripción completa', 'flavor-chat-ia'); ?> *</label>
                    <textarea name="descripcion" id="descripcion" rows="6" required></textarea>
                </div>

                <div class="flavor-form-group">
                    <label for="justificacion"><?php esc_html_e('Justificación', 'flavor-chat-ia'); ?></label>
                    <textarea name="justificacion" id="justificacion" rows="4"></textarea>
                    <p class="flavor-form-help"><?php esc_html_e('¿Por qué es necesaria esta propuesta?', 'flavor-chat-ia'); ?></p>
                </div>

                <div class="flavor-form-group">
                    <label for="beneficiarios"><?php esc_html_e('Beneficiarios', 'flavor-chat-ia'); ?></label>
                    <textarea name="beneficiarios" id="beneficiarios" rows="2"></textarea>
                    <p class="flavor-form-help"><?php esc_html_e('¿A quién beneficiará?', 'flavor-chat-ia'); ?></p>
                </div>

                <div class="flavor-form-row">
                    <div class="flavor-form-group flavor-form-col-6">
                        <label for="presupuesto_estimado"><?php esc_html_e('Presupuesto estimado (€)', 'flavor-chat-ia'); ?> *</label>
                        <input type="number" name="presupuesto_estimado" id="presupuesto_estimado" required min="0" step="0.01"
                            <?php if ($proceso->presupuesto_max_propuesta): ?>max="<?php echo esc_attr($proceso->presupuesto_max_propuesta); ?>"<?php endif; ?>
                            <?php if ($proceso->presupuesto_min_propuesta): ?>min="<?php echo esc_attr($proceso->presupuesto_min_propuesta); ?>"<?php endif; ?>>
                        <?php if ($proceso->presupuesto_max_propuesta): ?>
                            <p class="flavor-form-help"><?php printf(esc_html__('Máximo: %s €', 'flavor-chat-ia'), number_format_i18n($proceso->presupuesto_max_propuesta, 0)); ?></p>
                        <?php endif; ?>
                    </div>
                    <div class="flavor-form-group flavor-form-col-6">
                        <label for="ubicacion"><?php esc_html_e('Ubicación', 'flavor-chat-ia'); ?></label>
                        <input type="text" name="ubicacion" id="ubicacion" placeholder="<?php esc_attr_e('Dirección o zona', 'flavor-chat-ia'); ?>">
                    </div>
                </div>

                <div class="flavor-form-group">
                    <label for="imagen_principal"><?php esc_html_e('Imagen principal', 'flavor-chat-ia'); ?></label>
                    <input type="file" name="imagen_principal" id="imagen_principal" accept="image/*">
                </div>

                <div class="flavor-form-actions">
                    <button type="submit" name="guardar_borrador" value="1" class="flavor-btn flavor-btn-secondary">
                        <?php esc_html_e('Guardar como borrador', 'flavor-chat-ia'); ?>
                    </button>
                    <button type="submit" class="flavor-btn flavor-btn-primary">
                        <?php esc_html_e('Enviar Propuesta', 'flavor-chat-ia'); ?>
                    </button>
                </div>
            </form>
        </div>
        <?php
        return ob_get_clean();
    }

    /**
     * Shortcode: Mis propuestas
     */
    public function shortcode_mis_propuestas($atts) {
        if (!is_user_logged_in()) {
            return '<p class="flavor-login-required">' . __('Debes iniciar sesión.', 'flavor-chat-ia') . '</p>';
        }

        $this->encolar_assets();
        $usuario_id = get_current_user_id();

        global $wpdb;
        $tabla_propuestas = $wpdb->prefix . 'flavor_pp_propuestas';
        $tabla_procesos = $wpdb->prefix . 'flavor_pp_procesos';

        $propuestas = $wpdb->get_results($wpdb->prepare(
            "SELECT p.*, pr.titulo as proceso_titulo, pr.slug as proceso_slug
             FROM {$tabla_propuestas} p
             JOIN {$tabla_procesos} pr ON p.proceso_id = pr.id
             WHERE p.usuario_id = %d
             ORDER BY p.created_at DESC",
            $usuario_id
        ));

        $estados_colores = [
            'borrador' => 'secondary',
            'pendiente' => 'warning',
            'en_revision' => 'info',
            'viable' => 'success',
            'no_viable' => 'danger',
            'en_votacion' => 'primary',
            'aprobada' => 'success',
            'rechazada' => 'danger',
            'en_ejecucion' => 'info',
            'completada' => 'secondary',
        ];

        ob_start();
        ?>
        <div class="flavor-pp-mis-propuestas">
            <h2><?php esc_html_e('Mis Propuestas', 'flavor-chat-ia'); ?></h2>

            <?php if (empty($propuestas)): ?>
                <div class="flavor-empty-state">
                    <span class="dashicons dashicons-lightbulb"></span>
                    <p><?php esc_html_e('No has presentado ninguna propuesta todavía.', 'flavor-chat-ia'); ?></p>
                    <a href="<?php echo esc_url(home_url('/presupuestos-participativos/')); ?>" class="flavor-btn flavor-btn-primary">
                        <?php esc_html_e('Ver procesos activos', 'flavor-chat-ia'); ?>
                    </a>
                </div>
            <?php else: ?>
                <div class="flavor-table-responsive">
                    <table class="flavor-table">
                        <thead>
                            <tr>
                                <th><?php esc_html_e('Propuesta', 'flavor-chat-ia'); ?></th>
                                <th><?php esc_html_e('Proceso', 'flavor-chat-ia'); ?></th>
                                <th><?php esc_html_e('Presupuesto', 'flavor-chat-ia'); ?></th>
                                <th><?php esc_html_e('Votos', 'flavor-chat-ia'); ?></th>
                                <th><?php esc_html_e('Estado', 'flavor-chat-ia'); ?></th>
                                <th><?php esc_html_e('Acciones', 'flavor-chat-ia'); ?></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($propuestas as $propuesta): ?>
                                <tr>
                                    <td><?php echo esc_html(wp_trim_words($propuesta->titulo, 6)); ?></td>
                                    <td><?php echo esc_html(wp_trim_words($propuesta->proceso_titulo, 4)); ?></td>
                                    <td><?php echo number_format_i18n($propuesta->presupuesto_estimado, 0); ?> €</td>
                                    <td><?php echo number_format_i18n($propuesta->votos_total); ?></td>
                                    <td>
                                        <span class="flavor-badge flavor-badge-<?php echo esc_attr($estados_colores[$propuesta->estado] ?? 'secondary'); ?>">
                                            <?php echo esc_html(ucfirst(str_replace('_', ' ', $propuesta->estado))); ?>
                                        </span>
                                    </td>
                                    <td>
                                        <a href="<?php echo esc_url(home_url('/presupuestos/' . $propuesta->proceso_slug . '/propuesta/' . $propuesta->slug)); ?>" class="flavor-btn flavor-btn-sm flavor-btn-outline">
                                            <?php esc_html_e('Ver', 'flavor-chat-ia'); ?>
                                        </a>
                                        <?php if ($propuesta->estado === 'borrador'): ?>
                                            <a href="<?php echo esc_url(home_url('/presupuestos/' . $propuesta->proceso_slug . '/editar/' . $propuesta->id)); ?>" class="flavor-btn flavor-btn-sm flavor-btn-secondary">
                                                <?php esc_html_e('Editar', 'flavor-chat-ia'); ?>
                                            </a>
                                        <?php endif; ?>
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
     * Shortcode: Resultados
     */
    public function shortcode_resultados($atts) {
        $this->encolar_assets();

        $atts = shortcode_atts([
            'proceso_id' => 0,
            'proceso_slug' => '',
            'limite' => 20,
        ], $atts);

        global $wpdb;
        $tabla_procesos = $wpdb->prefix . 'flavor_pp_procesos';
        $tabla_propuestas = $wpdb->prefix . 'flavor_pp_propuestas';

        $proceso = null;
        if ($atts['proceso_id']) {
            $proceso = $wpdb->get_row($wpdb->prepare(
                "SELECT * FROM {$tabla_procesos} WHERE id = %d",
                $atts['proceso_id']
            ));
        } elseif ($atts['proceso_slug']) {
            $proceso = $wpdb->get_row($wpdb->prepare(
                "SELECT * FROM {$tabla_procesos} WHERE slug = %s",
                $atts['proceso_slug']
            ));
        }

        if (!$proceso) {
            return '<p class="flavor-error">' . __('Proceso no encontrado.', 'flavor-chat-ia') . '</p>';
        }

        $propuestas = $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM {$tabla_propuestas}
             WHERE proceso_id = %d AND estado IN ('aprobada', 'en_ejecucion', 'completada', 'en_votacion')
             ORDER BY votos_total DESC
             LIMIT %d",
            $proceso->id,
            intval($atts['limite'])
        ));

        $presupuesto_aprobado = $wpdb->get_var($wpdb->prepare(
            "SELECT SUM(presupuesto_estimado) FROM {$tabla_propuestas}
             WHERE proceso_id = %d AND estado IN ('aprobada', 'en_ejecucion', 'completada')",
            $proceso->id
        ));

        ob_start();
        ?>
        <div class="flavor-pp-resultados">
            <h2><?php printf(esc_html__('Resultados: %s', 'flavor-chat-ia'), esc_html($proceso->titulo)); ?></h2>

            <div class="resultados-resumen">
                <div class="stat-card">
                    <span class="stat-value"><?php echo number_format_i18n($proceso->presupuesto_total, 0); ?> €</span>
                    <span class="stat-label"><?php esc_html_e('Presupuesto Total', 'flavor-chat-ia'); ?></span>
                </div>
                <div class="stat-card">
                    <span class="stat-value"><?php echo number_format_i18n($presupuesto_aprobado ?? 0, 0); ?> €</span>
                    <span class="stat-label"><?php esc_html_e('Presupuesto Aprobado', 'flavor-chat-ia'); ?></span>
                </div>
                <div class="stat-card">
                    <span class="stat-value"><?php echo number_format_i18n($proceso->total_votantes); ?></span>
                    <span class="stat-label"><?php esc_html_e('Votantes', 'flavor-chat-ia'); ?></span>
                </div>
            </div>

            <div class="resultados-ranking">
                <?php foreach ($propuestas as $index => $propuesta): ?>
                    <div class="resultado-item <?php echo in_array($propuesta->estado, ['aprobada', 'en_ejecucion', 'completada']) ? 'aprobada' : ''; ?>">
                        <span class="resultado-posicion"><?php echo $index + 1; ?></span>
                        <div class="resultado-info">
                            <h3><?php echo esc_html($propuesta->titulo); ?></h3>
                            <p class="resultado-presupuesto"><?php echo number_format_i18n($propuesta->presupuesto_estimado, 0); ?> €</p>
                        </div>
                        <div class="resultado-votos">
                            <span class="votos-count"><?php echo number_format_i18n($propuesta->votos_total); ?></span>
                            <span class="votos-label"><?php esc_html_e('votos', 'flavor-chat-ia'); ?></span>
                        </div>
                        <span class="resultado-estado">
                            <?php if (in_array($propuesta->estado, ['aprobada', 'en_ejecucion', 'completada'])): ?>
                                <span class="flavor-badge flavor-badge-success"><?php esc_html_e('Aprobada', 'flavor-chat-ia'); ?></span>
                            <?php endif; ?>
                        </span>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
        <?php
        return ob_get_clean();
    }

    /**
     * Shortcode: Votación
     */
    public function shortcode_votacion($atts) {
        if (!is_user_logged_in()) {
            return '<p class="flavor-login-required">' . __('Debes iniciar sesión para votar.', 'flavor-chat-ia') . '</p>';
        }

        $this->encolar_assets();

        global $wpdb;
        $tabla_procesos = $wpdb->prefix . 'flavor_pp_procesos';
        $tabla_propuestas = $wpdb->prefix . 'flavor_pp_propuestas';
        $tabla_votos = $wpdb->prefix . 'flavor_pp_votos';
        $usuario_id = get_current_user_id();

        $proceso = $wpdb->get_row(
            "SELECT * FROM {$tabla_procesos} WHERE estado = 'votacion' ORDER BY fecha_inicio DESC LIMIT 1"
        );

        if (!$proceso) {
            return '<div class="flavor-empty-state"><p>' . __('No hay procesos en fase de votación.', 'flavor-chat-ia') . '</p></div>';
        }

        $votos_emitidos = (int) $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM {$tabla_votos} WHERE proceso_id = %d AND usuario_id = %d",
            $proceso->id,
            $usuario_id
        ));

        $votos_disponibles = max(0, ($proceso->votos_por_ciudadano ?? 3) - $votos_emitidos);

        $propuestas_votadas = $wpdb->get_col($wpdb->prepare(
            "SELECT propuesta_id FROM {$tabla_votos} WHERE proceso_id = %d AND usuario_id = %d",
            $proceso->id,
            $usuario_id
        ));

        $propuestas = $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM {$tabla_propuestas}
             WHERE proceso_id = %d AND estado = 'en_votacion'
             ORDER BY votos_total DESC",
            $proceso->id
        ));

        ob_start();
        ?>
        <div class="flavor-pp-votacion">
            <div class="votacion-header">
                <h2><?php echo esc_html($proceso->titulo); ?></h2>
                <div class="votos-disponibles">
                    <span class="votos-numero"><?php echo intval($votos_disponibles); ?></span>
                    <span class="votos-texto"><?php esc_html_e('votos disponibles', 'flavor-chat-ia'); ?></span>
                </div>
            </div>

            <?php if ($votos_disponibles <= 0): ?>
                <div class="flavor-notice flavor-notice-success">
                    <p><?php esc_html_e('¡Gracias! Ya has emitido todos tus votos.', 'flavor-chat-ia'); ?></p>
                </div>
            <?php endif; ?>

            <div class="propuestas-votacion">
                <?php foreach ($propuestas as $propuesta): ?>
                    <div class="propuesta-votar <?php echo in_array($propuesta->id, $propuestas_votadas) ? 'ya-votada' : ''; ?>">
                        <div class="propuesta-info">
                            <h3><?php echo esc_html($propuesta->titulo); ?></h3>
                            <p class="propuesta-presupuesto"><?php echo number_format_i18n($propuesta->presupuesto_estimado, 0); ?> €</p>
                            <p class="propuesta-votos-actual"><?php echo number_format_i18n($propuesta->votos_total); ?> <?php esc_html_e('votos', 'flavor-chat-ia'); ?></p>
                        </div>
                        <div class="propuesta-accion">
                            <?php if (in_array($propuesta->id, $propuestas_votadas)): ?>
                                <span class="ya-votado-badge">
                                    <span class="dashicons dashicons-yes-alt"></span>
                                    <?php esc_html_e('Votado', 'flavor-chat-ia'); ?>
                                </span>
                            <?php elseif ($votos_disponibles > 0): ?>
                                <button type="button" class="flavor-btn flavor-btn-primary btn-votar" data-propuesta="<?php echo esc_attr($propuesta->id); ?>">
                                    <span class="dashicons dashicons-yes-alt"></span>
                                    <?php esc_html_e('Votar', 'flavor-chat-ia'); ?>
                                </button>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
        <?php
        return ob_get_clean();
    }

    // ================================
    // AJAX HANDLERS
    // ================================

    /**
     * AJAX: Crear propuesta
     */
    public function ajax_crear_propuesta() {
        check_ajax_referer('pp_nonce', 'pp_nonce_field');

        if (!is_user_logged_in()) {
            wp_send_json_error(__('Debes iniciar sesión', 'flavor-chat-ia'));
        }

        $proceso_id = isset($_POST['proceso_id']) ? absint($_POST['proceso_id']) : 0;
        $titulo = isset($_POST['titulo']) ? sanitize_text_field($_POST['titulo']) : '';
        $descripcion = isset($_POST['descripcion']) ? wp_kses_post($_POST['descripcion']) : '';
        $presupuesto = isset($_POST['presupuesto_estimado']) ? floatval($_POST['presupuesto_estimado']) : 0;
        $categoria_id = isset($_POST['categoria_id']) ? absint($_POST['categoria_id']) : null;
        $es_borrador = isset($_POST['guardar_borrador']) && $_POST['guardar_borrador'] === '1';

        if (!$proceso_id || empty($titulo) || empty($descripcion) || $presupuesto <= 0) {
            wp_send_json_error(__('Todos los campos obligatorios deben completarse', 'flavor-chat-ia'));
        }

        global $wpdb;
        $tabla_propuestas = $wpdb->prefix . 'flavor_pp_propuestas';

        $slug = sanitize_title($titulo);
        $base_slug = $slug;
        $contador = 1;
        while ($wpdb->get_var($wpdb->prepare(
            "SELECT id FROM {$tabla_propuestas} WHERE proceso_id = %d AND slug = %s",
            $proceso_id, $slug
        ))) {
            $slug = $base_slug . '-' . $contador++;
        }

        $datos = [
            'proceso_id' => $proceso_id,
            'usuario_id' => get_current_user_id(),
            'titulo' => $titulo,
            'slug' => $slug,
            'descripcion' => $descripcion,
            'descripcion_corta' => isset($_POST['descripcion_corta']) ? sanitize_textarea_field($_POST['descripcion_corta']) : '',
            'justificacion' => isset($_POST['justificacion']) ? wp_kses_post($_POST['justificacion']) : '',
            'beneficiarios' => isset($_POST['beneficiarios']) ? sanitize_textarea_field($_POST['beneficiarios']) : '',
            'ubicacion' => isset($_POST['ubicacion']) ? sanitize_text_field($_POST['ubicacion']) : '',
            'categoria_id' => $categoria_id,
            'presupuesto_estimado' => $presupuesto,
            'estado' => $es_borrador ? 'borrador' : 'pendiente',
            'created_at' => current_time('mysql'),
        ];

        $resultado = $wpdb->insert($tabla_propuestas, $datos);

        if ($resultado) {
            $propuesta_id = $wpdb->insert_id;

            // Incrementar contador en proceso
            $tabla_procesos = $wpdb->prefix . 'flavor_pp_procesos';
            $wpdb->query($wpdb->prepare(
                "UPDATE {$tabla_procesos} SET total_propuestas = total_propuestas + 1 WHERE id = %d",
                $proceso_id
            ));

            do_action('pp_propuesta_created', $propuesta_id, get_current_user_id());

            wp_send_json_success([
                'mensaje' => $es_borrador
                    ? __('Propuesta guardada como borrador', 'flavor-chat-ia')
                    : __('Propuesta enviada correctamente', 'flavor-chat-ia'),
                'propuesta_id' => $propuesta_id,
            ]);
        } else {
            wp_send_json_error(__('Error al crear la propuesta', 'flavor-chat-ia'));
        }
    }

    /**
     * AJAX: Votar propuesta
     */
    public function ajax_votar_propuesta() {
        check_ajax_referer('pp_nonce', 'nonce');

        if (!is_user_logged_in()) {
            wp_send_json_error(__('Debes iniciar sesión', 'flavor-chat-ia'));
        }

        $propuesta_id = isset($_POST['propuesta_id']) ? absint($_POST['propuesta_id']) : 0;
        $usuario_id = get_current_user_id();

        if (!$propuesta_id) {
            wp_send_json_error(__('Propuesta no válida', 'flavor-chat-ia'));
        }

        global $wpdb;
        $tabla_propuestas = $wpdb->prefix . 'flavor_pp_propuestas';
        $tabla_procesos = $wpdb->prefix . 'flavor_pp_procesos';
        $tabla_votos = $wpdb->prefix . 'flavor_pp_votos';

        // Obtener propuesta y proceso
        $propuesta = $wpdb->get_row($wpdb->prepare(
            "SELECT proceso_id FROM {$tabla_propuestas} WHERE id = %d AND estado = 'en_votacion'",
            $propuesta_id
        ));

        if (!$propuesta) {
            wp_send_json_error(__('Esta propuesta no está en votación', 'flavor-chat-ia'));
        }

        $proceso = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$tabla_procesos} WHERE id = %d AND estado = 'votacion'",
            $propuesta->proceso_id
        ));

        if (!$proceso) {
            wp_send_json_error(__('El proceso no está en fase de votación', 'flavor-chat-ia'));
        }

        // Verificar votos disponibles
        $votos_emitidos = (int) $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM {$tabla_votos} WHERE proceso_id = %d AND usuario_id = %d",
            $proceso->id,
            $usuario_id
        ));

        $votos_max = $proceso->votos_por_ciudadano ?? 3;
        if ($votos_emitidos >= $votos_max) {
            wp_send_json_error(__('Ya has emitido todos tus votos', 'flavor-chat-ia'));
        }

        // Verificar si ya votó esta propuesta
        $ya_voto = $wpdb->get_var($wpdb->prepare(
            "SELECT id FROM {$tabla_votos} WHERE proceso_id = %d AND propuesta_id = %d AND usuario_id = %d",
            $proceso->id,
            $propuesta_id,
            $usuario_id
        ));

        if ($ya_voto) {
            wp_send_json_error(__('Ya has votado esta propuesta', 'flavor-chat-ia'));
        }

        // Registrar voto
        $resultado = $wpdb->insert($tabla_votos, [
            'proceso_id' => $proceso->id,
            'propuesta_id' => $propuesta_id,
            'usuario_id' => $usuario_id,
            'peso' => 1,
            'fecha_voto' => current_time('mysql'),
            'ip_address' => $_SERVER['REMOTE_ADDR'] ?? '',
        ]);

        if ($resultado) {
            // Actualizar contador de votos
            $wpdb->query($wpdb->prepare(
                "UPDATE {$tabla_propuestas} SET votos_total = votos_total + 1 WHERE id = %d",
                $propuesta_id
            ));

            // Actualizar votantes únicos en proceso
            $votantes_unicos = $wpdb->get_var($wpdb->prepare(
                "SELECT COUNT(DISTINCT usuario_id) FROM {$tabla_votos} WHERE proceso_id = %d",
                $proceso->id
            ));
            $wpdb->update($tabla_procesos, ['total_votantes' => $votantes_unicos], ['id' => $proceso->id]);

            $votos_restantes = $votos_max - $votos_emitidos - 1;

            wp_send_json_success([
                'mensaje' => __('¡Voto registrado!', 'flavor-chat-ia'),
                'votos_restantes' => $votos_restantes,
            ]);
        } else {
            wp_send_json_error(__('Error al registrar el voto', 'flavor-chat-ia'));
        }
    }

    /**
     * AJAX: Apoyar propuesta
     */
    public function ajax_apoyar_propuesta() {
        check_ajax_referer('pp_nonce', 'nonce');

        if (!is_user_logged_in()) {
            wp_send_json_error(__('Debes iniciar sesión', 'flavor-chat-ia'));
        }

        $propuesta_id = isset($_POST['propuesta_id']) ? absint($_POST['propuesta_id']) : 0;
        $usuario_id = get_current_user_id();

        if (!$propuesta_id) {
            wp_send_json_error(__('Propuesta no válida', 'flavor-chat-ia'));
        }

        global $wpdb;
        $tabla_apoyos = $wpdb->prefix . 'flavor_pp_apoyos';
        $tabla_propuestas = $wpdb->prefix . 'flavor_pp_propuestas';

        // Verificar si ya apoyó
        $ya_apoyo = $wpdb->get_var($wpdb->prepare(
            "SELECT id FROM {$tabla_apoyos} WHERE propuesta_id = %d AND usuario_id = %d",
            $propuesta_id,
            $usuario_id
        ));

        if ($ya_apoyo) {
            wp_send_json_error(__('Ya has apoyado esta propuesta', 'flavor-chat-ia'));
        }

        $resultado = $wpdb->insert($tabla_apoyos, [
            'propuesta_id' => $propuesta_id,
            'usuario_id' => $usuario_id,
            'created_at' => current_time('mysql'),
        ]);

        if ($resultado) {
            $wpdb->query($wpdb->prepare(
                "UPDATE {$tabla_propuestas} SET apoyos_count = apoyos_count + 1 WHERE id = %d",
                $propuesta_id
            ));

            $total_apoyos = $wpdb->get_var($wpdb->prepare(
                "SELECT apoyos_count FROM {$tabla_propuestas} WHERE id = %d",
                $propuesta_id
            ));

            wp_send_json_success([
                'mensaje' => __('¡Apoyo registrado!', 'flavor-chat-ia'),
                'total_apoyos' => intval($total_apoyos),
            ]);
        } else {
            wp_send_json_error(__('Error al registrar el apoyo', 'flavor-chat-ia'));
        }
    }

    /**
     * AJAX: Comentar
     */
    public function ajax_comentar() {
        check_ajax_referer('pp_nonce', 'pp_nonce_field');

        if (!is_user_logged_in()) {
            wp_send_json_error(__('Debes iniciar sesión', 'flavor-chat-ia'));
        }

        $propuesta_id = isset($_POST['propuesta_id']) ? absint($_POST['propuesta_id']) : 0;
        $contenido = isset($_POST['contenido']) ? sanitize_textarea_field($_POST['contenido']) : '';

        if (!$propuesta_id || empty($contenido)) {
            wp_send_json_error(__('Comentario no válido', 'flavor-chat-ia'));
        }

        global $wpdb;
        $tabla_comentarios = $wpdb->prefix . 'flavor_pp_comentarios';
        $tabla_propuestas = $wpdb->prefix . 'flavor_pp_propuestas';

        $resultado = $wpdb->insert($tabla_comentarios, [
            'propuesta_id' => $propuesta_id,
            'usuario_id' => get_current_user_id(),
            'contenido' => $contenido,
            'estado' => 'aprobado',
            'created_at' => current_time('mysql'),
        ]);

        if ($resultado) {
            $wpdb->query($wpdb->prepare(
                "UPDATE {$tabla_propuestas} SET comentarios_count = comentarios_count + 1 WHERE id = %d",
                $propuesta_id
            ));

            wp_send_json_success(['mensaje' => __('Comentario publicado', 'flavor-chat-ia')]);
        } else {
            wp_send_json_error(__('Error al publicar comentario', 'flavor-chat-ia'));
        }
    }

    /**
     * AJAX: Filtrar propuestas
     */
    public function ajax_filtrar_propuestas() {
        $proceso_id = isset($_POST['proceso_id']) ? absint($_POST['proceso_id']) : 0;
        $categoria = isset($_POST['categoria']) ? absint($_POST['categoria']) : 0;
        $orden = isset($_POST['orden']) ? sanitize_text_field($_POST['orden']) : 'recientes';
        $busqueda = isset($_POST['busqueda']) ? sanitize_text_field($_POST['busqueda']) : '';

        global $wpdb;
        $tabla_propuestas = $wpdb->prefix . 'flavor_pp_propuestas';
        $tabla_categorias = $wpdb->prefix . 'flavor_pp_categorias';
        $tabla_procesos = $wpdb->prefix . 'flavor_pp_procesos';

        $where = ["p.estado NOT IN ('borrador', 'rechazada')"];
        $params = [];

        if ($proceso_id) {
            $where[] = "p.proceso_id = %d";
            $params[] = $proceso_id;
        }

        if ($categoria) {
            $where[] = "p.categoria_id = %d";
            $params[] = $categoria;
        }

        if (!empty($busqueda)) {
            $where[] = "(p.titulo LIKE %s OR p.descripcion LIKE %s)";
            $like = '%' . $wpdb->esc_like($busqueda) . '%';
            $params[] = $like;
            $params[] = $like;
        }

        $orden_sql = match ($orden) {
            'votos' => 'p.votos_total DESC',
            'apoyos' => 'p.apoyos_count DESC',
            'presupuesto' => 'p.presupuesto_estimado DESC',
            default => 'p.created_at DESC',
        };

        $sql = "SELECT p.*, c.nombre as categoria_nombre, c.color as categoria_color, pr.slug as proceso_slug, pr.estado as proceso_estado
                FROM {$tabla_propuestas} p
                LEFT JOIN {$tabla_categorias} c ON p.categoria_id = c.id
                LEFT JOIN {$tabla_procesos} pr ON p.proceso_id = pr.id
                WHERE " . implode(' AND ', $where) . "
                ORDER BY {$orden_sql}
                LIMIT 50";

        $propuestas = empty($params)
            ? $wpdb->get_results($sql)
            : $wpdb->get_results($wpdb->prepare($sql, ...$params));

        ob_start();
        if (empty($propuestas)) {
            echo '<div class="flavor-empty-state"><p>' . __('No se encontraron propuestas.', 'flavor-chat-ia') . '</p></div>';
        } else {
            foreach ($propuestas as $propuesta) {
                $proceso = (object) ['estado' => $propuesta->proceso_estado, 'slug' => $propuesta->proceso_slug];
                $this->render_propuesta_card($propuesta, $proceso);
            }
        }
        $html = ob_get_clean();

        wp_send_json_success(['html' => $html, 'total' => count($propuestas)]);
    }

    /**
     * AJAX: Obtener procesos
     */
    public function ajax_obtener_procesos() {
        global $wpdb;
        $tabla_procesos = $wpdb->prefix . 'flavor_pp_procesos';

        $procesos = $wpdb->get_results(
            "SELECT id, titulo, slug, estado, anio, presupuesto_total
             FROM {$tabla_procesos}
             WHERE estado != 'borrador'
             ORDER BY anio DESC, fecha_inicio DESC"
        );

        wp_send_json_success(['procesos' => $procesos]);
    }
}

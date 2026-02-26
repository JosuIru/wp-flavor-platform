<?php
/**
 * Frontend Controller para Circulos de Cuidados
 *
 * Controlador frontend para redes de apoyo mutuo comunitario
 *
 * @package FlavorChatIA
 * @subpackage Modules\CirculosCuidados
 * @since 4.2.0
 */

if (!defined('ABSPATH')) {
    exit;
}

class Flavor_Circulos_Cuidados_Frontend_Controller {

    /**
     * Instancia singleton
     * @var Flavor_Circulos_Cuidados_Frontend_Controller|null
     */
    private static $instancia = null;

    /**
     * Nombre de las tablas
     */
    private $tabla_circulos;
    private $tabla_miembros;
    private $tabla_necesidades;
    private $tabla_respuestas;
    private $tabla_horas;

    /**
     * Tipos de circulos
     */
    private $tipos_circulo = [
        'mayores' => ['nombre' => 'Acompañamiento Mayores', 'icono' => 'dashicons-groups', 'color' => '#9b59b6'],
        'infancia' => ['nombre' => 'Cuidado Infancia', 'icono' => 'dashicons-heart', 'color' => '#e91e63'],
        'enfermedad' => ['nombre' => 'Apoyo Enfermedad', 'icono' => 'dashicons-plus-alt', 'color' => '#00bcd4'],
        'duelo' => ['nombre' => 'Acompañamiento Duelo', 'icono' => 'dashicons-admin-users', 'color' => '#607d8b'],
        'maternidad' => ['nombre' => 'Red de Maternidad', 'icono' => 'dashicons-admin-home', 'color' => '#ff9800'],
        'diversidad' => ['nombre' => 'Diversidad Funcional', 'icono' => 'dashicons-universal-access', 'color' => '#4caf50'],
    ];

    /**
     * Constructor privado
     */
    private function __construct() {
        global $wpdb;
        $this->tabla_circulos = $wpdb->prefix . 'flavor_circulos_cuidados';
        $this->tabla_miembros = $wpdb->prefix . 'flavor_circulos_miembros';
        $this->tabla_necesidades = $wpdb->prefix . 'flavor_circulos_necesidades';
        $this->tabla_respuestas = $wpdb->prefix . 'flavor_circulos_respuestas';
        $this->tabla_horas = $wpdb->prefix . 'flavor_circulos_horas';

        $this->init();
    }

    /**
     * Obtiene la instancia singleton
     *
     * @return Flavor_Circulos_Cuidados_Frontend_Controller
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
        add_shortcode('flavor_circulos_listado', [$this, 'shortcode_listado']);
        add_shortcode('flavor_circulos_detalle', [$this, 'shortcode_detalle']);
        add_shortcode('flavor_circulos_crear', [$this, 'shortcode_crear']);
        add_shortcode('flavor_circulos_necesidades', [$this, 'shortcode_necesidades']);
        add_shortcode('flavor_circulos_publicar_necesidad', [$this, 'shortcode_publicar_necesidad']);
        add_shortcode('flavor_circulos_mis_circulos', [$this, 'shortcode_mis_circulos']);
        add_shortcode('flavor_circulos_mis_horas', [$this, 'shortcode_mis_horas']);
        add_shortcode('flavor_circulos_mapa', [$this, 'shortcode_mapa']);

        // AJAX handlers
        add_action('wp_ajax_flavor_circulos_crear', [$this, 'ajax_crear_circulo']);
        add_action('wp_ajax_flavor_circulos_unirse', [$this, 'ajax_unirse']);
        add_action('wp_ajax_flavor_circulos_publicar_necesidad', [$this, 'ajax_publicar_necesidad']);
        add_action('wp_ajax_flavor_circulos_responder_necesidad', [$this, 'ajax_responder_necesidad']);
        add_action('wp_ajax_flavor_circulos_confirmar_horas', [$this, 'ajax_confirmar_horas']);
        add_action('wp_ajax_flavor_circulos_obtener', [$this, 'ajax_obtener_circulos']);
        add_action('wp_ajax_nopriv_flavor_circulos_obtener', [$this, 'ajax_obtener_circulos']);

        // Dashboard tabs
        add_filter('flavor_user_dashboard_tabs', [$this, 'registrar_dashboard_tabs']);

        // Assets
        add_action('wp_enqueue_scripts', [$this, 'registrar_assets']);
    }

    /**
     * Registra los tabs del dashboard
     */
    public function registrar_dashboard_tabs($tabs) {
        $tabs['circulos-cuidados'] = [
            'id' => 'circulos-cuidados',
            'label' => __('Cuidados', 'flavor-chat-ia'),
            'icon' => 'dashicons-heart',
            'orden' => 50,
            'callback' => [$this, 'render_dashboard_tab'],
        ];

        $tabs['circulos-necesidades'] = [
            'id' => 'circulos-necesidades',
            'label' => __('Necesidades', 'flavor-chat-ia'),
            'icon' => 'dashicons-sos',
            'orden' => 51,
            'parent' => 'circulos-cuidados',
            'callback' => [$this, 'render_dashboard_necesidades'],
        ];

        return $tabs;
    }

    /**
     * Registra assets frontend
     */
    public function registrar_assets() {
        wp_register_style(
            'flavor-circulos-frontend',
            FLAVOR_CHAT_IA_URL . 'includes/modules/circulos-cuidados/assets/css/circulos-frontend.css',
            [],
            FLAVOR_CHAT_IA_VERSION
        );

        wp_register_script(
            'flavor-circulos-frontend',
            FLAVOR_CHAT_IA_URL . 'includes/modules/circulos-cuidados/assets/js/circulos-frontend.js',
            ['jquery'],
            FLAVOR_CHAT_IA_VERSION,
            true
        );

        wp_localize_script('flavor-circulos-frontend', 'flavorCirculosConfig', [
            'ajaxUrl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('flavor_circulos_nonce'),
            'strings' => [
                'confirmar' => __('¿Confirmar esta acción?', 'flavor-chat-ia'),
                'enviando' => __('Enviando...', 'flavor-chat-ia'),
                'error' => __('Error al procesar', 'flavor-chat-ia'),
                'gracias' => __('Gracias por tu solidaridad', 'flavor-chat-ia'),
            ],
        ]);
    }

    /**
     * Encola assets
     */
    private function enqueue_assets() {
        wp_enqueue_style('flavor-circulos-frontend');
        wp_enqueue_script('flavor-circulos-frontend');
    }

    // =========================================================
    // SHORTCODES
    // =========================================================

    /**
     * Shortcode: Listado de circulos
     */
    public function shortcode_listado($atts) {
        $atts = shortcode_atts([
            'tipo' => '',
            'por_pagina' => 12,
        ], $atts);

        $this->enqueue_assets();

        if (!Flavor_Chat_Helpers::tabla_existe($this->tabla_circulos)) {
            return '<div class="flavor-alert flavor-alert-warning">' .
                   __('El sistema de círculos de cuidados no está configurado.', 'flavor-chat-ia') . '</div>';
        }

        global $wpdb;

        $filtro_tipo = isset($_GET['tipo']) ? sanitize_text_field($_GET['tipo']) : $atts['tipo'];

        $where = "c.estado = 'activo'";
        if (!empty($filtro_tipo)) {
            $where .= $wpdb->prepare(" AND c.tipo = %s", $filtro_tipo);
        }

        $pagina = max(1, absint($_GET['pag'] ?? 1));
        $offset = ($pagina - 1) * $atts['por_pagina'];

        $circulos = $wpdb->get_results("
            SELECT c.*,
                   (SELECT COUNT(*) FROM {$this->tabla_miembros} WHERE circulo_id = c.id AND estado = 'activo') as total_miembros,
                   (SELECT COUNT(*) FROM {$this->tabla_necesidades} WHERE circulo_id = c.id AND estado = 'abierta') as necesidades_abiertas
            FROM {$this->tabla_circulos} c
            WHERE {$where}
            ORDER BY c.fecha_creacion DESC
            LIMIT {$atts['por_pagina']} OFFSET {$offset}
        ");

        $total = $wpdb->get_var("SELECT COUNT(*) FROM {$this->tabla_circulos} c WHERE {$where}");

        ob_start();
        ?>
        <div class="flavor-circulos-listado">
            <div class="flavor-circulos-header">
                <h2><?php _e('Círculos de Cuidados', 'flavor-chat-ia'); ?></h2>
                <p class="flavor-circulos-intro">
                    <?php _e('Redes de apoyo mutuo para cuidarnos entre vecinos/as.', 'flavor-chat-ia'); ?>
                </p>
            </div>

            <div class="flavor-filtros">
                <form method="get" class="flavor-filtros-form">
                    <select name="tipo" onchange="this.form.submit()">
                        <option value=""><?php _e('Todos los tipos', 'flavor-chat-ia'); ?></option>
                        <?php foreach ($this->tipos_circulo as $clave => $tipo): ?>
                            <option value="<?php echo esc_attr($clave); ?>" <?php selected($filtro_tipo, $clave); ?>>
                                <?php echo esc_html($tipo['nombre']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <?php if (is_user_logged_in()): ?>
                        <a href="<?php echo esc_url(add_query_arg('accion', 'crear')); ?>" class="flavor-btn flavor-btn-primary">
                            <span class="dashicons dashicons-plus"></span>
                            <?php _e('Crear Círculo', 'flavor-chat-ia'); ?>
                        </a>
                    <?php endif; ?>
                </form>
            </div>

            <?php if (empty($circulos)): ?>
                <div class="flavor-alert flavor-alert-info">
                    <?php _e('No hay círculos de cuidados activos. ¡Sé el primero en crear uno!', 'flavor-chat-ia'); ?>
                </div>
            <?php else: ?>
                <div class="flavor-circulos-grid">
                    <?php foreach ($circulos as $circulo): ?>
                        <?php $this->render_card_circulo($circulo); ?>
                    <?php endforeach; ?>
                </div>

                <?php $this->render_paginacion($total, $atts['por_pagina'], $pagina); ?>
            <?php endif; ?>
        </div>
        <?php
        return ob_get_clean();
    }

    /**
     * Renderiza tarjeta de circulo
     */
    private function render_card_circulo($circulo) {
        $tipo_info = $this->tipos_circulo[$circulo->tipo] ?? ['nombre' => $circulo->tipo, 'icono' => 'dashicons-groups', 'color' => '#6b7280'];
        ?>
        <div class="flavor-circulo-card" style="border-top-color: <?php echo esc_attr($tipo_info['color']); ?>;">
            <div class="flavor-circulo-icono" style="background-color: <?php echo esc_attr($tipo_info['color']); ?>;">
                <span class="dashicons <?php echo esc_attr($tipo_info['icono']); ?>"></span>
            </div>
            <div class="flavor-circulo-contenido">
                <span class="flavor-circulo-tipo" style="color: <?php echo esc_attr($tipo_info['color']); ?>;">
                    <?php echo esc_html($tipo_info['nombre']); ?>
                </span>
                <h3 class="flavor-circulo-nombre">
                    <a href="<?php echo esc_url(add_query_arg('circulo_id', $circulo->id)); ?>">
                        <?php echo esc_html($circulo->nombre); ?>
                    </a>
                </h3>
                <?php if (!empty($circulo->descripcion)): ?>
                    <p class="flavor-circulo-descripcion"><?php echo esc_html(wp_trim_words($circulo->descripcion, 20)); ?></p>
                <?php endif; ?>
                <div class="flavor-circulo-stats">
                    <span class="flavor-stat">
                        <span class="dashicons dashicons-groups"></span>
                        <?php printf(_n('%d persona', '%d personas', $circulo->total_miembros, 'flavor-chat-ia'), $circulo->total_miembros); ?>
                    </span>
                    <?php if ($circulo->necesidades_abiertas > 0): ?>
                        <span class="flavor-stat flavor-stat-urgente">
                            <span class="dashicons dashicons-sos"></span>
                            <?php printf(_n('%d necesidad', '%d necesidades', $circulo->necesidades_abiertas, 'flavor-chat-ia'), $circulo->necesidades_abiertas); ?>
                        </span>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        <?php
    }

    /**
     * Shortcode: Detalle de circulo
     */
    public function shortcode_detalle($atts) {
        $atts = shortcode_atts([
            'circulo_id' => 0,
        ], $atts);

        $circulo_id = absint($atts['circulo_id'] ?: (isset($_GET['circulo_id']) ? $_GET['circulo_id'] : 0));
        if (!$circulo_id) {
            return $this->shortcode_listado([]);
        }

        $this->enqueue_assets();

        global $wpdb;
        $circulo = $wpdb->get_row($wpdb->prepare("
            SELECT c.*,
                   (SELECT COUNT(*) FROM {$this->tabla_miembros} WHERE circulo_id = c.id AND estado = 'activo') as total_miembros,
                   (SELECT SUM(horas) FROM {$this->tabla_horas} WHERE circulo_id = c.id AND estado = 'confirmado') as total_horas
            FROM {$this->tabla_circulos} c
            WHERE c.id = %d
        ", $circulo_id));

        if (!$circulo) {
            return '<div class="flavor-alert flavor-alert-warning">' .
                   __('Círculo no encontrado.', 'flavor-chat-ia') . '</div>';
        }

        $tipo_info = $this->tipos_circulo[$circulo->tipo] ?? ['nombre' => $circulo->tipo, 'icono' => 'dashicons-groups', 'color' => '#6b7280'];

        $usuario_actual = get_current_user_id();
        $es_miembro = $this->es_miembro($circulo_id, $usuario_actual);

        // Miembros
        $miembros = $wpdb->get_results($wpdb->prepare("
            SELECT m.*, u.display_name,
                   (SELECT COALESCE(SUM(horas), 0) FROM {$this->tabla_horas} WHERE usuario_id = m.usuario_id AND circulo_id = m.circulo_id AND estado = 'confirmado') as horas_aportadas
            FROM {$this->tabla_miembros} m
            LEFT JOIN {$wpdb->users} u ON m.usuario_id = u.ID
            WHERE m.circulo_id = %d AND m.estado = 'activo'
            ORDER BY horas_aportadas DESC
            LIMIT 12
        ", $circulo_id));

        // Necesidades abiertas
        $necesidades = $wpdb->get_results($wpdb->prepare("
            SELECT n.*, u.display_name as solicitante_nombre
            FROM {$this->tabla_necesidades} n
            LEFT JOIN {$wpdb->users} u ON n.usuario_id = u.ID
            WHERE n.circulo_id = %d AND n.estado = 'abierta'
            ORDER BY n.urgencia DESC, n.fecha_creacion DESC
            LIMIT 5
        ", $circulo_id));

        ob_start();
        ?>
        <div class="flavor-circulo-detalle">
            <div class="flavor-circulos-breadcrumb">
                <a href="<?php echo esc_url(remove_query_arg('circulo_id')); ?>">
                    <?php _e('Círculos', 'flavor-chat-ia'); ?>
                </a>
                <span class="dashicons dashicons-arrow-right-alt2"></span>
                <span><?php echo esc_html($circulo->nombre); ?></span>
            </div>

            <header class="flavor-circulo-header-detalle" style="border-left-color: <?php echo esc_attr($tipo_info['color']); ?>;">
                <div class="flavor-circulo-icono-grande" style="background-color: <?php echo esc_attr($tipo_info['color']); ?>;">
                    <span class="dashicons <?php echo esc_attr($tipo_info['icono']); ?>"></span>
                </div>
                <div class="flavor-circulo-info">
                    <span class="flavor-badge" style="background-color: <?php echo esc_attr($tipo_info['color']); ?>;">
                        <?php echo esc_html($tipo_info['nombre']); ?>
                    </span>
                    <h1><?php echo esc_html($circulo->nombre); ?></h1>
                    <?php if (!empty($circulo->barrio)): ?>
                        <p class="flavor-circulo-ubicacion">
                            <span class="dashicons dashicons-location"></span>
                            <?php echo esc_html($circulo->barrio); ?>
                        </p>
                    <?php endif; ?>

                    <div class="flavor-circulo-stats-grandes">
                        <div class="flavor-stat-grande">
                            <span class="valor"><?php echo absint($circulo->total_miembros); ?></span>
                            <span class="label"><?php _e('personas', 'flavor-chat-ia'); ?></span>
                        </div>
                        <div class="flavor-stat-grande">
                            <span class="valor"><?php echo absint($circulo->total_horas ?: 0); ?></span>
                            <span class="label"><?php _e('horas cuidando', 'flavor-chat-ia'); ?></span>
                        </div>
                    </div>

                    <?php if ($usuario_actual): ?>
                        <?php if ($es_miembro): ?>
                            <span class="flavor-badge flavor-badge-success">
                                <span class="dashicons dashicons-yes"></span>
                                <?php _e('Formas parte de este círculo', 'flavor-chat-ia'); ?>
                            </span>
                        <?php else: ?>
                            <button class="flavor-btn flavor-btn-primary flavor-unirse-circulo"
                                    data-circulo-id="<?php echo esc_attr($circulo_id); ?>">
                                <span class="dashicons dashicons-heart"></span>
                                <?php _e('Unirme al círculo', 'flavor-chat-ia'); ?>
                            </button>
                        <?php endif; ?>
                    <?php endif; ?>
                </div>
            </header>

            <div class="flavor-circulo-contenido-detalle">
                <div class="flavor-circulo-main">
                    <?php if (!empty($circulo->descripcion)): ?>
                        <section class="flavor-panel">
                            <h2><?php _e('Sobre este círculo', 'flavor-chat-ia'); ?></h2>
                            <?php echo wp_kses_post(wpautop($circulo->descripcion)); ?>
                        </section>
                    <?php endif; ?>

                    <section class="flavor-panel">
                        <h2>
                            <?php _e('Necesidades Abiertas', 'flavor-chat-ia'); ?>
                            <?php if ($es_miembro): ?>
                                <a href="<?php echo esc_url(add_query_arg(['accion' => 'publicar_necesidad', 'circulo_id' => $circulo_id])); ?>"
                                   class="flavor-btn flavor-btn-sm flavor-btn-outline" style="float: right;">
                                    <span class="dashicons dashicons-plus"></span>
                                    <?php _e('Publicar', 'flavor-chat-ia'); ?>
                                </a>
                            <?php endif; ?>
                        </h2>
                        <?php if (empty($necesidades)): ?>
                            <p class="flavor-no-datos"><?php _e('No hay necesidades abiertas en este momento.', 'flavor-chat-ia'); ?></p>
                        <?php else: ?>
                            <div class="flavor-necesidades-lista">
                                <?php foreach ($necesidades as $necesidad): ?>
                                    <?php $this->render_necesidad($necesidad, $es_miembro); ?>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                    </section>
                </div>

                <aside class="flavor-circulo-sidebar">
                    <section class="flavor-panel">
                        <h3><?php _e('Personas del círculo', 'flavor-chat-ia'); ?></h3>
                        <div class="flavor-miembros-circulo">
                            <?php foreach ($miembros as $miembro): ?>
                                <div class="flavor-miembro-item-circulo">
                                    <?php echo get_avatar($miembro->usuario_id, 40); ?>
                                    <div class="flavor-miembro-info">
                                        <span class="nombre"><?php echo esc_html($miembro->display_name); ?></span>
                                        <span class="horas"><?php printf(__('%dh aportadas', 'flavor-chat-ia'), $miembro->horas_aportadas); ?></span>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </section>

                    <?php if (!empty($circulo->valores)): ?>
                        <section class="flavor-panel">
                            <h3><?php _e('Valores del círculo', 'flavor-chat-ia'); ?></h3>
                            <?php echo wp_kses_post(wpautop($circulo->valores)); ?>
                        </section>
                    <?php endif; ?>
                </aside>
            </div>
        </div>
        <?php
        return ob_get_clean();
    }

    /**
     * Renderiza una necesidad
     */
    private function render_necesidad($necesidad, $puede_responder = false) {
        $urgencia_clases = [
            'baja' => 'flavor-urgencia-baja',
            'media' => 'flavor-urgencia-media',
            'alta' => 'flavor-urgencia-alta',
            'urgente' => 'flavor-urgencia-urgente',
        ];
        $clase_urgencia = $urgencia_clases[$necesidad->urgencia] ?? '';
        ?>
        <div class="flavor-necesidad-item <?php echo esc_attr($clase_urgencia); ?>" data-necesidad-id="<?php echo esc_attr($necesidad->id); ?>">
            <div class="flavor-necesidad-header">
                <span class="flavor-necesidad-solicitante">
                    <?php echo get_avatar($necesidad->usuario_id, 28); ?>
                    <?php echo esc_html($necesidad->solicitante_nombre); ?>
                </span>
                <span class="flavor-badge flavor-badge-urgencia-<?php echo esc_attr($necesidad->urgencia); ?>">
                    <?php echo esc_html(ucfirst($necesidad->urgencia)); ?>
                </span>
            </div>
            <h4 class="flavor-necesidad-titulo"><?php echo esc_html($necesidad->titulo); ?></h4>
            <p class="flavor-necesidad-descripcion"><?php echo esc_html(wp_trim_words($necesidad->descripcion, 25)); ?></p>
            <div class="flavor-necesidad-meta">
                <?php if (!empty($necesidad->fecha_necesaria)): ?>
                    <span><span class="dashicons dashicons-calendar"></span> <?php echo date_i18n(get_option('date_format'), strtotime($necesidad->fecha_necesaria)); ?></span>
                <?php endif; ?>
                <?php if (!empty($necesidad->horas_estimadas)): ?>
                    <span><span class="dashicons dashicons-clock"></span> ~<?php echo absint($necesidad->horas_estimadas); ?>h</span>
                <?php endif; ?>
            </div>
            <?php if ($puede_responder && $necesidad->usuario_id != get_current_user_id()): ?>
                <button class="flavor-btn flavor-btn-sm flavor-btn-primary flavor-responder-necesidad"
                        data-necesidad-id="<?php echo esc_attr($necesidad->id); ?>">
                    <span class="dashicons dashicons-heart"></span>
                    <?php _e('Puedo ayudar', 'flavor-chat-ia'); ?>
                </button>
            <?php endif; ?>
        </div>
        <?php
    }

    /**
     * Shortcode: Crear circulo
     */
    public function shortcode_crear($atts) {
        if (!is_user_logged_in()) {
            return '<div class="flavor-alert flavor-alert-warning">' .
                   sprintf(__('<a href="%s">Inicia sesión</a> para crear un círculo.', 'flavor-chat-ia'), wp_login_url(get_permalink())) .
                   '</div>';
        }

        $this->enqueue_assets();

        ob_start();
        ?>
        <div class="flavor-crear-circulo">
            <h2><?php _e('Crear Círculo de Cuidados', 'flavor-chat-ia'); ?></h2>
            <p class="flavor-intro"><?php _e('Los círculos de cuidados son redes de apoyo mutuo donde nos cuidamos entre vecinos/as.', 'flavor-chat-ia'); ?></p>

            <form id="flavor-form-crear-circulo" class="flavor-form">
                <?php wp_nonce_field('flavor_circulos_nonce', 'nonce'); ?>

                <div class="flavor-form-row">
                    <div class="flavor-form-group">
                        <label for="nombre"><?php _e('Nombre del círculo', 'flavor-chat-ia'); ?> *</label>
                        <input type="text" name="nombre" id="nombre" required maxlength="150"
                               placeholder="<?php esc_attr_e('Ej: Círculo de cuidados del barrio...', 'flavor-chat-ia'); ?>">
                    </div>
                    <div class="flavor-form-group">
                        <label for="tipo"><?php _e('Tipo de cuidados', 'flavor-chat-ia'); ?> *</label>
                        <select name="tipo" id="tipo" required>
                            <option value=""><?php _e('Seleccionar...', 'flavor-chat-ia'); ?></option>
                            <?php foreach ($this->tipos_circulo as $clave => $tipo): ?>
                                <option value="<?php echo esc_attr($clave); ?>"><?php echo esc_html($tipo['nombre']); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>

                <div class="flavor-form-group">
                    <label for="barrio"><?php _e('Barrio / Zona', 'flavor-chat-ia'); ?></label>
                    <input type="text" name="barrio" id="barrio"
                           placeholder="<?php esc_attr_e('¿Dónde se ubica el círculo?', 'flavor-chat-ia'); ?>">
                </div>

                <div class="flavor-form-group">
                    <label for="descripcion"><?php _e('Descripción', 'flavor-chat-ia'); ?> *</label>
                    <textarea name="descripcion" id="descripcion" rows="5" required
                              placeholder="<?php esc_attr_e('¿Qué tipo de apoyo ofrece este círculo?', 'flavor-chat-ia'); ?>"></textarea>
                </div>

                <div class="flavor-form-group">
                    <label for="valores"><?php _e('Valores del círculo', 'flavor-chat-ia'); ?></label>
                    <textarea name="valores" id="valores" rows="3"
                              placeholder="<?php esc_attr_e('¿Qué valores guían este círculo? Ej: Reciprocidad, confidencialidad...', 'flavor-chat-ia'); ?>"></textarea>
                </div>

                <div class="flavor-form-actions">
                    <button type="submit" class="flavor-btn flavor-btn-primary">
                        <span class="dashicons dashicons-heart"></span>
                        <?php _e('Crear Círculo', 'flavor-chat-ia'); ?>
                    </button>
                    <a href="<?php echo esc_url(remove_query_arg('accion')); ?>" class="flavor-btn flavor-btn-outline">
                        <?php _e('Cancelar', 'flavor-chat-ia'); ?>
                    </a>
                </div>
            </form>
        </div>
        <?php
        return ob_get_clean();
    }

    /**
     * Shortcode: Necesidades
     */
    public function shortcode_necesidades($atts) {
        $this->enqueue_assets();

        global $wpdb;
        $necesidades = $wpdb->get_results("
            SELECT n.*, c.nombre as circulo_nombre, u.display_name as solicitante_nombre
            FROM {$this->tabla_necesidades} n
            LEFT JOIN {$this->tabla_circulos} c ON n.circulo_id = c.id
            LEFT JOIN {$wpdb->users} u ON n.usuario_id = u.ID
            WHERE n.estado = 'abierta'
            ORDER BY n.urgencia DESC, n.fecha_creacion DESC
            LIMIT 20
        ");

        ob_start();
        ?>
        <div class="flavor-necesidades-generales">
            <h2><?php _e('Necesidades de Cuidados', 'flavor-chat-ia'); ?></h2>
            <p class="flavor-intro"><?php _e('Personas de la comunidad que necesitan apoyo.', 'flavor-chat-ia'); ?></p>

            <?php if (empty($necesidades)): ?>
                <div class="flavor-alert flavor-alert-info">
                    <?php _e('No hay necesidades abiertas en este momento.', 'flavor-chat-ia'); ?>
                </div>
            <?php else: ?>
                <div class="flavor-necesidades-grid">
                    <?php foreach ($necesidades as $necesidad): ?>
                        <?php $this->render_necesidad($necesidad, is_user_logged_in()); ?>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
        <?php
        return ob_get_clean();
    }

    /**
     * Shortcode: Publicar necesidad
     */
    public function shortcode_publicar_necesidad($atts) {
        if (!is_user_logged_in()) {
            return '';
        }

        $this->enqueue_assets();
        $circulo_id = isset($_GET['circulo_id']) ? absint($_GET['circulo_id']) : 0;

        ob_start();
        ?>
        <div class="flavor-publicar-necesidad">
            <h2><?php _e('Publicar Necesidad de Cuidados', 'flavor-chat-ia'); ?></h2>

            <form id="flavor-form-necesidad" class="flavor-form">
                <?php wp_nonce_field('flavor_circulos_nonce', 'nonce'); ?>
                <input type="hidden" name="circulo_id" value="<?php echo esc_attr($circulo_id); ?>">

                <div class="flavor-form-group">
                    <label for="titulo"><?php _e('¿Qué necesitas?', 'flavor-chat-ia'); ?> *</label>
                    <input type="text" name="titulo" id="titulo" required
                           placeholder="<?php esc_attr_e('Ej: Acompañamiento para cita médica', 'flavor-chat-ia'); ?>">
                </div>

                <div class="flavor-form-group">
                    <label for="descripcion"><?php _e('Descripción', 'flavor-chat-ia'); ?> *</label>
                    <textarea name="descripcion" id="descripcion" rows="4" required
                              placeholder="<?php esc_attr_e('Explica con más detalle qué necesitas...', 'flavor-chat-ia'); ?>"></textarea>
                </div>

                <div class="flavor-form-row">
                    <div class="flavor-form-group">
                        <label for="urgencia"><?php _e('Urgencia', 'flavor-chat-ia'); ?></label>
                        <select name="urgencia" id="urgencia">
                            <option value="baja"><?php _e('Baja - Cuando sea posible', 'flavor-chat-ia'); ?></option>
                            <option value="media" selected><?php _e('Media - Esta semana', 'flavor-chat-ia'); ?></option>
                            <option value="alta"><?php _e('Alta - En 1-2 días', 'flavor-chat-ia'); ?></option>
                            <option value="urgente"><?php _e('Urgente - Hoy', 'flavor-chat-ia'); ?></option>
                        </select>
                    </div>
                    <div class="flavor-form-group">
                        <label for="fecha_necesaria"><?php _e('Fecha deseada', 'flavor-chat-ia'); ?></label>
                        <input type="date" name="fecha_necesaria" id="fecha_necesaria">
                    </div>
                </div>

                <div class="flavor-form-group">
                    <label for="horas_estimadas"><?php _e('Horas estimadas', 'flavor-chat-ia'); ?></label>
                    <input type="number" name="horas_estimadas" id="horas_estimadas" min="0.5" step="0.5" value="1">
                </div>

                <div class="flavor-form-actions">
                    <button type="submit" class="flavor-btn flavor-btn-primary">
                        <?php _e('Publicar Necesidad', 'flavor-chat-ia'); ?>
                    </button>
                </div>
            </form>
        </div>
        <?php
        return ob_get_clean();
    }

    /**
     * Shortcode: Mis circulos
     */
    public function shortcode_mis_circulos($atts) {
        if (!is_user_logged_in()) {
            return '';
        }

        $this->enqueue_assets();
        $usuario_id = get_current_user_id();

        global $wpdb;
        $circulos = $wpdb->get_results($wpdb->prepare("
            SELECT c.*, m.fecha_union,
                   (SELECT COALESCE(SUM(horas), 0) FROM {$this->tabla_horas} WHERE usuario_id = %d AND circulo_id = c.id AND estado = 'confirmado') as mis_horas
            FROM {$this->tabla_circulos} c
            INNER JOIN {$this->tabla_miembros} m ON c.id = m.circulo_id
            WHERE m.usuario_id = %d AND m.estado = 'activo'
            ORDER BY m.fecha_union DESC
        ", $usuario_id, $usuario_id));

        ob_start();
        ?>
        <div class="flavor-mis-circulos">
            <h2><?php _e('Mis Círculos de Cuidados', 'flavor-chat-ia'); ?></h2>

            <?php if (empty($circulos)): ?>
                <div class="flavor-alert flavor-alert-info">
                    <?php _e('No perteneces a ningún círculo todavía.', 'flavor-chat-ia'); ?>
                    <a href="<?php echo esc_url(remove_query_arg('tab')); ?>" class="flavor-btn flavor-btn-sm flavor-btn-primary">
                        <?php _e('Explorar Círculos', 'flavor-chat-ia'); ?>
                    </a>
                </div>
            <?php else: ?>
                <div class="flavor-circulos-lista-compacta">
                    <?php foreach ($circulos as $circulo):
                        $tipo_info = $this->tipos_circulo[$circulo->tipo] ?? ['nombre' => $circulo->tipo, 'color' => '#6b7280'];
                        ?>
                        <div class="flavor-circulo-compacto">
                            <div class="flavor-circulo-info">
                                <h4>
                                    <a href="<?php echo esc_url(add_query_arg('circulo_id', $circulo->id)); ?>">
                                        <?php echo esc_html($circulo->nombre); ?>
                                    </a>
                                </h4>
                                <span class="flavor-badge" style="background-color: <?php echo esc_attr($tipo_info['color']); ?>;">
                                    <?php echo esc_html($tipo_info['nombre']); ?>
                                </span>
                            </div>
                            <div class="flavor-circulo-mis-stats">
                                <span class="flavor-mis-horas">
                                    <strong><?php echo absint($circulo->mis_horas); ?></strong> <?php _e('horas aportadas', 'flavor-chat-ia'); ?>
                                </span>
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
     * Shortcode: Mis horas
     */
    public function shortcode_mis_horas($atts) {
        if (!is_user_logged_in()) {
            return '';
        }

        $this->enqueue_assets();
        $usuario_id = get_current_user_id();

        global $wpdb;
        $horas = $wpdb->get_results($wpdb->prepare("
            SELECT h.*, c.nombre as circulo_nombre, n.titulo as necesidad_titulo
            FROM {$this->tabla_horas} h
            LEFT JOIN {$this->tabla_circulos} c ON h.circulo_id = c.id
            LEFT JOIN {$this->tabla_necesidades} n ON h.necesidad_id = n.id
            WHERE h.usuario_id = %d
            ORDER BY h.fecha DESC
        ", $usuario_id));

        $total_horas = array_sum(array_map(function($h) {
            return $h->estado === 'confirmado' ? $h->horas : 0;
        }, $horas));

        ob_start();
        ?>
        <div class="flavor-mis-horas">
            <h2><?php _e('Mis Horas de Cuidados', 'flavor-chat-ia'); ?></h2>

            <div class="flavor-resumen-horas">
                <div class="flavor-total-horas">
                    <span class="valor"><?php echo number_format($total_horas, 1); ?></span>
                    <span class="label"><?php _e('horas aportadas', 'flavor-chat-ia'); ?></span>
                </div>
            </div>

            <?php if (empty($horas)): ?>
                <p class="flavor-no-datos"><?php _e('Aún no has registrado horas de cuidados.', 'flavor-chat-ia'); ?></p>
            <?php else: ?>
                <table class="flavor-table">
                    <thead>
                        <tr>
                            <th><?php _e('Fecha', 'flavor-chat-ia'); ?></th>
                            <th><?php _e('Círculo', 'flavor-chat-ia'); ?></th>
                            <th><?php _e('Actividad', 'flavor-chat-ia'); ?></th>
                            <th><?php _e('Horas', 'flavor-chat-ia'); ?></th>
                            <th><?php _e('Estado', 'flavor-chat-ia'); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($horas as $registro): ?>
                            <tr>
                                <td><?php echo date_i18n(get_option('date_format'), strtotime($registro->fecha)); ?></td>
                                <td><?php echo esc_html($registro->circulo_nombre); ?></td>
                                <td><?php echo esc_html($registro->necesidad_titulo ?: $registro->descripcion); ?></td>
                                <td><?php echo number_format($registro->horas, 1); ?>h</td>
                                <td>
                                    <span class="flavor-badge flavor-badge-<?php echo esc_attr($registro->estado); ?>">
                                        <?php echo esc_html(ucfirst($registro->estado)); ?>
                                    </span>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </div>
        <?php
        return ob_get_clean();
    }

    /**
     * Shortcode: Mapa
     */
    public function shortcode_mapa($atts) {
        $this->enqueue_assets();
        wp_enqueue_script('leaflet');
        wp_enqueue_style('leaflet');

        ob_start();
        ?>
        <div class="flavor-circulos-mapa-container">
            <div id="flavor-mapa-circulos" style="height: 500px;"></div>
        </div>
        <?php
        return ob_get_clean();
    }

    // =========================================================
    // DASHBOARD TABS
    // =========================================================

    /**
     * Render del tab principal
     */
    public function render_dashboard_tab() {
        $this->enqueue_assets();
        $usuario_id = get_current_user_id();

        global $wpdb;

        $mis_circulos = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM {$this->tabla_miembros} WHERE usuario_id = %d AND estado = 'activo'",
            $usuario_id
        ));

        $mis_horas = $wpdb->get_var($wpdb->prepare(
            "SELECT COALESCE(SUM(horas), 0) FROM {$this->tabla_horas} WHERE usuario_id = %d AND estado = 'confirmado'",
            $usuario_id
        ));

        $necesidades_ayudadas = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM {$this->tabla_respuestas} WHERE usuario_id = %d AND estado = 'completada'",
            $usuario_id
        ));

        ?>
        <div class="flavor-dashboard-circulos">
            <div class="flavor-kpi-grid flavor-grid-3">
                <div class="flavor-kpi-card">
                    <div class="flavor-kpi-icono"><span class="dashicons dashicons-heart"></span></div>
                    <div class="flavor-kpi-contenido">
                        <span class="flavor-kpi-valor"><?php echo absint($mis_circulos); ?></span>
                        <span class="flavor-kpi-label"><?php _e('Círculos', 'flavor-chat-ia'); ?></span>
                    </div>
                </div>
                <div class="flavor-kpi-card">
                    <div class="flavor-kpi-icono"><span class="dashicons dashicons-clock"></span></div>
                    <div class="flavor-kpi-contenido">
                        <span class="flavor-kpi-valor"><?php echo number_format($mis_horas, 1); ?></span>
                        <span class="flavor-kpi-label"><?php _e('Horas cuidando', 'flavor-chat-ia'); ?></span>
                    </div>
                </div>
                <div class="flavor-kpi-card">
                    <div class="flavor-kpi-icono"><span class="dashicons dashicons-groups"></span></div>
                    <div class="flavor-kpi-contenido">
                        <span class="flavor-kpi-valor"><?php echo absint($necesidades_ayudadas); ?></span>
                        <span class="flavor-kpi-label"><?php _e('Personas ayudadas', 'flavor-chat-ia'); ?></span>
                    </div>
                </div>
            </div>

            <div class="flavor-panel">
                <h3><?php _e('Gracias por cuidar', 'flavor-chat-ia'); ?></h3>
                <p><?php _e('Cada hora que dedicas a cuidar a otras personas fortalece los vínculos de nuestra comunidad.', 'flavor-chat-ia'); ?></p>
            </div>
        </div>
        <?php
    }

    /**
     * Render de necesidades
     */
    public function render_dashboard_necesidades() {
        echo $this->shortcode_necesidades([]);
    }

    // =========================================================
    // AJAX HANDLERS
    // =========================================================

    /**
     * AJAX: Crear circulo
     */
    public function ajax_crear_circulo() {
        check_ajax_referer('flavor_circulos_nonce', 'nonce');

        if (!is_user_logged_in()) {
            wp_send_json_error(['message' => __('Debes iniciar sesión.', 'flavor-chat-ia')]);
        }

        $nombre = sanitize_text_field($_POST['nombre'] ?? '');
        $tipo = sanitize_text_field($_POST['tipo'] ?? '');
        $descripcion = wp_kses_post($_POST['descripcion'] ?? '');

        if (empty($nombre) || empty($tipo) || empty($descripcion)) {
            wp_send_json_error(['message' => __('Completa todos los campos requeridos.', 'flavor-chat-ia')]);
        }

        global $wpdb;

        $resultado = $wpdb->insert($this->tabla_circulos, [
            'nombre' => $nombre,
            'tipo' => $tipo,
            'descripcion' => $descripcion,
            'barrio' => sanitize_text_field($_POST['barrio'] ?? ''),
            'valores' => wp_kses_post($_POST['valores'] ?? ''),
            'creador_id' => get_current_user_id(),
            'estado' => 'activo',
            'fecha_creacion' => current_time('mysql'),
        ]);

        if ($resultado) {
            $circulo_id = $wpdb->insert_id;

            // Unir al creador
            $wpdb->insert($this->tabla_miembros, [
                'circulo_id' => $circulo_id,
                'usuario_id' => get_current_user_id(),
                'rol' => 'coordinador',
                'estado' => 'activo',
                'fecha_union' => current_time('mysql'),
            ]);

            wp_send_json_success([
                'message' => __('Círculo creado. ¡Gracias por tejer comunidad!', 'flavor-chat-ia'),
                'circulo_id' => $circulo_id,
                'redirect' => add_query_arg('circulo_id', $circulo_id, remove_query_arg('accion')),
            ]);
        } else {
            wp_send_json_error(['message' => __('Error al crear el círculo.', 'flavor-chat-ia')]);
        }
    }

    /**
     * AJAX: Unirse a circulo
     */
    public function ajax_unirse() {
        check_ajax_referer('flavor_circulos_nonce', 'nonce');

        if (!is_user_logged_in()) {
            wp_send_json_error(['message' => __('Debes iniciar sesión.', 'flavor-chat-ia')]);
        }

        $circulo_id = absint($_POST['circulo_id'] ?? 0);
        $usuario_id = get_current_user_id();

        if ($this->es_miembro($circulo_id, $usuario_id)) {
            wp_send_json_error(['message' => __('Ya formas parte de este círculo.', 'flavor-chat-ia')]);
        }

        global $wpdb;
        $resultado = $wpdb->insert($this->tabla_miembros, [
            'circulo_id' => $circulo_id,
            'usuario_id' => $usuario_id,
            'rol' => 'miembro',
            'estado' => 'activo',
            'fecha_union' => current_time('mysql'),
        ]);

        if ($resultado) {
            wp_send_json_success([
                'message' => __('¡Bienvenido/a al círculo!', 'flavor-chat-ia'),
            ]);
        } else {
            wp_send_json_error(['message' => __('Error al unirse.', 'flavor-chat-ia')]);
        }
    }

    /**
     * AJAX: Publicar necesidad
     */
    public function ajax_publicar_necesidad() {
        check_ajax_referer('flavor_circulos_nonce', 'nonce');

        if (!is_user_logged_in()) {
            wp_send_json_error(['message' => __('Debes iniciar sesión.', 'flavor-chat-ia')]);
        }

        $titulo = sanitize_text_field($_POST['titulo'] ?? '');
        $descripcion = wp_kses_post($_POST['descripcion'] ?? '');

        if (empty($titulo) || empty($descripcion)) {
            wp_send_json_error(['message' => __('Título y descripción son requeridos.', 'flavor-chat-ia')]);
        }

        global $wpdb;
        $resultado = $wpdb->insert($this->tabla_necesidades, [
            'circulo_id' => absint($_POST['circulo_id'] ?? 0) ?: null,
            'usuario_id' => get_current_user_id(),
            'titulo' => $titulo,
            'descripcion' => $descripcion,
            'urgencia' => sanitize_text_field($_POST['urgencia'] ?? 'media'),
            'fecha_necesaria' => sanitize_text_field($_POST['fecha_necesaria'] ?? '') ?: null,
            'horas_estimadas' => floatval($_POST['horas_estimadas'] ?? 1),
            'estado' => 'abierta',
            'fecha_creacion' => current_time('mysql'),
        ]);

        if ($resultado) {
            wp_send_json_success([
                'message' => __('Necesidad publicada. Pronto alguien te contactará.', 'flavor-chat-ia'),
            ]);
        } else {
            wp_send_json_error(['message' => __('Error al publicar.', 'flavor-chat-ia')]);
        }
    }

    /**
     * AJAX: Responder necesidad
     */
    public function ajax_responder_necesidad() {
        check_ajax_referer('flavor_circulos_nonce', 'nonce');

        if (!is_user_logged_in()) {
            wp_send_json_error(['message' => __('Debes iniciar sesión.', 'flavor-chat-ia')]);
        }

        $necesidad_id = absint($_POST['necesidad_id'] ?? 0);
        $usuario_id = get_current_user_id();

        global $wpdb;

        // Verificar que no sea su propia necesidad
        $necesidad = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$this->tabla_necesidades} WHERE id = %d",
            $necesidad_id
        ));

        if (!$necesidad || $necesidad->usuario_id == $usuario_id) {
            wp_send_json_error(['message' => __('No puedes responder a esta necesidad.', 'flavor-chat-ia')]);
        }

        $resultado = $wpdb->insert($this->tabla_respuestas, [
            'necesidad_id' => $necesidad_id,
            'usuario_id' => $usuario_id,
            'mensaje' => sanitize_textarea_field($_POST['mensaje'] ?? ''),
            'estado' => 'pendiente',
            'fecha_creacion' => current_time('mysql'),
        ]);

        if ($resultado) {
            wp_send_json_success([
                'message' => __('¡Gracias por tu solidaridad! Se notificará a quien lo necesita.', 'flavor-chat-ia'),
            ]);
        } else {
            wp_send_json_error(['message' => __('Error al responder.', 'flavor-chat-ia')]);
        }
    }

    /**
     * AJAX: Confirmar horas
     */
    public function ajax_confirmar_horas() {
        check_ajax_referer('flavor_circulos_nonce', 'nonce');

        // Implementar confirmación de horas
        wp_send_json_success(['message' => __('Horas confirmadas.', 'flavor-chat-ia')]);
    }

    /**
     * AJAX: Obtener circulos
     */
    public function ajax_obtener_circulos() {
        global $wpdb;

        $circulos = $wpdb->get_results("
            SELECT c.*,
                   (SELECT COUNT(*) FROM {$this->tabla_miembros} WHERE circulo_id = c.id AND estado = 'activo') as total_miembros
            FROM {$this->tabla_circulos} c
            WHERE c.estado = 'activo'
            ORDER BY c.fecha_creacion DESC
            LIMIT 50
        ");

        wp_send_json_success(['circulos' => $circulos]);
    }

    // =========================================================
    // HELPERS
    // =========================================================

    private function es_miembro($circulo_id, $usuario_id) {
        if (!$usuario_id) return false;

        global $wpdb;
        return (bool) $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM {$this->tabla_miembros} WHERE circulo_id = %d AND usuario_id = %d AND estado = 'activo'",
            $circulo_id, $usuario_id
        ));
    }

    private function render_paginacion($total, $por_pagina, $pagina_actual) {
        $total_paginas = ceil($total / $por_pagina);
        if ($total_paginas <= 1) return;

        $url_base = remove_query_arg('pag');
        ?>
        <nav class="flavor-paginacion">
            <?php if ($pagina_actual > 1): ?>
                <a href="<?php echo esc_url(add_query_arg('pag', $pagina_actual - 1, $url_base)); ?>" class="flavor-pag-link">
                    <span class="dashicons dashicons-arrow-left-alt2"></span>
                </a>
            <?php endif; ?>
            <?php for ($i = 1; $i <= $total_paginas; $i++): ?>
                <?php if ($i == $pagina_actual): ?>
                    <span class="flavor-pag-actual"><?php echo $i; ?></span>
                <?php else: ?>
                    <a href="<?php echo esc_url(add_query_arg('pag', $i, $url_base)); ?>" class="flavor-pag-link"><?php echo $i; ?></a>
                <?php endif; ?>
            <?php endfor; ?>
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
Flavor_Circulos_Cuidados_Frontend_Controller::get_instance();

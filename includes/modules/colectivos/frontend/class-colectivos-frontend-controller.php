<?php
/**
 * Frontend Controller para Colectivos y Asociaciones
 *
 * Controlador frontend con shortcodes, AJAX handlers y tabs para el dashboard
 *
 * @package FlavorChatIA
 * @subpackage Modules\Colectivos
 * @since 4.2.0
 */

if (!defined('ABSPATH')) {
    exit;
}

class Flavor_Colectivos_Frontend_Controller {

    /**
     * Instancia singleton
     * @var Flavor_Colectivos_Frontend_Controller|null
     */
    private static $instancia = null;

    /**
     * Nombre de las tablas
     */
    private $tabla_colectivos;
    private $tabla_miembros;
    private $tabla_proyectos;
    private $tabla_asambleas;

    /**
     * Tipos de colectivos
     */
    private $tipos = [
        'asociacion' => 'Asociación',
        'cooperativa' => 'Cooperativa',
        'ong' => 'ONG',
        'colectivo' => 'Colectivo',
        'plataforma' => 'Plataforma',
    ];

    /**
     * Constructor privado
     */
    private function __construct() {
        global $wpdb;
        $this->tabla_colectivos = $wpdb->prefix . 'flavor_colectivos';
        $this->tabla_miembros = $wpdb->prefix . 'flavor_colectivos_miembros';
        $this->tabla_proyectos = $wpdb->prefix . 'flavor_colectivos_proyectos';
        $this->tabla_asambleas = $wpdb->prefix . 'flavor_colectivos_asambleas';

        $this->init();
    }

    /**
     * Obtiene la instancia singleton
     *
     * @return Flavor_Colectivos_Frontend_Controller
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
        add_shortcode('flavor_colectivos_listado', [$this, 'shortcode_listado']);
        add_shortcode('flavor_colectivos_detalle', [$this, 'shortcode_detalle']);
        add_shortcode('flavor_colectivos_crear', [$this, 'shortcode_crear']);
        add_shortcode('flavor_colectivos_mis_colectivos', [$this, 'shortcode_mis_colectivos']);
        add_shortcode('flavor_colectivos_proyectos', [$this, 'shortcode_proyectos']);
        add_shortcode('flavor_colectivos_asambleas', [$this, 'shortcode_asambleas']);
        add_shortcode('flavor_colectivos_miembros', [$this, 'shortcode_miembros']);
        add_shortcode('flavor_colectivos_mapa', [$this, 'shortcode_mapa']);

        // AJAX handlers
        add_action('wp_ajax_flavor_colectivos_crear', [$this, 'ajax_crear']);
        add_action('wp_ajax_flavor_colectivos_unirse', [$this, 'ajax_unirse']);
        add_action('wp_ajax_flavor_colectivos_salir', [$this, 'ajax_salir']);
        add_action('wp_ajax_flavor_colectivos_crear_proyecto', [$this, 'ajax_crear_proyecto']);
        add_action('wp_ajax_flavor_colectivos_crear_asamblea', [$this, 'ajax_crear_asamblea']);
        add_action('wp_ajax_flavor_colectivos_confirmar_asistencia', [$this, 'ajax_confirmar_asistencia']);
        add_action('wp_ajax_flavor_colectivos_obtener', [$this, 'ajax_obtener']);
        add_action('wp_ajax_nopriv_flavor_colectivos_obtener', [$this, 'ajax_obtener']);
        add_action('wp_ajax_flavor_colectivos_actualizar_rol', [$this, 'ajax_actualizar_rol']);

        // Dashboard tabs
        add_filter('flavor_user_dashboard_tabs', [$this, 'registrar_dashboard_tabs']);

        // Assets
        add_action('wp_enqueue_scripts', [$this, 'registrar_assets']);
    }

    /**
     * Registra los tabs del dashboard
     */
    public function registrar_dashboard_tabs($tabs) {
        $tabs['colectivos'] = [
            'id' => 'colectivos',
            'label' => __('Colectivos', 'flavor-chat-ia'),
            'icon' => 'dashicons-groups',
            'orden' => 40,
            'callback' => [$this, 'render_dashboard_tab'],
        ];

        $tabs['colectivos-mis'] = [
            'id' => 'colectivos-mis',
            'label' => __('Mis Colectivos', 'flavor-chat-ia'),
            'icon' => 'dashicons-id-alt',
            'orden' => 41,
            'parent' => 'colectivos',
            'callback' => [$this, 'render_dashboard_mis_colectivos'],
        ];

        return $tabs;
    }

    /**
     * Registra assets frontend
     */
    public function registrar_assets() {
        wp_register_style(
            'flavor-colectivos-frontend',
            FLAVOR_CHAT_IA_URL . 'includes/modules/colectivos/assets/css/colectivos-frontend.css',
            [],
            FLAVOR_CHAT_IA_VERSION
        );

        wp_register_script(
            'flavor-colectivos-frontend',
            FLAVOR_CHAT_IA_URL . 'includes/modules/colectivos/assets/js/colectivos-frontend.js',
            ['jquery'],
            FLAVOR_CHAT_IA_VERSION,
            true
        );

        wp_localize_script('flavor-colectivos-frontend', 'flavorColectivosConfig', [
            'ajaxUrl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('flavor_colectivos_nonce'),
            'strings' => [
                'confirmarSalir' => __('¿Estás seguro de que quieres abandonar este colectivo?', 'flavor-chat-ia'),
                'procesando' => __('Procesando...', 'flavor-chat-ia'),
                'error' => __('Error al procesar la solicitud', 'flavor-chat-ia'),
            ],
        ]);
    }

    /**
     * Encola assets cuando se necesitan
     */
    private function enqueue_assets() {
        wp_enqueue_style('flavor-colectivos-frontend');
        wp_enqueue_script('flavor-colectivos-frontend');
    }

    // =========================================================
    // SHORTCODES
    // =========================================================

    /**
     * Shortcode: Listado de colectivos
     */
    public function shortcode_listado($atts) {
        $atts = shortcode_atts([
            'tipo' => '',
            'por_pagina' => 12,
            'vista' => 'grid',
        ], $atts);

        $this->enqueue_assets();

        if (!Flavor_Chat_Helpers::tabla_existe($this->tabla_colectivos)) {
            return '<div class="flavor-alert flavor-alert-warning">' .
                   __('El sistema de colectivos no está configurado.', 'flavor-chat-ia') . '</div>';
        }

        global $wpdb;

        $where = "estado = 'activo'";
        if (!empty($atts['tipo'])) {
            $where .= $wpdb->prepare(" AND tipo = %s", $atts['tipo']);
        }

        $filtro_tipo = isset($_GET['tipo']) ? sanitize_text_field($_GET['tipo']) : '';
        if (!empty($filtro_tipo)) {
            $where .= $wpdb->prepare(" AND tipo = %s", $filtro_tipo);
        }

        $pagina = max(1, absint($_GET['pag'] ?? 1));
        $offset = ($pagina - 1) * $atts['por_pagina'];

        $colectivos = $wpdb->get_results("
            SELECT c.*,
                   (SELECT COUNT(*) FROM {$this->tabla_miembros} WHERE colectivo_id = c.id AND estado = 'activo') as total_miembros,
                   (SELECT COUNT(*) FROM {$this->tabla_proyectos} WHERE colectivo_id = c.id AND estado = 'activo') as total_proyectos
            FROM {$this->tabla_colectivos} c
            WHERE {$where}
            ORDER BY c.fecha_creacion DESC
            LIMIT {$atts['por_pagina']} OFFSET {$offset}
        ");

        $total = $wpdb->get_var("SELECT COUNT(*) FROM {$this->tabla_colectivos} WHERE {$where}");

        ob_start();
        ?>
        <div class="flavor-colectivos-listado">
            <div class="flavor-colectivos-header">
                <h2><?php _e('Colectivos y Asociaciones', 'flavor-chat-ia'); ?></h2>
                <div class="flavor-colectivos-acciones">
                    <?php if (is_user_logged_in()): ?>
                        <a href="<?php echo esc_url(Flavor_Chat_Helpers::get_action_url('colectivos', 'crear')); ?>"
                           class="flavor-btn flavor-btn-primary">
                            <span class="dashicons dashicons-plus"></span>
                            <?php _e('Crear Colectivo', 'flavor-chat-ia'); ?>
                        </a>
                    <?php endif; ?>
                </div>
            </div>

            <div class="flavor-filtros">
                <form method="get" class="flavor-filtros-form">
                    <select name="tipo" onchange="this.form.submit()">
                        <option value=""><?php _e('Todos los tipos', 'flavor-chat-ia'); ?></option>
                        <?php foreach ($this->tipos as $valor => $nombre): ?>
                            <option value="<?php echo esc_attr($valor); ?>"
                                    <?php selected($filtro_tipo, $valor); ?>>
                                <?php echo esc_html($nombre); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </form>
            </div>

            <?php if (empty($colectivos)): ?>
                <div class="flavor-alert flavor-alert-info">
                    <?php _e('No hay colectivos disponibles.', 'flavor-chat-ia'); ?>
                </div>
            <?php else: ?>
                <div class="flavor-colectivos-grid">
                    <?php foreach ($colectivos as $colectivo): ?>
                        <?php $this->render_card_colectivo($colectivo); ?>
                    <?php endforeach; ?>
                </div>

                <?php $this->render_paginacion($total, $atts['por_pagina'], $pagina); ?>
            <?php endif; ?>
        </div>
        <?php
        return ob_get_clean();
    }

    /**
     * Renderiza tarjeta de colectivo
     */
    private function render_card_colectivo($colectivo) {
        $imagen = !empty($colectivo->imagen) ? $colectivo->imagen : FLAVOR_CHAT_IA_URL . 'assets/images/colectivo-default.png';
        ?>
        <div class="flavor-colectivo-card">
            <div class="flavor-colectivo-imagen">
                <img src="<?php echo esc_url($imagen); ?>" alt="<?php echo esc_attr($colectivo->nombre); ?>">
                <span class="flavor-badge flavor-badge-tipo"><?php echo esc_html($this->tipos[$colectivo->tipo] ?? $colectivo->tipo); ?></span>
            </div>
            <div class="flavor-colectivo-contenido">
                <h3 class="flavor-colectivo-nombre">
                    <a href="<?php echo esc_url(add_query_arg('colectivo_id', $colectivo->id)); ?>">
                        <?php echo esc_html($colectivo->nombre); ?>
                    </a>
                </h3>
                <?php if (!empty($colectivo->descripcion_corta)): ?>
                    <p class="flavor-colectivo-descripcion"><?php echo esc_html($colectivo->descripcion_corta); ?></p>
                <?php endif; ?>
                <div class="flavor-colectivo-stats">
                    <span class="flavor-stat">
                        <span class="dashicons dashicons-groups"></span>
                        <?php printf(_n('%d miembro', '%d miembros', $colectivo->total_miembros, 'flavor-chat-ia'), $colectivo->total_miembros); ?>
                    </span>
                    <span class="flavor-stat">
                        <span class="dashicons dashicons-portfolio"></span>
                        <?php printf(_n('%d proyecto', '%d proyectos', $colectivo->total_proyectos, 'flavor-chat-ia'), $colectivo->total_proyectos); ?>
                    </span>
                </div>
            </div>
        </div>
        <?php
    }

    /**
     * Shortcode: Detalle de colectivo
     */
    public function shortcode_detalle($atts) {
        $atts = shortcode_atts([
            'colectivo_id' => 0,
        ], $atts);

        $colectivo_id = absint($atts['colectivo_id'] ?: (isset($_GET['colectivo_id']) ? $_GET['colectivo_id'] : 0));
        if (!$colectivo_id) {
            return $this->shortcode_listado([]);
        }

        $this->enqueue_assets();

        global $wpdb;
        $colectivo = $wpdb->get_row($wpdb->prepare("
            SELECT c.*,
                   (SELECT COUNT(*) FROM {$this->tabla_miembros} WHERE colectivo_id = c.id AND estado = 'activo') as total_miembros,
                   (SELECT COUNT(*) FROM {$this->tabla_proyectos} WHERE colectivo_id = c.id) as total_proyectos,
                   (SELECT COUNT(*) FROM {$this->tabla_asambleas} WHERE colectivo_id = c.id) as total_asambleas
            FROM {$this->tabla_colectivos} c
            WHERE c.id = %d AND c.estado = 'activo'
        ", $colectivo_id));

        if (!$colectivo) {
            return '<div class="flavor-alert flavor-alert-warning">' .
                   __('Colectivo no encontrado.', 'flavor-chat-ia') . '</div>';
        }

        $usuario_actual = get_current_user_id();
        $es_miembro = $this->es_miembro($colectivo_id, $usuario_actual);
        $rol_usuario = $this->obtener_rol_usuario($colectivo_id, $usuario_actual);
        $es_admin = in_array($rol_usuario, ['presidente', 'secretario', 'admin']);

        // Obtener miembros destacados
        $miembros_destacados = $wpdb->get_results($wpdb->prepare("
            SELECT m.*, u.display_name, u.user_email
            FROM {$this->tabla_miembros} m
            LEFT JOIN {$wpdb->users} u ON m.usuario_id = u.ID
            WHERE m.colectivo_id = %d AND m.estado = 'activo'
            ORDER BY FIELD(m.rol, 'presidente', 'secretario', 'tesorero', 'vocal', 'miembro')
            LIMIT 8
        ", $colectivo_id));

        // Proyectos activos
        $proyectos = $wpdb->get_results($wpdb->prepare("
            SELECT * FROM {$this->tabla_proyectos}
            WHERE colectivo_id = %d AND estado = 'activo'
            ORDER BY fecha_inicio DESC
            LIMIT 4
        ", $colectivo_id));

        // Próximas asambleas
        $asambleas = $wpdb->get_results($wpdb->prepare("
            SELECT * FROM {$this->tabla_asambleas}
            WHERE colectivo_id = %d AND fecha >= CURDATE()
            ORDER BY fecha ASC
            LIMIT 3
        ", $colectivo_id));

        ob_start();
        ?>
        <div class="flavor-colectivo-detalle">
            <div class="flavor-colectivo-breadcrumb">
                <a href="<?php echo esc_url(remove_query_arg('colectivo_id')); ?>">
                    <?php _e('Colectivos', 'flavor-chat-ia'); ?>
                </a>
                <span class="dashicons dashicons-arrow-right-alt2"></span>
                <span><?php echo esc_html($colectivo->nombre); ?></span>
            </div>

            <header class="flavor-colectivo-header-detalle">
                <div class="flavor-colectivo-imagen-grande">
                    <?php if (!empty($colectivo->imagen)): ?>
                        <img src="<?php echo esc_url($colectivo->imagen); ?>" alt="<?php echo esc_attr($colectivo->nombre); ?>">
                    <?php else: ?>
                        <div class="flavor-colectivo-placeholder">
                            <span class="dashicons dashicons-groups"></span>
                        </div>
                    <?php endif; ?>
                </div>
                <div class="flavor-colectivo-info">
                    <span class="flavor-badge flavor-badge-tipo"><?php echo esc_html($this->tipos[$colectivo->tipo] ?? $colectivo->tipo); ?></span>
                    <h1><?php echo esc_html($colectivo->nombre); ?></h1>
                    <?php if (!empty($colectivo->descripcion_corta)): ?>
                        <p class="flavor-colectivo-subtitulo"><?php echo esc_html($colectivo->descripcion_corta); ?></p>
                    <?php endif; ?>

                    <div class="flavor-colectivo-meta-stats">
                        <span><span class="dashicons dashicons-groups"></span> <?php echo absint($colectivo->total_miembros); ?> <?php _e('miembros', 'flavor-chat-ia'); ?></span>
                        <span><span class="dashicons dashicons-portfolio"></span> <?php echo absint($colectivo->total_proyectos); ?> <?php _e('proyectos', 'flavor-chat-ia'); ?></span>
                        <span><span class="dashicons dashicons-calendar-alt"></span> <?php echo absint($colectivo->total_asambleas); ?> <?php _e('asambleas', 'flavor-chat-ia'); ?></span>
                        <?php if (!empty($colectivo->ubicacion)): ?>
                            <span><span class="dashicons dashicons-location"></span> <?php echo esc_html($colectivo->ubicacion); ?></span>
                        <?php endif; ?>
                    </div>

                    <div class="flavor-colectivo-acciones-principales">
                        <?php if ($usuario_actual): ?>
                            <?php if ($es_miembro): ?>
                                <span class="flavor-badge flavor-badge-success">
                                    <span class="dashicons dashicons-yes"></span>
                                    <?php _e('Eres miembro', 'flavor-chat-ia'); ?>
                                    <?php if ($rol_usuario): echo '(' . esc_html(ucfirst($rol_usuario)) . ')'; endif; ?>
                                </span>
                                <?php if (!$es_admin): ?>
                                    <button class="flavor-btn flavor-btn-outline flavor-btn-sm flavor-salir-colectivo"
                                            data-colectivo-id="<?php echo esc_attr($colectivo_id); ?>">
                                        <?php _e('Abandonar', 'flavor-chat-ia'); ?>
                                    </button>
                                <?php endif; ?>
                            <?php else: ?>
                                <button class="flavor-btn flavor-btn-primary flavor-unirse-colectivo"
                                        data-colectivo-id="<?php echo esc_attr($colectivo_id); ?>">
                                    <span class="dashicons dashicons-plus"></span>
                                    <?php _e('Unirse', 'flavor-chat-ia'); ?>
                                </button>
                            <?php endif; ?>
                        <?php endif; ?>

                        <?php if (!empty($colectivo->web)): ?>
                            <a href="<?php echo esc_url($colectivo->web); ?>" target="_blank" class="flavor-btn flavor-btn-outline flavor-btn-sm">
                                <span class="dashicons dashicons-admin-site"></span>
                                <?php _e('Web', 'flavor-chat-ia'); ?>
                            </a>
                        <?php endif; ?>
                    </div>
                </div>
            </header>

            <div class="flavor-colectivo-contenido-detalle">
                <div class="flavor-colectivo-main">
                    <?php if (!empty($colectivo->descripcion)): ?>
                        <section class="flavor-panel">
                            <h2><?php _e('Sobre nosotros', 'flavor-chat-ia'); ?></h2>
                            <div class="flavor-colectivo-descripcion-completa">
                                <?php echo wp_kses_post(wpautop($colectivo->descripcion)); ?>
                            </div>
                        </section>
                    <?php endif; ?>

                    <?php if (!empty($proyectos)): ?>
                        <section class="flavor-panel">
                            <h2><?php _e('Proyectos Activos', 'flavor-chat-ia'); ?></h2>
                            <div class="flavor-proyectos-lista">
                                <?php foreach ($proyectos as $proyecto): ?>
                                    <div class="flavor-proyecto-item">
                                        <h4><?php echo esc_html($proyecto->nombre); ?></h4>
                                        <p><?php echo esc_html(wp_trim_words($proyecto->descripcion, 20)); ?></p>
                                        <div class="flavor-proyecto-meta">
                                            <span class="flavor-badge flavor-badge-sm"><?php echo esc_html(ucfirst($proyecto->estado)); ?></span>
                                            <?php if (!empty($proyecto->fecha_inicio)): ?>
                                                <span><?php echo date_i18n(get_option('date_format'), strtotime($proyecto->fecha_inicio)); ?></span>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                            <?php if ($es_admin): ?>
                                <a href="<?php echo esc_url(Flavor_Chat_Helpers::get_action_url('colectivos', 'nuevo-proyecto', ['colectivo_id' => $colectivo_id])); ?>"
                                   class="flavor-btn flavor-btn-sm flavor-btn-outline">
                                    <span class="dashicons dashicons-plus"></span>
                                    <?php _e('Nuevo Proyecto', 'flavor-chat-ia'); ?>
                                </a>
                            <?php endif; ?>
                        </section>
                    <?php endif; ?>

                    <?php if (!empty($asambleas)): ?>
                        <section class="flavor-panel">
                            <h2><?php _e('Próximas Asambleas', 'flavor-chat-ia'); ?></h2>
                            <div class="flavor-asambleas-lista">
                                <?php foreach ($asambleas as $asamblea): ?>
                                    <div class="flavor-asamblea-item">
                                        <div class="flavor-asamblea-fecha">
                                            <span class="flavor-dia"><?php echo date('d', strtotime($asamblea->fecha)); ?></span>
                                            <span class="flavor-mes"><?php echo date_i18n('M', strtotime($asamblea->fecha)); ?></span>
                                        </div>
                                        <div class="flavor-asamblea-info">
                                            <h4><?php echo esc_html($asamblea->titulo); ?></h4>
                                            <p class="flavor-asamblea-hora">
                                                <span class="dashicons dashicons-clock"></span>
                                                <?php echo esc_html($asamblea->hora); ?>
                                            </p>
                                            <?php if (!empty($asamblea->ubicacion)): ?>
                                                <p class="flavor-asamblea-lugar">
                                                    <span class="dashicons dashicons-location"></span>
                                                    <?php echo esc_html($asamblea->ubicacion); ?>
                                                </p>
                                            <?php endif; ?>
                                        </div>
                                        <?php if ($es_miembro): ?>
                                            <button class="flavor-btn flavor-btn-sm flavor-confirmar-asistencia"
                                                    data-asamblea-id="<?php echo esc_attr($asamblea->id); ?>">
                                                <?php _e('Confirmar', 'flavor-chat-ia'); ?>
                                            </button>
                                        <?php endif; ?>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </section>
                    <?php endif; ?>
                </div>

                <aside class="flavor-colectivo-sidebar">
                    <section class="flavor-panel">
                        <h3><?php _e('Miembros', 'flavor-chat-ia'); ?></h3>
                        <div class="flavor-miembros-lista">
                            <?php foreach ($miembros_destacados as $miembro): ?>
                                <div class="flavor-miembro-item">
                                    <?php echo get_avatar($miembro->usuario_id, 40); ?>
                                    <div class="flavor-miembro-info">
                                        <span class="flavor-miembro-nombre"><?php echo esc_html($miembro->display_name); ?></span>
                                        <span class="flavor-miembro-rol"><?php echo esc_html(ucfirst($miembro->rol)); ?></span>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                        <?php if ($colectivo->total_miembros > 8): ?>
                            <a href="<?php echo esc_url(add_query_arg('ver', 'miembros')); ?>" class="flavor-ver-todos">
                                <?php printf(__('Ver todos (%d)', 'flavor-chat-ia'), $colectivo->total_miembros); ?>
                            </a>
                        <?php endif; ?>
                    </section>

                    <?php if (!empty($colectivo->contacto_email) || !empty($colectivo->telefono)): ?>
                        <section class="flavor-panel">
                            <h3><?php _e('Contacto', 'flavor-chat-ia'); ?></h3>
                            <?php if (!empty($colectivo->contacto_email)): ?>
                                <p>
                                    <span class="dashicons dashicons-email"></span>
                                    <a href="mailto:<?php echo esc_attr($colectivo->contacto_email); ?>">
                                        <?php echo esc_html($colectivo->contacto_email); ?>
                                    </a>
                                </p>
                            <?php endif; ?>
                            <?php if (!empty($colectivo->telefono)): ?>
                                <p>
                                    <span class="dashicons dashicons-phone"></span>
                                    <?php echo esc_html($colectivo->telefono); ?>
                                </p>
                            <?php endif; ?>
                        </section>
                    <?php endif; ?>

                    <?php if (!empty($colectivo->redes_sociales)): ?>
                        <?php $redes = maybe_unserialize($colectivo->redes_sociales); ?>
                        <?php if (!empty($redes) && is_array($redes)): ?>
                            <section class="flavor-panel">
                                <h3><?php _e('Redes Sociales', 'flavor-chat-ia'); ?></h3>
                                <div class="flavor-redes-sociales">
                                    <?php foreach ($redes as $red => $url): ?>
                                        <?php if (!empty($url)): ?>
                                            <a href="<?php echo esc_url($url); ?>" target="_blank" class="flavor-red-<?php echo esc_attr($red); ?>">
                                                <span class="dashicons dashicons-<?php echo esc_attr($red === 'twitter' ? 'twitter' : 'share'); ?>"></span>
                                            </a>
                                        <?php endif; ?>
                                    <?php endforeach; ?>
                                </div>
                            </section>
                        <?php endif; ?>
                    <?php endif; ?>
                </aside>
            </div>
        </div>
        <?php
        return ob_get_clean();
    }

    /**
     * Shortcode: Crear colectivo
     */
    public function shortcode_crear($atts) {
        if (!is_user_logged_in()) {
            return '<div class="flavor-alert flavor-alert-warning">' .
                   sprintf(__('<a href="%s">Inicia sesión</a> para crear un colectivo.', 'flavor-chat-ia'), wp_login_url(get_permalink())) .
                   '</div>';
        }

        $this->enqueue_assets();

        ob_start();
        ?>
        <div class="flavor-crear-colectivo">
            <h2><?php _e('Crear Nuevo Colectivo', 'flavor-chat-ia'); ?></h2>

            <form id="flavor-form-crear-colectivo" class="flavor-form" enctype="multipart/form-data">
                <?php wp_nonce_field('flavor_colectivos_nonce', 'nonce'); ?>

                <div class="flavor-form-row">
                    <div class="flavor-form-group">
                        <label for="nombre"><?php _e('Nombre del Colectivo', 'flavor-chat-ia'); ?> *</label>
                        <input type="text" name="nombre" id="nombre" required maxlength="150">
                    </div>
                    <div class="flavor-form-group">
                        <label for="tipo"><?php _e('Tipo', 'flavor-chat-ia'); ?> *</label>
                        <select name="tipo" id="tipo" required>
                            <option value=""><?php _e('Seleccionar...', 'flavor-chat-ia'); ?></option>
                            <?php foreach ($this->tipos as $valor => $nombre): ?>
                                <option value="<?php echo esc_attr($valor); ?>"><?php echo esc_html($nombre); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>

                <div class="flavor-form-group">
                    <label for="descripcion_corta"><?php _e('Descripción corta', 'flavor-chat-ia'); ?></label>
                    <input type="text" name="descripcion_corta" id="descripcion_corta" maxlength="250"
                           placeholder="<?php esc_attr_e('Breve descripción (máx. 250 caracteres)', 'flavor-chat-ia'); ?>">
                </div>

                <div class="flavor-form-group">
                    <label for="descripcion"><?php _e('Descripción completa', 'flavor-chat-ia'); ?> *</label>
                    <textarea name="descripcion" id="descripcion" rows="6" required
                              placeholder="<?php esc_attr_e('Describe los objetivos, actividades y valores del colectivo...', 'flavor-chat-ia'); ?>"></textarea>
                </div>

                <div class="flavor-form-row">
                    <div class="flavor-form-group">
                        <label for="ubicacion"><?php _e('Ubicación', 'flavor-chat-ia'); ?></label>
                        <input type="text" name="ubicacion" id="ubicacion"
                               placeholder="<?php esc_attr_e('Ciudad, barrio...', 'flavor-chat-ia'); ?>">
                    </div>
                    <div class="flavor-form-group">
                        <label for="web"><?php _e('Sitio web', 'flavor-chat-ia'); ?></label>
                        <input type="url" name="web" id="web" placeholder="https://">
                    </div>
                </div>

                <div class="flavor-form-row">
                    <div class="flavor-form-group">
                        <label for="contacto_email"><?php _e('Email de contacto', 'flavor-chat-ia'); ?></label>
                        <input type="email" name="contacto_email" id="contacto_email">
                    </div>
                    <div class="flavor-form-group">
                        <label for="telefono"><?php _e('Teléfono', 'flavor-chat-ia'); ?></label>
                        <input type="tel" name="telefono" id="telefono">
                    </div>
                </div>

                <div class="flavor-form-group">
                    <label for="imagen"><?php _e('Imagen/Logo', 'flavor-chat-ia'); ?></label>
                    <input type="file" name="imagen" id="imagen" accept="image/*">
                </div>

                <div class="flavor-form-actions">
                    <button type="submit" class="flavor-btn flavor-btn-primary">
                        <span class="dashicons dashicons-plus"></span>
                        <?php _e('Crear Colectivo', 'flavor-chat-ia'); ?>
                    </button>
                    <a href="<?php echo esc_url(Flavor_Chat_Helpers::get_module_url('colectivos')); ?>" class="flavor-btn flavor-btn-outline">
                        <?php _e('Cancelar', 'flavor-chat-ia'); ?>
                    </a>
                </div>
            </form>
        </div>
        <?php
        return ob_get_clean();
    }

    /**
     * Shortcode: Mis colectivos
     */
    public function shortcode_mis_colectivos($atts) {
        if (!is_user_logged_in()) {
            return '';
        }

        $this->enqueue_assets();
        $usuario_id = get_current_user_id();

        global $wpdb;
        $colectivos = $wpdb->get_results($wpdb->prepare("
            SELECT c.*, m.rol, m.fecha_union
            FROM {$this->tabla_colectivos} c
            INNER JOIN {$this->tabla_miembros} m ON c.id = m.colectivo_id
            WHERE m.usuario_id = %d AND m.estado = 'activo'
            ORDER BY m.fecha_union DESC
        ", $usuario_id));

        ob_start();
        ?>
        <div class="flavor-mis-colectivos">
            <h2><?php _e('Mis Colectivos', 'flavor-chat-ia'); ?></h2>

            <?php if (empty($colectivos)): ?>
                <div class="flavor-alert flavor-alert-info">
                    <?php _e('No perteneces a ningún colectivo todavía.', 'flavor-chat-ia'); ?>
                    <a href="<?php echo esc_url(remove_query_arg('tab')); ?>" class="flavor-btn flavor-btn-sm flavor-btn-primary">
                        <?php _e('Explorar Colectivos', 'flavor-chat-ia'); ?>
                    </a>
                </div>
            <?php else: ?>
                <div class="flavor-colectivos-grid">
                    <?php foreach ($colectivos as $colectivo): ?>
                        <div class="flavor-colectivo-card flavor-mi-colectivo">
                            <div class="flavor-colectivo-imagen">
                                <?php if (!empty($colectivo->imagen)): ?>
                                    <img src="<?php echo esc_url($colectivo->imagen); ?>" alt="">
                                <?php else: ?>
                                    <div class="flavor-colectivo-placeholder-sm">
                                        <span class="dashicons dashicons-groups"></span>
                                    </div>
                                <?php endif; ?>
                            </div>
                            <div class="flavor-colectivo-contenido">
                                <h3>
                                    <a href="<?php echo esc_url(add_query_arg('colectivo_id', $colectivo->id)); ?>">
                                        <?php echo esc_html($colectivo->nombre); ?>
                                    </a>
                                </h3>
                                <span class="flavor-badge flavor-badge-sm"><?php echo esc_html(ucfirst($colectivo->rol)); ?></span>
                                <p class="flavor-fecha-union">
                                    <?php printf(__('Miembro desde %s', 'flavor-chat-ia'), date_i18n(get_option('date_format'), strtotime($colectivo->fecha_union))); ?>
                                </p>
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
     * Shortcode: Proyectos del colectivo
     */
    public function shortcode_proyectos($atts) {
        $atts = shortcode_atts([
            'colectivo_id' => 0,
        ], $atts);

        $colectivo_id = absint($atts['colectivo_id'] ?: (isset($_GET['colectivo_id']) ? $_GET['colectivo_id'] : 0));
        if (!$colectivo_id) {
            return '';
        }

        $this->enqueue_assets();

        global $wpdb;
        $proyectos = $wpdb->get_results($wpdb->prepare("
            SELECT * FROM {$this->tabla_proyectos}
            WHERE colectivo_id = %d
            ORDER BY estado = 'activo' DESC, fecha_inicio DESC
        ", $colectivo_id));

        ob_start();
        ?>
        <div class="flavor-proyectos-colectivo">
            <h2><?php _e('Proyectos', 'flavor-chat-ia'); ?></h2>

            <?php if (empty($proyectos)): ?>
                <div class="flavor-alert flavor-alert-info">
                    <?php _e('Este colectivo no tiene proyectos todavía.', 'flavor-chat-ia'); ?>
                </div>
            <?php else: ?>
                <div class="flavor-proyectos-grid">
                    <?php foreach ($proyectos as $proyecto): ?>
                        <div class="flavor-proyecto-card">
                            <div class="flavor-proyecto-header">
                                <h3><?php echo esc_html($proyecto->nombre); ?></h3>
                                <span class="flavor-badge flavor-badge-<?php echo esc_attr($proyecto->estado); ?>">
                                    <?php echo esc_html(ucfirst($proyecto->estado)); ?>
                                </span>
                            </div>
                            <p class="flavor-proyecto-descripcion">
                                <?php echo esc_html(wp_trim_words($proyecto->descripcion, 30)); ?>
                            </p>
                            <div class="flavor-proyecto-meta">
                                <?php if (!empty($proyecto->fecha_inicio)): ?>
                                    <span>
                                        <span class="dashicons dashicons-calendar"></span>
                                        <?php echo date_i18n(get_option('date_format'), strtotime($proyecto->fecha_inicio)); ?>
                                    </span>
                                <?php endif; ?>
                                <?php if (!empty($proyecto->presupuesto)): ?>
                                    <span>
                                        <span class="dashicons dashicons-money-alt"></span>
                                        <?php echo number_format($proyecto->presupuesto, 0, ',', '.'); ?> &euro;
                                    </span>
                                <?php endif; ?>
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
     * Shortcode: Asambleas del colectivo
     */
    public function shortcode_asambleas($atts) {
        $atts = shortcode_atts([
            'colectivo_id' => 0,
            'solo_futuras' => 'si',
        ], $atts);

        $colectivo_id = absint($atts['colectivo_id'] ?: (isset($_GET['colectivo_id']) ? $_GET['colectivo_id'] : 0));
        if (!$colectivo_id) {
            return '';
        }

        $this->enqueue_assets();

        global $wpdb;
        $where = $wpdb->prepare("colectivo_id = %d", $colectivo_id);
        if ($atts['solo_futuras'] === 'si') {
            $where .= " AND fecha >= CURDATE()";
        }

        $asambleas = $wpdb->get_results("
            SELECT * FROM {$this->tabla_asambleas}
            WHERE {$where}
            ORDER BY fecha ASC
        ");

        ob_start();
        ?>
        <div class="flavor-asambleas-colectivo">
            <h2><?php _e('Asambleas', 'flavor-chat-ia'); ?></h2>

            <?php if (empty($asambleas)): ?>
                <div class="flavor-alert flavor-alert-info">
                    <?php _e('No hay asambleas programadas.', 'flavor-chat-ia'); ?>
                </div>
            <?php else: ?>
                <div class="flavor-asambleas-grid">
                    <?php foreach ($asambleas as $asamblea): ?>
                        <div class="flavor-asamblea-card">
                            <div class="flavor-asamblea-fecha-grande">
                                <span class="flavor-dia"><?php echo date('d', strtotime($asamblea->fecha)); ?></span>
                                <span class="flavor-mes"><?php echo date_i18n('M Y', strtotime($asamblea->fecha)); ?></span>
                            </div>
                            <div class="flavor-asamblea-contenido">
                                <h3><?php echo esc_html($asamblea->titulo); ?></h3>
                                <p class="flavor-asamblea-detalle">
                                    <span class="dashicons dashicons-clock"></span>
                                    <?php echo esc_html($asamblea->hora); ?>
                                </p>
                                <?php if (!empty($asamblea->ubicacion)): ?>
                                    <p class="flavor-asamblea-detalle">
                                        <span class="dashicons dashicons-location"></span>
                                        <?php echo esc_html($asamblea->ubicacion); ?>
                                    </p>
                                <?php endif; ?>
                                <?php if (!empty($asamblea->orden_del_dia)): ?>
                                    <details class="flavor-orden-del-dia">
                                        <summary><?php _e('Orden del día', 'flavor-chat-ia'); ?></summary>
                                        <?php echo wp_kses_post(wpautop($asamblea->orden_del_dia)); ?>
                                    </details>
                                <?php endif; ?>
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
     * Shortcode: Miembros del colectivo
     */
    public function shortcode_miembros($atts) {
        $atts = shortcode_atts([
            'colectivo_id' => 0,
        ], $atts);

        $colectivo_id = absint($atts['colectivo_id'] ?: (isset($_GET['colectivo_id']) ? $_GET['colectivo_id'] : 0));
        if (!$colectivo_id) {
            return '';
        }

        $this->enqueue_assets();

        global $wpdb;
        $miembros = $wpdb->get_results($wpdb->prepare("
            SELECT m.*, u.display_name
            FROM {$this->tabla_miembros} m
            LEFT JOIN {$wpdb->users} u ON m.usuario_id = u.ID
            WHERE m.colectivo_id = %d AND m.estado = 'activo'
            ORDER BY FIELD(m.rol, 'presidente', 'secretario', 'tesorero', 'vocal', 'miembro'), u.display_name
        ", $colectivo_id));

        ob_start();
        ?>
        <div class="flavor-miembros-colectivo">
            <h2><?php _e('Miembros', 'flavor-chat-ia'); ?></h2>

            <div class="flavor-miembros-grid">
                <?php foreach ($miembros as $miembro): ?>
                    <div class="flavor-miembro-card">
                        <?php echo get_avatar($miembro->usuario_id, 64); ?>
                        <h4><?php echo esc_html($miembro->display_name); ?></h4>
                        <span class="flavor-badge"><?php echo esc_html(ucfirst($miembro->rol)); ?></span>
                        <p class="flavor-fecha-union">
                            <?php printf(__('Desde %s', 'flavor-chat-ia'), date_i18n('M Y', strtotime($miembro->fecha_union))); ?>
                        </p>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
        <?php
        return ob_get_clean();
    }

    /**
     * Shortcode: Mapa de colectivos
     */
    public function shortcode_mapa($atts) {
        $this->enqueue_assets();
        wp_enqueue_script('leaflet');
        wp_enqueue_style('leaflet');

        global $wpdb;
        $colectivos = $wpdb->get_results("
            SELECT id, nombre, tipo, ubicacion, latitud, longitud, descripcion_corta
            FROM {$this->tabla_colectivos}
            WHERE estado = 'activo' AND latitud IS NOT NULL AND longitud IS NOT NULL
        ");

        ob_start();
        ?>
        <div class="flavor-colectivos-mapa">
            <div id="flavor-mapa-colectivos" style="height: 500px;"></div>
        </div>
        <script>
        document.addEventListener('DOMContentLoaded', function() {
            if (typeof L === 'undefined') return;

            var mapa = L.map('flavor-mapa-colectivos').setView([40.4168, -3.7038], 6);
            L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                attribution: '&copy; OpenStreetMap'
            }).addTo(mapa);

            var colectivos = <?php echo json_encode($colectivos); ?>;
            colectivos.forEach(function(c) {
                if (c.latitud && c.longitud) {
                    L.marker([parseFloat(c.latitud), parseFloat(c.longitud)])
                        .bindPopup('<strong>' + c.nombre + '</strong><br>' + (c.descripcion_corta || ''))
                        .addTo(mapa);
                }
            });
        });
        </script>
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

        $mis_colectivos = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM {$this->tabla_miembros} WHERE usuario_id = %d AND estado = 'activo'",
            $usuario_id
        ));

        $total_colectivos = $wpdb->get_var("SELECT COUNT(*) FROM {$this->tabla_colectivos} WHERE estado = 'activo'");

        // Colectivos recientes
        $recientes = $wpdb->get_results("
            SELECT c.*,
                   (SELECT COUNT(*) FROM {$this->tabla_miembros} WHERE colectivo_id = c.id AND estado = 'activo') as total_miembros
            FROM {$this->tabla_colectivos} c
            WHERE c.estado = 'activo'
            ORDER BY c.fecha_creacion DESC
            LIMIT 4
        ");

        ?>
        <div class="flavor-dashboard-colectivos">
            <div class="flavor-kpi-grid flavor-grid-3">
                <div class="flavor-kpi-card">
                    <div class="flavor-kpi-icono"><span class="dashicons dashicons-id-alt"></span></div>
                    <div class="flavor-kpi-contenido">
                        <span class="flavor-kpi-valor"><?php echo absint($mis_colectivos); ?></span>
                        <span class="flavor-kpi-label"><?php _e('Mis Colectivos', 'flavor-chat-ia'); ?></span>
                    </div>
                </div>
                <div class="flavor-kpi-card">
                    <div class="flavor-kpi-icono"><span class="dashicons dashicons-groups"></span></div>
                    <div class="flavor-kpi-contenido">
                        <span class="flavor-kpi-valor"><?php echo absint($total_colectivos); ?></span>
                        <span class="flavor-kpi-label"><?php _e('Total Colectivos', 'flavor-chat-ia'); ?></span>
                    </div>
                </div>
                <div class="flavor-kpi-card">
                    <div class="flavor-kpi-icono"><span class="dashicons dashicons-plus-alt"></span></div>
                    <div class="flavor-kpi-contenido">
                        <a href="<?php echo esc_url(Flavor_Chat_Helpers::get_action_url('colectivos', 'crear')); ?>" class="flavor-btn flavor-btn-primary">
                            <?php _e('Crear Colectivo', 'flavor-chat-ia'); ?>
                        </a>
                    </div>
                </div>
            </div>

            <div class="flavor-panel">
                <h3><?php _e('Colectivos Recientes', 'flavor-chat-ia'); ?></h3>
                <?php if (empty($recientes)): ?>
                    <p class="flavor-no-datos"><?php _e('No hay colectivos disponibles.', 'flavor-chat-ia'); ?></p>
                <?php else: ?>
                    <div class="flavor-colectivos-grid flavor-grid-sm">
                        <?php foreach ($recientes as $colectivo): ?>
                            <?php $this->render_card_colectivo($colectivo); ?>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
        <?php
    }

    /**
     * Render del tab "Mis Colectivos"
     */
    public function render_dashboard_mis_colectivos() {
        echo $this->shortcode_mis_colectivos([]);
    }

    // =========================================================
    // AJAX HANDLERS
    // =========================================================

    /**
     * AJAX: Crear colectivo
     */
    public function ajax_crear() {
        check_ajax_referer('flavor_colectivos_nonce', 'nonce');

        if (!is_user_logged_in()) {
            wp_send_json_error(['message' => __('Debes iniciar sesión.', 'flavor-chat-ia')]);
        }

        $nombre = sanitize_text_field($_POST['nombre'] ?? '');
        $tipo = sanitize_text_field($_POST['tipo'] ?? '');
        $descripcion = wp_kses_post($_POST['descripcion'] ?? '');

        if (empty($nombre) || empty($tipo) || empty($descripcion)) {
            wp_send_json_error(['message' => __('Los campos obligatorios son requeridos.', 'flavor-chat-ia')]);
        }

        global $wpdb;

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

        $resultado = $wpdb->insert($this->tabla_colectivos, [
            'nombre' => $nombre,
            'tipo' => $tipo,
            'descripcion' => $descripcion,
            'descripcion_corta' => sanitize_text_field($_POST['descripcion_corta'] ?? ''),
            'ubicacion' => sanitize_text_field($_POST['ubicacion'] ?? ''),
            'web' => esc_url_raw($_POST['web'] ?? ''),
            'contacto_email' => sanitize_email($_POST['contacto_email'] ?? ''),
            'telefono' => sanitize_text_field($_POST['telefono'] ?? ''),
            'imagen' => $imagen_url,
            'creador_id' => get_current_user_id(),
            'estado' => 'activo',
            'fecha_creacion' => current_time('mysql'),
        ]);

        if ($resultado) {
            $colectivo_id = $wpdb->insert_id;

            // Añadir creador como presidente
            $wpdb->insert($this->tabla_miembros, [
                'colectivo_id' => $colectivo_id,
                'usuario_id' => get_current_user_id(),
                'rol' => 'presidente',
                'estado' => 'activo',
                'fecha_union' => current_time('mysql'),
            ]);

            wp_send_json_success([
                'message' => __('Colectivo creado correctamente.', 'flavor-chat-ia'),
                'colectivo_id' => $colectivo_id,
                'redirect' => Flavor_Chat_Helpers::get_item_url('colectivos', $colectivo_id),
            ]);
        } else {
            wp_send_json_error(['message' => __('Error al crear el colectivo.', 'flavor-chat-ia')]);
        }
    }

    /**
     * AJAX: Unirse a colectivo
     */
    public function ajax_unirse() {
        check_ajax_referer('flavor_colectivos_nonce', 'nonce');

        if (!is_user_logged_in()) {
            wp_send_json_error(['message' => __('Debes iniciar sesión.', 'flavor-chat-ia')]);
        }

        $colectivo_id = absint($_POST['colectivo_id'] ?? 0);
        $usuario_id = get_current_user_id();

        if (!$colectivo_id) {
            wp_send_json_error(['message' => __('Colectivo no válido.', 'flavor-chat-ia')]);
        }

        if ($this->es_miembro($colectivo_id, $usuario_id)) {
            wp_send_json_error(['message' => __('Ya eres miembro de este colectivo.', 'flavor-chat-ia')]);
        }

        global $wpdb;
        $resultado = $wpdb->insert($this->tabla_miembros, [
            'colectivo_id' => $colectivo_id,
            'usuario_id' => $usuario_id,
            'rol' => 'miembro',
            'estado' => 'activo',
            'fecha_union' => current_time('mysql'),
        ]);

        if ($resultado) {
            wp_send_json_success([
                'message' => __('Te has unido al colectivo.', 'flavor-chat-ia'),
            ]);
        } else {
            wp_send_json_error(['message' => __('Error al unirse.', 'flavor-chat-ia')]);
        }
    }

    /**
     * AJAX: Salir del colectivo
     */
    public function ajax_salir() {
        check_ajax_referer('flavor_colectivos_nonce', 'nonce');

        if (!is_user_logged_in()) {
            wp_send_json_error(['message' => __('Debes iniciar sesión.', 'flavor-chat-ia')]);
        }

        $colectivo_id = absint($_POST['colectivo_id'] ?? 0);
        $usuario_id = get_current_user_id();

        global $wpdb;

        // Verificar que no sea el único admin
        $rol = $this->obtener_rol_usuario($colectivo_id, $usuario_id);
        if (in_array($rol, ['presidente', 'admin'])) {
            $otros_admins = $wpdb->get_var($wpdb->prepare("
                SELECT COUNT(*) FROM {$this->tabla_miembros}
                WHERE colectivo_id = %d AND usuario_id != %d AND rol IN ('presidente', 'admin') AND estado = 'activo'
            ", $colectivo_id, $usuario_id));

            if ($otros_admins == 0) {
                wp_send_json_error([
                    'message' => __('No puedes abandonar el colectivo siendo el único administrador. Asigna otro administrador primero.', 'flavor-chat-ia'),
                ]);
            }
        }

        $wpdb->update(
            $this->tabla_miembros,
            ['estado' => 'inactivo', 'fecha_baja' => current_time('mysql')],
            ['colectivo_id' => $colectivo_id, 'usuario_id' => $usuario_id]
        );

        wp_send_json_success([
            'message' => __('Has abandonado el colectivo.', 'flavor-chat-ia'),
        ]);
    }

    /**
     * AJAX: Crear proyecto
     */
    public function ajax_crear_proyecto() {
        check_ajax_referer('flavor_colectivos_nonce', 'nonce');

        if (!is_user_logged_in()) {
            wp_send_json_error(['message' => __('Debes iniciar sesión.', 'flavor-chat-ia')]);
        }

        $colectivo_id = absint($_POST['colectivo_id'] ?? 0);
        $usuario_id = get_current_user_id();

        // Verificar que es admin del colectivo
        $rol = $this->obtener_rol_usuario($colectivo_id, $usuario_id);
        if (!in_array($rol, ['presidente', 'secretario', 'admin'])) {
            wp_send_json_error(['message' => __('No tienes permiso para crear proyectos.', 'flavor-chat-ia')]);
        }

        $nombre = sanitize_text_field($_POST['nombre'] ?? '');
        $descripcion = wp_kses_post($_POST['descripcion'] ?? '');

        if (empty($nombre)) {
            wp_send_json_error(['message' => __('El nombre es requerido.', 'flavor-chat-ia')]);
        }

        global $wpdb;
        $resultado = $wpdb->insert($this->tabla_proyectos, [
            'colectivo_id' => $colectivo_id,
            'nombre' => $nombre,
            'descripcion' => $descripcion,
            'fecha_inicio' => sanitize_text_field($_POST['fecha_inicio'] ?? ''),
            'fecha_fin' => sanitize_text_field($_POST['fecha_fin'] ?? ''),
            'presupuesto' => floatval($_POST['presupuesto'] ?? 0),
            'estado' => 'activo',
            'creador_id' => $usuario_id,
            'fecha_creacion' => current_time('mysql'),
        ]);

        if ($resultado) {
            wp_send_json_success([
                'message' => __('Proyecto creado correctamente.', 'flavor-chat-ia'),
                'proyecto_id' => $wpdb->insert_id,
            ]);
        } else {
            wp_send_json_error(['message' => __('Error al crear el proyecto.', 'flavor-chat-ia')]);
        }
    }

    /**
     * AJAX: Crear asamblea
     */
    public function ajax_crear_asamblea() {
        check_ajax_referer('flavor_colectivos_nonce', 'nonce');

        if (!is_user_logged_in()) {
            wp_send_json_error(['message' => __('Debes iniciar sesión.', 'flavor-chat-ia')]);
        }

        $colectivo_id = absint($_POST['colectivo_id'] ?? 0);
        $usuario_id = get_current_user_id();

        $rol = $this->obtener_rol_usuario($colectivo_id, $usuario_id);
        if (!in_array($rol, ['presidente', 'secretario', 'admin'])) {
            wp_send_json_error(['message' => __('No tienes permiso para convocar asambleas.', 'flavor-chat-ia')]);
        }

        $titulo = sanitize_text_field($_POST['titulo'] ?? '');
        $fecha = sanitize_text_field($_POST['fecha'] ?? '');
        $hora = sanitize_text_field($_POST['hora'] ?? '');

        if (empty($titulo) || empty($fecha) || empty($hora)) {
            wp_send_json_error(['message' => __('Título, fecha y hora son requeridos.', 'flavor-chat-ia')]);
        }

        global $wpdb;
        $resultado = $wpdb->insert($this->tabla_asambleas, [
            'colectivo_id' => $colectivo_id,
            'titulo' => $titulo,
            'fecha' => $fecha,
            'hora' => $hora,
            'ubicacion' => sanitize_text_field($_POST['ubicacion'] ?? ''),
            'orden_del_dia' => wp_kses_post($_POST['orden_del_dia'] ?? ''),
            'convocante_id' => $usuario_id,
            'fecha_creacion' => current_time('mysql'),
        ]);

        if ($resultado) {
            wp_send_json_success([
                'message' => __('Asamblea convocada correctamente.', 'flavor-chat-ia'),
            ]);
        } else {
            wp_send_json_error(['message' => __('Error al convocar asamblea.', 'flavor-chat-ia')]);
        }
    }

    /**
     * AJAX: Confirmar asistencia
     */
    public function ajax_confirmar_asistencia() {
        check_ajax_referer('flavor_colectivos_nonce', 'nonce');

        if (!is_user_logged_in()) {
            wp_send_json_error(['message' => __('Debes iniciar sesión.', 'flavor-chat-ia')]);
        }

        // Guardar confirmación de asistencia
        $asamblea_id = absint($_POST['asamblea_id'] ?? 0);
        $usuario_id = get_current_user_id();

        // Se podría guardar en una tabla de asistencias
        update_user_meta($usuario_id, 'flavor_asistencia_asamblea_' . $asamblea_id, current_time('mysql'));

        wp_send_json_success([
            'message' => __('Asistencia confirmada.', 'flavor-chat-ia'),
        ]);
    }

    /**
     * AJAX: Obtener colectivos
     */
    public function ajax_obtener() {
        global $wpdb;

        $tipo = sanitize_text_field($_POST['tipo'] ?? '');
        $pagina = max(1, absint($_POST['pagina'] ?? 1));
        $por_pagina = 12;

        $where = "estado = 'activo'";
        if (!empty($tipo)) {
            $where .= $wpdb->prepare(" AND tipo = %s", $tipo);
        }

        $colectivos = $wpdb->get_results("
            SELECT c.*,
                   (SELECT COUNT(*) FROM {$this->tabla_miembros} WHERE colectivo_id = c.id AND estado = 'activo') as total_miembros
            FROM {$this->tabla_colectivos} c
            WHERE {$where}
            ORDER BY c.fecha_creacion DESC
            LIMIT {$por_pagina} OFFSET " . (($pagina - 1) * $por_pagina)
        );

        wp_send_json_success(['colectivos' => $colectivos]);
    }

    /**
     * AJAX: Actualizar rol de miembro
     */
    public function ajax_actualizar_rol() {
        check_ajax_referer('flavor_colectivos_nonce', 'nonce');

        if (!is_user_logged_in()) {
            wp_send_json_error(['message' => __('Debes iniciar sesión.', 'flavor-chat-ia')]);
        }

        $colectivo_id = absint($_POST['colectivo_id'] ?? 0);
        $miembro_id = absint($_POST['miembro_id'] ?? 0);
        $nuevo_rol = sanitize_text_field($_POST['rol'] ?? '');
        $usuario_actual = get_current_user_id();

        $rol_actual = $this->obtener_rol_usuario($colectivo_id, $usuario_actual);
        if (!in_array($rol_actual, ['presidente', 'admin'])) {
            wp_send_json_error(['message' => __('No tienes permiso para cambiar roles.', 'flavor-chat-ia')]);
        }

        global $wpdb;
        $wpdb->update(
            $this->tabla_miembros,
            ['rol' => $nuevo_rol],
            ['colectivo_id' => $colectivo_id, 'usuario_id' => $miembro_id]
        );

        wp_send_json_success(['message' => __('Rol actualizado.', 'flavor-chat-ia')]);
    }

    // =========================================================
    // HELPERS
    // =========================================================

    /**
     * Verifica si un usuario es miembro de un colectivo
     */
    private function es_miembro($colectivo_id, $usuario_id) {
        if (!$usuario_id) {
            return false;
        }

        global $wpdb;
        return (bool) $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM {$this->tabla_miembros} WHERE colectivo_id = %d AND usuario_id = %d AND estado = 'activo'",
            $colectivo_id, $usuario_id
        ));
    }

    /**
     * Obtiene el rol de un usuario en un colectivo
     */
    private function obtener_rol_usuario($colectivo_id, $usuario_id) {
        if (!$usuario_id) {
            return null;
        }

        global $wpdb;
        return $wpdb->get_var($wpdb->prepare(
            "SELECT rol FROM {$this->tabla_miembros} WHERE colectivo_id = %d AND usuario_id = %d AND estado = 'activo'",
            $colectivo_id, $usuario_id
        ));
    }

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
Flavor_Colectivos_Frontend_Controller::get_instance();

<?php
/**
 * Frontend Controller para Economia del Don
 *
 * Sistema de donaciones y regalos sin expectativa de retorno
 *
 * @package FlavorPlatform
 * @subpackage Modules\EconomiaDon
 * @since 4.2.0
 */

if (!defined('ABSPATH')) {
    exit;
}

class Flavor_Economia_Don_Frontend_Controller {

    /**
     * Instancia singleton
     * @var Flavor_Economia_Don_Frontend_Controller|null
     */
    private static $instancia = null;

    /**
     * Nombre de las tablas
     */
    private $tabla_dones;
    private $tabla_solicitudes;
    private $tabla_entregas;
    private $tabla_gratitudes;

    /**
     * Categorias de dones
     */
    private $categorias = [
        'objetos' => ['nombre' => 'Objetos', 'icono' => 'dashicons-archive', 'color' => '#3498db'],
        'alimentos' => ['nombre' => 'Alimentos', 'icono' => 'dashicons-carrot', 'color' => '#27ae60'],
        'servicios' => ['nombre' => 'Servicios', 'icono' => 'dashicons-admin-tools', 'color' => '#9b59b6'],
        'tiempo' => ['nombre' => 'Tiempo', 'icono' => 'dashicons-clock', 'color' => '#e74c3c'],
        'conocimiento' => ['nombre' => 'Conocimiento', 'icono' => 'dashicons-book', 'color' => '#f39c12'],
        'espacios' => ['nombre' => 'Espacios', 'icono' => 'dashicons-admin-home', 'color' => '#1abc9c'],
    ];

    /**
     * Estados del don
     */
    private $estados = [
        'disponible' => ['nombre' => 'Disponible', 'color' => '#27ae60'],
        'reservado' => ['nombre' => 'Reservado', 'color' => '#f39c12'],
        'entregado' => ['nombre' => 'Entregado', 'color' => '#3498db'],
        'recibido' => ['nombre' => 'Recibido con gratitud', 'color' => '#9b59b6'],
    ];

    /**
     * Constructor privado
     */
    private function __construct() {
        global $wpdb;
        $this->tabla_dones = $wpdb->prefix . 'flavor_economia_dones';
        $this->tabla_solicitudes = $wpdb->prefix . 'flavor_economia_solicitudes';
        $this->tabla_entregas = $wpdb->prefix . 'flavor_economia_entregas';
        $this->tabla_gratitudes = $wpdb->prefix . 'flavor_economia_gratitudes';

        $this->init();
    }

    /**
     * Obtiene la instancia singleton
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
        if (!shortcode_exists('flavor_don_listado')) {
            add_shortcode('flavor_don_listado', [$this, 'shortcode_listado']);
        }
        if (!shortcode_exists('flavor_don_detalle')) {
            add_shortcode('flavor_don_detalle', [$this, 'shortcode_detalle']);
        }
        if (!shortcode_exists('flavor_don_ofrecer')) {
            add_shortcode('flavor_don_ofrecer', [$this, 'shortcode_ofrecer']);
        }
        if (!shortcode_exists('flavor_don_mis_dones')) {
            add_shortcode('flavor_don_mis_dones', [$this, 'shortcode_mis_dones']);
        }
        if (!shortcode_exists('flavor_don_mis_recepciones')) {
            add_shortcode('flavor_don_mis_recepciones', [$this, 'shortcode_mis_recepciones']);
        }
        if (!shortcode_exists('flavor_don_muro_gratitud')) {
            add_shortcode('flavor_don_muro_gratitud', [$this, 'shortcode_muro_gratitud']);
        }
        if (!shortcode_exists('flavor_don_estadisticas')) {
            add_shortcode('flavor_don_estadisticas', [$this, 'shortcode_estadisticas']);
        }

        // AJAX handlers
        add_action('wp_ajax_flavor_don_ofrecer', [$this, 'ajax_ofrecer']);
        add_action('wp_ajax_flavor_don_solicitar', [$this, 'ajax_solicitar']);
        add_action('wp_ajax_flavor_don_confirmar_entrega', [$this, 'ajax_confirmar_entrega']);
        add_action('wp_ajax_flavor_don_agradecer', [$this, 'ajax_agradecer']);
        add_action('wp_ajax_flavor_don_obtener', [$this, 'ajax_obtener']);
        add_action('wp_ajax_nopriv_flavor_don_obtener', [$this, 'ajax_obtener']);

        // Dashboard tabs
        add_filter('flavor_user_dashboard_tabs', [$this, 'registrar_dashboard_tabs']);

        // Assets
        add_action('wp_enqueue_scripts', [$this, 'registrar_assets']);
    }

    /**
     * Registra los tabs del dashboard
     */
    public function registrar_dashboard_tabs($tabs) {
        $tabs['economia-don'] = [
            'id' => 'economia-don',
            'label' => __('Dar/Recibir', FLAVOR_PLATFORM_TEXT_DOMAIN),
            'icon' => 'dashicons-heart',
            'orden' => 55,
            'callback' => [$this, 'render_dashboard_tab'],
        ];

        return $tabs;
    }

    /**
     * Registra assets frontend
     */
    public function registrar_assets() {
        wp_register_style(
            'flavor-economia-don-frontend',
            FLAVOR_PLATFORM_URL . 'includes/modules/economia-don/assets/css/economia-don-frontend.css',
            [],
            FLAVOR_PLATFORM_VERSION
        );

        wp_register_script(
            'flavor-economia-don-frontend',
            FLAVOR_PLATFORM_URL . 'includes/modules/economia-don/assets/js/economia-don-frontend.js',
            ['jquery'],
            FLAVOR_PLATFORM_VERSION,
            true
        );

        wp_localize_script('flavor-economia-don-frontend', 'flavorDonConfig', [
            'ajaxUrl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('flavor_don_nonce'),
            'strings' => [
                'confirmar' => __('¿Confirmar?', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'enviando' => __('Enviando...', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'gracias' => __('Gracias por tu generosidad', FLAVOR_PLATFORM_TEXT_DOMAIN),
            ],
        ]);
    }

    /**
     * Encola assets
     */
    private function enqueue_assets() {
        wp_enqueue_style('flavor-economia-don-frontend');
        wp_enqueue_script('flavor-economia-don-frontend');
    }

    // =========================================================
    // SHORTCODES
    // =========================================================

    /**
     * Shortcode: Listado de dones
     */
    public function shortcode_listado($atts) {
        $atts = shortcode_atts([
            'categoria' => '',
            'por_pagina' => 12,
        ], $atts);

        $this->enqueue_assets();

        if (!Flavor_Platform_Helpers::tabla_existe($this->tabla_dones)) {
            return '<div class="flavor-alert flavor-alert-warning">' .
                   __('El sistema no está configurado.', FLAVOR_PLATFORM_TEXT_DOMAIN) . '</div>';
        }

        global $wpdb;

        $filtro_cat = isset($_GET['categoria']) ? sanitize_text_field($_GET['categoria']) : $atts['categoria'];

        $where = "d.estado = 'disponible'";
        if (!empty($filtro_cat)) {
            $where .= $wpdb->prepare(" AND d.categoria = %s", $filtro_cat);
        }

        $pagina = max(1, absint($_GET['pag'] ?? 1));
        $offset = ($pagina - 1) * $atts['por_pagina'];

        $dones = $wpdb->get_results("
            SELECT d.*, u.display_name as donante_nombre
            FROM {$this->tabla_dones} d
            LEFT JOIN {$wpdb->users} u ON d.usuario_id = u.ID
            WHERE {$where}
            ORDER BY d.fecha_creacion DESC
            LIMIT {$atts['por_pagina']} OFFSET {$offset}
        ");

        $total = $wpdb->get_var("SELECT COUNT(*) FROM {$this->tabla_dones} d WHERE {$where}");

        // Stats generales
        $stats = [
            'total_dones' => $wpdb->get_var("SELECT COUNT(*) FROM {$this->tabla_dones}"),
            'entregados' => $wpdb->get_var("SELECT COUNT(*) FROM {$this->tabla_dones} WHERE estado IN ('entregado', 'recibido')"),
            'donantes' => $wpdb->get_var("SELECT COUNT(DISTINCT usuario_id) FROM {$this->tabla_dones}"),
        ];

        ob_start();
        ?>
        <div class="flavor-don-listado">
            <div class="flavor-don-header">
                <div class="flavor-don-intro">
                    <h2><?php _e('Economía del Don', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></h2>
                    <p><?php _e('Dar y recibir sin esperar nada a cambio. El placer está en el acto de dar.', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></p>
                </div>
                <div class="flavor-don-stats-mini">
                    <span><strong><?php echo number_format($stats['total_dones']); ?></strong> <?php _e('dones ofrecidos', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></span>
                    <span><strong><?php echo number_format($stats['entregados']); ?></strong> <?php _e('entregados', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></span>
                    <span><strong><?php echo number_format($stats['donantes']); ?></strong> <?php _e('personas dando', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></span>
                </div>
            </div>

            <div class="flavor-don-categorias">
                <a href="<?php echo esc_url(remove_query_arg('categoria')); ?>"
                   class="flavor-cat-btn <?php echo empty($filtro_cat) ? 'activo' : ''; ?>">
                    <?php _e('Todos', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                </a>
                <?php foreach ($this->categorias as $clave => $cat): ?>
                    <a href="<?php echo esc_url(add_query_arg('categoria', $clave)); ?>"
                       class="flavor-cat-btn <?php echo $filtro_cat === $clave ? 'activo' : ''; ?>"
                       style="<?php echo $filtro_cat === $clave ? 'background-color:' . esc_attr($cat['color']) . ';' : ''; ?>">
                        <span class="dashicons <?php echo esc_attr($cat['icono']); ?>"></span>
                        <?php echo esc_html($cat['nombre']); ?>
                    </a>
                <?php endforeach; ?>
            </div>

            <?php if (is_user_logged_in()): ?>
                <div class="flavor-don-cta-ofrecer">
                    <a href="<?php echo esc_url(Flavor_Platform_Helpers::get_action_url('economia-don', 'ofrecer')); ?>" class="flavor-btn flavor-btn-primary flavor-btn-lg">
                        <span class="dashicons dashicons-heart"></span>
                        <?php _e('Ofrecer un don', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                    </a>
                </div>
            <?php endif; ?>

            <?php if (empty($dones)): ?>
                <div class="flavor-alert flavor-alert-info">
                    <?php _e('No hay dones disponibles en esta categoría.', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                </div>
            <?php else: ?>
                <div class="flavor-dones-grid">
                    <?php foreach ($dones as $don): ?>
                        <?php $this->render_card_don($don); ?>
                    <?php endforeach; ?>
                </div>

                <?php $this->render_paginacion($total, $atts['por_pagina'], $pagina); ?>
            <?php endif; ?>
        </div>
        <?php
        return ob_get_clean();
    }

    /**
     * Renderiza tarjeta de don
     */
    private function render_card_don($don) {
        $cat_info = $this->categorias[$don->categoria] ?? ['nombre' => $don->categoria, 'icono' => 'dashicons-heart', 'color' => '#6b7280'];
        ?>
        <div class="flavor-don-card">
            <div class="flavor-don-imagen">
                <?php if (!empty($don->imagen)): ?>
                    <img src="<?php echo esc_url($don->imagen); ?>" alt="">
                <?php else: ?>
                    <div class="flavor-don-placeholder" style="background-color: <?php echo esc_attr($cat_info['color']); ?>20;">
                        <span class="dashicons <?php echo esc_attr($cat_info['icono']); ?>" style="color: <?php echo esc_attr($cat_info['color']); ?>;"></span>
                    </div>
                <?php endif; ?>
                <span class="flavor-don-categoria" style="background-color: <?php echo esc_attr($cat_info['color']); ?>;">
                    <?php echo esc_html($cat_info['nombre']); ?>
                </span>
            </div>
            <div class="flavor-don-contenido">
                <h3 class="flavor-don-titulo">
                    <a href="<?php echo esc_url(add_query_arg('don_id', $don->id)); ?>">
                        <?php echo esc_html($don->titulo); ?>
                    </a>
                </h3>
                <p class="flavor-don-descripcion"><?php echo esc_html(wp_trim_words($don->descripcion, 15)); ?></p>
                <div class="flavor-don-donante">
                    <?php echo get_avatar($don->usuario_id, 28); ?>
                    <span><?php echo esc_html($don->donante_nombre); ?></span>
                </div>
                <?php if (!empty($don->ubicacion)): ?>
                    <p class="flavor-don-ubicacion">
                        <span class="dashicons dashicons-location"></span>
                        <?php echo esc_html($don->ubicacion); ?>
                    </p>
                <?php endif; ?>
            </div>
        </div>
        <?php
    }

    /**
     * Shortcode: Detalle de don
     */
    public function shortcode_detalle($atts) {
        $atts = shortcode_atts([
            'don_id' => 0,
        ], $atts);

        $don_id = absint($atts['don_id'] ?: (isset($_GET['don_id']) ? $_GET['don_id'] : 0));
        if (!$don_id) {
            return $this->shortcode_listado([]);
        }

        $this->enqueue_assets();

        global $wpdb;
        $don = $wpdb->get_row($wpdb->prepare("
            SELECT d.*, u.display_name as donante_nombre
            FROM {$this->tabla_dones} d
            LEFT JOIN {$wpdb->users} u ON d.usuario_id = u.ID
            WHERE d.id = %d
        ", $don_id));

        if (!$don) {
            return '<div class="flavor-alert flavor-alert-warning">' .
                   __('Don no encontrado.', FLAVOR_PLATFORM_TEXT_DOMAIN) . '</div>';
        }

        $cat_info = $this->categorias[$don->categoria] ?? ['nombre' => $don->categoria, 'icono' => 'dashicons-heart', 'color' => '#6b7280'];
        $estado_info = $this->estados[$don->estado] ?? ['nombre' => $don->estado, 'color' => '#6b7280'];
        $usuario_actual = get_current_user_id();
        $es_donante = ($don->usuario_id == $usuario_actual);

        ob_start();
        ?>
        <div class="flavor-don-detalle">
            <div class="flavor-don-breadcrumb">
                <a href="<?php echo esc_url(remove_query_arg('don_id')); ?>">
                    <?php _e('Dones', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                </a>
                <span class="dashicons dashicons-arrow-right-alt2"></span>
                <span><?php echo esc_html(wp_trim_words($don->titulo, 5)); ?></span>
            </div>

            <div class="flavor-don-contenido-detalle">
                <div class="flavor-don-main">
                    <div class="flavor-don-imagen-grande">
                        <?php if (!empty($don->imagen)): ?>
                            <img src="<?php echo esc_url($don->imagen); ?>" alt="">
                        <?php else: ?>
                            <div class="flavor-don-placeholder-grande" style="background-color: <?php echo esc_attr($cat_info['color']); ?>20;">
                                <span class="dashicons <?php echo esc_attr($cat_info['icono']); ?>" style="color: <?php echo esc_attr($cat_info['color']); ?>;"></span>
                            </div>
                        <?php endif; ?>
                    </div>

                    <div class="flavor-don-info-principal">
                        <div class="flavor-don-badges">
                            <span class="flavor-badge" style="background-color: <?php echo esc_attr($cat_info['color']); ?>;">
                                <?php echo esc_html($cat_info['nombre']); ?>
                            </span>
                            <span class="flavor-badge" style="background-color: <?php echo esc_attr($estado_info['color']); ?>;">
                                <?php echo esc_html($estado_info['nombre']); ?>
                            </span>
                        </div>

                        <h1><?php echo esc_html($don->titulo); ?></h1>

                        <div class="flavor-don-descripcion-completa">
                            <?php echo wp_kses_post(wpautop($don->descripcion)); ?>
                        </div>

                        <?php if (!empty($don->condiciones)): ?>
                            <div class="flavor-don-condiciones">
                                <h3><?php _e('Condiciones', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></h3>
                                <?php echo wp_kses_post(wpautop($don->condiciones)); ?>
                            </div>
                        <?php endif; ?>

                        <?php if ($don->estado === 'disponible' && $usuario_actual && !$es_donante): ?>
                            <div class="flavor-don-accion">
                                <button class="flavor-btn flavor-btn-primary flavor-btn-lg flavor-solicitar-don"
                                        data-don-id="<?php echo esc_attr($don_id); ?>">
                                    <span class="dashicons dashicons-heart"></span>
                                    <?php _e('Me gustaría recibirlo', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                                </button>
                                <p class="flavor-nota"><?php _e('Sin compromiso. Solo expresas tu interés.', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></p>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>

                <aside class="flavor-don-sidebar">
                    <section class="flavor-panel">
                        <h3><?php _e('Ofrecido por', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></h3>
                        <div class="flavor-donante-perfil">
                            <?php echo get_avatar($don->usuario_id, 64); ?>
                            <span class="flavor-donante-nombre"><?php echo esc_html($don->donante_nombre); ?></span>
                        </div>
                    </section>

                    <?php if (!empty($don->ubicacion)): ?>
                        <section class="flavor-panel">
                            <h3><?php _e('Ubicación', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></h3>
                            <p><span class="dashicons dashicons-location"></span> <?php echo esc_html($don->ubicacion); ?></p>
                        </section>
                    <?php endif; ?>

                    <section class="flavor-panel flavor-mensaje-don">
                        <h3><?php _e('Sobre el don', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></h3>
                        <p><?php _e('El don se ofrece sin esperar nada a cambio. El único "pago" es la gratitud y, si quieres, dar también algo a alguien en el futuro.', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></p>
                    </section>
                </aside>
            </div>
        </div>
        <?php
        return ob_get_clean();
    }

    /**
     * Shortcode: Ofrecer don
     */
    public function shortcode_ofrecer($atts) {
        if (!is_user_logged_in()) {
            return '<div class="flavor-alert flavor-alert-warning">' .
                   sprintf(__('<a href="%s">Inicia sesión</a> para ofrecer un don.', FLAVOR_PLATFORM_TEXT_DOMAIN), wp_login_url(flavor_current_request_url())) .
                   '</div>';
        }

        $this->enqueue_assets();

        ob_start();
        ?>
        <div class="flavor-ofrecer-don">
            <h2><?php _e('Ofrecer un Don', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></h2>
            <p class="flavor-intro"><?php _e('Dar por el placer de dar. Sin esperar nada a cambio.', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></p>

            <form id="flavor-form-ofrecer-don" class="flavor-form" enctype="multipart/form-data">
                <?php wp_nonce_field('flavor_don_nonce', 'nonce'); ?>

                <div class="flavor-form-group">
                    <label for="titulo"><?php _e('¿Qué ofreces?', FLAVOR_PLATFORM_TEXT_DOMAIN); ?> *</label>
                    <input type="text" name="titulo" id="titulo" required
                           placeholder="<?php esc_attr_e('Ej: Libros de cocina, clases de guitarra...', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>">
                </div>

                <div class="flavor-form-group">
                    <label for="categoria"><?php _e('Categoría', FLAVOR_PLATFORM_TEXT_DOMAIN); ?> *</label>
                    <div class="flavor-categoria-selector">
                        <?php foreach ($this->categorias as $clave => $cat): ?>
                            <label class="flavor-cat-option">
                                <input type="radio" name="categoria" value="<?php echo esc_attr($clave); ?>" required>
                                <span class="flavor-cat-visual" style="--cat-color: <?php echo esc_attr($cat['color']); ?>;">
                                    <span class="dashicons <?php echo esc_attr($cat['icono']); ?>"></span>
                                    <?php echo esc_html($cat['nombre']); ?>
                                </span>
                            </label>
                        <?php endforeach; ?>
                    </div>
                </div>

                <div class="flavor-form-group">
                    <label for="descripcion"><?php _e('Descripción', FLAVOR_PLATFORM_TEXT_DOMAIN); ?> *</label>
                    <textarea name="descripcion" id="descripcion" rows="5" required
                              placeholder="<?php esc_attr_e('Describe lo que ofreces con detalle...', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>"></textarea>
                </div>

                <div class="flavor-form-group">
                    <label for="condiciones"><?php _e('Condiciones (opcional)', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></label>
                    <textarea name="condiciones" id="condiciones" rows="3"
                              placeholder="<?php esc_attr_e('¿Hay alguna condición? Ej: Recoger en mano, solo fines de semana...', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>"></textarea>
                </div>

                <div class="flavor-form-group">
                    <label for="ubicacion"><?php _e('Ubicación', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></label>
                    <input type="text" name="ubicacion" id="ubicacion"
                           placeholder="<?php esc_attr_e('Barrio o zona', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>">
                </div>

                <div class="flavor-form-group">
                    <label for="imagen"><?php _e('Foto (opcional)', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></label>
                    <input type="file" name="imagen" id="imagen" accept="image/*">
                </div>

                <div class="flavor-form-actions">
                    <button type="submit" class="flavor-btn flavor-btn-primary">
                        <span class="dashicons dashicons-heart"></span>
                        <?php _e('Ofrecer Don', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                    </button>
                    <a href="<?php echo esc_url(Flavor_Platform_Helpers::get_module_url('economia-don')); ?>" class="flavor-btn flavor-btn-outline">
                        <?php _e('Cancelar', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                    </a>
                </div>
            </form>
        </div>
        <?php
        return ob_get_clean();
    }

    /**
     * Shortcode: Mis dones
     */
    public function shortcode_mis_dones($atts) {
        if (!is_user_logged_in()) {
            return '';
        }

        $this->enqueue_assets();
        $usuario_id = get_current_user_id();

        global $wpdb;
        $dones = $wpdb->get_results($wpdb->prepare("
            SELECT d.*,
                   (SELECT COUNT(*) FROM {$this->tabla_solicitudes} WHERE don_id = d.id) as solicitudes
            FROM {$this->tabla_dones} d
            WHERE d.usuario_id = %d
            ORDER BY d.fecha_creacion DESC
        ", $usuario_id));

        ob_start();
        ?>
        <div class="flavor-mis-dones">
            <h2><?php _e('Mis Dones Ofrecidos', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></h2>

            <?php if (empty($dones)): ?>
                <div class="flavor-alert flavor-alert-info">
                    <?php _e('No has ofrecido ningún don todavía.', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                    <a href="<?php echo esc_url(Flavor_Platform_Helpers::get_action_url('economia-don', 'ofrecer')); ?>" class="flavor-btn flavor-btn-sm flavor-btn-primary">
                        <?php _e('Ofrecer mi primer don', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                    </a>
                </div>
            <?php else: ?>
                <div class="flavor-tabla-responsive">
                    <table class="flavor-table">
                        <thead>
                            <tr>
                                <th><?php _e('Don', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></th>
                                <th><?php _e('Categoría', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></th>
                                <th><?php _e('Solicitudes', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></th>
                                <th><?php _e('Estado', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></th>
                                <th><?php _e('Fecha', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($dones as $don):
                                $cat_info = $this->categorias[$don->categoria] ?? ['nombre' => $don->categoria, 'color' => '#6b7280'];
                                $estado_info = $this->estados[$don->estado] ?? ['nombre' => $don->estado, 'color' => '#6b7280'];
                                ?>
                                <tr>
                                    <td>
                                        <a href="<?php echo esc_url(add_query_arg('don_id', $don->id)); ?>">
                                            <?php echo esc_html($don->titulo); ?>
                                        </a>
                                    </td>
                                    <td>
                                        <span class="flavor-badge" style="background-color: <?php echo esc_attr($cat_info['color']); ?>;">
                                            <?php echo esc_html($cat_info['nombre']); ?>
                                        </span>
                                    </td>
                                    <td><?php echo absint($don->solicitudes); ?></td>
                                    <td>
                                        <span class="flavor-badge" style="background-color: <?php echo esc_attr($estado_info['color']); ?>;">
                                            <?php echo esc_html($estado_info['nombre']); ?>
                                        </span>
                                    </td>
                                    <td><?php echo human_time_diff(strtotime($don->fecha_creacion)); ?></td>
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
     * Shortcode: Mis recepciones
     */
    public function shortcode_mis_recepciones($atts) {
        if (!is_user_logged_in()) {
            return '';
        }

        $this->enqueue_assets();
        $usuario_id = get_current_user_id();

        global $wpdb;
        $recepciones = $wpdb->get_results($wpdb->prepare("
            SELECT e.*, d.titulo as don_titulo, u.display_name as donante_nombre
            FROM {$this->tabla_entregas} e
            LEFT JOIN {$this->tabla_dones} d ON e.don_id = d.id
            LEFT JOIN {$wpdb->users} u ON d.usuario_id = u.ID
            WHERE e.receptor_id = %d
            ORDER BY e.fecha_entrega DESC
        ", $usuario_id));

        ob_start();
        ?>
        <div class="flavor-mis-recepciones">
            <h2><?php _e('Dones Recibidos', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></h2>

            <?php if (empty($recepciones)): ?>
                <p class="flavor-no-datos"><?php _e('No has recibido dones todavía.', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></p>
            <?php else: ?>
                <div class="flavor-recepciones-lista">
                    <?php foreach ($recepciones as $recepcion): ?>
                        <div class="flavor-recepcion-item">
                            <div class="flavor-recepcion-info">
                                <h4><?php echo esc_html($recepcion->don_titulo); ?></h4>
                                <p><?php printf(__('De %s', FLAVOR_PLATFORM_TEXT_DOMAIN), esc_html($recepcion->donante_nombre)); ?></p>
                                <span class="flavor-fecha"><?php echo date_i18n(get_option('date_format'), strtotime($recepcion->fecha_entrega)); ?></span>
                            </div>
                            <?php if (empty($recepcion->gratitud_enviada)): ?>
                                <button class="flavor-btn flavor-btn-sm flavor-btn-primary flavor-agradecer"
                                        data-entrega-id="<?php echo esc_attr($recepcion->id); ?>">
                                    <span class="dashicons dashicons-heart"></span>
                                    <?php _e('Agradecer', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                                </button>
                            <?php else: ?>
                                <span class="flavor-badge flavor-badge-success">
                                    <span class="dashicons dashicons-yes"></span>
                                    <?php _e('Agradecido', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                                </span>
                            <?php endif; ?>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
        <?php
        return ob_get_clean();
    }

    /**
     * Shortcode: Muro de gratitud
     */
    public function shortcode_muro_gratitud($atts) {
        $this->enqueue_assets();

        global $wpdb;
        $gratitudes = $wpdb->get_results("
            SELECT g.*, d.titulo as don_titulo,
                   u_receptor.display_name as receptor_nombre,
                   u_donante.display_name as donante_nombre
            FROM {$this->tabla_gratitudes} g
            LEFT JOIN {$this->tabla_dones} d ON g.don_id = d.id
            LEFT JOIN {$wpdb->users} u_receptor ON g.usuario_id = u_receptor.ID
            LEFT JOIN {$wpdb->users} u_donante ON d.usuario_id = u_donante.ID
            WHERE g.publico = 1
            ORDER BY g.fecha DESC
            LIMIT 20
        ");

        ob_start();
        ?>
        <div class="flavor-muro-gratitud">
            <h2><?php _e('Muro de Gratitud', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></h2>
            <p class="flavor-intro"><?php _e('Mensajes de agradecimiento por los dones recibidos.', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></p>

            <?php if (empty($gratitudes)): ?>
                <p class="flavor-no-datos"><?php _e('Aún no hay mensajes de gratitud.', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></p>
            <?php else: ?>
                <div class="flavor-gratitudes-grid">
                    <?php foreach ($gratitudes as $gratitud): ?>
                        <div class="flavor-gratitud-card">
                            <div class="flavor-gratitud-header">
                                <?php echo get_avatar($gratitud->usuario_id, 40); ?>
                                <div class="flavor-gratitud-meta">
                                    <span class="flavor-receptor"><?php echo esc_html($gratitud->receptor_nombre); ?></span>
                                    <span class="flavor-accion"><?php _e('agradeció a', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></span>
                                    <span class="flavor-donante"><?php echo esc_html($gratitud->donante_nombre); ?></span>
                                </div>
                            </div>
                            <p class="flavor-gratitud-mensaje"><?php echo esc_html($gratitud->mensaje); ?></p>
                            <div class="flavor-gratitud-footer">
                                <span class="flavor-don-referencia">
                                    <span class="dashicons dashicons-heart"></span>
                                    <?php echo esc_html($gratitud->don_titulo); ?>
                                </span>
                                <span class="flavor-fecha"><?php echo human_time_diff(strtotime($gratitud->fecha)); ?></span>
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
     * Shortcode: Estadisticas
     */
    public function shortcode_estadisticas($atts) {
        $this->enqueue_assets();

        global $wpdb;

        $stats = [
            'total_dones' => $wpdb->get_var("SELECT COUNT(*) FROM {$this->tabla_dones}"),
            'disponibles' => $wpdb->get_var("SELECT COUNT(*) FROM {$this->tabla_dones} WHERE estado = 'disponible'"),
            'entregados' => $wpdb->get_var("SELECT COUNT(*) FROM {$this->tabla_dones} WHERE estado IN ('entregado', 'recibido')"),
            'donantes' => $wpdb->get_var("SELECT COUNT(DISTINCT usuario_id) FROM {$this->tabla_dones}"),
            'receptores' => $wpdb->get_var("SELECT COUNT(DISTINCT receptor_id) FROM {$this->tabla_entregas}"),
        ];

        $por_categoria = $wpdb->get_results("
            SELECT categoria, COUNT(*) as total
            FROM {$this->tabla_dones}
            GROUP BY categoria
            ORDER BY total DESC
        ");

        ob_start();
        ?>
        <div class="flavor-don-estadisticas">
            <h2><?php _e('Estadísticas del Don', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></h2>

            <div class="flavor-kpi-grid flavor-grid-4">
                <div class="flavor-kpi-card">
                    <div class="flavor-kpi-icono"><span class="dashicons dashicons-heart"></span></div>
                    <div class="flavor-kpi-contenido">
                        <span class="flavor-kpi-valor"><?php echo number_format($stats['total_dones']); ?></span>
                        <span class="flavor-kpi-label"><?php _e('Dones ofrecidos', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></span>
                    </div>
                </div>
                <div class="flavor-kpi-card">
                    <div class="flavor-kpi-icono"><span class="dashicons dashicons-yes-alt"></span></div>
                    <div class="flavor-kpi-contenido">
                        <span class="flavor-kpi-valor"><?php echo number_format($stats['entregados']); ?></span>
                        <span class="flavor-kpi-label"><?php _e('Entregados', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></span>
                    </div>
                </div>
                <div class="flavor-kpi-card">
                    <div class="flavor-kpi-icono"><span class="dashicons dashicons-admin-users"></span></div>
                    <div class="flavor-kpi-contenido">
                        <span class="flavor-kpi-valor"><?php echo number_format($stats['donantes']); ?></span>
                        <span class="flavor-kpi-label"><?php _e('Personas dando', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></span>
                    </div>
                </div>
                <div class="flavor-kpi-card">
                    <div class="flavor-kpi-icono"><span class="dashicons dashicons-groups"></span></div>
                    <div class="flavor-kpi-contenido">
                        <span class="flavor-kpi-valor"><?php echo number_format($stats['receptores']); ?></span>
                        <span class="flavor-kpi-label"><?php _e('Personas recibiendo', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></span>
                    </div>
                </div>
            </div>

            <div class="flavor-panel">
                <h3><?php _e('Por categoría', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></h3>
                <div class="flavor-stats-bars">
                    <?php foreach ($por_categoria as $cat):
                        $cat_info = $this->categorias[$cat->categoria] ?? ['nombre' => $cat->categoria, 'color' => '#6b7280'];
                        $porcentaje = $stats['total_dones'] > 0 ? ($cat->total / $stats['total_dones']) * 100 : 0;
                        ?>
                        <div class="flavor-stat-bar-item">
                            <div class="flavor-stat-bar-label"><?php echo esc_html($cat_info['nombre']); ?></div>
                            <div class="flavor-stat-bar-track">
                                <div class="flavor-stat-bar-fill" style="width: <?php echo esc_attr($porcentaje); ?>%; background-color: <?php echo esc_attr($cat_info['color']); ?>;"></div>
                            </div>
                            <span class="flavor-stat-bar-valor"><?php echo absint($cat->total); ?></span>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
        <?php
        return ob_get_clean();
    }

    // =========================================================
    // DASHBOARD TAB
    // =========================================================

    /**
     * Render del tab principal
     */
    public function render_dashboard_tab() {
        $this->enqueue_assets();
        $usuario_id = get_current_user_id();

        global $wpdb;

        $mis_dones = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM {$this->tabla_dones} WHERE usuario_id = %d",
            $usuario_id
        ));

        $dones_entregados = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM {$this->tabla_dones} WHERE usuario_id = %d AND estado IN ('entregado', 'recibido')",
            $usuario_id
        ));

        $dones_recibidos = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM {$this->tabla_entregas} WHERE receptor_id = %d",
            $usuario_id
        ));

        ?>
        <div class="flavor-dashboard-don">
            <div class="flavor-kpi-grid flavor-grid-3">
                <div class="flavor-kpi-card">
                    <div class="flavor-kpi-icono"><span class="dashicons dashicons-heart"></span></div>
                    <div class="flavor-kpi-contenido">
                        <span class="flavor-kpi-valor"><?php echo absint($mis_dones); ?></span>
                        <span class="flavor-kpi-label"><?php _e('Dones ofrecidos', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></span>
                    </div>
                </div>
                <div class="flavor-kpi-card">
                    <div class="flavor-kpi-icono"><span class="dashicons dashicons-yes-alt"></span></div>
                    <div class="flavor-kpi-contenido">
                        <span class="flavor-kpi-valor"><?php echo absint($dones_entregados); ?></span>
                        <span class="flavor-kpi-label"><?php _e('Entregados', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></span>
                    </div>
                </div>
                <div class="flavor-kpi-card">
                    <div class="flavor-kpi-icono"><span class="dashicons dashicons-gift"></span></div>
                    <div class="flavor-kpi-contenido">
                        <span class="flavor-kpi-valor"><?php echo absint($dones_recibidos); ?></span>
                        <span class="flavor-kpi-label"><?php _e('Recibidos', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></span>
                    </div>
                </div>
            </div>

            <div class="flavor-acciones-rapidas">
                <a href="<?php echo esc_url(Flavor_Platform_Helpers::get_action_url('economia-don', 'ofrecer')); ?>" class="flavor-btn flavor-btn-primary">
                    <span class="dashicons dashicons-heart"></span>
                    <?php _e('Ofrecer un don', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                </a>
            </div>
        </div>
        <?php
    }

    // =========================================================
    // AJAX HANDLERS
    // =========================================================

    /**
     * AJAX: Ofrecer don
     */
    public function ajax_ofrecer() {
        check_ajax_referer('flavor_don_nonce', 'nonce');

        if (!is_user_logged_in()) {
            wp_send_json_error(['message' => __('Debes iniciar sesión.', FLAVOR_PLATFORM_TEXT_DOMAIN)]);
        }

        $titulo = sanitize_text_field($_POST['titulo'] ?? '');
        $categoria = sanitize_text_field($_POST['categoria'] ?? '');
        $descripcion = wp_kses_post($_POST['descripcion'] ?? '');

        if (empty($titulo) || empty($categoria) || empty($descripcion)) {
            wp_send_json_error(['message' => __('Completa los campos requeridos.', FLAVOR_PLATFORM_TEXT_DOMAIN)]);
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
        $resultado = $wpdb->insert($this->tabla_dones, [
            'usuario_id' => get_current_user_id(),
            'titulo' => $titulo,
            'categoria' => $categoria,
            'descripcion' => $descripcion,
            'condiciones' => wp_kses_post($_POST['condiciones'] ?? ''),
            'ubicacion' => sanitize_text_field($_POST['ubicacion'] ?? ''),
            'imagen' => $imagen_url,
            'estado' => 'disponible',
            'fecha_creacion' => current_time('mysql'),
        ]);

        if ($resultado) {
            wp_send_json_success([
                'message' => __('¡Gracias por tu generosidad! Tu don está disponible.', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'don_id' => $wpdb->insert_id,
                'redirect' => Flavor_Platform_Helpers::get_item_url('economia-don', $wpdb->insert_id),
            ]);
        } else {
            wp_send_json_error(['message' => __('Error al publicar.', FLAVOR_PLATFORM_TEXT_DOMAIN)]);
        }
    }

    /**
     * AJAX: Solicitar don
     */
    public function ajax_solicitar() {
        check_ajax_referer('flavor_don_nonce', 'nonce');

        if (!is_user_logged_in()) {
            wp_send_json_error(['message' => __('Debes iniciar sesión.', FLAVOR_PLATFORM_TEXT_DOMAIN)]);
        }

        $don_id = absint($_POST['don_id'] ?? 0);
        $usuario_id = get_current_user_id();

        global $wpdb;

        // Verificar que no sea su propio don
        $don = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$this->tabla_dones} WHERE id = %d",
            $don_id
        ));

        if (!$don || $don->usuario_id == $usuario_id) {
            wp_send_json_error(['message' => __('No puedes solicitar este don.', FLAVOR_PLATFORM_TEXT_DOMAIN)]);
        }

        $resultado = $wpdb->insert($this->tabla_solicitudes, [
            'don_id' => $don_id,
            'usuario_id' => $usuario_id,
            'mensaje' => sanitize_textarea_field($_POST['mensaje'] ?? ''),
            'estado' => 'pendiente',
            'fecha' => current_time('mysql'),
        ]);

        if ($resultado) {
            wp_send_json_success([
                'message' => __('Tu interés ha sido registrado. El donante se pondrá en contacto contigo.', FLAVOR_PLATFORM_TEXT_DOMAIN),
            ]);
        } else {
            wp_send_json_error(['message' => __('Error al solicitar.', FLAVOR_PLATFORM_TEXT_DOMAIN)]);
        }
    }

    /**
     * AJAX: Confirmar entrega
     */
    public function ajax_confirmar_entrega() {
        check_ajax_referer('flavor_don_nonce', 'nonce');

        // Implementar confirmación de entrega
        wp_send_json_success(['message' => __('Entrega confirmada.', FLAVOR_PLATFORM_TEXT_DOMAIN)]);
    }

    /**
     * AJAX: Agradecer
     */
    public function ajax_agradecer() {
        check_ajax_referer('flavor_don_nonce', 'nonce');

        if (!is_user_logged_in()) {
            wp_send_json_error(['message' => __('Debes iniciar sesión.', FLAVOR_PLATFORM_TEXT_DOMAIN)]);
        }

        $entrega_id = absint($_POST['entrega_id'] ?? 0);
        $mensaje = sanitize_textarea_field($_POST['mensaje'] ?? '');

        global $wpdb;

        // Obtener entrega
        $entrega = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$this->tabla_entregas} WHERE id = %d",
            $entrega_id
        ));

        if (!$entrega) {
            wp_send_json_error(['message' => __('Entrega no encontrada.', FLAVOR_PLATFORM_TEXT_DOMAIN)]);
        }

        $wpdb->insert($this->tabla_gratitudes, [
            'don_id' => $entrega->don_id,
            'usuario_id' => get_current_user_id(),
            'mensaje' => $mensaje ?: __('Gracias por tu generosidad.', FLAVOR_PLATFORM_TEXT_DOMAIN),
            'publico' => 1,
            'fecha' => current_time('mysql'),
        ]);

        // Marcar gratitud enviada
        $wpdb->update(
            $this->tabla_entregas,
            ['gratitud_enviada' => 1],
            ['id' => $entrega_id]
        );

        wp_send_json_success(['message' => __('¡Gracias enviadas!', FLAVOR_PLATFORM_TEXT_DOMAIN)]);
    }

    /**
     * AJAX: Obtener dones
     */
    public function ajax_obtener() {
        global $wpdb;

        $categoria = sanitize_text_field($_POST['categoria'] ?? '');
        $where = "estado = 'disponible'";
        if (!empty($categoria)) {
            $where .= $wpdb->prepare(" AND categoria = %s", $categoria);
        }

        $dones = $wpdb->get_results("
            SELECT * FROM {$this->tabla_dones}
            WHERE {$where}
            ORDER BY fecha_creacion DESC
            LIMIT 50
        ");

        wp_send_json_success(['dones' => $dones]);
    }

    // =========================================================
    // HELPERS
    // =========================================================

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
Flavor_Economia_Don_Frontend_Controller::get_instance();

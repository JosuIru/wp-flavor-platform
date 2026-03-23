<?php
/**
 * Controller Frontend para Biblioteca
 *
 * @package FlavorChatIA
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Clase controladora del frontend de Biblioteca
 */
class Flavor_Biblioteca_Frontend_Controller {

    /**
     * Instancia singleton
     */
    private static $instance = null;

    /**
     * Constructor privado (singleton)
     */
    private function __construct() {
        $this->init();
    }

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
     * Inicialización
     */
    private function init() {
        // Registrar assets
        add_action('wp_enqueue_scripts', [$this, 'registrar_assets']);

        // Registrar shortcodes avanzados sin pisar implementaciones previas.
        $shortcodes = [
            'biblioteca_catalogo' => 'shortcode_catalogo',
            'biblioteca_mis_prestamos' => 'shortcode_mis_prestamos',
            'biblioteca_reservas' => 'shortcode_reservas',
            'biblioteca_busqueda' => 'shortcode_busqueda',
            'biblioteca_novedades' => 'shortcode_novedades',
            'biblioteca_prestamos_activos' => 'shortcode_prestamos_activos',
        ];

        foreach ($shortcodes as $tag => $method) {
            if (!shortcode_exists($tag)) {
                add_shortcode($tag, [$this, $method]);
            }
        }

        // AJAX handlers
        add_action('wp_ajax_biblioteca_reservar_libro', [$this, 'ajax_reservar_libro']);
        add_action('wp_ajax_biblioteca_cancelar_reserva', [$this, 'ajax_cancelar_reserva']);
        add_action('wp_ajax_biblioteca_renovar_prestamo', [$this, 'ajax_renovar_prestamo']);
        add_action('wp_ajax_biblioteca_buscar_libros', [$this, 'ajax_buscar_libros']);
        add_action('wp_ajax_nopriv_biblioteca_buscar_libros', [$this, 'ajax_buscar_libros']);

        // Template overrides
        add_filter('template_include', [$this, 'cargar_templates']);

        // Registrar tabs en Mi Portal
        add_filter('flavor_user_dashboard_tabs', [$this, 'registrar_tabs_dashboard']);
    }

    /**
     * Registrar assets del frontend
     */
    public function registrar_assets() {
        $plugin_url = plugins_url('/', dirname(__FILE__));
        $version = defined('FLAVOR_VERSION') ? FLAVOR_VERSION : '1.0.0';

        // CSS base
        wp_register_style(
            'biblioteca-frontend',
            $plugin_url . 'assets/biblioteca-frontend.css',
            [],
            $version
        );

        // JavaScript base
        wp_register_script(
            'biblioteca-frontend',
            $plugin_url . 'assets/biblioteca-frontend.js',
            ['jquery'],
            $version,
            true
        );

        // Configuración global para JavaScript
        $configuracion_js = [
            'ajaxUrl' => admin_url('admin-ajax.php'),
            'restUrl' => rest_url('flavor/v1/biblioteca/'),
            'nonce' => wp_create_nonce('biblioteca_frontend_nonce'),
            'restNonce' => wp_create_nonce('wp_rest'),
            'isLoggedIn' => is_user_logged_in(),
            'loginUrl' => wp_login_url(flavor_current_request_url()),
            'i18n' => [
                'reservaExitosa' => __('Libro reservado correctamente', 'flavor-chat-ia'),
                'reservaCancelada' => __('Reserva cancelada', 'flavor-chat-ia'),
                'prestamoRenovado' => __('Préstamo renovado correctamente', 'flavor-chat-ia'),
                'error' => __('Ha ocurrido un error', 'flavor-chat-ia'),
                'confirmarReserva' => __('¿Confirmar reserva de este libro?', 'flavor-chat-ia'),
                'confirmarCancelar' => __('¿Cancelar esta reserva?', 'flavor-chat-ia'),
                'cargando' => __('Cargando...', 'flavor-chat-ia'),
                'sinResultados' => __('No se encontraron libros', 'flavor-chat-ia'),
            ],
        ];

        wp_localize_script('biblioteca-frontend', 'bibliotecaFrontend', $configuracion_js);
    }

    /**
     * Encolar assets cuando se necesitan
     */
    private function encolar_assets() {
        wp_enqueue_style('biblioteca-frontend');
        wp_enqueue_script('biblioteca-frontend');
    }

    /**
     * Registrar tabs en el dashboard de usuario
     *
     * @param array $tabs Tabs existentes
     * @return array
     */
    public function registrar_tabs_dashboard($tabs) {
        $tabs['biblioteca-prestamos'] = [
            'label' => __('Mis Préstamos', 'flavor-chat-ia'),
            'icon' => 'book',
            'callback' => [$this, 'render_tab_mis_prestamos'],
            'orden' => 40,
            'badge' => $this->contar_prestamos_activos(),
        ];

        $tabs['biblioteca-reservas'] = [
            'label' => __('Mis Reservas', 'flavor-chat-ia'),
            'icon' => 'calendar',
            'callback' => [$this, 'render_tab_reservas'],
            'orden' => 41,
            'badge' => $this->contar_reservas_activas(),
        ];

        return $tabs;
    }

    /**
     * Contar préstamos activos
     */
    private function contar_prestamos_activos() {
        if (!is_user_logged_in()) {
            return 0;
        }

        global $wpdb;
        $tabla = $wpdb->prefix . 'flavor_biblioteca_prestamos';

        if (!$this->tabla_existe($tabla)) {
            return 0;
        }

        return (int) $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM {$tabla} WHERE usuario_id = %d AND estado = 'activo'",
            get_current_user_id()
        ));
    }

    /**
     * Contar reservas activas
     */
    private function contar_reservas_activas() {
        if (!is_user_logged_in()) {
            return 0;
        }

        global $wpdb;
        $tabla = $wpdb->prefix . 'flavor_biblioteca_reservas';

        if (!$this->tabla_existe($tabla)) {
            return 0;
        }

        return (int) $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM {$tabla} WHERE usuario_id = %d AND estado = 'pendiente'",
            get_current_user_id()
        ));
    }

    /**
     * Verificar si una tabla existe
     */
    private function tabla_existe($tabla) {
        global $wpdb;
        return $wpdb->get_var($wpdb->prepare("SHOW TABLES LIKE %s", $tabla)) === $tabla;
    }

    /**
     * Shortcode: Catálogo de libros
     */
    public function shortcode_catalogo($atts) {
        $this->encolar_assets();

        $atributos = shortcode_atts([
            'categoria' => '',
            'columnas' => 4,
            'limite' => 12,
            'mostrar_filtros' => 'si',
            // Parámetros visuales (VBP)
            'esquema_color' => 'default',
            'estilo_tarjeta' => 'elevated',
            'radio_bordes' => 'lg',
            'animacion_entrada' => 'fade',
            'orderby' => 'title',
            'order' => 'ASC',
        ], $atts);

        ob_start();
        $this->render_catalogo($atributos);
        return ob_get_clean();
    }

    /**
     * Renderizar catálogo
     */
    private function render_catalogo($atts) {
        // Generar clases CSS visuales (VBP)
        $visual_classes = [];
        if (!empty($atts['esquema_color']) && $atts['esquema_color'] !== 'default') {
            $visual_classes[] = 'flavor-scheme-' . sanitize_html_class($atts['esquema_color']);
        }
        if (!empty($atts['estilo_tarjeta']) && $atts['estilo_tarjeta'] !== 'elevated') {
            $visual_classes[] = 'flavor-card-' . sanitize_html_class($atts['estilo_tarjeta']);
        }
        if (!empty($atts['radio_bordes']) && $atts['radio_bordes'] !== 'lg') {
            $visual_classes[] = 'flavor-radius-' . sanitize_html_class($atts['radio_bordes']);
        }
        if (!empty($atts['animacion_entrada']) && $atts['animacion_entrada'] !== 'none') {
            $visual_classes[] = 'flavor-animate-' . sanitize_html_class($atts['animacion_entrada']);
        }
        $visual_class_string = implode(' ', $visual_classes);

        // Mapeo de orderby para biblioteca
        $orderby_map = [
            'title' => 'title',
            'date' => 'date',
            'autor' => ['meta_key' => '_biblioteca_autor', 'orderby' => 'meta_value'],
            'disponibilidad' => ['meta_key' => '_biblioteca_disponibles', 'orderby' => 'meta_value_num'],
        ];
        $orderby_config = $orderby_map[$atts['orderby']] ?? ['orderby' => 'title'];
        $order = strtoupper($atts['order']) === 'DESC' ? 'DESC' : 'ASC';

        // Obtener libros
        $args = [
            'post_type' => 'biblioteca_libro',
            'post_status' => 'publish',
            'posts_per_page' => intval($atts['limite']),
            'order' => $order,
        ];

        // Aplicar orderby config
        if (is_array($orderby_config)) {
            if (isset($orderby_config['meta_key'])) {
                $args['meta_key'] = $orderby_config['meta_key'];
            }
            $args['orderby'] = $orderby_config['orderby'] ?? 'title';
        } else {
            $args['orderby'] = $orderby_config;
        }

        if (!empty($atts['categoria'])) {
            $args['tax_query'] = [[
                'taxonomy' => 'biblioteca_categoria',
                'field' => 'slug',
                'terms' => sanitize_text_field($atts['categoria']),
            ]];
        }

        $libros = get_posts($args);

        // Obtener categorías para filtros
        $categorias = get_terms([
            'taxonomy' => 'biblioteca_categoria',
            'hide_empty' => true,
        ]);
        ?>
        <div class="biblioteca-catalogo <?php echo esc_attr($visual_class_string); ?>" data-columnas="<?php echo esc_attr($atts['columnas']); ?>">
            <?php if ($atts['mostrar_filtros'] === 'si'): ?>
                <div class="biblioteca-filtros">
                    <div class="filtro-buscar">
                        <input type="text" id="biblioteca-buscar" placeholder="<?php _e('Buscar por título, autor o ISBN...', 'flavor-chat-ia'); ?>">
                        <span class="filtro-icon dashicons dashicons-search"></span>
                    </div>
                    <div class="filtro-categoria">
                        <select id="biblioteca-filtrar-categoria">
                            <option value=""><?php _e('Todas las categorías', 'flavor-chat-ia'); ?></option>
                            <?php if (!is_wp_error($categorias)): ?>
                                <?php foreach ($categorias as $categoria): ?>
                                    <option value="<?php echo esc_attr($categoria->slug); ?>">
                                        <?php echo esc_html($categoria->name); ?>
                                    </option>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </select>
                    </div>
                    <div class="filtro-disponibilidad">
                        <label>
                            <input type="checkbox" id="biblioteca-solo-disponibles">
                            <?php _e('Solo disponibles', 'flavor-chat-ia'); ?>
                        </label>
                    </div>
                </div>
            <?php endif; ?>

            <div class="biblioteca-grid" id="biblioteca-lista">
                <?php if (empty($libros)): ?>
                    <p class="biblioteca-sin-libros"><?php _e('No hay libros disponibles en este momento.', 'flavor-chat-ia'); ?></p>
                <?php else: ?>
                    <?php foreach ($libros as $libro): ?>
                        <?php $this->render_libro_card($libro); ?>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>
        <?php
    }

    /**
     * Renderizar tarjeta de libro
     */
    private function render_libro_card($libro) {
        $autor = get_post_meta($libro->ID, '_biblioteca_autor', true);
        $isbn = get_post_meta($libro->ID, '_biblioteca_isbn', true);
        $ejemplares_total = (int) get_post_meta($libro->ID, '_biblioteca_ejemplares', true) ?: 1;
        $ejemplares_disponibles = (int) get_post_meta($libro->ID, '_biblioteca_disponibles', true);
        $imagen = get_the_post_thumbnail_url($libro->ID, 'medium');
        $disponible = $ejemplares_disponibles > 0;
        ?>
        <div class="biblioteca-libro-card <?php echo $disponible ? 'disponible' : 'no-disponible'; ?>"
             data-libro-id="<?php echo esc_attr($libro->ID); ?>">

            <div class="libro-imagen">
                <?php if ($imagen): ?>
                    <a href="<?php echo get_permalink($libro->ID); ?>">
                        <img src="<?php echo esc_url($imagen); ?>" alt="<?php echo esc_attr($libro->post_title); ?>">
                    </a>
                <?php else: ?>
                    <div class="libro-sin-imagen">
                        <span class="dashicons dashicons-book-alt"></span>
                    </div>
                <?php endif; ?>
                <span class="libro-disponibilidad-badge <?php echo $disponible ? 'disponible' : 'no-disponible'; ?>">
                    <?php echo $disponible ? __('Disponible', 'flavor-chat-ia') : __('No disponible', 'flavor-chat-ia'); ?>
                </span>
            </div>

            <div class="libro-info">
                <h3 class="libro-titulo">
                    <a href="<?php echo get_permalink($libro->ID); ?>"><?php echo esc_html($libro->post_title); ?></a>
                </h3>
                <?php if ($autor): ?>
                    <p class="libro-autor">
                        <span class="dashicons dashicons-admin-users"></span>
                        <?php echo esc_html($autor); ?>
                    </p>
                <?php endif; ?>
                <?php if ($isbn): ?>
                    <p class="libro-isbn">
                        <span class="dashicons dashicons-book"></span>
                        ISBN: <?php echo esc_html($isbn); ?>
                    </p>
                <?php endif; ?>
                <p class="libro-ejemplares">
                    <span class="dashicons dashicons-admin-page"></span>
                    <?php printf(
                        __('%d de %d ejemplares disponibles', 'flavor-chat-ia'),
                        $ejemplares_disponibles,
                        $ejemplares_total
                    ); ?>
                </p>
            </div>

            <div class="libro-acciones">
                <a href="<?php echo get_permalink($libro->ID); ?>" class="btn-ver-detalle">
                    <?php _e('Ver detalles', 'flavor-chat-ia'); ?>
                </a>
                <?php if (is_user_logged_in() && $disponible): ?>
                    <button type="button" class="btn-reservar" data-libro-id="<?php echo esc_attr($libro->ID); ?>">
                        <?php _e('Reservar', 'flavor-chat-ia'); ?>
                    </button>
                <?php elseif (!$disponible): ?>
                    <button type="button" class="btn-lista-espera" data-libro-id="<?php echo esc_attr($libro->ID); ?>">
                        <?php _e('Lista de espera', 'flavor-chat-ia'); ?>
                    </button>
                <?php endif; ?>
            </div>
        </div>
        <?php
    }

    /**
     * Shortcode: Mis préstamos
     */
    public function shortcode_mis_prestamos($atts) {
        $this->encolar_assets();

        if (!is_user_logged_in()) {
            return '<p class="biblioteca-login-requerido">' . __('Inicia sesión para ver tus préstamos.', 'flavor-chat-ia') . '</p>';
        }

        ob_start();
        $this->render_tab_mis_prestamos();
        return ob_get_clean();
    }

    /**
     * Shortcode: Reservas
     */
    public function shortcode_reservas($atts) {
        $this->encolar_assets();

        if (!is_user_logged_in()) {
            return '<p class="biblioteca-login-requerido">' . __('Inicia sesión para ver tus reservas.', 'flavor-chat-ia') . '</p>';
        }

        ob_start();
        $this->render_tab_reservas();
        return ob_get_clean();
    }

    /**
     * Shortcode: Novedades
     */
    public function shortcode_novedades($atts) {
        $this->encolar_assets();

        $atributos = shortcode_atts([
            'limite' => 6,
        ], $atts);

        $libros = get_posts([
            'post_type' => 'biblioteca_libro',
            'post_status' => 'publish',
            'posts_per_page' => intval($atributos['limite']),
            'orderby' => 'date',
            'order' => 'DESC',
        ]);

        ob_start();
        ?>
        <div class="biblioteca-novedades">
            <h3><?php _e('Últimas adquisiciones', 'flavor-chat-ia'); ?></h3>
            <div class="biblioteca-grid columnas-3">
                <?php foreach ($libros as $libro): ?>
                    <?php $this->render_libro_card($libro); ?>
                <?php endforeach; ?>
            </div>
        </div>
        <?php
        return ob_get_clean();
    }

    /**
     * Shortcode: Búsqueda
     */
    public function shortcode_busqueda($atts) {
        $this->encolar_assets();

        $termino_busqueda = sanitize_text_field($_GET['texto'] ?? '');
        $categoria_filtro = sanitize_text_field($_GET['categoria'] ?? '');
        $solo_disponibles = isset($_GET['disponible']) ? (bool) $_GET['disponible'] : false;

        $libros_encontrados = [];
        $hay_busqueda = !empty($termino_busqueda) || !empty($categoria_filtro);

        if ($hay_busqueda) {
            $libros_encontrados = $this->buscar_libros($termino_busqueda, $categoria_filtro, $solo_disponibles);
        }

        ob_start();
        ?>
        <div class="biblioteca-busqueda-avanzada">
            <form class="busqueda-form" id="biblioteca-busqueda-form" method="get">
                <div class="busqueda-campos">
                    <div class="campo-grupo campo-principal">
                        <input type="text" id="busqueda-texto" name="texto"
                               placeholder="<?php _e('Título, autor, ISBN...', 'flavor-chat-ia'); ?>"
                               value="<?php echo esc_attr($termino_busqueda); ?>">
                    </div>
                    <div class="campo-grupo">
                        <select id="busqueda-categoria" name="categoria">
                            <option value=""><?php _e('Categoría', 'flavor-chat-ia'); ?></option>
                            <?php
                            $categorias = get_terms(['taxonomy' => 'biblioteca_categoria', 'hide_empty' => true]);
                            if (!is_wp_error($categorias)) {
                                foreach ($categorias as $cat) {
                                    echo '<option value="' . esc_attr($cat->slug) . '" ' . selected($categoria_filtro, $cat->slug, false) . '>' . esc_html($cat->name) . '</option>';
                                }
                            }
                            ?>
                        </select>
                    </div>
                    <div class="campo-grupo">
                        <label class="checkbox-label">
                            <input type="checkbox" id="busqueda-disponible" name="disponible" value="1" <?php checked($solo_disponibles); ?>>
                            <?php _e('Solo disponibles', 'flavor-chat-ia'); ?>
                        </label>
                    </div>
                </div>
                <button type="submit" class="btn-buscar">
                    <span class="dashicons dashicons-search"></span>
                    <?php _e('Buscar', 'flavor-chat-ia'); ?>
                </button>
            </form>

            <?php if ($hay_busqueda): ?>
            <div class="busqueda-resultados" id="biblioteca-resultados">
                <p class="resultados-count">
                    <?php printf(
                        _n('%d libro encontrado', '%d libros encontrados', count($libros_encontrados), 'flavor-chat-ia'),
                        count($libros_encontrados)
                    ); ?>
                </p>

                <?php if (!empty($libros_encontrados)): ?>
                <div class="biblioteca-grid">
                    <?php foreach ($libros_encontrados as $libro): ?>
                    <?php
                    $autor_libro = get_post_meta($libro->ID, '_autor', true);
                    $isbn_libro = get_post_meta($libro->ID, '_isbn', true);
                    $disponible = get_post_meta($libro->ID, '_disponible', true) !== 'no';
                    ?>
                    <div class="biblioteca-libro-card <?php echo $disponible ? 'disponible' : 'no-disponible'; ?>">
                        <div class="libro-portada">
                            <?php if (has_post_thumbnail($libro->ID)): ?>
                                <?php echo get_the_post_thumbnail($libro->ID, 'medium'); ?>
                            <?php else: ?>
                                <div class="libro-sin-portada">
                                    <span class="dashicons dashicons-book-alt"></span>
                                </div>
                            <?php endif; ?>
                            <span class="libro-estado-badge <?php echo $disponible ? 'disponible' : 'prestado'; ?>">
                                <?php echo $disponible ? __('Disponible', 'flavor-chat-ia') : __('Prestado', 'flavor-chat-ia'); ?>
                            </span>
                        </div>
                        <div class="libro-info">
                            <h4 class="libro-titulo">
                                <a href="<?php echo get_permalink($libro->ID); ?>"><?php echo esc_html($libro->post_title); ?></a>
                            </h4>
                            <?php if ($autor_libro): ?>
                            <p class="libro-autor"><?php echo esc_html($autor_libro); ?></p>
                            <?php endif; ?>
                            <div class="libro-meta">
                                <?php if ($isbn_libro): ?>
                                <span class="libro-isbn">ISBN: <?php echo esc_html($isbn_libro); ?></span>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
                <?php else: ?>
                <div class="sin-resultados">
                    <span class="dashicons dashicons-book"></span>
                    <p><?php _e('No se encontraron libros con esos criterios.', 'flavor-chat-ia'); ?></p>
                </div>
                <?php endif; ?>
            </div>
            <?php endif; ?>
        </div>
        <?php
        return ob_get_clean();
    }

    /**
     * Busca libros en la biblioteca
     */
    private function buscar_libros($termino = '', $categoria = '', $solo_disponibles = false, $limite = 12) {
        $argumentos_query = [
            'post_type' => 'biblioteca_libro',
            'post_status' => 'publish',
            'posts_per_page' => $limite,
            'orderby' => 'title',
            'order' => 'ASC',
        ];

        if (!empty($termino)) {
            $argumentos_query['s'] = $termino;
        }

        if (!empty($categoria)) {
            $argumentos_query['tax_query'] = [
                [
                    'taxonomy' => 'biblioteca_categoria',
                    'field' => 'slug',
                    'terms' => $categoria,
                ],
            ];
        }

        if ($solo_disponibles) {
            $argumentos_query['meta_query'] = [
                'relation' => 'OR',
                [
                    'key' => '_disponible',
                    'value' => 'no',
                    'compare' => '!=',
                ],
                [
                    'key' => '_disponible',
                    'compare' => 'NOT EXISTS',
                ],
            ];
        }

        $consulta_libros = new WP_Query($argumentos_query);
        return $consulta_libros->posts;
    }

    /**
     * Shortcode: Préstamos activos del usuario (widget compacto)
     * Muestra un resumen de los préstamos activos
     */
    public function shortcode_prestamos_activos($atts) {
        if (!is_user_logged_in()) {
            return '';
        }

        $atts = shortcode_atts([
            'limite' => 3,
            'mostrar_vencidos' => 'true',
        ], $atts);

        global $wpdb;
        $tabla = $wpdb->prefix . 'flavor_biblioteca_prestamos';
        $usuario_id = get_current_user_id();

        if (!$this->tabla_existe($tabla)) {
            return '';
        }

        $limite = intval($atts['limite']);
        $prestamos = $wpdb->get_results($wpdb->prepare(
            "SELECT p.*, l.post_title as libro_titulo
             FROM {$tabla} p
             LEFT JOIN {$wpdb->posts} l ON p.libro_id = l.ID
             WHERE p.usuario_id = %d
             AND p.estado IN ('activo', 'renovado')
             ORDER BY p.fecha_devolucion_prevista ASC
             LIMIT %d",
            $usuario_id,
            $limite
        ));

        if (empty($prestamos)) {
            return '';
        }

        ob_start();
        ?>
        <div class="biblioteca-prestamos-activos">
            <h4><?php esc_html_e('Mis Préstamos', 'flavor-chat-ia'); ?></h4>
            <ul class="lista-prestamos-mini">
                <?php foreach ($prestamos as $prestamo):
                    $vence = strtotime($prestamo->fecha_devolucion_prevista);
                    $hoy = time();
                    $dias_restantes = floor(($vence - $hoy) / DAY_IN_SECONDS);
                    $clase_vencimiento = $dias_restantes < 0 ? 'vencido' : ($dias_restantes <= 3 ? 'proximo' : 'normal');
                ?>
                <li class="prestamo-item vencimiento-<?php echo esc_attr($clase_vencimiento); ?>">
                    <span class="libro-titulo"><?php echo esc_html(wp_trim_words($prestamo->libro_titulo, 5)); ?></span>
                    <span class="prestamo-vence">
                        <?php if ($dias_restantes < 0): ?>
                            <span class="badge-vencido"><?php esc_html_e('Vencido', 'flavor-chat-ia'); ?></span>
                        <?php elseif ($dias_restantes === 0): ?>
                            <?php esc_html_e('Vence hoy', 'flavor-chat-ia'); ?>
                        <?php elseif ($dias_restantes === 1): ?>
                            <?php esc_html_e('Vence mañana', 'flavor-chat-ia'); ?>
                        <?php else: ?>
                            <?php printf(esc_html__('Vence en %d días', 'flavor-chat-ia'), $dias_restantes); ?>
                        <?php endif; ?>
                    </span>
                </li>
                <?php endforeach; ?>
            </ul>
            <a href="<?php echo esc_url(home_url('/mi-portal/biblioteca/mis-prestamos/')); ?>" class="ver-todos-link">
                <?php esc_html_e('Ver todos mis préstamos', 'flavor-chat-ia'); ?>
            </a>
        </div>
        <?php
        return ob_get_clean();
    }

    /**
     * Render tab de Mis Préstamos en dashboard
     */
    public function render_tab_mis_prestamos() {
        global $wpdb;
        $usuario_id = get_current_user_id();
        $tabla = $wpdb->prefix . 'flavor_biblioteca_prestamos';

        $prestamos = [];
        if ($this->tabla_existe($tabla)) {
            $prestamos = $wpdb->get_results($wpdb->prepare(
                "SELECT p.*, l.post_title as libro_titulo
                 FROM {$tabla} p
                 LEFT JOIN {$wpdb->posts} l ON p.libro_id = l.ID
                 WHERE p.usuario_id = %d
                 ORDER BY p.fecha_prestamo DESC",
                $usuario_id
            ));
        }
        ?>
        <div class="biblioteca-dashboard-tab biblioteca-prestamos">
            <div class="tab-header">
                <h2><?php _e('Mis Préstamos', 'flavor-chat-ia'); ?></h2>
            </div>

            <?php if (empty($prestamos)): ?>
                <div class="empty-state">
                    <span class="empty-icon dashicons dashicons-book"></span>
                    <p><?php _e('No tienes préstamos activos.', 'flavor-chat-ia'); ?></p>
                    <a href="<?php echo esc_url(home_url('/mi-portal/biblioteca/')); ?>" class="btn btn-primary">
                        <?php _e('Explorar catálogo', 'flavor-chat-ia'); ?>
                    </a>
                </div>
            <?php else: ?>
                <div class="prestamos-lista">
                    <?php foreach ($prestamos as $prestamo):
                        $imagen = get_the_post_thumbnail_url($prestamo->libro_id, 'thumbnail');
                        $fecha_devolucion = strtotime($prestamo->fecha_devolucion);
                        $dias_restantes = ceil(($fecha_devolucion - time()) / DAY_IN_SECONDS);
                        $vencido = $dias_restantes < 0 && $prestamo->estado === 'activo';
                        $proximo_vencer = $dias_restantes >= 0 && $dias_restantes <= 3 && $prestamo->estado === 'activo';
                    ?>
                        <div class="prestamo-item estado-<?php echo esc_attr($prestamo->estado); ?> <?php echo $vencido ? 'vencido' : ''; ?> <?php echo $proximo_vencer ? 'proximo-vencer' : ''; ?>">
                            <div class="prestamo-imagen">
                                <?php if ($imagen): ?>
                                    <img src="<?php echo esc_url($imagen); ?>" alt="">
                                <?php else: ?>
                                    <span class="sin-imagen dashicons dashicons-book-alt"></span>
                                <?php endif; ?>
                            </div>
                            <div class="prestamo-info">
                                <h4><?php echo esc_html($prestamo->libro_titulo); ?></h4>
                                <p class="prestamo-fechas">
                                    <span class="fecha-prestamo">
                                        <strong><?php _e('Préstamo:', 'flavor-chat-ia'); ?></strong>
                                        <?php echo date_i18n(get_option('date_format'), strtotime($prestamo->fecha_prestamo)); ?>
                                    </span>
                                    <span class="fecha-devolucion">
                                        <strong><?php _e('Devolución:', 'flavor-chat-ia'); ?></strong>
                                        <?php echo date_i18n(get_option('date_format'), $fecha_devolucion); ?>
                                    </span>
                                </p>
                                <?php if ($prestamo->estado === 'activo'): ?>
                                    <?php if ($vencido): ?>
                                        <p class="prestamo-alerta vencido">
                                            <span class="dashicons dashicons-warning"></span>
                                            <?php printf(__('Vencido hace %d días', 'flavor-chat-ia'), abs($dias_restantes)); ?>
                                        </p>
                                    <?php elseif ($proximo_vencer): ?>
                                        <p class="prestamo-alerta proximo">
                                            <span class="dashicons dashicons-clock"></span>
                                            <?php printf(__('Vence en %d días', 'flavor-chat-ia'), $dias_restantes); ?>
                                        </p>
                                    <?php endif; ?>
                                <?php endif; ?>
                            </div>
                            <div class="prestamo-estado">
                                <span class="estado-badge estado-<?php echo esc_attr($prestamo->estado); ?>">
                                    <?php
                                    $estados_label = [
                                        'activo' => __('Activo', 'flavor-chat-ia'),
                                        'devuelto' => __('Devuelto', 'flavor-chat-ia'),
                                        'vencido' => __('Vencido', 'flavor-chat-ia'),
                                    ];
                                    echo esc_html($estados_label[$prestamo->estado] ?? $prestamo->estado);
                                    ?>
                                </span>
                            </div>
                            <?php if ($prestamo->estado === 'activo' && !$vencido): ?>
                                <div class="prestamo-acciones">
                                    <button type="button" class="btn-renovar" data-prestamo-id="<?php echo esc_attr($prestamo->id); ?>">
                                        <span class="dashicons dashicons-update"></span>
                                        <?php _e('Renovar', 'flavor-chat-ia'); ?>
                                    </button>
                                </div>
                            <?php endif; ?>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
        <?php
    }

    /**
     * Render tab de Reservas en dashboard
     */
    public function render_tab_reservas() {
        global $wpdb;
        $usuario_id = get_current_user_id();
        $tabla = $wpdb->prefix . 'flavor_biblioteca_reservas';

        $reservas = [];
        if ($this->tabla_existe($tabla)) {
            $reservas = $wpdb->get_results($wpdb->prepare(
                "SELECT r.*, l.post_title as libro_titulo
                 FROM {$tabla} r
                 LEFT JOIN {$wpdb->posts} l ON r.libro_id = l.ID
                 WHERE r.usuario_id = %d
                 ORDER BY r.fecha_reserva DESC",
                $usuario_id
            ));
        }
        ?>
        <div class="biblioteca-dashboard-tab biblioteca-reservas">
            <div class="tab-header">
                <h2><?php _e('Mis Reservas', 'flavor-chat-ia'); ?></h2>
            </div>

            <?php if (empty($reservas)): ?>
                <div class="empty-state">
                    <span class="empty-icon dashicons dashicons-calendar"></span>
                    <p><?php _e('No tienes reservas activas.', 'flavor-chat-ia'); ?></p>
                    <a href="<?php echo esc_url(home_url('/mi-portal/biblioteca/')); ?>" class="btn btn-primary">
                        <?php _e('Explorar catálogo', 'flavor-chat-ia'); ?>
                    </a>
                </div>
            <?php else: ?>
                <div class="reservas-lista">
                    <?php foreach ($reservas as $reserva):
                        $imagen = get_the_post_thumbnail_url($reserva->libro_id, 'thumbnail');
                    ?>
                        <div class="reserva-item estado-<?php echo esc_attr($reserva->estado); ?>">
                            <div class="reserva-imagen">
                                <?php if ($imagen): ?>
                                    <img src="<?php echo esc_url($imagen); ?>" alt="">
                                <?php else: ?>
                                    <span class="sin-imagen dashicons dashicons-book-alt"></span>
                                <?php endif; ?>
                            </div>
                            <div class="reserva-info">
                                <h4><?php echo esc_html($reserva->libro_titulo); ?></h4>
                                <p class="reserva-fecha">
                                    <strong><?php _e('Reservado:', 'flavor-chat-ia'); ?></strong>
                                    <?php echo date_i18n(get_option('date_format'), strtotime($reserva->fecha_reserva)); ?>
                                </p>
                                <?php if ($reserva->posicion_cola): ?>
                                    <p class="reserva-posicion">
                                        <?php printf(__('Posición en cola: %d', 'flavor-chat-ia'), $reserva->posicion_cola); ?>
                                    </p>
                                <?php endif; ?>
                            </div>
                            <div class="reserva-estado">
                                <span class="estado-badge estado-<?php echo esc_attr($reserva->estado); ?>">
                                    <?php
                                    $estados_label = [
                                        'pendiente' => __('Pendiente', 'flavor-chat-ia'),
                                        'disponible' => __('Disponible', 'flavor-chat-ia'),
                                        'recogido' => __('Recogido', 'flavor-chat-ia'),
                                        'cancelada' => __('Cancelada', 'flavor-chat-ia'),
                                        'expirada' => __('Expirada', 'flavor-chat-ia'),
                                    ];
                                    echo esc_html($estados_label[$reserva->estado] ?? $reserva->estado);
                                    ?>
                                </span>
                            </div>
                            <?php if ($reserva->estado === 'pendiente'): ?>
                                <div class="reserva-acciones">
                                    <button type="button" class="btn-cancelar" data-reserva-id="<?php echo esc_attr($reserva->id); ?>">
                                        <span class="dashicons dashicons-no-alt"></span>
                                        <?php _e('Cancelar', 'flavor-chat-ia'); ?>
                                    </button>
                                </div>
                            <?php endif; ?>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
        <?php
    }

    /**
     * AJAX: Reservar libro
     */
    public function ajax_reservar_libro() {
        check_ajax_referer('biblioteca_frontend_nonce', 'nonce');

        if (!is_user_logged_in()) {
            wp_send_json_error(['message' => __('Debes iniciar sesión', 'flavor-chat-ia')]);
        }

        $libro_id = absint($_POST['libro_id'] ?? 0);
        if (!$libro_id) {
            wp_send_json_error(['message' => __('Libro no válido', 'flavor-chat-ia')]);
        }

        global $wpdb;
        $tabla = $wpdb->prefix . 'flavor_biblioteca_reservas';

        // Verificar si ya tiene reserva
        $existente = $wpdb->get_var($wpdb->prepare(
            "SELECT id FROM {$tabla} WHERE usuario_id = %d AND libro_id = %d AND estado IN ('pendiente', 'disponible')",
            get_current_user_id(),
            $libro_id
        ));

        if ($existente) {
            wp_send_json_error(['message' => __('Ya tienes una reserva para este libro', 'flavor-chat-ia')]);
        }

        // Calcular posición en cola
        $posicion = $wpdb->get_var($wpdb->prepare(
            "SELECT COALESCE(MAX(posicion_cola), 0) + 1 FROM {$tabla} WHERE libro_id = %d AND estado = 'pendiente'",
            $libro_id
        ));

        // Crear reserva
        $resultado = $wpdb->insert($tabla, [
            'usuario_id' => get_current_user_id(),
            'libro_id' => $libro_id,
            'fecha_reserva' => current_time('mysql'),
            'estado' => 'pendiente',
            'posicion_cola' => $posicion,
        ]);

        if ($resultado === false) {
            wp_send_json_error(['message' => __('Error al crear la reserva', 'flavor-chat-ia')]);
        }

        wp_send_json_success(['message' => __('Libro reservado correctamente', 'flavor-chat-ia'), 'posicion' => $posicion]);
    }

    /**
     * AJAX: Cancelar reserva
     */
    public function ajax_cancelar_reserva() {
        check_ajax_referer('biblioteca_frontend_nonce', 'nonce');

        if (!is_user_logged_in()) {
            wp_send_json_error(['message' => __('Debes iniciar sesión', 'flavor-chat-ia')]);
        }

        $reserva_id = absint($_POST['reserva_id'] ?? 0);

        global $wpdb;
        $tabla = $wpdb->prefix . 'flavor_biblioteca_reservas';

        $resultado = $wpdb->update(
            $tabla,
            ['estado' => 'cancelada'],
            ['id' => $reserva_id, 'usuario_id' => get_current_user_id()]
        );

        if ($resultado === false) {
            wp_send_json_error(['message' => __('Error al cancelar la reserva', 'flavor-chat-ia')]);
        }

        wp_send_json_success(['message' => __('Reserva cancelada', 'flavor-chat-ia')]);
    }

    /**
     * AJAX: Renovar préstamo
     */
    public function ajax_renovar_prestamo() {
        check_ajax_referer('biblioteca_frontend_nonce', 'nonce');

        if (!is_user_logged_in()) {
            wp_send_json_error(['message' => __('Debes iniciar sesión', 'flavor-chat-ia')]);
        }

        $prestamo_id = absint($_POST['prestamo_id'] ?? 0);

        global $wpdb;
        $tabla = $wpdb->prefix . 'flavor_biblioteca_prestamos';

        // Obtener préstamo
        $prestamo = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$tabla} WHERE id = %d AND usuario_id = %d AND estado = 'activo'",
            $prestamo_id,
            get_current_user_id()
        ));

        if (!$prestamo) {
            wp_send_json_error(['message' => __('Préstamo no encontrado', 'flavor-chat-ia')]);
        }

        // Verificar máximo de renovaciones
        $max_renovaciones = 2;
        if ($prestamo->renovaciones >= $max_renovaciones) {
            wp_send_json_error(['message' => __('Has alcanzado el máximo de renovaciones', 'flavor-chat-ia')]);
        }

        // Renovar (extender 14 días)
        $nueva_fecha = date('Y-m-d', strtotime($prestamo->fecha_devolucion . ' +14 days'));

        $wpdb->update(
            $tabla,
            [
                'fecha_devolucion' => $nueva_fecha,
                'renovaciones' => $prestamo->renovaciones + 1,
            ],
            ['id' => $prestamo_id]
        );

        wp_send_json_success([
            'message' => __('Préstamo renovado correctamente', 'flavor-chat-ia'),
            'nueva_fecha' => date_i18n(get_option('date_format'), strtotime($nueva_fecha)),
        ]);
    }

    /**
     * AJAX: Buscar libros
     */
    public function ajax_buscar_libros() {
        check_ajax_referer('biblioteca_frontend_nonce', 'nonce');

        $texto = sanitize_text_field($_POST['texto'] ?? '');
        $categoria = sanitize_text_field($_POST['categoria'] ?? '');
        $solo_disponibles = !empty($_POST['disponible']);

        $args = [
            'post_type' => 'biblioteca_libro',
            'post_status' => 'publish',
            'posts_per_page' => 20,
        ];

        if (!empty($texto)) {
            $args['s'] = $texto;
        }

        if (!empty($categoria)) {
            $args['tax_query'] = [[
                'taxonomy' => 'biblioteca_categoria',
                'field' => 'slug',
                'terms' => $categoria,
            ]];
        }

        if ($solo_disponibles) {
            $args['meta_query'][] = [
                'key' => '_biblioteca_disponibles',
                'value' => 0,
                'compare' => '>',
                'type' => 'NUMERIC',
            ];
        }

        $libros = get_posts($args);

        ob_start();
        if (empty($libros)) {
            echo '<p class="biblioteca-sin-libros">' . __('No se encontraron libros.', 'flavor-chat-ia') . '</p>';
        } else {
            echo '<div class="biblioteca-grid columnas-4">';
            foreach ($libros as $libro) {
                $this->render_libro_card($libro);
            }
            echo '</div>';
        }
        $html = ob_get_clean();

        wp_send_json_success(['html' => $html, 'count' => count($libros)]);
    }

    /**
     * Cargar templates personalizados
     */
    public function cargar_templates($template) {
        $plugin_templates_path = dirname(dirname(__FILE__)) . '/frontend/';

        // Template para single biblioteca_libro
        if (is_singular('biblioteca_libro')) {
            $custom_theme = locate_template('biblioteca/single-biblioteca_libro.php');
            if ($custom_theme) {
                return $custom_theme;
            }

            $plugin_template = $plugin_templates_path . 'single.php';
            if (file_exists($plugin_template)) {
                return $plugin_template;
            }
        }

        // Template para archive biblioteca_libro
        if (is_post_type_archive('biblioteca_libro')) {
            $custom_theme = locate_template('biblioteca/archive-biblioteca_libro.php');
            if ($custom_theme) {
                return $custom_theme;
            }

            $plugin_template = $plugin_templates_path . 'archive.php';
            if (file_exists($plugin_template)) {
                return $plugin_template;
            }
        }

        return $template;
    }
}

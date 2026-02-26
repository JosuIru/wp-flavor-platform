<?php
/**
 * Controller Frontend para Cursos
 *
 * @package FlavorChatIA
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Clase controladora del frontend de Cursos
 */
class Flavor_Cursos_Frontend_Controller {

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

        // Registrar shortcodes avanzados
        add_shortcode('cursos_catalogo', [$this, 'shortcode_catalogo']);
        add_shortcode('cursos_mis_inscripciones', [$this, 'shortcode_mis_inscripciones']);
        add_shortcode('cursos_mis_cursos', [$this, 'shortcode_mis_inscripciones']); // Alias para compatibilidad
        add_shortcode('cursos_calendario', [$this, 'shortcode_calendario']);
        add_shortcode('cursos_destacados', [$this, 'shortcode_destacados']);
        add_shortcode('cursos_busqueda', [$this, 'shortcode_busqueda']);
        add_shortcode('cursos_aula', [$this, 'shortcode_aula']);
        add_shortcode('cursos_mi_progreso', [$this, 'shortcode_mi_progreso']);

        // AJAX handlers
        add_action('wp_ajax_cursos_inscribirse', [$this, 'ajax_inscribirse']);
        add_action('wp_ajax_cursos_cancelar_inscripcion', [$this, 'ajax_cancelar_inscripcion']);
        add_action('wp_ajax_cursos_filtrar', [$this, 'ajax_filtrar']);
        add_action('wp_ajax_nopriv_cursos_filtrar', [$this, 'ajax_filtrar']);
        add_action('wp_ajax_cursos_completar_leccion', [$this, 'ajax_completar_leccion']);

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
            'cursos-frontend',
            $plugin_url . 'assets/cursos-frontend.css',
            [],
            $version
        );

        // JavaScript base
        wp_register_script(
            'cursos-frontend',
            $plugin_url . 'assets/cursos-frontend.js',
            ['jquery'],
            $version,
            true
        );

        // Configuración global para JavaScript
        $configuracion_js = [
            'ajaxUrl' => admin_url('admin-ajax.php'),
            'restUrl' => rest_url('flavor/v1/cursos/'),
            'nonce' => wp_create_nonce('cursos_frontend_nonce'),
            'restNonce' => wp_create_nonce('wp_rest'),
            'isLoggedIn' => is_user_logged_in(),
            'loginUrl' => wp_login_url(get_permalink()),
            'i18n' => [
                'inscripcionExitosa' => __('Inscripción realizada correctamente', 'flavor-chat-ia'),
                'inscripcionCancelada' => __('Inscripción cancelada', 'flavor-chat-ia'),
                'error' => __('Ha ocurrido un error', 'flavor-chat-ia'),
                'confirmarInscripcion' => __('¿Confirmar inscripción a este curso?', 'flavor-chat-ia'),
                'confirmarCancelar' => __('¿Cancelar tu inscripción?', 'flavor-chat-ia'),
                'cargando' => __('Cargando...', 'flavor-chat-ia'),
                'sinResultados' => __('No se encontraron cursos', 'flavor-chat-ia'),
                'plazasAgotadas' => __('Plazas agotadas', 'flavor-chat-ia'),
            ],
        ];

        wp_localize_script('cursos-frontend', 'cursosFrontend', $configuracion_js);
    }

    /**
     * Encolar assets cuando se necesitan
     */
    private function encolar_assets() {
        wp_enqueue_style('cursos-frontend');
        wp_enqueue_script('cursos-frontend');
    }

    /**
     * Registrar tabs en el dashboard de usuario
     *
     * @param array $tabs Tabs existentes
     * @return array
     */
    public function registrar_tabs_dashboard($tabs) {
        $tabs['cursos-inscripciones'] = [
            'label' => __('Mis Cursos', 'flavor-chat-ia'),
            'icon' => 'welcome-learn-more',
            'callback' => [$this, 'render_tab_mis_inscripciones'],
            'orden' => 45,
            'badge' => $this->contar_inscripciones_activas(),
        ];

        return $tabs;
    }

    /**
     * Contar inscripciones activas
     */
    private function contar_inscripciones_activas() {
        if (!is_user_logged_in()) {
            return 0;
        }

        global $wpdb;
        $tabla = $wpdb->prefix . 'flavor_cursos_inscripciones';

        if (!$this->tabla_existe($tabla)) {
            return 0;
        }

        return (int) $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM {$tabla} WHERE alumno_id = %d AND estado IN ('confirmada', 'en_curso')",
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
     * Shortcode: Catálogo de cursos
     */
    public function shortcode_catalogo($atts) {
        $this->encolar_assets();

        $atributos = shortcode_atts([
            'categoria' => '',
            'modalidad' => '', // presencial, online, hibrido
            'columnas' => 3,
            'limite' => 12,
            'mostrar_filtros' => 'si',
        ], $atts);

        ob_start();
        $this->render_catalogo($atributos);
        return ob_get_clean();
    }

    /**
     * Renderizar catálogo
     */
    private function render_catalogo($atts) {
        // Obtener cursos
        $args = [
            'post_type' => 'curso',
            'post_status' => 'publish',
            'posts_per_page' => intval($atts['limite']),
            'meta_key' => '_curso_fecha_inicio',
            'orderby' => 'meta_value',
            'order' => 'ASC',
            'meta_query' => [
                [
                    'key' => '_curso_fecha_inicio',
                    'value' => date('Y-m-d'),
                    'compare' => '>=',
                    'type' => 'DATE',
                ],
            ],
        ];

        if (!empty($atts['categoria'])) {
            $args['tax_query'] = [[
                'taxonomy' => 'curso_categoria',
                'field' => 'slug',
                'terms' => sanitize_text_field($atts['categoria']),
            ]];
        }

        if (!empty($atts['modalidad'])) {
            $args['meta_query'][] = [
                'key' => '_curso_modalidad',
                'value' => sanitize_text_field($atts['modalidad']),
            ];
        }

        $cursos = get_posts($args);

        // Obtener categorías para filtros
        $categorias = get_terms([
            'taxonomy' => 'curso_categoria',
            'hide_empty' => true,
        ]);

        $modalidades = [
            'presencial' => __('Presencial', 'flavor-chat-ia'),
            'online' => __('Online', 'flavor-chat-ia'),
            'hibrido' => __('Híbrido', 'flavor-chat-ia'),
        ];
        ?>
        <div class="cursos-catalogo" data-columnas="<?php echo esc_attr($atts['columnas']); ?>">
            <?php if ($atts['mostrar_filtros'] === 'si'): ?>
                <div class="cursos-filtros">
                    <div class="filtro-buscar">
                        <input type="text" id="cursos-buscar" placeholder="<?php _e('Buscar cursos...', 'flavor-chat-ia'); ?>">
                        <span class="filtro-icon dashicons dashicons-search"></span>
                    </div>
                    <div class="filtro-categoria">
                        <select id="cursos-filtrar-categoria">
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
                    <div class="filtro-modalidad">
                        <select id="cursos-filtrar-modalidad">
                            <option value=""><?php _e('Todas las modalidades', 'flavor-chat-ia'); ?></option>
                            <?php foreach ($modalidades as $key => $label): ?>
                                <option value="<?php echo esc_attr($key); ?>"><?php echo esc_html($label); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
            <?php endif; ?>

            <div class="cursos-grid" id="cursos-lista">
                <?php if (empty($cursos)): ?>
                    <p class="cursos-sin-resultados"><?php _e('No hay cursos disponibles en este momento.', 'flavor-chat-ia'); ?></p>
                <?php else: ?>
                    <?php foreach ($cursos as $curso): ?>
                        <?php $this->render_curso_card($curso); ?>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>
        <?php
    }

    /**
     * Renderizar tarjeta de curso
     */
    private function render_curso_card($curso) {
        $fecha_inicio = get_post_meta($curso->ID, '_curso_fecha_inicio', true);
        $fecha_fin = get_post_meta($curso->ID, '_curso_fecha_fin', true);
        $modalidad = get_post_meta($curso->ID, '_curso_modalidad', true) ?: 'presencial';
        $precio = get_post_meta($curso->ID, '_curso_precio', true);
        $plazas_total = (int) get_post_meta($curso->ID, '_curso_plazas', true);
        $plazas_ocupadas = (int) get_post_meta($curso->ID, '_curso_plazas_ocupadas', true);
        $plazas_disponibles = $plazas_total - $plazas_ocupadas;
        $duracion = get_post_meta($curso->ID, '_curso_duracion', true);
        $instructor = get_post_meta($curso->ID, '_curso_instructor', true);
        $imagen = get_the_post_thumbnail_url($curso->ID, 'medium');
        $hay_plazas = $plazas_disponibles > 0;

        $modalidades_label = [
            'presencial' => __('Presencial', 'flavor-chat-ia'),
            'online' => __('Online', 'flavor-chat-ia'),
            'hibrido' => __('Híbrido', 'flavor-chat-ia'),
        ];
        ?>
        <div class="cursos-curso-card <?php echo $hay_plazas ? 'hay-plazas' : 'sin-plazas'; ?>"
             data-curso-id="<?php echo esc_attr($curso->ID); ?>">

            <div class="curso-imagen">
                <?php if ($imagen): ?>
                    <a href="<?php echo get_permalink($curso->ID); ?>">
                        <img src="<?php echo esc_url($imagen); ?>" alt="<?php echo esc_attr($curso->post_title); ?>">
                    </a>
                <?php else: ?>
                    <div class="curso-sin-imagen">
                        <span class="dashicons dashicons-welcome-learn-more"></span>
                    </div>
                <?php endif; ?>
                <span class="curso-modalidad-badge modalidad-<?php echo esc_attr($modalidad); ?>">
                    <?php echo esc_html($modalidades_label[$modalidad] ?? $modalidad); ?>
                </span>
                <?php if (!$hay_plazas): ?>
                    <span class="curso-agotado-badge"><?php _e('Completo', 'flavor-chat-ia'); ?></span>
                <?php endif; ?>
            </div>

            <div class="curso-info">
                <h3 class="curso-titulo">
                    <a href="<?php echo get_permalink($curso->ID); ?>"><?php echo esc_html($curso->post_title); ?></a>
                </h3>
                <?php if ($fecha_inicio): ?>
                    <p class="curso-fechas">
                        <span class="dashicons dashicons-calendar-alt"></span>
                        <?php
                        echo date_i18n('j M Y', strtotime($fecha_inicio));
                        if ($fecha_fin && $fecha_fin !== $fecha_inicio) {
                            echo ' - ' . date_i18n('j M Y', strtotime($fecha_fin));
                        }
                        ?>
                    </p>
                <?php endif; ?>
                <?php if ($duracion): ?>
                    <p class="curso-duracion">
                        <span class="dashicons dashicons-clock"></span>
                        <?php echo esc_html($duracion); ?>
                    </p>
                <?php endif; ?>
                <?php if ($instructor): ?>
                    <p class="curso-instructor">
                        <span class="dashicons dashicons-admin-users"></span>
                        <?php echo esc_html($instructor); ?>
                    </p>
                <?php endif; ?>
                <div class="curso-footer">
                    <p class="curso-precio">
                        <?php if ($precio && floatval($precio) > 0): ?>
                            <span class="precio-valor"><?php echo number_format($precio, 2, ',', '.'); ?>€</span>
                        <?php else: ?>
                            <span class="precio-gratis"><?php _e('Gratuito', 'flavor-chat-ia'); ?></span>
                        <?php endif; ?>
                    </p>
                    <p class="curso-plazas <?php echo $plazas_disponibles <= 3 ? 'pocas-plazas' : ''; ?>">
                        <?php if ($hay_plazas): ?>
                            <?php printf(__('%d plazas disponibles', 'flavor-chat-ia'), $plazas_disponibles); ?>
                        <?php else: ?>
                            <?php _e('Sin plazas', 'flavor-chat-ia'); ?>
                        <?php endif; ?>
                    </p>
                </div>
            </div>

            <div class="curso-acciones">
                <a href="<?php echo get_permalink($curso->ID); ?>" class="btn-ver-detalle">
                    <?php _e('Ver detalles', 'flavor-chat-ia'); ?>
                </a>
                <?php if (is_user_logged_in() && $hay_plazas): ?>
                    <button type="button" class="btn-inscribirse" data-curso-id="<?php echo esc_attr($curso->ID); ?>">
                        <?php _e('Inscribirse', 'flavor-chat-ia'); ?>
                    </button>
                <?php elseif (!$hay_plazas): ?>
                    <button type="button" class="btn-lista-espera" data-curso-id="<?php echo esc_attr($curso->ID); ?>">
                        <?php _e('Lista de espera', 'flavor-chat-ia'); ?>
                    </button>
                <?php endif; ?>
            </div>
        </div>
        <?php
    }

    /**
     * Shortcode: Mis inscripciones
     */
    public function shortcode_mis_inscripciones($atts) {
        $this->encolar_assets();

        if (!is_user_logged_in()) {
            return '<p class="cursos-login-requerido">' . __('Inicia sesión para ver tus inscripciones.', 'flavor-chat-ia') . '</p>';
        }

        ob_start();
        $this->render_tab_mis_inscripciones();
        return ob_get_clean();
    }

    /**
     * Shortcode: Calendario de cursos
     */
    public function shortcode_calendario($atts) {
        $this->encolar_assets();

        $atributos = shortcode_atts([
            'meses' => 3,
        ], $atts);

        $cursos = get_posts([
            'post_type' => 'curso',
            'post_status' => 'publish',
            'posts_per_page' => -1,
            'meta_key' => '_curso_fecha_inicio',
            'orderby' => 'meta_value',
            'order' => 'ASC',
            'meta_query' => [
                [
                    'key' => '_curso_fecha_inicio',
                    'value' => date('Y-m-d'),
                    'compare' => '>=',
                    'type' => 'DATE',
                ],
                [
                    'key' => '_curso_fecha_inicio',
                    'value' => date('Y-m-d', strtotime('+' . $atributos['meses'] . ' months')),
                    'compare' => '<=',
                    'type' => 'DATE',
                ],
            ],
        ]);

        ob_start();
        ?>
        <div class="cursos-calendario">
            <h3><?php _e('Próximos cursos', 'flavor-chat-ia'); ?></h3>
            <?php if (empty($cursos)): ?>
                <p class="sin-cursos"><?php _e('No hay cursos programados próximamente.', 'flavor-chat-ia'); ?></p>
            <?php else: ?>
                <div class="calendario-lista">
                    <?php foreach ($cursos as $curso):
                        $fecha_inicio = get_post_meta($curso->ID, '_curso_fecha_inicio', true);
                        $modalidad = get_post_meta($curso->ID, '_curso_modalidad', true) ?: 'presencial';
                    ?>
                        <div class="calendario-item">
                            <div class="calendario-fecha">
                                <span class="fecha-dia"><?php echo date_i18n('j', strtotime($fecha_inicio)); ?></span>
                                <span class="fecha-mes"><?php echo date_i18n('M', strtotime($fecha_inicio)); ?></span>
                            </div>
                            <div class="calendario-info">
                                <h4><a href="<?php echo get_permalink($curso->ID); ?>"><?php echo esc_html($curso->post_title); ?></a></h4>
                                <span class="modalidad-badge"><?php echo esc_html(ucfirst($modalidad)); ?></span>
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
     * Shortcode: Cursos destacados
     */
    public function shortcode_destacados($atts) {
        $this->encolar_assets();

        $atributos = shortcode_atts([
            'limite' => 4,
        ], $atts);

        $cursos = get_posts([
            'post_type' => 'curso',
            'post_status' => 'publish',
            'posts_per_page' => intval($atributos['limite']),
            'meta_query' => [
                [
                    'key' => '_curso_destacado',
                    'value' => '1',
                ],
                [
                    'key' => '_curso_fecha_inicio',
                    'value' => date('Y-m-d'),
                    'compare' => '>=',
                    'type' => 'DATE',
                ],
            ],
        ]);

        ob_start();
        ?>
        <div class="cursos-destacados">
            <h3><?php _e('Cursos destacados', 'flavor-chat-ia'); ?></h3>
            <div class="cursos-grid columnas-4">
                <?php foreach ($cursos as $curso): ?>
                    <?php $this->render_curso_card($curso); ?>
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
        $modalidad_filtro = sanitize_text_field($_GET['modalidad'] ?? '');

        $cursos_encontrados = [];

        if (!empty($termino_busqueda) || !empty($categoria_filtro) || !empty($modalidad_filtro)) {
            $cursos_encontrados = $this->buscar_cursos($termino_busqueda, $categoria_filtro, $modalidad_filtro);
        }

        ob_start();
        ?>
        <div class="cursos-busqueda-avanzada">
            <form class="busqueda-form" id="cursos-busqueda-form" method="get">
                <div class="busqueda-campos">
                    <div class="campo-grupo campo-principal">
                        <input type="text" id="busqueda-texto" name="texto"
                               placeholder="<?php _e('Buscar cursos...', 'flavor-chat-ia'); ?>"
                               value="<?php echo esc_attr($termino_busqueda); ?>">
                    </div>
                    <div class="campo-grupo">
                        <select id="busqueda-categoria" name="categoria">
                            <option value=""><?php _e('Categoría', 'flavor-chat-ia'); ?></option>
                            <?php
                            $categorias = get_terms(['taxonomy' => 'curso_categoria', 'hide_empty' => true]);
                            if (!is_wp_error($categorias)) {
                                foreach ($categorias as $cat) {
                                    echo '<option value="' . esc_attr($cat->slug) . '" ' . selected($categoria_filtro, $cat->slug, false) . '>' . esc_html($cat->name) . '</option>';
                                }
                            }
                            ?>
                        </select>
                    </div>
                    <div class="campo-grupo">
                        <select id="busqueda-modalidad" name="modalidad">
                            <option value=""><?php _e('Modalidad', 'flavor-chat-ia'); ?></option>
                            <option value="presencial" <?php selected($modalidad_filtro, 'presencial'); ?>><?php _e('Presencial', 'flavor-chat-ia'); ?></option>
                            <option value="online" <?php selected($modalidad_filtro, 'online'); ?>><?php _e('Online', 'flavor-chat-ia'); ?></option>
                            <option value="hibrido" <?php selected($modalidad_filtro, 'hibrido'); ?>><?php _e('Híbrido', 'flavor-chat-ia'); ?></option>
                        </select>
                    </div>
                </div>
                <button type="submit" class="btn-buscar">
                    <span class="dashicons dashicons-search"></span>
                    <?php _e('Buscar', 'flavor-chat-ia'); ?>
                </button>
            </form>

            <?php if (!empty($termino_busqueda) || !empty($categoria_filtro) || !empty($modalidad_filtro)): ?>
            <div class="busqueda-resultados" id="cursos-resultados">
                <p class="resultados-count">
                    <?php printf(
                        _n('%d curso encontrado', '%d cursos encontrados', count($cursos_encontrados), 'flavor-chat-ia'),
                        count($cursos_encontrados)
                    ); ?>
                </p>

                <?php if (!empty($cursos_encontrados)): ?>
                <div class="cursos-grid">
                    <?php foreach ($cursos_encontrados as $curso): ?>
                    <div class="curso-card">
                        <?php if (has_post_thumbnail($curso->ID)): ?>
                        <div class="curso-card-imagen">
                            <?php echo get_the_post_thumbnail($curso->ID, 'medium'); ?>
                        </div>
                        <?php endif; ?>
                        <div class="curso-card-content">
                            <h4 class="curso-titulo">
                                <a href="<?php echo get_permalink($curso->ID); ?>"><?php echo esc_html($curso->post_title); ?></a>
                            </h4>
                            <p class="curso-extracto"><?php echo esc_html(wp_trim_words($curso->post_excerpt ?: $curso->post_content, 15)); ?></p>
                            <div class="curso-meta">
                                <?php
                                $modalidad_curso = get_post_meta($curso->ID, '_modalidad', true);
                                $precio_curso = get_post_meta($curso->ID, '_precio', true);
                                $plazas_disponibles = get_post_meta($curso->ID, '_plazas_disponibles', true);
                                ?>
                                <?php if ($modalidad_curso): ?>
                                <span class="curso-modalidad badge-<?php echo esc_attr($modalidad_curso); ?>">
                                    <?php echo esc_html(ucfirst($modalidad_curso)); ?>
                                </span>
                                <?php endif; ?>
                                <?php if ($precio_curso): ?>
                                <span class="curso-precio">
                                    <?php echo $precio_curso > 0 ? esc_html(number_format_i18n($precio_curso, 2) . '€') : __('Gratuito', 'flavor-chat-ia'); ?>
                                </span>
                                <?php endif; ?>
                                <?php if ($plazas_disponibles): ?>
                                <span class="curso-plazas">
                                    <span class="dashicons dashicons-groups"></span>
                                    <?php printf(__('%d plazas', 'flavor-chat-ia'), intval($plazas_disponibles)); ?>
                                </span>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
                <?php else: ?>
                <div class="sin-resultados">
                    <span class="dashicons dashicons-welcome-learn-more"></span>
                    <p><?php _e('No se encontraron cursos con esos criterios.', 'flavor-chat-ia'); ?></p>
                </div>
                <?php endif; ?>
            </div>
            <?php endif; ?>
        </div>
        <?php
        return ob_get_clean();
    }

    /**
     * Busca cursos por término, categoría y modalidad
     */
    private function buscar_cursos($termino = '', $categoria = '', $modalidad = '', $limite = 12) {
        $argumentos_query = [
            'post_type' => 'curso',
            'post_status' => 'publish',
            'posts_per_page' => $limite,
            'orderby' => 'date',
            'order' => 'DESC',
        ];

        if (!empty($termino)) {
            $argumentos_query['s'] = $termino;
        }

        if (!empty($categoria)) {
            $argumentos_query['tax_query'] = [
                [
                    'taxonomy' => 'curso_categoria',
                    'field' => 'slug',
                    'terms' => $categoria,
                ],
            ];
        }

        if (!empty($modalidad)) {
            $argumentos_query['meta_query'] = [
                [
                    'key' => '_modalidad',
                    'value' => $modalidad,
                    'compare' => '=',
                ],
            ];
        }

        $consulta_cursos = new WP_Query($argumentos_query);
        return $consulta_cursos->posts;
    }

    /**
     * Render tab de Mis Inscripciones en dashboard
     */
    public function render_tab_mis_inscripciones() {
        global $wpdb;
        $usuario_id = get_current_user_id();
        $tabla = $wpdb->prefix . 'flavor_cursos_inscripciones';

        $inscripciones = [];
        if ($this->tabla_existe($tabla)) {
            $inscripciones = $wpdb->get_results($wpdb->prepare(
                "SELECT i.*, c.post_title as curso_titulo
                 FROM {$tabla} i
                 LEFT JOIN {$wpdb->posts} c ON i.curso_id = c.ID
                 WHERE i.alumno_id = %d
                 ORDER BY i.fecha_inscripcion DESC",
                $usuario_id
            ));
        }
        ?>
        <div class="cursos-dashboard-tab cursos-inscripciones">
            <div class="tab-header">
                <h2><?php _e('Mis Cursos', 'flavor-chat-ia'); ?></h2>
            </div>

            <?php if (empty($inscripciones)): ?>
                <div class="empty-state">
                    <span class="empty-icon dashicons dashicons-welcome-learn-more"></span>
                    <p><?php _e('No estás inscrito en ningún curso.', 'flavor-chat-ia'); ?></p>
                    <a href="<?php echo esc_url(home_url('/mi-portal/cursos/')); ?>" class="btn btn-primary">
                        <?php _e('Explorar cursos', 'flavor-chat-ia'); ?>
                    </a>
                </div>
            <?php else: ?>
                <div class="inscripciones-lista">
                    <?php foreach ($inscripciones as $inscripcion):
                        $imagen = get_the_post_thumbnail_url($inscripcion->curso_id, 'thumbnail');
                        $fecha_inicio = get_post_meta($inscripcion->curso_id, '_curso_fecha_inicio', true);
                        $fecha_fin = get_post_meta($inscripcion->curso_id, '_curso_fecha_fin', true);
                        $modalidad = get_post_meta($inscripcion->curso_id, '_curso_modalidad', true);

                        // Determinar estado visual
                        $ahora = current_time('Y-m-d');
                        $estado_visual = $inscripcion->estado;
                        if ($inscripcion->estado === 'confirmada' && $fecha_inicio && $ahora >= $fecha_inicio) {
                            $estado_visual = 'en_curso';
                        }
                        if ($fecha_fin && $ahora > $fecha_fin) {
                            $estado_visual = 'finalizado';
                        }
                    ?>
                        <div class="inscripcion-item estado-<?php echo esc_attr($estado_visual); ?>">
                            <div class="inscripcion-imagen">
                                <?php if ($imagen): ?>
                                    <img src="<?php echo esc_url($imagen); ?>" alt="">
                                <?php else: ?>
                                    <span class="sin-imagen dashicons dashicons-welcome-learn-more"></span>
                                <?php endif; ?>
                            </div>
                            <div class="inscripcion-info">
                                <h4>
                                    <a href="<?php echo get_permalink($inscripcion->curso_id); ?>">
                                        <?php echo esc_html($inscripcion->curso_titulo); ?>
                                    </a>
                                </h4>
                                <?php if ($fecha_inicio): ?>
                                    <p class="inscripcion-fechas">
                                        <span class="dashicons dashicons-calendar-alt"></span>
                                        <?php
                                        echo date_i18n('j M Y', strtotime($fecha_inicio));
                                        if ($fecha_fin && $fecha_fin !== $fecha_inicio) {
                                            echo ' - ' . date_i18n('j M Y', strtotime($fecha_fin));
                                        }
                                        ?>
                                    </p>
                                <?php endif; ?>
                                <?php if ($modalidad): ?>
                                    <span class="modalidad-badge"><?php echo esc_html(ucfirst($modalidad)); ?></span>
                                <?php endif; ?>
                            </div>
                            <div class="inscripcion-estado">
                                <span class="estado-badge estado-<?php echo esc_attr($estado_visual); ?>">
                                    <?php
                                    $estados_label = [
                                        'pendiente' => __('Pendiente', 'flavor-chat-ia'),
                                        'confirmada' => __('Confirmada', 'flavor-chat-ia'),
                                        'en_curso' => __('En curso', 'flavor-chat-ia'),
                                        'finalizado' => __('Finalizado', 'flavor-chat-ia'),
                                        'cancelada' => __('Cancelada', 'flavor-chat-ia'),
                                    ];
                                    echo esc_html($estados_label[$estado_visual] ?? $estado_visual);
                                    ?>
                                </span>
                            </div>
                            <?php if ($inscripcion->estado === 'confirmada' && $fecha_inicio && $ahora < $fecha_inicio): ?>
                                <div class="inscripcion-acciones">
                                    <button type="button" class="btn-cancelar" data-inscripcion-id="<?php echo esc_attr($inscripcion->id); ?>">
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
     * AJAX: Inscribirse en curso
     */
    public function ajax_inscribirse() {
        check_ajax_referer('cursos_frontend_nonce', 'nonce');

        if (!is_user_logged_in()) {
            wp_send_json_error(['message' => __('Debes iniciar sesión', 'flavor-chat-ia')]);
        }

        $curso_id = absint($_POST['curso_id'] ?? 0);
        if (!$curso_id) {
            wp_send_json_error(['message' => __('Curso no válido', 'flavor-chat-ia')]);
        }

        // Verificar plazas
        $plazas_total = (int) get_post_meta($curso_id, '_curso_plazas', true);
        $plazas_ocupadas = (int) get_post_meta($curso_id, '_curso_plazas_ocupadas', true);

        if ($plazas_total > 0 && $plazas_ocupadas >= $plazas_total) {
            wp_send_json_error(['message' => __('No hay plazas disponibles', 'flavor-chat-ia')]);
        }

        global $wpdb;
        $tabla = $wpdb->prefix . 'flavor_cursos_inscripciones';

        // Verificar si ya está inscrito
        $existente = $wpdb->get_var($wpdb->prepare(
            "SELECT id FROM {$tabla} WHERE alumno_id = %d AND curso_id = %d AND estado != 'cancelada'",
            get_current_user_id(),
            $curso_id
        ));

        if ($existente) {
            wp_send_json_error(['message' => __('Ya estás inscrito en este curso', 'flavor-chat-ia')]);
        }

        // Crear inscripción
        $resultado = $wpdb->insert($tabla, [
            'alumno_id' => get_current_user_id(),
            'curso_id' => $curso_id,
            'fecha_inscripcion' => current_time('mysql'),
            'estado' => 'confirmada',
        ]);

        if ($resultado === false) {
            wp_send_json_error(['message' => __('Error al procesar la inscripción', 'flavor-chat-ia')]);
        }

        // Actualizar plazas ocupadas
        update_post_meta($curso_id, '_curso_plazas_ocupadas', $plazas_ocupadas + 1);

        wp_send_json_success(['message' => __('Inscripción realizada correctamente', 'flavor-chat-ia')]);
    }

    /**
     * AJAX: Cancelar inscripción
     */
    public function ajax_cancelar_inscripcion() {
        check_ajax_referer('cursos_frontend_nonce', 'nonce');

        if (!is_user_logged_in()) {
            wp_send_json_error(['message' => __('Debes iniciar sesión', 'flavor-chat-ia')]);
        }

        $inscripcion_id = absint($_POST['inscripcion_id'] ?? 0);

        global $wpdb;
        $tabla = $wpdb->prefix . 'flavor_cursos_inscripciones';

        // Obtener inscripción
        $inscripcion = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$tabla} WHERE id = %d AND alumno_id = %d",
            $inscripcion_id,
            get_current_user_id()
        ));

        if (!$inscripcion) {
            wp_send_json_error(['message' => __('Inscripción no encontrada', 'flavor-chat-ia')]);
        }

        // Cancelar
        $resultado = $wpdb->update(
            $tabla,
            ['estado' => 'cancelada'],
            ['id' => $inscripcion_id]
        );

        if ($resultado === false) {
            wp_send_json_error(['message' => __('Error al cancelar la inscripción', 'flavor-chat-ia')]);
        }

        // Liberar plaza
        $plazas_ocupadas = (int) get_post_meta($inscripcion->curso_id, '_curso_plazas_ocupadas', true);
        if ($plazas_ocupadas > 0) {
            update_post_meta($inscripcion->curso_id, '_curso_plazas_ocupadas', $plazas_ocupadas - 1);
        }

        wp_send_json_success(['message' => __('Inscripción cancelada', 'flavor-chat-ia')]);
    }

    /**
     * AJAX: Filtrar cursos
     */
    public function ajax_filtrar() {
        check_ajax_referer('cursos_frontend_nonce', 'nonce');

        $texto = sanitize_text_field($_POST['texto'] ?? '');
        $categoria = sanitize_text_field($_POST['categoria'] ?? '');
        $modalidad = sanitize_text_field($_POST['modalidad'] ?? '');

        $args = [
            'post_type' => 'curso',
            'post_status' => 'publish',
            'posts_per_page' => 20,
            'meta_query' => [
                [
                    'key' => '_curso_fecha_inicio',
                    'value' => date('Y-m-d'),
                    'compare' => '>=',
                    'type' => 'DATE',
                ],
            ],
        ];

        if (!empty($texto)) {
            $args['s'] = $texto;
        }

        if (!empty($categoria)) {
            $args['tax_query'] = [[
                'taxonomy' => 'curso_categoria',
                'field' => 'slug',
                'terms' => $categoria,
            ]];
        }

        if (!empty($modalidad)) {
            $args['meta_query'][] = [
                'key' => '_curso_modalidad',
                'value' => $modalidad,
            ];
        }

        $cursos = get_posts($args);

        ob_start();
        if (empty($cursos)) {
            echo '<p class="cursos-sin-resultados">' . __('No se encontraron cursos.', 'flavor-chat-ia') . '</p>';
        } else {
            echo '<div class="cursos-grid columnas-3">';
            foreach ($cursos as $curso) {
                $this->render_curso_card($curso);
            }
            echo '</div>';
        }
        $html = ob_get_clean();

        wp_send_json_success(['html' => $html, 'count' => count($cursos)]);
    }

    /**
     * AJAX: Completar leccion
     */
    public function ajax_completar_leccion() {
        check_ajax_referer('cursos_frontend_nonce', 'nonce');

        if (!is_user_logged_in()) {
            wp_send_json_error(['message' => __('Debes iniciar sesión', 'flavor-chat-ia')]);
        }

        $leccion_id = absint($_POST['leccion_id'] ?? 0);
        $inscripcion_id = absint($_POST['inscripcion_id'] ?? 0);

        if (!$leccion_id || !$inscripcion_id) {
            wp_send_json_error(['message' => __('Datos incompletos', 'flavor-chat-ia')]);
        }

        global $wpdb;
        $tabla_inscripciones = $wpdb->prefix . 'flavor_cursos_inscripciones';
        $tabla_lecciones = $wpdb->prefix . 'flavor_cursos_lecciones';
        $tabla_progreso = $wpdb->prefix . 'flavor_cursos_progreso';
        $usuario_id = get_current_user_id();

        // Verificar que la inscripcion pertenece al usuario
        $inscripcion = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$tabla_inscripciones} WHERE id = %d AND alumno_id = %d",
            $inscripcion_id,
            $usuario_id
        ));

        if (!$inscripcion) {
            wp_send_json_error(['message' => __('Inscripcion no válida', 'flavor-chat-ia')]);
        }

        // Verificar que la leccion pertenece al curso
        $leccion = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$tabla_lecciones} WHERE id = %d AND curso_id = %d",
            $leccion_id,
            $inscripcion->curso_id
        ));

        if (!$leccion) {
            wp_send_json_error(['message' => __('Leccion no válida', 'flavor-chat-ia')]);
        }

        // Verificar si ya existe progreso
        $progreso_existente = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$tabla_progreso} WHERE inscripcion_id = %d AND leccion_id = %d",
            $inscripcion_id,
            $leccion_id
        ));

        if ($progreso_existente) {
            // Actualizar
            $wpdb->update(
                $tabla_progreso,
                [
                    'completada' => 1,
                    'fecha_completado' => current_time('mysql'),
                ],
                ['id' => $progreso_existente->id]
            );
        } else {
            // Crear nuevo registro
            $wpdb->insert($tabla_progreso, [
                'inscripcion_id' => $inscripcion_id,
                'leccion_id' => $leccion_id,
                'completada' => 1,
                'fecha_inicio' => current_time('mysql'),
                'fecha_completado' => current_time('mysql'),
            ]);
        }

        // Actualizar progreso general del curso
        $total_lecciones = (int) $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM {$tabla_lecciones} WHERE curso_id = %d",
            $inscripcion->curso_id
        ));

        $lecciones_completadas = (int) $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM {$tabla_progreso} WHERE inscripcion_id = %d AND completada = 1",
            $inscripcion_id
        ));

        $porcentaje_progreso = $total_lecciones > 0 ? round(($lecciones_completadas / $total_lecciones) * 100, 2) : 0;

        $nuevo_estado = $porcentaje_progreso >= 100 ? 'completada' : 'activa';

        $wpdb->update(
            $tabla_inscripciones,
            [
                'progreso_porcentaje' => $porcentaje_progreso,
                'lecciones_completadas' => $lecciones_completadas,
                'fecha_ultima_actividad' => current_time('mysql'),
                'estado' => $nuevo_estado,
            ],
            ['id' => $inscripcion_id]
        );

        wp_send_json_success([
            'message' => __('Leccion completada', 'flavor-chat-ia'),
            'progreso' => $porcentaje_progreso,
            'completadas' => $lecciones_completadas,
            'total' => $total_lecciones,
            'curso_completado' => $porcentaje_progreso >= 100,
        ]);
    }

    /**
     * Shortcode: Aula virtual
     */
    public function shortcode_aula($atts) {
        $this->encolar_assets();

        if (!is_user_logged_in()) {
            return '<p class="cursos-login-requerido">' . __('Inicia sesión para acceder al aula virtual.', 'flavor-chat-ia') . '</p>';
        }

        $atributos = shortcode_atts([
            'curso_id' => 0,
            'leccion_id' => 0,
        ], $atts);

        // Obtener IDs de la URL si no se pasan como atributos
        $curso_id = $atributos['curso_id'] ?: (isset($_GET['curso_id']) ? absint($_GET['curso_id']) : 0);
        $leccion_id = $atributos['leccion_id'] ?: (isset($_GET['leccion_id']) ? absint($_GET['leccion_id']) : 0);

        if (!$curso_id) {
            return '<div class="cursos-aula-empty">' . $this->render_seleccionar_curso() . '</div>';
        }

        global $wpdb;
        $usuario_id = get_current_user_id();
        $tabla_inscripciones = $wpdb->prefix . 'flavor_cursos_inscripciones';
        $tabla_cursos = $wpdb->prefix . 'flavor_cursos';
        $tabla_lecciones = $wpdb->prefix . 'flavor_cursos_lecciones';
        $tabla_progreso = $wpdb->prefix . 'flavor_cursos_progreso';

        // Verificar inscripcion
        $inscripcion = null;
        if ($this->tabla_existe($tabla_inscripciones)) {
            $inscripcion = $wpdb->get_row($wpdb->prepare(
                "SELECT * FROM {$tabla_inscripciones} WHERE curso_id = %d AND alumno_id = %d AND estado IN ('activa', 'completada', 'confirmada', 'en_curso')",
                $curso_id,
                $usuario_id
            ));
        }

        if (!$inscripcion) {
            return '<div class="cursos-no-inscrito">
                <span class="dashicons dashicons-lock"></span>
                <p>' . __('No estás inscrito en este curso.', 'flavor-chat-ia') . '</p>
                <a href="' . esc_url(home_url('/mi-portal/cursos/catalogo/')) . '" class="btn btn-primary">' . __('Ver catálogo', 'flavor-chat-ia') . '</a>
            </div>';
        }

        // Obtener info del curso
        $curso = null;
        if ($this->tabla_existe($tabla_cursos)) {
            $curso = $wpdb->get_row($wpdb->prepare(
                "SELECT * FROM {$tabla_cursos} WHERE id = %d",
                $curso_id
            ));
        }

        if (!$curso) {
            return '<p class="cursos-error">' . __('Curso no encontrado.', 'flavor-chat-ia') . '</p>';
        }

        // Obtener lecciones del curso
        $lecciones = [];
        if ($this->tabla_existe($tabla_lecciones)) {
            $lecciones = $wpdb->get_results($wpdb->prepare(
                "SELECT l.*,
                        COALESCE(p.completada, 0) as completada,
                        p.tiempo_dedicado_minutos,
                        p.fecha_completado
                 FROM {$tabla_lecciones} l
                 LEFT JOIN {$tabla_progreso} p ON l.id = p.leccion_id AND p.inscripcion_id = %d
                 WHERE l.curso_id = %d
                 ORDER BY l.numero_orden ASC",
                $inscripcion->id,
                $curso_id
            ));
        }

        // Si no hay leccion seleccionada, usar la primera no completada o la primera
        if (!$leccion_id && !empty($lecciones)) {
            foreach ($lecciones as $leccion) {
                if (!$leccion->completada) {
                    $leccion_id = $leccion->id;
                    break;
                }
            }
            if (!$leccion_id) {
                $leccion_id = $lecciones[0]->id;
            }
        }

        // Obtener leccion actual
        $leccion_actual = null;
        foreach ($lecciones as $leccion) {
            if ($leccion->id == $leccion_id) {
                $leccion_actual = $leccion;
                break;
            }
        }

        ob_start();
        $this->render_aula($curso, $lecciones, $leccion_actual, $inscripcion);
        return ob_get_clean();
    }

    /**
     * Renderizar selector de curso para el aula
     */
    private function render_seleccionar_curso() {
        global $wpdb;
        $usuario_id = get_current_user_id();
        $tabla_inscripciones = $wpdb->prefix . 'flavor_cursos_inscripciones';
        $tabla_cursos = $wpdb->prefix . 'flavor_cursos';

        $cursos_inscritos = [];
        if ($this->tabla_existe($tabla_inscripciones) && $this->tabla_existe($tabla_cursos)) {
            $cursos_inscritos = $wpdb->get_results($wpdb->prepare(
                "SELECT c.*, i.progreso_porcentaje, i.estado as estado_inscripcion
                 FROM {$tabla_inscripciones} i
                 INNER JOIN {$tabla_cursos} c ON i.curso_id = c.id
                 WHERE i.alumno_id = %d AND i.estado IN ('activa', 'confirmada', 'en_curso')
                 ORDER BY i.fecha_ultima_actividad DESC",
                $usuario_id
            ));
        }

        ob_start();
        ?>
        <div class="aula-seleccionar-curso">
            <h3><?php _e('Selecciona un curso para continuar', 'flavor-chat-ia'); ?></h3>
            <?php if (empty($cursos_inscritos)): ?>
                <div class="empty-state">
                    <span class="dashicons dashicons-welcome-learn-more"></span>
                    <p><?php _e('No tienes cursos activos.', 'flavor-chat-ia'); ?></p>
                    <a href="<?php echo esc_url(home_url('/mi-portal/cursos/catalogo/')); ?>" class="btn btn-primary">
                        <?php _e('Explorar cursos', 'flavor-chat-ia'); ?>
                    </a>
                </div>
            <?php else: ?>
                <div class="cursos-activos-grid">
                    <?php foreach ($cursos_inscritos as $curso_item): ?>
                        <a href="<?php echo esc_url(add_query_arg('curso_id', $curso_item->id)); ?>" class="curso-activo-card">
                            <div class="curso-activo-imagen">
                                <?php if ($curso_item->imagen_portada): ?>
                                    <img src="<?php echo esc_url($curso_item->imagen_portada); ?>" alt="">
                                <?php else: ?>
                                    <span class="dashicons dashicons-welcome-learn-more"></span>
                                <?php endif; ?>
                            </div>
                            <div class="curso-activo-info">
                                <h4><?php echo esc_html($curso_item->titulo); ?></h4>
                                <div class="curso-progreso-bar">
                                    <div class="progreso-fill" style="width: <?php echo esc_attr($curso_item->progreso_porcentaje); ?>%"></div>
                                </div>
                                <span class="progreso-texto"><?php echo esc_html(round($curso_item->progreso_porcentaje)); ?>% completado</span>
                            </div>
                        </a>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
        <?php
        return ob_get_clean();
    }

    /**
     * Renderizar el aula virtual
     */
    private function render_aula($curso, $lecciones, $leccion_actual, $inscripcion) {
        $total_lecciones = count($lecciones);
        $lecciones_completadas = 0;
        foreach ($lecciones as $leccion_item) {
            if ($leccion_item->completada) {
                $lecciones_completadas++;
            }
        }
        $porcentaje_progreso = $total_lecciones > 0 ? round(($lecciones_completadas / $total_lecciones) * 100) : 0;
        ?>
        <div class="cursos-aula" data-curso-id="<?php echo esc_attr($curso->id); ?>">
            <!-- Header del aula -->
            <div class="aula-header">
                <div class="aula-curso-info">
                    <h2><?php echo esc_html($curso->titulo); ?></h2>
                    <div class="aula-progreso">
                        <div class="progreso-bar">
                            <div class="progreso-fill" style="width: <?php echo esc_attr($porcentaje_progreso); ?>%"></div>
                        </div>
                        <span class="progreso-texto">
                            <?php printf(__('%d de %d lecciones (%d%%)', 'flavor-chat-ia'), $lecciones_completadas, $total_lecciones, $porcentaje_progreso); ?>
                        </span>
                    </div>
                </div>
                <a href="<?php echo esc_url(home_url('/mi-portal/cursos/mis-cursos/')); ?>" class="btn-volver">
                    <span class="dashicons dashicons-arrow-left-alt"></span>
                    <?php _e('Mis cursos', 'flavor-chat-ia'); ?>
                </a>
            </div>

            <div class="aula-contenido">
                <!-- Sidebar con lecciones -->
                <div class="aula-sidebar">
                    <h3><?php _e('Contenido del curso', 'flavor-chat-ia'); ?></h3>
                    <ul class="aula-lecciones-lista">
                        <?php foreach ($lecciones as $index => $leccion_item):
                            $es_actual = $leccion_actual && $leccion_item->id == $leccion_actual->id;
                            $clase_estado = $leccion_item->completada ? 'completada' : ($es_actual ? 'actual' : 'pendiente');
                        ?>
                            <li class="leccion-item <?php echo esc_attr($clase_estado); ?>">
                                <a href="<?php echo esc_url(add_query_arg(['curso_id' => $curso->id, 'leccion_id' => $leccion_item->id])); ?>">
                                    <span class="leccion-numero"><?php echo esc_html($index + 1); ?></span>
                                    <span class="leccion-titulo"><?php echo esc_html($leccion_item->titulo); ?></span>
                                    <span class="leccion-estado">
                                        <?php if ($leccion_item->completada): ?>
                                            <span class="dashicons dashicons-yes-alt"></span>
                                        <?php elseif ($es_actual): ?>
                                            <span class="dashicons dashicons-marker"></span>
                                        <?php else: ?>
                                            <span class="dashicons dashicons-minus"></span>
                                        <?php endif; ?>
                                    </span>
                                </a>
                                <?php if ($leccion_item->duracion_minutos): ?>
                                    <span class="leccion-duracion"><?php echo esc_html($leccion_item->duracion_minutos); ?> min</span>
                                <?php endif; ?>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                </div>

                <!-- Area principal con contenido de la leccion -->
                <div class="aula-principal">
                    <?php if ($leccion_actual): ?>
                        <div class="leccion-contenedor" data-leccion-id="<?php echo esc_attr($leccion_actual->id); ?>">
                            <div class="leccion-header">
                                <h3><?php echo esc_html($leccion_actual->titulo); ?></h3>
                                <?php if ($leccion_actual->tipo): ?>
                                    <span class="leccion-tipo tipo-<?php echo esc_attr($leccion_actual->tipo); ?>">
                                        <?php
                                        $tipos_label = [
                                            'video' => __('Video', 'flavor-chat-ia'),
                                            'texto' => __('Texto', 'flavor-chat-ia'),
                                            'quiz' => __('Cuestionario', 'flavor-chat-ia'),
                                            'archivo' => __('Archivo', 'flavor-chat-ia'),
                                            'enlace' => __('Enlace', 'flavor-chat-ia'),
                                            'live' => __('En vivo', 'flavor-chat-ia'),
                                        ];
                                        echo esc_html($tipos_label[$leccion_actual->tipo] ?? $leccion_actual->tipo);
                                        ?>
                                    </span>
                                <?php endif; ?>
                            </div>

                            <?php if ($leccion_actual->descripcion): ?>
                                <div class="leccion-descripcion">
                                    <?php echo wp_kses_post($leccion_actual->descripcion); ?>
                                </div>
                            <?php endif; ?>

                            <!-- Contenido segun tipo -->
                            <div class="leccion-contenido">
                                <?php if ($leccion_actual->tipo === 'video' && $leccion_actual->video_url): ?>
                                    <div class="leccion-video">
                                        <?php echo wp_oembed_get($leccion_actual->video_url) ?: '<a href="' . esc_url($leccion_actual->video_url) . '" target="_blank">' . __('Ver video', 'flavor-chat-ia') . '</a>'; ?>
                                    </div>
                                <?php endif; ?>

                                <?php if ($leccion_actual->contenido): ?>
                                    <div class="leccion-texto">
                                        <?php echo wp_kses_post($leccion_actual->contenido); ?>
                                    </div>
                                <?php endif; ?>

                                <?php if ($leccion_actual->archivo_url): ?>
                                    <div class="leccion-archivo">
                                        <a href="<?php echo esc_url($leccion_actual->archivo_url); ?>" class="btn btn-secondary" download>
                                            <span class="dashicons dashicons-download"></span>
                                            <?php _e('Descargar material', 'flavor-chat-ia'); ?>
                                        </a>
                                    </div>
                                <?php endif; ?>
                            </div>

                            <!-- Acciones de la leccion -->
                            <div class="leccion-acciones">
                                <?php if (!$leccion_actual->completada): ?>
                                    <button type="button" class="btn btn-primary btn-completar-leccion"
                                            data-leccion-id="<?php echo esc_attr($leccion_actual->id); ?>"
                                            data-inscripcion-id="<?php echo esc_attr($inscripcion->id); ?>">
                                        <span class="dashicons dashicons-yes"></span>
                                        <?php _e('Marcar como completada', 'flavor-chat-ia'); ?>
                                    </button>
                                <?php else: ?>
                                    <span class="leccion-completada-badge">
                                        <span class="dashicons dashicons-yes-alt"></span>
                                        <?php _e('Leccion completada', 'flavor-chat-ia'); ?>
                                    </span>
                                <?php endif; ?>

                                <!-- Navegacion entre lecciones -->
                                <div class="leccion-navegacion">
                                    <?php
                                    $indice_actual = array_search($leccion_actual->id, array_column($lecciones, 'id'));
                                    $leccion_anterior = $indice_actual > 0 ? $lecciones[$indice_actual - 1] : null;
                                    $leccion_siguiente = $indice_actual < count($lecciones) - 1 ? $lecciones[$indice_actual + 1] : null;
                                    ?>
                                    <?php if ($leccion_anterior): ?>
                                        <a href="<?php echo esc_url(add_query_arg(['curso_id' => $curso->id, 'leccion_id' => $leccion_anterior->id])); ?>" class="btn btn-nav btn-anterior">
                                            <span class="dashicons dashicons-arrow-left-alt2"></span>
                                            <?php _e('Anterior', 'flavor-chat-ia'); ?>
                                        </a>
                                    <?php endif; ?>
                                    <?php if ($leccion_siguiente): ?>
                                        <a href="<?php echo esc_url(add_query_arg(['curso_id' => $curso->id, 'leccion_id' => $leccion_siguiente->id])); ?>" class="btn btn-nav btn-siguiente">
                                            <?php _e('Siguiente', 'flavor-chat-ia'); ?>
                                            <span class="dashicons dashicons-arrow-right-alt2"></span>
                                        </a>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    <?php else: ?>
                        <div class="sin-leccion">
                            <span class="dashicons dashicons-welcome-learn-more"></span>
                            <p><?php _e('Este curso aún no tiene lecciones disponibles.', 'flavor-chat-ia'); ?></p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        <?php
    }

    /**
     * Cargar templates personalizados
     */
    public function cargar_templates($template) {
        $plugin_templates_path = dirname(dirname(__FILE__)) . '/frontend/';

        // Template para single curso
        if (is_singular('curso')) {
            $custom_theme = locate_template('cursos/single-curso.php');
            if ($custom_theme) {
                return $custom_theme;
            }

            $plugin_template = $plugin_templates_path . 'single.php';
            if (file_exists($plugin_template)) {
                return $plugin_template;
            }
        }

        // Template para archive curso
        if (is_post_type_archive('curso')) {
            $custom_theme = locate_template('cursos/archive-curso.php');
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

    /**
     * Shortcode: Mi progreso general en cursos
     * Uso: [cursos_mi_progreso]
     */
    public function shortcode_mi_progreso($atts) {
        if (!is_user_logged_in()) {
            return '<p class="cursos-login-requerido">' . __('Inicia sesión para ver tu progreso.', 'flavor-chat-ia') . '</p>';
        }

        $this->encolar_assets();
        $usuario_id = get_current_user_id();

        global $wpdb;
        $tabla_inscripciones = $wpdb->prefix . 'flavor_cursos_inscripciones';
        $tabla_cursos = $wpdb->prefix . 'flavor_cursos';
        $tabla_progreso = $wpdb->prefix . 'flavor_cursos_progreso';
        $tabla_lecciones = $wpdb->prefix . 'flavor_cursos_lecciones';

        // Obtener cursos con progreso
        $cursos_progreso = $wpdb->get_results($wpdb->prepare(
            "SELECT c.id, c.titulo, c.imagen_url,
                    i.estado as estado_inscripcion,
                    COUNT(DISTINCT l.id) as total_lecciones,
                    COUNT(DISTINCT CASE WHEN p.completada = 1 THEN l.id END) as lecciones_completadas
             FROM {$tabla_inscripciones} i
             JOIN {$tabla_cursos} c ON i.curso_id = c.id
             LEFT JOIN {$tabla_lecciones} l ON c.id = l.curso_id
             LEFT JOIN {$tabla_progreso} p ON l.id = p.leccion_id AND p.inscripcion_id = i.id
             WHERE i.alumno_id = %d AND i.estado IN ('activa', 'en_curso', 'completada')
             GROUP BY c.id
             ORDER BY i.fecha_inscripcion DESC",
            $usuario_id
        ));

        if (empty($cursos_progreso)) {
            return '<div class="cursos-sin-inscripciones">
                <span class="dashicons dashicons-welcome-learn-more"></span>
                <p>' . __('Aún no estás inscrito en ningún curso.', 'flavor-chat-ia') . '</p>
                <a href="' . esc_url(home_url('/mi-portal/cursos/catalogo/')) . '" class="btn btn-primary">' . __('Explorar cursos', 'flavor-chat-ia') . '</a>
            </div>';
        }

        ob_start();
        ?>
        <div class="cursos-mi-progreso">
            <?php foreach ($cursos_progreso as $curso):
                $porcentaje = $curso->total_lecciones > 0
                    ? round(($curso->lecciones_completadas / $curso->total_lecciones) * 100)
                    : 0;
            ?>
            <div class="progreso-curso">
                <div class="progreso-imagen">
                    <?php if ($curso->imagen_url): ?>
                        <img src="<?php echo esc_url($curso->imagen_url); ?>" alt="">
                    <?php else: ?>
                        <span class="dashicons dashicons-welcome-learn-more"></span>
                    <?php endif; ?>
                </div>
                <div class="progreso-info">
                    <h4><?php echo esc_html($curso->titulo); ?></h4>
                    <div class="progreso-barra">
                        <div class="progreso-relleno" style="width: <?php echo $porcentaje; ?>%"></div>
                    </div>
                    <span class="progreso-texto"><?php printf(__('%d%% completado', 'flavor-chat-ia'), $porcentaje); ?></span>
                </div>
                <a href="<?php echo esc_url(home_url('/mi-portal/cursos/aula/?curso_id=' . $curso->id)); ?>" class="btn btn-sm">
                    <?php echo $porcentaje >= 100 ? __('Repasar', 'flavor-chat-ia') : __('Continuar', 'flavor-chat-ia'); ?>
                </a>
            </div>
            <?php endforeach; ?>
        </div>
        <?php
        return ob_get_clean();
    }
}

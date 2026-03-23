<?php
/**
 * Frontend Controller para Recetas
 *
 * @package FlavorChatIA
 * @subpackage Modules\Recetas
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Clase controladora del frontend para Recetas
 */
class Flavor_Recetas_Frontend_Controller {

    /**
     * Instancia singleton
     * @var Flavor_Recetas_Frontend_Controller|null
     */
    private static $instancia = null;

    /**
     * Post type de recetas
     * @var string
     */
    private $post_type = 'flavor_receta';

    /**
     * Dificultades disponibles
     * @var array
     */
    private $dificultades = [
        'facil' => ['nombre' => 'Fácil', 'color' => '#22c55e', 'icono' => '👶'],
        'media' => ['nombre' => 'Media', 'color' => '#f59e0b', 'icono' => '👨‍🍳'],
        'dificil' => ['nombre' => 'Difícil', 'color' => '#ef4444', 'icono' => '🧑‍🍳'],
        'experto' => ['nombre' => 'Experto', 'color' => '#7c3aed', 'icono' => '👨‍🎓'],
    ];

    /**
     * Indica si las recetas creadas desde frontend requieren moderación.
     *
     * @var bool
     */
    private $frontend_requires_moderation = true;

    /**
     * Constructor privado
     */
    private function __construct() {
        $this->init_hooks();
    }

    /**
     * Obtiene la instancia singleton
     *
     * @return Flavor_Recetas_Frontend_Controller
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
        add_filter('flavor_user_dashboard_tabs', [$this, 'registrar_tabs']);

        // Shortcodes adicionales
        $shortcodes = [
            'flavor_recetas_buscador' => 'shortcode_buscador',
            'flavor_recetas_categorias' => 'shortcode_categorias',
            'flavor_recetas_mis_recetas' => 'shortcode_mis_recetas',
            'flavor_recetas_favoritas' => 'shortcode_favoritas',
            'flavor_recetas_crear' => 'shortcode_crear',
            'flavor_recetas_destacadas' => 'shortcode_destacadas',
            'flavor_recetas_por_ingrediente' => 'shortcode_por_ingrediente',
            'flavor_recetas_dashboard' => 'shortcode_dashboard',
        ];
        foreach ($shortcodes as $tag => $method) {
            if (!shortcode_exists($tag)) {
                add_shortcode($tag, [$this, $method]);
            }
        }

        // AJAX handlers
        add_action('wp_ajax_flavor_recetas_guardar', [$this, 'ajax_guardar']);
        add_action('wp_ajax_flavor_recetas_favorito', [$this, 'ajax_favorito']);
        add_action('wp_ajax_nopriv_flavor_recetas_favorito', [$this, 'ajax_favorito']);
        add_action('wp_ajax_flavor_recetas_valorar', [$this, 'ajax_valorar']);
        add_action('wp_ajax_nopriv_flavor_recetas_valorar', [$this, 'ajax_valorar']);
        add_action('wp_ajax_flavor_recetas_buscar', [$this, 'ajax_buscar']);
        add_action('wp_ajax_nopriv_flavor_recetas_buscar', [$this, 'ajax_buscar']);
        add_action('wp_ajax_flavor_recetas_dashboard_data', [$this, 'ajax_dashboard_data']);
    }

    /**
     * Registra los assets del módulo
     */
    public function register_assets() {
        wp_register_style(
            'flavor-recetas-frontend',
            plugin_dir_url(__FILE__) . '../assets/css/recetas-frontend.css',
            [],
            FLAVOR_CHAT_IA_VERSION
        );

        wp_register_script(
            'flavor-recetas-frontend',
            plugin_dir_url(__FILE__) . '../assets/js/recetas-frontend.js',
            ['jquery'],
            FLAVOR_CHAT_IA_VERSION,
            true
        );
    }

    /**
     * Encola los assets
     */
    private function enqueue_assets() {
        wp_enqueue_style('flavor-recetas-frontend');
        wp_enqueue_script('flavor-recetas-frontend');

        wp_localize_script('flavor-recetas-frontend', 'flavorRecetasConfig', [
            'ajaxUrl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('flavor_recetas_nonce'),
            'requiresModeration' => $this->frontend_requires_moderation,
            'dificultades' => $this->dificultades,
            'strings' => [
                'guardado' => __('Receta guardada', 'flavor-chat-ia'),
                'guardado_pendiente' => __('Receta enviada para revisión', 'flavor-chat-ia'),
                'error' => __('Error al procesar', 'flavor-chat-ia'),
                'confirmar_eliminar' => __('¿Eliminar esta receta?', 'flavor-chat-ia'),
                'eliminado' => __('Receta eliminada', 'flavor-chat-ia'),
                'favorito_agregado' => __('Agregado a favoritos', 'flavor-chat-ia'),
                'favorito_quitado' => __('Quitado de favoritos', 'flavor-chat-ia'),
                'valoracion_guardada' => __('Gracias por tu valoración', 'flavor-chat-ia'),
                'buscando' => __('Buscando...', 'flavor-chat-ia'),
                'sin_resultados' => __('No se encontraron recetas', 'flavor-chat-ia'),
                'minutos' => __('minutos', 'flavor-chat-ia'),
                'porciones' => __('porciones', 'flavor-chat-ia'),
            ],
        ]);
    }

    /**
     * Registra los tabs del dashboard
     *
     * @param array $tabs
     * @return array
     */
    public function registrar_tabs($tabs) {
        $tabs['recetas'] = [
            'label' => __('Recetas', 'flavor-chat-ia'),
            'icon' => 'carrot',
            'callback' => [$this, 'render_tab_recetas'],
            'orden' => 80,
        ];

        return $tabs;
    }

    /**
     * Renderiza el tab de recetas en dashboard
     */
    public function render_tab_recetas() {
        $this->enqueue_assets();
        $user_id = get_current_user_id();

        if (!$user_id) {
            echo '<p class="flavor-login-required">' . esc_html__('Debes iniciar sesión para ver tus recetas.', 'flavor-chat-ia') . '</p>';
            return;
        }

        // Estadísticas
        $mis_recetas = count(get_posts([
            'post_type' => $this->post_type,
            'author' => $user_id,
            'post_status' => 'any',
            'posts_per_page' => -1,
            'fields' => 'ids',
        ]));

        $favoritos = get_user_meta($user_id, '_flavor_recetas_favoritas', true) ?: [];
        $total_favoritas = count($favoritos);

        global $wpdb;
        $tabla_valoraciones = $wpdb->prefix . 'flavor_recetas_valoraciones';
        $total_valoraciones = 0;
        $promedio_valoracion = 0;

        if (Flavor_Chat_Helpers::tabla_existe($tabla_valoraciones)) {
            $total_valoraciones = (int) $wpdb->get_var($wpdb->prepare(
                "SELECT COUNT(*) FROM {$tabla_valoraciones}
                 WHERE receta_id IN (SELECT ID FROM {$wpdb->posts} WHERE post_author = %d AND post_type = 'flavor_receta')",
                $user_id
            ));

            $promedio_valoracion = (float) $wpdb->get_var($wpdb->prepare(
                "SELECT AVG(valoracion) FROM {$tabla_valoraciones}
                 WHERE receta_id IN (SELECT ID FROM {$wpdb->posts} WHERE post_author = %d AND post_type = 'flavor_receta')",
                $user_id
            ));
        }

        ?>
        <div class="flavor-panel flavor-recetas-panel">
            <div class="flavor-panel-header">
                <h2><span class="dashicons dashicons-carrot"></span> <?php esc_html_e('Mis Recetas', 'flavor-chat-ia'); ?></h2>
                <p class="flavor-panel-subtitle"><?php esc_html_e('Crea, guarda y comparte tus recetas favoritas', 'flavor-chat-ia'); ?></p>
            </div>

            <div class="flavor-panel-kpis">
                <div class="flavor-kpi-card">
                    <span class="flavor-kpi-icon dashicons dashicons-book-alt"></span>
                    <div class="flavor-kpi-content">
                        <span class="flavor-kpi-value"><?php echo number_format_i18n($mis_recetas); ?></span>
                        <span class="flavor-kpi-label"><?php esc_html_e('Mis Recetas', 'flavor-chat-ia'); ?></span>
                    </div>
                </div>
                <div class="flavor-kpi-card">
                    <span class="flavor-kpi-icon dashicons dashicons-heart"></span>
                    <div class="flavor-kpi-content">
                        <span class="flavor-kpi-value"><?php echo number_format_i18n($total_favoritas); ?></span>
                        <span class="flavor-kpi-label"><?php esc_html_e('Favoritas', 'flavor-chat-ia'); ?></span>
                    </div>
                </div>
                <div class="flavor-kpi-card">
                    <span class="flavor-kpi-icon dashicons dashicons-star-filled"></span>
                    <div class="flavor-kpi-content">
                        <span class="flavor-kpi-value"><?php echo number_format_i18n($promedio_valoracion, 1); ?></span>
                        <span class="flavor-kpi-label"><?php esc_html_e('Valoración media', 'flavor-chat-ia'); ?></span>
                    </div>
                </div>
                <div class="flavor-kpi-card flavor-kpi-accent">
                    <span class="flavor-kpi-icon dashicons dashicons-thumbs-up"></span>
                    <div class="flavor-kpi-content">
                        <span class="flavor-kpi-value"><?php echo number_format_i18n($total_valoraciones); ?></span>
                        <span class="flavor-kpi-label"><?php esc_html_e('Valoraciones', 'flavor-chat-ia'); ?></span>
                    </div>
                </div>
            </div>

            <?php $this->render_recetas_recientes($user_id); ?>

            <div class="flavor-panel-actions">
                <a href="<?php echo esc_url(home_url('/mi-portal/recetas/nueva/')); ?>" class="flavor-btn flavor-btn-primary">
                    <span class="dashicons dashicons-plus-alt"></span>
                    <?php esc_html_e('Nueva Receta', 'flavor-chat-ia'); ?>
                </a>
                <a href="<?php echo esc_url(home_url('/mi-portal/recetas/mis-recetas/')); ?>" class="flavor-btn flavor-btn-secondary">
                    <span class="dashicons dashicons-book-alt"></span>
                    <?php esc_html_e('Mis Recetas', 'flavor-chat-ia'); ?>
                </a>
                <a href="<?php echo esc_url(home_url('/mi-portal/recetas/favoritas/')); ?>" class="flavor-btn flavor-btn-outline">
                    <span class="dashicons dashicons-heart"></span>
                    <?php esc_html_e('Favoritas', 'flavor-chat-ia'); ?>
                </a>
                <a href="<?php echo esc_url(home_url('/recetas/')); ?>" class="flavor-btn flavor-btn-outline">
                    <span class="dashicons dashicons-admin-site"></span>
                    <?php esc_html_e('Explorar', 'flavor-chat-ia'); ?>
                </a>
            </div>
        </div>
        <?php
    }

    /**
     * Renderiza las recetas recientes del usuario
     */
    private function render_recetas_recientes($user_id) {
        $recetas = get_posts([
            'post_type' => $this->post_type,
            'author' => $user_id,
            'posts_per_page' => 4,
            'post_status' => 'publish',
        ]);

        if (empty($recetas)) {
            echo '<div class="flavor-empty-state">';
            echo '<span class="dashicons dashicons-carrot"></span>';
            echo '<p>' . esc_html__('Aún no has creado ninguna receta.', 'flavor-chat-ia') . '</p>';
            echo '<a href="' . esc_url(home_url('/mi-portal/recetas/nueva/')) . '" class="flavor-btn flavor-btn-primary">';
            echo esc_html__('Crear mi primera receta', 'flavor-chat-ia') . '</a>';
            echo '</div>';
            return;
        }

        ?>
        <div class="flavor-panel-section">
            <h3><?php esc_html_e('Mis recetas recientes', 'flavor-chat-ia'); ?></h3>
            <div class="flavor-recetas-grid">
                <?php foreach ($recetas as $receta):
                    $tiempo = get_post_meta($receta->ID, '_receta_tiempo_preparacion', true);
                    $dificultad = get_post_meta($receta->ID, '_receta_dificultad', true);
                    $porciones = get_post_meta($receta->ID, '_receta_porciones', true);
                ?>
                    <article class="flavor-receta-card" data-id="<?php echo esc_attr($receta->ID); ?>">
                        <div class="flavor-receta-imagen">
                            <?php if (has_post_thumbnail($receta->ID)): ?>
                                <?php echo get_the_post_thumbnail($receta->ID, 'medium'); ?>
                            <?php else: ?>
                                <div class="flavor-receta-placeholder">
                                    <span class="dashicons dashicons-carrot"></span>
                                </div>
                            <?php endif; ?>
                            <?php if (!empty($dificultad) && isset($this->dificultades[$dificultad])): ?>
                                <span class="flavor-badge flavor-badge-dificultad" style="background-color:<?php echo esc_attr($this->dificultades[$dificultad]['color']); ?>">
                                    <?php echo esc_html($this->dificultades[$dificultad]['icono'] . ' ' . $this->dificultades[$dificultad]['nombre']); ?>
                                </span>
                            <?php endif; ?>
                        </div>
                        <div class="flavor-receta-content">
                            <h4><a href="<?php echo get_permalink($receta->ID); ?>"><?php echo esc_html($receta->post_title); ?></a></h4>
                            <div class="flavor-receta-meta">
                                <?php if ($tiempo): ?>
                                    <span><span class="dashicons dashicons-clock"></span> <?php echo esc_html($tiempo); ?> min</span>
                                <?php endif; ?>
                                <?php if ($porciones): ?>
                                    <span><span class="dashicons dashicons-groups"></span> <?php echo esc_html($porciones); ?></span>
                                <?php endif; ?>
                            </div>
                        </div>
                    </article>
                <?php endforeach; ?>
            </div>
        </div>
        <?php
    }

    /**
     * Shortcode: Buscador de recetas
     */
    public function shortcode_buscador($atts) {
        $this->enqueue_assets();

        $termino_busqueda = sanitize_text_field($_GET['busqueda'] ?? '');
        $categoria_filtro = sanitize_text_field($_GET['categoria'] ?? '');
        $dificultad_filtro = sanitize_text_field($_GET['dificultad'] ?? '');

        $recetas_encontradas = [];
        $hay_busqueda = !empty($termino_busqueda) || !empty($categoria_filtro) || !empty($dificultad_filtro);

        if ($hay_busqueda) {
            $recetas_encontradas = $this->buscar_recetas($termino_busqueda, $categoria_filtro, $dificultad_filtro);
        }

        ob_start();
        ?>
        <div class="flavor-recetas-buscador">
            <form id="form-buscar-recetas" class="flavor-form-inline" method="get">
                <div class="flavor-search-box">
                    <span class="dashicons dashicons-search"></span>
                    <input type="text" name="busqueda"
                           placeholder="<?php esc_attr_e('Buscar recetas...', 'flavor-chat-ia'); ?>"
                           class="flavor-input"
                           value="<?php echo esc_attr($termino_busqueda); ?>">
                </div>
                <select name="categoria" class="flavor-select">
                    <option value=""><?php esc_html_e('Todas las categorías', 'flavor-chat-ia'); ?></option>
                    <?php
                    $categorias = get_terms(['taxonomy' => 'receta_categoria', 'hide_empty' => true]);
                    foreach ($categorias as $categoria):
                    ?>
                        <option value="<?php echo esc_attr($categoria->slug); ?>" <?php selected($categoria_filtro, $categoria->slug); ?>><?php echo esc_html($categoria->name); ?></option>
                    <?php endforeach; ?>
                </select>
                <select name="dificultad" class="flavor-select">
                    <option value=""><?php esc_html_e('Cualquier dificultad', 'flavor-chat-ia'); ?></option>
                    <?php foreach ($this->dificultades as $clave_dificultad => $datos_dificultad): ?>
                        <option value="<?php echo esc_attr($clave_dificultad); ?>" <?php selected($dificultad_filtro, $clave_dificultad); ?>><?php echo esc_html($datos_dificultad['nombre']); ?></option>
                    <?php endforeach; ?>
                </select>
                <button type="submit" class="flavor-btn flavor-btn-primary"><?php esc_html_e('Buscar', 'flavor-chat-ia'); ?></button>
            </form>

            <?php if ($hay_busqueda): ?>
            <div id="resultados-recetas" class="flavor-recetas-resultados">
                <p class="flavor-resultados-count">
                    <?php printf(
                        _n('%d receta encontrada', '%d recetas encontradas', count($recetas_encontradas), 'flavor-chat-ia'),
                        count($recetas_encontradas)
                    ); ?>
                </p>

                <?php if (!empty($recetas_encontradas)): ?>
                <div class="flavor-recetas-grid">
                    <?php foreach ($recetas_encontradas as $receta): ?>
                    <?php
                    $tiempo_preparacion = get_post_meta($receta->ID, '_tiempo_preparacion', true);
                    $porciones_receta = get_post_meta($receta->ID, '_porciones', true);
                    $dificultad_receta = get_post_meta($receta->ID, '_dificultad', true);
                    $valoracion_receta = get_post_meta($receta->ID, '_valoracion_promedio', true);
                    $datos_dificultad = $this->dificultades[$dificultad_receta] ?? $this->dificultades['media'];
                    ?>
                    <div class="flavor-receta-card">
                        <div class="flavor-receta-imagen">
                            <?php if (has_post_thumbnail($receta->ID)): ?>
                                <a href="<?php echo get_permalink($receta->ID); ?>">
                                    <?php echo get_the_post_thumbnail($receta->ID, 'medium'); ?>
                                </a>
                            <?php else: ?>
                                <div class="flavor-receta-sin-imagen">
                                    <span>🍽️</span>
                                </div>
                            <?php endif; ?>
                            <?php if ($dificultad_receta): ?>
                            <span class="flavor-receta-dificultad" style="background-color: <?php echo esc_attr($datos_dificultad['color']); ?>">
                                <?php echo esc_html($datos_dificultad['icono'] . ' ' . $datos_dificultad['nombre']); ?>
                            </span>
                            <?php endif; ?>
                        </div>
                        <div class="flavor-receta-info">
                            <h4 class="flavor-receta-titulo">
                                <a href="<?php echo get_permalink($receta->ID); ?>"><?php echo esc_html($receta->post_title); ?></a>
                            </h4>
                            <div class="flavor-receta-meta">
                                <?php if ($tiempo_preparacion): ?>
                                <span class="flavor-receta-tiempo">
                                    <span class="dashicons dashicons-clock"></span>
                                    <?php echo intval($tiempo_preparacion); ?> <?php esc_html_e('min', 'flavor-chat-ia'); ?>
                                </span>
                                <?php endif; ?>
                                <?php if ($porciones_receta): ?>
                                <span class="flavor-receta-porciones">
                                    <span class="dashicons dashicons-groups"></span>
                                    <?php echo intval($porciones_receta); ?>
                                </span>
                                <?php endif; ?>
                                <?php if ($valoracion_receta): ?>
                                <span class="flavor-receta-valoracion">
                                    ⭐ <?php echo number_format_i18n(floatval($valoracion_receta), 1); ?>
                                </span>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
                <?php else: ?>
                <div class="flavor-sin-resultados">
                    <span>🔍</span>
                    <p><?php esc_html_e('No se encontraron recetas con esos criterios.', 'flavor-chat-ia'); ?></p>
                </div>
                <?php endif; ?>
            </div>
            <?php else: ?>
            <div id="resultados-recetas" class="flavor-recetas-resultados"></div>
            <?php endif; ?>
        </div>
        <?php
        return ob_get_clean();
    }

    /**
     * Busca recetas por término, categoría y dificultad
     */
    private function buscar_recetas($termino = '', $categoria = '', $dificultad = '', $limite = 12) {
        $argumentos_query = [
            'post_type' => $this->post_type,
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
                    'taxonomy' => 'receta_categoria',
                    'field' => 'slug',
                    'terms' => $categoria,
                ],
            ];
        }

        if (!empty($dificultad)) {
            $argumentos_query['meta_query'] = [
                [
                    'key' => '_dificultad',
                    'value' => $dificultad,
                    'compare' => '=',
                ],
            ];
        }

        $consulta_recetas = new WP_Query($argumentos_query);
        return $consulta_recetas->posts;
    }

    /**
     * Shortcode: Categorías de recetas
     */
    public function shortcode_categorias($atts) {
        $this->enqueue_assets();

        $categorias = get_terms([
            'taxonomy' => 'receta_categoria',
            'hide_empty' => true,
        ]);

        ob_start();
        ?>
        <div class="flavor-recetas-categorias">
            <?php foreach ($categorias as $categoria):
                $icono = get_term_meta($categoria->term_id, '_categoria_icono', true) ?: '🍽️';
            ?>
                <a href="<?php echo get_term_link($categoria); ?>" class="flavor-categoria-card">
                    <span class="flavor-categoria-icono"><?php echo esc_html($icono); ?></span>
                    <span class="flavor-categoria-nombre"><?php echo esc_html($categoria->name); ?></span>
                    <span class="flavor-categoria-count"><?php echo intval($categoria->count); ?> <?php esc_html_e('recetas', 'flavor-chat-ia'); ?></span>
                </a>
            <?php endforeach; ?>
        </div>
        <?php
        return ob_get_clean();
    }

    /**
     * Shortcode: Mis recetas
     */
    public function shortcode_mis_recetas($atts) {
        $this->enqueue_assets();

        if (!is_user_logged_in()) {
            return '<p class="flavor-login-required">' . esc_html__('Debes iniciar sesión para ver tus recetas.', 'flavor-chat-ia') . '</p>';
        }

        $user_id = get_current_user_id();
        $recetas = get_posts([
            'post_type' => $this->post_type,
            'author' => $user_id,
            'posts_per_page' => -1,
            'post_status' => ['publish', 'draft', 'pending'],
        ]);

        ob_start();
        ?>
        <div class="flavor-mis-recetas">
            <div class="flavor-panel-header">
                <h2><?php esc_html_e('Mis Recetas', 'flavor-chat-ia'); ?></h2>
                <a href="<?php echo esc_url(home_url('/mi-portal/recetas/nueva/')); ?>" class="flavor-btn flavor-btn-primary">
                    <span class="dashicons dashicons-plus-alt"></span>
                    <?php esc_html_e('Nueva Receta', 'flavor-chat-ia'); ?>
                </a>
            </div>

            <?php if (empty($recetas)): ?>
                <div class="flavor-empty-state">
                    <span class="dashicons dashicons-carrot"></span>
                    <p><?php esc_html_e('No has creado ninguna receta aún.', 'flavor-chat-ia'); ?></p>
                </div>
            <?php else: ?>
                <div class="flavor-recetas-grid">
                    <?php foreach ($recetas as $receta):
                        $tiempo = get_post_meta($receta->ID, '_receta_tiempo_preparacion', true);
                        $dificultad = get_post_meta($receta->ID, '_receta_dificultad', true);
                    ?>
                        <article class="flavor-receta-card flavor-receta-editable" data-id="<?php echo esc_attr($receta->ID); ?>">
                            <div class="flavor-receta-imagen">
                                <?php if (has_post_thumbnail($receta->ID)): ?>
                                    <?php echo get_the_post_thumbnail($receta->ID, 'medium'); ?>
                                <?php else: ?>
                                    <div class="flavor-receta-placeholder">
                                        <span class="dashicons dashicons-carrot"></span>
                                    </div>
                                <?php endif; ?>
                                <span class="flavor-badge flavor-badge-<?php echo esc_attr($receta->post_status); ?>">
                                    <?php echo esc_html(get_post_status_object($receta->post_status)->label); ?>
                                </span>
                            </div>
                            <div class="flavor-receta-content">
                                <h4><?php echo esc_html($receta->post_title); ?></h4>
                                <div class="flavor-receta-meta">
                                    <?php if ($tiempo): ?>
                                        <span><span class="dashicons dashicons-clock"></span> <?php echo esc_html($tiempo); ?> min</span>
                                    <?php endif; ?>
                                </div>
                            </div>
                            <div class="flavor-receta-actions">
                                <a href="<?php echo get_edit_post_link($receta->ID); ?>" class="flavor-btn-icon" title="<?php esc_attr_e('Editar', 'flavor-chat-ia'); ?>">
                                    <span class="dashicons dashicons-edit"></span>
                                </a>
                                <a href="<?php echo get_permalink($receta->ID); ?>" class="flavor-btn-icon" title="<?php esc_attr_e('Ver', 'flavor-chat-ia'); ?>">
                                    <span class="dashicons dashicons-visibility"></span>
                                </a>
                            </div>
                        </article>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
        <?php
        return ob_get_clean();
    }

    /**
     * Shortcode: Recetas favoritas
     */
    public function shortcode_favoritas($atts) {
        $this->enqueue_assets();

        if (!is_user_logged_in()) {
            return '<p class="flavor-login-required">' . esc_html__('Debes iniciar sesión para ver tus favoritos.', 'flavor-chat-ia') . '</p>';
        }

        $user_id = get_current_user_id();
        $favoritos = get_user_meta($user_id, '_flavor_recetas_favoritas', true) ?: [];

        ob_start();
        ?>
        <div class="flavor-recetas-favoritas">
            <h2><?php esc_html_e('Mis Recetas Favoritas', 'flavor-chat-ia'); ?></h2>

            <?php if (empty($favoritos)): ?>
                <div class="flavor-empty-state">
                    <span class="dashicons dashicons-heart"></span>
                    <p><?php esc_html_e('No tienes recetas favoritas aún.', 'flavor-chat-ia'); ?></p>
                    <a href="<?php echo esc_url(home_url('/recetas/')); ?>" class="flavor-btn flavor-btn-primary">
                        <?php esc_html_e('Explorar recetas', 'flavor-chat-ia'); ?>
                    </a>
                </div>
            <?php else:
                $recetas = get_posts([
                    'post_type' => $this->post_type,
                    'post__in' => $favoritos,
                    'posts_per_page' => -1,
                ]);
            ?>
                <div class="flavor-recetas-grid">
                    <?php foreach ($recetas as $receta):
                        $tiempo = get_post_meta($receta->ID, '_receta_tiempo_preparacion', true);
                        $dificultad = get_post_meta($receta->ID, '_receta_dificultad', true);
                    ?>
                        <article class="flavor-receta-card" data-id="<?php echo esc_attr($receta->ID); ?>">
                            <div class="flavor-receta-imagen">
                                <?php if (has_post_thumbnail($receta->ID)): ?>
                                    <?php echo get_the_post_thumbnail($receta->ID, 'medium'); ?>
                                <?php else: ?>
                                    <div class="flavor-receta-placeholder">
                                        <span class="dashicons dashicons-carrot"></span>
                                    </div>
                                <?php endif; ?>
                                <button type="button" class="flavor-btn-favorito activo" data-receta-id="<?php echo esc_attr($receta->ID); ?>">
                                    <span class="dashicons dashicons-heart"></span>
                                </button>
                            </div>
                            <div class="flavor-receta-content">
                                <h4><a href="<?php echo get_permalink($receta->ID); ?>"><?php echo esc_html($receta->post_title); ?></a></h4>
                                <div class="flavor-receta-meta">
                                    <?php if ($tiempo): ?>
                                        <span><span class="dashicons dashicons-clock"></span> <?php echo esc_html($tiempo); ?> min</span>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </article>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
        <?php
        return ob_get_clean();
    }

    /**
     * Shortcode: Crear receta
     */
    public function shortcode_crear($atts) {
        $this->enqueue_assets();

        if (!is_user_logged_in()) {
            return '<p class="flavor-login-required">' . esc_html__('Debes iniciar sesión para crear recetas.', 'flavor-chat-ia') . '</p>';
        }

        ob_start();
        ?>
        <div class="flavor-crear-receta">
            <form id="form-crear-receta" class="flavor-form" enctype="multipart/form-data">
                <?php wp_nonce_field('flavor_recetas_nonce', 'recetas_nonce'); ?>

                <div class="flavor-form-row">
                    <label for="titulo"><?php esc_html_e('Nombre de la receta *', 'flavor-chat-ia'); ?></label>
                    <input type="text" name="titulo" id="titulo" class="flavor-input" required>
                </div>

                <div class="flavor-form-row">
                    <label for="descripcion"><?php esc_html_e('Descripción', 'flavor-chat-ia'); ?></label>
                    <textarea name="descripcion" id="descripcion" class="flavor-textarea" rows="3"></textarea>
                </div>

                <div class="flavor-form-row">
                    <label for="imagen"><?php esc_html_e('Imagen', 'flavor-chat-ia'); ?></label>
                    <input type="file" name="imagen" id="imagen" accept="image/*">
                </div>

                <div class="flavor-form-grid flavor-form-grid-3">
                    <div class="flavor-form-row">
                        <label for="tiempo"><?php esc_html_e('Tiempo (minutos)', 'flavor-chat-ia'); ?></label>
                        <input type="number" name="tiempo" id="tiempo" class="flavor-input" min="1">
                    </div>
                    <div class="flavor-form-row">
                        <label for="porciones"><?php esc_html_e('Porciones', 'flavor-chat-ia'); ?></label>
                        <input type="number" name="porciones" id="porciones" class="flavor-input" min="1">
                    </div>
                    <div class="flavor-form-row">
                        <label for="dificultad"><?php esc_html_e('Dificultad', 'flavor-chat-ia'); ?></label>
                        <select name="dificultad" id="dificultad" class="flavor-select">
                            <?php foreach ($this->dificultades as $key => $dif): ?>
                                <option value="<?php echo esc_attr($key); ?>"><?php echo esc_html($dif['icono'] . ' ' . $dif['nombre']); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>

                <div class="flavor-form-row">
                    <label for="categoria"><?php esc_html_e('Categoría', 'flavor-chat-ia'); ?></label>
                    <select name="categoria" id="categoria" class="flavor-select">
                        <option value=""><?php esc_html_e('Selecciona una categoría', 'flavor-chat-ia'); ?></option>
                        <?php
                        $categorias = get_terms(['taxonomy' => 'receta_categoria', 'hide_empty' => false]);
                        foreach ($categorias as $categoria):
                        ?>
                            <option value="<?php echo esc_attr($categoria->term_id); ?>"><?php echo esc_html($categoria->name); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="flavor-form-row">
                    <label><?php esc_html_e('Ingredientes', 'flavor-chat-ia'); ?></label>
                    <div id="ingredientes-container">
                        <div class="flavor-ingrediente-row">
                            <input type="text" name="ingredientes[]" placeholder="<?php esc_attr_e('Ej: 2 tazas de harina', 'flavor-chat-ia'); ?>" class="flavor-input">
                            <button type="button" class="flavor-btn-icon flavor-btn-quitar-ingrediente">
                                <span class="dashicons dashicons-minus"></span>
                            </button>
                        </div>
                    </div>
                    <button type="button" id="agregar-ingrediente" class="flavor-btn flavor-btn-sm flavor-btn-secondary">
                        <span class="dashicons dashicons-plus"></span> <?php esc_html_e('Agregar ingrediente', 'flavor-chat-ia'); ?>
                    </button>
                </div>

                <div class="flavor-form-row">
                    <label><?php esc_html_e('Pasos de preparación', 'flavor-chat-ia'); ?></label>
                    <div id="pasos-container">
                        <div class="flavor-paso-row">
                            <span class="flavor-paso-numero">1</span>
                            <textarea name="pasos[]" placeholder="<?php esc_attr_e('Describe el paso...', 'flavor-chat-ia'); ?>" class="flavor-textarea" rows="2"></textarea>
                            <button type="button" class="flavor-btn-icon flavor-btn-quitar-paso">
                                <span class="dashicons dashicons-minus"></span>
                            </button>
                        </div>
                    </div>
                    <button type="button" id="agregar-paso" class="flavor-btn flavor-btn-sm flavor-btn-secondary">
                        <span class="dashicons dashicons-plus"></span> <?php esc_html_e('Agregar paso', 'flavor-chat-ia'); ?>
                    </button>
                </div>

                <?php $this->render_form_productos_gc(); ?>

                <?php $this->render_form_videos(); ?>

                <div class="flavor-form-actions">
                    <button type="submit" class="flavor-btn flavor-btn-primary flavor-btn-lg">
                        <span class="dashicons dashicons-saved"></span>
                        <?php
                        echo esc_html(
                            $this->frontend_requires_moderation
                                ? __('Enviar Receta', 'flavor-chat-ia')
                                : __('Guardar Receta', 'flavor-chat-ia')
                        );
                        ?>
                    </button>
                </div>
            </form>
        </div>
        <?php
        return ob_get_clean();
    }

    /**
     * Renderiza el campo de productos de Grupos de Consumo en el formulario
     */
    private function render_form_productos_gc() {
        // Obtener productos disponibles
        $productos_gc = get_posts([
            'post_type' => 'gc_producto',
            'posts_per_page' => -1,
            'post_status' => 'publish',
            'orderby' => 'title',
            'order' => 'ASC',
        ]);

        // Si no hay productos o el CPT no existe, no mostrar
        if (empty($productos_gc)) {
            return;
        }
        ?>
        <div class="flavor-form-row">
            <label><?php esc_html_e('Productos del Grupo de Consumo', 'flavor-chat-ia'); ?></label>
            <p class="flavor-form-help"><?php esc_html_e('Selecciona los productos locales que se usan en esta receta.', 'flavor-chat-ia'); ?></p>
            <div class="flavor-chips-selector" id="gc-productos-chips">
                <?php foreach ($productos_gc as $producto):
                    $precio = get_post_meta($producto->ID, '_gc_precio', true);
                    $unidad = get_post_meta($producto->ID, '_gc_unidad', true);
                    $precio_str = $precio ? " · {$precio}€/" . ($unidad ?: 'ud') : '';
                ?>
                <label class="flavor-chip">
                    <input type="checkbox" name="gc_productos[]" value="<?php echo esc_attr($producto->ID); ?>">
                    <span class="flavor-chip-content">
                        <span class="flavor-chip-icon">🥬</span>
                        <span class="flavor-chip-text"><?php echo esc_html($producto->post_title); ?></span>
                        <?php if ($precio_str): ?>
                            <span class="flavor-chip-meta"><?php echo esc_html($precio_str); ?></span>
                        <?php endif; ?>
                    </span>
                </label>
                <?php endforeach; ?>
            </div>
        </div>
        <?php
        $this->render_chips_styles();
    }

    /**
     * Renderiza los estilos CSS para los chips (solo una vez)
     */
    private function render_chips_styles() {
        static $rendered = false;
        if ($rendered) {
            return;
        }
        $rendered = true;
        ?>
        <style>
            .flavor-chips-selector {
                display: flex;
                flex-wrap: wrap;
                gap: 8px;
                padding: 12px;
                background: #f8fafc;
                border: 1px solid #e2e8f0;
                border-radius: 8px;
                max-height: 200px;
                overflow-y: auto;
            }
            .flavor-chip {
                display: inline-flex;
                cursor: pointer;
                margin: 0;
            }
            .flavor-chip input[type="checkbox"] {
                position: absolute;
                opacity: 0;
                pointer-events: none;
            }
            .flavor-chip-content {
                display: inline-flex;
                align-items: center;
                gap: 6px;
                padding: 6px 12px;
                background: #fff;
                border: 1px solid #e2e8f0;
                border-radius: 20px;
                font-size: 13px;
                color: #475569;
                transition: all 0.15s ease;
            }
            .flavor-chip:hover .flavor-chip-content {
                border-color: #94a3b8;
                background: #f1f5f9;
            }
            .flavor-chip input:checked + .flavor-chip-content {
                background: #10b981;
                border-color: #10b981;
                color: #fff;
            }
            .flavor-chip input:checked + .flavor-chip-content .flavor-chip-meta {
                color: rgba(255,255,255,0.8);
            }
            .flavor-chip-icon {
                font-size: 14px;
            }
            .flavor-chip-text {
                font-weight: 500;
            }
            .flavor-chip-meta {
                font-size: 11px;
                color: #94a3b8;
            }
            .flavor-chip--video .flavor-chip-content {
                background: #f0f9ff;
                border-color: #bae6fd;
            }
            .flavor-chip--video:hover .flavor-chip-content {
                background: #e0f2fe;
            }
            .flavor-chip--video input:checked + .flavor-chip-content {
                background: #0ea5e9;
                border-color: #0ea5e9;
            }
        </style>
        <?php
    }

    /**
     * Renderiza el campo de videos en el formulario
     */
    private function render_form_videos() {
        global $wpdb;
        $tabla_multimedia = $wpdb->prefix . 'flavor_multimedia';

        // Verificar que la tabla existe
        if (!Flavor_Chat_Helpers::tabla_existe($tabla_multimedia)) {
            return;
        }

        // Obtener videos disponibles
        $videos = $wpdb->get_results(
            "SELECT id, titulo FROM {$tabla_multimedia}
             WHERE tipo = 'video' AND estado IN ('publico', 'comunidad')
             ORDER BY fecha_creacion DESC
             LIMIT 50"
        );

        // Si no hay videos, no mostrar
        if (empty($videos)) {
            return;
        }
        ?>
        <div class="flavor-form-row">
            <label><?php esc_html_e('Videos de la Receta', 'flavor-chat-ia'); ?></label>
            <p class="flavor-form-help"><?php esc_html_e('Vincula videos tutoriales o de preparación.', 'flavor-chat-ia'); ?></p>
            <div class="flavor-chips-selector" id="videos-chips">
                <?php foreach ($videos as $video): ?>
                <label class="flavor-chip flavor-chip--video">
                    <input type="checkbox" name="videos[]" value="<?php echo esc_attr($video->id); ?>">
                    <span class="flavor-chip-content">
                        <span class="flavor-chip-icon">🎬</span>
                        <span class="flavor-chip-text"><?php echo esc_html($video->titulo); ?></span>
                    </span>
                </label>
                <?php endforeach; ?>
            </div>
        </div>
        <?php
    }

    /**
     * Shortcode: Recetas destacadas
     */
    public function shortcode_destacadas($atts) {
        $this->enqueue_assets();

        $atts = shortcode_atts([
            'limite' => 8,
        ], $atts);

        $recetas = get_posts([
            'post_type' => $this->post_type,
            'posts_per_page' => intval($atts['limite']),
            'meta_key' => '_receta_destacada',
            'meta_value' => '1',
        ]);

        if (empty($recetas)) {
            // Si no hay destacadas, mostrar las más valoradas
            $recetas = get_posts([
                'post_type' => $this->post_type,
                'posts_per_page' => intval($atts['limite']),
                'orderby' => 'meta_value_num',
                'meta_key' => '_receta_valoracion_promedio',
                'order' => 'DESC',
            ]);
        }

        ob_start();
        ?>
        <div class="flavor-recetas-destacadas">
            <div class="flavor-recetas-grid">
                <?php foreach ($recetas as $receta):
                    $tiempo = get_post_meta($receta->ID, '_receta_tiempo_preparacion', true);
                    $dificultad = get_post_meta($receta->ID, '_receta_dificultad', true);
                    $valoracion = get_post_meta($receta->ID, '_receta_valoracion_promedio', true);
                ?>
                    <article class="flavor-receta-card" data-id="<?php echo esc_attr($receta->ID); ?>">
                        <div class="flavor-receta-imagen">
                            <?php if (has_post_thumbnail($receta->ID)): ?>
                                <?php echo get_the_post_thumbnail($receta->ID, 'medium'); ?>
                            <?php else: ?>
                                <div class="flavor-receta-placeholder">
                                    <span class="dashicons dashicons-carrot"></span>
                                </div>
                            <?php endif; ?>
                            <?php if ($valoracion): ?>
                                <span class="flavor-badge flavor-badge-valoracion">
                                    <span class="dashicons dashicons-star-filled"></span> <?php echo number_format_i18n($valoracion, 1); ?>
                                </span>
                            <?php endif; ?>
                        </div>
                        <div class="flavor-receta-content">
                            <h4><a href="<?php echo get_permalink($receta->ID); ?>"><?php echo esc_html($receta->post_title); ?></a></h4>
                            <div class="flavor-receta-meta">
                                <?php if ($tiempo): ?>
                                    <span><span class="dashicons dashicons-clock"></span> <?php echo esc_html($tiempo); ?> min</span>
                                <?php endif; ?>
                                <?php if (!empty($dificultad) && isset($this->dificultades[$dificultad])): ?>
                                    <span style="color:<?php echo esc_attr($this->dificultades[$dificultad]['color']); ?>">
                                        <?php echo esc_html($this->dificultades[$dificultad]['nombre']); ?>
                                    </span>
                                <?php endif; ?>
                            </div>
                        </div>
                    </article>
                <?php endforeach; ?>
            </div>
        </div>
        <?php
        return ob_get_clean();
    }

    /**
     * Shortcode: Recetas por ingrediente
     */
    public function shortcode_por_ingrediente($atts) {
        $this->enqueue_assets();

        $atts = shortcode_atts([
            'ingrediente' => '',
            'limite' => 12,
        ], $atts);

        $ingrediente = sanitize_text_field($atts['ingrediente']);
        if (empty($ingrediente) && isset($_GET['ingrediente'])) {
            $ingrediente = sanitize_text_field($_GET['ingrediente']);
        }

        if (empty($ingrediente)) {
            return '<p class="flavor-notice">' . esc_html__('No se especificó un ingrediente.', 'flavor-chat-ia') . '</p>';
        }

        global $wpdb;
        $recetas = $wpdb->get_col($wpdb->prepare(
            "SELECT post_id FROM {$wpdb->postmeta}
             WHERE meta_key = '_receta_ingredientes'
             AND meta_value LIKE %s
             LIMIT %d",
            '%' . $wpdb->esc_like($ingrediente) . '%',
            intval($atts['limite'])
        ));

        ob_start();
        ?>
        <div class="flavor-recetas-ingrediente">
            <h2><?php printf(esc_html__('Recetas con "%s"', 'flavor-chat-ia'), esc_html($ingrediente)); ?></h2>

            <?php if (empty($recetas)): ?>
                <div class="flavor-empty-state">
                    <span class="dashicons dashicons-carrot"></span>
                    <p><?php esc_html_e('No se encontraron recetas con ese ingrediente.', 'flavor-chat-ia'); ?></p>
                </div>
            <?php else: ?>
                <div class="flavor-recetas-grid">
                    <?php foreach ($recetas as $receta_id):
                        $receta = get_post($receta_id);
                        if (!$receta || $receta->post_status !== 'publish') continue;
                        $tiempo = get_post_meta($receta_id, '_receta_tiempo_preparacion', true);
                    ?>
                        <article class="flavor-receta-card" data-id="<?php echo esc_attr($receta_id); ?>">
                            <div class="flavor-receta-imagen">
                                <?php if (has_post_thumbnail($receta_id)): ?>
                                    <?php echo get_the_post_thumbnail($receta_id, 'medium'); ?>
                                <?php else: ?>
                                    <div class="flavor-receta-placeholder">
                                        <span class="dashicons dashicons-carrot"></span>
                                    </div>
                                <?php endif; ?>
                            </div>
                            <div class="flavor-receta-content">
                                <h4><a href="<?php echo get_permalink($receta_id); ?>"><?php echo esc_html($receta->post_title); ?></a></h4>
                                <?php if ($tiempo): ?>
                                    <span><span class="dashicons dashicons-clock"></span> <?php echo esc_html($tiempo); ?> min</span>
                                <?php endif; ?>
                            </div>
                        </article>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
        <?php
        return ob_get_clean();
    }

    /**
     * Shortcode: Dashboard completo
     */
    public function shortcode_dashboard($atts) {
        $this->enqueue_assets();
        ob_start();
        $this->render_tab_recetas();
        return ob_get_clean();
    }

    /**
     * AJAX: Guardar receta
     */
    public function ajax_guardar() {
        check_ajax_referer('flavor_recetas_nonce', 'recetas_nonce');

        if (!is_user_logged_in()) {
            wp_send_json_error(['message' => __('Debes iniciar sesión', 'flavor-chat-ia')]);
        }

        $titulo = sanitize_text_field($_POST['titulo'] ?? '');
        if (empty($titulo)) {
            wp_send_json_error(['message' => __('El título es obligatorio', 'flavor-chat-ia')]);
        }

        $user_id = get_current_user_id();
        $receta_id = intval($_POST['receta_id'] ?? 0);
        $can_publish_directly = current_user_can('edit_posts');
        $post_status = $can_publish_directly ? 'publish' : ($this->frontend_requires_moderation ? 'pending' : 'publish');

        $datos_receta = [
            'post_title' => $titulo,
            'post_content' => wp_kses_post($_POST['descripcion'] ?? ''),
            'post_type' => $this->post_type,
            'post_status' => $post_status,
            'post_author' => $user_id,
        ];

        if ($receta_id) {
            // Verificar propiedad
            $receta_existente = get_post($receta_id);
            if (!$receta_existente || $receta_existente->post_author != $user_id) {
                wp_send_json_error(['message' => __('No tienes permiso para editar esta receta', 'flavor-chat-ia')]);
            }
            $datos_receta['ID'] = $receta_id;
            $receta_id = wp_update_post($datos_receta, true);
        } else {
            $receta_id = wp_insert_post($datos_receta, true);
        }

        if (is_wp_error($receta_id) || empty($receta_id)) {
            $error = is_wp_error($receta_id) ? $receta_id->get_error_message() : __('No se pudo guardar la receta.', 'flavor-chat-ia');
            wp_send_json_error(['message' => $error]);
        }

        // Guardar metas
        update_post_meta($receta_id, '_receta_tiempo_preparacion', intval($_POST['tiempo'] ?? 0));
        update_post_meta($receta_id, '_receta_porciones', intval($_POST['porciones'] ?? 0));
        update_post_meta($receta_id, '_receta_dificultad', sanitize_key($_POST['dificultad'] ?? 'facil'));

        // Ingredientes
        $ingredientes = array_filter(array_map('sanitize_text_field', $_POST['ingredientes'] ?? []));
        update_post_meta($receta_id, '_receta_ingredientes', $ingredientes);

        // Pasos
        $pasos = array_filter(array_map('sanitize_textarea_field', $_POST['pasos'] ?? []));
        update_post_meta($receta_id, '_receta_pasos', $pasos);

        // Categoría
        if (!empty($_POST['categoria'])) {
            wp_set_object_terms($receta_id, [intval($_POST['categoria'])], 'receta_categoria');
        }

        // Imagen
        if (!empty($_FILES['imagen']['tmp_name'])) {
            require_once(ABSPATH . 'wp-admin/includes/file.php');
            require_once(ABSPATH . 'wp-admin/includes/image.php');
            require_once(ABSPATH . 'wp-admin/includes/media.php');

            $attachment_id = media_handle_upload('imagen', $receta_id);
            if (!is_wp_error($attachment_id)) {
                set_post_thumbnail($receta_id, $attachment_id);
            }
        }

        // Productos de Grupos de Consumo
        if (!empty($_POST['gc_productos']) && is_array($_POST['gc_productos'])) {
            $gc_productos = array_map('absint', $_POST['gc_productos']);
            // Filtrar solo los que realmente son gc_producto
            $gc_productos = array_filter($gc_productos, function($id) {
                $post = get_post($id);
                return $post && $post->post_type === 'gc_producto';
            });
            update_post_meta($receta_id, '_receta_gc_productos', array_values($gc_productos));
        } else {
            update_post_meta($receta_id, '_receta_gc_productos', []);
        }

        // Videos del módulo multimedia
        if (!empty($_POST['videos']) && is_array($_POST['videos'])) {
            global $wpdb;
            $tabla_multimedia = $wpdb->prefix . 'flavor_multimedia';
            $videos = array_map('absint', $_POST['videos']);
            // Verificar que los videos existen
            if (Flavor_Chat_Helpers::tabla_existe($tabla_multimedia)) {
                $videos = array_filter($videos, function($id) use ($wpdb, $tabla_multimedia) {
                    return (bool) $wpdb->get_var($wpdb->prepare(
                        "SELECT id FROM {$tabla_multimedia} WHERE id = %d AND tipo = 'video'",
                        $id
                    ));
                });
            }
            update_post_meta($receta_id, '_receta_videos', array_values($videos));
        } else {
            update_post_meta($receta_id, '_receta_videos', []);
        }

        $message = $post_status === 'pending'
            ? __('Receta enviada para revisión. Aparecerá publicada cuando un moderador la apruebe.', 'flavor-chat-ia')
            : __('Receta guardada correctamente', 'flavor-chat-ia');

        $redirect_url = $post_status === 'publish'
            ? get_permalink($receta_id)
            : home_url('/mi-portal/recetas/mis-recetas/');

        wp_send_json_success([
            'message' => $message,
            'receta_id' => $receta_id,
            'status' => $post_status,
            'url' => $redirect_url,
        ]);
    }

    /**
     * AJAX: Favorito
     */
    public function ajax_favorito() {
        check_ajax_referer('flavor_recetas_nonce', 'nonce');

        if (!is_user_logged_in()) {
            wp_send_json_error(['message' => __('Debes iniciar sesión', 'flavor-chat-ia')]);
        }

        $receta_id = intval($_POST['receta_id'] ?? 0);
        if (!$receta_id) {
            wp_send_json_error(['message' => __('Receta no válida', 'flavor-chat-ia')]);
        }

        $user_id = get_current_user_id();
        $favoritos = get_user_meta($user_id, '_flavor_recetas_favoritas', true) ?: [];

        $es_favorito = in_array($receta_id, $favoritos);

        if ($es_favorito) {
            $favoritos = array_diff($favoritos, [$receta_id]);
            $mensaje = __('Quitado de favoritos', 'flavor-chat-ia');
        } else {
            $favoritos[] = $receta_id;
            $mensaje = __('Agregado a favoritos', 'flavor-chat-ia');
        }

        update_user_meta($user_id, '_flavor_recetas_favoritas', array_values($favoritos));

        wp_send_json_success([
            'message' => $mensaje,
            'es_favorito' => !$es_favorito,
        ]);
    }

    /**
     * AJAX: Valorar
     */
    public function ajax_valorar() {
        check_ajax_referer('flavor_recetas_nonce', 'nonce');

        $receta_id = intval($_POST['receta_id'] ?? 0);
        $valoracion = intval($_POST['valoracion'] ?? 0);

        if (!$receta_id || $valoracion < 1 || $valoracion > 5) {
            wp_send_json_error(['message' => __('Datos inválidos', 'flavor-chat-ia')]);
        }

        global $wpdb;
        $tabla = $wpdb->prefix . 'flavor_recetas_valoraciones';

        if (!Flavor_Chat_Helpers::tabla_existe($tabla)) {
            // Guardar en post meta como fallback
            $valoraciones = get_post_meta($receta_id, '_receta_valoraciones', true) ?: [];
            $valoraciones[] = $valoracion;
            update_post_meta($receta_id, '_receta_valoraciones', $valoraciones);

            $promedio = array_sum($valoraciones) / count($valoraciones);
            update_post_meta($receta_id, '_receta_valoracion_promedio', round($promedio, 1));
        } else {
            $user_id = is_user_logged_in() ? get_current_user_id() : 0;
            $ip = $_SERVER['REMOTE_ADDR'] ?? '';

            // Verificar si ya valoró
            $existe = $wpdb->get_var($wpdb->prepare(
                "SELECT id FROM {$tabla} WHERE receta_id = %d AND (usuario_id = %d OR ip = %s)",
                $receta_id, $user_id, $ip
            ));

            if ($existe) {
                $wpdb->update($tabla, ['valoracion' => $valoracion, 'fecha' => current_time('mysql')], ['id' => $existe]);
            } else {
                $wpdb->insert($tabla, [
                    'receta_id' => $receta_id,
                    'usuario_id' => $user_id,
                    'valoracion' => $valoracion,
                    'ip' => $ip,
                    'fecha' => current_time('mysql'),
                ]);
            }

            // Actualizar promedio
            $promedio = $wpdb->get_var($wpdb->prepare(
                "SELECT AVG(valoracion) FROM {$tabla} WHERE receta_id = %d",
                $receta_id
            ));
            update_post_meta($receta_id, '_receta_valoracion_promedio', round($promedio, 1));
        }

        wp_send_json_success([
            'message' => __('Gracias por tu valoración', 'flavor-chat-ia'),
            'promedio' => round($promedio, 1),
        ]);
    }

    /**
     * AJAX: Buscar recetas
     */
    public function ajax_buscar() {
        check_ajax_referer('flavor_recetas_nonce', 'nonce');

        $busqueda = sanitize_text_field($_POST['busqueda'] ?? '');
        $categoria = sanitize_text_field($_POST['categoria'] ?? '');
        $dificultad = sanitize_key($_POST['dificultad'] ?? '');

        $args = [
            'post_type' => $this->post_type,
            'posts_per_page' => 20,
            'post_status' => 'publish',
        ];

        if (!empty($busqueda)) {
            $args['s'] = $busqueda;
        }

        if (!empty($categoria)) {
            $args['tax_query'] = [[
                'taxonomy' => 'receta_categoria',
                'field' => 'slug',
                'terms' => $categoria,
            ]];
        }

        if (!empty($dificultad)) {
            $args['meta_query'] = [[
                'key' => '_receta_dificultad',
                'value' => $dificultad,
            ]];
        }

        $recetas = get_posts($args);
        $resultados = [];

        foreach ($recetas as $receta) {
            $resultados[] = [
                'id' => $receta->ID,
                'titulo' => $receta->post_title,
                'url' => get_permalink($receta->ID),
                'imagen' => get_the_post_thumbnail_url($receta->ID, 'medium') ?: '',
                'tiempo' => get_post_meta($receta->ID, '_receta_tiempo_preparacion', true),
                'dificultad' => get_post_meta($receta->ID, '_receta_dificultad', true),
            ];
        }

        wp_send_json_success(['recetas' => $resultados]);
    }

    /**
     * AJAX: Dashboard data
     */
    public function ajax_dashboard_data() {
        check_ajax_referer('flavor_recetas_nonce', 'nonce');

        if (!is_user_logged_in()) {
            wp_send_json_error(['message' => __('Debes iniciar sesión', 'flavor-chat-ia')]);
        }

        $user_id = get_current_user_id();

        $mis_recetas = count(get_posts([
            'post_type' => $this->post_type,
            'author' => $user_id,
            'post_status' => 'any',
            'posts_per_page' => -1,
            'fields' => 'ids',
        ]));

        $favoritos = get_user_meta($user_id, '_flavor_recetas_favoritas', true) ?: [];

        wp_send_json_success([
            'mis_recetas' => $mis_recetas,
            'favoritas' => count($favoritos),
        ]);
    }
}

// Inicializar
Flavor_Recetas_Frontend_Controller::get_instance();

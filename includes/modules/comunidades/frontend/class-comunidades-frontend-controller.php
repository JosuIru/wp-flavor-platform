<?php
/**
 * Frontend Controller para Comunidades
 *
 * @package FlavorPlatform
 * @subpackage Modules\Comunidades
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Controlador Frontend para el módulo de Comunidades
 */
class Flavor_Comunidades_Frontend_Controller {

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

        // Dashboard tabs
        add_filter('flavor_user_dashboard_tabs', [$this, 'registrar_tabs'], 10, 1);

        // AJAX handlers
        add_action('wp_ajax_comunidades_crear', [$this, 'ajax_crear_comunidad']);
        add_action('wp_ajax_comunidades_unirse', [$this, 'ajax_unirse']);
        add_action('wp_ajax_comunidades_salir', [$this, 'ajax_salir']);
        add_action('wp_ajax_comunidades_publicar', [$this, 'ajax_publicar']);
        add_action('wp_ajax_comunidades_comentar', [$this, 'ajax_comentar']);
        add_action('wp_ajax_comunidades_listar', [$this, 'ajax_listar']);
        add_action('wp_ajax_nopriv_comunidades_listar', [$this, 'ajax_listar']);
        add_action('wp_ajax_comunidades_obtener_feed', [$this, 'ajax_obtener_feed']);
    }

    /**
     * Registrar assets
     */
    public function registrar_assets() {
        $base_url = plugins_url('assets/', dirname(dirname(__FILE__)));
        $version = FLAVOR_PLATFORM_VERSION ?? '1.0.0';

        wp_register_style(
            'flavor-comunidades',
            $base_url . 'css/comunidades.css',
            ['flavor-modules-common'],
            $version
        );

        wp_register_script(
            'flavor-comunidades',
            $base_url . 'js/comunidades.js',
            ['jquery'],
            $version,
            true
        );

        wp_localize_script('flavor-comunidades', 'flavorComunidades', [
            'ajaxUrl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('comunidades_nonce'),
            'i18n' => [
                'unido' => __('Te has unido a la comunidad', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'salido' => __('Has abandonado la comunidad', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'publicado' => __('Publicación creada', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'error' => __('Ha ocurrido un error', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'cargando' => __('Cargando...', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'confirmar_salir' => __('¿Confirmas que quieres abandonar esta comunidad?', FLAVOR_PLATFORM_TEXT_DOMAIN),
            ],
        ]);
    }

    /**
     * Encolar assets
     */
    public function encolar_assets() {
        wp_enqueue_style('flavor-comunidades');
        wp_enqueue_script('flavor-comunidades');
    }

    /**
     * Campos compatibles con el esquema actual de comunidades.
     */
    private function get_comunidad_select_sql($alias = '') {
        global $wpdb;

        $prefix = $alias ? $alias . '.' : '';
        $tabla_actividad = $wpdb->prefix . 'flavor_comunidades_actividad';

        return "{$alias}.*,
            {$prefix}imagen AS imagen_portada,
            {$prefix}imagen AS imagen_perfil,
            LEFT(COALESCE({$prefix}descripcion, ''), 200) AS descripcion_corta,
            CASE
                WHEN {$prefix}tipo = 'abierta' THEN 'publica'
                WHEN {$prefix}tipo = 'cerrada' THEN 'visible'
                ELSE 'privada'
            END AS privacidad,
            0 AS verificada,
            (
                SELECT COUNT(*)
                FROM {$tabla_actividad} act
                WHERE act.comunidad_id = {$prefix}id
                  AND act.tipo = 'publicacion'
            ) AS publicaciones_count";
    }

    /**
     * Mapea la privacidad legacy al tipo actual.
     */
    private function map_privacidad_to_tipo($privacidad) {
        switch ($privacidad) {
            case 'privada':
                return 'secreta';
            case 'visible':
                return 'cerrada';
            case 'publica':
            default:
                return 'abierta';
        }
    }

    /**
     * Registrar shortcodes
     */
    public function registrar_shortcodes() {
        $shortcodes = [
            'comunidades_listado' => 'shortcode_listado',
            'comunidades_detalle' => 'shortcode_detalle',
            'comunidades_crear' => 'shortcode_crear',
            'comunidades_mis_comunidades' => 'shortcode_mis_comunidades',
            'comunidades_feed' => 'shortcode_feed',
            'comunidades_miembros' => 'shortcode_miembros',
            'comunidades_marketplace' => 'shortcode_marketplace',
        ];

        foreach ($shortcodes as $tag => $method) {
            if (!shortcode_exists($tag)) {
                add_shortcode($tag, [$this, $method]);
            }
        }
    }

    /**
     * Registrar tabs en dashboard
     */
    public function registrar_tabs($tabs) {
        $tabs['comunidades-explorar'] = [
            'label' => __('Comunidades', FLAVOR_PLATFORM_TEXT_DOMAIN),
            'icon' => 'groups',
            'callback' => [$this, 'render_tab_explorar'],
            'orden' => 25,
        ];

        $tabs['comunidades-mis'] = [
            'label' => __('Mis Comunidades', FLAVOR_PLATFORM_TEXT_DOMAIN),
            'icon' => 'networking',
            'callback' => [$this, 'render_tab_mis_comunidades'],
            'orden' => 26,
        ];

        return $tabs;
    }

    /**
     * Tab: Explorar comunidades
     */
    public function render_tab_explorar() {
        $this->encolar_assets();
        echo $this->shortcode_listado(['limite' => 12, 'mostrar_crear' => 'true']);
    }

    /**
     * Tab: Mis comunidades
     */
    public function render_tab_mis_comunidades() {
        $this->encolar_assets();
        echo $this->shortcode_mis_comunidades([]);
    }

    /**
     * Shortcode: Listado de comunidades
     */
    public function shortcode_listado($atts) {
        $this->encolar_assets();

        $atts = shortcode_atts([
            'tipo' => '',
            'categoria' => '',
            'limite' => 12,
            'mostrar_crear' => 'false',
            'mostrar_filtros' => 'true',
            // Parámetros visuales (VBP)
            'esquema_color' => 'default',
            'estilo_tarjeta' => 'elevated',
            'radio_bordes' => 'lg',
            'animacion_entrada' => 'fade',
            'orderby' => 'miembros',
            'order' => 'DESC',
        ], $atts);

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

        global $wpdb;
        $tabla_comunidades = $wpdb->prefix . 'flavor_comunidades';

        if (!Flavor_Platform_Helpers::tabla_existe($tabla_comunidades)) {
            return '<p class="flavor-error">' . __('El módulo no está configurado.', FLAVOR_PLATFORM_TEXT_DOMAIN) . '</p>';
        }

        $where = "estado = 'activa' AND tipo IN ('abierta', 'cerrada')";
        $params = [];

        if (!empty($atts['tipo'])) {
            $where .= " AND tipo = %s";
            $params[] = $atts['tipo'];
        }

        if (!empty($atts['categoria'])) {
            $where .= " AND categoria = %s";
            $params[] = $atts['categoria'];
        }

        // Mapeo de orderby para comunidades
        $orderby_map = [
            'miembros' => 'miembros_count DESC',
            'fecha' => 'created_at',
            'date' => 'created_at',
            'nombre' => 'nombre',
            'title' => 'nombre',
            'actividad' => 'updated_at',
        ];
        $orderby_column = $orderby_map[$atts['orderby']] ?? 'miembros_count DESC';
        $order = strtoupper($atts['order']) === 'ASC' ? 'ASC' : 'DESC';

        $sql = "SELECT " . $this->get_comunidad_select_sql() . " FROM {$tabla_comunidades} WHERE {$where} ORDER BY {$orderby_column} {$order}, created_at DESC LIMIT %d";
        $params[] = intval($atts['limite']);

        $comunidades = $wpdb->get_results($wpdb->prepare($sql, ...$params));

        // Obtener categorías para filtros
        $categorias = $wpdb->get_col("SELECT DISTINCT categoria FROM {$tabla_comunidades} WHERE categoria IS NOT NULL AND categoria != '' ORDER BY categoria");

        ob_start();
        ?>
        <div class="flavor-comunidades-listado <?php echo esc_attr($visual_class_string); ?>">
            <?php if ($atts['mostrar_crear'] === 'true' && is_user_logged_in()): ?>
                <div class="comunidades-header">
                    <a href="<?php echo esc_url(home_url('/mi-portal/comunidades/crear/')); ?>" class="flavor-btn flavor-btn-primary">
                        <span class="dashicons dashicons-plus-alt2"></span>
                        <?php esc_html_e('Crear Comunidad', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                    </a>
                </div>
            <?php endif; ?>

            <?php if ($atts['mostrar_filtros'] === 'true' && !empty($categorias)): ?>
                <div class="comunidades-filtros">
                    <select id="filtro-categoria-comunidades" class="filtro-select">
                        <option value=""><?php esc_html_e('Todas las categorías', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></option>
                        <?php foreach ($categorias as $cat): ?>
                            <option value="<?php echo esc_attr($cat); ?>" <?php selected($atts['categoria'], $cat); ?>>
                                <?php echo esc_html($cat); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <input type="search" id="buscar-comunidades" placeholder="<?php esc_attr_e('Buscar...', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>" class="filtro-input">
                </div>
            <?php endif; ?>

            <?php if (empty($comunidades)): ?>
                <div class="flavor-empty-state">
                    <span class="dashicons dashicons-groups"></span>
                    <p><?php esc_html_e('No hay comunidades disponibles.', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></p>
                    <?php if (is_user_logged_in()): ?>
                        <a href="<?php echo esc_url(home_url('/mi-portal/comunidades/crear/')); ?>" class="flavor-btn flavor-btn-primary">
                            <?php esc_html_e('Crear la primera', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                        </a>
                    <?php endif; ?>
                </div>
            <?php else: ?>
                <div class="flavor-grid flavor-grid-3" id="comunidades-grid">
                    <?php foreach ($comunidades as $comunidad): ?>
                        <?php $this->render_comunidad_card($comunidad); ?>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
        <?php
        return ob_get_clean();
    }

    /**
     * Renderizar tarjeta de comunidad
     */
    private function render_comunidad_card($comunidad) {
        $usuario_es_miembro = false;
        if (is_user_logged_in()) {
            global $wpdb;
            $tabla_miembros = $wpdb->prefix . 'flavor_comunidades_miembros';
            $usuario_es_miembro = $wpdb->get_var($wpdb->prepare(
                "SELECT id FROM {$tabla_miembros}
                 WHERE comunidad_id = %d AND user_id = %d AND estado = 'activo'",
                $comunidad->id,
                get_current_user_id()
            ));
        }
        ?>
        <div class="flavor-card comunidad-card" data-id="<?php echo esc_attr($comunidad->id); ?>">
            <?php if (!empty($comunidad->imagen_portada)): ?>
                <div class="flavor-card-image">
                    <img src="<?php echo esc_url($comunidad->imagen_portada); ?>" alt="<?php echo esc_attr($comunidad->nombre); ?>">
                </div>
            <?php else: ?>
                <div class="flavor-card-image comunidad-placeholder" style="background-color: <?php echo esc_attr($comunidad->color ?? '#6366f1'); ?>">
                    <span class="dashicons dashicons-groups"></span>
                </div>
            <?php endif; ?>

            <div class="flavor-card-body">
                <?php if ($comunidad->categoria): ?>
                    <span class="comunidad-categoria"><?php echo esc_html($comunidad->categoria); ?></span>
                <?php endif; ?>

                <h3><?php echo esc_html($comunidad->nombre); ?></h3>

                <?php if ($comunidad->descripcion_corta): ?>
                    <p class="comunidad-descripcion"><?php echo esc_html($comunidad->descripcion_corta); ?></p>
                <?php endif; ?>

                <div class="comunidad-stats">
                    <span class="stat">
                        <span class="dashicons dashicons-admin-users"></span>
                        <?php echo number_format_i18n($comunidad->miembros_count); ?> <?php esc_html_e('miembros', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                    </span>
                    <?php if ($comunidad->publicaciones_count > 0): ?>
                        <span class="stat">
                            <span class="dashicons dashicons-format-chat"></span>
                            <?php echo number_format_i18n($comunidad->publicaciones_count); ?>
                        </span>
                    <?php endif; ?>
                </div>

                <div class="comunidad-badges">
                    <?php if ($comunidad->privacidad === 'publica'): ?>
                        <span class="flavor-badge flavor-badge-success"><?php esc_html_e('Pública', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></span>
                    <?php elseif ($comunidad->privacidad === 'privada'): ?>
                        <span class="flavor-badge flavor-badge-warning"><?php esc_html_e('Privada', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></span>
                    <?php endif; ?>
                    <?php if ($comunidad->verificada): ?>
                        <span class="flavor-badge flavor-badge-primary"><?php esc_html_e('Verificada', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></span>
                    <?php endif; ?>
                </div>
            </div>

            <div class="flavor-card-footer">
                <a href="<?php echo esc_url(home_url('/mi-portal/comunidades/' . $comunidad->id . '/')); ?>" class="flavor-btn flavor-btn-outline">
                    <?php esc_html_e('Ver', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                </a>
                <?php if (is_user_logged_in()): ?>
                    <?php if ($usuario_es_miembro): ?>
                        <span class="miembro-badge">
                            <span class="dashicons dashicons-yes-alt"></span>
                            <?php esc_html_e('Miembro', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                        </span>
                    <?php elseif ($comunidad->privacidad === 'publica'): ?>
                        <button type="button" class="flavor-btn flavor-btn-primary btn-unirse" data-comunidad="<?php echo esc_attr($comunidad->id); ?>">
                            <?php esc_html_e('Unirse', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                        </button>
                    <?php else: ?>
                        <button type="button" class="flavor-btn flavor-btn-secondary btn-solicitar" data-comunidad="<?php echo esc_attr($comunidad->id); ?>">
                            <?php esc_html_e('Solicitar', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                        </button>
                    <?php endif; ?>
                <?php endif; ?>
            </div>
        </div>
        <?php
    }

    /**
     * Shortcode: Detalle de comunidad
     */
    public function shortcode_detalle($atts) {
        $this->encolar_assets();

        $atts = shortcode_atts([
            'id' => 0,
            'slug' => '',
        ], $atts);

        global $wpdb;
        $tabla_comunidades = $wpdb->prefix . 'flavor_comunidades';

        $comunidad = null;
        if ($atts['id']) {
            $comunidad = $wpdb->get_row($wpdb->prepare(
                "SELECT " . $this->get_comunidad_select_sql() . " FROM {$tabla_comunidades} WHERE id = %d",
                $atts['id']
            ));
        } elseif ($atts['slug']) {
            $comunidad = $wpdb->get_row($wpdb->prepare(
                "SELECT " . $this->get_comunidad_select_sql() . " FROM {$tabla_comunidades} WHERE slug = %s",
                $atts['slug']
            ));
        }

        if (!$comunidad) {
            return '<p class="flavor-error">' . __('Comunidad no encontrada.', FLAVOR_PLATFORM_TEXT_DOMAIN) . '</p>';
        }

        // Verificar si el usuario es miembro
        $es_miembro = false;
        $rol_miembro = null;
        $usuario_id = get_current_user_id();

        if ($usuario_id) {
            $tabla_miembros = $wpdb->prefix . 'flavor_comunidades_miembros';
            $membresia = $wpdb->get_row($wpdb->prepare(
                "SELECT rol FROM {$tabla_miembros}
                 WHERE comunidad_id = %d AND user_id = %d AND estado = 'activo'",
                $comunidad->id,
                $usuario_id
            ));
            if ($membresia) {
                $es_miembro = true;
                $rol_miembro = $membresia->rol;
            }
        }

        // Si es privada y no es miembro, mostrar información limitada
        if ($comunidad->privacidad === 'privada' && !$es_miembro) {
            ob_start();
            ?>
            <div class="flavor-comunidad-privada">
                <?php if (!empty($comunidad->imagen_portada)): ?>
                    <div class="comunidad-portada" style="background-image: url('<?php echo esc_url($comunidad->imagen_portada); ?>');"></div>
                <?php endif; ?>
                <div class="comunidad-info-basica">
                    <h1><?php echo esc_html($comunidad->nombre); ?></h1>
                    <p class="comunidad-privada-aviso">
                        <span class="dashicons dashicons-lock"></span>
                        <?php esc_html_e('Esta es una comunidad privada', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                    </p>
                    <?php if ($comunidad->descripcion_corta): ?>
                        <p><?php echo esc_html($comunidad->descripcion_corta); ?></p>
                    <?php endif; ?>
                    <p><?php echo number_format_i18n($comunidad->miembros_count); ?> <?php esc_html_e('miembros', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></p>
                    <?php if (is_user_logged_in()): ?>
                        <button type="button" class="flavor-btn flavor-btn-primary btn-solicitar-acceso" data-comunidad="<?php echo esc_attr($comunidad->id); ?>">
                            <?php esc_html_e('Solicitar Acceso', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                        </button>
                    <?php else: ?>
                        <p><?php esc_html_e('Inicia sesión para solicitar acceso.', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></p>
                    <?php endif; ?>
                </div>
            </div>
            <?php
            return ob_get_clean();
        }

        // Obtener últimas publicaciones
        $tabla_publicaciones = $wpdb->prefix . 'flavor_comunidades_actividad';
        $publicaciones = [];
        if (Flavor_Platform_Helpers::tabla_existe($tabla_publicaciones)) {
            $publicaciones = $wpdb->get_results($wpdb->prepare(
                "SELECT p.*, p.user_id AS autor_id, p.reacciones_count AS likes_count, u.display_name as autor_nombre
                 FROM {$tabla_publicaciones} p
                 LEFT JOIN {$wpdb->users} u ON p.user_id = u.ID
                 WHERE p.comunidad_id = %d AND p.tipo = 'publicacion'
                 ORDER BY p.es_fijado DESC, p.created_at DESC
                 LIMIT 20",
                $comunidad->id
            ));
        }

        // Obtener miembros destacados
        $tabla_miembros = $wpdb->prefix . 'flavor_comunidades_miembros';
        $miembros_destacados = [];
        if (Flavor_Platform_Helpers::tabla_existe($tabla_miembros)) {
            $miembros_destacados = $wpdb->get_results($wpdb->prepare(
                "SELECT m.*, m.user_id AS usuario_id, m.joined_at AS created_at, u.display_name, u.user_email
                 FROM {$tabla_miembros} m
                 LEFT JOIN {$wpdb->users} u ON m.user_id = u.ID
                 WHERE m.comunidad_id = %d AND m.estado = 'activo'
                 ORDER BY FIELD(m.rol, 'admin', 'moderador', 'miembro'), m.joined_at
                 LIMIT 12",
                $comunidad->id
            ));
        }

        $es_admin = $rol_miembro === 'admin' || $rol_miembro === 'moderador';

        ob_start();
        ?>
        <div class="flavor-comunidad-detalle" data-id="<?php echo esc_attr($comunidad->id); ?>">
            <?php if (!empty($comunidad->imagen_portada)): ?>
                <div class="comunidad-portada" style="background-image: url('<?php echo esc_url($comunidad->imagen_portada); ?>');">
                    <div class="portada-overlay"></div>
                </div>
            <?php endif; ?>

            <div class="comunidad-header">
                <?php if (!empty($comunidad->imagen_perfil)): ?>
                    <img src="<?php echo esc_url($comunidad->imagen_perfil); ?>" alt="" class="comunidad-avatar">
                <?php else: ?>
                    <div class="comunidad-avatar-placeholder" style="background-color: <?php echo esc_attr($comunidad->color ?? '#6366f1'); ?>">
                        <span class="dashicons dashicons-groups"></span>
                    </div>
                <?php endif; ?>

                <div class="comunidad-titulo">
                    <h1>
                        <?php echo esc_html($comunidad->nombre); ?>
                        <?php if ($comunidad->verificada): ?>
                            <span class="verificado-badge" title="<?php esc_attr_e('Comunidad verificada', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>">
                                <span class="dashicons dashicons-yes-alt"></span>
                            </span>
                        <?php endif; ?>
                    </h1>
                    <?php if ($comunidad->categoria): ?>
                        <span class="comunidad-categoria"><?php echo esc_html($comunidad->categoria); ?></span>
                    <?php endif; ?>
                </div>

                <div class="comunidad-acciones">
                    <?php if ($es_miembro): ?>
                        <?php if ($es_admin): ?>
                            <a href="<?php echo esc_url(add_query_arg(['comunidad_id' => intval($comunidad->id), 'tab' => 'miembros'], home_url('/mi-portal/comunidades/'))); ?>" class="flavor-btn flavor-btn-secondary">
                                <span class="dashicons dashicons-admin-generic"></span>
                                <?php esc_html_e('Administrar', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                            </a>
                        <?php endif; ?>
                        <button type="button" class="flavor-btn flavor-btn-outline btn-salir" data-comunidad="<?php echo esc_attr($comunidad->id); ?>">
                            <?php esc_html_e('Abandonar', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                        </button>
                    <?php elseif (is_user_logged_in()): ?>
                        <?php if ($comunidad->privacidad === 'publica'): ?>
                            <button type="button" class="flavor-btn flavor-btn-primary btn-unirse" data-comunidad="<?php echo esc_attr($comunidad->id); ?>">
                                <?php esc_html_e('Unirse', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                            </button>
                        <?php else: ?>
                            <button type="button" class="flavor-btn flavor-btn-primary btn-solicitar" data-comunidad="<?php echo esc_attr($comunidad->id); ?>">
                                <?php esc_html_e('Solicitar Acceso', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                            </button>
                        <?php endif; ?>
                    <?php endif; ?>
                </div>
            </div>

            <div class="comunidad-stats-bar">
                <div class="stat">
                    <span class="stat-valor"><?php echo number_format_i18n($comunidad->miembros_count); ?></span>
                    <span class="stat-label"><?php esc_html_e('Miembros', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></span>
                </div>
                <div class="stat">
                    <span class="stat-valor"><?php echo number_format_i18n($comunidad->publicaciones_count); ?></span>
                    <span class="stat-label"><?php esc_html_e('Publicaciones', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></span>
                </div>
                <div class="stat">
                    <span class="stat-valor"><?php echo date_i18n('M Y', strtotime($comunidad->created_at)); ?></span>
                    <span class="stat-label"><?php esc_html_e('Creada', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></span>
                </div>
            </div>

            <div class="comunidad-contenido">
                <div class="comunidad-main">
                    <?php if ($comunidad->descripcion): ?>
                        <div class="comunidad-descripcion-completa">
                            <h3><?php esc_html_e('Acerca de', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></h3>
                            <?php echo wp_kses_post($comunidad->descripcion); ?>
                        </div>
                    <?php endif; ?>

                    <?php if ($es_miembro): ?>
                        <div class="comunidad-publicar">
                            <form id="flavor-com-form-publicar" class="form-publicar">
                                <?php wp_nonce_field('comunidades_nonce', 'comunidades_nonce_field'); ?>
                                <input type="hidden" name="comunidad_id" value="<?php echo esc_attr($comunidad->id); ?>">
                                <textarea name="contenido" placeholder="<?php esc_attr_e('¿Qué quieres compartir con la comunidad?', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>" rows="3"></textarea>
                                <div class="publicar-opciones">
                                    <button type="submit" class="flavor-btn flavor-btn-primary">
                                        <?php esc_html_e('Publicar', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                                    </button>
                                </div>
                            </form>
                        </div>
                    <?php endif; ?>

                    <?php
                    // Sección Marketplace de la comunidad
                    $tiene_marketplace = class_exists('Flavor_Marketplace_Frontend_Controller');
                    if ($tiene_marketplace):
                    ?>
                    <div class="comunidad-marketplace-preview">
                        <?php echo $this->shortcode_marketplace(['comunidad_id' => $comunidad->id, 'limite' => 4, 'mostrar_formulario' => $es_miembro ? 'true' : 'false']); ?>
                    </div>
                    <?php endif; ?>

                    <div class="comunidad-feed">
                        <h3><?php esc_html_e('Publicaciones', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></h3>
                        <?php if (empty($publicaciones)): ?>
                            <div class="flavor-empty-state">
                                <p><?php esc_html_e('Aún no hay publicaciones.', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></p>
                            </div>
                        <?php else: ?>
                            <div class="publicaciones-lista">
                                <?php foreach ($publicaciones as $pub): ?>
                                    <div class="publicacion-item <?php echo $pub->fijado ? 'fijada' : ''; ?>">
                                        <?php if ($pub->fijado): ?>
                                            <span class="publicacion-fijada-badge">
                                                <span class="dashicons dashicons-admin-post"></span>
                                                <?php esc_html_e('Fijado', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                                            </span>
                                        <?php endif; ?>
                                        <div class="publicacion-header">
                                            <span class="publicacion-autor"><?php echo esc_html($pub->autor_nombre); ?></span>
                                            <span class="publicacion-fecha"><?php echo human_time_diff(strtotime($pub->created_at), current_time('timestamp')); ?></span>
                                        </div>
                                        <div class="publicacion-contenido">
                                            <?php echo wp_kses_post(nl2br($pub->contenido)); ?>
                                        </div>
                                        <?php if (!empty($pub->imagen)): ?>
                                            <div class="publicacion-imagen">
                                                <img src="<?php echo esc_url($pub->imagen); ?>" alt="">
                                            </div>
                                        <?php endif; ?>
                                        <div class="publicacion-acciones">
                                            <button class="btn-like" data-publicacion="<?php echo esc_attr($pub->id); ?>">
                                                <span class="dashicons dashicons-heart"></span>
                                                <span class="count"><?php echo intval($pub->likes_count); ?></span>
                                            </button>
                                            <button class="btn-comentar" data-publicacion="<?php echo esc_attr($pub->id); ?>">
                                                <span class="dashicons dashicons-admin-comments"></span>
                                                <span class="count"><?php echo intval($pub->comentarios_count); ?></span>
                                            </button>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>

                <div class="comunidad-sidebar">
                    <div class="sidebar-seccion">
                        <h4><?php esc_html_e('Miembros', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></h4>
                        <div class="miembros-grid">
                            <?php foreach ($miembros_destacados as $miembro): ?>
                                <div class="miembro-mini" title="<?php echo esc_attr($miembro->display_name); ?>">
                                    <?php echo get_avatar($miembro->user_email, 40); ?>
                                    <?php if ($miembro->rol === 'admin'): ?>
                                        <span class="rol-badge admin">A</span>
                                    <?php elseif ($miembro->rol === 'moderador'): ?>
                                        <span class="rol-badge mod">M</span>
                                    <?php endif; ?>
                                </div>
                            <?php endforeach; ?>
                        </div>
                <a href="<?php echo esc_url(add_query_arg(['comunidad_id' => intval($comunidad->id), 'tab' => 'miembros'], home_url('/mi-portal/comunidades/'))); ?>" class="ver-todos">
                            <?php esc_html_e('Ver todos', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                        </a>
                    </div>

                    <?php if ($comunidad->reglas): ?>
                        <div class="sidebar-seccion">
                            <h4><?php esc_html_e('Reglas', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></h4>
                            <div class="comunidad-reglas">
                                <?php echo wp_kses_post($comunidad->reglas); ?>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        <?php
        return ob_get_clean();
    }

    /**
     * Shortcode: Crear comunidad
     */
    public function shortcode_crear($atts) {
        if (!is_user_logged_in()) {
            return '<p class="flavor-login-required">' . __('Debes iniciar sesión para crear una comunidad.', FLAVOR_PLATFORM_TEXT_DOMAIN) . '</p>';
        }

        $this->encolar_assets();

        $categorias_disponibles = [
            'Vecindario', 'Deportes', 'Arte y Cultura', 'Tecnología', 'Medio Ambiente',
            'Educación', 'Salud', 'Emprendimiento', 'Voluntariado', 'Ocio', 'Otro'
        ];

        ob_start();
        ?>
        <div class="flavor-comunidades-crear">
            <h2><?php esc_html_e('Crear Nueva Comunidad', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></h2>

            <form id="flavor-com-form-crear" class="flavor-form" enctype="multipart/form-data">
                <?php wp_nonce_field('comunidades_nonce', 'comunidades_nonce_field'); ?>

                <div class="flavor-form-group">
                    <label for="nombre"><?php esc_html_e('Nombre de la comunidad', FLAVOR_PLATFORM_TEXT_DOMAIN); ?> *</label>
                    <input type="text" name="nombre" id="nombre" required maxlength="100">
                </div>

                <div class="flavor-form-group">
                    <label for="descripcion_corta"><?php esc_html_e('Descripción breve', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></label>
                    <input type="text" name="descripcion_corta" id="descripcion_corta" maxlength="200" placeholder="<?php esc_attr_e('Una línea que describa tu comunidad', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>">
                </div>

                <div class="flavor-form-group">
                    <label for="descripcion"><?php esc_html_e('Descripción completa', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></label>
                    <textarea name="descripcion" id="descripcion" rows="4"></textarea>
                </div>

                <div class="flavor-form-row">
                    <div class="flavor-form-group flavor-form-col-6">
                        <label for="categoria"><?php esc_html_e('Categoría', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></label>
                        <select name="categoria" id="categoria">
                            <option value=""><?php esc_html_e('Selecciona...', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></option>
                            <?php foreach ($categorias_disponibles as $cat): ?>
                                <option value="<?php echo esc_attr($cat); ?>"><?php echo esc_html($cat); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="flavor-form-group flavor-form-col-6">
                        <label for="privacidad"><?php esc_html_e('Privacidad', FLAVOR_PLATFORM_TEXT_DOMAIN); ?> *</label>
                        <select name="privacidad" id="privacidad" required>
                            <option value="publica"><?php esc_html_e('Pública - Cualquiera puede unirse', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></option>
                            <option value="visible"><?php esc_html_e('Visible - Requiere aprobación', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></option>
                            <option value="privada"><?php esc_html_e('Privada - Solo por invitación', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></option>
                        </select>
                    </div>
                </div>

                <div class="flavor-form-group">
                    <label for="imagen_portada"><?php esc_html_e('Imagen de portada', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></label>
                    <input type="file" name="imagen_portada" id="imagen_portada" accept="image/*">
                </div>

                <div class="flavor-form-group">
                    <label for="reglas"><?php esc_html_e('Reglas de la comunidad', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></label>
                    <textarea name="reglas" id="reglas" rows="3" placeholder="<?php esc_attr_e('Opcional: Define las normas de convivencia', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>"></textarea>
                </div>

                <div class="flavor-form-actions">
                    <button type="submit" class="flavor-btn flavor-btn-primary">
                        <span class="dashicons dashicons-plus-alt2"></span>
                        <?php esc_html_e('Crear Comunidad', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                    </button>
                </div>
            </form>
        </div>
        <?php
        return ob_get_clean();
    }

    /**
     * Shortcode: Mis comunidades
     */
    public function shortcode_mis_comunidades($atts) {
        if (!is_user_logged_in()) {
            return '<p class="flavor-login-required">' . __('Debes iniciar sesión.', FLAVOR_PLATFORM_TEXT_DOMAIN) . '</p>';
        }

        $this->encolar_assets();
        $usuario_id = get_current_user_id();

        global $wpdb;
        $tabla_miembros = $wpdb->prefix . 'flavor_comunidades_miembros';
        $tabla_comunidades = $wpdb->prefix . 'flavor_comunidades';

        $comunidades = [];
        if (Flavor_Platform_Helpers::tabla_existe($tabla_miembros)) {
            $comunidades = $wpdb->get_results($wpdb->prepare(
                "SELECT " . $this->get_comunidad_select_sql('c') . ", m.rol, m.joined_at as fecha_union
                 FROM {$tabla_miembros} m
                 JOIN {$tabla_comunidades} c ON m.comunidad_id = c.id
                 WHERE m.user_id = %d AND m.estado = 'activo'
                 ORDER BY m.joined_at DESC",
                $usuario_id
            ));
        }

        ob_start();
        ?>
        <div class="flavor-mis-comunidades">
            <div class="mis-comunidades-header">
                <h2><?php esc_html_e('Mis Comunidades', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></h2>
                <a href="<?php echo esc_url(home_url('/mi-portal/comunidades/crear/')); ?>" class="flavor-btn flavor-btn-primary">
                    <span class="dashicons dashicons-plus-alt2"></span>
                    <?php esc_html_e('Crear', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                </a>
            </div>

            <?php if (empty($comunidades)): ?>
                <div class="flavor-empty-state">
                    <span class="dashicons dashicons-groups"></span>
                    <p><?php esc_html_e('No perteneces a ninguna comunidad.', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></p>
                    <a href="<?php echo esc_url(home_url('/mi-portal/comunidades/')); ?>" class="flavor-btn flavor-btn-secondary">
                        <?php esc_html_e('Explorar Comunidades', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                    </a>
                </div>
            <?php else: ?>
                <div class="comunidades-lista">
                    <?php foreach ($comunidades as $comunidad): ?>
                        <div class="comunidad-item">
                            <?php if (!empty($comunidad->imagen_perfil)): ?>
                                <img src="<?php echo esc_url($comunidad->imagen_perfil); ?>" alt="" class="comunidad-avatar-sm">
                            <?php else: ?>
                                <div class="comunidad-avatar-sm placeholder" style="background-color: <?php echo esc_attr($comunidad->color ?? '#6366f1'); ?>">
                                    <span class="dashicons dashicons-groups"></span>
                                </div>
                            <?php endif; ?>
                            <div class="comunidad-info">
                                <h4>
                                <a href="<?php echo esc_url(home_url('/mi-portal/comunidades/' . $comunidad->id . '/')); ?>">
                                        <?php echo esc_html($comunidad->nombre); ?>
                                    </a>
                                </h4>
                                <p class="comunidad-meta">
                                    <?php echo number_format_i18n($comunidad->miembros_count); ?> <?php esc_html_e('miembros', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                                    <?php if ($comunidad->rol !== 'miembro'): ?>
                                        · <span class="rol-label"><?php echo esc_html(ucfirst($comunidad->rol)); ?></span>
                                    <?php endif; ?>
                                </p>
                            </div>
                            <a href="<?php echo esc_url(home_url('/mi-portal/comunidades/' . $comunidad->id . '/')); ?>" class="flavor-btn flavor-btn-sm flavor-btn-outline">
                                <?php esc_html_e('Ir', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
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
     * Shortcode: Feed de actividad
     */
    public function shortcode_feed($atts) {
        if (!is_user_logged_in()) {
            return '<p class="flavor-login-required">' . __('Debes iniciar sesión.', FLAVOR_PLATFORM_TEXT_DOMAIN) . '</p>';
        }

        $this->encolar_assets();
        $usuario_id = get_current_user_id();

        global $wpdb;
        $tabla_miembros = $wpdb->prefix . 'flavor_comunidades_miembros';
        $tabla_publicaciones = $wpdb->prefix . 'flavor_comunidades_actividad';
        $tabla_comunidades = $wpdb->prefix . 'flavor_comunidades';

        // Obtener publicaciones de comunidades del usuario
        $publicaciones = [];
        if (Flavor_Platform_Helpers::tabla_existe($tabla_publicaciones)) {
            $publicaciones = $wpdb->get_results($wpdb->prepare(
                "SELECT p.*, p.user_id AS autor_id, c.nombre as comunidad_nombre, c.slug as comunidad_slug, u.display_name as autor_nombre
                 FROM {$tabla_publicaciones} p
                 JOIN {$tabla_miembros} m ON p.comunidad_id = m.comunidad_id
                 JOIN {$tabla_comunidades} c ON p.comunidad_id = c.id
                 LEFT JOIN {$wpdb->users} u ON p.user_id = u.ID
                 WHERE m.user_id = %d AND m.estado = 'activo' AND p.tipo = 'publicacion'
                 ORDER BY p.created_at DESC
                 LIMIT 50",
                $usuario_id
            ));
        }

        ob_start();
        ?>
        <div class="flavor-comunidades-feed">
            <h2><?php esc_html_e('Feed de Actividad', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></h2>

            <?php if (empty($publicaciones)): ?>
                <div class="flavor-empty-state">
                    <p><?php esc_html_e('No hay actividad reciente en tus comunidades.', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></p>
                </div>
            <?php else: ?>
                <div class="feed-lista">
                    <?php foreach ($publicaciones as $pub): ?>
                        <div class="feed-item">
                            <div class="feed-header">
                                <a href="<?php echo esc_url(home_url('/mi-portal/comunidades/' . $pub->comunidad_id . '/')); ?>" class="feed-comunidad">
                                    <?php echo esc_html($pub->comunidad_nombre); ?>
                                </a>
                                <span class="feed-autor"><?php echo esc_html($pub->autor_nombre); ?></span>
                                <span class="feed-fecha"><?php echo human_time_diff(strtotime($pub->created_at), current_time('timestamp')); ?></span>
                            </div>
                            <div class="feed-contenido">
                                <?php echo wp_kses_post(nl2br($pub->contenido)); ?>
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
     * Shortcode: Miembros
     */
    public function shortcode_miembros($atts) {
        $this->encolar_assets();

        $atts = shortcode_atts([
            'comunidad_id' => 0,
            'comunidad_slug' => '',
            'limite' => 50,
        ], $atts);

        global $wpdb;
        $tabla_comunidades = $wpdb->prefix . 'flavor_comunidades';
        $tabla_miembros = $wpdb->prefix . 'flavor_comunidades_miembros';

        $comunidad = null;
        if ($atts['comunidad_id']) {
            $comunidad = $wpdb->get_row($wpdb->prepare(
                "SELECT * FROM {$tabla_comunidades} WHERE id = %d", $atts['comunidad_id']
            ));
        } elseif ($atts['comunidad_slug']) {
            $comunidad = $wpdb->get_row($wpdb->prepare(
                "SELECT * FROM {$tabla_comunidades} WHERE slug = %s", $atts['comunidad_slug']
            ));
        }

        if (!$comunidad) {
            return '<p class="flavor-error">' . __('Comunidad no encontrada.', FLAVOR_PLATFORM_TEXT_DOMAIN) . '</p>';
        }

        $miembros = $wpdb->get_results($wpdb->prepare(
            "SELECT m.*, m.user_id AS usuario_id, m.joined_at AS created_at, u.display_name, u.user_email
             FROM {$tabla_miembros} m
             LEFT JOIN {$wpdb->users} u ON m.user_id = u.ID
             WHERE m.comunidad_id = %d AND m.estado = 'activo'
             ORDER BY FIELD(m.rol, 'admin', 'moderador', 'miembro'), m.joined_at
             LIMIT %d",
            $comunidad->id,
            intval($atts['limite'])
        ));

        ob_start();
        ?>
        <div class="flavor-comunidad-miembros">
            <h2><?php printf(esc_html__('Miembros de %s', FLAVOR_PLATFORM_TEXT_DOMAIN), esc_html($comunidad->nombre)); ?></h2>
            <p><?php echo number_format_i18n(count($miembros)); ?> <?php esc_html_e('miembros', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></p>

            <div class="miembros-grid-full">
                <?php foreach ($miembros as $miembro): ?>
                    <div class="miembro-card">
                        <?php echo get_avatar($miembro->user_email, 60); ?>
                        <div class="miembro-info">
                            <h4><?php echo esc_html($miembro->display_name); ?></h4>
                            <span class="miembro-rol rol-<?php echo esc_attr($miembro->rol); ?>">
                                <?php echo esc_html(ucfirst($miembro->rol)); ?>
                            </span>
                            <p class="miembro-desde">
                                <?php printf(esc_html__('Desde %s', FLAVOR_PLATFORM_TEXT_DOMAIN), date_i18n('M Y', strtotime($miembro->created_at))); ?>
                            </p>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
        <?php
        return ob_get_clean();
    }

    /**
     * Shortcode: Marketplace de la comunidad
     */
    public function shortcode_marketplace($atts) {
        $this->encolar_assets();

        $atts = shortcode_atts([
            'comunidad_id' => 0,
            'limite' => 12,
            'mostrar_formulario' => 'true',
        ], $atts);

        $comunidad_id = absint($atts['comunidad_id']);

        if (!$comunidad_id) {
            return '<p class="flavor-error">' . __('Comunidad no especificada.', FLAVOR_PLATFORM_TEXT_DOMAIN) . '</p>';
        }

        global $wpdb;
        $tabla_comunidades = $wpdb->prefix . 'flavor_comunidades';

        $comunidad = $wpdb->get_row($wpdb->prepare(
            "SELECT id, nombre FROM {$tabla_comunidades} WHERE id = %d AND estado = 'activa'",
            $comunidad_id
        ));

        if (!$comunidad) {
            return '<p class="flavor-error">' . __('Comunidad no encontrada.', FLAVOR_PLATFORM_TEXT_DOMAIN) . '</p>';
        }

        // Verificar si el usuario es miembro
        $es_miembro = false;
        $usuario_id = get_current_user_id();

        if ($usuario_id) {
            $tabla_miembros = $wpdb->prefix . 'flavor_comunidades_miembros';
            $es_miembro = $wpdb->get_var($wpdb->prepare(
                "SELECT id FROM {$tabla_miembros}
                 WHERE comunidad_id = %d AND user_id = %d AND estado = 'activo'",
                $comunidad_id,
                $usuario_id
            ));
        }

        // Obtener anuncios de la comunidad
        $args_anuncios = [
            'post_type' => 'marketplace_item',
            'post_status' => 'publish',
            'posts_per_page' => intval($atts['limite']),
            'orderby' => 'date',
            'order' => 'DESC',
            'meta_query' => [
                [
                    'key' => '_marketplace_comunidad_id',
                    'value' => $comunidad_id,
                    'compare' => '=',
                    'type' => 'NUMERIC',
                ],
            ],
        ];

        $anuncios = get_posts($args_anuncios);

        ob_start();
        ?>
        <div class="flavor-comunidad-marketplace" data-comunidad="<?php echo esc_attr($comunidad_id); ?>">
            <div class="marketplace-header">
                <h3>
                    <span class="dashicons dashicons-store"></span>
                    <?php printf(esc_html__('Marketplace de %s', FLAVOR_PLATFORM_TEXT_DOMAIN), esc_html($comunidad->nombre)); ?>
                </h3>
                <?php if ($es_miembro && $atts['mostrar_formulario'] === 'true'): ?>
                    <a href="<?php echo esc_url(add_query_arg('comunidad_id', $comunidad_id, home_url('/mi-portal/marketplace/publicar/'))); ?>"
                       class="flavor-btn flavor-btn-primary flavor-btn-sm">
                        <span class="dashicons dashicons-plus-alt2"></span>
                        <?php esc_html_e('Publicar anuncio', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                    </a>
                <?php endif; ?>
            </div>

            <?php if (empty($anuncios)): ?>
                <div class="flavor-empty-state">
                    <span class="dashicons dashicons-store"></span>
                    <p><?php esc_html_e('No hay anuncios en esta comunidad.', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></p>
                    <?php if ($es_miembro): ?>
                        <p class="text-muted"><?php esc_html_e('¡Sé el primero en publicar algo!', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></p>
                    <?php endif; ?>
                </div>
            <?php else: ?>
                <div class="flavor-grid flavor-grid-3 marketplace-grid">
                    <?php foreach ($anuncios as $anuncio): ?>
                        <?php
                        $tipos_terminos = wp_get_post_terms($anuncio->ID, 'marketplace_tipo', ['fields' => 'slugs']);
                        $tipo = (!empty($tipos_terminos) && !is_wp_error($tipos_terminos)) ? $tipos_terminos[0] : 'venta';
                        $precio = get_post_meta($anuncio->ID, '_marketplace_precio', true);
                        $estado = get_post_meta($anuncio->ID, '_marketplace_estado', true) ?: 'disponible';
                        $imagen = get_the_post_thumbnail_url($anuncio->ID, 'medium');
                        $autor = get_userdata($anuncio->post_author);

                        $tipos_label = [
                            'regalo' => __('Regalo', FLAVOR_PLATFORM_TEXT_DOMAIN),
                            'venta' => __('Venta', FLAVOR_PLATFORM_TEXT_DOMAIN),
                            'cambio' => __('Cambio', FLAVOR_PLATFORM_TEXT_DOMAIN),
                            'alquiler' => __('Alquiler', FLAVOR_PLATFORM_TEXT_DOMAIN),
                        ];
                        ?>
                        <div class="flavor-card marketplace-card estado-<?php echo esc_attr($estado); ?>">
                            <div class="flavor-card-image">
                                <?php if ($imagen): ?>
                                    <a href="<?php echo esc_url(home_url('/mi-portal/marketplace/detalle/?anuncio_id=' . $anuncio->ID)); ?>">
                                        <img src="<?php echo esc_url($imagen); ?>" alt="<?php echo esc_attr($anuncio->post_title); ?>">
                                    </a>
                                <?php else: ?>
                                    <div class="placeholder-image">
                                        <span class="dashicons dashicons-format-image"></span>
                                    </div>
                                <?php endif; ?>
                                <span class="tipo-badge tipo-<?php echo esc_attr($tipo); ?>">
                                    <?php echo esc_html($tipos_label[$tipo] ?? ucfirst($tipo)); ?>
                                </span>
                                <?php if ($estado === 'vendido'): ?>
                                    <span class="vendido-badge"><?php esc_html_e('Vendido', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></span>
                                <?php endif; ?>
                            </div>
                            <div class="flavor-card-body">
                                <h4>
                                    <a href="<?php echo esc_url(home_url('/mi-portal/marketplace/detalle/?anuncio_id=' . $anuncio->ID)); ?>">
                                        <?php echo esc_html($anuncio->post_title); ?>
                                    </a>
                                </h4>
                                <?php if (in_array($tipo, ['venta', 'alquiler']) && $precio): ?>
                                    <p class="precio"><?php echo number_format($precio, 2, ',', '.'); ?>€</p>
                                <?php elseif ($tipo === 'regalo'): ?>
                                    <p class="precio gratis"><?php esc_html_e('Gratis', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></p>
                                <?php endif; ?>
                                <p class="autor">
                                    <span class="dashicons dashicons-admin-users"></span>
                                    <?php echo esc_html($autor ? $autor->display_name : __('Usuario', FLAVOR_PLATFORM_TEXT_DOMAIN)); ?>
                                </p>
                            </div>
                            <div class="flavor-card-footer">
                                <a href="<?php echo esc_url(home_url('/mi-portal/marketplace/detalle/?anuncio_id=' . $anuncio->ID)); ?>"
                                   class="flavor-btn flavor-btn-outline flavor-btn-sm">
                                    <?php esc_html_e('Ver', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                                </a>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>

                <?php if (count($anuncios) >= intval($atts['limite'])): ?>
                    <div class="marketplace-ver-mas">
                        <a href="<?php echo esc_url(add_query_arg('comunidad', $comunidad_id, home_url('/mi-portal/marketplace/'))); ?>"
                           class="flavor-btn flavor-btn-secondary">
                            <?php esc_html_e('Ver todos los anuncios', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                        </a>
                    </div>
                <?php endif; ?>
            <?php endif; ?>
        </div>
        <?php
        return ob_get_clean();
    }

    // ================================
    // AJAX HANDLERS
    // ================================

    /**
     * AJAX: Crear comunidad
     */
    public function ajax_crear_comunidad() {
        check_ajax_referer('comunidades_nonce', 'comunidades_nonce_field');

        if (!is_user_logged_in()) {
            wp_send_json_error(__('Debes iniciar sesión', FLAVOR_PLATFORM_TEXT_DOMAIN));
        }

        $nombre = isset($_POST['nombre']) ? sanitize_text_field($_POST['nombre']) : '';
        $descripcion = isset($_POST['descripcion']) ? wp_kses_post($_POST['descripcion']) : '';
        $descripcion_corta = isset($_POST['descripcion_corta']) ? sanitize_text_field($_POST['descripcion_corta']) : '';
        $categoria = isset($_POST['categoria']) ? sanitize_text_field($_POST['categoria']) : '';
        $privacidad = isset($_POST['privacidad']) ? sanitize_text_field($_POST['privacidad']) : 'publica';
        $reglas = isset($_POST['reglas']) ? wp_kses_post($_POST['reglas']) : '';

        if (empty($nombre)) {
            wp_send_json_error(__('El nombre es obligatorio', FLAVOR_PLATFORM_TEXT_DOMAIN));
        }

        global $wpdb;
        $tabla_comunidades = $wpdb->prefix . 'flavor_comunidades';
        $tabla_miembros = $wpdb->prefix . 'flavor_comunidades_miembros';

        // Generar slug único
        $slug = sanitize_title($nombre);
        $base_slug = $slug;
        $contador = 1;
        while ($wpdb->get_var($wpdb->prepare("SELECT id FROM {$tabla_comunidades} WHERE slug = %s", $slug))) {
            $slug = $base_slug . '-' . $contador++;
        }

        $usuario_id = get_current_user_id();

        // Crear comunidad
        $resultado = $wpdb->insert($tabla_comunidades, [
            'nombre' => $nombre,
            'slug' => $slug,
            'descripcion' => $descripcion ?: $descripcion_corta,
            'categoria' => $categoria,
            'tipo' => $this->map_privacidad_to_tipo($privacidad),
            'reglas' => $reglas,
            'creador_id' => $usuario_id,
            'estado' => 'activa',
            'miembros_count' => 1,
            'created_at' => current_time('mysql'),
        ]);

        if ($resultado) {
            $comunidad_id = $wpdb->insert_id;

            // Añadir al creador como admin
            $wpdb->insert($tabla_miembros, [
                'comunidad_id' => $comunidad_id,
                'user_id' => $usuario_id,
                'rol' => 'admin',
                'estado' => 'activo',
                'joined_at' => current_time('mysql'),
            ]);

            do_action('comunidad_created', $comunidad_id, $usuario_id);

            wp_send_json_success([
                'mensaje' => __('Comunidad creada correctamente', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'comunidad_id' => $comunidad_id,
                'redirect' => home_url('/mi-portal/comunidades/' . $comunidad_id . '/'),
            ]);
        } else {
            wp_send_json_error(__('Error al crear la comunidad', FLAVOR_PLATFORM_TEXT_DOMAIN));
        }
    }

    /**
     * AJAX: Unirse a comunidad
     */
    public function ajax_unirse() {
        check_ajax_referer('comunidades_nonce', 'nonce');

        if (!is_user_logged_in()) {
            wp_send_json_error(__('Debes iniciar sesión', FLAVOR_PLATFORM_TEXT_DOMAIN));
        }

        $comunidad_id = isset($_POST['comunidad_id']) ? absint($_POST['comunidad_id']) : 0;
        $usuario_id = get_current_user_id();

        if (!$comunidad_id) {
            wp_send_json_error(__('Comunidad no válida', FLAVOR_PLATFORM_TEXT_DOMAIN));
        }

        global $wpdb;
        $tabla_comunidades = $wpdb->prefix . 'flavor_comunidades';
        $tabla_miembros = $wpdb->prefix . 'flavor_comunidades_miembros';

        // Verificar que la comunidad existe y es pública
        $comunidad = $wpdb->get_row($wpdb->prepare(
            "SELECT " . $this->get_comunidad_select_sql() . " FROM {$tabla_comunidades} WHERE id = %d AND estado = 'activa'",
            $comunidad_id
        ));

        if (!$comunidad) {
            wp_send_json_error(__('Comunidad no encontrada', FLAVOR_PLATFORM_TEXT_DOMAIN));
        }

        // Verificar si ya es miembro
        $existe = $wpdb->get_var($wpdb->prepare(
            "SELECT id FROM {$tabla_miembros} WHERE comunidad_id = %d AND user_id = %d",
            $comunidad_id,
            $usuario_id
        ));

        if ($existe) {
            wp_send_json_error(__('Ya eres miembro de esta comunidad', FLAVOR_PLATFORM_TEXT_DOMAIN));
        }

        // Determinar estado según privacidad
        $estado = $comunidad->privacidad === 'publica' ? 'activo' : 'pendiente';

        $resultado = $wpdb->insert($tabla_miembros, [
            'comunidad_id' => $comunidad_id,
            'user_id' => $usuario_id,
            'rol' => 'miembro',
            'estado' => $estado,
            'joined_at' => current_time('mysql'),
        ]);

        if ($resultado) {
            if ($estado === 'activo') {
                // Incrementar contador
                $wpdb->query($wpdb->prepare(
                    "UPDATE {$tabla_comunidades} SET miembros_count = miembros_count + 1 WHERE id = %d",
                    $comunidad_id
                ));
                wp_send_json_success(['mensaje' => __('Te has unido a la comunidad', FLAVOR_PLATFORM_TEXT_DOMAIN)]);
            } else {
                wp_send_json_success(['mensaje' => __('Solicitud enviada. Espera aprobación.', FLAVOR_PLATFORM_TEXT_DOMAIN)]);
            }
        } else {
            wp_send_json_error(__('Error al unirse', FLAVOR_PLATFORM_TEXT_DOMAIN));
        }
    }

    /**
     * AJAX: Salir de comunidad
     */
    public function ajax_salir() {
        check_ajax_referer('comunidades_nonce', 'nonce');

        if (!is_user_logged_in()) {
            wp_send_json_error(__('Debes iniciar sesión', FLAVOR_PLATFORM_TEXT_DOMAIN));
        }

        $comunidad_id = isset($_POST['comunidad_id']) ? absint($_POST['comunidad_id']) : 0;
        $usuario_id = get_current_user_id();

        if (!$comunidad_id) {
            wp_send_json_error(__('Comunidad no válida', FLAVOR_PLATFORM_TEXT_DOMAIN));
        }

        global $wpdb;
        $tabla_comunidades = $wpdb->prefix . 'flavor_comunidades';
        $tabla_miembros = $wpdb->prefix . 'flavor_comunidades_miembros';

        // Verificar que no sea el único admin
        $miembro = $wpdb->get_row($wpdb->prepare(
            "SELECT rol FROM {$tabla_miembros} WHERE comunidad_id = %d AND user_id = %d AND estado = 'activo'",
            $comunidad_id,
            $usuario_id
        ));

        if ($miembro && $miembro->rol === 'admin') {
            $otros_admins = $wpdb->get_var($wpdb->prepare(
                "SELECT COUNT(*) FROM {$tabla_miembros}
                 WHERE comunidad_id = %d AND rol = 'admin' AND estado = 'activo' AND user_id != %d",
                $comunidad_id,
                $usuario_id
            ));

            if ($otros_admins == 0) {
                wp_send_json_error(__('Debes designar otro admin antes de abandonar la comunidad', FLAVOR_PLATFORM_TEXT_DOMAIN));
            }
        }

        $resultado = $wpdb->delete($tabla_miembros, [
            'comunidad_id' => $comunidad_id,
            'user_id' => $usuario_id,
        ]);

        if ($resultado) {
            // Decrementar contador
            $wpdb->query($wpdb->prepare(
                "UPDATE {$tabla_comunidades} SET miembros_count = GREATEST(0, miembros_count - 1) WHERE id = %d",
                $comunidad_id
            ));

            wp_send_json_success(['mensaje' => __('Has abandonado la comunidad', FLAVOR_PLATFORM_TEXT_DOMAIN)]);
        } else {
            wp_send_json_error(__('Error al abandonar', FLAVOR_PLATFORM_TEXT_DOMAIN));
        }
    }

    /**
     * AJAX: Publicar
     */
    public function ajax_publicar() {
        check_ajax_referer('comunidades_nonce', 'comunidades_nonce_field');

        if (!is_user_logged_in()) {
            wp_send_json_error(__('Debes iniciar sesión', FLAVOR_PLATFORM_TEXT_DOMAIN));
        }

        $comunidad_id = isset($_POST['comunidad_id']) ? absint($_POST['comunidad_id']) : 0;
        $contenido = isset($_POST['contenido']) ? wp_kses_post($_POST['contenido']) : '';
        $usuario_id = get_current_user_id();

        if (!$comunidad_id || empty(trim($contenido))) {
            wp_send_json_error(__('Contenido no válido', FLAVOR_PLATFORM_TEXT_DOMAIN));
        }

        global $wpdb;
        $tabla_miembros = $wpdb->prefix . 'flavor_comunidades_miembros';
        $tabla_publicaciones = $wpdb->prefix . 'flavor_comunidades_actividad';

        // Verificar que es miembro
        $es_miembro = $wpdb->get_var($wpdb->prepare(
            "SELECT id FROM {$tabla_miembros}
             WHERE comunidad_id = %d AND user_id = %d AND estado = 'activo'",
            $comunidad_id,
            $usuario_id
        ));

        if (!$es_miembro) {
            wp_send_json_error(__('No eres miembro de esta comunidad', FLAVOR_PLATFORM_TEXT_DOMAIN));
        }

        $resultado = $wpdb->insert($tabla_publicaciones, [
            'comunidad_id' => $comunidad_id,
            'user_id' => $usuario_id,
            'tipo' => 'publicacion',
            'contenido' => $contenido,
            'created_at' => current_time('mysql'),
        ]);

        if ($resultado) {
            wp_send_json_success(['mensaje' => __('Publicación creada', FLAVOR_PLATFORM_TEXT_DOMAIN)]);
        } else {
            wp_send_json_error(__('Error al publicar', FLAVOR_PLATFORM_TEXT_DOMAIN));
        }
    }

    /**
     * AJAX: Comentar
     */
    public function ajax_comentar() {
        check_ajax_referer('comunidades_nonce', 'nonce');

        if (!is_user_logged_in()) {
            wp_send_json_error(__('Debes iniciar sesión', FLAVOR_PLATFORM_TEXT_DOMAIN));
        }

        $publicacion_id = isset($_POST['publicacion_id']) ? absint($_POST['publicacion_id']) : 0;
        $contenido = isset($_POST['contenido']) ? sanitize_textarea_field($_POST['contenido']) : '';

        if (!$publicacion_id || empty($contenido)) {
            wp_send_json_error(__('Datos no válidos', FLAVOR_PLATFORM_TEXT_DOMAIN));
        }

        global $wpdb;
        $tabla_comentarios = $wpdb->prefix . 'flavor_comunidades_comentarios';
        if (!Flavor_Platform_Helpers::tabla_existe($tabla_comentarios)) {
            wp_send_json_error(__('Los comentarios no están disponibles en este flujo legacy.', FLAVOR_PLATFORM_TEXT_DOMAIN));
        }

        $resultado = $wpdb->insert($tabla_comentarios, [
            'publicacion_id' => $publicacion_id,
            'autor_id' => get_current_user_id(),
            'contenido' => $contenido,
            'estado' => 'aprobado',
            'created_at' => current_time('mysql'),
        ]);

        if ($resultado) {
            wp_send_json_success(['mensaje' => __('Comentario publicado', FLAVOR_PLATFORM_TEXT_DOMAIN)]);
        } else {
            wp_send_json_error(__('Error al comentar', FLAVOR_PLATFORM_TEXT_DOMAIN));
        }
    }

    /**
     * AJAX: Listar comunidades
     */
    public function ajax_listar() {
        $categoria = isset($_POST['categoria']) ? sanitize_text_field($_POST['categoria']) : '';
        $busqueda = isset($_POST['busqueda']) ? sanitize_text_field($_POST['busqueda']) : '';

        global $wpdb;
        $tabla_comunidades = $wpdb->prefix . 'flavor_comunidades';

        $where = "estado = 'activa' AND tipo IN ('abierta', 'cerrada')";
        $params = [];

        if (!empty($categoria)) {
            $where .= " AND categoria = %s";
            $params[] = $categoria;
        }

        if (!empty($busqueda)) {
            $where .= " AND (nombre LIKE %s OR descripcion LIKE %s)";
            $like = '%' . $wpdb->esc_like($busqueda) . '%';
            $params[] = $like;
            $params[] = $like;
        }

        $sql = "SELECT " . $this->get_comunidad_select_sql() . " FROM {$tabla_comunidades} WHERE {$where} ORDER BY miembros_count DESC LIMIT 50";

        $comunidades = empty($params)
            ? $wpdb->get_results($sql)
            : $wpdb->get_results($wpdb->prepare($sql, ...$params));

        ob_start();
        if (empty($comunidades)) {
            echo '<div class="flavor-empty-state"><p>' . __('No se encontraron comunidades.', FLAVOR_PLATFORM_TEXT_DOMAIN) . '</p></div>';
        } else {
            foreach ($comunidades as $comunidad) {
                $this->render_comunidad_card($comunidad);
            }
        }
        $html = ob_get_clean();

        wp_send_json_success(['html' => $html, 'total' => count($comunidades)]);
    }

    /**
     * AJAX: Obtener feed
     */
    public function ajax_obtener_feed() {
        if (!is_user_logged_in()) {
            wp_send_json_error(__('Debes iniciar sesión', FLAVOR_PLATFORM_TEXT_DOMAIN));
        }

        $pagina = isset($_POST['pagina']) ? absint($_POST['pagina']) : 1;
        $limite = 20;
        $offset = ($pagina - 1) * $limite;
        $usuario_id = get_current_user_id();

        global $wpdb;
        $tabla_miembros = $wpdb->prefix . 'flavor_comunidades_miembros';
        $tabla_publicaciones = $wpdb->prefix . 'flavor_comunidades_actividad';
        $tabla_comunidades = $wpdb->prefix . 'flavor_comunidades';

        $publicaciones = $wpdb->get_results($wpdb->prepare(
            "SELECT p.*, p.user_id AS autor_id, c.nombre as comunidad_nombre, c.slug as comunidad_slug, u.display_name as autor_nombre
             FROM {$tabla_publicaciones} p
             JOIN {$tabla_miembros} m ON p.comunidad_id = m.comunidad_id
             JOIN {$tabla_comunidades} c ON p.comunidad_id = c.id
             LEFT JOIN {$wpdb->users} u ON p.user_id = u.ID
             WHERE m.user_id = %d AND m.estado = 'activo' AND p.tipo = 'publicacion'
             ORDER BY p.created_at DESC
             LIMIT %d OFFSET %d",
            $usuario_id,
            $limite,
            $offset
        ));

        wp_send_json_success(['publicaciones' => $publicaciones]);
    }
}

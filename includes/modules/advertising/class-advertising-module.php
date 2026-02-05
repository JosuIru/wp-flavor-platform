<?php
/**
 * Módulo de Publicidad Ética
 *
 * Sistema de anuncios éticos con reparto de beneficios a la comunidad.
 *
 * @package FlavorChatIA
 */

if (!defined('ABSPATH')) {
    exit;
}

class Flavor_Chat_Advertising_Module extends Flavor_Chat_Module_Base {

    /**
     * Constructor
     */
    public function __construct() {
        $this->id = 'advertising';
        $this->name = __('Publicidad Ética', 'flavor-chat-ia');
        $this->description = __('Sistema de anuncios éticos con reparto de beneficios.', 'flavor-chat-ia');

        parent::__construct();
    }

    public function can_activate() {
        return true;
    }

    public function get_activation_error() {
        return '';
    }

    /**
     * Inicialización del módulo
     */
    public function init() {
        // Registrar CPT y taxonomías
        add_action('init', [$this, 'register_post_types']);
        add_action('init', [$this, 'register_taxonomies']);

        // Metaboxes
        add_action('add_meta_boxes', [$this, 'add_meta_boxes']);
        add_action('save_post_flavor_ad', [$this, 'save_ad_meta']);

        // REST API
        add_action('rest_api_init', [$this, 'register_rest_routes']);

        // AJAX handlers
        add_action('wp_ajax_flavor_ads_track_impression', [$this, 'ajax_track_impression']);
        add_action('wp_ajax_nopriv_flavor_ads_track_impression', [$this, 'ajax_track_impression']);
        add_action('wp_ajax_flavor_ads_track_click', [$this, 'ajax_track_click']);
        add_action('wp_ajax_nopriv_flavor_ads_track_click', [$this, 'ajax_track_click']);
        add_action('wp_ajax_flavor_ads_crear_campana', [$this, 'ajax_crear_campana']);
        add_action('wp_ajax_flavor_ads_pausar_campana', [$this, 'ajax_pausar_campana']);
        add_action('wp_ajax_flavor_ads_stats', [$this, 'ajax_obtener_stats']);

        // Admin AJAX
        add_action('wp_ajax_flavor_ads_aprobar', [$this, 'ajax_admin_aprobar']);
        add_action('wp_ajax_flavor_ads_rechazar', [$this, 'ajax_admin_rechazar']);
        add_action('wp_ajax_flavor_ads_procesar_pago', [$this, 'ajax_admin_procesar_pago']);

        // Shortcodes
        add_shortcode('flavor_ad', [$this, 'shortcode_ad']);
        add_shortcode('flavor_ads_dashboard', [$this, 'shortcode_dashboard']);
        add_shortcode('flavor_ads_crear', [$this, 'shortcode_crear']);
        add_shortcode('flavor_ads_ingresos', [$this, 'shortcode_ingresos']);

        // WP Cron
        add_action('flavor_ads_procesar_pagos', [$this, 'cron_procesar_pagos']);
        add_action('flavor_ads_actualizar_estadisticas', [$this, 'cron_actualizar_estadisticas']);

        if (!wp_next_scheduled('flavor_ads_procesar_pagos')) {
            wp_schedule_event(time(), 'daily', 'flavor_ads_procesar_pagos');
        }

        if (!wp_next_scheduled('flavor_ads_actualizar_estadisticas')) {
            wp_schedule_event(time(), 'hourly', 'flavor_ads_actualizar_estadisticas');
        }

        // Enqueue assets
        add_action('wp_enqueue_scripts', [$this, 'enqueue_scripts']);
    }

    /**
     * Registrar Custom Post Types
     */
    public function register_post_types() {
        register_post_type('flavor_ad', [
            'labels' => [
                'name' => __('Anuncios', 'flavor-chat-ia'),
                'singular_name' => __('Anuncio', 'flavor-chat-ia'),
                'add_new' => __('Añadir nuevo', 'flavor-chat-ia'),
                'add_new_item' => __('Añadir nuevo anuncio', 'flavor-chat-ia'),
                'edit_item' => __('Editar anuncio', 'flavor-chat-ia'),
                'view_item' => __('Ver anuncio', 'flavor-chat-ia'),
                'search_items' => __('Buscar anuncios', 'flavor-chat-ia'),
                'not_found' => __('No se encontraron anuncios', 'flavor-chat-ia'),
            ],
            'public' => false,
            'show_ui' => true,
            'show_in_menu' => 'flavor-chat-ia',
            'supports' => ['title', 'thumbnail'],
            'menu_icon' => 'dashicons-megaphone',
            'has_archive' => false,
            'rewrite' => false,
        ]);

        register_post_type('flavor_ad_campaign', [
            'labels' => [
                'name' => __('Campañas', 'flavor-chat-ia'),
                'singular_name' => __('Campaña', 'flavor-chat-ia'),
            ],
            'public' => false,
            'show_ui' => true,
            'show_in_menu' => 'edit.php?post_type=flavor_ad',
            'supports' => ['title'],
        ]);
    }

    /**
     * Registrar taxonomías
     */
    public function register_taxonomies() {
        register_taxonomy('ad_ubicacion', 'flavor_ad', [
            'labels' => [
                'name' => __('Ubicaciones', 'flavor-chat-ia'),
                'singular_name' => __('Ubicación', 'flavor-chat-ia'),
            ],
            'hierarchical' => true,
            'show_admin_column' => true,
            'rewrite' => false,
        ]);

        register_taxonomy('ad_categoria', 'flavor_ad', [
            'labels' => [
                'name' => __('Categorías de anuncio', 'flavor-chat-ia'),
                'singular_name' => __('Categoría', 'flavor-chat-ia'),
            ],
            'hierarchical' => true,
            'show_admin_column' => true,
            'rewrite' => false,
        ]);
    }

    /**
     * Añadir meta boxes
     */
    public function add_meta_boxes() {
        add_meta_box(
            'flavor_ad_settings',
            __('Configuración del anuncio', 'flavor-chat-ia'),
            [$this, 'render_ad_settings_metabox'],
            'flavor_ad',
            'normal',
            'high'
        );

        add_meta_box(
            'flavor_ad_stats',
            __('Estadísticas', 'flavor-chat-ia'),
            [$this, 'render_ad_stats_metabox'],
            'flavor_ad',
            'side'
        );
    }

    /**
     * Renderizar metabox de configuración
     */
    public function render_ad_settings_metabox($post) {
        wp_nonce_field('flavor_ad_meta', 'flavor_ad_nonce');

        $tipo = get_post_meta($post->ID, '_ad_tipo', true) ?: 'banner_horizontal';
        $url_destino = get_post_meta($post->ID, '_ad_url_destino', true);
        $fecha_inicio = get_post_meta($post->ID, '_ad_fecha_inicio', true);
        $fecha_fin = get_post_meta($post->ID, '_ad_fecha_fin', true);
        $presupuesto = get_post_meta($post->ID, '_ad_presupuesto', true);
        $precio_clic = get_post_meta($post->ID, '_ad_precio_clic', true) ?: '0.10';
        $precio_impresion = get_post_meta($post->ID, '_ad_precio_impresion', true) ?: '0.001';
        $reparto_comunidad = get_post_meta($post->ID, '_ad_reparto_comunidad', true) ?: '30';
        $anunciante_id = get_post_meta($post->ID, '_ad_anunciante_id', true);
        $contenido_html = get_post_meta($post->ID, '_ad_contenido_html', true);
        $imagen_url = get_post_meta($post->ID, '_ad_imagen_url', true);
        $texto_cta = get_post_meta($post->ID, '_ad_texto_cta', true) ?: __('Saber más', 'flavor-chat-ia');
        $segmentacion = get_post_meta($post->ID, '_ad_segmentacion', true) ?: [];
        ?>
        <style>
            .flavor-ad-metabox { display: grid; grid-template-columns: 1fr 1fr; gap: 20px; }
            .flavor-ad-metabox .full-width { grid-column: 1 / -1; }
            .flavor-ad-metabox label { display: block; font-weight: 600; margin-bottom: 5px; }
            .flavor-ad-metabox input, .flavor-ad-metabox select, .flavor-ad-metabox textarea {
                width: 100%; padding: 8px; border: 1px solid #ddd; border-radius: 4px;
            }
            .flavor-ad-metabox .description { font-size: 12px; color: #666; margin-top: 4px; }
        </style>
        <div class="flavor-ad-metabox">
            <div>
                <label><?php _e('Tipo de anuncio', 'flavor-chat-ia'); ?></label>
                <select name="ad_tipo">
                    <option value="banner_horizontal" <?php selected($tipo, 'banner_horizontal'); ?>><?php _e('Banner Horizontal (728x90)', 'flavor-chat-ia'); ?></option>
                    <option value="banner_sidebar" <?php selected($tipo, 'banner_sidebar'); ?>><?php _e('Banner Sidebar (300x250)', 'flavor-chat-ia'); ?></option>
                    <option value="banner_card" <?php selected($tipo, 'banner_card'); ?>><?php _e('Tarjeta', 'flavor-chat-ia'); ?></option>
                    <option value="banner_nativo" <?php selected($tipo, 'banner_nativo'); ?>><?php _e('Nativo', 'flavor-chat-ia'); ?></option>
                    <option value="video" <?php selected($tipo, 'video'); ?>><?php _e('Video', 'flavor-chat-ia'); ?></option>
                </select>
            </div>
            <div>
                <label><?php _e('Anunciante', 'flavor-chat-ia'); ?></label>
                <?php
                wp_dropdown_users([
                    'name' => 'ad_anunciante_id',
                    'selected' => $anunciante_id,
                    'show_option_none' => __('Seleccionar...', 'flavor-chat-ia'),
                    'option_none_value' => '',
                    'role__in' => ['administrator', 'editor', 'author', 'subscriber'],
                ]);
                ?>
            </div>
            <div class="full-width">
                <label><?php _e('URL de destino', 'flavor-chat-ia'); ?></label>
                <input type="url" name="ad_url_destino" value="<?php echo esc_attr($url_destino); ?>" placeholder="https://...">
            </div>
            <div class="full-width">
                <label><?php _e('Imagen del anuncio', 'flavor-chat-ia'); ?></label>
                <input type="url" name="ad_imagen_url" value="<?php echo esc_attr($imagen_url); ?>" placeholder="URL de la imagen">
                <p class="description"><?php _e('O usa la imagen destacada del post', 'flavor-chat-ia'); ?></p>
            </div>
            <div class="full-width">
                <label><?php _e('Contenido HTML (opcional)', 'flavor-chat-ia'); ?></label>
                <textarea name="ad_contenido_html" rows="4" placeholder="HTML personalizado..."><?php echo esc_textarea($contenido_html); ?></textarea>
            </div>
            <div>
                <label><?php _e('Texto del botón CTA', 'flavor-chat-ia'); ?></label>
                <input type="text" name="ad_texto_cta" value="<?php echo esc_attr($texto_cta); ?>">
            </div>
            <div>
                <label><?php _e('% para la comunidad', 'flavor-chat-ia'); ?></label>
                <input type="number" name="ad_reparto_comunidad" value="<?php echo esc_attr($reparto_comunidad); ?>" min="0" max="100" step="5">
                <p class="description"><?php _e('Porcentaje de ingresos que se reparten con la comunidad', 'flavor-chat-ia'); ?></p>
            </div>
            <div>
                <label><?php _e('Fecha inicio', 'flavor-chat-ia'); ?></label>
                <input type="date" name="ad_fecha_inicio" value="<?php echo esc_attr($fecha_inicio); ?>">
            </div>
            <div>
                <label><?php _e('Fecha fin', 'flavor-chat-ia'); ?></label>
                <input type="date" name="ad_fecha_fin" value="<?php echo esc_attr($fecha_fin); ?>">
            </div>
            <div>
                <label><?php _e('Presupuesto total (€)', 'flavor-chat-ia'); ?></label>
                <input type="number" name="ad_presupuesto" value="<?php echo esc_attr($presupuesto); ?>" min="0" step="0.01">
            </div>
            <div>
                <label><?php _e('Precio por clic (€)', 'flavor-chat-ia'); ?></label>
                <input type="number" name="ad_precio_clic" value="<?php echo esc_attr($precio_clic); ?>" min="0" step="0.01">
            </div>
            <div>
                <label><?php _e('Precio por 1000 impresiones (€)', 'flavor-chat-ia'); ?></label>
                <input type="number" name="ad_precio_impresion" value="<?php echo esc_attr($precio_impresion); ?>" min="0" step="0.001">
            </div>
        </div>
        <?php
    }

    /**
     * Renderizar metabox de estadísticas
     */
    public function render_ad_stats_metabox($post) {
        global $wpdb;
        $tabla_stats = $wpdb->prefix . 'flavor_ads_stats';

        $stats = $wpdb->get_row($wpdb->prepare(
            "SELECT
                SUM(impresiones) as total_impresiones,
                SUM(clics) as total_clics,
                SUM(gasto) as total_gasto
             FROM $tabla_stats WHERE ad_id = %d",
            $post->ID
        ));

        $impresiones = $stats->total_impresiones ?? 0;
        $clics = $stats->total_clics ?? 0;
        $gasto = $stats->total_gasto ?? 0;
        $ctr = $impresiones > 0 ? round(($clics / $impresiones) * 100, 2) : 0;
        ?>
        <div style="padding: 10px 0;">
            <p><strong><?php _e('Impresiones:', 'flavor-chat-ia'); ?></strong> <?php echo number_format($impresiones); ?></p>
            <p><strong><?php _e('Clics:', 'flavor-chat-ia'); ?></strong> <?php echo number_format($clics); ?></p>
            <p><strong><?php _e('CTR:', 'flavor-chat-ia'); ?></strong> <?php echo $ctr; ?>%</p>
            <p><strong><?php _e('Gasto total:', 'flavor-chat-ia'); ?></strong> <?php echo number_format($gasto, 2); ?>€</p>
        </div>
        <?php
    }

    /**
     * Guardar meta del anuncio
     */
    public function save_ad_meta($post_id) {
        if (!isset($_POST['flavor_ad_nonce']) || !wp_verify_nonce($_POST['flavor_ad_nonce'], 'flavor_ad_meta')) {
            return;
        }

        if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
            return;
        }

        $campos = [
            'ad_tipo', 'ad_url_destino', 'ad_fecha_inicio', 'ad_fecha_fin',
            'ad_presupuesto', 'ad_precio_clic', 'ad_precio_impresion',
            'ad_reparto_comunidad', 'ad_anunciante_id', 'ad_contenido_html',
            'ad_imagen_url', 'ad_texto_cta',
        ];

        foreach ($campos as $campo) {
            if (isset($_POST[$campo])) {
                update_post_meta($post_id, '_' . $campo, sanitize_text_field($_POST[$campo]));
            }
        }
    }

    /**
     * Acciones disponibles
     */
    public function get_actions() {
        return [
            'ver_estadisticas' => [
                'description' => 'Ver estadísticas de publicidad',
                'params' => ['periodo', 'ad_id'],
            ],
            'listar_anuncios' => [
                'description' => 'Listar anuncios activos',
                'params' => ['estado', 'tipo'],
            ],
            'crear_anuncio' => [
                'description' => 'Crear un nuevo anuncio',
                'params' => ['titulo', 'tipo', 'url_destino', 'imagen', 'presupuesto'],
            ],
            'mis_anuncios' => [
                'description' => 'Ver mis anuncios como anunciante',
                'params' => [],
            ],
            'pausar_anuncio' => [
                'description' => 'Pausar un anuncio activo',
                'params' => ['ad_id'],
            ],
            'reanudar_anuncio' => [
                'description' => 'Reanudar un anuncio pausado',
                'params' => ['ad_id'],
            ],
            'mis_ingresos' => [
                'description' => 'Ver ingresos de publicidad compartidos',
                'params' => ['periodo'],
            ],
            'ubicaciones_disponibles' => [
                'description' => 'Ver ubicaciones disponibles para anuncios',
                'params' => [],
            ],
        ];
    }

    /**
     * Ejecutar acción
     */
    public function execute_action($action_name, $params) {
        $metodo = 'action_' . $action_name;

        if (method_exists($this, $metodo)) {
            return $this->$metodo($params);
        }

        return [
            'success' => false,
            'error' => "Acción no implementada: {$action_name}",
        ];
    }

    /**
     * Acción: Ver estadísticas
     */
    private function action_ver_estadisticas($params) {
        global $wpdb;
        $tabla_stats = $wpdb->prefix . 'flavor_ads_stats';

        $periodo = $params['periodo'] ?? 'month';
        $ad_id = $params['ad_id'] ?? null;

        $fecha_inicio = $this->get_fecha_inicio_periodo($periodo);
        $where = "fecha >= %s";
        $valores = [$fecha_inicio];

        if ($ad_id) {
            $where .= " AND ad_id = %d";
            $valores[] = intval($ad_id);
        }

        $stats = $wpdb->get_row($wpdb->prepare(
            "SELECT
                SUM(impresiones) as total_impresiones,
                SUM(clics) as total_clics,
                SUM(gasto) as total_gasto,
                COUNT(DISTINCT ad_id) as total_anuncios
             FROM $tabla_stats WHERE $where",
            $valores
        ));

        $ctr = $stats->total_impresiones > 0
            ? round(($stats->total_clics / $stats->total_impresiones) * 100, 2)
            : 0;

        return [
            'success' => true,
            'data' => [
                'periodo' => $periodo,
                'impresiones' => (int)($stats->total_impresiones ?? 0),
                'clics' => (int)($stats->total_clics ?? 0),
                'ctr' => $ctr,
                'gasto' => (float)($stats->total_gasto ?? 0),
                'anuncios_activos' => (int)($stats->total_anuncios ?? 0),
            ],
        ];
    }

    /**
     * Acción: Listar anuncios
     */
    private function action_listar_anuncios($params) {
        $estado = $params['estado'] ?? 'publish';
        $tipo = $params['tipo'] ?? '';

        $args = [
            'post_type' => 'flavor_ad',
            'posts_per_page' => 50,
            'post_status' => $estado,
        ];

        if ($tipo) {
            $args['meta_query'] = [
                [
                    'key' => '_ad_tipo',
                    'value' => $tipo,
                ],
            ];
        }

        $anuncios = get_posts($args);
        $resultado = [];

        foreach ($anuncios as $ad) {
            $resultado[] = [
                'id' => $ad->ID,
                'titulo' => $ad->post_title,
                'tipo' => get_post_meta($ad->ID, '_ad_tipo', true),
                'url_destino' => get_post_meta($ad->ID, '_ad_url_destino', true),
                'presupuesto' => get_post_meta($ad->ID, '_ad_presupuesto', true),
                'fecha_inicio' => get_post_meta($ad->ID, '_ad_fecha_inicio', true),
                'fecha_fin' => get_post_meta($ad->ID, '_ad_fecha_fin', true),
                'estado' => $ad->post_status,
            ];
        }

        return [
            'success' => true,
            'data' => $resultado,
        ];
    }

    /**
     * Acción: Crear anuncio
     */
    private function action_crear_anuncio($params) {
        $usuario_id = get_current_user_id();
        if (!$usuario_id) {
            return ['success' => false, 'error' => __('Debes iniciar sesión', 'flavor-chat-ia')];
        }

        $titulo = sanitize_text_field($params['titulo'] ?? '');
        $tipo = sanitize_text_field($params['tipo'] ?? 'banner_horizontal');
        $url_destino = esc_url_raw($params['url_destino'] ?? '');
        $imagen = esc_url_raw($params['imagen'] ?? '');
        $presupuesto = floatval($params['presupuesto'] ?? 0);

        if (empty($titulo)) {
            return ['success' => false, 'error' => __('El título es requerido', 'flavor-chat-ia')];
        }

        $post_id = wp_insert_post([
            'post_type' => 'flavor_ad',
            'post_title' => $titulo,
            'post_status' => 'pending',
            'post_author' => $usuario_id,
        ]);

        if (is_wp_error($post_id)) {
            return ['success' => false, 'error' => $post_id->get_error_message()];
        }

        update_post_meta($post_id, '_ad_tipo', $tipo);
        update_post_meta($post_id, '_ad_url_destino', $url_destino);
        update_post_meta($post_id, '_ad_imagen_url', $imagen);
        update_post_meta($post_id, '_ad_presupuesto', $presupuesto);
        update_post_meta($post_id, '_ad_anunciante_id', $usuario_id);
        update_post_meta($post_id, '_ad_reparto_comunidad', 30);

        // Notificar a admins
        do_action('flavor_notificacion_enviar', [
            'tipo' => 'ads_nuevo_pendiente',
            'destinatario' => 'admins',
            'datos' => [
                'ad_id' => $post_id,
                'titulo' => $titulo,
                'anunciante' => get_userdata($usuario_id)->display_name,
            ],
        ]);

        return [
            'success' => true,
            'message' => __('Anuncio creado y pendiente de aprobación', 'flavor-chat-ia'),
            'data' => ['ad_id' => $post_id],
        ];
    }

    /**
     * Acción: Mis anuncios
     */
    private function action_mis_anuncios($params) {
        $usuario_id = get_current_user_id();
        if (!$usuario_id) {
            return ['success' => false, 'error' => __('Debes iniciar sesión', 'flavor-chat-ia')];
        }

        $anuncios = get_posts([
            'post_type' => 'flavor_ad',
            'posts_per_page' => -1,
            'post_status' => ['publish', 'pending', 'draft'],
            'meta_query' => [
                [
                    'key' => '_ad_anunciante_id',
                    'value' => $usuario_id,
                ],
            ],
        ]);

        global $wpdb;
        $tabla_stats = $wpdb->prefix . 'flavor_ads_stats';

        $resultado = [];
        foreach ($anuncios as $ad) {
            $stats = $wpdb->get_row($wpdb->prepare(
                "SELECT SUM(impresiones) as imp, SUM(clics) as cli, SUM(gasto) as gasto
                 FROM $tabla_stats WHERE ad_id = %d",
                $ad->ID
            ));

            $resultado[] = [
                'id' => $ad->ID,
                'titulo' => $ad->post_title,
                'tipo' => get_post_meta($ad->ID, '_ad_tipo', true),
                'estado' => $ad->post_status,
                'presupuesto' => get_post_meta($ad->ID, '_ad_presupuesto', true),
                'impresiones' => (int)($stats->imp ?? 0),
                'clics' => (int)($stats->cli ?? 0),
                'gasto' => (float)($stats->gasto ?? 0),
            ];
        }

        return ['success' => true, 'data' => $resultado];
    }

    /**
     * Acción: Pausar anuncio
     */
    private function action_pausar_anuncio($params) {
        $ad_id = intval($params['ad_id'] ?? 0);
        $usuario_id = get_current_user_id();

        if (!$ad_id) {
            return ['success' => false, 'error' => __('ID de anuncio requerido', 'flavor-chat-ia')];
        }

        $anunciante = get_post_meta($ad_id, '_ad_anunciante_id', true);
        if ($anunciante != $usuario_id && !current_user_can('manage_options')) {
            return ['success' => false, 'error' => __('No tienes permiso', 'flavor-chat-ia')];
        }

        wp_update_post([
            'ID' => $ad_id,
            'post_status' => 'draft',
        ]);

        return ['success' => true, 'message' => __('Anuncio pausado', 'flavor-chat-ia')];
    }

    /**
     * Acción: Reanudar anuncio
     */
    private function action_reanudar_anuncio($params) {
        $ad_id = intval($params['ad_id'] ?? 0);
        $usuario_id = get_current_user_id();

        $anunciante = get_post_meta($ad_id, '_ad_anunciante_id', true);
        if ($anunciante != $usuario_id && !current_user_can('manage_options')) {
            return ['success' => false, 'error' => __('No tienes permiso', 'flavor-chat-ia')];
        }

        wp_update_post([
            'ID' => $ad_id,
            'post_status' => 'publish',
        ]);

        return ['success' => true, 'message' => __('Anuncio reanudado', 'flavor-chat-ia')];
    }

    /**
     * Acción: Mis ingresos (por visualizar anuncios)
     */
    private function action_mis_ingresos($params) {
        global $wpdb;
        $tabla_ingresos = $wpdb->prefix . 'flavor_ads_ingresos';
        $usuario_id = get_current_user_id();

        if (!$usuario_id) {
            return ['success' => false, 'error' => __('Debes iniciar sesión', 'flavor-chat-ia')];
        }

        $periodo = $params['periodo'] ?? 'month';
        $fecha_inicio = $this->get_fecha_inicio_periodo($periodo);

        $ingresos = $wpdb->get_row($wpdb->prepare(
            "SELECT
                SUM(cantidad) as total,
                COUNT(*) as transacciones
             FROM $tabla_ingresos
             WHERE usuario_id = %d AND fecha >= %s AND estado = 'completado'",
            $usuario_id,
            $fecha_inicio
        ));

        $pendiente = $wpdb->get_var($wpdb->prepare(
            "SELECT SUM(cantidad) FROM $tabla_ingresos WHERE usuario_id = %d AND estado = 'pendiente'",
            $usuario_id
        ));

        return [
            'success' => true,
            'data' => [
                'total_periodo' => (float)($ingresos->total ?? 0),
                'transacciones' => (int)($ingresos->transacciones ?? 0),
                'pendiente_pago' => (float)($pendiente ?? 0),
            ],
        ];
    }

    /**
     * Acción: Ubicaciones disponibles
     */
    private function action_ubicaciones_disponibles($params) {
        $ubicaciones = get_terms([
            'taxonomy' => 'ad_ubicacion',
            'hide_empty' => false,
        ]);

        $resultado = [];
        foreach ($ubicaciones as $ub) {
            $resultado[] = [
                'id' => $ub->term_id,
                'nombre' => $ub->name,
                'slug' => $ub->slug,
                'descripcion' => $ub->description,
            ];
        }

        return ['success' => true, 'data' => $resultado];
    }

    /**
     * Registrar rutas REST
     */
    public function register_rest_routes() {
        $namespace = 'flavor/v1';

        register_rest_route($namespace, '/ads', [
            'methods' => 'GET',
            'callback' => [$this, 'rest_listar_anuncios'],
            'permission_callback' => '__return_true',
        ]);

        register_rest_route($namespace, '/ads/(?P<id>\d+)', [
            'methods' => 'GET',
            'callback' => [$this, 'rest_obtener_anuncio'],
            'permission_callback' => '__return_true',
        ]);

        register_rest_route($namespace, '/ads', [
            'methods' => 'POST',
            'callback' => [$this, 'rest_crear_anuncio'],
            'permission_callback' => [$this, 'check_user_logged_in'],
        ]);

        register_rest_route($namespace, '/ads/(?P<id>\d+)', [
            'methods' => 'PUT',
            'callback' => [$this, 'rest_actualizar_anuncio'],
            'permission_callback' => [$this, 'check_ad_permission'],
        ]);

        register_rest_route($namespace, '/ads/(?P<id>\d+)', [
            'methods' => 'DELETE',
            'callback' => [$this, 'rest_eliminar_anuncio'],
            'permission_callback' => [$this, 'check_ad_permission'],
        ]);

        register_rest_route($namespace, '/ads/stats', [
            'methods' => 'GET',
            'callback' => [$this, 'rest_estadisticas'],
            'permission_callback' => [$this, 'check_user_logged_in'],
        ]);

        register_rest_route($namespace, '/ads/serve', [
            'methods' => 'GET',
            'callback' => [$this, 'rest_servir_anuncio'],
            'permission_callback' => '__return_true',
        ]);

        register_rest_route($namespace, '/ads/track/impression', [
            'methods' => 'POST',
            'callback' => [$this, 'rest_track_impression'],
            'permission_callback' => '__return_true',
        ]);

        register_rest_route($namespace, '/ads/track/click', [
            'methods' => 'POST',
            'callback' => [$this, 'rest_track_click'],
            'permission_callback' => '__return_true',
        ]);

        register_rest_route($namespace, '/ads/mis-anuncios', [
            'methods' => 'GET',
            'callback' => [$this, 'rest_mis_anuncios'],
            'permission_callback' => [$this, 'check_user_logged_in'],
        ]);

        register_rest_route($namespace, '/ads/ingresos', [
            'methods' => 'GET',
            'callback' => [$this, 'rest_mis_ingresos'],
            'permission_callback' => [$this, 'check_user_logged_in'],
        ]);
    }

    public function check_user_logged_in() {
        return is_user_logged_in();
    }

    public function check_ad_permission($request) {
        $ad_id = $request['id'];
        $anunciante = get_post_meta($ad_id, '_ad_anunciante_id', true);
        return get_current_user_id() == $anunciante || current_user_can('manage_options');
    }

    /**
     * REST: Listar anuncios
     */
    public function rest_listar_anuncios($request) {
        $tipo = $request->get_param('tipo');
        $ubicacion = $request->get_param('ubicacion');

        $args = [
            'post_type' => 'flavor_ad',
            'posts_per_page' => 50,
            'post_status' => 'publish',
        ];

        $meta_query = [];
        if ($tipo) {
            $meta_query[] = ['key' => '_ad_tipo', 'value' => $tipo];
        }

        // Filtrar por fecha activa
        $hoy = date('Y-m-d');
        $meta_query[] = [
            'relation' => 'OR',
            ['key' => '_ad_fecha_inicio', 'compare' => 'NOT EXISTS'],
            ['key' => '_ad_fecha_inicio', 'value' => $hoy, 'compare' => '<='],
        ];
        $meta_query[] = [
            'relation' => 'OR',
            ['key' => '_ad_fecha_fin', 'compare' => 'NOT EXISTS'],
            ['key' => '_ad_fecha_fin', 'value' => $hoy, 'compare' => '>='],
        ];

        if (!empty($meta_query)) {
            $args['meta_query'] = $meta_query;
        }

        if ($ubicacion) {
            $args['tax_query'] = [
                ['taxonomy' => 'ad_ubicacion', 'field' => 'slug', 'terms' => $ubicacion],
            ];
        }

        $anuncios = get_posts($args);
        $resultado = array_map([$this, 'format_ad_response'], $anuncios);

        return rest_ensure_response($resultado);
    }

    /**
     * REST: Obtener anuncio
     */
    public function rest_obtener_anuncio($request) {
        $ad = get_post($request['id']);

        if (!$ad || $ad->post_type !== 'flavor_ad') {
            return new WP_Error('not_found', __('Anuncio no encontrado', 'flavor-chat-ia'), ['status' => 404]);
        }

        return rest_ensure_response($this->format_ad_response($ad));
    }

    /**
     * REST: Crear anuncio
     */
    public function rest_crear_anuncio($request) {
        $resultado = $this->action_crear_anuncio($request->get_params());

        if (!$resultado['success']) {
            return new WP_Error('error', $resultado['error'], ['status' => 400]);
        }

        return rest_ensure_response($resultado);
    }

    /**
     * REST: Servir anuncio (para mostrar)
     */
    public function rest_servir_anuncio($request) {
        $tipo = $request->get_param('tipo') ?: 'banner_horizontal';
        $ubicacion = $request->get_param('ubicacion');

        $args = [
            'post_type' => 'flavor_ad',
            'posts_per_page' => 1,
            'post_status' => 'publish',
            'orderby' => 'rand',
            'meta_query' => [
                ['key' => '_ad_tipo', 'value' => $tipo],
            ],
        ];

        if ($ubicacion) {
            $args['tax_query'] = [
                ['taxonomy' => 'ad_ubicacion', 'field' => 'slug', 'terms' => $ubicacion],
            ];
        }

        $anuncios = get_posts($args);

        if (empty($anuncios)) {
            return rest_ensure_response(null);
        }

        $ad = $anuncios[0];

        return rest_ensure_response([
            'id' => $ad->ID,
            'titulo' => $ad->post_title,
            'tipo' => get_post_meta($ad->ID, '_ad_tipo', true),
            'imagen' => get_post_meta($ad->ID, '_ad_imagen_url', true) ?: get_the_post_thumbnail_url($ad->ID, 'large'),
            'url' => get_post_meta($ad->ID, '_ad_url_destino', true),
            'cta' => get_post_meta($ad->ID, '_ad_texto_cta', true),
            'html' => get_post_meta($ad->ID, '_ad_contenido_html', true),
        ]);
    }

    /**
     * REST: Track impression
     */
    public function rest_track_impression($request) {
        $ad_id = intval($request->get_param('ad_id'));
        $this->registrar_impresion($ad_id);
        return rest_ensure_response(['success' => true]);
    }

    /**
     * REST: Track click
     */
    public function rest_track_click($request) {
        $ad_id = intval($request->get_param('ad_id'));
        $this->registrar_clic($ad_id);
        return rest_ensure_response(['success' => true]);
    }

    /**
     * REST: Estadísticas
     */
    public function rest_estadisticas($request) {
        $periodo = $request->get_param('periodo') ?: 'month';
        $resultado = $this->action_ver_estadisticas(['periodo' => $periodo]);
        return rest_ensure_response($resultado);
    }

    /**
     * REST: Mis anuncios
     */
    public function rest_mis_anuncios($request) {
        $resultado = $this->action_mis_anuncios([]);
        return rest_ensure_response($resultado);
    }

    /**
     * REST: Mis ingresos
     */
    public function rest_mis_ingresos($request) {
        $periodo = $request->get_param('periodo') ?: 'month';
        $resultado = $this->action_mis_ingresos(['periodo' => $periodo]);
        return rest_ensure_response($resultado);
    }

    /**
     * AJAX: Track impression
     */
    public function ajax_track_impression() {
        $ad_id = intval($_POST['ad_id'] ?? 0);
        $this->registrar_impresion($ad_id);
        wp_send_json_success();
    }

    /**
     * AJAX: Track click
     */
    public function ajax_track_click() {
        $ad_id = intval($_POST['ad_id'] ?? 0);
        $this->registrar_clic($ad_id);
        wp_send_json_success();
    }

    /**
     * AJAX: Crear campaña
     */
    public function ajax_crear_campana() {
        check_ajax_referer('flavor_ads_nonce', 'nonce');

        $resultado = $this->action_crear_anuncio($_POST);
        if ($resultado['success']) {
            wp_send_json_success($resultado);
        } else {
            wp_send_json_error($resultado['error']);
        }
    }

    /**
     * AJAX: Pausar campaña
     */
    public function ajax_pausar_campana() {
        check_ajax_referer('flavor_ads_nonce', 'nonce');

        $resultado = $this->action_pausar_anuncio($_POST);
        if ($resultado['success']) {
            wp_send_json_success($resultado);
        } else {
            wp_send_json_error($resultado['error']);
        }
    }

    /**
     * AJAX: Obtener stats
     */
    public function ajax_obtener_stats() {
        check_ajax_referer('flavor_ads_nonce', 'nonce');

        $resultado = $this->action_ver_estadisticas($_POST);
        wp_send_json($resultado);
    }

    /**
     * AJAX Admin: Aprobar anuncio
     */
    public function ajax_admin_aprobar() {
        check_ajax_referer('flavor_ads_admin_nonce', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_send_json_error(__('Sin permiso', 'flavor-chat-ia'));
        }

        $ad_id = intval($_POST['ad_id']);
        wp_update_post([
            'ID' => $ad_id,
            'post_status' => 'publish',
        ]);

        $anunciante = get_post_meta($ad_id, '_ad_anunciante_id', true);
        if ($anunciante) {
            do_action('flavor_notificacion_enviar', [
                'tipo' => 'ads_anuncio_aprobado',
                'destinatario' => $anunciante,
                'datos' => [
                    'ad_id' => $ad_id,
                    'titulo' => get_the_title($ad_id),
                ],
            ]);
        }

        wp_send_json_success(__('Anuncio aprobado', 'flavor-chat-ia'));
    }

    /**
     * AJAX Admin: Rechazar anuncio
     */
    public function ajax_admin_rechazar() {
        check_ajax_referer('flavor_ads_admin_nonce', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_send_json_error(__('Sin permiso', 'flavor-chat-ia'));
        }

        $ad_id = intval($_POST['ad_id']);
        $motivo = sanitize_textarea_field($_POST['motivo'] ?? '');

        wp_update_post([
            'ID' => $ad_id,
            'post_status' => 'draft',
        ]);

        update_post_meta($ad_id, '_ad_motivo_rechazo', $motivo);

        $anunciante = get_post_meta($ad_id, '_ad_anunciante_id', true);
        if ($anunciante) {
            do_action('flavor_notificacion_enviar', [
                'tipo' => 'ads_anuncio_rechazado',
                'destinatario' => $anunciante,
                'datos' => [
                    'ad_id' => $ad_id,
                    'titulo' => get_the_title($ad_id),
                    'motivo' => $motivo,
                ],
            ]);
        }

        wp_send_json_success(__('Anuncio rechazado', 'flavor-chat-ia'));
    }

    /**
     * AJAX Admin: Procesar pago
     */
    public function ajax_admin_procesar_pago() {
        check_ajax_referer('flavor_ads_admin_nonce', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_send_json_error(__('Sin permiso', 'flavor-chat-ia'));
        }

        global $wpdb;
        $tabla_ingresos = $wpdb->prefix . 'flavor_ads_ingresos';

        $usuario_id = intval($_POST['usuario_id']);
        $cantidad = floatval($_POST['cantidad']);

        $wpdb->update(
            $tabla_ingresos,
            ['estado' => 'completado', 'fecha_pago' => current_time('mysql')],
            ['usuario_id' => $usuario_id, 'estado' => 'pendiente']
        );

        do_action('flavor_notificacion_enviar', [
            'tipo' => 'ads_pago_procesado',
            'destinatario' => $usuario_id,
            'datos' => ['cantidad' => $cantidad],
        ]);

        wp_send_json_success(__('Pago procesado', 'flavor-chat-ia'));
    }

    /**
     * Registrar impresión
     */
    private function registrar_impresion($ad_id) {
        if (!$ad_id) return;

        global $wpdb;
        $tabla_stats = $wpdb->prefix . 'flavor_ads_stats';
        $hoy = date('Y-m-d');

        $existe = $wpdb->get_var($wpdb->prepare(
            "SELECT id FROM $tabla_stats WHERE ad_id = %d AND fecha = %s",
            $ad_id, $hoy
        ));

        $precio_cpm = floatval(get_post_meta($ad_id, '_ad_precio_impresion', true)) ?: 0.001;
        $costo = $precio_cpm / 1000;

        if ($existe) {
            $wpdb->query($wpdb->prepare(
                "UPDATE $tabla_stats SET impresiones = impresiones + 1, gasto = gasto + %f WHERE id = %d",
                $costo, $existe
            ));
        } else {
            $wpdb->insert($tabla_stats, [
                'ad_id' => $ad_id,
                'fecha' => $hoy,
                'impresiones' => 1,
                'clics' => 0,
                'gasto' => $costo,
            ]);
        }

        // Repartir con la comunidad
        $this->repartir_ingreso($ad_id, $costo);
    }

    /**
     * Registrar clic
     */
    private function registrar_clic($ad_id) {
        if (!$ad_id) return;

        global $wpdb;
        $tabla_stats = $wpdb->prefix . 'flavor_ads_stats';
        $hoy = date('Y-m-d');

        $existe = $wpdb->get_var($wpdb->prepare(
            "SELECT id FROM $tabla_stats WHERE ad_id = %d AND fecha = %s",
            $ad_id, $hoy
        ));

        $precio_clic = floatval(get_post_meta($ad_id, '_ad_precio_clic', true)) ?: 0.10;

        if ($existe) {
            $wpdb->query($wpdb->prepare(
                "UPDATE $tabla_stats SET clics = clics + 1, gasto = gasto + %f WHERE id = %d",
                $precio_clic, $existe
            ));
        } else {
            $wpdb->insert($tabla_stats, [
                'ad_id' => $ad_id,
                'fecha' => $hoy,
                'impresiones' => 0,
                'clics' => 1,
                'gasto' => $precio_clic,
            ]);
        }

        // Repartir con la comunidad
        $this->repartir_ingreso($ad_id, $precio_clic);
    }

    /**
     * Repartir ingreso con la comunidad
     */
    private function repartir_ingreso($ad_id, $cantidad) {
        $porcentaje_comunidad = floatval(get_post_meta($ad_id, '_ad_reparto_comunidad', true)) ?: 30;
        $cantidad_comunidad = $cantidad * ($porcentaje_comunidad / 100);

        if ($cantidad_comunidad <= 0) return;

        // Por ahora, acumular en un pool comunitario
        // En una implementación completa, se distribuiría entre usuarios activos
        $pool_actual = get_option('flavor_ads_pool_comunidad', 0);
        update_option('flavor_ads_pool_comunidad', $pool_actual + $cantidad_comunidad);
    }

    /**
     * Shortcode: Mostrar anuncio
     */
    public function shortcode_ad($atts) {
        $atts = shortcode_atts([
            'id' => 0,
            'tipo' => 'banner_horizontal',
            'ubicacion' => '',
            'class' => '',
        ], $atts);

        $this->enqueue_frontend_assets();

        if ($atts['id']) {
            $ad = get_post($atts['id']);
        } else {
            $args = [
                'post_type' => 'flavor_ad',
                'posts_per_page' => 1,
                'post_status' => 'publish',
                'orderby' => 'rand',
                'meta_query' => [
                    ['key' => '_ad_tipo', 'value' => $atts['tipo']],
                ],
            ];

            if ($atts['ubicacion']) {
                $args['tax_query'] = [
                    ['taxonomy' => 'ad_ubicacion', 'field' => 'slug', 'terms' => $atts['ubicacion']],
                ];
            }

            $ads = get_posts($args);
            $ad = $ads[0] ?? null;
        }

        if (!$ad) return '';

        $imagen = get_post_meta($ad->ID, '_ad_imagen_url', true) ?: get_the_post_thumbnail_url($ad->ID, 'large');
        $url = get_post_meta($ad->ID, '_ad_url_destino', true);
        $cta = get_post_meta($ad->ID, '_ad_texto_cta', true);
        $html = get_post_meta($ad->ID, '_ad_contenido_html', true);
        $tipo = get_post_meta($ad->ID, '_ad_tipo', true);

        ob_start();
        ?>
        <div class="flavor-ad flavor-ad-<?php echo esc_attr($tipo); ?> <?php echo esc_attr($atts['class']); ?>"
             data-ad-id="<?php echo $ad->ID; ?>">
            <span class="flavor-ad-label"><?php _e('Anuncio', 'flavor-chat-ia'); ?></span>
            <?php if ($html): ?>
                <?php echo wp_kses_post($html); ?>
            <?php else: ?>
                <a href="<?php echo esc_url($url); ?>" class="flavor-ad-link"
                   target="_blank" rel="noopener sponsored">
                    <?php if ($imagen): ?>
                        <img src="<?php echo esc_url($imagen); ?>" alt="<?php echo esc_attr($ad->post_title); ?>" class="flavor-ad-image">
                    <?php endif; ?>
                    <?php if ($tipo === 'banner_card' || $tipo === 'banner_nativo'): ?>
                        <div class="flavor-ad-content">
                            <h4 class="flavor-ad-title"><?php echo esc_html($ad->post_title); ?></h4>
                            <?php if ($cta): ?>
                                <span class="flavor-ad-cta"><?php echo esc_html($cta); ?></span>
                            <?php endif; ?>
                        </div>
                    <?php endif; ?>
                </a>
            <?php endif; ?>
        </div>
        <?php
        return ob_get_clean();
    }

    /**
     * Shortcode: Dashboard de anunciante
     */
    public function shortcode_dashboard($atts) {
        if (!is_user_logged_in()) {
            return '<p>' . __('Debes iniciar sesión para ver tu panel de anunciante.', 'flavor-chat-ia') . '</p>';
        }

        $this->enqueue_frontend_assets();

        $template_path = FLAVOR_CHAT_PATH . 'includes/modules/advertising/templates/dashboard.php';
        if (file_exists($template_path)) {
            ob_start();
            include $template_path;
            return ob_get_clean();
        }

        // Template inline si no existe el archivo
        $resultado = $this->action_mis_anuncios([]);
        $anuncios = $resultado['data'] ?? [];

        ob_start();
        ?>
        <div class="flavor-ads-dashboard">
            <div class="ads-dashboard-header">
                <h2><?php _e('Mis Anuncios', 'flavor-chat-ia'); ?></h2>
                <a href="<?php echo add_query_arg('vista', 'crear'); ?>" class="btn btn-primary">
                    <?php _e('Crear anuncio', 'flavor-chat-ia'); ?>
                </a>
            </div>

            <?php if ($anuncios): ?>
                <div class="ads-grid">
                    <?php foreach ($anuncios as $ad): ?>
                        <div class="ads-card">
                            <h4><?php echo esc_html($ad['titulo']); ?></h4>
                            <div class="ads-card-meta">
                                <span class="ads-status <?php echo esc_attr($ad['estado']); ?>">
                                    <?php echo esc_html($ad['estado']); ?>
                                </span>
                                <span><?php echo esc_html($ad['tipo']); ?></span>
                            </div>
                            <div class="ads-card-stats">
                                <div>
                                    <strong><?php echo number_format($ad['impresiones']); ?></strong>
                                    <span><?php _e('Impresiones', 'flavor-chat-ia'); ?></span>
                                </div>
                                <div>
                                    <strong><?php echo number_format($ad['clics']); ?></strong>
                                    <span><?php _e('Clics', 'flavor-chat-ia'); ?></span>
                                </div>
                                <div>
                                    <strong><?php echo number_format($ad['gasto'], 2); ?>€</strong>
                                    <span><?php _e('Gasto', 'flavor-chat-ia'); ?></span>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                <div class="ads-empty">
                    <p><?php _e('Aún no tienes anuncios. ¡Crea tu primera campaña!', 'flavor-chat-ia'); ?></p>
                </div>
            <?php endif; ?>
        </div>
        <?php
        return ob_get_clean();
    }

    /**
     * Shortcode: Crear anuncio
     */
    public function shortcode_crear($atts) {
        if (!is_user_logged_in()) {
            return '<p>' . __('Debes iniciar sesión para crear anuncios.', 'flavor-chat-ia') . '</p>';
        }

        $this->enqueue_frontend_assets();

        $template_path = FLAVOR_CHAT_PATH . 'includes/modules/advertising/templates/crear.php';
        if (file_exists($template_path)) {
            ob_start();
            include $template_path;
            return ob_get_clean();
        }

        ob_start();
        ?>
        <div class="flavor-ads-crear">
            <h2><?php _e('Crear nuevo anuncio', 'flavor-chat-ia'); ?></h2>
            <form class="ads-form" id="crear-anuncio-form">
                <?php wp_nonce_field('flavor_ads_nonce', 'nonce'); ?>

                <div class="form-grupo">
                    <label><?php _e('Título del anuncio', 'flavor-chat-ia'); ?> *</label>
                    <input type="text" name="titulo" required>
                </div>

                <div class="form-grupo">
                    <label><?php _e('Tipo de anuncio', 'flavor-chat-ia'); ?></label>
                    <select name="tipo">
                        <option value="banner_horizontal"><?php _e('Banner Horizontal', 'flavor-chat-ia'); ?></option>
                        <option value="banner_sidebar"><?php _e('Banner Sidebar', 'flavor-chat-ia'); ?></option>
                        <option value="banner_card"><?php _e('Tarjeta', 'flavor-chat-ia'); ?></option>
                        <option value="banner_nativo"><?php _e('Nativo', 'flavor-chat-ia'); ?></option>
                    </select>
                </div>

                <div class="form-grupo">
                    <label><?php _e('URL de destino', 'flavor-chat-ia'); ?> *</label>
                    <input type="url" name="url_destino" required placeholder="https://...">
                </div>

                <div class="form-grupo">
                    <label><?php _e('Imagen del anuncio', 'flavor-chat-ia'); ?></label>
                    <input type="url" name="imagen" placeholder="URL de la imagen">
                </div>

                <div class="form-grupo">
                    <label><?php _e('Presupuesto (€)', 'flavor-chat-ia'); ?></label>
                    <input type="number" name="presupuesto" min="0" step="0.01" value="50">
                </div>

                <button type="submit" class="btn btn-primary"><?php _e('Enviar para revisión', 'flavor-chat-ia'); ?></button>
            </form>
        </div>
        <?php
        return ob_get_clean();
    }

    /**
     * Shortcode: Mis ingresos
     */
    public function shortcode_ingresos($atts) {
        if (!is_user_logged_in()) {
            return '<p>' . __('Debes iniciar sesión.', 'flavor-chat-ia') . '</p>';
        }

        $this->enqueue_frontend_assets();

        $resultado = $this->action_mis_ingresos(['periodo' => 'month']);
        $data = $resultado['data'] ?? [];

        ob_start();
        ?>
        <div class="flavor-ads-ingresos">
            <h2><?php _e('Mis Ingresos por Publicidad', 'flavor-chat-ia'); ?></h2>
            <p style="color: #6b7280;"><?php _e('Como miembro de la comunidad, recibes una parte de los ingresos publicitarios.', 'flavor-chat-ia'); ?></p>

            <div class="ingresos-stats">
                <div class="ingreso-card">
                    <span class="ingreso-valor"><?php echo number_format($data['total_periodo'] ?? 0, 2); ?>€</span>
                    <span class="ingreso-label"><?php _e('Este mes', 'flavor-chat-ia'); ?></span>
                </div>
                <div class="ingreso-card">
                    <span class="ingreso-valor"><?php echo number_format($data['pendiente_pago'] ?? 0, 2); ?>€</span>
                    <span class="ingreso-label"><?php _e('Pendiente de pago', 'flavor-chat-ia'); ?></span>
                </div>
            </div>
        </div>
        <?php
        return ob_get_clean();
    }

    /**
     * Enqueue scripts y estilos frontend
     */
    private function enqueue_frontend_assets() {
        static $enqueued = false;
        if ($enqueued) return;

        $base_url = FLAVOR_CHAT_URL . 'includes/modules/advertising/assets/';

        wp_enqueue_style(
            'flavor-ads-frontend',
            $base_url . 'css/advertising-frontend.css',
            [],
            FLAVOR_CHAT_VERSION
        );

        wp_enqueue_script(
            'flavor-ads-frontend',
            $base_url . 'js/advertising-frontend.js',
            ['jquery'],
            FLAVOR_CHAT_VERSION,
            true
        );

        wp_localize_script('flavor-ads-frontend', 'flavorAdsData', [
            'ajaxurl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('flavor_ads_nonce'),
            'strings' => [
                'confirmar' => __('¿Estás seguro?', 'flavor-chat-ia'),
                'error' => __('Ha ocurrido un error', 'flavor-chat-ia'),
                'exito' => __('Operación completada', 'flavor-chat-ia'),
            ],
        ]);

        $enqueued = true;
    }

    /**
     * Enqueue scripts para frontend global (tracking)
     */
    public function enqueue_scripts() {
        if (is_admin()) return;

        wp_enqueue_script(
            'flavor-ads-tracking',
            FLAVOR_CHAT_URL . 'includes/modules/advertising/assets/js/tracking.js',
            ['jquery'],
            FLAVOR_CHAT_VERSION,
            true
        );

        wp_localize_script('flavor-ads-tracking', 'flavorAdsTracking', [
            'ajaxurl' => admin_url('admin-ajax.php'),
        ]);
    }

    /**
     * WP Cron: Procesar pagos pendientes
     */
    public function cron_procesar_pagos() {
        global $wpdb;
        $tabla_ingresos = $wpdb->prefix . 'flavor_ads_ingresos';

        // Obtener usuarios con saldo pendiente superior al mínimo
        $minimo_pago = 10; // 10€ mínimo para pagar

        $usuarios = $wpdb->get_results($wpdb->prepare(
            "SELECT usuario_id, SUM(cantidad) as total
             FROM $tabla_ingresos
             WHERE estado = 'pendiente'
             GROUP BY usuario_id
             HAVING total >= %f",
            $minimo_pago
        ));

        foreach ($usuarios as $usuario) {
            // Notificar que hay pago disponible
            do_action('flavor_notificacion_enviar', [
                'tipo' => 'ads_pago_disponible',
                'destinatario' => $usuario->usuario_id,
                'datos' => ['cantidad' => $usuario->total],
            ]);
        }
    }

    /**
     * WP Cron: Actualizar estadísticas
     */
    public function cron_actualizar_estadisticas() {
        // Verificar presupuestos agotados
        $anuncios = get_posts([
            'post_type' => 'flavor_ad',
            'post_status' => 'publish',
            'posts_per_page' => -1,
        ]);

        global $wpdb;
        $tabla_stats = $wpdb->prefix . 'flavor_ads_stats';

        foreach ($anuncios as $ad) {
            $presupuesto = floatval(get_post_meta($ad->ID, '_ad_presupuesto', true));
            if ($presupuesto <= 0) continue;

            $gasto = $wpdb->get_var($wpdb->prepare(
                "SELECT SUM(gasto) FROM $tabla_stats WHERE ad_id = %d",
                $ad->ID
            ));

            if ($gasto >= $presupuesto) {
                wp_update_post(['ID' => $ad->ID, 'post_status' => 'draft']);

                $anunciante = get_post_meta($ad->ID, '_ad_anunciante_id', true);
                if ($anunciante) {
                    do_action('flavor_notificacion_enviar', [
                        'tipo' => 'ads_presupuesto_agotado',
                        'destinatario' => $anunciante,
                        'datos' => [
                            'ad_id' => $ad->ID,
                            'titulo' => $ad->post_title,
                        ],
                    ]);
                }
            }
        }
    }

    /**
     * Helper: Fecha inicio según periodo
     */
    private function get_fecha_inicio_periodo($periodo) {
        switch ($periodo) {
            case 'today':
                return date('Y-m-d');
            case 'week':
                return date('Y-m-d', strtotime('-7 days'));
            case 'month':
            default:
                return date('Y-m-d', strtotime('-30 days'));
            case 'year':
                return date('Y-m-d', strtotime('-1 year'));
        }
    }

    /**
     * Helper: Formatear respuesta de anuncio
     */
    private function format_ad_response($ad) {
        return [
            'id' => $ad->ID,
            'titulo' => $ad->post_title,
            'tipo' => get_post_meta($ad->ID, '_ad_tipo', true),
            'imagen' => get_post_meta($ad->ID, '_ad_imagen_url', true) ?: get_the_post_thumbnail_url($ad->ID, 'large'),
            'url' => get_post_meta($ad->ID, '_ad_url_destino', true),
            'cta' => get_post_meta($ad->ID, '_ad_texto_cta', true),
            'estado' => $ad->post_status,
        ];
    }

    /**
     * Definiciones de herramientas IA
     */
    public function get_tool_definitions() {
        return [
            [
                'name' => 'advertising_stats',
                'description' => 'Obtener estadísticas de publicidad',
                'input_schema' => [
                    'type' => 'object',
                    'properties' => [
                        'periodo' => ['type' => 'string', 'description' => 'Periodo: today, week, month, year'],
                        'ad_id' => ['type' => 'integer', 'description' => 'ID del anuncio (opcional)'],
                    ],
                ],
            ],
            [
                'name' => 'advertising_crear',
                'description' => 'Crear un nuevo anuncio',
                'input_schema' => [
                    'type' => 'object',
                    'properties' => [
                        'titulo' => ['type' => 'string', 'description' => 'Título del anuncio'],
                        'tipo' => ['type' => 'string', 'description' => 'Tipo: banner_horizontal, banner_sidebar, banner_card, banner_nativo'],
                        'url_destino' => ['type' => 'string', 'description' => 'URL de destino'],
                        'imagen' => ['type' => 'string', 'description' => 'URL de la imagen'],
                        'presupuesto' => ['type' => 'number', 'description' => 'Presupuesto en euros'],
                    ],
                    'required' => ['titulo', 'url_destino'],
                ],
            ],
        ];
    }

    /**
     * Base de conocimiento IA
     */
    public function get_knowledge_base() {
        return <<<KNOWLEDGE
**Publicidad Ética**

Sistema de anuncios responsables con reparto de beneficios a la comunidad.

**Tipos de anuncios:**
- Banner horizontal (728x90)
- Banner sidebar (300x250)
- Anuncio tipo tarjeta
- Anuncio nativo

**Características:**
- Publicidad no intrusiva
- Etiquetado transparente ("Anuncio")
- Reparto de beneficios con la comunidad (configurable, por defecto 30%)
- Sistema de pago por clic (CPC) e impresiones (CPM)
- Aprobación manual de anuncios
- Segmentación por ubicación

**Modelo de ingresos:**
- Los anunciantes pagan por clics e impresiones
- Un porcentaje se reparte con la comunidad
- Los usuarios activos reciben parte de los ingresos
- Pagos cuando se alcanza el mínimo (10€)

**Comandos disponibles:**
- ver_estadisticas: Consultar métricas de publicidad
- listar_anuncios: Ver anuncios activos
- crear_anuncio: Crear nueva campaña
- mis_anuncios: Ver mis campañas como anunciante
- pausar_anuncio / reanudar_anuncio: Gestionar estado
- mis_ingresos: Ver ingresos compartidos
KNOWLEDGE;
    }

    /**
     * Componentes web del módulo
     */
    public function get_web_components() {
        return [
            'banner_horizontal' => [
                'label' => __('Banner Horizontal', 'flavor-chat-ia'),
                'description' => __('Banner publicitario horizontal (728x90 o similar)', 'flavor-chat-ia'),
                'category' => 'content',
                'icon' => 'dashicons-slides',
                'fields' => [
                    'ad_id' => [
                        'type' => 'select',
                        'label' => __('Anuncio', 'flavor-chat-ia'),
                        'options' => $this->get_available_ads(),
                        'default' => '',
                    ],
                    'position' => [
                        'type' => 'select',
                        'label' => __('Posición', 'flavor-chat-ia'),
                        'options' => ['header', 'content_top', 'content_bottom', 'footer'],
                        'default' => 'content_top',
                    ],
                    'mostrar_etiqueta' => [
                        'type' => 'toggle',
                        'label' => __('Mostrar etiqueta "Anuncio"', 'flavor-chat-ia'),
                        'default' => true,
                    ],
                ],
                'template' => 'advertising/banner-horizontal',
            ],
            'banner_sidebar' => [
                'label' => __('Banner Sidebar', 'flavor-chat-ia'),
                'description' => __('Banner vertical para barra lateral (300x250)', 'flavor-chat-ia'),
                'category' => 'content',
                'icon' => 'dashicons-slides',
                'fields' => [
                    'ad_id' => [
                        'type' => 'select',
                        'label' => __('Anuncio', 'flavor-chat-ia'),
                        'options' => $this->get_available_ads(),
                        'default' => '',
                    ],
                    'mostrar_etiqueta' => [
                        'type' => 'toggle',
                        'label' => __('Mostrar etiqueta "Anuncio"', 'flavor-chat-ia'),
                        'default' => true,
                    ],
                    'sticky' => [
                        'type' => 'toggle',
                        'label' => __('Fijo al hacer scroll', 'flavor-chat-ia'),
                        'default' => false,
                    ],
                ],
                'template' => 'advertising/banner-sidebar',
            ],
            'banner_card' => [
                'label' => __('Anuncio Tipo Tarjeta', 'flavor-chat-ia'),
                'description' => __('Anuncio integrado como tarjeta de contenido', 'flavor-chat-ia'),
                'category' => 'cards',
                'icon' => 'dashicons-format-aside',
                'fields' => [
                    'ad_id' => [
                        'type' => 'select',
                        'label' => __('Anuncio', 'flavor-chat-ia'),
                        'options' => $this->get_available_ads(),
                        'default' => '',
                    ],
                    'estilo' => [
                        'type' => 'select',
                        'label' => __('Estilo', 'flavor-chat-ia'),
                        'options' => ['minimal', 'card', 'featured'],
                        'default' => 'card',
                    ],
                ],
                'template' => 'advertising/banner-card',
            ],
            'banner_nativo' => [
                'label' => __('Anuncio Nativo', 'flavor-chat-ia'),
                'description' => __('Anuncio que se integra con el diseño del contenido', 'flavor-chat-ia'),
                'category' => 'content',
                'icon' => 'dashicons-admin-page',
                'fields' => [
                    'ad_id' => [
                        'type' => 'select',
                        'label' => __('Anuncio', 'flavor-chat-ia'),
                        'options' => $this->get_available_ads(),
                        'default' => '',
                    ],
                    'titulo_personalizado' => [
                        'type' => 'text',
                        'label' => __('Título personalizado', 'flavor-chat-ia'),
                        'default' => '',
                    ],
                ],
                'template' => 'advertising/banner-nativo',
            ],
        ];
    }

    /**
     * Obtener anuncios disponibles
     */
    private function get_available_ads() {
        $ads = get_posts([
            'post_type' => 'flavor_ad',
            'posts_per_page' => -1,
            'post_status' => 'publish',
        ]);

        $options = ['' => __('Seleccionar anuncio', 'flavor-chat-ia')];
        foreach ($ads as $ad) {
            $options[$ad->ID] = $ad->post_title;
        }

        return $options;
    }

    /**
     * Configuración del módulo
     */
    public function get_settings() {
        return get_option('flavor_advertising_settings', [
            'reparto_comunidad_default' => 30,
            'precio_clic_default' => 0.10,
            'precio_cpm_default' => 1.00,
            'minimo_pago' => 10,
            'aprobacion_automatica' => false,
            'mostrar_etiqueta' => true,
        ]);
    }

    /**
     * Guardar configuración
     */
    public function save_settings($settings) {
        update_option('flavor_advertising_settings', $settings);
    }

}

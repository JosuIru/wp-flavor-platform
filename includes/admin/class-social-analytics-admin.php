<?php
/**
 * Dashboard de Analytics Social
 *
 * Panel de administración con métricas de uso de la red social.
 *
 * @package Flavor_Chat_IA
 * @since 1.6.0
 */

if (!defined('ABSPATH')) {
    exit;
}

class Flavor_Social_Analytics_Admin {

    /**
     * Instancia singleton
     */
    private static $instance = null;

    /**
     * Prefijo de tablas
     */
    private $prefix;

    /**
     * Cache TTL (1 hora)
     */
    const CACHE_TTL = 3600;

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
        global $wpdb;
        $this->prefix = $wpdb->prefix . 'flavor_';

        $this->init_hooks();
    }

    /**
     * Inicializar hooks
     */
    private function init_hooks() {
        add_action('admin_menu', [$this, 'add_admin_menu']);
        add_action('admin_enqueue_scripts', [$this, 'enqueue_assets']);
        add_action('wp_ajax_flavor_analytics_get_data', [$this, 'ajax_get_analytics_data']);
        add_action('wp_ajax_flavor_analytics_export', [$this, 'ajax_export_report']);

        // REST API
        add_action('rest_api_init', [$this, 'register_rest_routes']);
    }

    /**
     * Añadir menú admin
     */
    public function add_admin_menu() {
        add_submenu_page(
            'flavor-settings',
            __('Analytics Social', FLAVOR_PLATFORM_TEXT_DOMAIN),
            __('Analytics', FLAVOR_PLATFORM_TEXT_DOMAIN),
            'manage_options',
            'flavor-social-analytics',
            [$this, 'render_admin_page']
        );
    }

    /**
     * Enqueue assets
     */
    public function enqueue_assets($hook) {
        if (strpos($hook, 'flavor-social-analytics') === false) {
            return;
        }

        // Chart.js
        wp_enqueue_script(
            'chartjs',
            'https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js',
            [],
            '4.4.1',
            true
        );

        // Estilos propios
        wp_enqueue_style(
            'flavor-social-analytics',
            FLAVOR_CHAT_IA_URL . 'includes/admin/css/social-analytics.css',
            [],
            FLAVOR_CHAT_IA_VERSION
        );

        // Scripts propios
        wp_enqueue_script(
            'flavor-social-analytics',
            FLAVOR_CHAT_IA_URL . 'includes/admin/js/social-analytics.js',
            ['jquery', 'chartjs'],
            FLAVOR_CHAT_IA_VERSION,
            true
        );

        wp_localize_script('flavor-social-analytics', 'flavorAnalytics', [
            'ajaxUrl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('flavor_analytics_nonce'),
            'strings' => [
                'loading' => __('Cargando...', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'error' => __('Error al cargar datos', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'usuarios' => __('Usuarios', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'publicaciones' => __('Publicaciones', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'comentarios' => __('Comentarios', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'likes' => __('Likes', FLAVOR_PLATFORM_TEXT_DOMAIN),
            ]
        ]);
    }

    /**
     * Renderizar página admin
     */
    public function render_admin_page() {
        if (!current_user_can('manage_options')) {
            wp_die(__('No tienes permisos para acceder a esta página.', FLAVOR_PLATFORM_TEXT_DOMAIN));
        }

        $periodo = isset($_GET['periodo']) ? sanitize_text_field($_GET['periodo']) : 'semana';
        $stats = $this->get_general_stats($periodo);
        $trending = $this->get_trending_hashtags();
        $top_users = $this->get_top_users();
        $top_posts = $this->get_top_posts();
        $comunidades_stats = $this->get_comunidades_stats();

        include dirname(__FILE__) . '/views/social-analytics.php';
    }

    /**
     * Obtener estadísticas generales
     */
    public function get_general_stats($periodo = 'semana') {
        $cache_key = 'flavor_analytics_general_' . $periodo;
        $cached = get_transient($cache_key);

        if ($cached !== false) {
            return $cached;
        }

        global $wpdb;

        $fecha_inicio = $this->get_fecha_inicio($periodo);
        $fecha_anterior = $this->get_fecha_anterior($periodo);

        $stats = [
            'periodo' => $periodo,
            'fecha_inicio' => $fecha_inicio,
        ];

        // Usuarios activos
        $tabla_perfiles = $this->prefix . 'social_perfiles';
        if (Flavor_Chat_Helpers::tabla_existe($tabla_perfiles)) {
            $stats['usuarios_activos'] = (int) $wpdb->get_var($wpdb->prepare(
                "SELECT COUNT(DISTINCT usuario_id) FROM {$tabla_perfiles}
                 WHERE ultima_actividad >= %s",
                $fecha_inicio
            ));

            $stats['usuarios_activos_anterior'] = (int) $wpdb->get_var($wpdb->prepare(
                "SELECT COUNT(DISTINCT usuario_id) FROM {$tabla_perfiles}
                 WHERE ultima_actividad >= %s AND ultima_actividad < %s",
                $fecha_anterior, $fecha_inicio
            ));

            $stats['usuarios_nuevos'] = (int) $wpdb->get_var($wpdb->prepare(
                "SELECT COUNT(*) FROM {$tabla_perfiles}
                 WHERE fecha_creacion >= %s",
                $fecha_inicio
            ));
        }

        // Publicaciones
        $tabla_publicaciones = $this->prefix . 'social_publicaciones';
        if (Flavor_Chat_Helpers::tabla_existe($tabla_publicaciones)) {
            $stats['publicaciones'] = (int) $wpdb->get_var($wpdb->prepare(
                "SELECT COUNT(*) FROM {$tabla_publicaciones}
                 WHERE fecha_creacion >= %s",
                $fecha_inicio
            ));

            $stats['publicaciones_anterior'] = (int) $wpdb->get_var($wpdb->prepare(
                "SELECT COUNT(*) FROM {$tabla_publicaciones}
                 WHERE fecha_creacion >= %s AND fecha_creacion < %s",
                $fecha_anterior, $fecha_inicio
            ));
        }

        // Likes
        $tabla_likes = $this->prefix . 'social_likes';
        if (Flavor_Chat_Helpers::tabla_existe($tabla_likes)) {
            $stats['likes'] = (int) $wpdb->get_var($wpdb->prepare(
                "SELECT COUNT(*) FROM {$tabla_likes}
                 WHERE fecha >= %s",
                $fecha_inicio
            ));
        }

        // Comentarios
        $tabla_comentarios = $this->prefix . 'social_comentarios';
        if (Flavor_Chat_Helpers::tabla_existe($tabla_comentarios)) {
            $stats['comentarios'] = (int) $wpdb->get_var($wpdb->prepare(
                "SELECT COUNT(*) FROM {$tabla_comentarios}
                 WHERE fecha_creacion >= %s",
                $fecha_inicio
            ));
        }

        // Engagement rate
        if (!empty($stats['publicaciones']) && $stats['publicaciones'] > 0) {
            $total_interacciones = ($stats['likes'] ?? 0) + ($stats['comentarios'] ?? 0);
            $stats['engagement_rate'] = round(($total_interacciones / $stats['publicaciones']) * 100, 2);
        } else {
            $stats['engagement_rate'] = 0;
        }

        // Calcular tendencias (% cambio)
        $stats['tendencia_usuarios'] = $this->calcular_tendencia(
            $stats['usuarios_activos'] ?? 0,
            $stats['usuarios_activos_anterior'] ?? 0
        );
        $stats['tendencia_publicaciones'] = $this->calcular_tendencia(
            $stats['publicaciones'] ?? 0,
            $stats['publicaciones_anterior'] ?? 0
        );

        // Datos para gráfico temporal
        $stats['grafico_actividad'] = $this->get_actividad_temporal($periodo);

        set_transient($cache_key, $stats, self::CACHE_TTL);

        return $stats;
    }

    /**
     * Obtener actividad temporal para gráfico
     */
    private function get_actividad_temporal($periodo) {
        global $wpdb;

        $dias = $this->get_dias_periodo($periodo);
        $fecha_inicio = date('Y-m-d', strtotime("-{$dias} days"));

        $datos = [
            'labels' => [],
            'publicaciones' => [],
            'comentarios' => [],
            'likes' => [],
        ];

        $tabla_publicaciones = $this->prefix . 'social_publicaciones';
        $tabla_comentarios = $this->prefix . 'social_comentarios';
        $tabla_likes = $this->prefix . 'social_likes';

        for ($i = $dias - 1; $i >= 0; $i--) {
            $fecha = date('Y-m-d', strtotime("-{$i} days"));
            $fecha_siguiente = date('Y-m-d', strtotime("-" . ($i - 1) . " days"));

            $datos['labels'][] = date_i18n('d M', strtotime($fecha));

            // Publicaciones
            if (Flavor_Chat_Helpers::tabla_existe($tabla_publicaciones)) {
                $datos['publicaciones'][] = (int) $wpdb->get_var($wpdb->prepare(
                    "SELECT COUNT(*) FROM {$tabla_publicaciones}
                     WHERE DATE(fecha_creacion) = %s",
                    $fecha
                ));
            } else {
                $datos['publicaciones'][] = 0;
            }

            // Comentarios
            if (Flavor_Chat_Helpers::tabla_existe($tabla_comentarios)) {
                $datos['comentarios'][] = (int) $wpdb->get_var($wpdb->prepare(
                    "SELECT COUNT(*) FROM {$tabla_comentarios}
                     WHERE DATE(fecha_creacion) = %s",
                    $fecha
                ));
            } else {
                $datos['comentarios'][] = 0;
            }

            // Likes
            if (Flavor_Chat_Helpers::tabla_existe($tabla_likes)) {
                $datos['likes'][] = (int) $wpdb->get_var($wpdb->prepare(
                    "SELECT COUNT(*) FROM {$tabla_likes}
                     WHERE DATE(fecha) = %s",
                    $fecha
                ));
            } else {
                $datos['likes'][] = 0;
            }
        }

        return $datos;
    }

    /**
     * Obtener hashtags trending
     */
    public function get_trending_hashtags($limite = 10) {
        global $wpdb;

        $tabla_hashtags = $this->prefix . 'social_hashtags';
        $tabla_publicaciones_hashtags = $this->prefix . 'social_publicaciones_hashtags';

        if (!Flavor_Chat_Helpers::tabla_existe($tabla_hashtags)) {
            return [];
        }

        $fecha_inicio = date('Y-m-d H:i:s', strtotime('-7 days'));

        $hashtags = $wpdb->get_results($wpdb->prepare(
            "SELECT h.id, h.nombre, COUNT(ph.publicacion_id) as usos
             FROM {$tabla_hashtags} h
             LEFT JOIN {$tabla_publicaciones_hashtags} ph ON h.id = ph.hashtag_id
             LEFT JOIN {$this->prefix}social_publicaciones p ON ph.publicacion_id = p.id
             WHERE p.fecha_creacion >= %s OR p.fecha_creacion IS NULL
             GROUP BY h.id
             ORDER BY usos DESC
             LIMIT %d",
            $fecha_inicio, $limite
        ));

        return $hashtags ?: [];
    }

    /**
     * Obtener top usuarios por engagement
     */
    public function get_top_users($limite = 10) {
        global $wpdb;

        $tabla_perfiles = $this->prefix . 'social_perfiles';

        if (!Flavor_Chat_Helpers::tabla_existe($tabla_perfiles)) {
            return [];
        }

        $usuarios = $wpdb->get_results($wpdb->prepare(
            "SELECT p.usuario_id, p.bio, p.seguidores_count, p.publicaciones_count,
                    u.display_name, u.user_email
             FROM {$tabla_perfiles} p
             INNER JOIN {$wpdb->users} u ON p.usuario_id = u.ID
             ORDER BY p.seguidores_count DESC
             LIMIT %d",
            $limite
        ));

        foreach ($usuarios as &$usuario) {
            $usuario->avatar = get_avatar_url($usuario->usuario_id, ['size' => 48]);
        }

        return $usuarios ?: [];
    }

    /**
     * Obtener top publicaciones
     */
    public function get_top_posts($limite = 10) {
        global $wpdb;

        $tabla_publicaciones = $this->prefix . 'social_publicaciones';

        if (!Flavor_Chat_Helpers::tabla_existe($tabla_publicaciones)) {
            return [];
        }

        $fecha_inicio = date('Y-m-d H:i:s', strtotime('-30 days'));

        $publicaciones = $wpdb->get_results($wpdb->prepare(
            "SELECT p.id, p.contenido, p.usuario_id, p.likes_count, p.comentarios_count,
                    p.compartidos_count, p.fecha_creacion, u.display_name
             FROM {$tabla_publicaciones} p
             INNER JOIN {$wpdb->users} u ON p.usuario_id = u.ID
             WHERE p.fecha_creacion >= %s AND p.estado = 'publicado'
             ORDER BY (p.likes_count + p.comentarios_count * 2 + p.compartidos_count * 3) DESC
             LIMIT %d",
            $fecha_inicio, $limite
        ));

        foreach ($publicaciones as &$post) {
            $post->avatar = get_avatar_url($post->usuario_id, ['size' => 32]);
            $post->contenido_corto = wp_trim_words($post->contenido, 20);
            $post->engagement = $post->likes_count + ($post->comentarios_count * 2) + ($post->compartidos_count * 3);
        }

        return $publicaciones ?: [];
    }

    /**
     * Obtener estadísticas de comunidades
     */
    public function get_comunidades_stats() {
        global $wpdb;

        $tabla_comunidades = $this->prefix . 'comunidades';
        $tabla_miembros = $this->prefix . 'comunidades_miembros';

        if (!Flavor_Chat_Helpers::tabla_existe($tabla_comunidades)) {
            return [];
        }

        $stats = [];

        // Total comunidades activas
        $stats['total'] = (int) $wpdb->get_var(
            "SELECT COUNT(*) FROM {$tabla_comunidades} WHERE estado = 'activa'"
        );

        // Top comunidades por miembros
        $stats['top_comunidades'] = $wpdb->get_results(
            "SELECT id, nombre, miembros_count, tipo
             FROM {$tabla_comunidades}
             WHERE estado = 'activa'
             ORDER BY miembros_count DESC
             LIMIT 5"
        );

        // Nuevas comunidades (última semana)
        $stats['nuevas'] = (int) $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM {$tabla_comunidades}
             WHERE fecha_creacion >= %s",
            date('Y-m-d', strtotime('-7 days'))
        ));

        return $stats;
    }

    /**
     * Obtener estadísticas de estados/stories
     */
    public function get_estados_stats() {
        global $wpdb;

        $tabla_estados = $this->prefix . 'chat_estados';

        if (!Flavor_Chat_Helpers::tabla_existe($tabla_estados)) {
            return [];
        }

        $stats = [];

        // Estados últimas 24h
        $stats['ultimas_24h'] = (int) $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM {$tabla_estados}
             WHERE fecha_creacion >= %s",
            date('Y-m-d H:i:s', strtotime('-24 hours'))
        ));

        // Usuarios que publicaron estados
        $stats['usuarios_activos'] = (int) $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(DISTINCT usuario_id) FROM {$tabla_estados}
             WHERE fecha_creacion >= %s",
            date('Y-m-d H:i:s', strtotime('-24 hours'))
        ));

        // Vistas totales
        $tabla_vistas = $this->prefix . 'chat_estados_vistas';
        if (Flavor_Chat_Helpers::tabla_existe($tabla_vistas)) {
            $stats['vistas_24h'] = (int) $wpdb->get_var($wpdb->prepare(
                "SELECT COUNT(*) FROM {$tabla_vistas}
                 WHERE fecha_visto >= %s",
                date('Y-m-d H:i:s', strtotime('-24 hours'))
            ));
        }

        return $stats;
    }

    /**
     * Calcular tendencia porcentual
     */
    private function calcular_tendencia($actual, $anterior) {
        if ($anterior == 0) {
            return $actual > 0 ? 100 : 0;
        }
        return round((($actual - $anterior) / $anterior) * 100, 1);
    }

    /**
     * Obtener fecha de inicio según período
     */
    private function get_fecha_inicio($periodo) {
        $dias = $this->get_dias_periodo($periodo);
        return date('Y-m-d H:i:s', strtotime("-{$dias} days"));
    }

    /**
     * Obtener fecha del período anterior para comparación
     */
    private function get_fecha_anterior($periodo) {
        $dias = $this->get_dias_periodo($periodo) * 2;
        return date('Y-m-d H:i:s', strtotime("-{$dias} days"));
    }

    /**
     * Obtener días según período
     */
    private function get_dias_periodo($periodo) {
        switch ($periodo) {
            case 'hoy':
                return 1;
            case 'semana':
                return 7;
            case 'mes':
                return 30;
            case 'trimestre':
                return 90;
            case 'año':
                return 365;
            default:
                return 7;
        }
    }

    /**
     * AJAX: Obtener datos de analytics
     */
    public function ajax_get_analytics_data() {
        check_ajax_referer('flavor_analytics_nonce', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_send_json_error(['message' => 'Sin permisos'], 403);
        }

        $periodo = sanitize_text_field($_POST['periodo'] ?? 'semana');
        $tipo = sanitize_text_field($_POST['tipo'] ?? 'general');

        switch ($tipo) {
            case 'general':
                $data = $this->get_general_stats($periodo);
                break;
            case 'trending':
                $data = $this->get_trending_hashtags();
                break;
            case 'top_users':
                $data = $this->get_top_users();
                break;
            case 'top_posts':
                $data = $this->get_top_posts();
                break;
            case 'comunidades':
                $data = $this->get_comunidades_stats();
                break;
            case 'estados':
                $data = $this->get_estados_stats();
                break;
            default:
                $data = $this->get_general_stats($periodo);
        }

        wp_send_json_success($data);
    }

    /**
     * AJAX: Exportar reporte
     */
    public function ajax_export_report() {
        check_ajax_referer('flavor_analytics_nonce', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_send_json_error(['message' => 'Sin permisos'], 403);
        }

        $periodo = sanitize_text_field($_POST['periodo'] ?? 'semana');
        $formato = sanitize_text_field($_POST['formato'] ?? 'csv');

        $stats = $this->get_general_stats($periodo);
        $trending = $this->get_trending_hashtags();
        $top_users = $this->get_top_users();
        $comunidades = $this->get_comunidades_stats();

        $reporte = [
            'generado' => current_time('mysql'),
            'periodo' => $periodo,
            'estadisticas_generales' => $stats,
            'hashtags_trending' => $trending,
            'top_usuarios' => $top_users,
            'comunidades' => $comunidades,
        ];

        if ($formato === 'json') {
            wp_send_json_success($reporte);
        } else {
            // CSV
            $csv = $this->generate_csv_report($reporte);
            wp_send_json_success([
                'csv' => $csv,
                'filename' => 'analytics_' . $periodo . '_' . date('Y-m-d') . '.csv'
            ]);
        }
    }

    /**
     * Generar reporte CSV
     */
    private function generate_csv_report($reporte) {
        $lines = [];

        // Header
        $lines[] = 'Reporte de Analytics Social';
        $lines[] = 'Generado: ' . $reporte['generado'];
        $lines[] = 'Periodo: ' . $reporte['periodo'];
        $lines[] = '';

        // Estadísticas generales
        $lines[] = 'ESTADISTICAS GENERALES';
        $lines[] = 'Metrica,Valor';
        $stats = $reporte['estadisticas_generales'];
        $lines[] = 'Usuarios Activos,' . ($stats['usuarios_activos'] ?? 0);
        $lines[] = 'Usuarios Nuevos,' . ($stats['usuarios_nuevos'] ?? 0);
        $lines[] = 'Publicaciones,' . ($stats['publicaciones'] ?? 0);
        $lines[] = 'Likes,' . ($stats['likes'] ?? 0);
        $lines[] = 'Comentarios,' . ($stats['comentarios'] ?? 0);
        $lines[] = 'Engagement Rate,' . ($stats['engagement_rate'] ?? 0) . '%';
        $lines[] = '';

        // Hashtags
        $lines[] = 'HASHTAGS TRENDING';
        $lines[] = 'Hashtag,Usos';
        foreach ($reporte['hashtags_trending'] as $tag) {
            $lines[] = '#' . $tag->nombre . ',' . $tag->usos;
        }
        $lines[] = '';

        // Top usuarios
        $lines[] = 'TOP USUARIOS';
        $lines[] = 'Usuario,Seguidores,Publicaciones';
        foreach ($reporte['top_usuarios'] as $user) {
            $lines[] = $user->display_name . ',' . $user->seguidores_count . ',' . $user->publicaciones_count;
        }

        return implode("\n", $lines);
    }

    /**
     * Registrar rutas REST
     */
    public function register_rest_routes() {
        register_rest_route('flavor-app/v1', '/analytics/stats', [
            'methods' => 'GET',
            'callback' => [$this, 'rest_get_stats'],
            'permission_callback' => function() {
                return current_user_can('manage_options');
            }
        ]);

        register_rest_route('flavor-app/v1', '/analytics/export', [
            'methods' => 'POST',
            'callback' => [$this, 'rest_export'],
            'permission_callback' => function() {
                return current_user_can('manage_options');
            }
        ]);
    }

    /**
     * REST: Obtener estadísticas
     */
    public function rest_get_stats(WP_REST_Request $request) {
        $periodo = $request->get_param('periodo') ?: 'semana';

        return new WP_REST_Response([
            'general' => $this->get_general_stats($periodo),
            'trending' => $this->get_trending_hashtags(),
            'top_users' => $this->get_top_users(5),
            'comunidades' => $this->get_comunidades_stats(),
            'estados' => $this->get_estados_stats(),
        ]);
    }

    /**
     * REST: Exportar
     */
    public function rest_export(WP_REST_Request $request) {
        $periodo = $request->get_param('periodo') ?: 'semana';

        return new WP_REST_Response([
            'generado' => current_time('mysql'),
            'periodo' => $periodo,
            'estadisticas' => $this->get_general_stats($periodo),
            'hashtags' => $this->get_trending_hashtags(),
            'usuarios' => $this->get_top_users(),
            'comunidades' => $this->get_comunidades_stats(),
        ]);
    }
}

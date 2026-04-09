<?php
/**
 * Dashboard de Analytics para administradores
 *
 * Panel unificado con KPIs, gráficos y métricas de la plataforma
 *
 * @package Flavor_Chat_IA
 */

if (!defined('ABSPATH')) {
    exit;
}

class Flavor_Analytics_Dashboard {

    /**
     * Instancia única
     */
    private static $instance = null;

    /**
     * Prefijo de tablas
     */
    private $prefix;

    /**
     * Constructor privado
     */
    private function __construct() {
        global $wpdb;
        $this->prefix = $wpdb->prefix . 'flavor_';
        $this->init_hooks();
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
     * Inicializar hooks
     */
    private function init_hooks() {
        add_action('admin_menu', [$this, 'add_menu'], 25);
        add_action('admin_enqueue_scripts', [$this, 'enqueue_assets']);
        add_action('wp_ajax_flavor_analytics_data', [$this, 'ajax_get_analytics_data']);
        add_action('wp_ajax_flavor_export_analytics', [$this, 'ajax_export_analytics']);
    }

    /**
     * Añadir menú de administración
     */
    public function add_menu() {
        add_submenu_page(
            FLAVOR_PLATFORM_TEXT_DOMAIN,
            __('Analytics', FLAVOR_PLATFORM_TEXT_DOMAIN),
            __('Analytics', FLAVOR_PLATFORM_TEXT_DOMAIN),
            'manage_options',
            'flavor-analytics',
            [$this, 'render_dashboard']
        );
    }

    /**
     * Cargar assets
     */
    public function enqueue_assets($hook) {
        if ($hook !== 'flavor-platform_page_flavor-analytics') {
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

        // Estilos del dashboard
        wp_enqueue_style(
            'flavor-analytics-dashboard',
            FLAVOR_CHAT_IA_URL . 'admin/css/analytics-dashboard.css',
            [],
            FLAVOR_CHAT_IA_VERSION
        );

        // Script del dashboard
        wp_enqueue_script(
            'flavor-analytics-dashboard',
            FLAVOR_CHAT_IA_URL . 'admin/js/analytics-dashboard.js',
            ['jquery', 'chartjs'],
            FLAVOR_CHAT_IA_VERSION,
            true
        );

        wp_localize_script('flavor-analytics-dashboard', 'flavorAnalytics', [
            'ajaxUrl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('flavor_analytics'),
            'i18n' => [
                'loading' => __('Cargando...', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'error' => __('Error al cargar datos', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'noData' => __('Sin datos disponibles', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'usuarios' => __('Usuarios', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'contenido' => __('Contenido', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'engagement' => __('Engagement', FLAVOR_PLATFORM_TEXT_DOMAIN)
            ]
        ]);
    }

    /**
     * Renderizar dashboard
     */
    public function render_dashboard() {
        if (!current_user_can('manage_options')) {
            wp_die(__('No tienes permisos para acceder a esta página.', FLAVOR_PLATFORM_TEXT_DOMAIN));
        }

        $kpis = $this->get_kpis();
        ?>
        <div class="wrap flavor-analytics-wrap">
            <h1 class="wp-heading-inline">
                <span class="dashicons dashicons-chart-area"></span>
                <?php _e('Analytics Dashboard', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
            </h1>

            <!-- Filtros de período -->
            <div class="flavor-analytics-filters">
                <select id="analytics-period" class="flavor-analytics-period">
                    <option value="7"><?php _e('Últimos 7 días', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></option>
                    <option value="30" selected><?php _e('Últimos 30 días', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></option>
                    <option value="90"><?php _e('Últimos 90 días', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></option>
                    <option value="365"><?php _e('Último año', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></option>
                </select>
                <button type="button" class="button" id="btn-refresh-analytics">
                    <span class="dashicons dashicons-update"></span>
                    <?php _e('Actualizar', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                </button>
                <button type="button" class="button" id="btn-export-analytics">
                    <span class="dashicons dashicons-download"></span>
                    <?php _e('Exportar CSV', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                </button>
            </div>

            <!-- KPIs principales -->
            <div class="flavor-analytics-kpis">
                <div class="kpi-card">
                    <div class="kpi-icon" style="background: #3b82f6;">
                        <span class="dashicons dashicons-groups"></span>
                    </div>
                    <div class="kpi-content">
                        <div class="kpi-value" id="kpi-usuarios"><?php echo number_format($kpis['usuarios_activos']); ?></div>
                        <div class="kpi-label"><?php _e('Usuarios Activos', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></div>
                        <div class="kpi-change <?php echo $kpis['usuarios_cambio'] >= 0 ? 'positive' : 'negative'; ?>">
                            <?php echo ($kpis['usuarios_cambio'] >= 0 ? '+' : '') . $kpis['usuarios_cambio']; ?>%
                        </div>
                    </div>
                </div>

                <div class="kpi-card">
                    <div class="kpi-icon" style="background: #10b981;">
                        <span class="dashicons dashicons-edit-large"></span>
                    </div>
                    <div class="kpi-content">
                        <div class="kpi-value" id="kpi-contenido"><?php echo number_format($kpis['contenido_creado']); ?></div>
                        <div class="kpi-label"><?php _e('Contenido Creado', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></div>
                        <div class="kpi-change <?php echo $kpis['contenido_cambio'] >= 0 ? 'positive' : 'negative'; ?>">
                            <?php echo ($kpis['contenido_cambio'] >= 0 ? '+' : '') . $kpis['contenido_cambio']; ?>%
                        </div>
                    </div>
                </div>

                <div class="kpi-card">
                    <div class="kpi-icon" style="background: #f59e0b;">
                        <span class="dashicons dashicons-heart"></span>
                    </div>
                    <div class="kpi-content">
                        <div class="kpi-value" id="kpi-engagement"><?php echo number_format($kpis['interacciones']); ?></div>
                        <div class="kpi-label"><?php _e('Interacciones', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></div>
                        <div class="kpi-change <?php echo $kpis['engagement_cambio'] >= 0 ? 'positive' : 'negative'; ?>">
                            <?php echo ($kpis['engagement_cambio'] >= 0 ? '+' : '') . $kpis['engagement_cambio']; ?>%
                        </div>
                    </div>
                </div>

                <div class="kpi-card">
                    <div class="kpi-icon" style="background: #8b5cf6;">
                        <span class="dashicons dashicons-calendar-alt"></span>
                    </div>
                    <div class="kpi-content">
                        <div class="kpi-value" id="kpi-eventos"><?php echo number_format($kpis['eventos_activos']); ?></div>
                        <div class="kpi-label"><?php _e('Eventos Activos', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></div>
                        <div class="kpi-change <?php echo $kpis['eventos_cambio'] >= 0 ? 'positive' : 'negative'; ?>">
                            <?php echo ($kpis['eventos_cambio'] >= 0 ? '+' : '') . $kpis['eventos_cambio']; ?>%
                        </div>
                    </div>
                </div>
            </div>

            <!-- Gráficos -->
            <div class="flavor-analytics-charts">
                <div class="chart-card chart-large">
                    <h3><?php _e('Actividad por Día', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></h3>
                    <canvas id="chart-actividad"></canvas>
                </div>

                <div class="chart-card">
                    <h3><?php _e('Distribución por Módulo', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></h3>
                    <canvas id="chart-modulos"></canvas>
                </div>

                <div class="chart-card">
                    <h3><?php _e('Tipos de Interacción', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></h3>
                    <canvas id="chart-interacciones"></canvas>
                </div>
            </div>

            <!-- Tablas de datos -->
            <div class="flavor-analytics-tables">
                <div class="table-card">
                    <h3><?php _e('Top Usuarios', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></h3>
                    <table class="widefat striped">
                        <thead>
                            <tr>
                                <th><?php _e('Usuario', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></th>
                                <th><?php _e('Nivel', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></th>
                                <th><?php _e('Puntos', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></th>
                                <th><?php _e('Actividad', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></th>
                            </tr>
                        </thead>
                        <tbody id="table-top-usuarios">
                            <?php echo $this->render_top_usuarios(); ?>
                        </tbody>
                    </table>
                </div>

                <div class="table-card">
                    <h3><?php _e('Contenido Popular', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></h3>
                    <table class="widefat striped">
                        <thead>
                            <tr>
                                <th><?php _e('Contenido', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></th>
                                <th><?php _e('Tipo', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></th>
                                <th><?php _e('Vistas', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></th>
                                <th><?php _e('Interacciones', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></th>
                            </tr>
                        </thead>
                        <tbody id="table-top-contenido">
                            <?php echo $this->render_top_contenido(); ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Métricas por módulo -->
            <div class="flavor-analytics-modules">
                <h3><?php _e('Métricas por Módulo', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></h3>
                <div class="module-metrics-grid" id="module-metrics">
                    <?php echo $this->render_module_metrics(); ?>
                </div>
            </div>
        </div>
        <?php
    }

    /**
     * Obtener KPIs principales
     */
    public function get_kpis($dias = 30) {
        global $wpdb;

        $fecha_inicio = date('Y-m-d', strtotime("-{$dias} days"));
        $fecha_anterior = date('Y-m-d', strtotime("-" . ($dias * 2) . " days"));

        // Usuarios activos (con actividad en el período)
        $usuarios_activos = $this->count_active_users($fecha_inicio);
        $usuarios_anterior = $this->count_active_users($fecha_anterior, $fecha_inicio);
        $usuarios_cambio = $usuarios_anterior > 0
            ? round((($usuarios_activos - $usuarios_anterior) / $usuarios_anterior) * 100)
            : 0;

        // Contenido creado
        $contenido_actual = $this->count_content_created($fecha_inicio);
        $contenido_anterior = $this->count_content_created($fecha_anterior, $fecha_inicio);
        $contenido_cambio = $contenido_anterior > 0
            ? round((($contenido_actual - $contenido_anterior) / $contenido_anterior) * 100)
            : 0;

        // Interacciones
        $interacciones_actual = $this->count_interactions($fecha_inicio);
        $interacciones_anterior = $this->count_interactions($fecha_anterior, $fecha_inicio);
        $engagement_cambio = $interacciones_anterior > 0
            ? round((($interacciones_actual - $interacciones_anterior) / $interacciones_anterior) * 100)
            : 0;

        // Eventos activos
        $eventos_actual = $this->count_active_events();
        $eventos_anterior = $this->count_events_in_period($fecha_anterior, $fecha_inicio);
        $eventos_cambio = $eventos_anterior > 0
            ? round((($eventos_actual - $eventos_anterior) / $eventos_anterior) * 100)
            : 0;

        return [
            'usuarios_activos' => $usuarios_activos,
            'usuarios_cambio' => $usuarios_cambio,
            'contenido_creado' => $contenido_actual,
            'contenido_cambio' => $contenido_cambio,
            'interacciones' => $interacciones_actual,
            'engagement_cambio' => $engagement_cambio,
            'eventos_activos' => $eventos_actual,
            'eventos_cambio' => $eventos_cambio
        ];
    }

    /**
     * Contar usuarios activos
     */
    private function count_active_users($desde, $hasta = null) {
        global $wpdb;

        $hasta = $hasta ?: date('Y-m-d');
        $tabla_actividad = $this->prefix . 'activity_log';

        if (!$this->table_exists($tabla_actividad)) {
            return $wpdb->get_var($wpdb->prepare(
                "SELECT COUNT(DISTINCT ID) FROM {$wpdb->users}
                 WHERE user_registered >= %s",
                $desde
            ));
        }

        return (int) $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(DISTINCT usuario_id)
             FROM {$tabla_actividad}
             WHERE fecha >= %s AND fecha <= %s",
            $desde,
            $hasta
        ));
    }

    /**
     * Contar contenido creado
     */
    private function count_content_created($desde, $hasta = null) {
        global $wpdb;

        $hasta = $hasta ?: date('Y-m-d 23:59:59');
        $total = 0;

        // Posts de WordPress
        $total += (int) $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM {$wpdb->posts}
             WHERE post_status = 'publish'
             AND post_date >= %s AND post_date <= %s",
            $desde,
            $hasta
        ));

        // Publicaciones sociales
        $tabla_publicaciones = $this->prefix . 'social_publicaciones';
        if ($this->table_exists($tabla_publicaciones)) {
            $total += (int) $wpdb->get_var($wpdb->prepare(
                "SELECT COUNT(*) FROM {$tabla_publicaciones}
                 WHERE fecha_creacion >= %s AND fecha_creacion <= %s",
                $desde,
                $hasta
            ));
        }

        return $total;
    }

    /**
     * Contar interacciones
     */
    private function count_interactions($desde, $hasta = null) {
        global $wpdb;

        $hasta = $hasta ?: date('Y-m-d 23:59:59');
        $total = 0;

        // Comentarios
        $total += (int) $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM {$wpdb->comments}
             WHERE comment_date >= %s AND comment_date <= %s
             AND comment_approved = '1'",
            $desde,
            $hasta
        ));

        // Reacciones sociales
        $tabla_reacciones = $this->prefix . 'social_reacciones';
        if ($this->table_exists($tabla_reacciones)) {
            $total += (int) $wpdb->get_var($wpdb->prepare(
                "SELECT COUNT(*) FROM {$tabla_reacciones}
                 WHERE fecha_creacion >= %s AND fecha_creacion <= %s",
                $desde,
                $hasta
            ));
        }

        return $total;
    }

    /**
     * Contar eventos activos
     */
    private function count_active_events() {
        global $wpdb;

        $tabla_eventos = $this->prefix . 'eventos';
        if (!$this->table_exists($tabla_eventos)) {
            return 0;
        }

        return (int) $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM {$tabla_eventos}
             WHERE fecha_inicio >= %s AND estado = 'publicado'",
            date('Y-m-d')
        ));
    }

    /**
     * Contar eventos en período
     */
    private function count_events_in_period($desde, $hasta) {
        global $wpdb;

        $tabla_eventos = $this->prefix . 'eventos';
        if (!$this->table_exists($tabla_eventos)) {
            return 0;
        }

        return (int) $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM {$tabla_eventos}
             WHERE fecha_inicio >= %s AND fecha_inicio <= %s",
            $desde,
            $hasta
        ));
    }

    /**
     * Obtener datos de actividad por día
     */
    public function get_activity_by_day($dias = 30) {
        global $wpdb;

        $datos = [];
        $tabla_actividad = $this->prefix . 'activity_log';

        for ($i = $dias - 1; $i >= 0; $i--) {
            $fecha = date('Y-m-d', strtotime("-{$i} days"));
            $datos[$fecha] = [
                'fecha' => $fecha,
                'usuarios' => 0,
                'contenido' => 0,
                'interacciones' => 0
            ];
        }

        // Si existe la tabla de actividad
        if ($this->table_exists($tabla_actividad)) {
            $actividad = $wpdb->get_results($wpdb->prepare(
                "SELECT DATE(fecha) as dia, COUNT(DISTINCT usuario_id) as usuarios,
                        COUNT(*) as acciones
                 FROM {$tabla_actividad}
                 WHERE fecha >= %s
                 GROUP BY DATE(fecha)",
                date('Y-m-d', strtotime("-{$dias} days"))
            ));

            foreach ($actividad as $row) {
                if (isset($datos[$row->dia])) {
                    $datos[$row->dia]['usuarios'] = (int) $row->usuarios;
                    $datos[$row->dia]['interacciones'] = (int) $row->acciones;
                }
            }
        }

        return array_values($datos);
    }

    /**
     * Obtener distribución por módulo
     */
    public function get_module_distribution() {
        global $wpdb;

        $modulos = [];
        $tabla_actividad = $this->prefix . 'activity_log';

        if (!$this->table_exists($tabla_actividad)) {
            // Datos de ejemplo si no hay tabla
            return [
                ['nombre' => 'Eventos', 'valor' => 25],
                ['nombre' => 'Marketplace', 'valor' => 20],
                ['nombre' => 'Comunidades', 'valor' => 18],
                ['nombre' => 'Cursos', 'valor' => 15],
                ['nombre' => 'Otros', 'valor' => 22]
            ];
        }

        $distribucion = $wpdb->get_results(
            "SELECT modulo, COUNT(*) as total
             FROM {$tabla_actividad}
             WHERE fecha >= DATE_SUB(NOW(), INTERVAL 30 DAY)
             GROUP BY modulo
             ORDER BY total DESC
             LIMIT 5"
        );

        foreach ($distribucion as $row) {
            $modulos[] = [
                'nombre' => ucfirst(str_replace('_', ' ', $row->modulo)),
                'valor' => (int) $row->total
            ];
        }

        return $modulos;
    }

    /**
     * Renderizar top usuarios
     */
    private function render_top_usuarios() {
        if (!function_exists('flavor_reputation')) {
            return '<tr><td colspan="4">' . __('Sistema de reputación no disponible', FLAVOR_PLATFORM_TEXT_DOMAIN) . '</td></tr>';
        }

        $leaderboard = flavor_reputation()->get_leaderboard('mes', 5);

        if (empty($leaderboard)) {
            return '<tr><td colspan="4">' . __('Sin datos disponibles', FLAVOR_PLATFORM_TEXT_DOMAIN) . '</td></tr>';
        }

        $html = '';
        foreach ($leaderboard as $usuario) {
            $html .= sprintf(
                '<tr>
                    <td>
                        <img src="%s" alt="" class="avatar" width="32" height="32">
                        %s
                    </td>
                    <td><span class="badge-nivel">%s</span></td>
                    <td>%s pts</td>
                    <td>%d días</td>
                </tr>',
                esc_url($usuario->avatar_url),
                esc_html($usuario->display_name),
                esc_html($usuario->nivel_nombre),
                number_format($usuario->puntos),
                $usuario->racha_dias
            );
        }

        return $html;
    }

    /**
     * Renderizar top contenido
     */
    private function render_top_contenido() {
        global $wpdb;

        $posts = $wpdb->get_results(
            "SELECT ID, post_title, post_type, comment_count
             FROM {$wpdb->posts}
             WHERE post_status = 'publish'
             AND post_type IN ('post', 'flavor_evento', 'flavor_curso')
             ORDER BY comment_count DESC
             LIMIT 5"
        );

        if (empty($posts)) {
            return '<tr><td colspan="4">' . __('Sin datos disponibles', FLAVOR_PLATFORM_TEXT_DOMAIN) . '</td></tr>';
        }

        $html = '';
        foreach ($posts as $post) {
            $vistas = (int) get_post_meta($post->ID, 'post_views_count', true);
            $html .= sprintf(
                '<tr>
                    <td><a href="%s">%s</a></td>
                    <td>%s</td>
                    <td>%s</td>
                    <td>%s</td>
                </tr>',
                get_edit_post_link($post->ID),
                esc_html(wp_trim_words($post->post_title, 5)),
                esc_html(get_post_type_object($post->post_type)->labels->singular_name ?? $post->post_type),
                number_format($vistas),
                number_format($post->comment_count)
            );
        }

        return $html;
    }

    /**
     * Renderizar métricas por módulo
     */
    private function render_module_metrics() {
        $modulos = [
            'eventos' => ['icon' => 'calendar-alt', 'color' => '#3b82f6'],
            'cursos' => ['icon' => 'welcome-learn-more', 'color' => '#10b981'],
            'marketplace' => ['icon' => 'cart', 'color' => '#f59e0b'],
            'comunidades' => ['icon' => 'groups', 'color' => '#8b5cf6'],
            'incidencias' => ['icon' => 'warning', 'color' => '#ef4444'],
            'reservas' => ['icon' => 'calendar', 'color' => '#06b6d4']
        ];

        $html = '';
        foreach ($modulos as $modulo => $config) {
            $metricas = $this->get_module_kpis($modulo);
            $html .= sprintf(
                '<div class="module-metric-card">
                    <div class="module-icon" style="background: %s;">
                        <span class="dashicons dashicons-%s"></span>
                    </div>
                    <div class="module-info">
                        <h4>%s</h4>
                        <div class="module-stats">
                            <span>%s total</span>
                            <span>%s este mes</span>
                        </div>
                    </div>
                </div>',
                esc_attr($config['color']),
                esc_attr($config['icon']),
                esc_html(ucfirst($modulo)),
                number_format($metricas['total']),
                number_format($metricas['mes'])
            );
        }

        return $html;
    }

    /**
     * Obtener KPIs de un módulo
     */
    private function get_module_kpis($modulo) {
        global $wpdb;

        $tablas = [
            'eventos' => 'eventos',
            'cursos' => 'cursos',
            'marketplace' => 'marketplace',
            'comunidades' => 'comunidades',
            'incidencias' => 'incidencias',
            'reservas' => 'reservas'
        ];

        $tabla = $this->prefix . ($tablas[$modulo] ?? $modulo);

        if (!$this->table_exists($tabla)) {
            return ['total' => 0, 'mes' => 0];
        }

        $total = (int) $wpdb->get_var("SELECT COUNT(*) FROM {$tabla}");
        $mes = (int) $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM {$tabla} WHERE created_at >= %s",
            date('Y-m-01')
        ));

        return ['total' => $total, 'mes' => $mes];
    }

    /**
     * AJAX: Obtener datos de analytics
     */
    public function ajax_get_analytics_data() {
        check_ajax_referer('flavor_analytics', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_send_json_error(['message' => 'Sin permisos']);
        }

        $dias = isset($_POST['dias']) ? (int) $_POST['dias'] : 30;

        wp_send_json_success([
            'kpis' => $this->get_kpis($dias),
            'actividad' => $this->get_activity_by_day($dias),
            'modulos' => $this->get_module_distribution()
        ]);
    }

    /**
     * AJAX: Exportar analytics a CSV
     */
    public function ajax_export_analytics() {
        check_ajax_referer('flavor_analytics', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_send_json_error(['message' => 'Sin permisos']);
        }

        $dias = isset($_POST['dias']) ? (int) $_POST['dias'] : 30;
        $actividad = $this->get_activity_by_day($dias);

        $csv = "Fecha,Usuarios Activos,Contenido,Interacciones\n";
        foreach ($actividad as $row) {
            $csv .= sprintf(
                "%s,%d,%d,%d\n",
                $row['fecha'],
                $row['usuarios'],
                $row['contenido'],
                $row['interacciones']
            );
        }

        wp_send_json_success([
            'filename' => 'analytics-' . date('Y-m-d') . '.csv',
            'content' => $csv
        ]);
    }

    /**
     * Verificar si una tabla existe
     */
    private function table_exists($tabla) {
        global $wpdb;
        return $wpdb->get_var($wpdb->prepare("SHOW TABLES LIKE %s", $tabla)) === $tabla;
    }
}

// Inicializar solo en admin
if (is_admin()) {
    add_action('plugins_loaded', function() {
        Flavor_Analytics_Dashboard::get_instance();
    });
}

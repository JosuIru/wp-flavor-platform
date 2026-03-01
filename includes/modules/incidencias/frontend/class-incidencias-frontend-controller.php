<?php
/**
 * Frontend Controller para Incidencias
 *
 * @package FlavorChatIA
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Controlador Frontend para el módulo de Incidencias
 * Gestiona shortcodes, assets y dashboard tabs del frontend
 */
class Flavor_Incidencias_Frontend_Controller {

    /**
     * Instancia única
     */
    private static $instance = null;

    /**
     * Obtener instancia singleton
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
     * Inicializar hooks y filtros
     */
    private function init() {
        // Registrar assets
        add_action('wp_enqueue_scripts', [$this, 'registrar_assets']);

        // Registrar shortcodes
        add_action('init', [$this, 'registrar_shortcodes']);

        // AJAX handlers
        add_action('wp_ajax_incidencias_reportar', [$this, 'ajax_reportar']);
        add_action('wp_ajax_nopriv_incidencias_reportar', [$this, 'ajax_reportar']);
        add_action('wp_ajax_incidencias_comentar', [$this, 'ajax_comentar']);
        add_action('wp_ajax_incidencias_votar', [$this, 'ajax_votar']);
        add_action('wp_ajax_incidencias_filtrar', [$this, 'ajax_filtrar']);
        add_action('wp_ajax_nopriv_incidencias_filtrar', [$this, 'ajax_filtrar']);

        // Dashboard tabs
        add_filter('flavor_user_dashboard_tabs', [$this, 'registrar_tabs'], 10, 1);

        // Template overrides
        add_filter('template_include', [$this, 'cargar_template']);
    }

    /**
     * Registrar assets CSS y JS
     */
    public function registrar_assets() {
        $base_url = plugins_url('', dirname(__FILE__));
        $version = FLAVOR_CHAT_IA_VERSION ?? '1.0.0';

        // CSS
        wp_register_style(
            'flavor-incidencias',
            $base_url . '/assets/css/incidencias-frontend.css',
            [],
            $version
        );

        // JS
        wp_register_script(
            'flavor-incidencias',
            $base_url . '/assets/js/incidencias-frontend.js',
            ['jquery'],
            $version,
            true
        );

        // Localizar script
        wp_localize_script('flavor-incidencias', 'flavorIncidencias', [
            'ajaxUrl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('incidencias_nonce'),
            'i18n' => [
                'reportada' => __('Incidencia reportada correctamente', 'flavor-chat-ia'),
                'error' => __('Ha ocurrido un error', 'flavor-chat-ia'),
                'confirmacion' => __('¿Estás seguro?', 'flavor-chat-ia'),
                'cargando' => __('Cargando...', 'flavor-chat-ia'),
                'gracias_voto' => __('Gracias por tu voto', 'flavor-chat-ia'),
                'comentario_enviado' => __('Comentario enviado', 'flavor-chat-ia'),
            ],
        ]);
    }

    /**
     * Encolar assets cuando sea necesario
     */
    public function encolar_assets() {
        wp_enqueue_style('flavor-incidencias');
        wp_enqueue_script('flavor-incidencias');
    }

    /**
     * Registrar shortcodes del módulo
     */
    public function registrar_shortcodes() {
        add_shortcode('incidencias_listado', [$this, 'shortcode_listado']);
        add_shortcode('incidencias_mapa', [$this, 'shortcode_mapa']);
        add_shortcode('incidencias_reportar', [$this, 'shortcode_reportar']);
        add_shortcode('incidencias_mis_reportes', [$this, 'shortcode_mis_reportes']);
        add_shortcode('incidencias_detalle', [$this, 'shortcode_detalle']);
        add_shortcode('incidencias_estadisticas', [$this, 'shortcode_estadisticas']);
        add_shortcode('incidencias_recientes', [$this, 'shortcode_recientes']);
    }

    /**
     * Shortcode: Listado de incidencias
     */
    public function shortcode_listado($atts) {
        $this->encolar_assets();

        $atts = shortcode_atts([
            'categoria' => '',
            'estado' => '',
            'limite' => 20,
            'mostrar_filtros' => 'true',
        ], $atts);

        ob_start();
        $this->render_listado($atts);
        return ob_get_clean();
    }

    /**
     * Shortcode: Mapa de incidencias
     */
    public function shortcode_mapa($atts) {
        $this->encolar_assets();
        wp_enqueue_script('leaflet');
        wp_enqueue_style('leaflet');

        $atts = shortcode_atts([
            'altura' => '500px',
            'centro_lat' => '',
            'centro_lng' => '',
            'zoom' => 13,
        ], $atts);

        ob_start();
        $this->render_mapa($atts);
        return ob_get_clean();
    }

    /**
     * Shortcode: Formulario de reporte
     */
    public function shortcode_reportar($atts) {
        $this->encolar_assets();

        ob_start();
        $this->render_formulario_reportar();
        return ob_get_clean();
    }

    /**
     * Shortcode: Mis reportes
     */
    public function shortcode_mis_reportes($atts) {
        if (!is_user_logged_in()) {
            return '<p class="flavor-login-required">' . __('Inicia sesión para ver tus reportes.', 'flavor-chat-ia') . '</p>';
        }

        $this->encolar_assets();

        $atts = shortcode_atts([
            'estado' => '',
            'limite' => 20,
        ], $atts);

        ob_start();
        $this->render_mis_reportes($atts);
        return ob_get_clean();
    }

    /**
     * Shortcode: Detalle de incidencia
     */
    public function shortcode_detalle($atts) {
        $this->encolar_assets();

        $atts = shortcode_atts([
            'id' => 0,
        ], $atts);

        $incidencia_id = $atts['id'] ?: (isset($_GET['incidencia_id']) ? absint($_GET['incidencia_id']) : 0);

        if (!$incidencia_id) {
            return '<p class="flavor-error">' . __('Incidencia no especificada.', 'flavor-chat-ia') . '</p>';
        }

        ob_start();
        $this->render_detalle($incidencia_id);
        return ob_get_clean();
    }

    /**
     * Shortcode: Estadísticas
     */
    public function shortcode_estadisticas($atts) {
        $this->encolar_assets();

        ob_start();
        $this->render_estadisticas();
        return ob_get_clean();
    }

    /**
     * Shortcode: Incidencias recientes (widget compacto)
     *
     * Muestra las últimas incidencias en formato compacto para widgets/sidebars.
     *
     * @param array $atts Atributos del shortcode
     * @return string HTML del widget
     */
    public function shortcode_recientes($atts) {
        global $wpdb;

        $atts = shortcode_atts([
            'limite' => 4,
            'estado' => '',
            'mostrar_fecha' => 'true',
            'mostrar_estado' => 'true',
        ], $atts);

        $tabla_incidencias = $wpdb->prefix . 'flavor_incidencias';

        if (!Flavor_Chat_Helpers::tabla_existe($tabla_incidencias)) {
            return '';
        }

        // Query para obtener incidencias recientes
        $where = ["estado != 'eliminada'"];
        $params = [];

        if (!empty($atts['estado'])) {
            $where[] = "estado = %s";
            $params[] = $atts['estado'];
        }

        $where_sql = implode(' AND ', $where);
        $limite = absint($atts['limite']);

        $query = "SELECT id, titulo, descripcion, estado, ubicacion, created_at
                  FROM $tabla_incidencias
                  WHERE $where_sql
                  ORDER BY created_at DESC
                  LIMIT %d";
        $params[] = $limite;

        $incidencias = $wpdb->get_results($wpdb->prepare($query, $params));

        if (empty($incidencias)) {
            return '<div class="flavor-widget-empty">
                <span class="text-4xl">✅</span>
                <p class="text-gray-500 text-sm mt-2">' . __('No hay incidencias recientes', 'flavor-chat-ia') . '</p>
            </div>';
        }

        // Estados con colores
        $estados_config = [
            'pendiente' => ['label' => __('Pendiente', 'flavor-chat-ia'), 'color' => 'red', 'icon' => '🔴'],
            'en_proceso' => ['label' => __('En proceso', 'flavor-chat-ia'), 'color' => 'yellow', 'icon' => '🟡'],
            'validada' => ['label' => __('Validada', 'flavor-chat-ia'), 'color' => 'blue', 'icon' => '🔵'],
            'resuelta' => ['label' => __('Resuelta', 'flavor-chat-ia'), 'color' => 'green', 'icon' => '🟢'],
        ];

        ob_start();
        ?>
        <div class="flavor-incidencias-recientes space-y-3">
            <?php foreach ($incidencias as $incidencia):
                $estado_info = $estados_config[$incidencia->estado] ?? $estados_config['pendiente'];
                $url = home_url("/mi-portal/incidencias/{$incidencia->id}/");
            ?>
                <a href="<?php echo esc_url($url); ?>"
                   class="block p-3 bg-white rounded-lg border border-gray-100 hover:border-<?php echo esc_attr($estado_info['color']); ?>-300 hover:shadow-sm transition-all group">
                    <div class="flex items-start gap-3">
                        <span class="text-lg flex-shrink-0"><?php echo esc_html($estado_info['icon']); ?></span>
                        <div class="flex-1 min-w-0">
                            <h4 class="font-medium text-gray-900 text-sm truncate group-hover:text-<?php echo esc_attr($estado_info['color']); ?>-600">
                                <?php echo esc_html($incidencia->titulo); ?>
                            </h4>
                            <?php if ($atts['mostrar_fecha'] === 'true'): ?>
                                <p class="text-xs text-gray-500 mt-0.5">
                                    <?php echo esc_html(human_time_diff(strtotime($incidencia->created_at), current_time('timestamp'))); ?>
                                    <?php _e('atrás', 'flavor-chat-ia'); ?>
                                </p>
                            <?php endif; ?>
                        </div>
                        <?php if ($atts['mostrar_estado'] === 'true'): ?>
                            <span class="px-2 py-0.5 text-xs rounded-full bg-<?php echo esc_attr($estado_info['color']); ?>-100 text-<?php echo esc_attr($estado_info['color']); ?>-700 flex-shrink-0">
                                <?php echo esc_html($estado_info['label']); ?>
                            </span>
                        <?php endif; ?>
                    </div>
                </a>
            <?php endforeach; ?>
        </div>
        <?php
        return ob_get_clean();
    }

    /**
     * Renderizar listado de incidencias
     *
     * Usa el sistema de plantillas dinámicas (Archive Renderer)
     */
    private function render_listado($atts) {
        global $wpdb;
        $tabla_incidencias = $wpdb->prefix . 'flavor_incidencias';

        if (!Flavor_Chat_Helpers::tabla_existe($tabla_incidencias)) {
            echo '<p class="flavor-error">' . __('El módulo de incidencias no está configurado.', 'flavor-chat-ia') . '</p>';
            return;
        }

        // Parámetros de paginación y filtros
        $per_page = intval($atts['limite'] ?? 20);
        $current_page = max(1, intval($_GET['pag'] ?? 1));
        $offset = ($current_page - 1) * $per_page;

        // Filtros desde URL
        $estado_filtro = isset($_GET['estado']) ? sanitize_text_field($_GET['estado']) : '';
        $tipo_filtro = isset($_GET['tipo']) ? sanitize_text_field($_GET['tipo']) : '';

        // Construir query
        $where = ["estado != 'eliminada'"];
        $params = [];

        if (!empty($atts['categoria'])) {
            $where[] = "categoria = %s";
            $params[] = $atts['categoria'];
        }

        if (!empty($atts['estado'])) {
            $where[] = "estado = %s";
            $params[] = $atts['estado'];
        } elseif ($estado_filtro) {
            $where[] = "estado = %s";
            $params[] = $estado_filtro;
        }

        if ($tipo_filtro) {
            $where[] = "tipo = %s";
            $params[] = $tipo_filtro;
        }

        $where_sql = implode(' AND ', $where);

        // Obtener total para paginación
        $count_query = "SELECT COUNT(*) FROM $tabla_incidencias WHERE $where_sql";
        if (!empty($params)) {
            $total_incidencias = (int) $wpdb->get_var($wpdb->prepare($count_query, $params));
        } else {
            $total_incidencias = (int) $wpdb->get_var($count_query);
        }

        // Obtener incidencias paginadas
        $query = "SELECT * FROM $tabla_incidencias WHERE $where_sql ORDER BY created_at DESC LIMIT %d OFFSET %d";
        $query_params = array_merge($params, [$per_page, $offset]);
        $incidencias_raw = $wpdb->get_results($wpdb->prepare($query, $query_params));

        // Transformar datos al formato que espera el Archive Renderer
        // Los campos deben coincidir con card_config.fields en get_module_config()
        $incidencias = [];
        foreach ($incidencias_raw as $inc) {
            $incidencias[] = [
                'id'          => $inc->id,
                'titulo'      => $inc->titulo,
                'descripcion' => wp_trim_words($inc->descripcion ?? '', 25),
                'imagen'      => $inc->imagen ?? '',
                'url'         => home_url('/mi-portal/incidencias/' . $inc->id . '/'),
                'fecha'       => date_i18n(get_option('date_format'), strtotime($inc->created_at)),
                'estado'      => $inc->estado,
                'tipo'        => $inc->tipo ?? '',
                'ubicacion'   => $inc->ubicacion ?? '',
                'categoria'   => $inc->categoria ?? '',
            ];
        }

        // Calcular estadísticas
        $estadisticas = $this->calcular_estadisticas_listado($tabla_incidencias);

        // Cargar el template estándar con Archive Renderer
        $template = FLAVOR_CHAT_IA_PATH . 'templates/frontend/incidencias/archive.php';
        if (file_exists($template)) {
            include $template;
        }
    }

    /**
     * Calcula estadísticas para el listado
     */
    private function calcular_estadisticas_listado($tabla) {
        global $wpdb;

        return [
            'pendientes'   => (int) $wpdb->get_var("SELECT COUNT(*) FROM $tabla WHERE estado = 'pendiente'"),
            'en_proceso'   => (int) $wpdb->get_var("SELECT COUNT(*) FROM $tabla WHERE estado IN ('en_proceso', 'validada')"),
            'resueltas'    => (int) $wpdb->get_var("SELECT COUNT(*) FROM $tabla WHERE estado = 'resuelta'"),
            'tiempo_medio' => $this->calcular_tiempo_medio_resolucion($tabla),
        ];
    }

    /**
     * Calcula el tiempo medio de resolución
     */
    private function calcular_tiempo_medio_resolucion($tabla) {
        global $wpdb;

        $tiempo = $wpdb->get_var(
            "SELECT AVG(DATEDIFF(fecha_resolucion, created_at))
             FROM $tabla
             WHERE estado = 'resuelta'
             AND fecha_resolucion IS NOT NULL
             AND created_at IS NOT NULL"
        );

        if ($tiempo === null) {
            return '—';
        }

        return round($tiempo, 1) . ' ' . __('días', 'flavor-chat-ia');
    }

    /**
     * Obtiene el label de un estado
     */
    private function get_estado_label($estado) {
        $labels = [
            'pendiente'   => __('Pendiente', 'flavor-chat-ia'),
            'validada'    => __('Validada', 'flavor-chat-ia'),
            'en_proceso'  => __('En proceso', 'flavor-chat-ia'),
            'resuelta'    => __('Resuelta', 'flavor-chat-ia'),
            'cerrada'     => __('Cerrada', 'flavor-chat-ia'),
            'rechazada'   => __('Rechazada', 'flavor-chat-ia'),
        ];
        return $labels[$estado] ?? ucfirst($estado);
    }

    /**
     * Obtiene el color de un estado
     */
    private function get_estado_color($estado) {
        $colors = [
            'pendiente'   => 'yellow',
            'validada'    => 'blue',
            'en_proceso'  => 'blue',
            'resuelta'    => 'green',
            'cerrada'     => 'gray',
            'rechazada'   => 'red',
        ];
        return $colors[$estado] ?? 'gray';
    }

    /**
     * Renderizar mapa de incidencias
     */
    private function render_mapa($atts) {
        $template = dirname(__FILE__) . '/../templates/mapa.php';
        if (file_exists($template)) {
            include $template;
        }
    }

    /**
     * Renderizar formulario de reporte
     */
    private function render_formulario_reportar() {
        $template = dirname(__FILE__) . '/../templates/formulario-reportar.php';
        if (file_exists($template)) {
            include $template;
        } else {
            echo '<div class="flavor-incidencias-form">';
            echo '<h3>' . __('Reportar Incidencia', 'flavor-chat-ia') . '</h3>';
            echo '<form id="form-reportar-incidencia" class="flavor-form">';
            wp_nonce_field('incidencias_nonce', 'incidencias_nonce_field');
            echo '<p><label>' . __('Tipo de incidencia', 'flavor-chat-ia') . '</label>';
            echo '<select name="categoria" required>';
            echo '<option value="">' . __('Selecciona...', 'flavor-chat-ia') . '</option>';
            echo '<option value="alumbrado">' . __('Alumbrado', 'flavor-chat-ia') . '</option>';
            echo '<option value="baches">' . __('Baches', 'flavor-chat-ia') . '</option>';
            echo '<option value="limpieza">' . __('Limpieza', 'flavor-chat-ia') . '</option>';
            echo '<option value="mobiliario">' . __('Mobiliario urbano', 'flavor-chat-ia') . '</option>';
            echo '<option value="otros">' . __('Otros', 'flavor-chat-ia') . '</option>';
            echo '</select></p>';
            echo '<p><label>' . __('Título', 'flavor-chat-ia') . '</label>';
            echo '<input type="text" name="titulo" required></p>';
            echo '<p><label>' . __('Descripción', 'flavor-chat-ia') . '</label>';
            echo '<textarea name="descripcion" rows="4" required></textarea></p>';
            echo '<p><label>' . __('Ubicación', 'flavor-chat-ia') . '</label>';
            echo '<input type="text" name="direccion" placeholder="' . __('Dirección aproximada', 'flavor-chat-ia') . '"></p>';
            echo '<p><button type="submit" class="flavor-btn flavor-btn-primary">' . __('Enviar Reporte', 'flavor-chat-ia') . '</button></p>';
            echo '</form>';
            echo '</div>';
        }
    }

    /**
     * Renderizar mis reportes
     */
    private function render_mis_reportes($atts) {
        global $wpdb;
        $tabla_incidencias = $wpdb->prefix . 'flavor_incidencias';
        $usuario_id = get_current_user_id();

        $where = ["usuario_id = %d"];
        $params = [$usuario_id];

        if (!empty($atts['estado'])) {
            $where[] = "estado = %s";
            $params[] = $atts['estado'];
        }

        $sql = "SELECT * FROM $tabla_incidencias WHERE " . implode(' AND ', $where) . " ORDER BY fecha_reporte DESC LIMIT %d";
        $params[] = intval($atts['limite']);

        $incidencias = $wpdb->get_results($wpdb->prepare($sql, ...$params));

        $template = dirname(__FILE__) . '/../templates/mis-incidencias.php';
        if (file_exists($template)) {
            include $template;
        }
    }

    /**
     * Renderizar detalle de incidencia
     */
    private function render_detalle($incidencia_id) {
        global $wpdb;
        $tabla_incidencias = $wpdb->prefix . 'flavor_incidencias';

        $incidencia = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM $tabla_incidencias WHERE id = %d",
            $incidencia_id
        ));

        if (!$incidencia) {
            echo '<p class="flavor-error">' . __('Incidencia no encontrada.', 'flavor-chat-ia') . '</p>';
            return;
        }

        $template = dirname(__FILE__) . '/single.php';
        if (file_exists($template)) {
            include $template;
        }
    }

    /**
     * Renderizar estadísticas
     */
    private function render_estadisticas() {
        global $wpdb;
        $tabla_incidencias = $wpdb->prefix . 'flavor_incidencias';

        $total = $wpdb->get_var("SELECT COUNT(*) FROM $tabla_incidencias");
        $resueltas = $wpdb->get_var("SELECT COUNT(*) FROM $tabla_incidencias WHERE estado = 'resolved'");
        $pendientes = $wpdb->get_var("SELECT COUNT(*) FROM $tabla_incidencias WHERE estado = 'pending'");
        $en_progreso = $wpdb->get_var("SELECT COUNT(*) FROM $tabla_incidencias WHERE estado = 'in_progress'");

        ?>
        <div class="flavor-incidencias-stats">
            <div class="stat-card">
                <span class="stat-number"><?php echo esc_html($total); ?></span>
                <span class="stat-label"><?php _e('Total Incidencias', 'flavor-chat-ia'); ?></span>
            </div>
            <div class="stat-card stat-success">
                <span class="stat-number"><?php echo esc_html($resueltas); ?></span>
                <span class="stat-label"><?php _e('Resueltas', 'flavor-chat-ia'); ?></span>
            </div>
            <div class="stat-card stat-warning">
                <span class="stat-number"><?php echo esc_html($en_progreso); ?></span>
                <span class="stat-label"><?php _e('En Progreso', 'flavor-chat-ia'); ?></span>
            </div>
            <div class="stat-card stat-pending">
                <span class="stat-number"><?php echo esc_html($pendientes); ?></span>
                <span class="stat-label"><?php _e('Pendientes', 'flavor-chat-ia'); ?></span>
            </div>
        </div>
        <?php
    }

    /**
     * Registrar tabs en el dashboard de usuario
     */
    public function registrar_tabs($tabs) {
        $tabs['incidencias-mis-reportes'] = [
            'titulo' => __('Mis Incidencias', 'flavor-chat-ia'),
            'icono' => 'dashicons-warning',
            'callback' => [$this, 'render_tab_mis_reportes'],
            'orden' => 40,
            'modulo' => 'incidencias',
        ];

        return $tabs;
    }

    /**
     * Renderizar tab de mis reportes
     */
    public function render_tab_mis_reportes() {
        $this->encolar_assets();
        $this->render_mis_reportes(['estado' => '', 'limite' => 20]);
    }

    /**
     * Cargar templates personalizados
     */
    public function cargar_template($template) {
        return $template;
    }

    /**
     * AJAX: Reportar incidencia
     */
    public function ajax_reportar() {
        check_ajax_referer('incidencias_nonce', 'nonce');

        $titulo = isset($_POST['titulo']) ? sanitize_text_field($_POST['titulo']) : '';
        $descripcion = isset($_POST['descripcion']) ? sanitize_textarea_field($_POST['descripcion']) : '';
        $categoria = isset($_POST['categoria']) ? sanitize_text_field($_POST['categoria']) : '';
        $direccion = isset($_POST['direccion']) ? sanitize_text_field($_POST['direccion']) : '';
        $latitud = isset($_POST['latitud']) ? floatval($_POST['latitud']) : null;
        $longitud = isset($_POST['longitud']) ? floatval($_POST['longitud']) : null;

        if (empty($titulo) || empty($descripcion) || empty($categoria)) {
            wp_send_json_error(__('Todos los campos son obligatorios', 'flavor-chat-ia'));
        }

        global $wpdb;
        $tabla_incidencias = $wpdb->prefix . 'flavor_incidencias';

        $resultado = $wpdb->insert($tabla_incidencias, [
            'usuario_id' => get_current_user_id(),
            'titulo' => $titulo,
            'descripcion' => $descripcion,
            'categoria' => $categoria,
            'direccion' => $direccion,
            'latitud' => $latitud,
            'longitud' => $longitud,
            'estado' => 'pending',
            'fecha_reporte' => current_time('mysql'),
        ]);

        if ($resultado) {
            $incidencia_id = $wpdb->insert_id;

            // Disparar acción para notificaciones
            do_action('incidencia_created', $incidencia_id, get_current_user_id());

            wp_send_json_success([
                'mensaje' => __('Incidencia reportada correctamente', 'flavor-chat-ia'),
                'incidencia_id' => $incidencia_id,
            ]);
        } else {
            wp_send_json_error(__('Error al reportar la incidencia', 'flavor-chat-ia'));
        }
    }

    /**
     * AJAX: Comentar incidencia
     */
    public function ajax_comentar() {
        check_ajax_referer('incidencias_nonce', 'nonce');

        if (!is_user_logged_in()) {
            wp_send_json_error(__('Debes iniciar sesión', 'flavor-chat-ia'));
        }

        $incidencia_id = isset($_POST['incidencia_id']) ? absint($_POST['incidencia_id']) : 0;
        $comentario = isset($_POST['comentario']) ? sanitize_textarea_field($_POST['comentario']) : '';
        $usuario_id = get_current_user_id();

        if (!$incidencia_id || empty($comentario)) {
            wp_send_json_error(__('Datos no válidos', 'flavor-chat-ia'));
        }

        global $wpdb;
        $tabla_comentarios = $wpdb->prefix . 'flavor_incidencias_comentarios';

        $resultado = $wpdb->insert($tabla_comentarios, [
            'incidencia_id' => $incidencia_id,
            'usuario_id' => $usuario_id,
            'comentario' => $comentario,
            'fecha' => current_time('mysql'),
        ]);

        if ($resultado) {
            // Notificar al autor de la incidencia
            $tabla_incidencias = $wpdb->prefix . 'flavor_incidencias';
            $incidencia = $wpdb->get_row($wpdb->prepare(
                "SELECT usuario_id FROM $tabla_incidencias WHERE id = %d",
                $incidencia_id
            ));

            if ($incidencia && $incidencia->usuario_id != $usuario_id) {
                $usuario = get_userdata($usuario_id);
                do_action('incidencia_comment_added', $incidencia_id, $incidencia->usuario_id, $usuario->display_name);
            }

            wp_send_json_success(['mensaje' => __('Comentario enviado', 'flavor-chat-ia')]);
        } else {
            wp_send_json_error(__('Error al enviar comentario', 'flavor-chat-ia'));
        }
    }

    /**
     * AJAX: Votar incidencia
     */
    public function ajax_votar() {
        check_ajax_referer('incidencias_nonce', 'nonce');

        if (!is_user_logged_in()) {
            wp_send_json_error(__('Debes iniciar sesión', 'flavor-chat-ia'));
        }

        $incidencia_id = isset($_POST['incidencia_id']) ? absint($_POST['incidencia_id']) : 0;
        $usuario_id = get_current_user_id();

        if (!$incidencia_id) {
            wp_send_json_error(__('Incidencia no válida', 'flavor-chat-ia'));
        }

        global $wpdb;
        $tabla_votos = $wpdb->prefix . 'flavor_incidencias_votos';

        // Verificar si ya votó
        $existe = $wpdb->get_var($wpdb->prepare(
            "SELECT id FROM $tabla_votos WHERE incidencia_id = %d AND usuario_id = %d",
            $incidencia_id,
            $usuario_id
        ));

        if ($existe) {
            wp_send_json_error(__('Ya has votado esta incidencia', 'flavor-chat-ia'));
        }

        $resultado = $wpdb->insert($tabla_votos, [
            'incidencia_id' => $incidencia_id,
            'usuario_id' => $usuario_id,
            'fecha' => current_time('mysql'),
        ]);

        if ($resultado) {
            // Obtener total de votos
            $total_votos = $wpdb->get_var($wpdb->prepare(
                "SELECT COUNT(*) FROM $tabla_votos WHERE incidencia_id = %d",
                $incidencia_id
            ));

            wp_send_json_success([
                'mensaje' => __('Gracias por tu voto', 'flavor-chat-ia'),
                'total_votos' => $total_votos,
            ]);
        } else {
            wp_send_json_error(__('Error al registrar voto', 'flavor-chat-ia'));
        }
    }

    /**
     * AJAX: Filtrar incidencias
     */
    public function ajax_filtrar() {
        check_ajax_referer('incidencias_nonce', 'nonce');

        $categoria = isset($_POST['categoria']) ? sanitize_text_field($_POST['categoria']) : '';
        $estado = isset($_POST['estado']) ? sanitize_text_field($_POST['estado']) : '';
        $busqueda = isset($_POST['busqueda']) ? sanitize_text_field($_POST['busqueda']) : '';

        global $wpdb;
        $tabla_incidencias = $wpdb->prefix . 'flavor_incidencias';

        $where = ["1=1"];
        $params = [];

        if (!empty($categoria)) {
            $where[] = "categoria = %s";
            $params[] = $categoria;
        }

        if (!empty($estado)) {
            $where[] = "estado = %s";
            $params[] = $estado;
        }

        if (!empty($busqueda)) {
            $where[] = "(titulo LIKE %s OR descripcion LIKE %s)";
            $like = '%' . $wpdb->esc_like($busqueda) . '%';
            $params[] = $like;
            $params[] = $like;
        }

        $sql = "SELECT * FROM $tabla_incidencias WHERE " . implode(' AND ', $where) . " ORDER BY fecha_reporte DESC LIMIT 50";

        if (!empty($params)) {
            $incidencias = $wpdb->get_results($wpdb->prepare($sql, ...$params));
        } else {
            $incidencias = $wpdb->get_results($sql);
        }

        ob_start();
        if (!empty($incidencias)) {
            foreach ($incidencias as $incidencia) {
                $this->render_incidencia_card($incidencia);
            }
        } else {
            echo '<p class="no-resultados">' . __('No se encontraron incidencias', 'flavor-chat-ia') . '</p>';
        }
        $html = ob_get_clean();

        wp_send_json_success(['html' => $html, 'total' => count($incidencias)]);
    }

    /**
     * Renderizar tarjeta de incidencia
     */
    private function render_incidencia_card($incidencia) {
        $estado_clases = [
            // Estados en español
            'pendiente' => 'estado-pendiente',
            'en_proceso' => 'estado-progreso',
            'resuelta' => 'estado-resuelta',
            'cerrada' => 'estado-cerrada',
            // Estados en inglés (para datos existentes)
            'pending' => 'estado-pendiente',
            'in_progress' => 'estado-progreso',
            'resolved' => 'estado-resuelta',
            'closed' => 'estado-cerrada',
        ];

        $estados_labels = [
            // Estados en español
            'pendiente' => __('Pendiente', 'flavor-chat-ia'),
            'en_proceso' => __('En proceso', 'flavor-chat-ia'),
            'resuelta' => __('Resuelta', 'flavor-chat-ia'),
            'cerrada' => __('Cerrada', 'flavor-chat-ia'),
            // Estados en inglés (para datos existentes)
            'pending' => __('Pendiente', 'flavor-chat-ia'),
            'in_progress' => __('En proceso', 'flavor-chat-ia'),
            'resolved' => __('Resuelta', 'flavor-chat-ia'),
            'closed' => __('Cerrada', 'flavor-chat-ia'),
        ];

        $clase_estado = $estado_clases[$incidencia->estado] ?? '';
        $estado_label = $estados_labels[$incidencia->estado] ?? ucfirst($incidencia->estado);
        ?>
        <div class="flavor-incidencia-card <?php echo esc_attr($clase_estado); ?>" data-id="<?php echo esc_attr($incidencia->id); ?>">
            <div class="incidencia-header">
                <span class="categoria"><?php echo esc_html($incidencia->categoria); ?></span>
                <span class="estado"><?php echo esc_html($estado_label); ?></span>
            </div>
            <h3><?php echo esc_html($incidencia->titulo); ?></h3>
            <p class="incidencia-descripcion"><?php echo esc_html(wp_trim_words($incidencia->descripcion, 20)); ?></p>
            <div class="incidencia-footer">
                <span class="fecha"><?php echo esc_html(date_i18n('d/m/Y', strtotime($incidencia->fecha_reporte))); ?></span>
                <a href="<?php echo esc_url(home_url('/mi-portal/incidencias/' . $incidencia->id . '/')); ?>" class="flavor-btn flavor-btn-sm">
                    <?php _e('Ver Detalles', 'flavor-chat-ia'); ?>
                </a>
            </div>
        </div>
        <?php
    }
}

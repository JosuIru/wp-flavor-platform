<?php
/**
 * Módulo Justicia Restaurativa
 *
 * Sistema de resolución de conflictos comunitaria,
 * enfocado en reparación, diálogo y reconciliación.
 *
 * Valoración de Conciencia: 92/100
 * - conciencia_fundamental: 0.30 (Conflicto como oportunidad de crecimiento)
 * - abundancia_organizable: 0.10 (Recursos comunitarios de mediación)
 * - interdependencia_radical: 0.25 (Comunidad como garante)
 * - madurez_ciclica: 0.20 (Proceso de sanación tiene su tiempo)
 * - valor_intrinseco: 0.15 (Dignidad de todas las partes)
 *
 * @package FlavorChatIA
 */

if (!defined('ABSPATH')) {
    exit;
}

class Flavor_Chat_Justicia_Restaurativa_Module extends Flavor_Chat_Module_Base {

    use Flavor_Module_Admin_Pages_Trait;

    /**
     * Tipos de procesos restaurativos
     */
    const TIPOS_PROCESO = [
        'mediacion' => [
            'nombre' => 'Mediación',
            'icono' => 'dashicons-groups',
            'color' => '#3498db',
            'descripcion' => 'Diálogo facilitado entre las partes',
            'duracion_estimada' => '1-3 sesiones',
        ],
        'circulo_dialogo' => [
            'nombre' => 'Círculo de diálogo',
            'icono' => 'dashicons-feedback',
            'color' => '#9b59b6',
            'descripcion' => 'Conversación comunitaria ampliada',
            'duracion_estimada' => '1-2 sesiones',
        ],
        'conferencia' => [
            'nombre' => 'Conferencia restaurativa',
            'icono' => 'dashicons-businessman',
            'color' => '#27ae60',
            'descripcion' => 'Reunión con todas las partes afectadas',
            'duracion_estimada' => '2-4 sesiones',
        ],
        'circulo_paz' => [
            'nombre' => 'Círculo de paz',
            'icono' => 'dashicons-heart',
            'color' => '#e74c3c',
            'descripcion' => 'Proceso comunitario de sanación',
            'duracion_estimada' => '3-6 sesiones',
        ],
    ];

    /**
     * Estados del proceso
     */
    const ESTADOS_PROCESO = [
        'solicitado' => ['nombre' => 'Solicitado', 'color' => '#f39c12'],
        'aceptado' => ['nombre' => 'Aceptado', 'color' => '#3498db'],
        'en_curso' => ['nombre' => 'En curso', 'color' => '#9b59b6'],
        'acuerdo' => ['nombre' => 'Acuerdo alcanzado', 'color' => '#27ae60'],
        'cumplido' => ['nombre' => 'Acuerdo cumplido', 'color' => '#2ecc71'],
        'no_acuerdo' => ['nombre' => 'Sin acuerdo', 'color' => '#95a5a6'],
        'cancelado' => ['nombre' => 'Cancelado', 'color' => '#e74c3c'],
    ];

    /**
     * Constructor
     */
    public function __construct() {
        $this->id = 'justicia_restaurativa';
        $this->name = __('Justicia Restaurativa', 'flavor-chat-ia');
        $this->description = __('Resolución de conflictos comunitaria basada en reparación y diálogo.', 'flavor-chat-ia');
        $this->icon = 'dashicons-shield';
        $this->color = '#9b59b6';

        parent::__construct();
    }

    /**
     * {@inheritdoc}
     */
    public function can_activate() {
        return true;
    }

    /**
     * {@inheritdoc}
     */
    protected function get_default_settings() {
        return [
            'tipos_habilitados' => array_keys(self::TIPOS_PROCESO),
            'confidencialidad_estricta' => true,
            'permitir_mediadores_voluntarios' => true,
            'notificar_partes' => true,
            'dias_respuesta_maximos' => 7,
            'mostrar_en_dashboard' => true,
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function init() {
        // Custom Post Types
        add_action('init', [$this, 'registrar_post_types']);

        // Meta boxes
        add_action('add_meta_boxes', [$this, 'registrar_meta_boxes']);
        add_action('save_post_jr_proceso', [$this, 'guardar_meta_proceso']);

        // Shortcodes
        $this->register_shortcodes();

        // AJAX
        add_action('wp_ajax_jr_solicitar_proceso', [$this, 'ajax_solicitar_proceso']);
        add_action('wp_ajax_jr_responder_solicitud', [$this, 'ajax_responder_solicitud']);
        add_action('wp_ajax_jr_registrar_sesion', [$this, 'ajax_registrar_sesion']);
        add_action('wp_ajax_jr_registrar_acuerdo', [$this, 'ajax_registrar_acuerdo']);
        add_action('wp_ajax_jr_ser_mediador', [$this, 'ajax_ser_mediador']);

        // Dashboard widget
        add_action('flavor_register_dashboard_widgets', [$this, 'register_dashboard_widget']);

        // Assets
        add_action('wp_enqueue_scripts', [$this, 'enqueue_assets']);

        // Restricción de acceso (confidencialidad)
        add_action('template_redirect', [$this, 'verificar_acceso_proceso']);

        // REST API
        add_action('rest_api_init', [$this, 'register_rest_routes']);

        // Panel Unificado Admin
        $this->registrar_en_panel_unificado();
        // Cargar Dashboard Tab
        $this->inicializar_dashboard_tab();

        // Dashboard tabs para usuarios (frontend)
        $this->init_dashboard_tabs();
    }

    /**
     * Inicializa los tabs del dashboard de usuario
     */
    private function init_dashboard_tabs() {
        $tab_file = dirname(__FILE__) . '/class-justicia-restaurativa-dashboard-tab.php';
        if (file_exists($tab_file)) {
            require_once $tab_file;
            if (class_exists('Flavor_Justicia_Restaurativa_Dashboard_Tab')) {
                Flavor_Justicia_Restaurativa_Dashboard_Tab::get_instance();
            }
        }
    }

    /**
     * Registra rutas REST API
     */
    public function register_rest_routes(): void {
        $namespace = 'flavor/v1';

        // Tipos de proceso
        register_rest_route($namespace, '/justicia-restaurativa/tipos', [
            'methods' => 'GET',
            'callback' => [$this, 'api_get_tipos'],
            'permission_callback' => '__return_true',
        ]);

        // Mis procesos
        register_rest_route($namespace, '/justicia-restaurativa/mis-procesos', [
            'methods' => 'GET',
            'callback' => [$this, 'api_get_mis_procesos'],
            'permission_callback' => [$this, 'check_user_logged_in'],
        ]);

        // Obtener proceso
        register_rest_route($namespace, '/justicia-restaurativa/proceso/(?P<id>\d+)', [
            'methods' => 'GET',
            'callback' => [$this, 'api_get_proceso'],
            'permission_callback' => [$this, 'check_user_logged_in'],
        ]);

        // Estadísticas
        register_rest_route($namespace, '/justicia-restaurativa/estadisticas', [
            'methods' => 'GET',
            'callback' => [$this, 'api_get_estadisticas'],
            'permission_callback' => [$this, 'check_user_logged_in'],
        ]);

        // Mediadores
        register_rest_route($namespace, '/justicia-restaurativa/mediadores', [
            'methods' => 'GET',
            'callback' => [$this, 'api_get_mediadores'],
            'permission_callback' => '__return_true',
        ]);
    }

    /**
     * Verifica si el usuario está logueado
     */
    public function check_user_logged_in(): bool {
        return is_user_logged_in();
    }

    /**
     * API: Obtener tipos de proceso
     */
    public function api_get_tipos(\WP_REST_Request $request): \WP_REST_Response {
        return new \WP_REST_Response(['tipos' => self::TIPOS_PROCESO]);
    }

    /**
     * API: Obtener mis procesos
     */
    public function api_get_mis_procesos(\WP_REST_Request $request): \WP_REST_Response {
        $user_id = get_current_user_id();
        global $wpdb;

        $procesos_ids = $wpdb->get_col($wpdb->prepare(
            "SELECT DISTINCT p.ID FROM {$wpdb->posts} p
             INNER JOIN {$wpdb->postmeta} pm ON p.ID = pm.post_id
             WHERE p.post_type = 'jr_proceso' AND p.post_status = 'private'
               AND ((pm.meta_key = '_jr_solicitante_id' AND pm.meta_value = %d)
                    OR (pm.meta_key = '_jr_otra_parte_id' AND pm.meta_value = %d)
                    OR (pm.meta_key = '_jr_mediador_id' AND pm.meta_value = %d))",
            $user_id, $user_id, $user_id
        ));

        $procesos = [];
        foreach ($procesos_ids as $id) {
            $tipo = get_post_meta($id, '_jr_tipo', true);
            $estado = get_post_meta($id, '_jr_estado', true);
            $procesos[] = [
                'id' => $id,
                'titulo' => get_the_title($id),
                'tipo' => self::TIPOS_PROCESO[$tipo]['nombre'] ?? $tipo,
                'estado' => self::ESTADOS_PROCESO[$estado]['nombre'] ?? $estado,
                'color_estado' => self::ESTADOS_PROCESO[$estado]['color'] ?? '#ccc',
            ];
        }

        return new \WP_REST_Response(['procesos' => $procesos]);
    }

    /**
     * API: Obtener proceso
     */
    public function api_get_proceso(\WP_REST_Request $request): \WP_REST_Response {
        $proceso_id = $request->get_param('id');
        $user_id = get_current_user_id();

        $solicitante_id = get_post_meta($proceso_id, '_jr_solicitante_id', true);
        $otra_parte_id = get_post_meta($proceso_id, '_jr_otra_parte_id', true);
        $mediador_id = get_post_meta($proceso_id, '_jr_mediador_id', true);

        $puede_ver = current_user_can('manage_options') ||
                     $user_id == $solicitante_id ||
                     $user_id == $otra_parte_id ||
                     $user_id == $mediador_id;

        if (!$puede_ver) {
            return new \WP_REST_Response(['error' => 'Acceso denegado'], 403);
        }

        $tipo = get_post_meta($proceso_id, '_jr_tipo', true);
        $estado = get_post_meta($proceso_id, '_jr_estado', true);

        return new \WP_REST_Response([
            'id' => $proceso_id,
            'titulo' => get_the_title($proceso_id),
            'tipo' => self::TIPOS_PROCESO[$tipo] ?? [],
            'estado' => self::ESTADOS_PROCESO[$estado] ?? [],
            'fecha_solicitud' => get_post_meta($proceso_id, '_jr_fecha_solicitud', true),
            'fecha_inicio' => get_post_meta($proceso_id, '_jr_fecha_inicio', true),
        ]);
    }

    /**
     * API: Obtener estadísticas
     */
    public function api_get_estadisticas(\WP_REST_Request $request): \WP_REST_Response {
        $user_id = get_current_user_id();
        return new \WP_REST_Response($this->get_estadisticas_usuario($user_id));
    }

    /**
     * API: Obtener mediadores
     */
    public function api_get_mediadores(\WP_REST_Request $request): \WP_REST_Response {
        $mediadores = $this->get_mediadores();
        $lista = [];
        foreach ($mediadores as $mediador) {
            $lista[] = [
                'id' => $mediador->ID,
                'nombre' => $mediador->display_name,
                'avatar' => get_avatar_url($mediador->ID, ['size' => 96]),
            ];
        }
        return new \WP_REST_Response(['mediadores' => $lista]);
    }

    /**
     * Configuración del admin para el panel unificado
     */
    public function get_admin_config(): array {
        return [
            'paginas' => [
                [
                    'slug' => 'justicia-restaurativa',
                    'titulo' => __('Justicia Restaurativa', 'flavor-chat-ia'),
                    'callback' => [$this, 'render_admin_dashboard'],
                ],
                [
                    'slug' => 'jr-procesos',
                    'titulo' => __('Procesos', 'flavor-chat-ia'),
                    'callback' => [$this, 'render_admin_procesos'],
                ],
                [
                    'slug' => 'jr-mediadores',
                    'titulo' => __('Mediadores', 'flavor-chat-ia'),
                    'callback' => [$this, 'render_admin_mediadores'],
                ],
            ],
        ];
    }

    /**
     * Render: Dashboard admin
     */
    public function render_admin_dashboard(): void {
        $procesos = wp_count_posts('jr_proceso');
        $pendientes = get_posts(['post_type' => 'jr_proceso', 'post_status' => 'private', 'meta_query' => [['key' => '_jr_estado', 'value' => 'solicitado']], 'posts_per_page' => -1]);
        $mediadores = $this->get_mediadores();
        ?>
        <div class="wrap flavor-admin-justicia">
            <h1><?php esc_html_e('Justicia Restaurativa', 'flavor-chat-ia'); ?></h1>
            <div class="flavor-stats-grid">
                <div class="flavor-stat-card">
                    <span class="dashicons dashicons-shield"></span>
                    <h3><?php echo esc_html($procesos->private ?? 0); ?></h3>
                    <p><?php esc_html_e('Procesos totales', 'flavor-chat-ia'); ?></p>
                </div>
                <div class="flavor-stat-card">
                    <span class="dashicons dashicons-clock"></span>
                    <h3><?php echo count($pendientes); ?></h3>
                    <p><?php esc_html_e('Pendientes asignar', 'flavor-chat-ia'); ?></p>
                </div>
                <div class="flavor-stat-card">
                    <span class="dashicons dashicons-groups"></span>
                    <h3><?php echo count($mediadores); ?></h3>
                    <p><?php esc_html_e('Mediadores activos', 'flavor-chat-ia'); ?></p>
                </div>
            </div>
        </div>
        <?php
    }

    /**
     * Render: Listado de procesos
     */
    public function render_admin_procesos(): void {
        $procesos = get_posts(['post_type' => 'jr_proceso', 'posts_per_page' => 50, 'post_status' => 'private']);
        ?>
        <div class="wrap">
            <h1><?php esc_html_e('Procesos Restaurativos', 'flavor-chat-ia'); ?></h1>
            <table class="wp-list-table widefat fixed striped">
                <thead><tr><th><?php esc_html_e('Título', 'flavor-chat-ia'); ?></th><th><?php esc_html_e('Tipo', 'flavor-chat-ia'); ?></th><th><?php esc_html_e('Estado', 'flavor-chat-ia'); ?></th><th><?php esc_html_e('Mediador', 'flavor-chat-ia'); ?></th></tr></thead>
                <tbody>
                <?php foreach ($procesos as $proc): $tipo = get_post_meta($proc->ID, '_jr_tipo', true); $estado = get_post_meta($proc->ID, '_jr_estado', true); $mediador_id = get_post_meta($proc->ID, '_jr_mediador_id', true); ?>
                    <tr>
                        <td><a href="<?php echo get_edit_post_link($proc->ID); ?>"><?php echo esc_html($proc->post_title); ?></a></td>
                        <td><?php echo esc_html(self::TIPOS_PROCESO[$tipo]['nombre'] ?? $tipo); ?></td>
                        <td><span style="color: <?php echo esc_attr(self::ESTADOS_PROCESO[$estado]['color'] ?? '#ccc'); ?>"><?php echo esc_html(self::ESTADOS_PROCESO[$estado]['nombre'] ?? $estado); ?></span></td>
                        <td><?php echo $mediador_id ? esc_html(get_userdata($mediador_id)->display_name) : '—'; ?></td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php
    }

    /**
     * Render: Listado de mediadores
     */
    public function render_admin_mediadores(): void {
        $mediadores = $this->get_mediadores();
        ?>
        <div class="wrap">
            <h1><?php esc_html_e('Mediadores', 'flavor-chat-ia'); ?></h1>
            <table class="wp-list-table widefat fixed striped">
                <thead><tr><th><?php esc_html_e('Nombre', 'flavor-chat-ia'); ?></th><th><?php esc_html_e('Email', 'flavor-chat-ia'); ?></th><th><?php esc_html_e('Procesos', 'flavor-chat-ia'); ?></th></tr></thead>
                <tbody>
                <?php foreach ($mediadores as $med): $procesos = get_posts(['post_type' => 'jr_proceso', 'meta_query' => [['key' => '_jr_mediador_id', 'value' => $med->ID]], 'posts_per_page' => -1]); ?>
                    <tr>
                        <td><a href="<?php echo get_edit_user_link($med->ID); ?>"><?php echo esc_html($med->display_name); ?></a></td>
                        <td><?php echo esc_html($med->user_email); ?></td>
                        <td><?php echo count($procesos); ?></td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php
    }

    /**
     * Registra los shortcodes del módulo
     */
    public function register_shortcodes() {
        // Shortcodes originales
        add_shortcode('justicia_restaurativa', [$this, 'shortcode_info']);
        add_shortcode('solicitar_mediacion', [$this, 'shortcode_solicitar']);
        add_shortcode('mis_procesos', [$this, 'shortcode_mis_procesos']);
        add_shortcode('mediadores', [$this, 'shortcode_mediadores']);

        // Aliases con prefijo flavor_justicia_
        add_shortcode('flavor_justicia_info', [$this, 'shortcode_info']);
        add_shortcode('flavor_justicia_solicitar', [$this, 'shortcode_solicitar']);
        add_shortcode('flavor_justicia_mis_procesos', [$this, 'shortcode_mis_procesos']);
        add_shortcode('flavor_justicia_mediadores', [$this, 'shortcode_mediadores']);
    }

    /**
     * Registra Custom Post Types
     */
    public function registrar_post_types() {
        // CPT: Procesos restaurativos
        register_post_type('jr_proceso', [
            'labels' => [
                'name' => __('Procesos Restaurativos', 'flavor-chat-ia'),
                'singular_name' => __('Proceso', 'flavor-chat-ia'),
                'add_new' => __('Nuevo Proceso', 'flavor-chat-ia'),
            ],
            'public' => false,
            'show_ui' => true,
            'supports' => ['title'],
            'menu_icon' => 'dashicons-shield',
            'capability_type' => 'post',
            'capabilities' => [
                'create_posts' => 'manage_options',
            ],
            'map_meta_cap' => true,
        ]);

        // CPT: Sesiones
        register_post_type('jr_sesion', [
            'labels' => [
                'name' => __('Sesiones', 'flavor-chat-ia'),
                'singular_name' => __('Sesión', 'flavor-chat-ia'),
            ],
            'public' => false,
            'show_ui' => true,
            'show_in_menu' => 'edit.php?post_type=jr_proceso',
            'supports' => ['title', 'editor'],
        ]);

        // CPT: Acuerdos
        register_post_type('jr_acuerdo', [
            'labels' => [
                'name' => __('Acuerdos', 'flavor-chat-ia'),
                'singular_name' => __('Acuerdo', 'flavor-chat-ia'),
            ],
            'public' => false,
            'show_ui' => true,
            'show_in_menu' => 'edit.php?post_type=jr_proceso',
            'supports' => ['title', 'editor'],
        ]);
    }

    /**
     * Registra meta boxes
     */
    public function registrar_meta_boxes() {
        add_meta_box(
            'jr_proceso_datos',
            __('Datos del Proceso', 'flavor-chat-ia'),
            [$this, 'render_meta_box_proceso'],
            'jr_proceso',
            'normal',
            'high'
        );

        add_meta_box(
            'jr_proceso_partes',
            __('Partes Involucradas', 'flavor-chat-ia'),
            [$this, 'render_meta_box_partes'],
            'jr_proceso',
            'side',
            'default'
        );
    }

    /**
     * Renderiza meta box del proceso
     */
    public function render_meta_box_proceso($post) {
        wp_nonce_field('jr_proceso_nonce', 'jr_proceso_nonce_field');

        $tipo = get_post_meta($post->ID, '_jr_tipo', true);
        $estado = get_post_meta($post->ID, '_jr_estado', true) ?: 'solicitado';
        $descripcion = get_post_meta($post->ID, '_jr_descripcion', true);
        $mediador_id = get_post_meta($post->ID, '_jr_mediador_id', true);
        $fecha_inicio = get_post_meta($post->ID, '_jr_fecha_inicio', true);
        ?>
        <table class="form-table">
            <tr>
                <th><label for="jr_tipo"><?php esc_html_e('Tipo de proceso', 'flavor-chat-ia'); ?></label></th>
                <td>
                    <select name="jr_tipo" id="jr_tipo">
                        <?php foreach (self::TIPOS_PROCESO as $id => $data) : ?>
                        <option value="<?php echo esc_attr($id); ?>" <?php selected($tipo, $id); ?>>
                            <?php echo esc_html($data['nombre']); ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                </td>
            </tr>
            <tr>
                <th><label for="jr_estado"><?php esc_html_e('Estado', 'flavor-chat-ia'); ?></label></th>
                <td>
                    <select name="jr_estado" id="jr_estado">
                        <?php foreach (self::ESTADOS_PROCESO as $id => $data) : ?>
                        <option value="<?php echo esc_attr($id); ?>" <?php selected($estado, $id); ?>>
                            <?php echo esc_html($data['nombre']); ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                </td>
            </tr>
            <tr>
                <th><label for="jr_mediador"><?php esc_html_e('Mediador/a', 'flavor-chat-ia'); ?></label></th>
                <td>
                    <?php
                    $mediadores = $this->get_mediadores();
                    ?>
                    <select name="jr_mediador_id" id="jr_mediador">
                        <option value=""><?php esc_html_e('Sin asignar', 'flavor-chat-ia'); ?></option>
                        <?php foreach ($mediadores as $mediador) : ?>
                        <option value="<?php echo esc_attr($mediador->ID); ?>" <?php selected($mediador_id, $mediador->ID); ?>>
                            <?php echo esc_html($mediador->display_name); ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                </td>
            </tr>
            <tr>
                <th><label for="jr_fecha_inicio"><?php esc_html_e('Fecha inicio', 'flavor-chat-ia'); ?></label></th>
                <td>
                    <input type="date" name="jr_fecha_inicio" id="jr_fecha_inicio"
                           value="<?php echo esc_attr($fecha_inicio); ?>">
                </td>
            </tr>
            <tr>
                <th><label for="jr_descripcion"><?php esc_html_e('Descripción (confidencial)', 'flavor-chat-ia'); ?></label></th>
                <td>
                    <textarea name="jr_descripcion" id="jr_descripcion" rows="4" class="large-text"><?php echo esc_textarea($descripcion); ?></textarea>
                    <p class="description"><?php esc_html_e('Esta información es confidencial y solo visible para administradores y el mediador.', 'flavor-chat-ia'); ?></p>
                </td>
            </tr>
        </table>
        <?php
    }

    /**
     * Renderiza meta box de partes
     */
    public function render_meta_box_partes($post) {
        $solicitante_id = get_post_meta($post->ID, '_jr_solicitante_id', true);
        $otra_parte_id = get_post_meta($post->ID, '_jr_otra_parte_id', true);

        $solicitante = $solicitante_id ? get_userdata($solicitante_id) : null;
        $otra_parte = $otra_parte_id ? get_userdata($otra_parte_id) : null;
        ?>
        <p>
            <strong><?php esc_html_e('Solicitante:', 'flavor-chat-ia'); ?></strong><br>
            <?php echo $solicitante ? esc_html($solicitante->display_name) : __('No asignado', 'flavor-chat-ia'); ?>
        </p>
        <p>
            <strong><?php esc_html_e('Otra parte:', 'flavor-chat-ia'); ?></strong><br>
            <?php echo $otra_parte ? esc_html($otra_parte->display_name) : __('No asignado', 'flavor-chat-ia'); ?>
        </p>
        <?php
    }

    /**
     * Guarda meta del proceso
     */
    public function guardar_meta_proceso($post_id) {
        if (!isset($_POST['jr_proceso_nonce_field']) ||
            !wp_verify_nonce($_POST['jr_proceso_nonce_field'], 'jr_proceso_nonce')) {
            return;
        }

        if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) return;
        if (!current_user_can('edit_post', $post_id)) return;

        $campos = ['tipo', 'estado', 'mediador_id', 'fecha_inicio', 'descripcion'];
        foreach ($campos as $campo) {
            if (isset($_POST['jr_' . $campo])) {
                update_post_meta($post_id, '_jr_' . $campo, sanitize_text_field($_POST['jr_' . $campo]));
            }
        }
    }

    /**
     * Obtiene mediadores disponibles
     */
    public function get_mediadores() {
        return get_users([
            'meta_key' => '_jr_es_mediador',
            'meta_value' => '1',
        ]);
    }

    /**
     * Verifica acceso a proceso (confidencialidad)
     */
    public function verificar_acceso_proceso() {
        if (!is_singular('jr_proceso')) {
            return;
        }

        $proceso_id = get_the_ID();
        $user_id = get_current_user_id();

        // Solo pueden ver: partes involucradas, mediador, admin
        $solicitante_id = get_post_meta($proceso_id, '_jr_solicitante_id', true);
        $otra_parte_id = get_post_meta($proceso_id, '_jr_otra_parte_id', true);
        $mediador_id = get_post_meta($proceso_id, '_jr_mediador_id', true);

        $puede_ver = current_user_can('manage_options') ||
                     $user_id == $solicitante_id ||
                     $user_id == $otra_parte_id ||
                     $user_id == $mediador_id;

        if (!$puede_ver) {
            wp_die(__('No tienes permiso para ver este proceso. La información es confidencial.', 'flavor-chat-ia'));
        }
    }

    /**
     * AJAX: Solicitar proceso restaurativo
     */
    public function ajax_solicitar_proceso() {
        check_ajax_referer('jr_nonce', 'nonce');

        if (!is_user_logged_in()) {
            wp_send_json_error(['message' => __('Debes iniciar sesión', 'flavor-chat-ia')]);
        }

        $tipo = sanitize_text_field($_POST['tipo'] ?? 'mediacion');
        $descripcion = sanitize_textarea_field($_POST['descripcion'] ?? '');
        $otra_parte_email = sanitize_email($_POST['otra_parte_email'] ?? '');
        $user_id = get_current_user_id();

        if (!$descripcion) {
            wp_send_json_error(['message' => __('Por favor, describe la situación', 'flavor-chat-ia')]);
        }

        // Buscar la otra parte por email
        $otra_parte = get_user_by('email', $otra_parte_email);

        // Crear proceso
        $proceso_id = wp_insert_post([
            'post_type' => 'jr_proceso',
            'post_title' => sprintf(
                __('Proceso %s - %s', 'flavor-chat-ia'),
                self::TIPOS_PROCESO[$tipo]['nombre'],
                date_i18n('d/m/Y')
            ),
            'post_status' => 'private',
            'post_author' => $user_id,
        ]);

        if ($proceso_id) {
            update_post_meta($proceso_id, '_jr_tipo', $tipo);
            update_post_meta($proceso_id, '_jr_estado', 'solicitado');
            update_post_meta($proceso_id, '_jr_descripcion', $descripcion);
            update_post_meta($proceso_id, '_jr_solicitante_id', $user_id);
            update_post_meta($proceso_id, '_jr_fecha_solicitud', current_time('mysql'));

            if ($otra_parte) {
                update_post_meta($proceso_id, '_jr_otra_parte_id', $otra_parte->ID);
                // Notificar a la otra parte
                $this->notificar_otra_parte($proceso_id, $otra_parte->ID, $user_id);
            }

            // Notificar a administradores/mediadores
            $this->notificar_nueva_solicitud($proceso_id);

            wp_send_json_success([
                'message' => __('Solicitud enviada. Un mediador se pondrá en contacto contigo.', 'flavor-chat-ia'),
                'proceso_id' => $proceso_id,
            ]);
        }

        wp_send_json_error(['message' => __('Error al crear la solicitud', 'flavor-chat-ia')]);
    }

    /**
     * AJAX: Responder a solicitud
     */
    public function ajax_responder_solicitud() {
        check_ajax_referer('jr_nonce', 'nonce');

        $proceso_id = absint($_POST['proceso_id'] ?? 0);
        $respuesta = sanitize_text_field($_POST['respuesta'] ?? '');
        $user_id = get_current_user_id();

        if (!$proceso_id) {
            wp_send_json_error(['message' => __('Datos inválidos', 'flavor-chat-ia')]);
        }

        // Verificar que es la otra parte
        $otra_parte_id = get_post_meta($proceso_id, '_jr_otra_parte_id', true);
        if ($otra_parte_id != $user_id) {
            wp_send_json_error(['message' => __('No tienes permiso', 'flavor-chat-ia')]);
        }

        if ($respuesta === 'acepto') {
            update_post_meta($proceso_id, '_jr_estado', 'aceptado');
            update_post_meta($proceso_id, '_jr_fecha_aceptacion', current_time('mysql'));

            $this->notificar_aceptacion($proceso_id);

            wp_send_json_success([
                'message' => __('Has aceptado participar. El mediador coordinará las sesiones.', 'flavor-chat-ia'),
            ]);
        } else {
            update_post_meta($proceso_id, '_jr_estado', 'cancelado');
            update_post_meta($proceso_id, '_jr_motivo_cancelacion', 'otra_parte_rechazó');

            wp_send_json_success([
                'message' => __('Proceso cancelado. Esperamos poder ayudarte en el futuro.', 'flavor-chat-ia'),
            ]);
        }
    }

    /**
     * AJAX: Ser mediador voluntario
     */
    public function ajax_ser_mediador() {
        check_ajax_referer('jr_nonce', 'nonce');

        if (!is_user_logged_in()) {
            wp_send_json_error(['message' => __('Debes iniciar sesión', 'flavor-chat-ia')]);
        }

        $user_id = get_current_user_id();
        $formacion = sanitize_textarea_field($_POST['formacion'] ?? '');
        $motivacion = sanitize_textarea_field($_POST['motivacion'] ?? '');

        // Guardar solicitud
        update_user_meta($user_id, '_jr_solicitud_mediador', [
            'formacion' => $formacion,
            'motivacion' => $motivacion,
            'fecha' => current_time('mysql'),
            'estado' => 'pendiente',
        ]);

        // Notificar a admin
        $this->notificar_solicitud_mediador($user_id);

        wp_send_json_success([
            'message' => __('Solicitud enviada. Nos pondremos en contacto contigo.', 'flavor-chat-ia'),
        ]);
    }

    /**
     * Notifica a la otra parte
     */
    private function notificar_otra_parte($proceso_id, $otra_parte_id, $solicitante_id) {
        if (!class_exists('Flavor_Notification_Center')) {
            return;
        }

        $solicitante = get_userdata($solicitante_id);
        $nc = Flavor_Notification_Center::get_instance();

        $nc->send(
            $otra_parte_id,
            __('Invitación a proceso restaurativo', 'flavor-chat-ia'),
            sprintf(
                __('%s te invita a participar en un proceso de diálogo y mediación.', 'flavor-chat-ia'),
                $solicitante->display_name
            ),
            [
                'module_id' => $this->id,
                'type' => 'info',
                'link' => add_query_arg('proceso', $proceso_id, home_url('/mi-portal/justicia-restaurativa/responder/')),
            ]
        );
    }

    /**
     * Notifica nueva solicitud a mediadores
     */
    private function notificar_nueva_solicitud($proceso_id) {
        $mediadores = $this->get_mediadores();

        if (!class_exists('Flavor_Notification_Center')) {
            return;
        }

        $nc = Flavor_Notification_Center::get_instance();

        foreach ($mediadores as $mediador) {
            $nc->send(
                $mediador->ID,
                __('Nueva solicitud de mediación', 'flavor-chat-ia'),
                __('Se ha solicitado un nuevo proceso restaurativo.', 'flavor-chat-ia'),
                [
                    'module_id' => $this->id,
                    'type' => 'info',
                ]
            );
        }
    }

    /**
     * Notifica aceptación
     */
    private function notificar_aceptacion($proceso_id) {
        $solicitante_id = get_post_meta($proceso_id, '_jr_solicitante_id', true);

        if (!class_exists('Flavor_Notification_Center') || !$solicitante_id) {
            return;
        }

        $nc = Flavor_Notification_Center::get_instance();

        $nc->send(
            $solicitante_id,
            __('¡Proceso aceptado!', 'flavor-chat-ia'),
            __('La otra parte ha aceptado participar en el proceso restaurativo.', 'flavor-chat-ia'),
            [
                'module_id' => $this->id,
                'type' => 'success',
            ]
        );
    }

    /**
     * Notifica solicitud de mediador
     */
    private function notificar_solicitud_mediador($user_id) {
        $admins = get_users(['role' => 'administrator']);
        $user = get_userdata($user_id);

        if (!class_exists('Flavor_Notification_Center')) {
            return;
        }

        $nc = Flavor_Notification_Center::get_instance();

        foreach ($admins as $admin) {
            $nc->send(
                $admin->ID,
                __('Solicitud de mediador', 'flavor-chat-ia'),
                sprintf(__('%s quiere ser mediador voluntario.', 'flavor-chat-ia'), $user->display_name),
                [
                    'module_id' => $this->id,
                    'type' => 'info',
                ]
            );
        }
    }

    /**
     * Shortcode: Información sobre justicia restaurativa
     */
    public function shortcode_info($atts) {
        ob_start();
        include dirname(__FILE__) . '/templates/info.php';
        return ob_get_clean();
    }

    /**
     * Shortcode: Solicitar mediación
     */
    public function shortcode_solicitar($atts) {
        if (!is_user_logged_in()) {
            return '<p>' . __('Inicia sesión para solicitar mediación.', 'flavor-chat-ia') . '</p>';
        }

        ob_start();
        include dirname(__FILE__) . '/templates/solicitar.php';
        return ob_get_clean();
    }

    /**
     * Shortcode: Mis procesos
     */
    public function shortcode_mis_procesos($atts) {
        if (!is_user_logged_in()) {
            return '<p>' . __('Inicia sesión para ver tus procesos.', 'flavor-chat-ia') . '</p>';
        }

        ob_start();
        include dirname(__FILE__) . '/templates/mis-procesos.php';
        return ob_get_clean();
    }

    /**
     * Shortcode: Lista de mediadores
     */
    public function shortcode_mediadores($atts) {
        ob_start();
        include dirname(__FILE__) . '/templates/mediadores.php';
        return ob_get_clean();
    }

    /**
     * Registra widget de dashboard
     */
    public function register_dashboard_widget($registry) {
        $settings = $this->get_settings();
        if (empty($settings['mostrar_en_dashboard'])) {
            return;
        }

        $widget_path = dirname(__FILE__) . '/class-justicia-restaurativa-widget.php';
        if (!class_exists('Flavor_Justicia_Restaurativa_Widget') && file_exists($widget_path)) {
            require_once $widget_path;
        }

        if (class_exists('Flavor_Justicia_Restaurativa_Widget')) {
            $registry->register(new Flavor_Justicia_Restaurativa_Widget($this));
        }
    }

    /**
     * Verifica si debe cargar los assets del módulo
     *
     * @return bool
     */
    private function should_load_assets() {
        global $post;

        if (!$post) {
            return false;
        }

        $shortcodes_modulo = [
            'justicia_restaurativa',
            'solicitar_mediacion',
            'mis_procesos',
            'mediadores',
            'flavor_justicia_info',
            'flavor_justicia_solicitar',
            'flavor_justicia_mis_procesos',
            'flavor_justicia_mediadores',
        ];

        foreach ($shortcodes_modulo as $shortcode) {
            if (has_shortcode($post->post_content, $shortcode)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Encola assets
     */
    public function enqueue_assets() {
        if (!$this->should_load_assets()) {
            return;
        }

        wp_enqueue_style(
            'flavor-justicia-restaurativa',
            FLAVOR_CHAT_IA_URL . 'includes/modules/justicia-restaurativa/assets/css/justicia-restaurativa.css',
            [],
            '1.0.0'
        );

        wp_enqueue_script(
            'flavor-justicia-restaurativa',
            FLAVOR_CHAT_IA_URL . 'includes/modules/justicia-restaurativa/assets/js/justicia-restaurativa.js',
            ['jquery'],
            '1.0.0',
            true
        );

        wp_localize_script('flavor-justicia-restaurativa', 'jrData', [
            'ajaxUrl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('jr_nonce'),
            'i18n' => [
                'confirmSolicitud' => __('¿Deseas iniciar este proceso de mediación?', 'flavor-chat-ia'),
                'enviando' => __('Enviando...', 'flavor-chat-ia'),
            ],
        ]);
    }

    /**
     * Estadísticas del usuario
     */
    public function get_estadisticas_usuario($user_id = null) {
        if (!$user_id) {
            $user_id = get_current_user_id();
        }

        global $wpdb;

        // Procesos como solicitante o como otra parte
        $procesos = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(DISTINCT p.ID) FROM {$wpdb->posts} p
             INNER JOIN {$wpdb->postmeta} pm ON p.ID = pm.post_id
             WHERE p.post_type = 'jr_proceso'
               AND (
                   (pm.meta_key = '_jr_solicitante_id' AND pm.meta_value = %d)
                   OR (pm.meta_key = '_jr_otra_parte_id' AND pm.meta_value = %d)
               )",
            $user_id, $user_id
        ));

        // Acuerdos alcanzados
        $acuerdos = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM {$wpdb->posts} p
             INNER JOIN {$wpdb->postmeta} pm ON p.ID = pm.post_id
             INNER JOIN {$wpdb->postmeta} pm2 ON p.ID = pm2.post_id
             WHERE p.post_type = 'jr_proceso'
               AND pm2.meta_key = '_jr_estado'
               AND pm2.meta_value IN ('acuerdo', 'cumplido')
               AND (
                   (pm.meta_key = '_jr_solicitante_id' AND pm.meta_value = %d)
                   OR (pm.meta_key = '_jr_otra_parte_id' AND pm.meta_value = %d)
               )",
            $user_id, $user_id
        ));

        // Es mediador
        $es_mediador = get_user_meta($user_id, '_jr_es_mediador', true) === '1';

        return [
            'procesos' => (int) $procesos,
            'acuerdos' => (int) $acuerdos,
            'es_mediador' => $es_mediador,
        ];
    }

    /**
     * Valoración para el Sello de Conciencia
     */
    public function get_consciousness_valuation() {
        return [
            'nombre' => 'Justicia Restaurativa',
            'puntuacion' => 92,
            'premisas' => [
                'conciencia_fundamental' => 0.30,
                'interdependencia_radical' => 0.25,
                'madurez_ciclica' => 0.20,
                'valor_intrinseco' => 0.15,
                'abundancia_organizable' => 0.10,
            ],
            'descripcion_contribucion' => 'Transforma el conflicto en oportunidad de crecimiento, reconoce la dignidad de todas las partes y promueve la reparación sobre el castigo.',
            'categoria' => 'gobernanza',
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function get_knowledge_base() {
        return <<<KNOWLEDGE
**Justicia Restaurativa - Guía de Uso**

La Justicia Restaurativa es un enfoque de resolución de conflictos que prioriza la reparación del daño y la reconciliación sobre el castigo.

**Principios:**
- El conflicto es una oportunidad de crecimiento
- Todas las partes tienen dignidad que respetar
- El objetivo es reparar, no castigar
- La comunidad participa como garante

**Tipos de procesos:**
- **Mediación**: Diálogo facilitado entre dos partes
- **Círculo de diálogo**: Conversación comunitaria ampliada
- **Conferencia restaurativa**: Con todas las partes afectadas
- **Círculo de paz**: Proceso comunitario de sanación

**Cómo funciona:**
1. Una parte solicita el proceso
2. Se invita a la otra parte a participar
3. Un mediador facilita el diálogo
4. Se busca un acuerdo reparador
5. Se hace seguimiento del cumplimiento

**Confidencialidad:**
Todo lo dicho en los procesos es confidencial y no puede usarse en otros contextos.
KNOWLEDGE;
    }

    /**
     * {@inheritdoc}
     */
    public function get_actions() {
        return [
            'mis_procesos' => [
                'description' => 'Ver mis procesos de justicia restaurativa',
                'params' => [],
            ],
            'solicitar_proceso' => [
                'description' => 'Solicitar un proceso restaurativo',
                'params' => ['tipo', 'descripcion'],
            ],
            'ver_mediadores' => [
                'description' => 'Ver mediadores disponibles',
                'params' => [],
            ],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function execute_action($action_name, $params) {
        return ['status' => 'not_implemented', 'message' => __('Acción no implementada', 'flavor-chat-ia')];
    }

    /**
     * {@inheritdoc}
     */
    public function get_tool_definitions() {
        return [];
    }

    /**
     * Configuración para el Module Renderer
     *
     * @return array
     */
    public static function get_renderer_config(): array {
        return [
            'module'   => 'justicia-restaurativa',
            'title'    => __('Justicia Restaurativa', 'flavor-chat-ia'),
            'subtitle' => __('Resolución de conflictos y reparación del daño', 'flavor-chat-ia'),
            'icon'     => '⚖️',
            'color'    => 'accent', // Usa variable CSS --flavor-primary del tema

            'database' => [
                'table'       => 'flavor_justicia_restaurativa',
                'primary_key' => 'id',
            ],

            'fields' => [
                'tipo_proceso' => ['type' => 'select', 'label' => __('Tipo de proceso', 'flavor-chat-ia'), 'options' => ['mediacion', 'conferencia', 'circulo_paz']],
                'descripcion'  => ['type' => 'textarea', 'label' => __('Descripción del conflicto', 'flavor-chat-ia')],
                'partes'       => ['type' => 'text', 'label' => __('Partes implicadas', 'flavor-chat-ia')],
                'mediador_id'  => ['type' => 'select', 'label' => __('Mediador asignado', 'flavor-chat-ia')],
                'fecha_sesion' => ['type' => 'datetime', 'label' => __('Fecha de sesión', 'flavor-chat-ia')],
                'acuerdo'      => ['type' => 'textarea', 'label' => __('Acuerdo reparador', 'flavor-chat-ia')],
            ],

            'estados' => [
                'solicitado'  => ['label' => __('Solicitado', 'flavor-chat-ia'), 'color' => 'blue', 'icon' => '📋'],
                'aceptado'    => ['label' => __('Aceptado', 'flavor-chat-ia'), 'color' => 'yellow', 'icon' => '✓'],
                'en_proceso'  => ['label' => __('En proceso', 'flavor-chat-ia'), 'color' => 'indigo', 'icon' => '🔄'],
                'acordado'    => ['label' => __('Acordado', 'flavor-chat-ia'), 'color' => 'green', 'icon' => '🤝'],
                'cumplido'    => ['label' => __('Cumplido', 'flavor-chat-ia'), 'color' => 'emerald', 'icon' => '✅'],
                'no_acuerdo'  => ['label' => __('Sin acuerdo', 'flavor-chat-ia'), 'color' => 'red', 'icon' => '❌'],
            ],

            'stats' => [
                'procesos_activos' => ['label' => __('Procesos activos', 'flavor-chat-ia'), 'icon' => '⚖️', 'color' => 'violet'],
                'acuerdos_logrados' => ['label' => __('Acuerdos logrados', 'flavor-chat-ia'), 'icon' => '🤝', 'color' => 'green'],
                'mediadores'       => ['label' => __('Mediadores', 'flavor-chat-ia'), 'icon' => '👤', 'color' => 'blue'],
                'tasa_exito'       => ['label' => __('Tasa de éxito', 'flavor-chat-ia'), 'icon' => '📊', 'color' => 'emerald'],
            ],

            'card' => [
                'template'     => 'proceso-card',
                'title_field'  => 'tipo_proceso',
                'subtitle_field' => 'estado',
                'meta_fields'  => ['fecha_sesion', 'mediador'],
                'show_estado'  => true,
                'privado'      => true,
            ],

            'tabs' => [
                'informacion' => [
                    'label'   => __('Información', 'flavor-chat-ia'),
                    'icon'    => 'dashicons-info',
                    'content' => 'shortcode:justicia_informacion',
                    'public'  => true,
                ],
                'mediadores' => [
                    'label'   => __('Mediadores', 'flavor-chat-ia'),
                    'icon'    => 'dashicons-groups',
                    'content' => 'shortcode:justicia_mediadores',
                    'public'  => true,
                ],
                'solicitar' => [
                    'label'      => __('Solicitar proceso', 'flavor-chat-ia'),
                    'icon'       => 'dashicons-plus-alt',
                    'content'    => 'shortcode:justicia_solicitar',
                    'requires_login' => true,
                ],
                'mis-procesos' => [
                    'label'      => __('Mis procesos', 'flavor-chat-ia'),
                    'icon'       => 'dashicons-admin-users',
                    'content'    => 'shortcode:justicia_mis_procesos',
                    'requires_login' => true,
                ],
            ],

            'archive' => [
                'columns'    => 2,
                'per_page'   => 10,
                'order_by'   => 'fecha_solicitud',
                'order'      => 'DESC',
                'filterable' => ['tipo_proceso', 'estado'],
                'privado'    => true,
            ],

            'dashboard' => [
                'widgets' => ['stats', 'mis_procesos', 'proximas_sesiones', 'recursos'],
                'actions' => [
                    'solicitar' => ['label' => __('Solicitar mediación', 'flavor-chat-ia'), 'icon' => '⚖️', 'color' => 'violet'],
                    'informar'  => ['label' => __('Más información', 'flavor-chat-ia'), 'icon' => 'ℹ️', 'color' => 'blue'],
                ],
            ],

            'features' => [
                'confidencialidad' => true,
                'seguimiento'      => true,
                'calendario'       => true,
                'documentos'       => true,
                'chat_privado'     => true,
            ],
        ];
    }


    /**
     * Inicializa el dashboard tab del módulo
     */
    private function inicializar_dashboard_tab() {
        $archivo = dirname(__FILE__) . '/class-justicia-restaurativa-dashboard-tab.php';
        if (file_exists($archivo)) {
            require_once $archivo;
            if (class_exists('Flavor_Justicia_Restaurativa_Dashboard_Tab')) {
                Flavor_Justicia_Restaurativa_Dashboard_Tab::get_instance();
            }
        }
    }
}

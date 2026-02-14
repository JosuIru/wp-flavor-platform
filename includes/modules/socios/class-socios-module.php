<?php
/**
 * Módulo de Gestión de Socios para Chat IA
 *
 * @package FlavorChatIA
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Módulo de Gestión de Socios - Control de socios/miembros de la cooperativa
 */
class Flavor_Chat_Socios_Module extends Flavor_Chat_Module_Base {

    use Flavor_Module_Admin_Pages_Trait;
    use Flavor_Module_Notifications_Trait;

    /**
     * Constructor
     */
    public function __construct() {
        $this->id = 'socios';
        $this->name = 'Gestion de Socios'; // Translation loaded on init
        $this->description = 'Gestion de socios, cuotas y membresias desde la app movil.'; // Translation loaded on init

        // Configurar visibilidad por defecto: solo miembros pueden ver este módulo
        $this->default_visibility = 'members_only';
        $this->required_capability = 'read';

        parent::__construct();
    }

    /**
     * {@inheritdoc}
     */
    public function can_activate() {
        global $wpdb;
        $tabla_socios = $wpdb->prefix . 'flavor_socios';

        return Flavor_Chat_Helpers::tabla_existe($tabla_socios);
    }

    /**
     * {@inheritdoc}
     */
    public function get_activation_error() {
        if (!$this->can_activate()) {
            return __('Las tablas de Socios no están creadas. Activa el módulo para crearlas automáticamente.', 'flavor-chat-ia');
        }
        
    return '';
    }

/**
     * Verifica si el módulo está activo
     */
    public function is_active() {
        return $this->can_activate();
    }

    /**
     * {@inheritdoc}
     */
    protected function get_default_settings() {
        return [
            'cuota_mensual' => 30.00,
            'cuota_anual' => 300.00,
            'dia_cargo' => 1, // Día del mes para cargo
            'permite_cuota_reducida' => true,
            'requiere_validacion_alta' => true,
            'tipos_socio' => [
                'consumidor' => __('Socio Consumidor', 'flavor-chat-ia'),
                'trabajador' => __('Socio Trabajador', 'flavor-chat-ia'),
                'colaborador' => __('Socio Colaborador', 'flavor-chat-ia'),
            ],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function init() {
        add_action('init', [$this, 'maybe_create_tables']);
        add_action('init', [$this, 'maybe_create_pages']);
        add_action('init', [$this, 'register_shortcodes']);
        add_action('rest_api_init', [$this, 'register_rest_routes']);
        add_action('wp_enqueue_scripts', [$this, 'enqueue_frontend_assets']);

        // Registrar en Panel Unificado de Gestión
        $this->registrar_en_panel_unificado();

        // Cargar sistema de cuotas periodicas
        $ruta_archivo_subscriptions = FLAVOR_CHAT_IA_PATH . 'includes/modules/socios/class-socios-subscriptions.php';
        if (file_exists($ruta_archivo_subscriptions)) {
            require_once $ruta_archivo_subscriptions;
            Flavor_Socios_Subscriptions::get_instance();
        }

        // Cargar gestor de pagos
        $ruta_archivo_payments = FLAVOR_CHAT_IA_PATH . 'includes/modules/socios/class-socios-payment-manager.php';
        if (file_exists($ruta_archivo_payments)) {
            require_once $ruta_archivo_payments;
            Flavor_Socios_Payment_Manager::get_instance();
        }
    }

    // =========================================================
    // Shortcodes Frontend
    // =========================================================

    /**
     * Registra los shortcodes del módulo
     */
    public function register_shortcodes() {
        add_shortcode('socios_pagar_cuota', [$this, 'shortcode_pagar_cuota']);
        add_shortcode('socios_mi_perfil', [$this, 'shortcode_mi_perfil']);
        add_shortcode('socios_mis_cuotas', [$this, 'shortcode_mis_cuotas']);
    }

    /**
     * Shortcode: Pagar cuota
     */
    public function shortcode_pagar_cuota($atributos) {
        $atributos = shortcode_atts([], $atributos, 'socios_pagar_cuota');

        $identificador_usuario = get_current_user_id();
        $socio = null;
        $cuotas_pendientes = [];
        $total_pendiente = 0;
        $gateways = [];

        if ($identificador_usuario) {
            $resultado_perfil = $this->action_mi_perfil_socio([]);
            if ($resultado_perfil['success']) {
                $socio = $resultado_perfil['socio'];
            }

            $resultado_cuotas = $this->action_mis_cuotas(['estado' => 'pendiente']);
            if ($resultado_cuotas['success']) {
                $cuotas_pendientes = $resultado_cuotas['cuotas'];
                $total_pendiente = $resultado_cuotas['resumen']['total_pendiente'];
            }

            // Obtener gateways de pago
            $ruta_archivo_payments = FLAVOR_CHAT_IA_PATH . 'includes/modules/socios/class-socios-payment-manager.php';
            if (file_exists($ruta_archivo_payments)) {
                require_once $ruta_archivo_payments;
                $payment_manager = Flavor_Socios_Payment_Manager::get_instance();
                $gateways = $payment_manager->get_gateways(true);
            }
        }

        ob_start();
        include dirname(__FILE__) . '/views/pagar-cuota.php';
        return ob_get_clean();
    }

    /**
     * Shortcode: Mi perfil de socio
     */
    public function shortcode_mi_perfil($atributos) {
        $atributos = shortcode_atts([], $atributos, 'socios_mi_perfil');

        $identificador_usuario = get_current_user_id();

        if (!$identificador_usuario) {
            return '<p class="flavor-soc-login-required">' .
                sprintf(
                    esc_html__('Debes %siniciar sesión%s para ver tu perfil de socio.', 'flavor-chat-ia'),
                    '<a href="' . esc_url(wp_login_url(get_permalink())) . '">',
                    '</a>'
                ) . '</p>';
        }

        $resultado = $this->action_mi_perfil_socio([]);

        if (!$resultado['success']) {
            return '<p class="flavor-soc-error">' . esc_html($resultado['error']) . '</p>';
        }

        $socio = $resultado['socio'];
        $tipos_socio = $this->get_setting('tipos_socio', []);
        $socio['tipo_label'] = $tipos_socio[$socio['tipo']] ?? ucfirst($socio['tipo']);

        ob_start();
        ?>
        <div class="flavor-soc-perfil">
            <div class="flavor-soc-perfil-card">
                <div class="flavor-soc-perfil-avatar">
                    <?php echo get_avatar($identificador_usuario, 100); ?>
                </div>
                <div class="flavor-soc-perfil-info">
                    <h3><?php echo esc_html($socio['nombre']); ?></h3>
                    <p class="flavor-soc-numero"><?php printf(esc_html__('Socio #%s', 'flavor-chat-ia'), esc_html($socio['numero'])); ?></p>
                    <p class="flavor-soc-email"><?php echo esc_html($socio['email']); ?></p>
                </div>
            </div>
            <div class="flavor-soc-perfil-datos">
                <div class="flavor-soc-dato-item">
                    <span class="label"><?php esc_html_e('Tipo de socio', 'flavor-chat-ia'); ?></span>
                    <span class="valor"><?php echo esc_html($socio['tipo_label']); ?></span>
                </div>
                <div class="flavor-soc-dato-item">
                    <span class="label"><?php esc_html_e('Fecha de alta', 'flavor-chat-ia'); ?></span>
                    <span class="valor"><?php echo esc_html($socio['fecha_alta']); ?></span>
                </div>
                <div class="flavor-soc-dato-item">
                    <span class="label"><?php esc_html_e('Estado', 'flavor-chat-ia'); ?></span>
                    <span class="valor estado-<?php echo esc_attr($socio['estado']); ?>"><?php echo esc_html(ucfirst($socio['estado'])); ?></span>
                </div>
                <div class="flavor-soc-dato-item">
                    <span class="label"><?php esc_html_e('Cuota mensual', 'flavor-chat-ia'); ?></span>
                    <span class="valor"><?php echo esc_html(number_format($socio['cuota_mensual'], 2, ',', '.')); ?> €</span>
                </div>
            </div>
        </div>
        <?php
        return ob_get_clean();
    }

    /**
     * Shortcode: Mis cuotas
     */
    public function shortcode_mis_cuotas($atributos) {
        $atributos = shortcode_atts([
            'limite' => 12,
            'estado' => '',
        ], $atributos, 'socios_mis_cuotas');

        $identificador_usuario = get_current_user_id();

        if (!$identificador_usuario) {
            return '<p class="flavor-soc-login-required">' .
                sprintf(
                    esc_html__('Debes %siniciar sesión%s para ver tus cuotas.', 'flavor-chat-ia'),
                    '<a href="' . esc_url(wp_login_url(get_permalink())) . '">',
                    '</a>'
                ) . '</p>';
        }

        $resultado = $this->action_mis_cuotas([
            'limite' => absint($atributos['limite']),
            'estado' => $atributos['estado'],
        ]);

        if (!$resultado['success']) {
            return '<p class="flavor-soc-error">' . esc_html($resultado['error']) . '</p>';
        }

        $cuotas = $resultado['cuotas'];
        $resumen = $resultado['resumen'];

        ob_start();
        ?>
        <div class="flavor-soc-mis-cuotas">
            <div class="flavor-soc-cuotas-resumen">
                <div class="resumen-item">
                    <span class="valor"><?php echo esc_html($resumen['cuotas_pendientes']); ?></span>
                    <span class="label"><?php esc_html_e('Pendientes', 'flavor-chat-ia'); ?></span>
                </div>
                <div class="resumen-item">
                    <span class="valor"><?php echo esc_html(number_format($resumen['total_pendiente'], 2, ',', '.')); ?> €</span>
                    <span class="label"><?php esc_html_e('Total pendiente', 'flavor-chat-ia'); ?></span>
                </div>
            </div>

            <?php if (empty($cuotas)): ?>
                <p class="flavor-soc-sin-cuotas"><?php esc_html_e('No tienes cuotas registradas.', 'flavor-chat-ia'); ?></p>
            <?php else: ?>
                <table class="flavor-soc-tabla-cuotas">
                    <thead>
                        <tr>
                            <th><?php esc_html_e('Periodo', 'flavor-chat-ia'); ?></th>
                            <th><?php esc_html_e('Importe', 'flavor-chat-ia'); ?></th>
                            <th><?php esc_html_e('Fecha cargo', 'flavor-chat-ia'); ?></th>
                            <th><?php esc_html_e('Estado', 'flavor-chat-ia'); ?></th>
                            <th><?php esc_html_e('Fecha pago', 'flavor-chat-ia'); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($cuotas as $cuota): ?>
                            <tr class="estado-<?php echo esc_attr($cuota['estado']); ?>">
                                <td><?php echo esc_html($cuota['periodo']); ?></td>
                                <td><?php echo esc_html(number_format($cuota['importe'], 2, ',', '.')); ?> €</td>
                                <td><?php echo esc_html($cuota['fecha_cargo']); ?></td>
                                <td><span class="badge badge-<?php echo esc_attr($cuota['estado']); ?>"><?php echo esc_html(ucfirst($cuota['estado'])); ?></span></td>
                                <td><?php echo $cuota['fecha_pago'] ? esc_html($cuota['fecha_pago']) : '-'; ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </div>
        <?php
        return ob_get_clean();
    }

    // =========================================================
    // Frontend Assets
    // =========================================================

    /**
     * Encola assets del frontend
     */
    public function enqueue_frontend_assets() {
        if (!$this->should_load_assets()) {
            return;
        }

        $directorio_plugin = plugin_dir_url(dirname(dirname(dirname(__FILE__))));

        wp_enqueue_style(
            'flavor-socios',
            $directorio_plugin . 'modules/socios/assets/css/socios.css',
            [],
            FLAVOR_CHAT_IA_VERSION
        );

        wp_enqueue_script(
            'flavor-socios',
            $directorio_plugin . 'modules/socios/assets/js/socios.js',
            ['jquery'],
            FLAVOR_CHAT_IA_VERSION,
            true
        );

        wp_localize_script('flavor-socios', 'flavorSociosConfig', [
            'ajaxUrl' => admin_url('admin-ajax.php'),
            'nonce'   => wp_create_nonce('flavor_socios_nonce'),
            'strings' => [
                'errorConexion'   => __('Error de conexión. Inténtalo de nuevo.', 'flavor-chat-ia'),
                'pagoConfirmado'  => __('Pago confirmado correctamente.', 'flavor-chat-ia'),
                'copiado'         => __('Copiado al portapapeles.', 'flavor-chat-ia'),
            ],
        ]);
    }

    /**
     * Determina si se deben cargar los assets
     */
    private function should_load_assets() {
        global $post;

        if (!$post) {
            return false;
        }

        // Cargar en páginas de socios
        if (strpos($post->post_name, 'socio') !== false) {
            return true;
        }

        // Cargar si hay shortcodes del módulo
        $shortcodes_modulo = ['socios_pagar_cuota', 'socios_mi_perfil', 'socios_mis_cuotas'];
        foreach ($shortcodes_modulo as $shortcode) {
            if (has_shortcode($post->post_content, $shortcode)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Registrar rutas REST API (para apps)
     */
    public function register_rest_routes() {
        register_rest_route('flavor/v1', '/socios/perfil', [
            'methods' => 'GET',
            'callback' => [$this, 'rest_mi_perfil'],
            'permission_callback' => 'is_user_logged_in',
        ]);

        register_rest_route('flavor/v1', '/socios/cuotas', [
            'methods' => 'GET',
            'callback' => [$this, 'rest_mis_cuotas'],
            'permission_callback' => 'is_user_logged_in',
        ]);

        register_rest_route('flavor/v1', '/socios/actualizar', [
            'methods' => 'POST',
            'callback' => [$this, 'rest_actualizar_datos'],
            'permission_callback' => 'is_user_logged_in',
        ]);

        register_rest_route('flavor/v1', '/socios/cuotas/pagar', [
            'methods' => 'POST',
            'callback' => [$this, 'rest_pagar_cuota'],
            'permission_callback' => 'is_user_logged_in',
        ]);

        register_rest_route('flavor/v1', '/socios/estadisticas', [
            'methods' => 'GET',
            'callback' => [$this, 'rest_estadisticas'],
            'permission_callback' => function() {
                return current_user_can('manage_options');
            },
        ]);
    }

    public function rest_mi_perfil($request) {
        return rest_ensure_response($this->action_mi_perfil_socio([]));
    }

    public function rest_mis_cuotas($request) {
        return rest_ensure_response($this->action_mis_cuotas([
            'estado' => $request->get_param('estado'),
            'limite' => $request->get_param('limite'),
        ]));
    }

    public function rest_actualizar_datos($request) {
        return rest_ensure_response($this->action_actualizar_datos([
            'telefono' => $request->get_param('telefono'),
            'direccion' => $request->get_param('direccion'),
            'iban' => $request->get_param('iban'),
            'notas' => $request->get_param('notas'),
        ]));
    }

    public function rest_pagar_cuota($request) {
        return rest_ensure_response($this->action_pagar_cuota([
            'cuota_id' => $request->get_param('cuota_id'),
            'metodo_pago' => $request->get_param('metodo_pago'),
            'referencia' => $request->get_param('referencia'),
        ]));
    }

    public function rest_estadisticas($request) {
        return rest_ensure_response($this->action_estadisticas_socios([]));
    }

    /**
     * Configuración para el Panel Unificado de Gestión
     *
     * @return array Configuración del módulo
     */
    protected function get_admin_config() {
        return [
            'id' => 'socios',
            'label' => __('Socios', 'flavor-chat-ia'),
            'icon' => 'dashicons-groups',
            'capability' => 'manage_options',
            'categoria' => 'personas',
            'paginas' => [
                [
                    'slug' => 'socios-dashboard',
                    'titulo' => __('Dashboard', 'flavor-chat-ia'),
                    'callback' => [$this, 'render_admin_dashboard'],
                ],
                [
                    'slug' => 'socios-listado',
                    'titulo' => __('Listado de Socios', 'flavor-chat-ia'),
                    'callback' => [$this, 'render_admin_listado'],
                ],
                [
                    'slug' => 'socios-cuotas',
                    'titulo' => __('Cuotas', 'flavor-chat-ia'),
                    'callback' => [$this, 'render_admin_cuotas'],
                    'badge' => [$this, 'contar_cuotas_pendientes'],
                ],
                [
                    'slug' => 'socios-altas-bajas',
                    'titulo' => __('Altas/Bajas', 'flavor-chat-ia'),
                    'callback' => [$this, 'render_admin_altas_bajas'],
                ],
                [
                    'slug' => 'socios-config',
                    'titulo' => __('Configuración', 'flavor-chat-ia'),
                    'callback' => [$this, 'render_admin_config'],
                ],
            ],
            'estadisticas' => [$this, 'get_estadisticas_dashboard'],
        ];
    }

    /**
     * Cuenta cuotas pendientes de pago
     *
     * @return int
     */
    public function contar_cuotas_pendientes() {
        // Verificar que el módulo esté activo
        if (!$this->can_activate()) {
            return 0;
        }

        global $wpdb;
        $tabla_cuotas = $wpdb->prefix . 'flavor_socios_cuotas';

        if (!Flavor_Chat_Helpers::tabla_existe($tabla_cuotas)) {
            return 0;
        }

        return (int) $wpdb->get_var(
            "SELECT COUNT(*) FROM {$tabla_cuotas} WHERE estado = 'pendiente'"
        );
    }

    /**
     * Estadísticas para el dashboard unificado
     *
     * @return array
     */
    public function get_estadisticas_dashboard() {
        global $wpdb;
        $estadisticas = [];
        $tabla_socios = $wpdb->prefix . 'flavor_socios';
        $tabla_cuotas = $wpdb->prefix . 'flavor_socios_cuotas';

        if (!Flavor_Chat_Helpers::tabla_existe($tabla_socios)) {
            return $estadisticas;
        }

        // Socios activos
        $socios_activos = (int) $wpdb->get_var(
            "SELECT COUNT(*) FROM {$tabla_socios} WHERE estado = 'activo'"
        );
        $estadisticas[] = [
            'icon' => 'dashicons-groups',
            'valor' => $socios_activos,
            'label' => __('Socios activos', 'flavor-chat-ia'),
            'color' => 'green',
            'enlace' => admin_url('admin.php?page=socios-listado&estado=activo'),
        ];

        // Cuotas pendientes
        if (Flavor_Chat_Helpers::tabla_existe($tabla_cuotas)) {
            $cuotas_pendientes = (int) $wpdb->get_var(
                "SELECT COUNT(*) FROM {$tabla_cuotas} WHERE estado = 'pendiente'"
            );
            $estadisticas[] = [
                'icon' => 'dashicons-money-alt',
                'valor' => $cuotas_pendientes,
                'label' => __('Cuotas pendientes', 'flavor-chat-ia'),
                'color' => $cuotas_pendientes > 0 ? 'orange' : 'green',
                'enlace' => admin_url('admin.php?page=socios-cuotas&estado=pendiente'),
            ];
        }

        return $estadisticas;
    }

    /**
     * Renderiza el dashboard de admin de socios
     */
    public function render_admin_dashboard() {
        echo '<div class="wrap flavor-modulo-page">';
        $this->render_page_header(__('Dashboard de Socios', 'flavor-chat-ia'), [
            ['label' => __('Nuevo Socio', 'flavor-chat-ia'), 'url' => admin_url('admin.php?page=socios-altas-bajas'), 'class' => 'button-primary'],
        ]);
        echo '<p>' . __('Panel de control del módulo de gestión de socios.', 'flavor-chat-ia') . '</p>';

        if (!$this->can_activate()) {
            echo '<div class="notice notice-warning"><p>' . esc_html__('El módulo no está activo o no tiene tablas creadas.', 'flavor-chat-ia') . '</p></div>';
            echo '</div>';
            return;
        }

        global $wpdb;
        $tabla_socios = $wpdb->prefix . 'flavor_socios';
        $tabla_cuotas = $wpdb->prefix . 'flavor_socios_cuotas';

        $activos = (int) $wpdb->get_var("SELECT COUNT(*) FROM $tabla_socios WHERE estado = 'activo'");
        $suspendidos = (int) $wpdb->get_var("SELECT COUNT(*) FROM $tabla_socios WHERE estado = 'suspendido'");
        $bajas = (int) $wpdb->get_var("SELECT COUNT(*) FROM $tabla_socios WHERE estado = 'baja'");
        $cuotas_pendientes = (int) $wpdb->get_var("SELECT COUNT(*) FROM $tabla_cuotas WHERE estado = 'pendiente'");

        echo '<div class="flavor-stats-grid">';
        echo '<div class="flavor-stat-card"><span class="dashicons dashicons-groups"></span><div class="stat-content"><span class="stat-number">' . esc_html($activos) . '</span><span class="stat-label">' . esc_html__('Socios activos', 'flavor-chat-ia') . '</span></div></div>';
        echo '<div class="flavor-stat-card"><span class="dashicons dashicons-warning"></span><div class="stat-content"><span class="stat-number">' . esc_html($suspendidos) . '</span><span class="stat-label">' . esc_html__('Suspendidos', 'flavor-chat-ia') . '</span></div></div>';
        echo '<div class="flavor-stat-card"><span class="dashicons dashicons-dismiss"></span><div class="stat-content"><span class="stat-number">' . esc_html($bajas) . '</span><span class="stat-label">' . esc_html__('Bajas', 'flavor-chat-ia') . '</span></div></div>';
        echo '<div class="flavor-stat-card"><span class="dashicons dashicons-money-alt"></span><div class="stat-content"><span class="stat-number">' . esc_html($cuotas_pendientes) . '</span><span class="stat-label">' . esc_html__('Cuotas pendientes', 'flavor-chat-ia') . '</span></div></div>';
        echo '</div>';

        echo '</div>';
    }

    /**
     * Renderiza el listado de socios
     */
    public function render_admin_listado() {
        echo '<div class="wrap flavor-modulo-page">';
        $this->render_page_header(__('Listado de Socios', 'flavor-chat-ia'), [
            ['label' => __('Nuevo Socio', 'flavor-chat-ia'), 'url' => admin_url('admin.php?page=socios-altas-bajas'), 'class' => 'button-primary'],
        ]);
        $this->handle_admin_actions();

        if (!$this->can_activate()) {
            echo '<div class="notice notice-warning"><p>' . esc_html__('El módulo no está activo o no tiene tablas creadas.', 'flavor-chat-ia') . '</p></div>';
            echo '</div>';
            return;
        }

        $estado = isset($_GET['estado']) ? sanitize_text_field($_GET['estado']) : '';
        $tipo = isset($_GET['tipo']) ? sanitize_text_field($_GET['tipo']) : '';
        $busqueda = isset($_GET['s']) ? sanitize_text_field($_GET['s']) : '';

        echo '<form method="get" style="margin: 12px 0;">';
        echo '<input type="hidden" name="page" value="socios-listado">';
        echo '<select name="estado">';
        echo '<option value="">' . esc_html__('Todos los estados', 'flavor-chat-ia') . '</option>';
        foreach (['activo' => __('Activo', 'flavor-chat-ia'), 'suspendido' => __('Suspendido', 'flavor-chat-ia'), 'baja' => __('Baja', 'flavor-chat-ia')] as $key => $label) {
            echo '<option value="' . esc_attr($key) . '" ' . selected($estado, $key, false) . '>' . esc_html($label) . '</option>';
        }
        echo '</select> ';
        echo '<select name="tipo">';
        echo '<option value="">' . esc_html__('Todos los tipos', 'flavor-chat-ia') . '</option>';
        foreach ($this->get_setting('tipos_socio', []) as $key => $label) {
            echo '<option value="' . esc_attr($key) . '" ' . selected($tipo, $key, false) . '>' . esc_html($label) . '</option>';
        }
        echo '</select> ';
        echo '<input type="search" name="s" placeholder="' . esc_attr__('Buscar por nombre o email', 'flavor-chat-ia') . '" value="' . esc_attr($busqueda) . '"> ';
        echo '<button class="button">' . esc_html__('Filtrar', 'flavor-chat-ia') . '</button>';
        echo '</form>';

        global $wpdb;
        $tabla_socios = $wpdb->prefix . 'flavor_socios';
        $tabla_users = $wpdb->users;

        $where = [];
        $params = [];
        if ($estado) {
            $where[] = 's.estado = %s';
            $params[] = $estado;
        }
        if ($tipo) {
            $where[] = 's.tipo_socio = %s';
            $params[] = $tipo;
        }
        if ($busqueda) {
            $where[] = '(u.display_name LIKE %s OR u.user_email LIKE %s)';
            $like = '%' . $wpdb->esc_like($busqueda) . '%';
            $params[] = $like;
            $params[] = $like;
        }

        $sql = "SELECT s.*, u.display_name, u.user_email FROM $tabla_socios s LEFT JOIN $tabla_users u ON s.usuario_id = u.ID";
        if ($where) {
            $sql .= ' WHERE ' . implode(' AND ', $where);
        }
        $sql .= ' ORDER BY s.fecha_alta DESC LIMIT 200';

        $socios = $params ? $wpdb->get_results($wpdb->prepare($sql, $params)) : $wpdb->get_results($sql);

        if (empty($socios)) {
            echo '<p>' . esc_html__('No hay socios con esos filtros.', 'flavor-chat-ia') . '</p>';
            echo '</div>';
            return;
        }

        echo '<table class="widefat striped"><thead><tr>';
        echo '<th>ID</th>';
        echo '<th>' . esc_html__('Nombre', 'flavor-chat-ia') . '</th>';
        echo '<th>Email</th>';
        echo '<th>' . esc_html__('Número', 'flavor-chat-ia') . '</th>';
        echo '<th>' . esc_html__('Tipo', 'flavor-chat-ia') . '</th>';
        echo '<th>' . esc_html__('Estado', 'flavor-chat-ia') . '</th>';
        echo '<th>' . esc_html__('Acciones', 'flavor-chat-ia') . '</th>';
        echo '</tr></thead><tbody>';
        foreach ($socios as $socio) {
            echo '<tr>';
            echo '<td>' . esc_html($socio->id) . '</td>';
            echo '<td>' . esc_html($socio->display_name ?: '-') . '</td>';
            echo '<td>' . esc_html($socio->user_email ?: '-') . '</td>';
            echo '<td>' . esc_html($socio->numero_socio) . '</td>';
            echo '<td>' . esc_html($socio->tipo_socio) . '</td>';
            echo '<td>' . esc_html($socio->estado) . '</td>';
            echo '<td>' . $this->render_estado_actions($socio->id, $socio->estado, 'socios-listado') . '</td>';
            echo '</tr>';
        }
        echo '</tbody></table>';
        echo '</div>';
    }

    /**
     * Renderiza la gestión de cuotas
     */
    public function render_admin_cuotas() {
        echo '<div class="wrap flavor-modulo-page">';
        $this->render_page_header(__('Gestión de Cuotas', 'flavor-chat-ia'), [
            ['label' => __('Generar Cuotas', 'flavor-chat-ia'), 'url' => '#', 'class' => 'button-primary'],
        ]);
        $this->handle_admin_actions();
        $this->handle_admin_cuota_action();

        if (!$this->can_activate()) {
            echo '<div class="notice notice-warning"><p>' . esc_html__('El módulo no está activo o no tiene tablas creadas.', 'flavor-chat-ia') . '</p></div>';
            echo '</div>';
            return;
        }

        $estado = isset($_GET['estado']) ? sanitize_text_field($_GET['estado']) : '';

        echo '<form method="get" style="margin: 12px 0;">';
        echo '<input type="hidden" name="page" value="socios-cuotas">';
        echo '<select name="estado">';
        echo '<option value="">' . esc_html__('Todos los estados', 'flavor-chat-ia') . '</option>';
        foreach (['pendiente', 'pagada', 'vencida', 'condonada'] as $key) {
            echo '<option value="' . esc_attr($key) . '" ' . selected($estado, $key, false) . '>' . esc_html(ucfirst($key)) . '</option>';
        }
        echo '</select> ';
        echo '<button class="button">' . esc_html__('Filtrar', 'flavor-chat-ia') . '</button>';
        echo '</form>';

        global $wpdb;
        $tabla_cuotas = $wpdb->prefix . 'flavor_socios_cuotas';
        $tabla_socios = $wpdb->prefix . 'flavor_socios';

        $where = [];
        $params = [];
        if ($estado) {
            $where[] = 'c.estado = %s';
            $params[] = $estado;
        }
        $sql = "SELECT c.*, s.numero_socio FROM $tabla_cuotas c LEFT JOIN $tabla_socios s ON c.socio_id = s.id";
        if ($where) {
            $sql .= ' WHERE ' . implode(' AND ', $where);
        }
        $sql .= ' ORDER BY c.fecha_cargo DESC LIMIT 200';

        $cuotas = $params ? $wpdb->get_results($wpdb->prepare($sql, $params)) : $wpdb->get_results($sql);

        if (empty($cuotas)) {
            echo '<p>' . esc_html__('No hay cuotas con esos filtros.', 'flavor-chat-ia') . '</p>';
            echo '</div>';
            return;
        }

        echo '<table class="widefat striped"><thead><tr>';
        echo '<th>ID</th>';
        echo '<th>' . esc_html__('Socio', 'flavor-chat-ia') . '</th>';
        echo '<th>' . esc_html__('Periodo', 'flavor-chat-ia') . '</th>';
        echo '<th>' . esc_html__('Importe', 'flavor-chat-ia') . '</th>';
        echo '<th>' . esc_html__('Fecha cargo', 'flavor-chat-ia') . '</th>';
        echo '<th>' . esc_html__('Estado', 'flavor-chat-ia') . '</th>';
        echo '<th>' . esc_html__('Acciones', 'flavor-chat-ia') . '</th>';
        echo '</tr></thead><tbody>';
        foreach ($cuotas as $cuota) {
            echo '<tr>';
            echo '<td>' . esc_html($cuota->id) . '</td>';
            echo '<td>' . esc_html($cuota->numero_socio) . '</td>';
            echo '<td>' . esc_html($cuota->periodo) . '</td>';
            echo '<td>' . esc_html(number_format((float) $cuota->importe, 2)) . '</td>';
            echo '<td>' . esc_html(date_i18n(get_option('date_format'), strtotime($cuota->fecha_cargo))) . '</td>';
            echo '<td>' . esc_html($cuota->estado) . '</td>';
            echo '<td>' . $this->render_cuota_actions($cuota->id, $cuota->estado) . '</td>';
            echo '</tr>';
        }
        echo '</tbody></table>';
        echo '</div>';
    }

    /**
     * Renderiza la página de altas y bajas
     */
    public function render_admin_altas_bajas() {
        echo '<div class="wrap flavor-modulo-page">';
        $this->render_page_header(__('Altas y Bajas de Socios', 'flavor-chat-ia'));
        $this->handle_admin_create_socio();
        $this->handle_admin_baja_socio();

        echo '<h3>' . esc_html__('Alta de socio', 'flavor-chat-ia') . '</h3>';
        echo '<form method="post">';
        wp_nonce_field('socios_alta', 'socios_alta_nonce');
        echo '<table class="form-table"><tbody>';
        echo '<tr><th>' . esc_html__('Usuario ID', 'flavor-chat-ia') . '</th><td><input type="number" name="usuario_id" min="1" required></td></tr>';
        echo '<tr><th>' . esc_html__('Número de socio', 'flavor-chat-ia') . '</th><td><input type="text" name="numero_socio" required></td></tr>';
        echo '<tr><th>' . esc_html__('Tipo', 'flavor-chat-ia') . '</th><td><select name="tipo_socio">';
        foreach ($this->get_setting('tipos_socio', []) as $key => $label) {
            echo '<option value="' . esc_attr($key) . '">' . esc_html($label) . '</option>';
        }
        echo '</select></td></tr>';
        echo '<tr><th>' . esc_html__('Cuota mensual', 'flavor-chat-ia') . '</th><td><input type="number" step="0.01" name="cuota_mensual" value="' . esc_attr($this->get_setting('cuota_mensual')) . '"></td></tr>';
        echo '<tr><th>' . esc_html__('Cuota reducida', 'flavor-chat-ia') . '</th><td><label><input type="checkbox" name="cuota_reducida" value="1"> ' . esc_html__('Aplicar cuota reducida', 'flavor-chat-ia') . '</label></td></tr>';
        echo '<tr><th>' . esc_html__('Notas', 'flavor-chat-ia') . '</th><td><textarea name="notas" rows="3" class="large-text"></textarea></td></tr>';
        echo '</tbody></table>';
        submit_button(__('Dar de alta', 'flavor-chat-ia'));
        echo '</form>';

        echo '<hr>';
        echo '<h3>' . esc_html__('Baja de socio', 'flavor-chat-ia') . '</h3>';
        echo '<form method="post">';
        wp_nonce_field('socios_baja', 'socios_baja_nonce');
        echo '<table class="form-table"><tbody>';
        echo '<tr><th>' . esc_html__('ID Socio', 'flavor-chat-ia') . '</th><td><input type="number" name="socio_id" min="1" required></td></tr>';
        echo '<tr><th>' . esc_html__('Motivo', 'flavor-chat-ia') . '</th><td><textarea name="motivo" rows="3" class="large-text"></textarea></td></tr>';
        echo '</tbody></table>';
        submit_button(__('Dar de baja', 'flavor-chat-ia'));
        echo '</form>';
        echo '</div>';
    }

    /**
     * Renderiza la configuración del módulo
     */
    public function render_admin_config() {
        echo '<div class="wrap flavor-modulo-page">';
        $this->render_page_header(__('Configuración de Socios', 'flavor-chat-ia'));
        $this->handle_admin_save_config();
        echo '<p>' . __('Configuración del sistema de gestión de socios.', 'flavor-chat-ia') . '</p>';

        $tipos = $this->get_setting('tipos_socio', []);
        $tipos_lineas = [];
        foreach ($tipos as $key => $label) {
            $tipos_lineas[] = $key . '|' . $label;
        }

        echo '<form method="post">';
        wp_nonce_field('socios_config', 'socios_config_nonce');
        echo '<table class="form-table"><tbody>';
        echo '<tr><th>' . esc_html__('Cuota mensual', 'flavor-chat-ia') . '</th><td><input type="number" step="0.01" name="cuota_mensual" value="' . esc_attr($this->get_setting('cuota_mensual')) . '"></td></tr>';
        echo '<tr><th>' . esc_html__('Cuota anual', 'flavor-chat-ia') . '</th><td><input type="number" step="0.01" name="cuota_anual" value="' . esc_attr($this->get_setting('cuota_anual')) . '"></td></tr>';
        echo '<tr><th>' . esc_html__('Día de cargo', 'flavor-chat-ia') . '</th><td><input type="number" name="dia_cargo" min="1" max="28" value="' . esc_attr($this->get_setting('dia_cargo')) . '"></td></tr>';
        echo '<tr><th>' . esc_html__('Permite cuota reducida', 'flavor-chat-ia') . '</th><td><label><input type="checkbox" name="permite_cuota_reducida" value="1" ' . checked($this->get_setting('permite_cuota_reducida'), true, false) . '> ' . esc_html__('Sí', 'flavor-chat-ia') . '</label></td></tr>';
        echo '<tr><th>' . esc_html__('Requiere validación de alta', 'flavor-chat-ia') . '</th><td><label><input type="checkbox" name="requiere_validacion_alta" value="1" ' . checked($this->get_setting('requiere_validacion_alta'), true, false) . '> ' . esc_html__('Sí', 'flavor-chat-ia') . '</label></td></tr>';
        echo '<tr><th>' . esc_html__('Tipos de socio', 'flavor-chat-ia') . '</th><td>';
        echo '<textarea name="tipos_socio" rows="5" class="large-text" placeholder="consumidor|Socio Consumidor">' . esc_textarea(implode("\n", $tipos_lineas)) . '</textarea>';
        echo '<p class="description">' . esc_html__('Un tipo por línea en formato clave|Etiqueta.', 'flavor-chat-ia') . '</p>';
        echo '</td></tr>';
        echo '</tbody></table>';
        submit_button(__('Guardar configuración', 'flavor-chat-ia'));
        echo '</form>';
        echo '</div>';
    }

    /**
     * Crea las tablas si no existen
     */
    public function maybe_create_tables() {
        global $wpdb;
        $tabla_socios = $wpdb->prefix . 'flavor_socios';

        if (!Flavor_Chat_Helpers::tabla_existe($tabla_socios)) {
            $this->create_tables();
        }
    }

    /**
     * Crea las tablas necesarias
     */
    private function create_tables() {
        global $wpdb;
        $charset_collate = $wpdb->get_charset_collate();

        $tabla_socios = $wpdb->prefix . 'flavor_socios';
        $tabla_cuotas = $wpdb->prefix . 'flavor_socios_cuotas';

        $sql_socios = "CREATE TABLE IF NOT EXISTS $tabla_socios (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            usuario_id bigint(20) unsigned NOT NULL,
            numero_socio varchar(50) NOT NULL,
            tipo_socio varchar(50) DEFAULT 'consumidor',
            fecha_alta date NOT NULL,
            fecha_baja date DEFAULT NULL,
            estado enum('activo','suspendido','baja') DEFAULT 'activo',
            cuota_mensual decimal(10,2) NOT NULL DEFAULT 30.00,
            cuota_reducida tinyint(1) DEFAULT 0,
            datos_bancarios text DEFAULT NULL,
            notas text DEFAULT NULL,
            fecha_creacion datetime DEFAULT CURRENT_TIMESTAMP,
            fecha_actualizacion datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            UNIQUE KEY usuario_id (usuario_id),
            UNIQUE KEY numero_socio (numero_socio),
            KEY tipo_socio (tipo_socio),
            KEY estado (estado)
        ) $charset_collate;";

        $sql_cuotas = "CREATE TABLE IF NOT EXISTS $tabla_cuotas (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            socio_id bigint(20) unsigned NOT NULL,
            periodo varchar(20) NOT NULL,
            importe decimal(10,2) NOT NULL,
            fecha_cargo date NOT NULL,
            fecha_pago date DEFAULT NULL,
            estado enum('pendiente','pagada','vencida','condonada') DEFAULT 'pendiente',
            metodo_pago varchar(50) DEFAULT NULL,
            referencia_pago varchar(100) DEFAULT NULL,
            notas text DEFAULT NULL,
            fecha_creacion datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            UNIQUE KEY socio_periodo (socio_id, periodo),
            KEY socio_id (socio_id),
            KEY estado (estado),
            KEY fecha_cargo (fecha_cargo)
        ) $charset_collate;";

        require_once ABSPATH . 'wp-admin/includes/upgrade.php';
        dbDelta($sql_socios);
        dbDelta($sql_cuotas);
    }

    /**
     * {@inheritdoc}
     */
    public function get_actions() {
        return [
            'mi_perfil_socio' => [
                'description' => 'Ver mi información como socio',
                'params' => [],
            ],
            'mis_cuotas' => [
                'description' => 'Ver estado de mis cuotas',
                'params' => ['estado', 'limite'],
            ],
            'pagar_cuota' => [
                'description' => 'Registrar pago de una cuota',
                'params' => ['cuota_id', 'metodo_pago', 'referencia'],
            ],
            'listar_socios' => [
                'description' => 'Listar socios (solo admin)',
                'params' => ['tipo', 'estado', 'limite'],
            ],
            'dar_alta_socio' => [
                'description' => 'Dar de alta un nuevo socio (solo admin)',
                'params' => ['usuario_id', 'tipo_socio', 'cuota_mensual'],
            ],
            'dar_baja_socio' => [
                'description' => 'Dar de baja a un socio (solo admin)',
                'params' => ['socio_id', 'motivo'],
            ],
            'estadisticas_socios' => [
                'description' => 'Obtener estadísticas de socios (solo admin)',
                'params' => [],
            ],
            'actualizar_datos' => [
                'description' => 'Actualizar datos personales del socio',
                'params' => ['telefono', 'direccion', 'iban', 'notas'],
            ],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function execute_action($action_name, $params) {
        $metodo_accion = 'action_' . $action_name;

        if (method_exists($this, $metodo_accion)) {
            return $this->$metodo_accion($params);
        }

        return [
            'success' => false,
            'error' => "Acción no implementada: {$action_name}",
        ];
    }

    /**
     * Acción: Ver mi perfil de socio
     */
    private function action_mi_perfil_socio($params) {
        $usuario_id = get_current_user_id();

        if (!$usuario_id) {
            return [
                'success' => false,
                'error' => __('Debes iniciar sesión para ver tu perfil.', 'flavor-chat-ia'),
            ];
        }

        global $wpdb;
        $tabla_socios = $wpdb->prefix . 'flavor_socios';

        $socio = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM $tabla_socios WHERE usuario_id = %d",
            $usuario_id
        ));

        if (!$socio) {
            return [
                'success' => false,
                'error' => __('No eres socio de la cooperativa.', 'flavor-chat-ia'),
            ];
        }

        $usuario = get_userdata($usuario_id);

        return [
            'success' => true,
            'socio' => [
                'numero' => $socio->numero_socio,
                'nombre' => $usuario->display_name,
                'email' => $usuario->user_email,
                'tipo' => $socio->tipo_socio,
                'fecha_alta' => date('d/m/Y', strtotime($socio->fecha_alta)),
                'estado' => $socio->estado,
                'cuota_mensual' => floatval($socio->cuota_mensual),
                'cuota_reducida' => (bool) $socio->cuota_reducida,
            ],
        ];
    }

    /**
     * Acción: Ver mis cuotas
     */
    private function action_mis_cuotas($params) {
        $usuario_id = get_current_user_id();

        if (!$usuario_id) {
            return [
                'success' => false,
                'error' => __('Debes iniciar sesión para ver tus cuotas.', 'flavor-chat-ia'),
            ];
        }

        global $wpdb;
        $tabla_socios = $wpdb->prefix . 'flavor_socios';
        $tabla_cuotas = $wpdb->prefix . 'flavor_socios_cuotas';

        $socio = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM $tabla_socios WHERE usuario_id = %d",
            $usuario_id
        ));

        if (!$socio) {
            return [
                'success' => false,
                'error' => __('No eres socio de la cooperativa.', 'flavor-chat-ia'),
            ];
        }

        $where = ['socio_id = %d'];
        $prepare_values = [$socio->id];

        if (!empty($params['estado'])) {
            $where[] = 'estado = %s';
            $prepare_values[] = $params['estado'];
        }

        $limite = absint($params['limite'] ?? 12);
        $sql_where = implode(' AND ', $where);

        $sql = "SELECT * FROM $tabla_cuotas WHERE $sql_where ORDER BY fecha_cargo DESC LIMIT %d";
        $prepare_values[] = $limite;

        $cuotas = $wpdb->get_results($wpdb->prepare($sql, ...$prepare_values));

        $cuotas_pendientes = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM $tabla_cuotas WHERE socio_id = %d AND estado = 'pendiente'",
            $socio->id
        ));

        $total_pendiente = $wpdb->get_var($wpdb->prepare(
            "SELECT IFNULL(SUM(importe), 0) FROM $tabla_cuotas WHERE socio_id = %d AND estado = 'pendiente'",
            $socio->id
        ));

        return [
            'success' => true,
            'resumen' => [
                'cuotas_pendientes' => $cuotas_pendientes,
                'total_pendiente' => floatval($total_pendiente),
            ],
            'cuotas' => array_map(function($c) {
                return [
                    'id' => $c->id,
                    'periodo' => $c->periodo,
                    'importe' => floatval($c->importe),
                    'fecha_cargo' => date('d/m/Y', strtotime($c->fecha_cargo)),
                    'estado' => $c->estado,
                    'fecha_pago' => $c->fecha_pago ? date('d/m/Y', strtotime($c->fecha_pago)) : null,
                ];
            }, $cuotas),
        ];
    }

    /**
     * Acción: Estadísticas de socios (solo admin)
     */
    private function action_estadisticas_socios($params) {
        if (!current_user_can('manage_options')) {
            return [
                'success' => false,
                'error' => __('No tienes permisos para ver las estadísticas.', 'flavor-chat-ia'),
            ];
        }

        global $wpdb;
        $tabla_socios = $wpdb->prefix . 'flavor_socios';
        $tabla_cuotas = $wpdb->prefix . 'flavor_socios_cuotas';

        $total_socios = $wpdb->get_var("SELECT COUNT(*) FROM $tabla_socios WHERE estado = 'activo'");
        $socios_por_tipo = $wpdb->get_results(
            "SELECT tipo_socio, COUNT(*) as total FROM $tabla_socios WHERE estado = 'activo' GROUP BY tipo_socio"
        );
        $cuotas_pendientes = $wpdb->get_var(
            "SELECT COUNT(*) FROM $tabla_cuotas WHERE estado = 'pendiente'"
        );
        $importe_pendiente = $wpdb->get_var(
            "SELECT IFNULL(SUM(importe), 0) FROM $tabla_cuotas WHERE estado = 'pendiente'"
        );

        return [
            'success' => true,
            'estadisticas' => [
                'total_socios' => $total_socios,
                'socios_por_tipo' => $socios_por_tipo,
                'cuotas_pendientes' => $cuotas_pendientes,
                'importe_pendiente' => floatval($importe_pendiente),
            ],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function get_tool_definitions() {
        return [
            [
                'name' => 'socios_mi_perfil',
                'description' => 'Ver mi información como socio de la cooperativa',
                'input_schema' => [
                    'type' => 'object',
                    'properties' => [],
                ],
            ],
            [
                'name' => 'socios_mis_cuotas',
                'description' => 'Ver el estado de mis cuotas de socio',
                'input_schema' => [
                    'type' => 'object',
                    'properties' => [
                        'estado' => [
                            'type' => 'string',
                            'description' => 'Filtrar por estado',
                            'enum' => ['pendiente', 'pagada', 'vencida'],
                        ],
                    ],
                ],
            ],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function get_knowledge_base() {
        return <<<KNOWLEDGE
**Sistema de Gestión de Socios**

Control completo de socios, cuotas y membresías de la cooperativa.

**Funcionalidades:**
- Registro de socios
- Gestión de cuotas mensuales/anuales
- Control de pagos
- Tipos de socio configurables
- Cuotas reducidas
- Estadísticas y reportes
- Altas y bajas

**Tipos de socio:**
- Consumidor: Socio que consume productos/servicios
- Trabajador: Socio que trabaja en la cooperativa
- Colaborador: Socio que colabora sin trabajar

**Gestión de cuotas:**
- Generación automática mensual
- Control de pagos y vencimientos
- Recordatorios automáticos
- Historial completo
KNOWLEDGE;
    }

    /**
     * {@inheritdoc}
     */
    public function get_faqs() {
        return [
            [
                'pregunta' => '¿Cómo pago mi cuota de socio?',
                'respuesta' => 'Puedes pagar desde la app, sección Socios, o mediante transferencia bancaria a la cuenta de la cooperativa.',
            ],
            [
                'pregunta' => '¿Qué pasa si no pago mi cuota?',
                'respuesta' => 'Tras varios meses impagados, tu estado de socio puede ser suspendido hasta regularizar la situación.',
            ],
        ];
    }

    /**
     * Configuración de formularios del módulo
     *
     * @param string $action_name Nombre de la acción
     * @return array Configuración del formulario
     */
    public function get_form_config($action_name) {
        $configs = [
            'dar_alta_socio' => [
                'title' => __('Hazte Socio', 'flavor-chat-ia'),
                'description' => __('Únete a nuestra comunidad y disfruta de todos los beneficios', 'flavor-chat-ia'),
                'fields' => [
                    'tipo_socio' => [
                        'type' => 'select',
                        'label' => __('Tipo de socio', 'flavor-chat-ia'),
                        'required' => true,
                        'options' => [
                            'consumidor' => __('Socio Consumidor - Acceso a grupo de consumo', 'flavor-chat-ia'),
                            'trabajador' => __('Socio Trabajador - Trabajas en la cooperativa', 'flavor-chat-ia'),
                            'colaborador' => __('Socio Colaborador - Apoyas el proyecto', 'flavor-chat-ia'),
                        ],
                        'default' => 'consumidor',
                    ],
                    'nombre_completo' => [
                        'type' => 'text',
                        'label' => __('Nombre completo', 'flavor-chat-ia'),
                        'required' => true,
                        'placeholder' => __('Tu nombre y apellidos', 'flavor-chat-ia'),
                    ],
                    'dni_nif' => [
                        'type' => 'text',
                        'label' => __('DNI/NIE/NIF', 'flavor-chat-ia'),
                        'required' => true,
                        'placeholder' => __('12345678X', 'flavor-chat-ia'),
                    ],
                    'email' => [
                        'type' => 'email',
                        'label' => __('Email', 'flavor-chat-ia'),
                        'required' => true,
                        'placeholder' => __('tu@email.com', 'flavor-chat-ia'),
                    ],
                    'telefono' => [
                        'type' => 'tel',
                        'label' => __('Teléfono', 'flavor-chat-ia'),
                        'required' => true,
                        'placeholder' => __('600123456', 'flavor-chat-ia'),
                    ],
                    'direccion' => [
                        'type' => 'textarea',
                        'label' => __('Dirección completa', 'flavor-chat-ia'),
                        'required' => true,
                        'rows' => 3,
                        'placeholder' => __('Calle, número, piso, ciudad, CP', 'flavor-chat-ia'),
                    ],
                    'iban' => [
                        'type' => 'text',
                        'label' => __('IBAN para domiciliación de cuotas', 'flavor-chat-ia'),
                        'required' => true,
                        'placeholder' => __('ES00 0000 0000 0000 0000 0000', 'flavor-chat-ia'),
                        'description' => __('Necesario para el pago automático de cuotas', 'flavor-chat-ia'),
                    ],
                    'cuota_reducida' => [
                        'type' => 'checkbox',
                        'label' => __('Solicitar cuota reducida', 'flavor-chat-ia'),
                        'checkbox_label' => __('Por situación económica, solicito cuota reducida (requiere justificación)', 'flavor-chat-ia'),
                    ],
                    'motivo_adhesion' => [
                        'type' => 'textarea',
                        'label' => __('¿Por qué quieres ser socio?', 'flavor-chat-ia'),
                        'rows' => 4,
                        'placeholder' => __('Cuéntanos tus motivaciones...', 'flavor-chat-ia'),
                    ],
                    'acepto_estatutos' => [
                        'type' => 'checkbox',
                        'label' => __('Acepto los estatutos', 'flavor-chat-ia'),
                        'checkbox_label' => __('He leído y acepto los estatutos de la cooperativa', 'flavor-chat-ia'),
                        'required' => true,
                    ],
                ],
                'submit_text' => __('Solicitar Alta como Socio', 'flavor-chat-ia'),
                'success_message' => __('¡Solicitud recibida! Te contactaremos en breve para completar el proceso.', 'flavor-chat-ia'),
                'redirect_url' => '/socios/bienvenida/',
            ],
            'pagar_cuota' => [
                'title' => __('Pagar Cuota', 'flavor-chat-ia'),
                'description' => __('Registra el pago de tu cuota de socio', 'flavor-chat-ia'),
                'fields' => [
                    'cuota_id' => [
                        'type' => 'hidden',
                        'required' => true,
                    ],
                    'metodo_pago' => [
                        'type' => 'select',
                        'label' => __('Método de pago', 'flavor-chat-ia'),
                        'required' => true,
                        'options' => [
                            'transferencia' => __('Transferencia bancaria', 'flavor-chat-ia'),
                            'efectivo' => __('Efectivo', 'flavor-chat-ia'),
                            'bizum' => __('Bizum', 'flavor-chat-ia'),
                            'domiciliacion' => __('Domiciliación bancaria', 'flavor-chat-ia'),
                        ],
                    ],
                    'referencia' => [
                        'type' => 'text',
                        'label' => __('Referencia de pago', 'flavor-chat-ia'),
                        'placeholder' => __('Nº de operación, recibo, etc.', 'flavor-chat-ia'),
                        'description' => __('Si el pago fue por transferencia o Bizum', 'flavor-chat-ia'),
                    ],
                    'fecha_pago' => [
                        'type' => 'date',
                        'label' => __('Fecha de pago', 'flavor-chat-ia'),
                        'required' => true,
                        'default' => date('Y-m-d'),
                    ],
                ],
                'submit_text' => __('Registrar Pago', 'flavor-chat-ia'),
                'success_message' => __('Pago registrado correctamente. Gracias!', 'flavor-chat-ia'),
            ],
            'actualizar_datos' => [
                'title' => __('Actualizar Mis Datos', 'flavor-chat-ia'),
                'description' => __('Mantén tu información actualizada', 'flavor-chat-ia'),
                'fields' => [
                    'telefono' => [
                        'type' => 'tel',
                        'label' => __('Teléfono', 'flavor-chat-ia'),
                        'placeholder' => __('600123456', 'flavor-chat-ia'),
                    ],
                    'direccion' => [
                        'type' => 'textarea',
                        'label' => __('Dirección', 'flavor-chat-ia'),
                        'rows' => 3,
                    ],
                    'iban' => [
                        'type' => 'text',
                        'label' => __('IBAN', 'flavor-chat-ia'),
                        'placeholder' => __('ES00 0000 0000 0000 0000 0000', 'flavor-chat-ia'),
                    ],
                    'notas' => [
                        'type' => 'textarea',
                        'label' => __('Notas o comentarios', 'flavor-chat-ia'),
                        'rows' => 3,
                        'placeholder' => __('Cualquier información relevante...', 'flavor-chat-ia'),
                    ],
                ],
                'submit_text' => __('Actualizar Datos', 'flavor-chat-ia'),
                'success_message' => __('Datos actualizados correctamente', 'flavor-chat-ia'),
            ],
        ];

        return $configs[$action_name] ?? [];
    }

    /**
     * Componentes web del módulo
     */
    public function get_web_components() {
        return [
            'hero' => [
                'label' => __('Hero Socios', 'flavor-chat-ia'),
                'description' => __('Sección hero con información de membresía', 'flavor-chat-ia'),
                'category' => 'hero',
                'icon' => 'dashicons-groups',
                'fields' => [
                    'titulo' => [
                        'type' => 'text',
                        'label' => __('Título', 'flavor-chat-ia'),
                        'default' => __('Hazte Socio', 'flavor-chat-ia'),
                    ],
                    'subtitulo' => [
                        'type' => 'textarea',
                        'label' => __('Subtítulo', 'flavor-chat-ia'),
                        'default' => __('Únete a nuestra comunidad y disfruta de todos los beneficios', 'flavor-chat-ia'),
                    ],
                    'mostrar_contador' => [
                        'type' => 'toggle',
                        'label' => __('Mostrar número de socios', 'flavor-chat-ia'),
                        'default' => true,
                    ],
                ],
                'template' => 'socios/hero',
            ],
            'beneficios' => [
                'label' => __('Beneficios de Socios', 'flavor-chat-ia'),
                'description' => __('Grid de ventajas de ser socio', 'flavor-chat-ia'),
                'category' => 'features',
                'icon' => 'dashicons-star-filled',
                'fields' => [
                    'titulo' => [
                        'type' => 'text',
                        'label' => __('Título', 'flavor-chat-ia'),
                        'default' => __('Beneficios', 'flavor-chat-ia'),
                    ],
                    'columnas' => [
                        'type' => 'select',
                        'label' => __('Columnas', 'flavor-chat-ia'),
                        'options' => [2, 3, 4],
                        'default' => 3,
                    ],
                ],
                'template' => 'socios/beneficios',
            ],
            'tipos_membresia' => [
                'label' => __('Tipos de Membresía', 'flavor-chat-ia'),
                'description' => __('Planes y cuotas disponibles', 'flavor-chat-ia'),
                'category' => 'content',
                'icon' => 'dashicons-id-alt',
                'fields' => [
                    'titulo' => [
                        'type' => 'text',
                        'label' => __('Título', 'flavor-chat-ia'),
                        'default' => __('Tipos de Membresía', 'flavor-chat-ia'),
                    ],
                    'mostrar_precios' => [
                        'type' => 'toggle',
                        'label' => __('Mostrar precios', 'flavor-chat-ia'),
                        'default' => true,
                    ],
                ],
                'template' => 'socios/tipos-membresia',
            ],
            'formulario_alta' => [
                'label' => __('Formulario de Alta', 'flavor-chat-ia'),
                'description' => __('Formulario para hacerse socio', 'flavor-chat-ia'),
                'category' => 'forms',
                'icon' => 'dashicons-edit',
                'fields' => [
                    'titulo' => [
                        'type' => 'text',
                        'label' => __('Título', 'flavor-chat-ia'),
                        'default' => __('Únete Ahora', 'flavor-chat-ia'),
                    ],
                    'campos_extra' => [
                        'type' => 'toggle',
                        'label' => __('Mostrar campos adicionales', 'flavor-chat-ia'),
                        'default' => false,
                    ],
                ],
                'template' => 'socios/formulario-alta',
            ],
            'testimonios' => [
                'label' => __('Testimonios de Socios', 'flavor-chat-ia'),
                'description' => __('Opiniones de socios actuales', 'flavor-chat-ia'),
                'category' => 'testimonials',
                'icon' => 'dashicons-format-quote',
                'fields' => [
                    'titulo' => [
                        'type' => 'text',
                        'label' => __('Título', 'flavor-chat-ia'),
                        'default' => __('Lo que dicen nuestros socios', 'flavor-chat-ia'),
                    ],
                    'limite' => [
                        'type' => 'number',
                        'label' => __('Número máximo', 'flavor-chat-ia'),
                        'default' => 3,
                    ],
                ],
                'template' => 'socios/testimonios',
            ],
        ];
    }

    /**
     * Accion: Actualizar datos del socio
     */
    private function action_actualizar_datos($params) {
        $usuario_id = get_current_user_id();

        if (!$usuario_id) {
            return [
                'success' => false,
                'error' => __('Debes iniciar sesión para actualizar tus datos.', 'flavor-chat-ia'),
            ];
        }

        global $wpdb;
        $tabla_socios = $wpdb->prefix . 'flavor_socios';

        $socio = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM $tabla_socios WHERE usuario_id = %d AND estado = 'activo'",
            $usuario_id
        ));

        if (!$socio) {
            return [
                'success' => false,
                'error' => __('No eres socio activo de la cooperativa.', 'flavor-chat-ia'),
            ];
        }

        $datos_actualizar = [];
        $campos_permitidos = ['telefono', 'direccion', 'iban'];

        foreach ($campos_permitidos as $campo) {
            if (isset($params[$campo]) && $params[$campo] !== '') {
                $datos_actualizar[$campo] = sanitize_text_field($params[$campo]);
            }
        }

        if (empty($datos_actualizar)) {
            return [
                'success' => false,
                'error' => __('No hay datos para actualizar.', 'flavor-chat-ia'),
            ];
        }

        $resultado = $wpdb->update(
            $tabla_socios,
            $datos_actualizar,
            ['id' => $socio->id]
        );

        if ($resultado === false) {
            return [
                'success' => false,
                'error' => __('Error al actualizar los datos.', 'flavor-chat-ia'),
            ];
        }

        return [
            'success' => true,
            'mensaje' => __('Datos actualizados correctamente.', 'flavor-chat-ia'),
        ];
    }

    private function handle_admin_actions() {
        if (empty($_GET['socio_action']) || empty($_GET['socio_id'])) {
            return;
        }

        $action = sanitize_text_field($_GET['socio_action']);
        $socio_id = absint($_GET['socio_id']);
        $nonce = $_GET['_wpnonce'] ?? '';

        if (!wp_verify_nonce($nonce, 'socios_estado_' . $socio_id)) {
            echo '<div class="notice notice-error"><p>' . esc_html__('Nonce inválido.', 'flavor-chat-ia') . '</p></div>';
            return;
        }

        if (!in_array($action, ['activo', 'suspendido', 'baja'], true)) {
            return;
        }

        global $wpdb;
        $tabla_socios = $wpdb->prefix . 'flavor_socios';
        $data = ['estado' => $action];
        if ($action === 'baja') {
            $data['fecha_baja'] = date('Y-m-d');
        }
        $wpdb->update($tabla_socios, $data, ['id' => $socio_id]);
        echo '<div class="notice notice-success"><p>' . esc_html__('Estado actualizado.', 'flavor-chat-ia') . '</p></div>';
    }

    private function render_estado_actions($socio_id, $estado_actual, $page) {
        $acciones = [];
        foreach (['activo' => __('Activo', 'flavor-chat-ia'), 'suspendido' => __('Suspendido', 'flavor-chat-ia'), 'baja' => __('Baja', 'flavor-chat-ia')] as $key => $label) {
            if ($key === $estado_actual) {
                continue;
            }
            $url = wp_nonce_url(
                add_query_arg([
                    'page' => $page,
                    'socio_action' => $key,
                    'socio_id' => $socio_id,
                ], admin_url('admin.php')),
                'socios_estado_' . $socio_id
            );
            $acciones[] = '<a href="' . esc_url($url) . '">' . esc_html($label) . '</a>';
        }
        return implode(' | ', $acciones);
    }

    private function render_cuota_actions($cuota_id, $estado_actual) {
        $acciones = [];
        foreach (['pagada' => __('Marcar pagada', 'flavor-chat-ia'), 'vencida' => __('Marcar vencida', 'flavor-chat-ia'), 'condonada' => __('Condonar', 'flavor-chat-ia')] as $key => $label) {
            if ($key === $estado_actual) {
                continue;
            }
            $url = wp_nonce_url(
                add_query_arg([
                    'page' => 'socios-cuotas',
                    'cuota_action' => $key,
                    'cuota_id' => $cuota_id,
                ], admin_url('admin.php')),
                'socios_cuota_' . $cuota_id
            );
            $acciones[] = '<a href="' . esc_url($url) . '">' . esc_html($label) . '</a>';
        }
        return implode(' | ', $acciones);
    }

    private function handle_admin_create_socio() {
        if (empty($_POST['socios_alta_nonce'])) {
            return;
        }
        if (!wp_verify_nonce($_POST['socios_alta_nonce'], 'socios_alta')) {
            echo '<div class="notice notice-error"><p>' . esc_html__('Nonce inválido.', 'flavor-chat-ia') . '</p></div>';
            return;
        }

        $usuario_id = absint($_POST['usuario_id'] ?? 0);
        $numero_socio = sanitize_text_field($_POST['numero_socio'] ?? '');
        $tipo_socio = sanitize_text_field($_POST['tipo_socio'] ?? 'consumidor');
        $cuota_mensual = floatval($_POST['cuota_mensual'] ?? $this->get_setting('cuota_mensual'));
        $cuota_reducida = !empty($_POST['cuota_reducida']) ? 1 : 0;
        $notas = sanitize_textarea_field($_POST['notas'] ?? '');

        if (!$usuario_id || !$numero_socio) {
            echo '<div class="notice notice-error"><p>' . esc_html__('Usuario y número de socio son obligatorios.', 'flavor-chat-ia') . '</p></div>';
            return;
        }

        global $wpdb;
        $tabla_socios = $wpdb->prefix . 'flavor_socios';
        $exists = $wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM $tabla_socios WHERE usuario_id = %d", $usuario_id));
        if ($exists) {
            echo '<div class="notice notice-error"><p>' . esc_html__('El usuario ya es socio.', 'flavor-chat-ia') . '</p></div>';
            return;
        }

        $wpdb->insert($tabla_socios, [
            'usuario_id' => $usuario_id,
            'numero_socio' => $numero_socio,
            'tipo_socio' => $tipo_socio,
            'fecha_alta' => date('Y-m-d'),
            'estado' => 'activo',
            'cuota_mensual' => $cuota_mensual,
            'cuota_reducida' => $cuota_reducida,
            'notas' => $notas,
        ]);

        echo '<div class="notice notice-success"><p>' . esc_html__('Socio creado.', 'flavor-chat-ia') . '</p></div>';
    }

    private function handle_admin_baja_socio() {
        if (empty($_POST['socios_baja_nonce'])) {
            return;
        }
        if (!wp_verify_nonce($_POST['socios_baja_nonce'], 'socios_baja')) {
            echo '<div class="notice notice-error"><p>' . esc_html__('Nonce inválido.', 'flavor-chat-ia') . '</p></div>';
            return;
        }

        $socio_id = absint($_POST['socio_id'] ?? 0);
        if (!$socio_id) {
            return;
        }

        global $wpdb;
        $tabla_socios = $wpdb->prefix . 'flavor_socios';
        $wpdb->update($tabla_socios, [
            'estado' => 'baja',
            'fecha_baja' => date('Y-m-d'),
            'notas' => sanitize_textarea_field($_POST['motivo'] ?? ''),
        ], ['id' => $socio_id]);

        echo '<div class="notice notice-success"><p>' . esc_html__('Socio dado de baja.', 'flavor-chat-ia') . '</p></div>';
    }

    private function handle_admin_save_config() {
        if (empty($_POST['socios_config_nonce'])) {
            return;
        }
        if (!wp_verify_nonce($_POST['socios_config_nonce'], 'socios_config')) {
            echo '<div class="notice notice-error"><p>' . esc_html__('Nonce inválido.', 'flavor-chat-ia') . '</p></div>';
            return;
        }

        $this->update_setting('cuota_mensual', floatval($_POST['cuota_mensual'] ?? 30));
        $this->update_setting('cuota_anual', floatval($_POST['cuota_anual'] ?? 300));
        $this->update_setting('dia_cargo', absint($_POST['dia_cargo'] ?? 1));
        $this->update_setting('permite_cuota_reducida', !empty($_POST['permite_cuota_reducida']));
        $this->update_setting('requiere_validacion_alta', !empty($_POST['requiere_validacion_alta']));

        $tipos_raw = sanitize_textarea_field($_POST['tipos_socio'] ?? '');
        $tipos = [];
        foreach (array_filter(array_map('trim', explode("\n", $tipos_raw))) as $linea) {
            $parts = array_map('trim', explode('|', $linea, 2));
            if (!empty($parts[0])) {
                $tipos[$parts[0]] = $parts[1] ?? $parts[0];
            }
        }
        if ($tipos) {
            $this->update_setting('tipos_socio', $tipos);
        }

        echo '<div class="notice notice-success"><p>' . esc_html__('Configuración guardada.', 'flavor-chat-ia') . '</p></div>';
    }

    private function handle_admin_cuota_action() {
        if (empty($_GET['cuota_action']) || empty($_GET['cuota_id'])) {
            return;
        }

        $action = sanitize_text_field($_GET['cuota_action']);
        $cuota_id = absint($_GET['cuota_id']);
        $nonce = $_GET['_wpnonce'] ?? '';

        if (!wp_verify_nonce($nonce, 'socios_cuota_' . $cuota_id)) {
            echo '<div class="notice notice-error"><p>' . esc_html__('Nonce inválido.', 'flavor-chat-ia') . '</p></div>';
            return;
        }

        if (!in_array($action, ['pagada', 'vencida', 'condonada'], true)) {
            return;
        }

        global $wpdb;
        $tabla_cuotas = $wpdb->prefix . 'flavor_socios_cuotas';
        $data = ['estado' => $action];
        if ($action === 'pagada') {
            $data['fecha_pago'] = date('Y-m-d');
        }
        $wpdb->update($tabla_cuotas, $data, ['id' => $cuota_id]);
        echo '<div class="notice notice-success"><p>' . esc_html__('Cuota actualizada.', 'flavor-chat-ia') . '</p></div>';
    }
    /**
     * Crea/actualiza páginas del módulo si es necesario
     */
    public function maybe_create_pages() {
        if (!class_exists('Flavor_Page_Creator')) {
            return;
        }

        // En admin: refrescar páginas del módulo
        if (is_admin()) {
            Flavor_Page_Creator::refresh_module_pages('socios');
            return;
        }

        // En frontend: crear páginas si no existen (solo una vez)
        $pagina = get_page_by_path('socios');
        if (!$pagina && !get_option('flavor_socios_pages_created')) {
            Flavor_Page_Creator::create_pages_for_modules(['socios']);
            update_option('flavor_socios_pages_created', 1, false);
        }
    }

    /**
     * Define las páginas del módulo (Page Creator V3)
     *
     * @return array Definiciones de páginas
     */
    public function get_pages_definition() {
        return [
            [
                'title' => __('Socios', 'flavor-chat-ia'),
                'slug' => 'socios',
                'content' => '<h1>' . __('Gestión de Socios', 'flavor-chat-ia') . '</h1>
<p>' . __('Bienvenido al área de socios. Aquí podrás gestionar tu membresía, consultar cuotas y actualizar tus datos.', 'flavor-chat-ia') . '</p>

[flavor_module_listing module="socios" action="listar" columnas="3" limite="12"]',
                'parent' => 0,
            ],
            [
                'title' => __('Unirse', 'flavor-chat-ia'),
                'slug' => 'unirse',
                'content' => '<h1>' . __('Hazte Socio', 'flavor-chat-ia') . '</h1>
<p>' . __('Únete a nuestra comunidad y disfruta de todos los beneficios de ser socio.', 'flavor-chat-ia') . '</p>

[flavor_module_form module="socios" action="dar_alta_socio"]',
                'parent' => 'socios',
            ],
            [
                'title' => __('Mi Perfil', 'flavor-chat-ia'),
                'slug' => 'mi-perfil',
                'content' => '<h1>' . __('Mi Perfil de Socio', 'flavor-chat-ia') . '</h1>
<p>' . __('Consulta y actualiza tu información personal y datos de contacto.', 'flavor-chat-ia') . '</p>

[flavor_module_listing module="socios" action="mi_perfil_socio" columnas="1" limite="1"]

[flavor_module_form module="socios" action="actualizar_datos"]',
                'parent' => 'socios',
            ],
            [
                'title' => __('Pagar Cuota', 'flavor-chat-ia'),
                'slug' => 'pagar-cuota',
                'content' => '<h1>' . __('Pagar Cuota de Socio', 'flavor-chat-ia') . '</h1>
<p>' . __('Gestiona el pago de tus cuotas de membresía.', 'flavor-chat-ia') . '</p>

[flavor_module_listing module="socios" action="mis_cuotas" columnas="1" limite="12"]

[flavor_module_form module="socios" action="pagar_cuota"]',
                'parent' => 'socios',
            ],
        ];
    }

}

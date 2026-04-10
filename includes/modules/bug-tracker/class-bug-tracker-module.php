<?php
/**
 * Clase principal del módulo Bug Tracker
 *
 * @package Flavor_Platform
 * @subpackage Bug_Tracker
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Módulo principal de Bug Tracker
 *
 * Gestiona la captura automática de errores PHP, reportes manuales
 * y notificaciones multicanal.
 */
class Flavor_Bug_Tracker_Module extends Flavor_Chat_Module_Base {

    use Flavor_Module_Admin_Pages_Trait;
    use Flavor_Module_Notifications_Trait;
    use Flavor_Module_Admin_UI_Trait;

    /**
     * ID único del módulo
     *
     * @var string
     */
    protected $id = 'bug-tracker';

    /**
     * Nombre del módulo
     *
     * @var string
     */
    protected $name = 'Bug Tracker';

    /**
     * Descripción del módulo
     *
     * @var string
     */
    protected $description = 'Sistema de captura y notificación de errores';

    /**
     * Icono del módulo
     *
     * @var string
     */
    protected $icon = 'dashicons-warning';

    /**
     * Color del módulo
     *
     * @var string
     */
    protected $color = '#dc2626';

    /**
     * Instancia del error handler
     *
     * @var Flavor_Bug_Tracker_Error_Handler
     */
    private $error_handler;

    /**
     * Instancia del gestor de canales
     *
     * @var Flavor_Bug_Tracker_Channels
     */
    private $channels;

    /**
     * Instancia de la API
     *
     * @var Flavor_Bug_Tracker_API
     */
    private $api;

    /**
     * Tabla de reportes de bugs
     *
     * @var string
     */
    private $tabla_bugs;

    /**
     * Tabla de canales
     *
     * @var string
     */
    private $tabla_channels;

    /**
     * Constructor
     */
    public function __construct() {
        global $wpdb;

        $this->module_role = 'transversal';
        $this->gailu_contribuye_a = ['resiliencia'];

        $this->tabla_bugs = $wpdb->prefix . 'flavor_bug_reports';
        $this->tabla_channels = $wpdb->prefix . 'flavor_bug_channels';

        parent::__construct();
    }

    /**
     * Verifica si el módulo puede activarse
     *
     * @return bool
     */
    public function can_activate() {
        global $wpdb;
        return Flavor_Platform_Helpers::tabla_existe($this->tabla_bugs);
    }

    /**
     * Obtiene el mensaje de error de activación
     *
     * @return string
     */
    public function get_activation_error() {
        if (!$this->can_activate()) {
            return __('Las tablas de Bug Tracker no están creadas. Ejecuta la instalación del módulo.', 'flavor-platform');
        }
        return '';
    }

    /**
     * Obtiene las dependencias del módulo
     *
     * @return array
     */
    public function get_dependencies() {
        return [];
    }

    /**
     * Configuración por defecto del módulo
     *
     * @return array
     */
    protected function get_default_settings() {
        return [
            'captura_automatica' => true,
            'capturar_warnings' => false,
            'capturar_notices' => false,
            'capturar_deprecations' => false,
            'plugins_monitorizados' => ['flavor-platform', 'flavor-landing', 'flavor-license-server'],
            'notificar_admins_inapp' => true,
            'agrupar_duplicados' => true,
            'limite_notificaciones_hora' => 10,
            'limpiar_resueltos_dias' => 30,
        ];
    }

    /**
     * Inicializa el módulo
     *
     * @return void
     */
    public function init() {
        // Asegurar que las tablas existen
        add_action('init', [$this, 'maybe_create_tables'], 1);

        // Inicializar componentes
        add_action('init', [$this, 'init_components'], 5);

        // Registrar menú admin
        add_action('admin_menu', [$this, 'registrar_menu_admin'], 99);

        // Registrar API REST
        add_action('rest_api_init', [$this, 'registrar_api_rest']);

        // Hook para limpiar bugs antiguos
        add_action('flavor_bug_tracker_cleanup', [$this, 'limpiar_bugs_antiguos']);

        // Programar limpieza si no existe
        if (!wp_next_scheduled('flavor_bug_tracker_cleanup')) {
            wp_schedule_event(time(), 'daily', 'flavor_bug_tracker_cleanup');
        }

        // Registrar función global
        $this->registrar_funcion_global();
    }

    /**
     * Crea las tablas si no existen
     *
     * @return void
     */
    public function maybe_create_tables() {
        $version_instalada = get_option('flavor_bug_tracker_db_version');
        if ($version_instalada === false) {
            require_once dirname(__FILE__) . '/install.php';
            flavor_bug_tracker_crear_tablas();
        }
    }

    /**
     * Inicializa los componentes del módulo
     *
     * @return void
     */
    public function init_components() {
        // Inicializar gestor de canales
        if (class_exists('Flavor_Bug_Tracker_Channels')) {
            $this->channels = new Flavor_Bug_Tracker_Channels($this);
        }

        // Inicializar error handler si está habilitada la captura automática
        if ($this->get_setting('captura_automatica') && class_exists('Flavor_Bug_Tracker_Error_Handler')) {
            $this->error_handler = new Flavor_Bug_Tracker_Error_Handler($this);
        }

        // Inicializar API
        if (class_exists('Flavor_Bug_Tracker_API')) {
            $this->api = new Flavor_Bug_Tracker_API($this);
        }
    }

    /**
     * Registra la función global flavor_report_bug
     *
     * @return void
     */
    private function registrar_funcion_global() {
        if (!function_exists('flavor_report_bug')) {
            $modulo = $this;

            /**
             * Función global para reportar bugs manualmente
             *
             * @param string $titulo Título del bug
             * @param string $descripcion Descripción del bug
             * @param array  $contexto Contexto adicional (severidad, modulo_id, etc.)
             * @return int|false ID del bug creado o false si falla
             */
            function flavor_report_bug($titulo, $descripcion, $contexto = []) {
                $modulo = Flavor_Platform_Module_Loader::get_instance()->get_module('bug-tracker');
                if (!$modulo) {
                    return false;
                }
                return $modulo->reportar_bug_manual($titulo, $descripcion, $contexto);
            }
        }
    }

    /**
     * Reporta un bug manualmente
     *
     * @param string $titulo Título del bug
     * @param string $descripcion Descripción del bug
     * @param array  $contexto Contexto adicional
     * @return int|false ID del bug creado o false si falla
     */
    public function reportar_bug_manual($titulo, $descripcion, $contexto = []) {
        $contexto_default = [
            'severidad' => 'medium',
            'modulo_id' => null,
            'archivo' => null,
            'linea' => null,
            'stack_trace' => null,
            'contexto_extra' => [],
        ];

        $contexto = wp_parse_args($contexto, $contexto_default);

        // Generar fingerprint basado en título y descripción
        $hash_fingerprint = $this->generar_fingerprint('manual', $titulo, $descripcion, '');

        return $this->registrar_bug([
            'tipo' => 'manual',
            'severidad' => $contexto['severidad'],
            'titulo' => $titulo,
            'mensaje' => $descripcion,
            'archivo' => $contexto['archivo'],
            'linea' => $contexto['linea'],
            'modulo_id' => $contexto['modulo_id'],
            'stack_trace' => $contexto['stack_trace'],
            'hash_fingerprint' => $hash_fingerprint,
            'contexto_extra' => $contexto['contexto_extra'],
        ]);
    }

    /**
     * Registra un bug en la base de datos
     *
     * @param array $datos Datos del bug
     * @return int|false ID del bug creado o false si falla
     */
    public function registrar_bug($datos) {
        global $wpdb;

        $datos_default = [
            'tipo' => 'error_php',
            'severidad' => 'medium',
            'titulo' => '',
            'mensaje' => '',
            'stack_trace' => null,
            'archivo' => null,
            'linea' => null,
            'modulo_id' => null,
            'hash_fingerprint' => '',
            'contexto_request' => null,
            'contexto_servidor' => null,
            'contexto_usuario' => null,
            'contexto_extra' => null,
        ];

        $datos = wp_parse_args($datos, $datos_default);

        // Verificar si ya existe un bug con el mismo fingerprint
        if ($this->get_setting('agrupar_duplicados') && !empty($datos['hash_fingerprint'])) {
            $bug_existente = $wpdb->get_row($wpdb->prepare(
                "SELECT id, ocurrencias FROM {$this->tabla_bugs} WHERE hash_fingerprint = %s AND estado != 'resuelto'",
                $datos['hash_fingerprint']
            ));

            if ($bug_existente) {
                // Incrementar contador de ocurrencias
                $wpdb->update(
                    $this->tabla_bugs,
                    [
                        'ocurrencias' => $bug_existente->ocurrencias + 1,
                        'ultima_ocurrencia' => current_time('mysql'),
                        'estado' => 'abierto',
                    ],
                    ['id' => $bug_existente->id]
                );

                return $bug_existente->id;
            }
        }

        // Generar código único
        $codigo = $this->generar_codigo_bug();

        // Preparar contextos como JSON
        $contexto_request = $this->capturar_contexto_request();
        $contexto_servidor = $this->capturar_contexto_servidor();
        $contexto_usuario = $this->capturar_contexto_usuario();

        // Insertar nuevo bug
        $resultado = $wpdb->insert(
            $this->tabla_bugs,
            [
                'codigo' => $codigo,
                'tipo' => $datos['tipo'],
                'severidad' => $datos['severidad'],
                'titulo' => mb_substr($datos['titulo'], 0, 500),
                'mensaje' => $datos['mensaje'],
                'stack_trace' => $datos['stack_trace'],
                'archivo' => $datos['archivo'],
                'linea' => $datos['linea'],
                'modulo_id' => $datos['modulo_id'],
                'hash_fingerprint' => $datos['hash_fingerprint'],
                'ocurrencias' => 1,
                'primera_ocurrencia' => current_time('mysql'),
                'ultima_ocurrencia' => current_time('mysql'),
                'estado' => 'nuevo',
                'contexto_request' => wp_json_encode($contexto_request),
                'contexto_servidor' => wp_json_encode($contexto_servidor),
                'contexto_usuario' => wp_json_encode($contexto_usuario),
                'contexto_extra' => !empty($datos['contexto_extra']) ? wp_json_encode($datos['contexto_extra']) : null,
            ]
        );

        if ($resultado === false) {
            return false;
        }

        $bug_id = $wpdb->insert_id;

        // Disparar acción para notificaciones
        $bug_completo = $this->obtener_bug($bug_id);
        do_action('flavor_bug_reported', $bug_completo, $this);

        // Enviar notificaciones por canales configurados
        if ($this->channels) {
            $this->channels->notificar_bug($bug_completo);
        }

        // Notificar admins in-app si está habilitado
        if ($this->get_setting('notificar_admins_inapp')) {
            $this->notificar_admins('bug_reportado', [
                'bug_id' => $bug_id,
                'codigo' => $codigo,
                'titulo' => $datos['titulo'],
                'severidad' => $datos['severidad'],
                'tipo' => $datos['tipo'],
            ]);
        }

        return $bug_id;
    }

    /**
     * Obtiene un bug por ID
     *
     * @param int $bug_id ID del bug
     * @return object|null
     */
    public function obtener_bug($bug_id) {
        global $wpdb;

        $bug = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$this->tabla_bugs} WHERE id = %d",
            $bug_id
        ));

        if ($bug) {
            // Decodificar campos JSON
            $bug->contexto_request = json_decode($bug->contexto_request, true);
            $bug->contexto_servidor = json_decode($bug->contexto_servidor, true);
            $bug->contexto_usuario = json_decode($bug->contexto_usuario, true);
            $bug->contexto_extra = json_decode($bug->contexto_extra, true);
        }

        return $bug;
    }

    /**
     * Lista bugs con filtros
     *
     * @param array $args Argumentos de filtrado
     * @return array
     */
    public function listar_bugs($args = []) {
        global $wpdb;

        $defaults = [
            'estado' => null,
            'severidad' => null,
            'tipo' => null,
            'modulo_id' => null,
            'busqueda' => null,
            'orderby' => 'ultima_ocurrencia',
            'order' => 'DESC',
            'limit' => 20,
            'offset' => 0,
        ];

        $args = wp_parse_args($args, $defaults);

        $where_condiciones = ['1=1'];
        $where_valores = [];

        if ($args['estado']) {
            $where_condiciones[] = 'estado = %s';
            $where_valores[] = $args['estado'];
        }

        if ($args['severidad']) {
            $where_condiciones[] = 'severidad = %s';
            $where_valores[] = $args['severidad'];
        }

        if ($args['tipo']) {
            $where_condiciones[] = 'tipo = %s';
            $where_valores[] = $args['tipo'];
        }

        if ($args['modulo_id']) {
            $where_condiciones[] = 'modulo_id = %s';
            $where_valores[] = $args['modulo_id'];
        }

        if ($args['busqueda']) {
            $where_condiciones[] = '(titulo LIKE %s OR mensaje LIKE %s OR codigo LIKE %s)';
            $busqueda_like = '%' . $wpdb->esc_like($args['busqueda']) . '%';
            $where_valores[] = $busqueda_like;
            $where_valores[] = $busqueda_like;
            $where_valores[] = $busqueda_like;
        }

        $where_sql = implode(' AND ', $where_condiciones);

        // Validar orderby
        $columnas_permitidas = ['id', 'codigo', 'severidad', 'tipo', 'estado', 'ocurrencias', 'ultima_ocurrencia', 'created_at'];
        $orderby = in_array($args['orderby'], $columnas_permitidas) ? $args['orderby'] : 'ultima_ocurrencia';
        $order = strtoupper($args['order']) === 'ASC' ? 'ASC' : 'DESC';

        $query = "SELECT * FROM {$this->tabla_bugs} WHERE {$where_sql} ORDER BY {$orderby} {$order} LIMIT %d OFFSET %d";
        $where_valores[] = $args['limit'];
        $where_valores[] = $args['offset'];

        $bugs = $wpdb->get_results($wpdb->prepare($query, $where_valores));

        // Contar total
        $count_query = "SELECT COUNT(*) FROM {$this->tabla_bugs} WHERE {$where_sql}";
        $count_valores = array_slice($where_valores, 0, -2);
        $total = $wpdb->get_var(
            !empty($count_valores) ? $wpdb->prepare($count_query, $count_valores) : $count_query
        );

        return [
            'bugs' => $bugs,
            'total' => (int) $total,
            'paginas' => ceil($total / $args['limit']),
        ];
    }

    /**
     * Actualiza el estado de un bug
     *
     * @param int    $bug_id ID del bug
     * @param string $estado Nuevo estado
     * @param string $notas Notas opcionales
     * @return bool
     */
    public function actualizar_estado_bug($bug_id, $estado, $notas = '') {
        global $wpdb;

        $estados_validos = ['nuevo', 'abierto', 'resuelto', 'ignorado'];
        if (!in_array($estado, $estados_validos)) {
            return false;
        }

        $datos_actualizar = [
            'estado' => $estado,
        ];

        if ($estado === 'resuelto') {
            $datos_actualizar['resuelto_por'] = get_current_user_id();
            $datos_actualizar['resuelto_at'] = current_time('mysql');
            do_action('flavor_bug_resolved', $bug_id, get_current_user_id());
        }

        if ($estado === 'ignorado') {
            do_action('flavor_bug_ignored', $bug_id, get_current_user_id());
        }

        if (!empty($notas)) {
            $bug_actual = $this->obtener_bug($bug_id);
            $notas_anteriores = $bug_actual ? $bug_actual->notas : '';
            $nueva_nota = sprintf(
                "[%s - %s] %s",
                current_time('Y-m-d H:i'),
                wp_get_current_user()->display_name,
                $notas
            );
            $datos_actualizar['notas'] = $notas_anteriores ? $notas_anteriores . "\n\n" . $nueva_nota : $nueva_nota;
        }

        return $wpdb->update($this->tabla_bugs, $datos_actualizar, ['id' => $bug_id]) !== false;
    }

    /**
     * Obtiene estadísticas de bugs
     *
     * @return array
     */
    public function obtener_estadisticas() {
        global $wpdb;

        $estadisticas = [
            'total' => 0,
            'por_estado' => [],
            'por_severidad' => [],
            'por_tipo' => [],
            'ultimas_24h' => 0,
            'ultima_semana' => 0,
        ];

        // Total
        $estadisticas['total'] = (int) $wpdb->get_var("SELECT COUNT(*) FROM {$this->tabla_bugs}");

        // Por estado
        $resultados_estado = $wpdb->get_results(
            "SELECT estado, COUNT(*) as cantidad FROM {$this->tabla_bugs} GROUP BY estado"
        );
        foreach ($resultados_estado as $fila) {
            $estadisticas['por_estado'][$fila->estado] = (int) $fila->cantidad;
        }

        // Por severidad
        $resultados_severidad = $wpdb->get_results(
            "SELECT severidad, COUNT(*) as cantidad FROM {$this->tabla_bugs} GROUP BY severidad"
        );
        foreach ($resultados_severidad as $fila) {
            $estadisticas['por_severidad'][$fila->severidad] = (int) $fila->cantidad;
        }

        // Por tipo
        $resultados_tipo = $wpdb->get_results(
            "SELECT tipo, COUNT(*) as cantidad FROM {$this->tabla_bugs} GROUP BY tipo"
        );
        foreach ($resultados_tipo as $fila) {
            $estadisticas['por_tipo'][$fila->tipo] = (int) $fila->cantidad;
        }

        // Últimas 24 horas
        $estadisticas['ultimas_24h'] = (int) $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM {$this->tabla_bugs} WHERE created_at >= %s",
            gmdate('Y-m-d H:i:s', strtotime('-24 hours'))
        ));

        // Última semana
        $estadisticas['ultima_semana'] = (int) $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM {$this->tabla_bugs} WHERE created_at >= %s",
            gmdate('Y-m-d H:i:s', strtotime('-7 days'))
        ));

        return $estadisticas;
    }

    /**
     * Genera un código único para el bug
     *
     * @return string
     */
    private function generar_codigo_bug() {
        global $wpdb;

        $anio = gmdate('Y');
        $prefijo = "BUG-{$anio}-";

        // Obtener el último número del año actual
        $ultimo_codigo = $wpdb->get_var($wpdb->prepare(
            "SELECT codigo FROM {$this->tabla_bugs} WHERE codigo LIKE %s ORDER BY id DESC LIMIT 1",
            $prefijo . '%'
        ));

        if ($ultimo_codigo) {
            $numero = (int) substr($ultimo_codigo, strlen($prefijo)) + 1;
        } else {
            $numero = 1;
        }

        return $prefijo . str_pad($numero, 5, '0', STR_PAD_LEFT);
    }

    /**
     * Genera un fingerprint para detectar duplicados
     *
     * @param string $tipo Tipo de error
     * @param string $mensaje Mensaje de error
     * @param string $archivo Archivo donde ocurrió
     * @param string $linea Línea donde ocurrió
     * @return string
     */
    public function generar_fingerprint($tipo, $mensaje, $archivo, $linea) {
        // Normalizar el mensaje (quitar números variables como IDs, timestamps)
        $mensaje_normalizado = preg_replace('/\d+/', 'N', $mensaje);
        $mensaje_normalizado = preg_replace('/0x[a-fA-F0-9]+/', 'HEX', $mensaje_normalizado);

        $datos_fingerprint = implode('|', [
            $tipo,
            $mensaje_normalizado,
            basename($archivo),
            $linea,
        ]);

        return hash('sha256', $datos_fingerprint);
    }

    /**
     * Captura el contexto de la request actual
     *
     * @return array
     */
    private function capturar_contexto_request() {
        return [
            'url' => isset($_SERVER['REQUEST_URI']) ? sanitize_text_field(wp_unslash($_SERVER['REQUEST_URI'])) : '',
            'metodo' => isset($_SERVER['REQUEST_METHOD']) ? sanitize_text_field(wp_unslash($_SERVER['REQUEST_METHOD'])) : '',
            'ip' => $this->obtener_ip_cliente(),
            'user_agent' => isset($_SERVER['HTTP_USER_AGENT']) ? sanitize_text_field(wp_unslash($_SERVER['HTTP_USER_AGENT'])) : '',
            'referer' => isset($_SERVER['HTTP_REFERER']) ? esc_url_raw(wp_unslash($_SERVER['HTTP_REFERER'])) : '',
            'es_ajax' => defined('DOING_AJAX') && DOING_AJAX,
            'es_cron' => defined('DOING_CRON') && DOING_CRON,
            'es_rest' => defined('REST_REQUEST') && REST_REQUEST,
        ];
    }

    /**
     * Captura el contexto del servidor
     *
     * @return array
     */
    private function capturar_contexto_servidor() {
        return [
            'php_version' => PHP_VERSION,
            'wp_version' => get_bloginfo('version'),
            'memoria_usada' => size_format(memory_get_usage(true)),
            'memoria_pico' => size_format(memory_get_peak_usage(true)),
            'servidor' => isset($_SERVER['SERVER_SOFTWARE']) ? sanitize_text_field(wp_unslash($_SERVER['SERVER_SOFTWARE'])) : '',
            'timestamp' => current_time('mysql'),
        ];
    }

    /**
     * Captura el contexto del usuario actual
     *
     * @return array|null
     */
    private function capturar_contexto_usuario() {
        if (!is_user_logged_in()) {
            return null;
        }

        $usuario = wp_get_current_user();
        return [
            'id' => $usuario->ID,
            'login' => $usuario->user_login,
            'email' => $usuario->user_email,
            'roles' => $usuario->roles,
        ];
    }

    /**
     * Obtiene la IP del cliente
     *
     * @return string
     */
    private function obtener_ip_cliente() {
        $headers_ip = [
            'HTTP_CF_CONNECTING_IP',
            'HTTP_X_FORWARDED_FOR',
            'HTTP_X_REAL_IP',
            'REMOTE_ADDR',
        ];

        foreach ($headers_ip as $header) {
            if (!empty($_SERVER[$header])) {
                $ip = sanitize_text_field(wp_unslash($_SERVER[$header]));
                // Si hay múltiples IPs, tomar la primera
                if (strpos($ip, ',') !== false) {
                    $ip = trim(explode(',', $ip)[0]);
                }
                if (filter_var($ip, FILTER_VALIDATE_IP)) {
                    return $ip;
                }
            }
        }

        return '0.0.0.0';
    }

    /**
     * Limpia bugs resueltos antiguos
     *
     * @return int Número de bugs eliminados
     */
    public function limpiar_bugs_antiguos() {
        global $wpdb;

        $dias = $this->get_setting('limpiar_resueltos_dias');
        if ($dias <= 0) {
            return 0;
        }

        $fecha_limite = gmdate('Y-m-d H:i:s', strtotime("-{$dias} days"));

        $eliminados = $wpdb->query($wpdb->prepare(
            "DELETE FROM {$this->tabla_bugs} WHERE estado = 'resuelto' AND resuelto_at < %s",
            $fecha_limite
        ));

        return $eliminados;
    }

    /**
     * Registra el menú de administración
     *
     * @return void
     */
    public function registrar_menu_admin() {
        add_submenu_page(
            'flavor-platform',
            __('Bug Tracker', 'flavor-platform'),
            __('🐛 Bug Tracker', 'flavor-platform'),
            'manage_options',
            'flavor-bug-tracker',
            [$this, 'render_pagina_admin']
        );
    }

    /**
     * Renderiza la página de administración
     *
     * @return void
     */
    public function render_pagina_admin() {
        $tab_actual = isset($_GET['tab']) ? sanitize_text_field(wp_unslash($_GET['tab'])) : 'lista';

        ?>
        <div class="wrap flavor-bug-tracker-admin">
            <h1><?php esc_html_e('Bug Tracker', 'flavor-platform'); ?></h1>

            <nav class="nav-tab-wrapper">
                <a href="?page=flavor-bug-tracker&tab=lista" class="nav-tab <?php echo $tab_actual === 'lista' ? 'nav-tab-active' : ''; ?>">
                    <?php esc_html_e('Lista de Bugs', 'flavor-platform'); ?>
                </a>
                <a href="?page=flavor-bug-tracker&tab=settings" class="nav-tab <?php echo $tab_actual === 'settings' ? 'nav-tab-active' : ''; ?>">
                    <?php esc_html_e('Configuración', 'flavor-platform'); ?>
                </a>
            </nav>

            <div class="tab-content" style="margin-top: 20px;">
                <?php
                if ($tab_actual === 'settings') {
                    include dirname(__FILE__) . '/views/admin-settings.php';
                } else {
                    include dirname(__FILE__) . '/views/admin-list.php';
                }
                ?>
            </div>
        </div>
        <?php
    }

    /**
     * Configuración admin canónica del módulo.
     *
     * @return array
     */
    protected function get_admin_config() {
        return [
            'id' => 'bug-tracker',
            'label' => __('Bug Tracker', 'flavor-platform'),
            'icon' => 'dashicons-warning',
            'capability' => 'manage_options',
            'categoria' => 'operaciones',
            'paginas' => [
                [
                    'slug' => 'flavor-bug-tracker',
                    'titulo' => __('Dashboard', 'flavor-platform'),
                    'callback' => [$this, 'render_pagina_admin'],
                ],
            ],
            'estadisticas' => [$this, 'get_estadisticas'],
        ];
    }

    /**
     * Registra la API REST
     *
     * @return void
     */
    public function registrar_api_rest() {
        if ($this->api) {
            $this->api->registrar_rutas();
        }
    }

    /**
     * Obtiene la instancia del gestor de canales
     *
     * @return Flavor_Bug_Tracker_Channels|null
     */
    public function get_channels() {
        return $this->channels;
    }

    /**
     * Obtiene la instancia del error handler
     *
     * @return Flavor_Bug_Tracker_Error_Handler|null
     */
    public function get_error_handler() {
        return $this->error_handler;
    }

    /**
     * Obtiene el nombre de la tabla de bugs
     *
     * @return string
     */
    public function get_tabla_bugs() {
        return $this->tabla_bugs;
    }

    /**
     * Obtiene el nombre de la tabla de canales
     *
     * @return string
     */
    public function get_tabla_channels() {
        return $this->tabla_channels;
    }

    /**
     * Obtiene las acciones disponibles del módulo
     *
     * @return array
     */
    public function get_actions() {
        return [
            'reportar_bug' => [
                'name' => 'Reportar Bug',
                'description' => 'Reporta un nuevo bug manualmente',
                'params' => ['titulo', 'descripcion', 'severidad'],
            ],
            'listar_bugs' => [
                'name' => 'Listar Bugs',
                'description' => 'Lista los bugs con filtros opcionales',
                'params' => ['estado', 'severidad', 'tipo', 'limit'],
            ],
            'resolver_bug' => [
                'name' => 'Resolver Bug',
                'description' => 'Marca un bug como resuelto',
                'params' => ['bug_id', 'notas'],
            ],
            'obtener_estadisticas' => [
                'name' => 'Obtener Estadísticas',
                'description' => 'Obtiene estadísticas de bugs',
                'params' => [],
            ],
        ];
    }

    /**
     * Ejecuta una acción del módulo
     *
     * @param string $action_name Nombre de la acción
     * @param array  $params Parámetros
     * @return array Resultado
     */
    public function execute_action($action_name, $params) {
        switch ($action_name) {
            case 'reportar_bug':
                $bug_id = $this->reportar_bug_manual(
                    $params['titulo'] ?? '',
                    $params['descripcion'] ?? '',
                    ['severidad' => $params['severidad'] ?? 'medium']
                );
                return [
                    'success' => $bug_id !== false,
                    'bug_id' => $bug_id,
                    'message' => $bug_id ? 'Bug reportado correctamente' : 'Error al reportar bug',
                ];

            case 'listar_bugs':
                $resultado = $this->listar_bugs([
                    'estado' => $params['estado'] ?? null,
                    'severidad' => $params['severidad'] ?? null,
                    'tipo' => $params['tipo'] ?? null,
                    'limit' => $params['limit'] ?? 20,
                ]);
                return [
                    'success' => true,
                    'bugs' => $resultado['bugs'],
                    'total' => $resultado['total'],
                ];

            case 'resolver_bug':
                $resultado = $this->actualizar_estado_bug(
                    $params['bug_id'] ?? 0,
                    'resuelto',
                    $params['notas'] ?? ''
                );
                return [
                    'success' => $resultado,
                    'message' => $resultado ? 'Bug resuelto' : 'Error al resolver bug',
                ];

            case 'obtener_estadisticas':
                return [
                    'success' => true,
                    'estadisticas' => $this->obtener_estadisticas(),
                ];

            default:
                return [
                    'success' => false,
                    'message' => 'Acción no reconocida',
                ];
        }
    }

    /**
     * Obtiene las definiciones de tools para Claude
     *
     * @return array
     */
    public function get_tool_definitions() {
        return [
            [
                'name' => 'bug_tracker_reportar',
                'description' => 'Reporta un nuevo bug en el sistema',
                'input_schema' => [
                    'type' => 'object',
                    'properties' => [
                        'titulo' => [
                            'type' => 'string',
                            'description' => 'Título descriptivo del bug',
                        ],
                        'descripcion' => [
                            'type' => 'string',
                            'description' => 'Descripción detallada del problema',
                        ],
                        'severidad' => [
                            'type' => 'string',
                            'enum' => ['critical', 'high', 'medium', 'low', 'info'],
                            'description' => 'Nivel de severidad del bug',
                        ],
                    ],
                    'required' => ['titulo', 'descripcion'],
                ],
            ],
            [
                'name' => 'bug_tracker_listar',
                'description' => 'Lista los bugs del sistema con filtros',
                'input_schema' => [
                    'type' => 'object',
                    'properties' => [
                        'estado' => [
                            'type' => 'string',
                            'enum' => ['nuevo', 'abierto', 'resuelto', 'ignorado'],
                        ],
                        'severidad' => [
                            'type' => 'string',
                            'enum' => ['critical', 'high', 'medium', 'low', 'info'],
                        ],
                    ],
                ],
            ],
            [
                'name' => 'bug_tracker_estadisticas',
                'description' => 'Obtiene estadísticas de bugs del sistema',
                'input_schema' => [
                    'type' => 'object',
                    'properties' => [],
                ],
            ],
        ];
    }

    /**
     * Obtiene el conocimiento base del módulo
     *
     * @return string
     */
    public function get_knowledge_base() {
        return <<<'KB'
# Bug Tracker

El módulo Bug Tracker permite capturar y gestionar errores del sistema.

## Funcionalidades
- Captura automática de errores PHP de los plugins Flavor
- Reportes manuales de bugs mediante la función `flavor_report_bug()`
- Notificaciones multicanal (Slack, Discord, Email)
- Panel de administración para gestionar bugs
- API REST para integración con sistemas externos

## Severidades
- **critical**: Errores que impiden el funcionamiento del sistema
- **high**: Errores importantes que afectan funcionalidad clave
- **medium**: Errores moderados con workarounds disponibles
- **low**: Errores menores de baja prioridad
- **info**: Información o sugerencias de mejora

## Estados
- **nuevo**: Bug recién reportado sin revisar
- **abierto**: Bug confirmado en proceso de resolución
- **resuelto**: Bug corregido
- **ignorado**: Bug descartado o no reproducible
KB;
    }
}

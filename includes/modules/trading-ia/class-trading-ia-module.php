<?php
/**
 * Modulo Trading IA para Chat IA
 *
 * Bot de trading simulado (paper trading) con IA para criptomonedas Solana.
 *
 * @package FlavorChatIA
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Modulo de Trading IA - Paper trading con indicadores tecnicos, gestion de riesgo y ciclo automatico
 */
class Flavor_Chat_Trading_IA_Module extends Flavor_Chat_Module_Base {

    use Flavor_Module_Admin_Pages_Trait;
    use Flavor_Module_Notifications_Trait;

    /**
     * Hook de WP Cron
     */
    const CRON_HOOK = 'flavor_trading_ia_ciclo_trading';

    /**
     * Nombre del schedule custom
     */
    const CRON_SCHEDULE = 'flavor_trading_ia_intervalo';

    /**
     * Constructor
     */
    public function __construct() {
        // Auto-registered AJAX handlers
        add_action('wp_ajax_trading_ia_reset_paper_trading', [$this, 'ajax_reset_paper_trading']);
        add_action('wp_ajax_nopriv_trading_ia_reset_paper_trading', [$this, 'ajax_reset_paper_trading']);

        $this->id = 'trading_ia';
        $this->name = 'Trading IA'; // Translation loaded on init
        $this->description = 'Bot de trading simulado con IA para criptomonedas Solana. Paper trading con indicadores tecnicos, gestion de riesgo y reglas dinamicas.'; // Translation loaded on init

        parent::__construct();
    }

    /**
     * {@inheritdoc}
     */
    public function can_activate() {
        global $wpdb;
        $tabla_trades = $wpdb->prefix . 'flavor_trading_ia_trades';

        return Flavor_Chat_Helpers::tabla_existe($tabla_trades);
    }

    /**
     * {@inheritdoc}
     */
    public function get_activation_error() {
        if (!$this->can_activate()) {
            return __('Las tablas del Trading IA no estan creadas. Se crearan automaticamente al activar el plugin.', 'flavor-chat-ia');
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
    public function get_table_schema() {
        global $wpdb;
        $charset_collate = $wpdb->get_charset_collate();
        $tabla_trades = $wpdb->prefix . 'flavor_trading_ia_trades';
        $tabla_portfolio = $wpdb->prefix . 'flavor_trading_ia_portfolio';
        $tabla_reglas = $wpdb->prefix . 'flavor_trading_ia_reglas';
        $tabla_alertas = $wpdb->prefix . 'flavor_trading_ia_alertas';

        return array(
            $tabla_trades => "CREATE TABLE {$tabla_trades} (
                id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
                usuario_id bigint(20) UNSIGNED NOT NULL DEFAULT 0,
                tipo enum('compra','venta') NOT NULL,
                token varchar(20) NOT NULL,
                cantidad decimal(20,8) NOT NULL,
                precio_entrada decimal(20,8) NOT NULL,
                precio_salida decimal(20,8) DEFAULT NULL,
                estado enum('abierta','cerrada','cancelada') NOT NULL DEFAULT 'abierta',
                stop_loss decimal(20,8) DEFAULT NULL,
                take_profit decimal(20,8) DEFAULT NULL,
                pnl decimal(20,8) DEFAULT NULL,
                pnl_porcentaje decimal(10,4) DEFAULT NULL,
                confianza_ia int(11) DEFAULT NULL,
                razon_ia text,
                timestamp datetime NOT NULL,
                fecha_apertura datetime NOT NULL,
                fecha_cierre datetime DEFAULT NULL,
                PRIMARY KEY (id),
                KEY usuario_id (usuario_id),
                KEY estado (estado),
                KEY token (token),
                KEY timestamp (timestamp),
                KEY fecha_apertura (fecha_apertura)
            ) $charset_collate;",

            $tabla_portfolio => "CREATE TABLE {$tabla_portfolio} (
                id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
                usuario_id bigint(20) UNSIGNED NOT NULL,
                balance_usd decimal(20,8) NOT NULL DEFAULT 1000,
                balance_inicial decimal(20,8) NOT NULL DEFAULT 1000,
                tokens_json longtext DEFAULT NULL,
                precios_entrada_json longtext DEFAULT NULL,
                fees_acumuladas_usd decimal(10,6) DEFAULT 0,
                contador_trades int(11) DEFAULT 0,
                fecha_creacion datetime DEFAULT NULL,
                fecha_actualizacion datetime DEFAULT NULL,
                PRIMARY KEY (id),
                UNIQUE KEY usuario_id (usuario_id)
            ) $charset_collate;",

            $tabla_reglas => "CREATE TABLE {$tabla_reglas} (
                id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
                regla_id varchar(50) NOT NULL,
                usuario_id bigint(20) UNSIGNED NOT NULL,
                nombre varchar(255) NOT NULL,
                token_condicion varchar(20) DEFAULT '*',
                indicador varchar(50) NOT NULL,
                operador varchar(5) NOT NULL,
                valor decimal(20,8) NOT NULL,
                accion_tipo varchar(50) NOT NULL,
                accion_parametros_json text DEFAULT NULL,
                activa tinyint(1) DEFAULT 1,
                creada_por varchar(10) DEFAULT 'ia',
                razon text DEFAULT NULL,
                veces_activada int(11) DEFAULT 0,
                ultima_activacion datetime DEFAULT NULL,
                fecha_creacion datetime DEFAULT NULL,
                PRIMARY KEY (id),
                KEY regla_id (regla_id),
                KEY usuario_id (usuario_id),
                KEY activa (activa)
            ) $charset_collate;",

            $tabla_alertas => "CREATE TABLE {$tabla_alertas} (
                id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
                usuario_id bigint(20) UNSIGNED NOT NULL DEFAULT 0,
                token varchar(20) NOT NULL,
                tipo_alerta enum('precio_mayor','precio_menor','cambio_porcentaje') NOT NULL,
                valor_objetivo decimal(20,8) NOT NULL,
                activa tinyint(1) NOT NULL DEFAULT 1,
                notificada tinyint(1) NOT NULL DEFAULT 0,
                fecha_creacion datetime NOT NULL,
                fecha_notificacion datetime DEFAULT NULL,
                PRIMARY KEY (id),
                KEY usuario_id (usuario_id),
                KEY activa (activa)
            ) $charset_collate;"
        );
    }

    /**
     * {@inheritdoc}
     */
    protected function get_default_settings() {
        return array(
            'agresividad'              => 5,
            'riesgo_maximo_porcentaje' => 5.0,
            'stop_loss_porcentaje'     => 3.0,
            'take_profit_porcentaje'   => 5.0,
            'intervalo_analisis'       => 60,
            'confianza_minima_trade'   => 60,
            'balance_inicial'          => 1000.0,
            'auto_ajuste_enabled'      => false,
            'bot_activo'               => false,
            'tokens_monitoreados'      => array('SOL', 'BONK', 'JUP', 'WIF', 'JTO'),
            'max_trades_por_hora'      => 10,
            'max_posiciones_abiertas'  => 5,
            'stop_loss_global'         => 15.0,
            'min_balance_usd'          => 10.0,
            'max_reglas'               => 30,
        );
    }

    /**
     * {@inheritdoc}
     */
    public function init() {
        if (!$this->is_experimental_enabled()) {
            // Aviso de módulo experimental deshabilitado (comentado para no mostrar)
            // add_action('admin_notices', [$this, 'render_experimental_disabled_notice']);
            return;
        }

        add_action('init', [$this, 'maybe_create_pages']);
        add_action('init', [$this, 'maybe_create_tables']);
        $this->cargar_clases_auxiliares();

        // Registrar schedule personalizado para WP Cron
        add_filter('cron_schedules', array($this, 'registrar_cron_schedule'));

        // Hook del ciclo de trading
        add_action(self::CRON_HOOK, array($this, 'ejecutar_ciclo_cron'));

        // Si el bot estaba activo, asegurar que el cron esta programado
        if ($this->get_setting('bot_activo', false)) {
            $this->programar_cron();
        }

        // Registrar REST API routes
        add_action('rest_api_init', array($this, 'register_rest_routes'));

        // Registrar shortcodes
        add_action('init', array($this, 'register_shortcodes'));

        // AJAX handlers para usuarios autenticados
        add_action('wp_ajax_trading_ia_obtener_estado', array($this, 'ajax_obtener_estado'));
        add_action('wp_ajax_trading_ia_obtener_portfolio', array($this, 'ajax_obtener_portfolio'));
        add_action('wp_ajax_trading_ia_obtener_mercado', array($this, 'ajax_obtener_mercado'));
        add_action('wp_ajax_trading_ia_obtener_indicadores', array($this, 'ajax_obtener_indicadores'));
        add_action('wp_ajax_trading_ia_ejecutar_compra', array($this, 'ajax_ejecutar_compra'));
        add_action('wp_ajax_trading_ia_ejecutar_venta', array($this, 'ajax_ejecutar_venta'));
        add_action('wp_ajax_trading_ia_iniciar_bot', array($this, 'ajax_iniciar_bot'));
        add_action('wp_ajax_trading_ia_detener_bot', array($this, 'ajax_detener_bot'));
        add_action('wp_ajax_trading_ia_historial_trades', array($this, 'ajax_historial_trades'));
        add_action('wp_ajax_trading_ia_obtener_reglas', array($this, 'ajax_obtener_reglas'));
        add_action('wp_ajax_trading_ia_crear_regla', array($this, 'ajax_crear_regla'));
        add_action('wp_ajax_trading_ia_eliminar_regla', array($this, 'ajax_eliminar_regla'));
        add_action('wp_ajax_trading_ia_actualizar_parametros', array($this, 'ajax_actualizar_parametros'));
        add_action('wp_ajax_trading_ia_reset', array($this, 'ajax_reset_paper_trading'));
        add_action('wp_ajax_trading_ia_estado_riesgo', array($this, 'ajax_estado_riesgo'));
        add_action('wp_ajax_trading_ia_agregar_token', array($this, 'ajax_agregar_token'));
        add_action('wp_ajax_trading_ia_eliminar_token', array($this, 'ajax_eliminar_token'));
        add_action('wp_ajax_trading_ia_exportar_historial', array($this, 'ajax_exportar_historial'));

        // Cron adicional para limpieza y reportes
        if (!wp_next_scheduled('flavor_trading_ia_reporte_diario')) {
            wp_schedule_event(time(), 'daily', 'flavor_trading_ia_reporte_diario');
        }
        add_action('flavor_trading_ia_reporte_diario', array($this, 'generar_reporte_diario'));

        // Cron para alertas de precios
        if (!wp_next_scheduled('flavor_trading_ia_verificar_alertas')) {
            wp_schedule_event(time(), 'hourly', 'flavor_trading_ia_verificar_alertas');
        }
        add_action('flavor_trading_ia_verificar_alertas', array($this, 'verificar_alertas_precio'));

        // Registrar en panel de administracion unificado
        
        // Cargar Dashboard Tab
        $this->inicializar_dashboard_tab();
        $this->registrar_en_panel_unificado();
    }

    /**
     * Permite activar módulos experimentales por módulo o de forma global.
     *
     * @return bool
     */
    private function is_experimental_enabled() {
        $settings = get_option('flavor_chat_ia_settings', []);
        $global_enabled = !empty($settings['enable_experimental_modules']);
        $module_enabled = !empty($settings['experimental_modules'])
            && is_array($settings['experimental_modules'])
            && in_array($this->id, $settings['experimental_modules'], true);

        return $global_enabled || $module_enabled;
    }

    /**
     * Aviso de módulo experimental deshabilitado.
     *
     * @return void
     */
    public function render_experimental_disabled_notice() {
        if (!is_admin() || !current_user_can('manage_options')) {
            return;
        }

        static $shown = false;
        if ($shown) {
            return;
        }
        $shown = true;

        echo '<div class="notice notice-warning"><p>';
        echo esc_html__('Trading IA está deshabilitado por defecto por ser un módulo experimental. Actívalo en ajustes avanzados si quieres usarlo.', 'flavor-chat-ia');
        echo '</p></div>';
    }

    /**
     * Configuracion para el panel de administracion unificado
     *
     * @return array
     */
    protected function get_admin_config() {
        return array(
            'id'         => $this->id,
            'label'      => $this->name,
            'icon'       => 'dashicons-chart-line',
            'capability' => 'manage_options',
            'categoria'  => 'economia',
            'paginas'    => array(
                array(
                    'slug'     => 'trading-ia-dashboard',
                    'titulo'   => __('Dashboard', 'flavor-chat-ia'),
                    'callback' => array($this, 'render_admin_dashboard'),
                ),
                array(
                    'slug'     => 'trading-ia-operaciones',
                    'titulo'   => __('Operaciones', 'flavor-chat-ia'),
                    'callback' => array($this, 'render_admin_operaciones'),
                    'badge'    => array($this, 'contar_posiciones_abiertas'),
                ),
                array(
                    'slug'     => 'trading-ia-configuracion',
                    'titulo'   => __('Configuracion', 'flavor-chat-ia'),
                    'callback' => array($this, 'render_admin_configuracion'),
                ),
            ),
            'dashboard_widget' => array($this, 'render_dashboard_widget'),
            'estadisticas'     => array($this, 'get_estadisticas_panel'),
        );
    }

    /**
     * Cuenta las posiciones abiertas para el badge
     *
     * @return int
     */
    public function contar_posiciones_abiertas() {
        // Verificar que el módulo esté activo
        if (!$this->can_activate()) {
            return 0;
        }

        global $wpdb;
        $tabla_trades = $wpdb->prefix . 'flavor_trading_ia_trades';

        return (int) $wpdb->get_var(
            "SELECT COUNT(*) FROM {$tabla_trades} WHERE estado = 'abierta'"
        );
    }

    /**
     * Renderiza el dashboard de administracion
     */
    public function render_admin_dashboard() {
        $this->render_page_header(__('Trading IA - Dashboard', 'flavor-chat-ia'));
        echo '<div class="wrap trading-ia-admin-dashboard">';
        echo '<p>' . esc_html__('Panel de control del bot de trading simulado.', 'flavor-chat-ia') . '</p>';
        // Aqui se puede agregar el contenido del dashboard
        echo '</div>';
    }

    /**
     * Renderiza la pagina de operaciones
     */
    public function render_admin_operaciones() {
        $this->render_page_header(
            __('Trading IA - Operaciones', 'flavor-chat-ia'),
            array(
                array(
                    'label' => __('Nueva operacion', 'flavor-chat-ia'),
                    'url'   => '#nueva-operacion',
                    'class' => 'button-primary',
                ),
            )
        );
        echo '<div class="wrap trading-ia-admin-operaciones">';
        echo '<p>' . esc_html__('Historial y gestion de operaciones de trading.', 'flavor-chat-ia') . '</p>';
        // Aqui se puede agregar el listado de operaciones
        echo '</div>';
    }

    /**
     * Renderiza la pagina de configuracion
     */
    public function render_admin_configuracion() {
        $this->render_page_header(__('Trading IA - Configuracion', 'flavor-chat-ia'));
        echo '<div class="wrap trading-ia-admin-configuracion">';
        echo '<p>' . esc_html__('Configuracion del bot de trading y parametros de riesgo.', 'flavor-chat-ia') . '</p>';
        // Aqui se puede agregar el formulario de configuracion
        echo '</div>';
    }

    /**
     * Renderiza el widget para el dashboard unificado
     */
    public function render_dashboard_widget() {
        $posiciones_abiertas = $this->contar_posiciones_abiertas();
        $bot_activo = $this->get_setting('bot_activo', false);
        $estado_bot = $bot_activo ? __('Activo', 'flavor-chat-ia') : __('Inactivo', 'flavor-chat-ia');
        ?>
        <div class="trading-ia-widget">
            <p><strong><?php esc_html_e('Estado del bot:', 'flavor-chat-ia'); ?></strong> <?php echo esc_html($estado_bot); ?></p>
            <p><strong><?php esc_html_e('Posiciones abiertas:', 'flavor-chat-ia'); ?></strong> <?php echo esc_html($posiciones_abiertas); ?></p>
            <a href="<?php echo esc_url($this->admin_page_url('trading-ia-dashboard')); ?>" class="button">
                <?php esc_html_e('Ver dashboard', 'flavor-chat-ia'); ?>
            </a>
        </div>
        <?php
    }

    /**
     * Obtiene estadisticas para el panel unificado
     *
     * @return array
     */
    public function get_estadisticas_panel() {
        global $wpdb;
        $tabla_trades = $wpdb->prefix . 'flavor_trading_ia_trades';

        $total_trades = (int) $wpdb->get_var("SELECT COUNT(*) FROM {$tabla_trades}");
        $trades_ganadores = (int) $wpdb->get_var(
            "SELECT COUNT(*) FROM {$tabla_trades} WHERE estado = 'cerrada' AND pnl > 0"
        );
        $trades_perdedores = (int) $wpdb->get_var(
            "SELECT COUNT(*) FROM {$tabla_trades} WHERE estado = 'cerrada' AND pnl < 0"
        );

        return array(
            'total_operaciones' => $total_trades,
            'operaciones_ganadoras' => $trades_ganadores,
            'operaciones_perdedoras' => $trades_perdedores,
            'posiciones_abiertas' => $this->contar_posiciones_abiertas(),
            'bot_activo' => $this->get_setting('bot_activo', false),
        );
    }

    /**
     * Carga las clases auxiliares del modulo
     */
    private function cargar_clases_auxiliares() {
        $directorio_modulo = FLAVOR_CHAT_IA_PATH . 'includes/modules/trading-ia/';

        $archivos_clases = array(
            'class-trading-ia-mercado.php',
            'class-trading-ia-indicadores.php',
            'class-trading-ia-paper-trading.php',
            'class-trading-ia-gestor-riesgo.php',
            'class-trading-ia-reglas-dinamicas.php',
            'class-trading-ia-auto-ajuste.php',
            'class-trading-ia-cerebro.php',
            'class-trading-ia-ciclo.php',
        );

        foreach ($archivos_clases as $archivo) {
            $ruta_completa = $directorio_modulo . $archivo;
            if (file_exists($ruta_completa)) {
                require_once $ruta_completa;
            }
        }
    }

    // =========================================================================
    // Acceso publico a settings (requerido por clases auxiliares)
    // =========================================================================

    /**
     * Obtiene un valor de configuracion (publico para clases auxiliares)
     *
     * @param string $clave Clave de configuracion
     * @param mixed  $valor_defecto Valor por defecto
     * @return mixed
     */
    public function get_setting($clave, $valor_defecto = null) {
        return parent::get_setting($clave, $valor_defecto);
    }

    /**
     * Actualiza un valor de configuracion (publico para clases auxiliares)
     *
     * @param string $clave Clave de configuracion
     * @param mixed  $valor Valor
     * @return bool
     */
    public function update_setting($clave, $valor) {
        return parent::update_setting($clave, $valor);
    }

    // =========================================================================
    // WP Cron
    // =========================================================================

    /**
     * Registra el schedule personalizado para WP Cron
     *
     * @param array $schedules Schedules existentes
     * @return array
     */
    public function registrar_cron_schedule($schedules) {
        $intervalo_segundos = intval($this->get_setting('intervalo_analisis', 60));

        $schedules[self::CRON_SCHEDULE] = array(
            'interval' => $intervalo_segundos,
            'display'  => sprintf(
                __('Trading IA - Cada %d segundos', 'flavor-chat-ia'),
                $intervalo_segundos
            ),
        );

        return $schedules;
    }

    /**
     * Programa el cron del ciclo de trading
     */
    private function programar_cron() {
        if (!wp_next_scheduled(self::CRON_HOOK)) {
            wp_schedule_event(time(), self::CRON_SCHEDULE, self::CRON_HOOK);
        }
    }

    /**
     * Detiene el cron del ciclo de trading
     */
    private function detener_cron() {
        $timestamp_programado = wp_next_scheduled(self::CRON_HOOK);
        if ($timestamp_programado) {
            wp_unschedule_event($timestamp_programado, self::CRON_HOOK);
        }
        wp_clear_scheduled_hook(self::CRON_HOOK);
    }

    /**
     * Ejecuta el ciclo de trading via WP Cron
     */
    public function ejecutar_ciclo_cron() {
        if (!$this->get_setting('bot_activo', false)) {
            $this->detener_cron();
            return;
        }

        $usuario_id = $this->obtener_usuario_trading();

        if (!$usuario_id) {
            flavor_chat_ia_log('Trading IA: No se encontro usuario para el ciclo cron', 'trading_ia');
            return;
        }

        try {
            $orquestador_ciclo = new Flavor_Trading_IA_Ciclo($this);
            $resultado_ciclo = $orquestador_ciclo->ejecutar_ciclo($usuario_id);

            if (!empty($resultado_ciclo['errores'])) {
                foreach ($resultado_ciclo['errores'] as $error) {
                    flavor_chat_ia_log('Trading IA Ciclo Error: ' . $error, 'trading_ia');
                }
            }
        } catch (\Exception $excepcion) {
            flavor_chat_ia_log('Trading IA Cron Exception: ' . $excepcion->getMessage(), 'trading_ia');
        }
    }

    /**
     * Obtiene el ID de usuario para el trading
     *
     * @return int ID del usuario o 0
     */
    private function obtener_usuario_trading() {
        $usuario_id = get_current_user_id();

        if (!$usuario_id) {
            $administradores = get_users(array(
                'role'   => 'administrator',
                'number' => 1,
                'fields' => 'ID',
            ));
            $usuario_id = !empty($administradores) ? intval($administradores[0]) : 0;
        }

        return $usuario_id;
    }

    // =========================================================================
    // Acciones del modulo
    // =========================================================================

    /**
     * {@inheritdoc}
     */
    public function get_actions() {
        return array(
            'obtener_estado' => array(
                'description' => 'Obtener estado completo del bot de trading',
                'params'      => array(),
            ),
            'obtener_portfolio' => array(
                'description' => 'Obtener estado del portfolio con ganancias/perdidas',
                'params'      => array(),
            ),
            'obtener_datos_mercado' => array(
                'description' => 'Obtener precios actuales de criptomonedas',
                'params'      => array('tokens'),
            ),
            'obtener_indicadores' => array(
                'description' => 'Obtener indicadores tecnicos de un token',
                'params'      => array('token'),
            ),
            'ejecutar_compra_manual' => array(
                'description' => 'Ejecutar una compra simulada de un token',
                'params'      => array('token', 'cantidad_usd'),
            ),
            'ejecutar_venta_manual' => array(
                'description' => 'Ejecutar una venta simulada de un token',
                'params'      => array('token', 'cantidad'),
            ),
            'iniciar_bot' => array(
                'description' => 'Iniciar el ciclo automatico de trading',
                'params'      => array(),
            ),
            'detener_bot' => array(
                'description' => 'Detener el ciclo automatico de trading',
                'params'      => array(),
            ),
            'obtener_historial_trades' => array(
                'description' => 'Obtener historial de operaciones realizadas',
                'params'      => array('limite'),
            ),
            'obtener_reglas' => array(
                'description' => 'Listar reglas dinamicas de trading',
                'params'      => array(),
            ),
            'crear_regla' => array(
                'description' => 'Crear una regla de trading personalizada',
                'params'      => array('nombre', 'token', 'indicador', 'operador', 'valor', 'accion_tipo'),
            ),
            'eliminar_regla' => array(
                'description' => 'Eliminar una regla de trading',
                'params'      => array('regla_id'),
            ),
            'actualizar_parametros' => array(
                'description' => 'Actualizar parametros de configuracion del trading',
                'params'      => array('parametros'),
            ),
            'reset_paper_trading' => array(
                'description' => 'Reiniciar la simulacion de trading a valores iniciales',
                'params'      => array(),
            ),
            'obtener_estado_riesgo' => array(
                'description' => 'Obtener estado de la gestion de riesgo',
                'params'      => array(),
            ),
        );
    }

    /**
     * {@inheritdoc}
     */
    public function execute_action($nombre_accion, $parametros) {
        $aliases = array(
            'estado' => 'obtener_estado',
            'listar' => 'obtener_estado',
            'resumen' => 'obtener_estado',
            'portfolio' => 'obtener_portfolio',
            'mercado' => 'obtener_datos_mercado',
            'indicadores' => 'obtener_indicadores',
            'comprar' => 'ejecutar_compra_manual',
            'vender' => 'ejecutar_venta_manual',
            'iniciar' => 'iniciar_bot',
            'detener' => 'detener_bot',
            'historial' => 'obtener_historial_trades',
            'reglas' => 'obtener_reglas',
            'crear_regla' => 'crear_regla',
            'eliminar' => 'eliminar_regla',
            'parametros' => 'actualizar_parametros',
            'reset' => 'reset_paper_trading',
            'riesgo' => 'obtener_estado_riesgo',
        );

        $nombre_accion = $aliases[$nombre_accion] ?? $nombre_accion;
        $metodo_accion = 'action_' . $nombre_accion;

        if (method_exists($this, $metodo_accion)) {
            return $this->$metodo_accion($parametros);
        }

        return array(
            'success' => false,
            'error'   => sprintf(__('Accion no implementada: %s', 'flavor-chat-ia'), $nombre_accion),
        );
    }

    // =========================================================================
    // Implementacion de acciones
    // =========================================================================

    /**
     * Accion: Obtener estado completo del bot
     *
     * @param array $parametros Parametros de la accion
     * @return array
     */
    private function action_obtener_estado($parametros) {
        $usuario_id = get_current_user_id() ?: $this->obtener_usuario_trading();

        $paper_trading = new Flavor_Trading_IA_Paper_Trading(
            $usuario_id,
            $this->get_setting('balance_inicial', 1000.0)
        );

        $configuracion_riesgo = $this->obtener_configuracion_riesgo();
        $gestor_riesgo = new Flavor_Trading_IA_Gestor_Riesgo($configuracion_riesgo, $usuario_id);
        $gestor_reglas = new Flavor_Trading_IA_Reglas_Dinamicas($usuario_id);

        $portfolio = $paper_trading->obtener_estado_portfolio();

        $estado_auto_ajuste = array('habilitado' => false);
        if ($this->get_setting('auto_ajuste_enabled', false)) {
            $gestor_ajuste = new Flavor_Trading_IA_Auto_Ajuste($this, $usuario_id);
            $estado_auto_ajuste = $gestor_ajuste->obtener_estado();
        }

        return array(
            'success' => true,
            'estado'  => array(
                'bot_activo'            => (bool) $this->get_setting('bot_activo', false),
                'intervalo_analisis'    => $this->get_setting('intervalo_analisis', 60),
                'agresividad'           => $this->get_setting('agresividad', 5),
                'tokens_monitoreados'   => $this->get_setting('tokens_monitoreados', array()),
                'confianza_minima'      => $this->get_setting('confianza_minima_trade', 60),
                'auto_ajuste'           => $estado_auto_ajuste,
                'portfolio'             => $portfolio,
                'riesgo'                => $gestor_riesgo->obtener_resumen(),
                'reglas'                => $gestor_reglas->obtener_estado(),
                'cron_programado'       => (bool) wp_next_scheduled(self::CRON_HOOK),
            ),
        );
    }

    /**
     * Accion: Obtener portfolio
     *
     * @param array $parametros Parametros de la accion
     * @return array
     */
    private function action_obtener_portfolio($parametros) {
        $usuario_id = get_current_user_id() ?: $this->obtener_usuario_trading();

        $paper_trading = new Flavor_Trading_IA_Paper_Trading(
            $usuario_id,
            $this->get_setting('balance_inicial', 1000.0)
        );

        $mercado = new Flavor_Trading_IA_Mercado();
        $tokens_monitoreados = $this->get_setting('tokens_monitoreados', array('SOL'));
        $precios_simples = $mercado->obtener_precios_simples($tokens_monitoreados);
        $paper_trading->actualizar_precios($precios_simples);

        return array(
            'success'   => true,
            'portfolio' => $paper_trading->obtener_estado_portfolio(),
        );
    }

    /**
     * Accion: Obtener datos de mercado
     *
     * @param array $parametros Parametros de la accion
     * @return array
     */
    private function action_obtener_datos_mercado($parametros) {
        $tokens_solicitados = isset($parametros['tokens']) ? $parametros['tokens'] : null;

        if (is_string($tokens_solicitados)) {
            $tokens_solicitados = array_map('trim', explode(',', $tokens_solicitados));
        }

        if (empty($tokens_solicitados)) {
            $tokens_solicitados = $this->get_setting('tokens_monitoreados', array('SOL'));
        }

        $tokens_mayusculas = array_map('strtoupper', $tokens_solicitados);

        $mercado = new Flavor_Trading_IA_Mercado();
        $datos_mercado = $mercado->obtener_datos_para_ia($tokens_mayusculas);

        return array(
            'success' => true,
            'mercado' => $datos_mercado,
        );
    }

    /**
     * Accion: Obtener indicadores tecnicos
     *
     * @param array $parametros Parametros de la accion
     * @return array
     */
    private function action_obtener_indicadores($parametros) {
        $token_solicitado = isset($parametros['token']) ? strtoupper(sanitize_text_field($parametros['token'])) : '';

        if (empty($token_solicitado)) {
            return array(
                'success' => false,
                'error'   => __('Debes especificar un token (ej: SOL, BONK)', 'flavor-chat-ia'),
            );
        }

        $mercado = new Flavor_Trading_IA_Mercado();
        $datos_token = $mercado->obtener_precio($token_solicitado);

        if (!$datos_token || empty($datos_token['precio_usd'])) {
            return array(
                'success' => false,
                'error'   => sprintf(__('No se pudo obtener el precio de %s', 'flavor-chat-ia'), $token_solicitado),
            );
        }

        $precio_actual = floatval($datos_token['precio_usd']);

        $calculador_indicadores = new Flavor_Trading_IA_Indicadores();
        $indicadores_calculados = $calculador_indicadores->calcular_indicadores($token_solicitado, $precio_actual);

        return array(
            'success'      => true,
            'token'        => $token_solicitado,
            'precio'       => $precio_actual,
            'indicadores'  => $indicadores_calculados,
        );
    }

    /**
     * Accion: Ejecutar compra manual
     *
     * @param array $parametros Parametros de la accion
     * @return array
     */
    private function action_ejecutar_compra_manual($parametros) {
        $usuario_id = get_current_user_id() ?: $this->obtener_usuario_trading();
        $token_compra = isset($parametros['token']) ? strtoupper(sanitize_text_field($parametros['token'])) : '';
        $cantidad_usd = isset($parametros['cantidad_usd']) ? floatval($parametros['cantidad_usd']) : 0;

        if (empty($token_compra)) {
            return array(
                'success' => false,
                'error'   => __('Debes especificar el token a comprar', 'flavor-chat-ia'),
            );
        }

        if ($cantidad_usd <= 0) {
            return array(
                'success' => false,
                'error'   => __('La cantidad en USD debe ser mayor a 0', 'flavor-chat-ia'),
            );
        }

        $mercado = new Flavor_Trading_IA_Mercado();
        $datos_token = $mercado->obtener_precio($token_compra);

        if (!$datos_token || empty($datos_token['precio_usd'])) {
            return array(
                'success' => false,
                'error'   => sprintf(__('No se pudo obtener el precio de %s', 'flavor-chat-ia'), $token_compra),
            );
        }

        $precio_token = floatval($datos_token['precio_usd']);

        $paper_trading = new Flavor_Trading_IA_Paper_Trading(
            $usuario_id,
            $this->get_setting('balance_inicial', 1000.0)
        );

        $resultado_compra = $paper_trading->ejecutar_compra($token_compra, $cantidad_usd, $precio_token);

        return array(
            'success'   => $resultado_compra['exito'],
            'resultado' => $resultado_compra,
        );
    }

    /**
     * Accion: Ejecutar venta manual
     *
     * @param array $parametros Parametros de la accion
     * @return array
     */
    private function action_ejecutar_venta_manual($parametros) {
        $usuario_id = get_current_user_id() ?: $this->obtener_usuario_trading();
        $token_venta = isset($parametros['token']) ? strtoupper(sanitize_text_field($parametros['token'])) : '';
        $cantidad_venta = isset($parametros['cantidad']) ? floatval($parametros['cantidad']) : null;

        if (empty($token_venta)) {
            return array(
                'success' => false,
                'error'   => __('Debes especificar el token a vender', 'flavor-chat-ia'),
            );
        }

        $mercado = new Flavor_Trading_IA_Mercado();
        $precios_simples = $mercado->obtener_precios_simples(array($token_venta));

        $paper_trading = new Flavor_Trading_IA_Paper_Trading(
            $usuario_id,
            $this->get_setting('balance_inicial', 1000.0)
        );
        $paper_trading->actualizar_precios($precios_simples);

        $resultado_venta = $paper_trading->ejecutar_venta($token_venta, $cantidad_venta);

        return array(
            'success'   => $resultado_venta['exito'],
            'resultado' => $resultado_venta,
        );
    }

    /**
     * Accion: Iniciar bot automatico
     *
     * @param array $parametros Parametros de la accion
     * @return array
     */
    private function action_iniciar_bot($parametros) {
        if ($this->get_setting('bot_activo', false)) {
            return array(
                'success' => true,
                'mensaje' => __('El bot ya esta activo.', 'flavor-chat-ia'),
            );
        }

        $this->update_setting('bot_activo', true);
        $this->programar_cron();

        $intervalo_configurado = $this->get_setting('intervalo_analisis', 60);

        return array(
            'success' => true,
            'mensaje' => sprintf(
                __('Bot de trading iniciado. Ciclo de analisis cada %d segundos. IMPORTANTE: Para intervalos menores a 60 segundos, configura un cron real del servidor.', 'flavor-chat-ia'),
                $intervalo_configurado
            ),
        );
    }

    /**
     * Accion: Detener bot automatico
     *
     * @param array $parametros Parametros de la accion
     * @return array
     */
    private function action_detener_bot($parametros) {
        $this->update_setting('bot_activo', false);
        $this->detener_cron();

        return array(
            'success' => true,
            'mensaje' => __('Bot de trading detenido.', 'flavor-chat-ia'),
        );
    }

    /**
     * Accion: Obtener historial de trades
     *
     * @param array $parametros Parametros de la accion
     * @return array
     */
    private function action_obtener_historial_trades($parametros) {
        $usuario_id = get_current_user_id() ?: $this->obtener_usuario_trading();
        $limite = isset($parametros['limite']) ? absint($parametros['limite']) : 20;

        global $wpdb;
        $tabla_trades = $wpdb->prefix . 'flavor_trading_ia_trades';

        $trades_recientes = $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM $tabla_trades WHERE usuario_id = %d ORDER BY timestamp DESC LIMIT %d",
            $usuario_id,
            $limite
        ), ARRAY_A);

        return array(
            'success' => true,
            'total'   => count($trades_recientes),
            'trades'  => $trades_recientes ?: array(),
        );
    }

    /**
     * Accion: Obtener reglas dinamicas
     *
     * @param array $parametros Parametros de la accion
     * @return array
     */
    private function action_obtener_reglas($parametros) {
        $usuario_id = get_current_user_id() ?: $this->obtener_usuario_trading();
        $gestor_reglas = new Flavor_Trading_IA_Reglas_Dinamicas($usuario_id);

        return array(
            'success' => true,
            'reglas'  => $gestor_reglas->obtener_reglas(),
            'estado'  => $gestor_reglas->obtener_estado(),
        );
    }

    /**
     * Accion: Crear regla de trading
     *
     * @param array $parametros Parametros de la accion
     * @return array
     */
    private function action_crear_regla($parametros) {
        $usuario_id = get_current_user_id() ?: $this->obtener_usuario_trading();

        $nombre_regla = sanitize_text_field($parametros['nombre'] ?? '');
        $token_condicion = strtoupper(sanitize_text_field($parametros['token'] ?? '*'));
        $indicador_regla = sanitize_text_field($parametros['indicador'] ?? 'precio');
        $operador_regla = sanitize_text_field($parametros['operador'] ?? '>');
        $valor_regla = floatval($parametros['valor'] ?? 0);
        $accion_tipo_regla = sanitize_text_field($parametros['accion_tipo'] ?? 'alerta');
        $accion_parametros_regla = isset($parametros['accion_parametros']) ? $parametros['accion_parametros'] : array();
        $razon_regla = sanitize_text_field($parametros['razon'] ?? '');

        if (empty($nombre_regla)) {
            return array(
                'success' => false,
                'error'   => __('Debes especificar un nombre para la regla', 'flavor-chat-ia'),
            );
        }

        $operadores_validos = array('>', '<', '>=', '<=', '==');
        if (!in_array($operador_regla, $operadores_validos, true)) {
            return array(
                'success' => false,
                'error'   => sprintf(
                    __('Operador invalido. Usa: %s', 'flavor-chat-ia'),
                    implode(', ', $operadores_validos)
                ),
            );
        }

        $acciones_validas = array('alerta', 'bloquear_compra', 'bloquear_venta', 'reducir_posicion', 'forzar_venta', 'ajustar_parametro');
        if (!in_array($accion_tipo_regla, $acciones_validas, true)) {
            return array(
                'success' => false,
                'error'   => sprintf(
                    __('Tipo de accion invalido. Usa: %s', 'flavor-chat-ia'),
                    implode(', ', $acciones_validas)
                ),
            );
        }

        $gestor_reglas = new Flavor_Trading_IA_Reglas_Dinamicas($usuario_id);
        $resultado_regla = $gestor_reglas->agregar_regla(
            $nombre_regla,
            $token_condicion,
            $indicador_regla,
            $operador_regla,
            $valor_regla,
            $accion_tipo_regla,
            $accion_parametros_regla,
            $razon_regla,
            'usuario'
        );

        return array(
            'success'   => $resultado_regla['exito'],
            'resultado' => $resultado_regla,
        );
    }

    /**
     * Accion: Eliminar regla
     *
     * @param array $parametros Parametros de la accion
     * @return array
     */
    private function action_eliminar_regla($parametros) {
        $usuario_id = get_current_user_id() ?: $this->obtener_usuario_trading();
        $regla_id = sanitize_text_field($parametros['regla_id'] ?? '');

        if (empty($regla_id)) {
            return array(
                'success' => false,
                'error'   => __('Debes especificar el ID de la regla a eliminar', 'flavor-chat-ia'),
            );
        }

        $gestor_reglas = new Flavor_Trading_IA_Reglas_Dinamicas($usuario_id);
        $eliminada = $gestor_reglas->eliminar_regla($regla_id);

        return array(
            'success' => $eliminada,
            'mensaje' => $eliminada
                ? __('Regla eliminada correctamente', 'flavor-chat-ia')
                : __('No se pudo eliminar la regla', 'flavor-chat-ia'),
        );
    }

    /**
     * Accion: Actualizar parametros de trading
     *
     * @param array $parametros Parametros de la accion
     * @return array
     */
    private function action_actualizar_parametros($parametros) {
        $parametros_trading = isset($parametros['parametros']) ? $parametros['parametros'] : $parametros;

        $parametros_permitidos = array(
            'agresividad'              => array('min' => 1, 'max' => 10, 'tipo' => 'int'),
            'riesgo_maximo_porcentaje' => array('min' => 1.0, 'max' => 25.0, 'tipo' => 'float'),
            'stop_loss_porcentaje'     => array('min' => 1.0, 'max' => 20.0, 'tipo' => 'float'),
            'take_profit_porcentaje'   => array('min' => 1.0, 'max' => 50.0, 'tipo' => 'float'),
            'intervalo_analisis'       => array('min' => 30, 'max' => 300, 'tipo' => 'int'),
            'confianza_minima_trade'   => array('min' => 30, 'max' => 90, 'tipo' => 'int'),
            'auto_ajuste_enabled'      => array('tipo' => 'bool'),
            'max_trades_por_hora'      => array('min' => 1, 'max' => 50, 'tipo' => 'int'),
            'max_posiciones_abiertas'  => array('min' => 1, 'max' => 20, 'tipo' => 'int'),
            'stop_loss_global'         => array('min' => 5.0, 'max' => 30.0, 'tipo' => 'float'),
        );

        $cambios_realizados = array();

        foreach ($parametros_permitidos as $nombre_parametro => $restricciones_parametro) {
            if (!isset($parametros_trading[$nombre_parametro])) {
                continue;
            }

            $valor_nuevo = $parametros_trading[$nombre_parametro];

            if ('bool' === $restricciones_parametro['tipo']) {
                $valor_nuevo = (bool) $valor_nuevo;
            } elseif ('int' === $restricciones_parametro['tipo']) {
                $valor_nuevo = intval($valor_nuevo);
                $valor_nuevo = max($restricciones_parametro['min'], min($restricciones_parametro['max'], $valor_nuevo));
            } elseif ('float' === $restricciones_parametro['tipo']) {
                $valor_nuevo = floatval($valor_nuevo);
                $valor_nuevo = max($restricciones_parametro['min'], min($restricciones_parametro['max'], $valor_nuevo));
            }

            $valor_anterior = $this->get_setting($nombre_parametro);
            $this->update_setting($nombre_parametro, $valor_nuevo);

            $cambios_realizados[$nombre_parametro] = array(
                'anterior' => $valor_anterior,
                'nuevo'    => $valor_nuevo,
            );
        }

        // Si cambio el intervalo y el bot esta activo, reprogramar cron
        if (isset($cambios_realizados['intervalo_analisis']) && $this->get_setting('bot_activo', false)) {
            $this->detener_cron();
            $this->programar_cron();
        }

        return array(
            'success' => true,
            'cambios' => $cambios_realizados,
            'mensaje' => sprintf(
                __('%d parametros actualizados', 'flavor-chat-ia'),
                count($cambios_realizados)
            ),
        );
    }

    /**
     * Accion: Reset paper trading
     *
     * @param array $parametros Parametros de la accion
     * @return array
     */
    private function action_reset_paper_trading($parametros) {
        $usuario_id = get_current_user_id() ?: $this->obtener_usuario_trading();
        $balance_inicial = $this->get_setting('balance_inicial', 1000.0);

        $paper_trading = new Flavor_Trading_IA_Paper_Trading($usuario_id, $balance_inicial);
        $paper_trading->reset();

        return array(
            'success' => true,
            'mensaje' => sprintf(
                __('Paper trading reiniciado con balance de $%.2f', 'flavor-chat-ia'),
                $balance_inicial
            ),
        );
    }

    /**
     * Accion: Obtener estado de riesgo
     *
     * @param array $parametros Parametros de la accion
     * @return array
     */
    private function action_obtener_estado_riesgo($parametros) {
        $usuario_id = get_current_user_id() ?: $this->obtener_usuario_trading();

        $paper_trading = new Flavor_Trading_IA_Paper_Trading(
            $usuario_id,
            $this->get_setting('balance_inicial', 1000.0)
        );

        $mercado = new Flavor_Trading_IA_Mercado();
        $tokens_monitoreados = $this->get_setting('tokens_monitoreados', array('SOL'));
        $precios_simples = $mercado->obtener_precios_simples($tokens_monitoreados);
        $paper_trading->actualizar_precios($precios_simples);

        $portfolio = $paper_trading->obtener_estado_portfolio();
        $configuracion_riesgo = $this->obtener_configuracion_riesgo();
        $gestor_riesgo = new Flavor_Trading_IA_Gestor_Riesgo($configuracion_riesgo, $usuario_id);

        $estado_riesgo = $gestor_riesgo->obtener_estado_riesgo(
            $portfolio['balance_total_usd'],
            $portfolio['posiciones']
        );

        return array(
            'success' => true,
            'riesgo'  => $estado_riesgo,
            'limites' => $gestor_riesgo->obtener_resumen(),
        );
    }

    // =========================================================================
    // REST API Routes
    // =========================================================================

    /**
     * Registra las rutas REST API
     */
    public function register_rest_routes() {
        $namespace = 'flavor/v1';

        // Rutas publicas (solo lectura)
        register_rest_route($namespace, '/trading-ia/mercado', array(
            'methods'             => 'GET',
            'callback'            => array($this, 'api_obtener_mercado'),
            'permission_callback' => [$this, 'public_permission_check'],
        ));

        register_rest_route($namespace, '/trading-ia/mercado/(?P<token>[a-zA-Z]+)', array(
            'methods'             => 'GET',
            'callback'            => array($this, 'api_obtener_token'),
            'permission_callback' => [$this, 'public_permission_check'],
        ));

        // Rutas que requieren autenticacion
        register_rest_route($namespace, '/trading-ia/estado', array(
            'methods'             => 'GET',
            'callback'            => array($this, 'api_obtener_estado'),
            'permission_callback' => array($this, 'check_user_logged_in'),
        ));

        register_rest_route($namespace, '/trading-ia/portfolio', array(
            'methods'             => 'GET',
            'callback'            => array($this, 'api_obtener_portfolio'),
            'permission_callback' => array($this, 'check_user_logged_in'),
        ));

        register_rest_route($namespace, '/trading-ia/indicadores/(?P<token>[a-zA-Z]+)', array(
            'methods'             => 'GET',
            'callback'            => array($this, 'api_obtener_indicadores'),
            'permission_callback' => array($this, 'check_user_logged_in'),
        ));

        register_rest_route($namespace, '/trading-ia/comprar', array(
            'methods'             => 'POST',
            'callback'            => array($this, 'api_ejecutar_compra'),
            'permission_callback' => array($this, 'check_user_logged_in'),
        ));

        register_rest_route($namespace, '/trading-ia/vender', array(
            'methods'             => 'POST',
            'callback'            => array($this, 'api_ejecutar_venta'),
            'permission_callback' => array($this, 'check_user_logged_in'),
        ));

        register_rest_route($namespace, '/trading-ia/bot/iniciar', array(
            'methods'             => 'POST',
            'callback'            => array($this, 'api_iniciar_bot'),
            'permission_callback' => array($this, 'check_user_can_manage'),
        ));

        register_rest_route($namespace, '/trading-ia/bot/detener', array(
            'methods'             => 'POST',
            'callback'            => array($this, 'api_detener_bot'),
            'permission_callback' => array($this, 'check_user_can_manage'),
        ));

        register_rest_route($namespace, '/trading-ia/historial', array(
            'methods'             => 'GET',
            'callback'            => array($this, 'api_obtener_historial'),
            'permission_callback' => array($this, 'check_user_logged_in'),
        ));

        register_rest_route($namespace, '/trading-ia/reglas', array(
            'methods'             => 'GET',
            'callback'            => array($this, 'api_obtener_reglas'),
            'permission_callback' => array($this, 'check_user_logged_in'),
        ));

        register_rest_route($namespace, '/trading-ia/reglas', array(
            'methods'             => 'POST',
            'callback'            => array($this, 'api_crear_regla'),
            'permission_callback' => array($this, 'check_user_logged_in'),
        ));

        register_rest_route($namespace, '/trading-ia/reglas/(?P<id>\d+)', array(
            'methods'             => 'DELETE',
            'callback'            => array($this, 'api_eliminar_regla'),
            'permission_callback' => array($this, 'check_user_logged_in'),
        ));

        register_rest_route($namespace, '/trading-ia/parametros', array(
            'methods'             => 'POST',
            'callback'            => array($this, 'api_actualizar_parametros'),
            'permission_callback' => array($this, 'check_user_can_manage'),
        ));

        register_rest_route($namespace, '/trading-ia/reset', array(
            'methods'             => 'POST',
            'callback'            => array($this, 'api_reset_paper_trading'),
            'permission_callback' => array($this, 'check_user_can_manage'),
        ));

        register_rest_route($namespace, '/trading-ia/riesgo', array(
            'methods'             => 'GET',
            'callback'            => array($this, 'api_estado_riesgo'),
            'permission_callback' => array($this, 'check_user_logged_in'),
        ));

        register_rest_route($namespace, '/trading-ia/estadisticas', array(
            'methods'             => 'GET',
            'callback'            => array($this, 'api_obtener_estadisticas'),
            'permission_callback' => array($this, 'check_user_logged_in'),
        ));

        register_rest_route($namespace, '/trading-ia/alertas', array(
            'methods'             => 'GET',
            'callback'            => array($this, 'api_obtener_alertas'),
            'permission_callback' => array($this, 'check_user_logged_in'),
        ));

        register_rest_route($namespace, '/trading-ia/alertas', array(
            'methods'             => 'POST',
            'callback'            => array($this, 'api_crear_alerta'),
            'permission_callback' => array($this, 'check_user_logged_in'),
        ));
    }

    /**
     * Verifica si el usuario esta logueado
     *
     * @return bool
     */
    public function check_user_logged_in() {
        return is_user_logged_in();
    }

    /**
     * Verifica si el usuario puede gestionar el trading
     *
     * @return bool
     */
    public function check_user_can_manage() {
        return current_user_can('manage_options') || current_user_can('edit_posts');
    }

    // =========================================================================
    // REST API Callbacks
    // =========================================================================

    /**
     * API: Obtener datos de mercado
     *
     * @param WP_REST_Request $request Request object
     * @return WP_REST_Response
     */
    public function api_obtener_mercado($request) {
        $tokens_param = $request->get_param('tokens');
        $resultado = $this->action_obtener_datos_mercado(array('tokens' => $tokens_param));
        return rest_ensure_response($resultado);
    }

    /**
     * API: Obtener datos de un token especifico
     *
     * @param WP_REST_Request $request Request object
     * @return WP_REST_Response
     */
    public function api_obtener_token($request) {
        $token = $request->get_param('token');
        $resultado = $this->action_obtener_indicadores(array('token' => $token));
        return rest_ensure_response($resultado);
    }

    /**
     * API: Obtener estado del bot
     *
     * @param WP_REST_Request $request Request object
     * @return WP_REST_Response
     */
    public function api_obtener_estado($request) {
        $resultado = $this->action_obtener_estado(array());
        return rest_ensure_response($resultado);
    }

    /**
     * API: Obtener portfolio
     *
     * @param WP_REST_Request $request Request object
     * @return WP_REST_Response
     */
    public function api_obtener_portfolio($request) {
        $resultado = $this->action_obtener_portfolio(array());
        return rest_ensure_response($resultado);
    }

    /**
     * API: Obtener indicadores de un token
     *
     * @param WP_REST_Request $request Request object
     * @return WP_REST_Response
     */
    public function api_obtener_indicadores($request) {
        $token = $request->get_param('token');
        $resultado = $this->action_obtener_indicadores(array('token' => $token));
        return rest_ensure_response($resultado);
    }

    /**
     * API: Ejecutar compra
     *
     * @param WP_REST_Request $request Request object
     * @return WP_REST_Response
     */
    public function api_ejecutar_compra($request) {
        $params = $request->get_json_params();
        $resultado = $this->action_ejecutar_compra_manual(array(
            'token'       => $params['token'] ?? '',
            'cantidad_usd' => $params['cantidad_usd'] ?? 0,
        ));

        if ($resultado['success']) {
            $this->integrar_gamificacion_compra($resultado);
            $this->enviar_notificacion_trade('compra', $resultado);
        }

        return rest_ensure_response($resultado);
    }

    /**
     * API: Ejecutar venta
     *
     * @param WP_REST_Request $request Request object
     * @return WP_REST_Response
     */
    public function api_ejecutar_venta($request) {
        $params = $request->get_json_params();
        $resultado = $this->action_ejecutar_venta_manual(array(
            'token'    => $params['token'] ?? '',
            'cantidad' => $params['cantidad'] ?? null,
        ));

        if ($resultado['success']) {
            $this->integrar_gamificacion_venta($resultado);
            $this->enviar_notificacion_trade('venta', $resultado);
        }

        return rest_ensure_response($resultado);
    }

    /**
     * API: Iniciar bot
     *
     * @param WP_REST_Request $request Request object
     * @return WP_REST_Response
     */
    public function api_iniciar_bot($request) {
        $resultado = $this->action_iniciar_bot(array());
        return rest_ensure_response($resultado);
    }

    /**
     * API: Detener bot
     *
     * @param WP_REST_Request $request Request object
     * @return WP_REST_Response
     */
    public function api_detener_bot($request) {
        $resultado = $this->action_detener_bot(array());
        return rest_ensure_response($resultado);
    }

    /**
     * API: Obtener historial de trades
     *
     * @param WP_REST_Request $request Request object
     * @return WP_REST_Response
     */
    public function api_obtener_historial($request) {
        $limite = $request->get_param('limite') ?: 20;
        $resultado = $this->action_obtener_historial_trades(array('limite' => $limite));
        return rest_ensure_response($resultado);
    }

    /**
     * API: Obtener reglas
     *
     * @param WP_REST_Request $request Request object
     * @return WP_REST_Response
     */
    public function api_obtener_reglas($request) {
        $resultado = $this->action_obtener_reglas(array());
        return rest_ensure_response($resultado);
    }

    /**
     * API: Crear regla
     *
     * @param WP_REST_Request $request Request object
     * @return WP_REST_Response
     */
    public function api_crear_regla($request) {
        $params = $request->get_json_params();
        $resultado = $this->action_crear_regla($params);
        return rest_ensure_response($resultado);
    }

    /**
     * API: Eliminar regla
     *
     * @param WP_REST_Request $request Request object
     * @return WP_REST_Response
     */
    public function api_eliminar_regla($request) {
        $regla_id = $request->get_param('id');
        $resultado = $this->action_eliminar_regla(array('regla_id' => $regla_id));
        return rest_ensure_response($resultado);
    }

    /**
     * API: Actualizar parametros
     *
     * @param WP_REST_Request $request Request object
     * @return WP_REST_Response
     */
    public function api_actualizar_parametros($request) {
        $params = $request->get_json_params();
        $resultado = $this->action_actualizar_parametros(array('parametros' => $params));
        return rest_ensure_response($resultado);
    }

    /**
     * API: Reset paper trading
     *
     * @param WP_REST_Request $request Request object
     * @return WP_REST_Response
     */
    public function api_reset_paper_trading($request) {
        $resultado = $this->action_reset_paper_trading(array());
        return rest_ensure_response($resultado);
    }

    /**
     * API: Estado de riesgo
     *
     * @param WP_REST_Request $request Request object
     * @return WP_REST_Response
     */
    public function api_estado_riesgo($request) {
        $resultado = $this->action_obtener_estado_riesgo(array());
        return rest_ensure_response($resultado);
    }

    /**
     * API: Obtener estadisticas del usuario
     *
     * @param WP_REST_Request $request Request object
     * @return WP_REST_Response
     */
    public function api_obtener_estadisticas($request) {
        $usuario_id = get_current_user_id();
        $estadisticas = $this->calcular_estadisticas_usuario($usuario_id);
        return rest_ensure_response(array(
            'success'      => true,
            'estadisticas' => $estadisticas,
        ));
    }

    /**
     * API: Obtener alertas de precio
     *
     * @param WP_REST_Request $request Request object
     * @return WP_REST_Response
     */
    public function api_obtener_alertas($request) {
        $usuario_id = get_current_user_id();
        $alertas = $this->obtener_alertas_usuario($usuario_id);
        return rest_ensure_response(array(
            'success' => true,
            'alertas' => $alertas,
        ));
    }

    /**
     * API: Crear alerta de precio
     *
     * @param WP_REST_Request $request Request object
     * @return WP_REST_Response
     */
    public function api_crear_alerta($request) {
        $params = $request->get_json_params();
        $resultado = $this->crear_alerta_precio(
            $params['token'] ?? '',
            $params['tipo'] ?? 'above',
            $params['precio'] ?? 0
        );
        return rest_ensure_response($resultado);
    }

    // =========================================================================
    // AJAX Handlers
    // =========================================================================

    /**
     * AJAX: Obtener estado
     */
    public function ajax_obtener_estado() {
        check_ajax_referer('trading_ia_nonce', 'nonce');
        $resultado = $this->action_obtener_estado(array());
        wp_send_json($resultado);
    }

    /**
     * AJAX: Obtener portfolio
     */
    public function ajax_obtener_portfolio() {
        check_ajax_referer('trading_ia_nonce', 'nonce');
        $resultado = $this->action_obtener_portfolio(array());
        wp_send_json($resultado);
    }

    /**
     * AJAX: Obtener mercado
     */
    public function ajax_obtener_mercado() {
        check_ajax_referer('trading_ia_nonce', 'nonce');
        $tokens = isset($_POST['tokens']) ? sanitize_text_field($_POST['tokens']) : '';
        $resultado = $this->action_obtener_datos_mercado(array('tokens' => $tokens));
        wp_send_json($resultado);
    }

    /**
     * AJAX: Obtener indicadores
     */
    public function ajax_obtener_indicadores() {
        check_ajax_referer('trading_ia_nonce', 'nonce');
        $token = isset($_POST['token']) ? sanitize_text_field($_POST['token']) : '';
        $resultado = $this->action_obtener_indicadores(array('token' => $token));
        wp_send_json($resultado);
    }

    /**
     * AJAX: Ejecutar compra
     */
    public function ajax_ejecutar_compra() {
        check_ajax_referer('trading_ia_nonce', 'nonce');

        $token = isset($_POST['token']) ? sanitize_text_field($_POST['token']) : '';
        $cantidad_usd = isset($_POST['cantidad_usd']) ? floatval($_POST['cantidad_usd']) : 0;

        $resultado = $this->action_ejecutar_compra_manual(array(
            'token'       => $token,
            'cantidad_usd' => $cantidad_usd,
        ));

        if ($resultado['success']) {
            $this->integrar_gamificacion_compra($resultado);
            $this->enviar_notificacion_trade('compra', $resultado);
        }

        wp_send_json($resultado);
    }

    /**
     * AJAX: Ejecutar venta
     */
    public function ajax_ejecutar_venta() {
        check_ajax_referer('trading_ia_nonce', 'nonce');

        $token = isset($_POST['token']) ? sanitize_text_field($_POST['token']) : '';
        $cantidad = isset($_POST['cantidad']) ? floatval($_POST['cantidad']) : null;

        $resultado = $this->action_ejecutar_venta_manual(array(
            'token'    => $token,
            'cantidad' => $cantidad,
        ));

        if ($resultado['success']) {
            $this->integrar_gamificacion_venta($resultado);
            $this->enviar_notificacion_trade('venta', $resultado);
        }

        wp_send_json($resultado);
    }

    /**
     * AJAX: Iniciar bot
     */
    public function ajax_iniciar_bot() {
        check_ajax_referer('trading_ia_nonce', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_send_json(array('success' => false, 'error' => __('Sin permisos', 'flavor-chat-ia')));
        }

        $resultado = $this->action_iniciar_bot(array());
        wp_send_json($resultado);
    }

    /**
     * AJAX: Detener bot
     */
    public function ajax_detener_bot() {
        check_ajax_referer('trading_ia_nonce', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_send_json(array('success' => false, 'error' => __('Sin permisos', 'flavor-chat-ia')));
        }

        $resultado = $this->action_detener_bot(array());
        wp_send_json($resultado);
    }

    /**
     * AJAX: Historial de trades
     */
    public function ajax_historial_trades() {
        check_ajax_referer('trading_ia_nonce', 'nonce');
        $limite = isset($_POST['limite']) ? absint($_POST['limite']) : 20;
        $resultado = $this->action_obtener_historial_trades(array('limite' => $limite));
        wp_send_json($resultado);
    }

    /**
     * AJAX: Obtener reglas
     */
    public function ajax_obtener_reglas() {
        check_ajax_referer('trading_ia_nonce', 'nonce');
        $resultado = $this->action_obtener_reglas(array());
        wp_send_json($resultado);
    }

    /**
     * AJAX: Crear regla
     */
    public function ajax_crear_regla() {
        check_ajax_referer('trading_ia_nonce', 'nonce');

        $resultado = $this->action_crear_regla(array(
            'nombre'      => isset($_POST['nombre']) ? sanitize_text_field($_POST['nombre']) : '',
            'token'       => isset($_POST['token']) ? sanitize_text_field($_POST['token']) : '*',
            'indicador'   => isset($_POST['indicador']) ? sanitize_text_field($_POST['indicador']) : 'precio',
            'operador'    => isset($_POST['operador']) ? sanitize_text_field($_POST['operador']) : '>',
            'valor'       => isset($_POST['valor']) ? floatval($_POST['valor']) : 0,
            'accion_tipo' => isset($_POST['accion_tipo']) ? sanitize_text_field($_POST['accion_tipo']) : 'alerta',
            'razon'       => isset($_POST['razon']) ? sanitize_text_field($_POST['razon']) : '',
        ));

        wp_send_json($resultado);
    }

    /**
     * AJAX: Eliminar regla
     */
    public function ajax_eliminar_regla() {
        check_ajax_referer('trading_ia_nonce', 'nonce');
        $regla_id = isset($_POST['regla_id']) ? sanitize_text_field($_POST['regla_id']) : '';
        $resultado = $this->action_eliminar_regla(array('regla_id' => $regla_id));
        wp_send_json($resultado);
    }

    /**
     * AJAX: Actualizar parametros
     */
    public function ajax_actualizar_parametros() {
        check_ajax_referer('trading_ia_nonce', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_send_json(array('success' => false, 'error' => __('Sin permisos', 'flavor-chat-ia')));
        }

        $parametros = isset($_POST['parametros']) ? $_POST['parametros'] : array();
        $resultado = $this->action_actualizar_parametros(array('parametros' => $parametros));
        wp_send_json($resultado);
    }

    /**
     * AJAX: Reset paper trading
     */
    public function ajax_reset_paper_trading() {
        check_ajax_referer('trading_ia_nonce', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_send_json(array('success' => false, 'error' => __('Sin permisos', 'flavor-chat-ia')));
        }

        $resultado = $this->action_reset_paper_trading(array());
        wp_send_json($resultado);
    }

    /**
     * AJAX: Estado de riesgo
     */
    public function ajax_estado_riesgo() {
        check_ajax_referer('trading_ia_nonce', 'nonce');
        $resultado = $this->action_obtener_estado_riesgo(array());
        wp_send_json($resultado);
    }

    /**
     * AJAX: Agregar token a monitoreo
     */
    public function ajax_agregar_token() {
        check_ajax_referer('trading_ia_nonce', 'nonce');

        $token_nuevo = isset($_POST['token']) ? strtoupper(sanitize_text_field($_POST['token'])) : '';

        if (empty($token_nuevo)) {
            wp_send_json(array('success' => false, 'error' => __('Token invalido', 'flavor-chat-ia')));
        }

        $tokens_actuales = $this->get_setting('tokens_monitoreados', array());

        if (in_array($token_nuevo, $tokens_actuales, true)) {
            wp_send_json(array('success' => false, 'error' => __('El token ya esta en la lista', 'flavor-chat-ia')));
        }

        $tokens_actuales[] = $token_nuevo;
        $this->update_setting('tokens_monitoreados', $tokens_actuales);

        wp_send_json(array(
            'success' => true,
            'mensaje' => sprintf(__('Token %s agregado al monitoreo', 'flavor-chat-ia'), $token_nuevo),
            'tokens'  => $tokens_actuales,
        ));
    }

    /**
     * AJAX: Eliminar token del monitoreo
     */
    public function ajax_eliminar_token() {
        check_ajax_referer('trading_ia_nonce', 'nonce');

        $token_eliminar = isset($_POST['token']) ? strtoupper(sanitize_text_field($_POST['token'])) : '';

        if (empty($token_eliminar)) {
            wp_send_json(array('success' => false, 'error' => __('Token invalido', 'flavor-chat-ia')));
        }

        $tokens_actuales = $this->get_setting('tokens_monitoreados', array());
        $tokens_filtrados = array_filter($tokens_actuales, function($token) use ($token_eliminar) {
            return $token !== $token_eliminar;
        });
        $tokens_filtrados = array_values($tokens_filtrados);

        $this->update_setting('tokens_monitoreados', $tokens_filtrados);

        wp_send_json(array(
            'success' => true,
            'mensaje' => sprintf(__('Token %s eliminado del monitoreo', 'flavor-chat-ia'), $token_eliminar),
            'tokens'  => $tokens_filtrados,
        ));
    }

    /**
     * AJAX: Exportar historial de trades
     */
    public function ajax_exportar_historial() {
        check_ajax_referer('trading_ia_nonce', 'nonce');

        $usuario_id = get_current_user_id();

        global $wpdb;
        $tabla_trades = $wpdb->prefix . 'flavor_trading_ia_trades';

        $trades = $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM $tabla_trades WHERE usuario_id = %d ORDER BY timestamp DESC",
            $usuario_id
        ), ARRAY_A);

        if (empty($trades)) {
            wp_send_json(array('success' => false, 'error' => __('No hay trades para exportar', 'flavor-chat-ia')));
        }

        $csv_data = "ID,Tipo,Token,Cantidad,Precio,Total USD,Comision,Ganancia/Perdida,Fecha\n";
        foreach ($trades as $trade) {
            $csv_data .= sprintf(
                "%d,%s,%s,%s,%s,%s,%s,%s,%s\n",
                $trade['id'],
                $trade['tipo'],
                $trade['token'],
                $trade['cantidad'],
                $trade['precio'],
                $trade['total_usd'],
                $trade['comision'],
                $trade['pnl'] ?? '0',
                $trade['timestamp']
            );
        }

        wp_send_json(array(
            'success'  => true,
            'csv'      => $csv_data,
            'filename' => 'trading-ia-historial-' . date('Y-m-d') . '.csv',
        ));
    }

    // =========================================================================
    // Shortcodes
    // =========================================================================

    /**
     * Registra los shortcodes del modulo
     */
    public function register_shortcodes() {
        add_shortcode('trading_ia_dashboard', array($this, 'shortcode_dashboard'));
        add_shortcode('trading_ia_portfolio', array($this, 'shortcode_portfolio'));
        add_shortcode('trading_ia_mercado', array($this, 'shortcode_mercado'));
        add_shortcode('trading_ia_historial', array($this, 'shortcode_historial'));
        add_shortcode('trading_ia_panel_control', array($this, 'shortcode_panel_control'));
        add_shortcode('trading_ia_widget_precio', array($this, 'shortcode_widget_precio'));
    }

    /**
     * Shortcode: Dashboard completo de trading
     *
     * @param array $atts Atributos del shortcode
     * @return string HTML
     */
    public function shortcode_dashboard($atts) {
        $atts = shortcode_atts(array(
            'mostrar_graficos' => 'true',
            'mostrar_reglas'   => 'true',
        ), $atts);

        if (!is_user_logged_in()) {
            return '<div class="trading-ia-login-required">' .
                   '<p>' . __('Debes iniciar sesion para ver el dashboard de trading.', 'flavor-chat-ia') . '</p>' .
                   '<a href="' . wp_login_url(flavor_current_request_url()) . '" class="button">' . __('Iniciar sesion', 'flavor-chat-ia') . '</a>' .
                   '</div>';
        }

        $this->enqueue_frontend_assets();

        $estado = $this->action_obtener_estado(array());
        $portfolio = $this->action_obtener_portfolio(array());

        ob_start();
        ?>
        <div class="trading-ia-dashboard" data-mostrar-graficos="<?php echo esc_attr($atts['mostrar_graficos']); ?>">
            <div class="trading-ia-header">
                <h2><?php _e('Trading IA - Paper Trading', 'flavor-chat-ia'); ?></h2>
                <div class="trading-ia-bot-status <?php echo $estado['estado']['bot_activo'] ? 'activo' : 'inactivo'; ?>">
                    <span class="status-indicator"></span>
                    <span class="status-text">
                        <?php echo $estado['estado']['bot_activo'] ? __('Bot Activo', 'flavor-chat-ia') : __('Bot Inactivo', 'flavor-chat-ia'); ?>
                    </span>
                </div>
            </div>

            <div class="trading-ia-grid">
                <div class="trading-ia-card balance-card">
                    <h3><?php _e('Balance', 'flavor-chat-ia'); ?></h3>
                    <div class="balance-total">
                        $<?php echo number_format($portfolio['portfolio']['balance_total_usd'] ?? 0, 2); ?>
                    </div>
                    <div class="balance-details">
                        <span class="disponible">
                            <?php _e('Disponible:', 'flavor-chat-ia'); ?>
                            $<?php echo number_format($portfolio['portfolio']['balance_disponible'] ?? 0, 2); ?>
                        </span>
                        <span class="pnl <?php echo ($portfolio['portfolio']['pnl_total'] ?? 0) >= 0 ? 'positivo' : 'negativo'; ?>">
                            <?php _e('P&L:', 'flavor-chat-ia'); ?>
                            <?php echo ($portfolio['portfolio']['pnl_total'] ?? 0) >= 0 ? '+' : ''; ?>$<?php echo number_format($portfolio['portfolio']['pnl_total'] ?? 0, 2); ?>
                        </span>
                    </div>
                </div>

                <div class="trading-ia-card stats-card">
                    <h3><?php _e('Estadisticas', 'flavor-chat-ia'); ?></h3>
                    <div class="stats-grid">
                        <div class="stat">
                            <span class="label"><?php _e('Posiciones', 'flavor-chat-ia'); ?></span>
                            <span class="value"><?php echo count($portfolio['portfolio']['posiciones'] ?? array()); ?></span>
                        </div>
                        <div class="stat">
                            <span class="label"><?php _e('Agresividad', 'flavor-chat-ia'); ?></span>
                            <span class="value"><?php echo $estado['estado']['agresividad']; ?>/10</span>
                        </div>
                        <div class="stat">
                            <span class="label"><?php _e('Confianza min.', 'flavor-chat-ia'); ?></span>
                            <span class="value"><?php echo $estado['estado']['confianza_minima']; ?>%</span>
                        </div>
                    </div>
                </div>

                <div class="trading-ia-card posiciones-card">
                    <h3><?php _e('Posiciones Abiertas', 'flavor-chat-ia'); ?></h3>
                    <div class="posiciones-lista">
                        <?php if (!empty($portfolio['portfolio']['posiciones'])): ?>
                            <?php foreach ($portfolio['portfolio']['posiciones'] as $posicion): ?>
                                <div class="posicion-item">
                                    <span class="token"><?php echo esc_html($posicion['token']); ?></span>
                                    <span class="cantidad"><?php echo number_format($posicion['cantidad'], 6); ?></span>
                                    <span class="valor">$<?php echo number_format($posicion['valor_actual'] ?? 0, 2); ?></span>
                                    <span class="pnl <?php echo ($posicion['pnl'] ?? 0) >= 0 ? 'positivo' : 'negativo'; ?>">
                                        <?php echo ($posicion['pnl'] ?? 0) >= 0 ? '+' : ''; ?><?php echo number_format($posicion['pnl_porcentaje'] ?? 0, 2); ?>%
                                    </span>
                                </div>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <p class="sin-posiciones"><?php _e('Sin posiciones abiertas', 'flavor-chat-ia'); ?></p>
                        <?php endif; ?>
                    </div>
                </div>

                <div class="trading-ia-card trading-form-card">
                    <h3><?php _e('Operar', 'flavor-chat-ia'); ?></h3>
                    <form class="trading-form" id="trading-ia-form">
                        <div class="form-row">
                            <label for="trading-token"><?php _e('Token', 'flavor-chat-ia'); ?></label>
                            <select id="trading-token" name="token">
                                <?php foreach ($estado['estado']['tokens_monitoreados'] as $token): ?>
                                    <option value="<?php echo esc_attr($token); ?>"><?php echo esc_html($token); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="form-row">
                            <label for="trading-cantidad"><?php _e('Cantidad USD', 'flavor-chat-ia'); ?></label>
                            <input type="number" id="trading-cantidad" name="cantidad_usd" step="0.01" min="1" placeholder="100.00">
                        </div>
                        <div class="form-actions">
                            <button type="button" class="btn-comprar" data-action="comprar"><?php _e('Comprar', 'flavor-chat-ia'); ?></button>
                            <button type="button" class="btn-vender" data-action="vender"><?php _e('Vender Todo', 'flavor-chat-ia'); ?></button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
        <?php
        return ob_get_clean();
    }

    /**
     * Shortcode: Portfolio del usuario
     *
     * @param array $atts Atributos del shortcode
     * @return string HTML
     */
    public function shortcode_portfolio($atts) {
        if (!is_user_logged_in()) {
            return '<p class="trading-ia-login-required">' . __('Inicia sesion para ver tu portfolio.', 'flavor-chat-ia') . '</p>';
        }

        $this->enqueue_frontend_assets();
        $portfolio = $this->action_obtener_portfolio(array());

        ob_start();
        ?>
        <div class="trading-ia-portfolio">
            <h3><?php _e('Mi Portfolio', 'flavor-chat-ia'); ?></h3>
            <div class="portfolio-resumen">
                <div class="balance-total">
                    <span class="label"><?php _e('Balance Total', 'flavor-chat-ia'); ?></span>
                    <span class="valor">$<?php echo number_format($portfolio['portfolio']['balance_total_usd'] ?? 0, 2); ?></span>
                </div>
                <div class="pnl-total">
                    <span class="label"><?php _e('Ganancia/Perdida', 'flavor-chat-ia'); ?></span>
                    <span class="valor <?php echo ($portfolio['portfolio']['pnl_total'] ?? 0) >= 0 ? 'positivo' : 'negativo'; ?>">
                        <?php echo ($portfolio['portfolio']['pnl_total'] ?? 0) >= 0 ? '+' : ''; ?>$<?php echo number_format($portfolio['portfolio']['pnl_total'] ?? 0, 2); ?>
                    </span>
                </div>
            </div>
            <table class="portfolio-tabla">
                <thead>
                    <tr>
                        <th><?php _e('Token', 'flavor-chat-ia'); ?></th>
                        <th><?php _e('Cantidad', 'flavor-chat-ia'); ?></th>
                        <th><?php _e('Precio Entrada', 'flavor-chat-ia'); ?></th>
                        <th><?php _e('Precio Actual', 'flavor-chat-ia'); ?></th>
                        <th><?php _e('Valor', 'flavor-chat-ia'); ?></th>
                        <th><?php _e('P&L', 'flavor-chat-ia'); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (!empty($portfolio['portfolio']['posiciones'])): ?>
                        <?php foreach ($portfolio['portfolio']['posiciones'] as $posicion): ?>
                            <tr>
                                <td><?php echo esc_html($posicion['token']); ?></td>
                                <td><?php echo number_format($posicion['cantidad'], 6); ?></td>
                                <td>$<?php echo number_format($posicion['precio_entrada'] ?? 0, 6); ?></td>
                                <td>$<?php echo number_format($posicion['precio_actual'] ?? 0, 6); ?></td>
                                <td>$<?php echo number_format($posicion['valor_actual'] ?? 0, 2); ?></td>
                                <td class="<?php echo ($posicion['pnl'] ?? 0) >= 0 ? 'positivo' : 'negativo'; ?>">
                                    <?php echo ($posicion['pnl'] ?? 0) >= 0 ? '+' : ''; ?><?php echo number_format($posicion['pnl_porcentaje'] ?? 0, 2); ?>%
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="6" class="sin-datos"><?php _e('No tienes posiciones abiertas', 'flavor-chat-ia'); ?></td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
        <?php
        return ob_get_clean();
    }

    /**
     * Shortcode: Precios de mercado
     *
     * @param array $atts Atributos del shortcode
     * @return string HTML
     */
    public function shortcode_mercado($atts) {
        $atts = shortcode_atts(array(
            'tokens' => 'SOL,BONK,JUP,WIF,JTO',
        ), $atts);

        $this->enqueue_frontend_assets();
        $mercado = $this->action_obtener_datos_mercado(array('tokens' => $atts['tokens']));

        ob_start();
        ?>
        <div class="trading-ia-mercado">
            <h3><?php _e('Precios de Mercado', 'flavor-chat-ia'); ?></h3>
            <div class="mercado-grid">
                <?php if (!empty($mercado['mercado'])): ?>
                    <?php foreach ($mercado['mercado'] as $token => $datos): ?>
                        <div class="token-card">
                            <div class="token-header">
                                <span class="token-nombre"><?php echo esc_html($token); ?></span>
                                <span class="token-cambio <?php echo ($datos['cambio_24h'] ?? 0) >= 0 ? 'positivo' : 'negativo'; ?>">
                                    <?php echo ($datos['cambio_24h'] ?? 0) >= 0 ? '+' : ''; ?><?php echo number_format($datos['cambio_24h'] ?? 0, 2); ?>%
                                </span>
                            </div>
                            <div class="token-precio">
                                $<?php $precio = $datos['precio_usd'] ?? 0; echo number_format($precio, $precio < 1 ? 8 : 2); ?>
                            </div>
                            <div class="token-volumen">
                                <?php _e('Vol:', 'flavor-chat-ia'); ?> $<?php echo $this->formatear_numero_grande($datos['volumen_24h'] ?? 0); ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <p><?php _e('No se pudieron cargar los datos del mercado', 'flavor-chat-ia'); ?></p>
                <?php endif; ?>
            </div>
        </div>
        <?php
        return ob_get_clean();
    }

    /**
     * Shortcode: Historial de trades
     *
     * @param array $atts Atributos del shortcode
     * @return string HTML
     */
    public function shortcode_historial($atts) {
        $atts = shortcode_atts(array(
            'limite' => 10,
        ), $atts);

        if (!is_user_logged_in()) {
            return '<p class="trading-ia-login-required">' . __('Inicia sesion para ver tu historial.', 'flavor-chat-ia') . '</p>';
        }

        $this->enqueue_frontend_assets();
        $historial = $this->action_obtener_historial_trades(array('limite' => intval($atts['limite'])));

        ob_start();
        ?>
        <div class="trading-ia-historial">
            <h3><?php _e('Historial de Operaciones', 'flavor-chat-ia'); ?></h3>
            <table class="historial-tabla">
                <thead>
                    <tr>
                        <th><?php _e('Fecha', 'flavor-chat-ia'); ?></th>
                        <th><?php _e('Tipo', 'flavor-chat-ia'); ?></th>
                        <th><?php _e('Token', 'flavor-chat-ia'); ?></th>
                        <th><?php _e('Cantidad', 'flavor-chat-ia'); ?></th>
                        <th><?php _e('Precio', 'flavor-chat-ia'); ?></th>
                        <th><?php _e('Total', 'flavor-chat-ia'); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (!empty($historial['trades'])): ?>
                        <?php foreach ($historial['trades'] as $trade): ?>
                            <tr class="trade-<?php echo esc_attr($trade['tipo']); ?>">
                                <td><?php echo esc_html(date('d/m/Y H:i', strtotime($trade['timestamp']))); ?></td>
                                <td class="tipo-<?php echo esc_attr($trade['tipo']); ?>">
                                    <?php echo $trade['tipo'] === 'compra' ? __('Compra', 'flavor-chat-ia') : __('Venta', 'flavor-chat-ia'); ?>
                                </td>
                                <td><?php echo esc_html($trade['token']); ?></td>
                                <td><?php echo number_format($trade['cantidad'], 6); ?></td>
                                <td>$<?php echo number_format($trade['precio'], 6); ?></td>
                                <td>$<?php echo number_format($trade['total_usd'], 2); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="6" class="sin-datos"><?php _e('No hay operaciones registradas', 'flavor-chat-ia'); ?></td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
        <?php
        return ob_get_clean();
    }

    /**
     * Shortcode: Panel de control (admin)
     *
     * @param array $atts Atributos del shortcode
     * @return string HTML
     */
    public function shortcode_panel_control($atts) {
        if (!current_user_can('manage_options')) {
            return '<p class="trading-ia-no-permisos">' . __('No tienes permisos para acceder al panel de control.', 'flavor-chat-ia') . '</p>';
        }

        $this->enqueue_frontend_assets();
        $estado = $this->action_obtener_estado(array());

        ob_start();
        ?>
        <div class="trading-ia-panel-control">
            <h3><?php _e('Panel de Control - Trading IA', 'flavor-chat-ia'); ?></h3>

            <div class="panel-seccion bot-control">
                <h4><?php _e('Control del Bot', 'flavor-chat-ia'); ?></h4>
                <div class="bot-status">
                    <span class="status-indicator <?php echo $estado['estado']['bot_activo'] ? 'activo' : 'inactivo'; ?>"></span>
                    <span><?php echo $estado['estado']['bot_activo'] ? __('Bot Activo', 'flavor-chat-ia') : __('Bot Inactivo', 'flavor-chat-ia'); ?></span>
                </div>
                <div class="bot-actions">
                    <button class="btn-iniciar-bot" <?php echo $estado['estado']['bot_activo'] ? 'disabled' : ''; ?>>
                        <?php _e('Iniciar Bot', 'flavor-chat-ia'); ?>
                    </button>
                    <button class="btn-detener-bot" <?php echo !$estado['estado']['bot_activo'] ? 'disabled' : ''; ?>>
                        <?php _e('Detener Bot', 'flavor-chat-ia'); ?>
                    </button>
                    <button class="btn-reset" onclick="return confirm('<?php _e('Estas seguro? Se borrara todo el historial.', 'flavor-chat-ia'); ?>')">
                        <?php _e('Reset Simulacion', 'flavor-chat-ia'); ?>
                    </button>
                </div>
            </div>

            <div class="panel-seccion parametros">
                <h4><?php _e('Parametros de Trading', 'flavor-chat-ia'); ?></h4>
                <form class="parametros-form" id="trading-ia-parametros-form">
                    <div class="form-row">
                        <label><?php _e('Agresividad (1-10)', 'flavor-chat-ia'); ?></label>
                        <input type="range" name="agresividad" min="1" max="10" value="<?php echo esc_attr($estado['estado']['agresividad']); ?>">
                        <span class="valor-actual"><?php echo $estado['estado']['agresividad']; ?></span>
                    </div>
                    <div class="form-row">
                        <label><?php _e('Intervalo analisis (seg)', 'flavor-chat-ia'); ?></label>
                        <input type="number" name="intervalo_analisis" min="30" max="300" value="<?php echo esc_attr($estado['estado']['intervalo_analisis']); ?>">
                    </div>
                    <div class="form-row">
                        <label><?php _e('Confianza minima (%)', 'flavor-chat-ia'); ?></label>
                        <input type="number" name="confianza_minima_trade" min="30" max="90" value="<?php echo esc_attr($estado['estado']['confianza_minima']); ?>">
                    </div>
                    <div class="form-row">
                        <label><?php _e('Auto-ajuste', 'flavor-chat-ia'); ?></label>
                        <input type="checkbox" name="auto_ajuste_enabled" <?php checked($estado['estado']['auto_ajuste']['habilitado'] ?? false); ?>>
                    </div>
                    <button type="submit" class="btn-guardar-parametros"><?php _e('Guardar Parametros', 'flavor-chat-ia'); ?></button>
                </form>
            </div>

            <div class="panel-seccion tokens">
                <h4><?php _e('Tokens Monitoreados', 'flavor-chat-ia'); ?></h4>
                <div class="tokens-lista">
                    <?php foreach ($estado['estado']['tokens_monitoreados'] as $token): ?>
                        <span class="token-badge">
                            <?php echo esc_html($token); ?>
                            <button class="btn-eliminar-token" data-token="<?php echo esc_attr($token); ?>"><?php echo esc_html__('&times;', 'flavor-chat-ia'); ?></button>
                        </span>
                    <?php endforeach; ?>
                </div>
                <div class="agregar-token">
                    <input type="text" id="nuevo-token" placeholder="<?php _e('Ej: PYTH', 'flavor-chat-ia'); ?>">
                    <button class="btn-agregar-token"><?php _e('Agregar', 'flavor-chat-ia'); ?></button>
                </div>
            </div>
        </div>
        <?php
        return ob_get_clean();
    }

    /**
     * Shortcode: Widget de precio individual
     *
     * @param array $atts Atributos del shortcode
     * @return string HTML
     */
    public function shortcode_widget_precio($atts) {
        $atts = shortcode_atts(array(
            'token' => 'SOL',
        ), $atts);

        $mercado = $this->action_obtener_datos_mercado(array('tokens' => $atts['token']));
        $token = strtoupper($atts['token']);
        $datos = $mercado['mercado'][$token] ?? array();

        ob_start();
        ?>
        <div class="trading-ia-widget-precio" data-token="<?php echo esc_attr($token); ?>">
            <span class="widget-token"><?php echo esc_html($token); ?></span>
            <span class="widget-precio">$<?php echo number_format($datos['precio_usd'] ?? 0, $datos['precio_usd'] < 1 ? 8 : 2); ?></span>
            <span class="widget-cambio <?php echo ($datos['cambio_24h'] ?? 0) >= 0 ? 'positivo' : 'negativo'; ?>">
                <?php echo ($datos['cambio_24h'] ?? 0) >= 0 ? '+' : ''; ?><?php echo number_format($datos['cambio_24h'] ?? 0, 2); ?>%
            </span>
        </div>
        <?php
        return ob_get_clean();
    }

    /**
     * Encola assets del frontend
     */
    private function enqueue_frontend_assets() {
        if (!$this->can_activate()) {
            return;
        }

        wp_enqueue_script(
            'trading-ia-frontend',
            $this->get_module_url() . 'assets/js/trading-ia-frontend.js',
            array('jquery'),
            '1.0.0',
            true
        );

        wp_localize_script('trading-ia-frontend', 'tradingIAData', array(
            'ajax_url' => admin_url('admin-ajax.php'),
            'rest_url' => rest_url('flavor/v1/trading-ia'),
            'nonce'    => wp_create_nonce('trading_ia_nonce'),
            'i18n'     => array(
                'confirmar_compra'  => __('Confirmar compra?', 'flavor-chat-ia'),
                'confirmar_venta'   => __('Confirmar venta?', 'flavor-chat-ia'),
                'operacion_exitosa' => __('Operacion realizada con exito', 'flavor-chat-ia'),
                'error_operacion'   => __('Error en la operacion', 'flavor-chat-ia'),
            ),
        ));

        wp_enqueue_style(
            'trading-ia-frontend',
            $this->get_module_url() . 'assets/css/trading-ia-frontend.css',
            array(),
            '1.0.0'
        );
    }

    // =========================================================================
    // WP Cron Jobs Adicionales
    // =========================================================================

    /**
     * Genera reporte diario de trading
     */
    public function generar_reporte_diario() {
        global $wpdb;
        $tabla_trades = $wpdb->prefix . 'flavor_trading_ia_trades';

        $fecha_ayer = date('Y-m-d', strtotime('-1 day'));

        $resumen_diario = $wpdb->get_row($wpdb->prepare(
            "SELECT
                COUNT(*) as total_trades,
                SUM(CASE WHEN tipo = 'compra' THEN 1 ELSE 0 END) as compras,
                SUM(CASE WHEN tipo = 'venta' THEN 1 ELSE 0 END) as ventas,
                SUM(CASE WHEN tipo = 'venta' THEN COALESCE(pnl, 0) ELSE 0 END) as pnl_total,
                SUM(total_usd) as volumen_total
            FROM $tabla_trades
            WHERE DATE(timestamp) = %s",
            $fecha_ayer
        ));

        if ($resumen_diario && $resumen_diario->total_trades > 0) {
            $mensaje_reporte = sprintf(
                __("Reporte Trading IA - %s\n\nTrades: %d (Compras: %d, Ventas: %d)\nVolumen: $%s\nP&L: $%s", 'flavor-chat-ia'),
                $fecha_ayer,
                $resumen_diario->total_trades,
                $resumen_diario->compras,
                $resumen_diario->ventas,
                number_format($resumen_diario->volumen_total, 2),
                number_format($resumen_diario->pnl_total, 2)
            );

            // Enviar notificacion si hay trades
            do_action('flavor_notificacion_enviar', array(
                'tipo'      => 'trading_reporte_diario',
                'titulo'    => __('Reporte Diario Trading IA', 'flavor-chat-ia'),
                'mensaje'   => $mensaje_reporte,
                'usuario_id' => $this->obtener_usuario_trading(),
                'prioridad' => 'normal',
            ));

            flavor_chat_ia_log('Trading IA Reporte Diario: ' . $mensaje_reporte, 'trading_ia');
        }
    }

    /**
     * Verifica alertas de precio configuradas
     */
    public function verificar_alertas_precio() {
        global $wpdb;
        $tabla_alertas = $wpdb->prefix . 'flavor_trading_ia_alertas';

        // Verificar si existe la tabla
        if (!Flavor_Chat_Helpers::tabla_existe($tabla_alertas)) {
            return;
        }

        $alertas_activas = $wpdb->get_results(
            "SELECT * FROM $tabla_alertas WHERE estado = 'activa'"
        );

        if (empty($alertas_activas)) {
            return;
        }

        $mercado = new Flavor_Trading_IA_Mercado();
        $tokens_unicos = array_unique(array_column($alertas_activas, 'token'));
        $precios_actuales = $mercado->obtener_precios_simples($tokens_unicos);

        foreach ($alertas_activas as $alerta) {
            $precio_actual = $precios_actuales[$alerta->token] ?? 0;

            if ($precio_actual <= 0) {
                continue;
            }

            $activar_alerta = false;

            if ($alerta->tipo === 'above' && $precio_actual >= $alerta->precio_objetivo) {
                $activar_alerta = true;
            } elseif ($alerta->tipo === 'below' && $precio_actual <= $alerta->precio_objetivo) {
                $activar_alerta = true;
            }

            if ($activar_alerta) {
                $this->disparar_alerta_precio($alerta, $precio_actual);

                // Marcar alerta como disparada
                $wpdb->update(
                    $tabla_alertas,
                    array('estado' => 'disparada', 'fecha_disparada' => current_time('mysql')),
                    array('id' => $alerta->id)
                );
            }
        }
    }

    /**
     * Dispara una alerta de precio
     *
     * @param object $alerta Datos de la alerta
     * @param float  $precio_actual Precio actual del token
     */
    private function disparar_alerta_precio($alerta, $precio_actual) {
        $tipo_texto = $alerta->tipo === 'above' ? __('supero', 'flavor-chat-ia') : __('bajo de', 'flavor-chat-ia');

        $mensaje_alerta = sprintf(
            __('Alerta de precio: %s %s $%s (objetivo: $%s)', 'flavor-chat-ia'),
            $alerta->token,
            $tipo_texto,
            number_format($precio_actual, 6),
            number_format($alerta->precio_objetivo, 6)
        );

        do_action('flavor_notificacion_enviar', array(
            'tipo'       => 'trading_alerta_precio',
            'titulo'     => sprintf(__('Alerta: %s', 'flavor-chat-ia'), $alerta->token),
            'mensaje'    => $mensaje_alerta,
            'usuario_id' => $alerta->usuario_id,
            'prioridad'  => 'alta',
        ));
    }

    // =========================================================================
    // Integracion Gamificacion y Notificaciones
    // =========================================================================

    /**
     * Integra gamificacion para compras
     *
     * @param array $resultado Resultado de la compra
     */
    private function integrar_gamificacion_compra($resultado) {
        if (!$resultado['success']) {
            return;
        }

        $usuario_id = get_current_user_id();
        if (!$usuario_id) {
            return;
        }

        // Agregar puntos por realizar trade
        do_action('flavor_gamificacion_agregar_puntos', array(
            'usuario_id' => $usuario_id,
            'puntos'     => 5,
            'razon'      => 'trading_compra',
            'descripcion' => sprintf(
                __('Compra de %s por $%s', 'flavor-chat-ia'),
                $resultado['resultado']['token'] ?? '',
                number_format($resultado['resultado']['total_usd'] ?? 0, 2)
            ),
        ));

        // Verificar logros
        $this->verificar_logros_trading($usuario_id);
    }

    /**
     * Integra gamificacion para ventas
     *
     * @param array $resultado Resultado de la venta
     */
    private function integrar_gamificacion_venta($resultado) {
        if (!$resultado['success']) {
            return;
        }

        $usuario_id = get_current_user_id();
        if (!$usuario_id) {
            return;
        }

        $puntos_base = 5;
        $pnl = $resultado['resultado']['pnl'] ?? 0;

        // Bonus por ganancia
        if ($pnl > 0) {
            $puntos_base += min(20, floor($pnl)); // Hasta 20 puntos extra por ganancia
        }

        do_action('flavor_gamificacion_agregar_puntos', array(
            'usuario_id'  => $usuario_id,
            'puntos'      => $puntos_base,
            'razon'       => 'trading_venta',
            'descripcion' => sprintf(
                __('Venta de %s - P&L: $%s', 'flavor-chat-ia'),
                $resultado['resultado']['token'] ?? '',
                number_format($pnl, 2)
            ),
        ));

        // Verificar logros
        $this->verificar_logros_trading($usuario_id);
    }

    /**
     * Verifica logros de trading para un usuario
     *
     * @param int $usuario_id ID del usuario
     */
    private function verificar_logros_trading($usuario_id) {
        global $wpdb;
        $tabla_trades = $wpdb->prefix . 'flavor_trading_ia_trades';

        $estadisticas = $wpdb->get_row($wpdb->prepare(
            "SELECT
                COUNT(*) as total_trades,
                SUM(CASE WHEN tipo = 'venta' AND pnl > 0 THEN 1 ELSE 0 END) as trades_ganadores,
                SUM(CASE WHEN tipo = 'venta' THEN COALESCE(pnl, 0) ELSE 0 END) as pnl_total
            FROM $tabla_trades
            WHERE usuario_id = %d",
            $usuario_id
        ));

        if (!$estadisticas) {
            return;
        }

        // Logro: Primer trade
        if ($estadisticas->total_trades === 1) {
            do_action('flavor_gamificacion_desbloquear_logro', array(
                'usuario_id' => $usuario_id,
                'logro_id'   => 'trading_primer_trade',
                'nombre'     => __('Primer Paso', 'flavor-chat-ia'),
                'descripcion' => __('Realizaste tu primer trade', 'flavor-chat-ia'),
            ));
        }

        // Logro: 10 trades
        if ($estadisticas->total_trades >= 10) {
            do_action('flavor_gamificacion_desbloquear_logro', array(
                'usuario_id' => $usuario_id,
                'logro_id'   => 'trading_10_trades',
                'nombre'     => __('Trader Activo', 'flavor-chat-ia'),
                'descripcion' => __('Realizaste 10 trades', 'flavor-chat-ia'),
            ));
        }

        // Logro: Primera ganancia
        if ($estadisticas->trades_ganadores >= 1) {
            do_action('flavor_gamificacion_desbloquear_logro', array(
                'usuario_id' => $usuario_id,
                'logro_id'   => 'trading_primera_ganancia',
                'nombre'     => __('En Verde', 'flavor-chat-ia'),
                'descripcion' => __('Cerraste tu primer trade con ganancias', 'flavor-chat-ia'),
            ));
        }

        // Logro: $100 de ganancia total
        if ($estadisticas->pnl_total >= 100) {
            do_action('flavor_gamificacion_desbloquear_logro', array(
                'usuario_id' => $usuario_id,
                'logro_id'   => 'trading_100_profit',
                'nombre'     => __('Centenario', 'flavor-chat-ia'),
                'descripcion' => __('Acumulaste $100 en ganancias', 'flavor-chat-ia'),
            ));
        }
    }

    /**
     * Envia notificacion de trade
     *
     * @param string $tipo Tipo de trade (compra/venta)
     * @param array  $resultado Resultado del trade
     */
    private function enviar_notificacion_trade($tipo, $resultado) {
        if (!$resultado['success']) {
            return;
        }

        $usuario_id = get_current_user_id();
        if (!$usuario_id) {
            return;
        }

        $datos_trade = $resultado['resultado'] ?? array();
        $titulo = $tipo === 'compra'
            ? sprintf(__('Compra ejecutada: %s', 'flavor-chat-ia'), $datos_trade['token'] ?? '')
            : sprintf(__('Venta ejecutada: %s', 'flavor-chat-ia'), $datos_trade['token'] ?? '');

        $mensaje = $tipo === 'compra'
            ? sprintf(
                __('Compraste %s %s por $%s', 'flavor-chat-ia'),
                number_format($datos_trade['cantidad'] ?? 0, 6),
                $datos_trade['token'] ?? '',
                number_format($datos_trade['total_usd'] ?? 0, 2)
            )
            : sprintf(
                __('Vendiste %s %s por $%s (P&L: $%s)', 'flavor-chat-ia'),
                number_format($datos_trade['cantidad'] ?? 0, 6),
                $datos_trade['token'] ?? '',
                number_format($datos_trade['total_usd'] ?? 0, 2),
                number_format($datos_trade['pnl'] ?? 0, 2)
            );

        do_action('flavor_notificacion_enviar', array(
            'tipo'       => 'trading_trade_ejecutado',
            'titulo'     => $titulo,
            'mensaje'    => $mensaje,
            'usuario_id' => $usuario_id,
            'prioridad'  => 'normal',
            'datos'      => $datos_trade,
        ));
    }

    // =========================================================================
    // Helpers Adicionales
    // =========================================================================

    /**
     * Calcula estadisticas del usuario
     *
     * @param int $usuario_id ID del usuario
     * @return array
     */
    private function calcular_estadisticas_usuario($usuario_id) {
        global $wpdb;
        $tabla_trades = $wpdb->prefix . 'flavor_trading_ia_trades';

        $estadisticas = $wpdb->get_row($wpdb->prepare(
            "SELECT
                COUNT(*) as total_trades,
                SUM(CASE WHEN tipo = 'compra' THEN 1 ELSE 0 END) as total_compras,
                SUM(CASE WHEN tipo = 'venta' THEN 1 ELSE 0 END) as total_ventas,
                SUM(CASE WHEN tipo = 'venta' AND pnl > 0 THEN 1 ELSE 0 END) as trades_ganadores,
                SUM(CASE WHEN tipo = 'venta' AND pnl <= 0 THEN 1 ELSE 0 END) as trades_perdedores,
                SUM(CASE WHEN tipo = 'venta' THEN COALESCE(pnl, 0) ELSE 0 END) as pnl_total,
                AVG(CASE WHEN tipo = 'venta' THEN COALESCE(pnl, 0) ELSE NULL END) as pnl_promedio,
                MAX(CASE WHEN tipo = 'venta' THEN pnl ELSE NULL END) as mejor_trade,
                MIN(CASE WHEN tipo = 'venta' THEN pnl ELSE NULL END) as peor_trade,
                SUM(total_usd) as volumen_total
            FROM $tabla_trades
            WHERE usuario_id = %d",
            $usuario_id
        ), ARRAY_A);

        if (!$estadisticas || $estadisticas['total_trades'] == 0) {
            return array(
                'total_trades'      => 0,
                'win_rate'          => 0,
                'pnl_total'         => 0,
                'pnl_promedio'      => 0,
                'mejor_trade'       => 0,
                'peor_trade'        => 0,
                'volumen_total'     => 0,
                'ratio_profit_loss' => 0,
            );
        }

        $ventas_totales = $estadisticas['total_ventas'] ?: 1;
        $win_rate = ($estadisticas['trades_ganadores'] / $ventas_totales) * 100;

        return array(
            'total_trades'       => intval($estadisticas['total_trades']),
            'total_compras'      => intval($estadisticas['total_compras']),
            'total_ventas'       => intval($estadisticas['total_ventas']),
            'trades_ganadores'   => intval($estadisticas['trades_ganadores']),
            'trades_perdedores'  => intval($estadisticas['trades_perdedores']),
            'win_rate'           => round($win_rate, 2),
            'pnl_total'          => floatval($estadisticas['pnl_total']),
            'pnl_promedio'       => floatval($estadisticas['pnl_promedio']),
            'mejor_trade'        => floatval($estadisticas['mejor_trade']),
            'peor_trade'         => floatval($estadisticas['peor_trade']),
            'volumen_total'      => floatval($estadisticas['volumen_total']),
        );
    }

    /**
     * Obtiene alertas de precio del usuario
     *
     * @param int $usuario_id ID del usuario
     * @return array
     */
    private function obtener_alertas_usuario($usuario_id) {
        global $wpdb;
        $tabla_alertas = $wpdb->prefix . 'flavor_trading_ia_alertas';

        if (!Flavor_Chat_Helpers::tabla_existe($tabla_alertas)) {
            return array();
        }

        $alertas = $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM $tabla_alertas WHERE usuario_id = %d ORDER BY created_at DESC",
            $usuario_id
        ), ARRAY_A);

        return $alertas ?: array();
    }

    /**
     * Crea una alerta de precio
     *
     * @param string $token Token
     * @param string $tipo Tipo (above/below)
     * @param float  $precio Precio objetivo
     * @return array
     */
    private function crear_alerta_precio($token, $tipo, $precio) {
        $usuario_id = get_current_user_id();

        if (!$usuario_id) {
            return array('success' => false, 'error' => __('Usuario no autenticado', 'flavor-chat-ia'));
        }

        $token = strtoupper(sanitize_text_field($token));
        $tipo = in_array($tipo, array('above', 'below'), true) ? $tipo : 'above';
        $precio = floatval($precio);

        if (empty($token) || $precio <= 0) {
            return array('success' => false, 'error' => __('Datos invalidos', 'flavor-chat-ia'));
        }

        global $wpdb;
        $tabla_alertas = $wpdb->prefix . 'flavor_trading_ia_alertas';

        $resultado = $wpdb->insert($tabla_alertas, array(
            'usuario_id'      => $usuario_id,
            'token'           => $token,
            'tipo'            => $tipo,
            'precio_objetivo' => $precio,
            'estado'          => 'activa',
            'created_at'      => current_time('mysql'),
        ));

        if ($resultado === false) {
            return array('success' => false, 'error' => __('Error al crear la alerta', 'flavor-chat-ia'));
        }

        return array(
            'success'  => true,
            'mensaje'  => sprintf(
                __('Alerta creada: %s %s $%s', 'flavor-chat-ia'),
                $token,
                $tipo === 'above' ? __('supere', 'flavor-chat-ia') : __('baje de', 'flavor-chat-ia'),
                number_format($precio, 6)
            ),
            'alerta_id' => $wpdb->insert_id,
        );
    }

    /**
     * Formatea un numero grande para mostrar
     *
     * @param float $numero Numero a formatear
     * @return string
     */
    private function formatear_numero_grande($numero) {
        if ($numero >= 1000000000) {
            return number_format($numero / 1000000000, 2) . 'B';
        } elseif ($numero >= 1000000) {
            return number_format($numero / 1000000, 2) . 'M';
        } elseif ($numero >= 1000) {
            return number_format($numero / 1000, 2) . 'K';
        }
        return number_format($numero, 2);
    }

    /**
     * Obtiene URL del modulo
     *
     * @return string
     */
    private function get_module_url() {
        return plugin_dir_url(__FILE__);
    }

    /**
     * Obtiene path del modulo
     *
     * @return string
     */
    private function get_module_path() {
        return plugin_dir_path(__FILE__);
    }

    // =========================================================================
    // Helpers
    // =========================================================================

    /**
     * Obtiene la configuracion de riesgo del modulo
     *
     * @return array
     */
    private function obtener_configuracion_riesgo() {
        return array(
            'riesgo_maximo_porcentaje' => $this->get_setting('riesgo_maximo_porcentaje', 5),
            'stop_loss_global'         => $this->get_setting('stop_loss_global', 15),
            'max_trades_por_hora'      => $this->get_setting('max_trades_por_hora', 10),
            'max_posiciones_abiertas'  => $this->get_setting('max_posiciones_abiertas', 5),
            'min_balance_usd'          => $this->get_setting('min_balance_usd', 10),
        );
    }

    // =========================================================================
    // Tool Definitions para Claude API
    // =========================================================================

    /**
     * {@inheritdoc}
     */
    public function get_tool_definitions() {
        return array(
            array(
                'name'         => 'trading_ia_obtener_estado',
                'description'  => 'Obtiene el estado completo del bot de trading IA: si esta activo, configuracion actual, resumen de portfolio, nivel de riesgo y reglas activas.',
                'input_schema' => array(
                    'type'       => 'object',
                    'properties' => new \stdClass(),
                ),
            ),
            array(
                'name'         => 'trading_ia_obtener_portfolio',
                'description'  => 'Obtiene el estado detallado del portfolio de paper trading: balance disponible, posiciones abiertas con precios de entrada, ganancias/perdidas por posicion y totales.',
                'input_schema' => array(
                    'type'       => 'object',
                    'properties' => new \stdClass(),
                ),
            ),
            array(
                'name'         => 'trading_ia_obtener_datos_mercado',
                'description'  => 'Obtiene datos de mercado actuales de CoinGecko para las criptomonedas especificadas: precio, cambio 24h, volumen, market cap.',
                'input_schema' => array(
                    'type'       => 'object',
                    'properties' => array(
                        'tokens' => array(
                            'type'        => 'string',
                            'description' => 'Tokens separados por comas (ej: "SOL,BONK,JUP"). Si no se especifica, usa los tokens monitoreados.',
                        ),
                    ),
                ),
            ),
            array(
                'name'         => 'trading_ia_obtener_indicadores',
                'description'  => 'Obtiene indicadores tecnicos de un token especifico: RSI, MACD, SMA, EMA, Bollinger Bands, tendencia y fuerza de la senal.',
                'input_schema' => array(
                    'type'       => 'object',
                    'properties' => array(
                        'token' => array(
                            'type'        => 'string',
                            'description' => 'Simbolo del token (ej: SOL, BONK, JUP, WIF)',
                        ),
                    ),
                    'required'   => array('token'),
                ),
            ),
            array(
                'name'         => 'trading_ia_ejecutar_compra_manual',
                'description'  => 'Ejecuta una compra simulada (paper trading) de un token con una cantidad especifica en USD. Incluye comisiones realistas de la red Solana.',
                'input_schema' => array(
                    'type'       => 'object',
                    'properties' => array(
                        'token' => array(
                            'type'        => 'string',
                            'description' => 'Simbolo del token a comprar (ej: SOL, BONK)',
                        ),
                        'cantidad_usd' => array(
                            'type'        => 'number',
                            'description' => 'Cantidad en USD a invertir',
                        ),
                    ),
                    'required'   => array('token', 'cantidad_usd'),
                ),
            ),
            array(
                'name'         => 'trading_ia_ejecutar_venta_manual',
                'description'  => 'Ejecuta una venta simulada (paper trading) de un token. Si no se especifica cantidad, vende toda la posicion.',
                'input_schema' => array(
                    'type'       => 'object',
                    'properties' => array(
                        'token' => array(
                            'type'        => 'string',
                            'description' => 'Simbolo del token a vender (ej: SOL, BONK)',
                        ),
                        'cantidad' => array(
                            'type'        => 'number',
                            'description' => 'Cantidad de tokens a vender (opcional, si no se indica vende todo)',
                        ),
                    ),
                    'required'   => array('token'),
                ),
            ),
            array(
                'name'         => 'trading_ia_iniciar_bot',
                'description'  => 'Inicia el ciclo automatico de trading. La IA analizara el mercado periodicamente y ejecutara operaciones segun su criterio y las reglas configuradas.',
                'input_schema' => array(
                    'type'       => 'object',
                    'properties' => new \stdClass(),
                ),
            ),
            array(
                'name'         => 'trading_ia_detener_bot',
                'description'  => 'Detiene el ciclo automatico de trading. Las posiciones abiertas se mantienen pero no se ejecutan nuevas operaciones.',
                'input_schema' => array(
                    'type'       => 'object',
                    'properties' => new \stdClass(),
                ),
            ),
            array(
                'name'         => 'trading_ia_obtener_historial_trades',
                'description'  => 'Obtiene el historial de operaciones de compra/venta realizadas, incluyendo precio, cantidad, comisiones y ganancia/perdida.',
                'input_schema' => array(
                    'type'       => 'object',
                    'properties' => array(
                        'limite' => array(
                            'type'        => 'integer',
                            'description' => 'Numero maximo de trades a mostrar (por defecto 20)',
                        ),
                    ),
                ),
            ),
            array(
                'name'         => 'trading_ia_obtener_reglas',
                'description'  => 'Lista todas las reglas dinamicas de trading activas e inactivas, incluyendo las creadas por la IA y por el usuario.',
                'input_schema' => array(
                    'type'       => 'object',
                    'properties' => new \stdClass(),
                ),
            ),
            array(
                'name'         => 'trading_ia_crear_regla',
                'description'  => 'Crea una regla de trading personalizada. Las reglas evaluan condiciones sobre indicadores y ejecutan acciones automaticas.',
                'input_schema' => array(
                    'type'       => 'object',
                    'properties' => array(
                        'nombre' => array(
                            'type'        => 'string',
                            'description' => 'Nombre descriptivo de la regla',
                        ),
                        'token' => array(
                            'type'        => 'string',
                            'description' => 'Token al que aplica la regla (ej: SOL) o * para todos',
                        ),
                        'indicador' => array(
                            'type'        => 'string',
                            'description' => 'Indicador a evaluar: precio, rsi, cambio_24h, volumen_24h, fuerza',
                            'enum'        => array('precio', 'rsi', 'cambio_24h', 'volumen_24h', 'fuerza'),
                        ),
                        'operador' => array(
                            'type'        => 'string',
                            'description' => 'Operador de comparacion',
                            'enum'        => array('>', '<', '>=', '<=', '=='),
                        ),
                        'valor' => array(
                            'type'        => 'number',
                            'description' => 'Valor de referencia para la condicion',
                        ),
                        'accion_tipo' => array(
                            'type'        => 'string',
                            'description' => 'Accion a ejecutar cuando se cumple la condicion',
                            'enum'        => array('alerta', 'bloquear_compra', 'bloquear_venta', 'reducir_posicion', 'forzar_venta', 'ajustar_parametro'),
                        ),
                        'razon' => array(
                            'type'        => 'string',
                            'description' => 'Razon o explicacion de la regla (opcional)',
                        ),
                    ),
                    'required'   => array('nombre', 'indicador', 'operador', 'valor', 'accion_tipo'),
                ),
            ),
            array(
                'name'         => 'trading_ia_eliminar_regla',
                'description'  => 'Elimina una regla de trading por su ID.',
                'input_schema' => array(
                    'type'       => 'object',
                    'properties' => array(
                        'regla_id' => array(
                            'type'        => 'string',
                            'description' => 'ID de la regla a eliminar',
                        ),
                    ),
                    'required'   => array('regla_id'),
                ),
            ),
            array(
                'name'         => 'trading_ia_actualizar_parametros',
                'description'  => 'Actualiza los parametros de configuracion del trading: agresividad, riesgo, stop loss, take profit, intervalo de analisis, etc.',
                'input_schema' => array(
                    'type'       => 'object',
                    'properties' => array(
                        'agresividad' => array(
                            'type'        => 'integer',
                            'description' => 'Nivel de agresividad de 1 (conservador) a 10 (agresivo)',
                        ),
                        'riesgo_maximo_porcentaje' => array(
                            'type'        => 'number',
                            'description' => 'Porcentaje maximo del portfolio por trade (1-25%)',
                        ),
                        'stop_loss_porcentaje' => array(
                            'type'        => 'number',
                            'description' => 'Porcentaje de stop loss por posicion (1-20%)',
                        ),
                        'take_profit_porcentaje' => array(
                            'type'        => 'number',
                            'description' => 'Porcentaje de take profit por posicion (1-50%)',
                        ),
                        'intervalo_analisis' => array(
                            'type'        => 'integer',
                            'description' => 'Intervalo en segundos entre analisis (30-300)',
                        ),
                        'confianza_minima_trade' => array(
                            'type'        => 'integer',
                            'description' => 'Confianza minima (0-90) para ejecutar un trade',
                        ),
                        'auto_ajuste_enabled' => array(
                            'type'        => 'boolean',
                            'description' => 'Permitir que la IA ajuste parametros automaticamente',
                        ),
                    ),
                ),
            ),
            array(
                'name'         => 'trading_ia_reset_paper_trading',
                'description'  => 'Reinicia completamente la simulacion de paper trading: borra historial de trades, resetea el portfolio al balance inicial.',
                'input_schema' => array(
                    'type'       => 'object',
                    'properties' => new \stdClass(),
                ),
            ),
            array(
                'name'         => 'trading_ia_obtener_estado_riesgo',
                'description'  => 'Obtiene el estado detallado de la gestion de riesgo: nivel actual, perdida diaria, trades en la ultima hora, limites configurados.',
                'input_schema' => array(
                    'type'       => 'object',
                    'properties' => new \stdClass(),
                ),
            ),
        );
    }

    // =========================================================================
    // Knowledge Base y FAQs
    // =========================================================================

    /**
     * {@inheritdoc}
     */
    public function get_knowledge_base() {
        return <<<'KNOWLEDGE'
**Trading IA - Bot de Paper Trading con Inteligencia Artificial**

Sistema de trading simulado (paper trading) para criptomonedas del ecosistema Solana, potenciado por IA.

**IMPORTANTE: Esto es paper trading (simulacion). NO se opera con dinero real. Las operaciones son simuladas para aprender y probar estrategias.**

**Como funciona:**
1. El bot monitorea precios en tiempo real via CoinGecko (SOL, BONK, JUP, WIF, JTO y mas)
2. Calcula indicadores tecnicos: RSI, MACD, SMA, EMA, Bollinger Bands, Momentum
3. La IA analiza los datos y decide si comprar, vender o esperar
4. Las operaciones simuladas incluyen comisiones realistas de la red Solana
5. Un gestor de riesgo protege el capital virtual con limites configurables

**Indicadores tecnicos disponibles:**
- RSI (14): Sobrecompra (>70) / Sobreventa (<30)
- MACD: Cruce de medias exponenciales para detectar tendencia
- SMA (7, 25, 99): Medias moviles simples de corto, medio y largo plazo
- EMA (12, 26): Medias moviles exponenciales
- Bollinger Bands: Volatilidad y niveles de soporte/resistencia
- Momentum y ROC: Velocidad del cambio de precio

**Gestion de riesgo:**
- Limite de perdida diaria (stop loss global)
- Maximo porcentaje por trade
- Limite de trades por hora
- Maximo de posiciones abiertas simultaneas
- Balance minimo de seguridad
- Niveles: BAJO, MEDIO, ALTO, CRITICO

**Reglas dinamicas:**
- La IA puede crear reglas automaticas basadas en su analisis
- El usuario puede crear reglas manuales (ej: "si RSI de SOL > 75, bloquear compra")
- Acciones disponibles: alerta, bloquear compra/venta, reducir posicion, forzar venta

**Auto-ajuste de parametros:**
- La IA puede sugerir ajustes a la agresividad, intervalos y limites
- Los cambios estan limitados a rangos seguros con cambios incrementales
- Cooldown de 5 minutos entre ajustes del mismo parametro

**Comisiones simuladas (red Solana):**
- Comision de red: 0.000005 SOL
- Comision de prioridad: 0.0005 SOL
- Comision DEX: 0.25%
- Slippage estimado: 0.3%

**WP Cron:**
Para que el bot funcione con precision en intervalos cortos, se recomienda configurar un cron real del servidor:
1. Anadir a wp-config.php: define('DISABLE_WP_CRON', true);
2. Configurar crontab: * * * * * wget -q -O /dev/null https://tu-sitio.com/wp-cron.php
KNOWLEDGE;
    }

    /**
     * {@inheritdoc}
     */
    public function get_faqs() {
        return array(
            array(
                'pregunta'  => 'Es trading real? Puedo perder dinero?',
                'respuesta' => 'No, es paper trading (simulacion). No se opera con dinero real. Es una herramienta educativa para aprender sobre trading de criptomonedas sin riesgo financiero.',
            ),
            array(
                'pregunta'  => 'Como inicio el bot de trading?',
                'respuesta' => 'Puedes decirme "iniciar bot" o "empezar a operar". El bot comenzara a analizar el mercado automaticamente segun el intervalo configurado.',
            ),
            array(
                'pregunta'  => 'Como hago una compra manual?',
                'respuesta' => 'Dime algo como "comprar 50 USD de SOL" o "comprar BONK por 100 dolares". La operacion simulada se ejecutara con comisiones realistas.',
            ),
            array(
                'pregunta'  => 'Que tokens puedo operar?',
                'respuesta' => 'Por defecto: SOL, BONK, JUP, WIF y JTO. Puedes pedir que se anadan o eliminen tokens de la lista de monitorizacion.',
            ),
            array(
                'pregunta'  => 'Como funciona la gestion de riesgo?',
                'respuesta' => 'El sistema limita la perdida diaria, el tamano de cada operacion, el numero de trades por hora y las posiciones abiertas. Si se alcanza un limite, el bot deja de operar temporalmente.',
            ),
            array(
                'pregunta'  => 'Que es el auto-ajuste?',
                'respuesta' => 'Si lo activas, la IA puede modificar parametros como la agresividad o los stop loss basandose en las condiciones del mercado. Los cambios son incrementales y dentro de rangos seguros.',
            ),
            array(
                'pregunta'  => 'Como reinicio la simulacion?',
                'respuesta' => 'Dime "reiniciar paper trading" o "reset simulacion". Esto borrara el historial de trades y volvera al balance inicial configurado.',
            ),
        );
    }

    // =========================================================================
    // Web Components
    // =========================================================================

    /**
     * Componentes web del modulo
     *
     * @return array
     */
    public function get_web_components() {
        return array(
            'hero' => array(
                'label'       => __('Hero Trading IA', 'flavor-chat-ia'),
                'description' => __('Seccion hero de Trading IA con estadisticas de senales, precision y mercados', 'flavor-chat-ia'),
                'category'    => 'hero',
                'icon'        => 'dashicons-chart-area',
                'fields'      => array(
                    'titulo_hero'           => array('type' => 'text', 'default' => __('Trading con IA', 'flavor-chat-ia')),
                    'subtitulo_hero'        => array('type' => 'textarea', 'default' => __('Analisis predictivo y senales de trading impulsados por inteligencia artificial', 'flavor-chat-ia')),
                    'senales_generadas'     => array('type' => 'text', 'default' => '12.450'),
                    'precision_porcentaje'  => array('type' => 'text', 'default' => '87.3'),
                    'mercados_analizados'   => array('type' => 'number', 'default' => 24),
                    'url_comenzar'          => array('type' => 'url', 'default' => '#comenzar-trading'),
                ),
                'template'    => 'trading-ia/hero',
            ),
            'features' => array(
                'label'       => __('Features Trading IA', 'flavor-chat-ia'),
                'description' => __('Grid de herramientas de trading inteligente: analisis tecnico, senales, backtesting, gestion de riesgo', 'flavor-chat-ia'),
                'category'    => 'features',
                'icon'        => 'dashicons-grid-view',
                'fields'      => array(
                    'titulo_features'          => array('type' => 'text', 'default' => __('Herramientas de Trading Inteligente', 'flavor-chat-ia')),
                    'funcionalidades_trading'  => array('type' => 'repeater', 'default' => array()),
                ),
                'template'    => 'trading-ia/features',
            ),
            'stats' => array(
                'label'       => __('Panel de Rendimiento Trading IA', 'flavor-chat-ia'),
                'description' => __('Dashboard de estadisticas con KPIs y grafico de rendimiento mensual', 'flavor-chat-ia'),
                'category'    => 'stats',
                'icon'        => 'dashicons-chart-bar',
                'fields'      => array(
                    'titulo_stats'            => array('type' => 'text', 'default' => __('Panel de Rendimiento', 'flavor-chat-ia')),
                    'rendimiento_mensual'     => array('type' => 'text', 'default' => '+12.4%'),
                    'operaciones_activas'     => array('type' => 'number', 'default' => 8),
                    'win_rate'                => array('type' => 'text', 'default' => '73.2%'),
                    'drawdown_maximo'         => array('type' => 'text', 'default' => '-4.8%'),
                    'datos_barras_mensuales'  => array('type' => 'repeater', 'default' => array()),
                ),
                'template'    => 'trading-ia/stats',
            ),
        );
    }

    // =========================================================================
    // Form Config
    // =========================================================================

    /**
     * Configuracion de formularios del modulo
     *
     * @param string $nombre_accion Nombre de la accion
     * @return array Configuracion del formulario
     */
    public function get_form_config($nombre_accion) {
        $configuraciones_formulario = array(
            'ejecutar_compra_manual' => array(
                'title'       => __('Compra Manual de Token', 'flavor-chat-ia'),
                'description' => __('Ejecuta una compra simulada (paper trading) de un token con USD', 'flavor-chat-ia'),
                'fields'      => array(
                    'token' => array(
                        'type'     => 'select',
                        'label'    => __('Token a comprar', 'flavor-chat-ia'),
                        'required' => true,
                        'options'  => array(
                            'SOL'  => 'SOL (Solana)',
                            'BONK' => 'BONK',
                            'JUP'  => 'JUP (Jupiter)',
                            'WIF'  => 'WIF (dogwifhat)',
                            'JTO'  => 'JTO (Jito)',
                        ),
                    ),
                    'cantidad_usd' => array(
                        'type'        => 'number',
                        'label'       => __('Cantidad en USD', 'flavor-chat-ia'),
                        'required'    => true,
                        'min'         => 1,
                        'step'        => '0.01',
                        'placeholder' => __('Cantidad en dolares a invertir', 'flavor-chat-ia'),
                        'description' => __('Monto en USD que deseas invertir en este token', 'flavor-chat-ia'),
                    ),
                ),
                'submit_text'     => __('Ejecutar Compra', 'flavor-chat-ia'),
                'success_message' => __('Compra simulada ejecutada correctamente.', 'flavor-chat-ia'),
            ),
            'ejecutar_venta_manual' => array(
                'title'       => __('Venta Manual de Token', 'flavor-chat-ia'),
                'description' => __('Ejecuta una venta simulada (paper trading) de un token', 'flavor-chat-ia'),
                'fields'      => array(
                    'token' => array(
                        'type'     => 'select',
                        'label'    => __('Token a vender', 'flavor-chat-ia'),
                        'required' => true,
                        'options'  => array(
                            'SOL'  => 'SOL (Solana)',
                            'BONK' => 'BONK',
                            'JUP'  => 'JUP (Jupiter)',
                            'WIF'  => 'WIF (dogwifhat)',
                            'JTO'  => 'JTO (Jito)',
                        ),
                    ),
                    'cantidad' => array(
                        'type'        => 'number',
                        'label'       => __('Cantidad de tokens', 'flavor-chat-ia'),
                        'min'         => 0,
                        'step'        => '0.000001',
                        'placeholder' => __('Cantidad de tokens a vender', 'flavor-chat-ia'),
                        'description' => __('Deja vacio para vender toda la posicion', 'flavor-chat-ia'),
                    ),
                ),
                'submit_text'     => __('Ejecutar Venta', 'flavor-chat-ia'),
                'success_message' => __('Venta simulada ejecutada correctamente.', 'flavor-chat-ia'),
            ),
            'crear_regla' => array(
                'title'       => __('Crear Regla de Trading', 'flavor-chat-ia'),
                'description' => __('Define una regla personalizada que se evalua automaticamente sobre indicadores tecnicos', 'flavor-chat-ia'),
                'fields'      => array(
                    'nombre' => array(
                        'type'        => 'text',
                        'label'       => __('Nombre de la regla', 'flavor-chat-ia'),
                        'required'    => true,
                        'placeholder' => __('Ej: Alerta RSI sobrecompra SOL', 'flavor-chat-ia'),
                    ),
                    'token' => array(
                        'type'     => 'select',
                        'label'    => __('Token', 'flavor-chat-ia'),
                        'required' => true,
                        'options'  => array(
                            '*'    => __('Todos los tokens', 'flavor-chat-ia'),
                            'SOL'  => 'SOL',
                            'BONK' => 'BONK',
                            'JUP'  => 'JUP',
                            'WIF'  => 'WIF',
                            'JTO'  => 'JTO',
                        ),
                    ),
                    'indicador' => array(
                        'type'     => 'select',
                        'label'    => __('Indicador', 'flavor-chat-ia'),
                        'required' => true,
                        'options'  => array(
                            'rsi'   => 'RSI',
                            'macd'  => 'MACD',
                            'sma'   => 'SMA (Media Movil Simple)',
                            'ema'   => 'EMA (Media Movil Exponencial)',
                        ),
                    ),
                    'operador' => array(
                        'type'     => 'select',
                        'label'    => __('Operador', 'flavor-chat-ia'),
                        'required' => true,
                        'options'  => array(
                            'mayor'  => __('Mayor que (>)', 'flavor-chat-ia'),
                            'menor'  => __('Menor que (<)', 'flavor-chat-ia'),
                            'igual'  => __('Igual a (==)', 'flavor-chat-ia'),
                            'cruce'  => __('Cruce de medias', 'flavor-chat-ia'),
                        ),
                    ),
                    'valor' => array(
                        'type'        => 'number',
                        'label'       => __('Valor de referencia', 'flavor-chat-ia'),
                        'required'    => true,
                        'step'        => '0.01',
                        'placeholder' => __('Ej: 70 para RSI sobrecompra', 'flavor-chat-ia'),
                        'description' => __('Valor numerico con el que se compara el indicador', 'flavor-chat-ia'),
                    ),
                    'accion_tipo' => array(
                        'type'     => 'select',
                        'label'    => __('Accion a ejecutar', 'flavor-chat-ia'),
                        'required' => true,
                        'options'  => array(
                            'comprar' => __('Comprar', 'flavor-chat-ia'),
                            'vender'  => __('Vender', 'flavor-chat-ia'),
                        ),
                    ),
                ),
                'submit_text'     => __('Crear Regla', 'flavor-chat-ia'),
                'success_message' => __('Regla de trading creada correctamente. Se evaluara en cada ciclo de analisis.', 'flavor-chat-ia'),
            ),
        );

        return $configuraciones_formulario[$nombre_accion] ?? array();
    }

    /**
     * Verifica y crea tablas si no existen
     */
    public function maybe_create_tables() {
        global $wpdb;

        $db_version = get_option('flavor_trading_ia_db_version', '0');
        $current_version = '2.0.0';

        // Si la versión es antigua, forzar actualización de tablas
        if (version_compare($db_version, $current_version, '<')) {
            $this->create_tables();
            $this->migrate_tables_v2();
            update_option('flavor_trading_ia_db_version', $current_version);
        }
    }

    /**
     * Migración de tablas a v2 (user_id -> usuario_id)
     */
    private function migrate_tables_v2() {
        global $wpdb;

        $tablas_columnas = array(
            $wpdb->prefix . 'flavor_trading_ia_portfolio' => array(
                'usuario_id' => 'bigint(20) UNSIGNED NOT NULL',
                'balance_usd' => 'decimal(20,8) NOT NULL DEFAULT 1000',
                'balance_inicial' => 'decimal(20,8) NOT NULL DEFAULT 1000',
                'tokens_json' => 'longtext DEFAULT NULL',
                'precios_entrada_json' => 'longtext DEFAULT NULL',
                'fees_acumuladas_usd' => 'decimal(10,6) DEFAULT 0',
                'contador_trades' => 'int(11) DEFAULT 0',
                'fecha_creacion' => 'datetime DEFAULT NULL',
                'fecha_actualizacion' => 'datetime DEFAULT NULL',
            ),
            $wpdb->prefix . 'flavor_trading_ia_reglas' => array(
                'regla_id' => 'varchar(50) NOT NULL',
                'usuario_id' => 'bigint(20) UNSIGNED NOT NULL',
                'token_condicion' => "varchar(20) DEFAULT '*'",
                'indicador' => 'varchar(50) NOT NULL',
                'operador' => 'varchar(5) NOT NULL',
                'valor' => 'decimal(20,8) NOT NULL',
                'accion_tipo' => 'varchar(50) NOT NULL',
                'accion_parametros_json' => 'text DEFAULT NULL',
                'creada_por' => "varchar(10) DEFAULT 'ia'",
                'razon' => 'text DEFAULT NULL',
                'veces_activada' => 'int(11) DEFAULT 0',
                'ultima_activacion' => 'datetime DEFAULT NULL',
            ),
            $wpdb->prefix . 'flavor_trading_ia_trades' => array(
                'usuario_id' => 'bigint(20) UNSIGNED NOT NULL DEFAULT 0',
                'timestamp' => 'datetime NOT NULL',
            ),
        );

        foreach ($tablas_columnas as $tabla => $columnas) {
            if (!Flavor_Chat_Helpers::tabla_existe($tabla)) {
                continue;
            }

            foreach ($columnas as $columna => $definicion) {
                // Verificar si la columna existe
                $columna_existe = $wpdb->get_var($wpdb->prepare(
                    "SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS
                     WHERE TABLE_SCHEMA = %s AND TABLE_NAME = %s AND COLUMN_NAME = %s",
                    DB_NAME, $tabla, $columna
                ));

                if (!$columna_existe) {
                    $wpdb->query("ALTER TABLE {$tabla} ADD COLUMN {$columna} {$definicion}");
                }
            }

            // Migrar user_id a usuario_id si existe
            $user_id_existe = $wpdb->get_var($wpdb->prepare(
                "SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS
                 WHERE TABLE_SCHEMA = %s AND TABLE_NAME = %s AND COLUMN_NAME = 'user_id'",
                DB_NAME, $tabla
            ));

            if ($user_id_existe) {
                // Copiar datos de user_id a usuario_id
                $wpdb->query("UPDATE {$tabla} SET usuario_id = user_id WHERE usuario_id = 0 OR usuario_id IS NULL");
            }
        }
    }

    /**
     * Crea las tablas necesarias
     */
    private function create_tables() {
        $esquemas = $this->get_table_schema();

        if (empty($esquemas)) {
            return;
        }

        require_once ABSPATH . 'wp-admin/includes/upgrade.php';

        foreach ($esquemas as $tabla => $sql) {
            dbDelta($sql);
        }
    }


    public function public_permission_check($request) {
        $method = strtoupper($request->get_method());
        $tipo = in_array($method, ['POST', 'PUT', 'DELETE'], true) ? 'post' : 'get';
        return Flavor_API_Rate_Limiter::check_rate_limit($tipo);
    }

    /**
     * Crea páginas frontend automáticamente
     */
    public function maybe_create_pages() {
        if (!class_exists('Flavor_Page_Creator')) {
            return;
        }

        // En admin: refrescar páginas del módulo
        if (is_admin()) {
            Flavor_Page_Creator::refresh_module_pages('trading_ia');
            return;
        }

        // En frontend: crear páginas si no existen
        $pagina = get_page_by_path('trading-ia');
        if (!$pagina && !get_option('flavor_trading_ia_pages_created')) {
            Flavor_Page_Creator::create_pages_for_modules(['trading_ia']);
            update_option('flavor_trading_ia_pages_created', 1, false);
        }
    }

    /**
     * Obtiene estadísticas para el dashboard del cliente
     *
     * @return array Estadísticas del módulo
     */
    public function get_estadisticas_dashboard() {
        global $wpdb;
        $estadisticas = [];

        $tabla_trades = $wpdb->prefix . 'flavor_trading_ia_trades';
        $tabla_portfolio = $wpdb->prefix . 'flavor_trading_ia_portfolio';

        $usuario_id = get_current_user_id();
        if (!$usuario_id) {
            return $estadisticas;
        }

        // Verificar si la tabla portfolio existe y tiene la estructura esperada
        if (Flavor_Chat_Helpers::tabla_existe($tabla_portfolio)) {
            // Suprimir errores SQL en caso de columnas faltantes
            $wpdb->suppress_errors(true);
            $portfolio = $wpdb->get_row($wpdb->prepare(
                "SELECT balance_usd, contador_trades FROM {$tabla_portfolio}
                 WHERE usuario_id = %d",
                $usuario_id
            ));
            $wpdb->suppress_errors(false);

            if ($portfolio && !$wpdb->last_error) {
                $estadisticas['balance'] = [
                    'icon' => 'dashicons-chart-area',
                    'valor' => number_format((float) $portfolio->balance_usd, 2),
                    'label' => __('Balance USD', 'flavor-chat-ia'),
                    'color' => 'green',
                ];

                if ((int) $portfolio->contador_trades > 0) {
                    $estadisticas['trades'] = [
                        'icon' => 'dashicons-chart-line',
                        'valor' => (int) $portfolio->contador_trades,
                        'label' => __('Trades', 'flavor-chat-ia'),
                        'color' => 'blue',
                    ];
                }
            }
        }

        return $estadisticas;
    }

    /**
     * Define las páginas del módulo para V3
     */
    public function get_pages_definition() {
        return [
            [
                'title' => __('Trading IA', 'flavor-chat-ia'),
                'slug' => 'trading-ia',
                'content' => '<h1>' . __('Trading IA', 'flavor-chat-ia') . '</h1>
<p>' . __('Análisis de mercados con inteligencia artificial', 'flavor-chat-ia') . '</p>

[flavor_module_listing module="trading_ia" action="dashboard" columnas="3" limite="12"]',
                'parent' => 0,
            ],
            [
                'title' => __('Señales', 'flavor-chat-ia'),
                'slug' => 'senales-trading',
                'content' => '<h1>' . __('Señales de Trading', 'flavor-chat-ia') . '</h1>
<p>' . __('Consulta las señales generadas por IA', 'flavor-chat-ia') . '</p>

[flavor_module_listing module="trading_ia" action="senales"]',
                'parent' => 'trading-ia',
            ],
            [
                'title' => __('Estrategias', 'flavor-chat-ia'),
                'slug' => 'estrategias-trading',
                'content' => '<h1>' . __('Estrategias de Trading', 'flavor-chat-ia') . '</h1>
<p>' . __('Configura tus estrategias automatizadas', 'flavor-chat-ia') . '</p>

[flavor_module_dashboard module="trading_ia" action="estrategias"]',
                'parent' => 'trading-ia',
            ],
            [
                'title' => __('Historial', 'flavor-chat-ia'),
                'slug' => 'historial-trading',
                'content' => '<h1>' . __('Historial de Operaciones', 'flavor-chat-ia') . '</h1>
<p>' . __('Revisa tu historial de operaciones', 'flavor-chat-ia') . '</p>

[flavor_module_listing module="trading_ia" action="historial"]',
                'parent' => 'trading-ia',
            ],
        ];
    }


    /**
     * Inicializa el dashboard tab del módulo
     */
    private function inicializar_dashboard_tab() {
        $archivo_tab = dirname(__FILE__) . '/class-trading-ia-dashboard-tab.php';
        if (file_exists($archivo_tab)) {
            require_once $archivo_tab;
            Flavor_Trading_Ia_Dashboard_Tab::get_instance();
        }
    }
}

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
        $this->id = 'trading_ia';
        $this->name = __('Trading IA', 'flavor-chat-ia');
        $this->description = __('Bot de trading simulado con IA para criptomonedas Solana. Paper trading con indicadores tecnicos, gestion de riesgo y reglas dinamicas.', 'flavor-chat-ia');

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
        $this->cargar_clases_auxiliares();

        // Registrar schedule personalizado para WP Cron
        add_filter('cron_schedules', array($this, 'registrar_cron_schedule'));

        // Hook del ciclo de trading
        add_action(self::CRON_HOOK, array($this, 'ejecutar_ciclo_cron'));

        // Si el bot estaba activo, asegurar que el cron esta programado
        if ($this->get_setting('bot_activo', false)) {
            $this->programar_cron();
        }
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
}

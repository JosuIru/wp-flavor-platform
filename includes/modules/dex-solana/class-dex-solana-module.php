<?php
/**
 * Modulo DEX Solana para Chat IA
 *
 * Exchange descentralizado simulado para tokens Solana con AMM, farming y modo dual (paper + real).
 *
 * @package FlavorChatIA
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Modulo DEX Solana - Swap de tokens, pools de liquidez AMM, yield farming y modo dual
 */
class Flavor_Chat_Dex_Solana_Module extends Flavor_Chat_Module_Base {

    /**
     * Instancias de clases auxiliares
     */
    private $jupiter_api    = null;
    private $token_registry = null;
    private $swap_engine    = null;
    private $pool_manager   = null;
    private $farming        = null;

    /**
     * Constructor
     */
    public function __construct() {
        $this->id          = 'dex_solana';
        $this->name        = __('DEX Solana', 'flavor-chat-ia');
        $this->description = __('Exchange descentralizado para tokens Solana. Swap de tokens via Jupiter, pools de liquidez AMM, yield farming y modo dual (paper + real).', 'flavor-chat-ia');

        parent::__construct();
    }

    /**
     * {@inheritdoc}
     */
    public function can_activate() {
        global $wpdb;
        $tabla_swaps = $wpdb->prefix . 'flavor_dex_swaps';

        return Flavor_Chat_Helpers::tabla_existe($tabla_swaps);
    }

    /**
     * {@inheritdoc}
     */
    public function get_activation_error() {
        if (!$this->can_activate()) {
            return __('Las tablas del DEX Solana no estan creadas. Se crearan automaticamente al activar el plugin.', 'flavor-chat-ia');
        }
        return '';
    }

    /**
     * {@inheritdoc}
     */
    protected function get_default_settings() {
        return array(
            'modo_activo'                 => 'paper',
            'balance_inicial_usdc'        => 1000.0,
            'slippage_maximo_porcentaje'  => 1.0,
            'slippage_defecto_porcentaje' => 0.5,
            'tokens_favoritos'            => array('SOL', 'USDC', 'JUP', 'RAY', 'BONK'),
            'cache_precios_segundos'      => 30,
            'cache_token_list_segundos'   => 3600,
            'max_swaps_por_hora'          => 20,
            'pools_semilla_activos'       => true,
            'farming_activo'              => true,
            'reward_multiplicador'        => 1.0,
            'wallet_address'              => '',
            'auto_compound'               => false,
        );
    }

    /**
     * {@inheritdoc}
     */
    public function init() {
        $this->cargar_clases_auxiliares();
        $this->inicializar_componentes();
    }

    /**
     * Carga las clases auxiliares del modulo
     */
    private function cargar_clases_auxiliares() {
        $directorio_modulo = FLAVOR_CHAT_IA_PATH . 'includes/modules/dex-solana/';

        $archivos_clases = array(
            'class-dex-solana-jupiter-api.php',
            'class-dex-solana-token-registry.php',
            'class-dex-solana-portfolio.php',
            'class-dex-solana-historial.php',
            'class-dex-solana-swap-engine.php',
            'class-dex-solana-pool-manager.php',
            'class-dex-solana-farming.php',
            'class-dex-solana-cerebro.php',
        );

        foreach ($archivos_clases as $archivo) {
            $ruta_completa = $directorio_modulo . $archivo;
            if (file_exists($ruta_completa)) {
                require_once $ruta_completa;
            }
        }
    }

    /**
     * Inicializa los componentes auxiliares
     */
    private function inicializar_componentes() {
        $this->jupiter_api    = new Flavor_Dex_Solana_Jupiter_API();
        $this->token_registry = new Flavor_Dex_Solana_Token_Registry();
        $this->pool_manager   = new Flavor_Dex_Solana_Pool_Manager($this->token_registry);
        $this->farming        = new Flavor_Dex_Solana_Farming();

        // Sembrar pools y programas iniciales si corresponde
        if ($this->get_setting('pools_semilla_activos', true)) {
            $this->pool_manager->sembrar_pools_iniciales();
        }
        if ($this->get_setting('farming_activo', true)) {
            global $wpdb;
            $tabla_pools = $wpdb->prefix . 'flavor_dex_pools';
            $pools_existentes = $wpdb->get_results("SELECT * FROM $tabla_pools WHERE activo = 1", ARRAY_A);
            if (!empty($pools_existentes)) {
                $this->farming->sembrar_programas_iniciales($pools_existentes);
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
    // Helpers de instancias por usuario
    // =========================================================================

    /**
     * Obtiene el ID del usuario actual o el admin por defecto
     *
     * @return int
     */
    private function obtener_usuario_id() {
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

    /**
     * Obtiene instancias de portfolio, historial y swap engine para un usuario
     *
     * @param int $usuario_id
     * @return array [portfolio, historial, swap_engine]
     */
    private function obtener_componentes_usuario($usuario_id) {
        $balance_inicial = floatval($this->get_setting('balance_inicial_usdc', 1000.0));
        $portfolio       = new Flavor_Dex_Solana_Portfolio($usuario_id, $balance_inicial);
        $historial       = new Flavor_Dex_Solana_Historial($usuario_id);
        $swap_engine     = new Flavor_Dex_Solana_Swap_Engine(
            $this->jupiter_api,
            $this->token_registry,
            $portfolio,
            $historial
        );

        return array($portfolio, $historial, $swap_engine);
    }

    // =========================================================================
    // Acciones del modulo (18 acciones)
    // =========================================================================

    /**
     * {@inheritdoc}
     */
    public function get_actions() {
        return array(
            'obtener_cotizacion_swap' => array(
                'description' => 'Obtener cotizacion para swap entre dos tokens via Jupiter',
                'params'      => array('token_entrada', 'token_salida', 'cantidad', 'slippage'),
            ),
            'ejecutar_swap' => array(
                'description' => 'Ejecutar swap de tokens (paper o real segun modo activo)',
                'params'      => array('token_entrada', 'token_salida', 'cantidad', 'slippage'),
            ),
            'preparar_swap_real' => array(
                'description' => 'Preparar transaccion sin firmar para swap real',
                'params'      => array('token_entrada', 'token_salida', 'cantidad', 'slippage'),
            ),
            'obtener_portfolio' => array(
                'description' => 'Obtener estado completo del portfolio DEX',
                'params'      => array(),
            ),
            'obtener_historial_swaps' => array(
                'description' => 'Obtener historial de swaps realizados',
                'params'      => array('limite'),
            ),
            'buscar_token' => array(
                'description' => 'Buscar token por simbolo o nombre',
                'params'      => array('busqueda'),
            ),
            'obtener_precios' => array(
                'description' => 'Obtener precios actuales de tokens via Jupiter',
                'params'      => array('tokens'),
            ),
            'listar_pools' => array(
                'description' => 'Listar pools de liquidez con APY',
                'params'      => array(),
            ),
            'obtener_pool' => array(
                'description' => 'Obtener detalles de un pool especifico',
                'params'      => array('pool_id'),
            ),
            'agregar_liquidez' => array(
                'description' => 'Agregar liquidez a un pool AMM',
                'params'      => array('pool_id', 'cantidad_token_a', 'cantidad_token_b'),
            ),
            'retirar_liquidez' => array(
                'description' => 'Retirar liquidez de un pool AMM',
                'params'      => array('pool_id', 'porcentaje'),
            ),
            'obtener_posiciones_lp' => array(
                'description' => 'Obtener posiciones LP del usuario',
                'params'      => array(),
            ),
            'listar_programas_farming' => array(
                'description' => 'Listar programas de farming activos',
                'params'      => array(),
            ),
            'stakear_lp' => array(
                'description' => 'Stakear LP tokens en un programa de farming',
                'params'      => array('programa_id'),
            ),
            'unstakear_lp' => array(
                'description' => 'Retirar LP tokens de un programa de farming',
                'params'      => array('programa_id'),
            ),
            'cosechar_rewards' => array(
                'description' => 'Cosechar rewards pendientes de farming',
                'params'      => array('programa_id'),
            ),
            'cambiar_modo' => array(
                'description' => 'Cambiar entre modo paper y real',
                'params'      => array('modo'),
            ),
            'reset_dex' => array(
                'description' => 'Reiniciar la simulacion DEX a valores iniciales',
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
    // Implementacion de las 18 acciones
    // =========================================================================

    /**
     * Accion 1: Obtener cotizacion de swap
     */
    private function action_obtener_cotizacion_swap($parametros) {
        $token_entrada = strtoupper(sanitize_text_field($parametros['token_entrada'] ?? ''));
        $token_salida  = strtoupper(sanitize_text_field($parametros['token_salida'] ?? ''));
        $cantidad      = floatval($parametros['cantidad'] ?? 0);
        $slippage      = floatval($parametros['slippage'] ?? $this->get_setting('slippage_defecto_porcentaje', 0.5));

        if (empty($token_entrada) || empty($token_salida)) {
            return array(
                'success' => false,
                'error'   => __('Debes especificar token de entrada y salida (ej: SOL, USDC)', 'flavor-chat-ia'),
            );
        }

        if ($cantidad <= 0) {
            return array(
                'success' => false,
                'error'   => __('La cantidad debe ser mayor a 0', 'flavor-chat-ia'),
            );
        }

        $usuario_id = $this->obtener_usuario_id();
        list($portfolio, $historial, $swap_engine) = $this->obtener_componentes_usuario($usuario_id);

        $cotizacion = $swap_engine->obtener_cotizacion_real($token_entrada, $token_salida, $cantidad, $slippage);

        if (!$cotizacion['success']) {
            return $cotizacion;
        }

        // Analisis IA opcional
        $analisis_ia = null;
        if (class_exists('Flavor_Dex_Solana_Cerebro')) {
            $cerebro     = new Flavor_Dex_Solana_Cerebro($usuario_id);
            $analisis_ia = $cerebro->analizar_swap(
                $cotizacion['cotizacion'],
                $portfolio->obtener_estado_completo()
            );
        }

        return array(
            'success'     => true,
            'cotizacion'  => $cotizacion['cotizacion'],
            'analisis_ia' => $analisis_ia,
        );
    }

    /**
     * Accion 2: Ejecutar swap
     */
    private function action_ejecutar_swap($parametros) {
        $token_entrada = strtoupper(sanitize_text_field($parametros['token_entrada'] ?? ''));
        $token_salida  = strtoupper(sanitize_text_field($parametros['token_salida'] ?? ''));
        $cantidad      = floatval($parametros['cantidad'] ?? 0);
        $slippage      = floatval($parametros['slippage'] ?? $this->get_setting('slippage_defecto_porcentaje', 0.5));

        if (empty($token_entrada) || empty($token_salida)) {
            return array(
                'success' => false,
                'error'   => __('Debes especificar token de entrada y salida', 'flavor-chat-ia'),
            );
        }

        if ($cantidad <= 0) {
            return array(
                'success' => false,
                'error'   => __('La cantidad debe ser mayor a 0', 'flavor-chat-ia'),
            );
        }

        $usuario_id = $this->obtener_usuario_id();
        list($portfolio, $historial, $swap_engine) = $this->obtener_componentes_usuario($usuario_id);

        $modo_activo = $portfolio->obtener_modo();

        if ($modo_activo === 'real') {
            // En modo real, redirigir a preparar_swap_real
            return $this->action_preparar_swap_real($parametros);
        }

        // Modo paper: ejecutar swap simulado
        $resultado = $swap_engine->ejecutar_swap_paper($token_entrada, $token_salida, $cantidad, $slippage);

        return $resultado;
    }

    /**
     * Accion 3: Preparar swap real (transaccion sin firmar)
     */
    private function action_preparar_swap_real($parametros) {
        $token_entrada = strtoupper(sanitize_text_field($parametros['token_entrada'] ?? ''));
        $token_salida  = strtoupper(sanitize_text_field($parametros['token_salida'] ?? ''));
        $cantidad      = floatval($parametros['cantidad'] ?? 0);
        $slippage      = floatval($parametros['slippage'] ?? $this->get_setting('slippage_defecto_porcentaje', 0.5));

        if (empty($token_entrada) || empty($token_salida) || $cantidad <= 0) {
            return array(
                'success' => false,
                'error'   => __('Debes especificar token de entrada, salida y cantidad', 'flavor-chat-ia'),
            );
        }

        $usuario_id = $this->obtener_usuario_id();
        list($portfolio, $historial, $swap_engine) = $this->obtener_componentes_usuario($usuario_id);

        $wallet_publica = $portfolio->obtener_estado_completo()['wallet_address'];

        if (empty($wallet_publica)) {
            $wallet_publica = $this->get_setting('wallet_address', '');
        }

        if (empty($wallet_publica)) {
            return array(
                'success' => false,
                'error'   => __('Debes configurar tu wallet address para modo real. Usa cambiar_modo con tu direccion de wallet.', 'flavor-chat-ia'),
            );
        }

        $resultado = $swap_engine->preparar_swap_real($token_entrada, $token_salida, $cantidad, $slippage, $wallet_publica);

        return $resultado;
    }

    /**
     * Accion 4: Obtener portfolio
     */
    private function action_obtener_portfolio($parametros) {
        $usuario_id = $this->obtener_usuario_id();
        $balance_inicial = floatval($this->get_setting('balance_inicial_usdc', 1000.0));
        $portfolio = new Flavor_Dex_Solana_Portfolio($usuario_id, $balance_inicial);

        $estado_portfolio = $portfolio->obtener_estado_completo();

        // Enriquecer con precios actuales de tokens
        $tokens_en_portfolio = array_keys($estado_portfolio['tokens'] ?? array());
        if (!empty($tokens_en_portfolio)) {
            $mints_para_precios = array();
            foreach ($tokens_en_portfolio as $simbolo_token) {
                $datos_token = $this->token_registry->obtener_token_por_simbolo($simbolo_token);
                if ($datos_token) {
                    $mints_para_precios[$simbolo_token] = $datos_token['mint'];
                }
            }

            if (!empty($mints_para_precios)) {
                $precios_actuales = $this->jupiter_api->obtener_precios(array_values($mints_para_precios));
                if ($precios_actuales['success'] ?? false) {
                    $estado_portfolio['precios_actuales'] = array();
                    foreach ($mints_para_precios as $simbolo => $mint) {
                        $precio = $precios_actuales['precios'][$mint] ?? 0;
                        $estado_portfolio['precios_actuales'][$simbolo] = $precio;
                    }
                }
            }
        }

        return array(
            'success'   => true,
            'portfolio' => $estado_portfolio,
        );
    }

    /**
     * Accion 5: Obtener historial de swaps
     */
    private function action_obtener_historial_swaps($parametros) {
        $usuario_id = $this->obtener_usuario_id();
        $limite     = isset($parametros['limite']) ? absint($parametros['limite']) : 20;

        $historial       = new Flavor_Dex_Solana_Historial($usuario_id);
        $swaps_recientes = $historial->obtener_historial_swaps($limite);
        $estadisticas    = $historial->obtener_estadisticas();

        return array(
            'success'      => true,
            'total'        => count($swaps_recientes),
            'swaps'        => $swaps_recientes,
            'estadisticas' => $estadisticas,
        );
    }

    /**
     * Accion 6: Buscar token
     */
    private function action_buscar_token($parametros) {
        $busqueda = sanitize_text_field($parametros['busqueda'] ?? '');

        if (empty($busqueda)) {
            return array(
                'success' => false,
                'error'   => __('Debes especificar un termino de busqueda', 'flavor-chat-ia'),
            );
        }

        // Buscar primero en tokens conocidos
        $resultado_local = $this->token_registry->obtener_token_por_simbolo($busqueda);

        // Buscar tambien en Jupiter
        $resultados_jupiter = $this->jupiter_api->buscar_token($busqueda);

        $tokens_encontrados = array();

        if ($resultado_local) {
            $tokens_encontrados[] = $resultado_local;
        }

        if ($resultados_jupiter['success'] ?? false) {
            foreach ($resultados_jupiter['tokens'] ?? array() as $token_jupiter) {
                $tokens_encontrados[] = $token_jupiter;
            }
        }

        return array(
            'success' => true,
            'total'   => count($tokens_encontrados),
            'tokens'  => $tokens_encontrados,
        );
    }

    /**
     * Accion 7: Obtener precios
     */
    private function action_obtener_precios($parametros) {
        $tokens_solicitados = $parametros['tokens'] ?? null;

        if (is_string($tokens_solicitados)) {
            $tokens_solicitados = array_map('trim', explode(',', $tokens_solicitados));
        }

        if (empty($tokens_solicitados)) {
            $tokens_solicitados = $this->get_setting('tokens_favoritos', array('SOL', 'USDC'));
        }

        $tokens_mayusculas = array_map('strtoupper', $tokens_solicitados);

        // Resolver simbolos a mints
        $mints = array();
        $mapa_simbolos = array();
        foreach ($tokens_mayusculas as $simbolo) {
            $datos_token = $this->token_registry->obtener_token_por_simbolo($simbolo);
            if ($datos_token) {
                $mints[]                      = $datos_token['mint'];
                $mapa_simbolos[$datos_token['mint']] = $simbolo;
            }
        }

        if (empty($mints)) {
            return array(
                'success' => false,
                'error'   => __('No se encontraron los tokens especificados', 'flavor-chat-ia'),
            );
        }

        $precios_resultado = $this->jupiter_api->obtener_precios($mints);

        if (!($precios_resultado['success'] ?? false)) {
            return $precios_resultado;
        }

        // Mapear de vuelta a simbolos
        $precios_por_simbolo = array();
        foreach ($precios_resultado['precios'] ?? array() as $mint => $precio_usd) {
            $simbolo = $mapa_simbolos[$mint] ?? $mint;
            $precios_por_simbolo[$simbolo] = $precio_usd;
        }

        return array(
            'success' => true,
            'precios' => $precios_por_simbolo,
        );
    }

    /**
     * Accion 8: Listar pools
     */
    private function action_listar_pools($parametros) {
        $pools = $this->pool_manager->listar_pools(true);

        return array(
            'success' => true,
            'total'   => count($pools),
            'pools'   => $pools,
        );
    }

    /**
     * Accion 9: Obtener pool
     */
    private function action_obtener_pool($parametros) {
        $pool_id = sanitize_text_field($parametros['pool_id'] ?? '');

        if (empty($pool_id)) {
            return array(
                'success' => false,
                'error'   => __('Debes especificar el pool_id', 'flavor-chat-ia'),
            );
        }

        $pool = $this->pool_manager->obtener_pool($pool_id);

        if (!$pool) {
            return array(
                'success' => false,
                'error'   => __('Pool no encontrado', 'flavor-chat-ia'),
            );
        }

        $apy = $this->pool_manager->obtener_apy_estimado($pool_id);

        return array(
            'success' => true,
            'pool'    => $pool,
            'apy'     => $apy,
        );
    }

    /**
     * Accion 10: Agregar liquidez
     */
    private function action_agregar_liquidez($parametros) {
        $pool_id          = sanitize_text_field($parametros['pool_id'] ?? '');
        $cantidad_token_a = floatval($parametros['cantidad_token_a'] ?? 0);
        $cantidad_token_b = floatval($parametros['cantidad_token_b'] ?? 0);

        if (empty($pool_id)) {
            return array(
                'success' => false,
                'error'   => __('Debes especificar el pool_id', 'flavor-chat-ia'),
            );
        }

        if ($cantidad_token_a <= 0 || $cantidad_token_b <= 0) {
            return array(
                'success' => false,
                'error'   => __('Las cantidades de ambos tokens deben ser mayores a 0', 'flavor-chat-ia'),
            );
        }

        $usuario_id = $this->obtener_usuario_id();
        $balance_inicial = floatval($this->get_setting('balance_inicial_usdc', 1000.0));
        $portfolio = new Flavor_Dex_Solana_Portfolio($usuario_id, $balance_inicial);

        // Verificar pool y obtener simbolos
        $pool = $this->pool_manager->obtener_pool($pool_id);
        if (!$pool) {
            return array(
                'success' => false,
                'error'   => __('Pool no encontrado', 'flavor-chat-ia'),
            );
        }

        $simbolo_a = $pool['token_a_simbolo'] ?? $pool->token_a_simbolo ?? '';
        $simbolo_b = $pool['token_b_simbolo'] ?? $pool->token_b_simbolo ?? '';

        // Verificar balances
        $balance_a = $portfolio->obtener_balance_token($simbolo_a);
        $balance_b = $portfolio->obtener_balance_token($simbolo_b);

        if ($balance_a < $cantidad_token_a || $balance_b < $cantidad_token_b) {
            return array(
                'success' => false,
                'error'   => sprintf(
                    __('Balance insuficiente. Tienes %s %s y %s %s', 'flavor-chat-ia'),
                    number_format($balance_a, 4),
                    $simbolo_a,
                    number_format($balance_b, 4),
                    $simbolo_b
                ),
            );
        }

        // Agregar liquidez al pool
        $resultado = $this->pool_manager->agregar_liquidez($pool_id, $cantidad_token_a, $cantidad_token_b, $usuario_id);

        if (!$resultado || !($resultado['success'] ?? true)) {
            return array(
                'success' => false,
                'error'   => $resultado['error'] ?? __('Error al agregar liquidez', 'flavor-chat-ia'),
            );
        }

        // Descontar tokens del portfolio
        $token_a_real = $resultado['token_a_real'] ?? $cantidad_token_a;
        $token_b_real = $resultado['token_b_real'] ?? $cantidad_token_b;
        $portfolio->restar_tokens($simbolo_a, $token_a_real);
        $portfolio->restar_tokens($simbolo_b, $token_b_real);

        // Registrar posicion LP
        $lp_tokens_emitidos = $resultado['lp_tokens_emitidos'] ?? 0;
        $valor_usd = ($token_a_real + $token_b_real); // Simplificado
        $portfolio->agregar_posicion_lp($pool_id, $lp_tokens_emitidos, $token_a_real, $token_b_real, $valor_usd);

        // Registrar en historial
        $historial = new Flavor_Dex_Solana_Historial($usuario_id);
        $historial->registrar_liquidez(array(
            'tipo'              => 'ADD',
            'pool_id'           => $pool_id,
            'token_a_simbolo'   => $simbolo_a,
            'token_b_simbolo'   => $simbolo_b,
            'cantidad_token_a'  => $token_a_real,
            'cantidad_token_b'  => $token_b_real,
            'lp_tokens'         => $lp_tokens_emitidos,
            'modo'              => $portfolio->obtener_modo(),
        ));

        return array(
            'success'           => true,
            'lp_tokens_emitidos' => $lp_tokens_emitidos,
            'token_a_depositado' => $token_a_real,
            'token_b_depositado' => $token_b_real,
            'share_porcentaje'  => $resultado['share_porcentaje'] ?? 0,
            'pool_id'           => $pool_id,
        );
    }

    /**
     * Accion 11: Retirar liquidez
     */
    private function action_retirar_liquidez($parametros) {
        $pool_id    = sanitize_text_field($parametros['pool_id'] ?? '');
        $porcentaje = floatval($parametros['porcentaje'] ?? 100);

        if (empty($pool_id)) {
            return array(
                'success' => false,
                'error'   => __('Debes especificar el pool_id', 'flavor-chat-ia'),
            );
        }

        $porcentaje = max(1, min(100, $porcentaje));

        $usuario_id = $this->obtener_usuario_id();

        $resultado = $this->pool_manager->retirar_liquidez($pool_id, $usuario_id, $porcentaje);

        if (!$resultado || !($resultado['success'] ?? true)) {
            return array(
                'success' => false,
                'error'   => $resultado['error'] ?? __('Error al retirar liquidez', 'flavor-chat-ia'),
            );
        }

        // Devolver tokens al portfolio
        $balance_inicial = floatval($this->get_setting('balance_inicial_usdc', 1000.0));
        $portfolio = new Flavor_Dex_Solana_Portfolio($usuario_id, $balance_inicial);

        $pool = $this->pool_manager->obtener_pool($pool_id);
        $simbolo_a = is_array($pool) ? ($pool['token_a_simbolo'] ?? '') : ($pool->token_a_simbolo ?? '');
        $simbolo_b = is_array($pool) ? ($pool['token_b_simbolo'] ?? '') : ($pool->token_b_simbolo ?? '');

        $token_a_retirado = $resultado['token_a_retirado'] ?? 0;
        $token_b_retirado = $resultado['token_b_retirado'] ?? 0;

        $portfolio->agregar_tokens($simbolo_a, $token_a_retirado);
        $portfolio->agregar_tokens($simbolo_b, $token_b_retirado);

        if ($porcentaje >= 100) {
            $portfolio->retirar_posicion_lp($pool_id);
        }

        // Registrar en historial
        $historial = new Flavor_Dex_Solana_Historial($usuario_id);
        $historial->registrar_liquidez(array(
            'tipo'              => 'REMOVE',
            'pool_id'           => $pool_id,
            'token_a_simbolo'   => $simbolo_a,
            'token_b_simbolo'   => $simbolo_b,
            'cantidad_token_a'  => $token_a_retirado,
            'cantidad_token_b'  => $token_b_retirado,
            'lp_tokens'         => $resultado['lp_tokens_retirados'] ?? 0,
            'modo'              => $portfolio->obtener_modo(),
        ));

        return array(
            'success'         => true,
            'token_a_retirado' => $token_a_retirado,
            'token_b_retirado' => $token_b_retirado,
            'porcentaje'      => $porcentaje,
            'pool_id'         => $pool_id,
        );
    }

    /**
     * Accion 12: Obtener posiciones LP
     */
    private function action_obtener_posiciones_lp($parametros) {
        $usuario_id = $this->obtener_usuario_id();
        $balance_inicial = floatval($this->get_setting('balance_inicial_usdc', 1000.0));
        $portfolio = new Flavor_Dex_Solana_Portfolio($usuario_id, $balance_inicial);

        $estado = $portfolio->obtener_estado_completo();
        $posiciones_lp = $estado['posiciones_lp'] ?? array();

        // Enriquecer con datos del pool
        foreach ($posiciones_lp as $pool_id => &$posicion) {
            $pool = $this->pool_manager->obtener_pool($pool_id);
            if ($pool) {
                $posicion['pool_datos'] = $pool;
                $posicion['apy']        = $this->pool_manager->obtener_apy_estimado($pool_id);
            }
        }

        return array(
            'success'    => true,
            'total'      => count($posiciones_lp),
            'posiciones' => $posiciones_lp,
        );
    }

    /**
     * Accion 13: Listar programas de farming
     */
    private function action_listar_programas_farming($parametros) {
        $programas = $this->farming->listar_programas(true);

        return array(
            'success'   => true,
            'total'     => count($programas),
            'programas' => $programas,
        );
    }

    /**
     * Accion 14: Stakear LP tokens
     */
    private function action_stakear_lp($parametros) {
        $programa_id = sanitize_text_field($parametros['programa_id'] ?? '');

        if (empty($programa_id)) {
            return array(
                'success' => false,
                'error'   => __('Debes especificar el programa_id de farming', 'flavor-chat-ia'),
            );
        }

        $usuario_id = $this->obtener_usuario_id();

        // Obtener LP tokens del usuario para este programa
        $balance_inicial = floatval($this->get_setting('balance_inicial_usdc', 1000.0));
        $portfolio = new Flavor_Dex_Solana_Portfolio($usuario_id, $balance_inicial);
        $estado = $portfolio->obtener_estado_completo();
        $posiciones_lp = $estado['posiciones_lp'] ?? array();

        // Buscar la posicion LP correspondiente al programa
        // Primero obtener el pool_id del programa
        global $wpdb;
        $tabla_farming = $wpdb->prefix . 'flavor_dex_farming';
        $programa = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM $tabla_farming WHERE programa_id = %s",
            $programa_id
        ));

        if (!$programa) {
            return array(
                'success' => false,
                'error'   => __('Programa de farming no encontrado', 'flavor-chat-ia'),
            );
        }

        $pool_id_programa = $programa->pool_id;

        if (!isset($posiciones_lp[$pool_id_programa])) {
            return array(
                'success' => false,
                'error'   => __('No tienes LP tokens en el pool de este programa. Primero agrega liquidez al pool.', 'flavor-chat-ia'),
            );
        }

        $lp_tokens_disponibles = floatval($posiciones_lp[$pool_id_programa]['lp_tokens'] ?? 0);

        if ($lp_tokens_disponibles <= 0) {
            return array(
                'success' => false,
                'error'   => __('No tienes LP tokens disponibles para stakear', 'flavor-chat-ia'),
            );
        }

        $resultado = $this->farming->stakear_lp($programa_id, $usuario_id, $lp_tokens_disponibles);

        if ($resultado && ($resultado['success'] ?? false)) {
            // Registrar en historial
            $historial = new Flavor_Dex_Solana_Historial($usuario_id);
            $historial->registrar_farming(array(
                'tipo'           => 'STAKE',
                'programa_id'    => $programa_id,
                'pool_id'        => $pool_id_programa,
                'lp_tokens'      => $lp_tokens_disponibles,
                'modo'           => $portfolio->obtener_modo(),
            ));
        }

        return $resultado ?? array(
            'success' => false,
            'error'   => __('Error al stakear LP tokens', 'flavor-chat-ia'),
        );
    }

    /**
     * Accion 15: Unstakear LP tokens
     */
    private function action_unstakear_lp($parametros) {
        $programa_id = sanitize_text_field($parametros['programa_id'] ?? '');

        if (empty($programa_id)) {
            return array(
                'success' => false,
                'error'   => __('Debes especificar el programa_id', 'flavor-chat-ia'),
            );
        }

        $usuario_id = $this->obtener_usuario_id();
        $resultado  = $this->farming->unstakear_lp($programa_id, $usuario_id);

        if ($resultado && ($resultado['success'] ?? false)) {
            // Agregar rewards al portfolio
            $balance_inicial = floatval($this->get_setting('balance_inicial_usdc', 1000.0));
            $portfolio = new Flavor_Dex_Solana_Portfolio($usuario_id, $balance_inicial);

            $rewards = $resultado['rewards_cosechados'] ?? array();
            if (!empty($rewards['reward_token']) && ($rewards['cantidad_rewards'] ?? 0) > 0) {
                $portfolio->agregar_tokens($rewards['reward_token'], $rewards['cantidad_rewards']);
            }

            // Registrar en historial
            $historial = new Flavor_Dex_Solana_Historial($usuario_id);
            $historial->registrar_farming(array(
                'tipo'        => 'UNSTAKE',
                'programa_id' => $programa_id,
                'lp_tokens'   => $resultado['lp_tokens_devueltos'] ?? 0,
                'modo'        => $portfolio->obtener_modo(),
            ));
        }

        return $resultado ?? array(
            'success' => false,
            'error'   => __('Error al unstakear LP tokens', 'flavor-chat-ia'),
        );
    }

    /**
     * Accion 16: Cosechar rewards
     */
    private function action_cosechar_rewards($parametros) {
        $programa_id = sanitize_text_field($parametros['programa_id'] ?? '');

        if (empty($programa_id)) {
            return array(
                'success' => false,
                'error'   => __('Debes especificar el programa_id', 'flavor-chat-ia'),
            );
        }

        $usuario_id = $this->obtener_usuario_id();
        $resultado  = $this->farming->cosechar_rewards($programa_id, $usuario_id);

        if ($resultado && ($resultado['success'] ?? false)) {
            // Agregar rewards al portfolio
            $balance_inicial = floatval($this->get_setting('balance_inicial_usdc', 1000.0));
            $portfolio = new Flavor_Dex_Solana_Portfolio($usuario_id, $balance_inicial);

            $reward_token    = $resultado['reward_token'] ?? '';
            $cantidad_rewards = floatval($resultado['cantidad_rewards'] ?? 0);

            if (!empty($reward_token) && $cantidad_rewards > 0) {
                $portfolio->agregar_tokens($reward_token, $cantidad_rewards);
            }

            // Registrar en historial
            $historial = new Flavor_Dex_Solana_Historial($usuario_id);
            $historial->registrar_farming(array(
                'tipo'             => 'HARVEST',
                'programa_id'      => $programa_id,
                'reward_token'     => $reward_token,
                'cantidad_rewards' => $cantidad_rewards,
                'modo'             => $portfolio->obtener_modo(),
            ));
        }

        return $resultado ?? array(
            'success' => false,
            'error'   => __('Error al cosechar rewards', 'flavor-chat-ia'),
        );
    }

    /**
     * Accion 17: Cambiar modo (paper/real)
     */
    private function action_cambiar_modo($parametros) {
        $modo_nuevo      = sanitize_text_field($parametros['modo'] ?? '');
        $wallet_address  = sanitize_text_field($parametros['wallet_address'] ?? '');

        if (!in_array($modo_nuevo, array('paper', 'real'), true)) {
            return array(
                'success' => false,
                'error'   => __('Modo invalido. Usa "paper" o "real".', 'flavor-chat-ia'),
            );
        }

        $usuario_id = $this->obtener_usuario_id();
        $balance_inicial = floatval($this->get_setting('balance_inicial_usdc', 1000.0));
        $portfolio = new Flavor_Dex_Solana_Portfolio($usuario_id, $balance_inicial);

        $resultado_cambio = $portfolio->cambiar_modo($modo_nuevo);

        if ($resultado_cambio === false) {
            return array(
                'success' => false,
                'error'   => __('No se pudo cambiar el modo', 'flavor-chat-ia'),
            );
        }

        if ($modo_nuevo === 'real' && !empty($wallet_address)) {
            $portfolio->set_wallet_address($wallet_address);
        }

        $this->update_setting('modo_activo', $modo_nuevo);

        $mensaje_modo = ($modo_nuevo === 'paper')
            ? __('Modo paper activado. Las operaciones son simuladas con precios reales de Jupiter.', 'flavor-chat-ia')
            : __('Modo real activado. Las transacciones se prepararan sin firmar para tu wallet.', 'flavor-chat-ia');

        return array(
            'success' => true,
            'modo'    => $modo_nuevo,
            'mensaje' => $mensaje_modo,
        );
    }

    /**
     * Accion 18: Reset DEX
     */
    private function action_reset_dex($parametros) {
        $usuario_id = $this->obtener_usuario_id();
        $balance_inicial = floatval($this->get_setting('balance_inicial_usdc', 1000.0));

        $portfolio = new Flavor_Dex_Solana_Portfolio($usuario_id, $balance_inicial);
        $portfolio->reset();

        return array(
            'success' => true,
            'mensaje' => sprintf(
                __('DEX reiniciado. Balance inicial: $%s USDC. Modo: paper.', 'flavor-chat-ia'),
                number_format($balance_inicial, 2)
            ),
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
                'name'         => 'dex_solana_obtener_cotizacion_swap',
                'description'  => 'Obtiene una cotizacion en tiempo real para un swap entre dos tokens de Solana via Jupiter Aggregator. Muestra precio, slippage estimado, impacto en precio y comisiones.',
                'input_schema' => array(
                    'type'       => 'object',
                    'properties' => array(
                        'token_entrada' => array(
                            'type'        => 'string',
                            'description' => 'Simbolo del token de entrada (ej: SOL, USDC, BONK)',
                        ),
                        'token_salida' => array(
                            'type'        => 'string',
                            'description' => 'Simbolo del token de salida (ej: USDC, SOL, JUP)',
                        ),
                        'cantidad' => array(
                            'type'        => 'number',
                            'description' => 'Cantidad del token de entrada a intercambiar',
                        ),
                        'slippage' => array(
                            'type'        => 'number',
                            'description' => 'Slippage maximo en porcentaje (por defecto 0.5%)',
                        ),
                    ),
                    'required' => array('token_entrada', 'token_salida', 'cantidad'),
                ),
            ),
            array(
                'name'         => 'dex_solana_ejecutar_swap',
                'description'  => 'Ejecuta un swap de tokens. En modo paper: simulacion con precios reales de Jupiter. En modo real: prepara la transaccion sin firmar para la wallet del usuario.',
                'input_schema' => array(
                    'type'       => 'object',
                    'properties' => array(
                        'token_entrada' => array(
                            'type'        => 'string',
                            'description' => 'Simbolo del token a vender (ej: SOL, USDC)',
                        ),
                        'token_salida' => array(
                            'type'        => 'string',
                            'description' => 'Simbolo del token a comprar (ej: USDC, JUP, BONK)',
                        ),
                        'cantidad' => array(
                            'type'        => 'number',
                            'description' => 'Cantidad del token de entrada',
                        ),
                        'slippage' => array(
                            'type'        => 'number',
                            'description' => 'Slippage maximo en porcentaje (por defecto 0.5%)',
                        ),
                    ),
                    'required' => array('token_entrada', 'token_salida', 'cantidad'),
                ),
            ),
            array(
                'name'         => 'dex_solana_preparar_swap_real',
                'description'  => 'Prepara una transaccion de swap sin firmar para el modo real. Devuelve la transaccion serializada que el usuario debe firmar con su wallet (Phantom/Solflare).',
                'input_schema' => array(
                    'type'       => 'object',
                    'properties' => array(
                        'token_entrada' => array(
                            'type'        => 'string',
                            'description' => 'Simbolo del token de entrada',
                        ),
                        'token_salida' => array(
                            'type'        => 'string',
                            'description' => 'Simbolo del token de salida',
                        ),
                        'cantidad' => array(
                            'type'        => 'number',
                            'description' => 'Cantidad del token de entrada',
                        ),
                        'slippage' => array(
                            'type'        => 'number',
                            'description' => 'Slippage maximo en porcentaje',
                        ),
                    ),
                    'required' => array('token_entrada', 'token_salida', 'cantidad'),
                ),
            ),
            array(
                'name'         => 'dex_solana_obtener_portfolio',
                'description'  => 'Obtiene el estado completo del portfolio DEX del usuario: balances USDC/SOL, tokens SPL, posiciones LP, posiciones farming, fees acumuladas, modo activo.',
                'input_schema' => array(
                    'type'       => 'object',
                    'properties' => new \stdClass(),
                ),
            ),
            array(
                'name'         => 'dex_solana_obtener_historial_swaps',
                'description'  => 'Obtiene el historial de swaps realizados por el usuario, incluyendo precios de ejecucion, comisiones y estadisticas acumuladas.',
                'input_schema' => array(
                    'type'       => 'object',
                    'properties' => array(
                        'limite' => array(
                            'type'        => 'integer',
                            'description' => 'Numero maximo de swaps a mostrar (por defecto 20)',
                        ),
                    ),
                ),
            ),
            array(
                'name'         => 'dex_solana_buscar_token',
                'description'  => 'Busca un token de Solana por su simbolo o nombre. Busca en tokens conocidos y en la lista de Jupiter.',
                'input_schema' => array(
                    'type'       => 'object',
                    'properties' => array(
                        'busqueda' => array(
                            'type'        => 'string',
                            'description' => 'Simbolo o nombre del token a buscar (ej: SOL, Jupiter, BONK)',
                        ),
                    ),
                    'required' => array('busqueda'),
                ),
            ),
            array(
                'name'         => 'dex_solana_obtener_precios',
                'description'  => 'Obtiene los precios actuales en USD de uno o varios tokens de Solana via Jupiter Price API.',
                'input_schema' => array(
                    'type'       => 'object',
                    'properties' => array(
                        'tokens' => array(
                            'type'        => 'string',
                            'description' => 'Tokens separados por comas (ej: "SOL,USDC,JUP,BONK"). Si no se especifica, usa los tokens favoritos.',
                        ),
                    ),
                ),
            ),
            array(
                'name'         => 'dex_solana_listar_pools',
                'description'  => 'Lista todos los pools de liquidez AMM disponibles con sus reservas, volumen 24h, fees acumuladas y APY estimado.',
                'input_schema' => array(
                    'type'       => 'object',
                    'properties' => new \stdClass(),
                ),
            ),
            array(
                'name'         => 'dex_solana_obtener_pool',
                'description'  => 'Obtiene los detalles completos de un pool de liquidez especifico: reservas, constante k, LP tokens totales, fee, volumen y APY.',
                'input_schema' => array(
                    'type'       => 'object',
                    'properties' => array(
                        'pool_id' => array(
                            'type'        => 'string',
                            'description' => 'ID del pool de liquidez',
                        ),
                    ),
                    'required' => array('pool_id'),
                ),
            ),
            array(
                'name'         => 'dex_solana_agregar_liquidez',
                'description'  => 'Agrega liquidez a un pool AMM depositando pares de tokens proporcionalmente. Recibe LP tokens a cambio.',
                'input_schema' => array(
                    'type'       => 'object',
                    'properties' => array(
                        'pool_id' => array(
                            'type'        => 'string',
                            'description' => 'ID del pool al que agregar liquidez',
                        ),
                        'cantidad_token_a' => array(
                            'type'        => 'number',
                            'description' => 'Cantidad del primer token del par',
                        ),
                        'cantidad_token_b' => array(
                            'type'        => 'number',
                            'description' => 'Cantidad del segundo token del par',
                        ),
                    ),
                    'required' => array('pool_id', 'cantidad_token_a', 'cantidad_token_b'),
                ),
            ),
            array(
                'name'         => 'dex_solana_retirar_liquidez',
                'description'  => 'Retira liquidez de un pool AMM quemando LP tokens. Devuelve los tokens depositados proporcionalmente.',
                'input_schema' => array(
                    'type'       => 'object',
                    'properties' => array(
                        'pool_id' => array(
                            'type'        => 'string',
                            'description' => 'ID del pool del que retirar liquidez',
                        ),
                        'porcentaje' => array(
                            'type'        => 'number',
                            'description' => 'Porcentaje de la posicion a retirar (1-100, por defecto 100%)',
                        ),
                    ),
                    'required' => array('pool_id'),
                ),
            ),
            array(
                'name'         => 'dex_solana_obtener_posiciones_lp',
                'description'  => 'Obtiene todas las posiciones de liquidez (LP) del usuario en los distintos pools, con datos actuales del pool y APY.',
                'input_schema' => array(
                    'type'       => 'object',
                    'properties' => new \stdClass(),
                ),
            ),
            array(
                'name'         => 'dex_solana_listar_programas_farming',
                'description'  => 'Lista todos los programas de yield farming activos con sus recompensas, APR y duracion.',
                'input_schema' => array(
                    'type'       => 'object',
                    'properties' => new \stdClass(),
                ),
            ),
            array(
                'name'         => 'dex_solana_stakear_lp',
                'description'  => 'Stakea los LP tokens del usuario en un programa de yield farming para ganar recompensas.',
                'input_schema' => array(
                    'type'       => 'object',
                    'properties' => array(
                        'programa_id' => array(
                            'type'        => 'string',
                            'description' => 'ID del programa de farming',
                        ),
                    ),
                    'required' => array('programa_id'),
                ),
            ),
            array(
                'name'         => 'dex_solana_unstakear_lp',
                'description'  => 'Retira los LP tokens stakeados de un programa de farming. Cosecha automaticamente los rewards pendientes.',
                'input_schema' => array(
                    'type'       => 'object',
                    'properties' => array(
                        'programa_id' => array(
                            'type'        => 'string',
                            'description' => 'ID del programa de farming',
                        ),
                    ),
                    'required' => array('programa_id'),
                ),
            ),
            array(
                'name'         => 'dex_solana_cosechar_rewards',
                'description'  => 'Cosecha (harvest) las recompensas pendientes de farming sin retirar los LP tokens stakeados.',
                'input_schema' => array(
                    'type'       => 'object',
                    'properties' => array(
                        'programa_id' => array(
                            'type'        => 'string',
                            'description' => 'ID del programa de farming',
                        ),
                    ),
                    'required' => array('programa_id'),
                ),
            ),
            array(
                'name'         => 'dex_solana_cambiar_modo',
                'description'  => 'Cambia entre modo paper (simulacion) y modo real (prepara transacciones sin firmar para wallet). En modo real requiere wallet address.',
                'input_schema' => array(
                    'type'       => 'object',
                    'properties' => array(
                        'modo' => array(
                            'type'        => 'string',
                            'description' => 'Modo a activar: "paper" o "real"',
                            'enum'        => array('paper', 'real'),
                        ),
                        'wallet_address' => array(
                            'type'        => 'string',
                            'description' => 'Direccion de wallet Solana (requerida para modo real)',
                        ),
                    ),
                    'required' => array('modo'),
                ),
            ),
            array(
                'name'         => 'dex_solana_reset_dex',
                'description'  => 'Reinicia completamente la simulacion DEX: borra todos los swaps, posiciones LP, farming y vuelve al balance inicial en USDC.',
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
**DEX Solana - Exchange Descentralizado Simulado**

Sistema de exchange descentralizado para tokens del ecosistema Solana con precios reales de Jupiter Aggregator.

**IMPORTANTE: En modo paper, las operaciones son simuladas con precios reales. NO se opera con dinero real. En modo real, se preparan transacciones sin firmar que el usuario firma con su wallet.**

**Como funciona el DEX:**
1. **Swap de tokens**: Intercambio de tokens Solana usando cotizaciones reales de Jupiter Aggregator v6
2. **Pools de liquidez**: AMM simulados con formula constant product (x*y=k), igual que Raydium/Orca
3. **Yield farming**: Stake de LP tokens para ganar recompensas adicionales
4. **Modo dual**: Paper trading (simulacion) o Real (transacciones sin firmar para wallet)

**Tokens soportados:**
- SOL (Solana nativo), USDC, USDT (stablecoins)
- JUP (Jupiter), RAY (Raydium), ORCA
- BONK, WIF (memecoins), JTO (Jito), PYTH (Pyth Network)
- Cualquier token listado en Jupiter

**Swap de tokens:**
- Cotizaciones en tiempo real via Jupiter Aggregator API v6
- Slippage configurable (por defecto 0.5%)
- Comisiones simuladas realistas: red Solana + DEX fee 0.25%
- Impacto en precio y mejor ruta mostrados

**Pools de liquidez AMM:**
- Formula: x * y = k (constant product market maker)
- Fee por swap: 0.30% (configurable por pool)
- LP tokens proporcionales al aporte
- Pools semilla: SOL/USDC, RAY/USDC, JUP/USDC, BONK/USDC, SOL/RAY
- APY estimado basado en volumen y fees

**Yield Farming:**
- Stake de LP tokens en programas de recompensas
- Rewards proporcionales: (tu_staked / total_staked) * rate * tiempo
- APR calculado en tiempo real
- Harvest (cosecha) de rewards sin unstakear

**Modo Real (limitaciones):**
- Jupiter API v6 devuelve transacciones serializadas
- PHP no gestiona claves privadas (seguridad)
- Flujo: backend prepara tx sin firmar -> frontend firma con Phantom/Solflare
- Se devuelve campo `transaccion_sin_firmar` para el frontend

**Comisiones Solana simuladas:**
- Comision de red base: ~$0.001
- Comision de prioridad: ~$0.10
- Comision DEX: 0.25% del trade
- Slippage estimado: ~0.1%

**Portfolio:**
- Balance inicial: 1000 USDC (configurable)
- Seguimiento de todos los tokens SPL
- Posiciones LP con valor de entrada
- Posiciones de farming con rewards acumulados
- Historial completo de operaciones
KNOWLEDGE;
    }

    /**
     * {@inheritdoc}
     */
    public function get_faqs() {
        return array(
            array(
                'pregunta'  => 'Es un DEX real? Puedo perder dinero?',
                'respuesta' => 'En modo paper (por defecto), es una simulacion con precios reales de Jupiter. No se opera con dinero real. En modo real, se preparan transacciones que debes firmar manualmente con tu wallet.',
            ),
            array(
                'pregunta'  => 'Como hago un swap de tokens?',
                'respuesta' => 'Dime algo como "swap 10 SOL por USDC" o "cambia 100 USDC a JUP". Obtendre la cotizacion de Jupiter y ejecutare el swap simulado.',
            ),
            array(
                'pregunta'  => 'Que tokens puedo operar?',
                'respuesta' => 'SOL, USDC, USDT, JUP, RAY, BONK, WIF, JTO, ORCA, PYTH y cualquier token listado en Jupiter. Puedes buscar tokens por nombre o simbolo.',
            ),
            array(
                'pregunta'  => 'Como funcionan los pools de liquidez?',
                'respuesta' => 'Los pools usan la formula x*y=k (como Raydium/Orca). Depositas pares de tokens proporcionalmente y recibes LP tokens. Ganas fees del 0.30% de cada swap en el pool.',
            ),
            array(
                'pregunta'  => 'Que es yield farming?',
                'respuesta' => 'Puedes stakear tus LP tokens en programas de farming para ganar recompensas adicionales. Las recompensas se acumulan proporcional a tu stake y puedes cosecharlas cuando quieras.',
            ),
            array(
                'pregunta'  => 'Cual es la diferencia entre modo paper y real?',
                'respuesta' => 'Paper mode simula operaciones con precios reales sin riesgo. Real mode prepara transacciones sin firmar que debes aprobar con tu wallet Phantom o Solflare.',
            ),
            array(
                'pregunta'  => 'Como agrego liquidez a un pool?',
                'respuesta' => 'Dime "agregar liquidez al pool SOL/USDC con 5 SOL y 750 USDC". Los montos deben ser proporcionales a las reservas actuales del pool.',
            ),
            array(
                'pregunta'  => 'Como reinicio la simulacion?',
                'respuesta' => 'Dime "reiniciar DEX" o "reset DEX". Esto borrara todo tu portfolio, historial y posiciones, volviendo al balance inicial de 1000 USDC.',
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
                'label'       => __('Hero DEX Solana', 'flavor-chat-ia'),
                'description' => __('Seccion hero del DEX con estadisticas TVL, tokens listados y volumen 24h', 'flavor-chat-ia'),
                'category'    => 'hero',
                'icon'        => 'dashicons-money-alt',
                'fields'      => array(
                    'titulo_hero'      => array('type' => 'text', 'default' => __('DEX en Solana', 'flavor-chat-ia')),
                    'subtitulo_hero'   => array('type' => 'textarea', 'default' => __('Intercambia tokens de forma descentralizada en la blockchain de Solana', 'flavor-chat-ia')),
                    'total_tvl'        => array('type' => 'text', 'default' => '$2.4M'),
                    'tokens_listados'  => array('type' => 'number', 'default' => 156),
                    'volumen_24h'      => array('type' => 'text', 'default' => '$890K'),
                    'url_conectar'     => array('type' => 'url', 'default' => '#conectar-wallet'),
                ),
                'template'    => 'dex-solana/hero',
            ),
            'features' => array(
                'label'       => __('Features DEX Solana', 'flavor-chat-ia'),
                'description' => __('Grid de funcionalidades DeFi del DEX: swap, pools, staking, gobernanza', 'flavor-chat-ia'),
                'category'    => 'features',
                'icon'        => 'dashicons-grid-view',
                'fields'      => array(
                    'titulo_features'      => array('type' => 'text', 'default' => __('Funcionalidades DeFi', 'flavor-chat-ia')),
                    'funcionalidades_dex'  => array('type' => 'repeater', 'default' => array()),
                ),
                'template'    => 'dex-solana/features',
            ),
            'cta_conectar' => array(
                'label'       => __('CTA Conectar Wallet', 'flavor-chat-ia'),
                'description' => __('Seccion CTA para conectar wallet de Solana con wallets soportadas', 'flavor-chat-ia'),
                'category'    => 'cta',
                'icon'        => 'dashicons-admin-links',
                'fields'      => array(
                    'titulo_cta'          => array('type' => 'text', 'default' => __('Conecta tu wallet y empieza', 'flavor-chat-ia')),
                    'descripcion_cta'     => array('type' => 'textarea', 'default' => __('Conecta tu wallet de Solana para acceder al intercambio descentralizado, pools de liquidez y staking.', 'flavor-chat-ia')),
                    'url_conectar'        => array('type' => 'url', 'default' => '#conectar-wallet'),
                    'wallets_soportadas'  => array('type' => 'repeater', 'default' => array()),
                ),
                'template'    => 'dex-solana/cta-conectar',
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
            'ejecutar_swap' => array(
                'title'       => __('Ejecutar Swap de Tokens', 'flavor-chat-ia'),
                'description' => __('Intercambia tokens en el DEX de Solana', 'flavor-chat-ia'),
                'fields'      => array(
                    'token_entrada' => array(
                        'type'        => 'select',
                        'label'       => __('Token de entrada', 'flavor-chat-ia'),
                        'required'    => true,
                        'options'     => array(
                            'SOL'  => 'SOL',
                            'USDC' => 'USDC',
                            'USDT' => 'USDT',
                            'JUP'  => 'JUP',
                            'RAY'  => 'RAY',
                            'BONK' => 'BONK',
                            'WIF'  => 'WIF',
                            'JTO'  => 'JTO',
                            'ORCA' => 'ORCA',
                            'PYTH' => 'PYTH',
                        ),
                    ),
                    'token_salida' => array(
                        'type'        => 'select',
                        'label'       => __('Token de salida', 'flavor-chat-ia'),
                        'required'    => true,
                        'options'     => array(
                            'SOL'  => 'SOL',
                            'USDC' => 'USDC',
                            'USDT' => 'USDT',
                            'JUP'  => 'JUP',
                            'RAY'  => 'RAY',
                            'BONK' => 'BONK',
                            'WIF'  => 'WIF',
                            'JTO'  => 'JTO',
                            'ORCA' => 'ORCA',
                            'PYTH' => 'PYTH',
                        ),
                    ),
                    'cantidad' => array(
                        'type'        => 'number',
                        'label'       => __('Cantidad', 'flavor-chat-ia'),
                        'required'    => true,
                        'min'         => 0,
                        'step'        => '0.000001',
                        'placeholder' => __('Cantidad del token de entrada', 'flavor-chat-ia'),
                    ),
                    'slippage' => array(
                        'type'        => 'number',
                        'label'       => __('Slippage maximo (%)', 'flavor-chat-ia'),
                        'min'         => 0.1,
                        'max'         => 5.0,
                        'step'        => '0.1',
                        'default'     => 0.5,
                        'description' => __('Porcentaje maximo de deslizamiento permitido (por defecto 0.5%)', 'flavor-chat-ia'),
                    ),
                ),
                'submit_text'     => __('Ejecutar Swap', 'flavor-chat-ia'),
                'success_message' => __('Swap ejecutado correctamente.', 'flavor-chat-ia'),
            ),
            'agregar_liquidez' => array(
                'title'       => __('Agregar Liquidez al Pool', 'flavor-chat-ia'),
                'description' => __('Deposita pares de tokens en un pool AMM para ganar comisiones', 'flavor-chat-ia'),
                'fields'      => array(
                    'pool_id' => array(
                        'type'     => 'hidden',
                        'required' => true,
                    ),
                    'cantidad_token_a' => array(
                        'type'        => 'number',
                        'label'       => __('Cantidad Token A', 'flavor-chat-ia'),
                        'required'    => true,
                        'min'         => 0,
                        'step'        => '0.000001',
                        'placeholder' => __('Cantidad del primer token del par', 'flavor-chat-ia'),
                    ),
                    'cantidad_token_b' => array(
                        'type'        => 'number',
                        'label'       => __('Cantidad Token B', 'flavor-chat-ia'),
                        'required'    => true,
                        'min'         => 0,
                        'step'        => '0.000001',
                        'placeholder' => __('Cantidad del segundo token del par', 'flavor-chat-ia'),
                    ),
                ),
                'submit_text'     => __('Agregar Liquidez', 'flavor-chat-ia'),
                'success_message' => __('Liquidez agregada correctamente. Recibiras LP tokens proporcionales a tu aporte.', 'flavor-chat-ia'),
            ),
            'cambiar_modo' => array(
                'title'       => __('Cambiar Modo de Operacion', 'flavor-chat-ia'),
                'description' => __('Alterna entre modo paper (simulacion) y modo real (transacciones sin firmar)', 'flavor-chat-ia'),
                'fields'      => array(
                    'modo' => array(
                        'type'     => 'select',
                        'label'    => __('Modo', 'flavor-chat-ia'),
                        'required' => true,
                        'options'  => array(
                            'paper' => __('Paper Trading (simulacion)', 'flavor-chat-ia'),
                            'real'  => __('Real (transacciones sin firmar)', 'flavor-chat-ia'),
                        ),
                        'default' => 'paper',
                    ),
                    'wallet_address' => array(
                        'type'        => 'text',
                        'label'       => __('Direccion de Wallet Solana', 'flavor-chat-ia'),
                        'placeholder' => __('Tu direccion publica de wallet (requerida para modo real)', 'flavor-chat-ia'),
                        'description' => __('Necesaria para el modo real. Compatible con Phantom, Solflare, Backpack y Ledger.', 'flavor-chat-ia'),
                    ),
                ),
                'submit_text'     => __('Cambiar Modo', 'flavor-chat-ia'),
                'success_message' => __('Modo de operacion actualizado correctamente.', 'flavor-chat-ia'),
            ),
        );

        return $configuraciones_formulario[$nombre_accion] ?? array();
    }
}

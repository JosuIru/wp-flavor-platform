<?php
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Gestiona el historial de transacciones del DEX Solana.
 *
 * Registra y consulta swaps, operaciones de liquidez y eventos de farming.
 */
class Flavor_Dex_Solana_Historial {

    /**
     * ID del usuario propietario del historial.
     *
     * @var int
     */
    private $usuario_id;

    /**
     * Nombre de la tabla de swaps con prefijo.
     *
     * @var string
     */
    private $tabla_swaps;

    /**
     * Constructor.
     *
     * @param int $usuario_id ID del usuario de WordPress.
     */
    public function __construct($usuario_id) {
        global $wpdb;

        $this->usuario_id = absint($usuario_id);
        $this->tabla_swaps = $wpdb->prefix . 'flavor_dex_swaps';
    }

    /**
     * Registra una operacion de swap en la base de datos.
     *
     * @param array $datos_swap Datos del swap a registrar.
     * @return string|false El swap_id generado en caso de exito, false en caso de error.
     */
    public function registrar_swap($datos_swap) {
        global $wpdb;

        $identificador_swap = wp_generate_uuid4();

        $ruta_codificada = isset($datos_swap['ruta_json'])
            ? (is_array($datos_swap['ruta_json']) ? wp_json_encode($datos_swap['ruta_json']) : $datos_swap['ruta_json'])
            : null;

        $datos_insercion = array(
            'swap_id'                => $identificador_swap,
            'usuario_id'             => $this->usuario_id,
            'modo'                   => isset($datos_swap['modo']) ? sanitize_text_field($datos_swap['modo']) : '',
            'token_entrada_mint'     => isset($datos_swap['token_entrada_mint']) ? sanitize_text_field($datos_swap['token_entrada_mint']) : '',
            'token_entrada_simbolo'  => isset($datos_swap['token_entrada_simbolo']) ? sanitize_text_field($datos_swap['token_entrada_simbolo']) : '',
            'token_salida_mint'      => isset($datos_swap['token_salida_mint']) ? sanitize_text_field($datos_swap['token_salida_mint']) : '',
            'token_salida_simbolo'   => isset($datos_swap['token_salida_simbolo']) ? sanitize_text_field($datos_swap['token_salida_simbolo']) : '',
            'cantidad_entrada'       => isset($datos_swap['cantidad_entrada']) ? floatval($datos_swap['cantidad_entrada']) : 0,
            'cantidad_salida'        => isset($datos_swap['cantidad_salida']) ? floatval($datos_swap['cantidad_salida']) : 0,
            'precio_ejecucion'       => isset($datos_swap['precio_ejecucion']) ? floatval($datos_swap['precio_ejecucion']) : 0,
            'slippage_configurado'   => isset($datos_swap['slippage_configurado']) ? floatval($datos_swap['slippage_configurado']) : 0,
            'slippage_real'          => isset($datos_swap['slippage_real']) ? floatval($datos_swap['slippage_real']) : 0,
            'impacto_precio'         => isset($datos_swap['impacto_precio']) ? floatval($datos_swap['impacto_precio']) : 0,
            'ruta_json'              => $ruta_codificada,
            'comision_red_usd'       => isset($datos_swap['comision_red_usd']) ? floatval($datos_swap['comision_red_usd']) : 0,
            'comision_plataforma_usd'=> isset($datos_swap['comision_plataforma_usd']) ? floatval($datos_swap['comision_plataforma_usd']) : 0,
            'fees_total_usd'         => isset($datos_swap['fees_total_usd']) ? floatval($datos_swap['fees_total_usd']) : 0,
            'tx_hash'                => isset($datos_swap['tx_hash']) ? sanitize_text_field($datos_swap['tx_hash']) : '',
            'estado'                 => isset($datos_swap['estado']) ? sanitize_text_field($datos_swap['estado']) : 'pendiente',
        );

        $formatos_columnas = array(
            '%s', // swap_id
            '%d', // usuario_id
            '%s', // modo
            '%s', // token_entrada_mint
            '%s', // token_entrada_simbolo
            '%s', // token_salida_mint
            '%s', // token_salida_simbolo
            '%f', // cantidad_entrada
            '%f', // cantidad_salida
            '%f', // precio_ejecucion
            '%f', // slippage_configurado
            '%f', // slippage_real
            '%f', // impacto_precio
            '%s', // ruta_json
            '%f', // comision_red_usd
            '%f', // comision_plataforma_usd
            '%f', // fees_total_usd
            '%s', // tx_hash
            '%s', // estado
        );

        $resultado_insercion = $wpdb->insert(
            $this->tabla_swaps,
            $datos_insercion,
            $formatos_columnas
        );

        if ($resultado_insercion === false) {
            return false;
        }

        return $identificador_swap;
    }

    /**
     * Registra una operacion de liquidez (agregar o remover) como entrada especial en la tabla de swaps.
     *
     * Los simbolos de token se marcan con prefijos LP_ADD_ o LP_REMOVE_ para identificar
     * el tipo de operacion (por ejemplo, "LP_ADD_SOL/USDC" o "LP_REMOVE_SOL/USDC").
     *
     * @param array $datos_liquidez Datos de la operacion de liquidez.
     * @return string|false El swap_id generado en caso de exito, false en caso de error.
     */
    public function registrar_liquidez($datos_liquidez) {
        $tipo_operacion = isset($datos_liquidez['tipo']) ? strtoupper(sanitize_text_field($datos_liquidez['tipo'])) : 'ADD';
        $prefijo_liquidez = ($tipo_operacion === 'REMOVE') ? 'LP_REMOVE_' : 'LP_ADD_';

        $par_tokens = isset($datos_liquidez['par_tokens']) ? sanitize_text_field($datos_liquidez['par_tokens']) : '';
        $simbolo_liquidez = $prefijo_liquidez . $par_tokens;

        $datos_swap_liquidez = array(
            'modo'                   => 'liquidez_' . strtolower($tipo_operacion),
            'token_entrada_mint'     => isset($datos_liquidez['token_a_mint']) ? $datos_liquidez['token_a_mint'] : '',
            'token_entrada_simbolo'  => $simbolo_liquidez,
            'token_salida_mint'      => isset($datos_liquidez['token_b_mint']) ? $datos_liquidez['token_b_mint'] : '',
            'token_salida_simbolo'   => $simbolo_liquidez,
            'cantidad_entrada'       => isset($datos_liquidez['cantidad_token_a']) ? $datos_liquidez['cantidad_token_a'] : 0,
            'cantidad_salida'        => isset($datos_liquidez['cantidad_token_b']) ? $datos_liquidez['cantidad_token_b'] : 0,
            'precio_ejecucion'       => isset($datos_liquidez['precio_ejecucion']) ? $datos_liquidez['precio_ejecucion'] : 0,
            'slippage_configurado'   => isset($datos_liquidez['slippage_configurado']) ? $datos_liquidez['slippage_configurado'] : 0,
            'slippage_real'          => isset($datos_liquidez['slippage_real']) ? $datos_liquidez['slippage_real'] : 0,
            'impacto_precio'         => isset($datos_liquidez['impacto_precio']) ? $datos_liquidez['impacto_precio'] : 0,
            'ruta_json'              => isset($datos_liquidez['detalles_pool']) ? $datos_liquidez['detalles_pool'] : null,
            'comision_red_usd'       => isset($datos_liquidez['comision_red_usd']) ? $datos_liquidez['comision_red_usd'] : 0,
            'comision_plataforma_usd'=> isset($datos_liquidez['comision_plataforma_usd']) ? $datos_liquidez['comision_plataforma_usd'] : 0,
            'fees_total_usd'         => isset($datos_liquidez['fees_total_usd']) ? $datos_liquidez['fees_total_usd'] : 0,
            'tx_hash'                => isset($datos_liquidez['tx_hash']) ? $datos_liquidez['tx_hash'] : '',
            'estado'                 => isset($datos_liquidez['estado']) ? $datos_liquidez['estado'] : 'pendiente',
        );

        return $this->registrar_swap($datos_swap_liquidez);
    }

    /**
     * Registra un evento de farming (stake, unstake, harvest) como entrada especial en la tabla de swaps.
     *
     * Los simbolos de token se marcan con prefijos FARM_STAKE_, FARM_UNSTAKE_ o FARM_HARVEST_
     * para identificar el tipo de evento.
     *
     * @param array $datos_farming Datos del evento de farming.
     * @return string|false El swap_id generado en caso de exito, false en caso de error.
     */
    public function registrar_farming($datos_farming) {
        $tipo_evento = isset($datos_farming['tipo']) ? strtoupper(sanitize_text_field($datos_farming['tipo'])) : 'STAKE';

        $prefijos_farming_validos = array('STAKE', 'UNSTAKE', 'HARVEST');
        if (!in_array($tipo_evento, $prefijos_farming_validos, true)) {
            $tipo_evento = 'STAKE';
        }

        $prefijo_farming = 'FARM_' . $tipo_evento . '_';
        $nombre_pool = isset($datos_farming['pool_nombre']) ? sanitize_text_field($datos_farming['pool_nombre']) : '';
        $simbolo_farming = $prefijo_farming . $nombre_pool;

        $datos_swap_farming = array(
            'modo'                   => 'farming_' . strtolower($tipo_evento),
            'token_entrada_mint'     => isset($datos_farming['token_mint']) ? $datos_farming['token_mint'] : '',
            'token_entrada_simbolo'  => $simbolo_farming,
            'token_salida_mint'      => isset($datos_farming['recompensa_mint']) ? $datos_farming['recompensa_mint'] : '',
            'token_salida_simbolo'   => $simbolo_farming,
            'cantidad_entrada'       => isset($datos_farming['cantidad_stake']) ? $datos_farming['cantidad_stake'] : 0,
            'cantidad_salida'        => isset($datos_farming['cantidad_recompensa']) ? $datos_farming['cantidad_recompensa'] : 0,
            'precio_ejecucion'       => isset($datos_farming['precio_token']) ? $datos_farming['precio_token'] : 0,
            'slippage_configurado'   => 0,
            'slippage_real'          => 0,
            'impacto_precio'         => 0,
            'ruta_json'              => isset($datos_farming['detalles_farm']) ? $datos_farming['detalles_farm'] : null,
            'comision_red_usd'       => isset($datos_farming['comision_red_usd']) ? $datos_farming['comision_red_usd'] : 0,
            'comision_plataforma_usd'=> isset($datos_farming['comision_plataforma_usd']) ? $datos_farming['comision_plataforma_usd'] : 0,
            'fees_total_usd'         => isset($datos_farming['fees_total_usd']) ? $datos_farming['fees_total_usd'] : 0,
            'tx_hash'                => isset($datos_farming['tx_hash']) ? $datos_farming['tx_hash'] : '',
            'estado'                 => isset($datos_farming['estado']) ? $datos_farming['estado'] : 'pendiente',
        );

        return $this->registrar_swap($datos_swap_farming);
    }

    /**
     * Obtiene el historial de swaps del usuario con paginacion.
     *
     * @param int $limite  Numero maximo de registros a devolver.
     * @param int $offset  Desplazamiento para la paginacion.
     * @return array|null Lista de registros de swaps o null en caso de error.
     */
    public function obtener_historial_swaps($limite = 20, $offset = 0) {
        global $wpdb;

        $limite_sanitizado = absint($limite);
        $offset_sanitizado = absint($offset);

        if ($limite_sanitizado === 0) {
            $limite_sanitizado = 20;
        }

        $consulta_historial = $wpdb->prepare(
            "SELECT * FROM {$this->tabla_swaps}
             WHERE usuario_id = %d
             ORDER BY created_at DESC
             LIMIT %d OFFSET %d",
            $this->usuario_id,
            $limite_sanitizado,
            $offset_sanitizado
        );

        $registros_swaps = $wpdb->get_results($consulta_historial, ARRAY_A);

        if ($registros_swaps === null) {
            return null;
        }

        return $registros_swaps;
    }

    /**
     * Obtiene estadisticas agregadas del historial de swaps del usuario.
     *
     * @return array Estadisticas con claves: total_swaps, volumen_total_usd,
     *               fees_totales_pagadas, pnl_estimado, primer_swap, ultimo_swap.
     */
    public function obtener_estadisticas() {
        global $wpdb;

        $estadisticas_por_defecto = array(
            'total_swaps'         => 0,
            'volumen_total_usd'   => 0.0,
            'fees_totales_pagadas'=> 0.0,
            'pnl_estimado'        => 0.0,
            'primer_swap'         => null,
            'ultimo_swap'         => null,
        );

        // Total de swaps realizados
        $consulta_total_swaps = $wpdb->prepare(
            "SELECT COUNT(*) FROM {$this->tabla_swaps} WHERE usuario_id = %d",
            $this->usuario_id
        );
        $total_swaps = (int) $wpdb->get_var($consulta_total_swaps);

        if ($total_swaps === 0) {
            return $estadisticas_por_defecto;
        }

        // Volumen total aproximado en USD (cantidad_entrada * precio_ejecucion)
        $consulta_volumen_total = $wpdb->prepare(
            "SELECT COALESCE(SUM(cantidad_entrada * precio_ejecucion), 0)
             FROM {$this->tabla_swaps}
             WHERE usuario_id = %d",
            $this->usuario_id
        );
        $volumen_total_usd = (float) $wpdb->get_var($consulta_volumen_total);

        // Fees totales pagadas
        $consulta_fees_totales = $wpdb->prepare(
            "SELECT COALESCE(SUM(fees_total_usd), 0)
             FROM {$this->tabla_swaps}
             WHERE usuario_id = %d",
            $this->usuario_id
        );
        $fees_totales_pagadas = (float) $wpdb->get_var($consulta_fees_totales);

        // PnL estimado: diferencia entre valor de salida y valor de entrada, menos fees
        $consulta_pnl_estimado = $wpdb->prepare(
            "SELECT COALESCE(
                SUM(cantidad_salida * precio_ejecucion) - SUM(cantidad_entrada * precio_ejecucion) - SUM(fees_total_usd),
                0
             )
             FROM {$this->tabla_swaps}
             WHERE usuario_id = %d
               AND estado = 'completado'",
            $this->usuario_id
        );
        $pnl_estimado = (float) $wpdb->get_var($consulta_pnl_estimado);

        // Fecha del primer swap
        $consulta_primer_swap = $wpdb->prepare(
            "SELECT MIN(created_at)
             FROM {$this->tabla_swaps}
             WHERE usuario_id = %d",
            $this->usuario_id
        );
        $fecha_primer_swap = $wpdb->get_var($consulta_primer_swap);

        // Fecha del ultimo swap
        $consulta_ultimo_swap = $wpdb->prepare(
            "SELECT MAX(created_at)
             FROM {$this->tabla_swaps}
             WHERE usuario_id = %d",
            $this->usuario_id
        );
        $fecha_ultimo_swap = $wpdb->get_var($consulta_ultimo_swap);

        $estadisticas_completas = array(
            'total_swaps'         => $total_swaps,
            'volumen_total_usd'   => round($volumen_total_usd, 2),
            'fees_totales_pagadas'=> round($fees_totales_pagadas, 2),
            'pnl_estimado'        => round($pnl_estimado, 2),
            'primer_swap'         => $fecha_primer_swap,
            'ultimo_swap'         => $fecha_ultimo_swap,
        );

        return $estadisticas_completas;
    }
}

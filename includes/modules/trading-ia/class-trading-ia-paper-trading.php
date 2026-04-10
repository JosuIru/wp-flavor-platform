<?php
/**
 * Paper Trading - Simulacion de trading sin dinero real
 *
 * @package FlavorPlatform
 */

if (!defined('ABSPATH')) {
    exit;
}

class Flavor_Trading_IA_Paper_Trading {

    /**
     * Comisiones realistas de Solana/Jupiter
     */
    const COMISION_RED_SOL        = 0.000005;
    const COMISION_PRIORIDAD_SOL  = 0.0005;
    const COMISION_DEX_PORCENTAJE = 0.25;
    const SLIPPAGE_PORCENTAJE     = 0.3;

    /**
     * ID del usuario
     */
    private $usuario_id;

    /**
     * Balance en USD
     */
    private $balance_usd;

    /**
     * Balance inicial
     */
    private $balance_inicial;

    /**
     * Tokens en cartera (simbolo => cantidad)
     */
    private $tokens;

    /**
     * Precios de entrada (simbolo => precio)
     */
    private $precios_entrada;

    /**
     * Fees acumuladas
     */
    private $fees_acumuladas_usd;

    /**
     * Contador de trades
     */
    private $contador_trades;

    /**
     * Precios actuales del mercado
     */
    private $precios_actuales;

    /**
     * Constructor - carga o crea portfolio del usuario
     *
     * @param int   $usuario_id ID del usuario
     * @param float $balance_inicial Balance inicial para nuevos portfolios
     */
    public function __construct($usuario_id, $balance_inicial = 1000.0) {
        $this->usuario_id = $usuario_id;
        $this->precios_actuales = array('SOL' => 150.0);

        $portfolio_existente = $this->cargar_portfolio();

        if ($portfolio_existente) {
            $this->balance_usd        = floatval($portfolio_existente->balance_usd);
            $this->balance_inicial    = floatval($portfolio_existente->balance_inicial);
            $this->tokens             = json_decode($portfolio_existente->tokens_json, true) ?: array();
            $this->precios_entrada    = json_decode($portfolio_existente->precios_entrada_json, true) ?: array();
            $this->fees_acumuladas_usd = floatval($portfolio_existente->fees_acumuladas_usd);
            $this->contador_trades    = intval($portfolio_existente->contador_trades);
        } else {
            $this->balance_usd        = $balance_inicial;
            $this->balance_inicial    = $balance_inicial;
            $this->tokens             = array();
            $this->precios_entrada    = array();
            $this->fees_acumuladas_usd = 0.0;
            $this->contador_trades    = 0;
            $this->guardar_portfolio();
        }
    }

    /**
     * Carga el portfolio del usuario desde la base de datos
     */
    private function cargar_portfolio() {
        global $wpdb;
        $tabla = $wpdb->prefix . 'flavor_trading_ia_portfolio';

        return $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM $tabla WHERE usuario_id = %d",
            $this->usuario_id
        ));
    }

    /**
     * Guarda el estado del portfolio en la base de datos
     */
    public function guardar_portfolio() {
        global $wpdb;
        $tabla = $wpdb->prefix . 'flavor_trading_ia_portfolio';

        $datos = array(
            'usuario_id'          => $this->usuario_id,
            'balance_usd'         => $this->balance_usd,
            'balance_inicial'     => $this->balance_inicial,
            'tokens_json'         => wp_json_encode($this->tokens),
            'precios_entrada_json' => wp_json_encode($this->precios_entrada),
            'fees_acumuladas_usd' => $this->fees_acumuladas_usd,
            'contador_trades'     => $this->contador_trades,
        );

        $existe = $wpdb->get_var($wpdb->prepare(
            "SELECT id FROM $tabla WHERE usuario_id = %d",
            $this->usuario_id
        ));

        if ($existe) {
            $wpdb->update($tabla, $datos, array('usuario_id' => $this->usuario_id));
        } else {
            $wpdb->insert($tabla, $datos);
        }
    }

    /**
     * Calcula las comisiones realistas de una operacion en Solana
     *
     * @param float $monto_usd Monto de la operacion
     * @return array Desglose de comisiones
     */
    private function calcular_comisiones($monto_usd) {
        $precio_sol = isset($this->precios_actuales['SOL']) ? $this->precios_actuales['SOL'] : 150.0;

        $comision_red_sol = self::COMISION_RED_SOL + self::COMISION_PRIORIDAD_SOL;
        $comision_red_usd = $comision_red_sol * $precio_sol;

        $comision_dex_usd = $monto_usd * (self::COMISION_DEX_PORCENTAJE / 100);
        $slippage_usd     = $monto_usd * (self::SLIPPAGE_PORCENTAJE / 100);
        $fees_total_usd   = $comision_red_usd + $comision_dex_usd + $slippage_usd;

        return array(
            'comision_red_usd' => round($comision_red_usd, 6),
            'comision_dex_usd' => round($comision_dex_usd, 6),
            'slippage_usd'     => round($slippage_usd, 6),
            'fees_total_usd'   => round($fees_total_usd, 6),
        );
    }

    /**
     * Calcula el balance total en USD
     *
     * @return float Balance total
     */
    public function obtener_balance_total_usd() {
        $total = $this->balance_usd;

        foreach ($this->tokens as $token => $cantidad) {
            $precio = isset($this->precios_actuales[$token]) ? $this->precios_actuales[$token] : 0;
            $total += $cantidad * $precio;
        }

        return $total;
    }

    /**
     * Obtiene el estado completo del portfolio
     *
     * @return array Estado del portfolio
     */
    public function obtener_estado_portfolio() {
        $balance_total    = $this->obtener_balance_total_usd();
        $pnl_total        = $balance_total - $this->balance_inicial;
        $pnl_porcentaje   = $this->balance_inicial > 0
            ? ($pnl_total / $this->balance_inicial * 100)
            : 0;

        $posiciones = array();
        foreach ($this->tokens as $token => $cantidad) {
            if ($cantidad <= 0) {
                continue;
            }

            $precio_actual  = isset($this->precios_actuales[$token]) ? $this->precios_actuales[$token] : 0;
            $precio_entrada = isset($this->precios_entrada[$token]) ? $this->precios_entrada[$token] : $precio_actual;
            $valor_usd      = $cantidad * $precio_actual;
            $pnl_posicion   = $precio_entrada > 0
                ? (($precio_actual - $precio_entrada) / $precio_entrada * 100)
                : 0;

            $posiciones[] = array(
                'token'           => $token,
                'cantidad'        => $cantidad,
                'valor_usd'       => $valor_usd,
                'precio_actual'   => $precio_actual,
                'precio_entrada'  => $precio_entrada,
                'pnl_porcentaje'  => round($pnl_posicion, 2),
            );
        }

        return array(
            'balance_total_usd'    => round($balance_total, 2),
            'disponible_usd'       => round($this->balance_usd, 2),
            'balance_inicial'      => $this->balance_inicial,
            'pnl_total_usd'        => round($pnl_total, 2),
            'pnl_total_porcentaje' => round($pnl_porcentaje, 2),
            'posiciones'           => $posiciones,
            'total_trades'         => $this->contador_trades,
            'fees_acumuladas_usd'  => round($this->fees_acumuladas_usd, 6),
        );
    }

    /**
     * Ejecuta una compra simulada
     *
     * @param string     $token Token a comprar
     * @param float      $cantidad_usd Cantidad en USD
     * @param float|null $precio Precio (null = usar precio actual)
     * @return array Resultado de la operacion
     */
    public function ejecutar_compra($token, $cantidad_usd, $precio = null) {
        if ($cantidad_usd > $this->balance_usd) {
            return array(
                'exito' => false,
                'error' => sprintf(
                    __('Balance insuficiente. Disponible: $%.2f', 'flavor-platform'),
                    $this->balance_usd
                ),
            );
        }

        if (null === $precio) {
            $precio = isset($this->precios_actuales[$token]) ? $this->precios_actuales[$token] : 1.0;
        }

        $comisiones       = $this->calcular_comisiones($cantidad_usd);
        $monto_efectivo   = $cantidad_usd - $comisiones['fees_total_usd'];
        $cantidad_tokens  = $precio > 0 ? $monto_efectivo / $precio : 0;

        // Actualizar balances
        $this->balance_usd -= $cantidad_usd;
        $this->fees_acumuladas_usd += $comisiones['fees_total_usd'];

        $cantidad_existente = isset($this->tokens[$token]) ? $this->tokens[$token] : 0;

        // Precio de entrada promedio ponderado
        if (isset($this->precios_entrada[$token]) && $cantidad_existente > 0) {
            $nueva_cantidad_total = $cantidad_existente + $cantidad_tokens;
            if ($nueva_cantidad_total > 0) {
                $this->precios_entrada[$token] = (
                    ($this->precios_entrada[$token] * $cantidad_existente) +
                    ($precio * $cantidad_tokens)
                ) / $nueva_cantidad_total;
            }
        } else {
            $this->precios_entrada[$token] = $precio;
        }

        $this->tokens[$token] = $cantidad_existente + $cantidad_tokens;

        // Registrar trade
        $this->contador_trades++;
        $trade_id = 'paper_' . $this->contador_trades;

        $this->registrar_trade(array(
            'trade_id'          => $trade_id,
            'tipo'              => 'COMPRA',
            'token_comprado'    => $token,
            'token_vendido'     => 'USD',
            'cantidad_comprada' => $cantidad_tokens,
            'cantidad_vendida'  => $cantidad_usd,
            'precio'            => $precio,
            'pnl'               => 0,
            'comision_red_usd'  => $comisiones['comision_red_usd'],
            'comision_dex_usd'  => $comisiones['comision_dex_usd'],
            'slippage_usd'      => $comisiones['slippage_usd'],
            'fees_total_usd'    => $comisiones['fees_total_usd'],
        ));

        $this->guardar_portfolio();

        return array(
            'exito'               => true,
            'trade_id'            => $trade_id,
            'token'               => $token,
            'cantidad'            => $cantidad_tokens,
            'precio'              => $precio,
            'usd_gastado'         => $cantidad_usd,
            'fees_usd'            => $comisiones['fees_total_usd'],
            'balance_usd_restante' => $this->balance_usd,
        );
    }

    /**
     * Ejecuta una venta simulada
     *
     * @param string     $token Token a vender
     * @param float|null $cantidad Cantidad (null = vender todo)
     * @param float|null $precio Precio (null = usar precio actual)
     * @return array Resultado de la operacion
     */
    public function ejecutar_venta($token, $cantidad = null, $precio = null) {
        $cantidad_disponible = isset($this->tokens[$token]) ? $this->tokens[$token] : 0;

        if ($cantidad_disponible <= 0) {
            return array(
                'exito' => false,
                'error' => sprintf(__('No tienes %s para vender', 'flavor-platform'), $token),
            );
        }

        $cantidad_vender = null !== $cantidad ? $cantidad : $cantidad_disponible;
        if ($cantidad_vender > $cantidad_disponible) {
            $cantidad_vender = $cantidad_disponible;
        }

        if (null === $precio) {
            $precio = isset($this->precios_actuales[$token]) ? $this->precios_actuales[$token] : 1.0;
        }

        $valor_bruto_usd = $cantidad_vender * $precio;
        $comisiones      = $this->calcular_comisiones($valor_bruto_usd);
        $usd_recibido    = $valor_bruto_usd - $comisiones['fees_total_usd'];

        $precio_entrada = isset($this->precios_entrada[$token]) ? $this->precios_entrada[$token] : $precio;
        $pnl = $precio_entrada > 0 ? (($precio - $precio_entrada) / $precio_entrada * 100) : 0;

        // Actualizar balances
        $this->balance_usd += $usd_recibido;
        $this->fees_acumuladas_usd += $comisiones['fees_total_usd'];
        $this->tokens[$token] = $cantidad_disponible - $cantidad_vender;

        if ($this->tokens[$token] <= 0) {
            unset($this->tokens[$token]);
            unset($this->precios_entrada[$token]);
        }

        // Registrar trade
        $this->contador_trades++;
        $trade_id = 'paper_' . $this->contador_trades;

        $this->registrar_trade(array(
            'trade_id'          => $trade_id,
            'tipo'              => 'VENTA',
            'token_comprado'    => 'USD',
            'token_vendido'     => $token,
            'cantidad_comprada' => $usd_recibido,
            'cantidad_vendida'  => $cantidad_vender,
            'precio'            => $precio,
            'pnl'               => round($pnl, 4),
            'comision_red_usd'  => $comisiones['comision_red_usd'],
            'comision_dex_usd'  => $comisiones['comision_dex_usd'],
            'slippage_usd'      => $comisiones['slippage_usd'],
            'fees_total_usd'    => $comisiones['fees_total_usd'],
        ));

        $this->guardar_portfolio();

        return array(
            'exito'            => true,
            'trade_id'         => $trade_id,
            'token'            => $token,
            'cantidad_vendida' => $cantidad_vender,
            'precio'           => $precio,
            'usd_recibido'     => $usd_recibido,
            'fees_usd'         => $comisiones['fees_total_usd'],
            'pnl_porcentaje'   => round($pnl, 2),
            'balance_usd'      => $this->balance_usd,
        );
    }

    /**
     * Registra un trade en la base de datos
     *
     * @param array $datos Datos del trade
     */
    private function registrar_trade($datos) {
        global $wpdb;
        $tabla = $wpdb->prefix . 'flavor_trading_ia_trades';

        $wpdb->insert($tabla, array(
            'trade_id'          => $datos['trade_id'],
            'usuario_id'        => $this->usuario_id,
            'timestamp'         => current_time('mysql'),
            'tipo'              => $datos['tipo'],
            'token_comprado'    => $datos['token_comprado'],
            'token_vendido'     => $datos['token_vendido'],
            'cantidad_comprada' => $datos['cantidad_comprada'],
            'cantidad_vendida'  => $datos['cantidad_vendida'],
            'precio'            => $datos['precio'],
            'pnl'               => $datos['pnl'],
            'comision_red_usd'  => $datos['comision_red_usd'],
            'comision_dex_usd'  => $datos['comision_dex_usd'],
            'slippage_usd'      => $datos['slippage_usd'],
            'fees_total_usd'    => $datos['fees_total_usd'],
        ));
    }

    /**
     * Actualiza los precios de mercado
     *
     * @param array $precios Precios indexados por simbolo
     */
    public function actualizar_precios($precios) {
        $this->precios_actuales = array_merge($this->precios_actuales, $precios);
    }

    /**
     * Reinicia el paper trading
     */
    public function reset() {
        global $wpdb;

        $this->balance_usd         = $this->balance_inicial;
        $this->tokens              = array();
        $this->precios_entrada     = array();
        $this->fees_acumuladas_usd = 0.0;
        $this->contador_trades     = 0;

        // Limpiar trades del usuario
        $tabla_trades = $wpdb->prefix . 'flavor_trading_ia_trades';
        $wpdb->delete($tabla_trades, array('usuario_id' => $this->usuario_id));

        // Limpiar decisiones del usuario
        $tabla_decisiones = $wpdb->prefix . 'flavor_trading_ia_decisiones';
        $wpdb->delete($tabla_decisiones, array('usuario_id' => $this->usuario_id));

        $this->guardar_portfolio();
    }

    /**
     * Obtiene el historial de trades del usuario
     *
     * @param int $limite Numero maximo de trades
     * @return array Historial de trades
     */
    public function obtener_historial($limite = 50) {
        global $wpdb;
        $tabla = $wpdb->prefix . 'flavor_trading_ia_trades';

        $resultados = $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM $tabla WHERE usuario_id = %d ORDER BY timestamp DESC LIMIT %d",
            $this->usuario_id,
            $limite
        ), ARRAY_A);

        return $resultados ?: array();
    }

    /**
     * Obtiene los tokens en cartera
     *
     * @return array Tokens y cantidades
     */
    public function obtener_tokens() {
        return $this->tokens;
    }

    /**
     * Obtiene los precios de entrada
     *
     * @return array Precios de entrada por token
     */
    public function obtener_precios_entrada() {
        return $this->precios_entrada;
    }

    /**
     * Obtiene el balance disponible en USD
     *
     * @return float Balance USD
     */
    public function obtener_balance_usd() {
        return $this->balance_usd;
    }
}

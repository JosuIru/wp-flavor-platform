<?php
if (!defined('ABSPATH')) { exit; }

/**
 * Motor de ejecucion de swaps para Solana DEX.
 * Gestiona swaps en modo paper y modo real mediante Jupiter API.
 */
class Flavor_Dex_Solana_Swap_Engine {

    /**
     * @var object Instancia de la API de Jupiter.
     */
    private $jupiter_api;

    /**
     * @var object Registro de tokens disponibles.
     */
    private $token_registry;

    /**
     * @var object Portfolio del usuario.
     */
    private $portfolio;

    /**
     * @var object Historial de operaciones.
     */
    private $historial;

    /**
     * Constructor.
     *
     * @param object $jupiter_api   Instancia de la API de Jupiter.
     * @param object $token_registry Registro de tokens.
     * @param object $portfolio      Portfolio del usuario.
     * @param object $historial      Historial de operaciones.
     */
    public function __construct($jupiter_api, $token_registry, $portfolio, $historial) {
        $this->jupiter_api    = $jupiter_api;
        $this->token_registry = $token_registry;
        $this->portfolio      = $portfolio;
        $this->historial      = $historial;
    }

    /**
     * Obtiene una cotizacion real desde Jupiter para un par de tokens.
     *
     * @param string $simbolo_entrada      Simbolo del token de entrada (ej: SOL, USDC).
     * @param string $simbolo_salida       Simbolo del token de salida.
     * @param float  $cantidad             Cantidad en unidades legibles del token de entrada.
     * @param float  $slippage_porcentaje  Porcentaje de slippage permitido (por defecto 0.5%).
     * @return array Resultado con la cotizacion o error.
     */
    public function obtener_cotizacion_real($simbolo_entrada, $simbolo_salida, $cantidad, $slippage_porcentaje = 0.5) {
        // Resolver simbolos a direcciones mint
        $direccion_mint_entrada = $this->token_registry->obtener_direccion_mint($simbolo_entrada);
        $direccion_mint_salida  = $this->token_registry->obtener_direccion_mint($simbolo_salida);

        if (!$direccion_mint_entrada) {
            return array(
                'success' => false,
                'error'   => sprintf('Token de entrada no encontrado: %s', $simbolo_entrada),
            );
        }

        if (!$direccion_mint_salida) {
            return array(
                'success' => false,
                'error'   => sprintf('Token de salida no encontrado: %s', $simbolo_salida),
            );
        }

        // Obtener decimales de cada token para convertir unidades
        $decimales_entrada = $this->token_registry->obtener_decimales($simbolo_entrada);
        $decimales_salida  = $this->token_registry->obtener_decimales($simbolo_salida);

        // Convertir cantidad legible a unidades base (lamports, etc.)
        $cantidad_en_unidades_base = intval($cantidad * pow(10, $decimales_entrada));

        // Convertir slippage de porcentaje a puntos basicos (BPS)
        $slippage_en_bps = intval($slippage_porcentaje * 100);

        // Solicitar cotizacion a Jupiter API
        $respuesta_cotizacion = $this->jupiter_api->obtener_cotizacion(
            $direccion_mint_entrada,
            $direccion_mint_salida,
            $cantidad_en_unidades_base,
            $slippage_en_bps
        );

        if (!$respuesta_cotizacion || isset($respuesta_cotizacion['error'])) {
            $mensaje_error = isset($respuesta_cotizacion['error']) ? $respuesta_cotizacion['error'] : 'Error desconocido al obtener cotizacion de Jupiter';
            return array(
                'success' => false,
                'error'   => $mensaje_error,
            );
        }

        // Convertir cantidad de salida de unidades base a formato legible
        $cantidad_salida_raw    = isset($respuesta_cotizacion['outAmount']) ? $respuesta_cotizacion['outAmount'] : 0;
        $cantidad_salida_legible = $cantidad_salida_raw / pow(10, $decimales_salida);

        // Calcular precio unitario (cuantos tokens de salida por cada token de entrada)
        $precio_unitario = ($cantidad > 0) ? ($cantidad_salida_legible / $cantidad) : 0;

        // Extraer impacto de precio de la respuesta de Jupiter
        $impacto_precio = isset($respuesta_cotizacion['priceImpactPct']) ? floatval($respuesta_cotizacion['priceImpactPct']) : 0;

        // Extraer informacion de la ruta
        $informacion_ruta = isset($respuesta_cotizacion['routePlan']) ? $respuesta_cotizacion['routePlan'] : array();

        // Estimar el valor en USD para calcular comisiones
        $valor_estimado_usd = $this->estimar_valor_usd($simbolo_entrada, $cantidad);

        // Calcular comisiones realistas de Solana
        $comisiones_calculadas = $this->calcular_comisiones_solana($valor_estimado_usd);

        return array(
            'success'    => true,
            'cotizacion' => array(
                'token_entrada'    => $simbolo_entrada,
                'token_salida'     => $simbolo_salida,
                'cantidad_entrada' => $cantidad,
                'cantidad_salida'  => $cantidad_salida_legible,
                'precio'           => $precio_unitario,
                'slippage_bps'     => $slippage_en_bps,
                'impacto_precio'   => $impacto_precio,
                'ruta'             => $informacion_ruta,
                'comisiones'       => $comisiones_calculadas,
                'quote_raw'        => $respuesta_cotizacion,
            ),
        );
    }

    /**
     * Ejecuta un swap en modo paper (simulado) usando cotizaciones reales.
     *
     * @param string $simbolo_entrada      Simbolo del token de entrada.
     * @param string $simbolo_salida       Simbolo del token de salida.
     * @param float  $cantidad             Cantidad del token de entrada a intercambiar.
     * @param float  $slippage_porcentaje  Porcentaje de slippage permitido.
     * @return array Resultado del swap simulado.
     */
    public function ejecutar_swap_paper($simbolo_entrada, $simbolo_salida, $cantidad, $slippage_porcentaje = 0.5) {
        // Obtener cotizacion real de Jupiter
        $resultado_cotizacion = $this->obtener_cotizacion_real($simbolo_entrada, $simbolo_salida, $cantidad, $slippage_porcentaje);

        if (!$resultado_cotizacion['success']) {
            return array(
                'success' => false,
                'error'   => $resultado_cotizacion['error'],
            );
        }

        $cotizacion_obtenida = $resultado_cotizacion['cotizacion'];

        // Verificar que el usuario tiene saldo suficiente del token de entrada
        $saldo_disponible_entrada = $this->portfolio->obtener_saldo($simbolo_entrada);

        if ($saldo_disponible_entrada < $cantidad) {
            return array(
                'success' => false,
                'error'   => sprintf(
                    'Saldo insuficiente de %s. Disponible: %s, Requerido: %s',
                    $simbolo_entrada,
                    $saldo_disponible_entrada,
                    $cantidad
                ),
            );
        }

        // Restar el token de entrada del portfolio
        $this->portfolio->restar_saldo($simbolo_entrada, $cantidad);

        // Sumar el token de salida al portfolio
        $cantidad_recibida = $cotizacion_obtenida['cantidad_salida'];
        $this->portfolio->sumar_saldo($simbolo_salida, $cantidad_recibida);

        // Calcular y deducir comisiones simuladas del saldo USDC
        $comisiones_del_swap     = $cotizacion_obtenida['comisiones'];
        $total_comisiones_usd    = $comisiones_del_swap['total_estimado_usd'];
        $saldo_usdc_actual       = $this->portfolio->obtener_saldo('USDC');

        if ($saldo_usdc_actual >= $total_comisiones_usd) {
            $this->portfolio->restar_saldo('USDC', $total_comisiones_usd);
        }

        // Generar identificador unico para el swap
        $identificador_swap = $this->generar_id_swap();

        // Registrar la operacion en el historial
        $datos_para_historial = array(
            'swap_id'          => $identificador_swap,
            'tipo'             => 'paper',
            'token_entrada'    => $simbolo_entrada,
            'token_salida'     => $simbolo_salida,
            'cantidad_entrada' => $cantidad,
            'cantidad_salida'  => $cantidad_recibida,
            'precio'           => $cotizacion_obtenida['precio'],
            'comisiones'       => $comisiones_del_swap,
            'timestamp'        => current_time('timestamp'),
        );

        $this->historial->registrar_swap($datos_para_historial);

        // Actualizar contadores del portfolio
        $this->portfolio->incrementar_contador_swaps();

        // Obtener saldo restante del token de entrada
        $saldo_restante_entrada = $this->portfolio->obtener_saldo($simbolo_entrada);

        return array(
            'success' => true,
            'swap'    => array(
                'swap_id'          => $identificador_swap,
                'token_entrada'    => $simbolo_entrada,
                'token_salida'     => $simbolo_salida,
                'cantidad_entrada' => $cantidad,
                'cantidad_salida'  => $cantidad_recibida,
                'precio'           => $cotizacion_obtenida['precio'],
                'comisiones'       => $comisiones_del_swap,
                'balance_restante' => $saldo_restante_entrada,
            ),
        );
    }

    /**
     * Prepara un swap real obteniendo la transaccion sin firmar desde Jupiter.
     * NO modifica el portfolio; eso ocurre tras la confirmacion de la transaccion en cadena.
     *
     * @param string $simbolo_entrada      Simbolo del token de entrada.
     * @param string $simbolo_salida       Simbolo del token de salida.
     * @param float  $cantidad             Cantidad del token de entrada.
     * @param float  $slippage_porcentaje  Porcentaje de slippage permitido.
     * @param string $wallet_publica       Direccion publica de la wallet del usuario.
     * @return array Transaccion sin firmar lista para ser firmada por la wallet del usuario.
     */
    public function preparar_swap_real($simbolo_entrada, $simbolo_salida, $cantidad, $slippage_porcentaje, $wallet_publica) {
        // Obtener cotizacion real de Jupiter
        $resultado_cotizacion = $this->obtener_cotizacion_real($simbolo_entrada, $simbolo_salida, $cantidad, $slippage_porcentaje);

        if (!$resultado_cotizacion['success']) {
            return array(
                'success' => false,
                'error'   => $resultado_cotizacion['error'],
            );
        }

        $cotizacion_obtenida = $resultado_cotizacion['cotizacion'];
        $datos_quote_raw     = $cotizacion_obtenida['quote_raw'];

        // Solicitar a Jupiter la transaccion de swap sin firmar
        $resultado_transaccion = $this->jupiter_api->preparar_transaccion_swap($datos_quote_raw, $wallet_publica);

        if (!$resultado_transaccion || isset($resultado_transaccion['error'])) {
            $mensaje_error_transaccion = isset($resultado_transaccion['error']) ? $resultado_transaccion['error'] : 'Error al preparar la transaccion de swap en Jupiter';
            return array(
                'success' => false,
                'error'   => $mensaje_error_transaccion,
            );
        }

        // Extraer la transaccion serializada sin firmar
        $transaccion_sin_firmar = isset($resultado_transaccion['swapTransaction']) ? $resultado_transaccion['swapTransaction'] : null;

        if (!$transaccion_sin_firmar) {
            return array(
                'success' => false,
                'error' => __('', 'flavor-platform'),
            );
        }

        return array(
            'success'               => true,
            'transaccion_sin_firmar' => $transaccion_sin_firmar,
            'quote'                 => $cotizacion_obtenida,
            'instrucciones'         => 'Firma esta transaccion con tu wallet Phantom/Solflare',
        );
    }

    /**
     * Calcula las comisiones realistas de una operacion en Solana.
     *
     * @param float $cantidad_usd Valor aproximado de la operacion en USD.
     * @return array Desglose detallado de todas las comisiones.
     */
    public function calcular_comisiones_solana($cantidad_usd) {
        // Comision base de la red Solana (signature fee)
        $comision_red_sol = 0.000005; // 5000 lamports
        $comision_red_usd = 0.001;    // Aproximadamente $0.001

        // Comision de prioridad para transacciones rapidas
        $comision_prioridad_sol = 0.0005;
        $comision_prioridad_usd = 0.10; // Aproximadamente $0.10

        // Comision del DEX (porcentaje sobre el monto operado)
        $porcentaje_comision_dex = 0.25; // 0.25%
        $comision_dex_usd        = ($cantidad_usd * $porcentaje_comision_dex) / 100;

        // Slippage estimado (coste implicito del deslizamiento de precio)
        $porcentaje_slippage_estimado = 0.10; // 0.1%
        $slippage_estimado_usd        = ($cantidad_usd * $porcentaje_slippage_estimado) / 100;

        // Calcular el total estimado en USD
        $total_estimado_usd = $comision_red_usd + $comision_prioridad_usd + $comision_dex_usd + $slippage_estimado_usd;

        return array(
            'comision_red' => array(
                'sol' => $comision_red_sol,
                'usd' => $comision_red_usd,
                'descripcion' => 'Tarifa base de la red Solana (signature fee)',
            ),
            'comision_prioridad' => array(
                'sol' => $comision_prioridad_sol,
                'usd' => $comision_prioridad_usd,
                'descripcion' => 'Tarifa de prioridad para ejecucion rapida',
            ),
            'comision_dex' => array(
                'porcentaje' => $porcentaje_comision_dex,
                'usd'        => $comision_dex_usd,
                'descripcion' => sprintf('Comision DEX del %.2f%% sobre el monto operado', $porcentaje_comision_dex),
            ),
            'slippage_estimado' => array(
                'porcentaje' => $porcentaje_slippage_estimado,
                'usd'        => $slippage_estimado_usd,
                'descripcion' => sprintf('Slippage estimado del %.2f%% por deslizamiento de precio', $porcentaje_slippage_estimado),
            ),
            'total_estimado_usd' => $total_estimado_usd,
        );
    }

    /**
     * Estima el valor en USD de una cantidad de un token dado.
     * Se usa internamente para calcular comisiones proporcionales.
     *
     * @param string $simbolo_token Simbolo del token.
     * @param float  $cantidad      Cantidad del token.
     * @return float Valor estimado en USD.
     */
    private function estimar_valor_usd($simbolo_token, $cantidad) {
        $simbolo_normalizado = strtoupper($simbolo_token);

        // Para stablecoins, el valor es directamente la cantidad
        $stablecoins_conocidas = array('USDC', 'USDT', 'DAI', 'BUSD', 'UST');
        if (in_array($simbolo_normalizado, $stablecoins_conocidas)) {
            return floatval($cantidad);
        }

        // Intentar obtener precio desde el registro de tokens
        $precio_token = $this->token_registry->obtener_precio_usd($simbolo_token);

        if ($precio_token && $precio_token > 0) {
            return $cantidad * $precio_token;
        }

        // Fallback: devolver la cantidad como estimacion por defecto
        return floatval($cantidad);
    }

    /**
     * Genera un identificador unico para un swap.
     *
     * @return string ID unico del swap con prefijo 'swap_paper_'.
     */
    private function generar_id_swap() {
        return 'swap_paper_' . bin2hex(random_bytes(8)) . '_' . time();
    }
}

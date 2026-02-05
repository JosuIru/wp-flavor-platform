<?php
/**
 * Cerebro IA - Toma decisiones de trading usando el motor IA del plugin
 *
 * @package FlavorChatIA
 */

if (!defined('ABSPATH')) {
    exit;
}

class Flavor_Trading_IA_Cerebro {

    /**
     * ID del usuario
     */
    private $usuario_id;

    /**
     * Referencia al modulo principal
     */
    private $modulo;

    /**
     * Constructor
     *
     * @param int    $usuario_id ID del usuario
     * @param object $modulo Modulo principal para acceder a settings
     */
    public function __construct($usuario_id, $modulo) {
        $this->usuario_id = $usuario_id;
        $this->modulo     = $modulo;
    }

    /**
     * Analiza el mercado y decide que hacer
     *
     * @param array $datos_mercado Datos de mercado formateados
     * @param array $estado_portfolio Estado del portfolio
     * @param array $parametros_usuario Parametros de trading
     * @return array Decision de la IA
     */
    public function analizar_y_decidir($datos_mercado, $estado_portfolio, $parametros_usuario) {
        $gestor_motores = Flavor_Engine_Manager::get_instance();

        $prompt_sistema  = $this->construir_prompt_sistema($parametros_usuario);
        $prompt_analisis = $this->construir_prompt_analisis($datos_mercado, $estado_portfolio, $parametros_usuario);

        $mensajes = array(
            array('role' => 'user', 'content' => $prompt_analisis),
        );

        $inicio_tiempo = microtime(true);

        $respuesta = $gestor_motores->send_message($mensajes, $prompt_sistema, array(), 'default');

        $tiempo_respuesta_ms = intval((microtime(true) - $inicio_tiempo) * 1000);

        if (empty($respuesta['success'])) {
            $decision_error = $this->crear_decision_espera(
                isset($respuesta['error']) ? $respuesta['error'] : __('Error al comunicar con la IA', 'flavor-chat-ia')
            );
            $this->registrar_decision($decision_error);
            return $decision_error;
        }

        $contenido_respuesta = isset($respuesta['content']) ? $respuesta['content'] : '';
        $decision = $this->parsear_respuesta($contenido_respuesta);
        $decision['tiempo_respuesta_ms'] = $tiempo_respuesta_ms;
        $decision['proveedor_usado']     = isset($respuesta['provider']) ? $respuesta['provider'] : '';

        $this->registrar_decision($decision);

        return $decision;
    }

    /**
     * Construye el prompt del sistema con reglas y parametros
     *
     * @param array $parametros Parametros del usuario
     * @return string Prompt del sistema
     */
    public function construir_prompt_sistema($parametros) {
        $agresividad       = isset($parametros['agresividad']) ? $parametros['agresividad'] : 5;
        $riesgo_maximo     = isset($parametros['riesgo_maximo_porcentaje']) ? $parametros['riesgo_maximo_porcentaje'] : 5;
        $stop_loss         = isset($parametros['stop_loss_porcentaje']) ? $parametros['stop_loss_porcentaje'] : 3;
        $take_profit       = isset($parametros['take_profit_porcentaje']) ? $parametros['take_profit_porcentaje'] : 5;
        $auto_ajuste       = !empty($parametros['auto_ajuste_enabled']);
        $tokens_monitoreados = isset($parametros['tokens_monitoreados']) ? $parametros['tokens_monitoreados'] : array('SOL', 'BONK', 'JUP', 'WIF', 'JTO');

        $seccion_auto_ajuste = '';
        if ($auto_ajuste) {
            $intervalo = isset($parametros['intervalo_analisis']) ? $parametros['intervalo_analisis'] : 60;
            $confianza = isset($parametros['confianza_minima_trade']) ? $parametros['confianza_minima_trade'] : 60;

            $seccion_auto_ajuste = "

AUTO-AJUSTE DE PARAMETROS (ACTIVADO):
Puedes sugerir ajustes a tus propios parametros de trading.

Parametros que puedes ajustar:
- intervalo_analisis: {$intervalo}s actual (rango: 30-300s)
- agresividad: {$agresividad} actual (rango: 1-10)
- confianza_minima: {$confianza} actual (rango: 40-80)
- riesgo_maximo: {$riesgo_maximo}% actual (rango: 1-15%)
- stop_loss: {$stop_loss}% actual (rango: 1-10%)
- take_profit: {$take_profit}% actual (rango: 2-20%)

Criterios para ajustar:
- Mercado muy volatil -> reducir agresividad, subir stop_loss, aumentar intervalo
- Mercado estable con tendencia clara -> subir agresividad, bajar confianza_minima
- Racha de perdidas -> reducir riesgo_maximo, bajar agresividad
- Racha de ganancias -> puedes ser mas agresivo gradualmente

Incluye el campo \"ajustes_parametros\" en tu respuesta JSON si deseas sugerir cambios.
Solo sugiere ajustes cuando haya una razon clara.";
        }

        $tokens_lista = implode(', ', $tokens_monitoreados);

        return "Eres un trader algoritmico experto en criptomonedas, especializado en Solana.

Tu objetivo es maximizar ganancias mientras respetas estrictamente los parametros de riesgo del usuario.

PARAMETROS DE TRADING:
- Agresividad: {$agresividad}/10
  (1=muy conservador, solo trades muy seguros | 10=muy agresivo, aprovecha cualquier oportunidad)
- Riesgo maximo por operacion: {$riesgo_maximo}% del portfolio
- Stop Loss automatico: {$stop_loss}%
- Take Profit objetivo: {$take_profit}%

REGLAS ESTRICTAS:
1. NUNCA arriesgues mas del porcentaje de riesgo maximo configurado
2. SIEMPRE define stop loss y take profit para cada operacion
3. Si no hay oportunidad clara, ESPERA - no fuerces trades
4. Considera la liquidez del token antes de operar
5. Ten en cuenta las comisiones de Solana (~0.00025 SOL por tx)
{$seccion_auto_ajuste}
SUGERENCIAS DE MEJORA:
Si detectas patrones o formas de mejorar la estrategia,
incluye sugerencias concretas en \"sugerencias_mejora\".

REGLAS DINAMICAS (AUTO-APLICABLES):
Puedes crear reglas que se evaluan automaticamente en cada ciclo.

Indicadores disponibles: \"rsi\", \"cambio_24h\", \"precio\", \"volumen_24h\", \"tendencia\", \"fuerza\"
Operadores: \">\", \"<\", \">=\", \"<=\", \"==\"
Acciones disponibles:
- \"bloquear_compra\": Impide comprar el token
- \"bloquear_venta\": Impide vender el token
- \"reducir_posicion\": Reduce posicion (parametro: {\"porcentaje\": 50})
- \"forzar_venta\": Vende toda la posicion del token
- \"ajustar_parametro\": Cambia un parametro (parametro: {\"nombre\": \"agresividad\", \"valor\": 3})
- \"alerta\": Solo genera alerta (parametro: {\"mensaje\": \"texto\"})

Tokens monitoreados: {$tokens_lista}

GESTION DE WATCHLIST:
Puedes sugerir agregar o eliminar tokens del watchlist.

RESPONDE SIEMPRE en formato JSON valido con esta estructura:
{
    \"accion\": \"COMPRAR\" | \"VENDER\" | \"ESPERAR\",
    \"token\": \"simbolo del token\",
    \"cantidad_porcentaje\": numero entre 0-100,
    \"stop_loss_porcentaje\": numero,
    \"take_profit_porcentaje\": numero,
    \"confianza\": numero 0-100,
    \"razonamiento\": \"explicacion breve\",
    \"ajustes_parametros\": {
        \"intervalo_analisis\": numero_o_null,
        \"agresividad\": numero_o_null,
        \"confianza_minima\": numero_o_null,
        \"riesgo_maximo\": numero_o_null,
        \"stop_loss\": numero_o_null,
        \"take_profit\": numero_o_null,
        \"razon_ajuste\": \"por que ajustar\"
    },
    \"sugerencias_mejora\": [\"sugerencia 1\"],
    \"reglas_nuevas\": [
        {
            \"nombre\": \"Nombre descriptivo\",
            \"token\": \"SOL\",
            \"indicador\": \"rsi\",
            \"operador\": \">\",
            \"valor\": 80,
            \"accion_tipo\": \"bloquear_compra\",
            \"accion_parametros\": {},
            \"razon\": \"RSI sobrecomprado\"
        }
    ],
    \"cambios_watchlist\": [
        {
            \"accion\": \"agregar\",
            \"token\": \"RNDR\",
            \"razon\": \"Token con momentum alcista\"
        }
    ]
}

Los campos opcionales solo se incluyen cuando hay razones claras.";
    }

    /**
     * Construye el prompt de analisis con datos actuales
     *
     * @param array $mercado Datos de mercado
     * @param array $portfolio Estado del portfolio
     * @param array $parametros Parametros del usuario
     * @return string Prompt de analisis
     */
    public function construir_prompt_analisis($mercado, $portfolio, $parametros) {
        $fecha_hora       = current_time('Y-m-d H:i:s');
        $mercado_json     = wp_json_encode($mercado, JSON_PRETTY_PRINT);
        $posiciones_json  = wp_json_encode(isset($portfolio['posiciones']) ? $portfolio['posiciones'] : array(), JSON_PRETTY_PRINT);
        $feedback_trades  = $this->construir_feedback_trades();
        $metricas         = $this->calcular_metricas_rendimiento();
        $historial        = $this->formatear_ultimas_decisiones(8);

        return "ANALISIS DE MERCADO - {$fecha_hora}

ESTADO DEL PORTFOLIO:
- Balance total: \$" . number_format($portfolio['balance_total_usd'] ?? 0, 2) . "
- Disponible para trading: \$" . number_format($portfolio['disponible_usd'] ?? 0, 2) . "
- Posiciones abiertas: {$posiciones_json}

DATOS DE MERCADO:
{$mercado_json}

FEEDBACK DE TRADES:
{$feedback_trades}

METRICAS DE RENDIMIENTO:
{$metricas}

HISTORIAL RECIENTE:
{$historial}

Basandote en esta informacion y los parametros configurados, que accion recomiendas?
Responde SOLO con el JSON de decision.";
    }

    /**
     * Parsea la respuesta de la IA
     *
     * @param string $texto_respuesta Respuesta de la IA
     * @return array Decision parseada
     */
    public function parsear_respuesta($texto_respuesta) {
        $texto_limpio = trim($texto_respuesta);

        // Limpiar bloques de codigo markdown
        if (strpos($texto_limpio, '```') !== false) {
            $partes = explode('```', $texto_limpio);
            if (isset($partes[1])) {
                $texto_limpio = $partes[1];
                if (strpos($texto_limpio, 'json') === 0) {
                    $texto_limpio = substr($texto_limpio, 4);
                }
            }
        }
        $texto_limpio = trim($texto_limpio);

        $datos = json_decode($texto_limpio, true);

        if (!is_array($datos)) {
            return $this->crear_decision_espera(
                sprintf('Error parseando respuesta: %s...', substr($texto_respuesta, 0, 100))
            );
        }

        $decision = array(
            'accion'              => isset($datos['accion']) ? strtoupper($datos['accion']) : 'ESPERAR',
            'token'               => isset($datos['token']) ? $datos['token'] : '',
            'cantidad_porcentaje' => floatval($datos['cantidad_porcentaje'] ?? 0),
            'stop_loss'           => floatval($datos['stop_loss_porcentaje'] ?? $this->modulo->get_setting('stop_loss_porcentaje', 3)),
            'take_profit'         => floatval($datos['take_profit_porcentaje'] ?? $this->modulo->get_setting('take_profit_porcentaje', 5)),
            'confianza'           => floatval($datos['confianza'] ?? 0),
            'razonamiento'        => isset($datos['razonamiento']) ? $datos['razonamiento'] : 'Sin razonamiento',
            'ajustes_sugeridos'   => isset($datos['ajustes_parametros']) ? $datos['ajustes_parametros'] : null,
            'sugerencias_mejora'  => isset($datos['sugerencias_mejora']) && is_array($datos['sugerencias_mejora'])
                ? $datos['sugerencias_mejora']
                : array(),
            'reglas_sugeridas'    => isset($datos['reglas_nuevas']) && is_array($datos['reglas_nuevas'])
                ? $datos['reglas_nuevas']
                : array(),
            'cambios_watchlist'   => isset($datos['cambios_watchlist']) && is_array($datos['cambios_watchlist'])
                ? $datos['cambios_watchlist']
                : array(),
            'tiempo_respuesta_ms' => 0,
            'proveedor_usado'     => '',
        );

        // Validar accion
        if (!in_array($decision['accion'], array('COMPRAR', 'VENDER', 'ESPERAR'), true)) {
            $decision['accion'] = 'ESPERAR';
        }

        return $decision;
    }

    /**
     * Crea una decision de espera por defecto
     *
     * @param string $razon Razon de la espera
     * @return array Decision
     */
    private function crear_decision_espera($razon) {
        return array(
            'accion'              => 'ESPERAR',
            'token'               => '',
            'cantidad_porcentaje' => 0,
            'stop_loss'           => 0,
            'take_profit'         => 0,
            'confianza'           => 0,
            'razonamiento'        => $razon,
            'ajustes_sugeridos'   => null,
            'sugerencias_mejora'  => array(),
            'reglas_sugeridas'    => array(),
            'cambios_watchlist'   => array(),
            'tiempo_respuesta_ms' => 0,
            'proveedor_usado'     => '',
        );
    }

    /**
     * Registra una decision en la base de datos
     *
     * @param array $decision Decision tomada
     */
    private function registrar_decision($decision) {
        global $wpdb;
        $tabla = $wpdb->prefix . 'flavor_trading_ia_decisiones';

        $wpdb->insert($tabla, array(
            'usuario_id'         => $this->usuario_id,
            'timestamp'          => current_time('mysql'),
            'accion'             => $decision['accion'],
            'token'              => $decision['token'],
            'cantidad_porcentaje' => $decision['cantidad_porcentaje'],
            'confianza'          => $decision['confianza'],
            'razonamiento'       => $decision['razonamiento'],
            'proveedor_usado'    => $decision['proveedor_usado'],
            'tiempo_respuesta_ms' => $decision['tiempo_respuesta_ms'],
        ));
    }

    /**
     * Construye feedback de trades para la IA
     *
     * @return string Feedback formateado
     */
    private function construir_feedback_trades() {
        global $wpdb;
        $tabla = $wpdb->prefix . 'flavor_trading_ia_trades';

        $ventas_recientes = $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM $tabla WHERE usuario_id = %d AND tipo = 'VENTA' ORDER BY timestamp DESC LIMIT 5",
            $this->usuario_id
        ), ARRAY_A);

        $lineas = array();

        if (!empty($ventas_recientes)) {
            $lineas[] = 'Ultimas ventas:';
            foreach ($ventas_recientes as $venta) {
                $lineas[] = sprintf(
                    '  %s: PnL %+.1f%%, fees $%.4f',
                    $venta['token_vendido'],
                    floatval($venta['pnl']),
                    floatval($venta['fees_total_usd'])
                );
            }
        }

        return !empty($lineas) ? implode("\n", $lineas) : 'Sin trades ejecutados aun';
    }

    /**
     * Calcula metricas de rendimiento
     *
     * @return string Metricas formateadas
     */
    private function calcular_metricas_rendimiento() {
        global $wpdb;
        $tabla = $wpdb->prefix . 'flavor_trading_ia_trades';

        $ventas = $wpdb->get_results($wpdb->prepare(
            "SELECT pnl FROM $tabla WHERE usuario_id = %d AND tipo = 'VENTA'",
            $this->usuario_id
        ), ARRAY_A);

        if (empty($ventas)) {
            return 'Sin ventas completadas para calcular metricas';
        }

        $total_ventas       = count($ventas);
        $ventas_ganadoras   = 0;
        $suma_ganancias     = 0;
        $suma_perdidas      = 0;
        $contador_perdidas  = 0;

        foreach ($ventas as $venta) {
            $pnl = floatval($venta['pnl']);
            if ($pnl > 0) {
                $ventas_ganadoras++;
                $suma_ganancias += $pnl;
            } else {
                $contador_perdidas++;
                $suma_perdidas += $pnl;
            }
        }

        $tasa_acierto      = ($total_ventas > 0) ? ($ventas_ganadoras / $total_ventas * 100) : 0;
        $ganancia_promedio  = ($ventas_ganadoras > 0) ? ($suma_ganancias / $ventas_ganadoras) : 0;
        $perdida_promedio   = ($contador_perdidas > 0) ? ($suma_perdidas / $contador_perdidas) : 0;

        return sprintf(
            "Win rate: %.0f%% (%d/%d)\nGanancia promedio: %+.2f%% | Perdida promedio: %+.2f%%\nTotal operaciones: %d",
            $tasa_acierto,
            $ventas_ganadoras,
            $total_ventas,
            $ganancia_promedio,
            $perdida_promedio,
            $total_ventas
        );
    }

    /**
     * Formatea las ultimas decisiones
     *
     * @param int $cantidad Numero de decisiones
     * @return string Decisiones formateadas
     */
    private function formatear_ultimas_decisiones($cantidad) {
        global $wpdb;
        $tabla = $wpdb->prefix . 'flavor_trading_ia_decisiones';

        $decisiones = $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM $tabla WHERE usuario_id = %d ORDER BY timestamp DESC LIMIT %d",
            $this->usuario_id,
            $cantidad
        ), ARRAY_A);

        if (empty($decisiones)) {
            return 'Sin historial de decisiones';
        }

        $lineas = array();
        foreach ($decisiones as $decision) {
            $hora = date('H:i', strtotime($decision['timestamp']));
            $lineas[] = sprintf(
                '- %s | %s | %s | Conf: %s%% | %s',
                $hora,
                $decision['accion'],
                $decision['token'] ?: 'N/A',
                $decision['confianza'],
                substr($decision['razonamiento'], 0, 120)
            );
        }

        return implode("\n", $lineas);
    }
}

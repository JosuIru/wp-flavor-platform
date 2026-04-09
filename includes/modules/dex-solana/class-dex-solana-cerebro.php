<?php
/**
 * Cerebro IA DEX Solana - Recomendaciones de swap y liquidez usando IA
 *
 * Proporciona analisis inteligente para operaciones de swap y gestion
 * de liquidez en pools AMM de Solana, usando el motor IA del plugin.
 *
 * @package FlavorChatIA
 */

if (!defined('ABSPATH')) {
    exit;
}

class Flavor_Dex_Solana_Cerebro {

    /**
     * ID del usuario
     *
     * @var int
     */
    private $usuario_id;

    /**
     * Constructor
     *
     * @param int $usuario_id ID del usuario
     */
    public function __construct($usuario_id) {
        $this->usuario_id = $usuario_id;
    }

    /**
     * Analiza una oportunidad de swap y devuelve recomendacion IA
     *
     * @param array $datos_cotizacion Datos de la cotizacion del swap (tokens, cantidades, slippage, ruta)
     * @param array $datos_portfolio  Estado actual del portfolio del usuario
     * @param array $datos_mercado    Datos opcionales del mercado (precios, volumen, tendencias)
     * @return array Recomendacion estructurada con campos: recomendacion, confianza, razonamiento, sugerencias
     */
    public function analizar_swap($datos_cotizacion, $datos_portfolio, $datos_mercado = array()) {
        $gestor_motores    = Flavor_Engine_Manager::get_instance();
        $prompt_sistema    = $this->construir_prompt_sistema();
        $mensaje_analisis  = $this->construir_mensaje_swap($datos_cotizacion, $datos_portfolio, $datos_mercado);

        $mensajes = array(
            array('role' => 'user', 'content' => $mensaje_analisis),
        );

        $inicio_tiempo = microtime(true);

        $respuesta_ia = $gestor_motores->send_message($mensajes, $prompt_sistema, array(), 'default');

        $tiempo_respuesta_ms = intval((microtime(true) - $inicio_tiempo) * 1000);

        if (empty($respuesta_ia['success'])) {
            $recomendacion_neutral = $this->crear_recomendacion_neutral(
                isset($respuesta_ia['error']) ? $respuesta_ia['error'] : __('Error al comunicar con la IA', 'flavor-platform')
            );

            $this->registrar_decision(
                'analizar_swap',
                wp_json_encode($datos_cotizacion),
                $recomendacion_neutral['confianza'],
                $recomendacion_neutral['razonamiento'],
                $tiempo_respuesta_ms
            );

            return $recomendacion_neutral;
        }

        $contenido_respuesta = isset($respuesta_ia['content']) ? $respuesta_ia['content'] : '';
        $recomendacion_parseada = $this->parsear_respuesta($contenido_respuesta);

        // Validar que la recomendacion sea un valor esperado para swap
        $recomendaciones_validas_swap = array('ejecutar', 'no_ejecutar', 'ajustar');
        if (!in_array($recomendacion_parseada['recomendacion'], $recomendaciones_validas_swap, true)) {
            $recomendacion_parseada['recomendacion'] = 'no_ejecutar';
        }

        $this->registrar_decision(
            'analizar_swap',
            wp_json_encode(array(
                'cotizacion'    => $datos_cotizacion,
                'recomendacion' => $recomendacion_parseada['recomendacion'],
            )),
            $recomendacion_parseada['confianza'],
            $recomendacion_parseada['razonamiento'],
            $tiempo_respuesta_ms
        );

        return $recomendacion_parseada;
    }

    /**
     * Analiza una oportunidad de provision de liquidez y devuelve recomendacion IA
     *
     * @param array $pool_datos      Datos del pool AMM (tokens, reservas, APY, volumen)
     * @param array $datos_portfolio Estado actual del portfolio del usuario
     * @return array Recomendacion estructurada con campos: recomendacion, porcentaje_sugerido, confianza, razonamiento
     */
    public function analizar_liquidez($pool_datos, $datos_portfolio) {
        $gestor_motores    = Flavor_Engine_Manager::get_instance();
        $prompt_sistema    = $this->construir_prompt_sistema();
        $mensaje_analisis  = $this->construir_mensaje_liquidez($pool_datos, $datos_portfolio);

        $mensajes = array(
            array('role' => 'user', 'content' => $mensaje_analisis),
        );

        $inicio_tiempo = microtime(true);

        $respuesta_ia = $gestor_motores->send_message($mensajes, $prompt_sistema, array(), 'default');

        $tiempo_respuesta_ms = intval((microtime(true) - $inicio_tiempo) * 1000);

        if (empty($respuesta_ia['success'])) {
            $recomendacion_neutral = array(
                'recomendacion'      => 'no_agregar',
                'porcentaje_sugerido' => 0,
                'confianza'          => 0,
                'razonamiento'       => isset($respuesta_ia['error'])
                    ? $respuesta_ia['error']
                    : __('IA no disponible. Se recomienda no actuar hasta tener analisis.', 'flavor-platform'),
            );

            $this->registrar_decision(
                'analizar_liquidez',
                wp_json_encode($pool_datos),
                $recomendacion_neutral['confianza'],
                $recomendacion_neutral['razonamiento'],
                $tiempo_respuesta_ms
            );

            return $recomendacion_neutral;
        }

        $contenido_respuesta = isset($respuesta_ia['content']) ? $respuesta_ia['content'] : '';
        $recomendacion_parseada = $this->parsear_respuesta($contenido_respuesta);

        // Validar que la recomendacion sea un valor esperado para liquidez
        $recomendaciones_validas_liquidez = array('agregar', 'no_agregar', 'retirar');
        $recomendacion_liquidez = isset($recomendacion_parseada['recomendacion'])
            ? $recomendacion_parseada['recomendacion']
            : 'no_agregar';

        if (!in_array($recomendacion_liquidez, $recomendaciones_validas_liquidez, true)) {
            $recomendacion_liquidez = 'no_agregar';
        }

        $resultado_liquidez = array(
            'recomendacion'       => $recomendacion_liquidez,
            'porcentaje_sugerido' => isset($recomendacion_parseada['porcentaje_sugerido'])
                ? floatval($recomendacion_parseada['porcentaje_sugerido'])
                : 0,
            'confianza'           => $recomendacion_parseada['confianza'],
            'razonamiento'        => $recomendacion_parseada['razonamiento'],
        );

        $this->registrar_decision(
            'analizar_liquidez',
            wp_json_encode(array(
                'pool'          => $pool_datos,
                'recomendacion' => $resultado_liquidez['recomendacion'],
            )),
            $resultado_liquidez['confianza'],
            $resultado_liquidez['razonamiento'],
            $tiempo_respuesta_ms
        );

        return $resultado_liquidez;
    }

    /**
     * Construye el prompt del sistema para analisis DEX
     *
     * Define el comportamiento de la IA como analista DeFi especializado en Solana,
     * con instrucciones para evaluar swaps, liquidez y responder en formato JSON.
     *
     * @return string Prompt del sistema
     */
    public function construir_prompt_sistema() {
        return "Eres un analista DeFi experto especializado en el ecosistema Solana.

Tu objetivo es proporcionar recomendaciones objetivas y fundamentadas sobre operaciones en DEX (intercambios descentralizados).

CAPACIDADES DE ANALISIS:

1. EVALUACION DE SWAPS:
   - Analiza el slippage esperado vs configurado
   - Evalua el impacto en precio (price impact) de la operacion
   - Considera las comisiones de red y plataforma
   - Verifica que la ruta de intercambio sea optima
   - Alerta si el token tiene baja liquidez o alto riesgo

2. EVALUACION DE LIQUIDEZ:
   - Calcula el riesgo de impermanent loss basado en la volatilidad de los tokens
   - Evalua si el APY compensa el riesgo de impermanent loss
   - Considera la proporcion optima de tokens a depositar
   - Analiza el volumen del pool y la sostenibilidad del rendimiento
   - Recomienda porcentaje del portfolio a destinar

3. CONSIDERACIONES DE PORTFOLIO:
   - Evalua la diversificacion actual del usuario
   - Verifica que la operacion no concentre demasiado en un solo token
   - Considera el balance general y la exposicion al riesgo
   - Sugiere ajustes para mantener un portfolio equilibrado

4. ALERTAS DE RIESGO:
   - Operaciones con slippage superior al 2% son de alto riesgo
   - Price impact superior al 1% indica baja liquidez
   - Pools con APY extremadamente alto (>500%) pueden ser insostenibles
   - Tokens no verificados requieren precaucion adicional

FORMATO DE RESPUESTA:
Responde SIEMPRE en formato JSON valido con esta estructura:
{
    \"recomendacion\": \"ejecutar\" | \"no_ejecutar\" | \"ajustar\" | \"agregar\" | \"no_agregar\" | \"retirar\",
    \"confianza\": numero entre 0 y 100,
    \"razonamiento\": \"explicacion detallada de la recomendacion\",
    \"sugerencias\": [\"sugerencia 1\", \"sugerencia 2\"],
    \"porcentaje_sugerido\": numero entre 0 y 100 (solo para liquidez)
}

REGLAS ESTRICTAS:
- NUNCA recomiendes ejecutar un swap con price impact superior al 5%
- SIEMPRE advierte sobre tokens no verificados
- Si no hay datos suficientes para decidir, recomienda NO ejecutar con confianza baja
- Prioriza la preservacion del capital del usuario
- Ten en cuenta las comisiones de Solana (~0.00025 SOL por transaccion)";
    }

    /**
     * Parsea la respuesta de texto de la IA y extrae datos estructurados
     *
     * Intenta extraer JSON de la respuesta, manejando bloques de codigo markdown.
     * Si el parseo falla, devuelve una recomendacion neutral por defecto.
     *
     * @param string $respuesta_texto Texto de respuesta de la IA
     * @return array Datos estructurados de la recomendacion
     */
    public function parsear_respuesta($respuesta_texto) {
        $texto_limpio = trim($respuesta_texto);

        // Limpiar bloques de codigo markdown (```json ... ``` o ``` ... ```)
        if (strpos($texto_limpio, '```') !== false) {
            $fragmentos = explode('```', $texto_limpio);
            if (isset($fragmentos[1])) {
                $texto_limpio = $fragmentos[1];
                // Eliminar indicador de lenguaje si existe
                if (strpos($texto_limpio, 'json') === 0) {
                    $texto_limpio = substr($texto_limpio, 4);
                }
            }
        }

        $texto_limpio = trim($texto_limpio);
        $datos_decodificados = json_decode($texto_limpio, true);

        if (!is_array($datos_decodificados)) {
            return $this->crear_recomendacion_neutral(
                sprintf(
                    'Error al parsear respuesta de IA: %s...',
                    substr($respuesta_texto, 0, 150)
                )
            );
        }

        $recomendacion_resultado = array(
            'recomendacion' => isset($datos_decodificados['recomendacion'])
                ? strtolower(sanitize_text_field($datos_decodificados['recomendacion']))
                : 'no_ejecutar',
            'confianza' => isset($datos_decodificados['confianza'])
                ? floatval($datos_decodificados['confianza'])
                : 0,
            'razonamiento' => isset($datos_decodificados['razonamiento'])
                ? sanitize_textarea_field($datos_decodificados['razonamiento'])
                : __('Sin razonamiento proporcionado', 'flavor-platform'),
            'sugerencias' => isset($datos_decodificados['sugerencias']) && is_array($datos_decodificados['sugerencias'])
                ? array_map('sanitize_text_field', $datos_decodificados['sugerencias'])
                : array(),
        );

        // Incluir porcentaje sugerido si existe (para analisis de liquidez)
        if (isset($datos_decodificados['porcentaje_sugerido'])) {
            $porcentaje_crudo = floatval($datos_decodificados['porcentaje_sugerido']);
            $recomendacion_resultado['porcentaje_sugerido'] = max(0, min(100, $porcentaje_crudo));
        }

        // Limitar confianza al rango valido
        $recomendacion_resultado['confianza'] = max(0, min(100, $recomendacion_resultado['confianza']));

        return $recomendacion_resultado;
    }

    /**
     * Registra una decision de la IA en la base de datos
     *
     * @param string $accion          Tipo de accion analizada (analizar_swap, analizar_liquidez)
     * @param string $detalles        Detalles de la operacion en formato JSON
     * @param float  $confianza       Nivel de confianza de la recomendacion (0-100)
     * @param string $razonamiento    Explicacion de la recomendacion
     * @param int    $tiempo_ms       Tiempo de respuesta en milisegundos
     */
    private function registrar_decision($accion, $detalles, $confianza, $razonamiento, $tiempo_ms) {
        global $wpdb;
        $tabla_decisiones = $wpdb->prefix . 'flavor_dex_decisiones_ia';

        $wpdb->insert(
            $tabla_decisiones,
            array(
                'usuario_id'         => $this->usuario_id,
                'timestamp'          => current_time('mysql'),
                'accion'             => $accion,
                'detalles_json'      => $detalles,
                'confianza'          => $confianza,
                'razonamiento'       => $razonamiento,
                'proveedor_usado'    => $this->obtener_proveedor_activo(),
                'tiempo_respuesta_ms' => $tiempo_ms,
            ),
            array('%d', '%s', '%s', '%s', '%f', '%s', '%s', '%d')
        );
    }

    /**
     * Construye el mensaje de analisis de swap para enviar a la IA
     *
     * @param array $datos_cotizacion Datos de la cotizacion
     * @param array $datos_portfolio  Estado del portfolio
     * @param array $datos_mercado    Datos de mercado opcionales
     * @return string Mensaje formateado para la IA
     */
    private function construir_mensaje_swap($datos_cotizacion, $datos_portfolio, $datos_mercado) {
        $fecha_hora         = current_time('Y-m-d H:i:s');
        $cotizacion_json    = wp_json_encode($datos_cotizacion, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
        $portfolio_json     = wp_json_encode($datos_portfolio, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);

        $mensaje = "ANALISIS DE SWAP - {$fecha_hora}

DATOS DE LA COTIZACION:
{$cotizacion_json}

ESTADO DEL PORTFOLIO DEL USUARIO:
{$portfolio_json}";

        if (!empty($datos_mercado)) {
            $mercado_json = wp_json_encode($datos_mercado, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
            $mensaje .= "

DATOS DE MERCADO:
{$mercado_json}";
        }

        $mensaje .= "

Basandote en los datos proporcionados, analiza esta operacion de swap.
Evalua el slippage, price impact, comisiones, y el estado del portfolio.
Responde SOLO con el JSON de recomendacion.";

        return $mensaje;
    }

    /**
     * Construye el mensaje de analisis de liquidez para enviar a la IA
     *
     * @param array $pool_datos      Datos del pool AMM
     * @param array $datos_portfolio Estado del portfolio
     * @return string Mensaje formateado para la IA
     */
    private function construir_mensaje_liquidez($pool_datos, $datos_portfolio) {
        $fecha_hora      = current_time('Y-m-d H:i:s');
        $pool_json       = wp_json_encode($pool_datos, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
        $portfolio_json  = wp_json_encode($datos_portfolio, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);

        return "ANALISIS DE LIQUIDEZ - {$fecha_hora}

DATOS DEL POOL:
{$pool_json}

ESTADO DEL PORTFOLIO DEL USUARIO:
{$portfolio_json}

Basandote en los datos proporcionados, analiza esta oportunidad de provision de liquidez.
Evalua el riesgo de impermanent loss, el APY, la sostenibilidad del rendimiento y la diversificacion del portfolio.
Indica que porcentaje del portfolio seria adecuado destinar a esta posicion.
Responde SOLO con el JSON de recomendacion, incluyendo el campo \"porcentaje_sugerido\".";
    }

    /**
     * Crea una recomendacion neutral para cuando la IA no esta disponible
     *
     * @param string $razon_error Descripcion del error o motivo
     * @return array Recomendacion neutral estructurada
     */
    private function crear_recomendacion_neutral($razon_error) {
        return array(
            'recomendacion' => 'no_ejecutar',
            'confianza'     => 0,
            'razonamiento'  => $razon_error,
            'sugerencias'   => array(
                __('Revisa la conexion con el proveedor de IA antes de operar.', 'flavor-platform'),
                __('Verifica manualmente los parametros de la operacion.', 'flavor-platform'),
            ),
        );
    }

    /**
     * Obtiene el nombre del proveedor de IA activo
     *
     * @return string Nombre del proveedor activo o cadena vacia
     */
    private function obtener_proveedor_activo() {
        $gestor_motores = Flavor_Engine_Manager::get_instance();
        $motor_activo   = $gestor_motores->get_active_engine('default');

        if ($motor_activo && method_exists($motor_activo, 'get_id')) {
            return $motor_activo->get_id();
        }

        return '';
    }
}

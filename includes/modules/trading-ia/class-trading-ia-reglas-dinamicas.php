<?php
/**
 * Motor de Reglas Dinamicas de Trading
 *
 * @package FlavorChatIA
 */

if (!defined('ABSPATH')) {
    exit;
}

class Flavor_Trading_IA_Reglas_Dinamicas {

    const MAX_REGLAS = 30;

    /**
     * ID del usuario
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
     * Agrega una nueva regla de trading
     *
     * @param string $nombre Nombre descriptivo
     * @param string $token_condicion Token o * para todos
     * @param string $indicador Indicador a evaluar
     * @param string $operador Operador de comparacion
     * @param float  $valor Valor de referencia
     * @param string $accion_tipo Tipo de accion
     * @param array  $accion_parametros Parametros de la accion
     * @param string $razon Razon de la regla
     * @param string $creada_por Quien creo la regla (ia/usuario)
     * @return array Resultado
     */
    public function agregar_regla($nombre, $token_condicion, $indicador, $operador, $valor, $accion_tipo, $accion_parametros = array(), $razon = '', $creada_por = 'ia') {
        global $wpdb;
        $tabla = $wpdb->prefix . 'flavor_trading_ia_reglas';

        // Verificar limite de reglas
        $total_reglas = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM $tabla WHERE usuario_id = %d",
            $this->usuario_id
        ));

        if ($total_reglas >= self::MAX_REGLAS) {
            // Eliminar la regla menos activada
            $wpdb->query($wpdb->prepare(
                "DELETE FROM $tabla WHERE usuario_id = %d ORDER BY activa ASC, veces_activada ASC LIMIT 1",
                $this->usuario_id
            ));
        }

        $regla_id = 'regla_' . uniqid() . '_' . date('His');

        $wpdb->insert($tabla, array(
            'regla_id'             => $regla_id,
            'usuario_id'           => $this->usuario_id,
            'nombre'               => $nombre,
            'token_condicion'      => $token_condicion,
            'indicador'            => $indicador,
            'operador'             => $operador,
            'valor'                => $valor,
            'accion_tipo'          => $accion_tipo,
            'accion_parametros_json' => wp_json_encode($accion_parametros),
            'activa'               => 1,
            'creada_por'           => $creada_por,
            'razon'                => $razon,
        ));

        return array(
            'exito'   => true,
            'id'      => $regla_id,
            'nombre'  => $nombre,
            'mensaje' => sprintf(__('Regla "%s" creada', 'flavor-chat-ia'), $nombre),
        );
    }

    /**
     * Elimina una regla por su ID
     *
     * @param string $regla_id ID de la regla
     * @return bool Exito
     */
    public function eliminar_regla($regla_id) {
        global $wpdb;
        $tabla = $wpdb->prefix . 'flavor_trading_ia_reglas';

        $eliminada = $wpdb->delete($tabla, array(
            'regla_id'   => $regla_id,
            'usuario_id' => $this->usuario_id,
        ));

        return $eliminada > 0;
    }

    /**
     * Activa o desactiva una regla
     *
     * @param string $regla_id ID de la regla
     * @param bool   $activa Estado
     * @return bool Exito
     */
    public function cambiar_estado_regla($regla_id, $activa) {
        global $wpdb;
        $tabla = $wpdb->prefix . 'flavor_trading_ia_reglas';

        $actualizada = $wpdb->update(
            $tabla,
            array('activa' => $activa ? 1 : 0),
            array('regla_id' => $regla_id, 'usuario_id' => $this->usuario_id)
        );

        return $actualizada > 0;
    }

    /**
     * Evalua todas las reglas activas contra datos de mercado
     *
     * @param array      $datos_mercado Datos de mercado por token
     * @param array|null $indicadores Indicadores tecnicos por token
     * @return array Resultado de la evaluacion
     */
    public function evaluar_reglas($datos_mercado, $indicadores = null) {
        global $wpdb;
        $tabla = $wpdb->prefix . 'flavor_trading_ia_reglas';

        $reglas = $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM $tabla WHERE usuario_id = %d AND activa = 1",
            $this->usuario_id
        ), ARRAY_A);

        $tokens_bloqueados_compra = array();
        $tokens_bloqueados_venta  = array();
        $alertas                  = array();
        $ajustes_parametros       = array();
        $reducciones_posicion     = array();
        $ventas_forzadas          = array();
        $reglas_activadas         = array();

        // Combinar datos de mercado con indicadores
        $datos_combinados = array();
        $tokens_mercado   = isset($datos_mercado['tokens']) ? $datos_mercado['tokens'] : $datos_mercado;

        foreach ($tokens_mercado as $token => $datos_token) {
            $datos_combinados[$token] = is_array($datos_token) ? $datos_token : array();
            if ($indicadores && isset($indicadores[$token])) {
                $datos_combinados[$token] = array_merge($datos_combinados[$token], $indicadores[$token]);
            }
        }

        foreach ($reglas as $regla) {
            $tokens_evaluar = array();

            if ('*' === $regla['token_condicion']) {
                $tokens_evaluar = array_keys($datos_combinados);
            } elseif (isset($datos_combinados[$regla['token_condicion']])) {
                $tokens_evaluar = array($regla['token_condicion']);
            }

            foreach ($tokens_evaluar as $token) {
                $datos_token = $datos_combinados[$token];

                if ($this->evaluar_condicion($regla['indicador'], $regla['operador'], floatval($regla['valor']), $datos_token)) {
                    $reglas_activadas[] = $regla;

                    // Actualizar contadores
                    $wpdb->update(
                        $tabla,
                        array(
                            'veces_activada'    => intval($regla['veces_activada']) + 1,
                            'ultima_activacion' => current_time('mysql'),
                        ),
                        array('id' => $regla['id'])
                    );

                    $accion_parametros = json_decode($regla['accion_parametros_json'], true) ?: array();

                    // Aplicar accion
                    switch ($regla['accion_tipo']) {
                        case 'bloquear_compra':
                            $tokens_bloqueados_compra[] = $token;
                            $alertas[] = sprintf('[%s] Compra bloqueada para %s', $regla['nombre'], $token);
                            break;

                        case 'bloquear_venta':
                            $tokens_bloqueados_venta[] = $token;
                            $alertas[] = sprintf('[%s] Venta bloqueada para %s', $regla['nombre'], $token);
                            break;

                        case 'reducir_posicion':
                            $porcentaje = isset($accion_parametros['porcentaje']) ? $accion_parametros['porcentaje'] : 50;
                            $reducciones_posicion[$token] = $porcentaje;
                            $alertas[] = sprintf('[%s] Reducir posicion %s un %d%%', $regla['nombre'], $token, $porcentaje);
                            break;

                        case 'forzar_venta':
                            $ventas_forzadas[] = $token;
                            $alertas[] = sprintf('[%s] Venta forzada de %s', $regla['nombre'], $token);
                            break;

                        case 'ajustar_parametro':
                            $nombre_parametro = isset($accion_parametros['nombre']) ? $accion_parametros['nombre'] : '';
                            $valor_parametro  = isset($accion_parametros['valor']) ? $accion_parametros['valor'] : null;
                            if ($nombre_parametro && null !== $valor_parametro) {
                                $ajustes_parametros[$nombre_parametro] = $valor_parametro;
                                $alertas[] = sprintf('[%s] Ajustar %s a %s', $regla['nombre'], $nombre_parametro, $valor_parametro);
                            }
                            break;

                        case 'alerta':
                            $mensaje = isset($accion_parametros['mensaje']) ? $accion_parametros['mensaje'] : $regla['nombre'];
                            $alertas[] = sprintf('[ALERTA] %s (%s)', $mensaje, $token);
                            break;
                    }
                }
            }
        }

        return array(
            'reglas_activadas'         => $reglas_activadas,
            'tokens_bloqueados_compra' => array_unique($tokens_bloqueados_compra),
            'tokens_bloqueados_venta'  => array_unique($tokens_bloqueados_venta),
            'alertas'                  => $alertas,
            'ajustes_parametros'       => $ajustes_parametros,
            'reducciones_posicion'     => $reducciones_posicion,
            'ventas_forzadas'          => array_unique($ventas_forzadas),
        );
    }

    /**
     * Evalua una condicion individual contra datos del token
     *
     * @param string $indicador Nombre del indicador
     * @param string $operador Operador de comparacion
     * @param float  $valor Valor de referencia
     * @param array  $datos_token Datos del token
     * @return bool Resultado de la evaluacion
     */
    private function evaluar_condicion($indicador, $operador, $valor, $datos_token) {
        $mapeo_indicadores = array(
            'rsi'        => 'rsi_14',
            'cambio_24h' => 'cambio_24h',
            'precio'     => 'precio_usd',
            'volumen_24h' => 'volumen_24h',
            'fuerza'     => 'senal_fuerza',
        );

        $clave_dato = isset($mapeo_indicadores[$indicador]) ? $mapeo_indicadores[$indicador] : $indicador;

        if (!isset($datos_token[$clave_dato])) {
            return false;
        }

        $valor_actual = floatval($datos_token[$clave_dato]);

        switch ($operador) {
            case '>':
                return $valor_actual > $valor;
            case '<':
                return $valor_actual < $valor;
            case '>=':
                return $valor_actual >= $valor;
            case '<=':
                return $valor_actual <= $valor;
            case '==':
                return abs($valor_actual - $valor) < 0.001;
            default:
                return false;
        }
    }

    /**
     * Obtiene todas las reglas del usuario
     *
     * @return array Lista de reglas
     */
    public function obtener_reglas() {
        global $wpdb;
        $tabla = $wpdb->prefix . 'flavor_trading_ia_reglas';

        $reglas = $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM $tabla WHERE usuario_id = %d ORDER BY fecha_creacion DESC",
            $this->usuario_id
        ), ARRAY_A);

        return $reglas ?: array();
    }

    /**
     * Obtiene un resumen del estado del motor de reglas
     *
     * @return array Estado
     */
    public function obtener_estado() {
        global $wpdb;
        $tabla = $wpdb->prefix . 'flavor_trading_ia_reglas';

        $total_reglas = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM $tabla WHERE usuario_id = %d", $this->usuario_id
        ));

        $reglas_activas = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM $tabla WHERE usuario_id = %d AND activa = 1", $this->usuario_id
        ));

        $reglas_ia = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM $tabla WHERE usuario_id = %d AND creada_por = 'ia'", $this->usuario_id
        ));

        $total_activaciones = $wpdb->get_var($wpdb->prepare(
            "SELECT COALESCE(SUM(veces_activada), 0) FROM $tabla WHERE usuario_id = %d", $this->usuario_id
        ));

        return array(
            'total_reglas'       => intval($total_reglas),
            'reglas_activas'     => intval($reglas_activas),
            'reglas_ia'          => intval($reglas_ia),
            'total_activaciones' => intval($total_activaciones),
            'max_reglas'         => self::MAX_REGLAS,
        );
    }
}

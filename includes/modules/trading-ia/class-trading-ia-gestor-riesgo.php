<?php
/**
 * Gestion de Riesgo - Protege el capital del usuario
 *
 * @package FlavorChatIA
 */

if (!defined('ABSPATH')) {
    exit;
}

class Flavor_Trading_IA_Gestor_Riesgo {

    /**
     * Configuracion de limites
     */
    private $riesgo_maximo_por_trade;
    private $stop_loss_global;
    private $max_trades_por_hora;
    private $max_posiciones_abiertas;
    private $min_balance_usd;

    /**
     * ID del usuario
     */
    private $usuario_id;

    /**
     * Constructor
     *
     * @param array $configuracion Parametros de riesgo
     * @param int   $usuario_id ID del usuario
     */
    public function __construct($configuracion, $usuario_id = 0) {
        $this->usuario_id              = $usuario_id;
        $this->riesgo_maximo_por_trade = floatval($configuracion['riesgo_maximo_porcentaje'] ?? 5.0);
        $this->stop_loss_global        = floatval($configuracion['stop_loss_global'] ?? 15.0);
        $this->max_trades_por_hora     = intval($configuracion['max_trades_por_hora'] ?? 10);
        $this->max_posiciones_abiertas = intval($configuracion['max_posiciones_abiertas'] ?? 5);
        $this->min_balance_usd         = floatval($configuracion['min_balance_usd'] ?? 10.0);
    }

    /**
     * Actualiza los limites basandose en parametros del usuario
     *
     * @param int   $agresividad Nivel de agresividad (1-10)
     * @param float $riesgo_maximo Riesgo maximo por trade
     * @param float $stop_loss Stop loss global
     */
    public function actualizar_limites($agresividad, $riesgo_maximo, $stop_loss) {
        $factor_agresividad = $agresividad / 5.0;

        $this->riesgo_maximo_por_trade = min($riesgo_maximo, 25.0);
        $this->stop_loss_global        = min($stop_loss * $factor_agresividad, 20.0);
        $this->max_trades_por_hora     = intval(5 + ($agresividad * 2));
        $this->max_posiciones_abiertas = min(3 + $agresividad, 10);
    }

    /**
     * Obtiene el estado actual de riesgo
     *
     * @param float $balance_actual Balance actual en USD
     * @param array $posiciones Lista de posiciones abiertas
     * @return array Estado de riesgo
     */
    public function obtener_estado_riesgo($balance_actual, $posiciones) {
        $perdida_hoy     = $this->obtener_perdida_diaria();
        $trades_recientes = $this->obtener_trades_ultima_hora();

        $porcentaje_perdida = $balance_actual > 0
            ? ($perdida_hoy / $balance_actual * 100)
            : 0;

        $nivel         = 'bajo';
        $puede_operar  = true;
        $razon_bloqueo = '';

        // Determinar nivel de riesgo
        if ($porcentaje_perdida >= $this->stop_loss_global) {
            $nivel         = 'critico';
            $puede_operar  = false;
            $razon_bloqueo = sprintf('Stop loss global alcanzado: %.1f%%', $porcentaje_perdida);
        } elseif ($porcentaje_perdida >= $this->stop_loss_global * 0.7) {
            $nivel = 'alto';
        } elseif ($porcentaje_perdida >= $this->stop_loss_global * 0.4) {
            $nivel = 'medio';
        }

        // Verificar limite de trades por hora
        if ($trades_recientes >= $this->max_trades_por_hora) {
            $puede_operar  = false;
            $razon_bloqueo = sprintf('Limite de trades por hora alcanzado: %d', $trades_recientes);
        }

        // Verificar posiciones abiertas
        if (count($posiciones) >= $this->max_posiciones_abiertas) {
            $puede_operar  = false;
            $razon_bloqueo = sprintf('Limite de posiciones abiertas: %d', count($posiciones));
        }

        // Verificar balance minimo
        if ($balance_actual < $this->min_balance_usd) {
            $puede_operar  = false;
            $razon_bloqueo = sprintf('Balance muy bajo: $%.2f', $balance_actual);
        }

        return array(
            'nivel'                => $nivel,
            'perdida_diaria_actual' => $perdida_hoy,
            'trades_ultima_hora'   => $trades_recientes,
            'posiciones_abiertas'  => count($posiciones),
            'balance_actual'       => $balance_actual,
            'puede_operar'         => $puede_operar,
            'razon_bloqueo'        => $razon_bloqueo,
        );
    }

    /**
     * Valida si un trade propuesto cumple con los limites de riesgo
     *
     * @param string $tipo Tipo de operacion (COMPRAR/VENDER)
     * @param float  $cantidad_usd Cantidad en USD
     * @param float  $balance_total Balance total
     * @param array  $posiciones_actuales Posiciones abiertas
     * @return array Resultado de validacion
     */
    public function validar_trade($tipo, $cantidad_usd, $balance_total, $posiciones_actuales) {
        $advertencias      = array();
        $cantidad_ajustada = $cantidad_usd;

        $estado = $this->obtener_estado_riesgo($balance_total, $posiciones_actuales);

        // Verificar si podemos operar
        if (!$estado['puede_operar']) {
            return array(
                'aprobado'          => false,
                'cantidad_ajustada' => 0,
                'razon'             => $estado['razon_bloqueo'] ?: __('Trading bloqueado por riesgo', 'flavor-platform'),
                'advertencias'      => array(),
            );
        }

        // Verificar porcentaje maximo por trade
        $porcentaje_trade = $balance_total > 0 ? ($cantidad_usd / $balance_total * 100) : 100;

        if ($porcentaje_trade > $this->riesgo_maximo_por_trade) {
            $cantidad_ajustada = $balance_total * ($this->riesgo_maximo_por_trade / 100);
            $advertencias[] = sprintf(
                __('Cantidad reducida de $%.2f a $%.2f (max %.0f%% por trade)', 'flavor-platform'),
                $cantidad_usd,
                $cantidad_ajustada,
                $this->riesgo_maximo_por_trade
            );
        }

        // Reducir posicion si riesgo alto
        if ('alto' === $estado['nivel']) {
            $cantidad_ajustada *= 0.5;
            $advertencias[] = __('Posicion reducida 50% por nivel de riesgo ALTO', 'flavor-platform');
        }

        // Cantidad minima viable
        if ($cantidad_ajustada < 1.0) {
            return array(
                'aprobado'          => false,
                'cantidad_ajustada' => 0,
                'razon'             => sprintf(
                    __('Cantidad muy pequena ($%.2f). Minimo: $1.00', 'flavor-platform'),
                    $cantidad_ajustada
                ),
                'advertencias'      => $advertencias,
            );
        }

        return array(
            'aprobado'          => true,
            'cantidad_ajustada' => $cantidad_ajustada,
            'razon'             => __('Trade aprobado', 'flavor-platform'),
            'advertencias'      => $advertencias,
        );
    }

    /**
     * Registra un trade ejecutado para tracking de riesgo
     *
     * @param array $informacion_trade Datos del trade
     */
    public function registrar_trade($informacion_trade) {
        $clave_hora = 'flavor_trading_ia_trades_hora_' . $this->usuario_id;
        $contador   = intval(get_transient($clave_hora));
        set_transient($clave_hora, $contador + 1, HOUR_IN_SECONDS);
    }

    /**
     * Registra una perdida
     *
     * @param float $monto Monto de la perdida
     */
    public function registrar_perdida($monto) {
        $clave_dia = 'flavor_trading_ia_perdida_dia_' . $this->usuario_id . '_' . date('Y-m-d');
        $perdida   = floatval(get_transient($clave_dia));
        set_transient($clave_dia, $perdida + $monto, DAY_IN_SECONDS);
    }

    /**
     * Registra una ganancia
     *
     * @param float $monto Monto de la ganancia
     */
    public function registrar_ganancia($monto) {
        $clave_dia = 'flavor_trading_ia_perdida_dia_' . $this->usuario_id . '_' . date('Y-m-d');
        $perdida   = floatval(get_transient($clave_dia));
        set_transient($clave_dia, $perdida - $monto, DAY_IN_SECONDS);
    }

    /**
     * Obtiene la perdida diaria acumulada
     *
     * @return float Perdida del dia
     */
    private function obtener_perdida_diaria() {
        $clave_dia = 'flavor_trading_ia_perdida_dia_' . $this->usuario_id . '_' . date('Y-m-d');
        return floatval(get_transient($clave_dia));
    }

    /**
     * Obtiene el numero de trades en la ultima hora
     *
     * @return int Numero de trades
     */
    private function obtener_trades_ultima_hora() {
        $clave_hora = 'flavor_trading_ia_trades_hora_' . $this->usuario_id;
        return intval(get_transient($clave_hora));
    }

    /**
     * Valida si el momentum del mercado permite la entrada
     *
     * Bloquea operaciones contra momentum fuerte para evitar
     * entrar en contra de la tendencia dominante.
     *
     * @param string $tipo_operacion COMPRAR o VENDER
     * @param float  $senal_fuerza Fuerza de la senal (-100 a 100)
     * @param string $senal_tendencia Tendencia detectada (alcista/bajista/lateral)
     * @param float  $rsi_valor Valor actual del RSI
     * @param string $divergencia_volumen Tipo de divergencia (alcista/bajista/ninguna)
     * @return array [permitido => bool, razon => string]
     */
    public function validar_momentum_entrada($tipo_operacion, $senal_fuerza, $senal_tendencia, $rsi_valor, $divergencia_volumen = 'ninguna') {
        if ('COMPRAR' === $tipo_operacion) {
            // Bloquear compra si momentum muy negativo con tendencia bajista
            // Excepcion: divergencia alcista de volumen indica posible giro
            if ($senal_fuerza < -50 && 'bajista' === $senal_tendencia) {
                if ('alcista' !== $divergencia_volumen) {
                    return array(
                        'permitido' => false,
                        'razon'     => sprintf(
                            'Momentum negativo (%.0f) con tendencia bajista - compra bloqueada',
                            $senal_fuerza
                        ),
                    );
                }
            }

            // Bloquear compra si RSI sobrecomprado
            if ($rsi_valor > 80) {
                return array(
                    'permitido' => false,
                    'razon'     => sprintf('RSI sobrecomprado (%.1f > 80) - compra bloqueada', $rsi_valor),
                );
            }
        }

        if ('VENDER' === $tipo_operacion) {
            // Bloquear venta si momentum muy positivo con tendencia alcista
            // Excepcion: divergencia bajista de volumen indica posible techo
            if ($senal_fuerza > 50 && 'alcista' === $senal_tendencia) {
                if ('bajista' !== $divergencia_volumen) {
                    return array(
                        'permitido' => false,
                        'razon'     => sprintf(
                            'Momentum positivo (%.0f) con tendencia alcista - venta bloqueada',
                            $senal_fuerza
                        ),
                    );
                }
            }

            // Bloquear venta si RSI sobrevendido
            if ($rsi_valor < 20) {
                return array(
                    'permitido' => false,
                    'razon'     => sprintf('RSI sobrevendido (%.1f < 20) - venta bloqueada', $rsi_valor),
                );
            }
        }

        return array(
            'permitido' => true,
            'razon'     => 'Momentum compatible con operacion',
        );
    }

    /**
     * Calcula el factor de dimensionamiento adaptativo de posicion
     *
     * Factor multiplicador entre 0.1 y 1.5 basado en:
     * - Racha actual de ganancias/perdidas
     * - Confianza de la IA
     * - Volatilidad del mercado
     * - Tasa de acierto historica
     *
     * @param array  $informacion_racha Datos de racha [tipo, longitud]
     * @param float  $confianza_ia Confianza de la IA (0-100)
     * @param string $volatilidad Nivel de volatilidad (alta/media/baja)
     * @param float  $tasa_acierto Porcentaje de trades ganadores (0-100)
     * @return float Factor multiplicador (0.1 - 1.5)
     */
    public function calcular_factor_tamano_posicion($informacion_racha, $confianza_ia, $volatilidad, $tasa_acierto) {
        $factor_base = 1.0;

        // Racha de perdidas: -0.15 por trade consecutivo, minimo 0.25
        if ('perdida' === $informacion_racha['tipo'] && $informacion_racha['longitud'] > 0) {
            $penalizacion_racha = max(0.25, 1.0 - ($informacion_racha['longitud'] * 0.15));
            $factor_base *= $penalizacion_racha;
        }

        // Racha de ganancias: +0.05 por trade consecutivo, maximo 1.2
        if ('ganancia' === $informacion_racha['tipo'] && $informacion_racha['longitud'] > 0) {
            $bonificacion_racha = min(1.2, 1.0 + ($informacion_racha['longitud'] * 0.05));
            $factor_base *= $bonificacion_racha;
        }

        // Escalado por confianza de la IA: rango 0.5 - 1.0
        $factor_confianza = max(0.5, min(1.0, $confianza_ia / 100));
        $factor_base *= $factor_confianza;

        // Ajuste por volatilidad
        $volatilidad_normalizada = strtolower($volatilidad);
        if (in_array($volatilidad_normalizada, array('alta', 'high'), true)) {
            $factor_base *= 0.6;
        } elseif (in_array($volatilidad_normalizada, array('media', 'medium', 'med'), true)) {
            $factor_base *= 0.85;
        }
        // Baja/low: factor 1.0, no se modifica

        // Ajuste por tasa de acierto historica
        if ($tasa_acierto < 40) {
            $factor_base *= 0.7;
        } elseif ($tasa_acierto > 60) {
            $factor_base *= 1.1;
        }

        // Limitar entre 0.1 y 1.5
        $factor_final = max(0.1, min(1.5, $factor_base));

        return round($factor_final, 3);
    }

    /**
     * Obtiene un resumen del estado de riesgo
     *
     * @return array Resumen
     */
    public function obtener_resumen() {
        return array(
            'limites' => array(
                'riesgo_max_por_trade' => $this->riesgo_maximo_por_trade,
                'stop_loss_global'     => $this->stop_loss_global,
                'max_trades_hora'      => $this->max_trades_por_hora,
                'max_posiciones'       => $this->max_posiciones_abiertas,
            ),
            'hoy' => array(
                'perdida_acumulada' => $this->obtener_perdida_diaria(),
                'trades_hora'       => $this->obtener_trades_ultima_hora(),
            ),
        );
    }
}

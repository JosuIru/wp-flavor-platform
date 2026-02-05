<?php
/**
 * Orquestador del Ciclo de Trading
 *
 * @package FlavorChatIA
 */

if (!defined('ABSPATH')) {
    exit;
}

class Flavor_Trading_IA_Ciclo {

    /**
     * Referencia al modulo principal
     */
    private $modulo;

    /**
     * Constructor
     *
     * @param object $modulo Modulo principal
     */
    public function __construct($modulo) {
        $this->modulo = $modulo;
    }

    /**
     * Obtiene informacion de la racha actual y tasa de acierto
     *
     * Analiza los ultimos trades de venta para determinar
     * la racha consecutiva (ganancias/perdidas) y el hit rate.
     *
     * @param Flavor_Trading_IA_Paper_Trading $paper_trading Instancia de paper trading
     * @return array Datos de racha y estadisticas
     */
    private function obtener_informacion_racha($paper_trading) {
        $historial = $paper_trading->obtener_historial(20);

        $tipo_racha      = 'ninguna';
        $longitud_racha  = 0;
        $total_ventas    = 0;
        $ventas_ganadoras = 0;

        foreach ($historial as $trade) {
            if ('VENTA' !== $trade['tipo']) {
                continue;
            }

            $total_ventas++;
            $pnl_trade   = floatval($trade['pnl']);
            $es_ganancia = $pnl_trade >= 0;

            if ($es_ganancia) {
                $ventas_ganadoras++;
            }

            // Determinar racha desde los trades mas recientes
            if (0 === $longitud_racha) {
                $tipo_racha     = $es_ganancia ? 'ganancia' : 'perdida';
                $longitud_racha = 1;
            } elseif (('ganancia' === $tipo_racha && $es_ganancia) || ('perdida' === $tipo_racha && !$es_ganancia)) {
                $longitud_racha++;
            } else {
                break; // La racha se rompio
            }
        }

        $tasa_acierto = $total_ventas > 0
            ? ($ventas_ganadoras / $total_ventas * 100)
            : 50.0;

        return array(
            'tipo'              => $tipo_racha,
            'longitud'          => $longitud_racha,
            'total_ventas'      => $total_ventas,
            'ventas_ganadoras'  => $ventas_ganadoras,
            'tasa_acierto'      => round($tasa_acierto, 1),
        );
    }

    /**
     * Ejecuta un ciclo completo de trading
     *
     * @param int $usuario_id ID del usuario
     * @return array Resultado del ciclo
     */
    public function ejecutar_ciclo($usuario_id) {
        $resultado_ciclo = array(
            'timestamp'       => current_time('c'),
            'exito'           => false,
            'decision'        => null,
            'trades_ejecutados' => array(),
            'alertas'         => array(),
            'errores'         => array(),
        );

        try {
            // 1. Obtener datos de mercado
            $mercado              = new Flavor_Trading_IA_Mercado();
            $tokens_monitoreados  = $this->modulo->get_setting('tokens_monitoreados', array('SOL', 'BONK', 'JUP', 'WIF', 'JTO'));
            $datos_mercado        = $mercado->obtener_datos_para_ia($tokens_monitoreados);
            $precios_simples      = $mercado->obtener_precios_simples($tokens_monitoreados);

            if (empty($datos_mercado['tokens'])) {
                $resultado_ciclo['errores'][] = 'No se pudieron obtener datos de mercado';
                return $resultado_ciclo;
            }

            // 2. Actualizar indicadores tecnicos
            $calculador_indicadores = new Flavor_Trading_IA_Indicadores();
            $indicadores_por_token  = array();

            foreach ($precios_simples as $token => $precio) {
                $datos_token_mercado = isset($datos_mercado['tokens'][$token]) ? $datos_mercado['tokens'][$token] : array();
                $volumen = isset($datos_token_mercado['volumen_24h_usd']) ? floatval(str_replace(array('$', ','), '', $datos_token_mercado['volumen_24h_usd'])) : 0;

                $calculador_indicadores->agregar_precio($token, $precio, $volumen);
                $indicadores_por_token[$token] = $calculador_indicadores->calcular_indicadores($token, $precio);
            }

            // 3. Actualizar paper trading con precios actuales
            $paper_trading = new Flavor_Trading_IA_Paper_Trading(
                $usuario_id,
                $this->modulo->get_setting('balance_inicial', 1000.0)
            );
            $paper_trading->actualizar_precios($precios_simples);

            // 4. Verificar riesgo
            $configuracion_riesgo = array(
                'riesgo_maximo_porcentaje' => $this->modulo->get_setting('riesgo_maximo_porcentaje', 5),
                'stop_loss_global'         => $this->modulo->get_setting('stop_loss_global', 15),
                'max_trades_por_hora'      => $this->modulo->get_setting('max_trades_por_hora', 10),
                'max_posiciones_abiertas'   => $this->modulo->get_setting('max_posiciones_abiertas', 5),
                'min_balance_usd'          => $this->modulo->get_setting('min_balance_usd', 10),
            );

            $gestor_riesgo   = new Flavor_Trading_IA_Gestor_Riesgo($configuracion_riesgo, $usuario_id);
            $portfolio       = $paper_trading->obtener_estado_portfolio();
            $estado_riesgo   = $gestor_riesgo->obtener_estado_riesgo(
                $portfolio['balance_total_usd'],
                $portfolio['posiciones']
            );

            if (!$estado_riesgo['puede_operar']) {
                $resultado_ciclo['alertas'][] = 'Trading bloqueado: ' . $estado_riesgo['razon_bloqueo'];
                $resultado_ciclo['exito'] = true;
                return $resultado_ciclo;
            }

            // 5. Llamar al cerebro IA para decision
            $cerebro = new Flavor_Trading_IA_Cerebro($usuario_id, $this->modulo);

            $parametros_usuario = array(
                'agresividad'              => $this->modulo->get_setting('agresividad', 5),
                'riesgo_maximo_porcentaje' => $this->modulo->get_setting('riesgo_maximo_porcentaje', 5),
                'stop_loss_porcentaje'     => $this->modulo->get_setting('stop_loss_porcentaje', 3),
                'take_profit_porcentaje'   => $this->modulo->get_setting('take_profit_porcentaje', 5),
                'confianza_minima_trade'   => $this->modulo->get_setting('confianza_minima_trade', 60),
                'auto_ajuste_enabled'      => $this->modulo->get_setting('auto_ajuste_enabled', false),
                'tokens_monitoreados'      => $tokens_monitoreados,
                'intervalo_analisis'       => $this->modulo->get_setting('intervalo_analisis', 60),
            );

            $decision = $cerebro->analizar_y_decidir($datos_mercado, $portfolio, $parametros_usuario);
            $resultado_ciclo['decision'] = $decision;

            // 6. Procesar reglas sugeridas por la IA
            if (!empty($decision['reglas_sugeridas'])) {
                $gestor_reglas = new Flavor_Trading_IA_Reglas_Dinamicas($usuario_id);
                foreach ($decision['reglas_sugeridas'] as $regla_sugerida) {
                    if (!is_array($regla_sugerida)) {
                        continue;
                    }
                    $gestor_reglas->agregar_regla(
                        $regla_sugerida['nombre'] ?? 'Regla IA',
                        $regla_sugerida['token'] ?? '*',
                        $regla_sugerida['indicador'] ?? 'precio',
                        $regla_sugerida['operador'] ?? '>',
                        floatval($regla_sugerida['valor'] ?? 0),
                        $regla_sugerida['accion_tipo'] ?? 'alerta',
                        $regla_sugerida['accion_parametros'] ?? array(),
                        $regla_sugerida['razon'] ?? '',
                        'ia'
                    );
                }
            }

            // 7. Procesar cambios de watchlist
            if (!empty($decision['cambios_watchlist'])) {
                foreach ($decision['cambios_watchlist'] as $cambio) {
                    if (!is_array($cambio) || empty($cambio['token'])) {
                        continue;
                    }

                    $accion_watchlist = isset($cambio['accion']) ? $cambio['accion'] : '';
                    $token_cambio     = strtoupper($cambio['token']);

                    if ('agregar' === $accion_watchlist && !in_array($token_cambio, $tokens_monitoreados, true)) {
                        $tokens_monitoreados[] = $token_cambio;
                        $this->modulo->update_setting('tokens_monitoreados', $tokens_monitoreados);
                    } elseif ('eliminar' === $accion_watchlist) {
                        $tokens_monitoreados = array_values(array_diff($tokens_monitoreados, array($token_cambio)));
                        $this->modulo->update_setting('tokens_monitoreados', $tokens_monitoreados);
                    }
                }
            }

            // 8. Procesar auto-ajustes
            if ($this->modulo->get_setting('auto_ajuste_enabled', false) && !empty($decision['ajustes_sugeridos'])) {
                $gestor_ajuste = new Flavor_Trading_IA_Auto_Ajuste($this->modulo, $usuario_id);
                $resultado_ajuste = $gestor_ajuste->aplicar_ajuste_seguro($decision['ajustes_sugeridos']);

                if ($resultado_ajuste['aplicado']) {
                    $resultado_ciclo['alertas'][] = sprintf(
                        'Auto-ajuste: %d parametros modificados',
                        $resultado_ajuste['cantidad_cambios']
                    );
                }
            }

            // 9. Evaluar reglas dinamicas existentes
            $gestor_reglas     = new Flavor_Trading_IA_Reglas_Dinamicas($usuario_id);
            $resultado_reglas  = $gestor_reglas->evaluar_reglas($datos_mercado, $indicadores_por_token);

            if (!empty($resultado_reglas['alertas'])) {
                $resultado_ciclo['alertas'] = array_merge($resultado_ciclo['alertas'], $resultado_reglas['alertas']);
            }

            // 10. Ejecutar ventas forzadas por reglas
            foreach ($resultado_reglas['ventas_forzadas'] as $token_venta) {
                $resultado_venta = $paper_trading->ejecutar_venta($token_venta);
                if ($resultado_venta['exito']) {
                    $resultado_ciclo['trades_ejecutados'][] = $resultado_venta;
                    $gestor_riesgo->registrar_trade($resultado_venta);
                }
            }

            // 11. Ejecutar reducciones de posicion
            foreach ($resultado_reglas['reducciones_posicion'] as $token_reduccion => $porcentaje) {
                $tokens_en_cartera = $paper_trading->obtener_tokens();
                if (isset($tokens_en_cartera[$token_reduccion]) && $tokens_en_cartera[$token_reduccion] > 0) {
                    $cantidad_reducir = $tokens_en_cartera[$token_reduccion] * ($porcentaje / 100);
                    $resultado_reduccion = $paper_trading->ejecutar_venta($token_reduccion, $cantidad_reducir);
                    if ($resultado_reduccion['exito']) {
                        $resultado_ciclo['trades_ejecutados'][] = $resultado_reduccion;
                        $gestor_riesgo->registrar_trade($resultado_reduccion);
                    }
                }
            }

            // 12. Ejecutar decision de la IA
            $confianza_minima = $this->modulo->get_setting('confianza_minima_trade', 60);

            if ($decision['confianza'] >= $confianza_minima) {
                // Obtener indicadores del token para filtro de momentum
                $token_decision         = !empty($decision['token']) ? $decision['token'] : '';
                $indicadores_token_dec  = isset($indicadores_por_token[$token_decision])
                    ? $indicadores_por_token[$token_decision]
                    : array();

                $senal_fuerza_decision     = floatval($indicadores_token_dec['senal_fuerza'] ?? 0);
                $senal_tendencia_decision  = $indicadores_token_dec['senal_tendencia'] ?? 'lateral';
                $rsi_valor_decision        = floatval($indicadores_token_dec['rsi'] ?? 50);
                $divergencia_decision      = $indicadores_token_dec['divergencia_precio_volumen'] ?? 'ninguna';

                if ('COMPRAR' === $decision['accion'] && !empty($token_decision)) {
                    // Verificar que el token no este bloqueado
                    if (!in_array($token_decision, $resultado_reglas['tokens_bloqueados_compra'], true)) {

                        // Filtro de momentum: bloquear compra contra tendencia fuerte
                        $validacion_momentum = $gestor_riesgo->validar_momentum_entrada(
                            'COMPRAR',
                            $senal_fuerza_decision,
                            $senal_tendencia_decision,
                            $rsi_valor_decision,
                            $divergencia_decision
                        );

                        if (!$validacion_momentum['permitido']) {
                            $resultado_ciclo['alertas'][] = sprintf(
                                'Compra %s rechazada: %s',
                                $token_decision,
                                $validacion_momentum['razon']
                            );
                        } else {
                            $cantidad_usd = $portfolio['disponible_usd'] * ($decision['cantidad_porcentaje'] / 100);

                            // Dimensionamiento adaptativo de posicion
                            $informacion_racha = $this->obtener_informacion_racha($paper_trading);
                            $volatilidad_token = $indicadores_token_dec['volatilidad'] ?? 'media';
                            $factor_tamano     = $gestor_riesgo->calcular_factor_tamano_posicion(
                                $informacion_racha,
                                $decision['confianza'],
                                $volatilidad_token,
                                $informacion_racha['tasa_acierto']
                            );

                            $cantidad_usd *= $factor_tamano;

                            if ($factor_tamano < 1.0) {
                                $resultado_ciclo['alertas'][] = sprintf(
                                    'Posicion %s reducida a %.0f%% (factor: %.3f, racha: %s x%d, acierto: %.0f%%)',
                                    $token_decision,
                                    $factor_tamano * 100,
                                    $factor_tamano,
                                    $informacion_racha['tipo'],
                                    $informacion_racha['longitud'],
                                    $informacion_racha['tasa_acierto']
                                );
                            }

                            // Validar con gestor de riesgo
                            $validacion = $gestor_riesgo->validar_trade(
                                'COMPRAR',
                                $cantidad_usd,
                                $portfolio['balance_total_usd'],
                                $portfolio['posiciones']
                            );

                            if ($validacion['aprobado']) {
                                $precio_token = isset($precios_simples[$token_decision]) ? $precios_simples[$token_decision] : null;
                                $resultado_compra = $paper_trading->ejecutar_compra(
                                    $token_decision,
                                    $validacion['cantidad_ajustada'],
                                    $precio_token
                                );

                                if ($resultado_compra['exito']) {
                                    $resultado_ciclo['trades_ejecutados'][] = $resultado_compra;
                                    $gestor_riesgo->registrar_trade($resultado_compra);
                                }
                            }
                        }
                    }
                } elseif ('VENDER' === $decision['accion'] && !empty($token_decision)) {
                    if (!in_array($token_decision, $resultado_reglas['tokens_bloqueados_venta'], true)) {

                        // Filtro de momentum: bloquear venta contra tendencia fuerte
                        $validacion_momentum_venta = $gestor_riesgo->validar_momentum_entrada(
                            'VENDER',
                            $senal_fuerza_decision,
                            $senal_tendencia_decision,
                            $rsi_valor_decision,
                            $divergencia_decision
                        );

                        if (!$validacion_momentum_venta['permitido']) {
                            $resultado_ciclo['alertas'][] = sprintf(
                                'Venta %s rechazada: %s',
                                $token_decision,
                                $validacion_momentum_venta['razon']
                            );
                        } else {
                            $resultado_venta_ia = $paper_trading->ejecutar_venta($token_decision);
                            if ($resultado_venta_ia['exito']) {
                                $resultado_ciclo['trades_ejecutados'][] = $resultado_venta_ia;
                                $gestor_riesgo->registrar_trade($resultado_venta_ia);

                                // Registrar ganancia/perdida para riesgo
                                $pnl = floatval($resultado_venta_ia['pnl_porcentaje'] ?? 0);
                                if ($pnl < 0) {
                                    $gestor_riesgo->registrar_perdida(abs($pnl));
                                } else {
                                    $gestor_riesgo->registrar_ganancia($pnl);
                                }
                            }
                        }
                    }
                }
            }

            $resultado_ciclo['exito'] = true;

        } catch (\Exception $excepcion) {
            $resultado_ciclo['errores'][] = $excepcion->getMessage();
            flavor_chat_ia_log('Error en ciclo trading: ' . $excepcion->getMessage(), 'trading_ia');
        }

        return $resultado_ciclo;
    }
}

<?php
/**
 * Calculador de Indicadores Tecnicos para Trading
 *
 * @package FlavorPlatform
 */

if (!defined('ABSPATH')) {
    exit;
}

class Flavor_Trading_IA_Indicadores {

    /**
     * Maximo de puntos de precio por token
     */
    const MAX_HISTORIAL = 500;

    /**
     * Agrega un nuevo precio al historial en base de datos
     *
     * @param string $token Simbolo del token
     * @param float  $precio Precio actual
     * @param float  $volumen Volumen (opcional)
     */
    public function agregar_precio($token, $precio, $volumen = 0) {
        global $wpdb;
        $tabla = $wpdb->prefix . 'flavor_trading_ia_indicadores';

        $wpdb->insert($tabla, array(
            'token'     => $token,
            'precio'    => $precio,
            'volumen'   => $volumen,
            'timestamp' => current_time('mysql'),
        ), array('%s', '%f', '%f', '%s'));

        // Mantener maximo de puntos por token
        $total_registros = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM $tabla WHERE token = %s",
            $token
        ));

        if ($total_registros > self::MAX_HISTORIAL) {
            $registros_eliminar = $total_registros - self::MAX_HISTORIAL;
            $wpdb->query($wpdb->prepare(
                "DELETE FROM $tabla WHERE token = %s ORDER BY timestamp ASC LIMIT %d",
                $token,
                $registros_eliminar
            ));
        }
    }

    /**
     * Carga el historial de precios de un token desde la base de datos
     *
     * @param string $token Simbolo del token
     * @param int    $limite Maximo de registros
     * @return array Lista de precios
     */
    public function cargar_historial_precios($token, $limite = 500) {
        global $wpdb;
        $tabla = $wpdb->prefix . 'flavor_trading_ia_indicadores';

        $resultados = $wpdb->get_results($wpdb->prepare(
            "SELECT precio FROM $tabla WHERE token = %s ORDER BY timestamp ASC LIMIT %d",
            $token,
            $limite
        ), ARRAY_A);

        return array_map(function ($registro) {
            return floatval($registro['precio']);
        }, $resultados);
    }

    /**
     * Carga el historial de volumenes de un token desde la base de datos
     *
     * @param string $token Simbolo del token
     * @param int    $limite Maximo de registros
     * @return array Lista de volumenes
     */
    public function cargar_historial_volumenes($token, $limite = 500) {
        global $wpdb;
        $tabla = $wpdb->prefix . 'flavor_trading_ia_indicadores';

        $resultados = $wpdb->get_results($wpdb->prepare(
            "SELECT volumen FROM $tabla WHERE token = %s ORDER BY timestamp ASC LIMIT %d",
            $token,
            $limite
        ), ARRAY_A);

        return array_map(function ($registro) {
            return floatval($registro['volumen']);
        }, $resultados);
    }

    /**
     * Calcula todos los indicadores tecnicos para un token
     *
     * @param string $token Simbolo del token
     * @param float  $precio_actual Precio actual
     * @return array Indicadores calculados
     */
    public function calcular_indicadores($token, $precio_actual) {
        $precios = $this->cargar_historial_precios($token);

        if (empty($precios)) {
            $precios = array($precio_actual);
        }

        if (end($precios) != $precio_actual) {
            $precios[] = $precio_actual;
        }

        $indicadores = array(
            'precio_actual'      => $precio_actual,
            'precio_apertura_24h' => 0,
            'precio_maximo_24h'  => 0,
            'precio_minimo_24h'  => 0,
            'sma_7'              => $this->calcular_sma($precios, 7),
            'sma_25'             => $this->calcular_sma($precios, 25),
            'sma_99'             => $this->calcular_sma($precios, 99),
            'ema_12'             => $this->calcular_ema($precios, 12),
            'ema_26'             => $this->calcular_ema($precios, 26),
            'rsi_14'             => $this->calcular_rsi($precios, 14),
            'macd_line'          => 0,
            'macd_signal'        => 0,
            'macd_histogram'     => 0,
            'bb_upper'           => 0,
            'bb_middle'          => 0,
            'bb_lower'           => 0,
            'bb_width'           => 0,
            'momentum_10'        => $this->calcular_momentum($precios, 10),
            'rate_of_change'     => $this->calcular_roc($precios, 10),
            'senal_tendencia'    => 'neutral',
            'senal_fuerza'       => 0,
            'senal_rsi'          => 'neutral',
        );

        // MACD
        $macd = $this->calcular_macd($precios);
        $indicadores['macd_line']      = $macd['line'];
        $indicadores['macd_signal']    = $macd['signal'];
        $indicadores['macd_histogram'] = $macd['histogram'];

        // Bollinger Bands
        $bollinger = $this->calcular_bollinger_bands($precios, 20, 2);
        $indicadores['bb_upper']  = $bollinger['upper'];
        $indicadores['bb_middle'] = $bollinger['middle'];
        $indicadores['bb_lower']  = $bollinger['lower'];
        $indicadores['bb_width']  = $bollinger['width'];

        // Precios 24h
        if (count($precios) >= 24) {
            $ultimos_24 = array_slice($precios, -24);
            $indicadores['precio_apertura_24h'] = $ultimos_24[0];
            $indicadores['precio_maximo_24h']   = max($ultimos_24);
            $indicadores['precio_minimo_24h']   = min($ultimos_24);
        } else {
            $indicadores['precio_apertura_24h'] = $precios[0];
            $indicadores['precio_maximo_24h']   = max($precios);
            $indicadores['precio_minimo_24h']   = min($precios);
        }

        // Senales
        $indicadores['senal_tendencia'] = $this->determinar_tendencia($indicadores);
        $indicadores['senal_fuerza']    = $this->calcular_fuerza_senal($indicadores);
        $indicadores['senal_rsi']       = $this->interpretar_rsi($indicadores['rsi_14']);

        // Analisis de volumen avanzado
        $analisis_volumen = $this->calcular_analisis_volumen($token, $precio_actual, $precios);
        $indicadores['volumen_ratio_promedio']       = $analisis_volumen['volumen_ratio_promedio'];
        $indicadores['volumen_spike']                = $analisis_volumen['volumen_spike'];
        $indicadores['divergencia_precio_volumen']   = $analisis_volumen['divergencia_precio_volumen'];
        $indicadores['volumen_cambio']               = $analisis_volumen['volumen_cambio'];

        // Soporte y resistencia
        $soporte_resistencia = $this->calcular_soportes_resistencias($precios, $precio_actual);
        $indicadores['soporte_principal']               = $soporte_resistencia['soporte_principal'];
        $indicadores['resistencia_principal']            = $soporte_resistencia['resistencia_principal'];
        $indicadores['distancia_soporte_porcentaje']    = $soporte_resistencia['distancia_soporte_porcentaje'];
        $indicadores['distancia_resistencia_porcentaje'] = $soporte_resistencia['distancia_resistencia_porcentaje'];
        $indicadores['zona_precio']                     = $soporte_resistencia['zona_precio'];

        return $indicadores;
    }

    /**
     * Simple Moving Average
     */
    public function calcular_sma($precios, $periodo) {
        if (empty($precios)) {
            return 0;
        }

        if (count($precios) < $periodo) {
            return array_sum($precios) / count($precios);
        }

        $ultimos = array_slice($precios, -$periodo);
        return array_sum($ultimos) / $periodo;
    }

    /**
     * Exponential Moving Average
     */
    public function calcular_ema($precios, $periodo) {
        if (count($precios) < 2) {
            return !empty($precios) ? $precios[0] : 0;
        }

        $multiplicador = 2 / ($periodo + 1);
        $ema = $precios[0];

        for ($i = 1; $i < count($precios); $i++) {
            $ema = ($precios[$i] * $multiplicador) + ($ema * (1 - $multiplicador));
        }

        return $ema;
    }

    /**
     * Relative Strength Index
     */
    public function calcular_rsi($precios, $periodo = 14) {
        if (count($precios) < $periodo + 1) {
            return 50.0;
        }

        $cambios = array();
        for ($i = 1; $i < count($precios); $i++) {
            $cambios[] = $precios[$i] - $precios[$i - 1];
        }

        $cambios_recientes = array_slice($cambios, -$periodo);
        $ganancias = array();
        $perdidas = array();

        foreach ($cambios_recientes as $cambio) {
            $ganancias[] = $cambio > 0 ? $cambio : 0;
            $perdidas[]  = $cambio < 0 ? abs($cambio) : 0;
        }

        $promedio_ganancia = array_sum($ganancias) / $periodo;
        $promedio_perdida  = array_sum($perdidas) / $periodo;

        if ($promedio_perdida == 0) {
            return 100.0;
        }

        $rs  = $promedio_ganancia / $promedio_perdida;
        $rsi = 100 - (100 / (1 + $rs));

        return round($rsi, 2);
    }

    /**
     * Moving Average Convergence Divergence
     */
    public function calcular_macd($precios) {
        $ema_12 = $this->calcular_ema($precios, 12);
        $ema_26 = $this->calcular_ema($precios, 26);

        $macd_line = $ema_12 - $ema_26;
        $signal    = $macd_line * 0.9; // Aproximacion

        return array(
            'line'      => round($macd_line, 6),
            'signal'    => round($signal, 6),
            'histogram' => round($macd_line - $signal, 6),
        );
    }

    /**
     * Bollinger Bands
     */
    public function calcular_bollinger_bands($precios, $periodo = 20, $desviacion_estandar = 2) {
        if (count($precios) < $periodo) {
            $periodo = count($precios);
        }

        if ($periodo < 1) {
            return array('upper' => 0, 'middle' => 0, 'lower' => 0, 'width' => 0);
        }

        $sma = $this->calcular_sma($precios, $periodo);

        $precios_periodo = array_slice($precios, -$periodo);
        $varianza = 0;
        foreach ($precios_periodo as $precio) {
            $varianza += pow($precio - $sma, 2);
        }
        $varianza /= $periodo;
        $desviacion = sqrt($varianza);

        $upper = $sma + ($desviacion_estandar * $desviacion);
        $lower = $sma - ($desviacion_estandar * $desviacion);
        $width = $sma > 0 ? (($upper - $lower) / $sma * 100) : 0;

        return array(
            'upper'  => round($upper, 4),
            'middle' => round($sma, 4),
            'lower'  => round($lower, 4),
            'width'  => round($width, 2),
        );
    }

    /**
     * Momentum
     */
    public function calcular_momentum($precios, $periodo = 10) {
        if (count($precios) <= $periodo) {
            return 0;
        }
        return round($precios[count($precios) - 1] - $precios[count($precios) - $periodo], 4);
    }

    /**
     * Rate of Change
     */
    public function calcular_roc($precios, $periodo = 10) {
        if (count($precios) <= $periodo || $precios[count($precios) - $periodo] == 0) {
            return 0;
        }

        $precio_actual   = $precios[count($precios) - 1];
        $precio_anterior = $precios[count($precios) - $periodo];

        return round((($precio_actual - $precio_anterior) / $precio_anterior) * 100, 2);
    }

    /**
     * Analisis avanzado de volumen: ratio vs promedio, spike, divergencia precio-volumen
     *
     * @param string $token Token
     * @param float  $precio_actual Precio actual
     * @param array  $precios Historial de precios
     * @return array Analisis de volumen
     */
    private function calcular_analisis_volumen($token, $precio_actual, $precios) {
        $volumenes = $this->cargar_historial_volumenes($token);
        $periodo_promedio = 20;

        $resultado = array(
            'volumen_ratio_promedio'     => 1.0,
            'volumen_spike'              => false,
            'divergencia_precio_volumen' => 'ninguna',
            'volumen_cambio'             => 0.0,
        );

        if (count($volumenes) < 2) {
            return $resultado;
        }

        // Ratio volumen actual vs promedio de los ultimos N periodos
        $volumen_actual = end($volumenes);
        $cantidad_para_promedio = min($periodo_promedio, count($volumenes) - 1);
        $volumenes_previos = array_slice($volumenes, -($cantidad_para_promedio + 1), $cantidad_para_promedio);
        $promedio_volumen = count($volumenes_previos) > 0 ? array_sum($volumenes_previos) / count($volumenes_previos) : 0;

        if ($promedio_volumen > 0) {
            $resultado['volumen_ratio_promedio'] = round($volumen_actual / $promedio_volumen, 2);
        }

        // Spike: ratio > 2.0
        $resultado['volumen_spike'] = $resultado['volumen_ratio_promedio'] > 2.0;

        // Cambio de volumen porcentual
        $volumen_anterior = count($volumenes) >= 2 ? $volumenes[count($volumenes) - 2] : 0;
        if ($volumen_anterior > 0) {
            $resultado['volumen_cambio'] = round(
                (($volumen_actual - $volumen_anterior) / $volumen_anterior) * 100,
                2
            );
        }

        // Divergencia precio-volumen (ultimos 5 periodos)
        $ventana_divergencia = 5;
        if (count($precios) >= $ventana_divergencia && count($volumenes) >= $ventana_divergencia) {
            $precios_ventana = array_slice($precios, -$ventana_divergencia);
            $volumenes_ventana = array_slice($volumenes, -$ventana_divergencia);

            $precio_subiendo = end($precios_ventana) > $precios_ventana[0];
            $volumen_subiendo = end($volumenes_ventana) > $volumenes_ventana[0];

            // Precio baja + volumen sube = divergencia alcista (acumulacion)
            if (!$precio_subiendo && $volumen_subiendo) {
                $resultado['divergencia_precio_volumen'] = 'alcista';
            // Precio sube + volumen baja = divergencia bajista (distribucion)
            } elseif ($precio_subiendo && !$volumen_subiendo) {
                $resultado['divergencia_precio_volumen'] = 'bajista';
            }
        }

        return $resultado;
    }

    /**
     * Calcula niveles de soporte y resistencia usando pivotes locales
     *
     * @param array $precios Historial de precios
     * @param float $precio_actual Precio actual
     * @return array Soporte, resistencia y zona
     */
    private function calcular_soportes_resistencias($precios, $precio_actual) {
        $resultado = array(
            'soporte_principal'               => 0,
            'resistencia_principal'            => 0,
            'distancia_soporte_porcentaje'    => 0,
            'distancia_resistencia_porcentaje' => 0,
            'zona_precio'                     => 'medio',
        );

        if (count($precios) < 11) {
            $resultado['soporte_principal'] = count($precios) > 0 ? min($precios) : $precio_actual;
            $resultado['resistencia_principal'] = count($precios) > 0 ? max($precios) : $precio_actual;
            $this->calcular_distancias_zona($resultado, $precio_actual);
            return $resultado;
        }

        $ventana = 5;
        $minimos_locales = array();
        $maximos_locales = array();

        // Encontrar pivotes locales con ventana de 5 puntos
        for ($i = $ventana; $i < count($precios) - $ventana; $i++) {
            $es_minimo_local = true;
            $es_maximo_local = true;

            for ($j = $i - $ventana; $j <= $i + $ventana; $j++) {
                if ($j === $i) {
                    continue;
                }
                if ($precios[$j] <= $precios[$i]) {
                    $es_minimo_local = false;
                }
                if ($precios[$j] >= $precios[$i]) {
                    $es_maximo_local = false;
                }
            }

            if ($es_minimo_local) {
                $minimos_locales[] = $precios[$i];
            }
            if ($es_maximo_local) {
                $maximos_locales[] = $precios[$i];
            }
        }

        // Soporte = maximo de los minimos locales bajo el precio actual
        $soportes_bajo_precio = array_filter($minimos_locales, function ($valor) use ($precio_actual) {
            return $valor < $precio_actual;
        });

        if (!empty($soportes_bajo_precio)) {
            $resultado['soporte_principal'] = max($soportes_bajo_precio);
        } else {
            $resultado['soporte_principal'] = min($precios);
        }

        // Resistencia = minimo de los maximos locales sobre el precio actual
        $resistencias_sobre_precio = array_filter($maximos_locales, function ($valor) use ($precio_actual) {
            return $valor > $precio_actual;
        });

        if (!empty($resistencias_sobre_precio)) {
            $resultado['resistencia_principal'] = min($resistencias_sobre_precio);
        } else {
            $resultado['resistencia_principal'] = max($precios);
        }

        $this->calcular_distancias_zona($resultado, $precio_actual);

        return $resultado;
    }

    /**
     * Calcula distancias porcentuales y zona de precio respecto a soporte/resistencia
     *
     * @param array &$resultado Array de soporte/resistencia (por referencia)
     * @param float  $precio_actual Precio actual
     */
    private function calcular_distancias_zona(&$resultado, $precio_actual) {
        if ($precio_actual > 0 && $resultado['soporte_principal'] > 0) {
            $resultado['distancia_soporte_porcentaje'] = round(
                (($precio_actual - $resultado['soporte_principal']) / $precio_actual) * 100,
                2
            );
        }
        if ($precio_actual > 0 && $resultado['resistencia_principal'] > 0) {
            $resultado['distancia_resistencia_porcentaje'] = round(
                (($resultado['resistencia_principal'] - $precio_actual) / $precio_actual) * 100,
                2
            );
        }

        // Zona: cerca_soporte si <3%, cerca_resistencia si <3%, medio si no
        if ($resultado['distancia_soporte_porcentaje'] < 3.0) {
            $resultado['zona_precio'] = 'cerca_soporte';
        } elseif ($resultado['distancia_resistencia_porcentaje'] < 3.0) {
            $resultado['zona_precio'] = 'cerca_resistencia';
        } else {
            $resultado['zona_precio'] = 'medio';
        }
    }

    /**
     * Determina la tendencia basandose en multiples indicadores
     */
    private function determinar_tendencia($indicadores) {
        $puntos_alcistas = 0;
        $puntos_bajistas = 0;

        if ($indicadores['precio_actual'] > $indicadores['sma_25']) {
            $puntos_alcistas++;
        } else {
            $puntos_bajistas++;
        }

        if ($indicadores['sma_7'] > $indicadores['sma_25']) {
            $puntos_alcistas++;
        } else {
            $puntos_bajistas++;
        }

        if ($indicadores['macd_histogram'] > 0) {
            $puntos_alcistas++;
        } else {
            $puntos_bajistas++;
        }

        if ($indicadores['rsi_14'] > 50) {
            $puntos_alcistas++;
        } else {
            $puntos_bajistas++;
        }

        if ($indicadores['precio_actual'] > $indicadores['bb_middle']) {
            $puntos_alcistas++;
        } else {
            $puntos_bajistas++;
        }

        if ($puntos_alcistas > $puntos_bajistas + 1) {
            return 'alcista';
        } elseif ($puntos_bajistas > $puntos_alcistas + 1) {
            return 'bajista';
        }

        return 'neutral';
    }

    /**
     * Calcula la fuerza de la senal (-100 a +100)
     */
    private function calcular_fuerza_senal($indicadores) {
        $fuerza = 0;

        // RSI contribucion (-30 a +30)
        if ($indicadores['rsi_14'] > 70) {
            $fuerza -= 30;
        } elseif ($indicadores['rsi_14'] < 30) {
            $fuerza += 30;
        } else {
            $fuerza += intval(($indicadores['rsi_14'] - 50) * 0.6);
        }

        // MACD contribucion (-25 a +25)
        if ($indicadores['macd_histogram'] > 0) {
            $fuerza += min(25, intval($indicadores['macd_histogram'] * 1000));
        } else {
            $fuerza += max(-25, intval($indicadores['macd_histogram'] * 1000));
        }

        // Tendencia SMA (-20 a +20)
        if ($indicadores['precio_actual'] > $indicadores['sma_25']) {
            $fuerza += 20;
        } else {
            $fuerza -= 20;
        }

        // Bollinger position (-25 a +25)
        if ($indicadores['bb_upper'] > $indicadores['bb_lower']) {
            $rango = $indicadores['bb_upper'] - $indicadores['bb_lower'];
            $posicion = ($indicadores['precio_actual'] - $indicadores['bb_lower']) / $rango;
            $fuerza += intval(($posicion - 0.5) * -50);
        }

        return max(-100, min(100, $fuerza));
    }

    /**
     * Interpreta el valor RSI
     */
    private function interpretar_rsi($rsi) {
        if ($rsi >= 70) {
            return 'sobrecompra';
        } elseif ($rsi <= 30) {
            return 'sobreventa';
        }
        return 'neutral';
    }

    /**
     * Genera un resumen de indicadores formateado para la IA
     *
     * @param string $token Simbolo del token
     * @param float  $precio Precio actual
     * @return array Resumen formateado
     */
    public function obtener_resumen_para_ia($token, $precio) {
        $indicadores = $this->calcular_indicadores($token, $precio);

        $interpretacion_fuerza = $this->interpretar_fuerza($indicadores['senal_fuerza']);

        return array(
            'precio' => array(
                'actual'     => $indicadores['precio_actual'],
                'vs_sma25'   => $indicadores['sma_25'] > 0
                    ? sprintf('%+.2f%%', (($indicadores['precio_actual'] / $indicadores['sma_25'] - 1) * 100))
                    : 'N/A',
                'rango_24h'  => sprintf(
                    '$%.2f - $%.2f',
                    $indicadores['precio_minimo_24h'],
                    $indicadores['precio_maximo_24h']
                ),
            ),
            'tendencia' => array(
                'direccion'      => $indicadores['senal_tendencia'],
                'fuerza'         => $indicadores['senal_fuerza'],
                'interpretacion' => $interpretacion_fuerza,
            ),
            'rsi' => array(
                'valor' => $indicadores['rsi_14'],
                'senal' => $indicadores['senal_rsi'],
            ),
            'macd' => array(
                'histograma' => $indicadores['macd_histogram'],
                'senal'      => $indicadores['macd_histogram'] > 0 ? 'alcista' : 'bajista',
            ),
            'bollinger' => array(
                'posicion'    => $this->posicion_bollinger($indicadores),
                'volatilidad' => sprintf('%.1f%%', $indicadores['bb_width']),
            ),
            'momentum' => array(
                'valor' => $indicadores['momentum_10'],
                'roc'   => sprintf('%+.2f%%', $indicadores['rate_of_change']),
            ),
            'volumen' => array(
                'ratio_vs_promedio' => $indicadores['volumen_ratio_promedio'],
                'spike'             => $indicadores['volumen_spike'] ? 'SI' : 'NO',
                'divergencia'       => $indicadores['divergencia_precio_volumen'],
                'cambio'            => sprintf('%+.1f%%', $indicadores['volumen_cambio']),
            ),
            'soporte_resistencia' => array(
                'soporte'               => $indicadores['soporte_principal'],
                'resistencia'           => $indicadores['resistencia_principal'],
                'distancia_soporte'     => sprintf('%.2f%%', $indicadores['distancia_soporte_porcentaje']),
                'distancia_resistencia' => sprintf('%.2f%%', $indicadores['distancia_resistencia_porcentaje']),
                'zona'                  => $indicadores['zona_precio'],
            ),
            'recomendacion' => $this->generar_recomendacion($indicadores),
        );
    }

    /**
     * Interpreta la fuerza de la senal
     */
    private function interpretar_fuerza($fuerza) {
        if ($fuerza > 50) {
            return 'Fuerte senal de compra';
        } elseif ($fuerza > 20) {
            return 'Senal moderada de compra';
        } elseif ($fuerza > -20) {
            return 'Sin senal clara';
        } elseif ($fuerza > -50) {
            return 'Senal moderada de venta';
        }
        return 'Fuerte senal de venta';
    }

    /**
     * Determina posicion en Bollinger Bands
     */
    private function posicion_bollinger($indicadores) {
        if ($indicadores['precio_actual'] >= $indicadores['bb_upper']) {
            return 'En banda superior (sobrecompra)';
        } elseif ($indicadores['precio_actual'] <= $indicadores['bb_lower']) {
            return 'En banda inferior (sobreventa)';
        } elseif ($indicadores['precio_actual'] > $indicadores['bb_middle']) {
            return 'Sobre la media';
        }
        return 'Bajo la media';
    }

    /**
     * Genera una recomendacion basada en indicadores
     */
    private function generar_recomendacion($indicadores) {
        if ($indicadores['senal_fuerza'] > 40 && $indicadores['rsi_14'] < 65) {
            return 'COMPRAR - Multiples indicadores positivos';
        } elseif ($indicadores['senal_fuerza'] < -40 && $indicadores['rsi_14'] > 35) {
            return 'VENDER - Multiples indicadores negativos';
        } elseif ($indicadores['rsi_14'] < 25) {
            return 'COMPRAR - Sobreventa extrema';
        } elseif ($indicadores['rsi_14'] > 75) {
            return 'VENDER - Sobrecompra extrema';
        }
        return 'ESPERAR - Sin senal clara';
    }
}

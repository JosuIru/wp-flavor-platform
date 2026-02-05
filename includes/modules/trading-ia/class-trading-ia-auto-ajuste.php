<?php
/**
 * Gestor de Auto-Ajuste de Parametros por la IA
 *
 * @package FlavorChatIA
 */

if (!defined('ABSPATH')) {
    exit;
}

class Flavor_Trading_IA_Auto_Ajuste {

    /**
     * Rangos permitidos para cada parametro
     */
    const RANGOS_PARAMETROS = array(
        'intervalo_analisis'       => array('min' => 30,  'max' => 300,  'cambio_max' => 30),
        'agresividad'              => array('min' => 1,   'max' => 10,   'cambio_max' => 1),
        'confianza_minima_trade'   => array('min' => 40,  'max' => 80,   'cambio_max' => 5),
        'riesgo_maximo_porcentaje' => array('min' => 1.0, 'max' => 15.0, 'cambio_max' => 1.0),
        'stop_loss_porcentaje'     => array('min' => 1.0, 'max' => 10.0, 'cambio_max' => 1.0),
        'take_profit_porcentaje'   => array('min' => 2.0, 'max' => 20.0, 'cambio_max' => 2.0),
    );

    /**
     * Mapeo de nombres cortos a nombres de configuracion
     */
    const MAPEO_PARAMETROS = array(
        'intervalo_analisis' => 'intervalo_analisis',
        'agresividad'        => 'agresividad',
        'confianza_minima'   => 'confianza_minima_trade',
        'riesgo_maximo'      => 'riesgo_maximo_porcentaje',
        'stop_loss'          => 'stop_loss_porcentaje',
        'take_profit'        => 'take_profit_porcentaje',
    );

    /**
     * Intervalo minimo entre ajustes del mismo parametro (segundos)
     */
    const INTERVALO_MINIMO = 300;

    /**
     * Referencia al modulo principal para acceder a settings
     */
    private $modulo;

    /**
     * ID del usuario
     */
    private $usuario_id;

    /**
     * Constructor
     *
     * @param object $modulo Modulo principal
     * @param int    $usuario_id ID del usuario
     */
    public function __construct($modulo, $usuario_id) {
        $this->modulo     = $modulo;
        $this->usuario_id = $usuario_id;
    }

    /**
     * Aplica ajustes sugeridos por la IA de forma segura
     *
     * @param array $ajustes_sugeridos Ajustes del JSON de la IA
     * @return array Resultado de los ajustes
     */
    public function aplicar_ajuste_seguro($ajustes_sugeridos) {
        if (empty($ajustes_sugeridos)) {
            return array('aplicado' => false, 'razon' => 'Sin ajustes sugeridos');
        }

        $razon_ajuste      = isset($ajustes_sugeridos['razon_ajuste']) ? $ajustes_sugeridos['razon_ajuste'] : '';
        $resultados        = array();
        $cambios_aplicados = 0;

        foreach (self::MAPEO_PARAMETROS as $nombre_corto => $nombre_config) {
            if (!isset($ajustes_sugeridos[$nombre_corto]) || null === $ajustes_sugeridos[$nombre_corto]) {
                continue;
            }

            $valor_sugerido = floatval($ajustes_sugeridos[$nombre_corto]);

            // Verificar cooldown
            if (!$this->puede_ajustar_parametro($nombre_config)) {
                $resultados[$nombre_corto] = array(
                    'aplicado' => false,
                    'razon'    => __('Parametro en cooldown', 'flavor-chat-ia'),
                );
                continue;
            }

            // Validar rango
            $valor_actual  = $this->obtener_valor_actual($nombre_config);
            $valor_ajustado = $this->validar_rango($nombre_config, $valor_sugerido, $valor_actual);

            // No aplicar si no hay cambio
            if ($valor_ajustado == $valor_actual) {
                $resultados[$nombre_corto] = array(
                    'aplicado' => false,
                    'razon'    => __('Sin cambio efectivo', 'flavor-chat-ia'),
                );
                continue;
            }

            // Aplicar cambio
            $this->modulo->update_setting($nombre_config, $valor_ajustado);
            $this->registrar_cooldown($nombre_config);
            $this->registrar_ajuste($nombre_config, $valor_actual, $valor_ajustado, $razon_ajuste);

            $resultados[$nombre_corto] = array(
                'aplicado' => true,
                'anterior' => $valor_actual,
                'nuevo'    => $valor_ajustado,
                'razon'    => $razon_ajuste,
            );
            $cambios_aplicados++;
        }

        return array(
            'aplicado'         => $cambios_aplicados > 0,
            'cantidad_cambios' => $cambios_aplicados,
            'detalles'         => $resultados,
        );
    }

    /**
     * Valida que un valor este dentro del rango permitido
     *
     * @param string $nombre_config Nombre del parametro
     * @param float  $valor_nuevo Valor sugerido
     * @param float  $valor_actual Valor actual
     * @return float Valor ajustado dentro del rango
     */
    private function validar_rango($nombre_config, $valor_nuevo, $valor_actual) {
        if (!isset(self::RANGOS_PARAMETROS[$nombre_config])) {
            return $valor_actual;
        }

        $rango = self::RANGOS_PARAMETROS[$nombre_config];

        // Limitar el cambio maximo
        $diferencia  = abs($valor_nuevo - $valor_actual);
        $cambio_max  = $rango['cambio_max'];

        if ($diferencia > $cambio_max) {
            $valor_nuevo = $valor_nuevo > $valor_actual
                ? $valor_actual + $cambio_max
                : $valor_actual - $cambio_max;
        }

        // Clamp al rango permitido
        $valor_nuevo = max($rango['min'], min($rango['max'], $valor_nuevo));

        // Redondear parametros enteros
        $parametros_enteros = array('intervalo_analisis', 'agresividad', 'confianza_minima_trade');
        if (in_array($nombre_config, $parametros_enteros, true)) {
            $valor_nuevo = intval(round($valor_nuevo));
        }

        return $valor_nuevo;
    }

    /**
     * Verifica si ha pasado suficiente tiempo desde el ultimo ajuste
     *
     * @param string $nombre_config Nombre del parametro
     * @return bool Puede ajustarse
     */
    private function puede_ajustar_parametro($nombre_config) {
        $clave = 'flavor_trading_ia_cooldown_' . $this->usuario_id . '_' . $nombre_config;
        return false === get_transient($clave);
    }

    /**
     * Registra el cooldown de un parametro
     *
     * @param string $nombre_config Nombre del parametro
     */
    private function registrar_cooldown($nombre_config) {
        $clave = 'flavor_trading_ia_cooldown_' . $this->usuario_id . '_' . $nombre_config;
        set_transient($clave, time(), self::INTERVALO_MINIMO);
    }

    /**
     * Obtiene el valor actual de un parametro del modulo
     *
     * @param string $nombre_config Nombre del parametro
     * @return mixed Valor actual
     */
    private function obtener_valor_actual($nombre_config) {
        return $this->modulo->get_setting($nombre_config);
    }

    /**
     * Registra un ajuste en la base de datos
     *
     * @param string $parametro Nombre del parametro
     * @param float  $valor_anterior Valor anterior
     * @param float  $valor_nuevo Valor nuevo
     * @param string $razon Razon del ajuste
     */
    private function registrar_ajuste($parametro, $valor_anterior, $valor_nuevo, $razon) {
        global $wpdb;
        $tabla = $wpdb->prefix . 'flavor_trading_ia_ajustes';

        $wpdb->insert($tabla, array(
            'usuario_id'     => $this->usuario_id,
            'timestamp'      => current_time('mysql'),
            'parametro'      => $parametro,
            'valor_anterior' => $valor_anterior,
            'valor_nuevo'    => $valor_nuevo,
            'razon'          => $razon,
        ));
    }

    /**
     * Obtiene el historial de ajustes
     *
     * @param int $limite Numero maximo de registros
     * @return array Historial
     */
    public function obtener_historial($limite = 50) {
        global $wpdb;
        $tabla = $wpdb->prefix . 'flavor_trading_ia_ajustes';

        $resultados = $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM $tabla WHERE usuario_id = %d ORDER BY timestamp DESC LIMIT %d",
            $this->usuario_id,
            $limite
        ), ARRAY_A);

        return $resultados ?: array();
    }

    /**
     * Obtiene el estado completo del gestor de auto-ajuste
     *
     * @return array Estado
     */
    public function obtener_estado() {
        $parametros_actuales = array();

        foreach (self::MAPEO_PARAMETROS as $nombre_corto => $nombre_config) {
            $valor_actual = $this->obtener_valor_actual($nombre_config);
            $rango        = isset(self::RANGOS_PARAMETROS[$nombre_config])
                ? self::RANGOS_PARAMETROS[$nombre_config]
                : array();

            $parametros_actuales[$nombre_corto] = array(
                'valor_actual'  => $valor_actual,
                'rango_min'     => isset($rango['min']) ? $rango['min'] : null,
                'rango_max'     => isset($rango['max']) ? $rango['max'] : null,
                'cambio_max'    => isset($rango['cambio_max']) ? $rango['cambio_max'] : null,
                'puede_ajustar' => $this->puede_ajustar_parametro($nombre_config),
            );
        }

        return array(
            'habilitado'          => (bool) $this->modulo->get_setting('auto_ajuste_enabled'),
            'total_ajustes'       => count($this->obtener_historial(1000)),
            'parametros_actuales' => $parametros_actuales,
            'ultimos_ajustes'     => $this->obtener_historial(10),
        );
    }
}

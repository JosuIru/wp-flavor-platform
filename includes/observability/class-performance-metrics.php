<?php
/**
 * Métricas de rendimiento para observabilidad
 *
 * Registra tiempos de ejecución de operaciones críticas
 * para detectar degradaciones de rendimiento.
 *
 * @package FlavorPlatform
 * @subpackage Observability
 * @since 3.4.0
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Clase para recopilar métricas de rendimiento
 */
class Flavor_Performance_Metrics {

    /**
     * Instancia singleton
     *
     * @var self|null
     */
    private static $instance = null;

    /**
     * Métricas del request actual
     *
     * @var array
     */
    private $current_request_metrics = [];

    /**
     * Timers activos
     *
     * @var array
     */
    private $timers = [];

    /**
     * Opción para almacenar métricas agregadas
     */
    private const METRICS_OPTION = 'flavor_performance_metrics';

    /**
     * Máximo de muestras a almacenar por operación
     */
    private const MAX_SAMPLES = 100;

    /**
     * TTL de métricas en segundos (24 horas)
     */
    private const METRICS_TTL = 86400;

    /**
     * Obtiene la instancia singleton
     *
     * @return self
     */
    public static function get_instance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Constructor privado
     */
    private function __construct() {
        // Registrar métricas al final del request
        add_action('shutdown', [$this, 'flush_metrics'], 999);
    }

    /**
     * Inicia un timer para una operación
     *
     * @param string $operation Nombre de la operación.
     * @return void
     */
    public function start_timer($operation) {
        $this->timers[$operation] = [
            'start' => microtime(true),
            'memory_start' => memory_get_usage(true),
        ];
    }

    /**
     * Detiene un timer y registra la métrica
     *
     * @param string $operation Nombre de la operación.
     * @param array  $tags      Tags adicionales (ej: ['module' => 'eventos']).
     * @return float|null Duración en ms o null si no había timer.
     */
    public function stop_timer($operation, array $tags = []) {
        if (!isset($this->timers[$operation])) {
            return null;
        }

        $timer = $this->timers[$operation];
        $duration_ms = (microtime(true) - $timer['start']) * 1000;
        $memory_used = memory_get_usage(true) - $timer['memory_start'];

        unset($this->timers[$operation]);

        $this->record_metric($operation, [
            'duration_ms' => round($duration_ms, 2),
            'memory_bytes' => $memory_used,
            'tags' => $tags,
            'timestamp' => time(),
        ]);

        return $duration_ms;
    }

    /**
     * Registra una métrica directamente
     *
     * @param string $operation Nombre de la operación.
     * @param array  $data      Datos de la métrica.
     * @return void
     */
    public function record_metric($operation, array $data) {
        if (!isset($this->current_request_metrics[$operation])) {
            $this->current_request_metrics[$operation] = [];
        }
        $this->current_request_metrics[$operation][] = $data;
    }

    /**
     * Registra un contador
     *
     * @param string $name  Nombre del contador.
     * @param int    $value Valor a incrementar.
     * @param array  $tags  Tags adicionales.
     * @return void
     */
    public function increment_counter($name, $value = 1, array $tags = []) {
        $this->record_metric('counter_' . $name, [
            'value' => $value,
            'tags' => $tags,
            'timestamp' => time(),
        ]);
    }

    /**
     * Mide el tiempo de una callable
     *
     * @param string   $operation Nombre de la operación.
     * @param callable $callback  Función a medir.
     * @param array    $tags      Tags adicionales.
     * @return mixed Resultado del callback.
     */
    public function measure($operation, callable $callback, array $tags = []) {
        $this->start_timer($operation);
        try {
            $result = $callback();
            return $result;
        } finally {
            $this->stop_timer($operation, $tags);
        }
    }

    /**
     * Guarda métricas agregadas al final del request
     *
     * @return void
     */
    public function flush_metrics() {
        if (empty($this->current_request_metrics)) {
            return;
        }

        // Solo guardar si hay métricas significativas
        $metrics = get_option(self::METRICS_OPTION, []);
        $now = time();

        // Limpiar métricas viejas
        foreach ($metrics as $operation => $samples) {
            $metrics[$operation] = array_filter($samples, function ($sample) use ($now) {
                return ($now - ($sample['timestamp'] ?? 0)) < self::METRICS_TTL;
            });
        }

        // Añadir nuevas métricas
        foreach ($this->current_request_metrics as $operation => $samples) {
            if (!isset($metrics[$operation])) {
                $metrics[$operation] = [];
            }

            $metrics[$operation] = array_merge($metrics[$operation], $samples);

            // Limitar muestras
            if (count($metrics[$operation]) > self::MAX_SAMPLES) {
                $metrics[$operation] = array_slice($metrics[$operation], -self::MAX_SAMPLES);
            }
        }

        update_option(self::METRICS_OPTION, $metrics, false);
        $this->current_request_metrics = [];
    }

    /**
     * Obtiene estadísticas agregadas de una operación
     *
     * @param string $operation Nombre de la operación.
     * @return array Estadísticas (min, max, avg, p95, count).
     */
    public function get_stats($operation) {
        $metrics = get_option(self::METRICS_OPTION, []);
        $samples = $metrics[$operation] ?? [];

        if (empty($samples)) {
            return [
                'count' => 0,
                'min_ms' => 0,
                'max_ms' => 0,
                'avg_ms' => 0,
                'p95_ms' => 0,
            ];
        }

        $durations = array_filter(array_column($samples, 'duration_ms'));
        if (empty($durations)) {
            return [
                'count' => count($samples),
                'min_ms' => 0,
                'max_ms' => 0,
                'avg_ms' => 0,
                'p95_ms' => 0,
            ];
        }

        sort($durations);
        $count = count($durations);
        $p95_index = (int) ceil($count * 0.95) - 1;

        return [
            'count' => $count,
            'min_ms' => round(min($durations), 2),
            'max_ms' => round(max($durations), 2),
            'avg_ms' => round(array_sum($durations) / $count, 2),
            'p95_ms' => round($durations[$p95_index] ?? end($durations), 2),
        ];
    }

    /**
     * Obtiene todas las estadísticas
     *
     * @return array Estadísticas por operación.
     */
    public function get_all_stats() {
        $metrics = get_option(self::METRICS_OPTION, []);
        $stats = [];

        foreach (array_keys($metrics) as $operation) {
            $stats[$operation] = $this->get_stats($operation);
        }

        return $stats;
    }

    /**
     * Limpia todas las métricas
     *
     * @return void
     */
    public function clear_metrics() {
        delete_option(self::METRICS_OPTION);
        $this->current_request_metrics = [];
    }

    /**
     * Verifica si hay degradación de rendimiento
     *
     * @param string $operation    Operación a verificar.
     * @param float  $threshold_ms Umbral en ms para considerar degradación.
     * @return bool True si el p95 supera el umbral.
     */
    public function is_degraded($operation, $threshold_ms) {
        $stats = $this->get_stats($operation);
        return $stats['p95_ms'] > $threshold_ms;
    }
}

/**
 * Helper global para métricas
 *
 * @return Flavor_Performance_Metrics
 */
function flavor_metrics() {
    return Flavor_Performance_Metrics::get_instance();
}

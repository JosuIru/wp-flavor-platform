<?php
/**
 * Vector Clock para ordenamiento causal
 *
 * Implementa un vector clock para determinar causalidad entre eventos
 * en sistemas distribuidos. Permite:
 * - Detectar relaciones happens-before
 * - Identificar eventos concurrentes
 * - Merge de estados divergentes
 *
 * @package FlavorPlatform\Network\CRDT
 * @since 1.5.0
 */

if (!defined('ABSPATH')) {
    exit;
}

class Flavor_Vector_Clock {

    /**
     * Datos del vector clock: peer_id => version
     *
     * @var array
     */
    private $clock_data = [];

    /**
     * Constructor
     *
     * @param array $initial_data Datos iniciales del clock
     */
    public function __construct(array $initial_data = []) {
        foreach ($initial_data as $peer_id => $version) {
            if (is_string($peer_id) && is_int($version) && $version >= 0) {
                $this->clock_data[$peer_id] = $version;
            }
        }
    }

    /**
     * Crea un Vector Clock desde JSON
     *
     * @param string $json JSON del clock
     * @return self
     */
    public static function from_json($json) {
        $data = json_decode($json, true);
        if (!is_array($data)) {
            $data = [];
        }
        return new self($data);
    }

    /**
     * Crea un Vector Clock desde un array
     *
     * @param array $data Datos del clock
     * @return self
     */
    public static function from_array(array $data) {
        return new self($data);
    }

    /**
     * Incrementa el contador para un peer
     *
     * @param string $peer_id ID del peer
     * @return self Para encadenamiento
     */
    public function increment($peer_id) {
        if (!isset($this->clock_data[$peer_id])) {
            $this->clock_data[$peer_id] = 0;
        }
        $this->clock_data[$peer_id]++;
        return $this;
    }

    /**
     * Obtiene la versión de un peer específico
     *
     * @param string $peer_id ID del peer
     * @return int Versión actual (0 si no existe)
     */
    public function get($peer_id) {
        return $this->clock_data[$peer_id] ?? 0;
    }

    /**
     * Establece la versión de un peer
     *
     * @param string $peer_id ID del peer
     * @param int $version Versión a establecer
     * @return self
     */
    public function set($peer_id, $version) {
        if ($version >= 0) {
            $this->clock_data[$peer_id] = (int) $version;
        }
        return $this;
    }

    /**
     * Merge con otro vector clock (max de cada componente)
     *
     * @param Flavor_Vector_Clock $other Otro vector clock
     * @return self Nuevo clock con el merge
     */
    public function merge(Flavor_Vector_Clock $other) {
        $merged_data = $this->clock_data;
        $other_data = $other->to_array();

        foreach ($other_data as $peer_id => $version) {
            if (!isset($merged_data[$peer_id])) {
                $merged_data[$peer_id] = $version;
            } else {
                $merged_data[$peer_id] = max($merged_data[$peer_id], $version);
            }
        }

        return new self($merged_data);
    }

    /**
     * Compara dos vector clocks
     *
     * @param Flavor_Vector_Clock $other Otro vector clock
     * @return string 'before' | 'after' | 'concurrent' | 'equal'
     */
    public function compare(Flavor_Vector_Clock $other) {
        $this_data = $this->clock_data;
        $other_data = $other->to_array();

        // Obtener todos los peers
        $all_peers = array_unique(array_merge(
            array_keys($this_data),
            array_keys($other_data)
        ));

        $this_greater = false;
        $other_greater = false;

        foreach ($all_peers as $peer_id) {
            $this_version = $this_data[$peer_id] ?? 0;
            $other_version = $other_data[$peer_id] ?? 0;

            if ($this_version > $other_version) {
                $this_greater = true;
            }
            if ($other_version > $this_version) {
                $other_greater = true;
            }
        }

        if ($this_greater && $other_greater) {
            return 'concurrent';
        }
        if ($this_greater) {
            return 'after';
        }
        if ($other_greater) {
            return 'before';
        }
        return 'equal';
    }

    /**
     * Verifica si este clock "happens before" otro
     *
     * @param Flavor_Vector_Clock $other Otro vector clock
     * @return bool True si this < other
     */
    public function happens_before(Flavor_Vector_Clock $other) {
        return $this->compare($other) === 'before';
    }

    /**
     * Verifica si son concurrentes (ni before ni after)
     *
     * @param Flavor_Vector_Clock $other Otro vector clock
     * @return bool True si son concurrentes
     */
    public function is_concurrent(Flavor_Vector_Clock $other) {
        return $this->compare($other) === 'concurrent';
    }

    /**
     * Obtiene la suma total de versiones (para ordenamiento rápido)
     *
     * @return int Suma de todas las versiones
     */
    public function sum() {
        return array_sum($this->clock_data);
    }

    /**
     * Obtiene la versión máxima entre todos los peers
     *
     * @return int Versión máxima
     */
    public function max_version() {
        if (empty($this->clock_data)) {
            return 0;
        }
        return max($this->clock_data);
    }

    /**
     * Obtiene el número de peers en el clock
     *
     * @return int Número de peers
     */
    public function count() {
        return count($this->clock_data);
    }

    /**
     * Verifica si el clock está vacío
     *
     * @return bool
     */
    public function is_empty() {
        return empty($this->clock_data);
    }

    /**
     * Obtiene todos los peer IDs en el clock
     *
     * @return array Lista de peer IDs
     */
    public function get_peers() {
        return array_keys($this->clock_data);
    }

    /**
     * Convierte a array
     *
     * @return array
     */
    public function to_array() {
        return $this->clock_data;
    }

    /**
     * Convierte a JSON
     *
     * @return string
     */
    public function to_json() {
        return wp_json_encode($this->clock_data);
    }

    /**
     * Clona el vector clock
     *
     * @return self
     */
    public function clone() {
        return new self($this->clock_data);
    }

    /**
     * Representación como string para debug
     *
     * @return string
     */
    public function __toString() {
        $parts = [];
        foreach ($this->clock_data as $peer_id => $version) {
            $short_id = substr($peer_id, 0, 8);
            $parts[] = "{$short_id}:{$version}";
        }
        return '{' . implode(', ', $parts) . '}';
    }

    /**
     * Calcula la distancia entre dos vector clocks
     * Útil para estimar cuánto de desactualizados están
     *
     * @param Flavor_Vector_Clock $other Otro vector clock
     * @return int Suma de diferencias absolutas
     */
    public function distance(Flavor_Vector_Clock $other) {
        $all_peers = array_unique(array_merge(
            array_keys($this->clock_data),
            array_keys($other->to_array())
        ));

        $distance = 0;
        foreach ($all_peers as $peer_id) {
            $this_version = $this->clock_data[$peer_id] ?? 0;
            $other_version = $other->get($peer_id);
            $distance += abs($this_version - $other_version);
        }

        return $distance;
    }

    /**
     * Comprueba qué peers tienen actualizaciones que no tenemos
     *
     * @param Flavor_Vector_Clock $other Otro vector clock (normalmente del remoto)
     * @return array peer_id => versions_behind
     */
    public function missing_from(Flavor_Vector_Clock $other) {
        $missing = [];
        $other_data = $other->to_array();

        foreach ($other_data as $peer_id => $version) {
            $our_version = $this->clock_data[$peer_id] ?? 0;
            if ($version > $our_version) {
                $missing[$peer_id] = $version - $our_version;
            }
        }

        return $missing;
    }
}

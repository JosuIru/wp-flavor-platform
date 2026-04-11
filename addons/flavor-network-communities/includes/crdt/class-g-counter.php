<?php
/**
 * G-Counter (Grow-only Counter) CRDT
 *
 * Un contador que solo puede incrementarse. Cada peer mantiene
 * su propio contador y el valor total es la suma de todos.
 *
 * Ideal para: vistas, likes, descargas, etc.
 *
 * @package FlavorPlatform\Network\CRDT
 * @since 1.5.0
 */

if (!defined('ABSPATH')) {
    exit;
}

class Flavor_G_Counter {

    /**
     * Contadores por peer: peer_id => count
     *
     * @var array
     */
    private $counts = [];

    /**
     * Constructor
     *
     * @param array $counts Contadores iniciales por peer
     */
    public function __construct(array $counts = []) {
        foreach ($counts as $peer_id => $count) {
            if (is_numeric($count) && $count >= 0) {
                $this->counts[$peer_id] = (int) $count;
            }
        }
    }

    /**
     * Crea desde JSON
     *
     * @param string $json
     * @return self
     */
    public static function from_json($json) {
        $data = json_decode($json, true);
        if (!is_array($data)) {
            return new self();
        }
        // Compatibilidad con formato simple {counts: {...}}
        if (isset($data['counts'])) {
            return new self($data['counts']);
        }
        return new self($data);
    }

    /**
     * Crea desde array
     *
     * @param array $data
     * @return self
     */
    public static function from_array(array $data) {
        if (isset($data['counts'])) {
            return new self($data['counts']);
        }
        return new self($data);
    }

    /**
     * Incrementa el contador para un peer
     *
     * @param string $peer_id ID del peer
     * @param int $amount Cantidad a incrementar (default 1)
     * @return self
     */
    public function increment($peer_id, $amount = 1) {
        if ($amount < 0) {
            // G-Counter solo puede incrementar
            return $this;
        }

        if (!isset($this->counts[$peer_id])) {
            $this->counts[$peer_id] = 0;
        }

        $this->counts[$peer_id] += (int) $amount;
        return $this;
    }

    /**
     * Obtiene el valor total (suma de todos los peers)
     *
     * @return int
     */
    public function value() {
        return array_sum($this->counts);
    }

    /**
     * Obtiene el contador de un peer específico
     *
     * @param string $peer_id
     * @return int
     */
    public function get($peer_id) {
        return $this->counts[$peer_id] ?? 0;
    }

    /**
     * Merge con otro G-Counter (max de cada componente)
     *
     * @param Flavor_G_Counter $other
     * @return self
     */
    public function merge(Flavor_G_Counter $other) {
        $merged_counts = $this->counts;
        $other_counts = $other->to_array()['counts'];

        foreach ($other_counts as $peer_id => $count) {
            if (!isset($merged_counts[$peer_id])) {
                $merged_counts[$peer_id] = $count;
            } else {
                $merged_counts[$peer_id] = max($merged_counts[$peer_id], $count);
            }
        }

        return new self($merged_counts);
    }

    /**
     * Compara con otro contador
     *
     * @param Flavor_G_Counter $other
     * @return int -1, 0, 1
     */
    public function compare(Flavor_G_Counter $other) {
        $this_value = $this->value();
        $other_value = $other->value();

        if ($this_value < $other_value) {
            return -1;
        }
        if ($this_value > $other_value) {
            return 1;
        }
        return 0;
    }

    /**
     * Verifica si está vacío
     *
     * @return bool
     */
    public function is_empty() {
        return $this->value() === 0;
    }

    /**
     * Obtiene todos los peers que han contribuido
     *
     * @return array
     */
    public function get_peers() {
        return array_keys($this->counts);
    }

    /**
     * Número de peers que han contribuido
     *
     * @return int
     */
    public function contributors_count() {
        return count(array_filter($this->counts, function ($count) {
            return $count > 0;
        }));
    }

    /**
     * Convierte a array
     *
     * @return array
     */
    public function to_array() {
        return [
            'counts' => $this->counts,
        ];
    }

    /**
     * Convierte a JSON
     *
     * @return string
     */
    public function to_json() {
        return wp_json_encode($this->to_array());
    }

    /**
     * Clona el contador
     *
     * @return self
     */
    public function clone() {
        return new self($this->counts);
    }

    /**
     * Representación como string
     *
     * @return string
     */
    public function __toString() {
        return "GCounter({$this->value()})";
    }
}

<?php
/**
 * PN-Counter (Positive-Negative Counter) CRDT
 *
 * Un contador que puede incrementarse y decrementarse.
 * Implementado como dos G-Counters: uno para incrementos (P)
 * y otro para decrementos (N). El valor es P - N.
 *
 * Ideal para: votos +/-, stock, saldo, etc.
 *
 * @package FlavorPlatform\Network\CRDT
 * @since 1.5.0
 */

if (!defined('ABSPATH')) {
    exit;
}

// Asegurar que G-Counter está cargado
if (!class_exists('Flavor_G_Counter')) {
    require_once __DIR__ . '/class-g-counter.php';
}

class Flavor_PN_Counter {

    /**
     * Contador de incrementos (P)
     *
     * @var Flavor_G_Counter
     */
    private $positive;

    /**
     * Contador de decrementos (N)
     *
     * @var Flavor_G_Counter
     */
    private $negative;

    /**
     * Constructor
     *
     * @param Flavor_G_Counter|null $positive Contador positivo
     * @param Flavor_G_Counter|null $negative Contador negativo
     */
    public function __construct($positive = null, $negative = null) {
        $this->positive = $positive ?? new Flavor_G_Counter();
        $this->negative = $negative ?? new Flavor_G_Counter();
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

        $positive = isset($data['positive'])
            ? Flavor_G_Counter::from_array($data['positive'])
            : new Flavor_G_Counter();

        $negative = isset($data['negative'])
            ? Flavor_G_Counter::from_array($data['negative'])
            : new Flavor_G_Counter();

        return new self($positive, $negative);
    }

    /**
     * Crea desde array
     *
     * @param array $data
     * @return self
     */
    public static function from_array(array $data) {
        $positive = isset($data['positive'])
            ? Flavor_G_Counter::from_array($data['positive'])
            : new Flavor_G_Counter();

        $negative = isset($data['negative'])
            ? Flavor_G_Counter::from_array($data['negative'])
            : new Flavor_G_Counter();

        return new self($positive, $negative);
    }

    /**
     * Incrementa el contador
     *
     * @param string $peer_id ID del peer
     * @param int $amount Cantidad a incrementar (default 1)
     * @return self
     */
    public function increment($peer_id, $amount = 1) {
        if ($amount < 0) {
            // Convertir incremento negativo a decremento
            return $this->decrement($peer_id, abs($amount));
        }
        $this->positive->increment($peer_id, $amount);
        return $this;
    }

    /**
     * Decrementa el contador
     *
     * @param string $peer_id ID del peer
     * @param int $amount Cantidad a decrementar (default 1)
     * @return self
     */
    public function decrement($peer_id, $amount = 1) {
        if ($amount < 0) {
            // Convertir decremento negativo a incremento
            return $this->increment($peer_id, abs($amount));
        }
        $this->negative->increment($peer_id, $amount);
        return $this;
    }

    /**
     * Obtiene el valor actual (P - N)
     *
     * @return int
     */
    public function value() {
        return $this->positive->value() - $this->negative->value();
    }

    /**
     * Obtiene el valor positivo total (sin restar negativos)
     *
     * @return int
     */
    public function positive_value() {
        return $this->positive->value();
    }

    /**
     * Obtiene el valor negativo total
     *
     * @return int
     */
    public function negative_value() {
        return $this->negative->value();
    }

    /**
     * Obtiene el balance de un peer específico
     *
     * @param string $peer_id
     * @return int
     */
    public function get_peer_balance($peer_id) {
        return $this->positive->get($peer_id) - $this->negative->get($peer_id);
    }

    /**
     * Merge con otro PN-Counter
     *
     * @param Flavor_PN_Counter $other
     * @return self
     */
    public function merge(Flavor_PN_Counter $other) {
        $merged_positive = $this->positive->merge($other->positive);
        $merged_negative = $this->negative->merge($other->negative);
        return new self($merged_positive, $merged_negative);
    }

    /**
     * Compara con otro contador
     *
     * @param Flavor_PN_Counter $other
     * @return int -1, 0, 1
     */
    public function compare(Flavor_PN_Counter $other) {
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
     * Verifica si es positivo
     *
     * @return bool
     */
    public function is_positive() {
        return $this->value() > 0;
    }

    /**
     * Verifica si es negativo
     *
     * @return bool
     */
    public function is_negative() {
        return $this->value() < 0;
    }

    /**
     * Verifica si es cero
     *
     * @return bool
     */
    public function is_zero() {
        return $this->value() === 0;
    }

    /**
     * Obtiene estadísticas del contador
     *
     * @return array
     */
    public function stats() {
        return [
            'value'        => $this->value(),
            'positive'     => $this->positive->value(),
            'negative'     => $this->negative->value(),
            'contributors' => count(array_unique(array_merge(
                $this->positive->get_peers(),
                $this->negative->get_peers()
            ))),
        ];
    }

    /**
     * Convierte a array
     *
     * @return array
     */
    public function to_array() {
        return [
            'positive' => $this->positive->to_array(),
            'negative' => $this->negative->to_array(),
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
        return new self(
            $this->positive->clone(),
            $this->negative->clone()
        );
    }

    /**
     * Representación como string
     *
     * @return string
     */
    public function __toString() {
        $value = $this->value();
        $sign = $value >= 0 ? '+' : '';
        return "PNCounter({$sign}{$value})";
    }
}

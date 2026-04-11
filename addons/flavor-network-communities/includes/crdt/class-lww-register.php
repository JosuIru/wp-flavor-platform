<?php
/**
 * LWW-Register (Last-Writer-Wins Register) CRDT
 *
 * Un registro donde el último escritor gana basándose en timestamp.
 * Ideal para campos simples como título, descripción, etc.
 *
 * @package FlavorPlatform\Network\CRDT
 * @since 1.5.0
 */

if (!defined('ABSPATH')) {
    exit;
}

class Flavor_LWW_Register {

    /**
     * Valor actual del registro
     *
     * @var mixed
     */
    private $value;

    /**
     * Timestamp de la última escritura (microsegundos)
     *
     * @var int
     */
    private $timestamp;

    /**
     * ID del peer que escribió el valor
     *
     * @var string
     */
    private $peer_id;

    /**
     * Constructor
     *
     * @param mixed $value Valor inicial
     * @param int|null $timestamp Timestamp (null = ahora)
     * @param string $peer_id ID del peer que escribe
     */
    public function __construct($value = null, $timestamp = null, $peer_id = '') {
        $this->value = $value;
        $this->timestamp = $timestamp ?? $this->generate_timestamp();
        $this->peer_id = $peer_id;
    }

    /**
     * Genera un timestamp con microsegundos para mayor precisión
     *
     * @return int
     */
    private function generate_timestamp() {
        return (int) (microtime(true) * 1000000);
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
        return new self(
            $data['value'] ?? null,
            $data['timestamp'] ?? null,
            $data['peer_id'] ?? ''
        );
    }

    /**
     * Crea desde array
     *
     * @param array $data
     * @return self
     */
    public static function from_array(array $data) {
        return new self(
            $data['value'] ?? null,
            $data['timestamp'] ?? null,
            $data['peer_id'] ?? ''
        );
    }

    /**
     * Obtiene el valor actual
     *
     * @return mixed
     */
    public function get() {
        return $this->value;
    }

    /**
     * Establece un nuevo valor
     *
     * @param mixed $value Nuevo valor
     * @param string $peer_id ID del peer que escribe
     * @return self
     */
    public function set($value, $peer_id) {
        $this->value = $value;
        $this->timestamp = $this->generate_timestamp();
        $this->peer_id = $peer_id;
        return $this;
    }

    /**
     * Obtiene el timestamp de la última escritura
     *
     * @return int
     */
    public function get_timestamp() {
        return $this->timestamp;
    }

    /**
     * Obtiene el peer_id del último escritor
     *
     * @return string
     */
    public function get_peer_id() {
        return $this->peer_id;
    }

    /**
     * Merge con otro LWW-Register (gana el más reciente)
     *
     * @param Flavor_LWW_Register $other Otro registro
     * @return self Nuevo registro con el merge
     */
    public function merge(Flavor_LWW_Register $other) {
        // El timestamp más alto gana
        if ($other->timestamp > $this->timestamp) {
            return new self($other->value, $other->timestamp, $other->peer_id);
        }

        // Si son iguales, desempate por peer_id (orden lexicográfico)
        if ($other->timestamp === $this->timestamp && $other->peer_id > $this->peer_id) {
            return new self($other->value, $other->timestamp, $other->peer_id);
        }

        return new self($this->value, $this->timestamp, $this->peer_id);
    }

    /**
     * Compara con otro registro
     *
     * @param Flavor_LWW_Register $other
     * @return int -1 si this < other, 0 si igual, 1 si this > other
     */
    public function compare(Flavor_LWW_Register $other) {
        if ($this->timestamp < $other->timestamp) {
            return -1;
        }
        if ($this->timestamp > $other->timestamp) {
            return 1;
        }
        // Desempate por peer_id
        return strcmp($this->peer_id, $other->peer_id);
    }

    /**
     * Verifica si este registro es más reciente
     *
     * @param Flavor_LWW_Register $other
     * @return bool
     */
    public function is_newer_than(Flavor_LWW_Register $other) {
        return $this->compare($other) > 0;
    }

    /**
     * Convierte a array
     *
     * @return array
     */
    public function to_array() {
        return [
            'value'     => $this->value,
            'timestamp' => $this->timestamp,
            'peer_id'   => $this->peer_id,
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
     * Verifica si está vacío (sin valor)
     *
     * @return bool
     */
    public function is_empty() {
        return $this->value === null;
    }

    /**
     * Clona el registro
     *
     * @return self
     */
    public function clone() {
        return new self($this->value, $this->timestamp, $this->peer_id);
    }

    /**
     * Representación como string
     *
     * @return string
     */
    public function __toString() {
        $value_preview = is_string($this->value)
            ? substr($this->value, 0, 50)
            : gettype($this->value);
        return "LWW({$value_preview}@{$this->timestamp})";
    }
}

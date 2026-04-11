<?php
/**
 * OR-Set (Observed-Remove Set) CRDT
 *
 * Un conjunto donde los elementos pueden añadirse y eliminarse.
 * Cada elemento tiene un "tag" único para evitar el problema de
 * add/remove concurrente.
 *
 * Ideal para: tags, categorías, listas de participantes, etc.
 *
 * @package FlavorPlatform\Network\CRDT
 * @since 1.5.0
 */

if (!defined('ABSPATH')) {
    exit;
}

class Flavor_OR_Set {

    /**
     * Elementos del conjunto: element => [tag1, tag2, ...]
     * Cada tag es: {id: unique_id, peer_id: string, timestamp: int}
     *
     * @var array
     */
    private $elements = [];

    /**
     * Tags de elementos eliminados (tombstones)
     *
     * @var array
     */
    private $tombstones = [];

    /**
     * Constructor
     *
     * @param array $elements Elementos iniciales
     * @param array $tombstones Tombstones iniciales
     */
    public function __construct(array $elements = [], array $tombstones = []) {
        $this->elements = $elements;
        $this->tombstones = $tombstones;
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
            $data['elements'] ?? [],
            $data['tombstones'] ?? []
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
            $data['elements'] ?? [],
            $data['tombstones'] ?? []
        );
    }

    /**
     * Genera un tag único para un elemento
     *
     * @param string $peer_id
     * @return array
     */
    private function generate_tag($peer_id) {
        return [
            'id'        => bin2hex(random_bytes(8)),
            'peer_id'   => $peer_id,
            'timestamp' => (int) (microtime(true) * 1000000),
        ];
    }

    /**
     * Añade un elemento al conjunto
     *
     * @param mixed $element Elemento a añadir (será convertido a string para key)
     * @param string $peer_id ID del peer que añade
     * @return self
     */
    public function add($element, $peer_id) {
        $key = $this->element_key($element);
        $tag = $this->generate_tag($peer_id);

        if (!isset($this->elements[$key])) {
            $this->elements[$key] = [];
        }

        $this->elements[$key][] = $tag;
        return $this;
    }

    /**
     * Elimina un elemento del conjunto
     * (marca todos sus tags actuales como tombstones)
     *
     * @param mixed $element Elemento a eliminar
     * @return self
     */
    public function remove($element) {
        $key = $this->element_key($element);

        if (isset($this->elements[$key])) {
            // Mover todos los tags a tombstones
            foreach ($this->elements[$key] as $tag) {
                $tag_id = $tag['id'];
                $this->tombstones[$tag_id] = $tag;
            }
            unset($this->elements[$key]);
        }

        return $this;
    }

    /**
     * Verifica si un elemento existe en el conjunto
     *
     * @param mixed $element
     * @return bool
     */
    public function contains($element) {
        $key = $this->element_key($element);
        return isset($this->elements[$key]) && !empty($this->elements[$key]);
    }

    /**
     * Obtiene todos los elementos del conjunto
     *
     * @return array
     */
    public function values() {
        return array_keys($this->elements);
    }

    /**
     * Obtiene el número de elementos
     *
     * @return int
     */
    public function count() {
        return count($this->elements);
    }

    /**
     * Verifica si está vacío
     *
     * @return bool
     */
    public function is_empty() {
        return empty($this->elements);
    }

    /**
     * Merge con otro OR-Set
     *
     * @param Flavor_OR_Set $other
     * @return self Nuevo conjunto con el merge
     */
    public function merge(Flavor_OR_Set $other) {
        // Combinar tombstones
        $merged_tombstones = array_merge($this->tombstones, $other->tombstones);

        // Combinar elementos, filtrando tags que están en tombstones
        $merged_elements = [];

        $all_keys = array_unique(array_merge(
            array_keys($this->elements),
            array_keys($other->elements)
        ));

        foreach ($all_keys as $key) {
            $tags_this = $this->elements[$key] ?? [];
            $tags_other = $other->elements[$key] ?? [];

            // Unir tags únicos
            $all_tags = [];
            foreach (array_merge($tags_this, $tags_other) as $tag) {
                $tag_id = $tag['id'];
                // Solo incluir si no está en tombstones
                if (!isset($merged_tombstones[$tag_id])) {
                    $all_tags[$tag_id] = $tag;
                }
            }

            if (!empty($all_tags)) {
                $merged_elements[$key] = array_values($all_tags);
            }
        }

        return new self($merged_elements, $merged_tombstones);
    }

    /**
     * Convierte un elemento a key string
     *
     * @param mixed $element
     * @return string
     */
    private function element_key($element) {
        if (is_array($element) || is_object($element)) {
            return md5(serialize($element));
        }
        return (string) $element;
    }

    /**
     * Añade múltiples elementos
     *
     * @param array $elements
     * @param string $peer_id
     * @return self
     */
    public function add_many(array $elements, $peer_id) {
        foreach ($elements as $element) {
            $this->add($element, $peer_id);
        }
        return $this;
    }

    /**
     * Elimina múltiples elementos
     *
     * @param array $elements
     * @return self
     */
    public function remove_many(array $elements) {
        foreach ($elements as $element) {
            $this->remove($element);
        }
        return $this;
    }

    /**
     * Diferencia con otro conjunto (elementos que tenemos y el otro no)
     *
     * @param Flavor_OR_Set $other
     * @return array
     */
    public function diff(Flavor_OR_Set $other) {
        $our_values = $this->values();
        $other_values = $other->values();
        return array_diff($our_values, $other_values);
    }

    /**
     * Intersección con otro conjunto
     *
     * @param Flavor_OR_Set $other
     * @return array
     */
    public function intersect(Flavor_OR_Set $other) {
        $our_values = $this->values();
        $other_values = $other->values();
        return array_intersect($our_values, $other_values);
    }

    /**
     * Convierte a array para serialización
     *
     * @return array
     */
    public function to_array() {
        return [
            'elements'   => $this->elements,
            'tombstones' => $this->tombstones,
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
     * Clona el conjunto
     *
     * @return self
     */
    public function clone() {
        return new self($this->elements, $this->tombstones);
    }

    /**
     * Limpia tombstones antiguos para ahorrar espacio
     * PRECAUCIÓN: Solo usar cuando todos los peers estén sincronizados
     *
     * @param int $max_age_seconds Edad máxima en segundos
     * @return int Número de tombstones eliminados
     */
    public function gc_tombstones($max_age_seconds = 86400 * 30) {
        $now = microtime(true) * 1000000;
        $max_age_us = $max_age_seconds * 1000000;
        $removed = 0;

        foreach ($this->tombstones as $tag_id => $tag) {
            if (($now - $tag['timestamp']) > $max_age_us) {
                unset($this->tombstones[$tag_id]);
                $removed++;
            }
        }

        return $removed;
    }

    /**
     * Representación como string
     *
     * @return string
     */
    public function __toString() {
        $values = $this->values();
        $count = count($values);
        $preview = implode(', ', array_slice($values, 0, 5));
        if ($count > 5) {
            $preview .= '...';
        }
        return "ORSet({$count})[{$preview}]";
    }
}

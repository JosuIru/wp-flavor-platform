<?php
/**
 * CRDT Manager
 *
 * Gestiona la creación, persistencia y sincronización de CRDTs.
 * Mapea campos de entidades a tipos CRDT específicos.
 *
 * @package FlavorPlatform\Network\CRDT
 * @since 1.5.0
 */

if (!defined('ABSPATH')) {
    exit;
}

// Cargar dependencias CRDT
require_once __DIR__ . '/class-vector-clock.php';
require_once __DIR__ . '/class-lww-register.php';
require_once __DIR__ . '/class-or-set.php';
require_once __DIR__ . '/class-g-counter.php';
require_once __DIR__ . '/class-pn-counter.php';

class Flavor_CRDT_Manager {

    /**
     * Instancia singleton
     *
     * @var self|null
     */
    private static $instance = null;

    /**
     * Mapeo de campos a tipos CRDT
     *
     * Formato: doc_type => [field_name => crdt_type]
     *
     * @var array
     */
    private $field_mappings = [];

    /**
     * Cache de estados CRDT en memoria
     *
     * @var array
     */
    private $cache = [];

    /**
     * Nombre de la tabla CRDT state
     *
     * @var string
     */
    private $table_name;

    /**
     * Nombre de la tabla vector clocks
     *
     * @var string
     */
    private $clock_table;

    /**
     * Constructor privado (singleton)
     */
    private function __construct() {
        global $wpdb;
        $this->table_name = $wpdb->prefix . 'flavor_network_crdt_state';
        $this->clock_table = $wpdb->prefix . 'flavor_network_vector_clocks';
        $this->init_default_mappings();
    }

    /**
     * Obtiene la instancia singleton
     *
     * @return self
     */
    public static function instance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Inicializa los mapeos por defecto de campos a CRDTs
     */
    private function init_default_mappings() {
        $this->field_mappings = [
            // Contenido compartido
            'shared_content' => [
                'titulo'       => 'lww_register',
                'descripcion'  => 'lww_register',
                'precio'       => 'lww_register',
                'categorias'   => 'or_set',
                'tags'         => 'or_set',
                'vistas'       => 'g_counter',
            ],
            // Eventos
            'events' => [
                'titulo'       => 'lww_register',
                'descripcion'  => 'lww_register',
                'ubicacion'    => 'lww_register',
                'categorias'   => 'or_set',
                'inscritos'    => 'g_counter',
            ],
            // Colaboraciones
            'collaborations' => [
                'titulo'       => 'lww_register',
                'descripcion'  => 'lww_register',
                'categorias'   => 'or_set',
            ],
            // Tablón
            'board' => [
                'titulo'       => 'lww_register',
                'contenido'    => 'lww_register',
                'categorias'   => 'or_set',
                'vistas'       => 'g_counter',
            ],
            // Preguntas
            'questions' => [
                'titulo'       => 'lww_register',
                'descripcion'  => 'lww_register',
                'tags'         => 'or_set',
                'vistas'       => 'g_counter',
                'votacion'     => 'pn_counter',
            ],
            // Respuestas
            'answers' => [
                'contenido'    => 'lww_register',
                'votos'        => 'pn_counter',
            ],
            // Alertas solidarias
            'solidarity_alerts' => [
                'titulo'       => 'lww_register',
                'descripcion'  => 'lww_register',
                'respuestas'   => 'g_counter',
            ],
        ];

        // Permitir extensión via filtro
        $this->field_mappings = apply_filters(
            'flavor_crdt_field_mappings',
            $this->field_mappings
        );
    }

    /**
     * Registra un mapeo de campo a CRDT
     *
     * @param string $doc_type Tipo de documento
     * @param string $field_name Nombre del campo
     * @param string $crdt_type Tipo CRDT: lww_register, or_set, g_counter, pn_counter
     */
    public function register_field($doc_type, $field_name, $crdt_type) {
        if (!isset($this->field_mappings[$doc_type])) {
            $this->field_mappings[$doc_type] = [];
        }
        $this->field_mappings[$doc_type][$field_name] = $crdt_type;
    }

    /**
     * Obtiene el tipo CRDT para un campo
     *
     * @param string $doc_type
     * @param string $field_name
     * @return string|null
     */
    public function get_field_type($doc_type, $field_name) {
        return $this->field_mappings[$doc_type][$field_name] ?? null;
    }

    /**
     * Obtiene o crea un CRDT para un campo específico
     *
     * @param string $doc_type Tipo de documento
     * @param string $doc_id ID del documento
     * @param string $field_name Nombre del campo
     * @return object|null CRDT instance o null si no hay mapeo
     */
    public function get_crdt($doc_type, $doc_id, $field_name) {
        $crdt_type = $this->get_field_type($doc_type, $field_name);
        if (!$crdt_type) {
            return null;
        }

        $cache_key = "{$doc_type}:{$doc_id}:{$field_name}";

        // Buscar en cache
        if (isset($this->cache[$cache_key])) {
            return $this->cache[$cache_key];
        }

        // Buscar en BD
        $crdt = $this->load_crdt($doc_type, $doc_id, $field_name);

        if ($crdt) {
            $this->cache[$cache_key] = $crdt;
            return $crdt;
        }

        // Crear nuevo CRDT del tipo apropiado
        $crdt = $this->create_crdt($crdt_type);
        $this->cache[$cache_key] = $crdt;

        return $crdt;
    }

    /**
     * Crea una instancia de CRDT según el tipo
     *
     * @param string $crdt_type
     * @return object
     */
    private function create_crdt($crdt_type) {
        switch ($crdt_type) {
            case 'lww_register':
                return new Flavor_LWW_Register();
            case 'or_set':
                return new Flavor_OR_Set();
            case 'g_counter':
                return new Flavor_G_Counter();
            case 'pn_counter':
                return new Flavor_PN_Counter();
            default:
                return new Flavor_LWW_Register();
        }
    }

    /**
     * Carga un CRDT desde la BD
     *
     * @param string $doc_type
     * @param string $doc_id
     * @param string $field_name
     * @return object|null
     */
    private function load_crdt($doc_type, $doc_id, $field_name) {
        global $wpdb;

        $row = $wpdb->get_row($wpdb->prepare(
            "SELECT crdt_type, state_data FROM {$this->table_name}
             WHERE doc_type = %s AND doc_id = %s AND field_name = %s",
            $doc_type,
            $doc_id,
            $field_name
        ));

        if (!$row) {
            return null;
        }

        return $this->deserialize_crdt($row->crdt_type, $row->state_data);
    }

    /**
     * Deserializa un CRDT desde JSON
     *
     * @param string $crdt_type
     * @param string $state_json
     * @return object
     */
    private function deserialize_crdt($crdt_type, $state_json) {
        switch ($crdt_type) {
            case 'lww_register':
                return Flavor_LWW_Register::from_json($state_json);
            case 'or_set':
                return Flavor_OR_Set::from_json($state_json);
            case 'g_counter':
                return Flavor_G_Counter::from_json($state_json);
            case 'pn_counter':
                return Flavor_PN_Counter::from_json($state_json);
            default:
                return Flavor_LWW_Register::from_json($state_json);
        }
    }

    /**
     * Guarda un CRDT en la BD
     *
     * @param string $doc_type
     * @param string $doc_id
     * @param string $field_name
     * @param object $crdt
     * @param string|null $peer_id Peer que hace el cambio
     * @return bool
     */
    public function save_crdt($doc_type, $doc_id, $field_name, $crdt, $peer_id = null) {
        global $wpdb;

        $crdt_type = $this->get_field_type($doc_type, $field_name);
        if (!$crdt_type) {
            return false;
        }

        $state_json = $crdt->to_json();

        // Obtener vector clock actual
        $vector_clock = $this->get_vector_clock($doc_type, $doc_id);
        if ($peer_id) {
            $vector_clock->increment($peer_id);
        }

        $data = [
            'doc_type'           => $doc_type,
            'doc_id'             => $doc_id,
            'field_name'         => $field_name,
            'crdt_type'          => $crdt_type,
            'state_data'         => $state_json,
            'vector_clock'       => $vector_clock->to_json(),
            'last_merge_peer_id' => $peer_id,
        ];

        $existing = $wpdb->get_var($wpdb->prepare(
            "SELECT id FROM {$this->table_name}
             WHERE doc_type = %s AND doc_id = %s AND field_name = %s",
            $doc_type,
            $doc_id,
            $field_name
        ));

        if ($existing) {
            $result = $wpdb->update(
                $this->table_name,
                array_merge($data, ['merge_count' => $wpdb->prepare('merge_count + 1')]),
                ['id' => $existing]
            );
        } else {
            $result = $wpdb->insert($this->table_name, $data);
        }

        // Actualizar cache
        $cache_key = "{$doc_type}:{$doc_id}:{$field_name}";
        $this->cache[$cache_key] = $crdt;

        // Actualizar vector clock
        $this->save_vector_clock($doc_type, $doc_id, $vector_clock, $peer_id);

        return $result !== false;
    }

    /**
     * Actualiza un campo usando CRDT
     *
     * @param string $doc_type
     * @param string $doc_id
     * @param string $field_name
     * @param mixed $value Nuevo valor o operación
     * @param string $peer_id Peer que hace el cambio
     * @param string $operation Operación: 'set', 'add', 'remove', 'increment', 'decrement'
     * @return bool
     */
    public function update_field($doc_type, $doc_id, $field_name, $value, $peer_id, $operation = 'set') {
        $crdt = $this->get_crdt($doc_type, $doc_id, $field_name);
        if (!$crdt) {
            return false;
        }

        $crdt_type = $this->get_field_type($doc_type, $field_name);

        switch ($crdt_type) {
            case 'lww_register':
                $crdt->set($value, $peer_id);
                break;

            case 'or_set':
                if ($operation === 'add') {
                    if (is_array($value)) {
                        $crdt->add_many($value, $peer_id);
                    } else {
                        $crdt->add($value, $peer_id);
                    }
                } elseif ($operation === 'remove') {
                    if (is_array($value)) {
                        $crdt->remove_many($value);
                    } else {
                        $crdt->remove($value);
                    }
                } elseif ($operation === 'set') {
                    // Reemplazar todo el conjunto
                    $current = $crdt->values();
                    $crdt->remove_many($current);
                    if (is_array($value)) {
                        $crdt->add_many($value, $peer_id);
                    } else {
                        $crdt->add($value, $peer_id);
                    }
                }
                break;

            case 'g_counter':
                $amount = is_numeric($value) ? (int) $value : 1;
                $crdt->increment($peer_id, $amount);
                break;

            case 'pn_counter':
                $amount = is_numeric($value) ? (int) $value : 1;
                if ($operation === 'increment' || $amount > 0) {
                    $crdt->increment($peer_id, abs($amount));
                } elseif ($operation === 'decrement' || $amount < 0) {
                    $crdt->decrement($peer_id, abs($amount));
                }
                break;
        }

        return $this->save_crdt($doc_type, $doc_id, $field_name, $crdt, $peer_id);
    }

    /**
     * Obtiene el valor actual de un campo CRDT
     *
     * @param string $doc_type
     * @param string $doc_id
     * @param string $field_name
     * @return mixed
     */
    public function get_value($doc_type, $doc_id, $field_name) {
        $crdt = $this->get_crdt($doc_type, $doc_id, $field_name);
        if (!$crdt) {
            return null;
        }

        $crdt_type = $this->get_field_type($doc_type, $field_name);

        switch ($crdt_type) {
            case 'lww_register':
                return $crdt->get();
            case 'or_set':
                return $crdt->values();
            case 'g_counter':
            case 'pn_counter':
                return $crdt->value();
            default:
                return null;
        }
    }

    /**
     * Merge con estado remoto
     *
     * @param string $doc_type
     * @param string $doc_id
     * @param string $field_name
     * @param string $remote_state_json Estado remoto serializado
     * @param string $remote_peer_id Peer remoto
     * @return bool
     */
    public function merge_remote($doc_type, $doc_id, $field_name, $remote_state_json, $remote_peer_id) {
        $crdt_type = $this->get_field_type($doc_type, $field_name);
        if (!$crdt_type) {
            return false;
        }

        // Obtener estado local
        $local_crdt = $this->get_crdt($doc_type, $doc_id, $field_name);

        // Deserializar estado remoto
        $remote_crdt = $this->deserialize_crdt($crdt_type, $remote_state_json);

        // Merge
        $merged_crdt = $local_crdt->merge($remote_crdt);

        // Guardar resultado
        return $this->save_crdt($doc_type, $doc_id, $field_name, $merged_crdt, $remote_peer_id);
    }

    /**
     * Obtiene el vector clock de una entidad
     *
     * @param string $entity_type
     * @param string $entity_id
     * @return Flavor_Vector_Clock
     */
    public function get_vector_clock($entity_type, $entity_id) {
        global $wpdb;

        $row = $wpdb->get_row($wpdb->prepare(
            "SELECT clock_data FROM {$this->clock_table}
             WHERE entity_type = %s AND entity_id = %s",
            $entity_type,
            $entity_id
        ));

        if ($row) {
            return Flavor_Vector_Clock::from_json($row->clock_data);
        }

        return new Flavor_Vector_Clock();
    }

    /**
     * Guarda el vector clock de una entidad
     *
     * @param string $entity_type
     * @param string $entity_id
     * @param Flavor_Vector_Clock $clock
     * @param string|null $peer_id
     * @return bool
     */
    public function save_vector_clock($entity_type, $entity_id, Flavor_Vector_Clock $clock, $peer_id = null) {
        global $wpdb;

        $existing = $wpdb->get_var($wpdb->prepare(
            "SELECT id FROM {$this->clock_table}
             WHERE entity_type = %s AND entity_id = %s",
            $entity_type,
            $entity_id
        ));

        $data = [
            'entity_type'         => $entity_type,
            'entity_id'           => $entity_id,
            'clock_data'          => $clock->to_json(),
            'last_update_peer_id' => $peer_id,
        ];

        if ($existing) {
            return $wpdb->update(
                $this->clock_table,
                array_merge($data, ['merged_count' => $wpdb->prepare('merged_count + 1')]),
                ['id' => $existing]
            ) !== false;
        }

        return $wpdb->insert($this->clock_table, $data) !== false;
    }

    /**
     * Exporta todos los CRDTs de un documento para sincronización
     *
     * @param string $doc_type
     * @param string $doc_id
     * @return array
     */
    public function export_document($doc_type, $doc_id) {
        global $wpdb;

        $rows = $wpdb->get_results($wpdb->prepare(
            "SELECT field_name, crdt_type, state_data, vector_clock
             FROM {$this->table_name}
             WHERE doc_type = %s AND doc_id = %s",
            $doc_type,
            $doc_id
        ));

        $export = [
            'doc_type'     => $doc_type,
            'doc_id'       => $doc_id,
            'vector_clock' => $this->get_vector_clock($doc_type, $doc_id)->to_json(),
            'fields'       => [],
        ];

        foreach ($rows as $row) {
            $export['fields'][$row->field_name] = [
                'crdt_type'  => $row->crdt_type,
                'state_data' => $row->state_data,
            ];
        }

        return $export;
    }

    /**
     * Importa un documento desde datos remotos
     *
     * @param array $remote_data Datos exportados de otro peer
     * @param string $remote_peer_id
     * @return bool
     */
    public function import_document(array $remote_data, $remote_peer_id) {
        $doc_type = $remote_data['doc_type'] ?? null;
        $doc_id = $remote_data['doc_id'] ?? null;

        if (!$doc_type || !$doc_id) {
            return false;
        }

        // Merge vector clocks
        $local_clock = $this->get_vector_clock($doc_type, $doc_id);
        $remote_clock = Flavor_Vector_Clock::from_json($remote_data['vector_clock'] ?? '{}');
        $merged_clock = $local_clock->merge($remote_clock);

        // Merge cada campo
        foreach ($remote_data['fields'] ?? [] as $field_name => $field_data) {
            $this->merge_remote(
                $doc_type,
                $doc_id,
                $field_name,
                $field_data['state_data'],
                $remote_peer_id
            );
        }

        // Guardar vector clock merged
        $this->save_vector_clock($doc_type, $doc_id, $merged_clock, $remote_peer_id);

        return true;
    }

    /**
     * Limpia la cache en memoria
     */
    public function clear_cache() {
        $this->cache = [];
    }

    /**
     * Obtiene estadísticas de uso de CRDTs
     *
     * @return array
     */
    public function get_stats() {
        global $wpdb;

        $stats = [
            'total_crdts' => $wpdb->get_var("SELECT COUNT(*) FROM {$this->table_name}"),
            'by_type'     => [],
            'by_doc_type' => [],
            'total_merges' => $wpdb->get_var("SELECT SUM(merge_count) FROM {$this->table_name}"),
        ];

        $by_type = $wpdb->get_results(
            "SELECT crdt_type, COUNT(*) as count FROM {$this->table_name} GROUP BY crdt_type"
        );
        foreach ($by_type as $row) {
            $stats['by_type'][$row->crdt_type] = (int) $row->count;
        }

        $by_doc = $wpdb->get_results(
            "SELECT doc_type, COUNT(*) as count FROM {$this->table_name} GROUP BY doc_type"
        );
        foreach ($by_doc as $row) {
            $stats['by_doc_type'][$row->doc_type] = (int) $row->count;
        }

        return $stats;
    }
}

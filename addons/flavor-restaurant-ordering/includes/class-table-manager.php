<?php
/**
 * Gestor de mesas del restaurante
 *
 * @package Flavor_Restaurant_Ordering
 */

if (!defined('ABSPATH')) {
    exit;
}

class Flavor_Table_Manager {

    /**
     * Instancia única
     */
    private static $instance = null;

    /**
     * Nombre de la tabla en la BD
     */
    private $table_name;

    /**
     * Obtener instancia
     */
    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Constructor
     */
    private function __construct() {
        global $wpdb;
        $this->table_name = $wpdb->prefix . 'restaurant_tables';
    }

    /**
     * Crear nueva mesa
     */
    public function create_table($data) {
        global $wpdb;

        $defaults = [
            'table_number' => '',
            'table_name' => '',
            'capacity' => 4,
            'status' => 'available',
            'location' => '',
            'notes' => ''
        ];

        $data = wp_parse_args($data, $defaults);

        // Validar número de mesa
        if (empty($data['table_number'])) {
            return new WP_Error('missing_table_number', __('El número de mesa es obligatorio', 'flavor-restaurant-ordering'));
        }

        // Verificar que no exista ya
        $exists = $wpdb->get_var($wpdb->prepare(
            "SELECT id FROM {$this->table_name} WHERE table_number = %s",
            $data['table_number']
        ));

        if ($exists) {
            return new WP_Error('duplicate_table', __('Ya existe una mesa con ese número', 'flavor-restaurant-ordering'));
        }

        // Generar QR code si está habilitado
        if (Flavor_Restaurant_Manager::get_instance()->get_settings('enable_table_qr')) {
            $data['qr_code'] = $this->generate_qr_code($data['table_number']);
        }

        // Insertar en BD
        $inserted = $wpdb->insert(
            $this->table_name,
            [
                'table_number' => sanitize_text_field($data['table_number']),
                'table_name' => sanitize_text_field($data['table_name']),
                'capacity' => absint($data['capacity']),
                'status' => sanitize_text_field($data['status']),
                'qr_code' => $data['qr_code'] ?? null,
                'location' => sanitize_text_field($data['location']),
                'notes' => wp_kses_post($data['notes']),
            ],
            ['%s', '%s', '%d', '%s', '%s', '%s', '%s']
        );

        if ($inserted === false) {
            return new WP_Error('db_error', __('Error al crear la mesa en la base de datos', 'flavor-restaurant-ordering'));
        }

        $table_id = $wpdb->insert_id;

        do_action('flavor_restaurant_table_created', $table_id, $data);

        return $this->get_table($table_id);
    }

    /**
     * Obtener mesa por ID
     */
    public function get_table($table_id) {
        global $wpdb;

        $table = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$this->table_name} WHERE id = %d",
            $table_id
        ));

        if (!$table) {
            return null;
        }

        return $this->format_table($table);
    }

    /**
     * Obtener mesa por número
     */
    public function get_table_by_number($table_number) {
        global $wpdb;

        $table = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$this->table_name} WHERE table_number = %s",
            $table_number
        ));

        if (!$table) {
            return null;
        }

        return $this->format_table($table);
    }

    /**
     * Obtener todas las mesas
     */
    public function get_tables($args = []) {
        global $wpdb;

        $defaults = [
            'status' => null,
            'location' => null,
            'orderby' => 'table_number',
            'order' => 'ASC',
            'limit' => -1,
            'offset' => 0,
        ];

        $args = wp_parse_args($args, $defaults);

        $where = ['1=1'];
        $where_values = [];

        if ($args['status']) {
            $where[] = 'status = %s';
            $where_values[] = $args['status'];
        }

        if ($args['location']) {
            $where[] = 'location = %s';
            $where_values[] = $args['location'];
        }

        $where_clause = implode(' AND ', $where);

        $orderby = in_array($args['orderby'], ['table_number', 'capacity', 'status', 'created_at'])
            ? $args['orderby']
            : 'table_number';

        $order = strtoupper($args['order']) === 'DESC' ? 'DESC' : 'ASC';

        $limit_clause = '';
        if ($args['limit'] > 0) {
            $limit_clause = $wpdb->prepare(' LIMIT %d OFFSET %d', $args['limit'], $args['offset']);
        }

        $query = "SELECT * FROM {$this->table_name} WHERE {$where_clause} ORDER BY {$orderby} {$order}{$limit_clause}";

        if (!empty($where_values)) {
            $query = $wpdb->prepare($query, $where_values);
        }

        $results = $wpdb->get_results($query);

        return array_map([$this, 'format_table'], $results);
    }

    /**
     * Actualizar mesa
     */
    public function update_table($table_id, $data) {
        global $wpdb;

        $table = $this->get_table($table_id);
        if (!$table) {
            return new WP_Error('table_not_found', __('Mesa no encontrada', 'flavor-restaurant-ordering'));
        }

        $allowed_fields = [
            'table_number',
            'table_name',
            'capacity',
            'status',
            'location',
            'notes'
        ];

        $update_data = [];
        $update_format = [];

        foreach ($allowed_fields as $field) {
            if (isset($data[$field])) {
                switch ($field) {
                    case 'capacity':
                        $update_data[$field] = absint($data[$field]);
                        $update_format[] = '%d';
                        break;
                    case 'notes':
                        $update_data[$field] = wp_kses_post($data[$field]);
                        $update_format[] = '%s';
                        break;
                    default:
                        $update_data[$field] = sanitize_text_field($data[$field]);
                        $update_format[] = '%s';
                }
            }
        }

        if (empty($update_data)) {
            return $table;
        }

        $updated = $wpdb->update(
            $this->table_name,
            $update_data,
            ['id' => $table_id],
            $update_format,
            ['%d']
        );

        if ($updated === false) {
            return new WP_Error('db_error', __('Error al actualizar la mesa', 'flavor-restaurant-ordering'));
        }

        do_action('flavor_restaurant_table_updated', $table_id, $data);

        return $this->get_table($table_id);
    }

    /**
     * Eliminar mesa
     */
    public function delete_table($table_id) {
        global $wpdb;

        $table = $this->get_table($table_id);
        if (!$table) {
            return new WP_Error('table_not_found', __('Mesa no encontrada', 'flavor-restaurant-ordering'));
        }

        // Verificar que no tenga pedidos activos
        $active_orders = $this->has_active_orders($table_id);
        if ($active_orders) {
            return new WP_Error('has_active_orders', __('No se puede eliminar una mesa con pedidos activos', 'flavor-restaurant-ordering'));
        }

        $deleted = $wpdb->delete(
            $this->table_name,
            ['id' => $table_id],
            ['%d']
        );

        if ($deleted === false) {
            return new WP_Error('db_error', __('Error al eliminar la mesa', 'flavor-restaurant-ordering'));
        }

        do_action('flavor_restaurant_table_deleted', $table_id);

        return true;
    }

    /**
     * Cambiar estado de mesa
     */
    public function update_status($table_id, $new_status) {
        $valid_statuses = ['available', 'occupied', 'reserved', 'cleaning', 'disabled'];

        if (!in_array($new_status, $valid_statuses)) {
            return new WP_Error('invalid_status', __('Estado de mesa inválido', 'flavor-restaurant-ordering'));
        }

        return $this->update_table($table_id, ['status' => $new_status]);
    }

    /**
     * Verificar si tiene pedidos activos
     */
    private function has_active_orders($table_id) {
        global $wpdb;

        $table_orders = $wpdb->prefix . 'restaurant_orders';

        $count = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM {$table_orders}
             WHERE table_id = %d
             AND status NOT IN ('completed', 'cancelled')",
            $table_id
        ));

        return $count > 0;
    }

    /**
     * Generar código QR para mesa
     */
    private function generate_qr_code($table_number) {
        // URL que apuntará a la app con el número de mesa
        $base_url = get_site_url();
        $qr_url = add_query_arg([
            'table' => $table_number,
            'action' => 'restaurant_order'
        ], $base_url);

        // En una implementación real, aquí se generaría el QR usando una librería
        // Por ahora, solo guardamos la URL
        return $qr_url;
    }

    /**
     * Formatear mesa para respuesta
     */
    private function format_table($table) {
        if (!$table) {
            return null;
        }

        return [
            'id' => (int) $table->id,
            'table_number' => $table->table_number,
            'table_name' => $table->table_name ?: $table->table_number,
            'capacity' => (int) $table->capacity,
            'status' => $table->status,
            'status_label' => $this->get_status_label($table->status),
            'qr_code' => $table->qr_code,
            'location' => $table->location,
            'notes' => $table->notes,
            'created_at' => $table->created_at,
            'updated_at' => $table->updated_at,
        ];
    }

    /**
     * Obtener etiqueta del estado
     */
    private function get_status_label($status) {
        $labels = [
            'available' => 'Disponible',
            'occupied' => 'Ocupada',
            'reserved' => 'Reservada',
            'cleaning' => 'En limpieza',
            'disabled' => 'Deshabilitada'
        ];

        return $labels[$status] ?? $status;
    }

    /**
     * Obtener estadísticas de mesas
     */
    public function get_statistics() {
        global $wpdb;

        $stats = [
            'total' => 0,
            'available' => 0,
            'occupied' => 0,
            'reserved' => 0,
            'cleaning' => 0,
            'disabled' => 0,
            'total_capacity' => 0,
        ];

        $results = $wpdb->get_results(
            "SELECT status, COUNT(*) as count, SUM(capacity) as capacity
             FROM {$this->table_name}
             GROUP BY status"
        );

        foreach ($results as $row) {
            $stats[$row->status] = (int) $row->count;
            $stats['total'] += (int) $row->count;
            $stats['total_capacity'] += (int) $row->capacity;
        }

        return $stats;
    }
}

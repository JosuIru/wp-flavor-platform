<?php
/**
 * Gestor de reservas del restaurante
 *
 * @package Flavor_Restaurant_Ordering
 */

if (!defined('ABSPATH')) {
    exit;
}

class Flavor_Reservation_Manager {

    /**
     * Instancia única
     */
    private static $instance = null;

    /**
     * Nombre de la tabla
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
        $this->table_name = $wpdb->prefix . 'restaurant_reservations';

        $this->init_hooks();
    }

    /**
     * Inicializar hooks
     */
    private function init_hooks() {
        // Cron para limpiar reservas expiradas
        add_action('flavor_restaurant_cleanup_reservations', [$this, 'cleanup_expired_reservations']);

        if (!wp_next_scheduled('flavor_restaurant_cleanup_reservations')) {
            wp_schedule_event(time(), 'daily', 'flavor_restaurant_cleanup_reservations');
        }
    }

    /**
     * Crear tabla de reservas
     */
    public static function create_table() {
        global $wpdb;

        $table_name = $wpdb->prefix . 'restaurant_reservations';
        $charset_collate = $wpdb->get_charset_collate();

        $sql = "CREATE TABLE IF NOT EXISTS $table_name (
            id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            reservation_code varchar(50) NOT NULL,
            table_id bigint(20) UNSIGNED DEFAULT NULL,
            customer_name varchar(255) NOT NULL,
            customer_phone varchar(50) NOT NULL,
            customer_email varchar(100) DEFAULT NULL,
            user_id bigint(20) UNSIGNED DEFAULT NULL,
            guests_count int(11) DEFAULT 2,
            reservation_date date NOT NULL,
            reservation_time time NOT NULL,
            duration int(11) DEFAULT 120,
            status varchar(20) DEFAULT 'pending',
            special_requests text DEFAULT NULL,
            confirmation_sent tinyint(1) DEFAULT 0,
            reminder_sent tinyint(1) DEFAULT 0,
            notes text DEFAULT NULL,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            confirmed_at datetime DEFAULT NULL,
            cancelled_at datetime DEFAULT NULL,
            PRIMARY KEY (id),
            UNIQUE KEY reservation_code (reservation_code),
            KEY table_id (table_id),
            KEY status (status),
            KEY reservation_date (reservation_date),
            KEY customer_email (customer_email)
        ) $charset_collate;";

        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);
    }

    /**
     * Crear nueva reserva
     */
    public function create_reservation($data) {
        global $wpdb;

        // Validar datos requeridos
        $required_fields = ['customer_name', 'customer_phone', 'reservation_date', 'reservation_time', 'guests_count'];
        foreach ($required_fields as $field) {
            if (empty($data[$field])) {
                return new WP_Error('missing_field', "Campo requerido: {$field}");
            }
        }

        // Validar formato de fecha y hora
        $reservation_datetime = $data['reservation_date'] . ' ' . $data['reservation_time'];
        if (strtotime($reservation_datetime) < time()) {
            return new WP_Error('invalid_datetime', 'No se puede reservar en el pasado');
        }

        // Buscar mesa disponible si no se especifica
        $table_id = isset($data['table_id']) ? absint($data['table_id']) : null;

        if (!$table_id) {
            $table_id = $this->find_available_table(
                $data['reservation_date'],
                $data['reservation_time'],
                $data['guests_count'],
                isset($data['duration']) ? $data['duration'] : 120
            );

            if (!$table_id) {
                return new WP_Error('no_tables_available', 'No hay mesas disponibles para esa fecha y hora');
            }
        } else {
            // Verificar que la mesa esté disponible
            if (!$this->is_table_available($table_id, $data['reservation_date'], $data['reservation_time'])) {
                return new WP_Error('table_not_available', 'La mesa seleccionada no está disponible en ese horario');
            }
        }

        // Generar código de reserva único
        $reservation_code = $this->generate_reservation_code();

        // Preparar datos
        $reservation_data = [
            'reservation_code' => $reservation_code,
            'table_id' => $table_id,
            'customer_name' => sanitize_text_field($data['customer_name']),
            'customer_phone' => sanitize_text_field($data['customer_phone']),
            'customer_email' => sanitize_email($data['customer_email'] ?? ''),
            'user_id' => isset($data['user_id']) ? absint($data['user_id']) : get_current_user_id(),
            'guests_count' => absint($data['guests_count']),
            'reservation_date' => sanitize_text_field($data['reservation_date']),
            'reservation_time' => sanitize_text_field($data['reservation_time']),
            'duration' => isset($data['duration']) ? absint($data['duration']) : 120,
            'status' => 'pending',
            'special_requests' => wp_kses_post($data['special_requests'] ?? ''),
            'notes' => wp_kses_post($data['notes'] ?? ''),
        ];

        // Insertar en BD
        $inserted = $wpdb->insert(
            $this->table_name,
            $reservation_data,
            ['%s', '%d', '%s', '%s', '%s', '%d', '%d', '%s', '%s', '%d', '%s', '%s', '%s']
        );

        if ($inserted === false) {
            return new WP_Error('db_error', 'Error al crear la reserva');
        }

        $reservation_id = $wpdb->insert_id;

        do_action('flavor_restaurant_reservation_created', $reservation_id, $reservation_data);

        // Enviar confirmación por email
        if (!empty($reservation_data['customer_email'])) {
            $this->send_reservation_email($reservation_id, 'created');
        }

        return $this->get_reservation($reservation_id);
    }

    /**
     * Obtener reserva por ID
     */
    public function get_reservation($reservation_id) {
        global $wpdb;

        $reservation = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$this->table_name} WHERE id = %d",
            $reservation_id
        ));

        if (!$reservation) {
            return null;
        }

        return $this->format_reservation($reservation);
    }

    /**
     * Obtener reserva por código
     */
    public function get_reservation_by_code($code) {
        global $wpdb;

        $reservation = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$this->table_name} WHERE reservation_code = %s",
            $code
        ));

        if (!$reservation) {
            return null;
        }

        return $this->format_reservation($reservation);
    }

    /**
     * Obtener reservas
     */
    public function get_reservations($args = []) {
        global $wpdb;

        $defaults = [
            'status' => null,
            'table_id' => null,
            'date' => null,
            'date_from' => null,
            'date_to' => null,
            'customer_email' => null,
            'user_id' => null,
            'upcoming' => false,
            'orderby' => 'reservation_date, reservation_time',
            'order' => 'ASC',
            'limit' => 50,
            'offset' => 0,
        ];

        $args = wp_parse_args($args, $defaults);

        $where = ['1=1'];
        $where_values = [];

        if ($args['status']) {
            if (is_array($args['status'])) {
                $placeholders = implode(',', array_fill(0, count($args['status']), '%s'));
                $where[] = "status IN ($placeholders)";
                $where_values = array_merge($where_values, $args['status']);
            } else {
                $where[] = 'status = %s';
                $where_values[] = $args['status'];
            }
        }

        if ($args['table_id']) {
            $where[] = 'table_id = %d';
            $where_values[] = $args['table_id'];
        }

        if ($args['date']) {
            $where[] = 'reservation_date = %s';
            $where_values[] = $args['date'];
        }

        if ($args['date_from']) {
            $where[] = 'reservation_date >= %s';
            $where_values[] = $args['date_from'];
        }

        if ($args['date_to']) {
            $where[] = 'reservation_date <= %s';
            $where_values[] = $args['date_to'];
        }

        if ($args['customer_email']) {
            $where[] = 'customer_email = %s';
            $where_values[] = $args['customer_email'];
        }

        if ($args['user_id']) {
            $where[] = 'user_id = %d';
            $where_values[] = $args['user_id'];
        }

        if ($args['upcoming']) {
            $where[] = "CONCAT(reservation_date, ' ', reservation_time) >= %s";
            $where_values[] = current_time('mysql');
        }

        $where_clause = implode(' AND ', $where);

        $order = strtoupper($args['order']) === 'DESC' ? 'DESC' : 'ASC';
        $limit_clause = $wpdb->prepare(' LIMIT %d OFFSET %d', $args['limit'], $args['offset']);

        $query = "SELECT * FROM {$this->table_name} WHERE {$where_clause} ORDER BY {$args['orderby']} {$order}{$limit_clause}";

        if (!empty($where_values)) {
            $query = $wpdb->prepare($query, $where_values);
        }

        $results = $wpdb->get_results($query);

        return array_map([$this, 'format_reservation'], $results);
    }

    /**
     * Actualizar reserva
     */
    public function update_reservation($reservation_id, $data) {
        global $wpdb;

        $reservation = $this->get_reservation($reservation_id);
        if (!$reservation) {
            return new WP_Error('reservation_not_found', 'Reserva no encontrada');
        }

        $allowed_fields = [
            'table_id',
            'customer_name',
            'customer_phone',
            'customer_email',
            'guests_count',
            'reservation_date',
            'reservation_time',
            'duration',
            'special_requests',
            'notes'
        ];

        $update_data = [];
        $update_format = [];

        foreach ($allowed_fields as $field) {
            if (isset($data[$field])) {
                switch ($field) {
                    case 'table_id':
                    case 'guests_count':
                    case 'duration':
                        $update_data[$field] = absint($data[$field]);
                        $update_format[] = '%d';
                        break;
                    case 'customer_email':
                        $update_data[$field] = sanitize_email($data[$field]);
                        $update_format[] = '%s';
                        break;
                    case 'special_requests':
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
            return $reservation;
        }

        $updated = $wpdb->update(
            $this->table_name,
            $update_data,
            ['id' => $reservation_id],
            $update_format,
            ['%d']
        );

        if ($updated === false) {
            return new WP_Error('db_error', 'Error al actualizar la reserva');
        }

        do_action('flavor_restaurant_reservation_updated', $reservation_id, $data);

        return $this->get_reservation($reservation_id);
    }

    /**
     * Actualizar estado de reserva
     */
    public function update_status($reservation_id, $new_status, $notes = '') {
        global $wpdb;

        $valid_statuses = ['pending', 'confirmed', 'cancelled', 'completed', 'no_show'];

        if (!in_array($new_status, $valid_statuses)) {
            return new WP_Error('invalid_status', 'Estado de reserva inválido');
        }

        $reservation = $this->get_reservation($reservation_id);
        if (!$reservation) {
            return new WP_Error('reservation_not_found', 'Reserva no encontrada');
        }

        $update_data = ['status' => $new_status];

        if ($new_status === 'confirmed' && !$reservation['confirmed_at']) {
            $update_data['confirmed_at'] = current_time('mysql');
        }

        if ($new_status === 'cancelled' && !$reservation['cancelled_at']) {
            $update_data['cancelled_at'] = current_time('mysql');
        }

        if ($notes) {
            $current_notes = $reservation['notes'];
            $update_data['notes'] = $current_notes . "\n" . current_time('mysql') . ': ' . $notes;
        }

        $updated = $wpdb->update(
            $this->table_name,
            $update_data,
            ['id' => $reservation_id],
            array_fill(0, count($update_data), '%s'),
            ['%d']
        );

        if ($updated === false) {
            return new WP_Error('db_error', 'Error al actualizar el estado');
        }

        // Enviar email de confirmación/cancelación
        if (in_array($new_status, ['confirmed', 'cancelled'])) {
            $this->send_reservation_email($reservation_id, $new_status);
        }

        // Actualizar estado de la mesa
        if ($new_status === 'confirmed') {
            $table_manager = Flavor_Table_Manager::get_instance();
            $table_manager->update_status($reservation['table_id'], 'reserved');
        } elseif (in_array($new_status, ['cancelled', 'completed', 'no_show'])) {
            // Verificar si hay otras reservas activas para la mesa
            $active_reservations = $this->get_reservations([
                'table_id' => $reservation['table_id'],
                'status' => ['pending', 'confirmed'],
                'upcoming' => true,
                'limit' => 1
            ]);

            if (empty($active_reservations)) {
                $table_manager = Flavor_Table_Manager::get_instance();
                $table_manager->update_status($reservation['table_id'], 'available');
            }
        }

        do_action('flavor_restaurant_reservation_status_updated', $reservation_id, $new_status, $reservation['status']);

        return $this->get_reservation($reservation_id);
    }

    /**
     * Cancelar reserva
     */
    public function cancel_reservation($reservation_id, $reason = '') {
        return $this->update_status($reservation_id, 'cancelled', $reason);
    }

    /**
     * Verificar si una mesa está disponible
     */
    private function is_table_available($table_id, $date, $time, $duration = 120, $exclude_reservation_id = null) {
        global $wpdb;

        // Calcular rango de tiempo
        $start_time = strtotime("$date $time");
        $end_time = $start_time + ($duration * 60);

        $query = "SELECT COUNT(*) FROM {$this->table_name}
                  WHERE table_id = %d
                  AND status IN ('pending', 'confirmed')
                  AND reservation_date = %s
                  AND (
                      (UNIX_TIMESTAMP(CONCAT(reservation_date, ' ', reservation_time)) < %d
                       AND UNIX_TIMESTAMP(CONCAT(reservation_date, ' ', reservation_time)) + (duration * 60) > %d)
                      OR
                      (UNIX_TIMESTAMP(CONCAT(reservation_date, ' ', reservation_time)) >= %d
                       AND UNIX_TIMESTAMP(CONCAT(reservation_date, ' ', reservation_time)) < %d)
                  )";

        $values = [$table_id, $date, $end_time, $start_time, $start_time, $end_time];

        if ($exclude_reservation_id) {
            $query .= " AND id != %d";
            $values[] = $exclude_reservation_id;
        }

        $count = $wpdb->get_var($wpdb->prepare($query, $values));

        return $count == 0;
    }

    /**
     * Buscar mesa disponible
     */
    private function find_available_table($date, $time, $guests_count, $duration = 120) {
        $table_manager = Flavor_Table_Manager::get_instance();

        // Obtener todas las mesas con capacidad suficiente
        $tables = $table_manager->get_tables([
            'status' => 'available'
        ]);

        foreach ($tables as $table) {
            if ($table['capacity'] >= $guests_count) {
                if ($this->is_table_available($table['id'], $date, $time, $duration)) {
                    return $table['id'];
                }
            }
        }

        return null;
    }

    /**
     * Generar código de reserva único
     */
    private function generate_reservation_code() {
        $prefix = 'RES';
        $timestamp = date('ymd');

        do {
            $random = str_pad(mt_rand(1, 9999), 4, '0', STR_PAD_LEFT);
            $code = $prefix . '-' . $timestamp . '-' . $random;

            global $wpdb;
            $exists = $wpdb->get_var($wpdb->prepare(
                "SELECT COUNT(*) FROM {$this->table_name} WHERE reservation_code = %s",
                $code
            ));
        } while ($exists > 0);

        return $code;
    }

    /**
     * Formatear reserva para respuesta
     */
    private function format_reservation($reservation) {
        if (!$reservation) {
            return null;
        }

        $formatted = [
            'id' => (int) $reservation->id,
            'reservation_code' => $reservation->reservation_code,
            'table_id' => $reservation->table_id ? (int) $reservation->table_id : null,
            'table' => null,
            'customer' => [
                'name' => $reservation->customer_name,
                'phone' => $reservation->customer_phone,
                'email' => $reservation->customer_email,
            ],
            'user_id' => $reservation->user_id ? (int) $reservation->user_id : null,
            'guests_count' => (int) $reservation->guests_count,
            'reservation_date' => $reservation->reservation_date,
            'reservation_time' => $reservation->reservation_time,
            'reservation_datetime' => $reservation->reservation_date . ' ' . $reservation->reservation_time,
            'duration' => (int) $reservation->duration,
            'status' => $reservation->status,
            'status_label' => $this->get_status_label($reservation->status),
            'special_requests' => $reservation->special_requests,
            'notes' => $reservation->notes,
            'confirmation_sent' => (bool) $reservation->confirmation_sent,
            'reminder_sent' => (bool) $reservation->reminder_sent,
            'created_at' => $reservation->created_at,
            'updated_at' => $reservation->updated_at,
            'confirmed_at' => $reservation->confirmed_at,
            'cancelled_at' => $reservation->cancelled_at,
        ];

        // Agregar información de mesa
        if ($reservation->table_id) {
            $table_manager = Flavor_Table_Manager::get_instance();
            $table = $table_manager->get_table($reservation->table_id);
            $formatted['table'] = $table;
        }

        return $formatted;
    }

    /**
     * Obtener etiqueta del estado
     */
    private function get_status_label($status) {
        $labels = [
            'pending' => 'Pendiente',
            'confirmed' => 'Confirmada',
            'cancelled' => 'Cancelada',
            'completed' => 'Completada',
            'no_show' => 'No se presentó'
        ];

        return $labels[$status] ?? $status;
    }

    /**
     * Enviar email de reserva
     */
    private function send_reservation_email($reservation_id, $type) {
        $reservation = $this->get_reservation($reservation_id);

        if (!$reservation || empty($reservation['customer']['email'])) {
            return false;
        }

        $to = $reservation['customer']['email'];
        $headers = ['Content-Type: text/html; charset=UTF-8'];

        switch ($type) {
            case 'created':
                $subject = 'Reserva Recibida - ' . $reservation['reservation_code'];
                $message = $this->get_email_template_created($reservation);
                break;

            case 'confirmed':
                $subject = 'Reserva Confirmada - ' . $reservation['reservation_code'];
                $message = $this->get_email_template_confirmed($reservation);
                break;

            case 'cancelled':
                $subject = 'Reserva Cancelada - ' . $reservation['reservation_code'];
                $message = $this->get_email_template_cancelled($reservation);
                break;

            default:
                return false;
        }

        return wp_mail($to, $subject, $message, $headers);
    }

    /**
     * Template de email de creación
     */
    private function get_email_template_created($reservation) {
        $datetime = date('d/m/Y', strtotime($reservation['reservation_date'])) . ' a las ' .
                    date('H:i', strtotime($reservation['reservation_time']));

        return "
        <html>
        <body style='font-family: Arial, sans-serif;'>
            <h2>¡Reserva Recibida!</h2>
            <p>Hola {$reservation['customer']['name']},</p>
            <p>Hemos recibido tu reserva. Te confirmaremos en breve.</p>

            <h3>Detalles de la Reserva:</h3>
            <ul>
                <li><strong>Código:</strong> {$reservation['reservation_code']}</li>
                <li><strong>Fecha y Hora:</strong> {$datetime}</li>
                <li><strong>Personas:</strong> {$reservation['guests_count']}</li>
                <li><strong>Mesa:</strong> {$reservation['table']['table_name']}</li>
            </ul>

            <p>Para cualquier cambio, contacta con nosotros.</p>
            <p>¡Gracias!</p>
        </body>
        </html>
        ";
    }

    /**
     * Template de email de confirmación
     */
    private function get_email_template_confirmed($reservation) {
        $datetime = date('d/m/Y', strtotime($reservation['reservation_date'])) . ' a las ' .
                    date('H:i', strtotime($reservation['reservation_time']));

        return "
        <html>
        <body style='font-family: Arial, sans-serif;'>
            <h2>✅ Reserva Confirmada</h2>
            <p>Hola {$reservation['customer']['name']},</p>
            <p><strong>¡Tu reserva está confirmada!</strong></p>

            <h3>Detalles:</h3>
            <ul>
                <li><strong>Código:</strong> {$reservation['reservation_code']}</li>
                <li><strong>Fecha y Hora:</strong> {$datetime}</li>
                <li><strong>Personas:</strong> {$reservation['guests_count']}</li>
                <li><strong>Mesa:</strong> {$reservation['table']['table_name']}</li>
            </ul>

            <p>Te esperamos. Si necesitas cancelar, contacta con nosotros.</p>
            <p>¡Hasta pronto!</p>
        </body>
        </html>
        ";
    }

    /**
     * Template de email de cancelación
     */
    private function get_email_template_cancelled($reservation) {
        return "
        <html>
        <body style='font-family: Arial, sans-serif;'>
            <h2>Reserva Cancelada</h2>
            <p>Hola {$reservation['customer']['name']},</p>
            <p>Tu reserva con código <strong>{$reservation['reservation_code']}</strong> ha sido cancelada.</p>

            <p>Si deseas hacer una nueva reserva, estaremos encantados de atenderte.</p>
            <p>Gracias.</p>
        </body>
        </html>
        ";
    }

    /**
     * Limpiar reservas expiradas
     */
    public function cleanup_expired_reservations() {
        global $wpdb;

        // Marcar como completadas las reservas pasadas que están confirmadas
        $wpdb->query("
            UPDATE {$this->table_name}
            SET status = 'completed'
            WHERE status = 'confirmed'
            AND CONCAT(reservation_date, ' ', reservation_time) < NOW()
        ");

        // Marcar como no_show las pendientes que ya pasaron
        $wpdb->query("
            UPDATE {$this->table_name}
            SET status = 'no_show'
            WHERE status = 'pending'
            AND CONCAT(reservation_date, ' ', reservation_time) < DATE_SUB(NOW(), INTERVAL 1 HOUR)
        ");
    }

    /**
     * Obtener estadísticas de reservas
     */
    public function get_statistics($date_from = null, $date_to = null) {
        global $wpdb;

        $where = ['1=1'];
        $where_values = [];

        if ($date_from) {
            $where[] = 'reservation_date >= %s';
            $where_values[] = $date_from;
        }

        if ($date_to) {
            $where[] = 'reservation_date <= %s';
            $where_values[] = $date_to;
        }

        $where_clause = implode(' AND ', $where);

        $query = "SELECT
            COUNT(*) as total_reservations,
            SUM(CASE WHEN status = 'confirmed' THEN 1 ELSE 0 END) as confirmed_reservations,
            SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) as pending_reservations,
            SUM(CASE WHEN status = 'cancelled' THEN 1 ELSE 0 END) as cancelled_reservations,
            SUM(CASE WHEN status = 'no_show' THEN 1 ELSE 0 END) as no_show_reservations,
            SUM(guests_count) as total_guests,
            AVG(guests_count) as average_party_size
            FROM {$this->table_name}
            WHERE {$where_clause}";

        if (!empty($where_values)) {
            $query = $wpdb->prepare($query, $where_values);
        }

        $stats = $wpdb->get_row($query, ARRAY_A);

        return [
            'total_reservations' => (int) $stats['total_reservations'],
            'confirmed_reservations' => (int) $stats['confirmed_reservations'],
            'pending_reservations' => (int) $stats['pending_reservations'],
            'cancelled_reservations' => (int) $stats['cancelled_reservations'],
            'no_show_reservations' => (int) $stats['no_show_reservations'],
            'total_guests' => (int) $stats['total_guests'],
            'average_party_size' => (float) $stats['average_party_size'],
        ];
    }
}

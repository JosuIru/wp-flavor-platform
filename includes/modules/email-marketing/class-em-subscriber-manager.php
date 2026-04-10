<?php
/**
 * Gestor de Suscriptores para Email Marketing
 *
 * Maneja todas las operaciones CRUD de suscriptores.
 *
 * @package FlavorPlatform
 * @subpackage EmailMarketing
 */

if (!defined('ABSPATH')) {
    exit;
}

class Flavor_EM_Subscriber_Manager {

    /**
     * Prefijo de tablas
     */
    const TABLE_PREFIX = 'flavor_em_';

    /**
     * Estados válidos de suscriptor
     */
    const VALID_STATUSES = ['pendiente', 'activo', 'baja', 'rebotado', 'spam'];

    /**
     * Instancia singleton
     * @var self|null
     */
    private static $instance = null;

    /**
     * Obtener instancia
     */
    public static function get_instance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Obtener suscriptores con filtros y paginación
     *
     * @param array $args Argumentos de consulta
     * @return array
     */
    public function get_subscribers($args = []) {
        global $wpdb;

        $defaults = [
            'page' => 1,
            'per_page' => 20,
            'lista_id' => null,
            'estado' => null,
            'search' => '',
            'orderby' => 'created_at',
            'order' => 'DESC',
        ];

        $args = wp_parse_args($args, $defaults);
        $table = $wpdb->prefix . self::TABLE_PREFIX . 'suscriptores';
        $table_rel = $wpdb->prefix . self::TABLE_PREFIX . 'suscriptor_lista';

        $where = ['1=1'];
        $params = [];

        // Filtro por lista
        if (!empty($args['lista_id'])) {
            $where[] = "s.id IN (SELECT suscriptor_id FROM {$table_rel} WHERE lista_id = %d)";
            $params[] = absint($args['lista_id']);
        }

        // Filtro por estado
        if (!empty($args['estado']) && in_array($args['estado'], self::VALID_STATUSES)) {
            $where[] = 's.estado = %s';
            $params[] = $args['estado'];
        }

        // Búsqueda
        if (!empty($args['search'])) {
            $search = '%' . $wpdb->esc_like($args['search']) . '%';
            $where[] = '(s.email LIKE %s OR s.nombre LIKE %s)';
            $params[] = $search;
            $params[] = $search;
        }

        $where_sql = implode(' AND ', $where);

        // Contar total
        $count_sql = "SELECT COUNT(*) FROM {$table} s WHERE {$where_sql}";
        $total = $wpdb->get_var($wpdb->prepare($count_sql, $params));

        // Obtener registros
        $offset = ($args['page'] - 1) * $args['per_page'];
        $orderby = sanitize_sql_orderby($args['orderby'] . ' ' . $args['order']) ?: 'created_at DESC';

        $sql = "SELECT s.* FROM {$table} s WHERE {$where_sql} ORDER BY {$orderby} LIMIT %d OFFSET %d";
        $params[] = $args['per_page'];
        $params[] = $offset;

        $subscribers = $wpdb->get_results($wpdb->prepare($sql, $params), ARRAY_A);

        return [
            'items' => $subscribers,
            'total' => (int) $total,
            'pages' => ceil($total / $args['per_page']),
            'page' => $args['page'],
        ];
    }

    /**
     * Obtener un suscriptor por ID
     *
     * @param int $id ID del suscriptor
     * @return array|null
     */
    public function get_subscriber($id) {
        global $wpdb;

        $table = $wpdb->prefix . self::TABLE_PREFIX . 'suscriptores';

        return $wpdb->get_row(
            $wpdb->prepare("SELECT * FROM {$table} WHERE id = %d", $id),
            ARRAY_A
        );
    }

    /**
     * Obtener suscriptor por email
     *
     * @param string $email Email del suscriptor
     * @return array|null
     */
    public function get_by_email($email) {
        global $wpdb;

        $table = $wpdb->prefix . self::TABLE_PREFIX . 'suscriptores';

        return $wpdb->get_row(
            $wpdb->prepare("SELECT * FROM {$table} WHERE email = %s", $email),
            ARRAY_A
        );
    }

    /**
     * Crear suscriptor
     *
     * @param array $data Datos del suscriptor
     * @return int|WP_Error ID del suscriptor o error
     */
    public function create($data) {
        global $wpdb;

        // Validar email
        if (empty($data['email']) || !is_email($data['email'])) {
            return new WP_Error('invalid_email', __('Email inválido', FLAVOR_PLATFORM_TEXT_DOMAIN));
        }

        // Verificar duplicado
        if ($this->get_by_email($data['email'])) {
            return new WP_Error('duplicate_email', __('El email ya está registrado', FLAVOR_PLATFORM_TEXT_DOMAIN));
        }

        $table = $wpdb->prefix . self::TABLE_PREFIX . 'suscriptores';

        $insert_data = [
            'email' => sanitize_email($data['email']),
            'nombre' => sanitize_text_field($data['nombre'] ?? ''),
            'estado' => in_array($data['estado'] ?? '', self::VALID_STATUSES) ? $data['estado'] : 'pendiente',
            'ip_registro' => $this->get_client_ip(),
            'fuente' => sanitize_text_field($data['fuente'] ?? 'api'),
            'campos_extra' => isset($data['campos_extra']) ? wp_json_encode($data['campos_extra']) : null,
            'created_at' => current_time('mysql'),
            'updated_at' => current_time('mysql'),
        ];

        $result = $wpdb->insert($table, $insert_data);

        if ($result === false) {
            return new WP_Error('db_error', __('Error al crear suscriptor', FLAVOR_PLATFORM_TEXT_DOMAIN));
        }

        $subscriber_id = $wpdb->insert_id;

        // Agregar a listas si se especificaron
        if (!empty($data['listas'])) {
            $this->add_to_lists($subscriber_id, (array) $data['listas']);
        }

        do_action('flavor_em_subscriber_created', $subscriber_id, $data);

        return $subscriber_id;
    }

    /**
     * Actualizar suscriptor
     *
     * @param int   $id   ID del suscriptor
     * @param array $data Datos a actualizar
     * @return bool|WP_Error
     */
    public function update($id, $data) {
        global $wpdb;

        $table = $wpdb->prefix . self::TABLE_PREFIX . 'suscriptores';

        $update_data = [];

        if (isset($data['nombre'])) {
            $update_data['nombre'] = sanitize_text_field($data['nombre']);
        }

        if (isset($data['estado']) && in_array($data['estado'], self::VALID_STATUSES)) {
            $update_data['estado'] = $data['estado'];
        }

        if (isset($data['campos_extra'])) {
            $update_data['campos_extra'] = wp_json_encode($data['campos_extra']);
        }

        if (empty($update_data)) {
            return new WP_Error('no_data', __('No hay datos para actualizar', FLAVOR_PLATFORM_TEXT_DOMAIN));
        }

        $update_data['updated_at'] = current_time('mysql');

        $result = $wpdb->update($table, $update_data, ['id' => $id]);

        if ($result === false) {
            return new WP_Error('db_error', __('Error al actualizar suscriptor', FLAVOR_PLATFORM_TEXT_DOMAIN));
        }

        do_action('flavor_em_subscriber_updated', $id, $data);

        return true;
    }

    /**
     * Eliminar suscriptor
     *
     * @param int $id ID del suscriptor
     * @return bool|WP_Error
     */
    public function delete($id) {
        global $wpdb;

        $table = $wpdb->prefix . self::TABLE_PREFIX . 'suscriptores';
        $table_rel = $wpdb->prefix . self::TABLE_PREFIX . 'suscriptor_lista';

        // Eliminar relaciones con listas
        $wpdb->delete($table_rel, ['suscriptor_id' => $id]);

        // Eliminar suscriptor
        $result = $wpdb->delete($table, ['id' => $id]);

        if ($result === false) {
            return new WP_Error('db_error', __('Error al eliminar suscriptor', FLAVOR_PLATFORM_TEXT_DOMAIN));
        }

        do_action('flavor_em_subscriber_deleted', $id);

        return true;
    }

    /**
     * Agregar suscriptor a listas
     *
     * @param int   $subscriber_id ID del suscriptor
     * @param array $list_ids      IDs de listas
     */
    public function add_to_lists($subscriber_id, $list_ids) {
        global $wpdb;

        $table = $wpdb->prefix . self::TABLE_PREFIX . 'suscriptor_lista';

        foreach ($list_ids as $list_id) {
            $wpdb->replace($table, [
                'suscriptor_id' => $subscriber_id,
                'lista_id' => absint($list_id),
                'fecha_suscripcion' => current_time('mysql'),
            ]);
        }
    }

    /**
     * Remover suscriptor de listas
     *
     * @param int   $subscriber_id ID del suscriptor
     * @param array $list_ids      IDs de listas
     */
    public function remove_from_lists($subscriber_id, $list_ids) {
        global $wpdb;

        $table = $wpdb->prefix . self::TABLE_PREFIX . 'suscriptor_lista';

        foreach ($list_ids as $list_id) {
            $wpdb->delete($table, [
                'suscriptor_id' => $subscriber_id,
                'lista_id' => absint($list_id),
            ]);
        }
    }

    /**
     * Obtener listas de un suscriptor
     *
     * @param int $subscriber_id ID del suscriptor
     * @return array
     */
    public function get_subscriber_lists($subscriber_id) {
        global $wpdb;

        $table_rel = $wpdb->prefix . self::TABLE_PREFIX . 'suscriptor_lista';
        $table_lists = $wpdb->prefix . self::TABLE_PREFIX . 'listas';

        return $wpdb->get_results($wpdb->prepare(
            "SELECT l.* FROM {$table_lists} l
             INNER JOIN {$table_rel} sl ON l.id = sl.lista_id
             WHERE sl.suscriptor_id = %d",
            $subscriber_id
        ), ARRAY_A);
    }

    /**
     * Dar de baja suscriptor
     *
     * @param string $email Email del suscriptor
     * @param string $reason Razón de la baja
     * @return bool|WP_Error
     */
    public function unsubscribe($email, $reason = '') {
        $subscriber = $this->get_by_email($email);

        if (!$subscriber) {
            return new WP_Error('not_found', __('Suscriptor no encontrado', FLAVOR_PLATFORM_TEXT_DOMAIN));
        }

        return $this->update($subscriber['id'], [
            'estado' => 'baja',
            'campos_extra' => array_merge(
                json_decode($subscriber['campos_extra'] ?? '{}', true) ?: [],
                ['razon_baja' => $reason, 'fecha_baja' => current_time('mysql')]
            ),
        ]);
    }

    /**
     * Obtener IP del cliente
     *
     * @return string
     */
    private function get_client_ip() {
        $ip_keys = ['HTTP_CF_CONNECTING_IP', 'HTTP_X_FORWARDED_FOR', 'REMOTE_ADDR'];

        foreach ($ip_keys as $key) {
            if (!empty($_SERVER[$key])) {
                $ip = explode(',', sanitize_text_field($_SERVER[$key]))[0];
                if (filter_var($ip, FILTER_VALIDATE_IP)) {
                    return $ip;
                }
            }
        }

        return '0.0.0.0';
    }

    /**
     * Contar suscriptores por estado
     *
     * @return array
     */
    public function count_by_status() {
        global $wpdb;

        $table = $wpdb->prefix . self::TABLE_PREFIX . 'suscriptores';

        $results = $wpdb->get_results(
            "SELECT estado, COUNT(*) as count FROM {$table} GROUP BY estado",
            ARRAY_A
        );

        $counts = array_fill_keys(self::VALID_STATUSES, 0);
        foreach ($results as $row) {
            $counts[$row['estado']] = (int) $row['count'];
        }

        return $counts;
    }
}

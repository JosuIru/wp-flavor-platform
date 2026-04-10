<?php
/**
 * Gestor de Listas para Email Marketing
 *
 * Maneja todas las operaciones CRUD de listas de suscriptores.
 *
 * @package FlavorPlatform
 * @subpackage EmailMarketing
 */

if (!defined('ABSPATH')) {
    exit;
}

class Flavor_EM_List_Manager {

    /**
     * Prefijo de tablas
     */
    const TABLE_PREFIX = 'flavor_em_';

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
     * Obtener todas las listas
     *
     * @param array $args Argumentos de consulta
     * @return array
     */
    public function get_lists($args = []) {
        global $wpdb;

        $defaults = [
            'page' => 1,
            'per_page' => 50,
            'search' => '',
            'orderby' => 'nombre',
            'order' => 'ASC',
            'include_counts' => true,
        ];

        $args = wp_parse_args($args, $defaults);
        $table = $wpdb->prefix . self::TABLE_PREFIX . 'listas';

        $where = ['1=1'];
        $params = [];

        if (!empty($args['search'])) {
            $search = '%' . $wpdb->esc_like($args['search']) . '%';
            $where[] = '(nombre LIKE %s OR descripcion LIKE %s)';
            $params[] = $search;
            $params[] = $search;
        }

        $where_sql = implode(' AND ', $where);

        // Contar total
        $count_sql = "SELECT COUNT(*) FROM {$table} WHERE {$where_sql}";
        $total = empty($params) ? $wpdb->get_var($count_sql) : $wpdb->get_var($wpdb->prepare($count_sql, $params));

        // Obtener registros
        $offset = ($args['page'] - 1) * $args['per_page'];
        $orderby = sanitize_sql_orderby($args['orderby'] . ' ' . $args['order']) ?: 'nombre ASC';

        $sql = "SELECT * FROM {$table} WHERE {$where_sql} ORDER BY {$orderby} LIMIT %d OFFSET %d";
        $params[] = $args['per_page'];
        $params[] = $offset;

        $lists = $wpdb->get_results($wpdb->prepare($sql, $params), ARRAY_A);

        // Agregar conteos si se solicitan
        if ($args['include_counts']) {
            foreach ($lists as &$list) {
                $list['subscriber_count'] = $this->get_subscriber_count($list['id']);
            }
        }

        return [
            'items' => $lists,
            'total' => (int) $total,
            'pages' => ceil($total / $args['per_page']),
            'page' => $args['page'],
        ];
    }

    /**
     * Obtener una lista por ID
     *
     * @param int $id ID de la lista
     * @return array|null
     */
    public function get_list($id) {
        global $wpdb;

        $table = $wpdb->prefix . self::TABLE_PREFIX . 'listas';

        $list = $wpdb->get_row(
            $wpdb->prepare("SELECT * FROM {$table} WHERE id = %d", $id),
            ARRAY_A
        );

        if ($list) {
            $list['subscriber_count'] = $this->get_subscriber_count($id);
            $list['stats'] = $this->get_list_stats($id);
        }

        return $list;
    }

    /**
     * Crear lista
     *
     * @param array $data Datos de la lista
     * @return int|WP_Error ID de la lista o error
     */
    public function create($data) {
        global $wpdb;

        if (empty($data['nombre'])) {
            return new WP_Error('missing_name', __('El nombre es obligatorio', FLAVOR_PLATFORM_TEXT_DOMAIN));
        }

        $table = $wpdb->prefix . self::TABLE_PREFIX . 'listas';

        // Verificar nombre duplicado
        $exists = $wpdb->get_var($wpdb->prepare(
            "SELECT id FROM {$table} WHERE nombre = %s",
            $data['nombre']
        ));

        if ($exists) {
            return new WP_Error('duplicate_name', __('Ya existe una lista con ese nombre', FLAVOR_PLATFORM_TEXT_DOMAIN));
        }

        $insert_data = [
            'nombre' => sanitize_text_field($data['nombre']),
            'descripcion' => sanitize_textarea_field($data['descripcion'] ?? ''),
            'slug' => sanitize_title($data['nombre']),
            'tipo' => sanitize_text_field($data['tipo'] ?? 'normal'),
            'doble_optin' => !empty($data['doble_optin']) ? 1 : 0,
            'configuracion' => isset($data['configuracion']) ? wp_json_encode($data['configuracion']) : null,
            'created_at' => current_time('mysql'),
            'updated_at' => current_time('mysql'),
        ];

        $result = $wpdb->insert($table, $insert_data);

        if ($result === false) {
            return new WP_Error('db_error', __('Error al crear lista', FLAVOR_PLATFORM_TEXT_DOMAIN));
        }

        $list_id = $wpdb->insert_id;

        do_action('flavor_em_list_created', $list_id, $data);

        return $list_id;
    }

    /**
     * Actualizar lista
     *
     * @param int   $id   ID de la lista
     * @param array $data Datos a actualizar
     * @return bool|WP_Error
     */
    public function update($id, $data) {
        global $wpdb;

        $list = $this->get_list($id);
        if (!$list) {
            return new WP_Error('not_found', __('Lista no encontrada', FLAVOR_PLATFORM_TEXT_DOMAIN));
        }

        $table = $wpdb->prefix . self::TABLE_PREFIX . 'listas';

        $update_data = [];

        if (isset($data['nombre'])) {
            // Verificar nombre duplicado
            $exists = $wpdb->get_var($wpdb->prepare(
                "SELECT id FROM {$table} WHERE nombre = %s AND id != %d",
                $data['nombre'],
                $id
            ));

            if ($exists) {
                return new WP_Error('duplicate_name', __('Ya existe una lista con ese nombre', FLAVOR_PLATFORM_TEXT_DOMAIN));
            }

            $update_data['nombre'] = sanitize_text_field($data['nombre']);
            $update_data['slug'] = sanitize_title($data['nombre']);
        }

        if (isset($data['descripcion'])) {
            $update_data['descripcion'] = sanitize_textarea_field($data['descripcion']);
        }

        if (isset($data['tipo'])) {
            $update_data['tipo'] = sanitize_text_field($data['tipo']);
        }

        if (isset($data['doble_optin'])) {
            $update_data['doble_optin'] = !empty($data['doble_optin']) ? 1 : 0;
        }

        if (isset($data['configuracion'])) {
            $update_data['configuracion'] = wp_json_encode($data['configuracion']);
        }

        if (empty($update_data)) {
            return new WP_Error('no_data', __('No hay datos para actualizar', FLAVOR_PLATFORM_TEXT_DOMAIN));
        }

        $update_data['updated_at'] = current_time('mysql');

        $result = $wpdb->update($table, $update_data, ['id' => $id]);

        if ($result === false) {
            return new WP_Error('db_error', __('Error al actualizar lista', FLAVOR_PLATFORM_TEXT_DOMAIN));
        }

        do_action('flavor_em_list_updated', $id, $data);

        return true;
    }

    /**
     * Eliminar lista
     *
     * @param int  $id               ID de la lista
     * @param bool $delete_subscribers Si eliminar también los suscriptores
     * @return bool|WP_Error
     */
    public function delete($id, $delete_subscribers = false) {
        global $wpdb;

        $list = $this->get_list($id);
        if (!$list) {
            return new WP_Error('not_found', __('Lista no encontrada', FLAVOR_PLATFORM_TEXT_DOMAIN));
        }

        // Verificar si es lista del sistema
        if ($list['tipo'] === 'sistema') {
            return new WP_Error('system_list', __('No se pueden eliminar listas del sistema', FLAVOR_PLATFORM_TEXT_DOMAIN));
        }

        $table = $wpdb->prefix . self::TABLE_PREFIX . 'listas';
        $table_rel = $wpdb->prefix . self::TABLE_PREFIX . 'suscriptor_lista';
        $table_suscriptores = $wpdb->prefix . self::TABLE_PREFIX . 'suscriptores';

        // Eliminar suscriptores si se solicita
        if ($delete_subscribers) {
            $subscriber_ids = $wpdb->get_col($wpdb->prepare(
                "SELECT suscriptor_id FROM {$table_rel} WHERE lista_id = %d",
                $id
            ));

            if (!empty($subscriber_ids)) {
                $ids_placeholder = implode(',', array_fill(0, count($subscriber_ids), '%d'));
                $wpdb->query($wpdb->prepare(
                    "DELETE FROM {$table_suscriptores} WHERE id IN ({$ids_placeholder})",
                    $subscriber_ids
                ));
            }
        }

        // Eliminar relaciones
        $wpdb->delete($table_rel, ['lista_id' => $id]);

        // Eliminar lista
        $result = $wpdb->delete($table, ['id' => $id]);

        if ($result === false) {
            return new WP_Error('db_error', __('Error al eliminar lista', FLAVOR_PLATFORM_TEXT_DOMAIN));
        }

        do_action('flavor_em_list_deleted', $id);

        return true;
    }

    /**
     * Obtener conteo de suscriptores de una lista
     *
     * @param int    $id     ID de la lista
     * @param string $status Estado a filtrar (opcional)
     * @return int
     */
    public function get_subscriber_count($id, $status = null) {
        global $wpdb;

        $table_rel = $wpdb->prefix . self::TABLE_PREFIX . 'suscriptor_lista';
        $table_suscriptores = $wpdb->prefix . self::TABLE_PREFIX . 'suscriptores';

        $sql = "SELECT COUNT(*) FROM {$table_rel} sl
                INNER JOIN {$table_suscriptores} s ON sl.suscriptor_id = s.id
                WHERE sl.lista_id = %d";

        $params = [$id];

        if ($status) {
            $sql .= " AND s.estado = %s";
            $params[] = $status;
        }

        return (int) $wpdb->get_var($wpdb->prepare($sql, $params));
    }

    /**
     * Obtener estadísticas de una lista
     *
     * @param int $id ID de la lista
     * @return array
     */
    public function get_list_stats($id) {
        global $wpdb;

        $table_rel = $wpdb->prefix . self::TABLE_PREFIX . 'suscriptor_lista';
        $table_suscriptores = $wpdb->prefix . self::TABLE_PREFIX . 'suscriptores';

        $stats = $wpdb->get_row($wpdb->prepare(
            "SELECT
                COUNT(*) as total,
                SUM(CASE WHEN s.estado = 'activo' THEN 1 ELSE 0 END) as activos,
                SUM(CASE WHEN s.estado = 'pendiente' THEN 1 ELSE 0 END) as pendientes,
                SUM(CASE WHEN s.estado = 'baja' THEN 1 ELSE 0 END) as bajas,
                SUM(CASE WHEN s.estado = 'rebotado' THEN 1 ELSE 0 END) as rebotados,
                SUM(CASE WHEN DATE(sl.fecha_suscripcion) = CURDATE() THEN 1 ELSE 0 END) as nuevos_hoy,
                SUM(CASE WHEN sl.fecha_suscripcion >= DATE_SUB(NOW(), INTERVAL 7 DAY) THEN 1 ELSE 0 END) as nuevos_semana
             FROM {$table_rel} sl
             INNER JOIN {$table_suscriptores} s ON sl.suscriptor_id = s.id
             WHERE sl.lista_id = %d",
            $id
        ), ARRAY_A);

        return [
            'total' => (int) ($stats['total'] ?? 0),
            'activos' => (int) ($stats['activos'] ?? 0),
            'pendientes' => (int) ($stats['pendientes'] ?? 0),
            'bajas' => (int) ($stats['bajas'] ?? 0),
            'rebotados' => (int) ($stats['rebotados'] ?? 0),
            'nuevos_hoy' => (int) ($stats['nuevos_hoy'] ?? 0),
            'nuevos_semana' => (int) ($stats['nuevos_semana'] ?? 0),
        ];
    }

    /**
     * Obtener listas de selección (para dropdowns)
     *
     * @return array [id => nombre]
     */
    public function get_lists_for_select() {
        global $wpdb;

        $table = $wpdb->prefix . self::TABLE_PREFIX . 'listas';

        $results = $wpdb->get_results(
            "SELECT id, nombre FROM {$table} ORDER BY nombre ASC",
            ARRAY_A
        );

        $options = [];
        foreach ($results as $row) {
            $options[$row['id']] = $row['nombre'];
        }

        return $options;
    }

    /**
     * Obtener o crear lista por nombre
     *
     * @param string $name Nombre de la lista
     * @return int ID de la lista
     */
    public function get_or_create_by_name($name) {
        global $wpdb;

        $table = $wpdb->prefix . self::TABLE_PREFIX . 'listas';

        $id = $wpdb->get_var($wpdb->prepare(
            "SELECT id FROM {$table} WHERE nombre = %s",
            $name
        ));

        if ($id) {
            return (int) $id;
        }

        return $this->create(['nombre' => $name]);
    }

    /**
     * Importar suscriptores a una lista
     *
     * @param int   $list_id ID de la lista
     * @param array $emails  Array de emails o arrays [email => nombre]
     * @return array Resultado de la importación
     */
    public function import_subscribers($list_id, $emails) {
        $subscriber_manager = Flavor_EM_Subscriber_Manager::get_instance();

        $results = [
            'imported' => 0,
            'duplicates' => 0,
            'errors' => 0,
            'details' => [],
        ];

        foreach ($emails as $email_data) {
            if (is_string($email_data)) {
                $email = $email_data;
                $name = '';
            } else {
                $email = $email_data['email'] ?? '';
                $name = $email_data['nombre'] ?? '';
            }

            if (!is_email($email)) {
                $results['errors']++;
                $results['details'][] = sprintf(__('Email inválido: %s', FLAVOR_PLATFORM_TEXT_DOMAIN), $email);
                continue;
            }

            $existing = $subscriber_manager->get_by_email($email);

            if ($existing) {
                // Agregar a la lista si no está
                $subscriber_manager->add_to_lists($existing['id'], [$list_id]);
                $results['duplicates']++;
            } else {
                $subscriber_id = $subscriber_manager->create([
                    'email' => $email,
                    'nombre' => $name,
                    'estado' => 'activo',
                    'fuente' => 'import',
                    'listas' => [$list_id],
                ]);

                if (is_wp_error($subscriber_id)) {
                    $results['errors']++;
                    $results['details'][] = $subscriber_id->get_error_message();
                } else {
                    $results['imported']++;
                }
            }
        }

        return $results;
    }
}

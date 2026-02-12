<?php
/**
 * Gestor de Campañas para Email Marketing
 *
 * Maneja todas las operaciones CRUD de campañas de email.
 *
 * @package FlavorChatIA
 * @subpackage EmailMarketing
 */

if (!defined('ABSPATH')) {
    exit;
}

class Flavor_EM_Campaign_Manager {

    /**
     * Prefijo de tablas
     */
    const TABLE_PREFIX = 'flavor_em_';

    /**
     * Estados válidos de campaña
     */
    const VALID_STATUSES = ['borrador', 'programada', 'enviando', 'enviada', 'pausada', 'cancelada'];

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
     * Obtener campañas con filtros y paginación
     *
     * @param array $args Argumentos de consulta
     * @return array
     */
    public function get_campaigns($args = []) {
        global $wpdb;

        $defaults = [
            'page' => 1,
            'per_page' => 20,
            'estado' => null,
            'lista_id' => null,
            'search' => '',
            'orderby' => 'created_at',
            'order' => 'DESC',
        ];

        $args = wp_parse_args($args, $defaults);
        $table = $wpdb->prefix . self::TABLE_PREFIX . 'campanias';

        $where = ['1=1'];
        $params = [];

        // Filtro por estado
        if (!empty($args['estado']) && in_array($args['estado'], self::VALID_STATUSES)) {
            $where[] = 'estado = %s';
            $params[] = $args['estado'];
        }

        // Filtro por lista
        if (!empty($args['lista_id'])) {
            $where[] = 'lista_id = %d';
            $params[] = absint($args['lista_id']);
        }

        // Búsqueda
        if (!empty($args['search'])) {
            $search = '%' . $wpdb->esc_like($args['search']) . '%';
            $where[] = '(asunto LIKE %s OR nombre LIKE %s)';
            $params[] = $search;
            $params[] = $search;
        }

        $where_sql = implode(' AND ', $where);

        // Contar total
        $count_sql = "SELECT COUNT(*) FROM {$table} WHERE {$where_sql}";
        $total = empty($params) ? $wpdb->get_var($count_sql) : $wpdb->get_var($wpdb->prepare($count_sql, $params));

        // Obtener registros
        $offset = ($args['page'] - 1) * $args['per_page'];
        $orderby = sanitize_sql_orderby($args['orderby'] . ' ' . $args['order']) ?: 'created_at DESC';

        $sql = "SELECT * FROM {$table} WHERE {$where_sql} ORDER BY {$orderby} LIMIT %d OFFSET %d";
        $params[] = $args['per_page'];
        $params[] = $offset;

        $campaigns = $wpdb->get_results($wpdb->prepare($sql, $params), ARRAY_A);

        // Agregar estadísticas a cada campaña
        foreach ($campaigns as &$campaign) {
            $campaign['stats'] = $this->get_campaign_stats($campaign['id']);
        }

        return [
            'items' => $campaigns,
            'total' => (int) $total,
            'pages' => ceil($total / $args['per_page']),
            'page' => $args['page'],
        ];
    }

    /**
     * Obtener una campaña por ID
     *
     * @param int $id ID de la campaña
     * @return array|null
     */
    public function get_campaign($id) {
        global $wpdb;

        $table = $wpdb->prefix . self::TABLE_PREFIX . 'campanias';

        $campaign = $wpdb->get_row(
            $wpdb->prepare("SELECT * FROM {$table} WHERE id = %d", $id),
            ARRAY_A
        );

        if ($campaign) {
            $campaign['stats'] = $this->get_campaign_stats($id);
        }

        return $campaign;
    }

    /**
     * Crear campaña
     *
     * @param array $data Datos de la campaña
     * @return int|WP_Error ID de la campaña o error
     */
    public function create($data) {
        global $wpdb;

        // Validaciones
        if (empty($data['nombre'])) {
            return new WP_Error('missing_name', __('El nombre es obligatorio', 'flavor-chat-ia'));
        }

        if (empty($data['asunto'])) {
            return new WP_Error('missing_subject', __('El asunto es obligatorio', 'flavor-chat-ia'));
        }

        $table = $wpdb->prefix . self::TABLE_PREFIX . 'campanias';

        $insert_data = [
            'nombre' => sanitize_text_field($data['nombre']),
            'asunto' => sanitize_text_field($data['asunto']),
            'contenido_html' => wp_kses_post($data['contenido_html'] ?? ''),
            'contenido_texto' => sanitize_textarea_field($data['contenido_texto'] ?? ''),
            'lista_id' => absint($data['lista_id'] ?? 0),
            'plantilla_id' => absint($data['plantilla_id'] ?? 0),
            'remitente_nombre' => sanitize_text_field($data['remitente_nombre'] ?? ''),
            'remitente_email' => sanitize_email($data['remitente_email'] ?? ''),
            'estado' => 'borrador',
            'programada_para' => !empty($data['programada_para']) ? sanitize_text_field($data['programada_para']) : null,
            'configuracion' => isset($data['configuracion']) ? wp_json_encode($data['configuracion']) : null,
            'created_at' => current_time('mysql'),
            'updated_at' => current_time('mysql'),
        ];

        $result = $wpdb->insert($table, $insert_data);

        if ($result === false) {
            return new WP_Error('db_error', __('Error al crear campaña', 'flavor-chat-ia'));
        }

        $campaign_id = $wpdb->insert_id;

        do_action('flavor_em_campaign_created', $campaign_id, $data);

        return $campaign_id;
    }

    /**
     * Actualizar campaña
     *
     * @param int   $id   ID de la campaña
     * @param array $data Datos a actualizar
     * @return bool|WP_Error
     */
    public function update($id, $data) {
        global $wpdb;

        $campaign = $this->get_campaign($id);
        if (!$campaign) {
            return new WP_Error('not_found', __('Campaña no encontrada', 'flavor-chat-ia'));
        }

        // No permitir editar campañas enviadas
        if (in_array($campaign['estado'], ['enviando', 'enviada'])) {
            return new WP_Error('not_editable', __('No se puede editar una campaña enviada', 'flavor-chat-ia'));
        }

        $table = $wpdb->prefix . self::TABLE_PREFIX . 'campanias';

        $update_data = [];

        $allowed_fields = ['nombre', 'asunto', 'contenido_html', 'contenido_texto',
                          'lista_id', 'plantilla_id', 'remitente_nombre', 'remitente_email',
                          'programada_para', 'configuracion'];

        foreach ($allowed_fields as $field) {
            if (isset($data[$field])) {
                switch ($field) {
                    case 'nombre':
                    case 'asunto':
                    case 'remitente_nombre':
                        $update_data[$field] = sanitize_text_field($data[$field]);
                        break;
                    case 'remitente_email':
                        $update_data[$field] = sanitize_email($data[$field]);
                        break;
                    case 'contenido_html':
                        $update_data[$field] = wp_kses_post($data[$field]);
                        break;
                    case 'contenido_texto':
                        $update_data[$field] = sanitize_textarea_field($data[$field]);
                        break;
                    case 'lista_id':
                    case 'plantilla_id':
                        $update_data[$field] = absint($data[$field]);
                        break;
                    case 'configuracion':
                        $update_data[$field] = wp_json_encode($data[$field]);
                        break;
                    default:
                        $update_data[$field] = $data[$field];
                }
            }
        }

        if (empty($update_data)) {
            return new WP_Error('no_data', __('No hay datos para actualizar', 'flavor-chat-ia'));
        }

        $update_data['updated_at'] = current_time('mysql');

        $result = $wpdb->update($table, $update_data, ['id' => $id]);

        if ($result === false) {
            return new WP_Error('db_error', __('Error al actualizar campaña', 'flavor-chat-ia'));
        }

        do_action('flavor_em_campaign_updated', $id, $data);

        return true;
    }

    /**
     * Eliminar campaña
     *
     * @param int $id ID de la campaña
     * @return bool|WP_Error
     */
    public function delete($id) {
        global $wpdb;

        $campaign = $this->get_campaign($id);
        if (!$campaign) {
            return new WP_Error('not_found', __('Campaña no encontrada', 'flavor-chat-ia'));
        }

        // No permitir eliminar campañas en proceso
        if ($campaign['estado'] === 'enviando') {
            return new WP_Error('in_progress', __('No se puede eliminar una campaña en proceso', 'flavor-chat-ia'));
        }

        $table = $wpdb->prefix . self::TABLE_PREFIX . 'campanias';

        $result = $wpdb->delete($table, ['id' => $id]);

        if ($result === false) {
            return new WP_Error('db_error', __('Error al eliminar campaña', 'flavor-chat-ia'));
        }

        do_action('flavor_em_campaign_deleted', $id);

        return true;
    }

    /**
     * Programar campaña
     *
     * @param int    $id       ID de la campaña
     * @param string $datetime Fecha y hora de envío
     * @return bool|WP_Error
     */
    public function schedule($id, $datetime) {
        global $wpdb;

        $campaign = $this->get_campaign($id);
        if (!$campaign) {
            return new WP_Error('not_found', __('Campaña no encontrada', 'flavor-chat-ia'));
        }

        if ($campaign['estado'] !== 'borrador') {
            return new WP_Error('invalid_status', __('Solo se pueden programar campañas en borrador', 'flavor-chat-ia'));
        }

        $table = $wpdb->prefix . self::TABLE_PREFIX . 'campanias';

        $result = $wpdb->update($table, [
            'estado' => 'programada',
            'programada_para' => sanitize_text_field($datetime),
            'updated_at' => current_time('mysql'),
        ], ['id' => $id]);

        if ($result === false) {
            return new WP_Error('db_error', __('Error al programar campaña', 'flavor-chat-ia'));
        }

        do_action('flavor_em_campaign_scheduled', $id, $datetime);

        return true;
    }

    /**
     * Cambiar estado de campaña
     *
     * @param int    $id     ID de la campaña
     * @param string $status Nuevo estado
     * @return bool|WP_Error
     */
    public function change_status($id, $status) {
        global $wpdb;

        if (!in_array($status, self::VALID_STATUSES)) {
            return new WP_Error('invalid_status', __('Estado inválido', 'flavor-chat-ia'));
        }

        $table = $wpdb->prefix . self::TABLE_PREFIX . 'campanias';

        $update_data = [
            'estado' => $status,
            'updated_at' => current_time('mysql'),
        ];

        if ($status === 'enviada') {
            $update_data['enviada_at'] = current_time('mysql');
        }

        $result = $wpdb->update($table, $update_data, ['id' => $id]);

        if ($result === false) {
            return new WP_Error('db_error', __('Error al cambiar estado', 'flavor-chat-ia'));
        }

        do_action('flavor_em_campaign_status_changed', $id, $status);

        return true;
    }

    /**
     * Obtener estadísticas de una campaña
     *
     * @param int $id ID de la campaña
     * @return array
     */
    public function get_campaign_stats($id) {
        global $wpdb;

        $table_envios = $wpdb->prefix . self::TABLE_PREFIX . 'envios';
        $table_eventos = $wpdb->prefix . self::TABLE_PREFIX . 'eventos';

        // Obtener conteos de envíos
        $envios = $wpdb->get_row($wpdb->prepare(
            "SELECT
                COUNT(*) as total,
                SUM(CASE WHEN estado = 'enviado' THEN 1 ELSE 0 END) as enviados,
                SUM(CASE WHEN estado = 'fallido' THEN 1 ELSE 0 END) as fallidos,
                SUM(CASE WHEN estado = 'pendiente' THEN 1 ELSE 0 END) as pendientes
             FROM {$table_envios} WHERE campania_id = %d",
            $id
        ), ARRAY_A);

        // Obtener conteos de eventos
        $eventos = $wpdb->get_results($wpdb->prepare(
            "SELECT tipo, COUNT(*) as count FROM {$table_eventos} WHERE campania_id = %d GROUP BY tipo",
            $id
        ), ARRAY_A);

        $evento_counts = [];
        foreach ($eventos as $evento) {
            $evento_counts[$evento['tipo']] = (int) $evento['count'];
        }

        $total = (int) ($envios['total'] ?? 0);
        $enviados = (int) ($envios['enviados'] ?? 0);
        $aperturas = $evento_counts['apertura'] ?? 0;
        $clicks = $evento_counts['click'] ?? 0;

        return [
            'total_destinatarios' => $total,
            'enviados' => $enviados,
            'fallidos' => (int) ($envios['fallidos'] ?? 0),
            'pendientes' => (int) ($envios['pendientes'] ?? 0),
            'aperturas' => $aperturas,
            'clicks' => $clicks,
            'bajas' => $evento_counts['baja'] ?? 0,
            'rebotes' => $evento_counts['rebote'] ?? 0,
            'tasa_apertura' => $enviados > 0 ? round(($aperturas / $enviados) * 100, 2) : 0,
            'tasa_click' => $enviados > 0 ? round(($clicks / $enviados) * 100, 2) : 0,
        ];
    }

    /**
     * Obtener campañas programadas para enviar
     *
     * @return array
     */
    public function get_scheduled_for_sending() {
        global $wpdb;

        $table = $wpdb->prefix . self::TABLE_PREFIX . 'campanias';

        return $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM {$table}
             WHERE estado = 'programada'
             AND programada_para <= %s",
            current_time('mysql')
        ), ARRAY_A);
    }

    /**
     * Duplicar campaña
     *
     * @param int $id ID de la campaña a duplicar
     * @return int|WP_Error ID de la nueva campaña
     */
    public function duplicate($id) {
        $campaign = $this->get_campaign($id);

        if (!$campaign) {
            return new WP_Error('not_found', __('Campaña no encontrada', 'flavor-chat-ia'));
        }

        unset($campaign['id'], $campaign['stats'], $campaign['created_at'],
              $campaign['updated_at'], $campaign['enviada_at']);

        $campaign['nombre'] = sprintf(__('%s (copia)', 'flavor-chat-ia'), $campaign['nombre']);
        $campaign['estado'] = 'borrador';
        $campaign['programada_para'] = null;

        return $this->create($campaign);
    }
}

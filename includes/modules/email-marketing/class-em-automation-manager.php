<?php
/**
 * Gestor de Automatizaciones para Email Marketing
 *
 * Maneja todas las operaciones de automatizaciones de email.
 *
 * @package FlavorChatIA
 * @subpackage EmailMarketing
 */

if (!defined('ABSPATH')) {
    exit;
}

class Flavor_EM_Automation_Manager {

    /**
     * Prefijo de tablas
     */
    const TABLE_PREFIX = 'flavor_em_';

    /**
     * Triggers válidos
     */
    const VALID_TRIGGERS = [
        'suscripcion',
        'nuevo_usuario',
        'nuevo_socio',
        'compra_completada',
        'cumpleanos',
        'inactividad',
        'tag_agregado',
        'click_enlace',
        'apertura_email',
        'formulario_enviado',
        'fecha_especifica',
    ];

    /**
     * Acciones válidas
     */
    const VALID_ACTIONS = [
        'enviar_email',
        'agregar_tag',
        'quitar_tag',
        'agregar_lista',
        'quitar_lista',
        'webhook',
        'esperar',
        'condicion',
    ];

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
     * Obtener automatizaciones
     *
     * @param array $args Argumentos
     * @return array
     */
    public function get_automations($args = []) {
        global $wpdb;

        $defaults = [
            'page' => 1,
            'per_page' => 20,
            'activa' => null,
            'trigger' => null,
            'orderby' => 'created_at',
            'order' => 'DESC',
        ];

        $args = wp_parse_args($args, $defaults);
        $table = $wpdb->prefix . self::TABLE_PREFIX . 'automatizaciones';

        $where = ['1=1'];
        $params = [];

        if ($args['activa'] !== null) {
            $where[] = 'activa = %d';
            $params[] = $args['activa'] ? 1 : 0;
        }

        if (!empty($args['trigger']) && in_array($args['trigger'], self::VALID_TRIGGERS)) {
            $where[] = 'trigger_tipo = %s';
            $params[] = $args['trigger'];
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

        $automations = $wpdb->get_results($wpdb->prepare($sql, $params), ARRAY_A);

        // Decodificar campos JSON
        foreach ($automations as &$automation) {
            $automation['acciones'] = json_decode($automation['acciones'] ?? '[]', true);
            $automation['condiciones'] = json_decode($automation['condiciones'] ?? '{}', true);
            $automation['stats'] = $this->get_automation_stats($automation['id']);
        }

        return [
            'items' => $automations,
            'total' => (int) $total,
            'pages' => ceil($total / $args['per_page']),
            'page' => $args['page'],
        ];
    }

    /**
     * Obtener una automatización por ID
     *
     * @param int $id ID
     * @return array|null
     */
    public function get_automation($id) {
        global $wpdb;

        $table = $wpdb->prefix . self::TABLE_PREFIX . 'automatizaciones';

        $automation = $wpdb->get_row(
            $wpdb->prepare("SELECT * FROM {$table} WHERE id = %d", $id),
            ARRAY_A
        );

        if ($automation) {
            $automation['acciones'] = json_decode($automation['acciones'] ?? '[]', true);
            $automation['condiciones'] = json_decode($automation['condiciones'] ?? '{}', true);
            $automation['stats'] = $this->get_automation_stats($id);
        }

        return $automation;
    }

    /**
     * Crear automatización
     *
     * @param array $data Datos
     * @return int|WP_Error
     */
    public function create($data) {
        global $wpdb;

        // Validaciones
        if (empty($data['nombre'])) {
            return new WP_Error('missing_name', __('El nombre es obligatorio', 'flavor-chat-ia'));
        }

        if (empty($data['trigger_tipo']) || !in_array($data['trigger_tipo'], self::VALID_TRIGGERS)) {
            return new WP_Error('invalid_trigger', __('Trigger inválido', 'flavor-chat-ia'));
        }

        $table = $wpdb->prefix . self::TABLE_PREFIX . 'automatizaciones';

        $insert_data = [
            'nombre' => sanitize_text_field($data['nombre']),
            'descripcion' => sanitize_textarea_field($data['descripcion'] ?? ''),
            'trigger_tipo' => $data['trigger_tipo'],
            'trigger_config' => isset($data['trigger_config']) ? wp_json_encode($data['trigger_config']) : null,
            'acciones' => isset($data['acciones']) ? wp_json_encode($data['acciones']) : '[]',
            'condiciones' => isset($data['condiciones']) ? wp_json_encode($data['condiciones']) : '{}',
            'activa' => !empty($data['activa']) ? 1 : 0,
            'created_at' => current_time('mysql'),
            'updated_at' => current_time('mysql'),
        ];

        $result = $wpdb->insert($table, $insert_data);

        if ($result === false) {
            return new WP_Error('db_error', __('Error al crear automatización', 'flavor-chat-ia'));
        }

        $automation_id = $wpdb->insert_id;

        // Registrar hooks si está activa
        if ($insert_data['activa']) {
            $this->register_automation_hook($automation_id, $data['trigger_tipo']);
        }

        do_action('flavor_em_automation_created', $automation_id, $data);

        return $automation_id;
    }

    /**
     * Actualizar automatización
     *
     * @param int   $id   ID
     * @param array $data Datos
     * @return bool|WP_Error
     */
    public function update($id, $data) {
        global $wpdb;

        $automation = $this->get_automation($id);
        if (!$automation) {
            return new WP_Error('not_found', __('Automatización no encontrada', 'flavor-chat-ia'));
        }

        $table = $wpdb->prefix . self::TABLE_PREFIX . 'automatizaciones';

        $update_data = [];

        if (isset($data['nombre'])) {
            $update_data['nombre'] = sanitize_text_field($data['nombre']);
        }

        if (isset($data['descripcion'])) {
            $update_data['descripcion'] = sanitize_textarea_field($data['descripcion']);
        }

        if (isset($data['trigger_tipo']) && in_array($data['trigger_tipo'], self::VALID_TRIGGERS)) {
            $update_data['trigger_tipo'] = $data['trigger_tipo'];
        }

        if (isset($data['trigger_config'])) {
            $update_data['trigger_config'] = wp_json_encode($data['trigger_config']);
        }

        if (isset($data['acciones'])) {
            $update_data['acciones'] = wp_json_encode($data['acciones']);
        }

        if (isset($data['condiciones'])) {
            $update_data['condiciones'] = wp_json_encode($data['condiciones']);
        }

        if (isset($data['activa'])) {
            $update_data['activa'] = !empty($data['activa']) ? 1 : 0;
        }

        if (empty($update_data)) {
            return new WP_Error('no_data', __('No hay datos para actualizar', 'flavor-chat-ia'));
        }

        $update_data['updated_at'] = current_time('mysql');

        $result = $wpdb->update($table, $update_data, ['id' => $id]);

        if ($result === false) {
            return new WP_Error('db_error', __('Error al actualizar automatización', 'flavor-chat-ia'));
        }

        do_action('flavor_em_automation_updated', $id, $data);

        return true;
    }

    /**
     * Eliminar automatización
     *
     * @param int $id ID
     * @return bool|WP_Error
     */
    public function delete($id) {
        global $wpdb;

        $automation = $this->get_automation($id);
        if (!$automation) {
            return new WP_Error('not_found', __('Automatización no encontrada', 'flavor-chat-ia'));
        }

        $table = $wpdb->prefix . self::TABLE_PREFIX . 'automatizaciones';

        $result = $wpdb->delete($table, ['id' => $id]);

        if ($result === false) {
            return new WP_Error('db_error', __('Error al eliminar automatización', 'flavor-chat-ia'));
        }

        do_action('flavor_em_automation_deleted', $id);

        return true;
    }

    /**
     * Activar/desactivar automatización
     *
     * @param int  $id     ID
     * @param bool $active Estado
     * @return bool|WP_Error
     */
    public function toggle_active($id, $active) {
        return $this->update($id, ['activa' => $active]);
    }

    /**
     * Ejecutar automatización para un suscriptor
     *
     * @param int   $automation_id ID de automatización
     * @param int   $subscriber_id ID de suscriptor
     * @param array $context       Contexto adicional
     * @return bool|WP_Error
     */
    public function execute($automation_id, $subscriber_id, $context = []) {
        $automation = $this->get_automation($automation_id);

        if (!$automation || !$automation['activa']) {
            return false;
        }

        // Verificar condiciones
        if (!$this->check_conditions($automation['condiciones'], $subscriber_id, $context)) {
            return false;
        }

        // Ejecutar acciones
        foreach ($automation['acciones'] as $action) {
            $result = $this->execute_action($action, $subscriber_id, $context);

            if (is_wp_error($result)) {
                $this->log_execution($automation_id, $subscriber_id, 'error', $result->get_error_message());
                return $result;
            }

            // Si es una acción de espera, programar continuación
            if ($action['tipo'] === 'esperar') {
                $this->schedule_continuation($automation_id, $subscriber_id, $action, $context);
                break;
            }
        }

        $this->log_execution($automation_id, $subscriber_id, 'success');

        return true;
    }

    /**
     * Verificar condiciones
     *
     * @param array $conditions    Condiciones
     * @param int   $subscriber_id ID de suscriptor
     * @param array $context       Contexto
     * @return bool
     */
    private function check_conditions($conditions, $subscriber_id, $context) {
        if (empty($conditions)) {
            return true;
        }

        $subscriber_manager = Flavor_EM_Subscriber_Manager::get_instance();
        $subscriber = $subscriber_manager->get_subscriber($subscriber_id);

        if (!$subscriber) {
            return false;
        }

        foreach ($conditions as $condition) {
            $field = $condition['field'] ?? '';
            $operator = $condition['operator'] ?? 'equals';
            $value = $condition['value'] ?? '';

            $actual_value = $subscriber[$field] ?? ($context[$field] ?? null);

            if (!$this->evaluate_condition($actual_value, $operator, $value)) {
                return false;
            }
        }

        return true;
    }

    /**
     * Evaluar una condición
     *
     * @param mixed  $actual   Valor actual
     * @param string $operator Operador
     * @param mixed  $expected Valor esperado
     * @return bool
     */
    private function evaluate_condition($actual, $operator, $expected) {
        switch ($operator) {
            case 'equals':
                return $actual == $expected;
            case 'not_equals':
                return $actual != $expected;
            case 'contains':
                return strpos($actual, $expected) !== false;
            case 'not_contains':
                return strpos($actual, $expected) === false;
            case 'greater_than':
                return $actual > $expected;
            case 'less_than':
                return $actual < $expected;
            case 'is_empty':
                return empty($actual);
            case 'is_not_empty':
                return !empty($actual);
            default:
                return true;
        }
    }

    /**
     * Ejecutar una acción
     *
     * @param array $action        Acción
     * @param int   $subscriber_id ID de suscriptor
     * @param array $context       Contexto
     * @return bool|WP_Error
     */
    private function execute_action($action, $subscriber_id, $context) {
        $tipo = $action['tipo'] ?? '';

        switch ($tipo) {
            case 'enviar_email':
                return $this->action_send_email($action, $subscriber_id, $context);

            case 'agregar_lista':
                $subscriber_manager = Flavor_EM_Subscriber_Manager::get_instance();
                $subscriber_manager->add_to_lists($subscriber_id, [$action['lista_id']]);
                return true;

            case 'quitar_lista':
                $subscriber_manager = Flavor_EM_Subscriber_Manager::get_instance();
                $subscriber_manager->remove_from_lists($subscriber_id, [$action['lista_id']]);
                return true;

            case 'agregar_tag':
                return $this->action_add_tag($subscriber_id, $action['tag']);

            case 'quitar_tag':
                return $this->action_remove_tag($subscriber_id, $action['tag']);

            case 'webhook':
                return $this->action_webhook($action, $subscriber_id, $context);

            case 'esperar':
                // Manejado en execute()
                return true;

            default:
                return new WP_Error('invalid_action', __('Acción inválida', 'flavor-chat-ia'));
        }
    }

    /**
     * Acción: Enviar email
     */
    private function action_send_email($action, $subscriber_id, $context) {
        // Implementación delegada al sender
        do_action('flavor_em_automation_send_email', $action, $subscriber_id, $context);
        return true;
    }

    /**
     * Acción: Agregar tag
     */
    private function action_add_tag($subscriber_id, $tag) {
        global $wpdb;

        $table = $wpdb->prefix . self::TABLE_PREFIX . 'suscriptor_tags';

        $wpdb->replace($table, [
            'suscriptor_id' => $subscriber_id,
            'tag' => sanitize_text_field($tag),
            'created_at' => current_time('mysql'),
        ]);

        return true;
    }

    /**
     * Acción: Quitar tag
     */
    private function action_remove_tag($subscriber_id, $tag) {
        global $wpdb;

        $table = $wpdb->prefix . self::TABLE_PREFIX . 'suscriptor_tags';

        $wpdb->delete($table, [
            'suscriptor_id' => $subscriber_id,
            'tag' => sanitize_text_field($tag),
        ]);

        return true;
    }

    /**
     * Acción: Webhook
     */
    private function action_webhook($action, $subscriber_id, $context) {
        $url = $action['url'] ?? '';

        if (empty($url) || !filter_var($url, FILTER_VALIDATE_URL)) {
            return new WP_Error('invalid_url', __('URL de webhook inválida', 'flavor-chat-ia'));
        }

        $subscriber_manager = Flavor_EM_Subscriber_Manager::get_instance();
        $subscriber = $subscriber_manager->get_subscriber($subscriber_id);

        $response = wp_remote_post($url, [
            'body' => wp_json_encode([
                'subscriber' => $subscriber,
                'context' => $context,
                'timestamp' => current_time('mysql'),
            ]),
            'headers' => ['Content-Type' => 'application/json'],
            'timeout' => 10,
        ]);

        if (is_wp_error($response)) {
            return $response;
        }

        return true;
    }

    /**
     * Programar continuación de automatización
     */
    private function schedule_continuation($automation_id, $subscriber_id, $wait_action, $context) {
        $delay = $wait_action['delay'] ?? 3600; // Default 1 hora

        wp_schedule_single_event(
            time() + $delay,
            'flavor_em_automation_continue',
            [$automation_id, $subscriber_id, $context]
        );
    }

    /**
     * Registrar hook para trigger
     */
    private function register_automation_hook($automation_id, $trigger_type) {
        // Los hooks se registran dinámicamente según el tipo de trigger
        update_option("flavor_em_automation_hook_{$automation_id}", $trigger_type);
    }

    /**
     * Obtener estadísticas de automatización
     *
     * @param int $id ID
     * @return array
     */
    public function get_automation_stats($id) {
        global $wpdb;

        $table = $wpdb->prefix . self::TABLE_PREFIX . 'automatizacion_logs';

        if (!$this->table_exists($table)) {
            return [
                'total_executions' => 0,
                'successful' => 0,
                'failed' => 0,
            ];
        }

        $stats = $wpdb->get_row($wpdb->prepare(
            "SELECT
                COUNT(*) as total,
                SUM(CASE WHEN resultado = 'success' THEN 1 ELSE 0 END) as successful,
                SUM(CASE WHEN resultado = 'error' THEN 1 ELSE 0 END) as failed
             FROM {$table} WHERE automatizacion_id = %d",
            $id
        ), ARRAY_A);

        return [
            'total_executions' => (int) ($stats['total'] ?? 0),
            'successful' => (int) ($stats['successful'] ?? 0),
            'failed' => (int) ($stats['failed'] ?? 0),
        ];
    }

    /**
     * Registrar ejecución
     */
    private function log_execution($automation_id, $subscriber_id, $result, $message = '') {
        global $wpdb;

        $table = $wpdb->prefix . self::TABLE_PREFIX . 'automatizacion_logs';

        if (!$this->table_exists($table)) {
            return;
        }

        $wpdb->insert($table, [
            'automatizacion_id' => $automation_id,
            'suscriptor_id' => $subscriber_id,
            'resultado' => $result,
            'mensaje' => $message,
            'created_at' => current_time('mysql'),
        ]);
    }

    /**
     * Verificar si tabla existe
     */
    private function table_exists($table) {
        global $wpdb;
        return $wpdb->get_var("SHOW TABLES LIKE '{$table}'") === $table;
    }
}

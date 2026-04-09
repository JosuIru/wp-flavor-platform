<?php
/**
 * Editor History - Sistema de Undo/Redo
 *
 * @package FlavorChatIA
 */

if (!defined('ABSPATH')) {
    exit;
}

class Flavor_Editor_History {

    /**
     * Instancia singleton
     */
    private static $instance = null;

    /**
     * Máximo de estados en el historial
     */
    const MAX_HISTORY_STATES = 50;

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
     * Constructor
     */
    private function __construct() {
        $this->init_hooks();
    }

    /**
     * Inicializar hooks
     */
    private function init_hooks() {
        add_action('wp_ajax_flavor_editor_save_state', [$this, 'ajax_save_state']);
        add_action('wp_ajax_flavor_editor_get_history', [$this, 'ajax_get_history']);
        add_action('wp_ajax_flavor_editor_undo', [$this, 'ajax_undo']);
        add_action('wp_ajax_flavor_editor_redo', [$this, 'ajax_redo']);
        add_action('wp_ajax_flavor_editor_clear_history', [$this, 'ajax_clear_history']);
    }

    /**
     * Guardar estado en el historial
     */
    public function ajax_save_state() {
        check_ajax_referer('flavor_editor_nonce', 'nonce');

        if (!current_user_can('edit_posts')) {
            wp_send_json_error(['message' => __('Sin permisos', 'flavor-platform')]);
        }

        $post_id = intval($_POST['post_id'] ?? 0);
        $state = $_POST['state'] ?? null;
        $action_label = sanitize_text_field($_POST['action_label'] ?? __('Cambio', 'flavor-platform'));

        if (!$post_id || !$state) {
            wp_send_json_error(['message' => __('Datos incompletos', 'flavor-platform')]);
        }

        $result = $this->save_state($post_id, $state, $action_label);

        wp_send_json_success($result);
    }

    /**
     * Guardar estado
     *
     * @param int $post_id
     * @param array $state
     * @param string $action_label
     * @return array
     */
    public function save_state($post_id, $state, $action_label = 'Cambio') {
        $user_id = get_current_user_id();
        $history_key = "flavor_editor_history_{$post_id}_{$user_id}";
        $pointer_key = "flavor_editor_pointer_{$post_id}_{$user_id}";

        $history = get_transient($history_key);
        if (!is_array($history)) {
            $history = [];
        }

        $pointer = get_transient($pointer_key);
        if ($pointer === false) {
            $pointer = -1;
        }

        // Si hay estados después del puntero actual, eliminarlos (nuevo branch)
        if ($pointer < count($history) - 1) {
            $history = array_slice($history, 0, $pointer + 1);
        }

        // Añadir nuevo estado
        $history[] = [
            'state' => $state,
            'label' => $action_label,
            'timestamp' => current_time('mysql'),
        ];

        // Limitar tamaño del historial
        if (count($history) > self::MAX_HISTORY_STATES) {
            $history = array_slice($history, -self::MAX_HISTORY_STATES);
        }

        // Actualizar puntero al último estado
        $pointer = count($history) - 1;

        // Guardar (transient de 4 horas)
        set_transient($history_key, $history, 4 * HOUR_IN_SECONDS);
        set_transient($pointer_key, $pointer, 4 * HOUR_IN_SECONDS);

        return [
            'history_length' => count($history),
            'current_position' => $pointer,
            'can_undo' => $pointer > 0,
            'can_redo' => false,
        ];
    }

    /**
     * Obtener historial
     */
    public function ajax_get_history() {
        check_ajax_referer('flavor_editor_nonce', 'nonce');

        if (!current_user_can('edit_posts')) {
            wp_send_json_error(['message' => __('Sin permisos', 'flavor-platform')]);
        }

        $post_id = intval($_POST['post_id'] ?? 0);

        if (!$post_id) {
            wp_send_json_error(['message' => __('Post ID requerido', 'flavor-platform')]);
        }

        $user_id = get_current_user_id();
        $history_key = "flavor_editor_history_{$post_id}_{$user_id}";
        $pointer_key = "flavor_editor_pointer_{$post_id}_{$user_id}";

        $history = get_transient($history_key);
        $pointer = get_transient($pointer_key);

        if (!is_array($history)) {
            $history = [];
            $pointer = -1;
        }

        // Solo devolver labels y timestamps para la UI
        $history_list = array_map(function($item, $index) use ($pointer) {
            return [
                'index' => $index,
                'label' => $item['label'],
                'timestamp' => $item['timestamp'],
                'is_current' => $index === $pointer,
            ];
        }, $history, array_keys($history));

        wp_send_json_success([
            'history' => $history_list,
            'current_position' => $pointer,
            'can_undo' => $pointer > 0,
            'can_redo' => $pointer < count($history) - 1,
        ]);
    }

    /**
     * Deshacer (Undo)
     */
    public function ajax_undo() {
        check_ajax_referer('flavor_editor_nonce', 'nonce');

        if (!current_user_can('edit_posts')) {
            wp_send_json_error(['message' => __('Sin permisos', 'flavor-platform')]);
        }

        $post_id = intval($_POST['post_id'] ?? 0);

        if (!$post_id) {
            wp_send_json_error(['message' => __('Post ID requerido', 'flavor-platform')]);
        }

        $result = $this->undo($post_id);

        if ($result === false) {
            wp_send_json_error(['message' => __('No hay más estados para deshacer', 'flavor-platform')]);
        }

        wp_send_json_success($result);
    }

    /**
     * Ejecutar undo
     *
     * @param int $post_id
     * @return array|false
     */
    public function undo($post_id) {
        $user_id = get_current_user_id();
        $history_key = "flavor_editor_history_{$post_id}_{$user_id}";
        $pointer_key = "flavor_editor_pointer_{$post_id}_{$user_id}";

        $history = get_transient($history_key);
        $pointer = get_transient($pointer_key);

        if (!is_array($history) || $pointer <= 0) {
            return false;
        }

        // Mover puntero hacia atrás
        $pointer--;
        set_transient($pointer_key, $pointer, 4 * HOUR_IN_SECONDS);

        return [
            'state' => $history[$pointer]['state'],
            'label' => $history[$pointer]['label'],
            'current_position' => $pointer,
            'can_undo' => $pointer > 0,
            'can_redo' => true,
        ];
    }

    /**
     * Rehacer (Redo)
     */
    public function ajax_redo() {
        check_ajax_referer('flavor_editor_nonce', 'nonce');

        if (!current_user_can('edit_posts')) {
            wp_send_json_error(['message' => __('Sin permisos', 'flavor-platform')]);
        }

        $post_id = intval($_POST['post_id'] ?? 0);

        if (!$post_id) {
            wp_send_json_error(['message' => __('Post ID requerido', 'flavor-platform')]);
        }

        $result = $this->redo($post_id);

        if ($result === false) {
            wp_send_json_error(['message' => __('No hay más estados para rehacer', 'flavor-platform')]);
        }

        wp_send_json_success($result);
    }

    /**
     * Ejecutar redo
     *
     * @param int $post_id
     * @return array|false
     */
    public function redo($post_id) {
        $user_id = get_current_user_id();
        $history_key = "flavor_editor_history_{$post_id}_{$user_id}";
        $pointer_key = "flavor_editor_pointer_{$post_id}_{$user_id}";

        $history = get_transient($history_key);
        $pointer = get_transient($pointer_key);

        if (!is_array($history) || $pointer >= count($history) - 1) {
            return false;
        }

        // Mover puntero hacia adelante
        $pointer++;
        set_transient($pointer_key, $pointer, 4 * HOUR_IN_SECONDS);

        return [
            'state' => $history[$pointer]['state'],
            'label' => $history[$pointer]['label'],
            'current_position' => $pointer,
            'can_undo' => true,
            'can_redo' => $pointer < count($history) - 1,
        ];
    }

    /**
     * Limpiar historial
     */
    public function ajax_clear_history() {
        check_ajax_referer('flavor_editor_nonce', 'nonce');

        if (!current_user_can('edit_posts')) {
            wp_send_json_error(['message' => __('Sin permisos', 'flavor-platform')]);
        }

        $post_id = intval($_POST['post_id'] ?? 0);

        if (!$post_id) {
            wp_send_json_error(['message' => __('Post ID requerido', 'flavor-platform')]);
        }

        $user_id = get_current_user_id();
        delete_transient("flavor_editor_history_{$post_id}_{$user_id}");
        delete_transient("flavor_editor_pointer_{$post_id}_{$user_id}");

        wp_send_json_success(['message' => __('Historial limpiado', 'flavor-platform')]);
    }

    /**
     * Ir a un estado específico
     *
     * @param int $post_id
     * @param int $index
     * @return array|false
     */
    public function goto_state($post_id, $index) {
        $user_id = get_current_user_id();
        $history_key = "flavor_editor_history_{$post_id}_{$user_id}";
        $pointer_key = "flavor_editor_pointer_{$post_id}_{$user_id}";

        $history = get_transient($history_key);

        if (!is_array($history) || !isset($history[$index])) {
            return false;
        }

        set_transient($pointer_key, $index, 4 * HOUR_IN_SECONDS);

        return [
            'state' => $history[$index]['state'],
            'label' => $history[$index]['label'],
            'current_position' => $index,
            'can_undo' => $index > 0,
            'can_redo' => $index < count($history) - 1,
        ];
    }
}

<?php
if (!defined('ABSPATH')) exit;

class Flavor_Feature_Reactions extends Flavor_Feature_Base {
    protected $feature_id = 'reactions';
    protected static $instance = null;
    const REACTIONS = ['like' => '👍', 'love' => '❤️', 'haha' => '😂', 'wow' => '😮', 'sad' => '😢', 'angry' => '😡'];

    protected function init() {
        add_action('wp_ajax_flavor_react', [$this, 'ajax_react']);
    }

    public function render($entity_type, $entity_id, $args = []) {
        $data = $this->get_data($entity_type, $entity_id);
        $html = '<div class="flavor-reactions" data-entity="' . esc_attr($entity_type) . '" data-id="' . $entity_id . '">';
        foreach (self::REACTIONS as $type => $emoji) {
            $count = $data['reactions'][$type] ?? 0;
            $html .= sprintf('<button class="reaction" data-type="%s">%s <span>%d</span></button>', $type, $emoji, $count);
        }
        return $html . '</div>';
    }

    public function get_data($entity_type, $entity_id) {
        global $wpdb;
        $results = $wpdb->get_results($wpdb->prepare(
            "SELECT reaction_type, COUNT(*) as count FROM {$wpdb->prefix}flavor_reactions WHERE entity_type = %s AND entity_id = %d GROUP BY reaction_type",
            $entity_type, $entity_id
        ), OBJECT_K);
        $reactions = [];
        foreach (self::REACTIONS as $type => $emoji) {
            $reactions[$type] = isset($results[$type]) ? intval($results[$type]->count) : 0;
        }
        return ['reactions' => $reactions, 'total' => array_sum($reactions)];
    }

    public function register_action($entity_type, $entity_id, $user_id, $value = null) {
        if (!$user_id) return new WP_Error('auth', __('Debes iniciar sesión', 'flavor-chat-ia'));
        if (!array_key_exists($value, self::REACTIONS)) return new WP_Error('invalid', __('Reacción inválida', 'flavor-chat-ia'));

        global $wpdb;
        $table = $wpdb->prefix . 'flavor_reactions';
        $wpdb->delete($table, ['entity_type' => $entity_type, 'entity_id' => $entity_id, 'user_id' => $user_id]);
        $wpdb->insert($table, [
            'entity_type' => $entity_type, 'entity_id' => $entity_id, 'user_id' => $user_id,
            'reaction_type' => $value, 'created_at' => current_time('mysql')
        ]);
        return ['reaction' => $value];
    }

    public function ajax_react() {
        check_ajax_referer('flavor_features', 'nonce');
        $result = $this->register_action(
            sanitize_text_field($_POST['entity_type'] ?? ''),
            intval($_POST['entity_id'] ?? 0),
            get_current_user_id(),
            sanitize_text_field($_POST['reaction'] ?? '')
        );
        is_wp_error($result) ? wp_send_json_error($result->get_error_message()) : wp_send_json_success($result);
    }
}

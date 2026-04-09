<?php
if (!defined('ABSPATH')) exit;

class Flavor_Feature_Follow extends Flavor_Feature_Base {
    protected $feature_id = 'follow';
    protected static $instance = null;

    protected function init() {
        add_action('wp_ajax_flavor_toggle_follow', [$this, 'ajax_toggle']);
    }

    public function render($entity_type, $entity_id, $args = []) {
        $is_following = $this->is_following($entity_type, $entity_id, get_current_user_id());
        $count = $this->get_data($entity_type, $entity_id)['count'];

        return sprintf(
            '<button class="flavor-follow %s" data-entity="%s" data-id="%d"><span class="count">%d</span> %s</button>',
            $is_following ? 'following' : '',
            esc_attr($entity_type), $entity_id, $count,
            $is_following ? __('Siguiendo', FLAVOR_PLATFORM_TEXT_DOMAIN) : __('Seguir', FLAVOR_PLATFORM_TEXT_DOMAIN)
        );
    }

    public function get_data($entity_type, $entity_id) {
        global $wpdb;
        $count = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM {$wpdb->prefix}flavor_follows WHERE entity_type = %s AND entity_id = %d",
            $entity_type, $entity_id
        ));
        return ['count' => intval($count)];
    }

    public function register_action($entity_type, $entity_id, $user_id, $value = null) {
        if (!$user_id) return new WP_Error('auth', __('Debes iniciar sesión', FLAVOR_PLATFORM_TEXT_DOMAIN));

        global $wpdb;
        $table = $wpdb->prefix . 'flavor_follows';

        if ($this->is_following($entity_type, $entity_id, $user_id)) {
            $wpdb->delete($table, ['entity_type' => $entity_type, 'entity_id' => $entity_id, 'user_id' => $user_id]);
            return ['action' => 'unfollowed'];
        }
        $wpdb->insert($table, ['entity_type' => $entity_type, 'entity_id' => $entity_id, 'user_id' => $user_id, 'created_at' => current_time('mysql')]);
        return ['action' => 'followed'];
    }

    public function is_following($entity_type, $entity_id, $user_id) {
        if (!$user_id) return false;
        global $wpdb;
        return (bool) $wpdb->get_var($wpdb->prepare(
            "SELECT id FROM {$wpdb->prefix}flavor_follows WHERE entity_type = %s AND entity_id = %d AND user_id = %d",
            $entity_type, $entity_id, $user_id
        ));
    }

    public function ajax_toggle() {
        check_ajax_referer('flavor_features', 'nonce');
        $result = $this->register_action(
            sanitize_text_field($_POST['entity_type'] ?? ''),
            intval($_POST['entity_id'] ?? 0),
            get_current_user_id()
        );
        is_wp_error($result) ? wp_send_json_error($result->get_error_message()) : wp_send_json_success($result);
    }
}

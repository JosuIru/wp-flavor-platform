<?php
if (!defined('ABSPATH')) exit;

class Flavor_Feature_Bookmarks extends Flavor_Feature_Base {
    protected $feature_id = 'bookmarks';
    protected static $instance = null;

    protected function init() {
        add_action('wp_ajax_flavor_toggle_bookmark', [$this, 'ajax_toggle']);
    }

    public function render($entity_type, $entity_id, $args = []) {
        $is_bookmarked = $this->is_bookmarked($entity_type, $entity_id, get_current_user_id());
        return sprintf(
            '<button class="flavor-bookmark %s" data-entity="%s" data-id="%d">%s</button>',
            $is_bookmarked ? 'active' : '', esc_attr($entity_type), $entity_id,
            $is_bookmarked ? '🔖' : '📑'
        );
    }

    public function get_data($entity_type, $entity_id) {
        global $wpdb;
        $count = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM {$wpdb->prefix}flavor_bookmarks WHERE entity_type = %s AND entity_id = %d",
            $entity_type, $entity_id
        ));
        return ['count' => intval($count)];
    }

    public function register_action($entity_type, $entity_id, $user_id, $value = null) {
        if (!$user_id) return new WP_Error('auth', __('Debes iniciar sesión', FLAVOR_PLATFORM_TEXT_DOMAIN));
        global $wpdb;
        $table = $wpdb->prefix . 'flavor_bookmarks';
        if ($this->is_bookmarked($entity_type, $entity_id, $user_id)) {
            $wpdb->delete($table, ['entity_type' => $entity_type, 'entity_id' => $entity_id, 'user_id' => $user_id]);
            return ['action' => 'removed'];
        }
        $wpdb->insert($table, ['entity_type' => $entity_type, 'entity_id' => $entity_id, 'user_id' => $user_id, 'created_at' => current_time('mysql')]);
        return ['action' => 'added'];
    }

    public function is_bookmarked($entity_type, $entity_id, $user_id) {
        if (!$user_id) return false;
        global $wpdb;
        return (bool) $wpdb->get_var($wpdb->prepare(
            "SELECT id FROM {$wpdb->prefix}flavor_bookmarks WHERE entity_type = %s AND entity_id = %d AND user_id = %d",
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

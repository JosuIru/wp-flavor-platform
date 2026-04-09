<?php
if (!defined('ABSPATH')) exit;

class Flavor_Feature_Report extends Flavor_Feature_Base {
    protected $feature_id = 'report';
    protected static $instance = null;

    protected function init() {
        add_action('wp_ajax_flavor_report_entity', [$this, 'ajax_report']);
    }

    public function render($entity_type, $entity_id, $args = []) {
        return sprintf(
            '<button class="flavor-report" data-entity="%s" data-id="%d">%s</button>',
            esc_attr($entity_type), $entity_id, __('Reportar', FLAVOR_PLATFORM_TEXT_DOMAIN)
        );
    }

    public function get_data($entity_type, $entity_id) {
        global $wpdb;
        $count = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM {$wpdb->prefix}flavor_reports WHERE entity_type = %s AND entity_id = %d",
            $entity_type, $entity_id
        ));
        return ['count' => intval($count)];
    }

    public function register_action($entity_type, $entity_id, $user_id, $value = null) {
        if (!$user_id) return new WP_Error('auth', __('Debes iniciar sesión', FLAVOR_PLATFORM_TEXT_DOMAIN));
        global $wpdb;
        $wpdb->insert($wpdb->prefix . 'flavor_reports', [
            'entity_type' => $entity_type, 'entity_id' => $entity_id, 'user_id' => $user_id,
            'reason' => sanitize_text_field($value), 'status' => 'pending', 'created_at' => current_time('mysql')
        ]);
        return ['report_id' => $wpdb->insert_id];
    }

    public function ajax_report() {
        check_ajax_referer('flavor_features', 'nonce');
        $result = $this->register_action(
            sanitize_text_field($_POST['entity_type'] ?? ''),
            intval($_POST['entity_id'] ?? 0),
            get_current_user_id(),
            sanitize_text_field($_POST['reason'] ?? '')
        );
        is_wp_error($result) ? wp_send_json_error($result->get_error_message()) : wp_send_json_success($result);
    }
}

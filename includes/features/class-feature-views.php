<?php
if (!defined('ABSPATH')) exit;

class Flavor_Feature_Views extends Flavor_Feature_Base {
    protected $feature_id = 'views';
    protected static $instance = null;

    protected function init() {}

    public function render($entity_type, $entity_id, $args = []) {
        $views = $this->get_data($entity_type, $entity_id)['count'];
        return sprintf('<span class="flavor-views">👁 %s</span>', number_format_i18n($views));
    }

    public function get_data($entity_type, $entity_id) {
        global $wpdb;
        $count = $wpdb->get_var($wpdb->prepare(
            "SELECT view_count FROM {$wpdb->prefix}flavor_view_counts WHERE entity_type = %s AND entity_id = %d",
            $entity_type, $entity_id
        ));
        return ['count' => intval($count)];
    }

    public function register_action($entity_type, $entity_id, $user_id, $value = null) {
        global $wpdb;
        $table = $wpdb->prefix . 'flavor_view_counts';
        $existing = $wpdb->get_var($wpdb->prepare(
            "SELECT id FROM {$table} WHERE entity_type = %s AND entity_id = %d",
            $entity_type, $entity_id
        ));
        if ($existing) {
            $wpdb->query($wpdb->prepare("UPDATE {$table} SET view_count = view_count + 1 WHERE id = %d", $existing));
        } else {
            $wpdb->insert($table, ['entity_type' => $entity_type, 'entity_id' => $entity_id, 'view_count' => 1]);
        }
        return true;
    }
    protected function allow_anonymous() { return true; }
}

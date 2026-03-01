<?php
if (!defined('ABSPATH')) exit;

class Flavor_Feature_Versions extends Flavor_Feature_Base {
    protected $feature_id = 'versions';
    protected static $instance = null;

    protected function init() {}

    public function render($entity_type, $entity_id, $args = []) {
        $versions = $this->get_versions($entity_type, $entity_id);
        if (empty($versions)) return '';
        $html = '<div class="flavor-versions"><select onchange="location.href=this.value">';
        foreach ($versions as $v) {
            $html .= sprintf('<option value="%s">v%d - %s</option>',
                esc_url(add_query_arg('version', $v->version_number)), $v->version_number, $v->created_at);
        }
        return $html . '</select></div>';
    }

    public function get_data($entity_type, $entity_id) {
        return ['versions' => $this->get_versions($entity_type, $entity_id)];
    }

    public function get_versions($entity_type, $entity_id) {
        global $wpdb;
        return $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM {$wpdb->prefix}flavor_versions WHERE entity_type = %s AND entity_id = %d ORDER BY version_number DESC",
            $entity_type, $entity_id
        ));
    }

    public function register_action($entity_type, $entity_id, $user_id, $value = null) {
        global $wpdb;
        $table = $wpdb->prefix . 'flavor_versions';
        $max_version = $wpdb->get_var($wpdb->prepare(
            "SELECT MAX(version_number) FROM {$table} WHERE entity_type = %s AND entity_id = %d",
            $entity_type, $entity_id
        ));
        $wpdb->insert($table, [
            'entity_type' => $entity_type, 'entity_id' => $entity_id, 'user_id' => $user_id ?: 0,
            'version_number' => intval($max_version) + 1, 'data' => maybe_serialize($value), 'created_at' => current_time('mysql')
        ]);
        return ['version' => intval($max_version) + 1];
    }
}

<?php
if (!defined('ABSPATH')) exit;

class Flavor_Feature_Share extends Flavor_Feature_Base {
    protected $feature_id = 'share';
    protected static $instance = null;

    protected function init() {}

    public function render($entity_type, $entity_id, $args = []) {
        $url = $args['url'] ?? get_permalink();
        $title = $args['title'] ?? get_the_title();
        return sprintf(
            '<div class="flavor-share"><a href="https://twitter.com/share?url=%s&text=%s" target="_blank">X</a> <a href="https://www.facebook.com/sharer/sharer.php?u=%s" target="_blank">FB</a> <a href="https://wa.me/?text=%s" target="_blank">WA</a></div>',
            urlencode($url), urlencode($title), urlencode($url), urlencode($title . ' ' . $url)
        );
    }

    public function get_data($entity_type, $entity_id) {
        global $wpdb;
        $count = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM {$wpdb->prefix}flavor_shares WHERE entity_type = %s AND entity_id = %d",
            $entity_type, $entity_id
        ));
        return ['count' => intval($count)];
    }

    public function register_action($entity_type, $entity_id, $user_id, $value = null) {
        global $wpdb;
        $wpdb->insert($wpdb->prefix . 'flavor_shares', [
            'entity_type' => $entity_type, 'entity_id' => $entity_id, 'user_id' => $user_id ?: 0,
            'platform' => sanitize_text_field($value), 'created_at' => current_time('mysql')
        ]);
        return true;
    }
    protected function allow_anonymous() { return true; }
}

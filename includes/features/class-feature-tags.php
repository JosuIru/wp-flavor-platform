<?php
if (!defined('ABSPATH')) exit;

class Flavor_Feature_Tags extends Flavor_Feature_Base {
    protected $feature_id = 'tags';
    protected static $instance = null;

    protected function init() {}

    public function render($entity_type, $entity_id, $args = []) {
        $tags = $this->get_tags($entity_type, $entity_id);
        if (empty($tags)) return '';
        $html = '<div class="flavor-tags">';
        foreach ($tags as $tag) {
            $html .= sprintf('<span class="tag">%s</span>', esc_html($tag->name));
        }
        return $html . '</div>';
    }

    public function get_data($entity_type, $entity_id) {
        return ['tags' => $this->get_tags($entity_type, $entity_id)];
    }

    public function get_tags($entity_type, $entity_id) {
        global $wpdb;
        return $wpdb->get_results($wpdb->prepare(
            "SELECT t.* FROM {$wpdb->prefix}flavor_tags t
             JOIN {$wpdb->prefix}flavor_entity_tags et ON t.id = et.tag_id
             WHERE et.entity_type = %s AND et.entity_id = %d",
            $entity_type, $entity_id
        ));
    }

    public function register_action($entity_type, $entity_id, $user_id, $value = null) {
        global $wpdb;
        $tag_name = sanitize_text_field($value);
        $tag = $wpdb->get_row($wpdb->prepare("SELECT id FROM {$wpdb->prefix}flavor_tags WHERE name = %s", $tag_name));
        if (!$tag) {
            $wpdb->insert($wpdb->prefix . 'flavor_tags', ['name' => $tag_name, 'created_at' => current_time('mysql')]);
            $tag_id = $wpdb->insert_id;
        } else {
            $tag_id = $tag->id;
        }
        $wpdb->insert($wpdb->prefix . 'flavor_entity_tags', [
            'entity_type' => $entity_type, 'entity_id' => $entity_id, 'tag_id' => $tag_id
        ]);
        return true;
    }
}

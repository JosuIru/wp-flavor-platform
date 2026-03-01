<?php
if (!defined('ABSPATH')) exit;

class Flavor_Feature_Export extends Flavor_Feature_Base {
    protected $feature_id = 'export';
    protected static $instance = null;
    const FORMATS = ['pdf', 'csv', 'json'];

    protected function init() {
        add_action('wp_ajax_flavor_export_entity', [$this, 'ajax_export']);
    }

    public function render($entity_type, $entity_id, $args = []) {
        $formats = $args['formats'] ?? self::FORMATS;
        $html = '<div class="flavor-export">';
        foreach ($formats as $format) {
            $html .= sprintf('<button class="export-btn" data-entity="%s" data-id="%d" data-format="%s">%s</button>',
                esc_attr($entity_type), $entity_id, $format, strtoupper($format));
        }
        return $html . '</div>';
    }

    public function get_data($entity_type, $entity_id) {
        return ['formats' => self::FORMATS];
    }

    public function register_action($entity_type, $entity_id, $user_id, $value = null) {
        global $wpdb;
        $wpdb->insert($wpdb->prefix . 'flavor_exports', [
            'entity_type' => $entity_type, 'entity_id' => $entity_id, 'user_id' => $user_id ?: 0,
            'format' => sanitize_text_field($value), 'created_at' => current_time('mysql')
        ]);
        return ['export_id' => $wpdb->insert_id];
    }

    public function ajax_export() {
        check_ajax_referer('flavor_features', 'nonce');
        $entity_type = sanitize_text_field($_POST['entity_type'] ?? '');
        $entity_id = intval($_POST['entity_id'] ?? 0);
        $format = sanitize_text_field($_POST['format'] ?? 'json');
        
        $this->register_action($entity_type, $entity_id, get_current_user_id(), $format);
        
        // Generar exportación según formato
        $data = apply_filters("flavor_export_{$entity_type}", [], $entity_id, $format);
        wp_send_json_success(['data' => $data, 'format' => $format]);
    }
}

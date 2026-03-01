<?php
if (!defined('ABSPATH')) exit;

class Flavor_Feature_QRCode extends Flavor_Feature_Base {
    protected $feature_id = 'qrcode';
    protected static $instance = null;

    protected function init() {}

    public function render($entity_type, $entity_id, $args = []) {
        $url = $args['url'] ?? get_permalink($entity_id);
        $size = $args['size'] ?? 150;
        $qr_url = 'https://api.qrserver.com/v1/create-qr-code/?size=' . $size . 'x' . $size . '&data=' . urlencode($url);
        return sprintf('<div class="flavor-qrcode"><img src="%s" alt="QR Code" width="%d" height="%d" loading="lazy"></div>', esc_url($qr_url), $size, $size);
    }

    public function get_data($entity_type, $entity_id) {
        return ['url' => get_permalink($entity_id)];
    }

    public function register_action($entity_type, $entity_id, $user_id, $value = null) {
        return true;
    }
    protected function allow_anonymous() { return true; }
}

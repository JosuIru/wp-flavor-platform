<?php
/**
 * Feature: Valoraciones (Ratings)
 *
 * Permite a los usuarios valorar entidades con estrellas (1-5).
 *
 * @package FlavorChatIA
 * @subpackage Features
 */

if (!defined('ABSPATH')) {
    exit;
}

class Flavor_Feature_Ratings extends Flavor_Feature_Base {

    protected $feature_id = 'ratings';
    protected static $instance = null;

    protected function init() {
        add_action('wp_ajax_flavor_rate_entity', [$this, 'ajax_rate']);
    }

    public function render($entity_type, $entity_id, $args = []) {
        $data = $this->get_data($entity_type, $entity_id);
        $user_rating = $this->get_user_rating($entity_type, $entity_id, get_current_user_id());

        ob_start();
        ?>
        <div class="flavor-rating" data-entity="<?php echo esc_attr($entity_type); ?>" data-id="<?php echo esc_attr($entity_id); ?>">
            <div class="flavor-stars">
                <?php for ($i = 1; $i <= 5; $i++): ?>
                    <span class="star <?php echo $i <= $data['average'] ? 'filled' : ''; ?> <?php echo $i <= $user_rating ? 'user-rated' : ''; ?>" data-value="<?php echo $i; ?>">★</span>
                <?php endfor; ?>
            </div>
            <span class="rating-count">(<?php echo $data['count']; ?>)</span>
        </div>
        <?php
        return ob_get_clean();
    }

    public function get_data($entity_type, $entity_id) {
        global $wpdb;
        $table = $wpdb->prefix . 'flavor_ratings';

        $result = $wpdb->get_row($wpdb->prepare(
            "SELECT AVG(rating) as average, COUNT(*) as count FROM {$table} WHERE entity_type = %s AND entity_id = %d",
            $entity_type, $entity_id
        ));

        return [
            'average' => round($result->average ?? 0, 1),
            'count' => intval($result->count ?? 0),
        ];
    }

    public function register_action($entity_type, $entity_id, $user_id, $value = null) {
        if (!$user_id || $value < 1 || $value > 5) {
            return new WP_Error('invalid_rating', __('Valoración inválida', FLAVOR_PLATFORM_TEXT_DOMAIN));
        }

        global $wpdb;
        $table = $wpdb->prefix . 'flavor_ratings';

        $existing = $wpdb->get_var($wpdb->prepare(
            "SELECT id FROM {$table} WHERE entity_type = %s AND entity_id = %d AND user_id = %d",
            $entity_type, $entity_id, $user_id
        ));

        if ($existing) {
            $wpdb->update($table, ['rating' => $value, 'updated_at' => current_time('mysql')],
                ['id' => $existing]);
        } else {
            $wpdb->insert($table, [
                'entity_type' => $entity_type,
                'entity_id' => $entity_id,
                'user_id' => $user_id,
                'rating' => $value,
                'created_at' => current_time('mysql'),
            ]);
        }

        return true;
    }

    private function get_user_rating($entity_type, $entity_id, $user_id) {
        if (!$user_id) return 0;

        global $wpdb;
        $table = $wpdb->prefix . 'flavor_ratings';

        return intval($wpdb->get_var($wpdb->prepare(
            "SELECT rating FROM {$table} WHERE entity_type = %s AND entity_id = %d AND user_id = %d",
            $entity_type, $entity_id, $user_id
        )));
    }

    public function ajax_rate() {
        check_ajax_referer('flavor_features', 'nonce');

        $entity_type = sanitize_text_field($_POST['entity_type'] ?? '');
        $entity_id = intval($_POST['entity_id'] ?? 0);
        $rating = intval($_POST['rating'] ?? 0);

        $result = $this->register_action($entity_type, $entity_id, get_current_user_id(), $rating);

        if (is_wp_error($result)) {
            wp_send_json_error($result->get_error_message());
        }

        wp_send_json_success($this->get_data($entity_type, $entity_id));
    }
}

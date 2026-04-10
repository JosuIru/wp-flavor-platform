<?php
/**
 * Feature: Favoritos
 *
 * Permite a los usuarios guardar entidades como favoritos.
 *
 * @package FlavorPlatform
 * @subpackage Features
 */

if (!defined('ABSPATH')) {
    exit;
}

class Flavor_Feature_Favorites extends Flavor_Feature_Base {

    protected $feature_id = 'favorites';
    protected static $instance = null;

    protected function init() {
        add_action('wp_ajax_flavor_toggle_favorite', [$this, 'ajax_toggle']);
    }

    public function render($entity_type, $entity_id, $args = []) {
        $is_favorite = $this->is_favorite($entity_type, $entity_id, get_current_user_id());
        $count = $this->get_data($entity_type, $entity_id)['count'];

        ob_start();
        ?>
        <button class="flavor-favorite <?php echo $is_favorite ? 'active' : ''; ?>"
                data-entity="<?php echo esc_attr($entity_type); ?>"
                data-id="<?php echo esc_attr($entity_id); ?>">
            <span class="icon"><?php echo $is_favorite ? '❤️' : '🤍'; ?></span>
            <span class="count"><?php echo $count; ?></span>
        </button>
        <?php
        return ob_get_clean();
    }

    public function get_data($entity_type, $entity_id) {
        global $wpdb;
        $table = $wpdb->prefix . 'flavor_favorites';

        $count = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM {$table} WHERE entity_type = %s AND entity_id = %d",
            $entity_type, $entity_id
        ));

        return ['count' => intval($count)];
    }

    public function register_action($entity_type, $entity_id, $user_id, $value = null) {
        if (!$user_id) {
            return new WP_Error('not_logged_in', __('Debes iniciar sesión', FLAVOR_PLATFORM_TEXT_DOMAIN));
        }

        global $wpdb;
        $table = $wpdb->prefix . 'flavor_favorites';

        if ($this->is_favorite($entity_type, $entity_id, $user_id)) {
            $wpdb->delete($table, [
                'entity_type' => $entity_type,
                'entity_id' => $entity_id,
                'user_id' => $user_id,
            ]);
            return ['action' => 'removed'];
        } else {
            $wpdb->insert($table, [
                'entity_type' => $entity_type,
                'entity_id' => $entity_id,
                'user_id' => $user_id,
                'created_at' => current_time('mysql'),
            ]);
            return ['action' => 'added'];
        }
    }

    public function is_favorite($entity_type, $entity_id, $user_id) {
        if (!$user_id) return false;

        global $wpdb;
        $table = $wpdb->prefix . 'flavor_favorites';

        return (bool) $wpdb->get_var($wpdb->prepare(
            "SELECT id FROM {$table} WHERE entity_type = %s AND entity_id = %d AND user_id = %d",
            $entity_type, $entity_id, $user_id
        ));
    }

    public function ajax_toggle() {
        check_ajax_referer('flavor_features', 'nonce');

        $entity_type = sanitize_text_field($_POST['entity_type'] ?? '');
        $entity_id = intval($_POST['entity_id'] ?? 0);

        $result = $this->register_action($entity_type, $entity_id, get_current_user_id());

        if (is_wp_error($result)) {
            wp_send_json_error($result->get_error_message());
        }

        wp_send_json_success(array_merge($result, $this->get_data($entity_type, $entity_id)));
    }
}

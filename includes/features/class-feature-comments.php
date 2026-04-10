<?php
/**
 * Feature: Comentarios
 *
 * Sistema de comentarios integrado para entidades.
 *
 * @package FlavorPlatform
 * @subpackage Features
 */

if (!defined('ABSPATH')) {
    exit;
}

class Flavor_Feature_Comments extends Flavor_Feature_Base {

    protected $feature_id = 'comments';
    protected static $instance = null;

    protected function init() {
        add_action('wp_ajax_flavor_add_comment', [$this, 'ajax_add']);
        add_action('wp_ajax_nopriv_flavor_add_comment', [$this, 'ajax_add']);
    }

    public function render($entity_type, $entity_id, $args = []) {
        $comments = $this->get_comments($entity_type, $entity_id);
        $count = count($comments);

        ob_start();
        ?>
        <div class="flavor-comments" data-entity="<?php echo esc_attr($entity_type); ?>" data-id="<?php echo esc_attr($entity_id); ?>">
            <h4><?php printf(_n('%d comentario', '%d comentarios', $count, FLAVOR_PLATFORM_TEXT_DOMAIN), $count); ?></h4>
            <div class="comments-list">
                <?php foreach ($comments as $comment): ?>
                    <div class="comment">
                        <strong><?php echo esc_html($comment->author_name); ?></strong>
                        <p><?php echo esc_html($comment->content); ?></p>
                        <small><?php echo human_time_diff(strtotime($comment->created_at)); ?></small>
                    </div>
                <?php endforeach; ?>
            </div>
            <?php if (is_user_logged_in() || $this->allow_anonymous()): ?>
                <form class="comment-form">
                    <textarea name="content" placeholder="<?php esc_attr_e('Escribe un comentario...', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>"></textarea>
                    <button type="submit"><?php esc_html_e('Enviar', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></button>
                </form>
            <?php endif; ?>
        </div>
        <?php
        return ob_get_clean();
    }

    public function get_data($entity_type, $entity_id) {
        return ['count' => count($this->get_comments($entity_type, $entity_id))];
    }

    public function get_comments($entity_type, $entity_id, $limit = 50) {
        global $wpdb;
        $table = $wpdb->prefix . 'flavor_comments';

        return $wpdb->get_results($wpdb->prepare(
            "SELECT c.*, u.display_name as author_name FROM {$table} c
             LEFT JOIN {$wpdb->users} u ON c.user_id = u.ID
             WHERE c.entity_type = %s AND c.entity_id = %d AND c.status = 'approved'
             ORDER BY c.created_at DESC LIMIT %d",
            $entity_type, $entity_id, $limit
        ));
    }

    public function register_action($entity_type, $entity_id, $user_id, $value = null) {
        global $wpdb;
        $table = $wpdb->prefix . 'flavor_comments';

        $wpdb->insert($table, [
            'entity_type' => $entity_type,
            'entity_id' => $entity_id,
            'user_id' => $user_id ?: 0,
            'content' => sanitize_textarea_field($value),
            'status' => 'approved',
            'created_at' => current_time('mysql'),
        ]);

        return ['comment_id' => $wpdb->insert_id];
    }

    protected function allow_anonymous() {
        return get_option('flavor_allow_anonymous_comments', false);
    }

    public function ajax_add() {
        check_ajax_referer('flavor_features', 'nonce');

        $entity_type = sanitize_text_field($_POST['entity_type'] ?? '');
        $entity_id = intval($_POST['entity_id'] ?? 0);
        $content = sanitize_textarea_field($_POST['content'] ?? '');

        if (empty($content)) {
            wp_send_json_error(__('El comentario no puede estar vacío', FLAVOR_PLATFORM_TEXT_DOMAIN));
        }

        $result = $this->register_action($entity_type, $entity_id, get_current_user_id(), $content);

        if (is_wp_error($result)) {
            wp_send_json_error($result->get_error_message());
        }

        wp_send_json_success($result);
    }
}

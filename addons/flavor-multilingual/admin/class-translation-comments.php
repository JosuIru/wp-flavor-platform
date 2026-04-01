<?php
/**
 * Sistema de comentarios en traducciones
 *
 * Permite comunicación entre traductores y revisores.
 *
 * @package FlavorMultilingual
 * @since 1.4.0
 */

if (!defined('ABSPATH')) {
    exit;
}

class Flavor_Translation_Comments {

    /**
     * Instancia singleton
     */
    private static $instance = null;

    /**
     * Nombre de la tabla
     */
    private $table_name;

    /**
     * Obtener instancia
     */
    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Constructor
     */
    private function __construct() {
        global $wpdb;
        $this->table_name = $wpdb->prefix . 'flavor_translation_comments';

        add_action('admin_init', array($this, 'maybe_create_table'));
        add_action('admin_enqueue_scripts', array($this, 'enqueue_scripts'));
        add_action('wp_ajax_flavor_ml_add_comment', array($this, 'ajax_add_comment'));
        add_action('wp_ajax_flavor_ml_get_comments', array($this, 'ajax_get_comments'));
        add_action('wp_ajax_flavor_ml_resolve_comment', array($this, 'ajax_resolve_comment'));
        add_action('wp_ajax_flavor_ml_delete_comment', array($this, 'ajax_delete_comment'));
    }

    /**
     * Cargar scripts y estilos
     */
    public function enqueue_scripts($hook) {
        // Solo en páginas del editor de traducción
        if (strpos($hook, 'flavor-multilingual') === false) {
            return;
        }

        wp_enqueue_style(
            'flavor-ml-comments',
            FLAVOR_MULTILINGUAL_URL . 'assets/css/translation-comments.css',
            array(),
            FLAVOR_MULTILINGUAL_VERSION
        );

        wp_enqueue_script(
            'flavor-ml-comments',
            FLAVOR_MULTILINGUAL_URL . 'assets/js/translation-comments.js',
            array('jquery'),
            FLAVOR_MULTILINGUAL_VERSION,
            true
        );

        $postId = isset($_GET['post_id']) ? absint($_GET['post_id']) : 0;
        $langCode = isset($_GET['lang']) ? sanitize_key($_GET['lang']) : '';

        wp_localize_script('flavor-ml-comments', 'flavorMLComments', array(
            'ajaxUrl'   => admin_url('admin-ajax.php'),
            'nonce'     => wp_create_nonce('flavor_ml_comments'),
            'postId'    => $postId,
            'langCode'  => $langCode,
            'fieldName' => '',
            'strings'   => array(
                'hide_resolved'  => __('Ocultar resueltos', 'flavor-multilingual'),
                'show_resolved'  => __('Ver resueltos', 'flavor-multilingual'),
                'write_reply'    => __('Escribe tu respuesta...', 'flavor-multilingual'),
                'reply'          => __('Responder', 'flavor-multilingual'),
                'cancel'         => __('Cancelar', 'flavor-multilingual'),
                'resolve'        => __('Resolver', 'flavor-multilingual'),
                'delete'         => __('Eliminar', 'flavor-multilingual'),
                'add_comment'    => __('Añadir comentario', 'flavor-multilingual'),
                'sending'        => __('Enviando...', 'flavor-multilingual'),
                'confirm_delete' => __('¿Eliminar este comentario?', 'flavor-multilingual'),
                'no_comments'    => __('No hay comentarios', 'flavor-multilingual'),
                'error'          => __('Ha ocurrido un error', 'flavor-multilingual'),
            ),
        ));
    }

    /**
     * Crear tabla si no existe
     */
    public function maybe_create_table() {
        global $wpdb;

        if (get_option('flavor_ml_comments_table_version') === '1.0') {
            return;
        }

        $charset_collate = $wpdb->get_charset_collate();

        $sql = "CREATE TABLE IF NOT EXISTS {$this->table_name} (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            post_id bigint(20) unsigned NOT NULL,
            language_code varchar(10) NOT NULL,
            field_name varchar(100) DEFAULT '',
            user_id bigint(20) unsigned NOT NULL,
            comment_text text NOT NULL,
            comment_type varchar(20) DEFAULT 'note',
            is_resolved tinyint(1) DEFAULT 0,
            resolved_by bigint(20) unsigned DEFAULT NULL,
            resolved_at datetime DEFAULT NULL,
            parent_id bigint(20) unsigned DEFAULT 0,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY post_lang (post_id, language_code),
            KEY user_id (user_id),
            KEY is_resolved (is_resolved)
        ) $charset_collate;";

        require_once ABSPATH . 'wp-admin/includes/upgrade.php';
        dbDelta($sql);

        update_option('flavor_ml_comments_table_version', '1.0');
    }

    /**
     * Añadir comentario
     */
    public function add_comment($data) {
        global $wpdb;

        $defaults = array(
            'post_id'       => 0,
            'language_code' => '',
            'field_name'    => '',
            'user_id'       => get_current_user_id(),
            'comment_text'  => '',
            'comment_type'  => 'note', // note, suggestion, issue, question
            'parent_id'     => 0,
        );

        $data = wp_parse_args($data, $defaults);

        if (empty($data['post_id']) || empty($data['language_code']) || empty($data['comment_text'])) {
            return false;
        }

        $result = $wpdb->insert(
            $this->table_name,
            array(
                'post_id'       => absint($data['post_id']),
                'language_code' => sanitize_text_field($data['language_code']),
                'field_name'    => sanitize_text_field($data['field_name']),
                'user_id'       => absint($data['user_id']),
                'comment_text'  => wp_kses_post($data['comment_text']),
                'comment_type'  => sanitize_text_field($data['comment_type']),
                'parent_id'     => absint($data['parent_id']),
            ),
            array('%d', '%s', '%s', '%d', '%s', '%s', '%d')
        );

        if ($result) {
            $comment_id = $wpdb->insert_id;

            // Notificar si es necesario
            $this->maybe_notify($comment_id, $data);

            return $comment_id;
        }

        return false;
    }

    /**
     * Obtener comentarios de una traducción
     */
    public function get_comments($post_id, $language_code, $field_name = '', $include_resolved = false) {
        global $wpdb;

        $where = $wpdb->prepare(
            "WHERE post_id = %d AND language_code = %s",
            $post_id,
            $language_code
        );

        if (!empty($field_name)) {
            $where .= $wpdb->prepare(" AND field_name = %s", $field_name);
        }

        if (!$include_resolved) {
            $where .= " AND is_resolved = 0";
        }

        $comments = $wpdb->get_results(
            "SELECT c.*, u.display_name as user_name, u.user_email
             FROM {$this->table_name} c
             LEFT JOIN {$wpdb->users} u ON c.user_id = u.ID
             {$where}
             ORDER BY c.created_at ASC"
        );

        // Organizar en hilos
        return $this->organize_threads($comments);
    }

    /**
     * Organizar comentarios en hilos
     */
    private function organize_threads($comments) {
        $threads = array();
        $children = array();

        foreach ($comments as $comment) {
            $comment->replies = array();
            $comment->avatar = get_avatar_url($comment->user_id, array('size' => 32));

            if ($comment->parent_id == 0) {
                $threads[$comment->id] = $comment;
            } else {
                $children[$comment->parent_id][] = $comment;
            }
        }

        // Asignar respuestas a hilos padres
        foreach ($threads as $id => $thread) {
            if (isset($children[$id])) {
                $threads[$id]->replies = $children[$id];
            }
        }

        return array_values($threads);
    }

    /**
     * Resolver comentario
     */
    public function resolve_comment($comment_id) {
        global $wpdb;

        return $wpdb->update(
            $this->table_name,
            array(
                'is_resolved' => 1,
                'resolved_by' => get_current_user_id(),
                'resolved_at' => current_time('mysql'),
            ),
            array('id' => $comment_id),
            array('%d', '%d', '%s'),
            array('%d')
        );
    }

    /**
     * Eliminar comentario
     */
    public function delete_comment($comment_id) {
        global $wpdb;

        // Verificar permisos
        $comment = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$this->table_name} WHERE id = %d",
            $comment_id
        ));

        if (!$comment) {
            return false;
        }

        // Solo el autor o managers pueden eliminar
        if ($comment->user_id != get_current_user_id() && !current_user_can('flavor_manage_translations')) {
            return false;
        }

        // Eliminar respuestas también
        $wpdb->delete($this->table_name, array('parent_id' => $comment_id), array('%d'));

        return $wpdb->delete($this->table_name, array('id' => $comment_id), array('%d'));
    }

    /**
     * Contar comentarios no resueltos
     */
    public function count_unresolved($post_id, $language_code = '') {
        global $wpdb;

        $where = $wpdb->prepare("WHERE post_id = %d AND is_resolved = 0", $post_id);

        if (!empty($language_code)) {
            $where .= $wpdb->prepare(" AND language_code = %s", $language_code);
        }

        return (int) $wpdb->get_var("SELECT COUNT(*) FROM {$this->table_name} {$where}");
    }

    /**
     * Notificar si es necesario
     */
    private function maybe_notify($comment_id, $data) {
        // Obtener usuarios a notificar
        $post_id = $data['post_id'];
        $language = $data['language_code'];

        // Notificar al traductor asignado si el comentario es de un revisor
        $translator_id = get_post_meta($post_id, '_flavor_ml_translator_' . $language, true);
        $current_user_id = get_current_user_id();

        if ($translator_id && $translator_id != $current_user_id) {
            $this->send_notification($translator_id, $comment_id, $data);
        }

        // Si es una respuesta, notificar al autor del comentario padre
        if (!empty($data['parent_id'])) {
            global $wpdb;
            $parent = $wpdb->get_row($wpdb->prepare(
                "SELECT user_id FROM {$this->table_name} WHERE id = %d",
                $data['parent_id']
            ));

            if ($parent && $parent->user_id != $current_user_id) {
                $this->send_notification($parent->user_id, $comment_id, $data);
            }
        }
    }

    /**
     * Enviar notificación
     */
    private function send_notification($user_id, $comment_id, $data) {
        $user = get_user_by('id', $user_id);
        if (!$user || !$user->user_email) {
            return;
        }

        $post = get_post($data['post_id']);
        $commenter = wp_get_current_user();

        $subject = sprintf(
            /* translators: %s: post title */
            __('[Traducción] Nuevo comentario en: %s', 'flavor-multilingual'),
            $post->post_title
        );

        $message = sprintf(
            /* translators: %1$s: user name, %2$s: post title, %3$s: comment text, %4$s: url */
            __('Hola,

%1$s ha dejado un comentario en la traducción de "%2$s":

"%3$s"

Ver traducción: %4$s

Saludos,
%5$s', 'flavor-multilingual'),
            $commenter->display_name,
            $post->post_title,
            wp_strip_all_tags($data['comment_text']),
            admin_url('admin.php?page=flavor-multilingual-translate&post_id=' . $data['post_id'] . '&lang=' . $data['language_code']),
            get_bloginfo('name')
        );

        wp_mail($user->user_email, $subject, $message);
    }

    /**
     * AJAX: Añadir comentario
     */
    public function ajax_add_comment() {
        check_ajax_referer('flavor_ml_comments', 'nonce');

        if (!current_user_can('flavor_translate')) {
            wp_send_json_error(array('message' => __('Sin permisos', 'flavor-multilingual')));
        }

        $comment_id = $this->add_comment(array(
            'post_id'       => absint($_POST['post_id'] ?? 0),
            'language_code' => sanitize_text_field($_POST['language'] ?? ''),
            'field_name'    => sanitize_text_field($_POST['field'] ?? ''),
            'comment_text'  => wp_kses_post($_POST['comment'] ?? ''),
            'comment_type'  => sanitize_text_field($_POST['type'] ?? 'note'),
            'parent_id'     => absint($_POST['parent_id'] ?? 0),
        ));

        if ($comment_id) {
            $user = wp_get_current_user();

            wp_send_json_success(array(
                'id'          => $comment_id,
                'user_name'   => $user->display_name,
                'avatar'      => get_avatar_url($user->ID, array('size' => 32)),
                'created_at'  => current_time('mysql'),
                'message'     => __('Comentario añadido', 'flavor-multilingual'),
            ));
        }

        wp_send_json_error(array('message' => __('Error al añadir comentario', 'flavor-multilingual')));
    }

    /**
     * AJAX: Obtener comentarios
     */
    public function ajax_get_comments() {
        check_ajax_referer('flavor_ml_comments', 'nonce');

        $post_id = absint($_POST['post_id'] ?? 0);
        $language = sanitize_text_field($_POST['language'] ?? '');
        $field = sanitize_text_field($_POST['field'] ?? '');
        $include_resolved = !empty($_POST['include_resolved']);

        $comments = $this->get_comments($post_id, $language, $field, $include_resolved);

        wp_send_json_success(array('comments' => $comments));
    }

    /**
     * AJAX: Resolver comentario
     */
    public function ajax_resolve_comment() {
        check_ajax_referer('flavor_ml_comments', 'nonce');

        if (!current_user_can('flavor_translate')) {
            wp_send_json_error(array('message' => __('Sin permisos', 'flavor-multilingual')));
        }

        $comment_id = absint($_POST['comment_id'] ?? 0);

        if ($this->resolve_comment($comment_id)) {
            wp_send_json_success(array('message' => __('Comentario resuelto', 'flavor-multilingual')));
        }

        wp_send_json_error(array('message' => __('Error al resolver', 'flavor-multilingual')));
    }

    /**
     * AJAX: Eliminar comentario
     */
    public function ajax_delete_comment() {
        check_ajax_referer('flavor_ml_comments', 'nonce');

        $comment_id = absint($_POST['comment_id'] ?? 0);

        if ($this->delete_comment($comment_id)) {
            wp_send_json_success(array('message' => __('Comentario eliminado', 'flavor-multilingual')));
        }

        wp_send_json_error(array('message' => __('Error al eliminar', 'flavor-multilingual')));
    }

    /**
     * Renderizar panel de comentarios (para incluir en editor)
     */
    public function render_comments_panel($post_id, $language) {
        $comments = $this->get_comments($post_id, $language, '', true);
        $unresolved_count = $this->count_unresolved($post_id, $language);
        ?>
        <div class="flavor-ml-comments-panel" data-post-id="<?php echo esc_attr($post_id); ?>" data-language="<?php echo esc_attr($language); ?>">
            <div class="comments-header">
                <h4>
                    <?php _e('Comentarios', 'flavor-multilingual'); ?>
                    <?php if ($unresolved_count > 0) : ?>
                        <span class="comment-count"><?php echo esc_html($unresolved_count); ?></span>
                    <?php endif; ?>
                </h4>
                <label class="toggle-resolved">
                    <input type="checkbox" id="show-resolved">
                    <?php _e('Mostrar resueltos', 'flavor-multilingual'); ?>
                </label>
            </div>

            <div class="comments-list">
                <?php if (empty($comments)) : ?>
                    <p class="no-comments"><?php _e('No hay comentarios', 'flavor-multilingual'); ?></p>
                <?php else : ?>
                    <?php foreach ($comments as $comment) : ?>
                        <?php $this->render_comment($comment); ?>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>

            <div class="comment-form">
                <div class="comment-type-selector">
                    <button type="button" class="type-btn active" data-type="note" title="<?php esc_attr_e('Nota', 'flavor-multilingual'); ?>">💬</button>
                    <button type="button" class="type-btn" data-type="suggestion" title="<?php esc_attr_e('Sugerencia', 'flavor-multilingual'); ?>">💡</button>
                    <button type="button" class="type-btn" data-type="issue" title="<?php esc_attr_e('Problema', 'flavor-multilingual'); ?>">⚠️</button>
                    <button type="button" class="type-btn" data-type="question" title="<?php esc_attr_e('Pregunta', 'flavor-multilingual'); ?>">❓</button>
                </div>
                <textarea id="new-comment" placeholder="<?php esc_attr_e('Escribe un comentario...', 'flavor-multilingual'); ?>" rows="2"></textarea>
                <button type="button" id="submit-comment" class="button button-primary">
                    <?php _e('Enviar', 'flavor-multilingual'); ?>
                </button>
            </div>
        </div>
        <?php
    }

    /**
     * Renderizar un comentario individual
     */
    private function render_comment($comment) {
        $type_icons = array(
            'note'       => '💬',
            'suggestion' => '💡',
            'issue'      => '⚠️',
            'question'   => '❓',
        );
        $icon = $type_icons[$comment->comment_type] ?? '💬';
        ?>
        <div class="comment-thread <?php echo $comment->is_resolved ? 'resolved' : ''; ?>" data-id="<?php echo esc_attr($comment->id); ?>">
            <div class="comment-item">
                <img src="<?php echo esc_url($comment->avatar); ?>" alt="" class="comment-avatar">
                <div class="comment-content">
                    <div class="comment-header">
                        <span class="comment-author"><?php echo esc_html($comment->user_name); ?></span>
                        <span class="comment-type"><?php echo $icon; ?></span>
                        <span class="comment-date"><?php echo esc_html(human_time_diff(strtotime($comment->created_at))); ?></span>
                    </div>
                    <div class="comment-text"><?php echo wp_kses_post($comment->comment_text); ?></div>
                    <div class="comment-actions">
                        <button type="button" class="reply-btn"><?php _e('Responder', 'flavor-multilingual'); ?></button>
                        <?php if (!$comment->is_resolved) : ?>
                            <button type="button" class="resolve-btn"><?php _e('Resolver', 'flavor-multilingual'); ?></button>
                        <?php endif; ?>
                        <?php if ($comment->user_id == get_current_user_id() || current_user_can('flavor_manage_translations')) : ?>
                            <button type="button" class="delete-btn"><?php _e('Eliminar', 'flavor-multilingual'); ?></button>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <?php if (!empty($comment->replies)) : ?>
                <div class="comment-replies">
                    <?php foreach ($comment->replies as $reply) : ?>
                        <div class="comment-item reply" data-id="<?php echo esc_attr($reply->id); ?>">
                            <img src="<?php echo esc_url($reply->avatar); ?>" alt="" class="comment-avatar">
                            <div class="comment-content">
                                <div class="comment-header">
                                    <span class="comment-author"><?php echo esc_html($reply->user_name); ?></span>
                                    <span class="comment-date"><?php echo esc_html(human_time_diff(strtotime($reply->created_at))); ?></span>
                                </div>
                                <div class="comment-text"><?php echo wp_kses_post($reply->comment_text); ?></div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>

            <div class="reply-form" style="display:none;">
                <textarea placeholder="<?php esc_attr_e('Escribe una respuesta...', 'flavor-multilingual'); ?>" rows="2"></textarea>
                <button type="button" class="button submit-reply"><?php _e('Responder', 'flavor-multilingual'); ?></button>
                <button type="button" class="button cancel-reply"><?php _e('Cancelar', 'flavor-multilingual'); ?></button>
            </div>
        </div>
        <?php
    }
}

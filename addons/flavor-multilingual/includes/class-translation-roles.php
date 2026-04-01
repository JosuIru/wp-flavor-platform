<?php
/**
 * Sistema de roles y permisos para traducciones
 *
 * Define roles específicos para el flujo de trabajo de traducción:
 * - Translation Manager: Gestiona idiomas, asigna traducciones, aprueba
 * - Translator: Puede traducir contenido asignado
 * - Reviewer: Puede revisar y aprobar traducciones
 *
 * @package FlavorMultilingual
 * @since 1.2.0
 */

if (!defined('ABSPATH')) {
    exit;
}

class Flavor_Translation_Roles {

    /**
     * Instancia singleton
     *
     * @var Flavor_Translation_Roles|null
     */
    private static $instance = null;

    /**
     * Capacidades de traducción (claves sin traducir, usar get_translation_caps() para etiquetas)
     *
     * @var array
     */
    private $translation_caps = array(
        'flavor_translate',
        'flavor_translate_own',
        'flavor_review_translations',
        'flavor_approve_translations',
        'flavor_reject_translations',
        'flavor_manage_languages',
        'flavor_assign_translations',
        'flavor_manage_glossary',
        'flavor_manage_tm',
        'flavor_use_ai_translation',
        'flavor_bulk_translate',
        'flavor_import_export_xliff',
        'flavor_view_translation_stats',
    );

    /**
     * Obtiene las capacidades con sus etiquetas traducidas
     *
     * @return array
     */
    public function get_translation_caps_labels() {
        return array(
            'flavor_translate'              => __('Traducir contenido', 'flavor-multilingual'),
            'flavor_translate_own'          => __('Traducir contenido propio', 'flavor-multilingual'),
            'flavor_review_translations'    => __('Revisar traducciones', 'flavor-multilingual'),
            'flavor_approve_translations'   => __('Aprobar traducciones', 'flavor-multilingual'),
            'flavor_reject_translations'    => __('Rechazar traducciones', 'flavor-multilingual'),
            'flavor_manage_languages'       => __('Gestionar idiomas', 'flavor-multilingual'),
            'flavor_assign_translations'    => __('Asignar traducciones', 'flavor-multilingual'),
            'flavor_manage_glossary'        => __('Gestionar glosario', 'flavor-multilingual'),
            'flavor_manage_tm'              => __('Gestionar memoria de traducción', 'flavor-multilingual'),
            'flavor_use_ai_translation'     => __('Usar traducción automática IA', 'flavor-multilingual'),
            'flavor_bulk_translate'         => __('Traducción masiva', 'flavor-multilingual'),
            'flavor_import_export_xliff'    => __('Importar/exportar XLIFF', 'flavor-multilingual'),
            'flavor_view_translation_stats' => __('Ver estadísticas de traducción', 'flavor-multilingual'),
        );
    }

    /**
     * Roles de traducción predefinidos
     *
     * @var array
     */
    private $translation_roles = array(
        'translation_manager' => array(
            'display_name' => 'Translation Manager',
            'capabilities' => array(
                'read'                        => true,
                'edit_posts'                  => true,
                'edit_others_posts'           => true,
                'edit_published_posts'        => true,
                'flavor_translate'            => true,
                'flavor_translate_own'        => true,
                'flavor_review_translations'  => true,
                'flavor_approve_translations' => true,
                'flavor_reject_translations'  => true,
                'flavor_manage_languages'     => true,
                'flavor_assign_translations'  => true,
                'flavor_manage_glossary'      => true,
                'flavor_manage_tm'            => true,
                'flavor_use_ai_translation'   => true,
                'flavor_bulk_translate'       => true,
                'flavor_import_export_xliff'  => true,
                'flavor_view_translation_stats' => true,
            ),
        ),
        'translator' => array(
            'display_name' => 'Translator',
            'capabilities' => array(
                'read'                      => true,
                'edit_posts'                => true,
                'flavor_translate'          => true,
                'flavor_translate_own'      => true,
                'flavor_use_ai_translation' => true,
            ),
        ),
        'translation_reviewer' => array(
            'display_name' => 'Translation Reviewer',
            'capabilities' => array(
                'read'                        => true,
                'edit_posts'                  => true,
                'flavor_translate'            => true,
                'flavor_translate_own'        => true,
                'flavor_review_translations'  => true,
                'flavor_approve_translations' => true,
                'flavor_reject_translations'  => true,
                'flavor_view_translation_stats' => true,
            ),
        ),
    );

    /**
     * Obtiene la instancia singleton
     *
     * @return Flavor_Translation_Roles
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
        add_action('init', array($this, 'register_capabilities'));
        add_action('admin_init', array($this, 'add_caps_to_admin'));

        // Filtros para verificar permisos
        add_filter('flavor_ml_can_translate', array($this, 'can_translate'), 10, 3);
        add_filter('flavor_ml_can_approve', array($this, 'can_approve'), 10, 2);
        add_filter('flavor_ml_can_assign', array($this, 'can_assign'), 10, 2);

        // AJAX handlers
        add_action('wp_ajax_flavor_ml_assign_translation', array($this, 'ajax_assign_translation'));
        add_action('wp_ajax_flavor_ml_update_translation_status', array($this, 'ajax_update_status'));
        add_action('wp_ajax_flavor_ml_get_assigned_translations', array($this, 'ajax_get_assigned'));
    }

    /**
     * Registra las capacidades en WordPress
     */
    public function register_capabilities() {
        // Solo ejecutar una vez
        if (get_option('flavor_ml_caps_registered_v2')) {
            return;
        }

        // Crear roles de traducción
        foreach ($this->translation_roles as $role_slug => $role_data) {
            // Eliminar si existe para actualizar
            remove_role($role_slug);

            add_role(
                $role_slug,
                $role_data['display_name'],
                $role_data['capabilities']
            );
        }

        update_option('flavor_ml_caps_registered_v2', true);
    }

    /**
     * Añade capacidades de traducción al administrador
     */
    public function add_caps_to_admin() {
        $admin_role = get_role('administrator');

        if (!$admin_role) {
            return;
        }

        // El admin tiene todas las capacidades
        foreach ($this->translation_caps as $cap) {
            if (!$admin_role->has_cap($cap)) {
                $admin_role->add_cap($cap);
            }
        }

        // También dar capacidades básicas a editores
        $editor_role = get_role('editor');
        if ($editor_role) {
            $editor_caps = array(
                'flavor_translate',
                'flavor_translate_own',
                'flavor_use_ai_translation',
                'flavor_view_translation_stats',
            );
            foreach ($editor_caps as $cap) {
                if (!$editor_role->has_cap($cap)) {
                    $editor_role->add_cap($cap);
                }
            }
        }
    }

    /**
     * Verifica si un usuario puede traducir un contenido
     *
     * @param bool $can_translate Valor inicial
     * @param int  $user_id       ID del usuario
     * @param int  $post_id       ID del post (opcional)
     * @return bool
     */
    public function can_translate($can_translate, $user_id, $post_id = 0) {
        $user = get_user_by('id', $user_id);

        if (!$user) {
            return false;
        }

        // Administrador siempre puede
        if (user_can($user, 'manage_options')) {
            return true;
        }

        // Verificar capacidad general
        if (!user_can($user, 'flavor_translate')) {
            // Verificar si puede traducir contenido propio
            if ($post_id && user_can($user, 'flavor_translate_own')) {
                $post = get_post($post_id);
                if ($post && $post->post_author == $user_id) {
                    return true;
                }
            }
            return false;
        }

        // Verificar si tiene asignación específica
        if ($post_id) {
            $assigned_users = $this->get_assigned_translators($post_id);
            if (!empty($assigned_users) && !in_array($user_id, $assigned_users)) {
                // Si hay asignaciones y el usuario no está incluido
                // Solo puede traducir si es manager
                return user_can($user, 'flavor_assign_translations');
            }
        }

        return true;
    }

    /**
     * Verifica si un usuario puede aprobar traducciones
     *
     * @param bool $can_approve Valor inicial
     * @param int  $user_id     ID del usuario
     * @return bool
     */
    public function can_approve($can_approve, $user_id) {
        $user = get_user_by('id', $user_id);

        if (!$user) {
            return false;
        }

        return user_can($user, 'flavor_approve_translations') || user_can($user, 'manage_options');
    }

    /**
     * Verifica si un usuario puede asignar traducciones
     *
     * @param bool $can_assign Valor inicial
     * @param int  $user_id    ID del usuario
     * @return bool
     */
    public function can_assign($can_assign, $user_id) {
        $user = get_user_by('id', $user_id);

        if (!$user) {
            return false;
        }

        return user_can($user, 'flavor_assign_translations') || user_can($user, 'manage_options');
    }

    /**
     * Obtiene los traductores asignados a un contenido
     *
     * @param int    $object_id ID del objeto
     * @param string $type      Tipo de objeto
     * @param string $lang      Idioma específico (opcional)
     * @return array IDs de usuarios asignados
     */
    public function get_assigned_translators($object_id, $type = 'post', $lang = '') {
        $meta_key = '_flavor_ml_translators';

        if ($type === 'post') {
            $assignments = get_post_meta($object_id, $meta_key, true);
        } else {
            $assignments = get_term_meta($object_id, $meta_key, true);
        }

        if (!is_array($assignments)) {
            return array();
        }

        if ($lang && isset($assignments[$lang])) {
            return (array) $assignments[$lang];
        }

        // Devolver todos los asignados
        $all_users = array();
        foreach ($assignments as $lang_code => $users) {
            $all_users = array_merge($all_users, (array) $users);
        }

        return array_unique($all_users);
    }

    /**
     * Asigna un traductor a un contenido
     *
     * @param int    $object_id ID del objeto
     * @param int    $user_id   ID del usuario
     * @param string $lang      Código de idioma
     * @param string $type      Tipo de objeto
     * @return bool
     */
    public function assign_translator($object_id, $user_id, $lang, $type = 'post') {
        $meta_key = '_flavor_ml_translators';

        if ($type === 'post') {
            $assignments = get_post_meta($object_id, $meta_key, true);
        } else {
            $assignments = get_term_meta($object_id, $meta_key, true);
        }

        if (!is_array($assignments)) {
            $assignments = array();
        }

        if (!isset($assignments[$lang])) {
            $assignments[$lang] = array();
        }

        if (!in_array($user_id, $assignments[$lang])) {
            $assignments[$lang][] = $user_id;
        }

        if ($type === 'post') {
            update_post_meta($object_id, $meta_key, $assignments);
        } else {
            update_term_meta($object_id, $meta_key, $assignments);
        }

        // Notificar al traductor
        $this->notify_translator($user_id, $object_id, $lang, $type);

        do_action('flavor_ml_translator_assigned', $object_id, $user_id, $lang, $type);

        return true;
    }

    /**
     * Quita un traductor de un contenido
     *
     * @param int    $object_id ID del objeto
     * @param int    $user_id   ID del usuario
     * @param string $lang      Código de idioma
     * @param string $type      Tipo de objeto
     * @return bool
     */
    public function unassign_translator($object_id, $user_id, $lang, $type = 'post') {
        $meta_key = '_flavor_ml_translators';

        if ($type === 'post') {
            $assignments = get_post_meta($object_id, $meta_key, true);
        } else {
            $assignments = get_term_meta($object_id, $meta_key, true);
        }

        if (!is_array($assignments) || !isset($assignments[$lang])) {
            return false;
        }

        $key = array_search($user_id, $assignments[$lang]);
        if ($key !== false) {
            unset($assignments[$lang][$key]);
            $assignments[$lang] = array_values($assignments[$lang]);
        }

        if ($type === 'post') {
            update_post_meta($object_id, $meta_key, $assignments);
        } else {
            update_term_meta($object_id, $meta_key, $assignments);
        }

        do_action('flavor_ml_translator_unassigned', $object_id, $user_id, $lang, $type);

        return true;
    }

    /**
     * Notifica a un traductor sobre una nueva asignación
     *
     * @param int    $user_id   ID del usuario
     * @param int    $object_id ID del objeto
     * @param string $lang      Código de idioma
     * @param string $type      Tipo de objeto
     */
    private function notify_translator($user_id, $object_id, $lang, $type) {
        $user = get_user_by('id', $user_id);
        if (!$user) {
            return;
        }

        // Obtener información del contenido
        if ($type === 'post') {
            $post = get_post($object_id);
            $title = $post ? $post->post_title : __('Contenido', 'flavor-multilingual');
            $edit_link = admin_url('post.php?post=' . $object_id . '&action=edit&lang=' . $lang);
        } else {
            $term = get_term($object_id);
            $title = $term ? $term->name : __('Término', 'flavor-multilingual');
            $edit_link = admin_url('term.php?taxonomy=' . $term->taxonomy . '&tag_ID=' . $object_id . '&lang=' . $lang);
        }

        // Obtener nombre del idioma
        $core = Flavor_Multilingual_Core::get_instance();
        $languages = $core->get_active_languages();
        $lang_name = isset($languages[$lang]) ? $languages[$lang]['native_name'] : $lang;

        $subject = sprintf(
            __('[%s] Nueva traducción asignada: %s → %s', 'flavor-multilingual'),
            get_bloginfo('name'),
            $title,
            $lang_name
        );

        $message = sprintf(
            __("Hola %s,\n\nSe te ha asignado una nueva traducción:\n\nContenido: %s\nIdioma destino: %s\n\nPuedes comenzar a traducir aquí:\n%s\n\nSaludos,\n%s", 'flavor-multilingual'),
            $user->display_name,
            $title,
            $lang_name,
            $edit_link,
            get_bloginfo('name')
        );

        // Permitir personalizar el email
        $subject = apply_filters('flavor_ml_assignment_email_subject', $subject, $user_id, $object_id, $lang);
        $message = apply_filters('flavor_ml_assignment_email_message', $message, $user_id, $object_id, $lang);

        wp_mail($user->user_email, $subject, $message);
    }

    // ================================================================
    // ESTADOS DE TRADUCCIÓN
    // ================================================================

    /**
     * Estados posibles de una traducción
     *
     * @return array
     */
    public function get_translation_statuses() {
        return array(
            'pending'     => array(
                'label' => __('Pendiente', 'flavor-multilingual'),
                'color' => '#f0ad4e',
                'icon'  => 'clock',
            ),
            'in_progress' => array(
                'label' => __('En progreso', 'flavor-multilingual'),
                'color' => '#5bc0de',
                'icon'  => 'edit',
            ),
            'needs_review' => array(
                'label' => __('Necesita revisión', 'flavor-multilingual'),
                'color' => '#9b59b6',
                'icon'  => 'visibility',
            ),
            'approved'    => array(
                'label' => __('Aprobada', 'flavor-multilingual'),
                'color' => '#5cb85c',
                'icon'  => 'yes-alt',
            ),
            'rejected'    => array(
                'label' => __('Rechazada', 'flavor-multilingual'),
                'color' => '#d9534f',
                'icon'  => 'dismiss',
            ),
            'published'   => array(
                'label' => __('Publicada', 'flavor-multilingual'),
                'color' => '#0073aa',
                'icon'  => 'admin-site',
            ),
        );
    }

    /**
     * Actualiza el estado de una traducción
     *
     * @param int    $object_id ID del objeto
     * @param string $lang      Código de idioma
     * @param string $status    Nuevo estado
     * @param string $type      Tipo de objeto
     * @param string $field     Campo específico (opcional)
     * @return bool
     */
    public function update_translation_status($object_id, $lang, $status, $type = 'post', $field = '') {
        $valid_statuses = array_keys($this->get_translation_statuses());

        if (!in_array($status, $valid_statuses)) {
            return false;
        }

        global $wpdb;
        $table = $wpdb->prefix . 'flavor_translations';

        $where = array(
            'object_type'   => $type,
            'object_id'     => $object_id,
            'language_code' => $lang,
        );

        if ($field) {
            $where['field_name'] = $field;
        }

        $updated = $wpdb->update(
            $table,
            array('status' => $status),
            $where
        );

        if ($updated !== false) {
            // Registrar en historial
            $this->log_status_change($object_id, $lang, $status, $type, $field);

            do_action('flavor_ml_translation_status_changed', $object_id, $lang, $status, $type);
        }

        return $updated !== false;
    }

    /**
     * Registra un cambio de estado en el historial
     *
     * @param int    $object_id ID del objeto
     * @param string $lang      Código de idioma
     * @param string $status    Nuevo estado
     * @param string $type      Tipo de objeto
     * @param string $field     Campo
     */
    private function log_status_change($object_id, $lang, $status, $type, $field) {
        $history_key = '_flavor_ml_status_history';

        $history = array(
            'status'    => $status,
            'user_id'   => get_current_user_id(),
            'timestamp' => current_time('mysql'),
            'field'     => $field,
        );

        if ($type === 'post') {
            $existing = get_post_meta($object_id, $history_key, true) ?: array();
            if (!isset($existing[$lang])) {
                $existing[$lang] = array();
            }
            $existing[$lang][] = $history;
            // Mantener solo últimos 50 cambios por idioma
            $existing[$lang] = array_slice($existing[$lang], -50);
            update_post_meta($object_id, $history_key, $existing);
        }
    }

    /**
     * Obtiene el historial de estados de una traducción
     *
     * @param int    $object_id ID del objeto
     * @param string $lang      Código de idioma
     * @param string $type      Tipo de objeto
     * @return array
     */
    public function get_status_history($object_id, $lang, $type = 'post') {
        $history_key = '_flavor_ml_status_history';

        if ($type === 'post') {
            $history = get_post_meta($object_id, $history_key, true) ?: array();
        } else {
            $history = get_term_meta($object_id, $history_key, true) ?: array();
        }

        return isset($history[$lang]) ? $history[$lang] : array();
    }

    // ================================================================
    // AJAX HANDLERS
    // ================================================================

    /**
     * AJAX: Asignar traducción a usuario
     */
    public function ajax_assign_translation() {
        check_ajax_referer('flavor_multilingual', 'nonce');

        if (!apply_filters('flavor_ml_can_assign', false, get_current_user_id())) {
            wp_send_json_error(__('Sin permisos para asignar traducciones', 'flavor-multilingual'));
        }

        $object_id = intval($_POST['object_id'] ?? 0);
        $user_id = intval($_POST['user_id'] ?? 0);
        $lang = sanitize_key($_POST['lang'] ?? '');
        $type = sanitize_key($_POST['type'] ?? 'post');

        if (!$object_id || !$user_id || !$lang) {
            wp_send_json_error(__('Parámetros inválidos', 'flavor-multilingual'));
        }

        $result = $this->assign_translator($object_id, $user_id, $lang, $type);

        if ($result) {
            wp_send_json_success(array(
                'message' => __('Traductor asignado correctamente', 'flavor-multilingual'),
            ));
        } else {
            wp_send_json_error(__('Error al asignar traductor', 'flavor-multilingual'));
        }
    }

    /**
     * AJAX: Actualizar estado de traducción
     */
    public function ajax_update_status() {
        check_ajax_referer('flavor_multilingual', 'nonce');

        $object_id = intval($_POST['object_id'] ?? 0);
        $lang = sanitize_key($_POST['lang'] ?? '');
        $status = sanitize_key($_POST['status'] ?? '');
        $type = sanitize_key($_POST['type'] ?? 'post');

        if (!$object_id || !$lang || !$status) {
            wp_send_json_error(__('Parámetros inválidos', 'flavor-multilingual'));
        }

        // Verificar permisos según estado
        $user_id = get_current_user_id();

        if (in_array($status, array('approved', 'rejected'))) {
            if (!apply_filters('flavor_ml_can_approve', false, $user_id)) {
                wp_send_json_error(__('Sin permisos para aprobar/rechazar', 'flavor-multilingual'));
            }
        }

        $result = $this->update_translation_status($object_id, $lang, $status, $type);

        if ($result) {
            $statuses = $this->get_translation_statuses();
            wp_send_json_success(array(
                'message' => sprintf(__('Estado actualizado a: %s', 'flavor-multilingual'), $statuses[$status]['label']),
                'status'  => $status,
                'label'   => $statuses[$status]['label'],
                'color'   => $statuses[$status]['color'],
            ));
        } else {
            wp_send_json_error(__('Error al actualizar estado', 'flavor-multilingual'));
        }
    }

    /**
     * AJAX: Obtener traducciones asignadas al usuario actual
     */
    public function ajax_get_assigned() {
        check_ajax_referer('flavor_multilingual', 'nonce');

        $user_id = get_current_user_id();
        $status = sanitize_key($_GET['status'] ?? '');
        $lang = sanitize_key($_GET['lang'] ?? '');

        $assignments = $this->get_user_assignments($user_id, $status, $lang);

        wp_send_json_success($assignments);
    }

    /**
     * Obtiene las asignaciones de un usuario
     *
     * @param int    $user_id ID del usuario
     * @param string $status  Filtrar por estado
     * @param string $lang    Filtrar por idioma
     * @return array
     */
    public function get_user_assignments($user_id, $status = '', $lang = '') {
        global $wpdb;

        $meta_key = '_flavor_ml_translators';

        // Buscar posts donde el usuario esté asignado
        $posts = $wpdb->get_results($wpdb->prepare(
            "SELECT p.ID, p.post_title, p.post_type, pm.meta_value
             FROM {$wpdb->posts} p
             INNER JOIN {$wpdb->postmeta} pm ON p.ID = pm.post_id
             WHERE pm.meta_key = %s
             AND p.post_status != 'trash'
             ORDER BY p.post_modified DESC
             LIMIT 100",
            $meta_key
        ));

        $assignments = array();

        foreach ($posts as $post) {
            $translators = maybe_unserialize($post->meta_value);

            if (!is_array($translators)) {
                continue;
            }

            foreach ($translators as $lang_code => $users) {
                if (!in_array($user_id, (array) $users)) {
                    continue;
                }

                if ($lang && $lang !== $lang_code) {
                    continue;
                }

                // Obtener estado de la traducción
                $translation_status = $this->get_translation_status($post->ID, $lang_code);

                if ($status && $status !== $translation_status) {
                    continue;
                }

                $assignments[] = array(
                    'id'        => $post->ID,
                    'title'     => $post->post_title,
                    'type'      => $post->post_type,
                    'lang'      => $lang_code,
                    'status'    => $translation_status,
                    'edit_link' => admin_url('post.php?post=' . $post->ID . '&action=edit&lang=' . $lang_code),
                );
            }
        }

        return $assignments;
    }

    /**
     * Obtiene el estado de traducción de un objeto
     *
     * @param int    $object_id ID del objeto
     * @param string $lang      Código de idioma
     * @param string $type      Tipo de objeto
     * @return string
     */
    public function get_translation_status($object_id, $lang, $type = 'post') {
        global $wpdb;
        $table = $wpdb->prefix . 'flavor_translations';

        $status = $wpdb->get_var($wpdb->prepare(
            "SELECT status FROM {$table}
             WHERE object_type = %s AND object_id = %d AND language_code = %s
             ORDER BY updated_at DESC LIMIT 1",
            $type,
            $object_id,
            $lang
        ));

        return $status ?: 'pending';
    }

    // ================================================================
    // UTILIDADES
    // ================================================================

    /**
     * Obtiene usuarios con capacidad de traducción
     *
     * @param string $capability Capacidad específica
     * @return array
     */
    public function get_translators($capability = 'flavor_translate') {
        $users = get_users(array(
            'capability' => $capability,
            'orderby'    => 'display_name',
            'order'      => 'ASC',
        ));

        // También incluir administradores
        $admins = get_users(array(
            'role'    => 'administrator',
            'orderby' => 'display_name',
            'order'   => 'ASC',
        ));

        $all_users = array_merge($users, $admins);

        // Eliminar duplicados
        $unique_users = array();
        $seen_ids = array();

        foreach ($all_users as $user) {
            if (!in_array($user->ID, $seen_ids)) {
                $unique_users[] = $user;
                $seen_ids[] = $user->ID;
            }
        }

        return $unique_users;
    }

    /**
     * Obtiene estadísticas de traducción de un usuario
     *
     * @param int $user_id ID del usuario
     * @return array
     */
    public function get_user_stats($user_id) {
        global $wpdb;
        $table = $wpdb->prefix . 'flavor_translations';

        // Traducciones completadas
        $completed = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM {$table}
             WHERE translator = %s AND status IN ('approved', 'published')",
            'user_' . $user_id
        ));

        // Traducciones pendientes de revisión
        $pending_review = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM {$table}
             WHERE translator = %s AND status = 'needs_review'",
            'user_' . $user_id
        ));

        // Traducciones en progreso
        $in_progress = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM {$table}
             WHERE translator = %s AND status = 'in_progress'",
            'user_' . $user_id
        ));

        // Palabras traducidas (aproximado)
        $words = $wpdb->get_var($wpdb->prepare(
            "SELECT SUM(LENGTH(translation) - LENGTH(REPLACE(translation, ' ', '')) + 1)
             FROM {$table}
             WHERE translator = %s AND status IN ('approved', 'published')",
            'user_' . $user_id
        ));

        return array(
            'completed'      => (int) $completed,
            'pending_review' => (int) $pending_review,
            'in_progress'    => (int) $in_progress,
            'words'          => (int) $words,
        );
    }

    /**
     * Limpia roles y capacidades al desinstalar
     */
    public static function uninstall() {
        // Eliminar roles
        remove_role('translation_manager');
        remove_role('translator');
        remove_role('translation_reviewer');

        // Eliminar capacidades de admin y editor
        $admin = get_role('administrator');
        $editor = get_role('editor');

        $caps = array(
            'flavor_translate',
            'flavor_translate_own',
            'flavor_review_translations',
            'flavor_approve_translations',
            'flavor_reject_translations',
            'flavor_manage_languages',
            'flavor_assign_translations',
            'flavor_manage_glossary',
            'flavor_manage_tm',
            'flavor_use_ai_translation',
            'flavor_bulk_translate',
            'flavor_import_export_xliff',
            'flavor_view_translation_stats',
        );

        foreach ($caps as $cap) {
            if ($admin) {
                $admin->remove_cap($cap);
            }
            if ($editor) {
                $editor->remove_cap($cap);
            }
        }

        delete_option('flavor_ml_caps_registered_v2');
    }
}

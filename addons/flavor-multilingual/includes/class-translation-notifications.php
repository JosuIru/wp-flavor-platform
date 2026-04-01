<?php
/**
 * Sistema de notificaciones para traducciones
 *
 * Envía emails cuando se asignan traducciones, se solicitan revisiones, etc.
 *
 * @package FlavorMultilingual
 * @since 1.3.0
 */

if (!defined('ABSPATH')) {
    exit;
}

class Flavor_Translation_Notifications {

    /**
     * Instancia singleton
     */
    private static $instance = null;

    /**
     * Tipos de notificación
     */
    private $notification_types = array(
        'translation_assigned',
        'translation_needs_review',
        'translation_approved',
        'translation_rejected',
        'translation_published',
        'bulk_translation_complete',
    );

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
        $this->init_hooks();
    }

    /**
     * Inicializar hooks
     */
    private function init_hooks() {
        // Hooks de traducción
        add_action('flavor_ml_translation_assigned', array($this, 'on_translation_assigned'), 10, 3);
        add_action('flavor_ml_translation_status_changed', array($this, 'on_status_changed'), 10, 4);
        add_action('flavor_ml_bulk_translation_complete', array($this, 'on_bulk_complete'), 10, 2);

        // Admin settings
        add_action('admin_init', array($this, 'register_settings'));

        // Email template hooks
        add_filter('flavor_ml_email_headers', array($this, 'get_email_headers'));
        add_filter('flavor_ml_email_footer', array($this, 'get_email_footer'));
    }

    /**
     * Registrar configuración
     */
    public function register_settings() {
        register_setting('flavor_multilingual_notifications', 'flavor_ml_notification_settings', array(
            'type'              => 'array',
            'sanitize_callback' => array($this, 'sanitize_settings'),
            'default'           => $this->get_default_settings(),
        ));
    }

    /**
     * Configuración por defecto
     */
    private function get_default_settings() {
        return array(
            'enabled'                     => true,
            'from_name'                   => get_bloginfo('name'),
            'from_email'                  => get_option('admin_email'),
            'notify_translation_assigned' => true,
            'notify_needs_review'         => true,
            'notify_approved'             => true,
            'notify_rejected'             => true,
            'notify_published'            => false,
            'notify_bulk_complete'        => true,
            'notify_managers_on_submit'   => true,
            'use_html_emails'             => true,
        );
    }

    /**
     * Obtener configuración
     */
    public function get_settings() {
        $saved = get_option('flavor_ml_notification_settings', array());
        return wp_parse_args($saved, $this->get_default_settings());
    }

    /**
     * Sanitizar configuración
     */
    public function sanitize_settings($settings) {
        $clean = array();

        $clean['enabled'] = !empty($settings['enabled']);
        $clean['from_name'] = sanitize_text_field($settings['from_name'] ?? '');
        $clean['from_email'] = sanitize_email($settings['from_email'] ?? '');
        $clean['notify_translation_assigned'] = !empty($settings['notify_translation_assigned']);
        $clean['notify_needs_review'] = !empty($settings['notify_needs_review']);
        $clean['notify_approved'] = !empty($settings['notify_approved']);
        $clean['notify_rejected'] = !empty($settings['notify_rejected']);
        $clean['notify_published'] = !empty($settings['notify_published']);
        $clean['notify_bulk_complete'] = !empty($settings['notify_bulk_complete']);
        $clean['notify_managers_on_submit'] = !empty($settings['notify_managers_on_submit']);
        $clean['use_html_emails'] = !empty($settings['use_html_emails']);

        return $clean;
    }

    /**
     * Verificar si las notificaciones están habilitadas
     */
    public function is_enabled($type = null) {
        $settings = $this->get_settings();

        if (!$settings['enabled']) {
            return false;
        }

        if ($type && isset($settings['notify_' . $type])) {
            return $settings['notify_' . $type];
        }

        return true;
    }

    /**
     * Hook: Traducción asignada
     */
    public function on_translation_assigned($post_id, $translator_id, $languages) {
        if (!$this->is_enabled('translation_assigned')) {
            return;
        }

        $translator = get_user_by('id', $translator_id);
        if (!$translator || !$translator->user_email) {
            return;
        }

        $post = get_post($post_id);
        if (!$post) {
            return;
        }

        $language_manager = Flavor_Language_Manager::get_instance();
        $language_names = array();

        foreach ((array) $languages as $lang_code) {
            $lang = $language_manager->get_language($lang_code);
            if ($lang) {
                $language_names[] = $lang['native_name'];
            }
        }

        $assigner = wp_get_current_user();

        $subject = sprintf(
            /* translators: %s: post title */
            __('[Traducción] Se te ha asignado: %s', 'flavor-multilingual'),
            $post->post_title
        );

        $template_data = array(
            'translator_name'  => $translator->display_name,
            'post_title'       => $post->post_title,
            'post_type'        => get_post_type_object($post->post_type)->labels->singular_name,
            'languages'        => $language_names,
            'assigner_name'    => $assigner->display_name,
            'edit_url'         => $this->get_translation_edit_url($post_id),
            'dashboard_url'    => admin_url('admin.php?page=flavor-multilingual-translations'),
        );

        $message = $this->render_email_template('translation-assigned', $template_data);

        $this->send_email($translator->user_email, $subject, $message);
    }

    /**
     * Hook: Estado de traducción cambiado
     */
    public function on_status_changed($post_id, $language, $old_status, $new_status) {
        $settings = $this->get_settings();

        // Determinar qué notificación enviar
        switch ($new_status) {
            case 'needs_review':
                if ($settings['notify_needs_review']) {
                    $this->notify_reviewers($post_id, $language);
                }
                if ($settings['notify_managers_on_submit']) {
                    $this->notify_managers_on_submit($post_id, $language);
                }
                break;

            case 'approved':
                if ($settings['notify_approved']) {
                    $this->notify_translator_approved($post_id, $language);
                }
                break;

            case 'rejected':
                if ($settings['notify_rejected']) {
                    $this->notify_translator_rejected($post_id, $language);
                }
                break;

            case 'published':
                if ($settings['notify_published']) {
                    $this->notify_translation_published($post_id, $language);
                }
                break;
        }
    }

    /**
     * Notificar a revisores
     */
    private function notify_reviewers($post_id, $language) {
        $reviewers = $this->get_users_with_capability('flavor_review_translations');
        $post = get_post($post_id);

        if (empty($reviewers) || !$post) {
            return;
        }

        $language_manager = Flavor_Language_Manager::get_instance();
        $lang = $language_manager->get_language($language);
        $translator = $this->get_translation_translator($post_id, $language);

        $subject = sprintf(
            /* translators: %s: post title */
            __('[Revisión] Traducción pendiente: %s', 'flavor-multilingual'),
            $post->post_title
        );

        $template_data = array(
            'post_title'       => $post->post_title,
            'language_name'    => $lang ? $lang['native_name'] : $language,
            'translator_name'  => $translator ? $translator->display_name : __('Desconocido', 'flavor-multilingual'),
            'review_url'       => $this->get_translation_edit_url($post_id, $language),
        );

        $message = $this->render_email_template('needs-review', $template_data);

        foreach ($reviewers as $reviewer) {
            $this->send_email($reviewer->user_email, $subject, $message);
        }
    }

    /**
     * Notificar a managers cuando se envía traducción
     */
    private function notify_managers_on_submit($post_id, $language) {
        $managers = $this->get_users_with_capability('flavor_manage_translations');
        $post = get_post($post_id);

        if (empty($managers) || !$post) {
            return;
        }

        $language_manager = Flavor_Language_Manager::get_instance();
        $lang = $language_manager->get_language($language);
        $translator = wp_get_current_user();

        $subject = sprintf(
            /* translators: %s: post title */
            __('[Traducción enviada] %s', 'flavor-multilingual'),
            $post->post_title
        );

        $template_data = array(
            'post_title'       => $post->post_title,
            'language_name'    => $lang ? $lang['native_name'] : $language,
            'translator_name'  => $translator->display_name,
            'review_url'       => $this->get_translation_edit_url($post_id, $language),
        );

        $message = $this->render_email_template('translation-submitted', $template_data);

        foreach ($managers as $manager) {
            $this->send_email($manager->user_email, $subject, $message);
        }
    }

    /**
     * Notificar al traductor que fue aprobada
     */
    private function notify_translator_approved($post_id, $language) {
        $translator = $this->get_translation_translator($post_id, $language);
        $post = get_post($post_id);

        if (!$translator || !$post) {
            return;
        }

        $language_manager = Flavor_Language_Manager::get_instance();
        $lang = $language_manager->get_language($language);
        $reviewer = wp_get_current_user();

        $subject = sprintf(
            /* translators: %s: post title */
            __('[Aprobada] Tu traducción de: %s', 'flavor-multilingual'),
            $post->post_title
        );

        $template_data = array(
            'translator_name'  => $translator->display_name,
            'post_title'       => $post->post_title,
            'language_name'    => $lang ? $lang['native_name'] : $language,
            'reviewer_name'    => $reviewer->display_name,
            'view_url'         => get_permalink($post_id),
        );

        $message = $this->render_email_template('translation-approved', $template_data);

        $this->send_email($translator->user_email, $subject, $message);
    }

    /**
     * Notificar al traductor que fue rechazada
     */
    private function notify_translator_rejected($post_id, $language) {
        $translator = $this->get_translation_translator($post_id, $language);
        $post = get_post($post_id);

        if (!$translator || !$post) {
            return;
        }

        $language_manager = Flavor_Language_Manager::get_instance();
        $lang = $language_manager->get_language($language);
        $reviewer = wp_get_current_user();

        // Obtener comentarios de rechazo si existen
        $rejection_notes = get_post_meta($post_id, '_flavor_ml_rejection_notes_' . $language, true);

        $subject = sprintf(
            /* translators: %s: post title */
            __('[Cambios solicitados] Tu traducción de: %s', 'flavor-multilingual'),
            $post->post_title
        );

        $template_data = array(
            'translator_name'  => $translator->display_name,
            'post_title'       => $post->post_title,
            'language_name'    => $lang ? $lang['native_name'] : $language,
            'reviewer_name'    => $reviewer->display_name,
            'rejection_notes'  => $rejection_notes,
            'edit_url'         => $this->get_translation_edit_url($post_id, $language),
        );

        $message = $this->render_email_template('translation-rejected', $template_data);

        $this->send_email($translator->user_email, $subject, $message);
    }

    /**
     * Notificar que la traducción fue publicada
     */
    private function notify_translation_published($post_id, $language) {
        $translator = $this->get_translation_translator($post_id, $language);
        $post = get_post($post_id);

        if (!$translator || !$post) {
            return;
        }

        $language_manager = Flavor_Language_Manager::get_instance();
        $lang = $language_manager->get_language($language);

        $subject = sprintf(
            /* translators: %s: post title */
            __('[Publicada] Tu traducción de: %s', 'flavor-multilingual'),
            $post->post_title
        );

        $url_manager = Flavor_URL_Manager::get_instance();
        $translated_url = $url_manager->get_url_for_language($language, get_permalink($post_id));

        $template_data = array(
            'translator_name'  => $translator->display_name,
            'post_title'       => $post->post_title,
            'language_name'    => $lang ? $lang['native_name'] : $language,
            'view_url'         => $translated_url,
        );

        $message = $this->render_email_template('translation-published', $template_data);

        $this->send_email($translator->user_email, $subject, $message);
    }

    /**
     * Hook: Traducción masiva completada
     */
    public function on_bulk_complete($user_id, $stats) {
        if (!$this->is_enabled('bulk_complete')) {
            return;
        }

        $user = get_user_by('id', $user_id);
        if (!$user || !$user->user_email) {
            return;
        }

        $subject = __('[Traducción] Proceso masivo completado', 'flavor-multilingual');

        $template_data = array(
            'user_name'     => $user->display_name,
            'total'         => $stats['total'] ?? 0,
            'success'       => $stats['success'] ?? 0,
            'failed'        => $stats['failed'] ?? 0,
            'languages'     => $stats['languages'] ?? array(),
            'dashboard_url' => admin_url('admin.php?page=flavor-multilingual-translations'),
        );

        $message = $this->render_email_template('bulk-complete', $template_data);

        $this->send_email($user->user_email, $subject, $message);
    }

    /**
     * Renderizar plantilla de email
     */
    private function render_email_template($template, $data) {
        $settings = $this->get_settings();

        if ($settings['use_html_emails']) {
            return $this->render_html_template($template, $data);
        }

        return $this->render_text_template($template, $data);
    }

    /**
     * Renderizar plantilla HTML
     */
    private function render_html_template($template, $data) {
        $templates = $this->get_html_templates();
        $template_content = $templates[$template] ?? '';

        if (empty($template_content)) {
            return $this->render_text_template($template, $data);
        }

        // Reemplazar variables
        foreach ($data as $key => $value) {
            if (is_array($value)) {
                $value = implode(', ', $value);
            }
            $template_content = str_replace('{{' . $key . '}}', esc_html($value), $template_content);
        }

        // Envolver en layout HTML
        return $this->wrap_html_email($template_content);
    }

    /**
     * Renderizar plantilla de texto
     */
    private function render_text_template($template, $data) {
        $templates = $this->get_text_templates();
        $template_content = $templates[$template] ?? '';

        // Reemplazar variables
        foreach ($data as $key => $value) {
            if (is_array($value)) {
                $value = implode(', ', $value);
            }
            $template_content = str_replace('{{' . $key . '}}', $value, $template_content);
        }

        return $template_content;
    }

    /**
     * Obtener plantillas HTML
     */
    private function get_html_templates() {
        return array(
            'translation-assigned' => '
                <h2>' . __('Nueva traducción asignada', 'flavor-multilingual') . '</h2>
                <p>' . __('Hola {{translator_name}},', 'flavor-multilingual') . '</p>
                <p>' . __('Se te ha asignado una nueva traducción:', 'flavor-multilingual') . '</p>
                <div style="background:#f5f5f5;padding:15px;border-radius:5px;margin:20px 0;">
                    <strong>' . __('Contenido:', 'flavor-multilingual') . '</strong> {{post_title}}<br>
                    <strong>' . __('Tipo:', 'flavor-multilingual') . '</strong> {{post_type}}<br>
                    <strong>' . __('Idiomas:', 'flavor-multilingual') . '</strong> {{languages}}<br>
                    <strong>' . __('Asignado por:', 'flavor-multilingual') . '</strong> {{assigner_name}}
                </div>
                <p style="text-align:center;">
                    <a href="{{edit_url}}" style="display:inline-block;padding:12px 24px;background:#2563eb;color:#fff;text-decoration:none;border-radius:5px;">' . __('Comenzar traducción', 'flavor-multilingual') . '</a>
                </p>
            ',

            'needs-review' => '
                <h2>' . __('Traducción pendiente de revisión', 'flavor-multilingual') . '</h2>
                <p>' . __('Una nueva traducción está lista para ser revisada:', 'flavor-multilingual') . '</p>
                <div style="background:#f5f5f5;padding:15px;border-radius:5px;margin:20px 0;">
                    <strong>' . __('Contenido:', 'flavor-multilingual') . '</strong> {{post_title}}<br>
                    <strong>' . __('Idioma:', 'flavor-multilingual') . '</strong> {{language_name}}<br>
                    <strong>' . __('Traductor:', 'flavor-multilingual') . '</strong> {{translator_name}}
                </div>
                <p style="text-align:center;">
                    <a href="{{review_url}}" style="display:inline-block;padding:12px 24px;background:#2563eb;color:#fff;text-decoration:none;border-radius:5px;">' . __('Revisar traducción', 'flavor-multilingual') . '</a>
                </p>
            ',

            'translation-submitted' => '
                <h2>' . __('Nueva traducción enviada', 'flavor-multilingual') . '</h2>
                <p>' . __('{{translator_name}} ha enviado una traducción para revisión:', 'flavor-multilingual') . '</p>
                <div style="background:#f5f5f5;padding:15px;border-radius:5px;margin:20px 0;">
                    <strong>' . __('Contenido:', 'flavor-multilingual') . '</strong> {{post_title}}<br>
                    <strong>' . __('Idioma:', 'flavor-multilingual') . '</strong> {{language_name}}
                </div>
                <p style="text-align:center;">
                    <a href="{{review_url}}" style="display:inline-block;padding:12px 24px;background:#2563eb;color:#fff;text-decoration:none;border-radius:5px;">' . __('Ver traducción', 'flavor-multilingual') . '</a>
                </p>
            ',

            'translation-approved' => '
                <h2>' . __('¡Traducción aprobada!', 'flavor-multilingual') . '</h2>
                <p>' . __('Hola {{translator_name}},', 'flavor-multilingual') . '</p>
                <p>' . __('Tu traducción ha sido aprobada:', 'flavor-multilingual') . '</p>
                <div style="background:#dcfce7;padding:15px;border-radius:5px;margin:20px 0;">
                    <strong>' . __('Contenido:', 'flavor-multilingual') . '</strong> {{post_title}}<br>
                    <strong>' . __('Idioma:', 'flavor-multilingual') . '</strong> {{language_name}}<br>
                    <strong>' . __('Aprobado por:', 'flavor-multilingual') . '</strong> {{reviewer_name}}
                </div>
                <p style="text-align:center;">
                    <a href="{{view_url}}" style="display:inline-block;padding:12px 24px;background:#16a34a;color:#fff;text-decoration:none;border-radius:5px;">' . __('Ver publicación', 'flavor-multilingual') . '</a>
                </p>
            ',

            'translation-rejected' => '
                <h2>' . __('Cambios solicitados en tu traducción', 'flavor-multilingual') . '</h2>
                <p>' . __('Hola {{translator_name}},', 'flavor-multilingual') . '</p>
                <p>' . __('Se han solicitado cambios en tu traducción:', 'flavor-multilingual') . '</p>
                <div style="background:#fef2f2;padding:15px;border-radius:5px;margin:20px 0;">
                    <strong>' . __('Contenido:', 'flavor-multilingual') . '</strong> {{post_title}}<br>
                    <strong>' . __('Idioma:', 'flavor-multilingual') . '</strong> {{language_name}}<br>
                    <strong>' . __('Revisor:', 'flavor-multilingual') . '</strong> {{reviewer_name}}
                </div>
                {{#rejection_notes}}
                <div style="background:#fff;padding:15px;border-left:4px solid #ef4444;margin:20px 0;">
                    <strong>' . __('Comentarios:', 'flavor-multilingual') . '</strong><br>
                    {{rejection_notes}}
                </div>
                {{/rejection_notes}}
                <p style="text-align:center;">
                    <a href="{{edit_url}}" style="display:inline-block;padding:12px 24px;background:#2563eb;color:#fff;text-decoration:none;border-radius:5px;">' . __('Editar traducción', 'flavor-multilingual') . '</a>
                </p>
            ',

            'translation-published' => '
                <h2>' . __('¡Tu traducción ha sido publicada!', 'flavor-multilingual') . '</h2>
                <p>' . __('Hola {{translator_name}},', 'flavor-multilingual') . '</p>
                <p>' . __('Tu traducción ya está disponible para los visitantes:', 'flavor-multilingual') . '</p>
                <div style="background:#dbeafe;padding:15px;border-radius:5px;margin:20px 0;">
                    <strong>' . __('Contenido:', 'flavor-multilingual') . '</strong> {{post_title}}<br>
                    <strong>' . __('Idioma:', 'flavor-multilingual') . '</strong> {{language_name}}
                </div>
                <p style="text-align:center;">
                    <a href="{{view_url}}" style="display:inline-block;padding:12px 24px;background:#2563eb;color:#fff;text-decoration:none;border-radius:5px;">' . __('Ver publicación', 'flavor-multilingual') . '</a>
                </p>
            ',

            'bulk-complete' => '
                <h2>' . __('Proceso de traducción masiva completado', 'flavor-multilingual') . '</h2>
                <p>' . __('Hola {{user_name}},', 'flavor-multilingual') . '</p>
                <p>' . __('El proceso de traducción masiva ha terminado:', 'flavor-multilingual') . '</p>
                <div style="background:#f5f5f5;padding:15px;border-radius:5px;margin:20px 0;">
                    <strong>' . __('Total procesados:', 'flavor-multilingual') . '</strong> {{total}}<br>
                    <strong style="color:#16a34a;">' . __('Exitosos:', 'flavor-multilingual') . '</strong> {{success}}<br>
                    <strong style="color:#ef4444;">' . __('Fallidos:', 'flavor-multilingual') . '</strong> {{failed}}<br>
                    <strong>' . __('Idiomas:', 'flavor-multilingual') . '</strong> {{languages}}
                </div>
                <p style="text-align:center;">
                    <a href="{{dashboard_url}}" style="display:inline-block;padding:12px 24px;background:#2563eb;color:#fff;text-decoration:none;border-radius:5px;">' . __('Ver traducciones', 'flavor-multilingual') . '</a>
                </p>
            ',
        );
    }

    /**
     * Obtener plantillas de texto
     */
    private function get_text_templates() {
        return array(
            'translation-assigned' => __('Hola {{translator_name}},

Se te ha asignado una nueva traducción:

Contenido: {{post_title}}
Tipo: {{post_type}}
Idiomas: {{languages}}
Asignado por: {{assigner_name}}

Puedes comenzar la traducción aquí: {{edit_url}}

Saludos,
{{site_name}}', 'flavor-multilingual'),

            'needs-review' => __('Una nueva traducción está lista para ser revisada:

Contenido: {{post_title}}
Idioma: {{language_name}}
Traductor: {{translator_name}}

Revisar aquí: {{review_url}}

Saludos,
{{site_name}}', 'flavor-multilingual'),

            'translation-approved' => __('Hola {{translator_name}},

Tu traducción ha sido aprobada:

Contenido: {{post_title}}
Idioma: {{language_name}}
Aprobado por: {{reviewer_name}}

Ver publicación: {{view_url}}

Saludos,
{{site_name}}', 'flavor-multilingual'),

            'translation-rejected' => __('Hola {{translator_name}},

Se han solicitado cambios en tu traducción:

Contenido: {{post_title}}
Idioma: {{language_name}}
Revisor: {{reviewer_name}}

Comentarios: {{rejection_notes}}

Editar aquí: {{edit_url}}

Saludos,
{{site_name}}', 'flavor-multilingual'),
        );
    }

    /**
     * Envolver email en layout HTML
     */
    private function wrap_html_email($content) {
        $settings = $this->get_settings();
        $site_name = get_bloginfo('name');
        $site_url = home_url();

        return '<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
</head>
<body style="margin:0;padding:0;font-family:-apple-system,BlinkMacSystemFont,\'Segoe UI\',Roboto,\'Helvetica Neue\',Arial,sans-serif;font-size:16px;line-height:1.5;color:#1f2937;background:#f3f4f6;">
    <table width="100%" cellpadding="0" cellspacing="0" style="background:#f3f4f6;padding:40px 20px;">
        <tr>
            <td align="center">
                <table width="600" cellpadding="0" cellspacing="0" style="background:#fff;border-radius:8px;overflow:hidden;box-shadow:0 1px 3px rgba(0,0,0,0.1);">
                    <!-- Header -->
                    <tr>
                        <td style="background:#2563eb;padding:24px;text-align:center;">
                            <a href="' . esc_url($site_url) . '" style="color:#fff;font-size:24px;font-weight:bold;text-decoration:none;">' . esc_html($site_name) . '</a>
                        </td>
                    </tr>
                    <!-- Content -->
                    <tr>
                        <td style="padding:32px 24px;">
                            ' . $content . '
                        </td>
                    </tr>
                    <!-- Footer -->
                    <tr>
                        <td style="background:#f9fafb;padding:24px;text-align:center;font-size:13px;color:#6b7280;">
                            ' . apply_filters('flavor_ml_email_footer', sprintf(
                                /* translators: %s: site name */
                                __('Este email fue enviado por %s', 'flavor-multilingual'),
                                '<a href="' . esc_url($site_url) . '" style="color:#2563eb;">' . esc_html($site_name) . '</a>'
                            )) . '
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
</body>
</html>';
    }

    /**
     * Enviar email
     */
    private function send_email($to, $subject, $message) {
        $settings = $this->get_settings();

        $headers = apply_filters('flavor_ml_email_headers', array(
            'Content-Type: ' . ($settings['use_html_emails'] ? 'text/html' : 'text/plain') . '; charset=UTF-8',
            'From: ' . $settings['from_name'] . ' <' . $settings['from_email'] . '>',
        ));

        $result = wp_mail($to, $subject, $message, $headers);

        // Log para debug
        if (defined('WP_DEBUG') && WP_DEBUG && !$result) {
            error_log('Flavor Multilingual: Failed to send email to ' . $to);
        }

        return $result;
    }

    /**
     * Obtener cabeceras de email
     */
    public function get_email_headers($headers = array()) {
        return $headers;
    }

    /**
     * Obtener footer de email
     */
    public function get_email_footer($footer) {
        return $footer;
    }

    /**
     * Obtener usuarios con una capacidad
     */
    private function get_users_with_capability($capability) {
        $users = get_users(array(
            'capability' => $capability,
            'fields'     => array('ID', 'user_email', 'display_name'),
        ));

        return $users;
    }

    /**
     * Obtener traductor de una traducción
     */
    private function get_translation_translator($post_id, $language) {
        $translator_id = get_post_meta($post_id, '_flavor_ml_translator_' . $language, true);

        if ($translator_id) {
            return get_user_by('id', $translator_id);
        }

        return null;
    }

    /**
     * Obtener URL de edición de traducción
     */
    private function get_translation_edit_url($post_id, $language = null) {
        $url = admin_url('admin.php?page=flavor-multilingual-translate&post_id=' . $post_id);

        if ($language) {
            $url .= '&lang=' . $language;
        }

        return $url;
    }

    /**
     * Renderizar panel de configuración
     */
    public function render_settings_panel() {
        $settings = $this->get_settings();
        ?>
        <div class="flavor-ml-notifications-config">
            <h3><?php _e('Configuración de Notificaciones', 'flavor-multilingual'); ?></h3>

            <table class="form-table">
                <tr>
                    <th scope="row"><?php _e('Habilitar notificaciones', 'flavor-multilingual'); ?></th>
                    <td>
                        <label>
                            <input type="checkbox" name="flavor_ml_notification_settings[enabled]" value="1" <?php checked($settings['enabled']); ?>>
                            <?php _e('Enviar notificaciones por email', 'flavor-multilingual'); ?>
                        </label>
                    </td>
                </tr>

                <tr>
                    <th scope="row"><?php _e('Remitente', 'flavor-multilingual'); ?></th>
                    <td>
                        <input type="text" name="flavor_ml_notification_settings[from_name]" value="<?php echo esc_attr($settings['from_name']); ?>" class="regular-text">
                        <br>
                        <input type="email" name="flavor_ml_notification_settings[from_email]" value="<?php echo esc_attr($settings['from_email']); ?>" class="regular-text">
                        <p class="description"><?php _e('Nombre y email del remitente', 'flavor-multilingual'); ?></p>
                    </td>
                </tr>

                <tr>
                    <th scope="row"><?php _e('Notificar cuando', 'flavor-multilingual'); ?></th>
                    <td>
                        <fieldset>
                            <label>
                                <input type="checkbox" name="flavor_ml_notification_settings[notify_translation_assigned]" value="1" <?php checked($settings['notify_translation_assigned']); ?>>
                                <?php _e('Se asigna una traducción', 'flavor-multilingual'); ?>
                            </label><br>
                            <label>
                                <input type="checkbox" name="flavor_ml_notification_settings[notify_needs_review]" value="1" <?php checked($settings['notify_needs_review']); ?>>
                                <?php _e('Una traducción necesita revisión', 'flavor-multilingual'); ?>
                            </label><br>
                            <label>
                                <input type="checkbox" name="flavor_ml_notification_settings[notify_approved]" value="1" <?php checked($settings['notify_approved']); ?>>
                                <?php _e('Una traducción es aprobada', 'flavor-multilingual'); ?>
                            </label><br>
                            <label>
                                <input type="checkbox" name="flavor_ml_notification_settings[notify_rejected]" value="1" <?php checked($settings['notify_rejected']); ?>>
                                <?php _e('Una traducción es rechazada', 'flavor-multilingual'); ?>
                            </label><br>
                            <label>
                                <input type="checkbox" name="flavor_ml_notification_settings[notify_published]" value="1" <?php checked($settings['notify_published']); ?>>
                                <?php _e('Una traducción es publicada', 'flavor-multilingual'); ?>
                            </label><br>
                            <label>
                                <input type="checkbox" name="flavor_ml_notification_settings[notify_bulk_complete]" value="1" <?php checked($settings['notify_bulk_complete']); ?>>
                                <?php _e('Completa proceso masivo', 'flavor-multilingual'); ?>
                            </label>
                        </fieldset>
                    </td>
                </tr>

                <tr>
                    <th scope="row"><?php _e('Formato de email', 'flavor-multilingual'); ?></th>
                    <td>
                        <label>
                            <input type="checkbox" name="flavor_ml_notification_settings[use_html_emails]" value="1" <?php checked($settings['use_html_emails']); ?>>
                            <?php _e('Usar emails HTML (recomendado)', 'flavor-multilingual'); ?>
                        </label>
                    </td>
                </tr>
            </table>
        </div>
        <?php
    }
}

<?php
/**
 * Visual Builder Pro - Form Handler
 *
 * Procesa envíos de formularios del VBP y envía notificaciones por email.
 *
 * @package Flavor_Chat_IA
 * @subpackage Visual_Builder_Pro
 * @since 2.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Clase para manejar formularios del Visual Builder Pro
 *
 * @since 2.0.0
 */
class Flavor_VBP_Form_Handler {

    /**
     * Nombre de la tabla de submissions
     *
     * @var string
     */
    private $table_name;

    /**
     * Instancia singleton
     *
     * @var Flavor_VBP_Form_Handler|null
     */
    private static $instance = null;

    /**
     * Obtiene la instancia singleton
     *
     * @return Flavor_VBP_Form_Handler
     */
    public static function get_instance() {
        if ( null === self::$instance ) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Constructor privado
     */
    private function __construct() {
        global $wpdb;
        $this->table_name = $wpdb->prefix . 'vbp_form_submissions';

        // Crear tabla si no existe
        add_action( 'init', array( $this, 'maybe_create_table' ) );

        // Registrar endpoint AJAX
        add_action( 'wp_ajax_vbp_submit_form', array( $this, 'ajax_submit_form' ) );
        add_action( 'wp_ajax_nopriv_vbp_submit_form', array( $this, 'ajax_submit_form' ) );
    }

    /**
     * Crear tabla de submissions si no existe
     */
    public function maybe_create_table() {
        global $wpdb;

        $installed_version = get_option( 'vbp_form_db_version', '0' );
        $current_version   = '1.0.0';

        if ( version_compare( $installed_version, $current_version, '<' ) ) {
            $charset_collate = $wpdb->get_charset_collate();

            $sql = "CREATE TABLE IF NOT EXISTS {$this->table_name} (
                id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
                form_id varchar(100) NOT NULL DEFAULT '',
                post_id bigint(20) unsigned NOT NULL DEFAULT 0,
                form_data longtext NOT NULL,
                email varchar(191) DEFAULT NULL,
                name varchar(191) DEFAULT NULL,
                ip_address varchar(45) DEFAULT NULL,
                user_agent text DEFAULT NULL,
                status varchar(20) NOT NULL DEFAULT 'new',
                created_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
                read_at datetime DEFAULT NULL,
                PRIMARY KEY (id),
                KEY form_id (form_id),
                KEY post_id (post_id),
                KEY status (status),
                KEY created_at (created_at),
                KEY email (email)
            ) $charset_collate;";

            require_once ABSPATH . 'wp-admin/includes/upgrade.php';
            dbDelta( $sql );

            update_option( 'vbp_form_db_version', $current_version );
        }
    }

    /**
     * Procesar envío de formulario vía AJAX
     */
    public function ajax_submit_form() {
        // Verificar nonce si está presente
        if ( isset( $_POST['_wpnonce'] ) && ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['_wpnonce'] ) ), 'vbp_form_submit' ) ) {
            wp_send_json_error(
                array( 'message' => __( 'Error de seguridad. Recarga la página e inténtalo de nuevo.', 'flavor-chat-ia' ) ),
                403
            );
        }

        // Rate limiting básico
        $ip = $this->get_client_ip();
        $rate_limit_key = 'vbp_form_rate_' . md5( $ip );
        $rate_count = get_transient( $rate_limit_key );

        if ( $rate_count !== false && $rate_count >= 5 ) {
            wp_send_json_error(
                array( 'message' => __( 'Has enviado demasiados formularios. Por favor, espera unos minutos.', 'flavor-chat-ia' ) ),
                429
            );
        }

        // Incrementar contador de rate limit
        set_transient( $rate_limit_key, ( $rate_count ? $rate_count + 1 : 1 ), 5 * MINUTE_IN_SECONDS );

        // Obtener datos del formulario
        $form_id = isset( $_POST['form_id'] ) ? sanitize_text_field( wp_unslash( $_POST['form_id'] ) ) : '';
        $post_id = isset( $_POST['post_id'] ) ? absint( $_POST['post_id'] ) : 0;
        $name    = isset( $_POST['name'] ) ? sanitize_text_field( wp_unslash( $_POST['name'] ) ) : '';
        $email   = isset( $_POST['email'] ) ? sanitize_email( wp_unslash( $_POST['email'] ) ) : '';
        $message = isset( $_POST['message'] ) ? sanitize_textarea_field( wp_unslash( $_POST['message'] ) ) : '';

        // Validaciones
        $errores = array();

        if ( empty( $name ) ) {
            $errores['name'] = __( 'El nombre es obligatorio.', 'flavor-chat-ia' );
        }

        if ( empty( $email ) ) {
            $errores['email'] = __( 'El email es obligatorio.', 'flavor-chat-ia' );
        } elseif ( ! is_email( $email ) ) {
            $errores['email'] = __( 'El email no es válido.', 'flavor-chat-ia' );
        }

        if ( empty( $message ) ) {
            $errores['message'] = __( 'El mensaje es obligatorio.', 'flavor-chat-ia' );
        }

        // Verificar honeypot (campo oculto para bots)
        if ( ! empty( $_POST['website_url'] ) ) {
            // Probable bot, simular éxito pero no hacer nada
            wp_send_json_success(
                array( 'message' => __( '¡Mensaje enviado correctamente!', 'flavor-chat-ia' ) )
            );
        }

        if ( ! empty( $errores ) ) {
            wp_send_json_error(
                array(
                    'message' => __( 'Por favor, corrige los errores en el formulario.', 'flavor-chat-ia' ),
                    'errors'  => $errores,
                ),
                400
            );
        }

        // Preparar datos para guardar
        $form_data = array(
            'name'    => $name,
            'email'   => $email,
            'message' => $message,
        );

        // Campos adicionales personalizados
        $campos_excluidos = array( 'form_id', 'post_id', 'name', 'email', 'message', 'action', '_wpnonce', 'website_url' );
        $post_data = wp_unslash( $_POST );
        foreach ( $post_data as $key => $value ) {
            if ( ! in_array( $key, $campos_excluidos, true ) ) {
                $sanitized_key = sanitize_key( $key );
                // Manejar arrays anidados de forma segura
                if ( is_array( $value ) ) {
                    $form_data[ $sanitized_key ] = array_map( 'sanitize_text_field', $value );
                } else {
                    $form_data[ $sanitized_key ] = sanitize_text_field( $value );
                }
            }
        }

        // Guardar en base de datos
        $submission_id = $this->save_submission( $form_id, $post_id, $form_data, $email, $name, $ip );

        if ( ! $submission_id ) {
            wp_send_json_error(
                array( 'message' => __( 'Error al guardar el mensaje. Por favor, inténtalo de nuevo.', 'flavor-chat-ia' ) ),
                500
            );
        }

        // Enviar email de notificación
        $email_sent = $this->send_notification_email( $form_data, $post_id );

        // Disparar action para integraciones
        do_action( 'vbp_form_submitted', $submission_id, $form_data, $post_id );

        wp_send_json_success(
            array(
                'message'       => __( '¡Mensaje enviado correctamente! Te responderemos pronto.', 'flavor-chat-ia' ),
                'submission_id' => $submission_id,
            )
        );
    }

    /**
     * Guardar submission en base de datos
     *
     * @param string $form_id   ID del formulario.
     * @param int    $post_id   ID del post.
     * @param array  $form_data Datos del formulario.
     * @param string $email     Email del remitente.
     * @param string $name      Nombre del remitente.
     * @param string $ip        IP del remitente.
     * @return int|false ID de la submission o false en error.
     */
    private function save_submission( $form_id, $post_id, $form_data, $email, $name, $ip ) {
        global $wpdb;

        $result = $wpdb->insert(
            $this->table_name,
            array(
                'form_id'    => $form_id,
                'post_id'    => $post_id,
                'form_data'  => wp_json_encode( $form_data ),
                'email'      => $email,
                'name'       => $name,
                'ip_address' => $ip,
                'user_agent' => isset( $_SERVER['HTTP_USER_AGENT'] ) ? sanitize_text_field( wp_unslash( $_SERVER['HTTP_USER_AGENT'] ) ) : '',
                'status'     => 'new',
                'created_at' => current_time( 'mysql' ),
            ),
            array( '%s', '%d', '%s', '%s', '%s', '%s', '%s', '%s', '%s' )
        );

        if ( false === $result ) {
            return false;
        }

        return $wpdb->insert_id;
    }

    /**
     * Enviar email de notificación al administrador
     *
     * @param array $form_data Datos del formulario.
     * @param int   $post_id   ID del post donde está el formulario.
     * @return bool Si se envió el email.
     */
    private function send_notification_email( $form_data, $post_id ) {
        $to = get_option( 'admin_email' );

        // Obtener título del post si existe
        $post_title = '';
        if ( $post_id ) {
            $post = get_post( $post_id );
            if ( $post ) {
                $post_title = $post->post_title;
            }
        }

        $subject = sprintf(
            /* translators: %s: Site name */
            __( '[%s] Nuevo mensaje de contacto', 'flavor-chat-ia' ),
            get_bloginfo( 'name' )
        );

        // Construir mensaje HTML
        $message_lines = array();
        $message_lines[] = '<html><body style="font-family: -apple-system, BlinkMacSystemFont, \'Segoe UI\', Roboto, sans-serif; line-height: 1.6;">';
        $message_lines[] = '<div style="max-width: 600px; margin: 0 auto; padding: 20px;">';
        $message_lines[] = '<h2 style="color: #333; margin-bottom: 20px;">' . esc_html__( 'Nuevo mensaje de contacto', 'flavor-chat-ia' ) . '</h2>';

        if ( $post_title ) {
            $message_lines[] = '<p style="color: #666; margin-bottom: 20px;">' . sprintf(
                /* translators: %s: Page title */
                esc_html__( 'Desde la página: %s', 'flavor-chat-ia' ),
                '<strong>' . esc_html( $post_title ) . '</strong>'
            ) . '</p>';
        }

        $message_lines[] = '<table style="width: 100%; border-collapse: collapse;">';

        foreach ( $form_data as $key => $value ) {
            $label = ucfirst( str_replace( '_', ' ', $key ) );
            $message_lines[] = '<tr style="border-bottom: 1px solid #eee;">';
            $message_lines[] = '<td style="padding: 12px 0; width: 120px; vertical-align: top; color: #666;"><strong>' . esc_html( $label ) . ':</strong></td>';
            $message_lines[] = '<td style="padding: 12px 0; color: #333;">' . nl2br( esc_html( $value ) ) . '</td>';
            $message_lines[] = '</tr>';
        }

        $message_lines[] = '</table>';
        $message_lines[] = '<hr style="border: none; border-top: 1px solid #eee; margin: 24px 0;">';
        $message_lines[] = '<p style="font-size: 12px; color: #999;">' . sprintf(
            /* translators: %s: Date/time */
            esc_html__( 'Enviado el %s', 'flavor-chat-ia' ),
            wp_date( get_option( 'date_format' ) . ' ' . get_option( 'time_format' ) )
        ) . '</p>';
        $message_lines[] = '</div></body></html>';

        $message = implode( "\n", $message_lines );

        // Headers
        $headers = array(
            'Content-Type: text/html; charset=UTF-8',
        );

        // Reply-To con el email del remitente si existe
        if ( ! empty( $form_data['email'] ) && is_email( $form_data['email'] ) ) {
            $reply_to_name = ! empty( $form_data['name'] ) ? $form_data['name'] : '';
            $headers[] = 'Reply-To: ' . ( $reply_to_name ? "$reply_to_name <{$form_data['email']}>" : $form_data['email'] );
        }

        return wp_mail( $to, $subject, $message, $headers );
    }

    /**
     * Obtener IP del cliente
     *
     * @return string IP del cliente.
     */
    private function get_client_ip() {
        $ip = '';

        if ( ! empty( $_SERVER['HTTP_CLIENT_IP'] ) ) {
            $ip = sanitize_text_field( wp_unslash( $_SERVER['HTTP_CLIENT_IP'] ) );
        } elseif ( ! empty( $_SERVER['HTTP_X_FORWARDED_FOR'] ) ) {
            $ips = explode( ',', sanitize_text_field( wp_unslash( $_SERVER['HTTP_X_FORWARDED_FOR'] ) ) );
            $ip  = trim( $ips[0] );
        } elseif ( ! empty( $_SERVER['REMOTE_ADDR'] ) ) {
            $ip = sanitize_text_field( wp_unslash( $_SERVER['REMOTE_ADDR'] ) );
        }

        // Validar IP
        return filter_var( $ip, FILTER_VALIDATE_IP ) ? $ip : '';
    }

    /**
     * Obtener submissions de un formulario
     *
     * @param array $args Argumentos de consulta.
     * @return array Lista de submissions.
     */
    public function get_submissions( $args = array() ) {
        global $wpdb;

        $defaults = array(
            'form_id'  => '',
            'post_id'  => 0,
            'status'   => '',
            'per_page' => 20,
            'page'     => 1,
            'orderby'  => 'created_at',
            'order'    => 'DESC',
        );

        $args = wp_parse_args( $args, $defaults );

        $where = array( '1=1' );
        $values = array();

        if ( ! empty( $args['form_id'] ) ) {
            $where[] = 'form_id = %s';
            $values[] = $args['form_id'];
        }

        if ( $args['post_id'] > 0 ) {
            $where[] = 'post_id = %d';
            $values[] = $args['post_id'];
        }

        if ( ! empty( $args['status'] ) ) {
            $where[] = 'status = %s';
            $values[] = $args['status'];
        }

        $offset = ( $args['page'] - 1 ) * $args['per_page'];
        $orderby = in_array( $args['orderby'], array( 'id', 'created_at', 'email', 'name' ), true ) ? $args['orderby'] : 'created_at';
        $order = strtoupper( $args['order'] ) === 'ASC' ? 'ASC' : 'DESC';

        $sql = $wpdb->prepare(
            "SELECT * FROM {$this->table_name} WHERE " . implode( ' AND ', $where ) . " ORDER BY {$orderby} {$order} LIMIT %d OFFSET %d",
            array_merge( $values, array( $args['per_page'], $offset ) )
        );

        return $wpdb->get_results( $sql );
    }

    /**
     * Contar submissions
     *
     * @param array $args Argumentos de consulta.
     * @return int Total de submissions.
     */
    public function count_submissions( $args = array() ) {
        global $wpdb;

        $where = array( '1=1' );
        $values = array();

        if ( ! empty( $args['form_id'] ) ) {
            $where[] = 'form_id = %s';
            $values[] = $args['form_id'];
        }

        if ( ! empty( $args['status'] ) ) {
            $where[] = 'status = %s';
            $values[] = $args['status'];
        }

        if ( empty( $values ) ) {
            $sql = "SELECT COUNT(*) FROM {$this->table_name} WHERE " . implode( ' AND ', $where );
        } else {
            $sql = $wpdb->prepare(
                "SELECT COUNT(*) FROM {$this->table_name} WHERE " . implode( ' AND ', $where ),
                $values
            );
        }

        return (int) $wpdb->get_var( $sql );
    }

    /**
     * Marcar submission como leída
     *
     * @param int $submission_id ID de la submission.
     * @return bool Éxito.
     */
    public function mark_as_read( $submission_id ) {
        global $wpdb;

        return $wpdb->update(
            $this->table_name,
            array(
                'status'  => 'read',
                'read_at' => current_time( 'mysql' ),
            ),
            array( 'id' => $submission_id ),
            array( '%s', '%s' ),
            array( '%d' )
        );
    }

    /**
     * Eliminar submission
     *
     * @param int $submission_id ID de la submission.
     * @return bool Éxito.
     */
    public function delete_submission( $submission_id ) {
        global $wpdb;

        return $wpdb->delete(
            $this->table_name,
            array( 'id' => $submission_id ),
            array( '%d' )
        );
    }
}

// Inicializar
Flavor_VBP_Form_Handler::get_instance();

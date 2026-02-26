<?php
/**
 * Gestor de Newsletter - Envio de campanas y tracking
 *
 * @package FlavorChatIA
 * @subpackage Newsletter
 * @since 3.1.0
 */

if (!defined('ABSPATH')) {
    exit;
}

class Flavor_Newsletter_Manager {

    private static $instancia = null;
    private $tabla_campanas;
    private $tabla_tracking;
    private $tabla_suscriptores;
    private $tabla_cola_envio;

    const TAMANO_BATCH = 50;
    const CRON_HOOK = 'flavor_newsletter_procesar_batch';
    const PIXEL_GIF_BASE64 = 'R0lGODlhAQABAIAAAAAAAP///yH5BAEAAAAALAAAAAABAAEAAAIBRAA7';

    public static function get_instance() {
        if (self::$instancia === null) {
            self::$instancia = new self();
        }
        return self::$instancia;
    }

    private function __construct() {
        global $wpdb;
        $this->tabla_campanas     = $wpdb->prefix . 'flavor_newsletter_campaigns';
        $this->tabla_tracking     = $wpdb->prefix . 'flavor_newsletter_tracking';
        $this->tabla_suscriptores = $wpdb->prefix . 'flavor_newsletter_subscribers';
        $this->tabla_cola_envio   = $wpdb->prefix . 'flavor_newsletter_queue';
        $this->registrar_hooks();
    }

    private function registrar_hooks() {
        add_action(self::CRON_HOOK, [$this, 'procesar_cola_envio']);
        add_action('wp_ajax_flavor_newsletter_send', [$this, 'ajax_enviar_campana']);
        add_action('wp_ajax_flavor_newsletter_save', [$this, 'ajax_guardar_campana']);
        add_action('wp_ajax_flavor_newsletter_delete', [$this, 'ajax_eliminar_campana']);
        add_action('wp_ajax_flavor_newsletter_stats', [$this, 'ajax_obtener_estadisticas']);
        add_action('wp_ajax_nopriv_flavor_newsletter_track_open', [$this, 'rastrear_apertura']);
        add_action('wp_ajax_flavor_newsletter_track_open', [$this, 'rastrear_apertura']);
        add_action('wp_ajax_nopriv_flavor_newsletter_track_click', [$this, 'rastrear_click']);
        add_action('wp_ajax_flavor_newsletter_track_click', [$this, 'rastrear_click']);
        add_action('wp_ajax_nopriv_flavor_newsletter_baja', [$this, 'procesar_baja_suscripcion']);
        add_action('wp_ajax_flavor_newsletter_baja', [$this, 'procesar_baja_suscripcion']);
    }

    public static function programar_cron() {
        if (!wp_next_scheduled(self::CRON_HOOK)) {
            wp_schedule_event(time(), 'five_minutes', self::CRON_HOOK);
        }
    }

    public static function desprogramar_cron() {
        wp_clear_scheduled_hook(self::CRON_HOOK);
    }

    public static function instalar_tablas() {
        global $wpdb;
        $charset_collate = $wpdb->get_charset_collate();
        require_once ABSPATH . 'wp-admin/includes/upgrade.php';

        $tabla_campanas = $wpdb->prefix . 'flavor_newsletter_campaigns';
        dbDelta("CREATE TABLE IF NOT EXISTS $tabla_campanas (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            asunto varchar(255) NOT NULL,
            contenido_html longtext NOT NULL,
            estado varchar(30) NOT NULL DEFAULT 'borrador',
            total_destinatarios int(11) NOT NULL DEFAULT 0,
            total_enviados int(11) NOT NULL DEFAULT 0,
            total_abiertos int(11) NOT NULL DEFAULT 0,
            total_clicks int(11) NOT NULL DEFAULT 0,
            fecha_programada datetime DEFAULT NULL,
            fecha_envio datetime DEFAULT NULL,
            created_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY estado (estado)
        ) $charset_collate;");

        $tabla_tracking = $wpdb->prefix . 'flavor_newsletter_tracking';
        dbDelta("CREATE TABLE IF NOT EXISTS $tabla_tracking (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            campana_id bigint(20) unsigned NOT NULL,
            email varchar(200) NOT NULL,
            tipo varchar(20) NOT NULL,
            url_click varchar(500) DEFAULT NULL,
            ip_address varchar(45) DEFAULT NULL,
            user_agent text DEFAULT NULL,
            created_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY campana_id (campana_id),
            KEY email (email),
            KEY tipo (tipo)
        ) $charset_collate;");

        $tabla_cola = $wpdb->prefix . 'flavor_newsletter_queue';
        dbDelta("CREATE TABLE IF NOT EXISTS $tabla_cola (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            campana_id bigint(20) unsigned NOT NULL,
            email varchar(200) NOT NULL,
            estado varchar(20) NOT NULL DEFAULT 'pendiente',
            intentos tinyint(3) NOT NULL DEFAULT 0,
            created_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
            processed_at datetime DEFAULT NULL,
            PRIMARY KEY (id),
            KEY campana_id (campana_id),
            KEY estado (estado)
        ) $charset_collate;");

        update_option('flavor_newsletter_db_version', '1.0.0');
    }

    public function guardar_campana($datos_campana) {
        global $wpdb;
        $asunto_campana = sanitize_text_field($datos_campana['asunto'] ?? '');
        $contenido_html = wp_kses_post($datos_campana['contenido_html'] ?? '');
        $identificador_campana = intval($datos_campana['id'] ?? 0);

        if (empty($asunto_campana)) {
            return new WP_Error('asunto_vacio', __('El asunto es obligatorio.', 'flavor-chat-ia'));
        }

        $datos_a_guardar = ['asunto' => $asunto_campana, 'contenido_html' => $contenido_html, 'updated_at' => current_time('mysql')];

        if ($identificador_campana > 0) {
            $wpdb->update($this->tabla_campanas, $datos_a_guardar, ['id' => $identificador_campana], ['%s', '%s', '%s'], ['%d']);
            return $identificador_campana;
        }

        $datos_a_guardar['estado'] = 'borrador';
        $datos_a_guardar['created_at'] = current_time('mysql');
        $wpdb->insert($this->tabla_campanas, $datos_a_guardar, ['%s', '%s', '%s', '%s', '%s']);
        return $wpdb->insert_id ?: new WP_Error('insert_error', __('Error al crear campana.', 'flavor-chat-ia'));
    }

    public function obtener_campana($identificador_campana) {
        global $wpdb;
        return $wpdb->get_row($wpdb->prepare("SELECT * FROM {$this->tabla_campanas} WHERE id = %d", $identificador_campana));
    }

    public function listar_campanas($estado_filtro = '', $limite_resultados = 20, $offset_resultados = 0) {
        global $wpdb;
        $clausula_where = '';
        $parametros_query = [];
        if (!empty($estado_filtro)) {
            $clausula_where = 'WHERE estado = %s';
            $parametros_query[] = $estado_filtro;
        }
        $parametros_query[] = $limite_resultados;
        $parametros_query[] = $offset_resultados;
        return $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM {$this->tabla_campanas} $clausula_where ORDER BY created_at DESC LIMIT %d OFFSET %d",
            ...$parametros_query
        ));
    }

    public function eliminar_campana($identificador_campana) {
        global $wpdb;
        $wpdb->delete($this->tabla_cola_envio, ['campana_id' => $identificador_campana], ['%d']);
        $wpdb->delete($this->tabla_tracking, ['campana_id' => $identificador_campana], ['%d']);
        return $wpdb->delete($this->tabla_campanas, ['id' => $identificador_campana], ['%d']);
    }

    public function iniciar_envio_campana($identificador_campana) {
        global $wpdb;
        $campana_seleccionada = $this->obtener_campana($identificador_campana);
        if (!$campana_seleccionada) {
            return new WP_Error('campana_no_encontrada', __('Campana no encontrada.', 'flavor-chat-ia'));
        }
        if (in_array($campana_seleccionada->estado, ['enviando', 'enviada'], true)) {
            return new WP_Error('campana_ya_enviada', __('Esta campana ya se esta enviando o fue enviada.', 'flavor-chat-ia'));
        }

        $lista_suscriptores = $this->obtener_suscriptores_activos();
        if (empty($lista_suscriptores)) {
            return new WP_Error('sin_suscriptores', __('No hay suscriptores activos.', 'flavor-chat-ia'));
        }

        foreach ($lista_suscriptores as $suscriptor_actual) {
            $wpdb->insert($this->tabla_cola_envio, [
                'campana_id' => $identificador_campana,
                'email'      => $suscriptor_actual->email,
                'estado'     => 'pendiente',
                'created_at' => current_time('mysql'),
            ], ['%d', '%s', '%s', '%s']);
        }

        $wpdb->update($this->tabla_campanas, [
            'estado' => 'enviando', 'total_destinatarios' => count($lista_suscriptores), 'fecha_envio' => current_time('mysql'),
        ], ['id' => $identificador_campana], ['%s', '%d', '%s'], ['%d']);

        self::programar_cron();
        return true;
    }

    public function procesar_cola_envio() {
        global $wpdb;
        $emails_pendientes = $wpdb->get_results($wpdb->prepare(
            "SELECT q.*, c.asunto, c.contenido_html FROM {$this->tabla_cola_envio} q
             JOIN {$this->tabla_campanas} c ON q.campana_id = c.id
             WHERE q.estado = 'pendiente' AND q.intentos < 3 ORDER BY q.id ASC LIMIT %d",
            self::TAMANO_BATCH
        ));

        if (empty($emails_pendientes)) { return; }

        foreach ($emails_pendientes as $email_en_cola) {
            $contenido_personalizado = $this->personalizar_contenido($email_en_cola->contenido_html, $email_en_cola->email, $email_en_cola->campana_id);
            $resultado_envio = wp_mail($email_en_cola->email, $email_en_cola->asunto, $contenido_personalizado, ['Content-Type: text/html; charset=UTF-8']);

            if ($resultado_envio) {
                $wpdb->update($this->tabla_cola_envio, ['estado' => 'enviado', 'processed_at' => current_time('mysql')], ['id' => $email_en_cola->id], ['%s', '%s'], ['%d']);
                $wpdb->query($wpdb->prepare("UPDATE {$this->tabla_campanas} SET total_enviados = total_enviados + 1 WHERE id = %d", $email_en_cola->campana_id));
            } else {
                $wpdb->update($this->tabla_cola_envio, ['intentos' => $email_en_cola->intentos + 1], ['id' => $email_en_cola->id], ['%d'], ['%d']);
            }
        }

        $total_pendientes = (int) $wpdb->get_var("SELECT COUNT(*) FROM {$this->tabla_cola_envio} WHERE estado = 'pendiente'");
        if ($total_pendientes === 0) {
            $wpdb->query("UPDATE {$this->tabla_campanas} SET estado = 'enviada' WHERE estado = 'enviando'");
            self::desprogramar_cron();
        }
    }

    private function personalizar_contenido($contenido_html_original, $direccion_email, $identificador_campana) {
        $contenido_procesado = $contenido_html_original;

        $contenido_procesado = preg_replace_callback('/<a\s+([^>]*?)href=["\x27]([^"\x27]+?)["\x27]([^>]*?)>/i',
            function ($coincidencias) use ($direccion_email, $identificador_campana) {
                $url_original = $coincidencias[2];
                if (strpos($url_original, '#') === 0 || strpos($url_original, 'unsubscribe') !== false) {
                    return $coincidencias[0];
                }
                $url_tracking = add_query_arg(['action' => 'flavor_newsletter_track_click', 'c' => $identificador_campana, 'e' => base64_encode($direccion_email), 'u' => urlencode($url_original)], admin_url('admin-ajax.php'));
                return '<a ' . $coincidencias[1] . 'href="' . esc_url($url_tracking) . '"' . $coincidencias[3] . '>';
            }, $contenido_procesado);

        $url_pixel = add_query_arg(['action' => 'flavor_newsletter_track_open', 'c' => $identificador_campana, 'e' => base64_encode($direccion_email)], admin_url('admin-ajax.php'));
        $pixel_html = '<img src="' . esc_url($url_pixel) . '" width="1" height="1" alt="" style="display:none;" />';
        $contenido_procesado = strpos($contenido_procesado, '</body>') !== false
            ? str_replace('</body>', $pixel_html . '</body>', $contenido_procesado)
            : $contenido_procesado . $pixel_html;

        $url_baja = add_query_arg(['action' => 'flavor_newsletter_baja', 'e' => base64_encode($direccion_email)], admin_url('admin-ajax.php'));
        $contenido_procesado = str_replace('{{unsubscribe_url}}', esc_url($url_baja), $contenido_procesado);

        return $contenido_procesado;
    }

    public function rastrear_apertura() {
        $identificador_campana = intval($_GET['c'] ?? 0);
        $direccion_email = $this->decodificar_email_seguro($_GET['e'] ?? '');

        if ($identificador_campana && $direccion_email) {
            global $wpdb;
            $wpdb->insert($this->tabla_tracking, [
                'campana_id' => $identificador_campana, 'email' => $direccion_email, 'tipo' => 'apertura',
                'ip_address' => $this->obtener_ip_real(), 'user_agent' => sanitize_text_field($_SERVER['HTTP_USER_AGENT'] ?? ''), 'created_at' => current_time('mysql'),
            ], ['%d', '%s', '%s', '%s', '%s', '%s']);

            $aperturas_previas = $wpdb->get_var($wpdb->prepare(
                "SELECT COUNT(*) FROM {$this->tabla_tracking} WHERE campana_id = %d AND email = %s AND tipo = 'apertura'", $identificador_campana, $direccion_email
            ));
            if ($aperturas_previas <= 1) {
                $wpdb->query($wpdb->prepare("UPDATE {$this->tabla_campanas} SET total_abiertos = total_abiertos + 1 WHERE id = %d", $identificador_campana));
            }
        }

        header('Content-Type: image/gif');
        header('Cache-Control: no-cache, no-store, must-revalidate');
        // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
        echo base64_decode(self::PIXEL_GIF_BASE64);
        exit;
    }

    public function rastrear_click() {
        $identificador_campana = intval($_GET['c'] ?? 0);
        $direccion_email = $this->decodificar_email_seguro($_GET['e'] ?? '');
        $url_destino = esc_url_raw(urldecode($_GET['u'] ?? ''));

        if ($identificador_campana && $direccion_email && $url_destino) {
            global $wpdb;
            $wpdb->insert($this->tabla_tracking, [
                'campana_id' => $identificador_campana, 'email' => $direccion_email, 'tipo' => 'click', 'url_click' => $url_destino,
                'ip_address' => $this->obtener_ip_real(), 'user_agent' => sanitize_text_field($_SERVER['HTTP_USER_AGENT'] ?? ''), 'created_at' => current_time('mysql'),
            ], ['%d', '%s', '%s', '%s', '%s', '%s', '%s']);
            $wpdb->query($wpdb->prepare("UPDATE {$this->tabla_campanas} SET total_clicks = total_clicks + 1 WHERE id = %d", $identificador_campana));
        }

        wp_redirect(!empty($url_destino) ? $url_destino : home_url());
        exit;
    }

    public function procesar_baja_suscripcion() {
        $direccion_email = $this->decodificar_email_seguro($_GET['e'] ?? $_POST['e'] ?? '');
        if (empty($direccion_email) || !is_email($direccion_email)) {
            wp_die(__('Enlace de baja no valido.', 'flavor-chat-ia'));
        }

        global $wpdb;
        $tabla_existente = $wpdb->get_var($wpdb->prepare("SHOW TABLES LIKE %s", $wpdb->esc_like($this->tabla_suscriptores)));
        if ($tabla_existente) {
            $wpdb->update($this->tabla_suscriptores, ['estado' => 'baja'], ['email' => $direccion_email], ['%s'], ['%s']);
        }

        $tabla_form_suscripciones = $wpdb->prefix . 'flavor_form_subscriptions';
        $tabla_form_existe = $wpdb->get_var($wpdb->prepare("SHOW TABLES LIKE %s", $wpdb->esc_like($tabla_form_suscripciones)));
        if ($tabla_form_existe) {
            $wpdb->update($tabla_form_suscripciones, ['estado' => 'baja'], ['email' => $direccion_email], ['%s'], ['%s']);
        }

        wp_die(
            '<h2>' . esc_html__('Te has dado de baja correctamente', 'flavor-chat-ia') . '</h2>' .
            '<p>' . esc_html__('Ya no recibiras mas emails de nuestra newsletter.', 'flavor-chat-ia') . '</p>',
            esc_html__('Baja de newsletter', 'flavor-chat-ia'),
            ['response' => 200]
        );
    }

    private function obtener_suscriptores_activos() {
        global $wpdb;
        $tabla_existente = $wpdb->get_var($wpdb->prepare("SHOW TABLES LIKE %s", $wpdb->esc_like($this->tabla_suscriptores)));
        if ($tabla_existente) {
            return $wpdb->get_results("SELECT email FROM {$this->tabla_suscriptores} WHERE estado = 'activo'");
        }
        $tabla_form = $wpdb->prefix . 'flavor_form_subscriptions';
        $tabla_form_existe = $wpdb->get_var($wpdb->prepare("SHOW TABLES LIKE %s", $wpdb->esc_like($tabla_form)));
        if ($tabla_form_existe) {
            return $wpdb->get_results("SELECT email FROM $tabla_form WHERE estado = 'activo' GROUP BY email");
        }
        return [];
    }

    public function obtener_estadisticas_campana($identificador_campana) {
        global $wpdb;
        $campana_datos = $this->obtener_campana($identificador_campana);
        if (!$campana_datos) { return null; }

        $aperturas_unicas = (int) $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(DISTINCT email) FROM {$this->tabla_tracking} WHERE campana_id = %d AND tipo = 'apertura'", $identificador_campana));
        $clicks_unicos = (int) $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(DISTINCT email) FROM {$this->tabla_tracking} WHERE campana_id = %d AND tipo = 'click'", $identificador_campana));

        $tasa_apertura = $campana_datos->total_enviados > 0 ? round(($aperturas_unicas / $campana_datos->total_enviados) * 100, 1) : 0;
        $tasa_clicks = $campana_datos->total_enviados > 0 ? round(($clicks_unicos / $campana_datos->total_enviados) * 100, 1) : 0;

        return ['campana' => $campana_datos, 'aperturas_unicas' => $aperturas_unicas, 'clicks_unicos' => $clicks_unicos, 'tasa_apertura' => $tasa_apertura, 'tasa_clicks' => $tasa_clicks];
    }

    public function ajax_guardar_campana() {
        check_ajax_referer('flavor_newsletter_nonce', 'nonce');
        if (!current_user_can('manage_options')) { wp_send_json_error(['message' => __('Permisos insuficientes.', 'flavor-chat-ia')]); }
        $resultado = $this->guardar_campana(['id' => intval($_POST['id'] ?? 0), 'asunto' => $_POST['asunto'] ?? '', 'contenido_html' => $_POST['contenido_html'] ?? '']);
        is_wp_error($resultado) ? wp_send_json_error(['message' => $resultado->get_error_message()]) : wp_send_json_success(['message' => __('Campana guardada.', 'flavor-chat-ia'), 'id' => $resultado]);
    }

    public function ajax_enviar_campana() {
        check_ajax_referer('flavor_newsletter_nonce', 'nonce');
        if (!current_user_can('manage_options')) { wp_send_json_error(['message' => __('Permisos insuficientes.', 'flavor-chat-ia')]); }
        $resultado = $this->iniciar_envio_campana(intval($_POST['campana_id'] ?? 0));
        is_wp_error($resultado) ? wp_send_json_error(['message' => $resultado->get_error_message()]) : wp_send_json_success(['message' => __('Envio de campana iniciado.', 'flavor-chat-ia')]);
    }

    public function ajax_eliminar_campana() {
        check_ajax_referer('flavor_newsletter_nonce', 'nonce');
        if (!current_user_can('manage_options')) { wp_send_json_error(['message' => __('Permisos insuficientes.', 'flavor-chat-ia')]); }
        $this->eliminar_campana(intval($_POST['campana_id'] ?? 0));
        wp_send_json_success(['message' => __('Campana eliminada.', 'flavor-chat-ia')]);
    }

    public function ajax_obtener_estadisticas() {
        check_ajax_referer('flavor_newsletter_nonce', 'nonce');
        if (!current_user_can('manage_options')) { wp_send_json_error(['message' => __('Permisos insuficientes.', 'flavor-chat-ia')]); }
        $estadisticas = $this->obtener_estadisticas_campana(intval($_GET['campana_id'] ?? $_POST['campana_id'] ?? 0));
        $estadisticas ? wp_send_json_success($estadisticas) : wp_send_json_error(['message' => __('Campana no encontrada.', 'flavor-chat-ia')]);
    }

    private function obtener_ip_real() {
        foreach (['HTTP_CF_CONNECTING_IP', 'HTTP_X_FORWARDED_FOR', 'HTTP_X_REAL_IP', 'REMOTE_ADDR'] as $clave_cabecera) {
            if (!empty($_SERVER[$clave_cabecera])) {
                $direccion_ip = sanitize_text_field($_SERVER[$clave_cabecera]);
                return strpos($direccion_ip, ',') !== false ? trim(explode(',', $direccion_ip)[0]) : $direccion_ip;
            }
        }
        return '0.0.0.0';
    }

    /**
     * Decodifica un email desde base64 de forma segura
     *
     * @param string $encoded Email codificado en base64
     * @return string Email decodificado y sanitizado, o vacío si es inválido
     */
    private function decodificar_email_seguro($encoded) {
        if (empty($encoded)) {
            return '';
        }

        // Limpiar caracteres de URL encoding y espacios
        $encoded = str_replace([' ', '%20'], ['+', '+'], $encoded);

        // Validar que solo contiene caracteres base64 válidos
        if (!preg_match('/^[A-Za-z0-9+\/=]+$/', $encoded)) {
            return '';
        }

        $decoded = base64_decode($encoded, true);
        if ($decoded === false) {
            return '';
        }

        // Sanitizar y validar como email
        $email = sanitize_email($decoded);
        return is_email($email) ? $email : '';
    }
}

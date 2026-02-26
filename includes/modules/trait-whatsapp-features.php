<?php
/**
 * Trait con funcionalidades tipo WhatsApp para módulos de chat
 *
 * Añade:
 * - Doble check (✓ enviado, ✓✓ entregado, ✓✓ azul leído)
 * - Link previews con metadata OG
 * - Mensajes temporales (que desaparecen)
 * - Indicadores de escritura mejorados
 * - Read receipts grupales
 *
 * @package Flavor_Chat_IA
 * @subpackage Modules
 * @since 1.5.0
 */

trait Flavor_WhatsApp_Features {

    /**
     * Estados de entrega de mensaje
     */
    const DELIVERY_PENDING = 'pending';      // ⏳ Pendiente (guardando)
    const DELIVERY_SENT = 'sent';            // ✓ Enviado al servidor
    const DELIVERY_DELIVERED = 'delivered';  // ✓✓ Entregado al dispositivo
    const DELIVERY_READ = 'read';            // ✓✓ Leído (azul)

    /**
     * Duración por defecto de mensajes temporales (24 horas)
     */
    const MENSAJE_TEMPORAL_DURACION = 86400;

    /**
     * Cache de link previews
     */
    private static $link_preview_cache = [];

    /**
     * Inicializar funcionalidades WhatsApp
     */
    protected function init_whatsapp_features() {
        // AJAX handlers adicionales
        add_action('wp_ajax_flavor_chat_delivery_status', [$this, 'ajax_update_delivery_status']);
        add_action('wp_ajax_flavor_chat_link_preview', [$this, 'ajax_get_link_preview']);
        add_action('wp_ajax_flavor_chat_set_temporal', [$this, 'ajax_set_temporal_mode']);

        // Filtro para procesar mensajes
        add_filter('flavor_chat_mensaje_pre_save', [$this, 'process_message_features'], 10, 2);
        add_filter('flavor_chat_mensaje_output', [$this, 'enhance_message_output'], 10, 2);

        // Cron para limpiar mensajes temporales
        add_action('flavor_limpiar_mensajes_temporales', [$this, 'limpiar_mensajes_temporales']);
        if (!wp_next_scheduled('flavor_limpiar_mensajes_temporales')) {
            wp_schedule_event(time(), 'hourly', 'flavor_limpiar_mensajes_temporales');
        }

        // Assets adicionales
        add_action('wp_enqueue_scripts', [$this, 'enqueue_whatsapp_assets']);
    }

    /**
     * Enqueue assets de WhatsApp features
     */
    public function enqueue_whatsapp_assets() {
        if (!is_user_logged_in()) {
            return;
        }

        wp_add_inline_style('flavor-chat-interno', $this->get_whatsapp_css());
        wp_add_inline_script('flavor-chat-interno', $this->get_whatsapp_js(), 'after');
    }

    // =========================================================================
    // DOBLE CHECK - ESTADOS DE ENTREGA
    // =========================================================================

    /**
     * Actualizar estado de entrega de un mensaje
     */
    public function update_delivery_status($mensaje_id, $status, $usuario_id = null) {
        global $wpdb;

        $usuario_id = $usuario_id ?: get_current_user_id();
        $tabla = $this->get_mensajes_table();

        // Validar estado
        $estados_validos = [self::DELIVERY_SENT, self::DELIVERY_DELIVERED, self::DELIVERY_READ];
        if (!in_array($status, $estados_validos)) {
            return false;
        }

        // Actualizar campo de delivery_status (asumiendo que existe)
        $result = $wpdb->query($wpdb->prepare(
            "UPDATE {$tabla}
             SET delivery_status = %s,
                 delivery_timestamp = %s
             WHERE id = %d",
            $status,
            current_time('mysql'),
            $mensaje_id
        ));

        // Si es "leído", también actualizar el campo leido existente
        if ($status === self::DELIVERY_READ) {
            $wpdb->update(
                $tabla,
                ['leido' => 1, 'fecha_lectura' => current_time('mysql')],
                ['id' => $mensaje_id]
            );
        }

        // Notificar por websocket/polling si está disponible
        do_action('flavor_chat_delivery_updated', $mensaje_id, $status, $usuario_id);

        return $result !== false;
    }

    /**
     * Marcar mensajes como entregados cuando usuario abre conversación
     */
    public function mark_messages_delivered($conversacion_id, $usuario_id = null) {
        global $wpdb;

        $usuario_id = $usuario_id ?: get_current_user_id();
        $tabla = $this->get_mensajes_table();

        // Marcar como entregados los mensajes no propios que están en 'sent'
        $wpdb->query($wpdb->prepare(
            "UPDATE {$tabla}
             SET delivery_status = %s
             WHERE conversacion_id = %d
               AND remitente_id != %d
               AND delivery_status = %s",
            self::DELIVERY_DELIVERED,
            $conversacion_id,
            $usuario_id,
            self::DELIVERY_SENT
        ));
    }

    /**
     * Marcar mensajes como leídos
     */
    public function mark_messages_read($conversacion_id, $usuario_id = null) {
        global $wpdb;

        $usuario_id = $usuario_id ?: get_current_user_id();
        $tabla = $this->get_mensajes_table();

        // Marcar como leídos los mensajes no propios
        $wpdb->query($wpdb->prepare(
            "UPDATE {$tabla}
             SET delivery_status = %s,
                 leido = 1,
                 fecha_lectura = %s
             WHERE conversacion_id = %d
               AND remitente_id != %d
               AND (delivery_status != %s OR leido = 0)",
            self::DELIVERY_READ,
            current_time('mysql'),
            $conversacion_id,
            $usuario_id,
            self::DELIVERY_READ
        ));

        // Disparar evento
        do_action('flavor_chat_mensajes_leidos', $conversacion_id, $usuario_id);
    }

    /**
     * Obtener HTML del indicador de estado de entrega
     */
    public function get_delivery_status_html($mensaje) {
        $status = $mensaje->delivery_status ?? self::DELIVERY_SENT;

        switch ($status) {
            case self::DELIVERY_PENDING:
                return '<span class="delivery-status pending" title="' . esc_attr__('Enviando...', 'flavor-chat-ia') . '">
                    <svg viewBox="0 0 16 15" width="16" height="15"><path fill="currentColor" d="M8 15l-8-8 1.41-1.41L8 12.17l12.59-12.59L22 1z"/></svg>
                </span>';

            case self::DELIVERY_SENT:
                return '<span class="delivery-status sent" title="' . esc_attr__('Enviado', 'flavor-chat-ia') . '">
                    <svg viewBox="0 0 16 15" width="16" height="15"><path fill="currentColor" d="M15.01 3.316l-.478-.372a.365.365 0 0 0-.51.063L8.666 9.879a.32.32 0 0 1-.484.033l-1.56-1.56a.365.365 0 0 0-.516 0l-.445.445a.365.365 0 0 0 0 .516l2.21 2.21a.402.402 0 0 0 .57 0l6.36-7.693a.366.366 0 0 0-.065-.512z"/></svg>
                </span>';

            case self::DELIVERY_DELIVERED:
                return '<span class="delivery-status delivered" title="' . esc_attr__('Entregado', 'flavor-chat-ia') . '">
                    <svg viewBox="0 0 16 15" width="16" height="15"><path fill="currentColor" d="M15.01 3.316l-.478-.372a.365.365 0 0 0-.51.063L8.666 9.879a.32.32 0 0 1-.484.033l-1.56-1.56a.365.365 0 0 0-.516 0l-.445.445a.365.365 0 0 0 0 .516l2.21 2.21a.402.402 0 0 0 .57 0l6.36-7.693a.366.366 0 0 0-.065-.512z"/><path fill="currentColor" d="M5.516 3.316l-.478-.372a.365.365 0 0 0-.51.063L1.666 9.879a.32.32 0 0 1-.484.033l-1.56-1.56a.365.365 0 0 0-.516 0l-.445.445a.365.365 0 0 0 0 .516l2.21 2.21a.402.402 0 0 0 .57 0l6.36-7.693a.366.366 0 0 0-.065-.512z" transform="translate(4.5)"/></svg>
                </span>';

            case self::DELIVERY_READ:
                return '<span class="delivery-status read" title="' . esc_attr__('Leído', 'flavor-chat-ia') . '">
                    <svg viewBox="0 0 16 15" width="16" height="15"><path fill="#53bdeb" d="M15.01 3.316l-.478-.372a.365.365 0 0 0-.51.063L8.666 9.879a.32.32 0 0 1-.484.033l-1.56-1.56a.365.365 0 0 0-.516 0l-.445.445a.365.365 0 0 0 0 .516l2.21 2.21a.402.402 0 0 0 .57 0l6.36-7.693a.366.366 0 0 0-.065-.512z"/><path fill="#53bdeb" d="M5.516 3.316l-.478-.372a.365.365 0 0 0-.51.063L1.666 9.879a.32.32 0 0 1-.484.033l-1.56-1.56a.365.365 0 0 0-.516 0l-.445.445a.365.365 0 0 0 0 .516l2.21 2.21a.402.402 0 0 0 .57 0l6.36-7.693a.366.366 0 0 0-.065-.512z" transform="translate(4.5)"/></svg>
                </span>';

            default:
                return '';
        }
    }

    // =========================================================================
    // LINK PREVIEWS
    // =========================================================================

    /**
     * Extraer URLs de un mensaje
     */
    public function extract_urls($text) {
        $pattern = '/https?:\/\/[^\s<>"{}|\\^`\[\]]+/i';
        preg_match_all($pattern, $text, $matches);
        return array_unique($matches[0] ?? []);
    }

    /**
     * Obtener preview de un link
     */
    public function get_link_preview($url) {
        // Cache check
        $cache_key = 'flavor_link_preview_' . md5($url);
        $cached = get_transient($cache_key);

        if ($cached !== false) {
            return $cached;
        }

        // Validar URL
        if (!filter_var($url, FILTER_VALIDATE_URL)) {
            return null;
        }

        // Obtener contenido
        $response = wp_remote_get($url, [
            'timeout' => 5,
            'user-agent' => 'FlavorBot/1.0 (Link Preview)',
            'sslverify' => false
        ]);

        if (is_wp_error($response)) {
            return null;
        }

        $body = wp_remote_retrieve_body($response);
        if (empty($body)) {
            return null;
        }

        // Parsear metadatos OG
        $preview = $this->parse_og_metadata($body, $url);

        // Guardar en cache (1 hora)
        set_transient($cache_key, $preview, HOUR_IN_SECONDS);

        return $preview;
    }

    /**
     * Parsear metadatos Open Graph
     */
    private function parse_og_metadata($html, $url) {
        $preview = [
            'url' => $url,
            'domain' => parse_url($url, PHP_URL_HOST),
            'title' => '',
            'description' => '',
            'image' => '',
            'type' => 'website'
        ];

        // Usar DOMDocument si está disponible
        if (class_exists('DOMDocument')) {
            libxml_use_internal_errors(true);
            $doc = new DOMDocument();
            $doc->loadHTML(mb_convert_encoding($html, 'HTML-ENTITIES', 'UTF-8'));
            libxml_clear_errors();

            $metas = $doc->getElementsByTagName('meta');

            foreach ($metas as $meta) {
                $property = $meta->getAttribute('property') ?: $meta->getAttribute('name');
                $content = $meta->getAttribute('content');

                switch ($property) {
                    case 'og:title':
                        $preview['title'] = $content;
                        break;
                    case 'og:description':
                        $preview['description'] = $content;
                        break;
                    case 'og:image':
                        $preview['image'] = $content;
                        break;
                    case 'og:type':
                        $preview['type'] = $content;
                        break;
                    case 'description':
                        if (empty($preview['description'])) {
                            $preview['description'] = $content;
                        }
                        break;
                }
            }

            // Fallback al title tag
            if (empty($preview['title'])) {
                $titles = $doc->getElementsByTagName('title');
                if ($titles->length > 0) {
                    $preview['title'] = $titles->item(0)->textContent;
                }
            }
        } else {
            // Regex fallback
            if (preg_match('/<meta[^>]+property=["\']og:title["\'][^>]+content=["\']([^"\']+)/i', $html, $m)) {
                $preview['title'] = $m[1];
            }
            if (preg_match('/<meta[^>]+property=["\']og:description["\'][^>]+content=["\']([^"\']+)/i', $html, $m)) {
                $preview['description'] = $m[1];
            }
            if (preg_match('/<meta[^>]+property=["\']og:image["\'][^>]+content=["\']([^"\']+)/i', $html, $m)) {
                $preview['image'] = $m[1];
            }
            if (preg_match('/<title>([^<]+)/i', $html, $m)) {
                if (empty($preview['title'])) {
                    $preview['title'] = $m[1];
                }
            }
        }

        // Limpiar
        $preview['title'] = wp_trim_words(wp_strip_all_tags($preview['title']), 15);
        $preview['description'] = wp_trim_words(wp_strip_all_tags($preview['description']), 30);

        // Convertir imagen relativa a absoluta
        if (!empty($preview['image']) && strpos($preview['image'], '//') !== 0 && strpos($preview['image'], 'http') !== 0) {
            $base = parse_url($url);
            $preview['image'] = $base['scheme'] . '://' . $base['host'] . '/' . ltrim($preview['image'], '/');
        }

        return !empty($preview['title']) ? $preview : null;
    }

    /**
     * Renderizar link preview HTML
     */
    public function render_link_preview($preview) {
        if (!$preview) {
            return '';
        }

        $html = '<div class="link-preview" data-url="' . esc_url($preview['url']) . '">';

        if (!empty($preview['image'])) {
            $html .= '<div class="link-preview-image">';
            $html .= '<img src="' . esc_url($preview['image']) . '" alt="" loading="lazy">';
            $html .= '</div>';
        }

        $html .= '<div class="link-preview-content">';
        $html .= '<div class="link-preview-domain">' . esc_html($preview['domain']) . '</div>';
        $html .= '<div class="link-preview-title">' . esc_html($preview['title']) . '</div>';

        if (!empty($preview['description'])) {
            $html .= '<div class="link-preview-description">' . esc_html($preview['description']) . '</div>';
        }

        $html .= '</div>';
        $html .= '</div>';

        return $html;
    }

    // =========================================================================
    // MENSAJES TEMPORALES
    // =========================================================================

    /**
     * Activar/desactivar modo temporal en conversación
     */
    public function set_temporal_mode($conversacion_id, $activo = true, $duracion = null) {
        $duracion = $duracion ?: self::MENSAJE_TEMPORAL_DURACION;

        update_post_meta($conversacion_id, '_temporal_mode', $activo ? 1 : 0);
        update_post_meta($conversacion_id, '_temporal_duracion', $duracion);

        return true;
    }

    /**
     * Verificar si conversación está en modo temporal
     */
    public function is_temporal_mode($conversacion_id) {
        return (bool) get_post_meta($conversacion_id, '_temporal_mode', true);
    }

    /**
     * Obtener duración temporal de conversación
     */
    public function get_temporal_duracion($conversacion_id) {
        $duracion = get_post_meta($conversacion_id, '_temporal_duracion', true);
        return $duracion ?: self::MENSAJE_TEMPORAL_DURACION;
    }

    /**
     * Marcar mensaje como temporal
     */
    public function set_mensaje_temporal($mensaje_id, $expira_en = null) {
        global $wpdb;

        $tabla = $this->get_mensajes_table();
        $expira_en = $expira_en ?: self::MENSAJE_TEMPORAL_DURACION;
        $fecha_expiracion = date('Y-m-d H:i:s', time() + $expira_en);

        $wpdb->update(
            $tabla,
            ['fecha_expiracion' => $fecha_expiracion, 'es_temporal' => 1],
            ['id' => $mensaje_id]
        );
    }

    /**
     * Limpiar mensajes temporales expirados
     */
    public function limpiar_mensajes_temporales() {
        global $wpdb;

        $tabla = $this->get_mensajes_table();

        // Marcar como eliminados los mensajes expirados
        $wpdb->query($wpdb->prepare(
            "UPDATE {$tabla}
             SET eliminado = 1,
                 mensaje = %s,
                 mensaje_html = %s
             WHERE es_temporal = 1
               AND fecha_expiracion < NOW()
               AND eliminado = 0",
            __('Este mensaje ha expirado', 'flavor-chat-ia'),
            '<em class="mensaje-expirado">' . __('Este mensaje ha expirado', 'flavor-chat-ia') . '</em>'
        ));
    }

    // =========================================================================
    // AJAX HANDLERS
    // =========================================================================

    /**
     * AJAX: Actualizar estado de entrega
     */
    public function ajax_update_delivery_status() {
        check_ajax_referer('flavor_chat_nonce', 'nonce');

        $mensaje_id = absint($_POST['mensaje_id'] ?? 0);
        $status = sanitize_text_field($_POST['status'] ?? '');

        if (!$mensaje_id || !$status) {
            wp_send_json_error(['message' => 'Parámetros inválidos']);
        }

        $result = $this->update_delivery_status($mensaje_id, $status);

        wp_send_json_success(['updated' => $result]);
    }

    /**
     * AJAX: Obtener link preview
     */
    public function ajax_get_link_preview() {
        check_ajax_referer('flavor_chat_nonce', 'nonce');

        $url = esc_url_raw($_POST['url'] ?? '');

        if (!$url) {
            wp_send_json_error(['message' => 'URL inválida']);
        }

        $preview = $this->get_link_preview($url);

        if ($preview) {
            wp_send_json_success($preview);
        } else {
            wp_send_json_error(['message' => 'No se pudo obtener preview']);
        }
    }

    /**
     * AJAX: Configurar modo temporal
     */
    public function ajax_set_temporal_mode() {
        check_ajax_referer('flavor_chat_nonce', 'nonce');

        $conversacion_id = absint($_POST['conversacion_id'] ?? 0);
        $activo = (bool) ($_POST['activo'] ?? false);
        $duracion = absint($_POST['duracion'] ?? self::MENSAJE_TEMPORAL_DURACION);

        if (!$conversacion_id) {
            wp_send_json_error(['message' => 'Conversación inválida']);
        }

        $this->set_temporal_mode($conversacion_id, $activo, $duracion);

        wp_send_json_success([
            'temporal_mode' => $activo,
            'duracion' => $duracion
        ]);
    }

    // =========================================================================
    // FILTROS Y HOOKS
    // =========================================================================

    /**
     * Procesar mensaje antes de guardar
     */
    public function process_message_features($datos, $conversacion_id) {
        // Establecer estado inicial de entrega
        $datos['delivery_status'] = self::DELIVERY_SENT;

        // Si la conversación está en modo temporal, marcar mensaje
        if ($this->is_temporal_mode($conversacion_id)) {
            $datos['es_temporal'] = 1;
            $duracion = $this->get_temporal_duracion($conversacion_id);
            $datos['fecha_expiracion'] = date('Y-m-d H:i:s', time() + $duracion);
        }

        // Extraer URLs para link previews
        $urls = $this->extract_urls($datos['mensaje'] ?? '');
        if (!empty($urls)) {
            $datos['has_links'] = 1;
            $datos['link_previews'] = wp_json_encode($urls);
        }

        return $datos;
    }

    /**
     * Mejorar output de mensaje
     */
    public function enhance_message_output($mensaje, $context) {
        // Añadir estado de entrega si es mensaje propio
        if (isset($mensaje->remitente_id) && $mensaje->remitente_id == get_current_user_id()) {
            $mensaje->delivery_html = $this->get_delivery_status_html($mensaje);
        }

        // Añadir link previews
        if (!empty($mensaje->has_links) && !empty($mensaje->link_previews)) {
            $urls = json_decode($mensaje->link_previews, true);
            $previews_html = '';

            foreach ($urls as $url) {
                $preview = $this->get_link_preview($url);
                if ($preview) {
                    $previews_html .= $this->render_link_preview($preview);
                }
            }

            $mensaje->link_previews_html = $previews_html;
        }

        // Indicador de mensaje temporal
        if (!empty($mensaje->es_temporal)) {
            $mensaje->es_temporal_html = '<span class="mensaje-temporal-badge" title="' .
                esc_attr__('Mensaje temporal', 'flavor-chat-ia') . '">⏱</span>';
        }

        return $mensaje;
    }

    // =========================================================================
    // CSS Y JS INLINE
    // =========================================================================

    /**
     * CSS para funcionalidades WhatsApp
     */
    private function get_whatsapp_css() {
        return '
        /* Estados de entrega */
        .delivery-status {
            display: inline-flex;
            align-items: center;
            margin-left: 4px;
            color: #8696a0;
        }

        .delivery-status svg {
            width: 16px;
            height: 15px;
        }

        .delivery-status.pending {
            opacity: 0.5;
        }

        .delivery-status.read svg path {
            fill: #53bdeb;
        }

        /* Link Preview */
        .link-preview {
            display: flex;
            flex-direction: column;
            background: rgba(0, 0, 0, 0.05);
            border-radius: 8px;
            overflow: hidden;
            margin-top: 8px;
            cursor: pointer;
            max-width: 300px;
        }

        .link-preview-image {
            width: 100%;
            max-height: 150px;
            overflow: hidden;
        }

        .link-preview-image img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        .link-preview-content {
            padding: 10px 12px;
        }

        .link-preview-domain {
            font-size: 11px;
            color: #00a884;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            margin-bottom: 4px;
        }

        .link-preview-title {
            font-size: 14px;
            font-weight: 500;
            color: #1a1a1a;
            line-height: 1.3;
            margin-bottom: 4px;
        }

        .link-preview-description {
            font-size: 13px;
            color: #667781;
            line-height: 1.4;
        }

        /* Mensaje temporal */
        .mensaje-temporal-badge {
            display: inline-flex;
            align-items: center;
            margin-left: 4px;
            font-size: 12px;
            color: #8696a0;
        }

        .mensaje-expirado {
            color: #8696a0;
            font-style: italic;
        }

        /* Indicador de modo temporal activo */
        .temporal-mode-indicator {
            display: flex;
            align-items: center;
            gap: 6px;
            padding: 8px 16px;
            background: rgba(37, 211, 102, 0.1);
            border-radius: 8px;
            font-size: 13px;
            color: #25d366;
            margin-bottom: 12px;
        }

        .temporal-mode-indicator .dashicons {
            font-size: 16px;
            width: 16px;
            height: 16px;
        }
        ';
    }

    /**
     * JS para funcionalidades WhatsApp
     */
    private function get_whatsapp_js() {
        return '
        (function($) {
            // Extender FlavorChat con funcionalidades WhatsApp
            if (typeof window.FlavorChat === "undefined") {
                window.FlavorChat = {};
            }

            FlavorChat.WhatsApp = {
                // Obtener link preview
                getLinkPreview: function(url, callback) {
                    $.ajax({
                        url: ajaxurl,
                        type: "POST",
                        data: {
                            action: "flavor_chat_link_preview",
                            nonce: FlavorChat.nonce,
                            url: url
                        },
                        success: function(response) {
                            if (response.success && callback) {
                                callback(response.data);
                            }
                        }
                    });
                },

                // Detectar URLs en input
                detectUrls: function(text) {
                    var pattern = /https?:\/\/[^\s<>"{}|\\^`\[\]]+/gi;
                    return text.match(pattern) || [];
                },

                // Actualizar estado de entrega
                updateDeliveryStatus: function(mensajeId, status) {
                    $.ajax({
                        url: ajaxurl,
                        type: "POST",
                        data: {
                            action: "flavor_chat_delivery_status",
                            nonce: FlavorChat.nonce,
                            mensaje_id: mensajeId,
                            status: status
                        }
                    });
                },

                // Toggle modo temporal
                toggleTemporalMode: function(conversacionId, activo) {
                    return $.ajax({
                        url: ajaxurl,
                        type: "POST",
                        data: {
                            action: "flavor_chat_set_temporal",
                            nonce: FlavorChat.nonce,
                            conversacion_id: conversacionId,
                            activo: activo ? 1 : 0
                        }
                    });
                }
            };

            // Auto-detect links en input
            $(document).on("input", ".chat-input textarea, .chat-input input[type=text]", function() {
                var $input = $(this);
                var text = $input.val();
                var urls = FlavorChat.WhatsApp.detectUrls(text);

                if (urls.length > 0 && !$input.data("preview-loading")) {
                    var url = urls[0];

                    // Evitar múltiples requests para la misma URL
                    if ($input.data("last-url") === url) return;
                    $input.data("last-url", url);
                    $input.data("preview-loading", true);

                    FlavorChat.WhatsApp.getLinkPreview(url, function(preview) {
                        $input.data("preview-loading", false);
                        if (preview) {
                            FlavorChat.showLinkPreviewUI(preview);
                        }
                    });
                }
            });

            // Click en link preview abre URL
            $(document).on("click", ".link-preview", function() {
                var url = $(this).data("url");
                if (url) {
                    window.open(url, "_blank");
                }
            });
        })(jQuery);
        ';
    }

    // =========================================================================
    // HELPERS
    // =========================================================================

    /**
     * Obtener nombre de la tabla de mensajes
     * (debe ser implementado por la clase que use el trait)
     */
    abstract protected function get_mensajes_table();
}

<?php
/**
 * Handlers AJAX para el Chat IA
 *
 * @package CalendarioExperiencias
 * @subpackage ChatIA
 */

if (!defined('ABSPATH')) {
    exit;
}

class Chat_IA_Ajax {

    /**
     * Flag para evitar doble registro
     */
    private static $hooks_registered = false;

    /**
     * Registra los hooks AJAX
     */
    public static function register_hooks() {
        // Evitar doble registro
        if (self::$hooks_registered) {
            return;
        }
        self::$hooks_registered = true;

        // Enviar mensaje
        add_action('wp_ajax_chat_ia_send_message', [__CLASS__, 'handle_send_message']);
        add_action('wp_ajax_nopriv_chat_ia_send_message', [__CLASS__, 'handle_send_message']);

        // Añadir al carrito
        add_action('wp_ajax_chat_ia_add_to_cart', [__CLASS__, 'handle_add_to_cart']);
        add_action('wp_ajax_nopriv_chat_ia_add_to_cart', [__CLASS__, 'handle_add_to_cart']);

        // Obtener borrador
        add_action('wp_ajax_chat_ia_get_draft', [__CLASS__, 'handle_get_draft']);
        add_action('wp_ajax_nopriv_chat_ia_get_draft', [__CLASS__, 'handle_get_draft']);

        // Iniciar sesión
        add_action('wp_ajax_chat_ia_init_session', [__CLASS__, 'handle_init_session']);
        add_action('wp_ajax_nopriv_chat_ia_init_session', [__CLASS__, 'handle_init_session']);

        // Admin: Verificar API key
        add_action('wp_ajax_chat_ia_verify_api_key', [__CLASS__, 'handle_verify_api_key']);

        // Admin: Obtener estadísticas
        add_action('wp_ajax_chat_ia_get_stats', [__CLASS__, 'handle_get_stats']);

        // Admin: Actualizar ticket de escalado
        add_action('wp_ajax_chat_ia_update_escalation', [__CLASS__, 'handle_update_escalation']);

        // Admin: Autoconfiguración IA
        add_action('wp_ajax_chat_ia_autoconfig', [__CLASS__, 'handle_autoconfig']);

        // Debug: Diagnóstico del calendario (solo admin)
        add_action('wp_ajax_chat_ia_debug_calendario', [__CLASS__, 'handle_debug_calendario']);

        // Admin: Regenerar token de seguridad admin móvil
        add_action('wp_ajax_chat_ia_regenerate_admin_token', [__CLASS__, 'handle_regenerate_admin_token']);
    }

    /**
     * Handler para regenerar token de seguridad admin móvil
     */
    public static function handle_regenerate_admin_token() {
        check_ajax_referer('chat_ia_admin_nonce', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_send_json_error(['error' => __('Sin permisos', 'flavor-chat-ia')]);
        }

        // Regenerar el token
        require_once CHAT_IA_ADDON_PATH . 'includes/class-mobile-api.php';
        Chat_IA_Mobile_API::get_admin_site_secret(true);

        wp_send_json_success([
            'message' => __('Token regenerado correctamente', 'flavor-chat-ia'),
            'qr_data' => Chat_IA_Mobile_API::get_admin_qr_data(),
        ]);
    }

    /**
     * Handler para diagnóstico del calendario (solo admin)
     */
    public static function handle_debug_calendario() {
        if (!current_user_can('manage_options')) {
            wp_send_json_error(['error' => __('Sin permisos', 'flavor-chat-ia')]);
        }

        global $wpdb;

        $debug = [];

        // 1. Prefijo de tablas
        $debug['prefijo'] = $wpdb->prefix;

        // 2. Verificar tabla calendario_dias
        $tabla_dias = $wpdb->prefix . 'calendario_dias';
        $existe = Flavor_Chat_Helpers::tabla_existe($tabla_dias) ? $tabla_dias : null;
        $debug['tabla_dias'] = [
            'nombre' => $tabla_dias,
            'existe' => !empty($existe),
        ];

        if ($existe) {
            // Contar registros
            $debug['tabla_dias']['total_registros'] = $wpdb->get_var("SELECT COUNT(*) FROM $tabla_dias");

            // Registros futuros
            $hoy = date('Y-m-d');
            $registros_futuros = $wpdb->get_results($wpdb->prepare(
                "SELECT fecha, estado, horario FROM $tabla_dias WHERE fecha >= %s ORDER BY fecha LIMIT 20",
                $hoy
            ));
            $debug['tabla_dias']['registros_futuros'] = $registros_futuros;

            // Si no hay futuros, mostrar los últimos
            if (empty($registros_futuros)) {
                $ultimos = $wpdb->get_results("SELECT fecha, estado, horario FROM $tabla_dias ORDER BY fecha DESC LIMIT 10");
                $debug['tabla_dias']['ultimos_registros'] = $ultimos;
            }
        }

        // 3. Verificar otras tablas relacionadas
        $tablas_calendario = $wpdb->get_col($wpdb->prepare("SHOW TABLES LIKE %s", $wpdb->esc_like($wpdb->prefix . 'flavor_') . '%calendario%'));
        $debug['tablas_calendario'] = $tablas_calendario;

        // 4. Estados configurados
        $debug['estados'] = get_option('calendario_experiencias_estados', []);

        // 5. Mapeo estado -> tickets
        $debug['mapeo_tickets'] = get_option('calendario_experiencias_state_ticket_mapping', []);

        // 6. Tipos de tickets
        $debug['tipos_tickets'] = get_option('calendario_experiencias_ticket_types', []);

        // 7. Datos en wp_options - probar diferentes nombres
        $datos_option = get_option('calendario_experiencias_datos', []);
        $debug['datos_en_options'] = [
            'existe' => !empty($datos_option),
            'cantidad' => is_array($datos_option) ? count($datos_option) : 0,
            'muestra' => is_array($datos_option) ? array_slice($datos_option, 0, 5, true) : $datos_option,
        ];

        // 8. Probar calendario_experiencias_dias (usado en reservas-assets.php)
        $dias_option = get_option('calendario_experiencias_dias', []);

        // Filtrar fechas por año
        $fechas_2025 = [];
        $fechas_2026 = [];
        $fechas_futuras = [];
        $hoy = date('Y-m-d');

        if (is_array($dias_option)) {
            foreach ($dias_option as $fecha => $estado) {
                if (strpos($fecha, '2025') === 0) {
                    $fechas_2025[$fecha] = $estado;
                } elseif (strpos($fecha, '2026') === 0) {
                    $fechas_2026[$fecha] = $estado;
                }
                if ($fecha >= $hoy) {
                    $fechas_futuras[$fecha] = $estado;
                }
            }
            ksort($fechas_2026);
            ksort($fechas_futuras);
        }

        $debug['dias_en_options'] = [
            'existe' => !empty($dias_option),
            'cantidad_total' => is_array($dias_option) ? count($dias_option) : 0,
            'cantidad_2025' => count($fechas_2025),
            'cantidad_2026' => count($fechas_2026),
            'cantidad_futuras' => count($fechas_futuras),
            'muestra_2026' => array_slice($fechas_2026, 0, 15, true),
            'muestra_futuras' => array_slice($fechas_futuras, 0, 15, true),
            'fecha_hoy' => $hoy,
        ];

        // Invalidar caché del knowledge base para forzar recarga
        delete_transient('chat_ia_knowledge_es');
        delete_transient('chat_ia_knowledge_eu');
        delete_transient('chat_ia_knowledge_en');
        delete_transient('chat_ia_knowledge_fr');
        delete_transient('chat_ia_knowledge_ca');
        $debug['cache_invalidado'] = true;

        // 9. Buscar todas las opciones que contengan 'calendario'
        global $wpdb;
        $opciones_calendario = $wpdb->get_col(
            "SELECT option_name FROM {$wpdb->options} WHERE option_name LIKE '%calendario%' LIMIT 30"
        );
        $debug['opciones_calendario'] = $opciones_calendario;

        wp_send_json_success($debug);
    }

    /**
     * Handler para enviar mensaje
     */
    public static function handle_send_message() {
        // Verificar nonce
        if (!wp_verify_nonce($_POST['nonce'] ?? '', 'chat_ia_nonce')) {
            wp_send_json_error([
                'error' => __('Sesión expirada. Por favor, recarga la página.', 'chat-ia-addon'),
                'error_code' => 'invalid_nonce',
            ]);
        }

        // Obtener datos básicos
        $ip = self::get_client_ip();
        $session_id = sanitize_text_field($_POST['session_id'] ?? '');
        $message = sanitize_textarea_field($_POST['message'] ?? '');
        $language = sanitize_text_field($_POST['language'] ?? 'es');
        $honeypot = sanitize_text_field($_POST['website_url'] ?? ''); // Campo honeypot

        // Validar idioma
        $allowed_languages = ['es', 'eu', 'en', 'fr', 'ca'];
        if (!in_array($language, $allowed_languages)) {
            $language = 'es';
        }

        // Validar mensaje básico
        if (empty($message)) {
            wp_send_json_error([
                'error' => __('Por favor, escribe un mensaje.', 'chat-ia-addon'),
                'error_code' => 'empty_message',
            ]);
        }

        if (strlen($message) > 2000) {
            wp_send_json_error([
                'error' => __('El mensaje es demasiado largo. Máximo 2000 caracteres.', 'chat-ia-addon'),
                'error_code' => 'message_too_long',
            ]);
        }

        // Sistema Antispam
        if (class_exists('Chat_IA_Antispam')) {
            $antispam = Chat_IA_Antispam::get_instance();
            $validation = $antispam->validate_message($message, $session_id, $ip, [
                'honeypot' => $honeypot,
            ]);

            if (!$validation['valid']) {
                wp_send_json_error([
                    'error' => $validation['error'],
                    'error_code' => $validation['error_code'],
                ]);
            }
        }

        // Procesar mensaje
        $core = Chat_IA_Core::get_instance();
        $result = $core->process_message($session_id, $message, $language);

        if ($result['success']) {
            wp_send_json_success($result);
        } else {
            wp_send_json_error($result);
        }
    }

    /**
     * Handler para añadir al carrito
     */
    public static function handle_add_to_cart() {
        // Verificar nonce
        if (!wp_verify_nonce($_POST['nonce'] ?? '', 'chat_ia_nonce')) {
            wp_send_json_error([
                'error' => __('Sesión expirada. Por favor, recarga la página.', 'chat-ia-addon'),
                'error_code' => 'invalid_nonce',
            ]);
        }

        $session_id = sanitize_text_field($_POST['session_id'] ?? '');

        if (empty($session_id)) {
            wp_send_json_error([
                'error' => __('Sesión no válida.', 'chat-ia-addon'),
                'error_code' => 'invalid_session',
            ]);
        }

        $core = Chat_IA_Core::get_instance();
        $result = $core->add_draft_to_cart($session_id);

        if ($result['success']) {
            wp_send_json_success($result);
        } else {
            wp_send_json_error($result);
        }
    }

    /**
     * Handler para obtener borrador
     */
    public static function handle_get_draft() {
        // Verificar nonce
        if (!wp_verify_nonce($_POST['nonce'] ?? '', 'chat_ia_nonce')) {
            wp_send_json_error([
                'error' => __('Sesión expirada.', 'chat-ia-addon'),
                'error_code' => 'invalid_nonce',
            ]);
        }

        $session_id = sanitize_text_field($_POST['session_id'] ?? '');

        if (empty($session_id)) {
            wp_send_json_success(['draft' => null]);
            return;
        }

        $core = Chat_IA_Core::get_instance();
        $draft = $core->get_session_draft($session_id);

        wp_send_json_success(['draft' => $draft]);
    }

    /**
     * Handler para iniciar sesión
     */
    public static function handle_init_session() {
        // Verificar nonce
        if (!wp_verify_nonce($_POST['nonce'] ?? '', 'chat_ia_nonce')) {
            wp_send_json_error([
                'error' => __('Error de seguridad.', 'chat-ia-addon'),
                'error_code' => 'invalid_nonce',
            ]);
        }

        $language = sanitize_text_field($_POST['language'] ?? 'es');

        // Crear nueva sesión
        $session = new Chat_IA_Session(null, $language);

        // Obtener configuración para el frontend
        $settings = get_option('chat_ia_settings', []);

        wp_send_json_success([
            'session_id' => $session->get_id(),
            'language' => $session->get_language(),
            'assistant_name' => $settings['assistant_name'] ?? __('Asistente Virtual', 'chat-ia-addon'),
            'welcome_message' => self::get_welcome_message($language),
        ]);
    }

    /**
     * Obtiene el mensaje de bienvenida
     *
     * @param string $language
     * @return string
     */
    private static function get_welcome_message($language) {
        $settings = get_option('chat_ia_settings', []);
        $assistant_name = $settings['assistant_name'] ?? __('Asistente Virtual', 'chat-ia-addon');

        $messages = [
            'es' => sprintf(__('¡Hola! Soy %s. Estoy aquí para ayudarte a hacer tu reserva, responder tus preguntas o darte información sobre nuestras experiencias. ¿En qué puedo ayudarte?', 'flavor-chat-ia'), $assistant_name),
            'eu' => sprintf(__('Kaixo! %s naiz. Hemen nago zure erreserba egiten laguntzeko, zure galderak erantzuteko edo gure esperientziei buruzko informazioa emateko. Zertan lagun dezaket?', 'flavor-chat-ia'), $assistant_name),
        ];

        return $messages[$language] ?? $messages['es'];
    }

    /**
     * Handler para verificar API key (admin)
     */
    public static function handle_verify_api_key() {
        // Verificar permisos
        if (!current_user_can('manage_options')) {
            wp_send_json_error(['error' => __('Sin permisos', 'flavor-chat-ia')]);
        }

        // Verificar nonce
        if (!wp_verify_nonce($_POST['nonce'] ?? '', 'chat_ia_admin_nonce')) {
            wp_send_json_error(['error' => __('Nonce inválido', 'flavor-chat-ia')]);
        }

        $api_key = sanitize_text_field($_POST['api_key'] ?? '');

        if (empty($api_key)) {
            wp_send_json_error(['error' => __('API key vacía', 'flavor-chat-ia')]);
        }

        $engine = Chat_IA_Claude_Engine::get_instance();
        $valid = $engine->verify_api_key($api_key);

        if ($valid) {
            wp_send_json_success(['message' => __('API key válida', 'flavor-chat-ia')]);
        } else {
            wp_send_json_error(['error' => __('API key no válida', 'flavor-chat-ia')]);
        }
    }

    /**
     * Handler para obtener estadísticas (admin)
     */
    public static function handle_get_stats() {
        // Verificar permisos
        if (!current_user_can('manage_options')) {
            wp_send_json_error(['error' => __('Sin permisos', 'flavor-chat-ia')]);
        }

        // Verificar nonce
        if (!wp_verify_nonce($_POST['nonce'] ?? '', 'chat_ia_admin_nonce')) {
            wp_send_json_error(['error' => __('Nonce inválido', 'flavor-chat-ia')]);
        }

        $periodo = sanitize_text_field($_POST['periodo'] ?? 'week');

        $core = Chat_IA_Core::get_instance();
        $stats = $core->get_statistics($periodo);

        $escalation = Chat_IA_Escalation::get_instance();
        $escalation_stats = $escalation->get_estadisticas($periodo);

        // Estadísticas del chat admin
        $admin_stats = [];
        if (class_exists('Chat_IA_Admin_Assistant')) {
            $admin_stats = Chat_IA_Admin_Assistant::get_admin_stats($periodo);
        }

        // Información del proveedor activo para cálculo de costes
        $provider_info = [
            'provider' => 'claude',
            'model' => 'claude-sonnet-4-20250514',
        ];
        if (class_exists('Chat_IA_Engine_Manager')) {
            $settings = get_option('chat_ia_settings', []);
            $provider_info['provider'] = $settings['active_provider'] ?? 'claude';
            $provider_info['model'] = $settings[$provider_info['provider'] . '_model'] ?? '';
        }

        // Estadísticas de apps móviles
        $mobile_stats = [];
        if (class_exists('Chat_IA_Mobile_API')) {
            $mobile_stats = Chat_IA_Mobile_API::get_mobile_stats($periodo);
        }

        wp_send_json_success([
            'chat' => $stats,
            'escalation' => $escalation_stats,
            'admin_chat' => $admin_stats,
            'mobile' => $mobile_stats,
            'provider' => $provider_info,
        ]);
    }

    /**
     * Handler para actualizar ticket de escalado (admin)
     */
    public static function handle_update_escalation() {
        // Verificar permisos
        if (!current_user_can('manage_options')) {
            wp_send_json_error(['error' => __('Sin permisos', 'flavor-chat-ia')]);
        }

        // Verificar nonce
        if (!wp_verify_nonce($_POST['nonce'] ?? '', 'chat_ia_admin_nonce')) {
            wp_send_json_error(['error' => __('Nonce inválido', 'flavor-chat-ia')]);
        }

        $ticket_id = intval($_POST['ticket_id'] ?? 0);
        $status = sanitize_text_field($_POST['status'] ?? '');
        $notes = sanitize_textarea_field($_POST['notes'] ?? '');

        if (!$ticket_id) {
            wp_send_json_error(['error' => __('ID de ticket no válido', 'flavor-chat-ia')]);
        }

        $allowed_statuses = ['pending', 'contacted', 'resolved'];
        if (!in_array($status, $allowed_statuses)) {
            wp_send_json_error(['error' => __('Estado no válido', 'flavor-chat-ia')]);
        }

        $escalation = Chat_IA_Escalation::get_instance();
        $result = $escalation->actualizar_ticket($ticket_id, $status, $notes);

        if ($result) {
            wp_send_json_success(['message' => __('Ticket actualizado', 'flavor-chat-ia')]);
        } else {
            wp_send_json_error(['error' => __('Error al actualizar el ticket', 'flavor-chat-ia')]);
        }
    }

    /**
     * Handler para autoconfiguración con IA (OPTIMIZADO)
     * - Usa caché de 24h para evitar llamadas repetidas
     * - Contenido compacto para reducir tokens
     * - Reintento automático en caso de overload
     */
    public static function handle_autoconfig() {
        // Verificar permisos
        if (!current_user_can('manage_options')) {
            wp_send_json_error(['error' => __('Sin permisos', 'flavor-chat-ia')]);
        }

        // Verificar nonce
        if (!wp_verify_nonce($_POST['nonce'] ?? '', 'chat_ia_admin_nonce')) {
            wp_send_json_error(['error' => __('Nonce inválido', 'flavor-chat-ia')]);
        }

        // Parámetro para forzar regeneración (skip cache)
        $force_refresh = !empty($_POST['force_refresh']);

        // Verificar caché (24 horas) - solo si no se fuerza refresh
        $cache_key = 'chat_ia_autoconfig_result';
        if (!$force_refresh) {
            $cached = get_transient($cache_key);
            if ($cached && is_array($cached)) {
                flavor_log_debug( 'Autoconfig: Usando resultado cacheado', 'ChatIA' );
                wp_send_json_success($cached);
                return;
            }
        }

        // Verificar que hay un motor de IA configurado
        if (!class_exists('Chat_IA_Engine_Manager')) {
            wp_send_json_error(['error' => __('Sistema de IA no disponible', 'flavor-chat-ia')]);
        }

        $engine_manager = Chat_IA_Engine_Manager::get_instance();
        $active_engine = $engine_manager->get_active_engine();

        if (!$active_engine) {
            wp_send_json_error(['error' => __('No hay proveedor de IA seleccionado. Ve a Proveedores IA y configura uno.', 'flavor-chat-ia')]);
        }

        if (!$active_engine->is_configured()) {
            $engine_name = $active_engine->get_name();
            wp_send_json_error(['error' => sprintf(__('El proveedor %s no está configurado. Añade la API key en Proveedores IA.', 'flavor-chat-ia'), $engine_name)]);
        }

        try {
            // Obtener contenido del sitio (ya optimizado)
            $site_content = self::get_site_content();

            if (empty($site_content)) {
                wp_send_json_error(['error' => __('No se pudo obtener contenido del sitio', 'flavor-chat-ia')]);
            }

            // Intentar análisis con reintento en caso de overload
            $max_retries = 2;
            $result = null;

            for ($attempt = 1; $attempt <= $max_retries; $attempt++) {
                $result = self::analyze_site_with_engine($active_engine, $site_content);

                // Si no hay error de overload, salir del bucle
                if (!isset($result['error']) || stripos($result['error'], 'sobrecargado') === false) {
                    break;
                }

                // Esperar antes de reintentar (solo si no es el último intento)
                if ($attempt < $max_retries) {
                    flavor_chat_ia_log( "Autoconfig: Overload detectado, reintentando en 3s (intento {$attempt}/{$max_retries})", 'warning', 'ChatIA' );
                    sleep(3);
                }
            }

            if (isset($result['error'])) {
                wp_send_json_error(['error' => $result['error']]);
            }

            if (!$result || !is_array($result)) {
                wp_send_json_error(['error' => __('Error al analizar con IA - respuesta vacía o inválida', 'flavor-chat-ia')]);
            }

            // Guardar en caché por 24 horas
            set_transient($cache_key, $result, DAY_IN_SECONDS);

            wp_send_json_success($result);

        } catch (Exception $e) {
            flavor_log_error( 'Autoconfig Error: ' . $e->getMessage(), 'ChatIA' );
            wp_send_json_error(['error' => $e->getMessage()]);
        }
    }

    /**
     * Obtiene contenido del sitio para análisis (EQUILIBRADO)
     * Suficiente contexto para FAQs útiles, pero limitado para evitar overload
     */
    private static function get_site_content() {
        $content = [];

        // Info básica del sitio
        $content['nombre'] = get_bloginfo('name');
        $content['descripcion'] = get_bloginfo('description');
        $content['email_admin'] = get_bloginfo('admin_email');

        $all_text = '';

        // Obtener páginas con contenido útil (límite 800 chars por página, max 5 páginas)
        $pages = get_posts([
            'post_type' => 'page',
            'post_status' => 'publish',
            'posts_per_page' => 8,
        ]);

        $content['paginas'] = [];
        foreach ($pages as $page) {
            $text = wp_strip_all_tags(strip_shortcodes($page->post_content));
            if (strlen($text) < 50) continue; // Ignorar páginas vacías

            $all_text .= ' ' . $text;
            $content['paginas'][] = [
                'titulo' => $page->post_title,
                'contenido' => substr($text, 0, 800)
            ];

            if (count($content['paginas']) >= 5) break;
        }

        // Obtener productos con descripción corta
        if (class_exists('WooCommerce')) {
            $products = get_posts([
                'post_type' => 'product',
                'post_status' => 'publish',
                'posts_per_page' => 8,
            ]);

            $content['productos'] = [];
            foreach ($products as $product) {
                $wc_product = wc_get_product($product->ID);
                if ($wc_product) {
                    $desc = wp_strip_all_tags($wc_product->get_short_description() ?: $wc_product->get_description());
                    $content['productos'][] = [
                        'nombre' => $wc_product->get_name(),
                        'precio' => $wc_product->get_price() . '€',
                        'descripcion' => substr($desc, 0, 200)
                    ];
                    $all_text .= ' ' . $desc;
                }
            }
        }

        // Obtener experiencias con descripción
        $experiencias = get_posts([
            'post_type' => 'experiencia',
            'post_status' => 'publish',
            'posts_per_page' => 8,
        ]);

        $content['experiencias'] = [];
        foreach ($experiencias as $exp) {
            $desc = wp_strip_all_tags($exp->post_content);
            $content['experiencias'][] = [
                'nombre' => $exp->post_title,
                'descripcion' => substr($desc, 0, 300)
            ];
            $all_text .= ' ' . $desc;
        }

        // Extraer teléfonos del contenido (formato internacional)
        // Soporta: +34 666 777 888, (555) 123-4567, +1-800-555-1234, etc.
        preg_match_all('/(?:\+\d{1,3}[\s\-]?)?(?:\(?\d{2,4}\)?[\s\-]?)?\d{3}[\s\-]?\d{3,4}[\s\-]?\d{0,4}/', $all_text, $phone_matches);
        if (!empty($phone_matches[0])) {
            // Filtrar matches muy cortos (menos de 9 dígitos probablemente no son teléfonos)
            $valid_phones = array_filter($phone_matches[0], function($p) {
                return preg_match_all('/\d/', $p) >= 9;
            });
            if ($valid_phones) {
                $content['telefono'] = trim(array_values($valid_phones)[0]);
            }
        }

        // Extraer emails del contenido
        preg_match_all('/[a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,}/', $all_text, $email_matches);
        if (!empty($email_matches[0])) {
            $emails = array_filter(array_unique($email_matches[0]), function($e) {
                return stripos($e, 'example') === false && stripos($e, 'test') === false && stripos($e, 'wordpress') === false;
            });
            $content['email'] = array_values($emails)[0] ?? '';
        }

        // === MEJORAS: Extraer datos adicionales para apps móviles ===

        // Extraer direcciones (patrones españoles comunes)
        preg_match_all('/(?:C\/|Calle|Avda\.|Avenida|Plaza|Paseo|Polígono|Barrio)[^<\n\r]{10,100}/i', $all_text, $address_matches);
        if (!empty($address_matches[0])) {
            $content['direccion'] = trim(strip_tags($address_matches[0][0]));
        }

        // Extraer coordenadas GPS (formato decimal)
        if (preg_match('/(-?\d{1,3}\.\d{4,8})[,\s]+(-?\d{1,3}\.\d{4,8})/', $all_text, $coords)) {
            $lat = floatval($coords[1]);
            $lng = floatval($coords[2]);
            if ($lat >= -90 && $lat <= 90 && $lng >= -180 && $lng <= 180) {
                $content['coordenadas'] = ['lat' => $lat, 'lng' => $lng];
            }
        }

        // Buscar URL de Google Maps
        if (preg_match('/(https?:\/\/(?:www\.)?(?:google\.com\/maps|maps\.google\.com|goo\.gl\/maps)[^\s<"\']+)/i', $all_text, $map_match)) {
            $content['map_url'] = $map_match[1];
        }

        // Extraer horarios (patrones comunes)
        $horario_patterns = [
            '/(?:Lunes|Martes|Miércoles|Jueves|Viernes|Sábado|Domingo|L-V|L-S|L-D)[^<\n]{5,100}(?:\d{1,2}[:\.]?\d{0,2}[^\d<\n]{1,10}\d{1,2}[:\.]?\d{0,2})/iu',
            '/(?:Horario|Abierto|Apertura)[:\s]+[^<\n]{10,150}/iu',
        ];
        foreach ($horario_patterns as $pattern) {
            if (preg_match($pattern, $all_text, $schedule_match)) {
                $content['horario'] = trim(strip_tags($schedule_match[0]));
                break;
            }
        }

        // Buscar número de WhatsApp (a veces se indica explícitamente)
        if (preg_match('/(?:whatsapp|wa\.me)[^\d]*(\+?\d[\d\s\-]{8,})/i', $all_text, $wa_match)) {
            $content['whatsapp'] = preg_replace('/[^\d+]/', '', $wa_match[1]);
        }

        return $content;
    }

    /**
     * Analiza el sitio usando el motor de IA activo (EQUILIBRADO)
     * Suficiente contexto para generar FAQs útiles
     *
     * @param Chat_IA_Engine_Interface $engine Motor de IA activo
     * @param array $site_content Contenido del sitio
     * @return array|null
     */
    private static function analyze_site_with_engine($engine, $site_content) {
        $system_prompt = "Eres un asistente que configura chatbots. Analiza el contenido y genera JSON. REGLAS CRÍTICAS:
1. SOLO usa información que encuentres en el contenido proporcionado
2. Si NO encuentras un dato (teléfono, email, dirección, horario), deja el campo VACÍO (\"\")
3. NO inventes información - es preferible dejar vacío que inventar
4. Las FAQs deben basarse en el contenido real del sitio
5. Responde SOLO con JSON válido, sin markdown ni explicaciones";

        // Construir contenido estructurado
        $site_data = [
            'nombre_sitio' => $site_content['nombre'],
            'descripcion_sitio' => $site_content['descripcion'] ?? '',
        ];

        // Datos de contacto detectados
        if (!empty($site_content['telefono'])) {
            $site_data['telefono_encontrado'] = $site_content['telefono'];
        }
        if (!empty($site_content['email'])) {
            $site_data['email_encontrado'] = $site_content['email'];
        } elseif (!empty($site_content['email_admin'])) {
            $site_data['email_admin'] = $site_content['email_admin'];
        }

        // Datos adicionales para apps móviles
        if (!empty($site_content['direccion'])) {
            $site_data['direccion_encontrada'] = $site_content['direccion'];
        }
        if (!empty($site_content['coordenadas'])) {
            $site_data['coordenadas_encontradas'] = $site_content['coordenadas'];
        }
        if (!empty($site_content['map_url'])) {
            $site_data['map_url_encontrada'] = $site_content['map_url'];
        }
        if (!empty($site_content['horario'])) {
            $site_data['horario_encontrado'] = $site_content['horario'];
        }
        if (!empty($site_content['whatsapp'])) {
            $site_data['whatsapp_encontrado'] = $site_content['whatsapp'];
        }

        // Páginas
        if (!empty($site_content['paginas'])) {
            $site_data['paginas'] = $site_content['paginas'];
        }

        // Productos
        if (!empty($site_content['productos'])) {
            $site_data['productos'] = $site_content['productos'];
        }

        // Experiencias
        if (!empty($site_content['experiencias'])) {
            $site_data['experiencias'] = $site_content['experiencias'];
        }

        $json_content = json_encode($site_data, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);

        $user_message = "Analiza este contenido de sitio web y genera configuración para chatbot:

{$json_content}

Genera un JSON con esta estructura exacta:
{
  \"business_info\": {
    \"description\": \"Descripción breve basada en el contenido real\",
    \"address\": \"SOLO si encontraste dirección, si no dejar vacío\",
    \"phone\": \"SOLO si encontraste teléfono, si no dejar vacío\",
    \"email\": \"SOLO si encontraste email, si no dejar vacío\",
    \"schedule\": \"SOLO si encontraste horarios, si no dejar vacío\"
  },
  \"faqs\": [
    {\"question\": \"Pregunta basada en el contenido\", \"answer\": \"Respuesta con info real del sitio\"}
  ],
  \"policies\": {
    \"cancellation\": \"Política de cancelación si la encontraste, si no genera una genérica\",
    \"groups\": \"Info sobre grupos si la encontraste, si no genera una genérica\",
    \"accessibility\": \"Info de accesibilidad si la encontraste, si no dejar vacío\"
  },
  \"mobile_contact\": {
    \"phone\": \"Teléfono principal (mismo que business_info)\",
    \"email\": \"Email principal (mismo que business_info)\",
    \"whatsapp\": \"Número WhatsApp si lo encontraste (formato +34XXXXXXXXX)\",
    \"schedule\": \"Horarios de atención formateados\"
  },
  \"mobile_location\": {
    \"address\": \"Dirección completa\",
    \"lat\": \"Latitud si encontraste coordenadas, si no vacío\",
    \"lng\": \"Longitud si encontraste coordenadas, si no vacío\",
    \"map_url\": \"URL de Google Maps si la encontraste\"
  },
  \"suggested_welcome\": \"Mensaje de bienvenida apropiado para este negocio\",
  \"suggested_name\": \"Nombre sugerido para el asistente\"
}

IMPORTANTE:
1. Genera 5-7 FAQs basadas en el contenido REAL. Ejemplos de preguntas útiles:
   - Qué ofrece el negocio
   - Precios si están disponibles
   - Cómo reservar/comprar
   - Ubicación si está disponible
   - Qué incluyen los servicios/productos
2. Para policies: Si no encuentras políticas específicas, genera unas genéricas apropiadas para el tipo de negocio
3. Para mobile_contact y mobile_location: Usa los mismos datos que business_info si aplica";

        $messages = [
            ['role' => 'user', 'content' => $user_message]
        ];

        $response = $engine->send_message($messages, $system_prompt, []);

        if (!$response['success']) {
            $error_msg = $response['error'] ?? 'Error desconocido de la API';
            flavor_log_error( 'Autoconfig API Error: ' . $error_msg, 'ChatIA' );
            return ['error' => sprintf(__('Error de la API: %s', 'flavor-chat-ia'), $error_msg)];
        }

        $text = $response['response'] ?? '';

        if (empty($text)) {
            flavor_chat_ia_log( 'Autoconfig: Empty response from API', 'warning', 'ChatIA' );
            return ['error' => __('La IA no devolvió ninguna respuesta', 'flavor-chat-ia')];
        }

        // Detectar si la respuesta contiene error de overload
        if (stripos($text, 'overload') !== false || stripos($text, 'rate limit') !== false) {
            return ['error' => __('El servicio de IA está temporalmente sobrecargado. Espera unos segundos e inténtalo de nuevo.', 'flavor-chat-ia')];
        }

        // Log para debug
        flavor_log_debug( 'Autoconfig: Raw response length: ' . strlen($text), 'ChatIA' );

        // Extraer JSON de forma robusta
        $json_text = self::extract_json_from_text($text);

        if (empty($json_text)) {
            flavor_chat_ia_log( 'Autoconfig: Could not extract JSON from response', 'warning', 'ChatIA' );
            flavor_log_debug( 'Autoconfig: Raw text (first 1000 chars) - ' . substr($text, 0, 1000), 'ChatIA' );
            return ['error' => __('No se pudo extraer JSON de la respuesta de la IA. Inténtalo de nuevo.', 'flavor-chat-ia')];
        }

        $analysis = json_decode($json_text, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            flavor_chat_ia_log( 'Autoconfig: JSON parse error - ' . json_last_error_msg(), 'warning', 'ChatIA' );
            flavor_log_debug( 'Autoconfig: Extracted JSON (first 500 chars) - ' . substr($json_text, 0, 500), 'ChatIA' );

            // Dar un mensaje más descriptivo
            $json_error = json_last_error_msg();
            return ['error' => sprintf(__('Error al procesar la respuesta de la IA. JSON inválido: %s. Inténtalo de nuevo.', 'flavor-chat-ia'), $json_error)];
        }

        // Normalizar estructura si la IA devolvió formato plano
        $analysis = self::normalize_autoconfig_response($analysis);

        flavor_log_debug( 'Autoconfig: Normalized response keys: ' . implode(', ', array_keys($analysis)), 'ChatIA' );

        return $analysis;
    }

    /**
     * Normaliza la respuesta de autoconfiguración para asegurar estructura correcta
     *
     * La IA a veces devuelve estructura plana (description, address directamente)
     * en lugar de la estructura anidada esperada (business_info.description, etc.)
     *
     * @param array $data Datos de la respuesta de IA
     * @return array Datos normalizados con estructura correcta
     */
    private static function normalize_autoconfig_response($data) {
        // Si ya tiene la estructura correcta, retornar tal cual
        if (isset($data['business_info']) && is_array($data['business_info'])) {
            return $data;
        }

        // Detectar si es estructura plana (tiene campos de business_info en raíz)
        $business_fields = ['description', 'address', 'phone', 'email', 'schedule'];
        $has_flat_structure = false;

        foreach ($business_fields as $field) {
            if (isset($data[$field]) && !empty($data[$field])) {
                $has_flat_structure = true;
                break;
            }
        }

        if ($has_flat_structure) {
            flavor_log_debug( 'Autoconfig: Detectada estructura plana, normalizando...', 'ChatIA' );

            // Construir estructura normalizada
            $normalized = [
                'business_info' => [
                    'description' => $data['description'] ?? '',
                    'address' => $data['address'] ?? '',
                    'phone' => $data['phone'] ?? '',
                    'email' => $data['email'] ?? '',
                    'schedule' => $data['schedule'] ?? '',
                ],
            ];

            // Copiar otros campos que ya tienen estructura correcta
            if (isset($data['faqs'])) {
                $normalized['faqs'] = $data['faqs'];
            }

            // Normalizar políticas - mapear nombres antiguos a nuevos si es necesario
            if (isset($data['policies'])) {
                $policies = $data['policies'];
                $normalized['policies'] = [
                    'cancellation' => $policies['cancellation'] ?? $policies['cancellations'] ?? '',
                    'groups' => $policies['groups'] ?? $policies['reservations'] ?? '',
                    'accessibility' => $policies['accessibility'] ?? $policies['payments'] ?? '',
                ];
            }

            if (isset($data['suggested_welcome'])) {
                $normalized['suggested_welcome'] = $data['suggested_welcome'];
            }
            if (isset($data['suggested_name'])) {
                $normalized['suggested_name'] = $data['suggested_name'];
            }

            return $normalized;
        }

        // Retornar datos originales si no se detectó patrón conocido
        return $data;
    }

    /**
     * Extrae JSON de un texto que puede contener markdown u otro contenido
     *
     * @param string $text Texto con posible JSON
     * @return string|null JSON extraído o null si no se encuentra
     */
    private static function extract_json_from_text($text) {
        // Limpiar caracteres problemáticos
        $text = preg_replace('/[\x00-\x1F\x7F]/u', ' ', $text); // Control chars
        $text = str_replace(["\r\n", "\r"], "\n", $text); // Normalizar saltos de línea

        // Eliminar bloques de código markdown
        $text = preg_replace('/```json\s*/i', '', $text);
        $text = preg_replace('/```\s*$/m', '', $text);
        $text = preg_replace('/^```\s*/m', '', $text);

        $text = trim($text);

        // Método 1: Intentar parsear directamente
        $decoded = json_decode($text, true);
        if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
            return $text;
        }

        // Método 2: Buscar el primer { y balancear llaves
        $start = strpos($text, '{');
        if ($start === false) {
            return null;
        }

        $depth = 0;
        $in_string = false;
        $escape = false;
        $len = strlen($text);

        for ($i = $start; $i < $len; $i++) {
            $char = $text[$i];

            if ($escape) {
                $escape = false;
                continue;
            }

            if ($char === '\\' && $in_string) {
                $escape = true;
                continue;
            }

            if ($char === '"') {
                $in_string = !$in_string;
                continue;
            }

            if ($in_string) {
                continue;
            }

            if ($char === '{') {
                $depth++;
            } elseif ($char === '}') {
                $depth--;
                if ($depth === 0) {
                    $json_candidate = substr($text, $start, $i - $start + 1);

                    // Verificar que es JSON válido
                    $decoded = json_decode($json_candidate, true);
                    if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
                        return $json_candidate;
                    }
                    break;
                }
            }
        }

        // Método 3: Regex más permisiva como último recurso
        if (preg_match('/\{[^{}]*(?:\{[^{}]*\}[^{}]*)*\}/s', $text, $matches)) {
            $decoded = json_decode($matches[0], true);
            if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
                return $matches[0];
            }
        }

        return null;
    }

    /**
     * Analiza el sitio con Claude (LEGACY - mantenido para compatibilidad)
     * @deprecated Usa analyze_site_with_engine en su lugar
     */
    private static function analyze_site_with_claude($api_key, $site_content) {
        // Intentar usar el nuevo sistema
        if (class_exists('Chat_IA_Engine_Manager')) {
            $engine_manager = Chat_IA_Engine_Manager::get_instance();
            $engine = $engine_manager->get_engine('claude');
            if ($engine && $engine->is_configured()) {
                return self::analyze_site_with_engine($engine, $site_content);
            }
        }

        // Fallback legacy
        $prompt = "Analiza la siguiente información de un sitio web y genera una configuración para un chatbot.";

        $response = wp_remote_post('https://api.anthropic.com/v1/messages', [
            'timeout' => 60,
            'headers' => [
                'Content-Type' => 'application/json',
                'x-api-key' => $api_key,
                'anthropic-version' => '2023-06-01',
            ],
            'body' => json_encode([
                'model' => 'claude-3-5-sonnet-latest',
                'max_tokens' => 2000,
                'messages' => [
                    ['role' => 'user', 'content' => $prompt . "\n" . json_encode($site_content)]
                ],
            ]),
        ]);

        if (is_wp_error($response)) {
            return null;
        }

        $body = json_decode(wp_remote_retrieve_body($response), true);

        if (empty($body['content'][0]['text'])) {
            return null;
        }

        $text = $body['content'][0]['text'];
        $text = preg_replace('/^```json\s*/i', '', $text);
        $text = preg_replace('/\s*```$/i', '', $text);

        return json_decode(trim($text), true);
    }

    /**
     * Obtiene la IP del cliente para rate limiting
     * Nota: Esta IP se usa solo para generar un hash temporal (transient de 1 minuto)
     * para rate limiting. No se almacena permanentemente en la base de datos.
     * La IP almacenada en sesiones está anonimizada (ver class-chat-session.php)
     *
     * @return string
     */
    private static function get_client_ip() {
        $ip_keys = [
            'HTTP_CF_CONNECTING_IP',
            'HTTP_X_FORWARDED_FOR',
            'REMOTE_ADDR',
        ];

        foreach ($ip_keys as $key) {
            if (!empty($_SERVER[$key])) {
                $ip = $_SERVER[$key];
                if (strpos($ip, ',') !== false) {
                    $ip = explode(',', $ip)[0];
                }
                return trim($ip);
            }
        }

        return '0.0.0.0';
    }
}

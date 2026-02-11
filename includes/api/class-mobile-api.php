<?php
/**
 * API REST para Apps Móviles
 *
 * Endpoints para las aplicaciones Flutter de clientes y admin
 *
 * @package ChatIAAddon
 */

if (!defined('ABSPATH')) {
    exit;
}

class Chat_IA_Mobile_API {

    /**
     * Version de contrato de API
     */
    const API_VERSION = '1.0.0';

    /**
     * Instancia singleton
     */
    private static $instance = null;

    /**
     * Namespace de la API
     */
    const API_NAMESPACE = 'chat-ia-mobile/v1';

    /**
     * Opción para el secreto de admin
     */
    const ADMIN_SECRET_OPTION = 'chat_ia_admin_site_secret';

    /**
     * Obtiene la instancia singleton
     */
    public static function get_instance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Constructor
     */
    private function __construct() {
        add_action('rest_api_init', [$this, 'register_routes']);
        // Handler para procesar carrito desde móvil en navegador
        add_action('template_redirect', [$this, 'handle_mobile_cart_redirect']);
        // Habilitar CORS para apps móviles
        add_action('rest_api_init', [$this, 'add_cors_headers'], 15);
        // Header de version de contrato
        add_filter('rest_post_dispatch', [$this, 'add_version_header'], 10, 3);
    }

    /**
     * Añade headers CORS para permitir acceso desde apps móviles
     *
     * Nota: Apps nativas no están sujetas a CORS, estos headers son para:
     * - WebViews dentro de las apps
     * - Testing desde navegadores durante desarrollo
     */
    public function add_cors_headers() {
        // Solo para nuestro namespace
        remove_filter('rest_pre_serve_request', 'rest_send_cors_headers');
        add_filter('rest_pre_serve_request', function($value) {
            // Verificar que es una petición a nuestro namespace
            $request_uri = $_SERVER['REQUEST_URI'] ?? '';
            if (strpos($request_uri, '/chat-ia-mobile/') === false) {
                return $value;
            }

            $origin = get_http_origin();
            $site_url = get_site_url();
            $allowed_origins = [
                $site_url,
                // Permitir localhost para desarrollo
                'http://localhost',
                'http://127.0.0.1',
            ];

            // Si hay un origen específico y está permitido, usarlo
            // De lo contrario, usar * para apps móviles nativas (no tienen origen)
            if ($origin && in_array(rtrim($origin, '/'), array_map('rtrim', $allowed_origins))) {
                header('Access-Control-Allow-Origin: ' . $origin);
                header('Access-Control-Allow-Credentials: true');
            } else {
                // Para apps móviles nativas que no envían Origin
                header('Access-Control-Allow-Origin: *');
                // Nota: No se puede usar Credentials con * - esto es correcto
            }

            header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
            header('Access-Control-Allow-Headers: Authorization, Content-Type, X-Requested-With, X-Mobile-App');
            header('Access-Control-Max-Age: 86400'); // Cache preflight por 24h

            return $value;
        });
    }

    /**
     * Procesa el carrito móvil cuando se abre la URL en el navegador
     * Añade los productos al carrito del navegador y redirige a checkout
     */
    public function handle_mobile_cart_redirect() {
        if (!isset($_GET['mobile_cart']) || !isset($_GET['sig'])) {
            return;
        }

        $token = sanitize_text_field($_GET['mobile_cart']);
        $signature = sanitize_text_field($_GET['sig']);

        // Verificar firma
        $expected_sig = substr(hash_hmac('sha256', $token, wp_salt('auth')), 0, 16);
        if (!hash_equals($expected_sig, $signature)) {
            wp_die(__('Enlace inválido o expirado', 'flavor-chat-ia'), __('Error', 'flavor-chat-ia'), ['response' => 403]);
        }

        // Decodificar datos (base64url: convertir -_ a +/ y añadir padding)
        $base64 = strtr($token, '-_', '+/');
        $base64_padded = str_pad($base64, strlen($base64) + (4 - strlen($base64) % 4) % 4, '=');
        $cart_data = json_decode(base64_decode($base64_padded), true);
        if (!$cart_data || !isset($cart_data['date']) || !isset($cart_data['tickets'])) {
            wp_die(__('Datos del carrito inválidos', 'flavor-chat-ia'), __('Error', 'flavor-chat-ia'), ['response' => 400]);
        }

        // Verificar que no sea muy antiguo (máx 1 hora)
        if (isset($cart_data['timestamp']) && (time() - $cart_data['timestamp']) > 3600) {
            wp_die(__('Este enlace ha expirado. Por favor, vuelve a la app y genera uno nuevo.', 'flavor-chat-ia'), __('Enlace Expirado', 'flavor-chat-ia'), ['response' => 410]);
        }

        // Asegurarse de que WooCommerce está cargado
        if (!class_exists('WooCommerce') || !function_exists('WC')) {
            wp_die(__('WooCommerce no disponible', 'flavor-chat-ia'), __('Error', 'flavor-chat-ia'), ['response' => 500]);
        }

        // Inicializar sesión y carrito
        if (!WC()->session) {
            WC()->session = new WC_Session_Handler();
            WC()->session->init();
        }
        if (!WC()->cart) {
            wc_load_cart();
        }

        // Vaciar carrito actual para evitar duplicados
        WC()->cart->empty_cart();

        $fecha = $cart_data['date'];
        $tickets = $cart_data['tickets'];
        $ticket_types = get_option('calendario_experiencias_ticket_types', []);
        $productos_addon = get_option('reservas_addon_productos', []);
        $added_count = 0;

        foreach ($tickets as $t) {
            $slug = sanitize_text_field($t['slug'] ?? '');
            $cantidad = intval($t['quantity'] ?? 0);

            if (empty($slug) || $cantidad <= 0) {
                continue;
            }

            // Buscar el producto de WooCommerce
            $product_id = $productos_addon[$slug] ?? null;

            if (!$product_id) {
                $products = wc_get_products([
                    'meta_key' => '_ticket_slug',
                    'meta_value' => $slug,
                    'limit' => 1,
                ]);
                if (!empty($products)) {
                    $product_id = $products[0]->get_id();
                }
            }

            // Crear producto dinámico si no existe
            if (!$product_id && isset($ticket_types[$slug])) {
                $ticket_info = $ticket_types[$slug];
                $product_id = $this->create_dynamic_product($slug, $ticket_info);
            }

            if (!$product_id) {
                continue;
            }

            // Añadir al carrito con metadatos
            $cart_item_data = [
                '_reserva_fecha' => $fecha,
                '_ticket_slug' => $slug,
                '_from_mobile_app' => true,
            ];

            try {
                $cart_item_key = WC()->cart->add_to_cart($product_id, $cantidad, 0, [], $cart_item_data);
                if ($cart_item_key) {
                    $added_count++;
                }
            } catch (Exception $e) {
                // Silenciar errores individuales
            }
        }

        if ($added_count > 0) {
            // Redirigir a checkout
            wp_redirect(wc_get_checkout_url());
            exit;
        } else {
            wp_die(__('No se pudieron añadir productos al carrito. Por favor, inténtalo de nuevo.', 'flavor-chat-ia'), __('Error', 'flavor-chat-ia'), ['response' => 500]);
        }
    }

    /**
     * Registra uso de la API móvil para estadísticas
     *
     * @param string $endpoint Endpoint llamado
     * @param string $platform android/ios
     * @param string $app_type admin/client
     * @param string $device_id ID del dispositivo (opcional)
     */
    private function track_mobile_usage($endpoint, $platform = 'unknown', $app_type = 'client', $device_id = '') {
        $stats = get_option('chat_ia_mobile_stats', [
            'total_requests' => 0,
            'by_platform' => ['android' => 0, 'ios' => 0, 'unknown' => 0],
            'by_app_type' => ['admin' => 0, 'client' => 0],
            'by_endpoint' => [],
            'daily_stats' => [],
            'devices' => [],
            'last_activity' => null,
        ]);

        $today = date('Y-m-d');

        // Incrementar contadores
        $stats['total_requests']++;
        $stats['by_platform'][$platform] = ($stats['by_platform'][$platform] ?? 0) + 1;
        $stats['by_app_type'][$app_type] = ($stats['by_app_type'][$app_type] ?? 0) + 1;
        $stats['by_endpoint'][$endpoint] = ($stats['by_endpoint'][$endpoint] ?? 0) + 1;
        $stats['last_activity'] = current_time('mysql');

        // Stats diarias
        if (!isset($stats['daily_stats'][$today])) {
            $stats['daily_stats'][$today] = ['requests' => 0, 'devices' => []];
        }
        $stats['daily_stats'][$today]['requests']++;

        // Registrar dispositivo único
        if (!empty($device_id)) {
            $device_key = md5($device_id);
            if (!isset($stats['devices'][$device_key])) {
                $stats['devices'][$device_key] = [
                    'platform' => $platform,
                    'app_type' => $app_type,
                    'first_seen' => $today,
                    'requests' => 0,
                ];
            }
            $stats['devices'][$device_key]['requests']++;
            $stats['devices'][$device_key]['last_seen'] = $today;

            // Dispositivos únicos del día
            if (!in_array($device_key, $stats['daily_stats'][$today]['devices'])) {
                $stats['daily_stats'][$today]['devices'][] = $device_key;
            }
        }

        // Limpiar stats antiguas (más de 30 días)
        $cutoff = date('Y-m-d', strtotime('-30 days'));
        foreach (array_keys($stats['daily_stats']) as $date) {
            if ($date < $cutoff) {
                unset($stats['daily_stats'][$date]);
            }
        }

        update_option('chat_ia_mobile_stats', $stats);
    }

    /**
     * Obtiene estadísticas de uso móvil
     *
     * @param string $periodo day/week/month
     * @return array
     */
    public static function get_mobile_stats($periodo = 'week') {
        $stats = get_option('chat_ia_mobile_stats', []);

        if (empty($stats)) {
            return [
                'total_requests' => 0,
                'periodo_requests' => 0,
                'devices_count' => 0,
                'active_devices' => 0,
                'by_platform' => ['android' => 0, 'ios' => 0],
                'by_app_type' => ['admin' => 0, 'client' => 0],
                'last_activity' => null,
            ];
        }

        $fecha_inicio = match ($periodo) {
            'day' => date('Y-m-d'),
            'week' => date('Y-m-d', strtotime('-7 days')),
            'month' => date('Y-m-d', strtotime('-30 days')),
            default => date('Y-m-d', strtotime('-7 days')),
        };

        // Calcular requests del periodo
        $periodo_requests = 0;
        $active_devices = [];
        foreach ($stats['daily_stats'] ?? [] as $date => $day_stats) {
            if ($date >= $fecha_inicio) {
                $periodo_requests += $day_stats['requests'] ?? 0;
                $active_devices = array_merge($active_devices, $day_stats['devices'] ?? []);
            }
        }

        return [
            'total_requests' => $stats['total_requests'] ?? 0,
            'periodo_requests' => $periodo_requests,
            'devices_count' => count($stats['devices'] ?? []),
            'active_devices' => count(array_unique($active_devices)),
            'by_platform' => $stats['by_platform'] ?? ['android' => 0, 'ios' => 0],
            'by_app_type' => $stats['by_app_type'] ?? ['admin' => 0, 'client' => 0],
            'last_activity' => $stats['last_activity'] ?? null,
        ];
    }

    /**
     * Registra las rutas de la API
     */
    public function register_routes() {
        // ==========================================
        // INFO DEL SITIO (PÚBLICO)
        // ==========================================
        register_rest_route(self::API_NAMESPACE, '/site-info', [
            'methods' => 'GET',
            'callback' => [$this, 'get_site_info'],
            'permission_callback' => [$this, 'public_permission_check'],
        ]);

        // Contenido inteligente del sitio (autoconfiguración)
        register_rest_route(self::API_NAMESPACE, '/site-content', [
            'methods' => 'GET',
            'callback' => [$this, 'get_site_content'],
            'permission_callback' => [$this, 'public_permission_check'],
        ]);

        // Configuración completa de la app cliente (tabs, features, info_sections)
        register_rest_route(self::API_NAMESPACE, '/client-app-config', [
            'methods' => 'GET',
            'callback' => [$this, 'get_client_app_config'],
            'permission_callback' => [$this, 'public_permission_check'],
        ]);

        // ==========================================
        // AUTENTICACIÓN
        // ==========================================
        register_rest_route(self::API_NAMESPACE, '/auth/login', [
            'methods' => 'POST',
            'callback' => [$this, 'handle_login'],
            'permission_callback' => [$this, 'public_permission_check'],
        ]);

        register_rest_route(self::API_NAMESPACE, '/auth/verify', [
            'methods' => 'GET',
            'callback' => [$this, 'verify_token'],
            'permission_callback' => [$this, 'check_auth_token'],
        ]);

        // ==========================================
        // CHAT (CLIENTES Y ADMIN)
        // ==========================================
        register_rest_route(self::API_NAMESPACE, '/chat/send', [
            'methods' => 'POST',
            'callback' => [$this, 'handle_chat_message'],
            'permission_callback' => [$this, 'public_permission_check'], // Público para clientes
        ]);

        register_rest_route(self::API_NAMESPACE, '/chat/session', [
            'methods' => 'POST',
            'callback' => [$this, 'create_chat_session'],
            'permission_callback' => [$this, 'public_permission_check'],
        ]);

        // ==========================================
        // INFORMACIÓN PÚBLICA (CLIENTES)
        // ==========================================
        register_rest_route(self::API_NAMESPACE, '/public/business-info', [
            'methods' => 'GET',
            'callback' => [$this, 'get_business_info'],
            'permission_callback' => [$this, 'public_permission_check'],
        ]);

        register_rest_route(self::API_NAMESPACE, '/public/availability', [
            'methods' => 'GET',
            'callback' => [$this, 'get_availability'],
            'permission_callback' => [$this, 'public_permission_check'],
        ]);

        register_rest_route(self::API_NAMESPACE, '/public/tickets', [
            'methods' => 'GET',
            'callback' => [$this, 'get_ticket_types'],
            'permission_callback' => [$this, 'public_permission_check'],
        ]);

        register_rest_route(self::API_NAMESPACE, '/public/experiences', [
            'methods' => 'GET',
            'callback' => [$this, 'get_experiences'],
            'permission_callback' => [$this, 'public_permission_check'],
        ]);

        register_rest_route(self::API_NAMESPACE, '/public/posts', [
            'methods' => 'GET',
            'callback' => [$this, 'get_latest_posts'],
            'permission_callback' => [$this, 'public_permission_check'],
        ]);

        register_rest_route(self::API_NAMESPACE, '/public/updates', [
            'methods' => 'GET',
            'callback' => [$this, 'get_site_updates'],
            'permission_callback' => [$this, 'public_permission_check'],
        ]);

        // ==========================================
        // RESERVAS (CLIENTES)
        // ==========================================
        register_rest_route(self::API_NAMESPACE, '/reservations/check', [
            'methods' => 'POST',
            'callback' => [$this, 'check_availability'],
            'permission_callback' => [$this, 'public_permission_check'],
        ]);

        register_rest_route(self::API_NAMESPACE, '/reservations/prepare', [
            'methods' => 'POST',
            'callback' => [$this, 'prepare_reservation'],
            'permission_callback' => [$this, 'public_permission_check'],
        ]);

        register_rest_route(self::API_NAMESPACE, '/reservations/add-to-cart', [
            'methods' => 'POST',
            'callback' => [$this, 'add_to_cart'],
            'permission_callback' => [$this, 'public_permission_check'],
        ]);

        register_rest_route(self::API_NAMESPACE, '/reservations/cart-url', [
            'methods' => 'GET',
            'callback' => [$this, 'get_cart_url'],
            'permission_callback' => [$this, 'public_permission_check'],
        ]);

        // Endpoint especial para checkout desde móvil (genera URL con datos)
        register_rest_route(self::API_NAMESPACE, '/reservations/mobile-checkout-url', [
            'methods' => 'POST',
            'callback' => [$this, 'get_mobile_checkout_url'],
            'permission_callback' => [$this, 'public_permission_check'],
        ]);

        // ==========================================
        // CLIENTE: MIS RESERVAS / BILLETERA
        // ==========================================
        register_rest_route(self::API_NAMESPACE, '/client/my-reservations', [
            'methods' => 'GET',
            'callback' => [$this, 'get_client_reservations'],
            'permission_callback' => [$this, 'check_client_verified_email'],
        ]);

        register_rest_route(self::API_NAMESPACE, '/client/reservation/(?P<code>[a-zA-Z0-9-]+)', [
            'methods' => 'GET',
            'callback' => [$this, 'get_client_reservation_by_code'],
            'permission_callback' => [$this, 'public_permission_check'],
        ]);

        register_rest_route(self::API_NAMESPACE, '/client/config', [
            'methods' => 'GET',
            'callback' => [$this, 'get_client_app_config'],
            'permission_callback' => [$this, 'public_permission_check'],
        ]);

        // ==========================================
        // VERIFICACIÓN DE EMAIL (BILLETERA)
        // ==========================================
        register_rest_route(self::API_NAMESPACE, '/client/email/send-code', [
            'methods' => 'POST',
            'callback' => [$this, 'send_verification_code'],
            'permission_callback' => [$this, 'public_permission_check'],
        ]);

        register_rest_route(self::API_NAMESPACE, '/client/email/verify-code', [
            'methods' => 'POST',
            'callback' => [$this, 'verify_email_code'],
            'permission_callback' => [$this, 'public_permission_check'],
        ]);

        register_rest_route(self::API_NAMESPACE, '/client/email/status', [
            'methods' => 'GET',
            'callback' => [$this, 'get_email_verification_status'],
            'permission_callback' => [$this, 'public_permission_check'],
        ]);

        // ==========================================
        // ADMIN: DASHBOARD
        // ==========================================
        register_rest_route(self::API_NAMESPACE, '/admin/dashboard', [
            'methods' => 'GET',
            'callback' => [$this, 'get_admin_dashboard'],
            'permission_callback' => [$this, 'check_admin_permission'],
        ]);

        register_rest_route(self::API_NAMESPACE, '/admin/reservations', [
            'methods' => 'GET',
            'callback' => [$this, 'get_admin_reservations'],
            'permission_callback' => [$this, 'check_admin_permission'],
        ]);

        register_rest_route(self::API_NAMESPACE, '/admin/reservations/(?P<id>\d+)', [
            'methods' => 'GET',
            'callback' => [$this, 'get_reservation_detail'],
            'permission_callback' => [$this, 'check_admin_permission'],
        ]);

        register_rest_route(self::API_NAMESPACE, '/admin/reservations/(?P<id>\d+)/checkin', [
            'methods' => 'POST',
            'callback' => [$this, 'do_checkin'],
            'permission_callback' => [$this, 'check_admin_permission'],
        ]);

        register_rest_route(self::API_NAMESPACE, '/admin/reservations/(?P<id>\d+)/cancel', [
            'methods' => 'POST',
            'callback' => [$this, 'cancel_reservation'],
            'permission_callback' => [$this, 'check_admin_permission'],
        ]);

        register_rest_route(self::API_NAMESPACE, '/admin/reservations/find', [
            'methods' => 'GET',
            'callback' => [$this, 'find_reservation_by_code'],
            'permission_callback' => [$this, 'check_admin_permission'],
        ]);

        register_rest_route(self::API_NAMESPACE, '/admin/stats', [
            'methods' => 'GET',
            'callback' => [$this, 'get_admin_stats'],
            'permission_callback' => [$this, 'check_admin_permission'],
        ]);

        register_rest_route(self::API_NAMESPACE, '/admin/customers', [
            'methods' => 'GET',
            'callback' => [$this, 'get_customers'],
            'permission_callback' => [$this, 'check_admin_permission'],
        ]);

        register_rest_route(self::API_NAMESPACE, '/admin/export/csv', [
            'methods' => 'POST',
            'callback' => [$this, 'export_csv'],
            'permission_callback' => [$this, 'check_admin_permission'],
        ]);

        // ==========================================
        // ADMIN: CLIENTES MANUALES
        // ==========================================
        register_rest_route(self::API_NAMESPACE, '/admin/manual-customers', [
            'methods' => 'GET',
            'callback' => [$this, 'get_manual_customers'],
            'permission_callback' => [$this, 'check_admin_permission'],
        ]);

        register_rest_route(self::API_NAMESPACE, '/admin/manual-customers', [
            'methods' => 'POST',
            'callback' => [$this, 'create_manual_customer'],
            'permission_callback' => [$this, 'check_admin_permission'],
        ]);

        register_rest_route(self::API_NAMESPACE, '/admin/manual-customers/(?P<id>\d+)', [
            'methods' => 'PUT',
            'callback' => [$this, 'update_manual_customer'],
            'permission_callback' => [$this, 'check_admin_permission'],
        ]);

        register_rest_route(self::API_NAMESPACE, '/admin/manual-customers/(?P<id>\d+)', [
            'methods' => 'DELETE',
            'callback' => [$this, 'delete_manual_customer'],
            'permission_callback' => [$this, 'check_admin_permission'],
        ]);

        register_rest_route(self::API_NAMESPACE, '/admin/customer-notes', [
            'methods' => 'POST',
            'callback' => [$this, 'save_customer_notes'],
            'permission_callback' => [$this, 'check_admin_permission'],
        ]);

        register_rest_route(self::API_NAMESPACE, '/admin/unified-customers', [
            'methods' => 'GET',
            'callback' => [$this, 'get_unified_customers'],
            'permission_callback' => [$this, 'check_admin_permission'],
        ]);

        // ==========================================
        // ADMIN: CHAT IA
        // ==========================================
        register_rest_route(self::API_NAMESPACE, '/admin/chat/send', [
            'methods' => 'POST',
            'callback' => [$this, 'handle_admin_chat'],
            'permission_callback' => [$this, 'check_admin_permission'],
        ]);

        // ESCALADO DE CHAT
        register_rest_route(self::API_NAMESPACE, '/admin/chat/escalated', [
            'methods' => 'GET',
            'callback' => [$this, 'get_escalated_chats'],
            'permission_callback' => [$this, 'check_admin_permission'],
        ]);

        register_rest_route(self::API_NAMESPACE, '/admin/chat/escalated/(?P<id>[a-zA-Z0-9_-]+)', [
            'methods' => 'GET',
            'callback' => [$this, 'get_escalated_chat_detail'],
            'permission_callback' => [$this, 'check_admin_permission'],
        ]);

        register_rest_route(self::API_NAMESPACE, '/admin/chat/escalated/(?P<id>[a-zA-Z0-9_-]+)/reply', [
            'methods' => 'POST',
            'callback' => [$this, 'reply_escalated_chat'],
            'permission_callback' => [$this, 'check_admin_permission'],
        ]);

        register_rest_route(self::API_NAMESPACE, '/admin/chat/escalated/(?P<id>[a-zA-Z0-9_-]+)/resolve', [
            'methods' => 'POST',
            'callback' => [$this, 'resolve_escalated_chat'],
            'permission_callback' => [$this, 'check_admin_permission'],
        ]);

        // ==========================================
        // NOTIFICACIONES PUSH
        // ==========================================
        register_rest_route(self::API_NAMESPACE, '/push/register', [
            'methods' => 'POST',
            'callback' => [$this, 'register_push_token'],
            'permission_callback' => [$this, 'check_auth_token'],
        ]);

        // ==========================================
        // AUTOCONFIGURACIÓN DE APPS (IA)
        // ==========================================

        // Generar configuración completa para personalizar app (con IA)
        register_rest_route(self::API_NAMESPACE, '/app-config/generate', [
            'methods' => 'GET',
            'callback' => [$this, 'generate_app_config'],
            'permission_callback' => [$this, 'public_permission_check'],
        ]);

        // Vista previa de configuración (admin)
        register_rest_route(self::API_NAMESPACE, '/app-config/preview', [
            'methods' => 'GET',
            'callback' => [$this, 'preview_app_config'],
            'permission_callback' => [$this, 'check_admin_permission'],
        ]);

        // ==========================================
        // VALIDACIÓN DE TOKEN ADMIN
        // ==========================================
        register_rest_route(self::API_NAMESPACE, '/admin/validate-site-token', [
            'methods' => 'POST',
            'callback' => [$this, 'validate_admin_site_token'],
            'permission_callback' => [$this, 'public_permission_check'],
        ]);
    }

    // ==========================================
    // SISTEMA DE TOKEN DE SEGURIDAD ADMIN
    // ==========================================

    /**
     * Obtiene o genera el secreto del sitio para app Admin
     *
     * @param bool $regenerate Si true, genera uno nuevo
     * @return string El secreto del sitio
     */
    public static function get_admin_site_secret($regenerate = false) {
        $secret = get_option(self::ADMIN_SECRET_OPTION);

        if (empty($secret) || $regenerate) {
            // Generar secreto: 32 caracteres alfanuméricos
            $secret = wp_generate_password(32, false, false);
            update_option(self::ADMIN_SECRET_OPTION, $secret);

            // Log para auditoría
            if ($regenerate) {
                error_log('Chat IA Mobile - Admin site secret regenerado');
            }
        }

        return $secret;
    }

    /**
     * Valida el token del sitio para la app Admin
     */
    public function validate_admin_site_token($request) {
        $site_token = sanitize_text_field($request->get_param('site_token'));

        if (empty($site_token)) {
            return new WP_Error('missing_token', 'Token de sitio requerido', ['status' => 400]);
        }

        $valid_secret = self::get_admin_site_secret();

        if (!hash_equals($valid_secret, $site_token)) {
            // Log intento fallido
            error_log('Chat IA Mobile - Intento de vinculación admin fallido desde IP: ' . $_SERVER['REMOTE_ADDR']);
            return new WP_Error('invalid_token', 'Token de sitio inválido', ['status' => 403]);
        }

        return rest_ensure_response([
            'success' => true,
            'message' => __('Token válido', 'flavor-chat-ia'),
            'site_name' => get_bloginfo('name'),
            'site_url' => home_url(),
        ]);
    }

    /**
     * Genera los datos del QR para la app Admin (incluye token de seguridad)
     *
     * @return array Datos para el QR
     */
    public static function get_admin_qr_data() {
        $chat_ia_settings = get_option('chat_ia_settings', []);
        $mobile_settings = $chat_ia_settings['mobile_apps'] ?? [];

        return [
            'url' => home_url(),
            'server_url' => home_url(),
            'name' => $mobile_settings['business_name'] ?? get_bloginfo('name'),
            'api' => '/wp-json/chat-ia-mobile/v1',
            'api_namespace' => '/wp-json/chat-ia-mobile/v1',
            'api_url' => home_url('/wp-json/chat-ia-mobile/v1'),
            'type' => 'admin',
            'token' => self::get_admin_site_secret(),
        ];
    }

    /**
     * Genera los datos del QR para la app Cliente (sin token)
     *
     * @return array Datos para el QR
     */
    public static function get_client_qr_data() {
        $chat_ia_settings = get_option('chat_ia_settings', []);
        $mobile_settings = $chat_ia_settings['mobile_apps'] ?? [];

        return [
            'url' => home_url(),
            'server_url' => home_url(),
            'name' => $mobile_settings['business_name'] ?? get_bloginfo('name'),
            'api' => '/wp-json/chat-ia-mobile/v1',
            'api_namespace' => '/wp-json/chat-ia-mobile/v1',
            'api_url' => home_url('/wp-json/chat-ia-mobile/v1'),
            'type' => 'client',
        ];
    }

    // ==========================================
    // INFO DEL SITIO
    // ==========================================

    /**
     * Obtiene información básica del sitio (logo, nombre)
     */
    public function get_site_info($request) {
        $logo_id = get_theme_mod('custom_logo');
        $logo_url = '';

        if ($logo_id) {
            $logo_url = wp_get_attachment_image_url($logo_id, 'full');
        }

        // Intentar obtener favicon como fallback
        $favicon_url = '';
        $site_icon_id = get_option('site_icon');
        if ($site_icon_id) {
            $favicon_url = wp_get_attachment_image_url($site_icon_id, 'full');
        }

        // Obtener configuración de apps móviles
        $chat_ia_settings = get_option('chat_ia_settings', []);
        $mobile_settings = $chat_ia_settings['mobile_apps'] ?? [];

        return rest_ensure_response([
            'success' => true,
            'name' => $mobile_settings['business_name'] ?? get_bloginfo('name'),
            'description' => get_bloginfo('description'),
            'logo_url' => $logo_url ?: $favicon_url,
            'favicon_url' => $favicon_url,
            'url' => home_url(),
            'api_url' => home_url('/wp-json/chat-ia-mobile/v1'),
            'api_version' => self::API_VERSION,
            'config' => [
                'primary_color' => $mobile_settings['primary_color'] ?? '#2196F3',
                'client_enabled' => $mobile_settings['client_enabled'] ?? true,
                'admin_enabled' => $mobile_settings['admin_enabled'] ?? true,
                'api_timeout' => intval($mobile_settings['api_timeout'] ?? 30),
                'debug_mode' => !empty($mobile_settings['debug_mode']),
            ],
        ]);
    }


    public function add_version_header($response, $server, $request) {
        $route = $request->get_route();
        if (strpos($route, '/chat-ia-mobile/v1') !== 0) {
            return $response;
        }

        if ($response instanceof WP_REST_Response) {
            $response->header('X-Flavor-API-Version', self::API_VERSION);
        }

        return $response;
    }

    /**
     * Obtiene contenido inteligente del sitio para la app cliente
     * Analiza páginas, posts, menús, widgets y genera una estructura optimizada
     */
    public function get_site_content($request) {
        $content = [
            'success' => true,
            'generated_at' => current_time('mysql'),
            'site' => $this->get_site_basic_info(),
            'sections' => [],
            'quick_links' => [],
            'social_links' => [],
            'contact' => [],
            'gallery' => [],
        ];

        // 1. Información de contacto y negocio
        $content['contact'] = $this->extract_contact_info();

        // 2. Páginas importantes (detectar automáticamente)
        $content['sections'] = $this->extract_important_pages();

        // 3. Enlaces rápidos del menú principal
        $content['quick_links'] = $this->extract_menu_links();

        // 4. Redes sociales
        $content['social_links'] = $this->extract_social_links();

        // 5. Galería de imágenes destacadas
        $content['gallery'] = $this->extract_featured_images();

        // 6. Últimas noticias/posts
        $content['news'] = $this->extract_latest_posts(5);

        // 7. Servicios/Experiencias (específico de este plugin)
        $content['services'] = $this->extract_services();

        // 8. Horarios
        $content['schedule'] = $this->extract_schedule();

        // 9. Ubicación
        $content['location'] = $this->extract_location();

        return rest_ensure_response($content);
    }

    /**
     * Info básica del sitio
     */
    private function get_site_basic_info() {
        $logo_id = get_theme_mod('custom_logo');
        $logo_url = $logo_id ? wp_get_attachment_image_url($logo_id, 'full') : '';

        return [
            'name' => get_bloginfo('name'),
            'tagline' => get_bloginfo('description'),
            'url' => home_url(),
            'logo' => $logo_url,
            'language' => get_locale(),
        ];
    }

    /**
     * Extrae información de contacto de varias fuentes
     */
    private function extract_contact_info() {
        $contact = [
            'phone' => '',
            'email' => '',
            'whatsapp' => '',
            'address' => '',
        ];

        // Prioridad 1: Configuración de Apps Móviles (admin)
        $chat_settings = get_option('chat_ia_settings', []);
        $mobile_contact = $chat_settings['mobile_apps']['contact'] ?? [];

        if (!empty($mobile_contact['phone'])) {
            $contact['phone'] = $mobile_contact['phone'];
        }
        if (!empty($mobile_contact['email'])) {
            $contact['email'] = $mobile_contact['email'];
        }
        if (!empty($mobile_contact['whatsapp'])) {
            $contact['whatsapp'] = $mobile_contact['whatsapp'];
        }

        // Prioridad 2: Buscar en opciones del tema/email admin
        if (empty($contact['email'])) {
            $contact['email'] = get_option('admin_email');
        }

        // Prioridad 3: Buscar en configuración del Chat IA (business_info)
        if (!empty($chat_settings['business_info'])) {
            $bi = $chat_settings['business_info'];
            if (empty($contact['phone'])) {
                $contact['phone'] = $bi['phone'] ?? '';
            }
            if (empty($contact['email'])) {
                $contact['email'] = $bi['email'] ?? $contact['email'];
            }
            if (empty($contact['address'])) {
                $contact['address'] = $bi['address'] ?? '';
            }
        }
        if (empty($contact['whatsapp']) && !empty($chat_settings['escalation_whatsapp'])) {
            $contact['whatsapp'] = $chat_settings['escalation_whatsapp'];
        }
        if (empty($contact['phone']) && !empty($chat_settings['escalation_phone'])) {
            $contact['phone'] = $chat_settings['escalation_phone'];
        }

        // Prioridad 4: Buscar en página de contacto (solo si faltan datos)
        if (empty($contact['phone']) || empty($contact['email'])) {
            $contact_page = get_page_by_path('contacto');
            if (!$contact_page) {
                $contact_page = get_page_by_path('contact');
            }
            if ($contact_page) {
                // Limpiar shortcodes del contenido antes de buscar
                $content = strip_shortcodes($contact_page->post_content);
                $content = wp_strip_all_tags($content);

                // Buscar teléfono
                if (empty($contact['phone']) && preg_match('/(\+?\d{2,3}[\s\-]?\d{3}[\s\-]?\d{3}[\s\-]?\d{3})/', $content, $matches)) {
                    $contact['phone'] = $matches[1];
                }
                // Buscar email
                if (empty($contact['email']) && preg_match('/([a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,})/', $content, $matches)) {
                    $contact['email'] = $matches[1];
                }
            }
        }

        return $contact;
    }

    /**
     * Detecta y extrae páginas importantes
     * Ahora usa el gestor de secciones configurables desde el admin
     */
    private function extract_important_pages() {
        $sections = [];

        // Intentar obtener secciones configuradas desde el admin
        if (class_exists('Chat_IA_Content_Sections_Manager')) {
            require_once CHAT_IA_ADDON_PATH . 'admin/class-content-sections-manager.php';
            $sections_manager = new Chat_IA_Content_Sections_Manager();
            $configured_sections = $sections_manager->get_sections(true); // Solo habilitadas

            if (!empty($configured_sections)) {
                // Usar secciones configuradas
                foreach ($configured_sections as $section) {
                    $sections[] = [
                        'id' => $section['id'],
                        'title' => $section['title'],
                        'summary' => $section['summary'] ?? '',
                        'url' => $section['url'] ?? '',
                        'image' => $section['image_url'] ?? '',
                        'icon' => $this->map_icon_to_material($section['icon'] ?? 'info'),
                        'priority' => $section['order'] ?? 999,
                    ];
                }

                return $sections;
            }
        }

        // FALLBACK: Si no hay secciones configuradas, usar detección automática (código legacy)
        // Patrones de páginas importantes
        $important_slugs = [
            'sobre-nosotros' => ['about', 'sobre-nosotros', 'quienes-somos', 'about-us', 'nosotros'],
            'servicios' => ['servicios', 'services', 'experiencias', 'actividades', 'what-we-do'],
            'contacto' => ['contacto', 'contact', 'contacta', 'contact-us'],
            'horarios' => ['horarios', 'horario', 'schedule', 'hours', 'opening-hours'],
            'tarifas' => ['tarifas', 'precios', 'prices', 'pricing', 'rates'],
            'como-llegar' => ['como-llegar', 'ubicacion', 'location', 'directions', 'where-we-are'],
            'faq' => ['faq', 'preguntas-frecuentes', 'faqs', 'ayuda', 'help'],
            'galeria' => ['galeria', 'gallery', 'fotos', 'photos', 'imagenes'],
            'blog' => ['blog', 'noticias', 'news', 'novedades'],
        ];

        $icons = [
            'sobre-nosotros' => 'info',
            'servicios' => 'star',
            'contacto' => 'phone',
            'horarios' => 'schedule',
            'tarifas' => 'euro',
            'como-llegar' => 'location_on',
            'faq' => 'help',
            'galeria' => 'photo_library',
            'blog' => 'article',
        ];

        foreach ($important_slugs as $key => $slugs) {
            foreach ($slugs as $slug) {
                $page = get_page_by_path($slug);
                if ($page && $page->post_status === 'publish') {
                    // Extraer resumen inteligente (generate_smart_summary ya limpia shortcodes)
                    $summary = $this->generate_smart_summary($page->post_content, 200);

                    // Obtener imagen destacada
                    $thumbnail = get_the_post_thumbnail_url($page->ID, 'medium');

                    // Si no hay resumen útil, intentar con excerpt o descripción
                    if (empty($summary)) {
                        $summary = $page->post_excerpt;
                        if (empty($summary)) {
                            $summary = 'Ver más información en la web';
                        }
                    }

                    $sections[] = [
                        'id' => $key,
                        'title' => $page->post_title,
                        'summary' => $summary,
                        'content' => $this->clean_content($page->post_content),
                        'url' => get_permalink($page->ID),
                        'image' => $thumbnail ?: '',
                        'icon' => $icons[$key] ?? 'article',
                        'priority' => array_search($key, array_keys($important_slugs)),
                    ];
                    break;
                }
            }
        }

        // Ordenar por prioridad
        usort($sections, fn($a, $b) => $a['priority'] - $b['priority']);

        return $sections;
    }

    /**
     * Mapea iconos de Dashicons a Material Icons para Flutter
     */
    private function map_icon_to_material($dashicon_key) {
        $icon_map = [
            'info' => 'info',
            'calendar' => 'calendar_today',
            'tickets' => 'confirmation_number',
            'location' => 'location_on',
            'phone' => 'phone',
            'email' => 'email',
            'star' => 'star',
            'food' => 'restaurant',
            'camera' => 'camera_alt',
            'groups' => 'group',
        ];

        return $icon_map[$dashicon_key] ?? 'article';
    }

    /**
     * Genera un resumen inteligente del contenido
     */
    private function generate_smart_summary($content, $max_length = 200) {
        // Limpiar shortcodes primero
        $content = strip_shortcodes($content);
        // Eliminar bloques de Gutenberg vacíos/comentarios
        $content = preg_replace('/<!--.*?-->/s', '', $content);
        // Limpiar HTML
        $content = wp_strip_all_tags($content);
        // Normalizar espacios
        $content = preg_replace('/\s+/', ' ', trim($content));

        if (empty($content)) {
            return '';
        }

        if (strlen($content) <= $max_length) {
            return $content;
        }

        // Intentar cortar en punto o coma
        $truncated = substr($content, 0, $max_length);
        $last_period = strrpos($truncated, '.');
        $last_comma = strrpos($truncated, ',');

        if ($last_period !== false && $last_period > $max_length * 0.5) {
            return substr($truncated, 0, $last_period + 1);
        } elseif ($last_comma !== false && $last_comma > $max_length * 0.7) {
            return substr($truncated, 0, $last_comma) . '...';
        }

        // Cortar en última palabra completa
        $truncated = substr($content, 0, $max_length);
        $last_space = strrpos($truncated, ' ');
        if ($last_space === false) {
            return $truncated . '...';
        }
        return substr($truncated, 0, $last_space) . '...';
    }

    /**
     * Limpia el contenido HTML para la app (texto plano)
     */
    private function clean_content($content) {
        // Eliminar shortcodes completamente
        $content = strip_shortcodes($content);

        // Procesar bloques de Gutenberg para obtener HTML
        $content = do_blocks($content);

        // Eliminar comentarios HTML y bloques de Gutenberg vacíos
        $content = preg_replace('/<!--.*?-->/s', '', $content);

        // Eliminar estilos inline y scripts
        $content = preg_replace('/<style[^>]*>.*?<\/style>/is', '', $content);
        $content = preg_replace('/<script[^>]*>.*?<\/script>/is', '', $content);

        // Convertir a texto plano limpio
        $content = wp_strip_all_tags($content);

        // Normalizar espacios y saltos de línea
        $content = preg_replace('/\s+/', ' ', $content);

        return trim($content);
    }

    /**
     * Extrae enlaces del menú principal
     */
    private function extract_menu_links() {
        $links = [];

        // Buscar menú principal
        $locations = get_nav_menu_locations();
        $menu_id = $locations['primary'] ?? $locations['main'] ?? $locations['main-menu'] ?? null;

        if (!$menu_id) {
            // Buscar cualquier menú
            $menus = wp_get_nav_menus();
            if (!empty($menus)) {
                $menu_id = $menus[0]->term_id;
            }
        }

        if ($menu_id) {
            $items = wp_get_nav_menu_items($menu_id);
            if ($items) {
                foreach ($items as $item) {
                    if ($item->menu_item_parent == 0) { // Solo items de primer nivel
                        $links[] = [
                            'title' => $item->title,
                            'url' => $item->url,
                            'target' => $item->target ?: '_self',
                        ];
                    }
                }
            }
        }

        return array_slice($links, 0, 8); // Máximo 8 enlaces
    }

    /**
     * Extrae enlaces de redes sociales
     */
    private function extract_social_links() {
        $social = [];

        // Patrones de redes sociales
        $patterns = [
            'facebook' => ['facebook.com', 'fb.com'],
            'instagram' => ['instagram.com'],
            'twitter' => ['twitter.com', 'x.com'],
            'youtube' => ['youtube.com', 'youtu.be'],
            'tiktok' => ['tiktok.com'],
            'linkedin' => ['linkedin.com'],
            'whatsapp' => ['wa.me', 'whatsapp.com'],
            'telegram' => ['t.me', 'telegram.me'],
        ];

        // Buscar en widgets
        $widget_content = '';
        $sidebars = wp_get_sidebars_widgets();
        foreach ($sidebars as $widgets) {
            if (is_array($widgets)) {
                foreach ($widgets as $widget_id) {
                    $widget_content .= $this->get_widget_content($widget_id);
                }
            }
        }

        // Buscar en footer y páginas
        $pages_to_check = ['contacto', 'contact', 'footer'];
        foreach ($pages_to_check as $slug) {
            $page = get_page_by_path($slug);
            if ($page) {
                $widget_content .= $page->post_content;
            }
        }

        // Extraer URLs de redes sociales
        preg_match_all('/(https?:\/\/[^\s"\'<>]+)/i', $widget_content, $matches);

        if (!empty($matches[1])) {
            foreach ($matches[1] as $url) {
                foreach ($patterns as $network => $domains) {
                    foreach ($domains as $domain) {
                        if (stripos($url, $domain) !== false && !isset($social[$network])) {
                            $social[$network] = [
                                'network' => $network,
                                'url' => $url,
                                'icon' => $network,
                            ];
                            break 2;
                        }
                    }
                }
            }
        }

        return array_values($social);
    }

    /**
     * Obtiene contenido de un widget
     */
    private function get_widget_content($widget_id) {
        try {
            global $wp_registered_widgets;
            if (isset($wp_registered_widgets[$widget_id]) && is_array($wp_registered_widgets[$widget_id]['callback'])) {
                $callback = $wp_registered_widgets[$widget_id]['callback'];
                // Verificar que el callback es una clase válida
                if (is_array($callback) && isset($callback[0]) && is_object($callback[0])) {
                    $widget_class = get_class($callback[0]);
                    ob_start();
                    the_widget($widget_class);
                    return ob_get_clean();
                }
            }
        } catch (Exception $e) {
            // Silenciar errores de widgets
        }
        return '';
    }

    /**
     * Extrae imágenes destacadas del sitio
     */
    private function extract_featured_images() {
        $images = [];

        // Imágenes de páginas importantes
        $pages = get_pages(['number' => 20, 'sort_column' => 'menu_order']);
        foreach ($pages as $page) {
            $thumb = get_the_post_thumbnail_url($page->ID, 'large');
            if ($thumb) {
                $images[] = [
                    'url' => $thumb,
                    'title' => $page->post_title,
                    'page_url' => get_permalink($page->ID),
                ];
            }
        }

        // Imágenes de posts recientes
        $posts = get_posts(['numberposts' => 10, 'post_status' => 'publish']);
        foreach ($posts as $post) {
            $thumb = get_the_post_thumbnail_url($post->ID, 'large');
            if ($thumb) {
                $images[] = [
                    'url' => $thumb,
                    'title' => $post->post_title,
                    'page_url' => get_permalink($post->ID),
                ];
            }
        }

        // Buscar galería de medios
        $attachments = get_posts([
            'post_type' => 'attachment',
            'post_mime_type' => 'image',
            'numberposts' => 20,
            'orderby' => 'date',
            'order' => 'DESC',
        ]);

        foreach ($attachments as $attachment) {
            $thumb = wp_get_attachment_image_url($attachment->ID, 'large');
            if ($thumb && !in_array($thumb, array_column($images, 'url'))) {
                $images[] = [
                    'url' => $thumb,
                    'title' => $attachment->post_title ?: '',
                    'page_url' => '',
                ];
            }
        }

        // Eliminar duplicados y limitar
        $unique = [];
        foreach ($images as $img) {
            if (!in_array($img['url'], array_column($unique, 'url'))) {
                $unique[] = $img;
            }
            if (count($unique) >= 12) break;
        }

        return $unique;
    }

    /**
     * Extrae posts recientes
     */
    private function extract_latest_posts($limit = 5) {
        $posts = get_posts([
            'numberposts' => $limit,
            'post_status' => 'publish',
            'orderby' => 'date',
            'order' => 'DESC',
        ]);

        $news = [];
        foreach ($posts as $post) {
            $news[] = [
                'id' => $post->ID,
                'title' => $post->post_title,
                'excerpt' => $this->generate_smart_summary(wp_strip_all_tags($post->post_content), 150),
                'date' => get_the_date('Y-m-d', $post),
                'image' => get_the_post_thumbnail_url($post->ID, 'medium') ?: '',
                'url' => get_permalink($post->ID),
                'categories' => wp_get_post_categories($post->ID, ['fields' => 'names']),
            ];
        }

        return $news;
    }

    /**
     * Extrae servicios/experiencias del plugin
     * Solo muestra tickets con fechas disponibles en los próximos 2 meses
     */
    private function extract_services() {
        global $wpdb;
        $services = [];

        // Obtener tipos de tickets
        $ticket_types = get_option('calendario_experiencias_ticket_types', []);

        // Calcular rango de fechas (próximos 2 meses)
        $fecha_inicio = date('Y-m-d');
        $fecha_fin = date('Y-m-d', strtotime('+2 months'));

        // Obtener días del calendario con disponibilidad
        $tabla_calendario = $wpdb->prefix . 'calendario_experiencias';
        $tabla_existe = Flavor_Chat_Helpers::tabla_existe($tabla_calendario);

        if (!$tabla_existe) {
            // Si no hay tabla, devolver todos los tickets sin filtro de fecha
            foreach ($ticket_types as $slug => $ticket) {
                if (!empty($ticket['name'])) {
                    $services[] = [
                        'id' => $slug,
                        'name' => $ticket['name'],
                        'description' => $ticket['description'] ?? '',
                        'price' => floatval($ticket['price'] ?? 0),
                        'duration' => $ticket['duration'] ?? '',
                        'image' => $ticket['image'] ?? '',
                        'bookable' => true,
                        'next_dates' => [],
                    ];
                }
            }
            return $services;
        }

        // Obtener días disponibles en el rango
        $dias = $wpdb->get_results($wpdb->prepare(
            "SELECT dia, estado, tickets_vinculados
             FROM {$tabla_calendario}
             WHERE dia BETWEEN %s AND %s
             AND estado IN ('disponible', 'abierto')
             ORDER BY dia ASC",
            $fecha_inicio,
            $fecha_fin
        ), ARRAY_A);

        // Agrupar fechas por ticket
        $tickets_con_fechas = [];
        foreach ($dias as $dia) {
            $tickets_vinculados = !empty($dia['tickets_vinculados'])
                ? json_decode($dia['tickets_vinculados'], true)
                : [];

            if (is_array($tickets_vinculados)) {
                foreach ($tickets_vinculados as $ticket_slug) {
                    if (!isset($tickets_con_fechas[$ticket_slug])) {
                        $tickets_con_fechas[$ticket_slug] = [];
                    }
                    $tickets_con_fechas[$ticket_slug][] = $dia['dia'];
                }
            }
        }

        // Crear array de servicios solo para tickets con fechas disponibles
        foreach ($ticket_types as $slug => $ticket) {
            if (!empty($ticket['name']) && isset($tickets_con_fechas[$slug])) {
                // Limitar a máximo 5 próximas fechas
                $proximas_fechas = array_slice($tickets_con_fechas[$slug], 0, 5);

                $services[] = [
                    'id' => $slug,
                    'name' => $ticket['name'],
                    'description' => $ticket['description'] ?? '',
                    'price' => floatval($ticket['price'] ?? 0),
                    'duration' => $ticket['duration'] ?? '',
                    'image' => $ticket['image'] ?? '',
                    'capacity' => $ticket['capacity'] ?? null,
                    'bookable' => true,
                    'next_dates' => $proximas_fechas,
                    'total_available_days' => count($tickets_con_fechas[$slug]),
                ];
            }
        }

        return $services;
    }

    /**
     * Extrae horarios
     */
    private function extract_schedule() {
        $schedule = [
            'text' => '',
            'days' => [],
        ];

        // Prioridad 1: Configuración de Apps Móviles
        $chat_settings = get_option('chat_ia_settings', []);
        $mobile_contact = $chat_settings['mobile_apps']['contact'] ?? [];
        if (!empty($mobile_contact['schedule'])) {
            $schedule['text'] = $mobile_contact['schedule'];
            return $schedule;
        }

        // Prioridad 2: Buscar en configuración del Chat IA (business_info)
        if (!empty($chat_settings['business_info']['schedule'])) {
            $schedule['text'] = $chat_settings['business_info']['schedule'];
        }
        if (empty($schedule['text']) && !empty($chat_settings['escalation_hours'])) {
            $schedule['text'] = $chat_settings['escalation_hours'];
        }

        // Prioridad 3: Buscar en página de horarios
        if (empty($schedule['text'])) {
            $schedule_page = get_page_by_path('horarios');
            if (!$schedule_page) {
                $schedule_page = get_page_by_path('horario');
            }
            if ($schedule_page) {
                $schedule['text'] = $this->generate_smart_summary(
                    wp_strip_all_tags(strip_shortcodes($schedule_page->post_content)),
                    300
                );
            }
        }

        return $schedule;
    }

    /**
     * Extrae información de ubicación
     */
    private function extract_location() {
        $location = [
            'address' => '',
            'coordinates' => null,
            'map_url' => '',
            'directions_url' => '',
        ];

        // Prioridad 1: Configuración de Apps Móviles
        $chat_settings = get_option('chat_ia_settings', []);
        $mobile_location = $chat_settings['mobile_apps']['location'] ?? [];

        if (!empty($mobile_location['address'])) {
            $location['address'] = $mobile_location['address'];
        }

        // Coordenadas GPS configuradas
        if (!empty($mobile_location['lat']) && !empty($mobile_location['lng'])) {
            $lat = floatval($mobile_location['lat']);
            $lng = floatval($mobile_location['lng']);
            if ($lat >= -90 && $lat <= 90 && $lng >= -180 && $lng <= 180) {
                $location['coordinates'] = ['lat' => $lat, 'lng' => $lng];
                $location['map_url'] = "https://www.google.com/maps?q={$lat},{$lng}";
                $location['directions_url'] = "https://www.google.com/maps/dir/?api=1&destination={$lat},{$lng}";
            }
        }

        // URL de mapa configurada manualmente
        if (!empty($mobile_location['map_url'])) {
            $location['map_url'] = $mobile_location['map_url'];
            // Usar también como directions si no hay coordenadas
            if (empty($location['directions_url'])) {
                $location['directions_url'] = $mobile_location['map_url'];
            }
        }

        // Si ya tenemos todos los datos, retornamos
        if (!empty($location['address']) && !empty($location['map_url'])) {
            return $location;
        }

        // Prioridad 2: Buscar en configuración del Chat IA
        if (empty($location['address']) && !empty($chat_settings['business_info']['address'])) {
            $location['address'] = $chat_settings['business_info']['address'];
        }

        // Prioridad 3: Buscar coordenadas en contenido de páginas
        if (empty($location['coordinates'])) {
            $location_pages = ['como-llegar', 'ubicacion', 'location', 'contacto'];
            foreach ($location_pages as $slug) {
                $page = get_page_by_path($slug);
                if ($page) {
                    $content = strip_shortcodes($page->post_content);

                    // Buscar coordenadas en formato común
                    if (preg_match('/(-?\d+\.\d+)[,\s]+(-?\d+\.\d+)/', $content, $matches)) {
                        $lat = floatval($matches[1]);
                        $lng = floatval($matches[2]);
                        if ($lat >= -90 && $lat <= 90 && $lng >= -180 && $lng <= 180) {
                            $location['coordinates'] = ['lat' => $lat, 'lng' => $lng];
                            if (empty($location['map_url'])) {
                                $location['map_url'] = "https://www.google.com/maps?q={$lat},{$lng}";
                            }
                            if (empty($location['directions_url'])) {
                                $location['directions_url'] = "https://www.google.com/maps/dir/?api=1&destination={$lat},{$lng}";
                            }
                        }
                    }

                    // Buscar dirección si no la tenemos
                    if (empty($location['address'])) {
                        // Buscar dirección típica española
                        if (preg_match('/(?:C\/|Calle|Avda\.|Avenida|Plaza|Paseo)[^<\n]{5,100}/i', $content, $matches)) {
                            $location['address'] = trim(strip_tags($matches[0]));
                        }
                    }
                }
            }
        }

        // Generar URL de direcciones si tenemos dirección pero no coordenadas
        if (!empty($location['address']) && empty($location['directions_url'])) {
            $encoded_address = urlencode($location['address']);
            $location['map_url'] = "https://www.google.com/maps/search/{$encoded_address}";
            $location['directions_url'] = "https://www.google.com/maps/dir/?api=1&destination={$encoded_address}";
        }

        return $location;
    }

    // ==========================================
    // AUTENTICACIÓN
    // ==========================================

    /**
     * Maneja el login y devuelve un token JWT
     */
    public function handle_login($request) {
        $username = sanitize_text_field($request->get_param('username'));
        $password = $request->get_param('password');
        $app_type = sanitize_text_field($request->get_param('app_type')); // 'client' o 'admin'

        if (empty($username) || empty($password)) {
            return new WP_Error('missing_credentials', 'Usuario y contraseña requeridos', ['status' => 400]);
        }

        $user = wp_authenticate($username, $password);

        if (is_wp_error($user)) {
            return new WP_Error('invalid_credentials', 'Credenciales inválidas', ['status' => 401]);
        }

        // Para admin, verificar que tiene permisos
        if ($app_type === 'admin' && !user_can($user, 'manage_options')) {
            return new WP_Error('insufficient_permissions', 'No tienes permisos de administrador', ['status' => 403]);
        }

        // Generar token
        $token = $this->generate_auth_token($user->ID, $app_type);

        return [
            'success' => true,
            'token' => $token,
            'user' => [
                'id' => $user->ID,
                'name' => $user->display_name,
                'email' => $user->user_email,
                'is_admin' => user_can($user, 'manage_options'),
            ],
            'expires_at' => time() + (7 * DAY_IN_SECONDS),
        ];
    }

    /**
     * Genera un token de autenticación
     */
    private function generate_auth_token($user_id, $app_type = 'client') {
        $secret_key = defined('AUTH_KEY') ? AUTH_KEY : wp_generate_password(64, true, true);
        $payload = [
            'user_id' => $user_id,
            'app_type' => $app_type,
            'issued_at' => time(),
            'expires_at' => time() + (7 * DAY_IN_SECONDS),
        ];

        $payload_json = json_encode($payload);
        $signature = hash_hmac('sha256', $payload_json, $secret_key);

        return base64_encode($payload_json) . '.' . $signature;
    }

    /**
     * Verifica el token de autenticación
     */
    public function check_auth_token($request) {
        $auth_header = $request->get_header('Authorization');

        if (empty($auth_header) || strpos($auth_header, 'Bearer ') !== 0) {
            return false;
        }

        $token = substr($auth_header, 7);
        return $this->validate_token($token);
    }

    /**
     * Valida un token
     */
    private function validate_token($token) {
        $parts = explode('.', $token);
        if (count($parts) !== 2) {
            return false;
        }

        $payload_json = base64_decode($parts[0]);
        $signature = $parts[1];

        $secret_key = defined('AUTH_KEY') ? AUTH_KEY : '';
        $expected_signature = hash_hmac('sha256', $payload_json, $secret_key);

        if (!hash_equals($expected_signature, $signature)) {
            return false;
        }

        $payload = json_decode($payload_json, true);

        if (!$payload || $payload['expires_at'] < time()) {
            return false;
        }

        // Establecer usuario actual
        wp_set_current_user($payload['user_id']);

        return true;
    }

    /**
     * Verifica permisos de admin
     */
    public function check_admin_permission($request) {
        error_log("[Mobile API] check_admin_permission - verificando token...");

        if (!$this->check_auth_token($request)) {
            error_log("[Mobile API] check_admin_permission - TOKEN INVÁLIDO");
            return new WP_Error(
                'rest_unauthorized',
                'Token de autenticación inválido o expirado',
                ['status' => 401]
            );
        }

        $user = wp_get_current_user();
        error_log("[Mobile API] check_admin_permission - Usuario: " . $user->user_login . " (ID: " . $user->ID . ")");

        if (!current_user_can('manage_options')) {
            error_log("[Mobile API] check_admin_permission - SIN PERMISOS ADMIN");
            return new WP_Error(
                'rest_forbidden',
                'No tienes permisos de administrador',
                ['status' => 403]
            );
        }

        error_log("[Mobile API] check_admin_permission - OK");
        return true;
    }

    public function public_permission_check($request) {
        $method = strtoupper($request->get_method());
        $tipo = in_array($method, ['POST', 'PUT', 'DELETE'], true) ? 'post' : 'get';
        return Flavor_API_Rate_Limiter::check_rate_limit($tipo);
    }

    /**
     * Verifica el token actual
     */
    public function verify_token($request) {
        $user = wp_get_current_user();

        return [
            'success' => true,
            'user' => [
                'id' => $user->ID,
                'name' => $user->display_name,
                'email' => $user->user_email,
                'is_admin' => current_user_can('manage_options'),
            ],
        ];
    }

    // ==========================================
    // CHAT
    // ==========================================

    /**
     * Crea una nueva sesión de chat
     */
    public function create_chat_session($request) {
        $language = sanitize_text_field($request->get_param('language')) ?: 'es';
        $device_id = sanitize_text_field($request->get_param('device_id'));
        $platform = sanitize_text_field($request->get_param('platform')) ?: 'unknown';

        // Tracking de uso
        $this->track_mobile_usage('chat/session', $platform, 'client', $device_id);

        // Crear sesión (el constructor ya guarda automáticamente)
        if (class_exists('Chat_IA_Session')) {
            $session = new Chat_IA_Session(null, $language);

            return [
                'success' => true,
                'session_id' => $session->get_id(),
                'language' => $language,
            ];
        }

        return new WP_Error('session_error', 'No se pudo crear la sesión', ['status' => 500]);
    }

    /**
     * Maneja mensajes del chat (clientes)
     */
    public function handle_chat_message($request) {
        $message = sanitize_text_field($request->get_param('message'));
        $session_id = sanitize_text_field($request->get_param('session_id'));
        $language = sanitize_text_field($request->get_param('language')) ?: 'es';
        $device_id = sanitize_text_field($request->get_param('device_id'));
        $platform = sanitize_text_field($request->get_param('platform')) ?: 'unknown';

        // Tracking de uso
        $this->track_mobile_usage('chat/message', $platform, 'client', $device_id);

        if (empty($message)) {
            return new WP_Error('empty_message', 'Mensaje vacío', ['status' => 400]);
        }

        // Obtener o crear sesión
        if (class_exists('Chat_IA_Session')) {
            $session = new Chat_IA_Session($session_id);
            $session->set_language($language);
        } else {
            return new WP_Error('chat_unavailable', 'Chat no disponible', ['status' => 500]);
        }

        // Procesar con el motor de IA
        if (class_exists('Chat_IA_Claude_Engine')) {
            $engine = Chat_IA_Claude_Engine::get_instance();
            $response = $engine->send_message($session, $message);

            if ($response['success']) {
                return [
                    'success' => true,
                    'response' => $response['response'],
                    'session_id' => $session->get_id(),
                    'has_action' => !empty($response['action']),
                    'action' => $response['action'] ?? null,
                ];
            } else {
                return new WP_Error('chat_error', $response['error'] ?? 'Error del chat', ['status' => 500]);
            }
        }

        return new WP_Error('engine_unavailable', 'Motor de IA no disponible', ['status' => 500]);
    }

    /**
     * Maneja mensajes del chat admin
     */
    public function handle_admin_chat($request) {
        $message = sanitize_text_field($request->get_param('message'));
        $session_id = sanitize_text_field($request->get_param('session_id'));
        $device_id = sanitize_text_field($request->get_param('device_id'));
        $platform = sanitize_text_field($request->get_param('platform')) ?: 'unknown';

        // Tracking de uso
        $this->track_mobile_usage('admin/chat', $platform, 'admin', $device_id);

        if (empty($message)) {
            return new WP_Error('empty_message', 'Mensaje vacío', ['status' => 400]);
        }

        // Usar el sistema de admin assistant
        if (class_exists('Chat_IA_Admin_Assistant')) {
            $assistant = Chat_IA_Admin_Assistant::get_instance();
            $response = $assistant->process_message($message, $session_id);

            return [
                'success' => true,
                'response' => $response['response'] ?? '',
                'data' => $response['data'] ?? null,
                'session_id' => $session_id,
            ];
        }

        return new WP_Error('admin_chat_unavailable', 'Chat admin no disponible', ['status' => 500]);
    }

    // ==========================================
    // INFORMACIÓN PÚBLICA
    // ==========================================

    /**
     * Obtiene información del negocio
     */
    public function get_business_info($request) {
        $language = sanitize_text_field($request->get_param('language')) ?: 'es';
        $settings = get_option('chat_ia_settings', []);
        $business_info = $settings['business_info'] ?? [];
        $social_info = $settings['social_media'] ?? $business_info['social'] ?? [];

        return [
            'success' => true,
            'business' => [
                'name' => get_bloginfo('name'),
                'description' => get_bloginfo('description'),
                'address' => $business_info['address'] ?? '',
                'phone' => $business_info['phone'] ?? '',
                'email' => $business_info['email'] ?? get_bloginfo('admin_email'),
                'whatsapp' => $business_info['whatsapp'] ?? '',
                'schedule' => $business_info['schedule'] ?? '',
                'maps_url' => !empty($business_info['address'])
                    ? 'https://www.google.com/maps/search/?api=1&query=' . urlencode($business_info['address'])
                    : '',
                'website' => home_url(),
                'social' => [
                    'facebook' => $social_info['facebook'] ?? '',
                    'instagram' => $social_info['instagram'] ?? '',
                    'twitter' => $social_info['twitter'] ?? '',
                    'youtube' => $social_info['youtube'] ?? '',
                ],
            ],
        ];
    }

    /**
     * Obtiene disponibilidad
     */
    public function get_availability($request) {
        $fecha_inicio = sanitize_text_field($request->get_param('from')) ?: date('Y-m-d');
        $fecha_fin = sanitize_text_field($request->get_param('to')) ?: date('Y-m-d', strtotime('+30 days'));

        $calendario_dias = get_option('calendario_experiencias_dias', []);
        $estados_config = get_option('calendario_experiencias_estados', []);

        $disponibilidad = [];

        foreach ($calendario_dias as $fecha => $estado) {
            if ($fecha >= $fecha_inicio && $fecha <= $fecha_fin && $estado !== 'cerrado') {
                $estado_info = $estados_config[$estado] ?? [];
                $disponibilidad[] = [
                    'date' => $fecha,
                    'state' => $estado,
                    'state_name' => $estado_info['nombre'] ?? $estado_info['title'] ?? $estado,
                    'schedule' => $estado_info['horario'] ?? '',
                    'color' => $estado_info['color'] ?? '#4CAF50',
                ];
            }
        }

        return [
            'success' => true,
            'from' => $fecha_inicio,
            'to' => $fecha_fin,
            'availability' => $disponibilidad,
        ];
    }

    /**
     * Obtiene tipos de tickets
     * Si se pasa el parámetro 'state', filtra los tickets según el mapeo estado-tickets
     */
    public function get_ticket_types($request) {
        $state = sanitize_text_field($request->get_param('state'));
        $ticket_types = get_option('calendario_experiencias_ticket_types', []);
        $state_mapping = get_option('calendario_experiencias_state_ticket_mapping', []);

        // Si hay un estado específico, obtener solo los tickets mapeados a ese estado
        $allowed_tickets = null;
        if (!empty($state) && !empty($state_mapping[$state])) {
            $allowed_tickets = $state_mapping[$state];
            // Puede ser un array o string separado por comas
            if (is_string($allowed_tickets)) {
                $allowed_tickets = array_map('trim', explode(',', $allowed_tickets));
            }
        }

        $tickets = [];
        foreach ($ticket_types as $slug => $ticket) {
            // Excluir bonos especiales
            $tipo = $ticket['tipo'] ?? 'normal';
            if (in_array($tipo, ['bono_regalo', 'abono_temporada'])) {
                continue;
            }

            // Si hay filtro de estado, verificar que el ticket esté permitido
            if ($allowed_tickets !== null && !in_array($slug, $allowed_tickets)) {
                continue;
            }

            // Obtener dependencias del ticket
            $depends_on = [];
            $requires = $ticket['requires'] ?? $ticket['requiere'] ?? $ticket['depends_on'] ?? '';
            if (!empty($requires)) {
                if (is_array($requires)) {
                    $depends_on = $requires;
                } else {
                    $depends_on = array_map('trim', explode(',', $requires));
                    $depends_on = array_filter($depends_on);
                }
            }

            $tickets[] = [
                'slug' => $slug,
                'name' => $ticket['name'] ?? $ticket['nombre'] ?? $slug,
                'description' => $ticket['descripcion'] ?? '',
                'price' => floatval($ticket['precio'] ?? 0),
                'capacity' => intval($ticket['plazas'] ?? 0),
                'duration' => $ticket['duracion'] ?? '',
                'type' => $tipo,
                'depends_on' => $depends_on,
                'min_quantity' => intval($ticket['min_cantidad'] ?? $ticket['min_quantity'] ?? 0),
                'max_quantity' => intval($ticket['max_cantidad'] ?? $ticket['max_quantity'] ?? 10),
            ];
        }

        return [
            'success' => true,
            'state' => $state ?: null,
            'tickets' => $tickets,
        ];
    }

    /**
     * Obtiene últimas publicaciones del blog
     */
    public function get_latest_posts($request) {
        $limit = intval($request->get_param('limit')) ?: 5;

        $args = [
            'post_type' => 'post',
            'post_status' => 'publish',
            'posts_per_page' => $limit,
            'orderby' => 'date',
            'order' => 'DESC',
        ];

        $query = new WP_Query($args);
        $posts = [];

        if ($query->have_posts()) {
            while ($query->have_posts()) {
                $query->the_post();
                $post_id = get_the_ID();
                $featured_image = get_the_post_thumbnail_url($post_id, 'medium') ?: '';

                $posts[] = [
                    'id' => $post_id,
                    'title' => get_the_title(),
                    'excerpt' => wp_trim_words(get_the_excerpt(), 20, '...'),
                    'url' => get_permalink(),
                    'image_url' => $featured_image,
                    'date' => get_the_date('Y-m-d'),
                    'author' => get_the_author(),
                    'categories' => wp_list_pluck(get_the_category(), 'name'),
                ];
            }
            wp_reset_postdata();
        }

        return [
            'success' => true,
            'posts' => $posts,
        ];
    }

    /**
     * Obtiene novedades/actualizaciones del sitio
     */
    public function get_site_updates($request) {
        $limit = intval($request->get_param('limit')) ?: 5;
        $updates = [];

        // Buscar en opciones personalizadas del plugin
        $custom_updates = get_option('chat_ia_site_updates', []);

        if (!empty($custom_updates)) {
            foreach (array_slice($custom_updates, 0, $limit) as $update) {
                $updates[] = [
                    'id' => $update['id'] ?? 0,
                    'title' => $update['title'] ?? '',
                    'summary' => $update['summary'] ?? '',
                    'type' => $update['type'] ?? 'news',
                    'date' => $update['date'] ?? date('Y-m-d'),
                    'url' => $update['url'] ?? null,
                    'image_url' => $update['image_url'] ?? null,
                ];
            }
        }

        // Si no hay updates personalizados, usar últimos posts como fallback
        if (empty($updates)) {
            $args = [
                'post_type' => 'post',
                'post_status' => 'publish',
                'posts_per_page' => $limit,
                'orderby' => 'date',
                'order' => 'DESC',
            ];

            $query = new WP_Query($args);

            if ($query->have_posts()) {
                while ($query->have_posts()) {
                    $query->the_post();
                    $updates[] = [
                        'id' => get_the_ID(),
                        'title' => get_the_title(),
                        'summary' => wp_trim_words(get_the_excerpt(), 15, '...'),
                        'type' => 'news',
                        'date' => get_the_date('Y-m-d'),
                        'url' => get_permalink(),
                        'image_url' => get_the_post_thumbnail_url(get_the_ID(), 'thumbnail'),
                    ];
                }
                wp_reset_postdata();
            }
        }

        return [
            'success' => true,
            'updates' => $updates,
        ];
    }

    /**
     * Obtiene experiencias/estados
     */
    public function get_experiences($request) {
        $estados = get_option('calendario_experiencias_estados', []);

        $experiences = [];
        foreach ($estados as $key => $estado) {
            if ($key === 'cerrado') continue;

            $experiences[] = [
                'id' => $key,
                'name' => $estado['nombre'] ?? $estado['title'] ?? $key,
                'description' => $estado['descripcion'] ?? '',
                'color' => $estado['color'] ?? '#4CAF50',
                'duration' => $estado['duracion'] ?? '',
                'schedules' => $estado['horarios'] ?? [],
            ];
        }

        return [
            'success' => true,
            'experiences' => $experiences,
        ];
    }

    // ==========================================
    // RESERVAS
    // ==========================================

    /**
     * Verifica disponibilidad para una fecha
     */
    public function check_availability($request) {
        $fecha = sanitize_text_field($request->get_param('date'));
        $ticket_slug = sanitize_text_field($request->get_param('ticket'));
        $cantidad = intval($request->get_param('quantity')) ?: 1;

        if (empty($fecha)) {
            return new WP_Error('missing_date', 'Fecha requerida', ['status' => 400]);
        }

        // Verificar estado del día
        $calendario_dias = get_option('calendario_experiencias_dias', []);
        $estado = $calendario_dias[$fecha] ?? 'cerrado';

        if ($estado === 'cerrado' || empty($estado)) {
            return [
                'success' => true,
                'available' => false,
                'reason' => 'Día no disponible',
            ];
        }

        // Verificar plazas disponibles
        if (!empty($ticket_slug)) {
            global $wpdb;
            $tabla = $wpdb->prefix . 'reservas_tickets';

            $vendidas = $wpdb->get_var($wpdb->prepare(
                "SELECT COUNT(*) FROM {$tabla}
                 WHERE fecha = %s AND ticket_slug = %s AND estado != 'cancelado'",
                $fecha, $ticket_slug
            ));

            $ticket_types = get_option('calendario_experiencias_ticket_types', []);
            $plazas_totales = intval($ticket_types[$ticket_slug]['plazas'] ?? 0);
            $plazas_disponibles = $plazas_totales - intval($vendidas);

            return [
                'success' => true,
                'available' => $plazas_disponibles >= $cantidad,
                'date' => $fecha,
                'state' => $estado,
                'ticket' => $ticket_slug,
                'requested' => $cantidad,
                'remaining' => max(0, $plazas_disponibles),
            ];
        }

        return [
            'success' => true,
            'available' => true,
            'date' => $fecha,
            'state' => $estado,
        ];
    }

    /**
     * Prepara una reserva
     */
    public function prepare_reservation($request) {
        $fecha = sanitize_text_field($request->get_param('date'));
        $tickets = $request->get_param('tickets'); // Array de {slug, quantity}
        $cliente = $request->get_param('customer'); // {name, email, phone}

        if (empty($fecha) || empty($tickets)) {
            return new WP_Error('missing_data', 'Faltan datos de la reserva', ['status' => 400]);
        }

        $ticket_types = get_option('calendario_experiencias_ticket_types', []);
        $items = [];
        $total = 0;

        foreach ($tickets as $t) {
            $slug = sanitize_text_field($t['slug']);
            $cantidad = intval($t['quantity']);

            if (!isset($ticket_types[$slug])) {
                continue;
            }

            $ticket_info = $ticket_types[$slug];
            $precio = floatval($ticket_info['precio'] ?? 0);
            $subtotal = $precio * $cantidad;

            $items[] = [
                'slug' => $slug,
                'name' => $ticket_info['name'] ?? $slug,
                'quantity' => $cantidad,
                'price' => $precio,
                'subtotal' => $subtotal,
            ];

            $total += $subtotal;
        }

        return [
            'success' => true,
            'reservation' => [
                'date' => $fecha,
                'items' => $items,
                'total' => $total,
                'customer' => $cliente,
            ],
        ];
    }

    /**
     * Añade al carrito de WooCommerce
     */
    public function add_to_cart($request) {
        $fecha = sanitize_text_field($request->get_param('date'));
        $tickets = $request->get_param('tickets');
        $cliente = $request->get_param('customer');

        if (!class_exists('WooCommerce')) {
            return new WP_Error('woo_unavailable', 'WooCommerce no disponible', ['status' => 500]);
        }

        if (empty($fecha) || empty($tickets)) {
            return new WP_Error('missing_data', 'Faltan datos de la reserva', ['status' => 400]);
        }

        // Inicializar sesión de WooCommerce si no existe
        if (!WC()->session) {
            WC()->session = new WC_Session_Handler();
            WC()->session->init();
        }

        // Inicializar carrito si no existe
        if (!WC()->cart) {
            wc_load_cart();
        }

        // Obtener tipos de tickets
        $ticket_types = get_option('calendario_experiencias_ticket_types', []);
        $productos_addon = get_option('reservas_addon_productos', []);
        $added_count = 0;
        $errors = [];

        foreach ($tickets as $t) {
            $slug = sanitize_text_field($t['slug'] ?? '');
            $cantidad = intval($t['quantity'] ?? 0);

            if (empty($slug) || $cantidad <= 0) {
                continue;
            }

            // Buscar el producto de WooCommerce asociado
            $product_id = $productos_addon[$slug] ?? null;

            // Si no hay producto asociado, intentar buscarlo por meta
            if (!$product_id) {
                $products = wc_get_products([
                    'meta_key' => '_ticket_slug',
                    'meta_value' => $slug,
                    'limit' => 1,
                ]);
                if (!empty($products)) {
                    $product_id = $products[0]->get_id();
                }
            }

            // Si aún no hay producto, crear uno dinámico
            if (!$product_id && isset($ticket_types[$slug])) {
                $ticket_info = $ticket_types[$slug];
                $product_id = $this->create_dynamic_product($slug, $ticket_info);
            }

            if (!$product_id) {
                $errors[] = "Producto no encontrado para: $slug";
                continue;
            }

            // Añadir al carrito con metadatos de fecha
            $cart_item_data = [
                '_reserva_fecha' => $fecha,
                '_ticket_slug' => $slug,
                '_from_mobile_app' => true,
            ];

            try {
                $cart_item_key = WC()->cart->add_to_cart($product_id, $cantidad, 0, [], $cart_item_data);
                if ($cart_item_key) {
                    $added_count++;
                } else {
                    $errors[] = "No se pudo añadir $slug al carrito";
                }
            } catch (Exception $e) {
                $errors[] = $e->getMessage();
            }
        }

        if ($added_count === 0) {
            return new WP_Error(
                'cart_error',
                !empty($errors) ? implode(', ', $errors) : 'No se pudo añadir ningún producto',
                ['status' => 500]
            );
        }

        // Guardar datos del cliente en sesión si los hay
        if (!empty($cliente)) {
            WC()->session->set('mobile_customer', [
                'name' => sanitize_text_field($cliente['name'] ?? ''),
                'email' => sanitize_email($cliente['email'] ?? ''),
                'phone' => sanitize_text_field($cliente['phone'] ?? ''),
            ]);
        }

        return [
            'success' => true,
            'cart_url' => wc_get_cart_url(),
            'checkout_url' => wc_get_checkout_url(),
            'message' => sprintf('Añadidos %d productos al carrito', $added_count),
            'cart_count' => WC()->cart->get_cart_contents_count(),
            'cart_total' => WC()->cart->get_cart_total(),
        ];
    }

    /**
     * Crea un producto dinámico de WooCommerce para un ticket
     */
    private function create_dynamic_product($slug, $ticket_info) {
        $product = new WC_Product_Simple();
        $product->set_name($ticket_info['name'] ?? $slug);
        $product->set_regular_price($ticket_info['precio'] ?? 0);
        $product->set_status('publish');
        $product->set_catalog_visibility('hidden');
        $product->set_virtual(true);
        $product->set_sold_individually(false);
        $product->update_meta_data('_ticket_slug', $slug);
        $product->update_meta_data('_is_reserva_ticket', true);
        $product->save();

        // Guardar referencia
        $productos_addon = get_option('reservas_addon_productos', []);
        $productos_addon[$slug] = $product->get_id();
        update_option('reservas_addon_productos', $productos_addon);

        return $product->get_id();
    }

    /**
     * Obtiene URL del carrito
     */
    public function get_cart_url($request) {
        if (!class_exists('WooCommerce')) {
            return new WP_Error('woo_unavailable', 'WooCommerce no disponible', ['status' => 500]);
        }

        return [
            'success' => true,
            'cart_url' => wc_get_cart_url(),
            'checkout_url' => wc_get_checkout_url(),
        ];
    }

    /**
     * Genera URL especial para checkout desde móvil
     * Esta URL contiene los datos del carrito codificados y cuando se abre
     * en el navegador, añade los productos al carrito del navegador
     */
    public function get_mobile_checkout_url($request) {
        $fecha = sanitize_text_field($request->get_param('date'));
        $tickets = $request->get_param('tickets');

        if (empty($fecha) || empty($tickets)) {
            return new WP_Error('missing_data', 'Faltan datos de la reserva', ['status' => 400]);
        }

        // Codificar datos del carrito
        $cart_data = [
            'date' => $fecha,
            'tickets' => $tickets,
            'timestamp' => time(),
        ];

        // Crear token firmado para seguridad
        // Usar base64url (sin +/= que pueden causar problemas en URLs)
        $json_data = json_encode($cart_data);
        $token = rtrim(strtr(base64_encode($json_data), '+/', '-_'), '=');
        $signature = hash_hmac('sha256', $token, wp_salt('auth'));

        // Generar URL especial
        $checkout_url = add_query_arg([
            'mobile_cart' => $token,
            'sig' => substr($signature, 0, 16),
        ], home_url('/'));

        return [
            'success' => true,
            'checkout_url' => $checkout_url,
            'message' => __('Token válido', 'flavor-chat-ia'),
        ];
    }

    // ==========================================
    // ADMIN: DASHBOARD Y ESTADÍSTICAS
    // ==========================================

    /**
     * Obtiene datos del dashboard admin
     */
    public function get_admin_dashboard($request) {
        global $wpdb;
        $tabla = $wpdb->prefix . 'reservas_tickets';
        $hoy = date('Y-m-d');

        // Log para debug
        error_log("[Mobile API] get_admin_dashboard llamado - tabla: $tabla");

        // Verificar si la tabla existe
        $tabla_existe = Flavor_Chat_Helpers::tabla_existe($tabla);
        error_log("[Mobile API] Tabla existe: " . ($tabla_existe ? 'SI' : 'NO'));
        if (!$tabla_existe) {
            return rest_ensure_response([
                'success' => true,
                'dashboard' => [
                    'today' => [
                        'date' => $hoy,
                        'reservations' => 0,
                        'checkins' => 0,
                        'pending_checkins' => 0,
                    ],
                    'week' => [
                        'reservations' => 0,
                    ],
                    'month' => [
                        'revenue' => 0,
                        'formatted_revenue' => '0,00€',
                    ],
                ],
                'notice' => 'No hay datos de reservas aún'
            ]);
        }

        // Reservas de hoy
        $reservas_hoy = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM {$tabla} WHERE fecha = %s AND estado != 'cancelado'",
            $hoy
        )) ?: 0;

        // Check-ins de hoy
        $checkins_hoy = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM {$tabla} WHERE fecha = %s AND checkin IS NOT NULL",
            $hoy
        )) ?: 0;

        // Reservas próximos 7 días
        $proxima_semana = date('Y-m-d', strtotime('+7 days'));
        $reservas_semana = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM {$tabla}
             WHERE fecha BETWEEN %s AND %s AND estado != 'cancelado'",
            $hoy, $proxima_semana
        )) ?: 0;

        // Ingresos del mes - Usar WooCommerce orders para mayor precisión
        $inicio_mes = date('Y-m-01');
        $fin_mes = date('Y-m-t');
        $ingresos_mes = 0;

        // Método 1: Intentar obtener ingresos de WooCommerce orders
        if (class_exists('WC_Order')) {
            $orders = wc_get_orders([
                'status' => ['completed', 'processing'],
                'date_created' => $inicio_mes . '...' . $fin_mes,
                'limit' => -1,
            ]);

            foreach ($orders as $order) {
                // Verificar que el pedido tiene productos relacionados con reservas
                $es_reserva = false;
                foreach ($order->get_items() as $item) {
                    $product = $item->get_product();
                    if ($product) {
                        $ticket_slug = $product->get_meta('_ticket_slug');
                        $reserva_fecha = $item->get_meta('_reserva_fecha');
                        if (!empty($ticket_slug) || !empty($reserva_fecha)) {
                            $es_reserva = true;
                            break;
                        }
                    }
                }
                if ($es_reserva) {
                    $ingresos_mes += floatval($order->get_total());
                }
            }
        }

        // Método 2: Fallback - calcular desde tickets si WooCommerce no dio resultados
        if ($ingresos_mes == 0) {
            $ticket_types = get_option('calendario_experiencias_ticket_types', []);

            $ventas_mes = $wpdb->get_results($wpdb->prepare(
                "SELECT ticket_slug, COUNT(*) as cantidad
                 FROM {$tabla}
                 WHERE fecha BETWEEN %s AND %s AND estado != 'cancelado'
                 GROUP BY ticket_slug",
                $inicio_mes, $fin_mes
            ), ARRAY_A) ?: [];

            foreach ($ventas_mes as $v) {
                $slug = $v['ticket_slug'];
                $precio = 0;
                if (isset($ticket_types[$slug])) {
                    $precio = floatval($ticket_types[$slug]['precio'] ?? $ticket_types[$slug]['price'] ?? 0);
                }
                $ingresos_mes += $precio * intval($v['cantidad']);
            }
        }

        return rest_ensure_response([
            'success' => true,
            'dashboard' => [
                'today' => [
                    'date' => $hoy,
                    'reservations' => intval($reservas_hoy),
                    'checkins' => intval($checkins_hoy),
                    'pending_checkins' => intval($reservas_hoy) - intval($checkins_hoy),
                ],
                'week' => [
                    'reservations' => intval($reservas_semana),
                ],
                'month' => [
                    'revenue' => $ingresos_mes,
                    'formatted_revenue' => number_format($ingresos_mes, 2, ',', '.') . '€',
                ],
            ],
        ]);
    }

    /**
     * Obtiene lista de reservas para admin
     */
    public function get_admin_reservations($request) {
        global $wpdb;
        $tabla = $wpdb->prefix . 'reservas_tickets';

        // Verificar si la tabla existe
        $tabla_existe = Flavor_Chat_Helpers::tabla_existe($tabla);
        if (!$tabla_existe) {
            return rest_ensure_response([
                'success' => true,
                'reservations' => [],
                'total' => 0,
                'notice' => 'No hay datos de reservas aún'
            ]);
        }

        $fecha = sanitize_text_field($request->get_param('date'));
        $fecha_inicio = sanitize_text_field($request->get_param('from'));
        $fecha_fin = sanitize_text_field($request->get_param('to'));
        $estado = sanitize_text_field($request->get_param('status'));
        $ticket_type = sanitize_text_field($request->get_param('ticket_type'));
        $search = sanitize_text_field($request->get_param('search'));
        $limit = intval($request->get_param('limit')) ?: 50;
        $offset = intval($request->get_param('offset')) ?: 0;

        $where = ["1=1"];
        $params = [];

        if (!empty($fecha)) {
            $where[] = "fecha = %s";
            $params[] = $fecha;
        } elseif (!empty($fecha_inicio) && !empty($fecha_fin)) {
            $where[] = "fecha BETWEEN %s AND %s";
            $params[] = $fecha_inicio;
            $params[] = $fecha_fin;
        }

        if (!empty($estado) && $estado !== 'todos') {
            $where[] = "estado = %s";
            $params[] = $estado;
        }

        // Filtro por tipo de ticket
        if (!empty($ticket_type)) {
            $where[] = "ticket_slug = %s";
            $params[] = $ticket_type;
        }

        // Búsqueda por nombre, email, teléfono o código de ticket
        if (!empty($search)) {
            $search_like = '%' . $wpdb->esc_like($search) . '%';
            $where[] = "(ticket_code LIKE %s OR cliente LIKE %s)";
            $params[] = $search_like;
            $params[] = $search_like;
        }

        $where_clause = implode(' AND ', $where);

        $query = "SELECT * FROM {$tabla} WHERE {$where_clause} ORDER BY fecha DESC, id DESC LIMIT %d OFFSET %d";
        $params[] = $limit;
        $params[] = $offset;

        $reservas = $wpdb->get_results($wpdb->prepare($query, ...$params), ARRAY_A);

        $ticket_types = get_option('calendario_experiencias_ticket_types', []);

        // Cache para pedidos de WooCommerce
        $orders_cache = [];

        foreach ($reservas as &$r) {
            $customer_name = '';
            $customer_email = '';
            $customer_phone = '';

            // El campo 'cliente' puede ser:
            // 1. Un string simple con el nombre (ej: "Juan López")
            // 2. JSON con datos completos (ej: {"nombre": "Juan", "email": "..."})
            // 3. Vacío
            $cliente_raw = trim($r['cliente'] ?? '');

            if (!empty($cliente_raw)) {
                // Verificar si es JSON (empieza con {)
                if (substr($cliente_raw, 0, 1) === '{') {
                    $cliente = json_decode($cliente_raw, true);
                    if ($cliente && is_array($cliente)) {
                        $customer_name = $cliente['nombre'] ?? $cliente['name'] ?? '';
                        $customer_email = $cliente['email'] ?? '';
                        $customer_phone = $cliente['telefono'] ?? $cliente['phone'] ?? '';
                    }
                } else {
                    // Es un string simple con el nombre
                    $customer_name = $cliente_raw;
                }
            }

            // Si aún no tenemos nombre, obtener del pedido WooCommerce
            // usando reserva_id (que contiene el order_id de WooCommerce)
            if (empty($customer_name) && function_exists('wc_get_order')) {
                $order_id = 0;

                // reserva_id contiene el order_id de WooCommerce
                if (!empty($r['reserva_id']) && intval($r['reserva_id']) > 0) {
                    $order_id = intval($r['reserva_id']);
                }

                if ($order_id > 0) {
                    if (!isset($orders_cache[$order_id])) {
                        $order = wc_get_order($order_id);
                        if ($order) {
                            $orders_cache[$order_id] = [
                                'name' => trim($order->get_billing_first_name() . ' ' . $order->get_billing_last_name()),
                                'email' => $order->get_billing_email(),
                                'phone' => $order->get_billing_phone(),
                            ];
                        } else {
                            $orders_cache[$order_id] = null;
                        }
                    }

                    if ($orders_cache[$order_id]) {
                        if (empty($customer_name)) {
                            $customer_name = $orders_cache[$order_id]['name'];
                        }
                        if (empty($customer_email)) {
                            $customer_email = $orders_cache[$order_id]['email'];
                        }
                        if (empty($customer_phone)) {
                            $customer_phone = $orders_cache[$order_id]['phone'];
                        }
                    }
                }
            }

            $r['customer'] = [
                'name' => trim($customer_name) ?: 'Sin nombre',
                'email' => $customer_email,
                'phone' => $customer_phone,
            ];
            $r['ticket_name'] = $ticket_types[$r['ticket_slug']]['name'] ?? $r['ticket_slug'];
            unset($r['cliente']); // Limpiar campo raw
        }

        // Total count
        $total = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM {$tabla} WHERE " . $where_clause,
            ...array_slice($params, 0, -2)
        )) ?: 0;

        return rest_ensure_response([
            'success' => true,
            'reservations' => $reservas,
            'total' => intval($total),
            'limit' => $limit,
            'offset' => $offset,
        ]);
    }

    /**
     * Obtiene detalle de una reserva
     */
    public function get_reservation_detail($request) {
        global $wpdb;
        $tabla = $wpdb->prefix . 'reservas_tickets';
        $id = intval($request->get_param('id'));

        $reserva = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$tabla} WHERE id = %d",
            $id
        ), ARRAY_A);

        if (!$reserva) {
            return new WP_Error('not_found', __('Reserva no encontrada', 'flavor-chat-ia'), ['status' => 404]);
        }

        $ticket_types = get_option('calendario_experiencias_ticket_types', []);

        // Parsear datos del cliente (puede ser string simple o JSON)
        $customer_name = '';
        $customer_email = '';
        $customer_phone = '';
        $cliente_raw = trim($reserva['cliente'] ?? '');

        if (!empty($cliente_raw)) {
            if (substr($cliente_raw, 0, 1) === '{') {
                $cliente = json_decode($cliente_raw, true);
                if ($cliente && is_array($cliente)) {
                    $customer_name = $cliente['nombre'] ?? $cliente['name'] ?? '';
                    $customer_email = $cliente['email'] ?? '';
                    $customer_phone = $cliente['telefono'] ?? $cliente['phone'] ?? '';
                }
            } else {
                $customer_name = $cliente_raw;
            }
        }

        // Complementar con datos de WooCommerce si faltan
        if ((empty($customer_name) || empty($customer_email)) && function_exists('wc_get_order')) {
            $order_id = intval($reserva['reserva_id'] ?? 0);
            if ($order_id > 0) {
                $order = wc_get_order($order_id);
                if ($order) {
                    if (empty($customer_name)) {
                        $customer_name = trim($order->get_billing_first_name() . ' ' . $order->get_billing_last_name());
                    }
                    if (empty($customer_email)) {
                        $customer_email = $order->get_billing_email();
                    }
                    if (empty($customer_phone)) {
                        $customer_phone = $order->get_billing_phone();
                    }
                }
            }
        }

        return [
            'success' => true,
            'reservation' => [
                'id' => $reserva['id'],
                'date' => $reserva['fecha'],
                'ticket_code' => $reserva['ticket_code'],
                'ticket_slug' => $reserva['ticket_slug'],
                'ticket_name' => $ticket_types[$reserva['ticket_slug']]['name'] ?? $reserva['ticket_slug'],
                'status' => $reserva['estado'],
                'checkin' => $reserva['checkin'],
                'blocked' => (bool) ($reserva['blocked'] ?? false),
                'customer' => [
                    'name' => trim($customer_name) ?: 'Sin nombre',
                    'email' => $customer_email,
                    'phone' => $customer_phone,
                ],
                'order_id' => $reserva['reserva_id'] ?? null,
                'created_at' => $reserva['created_at'] ?? null,
            ],
        ];
    }

    /**
     * Realiza check-in de una reserva
     */
    public function do_checkin($request) {
        global $wpdb;
        $tabla = $wpdb->prefix . 'reservas_tickets';
        $id = intval($request->get_param('id'));

        // Verificar que existe y está pendiente
        $reserva = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$tabla} WHERE id = %d",
            $id
        ), ARRAY_A);

        if (!$reserva) {
            return new WP_Error('not_found', __('Reserva no encontrada', 'flavor-chat-ia'), ['status' => 404]);
        }

        if ($reserva['estado'] === 'usado') {
            return new WP_Error('already_used', 'Esta reserva ya fue usada', ['status' => 400]);
        }

        if ($reserva['estado'] === 'cancelado') {
            return new WP_Error('cancelled', 'Esta reserva está cancelada', ['status' => 400]);
        }

        // Realizar check-in
        $updated = $wpdb->update(
            $tabla,
            [
                'estado' => 'usado',
                'checkin' => current_time('mysql'),
            ],
            ['id' => $id],
            ['%s', '%s'],
            ['%d']
        );

        if ($updated === false) {
            return new WP_Error('update_failed', 'Error al realizar check-in', ['status' => 500]);
        }

        return [
            'success' => true,
            'message' => __('Cancelada desde app admin', 'flavor-chat-ia'),
            'checkin_time' => current_time('mysql'),
        ];
    }

    /**
     * Cancela una reserva
     */
    public function cancel_reservation($request) {
        global $wpdb;
        $tabla = $wpdb->prefix . 'reservas_tickets';
        $id = intval($request->get_param('id'));
        $motivo = sanitize_text_field($request->get_param('reason')) ?: 'Cancelada desde app admin';

        // Verificar que existe
        $reserva = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$tabla} WHERE id = %d",
            $id
        ), ARRAY_A);

        if (!$reserva) {
            return new WP_Error('not_found', __('Reserva no encontrada', 'flavor-chat-ia'), ['status' => 404]);
        }

        if ($reserva['estado'] === 'cancelado') {
            return new WP_Error('already_cancelled', 'Esta reserva ya está cancelada', ['status' => 400]);
        }

        if ($reserva['estado'] === 'usado') {
            return new WP_Error('already_used', 'No se puede cancelar una reserva ya usada', ['status' => 400]);
        }

        // Cancelar reserva
        $updated = $wpdb->update(
            $tabla,
            [
                'estado' => 'cancelado',
                'notas' => $motivo,
            ],
            ['id' => $id],
            ['%s', '%s'],
            ['%d']
        );

        if ($updated === false) {
            return new WP_Error('update_failed', 'Error al cancelar la reserva', ['status' => 500]);
        }

        return [
            'success' => true,
            'message' => __('Token válido', 'flavor-chat-ia'),
        ];
    }

    /**
     * Busca una reserva por código de ticket (para QR)
     */
    public function find_reservation_by_code($request) {
        global $wpdb;
        $tabla = $wpdb->prefix . 'reservas_tickets';
        $code = sanitize_text_field($request->get_param('code'));

        if (empty($code)) {
            return new WP_Error('missing_code', 'Código de ticket requerido', ['status' => 400]);
        }

        $reserva = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$tabla} WHERE ticket_code = %s",
            $code
        ), ARRAY_A);

        if (!$reserva) {
            return new WP_Error('not_found', __('Reserva no encontrada', 'flavor-chat-ia'), ['status' => 404]);
        }

        $ticket_types = get_option('calendario_experiencias_ticket_types', []);
        $cliente = json_decode($reserva['cliente'], true) ?? [];

        return [
            'success' => true,
            'reservation' => [
                'id' => intval($reserva['id']),
                'date' => $reserva['fecha'],
                'ticket_code' => $reserva['ticket_code'],
                'ticket_slug' => $reserva['ticket_slug'],
                'ticket_name' => $ticket_types[$reserva['ticket_slug']]['name'] ?? $reserva['ticket_slug'],
                'status' => $reserva['estado'],
                'checkin' => $reserva['checkin'],
                'blocked' => (bool) ($reserva['blocked'] ?? false),
                'customer' => [
                    'name' => $cliente['nombre'] ?? $cliente['name'] ?? '',
                    'email' => $cliente['email'] ?? '',
                    'phone' => $cliente['telefono'] ?? $cliente['phone'] ?? '',
                ],
                'order_id' => $reserva['order_id'] ?? null,
                'created_at' => $reserva['created_at'] ?? null,
            ],
        ];
    }

    /**
     * Obtiene estadísticas
     */
    public function get_admin_stats($request) {
        global $wpdb;

        $fecha_inicio = sanitize_text_field($request->get_param('from')) ?: date('Y-m-01');
        $fecha_fin = sanitize_text_field($request->get_param('to')) ?: date('Y-m-d');

        $tabla = $wpdb->prefix . 'reservas_tickets';

        // Verificar si la tabla existe
        $tabla_existe = Flavor_Chat_Helpers::tabla_existe($tabla);
        if (!$tabla_existe) {
            return rest_ensure_response([
                'success' => true,
                'stats' => [
                    'total_reservations' => 0,
                    'total_checkins' => 0,
                    'total_cancelled' => 0,
                    'total_revenue' => 0,
                    'daily' => [],
                    'by_ticket_type' => []
                ],
                'notice' => 'No hay datos de reservas aún'
            ]);
        }

        $ticket_types = get_option('calendario_experiencias_ticket_types', []);

        // Estadísticas totales
        $total_reservations = (int) $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM {$tabla} WHERE fecha BETWEEN %s AND %s",
            $fecha_inicio, $fecha_fin
        ));

        $total_checkins = (int) $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM {$tabla} WHERE fecha BETWEEN %s AND %s AND estado = 'usado'",
            $fecha_inicio, $fecha_fin
        ));

        $total_cancelled = (int) $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM {$tabla} WHERE fecha BETWEEN %s AND %s AND estado = 'cancelado'",
            $fecha_inicio, $fecha_fin
        ));

        // Calcular ingresos
        $reservas_con_precio = $wpdb->get_results($wpdb->prepare(
            "SELECT ticket_slug, COUNT(*) as cantidad FROM {$tabla}
             WHERE fecha BETWEEN %s AND %s AND estado != 'cancelado'
             GROUP BY ticket_slug",
            $fecha_inicio, $fecha_fin
        ), ARRAY_A);

        $total_revenue = 0;
        $by_ticket_type = [];
        $revenue_by_ticket = [];

        foreach ($reservas_con_precio as $r) {
            $slug = $r['ticket_slug'];
            $cantidad = (int) $r['cantidad'];
            $precio = isset($ticket_types[$slug]['price']) ? floatval($ticket_types[$slug]['price']) : 0;
            $ingreso = $cantidad * $precio;

            $total_revenue += $ingreso;

            // Obtener nombre del ticket
            $nombre = isset($ticket_types[$slug]['nombre'])
                ? $ticket_types[$slug]['nombre']
                : (isset($ticket_types[$slug]['name']) ? $ticket_types[$slug]['name'] : $slug);

            // Usar nombre en lugar de slug
            $by_ticket_type[$nombre] = $cantidad;
            $revenue_by_ticket[$nombre] = $ingreso;
        }

        // Estadísticas diarias
        $daily_raw = $wpdb->get_results($wpdb->prepare(
            "SELECT fecha, COUNT(*) as reservations FROM {$tabla}
             WHERE fecha BETWEEN %s AND %s AND estado != 'cancelado'
             GROUP BY fecha ORDER BY fecha ASC",
            $fecha_inicio, $fecha_fin
        ), ARRAY_A);

        $daily_stats = [];
        foreach ($daily_raw as $d) {
            // Calcular revenue del día
            $revenue_day = $wpdb->get_results($wpdb->prepare(
                "SELECT ticket_slug, COUNT(*) as cantidad FROM {$tabla}
                 WHERE fecha = %s AND estado != 'cancelado'
                 GROUP BY ticket_slug",
                $d['fecha']
            ), ARRAY_A);

            $rev = 0;
            foreach ($revenue_day as $rd) {
                $slug = $rd['ticket_slug'];
                $precio = isset($ticket_types[$slug]['price']) ? floatval($ticket_types[$slug]['price']) : 0;
                $rev += intval($rd['cantidad']) * $precio;
            }

            $daily_stats[] = [
                'date' => $d['fecha'],
                'reservations' => (int) $d['reservations'],
                'revenue' => $rev
            ];
        }

        return rest_ensure_response([
            'success' => true,
            'stats' => [
                'total_reservations' => $total_reservations,
                'total_checkins' => $total_checkins,
                'total_cancelled' => $total_cancelled,
                'total_revenue' => $total_revenue,
                'daily' => $daily_stats,
                'by_ticket_type' => $by_ticket_type,
                'revenue_by_ticket' => $revenue_by_ticket
            ]
        ]);
    }

    /**
     * Obtiene lista de clientes
     */
    public function get_customers($request) {
        global $wpdb;

        $from = sanitize_text_field($request->get_param('from'));
        $to = sanitize_text_field($request->get_param('to'));
        $search = sanitize_text_field($request->get_param('search'));
        $limit = intval($request->get_param('limit')) ?: 50;

        // Si no hay fechas, usar últimos 30 días
        if (empty($from)) {
            $from = date('Y-m-d', strtotime('-30 days'));
        }
        if (empty($to)) {
            $to = date('Y-m-d');
        }

        $tabla = $wpdb->prefix . 'reservas_tickets';

        // Verificar si la tabla existe
        $tabla_existe = Flavor_Chat_Helpers::tabla_existe($tabla);
        if (!$tabla_existe) {
            return rest_ensure_response([
                'success' => true,
                'customers' => [],
                'total' => 0,
                'notice' => 'No hay datos de reservas aún'
            ]);
        }

        $customers = [];
        $orders_cache = [];

        // Obtener reservas con order_id
        $reservas = $wpdb->get_results($wpdb->prepare(
            "SELECT reserva_id, fecha, cliente, ticket_slug, estado
             FROM {$tabla}
             WHERE fecha BETWEEN %s AND %s AND reserva_id IS NOT NULL AND reserva_id > 0
             ORDER BY fecha DESC
             LIMIT %d",
            $from, $to, $limit * 3 // Más registros porque se agrupan
        ), ARRAY_A);

        // Agrupar por cliente
        foreach ($reservas as $r) {
            $order_id = intval($r['reserva_id']);
            $customer_name = '';
            $customer_email = '';
            $customer_phone = '';

            // Obtener del pedido WooCommerce
            if ($order_id > 0 && function_exists('wc_get_order')) {
                if (!isset($orders_cache[$order_id])) {
                    $order = wc_get_order($order_id);
                    if ($order) {
                        $orders_cache[$order_id] = [
                            'name' => trim($order->get_billing_first_name() . ' ' . $order->get_billing_last_name()),
                            'email' => $order->get_billing_email(),
                            'phone' => $order->get_billing_phone(),
                        ];
                    } else {
                        $orders_cache[$order_id] = null;
                    }
                }
                if ($orders_cache[$order_id]) {
                    $customer_name = $orders_cache[$order_id]['name'];
                    $customer_email = $orders_cache[$order_id]['email'];
                    $customer_phone = $orders_cache[$order_id]['phone'];
                }
            }

            // Fallback al campo cliente
            if (empty($customer_name)) {
                $cliente_raw = trim($r['cliente'] ?? '');
                if (!empty($cliente_raw)) {
                    if (substr($cliente_raw, 0, 1) === '{') {
                        $cliente = json_decode($cliente_raw, true);
                        if ($cliente && is_array($cliente)) {
                            $customer_name = $cliente['nombre'] ?? $cliente['name'] ?? '';
                            $customer_email = $cliente['email'] ?? $customer_email;
                            $customer_phone = $cliente['telefono'] ?? $cliente['phone'] ?? $customer_phone;
                        }
                    } else {
                        $customer_name = $cliente_raw;
                    }
                }
            }

            if (empty($customer_name)) {
                $customer_name = 'Sin nombre';
            }

            // Filtrar por búsqueda
            if (!empty($search)) {
                $search_lower = strtolower($search);
                if (strpos(strtolower($customer_name), $search_lower) === false &&
                    strpos(strtolower($customer_email), $search_lower) === false &&
                    strpos(strtolower($customer_phone), $search_lower) === false) {
                    continue;
                }
            }

            // Usar email como key único, o order_id si no hay email
            $key = !empty($customer_email) ? $customer_email : 'order_' . $order_id;

            if (!isset($customers[$key])) {
                $customers[$key] = [
                    'id' => $order_id,
                    'name' => $customer_name,
                    'email' => $customer_email,
                    'phone' => $customer_phone,
                    'total_reservations' => 0,
                    'last_reservation' => $r['fecha'],
                    'reservations' => []
                ];
            }

            $customers[$key]['total_reservations']++;
            $customers[$key]['reservations'][] = [
                'date' => $r['fecha'],
                'ticket' => $r['ticket_slug'],
                'status' => $r['estado']
            ];
        }

        // Limitar resultados
        $customers_list = array_slice(array_values($customers), 0, $limit);

        return rest_ensure_response([
            'success' => true,
            'customers' => $customers_list,
            'total' => count($customers)
        ]);
    }

    /**
     * Exporta datos a CSV
     */
    public function export_csv($request) {
        global $wpdb;

        $type = sanitize_text_field($request->get_param('type')) ?: 'reservations';
        $from = sanitize_text_field($request->get_param('from'));
        $to = sanitize_text_field($request->get_param('to'));
        $view_only = (bool) $request->get_param('view_only');
        $ticket_type_filter = sanitize_text_field($request->get_param('ticket_type'));

        if (empty($from) || empty($to)) {
            return new WP_Error('missing_dates', 'Fechas requeridas', ['status' => 400]);
        }

        $tabla = $wpdb->prefix . 'reservas_tickets';
        $ticket_types = get_option('calendario_experiencias_ticket_types', []);
        $data = [];
        $headers = [];

        switch ($type) {
            case 'reservations':
                $headers = ['Fecha', 'Código', 'Ticket', 'Cliente', 'Email', 'Teléfono', 'Estado', 'Check-in'];

                // Query base con filtro opcional de ticket_type
                $where_clause = "fecha BETWEEN %s AND %s";
                $params = [$from, $to];

                if (!empty($ticket_type_filter)) {
                    $where_clause .= " AND ticket_slug = %s";
                    $params[] = $ticket_type_filter;
                }

                $reservas = $wpdb->get_results($wpdb->prepare(
                    "SELECT * FROM {$tabla} WHERE {$where_clause} ORDER BY fecha DESC, id DESC",
                    ...$params
                ), ARRAY_A);

                // Cache de pedidos
                $orders_cache = [];

                foreach ($reservas as $r) {
                    $customer_name = '';
                    $customer_email = '';
                    $customer_phone = '';

                    // Parsear campo cliente (puede ser JSON o string)
                    $cliente_raw = trim($r['cliente'] ?? '');
                    if (!empty($cliente_raw)) {
                        if (substr($cliente_raw, 0, 1) === '{') {
                            $cliente = json_decode($cliente_raw, true);
                            if ($cliente && is_array($cliente)) {
                                $customer_name = $cliente['nombre'] ?? $cliente['name'] ?? '';
                                $customer_email = $cliente['email'] ?? '';
                                $customer_phone = $cliente['telefono'] ?? $cliente['phone'] ?? '';
                            }
                        } else {
                            $customer_name = $cliente_raw;
                        }
                    }

                    // Obtener del pedido WooCommerce si no hay datos
                    // El campo reserva_id contiene el order_id de WooCommerce
                    $order_id = intval($r['reserva_id'] ?? 0);
                    if ($order_id > 0 && function_exists('wc_get_order')) {
                        if (!isset($orders_cache[$order_id])) {
                            $order = wc_get_order($order_id);
                            if ($order) {
                                $orders_cache[$order_id] = [
                                    'name' => trim($order->get_billing_first_name() . ' ' . $order->get_billing_last_name()),
                                    'email' => $order->get_billing_email(),
                                    'phone' => $order->get_billing_phone(),
                                ];
                            } else {
                                $orders_cache[$order_id] = null;
                            }
                        }
                        if ($orders_cache[$order_id]) {
                            if (empty($customer_name)) {
                                $customer_name = $orders_cache[$order_id]['name'];
                            }
                            if (empty($customer_email)) {
                                $customer_email = $orders_cache[$order_id]['email'];
                            }
                            if (empty($customer_phone)) {
                                $customer_phone = $orders_cache[$order_id]['phone'];
                            }
                        }
                    }

                    $data[] = [
                        'fecha' => $r['fecha'],
                        'codigo' => $r['ticket_code'],
                        'ticket' => $ticket_types[$r['ticket_slug']]['name'] ?? $r['ticket_slug'],
                        'cliente' => $customer_name ?: 'Sin nombre',
                        'email' => $customer_email,
                        'telefono' => $customer_phone,
                        'estado' => ucfirst($r['estado']),
                        'checkin' => $r['checkin'] ?: '-',
                    ];
                }
                break;

            case 'customers':
                $headers = ['Cliente', 'Email', 'Teléfono', 'Total Reservas', 'Última Reserva'];

                // Agrupar por cliente desde pedidos
                // El campo reserva_id contiene el order_id de WooCommerce
                $customers = [];
                $reservas = $wpdb->get_results($wpdb->prepare(
                    "SELECT reserva_id, cliente, fecha FROM {$tabla}
                     WHERE fecha BETWEEN %s AND %s AND reserva_id IS NOT NULL AND reserva_id > 0
                     ORDER BY fecha DESC",
                    $from, $to
                ), ARRAY_A);

                foreach ($reservas as $r) {
                    $order_id = intval($r['reserva_id']);
                    if ($order_id > 0 && function_exists('wc_get_order')) {
                        $order = wc_get_order($order_id);
                        if ($order) {
                            $email = $order->get_billing_email();
                            if (empty($email)) continue; // Saltar si no tiene email

                            if (!isset($customers[$email])) {
                                $customers[$email] = [
                                    'name' => trim($order->get_billing_first_name() . ' ' . $order->get_billing_last_name()),
                                    'email' => $email,
                                    'phone' => $order->get_billing_phone(),
                                    'count' => 0,
                                    'last_date' => $r['fecha'],
                                ];
                            }
                            $customers[$email]['count']++;
                        }
                    }
                }

                foreach ($customers as $c) {
                    $data[] = [
                        'cliente' => $c['name'] ?: 'Sin nombre',
                        'email' => $c['email'],
                        'telefono' => $c['phone'],
                        'total' => $c['count'],
                        'ultima' => $c['last_date'],
                    ];
                }
                break;

            case 'revenue':
                $headers = ['Fecha', 'Reservas', 'Ingresos'];

                $stats = $wpdb->get_results($wpdb->prepare(
                    "SELECT fecha, ticket_slug, COUNT(*) as cantidad
                     FROM {$tabla}
                     WHERE fecha BETWEEN %s AND %s AND estado != 'cancelado'
                     GROUP BY fecha, ticket_slug
                     ORDER BY fecha DESC",
                    $from, $to
                ), ARRAY_A);

                $by_date = [];
                foreach ($stats as $s) {
                    $fecha = $s['fecha'];
                    if (!isset($by_date[$fecha])) {
                        $by_date[$fecha] = ['reservas' => 0, 'ingresos' => 0];
                    }
                    $precio = floatval($ticket_types[$s['ticket_slug']]['precio'] ?? 0);
                    $by_date[$fecha]['reservas'] += intval($s['cantidad']);
                    $by_date[$fecha]['ingresos'] += $precio * intval($s['cantidad']);
                }

                foreach ($by_date as $fecha => $d) {
                    $data[] = [
                        'fecha' => $fecha,
                        'reservas' => $d['reservas'],
                        'ingresos' => number_format($d['ingresos'], 2, ',', '.') . '€',
                    ];
                }
                break;

            default:
                return new WP_Error('invalid_type', 'Tipo de exportación no válido', ['status' => 400]);
        }

        // Si es solo visualización, devolver datos en JSON
        if ($view_only) {
            return [
                'success' => true,
                'type' => $type,
                'from' => $from,
                'to' => $to,
                'headers' => $headers,
                'data' => $data,
                'total' => count($data),
            ];
        }

        // Generar CSV
        $csv_lines = [];
        $csv_lines[] = implode(';', $headers);

        foreach ($data as $row) {
            $csv_lines[] = implode(';', array_map(function($val) {
                // Escapar valores con punto y coma o comillas
                $val = str_replace('"', '""', $val);
                if (strpos($val, ';') !== false || strpos($val, '"') !== false) {
                    $val = '"' . $val . '"';
                }
                return $val;
            }, array_values($row)));
        }

        $csv_content = implode("\n", $csv_lines);
        $filename = "{$type}_{$from}_{$to}.csv";

        return [
            'success' => true,
            'csv' => $csv_content,
            'filename' => $filename,
            'total' => count($data),
        ];
    }

    // ==========================================
    // CLIENTES MANUALES
    // ==========================================

    /**
     * Obtener clientes manuales
     */
    public function get_manual_customers($request) {
        global $wpdb;

        $from = sanitize_text_field($request->get_param('from'));
        $to = sanitize_text_field($request->get_param('to'));

        $tabla_clientes = $wpdb->prefix . 'clientes_manuales';
        $tabla_tickets = $wpdb->prefix . 'clientes_manuales_tickets';

        // Verificar si la tabla existe
        $tabla_existe = $wpdb->get_var($wpdb->prepare("SHOW TABLES LIKE %s", $tabla_clientes));
        if (!$tabla_existe) {
            return rest_ensure_response([
                'success' => true,
                'customers' => [],
                'message' => __('Tabla de clientes manuales no existe', 'flavor-chat-ia')
            ]);
        }

        // Construir query
        $where = "1=1";
        $params = [];

        if (!empty($from) && !empty($to)) {
            $where .= " AND c.fecha_evento BETWEEN %s AND %s";
            $params[] = $from;
            $params[] = $to;
        }

        $query = "SELECT c.*,
                         GROUP_CONCAT(CONCAT(t.ticket_slug, ':', t.cantidad) SEPARATOR ',') as tickets_raw
                  FROM {$tabla_clientes} c
                  LEFT JOIN {$tabla_tickets} t ON c.id = t.cliente_manual_id
                  WHERE {$where}
                  GROUP BY c.id
                  ORDER BY c.fecha_evento DESC, c.nombre ASC";

        if (!empty($params)) {
            $query = $wpdb->prepare($query, ...$params);
        }

        $clientes = $wpdb->get_results($query, ARRAY_A);

        // Obtener tipos de tickets para nombres
        $ticket_types = get_option('calendario_experiencias_ticket_types', []);

        // Formatear resultado
        $result = [];
        foreach ($clientes as $c) {
            $tickets = [];
            if (!empty($c['tickets_raw'])) {
                foreach (explode(',', $c['tickets_raw']) as $ticket_str) {
                    $parts = explode(':', $ticket_str);
                    if (count($parts) === 2) {
                        $slug = $parts[0];
                        $qty = intval($parts[1]);
                        $tickets[] = [
                            'slug' => $slug,
                            'name' => $ticket_types[$slug]['name'] ?? $slug,
                            'quantity' => $qty
                        ];
                    }
                }
            }

            $result[] = [
                'id' => intval($c['id']),
                'name' => $c['nombre'],
                'phone' => $c['telefono'] ?? '',
                'email' => $c['email'] ?? '',
                'date' => $c['fecha_evento'],
                'notes' => $c['notas'] ?? '',
                'status' => $c['estado'],
                'tickets' => $tickets,
                'created_at' => $c['creado_en'],
                'origin' => 'manual'
            ];
        }

        return rest_ensure_response([
            'success' => true,
            'customers' => $result
        ]);
    }

    /**
     * Crear cliente manual
     */
    public function create_manual_customer($request) {
        global $wpdb;

        $tabla_clientes = $wpdb->prefix . 'clientes_manuales';
        $tabla_tickets = $wpdb->prefix . 'clientes_manuales_tickets';

        $nombre = sanitize_text_field($request->get_param('name'));
        $telefono = sanitize_text_field($request->get_param('phone'));
        $email = sanitize_email($request->get_param('email'));
        $fecha = sanitize_text_field($request->get_param('date'));
        $notas = sanitize_textarea_field($request->get_param('notes'));
        $tickets = $request->get_param('tickets') ?? [];

        if (empty($nombre) || empty($fecha)) {
            return new WP_Error('missing_data', 'Nombre y fecha son requeridos', ['status' => 400]);
        }

        // Insertar cliente
        $inserted = $wpdb->insert($tabla_clientes, [
            'nombre' => $nombre,
            'telefono' => $telefono,
            'email' => $email,
            'fecha_evento' => $fecha,
            'notas' => $notas,
            'creado_por' => get_current_user_id(),
            'estado' => 'activo'
        ]);

        if ($inserted === false) {
            return new WP_Error('db_error', 'Error al crear cliente', ['status' => 500]);
        }

        $cliente_id = $wpdb->insert_id;

        // Insertar tickets
        if (!empty($tickets) && is_array($tickets)) {
            foreach ($tickets as $ticket) {
                $slug = sanitize_text_field($ticket['slug'] ?? '');
                $qty = intval($ticket['quantity'] ?? 0);
                if (!empty($slug) && $qty > 0) {
                    $wpdb->insert($tabla_tickets, [
                        'cliente_manual_id' => $cliente_id,
                        'ticket_slug' => $slug,
                        'cantidad' => $qty
                    ]);
                }
            }
        }

        return rest_ensure_response([
            'success' => true,
            'id' => $cliente_id,
            'message' => __('clientes_manuales_tickets', 'flavor-chat-ia')
        ]);
    }

    /**
     * Actualizar cliente manual
     */
    public function update_manual_customer($request) {
        global $wpdb;

        $id = intval($request->get_param('id'));
        $tabla_clientes = $wpdb->prefix . 'clientes_manuales';
        $tabla_tickets = $wpdb->prefix . 'clientes_manuales_tickets';

        $datos = [];

        if ($request->has_param('name')) {
            $datos['nombre'] = sanitize_text_field($request->get_param('name'));
        }
        if ($request->has_param('phone')) {
            $datos['telefono'] = sanitize_text_field($request->get_param('phone'));
        }
        if ($request->has_param('email')) {
            $datos['email'] = sanitize_email($request->get_param('email'));
        }
        if ($request->has_param('date')) {
            $datos['fecha_evento'] = sanitize_text_field($request->get_param('date'));
        }
        if ($request->has_param('notes')) {
            $datos['notas'] = sanitize_textarea_field($request->get_param('notes'));
        }
        if ($request->has_param('status')) {
            $datos['estado'] = sanitize_text_field($request->get_param('status'));
        }

        if (!empty($datos)) {
            $wpdb->update($tabla_clientes, $datos, ['id' => $id]);
        }

        // Actualizar tickets si se proporcionan
        $tickets = $request->get_param('tickets');
        if ($tickets !== null && is_array($tickets)) {
            // Eliminar tickets existentes
            $wpdb->delete($tabla_tickets, ['cliente_manual_id' => $id]);

            // Insertar nuevos
            foreach ($tickets as $ticket) {
                $slug = sanitize_text_field($ticket['slug'] ?? '');
                $qty = intval($ticket['quantity'] ?? 0);
                if (!empty($slug) && $qty > 0) {
                    $wpdb->insert($tabla_tickets, [
                        'cliente_manual_id' => $id,
                        'ticket_slug' => $slug,
                        'cantidad' => $qty
                    ]);
                }
            }
        }

        return rest_ensure_response([
            'success' => true,
            'message' => __('clientes_manuales_tickets', 'flavor-chat-ia')
        ]);
    }

    /**
     * Eliminar cliente manual
     */
    public function delete_manual_customer($request) {
        global $wpdb;

        $id = intval($request->get_param('id'));
        $tabla_clientes = $wpdb->prefix . 'clientes_manuales';
        $tabla_tickets = $wpdb->prefix . 'clientes_manuales_tickets';

        // Eliminar tickets asociados
        $wpdb->delete($tabla_tickets, ['cliente_manual_id' => $id]);

        // Eliminar cliente
        $result = $wpdb->delete($tabla_clientes, ['id' => $id]);

        if ($result === false) {
            return new WP_Error('db_error', 'Error al eliminar cliente', ['status' => 500]);
        }

        return rest_ensure_response([
            'success' => true,
            'message' => __('Cliente creado correctamente', 'flavor-chat-ia')
        ]);
    }

    /**
     * Guardar notas de cliente (manual o WooCommerce)
     */
    public function save_customer_notes($request) {
        global $wpdb;

        $origin = sanitize_text_field($request->get_param('origin')); // 'manual' o 'woocommerce'
        $notes = sanitize_textarea_field($request->get_param('notes'));

        if ($origin === 'manual') {
            $id = intval($request->get_param('id'));
            $tabla = $wpdb->prefix . 'clientes_manuales';
            $wpdb->update($tabla, ['notas' => $notes], ['id' => $id]);
        } else {
            // WooCommerce - usar tabla de notas WC
            $order_id = intval($request->get_param('order_id'));
            $fecha = sanitize_text_field($request->get_param('date'));
            $tabla = $wpdb->prefix . 'clientes_wc_notas';

            // Verificar si la tabla existe
            $tabla_existe = $wpdb->get_var($wpdb->prepare("SHOW TABLES LIKE %s", $tabla));
            if (!$tabla_existe) {
                return new WP_Error('table_not_found', 'Tabla de notas no existe', ['status' => 500]);
            }

            // Verificar si ya existe una nota
            $existe = $wpdb->get_var($wpdb->prepare(
                "SELECT id FROM {$tabla} WHERE order_id = %d AND fecha_evento = %s",
                $order_id, $fecha
            ));

            if ($existe) {
                $wpdb->update($tabla, ['notas' => $notes], ['order_id' => $order_id, 'fecha_evento' => $fecha]);
            } else {
                $wpdb->insert($tabla, [
                    'order_id' => $order_id,
                    'fecha_evento' => $fecha,
                    'notas' => $notes,
                    'creado_por' => get_current_user_id()
                ]);
            }
        }

        return rest_ensure_response([
            'success' => true,
            'message' => __('Cliente creado correctamente', 'flavor-chat-ia')
        ]);
    }

    /**
     * Obtener clientes unificados (WooCommerce + Manuales)
     */
    public function get_unified_customers($request) {
        global $wpdb;

        $from = sanitize_text_field($request->get_param('from'));
        $to = sanitize_text_field($request->get_param('to'));

        if (empty($from) || empty($to)) {
            return new WP_Error('missing_dates', 'Fechas requeridas', ['status' => 400]);
        }

        $ticket_types = get_option('calendario_experiencias_ticket_types', []);
        $customers = [];

        // 1. Obtener clientes de WooCommerce (reservas)
        $tabla_reservas = $wpdb->prefix . 'reservas_tickets';
        $tabla_notas_wc = $wpdb->prefix . 'clientes_wc_notas';

        $reservas = $wpdb->get_results($wpdb->prepare(
            "SELECT r.*, n.notas as notas_wc
             FROM {$tabla_reservas} r
             LEFT JOIN {$tabla_notas_wc} n ON r.reserva_id = n.order_id AND r.fecha = n.fecha_evento
             WHERE r.fecha BETWEEN %s AND %s AND r.estado != 'cancelado'
             ORDER BY r.fecha DESC",
            $from, $to
        ), ARRAY_A);

        // Agrupar por order_id y fecha
        $wc_grouped = [];
        foreach ($reservas as $r) {
            $order_id = intval($r['reserva_id']);
            $fecha = $r['fecha'];
            $key = $order_id . '_' . $fecha;

            if (!isset($wc_grouped[$key])) {
                // Obtener datos del pedido WooCommerce
                $customer_name = '';
                $customer_email = '';
                $customer_phone = '';

                if ($order_id > 0 && function_exists('wc_get_order')) {
                    $order = wc_get_order($order_id);
                    if ($order) {
                        $customer_name = trim($order->get_billing_first_name() . ' ' . $order->get_billing_last_name());
                        $customer_email = $order->get_billing_email();
                        $customer_phone = $order->get_billing_phone();
                    }
                }

                // Fallback al campo cliente si no hay datos de WC
                if (empty($customer_name)) {
                    $cliente_raw = trim($r['cliente'] ?? '');
                    if (!empty($cliente_raw) && substr($cliente_raw, 0, 1) === '{') {
                        $cliente = json_decode($cliente_raw, true);
                        if ($cliente) {
                            $customer_name = $cliente['nombre'] ?? $cliente['name'] ?? '';
                            $customer_email = $cliente['email'] ?? $customer_email;
                            $customer_phone = $cliente['telefono'] ?? $cliente['phone'] ?? $customer_phone;
                        }
                    } elseif (!empty($cliente_raw)) {
                        $customer_name = $cliente_raw;
                    }
                }

                $wc_grouped[$key] = [
                    'id' => $order_id,
                    'order_id' => $order_id,
                    'name' => $customer_name ?: 'Sin nombre',
                    'email' => $customer_email,
                    'phone' => $customer_phone,
                    'date' => $fecha,
                    'notes' => $r['notas_wc'] ?? '',
                    'tickets' => [],
                    'origin' => 'woocommerce',
                    'status' => $r['estado']
                ];
            }

            // Añadir ticket
            $wc_grouped[$key]['tickets'][] = [
                'slug' => $r['ticket_slug'],
                'name' => $ticket_types[$r['ticket_slug']]['name'] ?? $r['ticket_slug'],
                'quantity' => 1,
                'code' => $r['ticket_code']
            ];
        }

        $customers = array_values($wc_grouped);

        // 2. Obtener clientes manuales
        $tabla_manuales = $wpdb->prefix . 'clientes_manuales';
        $tabla_manuales_tickets = $wpdb->prefix . 'clientes_manuales_tickets';

        $tabla_existe = $wpdb->get_var($wpdb->prepare("SHOW TABLES LIKE %s", $tabla_manuales));
        if ($tabla_existe) {
            $manuales = $wpdb->get_results($wpdb->prepare(
                "SELECT c.*,
                        GROUP_CONCAT(CONCAT(t.ticket_slug, ':', t.cantidad) SEPARATOR ',') as tickets_raw
                 FROM {$tabla_manuales} c
                 LEFT JOIN {$tabla_manuales_tickets} t ON c.id = t.cliente_manual_id
                 WHERE c.fecha_evento BETWEEN %s AND %s AND c.estado = 'activo'
                 GROUP BY c.id
                 ORDER BY c.fecha_evento DESC",
                $from, $to
            ), ARRAY_A);

            foreach ($manuales as $m) {
                $tickets = [];
                if (!empty($m['tickets_raw'])) {
                    foreach (explode(',', $m['tickets_raw']) as $ticket_str) {
                        $parts = explode(':', $ticket_str);
                        if (count($parts) === 2) {
                            $slug = $parts[0];
                            $qty = intval($parts[1]);
                            $tickets[] = [
                                'slug' => $slug,
                                'name' => $ticket_types[$slug]['name'] ?? $slug,
                                'quantity' => $qty
                            ];
                        }
                    }
                }

                $customers[] = [
                    'id' => intval($m['id']),
                    'name' => $m['nombre'],
                    'email' => $m['email'] ?? '',
                    'phone' => $m['telefono'] ?? '',
                    'date' => $m['fecha_evento'],
                    'notes' => $m['notas'] ?? '',
                    'tickets' => $tickets,
                    'origin' => 'manual',
                    'status' => $m['estado']
                ];
            }
        }

        // Ordenar por fecha descendente
        usort($customers, function($a, $b) {
            return strcmp($b['date'], $a['date']);
        });

        return rest_ensure_response([
            'success' => true,
            'customers' => $customers,
            'total' => count($customers)
        ]);
    }

    // ==========================================
    // NOTIFICACIONES PUSH
    // ==========================================

    /**
     * Registra token de push notification
     */
    public function register_push_token($request) {
        $token = sanitize_text_field($request->get_param('token'));
        $platform = sanitize_text_field($request->get_param('platform')); // 'android' o 'ios'
        $user_id = get_current_user_id();

        if (empty($token)) {
            return new WP_Error('missing_token', 'Token requerido', ['status' => 400]);
        }

        // Guardar token
        $tokens = get_option('chat_ia_push_tokens', []);
        $tokens[$user_id] = [
            'token' => $token,
            'platform' => $platform,
            'updated_at' => current_time('mysql'),
        ];
        update_option('chat_ia_push_tokens', $tokens);

        return [
            'success' => true,
            'message' => __('Token válido', 'flavor-chat-ia'),
        ];
    }

    // ==========================================
    // CLIENTE: MIS RESERVAS / BILLETERA
    // ==========================================

    // ==========================================
    // VERIFICACIÓN DE EMAIL PARA BILLETERA
    // ==========================================

    /**
     * Envía código de verificación por email
     */
    public function send_verification_code($request) {
        $email = sanitize_email($request->get_param('email'));
        $device_id = sanitize_text_field($request->get_param('device_id'));

        if (empty($email) || !is_email($email)) {
            return new WP_Error('invalid_email', 'Email no válido', ['status' => 400]);
        }

        if (empty($device_id)) {
            return new WP_Error('missing_device', 'Se requiere device_id', ['status' => 400]);
        }

        // Rate limiting: máx 3 envíos por email cada 15 minutos
        $rate_limit_key = 'email_rate_' . md5($email);
        $rate_data = get_transient($rate_limit_key);

        if ($rate_data && $rate_data['count'] >= 3) {
            $wait_time = ceil(($rate_data['reset_at'] - time()) / 60);
            return new WP_Error(
                'rate_limited',
                sprintf('Demasiados intentos. Espera %d minutos.', max(1, $wait_time)),
                ['status' => 429]
            );
        }

        // Actualizar contador de rate limit
        if (!$rate_data) {
            $rate_data = ['count' => 0, 'reset_at' => time() + (15 * 60)];
        }
        $rate_data['count']++;
        set_transient($rate_limit_key, $rate_data, 15 * 60);

        // Generar código de 6 dígitos
        $code = str_pad(random_int(0, 999999), 6, '0', STR_PAD_LEFT);

        // Guardar código con expiración (15 minutos)
        $verification_data = [
            'code' => $code,
            'email' => $email,
            'device_id' => $device_id,
            'created_at' => time(),
            'expires_at' => time() + (15 * 60), // 15 minutos
            'attempts' => 0,
        ];

        $transient_key = 'email_verify_' . md5($device_id);
        set_transient($transient_key, $verification_data, 15 * 60);

        // Enviar email
        $site_name = get_bloginfo('name');
        $subject = sprintf('[%s] Tu código de verificación', $site_name);

        $message = sprintf(
            "Hola,\n\n" .
            "Tu código de verificación para acceder a tu billetera de tickets en %s es:\n\n" .
            "🔐 %s\n\n" .
            "Este código expira en 15 minutos.\n\n" .
            "Si no has solicitado este código, puedes ignorar este email.\n\n" .
            "Saludos,\n%s",
            $site_name,
            $code,
            $site_name
        );

        $headers = ['Content-Type: text/plain; charset=UTF-8'];

        $sent = wp_mail($email, $subject, $message, $headers);

        if (!$sent) {
            error_log('Chat IA Mobile - Error enviando email de verificación a: ' . $email);
            return new WP_Error('email_error', 'Error al enviar el código. Inténtalo de nuevo.', ['status' => 500]);
        }

        return rest_ensure_response([
            'success' => true,
            'message' => __('Cliente creado correctamente', 'flavor-chat-ia') . $this->mask_email($email),
            'expires_in' => 15 * 60, // segundos
        ]);
    }

    /**
     * Verifica el código de email
     */
    public function verify_email_code($request) {
        $code = sanitize_text_field($request->get_param('code'));
        $device_id = sanitize_text_field($request->get_param('device_id'));

        if (empty($code) || empty($device_id)) {
            return new WP_Error('missing_data', 'Código y device_id requeridos', ['status' => 400]);
        }

        $transient_key = 'email_verify_' . md5($device_id);
        $verification_data = get_transient($transient_key);

        if (!$verification_data) {
            return new WP_Error('expired', 'El código ha expirado. Solicita uno nuevo.', ['status' => 400]);
        }

        // Verificar intentos
        if ($verification_data['attempts'] >= 5) {
            delete_transient($transient_key);
            return new WP_Error('max_attempts', 'Demasiados intentos fallidos. Solicita un nuevo código.', ['status' => 400]);
        }

        // Verificar código
        if ($verification_data['code'] !== $code) {
            // Incrementar intentos
            $verification_data['attempts']++;
            set_transient($transient_key, $verification_data, 15 * 60);

            $remaining = 5 - $verification_data['attempts'];
            return new WP_Error(
                'invalid_code',
                sprintf('Código incorrecto. Te quedan %d intentos.', $remaining),
                ['status' => 400]
            );
        }

        // Código correcto - guardar email verificado para este dispositivo
        $verified_key = 'verified_email_' . md5($device_id);
        $verified_data = [
            'email' => $verification_data['email'],
            'verified_at' => time(),
            'device_id' => $device_id,
        ];

        // Guardar por 30 días
        set_transient($verified_key, $verified_data, 30 * DAY_IN_SECONDS);

        // Limpiar datos de verificación
        delete_transient($transient_key);

        return rest_ensure_response([
            'success' => true,
            'message' => __('Token válido', 'flavor-chat-ia'),
            'email' => $verification_data['email'],
            'verified_at' => date('c'),
        ]);
    }

    /**
     * Obtiene el estado de verificación del email
     */
    public function get_email_verification_status($request) {
        $device_id = sanitize_text_field($request->get_param('device_id'));

        if (empty($device_id)) {
            return new WP_Error('missing_device', 'Se requiere device_id', ['status' => 400]);
        }

        $verified_key = 'verified_email_' . md5($device_id);
        $verified_data = get_transient($verified_key);

        if (!$verified_data) {
            return rest_ensure_response([
                'success' => true,
                'verified' => false,
                'email' => null,
            ]);
        }

        return rest_ensure_response([
            'success' => true,
            'verified' => true,
            'email' => $verified_data['email'],
            'verified_at' => date('c', $verified_data['verified_at']),
        ]);
    }

    /**
     * Enmascara un email para mostrar (j***@e***.com)
     */
    private function mask_email($email) {
        $parts = explode('@', $email);
        if (count($parts) !== 2) return $email;

        $name = $parts[0];
        $domain = $parts[1];

        $masked_name = substr($name, 0, 1) . str_repeat('*', max(3, strlen($name) - 1));

        $domain_parts = explode('.', $domain);
        $masked_domain = substr($domain_parts[0], 0, 1) . str_repeat('*', max(2, strlen($domain_parts[0]) - 1));
        $masked_domain .= '.' . end($domain_parts);

        return $masked_name . '@' . $masked_domain;
    }

    // ==========================================
    // BILLETERA DE TICKETS
    // ==========================================

    /**
     * Obtiene las reservas de un cliente por email verificado o device_id
     * Para la "billetera de tickets" del cliente
     */
    public function get_client_reservations($request) {
        global $wpdb;

        $email = sanitize_email($request->get_param('email'));
        $device_id = sanitize_text_field($request->get_param('device_id'));
        $include_past = $request->get_param('include_past') === 'true';

        if (empty($email) && empty($device_id)) {
            return new WP_Error('missing_identifier', 'Se requiere email o device_id', ['status' => 400]);
        }

        // Verificar que el email está verificado para este dispositivo
        if (!empty($device_id)) {
            $verified_key = 'verified_email_' . md5($device_id);
            $verified_data = get_transient($verified_key);

            if ($verified_data) {
                // Usar el email verificado en lugar del proporcionado
                $email = $verified_data['email'];
            } elseif (!empty($email)) {
                // Email proporcionado pero no verificado - requiere verificación
                return rest_ensure_response([
                    'success' => true,
                    'requires_verification' => true,
                    'message' => __('reservations', 'flavor-chat-ia'),
                    'reservations' => [],
                ]);
            }
        }

        $tabla_tickets = $wpdb->prefix . 'reservas_tickets';

        // Verificar que existe la tabla
        if (!Flavor_Chat_Helpers::tabla_existe($tabla_tickets)) {
            return rest_ensure_response([
                'success' => true,
                'reservations' => [],
                'message' => __('Token válido', 'flavor-chat-ia'),
            ]);
        }

        // Construir query
        $where_conditions = [];
        $where_values = [];

        if (!empty($email)) {
            $where_conditions[] = "email = %s";
            $where_values[] = $email;
        }

        $where_sql = implode(' OR ', $where_conditions);

        // Filtrar por fecha si no incluye pasadas
        $date_filter = '';
        if (!$include_past) {
            $date_filter = "AND fecha >= CURDATE()";
        }

        $query = $wpdb->prepare(
            "SELECT * FROM {$tabla_tickets}
             WHERE ({$where_sql}) {$date_filter}
             ORDER BY fecha DESC, id DESC
             LIMIT 100",
            ...$where_values
        );

        $reservations = $wpdb->get_results($query, ARRAY_A);

        // Formatear para la app
        $formatted = [];
        foreach ($reservations as $res) {
            $formatted[] = [
                'id' => intval($res['id']),
                'ticket_code' => $res['codigo_ticket'] ?? $res['ticket_code'] ?? '',
                'ticket_slug' => $res['ticket_slug'] ?? '',
                'ticket_name' => $res['ticket_nombre'] ?? $res['ticket_name'] ?? '',
                'date' => $res['fecha'] ?? '',
                'time' => $res['hora'] ?? '',
                'status' => $res['estado'] ?? 'pendiente',
                'status_display' => $this->get_client_status_display($res['estado'] ?? 'pendiente'),
                'customer_name' => $res['nombre'] ?? '',
                'customer_email' => $res['email'] ?? '',
                'checkin' => $res['checkin'] ?? null,
                'order_id' => $res['order_id'] ?? null,
                'qr_data' => $res['codigo_ticket'] ?? $res['ticket_code'] ?? '',
                'created_at' => $res['created_at'] ?? '',
                'experience_name' => $res['experiencia_nombre'] ?? '',
                'price' => floatval($res['precio'] ?? 0),
            ];
        }

        return rest_ensure_response([
            'success' => true,
            'reservations' => $formatted,
            'total' => count($formatted),
            'cache_until' => date('Y-m-d H:i:s', strtotime('+15 minutes')),
        ]);
    }

    /**
     * Obtiene una reserva específica por código (para verificación QR)
     */
    public function get_client_reservation_by_code($request) {
        $rate_limit = Flavor_API_Rate_Limiter::check_rate_limit('get');
        if (is_wp_error($rate_limit)) {
            return $rate_limit;
        }

        global $wpdb;

        $code = sanitize_text_field($request->get_param('code'));

        if (empty($code)) {
            return new WP_Error('missing_code', __('Código requerido', 'flavor-chat-ia'), ['status' => 400]);
        }

        $tabla_tickets = $wpdb->prefix . 'reservas_tickets';

        $reservation = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$tabla_tickets} WHERE codigo_ticket = %s OR ticket_code = %s LIMIT 1",
            $code, $code
        ), ARRAY_A);

        if (!$reservation) {
            return rest_ensure_response([
                'success' => false,
                'message' => __('Reserva no encontrada', 'flavor-chat-ia'),
            ]);
        }

        return rest_ensure_response([
            'success' => true,
            'reservation' => [
                'id' => intval($reservation['id']),
                'ticket_code' => $reservation['codigo_ticket'] ?? $reservation['ticket_code'] ?? '',
                'ticket_slug' => $reservation['ticket_slug'] ?? '',
                'ticket_name' => $reservation['ticket_nombre'] ?? $reservation['ticket_name'] ?? '',
                'date' => $reservation['fecha'] ?? '',
                'time' => $reservation['hora'] ?? '',
                'status' => $reservation['estado'] ?? 'pendiente',
                'status_display' => $this->get_client_status_display($reservation['estado'] ?? 'pendiente'),
                'checkin' => $reservation['checkin'] ?? null,
                'qr_data' => $reservation['codigo_ticket'] ?? $reservation['ticket_code'] ?? '',
            ],
        ]);
    }

    /**
     * Helper para mostrar estado en español (cliente)
     */
    private function get_client_status_display($status) {
        $displays = [
            'pendiente' => 'Pendiente',
            'usado' => 'Usado',
            'cancelado' => 'Cancelado',
            'expirado' => 'Expirado',
        ];
        return $displays[$status] ?? ucfirst($status);
    }

    /**
     * Configuración de la app cliente (pantalla de inicio, etc.)
     * Permite personalizar desde WordPress admin
     */
    public function get_client_app_config($request) {
        $settings = get_option('chat_ia_settings', []);
        $mobile_config = $settings['mobile_apps'] ?? [];
        $client_config = $mobile_config['client_config'] ?? [];

        // Obtener módulos activos desde flavor_apps_config
        $app_config = get_option('flavor_apps_config', []);
        $enabled_modules = [];
        if (isset($app_config['modules']) && is_array($app_config['modules'])) {
            foreach ($app_config['modules'] as $module_id => $module_settings) {
                if (!empty($module_settings['enabled'])) {
                    $enabled_modules[] = $module_id;
                }
            }
        }

        // Tabs base (siempre disponibles)
        $base_tabs = [
            ['id' => 'info', 'label' => 'Info', 'icon' => 'info', 'enabled' => true, 'order' => 0],
        ];

        // Tabs adicionales según configuración de WP
        $order = 1;
        $additional_tabs = [];

        // Chat (si está habilitado)
        if (isset($client_config['chat_enabled']) && $client_config['chat_enabled']) {
            $additional_tabs[] = ['id' => 'chat', 'label' => 'Chat', 'icon' => 'chat_bubble', 'enabled' => true, 'order' => $order++];
        }

        // Reservas (si calendario-experiencias está activo)
        if (isset($client_config['reservations_enabled']) && $client_config['reservations_enabled']) {
            $additional_tabs[] = ['id' => 'reservations', 'label' => 'Reservar', 'icon' => 'calendar_today', 'enabled' => true, 'order' => $order++];
            $additional_tabs[] = ['id' => 'my_tickets', 'label' => 'Mis Tickets', 'icon' => 'confirmation_number', 'enabled' => true, 'order' => $order++];
        }

        // Módulos dinámicos desde flavor_apps_config
        $module_metadata = [
            'grupos_consumo' => ['label' => 'Grupos Consumo', 'icon' => 'groups'],
            'grupos-consumo' => ['label' => 'Grupos Consumo', 'icon' => 'groups'],
            'banco_tiempo' => ['label' => 'Banco de Tiempo', 'icon' => 'handyman'],
            'banco-tiempo' => ['label' => 'Banco de Tiempo', 'icon' => 'handyman'],
            'marketplace' => ['label' => 'Marketplace', 'icon' => 'store'],
            'eventos' => ['label' => 'Eventos', 'icon' => 'event'],
            'socios' => ['label' => 'Socios', 'icon' => 'card_membership'],
            'facturas' => ['label' => 'Facturas', 'icon' => 'receipt'],
            'chat-grupos' => ['label' => 'Grupos', 'icon' => 'forum'],
            'chat_grupos' => ['label' => 'Grupos', 'icon' => 'forum'],
            'chat-interno' => ['label' => 'Mensajes', 'icon' => 'message'],
            'chat_interno' => ['label' => 'Mensajes', 'icon' => 'message'],
        ];

        foreach ($enabled_modules as $module_id) {
            // Omitir módulos base que ya están gestionados
            if (in_array($module_id, ['chat', 'reservas', 'reservations'])) {
                continue;
            }

            $metadata = $module_metadata[$module_id] ?? [
                'label' => ucwords(str_replace(['_', '-'], ' ', $module_id)),
                'icon' => 'extension'
            ];

            $additional_tabs[] = [
                'id' => $module_id,
                'label' => $metadata['label'],
                'icon' => $metadata['icon'],
                'enabled' => true,
                'order' => $order++
            ];
        }

        // Combinar tabs base con adicionales
        $tabs = array_merge($base_tabs, $additional_tabs);

        // Permitir override manual si existe
        if (!empty($client_config['tabs'])) {
            $tabs = $client_config['tabs'];
        }

        $default_tab = $client_config['default_tab'] ?? 'info';

        // Funcionalidades habilitadas
        $features = [
            'chat_enabled' => $client_config['chat_enabled'] ?? true,
            'reservations_enabled' => $client_config['reservations_enabled'] ?? true,
            'my_tickets_enabled' => $client_config['my_tickets_enabled'] ?? true,
            'offline_tickets' => $client_config['offline_tickets'] ?? true,
            'push_notifications' => $client_config['push_notifications'] ?? false,
            'biometric_auth' => $client_config['biometric_auth'] ?? false,
        ];

        // Colores extendidos desde configuración
        $saved_colors = $mobile_config['colors'] ?? [];
        $default_colors = [
            'primary' => '#2196F3',
            'secondary' => '#FF9800',
            'accent' => '#4CAF50',
            'background' => '#FFFFFF',
            'surface' => '#F5F5F5',
            'text_primary' => '#212121',
            'text_secondary' => '#757575',
            'error' => '#F44336',
            'success' => '#4CAF50',
        ];
        $colors = array_merge($default_colors, $saved_colors);

        // Branding extendido
        $saved_branding = $mobile_config['branding'] ?? [];
        $logo_url = $this->get_mobile_logo_url($saved_branding['logo_id'] ?? 0);
        $logo_dark_url = $this->get_mobile_logo_url($saved_branding['logo_dark_id'] ?? 0);

        $branding = [
            'primary_color' => $mobile_config['primary_color'] ?? $colors['primary'],
            'logo_url' => $logo_url ?: $this->get_client_logo_url(),
            'logo_dark_url' => $logo_dark_url,
            'business_name' => $mobile_config['business_name'] ?? get_bloginfo('name'),
            'app_name' => $saved_branding['app_name'] ?: ($mobile_config['business_name'] ?? get_bloginfo('name')),
            'welcome_message' => $client_config['welcome_message'] ?? '¡Bienvenido! ¿En qué podemos ayudarte?',
        ];

        // Secciones de Info desde flavor_apps_config
        $info_sections_config = $app_config['info_sections'] ?? [];

        // Si no hay configuración, usar defaults
        if (empty($info_sections_config)) {
            $info_sections_config = [
                'header' => ['label' => 'Cabecera', 'icon' => 'image', 'enabled' => true, 'order' => 0, 'type' => 'predefined'],
                'about' => ['label' => 'Sobre nosotros', 'icon' => 'info', 'enabled' => true, 'order' => 1, 'type' => 'predefined'],
                'hours' => ['label' => 'Horarios', 'icon' => 'access_time', 'enabled' => true, 'order' => 2, 'type' => 'predefined'],
                'contact' => ['label' => 'Contacto', 'icon' => 'phone', 'enabled' => true, 'order' => 3, 'type' => 'predefined'],
                'location' => ['label' => 'Ubicación', 'icon' => 'location_on', 'enabled' => true, 'order' => 4, 'type' => 'predefined'],
                'social' => ['label' => 'Redes sociales', 'icon' => 'share', 'enabled' => true, 'order' => 5, 'type' => 'predefined'],
            ];
        }

        // Filtrar solo las secciones habilitadas y ordenarlas
        $info_sections = [];
        foreach ($info_sections_config as $section_id => $section_data) {
            if (is_array($section_data) && !empty($section_data['enabled'])) {
                $info_sections[] = [
                    'id' => $section_id,
                    'label' => $section_data['label'] ?? $section_id,
                    'icon' => $section_data['icon'] ?? 'article',
                    'order' => $section_data['order'] ?? 0,
                    'type' => $section_data['type'] ?? 'predefined',
                ];
            }
        }

        // Ordenar por orden
        usort($info_sections, function($a, $b) {
            return ($a['order'] ?? 0) - ($b['order'] ?? 0);
        });

        // Textos personalizados extendidos
        $saved_texts = $mobile_config['texts'] ?? [];
        $texts = [
            'welcome' => $saved_texts['welcome'] ?? '',
            'info_title' => $saved_texts['info_title'] ?? 'Información',
            'chat_placeholder' => $saved_texts['chat_placeholder'] ?? 'Escribe tu mensaje...',
            'reservations_title' => $saved_texts['reservations_title'] ?? 'Reservas',
            'no_reservations' => $saved_texts['no_reservations'] ?? 'No tienes reservas',
            'no_tickets' => $saved_texts['no_tickets'] ?? 'No tienes tickets',
        ];

        // Orden y tabs habilitadas
        $tabs_enabled = [];
        $tab_order = [];
        foreach ($tabs as $tab) {
            if (!empty($tab['enabled'])) {
                $tabs_enabled[] = $tab['id'];
            }
            $tab_order[] = $tab['id'];
        }

        return rest_ensure_response([
            'success' => true,
            'config' => [
                'tabs' => $tabs,
                'default_tab' => $default_tab,
                'features' => $features,
                'branding' => $branding,
                'colors' => $colors,
                'info_sections' => $info_sections,
                'texts' => $texts,
                'tab_order' => $tab_order,
                'tabs_enabled' => $tabs_enabled,
                'version' => '2.0.0',
                'cache_duration' => 3600,
            ],
        ]);
    }

    /**
     * Helper para obtener URL del logo móvil desde media ID
     */
    private function get_mobile_logo_url($logo_id) {
        if (!$logo_id) {
            return '';
        }
        return wp_get_attachment_image_url($logo_id, 'full') ?: '';
    }

    /**
     * Helper para obtener URL del logo (cliente)
     */
    private function get_client_logo_url() {
        $logo_id = get_theme_mod('custom_logo');
        if ($logo_id) {
            return wp_get_attachment_image_url($logo_id, 'full');
        }
        $site_icon_id = get_option('site_icon');
        if ($site_icon_id) {
            return wp_get_attachment_image_url($site_icon_id, 'full');
        }
        return '';
    }

    // ==========================================
    // AUTOCONFIGURACIÓN IA PARA APPS
    // ==========================================

    /**
     * Genera configuración completa para personalizar apps móviles
     * Usa IA para analizar el sitio y sugerir colores, nombres, etc.
     */
    public function generate_app_config($request) {
        // Recopilar información del sitio
        $site_info = $this->analyze_site_for_app_config();

        // Intentar mejorar con IA si está disponible
        $ai_enhanced = $this->enhance_config_with_ai($site_info);

        if ($ai_enhanced) {
            $config = $ai_enhanced;
        } else {
            $config = $this->build_basic_config($site_info);
        }

        return rest_ensure_response($config);
    }

    /**
     * Vista previa de la configuración (para admin)
     */
    public function preview_app_config($request) {
        $config = $this->generate_app_config($request);
        $data = $config->get_data();

        // Añadir info adicional para preview
        $data['_preview'] = true;
        $data['_generated_at'] = current_time('c');
        $data['_can_regenerate'] = true;

        return rest_ensure_response($data);
    }

    /**
     * Analiza el sitio para extraer información relevante
     */
    private function analyze_site_for_app_config() {
        // Información básica
        $site_name = get_bloginfo('name');
        $site_url = home_url();
        $tagline = get_bloginfo('description');

        // Logo
        $logo_url = '';
        $logo_id = get_theme_mod('custom_logo');
        if ($logo_id) {
            $logo_url = wp_get_attachment_image_url($logo_id, 'full');
        }
        if (!$logo_url) {
            $site_icon_id = get_option('site_icon');
            if ($site_icon_id) {
                $logo_url = wp_get_attachment_image_url($site_icon_id, 'full');
            }
        }

        // Colores del tema
        $colors = $this->extract_theme_colors();

        // Información de contacto
        $contact = $this->extract_contact_info();

        // Redes sociales
        $social = $this->extract_social_links();

        // Páginas principales
        $pages = $this->extract_important_pages();

        // Tipo de negocio (intentar detectar)
        $business_type = $this->detect_business_type();

        return [
            'site_name' => $site_name,
            'site_url' => $site_url,
            'tagline' => $tagline,
            'logo_url' => $logo_url,
            'colors' => $colors,
            'contact' => $contact,
            'social' => $social,
            'pages' => $pages,
            'business_type' => $business_type,
        ];
    }

    /**
     * Extrae colores del tema activo
     */
    private function extract_theme_colors() {
        $colors = [
            'primary' => '#2196F3',
            'secondary' => '#4CAF50',
            'accent' => '#FF9800',
        ];

        // Intentar obtener de theme.json (WordPress 5.8+)
        if (function_exists('wp_get_global_settings')) {
            $settings = wp_get_global_settings();
            if (!empty($settings['color']['palette']['theme'])) {
                foreach ($settings['color']['palette']['theme'] as $color) {
                    $slug = strtolower($color['slug'] ?? '');
                    if (strpos($slug, 'primary') !== false) {
                        $colors['primary'] = $color['color'];
                    } elseif (strpos($slug, 'secondary') !== false) {
                        $colors['secondary'] = $color['color'];
                    } elseif (strpos($slug, 'accent') !== false) {
                        $colors['accent'] = $color['color'];
                    }
                }
            }
        }

        // Intentar obtener de customizer
        $customizer_primary = get_theme_mod('primary_color');
        if ($customizer_primary) {
            $colors['primary'] = $customizer_primary;
        }

        // Intentar extraer del logo (obtener URL directamente para evitar recursión)
        $logo_url = $this->get_client_logo_url();
        if (!empty($logo_url)) {
            $logo_colors = $this->extract_colors_from_image($logo_url);
            if ($logo_colors) {
                $colors = array_merge($colors, $logo_colors);
            }
        }

        return $colors;
    }

    /**
     * Extrae colores dominantes de una imagen
     */
    private function extract_colors_from_image($image_url) {
        if (empty($image_url)) return null;

        try {
            // Descargar imagen
            $response = wp_remote_get($image_url, ['timeout' => 5]);
            if (is_wp_error($response)) return null;

            $image_data = wp_remote_retrieve_body($response);
            if (empty($image_data)) return null;

            // Crear imagen GD
            $image = @imagecreatefromstring($image_data);
            if (!$image) return null;

            $width = imagesx($image);
            $height = imagesy($image);

            // Muestrear colores
            $color_counts = [];
            $sample_step = max(1, (int)($width * $height / 1000)); // Muestrear ~1000 pixels

            for ($i = 0; $i < $width * $height; $i += $sample_step) {
                $x = $i % $width;
                $y = (int)($i / $width);
                $rgb = imagecolorat($image, $x, $y);
                $r = ($rgb >> 16) & 0xFF;
                $g = ($rgb >> 8) & 0xFF;
                $b = $rgb & 0xFF;

                // Ignorar colores muy claros (blancos) o muy oscuros (negros)
                $brightness = ($r + $g + $b) / 3;
                if ($brightness < 30 || $brightness > 225) continue;

                // Redondear para agrupar colores similares
                $r = round($r / 32) * 32;
                $g = round($g / 32) * 32;
                $b = round($b / 32) * 32;

                $hex = sprintf("#%02x%02x%02x", $r, $g, $b);
                $color_counts[$hex] = ($color_counts[$hex] ?? 0) + 1;
            }

            imagedestroy($image);

            if (empty($color_counts)) return null;

            // Ordenar por frecuencia
            arsort($color_counts);
            $dominant_colors = array_keys(array_slice($color_counts, 0, 3));

            return [
                'primary' => $dominant_colors[0] ?? '#2196F3',
                'secondary' => $dominant_colors[1] ?? '#4CAF50',
                'accent' => $dominant_colors[2] ?? '#FF9800',
            ];
        } catch (Exception $e) {
            return null;
        }
    }

    /**
     * Intenta detectar el tipo de negocio
     */
    private function detect_business_type() {
        $site_name = strtolower(get_bloginfo('name'));
        $tagline = strtolower(get_bloginfo('description'));
        $combined = $site_name . ' ' . $tagline;

        $types = [
            'zoo' => ['zoo', 'animal', 'safari', 'fauna', 'wildlife'],
            'park' => ['parque', 'park', 'naturaleza', 'nature', 'aventura'],
            'museum' => ['museo', 'museum', 'exposición', 'exhibition', 'arte', 'art'],
            'restaurant' => ['restaurante', 'restaurant', 'café', 'cafetería', 'gastro'],
            'hotel' => ['hotel', 'hostal', 'alojamiento', 'accommodation'],
            'spa' => ['spa', 'wellness', 'relax', 'masaje', 'balneario'],
            'gym' => ['gym', 'gimnasio', 'fitness', 'crossfit', 'deporte'],
            'tour' => ['tour', 'excursión', 'viaje', 'travel', 'guía'],
            'event' => ['evento', 'event', 'concierto', 'festival', 'teatro'],
        ];

        foreach ($types as $type => $keywords) {
            foreach ($keywords as $keyword) {
                if (strpos($combined, $keyword) !== false) {
                    return $type;
                }
            }
        }

        return 'generic';
    }

    /**
     * Construye configuración básica sin IA
     */
    private function build_basic_config($site_info) {
        $site_name = $site_info['site_name'];
        $clean_name = preg_replace('/[^a-zA-Z0-9]/', '', strtolower($site_name));
        $clean_name = substr($clean_name, 0, 20);

        return [
            'business_name' => $site_name,
            'app_id' => 'com.' . $clean_name . '.app',
            'client_app_name' => $site_name,
            'admin_app_name' => $site_name . ' Admin',
            'deep_link_scheme' => $clean_name,
            'server_url' => $site_info['site_url'],
            'logo_url' => $site_info['logo_url'],
            'colors' => $site_info['colors'],
            'developer' => [
                'name' => $site_name,
                'email' => $site_info['contact']['email'] ?? get_option('admin_email'),
                'phone' => $site_info['contact']['phone'] ?? '',
            ],
            'contact' => $site_info['contact'],
            'social' => $site_info['social'],
            '_generated_by' => 'basic',
            '_business_type' => $site_info['business_type'],
        ];
    }

    /**
     * Mejora la configuración usando IA
     */
    private function enhance_config_with_ai($site_info) {
        // Verificar si la IA está disponible
        $chat_ia_settings = get_option('chat_ia_settings', []);
        $api_key = $chat_ia_settings['anthropic_api_key'] ?? '';

        if (empty($api_key)) {
            return null; // No hay API key, usar config básica
        }

        try {
            // Construir prompt para la IA
            $prompt = $this->build_ai_prompt($site_info);

            // Llamar a la IA
            $response = $this->call_anthropic_api($api_key, $prompt);

            if (!$response) {
                return null;
            }

            // Parsear respuesta
            $ai_config = $this->parse_ai_response($response, $site_info);

            return $ai_config;
        } catch (Exception $e) {
            error_log('Chat IA - Error en autoconfiguración IA: ' . $e->getMessage());
            return null;
        }
    }

    /**
     * Construye el prompt para la IA
     */
    private function build_ai_prompt($site_info) {
        $json_info = json_encode($site_info, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);

        return <<<PROMPT
Eres un experto en branding y apps móviles. Analiza esta información de un sitio web y genera la configuración óptima para sus apps móviles.

INFORMACIÓN DEL SITIO:
$json_info

GENERA UN JSON con esta estructura exacta (responde SOLO con el JSON, sin explicaciones):
{
  "business_name": "Nombre comercial optimizado para app",
  "app_id": "com.dominio.app (formato válido para Google Play)",
  "client_app_name": "Nombre corto para la app cliente (max 12 caracteres)",
  "admin_app_name": "Nombre para la app admin",
  "deep_link_scheme": "esquema de url sin espacios ni caracteres especiales",
  "tagline": "Eslogan corto y atractivo",
  "colors": {
    "primary": "#hex del color principal (debe ser accesible)",
    "secondary": "#hex del color secundario",
    "accent": "#hex del color de acento"
  },
  "suggested_features": ["lista", "de", "características", "recomendadas"],
  "branding_tips": "Consejos breves de branding para la app"
}

Considera:
- El nombre de la app debe ser corto y memorable
- Los colores deben tener buen contraste
- El app_id debe ser válido (solo letras, números y puntos)
- El deep_link_scheme debe ser único y sin espacios
PROMPT;
    }

    /**
     * Llama a la API de Anthropic
     */
    private function call_anthropic_api($api_key, $prompt) {
        $response = wp_remote_post('https://api.anthropic.com/v1/messages', [
            'timeout' => 30,
            'headers' => [
                'Content-Type' => 'application/json',
                'x-api-key' => $api_key,
                'anthropic-version' => '2023-06-01',
            ],
            'body' => json_encode([
                'model' => 'claude-3-haiku-20240307',
                'max_tokens' => 1024,
                'messages' => [
                    ['role' => 'user', 'content' => $prompt]
                ],
            ]),
        ]);

        if (is_wp_error($response)) {
            error_log('Chat IA - Error API: ' . $response->get_error_message());
            return null;
        }

        $body = json_decode(wp_remote_retrieve_body($response), true);

        if (empty($body['content'][0]['text'])) {
            return null;
        }

        return $body['content'][0]['text'];
    }

    /**
     * Parsea la respuesta de la IA
     */
    private function parse_ai_response($response, $site_info) {
        // Extraer JSON de la respuesta
        preg_match('/\{[\s\S]*\}/', $response, $matches);

        if (empty($matches[0])) {
            return null;
        }

        $ai_data = json_decode($matches[0], true);

        if (!$ai_data) {
            return null;
        }

        // Combinar con datos del sitio
        $config = [
            'business_name' => $ai_data['business_name'] ?? $site_info['site_name'],
            'app_id' => $ai_data['app_id'] ?? 'com.example.app',
            'client_app_name' => $ai_data['client_app_name'] ?? $site_info['site_name'],
            'admin_app_name' => $ai_data['admin_app_name'] ?? $site_info['site_name'] . ' Admin',
            'deep_link_scheme' => $ai_data['deep_link_scheme'] ?? 'myapp',
            'server_url' => $site_info['site_url'],
            'logo_url' => $site_info['logo_url'],
            'colors' => $ai_data['colors'] ?? $site_info['colors'],
            'developer' => [
                'name' => $ai_data['business_name'] ?? $site_info['site_name'],
                'email' => $site_info['contact']['email'] ?? get_option('admin_email'),
                'phone' => $site_info['contact']['phone'] ?? '',
            ],
            'contact' => $site_info['contact'],
            'social' => $site_info['social'],
            'tagline' => $ai_data['tagline'] ?? $site_info['tagline'],
            'suggested_features' => $ai_data['suggested_features'] ?? [],
            'branding_tips' => $ai_data['branding_tips'] ?? '',
            '_generated_by' => 'ai',
            '_business_type' => $site_info['business_type'],
            '_ai_model' => 'claude-3-haiku',
        ];

        return $config;
    }

    /**
     * Información básica del sitio para contenido
     */
    private function get_site_info_for_content() {
        $logo_url = $this->get_client_logo_url();

        return [
            'name' => get_bloginfo('name'),
            'tagline' => get_bloginfo('description'),
            'logo' => $logo_url,
            'url' => home_url(),
        ];
    }

    /**
     * Obtiene secciones importantes del sitio
     */
    private function get_important_sections() {
        $sections = [];

        // Páginas del menú principal
        $menu_locations = get_nav_menu_locations();
        $menu_id = $menu_locations['primary'] ?? $menu_locations['main'] ?? null;

        if ($menu_id) {
            $menu_items = wp_get_nav_menu_items($menu_id);
            if ($menu_items) {
                foreach ($menu_items as $item) {
                    if ($item->menu_item_parent == 0 && $item->object === 'page') {
                        $page = get_post($item->object_id);
                        if ($page) {
                            $sections[] = [
                                'title' => $item->title,
                                'summary' => wp_trim_words(strip_tags($page->post_content), 30, '...'),
                                'url' => $item->url,
                                'image' => get_the_post_thumbnail_url($page->ID, 'medium'),
                                'icon' => $this->suggest_icon_for_page($item->title),
                            ];
                        }
                    }
                }
            }
        }

        // Si no hay menú, páginas importantes por título
        if (empty($sections)) {
            $important_slugs = ['about', 'sobre-nosotros', 'servicios', 'services', 'contacto', 'contact', 'horarios', 'tarifas', 'precios'];
            foreach ($important_slugs as $slug) {
                $page = get_page_by_path($slug);
                if ($page && $page->post_status === 'publish') {
                    $sections[] = [
                        'title' => $page->post_title,
                        'summary' => wp_trim_words(strip_tags($page->post_content), 30, '...'),
                        'url' => get_permalink($page),
                        'image' => get_the_post_thumbnail_url($page->ID, 'medium'),
                        'icon' => $this->suggest_icon_for_page($page->post_title),
                    ];
                }
            }
        }

        return array_slice($sections, 0, 6);
    }

    /**
     * Sugiere un icono para una página basado en su título
     */
    private function suggest_icon_for_page($title) {
        $title_lower = strtolower($title);

        $icon_map = [
            'info' => ['sobre', 'about', 'quiénes', 'historia', 'nosotros'],
            'star' => ['servicios', 'services', 'experiencias', 'actividades'],
            'phone' => ['contacto', 'contact'],
            'schedule' => ['horario', 'hours', 'schedule', 'apertura'],
            'euro' => ['precio', 'tarifa', 'price', 'rates', 'entradas', 'tickets'],
            'location_on' => ['ubicación', 'location', 'cómo llegar', 'dirección', 'mapa'],
            'help' => ['faq', 'preguntas', 'ayuda', 'help'],
            'photo_library' => ['galería', 'gallery', 'fotos', 'photos'],
            'article' => ['blog', 'noticias', 'news', 'artículos'],
        ];

        foreach ($icon_map as $icon => $keywords) {
            foreach ($keywords as $keyword) {
                if (strpos($title_lower, $keyword) !== false) {
                    return $icon;
                }
            }
        }

        return 'article';
    }

    /**
     * Obtiene enlaces rápidos
     */
    private function get_quick_links() {
        $links = [];

        // Enlace a reservas (si existe)
        if (class_exists('Reservas_Shortcodes')) {
            $links[] = [
                'title' => 'Reservar',
                'url' => home_url('/reservas'),
                'internal' => true,
            ];
        }

        // Páginas con ciertos slugs
        $quick_slugs = ['horarios', 'tarifas', 'precios', 'mapa', 'faq'];
        foreach ($quick_slugs as $slug) {
            $page = get_page_by_path($slug);
            if ($page && $page->post_status === 'publish') {
                $links[] = [
                    'title' => $page->post_title,
                    'url' => get_permalink($page),
                ];
            }
        }

        return array_slice($links, 0, 4);
    }

    /**
     * Obtiene redes sociales para contenido
     */
    private function get_social_links_for_content() {
        $social = $this->extract_social_links();
        $result = [];

        foreach ($social as $network => $url) {
            if (!empty($url)) {
                $result[] = [
                    'network' => $network,
                    'url' => $url,
                ];
            }
        }

        return $result;
    }

    /**
     * Obtiene información de contacto para contenido
     */
    private function get_contact_for_content() {
        return $this->extract_contact_info();
    }

    /**
     * Obtiene imágenes de galería
     */
    private function get_gallery_images() {
        $images = [];

        // Buscar página de galería
        $gallery_slugs = ['galeria', 'gallery', 'fotos', 'photos'];
        foreach ($gallery_slugs as $slug) {
            $page = get_page_by_path($slug);
            if ($page) {
                // Buscar galerías en el contenido
                if (preg_match_all('/\[gallery[^\]]*ids="([^"]+)"[^\]]*\]/', $page->post_content, $matches)) {
                    foreach (explode(',', $matches[1][0]) as $id) {
                        $url = wp_get_attachment_image_url(trim($id), 'medium');
                        if ($url) {
                            $images[] = [
                                'url' => $url,
                                'title' => get_the_title($id),
                            ];
                        }
                    }
                }
                break;
            }
        }

        // Si no hay galería, obtener imágenes destacadas recientes
        if (empty($images)) {
            $posts = get_posts([
                'numberposts' => 10,
                'meta_key' => '_thumbnail_id',
            ]);

            foreach ($posts as $post) {
                $url = get_the_post_thumbnail_url($post->ID, 'medium');
                if ($url) {
                    $images[] = [
                        'url' => $url,
                        'title' => $post->post_title,
                    ];
                }
            }
        }

        return array_slice($images, 0, 8);
    }

    /**
     * Obtiene noticias/posts recientes
     */
    private function get_news_for_content() {
        $posts = get_posts([
            'numberposts' => 5,
            'post_status' => 'publish',
        ]);

        $news = [];
        foreach ($posts as $post) {
            $news[] = [
                'title' => $post->post_title,
                'summary' => wp_trim_words($post->post_excerpt ?: strip_tags($post->post_content), 20, '...'),
                'url' => get_permalink($post),
                'date' => get_the_date('Y-m-d', $post),
                'image' => get_the_post_thumbnail_url($post->ID, 'thumbnail'),
            ];
        }

        return $news;
    }

    /**
     * Obtiene servicios/experiencias
     */
    private function get_services_for_content() {
        $services = [];

        // Obtener estados del calendario como servicios
        $estados = get_option('calendario_experiencias_estados', []);
        foreach ($estados as $key => $estado) {
            if ($key === 'cerrado') continue;

            $services[] = [
                'id' => $key,
                'name' => $estado['nombre'] ?? $estado['title'] ?? $key,
                'description' => $estado['descripcion'] ?? '',
                'price' => $estado['precio'] ?? null,
                'image' => $estado['imagen'] ?? null,
            ];
        }

        // También buscar productos WooCommerce de tipo reserva
        if (class_exists('WooCommerce')) {
            $products = wc_get_products([
                'limit' => 6,
                'status' => 'publish',
                'type' => 'simple',
            ]);

            foreach ($products as $product) {
                $services[] = [
                    'id' => 'wc_' . $product->get_id(),
                    'name' => $product->get_name(),
                    'description' => wp_trim_words($product->get_short_description(), 15, '...'),
                    'price' => $product->get_price(),
                    'image' => wp_get_attachment_image_url($product->get_image_id(), 'medium'),
                ];
            }
        }

        return array_slice($services, 0, 8);
    }

    /**
     * Obtiene información de horarios
     */
    private function get_schedule_info() {
        $schedule = ['text' => '', 'structured' => []];

        // Buscar en opciones del plugin
        $chat_ia_settings = get_option('chat_ia_settings', []);
        if (!empty($chat_ia_settings['business_hours'])) {
            $schedule['text'] = $chat_ia_settings['business_hours'];
        }

        // Buscar página de horarios
        $pages = ['horarios', 'horario', 'hours', 'schedule'];
        foreach ($pages as $slug) {
            $page = get_page_by_path($slug);
            if ($page && $page->post_status === 'publish') {
                // Extraer texto de horarios
                $content = strip_tags($page->post_content);
                if (strlen($content) < 500) {
                    $schedule['text'] = $content;
                }
                break;
            }
        }

        return $schedule;
    }

    /**
     * Obtiene información de ubicación
     */
    private function get_location_info() {
        $location = [
            'address' => '',
            'coordinates' => null,
            'map_url' => '',
            'directions_url' => '',
        ];

        // Buscar en opciones
        $chat_ia_settings = get_option('chat_ia_settings', []);
        if (!empty($chat_ia_settings['business_address'])) {
            $location['address'] = $chat_ia_settings['business_address'];
        }
        if (!empty($chat_ia_settings['latitude']) && !empty($chat_ia_settings['longitude'])) {
            $location['coordinates'] = [
                'lat' => floatval($chat_ia_settings['latitude']),
                'lng' => floatval($chat_ia_settings['longitude']),
            ];
            $location['map_url'] = 'https://www.google.com/maps?q=' . $chat_ia_settings['latitude'] . ',' . $chat_ia_settings['longitude'];
            $location['directions_url'] = 'https://www.google.com/maps/dir/?api=1&destination=' . $chat_ia_settings['latitude'] . ',' . $chat_ia_settings['longitude'];
        }

        // Si hay dirección pero no coordenadas, generar URL de búsqueda
        if (!empty($location['address']) && empty($location['map_url'])) {
            $encoded_address = urlencode($location['address']);
            $location['map_url'] = 'https://www.google.com/maps/search/?api=1&query=' . $encoded_address;
            $location['directions_url'] = 'https://www.google.com/maps/dir/?api=1&destination=' . $encoded_address;
        }

        return $location;
    }

    /**
     * Enriquece el contenido con IA (resúmenes inteligentes)
     */
    private function enhance_content_with_ai($content, $language) {
        // Verificar si la IA está disponible
        $chat_ia_settings = get_option('chat_ia_settings', []);
        $api_key = $chat_ia_settings['anthropic_api_key'] ?? '';

        if (empty($api_key)) {
            return $content; // Devolver sin mejoras
        }

        // Solo mejorar si hay secciones
        if (empty($content['sections'])) {
            return $content;
        }

        try {
            $prompt = "Eres un asistente que mejora textos para apps móviles. Dado el siguiente contenido de un sitio web, genera resúmenes más atractivos y concisos (máximo 50 palabras cada uno) para cada sección. Responde en " . ($language === 'es' ? 'español' : 'inglés') . ".\n\n";
            $prompt .= "Secciones:\n";

            foreach ($content['sections'] as $i => $section) {
                $prompt .= ($i + 1) . ". {$section['title']}: {$section['summary']}\n";
            }

            $prompt .= "\nResponde en formato JSON: {\"sections\": [{\"title\": \"...\", \"summary\": \"...\"}]}";

            $response = $this->call_anthropic_api($api_key, $prompt);

            if ($response) {
                preg_match('/\{[\s\S]*\}/', $response, $matches);
                if (!empty($matches[0])) {
                    $ai_data = json_decode($matches[0], true);
                    if (!empty($ai_data['sections'])) {
                        foreach ($ai_data['sections'] as $i => $ai_section) {
                            if (isset($content['sections'][$i])) {
                                $content['sections'][$i]['summary'] = $ai_section['summary'];
                            }
                        }
                        $content['_enhanced_by_ai'] = true;
                    }
                }
            }
        } catch (Exception $e) {
            // Ignorar errores de IA
        }

        return $content;
    }

    /**
     * Verifica que el cliente tiene un email verificado via device_id
     *
     * Se usa como permission_callback para endpoints que requieren
     * identificacion del cliente por email verificado (billetera de tickets).
     *
     * @param WP_REST_Request $request Peticion REST
     * @return true|WP_Error True si el email esta verificado, WP_Error en caso contrario
     */
    public function check_client_verified_email($request) {
        $email_proporcionado = sanitize_email($request->get_param('email'));
        $device_id_proporcionado = sanitize_text_field($request->get_param('device_id'));

        // Se requiere al menos un identificador
        if (empty($email_proporcionado) && empty($device_id_proporcionado)) {
            return new WP_Error(
                'missing_identifier',
                __('Se requiere email o device_id para acceder a este recurso.', 'flavor-chat-ia'),
                ['status' => 400]
            );
        }

        // Si hay device_id, verificar que tenga un email verificado asociado
        if (!empty($device_id_proporcionado)) {
            $clave_verificacion = 'verified_email_' . md5($device_id_proporcionado);
            $datos_verificacion = get_transient($clave_verificacion);

            if ($datos_verificacion) {
                // El dispositivo tiene un email verificado, permitir acceso
                return true;
            }

            // Si se proporciono email pero no esta verificado para este dispositivo
            if (!empty($email_proporcionado)) {
                return new WP_Error(
                    'email_not_verified',
                    __('Debes verificar tu email antes de acceder a este recurso.', 'flavor-chat-ia'),
                    ['status' => 403, 'requires_verification' => true]
                );
            }
        }

        // Si solo se proporciono email sin device_id, requerir verificacion
        if (!empty($email_proporcionado) && empty($device_id_proporcionado)) {
            return new WP_Error(
                'device_id_required',
                __('Se requiere device_id para verificar la identidad.', 'flavor-chat-ia'),
                ['status' => 400]
            );
        }

        return new WP_Error(
            'unauthorized_client',
            __('No se pudo verificar tu identidad. Por favor, verifica tu email.', 'flavor-chat-ia'),
            ['status' => 403]
        );
    }

}

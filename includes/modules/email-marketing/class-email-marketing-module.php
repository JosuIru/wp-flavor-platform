<?php
/**
 * Módulo de Email Marketing
 *
 * Newsletter, campañas, automatizaciones y gestión de suscriptores.
 *
 * @package FlavorChatIA
 * @subpackage EmailMarketing
 */

if (!defined('ABSPATH')) {
    exit;
}

class Flavor_Chat_Email_Marketing_Module extends Flavor_Chat_Module_Base {

    /**
     * Constructor
     */
    public function __construct() {
        $this->id = 'email_marketing';
        $this->name = __('Email Marketing', 'flavor-chat-ia');
        $this->description = __('Newsletter, campañas de email y automatizaciones', 'flavor-chat-ia');

        parent::__construct();
    }

    /**
     * Verificar si el módulo puede activarse
     */
    public function can_activate() {
        return true;
    }

    /**
     * Mensaje de error si no puede activarse
     */
    public function get_activation_error() {
        return '';
    }

    /**
     * Inicialización del módulo
     */
    public function init() {
        // Cargar dependencias
        $this->load_dependencies();

        // Hooks de WordPress
        add_action('admin_menu', [$this, 'add_admin_menu'], 20);
        add_action('admin_enqueue_scripts', [$this, 'enqueue_admin_assets']);
        add_action('wp_enqueue_scripts', [$this, 'enqueue_frontend_assets']);

        // Shortcodes
        add_shortcode('em_formulario_suscripcion', [$this, 'shortcode_formulario_suscripcion']);
        add_shortcode('em_preferencias', [$this, 'shortcode_preferencias']);
        add_shortcode('em_confirmar_suscripcion', [$this, 'shortcode_confirmar']);
        add_shortcode('em_darse_baja', [$this, 'shortcode_baja']);

        // AJAX handlers
        add_action('wp_ajax_em_suscribirse', [$this, 'ajax_suscribirse']);
        add_action('wp_ajax_nopriv_em_suscribirse', [$this, 'ajax_suscribirse']);
        add_action('wp_ajax_em_confirmar', [$this, 'ajax_confirmar']);
        add_action('wp_ajax_nopriv_em_confirmar', [$this, 'ajax_confirmar']);
        add_action('wp_ajax_em_darse_baja', [$this, 'ajax_darse_baja']);
        add_action('wp_ajax_nopriv_em_darse_baja', [$this, 'ajax_darse_baja']);

        // Admin AJAX
        add_action('wp_ajax_em_admin_campania', [$this, 'ajax_admin_campania']);
        add_action('wp_ajax_em_admin_lista', [$this, 'ajax_admin_lista']);
        add_action('wp_ajax_em_admin_automatizacion', [$this, 'ajax_admin_automatizacion']);
        add_action('wp_ajax_em_enviar_test', [$this, 'ajax_enviar_test']);
        add_action('wp_ajax_em_importar_suscriptores', [$this, 'ajax_importar_suscriptores']);
        add_action('wp_ajax_em_exportar_suscriptores', [$this, 'ajax_exportar_suscriptores']);

        // Tracking
        add_action('init', [$this, 'handle_tracking']);

        // WP Cron para procesamiento de cola
        add_action('em_procesar_cola_emails', [$this, 'procesar_cola_emails']);
        add_action('em_procesar_automatizaciones', [$this, 'procesar_automatizaciones']);

        if (!wp_next_scheduled('em_procesar_cola_emails')) {
            wp_schedule_event(time(), 'every_minute', 'em_procesar_cola_emails');
        }

        if (!wp_next_scheduled('em_procesar_automatizaciones')) {
            wp_schedule_event(time(), 'every_five_minutes', 'em_procesar_automatizaciones');
        }

        // Hooks para triggers de automatizaciones
        add_action('user_register', [$this, 'trigger_nuevo_usuario']);
        add_action('woocommerce_order_status_completed', [$this, 'trigger_compra_completada']);
        add_action('flavor_socio_registrado', [$this, 'trigger_nuevo_socio']);

        // Registrar intervalos de cron personalizados
        add_filter('cron_schedules', [$this, 'add_cron_intervals']);
    }

    /**
     * Cargar dependencias del módulo
     */
    private function load_dependencies() {
        $module_path = plugin_dir_path(__FILE__);

        require_once $module_path . 'class-email-marketing-api.php';
        require_once $module_path . 'class-email-marketing-sender.php';
        require_once $module_path . 'class-email-marketing-tracking.php';
    }

    /**
     * Añadir intervalos de cron personalizados
     */
    public function add_cron_intervals($schedules) {
        $schedules['every_minute'] = [
            'interval' => 60,
            'display' => __('Cada minuto', 'flavor-chat-ia'),
        ];
        $schedules['every_five_minutes'] = [
            'interval' => 300,
            'display' => __('Cada 5 minutos', 'flavor-chat-ia'),
        ];
        return $schedules;
    }

    /**
     * Configuración por defecto
     */
    public function get_default_settings() {
        return [
            'remitente_nombre' => get_bloginfo('name'),
            'remitente_email' => get_option('admin_email'),
            'responder_a' => get_option('admin_email'),
            'emails_por_hora' => 100,
            'doble_optin_global' => true,
            'tracking_aperturas' => true,
            'tracking_clicks' => true,
            'pie_email_global' => '',
            'color_primario' => '#3b82f6',
            'color_secundario' => '#1e40af',
            'proveedor_smtp' => 'wp_mail',
            'smtp_host' => '',
            'smtp_puerto' => 587,
            'smtp_usuario' => '',
            'smtp_password' => '',
            'smtp_encriptacion' => 'tls',
        ];
    }

    /**
     * Añadir menú de administración
     */
    public function add_admin_menu() {
        add_menu_page(
            __('Email Marketing', 'flavor-chat-ia'),
            __('Email Marketing', 'flavor-chat-ia'),
            'manage_options',
            'flavor-email-marketing',
            [$this, 'render_admin_dashboard'],
            'dashicons-email-alt',
            31
        );

        add_submenu_page(
            'flavor-email-marketing',
            __('Dashboard', 'flavor-chat-ia'),
            __('Dashboard', 'flavor-chat-ia'),
            'manage_options',
            'flavor-email-marketing',
            [$this, 'render_admin_dashboard']
        );

        add_submenu_page(
            'flavor-email-marketing',
            __('Campañas', 'flavor-chat-ia'),
            __('Campañas', 'flavor-chat-ia'),
            'manage_options',
            'flavor-em-campanias',
            [$this, 'render_admin_campanias']
        );

        add_submenu_page(
            'flavor-email-marketing',
            __('Automatizaciones', 'flavor-chat-ia'),
            __('Automatizaciones', 'flavor-chat-ia'),
            'manage_options',
            'flavor-em-automatizaciones',
            [$this, 'render_admin_automatizaciones']
        );

        add_submenu_page(
            'flavor-email-marketing',
            __('Suscriptores', 'flavor-chat-ia'),
            __('Suscriptores', 'flavor-chat-ia'),
            'manage_options',
            'flavor-em-suscriptores',
            [$this, 'render_admin_suscriptores']
        );

        add_submenu_page(
            'flavor-email-marketing',
            __('Listas', 'flavor-chat-ia'),
            __('Listas', 'flavor-chat-ia'),
            'manage_options',
            'flavor-em-listas',
            [$this, 'render_admin_listas']
        );

        add_submenu_page(
            'flavor-email-marketing',
            __('Plantillas', 'flavor-chat-ia'),
            __('Plantillas', 'flavor-chat-ia'),
            'manage_options',
            'flavor-em-plantillas',
            [$this, 'render_admin_plantillas']
        );

        add_submenu_page(
            'flavor-email-marketing',
            __('Estadísticas', 'flavor-chat-ia'),
            __('Estadísticas', 'flavor-chat-ia'),
            'manage_options',
            'flavor-em-estadisticas',
            [$this, 'render_admin_estadisticas']
        );

        add_submenu_page(
            'flavor-email-marketing',
            __('Configuración', 'flavor-chat-ia'),
            __('Configuración', 'flavor-chat-ia'),
            'manage_options',
            'flavor-em-configuracion',
            [$this, 'render_admin_configuracion']
        );
    }

    /**
     * Encolar assets de admin
     */
    public function enqueue_admin_assets($hook) {
        if (strpos($hook, 'flavor-em') === false && strpos($hook, 'flavor-email-marketing') === false) {
            return;
        }

        wp_enqueue_style(
            'flavor-em-admin',
            plugins_url('assets/css/em-admin.css', __FILE__),
            [],
            FLAVOR_CHAT_IA_VERSION
        );

        wp_enqueue_script(
            'flavor-em-admin',
            plugins_url('assets/js/em-admin.js', __FILE__),
            ['jquery', 'wp-util'],
            FLAVOR_CHAT_IA_VERSION,
            true
        );

        // Editor de email
        if (strpos($hook, 'campanias') !== false || strpos($hook, 'plantillas') !== false) {
            wp_enqueue_editor();
            wp_enqueue_media();
        }

        wp_localize_script('flavor-em-admin', 'flavorEM', [
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('flavor_em_admin'),
            'strings' => [
                'confirmDelete' => __('¿Estás seguro de eliminar este elemento?', 'flavor-chat-ia'),
                'sending' => __('Enviando...', 'flavor-chat-ia'),
                'sent' => __('Enviado', 'flavor-chat-ia'),
                'error' => __('Error', 'flavor-chat-ia'),
                'saved' => __('Guardado', 'flavor-chat-ia'),
            ],
        ]);
    }

    /**
     * Encolar assets de frontend
     */
    public function enqueue_frontend_assets() {
        wp_enqueue_style(
            'flavor-em-frontend',
            plugins_url('assets/css/em-frontend.css', __FILE__),
            [],
            FLAVOR_CHAT_IA_VERSION
        );

        wp_enqueue_script(
            'flavor-em-frontend',
            plugins_url('assets/js/em-frontend.js', __FILE__),
            ['jquery'],
            FLAVOR_CHAT_IA_VERSION,
            true
        );

        wp_localize_script('flavor-em-frontend', 'flavorEMFront', [
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('flavor_em_public'),
        ]);
    }

    // =========================================================================
    // SHORTCODES
    // =========================================================================

    /**
     * Shortcode de formulario de suscripción
     */
    public function shortcode_formulario_suscripcion($atts) {
        $atts = shortcode_atts([
            'lista' => 'newsletter-principal',
            'titulo' => __('Suscríbete a nuestro newsletter', 'flavor-chat-ia'),
            'descripcion' => __('Recibe las últimas novedades en tu email.', 'flavor-chat-ia'),
            'boton' => __('Suscribirme', 'flavor-chat-ia'),
            'mostrar_nombre' => 'true',
            'estilo' => 'card',
        ], $atts, 'em_formulario_suscripcion');

        ob_start();
        include plugin_dir_path(__FILE__) . 'templates/formulario-suscripcion.php';
        return ob_get_clean();
    }

    /**
     * Shortcode de preferencias de suscriptor
     */
    public function shortcode_preferencias($atts) {
        $token = isset($_GET['token']) ? sanitize_text_field($_GET['token']) : '';

        if (empty($token)) {
            return '<p class="em-error">' . __('Token no válido.', 'flavor-chat-ia') . '</p>';
        }

        $suscriptor = $this->get_suscriptor_por_token($token);

        if (!$suscriptor) {
            return '<p class="em-error">' . __('Suscriptor no encontrado.', 'flavor-chat-ia') . '</p>';
        }

        ob_start();
        include plugin_dir_path(__FILE__) . 'templates/preferencias.php';
        return ob_get_clean();
    }

    /**
     * Shortcode de confirmación
     */
    public function shortcode_confirmar($atts) {
        $token = isset($_GET['token']) ? sanitize_text_field($_GET['token']) : '';

        if (empty($token)) {
            return '<p class="em-error">' . __('Token de confirmación no válido.', 'flavor-chat-ia') . '</p>';
        }

        $resultado = $this->confirmar_suscripcion($token);

        if ($resultado['success']) {
            return '<div class="em-confirmacion-exitosa">
                <span class="em-icono">✓</span>
                <h3>' . __('¡Suscripción confirmada!', 'flavor-chat-ia') . '</h3>
                <p>' . __('Gracias por confirmar tu suscripción. Pronto recibirás nuestras novedades.', 'flavor-chat-ia') . '</p>
            </div>';
        }

        return '<p class="em-error">' . esc_html($resultado['error']) . '</p>';
    }

    /**
     * Shortcode de darse de baja
     */
    public function shortcode_baja($atts) {
        $token = isset($_GET['token']) ? sanitize_text_field($_GET['token']) : '';

        if (empty($token)) {
            return '<p class="em-error">' . __('Token no válido.', 'flavor-chat-ia') . '</p>';
        }

        $suscriptor = $this->get_suscriptor_por_token($token);

        if (!$suscriptor) {
            return '<p class="em-error">' . __('Suscriptor no encontrado.', 'flavor-chat-ia') . '</p>';
        }

        ob_start();
        include plugin_dir_path(__FILE__) . 'templates/darse-baja.php';
        return ob_get_clean();
    }

    // =========================================================================
    // GESTIÓN DE SUSCRIPTORES
    // =========================================================================

    /**
     * Suscribir un email a una lista
     */
    public function suscribir($email, $lista_slug, $datos = []) {
        global $wpdb;

        $email = sanitize_email($email);
        if (!is_email($email)) {
            return ['success' => false, 'error' => __('Email no válido', 'flavor-chat-ia')];
        }

        // Obtener lista
        $lista = $this->get_lista_por_slug($lista_slug);
        if (!$lista) {
            return ['success' => false, 'error' => __('Lista no encontrada', 'flavor-chat-ia')];
        }

        // Buscar o crear suscriptor
        $tabla_suscriptores = $wpdb->prefix . 'flavor_em_suscriptores';
        $suscriptor = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM $tabla_suscriptores WHERE email = %s",
            $email
        ));

        $es_nuevo = false;
        if (!$suscriptor) {
            $es_nuevo = true;
            $token = wp_generate_password(32, false);

            $insert_data = [
                'email' => $email,
                'nombre' => isset($datos['nombre']) ? sanitize_text_field($datos['nombre']) : '',
                'apellidos' => isset($datos['apellidos']) ? sanitize_text_field($datos['apellidos']) : '',
                'usuario_id' => isset($datos['usuario_id']) ? absint($datos['usuario_id']) : null,
                'estado' => $lista->doble_optin ? 'pendiente' : 'activo',
                'origen' => isset($datos['origen']) ? sanitize_text_field($datos['origen']) : 'formulario',
                'ip_registro' => $this->get_client_ip(),
            ];

            if (!$lista->doble_optin) {
                $insert_data['fecha_confirmacion'] = current_time('mysql');
            }

            $wpdb->insert($tabla_suscriptores, $insert_data);
            $suscriptor_id = $wpdb->insert_id;
        } else {
            $suscriptor_id = $suscriptor->id;

            // Actualizar datos si se proporcionan
            if (!empty($datos['nombre']) || !empty($datos['apellidos'])) {
                $update_data = [];
                if (!empty($datos['nombre'])) {
                    $update_data['nombre'] = sanitize_text_field($datos['nombre']);
                }
                if (!empty($datos['apellidos'])) {
                    $update_data['apellidos'] = sanitize_text_field($datos['apellidos']);
                }
                $wpdb->update($tabla_suscriptores, $update_data, ['id' => $suscriptor_id]);
            }
        }

        // Vincular a lista
        $tabla_relacion = $wpdb->prefix . 'flavor_em_suscriptor_lista';
        $ya_suscrito = $wpdb->get_var($wpdb->prepare(
            "SELECT id FROM $tabla_relacion WHERE suscriptor_id = %d AND lista_id = %d",
            $suscriptor_id,
            $lista->id
        ));

        if ($ya_suscrito) {
            // Verificar si está dado de baja
            $estado_actual = $wpdb->get_var($wpdb->prepare(
                "SELECT estado FROM $tabla_relacion WHERE id = %d",
                $ya_suscrito
            ));

            if ($estado_actual === 'baja') {
                // Reactivar suscripción
                $wpdb->update(
                    $tabla_relacion,
                    [
                        'estado' => $lista->doble_optin ? 'pendiente' : 'activo',
                        'fecha_suscripcion' => current_time('mysql'),
                        'fecha_baja' => null,
                    ],
                    ['id' => $ya_suscrito]
                );
            } else {
                return ['success' => false, 'error' => __('Ya estás suscrito a esta lista', 'flavor-chat-ia')];
            }
        } else {
            $wpdb->insert($tabla_relacion, [
                'suscriptor_id' => $suscriptor_id,
                'lista_id' => $lista->id,
                'estado' => $lista->doble_optin ? 'pendiente' : 'activo',
            ]);
        }

        // Actualizar contador de lista
        $this->actualizar_contador_lista($lista->id);

        // Enviar email de confirmación si es doble optin
        if ($lista->doble_optin) {
            $this->enviar_email_confirmacion($suscriptor_id, $lista->id);
        } else {
            // Trigger de automatización
            $this->trigger_suscripcion($suscriptor_id, $lista->id);

            // Enviar email de bienvenida
            if (!empty($lista->mensaje_bienvenida)) {
                $this->enviar_email_bienvenida($suscriptor_id, $lista);
            }
        }

        return [
            'success' => true,
            'mensaje' => $lista->doble_optin
                ? __('Te hemos enviado un email de confirmación. Por favor, revisa tu bandeja de entrada.', 'flavor-chat-ia')
                : __('¡Te has suscrito correctamente!', 'flavor-chat-ia'),
            'requiere_confirmacion' => $lista->doble_optin,
        ];
    }

    /**
     * Confirmar suscripción
     */
    public function confirmar_suscripcion($token) {
        global $wpdb;

        // Buscar suscriptor pendiente con este token
        $tabla_suscriptores = $wpdb->prefix . 'flavor_em_suscriptores';

        // Token almacenado en metadata
        $suscriptor = $wpdb->get_row($wpdb->prepare(
            "SELECT s.* FROM $tabla_suscriptores s
             WHERE s.estado = 'pendiente'
             AND JSON_EXTRACT(s.campos_personalizados, '$.token_confirmacion') = %s",
            $token
        ));

        if (!$suscriptor) {
            return ['success' => false, 'error' => __('Token de confirmación no válido o expirado.', 'flavor-chat-ia')];
        }

        // Activar suscriptor
        $wpdb->update(
            $tabla_suscriptores,
            [
                'estado' => 'activo',
                'fecha_confirmacion' => current_time('mysql'),
            ],
            ['id' => $suscriptor->id]
        );

        // Activar en todas las listas pendientes
        $tabla_relacion = $wpdb->prefix . 'flavor_em_suscriptor_lista';
        $wpdb->update(
            $tabla_relacion,
            ['estado' => 'activo'],
            ['suscriptor_id' => $suscriptor->id, 'estado' => 'pendiente']
        );

        // Obtener listas y enviar bienvenida
        $listas = $wpdb->get_results($wpdb->prepare(
            "SELECT l.* FROM {$wpdb->prefix}flavor_em_listas l
             INNER JOIN $tabla_relacion sl ON l.id = sl.lista_id
             WHERE sl.suscriptor_id = %d AND sl.estado = 'activo'",
            $suscriptor->id
        ));

        foreach ($listas as $lista) {
            $this->actualizar_contador_lista($lista->id);
            $this->trigger_suscripcion($suscriptor->id, $lista->id);

            if (!empty($lista->mensaje_bienvenida)) {
                $this->enviar_email_bienvenida($suscriptor->id, $lista);
            }
        }

        return ['success' => true];
    }

    /**
     * Dar de baja a un suscriptor
     */
    public function dar_de_baja($suscriptor_id, $lista_id = null, $motivo = '') {
        global $wpdb;

        $tabla_suscriptores = $wpdb->prefix . 'flavor_em_suscriptores';
        $tabla_relacion = $wpdb->prefix . 'flavor_em_suscriptor_lista';

        if ($lista_id) {
            // Baja de una lista específica
            $wpdb->update(
                $tabla_relacion,
                [
                    'estado' => 'baja',
                    'fecha_baja' => current_time('mysql'),
                ],
                [
                    'suscriptor_id' => $suscriptor_id,
                    'lista_id' => $lista_id,
                ]
            );

            $this->actualizar_contador_lista($lista_id);

            // Verificar si queda en alguna lista
            $listas_activas = $wpdb->get_var($wpdb->prepare(
                "SELECT COUNT(*) FROM $tabla_relacion WHERE suscriptor_id = %d AND estado = 'activo'",
                $suscriptor_id
            ));

            if ($listas_activas == 0) {
                $wpdb->update(
                    $tabla_suscriptores,
                    [
                        'estado' => 'baja',
                        'fecha_baja' => current_time('mysql'),
                        'motivo_baja' => $motivo,
                    ],
                    ['id' => $suscriptor_id]
                );
            }
        } else {
            // Baja total
            $wpdb->update(
                $tabla_suscriptores,
                [
                    'estado' => 'baja',
                    'fecha_baja' => current_time('mysql'),
                    'motivo_baja' => $motivo,
                ],
                ['id' => $suscriptor_id]
            );

            // Baja de todas las listas
            $listas = $wpdb->get_col($wpdb->prepare(
                "SELECT lista_id FROM $tabla_relacion WHERE suscriptor_id = %d",
                $suscriptor_id
            ));

            $wpdb->update(
                $tabla_relacion,
                [
                    'estado' => 'baja',
                    'fecha_baja' => current_time('mysql'),
                ],
                ['suscriptor_id' => $suscriptor_id]
            );

            foreach ($listas as $lista_id) {
                $this->actualizar_contador_lista($lista_id);
            }
        }

        // Registrar tracking
        $this->registrar_tracking([
            'suscriptor_id' => $suscriptor_id,
            'tipo' => 'baja',
        ]);

        return ['success' => true];
    }

    // =========================================================================
    // GESTIÓN DE LISTAS
    // =========================================================================

    /**
     * Obtener lista por slug
     */
    public function get_lista_por_slug($slug) {
        global $wpdb;
        return $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$wpdb->prefix}flavor_em_listas WHERE slug = %s",
            $slug
        ));
    }

    /**
     * Obtener todas las listas activas
     */
    public function get_listas_activas() {
        global $wpdb;
        return $wpdb->get_results(
            "SELECT * FROM {$wpdb->prefix}flavor_em_listas WHERE activa = 1 ORDER BY nombre ASC"
        );
    }

    /**
     * Actualizar contador de suscriptores de una lista
     */
    private function actualizar_contador_lista($lista_id) {
        global $wpdb;

        $total = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM {$wpdb->prefix}flavor_em_suscriptor_lista
             WHERE lista_id = %d AND estado = 'activo'",
            $lista_id
        ));

        $wpdb->update(
            $wpdb->prefix . 'flavor_em_listas',
            ['total_suscriptores' => $total],
            ['id' => $lista_id]
        );
    }

    // =========================================================================
    // CAMPAÑAS
    // =========================================================================

    /**
     * Crear campaña
     */
    public function crear_campania($datos) {
        global $wpdb;

        $tabla = $wpdb->prefix . 'flavor_em_campanias';

        $settings = $this->get_settings();

        $insert_data = [
            'nombre' => sanitize_text_field($datos['nombre']),
            'asunto' => sanitize_text_field($datos['asunto']),
            'asunto_alternativo' => isset($datos['asunto_alternativo']) ? sanitize_text_field($datos['asunto_alternativo']) : null,
            'preview_text' => isset($datos['preview_text']) ? sanitize_text_field($datos['preview_text']) : '',
            'contenido_html' => isset($datos['contenido_html']) ? wp_kses_post($datos['contenido_html']) : '',
            'contenido_texto' => isset($datos['contenido_texto']) ? sanitize_textarea_field($datos['contenido_texto']) : '',
            'plantilla_id' => isset($datos['plantilla_id']) ? absint($datos['plantilla_id']) : null,
            'tipo' => isset($datos['tipo']) ? sanitize_key($datos['tipo']) : 'regular',
            'estado' => 'borrador',
            'listas_ids' => isset($datos['listas_ids']) ? wp_json_encode($datos['listas_ids']) : '[]',
            'remitente_nombre' => isset($datos['remitente_nombre']) ? sanitize_text_field($datos['remitente_nombre']) : $settings['remitente_nombre'],
            'remitente_email' => isset($datos['remitente_email']) ? sanitize_email($datos['remitente_email']) : $settings['remitente_email'],
            'responder_a' => isset($datos['responder_a']) ? sanitize_email($datos['responder_a']) : $settings['responder_a'],
            'creado_por' => get_current_user_id(),
        ];

        $wpdb->insert($tabla, $insert_data);

        return $wpdb->insert_id;
    }

    /**
     * Programar envío de campaña
     */
    public function programar_campania($campania_id, $fecha_programada = null) {
        global $wpdb;

        $tabla = $wpdb->prefix . 'flavor_em_campanias';

        $campania = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM $tabla WHERE id = %d",
            $campania_id
        ));

        if (!$campania) {
            return ['success' => false, 'error' => __('Campaña no encontrada', 'flavor-chat-ia')];
        }

        // Calcular destinatarios
        $destinatarios = $this->calcular_destinatarios_campania($campania);

        if (empty($destinatarios)) {
            return ['success' => false, 'error' => __('No hay destinatarios para esta campaña', 'flavor-chat-ia')];
        }

        // Actualizar campaña
        $update_data = [
            'estado' => $fecha_programada ? 'programada' : 'enviando',
            'fecha_programada' => $fecha_programada,
            'total_destinatarios' => count($destinatarios),
        ];

        if (!$fecha_programada) {
            $update_data['fecha_inicio_envio'] = current_time('mysql');
        }

        $wpdb->update($tabla, $update_data, ['id' => $campania_id]);

        // Si es envío inmediato, añadir a cola
        if (!$fecha_programada) {
            $this->encolar_campania($campania_id, $destinatarios);
        }

        return [
            'success' => true,
            'destinatarios' => count($destinatarios),
            'mensaje' => $fecha_programada
                ? sprintf(__('Campaña programada para %s', 'flavor-chat-ia'), $fecha_programada)
                : __('Campaña en proceso de envío', 'flavor-chat-ia'),
        ];
    }

    /**
     * Calcular destinatarios de una campaña
     */
    private function calcular_destinatarios_campania($campania) {
        global $wpdb;

        $listas_ids = json_decode($campania->listas_ids, true) ?: [];

        if (empty($listas_ids)) {
            return [];
        }

        $placeholders = implode(',', array_fill(0, count($listas_ids), '%d'));

        $query = $wpdb->prepare(
            "SELECT DISTINCT s.id, s.email, s.nombre
             FROM {$wpdb->prefix}flavor_em_suscriptores s
             INNER JOIN {$wpdb->prefix}flavor_em_suscriptor_lista sl ON s.id = sl.suscriptor_id
             WHERE sl.lista_id IN ($placeholders)
             AND sl.estado = 'activo'
             AND s.estado = 'activo'",
            ...$listas_ids
        );

        return $wpdb->get_results($query, ARRAY_A);
    }

    /**
     * Encolar emails de campaña
     */
    private function encolar_campania($campania_id, $destinatarios) {
        global $wpdb;

        $tabla_cola = $wpdb->prefix . 'flavor_em_cola';
        $campania = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$wpdb->prefix}flavor_em_campanias WHERE id = %d",
            $campania_id
        ));

        foreach ($destinatarios as $destinatario) {
            $contenido_personalizado = $this->personalizar_contenido(
                $campania->contenido_html,
                $destinatario
            );

            $wpdb->insert($tabla_cola, [
                'campania_id' => $campania_id,
                'suscriptor_id' => $destinatario['id'],
                'email' => $destinatario['email'],
                'asunto' => $this->personalizar_contenido($campania->asunto, $destinatario),
                'contenido' => $contenido_personalizado,
                'prioridad' => 5,
                'estado' => 'pendiente',
            ]);
        }
    }

    /**
     * Personalizar contenido con datos del suscriptor
     */
    private function personalizar_contenido($contenido, $suscriptor) {
        $reemplazos = [
            '{{nombre}}' => $suscriptor['nombre'] ?? '',
            '{{email}}' => $suscriptor['email'],
            '{{nombre_sitio}}' => get_bloginfo('name'),
            '{{url_sitio}}' => home_url(),
            '{{fecha}}' => date_i18n(get_option('date_format')),
            '{{url_baja}}' => $this->generar_url_baja($suscriptor['id']),
            '{{url_preferencias}}' => $this->generar_url_preferencias($suscriptor['id']),
        ];

        $settings = $this->get_settings();
        $reemplazos['{{color_primario}}'] = $settings['color_primario'];
        $reemplazos['{{color_secundario}}'] = $settings['color_secundario'];

        return str_replace(array_keys($reemplazos), array_values($reemplazos), $contenido);
    }

    /**
     * Procesar cola de emails
     */
    public function procesar_cola_emails() {
        global $wpdb;

        $settings = $this->get_settings();
        $limite = min(50, intval($settings['emails_por_hora']) / 60);

        $tabla_cola = $wpdb->prefix . 'flavor_em_cola';

        // Obtener emails pendientes
        $emails = $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM $tabla_cola
             WHERE estado = 'pendiente'
             AND (programado_para IS NULL OR programado_para <= %s)
             ORDER BY prioridad ASC, id ASC
             LIMIT %d",
            current_time('mysql'),
            $limite
        ));

        if (empty($emails)) {
            return;
        }

        $sender = new Flavor_EM_Sender($settings);

        foreach ($emails as $email) {
            // Marcar como procesando
            $wpdb->update(
                $tabla_cola,
                ['estado' => 'procesando'],
                ['id' => $email->id]
            );

            $resultado = $sender->enviar($email);

            if ($resultado['success']) {
                $wpdb->update(
                    $tabla_cola,
                    [
                        'estado' => 'enviado',
                        'enviado_en' => current_time('mysql'),
                    ],
                    ['id' => $email->id]
                );

                // Registrar tracking
                $this->registrar_tracking([
                    'campania_id' => $email->campania_id,
                    'automatizacion_id' => $email->automatizacion_id,
                    'suscriptor_id' => $email->suscriptor_id,
                    'tipo' => 'enviado',
                    'email_hash' => md5($email->email . $email->id),
                ]);

                // Actualizar estadísticas de campaña
                if ($email->campania_id) {
                    $wpdb->query($wpdb->prepare(
                        "UPDATE {$wpdb->prefix}flavor_em_campanias
                         SET total_enviados = total_enviados + 1
                         WHERE id = %d",
                        $email->campania_id
                    ));
                }
            } else {
                $intentos = $email->intentos + 1;

                if ($intentos >= $email->max_intentos) {
                    $wpdb->update(
                        $tabla_cola,
                        [
                            'estado' => 'fallido',
                            'intentos' => $intentos,
                            'error_mensaje' => $resultado['error'],
                        ],
                        ['id' => $email->id]
                    );

                    // Registrar rebote
                    $this->registrar_tracking([
                        'campania_id' => $email->campania_id,
                        'suscriptor_id' => $email->suscriptor_id,
                        'tipo' => 'rebote',
                    ]);
                } else {
                    $wpdb->update(
                        $tabla_cola,
                        [
                            'estado' => 'pendiente',
                            'intentos' => $intentos,
                            'error_mensaje' => $resultado['error'],
                        ],
                        ['id' => $email->id]
                    );
                }
            }
        }

        // Verificar si campaña ha terminado
        $campanias_enviando = $wpdb->get_col(
            "SELECT DISTINCT campania_id FROM $tabla_cola WHERE estado = 'pendiente' AND campania_id IS NOT NULL"
        );

        $todas_campanias = $wpdb->get_col(
            "SELECT id FROM {$wpdb->prefix}flavor_em_campanias WHERE estado = 'enviando'"
        );

        foreach ($todas_campanias as $campania_id) {
            if (!in_array($campania_id, $campanias_enviando)) {
                $wpdb->update(
                    $wpdb->prefix . 'flavor_em_campanias',
                    [
                        'estado' => 'enviada',
                        'fecha_fin_envio' => current_time('mysql'),
                    ],
                    ['id' => $campania_id]
                );
            }
        }
    }

    // =========================================================================
    // AUTOMATIZACIONES
    // =========================================================================

    /**
     * Trigger: Nueva suscripción
     */
    public function trigger_suscripcion($suscriptor_id, $lista_id) {
        $this->ejecutar_trigger('suscripcion', [
            'suscriptor_id' => $suscriptor_id,
            'lista_id' => $lista_id,
        ]);
    }

    /**
     * Trigger: Nuevo usuario
     */
    public function trigger_nuevo_usuario($user_id) {
        $user = get_userdata($user_id);
        if (!$user) {
            return;
        }

        // Buscar o crear suscriptor
        $suscriptor_id = $this->get_o_crear_suscriptor_por_usuario($user_id);

        $this->ejecutar_trigger('nuevo_usuario', [
            'suscriptor_id' => $suscriptor_id,
            'usuario_id' => $user_id,
        ]);
    }

    /**
     * Trigger: Nuevo socio
     */
    public function trigger_nuevo_socio($socio_id) {
        $this->ejecutar_trigger('nuevo_socio', [
            'socio_id' => $socio_id,
        ]);
    }

    /**
     * Trigger: Compra completada
     */
    public function trigger_compra_completada($order_id) {
        $this->ejecutar_trigger('compra_completada', [
            'order_id' => $order_id,
        ]);
    }

    /**
     * Ejecutar trigger de automatización
     */
    private function ejecutar_trigger($tipo, $datos) {
        global $wpdb;

        $tabla = $wpdb->prefix . 'flavor_em_automatizaciones';

        // Buscar automatizaciones activas con este trigger
        $automatizaciones = $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM $tabla WHERE trigger_tipo = %s AND estado = 'activa'",
            $tipo
        ));

        foreach ($automatizaciones as $auto) {
            $suscriptor_id = isset($datos['suscriptor_id']) ? $datos['suscriptor_id'] : null;

            if (!$suscriptor_id && isset($datos['usuario_id'])) {
                $suscriptor_id = $this->get_o_crear_suscriptor_por_usuario($datos['usuario_id']);
            }

            if (!$suscriptor_id) {
                continue;
            }

            // Verificar si ya está en esta automatización
            $ya_inscrito = $wpdb->get_var($wpdb->prepare(
                "SELECT id FROM {$wpdb->prefix}flavor_em_auto_suscriptores
                 WHERE automatizacion_id = %d AND suscriptor_id = %d",
                $auto->id,
                $suscriptor_id
            ));

            if ($ya_inscrito) {
                continue;
            }

            // Inscribir en automatización
            $pasos = json_decode($auto->pasos, true) ?: [];
            $primer_paso = isset($pasos[0]) ? $pasos[0] : null;

            $fecha_proximo = current_time('mysql');
            if ($primer_paso && isset($primer_paso['espera'])) {
                $fecha_proximo = date('Y-m-d H:i:s', strtotime('+' . $primer_paso['espera']));
            }

            $wpdb->insert($wpdb->prefix . 'flavor_em_auto_suscriptores', [
                'automatizacion_id' => $auto->id,
                'suscriptor_id' => $suscriptor_id,
                'paso_actual' => 0,
                'estado' => 'activo',
                'fecha_proximo_paso' => $fecha_proximo,
            ]);

            // Actualizar contador
            $wpdb->query($wpdb->prepare(
                "UPDATE $tabla SET total_inscritos = total_inscritos + 1 WHERE id = %d",
                $auto->id
            ));
        }
    }

    /**
     * Procesar automatizaciones pendientes
     */
    public function procesar_automatizaciones() {
        global $wpdb;

        $tabla_auto_sus = $wpdb->prefix . 'flavor_em_auto_suscriptores';

        // Obtener suscriptores con pasos pendientes
        $pendientes = $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM $tabla_auto_sus
             WHERE estado = 'activo'
             AND fecha_proximo_paso <= %s
             LIMIT 50",
            current_time('mysql')
        ));

        foreach ($pendientes as $inscrito) {
            $this->procesar_paso_automatizacion($inscrito);
        }
    }

    /**
     * Procesar un paso de automatización
     */
    private function procesar_paso_automatizacion($inscrito) {
        global $wpdb;

        $automatizacion = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$wpdb->prefix}flavor_em_automatizaciones WHERE id = %d",
            $inscrito->automatizacion_id
        ));

        if (!$automatizacion || $automatizacion->estado !== 'activa') {
            return;
        }

        $pasos = json_decode($automatizacion->pasos, true) ?: [];
        $paso_actual = intval($inscrito->paso_actual);

        if (!isset($pasos[$paso_actual])) {
            // Completar automatización
            $wpdb->update(
                $wpdb->prefix . 'flavor_em_auto_suscriptores',
                [
                    'estado' => 'completado',
                    'fecha_completado' => current_time('mysql'),
                ],
                ['id' => $inscrito->id]
            );

            $wpdb->query($wpdb->prepare(
                "UPDATE {$wpdb->prefix}flavor_em_automatizaciones
                 SET total_completados = total_completados + 1
                 WHERE id = %d",
                $automatizacion->id
            ));

            return;
        }

        $paso = $pasos[$paso_actual];

        // Ejecutar acción del paso
        switch ($paso['tipo']) {
            case 'email':
                $this->enviar_email_automatizacion($inscrito->suscriptor_id, $paso, $automatizacion->id);
                break;

            case 'tag':
                $this->aplicar_tag_suscriptor($inscrito->suscriptor_id, $paso['tag']);
                break;

            case 'lista':
                if ($paso['accion'] === 'añadir') {
                    $this->suscribir_a_lista($inscrito->suscriptor_id, $paso['lista_id']);
                } else {
                    $this->dar_de_baja($inscrito->suscriptor_id, $paso['lista_id']);
                }
                break;
        }

        // Avanzar al siguiente paso
        $siguiente_paso = $paso_actual + 1;
        $fecha_proximo = null;

        if (isset($pasos[$siguiente_paso])) {
            $espera = isset($pasos[$siguiente_paso]['espera']) ? $pasos[$siguiente_paso]['espera'] : '1 hour';
            $fecha_proximo = date('Y-m-d H:i:s', strtotime('+' . $espera));
        }

        $historial = json_decode($inscrito->historial, true) ?: [];
        $historial[] = [
            'paso' => $paso_actual,
            'tipo' => $paso['tipo'],
            'fecha' => current_time('mysql'),
        ];

        $wpdb->update(
            $wpdb->prefix . 'flavor_em_auto_suscriptores',
            [
                'paso_actual' => $siguiente_paso,
                'fecha_proximo_paso' => $fecha_proximo,
                'historial' => wp_json_encode($historial),
            ],
            ['id' => $inscrito->id]
        );
    }

    // =========================================================================
    // TRACKING
    // =========================================================================

    /**
     * Manejar tracking (pixel y clicks)
     */
    public function handle_tracking() {
        if (isset($_GET['em_track'])) {
            $this->track_apertura();
        }

        if (isset($_GET['em_click'])) {
            $this->track_click();
        }
    }

    /**
     * Track de apertura
     */
    private function track_apertura() {
        $hash = sanitize_text_field($_GET['em_track']);

        global $wpdb;

        $tracking = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$wpdb->prefix}flavor_em_tracking
             WHERE email_hash = %s AND tipo = 'enviado'
             LIMIT 1",
            $hash
        ));

        if ($tracking) {
            $this->registrar_tracking([
                'campania_id' => $tracking->campania_id,
                'automatizacion_id' => $tracking->automatizacion_id,
                'suscriptor_id' => $tracking->suscriptor_id,
                'email_hash' => $hash,
                'tipo' => 'abierto',
            ]);

            // Actualizar estadísticas
            if ($tracking->campania_id) {
                $wpdb->query($wpdb->prepare(
                    "UPDATE {$wpdb->prefix}flavor_em_campanias
                     SET total_abiertos = total_abiertos + 1
                     WHERE id = %d",
                    $tracking->campania_id
                ));
            }

            // Actualizar suscriptor
            $wpdb->update(
                $wpdb->prefix . 'flavor_em_suscriptores',
                [
                    'total_abiertos' => $wpdb->get_var($wpdb->prepare(
                        "SELECT total_abiertos + 1 FROM {$wpdb->prefix}flavor_em_suscriptores WHERE id = %d",
                        $tracking->suscriptor_id
                    )),
                    'ultima_apertura' => current_time('mysql'),
                ],
                ['id' => $tracking->suscriptor_id]
            );
        }

        // Devolver pixel transparente
        header('Content-Type: image/gif');
        echo base64_decode('R0lGODlhAQABAIAAAAAAAP///yH5BAEAAAAALAAAAAABAAEAAAIBRAA7');
        exit;
    }

    /**
     * Track de click
     */
    private function track_click() {
        $data = base64_decode($_GET['em_click']);
        $parts = explode('|', $data);

        if (count($parts) !== 2) {
            wp_redirect(home_url());
            exit;
        }

        $hash = sanitize_text_field($parts[0]);
        $url = esc_url_raw($parts[1]);

        global $wpdb;

        $tracking = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$wpdb->prefix}flavor_em_tracking
             WHERE email_hash = %s AND tipo = 'enviado'
             LIMIT 1",
            $hash
        ));

        if ($tracking) {
            $this->registrar_tracking([
                'campania_id' => $tracking->campania_id,
                'automatizacion_id' => $tracking->automatizacion_id,
                'suscriptor_id' => $tracking->suscriptor_id,
                'email_hash' => $hash,
                'tipo' => 'click',
                'url_clickeada' => $url,
            ]);

            // Actualizar estadísticas
            if ($tracking->campania_id) {
                $wpdb->query($wpdb->prepare(
                    "UPDATE {$wpdb->prefix}flavor_em_campanias
                     SET total_clicks = total_clicks + 1
                     WHERE id = %d",
                    $tracking->campania_id
                ));
            }

            // Actualizar suscriptor
            $wpdb->update(
                $wpdb->prefix . 'flavor_em_suscriptores',
                [
                    'total_clicks' => $wpdb->get_var($wpdb->prepare(
                        "SELECT total_clicks + 1 FROM {$wpdb->prefix}flavor_em_suscriptores WHERE id = %d",
                        $tracking->suscriptor_id
                    )),
                    'ultimo_click' => current_time('mysql'),
                ],
                ['id' => $tracking->suscriptor_id]
            );
        }

        wp_redirect($url);
        exit;
    }

    /**
     * Registrar evento de tracking
     */
    private function registrar_tracking($datos) {
        global $wpdb;

        $insert_data = [
            'campania_id' => $datos['campania_id'] ?? null,
            'automatizacion_id' => $datos['automatizacion_id'] ?? null,
            'suscriptor_id' => $datos['suscriptor_id'],
            'email_hash' => $datos['email_hash'] ?? '',
            'tipo' => $datos['tipo'],
            'url_clickeada' => $datos['url_clickeada'] ?? null,
            'ip' => $this->get_client_ip(),
            'user_agent' => isset($_SERVER['HTTP_USER_AGENT']) ? sanitize_text_field($_SERVER['HTTP_USER_AGENT']) : '',
        ];

        $wpdb->insert($wpdb->prefix . 'flavor_em_tracking', $insert_data);
    }

    // =========================================================================
    // HELPERS
    // =========================================================================

    /**
     * Obtener IP del cliente
     */
    private function get_client_ip() {
        $ip_keys = ['HTTP_CLIENT_IP', 'HTTP_X_FORWARDED_FOR', 'REMOTE_ADDR'];

        foreach ($ip_keys as $key) {
            if (!empty($_SERVER[$key])) {
                $ip = explode(',', $_SERVER[$key])[0];
                return sanitize_text_field(trim($ip));
            }
        }

        return '0.0.0.0';
    }

    /**
     * Generar URL de baja
     */
    private function generar_url_baja($suscriptor_id) {
        $token = $this->generar_token_suscriptor($suscriptor_id);
        $pagina_baja = get_option('flavor_em_pagina_baja', home_url('/baja-newsletter/'));
        return add_query_arg('token', $token, $pagina_baja);
    }

    /**
     * Generar URL de preferencias
     */
    private function generar_url_preferencias($suscriptor_id) {
        $token = $this->generar_token_suscriptor($suscriptor_id);
        $pagina_preferencias = get_option('flavor_em_pagina_preferencias', home_url('/preferencias-email/'));
        return add_query_arg('token', $token, $pagina_preferencias);
    }

    /**
     * Generar token para suscriptor
     */
    private function generar_token_suscriptor($suscriptor_id) {
        return hash('sha256', $suscriptor_id . AUTH_SALT . date('Y-m'));
    }

    /**
     * Obtener suscriptor por token
     */
    private function get_suscriptor_por_token($token) {
        global $wpdb;

        // Buscar en todos los suscriptores activos
        $suscriptores = $wpdb->get_results(
            "SELECT * FROM {$wpdb->prefix}flavor_em_suscriptores WHERE estado != 'baja'"
        );

        foreach ($suscriptores as $suscriptor) {
            $token_esperado = $this->generar_token_suscriptor($suscriptor->id);
            if (hash_equals($token_esperado, $token)) {
                return $suscriptor;
            }
        }

        return null;
    }

    /**
     * Obtener o crear suscriptor por usuario WordPress
     */
    private function get_o_crear_suscriptor_por_usuario($user_id) {
        global $wpdb;

        $tabla = $wpdb->prefix . 'flavor_em_suscriptores';

        $suscriptor_id = $wpdb->get_var($wpdb->prepare(
            "SELECT id FROM $tabla WHERE usuario_id = %d",
            $user_id
        ));

        if ($suscriptor_id) {
            return $suscriptor_id;
        }

        $user = get_userdata($user_id);
        if (!$user) {
            return null;
        }

        $wpdb->insert($tabla, [
            'email' => $user->user_email,
            'nombre' => $user->first_name ?: $user->display_name,
            'apellidos' => $user->last_name,
            'usuario_id' => $user_id,
            'estado' => 'activo',
            'origen' => 'usuario_wp',
            'fecha_confirmacion' => current_time('mysql'),
        ]);

        return $wpdb->insert_id;
    }

    /**
     * Enviar email de confirmación
     */
    private function enviar_email_confirmacion($suscriptor_id, $lista_id) {
        global $wpdb;

        $suscriptor = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$wpdb->prefix}flavor_em_suscriptores WHERE id = %d",
            $suscriptor_id
        ));

        $lista = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$wpdb->prefix}flavor_em_listas WHERE id = %d",
            $lista_id
        ));

        $token = wp_generate_password(32, false);

        // Guardar token
        $campos = json_decode($suscriptor->campos_personalizados, true) ?: [];
        $campos['token_confirmacion'] = $token;

        $wpdb->update(
            $wpdb->prefix . 'flavor_em_suscriptores',
            ['campos_personalizados' => wp_json_encode($campos)],
            ['id' => $suscriptor_id]
        );

        $url_confirmacion = add_query_arg(
            'token',
            $token,
            get_option('flavor_em_pagina_confirmacion', home_url('/confirmar-suscripcion/'))
        );

        $asunto = sprintf(__('Confirma tu suscripción a %s', 'flavor-chat-ia'), get_bloginfo('name'));

        $mensaje = sprintf(
            __('Hola %s,', 'flavor-chat-ia') . "\n\n" .
            __('Has solicitado suscribirte a nuestra lista "%s".', 'flavor-chat-ia') . "\n\n" .
            __('Por favor, confirma tu suscripción haciendo clic en el siguiente enlace:', 'flavor-chat-ia') . "\n\n" .
            '%s' . "\n\n" .
            __('Si no has solicitado esta suscripción, puedes ignorar este email.', 'flavor-chat-ia') . "\n\n" .
            __('Saludos,', 'flavor-chat-ia') . "\n" .
            get_bloginfo('name'),
            $suscriptor->nombre ?: __('suscriptor', 'flavor-chat-ia'),
            $lista->nombre,
            $url_confirmacion
        );

        wp_mail($suscriptor->email, $asunto, $mensaje);
    }

    /**
     * Enviar email de bienvenida
     */
    private function enviar_email_bienvenida($suscriptor_id, $lista) {
        global $wpdb;

        $suscriptor = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$wpdb->prefix}flavor_em_suscriptores WHERE id = %d",
            $suscriptor_id
        ));

        $asunto = sprintf(__('¡Bienvenido/a a %s!', 'flavor-chat-ia'), get_bloginfo('name'));

        $mensaje = $this->personalizar_contenido($lista->mensaje_bienvenida, [
            'id' => $suscriptor_id,
            'nombre' => $suscriptor->nombre,
            'email' => $suscriptor->email,
        ]);

        wp_mail($suscriptor->email, $asunto, $mensaje);
    }

    /**
     * Enviar email de automatización
     */
    private function enviar_email_automatizacion($suscriptor_id, $paso, $automatizacion_id) {
        global $wpdb;

        $suscriptor = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$wpdb->prefix}flavor_em_suscriptores WHERE id = %d",
            $suscriptor_id
        ));

        $contenido_personalizado = $this->personalizar_contenido($paso['contenido'], [
            'id' => $suscriptor_id,
            'nombre' => $suscriptor->nombre,
            'email' => $suscriptor->email,
        ]);

        // Añadir a cola
        $wpdb->insert($wpdb->prefix . 'flavor_em_cola', [
            'automatizacion_id' => $automatizacion_id,
            'paso_auto' => $paso['orden'] ?? 0,
            'suscriptor_id' => $suscriptor_id,
            'email' => $suscriptor->email,
            'asunto' => $this->personalizar_contenido($paso['asunto'], [
                'nombre' => $suscriptor->nombre,
                'email' => $suscriptor->email,
            ]),
            'contenido' => $contenido_personalizado,
            'prioridad' => 3,
            'estado' => 'pendiente',
        ]);
    }

    /**
     * Aplicar tag a suscriptor
     */
    private function aplicar_tag_suscriptor($suscriptor_id, $tag) {
        global $wpdb;

        $suscriptor = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$wpdb->prefix}flavor_em_suscriptores WHERE id = %d",
            $suscriptor_id
        ));

        $tags = json_decode($suscriptor->tags, true) ?: [];

        if (!in_array($tag, $tags)) {
            $tags[] = $tag;
            $wpdb->update(
                $wpdb->prefix . 'flavor_em_suscriptores',
                ['tags' => wp_json_encode($tags)],
                ['id' => $suscriptor_id]
            );
        }
    }

    /**
     * Suscribir a lista por ID
     */
    private function suscribir_a_lista($suscriptor_id, $lista_id) {
        global $wpdb;

        $tabla = $wpdb->prefix . 'flavor_em_suscriptor_lista';

        $existe = $wpdb->get_var($wpdb->prepare(
            "SELECT id FROM $tabla WHERE suscriptor_id = %d AND lista_id = %d",
            $suscriptor_id,
            $lista_id
        ));

        if (!$existe) {
            $wpdb->insert($tabla, [
                'suscriptor_id' => $suscriptor_id,
                'lista_id' => $lista_id,
                'estado' => 'activo',
            ]);

            $this->actualizar_contador_lista($lista_id);
        }
    }

    // =========================================================================
    // AJAX HANDLERS
    // =========================================================================

    /**
     * AJAX: Suscribirse
     */
    public function ajax_suscribirse() {
        check_ajax_referer('flavor_em_public', 'nonce');

        $email = isset($_POST['email']) ? sanitize_email($_POST['email']) : '';
        $lista = isset($_POST['lista']) ? sanitize_text_field($_POST['lista']) : 'newsletter-principal';
        $nombre = isset($_POST['nombre']) ? sanitize_text_field($_POST['nombre']) : '';

        $resultado = $this->suscribir($email, $lista, ['nombre' => $nombre]);

        wp_send_json($resultado);
    }

    /**
     * AJAX: Confirmar suscripción
     */
    public function ajax_confirmar() {
        $token = isset($_POST['token']) ? sanitize_text_field($_POST['token']) : '';

        $resultado = $this->confirmar_suscripcion($token);

        wp_send_json($resultado);
    }

    /**
     * AJAX: Darse de baja
     */
    public function ajax_darse_baja() {
        $token = isset($_POST['token']) ? sanitize_text_field($_POST['token']) : '';
        $motivo = isset($_POST['motivo']) ? sanitize_text_field($_POST['motivo']) : '';

        $suscriptor = $this->get_suscriptor_por_token($token);

        if (!$suscriptor) {
            wp_send_json(['success' => false, 'error' => __('Token no válido', 'flavor-chat-ia')]);
        }

        $resultado = $this->dar_de_baja($suscriptor->id, null, $motivo);

        wp_send_json($resultado);
    }

    // =========================================================================
    // VISTAS ADMIN
    // =========================================================================

    /**
     * Render: Dashboard
     */
    public function render_admin_dashboard() {
        include plugin_dir_path(__FILE__) . 'views/dashboard.php';
    }

    /**
     * Render: Campañas
     */
    public function render_admin_campanias() {
        include plugin_dir_path(__FILE__) . 'views/campanias.php';
    }

    /**
     * Render: Automatizaciones
     */
    public function render_admin_automatizaciones() {
        include plugin_dir_path(__FILE__) . 'views/automatizaciones.php';
    }

    /**
     * Render: Suscriptores
     */
    public function render_admin_suscriptores() {
        include plugin_dir_path(__FILE__) . 'views/suscriptores.php';
    }

    /**
     * Render: Listas
     */
    public function render_admin_listas() {
        include plugin_dir_path(__FILE__) . 'views/listas.php';
    }

    /**
     * Render: Plantillas
     */
    public function render_admin_plantillas() {
        include plugin_dir_path(__FILE__) . 'views/plantillas.php';
    }

    /**
     * Render: Estadísticas
     */
    public function render_admin_estadisticas() {
        include plugin_dir_path(__FILE__) . 'views/estadisticas.php';
    }

    /**
     * Render: Configuración
     */
    public function render_admin_configuracion() {
        include plugin_dir_path(__FILE__) . 'views/configuracion.php';
    }

    // =========================================================================
    // ACCIONES Y HERRAMIENTAS PARA IA
    // =========================================================================

    /**
     * Obtener acciones disponibles
     */
    public function get_actions() {
        return [
            'listar_listas' => [
                'description' => 'Listar todas las listas de suscriptores',
                'params' => [],
            ],
            'obtener_suscriptor' => [
                'description' => 'Obtener información de un suscriptor',
                'params' => ['email'],
            ],
            'suscribir' => [
                'description' => 'Suscribir un email a una lista',
                'params' => ['email', 'lista', 'nombre'],
            ],
            'estadisticas_campania' => [
                'description' => 'Obtener estadísticas de una campaña',
                'params' => ['campania_id'],
            ],
            'estadisticas_generales' => [
                'description' => 'Obtener estadísticas generales del módulo',
                'params' => [],
            ],
        ];
    }

    /**
     * Ejecutar acción
     */
    public function execute_action($nombre, $params) {
        $metodo = 'action_' . $nombre;

        if (method_exists($this, $metodo)) {
            return $this->$metodo($params);
        }

        return ['success' => false, 'error' => __('Acción no encontrada', 'flavor-chat-ia')];
    }

    /**
     * Acción: Listar listas
     */
    private function action_listar_listas($params) {
        $listas = $this->get_listas_activas();

        return [
            'success' => true,
            'listas' => array_map(function ($lista) {
                return [
                    'id' => $lista->id,
                    'nombre' => $lista->nombre,
                    'slug' => $lista->slug,
                    'total_suscriptores' => $lista->total_suscriptores,
                ];
            }, $listas),
        ];
    }

    /**
     * Acción: Obtener suscriptor
     */
    private function action_obtener_suscriptor($params) {
        global $wpdb;

        $email = sanitize_email($params['email'] ?? '');

        $suscriptor = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$wpdb->prefix}flavor_em_suscriptores WHERE email = %s",
            $email
        ));

        if (!$suscriptor) {
            return ['success' => false, 'error' => __('Suscriptor no encontrado', 'flavor-chat-ia')];
        }

        return [
            'success' => true,
            'suscriptor' => [
                'email' => $suscriptor->email,
                'nombre' => $suscriptor->nombre,
                'estado' => $suscriptor->estado,
                'total_emails' => $suscriptor->total_emails_enviados,
                'total_abiertos' => $suscriptor->total_abiertos,
                'total_clicks' => $suscriptor->total_clicks,
            ],
        ];
    }

    /**
     * Acción: Suscribir
     */
    private function action_suscribir($params) {
        $email = $params['email'] ?? '';
        $lista = $params['lista'] ?? 'newsletter-principal';
        $nombre = $params['nombre'] ?? '';

        return $this->suscribir($email, $lista, ['nombre' => $nombre]);
    }

    /**
     * Acción: Estadísticas de campaña
     */
    private function action_estadisticas_campania($params) {
        global $wpdb;

        $campania_id = absint($params['campania_id'] ?? 0);

        $campania = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$wpdb->prefix}flavor_em_campanias WHERE id = %d",
            $campania_id
        ));

        if (!$campania) {
            return ['success' => false, 'error' => __('Campaña no encontrada', 'flavor-chat-ia')];
        }

        $tasa_apertura = $campania->total_enviados > 0
            ? round(($campania->total_abiertos / $campania->total_enviados) * 100, 2)
            : 0;

        $tasa_clicks = $campania->total_abiertos > 0
            ? round(($campania->total_clicks / $campania->total_abiertos) * 100, 2)
            : 0;

        return [
            'success' => true,
            'campania' => [
                'nombre' => $campania->nombre,
                'estado' => $campania->estado,
                'enviados' => $campania->total_enviados,
                'abiertos' => $campania->total_abiertos,
                'clicks' => $campania->total_clicks,
                'bajas' => $campania->total_bajas,
                'tasa_apertura' => $tasa_apertura . '%',
                'tasa_clicks' => $tasa_clicks . '%',
            ],
        ];
    }

    /**
     * Acción: Estadísticas generales
     */
    private function action_estadisticas_generales($params) {
        global $wpdb;

        $total_suscriptores = $wpdb->get_var(
            "SELECT COUNT(*) FROM {$wpdb->prefix}flavor_em_suscriptores WHERE estado = 'activo'"
        );

        $total_listas = $wpdb->get_var(
            "SELECT COUNT(*) FROM {$wpdb->prefix}flavor_em_listas WHERE activa = 1"
        );

        $total_campanias = $wpdb->get_var(
            "SELECT COUNT(*) FROM {$wpdb->prefix}flavor_em_campanias"
        );

        $campanias_enviadas = $wpdb->get_var(
            "SELECT COUNT(*) FROM {$wpdb->prefix}flavor_em_campanias WHERE estado = 'enviada'"
        );

        return [
            'success' => true,
            'estadisticas' => [
                'total_suscriptores' => intval($total_suscriptores),
                'total_listas' => intval($total_listas),
                'total_campanias' => intval($total_campanias),
                'campanias_enviadas' => intval($campanias_enviadas),
            ],
        ];
    }

    /**
     * Obtener definiciones de herramientas para Claude
     */
    public function get_tool_definitions() {
        return [
            [
                'name' => 'email_marketing_listar_listas',
                'description' => 'Lista todas las listas de suscriptores de email marketing',
                'input_schema' => [
                    'type' => 'object',
                    'properties' => [],
                ],
            ],
            [
                'name' => 'email_marketing_suscribir',
                'description' => 'Suscribe un email a una lista de newsletter',
                'input_schema' => [
                    'type' => 'object',
                    'properties' => [
                        'email' => ['type' => 'string', 'description' => 'Email a suscribir'],
                        'lista' => ['type' => 'string', 'description' => 'Slug de la lista'],
                        'nombre' => ['type' => 'string', 'description' => 'Nombre del suscriptor'],
                    ],
                    'required' => ['email'],
                ],
            ],
            [
                'name' => 'email_marketing_estadisticas',
                'description' => 'Obtiene estadísticas generales del email marketing',
                'input_schema' => [
                    'type' => 'object',
                    'properties' => [],
                ],
            ],
        ];
    }

    /**
     * Obtener base de conocimiento
     */
    public function get_knowledge_base() {
        return [
            'descripcion' => 'Sistema de email marketing con newsletters, campañas y automatizaciones',
            'funcionalidades' => [
                'Gestión de listas de suscriptores',
                'Campañas de email con plantillas',
                'Automatizaciones (secuencias de bienvenida, etc.)',
                'Tracking de aperturas y clicks',
                'Segmentación por tags y comportamiento',
                'Doble opt-in configurable',
            ],
            'triggers_automatizacion' => [
                'suscripcion' => 'Cuando alguien se suscribe a una lista',
                'nuevo_usuario' => 'Cuando se registra un nuevo usuario',
                'nuevo_socio' => 'Cuando se registra un nuevo socio',
                'compra_completada' => 'Cuando se completa una compra',
            ],
        ];
    }

    /**
     * Obtener FAQs
     */
    public function get_faqs() {
        return [
            [
                'pregunta' => '¿Cómo me suscribo al newsletter?',
                'respuesta' => 'Puedes suscribirte introduciendo tu email en el formulario de suscripción de nuestra web.',
            ],
            [
                'pregunta' => '¿Cómo me doy de baja?',
                'respuesta' => 'Cada email incluye un enlace de baja en el pie. También puedes gestionar tus preferencias.',
            ],
            [
                'pregunta' => '¿Qué es el doble opt-in?',
                'respuesta' => 'Es un proceso de confirmación donde recibes un email para verificar tu suscripción.',
            ],
        ];
    }
}

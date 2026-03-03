<?php
/**
 * Módulo de Email Marketing
 *
 * Newsletter, campañas, automatizaciones y gestión de suscriptores.
 * Sistema completo de email marketing integrado con WordPress.
 *
 * @package FlavorChatIA
 * @subpackage EmailMarketing
 * @version 2.0.0
 */

if (!defined('ABSPATH')) {
    exit;
}

class Flavor_Chat_Email_Marketing_Module extends Flavor_Chat_Module_Base {

    use Flavor_Module_Admin_Pages_Trait;
    use Flavor_Module_Notifications_Trait;

    /**
     * Versión del módulo
     * @var string
     */
    const VERSION = '2.0.0';

    /**
     * Prefijo para tablas de base de datos
     * @var string
     */
    const TABLE_PREFIX = 'flavor_em_';

    /**
     * Estados de suscriptor válidos
     * @var array
     */
    const ESTADOS_SUSCRIPTOR = ['pendiente', 'activo', 'baja', 'rebotado', 'spam'];

    /**
     * Estados de campaña válidos
     * @var array
     */
    const ESTADOS_CAMPANIA = ['borrador', 'programada', 'enviando', 'enviada', 'pausada', 'cancelada'];

    /**
     * Tipos de trigger de automatización
     * @var array
     */
    const TRIGGERS_AUTOMATIZACION = [
        'suscripcion',
        'nuevo_usuario',
        'nuevo_socio',
        'compra_completada',
        'cumpleanos',
        'inactividad',
        'tag_agregado',
        'click_enlace',
        'apertura_email'
    ];

    /**
     * Instancia del sender de emails
     * @var Flavor_EM_Sender|null
     */
    private $email_sender = null;

    /**
     * Cache de configuración
     * @var array|null
     */
    private $cached_settings = null;

    /**
     * Constructor
     */
    public function __construct() {
        $this->id = 'email_marketing';
        $this->name = 'Email Marketing'; // Translation loaded on init
        $this->description = 'Newsletter, campañas de email y automatizaciones'; // Translation loaded on init

        parent::__construct();
    }

    /**
     * Verificar si el módulo puede activarse
     *
     * @return bool
     */
    public function can_activate() {
        // Verificar requisitos mínimos
        if (version_compare(PHP_VERSION, '7.4', '<')) {
            return false;
        }

        // Verificar si la función mail está disponible
        if (!function_exists('mail') && !class_exists('PHPMailer\PHPMailer\PHPMailer')) {
            return false;
        }

        return true;
    }

    /**
     * Mensaje de error si no puede activarse
     *
     * @return string
     */
    public function get_activation_error() {
        if (version_compare(PHP_VERSION, '7.4', '<')) {
            return __('Se requiere PHP 7.4 o superior para el módulo de Email Marketing.', 'flavor-chat-ia');
        }

        if (!function_exists('mail') && !class_exists('PHPMailer\PHPMailer\PHPMailer')) {
            return __('No hay ningún sistema de envío de emails disponible.', 'flavor-chat-ia');
        }

        return '';
    }

    /**
     * Verifica si el módulo está activo
     */
    public function is_active() {
        return $this->can_activate();
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
        $this->register_shortcodes();

        // AJAX handlers públicos
        add_action('wp_ajax_em_suscribirse', [$this, 'ajax_suscribirse']);
        add_action('wp_ajax_nopriv_em_suscribirse', [$this, 'ajax_suscribirse']);
        add_action('wp_ajax_em_confirmar', [$this, 'ajax_confirmar']);
        add_action('wp_ajax_nopriv_em_confirmar', [$this, 'ajax_confirmar']);
        add_action('wp_ajax_em_darse_baja', [$this, 'ajax_darse_baja']);
        add_action('wp_ajax_nopriv_em_darse_baja', [$this, 'ajax_darse_baja']);
        add_action('wp_ajax_em_actualizar_preferencias', [$this, 'ajax_actualizar_preferencias']);
        add_action('wp_ajax_nopriv_em_actualizar_preferencias', [$this, 'ajax_actualizar_preferencias']);

        // Admin AJAX - Campañas
        add_action('wp_ajax_em_admin_campania', [$this, 'ajax_admin_campania']);
        add_action('wp_ajax_em_guardar_campania', [$this, 'ajax_guardar_campania']);
        add_action('wp_ajax_em_eliminar_campania', [$this, 'ajax_eliminar_campania']);
        add_action('wp_ajax_em_duplicar_campania', [$this, 'ajax_duplicar_campania']);
        add_action('wp_ajax_em_programar_campania', [$this, 'ajax_programar_campania']);
        add_action('wp_ajax_em_pausar_campania', [$this, 'ajax_pausar_campania']);
        add_action('wp_ajax_em_reanudar_campania', [$this, 'ajax_reanudar_campania']);
        add_action('wp_ajax_em_cancelar_campania', [$this, 'ajax_cancelar_campania']);
        add_action('wp_ajax_em_enviar_test', [$this, 'ajax_enviar_test']);
        add_action('wp_ajax_em_preview_campania', [$this, 'ajax_preview_campania']);

        // Admin AJAX - Listas
        add_action('wp_ajax_em_admin_lista', [$this, 'ajax_admin_lista']);
        add_action('wp_ajax_em_crear_lista', [$this, 'ajax_crear_lista']);
        add_action('wp_ajax_em_actualizar_lista', [$this, 'ajax_actualizar_lista']);
        add_action('wp_ajax_em_eliminar_lista', [$this, 'ajax_eliminar_lista']);
        add_action('wp_ajax_em_fusionar_listas', [$this, 'ajax_fusionar_listas']);

        // Admin AJAX - Suscriptores
        add_action('wp_ajax_em_admin_suscriptor', [$this, 'ajax_admin_suscriptor']);
        add_action('wp_ajax_em_crear_suscriptor', [$this, 'ajax_crear_suscriptor']);
        add_action('wp_ajax_em_actualizar_suscriptor', [$this, 'ajax_actualizar_suscriptor']);
        add_action('wp_ajax_em_eliminar_suscriptor', [$this, 'ajax_eliminar_suscriptor']);
        add_action('wp_ajax_em_importar_suscriptores', [$this, 'ajax_importar_suscriptores']);
        add_action('wp_ajax_em_exportar_suscriptores', [$this, 'ajax_exportar_suscriptores']);
        add_action('wp_ajax_em_bulk_action_suscriptores', [$this, 'ajax_bulk_action_suscriptores']);
        add_action('wp_ajax_em_buscar_suscriptores', [$this, 'ajax_buscar_suscriptores']);

        // Admin AJAX - Automatizaciones
        add_action('wp_ajax_em_admin_automatizacion', [$this, 'ajax_admin_automatizacion']);
        add_action('wp_ajax_em_crear_automatizacion', [$this, 'ajax_crear_automatizacion']);
        add_action('wp_ajax_em_actualizar_automatizacion', [$this, 'ajax_actualizar_automatizacion']);
        add_action('wp_ajax_em_eliminar_automatizacion', [$this, 'ajax_eliminar_automatizacion']);
        add_action('wp_ajax_em_activar_automatizacion', [$this, 'ajax_activar_automatizacion']);
        add_action('wp_ajax_em_pausar_automatizacion', [$this, 'ajax_pausar_automatizacion']);

        // Admin AJAX - Plantillas
        add_action('wp_ajax_em_admin_plantilla', [$this, 'ajax_admin_plantilla']);
        add_action('wp_ajax_em_guardar_plantilla', [$this, 'ajax_guardar_plantilla']);
        add_action('wp_ajax_em_eliminar_plantilla', [$this, 'ajax_eliminar_plantilla']);
        add_action('wp_ajax_em_duplicar_plantilla', [$this, 'ajax_duplicar_plantilla']);
        add_action('wp_ajax_em_preview_plantilla', [$this, 'ajax_preview_plantilla']);

        // Admin AJAX - Estadísticas
        add_action('wp_ajax_em_obtener_estadisticas', [$this, 'ajax_obtener_estadisticas']);
        add_action('wp_ajax_em_estadisticas_campania', [$this, 'ajax_estadisticas_campania']);
        add_action('wp_ajax_em_estadisticas_lista', [$this, 'ajax_estadisticas_lista']);
        add_action('wp_ajax_em_estadisticas_periodo', [$this, 'ajax_estadisticas_periodo']);
        add_action('wp_ajax_em_exportar_estadisticas', [$this, 'ajax_exportar_estadisticas']);

        // Admin AJAX - Configuración
        add_action('wp_ajax_em_guardar_configuracion', [$this, 'ajax_guardar_configuracion']);
        add_action('wp_ajax_em_test_smtp', [$this, 'ajax_test_smtp']);
        add_action('wp_ajax_em_limpiar_cola', [$this, 'ajax_limpiar_cola']);
        add_action('wp_ajax_em_limpiar_logs', [$this, 'ajax_limpiar_logs']);

        // Tracking
        add_action('init', [$this, 'handle_tracking']);

        // REST API
        add_action('rest_api_init', [$this, 'register_rest_routes']);

        // WP Cron para procesamiento de cola
        add_action('em_procesar_cola_emails', [$this, 'procesar_cola_emails']);
        add_action('em_procesar_automatizaciones', [$this, 'procesar_automatizaciones']);
        add_action('em_procesar_campanias_programadas', [$this, 'procesar_campanias_programadas']);
        add_action('em_limpiar_cola_antigua', [$this, 'limpiar_cola_antigua']);
        add_action('em_procesar_rebotes', [$this, 'procesar_rebotes']);
        add_action('em_enviar_resumen_diario', [$this, 'enviar_resumen_diario']);

        // Programar crons si no existen
        $this->programar_cron_events();

        // Hooks para triggers de automatizaciones
        add_action('user_register', [$this, 'trigger_nuevo_usuario']);
        add_action('woocommerce_order_status_completed', [$this, 'trigger_compra_completada']);
        add_action('flavor_socio_registrado', [$this, 'trigger_nuevo_socio']);
        add_action('profile_update', [$this, 'trigger_perfil_actualizado'], 10, 2);
        add_action('wp_login', [$this, 'trigger_login'], 10, 2);

        // Registrar intervalos de cron personalizados
        add_filter('cron_schedules', [$this, 'add_cron_intervals']);

        // Integración con notificaciones
        add_action('flavor_notification_send', [$this, 'handle_notification_integration'], 10, 2);

        // Widgets
        add_action('widgets_init', [$this, 'register_widgets']);

        // Filtros para personalización
        add_filter('flavor_em_personalizar_contenido', [$this, 'filter_personalizar_contenido'], 10, 2);
        add_filter('flavor_em_antes_enviar', [$this, 'filter_antes_enviar'], 10, 2);

        // Hook de activación de módulo
        add_action('flavor_module_activated_' . $this->id, [$this, 'on_module_activated']);

        // Registrar en panel unificado de administración
        $this->registrar_en_panel_unificado();
    }

    /**
     * Registra shortcodes del módulo
     */
    public function register_shortcodes() {
        // Shortcodes principales
        add_shortcode('em_formulario_suscripcion', [$this, 'shortcode_formulario_suscripcion']);
        add_shortcode('em_preferencias', [$this, 'shortcode_preferencias']);
        add_shortcode('em_confirmar_suscripcion', [$this, 'shortcode_confirmar']);
        add_shortcode('em_darse_baja', [$this, 'shortcode_baja']);

        // Shortcodes adicionales
        add_shortcode('flavor_suscripcion_newsletter', [$this, 'shortcode_suscripcion_newsletter']);
        add_shortcode('flavor_preferencias_email', [$this, 'shortcode_preferencias_email']);
        add_shortcode('flavor_archivo_newsletters', [$this, 'shortcode_archivo_newsletters']);
        add_shortcode('flavor_contador_suscriptores', [$this, 'shortcode_contador_suscriptores']);
        add_shortcode('flavor_formulario_popup', [$this, 'shortcode_formulario_popup']);
        add_shortcode('flavor_email_preview', [$this, 'shortcode_email_preview']);
    }

    /**
     * Programar eventos de cron
     */
    private function programar_cron_events() {
        if (!wp_next_scheduled('em_procesar_cola_emails')) {
            wp_schedule_event(time(), 'every_minute', 'em_procesar_cola_emails');
        }

        if (!wp_next_scheduled('em_procesar_automatizaciones')) {
            wp_schedule_event(time(), 'every_five_minutes', 'em_procesar_automatizaciones');
        }

        if (!wp_next_scheduled('em_procesar_campanias_programadas')) {
            wp_schedule_event(time(), 'every_five_minutes', 'em_procesar_campanias_programadas');
        }

        if (!wp_next_scheduled('em_limpiar_cola_antigua')) {
            wp_schedule_event(time(), 'daily', 'em_limpiar_cola_antigua');
        }

        if (!wp_next_scheduled('em_procesar_rebotes')) {
            wp_schedule_event(time(), 'hourly', 'em_procesar_rebotes');
        }

        if (!wp_next_scheduled('em_enviar_resumen_diario')) {
            wp_schedule_event(strtotime('tomorrow 9:00'), 'daily', 'em_enviar_resumen_diario');
        }
    }

    /**
     * Cargar dependencias del módulo
     */
    private function load_dependencies() {
        $module_path = plugin_dir_path(__FILE__);

        // Las clases auxiliares se cargan si existen
        $archivos_dependencias = [
            'class-email-marketing-api.php',
            'class-email-marketing-sender.php',
            'class-email-marketing-tracking.php',
            'class-em-dashboard-tab.php',
        ];

        foreach ($archivos_dependencias as $archivo) {
            $ruta_archivo = $module_path . $archivo;
            if (file_exists($ruta_archivo)) {
                require_once $ruta_archivo;
            }
        }
    }

    /**
     * Añadir intervalos de cron personalizados
     *
     * @param array $schedules Intervalos existentes
     * @return array Intervalos modificados
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
        $schedules['every_fifteen_minutes'] = [
            'interval' => 900,
            'display' => __('Cada 15 minutos', 'flavor-chat-ia'),
        ];
        $schedules['every_thirty_minutes'] = [
            'interval' => 1800,
            'display' => __('Cada 30 minutos', 'flavor-chat-ia'),
        ];
        return $schedules;
    }

    /**
     * Configuración por defecto
     *
     * @return array
     */
    public function get_default_settings() {
        return [
            // Remitente
            'remitente_nombre' => get_bloginfo('name'),
            'remitente_email' => get_option('admin_email'),
            'responder_a' => get_option('admin_email'),

            // Límites de envío
            'emails_por_hora' => 100,
            'emails_por_minuto' => 10,
            'max_reintentos' => 3,
            'tiempo_entre_reintentos' => 300, // segundos

            // Comportamiento
            'doble_optin_global' => true,
            'permitir_resuscripcion' => true,
            'dias_expiracion_confirmacion' => 7,
            'eliminar_inactivos_dias' => 365,

            // Tracking
            'tracking_aperturas' => true,
            'tracking_clicks' => true,
            'anonimizar_ip' => false,
            'guardar_user_agent' => true,

            // Diseño
            'pie_email_global' => '',
            'cabecera_email_global' => '',
            'color_primario' => '#3b82f6',
            'color_secundario' => '#1e40af',
            'color_texto' => '#333333',
            'color_fondo' => '#f5f5f5',
            'logo_url' => '',
            'ancho_email' => 600,

            // SMTP
            'proveedor_smtp' => 'wp_mail',
            'smtp_host' => '',
            'smtp_puerto' => 587,
            'smtp_usuario' => '',
            'smtp_password' => '',
            'smtp_encriptacion' => 'tls',
            'smtp_autenticacion' => true,

            // Páginas
            'pagina_confirmacion' => 0,
            'pagina_preferencias' => 0,
            'pagina_baja' => 0,
            'pagina_archivo' => 0,

            // Notificaciones admin
            'notificar_nuevos_suscriptores' => true,
            'notificar_bajas' => true,
            'notificar_rebotes' => true,
            'email_notificaciones' => get_option('admin_email'),
            'resumen_diario' => true,

            // Seguridad
            'honeypot_enabled' => true,
            'recaptcha_enabled' => false,
            'recaptcha_site_key' => '',
            'recaptcha_secret_key' => '',
            'rate_limit_enabled' => true,
            'rate_limit_requests' => 5,
            'rate_limit_window' => 60,

            // Integraciones
            'integrar_woocommerce' => true,
            'integrar_usuarios_wp' => true,
            'webhook_url' => '',
            'webhook_eventos' => ['suscripcion', 'baja', 'rebote'],
        ];
    }

    /**
     * Obtener configuración con cache
     *
     * @return array
     */
    public function get_settings() {
        if ($this->cached_settings === null) {
            $this->cached_settings = parent::get_settings();
        }
        return $this->cached_settings;
    }

    /**
     * Limpiar cache de configuración
     */
    public function clear_settings_cache() {
        $this->cached_settings = null;
    }

    /**
     * Obtener estadísticas para el dashboard del cliente
     *
     * @return array
     */
    public function get_estadisticas_dashboard() {
        global $wpdb;
        $estadisticas = [];

        $tabla_suscriptores = $wpdb->prefix . 'flavor_em_suscriptores';
        $tabla_campanias = $wpdb->prefix . 'flavor_em_campanias';

        if (!Flavor_Chat_Helpers::tabla_existe($tabla_suscriptores)) {
            return $estadisticas;
        }

        // Suscriptores activos
        $total_suscriptores = (int) $wpdb->get_var(
            "SELECT COUNT(*) FROM $tabla_suscriptores WHERE estado = 'activo'"
        );
        $estadisticas[] = [
            'icon'  => 'dashicons-email-alt',
            'valor' => $total_suscriptores,
            'label' => __('Suscriptores', 'flavor-chat-ia'),
            'color' => $total_suscriptores > 0 ? 'green' : 'gray'
        ];

        // Campañas enviadas este mes (verificar si la columna existe)
        if (Flavor_Chat_Helpers::tabla_existe($tabla_campanias)) {
            $columnas_campanias = $wpdb->get_col("SHOW COLUMNS FROM $tabla_campanias");
            $col_fecha_envio = in_array('fecha_inicio_envio', $columnas_campanias) ? 'fecha_inicio_envio' :
                              (in_array('fecha_envio', $columnas_campanias) ? 'fecha_envio' : null);

            if ($col_fecha_envio) {
                $primer_dia_mes = date('Y-m-01');
                $campanias_mes = (int) $wpdb->get_var($wpdb->prepare(
                    "SELECT COUNT(*) FROM $tabla_campanias WHERE estado = 'enviada' AND $col_fecha_envio >= %s",
                    $primer_dia_mes
                ));
            } else {
                $campanias_mes = (int) $wpdb->get_var(
                    "SELECT COUNT(*) FROM $tabla_campanias WHERE estado = 'enviada'"
                );
            }
            $estadisticas[] = [
                'icon'  => 'dashicons-megaphone',
                'valor' => $campanias_mes,
                'label' => __('Campañas este mes', 'flavor-chat-ia'),
                'color' => $campanias_mes > 0 ? 'blue' : 'gray'
            ];
        }

        // Nuevos suscriptores esta semana (verificar si la columna existe)
        $columnas_suscriptores = $wpdb->get_col("SHOW COLUMNS FROM $tabla_suscriptores");
        $col_fecha = in_array('creado_en', $columnas_suscriptores) ? 'creado_en' :
                    (in_array('fecha_registro', $columnas_suscriptores) ? 'fecha_registro' : null);

        if ($col_fecha) {
            $hace_una_semana = date('Y-m-d H:i:s', strtotime('-7 days'));
            $nuevos_semana = (int) $wpdb->get_var($wpdb->prepare(
                "SELECT COUNT(*) FROM $tabla_suscriptores WHERE estado = 'activo' AND $col_fecha >= %s",
                $hace_una_semana
            ));
            if ($nuevos_semana > 0) {
                $estadisticas[] = [
                    'icon'  => 'dashicons-plus-alt',
                    'valor' => '+' . $nuevos_semana,
                    'label' => __('Nuevos esta semana', 'flavor-chat-ia'),
                    'color' => 'green'
                ];
            }
        }

        return $estadisticas;
    }

    /**
     * Registrar widgets
     */
    public function register_widgets() {
        // Widget de suscripción se registraría aquí si existiera la clase
        // register_widget('Flavor_EM_Widget_Suscripcion');
    }

    /**
     * Handler para integración con notificaciones
     *
     * @param string $tipo Tipo de notificación
     * @param array $datos Datos de la notificación
     */
    public function handle_notification_integration($tipo, $datos) {
        if (!isset($datos['email']) || !is_email($datos['email'])) {
            return;
        }

        $settings = $this->get_settings();

        // Verificar si el email está suscrito
        global $wpdb;
        $suscriptor = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$wpdb->prefix}flavor_em_suscriptores WHERE email = %s AND estado = 'activo'",
            $datos['email']
        ));

        if (!$suscriptor) {
            return;
        }

        // Añadir a cola de envío
        $this->encolar_email([
            'suscriptor_id' => $suscriptor->id,
            'email' => $datos['email'],
            'asunto' => $datos['asunto'] ?? __('Notificación', 'flavor-chat-ia'),
            'contenido' => $datos['mensaje'] ?? '',
            'prioridad' => isset($datos['urgente']) && $datos['urgente'] ? 1 : 5,
            'tipo' => 'notificacion',
        ]);
    }

    /**
     * Filtro para personalizar contenido
     *
     * @param string $contenido Contenido original
     * @param array $suscriptor Datos del suscriptor
     * @return string Contenido personalizado
     */
    public function filter_personalizar_contenido($contenido, $suscriptor) {
        return $this->personalizar_contenido($contenido, $suscriptor);
    }

    /**
     * Filtro antes de enviar
     *
     * @param array $email_data Datos del email
     * @param object $suscriptor Suscriptor
     * @return array Datos modificados
     */
    public function filter_antes_enviar($email_data, $suscriptor) {
        // Verificar si el suscriptor está en lista de rebotes
        if ($this->es_email_rebotado($suscriptor->email)) {
            $email_data['cancelar'] = true;
            $email_data['razon_cancelacion'] = 'Email rebotado previamente';
        }

        return $email_data;
    }

    /**
     * Callback cuando el módulo se activa
     */
    public function on_module_activated() {
        // Crear tablas si no existen
        $this->crear_tablas_db();

        // Crear páginas por defecto
        $this->crear_paginas_por_defecto();

        // Crear lista por defecto
        $this->crear_lista_por_defecto();

        // Limpiar cache de rewrite rules
        flush_rewrite_rules();
    }

    /**
     * Crear tablas de base de datos
     */
    private function crear_tablas_db() {
        global $wpdb;

        $charset_collate = $wpdb->get_charset_collate();
        $prefijo = $wpdb->prefix . self::TABLE_PREFIX;

        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');

        // Tabla de suscriptores
        $sql_suscriptores = "CREATE TABLE IF NOT EXISTS {$prefijo}suscriptores (
            id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            email varchar(255) NOT NULL,
            nombre varchar(100) DEFAULT '',
            apellidos varchar(100) DEFAULT '',
            usuario_id bigint(20) UNSIGNED DEFAULT NULL,
            estado varchar(20) DEFAULT 'pendiente',
            origen varchar(50) DEFAULT 'formulario',
            ip_registro varchar(45) DEFAULT '',
            fecha_registro datetime DEFAULT CURRENT_TIMESTAMP,
            fecha_confirmacion datetime DEFAULT NULL,
            fecha_baja datetime DEFAULT NULL,
            motivo_baja text DEFAULT NULL,
            tags longtext DEFAULT NULL,
            campos_personalizados longtext DEFAULT NULL,
            total_emails_enviados int(11) DEFAULT 0,
            total_abiertos int(11) DEFAULT 0,
            total_clicks int(11) DEFAULT 0,
            ultima_apertura datetime DEFAULT NULL,
            ultimo_click datetime DEFAULT NULL,
            puntuacion int(11) DEFAULT 0,
            PRIMARY KEY (id),
            UNIQUE KEY email (email),
            KEY estado (estado),
            KEY usuario_id (usuario_id),
            KEY fecha_registro (fecha_registro)
        ) $charset_collate;";

        dbDelta($sql_suscriptores);

        // Tabla de listas
        $sql_listas = "CREATE TABLE IF NOT EXISTS {$prefijo}listas (
            id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            nombre varchar(255) NOT NULL,
            slug varchar(255) NOT NULL,
            descripcion text DEFAULT NULL,
            activa tinyint(1) DEFAULT 1,
            publica tinyint(1) DEFAULT 1,
            doble_optin tinyint(1) DEFAULT 1,
            mensaje_bienvenida longtext DEFAULT NULL,
            total_suscriptores int(11) DEFAULT 0,
            fecha_creacion datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            UNIQUE KEY slug (slug)
        ) $charset_collate;";

        dbDelta($sql_listas);

        // Tabla de relación suscriptor-lista
        $sql_relacion = "CREATE TABLE IF NOT EXISTS {$prefijo}suscriptor_lista (
            id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            suscriptor_id bigint(20) UNSIGNED NOT NULL,
            lista_id bigint(20) UNSIGNED NOT NULL,
            estado varchar(20) DEFAULT 'activo',
            fecha_suscripcion datetime DEFAULT CURRENT_TIMESTAMP,
            fecha_baja datetime DEFAULT NULL,
            PRIMARY KEY (id),
            UNIQUE KEY suscriptor_lista (suscriptor_id, lista_id),
            KEY lista_id (lista_id)
        ) $charset_collate;";

        dbDelta($sql_relacion);

        // Tabla de campañas
        $sql_campanias = "CREATE TABLE IF NOT EXISTS {$prefijo}campanias (
            id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            nombre varchar(255) NOT NULL,
            asunto varchar(255) NOT NULL,
            asunto_alternativo varchar(255) DEFAULT NULL,
            preview_text varchar(255) DEFAULT '',
            contenido_html longtext DEFAULT NULL,
            contenido_texto longtext DEFAULT NULL,
            plantilla_id bigint(20) UNSIGNED DEFAULT NULL,
            tipo varchar(20) DEFAULT 'regular',
            estado varchar(20) DEFAULT 'borrador',
            listas_ids longtext DEFAULT NULL,
            segmentos_ids longtext DEFAULT NULL,
            remitente_nombre varchar(100) DEFAULT '',
            remitente_email varchar(255) DEFAULT '',
            responder_a varchar(255) DEFAULT '',
            fecha_programada datetime DEFAULT NULL,
            fecha_inicio_envio datetime DEFAULT NULL,
            fecha_fin_envio datetime DEFAULT NULL,
            total_destinatarios int(11) DEFAULT 0,
            total_enviados int(11) DEFAULT 0,
            total_abiertos int(11) DEFAULT 0,
            total_clicks int(11) DEFAULT 0,
            total_bajas int(11) DEFAULT 0,
            total_rebotes int(11) DEFAULT 0,
            total_spam int(11) DEFAULT 0,
            creado_por bigint(20) UNSIGNED DEFAULT NULL,
            fecha_creacion datetime DEFAULT CURRENT_TIMESTAMP,
            fecha_modificacion datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY estado (estado),
            KEY fecha_programada (fecha_programada)
        ) $charset_collate;";

        dbDelta($sql_campanias);

        // Tabla de automatizaciones
        $sql_automatizaciones = "CREATE TABLE IF NOT EXISTS {$prefijo}automatizaciones (
            id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            nombre varchar(255) NOT NULL,
            descripcion text DEFAULT NULL,
            trigger_tipo varchar(50) NOT NULL,
            trigger_condiciones longtext DEFAULT NULL,
            pasos longtext DEFAULT NULL,
            estado varchar(20) DEFAULT 'inactiva',
            total_inscritos int(11) DEFAULT 0,
            total_completados int(11) DEFAULT 0,
            fecha_creacion datetime DEFAULT CURRENT_TIMESTAMP,
            fecha_modificacion datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY trigger_tipo (trigger_tipo),
            KEY estado (estado)
        ) $charset_collate;";

        dbDelta($sql_automatizaciones);

        // Tabla de suscriptores en automatizaciones
        $sql_auto_suscriptores = "CREATE TABLE IF NOT EXISTS {$prefijo}auto_suscriptores (
            id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            automatizacion_id bigint(20) UNSIGNED NOT NULL,
            suscriptor_id bigint(20) UNSIGNED NOT NULL,
            paso_actual int(11) DEFAULT 0,
            estado varchar(20) DEFAULT 'activo',
            fecha_inscripcion datetime DEFAULT CURRENT_TIMESTAMP,
            fecha_proximo_paso datetime DEFAULT NULL,
            fecha_completado datetime DEFAULT NULL,
            historial longtext DEFAULT NULL,
            PRIMARY KEY (id),
            UNIQUE KEY auto_suscriptor (automatizacion_id, suscriptor_id),
            KEY fecha_proximo_paso (fecha_proximo_paso)
        ) $charset_collate;";

        dbDelta($sql_auto_suscriptores);

        // Tabla de plantillas
        $sql_plantillas = "CREATE TABLE IF NOT EXISTS {$prefijo}plantillas (
            id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            nombre varchar(255) NOT NULL,
            categoria varchar(50) DEFAULT 'general',
            contenido_html longtext DEFAULT NULL,
            contenido_texto longtext DEFAULT NULL,
            thumbnail_url varchar(500) DEFAULT '',
            es_predefinida tinyint(1) DEFAULT 0,
            fecha_creacion datetime DEFAULT CURRENT_TIMESTAMP,
            fecha_modificacion datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY categoria (categoria)
        ) $charset_collate;";

        dbDelta($sql_plantillas);

        // Tabla de cola de envío
        $sql_cola = "CREATE TABLE IF NOT EXISTS {$prefijo}cola (
            id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            campania_id bigint(20) UNSIGNED DEFAULT NULL,
            automatizacion_id bigint(20) UNSIGNED DEFAULT NULL,
            paso_auto int(11) DEFAULT NULL,
            suscriptor_id bigint(20) UNSIGNED NOT NULL,
            email varchar(255) NOT NULL,
            asunto varchar(255) NOT NULL,
            contenido longtext NOT NULL,
            prioridad int(11) DEFAULT 5,
            estado varchar(20) DEFAULT 'pendiente',
            intentos int(11) DEFAULT 0,
            max_intentos int(11) DEFAULT 3,
            error_mensaje text DEFAULT NULL,
            programado_para datetime DEFAULT NULL,
            enviado_en datetime DEFAULT NULL,
            fecha_creacion datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY estado (estado),
            KEY programado_para (programado_para),
            KEY prioridad (prioridad),
            KEY campania_id (campania_id)
        ) $charset_collate;";

        dbDelta($sql_cola);

        // Tabla de tracking
        $sql_tracking = "CREATE TABLE IF NOT EXISTS {$prefijo}tracking (
            id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            campania_id bigint(20) UNSIGNED DEFAULT NULL,
            automatizacion_id bigint(20) UNSIGNED DEFAULT NULL,
            suscriptor_id bigint(20) UNSIGNED NOT NULL,
            email_hash varchar(64) DEFAULT '',
            tipo varchar(20) NOT NULL,
            url_clickeada varchar(2000) DEFAULT NULL,
            ip varchar(45) DEFAULT '',
            user_agent varchar(500) DEFAULT '',
            fecha datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY tipo (tipo),
            KEY email_hash (email_hash),
            KEY campania_id (campania_id),
            KEY fecha (fecha)
        ) $charset_collate;";

        dbDelta($sql_tracking);

        // Tabla de rebotes
        $sql_rebotes = "CREATE TABLE IF NOT EXISTS {$prefijo}rebotes (
            id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            email varchar(255) NOT NULL,
            tipo varchar(20) DEFAULT 'soft',
            razon text DEFAULT NULL,
            campania_id bigint(20) UNSIGNED DEFAULT NULL,
            fecha datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY email (email),
            KEY tipo (tipo)
        ) $charset_collate;";

        dbDelta($sql_rebotes);

        // Tabla de logs
        $sql_logs = "CREATE TABLE IF NOT EXISTS {$prefijo}logs (
            id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            nivel varchar(20) DEFAULT 'info',
            mensaje text NOT NULL,
            contexto longtext DEFAULT NULL,
            fecha datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY nivel (nivel),
            KEY fecha (fecha)
        ) $charset_collate;";

        dbDelta($sql_logs);
    }

    /**
     * Crear páginas por defecto
     */
    private function crear_paginas_por_defecto() {
        $paginas = [
            'confirmacion' => [
                'titulo' => __('Confirmar Suscripción', 'flavor-chat-ia'),
                'contenido' => '[em_confirmar_suscripcion]',
                'opcion' => 'flavor_em_pagina_confirmacion',
            ],
            'preferencias' => [
                'titulo' => __('Preferencias de Email', 'flavor-chat-ia'),
                'contenido' => '[em_preferencias]',
                'opcion' => 'flavor_em_pagina_preferencias',
            ],
            'baja' => [
                'titulo' => __('Darse de Baja', 'flavor-chat-ia'),
                'contenido' => '[em_darse_baja]',
                'opcion' => 'flavor_em_pagina_baja',
            ],
        ];

        foreach ($paginas as $slug => $pagina) {
            $pagina_existente = get_option($pagina['opcion']);

            if (!$pagina_existente || !get_post($pagina_existente)) {
                $nueva_pagina_id = wp_insert_post([
                    'post_title' => $pagina['titulo'],
                    'post_content' => $pagina['contenido'],
                    'post_status' => 'publish',
                    'post_type' => 'page',
                    'post_name' => 'email-' . $slug,
                ]);

                if ($nueva_pagina_id && !is_wp_error($nueva_pagina_id)) {
                    update_option($pagina['opcion'], $nueva_pagina_id);
                }
            }
        }
    }

    /**
     * Crear lista por defecto
     */
    private function crear_lista_por_defecto() {
        global $wpdb;

        $tabla_listas = $wpdb->prefix . self::TABLE_PREFIX . 'listas';

        $lista_existe = $wpdb->get_var(
            "SELECT id FROM $tabla_listas WHERE slug = 'newsletter-principal'"
        );

        if (!$lista_existe) {
            $wpdb->insert($tabla_listas, [
                'nombre' => __('Newsletter Principal', 'flavor-chat-ia'),
                'slug' => 'newsletter-principal',
                'descripcion' => __('Lista principal de suscriptores del sitio.', 'flavor-chat-ia'),
                'activa' => 1,
                'publica' => 1,
                'doble_optin' => 1,
            ]);
        }
    }

    /**
     * Verificar si un email está rebotado
     *
     * @param string $email Email a verificar
     * @return bool
     */
    private function es_email_rebotado($email) {
        global $wpdb;

        $rebotes_hard = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM {$wpdb->prefix}" . self::TABLE_PREFIX . "rebotes
             WHERE email = %s AND tipo = 'hard'",
            $email
        ));

        return $rebotes_hard > 0;
    }

    /**
     * Encolar un email para envío
     *
     * @param array $datos Datos del email
     * @return int|false ID de la cola o false si falla
     */
    public function encolar_email($datos) {
        global $wpdb;

        $insert_data = [
            'campania_id' => $datos['campania_id'] ?? null,
            'automatizacion_id' => $datos['automatizacion_id'] ?? null,
            'paso_auto' => $datos['paso_auto'] ?? null,
            'suscriptor_id' => $datos['suscriptor_id'],
            'email' => $datos['email'],
            'asunto' => $datos['asunto'],
            'contenido' => $datos['contenido'],
            'prioridad' => $datos['prioridad'] ?? 5,
            'estado' => 'pendiente',
            'programado_para' => $datos['programado_para'] ?? null,
            'max_intentos' => $datos['max_intentos'] ?? 3,
        ];

        $resultado = $wpdb->insert(
            $wpdb->prefix . self::TABLE_PREFIX . 'cola',
            $insert_data
        );

        return $resultado ? $wpdb->insert_id : false;
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
     * Verifica si se deben cargar los assets del módulo
     *
     * @return bool
     */
    private function should_load_assets() {
        global $post;

        if (!$post) {
            return false;
        }

        $shortcodes_modulo = [
            'em_formulario_suscripcion',
            'em_preferencias',
            'em_confirmar_suscripcion',
            'em_darse_baja',
            'flavor_suscripcion_newsletter',
            'flavor_preferencias_email',
            'flavor_archivo_newsletters',
            'flavor_contador_suscriptores',
            'flavor_formulario_popup',
            'flavor_email_preview',
        ];

        foreach ($shortcodes_modulo as $shortcode) {
            if (has_shortcode($post->post_content, $shortcode)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Encolar assets de frontend
     */
    public function enqueue_frontend_assets() {
        if (!$this->should_load_assets()) {
            return;
        }

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
    // REST API
    // =========================================================================

    /**
     * Registrar rutas REST API
     */
    public function register_rest_routes() {
        $namespace = 'flavor-em/v1';

        // Suscriptores
        register_rest_route($namespace, '/suscriptores', [
            [
                'methods' => WP_REST_Server::READABLE,
                'callback' => [$this, 'rest_get_suscriptores'],
                'permission_callback' => [$this, 'rest_permission_admin'],
            ],
            [
                'methods' => WP_REST_Server::CREATABLE,
                'callback' => [$this, 'rest_create_suscriptor'],
                'permission_callback' => [$this, 'public_permission_check'],
            ],
        ]);

        register_rest_route($namespace, '/suscriptores/(?P<id>\d+)', [
            [
                'methods' => WP_REST_Server::READABLE,
                'callback' => [$this, 'rest_get_suscriptor'],
                'permission_callback' => [$this, 'rest_permission_admin'],
            ],
            [
                'methods' => WP_REST_Server::EDITABLE,
                'callback' => [$this, 'rest_update_suscriptor'],
                'permission_callback' => [$this, 'rest_permission_admin'],
            ],
            [
                'methods' => WP_REST_Server::DELETABLE,
                'callback' => [$this, 'rest_delete_suscriptor'],
                'permission_callback' => [$this, 'rest_permission_admin'],
            ],
        ]);

        // Listas
        register_rest_route($namespace, '/listas', [
            [
                'methods' => WP_REST_Server::READABLE,
                'callback' => [$this, 'rest_get_listas'],
                'permission_callback' => [$this, 'public_permission_check'],
            ],
            [
                'methods' => WP_REST_Server::CREATABLE,
                'callback' => [$this, 'rest_create_lista'],
                'permission_callback' => [$this, 'rest_permission_admin'],
            ],
        ]);

        register_rest_route($namespace, '/listas/(?P<id>\d+)', [
            [
                'methods' => WP_REST_Server::READABLE,
                'callback' => [$this, 'rest_get_lista'],
                'permission_callback' => [$this, 'public_permission_check'],
            ],
            [
                'methods' => WP_REST_Server::EDITABLE,
                'callback' => [$this, 'rest_update_lista'],
                'permission_callback' => [$this, 'rest_permission_admin'],
            ],
            [
                'methods' => WP_REST_Server::DELETABLE,
                'callback' => [$this, 'rest_delete_lista'],
                'permission_callback' => [$this, 'rest_permission_admin'],
            ],
        ]);

        // Campañas
        register_rest_route($namespace, '/campanias', [
            [
                'methods' => WP_REST_Server::READABLE,
                'callback' => [$this, 'rest_get_campanias'],
                'permission_callback' => [$this, 'rest_permission_admin'],
            ],
            [
                'methods' => WP_REST_Server::CREATABLE,
                'callback' => [$this, 'rest_create_campania'],
                'permission_callback' => [$this, 'rest_permission_admin'],
            ],
        ]);

        register_rest_route($namespace, '/campanias/(?P<id>\d+)', [
            [
                'methods' => WP_REST_Server::READABLE,
                'callback' => [$this, 'rest_get_campania'],
                'permission_callback' => [$this, 'rest_permission_admin'],
            ],
            [
                'methods' => WP_REST_Server::EDITABLE,
                'callback' => [$this, 'rest_update_campania'],
                'permission_callback' => [$this, 'rest_permission_admin'],
            ],
            [
                'methods' => WP_REST_Server::DELETABLE,
                'callback' => [$this, 'rest_delete_campania'],
                'permission_callback' => [$this, 'rest_permission_admin'],
            ],
        ]);

        register_rest_route($namespace, '/campanias/(?P<id>\d+)/enviar', [
            'methods' => WP_REST_Server::CREATABLE,
            'callback' => [$this, 'rest_enviar_campania'],
            'permission_callback' => [$this, 'rest_permission_admin'],
        ]);

        register_rest_route($namespace, '/campanias/(?P<id>\d+)/estadisticas', [
            'methods' => WP_REST_Server::READABLE,
            'callback' => [$this, 'rest_estadisticas_campania'],
            'permission_callback' => [$this, 'rest_permission_admin'],
        ]);

        // Automatizaciones
        register_rest_route($namespace, '/automatizaciones', [
            [
                'methods' => WP_REST_Server::READABLE,
                'callback' => [$this, 'rest_get_automatizaciones'],
                'permission_callback' => [$this, 'rest_permission_admin'],
            ],
            [
                'methods' => WP_REST_Server::CREATABLE,
                'callback' => [$this, 'rest_create_automatizacion'],
                'permission_callback' => [$this, 'rest_permission_admin'],
            ],
        ]);

        // Estadísticas generales
        register_rest_route($namespace, '/estadisticas', [
            'methods' => WP_REST_Server::READABLE,
            'callback' => [$this, 'rest_get_estadisticas'],
            'permission_callback' => [$this, 'rest_permission_admin'],
        ]);

        register_rest_route($namespace, '/estadisticas/resumen', [
            'methods' => WP_REST_Server::READABLE,
            'callback' => [$this, 'rest_get_resumen_estadisticas'],
            'permission_callback' => [$this, 'rest_permission_admin'],
        ]);

        // Plantillas
        register_rest_route($namespace, '/plantillas', [
            [
                'methods' => WP_REST_Server::READABLE,
                'callback' => [$this, 'rest_get_plantillas'],
                'permission_callback' => [$this, 'rest_permission_admin'],
            ],
            [
                'methods' => WP_REST_Server::CREATABLE,
                'callback' => [$this, 'rest_create_plantilla'],
                'permission_callback' => [$this, 'rest_permission_admin'],
            ],
        ]);

        // Webhook para rebotes
        register_rest_route($namespace, '/webhook/bounce', [
            'methods' => WP_REST_Server::CREATABLE,
            'callback' => [$this, 'rest_webhook_bounce'],
            'permission_callback' => [$this, 'public_permission_check'],
        ]);

        // Suscripción pública
        register_rest_route($namespace, '/suscribir', [
            'methods' => WP_REST_Server::CREATABLE,
            'callback' => [$this, 'rest_suscribir_publico'],
            'permission_callback' => [$this, 'public_permission_check'],
        ]);

        // Baja pública
        register_rest_route($namespace, '/baja', [
            'methods' => WP_REST_Server::CREATABLE,
            'callback' => [$this, 'rest_baja_publica'],
            'permission_callback' => [$this, 'public_permission_check'],
        ]);
    }

    /**
     * Verifica firma HMAC en webhooks si hay secreto configurado
     *
     * @param WP_REST_Request $request
     * @return true|WP_Error
     */
    private function verify_webhook_signature($request) {
        $secret = (string) get_option('flavor_em_webhook_secret', '');
        if ($secret === '') {
            return true;
        }

        $signature = (string) $request->get_header('X-Flavor-Signature');
        if ($signature === '') {
            return new WP_Error('invalid_signature', __('Firma requerida', 'flavor-chat-ia'), ['status' => 403]);
        }

        $body = $request->get_body();
        $expected = hash_hmac('sha256', $body, $secret);
        if (!hash_equals($expected, $signature)) {
            return new WP_Error('invalid_signature', __('Firma inválida', 'flavor-chat-ia'), ['status' => 403]);
        }

        return true;
    }

    /**
     * Verificar permisos de admin para REST API
     *
     * @return bool
     */
    public function rest_permission_admin() {
        return current_user_can('manage_options');
    }

    /**
     * REST: Obtener suscriptores
     *
     * @param WP_REST_Request $request
     * @return WP_REST_Response
     */
    public function rest_get_suscriptores($request) {
        global $wpdb;

        $pagina = $request->get_param('page') ?: 1;
        $por_pagina = $request->get_param('per_page') ?: 20;
        $estado = $request->get_param('estado');
        $lista_id = $request->get_param('lista_id');
        $busqueda = $request->get_param('search');

        $offset = ($pagina - 1) * $por_pagina;
        $tabla = $wpdb->prefix . self::TABLE_PREFIX . 'suscriptores';
        $tabla_relacion = $wpdb->prefix . self::TABLE_PREFIX . 'suscriptor_lista';

        $where_clauses = ['1=1'];
        $params = [];

        if ($estado) {
            $where_clauses[] = 's.estado = %s';
            $params[] = $estado;
        }

        if ($lista_id) {
            $where_clauses[] = "EXISTS (SELECT 1 FROM $tabla_relacion sl WHERE sl.suscriptor_id = s.id AND sl.lista_id = %d AND sl.estado = 'activo')";
            $params[] = $lista_id;
        }

        if ($busqueda) {
            $where_clauses[] = '(s.email LIKE %s OR s.nombre LIKE %s)';
            $params[] = '%' . $wpdb->esc_like($busqueda) . '%';
            $params[] = '%' . $wpdb->esc_like($busqueda) . '%';
        }

        $where_sql = implode(' AND ', $where_clauses);

        $total = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM $tabla s WHERE $where_sql",
            ...$params
        ));

        $params[] = $por_pagina;
        $params[] = $offset;

        $suscriptores = $wpdb->get_results($wpdb->prepare(
            "SELECT s.* FROM $tabla s WHERE $where_sql ORDER BY s.fecha_registro DESC LIMIT %d OFFSET %d",
            ...$params
        ));

        return new WP_REST_Response([
            'items' => $suscriptores,
            'total' => (int) $total,
            'page' => (int) $pagina,
            'per_page' => (int) $por_pagina,
            'total_pages' => ceil($total / $por_pagina),
        ]);
    }

    /**
     * REST: Obtener un suscriptor
     *
     * @param WP_REST_Request $request
     * @return WP_REST_Response|WP_Error
     */
    public function rest_get_suscriptor($request) {
        global $wpdb;

        $id = $request->get_param('id');
        $tabla = $wpdb->prefix . self::TABLE_PREFIX . 'suscriptores';

        $suscriptor = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM $tabla WHERE id = %d",
            $id
        ));

        if (!$suscriptor) {
            return new WP_Error('not_found', __('Suscriptor no encontrado', 'flavor-chat-ia'), ['status' => 404]);
        }

        // Obtener listas del suscriptor
        $tabla_relacion = $wpdb->prefix . self::TABLE_PREFIX . 'suscriptor_lista';
        $tabla_listas = $wpdb->prefix . self::TABLE_PREFIX . 'listas';

        $listas = $wpdb->get_results($wpdb->prepare(
            "SELECT l.*, sl.estado as estado_suscripcion, sl.fecha_suscripcion
             FROM $tabla_listas l
             INNER JOIN $tabla_relacion sl ON l.id = sl.lista_id
             WHERE sl.suscriptor_id = %d",
            $id
        ));

        $suscriptor->listas = $listas;
        $suscriptor->tags = json_decode($suscriptor->tags) ?: [];
        $suscriptor->campos_personalizados = json_decode($suscriptor->campos_personalizados) ?: new stdClass();

        return new WP_REST_Response($suscriptor);
    }

    /**
     * REST: Crear suscriptor
     *
     * @param WP_REST_Request $request
     * @return WP_REST_Response|WP_Error
     */
    public function rest_create_suscriptor($request) {
        $email = sanitize_email($request->get_param('email'));
        $lista = $request->get_param('lista') ?: 'newsletter-principal';
        $nombre = sanitize_text_field($request->get_param('nombre') ?: '');

        if (!is_email($email)) {
            return new WP_Error('invalid_email', __('Email no válido', 'flavor-chat-ia'), ['status' => 400]);
        }

        // Verificar rate limit si está habilitado
        if (!$this->verificar_rate_limit()) {
            return new WP_Error('rate_limited', __('Demasiadas solicitudes. Intenta más tarde.', 'flavor-chat-ia'), ['status' => 429]);
        }

        $resultado = $this->suscribir($email, $lista, [
            'nombre' => $nombre,
            'origen' => 'api',
        ]);

        if (!$resultado['success']) {
            return new WP_Error('subscription_failed', $resultado['error'], ['status' => 400]);
        }

        return new WP_REST_Response($resultado, 201);
    }

    /**
     * REST: Actualizar suscriptor
     *
     * @param WP_REST_Request $request
     * @return WP_REST_Response|WP_Error
     */
    public function rest_update_suscriptor($request) {
        global $wpdb;

        $id = $request->get_param('id');
        $tabla = $wpdb->prefix . self::TABLE_PREFIX . 'suscriptores';

        $suscriptor = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM $tabla WHERE id = %d",
            $id
        ));

        if (!$suscriptor) {
            return new WP_Error('not_found', __('Suscriptor no encontrado', 'flavor-chat-ia'), ['status' => 404]);
        }

        $update_data = [];

        if ($request->has_param('nombre')) {
            $update_data['nombre'] = sanitize_text_field($request->get_param('nombre'));
        }
        if ($request->has_param('apellidos')) {
            $update_data['apellidos'] = sanitize_text_field($request->get_param('apellidos'));
        }
        if ($request->has_param('estado')) {
            $nuevo_estado = $request->get_param('estado');
            if (in_array($nuevo_estado, self::ESTADOS_SUSCRIPTOR)) {
                $update_data['estado'] = $nuevo_estado;
            }
        }
        if ($request->has_param('tags')) {
            $update_data['tags'] = wp_json_encode($request->get_param('tags'));
        }
        if ($request->has_param('campos_personalizados')) {
            $update_data['campos_personalizados'] = wp_json_encode($request->get_param('campos_personalizados'));
        }

        if (!empty($update_data)) {
            $wpdb->update($tabla, $update_data, ['id' => $id]);
        }

        return $this->rest_get_suscriptor($request);
    }

    /**
     * REST: Eliminar suscriptor
     *
     * @param WP_REST_Request $request
     * @return WP_REST_Response|WP_Error
     */
    public function rest_delete_suscriptor($request) {
        global $wpdb;

        $id = $request->get_param('id');
        $tabla = $wpdb->prefix . self::TABLE_PREFIX . 'suscriptores';
        $tabla_relacion = $wpdb->prefix . self::TABLE_PREFIX . 'suscriptor_lista';

        $suscriptor = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM $tabla WHERE id = %d",
            $id
        ));

        if (!$suscriptor) {
            return new WP_Error('not_found', __('Suscriptor no encontrado', 'flavor-chat-ia'), ['status' => 404]);
        }

        // Eliminar relaciones con listas
        $wpdb->delete($tabla_relacion, ['suscriptor_id' => $id]);

        // Eliminar suscriptor
        $wpdb->delete($tabla, ['id' => $id]);

        return new WP_REST_Response(['deleted' => true]);
    }

    /**
     * REST: Obtener listas
     *
     * @param WP_REST_Request $request
     * @return WP_REST_Response
     */
    public function rest_get_listas($request) {
        global $wpdb;

        $solo_publicas = !current_user_can('manage_options');
        $tabla = $wpdb->prefix . self::TABLE_PREFIX . 'listas';

        $where = $solo_publicas ? "WHERE activa = 1 AND publica = 1" : "WHERE 1=1";

        $listas = $wpdb->get_results(
            "SELECT * FROM $tabla $where ORDER BY nombre ASC"
        );

        return new WP_REST_Response($listas);
    }

    /**
     * REST: Obtener una lista
     *
     * @param WP_REST_Request $request
     * @return WP_REST_Response|WP_Error
     */
    public function rest_get_lista($request) {
        global $wpdb;

        $id = $request->get_param('id');
        $tabla = $wpdb->prefix . self::TABLE_PREFIX . 'listas';

        $lista = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM $tabla WHERE id = %d",
            $id
        ));

        if (!$lista) {
            return new WP_Error('not_found', __('Lista no encontrada', 'flavor-chat-ia'), ['status' => 404]);
        }

        return new WP_REST_Response($lista);
    }

    /**
     * REST: Crear lista
     *
     * @param WP_REST_Request $request
     * @return WP_REST_Response|WP_Error
     */
    public function rest_create_lista($request) {
        global $wpdb;

        $nombre = sanitize_text_field($request->get_param('nombre'));
        $slug = sanitize_title($request->get_param('slug') ?: $nombre);

        if (empty($nombre)) {
            return new WP_Error('invalid_name', __('El nombre es requerido', 'flavor-chat-ia'), ['status' => 400]);
        }

        $tabla = $wpdb->prefix . self::TABLE_PREFIX . 'listas';

        // Verificar que el slug no exista
        $slug_existe = $wpdb->get_var($wpdb->prepare(
            "SELECT id FROM $tabla WHERE slug = %s",
            $slug
        ));

        if ($slug_existe) {
            $slug = $slug . '-' . time();
        }

        $wpdb->insert($tabla, [
            'nombre' => $nombre,
            'slug' => $slug,
            'descripcion' => sanitize_textarea_field($request->get_param('descripcion') ?: ''),
            'activa' => (bool) $request->get_param('activa') !== false ? 1 : 0,
            'publica' => (bool) $request->get_param('publica') !== false ? 1 : 0,
            'doble_optin' => (bool) $request->get_param('doble_optin') !== false ? 1 : 0,
            'mensaje_bienvenida' => wp_kses_post($request->get_param('mensaje_bienvenida') ?: ''),
        ]);

        $nueva_lista_id = $wpdb->insert_id;

        $request->set_param('id', $nueva_lista_id);
        return $this->rest_get_lista($request);
    }

    /**
     * REST: Actualizar lista
     *
     * @param WP_REST_Request $request
     * @return WP_REST_Response|WP_Error
     */
    public function rest_update_lista($request) {
        global $wpdb;

        $id = $request->get_param('id');
        $tabla = $wpdb->prefix . self::TABLE_PREFIX . 'listas';

        $lista = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM $tabla WHERE id = %d",
            $id
        ));

        if (!$lista) {
            return new WP_Error('not_found', __('Lista no encontrada', 'flavor-chat-ia'), ['status' => 404]);
        }

        $update_data = [];

        if ($request->has_param('nombre')) {
            $update_data['nombre'] = sanitize_text_field($request->get_param('nombre'));
        }
        if ($request->has_param('descripcion')) {
            $update_data['descripcion'] = sanitize_textarea_field($request->get_param('descripcion'));
        }
        if ($request->has_param('activa')) {
            $update_data['activa'] = (bool) $request->get_param('activa') ? 1 : 0;
        }
        if ($request->has_param('publica')) {
            $update_data['publica'] = (bool) $request->get_param('publica') ? 1 : 0;
        }
        if ($request->has_param('doble_optin')) {
            $update_data['doble_optin'] = (bool) $request->get_param('doble_optin') ? 1 : 0;
        }
        if ($request->has_param('mensaje_bienvenida')) {
            $update_data['mensaje_bienvenida'] = wp_kses_post($request->get_param('mensaje_bienvenida'));
        }

        if (!empty($update_data)) {
            $wpdb->update($tabla, $update_data, ['id' => $id]);
        }

        return $this->rest_get_lista($request);
    }

    /**
     * REST: Eliminar lista
     *
     * @param WP_REST_Request $request
     * @return WP_REST_Response|WP_Error
     */
    public function rest_delete_lista($request) {
        global $wpdb;

        $id = $request->get_param('id');
        $tabla = $wpdb->prefix . self::TABLE_PREFIX . 'listas';
        $tabla_relacion = $wpdb->prefix . self::TABLE_PREFIX . 'suscriptor_lista';

        $lista = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM $tabla WHERE id = %d",
            $id
        ));

        if (!$lista) {
            return new WP_Error('not_found', __('Lista no encontrada', 'flavor-chat-ia'), ['status' => 404]);
        }

        // Eliminar relaciones
        $wpdb->delete($tabla_relacion, ['lista_id' => $id]);

        // Eliminar lista
        $wpdb->delete($tabla, ['id' => $id]);

        return new WP_REST_Response(['deleted' => true]);
    }

    /**
     * REST: Obtener campañas
     *
     * @param WP_REST_Request $request
     * @return WP_REST_Response
     */
    public function rest_get_campanias($request) {
        global $wpdb;

        $pagina = $request->get_param('page') ?: 1;
        $por_pagina = $request->get_param('per_page') ?: 20;
        $estado = $request->get_param('estado');

        $offset = ($pagina - 1) * $por_pagina;
        $tabla = $wpdb->prefix . self::TABLE_PREFIX . 'campanias';

        $where = '';
        $params = [];

        if ($estado) {
            $where = 'WHERE estado = %s';
            $params[] = $estado;
        }

        $total = $wpdb->get_var(
            $estado
                ? $wpdb->prepare("SELECT COUNT(*) FROM $tabla $where", ...$params)
                : "SELECT COUNT(*) FROM $tabla"
        );

        $params[] = $por_pagina;
        $params[] = $offset;

        $campanias = $estado
            ? $wpdb->get_results($wpdb->prepare(
                "SELECT * FROM $tabla $where ORDER BY fecha_creacion DESC LIMIT %d OFFSET %d",
                ...$params
            ))
            : $wpdb->get_results($wpdb->prepare(
                "SELECT * FROM $tabla ORDER BY fecha_creacion DESC LIMIT %d OFFSET %d",
                $por_pagina,
                $offset
            ));

        return new WP_REST_Response([
            'items' => $campanias,
            'total' => (int) $total,
            'page' => (int) $pagina,
            'per_page' => (int) $por_pagina,
            'total_pages' => ceil($total / $por_pagina),
        ]);
    }

    /**
     * REST: Obtener una campaña
     *
     * @param WP_REST_Request $request
     * @return WP_REST_Response|WP_Error
     */
    public function rest_get_campania($request) {
        global $wpdb;

        $id = $request->get_param('id');
        $tabla = $wpdb->prefix . self::TABLE_PREFIX . 'campanias';

        $campania = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM $tabla WHERE id = %d",
            $id
        ));

        if (!$campania) {
            return new WP_Error('not_found', __('Campaña no encontrada', 'flavor-chat-ia'), ['status' => 404]);
        }

        $campania->listas_ids = json_decode($campania->listas_ids) ?: [];

        return new WP_REST_Response($campania);
    }

    /**
     * REST: Crear campaña
     *
     * @param WP_REST_Request $request
     * @return WP_REST_Response|WP_Error
     */
    public function rest_create_campania($request) {
        $datos = [
            'nombre' => sanitize_text_field($request->get_param('nombre')),
            'asunto' => sanitize_text_field($request->get_param('asunto')),
            'contenido_html' => wp_kses_post($request->get_param('contenido_html') ?: ''),
            'contenido_texto' => sanitize_textarea_field($request->get_param('contenido_texto') ?: ''),
            'listas_ids' => $request->get_param('listas_ids') ?: [],
            'tipo' => $request->get_param('tipo') ?: 'regular',
        ];

        if (empty($datos['nombre']) || empty($datos['asunto'])) {
            return new WP_Error('invalid_data', __('Nombre y asunto son requeridos', 'flavor-chat-ia'), ['status' => 400]);
        }

        $campania_id = $this->crear_campania($datos);

        $request->set_param('id', $campania_id);
        return $this->rest_get_campania($request);
    }

    /**
     * REST: Actualizar campaña
     *
     * @param WP_REST_Request $request
     * @return WP_REST_Response|WP_Error
     */
    public function rest_update_campania($request) {
        global $wpdb;

        $id = $request->get_param('id');
        $tabla = $wpdb->prefix . self::TABLE_PREFIX . 'campanias';

        $campania = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM $tabla WHERE id = %d",
            $id
        ));

        if (!$campania) {
            return new WP_Error('not_found', __('Campaña no encontrada', 'flavor-chat-ia'), ['status' => 404]);
        }

        if (!in_array($campania->estado, ['borrador', 'programada'])) {
            return new WP_Error('cannot_edit', __('No se puede editar una campaña en este estado', 'flavor-chat-ia'), ['status' => 400]);
        }

        $update_data = [];

        if ($request->has_param('nombre')) {
            $update_data['nombre'] = sanitize_text_field($request->get_param('nombre'));
        }
        if ($request->has_param('asunto')) {
            $update_data['asunto'] = sanitize_text_field($request->get_param('asunto'));
        }
        if ($request->has_param('contenido_html')) {
            $update_data['contenido_html'] = wp_kses_post($request->get_param('contenido_html'));
        }
        if ($request->has_param('contenido_texto')) {
            $update_data['contenido_texto'] = sanitize_textarea_field($request->get_param('contenido_texto'));
        }
        if ($request->has_param('listas_ids')) {
            $update_data['listas_ids'] = wp_json_encode($request->get_param('listas_ids'));
        }

        if (!empty($update_data)) {
            $wpdb->update($tabla, $update_data, ['id' => $id]);
        }

        return $this->rest_get_campania($request);
    }

    /**
     * REST: Eliminar campaña
     *
     * @param WP_REST_Request $request
     * @return WP_REST_Response|WP_Error
     */
    public function rest_delete_campania($request) {
        global $wpdb;

        $id = $request->get_param('id');
        $tabla = $wpdb->prefix . self::TABLE_PREFIX . 'campanias';

        $campania = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM $tabla WHERE id = %d",
            $id
        ));

        if (!$campania) {
            return new WP_Error('not_found', __('Campaña no encontrada', 'flavor-chat-ia'), ['status' => 404]);
        }

        if ($campania->estado === 'enviando') {
            return new WP_Error('cannot_delete', __('No se puede eliminar una campaña en envío', 'flavor-chat-ia'), ['status' => 400]);
        }

        $wpdb->delete($tabla, ['id' => $id]);

        return new WP_REST_Response(['deleted' => true]);
    }

    /**
     * REST: Enviar campaña
     *
     * @param WP_REST_Request $request
     * @return WP_REST_Response|WP_Error
     */
    public function rest_enviar_campania($request) {
        $id = $request->get_param('id');
        $fecha_programada = $request->get_param('fecha_programada');

        $resultado = $this->programar_campania($id, $fecha_programada);

        if (!$resultado['success']) {
            return new WP_Error('send_failed', $resultado['error'], ['status' => 400]);
        }

        return new WP_REST_Response($resultado);
    }

    /**
     * REST: Estadísticas de campaña
     *
     * @param WP_REST_Request $request
     * @return WP_REST_Response|WP_Error
     */
    public function rest_estadisticas_campania($request) {
        $resultado = $this->action_estadisticas_campania([
            'campania_id' => $request->get_param('id'),
        ]);

        if (!$resultado['success']) {
            return new WP_Error('not_found', $resultado['error'], ['status' => 404]);
        }

        return new WP_REST_Response($resultado['campania']);
    }

    /**
     * REST: Obtener automatizaciones
     *
     * @param WP_REST_Request $request
     * @return WP_REST_Response
     */
    public function rest_get_automatizaciones($request) {
        global $wpdb;

        $tabla = $wpdb->prefix . self::TABLE_PREFIX . 'automatizaciones';

        $automatizaciones = $wpdb->get_results(
            "SELECT * FROM $tabla ORDER BY fecha_creacion DESC"
        );

        foreach ($automatizaciones as &$auto) {
            $auto->pasos = json_decode($auto->pasos) ?: [];
            $auto->trigger_condiciones = json_decode($auto->trigger_condiciones) ?: new stdClass();
        }

        return new WP_REST_Response($automatizaciones);
    }

    /**
     * REST: Crear automatización
     *
     * @param WP_REST_Request $request
     * @return WP_REST_Response|WP_Error
     */
    public function rest_create_automatizacion($request) {
        global $wpdb;

        $nombre = sanitize_text_field($request->get_param('nombre'));
        $trigger_tipo = sanitize_key($request->get_param('trigger_tipo'));

        if (empty($nombre) || !in_array($trigger_tipo, self::TRIGGERS_AUTOMATIZACION)) {
            return new WP_Error('invalid_data', __('Datos inválidos', 'flavor-chat-ia'), ['status' => 400]);
        }

        $tabla = $wpdb->prefix . self::TABLE_PREFIX . 'automatizaciones';

        $wpdb->insert($tabla, [
            'nombre' => $nombre,
            'descripcion' => sanitize_textarea_field($request->get_param('descripcion') ?: ''),
            'trigger_tipo' => $trigger_tipo,
            'trigger_condiciones' => wp_json_encode($request->get_param('trigger_condiciones') ?: []),
            'pasos' => wp_json_encode($request->get_param('pasos') ?: []),
            'estado' => 'inactiva',
        ]);

        $nueva_id = $wpdb->insert_id;

        $auto = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM $tabla WHERE id = %d",
            $nueva_id
        ));

        $auto->pasos = json_decode($auto->pasos) ?: [];
        $auto->trigger_condiciones = json_decode($auto->trigger_condiciones) ?: new stdClass();

        return new WP_REST_Response($auto, 201);
    }

    /**
     * REST: Estadísticas generales
     *
     * @param WP_REST_Request $request
     * @return WP_REST_Response
     */
    public function rest_get_estadisticas($request) {
        $resultado = $this->action_estadisticas_generales([]);
        return new WP_REST_Response($resultado['estadisticas']);
    }

    /**
     * REST: Resumen de estadísticas
     *
     * @param WP_REST_Request $request
     * @return WP_REST_Response
     */
    public function rest_get_resumen_estadisticas($request) {
        global $wpdb;

        $periodo = $request->get_param('periodo') ?: '30d';
        $fecha_inicio = $this->calcular_fecha_inicio_periodo($periodo);

        $prefijo = $wpdb->prefix . self::TABLE_PREFIX;

        // Suscriptores nuevos
        $nuevos_suscriptores = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM {$prefijo}suscriptores WHERE fecha_registro >= %s",
            $fecha_inicio
        ));

        // Bajas
        $bajas = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM {$prefijo}suscriptores WHERE fecha_baja >= %s",
            $fecha_inicio
        ));

        // Emails enviados
        $emails_enviados = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM {$prefijo}cola WHERE estado = 'enviado' AND enviado_en >= %s",
            $fecha_inicio
        ));

        // Aperturas
        $aperturas = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM {$prefijo}tracking WHERE tipo = 'abierto' AND fecha >= %s",
            $fecha_inicio
        ));

        // Clicks
        $clicks = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM {$prefijo}tracking WHERE tipo = 'click' AND fecha >= %s",
            $fecha_inicio
        ));

        // Calcular tasas
        $tasa_apertura = $emails_enviados > 0 ? round(($aperturas / $emails_enviados) * 100, 2) : 0;
        $tasa_clicks = $aperturas > 0 ? round(($clicks / $aperturas) * 100, 2) : 0;

        // Datos por día para gráfico
        $datos_diarios = $wpdb->get_results($wpdb->prepare(
            "SELECT DATE(fecha_registro) as fecha, COUNT(*) as total
             FROM {$prefijo}suscriptores
             WHERE fecha_registro >= %s
             GROUP BY DATE(fecha_registro)
             ORDER BY fecha ASC",
            $fecha_inicio
        ));

        return new WP_REST_Response([
            'periodo' => $periodo,
            'fecha_inicio' => $fecha_inicio,
            'nuevos_suscriptores' => (int) $nuevos_suscriptores,
            'bajas' => (int) $bajas,
            'emails_enviados' => (int) $emails_enviados,
            'aperturas' => (int) $aperturas,
            'clicks' => (int) $clicks,
            'tasa_apertura' => $tasa_apertura,
            'tasa_clicks' => $tasa_clicks,
            'crecimiento_neto' => (int) $nuevos_suscriptores - (int) $bajas,
            'datos_diarios' => $datos_diarios,
        ]);
    }

    /**
     * Calcular fecha de inicio según período
     *
     * @param string $periodo
     * @return string
     */
    private function calcular_fecha_inicio_periodo($periodo) {
        switch ($periodo) {
            case '7d':
                return date('Y-m-d H:i:s', strtotime('-7 days'));
            case '30d':
                return date('Y-m-d H:i:s', strtotime('-30 days'));
            case '90d':
                return date('Y-m-d H:i:s', strtotime('-90 days'));
            case '1y':
                return date('Y-m-d H:i:s', strtotime('-1 year'));
            default:
                return date('Y-m-d H:i:s', strtotime('-30 days'));
        }
    }

    /**
     * REST: Obtener plantillas
     *
     * @param WP_REST_Request $request
     * @return WP_REST_Response
     */
    public function rest_get_plantillas($request) {
        global $wpdb;

        $categoria = $request->get_param('categoria');
        $tabla = $wpdb->prefix . self::TABLE_PREFIX . 'plantillas';

        $where = $categoria ? $wpdb->prepare("WHERE categoria = %s", $categoria) : '';

        $plantillas = $wpdb->get_results(
            "SELECT * FROM $tabla $where ORDER BY nombre ASC"
        );

        return new WP_REST_Response($plantillas);
    }

    /**
     * REST: Crear plantilla
     *
     * @param WP_REST_Request $request
     * @return WP_REST_Response|WP_Error
     */
    public function rest_create_plantilla($request) {
        global $wpdb;

        $nombre = sanitize_text_field($request->get_param('nombre'));

        if (empty($nombre)) {
            return new WP_Error('invalid_name', __('El nombre es requerido', 'flavor-chat-ia'), ['status' => 400]);
        }

        $tabla = $wpdb->prefix . self::TABLE_PREFIX . 'plantillas';

        $wpdb->insert($tabla, [
            'nombre' => $nombre,
            'categoria' => sanitize_key($request->get_param('categoria') ?: 'general'),
            'contenido_html' => wp_kses_post($request->get_param('contenido_html') ?: ''),
            'contenido_texto' => sanitize_textarea_field($request->get_param('contenido_texto') ?: ''),
            'thumbnail_url' => esc_url_raw($request->get_param('thumbnail_url') ?: ''),
        ]);

        $nueva_id = $wpdb->insert_id;

        $plantilla = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM $tabla WHERE id = %d",
            $nueva_id
        ));

        return new WP_REST_Response($plantilla, 201);
    }

    /**
     * REST: Webhook de rebotes
     *
     * @param WP_REST_Request $request
     * @return WP_REST_Response
     */
    public function rest_webhook_bounce($request) {
        $firma = $this->verify_webhook_signature($request);
        if (is_wp_error($firma)) {
            return $firma;
        }

        $email = sanitize_email($request->get_param('email'));
        $tipo = sanitize_key($request->get_param('type') ?: 'soft');
        $razon = sanitize_text_field($request->get_param('reason') ?: '');

        if (!is_email($email)) {
            return new WP_REST_Response(['error' => __('Invalid email', 'flavor-chat-ia')], 400);
        }

        $this->registrar_rebote($email, $tipo, $razon);

        return new WP_REST_Response(['success' => true]);
    }

    /**
     * REST: Suscripción pública
     *
     * @param WP_REST_Request $request
     * @return WP_REST_Response
     */
    public function rest_suscribir_publico($request) {
        return $this->rest_create_suscriptor($request);
    }

    /**
     * REST: Baja pública
     *
     * @param WP_REST_Request $request
     * @return WP_REST_Response|WP_Error
     */
    public function rest_baja_publica($request) {
        $token = sanitize_text_field($request->get_param('token'));
        $motivo = sanitize_text_field($request->get_param('motivo') ?: '');

        $suscriptor = $this->get_suscriptor_por_token($token);

        if (!$suscriptor) {
            return new WP_Error('invalid_token', __('Token no válido', 'flavor-chat-ia'), ['status' => 400]);
        }

        $resultado = $this->dar_de_baja($suscriptor->id, null, $motivo);

        return new WP_REST_Response($resultado);
    }

    /**
     * Registrar rebote
     *
     * @param string $email
     * @param string $tipo
     * @param string $razon
     * @param int|null $campania_id
     */
    private function registrar_rebote($email, $tipo, $razon, $campania_id = null) {
        global $wpdb;

        $tabla_rebotes = $wpdb->prefix . self::TABLE_PREFIX . 'rebotes';
        $tabla_suscriptores = $wpdb->prefix . self::TABLE_PREFIX . 'suscriptores';

        // Registrar rebote
        $wpdb->insert($tabla_rebotes, [
            'email' => $email,
            'tipo' => $tipo,
            'razon' => $razon,
            'campania_id' => $campania_id,
        ]);

        // Si es hard bounce, marcar suscriptor como rebotado
        if ($tipo === 'hard') {
            $wpdb->update(
                $tabla_suscriptores,
                ['estado' => 'rebotado'],
                ['email' => $email]
            );
        }

        // Notificar si está habilitado
        $settings = $this->get_settings();
        if (!empty($settings['notificar_rebotes'])) {
            $this->enviar_notificacion_admin('rebote', [
                'email' => $email,
                'tipo' => $tipo,
                'razon' => $razon,
            ]);
        }
    }

    /**
     * Verificar rate limit
     *
     * @return bool
     */
    private function verificar_rate_limit() {
        $settings = $this->get_settings();

        if (empty($settings['rate_limit_enabled'])) {
            return true;
        }

        $ip = $this->get_client_ip();
        $transient_key = 'em_rate_limit_' . md5($ip);
        $intentos = get_transient($transient_key);

        if ($intentos === false) {
            set_transient($transient_key, 1, $settings['rate_limit_window']);
            return true;
        }

        if ($intentos >= $settings['rate_limit_requests']) {
            return false;
        }

        set_transient($transient_key, $intentos + 1, $settings['rate_limit_window']);
        return true;
    }

    /**
     * Enviar notificación al admin
     *
     * @param string $tipo
     * @param array $datos
     */
    private function enviar_notificacion_admin($tipo, $datos) {
        $settings = $this->get_settings();
        $email_admin = $settings['email_notificaciones'] ?: get_option('admin_email');

        $asuntos = [
            'nuevo_suscriptor' => sprintf(__('[%s] Nuevo suscriptor', 'flavor-chat-ia'), get_bloginfo('name')),
            'baja' => sprintf(__('[%s] Baja de suscriptor', 'flavor-chat-ia'), get_bloginfo('name')),
            'rebote' => sprintf(__('[%s] Email rebotado', 'flavor-chat-ia'), get_bloginfo('name')),
        ];

        $asunto = $asuntos[$tipo] ?? sprintf(__('[%s] Notificación Email Marketing', 'flavor-chat-ia'), get_bloginfo('name'));

        $mensaje = $this->generar_mensaje_notificacion($tipo, $datos);

        wp_mail($email_admin, $asunto, $mensaje);
    }

    /**
     * Generar mensaje de notificación
     *
     * @param string $tipo
     * @param array $datos
     * @return string
     */
    private function generar_mensaje_notificacion($tipo, $datos) {
        switch ($tipo) {
            case 'nuevo_suscriptor':
                return sprintf(
                    __("Nuevo suscriptor:\n\nEmail: %s\nNombre: %s\nLista: %s\nFecha: %s", 'flavor-chat-ia'),
                    $datos['email'] ?? '',
                    $datos['nombre'] ?? '-',
                    $datos['lista'] ?? '-',
                    current_time('mysql')
                );

            case 'baja':
                return sprintf(
                    __("Baja de suscriptor:\n\nEmail: %s\nMotivo: %s\nFecha: %s", 'flavor-chat-ia'),
                    $datos['email'] ?? '',
                    $datos['motivo'] ?? '-',
                    current_time('mysql')
                );

            case 'rebote':
                return sprintf(
                    __("Email rebotado:\n\nEmail: %s\nTipo: %s\nRazón: %s\nFecha: %s", 'flavor-chat-ia'),
                    $datos['email'] ?? '',
                    $datos['tipo'] ?? '-',
                    $datos['razon'] ?? '-',
                    current_time('mysql')
                );

            default:
                return wp_json_encode($datos, JSON_PRETTY_PRINT);
        }
    }

    // =========================================================================
    // SHORTCODES
    // =========================================================================

    /**
     * Shortcode de formulario de suscripción
     *
     * @param array $atts Atributos del shortcode
     * @return string HTML del formulario
     */
    public function shortcode_formulario_suscripcion($atts) {
        $atts = shortcode_atts([
            'lista' => 'newsletter-principal',
            'titulo' => __('Suscribete a nuestro newsletter', 'flavor-chat-ia'),
            'descripcion' => __('Recibe las ultimas novedades en tu email.', 'flavor-chat-ia'),
            'boton' => __('Suscribirme', 'flavor-chat-ia'),
            'mostrar_nombre' => 'true',
            'estilo' => 'card',
            'redirect' => '',
            'class' => '',
        ], $atts, 'em_formulario_suscripcion');

        $template_path = plugin_dir_path(__FILE__) . 'templates/formulario-suscripcion.php';

        if (file_exists($template_path)) {
            ob_start();
            include $template_path;
            return ob_get_clean();
        }

        // Fallback si no existe el template
        return $this->render_formulario_suscripcion_inline($atts);
    }

    /**
     * Render inline del formulario de suscripción
     *
     * @param array $atts
     * @return string
     */
    private function render_formulario_suscripcion_inline($atts) {
        $unique_id = 'em-form-' . wp_rand(1000, 9999);
        $mostrar_nombre = filter_var($atts['mostrar_nombre'], FILTER_VALIDATE_BOOLEAN);
        $settings = $this->get_settings();

        ob_start();
        ?>
        <div class="flavor-em-formulario <?php echo esc_attr($atts['estilo']); ?> <?php echo esc_attr($atts['class']); ?>" id="<?php echo esc_attr($unique_id); ?>">
            <?php if (!empty($atts['titulo'])): ?>
                <h3 class="em-titulo"><?php echo esc_html($atts['titulo']); ?></h3>
            <?php endif; ?>

            <?php if (!empty($atts['descripcion'])): ?>
                <p class="em-descripcion"><?php echo esc_html($atts['descripcion']); ?></p>
            <?php endif; ?>

            <form class="em-form" data-lista="<?php echo esc_attr($atts['lista']); ?>" data-redirect="<?php echo esc_url($atts['redirect']); ?>">
                <?php wp_nonce_field('flavor_em_public', 'em_nonce'); ?>

                <?php if ($mostrar_nombre): ?>
                    <div class="em-campo">
                        <input type="text" name="nombre" placeholder="<?php esc_attr_e('Tu nombre', 'flavor-chat-ia'); ?>" class="em-input">
                    </div>
                <?php endif; ?>

                <div class="em-campo">
                    <input type="email" name="email" placeholder="<?php esc_attr_e('Tu email', 'flavor-chat-ia'); ?>" required class="em-input">
                </div>

                <?php if (!empty($settings['honeypot_enabled'])): ?>
                    <div class="em-hp" style="position:absolute;left:-9999px;">
                        <input type="text" name="em_website" tabindex="-1" autocomplete="off">
                    </div>
                <?php endif; ?>

                <div class="em-campo">
                    <button type="submit" class="em-boton"><?php echo esc_html($atts['boton']); ?></button>
                </div>

                <div class="em-mensaje" style="display:none;"></div>
            </form>
        </div>
        <?php
        return ob_get_clean();
    }

    /**
     * Shortcode de preferencias de suscriptor
     *
     * @param array $atts
     * @return string
     */
    public function shortcode_preferencias($atts) {
        $token = isset($_GET['token']) ? sanitize_text_field($_GET['token']) : '';

        if (empty($token)) {
            return '<p class="em-error">' . __('Token no valido.', 'flavor-chat-ia') . '</p>';
        }

        $suscriptor = $this->get_suscriptor_por_token($token);

        if (!$suscriptor) {
            return '<p class="em-error">' . __('Suscriptor no encontrado.', 'flavor-chat-ia') . '</p>';
        }

        $template_path = plugin_dir_path(__FILE__) . 'templates/preferencias.php';

        if (file_exists($template_path)) {
            ob_start();
            include $template_path;
            return ob_get_clean();
        }

        return $this->render_preferencias_inline($suscriptor, $token);
    }

    /**
     * Render inline de preferencias
     *
     * @param object $suscriptor
     * @param string $token
     * @return string
     */
    private function render_preferencias_inline($suscriptor, $token) {
        global $wpdb;

        $tabla_relacion = $wpdb->prefix . self::TABLE_PREFIX . 'suscriptor_lista';
        $tabla_listas = $wpdb->prefix . self::TABLE_PREFIX . 'listas';

        $listas_suscriptor = $wpdb->get_results($wpdb->prepare(
            "SELECT l.*, sl.estado as estado_suscripcion
             FROM $tabla_listas l
             INNER JOIN $tabla_relacion sl ON l.id = sl.lista_id
             WHERE sl.suscriptor_id = %d AND l.publica = 1",
            $suscriptor->id
        ));

        $listas_disponibles = $wpdb->get_results($wpdb->prepare(
            "SELECT l.* FROM $tabla_listas l
             WHERE l.activa = 1 AND l.publica = 1
             AND l.id NOT IN (
                 SELECT lista_id FROM $tabla_relacion WHERE suscriptor_id = %d
             )",
            $suscriptor->id
        ));

        ob_start();
        ?>
        <div class="flavor-em-preferencias">
            <h3><?php esc_html_e('Preferencias de Email', 'flavor-chat-ia'); ?></h3>

            <p><?php printf(esc_html__('Email: %s', 'flavor-chat-ia'), esc_html($suscriptor->email)); ?></p>

            <form class="em-preferencias-form" data-token="<?php echo esc_attr($token); ?>">
                <?php wp_nonce_field('flavor_em_public', 'em_nonce'); ?>

                <h4><?php esc_html_e('Tus suscripciones actuales', 'flavor-chat-ia'); ?></h4>

                <?php if (empty($listas_suscriptor)): ?>
                    <p><?php esc_html_e('No estas suscrito a ninguna lista.', 'flavor-chat-ia'); ?></p>
                <?php else: ?>
                    <?php foreach ($listas_suscriptor as $lista): ?>
                        <label class="em-checkbox">
                            <input type="checkbox" name="listas[]" value="<?php echo esc_attr($lista->id); ?>"
                                <?php checked($lista->estado_suscripcion, 'activo'); ?>>
                            <?php echo esc_html($lista->nombre); ?>
                            <?php if (!empty($lista->descripcion)): ?>
                                <span class="em-lista-desc"><?php echo esc_html($lista->descripcion); ?></span>
                            <?php endif; ?>
                        </label>
                    <?php endforeach; ?>
                <?php endif; ?>

                <?php if (!empty($listas_disponibles)): ?>
                    <h4><?php esc_html_e('Otras listas disponibles', 'flavor-chat-ia'); ?></h4>
                    <?php foreach ($listas_disponibles as $lista): ?>
                        <label class="em-checkbox">
                            <input type="checkbox" name="listas_nuevas[]" value="<?php echo esc_attr($lista->id); ?>">
                            <?php echo esc_html($lista->nombre); ?>
                            <?php if (!empty($lista->descripcion)): ?>
                                <span class="em-lista-desc"><?php echo esc_html($lista->descripcion); ?></span>
                            <?php endif; ?>
                        </label>
                    <?php endforeach; ?>
                <?php endif; ?>

                <button type="submit" class="em-boton"><?php esc_html_e('Guardar preferencias', 'flavor-chat-ia'); ?></button>

                <div class="em-mensaje" style="display:none;"></div>
            </form>

            <hr>

            <p>
                <a href="<?php echo esc_url($this->generar_url_baja($suscriptor->id)); ?>" class="em-link-baja">
                    <?php esc_html_e('Darme de baja de todo', 'flavor-chat-ia'); ?>
                </a>
            </p>
        </div>
        <?php
        return ob_get_clean();
    }

    /**
     * Shortcode de confirmación
     *
     * @param array $atts
     * @return string
     */
    public function shortcode_confirmar($atts) {
        $token = isset($_GET['token']) ? sanitize_text_field($_GET['token']) : '';

        if (empty($token)) {
            return '<p class="em-error">' . __('Token de confirmacion no valido.', 'flavor-chat-ia') . '</p>';
        }

        $resultado = $this->confirmar_suscripcion($token);

        if ($resultado['success']) {
            return '<div class="em-confirmacion-exitosa">
                <span class="em-icono">&#10003;</span>
                <h3>' . __('Suscripcion confirmada!', 'flavor-chat-ia') . '</h3>
                <p>' . __('Gracias por confirmar tu suscripcion. Pronto recibiras nuestras novedades.', 'flavor-chat-ia') . '</p>
            </div>';
        }

        return '<p class="em-error">' . esc_html($resultado['error']) . '</p>';
    }

    /**
     * Shortcode de darse de baja
     *
     * @param array $atts
     * @return string
     */
    public function shortcode_baja($atts) {
        $token = isset($_GET['token']) ? sanitize_text_field($_GET['token']) : '';

        if (empty($token)) {
            return '<p class="em-error">' . __('Token no valido.', 'flavor-chat-ia') . '</p>';
        }

        $suscriptor = $this->get_suscriptor_por_token($token);

        if (!$suscriptor) {
            return '<p class="em-error">' . __('Suscriptor no encontrado.', 'flavor-chat-ia') . '</p>';
        }

        $template_path = plugin_dir_path(__FILE__) . 'templates/darse-baja.php';

        if (file_exists($template_path)) {
            ob_start();
            include $template_path;
            return ob_get_clean();
        }

        return $this->render_baja_inline($suscriptor, $token);
    }

    /**
     * Render inline de baja
     *
     * @param object $suscriptor
     * @param string $token
     * @return string
     */
    private function render_baja_inline($suscriptor, $token) {
        ob_start();
        ?>
        <div class="flavor-em-baja">
            <h3><?php esc_html_e('Darse de baja', 'flavor-chat-ia'); ?></h3>

            <p><?php printf(esc_html__('Email: %s', 'flavor-chat-ia'), esc_html($suscriptor->email)); ?></p>

            <form class="em-baja-form" data-token="<?php echo esc_attr($token); ?>">
                <?php wp_nonce_field('flavor_em_public', 'em_nonce'); ?>

                <p><?php esc_html_e('Lamentamos verte partir. Por favor, indicanos el motivo:', 'flavor-chat-ia'); ?></p>

                <label class="em-radio">
                    <input type="radio" name="motivo" value="<?php echo esc_attr__('demasiados_emails', 'flavor-chat-ia'); ?>">
                    <?php esc_html_e('Recibo demasiados emails', 'flavor-chat-ia'); ?>
                </label>

                <label class="em-radio">
                    <input type="radio" name="motivo" value="<?php echo esc_attr__('no_relevante', 'flavor-chat-ia'); ?>">
                    <?php esc_html_e('El contenido no es relevante para mi', 'flavor-chat-ia'); ?>
                </label>

                <label class="em-radio">
                    <input type="radio" name="motivo" value="<?php echo esc_attr__('no_recuerdo', 'flavor-chat-ia'); ?>">
                    <?php esc_html_e('No recuerdo haberme suscrito', 'flavor-chat-ia'); ?>
                </label>

                <label class="em-radio">
                    <input type="radio" name="motivo" value="<?php echo esc_attr__('otro', 'flavor-chat-ia'); ?>">
                    <?php esc_html_e('Otro motivo', 'flavor-chat-ia'); ?>
                </label>

                <div class="em-campo em-motivo-otro" style="display:none;">
                    <textarea name="motivo_detalle" placeholder="<?php esc_attr_e('Cuentanos mas...', 'flavor-chat-ia'); ?>"></textarea>
                </div>

                <button type="submit" class="em-boton em-boton-danger"><?php esc_html_e('Darme de baja', 'flavor-chat-ia'); ?></button>

                <div class="em-mensaje" style="display:none;"></div>
            </form>
        </div>
        <?php
        return ob_get_clean();
    }

    /**
     * Shortcode alias: Suscripción newsletter
     *
     * @param array $atts
     * @return string
     */
    public function shortcode_suscripcion_newsletter($atts) {
        return $this->shortcode_formulario_suscripcion($atts);
    }

    /**
     * Shortcode alias: Preferencias email
     *
     * @param array $atts
     * @return string
     */
    public function shortcode_preferencias_email($atts) {
        return $this->shortcode_preferencias($atts);
    }

    /**
     * Shortcode: Archivo de newsletters
     *
     * @param array $atts
     * @return string
     */
    public function shortcode_archivo_newsletters($atts) {
        $atts = shortcode_atts([
            'limite' => 10,
            'orden' => 'DESC',
        ], $atts, 'flavor_archivo_newsletters');

        global $wpdb;

        $tabla = $wpdb->prefix . self::TABLE_PREFIX . 'campanias';

        $campanias = $wpdb->get_results($wpdb->prepare(
            "SELECT id, nombre, asunto, fecha_fin_envio, contenido_html
             FROM $tabla
             WHERE estado = 'enviada'
             ORDER BY fecha_fin_envio " . ($atts['orden'] === 'ASC' ? 'ASC' : 'DESC') . "
             LIMIT %d",
            absint($atts['limite'])
        ));

        if (empty($campanias)) {
            return '<p class="em-archivo-vacio">' . __('No hay newsletters anteriores.', 'flavor-chat-ia') . '</p>';
        }

        ob_start();
        ?>
        <div class="flavor-em-archivo">
            <ul class="em-archivo-lista">
                <?php foreach ($campanias as $campania): ?>
                    <li class="em-archivo-item">
                        <span class="em-archivo-fecha">
                            <?php echo esc_html(date_i18n(get_option('date_format'), strtotime($campania->fecha_fin_envio))); ?>
                        </span>
                        <a href="<?php echo esc_url(add_query_arg('em_preview', $campania->id, home_url())); ?>" target="_blank" class="em-archivo-link">
                            <?php echo esc_html($campania->asunto); ?>
                        </a>
                    </li>
                <?php endforeach; ?>
            </ul>
        </div>
        <?php
        return ob_get_clean();
    }

    /**
     * Shortcode: Contador de suscriptores
     *
     * @param array $atts
     * @return string
     */
    public function shortcode_contador_suscriptores($atts) {
        $atts = shortcode_atts([
            'lista' => '',
            'formato' => 'numero',
            'prefijo' => '',
            'sufijo' => '',
        ], $atts, 'flavor_contador_suscriptores');

        global $wpdb;

        $tabla_suscriptores = $wpdb->prefix . self::TABLE_PREFIX . 'suscriptores';

        if (!empty($atts['lista'])) {
            $tabla_relacion = $wpdb->prefix . self::TABLE_PREFIX . 'suscriptor_lista';
            $tabla_listas = $wpdb->prefix . self::TABLE_PREFIX . 'listas';

            $total = $wpdb->get_var($wpdb->prepare(
                "SELECT COUNT(DISTINCT s.id)
                 FROM $tabla_suscriptores s
                 INNER JOIN $tabla_relacion sl ON s.id = sl.suscriptor_id
                 INNER JOIN $tabla_listas l ON sl.lista_id = l.id
                 WHERE l.slug = %s AND s.estado = 'activo' AND sl.estado = 'activo'",
                $atts['lista']
            ));
        } else {
            $total = $wpdb->get_var(
                "SELECT COUNT(*) FROM $tabla_suscriptores WHERE estado = 'activo'"
            );
        }

        $total = (int) $total;

        switch ($atts['formato']) {
            case 'miles':
                $numero_formateado = number_format($total, 0, ',', '.');
                break;
            case 'aproximado':
                if ($total >= 1000) {
                    $numero_formateado = round($total / 1000, 1) . 'k';
                } else {
                    $numero_formateado = $total;
                }
                break;
            default:
                $numero_formateado = $total;
        }

        return sprintf(
            '<span class="flavor-em-contador">%s%s%s</span>',
            esc_html($atts['prefijo']),
            esc_html($numero_formateado),
            esc_html($atts['sufijo'])
        );
    }

    /**
     * Shortcode: Formulario popup
     *
     * @param array $atts
     * @return string
     */
    public function shortcode_formulario_popup($atts) {
        $atts = shortcode_atts([
            'lista' => 'newsletter-principal',
            'titulo' => __('Suscribete!', 'flavor-chat-ia'),
            'descripcion' => __('No te pierdas nuestras novedades.', 'flavor-chat-ia'),
            'boton' => __('Suscribirme', 'flavor-chat-ia'),
            'trigger' => 'exit', // exit, scroll, time
            'delay' => 5,
            'scroll_percent' => 50,
            'mostrar_una_vez' => 'true',
            'cookie_dias' => 30,
        ], $atts, 'flavor_formulario_popup');

        $unique_id = 'em-popup-' . wp_rand(1000, 9999);

        ob_start();
        ?>
        <div class="flavor-em-popup" id="<?php echo esc_attr($unique_id); ?>" style="display:none;"
             data-trigger="<?php echo esc_attr($atts['trigger']); ?>"
             data-delay="<?php echo esc_attr($atts['delay']); ?>"
             data-scroll="<?php echo esc_attr($atts['scroll_percent']); ?>"
             data-once="<?php echo esc_attr($atts['mostrar_una_vez']); ?>"
             data-cookie-dias="<?php echo esc_attr($atts['cookie_dias']); ?>">

            <div class="em-popup-overlay"></div>

            <div class="em-popup-contenido">
                <button type="button" class="em-popup-cerrar" aria-label="<?php esc_attr_e('Cerrar', 'flavor-chat-ia'); ?>"><?php echo esc_html__('&times;', 'flavor-chat-ia'); ?></button>

                <?php if (!empty($atts['titulo'])): ?>
                    <h3 class="em-popup-titulo"><?php echo esc_html($atts['titulo']); ?></h3>
                <?php endif; ?>

                <?php if (!empty($atts['descripcion'])): ?>
                    <p class="em-popup-descripcion"><?php echo esc_html($atts['descripcion']); ?></p>
                <?php endif; ?>

                <form class="em-form em-popup-form" data-lista="<?php echo esc_attr($atts['lista']); ?>">
                    <?php wp_nonce_field('flavor_em_public', 'em_nonce'); ?>

                    <div class="em-campo">
                        <input type="email" name="email" placeholder="<?php esc_attr_e('Tu email', 'flavor-chat-ia'); ?>" required class="em-input">
                    </div>

                    <div class="em-campo">
                        <button type="submit" class="em-boton"><?php echo esc_html($atts['boton']); ?></button>
                    </div>

                    <div class="em-mensaje" style="display:none;"></div>
                </form>
            </div>
        </div>
        <?php
        return ob_get_clean();
    }

    /**
     * Shortcode: Preview de email
     *
     * @param array $atts
     * @return string
     */
    public function shortcode_email_preview($atts) {
        if (!isset($_GET['em_preview'])) {
            return '';
        }

        $campania_id = absint($_GET['em_preview']);

        global $wpdb;

        $tabla = $wpdb->prefix . self::TABLE_PREFIX . 'campanias';

        $campania = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM $tabla WHERE id = %d AND estado = 'enviada'",
            $campania_id
        ));

        if (!$campania) {
            return '<p class="em-error">' . __('Newsletter no encontrado.', 'flavor-chat-ia') . '</p>';
        }

        // Limpiar variables de personalización para la vista pública
        $contenido = $campania->contenido_html;
        $contenido = str_replace(['{{nombre}}', '{{email}}'], ['Suscriptor', 'email@ejemplo.com'], $contenido);
        $contenido = str_replace(['{{url_baja}}', '{{url_preferencias}}'], ['#', '#'], $contenido);

        return '<div class="flavor-em-preview">' . $contenido . '</div>';
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
    // AJAX HANDLERS PÚBLICOS
    // =========================================================================

    /**
     * AJAX: Suscribirse
     */
    public function ajax_suscribirse() {
        check_ajax_referer('flavor_em_public', 'nonce');

        // Verificar honeypot
        $settings = $this->get_settings();
        if (!empty($settings['honeypot_enabled']) && !empty($_POST['em_website'])) {
            wp_send_json(['success' => false, 'error' => __('Error de validacion', 'flavor-chat-ia')]);
        }

        // Verificar rate limit
        if (!$this->verificar_rate_limit()) {
            wp_send_json(['success' => false, 'error' => __('Demasiadas solicitudes. Intenta mas tarde.', 'flavor-chat-ia')]);
        }

        $email = isset($_POST['email']) ? sanitize_email($_POST['email']) : '';
        $lista = isset($_POST['lista']) ? sanitize_text_field($_POST['lista']) : 'newsletter-principal';
        $nombre = isset($_POST['nombre']) ? sanitize_text_field($_POST['nombre']) : '';

        $resultado = $this->suscribir($email, $lista, ['nombre' => $nombre, 'origen' => 'formulario']);

        // Notificar si está habilitado
        if ($resultado['success'] && !empty($settings['notificar_nuevos_suscriptores'])) {
            $lista_obj = $this->get_lista_por_slug($lista);
            $this->enviar_notificacion_admin('nuevo_suscriptor', [
                'email' => $email,
                'nombre' => $nombre,
                'lista' => $lista_obj ? $lista_obj->nombre : $lista,
            ]);
        }

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
        $motivo_detalle = isset($_POST['motivo_detalle']) ? sanitize_textarea_field($_POST['motivo_detalle']) : '';

        $suscriptor = $this->get_suscriptor_por_token($token);

        if (!$suscriptor) {
            wp_send_json(['success' => false, 'error' => __('Token no valido', 'flavor-chat-ia')]);
        }

        $motivo_completo = $motivo;
        if (!empty($motivo_detalle)) {
            $motivo_completo .= ': ' . $motivo_detalle;
        }

        $resultado = $this->dar_de_baja($suscriptor->id, null, $motivo_completo);

        // Notificar si está habilitado
        $settings = $this->get_settings();
        if ($resultado['success'] && !empty($settings['notificar_bajas'])) {
            $this->enviar_notificacion_admin('baja', [
                'email' => $suscriptor->email,
                'motivo' => $motivo_completo,
            ]);
        }

        wp_send_json($resultado);
    }

    /**
     * AJAX: Actualizar preferencias
     */
    public function ajax_actualizar_preferencias() {
        check_ajax_referer('flavor_em_public', 'nonce');

        $token = isset($_POST['token']) ? sanitize_text_field($_POST['token']) : '';
        $listas = isset($_POST['listas']) ? array_map('absint', (array) $_POST['listas']) : [];
        $listas_nuevas = isset($_POST['listas_nuevas']) ? array_map('absint', (array) $_POST['listas_nuevas']) : [];

        $suscriptor = $this->get_suscriptor_por_token($token);

        if (!$suscriptor) {
            wp_send_json(['success' => false, 'error' => __('Token no valido', 'flavor-chat-ia')]);
        }

        global $wpdb;

        $tabla_relacion = $wpdb->prefix . self::TABLE_PREFIX . 'suscriptor_lista';

        // Obtener listas actuales del suscriptor
        $listas_actuales = $wpdb->get_col($wpdb->prepare(
            "SELECT lista_id FROM $tabla_relacion WHERE suscriptor_id = %d AND estado = 'activo'",
            $suscriptor->id
        ));

        // Dar de baja de listas no seleccionadas
        foreach ($listas_actuales as $lista_id) {
            if (!in_array($lista_id, $listas)) {
                $this->dar_de_baja($suscriptor->id, $lista_id);
            }
        }

        // Reactivar listas seleccionadas que estaban de baja
        foreach ($listas as $lista_id) {
            $estado_actual = $wpdb->get_var($wpdb->prepare(
                "SELECT estado FROM $tabla_relacion WHERE suscriptor_id = %d AND lista_id = %d",
                $suscriptor->id,
                $lista_id
            ));

            if ($estado_actual === 'baja') {
                $wpdb->update(
                    $tabla_relacion,
                    ['estado' => 'activo', 'fecha_baja' => null],
                    ['suscriptor_id' => $suscriptor->id, 'lista_id' => $lista_id]
                );
                $this->actualizar_contador_lista($lista_id);
            }
        }

        // Suscribir a nuevas listas
        foreach ($listas_nuevas as $lista_id) {
            $this->suscribir_a_lista($suscriptor->id, $lista_id);
        }

        wp_send_json([
            'success' => true,
            'mensaje' => __('Preferencias actualizadas correctamente', 'flavor-chat-ia'),
        ]);
    }

    // =========================================================================
    // AJAX HANDLERS ADMIN - CAMPAÑAS
    // =========================================================================

    /**
     * AJAX Admin: Gestión de campaña
     */
    public function ajax_admin_campania() {
        check_ajax_referer('flavor_em_admin', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_send_json_error(__('Permisos insuficientes', 'flavor-chat-ia'));
        }

        $accion = isset($_POST['accion']) ? sanitize_key($_POST['accion']) : '';
        $campania_id = isset($_POST['campania_id']) ? absint($_POST['campania_id']) : 0;

        switch ($accion) {
            case 'obtener':
                $this->ajax_obtener_campania($campania_id);
                break;
            case 'listar':
                $this->ajax_listar_campanias();
                break;
            default:
                wp_send_json_error(__('Accion no valida', 'flavor-chat-ia'));
        }
    }

    /**
     * AJAX: Obtener campaña
     *
     * @param int $campania_id
     */
    private function ajax_obtener_campania($campania_id) {
        global $wpdb;

        $tabla = $wpdb->prefix . self::TABLE_PREFIX . 'campanias';

        $campania = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM $tabla WHERE id = %d",
            $campania_id
        ));

        if (!$campania) {
            wp_send_json_error(__('Campana no encontrada', 'flavor-chat-ia'));
        }

        $campania->listas_ids = json_decode($campania->listas_ids) ?: [];

        wp_send_json_success($campania);
    }

    /**
     * AJAX: Listar campañas
     */
    private function ajax_listar_campanias() {
        global $wpdb;

        $tabla = $wpdb->prefix . self::TABLE_PREFIX . 'campanias';
        $pagina = isset($_POST['pagina']) ? absint($_POST['pagina']) : 1;
        $por_pagina = isset($_POST['por_pagina']) ? absint($_POST['por_pagina']) : 20;
        $estado = isset($_POST['estado']) ? sanitize_key($_POST['estado']) : '';

        $offset = ($pagina - 1) * $por_pagina;

        $where = '';
        if (!empty($estado) && in_array($estado, self::ESTADOS_CAMPANIA)) {
            $where = $wpdb->prepare("WHERE estado = %s", $estado);
        }

        $total = $wpdb->get_var("SELECT COUNT(*) FROM $tabla $where");

        $campanias = $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM $tabla $where ORDER BY fecha_creacion DESC LIMIT %d OFFSET %d",
            $por_pagina,
            $offset
        ));

        wp_send_json_success([
            'items' => $campanias,
            'total' => (int) $total,
            'pagina' => $pagina,
            'total_paginas' => ceil($total / $por_pagina),
        ]);
    }

    /**
     * AJAX: Guardar campaña
     */
    public function ajax_guardar_campania() {
        check_ajax_referer('flavor_em_admin', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_send_json_error(__('Permisos insuficientes', 'flavor-chat-ia'));
        }

        $campania_id = isset($_POST['campania_id']) ? absint($_POST['campania_id']) : 0;

        $datos = [
            'nombre' => sanitize_text_field($_POST['nombre'] ?? ''),
            'asunto' => sanitize_text_field($_POST['asunto'] ?? ''),
            'asunto_alternativo' => sanitize_text_field($_POST['asunto_alternativo'] ?? ''),
            'preview_text' => sanitize_text_field($_POST['preview_text'] ?? ''),
            'contenido_html' => wp_kses_post($_POST['contenido_html'] ?? ''),
            'contenido_texto' => sanitize_textarea_field($_POST['contenido_texto'] ?? ''),
            'listas_ids' => isset($_POST['listas_ids']) ? (array) $_POST['listas_ids'] : [],
            'plantilla_id' => isset($_POST['plantilla_id']) ? absint($_POST['plantilla_id']) : null,
            'remitente_nombre' => sanitize_text_field($_POST['remitente_nombre'] ?? ''),
            'remitente_email' => sanitize_email($_POST['remitente_email'] ?? ''),
            'responder_a' => sanitize_email($_POST['responder_a'] ?? ''),
        ];

        if (empty($datos['nombre']) || empty($datos['asunto'])) {
            wp_send_json_error(__('Nombre y asunto son requeridos', 'flavor-chat-ia'));
        }

        global $wpdb;
        $tabla = $wpdb->prefix . self::TABLE_PREFIX . 'campanias';

        if ($campania_id > 0) {
            // Verificar que existe y se puede editar
            $campania_existente = $wpdb->get_row($wpdb->prepare(
                "SELECT estado FROM $tabla WHERE id = %d",
                $campania_id
            ));

            if (!$campania_existente) {
                wp_send_json_error(__('Campana no encontrada', 'flavor-chat-ia'));
            }

            if (!in_array($campania_existente->estado, ['borrador', 'programada'])) {
                wp_send_json_error(__('No se puede editar una campana en este estado', 'flavor-chat-ia'));
            }

            $wpdb->update($tabla, [
                'nombre' => $datos['nombre'],
                'asunto' => $datos['asunto'],
                'asunto_alternativo' => $datos['asunto_alternativo'] ?: null,
                'preview_text' => $datos['preview_text'],
                'contenido_html' => $datos['contenido_html'],
                'contenido_texto' => $datos['contenido_texto'],
                'listas_ids' => wp_json_encode($datos['listas_ids']),
                'plantilla_id' => $datos['plantilla_id'],
                'remitente_nombre' => $datos['remitente_nombre'],
                'remitente_email' => $datos['remitente_email'],
                'responder_a' => $datos['responder_a'],
            ], ['id' => $campania_id]);

            wp_send_json_success([
                'mensaje' => __('Campana actualizada', 'flavor-chat-ia'),
                'campania_id' => $campania_id,
            ]);
        } else {
            $nuevo_id = $this->crear_campania($datos);

            wp_send_json_success([
                'mensaje' => __('Campana creada', 'flavor-chat-ia'),
                'campania_id' => $nuevo_id,
            ]);
        }
    }

    /**
     * AJAX: Eliminar campaña
     */
    public function ajax_eliminar_campania() {
        check_ajax_referer('flavor_em_admin', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_send_json_error(__('Permisos insuficientes', 'flavor-chat-ia'));
        }

        $campania_id = isset($_POST['campania_id']) ? absint($_POST['campania_id']) : 0;

        global $wpdb;
        $tabla = $wpdb->prefix . self::TABLE_PREFIX . 'campanias';

        $campania = $wpdb->get_row($wpdb->prepare(
            "SELECT estado FROM $tabla WHERE id = %d",
            $campania_id
        ));

        if (!$campania) {
            wp_send_json_error(__('Campana no encontrada', 'flavor-chat-ia'));
        }

        if ($campania->estado === 'enviando') {
            wp_send_json_error(__('No se puede eliminar una campana en envio', 'flavor-chat-ia'));
        }

        $wpdb->delete($tabla, ['id' => $campania_id]);

        // También eliminar de la cola
        $wpdb->delete($wpdb->prefix . self::TABLE_PREFIX . 'cola', ['campania_id' => $campania_id]);

        wp_send_json_success(__('Campana eliminada', 'flavor-chat-ia'));
    }

    /**
     * AJAX: Duplicar campaña
     */
    public function ajax_duplicar_campania() {
        check_ajax_referer('flavor_em_admin', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_send_json_error(__('Permisos insuficientes', 'flavor-chat-ia'));
        }

        $campania_id = isset($_POST['campania_id']) ? absint($_POST['campania_id']) : 0;

        global $wpdb;
        $tabla = $wpdb->prefix . self::TABLE_PREFIX . 'campanias';

        $campania = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM $tabla WHERE id = %d",
            $campania_id
        ));

        if (!$campania) {
            wp_send_json_error(__('Campana no encontrada', 'flavor-chat-ia'));
        }

        $nueva_campania = [
            'nombre' => $campania->nombre . ' (copia)',
            'asunto' => $campania->asunto,
            'asunto_alternativo' => $campania->asunto_alternativo,
            'preview_text' => $campania->preview_text,
            'contenido_html' => $campania->contenido_html,
            'contenido_texto' => $campania->contenido_texto,
            'plantilla_id' => $campania->plantilla_id,
            'tipo' => $campania->tipo,
            'estado' => 'borrador',
            'listas_ids' => $campania->listas_ids,
            'remitente_nombre' => $campania->remitente_nombre,
            'remitente_email' => $campania->remitente_email,
            'responder_a' => $campania->responder_a,
            'creado_por' => get_current_user_id(),
        ];

        $wpdb->insert($tabla, $nueva_campania);
        $nueva_id = $wpdb->insert_id;

        wp_send_json_success([
            'mensaje' => __('Campana duplicada', 'flavor-chat-ia'),
            'campania_id' => $nueva_id,
        ]);
    }

    /**
     * AJAX: Programar campaña
     */
    public function ajax_programar_campania() {
        check_ajax_referer('flavor_em_admin', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_send_json_error(__('Permisos insuficientes', 'flavor-chat-ia'));
        }

        $campania_id = isset($_POST['campania_id']) ? absint($_POST['campania_id']) : 0;
        $fecha_programada = isset($_POST['fecha_programada']) ? sanitize_text_field($_POST['fecha_programada']) : null;

        $resultado = $this->programar_campania($campania_id, $fecha_programada);

        if ($resultado['success']) {
            wp_send_json_success($resultado);
        } else {
            wp_send_json_error($resultado['error']);
        }
    }

    /**
     * AJAX: Pausar campaña
     */
    public function ajax_pausar_campania() {
        check_ajax_referer('flavor_em_admin', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_send_json_error(__('Permisos insuficientes', 'flavor-chat-ia'));
        }

        $campania_id = isset($_POST['campania_id']) ? absint($_POST['campania_id']) : 0;

        global $wpdb;
        $tabla = $wpdb->prefix . self::TABLE_PREFIX . 'campanias';

        $campania = $wpdb->get_row($wpdb->prepare(
            "SELECT estado FROM $tabla WHERE id = %d",
            $campania_id
        ));

        if (!$campania || $campania->estado !== 'enviando') {
            wp_send_json_error(__('Solo se pueden pausar campanas en envio', 'flavor-chat-ia'));
        }

        $wpdb->update($tabla, ['estado' => 'pausada'], ['id' => $campania_id]);

        wp_send_json_success(__('Campana pausada', 'flavor-chat-ia'));
    }

    /**
     * AJAX: Reanudar campaña
     */
    public function ajax_reanudar_campania() {
        check_ajax_referer('flavor_em_admin', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_send_json_error(__('Permisos insuficientes', 'flavor-chat-ia'));
        }

        $campania_id = isset($_POST['campania_id']) ? absint($_POST['campania_id']) : 0;

        global $wpdb;
        $tabla = $wpdb->prefix . self::TABLE_PREFIX . 'campanias';

        $campania = $wpdb->get_row($wpdb->prepare(
            "SELECT estado FROM $tabla WHERE id = %d",
            $campania_id
        ));

        if (!$campania || $campania->estado !== 'pausada') {
            wp_send_json_error(__('Solo se pueden reanudar campanas pausadas', 'flavor-chat-ia'));
        }

        $wpdb->update($tabla, ['estado' => 'enviando'], ['id' => $campania_id]);

        wp_send_json_success(__('Campana reanudada', 'flavor-chat-ia'));
    }

    /**
     * AJAX: Cancelar campaña
     */
    public function ajax_cancelar_campania() {
        check_ajax_referer('flavor_em_admin', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_send_json_error(__('Permisos insuficientes', 'flavor-chat-ia'));
        }

        $campania_id = isset($_POST['campania_id']) ? absint($_POST['campania_id']) : 0;

        global $wpdb;
        $tabla = $wpdb->prefix . self::TABLE_PREFIX . 'campanias';
        $tabla_cola = $wpdb->prefix . self::TABLE_PREFIX . 'cola';

        $campania = $wpdb->get_row($wpdb->prepare(
            "SELECT estado FROM $tabla WHERE id = %d",
            $campania_id
        ));

        if (!$campania || !in_array($campania->estado, ['enviando', 'pausada', 'programada'])) {
            wp_send_json_error(__('No se puede cancelar esta campana', 'flavor-chat-ia'));
        }

        // Cancelar campaña
        $wpdb->update($tabla, ['estado' => 'cancelada'], ['id' => $campania_id]);

        // Eliminar emails pendientes de la cola
        $wpdb->delete($tabla_cola, ['campania_id' => $campania_id, 'estado' => 'pendiente']);

        wp_send_json_success(__('Campana cancelada', 'flavor-chat-ia'));
    }

    /**
     * AJAX: Enviar email de test
     */
    public function ajax_enviar_test() {
        check_ajax_referer('flavor_em_admin', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_send_json_error(__('Permisos insuficientes', 'flavor-chat-ia'));
        }

        $campania_id = isset($_POST['campania_id']) ? absint($_POST['campania_id']) : 0;
        $email_test = isset($_POST['email_test']) ? sanitize_email($_POST['email_test']) : '';

        if (!is_email($email_test)) {
            wp_send_json_error(__('Email de prueba no valido', 'flavor-chat-ia'));
        }

        global $wpdb;

        $campania = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$wpdb->prefix}" . self::TABLE_PREFIX . "campanias WHERE id = %d",
            $campania_id
        ));

        if (!$campania) {
            wp_send_json_error(__('Campana no encontrada', 'flavor-chat-ia'));
        }

        // Personalizar contenido con datos de prueba
        $contenido = $this->personalizar_contenido($campania->contenido_html, [
            'id' => 0,
            'nombre' => 'Usuario de Prueba',
            'email' => $email_test,
        ]);

        $asunto = '[TEST] ' . $this->personalizar_contenido($campania->asunto, [
            'nombre' => 'Usuario de Prueba',
            'email' => $email_test,
        ]);

        $settings = $this->get_settings();

        $headers = [
            'Content-Type: text/html; charset=UTF-8',
            'From: ' . $settings['remitente_nombre'] . ' <' . $settings['remitente_email'] . '>',
        ];

        $enviado = wp_mail($email_test, $asunto, $contenido, $headers);

        if ($enviado) {
            wp_send_json_success(__('Email de prueba enviado', 'flavor-chat-ia'));
        } else {
            wp_send_json_error(__('Error al enviar el email de prueba', 'flavor-chat-ia'));
        }
    }

    /**
     * AJAX: Preview de campaña
     */
    public function ajax_preview_campania() {
        check_ajax_referer('flavor_em_admin', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_send_json_error(__('Permisos insuficientes', 'flavor-chat-ia'));
        }

        $contenido_html = isset($_POST['contenido_html']) ? wp_kses_post($_POST['contenido_html']) : '';

        // Personalizar con datos de ejemplo
        $contenido_preview = $this->personalizar_contenido($contenido_html, [
            'id' => 0,
            'nombre' => 'Usuario Ejemplo',
            'email' => 'ejemplo@email.com',
        ]);

        wp_send_json_success([
            'html' => $contenido_preview,
        ]);
    }

    // =========================================================================
    // AJAX HANDLERS ADMIN - LISTAS
    // =========================================================================

    /**
     * AJAX Admin: Gestión de lista
     */
    public function ajax_admin_lista() {
        check_ajax_referer('flavor_em_admin', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_send_json_error(__('Permisos insuficientes', 'flavor-chat-ia'));
        }

        $accion = isset($_POST['accion']) ? sanitize_key($_POST['accion']) : '';

        switch ($accion) {
            case 'listar':
                $this->ajax_listar_listas();
                break;
            case 'obtener':
                $this->ajax_obtener_lista(absint($_POST['lista_id'] ?? 0));
                break;
            default:
                wp_send_json_error(__('Accion no valida', 'flavor-chat-ia'));
        }
    }

    /**
     * AJAX: Listar listas
     */
    private function ajax_listar_listas() {
        global $wpdb;

        $tabla = $wpdb->prefix . self::TABLE_PREFIX . 'listas';
        $listas = $wpdb->get_results("SELECT * FROM $tabla ORDER BY nombre ASC");

        wp_send_json_success($listas);
    }

    /**
     * AJAX: Obtener lista
     *
     * @param int $lista_id
     */
    private function ajax_obtener_lista($lista_id) {
        global $wpdb;

        $tabla = $wpdb->prefix . self::TABLE_PREFIX . 'listas';

        $lista = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM $tabla WHERE id = %d",
            $lista_id
        ));

        if (!$lista) {
            wp_send_json_error(__('Lista no encontrada', 'flavor-chat-ia'));
        }

        wp_send_json_success($lista);
    }

    /**
     * AJAX: Crear lista
     */
    public function ajax_crear_lista() {
        check_ajax_referer('flavor_em_admin', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_send_json_error(__('Permisos insuficientes', 'flavor-chat-ia'));
        }

        $nombre = sanitize_text_field($_POST['nombre'] ?? '');
        $slug = sanitize_title($_POST['slug'] ?? $nombre);

        if (empty($nombre)) {
            wp_send_json_error(__('El nombre es requerido', 'flavor-chat-ia'));
        }

        global $wpdb;
        $tabla = $wpdb->prefix . self::TABLE_PREFIX . 'listas';

        // Verificar slug único
        $slug_existe = $wpdb->get_var($wpdb->prepare(
            "SELECT id FROM $tabla WHERE slug = %s",
            $slug
        ));

        if ($slug_existe) {
            $slug = $slug . '-' . time();
        }

        $wpdb->insert($tabla, [
            'nombre' => $nombre,
            'slug' => $slug,
            'descripcion' => sanitize_textarea_field($_POST['descripcion'] ?? ''),
            'activa' => isset($_POST['activa']) ? 1 : 0,
            'publica' => isset($_POST['publica']) ? 1 : 0,
            'doble_optin' => isset($_POST['doble_optin']) ? 1 : 0,
            'mensaje_bienvenida' => wp_kses_post($_POST['mensaje_bienvenida'] ?? ''),
        ]);

        wp_send_json_success([
            'mensaje' => __('Lista creada', 'flavor-chat-ia'),
            'lista_id' => $wpdb->insert_id,
        ]);
    }

    /**
     * AJAX: Actualizar lista
     */
    public function ajax_actualizar_lista() {
        check_ajax_referer('flavor_em_admin', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_send_json_error(__('Permisos insuficientes', 'flavor-chat-ia'));
        }

        $lista_id = isset($_POST['lista_id']) ? absint($_POST['lista_id']) : 0;

        global $wpdb;
        $tabla = $wpdb->prefix . self::TABLE_PREFIX . 'listas';

        $lista = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM $tabla WHERE id = %d",
            $lista_id
        ));

        if (!$lista) {
            wp_send_json_error(__('Lista no encontrada', 'flavor-chat-ia'));
        }

        $wpdb->update($tabla, [
            'nombre' => sanitize_text_field($_POST['nombre'] ?? $lista->nombre),
            'descripcion' => sanitize_textarea_field($_POST['descripcion'] ?? ''),
            'activa' => isset($_POST['activa']) ? 1 : 0,
            'publica' => isset($_POST['publica']) ? 1 : 0,
            'doble_optin' => isset($_POST['doble_optin']) ? 1 : 0,
            'mensaje_bienvenida' => wp_kses_post($_POST['mensaje_bienvenida'] ?? ''),
        ], ['id' => $lista_id]);

        wp_send_json_success(__('Lista actualizada', 'flavor-chat-ia'));
    }

    /**
     * AJAX: Eliminar lista
     */
    public function ajax_eliminar_lista() {
        check_ajax_referer('flavor_em_admin', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_send_json_error(__('Permisos insuficientes', 'flavor-chat-ia'));
        }

        $lista_id = isset($_POST['lista_id']) ? absint($_POST['lista_id']) : 0;

        global $wpdb;
        $tabla = $wpdb->prefix . self::TABLE_PREFIX . 'listas';
        $tabla_relacion = $wpdb->prefix . self::TABLE_PREFIX . 'suscriptor_lista';

        // Eliminar relaciones
        $wpdb->delete($tabla_relacion, ['lista_id' => $lista_id]);

        // Eliminar lista
        $wpdb->delete($tabla, ['id' => $lista_id]);

        wp_send_json_success(__('Lista eliminada', 'flavor-chat-ia'));
    }

    /**
     * AJAX: Fusionar listas
     */
    public function ajax_fusionar_listas() {
        check_ajax_referer('flavor_em_admin', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_send_json_error(__('Permisos insuficientes', 'flavor-chat-ia'));
        }

        $lista_origen = isset($_POST['lista_origen']) ? absint($_POST['lista_origen']) : 0;
        $lista_destino = isset($_POST['lista_destino']) ? absint($_POST['lista_destino']) : 0;
        $eliminar_origen = isset($_POST['eliminar_origen']) && $_POST['eliminar_origen'] === 'true';

        if ($lista_origen === $lista_destino || !$lista_origen || !$lista_destino) {
            wp_send_json_error(__('Selecciona dos listas diferentes', 'flavor-chat-ia'));
        }

        global $wpdb;
        $tabla_relacion = $wpdb->prefix . self::TABLE_PREFIX . 'suscriptor_lista';

        // Obtener suscriptores de la lista origen
        $suscriptores_origen = $wpdb->get_col($wpdb->prepare(
            "SELECT suscriptor_id FROM $tabla_relacion WHERE lista_id = %d AND estado = 'activo'",
            $lista_origen
        ));

        $fusionados = 0;

        foreach ($suscriptores_origen as $suscriptor_id) {
            // Verificar si ya está en destino
            $existe = $wpdb->get_var($wpdb->prepare(
                "SELECT id FROM $tabla_relacion WHERE suscriptor_id = %d AND lista_id = %d",
                $suscriptor_id,
                $lista_destino
            ));

            if (!$existe) {
                $wpdb->insert($tabla_relacion, [
                    'suscriptor_id' => $suscriptor_id,
                    'lista_id' => $lista_destino,
                    'estado' => 'activo',
                ]);
                $fusionados++;
            }
        }

        $this->actualizar_contador_lista($lista_destino);

        if ($eliminar_origen) {
            $wpdb->delete($tabla_relacion, ['lista_id' => $lista_origen]);
            $wpdb->delete($wpdb->prefix . self::TABLE_PREFIX . 'listas', ['id' => $lista_origen]);
        }

        wp_send_json_success([
            'mensaje' => sprintf(__('%d suscriptores fusionados', 'flavor-chat-ia'), $fusionados),
        ]);
    }

    // =========================================================================
    // AJAX HANDLERS ADMIN - SUSCRIPTORES
    // =========================================================================

    /**
     * AJAX Admin: Gestión de suscriptor
     */
    public function ajax_admin_suscriptor() {
        check_ajax_referer('flavor_em_admin', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_send_json_error(__('Permisos insuficientes', 'flavor-chat-ia'));
        }

        $accion = isset($_POST['accion']) ? sanitize_key($_POST['accion']) : '';

        switch ($accion) {
            case 'listar':
                $this->ajax_listar_suscriptores();
                break;
            case 'obtener':
                $this->ajax_obtener_suscriptor(absint($_POST['suscriptor_id'] ?? 0));
                break;
            default:
                wp_send_json_error(__('Accion no valida', 'flavor-chat-ia'));
        }
    }

    /**
     * AJAX: Listar suscriptores
     */
    private function ajax_listar_suscriptores() {
        global $wpdb;

        $tabla = $wpdb->prefix . self::TABLE_PREFIX . 'suscriptores';
        $pagina = isset($_POST['pagina']) ? absint($_POST['pagina']) : 1;
        $por_pagina = isset($_POST['por_pagina']) ? absint($_POST['por_pagina']) : 20;
        $estado = isset($_POST['estado']) ? sanitize_key($_POST['estado']) : '';
        $lista_id = isset($_POST['lista_id']) ? absint($_POST['lista_id']) : 0;
        $busqueda = isset($_POST['busqueda']) ? sanitize_text_field($_POST['busqueda']) : '';

        $offset = ($pagina - 1) * $por_pagina;

        $where_clauses = ['1=1'];
        $params = [];

        if (!empty($estado) && in_array($estado, self::ESTADOS_SUSCRIPTOR)) {
            $where_clauses[] = 's.estado = %s';
            $params[] = $estado;
        }

        if ($lista_id > 0) {
            $tabla_relacion = $wpdb->prefix . self::TABLE_PREFIX . 'suscriptor_lista';
            $where_clauses[] = "EXISTS (SELECT 1 FROM $tabla_relacion sl WHERE sl.suscriptor_id = s.id AND sl.lista_id = %d)";
            $params[] = $lista_id;
        }

        if (!empty($busqueda)) {
            $where_clauses[] = '(s.email LIKE %s OR s.nombre LIKE %s)';
            $params[] = '%' . $wpdb->esc_like($busqueda) . '%';
            $params[] = '%' . $wpdb->esc_like($busqueda) . '%';
        }

        $where_sql = implode(' AND ', $where_clauses);

        $total = $wpdb->get_var(
            empty($params)
                ? "SELECT COUNT(*) FROM $tabla s WHERE $where_sql"
                : $wpdb->prepare("SELECT COUNT(*) FROM $tabla s WHERE $where_sql", ...$params)
        );

        $params[] = $por_pagina;
        $params[] = $offset;

        $suscriptores = $wpdb->get_results($wpdb->prepare(
            "SELECT s.* FROM $tabla s WHERE $where_sql ORDER BY s.fecha_registro DESC LIMIT %d OFFSET %d",
            ...$params
        ));

        wp_send_json_success([
            'items' => $suscriptores,
            'total' => (int) $total,
            'pagina' => $pagina,
            'total_paginas' => ceil($total / $por_pagina),
        ]);
    }

    /**
     * AJAX: Obtener suscriptor
     *
     * @param int $suscriptor_id
     */
    private function ajax_obtener_suscriptor($suscriptor_id) {
        global $wpdb;

        $tabla = $wpdb->prefix . self::TABLE_PREFIX . 'suscriptores';

        $suscriptor = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM $tabla WHERE id = %d",
            $suscriptor_id
        ));

        if (!$suscriptor) {
            wp_send_json_error(__('Suscriptor no encontrado', 'flavor-chat-ia'));
        }

        // Obtener listas
        $tabla_relacion = $wpdb->prefix . self::TABLE_PREFIX . 'suscriptor_lista';
        $tabla_listas = $wpdb->prefix . self::TABLE_PREFIX . 'listas';

        $listas = $wpdb->get_results($wpdb->prepare(
            "SELECT l.*, sl.estado as estado_suscripcion, sl.fecha_suscripcion
             FROM $tabla_listas l
             INNER JOIN $tabla_relacion sl ON l.id = sl.lista_id
             WHERE sl.suscriptor_id = %d",
            $suscriptor_id
        ));

        $suscriptor->listas = $listas;
        $suscriptor->tags = json_decode($suscriptor->tags) ?: [];

        wp_send_json_success($suscriptor);
    }

    /**
     * AJAX: Crear suscriptor (admin)
     */
    public function ajax_crear_suscriptor() {
        check_ajax_referer('flavor_em_admin', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_send_json_error(__('Permisos insuficientes', 'flavor-chat-ia'));
        }

        $email = sanitize_email($_POST['email'] ?? '');
        $nombre = sanitize_text_field($_POST['nombre'] ?? '');
        $apellidos = sanitize_text_field($_POST['apellidos'] ?? '');
        $listas = isset($_POST['listas']) ? array_map('absint', (array) $_POST['listas']) : [];

        if (!is_email($email)) {
            wp_send_json_error(__('Email no valido', 'flavor-chat-ia'));
        }

        global $wpdb;
        $tabla = $wpdb->prefix . self::TABLE_PREFIX . 'suscriptores';

        // Verificar si ya existe
        $existe = $wpdb->get_var($wpdb->prepare(
            "SELECT id FROM $tabla WHERE email = %s",
            $email
        ));

        if ($existe) {
            wp_send_json_error(__('Este email ya esta registrado', 'flavor-chat-ia'));
        }

        $wpdb->insert($tabla, [
            'email' => $email,
            'nombre' => $nombre,
            'apellidos' => $apellidos,
            'estado' => 'activo',
            'origen' => 'admin',
            'fecha_confirmacion' => current_time('mysql'),
        ]);

        $suscriptor_id = $wpdb->insert_id;

        // Añadir a listas
        foreach ($listas as $lista_id) {
            $this->suscribir_a_lista($suscriptor_id, $lista_id);
        }

        wp_send_json_success([
            'mensaje' => __('Suscriptor creado', 'flavor-chat-ia'),
            'suscriptor_id' => $suscriptor_id,
        ]);
    }

    /**
     * AJAX: Actualizar suscriptor
     */
    public function ajax_actualizar_suscriptor() {
        check_ajax_referer('flavor_em_admin', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_send_json_error(__('Permisos insuficientes', 'flavor-chat-ia'));
        }

        $suscriptor_id = isset($_POST['suscriptor_id']) ? absint($_POST['suscriptor_id']) : 0;

        global $wpdb;
        $tabla = $wpdb->prefix . self::TABLE_PREFIX . 'suscriptores';

        $suscriptor = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM $tabla WHERE id = %d",
            $suscriptor_id
        ));

        if (!$suscriptor) {
            wp_send_json_error(__('Suscriptor no encontrado', 'flavor-chat-ia'));
        }

        $update_data = [];

        if (isset($_POST['nombre'])) {
            $update_data['nombre'] = sanitize_text_field($_POST['nombre']);
        }
        if (isset($_POST['apellidos'])) {
            $update_data['apellidos'] = sanitize_text_field($_POST['apellidos']);
        }
        if (isset($_POST['estado']) && in_array($_POST['estado'], self::ESTADOS_SUSCRIPTOR)) {
            $update_data['estado'] = $_POST['estado'];
        }
        if (isset($_POST['tags'])) {
            $update_data['tags'] = wp_json_encode((array) $_POST['tags']);
        }

        if (!empty($update_data)) {
            $wpdb->update($tabla, $update_data, ['id' => $suscriptor_id]);
        }

        wp_send_json_success(__('Suscriptor actualizado', 'flavor-chat-ia'));
    }

    /**
     * AJAX: Eliminar suscriptor
     */
    public function ajax_eliminar_suscriptor() {
        check_ajax_referer('flavor_em_admin', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_send_json_error(__('Permisos insuficientes', 'flavor-chat-ia'));
        }

        $suscriptor_id = isset($_POST['suscriptor_id']) ? absint($_POST['suscriptor_id']) : 0;

        global $wpdb;

        $wpdb->delete($wpdb->prefix . self::TABLE_PREFIX . 'suscriptor_lista', ['suscriptor_id' => $suscriptor_id]);
        $wpdb->delete($wpdb->prefix . self::TABLE_PREFIX . 'suscriptores', ['id' => $suscriptor_id]);

        wp_send_json_success(__('Suscriptor eliminado', 'flavor-chat-ia'));
    }

    /**
     * AJAX: Importar suscriptores
     */
    public function ajax_importar_suscriptores() {
        check_ajax_referer('flavor_em_admin', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_send_json_error(__('Permisos insuficientes', 'flavor-chat-ia'));
        }

        if (!isset($_FILES['archivo'])) {
            wp_send_json_error(__('No se ha subido ningun archivo', 'flavor-chat-ia'));
        }

        $archivo = $_FILES['archivo'];
        $lista_id = isset($_POST['lista_id']) ? absint($_POST['lista_id']) : 0;
        $sobrescribir = isset($_POST['sobrescribir']) && $_POST['sobrescribir'] === 'true';

        $extension = strtolower(pathinfo($archivo['name'], PATHINFO_EXTENSION));

        if (!in_array($extension, ['csv', 'txt'])) {
            wp_send_json_error(__('Formato de archivo no soportado. Usa CSV.', 'flavor-chat-ia'));
        }

        $contenido = file_get_contents($archivo['tmp_name']);
        $lineas = explode("\n", $contenido);

        $importados = 0;
        $actualizados = 0;
        $errores = 0;
        $mensajes_error = [];

        global $wpdb;
        $tabla = $wpdb->prefix . self::TABLE_PREFIX . 'suscriptores';

        foreach ($lineas as $numero_linea => $linea) {
            $linea = trim($linea);
            if (empty($linea) || $numero_linea === 0) {
                continue; // Saltar línea vacía o cabecera
            }

            $datos = str_getcsv($linea);
            $email = isset($datos[0]) ? sanitize_email(trim($datos[0])) : '';
            $nombre = isset($datos[1]) ? sanitize_text_field(trim($datos[1])) : '';

            if (!is_email($email)) {
                $errores++;
                $mensajes_error[] = sprintf(__('Linea %d: Email invalido', 'flavor-chat-ia'), $numero_linea + 1);
                continue;
            }

            $existe = $wpdb->get_var($wpdb->prepare(
                "SELECT id FROM $tabla WHERE email = %s",
                $email
            ));

            if ($existe) {
                if ($sobrescribir) {
                    $wpdb->update($tabla, ['nombre' => $nombre], ['id' => $existe]);
                    $actualizados++;

                    if ($lista_id > 0) {
                        $this->suscribir_a_lista($existe, $lista_id);
                    }
                }
                continue;
            }

            $wpdb->insert($tabla, [
                'email' => $email,
                'nombre' => $nombre,
                'estado' => 'activo',
                'origen' => 'importacion',
                'fecha_confirmacion' => current_time('mysql'),
            ]);

            $suscriptor_id = $wpdb->insert_id;
            $importados++;

            if ($lista_id > 0) {
                $this->suscribir_a_lista($suscriptor_id, $lista_id);
            }
        }

        if ($lista_id > 0) {
            $this->actualizar_contador_lista($lista_id);
        }

        wp_send_json_success([
            'importados' => $importados,
            'actualizados' => $actualizados,
            'errores' => $errores,
            'mensajes_error' => array_slice($mensajes_error, 0, 10),
        ]);
    }

    /**
     * AJAX: Exportar suscriptores
     */
    public function ajax_exportar_suscriptores() {
        check_ajax_referer('flavor_em_admin', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_send_json_error(__('Permisos insuficientes', 'flavor-chat-ia'));
        }

        $lista_id = isset($_POST['lista_id']) ? absint($_POST['lista_id']) : 0;
        $estado = isset($_POST['estado']) ? sanitize_key($_POST['estado']) : '';

        global $wpdb;
        $tabla = $wpdb->prefix . self::TABLE_PREFIX . 'suscriptores';

        $where_clauses = ['1=1'];
        $params = [];

        if (!empty($estado) && in_array($estado, self::ESTADOS_SUSCRIPTOR)) {
            $where_clauses[] = 's.estado = %s';
            $params[] = $estado;
        }

        if ($lista_id > 0) {
            $tabla_relacion = $wpdb->prefix . self::TABLE_PREFIX . 'suscriptor_lista';
            $where_clauses[] = "EXISTS (SELECT 1 FROM $tabla_relacion sl WHERE sl.suscriptor_id = s.id AND sl.lista_id = %d AND sl.estado = 'activo')";
            $params[] = $lista_id;
        }

        $where_sql = implode(' AND ', $where_clauses);

        $suscriptores = empty($params)
            ? $wpdb->get_results("SELECT email, nombre, apellidos, estado, fecha_registro FROM $tabla s WHERE $where_sql")
            : $wpdb->get_results($wpdb->prepare("SELECT email, nombre, apellidos, estado, fecha_registro FROM $tabla s WHERE $where_sql", ...$params));

        $csv_content = "email,nombre,apellidos,estado,fecha_registro\n";

        foreach ($suscriptores as $suscriptor) {
            $csv_content .= sprintf(
                '"%s","%s","%s","%s","%s"' . "\n",
                $suscriptor->email,
                $suscriptor->nombre,
                $suscriptor->apellidos,
                $suscriptor->estado,
                $suscriptor->fecha_registro
            );
        }

        wp_send_json_success([
            'csv' => $csv_content,
            'filename' => 'suscriptores-' . date('Y-m-d') . '.csv',
            'total' => count($suscriptores),
        ]);
    }

    /**
     * AJAX: Acciones masivas de suscriptores
     */
    public function ajax_bulk_action_suscriptores() {
        check_ajax_referer('flavor_em_admin', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_send_json_error(__('Permisos insuficientes', 'flavor-chat-ia'));
        }

        $accion = isset($_POST['accion_bulk']) ? sanitize_key($_POST['accion_bulk']) : '';
        $suscriptor_ids = isset($_POST['suscriptor_ids']) ? array_map('absint', (array) $_POST['suscriptor_ids']) : [];

        if (empty($suscriptor_ids)) {
            wp_send_json_error(__('No se han seleccionado suscriptores', 'flavor-chat-ia'));
        }

        global $wpdb;
        $tabla = $wpdb->prefix . self::TABLE_PREFIX . 'suscriptores';

        $afectados = 0;

        switch ($accion) {
            case 'activar':
                foreach ($suscriptor_ids as $id) {
                    $wpdb->update($tabla, ['estado' => 'activo'], ['id' => $id]);
                    $afectados++;
                }
                break;

            case 'desactivar':
                foreach ($suscriptor_ids as $id) {
                    $wpdb->update($tabla, ['estado' => 'baja'], ['id' => $id]);
                    $afectados++;
                }
                break;

            case 'eliminar':
                $tabla_relacion = $wpdb->prefix . self::TABLE_PREFIX . 'suscriptor_lista';
                foreach ($suscriptor_ids as $id) {
                    $wpdb->delete($tabla_relacion, ['suscriptor_id' => $id]);
                    $wpdb->delete($tabla, ['id' => $id]);
                    $afectados++;
                }
                break;

            case 'agregar_lista':
                $lista_id = isset($_POST['lista_id']) ? absint($_POST['lista_id']) : 0;
                if ($lista_id > 0) {
                    foreach ($suscriptor_ids as $id) {
                        $this->suscribir_a_lista($id, $lista_id);
                        $afectados++;
                    }
                    $this->actualizar_contador_lista($lista_id);
                }
                break;

            case 'quitar_lista':
                $lista_id = isset($_POST['lista_id']) ? absint($_POST['lista_id']) : 0;
                if ($lista_id > 0) {
                    foreach ($suscriptor_ids as $id) {
                        $this->dar_de_baja($id, $lista_id);
                        $afectados++;
                    }
                }
                break;

            case 'agregar_tag':
                $tag = sanitize_text_field($_POST['tag'] ?? '');
                if (!empty($tag)) {
                    foreach ($suscriptor_ids as $id) {
                        $this->aplicar_tag_suscriptor($id, $tag);
                        $afectados++;
                    }
                }
                break;

            default:
                wp_send_json_error(__('Accion no valida', 'flavor-chat-ia'));
        }

        wp_send_json_success([
            'mensaje' => sprintf(__('%d suscriptores afectados', 'flavor-chat-ia'), $afectados),
        ]);
    }

    /**
     * AJAX: Buscar suscriptores
     */
    public function ajax_buscar_suscriptores() {
        check_ajax_referer('flavor_em_admin', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_send_json_error(__('Permisos insuficientes', 'flavor-chat-ia'));
        }

        $termino = isset($_POST['termino']) ? sanitize_text_field($_POST['termino']) : '';

        if (strlen($termino) < 2) {
            wp_send_json_success([]);
        }

        global $wpdb;
        $tabla = $wpdb->prefix . self::TABLE_PREFIX . 'suscriptores';

        $suscriptores = $wpdb->get_results($wpdb->prepare(
            "SELECT id, email, nombre FROM $tabla
             WHERE (email LIKE %s OR nombre LIKE %s) AND estado = 'activo'
             LIMIT 10",
            '%' . $wpdb->esc_like($termino) . '%',
            '%' . $wpdb->esc_like($termino) . '%'
        ));

        wp_send_json_success($suscriptores);
    }

    // =========================================================================
    // AJAX HANDLERS ADMIN - AUTOMATIZACIONES
    // =========================================================================

    /**
     * AJAX Admin: Gestión de automatización
     */
    public function ajax_admin_automatizacion() {
        check_ajax_referer('flavor_em_admin', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_send_json_error(__('Permisos insuficientes', 'flavor-chat-ia'));
        }

        $accion = isset($_POST['accion']) ? sanitize_key($_POST['accion']) : '';

        switch ($accion) {
            case 'listar':
                $this->ajax_listar_automatizaciones();
                break;
            case 'obtener':
                $this->ajax_obtener_automatizacion(absint($_POST['automatizacion_id'] ?? 0));
                break;
            default:
                wp_send_json_error(__('Accion no valida', 'flavor-chat-ia'));
        }
    }

    /**
     * AJAX: Listar automatizaciones
     */
    private function ajax_listar_automatizaciones() {
        global $wpdb;

        $tabla = $wpdb->prefix . self::TABLE_PREFIX . 'automatizaciones';
        $automatizaciones = $wpdb->get_results("SELECT * FROM $tabla ORDER BY fecha_creacion DESC");

        foreach ($automatizaciones as &$auto) {
            $auto->pasos = json_decode($auto->pasos) ?: [];
        }

        wp_send_json_success($automatizaciones);
    }

    /**
     * AJAX: Obtener automatización
     *
     * @param int $automatizacion_id
     */
    private function ajax_obtener_automatizacion($automatizacion_id) {
        global $wpdb;

        $tabla = $wpdb->prefix . self::TABLE_PREFIX . 'automatizaciones';

        $auto = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM $tabla WHERE id = %d",
            $automatizacion_id
        ));

        if (!$auto) {
            wp_send_json_error(__('Automatizacion no encontrada', 'flavor-chat-ia'));
        }

        $auto->pasos = json_decode($auto->pasos) ?: [];
        $auto->trigger_condiciones = json_decode($auto->trigger_condiciones) ?: new stdClass();

        wp_send_json_success($auto);
    }

    /**
     * AJAX: Crear automatización
     */
    public function ajax_crear_automatizacion() {
        check_ajax_referer('flavor_em_admin', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_send_json_error(__('Permisos insuficientes', 'flavor-chat-ia'));
        }

        $nombre = sanitize_text_field($_POST['nombre'] ?? '');
        $trigger_tipo = sanitize_key($_POST['trigger_tipo'] ?? '');

        if (empty($nombre) || !in_array($trigger_tipo, self::TRIGGERS_AUTOMATIZACION)) {
            wp_send_json_error(__('Datos invalidos', 'flavor-chat-ia'));
        }

        global $wpdb;
        $tabla = $wpdb->prefix . self::TABLE_PREFIX . 'automatizaciones';

        $wpdb->insert($tabla, [
            'nombre' => $nombre,
            'descripcion' => sanitize_textarea_field($_POST['descripcion'] ?? ''),
            'trigger_tipo' => $trigger_tipo,
            'trigger_condiciones' => wp_json_encode($_POST['trigger_condiciones'] ?? []),
            'pasos' => wp_json_encode($_POST['pasos'] ?? []),
            'estado' => 'inactiva',
        ]);

        wp_send_json_success([
            'mensaje' => __('Automatizacion creada', 'flavor-chat-ia'),
            'automatizacion_id' => $wpdb->insert_id,
        ]);
    }

    /**
     * AJAX: Actualizar automatización
     */
    public function ajax_actualizar_automatizacion() {
        check_ajax_referer('flavor_em_admin', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_send_json_error(__('Permisos insuficientes', 'flavor-chat-ia'));
        }

        $automatizacion_id = isset($_POST['automatizacion_id']) ? absint($_POST['automatizacion_id']) : 0;

        global $wpdb;
        $tabla = $wpdb->prefix . self::TABLE_PREFIX . 'automatizaciones';

        $auto = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM $tabla WHERE id = %d",
            $automatizacion_id
        ));

        if (!$auto) {
            wp_send_json_error(__('Automatizacion no encontrada', 'flavor-chat-ia'));
        }

        $update_data = [];

        if (isset($_POST['nombre'])) {
            $update_data['nombre'] = sanitize_text_field($_POST['nombre']);
        }
        if (isset($_POST['descripcion'])) {
            $update_data['descripcion'] = sanitize_textarea_field($_POST['descripcion']);
        }
        if (isset($_POST['trigger_condiciones'])) {
            $update_data['trigger_condiciones'] = wp_json_encode($_POST['trigger_condiciones']);
        }
        if (isset($_POST['pasos'])) {
            $update_data['pasos'] = wp_json_encode($_POST['pasos']);
        }

        if (!empty($update_data)) {
            $wpdb->update($tabla, $update_data, ['id' => $automatizacion_id]);
        }

        wp_send_json_success(__('Automatizacion actualizada', 'flavor-chat-ia'));
    }

    /**
     * AJAX: Eliminar automatización
     */
    public function ajax_eliminar_automatizacion() {
        check_ajax_referer('flavor_em_admin', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_send_json_error(__('Permisos insuficientes', 'flavor-chat-ia'));
        }

        $automatizacion_id = isset($_POST['automatizacion_id']) ? absint($_POST['automatizacion_id']) : 0;

        global $wpdb;

        // Eliminar suscriptores de la automatización
        $wpdb->delete($wpdb->prefix . self::TABLE_PREFIX . 'auto_suscriptores', ['automatizacion_id' => $automatizacion_id]);

        // Eliminar automatización
        $wpdb->delete($wpdb->prefix . self::TABLE_PREFIX . 'automatizaciones', ['id' => $automatizacion_id]);

        wp_send_json_success(__('Automatizacion eliminada', 'flavor-chat-ia'));
    }

    /**
     * AJAX: Activar automatización
     */
    public function ajax_activar_automatizacion() {
        check_ajax_referer('flavor_em_admin', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_send_json_error(__('Permisos insuficientes', 'flavor-chat-ia'));
        }

        $automatizacion_id = isset($_POST['automatizacion_id']) ? absint($_POST['automatizacion_id']) : 0;

        global $wpdb;
        $tabla = $wpdb->prefix . self::TABLE_PREFIX . 'automatizaciones';

        $wpdb->update($tabla, ['estado' => 'activa'], ['id' => $automatizacion_id]);

        wp_send_json_success(__('Automatizacion activada', 'flavor-chat-ia'));
    }

    /**
     * AJAX: Pausar automatización
     */
    public function ajax_pausar_automatizacion() {
        check_ajax_referer('flavor_em_admin', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_send_json_error(__('Permisos insuficientes', 'flavor-chat-ia'));
        }

        $automatizacion_id = isset($_POST['automatizacion_id']) ? absint($_POST['automatizacion_id']) : 0;

        global $wpdb;
        $tabla = $wpdb->prefix . self::TABLE_PREFIX . 'automatizaciones';

        $wpdb->update($tabla, ['estado' => 'pausada'], ['id' => $automatizacion_id]);

        wp_send_json_success(__('Automatizacion pausada', 'flavor-chat-ia'));
    }

    // =========================================================================
    // AJAX HANDLERS ADMIN - PLANTILLAS
    // =========================================================================

    /**
     * AJAX Admin: Gestión de plantilla
     */
    public function ajax_admin_plantilla() {
        check_ajax_referer('flavor_em_admin', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_send_json_error(__('Permisos insuficientes', 'flavor-chat-ia'));
        }

        $accion = isset($_POST['accion']) ? sanitize_key($_POST['accion']) : '';

        switch ($accion) {
            case 'listar':
                $this->ajax_listar_plantillas();
                break;
            case 'obtener':
                $this->ajax_obtener_plantilla(absint($_POST['plantilla_id'] ?? 0));
                break;
            default:
                wp_send_json_error(__('Accion no valida', 'flavor-chat-ia'));
        }
    }

    /**
     * AJAX: Listar plantillas
     */
    private function ajax_listar_plantillas() {
        global $wpdb;

        $tabla = $wpdb->prefix . self::TABLE_PREFIX . 'plantillas';
        $categoria = isset($_POST['categoria']) ? sanitize_key($_POST['categoria']) : '';

        $where = $categoria ? $wpdb->prepare("WHERE categoria = %s", $categoria) : '';

        $plantillas = $wpdb->get_results("SELECT * FROM $tabla $where ORDER BY nombre ASC");

        wp_send_json_success($plantillas);
    }

    /**
     * AJAX: Obtener plantilla
     *
     * @param int $plantilla_id
     */
    private function ajax_obtener_plantilla($plantilla_id) {
        global $wpdb;

        $tabla = $wpdb->prefix . self::TABLE_PREFIX . 'plantillas';

        $plantilla = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM $tabla WHERE id = %d",
            $plantilla_id
        ));

        if (!$plantilla) {
            wp_send_json_error(__('Plantilla no encontrada', 'flavor-chat-ia'));
        }

        wp_send_json_success($plantilla);
    }

    /**
     * AJAX: Guardar plantilla
     */
    public function ajax_guardar_plantilla() {
        check_ajax_referer('flavor_em_admin', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_send_json_error(__('Permisos insuficientes', 'flavor-chat-ia'));
        }

        $plantilla_id = isset($_POST['plantilla_id']) ? absint($_POST['plantilla_id']) : 0;
        $nombre = sanitize_text_field($_POST['nombre'] ?? '');

        if (empty($nombre)) {
            wp_send_json_error(__('El nombre es requerido', 'flavor-chat-ia'));
        }

        global $wpdb;
        $tabla = $wpdb->prefix . self::TABLE_PREFIX . 'plantillas';

        $datos = [
            'nombre' => $nombre,
            'categoria' => sanitize_key($_POST['categoria'] ?? 'general'),
            'contenido_html' => wp_kses_post($_POST['contenido_html'] ?? ''),
            'contenido_texto' => sanitize_textarea_field($_POST['contenido_texto'] ?? ''),
            'thumbnail_url' => esc_url_raw($_POST['thumbnail_url'] ?? ''),
        ];

        if ($plantilla_id > 0) {
            $wpdb->update($tabla, $datos, ['id' => $plantilla_id]);
            $mensaje = __('Plantilla actualizada', 'flavor-chat-ia');
        } else {
            $wpdb->insert($tabla, $datos);
            $plantilla_id = $wpdb->insert_id;
            $mensaje = __('Plantilla creada', 'flavor-chat-ia');
        }

        wp_send_json_success([
            'mensaje' => $mensaje,
            'plantilla_id' => $plantilla_id,
        ]);
    }

    /**
     * AJAX: Eliminar plantilla
     */
    public function ajax_eliminar_plantilla() {
        check_ajax_referer('flavor_em_admin', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_send_json_error(__('Permisos insuficientes', 'flavor-chat-ia'));
        }

        $plantilla_id = isset($_POST['plantilla_id']) ? absint($_POST['plantilla_id']) : 0;

        global $wpdb;
        $tabla = $wpdb->prefix . self::TABLE_PREFIX . 'plantillas';

        $plantilla = $wpdb->get_row($wpdb->prepare(
            "SELECT es_predefinida FROM $tabla WHERE id = %d",
            $plantilla_id
        ));

        if ($plantilla && $plantilla->es_predefinida) {
            wp_send_json_error(__('No se pueden eliminar plantillas predefinidas', 'flavor-chat-ia'));
        }

        $wpdb->delete($tabla, ['id' => $plantilla_id]);

        wp_send_json_success(__('Plantilla eliminada', 'flavor-chat-ia'));
    }

    /**
     * AJAX: Duplicar plantilla
     */
    public function ajax_duplicar_plantilla() {
        check_ajax_referer('flavor_em_admin', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_send_json_error(__('Permisos insuficientes', 'flavor-chat-ia'));
        }

        $plantilla_id = isset($_POST['plantilla_id']) ? absint($_POST['plantilla_id']) : 0;

        global $wpdb;
        $tabla = $wpdb->prefix . self::TABLE_PREFIX . 'plantillas';

        $plantilla = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM $tabla WHERE id = %d",
            $plantilla_id
        ));

        if (!$plantilla) {
            wp_send_json_error(__('Plantilla no encontrada', 'flavor-chat-ia'));
        }

        $wpdb->insert($tabla, [
            'nombre' => $plantilla->nombre . ' (copia)',
            'categoria' => $plantilla->categoria,
            'contenido_html' => $plantilla->contenido_html,
            'contenido_texto' => $plantilla->contenido_texto,
            'thumbnail_url' => $plantilla->thumbnail_url,
            'es_predefinida' => 0,
        ]);

        wp_send_json_success([
            'mensaje' => __('Plantilla duplicada', 'flavor-chat-ia'),
            'plantilla_id' => $wpdb->insert_id,
        ]);
    }

    /**
     * AJAX: Preview de plantilla
     */
    public function ajax_preview_plantilla() {
        check_ajax_referer('flavor_em_admin', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_send_json_error(__('Permisos insuficientes', 'flavor-chat-ia'));
        }

        $plantilla_id = isset($_POST['plantilla_id']) ? absint($_POST['plantilla_id']) : 0;

        global $wpdb;
        $tabla = $wpdb->prefix . self::TABLE_PREFIX . 'plantillas';

        $plantilla = $wpdb->get_row($wpdb->prepare(
            "SELECT contenido_html FROM $tabla WHERE id = %d",
            $plantilla_id
        ));

        if (!$plantilla) {
            wp_send_json_error(__('Plantilla no encontrada', 'flavor-chat-ia'));
        }

        $contenido_preview = $this->personalizar_contenido($plantilla->contenido_html, [
            'id' => 0,
            'nombre' => 'Usuario Ejemplo',
            'email' => 'ejemplo@email.com',
        ]);

        wp_send_json_success([
            'html' => $contenido_preview,
        ]);
    }

    // =========================================================================
    // AJAX HANDLERS ADMIN - ESTADÍSTICAS
    // =========================================================================

    /**
     * AJAX: Obtener estadísticas generales
     */
    public function ajax_obtener_estadisticas() {
        check_ajax_referer('flavor_em_admin', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_send_json_error(__('Permisos insuficientes', 'flavor-chat-ia'));
        }

        $resultado = $this->action_estadisticas_generales([]);
        wp_send_json_success($resultado['estadisticas']);
    }

    /**
     * AJAX: Estadísticas de campaña específica
     */
    public function ajax_estadisticas_campania() {
        check_ajax_referer('flavor_em_admin', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_send_json_error(__('Permisos insuficientes', 'flavor-chat-ia'));
        }

        $campania_id = isset($_POST['campania_id']) ? absint($_POST['campania_id']) : 0;

        $resultado = $this->action_estadisticas_campania(['campania_id' => $campania_id]);

        if ($resultado['success']) {
            wp_send_json_success($resultado['campania']);
        } else {
            wp_send_json_error($resultado['error']);
        }
    }

    /**
     * AJAX: Estadísticas de lista
     */
    public function ajax_estadisticas_lista() {
        check_ajax_referer('flavor_em_admin', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_send_json_error(__('Permisos insuficientes', 'flavor-chat-ia'));
        }

        $lista_id = isset($_POST['lista_id']) ? absint($_POST['lista_id']) : 0;

        global $wpdb;
        $prefijo = $wpdb->prefix . self::TABLE_PREFIX;

        // Total suscriptores
        $total = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM {$prefijo}suscriptor_lista WHERE lista_id = %d AND estado = 'activo'",
            $lista_id
        ));

        // Nuevos últimos 30 días
        $nuevos_30d = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM {$prefijo}suscriptor_lista
             WHERE lista_id = %d AND estado = 'activo' AND fecha_suscripcion >= DATE_SUB(NOW(), INTERVAL 30 DAY)",
            $lista_id
        ));

        // Bajas últimos 30 días
        $bajas_30d = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM {$prefijo}suscriptor_lista
             WHERE lista_id = %d AND estado = 'baja' AND fecha_baja >= DATE_SUB(NOW(), INTERVAL 30 DAY)",
            $lista_id
        ));

        // Crecimiento por mes (últimos 6 meses)
        $crecimiento_mensual = $wpdb->get_results($wpdb->prepare(
            "SELECT DATE_FORMAT(fecha_suscripcion, '%%Y-%%m') as mes, COUNT(*) as total
             FROM {$prefijo}suscriptor_lista
             WHERE lista_id = %d AND fecha_suscripcion >= DATE_SUB(NOW(), INTERVAL 6 MONTH)
             GROUP BY DATE_FORMAT(fecha_suscripcion, '%%Y-%%m')
             ORDER BY mes ASC",
            $lista_id
        ));

        wp_send_json_success([
            'total_suscriptores' => (int) $total,
            'nuevos_30d' => (int) $nuevos_30d,
            'bajas_30d' => (int) $bajas_30d,
            'crecimiento_neto_30d' => (int) $nuevos_30d - (int) $bajas_30d,
            'crecimiento_mensual' => $crecimiento_mensual,
        ]);
    }

    /**
     * AJAX: Estadísticas por período
     */
    public function ajax_estadisticas_periodo() {
        check_ajax_referer('flavor_em_admin', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_send_json_error(__('Permisos insuficientes', 'flavor-chat-ia'));
        }

        $periodo = isset($_POST['periodo']) ? sanitize_key($_POST['periodo']) : '30d';
        $fecha_inicio = $this->calcular_fecha_inicio_periodo($periodo);

        global $wpdb;
        $prefijo = $wpdb->prefix . self::TABLE_PREFIX;

        // Emails enviados
        $enviados = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM {$prefijo}cola WHERE estado = 'enviado' AND enviado_en >= %s",
            $fecha_inicio
        ));

        // Aperturas
        $aperturas = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM {$prefijo}tracking WHERE tipo = 'abierto' AND fecha >= %s",
            $fecha_inicio
        ));

        // Clicks
        $clicks = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM {$prefijo}tracking WHERE tipo = 'click' AND fecha >= %s",
            $fecha_inicio
        ));

        // Rebotes
        $rebotes = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM {$prefijo}rebotes WHERE fecha >= %s",
            $fecha_inicio
        ));

        // Bajas
        $bajas = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM {$prefijo}tracking WHERE tipo = 'baja' AND fecha >= %s",
            $fecha_inicio
        ));

        // URLs más clickeadas
        $urls_populares = $wpdb->get_results($wpdb->prepare(
            "SELECT url_clickeada, COUNT(*) as total
             FROM {$prefijo}tracking
             WHERE tipo = 'click' AND fecha >= %s AND url_clickeada IS NOT NULL
             GROUP BY url_clickeada
             ORDER BY total DESC
             LIMIT 10",
            $fecha_inicio
        ));

        // Actividad por día
        $actividad_diaria = $wpdb->get_results($wpdb->prepare(
            "SELECT DATE(fecha) as dia,
                    SUM(CASE WHEN tipo = 'abierto' THEN 1 ELSE 0 END) as aperturas,
                    SUM(CASE WHEN tipo = 'click' THEN 1 ELSE 0 END) as clicks
             FROM {$prefijo}tracking
             WHERE fecha >= %s
             GROUP BY DATE(fecha)
             ORDER BY dia ASC",
            $fecha_inicio
        ));

        wp_send_json_success([
            'periodo' => $periodo,
            'enviados' => (int) $enviados,
            'aperturas' => (int) $aperturas,
            'clicks' => (int) $clicks,
            'rebotes' => (int) $rebotes,
            'bajas' => (int) $bajas,
            'tasa_apertura' => $enviados > 0 ? round(($aperturas / $enviados) * 100, 2) : 0,
            'tasa_clicks' => $aperturas > 0 ? round(($clicks / $aperturas) * 100, 2) : 0,
            'tasa_rebote' => $enviados > 0 ? round(($rebotes / $enviados) * 100, 2) : 0,
            'urls_populares' => $urls_populares,
            'actividad_diaria' => $actividad_diaria,
        ]);
    }

    /**
     * AJAX: Exportar estadísticas
     */
    public function ajax_exportar_estadisticas() {
        check_ajax_referer('flavor_em_admin', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_send_json_error(__('Permisos insuficientes', 'flavor-chat-ia'));
        }

        $tipo = isset($_POST['tipo']) ? sanitize_key($_POST['tipo']) : 'general';
        $periodo = isset($_POST['periodo']) ? sanitize_key($_POST['periodo']) : '30d';

        global $wpdb;
        $prefijo = $wpdb->prefix . self::TABLE_PREFIX;
        $fecha_inicio = $this->calcular_fecha_inicio_periodo($periodo);

        $csv_content = '';

        switch ($tipo) {
            case 'campanias':
                $campanias = $wpdb->get_results($wpdb->prepare(
                    "SELECT nombre, asunto, estado, total_enviados, total_abiertos, total_clicks, fecha_inicio_envio
                     FROM {$prefijo}campanias
                     WHERE fecha_creacion >= %s
                     ORDER BY fecha_creacion DESC",
                    $fecha_inicio
                ));

                $csv_content = "nombre,asunto,estado,enviados,abiertos,clicks,fecha_envio\n";
                foreach ($campanias as $campania) {
                    $csv_content .= sprintf(
                        '"%s","%s","%s",%d,%d,%d,"%s"' . "\n",
                        $campania->nombre,
                        $campania->asunto,
                        $campania->estado,
                        $campania->total_enviados,
                        $campania->total_abiertos,
                        $campania->total_clicks,
                        $campania->fecha_inicio_envio
                    );
                }
                break;

            case 'tracking':
                $tracking = $wpdb->get_results($wpdb->prepare(
                    "SELECT t.tipo, t.fecha, t.url_clickeada, s.email
                     FROM {$prefijo}tracking t
                     LEFT JOIN {$prefijo}suscriptores s ON t.suscriptor_id = s.id
                     WHERE t.fecha >= %s
                     ORDER BY t.fecha DESC
                     LIMIT 10000",
                    $fecha_inicio
                ));

                $csv_content = "tipo,fecha,url,email\n";
                foreach ($tracking as $item) {
                    $csv_content .= sprintf(
                        '"%s","%s","%s","%s"' . "\n",
                        $item->tipo,
                        $item->fecha,
                        $item->url_clickeada ?: '',
                        $item->email
                    );
                }
                break;

            default:
                $estadisticas = $this->action_estadisticas_generales([]);
                $csv_content = "metrica,valor\n";
                foreach ($estadisticas['estadisticas'] as $clave => $valor) {
                    $csv_content .= sprintf('"%s",%s' . "\n", $clave, $valor);
                }
        }

        wp_send_json_success([
            'csv' => $csv_content,
            'filename' => 'estadisticas-' . $tipo . '-' . date('Y-m-d') . '.csv',
        ]);
    }

    // =========================================================================
    // AJAX HANDLERS ADMIN - CONFIGURACIÓN
    // =========================================================================

    /**
     * AJAX: Guardar configuración
     */
    public function ajax_guardar_configuracion() {
        check_ajax_referer('flavor_em_admin', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_send_json_error(__('Permisos insuficientes', 'flavor-chat-ia'));
        }

        $settings = $this->get_default_settings();
        $nuevos_settings = [];

        foreach ($settings as $clave => $valor_default) {
            if (isset($_POST[$clave])) {
                switch (gettype($valor_default)) {
                    case 'boolean':
                        $nuevos_settings[$clave] = filter_var($_POST[$clave], FILTER_VALIDATE_BOOLEAN);
                        break;
                    case 'integer':
                        $nuevos_settings[$clave] = absint($_POST[$clave]);
                        break;
                    case 'array':
                        $nuevos_settings[$clave] = (array) $_POST[$clave];
                        break;
                    default:
                        if (strpos($clave, 'email') !== false) {
                            $nuevos_settings[$clave] = sanitize_email($_POST[$clave]);
                        } elseif (strpos($clave, 'url') !== false) {
                            $nuevos_settings[$clave] = esc_url_raw($_POST[$clave]);
                        } elseif (strpos($clave, 'html') !== false || strpos($clave, 'global') !== false) {
                            $nuevos_settings[$clave] = wp_kses_post($_POST[$clave]);
                        } else {
                            $nuevos_settings[$clave] = sanitize_text_field($_POST[$clave]);
                        }
                }
            } else {
                // Para checkboxes no marcados
                if (is_bool($valor_default)) {
                    $nuevos_settings[$clave] = false;
                }
            }
        }

        $this->save_settings($nuevos_settings);
        $this->clear_settings_cache();

        wp_send_json_success(__('Configuracion guardada', 'flavor-chat-ia'));
    }

    /**
     * AJAX: Test de conexión SMTP
     */
    public function ajax_test_smtp() {
        check_ajax_referer('flavor_em_admin', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_send_json_error(__('Permisos insuficientes', 'flavor-chat-ia'));
        }

        $email_test = isset($_POST['email_test']) ? sanitize_email($_POST['email_test']) : get_option('admin_email');

        if (!is_email($email_test)) {
            wp_send_json_error(__('Email de prueba no valido', 'flavor-chat-ia'));
        }

        $settings = $this->get_settings();

        $asunto = sprintf(__('[Test SMTP] %s', 'flavor-chat-ia'), get_bloginfo('name'));
        $mensaje = sprintf(
            __("Este es un email de prueba enviado desde %s.\n\nSi recibes este email, la configuracion SMTP es correcta.", 'flavor-chat-ia'),
            get_bloginfo('name')
        );

        $headers = [
            'From: ' . $settings['remitente_nombre'] . ' <' . $settings['remitente_email'] . '>',
        ];

        $enviado = wp_mail($email_test, $asunto, $mensaje, $headers);

        if ($enviado) {
            wp_send_json_success(__('Email de prueba enviado correctamente', 'flavor-chat-ia'));
        } else {
            global $phpmailer;
            $error = '';
            if (isset($phpmailer) && is_wp_error($phpmailer->ErrorInfo)) {
                $error = $phpmailer->ErrorInfo;
            }
            wp_send_json_error(__('Error al enviar el email de prueba', 'flavor-chat-ia') . ($error ? ': ' . $error : ''));
        }
    }

    /**
     * AJAX: Limpiar cola de envío
     */
    public function ajax_limpiar_cola() {
        check_ajax_referer('flavor_em_admin', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_send_json_error(__('Permisos insuficientes', 'flavor-chat-ia'));
        }

        $tipo = isset($_POST['tipo']) ? sanitize_key($_POST['tipo']) : 'fallidos';

        global $wpdb;
        $tabla = $wpdb->prefix . self::TABLE_PREFIX . 'cola';

        switch ($tipo) {
            case 'fallidos':
                $eliminados = $wpdb->query("DELETE FROM $tabla WHERE estado = 'fallido'");
                break;
            case 'antiguos':
                $eliminados = $wpdb->query("DELETE FROM $tabla WHERE estado = 'enviado' AND enviado_en < DATE_SUB(NOW(), INTERVAL 30 DAY)");
                break;
            case 'todos':
                $eliminados = $wpdb->query("DELETE FROM $tabla WHERE estado IN ('fallido', 'enviado')");
                break;
            default:
                $eliminados = 0;
        }

        wp_send_json_success([
            'mensaje' => sprintf(__('%d registros eliminados', 'flavor-chat-ia'), $eliminados),
        ]);
    }

    /**
     * AJAX: Limpiar logs
     */
    public function ajax_limpiar_logs() {
        check_ajax_referer('flavor_em_admin', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_send_json_error(__('Permisos insuficientes', 'flavor-chat-ia'));
        }

        $dias = isset($_POST['dias']) ? absint($_POST['dias']) : 30;

        global $wpdb;
        $tabla = $wpdb->prefix . self::TABLE_PREFIX . 'logs';

        $eliminados = $wpdb->query($wpdb->prepare(
            "DELETE FROM $tabla WHERE fecha < DATE_SUB(NOW(), INTERVAL %d DAY)",
            $dias
        ));

        // También limpiar tracking antiguo
        $tabla_tracking = $wpdb->prefix . self::TABLE_PREFIX . 'tracking';
        $wpdb->query($wpdb->prepare(
            "DELETE FROM $tabla_tracking WHERE fecha < DATE_SUB(NOW(), INTERVAL %d DAY)",
            $dias * 3 // Tracking se guarda más tiempo
        ));

        wp_send_json_success([
            'mensaje' => sprintf(__('%d logs eliminados', 'flavor-chat-ia'), $eliminados),
        ]);
    }

    // =========================================================================
    // WP CRON - FUNCIONES ADICIONALES
    // =========================================================================

    /**
     * Procesar campañas programadas
     */
    public function procesar_campanias_programadas() {
        global $wpdb;

        $tabla = $wpdb->prefix . self::TABLE_PREFIX . 'campanias';

        // Buscar campañas programadas que deben iniciar
        $campanias = $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM $tabla
             WHERE estado = 'programada'
             AND fecha_programada <= %s",
            current_time('mysql')
        ));

        foreach ($campanias as $campania) {
            $resultado = $this->programar_campania($campania->id, null);

            $this->registrar_log('info', sprintf(
                'Campana programada iniciada: %s (ID: %d)',
                $campania->nombre,
                $campania->id
            ));
        }
    }

    /**
     * Limpiar cola antigua
     */
    public function limpiar_cola_antigua() {
        global $wpdb;

        $tabla = $wpdb->prefix . self::TABLE_PREFIX . 'cola';

        // Eliminar emails enviados hace más de 30 días
        $wpdb->query(
            "DELETE FROM $tabla WHERE estado = 'enviado' AND enviado_en < DATE_SUB(NOW(), INTERVAL 30 DAY)"
        );

        // Eliminar emails fallidos hace más de 7 días
        $wpdb->query(
            "DELETE FROM $tabla WHERE estado = 'fallido' AND fecha_creacion < DATE_SUB(NOW(), INTERVAL 7 DAY)"
        );

        $this->registrar_log('info', 'Limpieza de cola completada');
    }

    /**
     * Procesar rebotes (webhook o IMAP)
     */
    public function procesar_rebotes() {
        // Esta función procesaría rebotes si hay configuración IMAP
        // Por ahora es un placeholder para implementación futura
        $settings = $this->get_settings();

        // Si hay webhook configurado, los rebotes se procesan en rest_webhook_bounce
        // Si hay IMAP configurado, se procesarían aquí

        do_action('flavor_em_procesar_rebotes');
    }

    /**
     * Enviar resumen diario al administrador
     */
    public function enviar_resumen_diario() {
        $settings = $this->get_settings();

        if (empty($settings['resumen_diario'])) {
            return;
        }

        global $wpdb;
        $prefijo = $wpdb->prefix . self::TABLE_PREFIX;

        $ayer = date('Y-m-d', strtotime('-1 day'));

        // Estadísticas del día anterior
        $nuevos_suscriptores = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM {$prefijo}suscriptores WHERE DATE(fecha_registro) = %s",
            $ayer
        ));

        $bajas = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM {$prefijo}suscriptores WHERE DATE(fecha_baja) = %s",
            $ayer
        ));

        $emails_enviados = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM {$prefijo}cola WHERE estado = 'enviado' AND DATE(enviado_en) = %s",
            $ayer
        ));

        $aperturas = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM {$prefijo}tracking WHERE tipo = 'abierto' AND DATE(fecha) = %s",
            $ayer
        ));

        $clicks = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM {$prefijo}tracking WHERE tipo = 'click' AND DATE(fecha) = %s",
            $ayer
        ));

        $total_suscriptores = $wpdb->get_var(
            "SELECT COUNT(*) FROM {$prefijo}suscriptores WHERE estado = 'activo'"
        );

        $asunto = sprintf(__('[%s] Resumen Email Marketing - %s', 'flavor-chat-ia'), get_bloginfo('name'), $ayer);

        $mensaje = sprintf(
            __("Resumen de Email Marketing para %s\n\n" .
               "Nuevos suscriptores: %d\n" .
               "Bajas: %d\n" .
               "Emails enviados: %d\n" .
               "Aperturas: %d\n" .
               "Clicks: %d\n\n" .
               "Total suscriptores activos: %d\n\n" .
               "Administra tu Email Marketing en: %s", 'flavor-chat-ia'),
            $ayer,
            $nuevos_suscriptores,
            $bajas,
            $emails_enviados,
            $aperturas,
            $clicks,
            $total_suscriptores,
            admin_url('admin.php?page=flavor-email-marketing')
        );

        $email_admin = $settings['email_notificaciones'] ?: get_option('admin_email');

        wp_mail($email_admin, $asunto, $mensaje);
    }

    /**
     * Registrar log
     *
     * @param string $nivel
     * @param string $mensaje
     * @param array $contexto
     */
    private function registrar_log($nivel, $mensaje, $contexto = []) {
        global $wpdb;

        $wpdb->insert($wpdb->prefix . self::TABLE_PREFIX . 'logs', [
            'nivel' => $nivel,
            'mensaje' => $mensaje,
            'contexto' => !empty($contexto) ? wp_json_encode($contexto) : null,
        ]);
    }

    // =========================================================================
    // TRIGGERS ADICIONALES
    // =========================================================================

    /**
     * Trigger: Perfil actualizado
     *
     * @param int $user_id
     * @param object $old_user_data
     */
    public function trigger_perfil_actualizado($user_id, $old_user_data) {
        $user = get_userdata($user_id);
        if (!$user) {
            return;
        }

        // Sincronizar datos con suscriptor si existe
        global $wpdb;
        $tabla = $wpdb->prefix . self::TABLE_PREFIX . 'suscriptores';

        $suscriptor = $wpdb->get_row($wpdb->prepare(
            "SELECT id FROM $tabla WHERE usuario_id = %d OR email = %s",
            $user_id,
            $user->user_email
        ));

        if ($suscriptor) {
            $wpdb->update($tabla, [
                'nombre' => $user->first_name ?: $user->display_name,
                'apellidos' => $user->last_name,
                'email' => $user->user_email,
                'usuario_id' => $user_id,
            ], ['id' => $suscriptor->id]);
        }
    }

    /**
     * Trigger: Login de usuario
     *
     * @param string $user_login
     * @param object $user
     */
    public function trigger_login($user_login, $user) {
        // Actualizar última actividad del suscriptor
        global $wpdb;
        $tabla = $wpdb->prefix . self::TABLE_PREFIX . 'suscriptores';

        $wpdb->query($wpdb->prepare(
            "UPDATE $tabla SET campos_personalizados = JSON_SET(
                COALESCE(campos_personalizados, '{}'),
                '$.ultimo_login',
                %s
            ) WHERE usuario_id = %d OR email = %s",
            current_time('mysql'),
            $user->ID,
            $user->user_email
        ));
    }

    // =========================================================================
    // VISTAS ADMIN
    // =========================================================================

    /**
     * Render: Dashboard
     */
    public function render_admin_dashboard() {
        $template_path = plugin_dir_path(__FILE__) . 'views/dashboard.php';
        if (file_exists($template_path)) {
            include $template_path;
        } else {
            $this->render_vista_fallback('dashboard');
        }
    }

    /**
     * Render: Campañas
     */
    public function render_admin_campanias() {
        $template_path = plugin_dir_path(__FILE__) . 'views/campanias.php';
        if (file_exists($template_path)) {
            include $template_path;
        } else {
            $this->render_vista_fallback('campanias');
        }
    }

    /**
     * Render: Automatizaciones
     */
    public function render_admin_automatizaciones() {
        $template_path = plugin_dir_path(__FILE__) . 'views/automatizaciones.php';
        if (file_exists($template_path)) {
            include $template_path;
        } else {
            $this->render_vista_fallback('automatizaciones');
        }
    }

    /**
     * Render: Suscriptores
     */
    public function render_admin_suscriptores() {
        $template_path = plugin_dir_path(__FILE__) . 'views/suscriptores.php';
        if (file_exists($template_path)) {
            include $template_path;
        } else {
            $this->render_vista_fallback('suscriptores');
        }
    }

    /**
     * Render: Listas
     */
    public function render_admin_listas() {
        $template_path = plugin_dir_path(__FILE__) . 'views/listas.php';
        if (file_exists($template_path)) {
            include $template_path;
        } else {
            $this->render_vista_fallback('listas');
        }
    }

    /**
     * Render: Plantillas
     */
    public function render_admin_plantillas() {
        $template_path = plugin_dir_path(__FILE__) . 'views/plantillas.php';
        if (file_exists($template_path)) {
            include $template_path;
        } else {
            $this->render_vista_fallback('plantillas');
        }
    }

    /**
     * Render: Estadísticas
     */
    public function render_admin_estadisticas() {
        $template_path = plugin_dir_path(__FILE__) . 'views/estadisticas.php';
        if (file_exists($template_path)) {
            include $template_path;
        } else {
            $this->render_vista_fallback('estadisticas');
        }
    }

    /**
     * Render: Configuración
     */
    public function render_admin_configuracion() {
        $template_path = plugin_dir_path(__FILE__) . 'views/configuracion.php';
        if (file_exists($template_path)) {
            include $template_path;
        } else {
            $this->render_vista_fallback('configuracion');
        }
    }

    /**
     * Render fallback para vistas no existentes
     *
     * @param string $vista
     */
    private function render_vista_fallback($vista) {
        $paginas = [
            'dashboard' => admin_url('admin.php?page=flavor-email-marketing'),
            'campanias' => admin_url('admin.php?page=flavor-email-marketing-campanias'),
            'automatizaciones' => admin_url('admin.php?page=flavor-email-marketing-automatizaciones'),
            'suscriptores' => admin_url('admin.php?page=flavor-email-marketing-suscriptores'),
            'listas' => admin_url('admin.php?page=flavor-email-marketing-listas'),
            'plantillas' => admin_url('admin.php?page=flavor-email-marketing-plantillas'),
            'estadisticas' => admin_url('admin.php?page=flavor-email-marketing-estadisticas'),
            'configuracion' => admin_url('admin.php?page=flavor-email-marketing-configuracion'),
        ];

        echo '<div class="wrap">';
        echo '<h1>' . esc_html(ucfirst($vista)) . '</h1>';
        echo '<div class="notice notice-warning"><p>' . esc_html__('La plantilla concreta no está disponible. Se muestra navegación de respaldo para mantener el módulo accesible.', 'flavor-chat-ia') . '</p></div>';
        echo '<p>';
        foreach ($paginas as $slug => $url) {
            $classes = $slug === $vista ? 'button button-primary' : 'button';
            echo '<a class="' . esc_attr($classes) . '" style="margin-right:8px;margin-bottom:8px;" href="' . esc_url($url) . '">' . esc_html(ucfirst($slug)) . '</a>';
        }
        echo '</p>';
        echo '</div>';
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
        $aliases = [
            'listar' => 'listar_listas',
            'listado' => 'listar_listas',
            'listas' => 'listar_listas',
            'explorar' => 'listar_listas',
            'buscar' => 'obtener_suscriptor',
            'suscriptor' => 'obtener_suscriptor',
            'suscribir' => 'suscribir',
            'crear' => 'suscribir',
            'nuevo' => 'suscribir',
            'stats' => 'estadisticas_generales',
            'estadisticas' => 'estadisticas_generales',
            'campania' => 'estadisticas_campania',
        ];

        $nombre = $aliases[$nombre] ?? $nombre;
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

    /**
     * Configuración para el panel de administración unificado
     *
     * @return array
     */
    protected function get_admin_config() {
        return [
            'id' => 'email_marketing',
            'label' => __('Email Marketing', 'flavor-chat-ia'),
            'icon' => 'dashicons-email-alt',
            'capability' => 'manage_options',
            'categoria' => 'comunicacion',
            'paginas' => [
                [
                    'slug' => 'flavor-em-dashboard',
                    'titulo' => __('Dashboard', 'flavor-chat-ia'),
                    'callback' => [$this, 'render_admin_dashboard'],
                ],
                [
                    'slug' => 'flavor-em-campanias',
                    'titulo' => __('Campañas', 'flavor-chat-ia'),
                    'callback' => [$this, 'render_admin_campanias'],
                    'badge' => [$this, 'contar_campanias_activas'],
                ],
                [
                    'slug' => 'flavor-em-suscriptores',
                    'titulo' => __('Suscriptores', 'flavor-chat-ia'),
                    'callback' => [$this, 'render_admin_suscriptores'],
                    'badge' => [$this, 'contar_suscriptores_activos'],
                ],
            ],
            'dashboard_widget' => [$this, 'render_dashboard_widget'],
            'estadisticas' => [$this, 'get_estadisticas_resumen'],
        ];
    }

    /**
     * Cuenta las campañas activas para el badge
     *
     * @return int
     */
    public function contar_campanias_activas() {
        // Verificar que el módulo esté activo
        if (!$this->can_activate()) {
            return 0;
        }

        global $wpdb;
        $tabla_campanias = $wpdb->prefix . self::TABLE_PREFIX . 'campanias';

        $contador = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM {$tabla_campanias} WHERE estado IN (%s, %s)",
            'enviando',
            'programada'
        ));

        return (int) $contador;
    }

    /**
     * Cuenta los suscriptores activos para el badge
     *
     * @return int
     */
    public function contar_suscriptores_activos() {
        // Verificar que el módulo esté activo
        if (!$this->can_activate()) {
            return 0;
        }

        global $wpdb;
        $tabla_suscriptores = $wpdb->prefix . self::TABLE_PREFIX . 'suscriptores';

        $contador = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM {$tabla_suscriptores} WHERE estado = %s",
            'activo'
        ));

        return (int) $contador;
    }

    /**
     * Renderiza el widget del dashboard unificado
     */
    public function render_dashboard_widget() {
        $estadisticas = $this->get_estadisticas_resumen();
        ?>
        <div class="em-dashboard-widget">
            <div class="em-widget-stats">
                <div class="em-stat">
                    <span class="em-stat-value"><?php echo esc_html(number_format_i18n($estadisticas['total_suscriptores'])); ?></span>
                    <span class="em-stat-label"><?php esc_html_e('Suscriptores', 'flavor-chat-ia'); ?></span>
                </div>
                <div class="em-stat">
                    <span class="em-stat-value"><?php echo esc_html(number_format_i18n($estadisticas['campanias_enviadas'])); ?></span>
                    <span class="em-stat-label"><?php esc_html_e('Campañas enviadas', 'flavor-chat-ia'); ?></span>
                </div>
                <div class="em-stat">
                    <span class="em-stat-value"><?php echo esc_html($estadisticas['tasa_apertura']); ?>%</span>
                    <span class="em-stat-label"><?php esc_html_e('Tasa de apertura', 'flavor-chat-ia'); ?></span>
                </div>
            </div>
        </div>
        <?php
    }

    /**
     * Obtiene estadísticas resumen para el panel unificado
     *
     * @return array
     */
    public function get_estadisticas_resumen() {
        global $wpdb;
        $prefix = $wpdb->prefix . self::TABLE_PREFIX;

        $total_suscriptores = (int) $wpdb->get_var(
            "SELECT COUNT(*) FROM {$prefix}suscriptores WHERE estado = 'activo'"
        );

        $campanias_enviadas = (int) $wpdb->get_var(
            "SELECT COUNT(*) FROM {$prefix}campanias WHERE estado = 'enviada'"
        );

        $total_enviados = (int) $wpdb->get_var(
            "SELECT SUM(enviados) FROM {$prefix}campanias WHERE estado = 'enviada'"
        );

        $total_aperturas = (int) $wpdb->get_var(
            "SELECT SUM(aperturas) FROM {$prefix}campanias WHERE estado = 'enviada'"
        );

        $tasa_apertura = $total_enviados > 0
            ? round(($total_aperturas / $total_enviados) * 100, 1)
            : 0;

        return [
            'total_suscriptores' => $total_suscriptores,
            'campanias_enviadas' => $campanias_enviadas,
            'total_enviados' => $total_enviados,
            'total_aperturas' => $total_aperturas,
            'tasa_apertura' => $tasa_apertura,
        ];
    }

    public function public_permission_check($request) {
        $method = strtoupper($request->get_method());
        $tipo = in_array($method, ['POST', 'PUT', 'DELETE'], true) ? 'post' : 'get';
        return Flavor_API_Rate_Limiter::check_rate_limit($tipo);
    }

    /**
     * Define las páginas del módulo para V3
     */
    public function get_pages_definition() {
        return [
            [
                'title' => __('Email Marketing', 'flavor-chat-ia'),
                'slug' => 'email-marketing',
                'content' => '<h1>' . __('Email Marketing', 'flavor-chat-ia') . '</h1>
<p>' . __('Gestiona tus campañas de email', 'flavor-chat-ia') . '</p>

[flavor_module_listing module="email_marketing" action="dashboard" columnas="3" limite="12"]',
                'parent' => 0,
            ],
            [
                'title' => __('Crear Campaña', 'flavor-chat-ia'),
                'slug' => 'crear-campana-email',
                'content' => '<h1>' . __('Crear Campaña', 'flavor-chat-ia') . '</h1>
<p>' . __('Crea una nueva campaña de email', 'flavor-chat-ia') . '</p>

[flavor_module_form module="email_marketing" action="crear_campana"]',
                'parent' => 'email-marketing',
            ],
            [
                'title' => __('Mis Listas', 'flavor-chat-ia'),
                'slug' => 'listas-email',
                'content' => '<h1>' . __('Mis Listas', 'flavor-chat-ia') . '</h1>
<p>' . __('Gestiona tus listas de suscriptores', 'flavor-chat-ia') . '</p>

[flavor_module_dashboard module="email_marketing" action="listas"]',
                'parent' => 'email-marketing',
            ],
            [
                'title' => __('Estadísticas', 'flavor-chat-ia'),
                'slug' => 'estadisticas-email',
                'content' => '<h1>' . __('Estadísticas', 'flavor-chat-ia') . '</h1>
<p>' . __('Analiza el rendimiento de tus campañas', 'flavor-chat-ia') . '</p>

[flavor_module_listing module="email_marketing" action="estadisticas"]',
                'parent' => 'email-marketing',
            ],
        ];
    }

    /**
     * Configuración para el Module Renderer
     *
     * @return array
     */
    public static function get_renderer_config(): array {
        return [
            'module'   => 'email-marketing',
            'title'    => __('Email Marketing', 'flavor-chat-ia'),
            'subtitle' => __('Gestiona campañas de email y listas de suscriptores', 'flavor-chat-ia'),
            'icon'     => '📧',
            'color'    => 'primary', // Usa variable CSS --flavor-primary del tema

            'database' => [
                'table'       => 'flavor_email_campaigns',
                'primary_key' => 'id',
            ],

            'fields' => [
                'nombre'      => ['type' => 'text', 'label' => __('Nombre campaña', 'flavor-chat-ia'), 'required' => true],
                'asunto'      => ['type' => 'text', 'label' => __('Asunto', 'flavor-chat-ia'), 'required' => true],
                'lista_id'    => ['type' => 'select', 'label' => __('Lista destino', 'flavor-chat-ia')],
                'contenido'   => ['type' => 'editor', 'label' => __('Contenido', 'flavor-chat-ia')],
                'fecha_envio' => ['type' => 'datetime', 'label' => __('Fecha programada', 'flavor-chat-ia')],
                'plantilla'   => ['type' => 'select', 'label' => __('Plantilla', 'flavor-chat-ia')],
            ],

            'estados' => [
                'borrador'    => ['label' => __('Borrador', 'flavor-chat-ia'), 'color' => 'gray', 'icon' => '📝'],
                'programada'  => ['label' => __('Programada', 'flavor-chat-ia'), 'color' => 'yellow', 'icon' => '⏰'],
                'enviando'    => ['label' => __('Enviando', 'flavor-chat-ia'), 'color' => 'blue', 'icon' => '📤'],
                'enviada'     => ['label' => __('Enviada', 'flavor-chat-ia'), 'color' => 'green', 'icon' => '✅'],
                'pausada'     => ['label' => __('Pausada', 'flavor-chat-ia'), 'color' => 'orange', 'icon' => '⏸️'],
            ],

            'stats' => [
                'campanias_activas' => ['label' => __('Campañas activas', 'flavor-chat-ia'), 'icon' => '📧', 'color' => 'blue'],
                'suscriptores'      => ['label' => __('Suscriptores', 'flavor-chat-ia'), 'icon' => '👥', 'color' => 'green'],
                'tasa_apertura'     => ['label' => __('Tasa apertura', 'flavor-chat-ia'), 'icon' => '📊', 'color' => 'purple'],
                'tasa_clics'        => ['label' => __('Tasa clics', 'flavor-chat-ia'), 'icon' => '🖱️', 'color' => 'indigo'],
            ],

            'card' => [
                'template'     => 'campania-card',
                'title_field'  => 'nombre',
                'subtitle_field' => 'asunto',
                'meta_fields'  => ['fecha_envio', 'enviados', 'aperturas'],
                'show_estado'  => true,
            ],

            'tabs' => [
                'campanias' => [
                    'label'      => __('Campañas', 'flavor-chat-ia'),
                    'icon'       => 'dashicons-email-alt',
                    'content'    => 'template:_archive.php',
                    'requires_login' => true,
                ],
                'crear' => [
                    'label'      => __('Crear campaña', 'flavor-chat-ia'),
                    'icon'       => 'dashicons-plus-alt',
                    'content'    => 'shortcode:email_marketing_crear',
                    'requires_login' => true,
                ],
                'listas' => [
                    'label'      => __('Listas', 'flavor-chat-ia'),
                    'icon'       => 'dashicons-groups',
                    'content'    => 'shortcode:email_marketing_listas',
                    'requires_login' => true,
                ],
                'plantillas' => [
                    'label'      => __('Plantillas', 'flavor-chat-ia'),
                    'icon'       => 'dashicons-layout',
                    'content'    => 'shortcode:email_marketing_plantillas',
                    'requires_login' => true,
                ],
                'estadisticas' => [
                    'label'      => __('Estadísticas', 'flavor-chat-ia'),
                    'icon'       => 'dashicons-chart-bar',
                    'content'    => 'shortcode:email_marketing_estadisticas',
                    'requires_login' => true,
                ],
            ],

            'archive' => [
                'columns'    => 2,
                'per_page'   => 10,
                'order_by'   => 'fecha_creacion',
                'order'      => 'DESC',
                'filterable' => ['estado', 'lista'],
            ],

            'dashboard' => [
                'widgets' => ['stats', 'campanias_recientes', 'rendimiento', 'listas_activas'],
                'actions' => [
                    'crear'    => ['label' => __('Nueva campaña', 'flavor-chat-ia'), 'icon' => '📧', 'color' => 'blue'],
                    'importar' => ['label' => __('Importar contactos', 'flavor-chat-ia'), 'icon' => '📥', 'color' => 'green'],
                ],
            ],

            'features' => [
                'editor_visual'   => true,
                'plantillas'      => true,
                'segmentacion'    => true,
                'automatizacion'  => true,
                'tracking'        => true,
                'ab_testing'      => true,
            ],
        ];
    }
}

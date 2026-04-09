<?php
/**
 * Modulo de Tramites para Chat IA - Sistema Completo
 *
 * @package FlavorChatIA
 * @since 1.0.0
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Modulo de Tramites - Gestion completa de tramites administrativos
 */
class Flavor_Chat_Tramites_Module extends Flavor_Chat_Module_Base {

    use Flavor_Module_Admin_Pages_Trait;
    use Flavor_Module_Notifications_Trait;
    use Flavor_Module_Integration_Consumer;

    /** @var string Version del modulo */
    const VERSION = '2.0.0';

    /** @var array Nombres de tablas */
    private $tabla_tipos_tramite;
    private $tabla_expedientes;
    private $tabla_documentos;
    private $tabla_estados;
    private $tabla_historial_estados;
    private $tabla_campos_formulario;
    private $tabla_historial;

    /**
     * Constructor
     */
    public function __construct() {
        // Auto-registered AJAX handlers
        add_action('wp_ajax_tramites_get_tipo_tramite', [$this, 'ajax_get_tipo_tramite']);
        add_action('wp_ajax_nopriv_tramites_get_tipo_tramite', [$this, 'ajax_get_tipo_tramite']);
        add_action('wp_ajax_tramites_crear_expediente', [$this, 'ajax_crear_expediente']);
        add_action('wp_ajax_nopriv_tramites_crear_expediente', [$this, 'ajax_crear_expediente']);
        add_action('wp_ajax_tramites_listar_expedientes', [$this, 'ajax_listar_expedientes']);
        add_action('wp_ajax_tramites_consultar_estado', [$this, 'ajax_consultar_estado']);
        add_action('wp_ajax_nopriv_tramites_consultar_estado', [$this, 'ajax_consultar_estado']);

        global $wpdb;

        $this->id = 'tramites';
        $this->name = 'Tramites y Gestiones'; // Translation loaded on init
        $this->description = 'Sistema completo de gestion de tramites administrativos online.'; // Translation loaded on init

        $this->tabla_tipos_tramite = $wpdb->prefix . 'flavor_tipos_tramite';
        $this->tabla_expedientes = $wpdb->prefix . 'flavor_expedientes';
        $this->tabla_documentos = $wpdb->prefix . 'flavor_documentos_expediente';
        $this->tabla_estados = $wpdb->prefix . 'flavor_estados_tramite';
        $this->tabla_historial_estados = $wpdb->prefix . 'flavor_historial_estados_expediente';
        $this->tabla_campos_formulario = $wpdb->prefix . 'flavor_campos_formulario';
        $this->tabla_historial = $wpdb->prefix . 'flavor_historial_expediente';

        parent::__construct();
        $this->cargar_frontend_controller();
    }

    /**
     * Carga el frontend controller para registrar shortcodes públicos.
     */
    private function cargar_frontend_controller() {
        if (class_exists('Flavor_Tramites_Frontend_Controller')) {
            Flavor_Tramites_Frontend_Controller::get_instance();
            return;
        }

        $archivo_controller = dirname(__FILE__) . '/frontend/class-tramites-frontend-controller.php';
        if (file_exists($archivo_controller)) {
            require_once $archivo_controller;
            if (class_exists('Flavor_Tramites_Frontend_Controller')) {
                Flavor_Tramites_Frontend_Controller::get_instance();
            }
        }
    }

    /**
     * {@inheritdoc}
     */
    public function can_activate() {
        return Flavor_Chat_Helpers::tabla_existe($this->tabla_tipos_tramite);
    }

    /**
     * {@inheritdoc}
     */
    public function get_activation_error() {
        if (!$this->can_activate()) {
            return __('Las tablas de Tramites no estan creadas. Se crearan automaticamente al activar.', FLAVOR_PLATFORM_TEXT_DOMAIN);
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
     * {@inheritdoc}
     */
    public function get_table_schema() {
        global $wpdb;
        $charset_collate = $wpdb->get_charset_collate();

        return [
            $this->tabla_tipos_tramite => "CREATE TABLE {$this->tabla_tipos_tramite} (
                id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
                nombre varchar(255) NOT NULL,
                descripcion text,
                categoria varchar(100) DEFAULT NULL,
                icono varchar(50) DEFAULT NULL,
                color varchar(20) DEFAULT '#6b7280',
                plazo_resolucion_dias int(11) DEFAULT NULL,
                requiere_cita tinyint(1) NOT NULL DEFAULT 0,
                permite_online tinyint(1) NOT NULL DEFAULT 1,
                permite_presencial tinyint(1) NOT NULL DEFAULT 1,
                precio decimal(10,2) DEFAULT NULL,
                estado enum('activo','inactivo') NOT NULL DEFAULT 'activo',
                orden int(11) NOT NULL DEFAULT 0,
                created_at datetime NOT NULL,
                PRIMARY KEY (id),
                KEY categoria (categoria),
                KEY estado (estado)
            ) $charset_collate;",

            $this->tabla_expedientes => "CREATE TABLE {$this->tabla_expedientes} (
                id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
                numero_expediente varchar(50) NOT NULL,
                tipo_tramite_id bigint(20) UNSIGNED NOT NULL,
                user_id bigint(20) UNSIGNED NOT NULL,
                solicitante_id bigint(20) UNSIGNED NOT NULL,
                estado_actual varchar(50) NOT NULL DEFAULT 'pendiente',
                via_tramitacion enum('online','presencial') NOT NULL DEFAULT 'online',
                datos_formulario longtext,
                observaciones text,
                fecha_solicitud datetime NOT NULL,
                fecha_creacion datetime NOT NULL,
                fecha_resolucion datetime DEFAULT NULL,
                fecha_limite datetime DEFAULT NULL,
                asignado_a bigint(20) UNSIGNED DEFAULT NULL,
                prioridad enum('baja','media','alta','urgente') NOT NULL DEFAULT 'media',
                created_at datetime NOT NULL,
                updated_at datetime DEFAULT NULL,
                PRIMARY KEY (id),
                UNIQUE KEY numero_expediente (numero_expediente),
                KEY tipo_tramite_id (tipo_tramite_id),
                KEY user_id (user_id),
                KEY solicitante_id (solicitante_id),
                KEY estado_actual (estado_actual),
                KEY asignado_a (asignado_a)
            ) $charset_collate;",

            $this->tabla_documentos => "CREATE TABLE {$this->tabla_documentos} (
                id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
                expediente_id bigint(20) UNSIGNED NOT NULL,
                nombre_archivo varchar(255) NOT NULL,
                ruta_archivo varchar(500) NOT NULL,
                tipo_documento varchar(100) DEFAULT NULL,
                tamano_bytes int(11) DEFAULT NULL,
                subido_por bigint(20) UNSIGNED NOT NULL,
                fecha_subida datetime NOT NULL,
                PRIMARY KEY (id),
                KEY expediente_id (expediente_id)
            ) $charset_collate;",

            $this->tabla_estados => "CREATE TABLE {$this->tabla_estados} (
                codigo varchar(50) NOT NULL,
                nombre varchar(100) NOT NULL,
                descripcion text DEFAULT NULL,
                color varchar(20) DEFAULT '#6b7280',
                icono varchar(50) DEFAULT 'info',
                es_inicial tinyint(1) DEFAULT 0,
                es_final tinyint(1) DEFAULT 0,
                permite_edicion tinyint(1) DEFAULT 1,
                permite_documentos tinyint(1) DEFAULT 1,
                notifica_solicitante tinyint(1) DEFAULT 1,
                orden int(11) DEFAULT 0,
                activo tinyint(1) DEFAULT 1,
                PRIMARY KEY (codigo),
                KEY orden (orden),
                KEY activo (activo)
            ) $charset_collate;",

            $this->tabla_historial_estados => "CREATE TABLE {$this->tabla_historial_estados} (
                id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
                expediente_id bigint(20) UNSIGNED NOT NULL,
                estado_anterior varchar(50) DEFAULT NULL,
                estado_nuevo varchar(50) NOT NULL,
                usuario_id bigint(20) UNSIGNED NOT NULL,
                comentario text,
                fecha_cambio datetime NOT NULL,
                PRIMARY KEY (id),
                KEY expediente_id (expediente_id)
            ) $charset_collate;",

            $this->tabla_campos_formulario => "CREATE TABLE {$this->tabla_campos_formulario} (
                id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
                tipo_tramite_id bigint(20) UNSIGNED NOT NULL,
                nombre_campo varchar(100) NOT NULL,
                etiqueta varchar(255) NOT NULL,
                tipo_campo varchar(50) NOT NULL,
                opciones text,
                requerido tinyint(1) NOT NULL DEFAULT 0,
                placeholder varchar(255) DEFAULT NULL,
                ayuda text,
                orden int(11) NOT NULL DEFAULT 0,
                PRIMARY KEY (id),
                KEY tipo_tramite_id (tipo_tramite_id)
            ) $charset_collate;",

            $this->tabla_historial => "CREATE TABLE {$this->tabla_historial} (
                id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
                expediente_id bigint(20) UNSIGNED NOT NULL,
                usuario_id bigint(20) UNSIGNED NOT NULL,
                accion varchar(100) NOT NULL,
                descripcion text,
                metadata longtext,
                fecha datetime NOT NULL,
                PRIMARY KEY (id),
                KEY expediente_id (expediente_id),
                KEY usuario_id (usuario_id)
            ) $charset_collate;"
        ];
    }

    /**
     * {@inheritdoc}
     */
    protected function get_default_settings() {
        return [
            'disponible_app' => 'cliente',
            'requiere_aprobacion' => true,
            'permite_tramites_online' => true,
            'permite_tramites_presencial' => true,
            'plazo_resolucion_maximo_dias' => 30,
            'notificar_cambio_estado' => true,
            'notificar_por_email' => true,
            'permite_cancelacion' => true,
            'dias_limite_cancelacion' => 5,
            'tamanio_maximo_archivo_mb' => 10,
            'tipos_archivo_permitidos' => 'pdf,jpg,jpeg,png,doc,docx',
            'max_archivos_por_expediente' => 20,
            'mostrar_timeline_publico' => true,
            'auto_asignar_numero_expediente' => true,
            'prefijo_expediente' => 'EXP',
            'requiere_login' => true,
        ];
    }

    /**
     * Define que tipos de contenido acepta este modulo
     *
     * @return array IDs de providers aceptados
     */
    protected function get_accepted_integrations() {
        return ['multimedia', 'biblioteca'];
    }

    /**
     * Define donde se muestran los metaboxes de integracion
     *
     * @return array Configuracion de targets
     */
    protected function get_integration_targets() {
        global $wpdb;
        return [
            [
                'type'    => 'table',
                'table'   => $wpdb->prefix . 'flavor_tramites',
                'context' => 'side',
            ],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function init() {
        $this->register_as_integration_consumer();

        add_action('init', [$this, 'maybe_create_tables']);
        add_action('init', [$this, 'register_shortcodes']);
        add_action('wp_enqueue_scripts', [$this, 'enqueue_assets']);
        add_action('wp_ajax_flavor_tramites_action', [$this, 'handle_ajax_request']);
        add_action('wp_ajax_nopriv_flavor_tramites_action', [$this, 'handle_ajax_request']);
        add_action('rest_api_init', [$this, 'register_rest_routes']);

        // Registrar en Panel Unificado de Gestión
        $this->registrar_en_panel_unificado();
        // Cargar Dashboard Tab
        $this->inicializar_dashboard_tab();

        // Dashboard tabs para usuarios (frontend)
        $this->init_dashboard_tabs();

        // Registrar páginas de administración ocultas
        add_action('admin_menu', [$this, 'registrar_paginas_admin']);
    }

    /**
     * Inicializa los tabs del dashboard de usuario
     */
    private function init_dashboard_tabs() {
        $dashboard_tab_file = dirname(__FILE__) . '/class-tramites-dashboard-tab.php';
        if (file_exists($dashboard_tab_file)) {
            require_once $dashboard_tab_file;
            if (class_exists('Flavor_Tramites_Dashboard_Tab')) {
                Flavor_Tramites_Dashboard_Tab::get_instance();
            }
        }
    }

    /**
     * Registrar shortcodes
     */
    public function register_shortcodes() {
        add_shortcode('catalogo_tramites', [$this, 'shortcode_catalogo_tramites']);
        add_shortcode('iniciar_tramite', [$this, 'shortcode_iniciar_tramite']);
        add_shortcode('mis_expedientes', [$this, 'shortcode_mis_expedientes']);
        add_shortcode('estado_expediente', [$this, 'shortcode_estado_expediente']);
    }

    // =========================================================================
    // PANEL UNIFICADO DE GESTIÓN
    // =========================================================================

    /**
     * Configuración para el Panel Unificado de Gestión
     *
     * @return array Configuración del módulo
     */
    protected function get_admin_config() {
        return [
            'id' => 'tramites',
            'label' => __('Trámites', FLAVOR_PLATFORM_TEXT_DOMAIN),
            'icon' => 'dashicons-clipboard',
            'capability' => 'manage_options',
            'categoria' => 'servicios',
            'paginas' => [
                [
                    'slug' => 'tramites-dashboard',
                    'titulo' => __('Dashboard', FLAVOR_PLATFORM_TEXT_DOMAIN),
                    'callback' => [$this, 'render_pagina_dashboard'],
                ],
                [
                    'slug' => 'tramites-pendientes',
                    'titulo' => __('Pendientes', FLAVOR_PLATFORM_TEXT_DOMAIN),
                    'callback' => [$this, 'render_admin_pendientes'],
                    'badge' => [$this, 'contar_tramites_pendientes'],
                ],
                [
                    'slug' => 'tramites-historial',
                    'titulo' => __('Historial', FLAVOR_PLATFORM_TEXT_DOMAIN),
                    'callback' => [$this, 'render_admin_historial'],
                ],
                [
                    'slug' => 'tramites-tipos',
                    'titulo' => __('Tipos de Trámite', FLAVOR_PLATFORM_TEXT_DOMAIN),
                    'callback' => [$this, 'render_admin_tipos'],
                ],
                [
                    'slug' => 'tramites-config',
                    'titulo' => __('Configuración', FLAVOR_PLATFORM_TEXT_DOMAIN),
                    'callback' => [$this, 'render_admin_config'],
                ],
            ],
            'estadisticas' => [$this, 'get_estadisticas_dashboard'],
        ];
    }

    /**
     * Cuenta trámites pendientes de resolución
     *
     * @return int
     */
    public function contar_tramites_pendientes() {
        // Verificar que el módulo esté activo
        if (!$this->can_activate()) {
            return 0;
        }

        global $wpdb;
        if (!Flavor_Chat_Helpers::tabla_existe($this->tabla_expedientes)) {
            return 0;
        }
        return (int) $wpdb->get_var(
            "SELECT COUNT(*) FROM {$this->tabla_expedientes} WHERE estado IN ('pendiente', 'en_proceso', 'requiere_documentacion')"
        );
    }

    /**
     * Cuenta trámites procesados hoy
     *
     * @return int
     */
    public function contar_tramites_procesados_hoy() {
        // Verificar que el módulo esté activo
        if (!$this->can_activate()) {
            return 0;
        }

        global $wpdb;
        if (!Flavor_Chat_Helpers::tabla_existe($this->tabla_expedientes)) {
            return 0;
        }
        $fecha_hoy = date('Y-m-d');
        return (int) $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM {$this->tabla_expedientes}
             WHERE estado = 'resuelto' AND DATE(fecha_resolucion) = %s",
            $fecha_hoy
        ));
    }

    /**
     * Estadísticas para el dashboard unificado
     *
     * @return array
     */
    public function get_estadisticas_dashboard() {
        $estadisticas = [];

        // Trámites pendientes
        $tramites_pendientes = $this->contar_tramites_pendientes();
        $estadisticas[] = [
            'icon' => 'dashicons-clipboard',
            'valor' => $tramites_pendientes,
            'label' => __('Trámites pendientes', FLAVOR_PLATFORM_TEXT_DOMAIN),
            'color' => $tramites_pendientes > 0 ? 'orange' : 'green',
            'enlace' => admin_url('admin.php?page=tramites-pendientes'),
        ];

        // Procesados hoy
        $tramites_procesados_hoy = $this->contar_tramites_procesados_hoy();
        $estadisticas[] = [
            'icon' => 'dashicons-yes-alt',
            'valor' => $tramites_procesados_hoy,
            'label' => __('Procesados hoy', FLAVOR_PLATFORM_TEXT_DOMAIN),
            'color' => 'blue',
            'enlace' => admin_url('admin.php?page=tramites-historial'),
        ];

        return $estadisticas;
    }

    /**
     * Renderiza el dashboard de administración de trámites
     */
    public function render_admin_dashboard() {
        $is_dashboard_viewer = current_user_can('flavor_ver_dashboard') && !current_user_can('manage_options');

        echo '<div class="wrap flavor-modulo-page">';
        $acciones = $is_dashboard_viewer
            ? [
                ['label' => __('Ver en portal', FLAVOR_PLATFORM_TEXT_DOMAIN), 'url' => home_url('/mi-portal/tramites/'), 'class' => ''],
            ]
            : [
                ['label' => __('Ver Pendientes', FLAVOR_PLATFORM_TEXT_DOMAIN), 'url' => admin_url('admin.php?page=tramites-pendientes'), 'class' => 'button-primary'],
                ['label' => __('Tipos de Trámite', FLAVOR_PLATFORM_TEXT_DOMAIN), 'url' => admin_url('admin.php?page=tramites-tipos'), 'class' => ''],
            ];
        $this->render_page_header(__('Dashboard de Trámites', FLAVOR_PLATFORM_TEXT_DOMAIN), $acciones);

        if (!$is_dashboard_viewer) {
            $this->handle_admin_actions();
        } else {
            echo '<div class="notice notice-info"><p>' . esc_html__('Vista resumida para gestor de grupos. Este dashboard muestra métricas, pero la gestión administrativa avanzada sigue reservada a administradores.', FLAVOR_PLATFORM_TEXT_DOMAIN) . '</p></div>';
        }
        echo '<p>' . __('Panel de control del módulo de trámites administrativos.', FLAVOR_PLATFORM_TEXT_DOMAIN) . '</p>';

        if (!$this->can_activate()) {
            echo '<div class="notice notice-warning"><p>' . esc_html__('El módulo no está activo o no tiene tablas creadas.', FLAVOR_PLATFORM_TEXT_DOMAIN) . '</p></div>';
            echo '</div>';
            return;
        }

        $estadisticas = $this->get_estadisticas_dashboard();
        if (!empty($estadisticas)) {
            echo '<div class="flavor-stats-grid">';
            foreach ($estadisticas as $estadistica) {
                $color_class = !empty($estadistica['color']) ? 'flavor-stat-' . $estadistica['color'] : '';
                $enlace = !empty($estadistica['enlace']) ? $estadistica['enlace'] : '';
                $card_open = $enlace ? '<a class="flavor-stat-card ' . esc_attr($color_class) . '" href="' . esc_url($enlace) . '">' : '<div class="flavor-stat-card ' . esc_attr($color_class) . '">';
                $card_close = $enlace ? '</a>' : '</div>';

                echo $card_open;
                echo '<div class="flavor-stat-icon"><span class="dashicons ' . esc_attr($estadistica['icon']) . '"></span></div>';
                echo '<div class="flavor-stat-content">';
                echo '<div class="flavor-stat-value">' . esc_html($estadistica['valor']) . '</div>';
                echo '<div class="flavor-stat-label">' . esc_html($estadistica['label']) . '</div>';
                echo '</div>';
                echo $card_close;
            }
            echo '</div>';
        }

        $this->render_tramites_resumen();
        echo '</div>';
    }

    /**
     * Renderizar página dashboard con vista completa
     */
    public function render_pagina_dashboard() {
        $views_path = dirname(__FILE__) . '/views/dashboard.php';
        if (file_exists($views_path)) {
            include $views_path;
        } else {
            $this->render_admin_dashboard();
        }
    }

    /**
     * Renderiza el listado de trámites pendientes
     */
    public function render_admin_pendientes() {
        $this->render_admin_listado_expedientes(true, false);
    }

    /**
     * Renderiza el historial de trámites
     */
    public function render_admin_historial() {
        $this->render_admin_listado_expedientes(false, true);
    }

    /**
     * Renderiza la gestión de tipos de trámite
     */
    public function render_admin_tipos() {
        echo '<div class="wrap flavor-modulo-page">';
        $this->render_page_header(__('Tipos de Trámite', FLAVOR_PLATFORM_TEXT_DOMAIN), [
            ['label' => __('Nuevo Tipo', FLAVOR_PLATFORM_TEXT_DOMAIN), 'url' => '#nuevo-tipo', 'class' => 'button-primary'],
        ]);
        $this->handle_admin_tipo_action();

        if (!$this->can_activate()) {
            echo '<div class="notice notice-warning"><p>' . esc_html__('El módulo no está activo o no tiene tablas creadas.', FLAVOR_PLATFORM_TEXT_DOMAIN) . '</p></div>';
            echo '</div>';
            return;
        }

        global $wpdb;
        $tipos = $wpdb->get_results("SELECT id, nombre, descripcion, categoria, icono, plazo_resolucion_dias, requiere_cita, permite_online, permite_presencial, precio, estado, orden, created_at FROM {$this->tabla_tipos_tramite} ORDER BY orden ASC, nombre ASC");

        echo '<p>' . __('Aquí se gestionan los diferentes tipos de trámites disponibles.', FLAVOR_PLATFORM_TEXT_DOMAIN) . '</p>';

        if (empty($tipos)) {
            echo '<p>' . esc_html__('No hay tipos registrados todavía.', FLAVOR_PLATFORM_TEXT_DOMAIN) . '</p>';
        } else {
            echo '<table class="widefat striped"><thead><tr>';
            echo '<th>ID</th><th>' . esc_html__('Nombre', FLAVOR_PLATFORM_TEXT_DOMAIN) . '</th><th>' . esc_html__('Categoría', FLAVOR_PLATFORM_TEXT_DOMAIN) . '</th><th>' . esc_html__('Estado', FLAVOR_PLATFORM_TEXT_DOMAIN) . '</th><th>' . esc_html__('Online', FLAVOR_PLATFORM_TEXT_DOMAIN) . '</th><th>' . esc_html__('Presencial', FLAVOR_PLATFORM_TEXT_DOMAIN) . '</th><th>' . esc_html__('Acciones', FLAVOR_PLATFORM_TEXT_DOMAIN) . '</th>';
            echo '</tr></thead><tbody>';
            foreach ($tipos as $tipo) {
                $estado_label = $tipo->estado === 'activo' ? __('Activo', FLAVOR_PLATFORM_TEXT_DOMAIN) : __('Inactivo', FLAVOR_PLATFORM_TEXT_DOMAIN);
                echo '<tr>';
                echo '<td>' . esc_html($tipo->id) . '</td>';
                echo '<td>' . esc_html($tipo->nombre) . '</td>';
                echo '<td>' . esc_html($tipo->categoria ?: '-') . '</td>';
                echo '<td>' . esc_html($estado_label) . '</td>';
                echo '<td>' . esc_html($tipo->permite_online ? __('Sí', FLAVOR_PLATFORM_TEXT_DOMAIN) : __('No', FLAVOR_PLATFORM_TEXT_DOMAIN)) . '</td>';
                echo '<td>' . esc_html($tipo->permite_presencial ? __('Sí', FLAVOR_PLATFORM_TEXT_DOMAIN) : __('No', FLAVOR_PLATFORM_TEXT_DOMAIN)) . '</td>';
                echo '<td>' . $this->render_tipo_actions($tipo->id, $tipo->estado) . '</td>';
                echo '</tr>';
            }
            echo '</tbody></table>';
        }

        echo '<hr id="nuevo-tipo">';
        echo '<h3>' . esc_html__('Nuevo tipo de trámite', FLAVOR_PLATFORM_TEXT_DOMAIN) . '</h3>';
        echo '<form method="post">';
        wp_nonce_field('tramites_tipo', 'tramites_tipo_nonce');
        echo '<table class="form-table"><tbody>';
        echo '<tr><th>' . esc_html__('Nombre', FLAVOR_PLATFORM_TEXT_DOMAIN) . '</th><td><input type="text" name="nombre" class="regular-text" required></td></tr>';
        echo '<tr><th>' . esc_html__('Descripción', FLAVOR_PLATFORM_TEXT_DOMAIN) . '</th><td><textarea name="descripcion" rows="3" class="large-text"></textarea></td></tr>';
        echo '<tr><th>' . esc_html__('Categoría', FLAVOR_PLATFORM_TEXT_DOMAIN) . '</th><td><input type="text" name="categoria" class="regular-text"></td></tr>';
        echo '<tr><th>' . esc_html__('Icono (dashicons)', FLAVOR_PLATFORM_TEXT_DOMAIN) . '</th><td><input type="text" name="icono" class="regular-text" placeholder="dashicons-clipboard"></td></tr>';
        echo '<tr><th>' . esc_html__('Plazo resolución (días)', FLAVOR_PLATFORM_TEXT_DOMAIN) . '</th><td><input type="number" name="plazo_resolucion_dias" min="1" value="7"></td></tr>';
        echo '<tr><th>' . esc_html__('Requiere cita', FLAVOR_PLATFORM_TEXT_DOMAIN) . '</th><td><label><input type="checkbox" name="requiere_cita" value="1"> ' . esc_html__('Sí', FLAVOR_PLATFORM_TEXT_DOMAIN) . '</label></td></tr>';
        echo '<tr><th>' . esc_html__('Permite online', FLAVOR_PLATFORM_TEXT_DOMAIN) . '</th><td><label><input type="checkbox" name="permite_online" value="1" checked> ' . esc_html__('Sí', FLAVOR_PLATFORM_TEXT_DOMAIN) . '</label></td></tr>';
        echo '<tr><th>' . esc_html__('Permite presencial', FLAVOR_PLATFORM_TEXT_DOMAIN) . '</th><td><label><input type="checkbox" name="permite_presencial" value="1" checked> ' . esc_html__('Sí', FLAVOR_PLATFORM_TEXT_DOMAIN) . '</label></td></tr>';
        echo '<tr><th>' . esc_html__('Precio', FLAVOR_PLATFORM_TEXT_DOMAIN) . '</th><td><input type="number" step="0.01" name="precio" value="0"></td></tr>';
        echo '<tr><th>' . esc_html__('Orden', FLAVOR_PLATFORM_TEXT_DOMAIN) . '</th><td><input type="number" name="orden" value="0"></td></tr>';
        echo '</tbody></table>';
        submit_button(__('Crear Tipo', FLAVOR_PLATFORM_TEXT_DOMAIN));
        echo '</form>';
        echo '</div>';
    }

    /**
     * Renderiza la configuración del módulo
     */
    public function render_admin_config() {
        echo '<div class="wrap flavor-modulo-page">';
        $this->render_page_header(__('Configuración de Trámites', FLAVOR_PLATFORM_TEXT_DOMAIN));
        $this->handle_admin_save_config();
        echo '<p>' . __('Configuración del sistema de gestión de trámites.', FLAVOR_PLATFORM_TEXT_DOMAIN) . '</p>';

        echo '<form method="post">';
        wp_nonce_field('tramites_config', 'tramites_config_nonce');
        echo '<table class="form-table"><tbody>';
        echo '<tr><th>' . esc_html__('Disponible en app', FLAVOR_PLATFORM_TEXT_DOMAIN) . '</th><td><select name="disponible_app">';
        foreach (['cliente' => __('Cliente', FLAVOR_PLATFORM_TEXT_DOMAIN), 'admin' => __('Admin', FLAVOR_PLATFORM_TEXT_DOMAIN), 'ambas' => __('Ambas', FLAVOR_PLATFORM_TEXT_DOMAIN)] as $key => $label) {
            echo '<option value="' . esc_attr($key) . '" ' . selected($this->get_setting('disponible_app'), $key, false) . '>' . esc_html($label) . '</option>';
        }
        echo '</select></td></tr>';
        echo '<tr><th>' . esc_html__('Requiere aprobación', FLAVOR_PLATFORM_TEXT_DOMAIN) . '</th><td><label><input type="checkbox" name="requiere_aprobacion" value="1" ' . checked($this->get_setting('requiere_aprobacion'), true, false) . '> ' . esc_html__('Sí', FLAVOR_PLATFORM_TEXT_DOMAIN) . '</label></td></tr>';
        echo '<tr><th>' . esc_html__('Permite trámites online', FLAVOR_PLATFORM_TEXT_DOMAIN) . '</th><td><label><input type="checkbox" name="permite_tramites_online" value="1" ' . checked($this->get_setting('permite_tramites_online'), true, false) . '> ' . esc_html__('Sí', FLAVOR_PLATFORM_TEXT_DOMAIN) . '</label></td></tr>';
        echo '<tr><th>' . esc_html__('Permite trámites presenciales', FLAVOR_PLATFORM_TEXT_DOMAIN) . '</th><td><label><input type="checkbox" name="permite_tramites_presencial" value="1" ' . checked($this->get_setting('permite_tramites_presencial'), true, false) . '> ' . esc_html__('Sí', FLAVOR_PLATFORM_TEXT_DOMAIN) . '</label></td></tr>';
        echo '<tr><th>' . esc_html__('Plazo máximo resolución (días)', FLAVOR_PLATFORM_TEXT_DOMAIN) . '</th><td><input type="number" name="plazo_resolucion_maximo_dias" min="1" value="' . esc_attr($this->get_setting('plazo_resolucion_maximo_dias')) . '"></td></tr>';
        echo '<tr><th>' . esc_html__('Notificar cambio de estado', FLAVOR_PLATFORM_TEXT_DOMAIN) . '</th><td><label><input type="checkbox" name="notificar_cambio_estado" value="1" ' . checked($this->get_setting('notificar_cambio_estado'), true, false) . '> ' . esc_html__('Sí', FLAVOR_PLATFORM_TEXT_DOMAIN) . '</label></td></tr>';
        echo '<tr><th>' . esc_html__('Notificar por email', FLAVOR_PLATFORM_TEXT_DOMAIN) . '</th><td><label><input type="checkbox" name="notificar_por_email" value="1" ' . checked($this->get_setting('notificar_por_email'), true, false) . '> ' . esc_html__('Sí', FLAVOR_PLATFORM_TEXT_DOMAIN) . '</label></td></tr>';
        echo '<tr><th>' . esc_html__('Permite cancelación', FLAVOR_PLATFORM_TEXT_DOMAIN) . '</th><td><label><input type="checkbox" name="permite_cancelacion" value="1" ' . checked($this->get_setting('permite_cancelacion'), true, false) . '> ' . esc_html__('Sí', FLAVOR_PLATFORM_TEXT_DOMAIN) . '</label></td></tr>';
        echo '<tr><th>' . esc_html__('Días límite cancelación', FLAVOR_PLATFORM_TEXT_DOMAIN) . '</th><td><input type="number" name="dias_limite_cancelacion" min="0" value="' . esc_attr($this->get_setting('dias_limite_cancelacion')) . '"></td></tr>';
        echo '<tr><th>' . esc_html__('Tamaño máximo archivo (MB)', FLAVOR_PLATFORM_TEXT_DOMAIN) . '</th><td><input type="number" name="tamanio_maximo_archivo_mb" min="1" value="' . esc_attr($this->get_setting('tamanio_maximo_archivo_mb')) . '"></td></tr>';
        echo '<tr><th>' . esc_html__('Tipos de archivo permitidos', FLAVOR_PLATFORM_TEXT_DOMAIN) . '</th><td><input type="text" name="tipos_archivo_permitidos" class="regular-text" value="' . esc_attr($this->get_setting('tipos_archivo_permitidos')) . '"></td></tr>';
        echo '<tr><th>' . esc_html__('Máx. archivos por expediente', FLAVOR_PLATFORM_TEXT_DOMAIN) . '</th><td><input type="number" name="max_archivos_por_expediente" min="1" value="' . esc_attr($this->get_setting('max_archivos_por_expediente')) . '"></td></tr>';
        echo '<tr><th>' . esc_html__('Mostrar timeline público', FLAVOR_PLATFORM_TEXT_DOMAIN) . '</th><td><label><input type="checkbox" name="mostrar_timeline_publico" value="1" ' . checked($this->get_setting('mostrar_timeline_publico'), true, false) . '> ' . esc_html__('Sí', FLAVOR_PLATFORM_TEXT_DOMAIN) . '</label></td></tr>';
        echo '<tr><th>' . esc_html__('Auto asignar número', FLAVOR_PLATFORM_TEXT_DOMAIN) . '</th><td><label><input type="checkbox" name="auto_asignar_numero_expediente" value="1" ' . checked($this->get_setting('auto_asignar_numero_expediente'), true, false) . '> ' . esc_html__('Sí', FLAVOR_PLATFORM_TEXT_DOMAIN) . '</label></td></tr>';
        echo '<tr><th>' . esc_html__('Prefijo expediente', FLAVOR_PLATFORM_TEXT_DOMAIN) . '</th><td><input type="text" name="prefijo_expediente" value="' . esc_attr($this->get_setting('prefijo_expediente')) . '"></td></tr>';
        echo '<tr><th>' . esc_html__('Requiere login', FLAVOR_PLATFORM_TEXT_DOMAIN) . '</th><td><label><input type="checkbox" name="requiere_login" value="1" ' . checked($this->get_setting('requiere_login'), true, false) . '> ' . esc_html__('Sí', FLAVOR_PLATFORM_TEXT_DOMAIN) . '</label></td></tr>';
        echo '</tbody></table>';
        submit_button(__('Guardar configuración', FLAVOR_PLATFORM_TEXT_DOMAIN));
        echo '</form>';
        echo '</div>';
    }

    private function render_tramites_resumen() {
        global $wpdb;
        $tabla = $this->tabla_expedientes;
        $tabla_tipos = $this->tabla_tipos_tramite;
        $tabla_users = $wpdb->users;

        $expedientes = $wpdb->get_results(
            "SELECT e.id, e.numero_expediente, e.estado, e.prioridad, e.fecha_solicitud, t.nombre as tipo_nombre, u.display_name
             FROM $tabla e
             LEFT JOIN $tabla_tipos t ON e.tipo_tramite_id = t.id
             LEFT JOIN $tabla_users u ON e.solicitante_id = u.ID
             ORDER BY e.fecha_solicitud DESC
             LIMIT 10"
        );

        echo '<h3>' . esc_html__('Expedientes recientes', FLAVOR_PLATFORM_TEXT_DOMAIN) . '</h3>';
        if (empty($expedientes)) {
            echo '<p>' . esc_html__('No hay expedientes registrados aún.', FLAVOR_PLATFORM_TEXT_DOMAIN) . '</p>';
            return;
        }

        echo '<table class="widefat striped"><thead><tr>';
        echo '<th>ID</th><th>' . esc_html__('Número', FLAVOR_PLATFORM_TEXT_DOMAIN) . '</th><th>' . esc_html__('Tipo', FLAVOR_PLATFORM_TEXT_DOMAIN) . '</th><th>' . esc_html__('Solicitante', FLAVOR_PLATFORM_TEXT_DOMAIN) . '</th><th>' . esc_html__('Estado', FLAVOR_PLATFORM_TEXT_DOMAIN) . '</th><th>' . esc_html__('Prioridad', FLAVOR_PLATFORM_TEXT_DOMAIN) . '</th><th>' . esc_html__('Fecha', FLAVOR_PLATFORM_TEXT_DOMAIN) . '</th>';
        echo '</tr></thead><tbody>';
        foreach ($expedientes as $expediente) {
            echo '<tr>';
            echo '<td>' . esc_html($expediente->id) . '</td>';
            echo '<td>' . esc_html($expediente->numero_expediente) . '</td>';
            echo '<td>' . esc_html($expediente->tipo_nombre ?: '-') . '</td>';
            echo '<td>' . esc_html($expediente->display_name ?: '-') . '</td>';
            echo '<td>' . esc_html($expediente->estado) . '</td>';
            echo '<td>' . esc_html($expediente->prioridad) . '</td>';
            echo '<td>' . esc_html(date_i18n(get_option('date_format'), strtotime($expediente->fecha_solicitud))) . '</td>';
            echo '</tr>';
        }
        echo '</tbody></table>';
    }

    private function render_admin_listado_expedientes($solo_pendientes, $modo_historial) {
        echo '<div class="wrap flavor-modulo-page">';
        $titulo = $solo_pendientes ? __('Trámites Pendientes', FLAVOR_PLATFORM_TEXT_DOMAIN) : __('Historial de Trámites', FLAVOR_PLATFORM_TEXT_DOMAIN);
        $this->render_page_header($titulo, [
            ['label' => __('Tipos de Trámite', FLAVOR_PLATFORM_TEXT_DOMAIN), 'url' => admin_url('admin.php?page=tramites-tipos'), 'class' => ''],
        ]);
        $this->handle_admin_actions();

        if (!$this->can_activate()) {
            echo '<div class="notice notice-warning"><p>' . esc_html__('El módulo no está activo o no tiene tablas creadas.', FLAVOR_PLATFORM_TEXT_DOMAIN) . '</p></div>';
            echo '</div>';
            return;
        }

        $estado = isset($_GET['estado']) ? sanitize_text_field($_GET['estado']) : '';
        $prioridad = isset($_GET['prioridad']) ? sanitize_text_field($_GET['prioridad']) : '';
        $tipo = isset($_GET['tipo']) ? absint($_GET['tipo']) : 0;
        $busqueda = isset($_GET['s']) ? sanitize_text_field($_GET['s']) : '';
        $fecha_desde = isset($_GET['desde']) ? sanitize_text_field($_GET['desde']) : '';
        $fecha_hasta = isset($_GET['hasta']) ? sanitize_text_field($_GET['hasta']) : '';

        if ($solo_pendientes && !$estado) {
            $estado = 'pendientes';
        }

        global $wpdb;
        $tabla = $this->tabla_expedientes;
        $tabla_tipos = $this->tabla_tipos_tramite;
        $tabla_users = $wpdb->users;

        $tipos = $wpdb->get_results("SELECT id, nombre FROM $tabla_tipos ORDER BY nombre ASC");

        echo '<form method="get" style="margin: 12px 0;">';
        echo '<input type="hidden" name="page" value="' . esc_attr($solo_pendientes ? 'tramites-pendientes' : 'tramites-historial') . '">';
        echo '<select name="estado">';
        echo '<option value="">' . esc_html__('Todos los estados', FLAVOR_PLATFORM_TEXT_DOMAIN) . '</option>';
        if ($solo_pendientes) {
            echo '<option value="pendientes" ' . selected($estado, 'pendientes', false) . '>' . esc_html__('Solo pendientes', FLAVOR_PLATFORM_TEXT_DOMAIN) . '</option>';
        }
        foreach (['pendiente','en_proceso','requiere_documentacion','resuelto','rechazado','cancelado'] as $estado_key) {
            echo '<option value="' . esc_attr($estado_key) . '" ' . selected($estado, $estado_key, false) . '>' . esc_html($estado_key) . '</option>';
        }
        echo '</select> ';
        echo '<select name="prioridad">';
        echo '<option value="">' . esc_html__('Todas las prioridades', FLAVOR_PLATFORM_TEXT_DOMAIN) . '</option>';
        foreach (['baja','media','alta','urgente'] as $prio) {
            echo '<option value="' . esc_attr($prio) . '" ' . selected($prioridad, $prio, false) . '>' . esc_html(ucfirst($prio)) . '</option>';
        }
        echo '</select> ';
        echo '<select name="tipo">';
        echo '<option value="0">' . esc_html__('Todos los tipos', FLAVOR_PLATFORM_TEXT_DOMAIN) . '</option>';
        foreach ($tipos as $t) {
            echo '<option value="' . esc_attr($t->id) . '" ' . selected($tipo, $t->id, false) . '>' . esc_html($t->nombre) . '</option>';
        }
        echo '</select> ';
        if ($modo_historial) {
            echo '<input type="date" name="desde" value="' . esc_attr($fecha_desde) . '"> ';
            echo '<input type="date" name="hasta" value="' . esc_attr($fecha_hasta) . '"> ';
        }
        echo '<input type="search" name="s" placeholder="' . esc_attr__('Buscar por número', FLAVOR_PLATFORM_TEXT_DOMAIN) . '" value="' . esc_attr($busqueda) . '"> ';
        echo '<button class="button">' . esc_html__('Filtrar', FLAVOR_PLATFORM_TEXT_DOMAIN) . '</button>';
        echo '</form>';

        $where = [];
        $params = [];
        if ($estado === 'pendientes') {
            $where[] = "e.estado IN ('pendiente','en_proceso','requiere_documentacion')";
        } else if ($estado) {
            $where[] = 'e.estado = %s';
            $params[] = $estado;
        } elseif ($solo_pendientes) {
            $where[] = "e.estado IN ('pendiente','en_proceso','requiere_documentacion')";
        }
        if ($prioridad) {
            $where[] = 'e.prioridad = %s';
            $params[] = $prioridad;
        }
        if ($tipo) {
            $where[] = 'e.tipo_tramite_id = %d';
            $params[] = $tipo;
        }
        if ($busqueda) {
            $where[] = 'e.numero_expediente LIKE %s';
            $params[] = '%' . $wpdb->esc_like($busqueda) . '%';
        }
        if ($modo_historial && $fecha_desde) {
            $where[] = 'DATE(e.fecha_solicitud) >= %s';
            $params[] = $fecha_desde;
        }
        if ($modo_historial && $fecha_hasta) {
            $where[] = 'DATE(e.fecha_solicitud) <= %s';
            $params[] = $fecha_hasta;
        }

        $sql = "SELECT e.*, t.nombre as tipo_nombre, u.display_name
                FROM $tabla e
                LEFT JOIN $tabla_tipos t ON e.tipo_tramite_id = t.id
                LEFT JOIN $tabla_users u ON e.solicitante_id = u.ID";
        if ($where) {
            $sql .= ' WHERE ' . implode(' AND ', $where);
        }
        $sql .= ' ORDER BY e.fecha_solicitud DESC LIMIT 200';

        $expedientes = $params ? $wpdb->get_results($wpdb->prepare($sql, $params)) : $wpdb->get_results($sql);

        if (empty($expedientes)) {
            echo '<p>' . esc_html__('No hay expedientes con esos filtros.', FLAVOR_PLATFORM_TEXT_DOMAIN) . '</p>';
            echo '</div>';
            return;
        }

        echo '<table class="widefat striped"><thead><tr>';
        echo '<th>ID</th><th>' . esc_html__('Número', FLAVOR_PLATFORM_TEXT_DOMAIN) . '</th><th>' . esc_html__('Tipo', FLAVOR_PLATFORM_TEXT_DOMAIN) . '</th><th>' . esc_html__('Solicitante', FLAVOR_PLATFORM_TEXT_DOMAIN) . '</th><th>' . esc_html__('Estado', FLAVOR_PLATFORM_TEXT_DOMAIN) . '</th><th>' . esc_html__('Prioridad', FLAVOR_PLATFORM_TEXT_DOMAIN) . '</th><th>' . esc_html__('Fecha', FLAVOR_PLATFORM_TEXT_DOMAIN) . '</th><th>' . esc_html__('Acciones', FLAVOR_PLATFORM_TEXT_DOMAIN) . '</th>';
        echo '</tr></thead><tbody>';
        foreach ($expedientes as $expediente) {
            echo '<tr>';
            echo '<td>' . esc_html($expediente->id) . '</td>';
            echo '<td>' . esc_html($expediente->numero_expediente) . '</td>';
            echo '<td>' . esc_html($expediente->tipo_nombre ?: '-') . '</td>';
            echo '<td>' . esc_html($expediente->display_name ?: '-') . '</td>';
            echo '<td>' . esc_html($expediente->estado) . '</td>';
            echo '<td>' . esc_html($expediente->prioridad) . '</td>';
            echo '<td>' . esc_html(date_i18n(get_option('date_format'), strtotime($expediente->fecha_solicitud))) . '</td>';
            echo '<td>' . $this->render_expediente_actions($expediente->id, $expediente->estado, $expediente->prioridad, $solo_pendientes ? 'tramites-pendientes' : 'tramites-historial') . '</td>';
            echo '</tr>';
        }
        echo '</tbody></table>';
        echo '</div>';
    }

    private function render_expediente_actions($expediente_id, $estado_actual, $prioridad_actual, $page_slug) {
        $estados = ['pendiente','en_proceso','requiere_documentacion','resuelto','rechazado','cancelado'];
        $prioridades = ['baja','media','alta','urgente'];

        $links = [];
        foreach ($estados as $estado) {
            if ($estado === $estado_actual) {
                continue;
            }
            $url = add_query_arg([
                'page' => $page_slug,
                'tramite_action' => 'estado',
                'expediente_id' => $expediente_id,
                'estado' => $estado,
            ], admin_url('admin.php'));
            $url = wp_nonce_url($url, 'tramites_admin_' . $expediente_id);
            $links[] = '<a href="' . esc_url($url) . '">' . esc_html($estado) . '</a>';
            if (count($links) >= 3) {
                break;
            }
        }

        $prio_links = [];
        foreach ($prioridades as $prioridad) {
            if ($prioridad === $prioridad_actual) {
                continue;
            }
            $url = add_query_arg([
                'page' => $page_slug,
                'tramite_action' => 'prioridad',
                'expediente_id' => $expediente_id,
                'prioridad' => $prioridad,
            ], admin_url('admin.php'));
            $url = wp_nonce_url($url, 'tramites_admin_' . $expediente_id);
            $prio_links[] = '<a href="' . esc_url($url) . '">' . esc_html(ucfirst($prioridad)) . '</a>';
            if (count($prio_links) >= 2) {
                break;
            }
        }

        $output = '';
        if (!empty($links)) {
            $output .= '<div><strong>' . esc_html__('Estado:', FLAVOR_PLATFORM_TEXT_DOMAIN) . '</strong> ' . implode(' | ', $links) . '</div>';
        }
        if (!empty($prio_links)) {
            $output .= '<div><strong>' . esc_html__('Prioridad:', FLAVOR_PLATFORM_TEXT_DOMAIN) . '</strong> ' . implode(' | ', $prio_links) . '</div>';
        }
        return $output;
    }

    private function handle_admin_actions() {
        if (empty($_GET['tramite_action']) || empty($_GET['expediente_id'])) {
            return;
        }

        $accion = sanitize_text_field($_GET['tramite_action']);
        $expediente_id = absint($_GET['expediente_id']);
        $nonce = $_GET['_wpnonce'] ?? '';

        if (!$expediente_id) {
            return;
        }
        if (!wp_verify_nonce($nonce, 'tramites_admin_' . $expediente_id)) {
            echo '<div class="notice notice-error"><p>' . esc_html__('Nonce inválido.', FLAVOR_PLATFORM_TEXT_DOMAIN) . '</p></div>';
            return;
        }

        global $wpdb;
        $tabla = $this->tabla_expedientes;

        if ($accion === 'estado') {
            $nuevo_estado = sanitize_text_field($_GET['estado'] ?? '');
            if (!$nuevo_estado) {
                return;
            }
            $data = ['estado' => $nuevo_estado];
            $format = ['%s'];
            if ($nuevo_estado === 'resuelto') {
                $data['fecha_resolucion'] = current_time('mysql');
                $format[] = '%s';
            }
            $wpdb->update($tabla, $data, ['id' => $expediente_id], $format, ['%d']);
            echo '<div class="notice notice-success"><p>' . esc_html__('Estado actualizado.', FLAVOR_PLATFORM_TEXT_DOMAIN) . '</p></div>';
        }

        if ($accion === 'prioridad') {
            $nueva_prioridad = sanitize_text_field($_GET['prioridad'] ?? '');
            if (!$nueva_prioridad) {
                return;
            }
            $wpdb->update($tabla, ['prioridad' => $nueva_prioridad], ['id' => $expediente_id], ['%s'], ['%d']);
            echo '<div class="notice notice-success"><p>' . esc_html__('Prioridad actualizada.', FLAVOR_PLATFORM_TEXT_DOMAIN) . '</p></div>';
        }
    }

    private function handle_admin_save_config() {
        if (empty($_POST['tramites_config_nonce'])) {
            return;
        }
        if (!wp_verify_nonce($_POST['tramites_config_nonce'], 'tramites_config')) {
            echo '<div class="notice notice-error"><p>' . esc_html__('Nonce inválido.', FLAVOR_PLATFORM_TEXT_DOMAIN) . '</p></div>';
            return;
        }

        $this->update_setting('disponible_app', sanitize_text_field($_POST['disponible_app'] ?? 'cliente'));
        $this->update_setting('requiere_aprobacion', !empty($_POST['requiere_aprobacion']));
        $this->update_setting('permite_tramites_online', !empty($_POST['permite_tramites_online']));
        $this->update_setting('permite_tramites_presencial', !empty($_POST['permite_tramites_presencial']));
        $this->update_setting('plazo_resolucion_maximo_dias', absint($_POST['plazo_resolucion_maximo_dias'] ?? 30));
        $this->update_setting('notificar_cambio_estado', !empty($_POST['notificar_cambio_estado']));
        $this->update_setting('notificar_por_email', !empty($_POST['notificar_por_email']));
        $this->update_setting('permite_cancelacion', !empty($_POST['permite_cancelacion']));
        $this->update_setting('dias_limite_cancelacion', absint($_POST['dias_limite_cancelacion'] ?? 5));
        $this->update_setting('tamanio_maximo_archivo_mb', absint($_POST['tamanio_maximo_archivo_mb'] ?? 10));
        $this->update_setting('tipos_archivo_permitidos', sanitize_text_field($_POST['tipos_archivo_permitidos'] ?? ''));
        $this->update_setting('max_archivos_por_expediente', absint($_POST['max_archivos_por_expediente'] ?? 20));
        $this->update_setting('mostrar_timeline_publico', !empty($_POST['mostrar_timeline_publico']));
        $this->update_setting('auto_asignar_numero_expediente', !empty($_POST['auto_asignar_numero_expediente']));
        $this->update_setting('prefijo_expediente', sanitize_text_field($_POST['prefijo_expediente'] ?? 'EXP'));
        $this->update_setting('requiere_login', !empty($_POST['requiere_login']));

        echo '<div class="notice notice-success"><p>' . esc_html__('Configuración guardada.', FLAVOR_PLATFORM_TEXT_DOMAIN) . '</p></div>';
    }

    private function render_tipo_actions($tipo_id, $estado_actual) {
        $nuevo_estado = $estado_actual === 'activo' ? 'inactivo' : 'activo';
        $label = $estado_actual === 'activo' ? __('Desactivar', FLAVOR_PLATFORM_TEXT_DOMAIN) : __('Activar', FLAVOR_PLATFORM_TEXT_DOMAIN);
        $url = add_query_arg([
            'page' => 'tramites-tipos',
            'tipo_action' => 'estado',
            'tipo_id' => $tipo_id,
            'estado' => $nuevo_estado,
        ], admin_url('admin.php'));
        $url = wp_nonce_url($url, 'tramites_tipo_' . $tipo_id);
        return '<a href="' . esc_url($url) . '">' . esc_html($label) . '</a>';
    }

    private function handle_admin_tipo_action() {
        if (!empty($_POST['tramites_tipo_nonce'])) {
            if (!wp_verify_nonce($_POST['tramites_tipo_nonce'], 'tramites_tipo')) {
                echo '<div class="notice notice-error"><p>' . esc_html__('Nonce inválido.', FLAVOR_PLATFORM_TEXT_DOMAIN) . '</p></div>';
                return;
            }

            global $wpdb;
            $wpdb->insert($this->tabla_tipos_tramite, [
                'nombre' => sanitize_text_field($_POST['nombre'] ?? ''),
                'descripcion' => sanitize_textarea_field($_POST['descripcion'] ?? ''),
                'categoria' => sanitize_text_field($_POST['categoria'] ?? ''),
                'icono' => sanitize_text_field($_POST['icono'] ?? ''),
                'plazo_resolucion_dias' => absint($_POST['plazo_resolucion_dias'] ?? 0),
                'requiere_cita' => !empty($_POST['requiere_cita']) ? 1 : 0,
                'permite_online' => !empty($_POST['permite_online']) ? 1 : 0,
                'permite_presencial' => !empty($_POST['permite_presencial']) ? 1 : 0,
                'precio' => floatval($_POST['precio'] ?? 0),
                'estado' => 'activo',
                'orden' => absint($_POST['orden'] ?? 0),
                'created_at' => current_time('mysql'),
            ]);

            echo '<div class="notice notice-success"><p>' . esc_html__('Tipo creado.', FLAVOR_PLATFORM_TEXT_DOMAIN) . '</p></div>';
        }

        if (empty($_GET['tipo_action']) || empty($_GET['tipo_id'])) {
            return;
        }

        $accion = sanitize_text_field($_GET['tipo_action']);
        $tipo_id = absint($_GET['tipo_id']);
        $nonce = $_GET['_wpnonce'] ?? '';

        if (!$tipo_id) {
            return;
        }
        if (!wp_verify_nonce($nonce, 'tramites_tipo_' . $tipo_id)) {
            echo '<div class="notice notice-error"><p>' . esc_html__('Nonce inválido.', FLAVOR_PLATFORM_TEXT_DOMAIN) . '</p></div>';
            return;
        }

        if ($accion === 'estado') {
            $nuevo_estado = sanitize_text_field($_GET['estado'] ?? '');
            if (!$nuevo_estado) {
                return;
            }
            global $wpdb;
            $wpdb->update($this->tabla_tipos_tramite, ['estado' => $nuevo_estado], ['id' => $tipo_id], ['%s'], ['%d']);
            echo '<div class="notice notice-success"><p>' . esc_html__('Estado actualizado.', FLAVOR_PLATFORM_TEXT_DOMAIN) . '</p></div>';
        }
    }

    // =========================================================================
    // FIN PANEL UNIFICADO DE GESTIÓN
    // =========================================================================

    /**
     * Encolar assets
     */
    public function enqueue_assets() {
        if ($this->should_load_assets()) {
            $modulo_url = plugin_dir_url(__FILE__);

            wp_enqueue_style(
                'flavor-tramites-css',
                $modulo_url . 'assets/css/tramites.css',
                [],
                self::VERSION
            );

            wp_enqueue_script(
                'flavor-tramites-js',
                $modulo_url . 'assets/js/tramites.js',
                ['jquery'],
                self::VERSION,
                true
            );

            wp_localize_script('flavor-tramites-js', 'flavorTramitesConfig', [
                'ajaxUrl' => admin_url('admin-ajax.php'),
                'restUrl' => rest_url('flavor-tramites/v1/'),
                'nonce' => wp_create_nonce('flavor_tramites_nonce'),
                'maxFileSize' => $this->get_setting('tamanio_maximo_archivo_mb') * 1024 * 1024,
                'allowedTypes' => explode(',', $this->get_setting('tipos_archivo_permitidos')),
                'maxFiles' => $this->get_setting('max_archivos_por_expediente'),
                'i18n' => [
                    'uploading' => __('Subiendo...', FLAVOR_PLATFORM_TEXT_DOMAIN),
                    'uploadError' => __('Error al subir el archivo', FLAVOR_PLATFORM_TEXT_DOMAIN),
                    'fileTooBig' => __('El archivo es demasiado grande', FLAVOR_PLATFORM_TEXT_DOMAIN),
                    'invalidType' => __('Tipo de archivo no permitido', FLAVOR_PLATFORM_TEXT_DOMAIN),
                    'confirmDelete' => __('¿Estas seguro de eliminar este documento?', FLAVOR_PLATFORM_TEXT_DOMAIN),
                    'confirmSubmit' => __('¿Deseas enviar el tramite?', FLAVOR_PLATFORM_TEXT_DOMAIN),
                    'required' => __('Este campo es obligatorio', FLAVOR_PLATFORM_TEXT_DOMAIN),
                    'invalidEmail' => __('Email no valido', FLAVOR_PLATFORM_TEXT_DOMAIN),
                    'invalidPhone' => __('Telefono no valido', FLAVOR_PLATFORM_TEXT_DOMAIN),
                    'success' => __('Operacion completada', FLAVOR_PLATFORM_TEXT_DOMAIN),
                    'error' => __('Ha ocurrido un error', FLAVOR_PLATFORM_TEXT_DOMAIN),
                ],
            ]);
        }
    }

    /**
     * Determinar si cargar assets
     */
    private function should_load_assets() {
        global $post;
        if (!$post) return false;

        $shortcodes_modulo = ['catalogo_tramites', 'iniciar_tramite', 'mis_expedientes', 'estado_expediente'];
        foreach ($shortcodes_modulo as $shortcode) {
            if (has_shortcode($post->post_content, $shortcode)) {
                return true;
            }
        }
        return false;
    }

    /**
     * Crear tablas si no existen
     */
    public function maybe_create_tables() {
        $db_version = get_option('flavor_tramites_db_version', '0');
        $current_version = '2.1.0'; // Incrementado para forzar recreación

        if (version_compare($db_version, $current_version, '<')) {
            $this->create_tables();
            $this->migrate_tables_v2();
            $this->insert_default_data();
            update_option('flavor_tramites_db_version', $current_version);
        }
    }

    /**
     * Migración de tablas a v2
     */
    private function migrate_tables_v2() {
        global $wpdb;

        // Añadir columnas faltantes a tipos_tramite
        $columnas_tipos = [
            'color' => "varchar(20) DEFAULT '#6b7280'",
        ];
        foreach ($columnas_tipos as $columna => $definicion) {
            $existe = $wpdb->get_var($wpdb->prepare(
                "SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = %s AND TABLE_NAME = %s AND COLUMN_NAME = %s",
                DB_NAME, $this->tabla_tipos_tramite, $columna
            ));
            if (!$existe) {
                $wpdb->query("ALTER TABLE {$this->tabla_tipos_tramite} ADD COLUMN {$columna} {$definicion}");
            }
        }

        // Añadir columnas faltantes a expedientes
        $columnas_expedientes = [
            'estado_actual' => "varchar(50) NOT NULL DEFAULT 'pendiente'",
            'user_id' => "bigint(20) UNSIGNED NOT NULL DEFAULT 0",
            'fecha_creacion' => "datetime DEFAULT NULL",
        ];
        foreach ($columnas_expedientes as $columna => $definicion) {
            $existe = $wpdb->get_var($wpdb->prepare(
                "SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = %s AND TABLE_NAME = %s AND COLUMN_NAME = %s",
                DB_NAME, $this->tabla_expedientes, $columna
            ));
            if (!$existe) {
                $wpdb->query("ALTER TABLE {$this->tabla_expedientes} ADD COLUMN {$columna} {$definicion}");
            }
        }

        // Migrar 'estado' a 'estado_actual' si existe
        $estado_existe = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = %s AND TABLE_NAME = %s AND COLUMN_NAME = 'estado'",
            DB_NAME, $this->tabla_expedientes
        ));
        if ($estado_existe) {
            $wpdb->query("UPDATE {$this->tabla_expedientes} SET estado_actual = estado WHERE estado_actual = 'pendiente' OR estado_actual = ''");
        }

        // Migrar solicitante_id a user_id
        $wpdb->query("UPDATE {$this->tabla_expedientes} SET user_id = solicitante_id WHERE user_id = 0");

        // Añadir columnas faltantes a estados_tramite
        $columnas_estados = [
            'codigo' => "varchar(50) NOT NULL",
            'nombre' => "varchar(100) NOT NULL",
            'descripcion' => "text DEFAULT NULL",
            'color' => "varchar(20) DEFAULT '#6b7280'",
            'icono' => "varchar(50) DEFAULT 'info'",
            'es_inicial' => "tinyint(1) DEFAULT 0",
            'es_final' => "tinyint(1) DEFAULT 0",
            'permite_edicion' => "tinyint(1) DEFAULT 1",
            'permite_documentos' => "tinyint(1) DEFAULT 1",
            'notifica_solicitante' => "tinyint(1) DEFAULT 1",
            'orden' => "int(11) DEFAULT 0",
            'activo' => "tinyint(1) DEFAULT 1",
        ];

        if (Flavor_Chat_Helpers::tabla_existe($this->tabla_estados)) {
            foreach ($columnas_estados as $columna => $definicion) {
                $existe = $wpdb->get_var($wpdb->prepare(
                    "SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = %s AND TABLE_NAME = %s AND COLUMN_NAME = %s",
                    DB_NAME, $this->tabla_estados, $columna
                ));
                if (!$existe) {
                    $wpdb->query("ALTER TABLE {$this->tabla_estados} ADD COLUMN {$columna} {$definicion}");
                }
            }
        }
    }

    /**
     * Crear todas las tablas necesarias
     */
        /**
     * Crea las tablas necesarias
     */
    private function create_tables() {
        $esquemas = $this->get_table_schema();

        if (empty($esquemas)) {
            return;
        }

        require_once ABSPATH . 'wp-admin/includes/upgrade.php';

        foreach ($esquemas as $tabla => $sql) {
            dbDelta($sql);
        }
    }

    /**
     * Insertar datos por defecto
     */
    private function insert_default_data() {
        global $wpdb;

        // Estados por defecto
        $estados_existentes = $wpdb->get_var("SELECT COUNT(*) FROM {$this->tabla_estados}");
        if ($estados_existentes == 0) {
            $estados_defecto = [
                ['borrador', 'Borrador', 'Expediente en preparacion', '#9e9e9e', 'dashicons-edit', 1, 0, 1, 1, 0, 1],
                ['pendiente', 'Pendiente', 'Pendiente de revision', '#ff9800', 'dashicons-clock', 0, 0, 0, 1, 1, 2],
                ['en_revision', 'En Revision', 'Siendo revisado por el departamento', '#2196f3', 'dashicons-visibility', 0, 0, 0, 1, 1, 3],
                ['subsanacion', 'Pendiente Subsanacion', 'Requiere documentacion adicional', '#f44336', 'dashicons-warning', 0, 0, 1, 1, 1, 4],
                ['en_tramite', 'En Tramite', 'Tramitandose', '#673ab7', 'dashicons-admin-generic', 0, 0, 0, 0, 1, 5],
                ['resuelto_favorable', 'Resuelto Favorable', 'Tramite aprobado', '#4caf50', 'dashicons-yes-alt', 0, 1, 0, 0, 1, 6],
                ['resuelto_desfavorable', 'Resuelto Desfavorable', 'Tramite denegado', '#f44336', 'dashicons-dismiss', 0, 1, 0, 0, 1, 7],
                ['archivado', 'Archivado', 'Expediente archivado', '#607d8b', 'dashicons-archive', 0, 1, 0, 0, 0, 8],
            ];

            foreach ($estados_defecto as $estado) {
                $wpdb->insert($this->tabla_estados, [
                    'codigo' => $estado[0],
                    'nombre' => $estado[1],
                    'descripcion' => $estado[2],
                    'color' => $estado[3],
                    'icono' => $estado[4],
                    'es_inicial' => $estado[5],
                    'es_final' => $estado[6],
                    'permite_edicion' => $estado[7],
                    'permite_documentos' => $estado[8],
                    'notifica_solicitante' => $estado[9],
                    'orden' => $estado[10],
                ]);
            }
        }
    }

    /**
     * Registrar rutas REST API
     */
    public function register_rest_routes() {
        $namespace = 'flavor-tramites/v1';

        register_rest_route($namespace, '/tipos', [
            'methods' => 'GET',
            'callback' => [$this, 'rest_get_tipos_tramite'],
            'permission_callback' => [$this, 'public_permission_check'],
        ]);

        register_rest_route($namespace, '/tipos/(?P<id>\d+)', [
            'methods' => 'GET',
            'callback' => [$this, 'rest_get_tipo_tramite'],
            'permission_callback' => [$this, 'public_permission_check'],
        ]);

        register_rest_route($namespace, '/expedientes', [
            'methods' => 'GET',
            'callback' => [$this, 'rest_get_expedientes'],
            'permission_callback' => [$this, 'check_user_permission'],
        ]);

        register_rest_route($namespace, '/expedientes', [
            'methods' => 'POST',
            'callback' => [$this, 'rest_create_expediente'],
            'permission_callback' => [$this, 'public_permission_check'],
        ]);

        register_rest_route($namespace, '/expedientes/(?P<id>\d+)', [
            'methods' => 'GET',
            'callback' => [$this, 'rest_get_expediente'],
            'permission_callback' => [$this, 'check_expediente_permission'],
        ]);

        register_rest_route($namespace, '/expedientes/(?P<id>\d+)', [
            'methods' => 'PUT',
            'callback' => [$this, 'rest_update_expediente'],
            'permission_callback' => [$this, 'check_expediente_permission'],
        ]);

        register_rest_route($namespace, '/expedientes/(?P<id>\d+)/documentos', [
            'methods' => 'POST',
            'callback' => [$this, 'rest_upload_documento'],
            'permission_callback' => [$this, 'check_expediente_permission'],
        ]);

        register_rest_route($namespace, '/expedientes/(?P<id>\d+)/historial', [
            'methods' => 'GET',
            'callback' => [$this, 'rest_get_historial'],
            'permission_callback' => [$this, 'check_expediente_permission'],
        ]);

        register_rest_route($namespace, '/expedientes/consulta/(?P<numero>[a-zA-Z0-9-]+)', [
            'methods' => 'GET',
            'callback' => [$this, 'rest_consulta_expediente'],
            'permission_callback' => [$this, 'public_permission_check'],
        ]);

        register_rest_route($namespace, '/estados', [
            'methods' => 'GET',
            'callback' => [$this, 'rest_get_estados'],
            'permission_callback' => [$this, 'public_permission_check'],
        ]);

        // Cargar también la clase API móvil (namespace flavor-chat-ia/v1)
        $api_file = dirname(__FILE__) . '/class-tramites-api.php';
        if (file_exists($api_file)) {
            require_once $api_file;
            if (class_exists('Flavor_Tramites_API')) {
                Flavor_Tramites_API::get_instance();
            }
        }
    }

    /**
     * Verificar permiso de usuario
     */
    public function check_user_permission() {
        return is_user_logged_in();
    }

    /**
     * Verificar permiso sobre expediente
     */
    public function check_expediente_permission($request) {
        $expediente_id = $request->get_param('id');
        $expediente = $this->get_expediente($expediente_id);

        if (!$expediente) {
            return false;
        }

        if (current_user_can('manage_options')) {
            return true;
        }

        $usuario_actual_id = get_current_user_id();
        if ($usuario_actual_id && $expediente->user_id == $usuario_actual_id) {
            return true;
        }

        $session_id = isset($_COOKIE['flavor_tramites_session']) ? sanitize_text_field($_COOKIE['flavor_tramites_session']) : '';
        if ($session_id && $expediente->session_id === $session_id) {
            return true;
        }

        return false;
    }

    // =========================================================================
    // REST API HANDLERS
    // =========================================================================

    /**
     * REST: Obtener tipos de tramite
     */
    public function rest_get_tipos_tramite($request) {
        global $wpdb;

        $categoria = $request->get_param('categoria');
        $busqueda = $request->get_param('busqueda');

        $where = ["estado = 'activo'"];
        $valores_preparar = [];

        if (!is_user_logged_in()) {
            $where[] = "visibilidad = 'publico'";
        } elseif (!current_user_can('manage_options')) {
            $where[] = "visibilidad IN ('publico', 'registrados')";
        }

        if ($categoria) {
            $where[] = "categoria = %s";
            $valores_preparar[] = sanitize_text_field($categoria);
        }

        if ($busqueda) {
            $where[] = "(nombre LIKE %s OR descripcion LIKE %s)";
            $termino_busqueda = '%' . $wpdb->esc_like(sanitize_text_field($busqueda)) . '%';
            $valores_preparar[] = $termino_busqueda;
            $valores_preparar[] = $termino_busqueda;
        }

        $sql = "SELECT id, codigo, nombre, descripcion_corta, categoria, subcategoria, icono, color, plazo_resolucion_dias, tasa, permite_urgente, requiere_cita
                FROM {$this->tabla_tipos_tramite}
                WHERE " . implode(' AND ', $where) . "
                ORDER BY orden ASC, nombre ASC";

        if (!empty($valores_preparar)) {
            $tipos_tramite = $wpdb->get_results($wpdb->prepare($sql, ...$valores_preparar));
        } else {
            $tipos_tramite = $wpdb->get_results($sql);
        }

        $categorias = $wpdb->get_col("SELECT DISTINCT categoria FROM {$this->tabla_tipos_tramite} WHERE estado = 'activo' AND categoria IS NOT NULL ORDER BY categoria");

        return rest_ensure_response([
            'success' => true,
            'tipos' => $tipos_tramite,
            'categorias' => $categorias,
            'total' => count($tipos_tramite),
        ]);
    }

    /**
     * REST: Obtener tipo de tramite individual
     */
    public function rest_get_tipo_tramite($request) {
        global $wpdb;

        $tipo_id = absint($request->get_param('id'));

        $tipo = $wpdb->get_row($wpdb->prepare(
            "SELECT id, nombre, descripcion, categoria, icono, color, plazo_resolucion_dias, requiere_cita, permite_online, permite_presencial, precio, estado, orden, created_at, requisitos, documentos_requeridos, tasa
             FROM {$this->tabla_tipos_tramite}
             WHERE id = %d AND estado = 'activo'",
            $tipo_id
        ));

        if (!$tipo) {
            return new WP_Error('not_found', 'Tipo de tramite no encontrado', ['status' => 404]);
        }

        $campos_formulario = $wpdb->get_results($wpdb->prepare(
            "SELECT id, tipo_tramite_id, nombre_campo, etiqueta, tipo_campo, opciones, requerido, placeholder, ayuda, orden, grupo, ancho, clase_css, condicion_visible, valor_defecto, patron_validacion, mensaje_error, es_readonly, es_obligatorio, activo
             FROM {$this->tabla_campos_formulario}
             WHERE tipo_tramite_id = %d AND activo = 1 ORDER BY orden ASC",
            $tipo_id
        ));

        foreach ($campos_formulario as &$campo) {
            if ($campo->opciones) {
                $campo->opciones = json_decode($campo->opciones, true);
            }
            if ($campo->condicion_visible) {
                $campo->condicion_visible = json_decode($campo->condicion_visible, true);
            }
        }

        $tipo->requisitos = $tipo->requisitos ? json_decode($tipo->requisitos, true) : [];
        $tipo->documentos_requeridos = $tipo->documentos_requeridos ? json_decode($tipo->documentos_requeridos, true) : [];
        $tipo->campos_formulario = $campos_formulario;

        return rest_ensure_response([
            'success' => true,
            'tipo' => $tipo,
        ]);
    }

    /**
     * REST: Obtener expedientes del usuario
     */
    public function rest_get_expedientes($request) {
        global $wpdb;

        $usuario_id = get_current_user_id();
        $estado = $request->get_param('estado');
        $pagina = max(1, intval($request->get_param('pagina') ?: 1));
        $por_pagina = min(50, max(1, intval($request->get_param('por_pagina') ?: 10)));
        $offset = ($pagina - 1) * $por_pagina;

        $where = ['e.user_id = %d'];
        $valores = [$usuario_id];

        if ($estado) {
            $where[] = 'e.estado_actual = %s';
            $valores[] = sanitize_text_field($estado);
        }

        $sql_total = "SELECT COUNT(*) FROM {$this->tabla_expedientes} e WHERE " . implode(' AND ', $where);
        $total = $wpdb->get_var($wpdb->prepare($sql_total, ...$valores));

        $sql = "SELECT e.*, t.nombre as tipo_nombre, t.icono as tipo_icono, t.color as tipo_color,
                       es.nombre as estado_nombre, es.color as estado_color, es.icono as estado_icono
                FROM {$this->tabla_expedientes} e
                LEFT JOIN {$this->tabla_tipos_tramite} t ON e.tipo_tramite_id = t.id
                LEFT JOIN {$this->tabla_estados} es ON e.estado_actual = es.codigo
                WHERE " . implode(' AND ', $where) . "
                ORDER BY e.fecha_creacion DESC
                LIMIT %d OFFSET %d";

        $valores[] = $por_pagina;
        $valores[] = $offset;

        $expedientes = $wpdb->get_results($wpdb->prepare($sql, ...$valores));

        return rest_ensure_response([
            'success' => true,
            'expedientes' => $expedientes,
            'total' => intval($total),
            'pagina' => $pagina,
            'por_pagina' => $por_pagina,
            'total_paginas' => ceil($total / $por_pagina),
        ]);
    }

    /**
     * REST: Crear expediente
     */
    public function rest_create_expediente($request) {
        global $wpdb;

        $tipo_tramite_id = absint($request->get_param('tipo_tramite_id'));
        $datos_formulario = $request->get_param('datos_formulario');
        $es_borrador = $request->get_param('borrador') === true;

        $tipo = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$this->tabla_tipos_tramite} WHERE id = %d AND estado = 'activo'",
            $tipo_tramite_id
        ));

        if (!$tipo) {
            return new WP_Error('invalid_type', 'Tipo de tramite no valido', ['status' => 400]);
        }

        if ($this->get_setting('requiere_login') && !is_user_logged_in()) {
            return new WP_Error('auth_required', 'Debes iniciar sesion', ['status' => 401]);
        }

        $nombre_solicitante = sanitize_text_field($request->get_param('nombre_solicitante'));
        $email_solicitante = sanitize_email($request->get_param('email_solicitante'));
        $telefono_solicitante = sanitize_text_field($request->get_param('telefono_solicitante'));
        $dni_solicitante = sanitize_text_field($request->get_param('dni_solicitante'));
        $direccion_solicitante = sanitize_textarea_field($request->get_param('direccion_solicitante'));

        if (!$es_borrador) {
            if (empty($nombre_solicitante) || empty($email_solicitante)) {
                return new WP_Error('missing_data', 'Nombre y email son obligatorios', ['status' => 400]);
            }

            if (!is_email($email_solicitante)) {
                return new WP_Error('invalid_email', 'Email no valido', ['status' => 400]);
            }

            $errores_validacion = $this->validar_campos_formulario($tipo_tramite_id, $datos_formulario);
            if (!empty($errores_validacion)) {
                return new WP_Error('validation_error', 'Errores de validacion', ['status' => 400, 'errors' => $errores_validacion]);
            }
        }

        $numero_expediente = $this->generar_numero_expediente();
        $usuario_id = get_current_user_id();
        $session_id = $this->get_or_create_session_id();

        $fecha_limite = null;
        if ($tipo->plazo_resolucion_dias) {
            $fecha_limite = date('Y-m-d H:i:s', strtotime('+' . $tipo->plazo_resolucion_dias . ' days'));
        }

        $datos_insercion = [
            'numero_expediente' => $numero_expediente,
            'tipo_tramite_id' => $tipo_tramite_id,
            'user_id' => $usuario_id ?: null,
            'session_id' => $session_id,
            'nombre_solicitante' => $nombre_solicitante,
            'email_solicitante' => $email_solicitante,
            'telefono_solicitante' => $telefono_solicitante,
            'dni_solicitante' => $dni_solicitante,
            'direccion_solicitante' => $direccion_solicitante,
            'datos_formulario' => $datos_formulario ? wp_json_encode($datos_formulario) : null,
            'notas_solicitante' => sanitize_textarea_field($request->get_param('notas')),
            'estado_actual' => $es_borrador ? 'borrador' : 'pendiente',
            'prioridad' => $request->get_param('urgente') ? 'urgente' : 'normal',
            'canal_entrada' => 'online',
            'departamento' => $tipo->departamento_responsable,
            'fecha_inicio' => $es_borrador ? null : current_time('mysql'),
            'fecha_limite' => $es_borrador ? null : $fecha_limite,
            'ip_creacion' => $this->get_client_ip(),
            'user_agent_creacion' => isset($_SERVER['HTTP_USER_AGENT']) ? sanitize_text_field($_SERVER['HTTP_USER_AGENT']) : '',
            'fecha_creacion' => current_time('mysql'),
        ];

        $resultado = $wpdb->insert($this->tabla_expedientes, $datos_insercion);

        if ($resultado === false) {
            return new WP_Error('db_error', 'Error al crear expediente', ['status' => 500]);
        }

        $expediente_id = $wpdb->insert_id;

        $this->registrar_historial($expediente_id, 'creacion', null, $es_borrador ? 'borrador' : 'pendiente', 'Expediente creado');

        if (!$es_borrador && $this->get_setting('notificar_por_email')) {
            $this->enviar_notificacion_inicio($expediente_id);
        }

        return rest_ensure_response([
            'success' => true,
            'mensaje' => $es_borrador ? 'Borrador guardado' : 'Expediente creado correctamente',
            'expediente' => [
                'id' => $expediente_id,
                'numero_expediente' => $numero_expediente,
                'estado' => $es_borrador ? 'borrador' : 'pendiente',
            ],
        ]);
    }

    /**
     * REST: Obtener expediente
     */
    public function rest_get_expediente($request) {
        $expediente_id = absint($request->get_param('id'));
        $expediente = $this->get_expediente_completo($expediente_id);

        if (!$expediente) {
            return new WP_Error('not_found', 'Expediente no encontrado', ['status' => 404]);
        }

        return rest_ensure_response([
            'success' => true,
            'expediente' => $expediente,
        ]);
    }

    /**
     * REST: Actualizar expediente
     */
    public function rest_update_expediente($request) {
        global $wpdb;

        $expediente_id = absint($request->get_param('id'));
        $expediente = $this->get_expediente($expediente_id);

        if (!$expediente) {
            return new WP_Error('not_found', 'Expediente no encontrado', ['status' => 404]);
        }

        $estado_info = $this->get_estado($expediente->estado_actual);
        if (!$estado_info || !$estado_info->permite_edicion) {
            return new WP_Error('not_editable', 'El expediente no se puede editar en su estado actual', ['status' => 403]);
        }

        $datos_actualizacion = [];
        $campos_permitidos = ['nombre_solicitante', 'email_solicitante', 'telefono_solicitante', 'dni_solicitante', 'direccion_solicitante', 'notas_solicitante'];

        foreach ($campos_permitidos as $campo) {
            $valor = $request->get_param($campo);
            if ($valor !== null) {
                $datos_actualizacion[$campo] = sanitize_text_field($valor);
            }
        }

        $datos_formulario = $request->get_param('datos_formulario');
        if ($datos_formulario !== null) {
            $datos_actualizacion['datos_formulario'] = wp_json_encode($datos_formulario);
        }

        $enviar = $request->get_param('enviar');
        if ($enviar && $expediente->estado_actual === 'borrador') {
            $errores = $this->validar_campos_formulario($expediente->tipo_tramite_id, $datos_formulario ?: json_decode($expediente->datos_formulario, true));
            if (!empty($errores)) {
                return new WP_Error('validation_error', 'Errores de validacion', ['status' => 400, 'errors' => $errores]);
            }

            $datos_actualizacion['estado_actual'] = 'pendiente';
            $datos_actualizacion['fecha_inicio'] = current_time('mysql');

            $tipo = $this->get_tipo_tramite($expediente->tipo_tramite_id);
            if ($tipo && $tipo->plazo_resolucion_dias) {
                $datos_actualizacion['fecha_limite'] = date('Y-m-d H:i:s', strtotime('+' . $tipo->plazo_resolucion_dias . ' days'));
            }
        }

        if (!empty($datos_actualizacion)) {
            $datos_actualizacion['fecha_modificacion'] = current_time('mysql');

            $wpdb->update($this->tabla_expedientes, $datos_actualizacion, ['id' => $expediente_id]);

            if (isset($datos_actualizacion['estado_actual'])) {
                $this->registrar_historial($expediente_id, 'cambio_estado', $expediente->estado_actual, $datos_actualizacion['estado_actual'], 'Expediente enviado');

                if ($this->get_setting('notificar_por_email')) {
                    $this->enviar_notificacion_inicio($expediente_id);
                }
            } else {
                $this->registrar_historial($expediente_id, 'otro', null, null, 'Datos actualizados');
            }
        }

        return rest_ensure_response([
            'success' => true,
            'mensaje' => __('Expediente actualizado', FLAVOR_PLATFORM_TEXT_DOMAIN),
        ]);
    }

    /**
     * REST: Subir documento
     */
    public function rest_upload_documento($request) {
        global $wpdb;

        $expediente_id = absint($request->get_param('id'));
        $expediente = $this->get_expediente($expediente_id);

        if (!$expediente) {
            return new WP_Error('not_found', 'Expediente no encontrado', ['status' => 404]);
        }

        $estado_info = $this->get_estado($expediente->estado_actual);
        if (!$estado_info || !$estado_info->permite_documentos) {
            return new WP_Error('not_allowed', 'No se pueden subir documentos en este estado', ['status' => 403]);
        }

        $documentos_actuales = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM {$this->tabla_documentos} WHERE expediente_id = %d",
            $expediente_id
        ));

        $max_archivos = $this->get_setting('max_archivos_por_expediente');
        if ($documentos_actuales >= $max_archivos) {
            return new WP_Error('max_files', "Maximo de {$max_archivos} archivos alcanzado", ['status' => 400]);
        }

        if (empty($_FILES['documento'])) {
            return new WP_Error('no_file', 'No se recibio ningun archivo', ['status' => 400]);
        }

        $archivo = $_FILES['documento'];
        $tipos_permitidos = array_map('trim', explode(',', $this->get_setting('tipos_archivo_permitidos')));
        $extension = strtolower(pathinfo($archivo['name'], PATHINFO_EXTENSION));

        if (!in_array($extension, $tipos_permitidos)) {
            return new WP_Error('invalid_type', 'Tipo de archivo no permitido', ['status' => 400]);
        }

        $tamanio_maximo = $this->get_setting('tamanio_maximo_archivo_mb') * 1024 * 1024;
        if ($archivo['size'] > $tamanio_maximo) {
            return new WP_Error('file_too_big', 'El archivo excede el tamanio maximo', ['status' => 400]);
        }

        $directorio_upload = wp_upload_dir();
        $directorio_expedientes = $directorio_upload['basedir'] . '/expedientes/' . $expediente->numero_expediente;

        if (!file_exists($directorio_expedientes)) {
            wp_mkdir_p($directorio_expedientes);
        }

        $nombre_archivo = wp_unique_filename($directorio_expedientes, sanitize_file_name($archivo['name']));
        $ruta_destino = $directorio_expedientes . '/' . $nombre_archivo;

        if (!move_uploaded_file($archivo['tmp_name'], $ruta_destino)) {
            return new WP_Error('upload_failed', 'Error al subir el archivo', ['status' => 500]);
        }

        $hash_archivo = hash_file('sha256', $ruta_destino);
        $tipo_documento = sanitize_text_field($request->get_param('tipo_documento'));

        $wpdb->insert($this->tabla_documentos, [
            'expediente_id' => $expediente_id,
            'tipo_documento' => $tipo_documento,
            'nombre_original' => $archivo['name'],
            'nombre_archivo' => $nombre_archivo,
            'ruta_archivo' => str_replace($directorio_upload['basedir'], '', $ruta_destino),
            'mime_type' => $archivo['type'],
            'tamanio_bytes' => $archivo['size'],
            'hash_archivo' => $hash_archivo,
            'origen' => 'solicitante',
            'fecha_subida' => current_time('mysql'),
            'subido_por' => get_current_user_id() ?: null,
        ]);

        $documento_id = $wpdb->insert_id;

        $this->registrar_historial($expediente_id, 'documento_subido', null, null, "Documento subido: {$archivo['name']}");

        return rest_ensure_response([
            'success' => true,
            'mensaje' => __('Expediente actualizado', FLAVOR_PLATFORM_TEXT_DOMAIN),
            'documento' => [
                'id' => $documento_id,
                'nombre' => $archivo['name'],
                'tamanio' => $archivo['size'],
            ],
        ]);
    }

    /**
     * REST: Obtener historial
     */
    public function rest_get_historial($request) {
        global $wpdb;

        $expediente_id = absint($request->get_param('id'));

        $es_admin = current_user_can('manage_options');
        $where_publico = $es_admin ? '' : 'AND es_publico = 1';

        $historial = $wpdb->get_results($wpdb->prepare(
            "SELECT id, expediente_id, usuario_id, accion, descripcion, metadata, fecha, es_publico, fecha_evento
             FROM {$this->tabla_historial}
             WHERE expediente_id = %d {$where_publico}
             ORDER BY fecha_evento DESC",
            $expediente_id
        ));

        return rest_ensure_response([
            'success' => true,
            'historial' => $historial,
        ]);
    }

    /**
     * REST: Consulta publica de expediente
     */
    public function rest_consulta_expediente($request) {
        global $wpdb;

        $numero = sanitize_text_field($request->get_param('numero'));

        $expediente = $wpdb->get_row($wpdb->prepare(
            "SELECT e.numero_expediente, e.estado_actual, e.fecha_creacion, e.fecha_limite,
                    t.nombre as tipo_nombre, es.nombre as estado_nombre, es.color as estado_color
             FROM {$this->tabla_expedientes} e
             LEFT JOIN {$this->tabla_tipos_tramite} t ON e.tipo_tramite_id = t.id
             LEFT JOIN {$this->tabla_estados} es ON e.estado_actual = es.codigo
             WHERE e.numero_expediente = %s",
            $numero
        ));

        if (!$expediente) {
            return new WP_Error('not_found', 'Expediente no encontrado', ['status' => 404]);
        }

        $historial = $wpdb->get_results($wpdb->prepare(
            "SELECT tipo_evento, descripcion, fecha_evento
             FROM {$this->tabla_historial}
             WHERE expediente_id = (SELECT id FROM {$this->tabla_expedientes} WHERE numero_expediente = %s)
             AND es_publico = 1
             ORDER BY fecha_evento DESC
             LIMIT 10",
            $numero
        ));

        return rest_ensure_response([
            'success' => true,
            'expediente' => $expediente,
            'historial' => $historial,
        ]);
    }

    /**
     * REST: Obtener estados
     */
    public function rest_get_estados($request) {
        global $wpdb;

        $estados = $wpdb->get_results(
            "SELECT codigo, nombre, descripcion, color, icono, es_inicial, es_final
             FROM {$this->tabla_estados}
             WHERE activo = 1
             ORDER BY orden ASC"
        );

        return rest_ensure_response([
            'success' => true,
            'estados' => $estados,
        ]);
    }

    // =========================================================================
    // AJAX HANDLER
    // =========================================================================

    /**
     * Manejar peticiones AJAX
     */
    public function handle_ajax_request() {
        check_ajax_referer('flavor_tramites_nonce', 'nonce');

        $accion = isset($_POST['accion_tramites']) ? sanitize_text_field($_POST['accion_tramites']) : '';

        $acciones_permitidas = [
            'get_tipo_tramite',
            'crear_expediente',
            'actualizar_expediente',
            'subir_documento',
            'eliminar_documento',
            'obtener_expediente',
            'listar_expedientes',
            'consultar_estado',
            'cancelar_expediente',
        ];

        if (!in_array($accion, $acciones_permitidas)) {
            wp_send_json_error(['mensaje' => __('Accion no permitida', FLAVOR_PLATFORM_TEXT_DOMAIN)]);
        }

        $metodo = 'ajax_' . $accion;
        if (method_exists($this, $metodo)) {
            $resultado = $this->$metodo();
            if (isset($resultado['success']) && $resultado['success']) {
                wp_send_json_success($resultado);
            } else {
                wp_send_json_error($resultado);
            }
        } else {
            wp_send_json_error(['mensaje' => __('Metodo no implementado', FLAVOR_PLATFORM_TEXT_DOMAIN)]);
        }
    }

    /**
     * AJAX: Obtener tipo de tramite
     */
    private function ajax_get_tipo_tramite() {
        $tipo_id = isset($_POST['tipo_id']) ? absint($_POST['tipo_id']) : 0;

        if (!$tipo_id) {
            return ['success' => false, 'mensaje' => __('ID de tipo requerido', FLAVOR_PLATFORM_TEXT_DOMAIN)];
        }

        $tipo = $this->get_tipo_tramite_completo($tipo_id);

        if (!$tipo) {
            return ['success' => false, 'mensaje' => __('Tipo no encontrado', FLAVOR_PLATFORM_TEXT_DOMAIN)];
        }

        return ['success' => true, 'tipo' => $tipo];
    }

    /**
     * AJAX: Crear expediente
     */
    private function ajax_crear_expediente() {
        $request = new WP_REST_Request('POST');
        $request->set_param('tipo_tramite_id', isset($_POST['tipo_tramite_id']) ? absint($_POST['tipo_tramite_id']) : 0);
        $request->set_param('nombre_solicitante', isset($_POST['nombre_solicitante']) ? sanitize_text_field($_POST['nombre_solicitante']) : '');
        $request->set_param('email_solicitante', isset($_POST['email_solicitante']) ? sanitize_email($_POST['email_solicitante']) : '');
        $request->set_param('telefono_solicitante', isset($_POST['telefono_solicitante']) ? sanitize_text_field($_POST['telefono_solicitante']) : '');
        $request->set_param('dni_solicitante', isset($_POST['dni_solicitante']) ? sanitize_text_field($_POST['dni_solicitante']) : '');
        $request->set_param('direccion_solicitante', isset($_POST['direccion_solicitante']) ? sanitize_textarea_field($_POST['direccion_solicitante']) : '');
        $request->set_param('datos_formulario', isset($_POST['datos_formulario']) ? json_decode(stripslashes($_POST['datos_formulario']), true) : []);
        $request->set_param('notas', isset($_POST['notas']) ? sanitize_textarea_field($_POST['notas']) : '');
        $request->set_param('borrador', isset($_POST['borrador']) && $_POST['borrador'] === 'true');
        $request->set_param('urgente', isset($_POST['urgente']) && $_POST['urgente'] === 'true');

        $response = $this->rest_create_expediente($request);

        if (is_wp_error($response)) {
            return ['success' => false, 'mensaje' => $response->get_error_message()];
        }

        return $response->get_data();
    }

    /**
     * AJAX: Listar expedientes
     */
    private function ajax_listar_expedientes() {
        if (!is_user_logged_in()) {
            return ['success' => false, 'mensaje' => __('ID de tipo requerido', FLAVOR_PLATFORM_TEXT_DOMAIN)];
        }

        $request = new WP_REST_Request('GET');
        $request->set_param('estado', isset($_POST['estado']) ? sanitize_text_field($_POST['estado']) : '');
        $request->set_param('pagina', isset($_POST['pagina']) ? absint($_POST['pagina']) : 1);
        $request->set_param('por_pagina', isset($_POST['por_pagina']) ? absint($_POST['por_pagina']) : 10);

        $response = $this->rest_get_expedientes($request);

        return $response->get_data();
    }

    /**
     * AJAX: Consultar estado
     */
    private function ajax_consultar_estado() {
        $numero = isset($_POST['numero_expediente']) ? sanitize_text_field($_POST['numero_expediente']) : '';

        if (empty($numero)) {
            return ['success' => false, 'mensaje' => __('ID de tipo requerido', FLAVOR_PLATFORM_TEXT_DOMAIN)];
        }

        $request = new WP_REST_Request('GET');
        $request->set_param('numero', $numero);

        $response = $this->rest_consulta_expediente($request);

        if (is_wp_error($response)) {
            return ['success' => false, 'mensaje' => $response->get_error_message()];
        }

        return $response->get_data();
    }

    // =========================================================================
    // SHORTCODES
    // =========================================================================

    /**
     * Shortcode: Catalogo de tramites
     */
    public function shortcode_catalogo_tramites($atts) {
        $atributos = shortcode_atts([
            'categoria' => '',
            'columnas' => 3,
            'mostrar_filtros' => 'true',
            'mostrar_buscador' => 'true',
            'limite' => 12,
        ], $atts);

        global $wpdb;

        $where = ["estado = 'activo'"];
        $valores = [];

        if (!empty($atributos['categoria'])) {
            $where[] = "categoria = %s";
            $valores[] = sanitize_text_field($atributos['categoria']);
        }

        if (!is_user_logged_in()) {
            $where[] = "visibilidad = 'publico'";
        }

        $sql = "SELECT id, nombre, descripcion, categoria, icono, color, plazo_resolucion_dias, requiere_cita, permite_online, permite_presencial, precio, estado, orden, created_at, requisitos, documentos_requeridos, tasa FROM {$this->tabla_tipos_tramite} WHERE " . implode(' AND ', $where) . " ORDER BY orden ASC, nombre ASC LIMIT %d";
        $valores[] = absint($atributos['limite']);

        $tipos = $wpdb->get_results($wpdb->prepare($sql, ...$valores));

        $categorias = $wpdb->get_col("SELECT DISTINCT categoria FROM {$this->tabla_tipos_tramite} WHERE estado = 'activo' AND categoria IS NOT NULL ORDER BY categoria");

        ob_start();
        ?>
        <div class="flavor-tramites-catalogo" data-columnas="<?php echo esc_attr($atributos['columnas']); ?>">
            <?php if ($atributos['mostrar_buscador'] === 'true' || $atributos['mostrar_filtros'] === 'true'): ?>
            <div class="flavor-tramites-filtros">
                <?php if ($atributos['mostrar_buscador'] === 'true'): ?>
                <div class="flavor-tramites-buscador">
                    <input type="text" id="flavor-buscar-tramite" placeholder="<?php esc_attr_e('Buscar tramite...', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>">
                    <span class="dashicons dashicons-search"></span>
                </div>
                <?php endif; ?>

                <?php if ($atributos['mostrar_filtros'] === 'true' && !empty($categorias)): ?>
                <div class="flavor-tramites-categorias">
                    <button class="flavor-categoria-btn active" data-categoria=""><?php esc_html_e('Todos', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></button>
                    <?php foreach ($categorias as $categoria): ?>
                    <button class="flavor-categoria-btn" data-categoria="<?php echo esc_attr($categoria); ?>"><?php echo esc_html($categoria); ?></button>
                    <?php endforeach; ?>
                </div>
                <?php endif; ?>
            </div>
            <?php endif; ?>

            <div class="flavor-tramites-grid">
                <?php if (empty($tipos)): ?>
                <p class="flavor-tramites-vacio"><?php esc_html_e('No hay tramites disponibles.', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></p>
                <?php else: ?>
                    <?php foreach ($tipos as $tipo): ?>
                    <div class="flavor-tramite-card" data-categoria="<?php echo esc_attr($tipo->categoria); ?>" data-id="<?php echo esc_attr($tipo->id); ?>">
                        <div class="flavor-tramite-icono" style="background-color: <?php echo esc_attr($tipo->color); ?>">
                            <span class="dashicons <?php echo esc_attr($tipo->icono); ?>"></span>
                        </div>
                        <div class="flavor-tramite-contenido">
                            <h3 class="flavor-tramite-titulo"><?php echo esc_html($tipo->nombre); ?></h3>
                            <p class="flavor-tramite-descripcion"><?php echo esc_html($tipo->descripcion_corta); ?></p>
                            <div class="flavor-tramite-meta">
                                <?php if ($tipo->plazo_resolucion_dias): ?>
                                <span class="flavor-tramite-plazo">
                                    <span class="dashicons dashicons-clock"></span>
                                    <?php echo esc_html($tipo->plazo_resolucion_dias); ?> <?php esc_html_e('dias', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                                </span>
                                <?php endif; ?>
                                <?php if ($tipo->tasa > 0): ?>
                                <span class="flavor-tramite-tasa">
                                    <span class="dashicons dashicons-money-alt"></span>
                                    <?php echo number_format($tipo->tasa, 2); ?> EUR
                                </span>
                                <?php endif; ?>
                            </div>
                        </div>
                        <a href="<?php echo esc_url(add_query_arg('tramite', $tipo->id, flavor_current_request_url())); ?>" class="flavor-tramite-enlace">
                            <?php esc_html_e('Iniciar tramite', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                            <span class="dashicons dashicons-arrow-right-alt"></span>
                        </a>
                    </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>
        <?php
        return ob_get_clean();
    }

    /**
     * Shortcode: Iniciar tramite
     */
    public function shortcode_iniciar_tramite($atts) {
        $atributos = shortcode_atts([
            'id' => 0,
        ], $atts);

        $tipo_id = !empty($atributos['id']) ? absint($atributos['id']) : (isset($_GET['tramite']) ? absint($_GET['tramite']) : 0);

        if (!$tipo_id) {
            return '<p class="flavor-tramites-error">' . esc_html__('No se ha especificado el tipo de tramite.', FLAVOR_PLATFORM_TEXT_DOMAIN) . '</p>';
        }

        $tipo = $this->get_tipo_tramite_completo($tipo_id);

        if (!$tipo) {
            return '<p class="flavor-tramites-error">' . esc_html__('Tramite no encontrado.', FLAVOR_PLATFORM_TEXT_DOMAIN) . '</p>';
        }

        if ($this->get_setting('requiere_login') && !is_user_logged_in()) {
            return '<div class="flavor-tramites-login-required">
                <p>' . esc_html__('Debes iniciar sesion para realizar este tramite.', FLAVOR_PLATFORM_TEXT_DOMAIN) . '</p>
                <a href="' . esc_url(wp_login_url(flavor_current_request_url())) . '" class="flavor-btn flavor-btn-primary">' . esc_html__('Iniciar sesion', FLAVOR_PLATFORM_TEXT_DOMAIN) . '</a>
            </div>';
        }

        $usuario_actual = wp_get_current_user();

        ob_start();
        ?>
        <div class="flavor-tramites-formulario" data-tipo-id="<?php echo esc_attr($tipo_id); ?>">
            <div class="flavor-tramite-cabecera">
                <div class="flavor-tramite-icono-grande" style="background-color: <?php echo esc_attr($tipo->color); ?>">
                    <span class="dashicons <?php echo esc_attr($tipo->icono); ?>"></span>
                </div>
                <div class="flavor-tramite-info">
                    <h2><?php echo esc_html($tipo->nombre); ?></h2>
                    <p><?php echo esc_html($tipo->descripcion); ?></p>
                </div>
            </div>

            <?php if (!empty($tipo->requisitos)): ?>
            <div class="flavor-tramite-requisitos">
                <h3><span class="dashicons dashicons-info-outline"></span> <?php esc_html_e('Requisitos', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></h3>
                <ul>
                    <?php foreach ($tipo->requisitos as $requisito): ?>
                    <li><?php echo esc_html($requisito); ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
            <?php endif; ?>

            <form id="flavor-form-tramite" class="flavor-form" enctype="multipart/form-data">
                <input type="hidden" name="tipo_tramite_id" value="<?php echo esc_attr($tipo_id); ?>">

                <div class="flavor-form-seccion">
                    <h3><?php esc_html_e('Datos del solicitante', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></h3>

                    <div class="flavor-form-row">
                        <div class="flavor-form-field flavor-field-half">
                            <label for="nombre_solicitante"><?php esc_html_e('Nombre completo', FLAVOR_PLATFORM_TEXT_DOMAIN); ?> *</label>
                            <input type="text" id="nombre_solicitante" name="nombre_solicitante" required value="<?php echo esc_attr($usuario_actual->display_name); ?>">
                        </div>
                        <div class="flavor-form-field flavor-field-half">
                            <label for="dni_solicitante"><?php esc_html_e('DNI/NIE/CIF', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></label>
                            <input type="text" id="dni_solicitante" name="dni_solicitante" pattern="[0-9A-Za-z]{8,12}">
                        </div>
                    </div>

                    <div class="flavor-form-row">
                        <div class="flavor-form-field flavor-field-half">
                            <label for="email_solicitante"><?php esc_html_e('Email', FLAVOR_PLATFORM_TEXT_DOMAIN); ?> *</label>
                            <input type="email" id="email_solicitante" name="email_solicitante" required value="<?php echo esc_attr($usuario_actual->user_email); ?>">
                        </div>
                        <div class="flavor-form-field flavor-field-half">
                            <label for="telefono_solicitante"><?php esc_html_e('Telefono', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></label>
                            <input type="tel" id="telefono_solicitante" name="telefono_solicitante">
                        </div>
                    </div>

                    <div class="flavor-form-field">
                        <label for="direccion_solicitante"><?php esc_html_e('Direccion', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></label>
                        <textarea id="direccion_solicitante" name="direccion_solicitante" rows="2"></textarea>
                    </div>
                </div>

                <?php if (!empty($tipo->campos_formulario)): ?>
                <div class="flavor-form-seccion">
                    <h3><?php esc_html_e('Datos del tramite', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></h3>
                    <?php echo $this->renderizar_campos_formulario($tipo->campos_formulario); ?>
                </div>
                <?php endif; ?>

                <?php if (!empty($tipo->documentos_requeridos)): ?>
                <div class="flavor-form-seccion">
                    <h3><?php esc_html_e('Documentacion', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></h3>
                    <p class="flavor-form-ayuda"><?php esc_html_e('Formatos permitidos:', FLAVOR_PLATFORM_TEXT_DOMAIN); ?> <?php echo esc_html($this->get_setting('tipos_archivo_permitidos')); ?></p>

                    <div class="flavor-documentos-requeridos">
                        <?php foreach ($tipo->documentos_requeridos as $doc): ?>
                        <div class="flavor-documento-item" data-tipo="<?php echo esc_attr($doc['codigo']); ?>">
                            <div class="flavor-documento-info">
                                <span class="flavor-documento-nombre">
                                    <?php echo esc_html($doc['nombre']); ?>
                                    <?php if (!empty($doc['obligatorio'])): ?>
                                    <span class="flavor-obligatorio">*</span>
                                    <?php endif; ?>
                                </span>
                                <?php if (!empty($doc['descripcion'])): ?>
                                <span class="flavor-documento-desc"><?php echo esc_html($doc['descripcion']); ?></span>
                                <?php endif; ?>
                            </div>
                            <div class="flavor-documento-upload">
                                <input type="file" name="documentos[<?php echo esc_attr($doc['codigo']); ?>]" class="flavor-file-input" <?php echo !empty($doc['obligatorio']) ? 'required' : ''; ?>>
                                <label class="flavor-file-label">
                                    <span class="dashicons dashicons-upload"></span>
                                    <?php esc_html_e('Seleccionar archivo', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                                </label>
                                <span class="flavor-file-name"></span>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>
                <?php endif; ?>

                <div class="flavor-form-seccion">
                    <h3><?php esc_html_e('Observaciones', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></h3>
                    <div class="flavor-form-field">
                        <textarea id="notas_solicitante" name="notas" rows="3" placeholder="<?php esc_attr_e('Informacion adicional que quieras aportar...', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>"></textarea>
                    </div>
                </div>

                <?php if ($tipo->tasa > 0): ?>
                <div class="flavor-tramite-tasa-info">
                    <span class="dashicons dashicons-info"></span>
                    <?php printf(esc_html__('Este tramite tiene una tasa de %s EUR', FLAVOR_PLATFORM_TEXT_DOMAIN), number_format($tipo->tasa, 2)); ?>
                </div>
                <?php endif; ?>

                <div class="flavor-form-acciones">
                    <button type="button" class="flavor-btn flavor-btn-secondary" id="flavor-guardar-borrador">
                        <span class="dashicons dashicons-edit"></span>
                        <?php esc_html_e('Guardar borrador', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                    </button>
                    <button type="submit" class="flavor-btn flavor-btn-primary">
                        <span class="dashicons dashicons-yes-alt"></span>
                        <?php esc_html_e('Enviar solicitud', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                    </button>
                </div>
            </form>

            <div class="flavor-tramite-resultado" style="display: none;">
                <div class="flavor-resultado-icono">
                    <span class="dashicons dashicons-yes-alt"></span>
                </div>
                <h3><?php esc_html_e('Solicitud enviada correctamente', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></h3>
                <p class="flavor-resultado-numero"></p>
                <p class="flavor-resultado-mensaje"><?php esc_html_e('Recibiras un email de confirmacion con los detalles.', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></p>
                <div class="flavor-resultado-acciones">
                    <a href="#" class="flavor-btn flavor-btn-secondary flavor-ver-expediente" onclick="flavorVerExpediente(this); return false;"><?php esc_html_e('Ver mi expediente', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></a>
                    <a href="<?php echo esc_url(remove_query_arg('tramite')); ?>" class="flavor-btn flavor-btn-outline"><?php esc_html_e('Iniciar otro tramite', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></a>
                </div>
                <script>
                function flavorVerExpediente(el) {
                    var contenedor = el.closest('.flavor-tramite-resultado');
                    var numeroEl = contenedor.querySelector('.flavor-resultado-numero');
                    if (numeroEl) {
                        var texto = numeroEl.textContent;
                        var match = texto.match(/EXP-\d{4}-\d+/);
                        if (match) {
                            var numero = match[0];
                            var url = new URL(window.location.href);
                            url.searchParams.delete('tramite');
                            url.searchParams.set('expediente', numero);
                            window.location.href = url.toString();
                        }
                    }
                }
                </script>
            </div>
        </div>
        <?php
        return ob_get_clean();
    }

    /**
     * Shortcode: Mis expedientes
     */
    public function shortcode_mis_expedientes($atts) {
        if (!is_user_logged_in()) {
            return '<div class="flavor-tramites-login-required">
                <p>' . esc_html__('Debes iniciar sesion para ver tus expedientes.', FLAVOR_PLATFORM_TEXT_DOMAIN) . '</p>
                <a href="' . esc_url(wp_login_url(flavor_current_request_url())) . '" class="flavor-btn flavor-btn-primary">' . esc_html__('Iniciar sesion', FLAVOR_PLATFORM_TEXT_DOMAIN) . '</a>
            </div>';
        }

        global $wpdb;

        $usuario_id = get_current_user_id();

        $expedientes = $wpdb->get_results($wpdb->prepare(
            "SELECT e.*, t.nombre as tipo_nombre, t.icono as tipo_icono, t.color as tipo_color,
                    es.nombre as estado_nombre, es.color as estado_color, es.icono as estado_icono
             FROM {$this->tabla_expedientes} e
             LEFT JOIN {$this->tabla_tipos_tramite} t ON e.tipo_tramite_id = t.id
             LEFT JOIN {$this->tabla_estados} es ON e.estado_actual = es.codigo
             WHERE e.user_id = %d
             ORDER BY e.fecha_creacion DESC
             LIMIT 50",
            $usuario_id
        ));

        ob_start();
        ?>
        <div class="flavor-mis-expedientes">
            <div class="flavor-expedientes-cabecera">
                <h2><?php esc_html_e('Mis expedientes', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></h2>
                <div class="flavor-expedientes-filtros">
                    <select id="flavor-filtro-estado">
                        <option value=""><?php esc_html_e('Todos los estados', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></option>
                        <option value="<?php echo esc_attr__('borrador', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>"><?php esc_html_e('Borrador', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></option>
                        <option value="<?php echo esc_attr__('pendiente', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>"><?php esc_html_e('Pendiente', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></option>
                        <option value="<?php echo esc_attr__('en_revision', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>"><?php esc_html_e('En revision', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></option>
                        <option value="<?php echo esc_attr__('en_tramite', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>"><?php esc_html_e('En tramite', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></option>
                        <option value="<?php echo esc_attr__('resuelto_favorable', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>"><?php esc_html_e('Resuelto favorable', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></option>
                        <option value="<?php echo esc_attr__('resuelto_desfavorable', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>"><?php esc_html_e('Resuelto desfavorable', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></option>
                    </select>
                </div>
            </div>

            <?php if (empty($expedientes)): ?>
            <div class="flavor-expedientes-vacio">
                <span class="dashicons dashicons-clipboard"></span>
                <p><?php esc_html_e('No tienes expedientes registrados.', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></p>
            </div>
            <?php else: ?>
            <div class="flavor-expedientes-lista">
                <?php foreach ($expedientes as $expediente): ?>
                <div class="flavor-expediente-item" data-estado="<?php echo esc_attr($expediente->estado_actual); ?>">
                    <div class="flavor-expediente-icono" style="background-color: <?php echo esc_attr($expediente->tipo_color ?: '#0073aa'); ?>">
                        <span class="dashicons <?php echo esc_attr($expediente->tipo_icono ?: 'dashicons-clipboard'); ?>"></span>
                    </div>
                    <div class="flavor-expediente-info">
                        <div class="flavor-expediente-numero"><?php echo esc_html($expediente->numero_expediente); ?></div>
                        <div class="flavor-expediente-tipo"><?php echo esc_html($expediente->tipo_nombre); ?></div>
                        <div class="flavor-expediente-fecha">
                            <?php echo esc_html(date_i18n('d/m/Y H:i', strtotime($expediente->fecha_creacion))); ?>
                        </div>
                    </div>
                    <div class="flavor-expediente-estado">
                        <span class="flavor-estado-badge" style="background-color: <?php echo esc_attr($expediente->estado_color ?: '#666'); ?>">
                            <span class="dashicons <?php echo esc_attr($expediente->estado_icono ?: 'dashicons-marker'); ?>"></span>
                            <?php echo esc_html($expediente->estado_nombre); ?>
                        </span>
                    </div>
                    <div class="flavor-expediente-acciones">
                        <a href="<?php echo esc_url(add_query_arg('expediente', $expediente->numero_expediente, flavor_current_request_url())); ?>" class="flavor-btn flavor-btn-small">
                            <?php esc_html_e('Ver detalle', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                        </a>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>
        </div>
        <?php
        return ob_get_clean();
    }

    /**
     * Shortcode: Estado de expediente
     */
    public function shortcode_estado_expediente($atts) {
        $numero = isset($_GET['expediente']) ? sanitize_text_field($_GET['expediente']) : '';

        ob_start();
        ?>
        <div class="flavor-estado-expediente">
            <div class="flavor-consulta-estado">
                <h2><?php esc_html_e('Consultar estado de expediente', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></h2>
                <form id="flavor-form-consulta" class="flavor-form-inline">
                    <input type="text" id="flavor-numero-expediente" name="numero" placeholder="<?php esc_attr_e('Numero de expediente (ej: EXP-2024-00001)', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>" value="<?php echo esc_attr($numero); ?>">
                    <button type="submit" class="flavor-btn flavor-btn-primary">
                        <span class="dashicons dashicons-search"></span>
                        <?php esc_html_e('Consultar', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                    </button>
                </form>
            </div>

            <div class="flavor-expediente-detalle" style="display: none;">
                <div class="flavor-detalle-cabecera">
                    <div class="flavor-detalle-numero"></div>
                    <div class="flavor-detalle-estado"></div>
                </div>

                <div class="flavor-detalle-info">
                    <div class="flavor-info-item">
                        <span class="flavor-info-label"><?php esc_html_e('Tipo de tramite', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></span>
                        <span class="flavor-info-valor flavor-detalle-tipo"></span>
                    </div>
                    <div class="flavor-info-item">
                        <span class="flavor-info-label"><?php esc_html_e('Fecha de inicio', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></span>
                        <span class="flavor-info-valor flavor-detalle-fecha"></span>
                    </div>
                    <div class="flavor-info-item">
                        <span class="flavor-info-label"><?php esc_html_e('Fecha limite', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></span>
                        <span class="flavor-info-valor flavor-detalle-limite"></span>
                    </div>
                </div>

                <div class="flavor-timeline-container">
                    <h3><?php esc_html_e('Historico del expediente', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></h3>
                    <div class="flavor-timeline"></div>
                </div>
            </div>

            <div class="flavor-expediente-no-encontrado" style="display: none;">
                <span class="dashicons dashicons-warning"></span>
                <p><?php esc_html_e('No se ha encontrado ningun expediente con ese numero.', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></p>
            </div>
        </div>
        <?php
        return ob_get_clean();
    }

    // =========================================================================
    // METODOS AUXILIARES
    // =========================================================================

    /**
     * Obtener tipo de tramite
     */
    private function get_tipo_tramite($tipo_id) {
        global $wpdb;
        return $wpdb->get_row($wpdb->prepare(
            "SELECT id, nombre, descripcion, categoria, icono, color, plazo_resolucion_dias, requiere_cita, permite_online, permite_presencial, precio, estado, orden, created_at, requisitos, documentos_requeridos, tasa
             FROM {$this->tabla_tipos_tramite}
             WHERE id = %d",
            $tipo_id
        ));
    }

    /**
     * Obtener tipo de tramite completo con campos
     */
    private function get_tipo_tramite_completo($tipo_id) {
        global $wpdb;

        $tipo = $wpdb->get_row($wpdb->prepare(
            "SELECT id, nombre, descripcion, categoria, icono, color, plazo_resolucion_dias, requiere_cita, permite_online, permite_presencial, precio, estado, orden, created_at, requisitos, documentos_requeridos, tasa
             FROM {$this->tabla_tipos_tramite}
             WHERE id = %d AND estado = 'activo'",
            $tipo_id
        ));

        if (!$tipo) {
            return null;
        }

        $campos = $wpdb->get_results($wpdb->prepare(
            "SELECT id, tipo_tramite_id, nombre_campo, etiqueta, tipo_campo, opciones, requerido, placeholder, ayuda, orden, grupo, ancho, clase_css, condicion_visible, valor_defecto, patron_validacion, mensaje_error, es_readonly, es_obligatorio, activo
             FROM {$this->tabla_campos_formulario}
             WHERE tipo_tramite_id = %d AND activo = 1 ORDER BY orden ASC",
            $tipo_id
        ));

        foreach ($campos as &$campo) {
            if ($campo->opciones) {
                $campo->opciones = json_decode($campo->opciones, true);
            }
            if ($campo->condicion_visible) {
                $campo->condicion_visible = json_decode($campo->condicion_visible, true);
            }
        }

        $tipo->requisitos = $tipo->requisitos ? json_decode($tipo->requisitos, true) : [];
        $tipo->documentos_requeridos = $tipo->documentos_requeridos ? json_decode($tipo->documentos_requeridos, true) : [];
        $tipo->campos_formulario = $campos;

        return $tipo;
    }

    /**
     * Obtener expediente
     */
    private function get_expediente($expediente_id) {
        global $wpdb;
        return $wpdb->get_row($wpdb->prepare(
            "SELECT id, numero_expediente, tipo_tramite_id, user_id, solicitante_id, estado_actual, via_tramitacion, datos_formulario, observaciones, fecha_solicitud, fecha_creacion, fecha_resolucion, fecha_limite, asignado_a, prioridad, created_at, updated_at
             FROM {$this->tabla_expedientes}
             WHERE id = %d",
            $expediente_id
        ));
    }

    /**
     * Obtener expediente completo
     */
    private function get_expediente_completo($expediente_id) {
        global $wpdb;

        $expediente = $wpdb->get_row($wpdb->prepare(
            "SELECT e.*, t.nombre as tipo_nombre, t.icono as tipo_icono, t.color as tipo_color,
                    es.nombre as estado_nombre, es.color as estado_color, es.icono as estado_icono,
                    es.permite_edicion, es.permite_documentos
             FROM {$this->tabla_expedientes} e
             LEFT JOIN {$this->tabla_tipos_tramite} t ON e.tipo_tramite_id = t.id
             LEFT JOIN {$this->tabla_estados} es ON e.estado_actual = es.codigo
             WHERE e.id = %d",
            $expediente_id
        ));

        if (!$expediente) {
            return null;
        }

        $expediente->datos_formulario = $expediente->datos_formulario ? json_decode($expediente->datos_formulario, true) : [];

        $expediente->documentos = $wpdb->get_results($wpdb->prepare(
            "SELECT id, expediente_id, nombre_archivo, ruta_archivo, tipo_documento, tamano_bytes, subido_por, fecha_subida, visible_solicitante
             FROM {$this->tabla_documentos}
             WHERE expediente_id = %d AND visible_solicitante = 1 ORDER BY fecha_subida ASC",
            $expediente_id
        ));

        $expediente->historial = $wpdb->get_results($wpdb->prepare(
            "SELECT id, expediente_id, usuario_id, accion, descripcion, metadata, fecha, es_publico, fecha_evento
             FROM {$this->tabla_historial}
             WHERE expediente_id = %d AND es_publico = 1 ORDER BY fecha_evento DESC",
            $expediente_id
        ));

        return $expediente;
    }

    /**
     * Obtener estado
     */
    private function get_estado($codigo) {
        global $wpdb;
        return $wpdb->get_row($wpdb->prepare(
            "SELECT codigo, nombre, descripcion, color, icono, es_inicial, es_final, permite_edicion, permite_documentos, notifica_solicitante, orden, activo
             FROM {$this->tabla_estados}
             WHERE codigo = %s",
            $codigo
        ));
    }

    /**
     * Generar numero de expediente
     */
    private function generar_numero_expediente() {
        global $wpdb;

        $prefijo = $this->get_setting('prefijo_expediente');
        $anio = date('Y');

        $ultimo_numero = $wpdb->get_var($wpdb->prepare(
            "SELECT MAX(CAST(SUBSTRING_INDEX(numero_expediente, '-', -1) AS UNSIGNED))
             FROM {$this->tabla_expedientes}
             WHERE numero_expediente LIKE %s",
            $prefijo . '-' . $anio . '-%'
        ));

        $siguiente_numero = ($ultimo_numero ?: 0) + 1;

        return sprintf('%s-%s-%05d', $prefijo, $anio, $siguiente_numero);
    }

    /**
     * Obtener o crear ID de sesion
     */
    private function get_or_create_session_id() {
        if (isset($_COOKIE['flavor_tramites_session'])) {
            return sanitize_text_field($_COOKIE['flavor_tramites_session']);
        }

        $session_id = wp_generate_uuid4();
        setcookie('flavor_tramites_session', $session_id, time() + (86400 * 30), '/');

        return $session_id;
    }

    /**
     * Obtener IP del cliente
     */
    private function get_client_ip() {
        $claves_ip = ['HTTP_CF_CONNECTING_IP', 'HTTP_X_FORWARDED_FOR', 'HTTP_X_REAL_IP', 'REMOTE_ADDR'];

        foreach ($claves_ip as $clave) {
            if (!empty($_SERVER[$clave])) {
                $ip = sanitize_text_field($_SERVER[$clave]);
                if (strpos($ip, ',') !== false) {
                    $ip = trim(explode(',', $ip)[0]);
                }
                if (filter_var($ip, FILTER_VALIDATE_IP)) {
                    return $ip;
                }
            }
        }

        return '0.0.0.0';
    }

    /**
     * Validar campos del formulario
     */
    private function validar_campos_formulario($tipo_id, $datos) {
        global $wpdb;

        $campos = $wpdb->get_results($wpdb->prepare(
            "SELECT id, tipo_tramite_id, nombre_campo, etiqueta, tipo_campo, opciones, requerido, placeholder, ayuda, orden, grupo, ancho, clase_css, condicion_visible, valor_defecto, patron_validacion, mensaje_error, es_readonly, es_obligatorio, activo
             FROM {$this->tabla_campos_formulario}
             WHERE tipo_tramite_id = %d AND activo = 1",
            $tipo_id
        ));

        $errores = [];

        foreach ($campos as $campo) {
            $valor = isset($datos[$campo->nombre_campo]) ? $datos[$campo->nombre_campo] : null;

            if ($campo->es_obligatorio && (empty($valor) && $valor !== '0')) {
                $errores[$campo->nombre_campo] = sprintf(__('El campo %s es obligatorio', FLAVOR_PLATFORM_TEXT_DOMAIN), $campo->etiqueta);
                continue;
            }

            if (!empty($valor) && !empty($campo->patron_validacion)) {
                if (!preg_match('/' . $campo->patron_validacion . '/', $valor)) {
                    $errores[$campo->nombre_campo] = $campo->mensaje_error ?: sprintf(__('El campo %s no tiene un formato valido', FLAVOR_PLATFORM_TEXT_DOMAIN), $campo->etiqueta);
                }
            }

            if (!empty($valor)) {
                switch ($campo->tipo_campo) {
                    case 'email':
                        if (!is_email($valor)) {
                            $errores[$campo->nombre_campo] = __('Email no valido', FLAVOR_PLATFORM_TEXT_DOMAIN);
                        }
                        break;
                    case 'dni':
                        if (!$this->validar_dni_nie($valor)) {
                            $errores[$campo->nombre_campo] = __('DNI/NIE no valido', FLAVOR_PLATFORM_TEXT_DOMAIN);
                        }
                        break;
                    case 'iban':
                        if (!$this->validar_iban($valor)) {
                            $errores[$campo->nombre_campo] = __('IBAN no valido', FLAVOR_PLATFORM_TEXT_DOMAIN);
                        }
                        break;
                }
            }
        }

        return $errores;
    }

    /**
     * Validar DNI/NIE
     */
    private function validar_dni_nie($documento) {
        $documento = strtoupper(trim($documento));

        if (preg_match('/^[0-9]{8}[A-Z]$/', $documento)) {
            $letras = 'TRWAGMYFPDXBNJZSQVHLCKE';
            $numero = intval(substr($documento, 0, 8));
            return $documento[8] === $letras[$numero % 23];
        }

        if (preg_match('/^[XYZ][0-9]{7}[A-Z]$/', $documento)) {
            $reemplazos = ['X' => '0', 'Y' => '1', 'Z' => '2'];
            $numero = $reemplazos[$documento[0]] . substr($documento, 1, 7);
            $letras = 'TRWAGMYFPDXBNJZSQVHLCKE';
            return $documento[8] === $letras[intval($numero) % 23];
        }

        return false;
    }

    /**
     * Validar IBAN
     */
    private function validar_iban($iban) {
        $iban = strtoupper(preg_replace('/\s+/', '', $iban));

        if (!preg_match('/^[A-Z]{2}[0-9]{2}[A-Z0-9]{4,30}$/', $iban)) {
            return false;
        }

        $iban_reorganizado = substr($iban, 4) . substr($iban, 0, 4);
        $iban_numerico = '';

        for ($i = 0; $i < strlen($iban_reorganizado); $i++) {
            $caracter = $iban_reorganizado[$i];
            if (ctype_alpha($caracter)) {
                $iban_numerico .= (ord($caracter) - 55);
            } else {
                $iban_numerico .= $caracter;
            }
        }

        return bcmod($iban_numerico, '97') === '1';
    }

    /**
     * Renderizar campos del formulario
     */
    private function renderizar_campos_formulario($campos) {
        $html = '';
        $grupo_actual = '';

        foreach ($campos as $campo) {
            if ($campo->grupo && $campo->grupo !== $grupo_actual) {
                if ($grupo_actual !== '') {
                    $html .= '</div>';
                }
                $html .= '<div class="flavor-form-grupo" data-grupo="' . esc_attr($campo->grupo) . '">';
                $html .= '<h4>' . esc_html($campo->grupo) . '</h4>';
                $grupo_actual = $campo->grupo;
            }

            $clase_ancho = 'flavor-field-' . ($campo->ancho ?: 'full');
            $atributos_condicion = '';

            if ($campo->condicion_visible) {
                $atributos_condicion = ' data-condicion="' . esc_attr(wp_json_encode($campo->condicion_visible)) . '"';
            }

            $html .= '<div class="flavor-form-field ' . esc_attr($clase_ancho) . ' ' . esc_attr($campo->clase_css) . '"' . $atributos_condicion . '>';
            $html .= '<label for="campo_' . esc_attr($campo->nombre_campo) . '">';
            $html .= esc_html($campo->etiqueta);
            if ($campo->es_obligatorio) {
                $html .= ' <span class="flavor-obligatorio">*</span>';
            }
            $html .= '</label>';

            $atributos_campo = 'id="campo_' . esc_attr($campo->nombre_campo) . '" name="datos_formulario[' . esc_attr($campo->nombre_campo) . ']"';

            if ($campo->es_obligatorio) {
                $atributos_campo .= ' required';
            }
            if ($campo->es_readonly) {
                $atributos_campo .= ' readonly';
            }
            if ($campo->placeholder) {
                $atributos_campo .= ' placeholder="' . esc_attr($campo->placeholder) . '"';
            }
            if ($campo->patron_validacion) {
                $atributos_campo .= ' pattern="' . esc_attr($campo->patron_validacion) . '"';
            }

            switch ($campo->tipo_campo) {
                case 'textarea':
                    $html .= '<textarea ' . $atributos_campo . ' rows="4">' . esc_textarea($campo->valor_defecto) . '</textarea>';
                    break;

                case 'select':
                    $html .= '<select ' . $atributos_campo . '>';
                    $html .= '<option value="">' . esc_html__('Seleccionar...', FLAVOR_PLATFORM_TEXT_DOMAIN) . '</option>';
                    if (is_array($campo->opciones)) {
                        foreach ($campo->opciones as $opcion) {
                            $valor_opcion = is_array($opcion) ? $opcion['valor'] : $opcion;
                            $texto_opcion = is_array($opcion) ? $opcion['texto'] : $opcion;
                            $seleccionado = $campo->valor_defecto === $valor_opcion ? ' selected' : '';
                            $html .= '<option value="' . esc_attr($valor_opcion) . '"' . $seleccionado . '>' . esc_html($texto_opcion) . '</option>';
                        }
                    }
                    $html .= '</select>';
                    break;

                case 'radio':
                    $html .= '<div class="flavor-radio-group">';
                    if (is_array($campo->opciones)) {
                        foreach ($campo->opciones as $indice => $opcion) {
                            $valor_opcion = is_array($opcion) ? $opcion['valor'] : $opcion;
                            $texto_opcion = is_array($opcion) ? $opcion['texto'] : $opcion;
                            $marcado = $campo->valor_defecto === $valor_opcion ? ' checked' : '';
                            $html .= '<label class="flavor-radio-label">';
                            $html .= '<input type="radio" name="datos_formulario[' . esc_attr($campo->nombre_campo) . ']" value="' . esc_attr($valor_opcion) . '"' . $marcado . '>';
                            $html .= ' ' . esc_html($texto_opcion);
                            $html .= '</label>';
                        }
                    }
                    $html .= '</div>';
                    break;

                case 'checkbox':
                    $marcado = $campo->valor_defecto ? ' checked' : '';
                    $html .= '<label class="flavor-checkbox-label">';
                    $html .= '<input type="checkbox" ' . $atributos_campo . ' value="1"' . $marcado . '>';
                    $html .= ' ' . esc_html($campo->etiqueta);
                    $html .= '</label>';
                    break;

                case 'date':
                    $html .= '<input type="date" ' . $atributos_campo . ' value="' . esc_attr($campo->valor_defecto) . '">';
                    break;

                case 'datetime':
                    $html .= '<input type="datetime-local" ' . $atributos_campo . ' value="' . esc_attr($campo->valor_defecto) . '">';
                    break;

                case 'number':
                    $html .= '<input type="number" ' . $atributos_campo . ' value="' . esc_attr($campo->valor_defecto) . '">';
                    break;

                case 'file':
                    $html .= '<input type="file" ' . $atributos_campo . ' class="flavor-file-input">';
                    break;

                case 'hidden':
                    $html .= '<input type="hidden" ' . $atributos_campo . ' value="' . esc_attr($campo->valor_defecto) . '">';
                    break;

                default:
                    $tipo_input = in_array($campo->tipo_campo, ['email', 'tel', 'url']) ? $campo->tipo_campo : 'text';
                    $html .= '<input type="' . esc_attr($tipo_input) . '" ' . $atributos_campo . ' value="' . esc_attr($campo->valor_defecto) . '">';
            }

            if ($campo->ayuda) {
                $html .= '<span class="flavor-field-ayuda">' . esc_html($campo->ayuda) . '</span>';
            }

            $html .= '</div>';
        }

        if ($grupo_actual !== '') {
            $html .= '</div>';
        }

        return $html;
    }

    /**
     * Registrar evento en historial
     */
    private function registrar_historial($expediente_id, $tipo_evento, $estado_anterior, $estado_nuevo, $descripcion, $datos_adicionales = null, $es_publico = true) {
        global $wpdb;

        $usuario_id = get_current_user_id();
        $nombre_usuario = $usuario_id ? wp_get_current_user()->display_name : __('Sistema', FLAVOR_PLATFORM_TEXT_DOMAIN);

        $wpdb->insert($this->tabla_historial, [
            'expediente_id' => $expediente_id,
            'tipo_evento' => $tipo_evento,
            'estado_anterior' => $estado_anterior,
            'estado_nuevo' => $estado_nuevo,
            'descripcion' => $descripcion,
            'datos_adicionales' => $datos_adicionales ? wp_json_encode($datos_adicionales) : null,
            'es_publico' => $es_publico ? 1 : 0,
            'usuario_id' => $usuario_id ?: null,
            'nombre_usuario' => $nombre_usuario,
            'ip_origen' => $this->get_client_ip(),
            'fecha_evento' => current_time('mysql'),
        ]);
    }

    /**
     * Enviar notificacion de inicio
     */
    private function enviar_notificacion_inicio($expediente_id) {
        $expediente = $this->get_expediente_completo($expediente_id);

        if (!$expediente || empty($expediente->email_solicitante)) {
            return false;
        }

        $asunto = sprintf(__('Confirmacion de tramite - %s', FLAVOR_PLATFORM_TEXT_DOMAIN), $expediente->numero_expediente);

        $mensaje = sprintf(
            __("Estimado/a %s,\n\nSu solicitud ha sido registrada correctamente.\n\nNumero de expediente: %s\nTipo de tramite: %s\nFecha de registro: %s\n\nPuede consultar el estado de su expediente en cualquier momento.\n\nSaludos.", FLAVOR_PLATFORM_TEXT_DOMAIN),
            $expediente->nombre_solicitante,
            $expediente->numero_expediente,
            $expediente->tipo_nombre,
            date_i18n('d/m/Y H:i', strtotime($expediente->fecha_creacion))
        );

        $headers = ['Content-Type: text/plain; charset=UTF-8'];

        return wp_mail($expediente->email_solicitante, $asunto, $mensaje, $headers);
    }

    // =========================================================================
    // ACCIONES DEL MODULO (COMPATIBILIDAD)
    // =========================================================================

    /**
     * {@inheritdoc}
     */
    public function get_actions() {
        return [
            'listar_tramites' => [
                'description' => 'Listar tipos de tramites disponibles',
                'params' => ['categoria', 'busqueda'],
            ],
            'detalle_tramite' => [
                'description' => 'Ver detalles de un tipo de tramite',
                'params' => ['tramite_id'],
            ],
            'crear_expediente' => [
                'description' => 'Crear un nuevo expediente',
                'params' => ['tipo_tramite_id', 'datos_formulario'],
            ],
            'mis_expedientes' => [
                'description' => 'Ver mis expedientes',
                'params' => ['estado'],
            ],
            'estado_expediente' => [
                'description' => 'Consultar estado de un expediente',
                'params' => ['numero_expediente'],
            ],
            'cancelar_expediente' => [
                'description' => 'Cancelar un expediente en borrador',
                'params' => ['expediente_id'],
            ],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function execute_action($action_name, $params) {
        $aliases = [
            'listar' => 'listar_tramites',
            'listado' => 'listar_tramites',
            'explorar' => 'listar_tramites',
            'buscar' => 'listar_tramites',
            'detalle' => 'detalle_tramite',
            'ver' => 'detalle_tramite',
            'estado' => 'estado_expediente',
            'seguimiento' => 'estado_expediente',
        ];

        $action_name = $aliases[$action_name] ?? $action_name;
        $metodo = 'action_' . $action_name;

        if (method_exists($this, $metodo)) {
            return $this->$metodo($params);
        }

        return [
            'success' => false,
            'error' => "Accion no implementada: {$action_name}",
        ];
    }

    /**
     * Accion: Listar tramites
     */
    private function action_listar_tramites($params) {
        $request = new WP_REST_Request('GET');
        $request->set_param('categoria', isset($params['categoria']) ? $params['categoria'] : '');
        $request->set_param('busqueda', isset($params['busqueda']) ? $params['busqueda'] : '');

        $response = $this->rest_get_tipos_tramite($request);
        return $response->get_data();
    }

    /**
     * Accion: Detalle tramite
     */
    private function action_detalle_tramite($params) {
        if (empty($params['tramite_id'])) {
            return ['success' => false, 'error' => __('Se requiere el ID del tramite', FLAVOR_PLATFORM_TEXT_DOMAIN)];
        }

        $request = new WP_REST_Request('GET');
        $request->set_param('id', $params['tramite_id']);

        $response = $this->rest_get_tipo_tramite($request);

        if (is_wp_error($response)) {
            return ['success' => false, 'error' => $response->get_error_message()];
        }

        return $response->get_data();
    }

    /**
     * Accion: Estado expediente
     */
    private function action_estado_expediente($params) {
        if (empty($params['numero_expediente'])) {
            return ['success' => false, 'error' => __('Se requiere el ID del tramite', FLAVOR_PLATFORM_TEXT_DOMAIN)];
        }

        $request = new WP_REST_Request('GET');
        $request->set_param('numero', $params['numero_expediente']);

        $response = $this->rest_consulta_expediente($request);

        if (is_wp_error($response)) {
            return ['success' => false, 'error' => $response->get_error_message()];
        }

        return $response->get_data();
    }

    // =========================================================================
    // COMPONENTES WEB Y CONOCIMIENTO
    // =========================================================================

    /**
     * {@inheritdoc}
     */
    public function get_web_components() {
        return [
            'catalogo_tramites' => [
                'label' => __('Catalogo de Tramites', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'category' => 'listings',
                'icon' => 'dashicons-grid-view',
                'fields' => [
                    'titulo' => ['type' => 'text', 'default' => __('Tramites Disponibles', FLAVOR_PLATFORM_TEXT_DOMAIN)],
                    'columnas' => ['type' => 'select', 'options' => [2, 3, 4], 'default' => 3],
                    'categoria' => ['type' => 'text', 'default' => ''],
                    'mostrar_filtros' => ['type' => 'toggle', 'default' => true],
                ],
                'shortcode' => 'catalogo_tramites',
            ],
            'formulario_tramite' => [
                'label' => __('Formulario de Tramite', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'category' => 'forms',
                'icon' => 'dashicons-welcome-write-blog',
                'fields' => [
                    'tramite_id' => ['type' => 'number', 'default' => 0],
                ],
                'shortcode' => 'iniciar_tramite',
            ],
            'mis_expedientes' => [
                'label' => __('Mis Expedientes', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'category' => 'user',
                'icon' => 'dashicons-portfolio',
                'shortcode' => 'mis_expedientes',
            ],
            'consulta_estado' => [
                'label' => __('Consultar Estado', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'category' => 'widgets',
                'icon' => 'dashicons-search',
                'shortcode' => 'estado_expediente',
            ],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function get_tool_definitions() {
        return [
            [
                'name' => 'listar_tramites',
                'description' => 'Ver tipos de tramites disponibles',
                'input_schema' => [
                    'type' => 'object',
                    'properties' => [
                        'categoria' => ['type' => 'string', 'description' => 'Filtrar por categoria'],
                        'busqueda' => ['type' => 'string', 'description' => 'Termino de busqueda'],
                    ],
                ],
            ],
            [
                'name' => 'detalle_tramite',
                'description' => 'Ver detalles y requisitos de un tramite',
                'input_schema' => [
                    'type' => 'object',
                    'properties' => [
                        'tramite_id' => ['type' => 'integer', 'description' => 'ID del tipo de tramite'],
                    ],
                    'required' => ['tramite_id'],
                ],
            ],
            [
                'name' => 'estado_expediente',
                'description' => 'Consultar estado de un expediente',
                'input_schema' => [
                    'type' => 'object',
                    'properties' => [
                        'numero_expediente' => ['type' => 'string', 'description' => 'Numero de expediente'],
                    ],
                    'required' => ['numero_expediente'],
                ],
            ],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function get_knowledge_base() {
        return <<<KNOWLEDGE
**Sistema de Tramites Online**

Plataforma completa para la gestion de tramites administrativos de forma electronica.

**Funcionalidades principales:**
- Catalogo de tramites con buscador y filtros
- Formularios dinamicos configurables por tipo de tramite
- Subida de documentacion con validacion de formatos
- Seguimiento de expedientes con timeline de estados
- Notificaciones automaticas por email
- Consulta publica de estado por numero de expediente

**Proceso de tramitacion:**
1. Consultar el catalogo de tramites disponibles
2. Seleccionar el tramite y revisar requisitos
3. Cumplimentar el formulario con los datos requeridos
4. Adjuntar la documentacion necesaria
5. Enviar la solicitud y obtener numero de expediente
6. Seguir el estado del expediente hasta su resolucion

**Estados de un expediente:**
- Borrador: Solicitud guardada sin enviar
- Pendiente: Enviada, en espera de revision
- En revision: Siendo evaluada por el departamento
- Subsanacion: Requiere documentacion adicional
- En tramite: Tramitandose activamente
- Resuelto favorable: Aprobado
- Resuelto desfavorable: Denegado
- Archivado: Expediente cerrado

**Tipos de documentos aceptados:**
- PDF, JPG, JPEG, PNG para documentos escaneados
- DOC, DOCX para formularios editables

**Informacion importante:**
- Los expedientes tienen plazos de resolucion segun el tipo de tramite
- Puede haber tasas asociadas a determinados tramites
- Es posible guardar borradores y continuar mas tarde
- Las notificaciones de cambio de estado se envian por email
KNOWLEDGE;
    }

    /**
     * {@inheritdoc}
     */
    public function get_faqs() {
        return [
            [
                'pregunta' => 'Como puedo iniciar un tramite online?',
                'respuesta' => 'Accede al catalogo de tramites, selecciona el que necesitas, revisa los requisitos, rellena el formulario y adjunta la documentacion requerida.',
            ],
            [
                'pregunta' => 'Donde puedo consultar el estado de mi expediente?',
                'respuesta' => 'Puedes consultar el estado introduciendo tu numero de expediente en el buscador de estado, o accediendo a "Mis expedientes" si estas registrado.',
            ],
            [
                'pregunta' => 'Que documentos puedo adjuntar?',
                'respuesta' => 'Se aceptan archivos PDF, imagenes (JPG, PNG) y documentos Word (DOC, DOCX). El tamanio maximo por archivo es de 10MB.',
            ],
            [
                'pregunta' => 'Puedo guardar un tramite y continuarlo despues?',
                'respuesta' => 'Si, puedes guardar un borrador y retomarlo mas tarde desde "Mis expedientes".',
            ],
            [
                'pregunta' => 'Como recibo las notificaciones?',
                'respuesta' => 'Las notificaciones se envian automaticamente al email que proporcionaste al crear el expediente cuando hay cambios de estado.',
            ],
        ];
    }

    public function public_permission_check($request) {
        $method = strtoupper($request->get_method());
        $tipo = in_array($method, ['POST', 'PUT', 'DELETE'], true) ? 'post' : 'get';
        return Flavor_API_Rate_Limiter::check_rate_limit($tipo);
    }

    /**
     * Crea páginas frontend automáticamente
     */
    public function maybe_create_pages() {
        if (!class_exists('Flavor_Page_Creator')) {
            return;
        }

        // En admin: refrescar páginas del módulo
        if (is_admin()) {
            Flavor_Page_Creator::refresh_module_pages('tramites');
            return;
        }

        // En frontend: crear páginas si no existen
        $pagina = get_page_by_path('tramites');
        if (!$pagina && !get_option('flavor_tramites_pages_created')) {
            Flavor_Page_Creator::create_pages_for_modules(['tramites']);
            update_option('flavor_tramites_pages_created', 1, false);
        }
    }

    /**
     * Define las páginas del módulo (Page Creator V3)
     *
     * @return array Definiciones de páginas
     */
    public function get_pages_definition() {
        return [
            [
                'title' => __('Trámites Online', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'slug' => 'tramites',
                'content' => '<h1>' . __('Trámites Online', FLAVOR_PLATFORM_TEXT_DOMAIN) . '</h1>
<p>' . __('Realiza tus trámites de forma rápida y sencilla. Consulta el catálogo de servicios disponibles, inicia solicitudes y haz seguimiento de tus expedientes.', FLAVOR_PLATFORM_TEXT_DOMAIN) . '</p>

[flavor_module_listing module="tramites" action="listar" columnas="3" limite="12"]',
                'parent' => 0,
            ],
            [
                'title' => __('Iniciar Trámite', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'slug' => 'iniciar',
                'content' => '<h1>' . __('Iniciar Trámite', FLAVOR_PLATFORM_TEXT_DOMAIN) . '</h1>
<p>' . __('Selecciona el tipo de trámite que deseas realizar, completa el formulario con la información requerida y adjunta la documentación necesaria.', FLAVOR_PLATFORM_TEXT_DOMAIN) . '</p>

[flavor_module_listing module="tramites" action="crear"]',
                'parent' => 'tramites',
            ],
            [
                'title' => __('Mis Trámites', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'slug' => 'mis-tramites',
                'content' => '<h1>' . __('Mis Trámites', FLAVOR_PLATFORM_TEXT_DOMAIN) . '</h1>
<p>' . __('Consulta el estado de tus expedientes, revisa las notificaciones y gestiona tus solicitudes en curso.', FLAVOR_PLATFORM_TEXT_DOMAIN) . '</p>

[flavor_module_listing module="tramites" action="mis_items"]',
                'parent' => 'tramites',
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
            'module'   => 'tramites',
            'title'    => __('Trámites Online', FLAVOR_PLATFORM_TEXT_DOMAIN),
            'subtitle' => __('Realiza gestiones administrativas de forma rápida y sencilla', FLAVOR_PLATFORM_TEXT_DOMAIN),
            'icon'     => '📋',
            'color'    => 'secondary', // Usa variable CSS --flavor-secondary del tema

            'database' => [
                'table'       => 'flavor_tramites',
                'primary_key' => 'id',
            ],

            'fields' => [
                'tipo'          => ['type' => 'select', 'label' => __('Tipo de trámite', FLAVOR_PLATFORM_TEXT_DOMAIN), 'required' => true],
                'titulo'        => ['type' => 'text', 'label' => __('Asunto', FLAVOR_PLATFORM_TEXT_DOMAIN), 'required' => true],
                'descripcion'   => ['type' => 'textarea', 'label' => __('Descripción', FLAVOR_PLATFORM_TEXT_DOMAIN)],
                'documentos'    => ['type' => 'file', 'label' => __('Documentación', FLAVOR_PLATFORM_TEXT_DOMAIN), 'multiple' => true],
                'expediente'    => ['type' => 'text', 'label' => __('Nº Expediente', FLAVOR_PLATFORM_TEXT_DOMAIN), 'readonly' => true],
                'fecha_inicio'  => ['type' => 'date', 'label' => __('Fecha inicio', FLAVOR_PLATFORM_TEXT_DOMAIN)],
                'fecha_resolucion' => ['type' => 'date', 'label' => __('Fecha resolución', FLAVOR_PLATFORM_TEXT_DOMAIN)],
            ],

            'estados' => [
                'borrador'    => ['label' => __('Borrador', FLAVOR_PLATFORM_TEXT_DOMAIN), 'color' => 'gray', 'icon' => '📝'],
                'presentado'  => ['label' => __('Presentado', FLAVOR_PLATFORM_TEXT_DOMAIN), 'color' => 'blue', 'icon' => '📤'],
                'en_tramite'  => ['label' => __('En trámite', FLAVOR_PLATFORM_TEXT_DOMAIN), 'color' => 'yellow', 'icon' => '⏳'],
                'pendiente_doc' => ['label' => __('Pendiente documentación', FLAVOR_PLATFORM_TEXT_DOMAIN), 'color' => 'orange', 'icon' => '📎'],
                'resuelto'    => ['label' => __('Resuelto', FLAVOR_PLATFORM_TEXT_DOMAIN), 'color' => 'green', 'icon' => '✅'],
                'denegado'    => ['label' => __('Denegado', FLAVOR_PLATFORM_TEXT_DOMAIN), 'color' => 'red', 'icon' => '❌'],
                'archivado'   => ['label' => __('Archivado', FLAVOR_PLATFORM_TEXT_DOMAIN), 'color' => 'slate', 'icon' => '🗄️'],
            ],

            'stats' => [
                'total_tramites'   => ['label' => __('Total trámites', FLAVOR_PLATFORM_TEXT_DOMAIN), 'icon' => '📋', 'color' => 'slate'],
                'en_curso'         => ['label' => __('En curso', FLAVOR_PLATFORM_TEXT_DOMAIN), 'icon' => '⏳', 'color' => 'yellow'],
                'resueltos'        => ['label' => __('Resueltos', FLAVOR_PLATFORM_TEXT_DOMAIN), 'icon' => '✅', 'color' => 'green'],
                'tiempo_medio'     => ['label' => __('Tiempo medio', FLAVOR_PLATFORM_TEXT_DOMAIN), 'icon' => '⏱️', 'color' => 'blue'],
            ],

            'card' => [
                'template'     => 'tramite-card',
                'title_field'  => 'titulo',
                'subtitle_field' => 'tipo',
                'meta_fields'  => ['expediente', 'fecha_inicio', 'estado'],
                'show_estado'  => true,
                'show_timeline' => true,
            ],

            'tabs' => [
                'catalogo' => [
                    'label'   => __('Catálogo', FLAVOR_PLATFORM_TEXT_DOMAIN),
                    'icon'    => 'dashicons-portfolio',
                    'content' => '[flavor_tramites_catalogo]',
                    'public'  => true,
                ],
                'iniciar' => [
                    'label'      => __('Iniciar trámite', FLAVOR_PLATFORM_TEXT_DOMAIN),
                    'icon'       => 'dashicons-plus-alt',
                    'content'    => '[flavor_tramites_solicitar]',
                    'requires_login' => true,
                ],
                'mis-tramites' => [
                    'label'      => __('Mis trámites', FLAVOR_PLATFORM_TEXT_DOMAIN),
                    'icon'       => 'dashicons-admin-users',
                    'content'    => '[flavor_tramites_mis_solicitudes]',
                    'requires_login' => true,
                ],
                'seguimiento' => [
                    'label'   => __('Seguimiento', FLAVOR_PLATFORM_TEXT_DOMAIN),
                    'icon'    => 'dashicons-search',
                    'content' => '[flavor_tramites_seguimiento]',
                    'public'  => true,
                ],
                'detalle' => [
                    'label'      => __('Detalle', FLAVOR_PLATFORM_TEXT_DOMAIN),
                    'icon'       => 'dashicons-visibility',
                    'content'    => '[flavor_tramites_detalle]',
                    'public'     => true,
                    'hidden_nav' => true,
                ],
                'citas' => [
                    'label'          => __('Citas', FLAVOR_PLATFORM_TEXT_DOMAIN),
                    'icon'           => 'dashicons-calendar-alt',
                    'content'        => '[flavor_tramites_citas]',
                    'requires_login' => true,
                    'hidden_nav'     => true,
                ],
            ],

            'archive' => [
                'columns'    => 3,
                'per_page'   => 12,
                'order_by'   => 'fecha_inicio',
                'order'      => 'DESC',
                'filterable' => ['tipo', 'estado'],
            ],

            'dashboard' => [
                'widgets' => ['stats', 'mis_tramites', 'pendientes', 'historial'],
                'actions' => [
                    'nuevo'      => ['label' => __('Nuevo trámite', FLAVOR_PLATFORM_TEXT_DOMAIN), 'icon' => '➕', 'color' => 'blue'],
                    'seguimiento' => ['label' => __('Consultar estado', FLAVOR_PLATFORM_TEXT_DOMAIN), 'icon' => '🔍', 'color' => 'slate'],
                ],
            ],

            'features' => [
                'expedientes'    => true,
                'documentos'     => true,
                'notificaciones' => true,
                'firmaelectronica' => true,
                'timeline'       => true,
            ],
        ];
    }


    /**
     * Inicializa el dashboard tab del módulo
     */
    private function inicializar_dashboard_tab() {
        $archivo = dirname(__FILE__) . '/class-tramites-dashboard-tab.php';
        if (file_exists($archivo)) {
            require_once $archivo;
            if (class_exists('Flavor_Tramites_Dashboard_Tab')) {
                Flavor_Tramites_Dashboard_Tab::get_instance();
            }
        }
    }

    // =========================================================================
    // PÁGINAS DE ADMINISTRACIÓN OCULTAS
    // =========================================================================

    /**
     * Registra las páginas de administración ocultas del módulo
     */
    public function registrar_paginas_admin() {
        static $registered = false;
        if ($registered) {
            return;
        }
        $registered = true;


        $capability = 'manage_options';

        // Página principal - alias con sufijo -dashboard para Admin Shell
        add_submenu_page(
            null,
            __('Dashboard Trámites', FLAVOR_PLATFORM_TEXT_DOMAIN),
            __('Dashboard', FLAVOR_PLATFORM_TEXT_DOMAIN),
            $capability,
            'tramites-dashboard',
            [$this, 'render_pagina_dashboard']
        );

        // Pendientes
        add_submenu_page(
            null,
            __('Pendientes - Trámites', FLAVOR_PLATFORM_TEXT_DOMAIN),
            __('Pendientes', FLAVOR_PLATFORM_TEXT_DOMAIN),
            $capability,
            'tramites-pendientes',
            [$this, 'render_admin_pendientes']
        );

        // Historial
        add_submenu_page(
            null,
            __('Historial - Trámites', FLAVOR_PLATFORM_TEXT_DOMAIN),
            __('Historial', FLAVOR_PLATFORM_TEXT_DOMAIN),
            $capability,
            'tramites-historial',
            [$this, 'render_admin_historial']
        );

        // Tipos de trámite
        add_submenu_page(
            null,
            __('Tipos de Trámite - Trámites', FLAVOR_PLATFORM_TEXT_DOMAIN),
            __('Tipos de Trámite', FLAVOR_PLATFORM_TEXT_DOMAIN),
            $capability,
            'tramites-tipos',
            [$this, 'render_admin_tipos']
        );

        // Configuración
        add_submenu_page(
            null,
            __('Configuración - Trámites', FLAVOR_PLATFORM_TEXT_DOMAIN),
            __('Configuración', FLAVOR_PLATFORM_TEXT_DOMAIN),
            $capability,
            'tramites-config',
            [$this, 'render_admin_config']
        );
    }
}

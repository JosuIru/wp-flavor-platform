<?php
/**
 * Módulo de Empresas para Flavor Chat IA
 *
 * Gestiona empresas dentro de comunidades con contabilidad,
 * facturación y miembros independientes.
 *
 * @package FlavorPlatform
 * @since 1.0.0
 */

if (!defined('ABSPATH')) {
    exit;
}

class Flavor_Platform_Empresas_Module extends Flavor_Platform_Module_Base {

    use Flavor_Module_Admin_Pages_Trait;
    use Flavor_Module_Notifications_Trait;

    /** @var string */
    const VERSION = '1.0.0';

    /** @var string */
    private $tabla_empresas;

    /** @var string */
    private $tabla_miembros;

    /** @var string */
    private $tabla_documentos;

    /** @var string */
    private $tabla_actividad;

    public function __construct() {
        $this->id = 'empresas';
        $this->name = __('Empresas', 'flavor-platform');
        $this->description = __('Gestión de empresas con contabilidad, facturación y miembros independientes dentro de comunidades.', 'flavor-platform');

        global $wpdb;
        $this->tabla_empresas = $wpdb->prefix . 'flavor_empresas';
        $this->tabla_miembros = $wpdb->prefix . 'flavor_empresas_miembros';
        $this->tabla_documentos = $wpdb->prefix . 'flavor_empresas_documentos';
        $this->tabla_actividad = $wpdb->prefix . 'flavor_empresas_actividad';

        parent::__construct();
    }

    /**
     * Configuración del módulo para el panel de módulos.
     */
    public static function get_module_info() {
        return [
            'id' => 'empresas',
            'name' => __('Empresas', 'flavor-platform'),
            'description' => __('Crea y gestiona empresas dentro de tu comunidad. Cada empresa tiene su propia contabilidad, facturas, miembros y documentación.', 'flavor-platform'),
            'icon' => 'dashicons-building',
            'category' => 'economia',
            'tags' => ['empresas', 'negocios', 'contabilidad', 'facturación', 'b2b'],
            'version' => self::VERSION,
            'author' => 'Flavor Chat IA',
            'requires' => [],
            'recommends' => ['contabilidad', 'facturas', 'comunidades'],
            'features' => [
                __('Registro de empresas con datos fiscales', 'flavor-platform'),
                __('Miembros con roles (admin, contable, empleado)', 'flavor-platform'),
                __('Contabilidad independiente por empresa', 'flavor-platform'),
                __('Facturación propia', 'flavor-platform'),
                __('Documentación centralizada', 'flavor-platform'),
                __('Dashboard de cliente para empresas', 'flavor-platform'),
                __('API para aplicaciones móviles', 'flavor-platform'),
            ],
            'screenshots' => [],
            'documentation_url' => '',
            'support_url' => '',
        ];
    }

    public function can_activate() {
        return Flavor_Platform_Helpers::tabla_existe($this->tabla_empresas);
    }

    public function get_activation_error() {
        if (!$this->can_activate()) {
            return __('Las tablas de Empresas no están creadas. Se crearán automáticamente al activar.', 'flavor-platform');
        }
        return '';
    }

    public function is_active() {
        return $this->can_activate();
    }

    protected function get_default_settings() {
        return [
            'permitir_autoregistro' => false,
            'requiere_aprobacion' => true,
            'tipos_empresa' => ['sl', 'sa', 'autonomo', 'cooperativa', 'asociacion', 'comunidad_bienes', 'sociedad_civil'],
            'sectores' => ['tecnologia', 'comercio', 'servicios', 'industria', 'agricultura', 'construccion', 'hosteleria', 'transporte', 'educacion', 'salud', 'otros'],
            'roles_miembro' => ['admin', 'contable', 'empleado', 'colaborador', 'observador'],
            'max_miembros_gratis' => 5,
            'documentos_permitidos' => ['pdf', 'doc', 'docx', 'xls', 'xlsx', 'jpg', 'png'],
            'max_tamano_documento_mb' => 10,
            'integracion_contabilidad' => true,
            'integracion_facturas' => true,
        ];
    }

    public function init() {
        add_action('init', [$this, 'maybe_create_tables']);

        $this->registrar_en_panel_unificado();

        // Hooks para integración con otros módulos
        add_filter('flavor_contabilidad_empresa_id', [$this, 'get_empresa_actual_usuario']);
        add_filter('flavor_facturas_empresa_id', [$this, 'get_empresa_actual_usuario']);

        // API REST
        add_action('rest_api_init', [$this, 'register_rest_routes']);

        // Shortcodes
        add_shortcode('flavor_empresas_dashboard', [$this, 'shortcode_dashboard']);
        add_shortcode('flavor_empresas_listado', [$this, 'shortcode_listado']);
        add_shortcode('flavor_empresa_perfil', [$this, 'shortcode_perfil']);
    }

    protected function get_admin_config() {
        return [
            'id' => 'empresas',
            'label' => __('Empresas', 'flavor-platform'),
            'icon' => 'dashicons-building',
            'capability' => 'manage_options',
            'categoria' => 'negocios',
            'paginas' => [
                [
                    'slug' => 'empresas-dashboard',
                    'titulo' => __('Dashboard', 'flavor-platform'),
                    'callback' => [$this, 'render_admin_dashboard'],
                ],
                [
                    'slug' => 'empresas-listado',
                    'titulo' => __('Empresas', 'flavor-platform'),
                    'callback' => [$this, 'render_admin_listado'],
                ],
                [
                    'slug' => 'empresas-solicitudes',
                    'titulo' => __('Solicitudes', 'flavor-platform'),
                    'callback' => [$this, 'render_admin_solicitudes'],
                ],
                [
                    'slug' => 'empresas-config',
                    'titulo' => __('Configuración', 'flavor-platform'),
                    'callback' => [$this, 'render_admin_config'],
                ],
            ],
            'estadisticas' => [$this, 'get_estadisticas_dashboard'],
        ];
    }

    public function maybe_create_tables() {
        if (!Flavor_Platform_Helpers::tabla_existe($this->tabla_empresas)) {
            $this->create_tables();
        }
    }

    private function create_tables() {
        global $wpdb;
        $charset_collate = $wpdb->get_charset_collate();

        // Tabla principal de empresas
        $sql_empresas = "CREATE TABLE IF NOT EXISTS {$this->tabla_empresas} (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            nombre varchar(255) NOT NULL,
            razon_social varchar(255) DEFAULT NULL,
            cif_nif varchar(20) DEFAULT NULL,
            tipo enum('sl','sa','autonomo','cooperativa','asociacion','comunidad_bienes','sociedad_civil','otro') NOT NULL DEFAULT 'sl',
            sector varchar(100) DEFAULT NULL,
            descripcion text DEFAULT NULL,
            logo_url varchar(500) DEFAULT NULL,
            email varchar(100) DEFAULT NULL,
            telefono varchar(30) DEFAULT NULL,
            web varchar(255) DEFAULT NULL,
            direccion varchar(255) DEFAULT NULL,
            ciudad varchar(100) DEFAULT NULL,
            provincia varchar(100) DEFAULT NULL,
            codigo_postal varchar(10) DEFAULT NULL,
            pais varchar(100) DEFAULT 'España',
            comunidad_id bigint(20) unsigned DEFAULT NULL,
            colectivo_id bigint(20) unsigned DEFAULT NULL,
            creador_id bigint(20) unsigned NOT NULL,
            estado enum('pendiente','activa','suspendida','baja') NOT NULL DEFAULT 'pendiente',
            fecha_alta date DEFAULT NULL,
            fecha_baja date DEFAULT NULL,
            motivo_baja text DEFAULT NULL,
            configuracion longtext DEFAULT NULL,
            metadata longtext DEFAULT NULL,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            UNIQUE KEY uniq_cif_nif (cif_nif),
            KEY idx_nombre (nombre),
            KEY idx_tipo (tipo),
            KEY idx_estado (estado),
            KEY idx_comunidad (comunidad_id),
            KEY idx_colectivo (colectivo_id),
            KEY idx_creador (creador_id),
            KEY idx_sector (sector)
        ) $charset_collate;";

        // Tabla de miembros de empresa
        $sql_miembros = "CREATE TABLE IF NOT EXISTS {$this->tabla_miembros} (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            empresa_id bigint(20) unsigned NOT NULL,
            user_id bigint(20) unsigned NOT NULL,
            rol enum('admin','contable','empleado','colaborador','observador') NOT NULL DEFAULT 'empleado',
            cargo varchar(100) DEFAULT NULL,
            departamento varchar(100) DEFAULT NULL,
            email_corporativo varchar(100) DEFAULT NULL,
            telefono_corporativo varchar(30) DEFAULT NULL,
            estado enum('activo','pendiente','suspendido','baja') NOT NULL DEFAULT 'pendiente',
            permisos longtext DEFAULT NULL,
            fecha_alta date DEFAULT NULL,
            fecha_baja date DEFAULT NULL,
            invitado_por bigint(20) unsigned DEFAULT NULL,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            UNIQUE KEY uniq_empresa_user (empresa_id, user_id),
            KEY idx_empresa (empresa_id),
            KEY idx_user (user_id),
            KEY idx_rol (rol),
            KEY idx_estado (estado)
        ) $charset_collate;";

        // Tabla de documentos de empresa
        $sql_documentos = "CREATE TABLE IF NOT EXISTS {$this->tabla_documentos} (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            empresa_id bigint(20) unsigned NOT NULL,
            nombre varchar(255) NOT NULL,
            descripcion text DEFAULT NULL,
            tipo enum('estatutos','escritura','contrato','factura','nomina','certificado','otro') NOT NULL DEFAULT 'otro',
            archivo_url varchar(500) NOT NULL,
            archivo_nombre varchar(255) NOT NULL,
            archivo_tipo varchar(50) DEFAULT NULL,
            archivo_tamano bigint(20) unsigned DEFAULT NULL,
            visibilidad enum('privado','miembros','publico') NOT NULL DEFAULT 'miembros',
            subido_por bigint(20) unsigned NOT NULL,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY idx_empresa (empresa_id),
            KEY idx_tipo (tipo),
            KEY idx_visibilidad (visibilidad)
        ) $charset_collate;";

        // Tabla de actividad
        $sql_actividad = "CREATE TABLE IF NOT EXISTS {$this->tabla_actividad} (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            empresa_id bigint(20) unsigned NOT NULL,
            user_id bigint(20) unsigned DEFAULT NULL,
            accion varchar(100) NOT NULL,
            descripcion text DEFAULT NULL,
            entidad_tipo varchar(50) DEFAULT NULL,
            entidad_id bigint(20) unsigned DEFAULT NULL,
            datos longtext DEFAULT NULL,
            ip_address varchar(45) DEFAULT NULL,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY idx_empresa (empresa_id),
            KEY idx_user (user_id),
            KEY idx_accion (accion),
            KEY idx_created (created_at)
        ) $charset_collate;";

        require_once ABSPATH . 'wp-admin/includes/upgrade.php';
        dbDelta($sql_empresas);
        dbDelta($sql_miembros);
        dbDelta($sql_documentos);
        dbDelta($sql_actividad);
    }

    // =========================================================================
    // CRUD DE EMPRESAS
    // =========================================================================

    /**
     * Crear una nueva empresa.
     */
    public function crear_empresa($datos) {
        global $wpdb;

        $datos_insert = [
            'nombre' => sanitize_text_field($datos['nombre'] ?? ''),
            'razon_social' => sanitize_text_field($datos['razon_social'] ?? ''),
            'cif_nif' => sanitize_text_field($datos['cif_nif'] ?? ''),
            'tipo' => sanitize_key($datos['tipo'] ?? 'sl'),
            'sector' => sanitize_text_field($datos['sector'] ?? ''),
            'descripcion' => sanitize_textarea_field($datos['descripcion'] ?? ''),
            'email' => sanitize_email($datos['email'] ?? ''),
            'telefono' => sanitize_text_field($datos['telefono'] ?? ''),
            'web' => esc_url_raw($datos['web'] ?? ''),
            'direccion' => sanitize_text_field($datos['direccion'] ?? ''),
            'ciudad' => sanitize_text_field($datos['ciudad'] ?? ''),
            'provincia' => sanitize_text_field($datos['provincia'] ?? ''),
            'codigo_postal' => sanitize_text_field($datos['codigo_postal'] ?? ''),
            'pais' => sanitize_text_field($datos['pais'] ?? 'España'),
            'comunidad_id' => absint($datos['comunidad_id'] ?? 0) ?: null,
            'colectivo_id' => absint($datos['colectivo_id'] ?? 0) ?: null,
            'creador_id' => get_current_user_id(),
            'estado' => $this->get_setting('requiere_aprobacion', true) ? 'pendiente' : 'activa',
            'fecha_alta' => current_time('Y-m-d'),
        ];

        $resultado = $wpdb->insert($this->tabla_empresas, $datos_insert);

        if ($resultado === false) {
            return new WP_Error('db_error', __('Error al crear la empresa.', 'flavor-platform'));
        }

        $empresa_id = $wpdb->insert_id;

        // Añadir al creador como admin
        $this->agregar_miembro($empresa_id, get_current_user_id(), 'admin', [
            'cargo' => __('Administrador', 'flavor-platform'),
            'estado' => 'activo',
        ]);

        // Registrar actividad
        $this->registrar_actividad($empresa_id, 'empresa_creada', __('Empresa creada', 'flavor-platform'));

        do_action('flavor_empresa_creada', $empresa_id, $datos_insert);

        return $empresa_id;
    }

    /**
     * Obtener empresa por ID.
     */
    public function obtener_empresa($empresa_id) {
        global $wpdb;

        return $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$this->tabla_empresas} WHERE id = %d",
            absint($empresa_id)
        ));
    }

    /**
     * Actualizar empresa.
     */
    public function actualizar_empresa($empresa_id, $datos) {
        global $wpdb;

        $campos_permitidos = [
            'nombre', 'razon_social', 'cif_nif', 'tipo', 'sector', 'descripcion',
            'logo_url', 'email', 'telefono', 'web', 'direccion', 'ciudad',
            'provincia', 'codigo_postal', 'pais', 'estado', 'configuracion', 'metadata'
        ];

        $datos_update = [];
        foreach ($campos_permitidos as $campo) {
            if (isset($datos[$campo])) {
                $datos_update[$campo] = $campo === 'email'
                    ? sanitize_email($datos[$campo])
                    : sanitize_text_field($datos[$campo]);
            }
        }

        if (empty($datos_update)) {
            return false;
        }

        $resultado = $wpdb->update(
            $this->tabla_empresas,
            $datos_update,
            ['id' => absint($empresa_id)]
        );

        if ($resultado !== false) {
            $this->registrar_actividad($empresa_id, 'empresa_actualizada', __('Datos actualizados', 'flavor-platform'));
            do_action('flavor_empresa_actualizada', $empresa_id, $datos_update);
        }

        return $resultado !== false;
    }

    /**
     * Listar empresas con filtros.
     */
    public function listar_empresas($filtros = [], $limite = 50, $offset = 0) {
        global $wpdb;

        $where = ['1=1'];
        $params = [];

        if (!empty($filtros['estado'])) {
            $where[] = 'estado = %s';
            $params[] = sanitize_key($filtros['estado']);
        }

        if (!empty($filtros['tipo'])) {
            $where[] = 'tipo = %s';
            $params[] = sanitize_key($filtros['tipo']);
        }

        if (!empty($filtros['sector'])) {
            $where[] = 'sector = %s';
            $params[] = sanitize_text_field($filtros['sector']);
        }

        if (!empty($filtros['comunidad_id'])) {
            $where[] = 'comunidad_id = %d';
            $params[] = absint($filtros['comunidad_id']);
        }

        if (!empty($filtros['busqueda'])) {
            $where[] = '(nombre LIKE %s OR razon_social LIKE %s OR cif_nif LIKE %s)';
            $busqueda = '%' . $wpdb->esc_like(sanitize_text_field($filtros['busqueda'])) . '%';
            $params[] = $busqueda;
            $params[] = $busqueda;
            $params[] = $busqueda;
        }

        $sql = "SELECT * FROM {$this->tabla_empresas} WHERE " . implode(' AND ', $where);
        $sql .= " ORDER BY nombre ASC LIMIT %d OFFSET %d";
        $params[] = absint($limite);
        $params[] = absint($offset);

        return $wpdb->get_results($wpdb->prepare($sql, ...$params));
    }

    /**
     * Contar empresas con filtros.
     */
    public function contar_empresas($filtros = []) {
        global $wpdb;

        $where = ['1=1'];
        $params = [];

        if (!empty($filtros['estado'])) {
            $where[] = 'estado = %s';
            $params[] = sanitize_key($filtros['estado']);
        }

        $sql = "SELECT COUNT(*) FROM {$this->tabla_empresas} WHERE " . implode(' AND ', $where);

        if (!empty($params)) {
            return (int) $wpdb->get_var($wpdb->prepare($sql, ...$params));
        }

        return (int) $wpdb->get_var($sql);
    }

    // =========================================================================
    // GESTIÓN DE MIEMBROS
    // =========================================================================

    /**
     * Agregar miembro a empresa.
     */
    public function agregar_miembro($empresa_id, $user_id, $rol = 'empleado', $datos = []) {
        global $wpdb;

        $datos_insert = [
            'empresa_id' => absint($empresa_id),
            'user_id' => absint($user_id),
            'rol' => in_array($rol, ['admin', 'contable', 'empleado', 'colaborador', 'observador']) ? $rol : 'empleado',
            'cargo' => sanitize_text_field($datos['cargo'] ?? ''),
            'departamento' => sanitize_text_field($datos['departamento'] ?? ''),
            'email_corporativo' => sanitize_email($datos['email_corporativo'] ?? ''),
            'telefono_corporativo' => sanitize_text_field($datos['telefono_corporativo'] ?? ''),
            'estado' => sanitize_key($datos['estado'] ?? 'pendiente'),
            'fecha_alta' => current_time('Y-m-d'),
            'invitado_por' => get_current_user_id(),
        ];

        $resultado = $wpdb->insert($this->tabla_miembros, $datos_insert);

        if ($resultado !== false) {
            $this->registrar_actividad($empresa_id, 'miembro_agregado', sprintf(
                __('Miembro añadido: %s', 'flavor-platform'),
                get_userdata($user_id)->display_name ?? ''
            ));
            do_action('flavor_empresa_miembro_agregado', $empresa_id, $user_id, $rol);
        }

        return $resultado !== false ? $wpdb->insert_id : false;
    }

    /**
     * Obtener miembros de empresa.
     */
    public function obtener_miembros($empresa_id, $estado = null) {
        global $wpdb;

        $sql = "SELECT m.*, u.display_name, u.user_email
                FROM {$this->tabla_miembros} m
                LEFT JOIN {$wpdb->users} u ON m.user_id = u.ID
                WHERE m.empresa_id = %d";
        $params = [absint($empresa_id)];

        if ($estado) {
            $sql .= " AND m.estado = %s";
            $params[] = sanitize_key($estado);
        }

        $sql .= " ORDER BY FIELD(m.rol, 'admin', 'contable', 'empleado', 'colaborador', 'observador'), m.created_at ASC";

        return $wpdb->get_results($wpdb->prepare($sql, ...$params));
    }

    /**
     * Verificar si usuario es miembro de empresa.
     */
    public function es_miembro($empresa_id, $user_id = null) {
        global $wpdb;

        $user_id = $user_id ?: get_current_user_id();

        return (bool) $wpdb->get_var($wpdb->prepare(
            "SELECT id FROM {$this->tabla_miembros}
             WHERE empresa_id = %d AND user_id = %d AND estado = 'activo'",
            absint($empresa_id),
            absint($user_id)
        ));
    }

    /**
     * Obtener rol de usuario en empresa.
     */
    public function obtener_rol_usuario($empresa_id, $user_id = null) {
        global $wpdb;

        $user_id = $user_id ?: get_current_user_id();

        return $wpdb->get_var($wpdb->prepare(
            "SELECT rol FROM {$this->tabla_miembros}
             WHERE empresa_id = %d AND user_id = %d AND estado = 'activo'",
            absint($empresa_id),
            absint($user_id)
        ));
    }

    /**
     * Obtener empresas de un usuario.
     */
    public function obtener_empresas_usuario($user_id = null) {
        global $wpdb;

        $user_id = $user_id ?: get_current_user_id();

        return $wpdb->get_results($wpdb->prepare(
            "SELECT e.*, m.rol, m.cargo, e.id as empresa_id, e.nombre as empresa_nombre
             FROM {$this->tabla_empresas} e
             INNER JOIN {$this->tabla_miembros} m ON e.id = m.empresa_id
             WHERE m.user_id = %d AND m.estado = 'activo' AND e.estado = 'activa'
             ORDER BY e.nombre ASC",
            absint($user_id)
        ));
    }

    /**
     * Alias para compatibilidad con frontend.
     */
    public function get_empresas_usuario($user_id = null) {
        return $this->obtener_empresas_usuario($user_id);
    }

    /**
     * Alias para compatibilidad con frontend.
     */
    public function get_empresa($empresa_id) {
        return $this->obtener_empresa($empresa_id);
    }

    /**
     * Obtener datos de miembro en empresa.
     */
    public function get_miembro($empresa_id, $user_id = null) {
        global $wpdb;

        $user_id = $user_id ?: get_current_user_id();

        return $wpdb->get_row($wpdb->prepare(
            "SELECT m.*, u.display_name, u.user_email
             FROM {$this->tabla_miembros} m
             LEFT JOIN {$wpdb->users} u ON m.user_id = u.ID
             WHERE m.empresa_id = %d AND m.user_id = %d",
            absint($empresa_id),
            absint($user_id)
        ));
    }

    /**
     * Obtener empresa actual del usuario (la primera o la seleccionada).
     */
    public function get_empresa_actual_usuario($user_id = null) {
        $user_id = $user_id ?: get_current_user_id();

        // Verificar si hay empresa seleccionada en sesión
        $empresa_id = get_user_meta($user_id, '_flavor_empresa_actual', true);

        if ($empresa_id && $this->es_miembro($empresa_id, $user_id)) {
            return absint($empresa_id);
        }

        // Obtener primera empresa del usuario
        $empresas = $this->obtener_empresas_usuario($user_id);

        if (!empty($empresas)) {
            return absint($empresas[0]->id);
        }

        return 0;
    }

    // =========================================================================
    // ACTIVIDAD Y LOGS
    // =========================================================================

    /**
     * Registrar actividad en empresa.
     */
    public function registrar_actividad($empresa_id, $accion, $descripcion = '', $datos = []) {
        global $wpdb;

        return $wpdb->insert($this->tabla_actividad, [
            'empresa_id' => absint($empresa_id),
            'user_id' => get_current_user_id(),
            'accion' => sanitize_key($accion),
            'descripcion' => sanitize_text_field($descripcion),
            'entidad_tipo' => sanitize_key($datos['entidad_tipo'] ?? ''),
            'entidad_id' => absint($datos['entidad_id'] ?? 0),
            'datos' => !empty($datos) ? wp_json_encode($datos) : null,
            'ip_address' => sanitize_text_field($_SERVER['REMOTE_ADDR'] ?? ''),
        ]);
    }

    /**
     * Obtener actividad de empresa.
     */
    public function obtener_actividad($empresa_id, $limite = 20) {
        global $wpdb;

        return $wpdb->get_results($wpdb->prepare(
            "SELECT a.*, u.display_name
             FROM {$this->tabla_actividad} a
             LEFT JOIN {$wpdb->users} u ON a.user_id = u.ID
             WHERE a.empresa_id = %d
             ORDER BY a.created_at DESC
             LIMIT %d",
            absint($empresa_id),
            absint($limite)
        ));
    }

    // =========================================================================
    // ESTADÍSTICAS
    // =========================================================================

    public function get_estadisticas_dashboard() {
        $total_empresas = $this->contar_empresas(['estado' => 'activa']);
        $pendientes = $this->contar_empresas(['estado' => 'pendiente']);
        $total_miembros = $this->contar_miembros_total();

        return [
            [
                'icon' => 'dashicons-building',
                'valor' => $total_empresas,
                'label' => __('Empresas activas', 'flavor-platform'),
                'color' => 'blue',
                'enlace' => admin_url('admin.php?page=empresas-listado'),
            ],
            [
                'icon' => 'dashicons-clock',
                'valor' => $pendientes,
                'label' => __('Pendientes', 'flavor-platform'),
                'color' => $pendientes > 0 ? 'orange' : 'gray',
                'enlace' => admin_url('admin.php?page=empresas-solicitudes'),
            ],
            [
                'icon' => 'dashicons-groups',
                'valor' => $total_miembros,
                'label' => __('Miembros totales', 'flavor-platform'),
                'color' => 'green',
            ],
        ];
    }

    private function contar_miembros_total() {
        global $wpdb;
        return (int) $wpdb->get_var(
            "SELECT COUNT(*) FROM {$this->tabla_miembros} WHERE estado = 'activo'"
        );
    }

    // =========================================================================
    // RENDERS ADMIN
    // =========================================================================

    public function render_admin_dashboard() {
        $total_activas = $this->contar_empresas(['estado' => 'activa']);
        $total_pendientes = $this->contar_empresas(['estado' => 'pendiente']);
        $total_suspendidas = $this->contar_empresas(['estado' => 'suspendida']);
        $total_miembros = $this->contar_miembros_total();
        $ultimas_empresas = $this->listar_empresas(['estado' => 'activa'], 5);
        $solicitudes_recientes = $this->listar_empresas(['estado' => 'pendiente'], 5);

        // Estadísticas por tipo
        global $wpdb;
        $por_tipo = $wpdb->get_results(
            "SELECT tipo, COUNT(*) as total FROM {$this->tabla_empresas}
             WHERE estado = 'activa' GROUP BY tipo ORDER BY total DESC"
        );

        $por_sector = $wpdb->get_results(
            "SELECT sector, COUNT(*) as total FROM {$this->tabla_empresas}
             WHERE estado = 'activa' AND sector != '' GROUP BY sector ORDER BY total DESC LIMIT 10"
        );

        include dirname(__FILE__) . '/views/dashboard.php';
    }

    public function render_admin_listado() {
        $action = isset($_GET['action']) ? sanitize_key($_GET['action']) : '';
        $empresa_id = isset($_GET['id']) ? absint($_GET['id']) : 0;

        // Procesar acciones
        if ($action === 'crear' || ($action === 'editar' && $empresa_id)) {
            $this->render_admin_formulario($empresa_id);
            return;
        }

        if ($action === 'ver' && $empresa_id) {
            $this->render_admin_detalle($empresa_id);
            return;
        }

        if ($action === 'miembros' && $empresa_id) {
            $this->render_admin_miembros($empresa_id);
            return;
        }

        // Listar empresas
        $filtros = [
            'estado' => isset($_GET['estado']) ? sanitize_key($_GET['estado']) : 'activa',
            'tipo' => isset($_GET['tipo']) ? sanitize_key($_GET['tipo']) : '',
            'busqueda' => isset($_GET['s']) ? sanitize_text_field($_GET['s']) : '',
        ];

        $empresas = $this->listar_empresas($filtros, 50);
        $total = $this->contar_empresas($filtros);

        include dirname(__FILE__) . '/views/listado.php';
    }

    public function render_admin_solicitudes() {
        // Procesar aprobación/rechazo
        if (isset($_POST['accion_solicitud']) && isset($_POST['empresa_id'])) {
            $nonce = isset($_POST['_wpnonce']) ? sanitize_text_field($_POST['_wpnonce']) : '';
            if (wp_verify_nonce($nonce, 'gestionar_solicitud')) {
                $empresa_id = absint($_POST['empresa_id']);
                $accion = sanitize_key($_POST['accion_solicitud']);

                if ($accion === 'aprobar') {
                    $this->actualizar_empresa($empresa_id, ['estado' => 'activa']);
                    echo '<div class="notice notice-success"><p>' . esc_html__('Empresa aprobada.', 'flavor-platform') . '</p></div>';
                } elseif ($accion === 'rechazar') {
                    $this->actualizar_empresa($empresa_id, ['estado' => 'baja', 'motivo_baja' => sanitize_textarea_field($_POST['motivo'] ?? '')]);
                    echo '<div class="notice notice-warning"><p>' . esc_html__('Solicitud rechazada.', 'flavor-platform') . '</p></div>';
                }
            }
        }

        $solicitudes = $this->listar_empresas(['estado' => 'pendiente'], 50);

        include dirname(__FILE__) . '/views/solicitudes.php';
    }

    public function render_admin_config() {
        if (isset($_POST['guardar_config'])) {
            $nonce = isset($_POST['_wpnonce']) ? sanitize_text_field($_POST['_wpnonce']) : '';
            if (wp_verify_nonce($nonce, 'empresas_config')) {
                $this->update_setting('permitir_autoregistro', isset($_POST['permitir_autoregistro']));
                $this->update_setting('requiere_aprobacion', isset($_POST['requiere_aprobacion']));
                $this->update_setting('max_miembros_gratis', absint($_POST['max_miembros_gratis'] ?? 5));
                $this->update_setting('max_tamano_documento_mb', absint($_POST['max_tamano_documento_mb'] ?? 10));
                $this->update_setting('integracion_contabilidad', isset($_POST['integracion_contabilidad']));
                $this->update_setting('integracion_facturas', isset($_POST['integracion_facturas']));

                echo '<div class="notice notice-success"><p>' . esc_html__('Configuración guardada.', 'flavor-platform') . '</p></div>';
            }
        }

        include dirname(__FILE__) . '/views/configuracion.php';
    }

    private function render_admin_formulario($empresa_id = 0) {
        $empresa = $empresa_id ? $this->obtener_empresa($empresa_id) : null;
        $es_edicion = !empty($empresa);

        // Procesar guardado
        if (isset($_POST['guardar_empresa'])) {
            $nonce = isset($_POST['_wpnonce']) ? sanitize_text_field($_POST['_wpnonce']) : '';
            if (wp_verify_nonce($nonce, 'guardar_empresa')) {
                $datos = [
                    'nombre' => sanitize_text_field($_POST['nombre'] ?? ''),
                    'razon_social' => sanitize_text_field($_POST['razon_social'] ?? ''),
                    'cif_nif' => sanitize_text_field($_POST['cif_nif'] ?? ''),
                    'tipo' => sanitize_key($_POST['tipo'] ?? 'sl'),
                    'sector' => sanitize_text_field($_POST['sector'] ?? ''),
                    'descripcion' => sanitize_textarea_field($_POST['descripcion'] ?? ''),
                    'email' => sanitize_email($_POST['email'] ?? ''),
                    'telefono' => sanitize_text_field($_POST['telefono'] ?? ''),
                    'web' => esc_url_raw($_POST['web'] ?? ''),
                    'direccion' => sanitize_text_field($_POST['direccion'] ?? ''),
                    'ciudad' => sanitize_text_field($_POST['ciudad'] ?? ''),
                    'provincia' => sanitize_text_field($_POST['provincia'] ?? ''),
                    'codigo_postal' => sanitize_text_field($_POST['codigo_postal'] ?? ''),
                    'pais' => sanitize_text_field($_POST['pais'] ?? 'España'),
                ];

                if ($es_edicion) {
                    $this->actualizar_empresa($empresa_id, $datos);
                    echo '<div class="notice notice-success"><p>' . esc_html__('Empresa actualizada.', 'flavor-platform') . '</p></div>';
                    $empresa = $this->obtener_empresa($empresa_id);
                } else {
                    $resultado = $this->crear_empresa($datos);
                    if (is_wp_error($resultado)) {
                        echo '<div class="notice notice-error"><p>' . esc_html($resultado->get_error_message()) . '</p></div>';
                    } else {
                        wp_safe_redirect(admin_url('admin.php?page=empresas-listado&action=ver&id=' . $resultado . '&created=1'));
                        exit;
                    }
                }
            }
        }

        include dirname(__FILE__) . '/views/formulario.php';
    }

    private function render_admin_detalle($empresa_id) {
        $empresa = $this->obtener_empresa($empresa_id);

        if (!$empresa) {
            echo '<div class="notice notice-error"><p>' . esc_html__('Empresa no encontrada.', 'flavor-platform') . '</p></div>';
            return;
        }

        $miembros = $this->obtener_miembros($empresa_id, 'activo');
        $actividad = $this->obtener_actividad($empresa_id, 15);

        // Estadísticas de la empresa
        $stats = $this->obtener_estadisticas_empresa($empresa_id);

        include dirname(__FILE__) . '/views/detalle.php';
    }

    private function render_admin_miembros($empresa_id) {
        $empresa = $this->obtener_empresa($empresa_id);

        if (!$empresa) {
            echo '<div class="notice notice-error"><p>' . esc_html__('Empresa no encontrada.', 'flavor-platform') . '</p></div>';
            return;
        }

        // Procesar acciones de miembros
        if (isset($_POST['accion_miembro'])) {
            $this->procesar_accion_miembro();
        }

        $miembros = $this->obtener_miembros($empresa_id);
        $roles_disponibles = $this->get_setting('roles_miembro', ['admin', 'contable', 'empleado', 'colaborador', 'observador']);

        include dirname(__FILE__) . '/views/miembros.php';
    }

    private function procesar_accion_miembro() {
        $nonce = isset($_POST['_wpnonce']) ? sanitize_text_field($_POST['_wpnonce']) : '';
        if (!wp_verify_nonce($nonce, 'gestionar_miembro')) {
            return;
        }

        global $wpdb;
        $miembro_id = absint($_POST['miembro_id'] ?? 0);
        $accion = sanitize_key($_POST['accion_miembro']);

        switch ($accion) {
            case 'activar':
                $wpdb->update($this->tabla_miembros, ['estado' => 'activo'], ['id' => $miembro_id]);
                break;
            case 'suspender':
                $wpdb->update($this->tabla_miembros, ['estado' => 'suspendido'], ['id' => $miembro_id]);
                break;
            case 'dar_baja':
                $wpdb->update($this->tabla_miembros, ['estado' => 'baja', 'fecha_baja' => current_time('Y-m-d')], ['id' => $miembro_id]);
                break;
            case 'cambiar_rol':
                $nuevo_rol = sanitize_key($_POST['nuevo_rol'] ?? 'empleado');
                $wpdb->update($this->tabla_miembros, ['rol' => $nuevo_rol], ['id' => $miembro_id]);
                break;
        }
    }

    /**
     * Obtener estadísticas de una empresa específica.
     */
    private function obtener_estadisticas_empresa($empresa_id) {
        global $wpdb;

        $stats = [
            'miembros_activos' => 0,
            'miembros_totales' => 0,
            'documentos' => 0,
            'ingresos_mes' => 0,
            'gastos_mes' => 0,
        ];

        // Miembros
        $stats['miembros_activos'] = (int) $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM {$this->tabla_miembros} WHERE empresa_id = %d AND estado = 'activo'",
            $empresa_id
        ));

        $stats['miembros_totales'] = (int) $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM {$this->tabla_miembros} WHERE empresa_id = %d",
            $empresa_id
        ));

        // Documentos
        $stats['documentos'] = (int) $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM {$this->tabla_documentos} WHERE empresa_id = %d",
            $empresa_id
        ));

        // Integración con contabilidad (si existe)
        $tabla_movimientos = $wpdb->prefix . 'flavor_contabilidad_movimientos';
        if (Flavor_Platform_Helpers::tabla_existe($tabla_movimientos)) {
            $mes_actual = current_time('Y-m');

            $stats['ingresos_mes'] = (float) $wpdb->get_var($wpdb->prepare(
                "SELECT COALESCE(SUM(total), 0) FROM {$tabla_movimientos}
                 WHERE entidad_tipo = 'empresa' AND entidad_id = %d
                 AND tipo_movimiento = 'ingreso' AND estado = 'confirmado'
                 AND fecha_movimiento LIKE %s",
                $empresa_id,
                $mes_actual . '%'
            ));

            $stats['gastos_mes'] = (float) $wpdb->get_var($wpdb->prepare(
                "SELECT COALESCE(SUM(total), 0) FROM {$tabla_movimientos}
                 WHERE entidad_tipo = 'empresa' AND entidad_id = %d
                 AND tipo_movimiento = 'gasto' AND estado = 'confirmado'
                 AND fecha_movimiento LIKE %s",
                $empresa_id,
                $mes_actual . '%'
            ));
        }

        return $stats;
    }

    // =========================================================================
    // SHORTCODES FRONTEND
    // =========================================================================

    /**
     * Obtener el controlador frontend.
     */
    private function get_frontend_controller() {
        static $controller = null;

        if ($controller === null) {
            $controller_file = dirname(__FILE__) . '/frontend/class-empresas-frontend-controller.php';
            if (file_exists($controller_file)) {
                require_once $controller_file;
                $controller = new Flavor_Empresas_Frontend_Controller($this);
            }
        }

        return $controller;
    }

    public function shortcode_dashboard($atts) {
        $atts = shortcode_atts([
            'vista' => isset($_GET['vista']) ? sanitize_key($_GET['vista']) : 'dashboard',
        ], $atts);

        $controller = $this->get_frontend_controller();
        if ($controller) {
            return $controller->render($atts['vista'], $atts);
        }

        // Fallback si no existe el controlador
        if (!is_user_logged_in()) {
            return '<p>' . esc_html__('Debes iniciar sesión para ver tu dashboard de empresa.', 'flavor-platform') . '</p>';
        }

        $empresas = $this->obtener_empresas_usuario();

        if (empty($empresas)) {
            $puede_crear = $this->get_setting('permitir_crear_frontend', true);
            ob_start();
            include dirname(__FILE__) . '/frontend/views/sin-empresa.php';
            return ob_get_clean();
        }

        $empresa_id = $this->get_empresa_actual_usuario();
        $empresa = $this->obtener_empresa($empresa_id);
        $miembros = $this->obtener_miembros($empresa_id, 'activo');
        $miembro = $this->get_miembro($empresa_id);
        $rol_usuario = $this->obtener_rol_usuario($empresa_id);
        $es_admin = $rol_usuario === 'admin';
        $stats = $this->obtener_estadisticas_empresa($empresa_id);
        $empresas_usuario = $empresas;
        $miembros_recientes = array_slice($miembros, 0, 5);
        $actividad = $this->obtener_actividad($empresa_id, 10);

        ob_start();
        include dirname(__FILE__) . '/frontend/views/dashboard.php';
        return ob_get_clean();
    }

    public function shortcode_listado($atts) {
        $atts = shortcode_atts([
            'comunidad_id' => 0,
            'sector' => '',
            'limite' => 12,
        ], $atts);

        $controller = $this->get_frontend_controller();
        if ($controller) {
            return $controller->render_listado($atts);
        }

        // Fallback
        $filtros = [
            'estado' => 'activa',
        ];

        if ($atts['comunidad_id']) {
            $filtros['comunidad_id'] = absint($atts['comunidad_id']);
        }

        if ($atts['sector']) {
            $filtros['sector'] = sanitize_text_field($atts['sector']);
        }

        $empresas = $this->listar_empresas($filtros, absint($atts['limite']));

        ob_start();
        include dirname(__FILE__) . '/frontend/views/listado.php';
        return ob_get_clean();
    }

    public function shortcode_perfil($atts) {
        $atts = shortcode_atts([
            'id' => 0,
        ], $atts);

        $empresa_id = absint($atts['id']) ?: (isset($_GET['empresa_id']) ? absint($_GET['empresa_id']) : 0);

        if (!$empresa_id) {
            return '<p>' . esc_html__('Empresa no especificada.', 'flavor-platform') . '</p>';
        }

        $empresa = $this->obtener_empresa($empresa_id);

        if (!$empresa || $empresa->estado !== 'activa') {
            return '<p>' . esc_html__('Empresa no encontrada.', 'flavor-platform') . '</p>';
        }

        $miembros = $this->obtener_miembros($empresa_id, 'activo');
        $miembro = is_user_logged_in() ? $this->get_miembro($empresa_id) : null;
        $es_admin = $miembro && $miembro->rol === 'admin';
        $empresas_usuario = is_user_logged_in() ? $this->obtener_empresas_usuario() : [];

        ob_start();
        include dirname(__FILE__) . '/frontend/views/perfil.php';
        return ob_get_clean();
    }

    // =========================================================================
    // API REST
    // =========================================================================

    public function register_rest_routes() {
        register_rest_route('flavor/v1', '/empresas', [
            'methods' => 'GET',
            'callback' => [$this, 'api_listar_empresas'],
            'permission_callback' => '__return_true',
        ]);

        register_rest_route('flavor/v1', '/empresas/(?P<id>\d+)', [
            'methods' => 'GET',
            'callback' => [$this, 'api_obtener_empresa'],
            'permission_callback' => '__return_true',
        ]);

        register_rest_route('flavor/v1', '/empresas/mis-empresas', [
            'methods' => 'GET',
            'callback' => [$this, 'api_mis_empresas'],
            'permission_callback' => function() {
                return is_user_logged_in();
            },
        ]);

        register_rest_route('flavor/v1', '/empresas/(?P<id>\d+)/miembros', [
            'methods' => 'GET',
            'callback' => [$this, 'api_obtener_miembros'],
            'permission_callback' => function($request) {
                return $this->es_miembro($request->get_param('id'));
            },
        ]);

        register_rest_route('flavor/v1', '/empresas/(?P<id>\d+)/estadisticas', [
            'methods' => 'GET',
            'callback' => [$this, 'api_estadisticas_empresa'],
            'permission_callback' => function($request) {
                return $this->es_miembro($request->get_param('id'));
            },
        ]);
    }

    public function api_listar_empresas($request) {
        $filtros = [
            'estado' => 'activa',
            'sector' => $request->get_param('sector') ?: '',
            'busqueda' => $request->get_param('s') ?: '',
        ];

        $empresas = $this->listar_empresas($filtros, 50);

        return rest_ensure_response([
            'success' => true,
            'data' => array_map([$this, 'preparar_empresa_para_api'], $empresas),
        ]);
    }

    public function api_obtener_empresa($request) {
        $empresa = $this->obtener_empresa($request->get_param('id'));

        if (!$empresa) {
            return new WP_Error('not_found', __('Empresa no encontrada.', 'flavor-platform'), ['status' => 404]);
        }

        return rest_ensure_response([
            'success' => true,
            'data' => $this->preparar_empresa_para_api($empresa),
        ]);
    }

    public function api_mis_empresas($request) {
        $empresas = $this->obtener_empresas_usuario();

        return rest_ensure_response([
            'success' => true,
            'data' => array_map([$this, 'preparar_empresa_para_api'], $empresas),
        ]);
    }

    public function api_obtener_miembros($request) {
        $miembros = $this->obtener_miembros($request->get_param('id'), 'activo');

        return rest_ensure_response([
            'success' => true,
            'data' => array_map(function($m) {
                return [
                    'id' => (int) $m->id,
                    'user_id' => (int) $m->user_id,
                    'nombre' => $m->display_name,
                    'email' => $m->user_email,
                    'rol' => $m->rol,
                    'cargo' => $m->cargo,
                    'departamento' => $m->departamento,
                ];
            }, $miembros),
        ]);
    }

    public function api_estadisticas_empresa($request) {
        $stats = $this->obtener_estadisticas_empresa($request->get_param('id'));

        return rest_ensure_response([
            'success' => true,
            'data' => $stats,
        ]);
    }

    private function preparar_empresa_para_api($empresa) {
        return [
            'id' => (int) $empresa->id,
            'nombre' => $empresa->nombre,
            'razon_social' => $empresa->razon_social,
            'tipo' => $empresa->tipo,
            'sector' => $empresa->sector,
            'descripcion' => $empresa->descripcion,
            'logo_url' => $empresa->logo_url,
            'email' => $empresa->email,
            'telefono' => $empresa->telefono,
            'web' => $empresa->web,
            'direccion' => $empresa->direccion,
            'ciudad' => $empresa->ciudad,
            'provincia' => $empresa->provincia,
            'estado' => $empresa->estado,
            'rol' => $empresa->rol ?? null,
            'cargo' => $empresa->cargo ?? null,
        ];
    }

    // =========================================================================
    // HERRAMIENTAS IA
    // =========================================================================

    public function get_actions() {
        return [
            'listar_empresas' => [
                'description' => __('Listar empresas con filtros', 'flavor-platform'),
                'params' => ['estado', 'tipo', 'sector', 'busqueda', 'limite'],
            ],
            'obtener_empresa' => [
                'description' => __('Obtener detalles de una empresa', 'flavor-platform'),
                'params' => ['empresa_id'],
            ],
            'mis_empresas' => [
                'description' => __('Obtener empresas del usuario actual', 'flavor-platform'),
                'params' => [],
            ],
            'estadisticas_empresa' => [
                'description' => __('Obtener estadísticas de una empresa', 'flavor-platform'),
                'params' => ['empresa_id'],
            ],
        ];
    }

    public function execute_action($action_name, $params) {
        switch ($action_name) {
            case 'listar_empresas':
            case 'listar':
                $empresas = $this->listar_empresas($params, absint($params['limite'] ?? 20));
                return [
                    'success' => true,
                    'total' => count($empresas),
                    'empresas' => array_map([$this, 'preparar_empresa_para_api'], $empresas),
                ];

            case 'obtener_empresa':
            case 'detalle':
                $empresa = $this->obtener_empresa(absint($params['empresa_id'] ?? 0));
                if (!$empresa) {
                    return ['success' => false, 'error' => __('Empresa no encontrada.', 'flavor-platform')];
                }
                return [
                    'success' => true,
                    'empresa' => $this->preparar_empresa_para_api($empresa),
                ];

            case 'mis_empresas':
                $empresas = $this->obtener_empresas_usuario();
                return [
                    'success' => true,
                    'total' => count($empresas),
                    'empresas' => array_map([$this, 'preparar_empresa_para_api'], $empresas),
                ];

            case 'estadisticas_empresa':
            case 'stats':
                $empresa_id = absint($params['empresa_id'] ?? 0);
                if (!$empresa_id) {
                    return ['success' => false, 'error' => __('ID de empresa requerido.', 'flavor-platform')];
                }
                return [
                    'success' => true,
                    'estadisticas' => $this->obtener_estadisticas_empresa($empresa_id),
                ];

            default:
                return ['success' => false, 'error' => __('Acción no disponible.', 'flavor-platform')];
        }
    }

    public function get_tool_definitions() {
        return [
            [
                'name' => 'empresas_listar',
                'description' => 'Lista empresas registradas con filtros opcionales.',
                'input_schema' => [
                    'type' => 'object',
                    'properties' => [
                        'estado' => ['type' => 'string', 'enum' => ['activa', 'pendiente', 'suspendida', 'baja']],
                        'tipo' => ['type' => 'string'],
                        'sector' => ['type' => 'string'],
                        'limite' => ['type' => 'integer', 'minimum' => 1, 'maximum' => 100],
                    ],
                ],
            ],
            [
                'name' => 'empresas_detalle',
                'description' => 'Obtiene los detalles de una empresa específica.',
                'input_schema' => [
                    'type' => 'object',
                    'properties' => [
                        'empresa_id' => ['type' => 'integer', 'description' => 'ID de la empresa'],
                    ],
                    'required' => ['empresa_id'],
                ],
            ],
            [
                'name' => 'empresas_mis_empresas',
                'description' => 'Obtiene las empresas del usuario actual.',
                'input_schema' => [
                    'type' => 'object',
                    'properties' => [],
                ],
            ],
        ];
    }

    public function get_knowledge_base() {
        $total = $this->contar_empresas(['estado' => 'activa']);
        $pendientes = $this->contar_empresas(['estado' => 'pendiente']);

        return sprintf(
            __("Módulo de Empresas:\n- Empresas activas: %d\n- Solicitudes pendientes: %d\n\nPermite crear y gestionar empresas dentro de comunidades, con su propia contabilidad, facturación y miembros.", 'flavor-platform'),
            $total,
            $pendientes
        );
    }
}

if (!class_exists('Flavor_Chat_Empresas_Module', false)) {
    class_alias('Flavor_Platform_Empresas_Module', 'Flavor_Chat_Empresas_Module');
}

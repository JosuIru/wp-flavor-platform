<?php
/**
 * Control de Acceso por Roles para Admin Assistant
 *
 * Define qué herramientas y acciones están disponibles según el rol del usuario.
 * Los datos sensibles (finanzas, ingresos, datos personales completos) solo
 * están disponibles para administradores y contables.
 *
 * @package ChatIAAddon
 * @since 1.9.0
 */

if (!defined('ABSPATH')) {
    exit;
}

class Chat_IA_Admin_Role_Access {

    /**
     * Instancia singleton
     */
    private static $instance = null;

    /**
     * Configuración de permisos por rol
     */
    private $role_permissions = [];

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
     * Constructor privado
     */
    private function __construct() {
        $this->define_permissions();
        add_action('admin_init', [$this, 'register_settings']);
        add_action('wp_ajax_chat_ia_save_role_permissions', [$this, 'ajax_save_permissions']);
    }

    /**
     * Define los permisos por defecto para cada rol
     */
    private function define_permissions() {
        // Cargar permisos guardados o usar defaults
        $saved_permissions = get_option('chat_ia_role_permissions', []);

        // Definir permisos por defecto
        $defaults = [
            // ==========================================
            // ADMINISTRADOR - Acceso total
            // ==========================================
            'administrator' => [
                'level' => 'full',
                'label' => __('Administrador', 'chat-ia-addon'),
                'description' => __('Acceso completo a todas las funciones', 'chat-ia-addon'),
                'tools' => 'all',
                'shortcuts' => 'all',
                'can_see_finances' => true,
                'can_see_personal_data' => true,
                'can_export_data' => true,
                'can_modify_config' => true,
                'can_manage_backups' => true,
            ],

            // ==========================================
            // CONTABLE - Acceso a finanzas
            // ==========================================
            'contable' => [
                'level' => 'finances',
                'label' => __('Contable', 'chat-ia-addon'),
                'description' => __('Acceso a datos financieros y estadísticas', 'chat-ia-addon'),
                'tools' => [
                    // Consultas permitidas
                    'obtener_reservas_dia',
                    'obtener_plazas_disponibles',
                    'buscar_reservas',
                    'obtener_resumen_periodo',
                    'obtener_resumen_hoy',
                    'obtener_estadisticas_ingresos',
                    'obtener_tickets_mas_vendidos',
                    'obtener_comparativa_periodos',
                    'obtener_proximas_reservas',
                    'obtener_tipos_ticket',
                    'obtener_datos_clientes',
                    'obtener_detalle_cliente',
                    'exportar_datos_csv',
                    'obtener_dashboard_compacto',
                    'obtener_comparativa_rapida',
                    // Solo lectura de configuración
                    'obtener_estado_calendario',
                    'listar_estados_calendario',
                    'obtener_configuracion_sistema',
                ],
                'shortcuts' => [
                    'summary_today',
                    'summary_week',
                    'summary_month',
                    'comparison_yesterday',
                    'comparison_week',
                    'available_today',
                    'available_tomorrow',
                    'next_reservations',
                    'alerts',
                    'test_ping',
                ],
                'can_see_finances' => true,
                'can_see_personal_data' => true, // Necesario para facturas
                'can_export_data' => true,
                'can_modify_config' => false,
                'can_manage_backups' => false,
            ],

            // ==========================================
            // EDITOR - Gestión operativa
            // ==========================================
            'editor' => [
                'level' => 'operations',
                'label' => __('Editor', 'chat-ia-addon'),
                'description' => __('Gestión del calendario y consultas básicas', 'chat-ia-addon'),
                'tools' => [
                    // Consultas básicas
                    'obtener_reservas_dia',
                    'obtener_plazas_disponibles',
                    'obtener_resumen_hoy',
                    'obtener_proximas_reservas',
                    'obtener_estado_calendario',
                    'listar_estados_calendario',
                    'obtener_tipos_ticket',
                    'obtener_shortcodes_disponibles',
                    'generar_shortcode',
                    'explicar_seccion',
                    'obtener_alertas_sistema',
                    // Gestión del calendario
                    'asignar_estado_calendario',
                    'modificar_limite_plazas',
                    'bloquear_ticket',
                    'desbloquear_ticket',
                ],
                'shortcuts' => [
                    'summary_today',
                    'available_today',
                    'available_tomorrow',
                    'next_reservations',
                    'alerts',
                    'set_day_open',
                    'set_day_closed',
                    'set_range_state',
                    'gen_shortcode_calendar',
                    'gen_shortcode_tickets',
                    'gen_shortcode_cart',
                    'test_ping',
                ],
                'can_see_finances' => false,
                'can_see_personal_data' => false,
                'can_export_data' => false,
                'can_modify_config' => false,
                'can_manage_backups' => false,
            ],

            // ==========================================
            // EMPLEADO - Solo consultas
            // ==========================================
            'shop_manager' => [
                'level' => 'limited_operations',
                'label' => __('Gestor de tienda', 'chat-ia-addon'),
                'description' => __('Gestión de reservas y calendario', 'chat-ia-addon'),
                'tools' => [
                    'obtener_reservas_dia',
                    'obtener_plazas_disponibles',
                    'obtener_resumen_hoy',
                    'obtener_proximas_reservas',
                    'obtener_estado_calendario',
                    'listar_estados_calendario',
                    'obtener_tipos_ticket',
                    'obtener_alertas_sistema',
                    'asignar_estado_calendario',
                    'modificar_limite_plazas',
                ],
                'shortcuts' => [
                    'summary_today',
                    'available_today',
                    'available_tomorrow',
                    'next_reservations',
                    'alerts',
                    'set_day_open',
                    'set_day_closed',
                    'test_ping',
                ],
                'can_see_finances' => false,
                'can_see_personal_data' => false,
                'can_export_data' => false,
                'can_modify_config' => false,
                'can_manage_backups' => false,
            ],

            // ==========================================
            // AUTOR - Solo consultas básicas
            // ==========================================
            'author' => [
                'level' => 'basic',
                'label' => __('Autor/Empleado', 'chat-ia-addon'),
                'description' => __('Solo consultas básicas de disponibilidad', 'chat-ia-addon'),
                'tools' => [
                    'obtener_reservas_dia',
                    'obtener_plazas_disponibles',
                    'obtener_proximas_reservas',
                    'obtener_estado_calendario',
                    'listar_estados_calendario',
                    'obtener_tipos_ticket',
                    'obtener_alertas_sistema',
                    'obtener_shortcodes_disponibles',
                    'explicar_seccion',
                ],
                'shortcuts' => [
                    'available_today',
                    'available_tomorrow',
                    'next_reservations',
                    'alerts',
                    'gen_shortcode_calendar',
                    'test_ping',
                ],
                'can_see_finances' => false,
                'can_see_personal_data' => false,
                'can_export_data' => false,
                'can_modify_config' => false,
                'can_manage_backups' => false,
            ],

            // ==========================================
            // COLABORADOR - Mínimo acceso
            // ==========================================
            'contributor' => [
                'level' => 'minimal',
                'label' => __('Colaborador', 'chat-ia-addon'),
                'description' => __('Acceso mínimo - solo información pública', 'chat-ia-addon'),
                'tools' => [
                    'obtener_plazas_disponibles',
                    'obtener_estado_calendario',
                    'obtener_tipos_ticket',
                    'explicar_seccion',
                ],
                'shortcuts' => [
                    'available_today',
                    'test_ping',
                ],
                'can_see_finances' => false,
                'can_see_personal_data' => false,
                'can_export_data' => false,
                'can_modify_config' => false,
                'can_manage_backups' => false,
            ],
        ];

        // Combinar con permisos guardados
        $this->role_permissions = wp_parse_args($saved_permissions, $defaults);
    }

    /**
     * Registra settings para la configuración de permisos
     */
    public function register_settings() {
        register_setting('chat_ia_role_permissions', 'chat_ia_role_permissions', [
            'type' => 'array',
            'sanitize_callback' => [$this, 'sanitize_permissions'],
        ]);
    }

    /**
     * Sanitiza los permisos guardados
     */
    public function sanitize_permissions($input) {
        if (!is_array($input)) {
            return [];
        }

        // Validar estructura básica
        foreach ($input as $role => $permissions) {
            if (!is_array($permissions)) {
                unset($input[$role]);
                continue;
            }

            // Sanitizar cada campo
            $input[$role]['can_see_finances'] = !empty($permissions['can_see_finances']);
            $input[$role]['can_see_personal_data'] = !empty($permissions['can_see_personal_data']);
            $input[$role]['can_export_data'] = !empty($permissions['can_export_data']);
            $input[$role]['can_modify_config'] = !empty($permissions['can_modify_config']);
            $input[$role]['can_manage_backups'] = !empty($permissions['can_manage_backups']);

            if (isset($permissions['tools']) && is_array($permissions['tools'])) {
                $input[$role]['tools'] = array_map('sanitize_text_field', $permissions['tools']);
            }

            if (isset($permissions['shortcuts']) && is_array($permissions['shortcuts'])) {
                $input[$role]['shortcuts'] = array_map('sanitize_text_field', $permissions['shortcuts']);
            }
        }

        return $input;
    }

    /**
     * Verifica si el usuario actual puede usar una herramienta específica
     *
     * @param string $tool_name Nombre de la herramienta
     * @return bool
     */
    public function can_use_tool($tool_name) {
        $user = wp_get_current_user();
        $permissions = $this->get_user_permissions($user);

        if (!$permissions) {
            return false;
        }

        // Administradores tienen acceso total
        if ($permissions['tools'] === 'all') {
            return true;
        }

        // Verificar si la herramienta está en la lista permitida
        if (is_array($permissions['tools'])) {
            return in_array($tool_name, $permissions['tools']);
        }

        return false;
    }

    /**
     * Verifica si el usuario actual puede usar un shortcut específico
     *
     * @param string $shortcut_id ID del shortcut
     * @return bool
     */
    public function can_use_shortcut($shortcut_id) {
        $user = wp_get_current_user();
        $permissions = $this->get_user_permissions($user);

        if (!$permissions) {
            return false;
        }

        // Administradores tienen acceso total
        if ($permissions['shortcuts'] === 'all') {
            return true;
        }

        // Verificar si el shortcut está en la lista permitida
        if (is_array($permissions['shortcuts'])) {
            return in_array($shortcut_id, $permissions['shortcuts']);
        }

        return false;
    }

    /**
     * Verifica si el usuario actual puede ver datos financieros
     *
     * @return bool
     */
    public function can_see_finances() {
        $permissions = $this->get_user_permissions(wp_get_current_user());
        return $permissions && !empty($permissions['can_see_finances']);
    }

    /**
     * Verifica si el usuario actual puede ver datos personales completos
     *
     * @return bool
     */
    public function can_see_personal_data() {
        $permissions = $this->get_user_permissions(wp_get_current_user());
        return $permissions && !empty($permissions['can_see_personal_data']);
    }

    /**
     * Verifica si el usuario actual puede exportar datos
     *
     * @return bool
     */
    public function can_export_data() {
        $permissions = $this->get_user_permissions(wp_get_current_user());
        return $permissions && !empty($permissions['can_export_data']);
    }

    /**
     * Verifica si el usuario actual puede modificar configuración
     *
     * @return bool
     */
    public function can_modify_config() {
        $permissions = $this->get_user_permissions(wp_get_current_user());
        return $permissions && !empty($permissions['can_modify_config']);
    }

    /**
     * Verifica si el usuario actual puede gestionar backups
     *
     * @return bool
     */
    public function can_manage_backups() {
        $permissions = $this->get_user_permissions(wp_get_current_user());
        return $permissions && !empty($permissions['can_manage_backups']);
    }

    /**
     * Obtiene los permisos para un usuario específico
     *
     * @param WP_User $user
     * @return array|null
     */
    public function get_user_permissions($user) {
        if (!$user || !$user->exists()) {
            return null;
        }

        // Buscar el rol con más permisos que tenga el usuario
        $user_roles = $user->roles;

        // Prioridad de roles (de mayor a menor)
        $role_priority = ['administrator', 'contable', 'shop_manager', 'editor', 'author', 'contributor'];

        foreach ($role_priority as $role) {
            if (in_array($role, $user_roles) && isset($this->role_permissions[$role])) {
                return $this->role_permissions[$role];
            }
        }

        // Buscar cualquier rol configurado
        foreach ($user_roles as $role) {
            if (isset($this->role_permissions[$role])) {
                return $this->role_permissions[$role];
            }
        }

        // Sin permisos
        return null;
    }

    /**
     * Obtiene las herramientas permitidas para el usuario actual
     *
     * @return array
     */
    public function get_allowed_tools() {
        $permissions = $this->get_user_permissions(wp_get_current_user());

        if (!$permissions) {
            return [];
        }

        if ($permissions['tools'] === 'all') {
            return 'all';
        }

        return is_array($permissions['tools']) ? $permissions['tools'] : [];
    }

    /**
     * Obtiene los shortcuts permitidos para el usuario actual
     *
     * @return array
     */
    public function get_allowed_shortcuts() {
        $permissions = $this->get_user_permissions(wp_get_current_user());

        if (!$permissions) {
            return [];
        }

        if ($permissions['shortcuts'] === 'all') {
            return 'all';
        }

        return is_array($permissions['shortcuts']) ? $permissions['shortcuts'] : [];
    }

    /**
     * Filtra el resultado de una herramienta según los permisos del usuario
     *
     * @param array $result Resultado de la herramienta
     * @param string $tool_name Nombre de la herramienta
     * @return array Resultado filtrado
     */
    public function filter_tool_result($result, $tool_name) {
        if (!is_array($result) || !isset($result['success']) || !$result['success']) {
            return $result;
        }

        // Si puede ver todo, no filtrar
        if ($this->can_see_finances() && $this->can_see_personal_data()) {
            return $result;
        }

        // Campos financieros a ocultar si no tiene permiso
        $financial_fields = ['ingresos', 'ingresos_estimados', 'precio', 'total', 'importe', 'facturado'];

        // Campos personales a ocultar si no tiene permiso
        $personal_fields = ['email', 'telefono', 'phone', 'nombre_completo', 'direccion', 'dni', 'nif'];

        // Filtrar recursivamente
        $result = $this->filter_array_recursive($result, $financial_fields, $personal_fields);

        return $result;
    }

    /**
     * Filtra un array recursivamente
     */
    private function filter_array_recursive($data, $financial_fields, $personal_fields) {
        if (!is_array($data)) {
            return $data;
        }

        foreach ($data as $key => $value) {
            // Ocultar campos financieros
            if (!$this->can_see_finances() && in_array(strtolower($key), $financial_fields)) {
                $data[$key] = '***';
                continue;
            }

            // Ocultar campos personales
            if (!$this->can_see_personal_data() && in_array(strtolower($key), $personal_fields)) {
                $data[$key] = '***';
                continue;
            }

            // Recursión para arrays anidados
            if (is_array($value)) {
                $data[$key] = $this->filter_array_recursive($value, $financial_fields, $personal_fields);
            }
        }

        return $data;
    }

    /**
     * Obtiene todos los permisos configurados
     *
     * @return array
     */
    public function get_all_permissions() {
        return $this->role_permissions;
    }

    /**
     * Actualiza los permisos de un rol específico
     *
     * @param string $role Nombre del rol
     * @param array $permissions Nuevos permisos
     * @return bool
     */
    public function update_role_permissions($role, $permissions) {
        if (!current_user_can('manage_options')) {
            return false;
        }

        $this->role_permissions[$role] = $permissions;
        return update_option('chat_ia_role_permissions', $this->role_permissions);
    }

    /**
     * Handler AJAX para guardar permisos de roles
     */
    public function ajax_save_permissions() {
        // Verificar nonce
        if (!check_ajax_referer('chat_ia_save_roles', 'nonce', false)) {
            wp_send_json_error(['message' => __('Error de seguridad', 'chat-ia-addon')]);
        }

        // Verificar permisos
        if (!current_user_can('manage_options')) {
            wp_send_json_error(['message' => __('Sin permisos suficientes', 'chat-ia-addon')]);
        }

        // Obtener permisos enviados
        $permissions = isset($_POST['permissions']) ? $_POST['permissions'] : [];

        if (empty($permissions) || !is_array($permissions)) {
            wp_send_json_error(['message' => __('Datos inválidos', 'chat-ia-addon')]);
        }

        // Sanitizar y guardar cada rol
        $saved_permissions = [];
        foreach ($permissions as $role => $role_perms) {
            $role = sanitize_key($role);

            // No permitir modificar administrador
            if ($role === 'administrator') {
                continue;
            }

            // Mantener los valores por defecto del rol
            $defaults = $this->role_permissions[$role] ?? [];

            $saved_permissions[$role] = [
                'level' => $defaults['level'] ?? 'basic',
                'label' => $defaults['label'] ?? ucfirst($role),
                'description' => $defaults['description'] ?? '',
                'can_see_finances' => !empty($role_perms['can_see_finances']),
                'can_see_personal_data' => !empty($role_perms['can_see_personal_data']),
                'can_export_data' => !empty($role_perms['can_export_data']),
                'can_modify_config' => !empty($role_perms['can_modify_config']),
                'can_manage_backups' => !empty($role_perms['can_manage_backups']),
                'tools' => isset($role_perms['tools']) && is_array($role_perms['tools'])
                    ? array_map('sanitize_key', $role_perms['tools'])
                    : [],
                'shortcuts' => isset($role_perms['shortcuts']) && is_array($role_perms['shortcuts'])
                    ? array_map('sanitize_key', $role_perms['shortcuts'])
                    : [],
            ];
        }

        // Guardar en la base de datos
        $result = update_option('chat_ia_role_permissions', $saved_permissions);

        if ($result !== false) {
            // Recargar permisos
            $this->define_permissions();
            wp_send_json_success(['message' => __('Permisos guardados correctamente', 'chat-ia-addon')]);
        } else {
            wp_send_json_error(['message' => __('Error al guardar', 'chat-ia-addon')]);
        }
    }

    /**
     * Obtiene lista de herramientas sensibles (finanzas)
     *
     * @return array
     */
    public function get_financial_tools() {
        return [
            'obtener_estadisticas_ingresos',
            'obtener_tickets_mas_vendidos',
            'obtener_comparativa_periodos',
            'exportar_datos_csv',
            'obtener_dashboard_compacto',
            'obtener_comparativa_rapida',
        ];
    }

    /**
     * Obtiene lista de herramientas que requieren datos personales
     *
     * @return array
     */
    public function get_personal_data_tools() {
        return [
            'obtener_datos_clientes',
            'obtener_detalle_cliente',
            'buscar_reservas', // Puede mostrar datos de clientes
        ];
    }

    /**
     * Obtiene lista de herramientas de modificación
     *
     * @return array
     */
    public function get_modification_tools() {
        return [
            'asignar_estado_calendario',
            'resetear_calendario',
            'crear_estado_calendario',
            'editar_estado_calendario',
            'eliminar_estado_calendario',
            'crear_tipo_ticket',
            'editar_tipo_ticket',
            'eliminar_tipo_ticket',
            'modificar_limite_plazas',
            'bloquear_ticket',
            'desbloquear_ticket',
            'importar_estados_texto',
            'crear_ticket_rapido',
            'actualizar_precio_ticket',
        ];
    }

    /**
     * Obtiene el nivel de acceso del usuario actual
     *
     * @return string
     */
    public function get_current_access_level() {
        $permissions = $this->get_user_permissions(wp_get_current_user());
        return $permissions ? ($permissions['level'] ?? 'minimal') : 'none';
    }

    /**
     * Genera mensaje de error personalizado según el tipo de restricción
     *
     * @param string $restriction_type Tipo de restricción
     * @return string
     */
    public function get_restriction_message($restriction_type) {
        $messages = [
            'tool' => __('No tienes permisos para usar esta herramienta. Contacta con un administrador si necesitas acceso.', 'chat-ia-addon'),
            'shortcut' => __('Este atajo no está disponible para tu rol de usuario.', 'chat-ia-addon'),
            'finances' => __('No tienes permisos para ver información financiera.', 'chat-ia-addon'),
            'personal_data' => __('No tienes permisos para ver datos personales de clientes.', 'chat-ia-addon'),
            'export' => __('No tienes permisos para exportar datos.', 'chat-ia-addon'),
            'config' => __('No tienes permisos para modificar la configuración del sistema.', 'chat-ia-addon'),
            'backups' => __('No tienes permisos para gestionar backups.', 'chat-ia-addon'),
        ];

        return $messages[$restriction_type] ?? __('Acceso denegado.', 'chat-ia-addon');
    }
}

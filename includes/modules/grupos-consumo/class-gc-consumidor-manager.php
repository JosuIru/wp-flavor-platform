<?php
/**
 * Gestor de Consumidores para Grupos de Consumo
 *
 * Gestiona altas, bajas, roles y estado de los miembros del grupo.
 *
 * @package FlavorChatIA
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Clase para gestionar consumidores de grupos de consumo
 */
class Flavor_GC_Consumidor_Manager {

    /**
     * Instancia singleton
     * @var Flavor_GC_Consumidor_Manager|null
     */
    private static $instancia = null;

    /**
     * Tabla de consumidores
     * @var string
     */
    private $tabla_consumidores;

    /**
     * Roles disponibles
     * @var array
     */
    const ROLES = ['consumidor', 'coordinador', 'productor'];

    /**
     * Estados disponibles
     * @var array
     */
    const ESTADOS = ['pendiente', 'activo', 'suspendido', 'baja'];

    /**
     * Constructor privado (singleton)
     */
    private function __construct() {
        global $wpdb;
        $this->tabla_consumidores = $wpdb->prefix . 'flavor_gc_consumidores';

        $this->init_hooks();
    }

    /**
     * Obtiene la instancia singleton
     *
     * @return Flavor_GC_Consumidor_Manager
     */
    public static function get_instance() {
        if (null === self::$instancia) {
            self::$instancia = new self();
        }
        return self::$instancia;
    }

    /**
     * Inicializa hooks
     */
    private function init_hooks() {
        // Registrar roles de WordPress
        add_action('init', [$this, 'registrar_roles_wordpress']);

        // AJAX handlers
        add_action('wp_ajax_gc_alta_consumidor', [$this, 'ajax_alta_consumidor']);
        add_action('wp_ajax_gc_cambiar_estado_consumidor', [$this, 'ajax_cambiar_estado']);
        add_action('wp_ajax_gc_cambiar_rol_consumidor', [$this, 'ajax_cambiar_rol']);
        add_action('wp_ajax_gc_actualizar_preferencias', [$this, 'ajax_actualizar_preferencias']);

        // Hook cuando se elimina un usuario
        add_action('delete_user', [$this, 'on_delete_user']);
    }

    /**
     * Registra roles de WordPress específicos para GC
     */
    public function registrar_roles_wordpress() {
        // Rol: Coordinador de Grupo de Consumo
        if (!get_role('gc_coordinador')) {
            add_role('gc_coordinador', __('Coordinador GC', 'flavor-chat-ia'), [
                'read' => true,
                'gc_gestionar_pedidos' => true,
                'gc_gestionar_consumidores' => true,
                'gc_ver_consolidado' => true,
                'gc_gestionar_ciclos' => true,
            ]);
        }

        // Rol: Productor de Grupo de Consumo
        if (!get_role('gc_productor')) {
            add_role('gc_productor', __('Productor GC', 'flavor-chat-ia'), [
                'read' => true,
                'gc_ver_consolidado' => true,
                'gc_gestionar_productos_propios' => true,
            ]);
        }

        // Añadir capacidades al administrador
        $admin_role = get_role('administrator');
        if ($admin_role) {
            $admin_role->add_cap('gc_gestionar_pedidos');
            $admin_role->add_cap('gc_gestionar_consumidores');
            $admin_role->add_cap('gc_ver_consolidado');
            $admin_role->add_cap('gc_gestionar_ciclos');
            $admin_role->add_cap('gc_gestionar_productos_propios');
            $admin_role->add_cap('gc_gestionar_suscripciones');
            $admin_role->add_cap('gc_exportar_datos');
        }
    }

    /**
     * Da de alta a un consumidor en un grupo
     *
     * @param int    $usuario_id ID del usuario WordPress
     * @param int    $grupo_id   ID del grupo de consumo
     * @param string $rol        Rol inicial (consumidor por defecto)
     * @param array  $datos_extra Datos adicionales (preferencias, alergias, etc.)
     * @return array Resultado de la operación
     */
    public function alta_consumidor($usuario_id, $grupo_id, $rol = 'consumidor', $datos_extra = []) {
        global $wpdb;

        // Validar usuario
        $usuario = get_user_by('ID', $usuario_id);
        if (!$usuario) {
            return [
                'success' => false,
                'error' => __('Usuario no encontrado.', 'flavor-chat-ia'),
            ];
        }

        // Validar grupo
        $grupo = get_post($grupo_id);
        if (!$grupo || $grupo->post_type !== 'gc_grupo') {
            return [
                'success' => false,
                'error' => __('Grupo de consumo no encontrado.', 'flavor-chat-ia'),
            ];
        }

        // Verificar si ya es miembro
        $existente = $this->obtener_consumidor($usuario_id, $grupo_id);
        if ($existente) {
            return [
                'success' => false,
                'error' => __('El usuario ya es miembro de este grupo.', 'flavor-chat-ia'),
            ];
        }

        // Validar rol
        if (!in_array($rol, self::ROLES)) {
            $rol = 'consumidor';
        }

        // Preparar datos
        $datos_insertar = [
            'usuario_id' => $usuario_id,
            'grupo_id' => $grupo_id,
            'rol' => $rol,
            'estado' => 'pendiente',
            'preferencias_alimentarias' => isset($datos_extra['preferencias'])
                ? sanitize_textarea_field($datos_extra['preferencias'])
                : null,
            'alergias' => isset($datos_extra['alergias'])
                ? sanitize_textarea_field($datos_extra['alergias'])
                : null,
            'saldo_pendiente' => 0.00,
            'fecha_alta' => current_time('mysql'),
        ];

        $resultado = $wpdb->insert(
            $this->tabla_consumidores,
            $datos_insertar,
            ['%d', '%d', '%s', '%s', '%s', '%s', '%f', '%s']
        );

        if ($resultado === false) {
            return [
                'success' => false,
                'error' => __('Error al dar de alta al consumidor.', 'flavor-chat-ia'),
            ];
        }

        $consumidor_id = $wpdb->insert_id;

        // Asignar rol de WordPress si es coordinador o productor
        if ($rol === 'coordinador' && !user_can($usuario_id, 'gc_gestionar_consumidores')) {
            $usuario->add_role('gc_coordinador');
        } elseif ($rol === 'productor' && !user_can($usuario_id, 'gc_ver_consolidado')) {
            $usuario->add_role('gc_productor');
        }

        // Disparar acción
        do_action('gc_consumidor_alta', $consumidor_id, $usuario_id, $grupo_id);

        return [
            'success' => true,
            'consumidor_id' => $consumidor_id,
            'mensaje' => __('Consumidor dado de alta correctamente. Estado: pendiente de aprobación.', 'flavor-chat-ia'),
        ];
    }

    /**
     * Obtiene un consumidor por usuario y grupo
     *
     * @param int $usuario_id ID del usuario
     * @param int $grupo_id   ID del grupo
     * @return object|null
     */
    public function obtener_consumidor($usuario_id, $grupo_id) {
        global $wpdb;

        return $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$this->tabla_consumidores}
            WHERE usuario_id = %d AND grupo_id = %d",
            $usuario_id,
            $grupo_id
        ));
    }

    /**
     * Obtiene un consumidor por ID
     *
     * @param int $consumidor_id ID del consumidor
     * @return object|null
     */
    public function obtener_por_id($consumidor_id) {
        global $wpdb;

        return $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$this->tabla_consumidores} WHERE id = %d",
            $consumidor_id
        ));
    }

    /**
     * Lista consumidores de un grupo
     *
     * @param int    $grupo_id ID del grupo
     * @param array  $filtros  Filtros opcionales (estado, rol, busqueda)
     * @param int    $limite   Límite de resultados
     * @param int    $offset   Offset para paginación
     * @return array
     */
    public function listar_consumidores($grupo_id, $filtros = [], $limite = 50, $offset = 0) {
        global $wpdb;

        $where_clauses = ['c.grupo_id = %d'];
        $params = [$grupo_id];

        if (!empty($filtros['estado'])) {
            $where_clauses[] = 'c.estado = %s';
            $params[] = sanitize_text_field($filtros['estado']);
        }

        if (!empty($filtros['rol'])) {
            $where_clauses[] = 'c.rol = %s';
            $params[] = sanitize_text_field($filtros['rol']);
        }

        if (!empty($filtros['busqueda'])) {
            $busqueda = '%' . $wpdb->esc_like(sanitize_text_field($filtros['busqueda'])) . '%';
            $where_clauses[] = '(u.display_name LIKE %s OR u.user_email LIKE %s)';
            $params[] = $busqueda;
            $params[] = $busqueda;
        }

        $where_sql = implode(' AND ', $where_clauses);

        // Contar total
        $total = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM {$this->tabla_consumidores} c
            LEFT JOIN {$wpdb->users} u ON c.usuario_id = u.ID
            WHERE {$where_sql}",
            ...$params
        ));

        // Obtener registros
        $params[] = $limite;
        $params[] = $offset;

        $consumidores = $wpdb->get_results($wpdb->prepare(
            "SELECT c.*, u.display_name, u.user_email, u.user_registered
            FROM {$this->tabla_consumidores} c
            LEFT JOIN {$wpdb->users} u ON c.usuario_id = u.ID
            WHERE {$where_sql}
            ORDER BY c.fecha_alta DESC
            LIMIT %d OFFSET %d",
            ...$params
        ));

        return [
            'consumidores' => $consumidores,
            'total' => (int) $total,
            'paginas' => ceil($total / $limite),
        ];
    }

    /**
     * Cambia el estado de un consumidor
     *
     * @param int    $consumidor_id ID del consumidor
     * @param string $nuevo_estado  Nuevo estado
     * @return array
     */
    public function cambiar_estado($consumidor_id, $nuevo_estado) {
        global $wpdb;

        if (!in_array($nuevo_estado, self::ESTADOS)) {
            return [
                'success' => false,
                'error' => __('Estado no válido.', 'flavor-chat-ia'),
            ];
        }

        $consumidor = $this->obtener_por_id($consumidor_id);
        if (!$consumidor) {
            return [
                'success' => false,
                'error' => __('Consumidor no encontrado.', 'flavor-chat-ia'),
            ];
        }

        $datos_actualizar = ['estado' => $nuevo_estado];
        $formato = ['%s'];

        // Si es baja, registrar fecha
        if ($nuevo_estado === 'baja') {
            $datos_actualizar['fecha_baja'] = current_time('mysql');
            $formato[] = '%s';
        }

        $resultado = $wpdb->update(
            $this->tabla_consumidores,
            $datos_actualizar,
            ['id' => $consumidor_id],
            $formato,
            ['%d']
        );

        if ($resultado === false) {
            return [
                'success' => false,
                'error' => __('Error al actualizar el estado.', 'flavor-chat-ia'),
            ];
        }

        // Disparar acción
        do_action('gc_consumidor_estado_cambiado', $consumidor_id, $nuevo_estado, $consumidor->estado);

        return [
            'success' => true,
            'mensaje' => sprintf(
                __('Estado actualizado a: %s', 'flavor-chat-ia'),
                $this->obtener_etiqueta_estado($nuevo_estado)
            ),
        ];
    }

    /**
     * Cambia el rol de un consumidor
     *
     * @param int    $consumidor_id ID del consumidor
     * @param string $nuevo_rol     Nuevo rol
     * @return array
     */
    public function cambiar_rol($consumidor_id, $nuevo_rol) {
        global $wpdb;

        if (!in_array($nuevo_rol, self::ROLES)) {
            return [
                'success' => false,
                'error' => __('Rol no válido.', 'flavor-chat-ia'),
            ];
        }

        $consumidor = $this->obtener_por_id($consumidor_id);
        if (!$consumidor) {
            return [
                'success' => false,
                'error' => __('Consumidor no encontrado.', 'flavor-chat-ia'),
            ];
        }

        $rol_anterior = $consumidor->rol;

        $resultado = $wpdb->update(
            $this->tabla_consumidores,
            ['rol' => $nuevo_rol],
            ['id' => $consumidor_id],
            ['%s'],
            ['%d']
        );

        if ($resultado === false) {
            return [
                'success' => false,
                'error' => __('Error al actualizar el rol.', 'flavor-chat-ia'),
            ];
        }

        // Actualizar roles de WordPress
        $usuario = get_user_by('ID', $consumidor->usuario_id);
        if ($usuario) {
            // Quitar rol anterior
            if ($rol_anterior === 'coordinador') {
                $usuario->remove_role('gc_coordinador');
            } elseif ($rol_anterior === 'productor') {
                $usuario->remove_role('gc_productor');
            }

            // Añadir nuevo rol
            if ($nuevo_rol === 'coordinador') {
                $usuario->add_role('gc_coordinador');
            } elseif ($nuevo_rol === 'productor') {
                $usuario->add_role('gc_productor');
            }
        }

        // Disparar acción
        do_action('gc_consumidor_rol_cambiado', $consumidor_id, $nuevo_rol, $rol_anterior);

        return [
            'success' => true,
            'mensaje' => sprintf(
                __('Rol actualizado a: %s', 'flavor-chat-ia'),
                $this->obtener_etiqueta_rol($nuevo_rol)
            ),
        ];
    }

    /**
     * Actualiza preferencias de un consumidor
     *
     * @param int   $consumidor_id ID del consumidor
     * @param array $datos         Datos a actualizar
     * @return array
     */
    public function actualizar_preferencias($consumidor_id, $datos) {
        global $wpdb;

        $consumidor = $this->obtener_por_id($consumidor_id);
        if (!$consumidor) {
            return [
                'success' => false,
                'error' => __('Consumidor no encontrado.', 'flavor-chat-ia'),
            ];
        }

        $datos_actualizar = [];
        $formato = [];

        if (isset($datos['preferencias_alimentarias'])) {
            $datos_actualizar['preferencias_alimentarias'] = sanitize_textarea_field($datos['preferencias_alimentarias']);
            $formato[] = '%s';
        }

        if (isset($datos['alergias'])) {
            $datos_actualizar['alergias'] = sanitize_textarea_field($datos['alergias']);
            $formato[] = '%s';
        }

        if (isset($datos['notas_internas']) && current_user_can('gc_gestionar_consumidores')) {
            $datos_actualizar['notas_internas'] = sanitize_textarea_field($datos['notas_internas']);
            $formato[] = '%s';
        }

        if (empty($datos_actualizar)) {
            return [
                'success' => false,
                'error' => __('No hay datos para actualizar.', 'flavor-chat-ia'),
            ];
        }

        $resultado = $wpdb->update(
            $this->tabla_consumidores,
            $datos_actualizar,
            ['id' => $consumidor_id],
            $formato,
            ['%d']
        );

        if ($resultado === false) {
            return [
                'success' => false,
                'error' => __('Error al actualizar preferencias.', 'flavor-chat-ia'),
            ];
        }

        return [
            'success' => true,
            'mensaje' => __('Preferencias actualizadas correctamente.', 'flavor-chat-ia'),
        ];
    }

    /**
     * Actualiza el saldo pendiente de un consumidor
     *
     * @param int   $consumidor_id ID del consumidor
     * @param float $cantidad      Cantidad a añadir (positivo) o restar (negativo)
     * @return array
     */
    public function actualizar_saldo($consumidor_id, $cantidad) {
        global $wpdb;

        $consumidor = $this->obtener_por_id($consumidor_id);
        if (!$consumidor) {
            return [
                'success' => false,
                'error' => __('Consumidor no encontrado.', 'flavor-chat-ia'),
            ];
        }

        $nuevo_saldo = floatval($consumidor->saldo_pendiente) + floatval($cantidad);

        $resultado = $wpdb->update(
            $this->tabla_consumidores,
            ['saldo_pendiente' => $nuevo_saldo],
            ['id' => $consumidor_id],
            ['%f'],
            ['%d']
        );

        if ($resultado === false) {
            return [
                'success' => false,
                'error' => __('Error al actualizar saldo.', 'flavor-chat-ia'),
            ];
        }

        return [
            'success' => true,
            'saldo_anterior' => floatval($consumidor->saldo_pendiente),
            'saldo_nuevo' => $nuevo_saldo,
        ];
    }

    /**
     * Obtiene estadísticas de consumidores por grupo
     *
     * @param int $grupo_id ID del grupo
     * @return array
     */
    public function obtener_estadisticas($grupo_id) {
        global $wpdb;

        $estadisticas = [
            'total' => 0,
            'por_estado' => [],
            'por_rol' => [],
            'altas_mes' => 0,
            'bajas_mes' => 0,
        ];

        // Total por estado
        $por_estado = $wpdb->get_results($wpdb->prepare(
            "SELECT estado, COUNT(*) as cantidad
            FROM {$this->tabla_consumidores}
            WHERE grupo_id = %d
            GROUP BY estado",
            $grupo_id
        ));

        foreach ($por_estado as $item) {
            $estadisticas['por_estado'][$item->estado] = (int) $item->cantidad;
            $estadisticas['total'] += (int) $item->cantidad;
        }

        // Total por rol (solo activos)
        $por_rol = $wpdb->get_results($wpdb->prepare(
            "SELECT rol, COUNT(*) as cantidad
            FROM {$this->tabla_consumidores}
            WHERE grupo_id = %d AND estado = 'activo'
            GROUP BY rol",
            $grupo_id
        ));

        foreach ($por_rol as $item) {
            $estadisticas['por_rol'][$item->rol] = (int) $item->cantidad;
        }

        // Altas del mes
        $primer_dia_mes = date('Y-m-01 00:00:00');
        $estadisticas['altas_mes'] = (int) $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM {$this->tabla_consumidores}
            WHERE grupo_id = %d AND fecha_alta >= %s",
            $grupo_id,
            $primer_dia_mes
        ));

        // Bajas del mes
        $estadisticas['bajas_mes'] = (int) $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM {$this->tabla_consumidores}
            WHERE grupo_id = %d AND fecha_baja >= %s",
            $grupo_id,
            $primer_dia_mes
        ));

        return $estadisticas;
    }

    /**
     * Obtiene etiqueta legible del estado
     *
     * @param string $estado Estado
     * @return string
     */
    public function obtener_etiqueta_estado($estado) {
        $etiquetas = [
            'pendiente' => __('Pendiente', 'flavor-chat-ia'),
            'activo' => __('Activo', 'flavor-chat-ia'),
            'suspendido' => __('Suspendido', 'flavor-chat-ia'),
            'baja' => __('Baja', 'flavor-chat-ia'),
        ];
        return $etiquetas[$estado] ?? $estado;
    }

    /**
     * Obtiene etiqueta legible del rol
     *
     * @param string $rol Rol
     * @return string
     */
    public function obtener_etiqueta_rol($rol) {
        $etiquetas = [
            'consumidor' => __('Consumidor', 'flavor-chat-ia'),
            'coordinador' => __('Coordinador', 'flavor-chat-ia'),
            'productor' => __('Productor', 'flavor-chat-ia'),
        ];
        return $etiquetas[$rol] ?? $rol;
    }

    /**
     * Obtiene clase CSS para el badge de estado
     *
     * @param string $estado Estado
     * @return string
     */
    public function obtener_clase_estado($estado) {
        $clases = [
            'pendiente' => 'gc-estado-pendiente',
            'activo' => 'gc-estado-activo',
            'suspendido' => 'gc-estado-suspendido',
            'baja' => 'gc-estado-baja',
        ];
        return $clases[$estado] ?? 'gc-estado-default';
    }

    /**
     * Verifica si un usuario puede gestionar consumidores
     *
     * @param int|null $usuario_id ID del usuario (null para actual)
     * @return bool
     */
    public function puede_gestionar_consumidores($usuario_id = null) {
        if ($usuario_id === null) {
            return current_user_can('gc_gestionar_consumidores') || current_user_can('manage_options');
        }
        return user_can($usuario_id, 'gc_gestionar_consumidores') || user_can($usuario_id, 'manage_options');
    }

    /**
     * Hook cuando se elimina un usuario de WordPress
     *
     * @param int $usuario_id ID del usuario eliminado
     */
    public function on_delete_user($usuario_id) {
        global $wpdb;

        // Marcar como baja todos los registros del usuario
        $wpdb->update(
            $this->tabla_consumidores,
            [
                'estado' => 'baja',
                'fecha_baja' => current_time('mysql'),
            ],
            ['usuario_id' => $usuario_id],
            ['%s', '%s'],
            ['%d']
        );
    }

    // ========================================
    // AJAX Handlers
    // ========================================

    /**
     * AJAX: Alta de consumidor
     */
    public function ajax_alta_consumidor() {
        check_ajax_referer('gc_admin_nonce', 'nonce');

        if (!$this->puede_gestionar_consumidores()) {
            wp_send_json_error(['mensaje' => __('No tienes permisos.', 'flavor-chat-ia')]);
        }

        $usuario_id = isset($_POST['usuario_id']) ? absint($_POST['usuario_id']) : 0;
        $grupo_id = isset($_POST['grupo_id']) ? absint($_POST['grupo_id']) : 0;
        $rol = isset($_POST['rol']) ? sanitize_text_field($_POST['rol']) : 'consumidor';

        $datos_extra = [
            'preferencias' => isset($_POST['preferencias']) ? $_POST['preferencias'] : '',
            'alergias' => isset($_POST['alergias']) ? $_POST['alergias'] : '',
        ];

        $resultado = $this->alta_consumidor($usuario_id, $grupo_id, $rol, $datos_extra);

        if ($resultado['success']) {
            wp_send_json_success($resultado);
        } else {
            wp_send_json_error($resultado);
        }
    }

    /**
     * AJAX: Cambiar estado
     */
    public function ajax_cambiar_estado() {
        check_ajax_referer('gc_admin_nonce', 'nonce');

        if (!$this->puede_gestionar_consumidores()) {
            wp_send_json_error(['mensaje' => __('No tienes permisos.', 'flavor-chat-ia')]);
        }

        $consumidor_id = isset($_POST['consumidor_id']) ? absint($_POST['consumidor_id']) : 0;
        $nuevo_estado = isset($_POST['estado']) ? sanitize_text_field($_POST['estado']) : '';

        $resultado = $this->cambiar_estado($consumidor_id, $nuevo_estado);

        if ($resultado['success']) {
            wp_send_json_success($resultado);
        } else {
            wp_send_json_error($resultado);
        }
    }

    /**
     * AJAX: Cambiar rol
     */
    public function ajax_cambiar_rol() {
        check_ajax_referer('gc_admin_nonce', 'nonce');

        if (!$this->puede_gestionar_consumidores()) {
            wp_send_json_error(['mensaje' => __('No tienes permisos.', 'flavor-chat-ia')]);
        }

        $consumidor_id = isset($_POST['consumidor_id']) ? absint($_POST['consumidor_id']) : 0;
        $nuevo_rol = isset($_POST['rol']) ? sanitize_text_field($_POST['rol']) : '';

        $resultado = $this->cambiar_rol($consumidor_id, $nuevo_rol);

        if ($resultado['success']) {
            wp_send_json_success($resultado);
        } else {
            wp_send_json_error($resultado);
        }
    }

    /**
     * AJAX: Actualizar preferencias
     */
    public function ajax_actualizar_preferencias() {
        check_ajax_referer('gc_preferencias_nonce', 'nonce');

        if (!is_user_logged_in()) {
            wp_send_json_error(['mensaje' => __('Debes iniciar sesión.', 'flavor-chat-ia')]);
        }

        $consumidor_id = isset($_POST['consumidor_id']) ? absint($_POST['consumidor_id']) : 0;
        $consumidor = $this->obtener_por_id($consumidor_id);

        // Verificar que es el propio usuario o tiene permisos
        if (!$consumidor || ($consumidor->usuario_id !== get_current_user_id() && !$this->puede_gestionar_consumidores())) {
            wp_send_json_error(['mensaje' => __('No tienes permisos.', 'flavor-chat-ia')]);
        }

        $datos = [
            'preferencias_alimentarias' => isset($_POST['preferencias']) ? $_POST['preferencias'] : '',
            'alergias' => isset($_POST['alergias']) ? $_POST['alergias'] : '',
        ];

        $resultado = $this->actualizar_preferencias($consumidor_id, $datos);

        if ($resultado['success']) {
            wp_send_json_success($resultado);
        } else {
            wp_send_json_error($resultado);
        }
    }
}

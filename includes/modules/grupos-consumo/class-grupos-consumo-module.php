<?php
/**
 * Módulo Grupos de Consumo para Chat IA
 *
 * Gestión de pedidos colectivos, productores locales y repartos
 *
 * @package FlavorChatIA
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Módulo de Grupos de Consumo
 * Permite organizar pedidos colectivos de productores locales
 *
 * INTEGRACIONES:
 * - Este modulo es CONSUMER de contenido
 * - Productos y Productores pueden tener recetas, multimedia, videos vinculados
 */
class Flavor_Chat_Grupos_Consumo_Module extends Flavor_Chat_Module_Base {

    use Flavor_Module_Admin_Pages_Trait;
    use Flavor_Module_Notifications_Trait;
    use Flavor_Module_Integration_Consumer;
    use Flavor_Module_Dashboard_Tabs_Trait;

    /**
     * Constructor
     */
    public function __construct() {
        $this->id = 'grupos_consumo';
        $this->name = 'Grupos de Consumo'; // Translation loaded on init
        $this->description = 'Gestión de pedidos colectivos, productores locales y distribución comunitaria.'; // Translation loaded on init
        $this->module_role = 'vertical';
        $this->ecosystem_supports_modules = ['eventos', 'participacion'];
        $this->dashboard_parent_module = 'comunidades';
        $this->dashboard_satellite_priority = 30;
        $this->dashboard_client_contexts = ['consumo', 'comunidad', 'coordinacion'];
        $this->dashboard_admin_contexts = ['consumo', 'gestion', 'admin'];

        // Principios Gailu que implementa este modulo
        $this->gailu_principios = ['economia_local', 'regeneracion'];
        $this->gailu_contribuye_a = ['autonomia', 'impacto'];

        parent::__construct();

        // Registrar como consumidor de integraciones
        $this->register_as_integration_consumer();

        // Cargar clases auxiliares
        $this->cargar_clases_auxiliares();

        // Cargar sistema de pagos
        $this->cargar_sistema_pagos();

        // Cargar API REST para móviles
        $this->cargar_api_rest();

        // Integrar sistema de notificaciones
        $this->init_notifications();

        // Registrar widget de dashboard
        add_action('flavor_register_dashboard_widgets', [$this, 'register_dashboard_widget']);
        // Auto-registered AJAX handlers
        add_action('wp_ajax_grupos_consumo_agregar_a_lista', [$this, 'ajax_agregar_a_lista']);
        add_action('wp_ajax_nopriv_grupos_consumo_agregar_a_lista', [$this, 'ajax_agregar_a_lista']);
        add_action('wp_ajax_grupos_consumo_quitar_de_lista', [$this, 'ajax_quitar_de_lista']);
        add_action('wp_ajax_nopriv_grupos_consumo_quitar_de_lista', [$this, 'ajax_quitar_de_lista']);
        add_action('wp_ajax_grupos_consumo_convertir_lista_en_pedido', [$this, 'ajax_convertir_lista_en_pedido']);
        add_action('wp_ajax_nopriv_grupos_consumo_convertir_lista_en_pedido', [$this, 'ajax_convertir_lista_en_pedido']);
        add_action('wp_ajax_grupos_consumo_solicitar_union', [$this, 'ajax_solicitar_union']);
        add_action('wp_ajax_nopriv_grupos_consumo_solicitar_union', [$this, 'ajax_solicitar_union']);


        // Registrar handlers AJAX para gestión de pedidos
        add_action('wp_ajax_gc_cambiar_estado_pedido', [$this, 'ajax_cambiar_estado_pedido']);
        add_action('wp_ajax_gc_marcar_pedidos_completados', [$this, 'ajax_marcar_pedidos_completados']);

        // Registrar handlers AJAX para lista de compra (frontend)
        add_action('wp_ajax_gc_agregar_lista', [$this, 'ajax_agregar_a_lista']);
        add_action('wp_ajax_gc_quitar_lista', [$this, 'ajax_quitar_de_lista']);
        add_action('wp_ajax_gc_convertir_pedido', [$this, 'ajax_convertir_lista_en_pedido']);

        // Registrar handlers AJAX para gestión de consumidores
        add_action('wp_ajax_gc_alta_consumidor', [$this, 'ajax_alta_consumidor']);
        add_action('wp_ajax_gc_cambiar_estado_consumidor', [$this, 'ajax_cambiar_estado_consumidor']);
        add_action('wp_ajax_gc_cambiar_rol_consumidor', [$this, 'ajax_cambiar_rol_consumidor']);
        add_action('wp_ajax_gc_importar_usuarios_wp', [$this, 'ajax_importar_usuarios_wp']);
        add_action('wp_ajax_gc_listar_usuarios_wp', [$this, 'ajax_listar_usuarios_wp']);
        add_action('wp_ajax_gc_obtener_detalles_consumidor', [$this, 'ajax_obtener_detalles_consumidor']);

        // Cargar scripts y estilos del frontend
        add_action('wp_enqueue_scripts', [$this, 'enqueue_frontend_assets']);

        // Ciclos recurrentes - Programar cron y registrar handler
        add_action('init', [$this, 'programar_cron_ciclos_recurrentes']);
        add_action('gc_verificar_ciclos_recurrentes', [$this, 'verificar_ciclos_recurrentes']);

        // Provisionar grupo principal al crear una comunidad
        add_action('flavor_comunidad_creada', [$this, 'provisionar_grupo_principal_comunidad'], 10, 2);
    }

    /**
     * Encolar assets del frontend
     */
    public function enqueue_frontend_assets() {
        // Solo cargar si estamos en una página relacionada con grupos de consumo
        if (!$this->should_load_frontend_assets()) {
            return;
        }

        $assets_url = FLAVOR_CHAT_IA_URL . 'includes/modules/grupos-consumo/assets/';
        $assets_path = FLAVOR_CHAT_IA_PATH . 'includes/modules/grupos-consumo/assets/';
        $version = FLAVOR_CHAT_IA_VERSION;
        $gc_frontend_version = file_exists($assets_path . 'gc-frontend.js')
            ? (string) filemtime($assets_path . 'gc-frontend.js')
            : $version;

        // CSS del frontend
        if (file_exists($assets_path . 'gc-frontend.css')) {
            wp_enqueue_style(
                'gc-frontend',
                $assets_url . 'gc-frontend.css',
                [],
                $version
            );
        }

        // JavaScript del frontend
        if (file_exists($assets_path . 'gc-frontend.js')) {
            wp_enqueue_script(
                'gc-frontend',
                $assets_url . 'gc-frontend.js',
                ['jquery'],
                $gc_frontend_version,
                true
            );

            $scripts = wp_scripts();
            $gc_frontend_data = $scripts ? (string) $scripts->get_data('gc-frontend', 'data') : '';

            if (strpos($gc_frontend_data, 'var gcFrontend =') === false) {
                wp_localize_script('gc-frontend', 'gcFrontend', [
                    'ajaxUrl'    => admin_url('admin-ajax.php'),
                    'restUrl'    => rest_url('flavor/v1/grupos-consumo/'),
                    'nonce'      => wp_create_nonce('gc_nonce'),
                    'restNonce'  => wp_create_nonce('wp_rest'),
                    'isLoggedIn' => is_user_logged_in(),
                    'loginUrl'   => wp_login_url(home_url('/mi-portal/grupos-consumo/')),
                    'i18n'       => [
                        'agregado'         => __('Producto agregado a la lista', 'flavor-platform'),
                        'eliminado'        => __('Producto eliminado de la lista', 'flavor-platform'),
                        'error'            => __('Ha ocurrido un error', 'flavor-platform'),
                        'confirmarEliminar' => __('¿Eliminar este producto?', 'flavor-platform'),
                        'pedidoCreado'     => __('Pedido creado correctamente', 'flavor-platform'),
                        'cargando'         => __('Cargando...', 'flavor-platform'),
                        'sinProductos'     => __('Tu lista está vacía', 'flavor-platform'),
                    ],
                ]);
            }
        }
    }

    /**
     * Verificar si debemos cargar los assets del frontend
     */
    private function should_load_frontend_assets() {
        // Siempre cargar en páginas del módulo
        if (is_singular(['gc_producto', 'gc_productor', 'gc_grupo'])) {
            return true;
        }

        // Cargar en páginas con shortcodes del módulo
        global $post;
        if ($post && is_a($post, 'WP_Post')) {
            $shortcodes_gc = ['gc_productos', 'gc_catalogo', 'gc_pedido', 'gc_carrito', 'grupos_consumo'];
            foreach ($shortcodes_gc as $shortcode) {
                if (has_shortcode($post->post_content, $shortcode)) {
                    return true;
                }
            }
        }

        // Cargar en rutas propias del modulo dentro del portal
        $request_uri = $_SERVER['REQUEST_URI'] ?? '';
        $request_path = (string) wp_parse_url($request_uri, PHP_URL_PATH);

        if (preg_match('#^/grupos-consumo(?:/|$)#', $request_path)) {
            return true;
        }

        return false;
    }

    /**
     * Tipos de contenido que este modulo acepta de otros modulos
     * @return array IDs de providers aceptados
     */
    protected function get_accepted_integrations() {
        return ['recetas', 'multimedia', 'podcast', 'biblioteca'];
    }

    /**
     * Entidades donde mostrar las integraciones
     * @return array Configuracion de targets
     */
    protected function get_integration_targets() {
        return [
            [
                'type'      => 'post',
                'post_type' => 'gc_producto',
                'context'   => 'side',
                'label'     => __('Contenido del Producto', 'flavor-platform'),
            ],
            [
                'type'      => 'post',
                'post_type' => 'gc_productor',
                'context'   => 'normal',
                'label'     => __('Contenido del Productor', 'flavor-platform'),
            ],
        ];
    }

    // =========================================================================
    // TABS DEL DASHBOARD (SISTEMA FLEXIBLE)
    // =========================================================================

    /**
     * Define los tabs del dashboard para Mi Portal
     *
     * Este método usa el sistema flexible de tabs que permite:
     * - Definir contenido como shortcode, template o callable
     * - Añadir badges con contadores
     * - Filtrar por capabilities
     *
     * @return array Configuración de tabs
     */
    protected function define_dashboard_tabs() {
        $usuario_id = get_current_user_id();

        return [
            'catalogo' => [
                'label'    => __('Catálogo', 'flavor-platform'),
                'icon'     => 'dashicons-products',
                'content'  => '[gc_productos vista="catalogo" limit="12"]',
                'priority' => 10,
            ],
            'pedidos' => [
                'label'    => __('Historial', 'flavor-platform'),
                'icon'     => 'dashicons-cart',
                'content'  => '[gc_mis_pedidos]',
                'priority' => 20,
                'cap'      => 'read',
                'badge'    => [$this, 'contar_pedidos_pendientes'],
            ],
            'productores' => [
                'label'    => __('Productores', 'flavor-platform'),
                'icon'     => 'dashicons-groups',
                'content'  => '[gc_productores vista="grid" limit="12"]',
                'priority' => 30,
            ],
            'ciclos' => [
                'label'    => __('Ciclos', 'flavor-platform'),
                'icon'     => 'dashicons-calendar-alt',
                'content'  => '[gc_ciclos vista="listado"]',
                'priority' => 40,
            ],
        ];
    }

    // =========================================================================
    // AJAX: LISTA DE COMPRA (FRONTEND)
    // =========================================================================

    /**
     * AJAX: Agregar producto a la lista de compra
     */
    public function ajax_agregar_a_lista() {
        if (!wp_verify_nonce($_POST['nonce'] ?? '', 'gc_nonce')) {
            wp_send_json_error(['message' => __('Sesión expirada. Recarga la página.', 'flavor-platform')]);
        }

        if (!is_user_logged_in()) {
            wp_send_json_error(['message' => __('Debes iniciar sesión para añadir productos.', 'flavor-platform')]);
        }

        $producto_id = absint($_POST['producto_id'] ?? 0);
        $cantidad = max(1, min(99, absint($_POST['cantidad'] ?? 1)));

        if (!$producto_id) {
            wp_send_json_error(['message' => __('Producto no válido.', 'flavor-platform')]);
        }

        // Verificar que el producto existe
        $producto = get_post($producto_id);
        if (!$producto || $producto->post_type !== 'gc_producto') {
            wp_send_json_error(['message' => __('Producto no encontrado.', 'flavor-platform')]);
        }

        // Obtener lista actual del usuario
        $user_id = get_current_user_id();
        $lista = get_user_meta($user_id, 'gc_lista_compra', true);
        if (!is_array($lista)) {
            $lista = [];
        }

        // Agregar o actualizar cantidad
        $lista[$producto_id] = $cantidad;

        // Guardar
        update_user_meta($user_id, 'gc_lista_compra', $lista);

        wp_send_json_success([
            'message' => sprintf(__('%s añadido al pedido.', 'flavor-platform'), $producto->post_title),
            'lista_count' => count($lista),
        ]);
    }

    /**
     * AJAX: Quitar producto de la lista de compra
     */
    public function ajax_quitar_de_lista() {
        if (!wp_verify_nonce($_POST['nonce'] ?? '', 'gc_nonce')) {
            wp_send_json_error(['message' => __('Sesión expirada. Recarga la página.', 'flavor-platform')]);
        }

        if (!is_user_logged_in()) {
            wp_send_json_error(['message' => __('Debes iniciar sesión.', 'flavor-platform')]);
        }

        $producto_id = absint($_POST['producto_id'] ?? 0);

        if (!$producto_id) {
            wp_send_json_error(['message' => __('Producto no válido.', 'flavor-platform')]);
        }

        $user_id = get_current_user_id();
        $lista = get_user_meta($user_id, 'gc_lista_compra', true);
        if (!is_array($lista)) {
            $lista = [];
        }

        // Quitar producto
        unset($lista[$producto_id]);

        // Guardar
        update_user_meta($user_id, 'gc_lista_compra', $lista);

        wp_send_json_success([
            'message' => __('Producto eliminado de la lista.', 'flavor-platform'),
            'lista_count' => count($lista),
        ]);
    }

    /**
     * AJAX: Convertir lista de compra en pedido
     */
    public function ajax_convertir_lista_en_pedido() {
        if (!wp_verify_nonce($_POST['nonce'] ?? '', 'gc_nonce')) {
            wp_send_json_error(['message' => __('Sesión expirada. Recarga la página.', 'flavor-platform')]);
        }

        if (!is_user_logged_in()) {
            wp_send_json_error(['message' => __('Debes iniciar sesión.', 'flavor-platform')]);
        }

        $user_id = get_current_user_id();
        $lista = get_user_meta($user_id, 'gc_lista_compra', true);

        if (!is_array($lista) || empty($lista)) {
            wp_send_json_error(['message' => __('Tu lista de compra está vacía.', 'flavor-platform')]);
        }

        // Convertir la lista en formato de productos para el pedido
        $productos = [];
        foreach ($lista as $producto_id => $cantidad) {
            $productos[] = [
                'producto_id' => $producto_id,
                'cantidad'    => $cantidad,
            ];
        }

        // Usar la función existente de hacer pedido
        $resultado = $this->action_hacer_pedido(['productos' => $productos]);

        if ($resultado['success']) {
            // Limpiar la lista de compra
            delete_user_meta($user_id, 'gc_lista_compra');

            wp_send_json_success([
                'message' => $resultado['mensaje'] ?? __('Pedido realizado correctamente.', 'flavor-platform'),
            ]);
        } else {
            wp_send_json_error(['message' => $resultado['error'] ?? __('Error al crear el pedido.', 'flavor-platform')]);
        }
    }

    // =========================================================================
    // AJAX: GESTIÓN DE PEDIDOS (ADMIN)
    // =========================================================================

    /**
     * AJAX: Cambiar estado de un pedido individual
     */
    public function ajax_cambiar_estado_pedido() {
        if (!wp_verify_nonce($_POST['nonce'] ?? '', 'gc_pedidos_nonce')) {
            wp_send_json_error(__('Nonce inválido', 'flavor-platform'));
        }

        if (!current_user_can('gc_gestionar_pedidos') && !current_user_can('manage_options')) {
            wp_send_json_error(__('Sin permisos', 'flavor-platform'));
        }

        global $wpdb;
        $tabla_pedidos = $wpdb->prefix . 'flavor_gc_pedidos';

        $pedido_id = absint($_POST['pedido_id'] ?? 0);
        $nuevo_estado = sanitize_text_field($_POST['estado'] ?? '');

        $estados_validos = ['pendiente', 'confirmado', 'completado', 'cancelado', 'sin_stock'];

        if (!$pedido_id || !in_array($nuevo_estado, $estados_validos)) {
            wp_send_json_error(__('Datos inválidos', 'flavor-platform'));
        }

        $resultado = $wpdb->update(
            $tabla_pedidos,
            ['estado' => $nuevo_estado],
            ['id' => $pedido_id],
            ['%s'],
            ['%d']
        );

        if ($resultado !== false) {
            wp_send_json_success(['estado' => $nuevo_estado]);
        } else {
            wp_send_json_error(__('Error al actualizar', 'flavor-platform'));
        }
    }

    /**
     * AJAX: Marcar todos los pedidos de un usuario como completados
     */
    public function ajax_marcar_pedidos_completados() {
        if (!wp_verify_nonce($_POST['nonce'] ?? '', 'gc_pedidos_nonce')) {
            wp_send_json_error(__('Nonce inválido', 'flavor-platform'));
        }

        if (!current_user_can('gc_gestionar_pedidos') && !current_user_can('manage_options')) {
            wp_send_json_error(__('Sin permisos', 'flavor-platform'));
        }

        global $wpdb;
        $tabla_pedidos = $wpdb->prefix . 'flavor_gc_pedidos';

        $usuario_id = absint($_POST['usuario_id'] ?? 0);
        $ciclo_id = absint($_POST['ciclo_id'] ?? 0);

        if (!$usuario_id) {
            wp_send_json_error(__('Usuario no especificado', 'flavor-platform'));
        }

        $where = ['usuario_id = %d'];
        $params = [$usuario_id];

        if ($ciclo_id) {
            $where[] = 'ciclo_id = %d';
            $params[] = $ciclo_id;
        }

        $where_sql = implode(' AND ', $where);

        $resultado = $wpdb->query($wpdb->prepare(
            "UPDATE {$tabla_pedidos} SET estado = 'completado' WHERE {$where_sql}",
            ...$params
        ));

        if ($resultado !== false) {
            wp_send_json_success(['updated' => $resultado]);
        } else {
            wp_send_json_error(__('Error al actualizar', 'flavor-platform'));
        }
    }

    /**
     * AJAX: Alta de nuevo consumidor
     */
    public function ajax_alta_consumidor() {
        if (!wp_verify_nonce($_POST['gc_admin_nonce'] ?? '', 'gc_admin_nonce')) {
            wp_send_json_error(['error' => __('Nonce inválido', 'flavor-platform')]);
        }

        if (!current_user_can('gc_gestionar_consumidores') && !current_user_can('manage_options')) {
            wp_send_json_error(['error' => __('Sin permisos', 'flavor-platform')]);
        }

        $grupo_id = absint($_POST['grupo_id'] ?? 0);
        $usuario_id = absint($_POST['usuario_id'] ?? 0);
        $rol = sanitize_text_field($_POST['rol'] ?? 'consumidor');
        $preferencias = sanitize_textarea_field($_POST['preferencias'] ?? '');
        $alergias = sanitize_textarea_field($_POST['alergias'] ?? '');

        if (!$grupo_id || !$usuario_id) {
            wp_send_json_error(['error' => __('Datos requeridos faltantes', 'flavor-platform')]);
        }

        global $wpdb;
        $tabla_consumidores = $wpdb->prefix . 'flavor_gc_consumidores';

        // Verificar si ya existe
        $existe = $wpdb->get_var($wpdb->prepare(
            "SELECT id FROM {$tabla_consumidores} WHERE grupo_id = %d AND usuario_id = %d",
            $grupo_id,
            $usuario_id
        ));

        if ($existe) {
            wp_send_json_error(['error' => __('Este usuario ya es miembro del grupo', 'flavor-platform')]);
        }

        $resultado = $wpdb->insert(
            $tabla_consumidores,
            [
                'grupo_id' => $grupo_id,
                'usuario_id' => $usuario_id,
                'rol' => $rol,
                'estado' => 'activo',
                'preferencias_alimentarias' => $preferencias,
                'alergias' => $alergias,
                'fecha_alta' => current_time('mysql'),
            ],
            ['%d', '%d', '%s', '%s', '%s', '%s', '%s']
        );

        if ($resultado) {
            wp_send_json_success(['mensaje' => __('Consumidor añadido correctamente', 'flavor-platform')]);
        } else {
            wp_send_json_error(['error' => __('Error al añadir consumidor', 'flavor-platform')]);
        }
    }

    /**
     * AJAX: Cambiar estado de consumidor
     */
    public function ajax_cambiar_estado_consumidor() {
        if (!wp_verify_nonce($_POST['nonce'] ?? '', 'gc_admin_nonce')) {
            wp_send_json_error(['error' => __('Nonce inválido', 'flavor-platform')]);
        }

        if (!current_user_can('gc_gestionar_consumidores') && !current_user_can('manage_options')) {
            wp_send_json_error(['error' => __('Sin permisos', 'flavor-platform')]);
        }

        $consumidor_id = absint($_POST['consumidor_id'] ?? 0);
        $nuevo_estado = sanitize_text_field($_POST['estado'] ?? '');

        $estados_validos = ['pendiente', 'activo', 'suspendido', 'baja'];

        if (!$consumidor_id || !in_array($nuevo_estado, $estados_validos)) {
            wp_send_json_error(['error' => __('Datos inválidos', 'flavor-platform')]);
        }

        global $wpdb;
        $tabla_consumidores = $wpdb->prefix . 'flavor_gc_consumidores';

        $resultado = $wpdb->update(
            $tabla_consumidores,
            ['estado' => $nuevo_estado],
            ['id' => $consumidor_id],
            ['%s'],
            ['%d']
        );

        if ($resultado !== false) {
            wp_send_json_success(['estado' => $nuevo_estado]);
        } else {
            wp_send_json_error(['error' => __('Error al actualizar estado', 'flavor-platform')]);
        }
    }

    /**
     * AJAX: Cambiar rol de consumidor (soporta múltiples roles separados por coma)
     */
    public function ajax_cambiar_rol_consumidor() {
        if (!wp_verify_nonce($_POST['nonce'] ?? '', 'gc_admin_nonce')) {
            wp_send_json_error(['error' => __('Nonce inválido', 'flavor-platform')]);
        }

        if (!current_user_can('gc_gestionar_consumidores') && !current_user_can('manage_options')) {
            wp_send_json_error(['error' => __('Sin permisos', 'flavor-platform')]);
        }

        $consumidor_id = absint($_POST['consumidor_id'] ?? 0);
        $roles_input = sanitize_text_field($_POST['rol'] ?? '');

        if (!$consumidor_id || empty($roles_input)) {
            wp_send_json_error(['error' => __('Datos inválidos', 'flavor-platform')]);
        }

        // Validar cada rol
        $roles_validos = ['consumidor', 'coordinador', 'productor'];
        $roles_solicitados = array_map('trim', explode(',', $roles_input));
        $roles_finales = [];

        foreach ($roles_solicitados as $rol) {
            if (in_array($rol, $roles_validos)) {
                $roles_finales[] = $rol;
            }
        }

        // Si no hay roles válidos, usar "consumidor" por defecto
        if (empty($roles_finales)) {
            $roles_finales = ['consumidor'];
        }

        $roles_string = implode(',', $roles_finales);

        global $wpdb;
        $tabla_consumidores = $wpdb->prefix . 'flavor_gc_consumidores';

        $resultado = $wpdb->update(
            $tabla_consumidores,
            ['rol' => $roles_string],
            ['id' => $consumidor_id],
            ['%s'],
            ['%d']
        );

        if ($resultado !== false) {
            wp_send_json_success(['rol' => $roles_string, 'roles' => $roles_finales]);
        } else {
            wp_send_json_error(['error' => __('Error al actualizar rol', 'flavor-platform')]);
        }
    }

    /**
     * AJAX: Importar usuarios de WordPress como consumidores
     */
    public function ajax_importar_usuarios_wp() {
        if (!wp_verify_nonce($_POST['nonce'] ?? '', 'gc_admin_nonce')) {
            wp_send_json_error(['error' => __('Nonce inválido', 'flavor-platform')]);
        }

        if (!current_user_can('gc_gestionar_consumidores') && !current_user_can('manage_options')) {
            wp_send_json_error(['error' => __('Sin permisos', 'flavor-platform')]);
        }

        $grupo_id = absint($_POST['grupo_id'] ?? 0);
        $usuarios_ids = isset($_POST['usuarios']) ? array_map('absint', $_POST['usuarios']) : [];

        if (!$grupo_id) {
            wp_send_json_error(['error' => __('Grupo no especificado', 'flavor-platform')]);
        }

        if (empty($usuarios_ids)) {
            wp_send_json_error(['error' => __('No se seleccionaron usuarios', 'flavor-platform')]);
        }

        global $wpdb;
        $tabla_consumidores = $wpdb->prefix . 'flavor_gc_consumidores';

        $importados = 0;
        $omitidos = 0;

        foreach ($usuarios_ids as $usuario_id) {
            // Verificar si ya existe
            $existe = $wpdb->get_var($wpdb->prepare(
                "SELECT id FROM {$tabla_consumidores} WHERE grupo_id = %d AND usuario_id = %d",
                $grupo_id,
                $usuario_id
            ));

            if ($existe) {
                $omitidos++;
                continue;
            }

            $resultado = $wpdb->insert(
                $tabla_consumidores,
                [
                    'grupo_id' => $grupo_id,
                    'usuario_id' => $usuario_id,
                    'rol' => 'consumidor',
                    'estado' => 'activo',
                    'fecha_alta' => current_time('mysql'),
                ],
                ['%d', '%d', '%s', '%s', '%s']
            );

            if ($resultado) {
                $importados++;
            }
        }

        wp_send_json_success([
            'mensaje' => sprintf(
                __('%d usuarios importados, %d ya existían en el grupo', 'flavor-platform'),
                $importados,
                $omitidos
            ),
            'importados' => $importados,
            'omitidos' => $omitidos,
        ]);
    }

    /**
     * AJAX: Listar usuarios de WordPress para importar
     */
    public function ajax_listar_usuarios_wp() {
        if (!wp_verify_nonce($_POST['nonce'] ?? '', 'gc_admin_nonce')) {
            wp_send_json_error(['error' => __('Nonce inválido', 'flavor-platform')]);
        }

        if (!current_user_can('gc_gestionar_consumidores') && !current_user_can('manage_options')) {
            wp_send_json_error(['error' => __('Sin permisos', 'flavor-platform')]);
        }

        $grupo_id = absint($_POST['grupo_id'] ?? 0);

        global $wpdb;
        $tabla_consumidores = $wpdb->prefix . 'flavor_gc_consumidores';

        // Obtener IDs de usuarios ya en el grupo
        $usuarios_existentes = [];
        if ($grupo_id) {
            $usuarios_existentes = $wpdb->get_col($wpdb->prepare(
                "SELECT usuario_id FROM {$tabla_consumidores} WHERE grupo_id = %d",
                $grupo_id
            ));
        }

        // Obtener todos los usuarios de WordPress
        $args_usuarios = [
            'exclude' => $usuarios_existentes,
            'orderby' => 'display_name',
            'order' => 'ASC',
            'number' => 100,
        ];

        $usuarios_wp = get_users($args_usuarios);

        $lista_usuarios = [];
        foreach ($usuarios_wp as $usuario) {
            $lista_usuarios[] = [
                'id' => $usuario->ID,
                'nombre' => $usuario->display_name,
                'email' => $usuario->user_email,
            ];
        }

        wp_send_json_success(['usuarios' => $lista_usuarios]);
    }

    /**
     * AJAX: Obtener detalles de un consumidor
     */
    public function ajax_obtener_detalles_consumidor() {
        if (!wp_verify_nonce($_POST['nonce'] ?? '', 'gc_admin_nonce')) {
            wp_send_json_error(['error' => __('Nonce inválido', 'flavor-platform')]);
        }

        if (!current_user_can('gc_gestionar_consumidores') && !current_user_can('manage_options')) {
            wp_send_json_error(['error' => __('Sin permisos', 'flavor-platform')]);
        }

        $consumidor_id = absint($_POST['consumidor_id'] ?? 0);

        if (!$consumidor_id) {
            wp_send_json_error(['error' => __('ID de consumidor no especificado', 'flavor-platform')]);
        }

        global $wpdb;
        $tabla_consumidores = $wpdb->prefix . 'flavor_gc_consumidores';
        $tabla_pedidos = $wpdb->prefix . 'flavor_gc_pedidos';

        // Obtener datos del consumidor
        $consumidor = $wpdb->get_row($wpdb->prepare(
            "SELECT c.id, c.usuario_id, c.estado, c.rol, c.fecha_registro, u.display_name, u.user_email
             FROM {$tabla_consumidores} c
             LEFT JOIN {$wpdb->users} u ON c.usuario_id = u.ID
             WHERE c.id = %d",
            $consumidor_id
        ));

        if (!$consumidor) {
            wp_send_json_error(['error' => __('Consumidor no encontrado', 'flavor-platform')]);
        }

        // Obtener total de pedidos
        $total_pedidos = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM {$tabla_pedidos} WHERE usuario_id = %d",
            $consumidor->usuario_id
        ));

        // Obtener teléfono del usuario (meta)
        $telefono = get_user_meta($consumidor->usuario_id, 'billing_phone', true);
        if (!$telefono) {
            $telefono = get_user_meta($consumidor->usuario_id, 'phone', true);
        }

        // Labels para estados y roles
        $estados_labels = [
            'pendiente' => __('Pendiente', 'flavor-platform'),
            'activo' => __('Activo', 'flavor-platform'),
            'suspendido' => __('Suspendido', 'flavor-platform'),
            'baja' => __('Baja', 'flavor-platform'),
        ];

        $roles_labels = [
            'consumidor' => __('Consumidor', 'flavor-platform'),
            'coordinador' => __('Coordinador', 'flavor-platform'),
            'productor' => __('Productor', 'flavor-platform'),
        ];

        $datos = [
            'id' => $consumidor->id,
            'nombre' => $consumidor->display_name,
            'email' => $consumidor->user_email,
            'telefono' => $telefono,
            'avatar' => get_avatar_url($consumidor->usuario_id, ['size' => 160]),
            'estado' => $consumidor->estado,
            'estado_label' => $estados_labels[$consumidor->estado] ?? $consumidor->estado,
            'rol' => $consumidor->rol,
            'rol_label' => $roles_labels[$consumidor->rol] ?? $consumidor->rol,
            'fecha_alta' => date_i18n(get_option('date_format'), strtotime($consumidor->fecha_alta)),
            'saldo' => number_format($consumidor->saldo_pendiente ?? 0, 2),
            'preferencias' => esc_html($consumidor->preferencias_alimentarias ?? ''),
            'alergias' => esc_html($consumidor->alergias ?? ''),
            'total_pedidos' => intval($total_pedidos),
        ];

        wp_send_json_success(['consumidor' => $datos]);
    }

    /**
     * Inicializar sistema de notificaciones
     */
    private function init_notifications() {
        // Hooks para notificaciones de pedidos
        add_action('gc_order_created', [$this, 'notify_order_created'], 10, 2);
        add_action('gc_order_confirmed', [$this, 'notify_order_confirmed'], 10, 2);
        add_action('gc_order_ready', [$this, 'notify_order_ready'], 10, 2);
        add_action('gc_order_reminder', [$this, 'notify_pickup_reminder'], 10, 2);
        add_action('gc_new_product', [$this, 'notify_new_product'], 10, 2);
    }

    /**
     * Notificación: Pedido creado
     */
    public function notify_order_created($order_id, $user_id) {
        if (!class_exists('Flavor_Notification_Center')) {
            return;
        }

        $nc = Flavor_Notification_Center::get_instance();

        $nc->send(
            $user_id,
            __('Pedido registrado', 'flavor-platform'),
            sprintf(__('Tu pedido #%d ha sido registrado correctamente.', 'flavor-platform'), $order_id),
            [
                'module_id' => $this->id,
                'type' => 'success',
                'link' => home_url("/mi-portal/grupos-consumo/mis-pedidos?order={$order_id}"),
                'metadata' => [
                    'order_id' => $order_id,
                    'action' => 'order_created'
                ]
            ]
        );
    }

    /**
     * Notificación: Pedido confirmado
     */
    public function notify_order_confirmed($order_id, $user_id) {
        if (!class_exists('Flavor_Notification_Center')) {
            return;
        }

        $nc = Flavor_Notification_Center::get_instance();

        $nc->send(
            $user_id,
            __('Pedido confirmado', 'flavor-platform'),
            sprintf(__('Tu pedido #%d ha sido confirmado por el coordinador.', 'flavor-platform'), $order_id),
            [
                'module_id' => $this->id,
                'type' => 'success',
                'link' => home_url("/mi-portal/grupos-consumo/mis-pedidos?order={$order_id}"),
                'metadata' => [
                    'order_id' => $order_id,
                    'action' => 'order_confirmed'
                ]
            ]
        );
    }

    /**
     * Notificación: Pedido listo para recoger
     */
    public function notify_order_ready($order_id, $user_id) {
        if (!class_exists('Flavor_Notification_Center')) {
            return;
        }

        $nc = Flavor_Notification_Center::get_instance();

        $nc->send(
            $user_id,
            __('Pedido listo para recoger', 'flavor-platform'),
            sprintf(__('Tu pedido #%d ya está listo. Pasa a recogerlo cuando puedas.', 'flavor-platform'), $order_id),
            [
                'module_id' => $this->id,
                'type' => 'info',
                'link' => home_url("/mi-portal/grupos-consumo/mis-pedidos?order={$order_id}"),
                'metadata' => [
                    'order_id' => $order_id,
                    'action' => 'order_ready'
                ]
            ]
        );
    }

    /**
     * Notificación: Recordatorio de recogida (24h antes)
     */
    public function notify_pickup_reminder($order_id, $user_id) {
        if (!class_exists('Flavor_Notification_Center')) {
            return;
        }

        $nc = Flavor_Notification_Center::get_instance();

        $nc->send(
            $user_id,
            __('Recordatorio de recogida', 'flavor-platform'),
            sprintf(__('Recuerda recoger tu pedido #%d mañana. No olvides traer tus bolsas reutilizables.', 'flavor-platform'), $order_id),
            [
                'module_id' => $this->id,
                'type' => 'warning',
                'link' => home_url("/mi-portal/grupos-consumo/mis-pedidos?order={$order_id}"),
                'metadata' => [
                    'order_id' => $order_id,
                    'action' => 'pickup_reminder'
                ]
            ]
        );
    }

    /**
     * Notificación: Nuevo producto disponible (envío masivo)
     */
    public function notify_new_product($product_id, $user_ids = []) {
        if (!class_exists('Flavor_Notification_Center')) {
            return;
        }

        if (empty($user_ids)) {
            // Obtener todos los miembros activos del grupo
            $user_ids = $this->get_active_members();
        }

        $nc = Flavor_Notification_Center::get_instance();
        $product = get_post($product_id);

        if (!$product) {
            return;
        }

        $messages = [];
        foreach ($user_ids as $user_id) {
            $messages[] = [
                'user_id' => $user_id,
                'title' => __('Nuevo producto disponible', 'flavor-platform'),
                'message' => sprintf(
                    __('Ya puedes pedir: %s. ¡No te quedes sin tu parte!', 'flavor-platform'),
                    $product->post_title
                ),
                'options' => [
                    'module_id' => $this->id,
                    'type' => 'info',
                    'link' => home_url("/mi-portal/grupos-consumo/productos?product={$product_id}"),
                    'metadata' => [
                        'product_id' => $product_id,
                        'action' => 'new_product'
                    ]
                ]
            ];
        }

        // Envío masivo
        $nc->send_bulk($messages);
    }

    /**
     * Obtener miembros activos del grupo
     */
    private function get_active_members() {
        global $wpdb;
        $table_name = $wpdb->prefix . 'gc_consumidores';

        $user_ids = $wpdb->get_col(
            "SELECT user_id FROM {$table_name} WHERE estado = 'activo'"
        );

        return array_map('intval', $user_ids);
    }

    /**
     * Carga las clases auxiliares del módulo
     */
    private function cargar_clases_auxiliares() {
        $base_dir = dirname(__FILE__);

        // Clases principales
        $clases = [
            'class-gc-consumidor-manager.php',
            'class-gc-membership.php',
            'class-gc-subscriptions.php',
            'class-gc-dashboard-tab.php',
            'class-gc-notification-channels.php',
            'class-gc-export.php',
            'class-gc-conciencia-features.php', // v4.2.0: Sello de Conciencia
            'class-gc-facturas-integration.php', // Integracion con modulo de Facturas
        ];

        foreach ($clases as $clase) {
            $ruta_archivo = $base_dir . '/' . $clase;
            if (file_exists($ruta_archivo)) {
                require_once $ruta_archivo;
            }
        }

        // Cargar frontend controller
        $frontend_controller = $base_dir . '/frontend/class-gc-frontend-controller.php';
        if (file_exists($frontend_controller)) {
            require_once $frontend_controller;
        }

        // Inicializar singletons
        if (class_exists('Flavor_GC_Consumidor_Manager')) {
            Flavor_GC_Consumidor_Manager::get_instance();
        }
        if (class_exists('Flavor_GC_Subscriptions')) {
            Flavor_GC_Subscriptions::get_instance();
        }
        if (class_exists('Flavor_GC_Dashboard_Tab')) {
            Flavor_GC_Dashboard_Tab::get_instance();
        }
        if (class_exists('Flavor_GC_Notification_Channels')) {
            Flavor_GC_Notification_Channels::get_instance();
        }
        if (class_exists('Flavor_GC_Export')) {
            Flavor_GC_Export::get_instance();
        }
        if (class_exists('Flavor_GC_Membership')) {
            Flavor_GC_Membership::get_instance();
        }
        if (class_exists('Flavor_GC_Frontend_Controller')) {
            Flavor_GC_Frontend_Controller::get_instance();
        }
        // v4.2.0: Funcionalidades Sello de Conciencia
        if (class_exists('Flavor_GC_Conciencia_Features')) {
            Flavor_GC_Conciencia_Features::get_instance();
        }
    }

    /**
     * Carga la API REST
     */
    private function cargar_api_rest() {
        $api_file = dirname(__FILE__) . '/class-grupos-consumo-api.php';
        if (file_exists($api_file)) {
            require_once $api_file;
            // Inicializar API en el contexto REST apropiado
            add_action('rest_api_init', function() {
                if (class_exists('Flavor_Grupos_Consumo_API')) {
                    Flavor_Grupos_Consumo_API::get_instance();
                }
            });
        }
    }

    /**
     * {@inheritdoc}
     */
    public function can_activate() {
        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function get_activation_error() {
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
    protected function get_default_settings() {
        return [
            'dias_anticipacion_pedido' => 7,
            'hora_cierre_pedidos' => '23:59',
            'permitir_modificar_pedido' => true,
            'horas_limite_modificacion' => 24,
            'porcentaje_gestion' => 5, // % para gastos del grupo
            'requiere_aprobacion_productores' => true,
            'notificar_nuevos_productos' => true,
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function init() {
        // Crear tablas si no existen
        $this->crear_tablas_si_no_existen();

        // Registrar Custom Post Types
        add_action('init', [$this, 'registrar_custom_post_types']);
        add_action('init', [$this, 'registrar_taxonomias']);
        add_action('init', [$this, 'maybe_flush_rewrite_rules'], 99);

        // Meta Boxes
        add_action('add_meta_boxes', [$this, 'registrar_meta_boxes']);
        add_action('save_post_gc_productor', [$this, 'guardar_meta_productor']);
        add_action('save_post_gc_producto', [$this, 'guardar_meta_producto']);
        add_action('save_post_gc_ciclo', [$this, 'guardar_meta_ciclo']);

        // Columnas admin
        add_filter('manage_gc_ciclo_posts_columns', [$this, 'columnas_ciclo']);
        add_action('manage_gc_ciclo_posts_custom_column', [$this, 'contenido_columnas_ciclo'], 10, 2);
        add_filter('manage_gc_producto_posts_columns', [$this, 'columnas_producto']);
        add_action('manage_gc_producto_posts_custom_column', [$this, 'contenido_columnas_producto'], 10, 2);

        // Estados personalizados para ciclos
        add_action('init', [$this, 'registrar_estados_ciclo']);

        // Shortcodes
        $this->register_shortcodes();
        // gc_formulario_union se registra en class-gc-membership.php

        // Registrar en Panel Unificado de Gestión
        $this->registrar_en_panel_unificado();

        // Páginas de admin (menú propio legacy - se mantiene por compatibilidad)
        add_action('admin_menu', [$this, 'registrar_paginas_admin']);

        // AJAX
        add_action('wp_ajax_gc_hacer_pedido', [$this, 'ajax_hacer_pedido']);
        add_action('wp_ajax_gc_modificar_pedido', [$this, 'ajax_modificar_pedido']);
        // AJAX de solicitudes se registra en class-gc-membership.php

        // Assets
        add_action('wp_enqueue_scripts', [$this, 'enqueue_assets']);

        // Refrescar/crear páginas GC después de que $wp_rewrite esté disponible
        // (wp_insert_post requiere $wp_rewrite inicializado)
        add_action('init', [$this, 'maybe_create_gc_pages'], 99);

        // Cron para cerrar ciclos automáticamente
        add_action('gc_cerrar_ciclos_automatico', [$this, 'cerrar_ciclos_automatico']);
        if (!wp_next_scheduled('gc_cerrar_ciclos_automatico')) {
            wp_schedule_event(time(), 'hourly', 'gc_cerrar_ciclos_automatico');
        }
    }

    /**
     * Registra shortcodes del módulo
     */
    public function register_shortcodes() {
        $shortcodes = [
            'gc_ciclo_actual' => 'shortcode_ciclo_actual',
            'gc_productos' => 'shortcode_productos',
            'gc_mi_pedido' => 'shortcode_mi_pedido',
            'gc_catalogo' => 'shortcode_catalogo',
            'gc_carrito' => 'shortcode_carrito',
            'gc_calendario' => 'shortcode_calendario',
            'gc_historial' => 'shortcode_historial',
            'gc_suscripciones' => 'shortcode_suscripciones',
            'gc_mi_cesta' => 'shortcode_mi_cesta',
            'gc_grupos_lista' => 'shortcode_grupos_lista',
            'gc_productores_cercanos' => 'shortcode_productores_cercanos',
            'gc_panel' => 'shortcode_panel',
            'gc_nav' => 'shortcode_nav',
            // Aliases para compatibilidad con tabs del dashboard
            'gc_mis_pedidos' => 'shortcode_historial',
            'gc_productores' => 'shortcode_productores_cercanos',
            'gc_ciclos' => 'shortcode_ciclo_actual',
        ];

        foreach ($shortcodes as $tag => $method) {
            if (!shortcode_exists($tag)) {
                add_shortcode($tag, [$this, $method]);
            }
        }
    }

    /**
     * Flush de rewrite una sola vez cuando cambia el slug de CPTs GC.
     */
    public function maybe_flush_rewrite_rules() {
        if (get_option('flavor_gc_rewrite_flushed')) {
            return;
        }
        flush_rewrite_rules(false);
        update_option('flavor_gc_rewrite_flushed', 1, false);
    }

    /**
     * Crea/actualiza páginas de GC si es necesario.
     * Se ejecuta en hook init para que $wp_rewrite esté disponible.
     */
    public function maybe_create_gc_pages() {
        if (!class_exists('Flavor_Page_Creator')) {
            return;
        }

        // En admin: refrescar páginas del módulo
        if (is_admin()) {
            Flavor_Page_Creator::refresh_module_pages('grupos_consumo');
            return;
        }

        // En frontend: crear páginas si no existen (solo una vez)
        $pagina_gc = get_page_by_path('grupos-consumo');
        if (!$pagina_gc && !get_option('flavor_gc_pages_created')) {
            Flavor_Page_Creator::create_pages_for_modules(['grupos_consumo']);
            update_option('flavor_gc_pages_created', 1, false);
        }
    }

    /**
     * Shortcode: Panel resumen de Grupos de Consumo
     */
    public function shortcode_panel() {
        $user_id = get_current_user_id();
        $has_user = $user_id > 0;

        $ciclo = $this->obtener_ciclo_activo();
        $total_productores = wp_count_posts('gc_productor')->publish ?? 0;
        $total_productos = wp_count_posts('gc_producto')->publish ?? 0;
        $alertas = [];

        if ($ciclo) {
            $cierre_ts = $ciclo['fecha_cierre'] ? strtotime($ciclo['fecha_cierre']) : 0;
            $entrega_ts = $ciclo['fecha_entrega'] ? strtotime($ciclo['fecha_entrega']) : 0;
            $ahora = current_time('timestamp');
            if ($cierre_ts && ($cierre_ts - $ahora) <= 48 * HOUR_IN_SECONDS && ($cierre_ts - $ahora) > 0) {
                $alertas[] = __('El ciclo cierra en menos de 48 horas.', 'flavor-platform');
            }
            if ($entrega_ts && ($entrega_ts - $ahora) <= 24 * HOUR_IN_SECONDS && ($entrega_ts - $ahora) > 0) {
                $alertas[] = __('La entrega es en menos de 24 horas.', 'flavor-platform');
            }
        }

        $pedidos_usuario = 0;
        $suscripciones_usuario = 0;
        $gasto_total = 0.0;
        $ticket_medio = 0.0;
        $pedidos_mes = 0;
        $importe_mes = 0.0;
        $importe_mes_anterior = 0.0;
        $variacion_mes = 0.0;
        $ultimo_ciclo = null;
        $importe_ultimo_ciclo = 0.0;
        $importe_ciclo_activo = 0.0;

        if ($has_user) {
            global $wpdb;
            $tabla_pedidos = $wpdb->prefix . 'flavor_gc_pedidos';
            $tabla_consumidores = $wpdb->prefix . 'flavor_gc_consumidores';
            $tabla_suscripciones = $wpdb->prefix . 'flavor_gc_suscripciones';

            if (Flavor_Chat_Helpers::tabla_existe($tabla_pedidos)) {
                $pedidos_usuario = (int) $wpdb->get_var($wpdb->prepare(
                    "SELECT COUNT(*) FROM {$tabla_pedidos} WHERE usuario_id = %d",
                    $user_id
                ));
                $gasto_total = (float) $wpdb->get_var($wpdb->prepare(
                    "SELECT COALESCE(SUM(cantidad * precio_unitario), 0) FROM {$tabla_pedidos} WHERE usuario_id = %d",
                    $user_id
                ));
                $ticket_medio = $pedidos_usuario > 0 ? ($gasto_total / $pedidos_usuario) : 0.0;

                $inicio_mes = gmdate('Y-m-01 00:00:00');
                $pedidos_mes = (int) $wpdb->get_var($wpdb->prepare(
                    "SELECT COUNT(*) FROM {$tabla_pedidos} WHERE usuario_id = %d AND fecha_pedido >= %s",
                    $user_id,
                    $inicio_mes
                ));
                $importe_mes = (float) $wpdb->get_var($wpdb->prepare(
                    "SELECT COALESCE(SUM(cantidad * precio_unitario), 0) FROM {$tabla_pedidos} WHERE usuario_id = %d AND fecha_pedido >= %s",
                    $user_id,
                    $inicio_mes
                ));

                $inicio_mes_anterior = gmdate('Y-m-01 00:00:00', strtotime('-1 month'));
                $fin_mes_anterior = gmdate('Y-m-t 23:59:59', strtotime('-1 month'));
                $importe_mes_anterior = (float) $wpdb->get_var($wpdb->prepare(
                    "SELECT COALESCE(SUM(cantidad * precio_unitario), 0) FROM {$tabla_pedidos} WHERE usuario_id = %d AND fecha_pedido BETWEEN %s AND %s",
                    $user_id,
                    $inicio_mes_anterior,
                    $fin_mes_anterior
                ));

                if ($importe_mes_anterior > 0) {
                    $variacion_mes = (($importe_mes - $importe_mes_anterior) / $importe_mes_anterior) * 100;
                } elseif ($importe_mes > 0) {
                    $variacion_mes = 100;
                }

                $ultimo_ciclo_id = (int) $wpdb->get_var($wpdb->prepare(
                    "SELECT ciclo_id FROM {$tabla_pedidos} WHERE usuario_id = %d GROUP BY ciclo_id ORDER BY MAX(fecha_pedido) DESC LIMIT 1",
                    $user_id
                ));
                if ($ultimo_ciclo_id) {
                    $ultimo_post = get_post($ultimo_ciclo_id);
                    $ultimo_ciclo = $ultimo_post ? $ultimo_post->post_title : null;
                    $importe_ultimo_ciclo = (float) $wpdb->get_var($wpdb->prepare(
                        "SELECT COALESCE(SUM(cantidad * precio_unitario), 0) FROM {$tabla_pedidos} WHERE usuario_id = %d AND ciclo_id = %d",
                        $user_id,
                        $ultimo_ciclo_id
                    ));
                }

                if ($ciclo) {
                    $importe_ciclo_activo = (float) $wpdb->get_var($wpdb->prepare(
                        "SELECT COALESCE(SUM(cantidad * precio_unitario), 0) FROM {$tabla_pedidos} WHERE usuario_id = %d AND ciclo_id = %d",
                        $user_id,
                        $ciclo['id']
                    ));
                }
            }

            $consumidor_id = (int) $wpdb->get_var($wpdb->prepare(
                "SELECT id FROM {$tabla_consumidores} WHERE usuario_id = %d LIMIT 1",
                $user_id
            ));

            if ($consumidor_id) {
                $suscripciones_usuario = (int) $wpdb->get_var($wpdb->prepare(
                    "SELECT COUNT(*) FROM {$tabla_suscripciones} WHERE consumidor_id = %d",
                    $consumidor_id
                ));
            }
        }

        $links = [
            [
                'label' => __('Ver productos', 'flavor-platform'),
                'url' => home_url('/mi-portal/grupos-consumo/productos/'),
            ],
            [
                'label' => __('Mi cesta', 'flavor-platform'),
                'url' => home_url('/mi-portal/grupos-consumo/mi-cesta/'),
            ],
            [
                'label' => __('Pedido actual', 'flavor-platform'),
                'url' => home_url('/mi-portal/grupos-consumo/mi-pedido/'),
            ],
            [
                'label' => __('Historial', 'flavor-platform'),
                'url' => home_url('/mi-portal/grupos-consumo/mis-pedidos/'),
            ],
            [
                'label' => __('Suscripciones', 'flavor-platform'),
                'url' => home_url('/mi-portal/grupos-consumo/suscripciones/'),
            ],
        ];

        ob_start();
        ?>
        <div class="gc-panel">
            <?php if (!$has_user): ?>
                <div class="gc-panel-alerts">
                    <div class="gc-panel-alert gc-panel-alert-info">
                        <?php _e('Inicia sesión para ver tu actividad y gestionar tus pedidos.', 'flavor-platform'); ?>
                    </div>
                </div>
            <?php endif; ?>
            <?php if (!empty($alertas)): ?>
                <div class="gc-panel-alerts">
                    <?php foreach ($alertas as $alerta): ?>
                        <div class="gc-panel-alert"><?php echo esc_html($alerta); ?></div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
            <div class="gc-panel-kpis">
                <div class="gc-panel-card">
                    <span class="gc-panel-label"><?php _e('Productores', 'flavor-platform'); ?></span>
                    <strong class="gc-panel-value"><?php echo number_format_i18n($total_productores); ?></strong>
                </div>
                <div class="gc-panel-card">
                    <span class="gc-panel-label"><?php _e('Productos', 'flavor-platform'); ?></span>
                    <strong class="gc-panel-value"><?php echo number_format_i18n($total_productos); ?></strong>
                </div>
                <div class="gc-panel-card">
                    <span class="gc-panel-label"><?php _e('Mis pedidos', 'flavor-platform'); ?></span>
                    <strong class="gc-panel-value"><?php echo number_format_i18n($pedidos_usuario); ?></strong>
                </div>
                <div class="gc-panel-card">
                    <span class="gc-panel-label"><?php _e('Mis suscripciones', 'flavor-platform'); ?></span>
                    <strong class="gc-panel-value"><?php echo number_format_i18n($suscripciones_usuario); ?></strong>
                </div>
                <div class="gc-panel-card">
                    <span class="gc-panel-label"><?php _e('Gasto total', 'flavor-platform'); ?></span>
                    <strong class="gc-panel-value"><?php echo number_format_i18n($gasto_total, 2); ?> €</strong>
                </div>
                <div class="gc-panel-card">
                    <span class="gc-panel-label"><?php _e('Ticket medio', 'flavor-platform'); ?></span>
                    <strong class="gc-panel-value"><?php echo number_format_i18n($ticket_medio, 2); ?> €</strong>
                </div>
                <div class="gc-panel-card">
                    <span class="gc-panel-label"><?php _e('Pedidos este mes', 'flavor-platform'); ?></span>
                    <strong class="gc-panel-value"><?php echo number_format_i18n($pedidos_mes); ?></strong>
                </div>
                <div class="gc-panel-card">
                    <span class="gc-panel-label"><?php _e('Facturación este mes', 'flavor-platform'); ?></span>
                    <strong class="gc-panel-value"><?php echo number_format_i18n($importe_mes, 2); ?> €</strong>
                </div>
            </div>

            <div class="gc-panel-section">
                <h3><?php _e('Ciclo actual', 'flavor-platform'); ?></h3>
                <?php if ($ciclo): ?>
                    <div class="gc-panel-ciclo">
                        <p><strong><?php echo esc_html($ciclo['titulo']); ?></strong></p>
                        <p><?php printf(__('Cierre: %s', 'flavor-platform'), esc_html($ciclo['fecha_cierre'])); ?></p>
                        <p><?php printf(__('Entrega: %s', 'flavor-platform'), esc_html($ciclo['fecha_entrega'])); ?></p>
                        <?php if (!empty($ciclo['lugar_entrega'])): ?>
                            <p><?php printf(__('Lugar: %s', 'flavor-platform'), esc_html($ciclo['lugar_entrega'])); ?></p>
                        <?php endif; ?>
                    </div>
                <?php else: ?>
                    <p class="gc-panel-muted"><?php _e('No hay ciclo abierto en este momento.', 'flavor-platform'); ?></p>
                <?php endif; ?>
            </div>

            <div class="gc-panel-section">
                <h3><?php _e('Comparativa mensual', 'flavor-platform'); ?></h3>
                <?php if ($has_user && $pedidos_usuario > 0): ?>
                    <p class="gc-panel-muted">
                        <?php _e('Variación respecto al mes anterior', 'flavor-platform'); ?>:
                        <strong class="gc-panel-trend <?php echo $variacion_mes >= 0 ? 'gc-trend-up' : 'gc-trend-down'; ?>">
                            <?php echo number_format_i18n($variacion_mes, 1); ?>%
                        </strong>
                    </p>
                <?php else: ?>
                    <p class="gc-panel-muted"><?php _e('Aún no hay suficientes datos para comparar.', 'flavor-platform'); ?></p>
                <?php endif; ?>
            </div>

            <div class="gc-panel-section">
                <h3><?php _e('Último ciclo', 'flavor-platform'); ?></h3>
                <?php if ($ultimo_ciclo): ?>
                    <p><strong><?php echo esc_html($ultimo_ciclo); ?></strong></p>
                    <p><?php printf(__('Importe: %s', 'flavor-platform'), number_format_i18n($importe_ultimo_ciclo, 2) . ' €'); ?></p>
                <?php else: ?>
                    <p class="gc-panel-muted"><?php _e('Aún no tienes pedidos cerrados.', 'flavor-platform'); ?></p>
                <?php endif; ?>
            </div>

            <?php if ($ciclo): ?>
                <div class="gc-panel-section">
                    <h3><?php _e('Pedido actual en el ciclo activo', 'flavor-platform'); ?></h3>
                    <p><?php printf(__('Importe: %s', 'flavor-platform'), number_format_i18n($importe_ciclo_activo, 2) . ' €'); ?></p>
                </div>
            <?php endif; ?>

            <div class="gc-panel-actions">
                <?php foreach ($links as $link): ?>
                    <a class="gc-btn gc-btn-primary" href="<?php echo esc_url($link['url']); ?>">
                        <?php echo esc_html($link['label']); ?>
                    </a>
                <?php endforeach; ?>
            </div>
        </div>
        <?php
        return ob_get_clean();
    }

    /**
     * Shortcode: Navegación rápida para páginas GC.
     *
     * @return string
     */
    public function shortcode_nav() {
        $items = [
            ['label' => __('Inicio', 'flavor-platform'), 'path' => 'grupos-consumo'],
            ['label' => __('Panel', 'flavor-platform'), 'path' => 'grupos-consumo/panel'],
            ['label' => __('Catálogo', 'flavor-platform'), 'path' => 'grupos-consumo/productos'],
            ['label' => __('Mi cesta', 'flavor-platform'), 'path' => 'grupos-consumo/mi-cesta'],
            ['label' => __('Pedido actual', 'flavor-platform'), 'path' => 'grupos-consumo/mi-pedido'],
            ['label' => __('Mis pedidos', 'flavor-platform'), 'path' => 'grupos-consumo/mis-pedidos'],
            ['label' => __('Suscripciones', 'flavor-platform'), 'path' => 'grupos-consumo/suscripciones'],
            ['label' => __('Productores', 'flavor-platform'), 'path' => 'grupos-consumo/productores-cercanos'],
            ['label' => __('Ciclo', 'flavor-platform'), 'path' => 'grupos-consumo/ciclo'],
            ['label' => __('Unirme', 'flavor-platform'), 'path' => 'grupos-consumo/unirme'],
        ];

        $current = trim(parse_url(home_url(add_query_arg([])), PHP_URL_PATH), '/');
        $links = [];
        foreach ($items as $item) {
            $url = home_url('/mi-portal/' . trim($item['path'], '/') . '/');
            $is_active = $current === trim(parse_url($url, PHP_URL_PATH), '/');
            $links[] = [
                'label' => $item['label'],
                'url' => $url,
                'active' => $is_active,
            ];
        }

        if (empty($links)) {
            return '';
        }

        ob_start();
        ?>
        <nav class="gc-nav" aria-label="<?php echo esc_attr__('Navegación Grupos de Consumo', 'flavor-platform'); ?>">
            <?php foreach ($links as $link): ?>
                <a class="gc-nav-link <?php echo $link['active'] ? 'is-active' : ''; ?>" href="<?php echo esc_url($link['url']); ?>">
                    <?php echo esc_html($link['label']); ?>
                </a>
            <?php endforeach; ?>
        </nav>
        <?php
        return ob_get_clean();
    }

    /**
     * Obtiene ciclo activo (abierto) con datos básicos.
     *
     * @return array|null
     */
    private function obtener_ciclo_activo() {
        $ciclos = get_posts([
            'post_type' => 'gc_ciclo',
            'post_status' => 'gc_abierto',
            'posts_per_page' => 1,
            'orderby' => 'date',
            'order' => 'DESC',
        ]);

        if (empty($ciclos)) {
            return null;
        }

        $ciclo = $ciclos[0];
        return [
            'id' => $ciclo->ID,
            'titulo' => $ciclo->post_title,
            'fecha_cierre' => get_post_meta($ciclo->ID, '_gc_fecha_cierre', true),
            'fecha_entrega' => get_post_meta($ciclo->ID, '_gc_fecha_entrega', true),
            'lugar_entrega' => get_post_meta($ciclo->ID, '_gc_lugar_entrega', true),
        ];
    }

    /**
     * Configuración para el Panel Unificado de Gestión
     * Define las páginas de admin del módulo que aparecerán en el menú "Gestión"
     *
     * @return array Configuración del módulo
     */
    protected function get_admin_config() {
        return [
            'id' => 'grupos_consumo',
            'label' => __('Grupos de Consumo', 'flavor-platform'),
            'icon' => 'dashicons-carrot',
            'capability' => 'manage_options',
            'categoria' => 'comunidad', // personas|economia|operaciones|recursos|comunicacion|actividades|servicios|comunidad|sostenibilidad
            'paginas' => [
                [
                    'slug' => 'gc-dashboard',
                    'titulo' => __('Dashboard', 'flavor-platform'),
                    'callback' => [$this, 'render_pagina_dashboard'],
                ],
                [
                    'slug' => 'gc-consumidores',
                    'titulo' => __('Consumidores', 'flavor-platform'),
                    'callback' => [$this, 'render_pagina_consumidores'],
                ],
                [
                    'slug' => 'gc-solicitudes',
                    'titulo' => __('Solicitudes', 'flavor-platform'),
                    'callback' => [$this, 'render_pagina_solicitudes'],
                    'badge' => [$this, 'contar_solicitudes_pendientes'],
                ],
                [
                    'slug' => 'gc-pedidos',
                    'titulo' => __('Pedidos', 'flavor-platform'),
                    'callback' => [$this, 'render_pagina_pedidos'],
                    'badge' => [$this, 'contar_pedidos_pendientes'],
                ],
                [
                    'slug' => 'gc-suscripciones',
                    'titulo' => __('Suscripciones', 'flavor-platform'),
                    'callback' => [$this, 'render_pagina_suscripciones'],
                ],
                [
                    'slug' => 'gc-consolidado',
                    'titulo' => __('Consolidado', 'flavor-platform'),
                    'callback' => [$this, 'render_pagina_consolidado'],
                ],
                [
                    'slug' => 'gc-reportes',
                    'titulo' => __('Reportes', 'flavor-platform'),
                    'callback' => [$this, 'render_pagina_reportes'],
                ],
                [
                    'slug' => 'gc-configuracion',
                    'titulo' => __('Configuración', 'flavor-platform'),
                    'callback' => [$this, 'render_pagina_configuracion'],
                ],
                [
                    'slug' => 'gc-pagos',
                    'titulo' => __('Pagos', 'flavor-platform'),
                    'callback' => [$this, 'render_pagina_pagos'],
                    'icon' => 'dashicons-money-alt',
                ],
            ],
            'estadisticas' => [$this, 'get_estadisticas_dashboard'],
        ];
    }

    /**
     * Cuenta solicitudes pendientes para el badge
     *
     * @return int
     */
    public function contar_solicitudes_pendientes() {
        if (class_exists('Flavor_GC_Membership')) {
            return Flavor_GC_Membership::get_instance()->contar_solicitudes_pendientes();
        }
        return 0;
    }

    /**
     * Cuenta pedidos pendientes para el badge
     *
     * @return int
     */
    public function contar_pedidos_pendientes() {
        // Verificar que el módulo esté activo
        if (!$this->can_activate()) {
            return 0;
        }

        global $wpdb;
        $tabla = $wpdb->prefix . 'flavor_gc_pedidos';
        if (!Flavor_Chat_Helpers::tabla_existe($tabla)) {
            return 0;
        }
        return (int) $wpdb->get_var("SELECT COUNT(*) FROM $tabla WHERE estado = 'pendiente'");
    }

    /**
     * Devuelve estadísticas para el dashboard unificado
     *
     * @return array
     */
    public function get_estadisticas_dashboard() {
        global $wpdb;
        $tabla_pedidos = $wpdb->prefix . 'flavor_gc_pedidos';
        $tabla_consumidores = $wpdb->prefix . 'flavor_gc_consumidores';

        $stats = [];

        // Pedidos pendientes
        if (Flavor_Chat_Helpers::tabla_existe($tabla_pedidos)) {
            $pendientes = (int) $wpdb->get_var("SELECT COUNT(*) FROM $tabla_pedidos WHERE estado = 'pendiente'");
            $stats[] = [
                'icon' => 'dashicons-clipboard',
                'valor' => $pendientes,
                'label' => __('Pedidos pendientes', 'flavor-platform'),
                'color' => $pendientes > 0 ? 'orange' : 'green',
                'enlace' => admin_url('admin.php?page=gc-pedidos'),
            ];
        }

        // Consumidores activos
        if (Flavor_Chat_Helpers::tabla_existe($tabla_consumidores)) {
            $activos = (int) $wpdb->get_var("SELECT COUNT(*) FROM $tabla_consumidores WHERE estado = 'activo'");
            $stats[] = [
                'icon' => 'dashicons-groups',
                'valor' => $activos,
                'label' => __('Consumidores activos', 'flavor-platform'),
                'color' => 'blue',
                'enlace' => admin_url('admin.php?page=gc-consumidores'),
            ];
        }

        // Ciclo activo
        $ciclo_activo = get_posts([
            'post_type' => 'gc_ciclo',
            'post_status' => 'gc_abierto',
            'posts_per_page' => 1,
        ]);
        $stats[] = [
            'icon' => 'dashicons-calendar-alt',
            'valor' => count($ciclo_activo) > 0 ? __('Activo', 'flavor-platform') : __('Cerrado', 'flavor-platform'),
            'label' => __('Ciclo de pedidos', 'flavor-platform'),
            'color' => count($ciclo_activo) > 0 ? 'green' : 'red',
            'enlace' => admin_url('edit.php?post_type=gc_ciclo'),
        ];

        return $stats;
    }

    /**
     * Crea las tablas necesarias si no existen
     */
    private function crear_tablas_si_no_existen() {
        global $wpdb;

        // Verificar si la tabla principal ya existe (indicador de que ya se instalo)
        $tabla_pedidos = $wpdb->prefix . 'flavor_gc_pedidos';
        $tabla_lista_compra = $wpdb->prefix . 'flavor_gc_lista_compra';

        // Si ambas tablas existen, ya esta instalado
        if (Flavor_Chat_Helpers::tabla_existe($tabla_pedidos) &&
            Flavor_Chat_Helpers::tabla_existe($tabla_lista_compra)) {
            return;
        }

        // Cargar install.php completo si existe
        $install_file = dirname(__FILE__) . '/install.php';
        if (file_exists($install_file)) {
            require_once $install_file;
            if (function_exists('flavor_grupos_consumo_install')) {
                flavor_grupos_consumo_install();
                return;
            }
        }

        // Fallback: crear tabla basica de pedidos
        $charset_collate = $wpdb->get_charset_collate();
        $sql = "CREATE TABLE $tabla_pedidos (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            ciclo_id bigint(20) unsigned NOT NULL,
            usuario_id bigint(20) unsigned NOT NULL,
            producto_id bigint(20) unsigned NOT NULL,
            cantidad decimal(10,2) NOT NULL,
            precio_unitario decimal(10,2) NOT NULL,
            fecha_pedido datetime DEFAULT CURRENT_TIMESTAMP,
            estado varchar(20) DEFAULT 'pendiente',
            notas text,
            PRIMARY KEY  (id),
            KEY ciclo_id (ciclo_id),
            KEY usuario_id (usuario_id),
            KEY producto_id (producto_id)
        ) $charset_collate;";

        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);
    }

    // Método enqueue_assets está al final del archivo

    /**
     * Registra Custom Post Types
     */
    public function registrar_custom_post_types() {
        // CPT: Productores
        register_post_type('gc_productor', [
            'labels' => [
                'name' => __('Productores', 'flavor-platform'),
                'singular_name' => __('Productor', 'flavor-platform'),
                'add_new' => __('Añadir Productor', 'flavor-platform'),
                'add_new_item' => __('Añadir Nuevo Productor', 'flavor-platform'),
                'edit_item' => __('Editar Productor', 'flavor-platform'),
            ],
            'public' => true,
            'has_archive' => true,
            'show_in_menu' => true,
            'menu_icon' => 'dashicons-carrot',
            'supports' => ['title', 'editor', 'thumbnail'],
            'show_in_rest' => true,
            'capability_type' => 'post',
            'rewrite' => ['slug' => 'productores'],
        ]);

        // CPT: Productos
        register_post_type('gc_producto', [
            'labels' => [
                'name' => __('Productos', 'flavor-platform'),
                'singular_name' => __('Producto', 'flavor-platform'),
                'add_new' => __('Añadir Producto', 'flavor-platform'),
            ],
            'public' => true,
            'has_archive' => true,
            'menu_icon' => 'dashicons-carrot',
            'show_in_menu' => 'edit.php?post_type=gc_productor',
            'supports' => ['title', 'editor', 'thumbnail'],
            'show_in_rest' => true,
            'rewrite' => ['slug' => 'productos-grupo-consumo'],
        ]);

        // CPT: Ciclos de pedido
        register_post_type('gc_ciclo', [
            'labels' => [
                'name' => __('Ciclos de Pedido', 'flavor-platform'),
                'singular_name' => __('Ciclo', 'flavor-platform'),
                'add_new' => __('Crear Ciclo', 'flavor-platform'),
            ],
            'public' => true,
            'show_in_menu' => 'edit.php?post_type=gc_productor',
            'menu_icon' => 'dashicons-calendar-alt',
            'supports' => ['title'],
            'show_in_rest' => true,
            'capability_type' => 'post',
            'rewrite' => ['slug' => 'ciclos-pedido'],
        ]);

        // CPT: Grupos de Consumo
        register_post_type('gc_grupo', [
            'labels' => [
                'name' => __('Grupos de Consumo', 'flavor-platform'),
                'singular_name' => __('Grupo', 'flavor-platform'),
                'add_new' => __('Crear Grupo', 'flavor-platform'),
                'add_new_item' => __('Crear Nuevo Grupo', 'flavor-platform'),
                'edit_item' => __('Editar Grupo', 'flavor-platform'),
                'view_item' => __('Ver Grupo', 'flavor-platform'),
                'all_items' => __('Todos los Grupos', 'flavor-platform'),
            ],
            'public' => true,
            'has_archive' => 'gc-grupos',
            'show_in_menu' => 'edit.php?post_type=gc_productor',
            'menu_icon' => 'dashicons-groups',
            'supports' => ['title', 'editor', 'thumbnail'],
            'show_in_rest' => true,
            'capability_type' => 'post',
            'rewrite' => ['slug' => 'gc-grupo'],
        ]);
    }

    /**
     * Registra taxonomías
     */
    public function registrar_taxonomias() {
        // Taxonomía: Categoría de productos
        register_taxonomy('gc_categoria', 'gc_producto', [
            'labels' => [
                'name' => __('Categorías', 'flavor-platform'),
                'singular_name' => __('Categoría', 'flavor-platform'),
            ],
            'hierarchical' => true,
            'show_in_rest' => true,
            'rewrite' => ['slug' => 'categoria-producto'],
        ]);

        // Términos por defecto
        $categorias_defecto = [
            'frutas' => __('Frutas', 'flavor-platform'),
            'verduras' => __('Verduras', 'flavor-platform'),
            'lacteos' => __('Lácteos', 'flavor-platform'),
            'carne' => __('Carne', 'flavor-platform'),
            'pescado' => __('Pescado', 'flavor-platform'),
            'pan' => __('Pan y Cereales', 'flavor-platform'),
            'conservas' => __('Conservas', 'flavor-platform'),
            'bebidas' => __('Bebidas', 'flavor-platform'),
            'otros' => __('Otros', 'flavor-platform'),
        ];

        foreach ($categorias_defecto as $slug => $nombre) {
            if (!term_exists($slug, 'gc_categoria')) {
                wp_insert_term($nombre, 'gc_categoria', ['slug' => $slug]);
            }
        }
    }

    /**
     * Registra estados personalizados para ciclos
     */
    public function registrar_estados_ciclo() {
        register_post_status('gc_abierto', [
            'label' => __('Abierto', 'flavor-platform'),
            'public' => true,
            'show_in_admin_all_list' => true,
            'show_in_admin_status_list' => true,
            'label_count' => _n_noop('Abierto <span class="count">(%s)</span>', 'Abiertos <span class="count">(%s)</span>', 'flavor-platform'),
        ]);

        register_post_status('gc_cerrado', [
            'label' => __('Cerrado', 'flavor-platform'),
            'public' => true,
            'show_in_admin_all_list' => true,
            'show_in_admin_status_list' => true,
            'label_count' => _n_noop('Cerrado <span class="count">(%s)</span>', 'Cerrados <span class="count">(%s)</span>', 'flavor-platform'),
        ]);

        register_post_status('gc_entregado', [
            'label' => __('Entregado', 'flavor-platform'),
            'public' => true,
            'show_in_admin_all_list' => true,
            'show_in_admin_status_list' => true,
            'label_count' => _n_noop('Entregado <span class="count">(%s)</span>', 'Entregados <span class="count">(%s)</span>', 'flavor-platform'),
        ]);
    }

    /**
     * Registra meta boxes
     */
    public function registrar_meta_boxes() {
        // Meta box para productores
        add_meta_box(
            'gc_productor_info',
            __('Información del Productor', 'flavor-platform'),
            [$this, 'render_meta_productor'],
            'gc_productor',
            'normal',
            'high'
        );

        // Meta box para productos
        add_meta_box(
            'gc_producto_info',
            __('Información del Producto', 'flavor-platform'),
            [$this, 'render_meta_producto'],
            'gc_producto',
            'normal',
            'high'
        );

        // Meta box para ciclos
        add_meta_box(
            'gc_ciclo_info',
            __('Información del Ciclo', 'flavor-platform'),
            [$this, 'render_meta_ciclo'],
            'gc_ciclo',
            'normal',
            'high'
        );

        // Meta box resumen pedidos del ciclo
        add_meta_box(
            'gc_ciclo_pedidos',
            __('Resumen de Pedidos', 'flavor-platform'),
            [$this, 'render_meta_ciclo_pedidos'],
            'gc_ciclo',
            'side',
            'default'
        );
    }

    /**
     * Renderiza meta box del productor
     */
    public function render_meta_productor($post) {
        wp_nonce_field('gc_productor_meta', 'gc_productor_nonce');

        $contacto_nombre = get_post_meta($post->ID, '_gc_contacto_nombre', true);
        $contacto_telefono = get_post_meta($post->ID, '_gc_contacto_telefono', true);
        $contacto_email = get_post_meta($post->ID, '_gc_contacto_email', true);
        $ubicacion_ciudad = get_post_meta($post->ID, '_gc_ubicacion', true);
        $certificacion_ecologica = get_post_meta($post->ID, '_gc_certificacion_eco', true);
        $numero_certificado = get_post_meta($post->ID, '_gc_numero_certificado', true);
        $metodos_produccion = get_post_meta($post->ID, '_gc_metodos_produccion', true);

        // Campos de ubicación y radio de entrega
        $radio_entrega_km = get_post_meta($post->ID, '_gc_radio_entrega_km', true);
        $direccion_completa = get_post_meta($post->ID, '_gc_direccion_completa', true);
        $latitud_productor = get_post_meta($post->ID, '_gc_lat', true);
        $longitud_productor = get_post_meta($post->ID, '_gc_lng', true);
        ?>
        <table class="form-table">
            <tr>
                <th><label for="gc_contacto_nombre"><?php _e('Nombre de Contacto', 'flavor-platform'); ?></label></th>
                <td><input type="text" id="gc_contacto_nombre" name="gc_contacto_nombre"
                           value="<?php echo esc_attr($contacto_nombre); ?>" class="regular-text" /></td>
            </tr>
            <tr>
                <th><label for="gc_contacto_telefono"><?php _e('Teléfono', 'flavor-platform'); ?></label></th>
                <td><input type="tel" id="gc_contacto_telefono" name="gc_contacto_telefono"
                           value="<?php echo esc_attr($contacto_telefono); ?>" class="regular-text" /></td>
            </tr>
            <tr>
                <th><label for="gc_contacto_email"><?php _e('Email', 'flavor-platform'); ?></label></th>
                <td><input type="email" id="gc_contacto_email" name="gc_contacto_email"
                           value="<?php echo esc_attr($contacto_email); ?>" class="regular-text" /></td>
            </tr>
            <tr>
                <th><label for="gc_ubicacion"><?php _e('Ubicación', 'flavor-platform'); ?></label></th>
                <td><input type="text" id="gc_ubicacion" name="gc_ubicacion"
                           value="<?php echo esc_attr($ubicacion_ciudad); ?>" class="regular-text"
                           placeholder="<?php _e('Ciudad, Provincia', 'flavor-platform'); ?>" /></td>
            </tr>
            <tr>
                <th><label for="gc_certificacion_eco"><?php _e('Certificación Ecológica', 'flavor-platform'); ?></label></th>
                <td>
                    <label>
                        <input type="checkbox" id="gc_certificacion_eco" name="gc_certificacion_eco"
                               value="1" <?php checked($certificacion_ecologica, '1'); ?> />
                        <?php _e('Productor certificado ecológico', 'flavor-platform'); ?>
                    </label>
                </td>
            </tr>
            <tr>
                <th><label for="gc_numero_certificado"><?php _e('Nº Certificado', 'flavor-platform'); ?></label></th>
                <td><input type="text" id="gc_numero_certificado" name="gc_numero_certificado"
                           value="<?php echo esc_attr($numero_certificado); ?>" class="regular-text" /></td>
            </tr>
            <tr>
                <th><label for="gc_metodos_produccion"><?php _e('Métodos de Producción', 'flavor-platform'); ?></label></th>
                <td>
                    <textarea id="gc_metodos_produccion" name="gc_metodos_produccion"
                              rows="4" class="large-text"><?php echo esc_textarea($metodos_produccion); ?></textarea>
                    <p class="description"><?php _e('Describe los métodos y prácticas de producción', 'flavor-platform'); ?></p>
                </td>
            </tr>
        </table>

        <!-- Sección de Zona de Entrega -->
        <h3 style="margin-top: 20px; padding-top: 20px; border-top: 1px solid #ddd;">
            <span class="dashicons dashicons-location-alt" style="color: #2271b1;"></span>
            <?php _e('Zona de Entrega a Domicilio', 'flavor-platform'); ?>
        </h3>
        <p class="description" style="margin-bottom: 15px;">
            <?php _e('Configura el área geográfica donde el productor puede realizar entregas a domicilio.', 'flavor-platform'); ?>
        </p>

        <table class="form-table">
            <tr>
                <th><label for="gc_radio_entrega_km"><?php _e('Radio de Entrega (km)', 'flavor-platform'); ?></label></th>
                <td>
                    <input type="number" id="gc_radio_entrega_km" name="gc_radio_entrega_km"
                           value="<?php echo esc_attr($radio_entrega_km); ?>"
                           min="0" max="500" step="1" style="width: 100px;" />
                    <span class="description"><?php _e('km desde la ubicación del productor', 'flavor-platform'); ?></span>
                    <p class="description">
                        <?php _e('Deja en 0 o vacío si no ofrece entrega a domicilio.', 'flavor-platform'); ?>
                    </p>
                </td>
            </tr>
            <tr>
                <th><label for="gc_direccion_completa"><?php _e('Dirección Completa', 'flavor-platform'); ?></label></th>
                <td>
                    <input type="text" id="gc_direccion_completa" name="gc_direccion_completa"
                           value="<?php echo esc_attr($direccion_completa); ?>" class="large-text"
                           placeholder="<?php _e('Calle, número, ciudad, código postal...', 'flavor-platform'); ?>" />
                    <p class="description">
                        <?php _e('Escribe la dirección y pulsa el botón para obtener las coordenadas.', 'flavor-platform'); ?>
                    </p>
                    <button type="button" id="gc_geocodificar_btn" class="button button-secondary" style="margin-top: 5px;">
                        <span class="dashicons dashicons-search" style="vertical-align: middle;"></span>
                        <?php _e('Obtener coordenadas', 'flavor-platform'); ?>
                    </button>
                    <span id="gc_geocoding_status" style="margin-left: 10px;"></span>
                </td>
            </tr>
            <tr>
                <th><label><?php _e('Coordenadas', 'flavor-platform'); ?></label></th>
                <td>
                    <div style="display: flex; gap: 15px; align-items: center;">
                        <div>
                            <label for="gc_lat" style="font-weight: normal;"><?php _e('Latitud:', 'flavor-platform'); ?></label>
                            <input type="text" id="gc_lat" name="gc_lat"
                                   value="<?php echo esc_attr($latitud_productor); ?>"
                                   style="width: 150px;" readonly />
                        </div>
                        <div>
                            <label for="gc_lng" style="font-weight: normal;"><?php _e('Longitud:', 'flavor-platform'); ?></label>
                            <input type="text" id="gc_lng" name="gc_lng"
                                   value="<?php echo esc_attr($longitud_productor); ?>"
                                   style="width: 150px;" readonly />
                        </div>
                        <button type="button" id="gc_limpiar_coords_btn" class="button" title="<?php esc_attr_e('Limpiar coordenadas', 'flavor-platform'); ?>">
                            <span class="dashicons dashicons-no" style="vertical-align: middle;"></span>
                        </button>
                    </div>
                </td>
            </tr>
            <tr>
                <th><label><?php _e('Vista Previa del Área', 'flavor-platform'); ?></label></th>
                <td>
                    <div id="gc_mapa_preview" style="width: 100%; max-width: 500px; height: 300px; background: #f0f0f0; border: 1px solid #ddd; border-radius: 4px; display: flex; align-items: center; justify-content: center;">
                        <?php if ($latitud_productor && $longitud_productor): ?>
                            <div id="gc_mapa_container" style="width: 100%; height: 100%;"></div>
                        <?php else: ?>
                            <span style="color: #666;"><?php _e('El mapa aparecerá cuando se establezcan las coordenadas', 'flavor-platform'); ?></span>
                        <?php endif; ?>
                    </div>
                    <p class="description" style="margin-top: 5px;">
                        <?php _e('El círculo azul muestra el área de cobertura de entrega.', 'flavor-platform'); ?>
                    </p>
                </td>
            </tr>
        </table>

        <?php
        // Sección Compartir en Red (solo si el módulo de red está activo)
        if (class_exists('Flavor_Network_Manager')):
            $compartir_en_red = get_post_meta($post->ID, '_gc_compartir_en_red', true);
            $acepta_mensajeria = get_post_meta($post->ID, '_gc_acepta_mensajeria', true);
            $nodos_visibles = get_post_meta($post->ID, '_gc_nodos_visibles', true);
            if (!is_array($nodos_visibles)) {
                $nodos_visibles = [];
            }
        ?>
        <h3 style="margin-top: 20px; padding-top: 20px; border-top: 1px solid #ddd;">
            <span class="dashicons dashicons-networking" style="color: #9b59b6;"></span>
            <?php _e('Compartir en Red de Nodos', 'flavor-platform'); ?>
        </h3>
        <p class="description" style="margin-bottom: 15px;">
            <?php _e('Permite que otros nodos de la red puedan ver este productor y sus productos.', 'flavor-platform'); ?>
        </p>

        <table class="form-table">
            <tr>
                <th><label for="gc_compartir_en_red"><?php _e('Compartir en Red', 'flavor-platform'); ?></label></th>
                <td>
                    <label>
                        <input type="checkbox" id="gc_compartir_en_red" name="gc_compartir_en_red"
                               value="1" <?php checked($compartir_en_red, '1'); ?> />
                        <?php _e('Permitir que este productor sea visible en otros nodos de la red', 'flavor-platform'); ?>
                    </label>
                    <p class="description">
                        <?php _e('Los nodos cercanos (dentro del radio de entrega) podrán ver y contactar a este productor.', 'flavor-platform'); ?>
                    </p>
                </td>
            </tr>
            <tr>
                <th><label for="gc_acepta_mensajeria"><?php _e('Envío por Mensajería', 'flavor-platform'); ?></label></th>
                <td>
                    <label>
                        <input type="checkbox" id="gc_acepta_mensajeria" name="gc_acepta_mensajeria"
                               value="1" <?php checked($acepta_mensajeria, '1'); ?> />
                        <?php _e('Acepta envíos por mensajería a cualquier destino', 'flavor-platform'); ?>
                    </label>
                    <p class="description">
                        <?php _e('Si está activo, el productor será visible en todos los nodos independientemente de la distancia.', 'flavor-platform'); ?>
                    </p>
                </td>
            </tr>
            <tr id="gc_nodos_visibles_row" style="<?php echo ($compartir_en_red && !$acepta_mensajeria) ? '' : 'display:none;'; ?>">
                <th><label><?php _e('Visibilidad', 'flavor-platform'); ?></label></th>
                <td>
                    <p class="description" style="margin-bottom: 10px;">
                        <span class="dashicons dashicons-info-outline" style="color: #2271b1;"></span>
                        <?php
                        if ($latitud_productor && $longitud_productor && $radio_entrega_km) {
                            printf(
                                __('Este productor será visible en nodos que se encuentren a menos de %d km de su ubicación.', 'flavor-platform'),
                                intval($radio_entrega_km)
                            );
                        } else {
                            _e('Configura la ubicación y el radio de entrega para definir el área de visibilidad en la red.', 'flavor-platform');
                        }
                        ?>
                    </p>
                </td>
            </tr>
        </table>

        <script>
        jQuery(document).ready(function($) {
            function actualizarVisibilidadNodos() {
                var compartir = $('#gc_compartir_en_red').is(':checked');
                var mensajeria = $('#gc_acepta_mensajeria').is(':checked');

                if (compartir && !mensajeria) {
                    $('#gc_nodos_visibles_row').show();
                } else {
                    $('#gc_nodos_visibles_row').hide();
                }
            }

            $('#gc_compartir_en_red, #gc_acepta_mensajeria').on('change', actualizarVisibilidadNodos);
        });
        </script>
        <?php endif; ?>

        <script>
        jQuery(document).ready(function($) {
            var mapaInicializado = false;
            var marcadorProductor = null;
            var circuloCobertura = null;
            var mapaLeaflet = null;

            // Función para inicializar o actualizar el mapa
            function actualizarMapa() {
                var latitud = parseFloat($('#gc_lat').val());
                var longitud = parseFloat($('#gc_lng').val());
                var radioKm = parseFloat($('#gc_radio_entrega_km').val()) || 0;

                if (!latitud || !longitud) {
                    $('#gc_mapa_container').hide();
                    $('#gc_mapa_preview').html('<span style="color: #666;"><?php echo esc_js(__('El mapa aparecerá cuando se establezcan las coordenadas', 'flavor-platform')); ?></span>');
                    return;
                }

                // Mostrar contenedor del mapa
                if ($('#gc_mapa_container').length === 0) {
                    $('#gc_mapa_preview').html('<div id="gc_mapa_container" style="width: 100%; height: 100%;"></div>');
                }
                $('#gc_mapa_container').show();

                // Cargar Leaflet si no está cargado
                if (typeof L === 'undefined') {
                    // Cargar CSS de Leaflet
                    if ($('link[href*="leaflet.css"]').length === 0) {
                        $('head').append('<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />');
                    }

                    // Cargar JS de Leaflet
                    $.getScript('https://unpkg.com/leaflet@1.9.4/dist/leaflet.js', function() {
                        inicializarMapaLeaflet(latitud, longitud, radioKm);
                    });
                } else {
                    inicializarMapaLeaflet(latitud, longitud, radioKm);
                }
            }

            function inicializarMapaLeaflet(latitud, longitud, radioKm) {
                var contenedor = document.getElementById('gc_mapa_container');
                if (!contenedor) return;

                // Destruir mapa existente si hay uno
                if (mapaLeaflet) {
                    mapaLeaflet.remove();
                    mapaLeaflet = null;
                }

                // Crear nuevo mapa
                mapaLeaflet = L.map('gc_mapa_container').setView([latitud, longitud], 10);

                // Añadir capa de tiles (OpenStreetMap)
                L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                    attribution: '&copy; OpenStreetMap contributors'
                }).addTo(mapaLeaflet);

                // Añadir marcador del productor
                marcadorProductor = L.marker([latitud, longitud]).addTo(mapaLeaflet)
                    .bindPopup('<?php echo esc_js(__('Ubicación del productor', 'flavor-platform')); ?>');

                // Añadir círculo de cobertura si hay radio
                if (radioKm > 0) {
                    circuloCobertura = L.circle([latitud, longitud], {
                        color: '#2271b1',
                        fillColor: '#2271b1',
                        fillOpacity: 0.15,
                        radius: radioKm * 1000 // Convertir km a metros
                    }).addTo(mapaLeaflet);

                    // Ajustar zoom para mostrar todo el círculo
                    mapaLeaflet.fitBounds(circuloCobertura.getBounds());
                }

                // Forzar redimensionado
                setTimeout(function() {
                    mapaLeaflet.invalidateSize();
                }, 100);
            }

            // Geocodificación usando Nominatim (OpenStreetMap)
            $('#gc_geocodificar_btn').on('click', function() {
                var direccion = $('#gc_direccion_completa').val();
                var $status = $('#gc_geocoding_status');
                var $btn = $(this);

                if (!direccion) {
                    $status.html('<span style="color: #d63638;"><?php echo esc_js(__('Introduce una dirección', 'flavor-platform')); ?></span>');
                    return;
                }

                $btn.prop('disabled', true);
                $status.html('<span style="color: #2271b1;"><span class="spinner is-active" style="float: none; margin: 0;"></span> <?php echo esc_js(__('Buscando...', 'flavor-platform')); ?></span>');

                // Usar Nominatim de OpenStreetMap (gratuito, sin API key)
                $.ajax({
                    url: 'https://nominatim.openstreetmap.org/search',
                    data: {
                        q: direccion,
                        format: 'json',
                        limit: 1,
                        countrycodes: 'es' // Priorizar España
                    },
                    dataType: 'json',
                    success: function(resultados) {
                        $btn.prop('disabled', false);

                        if (resultados && resultados.length > 0) {
                            var resultado = resultados[0];
                            $('#gc_lat').val(resultado.lat);
                            $('#gc_lng').val(resultado.lon);
                            $status.html('<span style="color: #00a32a;"><span class="dashicons dashicons-yes"></span> <?php echo esc_js(__('Coordenadas encontradas', 'flavor-platform')); ?></span>');

                            // Actualizar mapa
                            actualizarMapa();
                        } else {
                            $status.html('<span style="color: #d63638;"><?php echo esc_js(__('No se encontró la dirección', 'flavor-platform')); ?></span>');
                        }
                    },
                    error: function() {
                        $btn.prop('disabled', false);
                        $status.html('<span style="color: #d63638;"><?php echo esc_js(__('Error al buscar', 'flavor-platform')); ?></span>');
                    }
                });
            });

            // Limpiar coordenadas
            $('#gc_limpiar_coords_btn').on('click', function() {
                $('#gc_lat').val('');
                $('#gc_lng').val('');
                actualizarMapa();
            });

            // Actualizar mapa cuando cambia el radio
            $('#gc_radio_entrega_km').on('change', function() {
                actualizarMapa();
            });

            // Inicializar mapa si hay coordenadas
            <?php if ($latitud_productor && $longitud_productor): ?>
            setTimeout(function() {
                actualizarMapa();
            }, 500);
            <?php endif; ?>
        });
        </script>
        <?php
    }

    /**
     * Renderiza meta box del producto
     */
    public function render_meta_producto($post) {
        wp_nonce_field('gc_producto_meta', 'gc_producto_nonce');

        $productor_id = get_post_meta($post->ID, '_gc_productor_id', true);
        $precio = get_post_meta($post->ID, '_gc_precio', true);
        $unidad = get_post_meta($post->ID, '_gc_unidad', true);
        $cantidad_minima = get_post_meta($post->ID, '_gc_cantidad_minima', true);
        $stock_disponible = get_post_meta($post->ID, '_gc_stock', true);
        $temporada = get_post_meta($post->ID, '_gc_temporada', true);
        $origen = get_post_meta($post->ID, '_gc_origen', true);

        // Obtener productores
        $productores = get_posts([
            'post_type' => 'gc_productor',
            'posts_per_page' => -1,
            'orderby' => 'title',
            'order' => 'ASC',
        ]);
        ?>
        <table class="form-table">
            <tr>
                <th><label for="gc_productor_id"><?php _e('Productor', 'flavor-platform'); ?> *</label></th>
                <td>
                    <select id="gc_productor_id" name="gc_productor_id" class="regular-text" required>
                        <option value=""><?php _e('Selecciona un productor', 'flavor-platform'); ?></option>
                        <?php foreach ($productores as $productor): ?>
                            <option value="<?php echo $productor->ID; ?>"
                                    <?php selected($productor_id, $productor->ID); ?>>
                                <?php echo esc_html($productor->post_title); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </td>
            </tr>
            <tr>
                <th><label for="gc_precio"><?php _e('Precio', 'flavor-platform'); ?> *</label></th>
                <td>
                    <input type="number" step="0.01" id="gc_precio" name="gc_precio"
                           value="<?php echo esc_attr($precio); ?>" required /> €
                </td>
            </tr>
            <tr>
                <th><label for="gc_unidad"><?php _e('Unidad', 'flavor-platform'); ?></label></th>
                <td>
                    <select id="gc_unidad" name="gc_unidad">
                        <option value="<?php echo esc_attr__('kg', 'flavor-platform'); ?>" <?php selected($unidad, 'kg'); ?>><?php echo esc_html__('Kg', 'flavor-platform'); ?></option>
                        <option value="<?php echo esc_attr__('g', 'flavor-platform'); ?>" <?php selected($unidad, 'g'); ?>><?php echo esc_html__('g', 'flavor-platform'); ?></option>
                        <option value="<?php echo esc_attr__('l', 'flavor-platform'); ?>" <?php selected($unidad, 'l'); ?>><?php echo esc_html__('L', 'flavor-platform'); ?></option>
                        <option value="<?php echo esc_attr__('unidad', 'flavor-platform'); ?>" <?php selected($unidad, 'unidad'); ?>><?php _e('Unidad', 'flavor-platform'); ?></option>
                        <option value="<?php echo esc_attr__('caja', 'flavor-platform'); ?>" <?php selected($unidad, 'caja'); ?>><?php _e('Caja', 'flavor-platform'); ?></option>
                        <option value="<?php echo esc_attr__('ramo', 'flavor-platform'); ?>" <?php selected($unidad, 'ramo'); ?>><?php _e('Ramo', 'flavor-platform'); ?></option>
                    </select>
                </td>
            </tr>
            <tr>
                <th><label for="gc_cantidad_minima"><?php _e('Cantidad Mínima', 'flavor-platform'); ?></label></th>
                <td>
                    <input type="number" step="0.1" id="gc_cantidad_minima" name="gc_cantidad_minima"
                           value="<?php echo esc_attr($cantidad_minima ?: 1); ?>" />
                </td>
            </tr>
            <tr>
                <th><label for="gc_stock"><?php _e('Stock Disponible', 'flavor-platform'); ?></label></th>
                <td>
                    <input type="number" step="0.1" id="gc_stock" name="gc_stock"
                           value="<?php echo esc_attr($stock_disponible); ?>" />
                    <p class="description"><?php _e('Dejar vacío si es ilimitado', 'flavor-platform'); ?></p>
                </td>
            </tr>
            <tr>
                <th><label for="gc_temporada"><?php _e('Temporada', 'flavor-platform'); ?></label></th>
                <td>
                    <input type="text" id="gc_temporada" name="gc_temporada"
                           value="<?php echo esc_attr($temporada); ?>" class="regular-text"
                           placeholder="<?php _e('Ej: Primavera-Verano', 'flavor-platform'); ?>" />
                </td>
            </tr>
            <tr>
                <th><label for="gc_origen"><?php _e('Origen', 'flavor-platform'); ?></label></th>
                <td>
                    <input type="text" id="gc_origen" name="gc_origen"
                           value="<?php echo esc_attr($origen); ?>" class="regular-text" />
                </td>
            </tr>
        </table>
        <?php
    }

    /**
     * Renderiza meta box del ciclo
     */
    public function render_meta_ciclo($post) {
        wp_nonce_field('gc_ciclo_meta', 'gc_ciclo_nonce');

        $fecha_inicio = get_post_meta($post->ID, '_gc_fecha_inicio', true);
        $fecha_cierre = get_post_meta($post->ID, '_gc_fecha_cierre', true);
        $fecha_entrega = get_post_meta($post->ID, '_gc_fecha_entrega', true);
        $lugar_entrega = get_post_meta($post->ID, '_gc_lugar_entrega', true);
        $hora_entrega = get_post_meta($post->ID, '_gc_hora_entrega', true);
        $notas = get_post_meta($post->ID, '_gc_notas', true);

        // Campos de recurrencia
        $es_recurrente = get_post_meta($post->ID, '_gc_es_recurrente', true);
        $tipo_recurrencia = get_post_meta($post->ID, '_gc_tipo_recurrencia', true) ?: 'semanal';
        $recurrencia_fin = get_post_meta($post->ID, '_gc_recurrencia_fin', true);
        $ciclo_padre_id = get_post_meta($post->ID, '_gc_ciclo_padre', true);
        $auto_publicar = get_post_meta($post->ID, '_gc_auto_publicar', true);
        ?>
        <table class="form-table">
            <tr>
                <th><label for="gc_fecha_inicio"><?php _e('Fecha Apertura', 'flavor-platform'); ?> *</label></th>
                <td><input type="datetime-local" id="gc_fecha_inicio" name="gc_fecha_inicio"
                           value="<?php echo esc_attr($fecha_inicio); ?>" required /></td>
            </tr>
            <tr>
                <th><label for="gc_fecha_cierre"><?php _e('Fecha Cierre', 'flavor-platform'); ?> *</label></th>
                <td><input type="datetime-local" id="gc_fecha_cierre" name="gc_fecha_cierre"
                           value="<?php echo esc_attr($fecha_cierre); ?>" required /></td>
            </tr>
            <tr>
                <th><label for="gc_fecha_entrega"><?php _e('Fecha Entrega', 'flavor-platform'); ?> *</label></th>
                <td><input type="date" id="gc_fecha_entrega" name="gc_fecha_entrega"
                           value="<?php echo esc_attr($fecha_entrega); ?>" required /></td>
            </tr>
            <tr>
                <th><label for="gc_hora_entrega"><?php _e('Hora Entrega', 'flavor-platform'); ?></label></th>
                <td><input type="time" id="gc_hora_entrega" name="gc_hora_entrega"
                           value="<?php echo esc_attr($hora_entrega); ?>" /></td>
            </tr>
            <tr>
                <th><label for="gc_lugar_entrega"><?php _e('Lugar de Entrega', 'flavor-platform'); ?></label></th>
                <td><input type="text" id="gc_lugar_entrega" name="gc_lugar_entrega"
                           value="<?php echo esc_attr($lugar_entrega); ?>" class="large-text" /></td>
            </tr>
            <tr>
                <th><label for="gc_notas"><?php _e('Notas', 'flavor-platform'); ?></label></th>
                <td>
                    <textarea id="gc_notas" name="gc_notas" rows="4"
                              class="large-text"><?php echo esc_textarea($notas); ?></textarea>
                </td>
            </tr>
        </table>

        <!-- Sección de Recurrencia -->
        <h3 style="margin-top: 25px; padding-top: 20px; border-top: 1px solid #ddd;">
            <span class="dashicons dashicons-update" style="color: #2271b1;"></span>
            <?php _e('Configuración de Recurrencia', 'flavor-platform'); ?>
        </h3>

        <?php if ($ciclo_padre_id): ?>
            <div class="notice notice-info inline" style="margin: 10px 0;">
                <p>
                    <span class="dashicons dashicons-info"></span>
                    <?php
                    $ciclo_padre = get_post($ciclo_padre_id);
                    printf(
                        __('Este ciclo fue generado automáticamente a partir del ciclo plantilla: %s', 'flavor-platform'),
                        '<a href="' . get_edit_post_link($ciclo_padre_id) . '">' . esc_html($ciclo_padre->post_title) . '</a>'
                    );
                    ?>
                </p>
            </div>
        <?php endif; ?>

        <table class="form-table">
            <tr>
                <th><label for="gc_es_recurrente"><?php _e('Ciclo Recurrente', 'flavor-platform'); ?></label></th>
                <td>
                    <label>
                        <input type="checkbox" id="gc_es_recurrente" name="gc_es_recurrente"
                               value="1" <?php checked($es_recurrente, '1'); ?> />
                        <?php _e('Generar automáticamente el siguiente ciclo al cerrar este', 'flavor-platform'); ?>
                    </label>
                    <p class="description">
                        <?php _e('Al activar esta opción, cuando el ciclo actual se cierre, se creará automáticamente uno nuevo con las mismas características.', 'flavor-platform'); ?>
                    </p>
                </td>
            </tr>
            <tr class="gc-recurrencia-campo" style="<?php echo $es_recurrente ? '' : 'display:none;'; ?>">
                <th><label for="gc_tipo_recurrencia"><?php _e('Frecuencia', 'flavor-platform'); ?></label></th>
                <td>
                    <select id="gc_tipo_recurrencia" name="gc_tipo_recurrencia">
                        <option value="semanal" <?php selected($tipo_recurrencia, 'semanal'); ?>><?php _e('Semanal (cada 7 días)', 'flavor-platform'); ?></option>
                        <option value="quincenal" <?php selected($tipo_recurrencia, 'quincenal'); ?>><?php _e('Quincenal (cada 14 días)', 'flavor-platform'); ?></option>
                        <option value="mensual" <?php selected($tipo_recurrencia, 'mensual'); ?>><?php _e('Mensual (cada 30 días)', 'flavor-platform'); ?></option>
                        <option value="bimensual" <?php selected($tipo_recurrencia, 'bimensual'); ?>><?php _e('Bimensual (cada 60 días)', 'flavor-platform'); ?></option>
                    </select>
                    <p class="description">
                        <?php _e('Define cada cuánto tiempo se creará un nuevo ciclo.', 'flavor-platform'); ?>
                    </p>
                </td>
            </tr>
            <tr class="gc-recurrencia-campo" style="<?php echo $es_recurrente ? '' : 'display:none;'; ?>">
                <th><label for="gc_auto_publicar"><?php _e('Auto-publicar', 'flavor-platform'); ?></label></th>
                <td>
                    <label>
                        <input type="checkbox" id="gc_auto_publicar" name="gc_auto_publicar"
                               value="1" <?php checked($auto_publicar, '1'); ?> />
                        <?php _e('Publicar automáticamente el nuevo ciclo (si no, quedará como borrador)', 'flavor-platform'); ?>
                    </label>
                </td>
            </tr>
            <tr class="gc-recurrencia-campo" style="<?php echo $es_recurrente ? '' : 'display:none;'; ?>">
                <th><label for="gc_recurrencia_fin"><?php _e('Fecha Fin Recurrencia', 'flavor-platform'); ?></label></th>
                <td>
                    <input type="date" id="gc_recurrencia_fin" name="gc_recurrencia_fin"
                           value="<?php echo esc_attr($recurrencia_fin); ?>" />
                    <p class="description">
                        <?php _e('Opcional. Si se define, la recurrencia se detendrá en esta fecha.', 'flavor-platform'); ?>
                    </p>
                </td>
            </tr>
        </table>

        <script>
        jQuery(document).ready(function($) {
            $('#gc_es_recurrente').on('change', function() {
                if ($(this).is(':checked')) {
                    $('.gc-recurrencia-campo').show();
                } else {
                    $('.gc-recurrencia-campo').hide();
                }
            });
        });
        </script>
        <?php
    }

    /**
     * Renderiza resumen de pedidos del ciclo
     */
    public function render_meta_ciclo_pedidos($post) {
        global $wpdb;
        $tabla_pedidos = $wpdb->prefix . 'flavor_gc_pedidos';

        // Total pedidos
        $total_pedidos = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(DISTINCT usuario_id) FROM $tabla_pedidos WHERE ciclo_id = %d",
            $post->ID
        ));

        // Total importe
        $total_importe = $wpdb->get_var($wpdb->prepare(
            "SELECT SUM(precio_unitario * cantidad) FROM $tabla_pedidos WHERE ciclo_id = %d",
            $post->ID
        ));

        // Productos más pedidos
        $productos_top = $wpdb->get_results($wpdb->prepare(
            "SELECT producto_id, SUM(cantidad) as total_cantidad
            FROM $tabla_pedidos
            WHERE ciclo_id = %d
            GROUP BY producto_id
            ORDER BY total_cantidad DESC
            LIMIT 5",
            $post->ID
        ));

        echo '<div class="gc-resumen-pedidos">';
        echo '<p><strong>' . __('Total Pedidos:', 'flavor-platform') . '</strong> ' . intval($total_pedidos) . '</p>';
        echo '<p><strong>' . __('Importe Total:', 'flavor-platform') . '</strong> ' . number_format($total_importe, 2) . ' €</p>';

        if (!empty($productos_top)) {
            echo '<h4>' . __('Productos Más Pedidos:', 'flavor-platform') . '</h4>';
            echo '<ul>';
            foreach ($productos_top as $prod) {
                $producto = get_post($prod->producto_id);
                if ($producto) {
                    echo '<li>' . esc_html($producto->post_title) . ': ' . floatval($prod->total_cantidad) . '</li>';
                }
            }
            echo '</ul>';
        }
        echo '</div>';
    }

    /**
     * Guarda meta del productor
     */
    public function guardar_meta_productor($post_id) {
        if (!isset($_POST['gc_productor_nonce']) ||
            !wp_verify_nonce($_POST['gc_productor_nonce'], 'gc_productor_meta')) {
            return;
        }

        if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) return;
        if (!current_user_can('edit_post', $post_id)) return;

        $campos_texto = [
            '_gc_contacto_nombre' => 'sanitize_text_field',
            '_gc_contacto_telefono' => 'sanitize_text_field',
            '_gc_contacto_email' => 'sanitize_email',
            '_gc_ubicacion' => 'sanitize_text_field',
            '_gc_certificacion_eco' => 'sanitize_text_field',
            '_gc_numero_certificado' => 'sanitize_text_field',
            '_gc_metodos_produccion' => 'sanitize_textarea_field',
            '_gc_direccion_completa' => 'sanitize_text_field',
        ];

        foreach ($campos_texto as $campo => $funcion_sanitizar) {
            $campo_form = str_replace('_gc_', 'gc_', $campo);
            if (isset($_POST[$campo_form])) {
                update_post_meta($post_id, $campo, $funcion_sanitizar($_POST[$campo_form]));
            }
        }

        // Guardar campos numéricos (radio de entrega)
        if (isset($_POST['gc_radio_entrega_km'])) {
            $radio_entrega = floatval($_POST['gc_radio_entrega_km']);
            update_post_meta($post_id, '_gc_radio_entrega_km', $radio_entrega);
        }

        // Guardar coordenadas
        if (isset($_POST['gc_lat'])) {
            $latitud = sanitize_text_field($_POST['gc_lat']);
            // Validar que sea un número válido de latitud
            if ($latitud === '' || (is_numeric($latitud) && $latitud >= -90 && $latitud <= 90)) {
                update_post_meta($post_id, '_gc_lat', $latitud);
            }
        }

        if (isset($_POST['gc_lng'])) {
            $longitud = sanitize_text_field($_POST['gc_lng']);
            // Validar que sea un número válido de longitud
            if ($longitud === '' || (is_numeric($longitud) && $longitud >= -180 && $longitud <= 180)) {
                update_post_meta($post_id, '_gc_lng', $longitud);
            }
        }

        // Guardar campos de compartir en red
        $compartir_en_red = isset($_POST['gc_compartir_en_red']) ? '1' : '0';
        update_post_meta($post_id, '_gc_compartir_en_red', $compartir_en_red);

        $acepta_mensajeria = isset($_POST['gc_acepta_mensajeria']) ? '1' : '0';
        update_post_meta($post_id, '_gc_acepta_mensajeria', $acepta_mensajeria);

        // Sincronizar con la tabla de red si está habilitado
        if ($compartir_en_red === '1' && class_exists('Flavor_Network_Manager')) {
            $this->sincronizar_productor_con_red($post_id);
        } elseif ($compartir_en_red === '0' && class_exists('Flavor_Network_Manager')) {
            $this->eliminar_productor_de_red($post_id);
        }
    }

    /**
     * Sincroniza un productor con la tabla de red
     */
    private function sincronizar_productor_con_red($productor_id) {
        global $wpdb;
        $tabla_productores_red = $wpdb->prefix . 'flavor_network_producers';

        // Verificar que la tabla existe
        if ($wpdb->get_var("SHOW TABLES LIKE '$tabla_productores_red'") !== $tabla_productores_red) {
            return false;
        }

        $productor = get_post($productor_id);
        if (!$productor || $productor->post_type !== 'gc_productor') {
            return false;
        }

        // Obtener ID del nodo actual
        $nodo_id = get_option('flavor_network_node_id', '');
        if (empty($nodo_id)) {
            // Generar ID de nodo si no existe
            $nodo_id = wp_generate_uuid4();
            update_option('flavor_network_node_id', $nodo_id);
        }

        $datos_productor = [
            'nodo_id'           => $nodo_id,
            'productor_id'      => $productor_id,
            'nombre'            => $productor->post_title,
            'slug'              => $productor->post_name,
            'ubicacion'         => get_post_meta($productor_id, '_gc_ubicacion', true),
            'latitud'           => floatval(get_post_meta($productor_id, '_gc_lat', true)),
            'longitud'          => floatval(get_post_meta($productor_id, '_gc_lng', true)),
            'radio_entrega_km'  => floatval(get_post_meta($productor_id, '_gc_radio_entrega_km', true)),
            'certificacion_eco' => get_post_meta($productor_id, '_gc_certificacion_eco', true) === '1' ? 1 : 0,
            'compartir_en_red'  => 1,
            'acepta_mensajeria' => get_post_meta($productor_id, '_gc_acepta_mensajeria', true) === '1' ? 1 : 0,
            'visible_en_red'    => 1,
            'actualizado_en'    => current_time('mysql'),
        ];

        // Verificar si ya existe
        $existe = $wpdb->get_var($wpdb->prepare(
            "SELECT id FROM $tabla_productores_red WHERE nodo_id = %s AND productor_id = %d",
            $nodo_id,
            $productor_id
        ));

        if ($existe) {
            $wpdb->update($tabla_productores_red, $datos_productor, [
                'nodo_id' => $nodo_id,
                'productor_id' => $productor_id
            ]);
        } else {
            $datos_productor['creado_en'] = current_time('mysql');
            $wpdb->insert($tabla_productores_red, $datos_productor);
        }

        // Sincronizar productos del productor
        $this->sincronizar_productos_productor_con_red($productor_id);

        return true;
    }

    /**
     * Sincroniza los productos de un productor con la tabla de red
     */
    private function sincronizar_productos_productor_con_red($productor_id) {
        global $wpdb;
        $tabla_productos_red = $wpdb->prefix . 'flavor_network_producer_products';

        // Verificar que la tabla existe
        if ($wpdb->get_var("SHOW TABLES LIKE '$tabla_productos_red'") !== $tabla_productos_red) {
            return;
        }

        $nodo_id = get_option('flavor_network_node_id', '');

        // Obtener productos del productor
        $productos = get_posts([
            'post_type'      => 'gc_producto',
            'post_status'    => 'publish',
            'posts_per_page' => -1,
            'meta_query'     => [
                [
                    'key'   => '_gc_productor_id',
                    'value' => $productor_id
                ]
            ]
        ]);

        // Eliminar productos anteriores de la red
        $wpdb->delete($tabla_productos_red, [
            'nodo_id' => $nodo_id,
            'productor_id' => $productor_id
        ]);

        // Insertar productos actuales
        foreach ($productos as $producto) {
            $wpdb->insert($tabla_productos_red, [
                'nodo_id'        => $nodo_id,
                'productor_id'   => $productor_id,
                'producto_id'    => $producto->ID,
                'nombre'         => $producto->post_title,
                'precio'         => floatval(get_post_meta($producto->ID, '_gc_precio', true)),
                'unidad'         => get_post_meta($producto->ID, '_gc_unidad', true),
                'disponible'     => 1,
                'actualizado_en' => current_time('mysql'),
            ]);
        }
    }

    /**
     * Elimina un productor de la tabla de red
     */
    private function eliminar_productor_de_red($productor_id) {
        global $wpdb;
        $tabla_productores_red = $wpdb->prefix . 'flavor_network_producers';
        $tabla_productos_red = $wpdb->prefix . 'flavor_network_producer_products';

        $nodo_id = get_option('flavor_network_node_id', '');

        // Eliminar productos del productor
        if ($wpdb->get_var("SHOW TABLES LIKE '$tabla_productos_red'") === $tabla_productos_red) {
            $wpdb->delete($tabla_productos_red, [
                'nodo_id' => $nodo_id,
                'productor_id' => $productor_id
            ]);
        }

        // Eliminar productor
        if ($wpdb->get_var("SHOW TABLES LIKE '$tabla_productores_red'") === $tabla_productores_red) {
            $wpdb->delete($tabla_productores_red, [
                'nodo_id' => $nodo_id,
                'productor_id' => $productor_id
            ]);
        }
    }

    /**
     * Guarda meta del producto
     */
    public function guardar_meta_producto($post_id) {
        if (!isset($_POST['gc_producto_nonce']) ||
            !wp_verify_nonce($_POST['gc_producto_nonce'], 'gc_producto_meta')) {
            return;
        }

        if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) return;
        if (!current_user_can('edit_post', $post_id)) return;

        $campos_a_guardar = [
            '_gc_productor_id' => 'absint',
            '_gc_precio' => 'floatval',
            '_gc_unidad' => 'sanitize_text_field',
            '_gc_cantidad_minima' => 'floatval',
            '_gc_stock' => 'floatval',
            '_gc_temporada' => 'sanitize_text_field',
            '_gc_origen' => 'sanitize_text_field',
        ];

        foreach ($campos_a_guardar as $campo => $funcion_sanitizar) {
            $campo_form = str_replace('_gc_', 'gc_', $campo);
            if (isset($_POST[$campo_form])) {
                $valor = $_POST[$campo_form];
                if ($funcion_sanitizar === 'absint') {
                    $valor = absint($valor);
                } elseif ($funcion_sanitizar === 'floatval') {
                    $valor = floatval($valor);
                } else {
                    $valor = $funcion_sanitizar($valor);
                }
                update_post_meta($post_id, $campo, $valor);
            }
        }
    }

    /**
     * Guarda meta del ciclo
     */
    public function guardar_meta_ciclo($post_id) {
        if (!isset($_POST['gc_ciclo_nonce']) ||
            !wp_verify_nonce($_POST['gc_ciclo_nonce'], 'gc_ciclo_meta')) {
            return;
        }

        if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) return;
        if (!current_user_can('edit_post', $post_id)) return;

        $campos_a_guardar = [
            '_gc_fecha_inicio' => 'sanitize_text_field',
            '_gc_fecha_cierre' => 'sanitize_text_field',
            '_gc_fecha_entrega' => 'sanitize_text_field',
            '_gc_lugar_entrega' => 'sanitize_text_field',
            '_gc_hora_entrega' => 'sanitize_text_field',
            '_gc_notas' => 'sanitize_textarea_field',
            '_gc_tipo_recurrencia' => 'sanitize_text_field',
            '_gc_recurrencia_fin' => 'sanitize_text_field',
        ];

        foreach ($campos_a_guardar as $campo => $funcion_sanitizar) {
            $campo_form = str_replace('_gc_', 'gc_', $campo);
            if (isset($_POST[$campo_form])) {
                update_post_meta($post_id, $campo, $funcion_sanitizar($_POST[$campo_form]));
            }
        }

        // Campos checkbox
        $es_recurrente = isset($_POST['gc_es_recurrente']) ? '1' : '0';
        update_post_meta($post_id, '_gc_es_recurrente', $es_recurrente);

        $auto_publicar = isset($_POST['gc_auto_publicar']) ? '1' : '0';
        update_post_meta($post_id, '_gc_auto_publicar', $auto_publicar);
    }

    /**
     * Genera el siguiente ciclo recurrente
     *
     * @param int $ciclo_id ID del ciclo plantilla
     * @return int|false ID del nuevo ciclo o false si falla
     */
    public function generar_siguiente_ciclo($ciclo_id) {
        $ciclo_actual = get_post($ciclo_id);
        if (!$ciclo_actual || $ciclo_actual->post_type !== 'gc_ciclo') {
            return false;
        }

        // Verificar que es recurrente
        $es_recurrente = get_post_meta($ciclo_id, '_gc_es_recurrente', true);
        if ($es_recurrente !== '1') {
            return false;
        }

        // Verificar fecha fin de recurrencia
        $recurrencia_fin = get_post_meta($ciclo_id, '_gc_recurrencia_fin', true);
        if (!empty($recurrencia_fin) && strtotime($recurrencia_fin) < time()) {
            return false;
        }

        // Obtener datos del ciclo actual
        $fecha_inicio = get_post_meta($ciclo_id, '_gc_fecha_inicio', true);
        $fecha_cierre = get_post_meta($ciclo_id, '_gc_fecha_cierre', true);
        $fecha_entrega = get_post_meta($ciclo_id, '_gc_fecha_entrega', true);
        $lugar_entrega = get_post_meta($ciclo_id, '_gc_lugar_entrega', true);
        $hora_entrega = get_post_meta($ciclo_id, '_gc_hora_entrega', true);
        $notas = get_post_meta($ciclo_id, '_gc_notas', true);
        $tipo_recurrencia = get_post_meta($ciclo_id, '_gc_tipo_recurrencia', true) ?: 'semanal';
        $auto_publicar = get_post_meta($ciclo_id, '_gc_auto_publicar', true);

        // Calcular días de desplazamiento según tipo de recurrencia
        $dias_desplazamiento = [
            'semanal' => 7,
            'quincenal' => 14,
            'mensual' => 30,
            'bimensual' => 60,
        ];
        $dias = $dias_desplazamiento[$tipo_recurrencia] ?? 7;

        // Calcular nuevas fechas
        $nueva_fecha_inicio = date('Y-m-d\TH:i', strtotime($fecha_inicio . ' +' . $dias . ' days'));
        $nueva_fecha_cierre = date('Y-m-d\TH:i', strtotime($fecha_cierre . ' +' . $dias . ' days'));
        $nueva_fecha_entrega = date('Y-m-d', strtotime($fecha_entrega . ' +' . $dias . ' days'));

        // Verificar que la nueva fecha no supere el fin de recurrencia
        if (!empty($recurrencia_fin) && strtotime($nueva_fecha_inicio) > strtotime($recurrencia_fin)) {
            return false;
        }

        // Generar título del nuevo ciclo
        $numero_ciclo = 1;
        $titulo_base = preg_replace('/\s*#\d+$/', '', $ciclo_actual->post_title);
        $titulo_base = preg_replace('/\s*\(\d+\)$/', '', $titulo_base);
        $titulo_base = trim($titulo_base);

        // Buscar el número más alto de ciclos con este título base
        $ciclos_similares = get_posts([
            'post_type' => 'gc_ciclo',
            'posts_per_page' => -1,
            'post_status' => 'any',
            'meta_query' => [
                [
                    'key' => '_gc_ciclo_padre',
                    'value' => $ciclo_id,
                ],
            ],
        ]);
        $numero_ciclo = count($ciclos_similares) + 2;

        $nuevo_titulo = $titulo_base . ' #' . $numero_ciclo;

        // Crear el nuevo ciclo
        $nuevo_ciclo_id = wp_insert_post([
            'post_type' => 'gc_ciclo',
            'post_title' => $nuevo_titulo,
            'post_status' => $auto_publicar === '1' ? 'publish' : 'draft',
            'post_author' => $ciclo_actual->post_author,
        ]);

        if (is_wp_error($nuevo_ciclo_id)) {
            return false;
        }

        // Guardar meta del nuevo ciclo
        update_post_meta($nuevo_ciclo_id, '_gc_fecha_inicio', $nueva_fecha_inicio);
        update_post_meta($nuevo_ciclo_id, '_gc_fecha_cierre', $nueva_fecha_cierre);
        update_post_meta($nuevo_ciclo_id, '_gc_fecha_entrega', $nueva_fecha_entrega);
        update_post_meta($nuevo_ciclo_id, '_gc_lugar_entrega', $lugar_entrega);
        update_post_meta($nuevo_ciclo_id, '_gc_hora_entrega', $hora_entrega);
        update_post_meta($nuevo_ciclo_id, '_gc_notas', $notas);
        update_post_meta($nuevo_ciclo_id, '_gc_es_recurrente', '1');
        update_post_meta($nuevo_ciclo_id, '_gc_tipo_recurrencia', $tipo_recurrencia);
        update_post_meta($nuevo_ciclo_id, '_gc_recurrencia_fin', $recurrencia_fin);
        update_post_meta($nuevo_ciclo_id, '_gc_auto_publicar', $auto_publicar);
        update_post_meta($nuevo_ciclo_id, '_gc_ciclo_padre', $ciclo_id);
        update_post_meta($nuevo_ciclo_id, '_gc_estado', 'pendiente');

        // Disparar action para que otros sistemas se enteren
        do_action('gc_ciclo_recurrente_creado', $nuevo_ciclo_id, $ciclo_id);

        return $nuevo_ciclo_id;
    }

    /**
     * Verifica y genera ciclos recurrentes pendientes
     * Se ejecuta via WP-Cron
     */
    public function verificar_ciclos_recurrentes() {
        // Buscar ciclos recurrentes que han cerrado
        $ciclos_cerrados = get_posts([
            'post_type' => 'gc_ciclo',
            'posts_per_page' => -1,
            'post_status' => 'publish',
            'meta_query' => [
                'relation' => 'AND',
                [
                    'key' => '_gc_es_recurrente',
                    'value' => '1',
                ],
                [
                    'key' => '_gc_fecha_cierre',
                    'value' => current_time('Y-m-d\TH:i'),
                    'compare' => '<',
                    'type' => 'DATETIME',
                ],
                [
                    'key' => '_gc_siguiente_generado',
                    'compare' => 'NOT EXISTS',
                ],
            ],
        ]);

        foreach ($ciclos_cerrados as $ciclo) {
            $nuevo_ciclo_id = $this->generar_siguiente_ciclo($ciclo->ID);
            if ($nuevo_ciclo_id) {
                // Marcar que ya se generó el siguiente
                update_post_meta($ciclo->ID, '_gc_siguiente_generado', $nuevo_ciclo_id);
            }
        }
    }

    /**
     * Programa el cron de ciclos recurrentes
     */
    public function programar_cron_ciclos_recurrentes() {
        if (!wp_next_scheduled('gc_verificar_ciclos_recurrentes')) {
            wp_schedule_event(time(), 'hourly', 'gc_verificar_ciclos_recurrentes');
        }
    }

    /**
     * Columnas personalizadas para ciclos
     */
    public function columnas_ciclo($columnas) {
        $nuevas_columnas = [
            'cb' => $columnas['cb'],
            'title' => $columnas['title'],
            'gc_fechas' => __('Fechas', 'flavor-platform'),
            'gc_estado_ciclo' => __('Estado', 'flavor-platform'),
            'gc_total_pedidos' => __('Pedidos', 'flavor-platform'),
            'date' => $columnas['date'],
        ];
        return $nuevas_columnas;
    }

    /**
     * Contenido columnas ciclo
     */
    public function contenido_columnas_ciclo($columna, $post_id) {
        switch ($columna) {
            case 'gc_fechas':
                $fecha_cierre = get_post_meta($post_id, '_gc_fecha_cierre', true);
                $fecha_entrega = get_post_meta($post_id, '_gc_fecha_entrega', true);
                echo '<strong>' . __('Cierre:', 'flavor-platform') . '</strong> ' .
                     esc_html(date('d/m/Y H:i', strtotime($fecha_cierre))) . '<br>';
                echo '<strong>' . __('Entrega:', 'flavor-platform') . '</strong> ' .
                     esc_html(date('d/m/Y', strtotime($fecha_entrega)));
                break;

            case 'gc_estado_ciclo':
                $estado = get_post_status($post_id);
                $etiquetas = [
                    'gc_abierto' => '<span style="color: green;">⬤ ' . __('Abierto', 'flavor-platform') . '</span>',
                    'gc_cerrado' => '<span style="color: orange;">⬤ ' . __('Cerrado', 'flavor-platform') . '</span>',
                    'gc_entregado' => '<span style="color: blue;">⬤ ' . __('Entregado', 'flavor-platform') . '</span>',
                ];
                echo $etiquetas[$estado] ?? $estado;
                break;

            case 'gc_total_pedidos':
                global $wpdb;
                $tabla_pedidos = $wpdb->prefix . 'flavor_gc_pedidos';
                $total = $wpdb->get_var($wpdb->prepare(
                    "SELECT COUNT(DISTINCT usuario_id) FROM $tabla_pedidos WHERE ciclo_id = %d",
                    $post_id
                ));
                echo intval($total);
                break;
        }
    }

    /**
     * Columnas producto
     */
    public function columnas_producto($columnas) {
        $nuevas_columnas = [
            'cb' => $columnas['cb'],
            'title' => $columnas['title'],
            'gc_productor' => __('Productor', 'flavor-platform'),
            'gc_precio_producto' => __('Precio', 'flavor-platform'),
            'gc_stock_producto' => __('Stock', 'flavor-platform'),
            'date' => $columnas['date'],
        ];
        return $nuevas_columnas;
    }

    /**
     * Contenido columnas producto
     */
    public function contenido_columnas_producto($columna, $post_id) {
        switch ($columna) {
            case 'gc_productor':
                $productor_id = get_post_meta($post_id, '_gc_productor_id', true);
                if ($productor_id) {
                    $productor = get_post($productor_id);
                    echo $productor ? esc_html($productor->post_title) : '—';
                }
                break;

            case 'gc_precio_producto':
                $precio = get_post_meta($post_id, '_gc_precio', true);
                $unidad = get_post_meta($post_id, '_gc_unidad', true);
                echo $precio ? number_format($precio, 2) . ' €/' . esc_html($unidad) : '—';
                break;

            case 'gc_stock_producto':
                $stock = get_post_meta($post_id, '_gc_stock', true);
                echo $stock ? esc_html($stock) : __('Ilimitado', 'flavor-platform');
                break;
        }
    }

    /**
     * Cierra ciclos automáticamente cuando llega la fecha
     */
    public function cerrar_ciclos_automatico() {
        $ciclos_abiertos = get_posts([
            'post_type' => 'gc_ciclo',
            'post_status' => 'gc_abierto',
            'posts_per_page' => -1,
        ]);

        $fecha_actual = current_time('timestamp');

        foreach ($ciclos_abiertos as $ciclo) {
            $fecha_cierre = get_post_meta($ciclo->ID, '_gc_fecha_cierre', true);
            if ($fecha_cierre && strtotime($fecha_cierre) <= $fecha_actual) {
                wp_update_post([
                    'ID' => $ciclo->ID,
                    'post_status' => 'gc_cerrado',
                ]);

                // Notificar a coordinadores
                do_action('gc_ciclo_cerrado', $ciclo->ID);
            }
        }
    }

    /**
     * {@inheritdoc}
     */
    public function get_actions() {
        return [
            'listar_productos' => [
                'description' => 'Listar productos disponibles',
                'params' => ['categoria', 'productor_id', 'limite'],
            ],
            'ciclo_actual' => [
                'description' => 'Obtener información del ciclo actual',
                'params' => [],
            ],
            'hacer_pedido' => [
                'description' => 'Realizar un pedido en el ciclo actual',
                'params' => ['productos'], // [{producto_id, cantidad}, ...]
            ],
            'ver_mi_pedido' => [
                'description' => 'Ver el pedido del usuario en el ciclo actual',
                'params' => [],
            ],
            'modificar_pedido' => [
                'description' => 'Modificar un pedido existente',
                'params' => ['productos'],
            ],
            'buscar_productor' => [
                'description' => 'Buscar productores',
                'params' => ['busqueda', 'certificacion_eco'],
            ],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function execute_action($nombre_accion, $parametros) {
        $aliases = [
            'listar' => 'listar_productos',
            'listado' => 'listar_productos',
            'explorar' => 'listar_productos',
            'buscar' => 'listar_productos',
            'productos' => 'listar_productos',
            'catalogo' => 'listar_productos',
            'productores' => 'buscar_productor',
            'buscar_productor' => 'buscar_productor',
            'ciclo' => 'ciclo_actual',
            'ciclo_actual' => 'ciclo_actual',
            'pedido' => 'ver_mi_pedido',
            'mi_pedido' => 'ver_mi_pedido',
            'mi-pedido' => 'ver_mi_pedido',
            'hacer_pedido' => 'hacer_pedido',
            'modificar_pedido' => 'modificar_pedido',
        ];

        $nombre_accion = $aliases[$nombre_accion] ?? $nombre_accion;
        $metodo_accion = 'action_' . $nombre_accion;

        if (method_exists($this, $metodo_accion)) {
            return $this->$metodo_accion($parametros);
        }

        switch ($nombre_accion) {
            case 'grupos':
                return do_shortcode('[flavor module="grupos-consumo" view="listado" header="no" limit="12"]');
            case 'productos':
                return do_shortcode('[gc_catalogo]');
            case 'productores':
                return do_shortcode('[gc_productores]');
            case 'mi-cesta':
                return do_shortcode('[gc_mi_cesta]');
            case 'mis-pedidos':
                return do_shortcode('[gc_historial]');
            case 'ciclos':
                return do_shortcode('[gc_ciclo_actual]');
            case 'suscripciones':
                return do_shortcode('[gc_suscripciones]');
            case 'panel':
                return do_shortcode('[gc_panel]');
            case 'unirme':
                return do_shortcode('[gc_formulario_union]');
            case 'foro':
                $entity_id = absint($parametros['grupo_id'] ?? $parametros['id'] ?? 0);
                if ($entity_id > 0) {
                    return do_shortcode(sprintf(
                        '[flavor_foros_integrado entidad="grupo_consumo" entidad_id="%d"]',
                        $entity_id
                    ));
                }
                return do_shortcode('[foros_actividad_reciente limit="8"]');
            case 'recetas':
                return do_shortcode('[flavor module="recetas" view="listado" header="no" limit="12"]');
        }

        return [
            'success' => false,
            'error' => __('La vista solicitada no está disponible en Grupos de Consumo.', 'flavor-platform'),
        ];
    }

    /**
     * Acción: Listar productos
     */
    private function action_listar_productos($parametros) {
        $pagina = max(1, absint($parametros['pagina'] ?? 1));
        $args_query = [
            'post_type' => 'gc_producto',
            'post_status' => 'publish',
            'posts_per_page' => absint($parametros['limite'] ?? 20),
            'paged' => $pagina,
        ];

        if (!empty($parametros['categoria'])) {
            $args_query['tax_query'] = [
                [
                    'taxonomy' => 'gc_categoria',
                    'field' => 'slug',
                    'terms' => sanitize_text_field($parametros['categoria']),
                ],
            ];
        }

        if (!empty($parametros['productor_id'])) {
            $args_query['meta_query'] = [
                [
                    'key' => '_gc_productor_id',
                    'value' => absint($parametros['productor_id']),
                ],
            ];
        }

        $query = new WP_Query($args_query);
        $productos_formateados = [];

        if ($query->have_posts()) {
            while ($query->have_posts()) {
                $query->the_post();
                $post_id = get_the_ID();

                $productor_id = get_post_meta($post_id, '_gc_productor_id', true);
                $productor = get_post($productor_id);

                $productos_formateados[] = [
                    'id' => $post_id,
                    'nombre' => get_the_title(),
                    'descripcion' => wp_trim_words(get_the_content(), 20),
                    'precio' => floatval(get_post_meta($post_id, '_gc_precio', true)),
                    'unidad' => get_post_meta($post_id, '_gc_unidad', true),
                    'stock' => get_post_meta($post_id, '_gc_stock', true),
                    'productor' => $productor ? $productor->post_title : '',
                    'imagen' => get_the_post_thumbnail_url($post_id, 'medium'),
                ];
            }
            wp_reset_postdata();
        }

        return [
            'success' => true,
            'total' => count($productos_formateados),
            'productos' => $productos_formateados,
            'pagina_actual' => $pagina,
            'total_paginas' => $query->max_num_pages,
            'total_productos' => $query->found_posts,
            'hay_mas' => $pagina < $query->max_num_pages,
        ];
    }

    /**
     * Acción: Ciclo actual
     */
    private function action_ciclo_actual($parametros) {
        $ciclo_actual = get_posts([
            'post_type' => 'gc_ciclo',
            'post_status' => 'gc_abierto',
            'posts_per_page' => 1,
            'orderby' => 'date',
            'order' => 'DESC',
        ]);

        if (empty($ciclo_actual)) {
            return [
                'success' => false,
                'error' => __('No hay ningún ciclo abierto actualmente.', 'flavor-platform'),
            ];
        }

        $ciclo = $ciclo_actual[0];
        $fecha_cierre = get_post_meta($ciclo->ID, '_gc_fecha_cierre', true);
        $fecha_entrega = get_post_meta($ciclo->ID, '_gc_fecha_entrega', true);
        $lugar_entrega = get_post_meta($ciclo->ID, '_gc_lugar_entrega', true);

        return [
            'success' => true,
            'ciclo' => [
                'id' => $ciclo->ID,
                'nombre' => $ciclo->post_title,
                'fecha_cierre' => $fecha_cierre,
                'fecha_entrega' => $fecha_entrega,
                'lugar_entrega' => $lugar_entrega,
                'tiempo_restante' => human_time_diff(current_time('timestamp'), strtotime($fecha_cierre)),
            ],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function get_tool_definitions() {
        return [
            [
                'name' => 'gc_listar_productos',
                'description' => 'Lista los productos disponibles del grupo de consumo',
                'input_schema' => [
                    'type' => 'object',
                    'properties' => [
                        'categoria' => [
                            'type' => 'string',
                            'description' => 'Categoría de productos',
                            'enum' => ['frutas', 'verduras', 'lacteos', 'carne', 'pescado', 'pan', 'conservas', 'bebidas', 'otros'],
                        ],
                        'productor_id' => [
                            'type' => 'integer',
                            'description' => 'ID del productor',
                        ],
                        'limite' => [
                            'type' => 'integer',
                            'description' => 'Número máximo de productos',
                        ],
                    ],
                ],
            ],
            [
                'name' => 'gc_ciclo_actual',
                'description' => 'Obtiene información del ciclo de pedidos actual',
                'input_schema' => [
                    'type' => 'object',
                    'properties' => [],
                ],
            ],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function get_knowledge_base() {
        return <<<KNOWLEDGE
**Grupos de Consumo**

Un grupo de consumo es una asociación de consumidores que se organizan para comprar directamente a productores locales.

**Funcionamiento:**
1. **Ciclos de Pedido**: Períodos (normalmente semanales o quincenales) donde se abren pedidos
2. **Productos Locales**: De productores cercanos, priorizando ecológicos
3. **Compra Colectiva**: Se juntan pedidos para conseguir mejores precios
4. **Repartos**: En un lugar y fecha acordados

**Ventajas:**
- Productos frescos y de temporada
- Apoyo a productores locales
- Precios justos para productor y consumidor
- Trazabilidad completa
- Reducción de intermediarios
- Menor huella ecológica

**Ciclo de Pedido:**
1. Se abre el ciclo con fechas de cierre y entrega
2. Los miembros hacen pedidos online
3. Se cierra el ciclo automáticamente
4. Se consolidan pedidos por productor
5. Entrega en el punto acordado

**Categorías de productos**: Frutas, Verduras, Lácteos, Carne, Pescado, Pan, Conservas, Bebidas, etc.
KNOWLEDGE;
    }

    /**
     * {@inheritdoc}
     */
    public function get_faqs() {
        return [
            [
                'pregunta' => '¿Cómo funciona el grupo de consumo?',
                'respuesta' => 'Se abren ciclos de pedido periódicos donde puedes comprar productos directamente de productores locales. Los pedidos se recogen en un punto acordado.',
            ],
            [
                'pregunta' => '¿Cuándo puedo hacer pedidos?',
                'respuesta' => 'Durante el ciclo abierto. Consulta las fechas de apertura y cierre del ciclo actual.',
            ],
            [
                'pregunta' => '¿Los productos son ecológicos?',
                'respuesta' => 'Priorizamos productos ecológicos certificados, aunque también trabajamos con productores locales de agricultura sostenible.',
            ],
            [
                'pregunta' => '¿Puedo modificar mi pedido?',
                'respuesta' => 'Sí, hasta 24 horas antes del cierre del ciclo puedes modificar tu pedido.',
            ],
        ];
    }

    /**
     * Shortcode: Ciclo actual / Ciclos
     *
     * @param array $atributos Atributos del shortcode:
     *                         - vista: 'actual', 'listado', 'calendario' (default: 'actual')
     *                         - limite: número máximo de ciclos a mostrar
     *                         - mostrar_pasados: 'si' o 'no' (default: 'no')
     */
    public function shortcode_ciclo_actual($atributos) {
        $atributos = shortcode_atts([
            'vista'           => 'actual',
            'limite'          => 5,
            'mostrar_pasados' => 'no',
        ], $atributos);

        // Vista de listado: mostrar múltiples ciclos
        if ($atributos['vista'] === 'listado') {
            return $this->render_ciclos_listado($atributos);
        }

        // Vista de calendario
        if ($atributos['vista'] === 'calendario') {
            return $this->shortcode_calendario($atributos);
        }

        // Vista por defecto: ciclo actual
        $resultado = $this->action_ciclo_actual([]);

        if (!$resultado['success']) {
            return '<p class="gc-aviso">' . esc_html($resultado['error']) . '</p>';
        }

        $ciclo = $resultado['ciclo'];
        ob_start();
        ?>
        <div class="gc-ciclo-actual">
            <h3><?php echo esc_html($ciclo['nombre']); ?></h3>
            <p><strong><?php _e('Cierra:', 'flavor-platform'); ?></strong> <?php echo esc_html($ciclo['fecha_cierre']); ?></p>
            <p><strong><?php _e('Entrega:', 'flavor-platform'); ?></strong> <?php echo esc_html($ciclo['fecha_entrega']); ?></p>
            <?php if (!empty($ciclo['lugar_entrega'])): ?>
                <p><strong><?php _e('Lugar:', 'flavor-platform'); ?></strong> <?php echo esc_html($ciclo['lugar_entrega']); ?></p>
            <?php endif; ?>
            <?php if (!empty($ciclo['tiempo_restante'])): ?>
                <p class="gc-tiempo-restante"><?php printf(__('Quedan %s para cerrar el ciclo', 'flavor-platform'), $ciclo['tiempo_restante']); ?></p>
            <?php endif; ?>
        </div>
        <?php
        return ob_get_clean();
    }

    /**
     * Renderiza un listado de ciclos
     *
     * @param array $atributos Atributos del shortcode
     * @return string HTML del listado
     */
    protected function render_ciclos_listado($atributos) {
        $limite = absint($atributos['limite']) ?: 5;
        $mostrar_pasados = $atributos['mostrar_pasados'] === 'si';

        // Query de ciclos
        $meta_query = [];
        if (!$mostrar_pasados) {
            $meta_query[] = [
                'key'     => '_gc_fecha_cierre',
                'value'   => current_time('Y-m-d'),
                'compare' => '>=',
                'type'    => 'DATE',
            ];
        }

        $ciclos = get_posts([
            'post_type'      => 'gc_ciclo',
            'post_status'    => 'publish',
            'posts_per_page' => $limite,
            'meta_query'     => $meta_query,
            'orderby'        => 'meta_value',
            'meta_key'       => '_gc_fecha_cierre',
            'order'          => 'ASC',
        ]);

        if (empty($ciclos)) {
            return '<p class="gc-sin-ciclos">' . __('No hay ciclos programados próximamente.', 'flavor-platform') . '</p>';
        }

        ob_start();
        ?>
        <div class="gc-ciclos-listado">
            <h3 class="gc-seccion-titulo">
                <span class="dashicons dashicons-calendar-alt"></span>
                <?php _e('Ciclos de Pedido', 'flavor-platform'); ?>
            </h3>
            <div class="gc-ciclos-grid">
                <?php foreach ($ciclos as $ciclo_post):
                    $estado = get_post_meta($ciclo_post->ID, '_gc_estado', true) ?: 'pendiente';
                    $fecha_cierre = get_post_meta($ciclo_post->ID, '_gc_fecha_cierre', true);
                    $fecha_entrega = get_post_meta($ciclo_post->ID, '_gc_fecha_entrega', true);
                    $lugar_entrega = get_post_meta($ciclo_post->ID, '_gc_lugar_entrega', true);
                    $es_abierto = $estado === 'abierto';
                ?>
                    <div class="gc-ciclo-card gc-ciclo-<?php echo esc_attr($estado); ?>">
                        <div class="gc-ciclo-header">
                            <h4><?php echo esc_html($ciclo_post->post_title); ?></h4>
                            <span class="gc-ciclo-estado gc-estado-<?php echo esc_attr($estado); ?>">
                                <?php echo esc_html(ucfirst($estado)); ?>
                            </span>
                        </div>
                        <div class="gc-ciclo-fechas">
                            <p>
                                <span class="dashicons dashicons-clock"></span>
                                <strong><?php _e('Cierre:', 'flavor-platform'); ?></strong>
                                <?php echo $fecha_cierre ? date_i18n('j M Y', strtotime($fecha_cierre)) : '-'; ?>
                            </p>
                            <p>
                                <span class="dashicons dashicons-location"></span>
                                <strong><?php _e('Entrega:', 'flavor-platform'); ?></strong>
                                <?php echo $fecha_entrega ? date_i18n('j M Y', strtotime($fecha_entrega)) : '-'; ?>
                            </p>
                            <?php if ($lugar_entrega): ?>
                                <p class="gc-ciclo-lugar">
                                    <span class="dashicons dashicons-location-alt"></span>
                                    <?php echo esc_html($lugar_entrega); ?>
                                </p>
                            <?php endif; ?>
                        </div>
                        <?php if ($es_abierto): ?>
                            <a href="<?php echo esc_url(add_query_arg('ciclo', intval($ciclo_post->ID), home_url('/mi-portal/grupos-consumo/ciclo/'))); ?>" class="gc-btn gc-btn-primary gc-btn-sm">
                                <?php _e('Ver productos', 'flavor-platform'); ?>
                            </a>
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
        <style>
        .gc-ciclos-listado { padding: 20px 0; }
        .gc-seccion-titulo { display: flex; align-items: center; gap: 10px; margin-bottom: 20px; font-size: 1.25rem; }
        .gc-ciclos-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(280px, 1fr)); gap: 20px; }
        .gc-ciclo-card { background: #fff; border-radius: 12px; padding: 20px; box-shadow: 0 2px 8px rgba(0,0,0,0.08); border: 1px solid #e5e7eb; }
        .gc-ciclo-card.gc-ciclo-abierto { border-color: #16a34a; }
        .gc-ciclo-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 15px; }
        .gc-ciclo-header h4 { margin: 0; font-size: 1.1rem; }
        .gc-ciclo-estado { padding: 4px 10px; border-radius: 20px; font-size: 0.75rem; font-weight: 600; text-transform: uppercase; }
        .gc-estado-abierto { background: #dcfce7; color: #16a34a; }
        .gc-estado-cerrado { background: #fee2e2; color: #dc2626; }
        .gc-estado-pendiente { background: #fef3c7; color: #d97706; }
        .gc-ciclo-fechas p { margin: 8px 0; font-size: 0.9rem; display: flex; align-items: center; gap: 6px; }
        .gc-ciclo-fechas .dashicons { font-size: 16px; width: 16px; height: 16px; color: #6b7280; }
        .gc-btn-sm { display: inline-block; margin-top: 15px; padding: 8px 16px; background: #4a7c59; color: #fff; text-decoration: none; border-radius: 6px; font-size: 0.875rem; font-weight: 500; }
        .gc-btn-sm:hover { background: #3d6a4a; color: #fff; }
        </style>
        <?php
        return ob_get_clean();
    }

    /**
     * Shortcode: Productos
     *
     * @param array $atributos Atributos del shortcode:
     *                         - vista: 'catalogo', 'grid', 'list' (default: 'grid')
     *                         - categoria: slug de categoría para filtrar
     *                         - limite/limit: número máximo de productos
     *                         - productor: ID del productor para filtrar
     */
    public function shortcode_productos($atributos) {
        $atributos = shortcode_atts([
            'vista'     => 'grid',
            'categoria' => '',
            'limite'    => 12,
            'limit'     => 12, // Alias
            'productor' => '',
        ], $atributos);

        // Usar limit si se proporciona
        $limite = absint($atributos['limit']) ?: absint($atributos['limite']);

        // Si vista es 'catalogo', usar el template de catálogo completo
        if ($atributos['vista'] === 'catalogo') {
            return $this->render_catalogo_completo($atributos);
        }

        $resultado = $this->action_listar_productos($atributos);

        if (!$resultado['success']) {
            return '<p>' . esc_html($resultado['error']) . '</p>';
        }

        ob_start();
        ?>
        <div class="gc-productos-grid">
            <?php foreach ($resultado['productos'] as $producto): ?>
                <div class="gc-producto-card">
                    <?php if ($producto['imagen']): ?>
                        <img src="<?php echo esc_url($producto['imagen']); ?>" alt="<?php echo esc_attr($producto['nombre']); ?>" />
                    <?php endif; ?>
                    <h4><?php echo esc_html($producto['nombre']); ?></h4>
                    <p class="gc-productor"><?php echo esc_html($producto['productor']); ?></p>
                    <p class="gc-precio"><?php echo number_format($producto['precio'], 2); ?> € / <?php echo esc_html($producto['unidad']); ?></p>
                    <button class="gc-anadir-pedido" data-producto-id="<?php echo $producto['id']; ?>">
                        <?php _e('Añadir al pedido', 'flavor-platform'); ?>
                    </button>
                </div>
            <?php endforeach; ?>
        </div>
        <script>
        (function() {
            document.querySelectorAll('.gc-anadir-pedido').forEach(function(btn) {
                if (btn.dataset.gcBound) return;
                btn.dataset.gcBound = 'true';
                btn.addEventListener('click', function(e) {
                    e.preventDefault();
                    var productoId = this.dataset.productoId;
                    var btnEl = this;

                    // Crear modal
                    var modal = document.createElement('div');
                    modal.className = 'gc-modal-overlay';
                    modal.id = 'modal-anadir-producto';
                    modal.innerHTML = '<div class="gc-modal-content">' +
                        '<button type="button" class="gc-modal-close">&times;</button>' +
                        '<h3>Añadir al pedido</h3>' +
                        '<form class="gc-form-anadir">' +
                        '<div class="gc-form-group">' +
                        '<label>Cantidad</label>' +
                        '<div class="gc-cantidad-control-modal">' +
                        '<button type="button" class="gc-btn-modal-menos">−</button>' +
                        '<input type="number" name="cantidad" value="1" min="1" max="99">' +
                        '<button type="button" class="gc-btn-modal-mas">+</button>' +
                        '</div></div>' +
                        '<div class="gc-form-actions">' +
                        '<button type="submit" class="gc-btn gc-btn-primary">Confirmar</button>' +
                        '<button type="button" class="gc-btn gc-btn-secondary gc-modal-cancelar">Cancelar</button>' +
                        '</div></form></div>';
                    document.body.appendChild(modal);

                    var input = modal.querySelector('input[name="cantidad"]');
                    modal.querySelector('.gc-btn-modal-menos').onclick = function() {
                        input.value = Math.max(1, parseInt(input.value) - 1);
                    };
                    modal.querySelector('.gc-btn-modal-mas').onclick = function() {
                        input.value = Math.min(99, parseInt(input.value) + 1);
                    };
                    modal.querySelector('.gc-modal-close').onclick = function() { modal.remove(); };
                    modal.querySelector('.gc-modal-cancelar').onclick = function() { modal.remove(); };
                    modal.onclick = function(e) { if (e.target === modal) modal.remove(); };

                    modal.querySelector('form').onsubmit = function(e) {
                        e.preventDefault();
                        var cantidad = input.value;
                        var submitBtn = this.querySelector('button[type="submit"]');
                        submitBtn.disabled = true;
                        submitBtn.textContent = 'Añadiendo...';

                        var formData = new FormData();
                        formData.append('action', 'gc_agregar_lista');
                        formData.append('nonce', '<?php echo wp_create_nonce("gc_nonce"); ?>');
                        formData.append('producto_id', productoId);
                        formData.append('cantidad', cantidad);

                        fetch('<?php echo admin_url("admin-ajax.php"); ?>', {
                            method: 'POST',
                            body: formData
                        })
                        .then(function(r) { return r.json(); })
                        .then(function(data) {
                            if (data.success) {
                                btnEl.classList.add('gc-en-pedido');
                                btnEl.textContent = '✓ En pedido';
                                modal.remove();
                            } else {
                                if (window.gcToast) {
                                    window.gcToast(data.data?.message || 'Error al añadir', 'error');
                                }
                                submitBtn.disabled = false;
                                submitBtn.textContent = 'Confirmar';
                            }
                        })
                        .catch(function() {
                            if (window.gcToast) {
                                window.gcToast('Error de conexión', 'error');
                            }
                            submitBtn.disabled = false;
                            submitBtn.textContent = 'Confirmar';
                        });
                    };
                });
            });
        })();
        </script>
        <style>
        .gc-modal-overlay{position:fixed;top:0;left:0;width:100%;height:100%;background:rgba(0,0,0,0.6);display:flex;align-items:center;justify-content:center;z-index:99999}
        .gc-modal-content{background:#fff;padding:30px;border-radius:16px;max-width:400px;width:90%;position:relative;box-shadow:0 20px 60px rgba(0,0,0,0.3)}
        .gc-modal-close{position:absolute;top:15px;right:15px;width:32px;height:32px;background:#f0f0f0;border:none;border-radius:50%;font-size:20px;cursor:pointer}
        .gc-modal-content h3{margin:0 0 25px;color:#2c5530;font-size:20px}
        .gc-form-group{margin-bottom:25px}
        .gc-form-group label{display:block;margin-bottom:10px;font-weight:600}
        .gc-cantidad-control-modal{display:flex;align-items:center;gap:10px}
        .gc-cantidad-control-modal input{width:80px;padding:12px;text-align:center;border:2px solid #ddd;border-radius:8px;font-size:18px;font-weight:600}
        .gc-btn-modal-menos,.gc-btn-modal-mas{width:44px;height:44px;border:2px solid #4a7c59;background:#fff;color:#4a7c59;border-radius:8px;font-size:24px;cursor:pointer}
        .gc-btn-modal-menos:hover,.gc-btn-modal-mas:hover{background:#4a7c59;color:#fff}
        .gc-form-actions{display:flex;gap:12px}
        .gc-btn{flex:1;padding:14px 20px;border:none;border-radius:8px;font-size:15px;font-weight:600;cursor:pointer}
        .gc-btn-primary{background:#4a7c59;color:#fff}
        .gc-btn-secondary{background:#f0f0f0;color:#666}
        .gc-anadir-pedido{display:inline-block;padding:10px 20px;background:#4a7c59;color:#fff;border:none;border-radius:6px;font-size:14px;font-weight:600;cursor:pointer}
        .gc-anadir-pedido.gc-en-pedido{background:#2196f3}
        </style>
        <?php
        return ob_get_clean();
    }

    /**
     * Renderiza el catálogo completo de productos con filtros
     *
     * @param array $atributos Atributos del shortcode
     * @return string HTML del catálogo
     */
    protected function render_catalogo_completo($atributos) {
        // Intentar cargar el template del catálogo si existe
        $template_path = FLAVOR_CHAT_IA_PATH . 'includes/modules/grupos-consumo/templates/catalogo.php';

        if (file_exists($template_path)) {
            // Preparar datos para el template
            $limite = absint($atributos['limit']) ?: absint($atributos['limite']);

            // Obtener productos
            $args_query = [
                'post_type'      => 'gc_producto',
                'post_status'    => 'publish',
                'posts_per_page' => $limite ?: -1,
                'orderby'        => 'title',
                'order'          => 'ASC',
            ];

            if (!empty($atributos['productor'])) {
                $args_query['meta_query'][] = [
                    'key'   => '_gc_productor_id',
                    'value' => absint($atributos['productor']),
                ];
            }

            if (!empty($atributos['categoria'])) {
                $args_query['tax_query'] = [[
                    'taxonomy' => 'gc_categoria',
                    'field'    => 'slug',
                    'terms'    => sanitize_text_field($atributos['categoria']),
                ]];
            }

            $productos = get_posts($args_query);

            // Obtener productores para filtros
            $productores = get_posts([
                'post_type'      => 'gc_productor',
                'posts_per_page' => -1,
                'orderby'        => 'title',
                'order'          => 'ASC',
            ]);

            // Obtener categorías
            $categorias = get_terms([
                'taxonomy'   => 'gc_categoria',
                'hide_empty' => true,
            ]);

            // Lista de compra del usuario
            $lista_compra = [];
            if (is_user_logged_in()) {
                global $wpdb;
                $items = $wpdb->get_results($wpdb->prepare(
                    "SELECT producto_id, cantidad FROM {$wpdb->prefix}flavor_gc_lista_compra WHERE usuario_id = %d",
                    get_current_user_id()
                ));
                foreach ($items as $item) {
                    $lista_compra[$item->producto_id] = floatval($item->cantidad);
                }
            }

            // Obtener ciclo activo
            $ciclo_activo = $this->obtener_ciclo_activo_interno();

            // Configuración del módulo
            $opciones = get_option('flavor_chat_modules', []);
            $porcentaje_gestion = floatval($opciones['grupos_consumo']['settings']['porcentaje_gestion'] ?? 5);

            $args = [
                'productos'          => $productos,
                'productores'        => $productores,
                'categorias'         => is_array($categorias) ? $categorias : [],
                'lista_compra'       => $lista_compra,
                'ciclo_activo'       => $ciclo_activo,
                'porcentaje_gestion' => $porcentaje_gestion,
                'atts'               => $atributos,
            ];

            ob_start();
            include $template_path;
            return ob_get_clean();
        }

        // Fallback: usar el render simple de productos
        $resultado = $this->action_listar_productos($atributos);

        if (!$resultado['success']) {
            return '<p>' . esc_html($resultado['error']) . '</p>';
        }

        ob_start();
        ?>
        <div class="gc-catalogo gc-catalogo-completo">
            <div class="gc-catalogo-header">
                <h2><?php _e('Catálogo de Productos', 'flavor-platform'); ?></h2>
            </div>
            <div class="gc-productos-grid gc-productos-catalogo">
                <?php if (empty($resultado['productos'])): ?>
                    <p class="gc-sin-productos"><?php _e('No hay productos disponibles en este momento.', 'flavor-platform'); ?></p>
                <?php else: ?>
                    <?php foreach ($resultado['productos'] as $producto): ?>
                        <div class="gc-producto-card">
                            <?php if (!empty($producto['imagen'])): ?>
                                <div class="gc-producto-imagen">
                                    <img src="<?php echo esc_url($producto['imagen']); ?>" alt="<?php echo esc_attr($producto['nombre']); ?>" />
                                </div>
                            <?php endif; ?>
                            <div class="gc-producto-info">
                                <h4><?php echo esc_html($producto['nombre']); ?></h4>
                                <?php if (!empty($producto['productor'])): ?>
                                    <p class="gc-productor"><?php echo esc_html($producto['productor']); ?></p>
                                <?php endif; ?>
                                <p class="gc-precio"><?php echo number_format($producto['precio'], 2, ',', '.'); ?> € / <?php echo esc_html($producto['unidad']); ?></p>
                            </div>
                            <?php if (is_user_logged_in()): ?>
                                <button class="gc-anadir-pedido" data-producto-id="<?php echo esc_attr($producto['id']); ?>">
                                    <?php _e('Añadir al pedido', 'flavor-platform'); ?>
                                </button>
                            <?php endif; ?>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>
        <?php
        return ob_get_clean();
    }

    /**
     * Obtiene el ciclo activo (método interno)
     *
     * @return array|null Datos del ciclo o null
     */
    protected function obtener_ciclo_activo_interno() {
        $ciclos = get_posts([
            'post_type'      => 'gc_ciclo',
            'post_status'    => 'publish',
            'posts_per_page' => 1,
            'meta_query'     => [
                [
                    'key'   => '_gc_estado',
                    'value' => 'abierto',
                ],
            ],
            'orderby'  => 'meta_value',
            'meta_key' => '_gc_fecha_cierre',
            'order'    => 'ASC',
        ]);

        if (empty($ciclos)) {
            return null;
        }

        $ciclo = $ciclos[0];
        return [
            'id'            => $ciclo->ID,
            'titulo'        => $ciclo->post_title,
            'fecha_cierre'  => get_post_meta($ciclo->ID, '_gc_fecha_cierre', true),
            'fecha_entrega' => get_post_meta($ciclo->ID, '_gc_fecha_entrega', true),
            'hora_entrega'  => get_post_meta($ciclo->ID, '_gc_hora_entrega', true),
            'lugar_entrega' => get_post_meta($ciclo->ID, '_gc_lugar_entrega', true),
            'notas'         => get_post_meta($ciclo->ID, '_gc_notas', true),
            'estado'        => 'abierto',
        ];
    }

    /**
     * Shortcode: Mi pedido
     */
    public function shortcode_mi_pedido() {
        ob_start();

        $base_dir = dirname(__FILE__) . '/templates/';
        $template = !empty($_GET['entrega_id'])
            ? $base_dir . 'mi-pedido.php'
            : $base_dir . 'mi-cesta.php';

        if (file_exists($template)) {
            include $template;
            return ob_get_clean();
        }

        if (!is_user_logged_in()) {
            return '<p>' . __('Debes iniciar sesión para ver tu pedido.', 'flavor-platform') . '</p>';
        }

        $resultado = $this->action_ver_mi_pedido([]);

        if (!$resultado['success']) {
            return '<p>' . esc_html($resultado['error'] ?? __('No se pudo cargar tu pedido.', 'flavor-platform')) . '</p>';
        }

        return '<div class="gc-mi-pedido"></div>';
    }

    /**
     * AJAX: Hacer pedido
     */
    public function ajax_hacer_pedido() {
        check_ajax_referer('gc_pedido_nonce', 'nonce');

        if (!is_user_logged_in()) {
            wp_send_json_error(['mensaje' => __('Debes iniciar sesion.', 'flavor-platform')]);
        }

        // Verificar permiso granular para crear pedidos
        if (!Flavor_Permission_Helper::can('gc_crear_pedido')) {
            wp_send_json_error([
                'mensaje' => __('No tienes permisos para crear pedidos.', 'flavor-platform'),
                'code' => 'permission_denied',
            ], 403);
        }

        $productos = isset($_POST['productos']) ? json_decode(stripslashes($_POST['productos']), true) : [];

        if (empty($productos)) {
            wp_send_json_error(['mensaje' => __('No hay productos en el pedido.', 'flavor-platform')]);
        }

        $resultado = $this->action_hacer_pedido(['productos' => $productos]);

        if ($resultado['success']) {
            wp_send_json_success(['mensaje' => $resultado['mensaje'] ?? __('Pedido realizado correctamente.', 'flavor-platform')]);
        } else {
            wp_send_json_error(['mensaje' => $resultado['error']]);
        }
    }

    /**
     * AJAX: Modificar pedido
     */
    public function ajax_modificar_pedido() {
        check_ajax_referer('gc_pedido_nonce', 'nonce');

        if (!is_user_logged_in()) {
            wp_send_json_error(['mensaje' => __('Debes iniciar sesión.', 'flavor-platform')]);
        }

        $productos = isset($_POST['productos']) ? json_decode(stripslashes($_POST['productos']), true) : [];

        if (empty($productos)) {
            wp_send_json_error(['mensaje' => __('No hay productos para modificar.', 'flavor-platform')]);
        }

        $resultado = $this->action_modificar_pedido(['productos' => $productos]);

        if ($resultado['success']) {
            wp_send_json_success(['mensaje' => $resultado['mensaje'] ?? __('Pedido modificado correctamente.', 'flavor-platform')]);
        } else {
            wp_send_json_error(['mensaje' => $resultado['error']]);
        }
    }

    /**
     * AJAX: Solicitar unión a un grupo de consumo
     */
    public function ajax_solicitar_union() {
        check_ajax_referer('gc_nonce', 'nonce');

        $grupo_id = absint($_POST['grupo_id'] ?? 0);

        if (!$grupo_id) {
            wp_send_json_error(['mensaje' => __('Debes seleccionar un grupo de consumo.', 'flavor-platform')]);
        }

        // Verificar que el grupo existe y acepta miembros
        $grupo = get_post($grupo_id);
        if (!$grupo || $grupo->post_type !== 'gc_grupo') {
            wp_send_json_error(['mensaje' => __('El grupo de consumo no existe.', 'flavor-platform')]);
        }

        $acepta_miembros = get_post_meta($grupo_id, '_gc_acepta_miembros', true);
        if ($acepta_miembros === '0') {
            wp_send_json_error(['mensaje' => __('Este grupo no está aceptando nuevos miembros en este momento.', 'flavor-platform')]);
        }

        // Datos del usuario
        $usuario_id = 0;
        $nombre_solicitante = '';
        $email_solicitante = '';
        $telefono_solicitante = '';

        if (is_user_logged_in()) {
            $usuario_id = get_current_user_id();
            $usuario = wp_get_current_user();
            $nombre_solicitante = $usuario->display_name;
            $email_solicitante = $usuario->user_email;
        } else {
            $nombre_solicitante = sanitize_text_field($_POST['nombre'] ?? '');
            $email_solicitante = sanitize_email($_POST['email'] ?? '');
            $telefono_solicitante = sanitize_text_field($_POST['telefono'] ?? '');

            if (empty($nombre_solicitante) || empty($email_solicitante)) {
                wp_send_json_error(['mensaje' => __('El nombre y email son obligatorios.', 'flavor-platform')]);
            }

            // Verificar si ya existe un usuario con ese email
            $usuario_existente = get_user_by('email', $email_solicitante);
            if ($usuario_existente) {
                $usuario_id = $usuario_existente->ID;
            }
        }

        // Verificar si ya es miembro del grupo
        if ($usuario_id > 0) {
            $consumidor_manager = Flavor_GC_Consumidor_Manager::get_instance();
            $consumidor_existente = $consumidor_manager->obtener_consumidor($usuario_id, $grupo_id);

            if ($consumidor_existente) {
                if ($consumidor_existente->estado === 'activo') {
                    wp_send_json_error(['mensaje' => __('Ya eres miembro de este grupo de consumo.', 'flavor-platform')]);
                } elseif ($consumidor_existente->estado === 'pendiente') {
                    wp_send_json_error(['mensaje' => __('Ya tienes una solicitud pendiente para este grupo.', 'flavor-platform')]);
                }
            }
        }

        // Datos adicionales
        $direccion = sanitize_textarea_field($_POST['direccion'] ?? '');
        $preferencias = sanitize_textarea_field($_POST['preferencias'] ?? '');
        $motivacion = sanitize_textarea_field($_POST['motivacion'] ?? '');

        // Guardar la solicitud
        global $wpdb;
        $tabla_consumidores = $wpdb->prefix . 'flavor_gc_consumidores';

        // Verificar si la tabla existe
        if (!Flavor_Chat_Helpers::tabla_existe($tabla_consumidores)) {
            // Crear la tabla si no existe
            $this->crear_tabla_consumidores();
        }

        // Insertar solicitud
        $resultado_insert = $wpdb->insert(
            $tabla_consumidores,
            [
                'usuario_id' => $usuario_id,
                'grupo_id' => $grupo_id,
                'nombre' => $nombre_solicitante,
                'email' => $email_solicitante,
                'telefono' => $telefono_solicitante,
                'direccion' => $direccion,
                'preferencias' => $preferencias,
                'notas' => $motivacion,
                'estado' => 'pendiente',
                'fecha_alta' => current_time('mysql'),
            ],
            ['%d', '%d', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s']
        );

        if ($resultado_insert === false) {
            wp_send_json_error(['mensaje' => __('Error al procesar la solicitud. Inténtalo de nuevo.', 'flavor-platform')]);
        }

        // Notificar al administrador del grupo
        $admin_email = get_option('admin_email');
        $asunto = sprintf(__('[%s] Nueva solicitud de unión al grupo de consumo', 'flavor-platform'), get_bloginfo('name'));
        $mensaje_email = sprintf(
            __("Nueva solicitud de unión:\n\nGrupo: %s\nNombre: %s\nEmail: %s\nTeléfono: %s\n\nMotivación:\n%s\n\nPreferencias:\n%s\n\nDirección:\n%s", 'flavor-platform'),
            $grupo->post_title,
            $nombre_solicitante,
            $email_solicitante,
            $telefono_solicitante,
            $motivacion,
            $preferencias,
            $direccion
        );
        wp_mail($admin_email, $asunto, $mensaje_email);

        wp_send_json_success([
            'mensaje' => __('Tu solicitud ha sido enviada correctamente. Te contactaremos pronto para confirmar tu incorporación al grupo.', 'flavor-platform'),
        ]);
    }

    /**
     * Crea la tabla de consumidores si no existe
     */
    private function crear_tabla_consumidores() {
        global $wpdb;
        $tabla = $wpdb->prefix . 'flavor_gc_consumidores';
        $charset_collate = $wpdb->get_charset_collate();

        $sql = "CREATE TABLE IF NOT EXISTS $tabla (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            usuario_id bigint(20) unsigned DEFAULT 0,
            grupo_id bigint(20) unsigned NOT NULL,
            nombre varchar(255) NOT NULL,
            email varchar(255) NOT NULL,
            telefono varchar(50) DEFAULT '',
            direccion text,
            preferencias text,
            notas text,
            estado varchar(20) DEFAULT 'pendiente',
            fecha_alta datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY usuario_id (usuario_id),
            KEY grupo_id (grupo_id),
            KEY estado (estado)
        ) $charset_collate;";

        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);
    }

    /**
     * Acción: Ver mi pedido
     */
    private function action_ver_mi_pedido($parametros) {
        if (!is_user_logged_in()) {
            return [
                'success' => false,
                'error' => __('Debes iniciar sesión.', 'flavor-platform'),
            ];
        }

        // Obtener ciclo actual
        $resultado_ciclo = $this->action_ciclo_actual([]);
        if (!$resultado_ciclo['success']) {
            return $resultado_ciclo;
        }

        $ciclo_id = $resultado_ciclo['ciclo']['id'];
        $usuario_id = get_current_user_id();

        global $wpdb;
        $tabla_pedidos = $wpdb->prefix . 'flavor_gc_pedidos';

        $items_pedido = $wpdb->get_results($wpdb->prepare(
            "SELECT id, ciclo_id, usuario_id, producto_id, cantidad, precio_unitario, subtotal, estado
             FROM $tabla_pedidos
             WHERE ciclo_id = %d AND usuario_id = %d",
            $ciclo_id,
            $usuario_id
        ));

        if (empty($items_pedido)) {
            return [
                'success' => false,
                'error' => __('No tienes pedidos en el ciclo actual.', 'flavor-platform'),
            ];
        }

        $pedido_formateado = [];
        $total = 0;

        foreach ($items_pedido as $item) {
            $producto = get_post($item->producto_id);
            $subtotal = $item->precio_unitario * $item->cantidad;
            $total += $subtotal;

            $pedido_formateado[] = [
                'producto' => $producto ? $producto->post_title : '',
                'cantidad' => floatval($item->cantidad),
                'precio_unitario' => floatval($item->precio_unitario),
                'subtotal' => $subtotal,
            ];
        }

        return [
            'success' => true,
            'pedido' => $pedido_formateado,
            'total' => $total,
        ];
    }

    /**
     * Acción: Hacer pedido
     */
    private function action_hacer_pedido($parametros) {
        if (!is_user_logged_in()) {
            return [
                'success' => false,
                'error' => __('Debes iniciar sesión.', 'flavor-platform'),
            ];
        }

        // Obtener ciclo actual
        $resultado_ciclo = $this->action_ciclo_actual([]);
        if (!$resultado_ciclo['success']) {
            return $resultado_ciclo;
        }

        $ciclo_id = $resultado_ciclo['ciclo']['id'];
        $usuario_id = get_current_user_id();
        $productos = $parametros['productos'] ?? [];

        if (empty($productos)) {
            return [
                'success' => false,
                'error' => __('No hay productos en el pedido.', 'flavor-platform'),
            ];
        }

        global $wpdb;
        $tabla_pedidos = $wpdb->prefix . 'flavor_gc_pedidos';

        // Verificar si ya tiene pedido en este ciclo
        $pedido_existente = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM $tabla_pedidos WHERE ciclo_id = %d AND usuario_id = %d",
            $ciclo_id,
            $usuario_id
        ));

        if ($pedido_existente > 0) {
            return [
                'success' => false,
                'error' => __('Ya tienes un pedido en este ciclo. Puedes modificarlo.', 'flavor-platform'),
            ];
        }

        // Insertar productos
        $total_importe = 0;
        foreach ($productos as $producto) {
            $producto_id = absint($producto['producto_id']);
            $cantidad = floatval($producto['cantidad']);

            $precio = get_post_meta($producto_id, '_gc_precio', true);

            $wpdb->insert(
                $tabla_pedidos,
                [
                    'ciclo_id' => $ciclo_id,
                    'usuario_id' => $usuario_id,
                    'producto_id' => $producto_id,
                    'cantidad' => $cantidad,
                    'precio_unitario' => $precio,
                    'estado' => 'pendiente',
                ],
                ['%d', '%d', '%d', '%f', '%f', '%s']
            );

            $total_importe += $precio * $cantidad;
        }

        return [
            'success' => true,
            'mensaje' => sprintf(
                __('Pedido realizado correctamente. Total: %.2f €', 'flavor-platform'),
                $total_importe
            ),
            'total' => $total_importe,
        ];
    }

    /**
     * Acción: Modificar pedido
     */
    private function action_modificar_pedido($parametros) {
        if (!is_user_logged_in()) {
            return [
                'success' => false,
                'error' => __('Debes iniciar sesión.', 'flavor-platform'),
            ];
        }

        // Obtener ciclo actual
        $resultado_ciclo = $this->action_ciclo_actual([]);
        if (!$resultado_ciclo['success']) {
            return $resultado_ciclo;
        }

        $ciclo_id = $resultado_ciclo['ciclo']['id'];
        $usuario_id = get_current_user_id();
        $productos = $parametros['productos'] ?? [];

        if (empty($productos)) {
            return [
                'success' => false,
                'error' => __('No hay productos para modificar.', 'flavor-platform'),
            ];
        }

        // Verificar si puede modificar (24 horas antes del cierre)
        $fecha_cierre = get_post_meta($ciclo_id, '_gc_fecha_cierre', true);
        $tiempo_restante = strtotime($fecha_cierre) - current_time('timestamp');
        $horas_restantes = $tiempo_restante / 3600;

        $limite_modificacion = $this->get_setting('horas_limite_modificacion', 24);

        if ($horas_restantes < $limite_modificacion && !$this->get_setting('permitir_modificar_pedido', true)) {
            return [
                'success' => false,
                'error' => sprintf(
                    __('No se puede modificar el pedido. Límite: %d horas antes del cierre.', 'flavor-platform'),
                    $limite_modificacion
                ),
            ];
        }

        global $wpdb;
        $tabla_pedidos = $wpdb->prefix . 'flavor_gc_pedidos';

        // Eliminar pedido anterior
        $wpdb->delete(
            $tabla_pedidos,
            [
                'ciclo_id' => $ciclo_id,
                'usuario_id' => $usuario_id,
            ],
            ['%d', '%d']
        );

        // Insertar nuevo pedido
        $total_importe = 0;
        foreach ($productos as $producto) {
            $producto_id = absint($producto['producto_id']);
            $cantidad = floatval($producto['cantidad']);

            $precio = get_post_meta($producto_id, '_gc_precio', true);

            $wpdb->insert(
                $tabla_pedidos,
                [
                    'ciclo_id' => $ciclo_id,
                    'usuario_id' => $usuario_id,
                    'producto_id' => $producto_id,
                    'cantidad' => $cantidad,
                    'precio_unitario' => $precio,
                    'estado' => 'pendiente',
                ],
                ['%d', '%d', '%d', '%f', '%f', '%s']
            );

            $total_importe += $precio * $cantidad;
        }

        return [
            'success' => true,
            'mensaje' => sprintf(
                __('Pedido modificado correctamente. Total: %.2f €', 'flavor-platform'),
                $total_importe
            ),
            'total' => $total_importe,
        ];
    }

    /**
     * Acción: Buscar productor
     */
    private function action_buscar_productor($parametros) {
        $args = [
            'post_type' => 'gc_productor',
            'post_status' => 'publish',
            'posts_per_page' => 20,
        ];

        if (!empty($parametros['busqueda'])) {
            $args['s'] = sanitize_text_field($parametros['busqueda']);
        }

        if (!empty($parametros['certificacion_eco'])) {
            $args['meta_query'] = [
                [
                    'key' => '_gc_certificacion_eco',
                    'value' => '1',
                ],
            ];
        }

        $query = new WP_Query($args);
        $productores = [];

        if ($query->have_posts()) {
            while ($query->have_posts()) {
                $query->the_post();
                $post_id = get_the_ID();

                $productores[] = [
                    'id' => $post_id,
                    'nombre' => get_the_title(),
                    'descripcion' => wp_trim_words(get_the_content(), 30),
                    'ubicacion' => get_post_meta($post_id, '_gc_ubicacion', true),
                    'certificacion_eco' => get_post_meta($post_id, '_gc_certificacion_eco', true) == '1',
                    'contacto' => get_post_meta($post_id, '_gc_contacto_email', true),
                ];
            }
            wp_reset_postdata();
        }

        return [
            'success' => true,
            'total' => count($productores),
            'productores' => $productores,
        ];
    }

    /**
     * Componentes web del módulo
     */
    public function get_web_components() {
        return [
            'hero' => [
                'label' => __('Hero Grupos de Consumo', 'flavor-platform'),
                'description' => __('Sección hero con buscador de grupos', 'flavor-platform'),
                'category' => 'hero',
                'icon' => 'dashicons-carrot',
                'fields' => [
                    'titulo' => [
                        'type' => 'text',
                        'label' => __('Título', 'flavor-platform'),
                        'default' => __('Grupos de Consumo', 'flavor-platform'),
                    ],
                    'subtitulo' => [
                        'type' => 'textarea',
                        'label' => __('Subtítulo', 'flavor-platform'),
                        'default' => __('Consume local, apoya a productores cercanos', 'flavor-platform'),
                    ],
                    'mostrar_buscador' => [
                        'type' => 'toggle',
                        'label' => __('Mostrar buscador', 'flavor-platform'),
                        'default' => true,
                    ],
                ],
                'template' => 'grupos-consumo/hero',
            ],
            'grupos_grid' => [
                'label' => __('Grid de Grupos', 'flavor-platform'),
                'description' => __('Listado de grupos de consumo activos', 'flavor-platform'),
                'category' => 'listings',
                'icon' => 'dashicons-groups',
                'fields' => [
                    'titulo' => [
                        'type' => 'text',
                        'label' => __('Título', 'flavor-platform'),
                        'default' => __('Grupos Activos', 'flavor-platform'),
                    ],
                    'columnas' => [
                        'type' => 'select',
                        'label' => __('Columnas', 'flavor-platform'),
                        'options' => [2, 3, 4],
                        'default' => 3,
                    ],
                    'limite' => [
                        'type' => 'number',
                        'label' => __('Número máximo', 'flavor-platform'),
                        'default' => 6,
                    ],
                ],
                'template' => 'grupos-consumo/grupos-grid',
            ],
            'productores' => [
                'label' => __('Productores Locales', 'flavor-platform'),
                'description' => __('Listado de productores asociados', 'flavor-platform'),
                'category' => 'listings',
                'icon' => 'dashicons-store',
                'fields' => [
                    'titulo' => [
                        'type' => 'text',
                        'label' => __('Título', 'flavor-platform'),
                        'default' => __('Nuestros Productores', 'flavor-platform'),
                    ],
                    'limite' => [
                        'type' => 'number',
                        'label' => __('Número máximo', 'flavor-platform'),
                        'default' => 8,
                    ],
                ],
                'template' => 'grupos-consumo/productores',
            ],
            'como_funciona' => [
                'label' => __('Cómo Funciona', 'flavor-platform'),
                'description' => __('Pasos para unirse a un grupo', 'flavor-platform'),
                'category' => 'features',
                'icon' => 'dashicons-info',
                'fields' => [
                    'titulo' => [
                        'type' => 'text',
                        'label' => __('Título', 'flavor-platform'),
                        'default' => __('¿Cómo funciona?', 'flavor-platform'),
                    ],
                ],
                'template' => 'grupos-consumo/como-funciona',
            ],
            'proximo_pedido' => [
                'label' => __('Próximo Pedido', 'flavor-platform'),
                'description' => __('Información del próximo pedido colectivo', 'flavor-platform'),
                'category' => 'cta',
                'icon' => 'dashicons-calendar-alt',
                'fields' => [
                    'titulo' => [
                        'type' => 'text',
                        'label' => __('Título', 'flavor-platform'),
                        'default' => __('Próximo Pedido', 'flavor-platform'),
                    ],
                ],
                'template' => 'grupos-consumo/proximo-pedido',
            ],
        ];
    }

    // ========================================
    // Nuevos Shortcodes
    // ========================================

    /**
     * Shortcode: Catálogo de productos con filtros
     */
    public function shortcode_catalogo($atributos) {
        $atributos = shortcode_atts([
            'categoria' => '',
            'productor' => '',
            'columnas' => 3,
            'limite' => 12,
            'mostrar_filtros' => 'true',
            'pagina' => 1,
        ], $atributos);

        $mostrar_filtros = filter_var($atributos['mostrar_filtros'], FILTER_VALIDATE_BOOLEAN);

        // Obtener categorías para filtros
        $categorias = get_terms([
            'taxonomy' => 'gc_categoria',
            'hide_empty' => true,
        ]);

        // Obtener productores para filtros
        $productores = get_posts([
            'post_type' => 'gc_productor',
            'posts_per_page' => -1,
            'orderby' => 'title',
            'order' => 'ASC',
        ]);

        // Obtener productos
        $resultado = $this->action_listar_productos([
            'categoria' => $atributos['categoria'],
            'productor_id' => $atributos['productor'],
            'limite' => $atributos['limite'],
            'pagina' => $atributos['pagina'],
        ]);

        ob_start();
        ?>
        <div class="gc-catalogo flavor-gc-catalogo" data-columnas="<?php echo esc_attr($atributos['columnas']); ?>">
            <?php if ($mostrar_filtros): ?>
            <div class="gc-catalogo-filtros">
                <select class="gc-filtro-categoria" data-filtro="categoria">
                    <option value=""><?php _e('Todas las categorías', 'flavor-platform'); ?></option>
                    <?php foreach ($categorias as $cat): ?>
                        <option value="<?php echo esc_attr($cat->slug); ?>" <?php selected($atributos['categoria'], $cat->slug); ?>>
                            <?php echo esc_html($cat->name); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
                <select class="gc-filtro-productor" data-filtro="productor">
                    <option value=""><?php _e('Todos los productores', 'flavor-platform'); ?></option>
                    <?php foreach ($productores as $prod): ?>
                        <option value="<?php echo esc_attr($prod->ID); ?>" <?php selected($atributos['productor'], $prod->ID); ?>>
                            <?php echo esc_html($prod->post_title); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <?php endif; ?>

            <div id="gc-productos-grid" class="gc-productos-grid gc-columnas-<?php echo esc_attr($atributos['columnas']); ?>">
                <?php if ($resultado['success'] && !empty($resultado['productos'])): ?>
                    <?php foreach ($resultado['productos'] as $producto): ?>
                        <div class="gc-producto-card" data-producto-id="<?php echo esc_attr($producto['id']); ?>">
                            <div class="gc-producto-imagen">
                                <?php if ($producto['imagen']): ?>
                                    <img src="<?php echo esc_url($producto['imagen']); ?>" alt="<?php echo esc_attr($producto['nombre']); ?>">
                                <?php else: ?>
                                    <span class="gc-placeholder dashicons dashicons-carrot"></span>
                                <?php endif; ?>
                            </div>
                            <div class="gc-producto-info">
                                <h4><?php echo esc_html($producto['nombre']); ?></h4>
                                <p class="gc-productor"><?php echo esc_html($producto['productor']); ?></p>
                                <p class="gc-descripcion"><?php echo esc_html($producto['descripcion']); ?></p>
                            </div>
                            <div class="gc-producto-footer">
                                <span class="gc-precio"><?php echo number_format($producto['precio'], 2); ?> € / <?php echo esc_html($producto['unidad']); ?></span>
                                <button type="button" class="gc-btn gc-btn-primary gc-agregar-lista gc-anadir-pedido" data-producto-id="<?php echo esc_attr($producto['id']); ?>">
                                    <span class="dashicons dashicons-plus"></span>
                                    <?php _e('Añadir', 'flavor-platform'); ?>
                                </button>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <p class="gc-sin-resultados"><?php _e('No se encontraron productos.', 'flavor-platform'); ?></p>
                <?php endif; ?>
            </div>

            <?php if (!empty($resultado['hay_mas'])): ?>
                <div class="flavor-gc-cargar-mas">
                    <button type="button" id="gc-btn-cargar-mas" class="gc-btn gc-btn-outline">
                        <span class="flavor-gc-btn-texto"><?php _e('Cargar más productos', 'flavor-platform'); ?></span>
                        <span class="flavor-gc-btn-loading" style="display:none;"><?php _e('Cargando...', 'flavor-platform'); ?></span>
                    </button>
                </div>
            <?php endif; ?>
        </div>
        <?php
        return ob_get_clean();
    }

    /**
     * Shortcode: Mini carrito/lista de compra
     */
    public function shortcode_carrito($atributos) {
        if (!is_user_logged_in()) {
            return '<p class="gc-aviso">' . __('Inicia sesión para ver tu lista de compra.', 'flavor-platform') . '</p>';
        }

        $dashboard_tab = Flavor_GC_Dashboard_Tab::get_instance();
        $items = $dashboard_tab->obtener_lista_compra(get_current_user_id());

        $total = 0;
        foreach ($items as $item) {
            $total += floatval($item->precio) * floatval($item->cantidad);
        }

        ob_start();
        ?>
        <div class="gc-mini-carrito">
            <div class="gc-carrito-header">
                <span class="dashicons dashicons-cart"></span>
                <span class="gc-carrito-titulo"><?php _e('Mi Lista', 'flavor-platform'); ?></span>
                <span class="gc-carrito-count"><?php echo count($items); ?></span>
            </div>
            <?php if (!empty($items)): ?>
                <div class="gc-carrito-items">
                    <?php foreach (array_slice($items, 0, 5) as $item): ?>
                        <div class="gc-carrito-item">
                            <span class="gc-item-nombre"><?php echo esc_html($item->producto_nombre); ?></span>
                            <span class="gc-item-cantidad"><?php echo esc_html($item->cantidad); ?></span>
                        </div>
                    <?php endforeach; ?>
                    <?php if (count($items) > 5): ?>
                        <p class="gc-carrito-mas"><?php printf(__('y %d más...', 'flavor-platform'), count($items) - 5); ?></p>
                    <?php endif; ?>
                </div>
                <div class="gc-carrito-footer">
                    <span class="gc-carrito-total"><?php printf(__('Total: %s €', 'flavor-platform'), number_format($total, 2)); ?></span>
                    <a href="<?php echo esc_url(home_url('/mi-portal/grupos-consumo/mi-pedido/')); ?>" class="gc-btn gc-btn-primary gc-ver-lista"><?php _e('Ver pedido', 'flavor-platform'); ?></a>
                </div>
            <?php else: ?>
                <p class="gc-carrito-vacio"><?php _e('Tu lista está vacía', 'flavor-platform'); ?></p>
            <?php endif; ?>
        </div>
        <?php
        return ob_get_clean();
    }

    /**
     * Shortcode: Calendario visual de ciclos
     */
    public function shortcode_calendario($atributos) {
        $atributos = shortcode_atts([
            'meses' => 3,
        ], $atributos);

        // Obtener ciclos de los próximos meses
        $fecha_inicio = date('Y-m-01');
        $fecha_fin = date('Y-m-t', strtotime('+' . intval($atributos['meses']) . ' months'));

        $ciclos = get_posts([
            'post_type' => 'gc_ciclo',
            'posts_per_page' => -1,
            'post_status' => ['gc_abierto', 'gc_cerrado', 'gc_entregado', 'publish'],
            'meta_query' => [
                [
                    'key' => '_gc_fecha_entrega',
                    'value' => [$fecha_inicio, $fecha_fin],
                    'compare' => 'BETWEEN',
                    'type' => 'DATE',
                ],
            ],
            'orderby' => 'meta_value',
            'meta_key' => '_gc_fecha_entrega',
            'order' => 'ASC',
        ]);

        ob_start();
        ?>
        <div class="gc-calendario">
            <div class="gc-calendario-header">
                <h3><?php _e('Calendario de Entregas', 'flavor-platform'); ?></h3>
            </div>
            <div class="gc-calendario-lista">
                <?php if (empty($ciclos)): ?>
                    <p class="gc-sin-ciclos"><?php _e('No hay ciclos programados próximamente.', 'flavor-platform'); ?></p>
                <?php else: ?>
                    <?php foreach ($ciclos as $ciclo):
                        $fecha_cierre = get_post_meta($ciclo->ID, '_gc_fecha_cierre', true);
                        $fecha_entrega = get_post_meta($ciclo->ID, '_gc_fecha_entrega', true);
                        $lugar_entrega = get_post_meta($ciclo->ID, '_gc_lugar_entrega', true);
                        $hora_entrega = get_post_meta($ciclo->ID, '_gc_hora_entrega', true);
                        $estado = get_post_status($ciclo->ID);
                    ?>
                        <div class="gc-calendario-item gc-estado-<?php echo esc_attr($estado); ?>">
                            <div class="gc-fecha-box">
                                <span class="gc-dia"><?php echo esc_html(date('d', strtotime($fecha_entrega))); ?></span>
                                <span class="gc-mes"><?php echo esc_html(date_i18n('M', strtotime($fecha_entrega))); ?></span>
                            </div>
                            <div class="gc-ciclo-info">
                                <h4><?php echo esc_html($ciclo->post_title); ?></h4>
                                <p class="gc-lugar">
                                    <span class="dashicons dashicons-location"></span>
                                    <?php echo esc_html($lugar_entrega); ?>
                                    <?php if ($hora_entrega): ?>
                                        - <?php echo esc_html($hora_entrega); ?>
                                    <?php endif; ?>
                                </p>
                                <?php if ($estado === 'gc_abierto'): ?>
                                    <p class="gc-cierre">
                                        <?php printf(__('Pedidos hasta: %s', 'flavor-platform'), date_i18n(get_option('date_format') . ' H:i', strtotime($fecha_cierre))); ?>
                                    </p>
                                <?php endif; ?>
                            </div>
                            <div class="gc-estado-badge gc-estado-<?php echo esc_attr($estado); ?>">
                                <?php
                                $estados_texto = [
                                    'gc_abierto' => __('Abierto', 'flavor-platform'),
                                    'gc_cerrado' => __('Cerrado', 'flavor-platform'),
                                    'gc_entregado' => __('Entregado', 'flavor-platform'),
                                ];
                                echo esc_html($estados_texto[$estado] ?? $estado);
                                ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>
        <?php
        return ob_get_clean();
    }

    /**
     * Shortcode: Historial de pedidos del usuario
     */
    public function shortcode_historial($atributos) {
        if (!is_user_logged_in()) {
            return '<p class="gc-aviso">' . __('Inicia sesión para ver tu historial.', 'flavor-platform') . '</p>';
        }

        $atributos = shortcode_atts([
            'limite' => 10,
        ], $atributos);

        global $wpdb;
        $tabla_pedidos = $wpdb->prefix . 'flavor_gc_pedidos';
        $usuario_id = get_current_user_id();
        $pagina = max(1, absint($_GET['gc_hist'] ?? 1));
        $limite = max(1, absint($atributos['limite']));
        $offset = ($pagina - 1) * $limite;

        // Obtener pedidos agrupados por ciclo
        $pedidos = $wpdb->get_results($wpdb->prepare(
            "SELECT ciclo_id, SUM(cantidad * precio_unitario) as total, COUNT(*) as num_items, MIN(fecha_pedido) as fecha
            FROM $tabla_pedidos
            WHERE usuario_id = %d
            GROUP BY ciclo_id
            ORDER BY fecha DESC
            LIMIT %d OFFSET %d",
            $usuario_id,
            $limite,
            $offset
        ));

        $total_ciclos = (int) $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(DISTINCT ciclo_id) FROM $tabla_pedidos WHERE usuario_id = %d",
            $usuario_id
        ));
        $total_paginas = $limite > 0 ? (int) ceil($total_ciclos / $limite) : 1;

        ob_start();
        ?>
        <div class="gc-historial">
            <h3><?php _e('Mi Historial', 'flavor-platform'); ?></h3>
            <?php if (empty($pedidos)): ?>
                <p class="gc-sin-historial"><?php _e('No tienes pedidos anteriores.', 'flavor-platform'); ?></p>
            <?php else: ?>
                <div class="gc-historial-lista">
                    <?php foreach ($pedidos as $pedido):
                        $ciclo = get_post($pedido->ciclo_id);
                        $fecha_entrega = get_post_meta($pedido->ciclo_id, '_gc_fecha_entrega', true);
                        $estado = get_post_status($pedido->ciclo_id);
                    ?>
                        <div class="gc-historial-item">
                            <div class="gc-historial-fecha">
                                <?php echo esc_html(date_i18n(get_option('date_format'), strtotime($fecha_entrega))); ?>
                            </div>
                            <div class="gc-historial-info">
                                <strong><?php echo $ciclo ? esc_html($ciclo->post_title) : __('Ciclo eliminado', 'flavor-platform'); ?></strong>
                                <span><?php printf(_n('%d producto', '%d productos', $pedido->num_items, 'flavor-platform'), $pedido->num_items); ?></span>
                            </div>
                            <div class="gc-historial-total">
                                <?php echo number_format($pedido->total, 2); ?> €
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
                <?php if ($total_paginas > 1): ?>
                    <div class="gc-paginacion">
                        <?php
                        echo paginate_links([
                            'base' => esc_url(add_query_arg('gc_hist', '%#%')),
                            'format' => '',
                            'current' => $pagina,
                            'total' => $total_paginas,
                            'prev_text' => __('Anterior', 'flavor-platform'),
                            'next_text' => __('Siguiente', 'flavor-platform'),
                        ]);
                        ?>
                    </div>
                <?php endif; ?>
            <?php endif; ?>
        </div>
        <?php
        return ob_get_clean();
    }

    /**
     * Shortcode: Cestas disponibles para suscribirse
     */
    public function shortcode_suscripciones($atributos) {
        $atributos = shortcode_atts([
            'columnas' => 3,
        ], $atributos);

        $suscripciones_manager = Flavor_GC_Subscriptions::get_instance();
        $cestas = $suscripciones_manager->listar_tipos_cestas();

        ob_start();
        ?>
        <div class="gc-cestas-disponibles">
            <h3><?php _e('Nuestras Cestas', 'flavor-platform'); ?></h3>
            <p class="gc-cestas-intro"><?php _e('Suscríbete a una cesta y recibe productos frescos de forma regular.', 'flavor-platform'); ?></p>
            <div class="gc-cestas-grid gc-columnas-<?php echo esc_attr($atributos['columnas']); ?>">
                <?php foreach ($cestas as $cesta): ?>
                    <div class="gc-cesta-card">
                        <div class="gc-cesta-imagen">
                            <?php if ($cesta->imagen_id): ?>
                                <?php echo wp_get_attachment_image($cesta->imagen_id, 'medium'); ?>
                            <?php else: ?>
                                <span class="gc-placeholder dashicons dashicons-carrot"></span>
                            <?php endif; ?>
                        </div>
                        <div class="gc-cesta-info">
                            <h4><?php echo esc_html($cesta->nombre); ?></h4>
                            <p class="gc-cesta-descripcion"><?php echo esc_html($cesta->descripcion); ?></p>
                            <p class="gc-cesta-precio">
                                <?php if ($cesta->precio_base > 0): ?>
                                    <strong><?php echo number_format($cesta->precio_base, 2); ?> €</strong>
                                    <span><?php _e('/ entrega', 'flavor-platform'); ?></span>
                                <?php else: ?>
                                    <?php _e('Precio según contenido', 'flavor-platform'); ?>
                                <?php endif; ?>
                            </p>
                        </div>
                        <?php if (is_user_logged_in()): ?>
                            <button type="button" class="gc-btn gc-btn-primary gc-suscribirse-cesta" data-cesta-id="<?php echo esc_attr($cesta->id); ?>">
                                <?php _e('Suscribirse', 'flavor-platform'); ?>
                            </button>
                        <?php else: ?>
                            <a href="<?php echo esc_url(wp_login_url(home_url('/mi-portal/grupos-consumo/suscripciones/'))); ?>" class="gc-btn gc-btn-outline">
                                <?php _e('Inicia sesión para suscribirte', 'flavor-platform'); ?>
                            </a>
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
        <?php
        return ob_get_clean();
    }

    /**
     * Shortcode: Mi suscripción activa
     */
    public function shortcode_mi_cesta($atributos) {
        if (!is_user_logged_in()) {
            return '<p class="gc-aviso">' . __('Inicia sesión para ver tu cesta.', 'flavor-platform') . '</p>';
        }

        $usuario_id = get_current_user_id();

        // Buscar consumidor
        $consumidor_manager = Flavor_GC_Consumidor_Manager::get_instance();
        $grupos = get_posts([
            'post_type' => 'gc_grupo',
            'posts_per_page' => 1,
            'post_status' => 'publish',
        ]);

        if (empty($grupos)) {
            return '<p class="gc-aviso">' . __('No hay grupos de consumo disponibles.', 'flavor-platform') . '</p>';
        }

        $consumidor = $consumidor_manager->obtener_consumidor($usuario_id, $grupos[0]->ID);

        if (!$consumidor) {
            return '<div class="gc-aviso gc-aviso-info"><p>' . __('No eres miembro de ningún grupo de consumo todavía.', 'flavor-platform') . '</p></div>';
        }

        $suscripciones_manager = Flavor_GC_Subscriptions::get_instance();
        $suscripciones = $suscripciones_manager->listar_suscripciones_consumidor($consumidor->id, ['estado' => 'activa']);

        ob_start();
        ?>
        <div class="gc-mi-cesta">
            <?php if (empty($suscripciones)): ?>
                <div class="gc-sin-suscripcion">
                    <span class="dashicons dashicons-heart"></span>
                    <p><?php _e('No tienes ninguna suscripción activa.', 'flavor-platform'); ?></p>
                    <a href="<?php echo esc_url(home_url('/mi-portal/grupos-consumo/suscripciones/')); ?>" class="gc-btn gc-btn-primary"><?php _e('Ver Cestas Disponibles', 'flavor-platform'); ?></a>
                </div>
            <?php else: ?>
                <?php foreach ($suscripciones as $suscripcion): ?>
                    <div class="gc-suscripcion-activa">
                        <div class="gc-suscripcion-header">
                            <h4><?php echo esc_html($suscripcion->cesta_nombre); ?></h4>
                            <span class="gc-estado-activa"><?php _e('Activa', 'flavor-platform'); ?></span>
                        </div>
                        <div class="gc-suscripcion-detalles">
                            <p><strong><?php _e('Frecuencia:', 'flavor-platform'); ?></strong> <?php echo esc_html($suscripciones_manager->obtener_etiqueta_frecuencia($suscripcion->frecuencia)); ?></p>
                            <p><strong><?php _e('Importe:', 'flavor-platform'); ?></strong> <?php echo number_format($suscripcion->importe, 2); ?> €</p>
                            <p><strong><?php _e('Próxima entrega:', 'flavor-platform'); ?></strong> <?php echo esc_html(date_i18n(get_option('date_format'), strtotime($suscripcion->fecha_proximo_cargo))); ?></p>
                        </div>
                        <div class="gc-suscripcion-acciones">
                            <button type="button" class="gc-btn gc-btn-outline gc-pausar-suscripcion" data-suscripcion-id="<?php echo esc_attr($suscripcion->id); ?>">
                                <?php _e('Pausar', 'flavor-platform'); ?>
                            </button>
                            <button type="button" class="gc-btn gc-btn-danger gc-cancelar-suscripcion" data-suscripcion-id="<?php echo esc_attr($suscripcion->id); ?>">
                                <?php _e('Cancelar', 'flavor-platform'); ?>
                            </button>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
        <?php
        return ob_get_clean();
    }

    /**
     * Registra las paginas de administracion del modulo
     *
     * Utiliza capabilities granulares del sistema de permisos.
     * NOTA: Todas las páginas son OCULTAS (no aparecen en el sidebar de WordPress)
     * pero siguen siendo accesibles via URL directa: admin.php?page=slug
     */
    public function registrar_paginas_admin() {
        static $registered = false;
        if ($registered) {
            return;
        }
        $registered = true;


        // Capability base para acceder al menú (administradores)
        $capability_base = 'manage_options';

        // Pagina principal de Grupos de Consumo (OCULTA - sin menu visible)
        add_submenu_page(
            null, // null = página oculta, no aparece en sidebar
            __('Grupos de Consumo', 'flavor-platform'),
            __('Dashboard', 'flavor-platform'),
            $capability_base,
            'grupos-consumo',
            [$this, 'render_pagina_dashboard']
        );

        // Pagina: Consumidores (OCULTA)
        add_submenu_page(
            null,
            __('Consumidores', 'flavor-platform'),
            __('Consumidores', 'flavor-platform'),
            $capability_base,
            'gc-consumidores',
            [$this, 'render_pagina_consumidores']
        );

        // Pagina: Solicitudes (OCULTA)
        add_submenu_page(
            null,
            __('Solicitudes de Unión', 'flavor-platform'),
            __('Solicitudes', 'flavor-platform'),
            $capability_base,
            'gc-solicitudes',
            [$this, 'render_pagina_solicitudes']
        );

        // Pagina: Suscripciones (OCULTA)
        add_submenu_page(
            null,
            __('Suscripciones', 'flavor-platform'),
            __('Suscripciones', 'flavor-platform'),
            $capability_base,
            'gc-suscripciones',
            [$this, 'render_pagina_suscripciones']
        );

        // Pagina: Pedidos (OCULTA)
        add_submenu_page(
            null,
            __('Pedidos', 'flavor-platform'),
            __('Pedidos', 'flavor-platform'),
            $capability_base,
            'gc-pedidos',
            [$this, 'render_pagina_pedidos']
        );

        // Pagina: Consolidado (OCULTA)
        add_submenu_page(
            null,
            __('Consolidado', 'flavor-platform'),
            __('Consolidado', 'flavor-platform'),
            $capability_base,
            'gc-consolidado',
            [$this, 'render_pagina_consolidado']
        );

        // Pagina: Reportes (OCULTA)
        add_submenu_page(
            null,
            __('Reportes', 'flavor-platform'),
            __('Reportes', 'flavor-platform'),
            $capability_base,
            'gc-reportes',
            [$this, 'render_pagina_reportes']
        );

        // Pagina: Configuración (OCULTA)
        add_submenu_page(
            null,
            __('Configuración', 'flavor-platform'),
            __('Configuración', 'flavor-platform'),
            $capability_base,
            'gc-configuracion',
            [$this, 'render_pagina_configuracion']
        );
    }

    /**
     * Renderiza página dashboard
     */
    public function render_pagina_dashboard() {
        if (class_exists('Flavor_Module_Dashboards_Registrar')) {
            $registrar = Flavor_Module_Dashboards_Registrar::get_instance();
            if (is_object($registrar) && method_exists($registrar, 'render_relationship_panel_for_module')) {
                $registrar->render_relationship_panel_for_module('grupos_consumo');
            }
        }

        $views_path = dirname(__FILE__) . '/views/dashboard.php';
        if (file_exists($views_path)) {
            include $views_path;
        } else {
            echo '<div class="wrap"><h1>' . __('Dashboard Grupos de Consumo', 'flavor-platform') . '</h1></div>';
        }
    }

    /**
     * Renderiza página de consumidores
     */
    public function render_pagina_consumidores() {
        include dirname(__FILE__) . '/views/consumidores.php';
    }

    /**
     * Renderiza página de solicitudes
     */
    public function render_pagina_solicitudes() {
        include dirname(__FILE__) . '/views/solicitudes.php';
    }

    /**
     * Renderiza página de suscripciones
     */
    public function render_pagina_suscripciones() {
        include dirname(__FILE__) . '/views/suscripciones.php';
    }

    /**
     * Renderiza página de pedidos
     */
    public function render_pagina_pedidos() {
        include dirname(__FILE__) . '/views/pedidos.php';
    }

    /**
     * Renderiza página de consolidado
     */
    public function render_pagina_consolidado() {
        include dirname(__FILE__) . '/views/consolidado.php';
    }

    /**
     * Renderiza página de reportes
     */
    public function render_pagina_reportes() {
        include dirname(__FILE__) . '/views/reportes.php';
    }

    /**
     * Renderiza página de configuración
     */
    public function render_pagina_configuracion() {
        include dirname(__FILE__) . '/views/settings.php';
    }

    /**
     * Renderiza página de configuración de pagos
     *
     * @since 4.1.0
     */
    public function render_pagina_pagos() {
        include dirname(__FILE__) . '/views/admin-payment-settings.php';
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
            'gc_ciclo_actual',
            'gc_productos',
            'gc_mi_pedido',
            'gc_catalogo',
            'gc_carrito',
            'gc_calendario',
            'gc_historial',
            'gc_suscripciones',
            'gc_mi_cesta',
            'gc_grupos_lista',
            'gc_productores_cercanos',
            'gc_panel',
            'gc_nav',
            'gc_mis_pedidos',
            'gc_productores',
            'gc_ciclos',
        ];

        foreach ($shortcodes_modulo as $shortcode) {
            if (has_shortcode($post->post_content, $shortcode)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Encola assets del módulo
     */
    public function enqueue_assets() {
        if (!is_admin() && $this->should_load_assets()) {
            $plugin_url = plugins_url('assets/', __FILE__);
            $version = defined('FLAVOR_VERSION') ? FLAVOR_VERSION : '1.0.0';

            // CSS Frontend
            wp_enqueue_style(
                'gc-frontend',
                $plugin_url . 'gc-frontend.css',
                [],
                $version
            );

            // JS Frontend
            wp_enqueue_script(
                'gc-frontend',
                $plugin_url . 'gc-frontend.js',
                ['jquery'],
                $version,
                true
            );

            $scripts = wp_scripts();
            $gc_frontend_data = $scripts ? (string) $scripts->get_data('gc-frontend', 'data') : '';

            if (strpos($gc_frontend_data, 'var gcFrontend =') === false) {
                wp_localize_script('gc-frontend', 'gcFrontend', [
                    'ajaxUrl' => admin_url('admin-ajax.php'),
                    'restUrl' => rest_url('flavor-chat-ia/v1/gc/'),
                    'nonce' => wp_create_nonce('gc_nonce'),
                    'restNonce' => wp_create_nonce('wp_rest'),
                    'isLoggedIn' => is_user_logged_in(),
                    'i18n' => [
                        'agregado' => __('Producto agregado a la lista', 'flavor-platform'),
                        'eliminado' => __('Producto eliminado de la lista', 'flavor-platform'),
                        'error' => __('Ha ocurrido un error', 'flavor-platform'),
                        'confirmarEliminar' => __('¿Eliminar este producto de la lista?', 'flavor-platform'),
                        'cargando' => __('Cargando...', 'flavor-platform'),
                        'sinProductos' => __('No hay productos disponibles', 'flavor-platform'),
                        'pedidoCreado' => __('Pedido creado correctamente', 'flavor-platform'),
                    ],
                ]);
            }
        }

        // Admin assets
        if (is_admin()) {
            $screen = get_current_screen();
            if ($screen && (strpos($screen->id, 'gc-') !== false || strpos($screen->id, 'grupos-consumo') !== false)) {
                $plugin_url = plugins_url('assets/', __FILE__);
                $version = defined('FLAVOR_VERSION') ? FLAVOR_VERSION : '1.0.0';

                wp_enqueue_style(
                    'gc-admin',
                    $plugin_url . 'gc-admin.css',
                    [],
                    $version
                );

                wp_enqueue_script(
                    'gc-admin',
                    $plugin_url . 'gc-admin.js',
                    ['jquery'],
                    $version,
                    true
                );

                wp_localize_script('gc-admin', 'gcAdmin', [
                    'nonce' => wp_create_nonce('gc_admin_nonce'),
                    'ajaxUrl' => admin_url('admin-ajax.php'),
                ]);
            }
        }
    }

    /**
     * Shortcode: Lista de grupos de consumo disponibles
     *
     * @param array $atributos Atributos del shortcode
     * @return string HTML del listado de grupos
     */
    public function shortcode_grupos_lista($atributos) {
        $atributos = shortcode_atts([
            'columnas' => 3,
            'limite' => 12,
            'mostrar_cerrados' => 'false',
            'comunidad_id' => 0,
        ], $atributos);

        $mostrar_cerrados = filter_var($atributos['mostrar_cerrados'], FILTER_VALIDATE_BOOLEAN);
        $comunidad_id = absint($atributos['comunidad_id']);
        $pagina = max(1, absint($_GET['gc_p'] ?? 1));

        // Consultar grupos de consumo
        $args_query = [
            'post_type' => 'gc_grupo',
            'post_status' => 'publish',
            'posts_per_page' => intval($atributos['limite']),
            'orderby' => 'title',
            'order' => 'ASC',
            'paged' => $pagina,
        ];

        // Filtrar solo grupos que aceptan nuevos miembros
        if (!$mostrar_cerrados) {
            $args_query['meta_query'] = [
                'relation' => 'OR',
                [
                    'key' => '_gc_acepta_miembros',
                    'value' => '1',
                    'compare' => '=',
                ],
                [
                    'key' => '_gc_acepta_miembros',
                    'compare' => 'NOT EXISTS',
                ],
            ];
        }

        if ($comunidad_id > 0) {
            $existing_meta_query = $args_query['meta_query'] ?? [];
            $meta_query = ['relation' => 'AND'];

            if (!empty($existing_meta_query)) {
                $meta_query[] = $existing_meta_query;
            }

            $meta_query[] = [
                'key' => '_flavor_comunidad_id',
                'value' => $comunidad_id,
                'compare' => '=',
                'type' => 'NUMERIC',
            ];
            $args_query['meta_query'] = $meta_query;
        }

        $grupos = new WP_Query($args_query);

        ob_start();
        ?>
        <div class="gc-grupos-lista">
            <div class="gc-grupos-grid gc-columnas-<?php echo esc_attr($atributos['columnas']); ?>">
                <?php if ($grupos->have_posts()): ?>
                    <?php while ($grupos->have_posts()): $grupos->the_post();
                        $grupo_id = get_the_ID();
                        $imagen_url = get_the_post_thumbnail_url($grupo_id, 'medium');
                        $descripcion_corta = wp_trim_words(get_the_excerpt(), 20, '...');
                        $numero_miembros = $this->contar_miembros_grupo($grupo_id);
                        $ubicacion = get_post_meta($grupo_id, '_gc_ubicacion', true);
                        $acepta_miembros = get_post_meta($grupo_id, '_gc_acepta_miembros', true);
                        $acepta = empty($acepta_miembros) || $acepta_miembros === '1';
                    ?>
                        <div class="gc-grupo-card">
                            <div class="gc-grupo-imagen">
                                <?php if ($imagen_url): ?>
                                    <img src="<?php echo esc_url($imagen_url); ?>" alt="<?php echo esc_attr(get_the_title()); ?>">
                                <?php else: ?>
                                    <div class="gc-grupo-imagen-placeholder">
                                        <span class="dashicons dashicons-groups"></span>
                                    </div>
                                <?php endif; ?>
                                <?php if ($acepta): ?>
                                    <span class="gc-badge gc-badge-success"><?php _e('Abierto', 'flavor-platform'); ?></span>
                                <?php else: ?>
                                    <span class="gc-badge gc-badge-warning"><?php _e('Completo', 'flavor-platform'); ?></span>
                                <?php endif; ?>
                            </div>
                            <div class="gc-grupo-contenido">
                                <h3 class="gc-grupo-titulo"><?php the_title(); ?></h3>
                                <?php if ($ubicacion): ?>
                                    <p class="gc-grupo-ubicacion">
                                        <span class="dashicons dashicons-location"></span>
                                        <?php echo esc_html($ubicacion); ?>
                                    </p>
                                <?php endif; ?>
                                <p class="gc-grupo-descripcion"><?php echo esc_html($descripcion_corta); ?></p>
                                <div class="gc-grupo-meta">
                                    <span class="gc-grupo-miembros">
                                        <span class="dashicons dashicons-admin-users"></span>
                                        <?php printf(_n('%d miembro', '%d miembros', $numero_miembros, 'flavor-platform'), $numero_miembros); ?>
                                    </span>
                                </div>
                            </div>
                            <div class="gc-grupo-footer">
                                <a href="<?php the_permalink(); ?>" class="gc-btn gc-btn-outline gc-btn-sm">
                                    <?php _e('Ver grupo', 'flavor-platform'); ?>
                                </a>
                                <?php if ($acepta): ?>
                                    <a href="<?php echo esc_url(home_url('/mi-portal/grupos-consumo/unirme/?grupo=' . $grupo_id)); ?>" class="gc-btn gc-btn-primary gc-btn-sm">
                                        <?php _e('Unirme', 'flavor-platform'); ?>
                                    </a>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endwhile; ?>
                    <?php wp_reset_postdata(); ?>
                <?php else: ?>
                    <div class="gc-sin-grupos">
                        <span class="dashicons dashicons-groups"></span>
                        <p><?php _e('No hay grupos de consumo disponibles en este momento.', 'flavor-platform'); ?></p>
                    </div>
                <?php endif; ?>
            </div>
            <?php if ($grupos->max_num_pages > 1): ?>
                <div class="gc-paginacion">
                    <?php
                    echo paginate_links([
                        'base' => esc_url(add_query_arg('gc_p', '%#%')),
                        'format' => '',
                        'current' => $pagina,
                        'total' => $grupos->max_num_pages,
                        'prev_text' => __('Anterior', 'flavor-platform'),
                        'next_text' => __('Siguiente', 'flavor-platform'),
                    ]);
                    ?>
                </div>
            <?php endif; ?>
        </div>
        <?php
        return ob_get_clean();
    }

    /**
     * Cuenta el número de miembros activos en un grupo
     *
     * @param int $grupo_id ID del grupo
     * @return int Número de miembros
     */
    private function contar_miembros_grupo($grupo_id) {
        global $wpdb;
        $tabla_consumidores = $wpdb->prefix . 'flavor_gc_consumidores';

        // Verificar si la tabla existe
        if (!Flavor_Chat_Helpers::tabla_existe($tabla_consumidores)) {
            return 0;
        }

        $count = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM $tabla_consumidores WHERE grupo_id = %d AND estado = 'activo'",
            $grupo_id
        ));

        return intval($count);
    }

    /**
     * Obtiene el grupo principal asociado a una comunidad.
     *
     * @param int $comunidad_id ID de la comunidad.
     * @return WP_Post|null
     */
    public function obtener_grupo_principal_comunidad($comunidad_id) {
        $comunidad_id = absint($comunidad_id);
        if ($comunidad_id <= 0) {
            return null;
        }

        $grupos = get_posts([
            'post_type' => 'gc_grupo',
            'post_status' => 'publish',
            'posts_per_page' => 1,
            'orderby' => 'date',
            'order' => 'ASC',
            'meta_query' => [
                [
                    'key' => '_flavor_comunidad_id',
                    'value' => $comunidad_id,
                    'compare' => '=',
                    'type' => 'NUMERIC',
                ],
            ],
        ]);

        return !empty($grupos) ? $grupos[0] : null;
    }

    /**
     * Provisiona un grupo principal para una comunidad recién creada.
     *
     * @param int   $comunidad_id ID de la comunidad.
     * @param array $datos        Datos de la comunidad.
     * @return int ID del grupo creado o existente.
     */
    public function provisionar_grupo_principal_comunidad($comunidad_id, $datos = []) {
        $comunidad_id = absint($comunidad_id);
        if ($comunidad_id <= 0) {
            return 0;
        }

        $grupo_existente = $this->obtener_grupo_principal_comunidad($comunidad_id);
        if ($grupo_existente instanceof WP_Post) {
            return (int) $grupo_existente->ID;
        }

        $nombre = sanitize_text_field($datos['nombre'] ?? '');
        if ($nombre === '') {
            $nombre = sprintf(__('Grupo de consumo %d', 'flavor-platform'), $comunidad_id);
        }

        $descripcion = wp_kses_post($datos['descripcion'] ?? '');
        $ubicacion = sanitize_text_field($datos['ubicacion'] ?? '');

        $grupo_id = wp_insert_post([
            'post_type' => 'gc_grupo',
            'post_status' => 'publish',
            'post_title' => sprintf(__('Grupo de consumo de %s', 'flavor-platform'), $nombre),
            'post_content' => $descripcion,
            'post_excerpt' => $descripcion ? wp_trim_words(wp_strip_all_tags($descripcion), 26, '...') : '',
        ], true);

        if (is_wp_error($grupo_id) || !$grupo_id) {
            return 0;
        }

        update_post_meta($grupo_id, '_flavor_comunidad_id', $comunidad_id);
        update_post_meta($grupo_id, '_gc_grupo_principal_comunidad', 1);

        if ($ubicacion !== '') {
            update_post_meta($grupo_id, '_gc_ubicacion', $ubicacion);
        }

        return (int) $grupo_id;
    }

    /**
     * Obtiene los grupos contextuales para tabs satélite.
     *
     * @return int[]
     */
    private function get_contextual_grupo_ids_for_satellite_tabs() {
        global $wpdb;

        $direct_id = absint($_GET['grupo'] ?? $_GET['grupo_id'] ?? $_GET['id'] ?? 0);
        if ($direct_id > 0) {
            return [$direct_id];
        }

        if (!is_user_logged_in()) {
            return [];
        }

        $tabla_consumidores = $wpdb->prefix . 'flavor_gc_consumidores';
        if (!Flavor_Chat_Helpers::tabla_existe($tabla_consumidores)) {
            return [];
        }

        return array_map('intval', (array) $wpdb->get_col($wpdb->prepare(
            "SELECT grupo_id
             FROM {$tabla_consumidores}
             WHERE usuario_id = %d AND estado = 'activo'
             ORDER BY fecha_alta DESC
             LIMIT 12",
            get_current_user_id()
        )));
    }

    /**
     * Obtiene el grupo contextual principal para tabs satélite.
     *
     * @return WP_Post|null
     */
    private function get_contextual_grupo_for_satellite_tabs() {
        $grupo_ids = $this->get_contextual_grupo_ids_for_satellite_tabs();
        if (empty($grupo_ids)) {
            return null;
        }

        $grupo = get_post((int) $grupo_ids[0]);
        return ($grupo && $grupo->post_type === 'gc_grupo') ? $grupo : null;
    }

    /**
     * Renderiza el tab contextual de foro.
     *
     * @return string
     */
    public function render_tab_foro() {
        $grupo = $this->get_contextual_grupo_for_satellite_tabs();
        if (!$grupo) {
            return '<div class="flavor-empty-state bg-gray-50 rounded-xl p-8 text-center">
                <span class="text-5xl mb-4 block">💬</span>
                <h3 class="text-lg font-semibold text-gray-900 mb-2">' . esc_html__('Foro del grupo', 'flavor-platform') . '</h3>
                <p class="text-gray-500 mb-4">' . esc_html__('Accede a un grupo concreto para ver su foro asociado.', 'flavor-platform') . '</p>
            </div>';
        }

        $header = '<div class="flavor-integrated-tab-header bg-white rounded-xl p-4 mb-4 border border-gray-100">';
        $header .= '<h3 class="text-lg font-semibold text-gray-900 mb-1">' . esc_html__('Foro del grupo', 'flavor-platform') . '</h3>';
        $header .= '<p class="text-sm text-gray-500">' . esc_html(get_the_title($grupo)) . '</p>';
        $header .= '</div>';

        return $header . do_shortcode('[flavor_foros_integrado entidad="grupo_consumo" entidad_id="' . (int) $grupo->ID . '"]');
    }

    /**
     * Renderiza el tab contextual de chat.
     *
     * @return string
     */
    public function render_tab_chat() {
        if (!is_user_logged_in()) {
            return '<div class="flavor-empty-state bg-gray-50 rounded-xl p-8 text-center">
                <span class="text-5xl mb-4 block">💬</span>
                <h3 class="text-lg font-semibold text-gray-900 mb-2">' . esc_html__('Chat del grupo', 'flavor-platform') . '</h3>
                <p class="text-gray-500 mb-4">' . esc_html__('Inicia sesión para acceder al chat del grupo.', 'flavor-platform') . '</p>
            </div>';
        }

        $grupo = $this->get_contextual_grupo_for_satellite_tabs();
        if (!$grupo) {
            return '<div class="flavor-empty-state bg-gray-50 rounded-xl p-8 text-center">
                <span class="text-5xl mb-4 block">💬</span>
                <h3 class="text-lg font-semibold text-gray-900 mb-2">' . esc_html__('Chat del grupo', 'flavor-platform') . '</h3>
                <p class="text-gray-500 mb-4">' . esc_html__('Accede a un grupo concreto para abrir su chat asociado.', 'flavor-platform') . '</p>
            </div>';
        }

        $header = '<div class="flavor-integrated-tab-header bg-white rounded-xl p-4 mb-4 border border-gray-100 flex items-center justify-between gap-4 flex-wrap">';
        $header .= '<div>';
        $header .= '<h3 class="text-lg font-semibold text-gray-900 mb-1">' . esc_html__('Chat del grupo', 'flavor-platform') . '</h3>';
        $header .= '<p class="text-sm text-gray-500">' . esc_html(get_the_title($grupo)) . '</p>';
        $header .= '</div>';
        $header .= '<a class="inline-flex items-center gap-2 px-4 py-2 rounded-lg bg-emerald-600 text-white text-sm font-medium hover:opacity-90" href="' . esc_url(add_query_arg(['grupo_id' => (int) $grupo->ID], home_url('/mi-portal/chat-grupos/mensajes/'))) . '">';
        $header .= '<span class="dashicons dashicons-external" style="font-size:16px;width:16px;height:16px;"></span>';
        $header .= esc_html__('Abrir chat completo', 'flavor-platform');
        $header .= '</a>';
        $header .= '</div>';

        return $header . do_shortcode('[flavor_chat_grupo_integrado entidad="grupo_consumo" entidad_id="' . (int) $grupo->ID . '"]');
    }

    /**
     * Renderiza el tab contextual de multimedia.
     *
     * @return string
     */
    public function render_tab_multimedia() {
        $grupo = $this->get_contextual_grupo_for_satellite_tabs();
        if (!$grupo) {
            return '<div class="flavor-empty-state bg-gray-50 rounded-xl p-8 text-center">
                <span class="text-5xl mb-4 block">🖼️</span>
                <h3 class="text-lg font-semibold text-gray-900 mb-2">' . esc_html__('Galería del grupo', 'flavor-platform') . '</h3>
                <p class="text-gray-500 mb-4">' . esc_html__('Accede a un grupo concreto para ver su galería multimedia.', 'flavor-platform') . '</p>
            </div>';
        }

        $header = '<div class="flavor-integrated-tab-header bg-white rounded-xl p-4 mb-4 border border-gray-100 flex items-center justify-between gap-4 flex-wrap">';
        $header .= '<div>';
        $header .= '<h3 class="text-lg font-semibold text-gray-900 mb-1">' . esc_html__('Galería del grupo', 'flavor-platform') . '</h3>';
        $header .= '<p class="text-sm text-gray-500">' . esc_html(get_the_title($grupo)) . '</p>';
        $header .= '</div>';
        if (is_user_logged_in()) {
            $header .= '<a class="inline-flex items-center gap-2 px-4 py-2 rounded-lg bg-emerald-600 text-white text-sm font-medium hover:opacity-90" href="' . esc_url(add_query_arg(['grupo_id' => (int) $grupo->ID], home_url('/mi-portal/multimedia/subir/'))) . '">';
            $header .= '<span class="dashicons dashicons-plus-alt" style="font-size:16px;width:16px;height:16px;"></span>';
            $header .= esc_html__('Subir archivo', 'flavor-platform');
            $header .= '</a>';
        }
        $header .= '</div>';

        return $header . do_shortcode('[flavor_multimedia_galeria entidad="grupo_consumo" entidad_id="' . (int) $grupo->ID . '" limite="12" columnas="4" mostrar_filtros="true"]');
    }

    /**
     * Renderiza el tab contextual de red social.
     *
     * @return string
     */
    public function render_tab_red_social() {
        if (!is_user_logged_in()) {
            return '<div class="flavor-empty-state bg-gray-50 rounded-xl p-8 text-center">
                <span class="text-5xl mb-4 block">🫂</span>
                <h3 class="text-lg font-semibold text-gray-900 mb-2">' . esc_html__('Actividad social del grupo', 'flavor-platform') . '</h3>
                <p class="text-gray-500 mb-4">' . esc_html__('Inicia sesión para ver la actividad social del grupo.', 'flavor-platform') . '</p>
            </div>';
        }

        $grupo = $this->get_contextual_grupo_for_satellite_tabs();
        if (!$grupo) {
            return '<div class="flavor-empty-state bg-gray-50 rounded-xl p-8 text-center">
                <span class="text-5xl mb-4 block">🫂</span>
                <h3 class="text-lg font-semibold text-gray-900 mb-2">' . esc_html__('Actividad social del grupo', 'flavor-platform') . '</h3>
                <p class="text-gray-500 mb-4">' . esc_html__('Accede a un grupo concreto para ver su actividad social.', 'flavor-platform') . '</p>
            </div>';
        }

        $header = '<div class="flavor-integrated-tab-header bg-white rounded-xl p-4 mb-4 border border-gray-100 flex items-center justify-between gap-4 flex-wrap">';
        $header .= '<div>';
        $header .= '<h3 class="text-lg font-semibold text-gray-900 mb-1">' . esc_html__('Actividad social del grupo', 'flavor-platform') . '</h3>';
        $header .= '<p class="text-sm text-gray-500">' . esc_html(get_the_title($grupo)) . '</p>';
        $header .= '</div>';
        $header .= '<a class="inline-flex items-center gap-2 px-4 py-2 rounded-lg bg-emerald-600 text-white text-sm font-medium hover:opacity-90" href="' . esc_url(add_query_arg(['grupo_id' => (int) $grupo->ID], home_url('/mi-portal/red-social/crear/'))) . '">';
        $header .= '<span class="dashicons dashicons-plus-alt" style="font-size:16px;width:16px;height:16px;"></span>';
        $header .= esc_html__('Publicar', 'flavor-platform');
        $header .= '</a>';
        $header .= '</div>';

        return $header . do_shortcode('[flavor_social_feed entidad="grupo_consumo" entidad_id="' . (int) $grupo->ID . '"]');
    }

    /**
     * Renderiza el tab contextual de recetas.
     *
     * @return string
     */
    public function render_tab_recetas() {
        $grupo = $this->get_contextual_grupo_for_satellite_tabs();
        if (!$grupo) {
            return '<div class="flavor-empty-state bg-gray-50 rounded-xl p-8 text-center">
                <span class="text-5xl mb-4 block">🍳</span>
                <h3 class="text-lg font-semibold text-gray-900 mb-2">' . esc_html__('Recetas del grupo', 'flavor-platform') . '</h3>
                <p class="text-gray-500 mb-4">' . esc_html__('Accede a un grupo concreto para descubrir sus recetas relacionadas.', 'flavor-platform') . '</p>
            </div>';
        }

        $header = '<div class="flavor-integrated-tab-header bg-white rounded-xl p-4 mb-4 border border-gray-100 flex items-center justify-between gap-4 flex-wrap">';
        $header .= '<div>';
        $header .= '<h3 class="text-lg font-semibold text-gray-900 mb-1">' . esc_html__('Recetas del grupo', 'flavor-platform') . '</h3>';
        $header .= '<p class="text-sm text-gray-500">' . esc_html(get_the_title($grupo)) . '</p>';
        $header .= '</div>';
        if (is_user_logged_in()) {
            $header .= '<a class="inline-flex items-center gap-2 px-4 py-2 rounded-lg bg-emerald-600 text-white text-sm font-medium hover:opacity-90" href="' . esc_url(add_query_arg(['grupo_id' => (int) $grupo->ID], home_url('/mi-portal/recetas/nueva/'))) . '">';
            $header .= '<span class="dashicons dashicons-plus-alt" style="font-size:16px;width:16px;height:16px;"></span>';
            $header .= esc_html__('Compartir receta', 'flavor-platform');
            $header .= '</a>';
        }
        $header .= '</div>';

        return $header . do_shortcode('[flavor module="recetas" view="listado" header="no" limit="12"]');
    }

    /**
     * Renderiza el tab contextual de biblioteca.
     *
     * @return string
     */
    public function render_tab_biblioteca() {
        $grupo = $this->get_contextual_grupo_for_satellite_tabs();
        if (!$grupo) {
            return '<div class="flavor-empty-state bg-gray-50 rounded-xl p-8 text-center">
                <span class="text-5xl mb-4 block">📚</span>
                <h3 class="text-lg font-semibold text-gray-900 mb-2">' . esc_html__('Biblioteca del grupo', 'flavor-platform') . '</h3>
                <p class="text-gray-500 mb-4">' . esc_html__('Accede a un grupo concreto para explorar su biblioteca compartida.', 'flavor-platform') . '</p>
            </div>';
        }

        $header = '<div class="flavor-integrated-tab-header bg-white rounded-xl p-4 mb-4 border border-gray-100 flex items-center justify-between gap-4 flex-wrap">';
        $header .= '<div>';
        $header .= '<h3 class="text-lg font-semibold text-gray-900 mb-1">' . esc_html__('Biblioteca del grupo', 'flavor-platform') . '</h3>';
        $header .= '<p class="text-sm text-gray-500">' . esc_html(get_the_title($grupo)) . '</p>';
        $header .= '</div>';
        if (is_user_logged_in()) {
            $header .= '<a class="inline-flex items-center gap-2 px-4 py-2 rounded-lg bg-emerald-600 text-white text-sm font-medium hover:opacity-90" href="' . esc_url(add_query_arg(['grupo_id' => (int) $grupo->ID], home_url('/mi-portal/biblioteca/anadir/'))) . '">';
            $header .= '<span class="dashicons dashicons-plus-alt" style="font-size:16px;width:16px;height:16px;"></span>';
            $header .= esc_html__('Añadir libro', 'flavor-platform');
            $header .= '</a>';
        }
        $header .= '</div>';

        return $header . do_shortcode('[biblioteca_catalogo]');
    }

    /**
     * Renderiza el tab contextual de podcast.
     *
     * @return string
     */
    public function render_tab_podcast() {
        $grupo = $this->get_contextual_grupo_for_satellite_tabs();
        if (!$grupo) {
            return '<div class="flavor-empty-state bg-gray-50 rounded-xl p-8 text-center">
                <span class="text-5xl mb-4 block">🎙️</span>
                <h3 class="text-lg font-semibold text-gray-900 mb-2">' . esc_html__('Podcast del grupo', 'flavor-platform') . '</h3>
                <p class="text-gray-500 mb-4">' . esc_html__('Accede a un grupo concreto para escuchar sus programas y episodios.', 'flavor-platform') . '</p>
            </div>';
        }

        $header = '<div class="flavor-integrated-tab-header bg-white rounded-xl p-4 mb-4 border border-gray-100 flex items-center justify-between gap-4 flex-wrap">';
        $header .= '<div>';
        $header .= '<h3 class="text-lg font-semibold text-gray-900 mb-1">' . esc_html__('Podcast del grupo', 'flavor-platform') . '</h3>';
        $header .= '<p class="text-sm text-gray-500">' . esc_html(get_the_title($grupo)) . '</p>';
        $header .= '</div>';
        $header .= '<a class="inline-flex items-center gap-2 px-4 py-2 rounded-lg bg-emerald-600 text-white text-sm font-medium hover:opacity-90" href="' . esc_url(add_query_arg(['grupo_id' => (int) $grupo->ID], home_url('/mi-portal/podcast/programas/'))) . '">';
        $header .= '<span class="dashicons dashicons-external" style="font-size:16px;width:16px;height:16px;"></span>';
        $header .= esc_html__('Abrir módulo completo', 'flavor-platform');
        $header .= '</a>';
        $header .= '</div>';

        return $header . do_shortcode('[podcast_series]');
    }

    /**
     * Shortcode: Formulario para unirse a un grupo de consumo
     *
     * @param array $atributos Atributos del shortcode
     * @return string HTML del formulario
     */
    public function shortcode_formulario_union($atributos) {
        $atributos = shortcode_atts([
            'grupo_id' => '',
        ], $atributos);

        // Obtener grupo_id de la URL si no se especifica
        $grupo_id = !empty($atributos['grupo_id']) ? intval($atributos['grupo_id']) : intval($_GET['grupo'] ?? 0);

        // Si el usuario ya está logueado, verificar si ya es miembro
        if (is_user_logged_in() && $grupo_id > 0) {
            $consumidor_manager = Flavor_GC_Consumidor_Manager::get_instance();
            $consumidor = $consumidor_manager->obtener_consumidor(get_current_user_id(), $grupo_id);

            if ($consumidor && $consumidor->estado === 'activo') {
                return '<div class="gc-aviso gc-aviso-info"><p>' .
                    __('Ya eres miembro de este grupo de consumo.', 'flavor-platform') .
                    '</p><a href="' . esc_url(home_url('/mi-portal/grupos-consumo/productos/')) . '" class="gc-btn gc-btn-primary">' .
                    __('Ver productos', 'flavor-platform') . '</a></div>';
            }
        }

        // Obtener grupos disponibles para el select
        $grupos_disponibles = get_posts([
            'post_type' => 'gc_grupo',
            'post_status' => 'publish',
            'posts_per_page' => -1,
            'orderby' => 'title',
            'order' => 'ASC',
            'meta_query' => [
                'relation' => 'OR',
                [
                    'key' => '_gc_acepta_miembros',
                    'value' => '1',
                    'compare' => '=',
                ],
                [
                    'key' => '_gc_acepta_miembros',
                    'compare' => 'NOT EXISTS',
                ],
            ],
        ]);

        $grupo_seleccionado = null;
        if ($grupo_id > 0) {
            $grupo_seleccionado = get_post($grupo_id);
        }

        $usuario_logueado = is_user_logged_in();
        $usuario_actual = $usuario_logueado ? wp_get_current_user() : null;

        ob_start();
        ?>
        <div class="gc-formulario-union">
            <?php if (empty($grupos_disponibles)): ?>
                <div class="gc-aviso gc-aviso-warning">
                    <p><?php _e('No hay grupos de consumo aceptando nuevos miembros en este momento.', 'flavor-platform'); ?></p>
                </div>
            <?php else: ?>
                <form id="gc-form-union" class="gc-form" method="post" action="">
                    <?php wp_nonce_field('gc_union_nonce', 'gc_union_nonce'); ?>

                    <div class="gc-form-header">
                        <h3><?php _e('Unirme a un Grupo de Consumo', 'flavor-platform'); ?></h3>
                        <p><?php _e('Completa el formulario para solicitar tu incorporación al grupo.', 'flavor-platform'); ?></p>
                    </div>

                    <div class="gc-form-group">
                        <label for="gc_grupo_id"><?php _e('Grupo de Consumo', 'flavor-platform'); ?> *</label>
                        <select id="gc_grupo_id" name="grupo_id" required>
                            <option value=""><?php _e('Selecciona un grupo', 'flavor-platform'); ?></option>
                            <?php foreach ($grupos_disponibles as $grupo):
                                $ubicacion_grupo = get_post_meta($grupo->ID, '_gc_ubicacion', true);
                            ?>
                                <option value="<?php echo esc_attr($grupo->ID); ?>" <?php selected($grupo_id, $grupo->ID); ?>>
                                    <?php echo esc_html($grupo->post_title); ?>
                                    <?php if ($ubicacion_grupo): ?>
                                        (<?php echo esc_html($ubicacion_grupo); ?>)
                                    <?php endif; ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <?php if (!$usuario_logueado): ?>
                        <div class="gc-form-group">
                            <label for="gc_nombre"><?php _e('Nombre completo', 'flavor-platform'); ?> *</label>
                            <input type="text" id="gc_nombre" name="nombre" required>
                        </div>

                        <div class="gc-form-group">
                            <label for="gc_email"><?php _e('Email', 'flavor-platform'); ?> *</label>
                            <input type="email" id="gc_email" name="email" required>
                        </div>

                        <div class="gc-form-group">
                            <label for="gc_telefono"><?php _e('Teléfono', 'flavor-platform'); ?></label>
                            <input type="tel" id="gc_telefono" name="telefono">
                        </div>
                    <?php else: ?>
                        <div class="gc-form-info">
                            <p><strong><?php _e('Nombre:', 'flavor-platform'); ?></strong> <?php echo esc_html($usuario_actual->display_name); ?></p>
                            <p><strong><?php _e('Email:', 'flavor-platform'); ?></strong> <?php echo esc_html($usuario_actual->user_email); ?></p>
                        </div>
                    <?php endif; ?>

                    <div class="gc-form-group">
                        <label for="gc_direccion"><?php _e('Dirección de entrega', 'flavor-platform'); ?></label>
                        <textarea id="gc_direccion" name="direccion" rows="2" placeholder="<?php esc_attr_e('Calle, número, piso...', 'flavor-platform'); ?>"></textarea>
                    </div>

                    <div class="gc-form-group">
                        <label for="gc_preferencias"><?php _e('Preferencias alimentarias', 'flavor-platform'); ?></label>
                        <textarea id="gc_preferencias" name="preferencias" rows="3" placeholder="<?php esc_attr_e('Alergias, intolerancias, preferencias...', 'flavor-platform'); ?>"></textarea>
                    </div>

                    <div class="gc-form-group">
                        <label for="gc_motivacion"><?php _e('¿Por qué quieres unirte?', 'flavor-platform'); ?></label>
                        <textarea id="gc_motivacion" name="motivacion" rows="3" placeholder="<?php esc_attr_e('Cuéntanos brevemente tu motivación...', 'flavor-platform'); ?>"></textarea>
                    </div>

                    <div class="gc-form-group gc-form-checkbox">
                        <label>
                            <input type="checkbox" name="acepta_condiciones" required>
                            <?php _e('Acepto las condiciones de participación del grupo de consumo', 'flavor-platform'); ?> *
                        </label>
                    </div>

                    <div class="gc-form-actions">
                        <button type="submit" class="gc-btn gc-btn-primary gc-btn-lg">
                            <?php _e('Enviar solicitud', 'flavor-platform'); ?>
                        </button>
                    </div>

                    <div class="gc-form-mensaje" style="display: none;"></div>
                </form>

                <script>
                jQuery(document).ready(function($) {
                    $('#gc-form-union').on('submit', function(e) {
                        e.preventDefault();
                        var $form = $(this);
                        var $mensaje = $form.find('.gc-form-mensaje');
                        var $boton = $form.find('button[type="submit"]');

                        $boton.prop('disabled', true).text('<?php echo esc_js(__('Enviando...', 'flavor-platform')); ?>');

                        $.ajax({
                            url: gcFrontend.ajaxUrl,
                            type: 'POST',
                            data: {
                                action: 'gc_solicitar_union',
                                nonce: gcFrontend.nonce,
                                grupo_id: $form.find('[name="grupo_id"]').val(),
                                nombre: $form.find('[name="nombre"]').val() || '',
                                email: $form.find('[name="email"]').val() || '',
                                telefono: $form.find('[name="telefono"]').val() || '',
                                direccion: $form.find('[name="direccion"]').val(),
                                preferencias: $form.find('[name="preferencias"]').val(),
                                motivacion: $form.find('[name="motivacion"]').val()
                            },
                            success: function(response) {
                                if (response.success) {
                                    $mensaje.removeClass('gc-error').addClass('gc-success')
                                        .html('<p>' + response.data.mensaje + '</p>').show();
                                    $form.find('input, textarea, select, button').prop('disabled', true);
                                } else {
                                    $mensaje.removeClass('gc-success').addClass('gc-error')
                                        .html('<p>' + response.data.mensaje + '</p>').show();
                                    $boton.prop('disabled', false).text('<?php echo esc_js(__('Enviar solicitud', 'flavor-platform')); ?>');
                                }
                            },
                            error: function() {
                                $mensaje.removeClass('gc-success').addClass('gc-error')
                                    .html('<p><?php echo esc_js(__('Error al procesar la solicitud. Inténtalo de nuevo.', 'flavor-platform')); ?></p>').show();
                                $boton.prop('disabled', false).text('<?php echo esc_js(__('Enviar solicitud', 'flavor-platform')); ?>');
                            }
                        });
                    });
                });
                </script>
            <?php endif; ?>
        </div>
        <?php
        return ob_get_clean();
    }

    /**
     * Shortcode: Productores cercanos al usuario
     *
     * Muestra productores que realizan entregas a domicilio en la ubicación del usuario.
     * Usa la geolocalización del navegador para determinar la ubicación.
     *
     * @param array $atributos Atributos del shortcode:
     *                         - vista: 'cercanos', 'grid', 'list' (default: 'cercanos')
     *                         - limite/limit: número máximo de productores
     *                         - mostrar_mapa: 'si' o 'no'
     *                         - titulo: título de la sección
     * @return string HTML del componente
     */
    public function shortcode_productores_cercanos($atributos) {
        $atributos = shortcode_atts([
            'vista'       => 'cercanos',
            'limite'      => 12,
            'limit'       => 12, // Alias
            'mostrar_mapa' => 'si',
            'titulo'      => __('Productores que entregan en tu zona', 'flavor-platform'),
        ], $atributos);

        // Usar limit si se proporciona
        $limite = absint($atributos['limit']) ?: absint($atributos['limite']);

        // Vista de grid: mostrar todos los productores en formato grid simple
        if ($atributos['vista'] === 'grid' || $atributos['vista'] === 'list') {
            return $this->render_productores_grid($atributos);
        }

        // Encolar assets necesarios
        wp_enqueue_script('gc-frontend');
        wp_enqueue_style('gc-frontend');

        ob_start();
        ?>
        <div class="gc-productores-cercanos" id="gc-productores-cercanos-container">
            <div class="gc-seccion-header">
                <h2 class="gc-seccion-titulo">
                    <span class="dashicons dashicons-location-alt" style="color: #16a34a;"></span>
                    <?php echo esc_html($atributos['titulo']); ?>
                </h2>
                <p class="gc-seccion-descripcion">
                    <?php _e('Descubre productores locales que entregan directamente a tu domicilio.', 'flavor-platform'); ?>
                </p>
            </div>

            <!-- Estado inicial: solicitar ubicación -->
            <div id="gc-solicitar-ubicacion" class="gc-ubicacion-solicitar">
                <div class="gc-ubicacion-icono">
                    <svg xmlns="http://www.w3.org/2000/svg" width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="color: #16a34a;">
                        <circle cx="12" cy="10" r="3"/>
                        <path d="M12 21.7C17.3 17 20 13 20 10a8 8 0 1 0-16 0c0 3 2.7 6.9 8 11.7z"/>
                    </svg>
                </div>
                <p><?php _e('Para mostrarte productores que entregan en tu zona, necesitamos conocer tu ubicación.', 'flavor-platform'); ?></p>
                <button type="button" id="gc-obtener-ubicacion-btn" class="gc-btn gc-btn-primary gc-btn-lg">
                    <span class="dashicons dashicons-location" style="vertical-align: middle;"></span>
                    <?php _e('Compartir mi ubicación', 'flavor-platform'); ?>
                </button>
            </div>

            <!-- Estado: cargando -->
            <div id="gc-cargando-productores" class="gc-cargando" style="display: none;">
                <div class="gc-spinner"></div>
                <p><?php _e('Buscando productores cercanos...', 'flavor-platform'); ?></p>
            </div>

            <!-- Estado: sin ubicación / error -->
            <div id="gc-error-ubicacion" class="gc-error-ubicacion" style="display: none;">
                <span class="dashicons dashicons-warning" style="color: #dc2626;"></span>
                <p id="gc-error-mensaje"><?php _e('No pudimos obtener tu ubicación.', 'flavor-platform'); ?></p>
                <button type="button" id="gc-reintentar-ubicacion-btn" class="gc-btn gc-btn-outline">
                    <?php _e('Reintentar', 'flavor-platform'); ?>
                </button>
            </div>

            <!-- Resultados -->
            <div id="gc-productores-resultados" class="gc-productores-grid" style="display: none;">
                <!-- Se llenan dinámicamente con JavaScript -->
            </div>

            <!-- Sin resultados -->
            <div id="gc-sin-productores" class="gc-sin-resultados" style="display: none;">
                <span class="dashicons dashicons-store" style="color: #9ca3af;"></span>
                <h3><?php _e('No hay productores que entreguen en tu zona', 'flavor-platform'); ?></h3>
                <p><?php _e('Por ahora no hay productores que realicen entregas a domicilio en tu ubicación. Puedes explorar todos nuestros productores.', 'flavor-platform'); ?></p>
                <a href="<?php echo esc_url(get_post_type_archive_link('gc_productor')); ?>" class="gc-btn gc-btn-outline">
                    <?php _e('Ver todos los productores', 'flavor-platform'); ?>
                </a>
            </div>

            <?php if ($atributos['mostrar_mapa'] === 'si'): ?>
                <!-- Mapa -->
                <div id="gc-mapa-productores-container" style="display: none; margin-top: 20px;">
                    <div id="gc-mapa-productores" style="width: 100%; height: 400px; border-radius: 12px; border: 1px solid #e5e7eb;"></div>
                </div>
            <?php endif; ?>
        </div>

        <style>
        .gc-productores-cercanos {
            padding: 20px 0;
        }
        .gc-seccion-header {
            text-align: center;
            margin-bottom: 30px;
        }
        .gc-seccion-titulo {
            font-size: 1.75rem;
            font-weight: 700;
            color: #111827;
            margin-bottom: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
        }
        .gc-seccion-descripcion {
            color: #6b7280;
            font-size: 1rem;
        }
        .gc-ubicacion-solicitar,
        .gc-error-ubicacion,
        .gc-cargando,
        .gc-sin-resultados {
            text-align: center;
            padding: 40px 20px;
            background: #f9fafb;
            border-radius: 12px;
            border: 2px dashed #e5e7eb;
        }
        .gc-ubicacion-icono {
            margin-bottom: 15px;
        }
        .gc-spinner {
            width: 40px;
            height: 40px;
            border: 4px solid #e5e7eb;
            border-top-color: #16a34a;
            border-radius: 50%;
            animation: gc-spin 1s linear infinite;
            margin: 0 auto 15px;
        }
        @keyframes gc-spin {
            to { transform: rotate(360deg); }
        }
        .gc-productores-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
            gap: 20px;
        }
        .gc-productor-card {
            background: #fff;
            border-radius: 12px;
            border: 1px solid #e5e7eb;
            overflow: hidden;
            transition: all 0.3s ease;
        }
        .gc-productor-card:hover {
            box-shadow: 0 10px 25px rgba(0,0,0,0.1);
            transform: translateY(-2px);
        }
        .gc-productor-imagen {
            height: 150px;
            background: linear-gradient(135deg, #dcfce7 0%, #bbf7d0 100%);
            display: flex;
            align-items: center;
            justify-content: center;
            position: relative;
        }
        .gc-productor-imagen img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }
        .gc-productor-badges {
            position: absolute;
            top: 10px;
            right: 10px;
            display: flex;
            gap: 5px;
        }
        .gc-badge-entrega {
            background: #dbeafe;
            color: #1d4ed8;
            padding: 4px 8px;
            border-radius: 6px;
            font-size: 0.75rem;
            font-weight: 600;
            display: flex;
            align-items: center;
            gap: 4px;
        }
        .gc-badge-eco {
            background: #dcfce7;
            color: #16a34a;
            padding: 4px 8px;
            border-radius: 6px;
            font-size: 0.75rem;
            font-weight: 600;
        }
        .gc-productor-info {
            padding: 15px;
        }
        .gc-productor-nombre {
            font-size: 1.1rem;
            font-weight: 700;
            color: #111827;
            margin-bottom: 5px;
        }
        .gc-productor-descripcion {
            color: #6b7280;
            font-size: 0.9rem;
            margin-bottom: 10px;
        }
        .gc-productor-distancia {
            color: #16a34a;
            font-size: 0.85rem;
            font-weight: 600;
            display: flex;
            align-items: center;
            gap: 5px;
        }
        .gc-productor-footer {
            padding: 10px 15px 15px;
            border-top: 1px solid #f3f4f6;
        }
        </style>

        <script>
        jQuery(document).ready(function($) {
            var limite = <?php echo intval($atributos['limite']); ?>;
            var mostrarMapa = <?php echo $atributos['mostrar_mapa'] === 'si' ? 'true' : 'false'; ?>;
            var mapaLeaflet = null;
            var marcadores = [];

            function mostrarEstado(estado) {
                $('#gc-solicitar-ubicacion, #gc-cargando-productores, #gc-error-ubicacion, #gc-productores-resultados, #gc-sin-productores, #gc-mapa-productores-container').hide();
                $(estado).show();
            }

            function obtenerUbicacion() {
                if (!navigator.geolocation) {
                    mostrarEstado('#gc-error-ubicacion');
                    $('#gc-error-mensaje').text('<?php echo esc_js(__('Tu navegador no soporta geolocalización.', 'flavor-platform')); ?>');
                    return;
                }

                mostrarEstado('#gc-cargando-productores');

                navigator.geolocation.getCurrentPosition(
                    function(posicion) {
                        buscarProductoresCercanos(posicion.coords.latitude, posicion.coords.longitude);
                    },
                    function(error) {
                        mostrarEstado('#gc-error-ubicacion');
                        var mensajesError = {
                            1: '<?php echo esc_js(__('Permiso de ubicación denegado.', 'flavor-platform')); ?>',
                            2: '<?php echo esc_js(__('No se pudo obtener la ubicación.', 'flavor-platform')); ?>',
                            3: '<?php echo esc_js(__('Tiempo de espera agotado.', 'flavor-platform')); ?>'
                        };
                        $('#gc-error-mensaje').text(mensajesError[error.code] || '<?php echo esc_js(__('Error desconocido.', 'flavor-platform')); ?>');
                    },
                    {
                        enableHighAccuracy: true,
                        timeout: 10000,
                        maximumAge: 300000
                    }
                );
            }

            function buscarProductoresCercanos(latitud, longitud) {
                $.ajax({
                    url: '<?php echo esc_url(rest_url('flavor-chat-ia/v1/gc/productores-cercanos')); ?>',
                    method: 'GET',
                    data: {
                        lat: latitud,
                        lng: longitud,
                        limite: limite
                    },
                    beforeSend: function(xhr) {
                        xhr.setRequestHeader('X-WP-Nonce', '<?php echo wp_create_nonce('wp_rest'); ?>');
                    },
                    success: function(response) {
                        if (response.success && response.data.length > 0) {
                            renderizarProductores(response.data, latitud, longitud);
                            mostrarEstado('#gc-productores-resultados');
                            if (mostrarMapa) {
                                $('#gc-mapa-productores-container').show();
                                inicializarMapa(response.data, latitud, longitud);
                            }
                        } else {
                            mostrarEstado('#gc-sin-productores');
                        }
                    },
                    error: function() {
                        mostrarEstado('#gc-error-ubicacion');
                        $('#gc-error-mensaje').text('<?php echo esc_js(__('Error al buscar productores.', 'flavor-platform')); ?>');
                    }
                });
            }

            function renderizarProductores(productores, latUsuario, lngUsuario) {
                var contenedor = $('#gc-productores-resultados');
                contenedor.empty();

                productores.forEach(function(productor) {
                    var htmlCard = '<div class="gc-productor-card">' +
                        '<div class="gc-productor-imagen">' +
                            (productor.imagen ? '<img src="' + productor.imagen + '" alt="<?php echo esc_attr__('\' + productor.nombre + \'', 'flavor-platform'); ?>">' : '<span style="font-size: 48px;">🌾</span>') +
                            '<div class="gc-productor-badges">' +
                                '<span class="gc-badge-entrega"><svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M5 8h14M5 8a2 2 0 110-4h14a2 2 0 110 4M5 8v10a2 2 0 002 2h10a2 2 0 002-2V8m-9 4h4"/></svg> <?php echo esc_js(__('Entrega', 'flavor-platform')); ?></span>' +
                                (productor.certificacion_eco ? '<span class="gc-badge-eco">ECO</span>' : '') +
                            '</div>' +
                        '</div>' +
                        '<div class="gc-productor-info">' +
                            '<h3 class="gc-productor-nombre">' + productor.nombre + '</h3>' +
                            '<p class="gc-productor-descripcion">' + (productor.descripcion || '') + '</p>' +
                            '<p class="gc-productor-distancia"><svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="10" r="3"/><path d="M12 21.7C17.3 17 20 13 20 10a8 8 0 1 0-16 0c0 3 2.7 6.9 8 11.7z"/></svg> ' + productor.distancia_km + ' km - <?php echo esc_js(__('Entrega hasta', 'flavor-platform')); ?> ' + productor.radio_entrega_km + ' km</p>' +
                        '</div>' +
                        '<div class="gc-productor-footer">' +
                            '<a href="' + productor.url + '" class="gc-btn gc-btn-primary gc-btn-block"><?php echo esc_js(__('Ver productor', 'flavor-platform')); ?></a>' +
                        '</div>' +
                    '</div>';

                    contenedor.append(htmlCard);
                });
            }

            function inicializarMapa(productores, latUsuario, lngUsuario) {
                // Cargar Leaflet si no está cargado
                if (typeof L === 'undefined') {
                    if ($('link[href*="leaflet.css"]').length === 0) {
                        $('head').append('<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />');
                    }
                    $.getScript('https://unpkg.com/leaflet@1.9.4/dist/leaflet.js', function() {
                        crearMapa(productores, latUsuario, lngUsuario);
                    });
                } else {
                    crearMapa(productores, latUsuario, lngUsuario);
                }
            }

            function crearMapa(productores, latUsuario, lngUsuario) {
                if (mapaLeaflet) {
                    mapaLeaflet.remove();
                }

                mapaLeaflet = L.map('gc-mapa-productores').setView([latUsuario, lngUsuario], 11);

                L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                    attribution: '&copy; OpenStreetMap contributors'
                }).addTo(mapaLeaflet);

                // Marcador del usuario
                var iconoUsuario = L.divIcon({
                    html: '<div style="background: #3b82f6; width: 20px; height: 20px; border-radius: 50%; border: 3px solid white; box-shadow: 0 2px 5px rgba(0,0,0,0.3);"></div>',
                    className: 'gc-marcador-usuario',
                    iconSize: [20, 20],
                    iconAnchor: [10, 10]
                });
                L.marker([latUsuario, lngUsuario], {icon: iconoUsuario})
                    .addTo(mapaLeaflet)
                    .bindPopup('<?php echo esc_js(__('Tu ubicación', 'flavor-platform')); ?>');

                // Marcadores de productores
                productores.forEach(function(productor) {
                    if (productor.coordenadas) {
                        var iconoProductor = L.divIcon({
                            html: '<div style="background: #16a34a; width: 24px; height: 24px; border-radius: 50%; border: 3px solid white; box-shadow: 0 2px 5px rgba(0,0,0,0.3); display: flex; align-items: center; justify-content: center; color: white; font-size: 12px;">🌾</div>',
                            className: 'gc-marcador-productor',
                            iconSize: [24, 24],
                            iconAnchor: [12, 12]
                        });

                        L.marker([productor.coordenadas.lat, productor.coordenadas.lng], {icon: iconoProductor})
                            .addTo(mapaLeaflet)
                            .bindPopup('<strong>' + productor.nombre + '</strong><br>' + productor.distancia_km + ' km' + (productor.certificacion_eco ? ' <span style="color: #16a34a;">ECO</span>' : ''));

                        // Círculo de cobertura
                        L.circle([productor.coordenadas.lat, productor.coordenadas.lng], {
                            color: '#16a34a',
                            fillColor: '#16a34a',
                            fillOpacity: 0.1,
                            radius: productor.radio_entrega_km * 1000
                        }).addTo(mapaLeaflet);
                    }
                });

                setTimeout(function() {
                    mapaLeaflet.invalidateSize();
                }, 100);
            }

            // Event handlers
            $('#gc-obtener-ubicacion-btn, #gc-reintentar-ubicacion-btn').on('click', function() {
                obtenerUbicacion();
            });
        });
        </script>
        <?php
        return ob_get_clean();
    }

    /**
     * Renderiza un grid de productores
     *
     * @param array $atributos Atributos del shortcode
     * @return string HTML del grid
     */
    protected function render_productores_grid($atributos) {
        $limite = absint($atributos['limit']) ?: absint($atributos['limite']);
        $vista = $atributos['vista'];

        // Obtener productores
        $productores = get_posts([
            'post_type'      => 'gc_productor',
            'post_status'    => 'publish',
            'posts_per_page' => $limite ?: 12,
            'orderby'        => 'title',
            'order'          => 'ASC',
        ]);

        if (empty($productores)) {
            return '<p class="gc-sin-productores">' . __('No hay productores disponibles en este momento.', 'flavor-platform') . '</p>';
        }

        ob_start();
        ?>
        <div class="gc-productores-grid gc-productores-<?php echo esc_attr($vista); ?>">
            <?php foreach ($productores as $productor):
                $imagen = get_the_post_thumbnail_url($productor->ID, 'medium');
                $certificacion_eco = get_post_meta($productor->ID, '_gc_certificacion_eco', true);
                $ubicacion = get_post_meta($productor->ID, '_gc_ubicacion', true);
                $descripcion = get_the_excerpt($productor);

                // Contar productos del productor
                $productos_count = count(get_posts([
                    'post_type'      => 'gc_producto',
                    'post_status'    => 'publish',
                    'posts_per_page' => -1,
                    'meta_query'     => [
                        [
                            'key'   => '_gc_productor_id',
                            'value' => $productor->ID,
                        ],
                    ],
                    'fields' => 'ids',
                ]));
            ?>
                <div class="gc-productor-card">
                    <div class="gc-productor-imagen">
                        <?php if ($imagen): ?>
                            <img src="<?php echo esc_url($imagen); ?>" alt="<?php echo esc_attr($productor->post_title); ?>" />
                        <?php else: ?>
                            <div class="gc-productor-sin-imagen">
                                <span class="dashicons dashicons-store"></span>
                            </div>
                        <?php endif; ?>
                        <?php if ($certificacion_eco): ?>
                            <span class="gc-badge-eco" title="<?php esc_attr_e('Productor ecológico certificado', 'flavor-platform'); ?>">
                                <span class="dashicons dashicons-awards"></span>
                            </span>
                        <?php endif; ?>
                    </div>
                    <div class="gc-productor-info">
                        <h4><?php echo esc_html($productor->post_title); ?></h4>
                        <?php if ($ubicacion): ?>
                            <p class="gc-productor-ubicacion">
                                <span class="dashicons dashicons-location"></span>
                                <?php echo esc_html($ubicacion); ?>
                            </p>
                        <?php endif; ?>
                        <?php if ($descripcion): ?>
                            <p class="gc-productor-descripcion"><?php echo esc_html(wp_trim_words($descripcion, 15)); ?></p>
                        <?php endif; ?>
                        <p class="gc-productor-productos">
                            <span class="dashicons dashicons-products"></span>
                            <?php printf(_n('%d producto', '%d productos', $productos_count, 'flavor-platform'), $productos_count); ?>
                        </p>
                    </div>
                    <div class="gc-productor-acciones">
                        <a href="<?php echo esc_url(add_query_arg('productor', intval($productor->ID), home_url('/mi-portal/grupos-consumo/productores-cercanos/'))); ?>" class="gc-btn gc-btn-outline gc-btn-sm">
                            <?php _e('Ver productos', 'flavor-platform'); ?>
                        </a>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
        <style>
        .gc-productores-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(280px, 1fr)); gap: 20px; padding: 20px 0; }
        .gc-productores-list { display: flex; flex-direction: column; gap: 15px; }
        .gc-productores-list .gc-productor-card { display: flex; flex-direction: row; align-items: center; }
        .gc-productores-list .gc-productor-imagen { width: 100px; flex-shrink: 0; }
        .gc-productor-card { background: #fff; border-radius: 12px; overflow: hidden; box-shadow: 0 2px 8px rgba(0,0,0,0.08); border: 1px solid #e5e7eb; transition: transform 0.2s, box-shadow 0.2s; }
        .gc-productor-card:hover { transform: translateY(-2px); box-shadow: 0 4px 12px rgba(0,0,0,0.12); }
        .gc-productor-imagen { position: relative; height: 180px; background: #f3f4f6; }
        .gc-productor-imagen img { width: 100%; height: 100%; object-fit: cover; }
        .gc-productor-sin-imagen { display: flex; align-items: center; justify-content: center; height: 100%; color: #9ca3af; }
        .gc-productor-sin-imagen .dashicons { font-size: 48px; width: 48px; height: 48px; }
        .gc-badge-eco { position: absolute; top: 10px; right: 10px; background: #16a34a; color: #fff; width: 28px; height: 28px; border-radius: 50%; display: flex; align-items: center; justify-content: center; }
        .gc-badge-eco .dashicons { font-size: 16px; width: 16px; height: 16px; }
        .gc-productor-info { padding: 15px; }
        .gc-productor-info h4 { margin: 0 0 8px; font-size: 1.1rem; color: #111827; }
        .gc-productor-ubicacion, .gc-productor-productos { display: flex; align-items: center; gap: 6px; font-size: 0.85rem; color: #6b7280; margin: 5px 0; }
        .gc-productor-descripcion { font-size: 0.9rem; color: #4b5563; margin: 10px 0; line-height: 1.4; }
        .gc-productor-acciones { padding: 0 15px 15px; }
        .gc-btn-outline { display: inline-block; padding: 8px 16px; border: 2px solid #4a7c59; color: #4a7c59; text-decoration: none; border-radius: 6px; font-size: 0.875rem; font-weight: 500; background: transparent; }
        .gc-btn-outline:hover { background: #4a7c59; color: #fff; }
        </style>
        <?php
        return ob_get_clean();
    }

    /**
     * Define las páginas del módulo (Page Creator V3)
     *
     * @return array Definiciones de páginas
     */
    public function get_pages_definition() {
        if (!class_exists('Flavor_Page_Creator_V3')) {
            return [];
        }

        return [
            // Página principal
            [
                'title' => __('Grupos de Consumo', 'flavor-platform'),
                'slug' => 'grupos-consumo',
                'content' => Flavor_Page_Creator_V3::page_content([
                    'title' => __('Grupos de Consumo Responsable', 'flavor-platform'),
                    'subtitle' => __('Productos locales y ecológicos de temporada', 'flavor-platform'),
                    'background' => 'gradient',
                    'module' => 'grupos_consumo',
                    'current' => 'listado',
                    'content_after' => '[flavor_module_listing module="grupos_consumo" action="grupos_activos" columnas="3"]',
                ]),
                'parent' => 0,
            ],

            // Catálogo de productos
            [
                'title' => __('Catálogo', 'flavor-platform'),
                'slug' => 'catalogo',
                'content' => Flavor_Page_Creator_V3::page_content([
                    'title' => __('Catálogo de Productos', 'flavor-platform'),
                    'subtitle' => __('Explora nuestra selección de productos ecológicos', 'flavor-platform'),
                    'module' => 'grupos_consumo',
                    'current' => 'catalogo',
                    'content_after' => '[gc_catalogo]',
                ]),
                'parent' => 'grupos-consumo',
            ],

            // Mis pedidos
            [
                'title' => __('Historial', 'flavor-platform'),
                'slug' => 'mis-pedidos',
                'content' => Flavor_Page_Creator_V3::page_content([
                    'title' => __('Historial', 'flavor-platform'),
                    'subtitle' => __('Gestiona tus pedidos y suscripciones', 'flavor-platform'),
                    'module' => 'grupos_consumo',
                    'current' => 'pedidos',
                    'content_after' => '[flavor_module_listing module="grupos_consumo" action="mis_pedidos" user_specific="yes"]',
                ]),
                'parent' => 'grupos-consumo',
            ],

            // Calendario de pedidos
            [
                'title' => __('Calendario', 'flavor-platform'),
                'slug' => 'calendario',
                'content' => Flavor_Page_Creator_V3::page_content([
                    'title' => __('Calendario de Pedidos', 'flavor-platform'),
                    'subtitle' => __('Próximos cierres de pedidos y entregas', 'flavor-platform'),
                    'module' => 'grupos_consumo',
                    'current' => 'calendario',
                    'content_after' => '[gc_calendario]',
                ]),
                'parent' => 'grupos-consumo',
            ],

            // Productores
            [
                'title' => __('Productores', 'flavor-platform'),
                'slug' => 'productores',
                'content' => Flavor_Page_Creator_V3::page_content([
                    'title' => __('Nuestros Productores', 'flavor-platform'),
                    'subtitle' => __('Conoce a los productores locales que nos abastecen', 'flavor-platform'),
                    'module' => 'grupos_consumo',
                    'current' => 'productores',
                    'content_after' => '[flavor_module_listing module="grupos_consumo" action="productores" columnas="3"]',
                ]),
                'parent' => 'grupos-consumo',
            ],
        ];
    }

    /**
     * Carga el sistema de pagos
     *
     * @return void
     * @since 4.1.0
     */
    private function cargar_sistema_pagos() {
        $base = dirname(__FILE__);

        // Cargar clase base y manager
        $payment_files = [
            'class-gc-payment-gateway.php',
            'class-gc-payment-manager.php',
        ];

        foreach ($payment_files as $file) {
            $path = $base . '/' . $file;
            if (file_exists($path)) {
                require_once $path;
            }
        }

        // Cargar pasarelas de pago
        $gateway_path = $base . '/gateways/';
        if (is_dir($gateway_path)) {
            $gateways = [
                'class-gc-gateway-pickup.php',
                'class-gc-gateway-stripe.php',
                'class-gc-gateway-paypal.php',
                'class-gc-gateway-woocommerce.php',
            ];

            foreach ($gateways as $gateway_file) {
                $full_path = $gateway_path . $gateway_file;
                if (file_exists($full_path)) {
                    require_once $full_path;
                }
            }
        }

        // Inicializar manager y registrar pasarelas
        if (class_exists('Flavor_GC_Payment_Manager')) {
            $manager = Flavor_GC_Payment_Manager::get_instance();

            // Registrar pasarelas disponibles
            if (class_exists('Flavor_GC_Gateway_Pickup')) {
                $manager->register_gateway(new Flavor_GC_Gateway_Pickup());
            }
            if (class_exists('Flavor_GC_Gateway_Stripe')) {
                $manager->register_gateway(new Flavor_GC_Gateway_Stripe());
            }
            if (class_exists('Flavor_GC_Gateway_PayPal')) {
                $manager->register_gateway(new Flavor_GC_Gateway_PayPal());
            }
            if (class_exists('Flavor_GC_Gateway_WooCommerce')) {
                $manager->register_gateway(new Flavor_GC_Gateway_WooCommerce());
            }
        }
    }

    /**
     * Registra el widget de dashboard
     *
     * @param Flavor_Widget_Registry $registry Registro de widgets
     * @return void
     * @since 4.1.0
     */
    public function register_dashboard_widget($registry) {
        $widget_path = dirname(__FILE__) . '/class-gc-dashboard-widget.php';

        if (!class_exists('Flavor_GC_Dashboard_Widget') && file_exists($widget_path)) {
            require_once $widget_path;
        }

        if (class_exists('Flavor_GC_Dashboard_Widget')) {
            $registry->register(new Flavor_GC_Dashboard_Widget());
        }
    }

    /**
     * Configuración para el Module Renderer
     *
     * @return array
     */
    public static function get_renderer_config(): array {
        return [
            'module'   => 'grupos-consumo',
            'title'    => __('Grupos de Consumo', 'flavor-platform'),
            'subtitle' => __('Compra colectiva directa a productores locales', 'flavor-platform'),
            'icon'     => '🥬',
            'color'    => 'success', // Usa variable CSS --flavor-success del tema

            'database' => [
                'table'       => 'flavor_gc_grupos',
                'primary_key' => 'id',
            ],

            'fields' => [
                'nombre'       => ['type' => 'text', 'label' => __('Nombre del grupo', 'flavor-platform'), 'required' => true],
                'descripcion'  => ['type' => 'textarea', 'label' => __('Descripción', 'flavor-platform')],
                'ubicacion'    => ['type' => 'text', 'label' => __('Punto de recogida', 'flavor-platform')],
                'dia_recogida' => ['type' => 'select', 'label' => __('Día de recogida', 'flavor-platform')],
                'hora_recogida' => ['type' => 'time', 'label' => __('Hora de recogida', 'flavor-platform')],
                'cuota_mensual' => ['type' => 'number', 'label' => __('Cuota mensual', 'flavor-platform'), 'step' => '0.5'],
                'max_miembros' => ['type' => 'number', 'label' => __('Máximo miembros', 'flavor-platform')],
            ],

            'estados' => [
                'activo'    => ['label' => __('Activo', 'flavor-platform'), 'color' => 'green', 'icon' => '🟢'],
                'pausado'   => ['label' => __('Pausado', 'flavor-platform'), 'color' => 'yellow', 'icon' => '⏸️'],
                'cerrado'   => ['label' => __('Cerrado', 'flavor-platform'), 'color' => 'red', 'icon' => '🔴'],
                'completo'  => ['label' => __('Completo', 'flavor-platform'), 'color' => 'blue', 'icon' => '✅'],
            ],

            'stats' => [
                'grupos_activos'    => ['label' => __('Grupos activos', 'flavor-platform'), 'icon' => '🥬', 'color' => 'emerald'],
                'consumidores'      => ['label' => __('Consumidores', 'flavor-platform'), 'icon' => '👥', 'color' => 'blue'],
                'productores'       => ['label' => __('Productores', 'flavor-platform'), 'icon' => '🌾', 'color' => 'amber'],
                'pedidos_mes'       => ['label' => __('Pedidos/mes', 'flavor-platform'), 'icon' => '📦', 'color' => 'purple'],
            ],

            'card' => [
                'template'     => 'grupo-consumo-card',
                'title_field'  => 'nombre',
                'subtitle_field' => 'ubicacion',
                'meta_fields'  => ['dia_recogida', 'max_miembros', 'estado'],
                'show_imagen'  => true,
                'show_estado'  => true,
            ],

            'tabs' => [
                // === TABS PÚBLICOS (visibles sin login) ===
                'grupos' => [
                    'label'   => __('Grupos', 'flavor-platform'),
                    'icon'    => 'dashicons-groups',
                    'content' => 'template:_archive.php',
                    'public'  => true,
                ],
                'productos' => [
                    'label'   => __('Productos', 'flavor-platform'),
                    'icon'    => 'dashicons-products',
                    'content' => '[gc_catalogo]',
                    'public'  => true,
                ],
                'productores' => [
                    'label'   => __('Productores', 'flavor-platform'),
                    'icon'    => 'dashicons-store',
                    'content' => '[gc_productores]',
                    'public'  => true,
                ],

                // === TABS PRIVADOS (requieren login) ===
                'mi-cesta' => [
                    'label'      => __('Mi cesta', 'flavor-platform'),
                    'icon'       => 'dashicons-cart',
                    'content'    => '[gc_mi_cesta]',
                    'requires_login' => true,
                ],
                'mi-pedido' => [
                    'label'      => __('Pedido actual', 'flavor-platform'),
                    'icon'       => 'dashicons-list-view',
                    'content'    => '[gc_mi_pedido]',
                    'requires_login' => true,
                ],
                'mis-pedidos' => [
                    'label'      => __('Historial', 'flavor-platform'),
                    'icon'       => 'dashicons-clipboard',
                    'content'    => '[gc_historial]',
                    'requires_login' => true,
                ],
                'ciclos' => [
                    'label'      => __('Ciclos', 'flavor-platform'),
                    'icon'       => 'dashicons-calendar-alt',
                    'content'    => '[gc_ciclo_actual]',
                    'requires_login' => true,
                ],
                'suscripciones' => [
                    'label'      => __('Suscripciones', 'flavor-platform'),
                    'icon'       => 'dashicons-heart',
                    'content'    => '[gc_suscripciones]',
                    'requires_login' => true,
                    'hidden_nav' => true,
                ],
                'panel' => [
                    'label'      => __('Panel', 'flavor-platform'),
                    'icon'       => 'dashicons-dashboard',
                    'content'    => '[gc_panel]',
                    'requires_login' => true,
                    'hidden_nav' => true,
                ],

                // === ACCIONES (accesibles solo por URL) ===
                'unirme' => [
                    'label'         => __('Unirme', 'flavor-platform'),
                    'icon'          => 'dashicons-plus-alt',
                    'content'       => '[gc_formulario_union]',
                    'requires_login' => true,
                    'hidden_nav'    => true,
                ],

                // === INTEGRACIONES CON OTROS MÓDULOS ===
                'foro' => [
                    'label'         => __('Foro', 'flavor-platform'),
                    'icon'          => 'dashicons-admin-comments',
                    'content'       => 'callback:render_tab_foro',
                ],
                'chat' => [
                    'label'         => __('Chat', 'flavor-platform'),
                    'icon'          => 'dashicons-format-chat',
                    'content'       => 'callback:render_tab_chat',
                    'requires_login' => true,
                ],
                'multimedia' => [
                    'label'         => __('Multimedia', 'flavor-platform'),
                    'icon'          => 'dashicons-format-gallery',
                    'content'       => 'callback:render_tab_multimedia',
                ],
                'red-social' => [
                    'label'         => __('Red social', 'flavor-platform'),
                    'icon'          => 'dashicons-share',
                    'content'       => 'callback:render_tab_red_social',
                    'requires_login' => true,
                ],
                'recetas' => [
                    'label'         => __('Recetas', 'flavor-platform'),
                    'icon'          => 'dashicons-carrot',
                    'content'       => 'callback:render_tab_recetas',
                ],
                'biblioteca' => [
                    'label'         => __('Biblioteca', 'flavor-platform'),
                    'icon'          => 'dashicons-book',
                    'content'       => 'callback:render_tab_biblioteca',
                ],
                'podcast' => [
                    'label'         => __('Podcast', 'flavor-platform'),
                    'icon'          => 'dashicons-microphone',
                    'content'       => 'callback:render_tab_podcast',
                ],
            ],

            'archive' => [
                'columns'    => 3,
                'per_page'   => 9,
                'order_by'   => 'nombre',
                'order'      => 'ASC',
                'filterable' => ['zona', 'dia_recogida', 'estado'],
            ],

            'dashboard' => [
                'widgets' => ['ciclo_actual', 'mi_cesta', 'proxima_recogida', 'mis_grupos'],
                'actions' => [
                    'pedir'   => ['label' => __('Hacer pedido', 'flavor-platform'), 'icon' => '🛒', 'color' => 'emerald'],
                    'unirse'  => ['label' => __('Unirse a grupo', 'flavor-platform'), 'icon' => '👥', 'color' => 'blue'],
                ],
            ],

            'features' => [
                'ciclos_pedido'     => true,
                'pagos_online'      => true,
                'trueques'          => true,
                'chat_grupo'        => true,
                'notificaciones'    => true,
                'facturacion'       => true,
            ],
        ];
    }
}

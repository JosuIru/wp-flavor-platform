<?php
/**
 * Modulo de Colectivos y Asociaciones para Chat IA
 *
 * Gestion de colectivos, asociaciones, cooperativas, ONGs
 * con proyectos, asambleas y miembros.
 *
 * @package FlavorChatIA
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Modulo de Colectivos y Asociaciones
 */
class Flavor_Chat_Colectivos_Module extends Flavor_Chat_Module_Base {

    use Flavor_Module_Admin_Pages_Trait;
    use Flavor_Module_Notifications_Trait;
    use Flavor_Module_Integration_Consumer;

    /**
     * Constructor
     */
    public function __construct() {
        // Auto-registered AJAX handlers
        add_action('wp_ajax_colectivos_obtener_colectivo', [$this, 'ajax_obtener_colectivo']);
        add_action('wp_ajax_nopriv_colectivos_obtener_colectivo', [$this, 'ajax_obtener_colectivo']);

        $this->id = 'colectivos';
        $this->name = 'Colectivos y Asociaciones'; // Translation loaded on init
        $this->description = 'Gestión de colectivos, asociaciones y cooperativas con proyectos, asambleas y miembros'; // Translation loaded on init
        $this->module_role = 'base';
        $this->dashboard_parent_module = 'colectivos';
        $this->dashboard_satellite_priority = 20;
        $this->dashboard_client_contexts = ['colectivos', 'asociacion', 'gobernanza', 'comunidad'];
        $this->dashboard_admin_contexts = ['colectivos', 'gobernanza', 'admin'];

        // Principios Gailu que implementa este modulo
        $this->gailu_principios = ['gobernanza', 'cuidados'];
        $this->gailu_contribuye_a = ['cohesion', 'autonomia'];

        parent::__construct();
    }

    // =========================================================
    // Activacion y configuracion
    // =========================================================

    /**
     * {@inheritdoc}
     */
    public function can_activate() {
        global $wpdb;
        $tabla_colectivos = $wpdb->prefix . 'flavor_colectivos';

        return Flavor_Chat_Helpers::tabla_existe($tabla_colectivos);
    }

    /**
     * {@inheritdoc}
     */
    public function get_activation_error() {
        if (!$this->can_activate()) {
            return __('Las tablas de Colectivos no están creadas. Activa el módulo para crearlas automáticamente.', 'flavor-chat-ia');
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
    protected function get_default_settings() {
        return [
            'requiere_aprobacion'          => false,
            'maximo_colectivos_por_usuario' => 5,
            'permitir_proyectos'           => true,
            'permitir_asambleas'           => true,
            'tipos_permitidos'             => ['asociacion', 'cooperativa', 'ong', 'colectivo', 'plataforma'],
            'roles_miembro'                => [
                'presidente' => __('Presidente/a', 'flavor-chat-ia'),
                'secretario' => __('Secretario/a', 'flavor-chat-ia'),
                'tesorero'   => __('Tesorero/a', 'flavor-chat-ia'),
                'vocal'      => __('Vocal', 'flavor-chat-ia'),
                'miembro'    => __('Miembro', 'flavor-chat-ia'),
            ],
        ];
    }

    /**
     * Define que tipos de contenido acepta este modulo
     *
     * @return array IDs de providers aceptados
     */
    protected function get_accepted_integrations() {
        return ['multimedia', 'articulos_social', 'eventos', 'podcast'];
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
                'table'   => $wpdb->prefix . 'flavor_colectivos',
                'context' => 'normal',
            ],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function init() {
        $this->register_as_integration_consumer();
        add_action('init', [$this, 'maybe_create_pages']);
        add_action('init', [$this, 'maybe_create_tables']);
        add_action('init', [$this, 'register_shortcodes']);
        add_action('rest_api_init', [$this, 'register_rest_routes']);
        add_action('wp_enqueue_scripts', [$this, 'enqueue_frontend_assets']);
        $this->register_ajax_handlers();
        $this->cargar_frontend_controller();

        // Registrar páginas de administración
        add_action('admin_menu', [$this, 'registrar_paginas_admin']);

        $this->registrar_en_panel_unificado();
        // Cargar Dashboard Tab
        $this->inicializar_dashboard_tab();

        // Cargar Dashboard Tab para el panel del cliente
        $this->cargar_dashboard_tab();
    }

    /**
     * Carga el controlador frontend para asegurar los shortcodes modernos.
     */
    private function cargar_frontend_controller() {
        $ruta_frontend = dirname(__FILE__) . '/frontend/class-colectivos-frontend-controller.php';
        if (file_exists($ruta_frontend)) {
            require_once $ruta_frontend;

            if (class_exists('Flavor_Colectivos_Frontend_Controller')) {
                Flavor_Colectivos_Frontend_Controller::get_instance();
            }
        }
    }

    /**
     * Carga el Dashboard Tab del módulo
     */
    private function cargar_dashboard_tab() {
        $ruta_dashboard_tab = dirname(__FILE__) . '/class-colectivos-dashboard-tab.php';
        if (file_exists($ruta_dashboard_tab)) {
            require_once $ruta_dashboard_tab;
            if (class_exists('Flavor_Colectivos_Dashboard_Tab')) {
                Flavor_Colectivos_Dashboard_Tab::get_instance();
            }
        }
    }

    // =========================================================
    // Shortcodes
    // =========================================================

    /**
     * Registra los shortcodes del módulo
     */
    public function register_shortcodes() {
        add_shortcode('colectivos_listar', [$this, 'shortcode_listar']);
        add_shortcode('colectivos_crear', [$this, 'shortcode_crear']);
        add_shortcode('colectivos_detalle', [$this, 'shortcode_detalle']);
        add_shortcode('colectivos_mis_colectivos', [$this, 'shortcode_mis_colectivos']);
        add_shortcode('colectivos_proyectos', [$this, 'shortcode_proyectos']);
        add_shortcode('colectivos_asambleas', [$this, 'shortcode_asambleas']);
        add_shortcode('colectivos_mi_actividad', [$this, 'shortcode_mi_actividad']);
    }

    /**
     * Shortcode: Listado de colectivos
     */
    public function shortcode_listar($atributos) {
        $atributos = shortcode_atts([
            'tipo'     => '',
            'sector'   => '',
            'columnas' => 3,
            'limite'   => 12,
        ], $atributos, 'colectivos_listar');

        $resultado = $this->action_listar_colectivos([
            'tipo'   => $atributos['tipo'],
            'sector' => $atributos['sector'],
            'limite' => absint($atributos['limite']),
        ]);

        $colectivos = $resultado['success'] ? $resultado['colectivos'] : [];
        $categorias = $this->get_etiquetas_tipo();

        ob_start();
        include dirname(__FILE__) . '/views/listado-colectivos.php';
        return ob_get_clean();
    }

    /**
     * Shortcode: Crear colectivo
     */
    public function shortcode_crear($atributos) {
        $atributos = shortcode_atts([], $atributos, 'colectivos_crear');

        $identificador_usuario = get_current_user_id();
        $tipos_disponibles = $this->get_etiquetas_tipo();
        $sectores_disponibles = $this->get_sectores_disponibles();

        ob_start();
        include dirname(__FILE__) . '/views/crear-colectivo.php';
        return ob_get_clean();
    }

    /**
     * Shortcode: Detalle de colectivo
     */
    public function shortcode_detalle($atributos) {
        $atributos = shortcode_atts([
            'id' => 0,
        ], $atributos, 'colectivos_detalle');

        $colectivo_id = absint($atributos['id']) ?: absint($_GET['colectivo'] ?? 0);

        if (!$colectivo_id) {
            return '<p class="flavor-col-error">' . esc_html__('Colectivo no especificado.', 'flavor-chat-ia') . '</p>';
        }

        $resultado = $this->action_ver_colectivo(['colectivo_id' => $colectivo_id]);

        if (!$resultado['success']) {
            return '<p class="flavor-col-error">' . esc_html($resultado['error']) . '</p>';
        }

        $colectivo = $resultado['colectivo'];
        $miembros = $resultado['miembros'];
        $identificador_usuario = get_current_user_id();
        $es_miembro = $this->es_miembro_activo($colectivo_id, $identificador_usuario);
        $rol_usuario = $this->obtener_rol_miembro($colectivo_id, $identificador_usuario);

        // Obtener proyectos y asambleas
        $proyectos_resultado = $this->action_listar_proyectos(['colectivo_id' => $colectivo_id]);
        $proyectos = $proyectos_resultado['success'] ? $proyectos_resultado['proyectos'] : [];

        $asambleas_resultado = $this->action_ver_asambleas(['colectivo_id' => $colectivo_id]);
        $asambleas = $asambleas_resultado['success'] ? $asambleas_resultado['asambleas'] : [];

        ob_start();
        include dirname(__FILE__) . '/views/detalle-colectivo.php';
        return ob_get_clean();
    }

    /**
     * Shortcode: Mis colectivos
     */
    public function shortcode_mis_colectivos($atributos) {
        $atributos = shortcode_atts([
            'columnas' => 2,
        ], $atributos, 'colectivos_mis_colectivos');

        $identificador_usuario = get_current_user_id();

        if (!$identificador_usuario) {
            return '<p class="flavor-col-login-required">' .
                sprintf(
                    esc_html__('Debes %siniciar sesión%s para ver tus colectivos.', 'flavor-chat-ia'),
                    '<a href="' . esc_url(wp_login_url(flavor_current_request_url())) . '">',
                    '</a>'
                ) . '</p>';
        }

        $resultado = $this->action_mis_colectivos([]);
        $colectivos = $resultado['success'] ? $resultado['colectivos'] : [];
        $etiquetas_rol = $this->get_default_settings()['roles_miembro'];

        ob_start();
        include dirname(__FILE__) . '/views/mis-colectivos.php';
        return ob_get_clean();
    }

    /**
     * Shortcode: Proyectos de colectivo
     */
    public function shortcode_proyectos($atributos) {
        $atributos = shortcode_atts([
            'colectivo_id' => 0,
            'estado'       => '',
            'limite'       => 10,
        ], $atributos, 'colectivos_proyectos');

        $colectivo_id = absint($atributos['colectivo_id']) ?: absint($_GET['colectivo'] ?? 0);

        if (!$colectivo_id) {
            return '<p class="flavor-col-error">' . esc_html__('Colectivo no especificado.', 'flavor-chat-ia') . '</p>';
        }

        $resultado = $this->action_listar_proyectos([
            'colectivo_id' => $colectivo_id,
            'estado'       => $atributos['estado'],
        ]);

        $proyectos = $resultado['success'] ? $resultado['proyectos'] : [];
        $identificador_usuario = get_current_user_id();
        $es_miembro = $this->es_miembro_activo($colectivo_id, $identificador_usuario);

        ob_start();
        include dirname(__FILE__) . '/views/proyectos.php';
        return ob_get_clean();
    }

    /**
     * Shortcode: Asambleas de colectivo
     */
    public function shortcode_asambleas($atributos) {
        $atributos = shortcode_atts([
            'colectivo_id' => 0,
            'estado'       => '',
            'limite'       => 10,
        ], $atributos, 'colectivos_asambleas');

        $colectivo_id = absint($atributos['colectivo_id']) ?: absint($_GET['colectivo'] ?? 0);

        if (!$colectivo_id) {
            return '<p class="flavor-col-error">' . esc_html__('Colectivo no especificado.', 'flavor-chat-ia') . '</p>';
        }

        $resultado = $this->action_ver_asambleas([
            'colectivo_id' => $colectivo_id,
            'estado'       => $atributos['estado'],
        ]);

        $asambleas = $resultado['success'] ? $resultado['asambleas'] : [];
        $identificador_usuario = get_current_user_id();
        $rol_usuario = $this->obtener_rol_miembro($colectivo_id, $identificador_usuario);
        $puede_convocar = in_array($rol_usuario, ['presidente', 'secretario'], true);

        ob_start();
        include dirname(__FILE__) . '/views/asambleas.php';
        return ob_get_clean();
    }

    /**
     * Shortcode: Mi actividad en colectivos
     * Muestra resumen de actividad reciente del usuario en sus colectivos
     */
    public function shortcode_mi_actividad($atributos) {
        $atributos = shortcode_atts([
            'limite' => 5,
        ], $atributos, 'colectivos_mi_actividad');

        $identificador_usuario = get_current_user_id();

        if (!$identificador_usuario) {
            return '<p class="flavor-col-login-required">' .
                esc_html__('Inicia sesión para ver tu actividad.', 'flavor-chat-ia') . '</p>';
        }

        global $wpdb;
        $tabla_miembros = $wpdb->prefix . 'flavor_colectivos_miembros';
        $tabla_colectivos = $wpdb->prefix . 'flavor_colectivos';
        $tabla_proyectos = $wpdb->prefix . 'flavor_colectivos_proyectos';
        $tabla_asambleas = $wpdb->prefix . 'flavor_colectivos_asambleas';

        // Obtener colectivos del usuario
        $mis_colectivos = $wpdb->get_results($wpdb->prepare(
            "SELECT c.id, c.nombre, c.tipo, m.rol, m.fecha_alta
             FROM $tabla_miembros m
             JOIN $tabla_colectivos c ON m.colectivo_id = c.id
             WHERE m.usuario_id = %d AND m.estado = 'activo'
             ORDER BY m.fecha_alta DESC
             LIMIT 5",
            $identificador_usuario
        ));

        // Obtener próximas asambleas de mis colectivos
        $colectivo_ids = wp_list_pluck($mis_colectivos, 'id');
        $proximas_asambleas = [];
        if (!empty($colectivo_ids)) {
            $placeholders = implode(',', array_fill(0, count($colectivo_ids), '%d'));
            $proximas_asambleas = $wpdb->get_results($wpdb->prepare(
                "SELECT a.*, c.nombre as colectivo_nombre
                 FROM $tabla_asambleas a
                 JOIN $tabla_colectivos c ON a.colectivo_id = c.id
                 WHERE a.colectivo_id IN ($placeholders)
                 AND a.fecha >= CURDATE()
                 AND a.estado = 'convocada'
                 ORDER BY a.fecha ASC
                 LIMIT 3",
                ...$colectivo_ids
            ));
        }

        // Obtener proyectos activos
        $proyectos_activos = [];
        if (!empty($colectivo_ids)) {
            $placeholders = implode(',', array_fill(0, count($colectivo_ids), '%d'));
            $proyectos_activos = $wpdb->get_results($wpdb->prepare(
                "SELECT p.*, c.nombre as colectivo_nombre
                 FROM $tabla_proyectos p
                 JOIN $tabla_colectivos c ON p.colectivo_id = c.id
                 WHERE p.colectivo_id IN ($placeholders)
                 AND p.estado = 'en_curso'
                 ORDER BY p.fecha_actualizacion DESC
                 LIMIT 3",
                ...$colectivo_ids
            ));
        }

        $roles_etiquetas = $this->get_default_settings()['roles_miembro'];

        ob_start();
        ?>
        <div class="flavor-colectivos-mi-actividad">
            <?php if (empty($mis_colectivos)) : ?>
                <p class="flavor-col-empty"><?php esc_html_e('No perteneces a ningún colectivo todavía.', 'flavor-chat-ia'); ?></p>
            <?php else : ?>
                <div class="flavor-col-actividad-grid">
                    <!-- Mis colectivos -->
                    <div class="flavor-col-actividad-seccion">
                        <h4><?php esc_html_e('Mis Colectivos', 'flavor-chat-ia'); ?></h4>
                        <ul class="flavor-col-lista-compacta">
                            <?php foreach ($mis_colectivos as $colectivo) : ?>
                                <li>
                                    <a href="<?php echo esc_url(home_url('/mi-portal/colectivos/?colectivo=' . $colectivo->id)); ?>">
                                        <?php echo esc_html($colectivo->nombre); ?>
                                    </a>
                                    <span class="flavor-col-rol-badge"><?php echo esc_html($roles_etiquetas[$colectivo->rol] ?? $colectivo->rol); ?></span>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                    </div>

                    <?php if (!empty($proximas_asambleas)) : ?>
                    <div class="flavor-col-actividad-seccion">
                        <h4><?php esc_html_e('Próximas Asambleas', 'flavor-chat-ia'); ?></h4>
                        <ul class="flavor-col-lista-compacta">
                            <?php foreach ($proximas_asambleas as $asamblea) : ?>
                                <li>
                                    <span class="flavor-col-fecha"><?php echo esc_html(date_i18n('d M', strtotime($asamblea->fecha))); ?></span>
                                    <?php echo esc_html($asamblea->titulo); ?>
                                    <small><?php echo esc_html($asamblea->colectivo_nombre); ?></small>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                    <?php endif; ?>

                    <?php if (!empty($proyectos_activos)) : ?>
                    <div class="flavor-col-actividad-seccion">
                        <h4><?php esc_html_e('Proyectos Activos', 'flavor-chat-ia'); ?></h4>
                        <ul class="flavor-col-lista-compacta">
                            <?php foreach ($proyectos_activos as $proyecto) : ?>
                                <li>
                                    <?php echo esc_html($proyecto->nombre); ?>
                                    <small><?php echo esc_html($proyecto->colectivo_nombre); ?></small>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
        </div>
        <?php
        return ob_get_clean();
    }

    // =========================================================
    // AJAX Handlers
    // =========================================================

    /**
     * Registra los handlers AJAX
     */
    public function register_ajax_handlers() {
        // Handlers con autenticación
        add_action('wp_ajax_colectivos_crear', [$this, 'ajax_crear']);
        add_action('wp_ajax_colectivos_unirse', [$this, 'ajax_unirse']);
        add_action('wp_ajax_colectivos_abandonar', [$this, 'ajax_abandonar']);
        add_action('wp_ajax_colectivos_crear_proyecto', [$this, 'ajax_crear_proyecto']);
        add_action('wp_ajax_colectivos_actualizar_proyecto', [$this, 'ajax_actualizar_proyecto']);
        add_action('wp_ajax_colectivos_convocar_asamblea', [$this, 'ajax_convocar_asamblea']);
        add_action('wp_ajax_colectivos_confirmar_asistencia', [$this, 'ajax_confirmar_asistencia']);
        add_action('wp_ajax_colectivos_aprobar_miembro', [$this, 'ajax_aprobar_miembro']);

        // Handlers públicos
        add_action('wp_ajax_colectivos_obtener', [$this, 'ajax_obtener_colectivo']);
        add_action('wp_ajax_nopriv_colectivos_obtener', [$this, 'ajax_obtener_colectivo']);
        add_action('wp_ajax_colectivos_listar', [$this, 'ajax_listar']);
        add_action('wp_ajax_nopriv_colectivos_listar', [$this, 'ajax_listar']);
    }

    /**
     * AJAX: Crear colectivo
     */
    public function ajax_crear() {
        check_ajax_referer('flavor_colectivos_nonce', 'nonce');

        $resultado = $this->action_crear_colectivo([
            'nombre'         => sanitize_text_field($_POST['nombre'] ?? ''),
            'descripcion'    => sanitize_textarea_field($_POST['descripcion'] ?? ''),
            'tipo'           => sanitize_text_field($_POST['tipo'] ?? 'colectivo'),
            'sector'         => sanitize_text_field($_POST['sector'] ?? ''),
            'email_contacto' => sanitize_email($_POST['email_contacto'] ?? ''),
            'telefono'       => sanitize_text_field($_POST['telefono'] ?? ''),
            'direccion'      => sanitize_textarea_field($_POST['direccion'] ?? ''),
            'web'            => esc_url_raw($_POST['web'] ?? ''),
        ]);

        wp_send_json($resultado);
    }

    /**
     * AJAX: Unirse a colectivo
     */
    public function ajax_unirse() {
        check_ajax_referer('flavor_colectivos_nonce', 'nonce');

        $resultado = $this->action_unirse([
            'colectivo_id' => absint($_POST['colectivo_id'] ?? 0),
        ]);

        wp_send_json($resultado);
    }

    /**
     * AJAX: Abandonar colectivo
     */
    public function ajax_abandonar() {
        check_ajax_referer('flavor_colectivos_nonce', 'nonce');

        $identificador_usuario = get_current_user_id();
        $colectivo_id = absint($_POST['colectivo_id'] ?? 0);

        if (!$identificador_usuario || !$colectivo_id) {
            wp_send_json(['success' => false, 'error' => __('Datos inválidos.', 'flavor-chat-ia')]);
        }

        global $wpdb;
        $tabla_colectivos_miembros = $wpdb->prefix . 'flavor_colectivos_miembros';

        // Verificar que no es el presidente (no puede abandonar)
        $rol_actual = $this->obtener_rol_miembro($colectivo_id, $identificador_usuario);
        if ($rol_actual === 'presidente') {
            wp_send_json([
                'success' => false,
                'error'   => __('El presidente no puede abandonar el colectivo. Primero transfiere el rol a otro miembro.', 'flavor-chat-ia'),
            ]);
        }

        $wpdb->update(
            $tabla_colectivos_miembros,
            [
                'estado'     => 'baja',
                'fecha_baja' => current_time('mysql'),
            ],
            [
                'colectivo_id' => $colectivo_id,
                'user_id'      => $identificador_usuario,
            ],
            ['%s', '%s'],
            ['%d', '%d']
        );

        // Actualizar contador
        $tabla_colectivos = $wpdb->prefix . 'flavor_colectivos';
        $wpdb->query($wpdb->prepare(
            "UPDATE $tabla_colectivos SET miembros_count = GREATEST(0, miembros_count - 1) WHERE id = %d",
            $colectivo_id
        ));

        wp_send_json([
            'success' => true,
            'mensaje' => __('Has abandonado el colectivo.', 'flavor-chat-ia'),
        ]);
    }

    /**
     * AJAX: Crear proyecto
     */
    public function ajax_crear_proyecto() {
        check_ajax_referer('flavor_colectivos_nonce', 'nonce');

        $resultado = $this->action_crear_proyecto([
            'colectivo_id' => absint($_POST['colectivo_id'] ?? 0),
            'titulo'       => sanitize_text_field($_POST['titulo'] ?? ''),
            'descripcion'  => sanitize_textarea_field($_POST['descripcion'] ?? ''),
            'presupuesto'  => floatval($_POST['presupuesto'] ?? 0),
            'fecha_inicio' => sanitize_text_field($_POST['fecha_inicio'] ?? ''),
            'fecha_fin'    => sanitize_text_field($_POST['fecha_fin'] ?? ''),
        ]);

        wp_send_json($resultado);
    }

    /**
     * AJAX: Actualizar proyecto
     */
    public function ajax_actualizar_proyecto() {
        check_ajax_referer('flavor_colectivos_nonce', 'nonce');

        $proyecto_id = absint($_POST['proyecto_id'] ?? 0);
        $colectivo_id = absint($_POST['colectivo_id'] ?? 0);
        $identificador_usuario = get_current_user_id();

        if (!$proyecto_id || !$colectivo_id) {
            wp_send_json(['success' => false, 'error' => __('Datos inválidos.', 'flavor-chat-ia')]);
        }

        // Verificar permisos
        if (!$this->es_miembro_activo($colectivo_id, $identificador_usuario)) {
            wp_send_json(['success' => false, 'error' => __('No tienes permisos.', 'flavor-chat-ia')]);
        }

        global $wpdb;
        $tabla_colectivos_proyectos = $wpdb->prefix . 'flavor_colectivos_proyectos';

        $campos_actualizar = [];
        $formatos = [];

        if (isset($_POST['estado'])) {
            $estados_validos = ['planificado', 'en_curso', 'completado', 'cancelado'];
            $estado_nuevo = sanitize_text_field($_POST['estado']);
            if (in_array($estado_nuevo, $estados_validos, true)) {
                $campos_actualizar['estado'] = $estado_nuevo;
                $formatos[] = '%s';
            }
        }

        if (isset($_POST['progreso'])) {
            $campos_actualizar['progreso'] = min(100, max(0, absint($_POST['progreso'])));
            $formatos[] = '%d';
        }

        if (empty($campos_actualizar)) {
            wp_send_json(['success' => false, 'error' => __('No hay campos para actualizar.', 'flavor-chat-ia')]);
        }

        $wpdb->update(
            $tabla_colectivos_proyectos,
            $campos_actualizar,
            ['id' => $proyecto_id, 'colectivo_id' => $colectivo_id],
            $formatos,
            ['%d', '%d']
        );

        wp_send_json([
            'success' => true,
            'mensaje' => __('Proyecto actualizado.', 'flavor-chat-ia'),
        ]);
    }

    /**
     * AJAX: Convocar asamblea
     */
    public function ajax_convocar_asamblea() {
        check_ajax_referer('flavor_colectivos_nonce', 'nonce');

        $resultado = $this->action_convocar_asamblea([
            'colectivo_id' => absint($_POST['colectivo_id'] ?? 0),
            'titulo'       => sanitize_text_field($_POST['titulo'] ?? ''),
            'descripcion'  => sanitize_textarea_field($_POST['descripcion'] ?? ''),
            'tipo'         => sanitize_text_field($_POST['tipo'] ?? 'ordinaria'),
            'fecha'        => sanitize_text_field($_POST['fecha'] ?? ''),
            'lugar'        => sanitize_text_field($_POST['lugar'] ?? ''),
            'orden_del_dia'=> sanitize_textarea_field($_POST['orden_del_dia'] ?? ''),
        ]);

        wp_send_json($resultado);
    }

    /**
     * AJAX: Confirmar asistencia a asamblea
     */
    public function ajax_confirmar_asistencia() {
        check_ajax_referer('flavor_colectivos_nonce', 'nonce');

        $asamblea_id = absint($_POST['asamblea_id'] ?? 0);
        $identificador_usuario = get_current_user_id();

        if (!$asamblea_id || !$identificador_usuario) {
            wp_send_json(['success' => false, 'error' => __('Datos inválidos.', 'flavor-chat-ia')]);
        }

        global $wpdb;
        $tabla_colectivos_asambleas = $wpdb->prefix . 'flavor_colectivos_asambleas';

        $asamblea = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM $tabla_colectivos_asambleas WHERE id = %d",
            $asamblea_id
        ));

        if (!$asamblea || $asamblea->estado !== 'convocada') {
            wp_send_json(['success' => false, 'error' => __('Asamblea no disponible.', 'flavor-chat-ia')]);
        }

        // Verificar membresía
        if (!$this->es_miembro_activo($asamblea->colectivo_id, $identificador_usuario)) {
            wp_send_json(['success' => false, 'error' => __('No eres miembro de este colectivo.', 'flavor-chat-ia')]);
        }

        $asistentes = json_decode($asamblea->asistentes, true) ?: [];

        if (in_array($identificador_usuario, $asistentes, true)) {
            wp_send_json(['success' => false, 'error' => __('Ya confirmaste tu asistencia.', 'flavor-chat-ia')]);
        }

        $asistentes[] = $identificador_usuario;

        $wpdb->update(
            $tabla_colectivos_asambleas,
            ['asistentes' => wp_json_encode($asistentes)],
            ['id' => $asamblea_id],
            ['%s'],
            ['%d']
        );

        wp_send_json([
            'success'        => true,
            'mensaje'        => __('Asistencia confirmada.', 'flavor-chat-ia'),
            'total_asistentes' => count($asistentes),
        ]);
    }

    /**
     * AJAX: Aprobar miembro pendiente
     */
    public function ajax_aprobar_miembro() {
        check_ajax_referer('flavor_colectivos_nonce', 'nonce');

        $membresia_id = absint($_POST['membresia_id'] ?? 0);
        $colectivo_id = absint($_POST['colectivo_id'] ?? 0);
        $accion = sanitize_text_field($_POST['accion_aprobar'] ?? 'aprobar');
        $identificador_usuario = get_current_user_id();

        if (!$membresia_id || !$colectivo_id) {
            wp_send_json(['success' => false, 'error' => __('Datos inválidos.', 'flavor-chat-ia')]);
        }

        // Solo presidente o secretario pueden aprobar
        $rol_actual = $this->obtener_rol_miembro($colectivo_id, $identificador_usuario);
        if (!in_array($rol_actual, ['presidente', 'secretario'], true)) {
            wp_send_json(['success' => false, 'error' => __('No tienes permisos para aprobar miembros.', 'flavor-chat-ia')]);
        }

        global $wpdb;
        $tabla_colectivos_miembros = $wpdb->prefix . 'flavor_colectivos_miembros';

        if ($accion === 'aprobar') {
            $wpdb->update(
                $tabla_colectivos_miembros,
                ['estado' => 'activo'],
                ['id' => $membresia_id, 'colectivo_id' => $colectivo_id, 'estado' => 'pendiente'],
                ['%s'],
                ['%d', '%d', '%s']
            );

            // Actualizar contador
            $tabla_colectivos = $wpdb->prefix . 'flavor_colectivos';
            $wpdb->query($wpdb->prepare(
                "UPDATE $tabla_colectivos SET miembros_count = miembros_count + 1 WHERE id = %d",
                $colectivo_id
            ));

            wp_send_json(['success' => true, 'mensaje' => __('Miembro aprobado.', 'flavor-chat-ia')]);
        } else {
            $wpdb->delete(
                $tabla_colectivos_miembros,
                ['id' => $membresia_id, 'colectivo_id' => $colectivo_id, 'estado' => 'pendiente'],
                ['%d', '%d', '%s']
            );

            wp_send_json(['success' => true, 'mensaje' => __('Solicitud rechazada.', 'flavor-chat-ia')]);
        }
    }

    /**
     * AJAX: Obtener colectivo
     */
    public function ajax_obtener_colectivo() {
        $colectivo_id = absint($_GET['colectivo_id'] ?? $_POST['colectivo_id'] ?? 0);

        $resultado = $this->action_ver_colectivo(['colectivo_id' => $colectivo_id]);

        wp_send_json($resultado);
    }

    /**
     * AJAX: Listar colectivos
     */
    public function ajax_listar() {
        $resultado = $this->action_listar_colectivos([
            'tipo'     => sanitize_text_field($_GET['tipo'] ?? $_POST['tipo'] ?? ''),
            'sector'   => sanitize_text_field($_GET['sector'] ?? $_POST['sector'] ?? ''),
            'busqueda' => sanitize_text_field($_GET['busqueda'] ?? $_POST['busqueda'] ?? ''),
            'limite'   => absint($_GET['limite'] ?? $_POST['limite'] ?? 20),
        ]);

        wp_send_json($resultado);
    }

    // =========================================================
    // Assets Frontend
    // =========================================================

    /**
     * Encola assets del frontend
     */
    public function enqueue_frontend_assets() {
        if (!$this->should_load_assets()) {
            return;
        }

        $directorio_plugin = plugin_dir_url(dirname(dirname(dirname(__FILE__))));

        wp_enqueue_style(
            'flavor-colectivos',
            $directorio_plugin . 'modules/colectivos/assets/css/colectivos.css',
            [],
            FLAVOR_CHAT_IA_VERSION
        );

        wp_enqueue_script(
            'flavor-colectivos',
            $directorio_plugin . 'modules/colectivos/assets/js/colectivos.js',
            ['jquery'],
            FLAVOR_CHAT_IA_VERSION,
            true
        );

        wp_localize_script('flavor-colectivos', 'flavorColectivosConfig', [
            'ajaxUrl' => admin_url('admin-ajax.php'),
            'nonce'   => wp_create_nonce('flavor_colectivos_nonce'),
            'strings' => [
                'confirmUnirse'    => __('¿Deseas unirte a este colectivo?', 'flavor-chat-ia'),
                'confirmAbandonar' => __('¿Estás seguro de que quieres abandonar este colectivo?', 'flavor-chat-ia'),
                'confirmAsistencia'=> __('¿Confirmas tu asistencia a esta asamblea?', 'flavor-chat-ia'),
                'errorConexion'    => __('Error de conexión. Inténtalo de nuevo.', 'flavor-chat-ia'),
            ],
        ]);
    }

    /**
     * Determina si se deben cargar los assets
     */
    private function should_load_assets() {
        global $post;

        if (!$post) {
            return false;
        }

        // Cargar en página de colectivos
        if (strpos($post->post_name, 'colectivo') !== false) {
            return true;
        }

        // Cargar si hay shortcodes del módulo
        $shortcodes_modulo = ['colectivos_listar', 'colectivos_crear', 'colectivos_detalle', 'colectivos_mis_colectivos', 'colectivos_proyectos', 'colectivos_asambleas'];
        foreach ($shortcodes_modulo as $shortcode) {
            if (has_shortcode($post->post_content, $shortcode)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Obtiene los sectores disponibles
     */
    private function get_sectores_disponibles() {
        return [
            'cultura'         => __('Cultura y Arte', 'flavor-chat-ia'),
            'medioambiente'   => __('Medio Ambiente', 'flavor-chat-ia'),
            'educacion'       => __('Educación', 'flavor-chat-ia'),
            'salud'           => __('Salud', 'flavor-chat-ia'),
            'derechos'        => __('Derechos Humanos', 'flavor-chat-ia'),
            'economia_social' => __('Economía Social', 'flavor-chat-ia'),
            'tecnologia'      => __('Tecnología', 'flavor-chat-ia'),
            'deportes'        => __('Deportes', 'flavor-chat-ia'),
            'vecinal'         => __('Vecinal', 'flavor-chat-ia'),
            'otro'            => __('Otro', 'flavor-chat-ia'),
        ];
    }

    // =========================================================
    // REST API
    // =========================================================

    /**
     * Registra las rutas de la REST API para el modulo de colectivos
     */
    public function register_rest_routes() {
        $namespace = 'flavor/v1';

        // GET /flavor/v1/colectivos - Listar colectivos
        register_rest_route($namespace, '/colectivos', [
            'methods'             => \WP_REST_Server::READABLE,
            'callback'            => [$this, 'api_listar_colectivos'],
            'permission_callback' => [$this, 'api_verificar_lectura_publica'],
            'args'                => [
                'tipo' => [
                    'type'              => 'string',
                    'enum'              => ['asociacion', 'cooperativa', 'ong', 'colectivo', 'plataforma'],
                    'sanitize_callback' => 'sanitize_text_field',
                ],
                'sector' => [
                    'type'              => 'string',
                    'sanitize_callback' => 'sanitize_text_field',
                ],
                'busqueda' => [
                    'type'              => 'string',
                    'sanitize_callback' => 'sanitize_text_field',
                ],
                'limite' => [
                    'type'              => 'integer',
                    'default'           => 20,
                    'sanitize_callback' => 'absint',
                ],
            ],
        ]);

        // GET /flavor/v1/colectivos/{id} - Obtener un colectivo
        register_rest_route($namespace, '/colectivos/(?P<id>\d+)', [
            'methods'             => \WP_REST_Server::READABLE,
            'callback'            => [$this, 'api_obtener_colectivo'],
            'permission_callback' => [$this, 'api_verificar_lectura_publica'],
            'args'                => [
                'id' => [
                    'required'          => true,
                    'type'              => 'integer',
                    'sanitize_callback' => 'absint',
                    'validate_callback' => function ($valor) {
                        return is_numeric($valor) && $valor > 0;
                    },
                ],
            ],
        ]);

        // POST /flavor/v1/colectivos/{id}/unirse - Unirse a colectivo
        register_rest_route($namespace, '/colectivos/(?P<id>\d+)/unirse', [
            'methods'             => \WP_REST_Server::CREATABLE,
            'callback'            => [$this, 'api_unirse_colectivo'],
            'permission_callback' => [$this, 'api_verificar_usuario_autenticado'],
            'args'                => [
                'id' => [
                    'required'          => true,
                    'type'              => 'integer',
                    'sanitize_callback' => 'absint',
                    'validate_callback' => function ($valor) {
                        return is_numeric($valor) && $valor > 0;
                    },
                ],
            ],
        ]);

        // GET /flavor/v1/colectivos/{id}/miembros - Ver miembros de un colectivo
        register_rest_route($namespace, '/colectivos/(?P<id>\d+)/miembros', [
            'methods'             => \WP_REST_Server::READABLE,
            'callback'            => [$this, 'api_obtener_miembros'],
            'permission_callback' => [$this, 'api_verificar_lectura_miembros'],
            'args'                => [
                'id' => [
                    'required'          => true,
                    'type'              => 'integer',
                    'sanitize_callback' => 'absint',
                    'validate_callback' => function ($valor) {
                        return is_numeric($valor) && $valor > 0;
                    },
                ],
            ],
        ]);

        // GET /flavor/v1/colectivos/mis-colectivos - Colectivos del usuario
        register_rest_route($namespace, '/colectivos/mis-colectivos', [
            'methods'             => \WP_REST_Server::READABLE,
            'callback'            => [$this, 'api_mis_colectivos'],
            'permission_callback' => [$this, 'api_verificar_usuario_autenticado'],
            'args'                => [
                'rol' => [
                    'type'              => 'string',
                    'enum'              => ['presidente', 'secretario', 'tesorero', 'vocal', 'miembro'],
                    'sanitize_callback' => 'sanitize_text_field',
                ],
                'estado' => [
                    'type'              => 'string',
                    'enum'              => ['activo', 'pendiente'],
                    'default'           => 'activo',
                    'sanitize_callback' => 'sanitize_text_field',
                ],
            ],
        ]);
    }

    /**
     * Verifica si el usuario esta autenticado para la REST API
     *
     * @return bool|WP_Error
     */
    public function api_verificar_usuario_autenticado() {
        if (!is_user_logged_in()) {
            return new \WP_Error(
                'rest_not_logged_in',
                __('Debes iniciar sesion para realizar esta accion.', 'flavor-chat-ia'),
                ['status' => 401]
            );
        }
        return true;
    }

    /**
     * Permite lecturas públicas del catálogo de colectivos.
     *
     * @return bool
     */
    public function api_verificar_lectura_publica() {
        return true;
    }

    /**
     * Restringe la lectura del listado de miembros a usuarios autenticados.
     *
     * @return bool|\WP_Error
     */
    public function api_verificar_lectura_miembros() {
        if (!is_user_logged_in()) {
            return new \WP_Error(
                'rest_not_logged_in',
                __('Debes iniciar sesión para ver los miembros del colectivo.', 'flavor-chat-ia'),
                ['status' => 401]
            );
        }

        return true;
    }

    /**
     * API: Listar colectivos
     *
     * @param WP_REST_Request $request Objeto de solicitud REST.
     * @return WP_REST_Response|WP_Error
     */
    public function api_listar_colectivos($request) {
        $parametros = [
            'tipo'     => $request->get_param('tipo'),
            'sector'   => $request->get_param('sector'),
            'busqueda' => $request->get_param('busqueda'),
            'limite'   => $request->get_param('limite'),
        ];

        $resultado = $this->action_listar_colectivos($parametros);

        if (!$resultado['success']) {
            return new \WP_Error(
                'colectivos_error',
                $resultado['error'],
                ['status' => 400]
            );
        }

        return rest_ensure_response($resultado);
    }

    /**
     * API: Obtener un colectivo especifico
     *
     * @param WP_REST_Request $request Objeto de solicitud REST.
     * @return WP_REST_Response|WP_Error
     */
    public function api_obtener_colectivo($request) {
        $colectivo_id = $request->get_param('id');

        $parametros = [
            'colectivo_id' => $colectivo_id,
        ];

        $resultado = $this->action_ver_colectivo($parametros);

        if (!$resultado['success']) {
            return new \WP_Error(
                'colectivo_no_encontrado',
                $resultado['error'],
                ['status' => 404]
            );
        }

        if (!is_user_logged_in() && !empty($resultado['miembros'])) {
            $resultado['miembros'] = array_map(function ($miembro) {
                unset($miembro['email']);
                return $miembro;
            }, $resultado['miembros']);
        }

        return rest_ensure_response($resultado);
    }

    /**
     * API: Unirse a un colectivo
     *
     * @param WP_REST_Request $request Objeto de solicitud REST.
     * @return WP_REST_Response|WP_Error
     */
    public function api_unirse_colectivo($request) {
        $colectivo_id = $request->get_param('id');

        $parametros = [
            'colectivo_id' => $colectivo_id,
        ];

        $resultado = $this->action_unirse($parametros);

        if (!$resultado['success']) {
            $codigo_estado = 400;

            // Determinar codigo de estado apropiado
            if (strpos($resultado['error'], 'ya eres miembro') !== false) {
                $codigo_estado = 409; // Conflict
            } elseif (strpos($resultado['error'], 'solicitud pendiente') !== false) {
                $codigo_estado = 409; // Conflict
            } elseif (strpos($resultado['error'], 'no encontrado') !== false) {
                $codigo_estado = 404;
            }

            return new \WP_Error(
                'unirse_error',
                $resultado['error'],
                ['status' => $codigo_estado]
            );
        }

        return rest_ensure_response($resultado);
    }

    /**
     * API: Obtener miembros de un colectivo
     *
     * @param WP_REST_Request $request Objeto de solicitud REST.
     * @return WP_REST_Response|WP_Error
     */
    public function api_obtener_miembros($request) {
        $colectivo_id = $request->get_param('id');

        $parametros = [
            'colectivo_id' => $colectivo_id,
        ];

        // Usamos action_ver_colectivo que ya incluye miembros
        $resultado = $this->action_ver_colectivo($parametros);

        if (!$resultado['success']) {
            return new \WP_Error(
                'colectivo_no_encontrado',
                $resultado['error'],
                ['status' => 404]
            );
        }

        // Retornar solo los miembros
        return rest_ensure_response([
            'success'       => true,
            'colectivo_id'  => $colectivo_id,
            'nombre'        => $resultado['colectivo']['nombre'],
            'total'         => count($resultado['miembros']),
            'miembros'      => $resultado['miembros'],
        ]);
    }

    /**
     * API: Obtener colectivos del usuario autenticado
     *
     * @param WP_REST_Request $request Objeto de solicitud REST.
     * @return WP_REST_Response|WP_Error
     */
    public function api_mis_colectivos($request) {
        $parametros = [
            'rol'    => $request->get_param('rol'),
            'estado' => $request->get_param('estado'),
        ];

        $resultado = $this->action_mis_colectivos($parametros);

        if (!$resultado['success']) {
            return new \WP_Error(
                'mis_colectivos_error',
                $resultado['error'],
                ['status' => 400]
            );
        }

        return rest_ensure_response($resultado);
    }

    /**
     * Configuracion de paginas de administracion para el panel unificado
     *
     * @return array
     */
    protected function get_admin_config() {
        return [
            'id'         => 'colectivos',
            'label'      => __('Colectivos', 'flavor-chat-ia'),
            'icon'       => 'dashicons-networking',
            'capability' => 'manage_options',
            'categoria'  => 'comunidad',
            'paginas'    => [
                [
                    'slug'     => 'flavor-colectivos-dashboard',
                    'titulo'   => __('Dashboard', 'flavor-chat-ia'),
                    'callback' => [$this, 'render_pagina_dashboard'],
                ],
                [
                    'slug'     => 'flavor-colectivos-listado',
                    'titulo'   => __('Colectivos', 'flavor-chat-ia'),
                    'callback' => [$this, 'render_admin_colectivos'],
                ],
                [
                    'slug'     => 'flavor-colectivos-miembros',
                    'titulo'   => __('Miembros', 'flavor-chat-ia'),
                    'callback' => [$this, 'render_admin_miembros'],
                    'badge'    => [$this, 'contar_solicitudes_pendientes'],
                ],
                [
                    'slug'     => 'colectivos-config',
                    'titulo'   => __('Configuración', 'flavor-chat-ia'),
                    'callback' => [$this, 'render_admin_configuracion'],
                ],
            ],
            'dashboard_widget' => [$this, 'render_dashboard_widget'],
            'estadisticas'     => [$this, 'get_estadisticas_globales'],
        ];
    }

    /**
     * Renderiza el dashboard de administracion del modulo
     */
    public function render_admin_dashboard() {
        $this->render_page_header(
            __('Dashboard de Colectivos', 'flavor-chat-ia'),
            [
                [
                    'label' => __('Nuevo Colectivo', 'flavor-chat-ia'),
                    'url'   => $this->admin_page_url('flavor-colectivos-listado') . '&action=new',
                    'class' => 'button-primary',
                ],
            ]
        );

        $estadisticas = $this->get_estadisticas_globales();
        $legacy_view = dirname(__FILE__) . '/views/admin-dashboard.php';
        $canonical_view = dirname(__FILE__) . '/views/dashboard.php';

        if (file_exists($legacy_view)) {
            include $legacy_view;
            return;
        }

        if (file_exists($canonical_view)) {
            include $canonical_view;
            return;
        }

        echo '<div class="wrap"><h1>' . esc_html__('Dashboard de Colectivos', 'flavor-chat-ia') . '</h1>';
        echo '<p>' . esc_html__('No se encontró la vista de dashboard.', 'flavor-chat-ia') . '</p></div>';
    }

    /**
     * Renderiza el listado de colectivos en administracion
     */
    public function render_admin_colectivos() {
        $this->render_page_header(
            __('Gestión de Colectivos', 'flavor-chat-ia'),
            [
                [
                    'label' => __('Nuevo Colectivo', 'flavor-chat-ia'),
                    'url'   => $this->admin_page_url('flavor-colectivos-listado') . '&action=new',
                    'class' => 'button-primary',
                ],
            ]
        );

        include dirname(__FILE__) . '/views/admin-colectivos.php';
    }

    /**
     * Renderiza el listado de miembros en administracion
     */
    public function render_admin_miembros() {
        $solicitudes_pendientes = $this->contar_solicitudes_pendientes();

        $this->render_page_header(
            __('Gestión de Miembros', 'flavor-chat-ia'),
            []
        );

        $tabs = [
            [
                'slug'  => 'activos',
                'label' => __('Activos', 'flavor-chat-ia'),
            ],
            [
                'slug'  => 'pendientes',
                'label' => __('Pendientes', 'flavor-chat-ia'),
                'badge' => $solicitudes_pendientes,
            ],
        ];

        $tab_actual = isset($_GET['tab']) ? sanitize_text_field($_GET['tab']) : 'activos';
        $this->render_page_tabs($tabs, $tab_actual);

        include dirname(__FILE__) . '/views/admin-miembros.php';
    }

    /**
     * Renderiza el widget del dashboard principal
     */
    public function render_dashboard_widget() {
        $estadisticas = $this->get_estadisticas_globales();
        ?>
        <div class="colectivos-widget">
            <div class="widget-stats">
                <div class="stat-item">
                    <span class="stat-value"><?php echo esc_html($estadisticas['total_colectivos']); ?></span>
                    <span class="stat-label"><?php esc_html_e('Colectivos', 'flavor-chat-ia'); ?></span>
                </div>
                <div class="stat-item">
                    <span class="stat-value"><?php echo esc_html($estadisticas['total_miembros']); ?></span>
                    <span class="stat-label"><?php esc_html_e('Miembros', 'flavor-chat-ia'); ?></span>
                </div>
                <div class="stat-item">
                    <span class="stat-value"><?php echo esc_html($estadisticas['proyectos_activos']); ?></span>
                    <span class="stat-label"><?php esc_html_e('Proyectos activos', 'flavor-chat-ia'); ?></span>
                </div>
            </div>
            <a href="<?php echo esc_url($this->admin_page_url('flavor-colectivos-dashboard')); ?>" class="button">
                <?php esc_html_e('Ver todo', 'flavor-chat-ia'); ?>
            </a>
        </div>
        <?php
    }

    /**
     * Obtiene las estadisticas globales del modulo
     *
     * @return array
     */
    public function get_estadisticas_globales() {
        global $wpdb;
        $tabla_colectivos          = $wpdb->prefix . 'flavor_colectivos';
        $tabla_colectivos_miembros = $wpdb->prefix . 'flavor_colectivos_miembros';
        $tabla_colectivos_proyectos = $wpdb->prefix . 'flavor_colectivos_proyectos';

        $total_colectivos = (int) $wpdb->get_var("SELECT COUNT(*) FROM $tabla_colectivos WHERE estado = 'activo'");
        $total_miembros   = (int) $wpdb->get_var("SELECT COUNT(*) FROM $tabla_colectivos_miembros WHERE estado = 'activo'");
        $proyectos_activos = (int) $wpdb->get_var("SELECT COUNT(*) FROM $tabla_colectivos_proyectos WHERE estado = 'en_curso'");
        $solicitudes_pendientes = (int) $wpdb->get_var("SELECT COUNT(*) FROM $tabla_colectivos_miembros WHERE estado = 'pendiente'");

        return [
            'total_colectivos'       => $total_colectivos,
            'total_miembros'         => $total_miembros,
            'proyectos_activos'      => $proyectos_activos,
            'solicitudes_pendientes' => $solicitudes_pendientes,
        ];
    }

    /**
     * Cuenta las solicitudes de membresia pendientes
     *
     * @return int
     */
    public function contar_solicitudes_pendientes() {
        // Verificar que el módulo esté activo
        if (!$this->can_activate()) {
            return 0;
        }

        global $wpdb;
        $tabla_colectivos_miembros = $wpdb->prefix . 'flavor_colectivos_miembros';

        return (int) $wpdb->get_var("SELECT COUNT(*) FROM $tabla_colectivos_miembros WHERE estado = 'pendiente'");
    }

    // =========================================================
    // Creacion de tablas
    // =========================================================

    /**
     * Crea las tablas si no existen
     */
    public function maybe_create_tables() {
        global $wpdb;
        $tabla_colectivos = $wpdb->prefix . 'flavor_colectivos';

        if (!Flavor_Chat_Helpers::tabla_existe($tabla_colectivos)) {
            $this->create_tables();
        }
    }

    /**
     * Crea las tablas necesarias para el modulo
     */
    private function create_tables() {
        global $wpdb;
        $charset_collate = $wpdb->get_charset_collate();

        $tabla_colectivos           = $wpdb->prefix . 'flavor_colectivos';
        $tabla_colectivos_miembros  = $wpdb->prefix . 'flavor_colectivos_miembros';
        $tabla_colectivos_proyectos = $wpdb->prefix . 'flavor_colectivos_proyectos';
        $tabla_colectivos_asambleas = $wpdb->prefix . 'flavor_colectivos_asambleas';

        $sql_colectivos = "CREATE TABLE IF NOT EXISTS $tabla_colectivos (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            nombre varchar(200) NOT NULL,
            descripcion text DEFAULT NULL,
            tipo enum('asociacion','cooperativa','ong','colectivo','plataforma') DEFAULT 'colectivo',
            imagen varchar(255) DEFAULT NULL,
            email_contacto varchar(200) DEFAULT NULL,
            telefono varchar(50) DEFAULT NULL,
            direccion text DEFAULT NULL,
            web varchar(255) DEFAULT NULL,
            redes_sociales text DEFAULT NULL,
            sector varchar(100) DEFAULT NULL,
            miembros_count int(11) DEFAULT 0,
            proyectos_count int(11) DEFAULT 0,
            creador_id bigint(20) unsigned DEFAULT NULL,
            estado enum('activo','inactivo','en_formacion') DEFAULT 'activo',
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY creador_id (creador_id),
            KEY tipo (tipo),
            KEY estado (estado),
            KEY sector (sector)
        ) $charset_collate;";

        $sql_miembros = "CREATE TABLE IF NOT EXISTS $tabla_colectivos_miembros (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            colectivo_id bigint(20) unsigned NOT NULL,
            user_id bigint(20) unsigned NOT NULL,
            rol enum('presidente','secretario','tesorero','vocal','miembro') DEFAULT 'miembro',
            estado enum('activo','pendiente','baja') DEFAULT 'pendiente',
            fecha_alta datetime DEFAULT CURRENT_TIMESTAMP,
            fecha_baja datetime DEFAULT NULL,
            PRIMARY KEY (id),
            UNIQUE KEY colectivo_usuario (colectivo_id, user_id),
            KEY colectivo_id (colectivo_id),
            KEY user_id (user_id),
            KEY estado (estado)
        ) $charset_collate;";

        $sql_proyectos = "CREATE TABLE IF NOT EXISTS $tabla_colectivos_proyectos (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            colectivo_id bigint(20) unsigned NOT NULL,
            titulo varchar(255) NOT NULL,
            descripcion text DEFAULT NULL,
            estado enum('planificado','en_curso','completado','cancelado') DEFAULT 'planificado',
            presupuesto decimal(10,2) DEFAULT 0.00,
            fecha_inicio date DEFAULT NULL,
            fecha_fin date DEFAULT NULL,
            responsable_id bigint(20) unsigned DEFAULT NULL,
            participantes text DEFAULT NULL,
            progreso int(11) DEFAULT 0,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY colectivo_id (colectivo_id),
            KEY estado (estado),
            KEY responsable_id (responsable_id)
        ) $charset_collate;";

        $sql_asambleas = "CREATE TABLE IF NOT EXISTS $tabla_colectivos_asambleas (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            colectivo_id bigint(20) unsigned NOT NULL,
            titulo varchar(255) NOT NULL,
            descripcion text DEFAULT NULL,
            tipo enum('ordinaria','extraordinaria') DEFAULT 'ordinaria',
            fecha datetime NOT NULL,
            lugar varchar(255) DEFAULT NULL,
            orden_del_dia text DEFAULT NULL,
            acta text DEFAULT NULL,
            asistentes text DEFAULT NULL,
            estado enum('convocada','en_curso','finalizada','cancelada') DEFAULT 'convocada',
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY colectivo_id (colectivo_id),
            KEY fecha (fecha),
            KEY estado (estado)
        ) $charset_collate;";

        require_once ABSPATH . 'wp-admin/includes/upgrade.php';
        dbDelta($sql_colectivos);
        dbDelta($sql_miembros);
        dbDelta($sql_proyectos);
        dbDelta($sql_asambleas);
    }

    // =========================================================
    // Acciones del modulo
    // =========================================================

    /**
     * {@inheritdoc}
     */
    public function get_actions() {
        return [
            'listar_colectivos' => [
                'description' => 'Listar colectivos con filtros por tipo, sector y estado',
                'params'      => ['tipo', 'sector', 'estado', 'busqueda', 'limite'],
            ],
            'ver_colectivo' => [
                'description' => 'Ver detalles completos de un colectivo',
                'params'      => ['colectivo_id'],
            ],
            'crear_colectivo' => [
                'description' => 'Crear un nuevo colectivo o asociacion',
                'params'      => ['nombre', 'descripcion', 'tipo', 'email_contacto', 'telefono', 'direccion', 'web', 'sector'],
            ],
            'unirse' => [
                'description' => 'Solicitar ser miembro de un colectivo',
                'params'      => ['colectivo_id'],
            ],
            'mis_colectivos' => [
                'description' => 'Ver los colectivos del usuario actual',
                'params'      => [],
            ],
            'listar_proyectos' => [
                'description' => 'Listar proyectos de un colectivo',
                'params'      => ['colectivo_id', 'estado'],
            ],
            'crear_proyecto' => [
                'description' => 'Crear un nuevo proyecto dentro de un colectivo',
                'params'      => ['colectivo_id', 'titulo', 'descripcion', 'presupuesto', 'fecha_inicio', 'fecha_fin'],
            ],
            'convocar_asamblea' => [
                'description' => 'Convocar una asamblea para un colectivo',
                'params'      => ['colectivo_id', 'titulo', 'descripcion', 'tipo', 'fecha', 'lugar', 'orden_del_dia'],
            ],
            'ver_asambleas' => [
                'description' => 'Ver asambleas de un colectivo',
                'params'      => ['colectivo_id', 'estado'],
            ],
            'estadisticas' => [
                'description' => 'Obtener estadisticas de un colectivo',
                'params'      => ['colectivo_id'],
            ],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function execute_action($nombre_accion, $parametros) {
        $aliases = [
            'listar' => 'listar_colectivos',
            'listado' => 'listar_colectivos',
            'explorar' => 'listar_colectivos',
            'buscar' => 'listar_colectivos',
            'crear' => 'crear_colectivo',
            'nuevo' => 'crear_colectivo',
            'detalle' => 'ver_colectivo',
            'ver' => 'ver_colectivo',
            'unirse' => 'unirse',
            'mis_items' => 'mis_colectivos',
            'mis-colectivos' => 'mis_colectivos',
            'proyectos' => 'listar_proyectos',
            'crear_proyecto' => 'crear_proyecto',
            'convocar' => 'convocar_asamblea',
            'asambleas' => 'ver_asambleas',
            'stats' => 'estadisticas',
        ];

        $nombre_accion = $aliases[$nombre_accion] ?? $nombre_accion;
        $metodo_accion = 'action_' . $nombre_accion;

        if (method_exists($this, $metodo_accion)) {
            return $this->$metodo_accion($parametros);
        }

        return [
            'success' => false,
            'error'   => sprintf(__('Acción no implementada: %s', 'flavor-chat-ia'), $nombre_accion),
        ];
    }

    // =========================================================
    // Implementacion de acciones
    // =========================================================

    /**
     * Accion: Listar colectivos con filtros
     */
    private function action_listar_colectivos($parametros) {
        global $wpdb;
        $tabla_colectivos = $wpdb->prefix . 'flavor_colectivos';

        $condiciones_where   = ['1=1'];
        $valores_preparacion = [];

        // Filtro por tipo
        if (!empty($parametros['tipo'])) {
            $condiciones_where[]   = 'tipo = %s';
            $valores_preparacion[] = sanitize_text_field($parametros['tipo']);
        }

        // Filtro por sector
        if (!empty($parametros['sector'])) {
            $condiciones_where[]   = 'sector = %s';
            $valores_preparacion[] = sanitize_text_field($parametros['sector']);
        }

        // Filtro por estado
        if (!empty($parametros['estado'])) {
            $condiciones_where[]   = 'estado = %s';
            $valores_preparacion[] = sanitize_text_field($parametros['estado']);
        } else {
            $condiciones_where[] = "estado = 'activo'";
        }

        // Filtro por busqueda
        if (!empty($parametros['busqueda'])) {
            $termino_busqueda      = '%' . $wpdb->esc_like(sanitize_text_field($parametros['busqueda'])) . '%';
            $condiciones_where[]   = '(nombre LIKE %s OR descripcion LIKE %s OR sector LIKE %s)';
            $valores_preparacion[] = $termino_busqueda;
            $valores_preparacion[] = $termino_busqueda;
            $valores_preparacion[] = $termino_busqueda;
        }

        $limite_resultados     = absint($parametros['limite'] ?? 20);
        $clausula_where        = implode(' AND ', $condiciones_where);

        $consulta_sql          = "SELECT * FROM $tabla_colectivos WHERE $clausula_where ORDER BY nombre ASC LIMIT %d";
        $valores_preparacion[] = $limite_resultados;

        $colectivos_encontrados = $wpdb->get_results($wpdb->prepare($consulta_sql, ...$valores_preparacion));

        $etiquetas_tipo = $this->get_etiquetas_tipo();

        return [
            'success'     => true,
            'total'       => count($colectivos_encontrados),
            'colectivos'  => array_map(function ($colectivo) use ($etiquetas_tipo) {
                return [
                    'id'              => (int) $colectivo->id,
                    'nombre'          => $colectivo->nombre,
                    'tipo'            => $colectivo->tipo,
                    'tipo_label'      => $etiquetas_tipo[$colectivo->tipo] ?? ucfirst($colectivo->tipo),
                    'sector'          => $colectivo->sector,
                    'miembros_count'  => (int) $colectivo->miembros_count,
                    'proyectos_count' => (int) $colectivo->proyectos_count,
                    'estado'          => $colectivo->estado,
                    'imagen'          => $colectivo->imagen,
                ];
            }, $colectivos_encontrados),
        ];
    }

    /**
     * Accion: Ver detalle de un colectivo
     */
    private function action_ver_colectivo($parametros) {
        global $wpdb;
        $tabla_colectivos          = $wpdb->prefix . 'flavor_colectivos';
        $tabla_colectivos_miembros = $wpdb->prefix . 'flavor_colectivos_miembros';

        $identificador_colectivo = absint($parametros['colectivo_id'] ?? 0);

        if (!$identificador_colectivo) {
            return [
                'success' => false,
                'error'   => __('ID de colectivo no válido.', 'flavor-chat-ia'),
            ];
        }

        $colectivo = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM $tabla_colectivos WHERE id = %d",
            $identificador_colectivo
        ));

        if (!$colectivo) {
            return [
                'success' => false,
                'error'   => __('Colectivo no encontrado.', 'flavor-chat-ia'),
            ];
        }

        // Obtener miembros activos
        $miembros_activos = $wpdb->get_results($wpdb->prepare(
            "SELECT m.*, u.display_name, u.user_email
             FROM $tabla_colectivos_miembros m
             INNER JOIN {$wpdb->users} u ON m.user_id = u.ID
             WHERE m.colectivo_id = %d AND m.estado = 'activo'
             ORDER BY FIELD(m.rol, 'presidente','secretario','tesorero','vocal','miembro')",
            $identificador_colectivo
        ));

        $etiquetas_tipo = $this->get_etiquetas_tipo();
        $etiquetas_rol  = $this->get_default_settings()['roles_miembro'];

        $redes_sociales_decodificadas = !empty($colectivo->redes_sociales)
            ? json_decode($colectivo->redes_sociales, true)
            : [];

        $creador_usuario = $colectivo->creador_id ? get_user_by('ID', $colectivo->creador_id) : null;

        return [
            'success'    => true,
            'colectivo'  => [
                'id'              => (int) $colectivo->id,
                'nombre'          => $colectivo->nombre,
                'descripcion'     => $colectivo->descripcion,
                'tipo'            => $colectivo->tipo,
                'tipo_label'      => $etiquetas_tipo[$colectivo->tipo] ?? ucfirst($colectivo->tipo),
                'imagen'          => $colectivo->imagen,
                'email_contacto'  => $colectivo->email_contacto,
                'telefono'        => $colectivo->telefono,
                'direccion'       => $colectivo->direccion,
                'web'             => $colectivo->web,
                'redes_sociales'  => $redes_sociales_decodificadas,
                'sector'          => $colectivo->sector,
                'miembros_count'  => (int) $colectivo->miembros_count,
                'proyectos_count' => (int) $colectivo->proyectos_count,
                'estado'          => $colectivo->estado,
                'creador'         => $creador_usuario ? [
                    'id'     => $creador_usuario->ID,
                    'nombre' => $creador_usuario->display_name,
                    'avatar' => get_avatar_url($creador_usuario->ID, ['size' => 96]),
                ] : null,
                'created_at'      => $colectivo->created_at,
            ],
            'miembros'   => array_map(function ($miembro) use ($etiquetas_rol) {
                return [
                    'id'           => (int) $miembro->id,
                    'user_id'      => (int) $miembro->user_id,
                    'nombre'       => $miembro->display_name,
                    'email'        => $miembro->user_email,
                    'rol'          => $miembro->rol,
                    'rol_label'    => $etiquetas_rol[$miembro->rol] ?? ucfirst($miembro->rol),
                    'fecha_alta'   => $miembro->fecha_alta,
                    'avatar'       => get_avatar_url($miembro->user_id, ['size' => 64]),
                ];
            }, $miembros_activos),
        ];
    }

    /**
     * Accion: Crear un nuevo colectivo
     */
    private function action_crear_colectivo($parametros) {
        $identificador_usuario = get_current_user_id();

        if (!$identificador_usuario) {
            return [
                'success' => false,
                'error'   => __('Debes iniciar sesión para crear un colectivo.', 'flavor-chat-ia'),
            ];
        }

        $nombre_colectivo = sanitize_text_field($parametros['nombre'] ?? '');

        if (empty($nombre_colectivo)) {
            return [
                'success' => false,
                'error'   => __('El nombre del colectivo es obligatorio.', 'flavor-chat-ia'),
            ];
        }

        // Verificar limite de colectivos por usuario
        global $wpdb;
        $tabla_colectivos = $wpdb->prefix . 'flavor_colectivos';

        $maximo_colectivos       = $this->get_setting('maximo_colectivos_por_usuario', 5);
        $colectivos_del_usuario  = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM $tabla_colectivos WHERE creador_id = %d",
            $identificador_usuario
        ));

        if ($colectivos_del_usuario >= $maximo_colectivos) {
            return [
                'success' => false,
                'error'   => sprintf(
                    __('Has alcanzado el límite máximo de %d colectivos creados.', 'flavor-chat-ia'),
                    $maximo_colectivos
                ),
            ];
        }

        // Validar tipo
        $tipos_permitidos = $this->get_setting('tipos_permitidos', ['asociacion', 'cooperativa', 'ong', 'colectivo', 'plataforma']);
        $tipo_colectivo   = sanitize_text_field($parametros['tipo'] ?? 'colectivo');
        if (!in_array($tipo_colectivo, $tipos_permitidos, true)) {
            $tipo_colectivo = 'colectivo';
        }

        $estado_inicial = $this->get_setting('requiere_aprobacion', false) ? 'en_formacion' : 'activo';

        $redes_sociales_json = '';
        if (!empty($parametros['redes_sociales']) && is_array($parametros['redes_sociales'])) {
            $redes_sociales_json = wp_json_encode($parametros['redes_sociales']);
        }

        $resultado_insercion = $wpdb->insert(
            $tabla_colectivos,
            [
                'nombre'          => $nombre_colectivo,
                'descripcion'     => sanitize_textarea_field($parametros['descripcion'] ?? ''),
                'tipo'            => $tipo_colectivo,
                'imagen'          => esc_url_raw($parametros['imagen'] ?? ''),
                'email_contacto'  => sanitize_email($parametros['email_contacto'] ?? ''),
                'telefono'        => sanitize_text_field($parametros['telefono'] ?? ''),
                'direccion'       => sanitize_textarea_field($parametros['direccion'] ?? ''),
                'web'             => esc_url_raw($parametros['web'] ?? ''),
                'redes_sociales'  => $redes_sociales_json,
                'sector'          => sanitize_text_field($parametros['sector'] ?? ''),
                'miembros_count'  => 1,
                'proyectos_count' => 0,
                'creador_id'      => $identificador_usuario,
                'estado'          => $estado_inicial,
            ],
            ['%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%d', '%d', '%d', '%s']
        );

        if ($resultado_insercion === false) {
            return [
                'success' => false,
                'error'   => __('Error al crear el colectivo.', 'flavor-chat-ia'),
            ];
        }

        $identificador_nuevo_colectivo = $wpdb->insert_id;

        // Registrar al creador como presidente
        $tabla_colectivos_miembros = $wpdb->prefix . 'flavor_colectivos_miembros';
        $wpdb->insert(
            $tabla_colectivos_miembros,
            [
                'colectivo_id' => $identificador_nuevo_colectivo,
                'user_id'      => $identificador_usuario,
                'rol'          => 'presidente',
                'estado'       => 'activo',
            ],
            ['%d', '%d', '%s', '%s']
        );

        return [
            'success'       => true,
            'colectivo_id'  => $identificador_nuevo_colectivo,
            'mensaje'       => sprintf(
                __('Colectivo "%s" creado correctamente. Has sido registrado como presidente.', 'flavor-chat-ia'),
                $nombre_colectivo
            ),
        ];
    }

    /**
     * Accion: Solicitar union a un colectivo
     */
    private function action_unirse($parametros) {
        $identificador_usuario = get_current_user_id();

        if (!$identificador_usuario) {
            return [
                'success' => false,
                'error'   => __('Debes iniciar sesión para unirte a un colectivo.', 'flavor-chat-ia'),
            ];
        }

        $identificador_colectivo = absint($parametros['colectivo_id'] ?? 0);

        if (!$identificador_colectivo) {
            return [
                'success' => false,
                'error'   => __('ID de colectivo no válido.', 'flavor-chat-ia'),
            ];
        }

        global $wpdb;
        $tabla_colectivos          = $wpdb->prefix . 'flavor_colectivos';
        $tabla_colectivos_miembros = $wpdb->prefix . 'flavor_colectivos_miembros';

        // Verificar que el colectivo existe y esta activo
        $colectivo = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM $tabla_colectivos WHERE id = %d AND estado = 'activo'",
            $identificador_colectivo
        ));

        if (!$colectivo) {
            return [
                'success' => false,
                'error'   => __('Colectivo no encontrado o no está activo.', 'flavor-chat-ia'),
            ];
        }

        // Verificar si ya es miembro
        $membresia_existente = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM $tabla_colectivos_miembros WHERE colectivo_id = %d AND user_id = %d",
            $identificador_colectivo,
            $identificador_usuario
        ));

        if ($membresia_existente) {
            if ($membresia_existente->estado === 'activo') {
                return [
                    'success' => false,
                    'error'   => __('Ya eres miembro de este colectivo.', 'flavor-chat-ia'),
                ];
            }
            if ($membresia_existente->estado === 'pendiente') {
                return [
                    'success' => false,
                    'error'   => __('Ya tienes una solicitud pendiente para este colectivo.', 'flavor-chat-ia'),
                ];
            }
            // Si estaba de baja, reactivar solicitud
            $wpdb->update(
                $tabla_colectivos_miembros,
                [
                    'estado'     => 'pendiente',
                    'fecha_alta' => current_time('mysql'),
                    'fecha_baja' => null,
                ],
                [
                    'colectivo_id' => $identificador_colectivo,
                    'user_id'      => $identificador_usuario,
                ],
                ['%s', '%s', null],
                ['%d', '%d']
            );
        } else {
            $wpdb->insert(
                $tabla_colectivos_miembros,
                [
                    'colectivo_id' => $identificador_colectivo,
                    'user_id'      => $identificador_usuario,
                    'rol'          => 'miembro',
                    'estado'       => 'pendiente',
                ],
                ['%d', '%d', '%s', '%s']
            );
        }

        return [
            'success' => true,
            'mensaje' => sprintf(
                __('Tu solicitud para unirte a "%s" ha sido enviada. Un administrador del colectivo la revisará.', 'flavor-chat-ia'),
                $colectivo->nombre
            ),
        ];
    }

    /**
     * Accion: Ver colectivos del usuario actual
     */
    private function action_mis_colectivos($parametros) {
        $identificador_usuario = get_current_user_id();

        if (!$identificador_usuario) {
            return [
                'success' => false,
                'error'   => __('Debes iniciar sesión para ver tus colectivos.', 'flavor-chat-ia'),
            ];
        }

        global $wpdb;
        $tabla_colectivos          = $wpdb->prefix . 'flavor_colectivos';
        $tabla_colectivos_miembros = $wpdb->prefix . 'flavor_colectivos_miembros';

        $colectivos_usuario = $wpdb->get_results($wpdb->prepare(
            "SELECT c.*, m.rol, m.estado as membresia_estado, m.fecha_alta
             FROM $tabla_colectivos c
             INNER JOIN $tabla_colectivos_miembros m ON c.id = m.colectivo_id
             WHERE m.user_id = %d AND m.estado IN ('activo', 'pendiente')
             ORDER BY m.fecha_alta DESC",
            $identificador_usuario
        ));

        $etiquetas_tipo = $this->get_etiquetas_tipo();
        $etiquetas_rol  = $this->get_default_settings()['roles_miembro'];

        return [
            'success'     => true,
            'total'       => count($colectivos_usuario),
            'colectivos'  => array_map(function ($colectivo) use ($etiquetas_tipo, $etiquetas_rol) {
                return [
                    'id'                => (int) $colectivo->id,
                    'nombre'            => $colectivo->nombre,
                    'tipo'              => $colectivo->tipo,
                    'tipo_label'        => $etiquetas_tipo[$colectivo->tipo] ?? ucfirst($colectivo->tipo),
                    'sector'            => $colectivo->sector,
                    'rol'               => $colectivo->rol,
                    'rol_label'         => $etiquetas_rol[$colectivo->rol] ?? ucfirst($colectivo->rol),
                    'membresia_estado'  => $colectivo->membresia_estado,
                    'miembros_count'    => (int) $colectivo->miembros_count,
                    'proyectos_count'   => (int) $colectivo->proyectos_count,
                    'imagen'            => $colectivo->imagen,
                    'fecha_alta'        => $colectivo->fecha_alta,
                ];
            }, $colectivos_usuario),
        ];
    }

    /**
     * Accion: Listar proyectos de un colectivo
     */
    private function action_listar_proyectos($parametros) {
        $identificador_colectivo = absint($parametros['colectivo_id'] ?? 0);

        if (!$identificador_colectivo) {
            return [
                'success' => false,
                'error'   => __('ID de colectivo no válido.', 'flavor-chat-ia'),
            ];
        }

        global $wpdb;
        $tabla_colectivos_proyectos = $wpdb->prefix . 'flavor_colectivos_proyectos';

        $condiciones_where   = ['colectivo_id = %d'];
        $valores_preparacion = [$identificador_colectivo];

        if (!empty($parametros['estado'])) {
            $condiciones_where[]   = 'estado = %s';
            $valores_preparacion[] = sanitize_text_field($parametros['estado']);
        }

        $clausula_where = implode(' AND ', $condiciones_where);

        $proyectos_encontrados = $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM $tabla_colectivos_proyectos WHERE $clausula_where ORDER BY created_at DESC",
            ...$valores_preparacion
        ));

        return [
            'success'    => true,
            'total'      => count($proyectos_encontrados),
            'proyectos'  => array_map(function ($proyecto) {
                $responsable_usuario = $proyecto->responsable_id ? get_user_by('ID', $proyecto->responsable_id) : null;
                $participantes_decodificados = !empty($proyecto->participantes)
                    ? json_decode($proyecto->participantes, true)
                    : [];

                return [
                    'id'              => (int) $proyecto->id,
                    'titulo'          => $proyecto->titulo,
                    'descripcion'     => $proyecto->descripcion,
                    'estado'          => $proyecto->estado,
                    'estado_label'    => $this->get_etiqueta_estado_proyecto($proyecto->estado),
                    'presupuesto'     => (float) $proyecto->presupuesto,
                    'presupuesto_fmt' => number_format($proyecto->presupuesto, 2, ',', '.') . ' EUR',
                    'fecha_inicio'    => $proyecto->fecha_inicio,
                    'fecha_fin'       => $proyecto->fecha_fin,
                    'progreso'        => (int) $proyecto->progreso,
                    'responsable'     => $responsable_usuario ? [
                        'id'     => $responsable_usuario->ID,
                        'nombre' => $responsable_usuario->display_name,
                        'avatar' => get_avatar_url($responsable_usuario->ID, ['size' => 64]),
                    ] : null,
                    'num_participantes' => count($participantes_decodificados),
                ];
            }, $proyectos_encontrados),
        ];
    }

    /**
     * Accion: Crear proyecto dentro de un colectivo
     */
    private function action_crear_proyecto($parametros) {
        $identificador_usuario = get_current_user_id();

        if (!$identificador_usuario) {
            return [
                'success' => false,
                'error'   => __('Debes iniciar sesión para crear un proyecto.', 'flavor-chat-ia'),
            ];
        }

        $identificador_colectivo = absint($parametros['colectivo_id'] ?? 0);

        if (!$identificador_colectivo) {
            return [
                'success' => false,
                'error'   => __('ID de colectivo no válido.', 'flavor-chat-ia'),
            ];
        }

        // Verificar que el usuario es miembro activo del colectivo
        if (!$this->es_miembro_activo($identificador_colectivo, $identificador_usuario)) {
            return [
                'success' => false,
                'error'   => __('Debes ser miembro activo del colectivo para crear proyectos.', 'flavor-chat-ia'),
            ];
        }

        $titulo_proyecto = sanitize_text_field($parametros['titulo'] ?? '');

        if (empty($titulo_proyecto)) {
            return [
                'success' => false,
                'error'   => __('El título del proyecto es obligatorio.', 'flavor-chat-ia'),
            ];
        }

        global $wpdb;
        $tabla_colectivos_proyectos = $wpdb->prefix . 'flavor_colectivos_proyectos';

        $participantes_json = '';
        if (!empty($parametros['participantes']) && is_array($parametros['participantes'])) {
            $participantes_json = wp_json_encode(array_map('absint', $parametros['participantes']));
        }

        $resultado_insercion = $wpdb->insert(
            $tabla_colectivos_proyectos,
            [
                'colectivo_id'   => $identificador_colectivo,
                'titulo'         => $titulo_proyecto,
                'descripcion'    => sanitize_textarea_field($parametros['descripcion'] ?? ''),
                'estado'         => 'planificado',
                'presupuesto'    => floatval($parametros['presupuesto'] ?? 0),
                'fecha_inicio'   => !empty($parametros['fecha_inicio']) ? sanitize_text_field($parametros['fecha_inicio']) : null,
                'fecha_fin'      => !empty($parametros['fecha_fin']) ? sanitize_text_field($parametros['fecha_fin']) : null,
                'responsable_id' => $identificador_usuario,
                'participantes'  => $participantes_json,
                'progreso'       => 0,
            ],
            ['%d', '%s', '%s', '%s', '%f', '%s', '%s', '%d', '%s', '%d']
        );

        if ($resultado_insercion === false) {
            return [
                'success' => false,
                'error'   => __('Error al crear el proyecto.', 'flavor-chat-ia'),
            ];
        }

        // Actualizar contador de proyectos
        $tabla_colectivos = $wpdb->prefix . 'flavor_colectivos';
        $wpdb->query($wpdb->prepare(
            "UPDATE $tabla_colectivos SET proyectos_count = proyectos_count + 1 WHERE id = %d",
            $identificador_colectivo
        ));

        return [
            'success'      => true,
            'proyecto_id'  => $wpdb->insert_id,
            'mensaje'      => sprintf(
                __('Proyecto "%s" creado correctamente.', 'flavor-chat-ia'),
                $titulo_proyecto
            ),
        ];
    }

    /**
     * Accion: Convocar asamblea
     */
    private function action_convocar_asamblea($parametros) {
        $identificador_usuario = get_current_user_id();

        if (!$identificador_usuario) {
            return [
                'success' => false,
                'error'   => __('Debes iniciar sesión para convocar una asamblea.', 'flavor-chat-ia'),
            ];
        }

        $identificador_colectivo = absint($parametros['colectivo_id'] ?? 0);

        if (!$identificador_colectivo) {
            return [
                'success' => false,
                'error'   => __('ID de colectivo no válido.', 'flavor-chat-ia'),
            ];
        }

        // Verificar que el usuario tiene rol de gestion
        $rol_usuario = $this->obtener_rol_miembro($identificador_colectivo, $identificador_usuario);
        $roles_permitidos_convocatoria = ['presidente', 'secretario'];

        if (!in_array($rol_usuario, $roles_permitidos_convocatoria, true)) {
            return [
                'success' => false,
                'error'   => __('Solo el presidente o secretario pueden convocar asambleas.', 'flavor-chat-ia'),
            ];
        }

        $titulo_asamblea = sanitize_text_field($parametros['titulo'] ?? '');
        $fecha_asamblea  = sanitize_text_field($parametros['fecha'] ?? '');

        if (empty($titulo_asamblea) || empty($fecha_asamblea)) {
            return [
                'success' => false,
                'error'   => __('El título y la fecha de la asamblea son obligatorios.', 'flavor-chat-ia'),
            ];
        }

        // Validar tipo de asamblea
        $tipo_asamblea = sanitize_text_field($parametros['tipo'] ?? 'ordinaria');
        if (!in_array($tipo_asamblea, ['ordinaria', 'extraordinaria'], true)) {
            $tipo_asamblea = 'ordinaria';
        }

        global $wpdb;
        $tabla_colectivos_asambleas = $wpdb->prefix . 'flavor_colectivos_asambleas';

        $resultado_insercion = $wpdb->insert(
            $tabla_colectivos_asambleas,
            [
                'colectivo_id' => $identificador_colectivo,
                'titulo'       => $titulo_asamblea,
                'descripcion'  => sanitize_textarea_field($parametros['descripcion'] ?? ''),
                'tipo'         => $tipo_asamblea,
                'fecha'        => $fecha_asamblea,
                'lugar'        => sanitize_text_field($parametros['lugar'] ?? ''),
                'orden_del_dia'=> sanitize_textarea_field($parametros['orden_del_dia'] ?? ''),
                'acta'         => '',
                'asistentes'   => '[]',
                'estado'       => 'convocada',
            ],
            ['%d', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s']
        );

        if ($resultado_insercion === false) {
            return [
                'success' => false,
                'error'   => __('Error al convocar la asamblea.', 'flavor-chat-ia'),
            ];
        }

        return [
            'success'      => true,
            'asamblea_id'  => $wpdb->insert_id,
            'mensaje'      => sprintf(
                __('Asamblea "%s" convocada para el %s.', 'flavor-chat-ia'),
                $titulo_asamblea,
                date_i18n('j F Y, H:i', strtotime($fecha_asamblea))
            ),
        ];
    }

    /**
     * Accion: Ver asambleas de un colectivo
     */
    private function action_ver_asambleas($parametros) {
        $identificador_colectivo = absint($parametros['colectivo_id'] ?? 0);

        if (!$identificador_colectivo) {
            return [
                'success' => false,
                'error'   => __('ID de colectivo no válido.', 'flavor-chat-ia'),
            ];
        }

        global $wpdb;
        $tabla_colectivos_asambleas = $wpdb->prefix . 'flavor_colectivos_asambleas';

        $condiciones_where   = ['colectivo_id = %d'];
        $valores_preparacion = [$identificador_colectivo];

        if (!empty($parametros['estado'])) {
            $condiciones_where[]   = 'estado = %s';
            $valores_preparacion[] = sanitize_text_field($parametros['estado']);
        }

        $clausula_where = implode(' AND ', $condiciones_where);

        $asambleas_encontradas = $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM $tabla_colectivos_asambleas WHERE $clausula_where ORDER BY fecha DESC",
            ...$valores_preparacion
        ));

        return [
            'success'    => true,
            'total'      => count($asambleas_encontradas),
            'asambleas'  => array_map(function ($asamblea) {
                $asistentes_decodificados = !empty($asamblea->asistentes)
                    ? json_decode($asamblea->asistentes, true)
                    : [];

                return [
                    'id'              => (int) $asamblea->id,
                    'titulo'          => $asamblea->titulo,
                    'descripcion'     => $asamblea->descripcion,
                    'tipo'            => $asamblea->tipo,
                    'tipo_label'      => $asamblea->tipo === 'ordinaria'
                        ? __('Ordinaria', 'flavor-chat-ia')
                        : __('Extraordinaria', 'flavor-chat-ia'),
                    'fecha'           => $asamblea->fecha,
                    'fecha_formateada'=> date_i18n('l j F Y, H:i', strtotime($asamblea->fecha)),
                    'lugar'           => $asamblea->lugar,
                    'orden_del_dia'   => $asamblea->orden_del_dia,
                    'estado'          => $asamblea->estado,
                    'estado_label'    => $this->get_etiqueta_estado_asamblea($asamblea->estado),
                    'num_asistentes'  => count($asistentes_decodificados),
                    'tiene_acta'      => !empty($asamblea->acta),
                ];
            }, $asambleas_encontradas),
        ];
    }

    /**
     * Accion: Estadisticas de un colectivo
     */
    private function action_estadisticas($parametros) {
        $identificador_colectivo = absint($parametros['colectivo_id'] ?? 0);

        if (!$identificador_colectivo) {
            return [
                'success' => false,
                'error'   => __('ID de colectivo no válido.', 'flavor-chat-ia'),
            ];
        }

        global $wpdb;
        $tabla_colectivos           = $wpdb->prefix . 'flavor_colectivos';
        $tabla_colectivos_miembros  = $wpdb->prefix . 'flavor_colectivos_miembros';
        $tabla_colectivos_proyectos = $wpdb->prefix . 'flavor_colectivos_proyectos';
        $tabla_colectivos_asambleas = $wpdb->prefix . 'flavor_colectivos_asambleas';

        // Verificar que el colectivo existe
        $colectivo = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM $tabla_colectivos WHERE id = %d",
            $identificador_colectivo
        ));

        if (!$colectivo) {
            return [
                'success' => false,
                'error'   => __('Colectivo no encontrado.', 'flavor-chat-ia'),
            ];
        }

        // Total de miembros activos
        $total_miembros_activos = (int) $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM $tabla_colectivos_miembros WHERE colectivo_id = %d AND estado = 'activo'",
            $identificador_colectivo
        ));

        // Solicitudes pendientes
        $total_solicitudes_pendientes = (int) $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM $tabla_colectivos_miembros WHERE colectivo_id = %d AND estado = 'pendiente'",
            $identificador_colectivo
        ));

        // Proyectos por estado
        $proyectos_por_estado = $wpdb->get_results($wpdb->prepare(
            "SELECT estado, COUNT(*) as total FROM $tabla_colectivos_proyectos WHERE colectivo_id = %d GROUP BY estado",
            $identificador_colectivo
        ));

        $resumen_proyectos = [];
        $total_proyectos   = 0;
        foreach ($proyectos_por_estado as $fila_estado) {
            $resumen_proyectos[$fila_estado->estado] = (int) $fila_estado->total;
            $total_proyectos += (int) $fila_estado->total;
        }

        // Presupuesto total
        $presupuesto_total = (float) $wpdb->get_var($wpdb->prepare(
            "SELECT COALESCE(SUM(presupuesto), 0) FROM $tabla_colectivos_proyectos WHERE colectivo_id = %d",
            $identificador_colectivo
        ));

        // Progreso medio de proyectos en curso
        $progreso_medio = (float) $wpdb->get_var($wpdb->prepare(
            "SELECT COALESCE(AVG(progreso), 0) FROM $tabla_colectivos_proyectos WHERE colectivo_id = %d AND estado = 'en_curso'",
            $identificador_colectivo
        ));

        // Asambleas
        $total_asambleas = (int) $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM $tabla_colectivos_asambleas WHERE colectivo_id = %d",
            $identificador_colectivo
        ));

        $proxima_asamblea = $wpdb->get_row($wpdb->prepare(
            "SELECT titulo, fecha, lugar FROM $tabla_colectivos_asambleas
             WHERE colectivo_id = %d AND estado = 'convocada' AND fecha >= NOW()
             ORDER BY fecha ASC LIMIT 1",
            $identificador_colectivo
        ));

        return [
            'success'       => true,
            'estadisticas'  => [
                'nombre_colectivo'        => $colectivo->nombre,
                'tipo'                    => $colectivo->tipo,
                'miembros_activos'        => $total_miembros_activos,
                'solicitudes_pendientes'  => $total_solicitudes_pendientes,
                'total_proyectos'         => $total_proyectos,
                'proyectos_por_estado'    => $resumen_proyectos,
                'presupuesto_total'       => $presupuesto_total,
                'presupuesto_total_fmt'   => number_format($presupuesto_total, 2, ',', '.') . ' EUR',
                'progreso_medio'          => round($progreso_medio, 1),
                'total_asambleas'         => $total_asambleas,
                'proxima_asamblea'        => $proxima_asamblea ? [
                    'titulo' => $proxima_asamblea->titulo,
                    'fecha'  => date_i18n('j F Y, H:i', strtotime($proxima_asamblea->fecha)),
                    'lugar'  => $proxima_asamblea->lugar,
                ] : null,
            ],
        ];
    }

    // =========================================================
    // AI Tools (definiciones para Claude)
    // =========================================================

    /**
     * {@inheritdoc}
     */
    public function get_tool_definitions() {
        return [
            [
                'name'         => 'colectivos_listar',
                'description'  => 'Lista los colectivos y asociaciones disponibles con filtros opcionales',
                'input_schema' => [
                    'type'       => 'object',
                    'properties' => [
                        'tipo' => [
                            'type'        => 'string',
                            'description' => 'Filtrar por tipo: asociacion, cooperativa, ong, colectivo, plataforma',
                            'enum'        => ['asociacion', 'cooperativa', 'ong', 'colectivo', 'plataforma'],
                        ],
                        'sector' => [
                            'type'        => 'string',
                            'description' => 'Filtrar por sector de actividad',
                        ],
                        'limite' => [
                            'type'        => 'integer',
                            'description' => 'Numero maximo de resultados',
                            'default'     => 20,
                        ],
                    ],
                ],
            ],
            [
                'name'         => 'colectivos_buscar',
                'description'  => 'Busca colectivos por nombre, descripcion o sector',
                'input_schema' => [
                    'type'       => 'object',
                    'properties' => [
                        'busqueda' => [
                            'type'        => 'string',
                            'description' => 'Termino de busqueda',
                        ],
                        'tipo' => [
                            'type'        => 'string',
                            'description' => 'Filtrar por tipo',
                            'enum'        => ['asociacion', 'cooperativa', 'ong', 'colectivo', 'plataforma'],
                        ],
                    ],
                    'required' => ['busqueda'],
                ],
            ],
            [
                'name'         => 'colectivos_crear',
                'description'  => 'Crea un nuevo colectivo o asociacion. Requiere autenticacion.',
                'input_schema' => [
                    'type'       => 'object',
                    'properties' => [
                        'nombre' => [
                            'type'        => 'string',
                            'description' => 'Nombre del colectivo',
                        ],
                        'descripcion' => [
                            'type'        => 'string',
                            'description' => 'Descripcion del colectivo',
                        ],
                        'tipo' => [
                            'type'        => 'string',
                            'description' => 'Tipo de organizacion',
                            'enum'        => ['asociacion', 'cooperativa', 'ong', 'colectivo', 'plataforma'],
                            'default'     => 'colectivo',
                        ],
                        'sector' => [
                            'type'        => 'string',
                            'description' => 'Sector de actividad',
                        ],
                        'email_contacto' => [
                            'type'        => 'string',
                            'description' => 'Email de contacto',
                        ],
                    ],
                    'required' => ['nombre'],
                ],
            ],
        ];
    }

    // =========================================================
    // Knowledge Base y FAQs
    // =========================================================

    /**
     * {@inheritdoc}
     */
    public function get_knowledge_base() {
        return <<<KNOWLEDGE
**Sistema de Colectivos y Asociaciones**

Gestion completa de colectivos, asociaciones, cooperativas, ONGs y plataformas ciudadanas.

**Funcionalidades:**
- Crear y gestionar colectivos de distintos tipos
- Solicitar membresia y gestionar miembros con roles
- Crear y seguir proyectos con presupuesto y progreso
- Convocar y gestionar asambleas ordinarias y extraordinarias
- Estadisticas completas de cada colectivo

**Tipos de organizaciones:**
- Asociacion: Organizacion formal con estatutos
- Cooperativa: Empresa de economia social
- ONG: Organizacion no gubernamental
- Colectivo: Grupo informal organizado
- Plataforma: Plataforma ciudadana o movimiento

**Roles de miembro:**
- Presidente/a: Maximo responsable
- Secretario/a: Gestion administrativa
- Tesorero/a: Gestion economica
- Vocal: Miembro de la junta
- Miembro: Miembro base

**Comandos disponibles:**
- "ver colectivos": lista todos los colectivos activos
- "buscar colectivo [nombre]": busca por nombre o sector
- "crear colectivo": inicia el proceso de creacion
- "mis colectivos": muestra tus colectivos
- "proyectos de [colectivo]": lista proyectos
- "asambleas de [colectivo]": lista asambleas
- "estadisticas de [colectivo]": muestra estadisticas
KNOWLEDGE;
    }

    /**
     * {@inheritdoc}
     */
    public function get_faqs() {
        return [
            [
                'pregunta'  => '¿Cómo creo un colectivo?',
                'respuesta' => 'Puedes crear un colectivo desde la sección de Colectivos. Necesitas proporcionar un nombre, tipo de organización y una descripción. Automáticamente serás registrado como presidente.',
            ],
            [
                'pregunta'  => '¿Cómo me uno a un colectivo existente?',
                'respuesta' => 'Ve a la ficha del colectivo y solicita unirte. Un administrador del colectivo revisará tu solicitud y la aprobará.',
            ],
            [
                'pregunta'  => '¿Quién puede convocar asambleas?',
                'respuesta' => 'Solo el presidente o el secretario del colectivo pueden convocar asambleas ordinarias o extraordinarias.',
            ],
            [
                'pregunta'  => '¿Qué tipos de colectivos puedo crear?',
                'respuesta' => 'Puedes crear asociaciones, cooperativas, ONGs, colectivos informales y plataformas ciudadanas.',
            ],
        ];
    }

    // =========================================================
    // Componentes Web
    // =========================================================

    /**
     * Componentes web del modulo
     */
    public function get_web_components() {
        return [
            'colectivos_hero' => [
                'label'       => __('Hero Colectivos', 'flavor-chat-ia'),
                'description' => __('Sección hero para la página de colectivos y asociaciones', 'flavor-chat-ia'),
                'category'    => 'hero',
                'icon'        => 'dashicons-groups',
                'fields'      => [
                    'titulo' => [
                        'type'    => 'text',
                        'label'   => __('Título', 'flavor-chat-ia'),
                        'default' => __('Colectivos y Asociaciones', 'flavor-chat-ia'),
                    ],
                    'subtitulo' => [
                        'type'    => 'textarea',
                        'label'   => __('Subtítulo', 'flavor-chat-ia'),
                        'default' => __('Descubre y participa en los colectivos de tu comunidad', 'flavor-chat-ia'),
                    ],
                ],
                'template' => 'colectivos/hero',
            ],
            'colectivos_grid' => [
                'label'       => __('Grid de Colectivos', 'flavor-chat-ia'),
                'description' => __('Listado de colectivos en tarjetas con filtros', 'flavor-chat-ia'),
                'category'    => 'listings',
                'icon'        => 'dashicons-grid-view',
                'fields'      => [
                    'titulo_seccion' => [
                        'type'    => 'text',
                        'label'   => __('Título de sección', 'flavor-chat-ia'),
                        'default' => __('Nuestros Colectivos', 'flavor-chat-ia'),
                    ],
                    'columnas' => [
                        'type'    => 'select',
                        'label'   => __('Columnas', 'flavor-chat-ia'),
                        'options' => [2, 3, 4],
                        'default' => 3,
                    ],
                    'tipo_filtro' => [
                        'type'    => 'select',
                        'label'   => __('Filtrar por tipo', 'flavor-chat-ia'),
                        'options' => ['todos', 'asociacion', 'cooperativa', 'ong', 'colectivo', 'plataforma'],
                        'default' => 'todos',
                    ],
                ],
                'template' => 'colectivos/colectivos-grid',
            ],
            'colectivos_proyectos' => [
                'label'       => __('Proyectos de Colectivos', 'flavor-chat-ia'),
                'description' => __('Muestra los proyectos activos de los colectivos', 'flavor-chat-ia'),
                'category'    => 'content',
                'icon'        => 'dashicons-portfolio',
                'fields'      => [
                    'titulo_seccion' => [
                        'type'    => 'text',
                        'label'   => __('Título de sección', 'flavor-chat-ia'),
                        'default' => __('Proyectos en Marcha', 'flavor-chat-ia'),
                    ],
                    'mostrar_progreso' => [
                        'type'    => 'toggle',
                        'label'   => __('Mostrar barra de progreso', 'flavor-chat-ia'),
                        'default' => true,
                    ],
                ],
                'template' => 'colectivos/proyectos',
            ],
        ];
    }

    // =========================================================
    // Helpers internos
    // =========================================================

    /**
     * Verifica si un usuario es miembro activo de un colectivo
     *
     * @param int $identificador_colectivo ID del colectivo
     * @param int $identificador_usuario   ID del usuario
     * @return bool
     */
    private function es_miembro_activo($identificador_colectivo, $identificador_usuario) {
        global $wpdb;
        $tabla_colectivos_miembros = $wpdb->prefix . 'flavor_colectivos_miembros';

        return (bool) $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM $tabla_colectivos_miembros WHERE colectivo_id = %d AND user_id = %d AND estado = 'activo'",
            $identificador_colectivo,
            $identificador_usuario
        ));
    }

    /**
     * Obtiene el rol de un miembro en un colectivo
     *
     * @param int $identificador_colectivo ID del colectivo
     * @param int $identificador_usuario   ID del usuario
     * @return string|null
     */
    private function obtener_rol_miembro($identificador_colectivo, $identificador_usuario) {
        global $wpdb;
        $tabla_colectivos_miembros = $wpdb->prefix . 'flavor_colectivos_miembros';

        return $wpdb->get_var($wpdb->prepare(
            "SELECT rol FROM $tabla_colectivos_miembros WHERE colectivo_id = %d AND user_id = %d AND estado = 'activo'",
            $identificador_colectivo,
            $identificador_usuario
        ));
    }

    /**
     * Devuelve las etiquetas legibles de los tipos de colectivo
     *
     * @return array
     */
    private function get_etiquetas_tipo() {
        return [
            'asociacion'  => __('Asociación', 'flavor-chat-ia'),
            'cooperativa' => __('Cooperativa', 'flavor-chat-ia'),
            'ong'         => __('ONG', 'flavor-chat-ia'),
            'colectivo'   => __('Colectivo', 'flavor-chat-ia'),
            'plataforma'  => __('Plataforma', 'flavor-chat-ia'),
        ];
    }

    /**
     * Devuelve la etiqueta legible del estado de un proyecto
     *
     * @param string $estado_proyecto Estado del proyecto
     * @return string
     */
    private function get_etiqueta_estado_proyecto($estado_proyecto) {
        $etiquetas_estado_proyecto = [
            'planificado' => __('Planificado', 'flavor-chat-ia'),
            'en_curso'    => __('En curso', 'flavor-chat-ia'),
            'completado'  => __('Completado', 'flavor-chat-ia'),
            'cancelado'   => __('Cancelado', 'flavor-chat-ia'),
        ];

        return $etiquetas_estado_proyecto[$estado_proyecto] ?? ucfirst($estado_proyecto);
    }

    /**
     * Devuelve la etiqueta legible del estado de una asamblea
     *
     * @param string $estado_asamblea Estado de la asamblea
     * @return string
     */
    private function get_etiqueta_estado_asamblea($estado_asamblea) {
        $etiquetas_estado_asamblea = [
            'convocada'  => __('Convocada', 'flavor-chat-ia'),
            'en_curso'   => __('En curso', 'flavor-chat-ia'),
            'finalizada' => __('Finalizada', 'flavor-chat-ia'),
            'cancelada'  => __('Cancelada', 'flavor-chat-ia'),
        ];

        return $etiquetas_estado_asamblea[$estado_asamblea] ?? ucfirst($estado_asamblea);
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
            Flavor_Page_Creator::refresh_module_pages('colectivos');
            return;
        }

        // En frontend: crear páginas si no existen
        $pagina = get_page_by_path('colectivos');
        if (!$pagina && !get_option('flavor_colectivos_pages_created')) {
            Flavor_Page_Creator::create_pages_for_modules(['colectivos']);
            update_option('flavor_colectivos_pages_created', 1, false);
        }
    }

    /**
     * Obtiene estadísticas para el dashboard del cliente
     *
     * @return array Estadísticas del módulo
     */
    public function get_estadisticas_dashboard() {
        global $wpdb;
        $estadisticas = [];

        $tabla_colectivos = $wpdb->prefix . 'flavor_colectivos';
        $tabla_miembros = $wpdb->prefix . 'flavor_colectivos_miembros';
        $tabla_asambleas = $wpdb->prefix . 'flavor_colectivos_asambleas';

        if (!Flavor_Chat_Helpers::tabla_existe($tabla_colectivos)) {
            return $estadisticas;
        }

        // Total de colectivos activos
        $total_colectivos = (int) $wpdb->get_var(
            "SELECT COUNT(*) FROM {$tabla_colectivos} WHERE estado = 'activo'"
        );

        $estadisticas['colectivos_activos'] = [
            'icon' => 'dashicons-groups',
            'valor' => $total_colectivos,
            'label' => __('Colectivos', 'flavor-chat-ia'),
            'color' => 'purple',
        ];

        $usuario_id = get_current_user_id();
        if ($usuario_id && Flavor_Chat_Helpers::tabla_existe($tabla_miembros)) {
            // Mis colectivos
            $mis_colectivos = (int) $wpdb->get_var($wpdb->prepare(
                "SELECT COUNT(*) FROM {$tabla_miembros}
                 WHERE usuario_id = %d AND estado = 'activo'",
                $usuario_id
            ));

            $estadisticas['mis_colectivos'] = [
                'icon' => 'dashicons-admin-users',
                'valor' => $mis_colectivos,
                'label' => __('Mis colectivos', 'flavor-chat-ia'),
                'color' => $mis_colectivos > 0 ? 'green' : 'gray',
            ];
        }

        // Próximas asambleas
        if (Flavor_Chat_Helpers::tabla_existe($tabla_asambleas)) {
            $proximas_asambleas = (int) $wpdb->get_var(
                "SELECT COUNT(*) FROM {$tabla_asambleas}
                 WHERE fecha >= NOW() AND estado = 'programada'"
            );

            if ($proximas_asambleas > 0) {
                $estadisticas['proximas_asambleas'] = [
                    'icon' => 'dashicons-calendar-alt',
                    'valor' => $proximas_asambleas,
                    'label' => __('Asambleas próximas', 'flavor-chat-ia'),
                    'color' => 'blue',
                ];
            }
        }

        return $estadisticas;
    }

    /**
     * Define las páginas del módulo (Page Creator V3)
     *
     * @return array Definiciones de páginas
     */
    public function get_pages_definition() {
        return [
            [
                'title' => __('Colectivos', 'flavor-chat-ia'),
                'slug' => 'colectivos',
                'content' => '<h1>' . __('Colectivos y Asociaciones', 'flavor-chat-ia') . '</h1>
<p>' . __('Descubre colectivos, asociaciones, cooperativas y ONGs de tu comunidad. Únete y participa en proyectos colectivos.', 'flavor-chat-ia') . '</p>

[flavor_module_listing module="colectivos" action="listar" columnas="3" limite="12"]',
                'parent' => 0,
            ],
            [
                'title' => __('Crear Colectivo', 'flavor-chat-ia'),
                'slug' => 'crear',
                'content' => '<h1>' . __('Crear Colectivo', 'flavor-chat-ia') . '</h1>
<p>' . __('Crea tu propio colectivo, asociación o cooperativa y organiza proyectos y asambleas.', 'flavor-chat-ia') . '</p>

[flavor_module_listing module="colectivos" action="crear"]',
                'parent' => 'colectivos',
            ],
            [
                'title' => __('Mis Colectivos', 'flavor-chat-ia'),
                'slug' => 'mis-colectivos',
                'content' => '<h1>' . __('Mis Colectivos', 'flavor-chat-ia') . '</h1>
<p>' . __('Gestiona los colectivos de los que eres miembro y los que has creado.', 'flavor-chat-ia') . '</p>

[flavor_module_listing module="colectivos" action="mis_colectivos" columnas="3" limite="12"]',
                'parent' => 'colectivos',
            ],
        ];
    }

    // =========================================================================
    // PÁGINAS DE ADMINISTRACIÓN
    // =========================================================================

    /**
     * Registra las páginas de administración del módulo (ocultas del sidebar)
     */
    public function registrar_paginas_admin() {
        static $registered = false;
        if ($registered) {
            return;
        }
        $registered = true;


        $capability = 'manage_options';

        // Página principal (oculta)
        add_submenu_page(
            null,
            __('Colectivos', 'flavor-chat-ia'),
            __('Colectivos', 'flavor-chat-ia'),
            $capability,
            'colectivos',
            [$this, 'render_pagina_dashboard']
        );

        // Dashboard - página para panel unificado
        add_submenu_page(
            null,
            __('Dashboard Colectivos', 'flavor-chat-ia'),
            __('Dashboard', 'flavor-chat-ia'),
            $capability,
            'flavor-colectivos-dashboard',
            [$this, 'render_pagina_dashboard']
        );

        // Página: Listado (oculta)
        add_submenu_page(
            null,
            __('Todos los Colectivos', 'flavor-chat-ia'),
            __('Listado', 'flavor-chat-ia'),
            $capability,
            'colectivos-listado',
            [$this, 'render_pagina_listado']
        );

        // Página: Proyectos (oculta)
        add_submenu_page(
            null,
            __('Proyectos', 'flavor-chat-ia'),
            __('Proyectos', 'flavor-chat-ia'),
            $capability,
            'colectivos-proyectos',
            [$this, 'render_pagina_proyectos']
        );

        // Página: Asambleas (oculta)
        add_submenu_page(
            null,
            __('Asambleas', 'flavor-chat-ia'),
            __('Asambleas', 'flavor-chat-ia'),
            $capability,
            'colectivos-asambleas',
            [$this, 'render_pagina_asambleas']
        );

        // Página: Miembros (oculta)
        add_submenu_page(
            null,
            __('Miembros de Colectivos', 'flavor-chat-ia'),
            __('Miembros', 'flavor-chat-ia'),
            $capability,
            'colectivos-miembros',
            [$this, 'render_pagina_miembros']
        );

        // Página: Nuevo (oculta)
        add_submenu_page(
            null,
            __('Nuevo Colectivo', 'flavor-chat-ia'),
            __('Nuevo', 'flavor-chat-ia'),
            $capability,
            'colectivos-nuevo',
            [$this, 'render_pagina_nuevo']
        );

        // Página: Solicitudes de unión (oculta)
        add_submenu_page(
            null,
            __('Solicitudes de Unión', 'flavor-chat-ia'),
            __('Solicitudes', 'flavor-chat-ia'),
            $capability,
            'colectivos-solicitudes',
            [$this, 'render_pagina_solicitudes']
        );
    }

    /**
     * Renderiza página dashboard
     */
    public function render_pagina_dashboard() {
        $rutaVistaDashboard = dirname(__FILE__) . '/views/dashboard.php';
        if (file_exists($rutaVistaDashboard)) {
            include $rutaVistaDashboard;
        } else {
            // Fallback si no existe dashboard.php
            echo '<div class="wrap">';
            echo '<h1>' . esc_html__('Dashboard Colectivos', 'flavor-chat-ia') . '</h1>';
            $views_path = dirname(__FILE__) . '/views/listado-colectivos.php';
            if (file_exists($views_path)) {
                include $views_path;
            }
            echo '</div>';
        }
    }

    /**
     * Renderiza página de listado
     */
    public function render_pagina_listado() {
        echo '<div class="wrap">';
        echo '<h1>' . esc_html__('Todos los Colectivos', 'flavor-chat-ia') . '</h1>';
        $views_path = dirname(__FILE__) . '/views/listado-colectivos.php';
        if (file_exists($views_path)) {
            include $views_path;
        }
        echo '</div>';
    }

    /**
     * Renderiza página de proyectos
     */
    public function render_pagina_proyectos() {
        echo '<div class="wrap">';
        echo '<h1>' . esc_html__('Proyectos', 'flavor-chat-ia') . '</h1>';
        $views_path = dirname(__FILE__) . '/views/proyectos.php';
        if (file_exists($views_path)) {
            include $views_path;
        }
        echo '</div>';
    }

    /**
     * Renderiza página de asambleas
     */
    public function render_pagina_asambleas() {
        echo '<div class="wrap">';
        echo '<h1>' . esc_html__('Asambleas', 'flavor-chat-ia') . '</h1>';
        $views_path = dirname(__FILE__) . '/views/asambleas.php';
        if (file_exists($views_path)) {
            include $views_path;
        }
        echo '</div>';
    }

    /**
     * Renderiza página de miembros
     */
    public function render_pagina_miembros() {
        $views_path = dirname(__FILE__) . '/views/miembros.php';
        if (file_exists($views_path)) {
            include $views_path;
        } else {
            echo '<div class="wrap"><h1>' . esc_html__('Miembros de Colectivos', 'flavor-chat-ia') . '</h1>';
            echo '<p>' . esc_html__('Vista en desarrollo.', 'flavor-chat-ia') . '</p></div>';
        }
    }

    /**
     * Renderiza página de nuevo colectivo
     */
    public function render_pagina_nuevo() {
        $views_path = dirname(__FILE__) . '/views/nuevo.php';
        if (file_exists($views_path)) {
            include $views_path;
        } else {
            echo '<div class="wrap"><h1>' . esc_html__('Nuevo Colectivo', 'flavor-chat-ia') . '</h1>';
            echo '<p>' . esc_html__('Vista en desarrollo.', 'flavor-chat-ia') . '</p></div>';
        }
    }

    /**
     * Renderiza página de solicitudes de unión
     */
    public function render_pagina_solicitudes() {
        $views_path = dirname(__FILE__) . '/views/solicitudes.php';
        if (file_exists($views_path)) {
            include $views_path;
        } else {
            echo '<div class="wrap"><h1>' . esc_html__('Solicitudes de Unión', 'flavor-chat-ia') . '</h1>';
            echo '<p>' . esc_html__('Vista en desarrollo.', 'flavor-chat-ia') . '</p></div>';
        }
    }

    private function resolve_contextual_colectivo(): ?array {
        global $wpdb;

        $colectivo_id = absint($_GET['colectivo_id'] ?? $_GET['colectivo'] ?? $_GET['id'] ?? 0);
        if (!$colectivo_id) {
            return null;
        }

        $tabla = $wpdb->prefix . 'flavor_colectivos';
        $colectivo = $wpdb->get_row($wpdb->prepare(
            "SELECT id, nombre, estado FROM $tabla WHERE id = %d",
            $colectivo_id
        ), ARRAY_A);

        if (!$colectivo || ($colectivo['estado'] ?? '') === 'eliminado') {
            return null;
        }

        return $colectivo;
    }

    public function render_tab_foro($usuario_id): string {
        $colectivo = $this->resolve_contextual_colectivo();
        if (!$colectivo) {
            return '<p class="flavor-col-error">' . esc_html__('Selecciona un colectivo para ver su foro.', 'flavor-chat-ia') . '</p>';
        }

        return '<div class="flavor-contextual-block">'
            . '<div class="flavor-contextual-header"><h3>' . esc_html__('Foro del colectivo', 'flavor-chat-ia') . '</h3><p>' . esc_html($colectivo['nombre']) . '</p></div>'
            . do_shortcode('[flavor_foros_integrado entidad="colectivo" entidad_id="' . absint($colectivo['id']) . '"]')
            . '</div>';
    }

    public function render_tab_chat($usuario_id): string {
        if (!$usuario_id) {
            return '<p class="flavor-col-error">' . esc_html__('Inicia sesión para acceder al chat del colectivo.', 'flavor-chat-ia') . '</p>';
        }

        $colectivo = $this->resolve_contextual_colectivo();
        if (!$colectivo) {
            return '<p class="flavor-col-error">' . esc_html__('Selecciona un colectivo para ver su chat.', 'flavor-chat-ia') . '</p>';
        }

        $cta = home_url('/mi-portal/chat-grupos/mensajes/?colectivo_id=' . absint($colectivo['id']));

        return '<div class="flavor-contextual-block">'
            . '<div class="flavor-contextual-header"><h3>' . esc_html__('Chat del colectivo', 'flavor-chat-ia') . '</h3><p>' . esc_html($colectivo['nombre']) . '</p>'
            . '<p><a class="button button-primary" href="' . esc_url($cta) . '">' . esc_html__('Abrir chat completo', 'flavor-chat-ia') . '</a></p></div>'
            . do_shortcode('[flavor_chat_grupo_integrado entidad="colectivo" entidad_id="' . absint($colectivo['id']) . '"]')
            . '</div>';
    }

    public function render_tab_multimedia($usuario_id): string {
        $colectivo = $this->resolve_contextual_colectivo();
        if (!$colectivo) {
            return '<p class="flavor-col-error">' . esc_html__('Selecciona un colectivo para ver sus documentos.', 'flavor-chat-ia') . '</p>';
        }

        $cta = home_url('/mi-portal/multimedia/subir/?colectivo_id=' . absint($colectivo['id']));

        return '<div class="flavor-contextual-block">'
            . '<div class="flavor-contextual-header"><h3>' . esc_html__('Documentos y multimedia', 'flavor-chat-ia') . '</h3><p>' . esc_html($colectivo['nombre']) . '</p>'
            . '<p><a class="button" href="' . esc_url($cta) . '">' . esc_html__('Subir archivo', 'flavor-chat-ia') . '</a></p></div>'
            . do_shortcode('[flavor_multimedia_galeria entidad="colectivo" entidad_id="' . absint($colectivo['id']) . '"]')
            . '</div>';
    }

    public function render_tab_red_social($usuario_id): string {
        if (!$usuario_id) {
            return '<p class="flavor-col-error">' . esc_html__('Inicia sesión para ver la actividad social del colectivo.', 'flavor-chat-ia') . '</p>';
        }

        $colectivo = $this->resolve_contextual_colectivo();
        if (!$colectivo) {
            return '<p class="flavor-col-error">' . esc_html__('Selecciona un colectivo para ver su actividad social.', 'flavor-chat-ia') . '</p>';
        }

        $cta = home_url('/mi-portal/red-social/crear/?colectivo_id=' . absint($colectivo['id']));

        return '<div class="flavor-contextual-block">'
            . '<div class="flavor-contextual-header"><h3>' . esc_html__('Actividad social del colectivo', 'flavor-chat-ia') . '</h3><p>' . esc_html($colectivo['nombre']) . '</p>'
            . '<p><a class="button" href="' . esc_url($cta) . '">' . esc_html__('Publicar', 'flavor-chat-ia') . '</a></p></div>'
            . do_shortcode('[flavor_social_feed entidad="colectivo" entidad_id="' . absint($colectivo['id']) . '"]')
            . '</div>';
    }

    /**
     * Configuración para el Module Renderer
     *
     * @return array Configuración completa del módulo
     */
    public static function get_renderer_config(): array {
        return [
            'module'   => 'colectivos',
            'title'    => __('Colectivos', 'flavor-chat-ia'),
            'subtitle' => __('Grupos organizados del barrio', 'flavor-chat-ia'),
            'icon'     => '✊',
            'color'    => 'secondary', // Usa variable CSS --flavor-secondary del tema

            'database' => [
                'table'          => 'flavor_colectivos',
                'status_field'   => 'estado',
                'exclude_status' => 'eliminado',
                'order_by'       => 'created_at DESC',
                'filter_fields'  => ['estado', 'categoria', 'ambito'],
            ],

            'fields' => [
                'titulo'      => 'nombre',
                'descripcion' => 'descripcion',
                'imagen'      => 'logo',
                'estado'      => 'estado',
                'categoria'   => 'categoria',
                'ambito'      => 'ambito',
                'miembros'    => 'num_miembros',
                'user_id'     => 'creador_id',
            ],

            'estados' => [
                'activo'   => ['label' => __('Activo', 'flavor-chat-ia'), 'color' => 'green', 'icon' => '🟢'],
                'inactivo' => ['label' => __('Inactivo', 'flavor-chat-ia'), 'color' => 'gray', 'icon' => '⚫'],
            ],

            'stats' => [
                ['label' => __('Colectivos', 'flavor-chat-ia'), 'icon' => '✊', 'color' => 'rose', 'count_where' => "estado = 'activo'"],
                ['label' => __('Miembros', 'flavor-chat-ia'), 'icon' => '👥', 'color' => 'blue', 'query' => "SELECT COALESCE(SUM(num_miembros), 0) FROM {table} WHERE estado = 'activo'"],
                ['label' => __('Proyectos', 'flavor-chat-ia'), 'icon' => '📋', 'color' => 'green', 'query' => "SELECT COUNT(*) FROM {table}_proyectos WHERE estado = 'activo'"],
                ['label' => __('Asambleas', 'flavor-chat-ia'), 'icon' => '🗣️', 'color' => 'purple', 'query' => "SELECT COUNT(*) FROM {table}_asambleas WHERE fecha > NOW()"],
            ],

            'card' => [
                'color'  => 'rose',
                'icon'   => '✊',
                'fields' => [
                    'id'       => 'id',
                    'title'    => 'nombre',
                    'subtitle' => 'descripcion',
                    'image'    => 'logo',
                    'url'      => 'url',
                ],
                'badge' => [
                    'field'  => 'estado',
                    'colors' => ['activo' => 'green', 'inactivo' => 'gray'],
                ],
                'meta' => [
                    ['icon' => '👥', 'field' => 'miembros', 'suffix' => ' miembros'],
                    ['icon' => '📁', 'field' => 'categoria'],
                ],
            ],

            'tabs' => [
                'listado' => [
                    'label'   => __('Colectivos', 'flavor-chat-ia'),
                    'icon'    => 'dashicons-groups',
                    'content' => 'template:archive.php',
                ],
                'mis-colectivos' => [
                    'label'   => __('Mis Colectivos', 'flavor-chat-ia'),
                    'icon'    => 'dashicons-admin-users',
                    'content' => 'template:mis-colectivos.php',
                ],
                'proyectos' => [
                    'label'   => __('Proyectos', 'flavor-chat-ia'),
                    'icon'    => 'dashicons-portfolio',
                    'content' => 'template:proyectos.php',
                ],
                'asambleas' => [
                    'label'   => __('Asambleas', 'flavor-chat-ia'),
                    'icon'    => 'dashicons-megaphone',
                    'content' => 'template:asambleas.php',
                ],
                'foro' => [
                    'label'          => __('Foro', 'flavor-chat-ia'),
                    'icon'           => 'dashicons-format-chat',
                    'content'        => 'callback:render_tab_foro',
                    'requires_login' => true,
                ],
                'chat' => [
                    'label'          => __('Chat', 'flavor-chat-ia'),
                    'icon'           => 'dashicons-format-status',
                    'content'        => 'callback:render_tab_chat',
                    'requires_login' => true,
                ],
                'documentos' => [
                    'label'   => __('Documentos', 'flavor-chat-ia'),
                    'icon'    => 'dashicons-media-document',
                    'content' => 'callback:render_tab_multimedia',
                ],
                'red-social' => [
                    'label'          => __('Red social', 'flavor-chat-ia'),
                    'icon'           => 'dashicons-share',
                    'content'        => 'callback:render_tab_red_social',
                    'requires_login' => true,
                ],
            ],

            'archive' => [
                'columns'     => 3,
                'per_page'    => 12,
                'filter_field'=> 'categoria',
                'filters' => [
                    ['id' => 'todos', 'label' => __('Todos', 'flavor-chat-ia'), 'active' => true],
                    ['id' => 'social', 'label' => __('Social', 'flavor-chat-ia'), 'icon' => '🤝'],
                    ['id' => 'cultural', 'label' => __('Cultural', 'flavor-chat-ia'), 'icon' => '🎭'],
                    ['id' => 'ecologista', 'label' => __('Ecologista', 'flavor-chat-ia'), 'icon' => '🌿'],
                    ['id' => 'vecinal', 'label' => __('Vecinal', 'flavor-chat-ia'), 'icon' => '🏘️'],
                ],
                'cta_text' => __('Crear colectivo', 'flavor-chat-ia'),
                'cta_icon' => '➕',
                'cta_url'  => home_url('/mi-portal/colectivos/nuevo/'),
                'empty_state' => [
                    'icon'     => '✊',
                    'title'    => __('No hay colectivos', 'flavor-chat-ia'),
                    'text'     => __('Organiza el primer colectivo del barrio', 'flavor-chat-ia'),
                ],
            ],

            'dashboard' => [
                'show_header' => true,
                'header_actions' => [
                    ['label' => __('Crear', 'flavor-chat-ia'), 'icon' => '➕', 'url' => home_url('/mi-portal/colectivos/nuevo/'), 'primary' => true],
                    ['label' => __('Explorar', 'flavor-chat-ia'), 'icon' => '🔍', 'url' => home_url('/mi-portal/colectivos/')],
                ],
                'quick_actions' => [
                    ['title' => __('Explorar', 'flavor-chat-ia'), 'icon' => '🔍', 'color' => 'rose', 'url' => home_url('/mi-portal/colectivos/')],
                    ['title' => __('Mis colectivos', 'flavor-chat-ia'), 'icon' => '👥', 'color' => 'blue', 'url' => home_url('/mi-portal/colectivos/?tab=mis-colectivos')],
                    ['title' => __('Proyectos', 'flavor-chat-ia'), 'icon' => '📋', 'color' => 'green', 'url' => home_url('/mi-portal/colectivos/?tab=proyectos')],
                    ['title' => __('Asambleas', 'flavor-chat-ia'), 'icon' => '🗣️', 'color' => 'purple', 'url' => home_url('/mi-portal/colectivos/?tab=asambleas')],
                ],
                'show_recent' => true,
                'recent_title' => __('Colectivos activos', 'flavor-chat-ia'),
            ],
        ];
    }


    /**
     * Inicializa el dashboard tab del módulo
     */
    private function inicializar_dashboard_tab() {
        $archivo = dirname(__FILE__) . '/class-colectivos-dashboard-tab.php';
        if (file_exists($archivo)) {
            require_once $archivo;
            if (class_exists('Flavor_Colectivos_Dashboard_Tab')) {
                Flavor_Colectivos_Dashboard_Tab::get_instance();
            }
        }
    }

    /**
     * Renderiza la página de configuración
     */
    public function render_admin_configuracion() {
        $template_path = dirname(__FILE__) . '/views/config.php';
        if (file_exists($template_path)) {
            include $template_path;
        }
    }
}

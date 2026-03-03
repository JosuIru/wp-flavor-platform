<?php
/**
 * Módulo de Presupuestos Participativos para Chat IA
 *
 * @package FlavorChatIA
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Módulo de Presupuestos Participativos - Democracia económica directa
 */
class Flavor_Chat_Presupuestos_Participativos_Module extends Flavor_Chat_Module_Base {

    use Flavor_Module_Admin_Pages_Trait;
    use Flavor_Module_Integration_Consumer;

    /**
     * Constructor
     */
    public function __construct() {
        $this->id = 'presupuestos_participativos';
        $this->name = 'Presupuestos Participativos'; // Translation loaded on init
        $this->description = 'Democracia participativa: los vecinos deciden en qué invertir el presupuesto del barrio.'; // Translation loaded on init

        parent::__construct();

        // Admin pages
        add_action('admin_menu', [$this, 'registrar_paginas_admin']);
        // Auto-registered AJAX handlers
        add_action('wp_ajax_presupuestos_participativos_proponer_proyecto', [$this, 'ajax_proponer_proyecto']);
        add_action('wp_ajax_nopriv_presupuestos_participativos_proponer_proyecto', [$this, 'ajax_proponer_proyecto']);
        add_action('wp_ajax_presupuestos_participativos_votar_proyecto', [$this, 'ajax_votar_proyecto']);
        add_action('wp_ajax_nopriv_presupuestos_participativos_votar_proyecto', [$this, 'ajax_votar_proyecto']);
        add_action('wp_ajax_presupuestos_participativos_quitar_voto', [$this, 'ajax_quitar_voto']);
        add_action('wp_ajax_nopriv_presupuestos_participativos_quitar_voto', [$this, 'ajax_quitar_voto']);
        add_action('wp_ajax_presupuestos_participativos_editar_propuesta', [$this, 'ajax_editar_propuesta']);
        add_action('wp_ajax_nopriv_presupuestos_participativos_editar_propuesta', [$this, 'ajax_editar_propuesta']);
        add_action('wp_ajax_presupuestos_participativos_eliminar_propuesta', [$this, 'ajax_eliminar_propuesta']);
        add_action('wp_ajax_nopriv_presupuestos_participativos_eliminar_propuesta', [$this, 'ajax_eliminar_propuesta']);
        add_action('wp_ajax_presupuestos_participativos_subir_imagen', [$this, 'ajax_subir_imagen']);
        add_action('wp_ajax_nopriv_presupuestos_participativos_subir_imagen', [$this, 'ajax_subir_imagen']);
        add_action('wp_ajax_presupuestos_participativos_comentar_proyecto', [$this, 'ajax_comentar_proyecto']);
        add_action('wp_ajax_nopriv_presupuestos_participativos_comentar_proyecto', [$this, 'ajax_comentar_proyecto']);
        add_action('wp_ajax_presupuestos_participativos_reportar_proyecto', [$this, 'ajax_reportar_proyecto']);
        add_action('wp_ajax_nopriv_presupuestos_participativos_reportar_proyecto', [$this, 'ajax_reportar_proyecto']);
        add_action('wp_ajax_presupuestos_participativos_cargar_proyectos', [$this, 'ajax_cargar_proyectos']);
        add_action('wp_ajax_nopriv_presupuestos_participativos_cargar_proyectos', [$this, 'ajax_cargar_proyectos']);
        add_action('wp_ajax_presupuestos_participativos_obtener_proyecto', [$this, 'ajax_obtener_proyecto']);
        add_action('wp_ajax_nopriv_presupuestos_participativos_obtener_proyecto', [$this, 'ajax_obtener_proyecto']);

    }

    /**
     * {@inheritdoc}
     */
    public function can_activate() {
        global $wpdb;
        $tabla_proyectos = $wpdb->prefix . 'flavor_pp_proyectos';

        return Flavor_Chat_Helpers::tabla_existe($tabla_proyectos);
    }

    /**
     * {@inheritdoc}
     */
    public function get_activation_error() {
        if (!$this->can_activate()) {
            return __('Las tablas de Presupuestos Participativos no están creadas. Activa el módulo para crearlas automáticamente.', 'flavor-chat-ia');
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
            'presupuesto_anual' => 100000.00,
            'presupuesto_minimo_proyecto' => 1000.00,
            'presupuesto_maximo_proyecto' => 50000.00,
            'votos_maximos_por_persona' => 3,
            'requiere_verificacion' => true,
            'fase_actual' => 'cerrada', // propuestas, votacion, implementacion, cerrada
            'fecha_inicio_propuestas' => null,
            'fecha_fin_propuestas' => null,
            'fecha_inicio_votacion' => null,
            'fecha_fin_votacion' => null,
            'categorias' => [
                'infraestructura' => __('Infraestructura', 'flavor-chat-ia'),
                'medio_ambiente' => __('Medio Ambiente', 'flavor-chat-ia'),
                'cultura' => __('Cultura y Ocio', 'flavor-chat-ia'),
                'deporte' => __('Deporte', 'flavor-chat-ia'),
                'social' => __('Social', 'flavor-chat-ia'),
                'educacion' => __('Educación', 'flavor-chat-ia'),
                'accesibilidad' => __('Accesibilidad', 'flavor-chat-ia'),
            ],
        ];
    }

    /**
     * Define que tipos de contenido acepta este modulo
     *
     * @return array IDs de providers aceptados
     */
    protected function get_accepted_integrations() {
        return ['multimedia'];
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
                'table'   => $wpdb->prefix . 'flavor_pp_proyectos',
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
        add_action('init', [$this, 'maybe_create_pages']);
        add_action('init', [$this, 'register_shortcodes']);
        add_action('rest_api_init', [$this, 'register_rest_routes']);
        add_action('wp_enqueue_scripts', [$this, 'enqueue_frontend_assets']);
        $this->register_ajax_handlers();
        
        // Cargar Dashboard Tab
        $this->inicializar_dashboard_tab();
        $this->registrar_en_panel_unificado();
    }

    /**
     * Registra los shortcodes del módulo
     */
    public function register_shortcodes() {
        add_shortcode('presupuestos_listado', [$this, 'shortcode_listado_proyectos']);
        add_shortcode('presupuestos_proponer', [$this, 'shortcode_formulario_propuesta']);
        add_shortcode('presupuestos_votar', [$this, 'shortcode_interfaz_votacion']);
        add_shortcode('presupuestos_resultados', [$this, 'shortcode_resultados']);
        add_shortcode('presupuestos_mi_proyecto', [$this, 'shortcode_mis_propuestas']);
        add_shortcode('presupuesto_estado_actual', [$this, 'shortcode_estado_actual']);
    }

    /**
     * Registra los handlers AJAX del módulo
     */
    public function register_ajax_handlers() {
        // Acciones que requieren autenticación
        $acciones_autenticadas = [
            'pp_proponer_proyecto',
            'pp_votar_proyecto',
            'pp_quitar_voto',
            'pp_editar_propuesta',
            'pp_eliminar_propuesta',
            'pp_subir_imagen',
            'pp_comentar_proyecto',
            'pp_reportar_proyecto',
        ];

        foreach ($acciones_autenticadas as $accion) {
            add_action('wp_ajax_' . $accion, [$this, 'ajax_' . str_replace('pp_', '', $accion)]);
        }

        // Acciones públicas (solo lectura)
        $acciones_publicas = [
            'pp_cargar_proyectos',
            'pp_obtener_proyecto',
        ];

        foreach ($acciones_publicas as $accion) {
            add_action('wp_ajax_' . $accion, [$this, 'ajax_' . str_replace('pp_', '', $accion)]);
            add_action('wp_ajax_nopriv_' . $accion, [$this, 'ajax_' . str_replace('pp_', '', $accion)]);
        }
    }

    /**
     * Encola los assets del frontend
     */
    public function enqueue_frontend_assets() {
        if (!$this->should_load_assets()) {
            return;
        }

        $ruta_modulo = plugin_dir_url(__FILE__);

        // CSS
        wp_enqueue_style(
            'flavor-presupuestos',
            $ruta_modulo . 'assets/css/presupuestos.css',
            [],
            '1.0.0'
        );

        // JS
        wp_enqueue_script(
            'flavor-presupuestos',
            $ruta_modulo . 'assets/js/presupuestos.js',
            ['jquery'],
            '1.0.0',
            true
        );

        // Configuración para JS
        $configuracion = $this->settings;
        wp_localize_script('flavor-presupuestos', 'flavorPresupuestosConfig', [
            'ajaxUrl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('flavor_presupuestos_nonce'),
            'votosMaximos' => intval($configuracion['votos_maximos_por_persona'] ?? 3),
            'presupuestoMinimo' => floatval($configuracion['presupuesto_minimo_proyecto'] ?? 1000),
            'presupuestoMaximo' => floatval($configuracion['presupuesto_maximo_proyecto'] ?? 50000),
            'strings' => [
                'error' => __('Ha ocurrido un error. Inténtalo de nuevo.', 'flavor-chat-ia'),
                'cargando' => __('Cargando...', 'flavor-chat-ia'),
                'confirmVoto' => __('¿Confirmas tu voto para este proyecto?', 'flavor-chat-ia'),
                'votoRegistrado' => __('¡Voto registrado correctamente!', 'flavor-chat-ia'),
                'limiteVotos' => __('Has alcanzado el límite de votos.', 'flavor-chat-ia'),
                'yaVotado' => __('Ya has votado este proyecto.', 'flavor-chat-ia'),
                'propuestaEnviada' => __('¡Propuesta enviada correctamente!', 'flavor-chat-ia'),
            ],
        ]);
    }

    /**
     * Verifica si se deben cargar los assets
     *
     * @return bool
     */
    private function should_load_assets() {
        global $post;
        if (!$post) {
            return false;
        }

        $shortcodes = [
            'presupuestos_listado',
            'presupuestos_proponer',
            'presupuestos_votar',
            'presupuestos_resultados',
            'presupuestos_mi_proyecto',
        ];

        foreach ($shortcodes as $shortcode) {
            if (has_shortcode($post->post_content, $shortcode)) {
                return true;
            }
        }

        return false;
    }

    // =========================================================================
    // Shortcodes
    // =========================================================================

    /**
     * Shortcode: Listado de proyectos
     *
     * @param array $atts Atributos del shortcode.
     * @return string
     */
    public function shortcode_listado_proyectos($atts) {
        $atributos = shortcode_atts([
            'categoria' => '',
            'estado' => '',
            'columnas' => 3,
            'limite' => 12,
            'mostrar_filtros' => 'si',
            'ordenar' => 'votos',
        ], $atts);

        $resultado = $this->action_listar_proyectos([
            'categoria' => $atributos['categoria'],
            'estado' => $atributos['estado'],
            'limite' => intval($atributos['limite']),
        ]);

        $proyectos = $resultado['success'] ? $resultado['proyectos'] : [];
        $edicion = $resultado['edicion'] ?? '';
        $fase = $resultado['fase'] ?? 'cerrada';
        $categorias = $this->settings['categorias'] ?? [];
        $identificador_usuario = get_current_user_id();

        // Obtener votos del usuario actual
        $votos_usuario = [];
        if ($identificador_usuario) {
            $resultado_votos = $this->api_mis_votos(new WP_REST_Request());
            if ($resultado_votos instanceof WP_REST_Response) {
                $datos_votos = $resultado_votos->get_data();
                if (!empty($datos_votos['votos'])) {
                    $votos_usuario = array_column($datos_votos['votos'], 'proyecto_id');
                }
            }
        }

        ob_start();
        include dirname(__FILE__) . '/views/listado-proyectos.php';
        return ob_get_clean();
    }

    /**
     * Shortcode: Formulario de propuesta
     *
     * @param array $atts Atributos del shortcode.
     * @return string
     */
    public function shortcode_formulario_propuesta($atts) {
        if (!is_user_logged_in()) {
            return '<div class="flavor-pp-notice flavor-pp-notice-warning">' .
                   sprintf(
                       __('Debes <a href="%s">iniciar sesión</a> para proponer un proyecto.', 'flavor-chat-ia'),
                       wp_login_url(get_permalink())
                   ) .
                   '</div>';
        }

        // Verificar fase
        global $wpdb;
        $tabla_ediciones = $wpdb->prefix . 'flavor_pp_ediciones';
        $edicion = $wpdb->get_row(
            "SELECT * FROM $tabla_ediciones WHERE fase = 'propuestas' ORDER BY anio DESC LIMIT 1"
        );

        if (!$edicion) {
            return '<div class="flavor-pp-notice flavor-pp-notice-info">' .
                   __('No estamos en fase de recepción de propuestas. Consulta el calendario del proceso participativo.', 'flavor-chat-ia') .
                   '</div>';
        }

        $categorias = $this->settings['categorias'] ?? [];
        $configuracion = $this->settings;

        ob_start();
        include dirname(__FILE__) . '/views/formulario-propuesta.php';
        return ob_get_clean();
    }

    /**
     * Shortcode: Interfaz de votación
     *
     * @param array $atts Atributos del shortcode.
     * @return string
     */
    public function shortcode_interfaz_votacion($atts) {
        $atributos = shortcode_atts([
            'columnas' => 2,
            'limite' => 20,
        ], $atts);

        if (!is_user_logged_in()) {
            return '<div class="flavor-pp-notice flavor-pp-notice-warning">' .
                   sprintf(
                       __('Debes <a href="%s">iniciar sesión</a> para votar.', 'flavor-chat-ia'),
                       wp_login_url(get_permalink())
                   ) .
                   '</div>';
        }

        // Verificar fase
        global $wpdb;
        $tabla_ediciones = $wpdb->prefix . 'flavor_pp_ediciones';
        $edicion = $wpdb->get_row(
            "SELECT * FROM $tabla_ediciones WHERE fase = 'votacion' ORDER BY anio DESC LIMIT 1"
        );

        if (!$edicion) {
            return '<div class="flavor-pp-notice flavor-pp-notice-info">' .
                   __('No estamos en fase de votación. Consulta el calendario del proceso participativo.', 'flavor-chat-ia') .
                   '</div>';
        }

        // Obtener proyectos disponibles para votar
        $tabla_proyectos = $wpdb->prefix . 'flavor_pp_proyectos';
        $proyectos = $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM $tabla_proyectos
             WHERE edicion_id = %d AND estado IN ('validado', 'en_votacion')
             ORDER BY votos_recibidos DESC, fecha_creacion DESC
             LIMIT %d",
            $edicion->id,
            intval($atributos['limite'])
        ));

        // Obtener votos del usuario
        $identificador_usuario = get_current_user_id();
        $tabla_votos = $wpdb->prefix . 'flavor_pp_votos';
        $votos_usuario = $wpdb->get_col($wpdb->prepare(
            "SELECT proyecto_id FROM $tabla_votos WHERE usuario_id = %d AND edicion_id = %d",
            $identificador_usuario,
            $edicion->id
        ));

        $configuracion = $this->settings;
        $votos_maximos = intval($configuracion['votos_maximos_por_persona'] ?? 3);
        $votos_restantes = max(0, $votos_maximos - count($votos_usuario));
        $categorias = $configuracion['categorias'] ?? [];

        ob_start();
        include dirname(__FILE__) . '/views/interfaz-votacion.php';
        return ob_get_clean();
    }

    /**
     * Shortcode: Resultados de votación
     *
     * @param array $atts Atributos del shortcode.
     * @return string
     */
    public function shortcode_resultados($atts) {
        $atributos = shortcode_atts([
            'edicion' => '',
            'limite' => 10,
            'columnas' => 2,
        ], $atts);

        global $wpdb;
        $tabla_ediciones = $wpdb->prefix . 'flavor_pp_ediciones';
        $tabla_proyectos = $wpdb->prefix . 'flavor_pp_proyectos';
        $tabla_votos = $wpdb->prefix . 'flavor_pp_votos';

        // Obtener edición
        if (!empty($atributos['edicion'])) {
            $edicion = $wpdb->get_row($wpdb->prepare(
                "SELECT * FROM $tabla_ediciones WHERE anio = %d",
                intval($atributos['edicion'])
            ));
        } else {
            $edicion = $wpdb->get_row(
                "SELECT * FROM $tabla_ediciones ORDER BY anio DESC LIMIT 1"
            );
        }

        if (!$edicion) {
            return '<div class="flavor-pp-notice flavor-pp-notice-info">' .
                   __('No hay ediciones de presupuestos participativos disponibles.', 'flavor-chat-ia') .
                   '</div>';
        }

        // Obtener proyectos ordenados por votos
        $proyectos_ranking = $wpdb->get_results($wpdb->prepare(
            "SELECT p.*, COUNT(v.id) as total_votos
             FROM $tabla_proyectos p
             LEFT JOIN $tabla_votos v ON p.id = v.proyecto_id
             WHERE p.edicion_id = %d AND p.estado IN ('validado', 'en_votacion', 'seleccionado', 'en_ejecucion', 'ejecutado')
             GROUP BY p.id
             ORDER BY total_votos DESC
             LIMIT %d",
            $edicion->id,
            intval($atributos['limite'])
        ));

        // Estadísticas
        $total_votantes = (int) $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(DISTINCT usuario_id) FROM $tabla_votos WHERE edicion_id = %d",
            $edicion->id
        ));

        $total_proyectos = (int) $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM $tabla_proyectos WHERE edicion_id = %d",
            $edicion->id
        ));

        ob_start();
        include dirname(__FILE__) . '/views/resultados.php';
        return ob_get_clean();
    }

    /**
     * Shortcode: Mis propuestas
     *
     * @param array $atts Atributos del shortcode.
     * @return string
     */
    public function shortcode_mis_propuestas($atts) {
        if (!is_user_logged_in()) {
            return '<div class="flavor-pp-notice flavor-pp-notice-warning">' .
                   sprintf(
                       __('Debes <a href="%s">iniciar sesión</a> para ver tus propuestas.', 'flavor-chat-ia'),
                       wp_login_url(get_permalink())
                   ) .
                   '</div>';
        }

        $atributos = shortcode_atts([
            'limite' => 10,
        ], $atts);

        global $wpdb;
        $tabla_proyectos = $wpdb->prefix . 'flavor_pp_proyectos';
        $identificador_usuario = get_current_user_id();

        $propuestas = $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM $tabla_proyectos
             WHERE proponente_id = %d
             ORDER BY fecha_creacion DESC
             LIMIT %d",
            $identificador_usuario,
            intval($atributos['limite'])
        ));

        $categorias = $this->settings['categorias'] ?? [];

        ob_start();
        include dirname(__FILE__) . '/views/mis-propuestas.php';
        return ob_get_clean();
    }

    /**
     * Shortcode: Estado actual del presupuesto participativo
     * Muestra resumen del ciclo activo con estadísticas
     *
     * @param array $atts Atributos del shortcode
     * @return string HTML del widget de estado
     */
    public function shortcode_estado_actual($atts) {
        $atributos = shortcode_atts([
            'mostrar_grafico' => 'yes',
        ], $atts);

        global $wpdb;
        $tabla_ciclos = $wpdb->prefix . 'flavor_pp_ciclos';
        $tabla_proyectos = $wpdb->prefix . 'flavor_pp_proyectos';
        $tabla_votos = $wpdb->prefix . 'flavor_pp_votos';

        // Obtener ciclo activo
        $ciclo_activo = $wpdb->get_row(
            "SELECT * FROM $tabla_ciclos WHERE estado = 'activo' ORDER BY fecha_inicio DESC LIMIT 1"
        );

        // Estadísticas generales
        $total_proyectos = $wpdb->get_var("SELECT COUNT(*) FROM $tabla_proyectos WHERE estado != 'borrador'");
        $total_votos = $wpdb->get_var("SELECT COUNT(*) FROM $tabla_votos");
        $presupuesto_total = $this->settings['presupuesto_anual'] ?? 0;
        $presupuesto_asignado = $wpdb->get_var(
            "SELECT COALESCE(SUM(presupuesto), 0) FROM $tabla_proyectos WHERE estado = 'aprobado'"
        );

        // Proyectos por categoría
        $proyectos_por_categoria = $wpdb->get_results(
            "SELECT categoria, COUNT(*) as total FROM $tabla_proyectos
             WHERE estado != 'borrador' GROUP BY categoria"
        );

        $categorias = $this->settings['categorias'] ?? [
            'social' => __('Social', 'flavor-chat-ia'),
            'infraestructura' => __('Infraestructura', 'flavor-chat-ia'),
            'cultura' => __('Cultura', 'flavor-chat-ia'),
            'medioambiente' => __('Medio Ambiente', 'flavor-chat-ia'),
        ];

        ob_start();
        ?>
        <div class="flavor-pp-estado-actual">
            <?php if ($ciclo_activo): ?>
                <div class="flavor-pp-ciclo-info">
                    <h4><?php echo esc_html($ciclo_activo->nombre); ?></h4>
                    <p class="flavor-pp-fase">
                        <span class="dashicons dashicons-calendar-alt"></span>
                        <?php echo esc_html(ucfirst($ciclo_activo->fase_actual ?? __('En curso', 'flavor-chat-ia'))); ?>
                    </p>
                </div>
            <?php else: ?>
                <p class="flavor-pp-no-ciclo"><?php _e('No hay ciclo activo actualmente.', 'flavor-chat-ia'); ?></p>
            <?php endif; ?>

            <div class="flavor-kpi-grid flavor-grid-2">
                <div class="flavor-kpi-card">
                    <div class="flavor-kpi-icono"><span class="dashicons dashicons-portfolio"></span></div>
                    <div class="flavor-kpi-contenido">
                        <span class="flavor-kpi-valor"><?php echo absint($total_proyectos); ?></span>
                        <span class="flavor-kpi-label"><?php _e('Proyectos', 'flavor-chat-ia'); ?></span>
                    </div>
                </div>
                <div class="flavor-kpi-card">
                    <div class="flavor-kpi-icono"><span class="dashicons dashicons-thumbs-up"></span></div>
                    <div class="flavor-kpi-contenido">
                        <span class="flavor-kpi-valor"><?php echo absint($total_votos); ?></span>
                        <span class="flavor-kpi-label"><?php _e('Votos', 'flavor-chat-ia'); ?></span>
                    </div>
                </div>
            </div>

            <?php if ($presupuesto_total > 0): ?>
                <div class="flavor-pp-presupuesto-barra">
                    <div class="flavor-pp-barra-header">
                        <span><?php _e('Presupuesto asignado', 'flavor-chat-ia'); ?></span>
                        <span><?php echo number_format($presupuesto_asignado, 0, ',', '.'); ?>€ / <?php echo number_format($presupuesto_total, 0, ',', '.'); ?>€</span>
                    </div>
                    <div class="flavor-pp-barra-contenedor">
                        <div class="flavor-pp-barra-progreso" style="width: <?php echo min(100, ($presupuesto_asignado / $presupuesto_total) * 100); ?>%"></div>
                    </div>
                </div>
            <?php endif; ?>

            <?php if (!empty($proyectos_por_categoria)): ?>
                <div class="flavor-pp-categorias-resumen">
                    <h5><?php _e('Por categoría', 'flavor-chat-ia'); ?></h5>
                    <ul>
                        <?php foreach ($proyectos_por_categoria as $item): ?>
                            <li>
                                <span class="flavor-pp-cat-nombre"><?php echo esc_html($categorias[$item->categoria] ?? $item->categoria); ?></span>
                                <span class="flavor-pp-cat-count"><?php echo absint($item->total); ?></span>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            <?php endif; ?>
        </div>
        <?php
        return ob_get_clean();
    }

    // =========================================================================
    // AJAX Handlers
    // =========================================================================

    /**
     * Verifica la seguridad de las peticiones AJAX
     *
     * @return bool
     */
    private function verificar_seguridad_ajax() {
        if (!check_ajax_referer('flavor_presupuestos_nonce', 'nonce', false)) {
            wp_send_json_error([
                'message' => __('Error de seguridad. Recarga la página e inténtalo de nuevo.', 'flavor-chat-ia'),
                'code' => 'invalid_nonce'
            ], 403);
            return false;
        }
        return true;
    }

    /**
     * AJAX: Proponer proyecto
     */
    public function ajax_proponer_proyecto() {
        if (!$this->verificar_seguridad_ajax()) {
            return;
        }

        if (!is_user_logged_in()) {
            wp_send_json_error([
                'message' => __('Debes iniciar sesión.', 'flavor-chat-ia'),
                'redirect' => wp_login_url()
            ], 401);
            return;
        }

        $resultado = $this->action_proponer_proyecto([
            'titulo' => sanitize_text_field($_POST['titulo'] ?? ''),
            'descripcion' => sanitize_textarea_field($_POST['descripcion'] ?? ''),
            'categoria' => sanitize_text_field($_POST['categoria'] ?? 'social'),
            'presupuesto' => floatval($_POST['presupuesto'] ?? 0),
            'ubicacion' => sanitize_text_field($_POST['ubicacion'] ?? ''),
        ]);

        if ($resultado['success']) {
            wp_send_json_success($resultado);
        } else {
            wp_send_json_error($resultado, 400);
        }
    }

    /**
     * AJAX: Votar proyecto
     */
    public function ajax_votar_proyecto() {
        if (!$this->verificar_seguridad_ajax()) {
            return;
        }

        if (!is_user_logged_in()) {
            wp_send_json_error([
                'message' => __('Debes iniciar sesión.', 'flavor-chat-ia'),
                'redirect' => wp_login_url()
            ], 401);
            return;
        }

        $resultado = $this->action_votar_proyecto_individual([
            'proyecto_id' => absint($_POST['proyecto_id'] ?? 0),
            'prioridad' => absint($_POST['prioridad'] ?? 1),
        ]);

        if ($resultado['success']) {
            wp_send_json_success($resultado);
        } else {
            wp_send_json_error($resultado, 400);
        }
    }

    /**
     * AJAX: Quitar voto
     */
    public function ajax_quitar_voto() {
        if (!$this->verificar_seguridad_ajax()) {
            return;
        }

        if (!is_user_logged_in()) {
            wp_send_json_error([
                'message' => __('Debes iniciar sesión.', 'flavor-chat-ia'),
            ], 401);
            return;
        }

        $identificador_proyecto = absint($_POST['proyecto_id'] ?? 0);
        $identificador_usuario = get_current_user_id();

        global $wpdb;
        $tabla_votos = $wpdb->prefix . 'flavor_pp_votos';
        $tabla_ediciones = $wpdb->prefix . 'flavor_pp_ediciones';

        // Verificar fase de votación
        $edicion = $wpdb->get_row(
            "SELECT * FROM $tabla_ediciones WHERE fase = 'votacion' ORDER BY anio DESC LIMIT 1"
        );

        if (!$edicion) {
            wp_send_json_error([
                'message' => __('No estamos en fase de votación.', 'flavor-chat-ia'),
            ], 400);
            return;
        }

        $eliminado = $wpdb->delete(
            $tabla_votos,
            [
                'proyecto_id' => $identificador_proyecto,
                'usuario_id' => $identificador_usuario,
                'edicion_id' => $edicion->id,
            ],
            ['%d', '%d', '%d']
        );

        if ($eliminado) {
            $this->actualizar_contadores_votos($edicion->id);
            wp_send_json_success([
                'message' => __('Voto eliminado correctamente.', 'flavor-chat-ia'),
            ]);
        } else {
            wp_send_json_error([
                'message' => __('No se encontró el voto.', 'flavor-chat-ia'),
            ], 404);
        }
    }

    /**
     * AJAX: Editar propuesta
     */
    public function ajax_editar_propuesta() {
        if (!$this->verificar_seguridad_ajax()) {
            return;
        }

        if (!is_user_logged_in()) {
            wp_send_json_error([
                'message' => __('Debes iniciar sesión.', 'flavor-chat-ia'),
            ], 401);
            return;
        }

        $identificador_proyecto = absint($_POST['proyecto_id'] ?? 0);
        $identificador_usuario = get_current_user_id();

        global $wpdb;
        $tabla_proyectos = $wpdb->prefix . 'flavor_pp_proyectos';

        // Verificar propiedad y estado
        $proyecto = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM $tabla_proyectos WHERE id = %d AND proponente_id = %d",
            $identificador_proyecto,
            $identificador_usuario
        ));

        if (!$proyecto) {
            wp_send_json_error([
                'message' => __('Proyecto no encontrado o no tienes permisos.', 'flavor-chat-ia'),
            ], 404);
            return;
        }

        if (!in_array($proyecto->estado, ['borrador', 'pendiente_validacion'], true)) {
            wp_send_json_error([
                'message' => __('Este proyecto ya no puede ser editado.', 'flavor-chat-ia'),
            ], 400);
            return;
        }

        $actualizado = $wpdb->update(
            $tabla_proyectos,
            [
                'titulo' => sanitize_text_field($_POST['titulo'] ?? $proyecto->titulo),
                'descripcion' => sanitize_textarea_field($_POST['descripcion'] ?? $proyecto->descripcion),
                'categoria' => sanitize_text_field($_POST['categoria'] ?? $proyecto->categoria),
                'presupuesto_solicitado' => floatval($_POST['presupuesto'] ?? $proyecto->presupuesto_solicitado),
                'ubicacion' => sanitize_text_field($_POST['ubicacion'] ?? $proyecto->ubicacion),
            ],
            ['id' => $identificador_proyecto],
            ['%s', '%s', '%s', '%f', '%s'],
            ['%d']
        );

        if ($actualizado !== false) {
            wp_send_json_success([
                'message' => __('Propuesta actualizada correctamente.', 'flavor-chat-ia'),
            ]);
        } else {
            wp_send_json_error([
                'message' => __('Error al actualizar la propuesta.', 'flavor-chat-ia'),
            ], 500);
        }
    }

    /**
     * AJAX: Eliminar propuesta
     */
    public function ajax_eliminar_propuesta() {
        if (!$this->verificar_seguridad_ajax()) {
            return;
        }

        if (!is_user_logged_in()) {
            wp_send_json_error([
                'message' => __('Debes iniciar sesión.', 'flavor-chat-ia'),
            ], 401);
            return;
        }

        $identificador_proyecto = absint($_POST['proyecto_id'] ?? 0);
        $identificador_usuario = get_current_user_id();

        global $wpdb;
        $tabla_proyectos = $wpdb->prefix . 'flavor_pp_proyectos';

        // Verificar propiedad y estado
        $proyecto = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM $tabla_proyectos WHERE id = %d AND proponente_id = %d",
            $identificador_proyecto,
            $identificador_usuario
        ));

        if (!$proyecto) {
            wp_send_json_error([
                'message' => __('Proyecto no encontrado o no tienes permisos.', 'flavor-chat-ia'),
            ], 404);
            return;
        }

        if (!in_array($proyecto->estado, ['borrador', 'pendiente_validacion'], true)) {
            wp_send_json_error([
                'message' => __('Este proyecto ya no puede ser eliminado.', 'flavor-chat-ia'),
            ], 400);
            return;
        }

        $eliminado = $wpdb->delete(
            $tabla_proyectos,
            ['id' => $identificador_proyecto],
            ['%d']
        );

        if ($eliminado) {
            wp_send_json_success([
                'message' => __('Propuesta eliminada correctamente.', 'flavor-chat-ia'),
            ]);
        } else {
            wp_send_json_error([
                'message' => __('Error al eliminar la propuesta.', 'flavor-chat-ia'),
            ], 500);
        }
    }

    /**
     * AJAX: Subir imagen
     */
    public function ajax_subir_imagen() {
        if (!$this->verificar_seguridad_ajax()) {
            return;
        }

        if (!is_user_logged_in()) {
            wp_send_json_error([
                'message' => __('Debes iniciar sesión.', 'flavor-chat-ia'),
            ], 401);
            return;
        }

        if (empty($_FILES['imagen'])) {
            wp_send_json_error([
                'message' => __('No se ha enviado ninguna imagen.', 'flavor-chat-ia'),
            ], 400);
            return;
        }

        require_once ABSPATH . 'wp-admin/includes/file.php';
        require_once ABSPATH . 'wp-admin/includes/image.php';
        require_once ABSPATH . 'wp-admin/includes/media.php';

        $attachment_id = media_handle_upload('imagen', 0);

        if (is_wp_error($attachment_id)) {
            wp_send_json_error([
                'message' => $attachment_id->get_error_message(),
            ], 400);
            return;
        }

        wp_send_json_success([
            'attachment_id' => $attachment_id,
            'url' => wp_get_attachment_url($attachment_id),
            'thumbnail' => wp_get_attachment_image_url($attachment_id, 'medium'),
        ]);
    }

    /**
     * AJAX: Comentar proyecto
     */
    public function ajax_comentar_proyecto() {
        if (!$this->verificar_seguridad_ajax()) {
            return;
        }

        if (!is_user_logged_in()) {
            wp_send_json_error([
                'message' => __('Debes iniciar sesión.', 'flavor-chat-ia'),
            ], 401);
            return;
        }

        // Por implementar: Sistema de comentarios
        wp_send_json_error([
            'message' => __('Sistema de comentarios en desarrollo.', 'flavor-chat-ia'),
        ], 501);
    }

    /**
     * AJAX: Reportar proyecto
     */
    public function ajax_reportar_proyecto() {
        if (!$this->verificar_seguridad_ajax()) {
            return;
        }

        if (!is_user_logged_in()) {
            wp_send_json_error([
                'message' => __('Debes iniciar sesión.', 'flavor-chat-ia'),
            ], 401);
            return;
        }

        // Por implementar: Sistema de reportes
        wp_send_json_success([
            'message' => __('Gracias por tu reporte. Lo revisaremos pronto.', 'flavor-chat-ia'),
        ]);
    }

    /**
     * AJAX: Cargar más proyectos (paginación)
     */
    public function ajax_cargar_proyectos() {
        if (!$this->verificar_seguridad_ajax()) {
            return;
        }

        $pagina = absint($_POST['pagina'] ?? 1);
        $limite = absint($_POST['limite'] ?? 12);
        $categoria = sanitize_text_field($_POST['categoria'] ?? '');
        $ordenar = sanitize_text_field($_POST['ordenar'] ?? 'votos');

        $offset = ($pagina - 1) * $limite;

        global $wpdb;
        $tabla_proyectos = $wpdb->prefix . 'flavor_pp_proyectos';
        $tabla_ediciones = $wpdb->prefix . 'flavor_pp_ediciones';

        $edicion = $wpdb->get_row(
            "SELECT * FROM $tabla_ediciones WHERE fase != 'cerrada' ORDER BY anio DESC LIMIT 1"
        );

        if (!$edicion) {
            wp_send_json_success([
                'proyectos' => [],
                'hay_mas' => false,
            ]);
            return;
        }

        $condiciones_where = ["edicion_id = %d", "estado IN ('validado', 'en_votacion', 'seleccionado')"];
        $valores_preparados = [$edicion->id];

        if (!empty($categoria)) {
            $condiciones_where[] = "categoria = %s";
            $valores_preparados[] = $categoria;
        }

        $clausula_where = implode(' AND ', $condiciones_where);

        $orden_sql = match ($ordenar) {
            'recientes' => 'fecha_creacion DESC',
            'presupuesto' => 'presupuesto_solicitado DESC',
            default => 'votos_recibidos DESC',
        };

        $valores_preparados[] = $limite + 1; // +1 para verificar si hay más
        $valores_preparados[] = $offset;

        $proyectos = $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM $tabla_proyectos WHERE $clausula_where ORDER BY $orden_sql LIMIT %d OFFSET %d",
            ...$valores_preparados
        ));

        $hay_mas = count($proyectos) > $limite;
        if ($hay_mas) {
            array_pop($proyectos); // Quitar el proyecto extra
        }

        $proyectos_formateados = array_map(function ($proyecto) {
            return [
                'id' => $proyecto->id,
                'titulo' => $proyecto->titulo,
                'descripcion' => wp_trim_words($proyecto->descripcion, 30),
                'categoria' => $proyecto->categoria,
                'presupuesto' => floatval($proyecto->presupuesto_solicitado),
                'presupuesto_fmt' => number_format($proyecto->presupuesto_solicitado, 0, ',', '.') . ' €',
                'votos' => intval($proyecto->votos_recibidos),
                'estado' => $proyecto->estado,
                'ubicacion' => $proyecto->ubicacion,
                'imagen' => $proyecto->imagenes ? explode(',', $proyecto->imagenes)[0] : '',
            ];
        }, $proyectos);

        wp_send_json_success([
            'proyectos' => $proyectos_formateados,
            'hay_mas' => $hay_mas,
            'pagina' => $pagina,
        ]);
    }

    /**
     * AJAX: Obtener detalle de proyecto
     */
    public function ajax_obtener_proyecto() {
        $identificador_proyecto = absint($_POST['proyecto_id'] ?? $_GET['proyecto_id'] ?? 0);

        if (!$identificador_proyecto) {
            wp_send_json_error([
                'message' => __('ID de proyecto no válido.', 'flavor-chat-ia'),
            ], 400);
            return;
        }

        global $wpdb;
        $tabla_proyectos = $wpdb->prefix . 'flavor_pp_proyectos';

        $proyecto = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM $tabla_proyectos WHERE id = %d",
            $identificador_proyecto
        ));

        if (!$proyecto) {
            wp_send_json_error([
                'message' => __('Proyecto no encontrado.', 'flavor-chat-ia'),
            ], 404);
            return;
        }

        $proponente = $proyecto->proponente_id ? get_user_by('ID', $proyecto->proponente_id) : null;
        $categorias = $this->settings['categorias'] ?? [];

        wp_send_json_success([
            'proyecto' => [
                'id' => $proyecto->id,
                'titulo' => $proyecto->titulo,
                'descripcion' => $proyecto->descripcion,
                'categoria' => $proyecto->categoria,
                'categoria_label' => $categorias[$proyecto->categoria] ?? ucfirst($proyecto->categoria),
                'ubicacion' => $proyecto->ubicacion,
                'presupuesto' => floatval($proyecto->presupuesto_solicitado),
                'presupuesto_fmt' => number_format($proyecto->presupuesto_solicitado, 0, ',', '.') . ' €',
                'estado' => $proyecto->estado,
                'votos' => intval($proyecto->votos_recibidos),
                'ranking' => intval($proyecto->ranking),
                'porcentaje_ejecucion' => intval($proyecto->porcentaje_ejecucion),
                'fecha_creacion' => $proyecto->fecha_creacion,
                'imagenes' => $proyecto->imagenes ? explode(',', $proyecto->imagenes) : [],
                'proponente' => $proponente ? [
                    'nombre' => $proponente->display_name,
                    'avatar' => get_avatar_url($proponente->ID, ['size' => 64]),
                ] : null,
            ],
        ]);
    }

    /**
     * Registrar rutas REST API para APKs
     */
    public function register_rest_routes() {
        $namespace = 'flavor/v1';

        // Listar proyectos/propuestas
        register_rest_route($namespace, '/presupuestos', [
            'methods' => 'GET',
            'callback' => [$this, 'api_listar_proyectos'],
            'permission_callback' => '__return_true',
            'args' => [
                'categoria' => [
                    'type' => 'string',
                    'description' => 'Filtrar por categoría del proyecto',
                ],
                'estado' => [
                    'type' => 'string',
                    'enum' => ['borrador', 'pendiente_validacion', 'validado', 'en_votacion', 'seleccionado', 'en_ejecucion', 'ejecutado', 'rechazado'],
                ],
                'limite' => [
                    'type' => 'integer',
                    'default' => 20,
                ],
            ],
        ]);

        // Obtener un proyecto específico
        register_rest_route($namespace, '/presupuestos/(?P<id>\d+)', [
            'methods' => 'GET',
            'callback' => [$this, 'api_obtener_proyecto'],
            'permission_callback' => '__return_true',
            'args' => [
                'id' => [
                    'required' => true,
                    'type' => 'integer',
                ],
            ],
        ]);

        // Crear nueva propuesta
        register_rest_route($namespace, '/presupuestos', [
            'methods' => 'POST',
            'callback' => [$this, 'api_crear_propuesta'],
            'permission_callback' => [$this, 'verificar_usuario_autenticado'],
            'args' => [
                'titulo' => [
                    'required' => true,
                    'type' => 'string',
                ],
                'descripcion' => [
                    'required' => true,
                    'type' => 'string',
                ],
                'categoria' => [
                    'type' => 'string',
                    'default' => 'social',
                ],
                'presupuesto' => [
                    'required' => true,
                    'type' => 'number',
                ],
                'ubicacion' => [
                    'type' => 'string',
                ],
            ],
        ]);

        // Votar proyecto
        register_rest_route($namespace, '/presupuestos/(?P<id>\d+)/votar', [
            'methods' => 'POST',
            'callback' => [$this, 'api_votar_proyecto'],
            'permission_callback' => [$this, 'verificar_usuario_autenticado'],
            'args' => [
                'id' => [
                    'required' => true,
                    'type' => 'integer',
                ],
                'prioridad' => [
                    'type' => 'integer',
                    'default' => 1,
                ],
            ],
        ]);

        // Ver resultados de votación
        register_rest_route($namespace, '/presupuestos/resultados', [
            'methods' => 'GET',
            'callback' => [$this, 'api_resultados'],
            'permission_callback' => '__return_true',
            'args' => [
                'edicion_id' => [
                    'type' => 'integer',
                ],
                'limite' => [
                    'type' => 'integer',
                    'default' => 20,
                ],
            ],
        ]);

        // Ver propuestas del usuario
        register_rest_route($namespace, '/presupuestos/mis-propuestas', [
            'methods' => 'GET',
            'callback' => [$this, 'api_mis_propuestas'],
            'permission_callback' => [$this, 'verificar_usuario_autenticado'],
            'args' => [
                'estado' => [
                    'type' => 'string',
                ],
                'limite' => [
                    'type' => 'integer',
                    'default' => 20,
                ],
            ],
        ]);

        // Obtener información de la edición actual
        register_rest_route($namespace, '/presupuestos/edicion', [
            'methods' => 'GET',
            'callback' => [$this, 'api_info_edicion'],
            'permission_callback' => '__return_true',
        ]);

        // Ver mis votos
        register_rest_route($namespace, '/presupuestos/mis-votos', [
            'methods' => 'GET',
            'callback' => [$this, 'api_mis_votos'],
            'permission_callback' => [$this, 'verificar_usuario_autenticado'],
        ]);

        // Obtener configuración (categorías, límites, etc.)
        register_rest_route($namespace, '/presupuestos/config', [
            'methods' => 'GET',
            'callback' => [$this, 'api_obtener_config'],
            'permission_callback' => '__return_true',
        ]);

        // Seguimiento de un proyecto
        register_rest_route($namespace, '/presupuestos/(?P<id>\d+)/seguimiento', [
            'methods' => 'GET',
            'callback' => [$this, 'api_seguimiento_proyecto'],
            'permission_callback' => '__return_true',
            'args' => [
                'id' => [
                    'required' => true,
                    'type' => 'integer',
                ],
            ],
        ]);
    }

    /**
     * Verificar que el usuario está autenticado
     */
    public function verificar_usuario_autenticado() {
        return is_user_logged_in();
    }

    // =========================================================================
    // Métodos API REST
    // =========================================================================

    /**
     * API: Listar proyectos
     */
    public function api_listar_proyectos($request) {
        $resultado = $this->action_listar_proyectos([
            'categoria' => $request->get_param('categoria'),
            'estado' => $request->get_param('estado'),
            'limite' => $request->get_param('limite') ?: 20,
        ]);

        if (!$resultado['success']) {
            return new WP_REST_Response(['success' => false, 'error' => $resultado['error'] ?? 'Error al obtener proyectos'], 400);
        }

        return new WP_REST_Response($resultado, 200);
    }

    /**
     * API: Obtener un proyecto específico
     */
    public function api_obtener_proyecto($request) {
        global $wpdb;
        $tabla_proyectos = $wpdb->prefix . 'flavor_pp_proyectos';
        $identificador_proyecto = absint($request->get_param('id'));

        $proyecto = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM $tabla_proyectos WHERE id = %d",
            $identificador_proyecto
        ));

        if (!$proyecto) {
            return new WP_REST_Response(['success' => false, 'error' => 'Proyecto no encontrado'], 404);
        }

        return new WP_REST_Response([
            'success' => true,
            'proyecto' => [
                'id' => $proyecto->id,
                'titulo' => $proyecto->titulo,
                'descripcion' => $proyecto->descripcion,
                'categoria' => $proyecto->categoria,
                'ambito' => $proyecto->ambito,
                'ubicacion' => $proyecto->ubicacion,
                'presupuesto_solicitado' => floatval($proyecto->presupuesto_solicitado),
                'presupuesto_aprobado' => $proyecto->presupuesto_aprobado ? floatval($proyecto->presupuesto_aprobado) : null,
                'estado' => $proyecto->estado,
                'votos_recibidos' => intval($proyecto->votos_recibidos),
                'ranking' => intval($proyecto->ranking),
                'es_viable' => $proyecto->es_viable,
                'porcentaje_ejecucion' => intval($proyecto->porcentaje_ejecucion),
                'fecha_creacion' => $proyecto->fecha_creacion,
            ],
        ], 200);
    }

    /**
     * API: Crear propuesta
     */
    public function api_crear_propuesta($request) {
        $resultado = $this->action_proponer_proyecto([
            'titulo' => $request->get_param('titulo'),
            'descripcion' => $request->get_param('descripcion'),
            'categoria' => $request->get_param('categoria'),
            'presupuesto' => $request->get_param('presupuesto'),
            'ubicacion' => $request->get_param('ubicacion'),
        ]);

        if (!$resultado['success']) {
            return new WP_REST_Response(['success' => false, 'error' => $resultado['error']], 400);
        }

        return new WP_REST_Response($resultado, 201);
    }

    /**
     * API: Votar proyecto
     */
    public function api_votar_proyecto($request) {
        $identificador_proyecto = absint($request->get_param('id'));
        $prioridad_voto = absint($request->get_param('prioridad') ?: 1);

        $resultado = $this->action_votar_proyecto_individual([
            'proyecto_id' => $identificador_proyecto,
            'prioridad' => $prioridad_voto,
        ]);

        if (!$resultado['success']) {
            return new WP_REST_Response(['success' => false, 'error' => $resultado['error']], 400);
        }

        return new WP_REST_Response($resultado, 200);
    }

    /**
     * API: Ver resultados de votación
     */
    public function api_resultados($request) {
        global $wpdb;
        $tabla_ediciones = $wpdb->prefix . 'flavor_pp_ediciones';
        $tabla_proyectos = $wpdb->prefix . 'flavor_pp_proyectos';
        $tabla_votos = $wpdb->prefix . 'flavor_pp_votos';

        $identificador_edicion = $request->get_param('edicion_id');
        $limite_resultados = absint($request->get_param('limite') ?: 20);

        // Obtener edición
        if ($identificador_edicion) {
            $edicion = $wpdb->get_row($wpdb->prepare(
                "SELECT * FROM $tabla_ediciones WHERE id = %d",
                $identificador_edicion
            ));
        } else {
            $edicion = $wpdb->get_row(
                "SELECT * FROM $tabla_ediciones WHERE fase != 'cerrada' ORDER BY anio DESC LIMIT 1"
            );
        }

        if (!$edicion) {
            return new WP_REST_Response([
                'success' => true,
                'mensaje' => 'No hay edición activa',
                'proyectos' => [],
            ], 200);
        }

        // Obtener proyectos ordenados por votos
        $proyectos_ranking = $wpdb->get_results($wpdb->prepare(
            "SELECT p.*, COUNT(v.id) as total_votos
             FROM $tabla_proyectos p
             LEFT JOIN $tabla_votos v ON p.id = v.proyecto_id
             WHERE p.edicion_id = %d AND p.estado IN ('validado', 'en_votacion', 'seleccionado', 'en_ejecucion', 'ejecutado')
             GROUP BY p.id
             ORDER BY total_votos DESC
             LIMIT %d",
            $edicion->id,
            $limite_resultados
        ));

        $total_votantes = (int) $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(DISTINCT usuario_id) FROM $tabla_votos WHERE edicion_id = %d",
            $edicion->id
        ));

        $proyectos_formateados = array_map(function($proyecto) {
            return [
                'id' => $proyecto->id,
                'titulo' => $proyecto->titulo,
                'descripcion' => wp_trim_words($proyecto->descripcion, 30),
                'categoria' => $proyecto->categoria,
                'presupuesto' => floatval($proyecto->presupuesto_solicitado),
                'votos' => intval($proyecto->total_votos),
                'estado' => $proyecto->estado,
                'porcentaje_ejecucion' => intval($proyecto->porcentaje_ejecucion),
            ];
        }, $proyectos_ranking);

        return new WP_REST_Response([
            'success' => true,
            'edicion' => [
                'id' => $edicion->id,
                'anio' => $edicion->anio,
                'fase' => $edicion->fase,
                'presupuesto_total' => floatval($edicion->presupuesto_total),
            ],
            'total_votantes' => $total_votantes,
            'total_proyectos' => count($proyectos_formateados),
            'proyectos' => $proyectos_formateados,
        ], 200);
    }

    /**
     * API: Obtener seguimiento de un proyecto
     */
    public function api_seguimiento_proyecto($request) {
        $resultado = $this->action_seguimiento_proyecto([
            'proyecto_id' => $request->get_param('id'),
        ]);

        $code = $resultado['success'] ? 200 : 404;
        return new WP_REST_Response($resultado, $code);
    }

    /**
     * API: Ver propuestas del usuario
     */
    public function api_mis_propuestas($request) {
        global $wpdb;
        $tabla_proyectos = $wpdb->prefix . 'flavor_pp_proyectos';

        $identificador_usuario = get_current_user_id();
        $estado_filtro = $request->get_param('estado');
        $limite_resultados = absint($request->get_param('limite') ?: 20);

        $condiciones_where = ['proponente_id = %d'];
        $valores_preparados = [$identificador_usuario];

        if ($estado_filtro) {
            $condiciones_where[] = 'estado = %s';
            $valores_preparados[] = sanitize_text_field($estado_filtro);
        }

        $clausula_where = implode(' AND ', $condiciones_where);
        $valores_preparados[] = $limite_resultados;

        $propuestas = $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM $tabla_proyectos WHERE $clausula_where ORDER BY fecha_creacion DESC LIMIT %d",
            ...$valores_preparados
        ));

        $propuestas_formateadas = array_map(function($proyecto) {
            return [
                'id' => $proyecto->id,
                'titulo' => $proyecto->titulo,
                'descripcion' => wp_trim_words($proyecto->descripcion, 30),
                'categoria' => $proyecto->categoria,
                'presupuesto' => floatval($proyecto->presupuesto_solicitado),
                'estado' => $proyecto->estado,
                'votos' => intval($proyecto->votos_recibidos),
                'fecha_creacion' => $proyecto->fecha_creacion,
            ];
        }, $propuestas);

        return new WP_REST_Response([
            'success' => true,
            'total' => count($propuestas_formateadas),
            'propuestas' => $propuestas_formateadas,
        ], 200);
    }

    /**
     * API: Información de la edición actual
     */
    public function api_info_edicion($request) {
        $resultado = $this->action_info_edicion_actual([]);

        return new WP_REST_Response($resultado, 200);
    }

    /**
     * API: Ver mis votos
     */
    public function api_mis_votos($request) {
        global $wpdb;
        $tabla_votos = $wpdb->prefix . 'flavor_pp_votos';
        $tabla_proyectos = $wpdb->prefix . 'flavor_pp_proyectos';
        $tabla_ediciones = $wpdb->prefix . 'flavor_pp_ediciones';

        $identificador_usuario = get_current_user_id();

        // Obtener edición actual
        $edicion = $wpdb->get_row(
            "SELECT * FROM $tabla_ediciones WHERE fase != 'cerrada' ORDER BY anio DESC LIMIT 1"
        );

        if (!$edicion) {
            return new WP_REST_Response([
                'success' => true,
                'mensaje' => 'No hay edición activa',
                'votos' => [],
            ], 200);
        }

        $votos_usuario = $wpdb->get_results($wpdb->prepare(
            "SELECT v.*, p.titulo, p.categoria, p.presupuesto_solicitado
             FROM $tabla_votos v
             INNER JOIN $tabla_proyectos p ON v.proyecto_id = p.id
             WHERE v.usuario_id = %d AND v.edicion_id = %d
             ORDER BY v.prioridad ASC",
            $identificador_usuario,
            $edicion->id
        ));

        $votos_formateados = array_map(function($voto) {
            return [
                'proyecto_id' => $voto->proyecto_id,
                'titulo' => $voto->titulo,
                'categoria' => $voto->categoria,
                'presupuesto' => floatval($voto->presupuesto_solicitado),
                'prioridad' => intval($voto->prioridad),
                'fecha_voto' => $voto->fecha_voto,
            ];
        }, $votos_usuario);

        $configuracion = $this->settings;
        $votos_maximos = $configuracion['votos_maximos_por_persona'] ?? 3;

        return new WP_REST_Response([
            'success' => true,
            'edicion' => $edicion->anio,
            'votos_emitidos' => count($votos_formateados),
            'votos_disponibles' => max(0, $votos_maximos - count($votos_formateados)),
            'votos' => $votos_formateados,
        ], 200);
    }

    /**
     * API: Obtener configuración del módulo
     */
    public function api_obtener_config($request) {
        $configuracion = $this->settings;

        return new WP_REST_Response([
            'success' => true,
            'config' => [
                'presupuesto_minimo_proyecto' => floatval($configuracion['presupuesto_minimo_proyecto'] ?? 1000),
                'presupuesto_maximo_proyecto' => floatval($configuracion['presupuesto_maximo_proyecto'] ?? 50000),
                'votos_maximos_por_persona' => intval($configuracion['votos_maximos_por_persona'] ?? 3),
                'requiere_verificacion' => $configuracion['requiere_verificacion'] ?? true,
                'categorias' => $configuracion['categorias'] ?? [],
            ],
        ], 200);
    }

    /**
     * Acción: Votar un proyecto individual
     */
    private function action_votar_proyecto_individual($params) {
        $identificador_usuario = get_current_user_id();

        if (!$identificador_usuario) {
            return [
                'success' => false,
                'error' => __('Debes iniciar sesión para votar.', 'flavor-chat-ia'),
            ];
        }

        global $wpdb;
        $tabla_ediciones = $wpdb->prefix . 'flavor_pp_ediciones';
        $tabla_votos = $wpdb->prefix . 'flavor_pp_votos';
        $tabla_proyectos = $wpdb->prefix . 'flavor_pp_proyectos';

        // Verificar fase de votación
        $edicion = $wpdb->get_row(
            "SELECT * FROM $tabla_ediciones WHERE fase = 'votacion' ORDER BY anio DESC LIMIT 1"
        );

        if (!$edicion) {
            return [
                'success' => false,
                'error' => __('No estamos en fase de votación.', 'flavor-chat-ia'),
            ];
        }

        $identificador_proyecto = absint($params['proyecto_id']);
        $prioridad_voto = absint($params['prioridad'] ?? 1);

        // Verificar que el proyecto existe y está en votación
        $proyecto = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM $tabla_proyectos WHERE id = %d AND edicion_id = %d AND estado IN ('validado', 'en_votacion')",
            $identificador_proyecto,
            $edicion->id
        ));

        if (!$proyecto) {
            return [
                'success' => false,
                'error' => __('Proyecto no encontrado o no disponible para votar.', 'flavor-chat-ia'),
            ];
        }

        // Verificar límite de votos
        $configuracion = $this->settings;
        $votos_maximos = $configuracion['votos_maximos_por_persona'] ?? 3;

        $votos_actuales = (int) $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM $tabla_votos WHERE usuario_id = %d AND edicion_id = %d",
            $identificador_usuario,
            $edicion->id
        ));

        // Verificar si ya votó este proyecto
        $voto_existente = $wpdb->get_var($wpdb->prepare(
            "SELECT id FROM $tabla_votos WHERE usuario_id = %d AND proyecto_id = %d",
            $identificador_usuario,
            $identificador_proyecto
        ));

        if ($voto_existente) {
            return [
                'success' => false,
                'error' => __('Ya has votado este proyecto.', 'flavor-chat-ia'),
            ];
        }

        if ($votos_actuales >= $votos_maximos) {
            return [
                'success' => false,
                'error' => sprintf(__('Has alcanzado el límite de %d votos.', 'flavor-chat-ia'), $votos_maximos),
            ];
        }

        // Registrar voto
        $resultado_insercion = $wpdb->insert(
            $tabla_votos,
            [
                'proyecto_id' => $identificador_proyecto,
                'edicion_id' => $edicion->id,
                'usuario_id' => $identificador_usuario,
                'prioridad' => $prioridad_voto,
            ],
            ['%d', '%d', '%d', '%d']
        );

        if ($resultado_insercion === false) {
            return [
                'success' => false,
                'error' => __('Error al registrar el voto.', 'flavor-chat-ia'),
            ];
        }

        // Actualizar contador de votos del proyecto
        $this->actualizar_contadores_votos($edicion->id);

        return [
            'success' => true,
            'mensaje' => sprintf(__('¡Voto registrado para "%s"!', 'flavor-chat-ia'), $proyecto->titulo),
            'votos_restantes' => $votos_maximos - $votos_actuales - 1,
        ];
    }

    /**
     * Configuración para el panel de administración unificado
     *
     * @return array
     */
    protected function get_admin_config() {
        return [
            'id' => $this->id,
            'label' => $this->name,
            'icon' => 'dashicons-money-alt',
            'capability' => 'manage_options',
            'categoria' => 'economia',
            'paginas' => [
                [
                    'slug' => 'pp-dashboard',
                    'titulo' => __('Dashboard', 'flavor-chat-ia'),
                    'callback' => [$this, 'render_admin_dashboard'],
                ],
                [
                    'slug' => 'pp-propuestas',
                    'titulo' => __('Propuestas', 'flavor-chat-ia'),
                    'callback' => [$this, 'render_admin_propuestas'],
                    'badge' => [$this, 'contar_propuestas_pendientes'],
                ],
                [
                    'slug' => 'pp-votaciones',
                    'titulo' => __('Votaciones', 'flavor-chat-ia'),
                    'callback' => [$this, 'render_admin_votaciones'],
                ],
            ],
            'dashboard_widget' => [$this, 'render_widget_resumen'],
            'estadisticas' => [$this, 'get_estadisticas_dashboard'],
        ];
    }

    /**
     * Cuenta propuestas pendientes de validación
     *
     * @return int
     */
    public function contar_propuestas_pendientes() {
        // Verificar que el módulo esté activo
        if (!$this->can_activate()) {
            return 0;
        }

        global $wpdb;
        $tabla_proyectos = $wpdb->prefix . 'flavor_pp_proyectos';

        if (!Flavor_Chat_Helpers::tabla_existe($tabla_proyectos)) {
            return 0;
        }

        return (int) $wpdb->get_var(
            "SELECT COUNT(*) FROM $tabla_proyectos WHERE estado = 'pendiente_validacion'"
        );
    }

    /**
     * Renderiza el dashboard de administración
     */
    public function render_admin_dashboard() {
        $this->render_page_header(
            __('Presupuestos Participativos - Dashboard', 'flavor-chat-ia'),
            [
                [
                    'label' => __('Nueva Edición', 'flavor-chat-ia'),
                    'url' => admin_url('admin.php?page=pp-propuestas&action=nueva-edicion'),
                    'class' => 'button-primary',
                ],
            ]
        );

        global $wpdb;
        $tabla_ediciones = $wpdb->prefix . 'flavor_pp_ediciones';
        $tabla_proyectos = $wpdb->prefix . 'flavor_pp_proyectos';
        $tabla_votos = $wpdb->prefix . 'flavor_pp_votos';

        $edicion_actual = $wpdb->get_row(
            "SELECT * FROM $tabla_ediciones WHERE fase != 'cerrada' ORDER BY anio DESC LIMIT 1"
        );

        $total_proyectos = (int) $wpdb->get_var("SELECT COUNT(*) FROM $tabla_proyectos");
        $total_votos = (int) $wpdb->get_var("SELECT COUNT(*) FROM $tabla_votos");
        $proyectos_pendientes = $this->contar_propuestas_pendientes();

        ?>
        <div class="wrap flavor-pp-dashboard">
            <div class="flavor-pp-stats-grid">
                <div class="flavor-pp-stat-card">
                    <span class="dashicons dashicons-portfolio"></span>
                    <div class="stat-content">
                        <span class="stat-value"><?php echo esc_html($total_proyectos); ?></span>
                        <span class="stat-label"><?php _e('Total Proyectos', 'flavor-chat-ia'); ?></span>
                    </div>
                </div>
                <div class="flavor-pp-stat-card warning">
                    <span class="dashicons dashicons-clock"></span>
                    <div class="stat-content">
                        <span class="stat-value"><?php echo esc_html($proyectos_pendientes); ?></span>
                        <span class="stat-label"><?php _e('Pendientes Validación', 'flavor-chat-ia'); ?></span>
                    </div>
                </div>
                <div class="flavor-pp-stat-card">
                    <span class="dashicons dashicons-thumbs-up"></span>
                    <div class="stat-content">
                        <span class="stat-value"><?php echo esc_html($total_votos); ?></span>
                        <span class="stat-label"><?php _e('Votos Emitidos', 'flavor-chat-ia'); ?></span>
                    </div>
                </div>
                <div class="flavor-pp-stat-card <?php echo $edicion_actual ? 'success' : ''; ?>">
                    <span class="dashicons dashicons-calendar-alt"></span>
                    <div class="stat-content">
                        <span class="stat-value"><?php echo $edicion_actual ? esc_html($edicion_actual->fase) : __('N/A', 'flavor-chat-ia'); ?></span>
                        <span class="stat-label"><?php _e('Fase Actual', 'flavor-chat-ia'); ?></span>
                    </div>
                </div>
            </div>

            <?php if ($edicion_actual): ?>
                <div class="flavor-pp-edicion-info">
                    <h2><?php printf(__('Edición %d', 'flavor-chat-ia'), $edicion_actual->anio); ?></h2>
                    <p>
                        <strong><?php _e('Presupuesto Total:', 'flavor-chat-ia'); ?></strong>
                        <?php echo number_format($edicion_actual->presupuesto_total, 2, ',', '.'); ?> €
                    </p>
                    <p>
                        <strong><?php _e('Fase:', 'flavor-chat-ia'); ?></strong>
                        <?php echo esc_html(ucfirst($edicion_actual->fase)); ?>
                    </p>
                </div>
            <?php else: ?>
                <div class="notice notice-info">
                    <p><?php _e('No hay una edición activa. Crea una nueva edición para comenzar.', 'flavor-chat-ia'); ?></p>
                </div>
            <?php endif; ?>
        </div>
        <?php
    }

    /**
     * Renderiza la página de administración de propuestas
     */
    public function render_admin_propuestas() {
        $this->render_page_header(
            __('Gestión de Propuestas', 'flavor-chat-ia'),
            [
                [
                    'label' => __('Exportar', 'flavor-chat-ia'),
                    'url' => admin_url('admin.php?page=pp-propuestas&action=exportar'),
                    'class' => '',
                ],
            ]
        );

        $tab_actual = isset($_GET['tab']) ? sanitize_text_field($_GET['tab']) : 'pendientes';

        $this->render_page_tabs([
            ['slug' => 'pendientes', 'label' => __('Pendientes', 'flavor-chat-ia'), 'badge' => $this->contar_propuestas_pendientes()],
            ['slug' => 'validados', 'label' => __('Validados', 'flavor-chat-ia')],
            ['slug' => 'rechazados', 'label' => __('Rechazados', 'flavor-chat-ia')],
            ['slug' => 'todos', 'label' => __('Todos', 'flavor-chat-ia')],
        ], $tab_actual);

        global $wpdb;
        $tabla_proyectos = $wpdb->prefix . 'flavor_pp_proyectos';

        $where_estado = '';
        switch ($tab_actual) {
            case 'pendientes':
                $where_estado = "WHERE estado = 'pendiente_validacion'";
                break;
            case 'validados':
                $where_estado = "WHERE estado IN ('validado', 'en_votacion', 'seleccionado')";
                break;
            case 'rechazados':
                $where_estado = "WHERE estado = 'rechazado'";
                break;
        }

        $proyectos = $wpdb->get_results("SELECT * FROM $tabla_proyectos $where_estado ORDER BY fecha_creacion DESC LIMIT 50");

        ?>
        <div class="wrap">
            <table class="wp-list-table widefat fixed striped">
                <thead>
                    <tr>
                        <th><?php _e('ID', 'flavor-chat-ia'); ?></th>
                        <th><?php _e('Título', 'flavor-chat-ia'); ?></th>
                        <th><?php _e('Categoría', 'flavor-chat-ia'); ?></th>
                        <th><?php _e('Presupuesto', 'flavor-chat-ia'); ?></th>
                        <th><?php _e('Estado', 'flavor-chat-ia'); ?></th>
                        <th><?php _e('Votos', 'flavor-chat-ia'); ?></th>
                        <th><?php _e('Acciones', 'flavor-chat-ia'); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($proyectos)): ?>
                        <tr>
                            <td colspan="7"><?php _e('No hay propuestas en esta categoría.', 'flavor-chat-ia'); ?></td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($proyectos as $proyecto): ?>
                            <tr>
                                <td><?php echo esc_html($proyecto->id); ?></td>
                                <td><strong><?php echo esc_html($proyecto->titulo); ?></strong></td>
                                <td><?php echo esc_html(ucfirst($proyecto->categoria)); ?></td>
                                <td><?php echo number_format($proyecto->presupuesto_solicitado, 2, ',', '.'); ?> €</td>
                                <td>
                                    <span class="flavor-pp-estado flavor-pp-estado-<?php echo esc_attr($proyecto->estado); ?>">
                                        <?php echo esc_html(ucfirst(str_replace('_', ' ', $proyecto->estado))); ?>
                                    </span>
                                </td>
                                <td><?php echo esc_html($proyecto->votos_recibidos); ?></td>
                                <td>
                                    <a href="<?php echo admin_url('admin.php?page=pp-propuestas&action=ver&id=' . $proyecto->id); ?>" class="button button-small">
                                        <?php _e('Ver', 'flavor-chat-ia'); ?>
                                    </a>
                                    <?php if ($proyecto->estado === 'pendiente_validacion'): ?>
                                        <a href="<?php echo wp_nonce_url(admin_url('admin.php?page=pp-propuestas&action=validar&id=' . $proyecto->id), 'validar_proyecto_' . $proyecto->id); ?>" class="button button-small button-primary">
                                            <?php _e('Validar', 'flavor-chat-ia'); ?>
                                        </a>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
        <?php
    }

    /**
     * Renderiza la página de administración de votaciones
     */
    public function render_admin_votaciones() {
        $this->render_page_header(
            __('Gestión de Votaciones', 'flavor-chat-ia')
        );

        global $wpdb;
        $tabla_ediciones = $wpdb->prefix . 'flavor_pp_ediciones';
        $tabla_votos = $wpdb->prefix . 'flavor_pp_votos';
        $tabla_proyectos = $wpdb->prefix . 'flavor_pp_proyectos';

        $edicion_actual = $wpdb->get_row(
            "SELECT * FROM $tabla_ediciones WHERE fase != 'cerrada' ORDER BY anio DESC LIMIT 1"
        );

        if (!$edicion_actual) {
            echo '<div class="notice notice-warning"><p>' . __('No hay edición activa.', 'flavor-chat-ia') . '</p></div>';
            return;
        }

        $total_votantes = (int) $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(DISTINCT usuario_id) FROM $tabla_votos WHERE edicion_id = %d",
            $edicion_actual->id
        ));

        $proyectos_ranking = $wpdb->get_results($wpdb->prepare(
            "SELECT p.*, COUNT(v.id) as total_votos
             FROM $tabla_proyectos p
             LEFT JOIN $tabla_votos v ON p.id = v.proyecto_id
             WHERE p.edicion_id = %d AND p.estado IN ('validado', 'en_votacion', 'seleccionado')
             GROUP BY p.id
             ORDER BY total_votos DESC
             LIMIT 20",
            $edicion_actual->id
        ));

        ?>
        <div class="wrap flavor-pp-votaciones">
            <div class="flavor-pp-votacion-info">
                <h2><?php printf(__('Votación - Edición %d', 'flavor-chat-ia'), $edicion_actual->anio); ?></h2>
                <p>
                    <strong><?php _e('Fase actual:', 'flavor-chat-ia'); ?></strong>
                    <?php echo esc_html(ucfirst($edicion_actual->fase)); ?>
                </p>
                <p>
                    <strong><?php _e('Total votantes:', 'flavor-chat-ia'); ?></strong>
                    <?php echo esc_html($total_votantes); ?>
                </p>
                <?php if ($edicion_actual->fecha_inicio_votacion && $edicion_actual->fecha_fin_votacion): ?>
                    <p>
                        <strong><?php _e('Período:', 'flavor-chat-ia'); ?></strong>
                        <?php echo esc_html($edicion_actual->fecha_inicio_votacion); ?> - <?php echo esc_html($edicion_actual->fecha_fin_votacion); ?>
                    </p>
                <?php endif; ?>
            </div>

            <h3><?php _e('Ranking de Proyectos', 'flavor-chat-ia'); ?></h3>
            <table class="wp-list-table widefat fixed striped">
                <thead>
                    <tr>
                        <th style="width: 50px;"><?php _e('#', 'flavor-chat-ia'); ?></th>
                        <th><?php _e('Proyecto', 'flavor-chat-ia'); ?></th>
                        <th><?php _e('Categoría', 'flavor-chat-ia'); ?></th>
                        <th><?php _e('Presupuesto', 'flavor-chat-ia'); ?></th>
                        <th><?php _e('Votos', 'flavor-chat-ia'); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($proyectos_ranking)): ?>
                        <tr>
                            <td colspan="5"><?php _e('No hay proyectos en votación.', 'flavor-chat-ia'); ?></td>
                        </tr>
                    <?php else: ?>
                        <?php
                        $posicion = 1;
                        foreach ($proyectos_ranking as $proyecto):
                        ?>
                            <tr>
                                <td><strong><?php echo esc_html($posicion++); ?></strong></td>
                                <td><?php echo esc_html($proyecto->titulo); ?></td>
                                <td><?php echo esc_html(ucfirst($proyecto->categoria)); ?></td>
                                <td><?php echo number_format($proyecto->presupuesto_solicitado, 2, ',', '.'); ?> €</td>
                                <td><strong><?php echo esc_html($proyecto->total_votos); ?></strong></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
        <?php
    }

    /**
     * Renderiza widget de resumen para el dashboard unificado
     */
    public function render_widget_resumen() {
        global $wpdb;
        $tabla_ediciones = $wpdb->prefix . 'flavor_pp_ediciones';

        $edicion = $wpdb->get_row(
            "SELECT * FROM $tabla_ediciones WHERE fase != 'cerrada' ORDER BY anio DESC LIMIT 1"
        );

        if ($edicion) {
            $fases_texto = [
                'propuestas' => __('Recibiendo propuestas', 'flavor-chat-ia'),
                'evaluacion' => __('Evaluando proyectos', 'flavor-chat-ia'),
                'votacion' => __('Votación abierta', 'flavor-chat-ia'),
                'implementacion' => __('En implementación', 'flavor-chat-ia'),
            ];
            echo '<p><strong>' . sprintf(__('Edición %d:', 'flavor-chat-ia'), $edicion->anio) . '</strong> ';
            echo esc_html($fases_texto[$edicion->fase] ?? $edicion->fase) . '</p>';
        } else {
            echo '<p>' . __('Sin edición activa', 'flavor-chat-ia') . '</p>';
        }
    }

    /**
     * Obtiene estadísticas para el dashboard unificado
     *
     * @return array
     */
    public function get_estadisticas_dashboard() {
        $propuestas_pendientes = $this->contar_propuestas_pendientes();

        $estadisticas = [];

        if ($propuestas_pendientes > 0) {
            $estadisticas[] = [
                'icon' => 'dashicons-portfolio',
                'valor' => $propuestas_pendientes,
                'label' => __('Propuestas pendientes', 'flavor-chat-ia'),
                'color' => 'orange',
                'enlace' => admin_url('admin.php?page=pp-propuestas&tab=pendientes'),
            ];
        }

        return $estadisticas;
    }

    /**
     * Crea las tablas si no existen
     */
    public function maybe_create_tables() {
        global $wpdb;
        $tabla_proyectos = $wpdb->prefix . 'flavor_pp_proyectos';

        if (!Flavor_Chat_Helpers::tabla_existe($tabla_proyectos)) {
            $this->create_tables();
        }
    }

    /**
     * Crea las tablas necesarias
     */
    private function create_tables() {
        global $wpdb;
        $charset_collate = $wpdb->get_charset_collate();

        $tabla_proyectos = $wpdb->prefix . 'flavor_pp_proyectos';
        $tabla_votos = $wpdb->prefix . 'flavor_pp_votos';
        $tabla_ediciones = $wpdb->prefix . 'flavor_pp_ediciones';

        $sql_ediciones = "CREATE TABLE IF NOT EXISTS $tabla_ediciones (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            anio int(11) NOT NULL,
            presupuesto_total decimal(12,2) NOT NULL,
            presupuesto_gastado decimal(12,2) DEFAULT 0.00,
            fase enum('propuestas','evaluacion','votacion','implementacion','cerrada') DEFAULT 'propuestas',
            fecha_inicio_propuestas date DEFAULT NULL,
            fecha_fin_propuestas date DEFAULT NULL,
            fecha_inicio_votacion date DEFAULT NULL,
            fecha_fin_votacion date DEFAULT NULL,
            total_proyectos int(11) DEFAULT 0,
            total_votantes int(11) DEFAULT 0,
            participacion_porcentaje decimal(5,2) DEFAULT 0.00,
            fecha_creacion datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            UNIQUE KEY anio (anio)
        ) $charset_collate;";

        $sql_proyectos = "CREATE TABLE IF NOT EXISTS $tabla_proyectos (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            edicion_id bigint(20) unsigned NOT NULL,
            titulo varchar(255) NOT NULL,
            descripcion text NOT NULL,
            categoria varchar(50) NOT NULL,
            ambito varchar(100) DEFAULT NULL,
            ubicacion varchar(500) DEFAULT NULL,
            latitud decimal(10,8) DEFAULT NULL,
            longitud decimal(11,8) DEFAULT NULL,
            presupuesto_solicitado decimal(12,2) NOT NULL,
            presupuesto_aprobado decimal(12,2) DEFAULT NULL,
            proponente_id bigint(20) unsigned DEFAULT NULL,
            proponente_grupo varchar(255) DEFAULT NULL,
            estado enum('borrador','pendiente_validacion','validado','en_votacion','seleccionado','en_ejecucion','ejecutado','rechazado') DEFAULT 'pendiente_validacion',
            votos_recibidos int(11) DEFAULT 0,
            ranking int(11) DEFAULT 0,
            es_viable tinyint(1) DEFAULT NULL,
            motivo_no_viable text DEFAULT NULL,
            fecha_validacion datetime DEFAULT NULL,
            fecha_inicio_ejecucion datetime DEFAULT NULL,
            fecha_fin_ejecucion datetime DEFAULT NULL,
            porcentaje_ejecucion int(11) DEFAULT 0,
            imagenes text DEFAULT NULL,
            documentos text DEFAULT NULL,
            fecha_creacion datetime DEFAULT CURRENT_TIMESTAMP,
            fecha_actualizacion datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY edicion_id (edicion_id),
            KEY proponente_id (proponente_id),
            KEY estado (estado),
            KEY categoria (categoria)
        ) $charset_collate;";

        $sql_votos = "CREATE TABLE IF NOT EXISTS $tabla_votos (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            proyecto_id bigint(20) unsigned NOT NULL,
            edicion_id bigint(20) unsigned NOT NULL,
            usuario_id bigint(20) unsigned NOT NULL,
            prioridad int(11) DEFAULT 1,
            fecha_voto datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            UNIQUE KEY usuario_proyecto (usuario_id, proyecto_id),
            KEY proyecto_id (proyecto_id),
            KEY edicion_id (edicion_id),
            KEY usuario_id (usuario_id)
        ) $charset_collate;";

        require_once ABSPATH . 'wp-admin/includes/upgrade.php';
        dbDelta($sql_ediciones);
        dbDelta($sql_proyectos);
        dbDelta($sql_votos);
    }

    /**
     * {@inheritdoc}
     */
    public function get_actions() {
        return [
            'info_edicion_actual' => [
                'description' => 'Ver información de la edición actual de presupuestos participativos',
                'params' => [],
            ],
            'proponer_proyecto' => [
                'description' => 'Proponer un proyecto para presupuestos participativos',
                'params' => ['titulo', 'descripcion', 'categoria', 'presupuesto', 'ubicacion'],
            ],
            'listar_proyectos' => [
                'description' => 'Ver proyectos propuestos',
                'params' => ['categoria', 'estado', 'limite'],
            ],
            'ver_proyecto' => [
                'description' => 'Ver detalles de un proyecto',
                'params' => ['proyecto_id'],
            ],
            'votar_proyectos' => [
                'description' => 'Votar tus proyectos favoritos (hasta 3)',
                'params' => ['proyecto_ids'],
            ],
            'mis_votos' => [
                'description' => 'Ver qué proyectos he votado',
                'params' => [],
            ],
            'resultados' => [
                'description' => 'Ver resultados de la votación',
                'params' => ['edicion_id'],
            ],
            'seguimiento_proyecto' => [
                'description' => 'Ver estado de ejecución de un proyecto aprobado',
                'params' => ['proyecto_id'],
            ],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function execute_action($action_name, $params) {
        $aliases = [
            'listar' => 'listar_proyectos',
            'listado' => 'listar_proyectos',
            'explorar' => 'listar_proyectos',
            'buscar' => 'listar_proyectos',
            'proyectos' => 'listar_proyectos',
            'proponer' => 'proponer_proyecto',
            'crear' => 'proponer_proyecto',
            'votar' => 'votar_proyectos',
            'resultados' => 'info_edicion_actual',
            'estado_actual' => 'info_edicion_actual',
            'seguimiento' => 'seguimiento_proyecto',
        ];

        $action_name = $aliases[$action_name] ?? $action_name;
        $metodo_accion = 'action_' . $action_name;

        if (method_exists($this, $metodo_accion)) {
            return $this->$metodo_accion($params);
        }

        return [
            'success' => false,
            'error' => "Acción no implementada: {$action_name}",
        ];
    }

    /**
     * Acción: Info edición actual
     */
    private function action_info_edicion_actual($params) {
        global $wpdb;
        $tabla_ediciones = $wpdb->prefix . 'flavor_pp_ediciones';

        $edicion = $wpdb->get_row(
            "SELECT * FROM $tabla_ediciones WHERE fase != 'cerrada' ORDER BY anio DESC LIMIT 1"
        );

        if (!$edicion) {
            return [
                'success' => true,
                'activa' => false,
                'mensaje' => __('No hay ninguna edición de presupuestos participativos activa actualmente.', 'flavor-chat-ia'),
            ];
        }

        $fases_texto = [
            'propuestas' => 'Fase de propuestas ciudadanas',
            'evaluacion' => 'Fase de evaluación técnica',
            'votacion' => 'Fase de votación',
            'implementacion' => 'Fase de implementación',
        ];

        return [
            'success' => true,
            'activa' => true,
            'edicion' => [
                'anio' => $edicion->anio,
                'presupuesto_total' => floatval($edicion->presupuesto_total),
                'presupuesto_disponible' => floatval($edicion->presupuesto_total - $edicion->presupuesto_gastado),
                'fase' => $edicion->fase,
                'fase_texto' => $fases_texto[$edicion->fase] ?? $edicion->fase,
                'fechas' => [
                    'propuestas' => [
                        'inicio' => $edicion->fecha_inicio_propuestas,
                        'fin' => $edicion->fecha_fin_propuestas,
                    ],
                    'votacion' => [
                        'inicio' => $edicion->fecha_inicio_votacion,
                        'fin' => $edicion->fecha_fin_votacion,
                    ],
                ],
                'total_proyectos' => $edicion->total_proyectos,
                'total_votantes' => $edicion->total_votantes,
                'participacion' => floatval($edicion->participacion_porcentaje) . '%',
            ],
        ];
    }

    /**
     * Acción: Proponer proyecto
     */
    private function action_proponer_proyecto($params) {
        $usuario_id = get_current_user_id();

        if (!$usuario_id) {
            return [
                'success' => false,
                'error' => __('Debes iniciar sesión para proponer un proyecto.', 'flavor-chat-ia'),
            ];
        }

        // Verificar que estamos en fase de propuestas
        global $wpdb;
        $tabla_ediciones = $wpdb->prefix . 'flavor_pp_ediciones';
        $tabla_proyectos = $wpdb->prefix . 'flavor_pp_proyectos';

        $edicion = $wpdb->get_row(
            "SELECT * FROM $tabla_ediciones WHERE fase = 'propuestas' ORDER BY anio DESC LIMIT 1"
        );

        if (!$edicion) {
            return [
                'success' => false,
                'error' => __('No estamos en fase de recepción de propuestas. Consulta el calendario del proceso.', 'flavor-chat-ia'),
            ];
        }

        $titulo = sanitize_text_field($params['titulo'] ?? '');
        $descripcion = sanitize_textarea_field($params['descripcion'] ?? '');
        $presupuesto = floatval($params['presupuesto'] ?? 0);

        if (empty($titulo) || empty($descripcion)) {
            return [
                'success' => false,
                'error' => __('El título y la descripción del proyecto son obligatorios.', 'flavor-chat-ia'),
            ];
        }

        $settings = $this->settings;
        if ($presupuesto < $settings['presupuesto_minimo_proyecto'] || $presupuesto > $settings['presupuesto_maximo_proyecto']) {
            return [
                'success' => false,
                'error' => sprintf(
                    'El presupuesto debe estar entre %s€ y %s€.',
                    number_format($settings['presupuesto_minimo_proyecto'], 0, ',', '.'),
                    number_format($settings['presupuesto_maximo_proyecto'], 0, ',', '.')
                ),
            ];
        }

        $resultado = $wpdb->insert(
            $tabla_proyectos,
            [
                'edicion_id' => $edicion->id,
                'titulo' => $titulo,
                'descripcion' => $descripcion,
                'categoria' => sanitize_text_field($params['categoria'] ?? 'social'),
                'ubicacion' => sanitize_text_field($params['ubicacion'] ?? ''),
                'presupuesto_solicitado' => $presupuesto,
                'proponente_id' => $usuario_id,
                'estado' => 'pendiente_validacion',
            ],
            ['%d', '%s', '%s', '%s', '%s', '%f', '%d', '%s']
        );

        if ($resultado === false) {
            return [
                'success' => false,
                'error' => __('Error al crear el proyecto. Por favor, inténtalo de nuevo.', 'flavor-chat-ia'),
            ];
        }

        return [
            'success' => true,
            'proyecto_id' => $wpdb->insert_id,
            'mensaje' => __('¡Proyecto propuesto! Será evaluado técnicamente antes de pasar a votación.', 'flavor-chat-ia'),
        ];
    }

    /**
     * Acción: Listar proyectos
     */
    private function action_listar_proyectos($params) {
        global $wpdb;
        $tabla_proyectos = $wpdb->prefix . 'flavor_pp_proyectos';
        $tabla_ediciones = $wpdb->prefix . 'flavor_pp_ediciones';

        // Obtener edición actual
        $edicion = $wpdb->get_row(
            "SELECT * FROM $tabla_ediciones WHERE fase != 'cerrada' ORDER BY anio DESC LIMIT 1"
        );

        if (!$edicion) {
            return [
                'success' => true,
                'total' => 0,
                'proyectos' => [],
            ];
        }

        $where = ['edicion_id = %d'];
        $prepare_values = [$edicion->id];

        if (!empty($params['categoria'])) {
            $where[] = 'categoria = %s';
            $prepare_values[] = $params['categoria'];
        }

        if (!empty($params['estado'])) {
            $where[] = 'estado = %s';
            $prepare_values[] = $params['estado'];
        } else {
            // Por defecto, mostrar proyectos validados o en votación
            $where[] = "estado IN ('validado', 'en_votacion', 'seleccionado')";
        }

        $limite = absint($params['limite'] ?? 20);
        $sql_where = implode(' AND ', $where);

        $sql = "SELECT * FROM $tabla_proyectos WHERE $sql_where ORDER BY votos_recibidos DESC, fecha_creacion DESC LIMIT %d";
        $prepare_values[] = $limite;

        $proyectos = $wpdb->get_results($wpdb->prepare($sql, ...$prepare_values));

        return [
            'success' => true,
            'edicion' => $edicion->anio,
            'fase' => $edicion->fase,
            'total' => count($proyectos),
            'proyectos' => array_map(function($p) {
                return [
                    'id' => $p->id,
                    'titulo' => $p->titulo,
                    'descripcion' => wp_trim_words($p->descripcion, 30),
                    'categoria' => $p->categoria,
                    'presupuesto' => floatval($p->presupuesto_solicitado),
                    'ubicacion' => $p->ubicacion,
                    'votos' => $p->votos_recibidos,
                    'ranking' => $p->ranking,
                    'estado' => $p->estado,
                    'porcentaje_ejecucion' => $p->porcentaje_ejecucion,
                ];
            }, $proyectos),
        ];
    }

    /**
     * Acción: Votar proyectos
     */
    private function action_votar_proyectos($params) {
        $usuario_id = get_current_user_id();

        if (!$usuario_id) {
            return [
                'success' => false,
                'error' => __('Debes iniciar sesión para votar.', 'flavor-chat-ia'),
            ];
        }

        global $wpdb;
        $tabla_ediciones = $wpdb->prefix . 'flavor_pp_ediciones';

        // Verificar fase de votación
        $edicion = $wpdb->get_row(
            "SELECT * FROM $tabla_ediciones WHERE fase = 'votacion' ORDER BY anio DESC LIMIT 1"
        );

        if (!$edicion) {
            return [
                'success' => false,
                'error' => __('Actualmente no hay ninguna votación abierta.', 'flavor-chat-ia'),
            ];
        }

        $proyecto_ids = $params['proyecto_ids'] ?? [];
        if (!is_array($proyecto_ids)) {
            $proyecto_ids = [$proyecto_ids];
        }

        $settings = $this->settings;
        $max_votos = $settings['votos_maximos_por_persona'];

        if (count($proyecto_ids) > $max_votos) {
            return [
                'success' => false,
                'error' => "Solo puedes votar hasta {$max_votos} proyectos.",
            ];
        }

        // Limpiar votos anteriores
        $tabla_votos = $wpdb->prefix . 'flavor_pp_votos';
        $wpdb->delete(
            $tabla_votos,
            ['usuario_id' => $usuario_id, 'edicion_id' => $edicion->id],
            ['%d', '%d']
        );

        // Registrar nuevos votos
        $votos_registrados = 0;
        foreach ($proyecto_ids as $prioridad => $proyecto_id) {
            $resultado = $wpdb->insert(
                $tabla_votos,
                [
                    'proyecto_id' => absint($proyecto_id),
                    'edicion_id' => $edicion->id,
                    'usuario_id' => $usuario_id,
                    'prioridad' => $prioridad + 1,
                ],
                ['%d', '%d', '%d', '%d']
            );

            if ($resultado !== false) {
                $votos_registrados++;
            }
        }

        // Actualizar contadores
        $this->actualizar_contadores_votos($edicion->id);

        return [
            'success' => true,
            'votos_registrados' => $votos_registrados,
            'mensaje' => "¡Voto registrado! Has votado {$votos_registrados} proyectos.",
        ];
    }

    /**
     * Acción: Obtener seguimiento de un proyecto
     */
    private function action_seguimiento_proyecto($params) {
        $proyecto_id = absint($params['proyecto_id'] ?? 0);

        if (!$proyecto_id) {
            return [
                'success' => false,
                'error' => __('ID de proyecto no válido.', 'flavor-chat-ia'),
            ];
        }

        global $wpdb;
        $tabla_proyectos = $wpdb->prefix . 'flavor_pp_proyectos';
        $tabla_ediciones = $wpdb->prefix . 'flavor_pp_ediciones';

        $proyecto = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM $tabla_proyectos WHERE id = %d",
            $proyecto_id
        ));

        if (!$proyecto) {
            return [
                'success' => false,
                'error' => __('Proyecto no encontrado.', 'flavor-chat-ia'),
            ];
        }

        $edicion = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM $tabla_ediciones WHERE id = %d",
            $proyecto->edicion_id
        ));

        $proponente = $proyecto->proponente_id ? get_user_by('ID', $proyecto->proponente_id) : null;
        $categorias = $this->settings['categorias'] ?? [];
        $estado_actual = $proyecto->estado;

        $timeline = [];
        $timeline[] = [
            'key' => 'registro',
            'label' => __('Propuesta registrada', 'flavor-chat-ia'),
            'fecha' => $this->format_timeline_date($proyecto->fecha_creacion),
            'estado' => 'done',
            'detalle' => sprintf(
                __('Propuesta por %s', 'flavor-chat-ia'),
                $proponente ? $proponente->display_name : __('Anónimo', 'flavor-chat-ia')
            ),
        ];

        $timeline[] = [
            'key' => 'validacion',
            'label' => __('Evaluación técnica', 'flavor-chat-ia'),
            'fecha' => $this->format_timeline_date($proyecto->fecha_validacion),
            'estado' => $this->get_timeline_status($estado_actual, 'validacion'),
            'detalle' => $this->get_timeline_status($estado_actual, 'validacion') === 'done'
                ? __('Proyecto validado por el equipo técnico.', 'flavor-chat-ia')
                : __('Proyecto en evaluación técnica.', 'flavor-chat-ia'),
        ];

        $timeline[] = [
            'key' => 'votacion',
            'label' => __('Votación ciudadana', 'flavor-chat-ia'),
            'fecha' => $this->format_timeline_date($edicion->fecha_inicio_votacion ?? $proyecto->fecha_inicio_ejecucion),
            'estado' => $this->get_timeline_status($estado_actual, 'votacion', $edicion->fase ?? null),
            'detalle' => $this->get_timeline_status($estado_actual, 'votacion', $edicion->fase ?? null) === 'done'
                ? __('Proyecto votado y elegido por la comunidad.', 'flavor-chat-ia')
                : __('Votación abierta o programada.', 'flavor-chat-ia'),
        ];

        $timeline[] = [
            'key' => 'implementacion',
            'label' => __('Implementación', 'flavor-chat-ia'),
            'fecha' => $this->format_timeline_date($proyecto->fecha_inicio_ejecucion),
            'estado' => $this->get_timeline_status($estado_actual, 'implementacion', null, $proyecto->porcentaje_ejecucion),
            'detalle' => $this->get_timeline_status($estado_actual, 'implementacion', null, $proyecto->porcentaje_ejecucion) === 'done'
                ? __('Proyecto en ejecución o completado.', 'flavor-chat-ia')
                : __('Proyecto aún no ha iniciado la ejecución.', 'flavor-chat-ia'),
        ];

        $timeline[] = [
            'key' => 'cierre',
            'label' => __('Seguimiento y cierre', 'flavor-chat-ia'),
            'fecha' => $this->format_timeline_date($proyecto->fecha_fin_ejecucion ?? $proyecto->fecha_actualizacion),
            'estado' => $estado_actual === 'ejecutado' ? 'done' : 'pending',
            'detalle' => $estado_actual === 'ejecutado'
                ? __('Proyecto completado y documentado.', 'flavor-chat-ia')
                : __('Seguimiento del avance en curso.', 'flavor-chat-ia'),
        ];

        return [
            'success' => true,
            'proyecto' => [
                'id' => $proyecto->id,
                'titulo' => $proyecto->titulo,
                'descripcion' => $proyecto->descripcion,
                'categoria' => $proyecto->categoria,
                'categoria_label' => $categorias[$proyecto->categoria] ?? ucfirst($proyecto->categoria),
                'ubicacion' => $proyecto->ubicacion,
                'presupuesto' => floatval($proyecto->presupuesto_solicitado),
                'presupuesto_aprobado' => $proyecto->presupuesto_aprobado ? floatval($proyecto->presupuesto_aprobado) : null,
                'proponente' => $proponente ? $proponente->display_name : null,
                'estado' => $estado_actual,
                'votos_recibidos' => intval($proyecto->votos_recibidos),
                'ranking' => intval($proyecto->ranking),
                'porcentaje_ejecucion' => intval($proyecto->porcentaje_ejecucion),
                'imagenes' => $proyecto->imagenes ? explode(',', $proyecto->imagenes) : [],
            ],
            'edicion' => $edicion ? [
                'anio' => $edicion->anio,
                'fase' => $edicion->fase,
                'presupuesto_total' => floatval($edicion->presupuesto_total),
                'fecha_inicio_votacion' => $this->format_timeline_date($edicion->fecha_inicio_votacion),
                'fecha_fin_votacion' => $this->format_timeline_date($edicion->fecha_fin_votacion),
            ] : null,
            'timeline' => $timeline,
        ];
    }

    /**
     * Formatea fechas para el timeline
     */
    private function format_timeline_date($fecha) {
        if (empty($fecha)) {
            return null;
        }

        $timestamp = strtotime($fecha);
        if (!$timestamp) {
            return null;
        }

        return date_i18n('c', $timestamp);
    }

    /**
     * Determina el estado de un paso del timeline
     */
    private function get_timeline_status($estado_proyecto, $paso, $fase_edicion = null, $porcentaje = 0) {
        switch ($paso) {
            case 'validacion':
                if ($estado_proyecto === 'pendiente_validacion') {
                    return 'active';
                }
                if (in_array($estado_proyecto, ['validado','en_votacion','seleccionado','en_ejecucion','ejecutado'], true)) {
                    return 'done';
                }
                return 'pending';
            case 'votacion':
                if (in_array($estado_proyecto, ['en_votacion','seleccionado','en_ejecucion','ejecutado'], true)) {
                    return 'done';
                }
                if ($fase_edicion === 'votacion' || $estado_proyecto === 'en_votacion') {
                    return 'active';
                }
                return 'pending';
            case 'implementacion':
                if ($estado_proyecto === 'ejecutado') {
                    return 'done';
                }
                if ($estado_proyecto === 'en_ejecucion' || $porcentaje > 0) {
                    return 'active';
                }
                return $fase_edicion === 'implementacion' ? 'active' : 'pending';
        }

        return 'pending';
    }

    /**
     * Actualiza contadores de votos
     */
    private function actualizar_contadores_votos($edicion_id) {
        global $wpdb;
        $tabla_votos = $wpdb->prefix . 'flavor_pp_votos';
        $tabla_proyectos = $wpdb->prefix . 'flavor_pp_proyectos';

        // Actualizar votos por proyecto
        $wpdb->query("
            UPDATE $tabla_proyectos p
            SET votos_recibidos = (
                SELECT COUNT(*) FROM $tabla_votos v
                WHERE v.proyecto_id = p.id AND v.edicion_id = $edicion_id
            )
            WHERE p.edicion_id = $edicion_id
        ");
    }

    /**
     * {@inheritdoc}
     */
    public function get_tool_definitions() {
        return [
            [
                'name' => 'pp_info',
                'description' => 'Ver información de los presupuestos participativos actuales',
                'input_schema' => [
                    'type' => 'object',
                    'properties' => [],
                ],
            ],
            [
                'name' => 'pp_proponer',
                'description' => 'Proponer un proyecto para presupuestos participativos',
                'input_schema' => [
                    'type' => 'object',
                    'properties' => [
                        'titulo' => ['type' => 'string', 'description' => 'Título del proyecto'],
                        'descripcion' => ['type' => 'string', 'description' => 'Descripción detallada'],
                        'categoria' => ['type' => 'string', 'description' => 'Categoría'],
                        'presupuesto' => ['type' => 'number', 'description' => 'Presupuesto estimado en euros'],
                    ],
                    'required' => ['titulo', 'descripcion', 'presupuesto'],
                ],
            ],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function get_knowledge_base() {
        return <<<KNOWLEDGE
**Presupuestos Participativos**

Democracia económica directa: los vecinos deciden en qué se invierte parte del presupuesto municipal.

**Fases del proceso:**
1. Propuestas: Cualquier vecino puede proponer proyectos
2. Evaluación: Técnicos evalúan viabilidad y coste
3. Votación: Los vecinos votan sus proyectos favoritos
4. Implementación: Se ejecutan los proyectos ganadores

**Tipos de proyectos:**
- Infraestructura: Arreglos, mejoras urbanas
- Medio ambiente: Zonas verdes, reciclaje
- Cultura: Actividades, espacios culturales
- Deporte: Instalaciones deportivas
- Social: Servicios sociales, inclusión
- Educación: Formación, bibliotecas
- Accesibilidad: Rampas, adaptaciones

**Reglas:**
- Cada vecino puede votar hasta 3 proyectos
- Los proyectos se ordenan por votos recibidos
- Se aprueban en orden hasta agotar presupuesto
- Cada proyecto tiene un presupuesto mínimo y máximo
KNOWLEDGE;
    }

    /**
     * {@inheritdoc}
     */
    public function get_faqs() {
        return [
            [
                'pregunta' => '¿Qué son los presupuestos participativos?',
                'respuesta' => 'Es un proceso donde los vecinos proponemos y votamos proyectos para el barrio, y el ayuntamiento ejecuta los más votados.',
            ],
            [
                'pregunta' => '¿Cuántos proyectos puedo votar?',
                'respuesta' => 'Normalmente puedes votar hasta 3 proyectos, eligiendo tus favoritos.',
            ],
        ];
    }

    /**
     * Componentes web del módulo
     */
    public function get_web_components() {
        return [
            'hero' => [
                'label' => __('Hero Presupuestos', 'flavor-chat-ia'),
                'description' => __('Sección hero con fase actual del proceso', 'flavor-chat-ia'),
                'category' => 'hero',
                'icon' => 'dashicons-money-alt',
                'fields' => [
                    'titulo' => [
                        'type' => 'text',
                        'label' => __('Título', 'flavor-chat-ia'),
                        'default' => __('Presupuestos Participativos', 'flavor-chat-ia'),
                    ],
                    'subtitulo' => [
                        'type' => 'textarea',
                        'label' => __('Subtítulo', 'flavor-chat-ia'),
                        'default' => __('Decide en qué se invierte el presupuesto de tu barrio', 'flavor-chat-ia'),
                    ],
                    'mostrar_fase' => [
                        'type' => 'toggle',
                        'label' => __('Mostrar fase actual', 'flavor-chat-ia'),
                        'default' => true,
                    ],
                    'mostrar_presupuesto' => [
                        'type' => 'toggle',
                        'label' => __('Mostrar presupuesto total', 'flavor-chat-ia'),
                        'default' => true,
                    ],
                ],
                'template' => 'presupuestos-participativos/hero',
            ],
            'proyectos_grid' => [
                'label' => __('Grid de Proyectos', 'flavor-chat-ia'),
                'description' => __('Listado de proyectos propuestos', 'flavor-chat-ia'),
                'category' => 'listings',
                'icon' => 'dashicons-portfolio',
                'fields' => [
                    'titulo' => [
                        'type' => 'text',
                        'label' => __('Título', 'flavor-chat-ia'),
                        'default' => __('Proyectos Propuestos', 'flavor-chat-ia'),
                    ],
                    'categoria' => [
                        'type' => 'select',
                        'label' => __('Filtrar por categoría', 'flavor-chat-ia'),
                        'options' => ['todas', 'infraestructura', 'cultura', 'medio_ambiente', 'social'],
                        'default' => 'todas',
                    ],
                    'ordenar' => [
                        'type' => 'select',
                        'label' => __('Ordenar por', 'flavor-chat-ia'),
                        'options' => ['votos', 'coste', 'recientes'],
                        'default' => 'votos',
                    ],
                    'limite' => [
                        'type' => 'number',
                        'label' => __('Número máximo', 'flavor-chat-ia'),
                        'default' => 9,
                    ],
                ],
                'template' => 'presupuestos-participativos/proyectos-grid',
            ],
            'fases_proceso' => [
                'label' => __('Fases del Proceso', 'flavor-chat-ia'),
                'description' => __('Timeline del proceso participativo', 'flavor-chat-ia'),
                'category' => 'content',
                'icon' => 'dashicons-backup',
                'fields' => [
                    'titulo' => [
                        'type' => 'text',
                        'label' => __('Título', 'flavor-chat-ia'),
                        'default' => __('¿Cómo funciona?', 'flavor-chat-ia'),
                    ],
                ],
                'template' => 'presupuestos-participativos/fases',
            ],
            'resultados' => [
                'label' => __('Resultados Votación', 'flavor-chat-ia'),
                'description' => __('Proyectos ganadores y estadísticas', 'flavor-chat-ia'),
                'category' => 'content',
                'icon' => 'dashicons-chart-bar',
                'fields' => [
                    'titulo' => [
                        'type' => 'text',
                        'label' => __('Título', 'flavor-chat-ia'),
                        'default' => __('Resultados', 'flavor-chat-ia'),
                    ],
                    'edicion' => [
                        'type' => 'select',
                        'label' => __('Edición', 'flavor-chat-ia'),
                        'options' => ['actual', 'anterior'],
                        'default' => 'actual',
                    ],
                ],
                'template' => 'presupuestos-participativos/resultados',
            ],
            'cta_proponer' => [
                'label' => __('CTA Proponer Proyecto', 'flavor-chat-ia'),
                'description' => __('Llamada a acción para proponer proyecto', 'flavor-chat-ia'),
                'category' => 'cta',
                'icon' => 'dashicons-plus-alt',
                'fields' => [
                    'titulo' => [
                        'type' => 'text',
                        'label' => __('Título', 'flavor-chat-ia'),
                        'default' => __('¿Tienes un proyecto para el barrio?', 'flavor-chat-ia'),
                    ],
                    'boton_texto' => [
                        'type' => 'text',
                        'label' => __('Texto del botón', 'flavor-chat-ia'),
                        'default' => __('Proponer Proyecto', 'flavor-chat-ia'),
                    ],
                ],
                'template' => 'presupuestos-participativos/cta-proponer',
            ],
        ];
    }
    /**
     * Crea/actualiza páginas del módulo si es necesario
     */
    public function maybe_create_pages() {
        if (!class_exists('Flavor_Page_Creator')) {
            return;
        }

        // En admin: refrescar páginas del módulo
        if (is_admin()) {
            Flavor_Page_Creator::refresh_module_pages('presupuestos_participativos');
            return;
        }

        // En frontend: crear páginas si no existen (solo una vez)
        $pagina = get_page_by_path('presupuestos-participativos');
        if (!$pagina && !get_option('flavor_presupuestos_participativos_pages_created')) {
            Flavor_Page_Creator::create_pages_for_modules(['presupuestos_participativos']);
            update_option('flavor_presupuestos_participativos_pages_created', 1, false);
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
                'title' => __('Presupuestos Participativos', 'flavor-chat-ia'),
                'slug' => 'presupuestos-participativos',
                'content' => '<h1>' . __('Presupuestos Participativos', 'flavor-chat-ia') . '</h1>
<p>' . __('Decide en qué se invierte el presupuesto de tu barrio. Propón proyectos, vota y sigue la ejecución de los ganadores.', 'flavor-chat-ia') . '</p>

[flavor_module_listing module="presupuestos_participativos" action="listar" columnas="3" limite="12"]',
                'parent' => 0,
            ],
            [
                'title' => __('Proponer Proyecto', 'flavor-chat-ia'),
                'slug' => 'presupuestos-participativos/proponer',
                'content' => '<h1>' . __('Proponer Nuevo Proyecto', 'flavor-chat-ia') . '</h1>
<p>' . __('Presenta tu idea de proyecto para el barrio. Indica el presupuesto estimado y la ubicación propuesta.', 'flavor-chat-ia') . '</p>

[flavor_module_listing module="presupuestos_participativos" action="proponer"]',
                'parent' => 'presupuestos-participativos',
            ],
            [
                'title' => __('Votar Proyectos', 'flavor-chat-ia'),
                'slug' => 'presupuestos-participativos/votar',
                'content' => '<h1>' . __('Votar Proyectos', 'flavor-chat-ia') . '</h1>
<p>' . __('Selecciona tus proyectos favoritos. Puedes votar hasta 3 proyectos diferentes.', 'flavor-chat-ia') . '</p>

[flavor_module_listing module="presupuestos_participativos" action="votar" columnas="2" limite="20"]',
                'parent' => 'presupuestos-participativos',
            ],
            [
                'title' => __('Resultados', 'flavor-chat-ia'),
                'slug' => 'presupuestos-participativos/resultados',
                'content' => '<h1>' . __('Resultados de la Votación', 'flavor-chat-ia') . '</h1>
<p>' . __('Consulta los proyectos ganadores y el estado de ejecución de cada uno.', 'flavor-chat-ia') . '</p>

[flavor_module_listing module="presupuestos_participativos" action="resultados" columnas="2" limite="10"]',
                'parent' => 'presupuestos-participativos',
            ],
        ];
    }

    /**
     * Registrar páginas de administración
     */
    public function registrar_paginas_admin() {
        $capability = 'manage_options';

        // Páginas ocultas (sin menú visible en el sidebar)
        add_submenu_page(null, __('Presupuestos', 'flavor-chat-ia'), __('Presupuestos', 'flavor-chat-ia'), $capability, 'presupuestos-participativos', [$this, 'render_pagina_dashboard']);
        add_submenu_page(null, __('Proyectos', 'flavor-chat-ia'), __('Proyectos', 'flavor-chat-ia'), $capability, 'pp-proyectos', [$this, 'render_pagina_proyectos']);
        add_submenu_page(null, __('Presupuesto', 'flavor-chat-ia'), __('Presupuesto', 'flavor-chat-ia'), $capability, 'pp-presupuesto', [$this, 'render_pagina_presupuesto']);
        add_submenu_page(null, __('Votos', 'flavor-chat-ia'), __('Votos', 'flavor-chat-ia'), $capability, 'pp-votos', [$this, 'render_pagina_votos']);
        add_submenu_page(null, __('Resultados', 'flavor-chat-ia'), __('Resultados', 'flavor-chat-ia'), $capability, 'pp-resultados', [$this, 'render_pagina_resultados']);
    }

    public function render_pagina_dashboard() {
        $views_path = dirname(__FILE__) . '/views/dashboard.php';
        if (file_exists($views_path)) { include $views_path; }
        else { echo '<div class="wrap"><h1>' . esc_html__('Dashboard Presupuestos Participativos', 'flavor-chat-ia') . '</h1></div>'; }
    }

    public function render_pagina_proyectos() {
        $views_path = dirname(__FILE__) . '/views/proyectos.php';
        if (file_exists($views_path)) { include $views_path; }
        else { echo '<div class="wrap"><h1>' . esc_html__('Gestión de Proyectos', 'flavor-chat-ia') . '</h1></div>'; }
    }

    public function render_pagina_presupuesto() {
        $views_path = dirname(__FILE__) . '/views/presupuesto.php';
        if (file_exists($views_path)) { include $views_path; }
        else { echo '<div class="wrap"><h1>' . esc_html__('Gestión de Presupuesto', 'flavor-chat-ia') . '</h1></div>'; }
    }

    public function render_pagina_votos() {
        $views_path = dirname(__FILE__) . '/views/votos.php';
        if (file_exists($views_path)) { include $views_path; }
        else { echo '<div class="wrap"><h1>' . esc_html__('Gestión de Votos', 'flavor-chat-ia') . '</h1></div>'; }
    }

    public function render_pagina_resultados() {
        $views_path = dirname(__FILE__) . '/views/resultados.php';
        if (file_exists($views_path)) { include $views_path; }
        else { echo '<div class="wrap"><h1>' . esc_html__('Resultados', 'flavor-chat-ia') . '</h1></div>'; }
    }


    /**
     * Inicializa el dashboard tab del módulo
     */
    private function inicializar_dashboard_tab() {
        $archivo_tab = dirname(__FILE__) . '/class-presupuestos-participativos-dashboard-tab.php';
        if (file_exists($archivo_tab)) {
            require_once $archivo_tab;
            Flavor_Presupuestos_Participativos_Dashboard_Tab::get_instance();
        }
    }
}

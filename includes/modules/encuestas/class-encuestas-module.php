<?php
/**
 * Módulo de Encuestas/Formularios Reutilizable para Flavor Chat IA
 *
 * Sistema central de encuestas que puede integrarse en múltiples contextos:
 * red social, chat-grupos, foros, comunidades, etc.
 *
 * @package FlavorChatIA
 * @since 1.0.0
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Módulo de Encuestas y Formularios
 */
class Flavor_Chat_Encuestas_Module extends Flavor_Chat_Module_Base {

    use Flavor_Module_Admin_Pages_Trait;
    use Flavor_Module_Notifications_Trait;

    /**
     * Versión del módulo
     */
    const VERSION = '1.0.0';

    /**
     * Tipos de encuesta disponibles
     */
    const TIPOS_ENCUESTA = ['encuesta', 'formulario', 'quiz'];

    /**
     * Estados de encuesta
     */
    const ESTADOS_ENCUESTA = ['borrador', 'activa', 'cerrada', 'archivada'];

    /**
     * Tipos de campo disponibles
     */
    const TIPOS_CAMPO = [
        'texto'              => 'Texto corto',
        'textarea'           => 'Texto largo',
        'email'              => 'Email',
        'telefono'           => 'Teléfono',
        'url'                => 'URL',
        'seleccion_unica'    => 'Selección única',
        'seleccion_multiple' => 'Selección múltiple',
        'fecha'              => 'Fecha',
        'fecha_hora'         => 'Fecha y hora',
        'numero'             => 'Número',
        'rango'              => 'Rango (slider)',
        'escala'             => 'Escala (1-10)',
        'nps'                => 'NPS (0-10)',
        'si_no'              => 'Sí/No',
        'estrellas'          => 'Estrellas (1-5)',
    ];

    /**
     * Contextos válidos para integración
     */
    const CONTEXTOS_VALIDOS = [
        'chat_grupo',
        'foro',
        'red_social',
        'comunidad',
        'evento',
        'curso',
        'general',
    ];

    /**
     * Instancia del renderer
     *
     * @var Flavor_Encuestas_Renderer
     */
    private $renderer = null;

    /**
     * Instancia de la API
     *
     * @var Flavor_Encuestas_API
     */
    private $api = null;

    /**
     * Constructor
     */
    public function __construct() {
        $this->id = 'encuestas';
        $this->name = 'Encuestas y Formularios';
        $this->description = 'Sistema de encuestas y formularios reutilizable para múltiples contextos.';
        $this->icon = 'dashicons-forms';
        $this->color = '#8b5cf6';
        $this->category = 'comunicacion';

        parent::__construct();

        add_action('admin_menu', [$this, 'registrar_paginas_admin']);
    }

    /**
     * {@inheritdoc}
     */
    public function can_activate() {
        global $wpdb;
        $tabla_encuestas = $wpdb->prefix . 'flavor_encuestas';
        return Flavor_Chat_Helpers::tabla_existe($tabla_encuestas);
    }

    /**
     * {@inheritdoc}
     */
    public function get_activation_error() {
        if (!$this->can_activate()) {
            return __('Las tablas de Encuestas no están creadas. Activa el módulo para crearlas automáticamente.', 'flavor-chat-ia');
        }
        return '';
    }

    /**
     * {@inheritdoc}
     */
    protected function get_default_settings() {
        return [
            'permitir_encuestas_anonimas'    => true,
            'permitir_multiples_respuestas'  => false,
            'moderacion_encuestas'           => true,
            'max_opciones_por_pregunta'      => 10,
            'max_campos_por_encuesta'        => 20,
            'duracion_default_dias'          => 7,
            'notificar_nuevas_respuestas'    => true,
            'notificar_cierre_encuesta'      => true,
            'permitir_exportar_resultados'   => true,
            'mostrar_estadisticas_publicas'  => true,
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function init() {
        // Cargar dependencias
        $this->cargar_dependencias();

        // Hooks básicos
        add_action('init', [$this, 'maybe_create_tables']);
        add_action('init', [$this, 'register_shortcodes']);
        add_action('wp_enqueue_scripts', [$this, 'enqueue_assets']);

        // AJAX handlers
        $this->registrar_ajax_handlers();

        // REST API
        add_action('rest_api_init', [$this, 'register_rest_routes']);

        // Cron para cerrar encuestas automáticamente
        if (!wp_next_scheduled('flavor_encuestas_cerrar_expiradas')) {
            wp_schedule_event(time(), 'hourly', 'flavor_encuestas_cerrar_expiradas');
        }
        add_action('flavor_encuestas_cerrar_expiradas', [$this, 'cerrar_encuestas_expiradas']);

        // Panel unificado
        $this->registrar_en_panel_unificado();
        // Cargar Dashboard Tab
        $this->inicializar_dashboard_tab();
    }

    /**
     * Carga las dependencias del módulo
     */
    private function cargar_dependencias() {
        $ruta_modulo = dirname(__FILE__) . '/';

        // Cargar renderer
        if (file_exists($ruta_modulo . 'class-encuestas-renderer.php')) {
            require_once $ruta_modulo . 'class-encuestas-renderer.php';
            $this->renderer = new Flavor_Encuestas_Renderer($this);
        }

        // Cargar API
        if (file_exists($ruta_modulo . 'class-encuestas-api.php')) {
            require_once $ruta_modulo . 'class-encuestas-api.php';
            $this->api = new Flavor_Encuestas_API($this);
        }

        // Cargar Dashboard Tab
        if (file_exists($ruta_modulo . 'class-encuestas-dashboard-tab.php')) {
            require_once $ruta_modulo . 'class-encuestas-dashboard-tab.php';
            Flavor_Encuestas_Dashboard_Tab::get_instance();
        }
    }

    /**
     * Registra handlers AJAX
     */
    private function registrar_ajax_handlers() {
        $acciones = [
            'encuestas_crear',
            'encuestas_editar',
            'encuestas_eliminar',
            'encuestas_responder',
            'encuestas_obtener_resultados',
            'encuestas_cerrar',
            'encuestas_buscar_contexto',
            'encuestas_agregar_campo',
            'encuestas_eliminar_campo',
            'encuestas_reordenar_campos',
        ];

        foreach ($acciones as $accion) {
            add_action("wp_ajax_{$accion}", [$this, "ajax_{$accion}"]);
        }

        // Algunas acciones también disponibles sin login
        add_action('wp_ajax_nopriv_encuestas_responder', [$this, 'ajax_encuestas_responder']);
        add_action('wp_ajax_nopriv_encuestas_obtener_resultados', [$this, 'ajax_encuestas_obtener_resultados']);
    }

    /**
     * Registra rutas REST
     */
    public function register_rest_routes() {
        if ($this->api) {
            $this->api->register_routes();
        }
    }

    /**
     * Registra shortcodes
     */
    public function register_shortcodes() {
        add_shortcode('flavor_encuesta', [$this, 'shortcode_encuesta']);
        add_shortcode('flavor_encuesta_crear', [$this, 'shortcode_crear_encuesta']);
        add_shortcode('flavor_encuestas_contexto', [$this, 'shortcode_encuestas_contexto']);
        add_shortcode('flavor_encuesta_resultados', [$this, 'shortcode_resultados']);
        add_shortcode('flavor_encuesta_mini', [$this, 'shortcode_encuesta_mini']);

        // Aliases de compatibilidad para tabs legacy declaradas en renderer config.
        add_shortcode('encuestas_crear', [$this, 'shortcode_crear_encuesta']);
        add_shortcode('encuestas_mis_encuestas', [$this, 'shortcode_mis_encuestas']);
        add_shortcode('encuestas_resultados', [$this, 'shortcode_encuestas_resultados']);
        add_shortcode('flavor_encuestas_mis_encuestas', [$this, 'shortcode_mis_encuestas']);
        add_shortcode('flavor_encuestas_resultados', [$this, 'shortcode_encuestas_resultados']);
    }

    /**
     * Encola assets
     */
    public function enqueue_assets() {
        if (!$this->should_load_assets()) {
            return;
        }

        $ruta_assets = plugin_dir_url(__FILE__) . 'assets/';
        $asset_path_base = dirname(__FILE__) . '/assets/';
        $css_version = file_exists($asset_path_base . 'css/encuestas.css')
            ? (string) filemtime($asset_path_base . 'css/encuestas.css')
            : self::VERSION;
        $js_version = file_exists($asset_path_base . 'js/encuestas.js')
            ? (string) filemtime($asset_path_base . 'js/encuestas.js')
            : self::VERSION;

        wp_enqueue_style(
            'flavor-encuestas',
            $ruta_assets . 'css/encuestas.css',
            [],
            $css_version
        );

        wp_enqueue_script(
            'flavor-encuestas',
            $ruta_assets . 'js/encuestas.js',
            ['jquery'],
            $js_version,
            true
        );

        wp_localize_script('flavor-encuestas', 'flavorEncuestas', [
            'ajaxUrl'       => admin_url('admin-ajax.php'),
            'restUrl'       => rest_url('flavor/v1/encuestas'),
            'nonce'         => wp_create_nonce('flavor_encuestas_nonce'),
            'restNonce'     => wp_create_nonce('wp_rest'),
            'userId'        => get_current_user_id(),
            'isLoggedIn'    => is_user_logged_in(),
            'strings'       => [
                'confirmarEliminar'  => __('¿Eliminar esta encuesta?', 'flavor-chat-ia'),
                'confirmarCerrar'    => __('¿Cerrar esta encuesta? No se podrán añadir más respuestas.', 'flavor-chat-ia'),
                'enviando'           => __('Enviando...', 'flavor-chat-ia'),
                'graciasRespuesta'   => __('¡Gracias por tu respuesta!', 'flavor-chat-ia'),
                'error'              => __('Ha ocurrido un error', 'flavor-chat-ia'),
                'campoRequerido'     => __('Este campo es obligatorio', 'flavor-chat-ia'),
                'seleccionaOpcion'   => __('Selecciona una opción', 'flavor-chat-ia'),
                'buscando'           => __('Buscando...', 'flavor-chat-ia'),
                'sinResultados'      => __('No se han encontrado resultados', 'flavor-chat-ia'),
                'contextoObligatorio'=> __('Debes seleccionar un destino para el contexto elegido', 'flavor-chat-ia'),
            ],
            'tiposCampo'    => self::TIPOS_CAMPO,
        ]);
    }

    /**
     * Determina si se deben cargar assets
     */
    private function should_load_assets() {
        global $post;

        // Siempre cargar en páginas con shortcodes de encuestas
        if ($post && has_shortcode($post->post_content, 'flavor_encuesta')) {
            return true;
        }
        if ($post && has_shortcode($post->post_content, 'flavor_encuesta_crear')) {
            return true;
        }
        if ($post && has_shortcode($post->post_content, 'flavor_encuestas_contexto')) {
            return true;
        }

        // Portal dinámico: cargar assets en rutas de encuestas aunque el
        // contenido no provenga de un post con shortcode explícito.
        $is_flavor_app = (bool) get_query_var('flavor_app');
        $flavor_module = sanitize_key((string) get_query_var('flavor_module', ''));
        if ($is_flavor_app && in_array($flavor_module, ['encuestas', 'encuesta'], true)) {
            return true;
        }

        $request_uri = isset($_SERVER['REQUEST_URI']) ? wp_unslash((string) $_SERVER['REQUEST_URI']) : '';
        if ($request_uri !== '') {
            $uri = strtolower($request_uri);
            if (
                strpos($uri, '/mi-portal/encuestas') !== false ||
                preg_match('#/(encuestas|encuesta)(/|$)#', $uri)
            ) {
                return true;
            }
        }

        // Cargar si hay filtro que lo permita
        return apply_filters('flavor_encuestas_load_assets', false);
    }

    // =========================================================================
    // CRUD DE ENCUESTAS
    // =========================================================================

    /**
     * Crea una nueva encuesta
     *
     * @param array $datos Datos de la encuesta
     * @return int|WP_Error ID de la encuesta o error
     */
    public function crear_encuesta($datos) {
        global $wpdb;

        $usuario_id = get_current_user_id();
        if (!$usuario_id && empty($datos['autor_id'])) {
            return new WP_Error('no_auth', __('Debes iniciar sesión para crear encuestas', 'flavor-chat-ia'));
        }

        // Validar datos mínimos
        if (empty($datos['titulo'])) {
            return new WP_Error('titulo_vacio', __('El título es obligatorio', 'flavor-chat-ia'));
        }

        // Preparar datos
        $datos_encuesta = [
            'titulo'            => sanitize_text_field($datos['titulo']),
            'descripcion'       => isset($datos['descripcion']) ? wp_kses_post($datos['descripcion']) : '',
            'autor_id'          => $datos['autor_id'] ?? $usuario_id,
            'estado'            => in_array($datos['estado'] ?? '', self::ESTADOS_ENCUESTA) ? $datos['estado'] : 'borrador',
            'tipo'              => in_array($datos['tipo'] ?? '', self::TIPOS_ENCUESTA) ? $datos['tipo'] : 'encuesta',
            'contexto_tipo'     => in_array($datos['contexto_tipo'] ?? '', self::CONTEXTOS_VALIDOS) ? $datos['contexto_tipo'] : null,
            'contexto_id'       => !empty($datos['contexto_id']) ? absint($datos['contexto_id']) : null,
            'es_anonima'        => !empty($datos['es_anonima']) ? 1 : 0,
            'permite_multiples' => !empty($datos['permite_multiples']) ? 1 : 0,
            'mostrar_resultados'=> in_array($datos['mostrar_resultados'] ?? '', ['siempre', 'al_votar', 'al_cerrar', 'nunca'])
                                   ? $datos['mostrar_resultados'] : 'al_votar',
            'fecha_cierre'      => !empty($datos['fecha_cierre']) ? $datos['fecha_cierre'] : null,
            'configuracion'     => !empty($datos['configuracion']) ? wp_json_encode($datos['configuracion']) : null,
            'fecha_creacion'    => current_time('mysql'),
        ];

        $tabla = $wpdb->prefix . 'flavor_encuestas';
        $resultado = $wpdb->insert($tabla, $datos_encuesta);

        if ($resultado === false) {
            return new WP_Error('db_error', __('Error al crear la encuesta', 'flavor-chat-ia'));
        }

        $encuesta_id = $wpdb->insert_id;

        // Crear campos si se proporcionaron
        if (!empty($datos['campos']) && is_array($datos['campos'])) {
            foreach ($datos['campos'] as $indice => $campo) {
                $campo['encuesta_id'] = $encuesta_id;
                $campo['orden'] = $indice;
                $this->crear_campo($campo);
            }
        }

        do_action('flavor_encuesta_creada', $encuesta_id, $datos_encuesta);

        return $encuesta_id;
    }

    /**
     * Obtiene una encuesta con sus campos
     *
     * @param int $encuesta_id ID de la encuesta
     * @param bool $incluir_campos Incluir campos
     * @return object|null
     */
    public function obtener_encuesta($encuesta_id, $incluir_campos = true) {
        global $wpdb;

        $tabla = $wpdb->prefix . 'flavor_encuestas';
        $encuesta = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$tabla} WHERE id = %d",
            $encuesta_id
        ));

        if (!$encuesta) {
            return null;
        }

        // Parsear configuración JSON
        if (!empty($encuesta->configuracion)) {
            $encuesta->configuracion = json_decode($encuesta->configuracion, true);
        }

        // Incluir campos si se solicita
        if ($incluir_campos) {
            $encuesta->campos = $this->obtener_campos($encuesta_id);
        }

        return $encuesta;
    }

    /**
     * Actualiza una encuesta
     *
     * @param int $encuesta_id ID de la encuesta
     * @param array $datos Datos a actualizar
     * @return bool|WP_Error
     */
    public function actualizar_encuesta($encuesta_id, $datos) {
        global $wpdb;

        $encuesta = $this->obtener_encuesta($encuesta_id, false);
        if (!$encuesta) {
            return new WP_Error('no_encontrada', __('Encuesta no encontrada', 'flavor-chat-ia'));
        }

        // Verificar permisos
        if (!$this->puede_editar_encuesta($encuesta_id)) {
            return new WP_Error('sin_permisos', __('No tienes permisos para editar esta encuesta', 'flavor-chat-ia'));
        }

        $datos_actualizacion = [];

        // Campos actualizables
        $campos_permitidos = [
            'titulo', 'descripcion', 'estado', 'tipo', 'es_anonima',
            'permite_multiples', 'mostrar_resultados', 'fecha_cierre', 'configuracion'
        ];

        foreach ($campos_permitidos as $campo) {
            if (isset($datos[$campo])) {
                if ($campo === 'configuracion' && is_array($datos[$campo])) {
                    $datos_actualizacion[$campo] = wp_json_encode($datos[$campo]);
                } elseif ($campo === 'titulo') {
                    $datos_actualizacion[$campo] = sanitize_text_field($datos[$campo]);
                } elseif ($campo === 'descripcion') {
                    $datos_actualizacion[$campo] = wp_kses_post($datos[$campo]);
                } else {
                    $datos_actualizacion[$campo] = $datos[$campo];
                }
            }
        }

        if (empty($datos_actualizacion)) {
            return true; // Nada que actualizar
        }

        $tabla = $wpdb->prefix . 'flavor_encuestas';
        $resultado = $wpdb->update(
            $tabla,
            $datos_actualizacion,
            ['id' => $encuesta_id]
        );

        if ($resultado === false) {
            return new WP_Error('db_error', __('Error al actualizar la encuesta', 'flavor-chat-ia'));
        }

        do_action('flavor_encuesta_actualizada', $encuesta_id, $datos_actualizacion);

        return true;
    }

    /**
     * Elimina una encuesta
     *
     * @param int $encuesta_id ID de la encuesta
     * @return bool|WP_Error
     */
    public function eliminar_encuesta($encuesta_id) {
        global $wpdb;

        if (!$this->puede_editar_encuesta($encuesta_id)) {
            return new WP_Error('sin_permisos', __('No tienes permisos para eliminar esta encuesta', 'flavor-chat-ia'));
        }

        $prefix = $wpdb->prefix . 'flavor_';

        // Eliminar en orden: respuestas, participantes, campos, encuesta
        $wpdb->delete("{$prefix}encuestas_respuestas", ['encuesta_id' => $encuesta_id]);
        $wpdb->delete("{$prefix}encuestas_participantes", ['encuesta_id' => $encuesta_id]);
        $wpdb->delete("{$prefix}encuestas_campos", ['encuesta_id' => $encuesta_id]);
        $wpdb->delete("{$prefix}encuestas", ['id' => $encuesta_id]);

        do_action('flavor_encuesta_eliminada', $encuesta_id);

        return true;
    }

    /**
     * Cierra una encuesta
     *
     * @param int $encuesta_id ID de la encuesta
     * @return bool|WP_Error
     */
    public function cerrar_encuesta($encuesta_id) {
        return $this->actualizar_encuesta($encuesta_id, ['estado' => 'cerrada']);
    }

    // =========================================================================
    // CRUD DE CAMPOS
    // =========================================================================

    /**
     * Crea un campo para una encuesta
     *
     * @param array $datos Datos del campo
     * @return int|WP_Error ID del campo o error
     */
    public function crear_campo($datos) {
        global $wpdb;

        if (empty($datos['encuesta_id'])) {
            return new WP_Error('sin_encuesta', __('ID de encuesta requerido', 'flavor-chat-ia'));
        }

        if (empty($datos['etiqueta'])) {
            return new WP_Error('sin_etiqueta', __('La pregunta es obligatoria', 'flavor-chat-ia'));
        }

        $datos_campo = [
            'encuesta_id'   => absint($datos['encuesta_id']),
            'tipo'          => isset($datos['tipo']) && array_key_exists($datos['tipo'], self::TIPOS_CAMPO)
                              ? $datos['tipo'] : 'seleccion_unica',
            'etiqueta'      => sanitize_text_field($datos['etiqueta']),
            'descripcion'   => isset($datos['descripcion']) ? sanitize_textarea_field($datos['descripcion']) : '',
            'opciones'      => !empty($datos['opciones']) ? wp_json_encode($datos['opciones']) : null,
            'es_requerido'  => isset($datos['es_requerido']) ? (int) $datos['es_requerido'] : 1,
            'orden'         => isset($datos['orden']) ? absint($datos['orden']) : 0,
            'configuracion' => !empty($datos['configuracion']) ? wp_json_encode($datos['configuracion']) : null,
            'fecha_creacion'=> current_time('mysql'),
        ];

        $tabla = $wpdb->prefix . 'flavor_encuestas_campos';
        $resultado = $wpdb->insert($tabla, $datos_campo);

        if ($resultado === false) {
            return new WP_Error('db_error', __('Error al crear el campo', 'flavor-chat-ia'));
        }

        return $wpdb->insert_id;
    }

    /**
     * Obtiene los campos de una encuesta
     *
     * @param int $encuesta_id ID de la encuesta
     * @return array
     */
    public function obtener_campos($encuesta_id) {
        global $wpdb;

        $tabla = $wpdb->prefix . 'flavor_encuestas_campos';
        $campos = $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM {$tabla} WHERE encuesta_id = %d ORDER BY orden ASC",
            $encuesta_id
        ));

        // Parsear JSON en cada campo
        foreach ($campos as &$campo) {
            if (!empty($campo->opciones)) {
                $campo->opciones = json_decode($campo->opciones, true);
            }
            if (!empty($campo->configuracion)) {
                $campo->configuracion = json_decode($campo->configuracion, true);
            }
        }

        return $campos;
    }

    /**
     * Actualiza un campo
     *
     * @param int $campo_id ID del campo
     * @param array $datos Datos a actualizar
     * @return bool|WP_Error
     */
    public function actualizar_campo($campo_id, $datos) {
        global $wpdb;

        $datos_actualizacion = [];

        if (isset($datos['etiqueta'])) {
            $datos_actualizacion['etiqueta'] = sanitize_text_field($datos['etiqueta']);
        }
        if (isset($datos['descripcion'])) {
            $datos_actualizacion['descripcion'] = sanitize_textarea_field($datos['descripcion']);
        }
        if (isset($datos['tipo'])) {
            $datos_actualizacion['tipo'] = $datos['tipo'];
        }
        if (isset($datos['opciones'])) {
            $datos_actualizacion['opciones'] = wp_json_encode($datos['opciones']);
        }
        if (isset($datos['es_requerido'])) {
            $datos_actualizacion['es_requerido'] = (int) $datos['es_requerido'];
        }
        if (isset($datos['orden'])) {
            $datos_actualizacion['orden'] = absint($datos['orden']);
        }
        if (isset($datos['configuracion'])) {
            $datos_actualizacion['configuracion'] = wp_json_encode($datos['configuracion']);
        }

        $tabla = $wpdb->prefix . 'flavor_encuestas_campos';
        $resultado = $wpdb->update($tabla, $datos_actualizacion, ['id' => $campo_id]);

        return $resultado !== false;
    }

    /**
     * Elimina un campo
     *
     * @param int $campo_id ID del campo
     * @return bool
     */
    public function eliminar_campo($campo_id) {
        global $wpdb;

        // Eliminar respuestas del campo
        $wpdb->delete($wpdb->prefix . 'flavor_encuestas_respuestas', ['campo_id' => $campo_id]);

        // Eliminar campo
        return $wpdb->delete($wpdb->prefix . 'flavor_encuestas_campos', ['id' => $campo_id]) !== false;
    }

    // =========================================================================
    // SISTEMA DE RESPUESTAS
    // =========================================================================

    /**
     * Registra respuestas a una encuesta
     *
     * @param int $encuesta_id ID de la encuesta
     * @param array $respuestas Array de respuestas [campo_id => valor]
     * @return bool|WP_Error
     */
    public function registrar_respuestas($encuesta_id, $respuestas) {
        global $wpdb;

        $encuesta = $this->obtener_encuesta($encuesta_id);
        if (!$encuesta) {
            return new WP_Error('no_encontrada', __('Encuesta no encontrada', 'flavor-chat-ia'));
        }

        // Verificar que la encuesta está activa
        if ($encuesta->estado !== 'activa') {
            return new WP_Error('encuesta_cerrada', __('Esta encuesta ya no acepta respuestas', 'flavor-chat-ia'));
        }

        // Verificar fecha de cierre
        if ($encuesta->fecha_cierre && strtotime($encuesta->fecha_cierre) < time()) {
            return new WP_Error('encuesta_expirada', __('Esta encuesta ha expirado', 'flavor-chat-ia'));
        }

        $usuario_id = get_current_user_id();
        $sesion_id = $this->obtener_sesion_id();

        // Verificar si ya participó (si no es anónima o no permite múltiples)
        if (!$encuesta->es_anonima || !$encuesta->permite_multiples) {
            $ya_participo = $this->usuario_ya_participo($encuesta_id, $usuario_id, $sesion_id);
            if ($ya_participo) {
                return new WP_Error('ya_participo', __('Ya has respondido a esta encuesta', 'flavor-chat-ia'));
            }
        }

        // Validar campos requeridos
        foreach ($encuesta->campos as $campo) {
            if ($campo->es_requerido && empty($respuestas[$campo->id])) {
                return new WP_Error(
                    'campo_requerido',
                    sprintf(__('El campo "%s" es obligatorio', 'flavor-chat-ia'), $campo->etiqueta)
                );
            }
        }

        // Registrar participante
        $participante_id = $this->registrar_participante($encuesta_id, $usuario_id, $sesion_id);

        // Insertar respuestas
        $tabla_respuestas = $wpdb->prefix . 'flavor_encuestas_respuestas';

        foreach ($respuestas as $campo_id => $valor) {
            $campo_id = absint($campo_id);

            // Obtener info del campo
            $campo = $this->obtener_campo($campo_id);
            if (!$campo || $campo->encuesta_id != $encuesta_id) {
                continue;
            }

            // Procesar valor según tipo
            $datos_respuesta = $this->procesar_respuesta($campo, $valor, $usuario_id, $sesion_id);

            $wpdb->insert($tabla_respuestas, $datos_respuesta);
        }

        // Marcar participante como completado
        $this->marcar_participante_completado($participante_id);

        // Actualizar contadores
        $this->actualizar_contadores($encuesta_id);

        do_action('flavor_encuesta_respondida', $encuesta_id, $usuario_id, $respuestas);

        return true;
    }

    /**
     * Procesa una respuesta según el tipo de campo
     */
    private function procesar_respuesta($campo, $valor, $usuario_id, $sesion_id) {
        $datos = [
            'encuesta_id'    => $campo->encuesta_id,
            'campo_id'       => $campo->id,
            'usuario_id'     => $usuario_id ?: null,
            'sesion_id'      => $sesion_id,
            'fecha_respuesta'=> current_time('mysql'),
        ];

        // Procesar según tipo
        switch ($campo->tipo) {
            case 'seleccion_unica':
            case 'si_no':
                $datos['opcion_index'] = is_numeric($valor) ? absint($valor) : null;
                $datos['valor'] = is_array($campo->opciones) && isset($campo->opciones[$valor])
                    ? $campo->opciones[$valor] : $valor;
                break;

            case 'seleccion_multiple':
                if (is_array($valor)) {
                    $datos['valor'] = wp_json_encode($valor);
                    $datos['opcion_index'] = null; // Múltiples opciones
                } else {
                    $datos['valor'] = $valor;
                }
                break;

            case 'numero':
            case 'rango':
            case 'nps':
            case 'escala':
            case 'estrellas':
                $datos['valor'] = is_numeric($valor) ? floatval($valor) : null;
                $datos['opcion_index'] = absint($valor);
                break;

            case 'email':
                $datos['valor'] = sanitize_email($valor);
                break;

            case 'url':
                $datos['valor'] = esc_url_raw($valor);
                break;

            case 'telefono':
                $datos['valor'] = preg_replace('/[^0-9+()\-\s]/', '', (string) $valor);
                break;

            default:
                $datos['valor'] = sanitize_textarea_field($valor);
                break;
        }

        return $datos;
    }

    /**
     * Obtiene un campo por ID
     */
    private function obtener_campo($campo_id) {
        global $wpdb;
        $tabla = $wpdb->prefix . 'flavor_encuestas_campos';
        $campo = $wpdb->get_row($wpdb->prepare("SELECT * FROM {$tabla} WHERE id = %d", $campo_id));

        if ($campo && !empty($campo->opciones)) {
            $campo->opciones = json_decode($campo->opciones, true);
        }

        return $campo;
    }

    /**
     * Verifica si un usuario ya participó
     */
    public function usuario_ya_participo($encuesta_id, $usuario_id = null, $sesion_id = null) {
        global $wpdb;

        $tabla = $wpdb->prefix . 'flavor_encuestas_participantes';

        if ($usuario_id) {
            $existe = $wpdb->get_var($wpdb->prepare(
                "SELECT id FROM {$tabla} WHERE encuesta_id = %d AND usuario_id = %d",
                $encuesta_id,
                $usuario_id
            ));
        } else {
            $existe = $wpdb->get_var($wpdb->prepare(
                "SELECT id FROM {$tabla} WHERE encuesta_id = %d AND sesion_id = %s",
                $encuesta_id,
                $sesion_id
            ));
        }

        return (bool) $existe;
    }

    /**
     * Registra un participante
     */
    private function registrar_participante($encuesta_id, $usuario_id, $sesion_id) {
        global $wpdb;

        $tabla = $wpdb->prefix . 'flavor_encuestas_participantes';

        $wpdb->insert($tabla, [
            'encuesta_id'   => $encuesta_id,
            'usuario_id'    => $usuario_id ?: null,
            'sesion_id'     => $sesion_id,
            'completada'    => 0,
            'fecha_inicio'  => current_time('mysql'),
            'ip_address'    => $this->obtener_ip_usuario(),
            'user_agent'    => isset($_SERVER['HTTP_USER_AGENT']) ? substr($_SERVER['HTTP_USER_AGENT'], 0, 255) : null,
        ]);

        return $wpdb->insert_id;
    }

    /**
     * Marca participante como completado
     */
    private function marcar_participante_completado($participante_id) {
        global $wpdb;
        $tabla = $wpdb->prefix . 'flavor_encuestas_participantes';
        $wpdb->update(
            $tabla,
            ['completada' => 1, 'fecha_completada' => current_time('mysql')],
            ['id' => $participante_id]
        );
    }

    /**
     * Actualiza contadores de la encuesta
     */
    private function actualizar_contadores($encuesta_id) {
        global $wpdb;
        $prefix = $wpdb->prefix . 'flavor_';

        $total_respuestas = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM {$prefix}encuestas_respuestas WHERE encuesta_id = %d",
            $encuesta_id
        ));

        $total_participantes = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM {$prefix}encuestas_participantes WHERE encuesta_id = %d AND completada = 1",
            $encuesta_id
        ));

        $wpdb->update(
            $prefix . 'encuestas',
            [
                'total_respuestas'    => $total_respuestas,
                'total_participantes' => $total_participantes,
            ],
            ['id' => $encuesta_id]
        );
    }

    // =========================================================================
    // RESULTADOS Y ESTADÍSTICAS
    // =========================================================================

    /**
     * Obtiene los resultados de una encuesta
     *
     * @param int $encuesta_id ID de la encuesta
     * @return array
     */
    public function obtener_resultados($encuesta_id) {
        global $wpdb;

        $encuesta = $this->obtener_encuesta($encuesta_id);
        if (!$encuesta) {
            return [];
        }

        $resultados = [
            'encuesta_id'         => $encuesta_id,
            'titulo'              => $encuesta->titulo,
            'total_participantes' => $encuesta->total_participantes,
            'total_respuestas'    => $encuesta->total_respuestas,
            'estado'              => $encuesta->estado,
            'campos'              => [],
        ];

        $tabla_respuestas = $wpdb->prefix . 'flavor_encuestas_respuestas';

        foreach ($encuesta->campos as $campo) {
            $resultado_campo = [
                'id'          => $campo->id,
                'tipo'        => $campo->tipo,
                'etiqueta'    => $campo->etiqueta,
                'opciones'    => $campo->opciones,
                'respuestas'  => [],
            ];

            // Obtener respuestas según tipo
            switch ($campo->tipo) {
                case 'seleccion_unica':
                case 'si_no':
                case 'estrellas':
                case 'escala':
                    // Contar por opción
                    $conteos = $wpdb->get_results($wpdb->prepare(
                        "SELECT opcion_index, COUNT(*) as total
                         FROM {$tabla_respuestas}
                         WHERE campo_id = %d
                         GROUP BY opcion_index",
                        $campo->id
                    ), ARRAY_A);

                    $resultado_campo['conteos'] = [];
                    foreach ($conteos as $conteo) {
                        $resultado_campo['conteos'][$conteo['opcion_index']] = (int) $conteo['total'];
                    }
                    break;

                case 'seleccion_multiple':
                    // Obtener todas las respuestas y contar
                    $respuestas_raw = $wpdb->get_col($wpdb->prepare(
                        "SELECT valor FROM {$tabla_respuestas} WHERE campo_id = %d",
                        $campo->id
                    ));

                    $conteos = [];
                    foreach ($respuestas_raw as $resp) {
                        $indices = json_decode($resp, true);
                        if (is_array($indices)) {
                            foreach ($indices as $idx) {
                                $conteos[$idx] = ($conteos[$idx] ?? 0) + 1;
                            }
                        }
                    }
                    $resultado_campo['conteos'] = $conteos;
                    break;

                case 'numero':
                case 'rango':
                case 'nps':
                    // Estadísticas numéricas
                    $stats = $wpdb->get_row($wpdb->prepare(
                        "SELECT
                            AVG(CAST(valor AS DECIMAL(10,2))) as promedio,
                            MIN(CAST(valor AS DECIMAL(10,2))) as minimo,
                            MAX(CAST(valor AS DECIMAL(10,2))) as maximo,
                            COUNT(*) as total
                         FROM {$tabla_respuestas}
                         WHERE campo_id = %d",
                        $campo->id
                    ), ARRAY_A);
                    $resultado_campo['estadisticas'] = $stats;
                    break;

                default:
                    // Texto: listar respuestas (últimas 50)
                    $textos = $wpdb->get_col($wpdb->prepare(
                        "SELECT valor FROM {$tabla_respuestas} WHERE campo_id = %d ORDER BY fecha_respuesta DESC LIMIT 50",
                        $campo->id
                    ));
                    $resultado_campo['respuestas_texto'] = $textos;
                    break;
            }

            $resultados['campos'][] = $resultado_campo;
        }

        return $resultados;
    }

    /**
     * Verifica si el usuario puede ver resultados
     */
    public function puede_ver_resultados($encuesta_id) {
        $encuesta = $this->obtener_encuesta($encuesta_id, false);
        if (!$encuesta) {
            return false;
        }

        $usuario_id = get_current_user_id();

        // Autor siempre puede ver
        if ($usuario_id && $encuesta->autor_id == $usuario_id) {
            return true;
        }

        // Admin siempre puede ver
        if (current_user_can('manage_options')) {
            return true;
        }

        // Según configuración de mostrar_resultados
        switch ($encuesta->mostrar_resultados) {
            case 'siempre':
                return true;
            case 'al_votar':
                return $this->usuario_ya_participo($encuesta_id, $usuario_id, $this->obtener_sesion_id());
            case 'al_cerrar':
                return $encuesta->estado === 'cerrada';
            case 'nunca':
                return false;
        }

        return false;
    }

    // =========================================================================
    // CONSULTAS Y LISTADOS
    // =========================================================================

    /**
     * Lista encuestas por contexto
     *
     * @param string $contexto_tipo Tipo de contexto
     * @param int $contexto_id ID del contexto
     * @param array $args Argumentos adicionales
     * @return array
     */
    public function listar_por_contexto($contexto_tipo, $contexto_id, $args = []) {
        global $wpdb;

        $defaults = [
            'estado'   => 'activa',
            'limit'    => 20,
            'offset'   => 0,
            'orderby'  => 'fecha_creacion',
            'order'    => 'DESC',
        ];

        $args = wp_parse_args($args, $defaults);
        $tabla = $wpdb->prefix . 'flavor_encuestas';

        $where = "WHERE contexto_tipo = %s AND contexto_id = %d";
        $valores = [$contexto_tipo, $contexto_id];

        if ($args['estado']) {
            $where .= " AND estado = %s";
            $valores[] = $args['estado'];
        }

        $orderby = sanitize_sql_orderby("{$args['orderby']} {$args['order']}") ?: 'fecha_creacion DESC';

        $sql = $wpdb->prepare(
            "SELECT * FROM {$tabla} {$where} ORDER BY {$orderby} LIMIT %d OFFSET %d",
            array_merge($valores, [$args['limit'], $args['offset']])
        );

        return $wpdb->get_results($sql);
    }

    /**
     * Lista encuestas de un usuario
     */
    public function listar_por_usuario($usuario_id, $args = []) {
        global $wpdb;

        $defaults = [
            'estado'  => null,
            'limit'   => 20,
            'offset'  => 0,
        ];

        $args = wp_parse_args($args, $defaults);
        $tabla = $wpdb->prefix . 'flavor_encuestas';

        $where = "WHERE autor_id = %d";
        $valores = [$usuario_id];

        if ($args['estado']) {
            $where .= " AND estado = %s";
            $valores[] = $args['estado'];
        }

        $sql = $wpdb->prepare(
            "SELECT * FROM {$tabla} {$where} ORDER BY fecha_creacion DESC LIMIT %d OFFSET %d",
            array_merge($valores, [$args['limit'], $args['offset']])
        );

        return $wpdb->get_results($sql);
    }

    // =========================================================================
    // SHORTCODES
    // =========================================================================

    /**
     * Shortcode: Mostrar encuesta
     */
    public function shortcode_encuesta($atts) {
        $atts = shortcode_atts([
            'id' => 0,
        ], $atts);

        if (!$atts['id']) {
            return '';
        }

        if ($this->renderer) {
            return $this->renderer->render_encuesta($atts['id']);
        }

        return '';
    }

    /**
     * Shortcode: Formulario de creación
     */
    public function shortcode_crear_encuesta($atts) {
        $atts = shortcode_atts([
            'contexto'    => 'general',
            'contexto_id' => 0,
        ], $atts);

        if ($this->renderer) {
            return $this->renderer->render_formulario_crear($atts);
        }

        return '';
    }

    /**
     * Shortcode: Mis encuestas creadas.
     */
    public function shortcode_mis_encuestas($atts) {
        if (!is_user_logged_in()) {
            return '';
        }

        $atts = shortcode_atts([
            'limit' => 10,
        ], $atts);

        $encuestas = $this->listar_por_usuario(get_current_user_id(), [
            'limit' => absint($atts['limit']),
        ]);

        if (empty($encuestas)) {
            return '<div class="flavor-encuestas-lista__empty-state">' .
                '<p class="flavor-encuestas-lista__empty">' . esc_html__('No has creado encuestas todavía.', 'flavor-chat-ia') . '</p>' .
                '<p><a class="flavor-encuestas-lista__empty-cta" href="' . esc_url(home_url('/mi-portal/encuestas/crear/')) . '">' . esc_html__('Crear encuesta', 'flavor-chat-ia') . '</a></p>' .
                '</div>';
        }

        ob_start();
        ?>
        <div class="flavor-encuestas-lista">
            <?php foreach ($encuestas as $encuesta): ?>
                <div class="flavor-encuestas-lista__item">
                    <a class="flavor-encuestas-lista__link" href="<?php echo esc_url(home_url('/mi-portal/encuestas/' . absint($encuesta->id) . '/')); ?>">
                        <h4 class="flavor-encuestas-lista__titulo"><?php echo esc_html($encuesta->titulo); ?></h4>
                        <div class="flavor-encuestas-lista__meta">
                            <span><?php echo esc_html(ucfirst((string) $encuesta->estado)); ?></span>
                            <span><?php echo esc_html((int) $encuesta->total_participantes); ?> <?php esc_html_e('participantes', 'flavor-chat-ia'); ?></span>
                        </div>
                    </a>
                </div>
            <?php endforeach; ?>
        </div>
        <?php
        return ob_get_clean();
    }

    /**
     * Shortcode: Listar encuestas de contexto
     */
    public function shortcode_encuestas_contexto($atts) {
        $atts = shortcode_atts([
            'tipo'   => 'general',
            'id'     => 0,
            'estado' => 'activa',
            'limit'  => 10,
        ], $atts);

        if ($this->renderer) {
            return $this->renderer->render_lista_contexto($atts);
        }

        return '';
    }

    /**
     * Shortcode: Solo resultados
     */
    public function shortcode_resultados($atts) {
        $atts = shortcode_atts([
            'id'      => 0,
            'formato' => 'barras', // barras, pastel, texto
        ], $atts);

        if (!$atts['id']) {
            return '';
        }

        if ($this->renderer) {
            return $this->renderer->render_resultados($atts['id'], $atts['formato']);
        }

        return '';
    }

    /**
     * Shortcode: Resumen de encuestas con resultados.
     */
    public function shortcode_encuestas_resultados($atts) {
        global $wpdb;

        $atts = shortcode_atts([
            'limit' => 8,
        ], $atts);

        $tabla = $wpdb->prefix . 'flavor_encuestas';
        $limit = max(1, min(20, absint($atts['limit'])));

        $encuestas = $wpdb->get_results(
            $wpdb->prepare(
                "SELECT id, titulo, estado, total_respuestas, total_participantes
                 FROM {$tabla}
                 WHERE total_respuestas > 0
                 ORDER BY fecha_creacion DESC
                 LIMIT %d",
                $limit
            )
        );

        if (empty($encuestas)) {
            return '<div class="flavor-encuestas-lista__empty-state">' .
                '<p class="flavor-encuestas-lista__empty">' . esc_html__('No hay resultados disponibles todavía.', 'flavor-chat-ia') . '</p>' .
                '<p><a class="flavor-encuestas-lista__empty-cta" href="' . esc_url(home_url('/mi-portal/encuestas/crear/')) . '">' . esc_html__('Crear encuesta', 'flavor-chat-ia') . '</a></p>' .
                '</div>';
        }

        ob_start();
        ?>
        <div class="flavor-encuestas-lista">
            <?php foreach ($encuestas as $encuesta): ?>
                <div class="flavor-encuestas-lista__item">
                    <a class="flavor-encuestas-lista__link" href="<?php echo esc_url(home_url('/mi-portal/encuestas/' . absint($encuesta->id) . '/')); ?>">
                        <h4 class="flavor-encuestas-lista__titulo"><?php echo esc_html($encuesta->titulo); ?></h4>
                        <div class="flavor-encuestas-lista__meta">
                            <span><?php echo esc_html((int) $encuesta->total_respuestas); ?> <?php esc_html_e('respuestas', 'flavor-chat-ia'); ?></span>
                            <span><?php echo esc_html((int) $encuesta->total_participantes); ?> <?php esc_html_e('participantes', 'flavor-chat-ia'); ?></span>
                        </div>
                    </a>
                </div>
            <?php endforeach; ?>
        </div>
        <?php
        return ob_get_clean();
    }

    /**
     * Shortcode: Versión mini para chat
     */
    public function shortcode_encuesta_mini($atts) {
        $atts = shortcode_atts([
            'id' => 0,
        ], $atts);

        if (!$atts['id']) {
            return '';
        }

        if ($this->renderer) {
            return $this->renderer->render_encuesta_mini($atts['id']);
        }

        return '';
    }

    // =========================================================================
    // AJAX HANDLERS
    // =========================================================================

    /**
     * AJAX: Crear encuesta
     */
    public function ajax_encuestas_crear() {
        check_ajax_referer('flavor_encuestas_nonce', 'nonce');

        if (!is_user_logged_in()) {
            wp_send_json_error(['message' => __('Debes iniciar sesión', 'flavor-chat-ia')]);
        }

        $datos = [
            'titulo'            => sanitize_text_field($_POST['titulo'] ?? ''),
            'descripcion'       => wp_kses_post($_POST['descripcion'] ?? ''),
            'tipo'              => sanitize_text_field($_POST['tipo'] ?? 'encuesta'),
            'contexto_tipo'     => sanitize_text_field($_POST['contexto_tipo'] ?? ''),
            'contexto_id'       => absint($_POST['contexto_id'] ?? 0),
            'es_anonima'        => !empty($_POST['es_anonima']),
            'permite_multiples' => !empty($_POST['permite_multiples']),
            'mostrar_resultados'=> sanitize_text_field($_POST['mostrar_resultados'] ?? 'al_votar'),
            'fecha_cierre'      => sanitize_text_field($_POST['fecha_cierre'] ?? ''),
            'campos'            => isset($_POST['campos']) ? $_POST['campos'] : [],
            'estado'            => 'activa',
        ];

        $resultado = $this->crear_encuesta($datos);

        if (is_wp_error($resultado)) {
            wp_send_json_error(['message' => $resultado->get_error_message()]);
        }

        wp_send_json_success([
            'id'      => $resultado,
            'message' => __('Encuesta creada correctamente', 'flavor-chat-ia'),
        ]);
    }

    /**
     * AJAX: Responder encuesta
     */
    public function ajax_encuestas_responder() {
        check_ajax_referer('flavor_encuestas_nonce', 'nonce');

        $encuesta_id = absint($_POST['encuesta_id'] ?? 0);
        $respuestas = $_POST['respuestas'] ?? [];

        if (!$encuesta_id || empty($respuestas)) {
            wp_send_json_error(['message' => __('Datos incompletos', 'flavor-chat-ia')]);
        }

        $resultado = $this->registrar_respuestas($encuesta_id, $respuestas);

        if (is_wp_error($resultado)) {
            wp_send_json_error(['message' => $resultado->get_error_message()]);
        }

        // Obtener resultados si corresponde
        $resultados = null;
        if ($this->puede_ver_resultados($encuesta_id)) {
            $resultados = $this->obtener_resultados($encuesta_id);
        }

        wp_send_json_success([
            'message'    => __('¡Gracias por tu respuesta!', 'flavor-chat-ia'),
            'resultados' => $resultados,
        ]);
    }

    /**
     * AJAX: Obtener resultados
     */
    public function ajax_encuestas_obtener_resultados() {
        $encuesta_id = absint($_GET['encuesta_id'] ?? $_POST['encuesta_id'] ?? 0);

        if (!$encuesta_id) {
            wp_send_json_error(['message' => __('ID de encuesta requerido', 'flavor-chat-ia')]);
        }

        if (!$this->puede_ver_resultados($encuesta_id)) {
            wp_send_json_error(['message' => __('No tienes permiso para ver los resultados', 'flavor-chat-ia')]);
        }

        $resultados = $this->obtener_resultados($encuesta_id);

        wp_send_json_success($resultados);
    }

    /**
     * AJAX: Cerrar encuesta
     */
    public function ajax_encuestas_cerrar() {
        check_ajax_referer('flavor_encuestas_nonce', 'nonce');

        $encuesta_id = absint($_POST['encuesta_id'] ?? 0);

        if (!$this->puede_editar_encuesta($encuesta_id)) {
            wp_send_json_error(['message' => __('No tienes permisos', 'flavor-chat-ia')]);
        }

        $resultado = $this->cerrar_encuesta($encuesta_id);

        if (is_wp_error($resultado)) {
            wp_send_json_error(['message' => $resultado->get_error_message()]);
        }

        wp_send_json_success(['message' => __('Encuesta cerrada', 'flavor-chat-ia')]);
    }

    /**
     * AJAX: Buscar entidades para vincular la encuesta por contexto.
     */
    public function ajax_encuestas_buscar_contexto() {
        check_ajax_referer('flavor_encuestas_nonce', 'nonce');

        if (!is_user_logged_in()) {
            wp_send_json_error(['message' => __('Debes iniciar sesión', 'flavor-chat-ia')], 401);
        }

        $contexto_tipo = sanitize_key((string) ($_POST['contexto_tipo'] ?? $_GET['contexto_tipo'] ?? ''));
        $query = sanitize_text_field(wp_unslash((string) ($_POST['q'] ?? $_GET['q'] ?? '')));
        $limit = max(3, min(12, absint($_POST['limit'] ?? $_GET['limit'] ?? 8)));

        if (!in_array($contexto_tipo, self::CONTEXTOS_VALIDOS, true) || $contexto_tipo === 'general') {
            wp_send_json_success(['items' => []]);
        }

        if (strlen($query) < 2) {
            wp_send_json_success(['items' => []]);
        }

        $items = $this->buscar_items_contexto($contexto_tipo, $query, $limit);
        wp_send_json_success(['items' => $items]);
    }

    /**
     * Busca entidades por tipo de contexto.
     *
     * @param string $contexto_tipo
     * @param string $query
     * @param int $limit
     * @return array<int, array<string, mixed>>
     */
    private function buscar_items_contexto($contexto_tipo, $query, $limit = 8) {
        global $wpdb;

        $like = '%' . $wpdb->esc_like($query) . '%';
        $items = [];
        $user_id = get_current_user_id();
        $is_admin = current_user_can('manage_options');

        switch ($contexto_tipo) {
            case 'chat_grupo':
                $tabla = $wpdb->prefix . 'flavor_chat_grupos';
                if ($this->tabla_existe_segura($tabla)) {
                    $cols = $this->obtener_columnas_tabla_segura($tabla);
                    $search_col = in_array('descripcion', $cols, true) ? 'descripcion' : 'nombre';
                    $where = ["(g.nombre LIKE %s OR g.{$search_col} LIKE %s)"];
                    $params = [$like, $like];

                    if (in_array('estado', $cols, true)) {
                        $where[] = "g.estado = 'activo'";
                    }

                    $join = '';
                    if (!$is_admin && $user_id > 0) {
                        $canales = [];
                        if (in_array('tipo', $cols, true)) {
                            $canales[] = "g.tipo = 'publico'";
                        }
                        if (in_array('creador_id', $cols, true)) {
                            $canales[] = 'g.creador_id = %d';
                            $params[] = $user_id;
                        }

                        $tabla_miembros = $wpdb->prefix . 'flavor_chat_grupos_miembros';
                        if ($this->tabla_existe_segura($tabla_miembros)) {
                            $join = " LEFT JOIN {$tabla_miembros} m ON m.grupo_id = g.id AND m.usuario_id = %d";
                            array_unshift($params, $user_id);
                            $canales[] = 'm.id IS NOT NULL';
                        }

                        if (!empty($canales)) {
                            $where[] = '(' . implode(' OR ', $canales) . ')';
                        }
                    }

                    $sql = "SELECT DISTINCT g.id, g.nombre, " . (in_array('descripcion', $cols, true) ? 'g.descripcion' : "'' AS descripcion") . ", " . (in_array('estado', $cols, true) ? 'g.estado' : "'' AS estado") . "
                        FROM {$tabla} g
                        {$join}
                        WHERE " . implode(' AND ', $where) . "
                        ORDER BY g.id DESC
                        LIMIT %d";
                    $params[] = $limit;

                    $rows = $wpdb->get_results($this->prepare_query($sql, $params));
                    $items = $this->mapear_items_desde_filas($rows, 'nombre', 'descripcion', __('Grupo de chat', 'flavor-chat-ia'));
                }
                break;

            case 'foro':
                $tabla = $wpdb->prefix . 'flavor_foros';
                if ($this->tabla_existe_segura($tabla)) {
                    $cols = $this->obtener_columnas_tabla_segura($tabla);
                    $where = ["(nombre LIKE %s OR " . (in_array('descripcion', $cols, true) ? 'descripcion' : 'nombre') . " LIKE %s)"];
                    $params = [$like, $like];

                    if (in_array('estado', $cols, true)) {
                        $where[] = "estado = 'activo'";
                    }
                    if (!$is_admin && in_array('solo_admins', $cols, true)) {
                        $where[] = 'solo_admins = 0';
                    }

                    $sql = "SELECT id, nombre, " . (in_array('descripcion', $cols, true) ? 'descripcion' : "'' AS descripcion") . ", " . (in_array('estado', $cols, true) ? 'estado' : "'' AS estado") . "
                        FROM {$tabla}
                        WHERE " . implode(' AND ', $where) . "
                        ORDER BY id DESC
                        LIMIT %d";
                    $params[] = $limit;

                    $rows = $wpdb->get_results($this->prepare_query($sql, $params));
                    $items = $this->mapear_items_desde_filas($rows, 'nombre', 'descripcion', __('Foro', 'flavor-chat-ia'));
                }
                break;

            case 'comunidad':
                $tabla = $wpdb->prefix . 'flavor_comunidades';
                if ($this->tabla_existe_segura($tabla)) {
                    $cols = $this->obtener_columnas_tabla_segura($tabla);
                    $join = '';
                    $where = ["(c.nombre LIKE %s OR " . (in_array('descripcion', $cols, true) ? 'c.descripcion' : 'c.nombre') . ' LIKE %s)'];
                    $params = [$like, $like];

                    if (in_array('estado', $cols, true)) {
                        $where[] = "c.estado = 'activa'";
                    }

                    if (!$is_admin && $user_id > 0) {
                        $canales = [];
                        if (in_array('tipo', $cols, true)) {
                            $canales[] = "c.tipo = 'abierta'";
                        }
                        if (in_array('creador_id', $cols, true)) {
                            $canales[] = 'c.creador_id = %d';
                            $params[] = $user_id;
                        }

                        $tabla_miembros = $wpdb->prefix . 'flavor_comunidades_miembros';
                        if ($this->tabla_existe_segura($tabla_miembros) && $this->columna_existe_segura($tabla_miembros, 'user_id')) {
                            $join = " LEFT JOIN {$tabla_miembros} m ON m.comunidad_id = c.id AND m.user_id = %d";
                            array_unshift($params, $user_id);
                            $canales[] = 'm.id IS NOT NULL';
                        }

                        if (!empty($canales)) {
                            $where[] = '(' . implode(' OR ', $canales) . ')';
                        }
                    }

                    $sql = "SELECT DISTINCT c.id, c.nombre, " . (in_array('descripcion', $cols, true) ? 'c.descripcion' : "'' AS descripcion") . ", " . (in_array('estado', $cols, true) ? 'c.estado' : "'' AS estado") . "
                        FROM {$tabla} c
                        {$join}
                        WHERE " . implode(' AND ', $where) . "
                        ORDER BY c.id DESC
                        LIMIT %d";
                    $params[] = $limit;

                    $rows = $wpdb->get_results($this->prepare_query($sql, $params));
                    $items = $this->mapear_items_desde_filas($rows, 'nombre', 'descripcion', __('Comunidad', 'flavor-chat-ia'));
                }
                break;

            case 'evento':
                $tabla = $wpdb->prefix . 'flavor_eventos';
                if ($this->tabla_existe_segura($tabla)) {
                    $cols = $this->obtener_columnas_tabla_segura($tabla);
                    $join = '';
                    $where = ["(e.titulo LIKE %s OR " . (in_array('descripcion', $cols, true) ? 'e.descripcion' : 'e.titulo') . ' LIKE %s)'];
                    $params = [$like, $like];

                    if (!$is_admin && $user_id > 0) {
                        $canales = [];
                        if (in_array('estado', $cols, true)) {
                            $canales[] = "e.estado = 'publicado'";
                        }
                        if (in_array('organizador_id', $cols, true)) {
                            $canales[] = 'e.organizador_id = %d';
                            $params[] = $user_id;
                        }

                        $tabla_ins = $wpdb->prefix . 'flavor_eventos_inscripciones';
                        if ($this->tabla_existe_segura($tabla_ins) && $this->columna_existe_segura($tabla_ins, 'user_id')) {
                            $join = " LEFT JOIN {$tabla_ins} i ON i.evento_id = e.id AND i.user_id = %d";
                            array_unshift($params, $user_id);
                            $canales[] = 'i.id IS NOT NULL';
                        }

                        if (!empty($canales)) {
                            $where[] = '(' . implode(' OR ', $canales) . ')';
                        }
                    }

                    $sql = "SELECT DISTINCT e.id, e.titulo, " . (in_array('descripcion', $cols, true) ? 'e.descripcion' : "'' AS descripcion") . ", " . (in_array('fecha_inicio', $cols, true) ? 'e.fecha_inicio' : 'NULL AS fecha_inicio') . ", " . (in_array('estado', $cols, true) ? 'e.estado' : "'' AS estado") . "
                         FROM {$tabla} e
                         {$join}
                         WHERE " . implode(' AND ', $where) . "
                         ORDER BY " . (in_array('fecha_inicio', $cols, true) ? 'e.fecha_inicio DESC, ' : '') . "e.id DESC
                         LIMIT %d";
                    $params[] = $limit;

                    $rows = $wpdb->get_results($this->prepare_query($sql, $params));

                    foreach ((array) $rows as $row) {
                        $subtitle = __('Evento', 'flavor-chat-ia');
                        if (!empty($row->fecha_inicio)) {
                            $subtitle .= ' • ' . mysql2date(get_option('date_format'), $row->fecha_inicio);
                        }
                        $items[] = [
                            'id'       => (int) $row->id,
                            'label'    => wp_strip_all_tags((string) $row->titulo),
                            'subtitle' => $subtitle,
                            'type_label' => __('Evento', 'flavor-chat-ia'),
                            'status_label' => !empty($row->estado) ? $this->formatear_estado_contexto($row->estado) : '',
                        ];
                    }
                }
                break;

            case 'curso':
                $tabla = $wpdb->prefix . 'flavor_cursos';
                if ($this->tabla_existe_segura($tabla)) {
                    $cols = $this->obtener_columnas_tabla_segura($tabla);
                    $join = '';
                    $where = ["(c.titulo LIKE %s OR " . (in_array('descripcion', $cols, true) ? 'c.descripcion' : 'c.titulo') . ' LIKE %s)'];
                    $params = [$like, $like];

                    if (!$is_admin && $user_id > 0) {
                        $canales = [];
                        if (in_array('estado', $cols, true)) {
                            $canales[] = "c.estado = 'publicado'";
                        }
                        if (in_array('instructor_id', $cols, true)) {
                            $canales[] = 'c.instructor_id = %d';
                            $params[] = $user_id;
                        }

                        $tabla_ins = $wpdb->prefix . 'flavor_cursos_inscripciones';
                        if ($this->tabla_existe_segura($tabla_ins) && $this->columna_existe_segura($tabla_ins, 'usuario_id')) {
                            $join = " LEFT JOIN {$tabla_ins} i ON i.curso_id = c.id AND i.usuario_id = %d";
                            array_unshift($params, $user_id);
                            $canales[] = 'i.id IS NOT NULL';
                        }

                        if (!empty($canales)) {
                            $where[] = '(' . implode(' OR ', $canales) . ')';
                        }
                    }

                    $sql = "SELECT DISTINCT c.id, c.titulo, " . (in_array('descripcion', $cols, true) ? 'c.descripcion' : "'' AS descripcion") . ", " . (in_array('estado', $cols, true) ? 'c.estado' : "'' AS estado") . "
                         FROM {$tabla} c
                         {$join}
                         WHERE " . implode(' AND ', $where) . "
                         ORDER BY c.id DESC
                         LIMIT %d";
                    $params[] = $limit;

                    $rows = $wpdb->get_results($this->prepare_query($sql, $params));
                    $items = $this->mapear_items_desde_filas($rows, 'titulo', 'descripcion', __('Curso', 'flavor-chat-ia'));
                }
                break;

            case 'red_social':
                $tabla = $wpdb->prefix . 'flavor_social_publicaciones';
                if ($this->tabla_existe_segura($tabla)) {
                    $cols = $this->obtener_columnas_tabla_segura($tabla);
                    $where = ["contenido LIKE %s"];
                    $params = [$like];

                    if (!$is_admin) {
                        if (in_array('estado', $cols, true)) {
                            $where[] = "estado = 'publicado'";
                        }
                        if ($user_id > 0 && in_array('visibilidad', $cols, true) && in_array('autor_id', $cols, true)) {
                            $where[] = "(visibilidad IN ('publica','comunidad') OR autor_id = %d)";
                            $params[] = $user_id;
                        } elseif (in_array('visibilidad', $cols, true)) {
                            $where[] = "visibilidad IN ('publica','comunidad')";
                        }
                    }

                    $sql = "SELECT id, contenido, " . (in_array('estado', $cols, true) ? 'estado' : "'' AS estado") . "
                         FROM {$tabla}
                         WHERE " . implode(' AND ', $where) . "
                         ORDER BY id DESC
                         LIMIT %d";
                    $params[] = $limit;

                    $rows = $wpdb->get_results($this->prepare_query($sql, $params));
                    foreach ((array) $rows as $row) {
                        $items[] = [
                            'id'       => (int) $row->id,
                            'label'    => sprintf(__('Publicación #%d', 'flavor-chat-ia'), (int) $row->id),
                            'subtitle' => wp_trim_words(wp_strip_all_tags((string) $row->contenido), 12, '…'),
                            'type_label' => __('Red social', 'flavor-chat-ia'),
                            'status_label' => !empty($row->estado) ? $this->formatear_estado_contexto($row->estado) : '',
                        ];
                    }
                }
                break;
        }

        return $items;
    }

    /**
     * Mapea filas DB a formato común de resultados de búsqueda.
     *
     * @param array<int, object> $rows
     * @param string $label_key
     * @param string $desc_key
     * @param string $type_label
     * @return array<int, array<string, mixed>>
     */
    private function mapear_items_desde_filas($rows, $label_key, $desc_key, $type_label) {
        $items = [];
        foreach ((array) $rows as $row) {
            $label = isset($row->{$label_key}) ? wp_strip_all_tags((string) $row->{$label_key}) : '';
            if ($label === '') {
                continue;
            }

            $subtitle = $type_label;
            $desc = isset($row->{$desc_key}) ? wp_strip_all_tags((string) $row->{$desc_key}) : '';
            if ($desc !== '') {
                $subtitle .= ' • ' . wp_trim_words($desc, 12, '…');
            }

            $items[] = [
                'id'       => isset($row->id) ? (int) $row->id : 0,
                'label'    => $label,
                'subtitle' => $subtitle,
                'type_label' => $type_label,
                'status_label' => isset($row->estado) ? $this->formatear_estado_contexto($row->estado) : '',
            ];
        }

        return $items;
    }

    /**
     * Formatea un estado técnico para mostrarlo en UI.
     *
     * @param string $estado
     * @return string
     */
    private function formatear_estado_contexto($estado) {
        $estado = trim((string) $estado);
        if ($estado === '') {
            return '';
        }
        return ucwords(str_replace('_', ' ', $estado));
    }

    /**
     * Comprueba existencia de tabla sin lanzar errores SQL al frontend.
     *
     * @param string $tabla Nombre completo de tabla.
     * @return bool
     */
    private function tabla_existe_segura($tabla) {
        global $wpdb;
        $found = $wpdb->get_var($wpdb->prepare('SHOW TABLES LIKE %s', $tabla));
        return $found === $tabla;
    }

    /**
     * Comprueba existencia de columna en una tabla.
     *
     * @param string $tabla
     * @param string $columna
     * @return bool
     */
    private function columna_existe_segura($tabla, $columna) {
        global $wpdb;
        $found = $wpdb->get_var($wpdb->prepare("SHOW COLUMNS FROM {$tabla} LIKE %s", $columna));
        return !empty($found);
    }

    /**
     * Devuelve listado de columnas disponibles para una tabla.
     *
     * @param string $tabla
     * @return string[]
     */
    private function obtener_columnas_tabla_segura($tabla) {
        global $wpdb;
        $result = $wpdb->get_col("SHOW COLUMNS FROM {$tabla}", 0);
        if (empty($result) || !is_array($result)) {
            return [];
        }
        return array_map('strval', $result);
    }

    /**
     * Prepara una query con parámetros variables.
     *
     * @param string $sql
     * @param array<int, mixed> $params
     * @return string
     */
    private function prepare_query($sql, array $params) {
        global $wpdb;
        $prepared = call_user_func_array([$wpdb, 'prepare'], array_merge([$sql], $params));
        return is_string($prepared) ? $prepared : $sql;
    }

    // =========================================================================
    // UTILIDADES
    // =========================================================================

    /**
     * Verifica si el usuario puede editar una encuesta
     */
    public function puede_editar_encuesta($encuesta_id) {
        $encuesta = $this->obtener_encuesta($encuesta_id, false);
        if (!$encuesta) {
            return false;
        }

        $usuario_id = get_current_user_id();

        // Autor puede editar
        if ($usuario_id && $encuesta->autor_id == $usuario_id) {
            return true;
        }

        // Admin puede editar
        if (current_user_can('manage_options')) {
            return true;
        }

        return false;
    }

    /**
     * Obtiene o genera ID de sesión para tracking anónimo
     */
    private function obtener_sesion_id() {
        if (!isset($_COOKIE['flavor_encuestas_sid'])) {
            $session_id = wp_generate_uuid4();
            setcookie('flavor_encuestas_sid', $session_id, time() + (86400 * 30), '/');
            return $session_id;
        }
        return sanitize_text_field($_COOKIE['flavor_encuestas_sid']);
    }

    /**
     * Obtiene IP del usuario
     */
    private function obtener_ip_usuario() {
        $ip = '';
        if (!empty($_SERVER['HTTP_CLIENT_IP'])) {
            $ip = $_SERVER['HTTP_CLIENT_IP'];
        } elseif (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
            $ip = explode(',', $_SERVER['HTTP_X_FORWARDED_FOR'])[0];
        } elseif (!empty($_SERVER['REMOTE_ADDR'])) {
            $ip = $_SERVER['REMOTE_ADDR'];
        }
        return filter_var($ip, FILTER_VALIDATE_IP) ?: '';
    }

    /**
     * Cron: Cierra encuestas expiradas
     */
    public function cerrar_encuestas_expiradas() {
        global $wpdb;

        $tabla = $wpdb->prefix . 'flavor_encuestas';

        $wpdb->query($wpdb->prepare(
            "UPDATE {$tabla}
             SET estado = 'cerrada'
             WHERE estado = 'activa'
             AND fecha_cierre IS NOT NULL
             AND fecha_cierre < %s",
            current_time('mysql')
        ));
    }

    /**
     * Crea tablas si no existen
     */
    public function maybe_create_tables() {
        if ($this->can_activate()) {
            return;
        }

        require_once ABSPATH . 'wp-admin/includes/upgrade.php';
        Flavor_Database_Installer::install_tables();
    }

    // =========================================================================
    // ADMINISTRACIÓN - PANEL UNIFICADO
    // =========================================================================

    /**
     * Configuración para el panel de administración unificado
     *
     * @return array
     */
    protected function get_admin_config() {
        return [
            'id'         => $this->id,
            'label'      => $this->name,
            'icon'       => 'dashicons-forms',
            'capability' => 'manage_options',
            'categoria'  => 'comunicacion',
            'paginas'    => [
                [
                    'slug'     => 'encuestas-dashboard',
                    'titulo'   => __('Dashboard', 'flavor-chat-ia'),
                    'callback' => [$this, 'render_admin_dashboard'],
                ],
                [
                    'slug'     => 'encuestas-listado',
                    'titulo'   => __('Encuestas', 'flavor-chat-ia'),
                    'callback' => [$this, 'render_admin_listado'],
                    'badge'    => [$this, 'contar_encuestas_activas'],
                ],
                [
                    'slug'     => 'encuestas-crear',
                    'titulo'   => __('Crear Encuesta', 'flavor-chat-ia'),
                    'callback' => [$this, 'render_admin_crear'],
                ],
                [
                    'slug'     => 'encuestas-resultados',
                    'titulo'   => __('Resultados', 'flavor-chat-ia'),
                    'callback' => [$this, 'render_admin_resultados'],
                ],
                [
                    'slug'     => 'encuestas-config',
                    'titulo'   => __('Configuración', 'flavor-chat-ia'),
                    'callback' => [$this, 'render_admin_config'],
                ],
            ],
            'dashboard_widget' => [$this, 'render_widget_resumen'],
            'estadisticas'     => [$this, 'get_estadisticas_dashboard'],
        ];
    }

    /**
     * Registra las páginas de admin ocultas (accesibles desde panel unificado)
     */
    public function registrar_paginas_admin() {
        static $registered = false;
        if ($registered) {
            return;
        }
        $registered = true;


        $capability = 'manage_options';

        // Páginas ocultas (sin menú visible en el sidebar de WordPress)
        add_submenu_page(
            null,
            __('Dashboard Encuestas', 'flavor-chat-ia'),
            __('Dashboard', 'flavor-chat-ia'),
            $capability,
            'encuestas-dashboard',
            [$this, 'render_admin_dashboard']
        );

        add_submenu_page(
            null,
            __('Listado de Encuestas', 'flavor-chat-ia'),
            __('Encuestas', 'flavor-chat-ia'),
            $capability,
            'encuestas-listado',
            [$this, 'render_admin_listado']
        );

        add_submenu_page(
            null,
            __('Crear Encuesta', 'flavor-chat-ia'),
            __('Crear', 'flavor-chat-ia'),
            $capability,
            'encuestas-crear',
            [$this, 'render_admin_crear']
        );

        add_submenu_page(
            null,
            __('Resultados de Encuestas', 'flavor-chat-ia'),
            __('Resultados', 'flavor-chat-ia'),
            $capability,
            'encuestas-resultados',
            [$this, 'render_admin_resultados']
        );

        add_submenu_page(
            null,
            __('Configuración de Encuestas', 'flavor-chat-ia'),
            __('Configuración', 'flavor-chat-ia'),
            $capability,
            'encuestas-config',
            [$this, 'render_admin_config']
        );
    }

    /**
     * Cuenta encuestas activas para badge
     *
     * @return int
     */
    public function contar_encuestas_activas() {
        if (!$this->can_activate()) {
            return 0;
        }

        global $wpdb;
        $tabla_encuestas = $wpdb->prefix . 'flavor_encuestas';

        if (!Flavor_Chat_Helpers::tabla_existe($tabla_encuestas)) {
            return 0;
        }

        return (int) $wpdb->get_var(
            "SELECT COUNT(*) FROM $tabla_encuestas WHERE estado = 'activa'"
        );
    }

    /**
     * Obtiene estadísticas para el dashboard
     *
     * @return array
     */
    public function get_estadisticas_dashboard() {
        global $wpdb;
        $prefix = $wpdb->prefix . 'flavor_';

        $tabla_encuestas = $prefix . 'encuestas';

        if (!Flavor_Chat_Helpers::tabla_existe($tabla_encuestas)) {
            return [];
        }

        $total_encuestas = (int) $wpdb->get_var("SELECT COUNT(*) FROM $tabla_encuestas");
        $encuestas_activas = (int) $wpdb->get_var("SELECT COUNT(*) FROM $tabla_encuestas WHERE estado = 'activa'");
        $total_respuestas = (int) $wpdb->get_var("SELECT SUM(total_respuestas) FROM $tabla_encuestas");
        $total_participantes = (int) $wpdb->get_var("SELECT SUM(total_participantes) FROM $tabla_encuestas");

        return [
            [
                'icon'  => 'dashicons-forms',
                'valor' => $total_encuestas,
                'label' => __('Total Encuestas', 'flavor-chat-ia'),
                'color' => 'purple',
            ],
            [
                'icon'  => 'dashicons-yes-alt',
                'valor' => $encuestas_activas,
                'label' => __('Activas', 'flavor-chat-ia'),
                'color' => 'green',
            ],
            [
                'icon'  => 'dashicons-chart-bar',
                'valor' => $total_respuestas,
                'label' => __('Respuestas', 'flavor-chat-ia'),
                'color' => 'blue',
            ],
            [
                'icon'  => 'dashicons-groups',
                'valor' => $total_participantes,
                'label' => __('Participantes', 'flavor-chat-ia'),
                'color' => 'orange',
            ],
        ];
    }

    /**
     * Widget de resumen para el dashboard unificado
     */
    public function render_widget_resumen() {
        global $wpdb;
        $tabla_encuestas = $wpdb->prefix . 'flavor_encuestas';

        if (!Flavor_Chat_Helpers::tabla_existe($tabla_encuestas)) {
            echo '<p>' . __('Módulo no inicializado.', 'flavor-chat-ia') . '</p>';
            return;
        }

        $encuestas_activas = (int) $wpdb->get_var("SELECT COUNT(*) FROM $tabla_encuestas WHERE estado = 'activa'");
        $ultima_encuesta = $wpdb->get_row("SELECT titulo, total_participantes FROM $tabla_encuestas ORDER BY fecha_creacion DESC LIMIT 1");

        echo '<div class="flavor-encuestas-widget">';
        echo '<p><strong>' . $encuestas_activas . '</strong> ' . __('encuestas activas', 'flavor-chat-ia') . '</p>';

        if ($ultima_encuesta) {
            echo '<p class="description">';
            echo __('Última:', 'flavor-chat-ia') . ' ' . esc_html($ultima_encuesta->titulo);
            echo ' (' . $ultima_encuesta->total_participantes . ' ' . __('participantes', 'flavor-chat-ia') . ')';
            echo '</p>';
        }

        echo '</div>';
    }

    // =========================================================================
    // PÁGINAS DE ADMINISTRACIÓN - RENDER
    // =========================================================================

    /**
     * Renderiza el dashboard de administración
     */
    public function render_admin_dashboard() {
        global $wpdb;
        $prefix = $wpdb->prefix . 'flavor_';

        $tabla_encuestas = $prefix . 'encuestas';

        // Verificar si la tabla existe
        if (!Flavor_Chat_Helpers::tabla_existe($tabla_encuestas)) {
            $this->render_page_header(__('Dashboard de Encuestas', 'flavor-chat-ia'));
            echo '<div class="notice notice-warning"><p>' . __('Las tablas del módulo no están creadas. Por favor, desactiva y reactiva el módulo.', 'flavor-chat-ia') . '</p></div>';
            return;
        }

        // Obtener estadísticas
        $total_encuestas = (int) $wpdb->get_var("SELECT COUNT(*) FROM $tabla_encuestas");
        $encuestas_activas = (int) $wpdb->get_var("SELECT COUNT(*) FROM $tabla_encuestas WHERE estado = 'activa'");
        $encuestas_borradores = (int) $wpdb->get_var("SELECT COUNT(*) FROM $tabla_encuestas WHERE estado = 'borrador'");
        $encuestas_cerradas = (int) $wpdb->get_var("SELECT COUNT(*) FROM $tabla_encuestas WHERE estado = 'cerrada'");
        $total_respuestas = (int) $wpdb->get_var("SELECT COALESCE(SUM(total_respuestas), 0) FROM $tabla_encuestas");
        $total_participantes = (int) $wpdb->get_var("SELECT COALESCE(SUM(total_participantes), 0) FROM $tabla_encuestas");

        // Calcular tasa de participación
        $tasa_participacion = 0;
        if ($total_encuestas > 0 && $encuestas_activas > 0) {
            $promedio_participantes = $total_participantes / $total_encuestas;
            $tasa_participacion = round($promedio_participantes, 1);
        }

        // Encuestas recientes
        $encuestas_recientes = $wpdb->get_results(
            "SELECT id, titulo, estado, tipo, total_participantes, total_respuestas, fecha_creacion
             FROM $tabla_encuestas
             ORDER BY fecha_creacion DESC
             LIMIT 5"
        );

        // Encuestas más populares
        $encuestas_populares = $wpdb->get_results(
            "SELECT id, titulo, estado, total_participantes, total_respuestas
             FROM $tabla_encuestas
             WHERE total_participantes > 0
             ORDER BY total_participantes DESC
             LIMIT 5"
        );

        $this->render_page_header(
            __('Dashboard de Encuestas', 'flavor-chat-ia'),
            [
                [
                    'label' => __('Nueva Encuesta', 'flavor-chat-ia'),
                    'url'   => admin_url('admin.php?page=encuestas-crear'),
                    'class' => 'button-primary',
                ],
                [
                    'label' => __('Ver Todas', 'flavor-chat-ia'),
                    'url'   => admin_url('admin.php?page=encuestas-listado'),
                    'class' => '',
                ],
            ]
        );
        ?>
        <div class="wrap flavor-encuestas-dashboard">
            <style>
                .flavor-encuestas-dashboard { max-width: 1400px; }
                .flavor-encuestas-stats { display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 20px; margin-bottom: 30px; }
                .flavor-stat-card { background: #fff; border: 1px solid #e0e0e0; border-radius: 8px; padding: 20px; display: flex; align-items: center; gap: 15px; transition: box-shadow 0.2s; }
                .flavor-stat-card:hover { box-shadow: 0 2px 8px rgba(0,0,0,0.1); }
                .flavor-stat-card .dashicons { font-size: 40px; width: 40px; height: 40px; }
                .flavor-stat-card.purple .dashicons { color: #8b5cf6; }
                .flavor-stat-card.green .dashicons { color: #22c55e; }
                .flavor-stat-card.blue .dashicons { color: #3b82f6; }
                .flavor-stat-card.orange .dashicons { color: #f97316; }
                .flavor-stat-card.gray .dashicons { color: #6b7280; }
                .flavor-stat-content .stat-value { display: block; font-size: 28px; font-weight: 600; line-height: 1.2; }
                .flavor-stat-content .stat-label { color: #6b7280; font-size: 13px; }
                .flavor-dashboard-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 20px; }
                .flavor-dashboard-card { background: #fff; border: 1px solid #e0e0e0; border-radius: 8px; padding: 20px; }
                .flavor-dashboard-card h3 { margin: 0 0 15px 0; padding-bottom: 10px; border-bottom: 1px solid #eee; display: flex; align-items: center; gap: 8px; }
                .flavor-dashboard-card h3 .dashicons { color: #8b5cf6; }
                .flavor-encuesta-list { list-style: none; margin: 0; padding: 0; }
                .flavor-encuesta-list li { padding: 12px 0; border-bottom: 1px solid #f0f0f0; display: flex; justify-content: space-between; align-items: center; }
                .flavor-encuesta-list li:last-child { border-bottom: none; }
                .flavor-encuesta-titulo { font-weight: 500; color: #1d2327; }
                .flavor-encuesta-meta { color: #6b7280; font-size: 12px; }
                .flavor-estado { display: inline-block; padding: 2px 8px; border-radius: 10px; font-size: 11px; font-weight: 500; }
                .flavor-estado-activa { background: #dcfce7; color: #166534; }
                .flavor-estado-borrador { background: #f3f4f6; color: #4b5563; }
                .flavor-estado-cerrada { background: #fee2e2; color: #991b1b; }
                .flavor-estado-archivada { background: #e5e7eb; color: #6b7280; }
                @media (max-width: 782px) { .flavor-dashboard-grid { grid-template-columns: 1fr; } }
            </style>

            <!-- Tarjetas de estadísticas -->
            <div class="flavor-encuestas-stats">
                <div class="flavor-stat-card purple">
                    <span class="dashicons dashicons-forms"></span>
                    <div class="flavor-stat-content">
                        <span class="stat-value"><?php echo esc_html($total_encuestas); ?></span>
                        <span class="stat-label"><?php _e('Total Encuestas', 'flavor-chat-ia'); ?></span>
                    </div>
                </div>
                <div class="flavor-stat-card green">
                    <span class="dashicons dashicons-yes-alt"></span>
                    <div class="flavor-stat-content">
                        <span class="stat-value"><?php echo esc_html($encuestas_activas); ?></span>
                        <span class="stat-label"><?php _e('Activas', 'flavor-chat-ia'); ?></span>
                    </div>
                </div>
                <div class="flavor-stat-card blue">
                    <span class="dashicons dashicons-chart-bar"></span>
                    <div class="flavor-stat-content">
                        <span class="stat-value"><?php echo esc_html($total_respuestas); ?></span>
                        <span class="stat-label"><?php _e('Respuestas Totales', 'flavor-chat-ia'); ?></span>
                    </div>
                </div>
                <div class="flavor-stat-card orange">
                    <span class="dashicons dashicons-groups"></span>
                    <div class="flavor-stat-content">
                        <span class="stat-value"><?php echo esc_html($total_participantes); ?></span>
                        <span class="stat-label"><?php _e('Participantes', 'flavor-chat-ia'); ?></span>
                    </div>
                </div>
            </div>

            <!-- Grid de contenido -->
            <div class="flavor-dashboard-grid">
                <!-- Encuestas recientes -->
                <div class="flavor-dashboard-card">
                    <h3>
                        <span class="dashicons dashicons-clock"></span>
                        <?php _e('Encuestas Recientes', 'flavor-chat-ia'); ?>
                    </h3>
                    <?php if (empty($encuestas_recientes)): ?>
                        <p class="description"><?php _e('No hay encuestas creadas aún.', 'flavor-chat-ia'); ?></p>
                    <?php else: ?>
                        <ul class="flavor-encuesta-list">
                            <?php foreach ($encuestas_recientes as $encuesta): ?>
                                <li>
                                    <div>
                                        <a href="<?php echo admin_url('admin.php?page=encuestas-resultados&id=' . $encuesta->id); ?>" class="flavor-encuesta-titulo">
                                            <?php echo esc_html($encuesta->titulo); ?>
                                        </a>
                                        <div class="flavor-encuesta-meta">
                                            <?php echo esc_html(ucfirst($encuesta->tipo)); ?> &bull;
                                            <?php echo esc_html(human_time_diff(strtotime($encuesta->fecha_creacion), current_time('timestamp'))); ?> <?php _e('atrás', 'flavor-chat-ia'); ?>
                                        </div>
                                    </div>
                                    <span class="flavor-estado flavor-estado-<?php echo esc_attr($encuesta->estado); ?>">
                                        <?php echo esc_html(ucfirst($encuesta->estado)); ?>
                                    </span>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                    <?php endif; ?>
                </div>

                <!-- Encuestas más populares -->
                <div class="flavor-dashboard-card">
                    <h3>
                        <span class="dashicons dashicons-star-filled"></span>
                        <?php _e('Más Populares', 'flavor-chat-ia'); ?>
                    </h3>
                    <?php if (empty($encuestas_populares)): ?>
                        <p class="description"><?php _e('Aún no hay encuestas con participantes.', 'flavor-chat-ia'); ?></p>
                    <?php else: ?>
                        <ul class="flavor-encuesta-list">
                            <?php foreach ($encuestas_populares as $encuesta): ?>
                                <li>
                                    <div>
                                        <a href="<?php echo admin_url('admin.php?page=encuestas-resultados&id=' . $encuesta->id); ?>" class="flavor-encuesta-titulo">
                                            <?php echo esc_html($encuesta->titulo); ?>
                                        </a>
                                        <div class="flavor-encuesta-meta">
                                            <?php echo $encuesta->total_participantes; ?> <?php _e('participantes', 'flavor-chat-ia'); ?> &bull;
                                            <?php echo $encuesta->total_respuestas; ?> <?php _e('respuestas', 'flavor-chat-ia'); ?>
                                        </div>
                                    </div>
                                    <span class="flavor-estado flavor-estado-<?php echo esc_attr($encuesta->estado); ?>">
                                        <?php echo esc_html(ucfirst($encuesta->estado)); ?>
                                    </span>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Resumen por estado -->
            <div class="flavor-dashboard-card" style="margin-top: 20px;">
                <h3>
                    <span class="dashicons dashicons-chart-pie"></span>
                    <?php _e('Distribución por Estado', 'flavor-chat-ia'); ?>
                </h3>
                <div style="display: flex; gap: 30px; flex-wrap: wrap;">
                    <div>
                        <span class="flavor-estado flavor-estado-activa" style="font-size: 14px;"><?php echo $encuestas_activas; ?> <?php _e('Activas', 'flavor-chat-ia'); ?></span>
                    </div>
                    <div>
                        <span class="flavor-estado flavor-estado-borrador" style="font-size: 14px;"><?php echo $encuestas_borradores; ?> <?php _e('Borradores', 'flavor-chat-ia'); ?></span>
                    </div>
                    <div>
                        <span class="flavor-estado flavor-estado-cerrada" style="font-size: 14px;"><?php echo $encuestas_cerradas; ?> <?php _e('Cerradas', 'flavor-chat-ia'); ?></span>
                    </div>
                </div>
            </div>
        </div>
        <?php
    }

    /**
     * Renderiza el listado de encuestas
     */
    public function render_admin_listado() {
        global $wpdb;
        $tabla_encuestas = $wpdb->prefix . 'flavor_encuestas';

        // Manejar acciones
        $action = isset($_GET['action']) ? sanitize_text_field($_GET['action']) : '';
        $encuesta_id = isset($_GET['id']) ? absint($_GET['id']) : 0;

        // Eliminar encuesta
        if ($action === 'eliminar' && $encuesta_id && wp_verify_nonce($_GET['_wpnonce'] ?? '', 'eliminar_encuesta_' . $encuesta_id)) {
            $this->eliminar_encuesta($encuesta_id);
            echo '<div class="notice notice-success"><p>' . __('Encuesta eliminada correctamente.', 'flavor-chat-ia') . '</p></div>';
        }

        // Cambiar estado
        if ($action === 'activar' && $encuesta_id && wp_verify_nonce($_GET['_wpnonce'] ?? '', 'activar_encuesta_' . $encuesta_id)) {
            $this->actualizar_encuesta($encuesta_id, ['estado' => 'activa']);
            echo '<div class="notice notice-success"><p>' . __('Encuesta activada.', 'flavor-chat-ia') . '</p></div>';
        }

        if ($action === 'cerrar' && $encuesta_id && wp_verify_nonce($_GET['_wpnonce'] ?? '', 'cerrar_encuesta_' . $encuesta_id)) {
            $this->cerrar_encuesta($encuesta_id);
            echo '<div class="notice notice-success"><p>' . __('Encuesta cerrada.', 'flavor-chat-ia') . '</p></div>';
        }

        // Filtros
        $tab_actual = isset($_GET['tab']) ? sanitize_text_field($_GET['tab']) : 'todas';
        $busqueda = isset($_GET['s']) ? sanitize_text_field($_GET['s']) : '';

        $this->render_page_header(
            __('Gestión de Encuestas', 'flavor-chat-ia'),
            [
                [
                    'label' => __('Nueva Encuesta', 'flavor-chat-ia'),
                    'url'   => admin_url('admin.php?page=encuestas-crear'),
                    'class' => 'button-primary',
                ],
            ]
        );

        // Tabs de filtro
        $this->render_page_tabs([
            ['slug' => 'todas', 'label' => __('Todas', 'flavor-chat-ia')],
            ['slug' => 'activas', 'label' => __('Activas', 'flavor-chat-ia'), 'badge' => $this->contar_encuestas_activas()],
            ['slug' => 'borradores', 'label' => __('Borradores', 'flavor-chat-ia')],
            ['slug' => 'cerradas', 'label' => __('Cerradas', 'flavor-chat-ia')],
        ], $tab_actual);

        // Construir query
        $where_clauses = [];
        $values = [];

        switch ($tab_actual) {
            case 'activas':
                $where_clauses[] = "estado = 'activa'";
                break;
            case 'borradores':
                $where_clauses[] = "estado = 'borrador'";
                break;
            case 'cerradas':
                $where_clauses[] = "estado IN ('cerrada', 'archivada')";
                break;
        }

        if (!empty($busqueda)) {
            $where_clauses[] = "titulo LIKE %s";
            $values[] = '%' . $wpdb->esc_like($busqueda) . '%';
        }

        $where_sql = !empty($where_clauses) ? 'WHERE ' . implode(' AND ', $where_clauses) : '';

        if (!empty($values)) {
            $sql = $wpdb->prepare(
                "SELECT * FROM $tabla_encuestas $where_sql ORDER BY fecha_creacion DESC LIMIT 50",
                $values
            );
        } else {
            $sql = "SELECT * FROM $tabla_encuestas $where_sql ORDER BY fecha_creacion DESC LIMIT 50";
        }

        $encuestas = $wpdb->get_results($sql);
        ?>
        <div class="wrap">
            <!-- Búsqueda -->
            <form method="get" style="margin: 15px 0;">
                <input type="hidden" name="page" value="encuestas-listado">
                <input type="hidden" name="tab" value="<?php echo esc_attr($tab_actual); ?>">
                <p class="search-box">
                    <input type="search" name="s" value="<?php echo esc_attr($busqueda); ?>" placeholder="<?php _e('Buscar encuestas...', 'flavor-chat-ia'); ?>">
                    <input type="submit" class="button" value="<?php _e('Buscar', 'flavor-chat-ia'); ?>">
                </p>
            </form>

            <table class="wp-list-table widefat fixed striped">
                <thead>
                    <tr>
                        <th style="width: 50px;"><?php _e('ID', 'flavor-chat-ia'); ?></th>
                        <th><?php _e('Título', 'flavor-chat-ia'); ?></th>
                        <th style="width: 100px;"><?php _e('Tipo', 'flavor-chat-ia'); ?></th>
                        <th style="width: 100px;"><?php _e('Estado', 'flavor-chat-ia'); ?></th>
                        <th style="width: 120px;"><?php _e('Respuestas', 'flavor-chat-ia'); ?></th>
                        <th style="width: 120px;"><?php _e('Participantes', 'flavor-chat-ia'); ?></th>
                        <th style="width: 150px;"><?php _e('Fecha', 'flavor-chat-ia'); ?></th>
                        <th style="width: 180px;"><?php _e('Acciones', 'flavor-chat-ia'); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($encuestas)): ?>
                        <tr>
                            <td colspan="8"><?php _e('No hay encuestas que mostrar.', 'flavor-chat-ia'); ?></td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($encuestas as $encuesta): ?>
                            <tr>
                                <td><?php echo esc_html($encuesta->id); ?></td>
                                <td>
                                    <strong>
                                        <a href="<?php echo admin_url('admin.php?page=encuestas-resultados&id=' . $encuesta->id); ?>">
                                            <?php echo esc_html($encuesta->titulo); ?>
                                        </a>
                                    </strong>
                                    <?php if (!empty($encuesta->descripcion)): ?>
                                        <p class="description"><?php echo esc_html(wp_trim_words($encuesta->descripcion, 10)); ?></p>
                                    <?php endif; ?>
                                </td>
                                <td><?php echo esc_html(ucfirst($encuesta->tipo)); ?></td>
                                <td>
                                    <span class="flavor-estado flavor-estado-<?php echo esc_attr($encuesta->estado); ?>">
                                        <?php echo esc_html(ucfirst($encuesta->estado)); ?>
                                    </span>
                                </td>
                                <td><?php echo esc_html($encuesta->total_respuestas); ?></td>
                                <td><?php echo esc_html($encuesta->total_participantes); ?></td>
                                <td>
                                    <?php echo esc_html(date_i18n(get_option('date_format'), strtotime($encuesta->fecha_creacion))); ?>
                                </td>
                                <td>
                                    <a href="<?php echo admin_url('admin.php?page=encuestas-resultados&id=' . $encuesta->id); ?>" class="button button-small" title="<?php _e('Ver resultados', 'flavor-chat-ia'); ?>">
                                        <span class="dashicons dashicons-chart-bar" style="vertical-align: middle;"></span>
                                    </a>
                                    <a href="<?php echo admin_url('admin.php?page=encuestas-crear&id=' . $encuesta->id); ?>" class="button button-small" title="<?php _e('Editar', 'flavor-chat-ia'); ?>">
                                        <span class="dashicons dashicons-edit" style="vertical-align: middle;"></span>
                                    </a>
                                    <?php if ($encuesta->estado === 'borrador'): ?>
                                        <a href="<?php echo wp_nonce_url(admin_url('admin.php?page=encuestas-listado&action=activar&id=' . $encuesta->id), 'activar_encuesta_' . $encuesta->id); ?>" class="button button-small button-primary" title="<?php _e('Activar', 'flavor-chat-ia'); ?>">
                                            <span class="dashicons dashicons-yes" style="vertical-align: middle;"></span>
                                        </a>
                                    <?php elseif ($encuesta->estado === 'activa'): ?>
                                        <a href="<?php echo wp_nonce_url(admin_url('admin.php?page=encuestas-listado&action=cerrar&id=' . $encuesta->id), 'cerrar_encuesta_' . $encuesta->id); ?>" class="button button-small" title="<?php _e('Cerrar', 'flavor-chat-ia'); ?>">
                                            <span class="dashicons dashicons-lock" style="vertical-align: middle;"></span>
                                        </a>
                                    <?php endif; ?>
                                    <a href="<?php echo wp_nonce_url(admin_url('admin.php?page=encuestas-listado&action=eliminar&id=' . $encuesta->id), 'eliminar_encuesta_' . $encuesta->id); ?>" class="button button-small" onclick="return confirm('<?php _e('¿Estás seguro de eliminar esta encuesta?', 'flavor-chat-ia'); ?>');" title="<?php _e('Eliminar', 'flavor-chat-ia'); ?>">
                                        <span class="dashicons dashicons-trash" style="vertical-align: middle;"></span>
                                    </a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
        <style>
            .flavor-estado { display: inline-block; padding: 2px 8px; border-radius: 10px; font-size: 11px; font-weight: 500; }
            .flavor-estado-activa { background: #dcfce7; color: #166534; }
            .flavor-estado-borrador { background: #f3f4f6; color: #4b5563; }
            .flavor-estado-cerrada { background: #fee2e2; color: #991b1b; }
            .flavor-estado-archivada { background: #e5e7eb; color: #6b7280; }
        </style>
        <?php
    }

    /**
     * Renderiza el formulario de creación/edición de encuestas
     */
    public function render_admin_crear() {
        global $wpdb;
        $tabla_encuestas = $wpdb->prefix . 'flavor_encuestas';
        $tabla_campos = $wpdb->prefix . 'flavor_encuestas_campos';

        $encuesta_id = isset($_GET['id']) ? absint($_GET['id']) : 0;
        $encuesta = null;
        $campos = [];

        // Cargar encuesta existente si estamos editando
        if ($encuesta_id) {
            $encuesta = $this->obtener_encuesta($encuesta_id, true);
            if ($encuesta) {
                $campos = $encuesta->campos ?? [];
            }
        }

        // Procesar formulario
        if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['encuesta_nonce']) && wp_verify_nonce($_POST['encuesta_nonce'], 'guardar_encuesta')) {
            $datos = [
                'titulo'            => sanitize_text_field($_POST['titulo'] ?? ''),
                'descripcion'       => wp_kses_post($_POST['descripcion'] ?? ''),
                'tipo'              => sanitize_text_field($_POST['tipo'] ?? 'encuesta'),
                'estado'            => sanitize_text_field($_POST['estado'] ?? 'borrador'),
                'es_anonima'        => isset($_POST['es_anonima']) ? 1 : 0,
                'permite_multiples' => isset($_POST['permite_multiples']) ? 1 : 0,
                'mostrar_resultados'=> sanitize_text_field($_POST['mostrar_resultados'] ?? 'al_votar'),
                'fecha_cierre'      => !empty($_POST['fecha_cierre']) ? sanitize_text_field($_POST['fecha_cierre']) : null,
            ];

            // Procesar campos/preguntas
            $campos_data = [];
            if (!empty($_POST['preguntas']) && is_array($_POST['preguntas'])) {
                foreach ($_POST['preguntas'] as $indice => $pregunta) {
                    $opciones = [];
                    if (!empty($pregunta['opciones']) && is_array($pregunta['opciones'])) {
                        $opciones = array_map('sanitize_text_field', array_filter($pregunta['opciones']));
                    }

                    $campos_data[] = [
                        'tipo'        => sanitize_text_field($pregunta['tipo'] ?? 'seleccion_unica'),
                        'etiqueta'    => sanitize_text_field($pregunta['etiqueta'] ?? ''),
                        'descripcion' => sanitize_textarea_field($pregunta['descripcion'] ?? ''),
                        'opciones'    => $opciones,
                        'es_requerido'=> isset($pregunta['es_requerido']) ? 1 : 0,
                        'orden'       => $indice,
                    ];
                }
            }

            $datos['campos'] = $campos_data;

            if ($encuesta_id) {
                // Actualizar encuesta existente
                $resultado = $this->actualizar_encuesta($encuesta_id, $datos);

                // Actualizar campos: eliminar existentes y crear nuevos
                $wpdb->delete($tabla_campos, ['encuesta_id' => $encuesta_id]);
                foreach ($datos['campos'] as $campo) {
                    $campo['encuesta_id'] = $encuesta_id;
                    $this->crear_campo($campo);
                }

                if (!is_wp_error($resultado)) {
                    echo '<div class="notice notice-success"><p>' . __('Encuesta actualizada correctamente.', 'flavor-chat-ia') . '</p></div>';
                    $encuesta = $this->obtener_encuesta($encuesta_id, true);
                    $campos = $encuesta->campos ?? [];
                }
            } else {
                // Crear nueva encuesta
                $resultado = $this->crear_encuesta($datos);
                if (!is_wp_error($resultado)) {
                    echo '<div class="notice notice-success"><p>' . __('Encuesta creada correctamente.', 'flavor-chat-ia') . ' <a href="' . admin_url('admin.php?page=encuestas-listado') . '">' . __('Ver listado', 'flavor-chat-ia') . '</a></p></div>';
                    // Redirigir a edición
                    wp_redirect(admin_url('admin.php?page=encuestas-crear&id=' . $resultado));
                    exit;
                } else {
                    echo '<div class="notice notice-error"><p>' . $resultado->get_error_message() . '</p></div>';
                }
            }
        }

        $titulo_pagina = $encuesta_id ? __('Editar Encuesta', 'flavor-chat-ia') : __('Nueva Encuesta', 'flavor-chat-ia');
        $this->render_page_header($titulo_pagina);
        ?>
        <div class="wrap">
            <style>
                .flavor-encuesta-form { max-width: 900px; background: #fff; padding: 25px; border: 1px solid #e0e0e0; border-radius: 8px; }
                .flavor-form-section { margin-bottom: 25px; padding-bottom: 20px; border-bottom: 1px solid #eee; }
                .flavor-form-section:last-child { border-bottom: none; margin-bottom: 0; }
                .flavor-form-section h3 { margin: 0 0 15px 0; color: #1d2327; display: flex; align-items: center; gap: 8px; }
                .flavor-form-section h3 .dashicons { color: #8b5cf6; }
                .flavor-form-row { margin-bottom: 15px; }
                .flavor-form-row label { display: block; font-weight: 500; margin-bottom: 5px; }
                .flavor-form-row input[type="text"],
                .flavor-form-row input[type="datetime-local"],
                .flavor-form-row textarea,
                .flavor-form-row select { width: 100%; max-width: 500px; }
                .flavor-form-row textarea { min-height: 80px; }
                .flavor-checkbox-row { display: flex; align-items: center; gap: 8px; }
                .flavor-checkbox-row label { margin: 0; font-weight: normal; }
                .flavor-preguntas-container { margin-top: 15px; }
                .flavor-pregunta-item { background: #f9fafb; border: 1px solid #e5e7eb; border-radius: 6px; padding: 15px; margin-bottom: 15px; position: relative; }
                .flavor-pregunta-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 10px; }
                .flavor-pregunta-numero { font-weight: 600; color: #8b5cf6; }
                .flavor-pregunta-eliminar { background: #fee2e2; color: #991b1b; border: none; padding: 5px 10px; border-radius: 4px; cursor: pointer; }
                .flavor-pregunta-eliminar:hover { background: #fecaca; }
                .flavor-opciones-container { margin-top: 10px; }
                .flavor-opcion-item { display: flex; gap: 8px; margin-bottom: 8px; }
                .flavor-opcion-item input { flex: 1; }
                .flavor-opcion-eliminar { background: none; border: none; color: #dc2626; cursor: pointer; padding: 5px; }
                .flavor-agregar-opcion { background: #f3f4f6; border: 1px dashed #9ca3af; color: #6b7280; padding: 8px 15px; cursor: pointer; border-radius: 4px; }
                .flavor-agregar-opcion:hover { background: #e5e7eb; }
                .flavor-agregar-pregunta { background: #8b5cf6; color: #fff; border: none; padding: 10px 20px; border-radius: 6px; cursor: pointer; font-size: 14px; }
                .flavor-agregar-pregunta:hover { background: #7c3aed; }
            </style>

            <form method="post" class="flavor-encuesta-form">
                <?php wp_nonce_field('guardar_encuesta', 'encuesta_nonce'); ?>

                <!-- Datos básicos -->
                <div class="flavor-form-section">
                    <h3><span class="dashicons dashicons-edit"></span> <?php _e('Información General', 'flavor-chat-ia'); ?></h3>

                    <div class="flavor-form-row">
                        <label for="titulo"><?php _e('Título de la encuesta', 'flavor-chat-ia'); ?> <span class="required">*</span></label>
                        <input type="text" id="titulo" name="titulo" value="<?php echo esc_attr($encuesta->titulo ?? ''); ?>" required>
                    </div>

                    <div class="flavor-form-row">
                        <label for="descripcion"><?php _e('Descripción', 'flavor-chat-ia'); ?></label>
                        <textarea id="descripcion" name="descripcion"><?php echo esc_textarea($encuesta->descripcion ?? ''); ?></textarea>
                    </div>

                    <div class="flavor-form-row">
                        <label for="tipo"><?php _e('Tipo', 'flavor-chat-ia'); ?></label>
                        <select id="tipo" name="tipo">
                            <?php foreach (self::TIPOS_ENCUESTA as $tipo_valor): ?>
                                <option value="<?php echo esc_attr($tipo_valor); ?>" <?php selected(($encuesta->tipo ?? ''), $tipo_valor); ?>>
                                    <?php echo esc_html(ucfirst($tipo_valor)); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="flavor-form-row">
                        <label for="estado"><?php _e('Estado', 'flavor-chat-ia'); ?></label>
                        <select id="estado" name="estado">
                            <?php foreach (self::ESTADOS_ENCUESTA as $estado_valor): ?>
                                <option value="<?php echo esc_attr($estado_valor); ?>" <?php selected(($encuesta->estado ?? 'borrador'), $estado_valor); ?>>
                                    <?php echo esc_html(ucfirst($estado_valor)); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="flavor-form-row">
                        <label for="fecha_cierre"><?php _e('Fecha de cierre (opcional)', 'flavor-chat-ia'); ?></label>
                        <input type="datetime-local" id="fecha_cierre" name="fecha_cierre" value="<?php echo esc_attr($encuesta->fecha_cierre ?? ''); ?>">
                    </div>
                </div>

                <!-- Opciones -->
                <div class="flavor-form-section">
                    <h3><span class="dashicons dashicons-admin-settings"></span> <?php _e('Opciones', 'flavor-chat-ia'); ?></h3>

                    <div class="flavor-form-row flavor-checkbox-row">
                        <input type="checkbox" id="es_anonima" name="es_anonima" value="1" <?php checked(!empty($encuesta->es_anonima)); ?>>
                        <label for="es_anonima"><?php _e('Respuestas anónimas', 'flavor-chat-ia'); ?></label>
                    </div>

                    <div class="flavor-form-row flavor-checkbox-row">
                        <input type="checkbox" id="permite_multiples" name="permite_multiples" value="1" <?php checked(!empty($encuesta->permite_multiples)); ?>>
                        <label for="permite_multiples"><?php _e('Permitir múltiples respuestas por usuario', 'flavor-chat-ia'); ?></label>
                    </div>

                    <div class="flavor-form-row">
                        <label for="mostrar_resultados"><?php _e('Mostrar resultados', 'flavor-chat-ia'); ?></label>
                        <select id="mostrar_resultados" name="mostrar_resultados">
                            <option value="siempre" <?php selected(($encuesta->mostrar_resultados ?? ''), 'siempre'); ?>><?php _e('Siempre', 'flavor-chat-ia'); ?></option>
                            <option value="al_votar" <?php selected(($encuesta->mostrar_resultados ?? 'al_votar'), 'al_votar'); ?>><?php _e('Después de votar', 'flavor-chat-ia'); ?></option>
                            <option value="al_cerrar" <?php selected(($encuesta->mostrar_resultados ?? ''), 'al_cerrar'); ?>><?php _e('Al cerrar encuesta', 'flavor-chat-ia'); ?></option>
                            <option value="nunca" <?php selected(($encuesta->mostrar_resultados ?? ''), 'nunca'); ?>><?php _e('Nunca', 'flavor-chat-ia'); ?></option>
                        </select>
                    </div>
                </div>

                <!-- Preguntas -->
                <div class="flavor-form-section">
                    <h3><span class="dashicons dashicons-list-view"></span> <?php _e('Preguntas', 'flavor-chat-ia'); ?></h3>

                    <div class="flavor-preguntas-container" id="preguntas-container">
                        <?php
                        if (!empty($campos)):
                            foreach ($campos as $indice => $campo):
                        ?>
                            <div class="flavor-pregunta-item" data-index="<?php echo $indice; ?>">
                                <div class="flavor-pregunta-header">
                                    <span class="flavor-pregunta-numero"><?php printf(__('Pregunta %d', 'flavor-chat-ia'), $indice + 1); ?></span>
                                    <button type="button" class="flavor-pregunta-eliminar" onclick="eliminarPregunta(this)"><?php _e('Eliminar', 'flavor-chat-ia'); ?></button>
                                </div>
                                <div class="flavor-form-row">
                                    <label><?php _e('Pregunta', 'flavor-chat-ia'); ?></label>
                                    <input type="text" name="preguntas[<?php echo $indice; ?>][etiqueta]" value="<?php echo esc_attr($campo->etiqueta); ?>" required>
                                </div>
                                <div class="flavor-form-row">
                                    <label><?php _e('Tipo de respuesta', 'flavor-chat-ia'); ?></label>
                                    <select name="preguntas[<?php echo $indice; ?>][tipo]" onchange="toggleOpciones(this)">
                                        <?php foreach (self::TIPOS_CAMPO as $tipo_key => $tipo_label): ?>
                                            <option value="<?php echo esc_attr($tipo_key); ?>" <?php selected($campo->tipo, $tipo_key); ?>>
                                                <?php echo esc_html($tipo_label); ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <?php if (in_array($campo->tipo, ['seleccion_unica', 'seleccion_multiple'])): ?>
                                <div class="flavor-opciones-container">
                                    <label><?php _e('Opciones', 'flavor-chat-ia'); ?></label>
                                    <?php if (!empty($campo->opciones) && is_array($campo->opciones)): ?>
                                        <?php foreach ($campo->opciones as $opcion_idx => $opcion): ?>
                                        <div class="flavor-opcion-item">
                                            <input type="text" name="preguntas[<?php echo $indice; ?>][opciones][]" value="<?php echo esc_attr($opcion); ?>" placeholder="<?php _e('Opción', 'flavor-chat-ia'); ?>">
                                            <button type="button" class="flavor-opcion-eliminar" onclick="eliminarOpcion(this)">×</button>
                                        </div>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                    <button type="button" class="flavor-agregar-opcion" onclick="agregarOpcion(this)"><?php _e('+ Añadir opción', 'flavor-chat-ia'); ?></button>
                                </div>
                                <?php endif; ?>
                                <div class="flavor-form-row flavor-checkbox-row" style="margin-top: 10px;">
                                    <input type="checkbox" name="preguntas[<?php echo $indice; ?>][es_requerido]" value="1" <?php checked($campo->es_requerido); ?>>
                                    <label><?php _e('Campo obligatorio', 'flavor-chat-ia'); ?></label>
                                </div>
                            </div>
                        <?php
                            endforeach;
                        endif;
                        ?>
                    </div>

                    <button type="button" class="flavor-agregar-pregunta" onclick="agregarPregunta()">
                        <span class="dashicons dashicons-plus-alt" style="vertical-align: middle;"></span>
                        <?php _e('Añadir pregunta', 'flavor-chat-ia'); ?>
                    </button>
                </div>

                <p class="submit">
                    <button type="submit" class="button button-primary button-large">
                        <?php echo $encuesta_id ? __('Guardar Cambios', 'flavor-chat-ia') : __('Crear Encuesta', 'flavor-chat-ia'); ?>
                    </button>
                    <a href="<?php echo admin_url('admin.php?page=encuestas-listado'); ?>" class="button button-large">
                        <?php _e('Cancelar', 'flavor-chat-ia'); ?>
                    </a>
                </p>
            </form>
        </div>

        <script>
        let preguntaIndex = <?php echo !empty($campos) ? count($campos) : 0; ?>;

        const tiposCampo = <?php echo json_encode(self::TIPOS_CAMPO); ?>;
        const tiposConOpciones = ['seleccion_unica', 'seleccion_multiple'];

        function agregarPregunta() {
            const container = document.getElementById('preguntas-container');
            const html = `
                <div class="flavor-pregunta-item" data-index="${preguntaIndex}">
                    <div class="flavor-pregunta-header">
                        <span class="flavor-pregunta-numero"><?php _e('Pregunta', 'flavor-chat-ia'); ?> ${preguntaIndex + 1}</span>
                        <button type="button" class="flavor-pregunta-eliminar" onclick="eliminarPregunta(this)"><?php _e('Eliminar', 'flavor-chat-ia'); ?></button>
                    </div>
                    <div class="flavor-form-row">
                        <label><?php _e('Pregunta', 'flavor-chat-ia'); ?></label>
                        <input type="text" name="preguntas[${preguntaIndex}][etiqueta]" required>
                    </div>
                    <div class="flavor-form-row">
                        <label><?php _e('Tipo de respuesta', 'flavor-chat-ia'); ?></label>
                        <select name="preguntas[${preguntaIndex}][tipo]" onchange="toggleOpciones(this)">
                            ${Object.entries(tiposCampo).map(([key, label]) => `<option value="${key}">${label}</option>`).join('')}
                        </select>
                    </div>
                    <div class="flavor-opciones-container" style="display: none;">
                        <label><?php _e('Opciones', 'flavor-chat-ia'); ?></label>
                        <div class="flavor-opcion-item">
                            <input type="text" name="preguntas[${preguntaIndex}][opciones][]" placeholder="<?php _e('Opción 1', 'flavor-chat-ia'); ?>">
                            <button type="button" class="flavor-opcion-eliminar" onclick="eliminarOpcion(this)">×</button>
                        </div>
                        <div class="flavor-opcion-item">
                            <input type="text" name="preguntas[${preguntaIndex}][opciones][]" placeholder="<?php _e('Opción 2', 'flavor-chat-ia'); ?>">
                            <button type="button" class="flavor-opcion-eliminar" onclick="eliminarOpcion(this)">×</button>
                        </div>
                        <button type="button" class="flavor-agregar-opcion" onclick="agregarOpcion(this)"><?php _e('+ Añadir opción', 'flavor-chat-ia'); ?></button>
                    </div>
                    <div class="flavor-form-row flavor-checkbox-row" style="margin-top: 10px;">
                        <input type="checkbox" name="preguntas[${preguntaIndex}][es_requerido]" value="1" checked>
                        <label><?php _e('Campo obligatorio', 'flavor-chat-ia'); ?></label>
                    </div>
                </div>
            `;
            container.insertAdjacentHTML('beforeend', html);
            preguntaIndex++;

            // Mostrar opciones si es selección
            const lastItem = container.lastElementChild;
            const select = lastItem.querySelector('select');
            toggleOpciones(select);
        }

        function eliminarPregunta(btn) {
            if (confirm('<?php _e('¿Eliminar esta pregunta?', 'flavor-chat-ia'); ?>')) {
                btn.closest('.flavor-pregunta-item').remove();
            }
        }

        function toggleOpciones(select) {
            const container = select.closest('.flavor-pregunta-item').querySelector('.flavor-opciones-container');
            if (container) {
                container.style.display = tiposConOpciones.includes(select.value) ? 'block' : 'none';
            }
        }

        function agregarOpcion(btn) {
            const container = btn.closest('.flavor-opciones-container');
            const preguntaItem = btn.closest('.flavor-pregunta-item');
            const index = preguntaItem.dataset.index;
            const opcionCount = container.querySelectorAll('.flavor-opcion-item').length;

            const html = `
                <div class="flavor-opcion-item">
                    <input type="text" name="preguntas[${index}][opciones][]" placeholder="<?php _e('Opción', 'flavor-chat-ia'); ?> ${opcionCount + 1}">
                    <button type="button" class="flavor-opcion-eliminar" onclick="eliminarOpcion(this)">×</button>
                </div>
            `;
            btn.insertAdjacentHTML('beforebegin', html);
        }

        function eliminarOpcion(btn) {
            const container = btn.closest('.flavor-opciones-container');
            if (container.querySelectorAll('.flavor-opcion-item').length > 1) {
                btn.closest('.flavor-opcion-item').remove();
            }
        }
        </script>
        <?php
    }

    /**
     * Renderiza la página de resultados
     */
    public function render_admin_resultados() {
        global $wpdb;

        $encuesta_id = isset($_GET['id']) ? absint($_GET['id']) : 0;

        $this->render_page_header(
            __('Resultados de Encuestas', 'flavor-chat-ia'),
            [
                [
                    'label' => __('Ver Listado', 'flavor-chat-ia'),
                    'url'   => admin_url('admin.php?page=encuestas-listado'),
                    'class' => '',
                ],
            ]
        );

        // Si no hay ID, mostrar selector de encuestas
        if (!$encuesta_id) {
            $tabla_encuestas = $wpdb->prefix . 'flavor_encuestas';
            $encuestas = $wpdb->get_results(
                "SELECT id, titulo, estado, total_participantes, total_respuestas, fecha_creacion
                 FROM $tabla_encuestas
                 WHERE total_participantes > 0
                 ORDER BY fecha_creacion DESC
                 LIMIT 20"
            );
            ?>
            <div class="wrap">
                <h3><?php _e('Selecciona una encuesta para ver sus resultados', 'flavor-chat-ia'); ?></h3>
                <?php if (empty($encuestas)): ?>
                    <p class="description"><?php _e('No hay encuestas con respuestas aún.', 'flavor-chat-ia'); ?></p>
                <?php else: ?>
                    <table class="wp-list-table widefat fixed striped">
                        <thead>
                            <tr>
                                <th><?php _e('Encuesta', 'flavor-chat-ia'); ?></th>
                                <th style="width: 100px;"><?php _e('Estado', 'flavor-chat-ia'); ?></th>
                                <th style="width: 120px;"><?php _e('Participantes', 'flavor-chat-ia'); ?></th>
                                <th style="width: 120px;"><?php _e('Respuestas', 'flavor-chat-ia'); ?></th>
                                <th style="width: 100px;"><?php _e('Acción', 'flavor-chat-ia'); ?></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($encuestas as $enc): ?>
                                <tr>
                                    <td><strong><?php echo esc_html($enc->titulo); ?></strong></td>
                                    <td>
                                        <span class="flavor-estado flavor-estado-<?php echo esc_attr($enc->estado); ?>">
                                            <?php echo esc_html(ucfirst($enc->estado)); ?>
                                        </span>
                                    </td>
                                    <td><?php echo esc_html($enc->total_participantes); ?></td>
                                    <td><?php echo esc_html($enc->total_respuestas); ?></td>
                                    <td>
                                        <a href="<?php echo admin_url('admin.php?page=encuestas-resultados&id=' . $enc->id); ?>" class="button button-primary button-small">
                                            <?php _e('Ver', 'flavor-chat-ia'); ?>
                                        </a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php endif; ?>
            </div>
            <style>
                .flavor-estado { display: inline-block; padding: 2px 8px; border-radius: 10px; font-size: 11px; font-weight: 500; }
                .flavor-estado-activa { background: #dcfce7; color: #166534; }
                .flavor-estado-borrador { background: #f3f4f6; color: #4b5563; }
                .flavor-estado-cerrada { background: #fee2e2; color: #991b1b; }
            </style>
            <?php
            return;
        }

        // Obtener resultados de la encuesta
        $resultados = $this->obtener_resultados($encuesta_id);
        $encuesta = $this->obtener_encuesta($encuesta_id);

        if (!$encuesta) {
            echo '<div class="notice notice-error"><p>' . __('Encuesta no encontrada.', 'flavor-chat-ia') . '</p></div>';
            return;
        }
        ?>
        <div class="wrap flavor-resultados">
            <style>
                .flavor-resultados { max-width: 1000px; }
                .flavor-resultado-header { background: #fff; border: 1px solid #e0e0e0; border-radius: 8px; padding: 20px; margin-bottom: 20px; }
                .flavor-resultado-header h2 { margin: 0 0 10px 0; }
                .flavor-resultado-stats { display: flex; gap: 30px; margin-top: 15px; }
                .flavor-resultado-stat { text-align: center; }
                .flavor-resultado-stat .valor { font-size: 24px; font-weight: 600; color: #8b5cf6; }
                .flavor-resultado-stat .label { color: #6b7280; font-size: 13px; }
                .flavor-campo-resultado { background: #fff; border: 1px solid #e0e0e0; border-radius: 8px; padding: 20px; margin-bottom: 15px; }
                .flavor-campo-resultado h3 { margin: 0 0 15px 0; display: flex; align-items: center; gap: 8px; }
                .flavor-campo-resultado h3 .tipo { background: #f3f4f6; padding: 2px 8px; border-radius: 4px; font-size: 12px; font-weight: normal; color: #6b7280; }
                .flavor-barra-container { margin-bottom: 10px; }
                .flavor-barra-label { display: flex; justify-content: space-between; margin-bottom: 5px; }
                .flavor-barra-opcion { font-weight: 500; }
                .flavor-barra-count { color: #6b7280; }
                .flavor-barra { height: 24px; background: #f3f4f6; border-radius: 4px; overflow: hidden; }
                .flavor-barra-fill { height: 100%; background: linear-gradient(90deg, #8b5cf6, #a78bfa); border-radius: 4px; transition: width 0.3s; display: flex; align-items: center; justify-content: flex-end; padding-right: 8px; }
                .flavor-barra-porcentaje { color: #fff; font-size: 12px; font-weight: 600; }
                .flavor-respuestas-texto { max-height: 200px; overflow-y: auto; background: #f9fafb; padding: 15px; border-radius: 6px; }
                .flavor-respuestas-texto p { margin: 0 0 8px 0; padding-bottom: 8px; border-bottom: 1px solid #e5e7eb; }
                .flavor-respuestas-texto p:last-child { margin-bottom: 0; padding-bottom: 0; border-bottom: none; }
                .flavor-estadisticas-num { display: grid; grid-template-columns: repeat(3, 1fr); gap: 15px; }
                .flavor-stat-box { background: #f9fafb; padding: 15px; border-radius: 6px; text-align: center; }
                .flavor-stat-box .valor { font-size: 20px; font-weight: 600; color: #1d2327; }
                .flavor-stat-box .label { color: #6b7280; font-size: 12px; }
            </style>

            <!-- Header con resumen -->
            <div class="flavor-resultado-header">
                <h2><?php echo esc_html($encuesta->titulo); ?></h2>
                <p class="description"><?php echo esc_html($encuesta->descripcion); ?></p>
                <div class="flavor-resultado-stats">
                    <div class="flavor-resultado-stat">
                        <div class="valor"><?php echo esc_html($resultados['total_participantes']); ?></div>
                        <div class="label"><?php _e('Participantes', 'flavor-chat-ia'); ?></div>
                    </div>
                    <div class="flavor-resultado-stat">
                        <div class="valor"><?php echo esc_html($resultados['total_respuestas']); ?></div>
                        <div class="label"><?php _e('Respuestas', 'flavor-chat-ia'); ?></div>
                    </div>
                    <div class="flavor-resultado-stat">
                        <div class="valor">
                            <span class="flavor-estado flavor-estado-<?php echo esc_attr($encuesta->estado); ?>">
                                <?php echo esc_html(ucfirst($encuesta->estado)); ?>
                            </span>
                        </div>
                        <div class="label"><?php _e('Estado', 'flavor-chat-ia'); ?></div>
                    </div>
                </div>
            </div>

            <!-- Resultados por campo -->
            <?php foreach ($resultados['campos'] as $campo): ?>
                <div class="flavor-campo-resultado">
                    <h3>
                        <?php echo esc_html($campo['etiqueta']); ?>
                        <span class="tipo"><?php echo esc_html(self::TIPOS_CAMPO[$campo['tipo']] ?? $campo['tipo']); ?></span>
                    </h3>

                    <?php
                    // Renderizar según tipo
                    switch ($campo['tipo']):
                        case 'seleccion_unica':
                        case 'seleccion_multiple':
                        case 'si_no':
                            $total_votos = array_sum($campo['conteos'] ?? []);
                            $opciones = $campo['opciones'] ?? [];

                            if ($campo['tipo'] === 'si_no') {
                                $opciones = [__('Sí', 'flavor-chat-ia'), __('No', 'flavor-chat-ia')];
                            }

                            foreach ($opciones as $idx => $opcion):
                                $votos = $campo['conteos'][$idx] ?? 0;
                                $porcentaje = $total_votos > 0 ? round(($votos / $total_votos) * 100, 1) : 0;
                            ?>
                            <div class="flavor-barra-container">
                                <div class="flavor-barra-label">
                                    <span class="flavor-barra-opcion"><?php echo esc_html($opcion); ?></span>
                                    <span class="flavor-barra-count"><?php echo $votos; ?> <?php _e('votos', 'flavor-chat-ia'); ?></span>
                                </div>
                                <div class="flavor-barra">
                                    <div class="flavor-barra-fill" style="width: <?php echo $porcentaje; ?>%;">
                                        <?php if ($porcentaje > 5): ?>
                                            <span class="flavor-barra-porcentaje"><?php echo $porcentaje; ?>%</span>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                            <?php endforeach;
                            break;

                        case 'escala':
                        case 'estrellas':
                            $min_valor = 1;
                            $max_valor = $campo['tipo'] === 'estrellas' ? 5 : 10;
                            $total_votos = array_sum($campo['conteos'] ?? []);

                            for ($i = $min_valor; $i <= $max_valor; $i++):
                                $votos = $campo['conteos'][$i] ?? 0;
                                $porcentaje = $total_votos > 0 ? round(($votos / $total_votos) * 100, 1) : 0;
                            ?>
                            <div class="flavor-barra-container">
                                <div class="flavor-barra-label">
                                    <span class="flavor-barra-opcion">
                                        <?php
                                        if ($campo['tipo'] === 'estrellas') {
                                            echo str_repeat('⭐', $i);
                                        } else {
                                            echo $i;
                                        }
                                        ?>
                                    </span>
                                    <span class="flavor-barra-count"><?php echo $votos; ?></span>
                                </div>
                                <div class="flavor-barra">
                                    <div class="flavor-barra-fill" style="width: <?php echo $porcentaje; ?>%;">
                                        <?php if ($porcentaje > 5): ?>
                                            <span class="flavor-barra-porcentaje"><?php echo $porcentaje; ?>%</span>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                            <?php endfor;
                            break;

                        case 'numero':
                        case 'rango':
                        case 'nps':
                            $stats = $campo['estadisticas'] ?? [];
                            ?>
                            <div class="flavor-estadisticas-num">
                                <div class="flavor-stat-box">
                                    <div class="valor"><?php echo esc_html(number_format($stats['promedio'] ?? 0, 2)); ?></div>
                                    <div class="label"><?php _e('Promedio', 'flavor-chat-ia'); ?></div>
                                </div>
                                <div class="flavor-stat-box">
                                    <div class="valor"><?php echo esc_html($stats['minimo'] ?? 0); ?></div>
                                    <div class="label"><?php _e('Mínimo', 'flavor-chat-ia'); ?></div>
                                </div>
                                <div class="flavor-stat-box">
                                    <div class="valor"><?php echo esc_html($stats['maximo'] ?? 0); ?></div>
                                    <div class="label"><?php _e('Máximo', 'flavor-chat-ia'); ?></div>
                                </div>
                            </div>
                            <?php
                            break;

                        default: // texto, textarea
                            $respuestas = $campo['respuestas_texto'] ?? [];
                            ?>
                            <div class="flavor-respuestas-texto">
                                <?php if (empty($respuestas)): ?>
                                    <p class="description"><?php _e('Sin respuestas.', 'flavor-chat-ia'); ?></p>
                                <?php else: ?>
                                    <?php foreach ($respuestas as $respuesta): ?>
                                        <p><?php echo esc_html($respuesta); ?></p>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </div>
                            <?php
                            break;
                    endswitch;
                    ?>
                </div>
            <?php endforeach; ?>

            <!-- Acciones -->
            <div style="margin-top: 20px;">
                <a href="<?php echo admin_url('admin.php?page=encuestas-crear&id=' . $encuesta_id); ?>" class="button">
                    <span class="dashicons dashicons-edit" style="vertical-align: middle;"></span>
                    <?php _e('Editar Encuesta', 'flavor-chat-ia'); ?>
                </a>
                <?php if ($encuesta->estado === 'activa'): ?>
                    <a href="<?php echo wp_nonce_url(admin_url('admin.php?page=encuestas-listado&action=cerrar&id=' . $encuesta_id), 'cerrar_encuesta_' . $encuesta_id); ?>" class="button">
                        <span class="dashicons dashicons-lock" style="vertical-align: middle;"></span>
                        <?php _e('Cerrar Encuesta', 'flavor-chat-ia'); ?>
                    </a>
                <?php endif; ?>
            </div>
        </div>
        <?php
    }

    /**
     * Renderiza la página de configuración
     */
    public function render_admin_config() {
        // Guardar configuración
        if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['config_nonce']) && wp_verify_nonce($_POST['config_nonce'], 'guardar_config_encuestas')) {
            $nueva_config = [
                'permitir_encuestas_anonimas'   => isset($_POST['permitir_encuestas_anonimas']),
                'permitir_multiples_respuestas' => isset($_POST['permitir_multiples_respuestas']),
                'moderacion_encuestas'          => isset($_POST['moderacion_encuestas']),
                'max_opciones_por_pregunta'     => absint($_POST['max_opciones_por_pregunta'] ?? 10),
                'max_campos_por_encuesta'       => absint($_POST['max_campos_por_encuesta'] ?? 20),
                'duracion_default_dias'         => absint($_POST['duracion_default_dias'] ?? 7),
                'notificar_nuevas_respuestas'   => isset($_POST['notificar_nuevas_respuestas']),
                'notificar_cierre_encuesta'     => isset($_POST['notificar_cierre_encuesta']),
                'permitir_exportar_resultados'  => isset($_POST['permitir_exportar_resultados']),
                'mostrar_estadisticas_publicas' => isset($_POST['mostrar_estadisticas_publicas']),
            ];

            foreach ($nueva_config as $key => $value) {
                $this->set_setting($key, $value);
            }

            echo '<div class="notice notice-success"><p>' . __('Configuración guardada correctamente.', 'flavor-chat-ia') . '</p></div>';
        }

        $config = $this->get_settings();

        $this->render_page_header(__('Configuración de Encuestas', 'flavor-chat-ia'));
        ?>
        <div class="wrap">
            <style>
                .flavor-config-form { max-width: 700px; background: #fff; padding: 25px; border: 1px solid #e0e0e0; border-radius: 8px; }
                .flavor-config-section { margin-bottom: 25px; padding-bottom: 20px; border-bottom: 1px solid #eee; }
                .flavor-config-section:last-child { border-bottom: none; margin-bottom: 0; }
                .flavor-config-section h3 { margin: 0 0 15px 0; color: #1d2327; display: flex; align-items: center; gap: 8px; }
                .flavor-config-section h3 .dashicons { color: #8b5cf6; }
                .flavor-config-row { margin-bottom: 15px; }
                .flavor-config-row label { display: flex; align-items: center; gap: 10px; }
                .flavor-config-row label input[type="checkbox"] { margin: 0; }
                .flavor-config-row input[type="number"] { width: 80px; }
                .flavor-config-description { color: #6b7280; font-size: 13px; margin-left: 30px; margin-top: 3px; }
            </style>

            <form method="post" class="flavor-config-form">
                <?php wp_nonce_field('guardar_config_encuestas', 'config_nonce'); ?>

                <!-- Opciones generales -->
                <div class="flavor-config-section">
                    <h3><span class="dashicons dashicons-admin-settings"></span> <?php _e('Opciones Generales', 'flavor-chat-ia'); ?></h3>

                    <div class="flavor-config-row">
                        <label>
                            <input type="checkbox" name="permitir_encuestas_anonimas" value="1" <?php checked($config['permitir_encuestas_anonimas'] ?? true); ?>>
                            <?php _e('Permitir encuestas anónimas', 'flavor-chat-ia'); ?>
                        </label>
                        <p class="flavor-config-description"><?php _e('Los creadores pueden elegir si las respuestas son anónimas.', 'flavor-chat-ia'); ?></p>
                    </div>

                    <div class="flavor-config-row">
                        <label>
                            <input type="checkbox" name="permitir_multiples_respuestas" value="1" <?php checked($config['permitir_multiples_respuestas'] ?? false); ?>>
                            <?php _e('Permitir múltiples respuestas por defecto', 'flavor-chat-ia'); ?>
                        </label>
                        <p class="flavor-config-description"><?php _e('Los usuarios pueden responder varias veces a la misma encuesta.', 'flavor-chat-ia'); ?></p>
                    </div>

                    <div class="flavor-config-row">
                        <label>
                            <input type="checkbox" name="moderacion_encuestas" value="1" <?php checked($config['moderacion_encuestas'] ?? true); ?>>
                            <?php _e('Moderar encuestas antes de publicar', 'flavor-chat-ia'); ?>
                        </label>
                        <p class="flavor-config-description"><?php _e('Las encuestas requieren aprobación de un administrador.', 'flavor-chat-ia'); ?></p>
                    </div>

                    <div class="flavor-config-row">
                        <label>
                            <input type="checkbox" name="mostrar_estadisticas_publicas" value="1" <?php checked($config['mostrar_estadisticas_publicas'] ?? true); ?>>
                            <?php _e('Mostrar estadísticas públicas', 'flavor-chat-ia'); ?>
                        </label>
                        <p class="flavor-config-description"><?php _e('Permite ver resultados según configuración de cada encuesta.', 'flavor-chat-ia'); ?></p>
                    </div>
                </div>

                <!-- Límites -->
                <div class="flavor-config-section">
                    <h3><span class="dashicons dashicons-editor-ol"></span> <?php _e('Límites', 'flavor-chat-ia'); ?></h3>

                    <div class="flavor-config-row">
                        <label>
                            <?php _e('Máximo de opciones por pregunta:', 'flavor-chat-ia'); ?>
                            <input type="number" name="max_opciones_por_pregunta" value="<?php echo esc_attr($config['max_opciones_por_pregunta'] ?? 10); ?>" min="2" max="50">
                        </label>
                    </div>

                    <div class="flavor-config-row">
                        <label>
                            <?php _e('Máximo de preguntas por encuesta:', 'flavor-chat-ia'); ?>
                            <input type="number" name="max_campos_por_encuesta" value="<?php echo esc_attr($config['max_campos_por_encuesta'] ?? 20); ?>" min="1" max="100">
                        </label>
                    </div>

                    <div class="flavor-config-row">
                        <label>
                            <?php _e('Duración por defecto (días):', 'flavor-chat-ia'); ?>
                            <input type="number" name="duracion_default_dias" value="<?php echo esc_attr($config['duracion_default_dias'] ?? 7); ?>" min="1" max="365">
                        </label>
                    </div>
                </div>

                <!-- Notificaciones -->
                <div class="flavor-config-section">
                    <h3><span class="dashicons dashicons-bell"></span> <?php _e('Notificaciones', 'flavor-chat-ia'); ?></h3>

                    <div class="flavor-config-row">
                        <label>
                            <input type="checkbox" name="notificar_nuevas_respuestas" value="1" <?php checked($config['notificar_nuevas_respuestas'] ?? true); ?>>
                            <?php _e('Notificar nuevas respuestas al autor', 'flavor-chat-ia'); ?>
                        </label>
                    </div>

                    <div class="flavor-config-row">
                        <label>
                            <input type="checkbox" name="notificar_cierre_encuesta" value="1" <?php checked($config['notificar_cierre_encuesta'] ?? true); ?>>
                            <?php _e('Notificar al cerrar encuesta', 'flavor-chat-ia'); ?>
                        </label>
                    </div>
                </div>

                <!-- Exportación -->
                <div class="flavor-config-section">
                    <h3><span class="dashicons dashicons-download"></span> <?php _e('Exportación', 'flavor-chat-ia'); ?></h3>

                    <div class="flavor-config-row">
                        <label>
                            <input type="checkbox" name="permitir_exportar_resultados" value="1" <?php checked($config['permitir_exportar_resultados'] ?? true); ?>>
                            <?php _e('Permitir exportar resultados', 'flavor-chat-ia'); ?>
                        </label>
                        <p class="flavor-config-description"><?php _e('Los autores pueden exportar resultados a CSV.', 'flavor-chat-ia'); ?></p>
                    </div>
                </div>

                <p class="submit">
                    <button type="submit" class="button button-primary">
                        <?php _e('Guardar Configuración', 'flavor-chat-ia'); ?>
                    </button>
                </p>
            </form>
        </div>
        <?php
    }

    // =========================================================================
    // INTERFACE REQUIREMENTS
    // =========================================================================

    /**
     * {@inheritdoc}
     */
    public function get_actions() {
        return [
            'crear_encuesta' => [
                'label'       => __('Crear encuesta', 'flavor-chat-ia'),
                'description' => __('Crea una nueva encuesta', 'flavor-chat-ia'),
                'callback'    => [$this, 'crear_encuesta'],
            ],
            'cerrar_encuesta' => [
                'label'       => __('Cerrar encuesta', 'flavor-chat-ia'),
                'description' => __('Cierra una encuesta para no aceptar más respuestas', 'flavor-chat-ia'),
                'callback'    => [$this, 'cerrar_encuesta'],
            ],
            'obtener_resultados' => [
                'label'       => __('Obtener resultados', 'flavor-chat-ia'),
                'description' => __('Obtiene los resultados de una encuesta', 'flavor-chat-ia'),
                'callback'    => [$this, 'obtener_resultados'],
            ],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function execute_action($action_name, $params) {
        $acciones = $this->get_actions();

        if (!isset($acciones[$action_name])) {
            return [
                'success' => false,
                'error'   => __('Acción no encontrada', 'flavor-chat-ia'),
            ];
        }

        $callback = $acciones[$action_name]['callback'];
        $resultado = call_user_func($callback, $params);

        if (is_wp_error($resultado)) {
            return [
                'success' => false,
                'error'   => $resultado->get_error_message(),
            ];
        }

        return [
            'success' => true,
            'data'    => $resultado,
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function get_tool_definitions() {
        return [];
    }

    /**
     * {@inheritdoc}
     */
    public function get_knowledge_base() {
        return __('Sistema de encuestas y formularios que permite crear votaciones, cuestionarios y formularios reutilizables en diferentes contextos de la plataforma.', 'flavor-chat-ia');
    }

    /**
     * Obtiene el renderer
     *
     * @return Flavor_Encuestas_Renderer|null
     */
    public function get_renderer() {
        return $this->renderer;
    }

    /**
     * Obtiene la API
     *
     * @return Flavor_Encuestas_API|null
     */
    public function get_api() {
        return $this->api;
    }

    /**
     * Configuración para el Module Renderer
     *
     * @return array
     */
    public static function get_renderer_config(): array {
        return [
            'module'   => 'encuestas',
            'title'    => __('Encuestas y Votaciones', 'flavor-chat-ia'),
            'subtitle' => __('Crea encuestas, votaciones y formularios para tu comunidad', 'flavor-chat-ia'),
            'icon'     => '📊',
            'color'    => 'accent', // Usa variable CSS --flavor-primary del tema

            'database' => [
                'table'       => 'flavor_encuestas',
                'primary_key' => 'id',
            ],

            'fields' => [
                'titulo'       => ['type' => 'text', 'label' => __('Título', 'flavor-chat-ia'), 'required' => true],
                'descripcion'  => ['type' => 'textarea', 'label' => __('Descripción', 'flavor-chat-ia')],
                'tipo'         => ['type' => 'select', 'label' => __('Tipo', 'flavor-chat-ia'), 'options' => ['encuesta', 'votacion', 'formulario', 'quiz']],
                'fecha_inicio' => ['type' => 'datetime', 'label' => __('Fecha inicio', 'flavor-chat-ia')],
                'fecha_fin'    => ['type' => 'datetime', 'label' => __('Fecha fin', 'flavor-chat-ia')],
                'anonima'      => ['type' => 'checkbox', 'label' => __('Respuestas anónimas', 'flavor-chat-ia')],
                'multiple'     => ['type' => 'checkbox', 'label' => __('Selección múltiple', 'flavor-chat-ia')],
            ],

            'estados' => [
                'borrador'   => ['label' => __('Borrador', 'flavor-chat-ia'), 'color' => 'gray', 'icon' => '📝'],
                'activa'     => ['label' => __('Activa', 'flavor-chat-ia'), 'color' => 'green', 'icon' => '🟢'],
                'pausada'    => ['label' => __('Pausada', 'flavor-chat-ia'), 'color' => 'yellow', 'icon' => '⏸️'],
                'finalizada' => ['label' => __('Finalizada', 'flavor-chat-ia'), 'color' => 'blue', 'icon' => '✅'],
                'archivada'  => ['label' => __('Archivada', 'flavor-chat-ia'), 'color' => 'slate', 'icon' => '🗄️'],
            ],

            'stats' => [
                'encuestas_activas' => ['label' => __('Activas', 'flavor-chat-ia'), 'icon' => '📊', 'color' => 'violet'],
                'respuestas_total'  => ['label' => __('Respuestas', 'flavor-chat-ia'), 'icon' => '✏️', 'color' => 'blue'],
                'participacion'     => ['label' => __('Participación', 'flavor-chat-ia'), 'icon' => '👥', 'color' => 'green'],
                'finalizadas_mes'   => ['label' => __('Finalizadas/mes', 'flavor-chat-ia'), 'icon' => '📈', 'color' => 'purple'],
            ],

            'card' => [
                'template'     => 'encuesta-card',
                'title_field'  => 'titulo',
                'subtitle_field' => 'tipo',
                'meta_fields'  => ['fecha_fin', 'respuestas_count', 'estado'],
                'show_progreso' => true,
                'show_estado'  => true,
            ],

            'tabs' => [
                'activas' => [
                    'label'   => __('Activas', 'flavor-chat-ia'),
                    'icon'    => 'dashicons-chart-bar',
                    'content' => 'template:_archive.php',
                    'public'  => true,
                ],
                'crear' => [
                    'label'      => __('Crear encuesta', 'flavor-chat-ia'),
                    'icon'       => 'dashicons-plus-alt',
                    'content'    => 'shortcode:flavor_encuesta_crear',
                    'requires_login' => true,
                ],
                'mis-encuestas' => [
                    'label'      => __('Mis encuestas', 'flavor-chat-ia'),
                    'icon'       => 'dashicons-admin-users',
                    'content'    => 'shortcode:encuestas_mis_encuestas',
                    'requires_login' => true,
                ],
                'resultados' => [
                    'label'   => __('Resultados', 'flavor-chat-ia'),
                    'icon'    => 'dashicons-chart-pie',
                    'content' => 'shortcode:encuestas_resultados',
                    'public'  => true,
                ],
            ],

            'archive' => [
                'columns'    => 2,
                'per_page'   => 10,
                'order_by'   => 'fecha_creacion',
                'order'      => 'DESC',
                'filterable' => ['tipo', 'estado'],
            ],

            'dashboard' => [
                'widgets' => ['stats', 'encuestas_activas', 'mis_respuestas', 'resultados_recientes'],
                'actions' => [
                    'crear'      => ['label' => __('Nueva encuesta', 'flavor-chat-ia'), 'icon' => '📊', 'color' => 'violet'],
                    'responder'  => ['label' => __('Participar', 'flavor-chat-ia'), 'icon' => '✏️', 'color' => 'blue'],
                ],
            ],

            'features' => [
                'tipos_pregunta'  => true,
                'logica_condicional' => true,
                'graficos'        => true,
                'exportar'        => true,
                'integraciones'   => true,
                'anonimato'       => true,
            ],
        ];
    }


    /**
     * Inicializa el dashboard tab del módulo
     */
    private function inicializar_dashboard_tab() {
        $archivo = dirname(__FILE__) . '/class-encuestas-dashboard-tab.php';
        if (file_exists($archivo)) {
            require_once $archivo;
            if (class_exists('Flavor_Encuestas_Dashboard_Tab')) {
                Flavor_Encuestas_Dashboard_Tab::get_instance();
            }
        }
    }
}

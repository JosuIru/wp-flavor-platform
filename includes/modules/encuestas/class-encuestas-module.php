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
        'seleccion_unica'    => 'Selección única',
        'seleccion_multiple' => 'Selección múltiple',
        'fecha'              => 'Fecha',
        'numero'             => 'Número',
        'escala'             => 'Escala (1-10)',
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
    }

    /**
     * Encola assets
     */
    public function enqueue_assets() {
        if (!$this->should_load_assets()) {
            return;
        }

        $ruta_assets = plugin_dir_url(__FILE__) . 'assets/';

        wp_enqueue_style(
            'flavor-encuestas',
            $ruta_assets . 'css/encuestas.css',
            [],
            self::VERSION
        );

        wp_enqueue_script(
            'flavor-encuestas',
            $ruta_assets . 'js/encuestas.js',
            ['jquery'],
            self::VERSION,
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
            case 'escala':
            case 'estrellas':
                $datos['valor'] = is_numeric($valor) ? floatval($valor) : null;
                $datos['opcion_index'] = absint($valor);
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

    /**
     * Registra en panel unificado de gestión
     */
    private function registrar_en_panel_unificado() {
        add_filter('flavor_panel_unificado_tabs', function($tabs) {
            $tabs['encuestas'] = [
                'label'    => __('Encuestas', 'flavor-chat-ia'),
                'icon'     => 'dashicons-forms',
                'priority' => 60,
            ];
            return $tabs;
        });
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
}

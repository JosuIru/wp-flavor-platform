<?php
/**
 * Clase para gestionar presets de diseño en Visual Builder Pro
 *
 * Permite aplicar esquemas de colores predefinidos y personalizados
 * a los diseños creados con el editor visual.
 *
 * @package Flavor_Chat_IA
 * @subpackage Visual_Builder_Pro
 * @since 1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Clase Flavor_VBP_Design_Presets
 *
 * Sistema de presets de colores y estilos globales para VBP
 */
class Flavor_VBP_Design_Presets {

    /**
     * Instancia única de la clase (Singleton)
     *
     * @var Flavor_VBP_Design_Presets
     */
    private static $instance = null;

    /**
     * Namespace para la REST API
     *
     * @var string
     */
    const NAMESPACE = 'flavor-vbp/v1';

    /**
     * Opción donde se guardan los presets personalizados
     *
     * @var string
     */
    const OPTION_CUSTOM_PRESETS = 'flavor_vbp_custom_presets';

    /**
     * Opción donde se guarda el preset activo
     *
     * @var string
     */
    const OPTION_ACTIVE_PRESET = 'flavor_vbp_active_preset';

    /**
     * Presets predefinidos del sistema
     *
     * @var array
     */
    private $presets_predefinidos = array();

    /**
     * Obtiene la instancia única de la clase
     *
     * @return Flavor_VBP_Design_Presets
     */
    public static function get_instance() {
        if ( null === self::$instance ) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Constructor privado (Singleton)
     */
    private function __construct() {
        $this->inicializar_presets_predefinidos();
        $this->registrar_hooks();
    }

    /**
     * Inicializa los presets de colores predefinidos
     *
     * @return void
     */
    private function inicializar_presets_predefinidos() {
        $this->presets_predefinidos = array(
            'modern-blue' => array(
                'id'          => 'modern-blue',
                'nombre'      => __( 'Azul Moderno', 'flavor-chat-ia' ),
                'descripcion' => __( 'Esquema profesional con tonos azules', 'flavor-chat-ia' ),
                'colores'     => array(
                    'primary'    => '#3b82f6',
                    'secondary'  => '#1e40af',
                    'accent'     => '#60a5fa',
                    'background' => '#f8fafc',
                    'surface'    => '#ffffff',
                    'text'       => '#1e293b',
                    'text-muted' => '#64748b',
                    'border'     => '#e2e8f0',
                    'success'    => '#22c55e',
                    'warning'    => '#f59e0b',
                    'error'      => '#ef4444',
                ),
                'tipografia'  => array(
                    'font-family-heading' => "'Inter', sans-serif",
                    'font-family-body'    => "'Inter', sans-serif",
                    'font-size-base'      => '16px',
                    'line-height-base'    => '1.6',
                ),
                'espaciado'   => array(
                    'spacing-unit' => '8px',
                    'border-radius' => '8px',
                ),
                'es_sistema'  => true,
            ),
            'eco-green' => array(
                'id'          => 'eco-green',
                'nombre'      => __( 'Verde Ecológico', 'flavor-chat-ia' ),
                'descripcion' => __( 'Esquema natural y sostenible', 'flavor-chat-ia' ),
                'colores'     => array(
                    'primary'    => '#22c55e',
                    'secondary'  => '#15803d',
                    'accent'     => '#4ade80',
                    'background' => '#f0fdf4',
                    'surface'    => '#ffffff',
                    'text'       => '#14532d',
                    'text-muted' => '#166534',
                    'border'     => '#bbf7d0',
                    'success'    => '#22c55e',
                    'warning'    => '#eab308',
                    'error'      => '#dc2626',
                ),
                'tipografia'  => array(
                    'font-family-heading' => "'Poppins', sans-serif",
                    'font-family-body'    => "'Open Sans', sans-serif",
                    'font-size-base'      => '16px',
                    'line-height-base'    => '1.7',
                ),
                'espaciado'   => array(
                    'spacing-unit' => '8px',
                    'border-radius' => '12px',
                ),
                'es_sistema'  => true,
            ),
            'warm-orange' => array(
                'id'          => 'warm-orange',
                'nombre'      => __( 'Naranja Cálido', 'flavor-chat-ia' ),
                'descripcion' => __( 'Esquema energético y acogedor', 'flavor-chat-ia' ),
                'colores'     => array(
                    'primary'    => '#f97316',
                    'secondary'  => '#c2410c',
                    'accent'     => '#fb923c',
                    'background' => '#fffbeb',
                    'surface'    => '#ffffff',
                    'text'       => '#431407',
                    'text-muted' => '#9a3412',
                    'border'     => '#fed7aa',
                    'success'    => '#16a34a',
                    'warning'    => '#f97316',
                    'error'      => '#dc2626',
                ),
                'tipografia'  => array(
                    'font-family-heading' => "'Montserrat', sans-serif",
                    'font-family-body'    => "'Lato', sans-serif",
                    'font-size-base'      => '16px',
                    'line-height-base'    => '1.6',
                ),
                'espaciado'   => array(
                    'spacing-unit' => '8px',
                    'border-radius' => '6px',
                ),
                'es_sistema'  => true,
            ),
            'elegant-purple' => array(
                'id'          => 'elegant-purple',
                'nombre'      => __( 'Púrpura Elegante', 'flavor-chat-ia' ),
                'descripcion' => __( 'Esquema sofisticado y creativo', 'flavor-chat-ia' ),
                'colores'     => array(
                    'primary'    => '#8b5cf6',
                    'secondary'  => '#6d28d9',
                    'accent'     => '#a78bfa',
                    'background' => '#faf5ff',
                    'surface'    => '#ffffff',
                    'text'       => '#3b0764',
                    'text-muted' => '#7c3aed',
                    'border'     => '#ddd6fe',
                    'success'    => '#22c55e',
                    'warning'    => '#f59e0b',
                    'error'      => '#ef4444',
                ),
                'tipografia'  => array(
                    'font-family-heading' => "'Playfair Display', serif",
                    'font-family-body'    => "'Source Sans Pro', sans-serif",
                    'font-size-base'      => '17px',
                    'line-height-base'    => '1.65',
                ),
                'espaciado'   => array(
                    'spacing-unit' => '8px',
                    'border-radius' => '16px',
                ),
                'es_sistema'  => true,
            ),
            'antifa-red' => array(
                'id'          => 'antifa-red',
                'nombre'      => __( 'Rojo Antifa', 'flavor-chat-ia' ),
                'descripcion' => __( 'Esquema activista y comprometido', 'flavor-chat-ia' ),
                'colores'     => array(
                    'primary'    => '#dc2626',
                    'secondary'  => '#1f2937',
                    'accent'     => '#ef4444',
                    'background' => '#111827',
                    'surface'    => '#1f2937',
                    'text'       => '#f9fafb',
                    'text-muted' => '#9ca3af',
                    'border'     => '#374151',
                    'success'    => '#22c55e',
                    'warning'    => '#f59e0b',
                    'error'      => '#ef4444',
                ),
                'tipografia'  => array(
                    'font-family-heading' => "'Bebas Neue', sans-serif",
                    'font-family-body'    => "'Roboto', sans-serif",
                    'font-size-base'      => '16px',
                    'line-height-base'    => '1.6',
                ),
                'espaciado'   => array(
                    'spacing-unit' => '8px',
                    'border-radius' => '4px',
                ),
                'es_sistema'  => true,
            ),
            'dark-mode' => array(
                'id'          => 'dark-mode',
                'nombre'      => __( 'Modo Oscuro', 'flavor-chat-ia' ),
                'descripcion' => __( 'Esquema oscuro para reducir fatiga visual', 'flavor-chat-ia' ),
                'colores'     => array(
                    'primary'    => '#6366f1',
                    'secondary'  => '#4f46e5',
                    'accent'     => '#818cf8',
                    'background' => '#0f172a',
                    'surface'    => '#1e293b',
                    'text'       => '#f1f5f9',
                    'text-muted' => '#94a3b8',
                    'border'     => '#334155',
                    'success'    => '#4ade80',
                    'warning'    => '#fbbf24',
                    'error'      => '#f87171',
                ),
                'tipografia'  => array(
                    'font-family-heading' => "'Inter', sans-serif",
                    'font-family-body'    => "'Inter', sans-serif",
                    'font-size-base'      => '16px',
                    'line-height-base'    => '1.6',
                ),
                'espaciado'   => array(
                    'spacing-unit' => '8px',
                    'border-radius' => '8px',
                ),
                'es_sistema'  => true,
            ),
            'minimal-mono' => array(
                'id'          => 'minimal-mono',
                'nombre'      => __( 'Minimalista Monocromático', 'flavor-chat-ia' ),
                'descripcion' => __( 'Esquema limpio en blanco y negro', 'flavor-chat-ia' ),
                'colores'     => array(
                    'primary'    => '#18181b',
                    'secondary'  => '#27272a',
                    'accent'     => '#3f3f46',
                    'background' => '#ffffff',
                    'surface'    => '#fafafa',
                    'text'       => '#18181b',
                    'text-muted' => '#71717a',
                    'border'     => '#e4e4e7',
                    'success'    => '#22c55e',
                    'warning'    => '#f59e0b',
                    'error'      => '#ef4444',
                ),
                'tipografia'  => array(
                    'font-family-heading' => "'Space Grotesk', sans-serif",
                    'font-family-body'    => "'IBM Plex Sans', sans-serif",
                    'font-size-base'      => '16px',
                    'line-height-base'    => '1.7',
                ),
                'espaciado'   => array(
                    'spacing-unit' => '8px',
                    'border-radius' => '0px',
                ),
                'es_sistema'  => true,
            ),
        );

        /**
         * Filtro para modificar los presets predefinidos
         *
         * @param array $presets_predefinidos Array de presets del sistema
         */
        $this->presets_predefinidos = apply_filters( 'flavor_vbp_presets_predefinidos', $this->presets_predefinidos );
    }

    /**
     * Registra los hooks de WordPress
     *
     * @return void
     */
    private function registrar_hooks() {
        add_action( 'rest_api_init', array( $this, 'registrar_endpoints' ) );
        add_action( 'wp_head', array( $this, 'inyectar_variables_css' ), 5 );
        add_action( 'admin_head', array( $this, 'inyectar_variables_css_admin' ), 5 );
        add_filter( 'body_class', array( $this, 'agregar_clase_tema_body' ) );
    }

    /**
     * Agrega la clase del tema activo al body
     *
     * @param array $classes Clases actuales del body
     * @return array Clases modificadas
     */
    public function agregar_clase_tema_body( $classes ) {
        $preset_activo_id = get_option( self::OPTION_ACTIVE_PRESET, 'modern-blue' );
        if ( $preset_activo_id ) {
            $classes[] = 'vbp-theme-' . sanitize_html_class( $preset_activo_id );
        }
        return $classes;
    }

    /**
     * Registra los endpoints de la REST API
     *
     * @return void
     */
    public function registrar_endpoints() {
        // Obtener todos los presets
        register_rest_route( self::NAMESPACE, '/presets', array(
            'methods'             => WP_REST_Server::READABLE,
            'callback'            => array( $this, 'api_obtener_presets' ),
            'permission_callback' => array( $this, 'verificar_permisos_lectura' ),
        ) );

        // Obtener preset específico
        register_rest_route( self::NAMESPACE, '/presets/(?P<id>[a-zA-Z0-9_-]+)', array(
            'methods'             => WP_REST_Server::READABLE,
            'callback'            => array( $this, 'api_obtener_preset' ),
            'permission_callback' => array( $this, 'verificar_permisos_lectura' ),
            'args'                => array(
                'id' => array(
                    'required'          => true,
                    'sanitize_callback' => 'sanitize_text_field',
                ),
            ),
        ) );

        // Crear preset personalizado
        register_rest_route( self::NAMESPACE, '/presets', array(
            'methods'             => WP_REST_Server::CREATABLE,
            'callback'            => array( $this, 'api_crear_preset' ),
            'permission_callback' => array( $this, 'verificar_permisos_escritura' ),
            'args'                => array(
                'nombre'  => array( 'required' => true ),
                'colores' => array( 'required' => true ),
            ),
        ) );

        // Actualizar preset personalizado
        register_rest_route( self::NAMESPACE, '/presets/(?P<id>[a-zA-Z0-9_-]+)', array(
            'methods'             => WP_REST_Server::EDITABLE,
            'callback'            => array( $this, 'api_actualizar_preset' ),
            'permission_callback' => array( $this, 'verificar_permisos_escritura' ),
        ) );

        // Eliminar preset personalizado
        register_rest_route( self::NAMESPACE, '/presets/(?P<id>[a-zA-Z0-9_-]+)', array(
            'methods'             => WP_REST_Server::DELETABLE,
            'callback'            => array( $this, 'api_eliminar_preset' ),
            'permission_callback' => array( $this, 'verificar_permisos_escritura' ),
        ) );

        // Aplicar preset
        register_rest_route( self::NAMESPACE, '/presets/(?P<id>[a-zA-Z0-9_-]+)/apply', array(
            'methods'             => WP_REST_Server::CREATABLE,
            'callback'            => array( $this, 'api_aplicar_preset' ),
            'permission_callback' => array( $this, 'verificar_permisos_escritura' ),
        ) );

        // Obtener preset activo
        register_rest_route( self::NAMESPACE, '/presets/active', array(
            'methods'             => WP_REST_Server::READABLE,
            'callback'            => array( $this, 'api_obtener_preset_activo' ),
            'permission_callback' => array( $this, 'verificar_permisos_lectura' ),
        ) );

        // Generar CSS de un preset
        register_rest_route( self::NAMESPACE, '/presets/(?P<id>[a-zA-Z0-9_-]+)/css', array(
            'methods'             => WP_REST_Server::READABLE,
            'callback'            => array( $this, 'api_obtener_css_preset' ),
            'permission_callback' => array( $this, 'verificar_permisos_lectura' ),
        ) );
    }

    /**
     * Verifica permisos de lectura
     *
     * @return bool
     */
    public function verificar_permisos_lectura() {
        return true; // Los presets son públicos para lectura
    }

    /**
     * Verifica permisos de escritura
     *
     * @return bool
     */
    public function verificar_permisos_escritura() {
        return current_user_can( 'edit_posts' );
    }

    /**
     * API: Obtiene todos los presets
     *
     * @param WP_REST_Request $request Petición REST
     * @return WP_REST_Response
     */
    public function api_obtener_presets( $request ) {
        $todos_los_presets = $this->obtener_todos_los_presets();
        $preset_activo_id = get_option( self::OPTION_ACTIVE_PRESET, 'modern-blue' );

        return rest_ensure_response( array(
            'success'       => true,
            'presets'       => array_values( $todos_los_presets ),
            'preset_activo' => $preset_activo_id,
        ) );
    }

    /**
     * API: Obtiene un preset específico
     *
     * @param WP_REST_Request $request Petición REST
     * @return WP_REST_Response|WP_Error
     */
    public function api_obtener_preset( $request ) {
        $preset_id = $request->get_param( 'id' );
        $preset = $this->obtener_preset( $preset_id );

        if ( ! $preset ) {
            return new WP_Error(
                'preset_no_encontrado',
                __( 'El preset no existe', 'flavor-chat-ia' ),
                array( 'status' => 404 )
            );
        }

        return rest_ensure_response( array(
            'success' => true,
            'preset'  => $preset,
        ) );
    }

    /**
     * API: Crea un preset personalizado
     *
     * @param WP_REST_Request $request Petición REST
     * @return WP_REST_Response|WP_Error
     */
    public function api_crear_preset( $request ) {
        $nombre = sanitize_text_field( $request->get_param( 'nombre' ) );
        $descripcion = sanitize_text_field( $request->get_param( 'descripcion' ) ?? '' );
        $colores = $request->get_param( 'colores' );
        $tipografia = $request->get_param( 'tipografia' ) ?? array();
        $espaciado = $request->get_param( 'espaciado' ) ?? array();

        // Generar ID único
        $preset_id = sanitize_title( $nombre ) . '-' . substr( md5( time() ), 0, 6 );

        // Sanitizar colores
        $colores_sanitizados = $this->sanitizar_colores( $colores );

        $preset_nuevo = array(
            'id'          => $preset_id,
            'nombre'      => $nombre,
            'descripcion' => $descripcion,
            'colores'     => $colores_sanitizados,
            'tipografia'  => $this->sanitizar_tipografia( $tipografia ),
            'espaciado'   => $this->sanitizar_espaciado( $espaciado ),
            'es_sistema'  => false,
            'creado'      => current_time( 'mysql' ),
            'modificado'  => current_time( 'mysql' ),
        );

        // Guardar
        $presets_personalizados = get_option( self::OPTION_CUSTOM_PRESETS, array() );
        $presets_personalizados[ $preset_id ] = $preset_nuevo;
        update_option( self::OPTION_CUSTOM_PRESETS, $presets_personalizados );

        return rest_ensure_response( array(
            'success' => true,
            'mensaje' => __( 'Preset creado correctamente', 'flavor-chat-ia' ),
            'preset'  => $preset_nuevo,
        ) );
    }

    /**
     * API: Actualiza un preset personalizado
     *
     * @param WP_REST_Request $request Petición REST
     * @return WP_REST_Response|WP_Error
     */
    public function api_actualizar_preset( $request ) {
        $preset_id = $request->get_param( 'id' );

        // Verificar que no es un preset del sistema
        if ( isset( $this->presets_predefinidos[ $preset_id ] ) ) {
            return new WP_Error(
                'preset_sistema',
                __( 'No se pueden modificar los presets del sistema', 'flavor-chat-ia' ),
                array( 'status' => 403 )
            );
        }

        $presets_personalizados = get_option( self::OPTION_CUSTOM_PRESETS, array() );

        if ( ! isset( $presets_personalizados[ $preset_id ] ) ) {
            return new WP_Error(
                'preset_no_encontrado',
                __( 'El preset no existe', 'flavor-chat-ia' ),
                array( 'status' => 404 )
            );
        }

        $preset_actual = $presets_personalizados[ $preset_id ];

        // Actualizar campos proporcionados
        if ( $request->has_param( 'nombre' ) ) {
            $preset_actual['nombre'] = sanitize_text_field( $request->get_param( 'nombre' ) );
        }
        if ( $request->has_param( 'descripcion' ) ) {
            $preset_actual['descripcion'] = sanitize_text_field( $request->get_param( 'descripcion' ) );
        }
        if ( $request->has_param( 'colores' ) ) {
            $preset_actual['colores'] = $this->sanitizar_colores( $request->get_param( 'colores' ) );
        }
        if ( $request->has_param( 'tipografia' ) ) {
            $preset_actual['tipografia'] = $this->sanitizar_tipografia( $request->get_param( 'tipografia' ) );
        }
        if ( $request->has_param( 'espaciado' ) ) {
            $preset_actual['espaciado'] = $this->sanitizar_espaciado( $request->get_param( 'espaciado' ) );
        }

        $preset_actual['modificado'] = current_time( 'mysql' );

        $presets_personalizados[ $preset_id ] = $preset_actual;
        update_option( self::OPTION_CUSTOM_PRESETS, $presets_personalizados );

        return rest_ensure_response( array(
            'success' => true,
            'mensaje' => __( 'Preset actualizado correctamente', 'flavor-chat-ia' ),
            'preset'  => $preset_actual,
        ) );
    }

    /**
     * API: Elimina un preset personalizado
     *
     * @param WP_REST_Request $request Petición REST
     * @return WP_REST_Response|WP_Error
     */
    public function api_eliminar_preset( $request ) {
        $preset_id = $request->get_param( 'id' );

        // Verificar que no es un preset del sistema
        if ( isset( $this->presets_predefinidos[ $preset_id ] ) ) {
            return new WP_Error(
                'preset_sistema',
                __( 'No se pueden eliminar los presets del sistema', 'flavor-chat-ia' ),
                array( 'status' => 403 )
            );
        }

        $presets_personalizados = get_option( self::OPTION_CUSTOM_PRESETS, array() );

        if ( ! isset( $presets_personalizados[ $preset_id ] ) ) {
            return new WP_Error(
                'preset_no_encontrado',
                __( 'El preset no existe', 'flavor-chat-ia' ),
                array( 'status' => 404 )
            );
        }

        unset( $presets_personalizados[ $preset_id ] );
        update_option( self::OPTION_CUSTOM_PRESETS, $presets_personalizados );

        // Si era el preset activo, volver al predeterminado
        if ( get_option( self::OPTION_ACTIVE_PRESET ) === $preset_id ) {
            update_option( self::OPTION_ACTIVE_PRESET, 'modern-blue' );
        }

        return rest_ensure_response( array(
            'success' => true,
            'mensaje' => __( 'Preset eliminado correctamente', 'flavor-chat-ia' ),
        ) );
    }

    /**
     * API: Aplica un preset como activo
     *
     * @param WP_REST_Request $request Petición REST
     * @return WP_REST_Response|WP_Error
     */
    public function api_aplicar_preset( $request ) {
        $preset_id = $request->get_param( 'id' );
        $preset = $this->obtener_preset( $preset_id );

        if ( ! $preset ) {
            return new WP_Error(
                'preset_no_encontrado',
                __( 'El preset no existe', 'flavor-chat-ia' ),
                array( 'status' => 404 )
            );
        }

        update_option( self::OPTION_ACTIVE_PRESET, $preset_id );

        return rest_ensure_response( array(
            'success' => true,
            'mensaje' => sprintf(
                __( 'Preset "%s" aplicado correctamente', 'flavor-chat-ia' ),
                $preset['nombre']
            ),
            'preset'  => $preset,
            'css'     => $this->generar_css_variables( $preset ),
        ) );
    }

    /**
     * API: Obtiene el preset activo
     *
     * @param WP_REST_Request $request Petición REST
     * @return WP_REST_Response
     */
    public function api_obtener_preset_activo( $request ) {
        $preset_activo_id = get_option( self::OPTION_ACTIVE_PRESET, 'modern-blue' );
        $preset = $this->obtener_preset( $preset_activo_id );

        if ( ! $preset ) {
            $preset = $this->presets_predefinidos['modern-blue'];
        }

        return rest_ensure_response( array(
            'success' => true,
            'preset'  => $preset,
            'css'     => $this->generar_css_variables( $preset ),
        ) );
    }

    /**
     * API: Genera el CSS de un preset
     *
     * @param WP_REST_Request $request Petición REST
     * @return WP_REST_Response|WP_Error
     */
    public function api_obtener_css_preset( $request ) {
        $preset_id = $request->get_param( 'id' );
        $preset = $this->obtener_preset( $preset_id );

        if ( ! $preset ) {
            return new WP_Error(
                'preset_no_encontrado',
                __( 'El preset no existe', 'flavor-chat-ia' ),
                array( 'status' => 404 )
            );
        }

        return rest_ensure_response( array(
            'success' => true,
            'css'     => $this->generar_css_variables( $preset ),
        ) );
    }

    /**
     * Obtiene todos los presets (sistema + personalizados)
     *
     * @return array
     */
    public function obtener_todos_los_presets() {
        $presets_personalizados = get_option( self::OPTION_CUSTOM_PRESETS, array() );
        return array_merge( $this->presets_predefinidos, $presets_personalizados );
    }

    /**
     * Obtiene un preset por su ID
     *
     * @param string $preset_id ID del preset
     * @return array|null
     */
    public function obtener_preset( $preset_id ) {
        // Primero buscar en presets del sistema
        if ( isset( $this->presets_predefinidos[ $preset_id ] ) ) {
            return $this->presets_predefinidos[ $preset_id ];
        }

        // Luego en personalizados
        $presets_personalizados = get_option( self::OPTION_CUSTOM_PRESETS, array() );
        if ( isset( $presets_personalizados[ $preset_id ] ) ) {
            return $presets_personalizados[ $preset_id ];
        }

        return null;
    }

    /**
     * Obtiene el preset activo actual
     *
     * @return array
     */
    public function obtener_preset_activo() {
        $preset_activo_id = get_option( self::OPTION_ACTIVE_PRESET, 'modern-blue' );
        $preset = $this->obtener_preset( $preset_activo_id );

        if ( ! $preset ) {
            return $this->presets_predefinidos['modern-blue'];
        }

        return $preset;
    }

    /**
     * Genera las variables CSS para un preset
     *
     * @param array $preset Datos del preset
     * @return string CSS generado
     */
    public function generar_css_variables( $preset ) {
        $css = ":root {\n";

        // Variables de colores
        if ( ! empty( $preset['colores'] ) ) {
            foreach ( $preset['colores'] as $nombre_variable => $valor ) {
                $css .= "    --flavor-{$nombre_variable}: {$valor};\n";
            }

            // Generar variantes automáticas (más claras y más oscuras)
            if ( isset( $preset['colores']['primary'] ) ) {
                $primary_claro = $this->ajustar_luminosidad( $preset['colores']['primary'], 20 );
                $primary_oscuro = $this->ajustar_luminosidad( $preset['colores']['primary'], -20 );
                $css .= "    --flavor-primary-light: {$primary_claro};\n";
                $css .= "    --flavor-primary-dark: {$primary_oscuro};\n";
            }
            if ( isset( $preset['colores']['secondary'] ) ) {
                $secondary_claro = $this->ajustar_luminosidad( $preset['colores']['secondary'], 20 );
                $secondary_oscuro = $this->ajustar_luminosidad( $preset['colores']['secondary'], -20 );
                $css .= "    --flavor-secondary-light: {$secondary_claro};\n";
                $css .= "    --flavor-secondary-dark: {$secondary_oscuro};\n";
            }
        }

        // Variables de tipografía
        if ( ! empty( $preset['tipografia'] ) ) {
            foreach ( $preset['tipografia'] as $nombre_variable => $valor ) {
                $css .= "    --flavor-{$nombre_variable}: {$valor};\n";
            }
        }

        // Variables de espaciado
        if ( ! empty( $preset['espaciado'] ) ) {
            foreach ( $preset['espaciado'] as $nombre_variable => $valor ) {
                $css .= "    --flavor-{$nombre_variable}: {$valor};\n";
            }

            // Generar escala de espaciado si hay spacing-unit
            if ( isset( $preset['espaciado']['spacing-unit'] ) ) {
                $unidad = intval( $preset['espaciado']['spacing-unit'] );
                $css .= "    --flavor-spacing-xs: " . ( $unidad * 0.5 ) . "px;\n";
                $css .= "    --flavor-spacing-sm: " . ( $unidad ) . "px;\n";
                $css .= "    --flavor-spacing-md: " . ( $unidad * 2 ) . "px;\n";
                $css .= "    --flavor-spacing-lg: " . ( $unidad * 3 ) . "px;\n";
                $css .= "    --flavor-spacing-xl: " . ( $unidad * 4 ) . "px;\n";
                $css .= "    --flavor-spacing-2xl: " . ( $unidad * 6 ) . "px;\n";
            }
        }

        $css .= "}\n";

        return $css;
    }

    /**
     * Inyecta las variables CSS en el frontend
     *
     * @return void
     */
    public function inyectar_variables_css() {
        $preset_activo = $this->obtener_preset_activo();
        $css = $this->generar_css_variables( $preset_activo );

        echo "<style id=\"flavor-vbp-preset-variables\">\n{$css}</style>\n";
    }

    /**
     * Inyecta las variables CSS en el admin (editor)
     *
     * @return void
     */
    public function inyectar_variables_css_admin() {
        // Solo en páginas del editor VBP
        $pantalla_actual = get_current_screen();
        if ( $pantalla_actual && strpos( $pantalla_actual->id, 'flavor' ) !== false ) {
            $this->inyectar_variables_css();
        }
    }

    /**
     * Sanitiza un array de colores
     *
     * @param array $colores Array de colores
     * @return array
     */
    private function sanitizar_colores( $colores ) {
        $colores_sanitizados = array();
        $claves_permitidas = array(
            'primary', 'secondary', 'accent', 'background', 'surface',
            'text', 'text-muted', 'border', 'success', 'warning', 'error',
        );

        foreach ( $colores as $clave => $valor ) {
            if ( in_array( $clave, $claves_permitidas, true ) ) {
                $colores_sanitizados[ $clave ] = sanitize_hex_color( $valor ) ?: $valor;
            }
        }

        return $colores_sanitizados;
    }

    /**
     * Sanitiza configuración de tipografía
     *
     * @param array $tipografia Array de tipografía
     * @return array
     */
    private function sanitizar_tipografia( $tipografia ) {
        $tipografia_sanitizada = array();
        $claves_permitidas = array(
            'font-family-heading', 'font-family-body',
            'font-size-base', 'line-height-base',
        );

        foreach ( $tipografia as $clave => $valor ) {
            if ( in_array( $clave, $claves_permitidas, true ) ) {
                $tipografia_sanitizada[ $clave ] = sanitize_text_field( $valor );
            }
        }

        return $tipografia_sanitizada;
    }

    /**
     * Sanitiza configuración de espaciado
     *
     * @param array $espaciado Array de espaciado
     * @return array
     */
    private function sanitizar_espaciado( $espaciado ) {
        $espaciado_sanitizado = array();
        $claves_permitidas = array( 'spacing-unit', 'border-radius' );

        foreach ( $espaciado as $clave => $valor ) {
            if ( in_array( $clave, $claves_permitidas, true ) ) {
                $espaciado_sanitizado[ $clave ] = sanitize_text_field( $valor );
            }
        }

        return $espaciado_sanitizado;
    }

    /**
     * Ajusta la luminosidad de un color hexadecimal
     *
     * @param string $color_hex Color en formato hexadecimal
     * @param int    $porcentaje Porcentaje de ajuste (-100 a 100)
     * @return string Color ajustado
     */
    private function ajustar_luminosidad( $color_hex, $porcentaje ) {
        $color_hex = ltrim( $color_hex, '#' );

        // Convertir a RGB
        $rojo = hexdec( substr( $color_hex, 0, 2 ) );
        $verde = hexdec( substr( $color_hex, 2, 2 ) );
        $azul = hexdec( substr( $color_hex, 4, 2 ) );

        // Ajustar
        $factor = 1 + ( $porcentaje / 100 );
        $rojo = min( 255, max( 0, round( $rojo * $factor ) ) );
        $verde = min( 255, max( 0, round( $verde * $factor ) ) );
        $azul = min( 255, max( 0, round( $azul * $factor ) ) );

        return sprintf( '#%02x%02x%02x', $rojo, $verde, $azul );
    }

    /**
     * Obtiene los presets predefinidos del sistema
     *
     * @return array
     */
    public function obtener_presets_sistema() {
        return $this->presets_predefinidos;
    }

    /**
     * Obtiene solo los presets personalizados
     *
     * @return array
     */
    public function obtener_presets_personalizados() {
        return get_option( self::OPTION_CUSTOM_PRESETS, array() );
    }

    /**
     * Duplica un preset existente
     *
     * @param string $preset_id ID del preset a duplicar
     * @param string $nuevo_nombre Nombre para el duplicado
     * @return array|WP_Error
     */
    public function duplicar_preset( $preset_id, $nuevo_nombre ) {
        $preset_original = $this->obtener_preset( $preset_id );

        if ( ! $preset_original ) {
            return new WP_Error(
                'preset_no_encontrado',
                __( 'El preset original no existe', 'flavor-chat-ia' )
            );
        }

        // Crear ID único para el duplicado
        $nuevo_id = sanitize_title( $nuevo_nombre ) . '-' . substr( md5( time() ), 0, 6 );

        $preset_duplicado = array(
            'id'          => $nuevo_id,
            'nombre'      => $nuevo_nombre,
            'descripcion' => sprintf(
                __( 'Copia de %s', 'flavor-chat-ia' ),
                $preset_original['nombre']
            ),
            'colores'     => $preset_original['colores'],
            'tipografia'  => $preset_original['tipografia'] ?? array(),
            'espaciado'   => $preset_original['espaciado'] ?? array(),
            'es_sistema'  => false,
            'creado'      => current_time( 'mysql' ),
            'modificado'  => current_time( 'mysql' ),
        );

        // Guardar
        $presets_personalizados = get_option( self::OPTION_CUSTOM_PRESETS, array() );
        $presets_personalizados[ $nuevo_id ] = $preset_duplicado;
        update_option( self::OPTION_CUSTOM_PRESETS, $presets_personalizados );

        return $preset_duplicado;
    }

    /**
     * Exporta un preset a JSON
     *
     * @param string $preset_id ID del preset
     * @return string|WP_Error JSON del preset
     */
    public function exportar_preset( $preset_id ) {
        $preset = $this->obtener_preset( $preset_id );

        if ( ! $preset ) {
            return new WP_Error(
                'preset_no_encontrado',
                __( 'El preset no existe', 'flavor-chat-ia' )
            );
        }

        // Eliminar campos internos
        $preset_exportable = $preset;
        unset( $preset_exportable['es_sistema'] );

        return wp_json_encode( $preset_exportable, JSON_PRETTY_PRINT );
    }

    /**
     * Importa un preset desde JSON
     *
     * @param string $json_preset JSON del preset
     * @return array|WP_Error
     */
    public function importar_preset( $json_preset ) {
        $preset_datos = json_decode( $json_preset, true );

        if ( json_last_error() !== JSON_ERROR_NONE ) {
            return new WP_Error(
                'json_invalido',
                __( 'El JSON del preset no es válido', 'flavor-chat-ia' )
            );
        }

        // Validar campos requeridos
        if ( empty( $preset_datos['nombre'] ) || empty( $preset_datos['colores'] ) ) {
            return new WP_Error(
                'datos_incompletos',
                __( 'El preset debe tener nombre y colores', 'flavor-chat-ia' )
            );
        }

        // Generar nuevo ID
        $nuevo_id = sanitize_title( $preset_datos['nombre'] ) . '-imported-' . substr( md5( time() ), 0, 6 );

        $preset_nuevo = array(
            'id'          => $nuevo_id,
            'nombre'      => sanitize_text_field( $preset_datos['nombre'] ),
            'descripcion' => sanitize_text_field( $preset_datos['descripcion'] ?? '' ),
            'colores'     => $this->sanitizar_colores( $preset_datos['colores'] ),
            'tipografia'  => $this->sanitizar_tipografia( $preset_datos['tipografia'] ?? array() ),
            'espaciado'   => $this->sanitizar_espaciado( $preset_datos['espaciado'] ?? array() ),
            'es_sistema'  => false,
            'creado'      => current_time( 'mysql' ),
            'modificado'  => current_time( 'mysql' ),
        );

        // Guardar
        $presets_personalizados = get_option( self::OPTION_CUSTOM_PRESETS, array() );
        $presets_personalizados[ $nuevo_id ] = $preset_nuevo;
        update_option( self::OPTION_CUSTOM_PRESETS, $presets_personalizados );

        return $preset_nuevo;
    }
}

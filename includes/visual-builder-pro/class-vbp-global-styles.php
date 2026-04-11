<?php
/**
 * Visual Builder Pro - Global Styles
 *
 * Sistema de estilos globales reutilizables para VBP.
 * Permite definir clases CSS reutilizables que se aplican a elementos.
 *
 * DIFERENCIA CON DESIGN TOKENS:
 * - Design Tokens: Variables CSS (colores, espaciados, tipografía)
 * - Global Styles: Clases CSS completas que combinan múltiples propiedades
 *
 * @package Flavor_Platform
 * @subpackage Visual_Builder_Pro
 * @since 2.3.0
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Clase para gestionar estilos globales reutilizables
 *
 * @since 2.3.0
 */
class Flavor_VBP_Global_Styles {

    /**
     * Namespace de la API REST
     *
     * @var string
     */
    const REST_NAMESPACE = 'flavor-vbp/v1';

    /**
     * Option name para almacenar los estilos globales
     *
     * @var string
     */
    const OPTION_NAME = 'vbp_global_styles';

    /**
     * Categorias de estilos disponibles
     *
     * @var array
     */
    const CATEGORIES = array(
        'typography'  => array(
            'id'    => 'typography',
            'name'  => 'Tipografía',
            'icon'  => 'T',
            'order' => 1,
        ),
        'buttons'     => array(
            'id'    => 'buttons',
            'name'  => 'Botones',
            'icon'  => '▢',
            'order' => 2,
        ),
        'containers'  => array(
            'id'    => 'containers',
            'name'  => 'Contenedores',
            'icon'  => '□',
            'order' => 3,
        ),
        'custom'      => array(
            'id'    => 'custom',
            'name'  => 'Personalizados',
            'icon'  => '✦',
            'order' => 4,
        ),
    );

    /**
     * Instancia singleton
     *
     * @var Flavor_VBP_Global_Styles|null
     */
    private static $instancia = null;

    /**
     * Cache de estilos cargados
     *
     * @var array|null
     */
    private $styles_cache = null;

    /**
     * Obtiene la instancia singleton
     *
     * @return Flavor_VBP_Global_Styles
     */
    public static function get_instance() {
        if ( null === self::$instancia ) {
            self::$instancia = new self();
        }
        return self::$instancia;
    }

    /**
     * Constructor privado
     */
    private function __construct() {
        add_action( 'rest_api_init', array( $this, 'registrar_rutas_rest' ) );
        add_action( 'init', array( $this, 'inicializar_estilos_predefinidos' ) );

        // Inyectar CSS generado en el frontend
        add_action( 'wp_head', array( $this, 'inyectar_css_frontend' ), 100 );
        // Inyectar CSS en el editor
        add_action( 'admin_head', array( $this, 'inyectar_css_editor' ), 100 );
    }

    /**
     * Registra las rutas REST para global styles
     */
    public function registrar_rutas_rest() {
        // Obtener todos los estilos globales
        register_rest_route(
            self::REST_NAMESPACE,
            '/global-styles',
            array(
                array(
                    'methods'             => WP_REST_Server::READABLE,
                    'callback'            => array( $this, 'rest_obtener_estilos' ),
                    'permission_callback' => array( $this, 'verificar_permiso_lectura' ),
                ),
                array(
                    'methods'             => WP_REST_Server::CREATABLE,
                    'callback'            => array( $this, 'rest_crear_estilo' ),
                    'permission_callback' => array( $this, 'verificar_permiso_escritura' ),
                ),
            )
        );

        // Operaciones CRUD en un estilo específico
        register_rest_route(
            self::REST_NAMESPACE,
            '/global-styles/(?P<id>[a-zA-Z0-9_]+)',
            array(
                array(
                    'methods'             => WP_REST_Server::READABLE,
                    'callback'            => array( $this, 'rest_obtener_estilo' ),
                    'permission_callback' => array( $this, 'verificar_permiso_lectura' ),
                ),
                array(
                    'methods'             => WP_REST_Server::EDITABLE,
                    'callback'            => array( $this, 'rest_actualizar_estilo' ),
                    'permission_callback' => array( $this, 'verificar_permiso_escritura' ),
                ),
                array(
                    'methods'             => WP_REST_Server::DELETABLE,
                    'callback'            => array( $this, 'rest_eliminar_estilo' ),
                    'permission_callback' => array( $this, 'verificar_permiso_escritura' ),
                ),
            )
        );

        // Obtener categorías disponibles
        register_rest_route(
            self::REST_NAMESPACE,
            '/global-styles/categories',
            array(
                'methods'             => WP_REST_Server::READABLE,
                'callback'            => array( $this, 'rest_obtener_categorias' ),
                'permission_callback' => '__return_true',
            )
        );

        // Generar CSS (para debug/export)
        register_rest_route(
            self::REST_NAMESPACE,
            '/global-styles/css',
            array(
                'methods'             => WP_REST_Server::READABLE,
                'callback'            => array( $this, 'rest_obtener_css' ),
                'permission_callback' => '__return_true',
            )
        );
    }

    /**
     * Verificar permiso de lectura
     *
     * @return bool
     */
    public function verificar_permiso_lectura() {
        return current_user_can( 'edit_posts' );
    }

    /**
     * Verificar permiso de escritura
     *
     * @return bool
     */
    public function verificar_permiso_escritura() {
        return current_user_can( 'edit_posts' );
    }

    /**
     * Inicializa los estilos predefinidos si no existen
     */
    public function inicializar_estilos_predefinidos() {
        $estilos_existentes = get_option( self::OPTION_NAME, array() );

        // Si ya hay estilos, no inicializar
        if ( ! empty( $estilos_existentes ) ) {
            return;
        }

        $estilos_predefinidos = $this->obtener_estilos_predefinidos();
        update_option( self::OPTION_NAME, $estilos_predefinidos );
    }

    /**
     * Obtiene los estilos predefinidos
     *
     * @return array
     */
    private function obtener_estilos_predefinidos() {
        return array(
            // Tipografía
            array(
                'id'          => 'gs_heading_1',
                'name'        => 'Heading 1',
                'slug'        => 'heading-1',
                'category'    => 'typography',
                'description' => 'Título principal de sección',
                'isDefault'   => true,
                'styles'      => array(
                    'fontSize'     => '48px',
                    'fontWeight'   => '700',
                    'lineHeight'   => '1.2',
                    'color'        => 'var(--flavor-text)',
                    'marginBottom' => '24px',
                ),
                'createdAt'   => current_time( 'mysql' ),
                'updatedAt'   => current_time( 'mysql' ),
            ),
            array(
                'id'          => 'gs_heading_2',
                'name'        => 'Heading 2',
                'slug'        => 'heading-2',
                'category'    => 'typography',
                'description' => 'Subtítulo de sección',
                'isDefault'   => true,
                'styles'      => array(
                    'fontSize'     => '36px',
                    'fontWeight'   => '600',
                    'lineHeight'   => '1.3',
                    'color'        => 'var(--flavor-text)',
                    'marginBottom' => '20px',
                ),
                'createdAt'   => current_time( 'mysql' ),
                'updatedAt'   => current_time( 'mysql' ),
            ),
            array(
                'id'          => 'gs_heading_3',
                'name'        => 'Heading 3',
                'slug'        => 'heading-3',
                'category'    => 'typography',
                'description' => 'Título de subsección',
                'isDefault'   => true,
                'styles'      => array(
                    'fontSize'     => '28px',
                    'fontWeight'   => '600',
                    'lineHeight'   => '1.3',
                    'color'        => 'var(--flavor-text)',
                    'marginBottom' => '16px',
                ),
                'createdAt'   => current_time( 'mysql' ),
                'updatedAt'   => current_time( 'mysql' ),
            ),
            array(
                'id'          => 'gs_body_text',
                'name'        => 'Body Text',
                'slug'        => 'body-text',
                'category'    => 'typography',
                'description' => 'Texto de cuerpo estándar',
                'isDefault'   => true,
                'styles'      => array(
                    'fontSize'     => '16px',
                    'fontWeight'   => '400',
                    'lineHeight'   => '1.6',
                    'color'        => 'var(--flavor-text)',
                ),
                'createdAt'   => current_time( 'mysql' ),
                'updatedAt'   => current_time( 'mysql' ),
            ),
            array(
                'id'          => 'gs_body_large',
                'name'        => 'Body Large',
                'slug'        => 'body-large',
                'category'    => 'typography',
                'description' => 'Texto grande para introducción',
                'isDefault'   => true,
                'styles'      => array(
                    'fontSize'     => '20px',
                    'fontWeight'   => '400',
                    'lineHeight'   => '1.6',
                    'color'        => 'var(--flavor-text-muted)',
                ),
                'createdAt'   => current_time( 'mysql' ),
                'updatedAt'   => current_time( 'mysql' ),
            ),
            array(
                'id'          => 'gs_caption',
                'name'        => 'Caption',
                'slug'        => 'caption',
                'category'    => 'typography',
                'description' => 'Texto pequeño para pies de foto',
                'isDefault'   => true,
                'styles'      => array(
                    'fontSize'     => '14px',
                    'fontWeight'   => '400',
                    'lineHeight'   => '1.5',
                    'color'        => 'var(--flavor-text-muted)',
                ),
                'createdAt'   => current_time( 'mysql' ),
                'updatedAt'   => current_time( 'mysql' ),
            ),

            // Botones
            array(
                'id'          => 'gs_button_primary',
                'name'        => 'Button Primary',
                'slug'        => 'button-primary',
                'category'    => 'buttons',
                'description' => 'Botón principal de acción',
                'isDefault'   => true,
                'styles'      => array(
                    'padding'         => '12px 24px',
                    'backgroundColor' => 'var(--flavor-primary)',
                    'color'           => '#ffffff',
                    'borderRadius'    => '6px',
                    'fontWeight'      => '600',
                    'fontSize'        => '16px',
                    'textAlign'       => 'center',
                    'cursor'          => 'pointer',
                    'display'         => 'inline-block',
                    'transition'      => 'all 0.2s ease',
                ),
                'createdAt'   => current_time( 'mysql' ),
                'updatedAt'   => current_time( 'mysql' ),
            ),
            array(
                'id'          => 'gs_button_secondary',
                'name'        => 'Button Secondary',
                'slug'        => 'button-secondary',
                'category'    => 'buttons',
                'description' => 'Botón secundario',
                'isDefault'   => true,
                'styles'      => array(
                    'padding'         => '12px 24px',
                    'backgroundColor' => 'var(--flavor-secondary)',
                    'color'           => '#ffffff',
                    'borderRadius'    => '6px',
                    'fontWeight'      => '600',
                    'fontSize'        => '16px',
                    'textAlign'       => 'center',
                    'cursor'          => 'pointer',
                    'display'         => 'inline-block',
                    'transition'      => 'all 0.2s ease',
                ),
                'createdAt'   => current_time( 'mysql' ),
                'updatedAt'   => current_time( 'mysql' ),
            ),
            array(
                'id'          => 'gs_button_outline',
                'name'        => 'Button Outline',
                'slug'        => 'button-outline',
                'category'    => 'buttons',
                'description' => 'Botón con borde',
                'isDefault'   => true,
                'styles'      => array(
                    'padding'         => '11px 23px',
                    'backgroundColor' => 'transparent',
                    'color'           => 'var(--flavor-primary)',
                    'borderRadius'    => '6px',
                    'border'          => '1px solid var(--flavor-primary)',
                    'fontWeight'      => '600',
                    'fontSize'        => '16px',
                    'textAlign'       => 'center',
                    'cursor'          => 'pointer',
                    'display'         => 'inline-block',
                    'transition'      => 'all 0.2s ease',
                ),
                'createdAt'   => current_time( 'mysql' ),
                'updatedAt'   => current_time( 'mysql' ),
            ),
            array(
                'id'          => 'gs_button_ghost',
                'name'        => 'Button Ghost',
                'slug'        => 'button-ghost',
                'category'    => 'buttons',
                'description' => 'Botón transparente',
                'isDefault'   => true,
                'styles'      => array(
                    'padding'         => '12px 24px',
                    'backgroundColor' => 'transparent',
                    'color'           => 'var(--flavor-primary)',
                    'borderRadius'    => '6px',
                    'fontWeight'      => '600',
                    'fontSize'        => '16px',
                    'textAlign'       => 'center',
                    'cursor'          => 'pointer',
                    'display'         => 'inline-block',
                    'transition'      => 'all 0.2s ease',
                ),
                'createdAt'   => current_time( 'mysql' ),
                'updatedAt'   => current_time( 'mysql' ),
            ),

            // Contenedores
            array(
                'id'          => 'gs_card',
                'name'        => 'Card',
                'slug'        => 'card',
                'category'    => 'containers',
                'description' => 'Tarjeta con sombra',
                'isDefault'   => true,
                'styles'      => array(
                    'padding'         => '24px',
                    'backgroundColor' => '#ffffff',
                    'borderRadius'    => '12px',
                    'boxShadow'       => '0 2px 8px rgba(0,0,0,0.1)',
                ),
                'createdAt'   => current_time( 'mysql' ),
                'updatedAt'   => current_time( 'mysql' ),
            ),
            array(
                'id'          => 'gs_card_elevated',
                'name'        => 'Card Elevated',
                'slug'        => 'card-elevated',
                'category'    => 'containers',
                'description' => 'Tarjeta con sombra elevada',
                'isDefault'   => true,
                'styles'      => array(
                    'padding'         => '24px',
                    'backgroundColor' => '#ffffff',
                    'borderRadius'    => '12px',
                    'boxShadow'       => '0 8px 24px rgba(0,0,0,0.12)',
                ),
                'createdAt'   => current_time( 'mysql' ),
                'updatedAt'   => current_time( 'mysql' ),
            ),
            array(
                'id'          => 'gs_card_bordered',
                'name'        => 'Card Bordered',
                'slug'        => 'card-bordered',
                'category'    => 'containers',
                'description' => 'Tarjeta con borde',
                'isDefault'   => true,
                'styles'      => array(
                    'padding'         => '24px',
                    'backgroundColor' => '#ffffff',
                    'borderRadius'    => '12px',
                    'border'          => '1px solid #e5e7eb',
                ),
                'createdAt'   => current_time( 'mysql' ),
                'updatedAt'   => current_time( 'mysql' ),
            ),
            array(
                'id'          => 'gs_section_padded',
                'name'        => 'Section Padded',
                'slug'        => 'section-padded',
                'category'    => 'containers',
                'description' => 'Sección con padding estándar',
                'isDefault'   => true,
                'styles'      => array(
                    'padding'         => '80px 20px',
                ),
                'createdAt'   => current_time( 'mysql' ),
                'updatedAt'   => current_time( 'mysql' ),
            ),
            array(
                'id'          => 'gs_container_centered',
                'name'        => 'Container Centered',
                'slug'        => 'container-centered',
                'category'    => 'containers',
                'description' => 'Contenedor centrado con ancho máximo',
                'isDefault'   => true,
                'styles'      => array(
                    'maxWidth'  => '1200px',
                    'margin'    => '0 auto',
                    'padding'   => '0 20px',
                ),
                'createdAt'   => current_time( 'mysql' ),
                'updatedAt'   => current_time( 'mysql' ),
            ),
        );
    }

    /**
     * Obtener todos los estilos globales
     *
     * @return array
     */
    public function obtener_estilos() {
        if ( null !== $this->styles_cache ) {
            return $this->styles_cache;
        }

        $estilos = get_option( self::OPTION_NAME, array() );

        // Asegurar que sea un array
        if ( ! is_array( $estilos ) ) {
            $estilos = array();
        }

        $this->styles_cache = $estilos;
        return $estilos;
    }

    /**
     * Obtener un estilo por ID
     *
     * @param string $estilo_id ID del estilo.
     * @return array|null
     */
    public function obtener_estilo( $estilo_id ) {
        $estilos = $this->obtener_estilos();

        foreach ( $estilos as $estilo ) {
            if ( isset( $estilo['id'] ) && $estilo['id'] === $estilo_id ) {
                return $estilo;
            }
        }

        return null;
    }

    /**
     * Crear un nuevo estilo global
     *
     * @param array $datos Datos del estilo.
     * @return array|WP_Error
     */
    public function crear_estilo( $datos ) {
        // Validar datos requeridos
        if ( empty( $datos['name'] ) ) {
            return new WP_Error( 'missing_name', __( 'El nombre es requerido', 'flavor-platform' ) );
        }

        if ( empty( $datos['category'] ) ) {
            return new WP_Error( 'missing_category', __( 'La categoría es requerida', 'flavor-platform' ) );
        }

        // Generar ID y slug
        $estilo_id = 'gs_' . bin2hex( random_bytes( 6 ) );
        $slug = sanitize_title( $datos['name'] );

        $nuevo_estilo = array(
            'id'          => $estilo_id,
            'name'        => sanitize_text_field( $datos['name'] ),
            'slug'        => $slug,
            'category'    => sanitize_key( $datos['category'] ),
            'description' => isset( $datos['description'] ) ? sanitize_text_field( $datos['description'] ) : '',
            'isDefault'   => false,
            'styles'      => isset( $datos['styles'] ) ? $this->sanitizar_estilos( $datos['styles'] ) : array(),
            'createdAt'   => current_time( 'mysql' ),
            'updatedAt'   => current_time( 'mysql' ),
        );

        // Añadir a la lista
        $estilos = $this->obtener_estilos();
        $estilos[] = $nuevo_estilo;

        // Guardar
        update_option( self::OPTION_NAME, $estilos );
        $this->styles_cache = null; // Invalidar cache

        return $nuevo_estilo;
    }

    /**
     * Actualizar un estilo global
     *
     * @param string $estilo_id ID del estilo.
     * @param array  $datos     Datos a actualizar.
     * @return array|WP_Error
     */
    public function actualizar_estilo( $estilo_id, $datos ) {
        $estilos = $this->obtener_estilos();
        $encontrado = false;

        foreach ( $estilos as $index => $estilo ) {
            if ( isset( $estilo['id'] ) && $estilo['id'] === $estilo_id ) {
                // Actualizar campos permitidos
                if ( isset( $datos['name'] ) ) {
                    $estilos[ $index ]['name'] = sanitize_text_field( $datos['name'] );
                    $estilos[ $index ]['slug'] = sanitize_title( $datos['name'] );
                }
                if ( isset( $datos['category'] ) ) {
                    $estilos[ $index ]['category'] = sanitize_key( $datos['category'] );
                }
                if ( isset( $datos['description'] ) ) {
                    $estilos[ $index ]['description'] = sanitize_text_field( $datos['description'] );
                }
                if ( isset( $datos['styles'] ) ) {
                    $estilos[ $index ]['styles'] = $this->sanitizar_estilos( $datos['styles'] );
                }

                $estilos[ $index ]['updatedAt'] = current_time( 'mysql' );
                $encontrado = true;
                break;
            }
        }

        if ( ! $encontrado ) {
            return new WP_Error( 'not_found', __( 'Estilo no encontrado', 'flavor-platform' ) );
        }

        // Guardar
        update_option( self::OPTION_NAME, $estilos );
        $this->styles_cache = null; // Invalidar cache

        return $this->obtener_estilo( $estilo_id );
    }

    /**
     * Eliminar un estilo global
     *
     * @param string $estilo_id ID del estilo.
     * @return bool|WP_Error
     */
    public function eliminar_estilo( $estilo_id ) {
        $estilos = $this->obtener_estilos();
        $indice_eliminar = -1;

        foreach ( $estilos as $index => $estilo ) {
            if ( isset( $estilo['id'] ) && $estilo['id'] === $estilo_id ) {
                // No permitir eliminar estilos por defecto
                if ( ! empty( $estilo['isDefault'] ) ) {
                    return new WP_Error( 'cannot_delete_default', __( 'No se pueden eliminar estilos predefinidos', 'flavor-platform' ) );
                }
                $indice_eliminar = $index;
                break;
            }
        }

        if ( $indice_eliminar === -1 ) {
            return new WP_Error( 'not_found', __( 'Estilo no encontrado', 'flavor-platform' ) );
        }

        // Eliminar
        array_splice( $estilos, $indice_eliminar, 1 );

        // Guardar
        update_option( self::OPTION_NAME, $estilos );
        $this->styles_cache = null; // Invalidar cache

        return true;
    }

    /**
     * Sanitizar array de estilos CSS
     *
     * @param array $estilos Estilos a sanitizar.
     * @return array
     */
    private function sanitizar_estilos( $estilos ) {
        if ( ! is_array( $estilos ) ) {
            return array();
        }

        $estilos_sanitizados = array();

        // Lista de propiedades CSS permitidas
        $propiedades_permitidas = array(
            'fontSize', 'fontWeight', 'fontFamily', 'fontStyle',
            'lineHeight', 'letterSpacing', 'textAlign', 'textDecoration', 'textTransform',
            'color', 'backgroundColor', 'backgroundImage', 'backgroundSize', 'backgroundPosition',
            'padding', 'paddingTop', 'paddingRight', 'paddingBottom', 'paddingLeft',
            'margin', 'marginTop', 'marginRight', 'marginBottom', 'marginLeft',
            'border', 'borderTop', 'borderRight', 'borderBottom', 'borderLeft',
            'borderRadius', 'borderColor', 'borderWidth', 'borderStyle',
            'boxShadow', 'opacity',
            'display', 'flexDirection', 'justifyContent', 'alignItems', 'gap', 'flexWrap',
            'width', 'height', 'minWidth', 'minHeight', 'maxWidth', 'maxHeight',
            'position', 'top', 'right', 'bottom', 'left', 'zIndex',
            'overflow', 'cursor', 'transition', 'transform',
        );

        foreach ( $estilos as $propiedad => $valor ) {
            if ( in_array( $propiedad, $propiedades_permitidas, true ) ) {
                // Sanitizar valor (permitir variables CSS)
                $valor_limpio = wp_strip_all_tags( $valor );
                $estilos_sanitizados[ $propiedad ] = $valor_limpio;
            }
        }

        return $estilos_sanitizados;
    }

    /**
     * Generar CSS de todos los estilos globales
     *
     * @return string
     */
    public function generar_css() {
        $estilos = $this->obtener_estilos();
        $css = "/* VBP Global Styles - Generated */\n";

        foreach ( $estilos as $estilo ) {
            if ( empty( $estilo['slug'] ) || empty( $estilo['styles'] ) ) {
                continue;
            }

            $css .= '.vbp-gs-' . esc_attr( $estilo['slug'] ) . " {\n";

            foreach ( $estilo['styles'] as $propiedad => $valor ) {
                $propiedad_css = $this->camel_to_kebab( $propiedad );
                $css .= '  ' . esc_attr( $propiedad_css ) . ': ' . esc_attr( $valor ) . ";\n";
            }

            $css .= "}\n\n";
        }

        return $css;
    }

    /**
     * Convertir camelCase a kebab-case
     *
     * @param string $string String en camelCase.
     * @return string
     */
    private function camel_to_kebab( $string ) {
        return strtolower( preg_replace( '/([a-z])([A-Z])/', '$1-$2', $string ) );
    }

    /**
     * Inyectar CSS en el frontend
     */
    public function inyectar_css_frontend() {
        // Solo inyectar si hay páginas VBP
        global $post;
        if ( ! $post ) {
            return;
        }

        $vbp_data = get_post_meta( $post->ID, '_flavor_vbp_data', true );
        if ( empty( $vbp_data ) ) {
            return;
        }

        $css = $this->generar_css();
        if ( ! empty( $css ) ) {
            echo '<style id="vbp-global-styles">' . "\n" . $css . '</style>' . "\n";
        }
    }

    /**
     * Inyectar CSS en el editor
     */
    public function inyectar_css_editor() {
        $screen = get_current_screen();
        if ( ! $screen || 'admin_page_vbp-editor' !== $screen->id ) {
            return;
        }

        $css = $this->generar_css();
        if ( ! empty( $css ) ) {
            echo '<style id="vbp-global-styles-editor">' . "\n" . $css . '</style>' . "\n";
        }
    }

    // =========================================
    // REST API Callbacks
    // =========================================

    /**
     * REST: Obtener todos los estilos
     *
     * @param WP_REST_Request $request Request.
     * @return WP_REST_Response
     */
    public function rest_obtener_estilos( $request ) {
        $estilos = $this->obtener_estilos();

        // Agrupar por categoría si se solicita
        $group_by_category = $request->get_param( 'group' );

        if ( $group_by_category ) {
            $agrupados = array();
            foreach ( self::CATEGORIES as $cat_id => $cat_data ) {
                $agrupados[ $cat_id ] = array(
                    'category' => $cat_data,
                    'styles'   => array_filter( $estilos, function( $estilo ) use ( $cat_id ) {
                        return isset( $estilo['category'] ) && $estilo['category'] === $cat_id;
                    }),
                );
                // Re-indexar array
                $agrupados[ $cat_id ]['styles'] = array_values( $agrupados[ $cat_id ]['styles'] );
            }
            return rest_ensure_response( array(
                'success'    => true,
                'categories' => $agrupados,
            ) );
        }

        return rest_ensure_response( array(
            'success' => true,
            'styles'  => $estilos,
        ) );
    }

    /**
     * REST: Obtener un estilo
     *
     * @param WP_REST_Request $request Request.
     * @return WP_REST_Response
     */
    public function rest_obtener_estilo( $request ) {
        $estilo_id = $request->get_param( 'id' );
        $estilo = $this->obtener_estilo( $estilo_id );

        if ( ! $estilo ) {
            return new WP_REST_Response( array(
                'success' => false,
                'message' => __( 'Estilo no encontrado', 'flavor-platform' ),
            ), 404 );
        }

        return rest_ensure_response( array(
            'success' => true,
            'style'   => $estilo,
        ) );
    }

    /**
     * REST: Crear estilo
     *
     * @param WP_REST_Request $request Request.
     * @return WP_REST_Response
     */
    public function rest_crear_estilo( $request ) {
        $datos = $request->get_json_params();

        $resultado = $this->crear_estilo( $datos );

        if ( is_wp_error( $resultado ) ) {
            return new WP_REST_Response( array(
                'success' => false,
                'message' => $resultado->get_error_message(),
            ), 400 );
        }

        return rest_ensure_response( array(
            'success' => true,
            'style'   => $resultado,
            'message' => __( 'Estilo creado correctamente', 'flavor-platform' ),
        ) );
    }

    /**
     * REST: Actualizar estilo
     *
     * @param WP_REST_Request $request Request.
     * @return WP_REST_Response
     */
    public function rest_actualizar_estilo( $request ) {
        $estilo_id = $request->get_param( 'id' );
        $datos = $request->get_json_params();

        $resultado = $this->actualizar_estilo( $estilo_id, $datos );

        if ( is_wp_error( $resultado ) ) {
            return new WP_REST_Response( array(
                'success' => false,
                'message' => $resultado->get_error_message(),
            ), 400 );
        }

        return rest_ensure_response( array(
            'success' => true,
            'style'   => $resultado,
            'message' => __( 'Estilo actualizado correctamente', 'flavor-platform' ),
        ) );
    }

    /**
     * REST: Eliminar estilo
     *
     * @param WP_REST_Request $request Request.
     * @return WP_REST_Response
     */
    public function rest_eliminar_estilo( $request ) {
        $estilo_id = $request->get_param( 'id' );

        $resultado = $this->eliminar_estilo( $estilo_id );

        if ( is_wp_error( $resultado ) ) {
            return new WP_REST_Response( array(
                'success' => false,
                'message' => $resultado->get_error_message(),
            ), 400 );
        }

        return rest_ensure_response( array(
            'success' => true,
            'message' => __( 'Estilo eliminado correctamente', 'flavor-platform' ),
        ) );
    }

    /**
     * REST: Obtener categorías
     *
     * @param WP_REST_Request $request Request.
     * @return WP_REST_Response
     */
    public function rest_obtener_categorias( $request ) {
        return rest_ensure_response( array(
            'success'    => true,
            'categories' => array_values( self::CATEGORIES ),
        ) );
    }

    /**
     * REST: Obtener CSS generado
     *
     * @param WP_REST_Request $request Request.
     * @return WP_REST_Response
     */
    public function rest_obtener_css( $request ) {
        $css = $this->generar_css();

        return rest_ensure_response( array(
            'success' => true,
            'css'     => $css,
        ) );
    }

    /**
     * Obtener datos para localización JavaScript
     *
     * @return array
     */
    public function obtener_datos_localizacion() {
        return array(
            'styles'     => $this->obtener_estilos(),
            'categories' => array_values( self::CATEGORIES ),
            'endpoints'  => array(
                'list'       => rest_url( self::REST_NAMESPACE . '/global-styles' ),
                'create'     => rest_url( self::REST_NAMESPACE . '/global-styles' ),
                'update'     => rest_url( self::REST_NAMESPACE . '/global-styles/{id}' ),
                'delete'     => rest_url( self::REST_NAMESPACE . '/global-styles/{id}' ),
                'categories' => rest_url( self::REST_NAMESPACE . '/global-styles/categories' ),
                'css'        => rest_url( self::REST_NAMESPACE . '/global-styles/css' ),
            ),
            'strings'    => array(
                'panelTitle'        => __( 'Estilos Globales', 'flavor-platform' ),
                'createNew'         => __( 'Crear estilo', 'flavor-platform' ),
                'editStyle'         => __( 'Editar estilo', 'flavor-platform' ),
                'deleteStyle'       => __( 'Eliminar estilo', 'flavor-platform' ),
                'applyStyle'        => __( 'Aplicar', 'flavor-platform' ),
                'detachStyle'       => __( 'Desenlazar', 'flavor-platform' ),
                'createFromElement' => __( 'Crear desde elemento', 'flavor-platform' ),
                'noGlobalStyle'     => __( 'Sin estilo global', 'flavor-platform' ),
                'confirmDelete'     => __( '¿Eliminar este estilo? Los elementos que lo usan mantendrán sus estilos.', 'flavor-platform' ),
                'styleSaved'        => __( 'Estilo guardado', 'flavor-platform' ),
                'styleDeleted'      => __( 'Estilo eliminado', 'flavor-platform' ),
                'styleApplied'      => __( 'Estilo aplicado', 'flavor-platform' ),
                'styleDetached'     => __( 'Estilo desenlazado', 'flavor-platform' ),
                'noStyles'          => __( 'No hay estilos en esta categoría', 'flavor-platform' ),
                'linkedStyle'       => __( 'Estilo enlazado', 'flavor-platform' ),
                'hasOverrides'      => __( 'Con modificaciones locales', 'flavor-platform' ),
            ),
        );
    }
}

// Inicializar
Flavor_VBP_Global_Styles::get_instance();

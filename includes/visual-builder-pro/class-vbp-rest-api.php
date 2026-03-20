<?php
/**
 * Visual Builder Pro - REST API
 *
 * Endpoints REST para el editor visual.
 *
 * @package Flavor_Chat_IA
 * @subpackage Visual_Builder_Pro
 * @since 2.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Clase para los endpoints REST del Visual Builder Pro
 *
 * @since 2.0.0
 */
class Flavor_VBP_REST_API {

    /**
     * Namespace de la API
     *
     * @var string
     */
    const NAMESPACE = 'flavor-vbp/v1';

    /**
     * Instancia singleton
     *
     * @var Flavor_VBP_REST_API|null
     */
    private static $instancia = null;

    /**
     * Obtiene la instancia singleton
     *
     * @return Flavor_VBP_REST_API
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
        add_action( 'rest_api_init', array( $this, 'registrar_rutas' ) );
    }

    /**
     * Registra las rutas REST
     */
    public function registrar_rutas() {
        // Documentos
        register_rest_route(
            self::NAMESPACE,
            '/documents/(?P<id>\d+)',
            array(
                array(
                    'methods'             => WP_REST_Server::READABLE,
                    'callback'            => array( $this, 'obtener_documento' ),
                    'permission_callback' => array( $this, 'verificar_permiso_lectura' ),
                    'args'                => array(
                        'id' => array(
                            'required'          => true,
                            'validate_callback' => function ( $param ) {
                                return is_numeric( $param );
                            },
                        ),
                    ),
                ),
                array(
                    'methods'             => WP_REST_Server::CREATABLE,
                    'callback'            => array( $this, 'guardar_documento' ),
                    'permission_callback' => array( $this, 'verificar_permiso_escritura' ),
                ),
            )
        );

        // Revisiones
        register_rest_route(
            self::NAMESPACE,
            '/documents/(?P<id>\d+)/revisions',
            array(
                'methods'             => WP_REST_Server::READABLE,
                'callback'            => array( $this, 'obtener_revisiones' ),
                'permission_callback' => array( $this, 'verificar_permiso_lectura' ),
            )
        );

        // Restaurar revisión
        register_rest_route(
            self::NAMESPACE,
            '/documents/(?P<id>\d+)/revisions/(?P<revision_id>\d+)/restore',
            array(
                'methods'             => WP_REST_Server::CREATABLE,
                'callback'            => array( $this, 'restaurar_revision' ),
                'permission_callback' => array( $this, 'verificar_permiso_escritura' ),
            )
        );

        // Renderizar elemento
        register_rest_route(
            self::NAMESPACE,
            '/render-element',
            array(
                'methods'             => WP_REST_Server::CREATABLE,
                'callback'            => array( $this, 'renderizar_elemento' ),
                'permission_callback' => array( $this, 'verificar_permiso_lectura' ),
            )
        );

        // Librería de bloques
        register_rest_route(
            self::NAMESPACE,
            '/blocks',
            array(
                'methods'             => WP_REST_Server::READABLE,
                'callback'            => array( $this, 'obtener_bloques' ),
                'permission_callback' => array( $this, 'verificar_permiso_lectura' ),
            )
        );

        // Schema de bloques (para Claude Code y automatización)
        register_rest_route(
            self::NAMESPACE,
            '/blocks/schema',
            array(
                'methods'             => WP_REST_Server::READABLE,
                'callback'            => array( $this, 'obtener_schema_bloques' ),
                'permission_callback' => '__return_true', // Público para facilitar integración
            )
        );

        // Shortcodes disponibles por módulo
        register_rest_route(
            self::NAMESPACE,
            '/shortcodes',
            array(
                'methods'             => WP_REST_Server::READABLE,
                'callback'            => array( $this, 'obtener_shortcodes' ),
                'permission_callback' => '__return_true',
            )
        );

        // Templates
        register_rest_route(
            self::NAMESPACE,
            '/templates',
            array(
                array(
                    'methods'             => WP_REST_Server::READABLE,
                    'callback'            => array( $this, 'obtener_templates' ),
                    'permission_callback' => array( $this, 'verificar_permiso_lectura' ),
                ),
                array(
                    'methods'             => WP_REST_Server::CREATABLE,
                    'callback'            => array( $this, 'crear_template' ),
                    'permission_callback' => array( $this, 'verificar_permiso_escritura' ),
                ),
            )
        );

        // Aplicar template
        register_rest_route(
            self::NAMESPACE,
            '/documents/(?P<id>\d+)/apply-template',
            array(
                'methods'             => WP_REST_Server::CREATABLE,
                'callback'            => array( $this, 'aplicar_template' ),
                'permission_callback' => array( $this, 'verificar_permiso_escritura' ),
            )
        );

        // Eliminar template
        register_rest_route(
            self::NAMESPACE,
            '/templates/(?P<id>\d+)',
            array(
                'methods'             => WP_REST_Server::DELETABLE,
                'callback'            => array( $this, 'eliminar_template' ),
                'permission_callback' => array( $this, 'verificar_permiso_escritura' ),
            )
        );

        // Exportar HTML
        register_rest_route(
            self::NAMESPACE,
            '/documents/(?P<id>\d+)/export-html',
            array(
                'methods'             => WP_REST_Server::CREATABLE,
                'callback'            => array( $this, 'exportar_html' ),
                'permission_callback' => array( $this, 'verificar_permiso_lectura' ),
            )
        );

        // Ping (health check)
        register_rest_route(
            self::NAMESPACE,
            '/ping',
            array(
                'methods'             => WP_REST_Server::READABLE,
                'callback'            => function () {
                    return new WP_REST_Response( array( 'status' => 'ok' ), 200 );
                },
                'permission_callback' => '__return_true',
            )
        );

        // Previsualización de shortcode para módulos
        register_rest_route(
            self::NAMESPACE,
            '/preview-shortcode',
            array(
                'methods'             => WP_REST_Server::CREATABLE,
                'callback'            => array( $this, 'previsualizar_shortcode' ),
                'permission_callback' => array( $this, 'verificar_permiso_lectura' ),
            )
        );

        // Búsqueda de posts para autocompletado de enlaces
        register_rest_route(
            self::NAMESPACE,
            '/search-posts',
            array(
                'methods'             => WP_REST_Server::READABLE,
                'callback'            => array( $this, 'buscar_posts' ),
                'permission_callback' => array( $this, 'verificar_permiso_lectura' ),
                'args'                => array(
                    'search' => array(
                        'required'          => true,
                        'sanitize_callback' => 'sanitize_text_field',
                    ),
                    'type'   => array(
                        'default'           => 'any',
                        'sanitize_callback' => 'sanitize_text_field',
                    ),
                ),
            )
        );

        // Blog posts para widget dinámico
        register_rest_route(
            self::NAMESPACE,
            '/blog-posts',
            array(
                'methods'             => WP_REST_Server::READABLE,
                'callback'            => array( $this, 'obtener_blog_posts' ),
                'permission_callback' => '__return_true', // Público para frontend
                'args'                => array(
                    'category'    => array(
                        'default'           => '',
                        'sanitize_callback' => 'sanitize_text_field',
                    ),
                    'per_page'    => array(
                        'default'           => 6,
                        'sanitize_callback' => 'absint',
                    ),
                    'page'        => array(
                        'default'           => 1,
                        'sanitize_callback' => 'absint',
                    ),
                    'orderby'     => array(
                        'default'           => 'date',
                        'sanitize_callback' => 'sanitize_text_field',
                    ),
                    'order'       => array(
                        'default'           => 'DESC',
                        'sanitize_callback' => 'sanitize_text_field',
                    ),
                    'post_type'   => array(
                        'default'           => 'post',
                        'sanitize_callback' => 'sanitize_text_field',
                    ),
                    'exclude'     => array(
                        'default'           => '',
                        'sanitize_callback' => 'sanitize_text_field',
                    ),
                ),
            )
        );

        // Obtener categorías para el selector
        register_rest_route(
            self::NAMESPACE,
            '/categories',
            array(
                'methods'             => WP_REST_Server::READABLE,
                'callback'            => array( $this, 'obtener_categorias' ),
                'permission_callback' => '__return_true',
            )
        );
    }

    /**
     * Verifica permiso de lectura
     *
     * @param WP_REST_Request $request Petición REST.
     * @return bool
     */
    public function verificar_permiso_lectura( $request ) {
        $post_id = $request->get_param( 'id' );

        if ( $post_id ) {
            return current_user_can( 'read_post', $post_id );
        }

        return current_user_can( 'edit_posts' );
    }

    /**
     * Verifica permiso de escritura
     *
     * @param WP_REST_Request $request Petición REST.
     * @return bool
     */
    public function verificar_permiso_escritura( $request ) {
        $post_id = $request->get_param( 'id' );

        if ( $post_id ) {
            return current_user_can( 'edit_post', $post_id );
        }

        return current_user_can( 'edit_posts' );
    }

    /**
     * Obtiene un documento
     *
     * @param WP_REST_Request $request Petición REST.
     * @return WP_REST_Response|WP_Error
     */
    public function obtener_documento( $request ) {
        $post_id = $request->get_param( 'id' );
        $post    = get_post( $post_id );

        if ( ! $post ) {
            return new WP_Error(
                'documento_no_encontrado',
                __( 'Documento no encontrado', 'flavor-chat-ia' ),
                array( 'status' => 404 )
            );
        }

        $editor = Flavor_VBP_Editor::get_instance();
        $datos  = $editor->obtener_datos_documento( $post_id );

        return new WP_REST_Response(
            array(
                'id'       => $post->ID,
                'title'    => $post->post_title,
                'status'   => $post->post_status,
                'document' => $datos,
            ),
            200
        );
    }

    /**
     * Guarda un documento
     *
     * @param WP_REST_Request $request Petición REST.
     * @return WP_REST_Response|WP_Error
     */
    public function guardar_documento( $request ) {
        $post_id = $request->get_param( 'id' );
        $datos   = $request->get_json_params();

        // Debug: verificar datos recibidos
        if ( empty( $datos ) ) {
            return new WP_Error(
                'datos_vacios',
                __( 'No se recibieron datos para guardar', 'flavor-chat-ia' ),
                array( 'status' => 400 )
            );
        }

        $editor   = Flavor_VBP_Editor::get_instance();
        $guardado = $editor->guardar_datos_documento( $post_id, $datos );

        if ( $guardado ) {
            // Actualizar título si se proporcionó
            if ( isset( $datos['title'] ) ) {
                wp_update_post(
                    array(
                        'ID'         => $post_id,
                        'post_title' => sanitize_text_field( $datos['title'] ),
                    )
                );
            }

            return new WP_REST_Response(
                array(
                    'success'   => true,
                    'message'   => __( 'Documento guardado', 'flavor-chat-ia' ),
                    'updatedAt' => current_time( 'mysql' ),
                ),
                200
            );
        }

        return new WP_Error(
            'error_guardado',
            __( 'Error al guardar el documento', 'flavor-chat-ia' ),
            array( 'status' => 500 )
        );
    }

    /**
     * Obtiene las revisiones de un documento
     *
     * @param WP_REST_Request $request Petición REST.
     * @return WP_REST_Response
     */
    public function obtener_revisiones( $request ) {
        $post_id    = $request->get_param( 'id' );
        $revisiones = wp_get_post_revisions( $post_id );

        $resultado = array();
        foreach ( $revisiones as $revision ) {
            $resultado[] = array(
                'id'         => $revision->ID,
                'author'     => get_the_author_meta( 'display_name', $revision->post_author ),
                'date'       => $revision->post_modified,
                'title'      => $revision->post_title,
            );
        }

        return new WP_REST_Response( $resultado, 200 );
    }

    /**
     * Restaura una revisión
     *
     * @param WP_REST_Request $request Petición REST.
     * @return WP_REST_Response|WP_Error
     */
    public function restaurar_revision( $request ) {
        $post_id     = $request->get_param( 'id' );
        $revision_id = $request->get_param( 'revision_id' );

        $restaurado = wp_restore_post_revision( $revision_id );

        if ( $restaurado ) {
            return new WP_REST_Response(
                array(
                    'success' => true,
                    'message' => __( 'Revisión restaurada', 'flavor-chat-ia' ),
                ),
                200
            );
        }

        return new WP_Error(
            'error_restaurar',
            __( 'Error al restaurar la revisión', 'flavor-chat-ia' ),
            array( 'status' => 500 )
        );
    }

    /**
     * Renderiza un elemento
     *
     * @param WP_REST_Request $request Petición REST.
     * @return WP_REST_Response
     */
    public function renderizar_elemento( $request ) {
        $elemento = $request->get_json_params();

        if ( class_exists( 'Flavor_VBP_Canvas' ) ) {
            $canvas = Flavor_VBP_Canvas::get_instance();
            $html   = $canvas->renderizar_elemento( $elemento );

            return new WP_REST_Response(
                array(
                    'html' => $html,
                ),
                200
            );
        }

        return new WP_REST_Response(
            array(
                'html' => '<div class="vbp-error">Renderer no disponible</div>',
            ),
            200
        );
    }

    /**
     * Previsualiza un shortcode renderizado
     *
     * @param WP_REST_Request $request Petición REST.
     * @return WP_REST_Response
     */
    public function previsualizar_shortcode( $request ) {
        $params    = $request->get_json_params();
        $shortcode = sanitize_text_field( $params['shortcode'] ?? '' );
        $atributos = $params['attributes'] ?? array();

        if ( empty( $shortcode ) ) {
            return new WP_REST_Response(
                array(
                    'success' => false,
                    'html'    => '<div class="vbp-preview-error">No se especificó shortcode</div>',
                ),
                400
            );
        }

        // Construir el shortcode con atributos
        $shortcode_string = '[' . $shortcode;
        if ( ! empty( $atributos ) && is_array( $atributos ) ) {
            foreach ( $atributos as $key => $value ) {
                $shortcode_string .= ' ' . sanitize_key( $key ) . '="' . esc_attr( $value ) . '"';
            }
        }
        $shortcode_string .= ']';

        // Renderizar el shortcode
        ob_start();
        echo do_shortcode( $shortcode_string );
        $html = ob_get_clean();

        // Si está vacío, mostrar mensaje
        if ( empty( trim( $html ) ) ) {
            $html = '<div class="vbp-preview-empty" style="padding: 24px; text-align: center; background: var(--flavor-bg-muted, #f5f5f5); border-radius: 8px; color: var(--flavor-text-muted, #666);">'
                  . '<p style="margin: 0;">El módulo <strong>' . esc_html( $shortcode ) . '</strong> no tiene contenido para mostrar.</p>'
                  . '<p style="margin: 8px 0 0; font-size: 13px;">Esto puede deberse a que no hay datos o la configuración está incompleta.</p>'
                  . '</div>';
        }

        // Envolver en contenedor para estilos
        $html = '<div class="vbp-shortcode-preview">' . $html . '</div>';

        return new WP_REST_Response(
            array(
                'success'   => true,
                'html'      => $html,
                'shortcode' => $shortcode_string,
            ),
            200
        );
    }

    /**
     * Obtiene la librería de bloques
     *
     * @return WP_REST_Response
     */
    public function obtener_bloques() {
        if ( class_exists( 'Flavor_VBP_Block_Library' ) ) {
            $libreria   = Flavor_VBP_Block_Library::get_instance();
            $categorias = $libreria->get_categorias_con_bloques();

            return new WP_REST_Response( $categorias, 200 );
        }

        return new WP_REST_Response( array(), 200 );
    }

    /**
     * Obtiene el schema JSON de todos los bloques disponibles
     * Diseñado para Claude Code y automatización
     *
     * @return WP_REST_Response
     */
    public function obtener_schema_bloques() {
        if ( ! class_exists( 'Flavor_VBP_Block_Library' ) ) {
            return new WP_REST_Response( array( 'error' => 'Block Library not available' ), 500 );
        }

        $libreria   = Flavor_VBP_Block_Library::get_instance();
        $categorias = $libreria->get_categorias_con_bloques();
        $schema     = array(
            'version'    => FLAVOR_CHAT_IA_VERSION ?? '2.0.0',
            'generated'  => gmdate( 'c' ),
            'categories' => array(),
            'blocks'     => array(),
        );

        foreach ( $categorias as $categoria ) {
            $schema['categories'][] = array(
                'id'    => $categoria['id'],
                'name'  => $categoria['name'],
                'order' => $categoria['order'] ?? 0,
            );

            if ( ! empty( $categoria['blocks'] ) ) {
                foreach ( $categoria['blocks'] as $bloque ) {
                    $block_schema = array(
                        'id'          => $bloque['id'],
                        'name'        => $bloque['name'],
                        'category'    => $categoria['id'],
                        'icon'        => $bloque['icon'] ?? '',
                        'description' => $bloque['description'] ?? '',
                    );

                    // Variantes
                    if ( ! empty( $bloque['variants'] ) ) {
                        $block_schema['variants'] = array_keys( $bloque['variants'] );
                    }

                    // Campos (data properties)
                    if ( ! empty( $bloque['fields'] ) ) {
                        $block_schema['fields'] = array();
                        foreach ( $bloque['fields'] as $field_id => $field ) {
                            if ( strpos( $field_id, '_separator' ) === 0 ) {
                                continue; // Ignorar separadores
                            }
                            $block_schema['fields'][ $field_id ] = array(
                                'type'    => $field['type'] ?? 'text',
                                'label'   => $field['label'] ?? $field_id,
                                'default' => $field['default'] ?? null,
                            );
                            if ( ! empty( $field['options'] ) ) {
                                $block_schema['fields'][ $field_id ]['options'] = array_keys( $field['options'] );
                            }
                            if ( ! empty( $field['fields'] ) ) {
                                // Repeater fields
                                $block_schema['fields'][ $field_id ]['subfields'] = array_keys( $field['fields'] );
                            }
                        }
                    }

                    // Presets
                    if ( ! empty( $bloque['presets'] ) ) {
                        $block_schema['presets'] = array_keys( $bloque['presets'] );
                    }

                    // Módulo (si es widget de módulo)
                    if ( ! empty( $bloque['module'] ) ) {
                        $block_schema['module'] = $bloque['module'];
                    }

                    // Shortcode
                    if ( ! empty( $bloque['shortcode'] ) ) {
                        $block_schema['shortcode'] = $bloque['shortcode'];
                    }

                    $schema['blocks'][ $bloque['id'] ] = $block_schema;
                }
            }
        }

        // Añadir estructura de estilos común
        $schema['styles_schema'] = array(
            'spacing'    => array(
                'margin'  => array( 'top', 'right', 'bottom', 'left' ),
                'padding' => array( 'top', 'right', 'bottom', 'left' ),
            ),
            'colors'     => array( 'background', 'text' ),
            'background' => array( 'type', 'gradientDirection', 'gradientStart', 'gradientEnd', 'image', 'size', 'position', 'repeat', 'fixed' ),
            'typography' => array( 'fontSize', 'fontWeight', 'lineHeight', 'textAlign' ),
            'borders'    => array( 'radius', 'width', 'color', 'style' ),
            'shadows'    => array( 'boxShadow' ),
            'layout'     => array( 'display', 'flexDirection', 'justifyContent', 'alignItems', 'gap' ),
            'dimensions' => array( 'width', 'height', 'minHeight', 'maxWidth' ),
            'position'   => array( 'position', 'top', 'right', 'bottom', 'left', 'zIndex' ),
            'transform'  => array( 'rotate', 'scale', 'translateX', 'translateY', 'skewX', 'skewY' ),
            'overflow'   => 'string',
            'opacity'    => 'number',
        );

        return new WP_REST_Response( $schema, 200 );
    }

    /**
     * Obtiene lista de shortcodes disponibles por módulo
     *
     * @return WP_REST_Response
     */
    public function obtener_shortcodes() {
        global $shortcode_tags;

        $shortcodes = array();

        // Filtrar shortcodes de Flavor
        foreach ( $shortcode_tags as $tag => $callback ) {
            // Detectar shortcodes de flavor
            if (
                strpos( $tag, 'flavor_' ) === 0 ||
                strpos( $tag, 'gc_' ) === 0 ||
                strpos( $tag, 'ev_' ) === 0 ||
                strpos( $tag, 'bt_' ) === 0 ||
                strpos( $tag, 'cursos_' ) === 0 ||
                strpos( $tag, 'socios_' ) === 0 ||
                strpos( $tag, 'rs_' ) === 0 ||
                strpos( $tag, 'chat_' ) === 0
            ) {
                $module = 'general';
                if ( strpos( $tag, 'gc_' ) === 0 ) {
                    $module = 'grupos_consumo';
                } elseif ( strpos( $tag, 'ev_' ) === 0 ) {
                    $module = 'eventos';
                } elseif ( strpos( $tag, 'bt_' ) === 0 ) {
                    $module = 'banco_tiempo';
                } elseif ( strpos( $tag, 'cursos_' ) === 0 ) {
                    $module = 'cursos';
                } elseif ( strpos( $tag, 'socios_' ) === 0 ) {
                    $module = 'socios';
                } elseif ( strpos( $tag, 'rs_' ) === 0 ) {
                    $module = 'red_social';
                } elseif ( strpos( $tag, 'chat_' ) === 0 ) {
                    $module = 'chat';
                }

                if ( ! isset( $shortcodes[ $module ] ) ) {
                    $shortcodes[ $module ] = array();
                }
                $shortcodes[ $module ][] = $tag;
            }
        }

        // Obtener shortcodes de la Block Library también
        if ( class_exists( 'Flavor_VBP_Block_Library' ) ) {
            $libreria   = Flavor_VBP_Block_Library::get_instance();
            $categorias = $libreria->get_categorias_con_bloques();

            foreach ( $categorias as $categoria ) {
                if ( ! empty( $categoria['blocks'] ) ) {
                    foreach ( $categoria['blocks'] as $bloque ) {
                        if ( ! empty( $bloque['shortcode'] ) && ! empty( $bloque['module'] ) ) {
                            $module = $bloque['module'];
                            if ( ! isset( $shortcodes[ $module ] ) ) {
                                $shortcodes[ $module ] = array();
                            }
                            if ( ! in_array( $bloque['shortcode'], $shortcodes[ $module ], true ) ) {
                                $shortcodes[ $module ][] = $bloque['shortcode'];
                            }
                        }
                    }
                }
            }
        }

        return new WP_REST_Response(
            array(
                'shortcodes' => $shortcodes,
                'total'      => array_sum( array_map( 'count', $shortcodes ) ),
            ),
            200
        );
    }

    /**
     * Obtiene los templates disponibles
     *
     * @return WP_REST_Response
     */
    public function obtener_templates() {
        $current_user_id = get_current_user_id();

        // Templates del usuario actual
        $user_templates = get_posts(
            array(
                'post_type'      => 'vbp_template',
                'posts_per_page' => -1,
                'orderby'        => 'date',
                'order'          => 'DESC',
                'author'         => $current_user_id,
            )
        );

        $user_result = array();
        foreach ( $user_templates as $template ) {
            $user_result[] = array(
                'id'          => $template->ID,
                'title'       => $template->post_title,
                'thumbnail'   => get_post_meta( $template->ID, '_vbp_template_thumbnail', true ),
                'category'    => get_post_meta( $template->ID, '_vbp_template_category', true ),
                'description' => $template->post_excerpt,
                'date'        => get_the_date( 'd M Y', $template ),
            );
        }

        // Templates de librería (creados por admins o importados)
        $library_templates = $this->get_library_templates();

        return new WP_REST_Response(
            array(
                'library' => $library_templates,
                'user'    => $user_result,
            ),
            200
        );
    }

    /**
     * Obtiene los templates de la librería predefinidos
     *
     * @return array
     */
    public function get_library_templates() {
        // Templates predefinidos de la librería
        $library = array(
            array(
                'id'          => 'starter-landing',
                'title'       => 'Landing Page Starter',
                'thumbnail'   => FLAVOR_CHAT_IA_URL . 'assets/vbp/images/templates/landing-starter.svg',
                'category'    => 'landing',
                'description' => 'Landing page minimalista con hero, features y CTA',
                'preview_url' => '',
                'elements'    => array(
                    array( 'type' => 'hero', 'data' => array( 'titulo' => 'Bienvenido a tu nuevo proyecto', 'subtitulo' => 'Una solución elegante y moderna para tu negocio', 'boton_texto' => 'Comenzar ahora' ) ),
                    array( 'type' => 'features', 'data' => array( 'titulo' => 'Nuestras características', 'items' => array( array( 'icono' => '⚡', 'titulo' => 'Rápido', 'descripcion' => 'Implementación en minutos' ), array( 'icono' => '🔒', 'titulo' => 'Seguro', 'descripcion' => 'Protección garantizada' ), array( 'icono' => '📱', 'titulo' => 'Responsive', 'descripcion' => 'Perfecto en cualquier dispositivo' ) ) ) ),
                    array( 'type' => 'cta', 'data' => array( 'titulo' => '¿Listo para empezar?', 'subtitulo' => 'Únete a miles de usuarios satisfechos', 'boton_texto' => 'Registrarse gratis' ) ),
                ),
            ),
            array(
                'id'          => 'saas-pricing',
                'title'       => 'SaaS Pricing',
                'thumbnail'   => FLAVOR_CHAT_IA_URL . 'assets/vbp/images/templates/saas-pricing.svg',
                'category'    => 'business',
                'description' => 'Página de precios para productos SaaS',
                'preview_url' => '',
                'elements'    => array(
                    array( 'type' => 'hero', 'data' => array( 'titulo' => 'Precios simples y transparentes', 'subtitulo' => 'Elige el plan que mejor se adapte a tu equipo', 'boton_texto' => 'Ver planes' ) ),
                    array( 'type' => 'pricing', 'data' => array( 'titulo' => 'Nuestros Planes', 'subtitulo' => 'Sin costes ocultos' ) ),
                    array( 'type' => 'faq', 'data' => array( 'titulo' => 'Preguntas frecuentes' ) ),
                    array( 'type' => 'cta', 'data' => array( 'titulo' => '¿Necesitas ayuda para elegir?', 'subtitulo' => 'Contacta con nuestro equipo de ventas', 'boton_texto' => 'Contactar' ) ),
                ),
            ),
            array(
                'id'          => 'portfolio',
                'title'       => 'Portfolio Creativo',
                'thumbnail'   => FLAVOR_CHAT_IA_URL . 'assets/vbp/images/templates/portfolio.svg',
                'category'    => 'portfolio',
                'description' => 'Portfolio para mostrar tus proyectos',
                'preview_url' => '',
                'elements'    => array(
                    array( 'type' => 'hero', 'data' => array( 'titulo' => 'Diseño con propósito', 'subtitulo' => 'Portfolio de proyectos creativos', 'boton_texto' => 'Ver trabajos' ) ),
                    array( 'type' => 'gallery', 'data' => array( 'titulo' => 'Proyectos destacados' ) ),
                    array( 'type' => 'testimonials', 'data' => array( 'titulo' => 'Lo que dicen mis clientes' ) ),
                    array( 'type' => 'contact', 'data' => array( 'titulo' => 'Trabajemos juntos', 'subtitulo' => 'Cuéntame sobre tu proyecto' ) ),
                ),
            ),
            array(
                'id'          => 'startup',
                'title'       => 'Startup Launch',
                'thumbnail'   => FLAVOR_CHAT_IA_URL . 'assets/vbp/images/templates/startup.svg',
                'category'    => 'business',
                'description' => 'Landing para lanzamiento de startup',
                'preview_url' => '',
                'elements'    => array(
                    array( 'type' => 'hero', 'data' => array( 'titulo' => 'El futuro de la productividad', 'subtitulo' => 'La herramienta que tu equipo necesita', 'boton_texto' => 'Solicitar acceso' ) ),
                    array( 'type' => 'stats', 'data' => array( 'items' => array( array( 'numero' => '10K+', 'label' => 'Usuarios' ), array( 'numero' => '99%', 'label' => 'Satisfacción' ), array( 'numero' => '24/7', 'label' => 'Soporte' ) ) ) ),
                    array( 'type' => 'features', 'data' => array( 'titulo' => 'Todo lo que necesitas' ) ),
                    array( 'type' => 'team', 'data' => array( 'titulo' => 'Nuestro equipo' ) ),
                    array( 'type' => 'cta', 'data' => array( 'titulo' => 'Únete a la revolución', 'boton_texto' => 'Comenzar gratis' ) ),
                ),
            ),
            array(
                'id'          => 'ecommerce',
                'title'       => 'E-commerce Promo',
                'thumbnail'   => FLAVOR_CHAT_IA_URL . 'assets/vbp/images/templates/ecommerce.svg',
                'category'    => 'ecommerce',
                'description' => 'Landing promocional para tienda online',
                'preview_url' => '',
                'elements'    => array(
                    array( 'type' => 'hero', 'data' => array( 'titulo' => 'Ofertas de temporada', 'subtitulo' => 'Hasta 50% de descuento en productos seleccionados', 'boton_texto' => 'Ver ofertas' ) ),
                    array( 'type' => 'features', 'data' => array( 'titulo' => 'Por qué elegirnos', 'items' => array( array( 'icono' => '🚚', 'titulo' => 'Envío gratis', 'descripcion' => 'En pedidos +50€' ), array( 'icono' => '↩️', 'titulo' => 'Devolución fácil', 'descripcion' => '30 días de garantía' ), array( 'icono' => '🔐', 'titulo' => 'Pago seguro', 'descripcion' => 'Encriptación SSL' ) ) ) ),
                    array( 'type' => 'testimonials', 'data' => array( 'titulo' => 'Opiniones de clientes' ) ),
                    array( 'type' => 'cta', 'data' => array( 'titulo' => 'No te pierdas nuestras ofertas', 'subtitulo' => 'Suscríbete y recibe un 10% de descuento', 'boton_texto' => 'Suscribirse' ) ),
                ),
            ),
        );

        return $library;
    }

    /**
     * Crea un nuevo template
     *
     * @param WP_REST_Request $request Petición REST.
     * @return WP_REST_Response|WP_Error
     */
    public function crear_template( $request ) {
        $datos   = $request->get_json_params();
        $post_id = isset( $datos['post_id'] ) ? absint( $datos['post_id'] ) : 0;
        $nombre  = isset( $datos['name'] ) ? sanitize_text_field( $datos['name'] ) : '';

        if ( ! $post_id || ! $nombre ) {
            return new WP_Error(
                'datos_invalidos',
                __( 'Datos inválidos', 'flavor-chat-ia' ),
                array( 'status' => 400 )
            );
        }

        // Obtener datos del documento original
        $editor       = Flavor_VBP_Editor::get_instance();
        $datos_origen = $editor->obtener_datos_documento( $post_id );

        // Crear el template
        $template_id = wp_insert_post(
            array(
                'post_type'   => 'vbp_template',
                'post_title'  => $nombre,
                'post_status' => 'publish',
            )
        );

        if ( is_wp_error( $template_id ) ) {
            return new WP_Error(
                'error_crear',
                __( 'Error al crear el template', 'flavor-chat-ia' ),
                array( 'status' => 500 )
            );
        }

        // Guardar datos del template
        update_post_meta( $template_id, '_vbp_template_data', $datos_origen );

        return new WP_REST_Response(
            array(
                'success'     => true,
                'template_id' => $template_id,
                'message'     => __( 'Template creado', 'flavor-chat-ia' ),
            ),
            200
        );
    }

    /**
     * Aplica un template a un documento
     *
     * @param WP_REST_Request $request Petición REST.
     * @return WP_REST_Response|WP_Error
     */
    public function aplicar_template( $request ) {
        $post_id     = $request->get_param( 'id' );
        $datos       = $request->get_json_params();
        $template_id = isset( $datos['template_id'] ) ? $datos['template_id'] : '';

        if ( empty( $template_id ) ) {
            return new WP_Error(
                'template_invalido',
                __( 'Template inválido', 'flavor-chat-ia' ),
                array( 'status' => 400 )
            );
        }

        $datos_template = null;

        // Verificar si es un template de librería (string ID) o de usuario (numeric ID)
        if ( is_numeric( $template_id ) ) {
            // Template de usuario (guardado en BD)
            $datos_template = get_post_meta( absint( $template_id ), '_vbp_template_data', true );
        } else {
            // Template de librería predefinida
            $library_templates = $this->get_library_templates();
            foreach ( $library_templates as $template ) {
                if ( $template['id'] === $template_id ) {
                    $datos_template = array(
                        'elements' => $template['elements'],
                        'settings' => array(),
                    );
                    break;
                }
            }
        }

        if ( empty( $datos_template ) ) {
            return new WP_Error(
                'template_vacio',
                __( 'El template está vacío o no existe', 'flavor-chat-ia' ),
                array( 'status' => 400 )
            );
        }

        // Aplicar al documento
        $editor   = Flavor_VBP_Editor::get_instance();
        $guardado = $editor->guardar_datos_documento( $post_id, $datos_template );

        if ( $guardado ) {
            return new WP_REST_Response(
                array(
                    'success'  => true,
                    'message'  => __( 'Template aplicado', 'flavor-chat-ia' ),
                    'document' => $datos_template,
                ),
                200
            );
        }

        return new WP_Error(
            'error_aplicar',
            __( 'Error al aplicar el template', 'flavor-chat-ia' ),
            array( 'status' => 500 )
        );
    }

    /**
     * Elimina un template
     *
     * @param WP_REST_Request $request Petición REST.
     * @return WP_REST_Response|WP_Error
     */
    public function eliminar_template( $request ) {
        $template_id = $request->get_param( 'id' );
        $template    = get_post( $template_id );

        if ( ! $template || 'vbp_template' !== $template->post_type ) {
            return new WP_Error(
                'template_no_encontrado',
                __( 'Template no encontrado', 'flavor-chat-ia' ),
                array( 'status' => 404 )
            );
        }

        // Solo el autor o admins pueden eliminar
        if ( $template->post_author !== get_current_user_id() && ! current_user_can( 'delete_others_posts' ) ) {
            return new WP_Error(
                'sin_permisos',
                __( 'No tienes permisos para eliminar este template', 'flavor-chat-ia' ),
                array( 'status' => 403 )
            );
        }

        $eliminado = wp_delete_post( $template_id, true );

        if ( $eliminado ) {
            return new WP_REST_Response(
                array(
                    'success' => true,
                    'message' => __( 'Template eliminado', 'flavor-chat-ia' ),
                ),
                200
            );
        }

        return new WP_Error(
            'error_eliminar',
            __( 'Error al eliminar el template', 'flavor-chat-ia' ),
            array( 'status' => 500 )
        );
    }

    /**
     * Exporta un documento como HTML
     *
     * @param WP_REST_Request $request Petición REST.
     * @return WP_REST_Response
     */
    public function exportar_html( $request ) {
        $post_id = $request->get_param( 'id' );
        $post    = get_post( $post_id );

        if ( ! $post ) {
            return new WP_Error(
                'documento_no_encontrado',
                __( 'Documento no encontrado', 'flavor-chat-ia' ),
                array( 'status' => 404 )
            );
        }

        $editor = Flavor_VBP_Editor::get_instance();
        $datos  = $editor->obtener_datos_documento( $post_id );

        // Generar HTML usando el canvas renderer
        $html = $this->generar_html_exportable( $post, $datos );

        return new WP_REST_Response(
            array(
                'success' => true,
                'html'    => $html,
            ),
            200
        );
    }

    /**
     * Genera el HTML exportable
     *
     * @param WP_Post $post  Post del documento.
     * @param array   $datos Datos del documento.
     * @return string
     */
    private function generar_html_exportable( $post, $datos ) {
        $elementos = isset( $datos['elements'] ) ? $datos['elements'] : array();
        $settings  = isset( $datos['settings'] ) ? $datos['settings'] : array();

        // Obtener design settings
        $design_settings = get_option( 'flavor_design_settings', array() );

        $primary_color    = isset( $design_settings['primary_color'] ) ? $design_settings['primary_color'] : '#3b82f6';
        $secondary_color  = isset( $design_settings['secondary_color'] ) ? $design_settings['secondary_color'] : '#8b5cf6';
        $text_color       = isset( $design_settings['text_color'] ) ? $design_settings['text_color'] : '#1f2937';
        $background_color = isset( $settings['backgroundColor'] ) ? $settings['backgroundColor'] : '#ffffff';

        ob_start();
        ?>
<!DOCTYPE html>
<html lang="<?php echo esc_attr( get_bloginfo( 'language' ) ); ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo esc_html( $post->post_title ); ?></title>
    <style>
        :root {
            --primary-color: <?php echo esc_attr( $primary_color ); ?>;
            --secondary-color: <?php echo esc_attr( $secondary_color ); ?>;
            --text-color: <?php echo esc_attr( $text_color ); ?>;
            --background-color: <?php echo esc_attr( $background_color ); ?>;
        }
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Oxygen, Ubuntu, sans-serif;
            color: var(--text-color);
            background: var(--background-color);
            line-height: 1.6;
        }
        .container { max-width: 1200px; margin: 0 auto; padding: 0 20px; }
        h1, h2, h3, h4, h5, h6 { line-height: 1.2; margin-bottom: 1rem; }
        p { margin-bottom: 1rem; }
        img { max-width: 100%; height: auto; }
        .button {
            display: inline-block;
            padding: 12px 24px;
            background: var(--primary-color);
            color: white;
            text-decoration: none;
            border-radius: 8px;
            font-weight: 600;
            transition: opacity 0.2s;
        }
        .button:hover { opacity: 0.9; }
        section { padding: 80px 40px; }
        .card {
            background: white;
            border-radius: 12px;
            padding: 24px;
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
        }
        .grid { display: grid; gap: 24px; }
        @media (min-width: 768px) {
            .grid-2 { grid-template-columns: repeat(2, 1fr); }
            .grid-3 { grid-template-columns: repeat(3, 1fr); }
            .grid-4 { grid-template-columns: repeat(4, 1fr); }
        }
    </style>
</head>
<body>
<?php
        // Renderizar elementos
        if ( class_exists( 'Flavor_VBP_Canvas' ) ) {
            $canvas = Flavor_VBP_Canvas::get_instance();
            foreach ( $elementos as $elemento ) {
                echo $canvas->renderizar_elemento( $elemento );
            }
        }
?>
</body>
</html>
        <?php
        return ob_get_clean();
    }

    /**
     * Busca posts y páginas para autocompletado de enlaces
     *
     * @param WP_REST_Request $request Petición REST.
     * @return WP_REST_Response
     */
    public function buscar_posts( $request ) {
        $search_term = $request->get_param( 'search' );
        $post_type   = $request->get_param( 'type' );

        // Determinar tipos de post a buscar
        $post_types = array( 'post', 'page' );
        if ( 'post' === $post_type ) {
            $post_types = array( 'post' );
        } elseif ( 'page' === $post_type ) {
            $post_types = array( 'page' );
        }

        // Agregar landing pages si existen
        if ( 'any' === $post_type && post_type_exists( 'flavor_landing' ) ) {
            $post_types[] = 'flavor_landing';
        }

        // Buscar posts
        $query_args = array(
            's'              => $search_term,
            'post_type'      => $post_types,
            'post_status'    => 'publish',
            'posts_per_page' => 10,
            'orderby'        => 'relevance',
        );

        $posts   = get_posts( $query_args );
        $results = array();

        foreach ( $posts as $post ) {
            $icon = '📄';
            if ( 'post' === $post->post_type ) {
                $icon = '📝';
            } elseif ( 'flavor_landing' === $post->post_type ) {
                $icon = '🚀';
            }

            $results[] = array(
                'id'    => $post->ID,
                'title' => $post->post_title,
                'type'  => $post->post_type,
                'url'   => get_permalink( $post->ID ),
                'icon'  => $icon,
            );
        }

        // También buscar categorías si el término tiene al menos 2 caracteres
        if ( strlen( $search_term ) >= 2 ) {
            $categories = get_terms(
                array(
                    'taxonomy'   => 'category',
                    'name__like' => $search_term,
                    'number'     => 5,
                    'hide_empty' => true,
                )
            );

            if ( ! is_wp_error( $categories ) ) {
                foreach ( $categories as $category ) {
                    $results[] = array(
                        'id'    => 'cat_' . $category->term_id,
                        'title' => $category->name,
                        'type'  => 'category',
                        'url'   => get_term_link( $category ),
                        'icon'  => '🏷️',
                    );
                }
            }
        }

        return new WP_REST_Response(
            array(
                'results' => $results,
            ),
            200
        );
    }

    /**
     * Obtiene posts para el widget de blog
     *
     * @param WP_REST_Request $request Petición REST.
     * @return WP_REST_Response
     */
    public function obtener_blog_posts( $request ) {
        $category  = $request->get_param( 'category' );
        $per_page  = min( 20, max( 1, $request->get_param( 'per_page' ) ) );
        $page      = max( 1, $request->get_param( 'page' ) );
        $orderby   = $request->get_param( 'orderby' );
        $order     = strtoupper( $request->get_param( 'order' ) ) === 'ASC' ? 'ASC' : 'DESC';
        $post_type = $request->get_param( 'post_type' );
        $exclude   = $request->get_param( 'exclude' );

        // Validar orderby
        $allowed_orderby = array( 'date', 'title', 'modified', 'rand', 'comment_count', 'menu_order' );
        if ( ! in_array( $orderby, $allowed_orderby, true ) ) {
            $orderby = 'date';
        }

        // Construir argumentos de consulta
        $args = array(
            'post_type'      => sanitize_text_field( $post_type ) ?: 'post',
            'posts_per_page' => $per_page,
            'paged'          => $page,
            'orderby'        => $orderby,
            'order'          => $order,
            'post_status'    => 'publish',
        );

        // Filtrar por categoría
        if ( ! empty( $category ) ) {
            // Puede ser slug o ID
            if ( is_numeric( $category ) ) {
                $args['cat'] = absint( $category );
            } else {
                $args['category_name'] = sanitize_text_field( $category );
            }
        }

        // Excluir posts
        if ( ! empty( $exclude ) ) {
            $exclude_ids = array_map( 'absint', explode( ',', $exclude ) );
            $args['post__not_in'] = $exclude_ids;
        }

        $query = new WP_Query( $args );
        $posts = array();

        if ( $query->have_posts() ) {
            while ( $query->have_posts() ) {
                $query->the_post();

                $post_id = get_the_ID();
                $thumbnail_url = '';

                if ( has_post_thumbnail( $post_id ) ) {
                    $thumbnail_url = get_the_post_thumbnail_url( $post_id, 'medium_large' );
                }

                $categories = array();
                $post_categories = get_the_category( $post_id );
                if ( ! empty( $post_categories ) ) {
                    foreach ( $post_categories as $cat ) {
                        $categories[] = array(
                            'id'   => $cat->term_id,
                            'name' => $cat->name,
                            'slug' => $cat->slug,
                        );
                    }
                }

                $posts[] = array(
                    'id'           => $post_id,
                    'title'        => get_the_title(),
                    'excerpt'      => wp_trim_words( get_the_excerpt(), 20, '...' ),
                    'content'      => get_the_content(),
                    'url'          => get_permalink( $post_id ),
                    'date'         => get_the_date( 'c' ),
                    'date_display' => get_the_date(),
                    'author'       => array(
                        'id'     => get_the_author_meta( 'ID' ),
                        'name'   => get_the_author(),
                        'avatar' => get_avatar_url( get_the_author_meta( 'ID' ), array( 'size' => 48 ) ),
                    ),
                    'thumbnail'    => $thumbnail_url,
                    'categories'   => $categories,
                );
            }
            wp_reset_postdata();
        }

        return new WP_REST_Response(
            array(
                'posts'       => $posts,
                'total'       => $query->found_posts,
                'total_pages' => $query->max_num_pages,
                'page'        => $page,
                'per_page'    => $per_page,
            ),
            200
        );
    }

    /**
     * Obtiene categorías para el selector
     *
     * @param WP_REST_Request $request Petición REST.
     * @return WP_REST_Response
     */
    public function obtener_categorias( $request ) {
        $categories = get_categories(
            array(
                'hide_empty' => false,
                'orderby'    => 'name',
                'order'      => 'ASC',
            )
        );

        $result = array();

        foreach ( $categories as $category ) {
            $result[] = array(
                'id'    => $category->term_id,
                'name'  => $category->name,
                'slug'  => $category->slug,
                'count' => $category->count,
            );
        }

        return new WP_REST_Response(
            array(
                'categories' => $result,
            ),
            200
        );
    }
}

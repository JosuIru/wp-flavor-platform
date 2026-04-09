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
     * Meta key unificada para datos VBP (misma que usa el Editor)
     * IMPORTANTE: Usar siempre esta constante, NO self::META_DATA (legacy)
     *
     * @var string
     */
    const META_DATA = '_flavor_vbp_data';

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

        // ============================================
        // Endpoints IA para generación de contenido
        // ============================================

        // Generar sección con IA
        register_rest_route(
            self::NAMESPACE,
            '/ai/generate-section',
            array(
                'methods'             => WP_REST_Server::CREATABLE,
                'callback'            => array( $this, 'generar_seccion_ia' ),
                'permission_callback' => array( $this, 'verificar_permiso_escritura' ),
                'args'                => array(
                    'type'     => array(
                        'required'          => true,
                        'sanitize_callback' => 'sanitize_text_field',
                        'description'       => __( 'Tipo de sección: hero, features, faq, testimonials, cta, etc.', FLAVOR_PLATFORM_TEXT_DOMAIN ),
                    ),
                    'context'  => array(
                        'required'          => false,
                        'sanitize_callback' => 'sanitize_textarea_field',
                        'description'       => __( 'Contexto adicional: industria, tono, público objetivo.', FLAVOR_PLATFORM_TEXT_DOMAIN ),
                    ),
                    'language' => array(
                        'default'           => 'es',
                        'sanitize_callback' => 'sanitize_text_field',
                    ),
                    'style'    => array(
                        'default'           => 'professional',
                        'sanitize_callback' => 'sanitize_text_field',
                        'description'       => __( 'Estilo: professional, casual, creative, formal.', FLAVOR_PLATFORM_TEXT_DOMAIN ),
                    ),
                ),
            )
        );

        // Mejorar texto con IA
        register_rest_route(
            self::NAMESPACE,
            '/ai/improve-text',
            array(
                'methods'             => WP_REST_Server::CREATABLE,
                'callback'            => array( $this, 'mejorar_texto_ia' ),
                'permission_callback' => array( $this, 'verificar_permiso_escritura' ),
                'args'                => array(
                    'text'   => array(
                        'required'          => true,
                        'sanitize_callback' => 'sanitize_textarea_field',
                    ),
                    'action' => array(
                        'required'          => true,
                        'sanitize_callback' => 'sanitize_text_field',
                        'description'       => __( 'Acción: expand, shorten, rephrase, formal, casual, seo.', FLAVOR_PLATFORM_TEXT_DOMAIN ),
                    ),
                ),
            )
        );

        // Traducir contenido con IA
        register_rest_route(
            self::NAMESPACE,
            '/ai/translate',
            array(
                'methods'             => WP_REST_Server::CREATABLE,
                'callback'            => array( $this, 'traducir_contenido_ia' ),
                'permission_callback' => array( $this, 'verificar_permiso_escritura' ),
                'args'                => array(
                    'text'      => array(
                        'required'          => true,
                        'sanitize_callback' => 'sanitize_textarea_field',
                    ),
                    'from_lang' => array(
                        'default'           => 'auto',
                        'sanitize_callback' => 'sanitize_text_field',
                    ),
                    'to_lang'   => array(
                        'required'          => true,
                        'sanitize_callback' => 'sanitize_text_field',
                    ),
                ),
            )
        );

        // Generar variaciones de contenido
        register_rest_route(
            self::NAMESPACE,
            '/ai/generate-variations',
            array(
                'methods'             => WP_REST_Server::CREATABLE,
                'callback'            => array( $this, 'generar_variaciones_ia' ),
                'permission_callback' => array( $this, 'verificar_permiso_escritura' ),
                'args'                => array(
                    'text'  => array(
                        'required'          => true,
                        'sanitize_callback' => 'sanitize_textarea_field',
                    ),
                    'count' => array(
                        'default'           => 3,
                        'sanitize_callback' => 'absint',
                    ),
                    'type'  => array(
                        'default'           => 'headlines',
                        'sanitize_callback' => 'sanitize_text_field',
                        'description'       => __( 'Tipo: headlines, descriptions, cta, taglines.', FLAVOR_PLATFORM_TEXT_DOMAIN ),
                    ),
                ),
            )
        );

        // Batch Operations para Claude Code
        register_rest_route(
            self::NAMESPACE,
            '/claude/batch',
            array(
                'methods'             => WP_REST_Server::CREATABLE,
                'callback'            => array( $this, 'ejecutar_batch_operations' ),
                'permission_callback' => array( $this, 'verificar_api_key_claude' ),
                'args'                => array(
                    'operations' => array(
                        'required'    => true,
                        'type'        => 'array',
                        'description' => __( 'Array de operaciones a ejecutar', FLAVOR_PLATFORM_TEXT_DOMAIN ),
                    ),
                    'stop_on_error' => array(
                        'default'     => false,
                        'type'        => 'boolean',
                        'description' => __( 'Detener si hay error en alguna operación', FLAVOR_PLATFORM_TEXT_DOMAIN ),
                    ),
                ),
            )
        );

        // Batch Create - Crear múltiples páginas
        register_rest_route(
            self::NAMESPACE,
            '/claude/batch/pages',
            array(
                'methods'             => WP_REST_Server::CREATABLE,
                'callback'            => array( $this, 'crear_paginas_batch' ),
                'permission_callback' => array( $this, 'verificar_api_key_claude' ),
                'args'                => array(
                    'pages' => array(
                        'required'    => true,
                        'type'        => 'array',
                        'description' => __( 'Array de páginas a crear', FLAVOR_PLATFORM_TEXT_DOMAIN ),
                    ),
                ),
            )
        );

        // Batch Update - Actualizar múltiples elementos
        register_rest_route(
            self::NAMESPACE,
            '/claude/batch/elements',
            array(
                'methods'             => WP_REST_Server::CREATABLE,
                'callback'            => array( $this, 'actualizar_elementos_batch' ),
                'permission_callback' => array( $this, 'verificar_api_key_claude' ),
                'args'                => array(
                    'page_id'  => array(
                        'required' => true,
                        'type'     => 'integer',
                    ),
                    'elements' => array(
                        'required' => true,
                        'type'     => 'array',
                    ),
                ),
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
                __( 'Documento no encontrado', FLAVOR_PLATFORM_TEXT_DOMAIN ),
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
                __( 'No se recibieron datos para guardar', FLAVOR_PLATFORM_TEXT_DOMAIN ),
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

            // Registrar en audit log
            if ( function_exists( 'vbp_audit_log' ) ) {
                $elementos_count = isset( $datos['elements'] ) ? count( $datos['elements'] ) : 0;
                vbp_audit_log( 'page_updated', array(
                    'post_id' => $post_id,
                    'details' => array(
                        'elements_count' => $elementos_count,
                        'has_settings'   => isset( $datos['settings'] ),
                    ),
                ) );
            }

            return new WP_REST_Response(
                array(
                    'success'   => true,
                    'message'   => __( 'Documento guardado', FLAVOR_PLATFORM_TEXT_DOMAIN ),
                    'updatedAt' => current_time( 'mysql' ),
                ),
                200
            );
        }

        return new WP_Error(
            'error_guardado',
            __( 'Error al guardar el documento', FLAVOR_PLATFORM_TEXT_DOMAIN ),
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
            // Registrar en audit log
            if ( function_exists( 'vbp_audit_log' ) ) {
                vbp_audit_log( 'revision_restored', array(
                    'post_id' => $post_id,
                    'details' => array(
                        'revision_id' => $revision_id,
                    ),
                ) );
            }

            return new WP_REST_Response(
                array(
                    'success' => true,
                    'message' => __( 'Revisión restaurada', FLAVOR_PLATFORM_TEXT_DOMAIN ),
                ),
                200
            );
        }

        return new WP_Error(
            'error_restaurar',
            __( 'Error al restaurar la revisión', FLAVOR_PLATFORM_TEXT_DOMAIN ),
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
                'posts_per_page' => 100,
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
                __( 'Datos inválidos', FLAVOR_PLATFORM_TEXT_DOMAIN ),
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
                __( 'Error al crear el template', FLAVOR_PLATFORM_TEXT_DOMAIN ),
                array( 'status' => 500 )
            );
        }

        // Guardar datos del template
        update_post_meta( $template_id, '_vbp_template_data', $datos_origen );

        return new WP_REST_Response(
            array(
                'success'     => true,
                'template_id' => $template_id,
                'message'     => __( 'Template creado', FLAVOR_PLATFORM_TEXT_DOMAIN ),
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
                __( 'Template inválido', FLAVOR_PLATFORM_TEXT_DOMAIN ),
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
                __( 'El template está vacío o no existe', FLAVOR_PLATFORM_TEXT_DOMAIN ),
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
                    'message'  => __( 'Template aplicado', FLAVOR_PLATFORM_TEXT_DOMAIN ),
                    'document' => $datos_template,
                ),
                200
            );
        }

        return new WP_Error(
            'error_aplicar',
            __( 'Error al aplicar el template', FLAVOR_PLATFORM_TEXT_DOMAIN ),
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
                __( 'Template no encontrado', FLAVOR_PLATFORM_TEXT_DOMAIN ),
                array( 'status' => 404 )
            );
        }

        // Solo el autor o admins pueden eliminar
        if ( $template->post_author !== get_current_user_id() && ! current_user_can( 'delete_others_posts' ) ) {
            return new WP_Error(
                'sin_permisos',
                __( 'No tienes permisos para eliminar este template', FLAVOR_PLATFORM_TEXT_DOMAIN ),
                array( 'status' => 403 )
            );
        }

        $eliminado = wp_delete_post( $template_id, true );

        if ( $eliminado ) {
            return new WP_REST_Response(
                array(
                    'success' => true,
                    'message' => __( 'Template eliminado', FLAVOR_PLATFORM_TEXT_DOMAIN ),
                ),
                200
            );
        }

        return new WP_Error(
            'error_eliminar',
            __( 'Error al eliminar el template', FLAVOR_PLATFORM_TEXT_DOMAIN ),
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
                __( 'Documento no encontrado', FLAVOR_PLATFORM_TEXT_DOMAIN ),
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
        $per_page  = min( 12, max( 1, $request->get_param( 'per_page' ) ) ); // Máximo reducido a 12
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

        // SEGURIDAD: Lista blanca de post types públicos permitidos
        $allowed_post_types = apply_filters(
            'flavor_vbp_allowed_blog_post_types',
            array( 'post', 'page', 'flavor_evento', 'flavor_noticia' )
        );

        $sanitized_post_type = sanitize_text_field( $post_type ) ?: 'post';

        // Verificar que el post_type está en la lista blanca Y es público
        if ( ! in_array( $sanitized_post_type, $allowed_post_types, true ) ) {
            $sanitized_post_type = 'post';
        }

        // Doble verificación: asegurar que el post type es realmente público
        $post_type_obj = get_post_type_object( $sanitized_post_type );
        if ( ! $post_type_obj || ! $post_type_obj->public ) {
            $sanitized_post_type = 'post';
        }

        // Construir argumentos de consulta
        $args = array(
            'post_type'      => $sanitized_post_type,
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

                // SEGURIDAD: No exponer contenido completo en endpoint público
                // Solo devolver excerpt para prevenir scraping de contenido
                $posts[] = array(
                    'id'           => $post_id,
                    'title'        => get_the_title(),
                    'excerpt'      => wp_trim_words( get_the_excerpt(), 30, '...' ),
                    'url'          => get_permalink( $post_id ),
                    'date'         => get_the_date( 'c' ),
                    'date_display' => get_the_date(),
                    'author'       => array(
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

    // ============================================
    // Métodos de IA para generación de contenido
    // ============================================

    /**
     * Genera una sección completa con IA
     *
     * @param WP_REST_Request $request Petición REST.
     * @return WP_REST_Response|WP_Error
     */
    public function generar_seccion_ia( $request ) {
        $tipo     = $request->get_param( 'type' );
        $contexto = $request->get_param( 'context' ) ?? '';
        $idioma   = $request->get_param( 'language' ) ?? 'es';
        $estilo   = $request->get_param( 'style' ) ?? 'professional';

        // Verificar si IA está disponible
        if ( ! class_exists( 'Flavor_VBP_AI_Content' ) ) {
            // Usar contenido de plantilla predefinido si no hay IA
            $contenido_plantilla = $this->get_seccion_plantilla( $tipo, $contexto );
            return new WP_REST_Response( $contenido_plantilla, 200 );
        }

        try {
            $generador_ia = Flavor_VBP_AI_Content::get_instance();
            $resultado    = $generador_ia->generar_seccion( $tipo, array(
                'context'  => $contexto,
                'language' => $idioma,
                'style'    => $estilo,
            ) );

            return new WP_REST_Response( $resultado, 200 );

        } catch ( Exception $e ) {
            return new WP_Error(
                'ai_error',
                $e->getMessage(),
                array( 'status' => 500 )
            );
        }
    }

    /**
     * Obtiene contenido de plantilla predefinido para una sección
     *
     * @param string $tipo    Tipo de sección.
     * @param string $contexto Contexto adicional.
     * @return array
     */
    private function get_seccion_plantilla( $tipo, $contexto = '' ) {
        $plantillas = array(
            'hero' => array(
                'titulo'       => __( 'Bienvenido a nuestra plataforma', FLAVOR_PLATFORM_TEXT_DOMAIN ),
                'subtitulo'    => __( 'La solución que estabas buscando para potenciar tu negocio', FLAVOR_PLATFORM_TEXT_DOMAIN ),
                'boton_texto'  => __( 'Comenzar ahora', FLAVOR_PLATFORM_TEXT_DOMAIN ),
                'boton_url'    => '#contacto',
            ),
            'features' => array(
                'titulo' => __( 'Nuestras características', FLAVOR_PLATFORM_TEXT_DOMAIN ),
                'items'  => array(
                    array(
                        'icono'       => '⚡',
                        'titulo'      => __( 'Rápido y eficiente', FLAVOR_PLATFORM_TEXT_DOMAIN ),
                        'descripcion' => __( 'Implementación en minutos con resultados inmediatos.', FLAVOR_PLATFORM_TEXT_DOMAIN ),
                    ),
                    array(
                        'icono'       => '🔒',
                        'titulo'      => __( 'Seguro y confiable', FLAVOR_PLATFORM_TEXT_DOMAIN ),
                        'descripcion' => __( 'Protección de datos con los más altos estándares.', FLAVOR_PLATFORM_TEXT_DOMAIN ),
                    ),
                    array(
                        'icono'       => '📱',
                        'titulo'      => __( '100% responsive', FLAVOR_PLATFORM_TEXT_DOMAIN ),
                        'descripcion' => __( 'Funciona perfectamente en todos los dispositivos.', FLAVOR_PLATFORM_TEXT_DOMAIN ),
                    ),
                ),
            ),
            'faq' => array(
                'titulo' => __( 'Preguntas frecuentes', FLAVOR_PLATFORM_TEXT_DOMAIN ),
                'items'  => array(
                    array(
                        'pregunta'  => __( '¿Cómo funciona el servicio?', FLAVOR_PLATFORM_TEXT_DOMAIN ),
                        'respuesta' => __( 'Nuestro servicio es muy sencillo de usar. Solo tienes que registrarte y empezar a disfrutar de todas las funcionalidades.', FLAVOR_PLATFORM_TEXT_DOMAIN ),
                    ),
                    array(
                        'pregunta'  => __( '¿Puedo cancelar en cualquier momento?', FLAVOR_PLATFORM_TEXT_DOMAIN ),
                        'respuesta' => __( 'Sí, puedes cancelar tu suscripción cuando quieras sin penalizaciones ni compromisos.', FLAVOR_PLATFORM_TEXT_DOMAIN ),
                    ),
                    array(
                        'pregunta'  => __( '¿Ofrecen soporte técnico?', FLAVOR_PLATFORM_TEXT_DOMAIN ),
                        'respuesta' => __( 'Contamos con un equipo de soporte disponible 24/7 para ayudarte con cualquier duda o incidencia.', FLAVOR_PLATFORM_TEXT_DOMAIN ),
                    ),
                ),
            ),
            'testimonials' => array(
                'titulo' => __( 'Lo que dicen nuestros clientes', FLAVOR_PLATFORM_TEXT_DOMAIN ),
                'items'  => array(
                    array(
                        'texto' => __( 'Excelente servicio, ha superado todas nuestras expectativas. Lo recomendamos sin duda.', FLAVOR_PLATFORM_TEXT_DOMAIN ),
                        'autor' => 'María García',
                        'cargo' => 'CEO, Empresa ABC',
                    ),
                    array(
                        'texto' => __( 'La mejor inversión que hemos hecho. El ROI fue inmediato y el soporte es increíble.', FLAVOR_PLATFORM_TEXT_DOMAIN ),
                        'autor' => 'Carlos López',
                        'cargo' => 'Director de Marketing',
                    ),
                ),
            ),
            'cta' => array(
                'titulo'      => __( '¿Listo para empezar?', FLAVOR_PLATFORM_TEXT_DOMAIN ),
                'subtitulo'   => __( 'Únete a miles de usuarios que ya confían en nosotros', FLAVOR_PLATFORM_TEXT_DOMAIN ),
                'boton_texto' => __( 'Empezar gratis', FLAVOR_PLATFORM_TEXT_DOMAIN ),
                'boton_url'   => '#',
            ),
        );

        return isset( $plantillas[ $tipo ] )
            ? array( 'success' => true, 'data' => $plantillas[ $tipo ], 'source' => 'template' )
            : array( 'success' => false, 'error' => __( 'Tipo de sección no reconocido', FLAVOR_PLATFORM_TEXT_DOMAIN ) );
    }

    /**
     * Mejora texto con IA
     *
     * @param WP_REST_Request $request Petición REST.
     * @return WP_REST_Response|WP_Error
     */
    public function mejorar_texto_ia( $request ) {
        $texto  = $request->get_param( 'text' );
        $accion = $request->get_param( 'action' );

        if ( empty( $texto ) ) {
            return new WP_Error(
                'texto_vacio',
                __( 'El texto no puede estar vacío', FLAVOR_PLATFORM_TEXT_DOMAIN ),
                array( 'status' => 400 )
            );
        }

        // Verificar si IA está disponible
        if ( ! class_exists( 'Flavor_VBP_AI_Content' ) ) {
            // Devolver texto original con mensaje
            return new WP_REST_Response(
                array(
                    'success'  => false,
                    'original' => $texto,
                    'message'  => __( 'La función de IA no está disponible. Por favor, configure la API de IA.', FLAVOR_PLATFORM_TEXT_DOMAIN ),
                ),
                200
            );
        }

        try {
            $generador_ia     = Flavor_VBP_AI_Content::get_instance();
            $texto_mejorado = $generador_ia->mejorar_texto( $texto, $accion );

            return new WP_REST_Response(
                array(
                    'success'  => true,
                    'original' => $texto,
                    'improved' => $texto_mejorado,
                    'action'   => $accion,
                ),
                200
            );

        } catch ( Exception $e ) {
            return new WP_Error(
                'ai_error',
                $e->getMessage(),
                array( 'status' => 500 )
            );
        }
    }

    /**
     * Traduce contenido con IA
     *
     * @param WP_REST_Request $request Petición REST.
     * @return WP_REST_Response|WP_Error
     */
    public function traducir_contenido_ia( $request ) {
        $texto     = $request->get_param( 'text' );
        $desde     = $request->get_param( 'from_lang' ) ?? 'auto';
        $hacia     = $request->get_param( 'to_lang' );

        if ( empty( $texto ) ) {
            return new WP_Error(
                'texto_vacio',
                __( 'El texto no puede estar vacío', FLAVOR_PLATFORM_TEXT_DOMAIN ),
                array( 'status' => 400 )
            );
        }

        // Verificar si IA o multilingual está disponible
        if ( class_exists( 'Flavor_Multilingual' ) ) {
            try {
                $multilingual    = Flavor_Multilingual::get_instance();
                $texto_traducido = $multilingual->traducir( $texto, $desde, $hacia );

                return new WP_REST_Response(
                    array(
                        'success'    => true,
                        'original'   => $texto,
                        'translated' => $texto_traducido,
                        'from_lang'  => $desde,
                        'to_lang'    => $hacia,
                    ),
                    200
                );
            } catch ( Exception $e ) {
                return new WP_Error(
                    'translation_error',
                    $e->getMessage(),
                    array( 'status' => 500 )
                );
            }
        }

        // Fallback: usar IA directamente
        if ( class_exists( 'Flavor_VBP_AI_Content' ) ) {
            try {
                $generador_ia    = Flavor_VBP_AI_Content::get_instance();
                $texto_traducido = $generador_ia->traducir( $texto, $hacia, $desde );

                return new WP_REST_Response(
                    array(
                        'success'    => true,
                        'original'   => $texto,
                        'translated' => $texto_traducido,
                        'from_lang'  => $desde,
                        'to_lang'    => $hacia,
                    ),
                    200
                );
            } catch ( Exception $e ) {
                return new WP_Error(
                    'ai_error',
                    $e->getMessage(),
                    array( 'status' => 500 )
                );
            }
        }

        return new WP_Error(
            'service_unavailable',
            __( 'El servicio de traducción no está disponible.', FLAVOR_PLATFORM_TEXT_DOMAIN ),
            array( 'status' => 503 )
        );
    }

    /**
     * Genera variaciones de contenido con IA
     *
     * @param WP_REST_Request $request Petición REST.
     * @return WP_REST_Response|WP_Error
     */
    public function generar_variaciones_ia( $request ) {
        $texto    = $request->get_param( 'text' );
        $cantidad = $request->get_param( 'count' ) ?? 3;
        $tipo     = $request->get_param( 'type' ) ?? 'headlines';

        if ( empty( $texto ) ) {
            return new WP_Error(
                'texto_vacio',
                __( 'El texto no puede estar vacío', FLAVOR_PLATFORM_TEXT_DOMAIN ),
                array( 'status' => 400 )
            );
        }

        $cantidad = max( 1, min( 10, intval( $cantidad ) ) );

        // Verificar si IA está disponible
        if ( ! class_exists( 'Flavor_VBP_AI_Content' ) ) {
            // Generar variaciones simples sin IA
            $variaciones = $this->generar_variaciones_simples( $texto, $cantidad, $tipo );

            return new WP_REST_Response(
                array(
                    'success'    => true,
                    'original'   => $texto,
                    'variations' => $variaciones,
                    'type'       => $tipo,
                    'source'     => 'simple',
                ),
                200
            );
        }

        try {
            $generador_ia = Flavor_VBP_AI_Content::get_instance();
            $variaciones  = $generador_ia->generar_variaciones( $texto, $cantidad, $tipo );

            return new WP_REST_Response(
                array(
                    'success'    => true,
                    'original'   => $texto,
                    'variations' => $variaciones,
                    'type'       => $tipo,
                    'source'     => 'ai',
                ),
                200
            );

        } catch ( Exception $e ) {
            return new WP_Error(
                'ai_error',
                $e->getMessage(),
                array( 'status' => 500 )
            );
        }
    }

    /**
     * Genera variaciones simples sin IA
     *
     * @param string $texto    Texto original.
     * @param int    $cantidad Cantidad de variaciones.
     * @param string $tipo     Tipo de variación.
     * @return array
     */
    private function generar_variaciones_simples( $texto, $cantidad, $tipo ) {
        $variaciones = array();

        $prefijos_titulo = array(
            __( 'Descubre', FLAVOR_PLATFORM_TEXT_DOMAIN ),
            __( 'Conoce', FLAVOR_PLATFORM_TEXT_DOMAIN ),
            __( 'Explora', FLAVOR_PLATFORM_TEXT_DOMAIN ),
            __( 'Aprende sobre', FLAVOR_PLATFORM_TEXT_DOMAIN ),
            __( 'Todo sobre', FLAVOR_PLATFORM_TEXT_DOMAIN ),
        );

        $sufijos_cta = array(
            __( 'ahora', FLAVOR_PLATFORM_TEXT_DOMAIN ),
            __( 'hoy', FLAVOR_PLATFORM_TEXT_DOMAIN ),
            __( 'ya', FLAVOR_PLATFORM_TEXT_DOMAIN ),
            __( 'gratis', FLAVOR_PLATFORM_TEXT_DOMAIN ),
            __( 'sin compromiso', FLAVOR_PLATFORM_TEXT_DOMAIN ),
        );

        for ( $i = 0; $i < $cantidad; $i++ ) {
            switch ( $tipo ) {
                case 'headlines':
                    $prefijo       = $prefijos_titulo[ $i % count( $prefijos_titulo ) ];
                    $variaciones[] = $prefijo . ' ' . lcfirst( $texto );
                    break;

                case 'cta':
                    $sufijo        = $sufijos_cta[ $i % count( $sufijos_cta ) ];
                    $variaciones[] = $texto . ' ' . $sufijo;
                    break;

                case 'descriptions':
                case 'taglines':
                default:
                    $variaciones[] = $texto . ' (variación ' . ( $i + 1 ) . ')';
                    break;
            }
        }

        return $variaciones;
    }

    /**
     * Verificar API Key de Claude
     *
     * @param WP_REST_Request $request Petición REST.
     * @return bool
     */
    public function verificar_api_key_claude( $request ) {
        $api_key = $request->get_header( 'X-VBP-Key' );

        // Obtener key desde opciones (configurable) con fallback seguro
        $settings = get_option( 'flavor_vbp_settings', array() );
        $valid_key = isset( $settings['api_key'] ) && ! empty( $settings['api_key'] )
            ? $settings['api_key']
            : wp_hash( 'flavor-vbp-' . NONCE_SALT ); // Genera key única por instalación

        // En desarrollo local, permitir key legacy (solo si está explícitamente habilitado)
        $allow_legacy = defined( 'FLAVOR_VBP_ALLOW_LEGACY_KEY' ) && FLAVOR_VBP_ALLOW_LEGACY_KEY;
        if ( $allow_legacy && $api_key === 'flavor-vbp-2024' ) {
            // Log de advertencia
            if ( function_exists( 'flavor_log_debug' ) ) {
                flavor_log_debug( 'VBP API: Usando key legacy - configure una key segura en Ajustes > VBP', 'VBP-Security' );
            }
            return true;
        }

        if ( $api_key === $valid_key ) {
            return true;
        }

        // Fallback: verificar permisos normales (usuario autenticado)
        return current_user_can( 'edit_posts' );
    }

    /**
     * Obtener la API key actual (para mostrar en ajustes)
     *
     * @return string
     */
    public static function obtener_api_key() {
        $settings = get_option( 'flavor_vbp_settings', array() );
        if ( isset( $settings['api_key'] ) && ! empty( $settings['api_key'] ) ) {
            return $settings['api_key'];
        }
        return wp_hash( 'flavor-vbp-' . NONCE_SALT );
    }

    /**
     * Regenerar API key
     *
     * @return string Nueva key
     */
    public static function regenerar_api_key() {
        $nueva_key = wp_generate_password( 32, false, false );
        $settings = get_option( 'flavor_vbp_settings', array() );
        $settings['api_key'] = $nueva_key;
        update_option( 'flavor_vbp_settings', $settings );
        return $nueva_key;
    }

    /**
     * Obtiene el documento VBP normalizado para operaciones batch.
     *
     * @param int $post_id ID del post.
     * @return array
     */
    private function obtener_documento_batch( $post_id ) {
        if ( class_exists( 'Flavor_VBP_Editor' ) ) {
            return Flavor_VBP_Editor::get_instance()->obtener_datos_documento( $post_id );
        }

        $documento = get_post_meta( $post_id, self::META_DATA, true );
        if ( is_array( $documento ) ) {
            return $documento;
        }

        return array(
            'version'  => '2.2.4',
            'elements' => array(),
            'settings' => array(
                'pageWidth'       => 1200,
                'backgroundColor' => '#ffffff',
                'customCss'       => '',
            ),
        );
    }

    /**
     * Guarda un documento VBP desde operaciones batch.
     *
     * @param int   $post_id   ID del post.
     * @param array $documento Documento a guardar.
     * @return bool
     */
    private function guardar_documento_batch( $post_id, $documento ) {
        if ( ! is_array( $documento ) ) {
            return false;
        }

        if ( class_exists( 'Flavor_VBP_Editor' ) ) {
            $guardado_editor = Flavor_VBP_Editor::get_instance()->guardar_datos_documento( $post_id, $documento );
            if ( $guardado_editor ) {
                return true;
            }
        }

        // Las rutas batch autenticadas por API key no siempre tienen un usuario WP activo,
        // así que el guardado debe poder persistir el documento aunque falle current_user_can().
        $documento['updatedAt'] = current_time( 'mysql' );
        if ( empty( $documento['version'] ) || ! is_string( $documento['version'] ) ) {
            $documento['version'] = defined( 'Flavor_VBP_Editor::VERSION' ) ? Flavor_VBP_Editor::VERSION : '2.2.4';
        }

        $resultado = update_post_meta( $post_id, self::META_DATA, $documento );
        update_post_meta( $post_id, '_flavor_vbp_version', $documento['version'] );

        if ( false !== $resultado ) {
            do_action( 'vbp_content_saved', $post_id, $documento );
        }

        return false !== $resultado;
    }

    /**
     * Normaliza el payload batch al esquema que usa el editor.
     *
     * @param array $payload        Bloques o documento parcial.
     * @param array $documento_base Documento actual.
     * @return array
     */
    private function normalizar_documento_batch( $payload, $documento_base = array() ) {
        $documento = wp_parse_args(
            is_array( $documento_base ) ? $documento_base : array(),
            array(
                'version'  => '2.2.4',
                'elements' => array(),
                'settings' => array(
                    'pageWidth'       => 1200,
                    'backgroundColor' => '#ffffff',
                    'customCss'       => '',
                ),
            )
        );

        if ( ! is_array( $payload ) ) {
            return $documento;
        }

        if ( isset( $payload['elements'] ) || isset( $payload['settings'] ) ) {
            if ( isset( $payload['elements'] ) && is_array( $payload['elements'] ) ) {
                $documento['elements'] = $payload['elements'];
            }

            if ( isset( $payload['settings'] ) && is_array( $payload['settings'] ) ) {
                $documento['settings'] = array_merge( $documento['settings'], $payload['settings'] );
            }

            if ( isset( $payload['version'] ) && is_string( $payload['version'] ) ) {
                $documento['version'] = $payload['version'];
            }

            return $documento;
        }

        $documento['elements'] = $payload;
        return $documento;
    }

    /**
     * Ejecutar batch de operaciones
     *
     * @param WP_REST_Request $request Petición REST.
     * @return WP_REST_Response|WP_Error
     */
    public function ejecutar_batch_operations( $request ) {
        $operations    = $request->get_param( 'operations' );
        $stop_on_error = $request->get_param( 'stop_on_error' );

        if ( ! is_array( $operations ) || empty( $operations ) ) {
            return new WP_Error(
                'operaciones_invalidas',
                __( 'Se requiere un array de operaciones', FLAVOR_PLATFORM_TEXT_DOMAIN ),
                array( 'status' => 400 )
            );
        }

        $resultados        = array();
        $operaciones_total = count( $operations );
        $operaciones_ok    = 0;
        $operaciones_error = 0;

        foreach ( $operations as $indice => $operacion ) {
            $tipo    = sanitize_text_field( $operacion['type'] ?? '' );
            $datos   = $operacion['data'] ?? array();
            $op_id   = $operacion['id'] ?? "op_$indice";

            $resultado = array(
                'id'      => $op_id,
                'type'    => $tipo,
                'success' => false,
            );

            try {
                switch ( $tipo ) {
                    case 'create_page':
                        $resultado = $this->batch_crear_pagina( $datos, $op_id );
                        break;

                    case 'update_page':
                        $resultado = $this->batch_actualizar_pagina( $datos, $op_id );
                        break;

                    case 'delete_page':
                        $resultado = $this->batch_eliminar_pagina( $datos, $op_id );
                        break;

                    case 'add_element':
                        $resultado = $this->batch_agregar_elemento( $datos, $op_id );
                        break;

                    case 'update_element':
                        $resultado = $this->batch_actualizar_elemento( $datos, $op_id );
                        break;

                    case 'delete_element':
                        $resultado = $this->batch_eliminar_elemento( $datos, $op_id );
                        break;

                    case 'apply_styles':
                        $resultado = $this->batch_aplicar_estilos( $datos, $op_id );
                        break;

                    case 'publish_page':
                        $resultado = $this->batch_publicar_pagina( $datos, $op_id );
                        break;

                    default:
                        $resultado['error'] = __( 'Tipo de operación no soportado', FLAVOR_PLATFORM_TEXT_DOMAIN );
                }

                if ( $resultado['success'] ) {
                    $operaciones_ok++;
                } else {
                    $operaciones_error++;
                    if ( $stop_on_error ) {
                        $resultado['stopped_at'] = $indice;
                        $resultados[] = $resultado;
                        break;
                    }
                }

            } catch ( Exception $e ) {
                $resultado['success'] = false;
                $resultado['error']   = $e->getMessage();
                $operaciones_error++;

                if ( $stop_on_error ) {
                    $resultado['stopped_at'] = $indice;
                    $resultados[] = $resultado;
                    break;
                }
            }

            $resultados[] = $resultado;
        }

        return new WP_REST_Response(
            array(
                'success'  => $operaciones_error === 0,
                'summary'  => array(
                    'total'     => $operaciones_total,
                    'success'   => $operaciones_ok,
                    'failed'    => $operaciones_error,
                    'processed' => count( $resultados ),
                ),
                'results'  => $resultados,
            ),
            200
        );
    }

    /**
     * Batch: Crear página
     */
    private function batch_crear_pagina( $datos, $op_id ) {
        $titulo = sanitize_text_field( $datos['title'] ?? '' );
        $slug   = sanitize_title( $datos['slug'] ?? $titulo );
        $blocks = $datos['blocks'] ?? array();
        $status = sanitize_text_field( $datos['status'] ?? 'draft' );

        if ( empty( $titulo ) ) {
            return array(
                'id'      => $op_id,
                'type'    => 'create_page',
                'success' => false,
                'error'   => __( 'Se requiere un título', FLAVOR_PLATFORM_TEXT_DOMAIN ),
            );
        }

        $post_id = wp_insert_post(
            array(
                'post_title'  => $titulo,
                'post_name'   => $slug,
                'post_type'   => 'flavor_landing',
                'post_status' => $status,
            )
        );

        if ( is_wp_error( $post_id ) ) {
            return array(
                'id'      => $op_id,
                'type'    => 'create_page',
                'success' => false,
                'error'   => $post_id->get_error_message(),
            );
        }

        // Guardar documento VBP con el mismo esquema que consume el editor.
        if ( ! empty( $blocks ) ) {
            $documento = $this->normalizar_documento_batch( $blocks );
            $this->guardar_documento_batch( $post_id, $documento );
        }

        return array(
            'id'      => $op_id,
            'type'    => 'create_page',
            'success' => true,
            'page_id' => $post_id,
            'url'     => get_permalink( $post_id ),
        );
    }

    /**
     * Batch: Actualizar página
     */
    private function batch_actualizar_pagina( $datos, $op_id ) {
        $page_id = absint( $datos['page_id'] ?? 0 );
        $blocks  = $datos['blocks'] ?? null;

        if ( ! $page_id || ! get_post( $page_id ) ) {
            return array(
                'id'      => $op_id,
                'type'    => 'update_page',
                'success' => false,
                'error'   => __( 'Página no encontrada', FLAVOR_PLATFORM_TEXT_DOMAIN ),
            );
        }

        // Actualizar campos de post si se proporcionan
        $actualizar = array( 'ID' => $page_id );
        if ( isset( $datos['title'] ) ) {
            $actualizar['post_title'] = sanitize_text_field( $datos['title'] );
        }
        if ( isset( $datos['status'] ) ) {
            $actualizar['post_status'] = sanitize_text_field( $datos['status'] );
        }

        if ( count( $actualizar ) > 1 ) {
            wp_update_post( $actualizar );
        }

        // Actualizar documento VBP con el mismo esquema que consume el editor.
        if ( $blocks !== null ) {
            $documento = $this->obtener_documento_batch( $page_id );
            $documento = $this->normalizar_documento_batch( $blocks, $documento );
            $this->guardar_documento_batch( $page_id, $documento );
        }

        return array(
            'id'      => $op_id,
            'type'    => 'update_page',
            'success' => true,
            'page_id' => $page_id,
        );
    }

    /**
     * Batch: Eliminar página
     */
    private function batch_eliminar_pagina( $datos, $op_id ) {
        $page_id = absint( $datos['page_id'] ?? 0 );
        $force   = (bool) ( $datos['force'] ?? false );

        if ( ! $page_id || ! get_post( $page_id ) ) {
            return array(
                'id'      => $op_id,
                'type'    => 'delete_page',
                'success' => false,
                'error'   => __( 'Página no encontrada', FLAVOR_PLATFORM_TEXT_DOMAIN ),
            );
        }

        $eliminado = wp_delete_post( $page_id, $force );

        return array(
            'id'      => $op_id,
            'type'    => 'delete_page',
            'success' => (bool) $eliminado,
            'page_id' => $page_id,
        );
    }

    /**
     * Batch: Agregar elemento a página
     */
    private function batch_agregar_elemento( $datos, $op_id ) {
        $page_id  = absint( $datos['page_id'] ?? 0 );
        $elemento = $datos['element'] ?? array();
        $parent   = sanitize_text_field( $datos['parent_id'] ?? '' );
        $position = absint( $datos['position'] ?? -1 );

        if ( ! $page_id || ! get_post( $page_id ) ) {
            return array(
                'id'      => $op_id,
                'type'    => 'add_element',
                'success' => false,
                'error'   => __( 'Página no encontrada', FLAVOR_PLATFORM_TEXT_DOMAIN ),
            );
        }

        $documento = $this->obtener_documento_batch( $page_id );
        $elementos = isset( $documento['elements'] ) && is_array( $documento['elements'] ) ? $documento['elements'] : array();

        // Generar ID único
        if ( empty( $elemento['id'] ) ) {
            $elemento['id'] = 'el_' . wp_generate_uuid4();
        }

        // Insertar en posición
        if ( $position >= 0 && $position < count( $elementos ) ) {
            array_splice( $elementos, $position, 0, array( $elemento ) );
        } else {
            $elementos[] = $elemento;
        }

        $documento['elements'] = $elementos;
        $this->guardar_documento_batch( $page_id, $documento );

        return array(
            'id'         => $op_id,
            'type'       => 'add_element',
            'success'    => true,
            'element_id' => $elemento['id'],
            'page_id'    => $page_id,
        );
    }

    /**
     * Batch: Actualizar elemento
     */
    private function batch_actualizar_elemento( $datos, $op_id ) {
        $page_id    = absint( $datos['page_id'] ?? 0 );
        $element_id = sanitize_text_field( $datos['element_id'] ?? '' );
        $updates    = $datos['updates'] ?? array();

        if ( ! $page_id || ! $element_id ) {
            return array(
                'id'      => $op_id,
                'type'    => 'update_element',
                'success' => false,
                'error'   => __( 'Se requiere page_id y element_id', FLAVOR_PLATFORM_TEXT_DOMAIN ),
            );
        }

        $documento = $this->obtener_documento_batch( $page_id );
        $elementos = isset( $documento['elements'] ) && is_array( $documento['elements'] ) ? $documento['elements'] : array();

        $encontrado = $this->actualizar_elemento_recursivo( $elementos, $element_id, $updates );

        if ( ! $encontrado ) {
            return array(
                'id'      => $op_id,
                'type'    => 'update_element',
                'success' => false,
                'error'   => __( 'Elemento no encontrado', FLAVOR_PLATFORM_TEXT_DOMAIN ),
            );
        }

        $documento['elements'] = $elementos;
        $this->guardar_documento_batch( $page_id, $documento );

        return array(
            'id'         => $op_id,
            'type'       => 'update_element',
            'success'    => true,
            'element_id' => $element_id,
        );
    }

    /**
     * Actualizar elemento recursivamente
     */
    private function actualizar_elemento_recursivo( &$elementos, $id, $updates ) {
        foreach ( $elementos as &$elemento ) {
            if ( isset( $elemento['id'] ) && $elemento['id'] === $id ) {
                $elemento = array_merge( $elemento, $updates );
                return true;
            }
            if ( isset( $elemento['children'] ) && is_array( $elemento['children'] ) ) {
                if ( $this->actualizar_elemento_recursivo( $elemento['children'], $id, $updates ) ) {
                    return true;
                }
            }
        }
        return false;
    }

    /**
     * Batch: Eliminar elemento
     */
    private function batch_eliminar_elemento( $datos, $op_id ) {
        $page_id    = absint( $datos['page_id'] ?? 0 );
        $element_id = sanitize_text_field( $datos['element_id'] ?? '' );

        if ( ! $page_id || ! $element_id ) {
            return array(
                'id'      => $op_id,
                'type'    => 'delete_element',
                'success' => false,
                'error'   => __( 'Se requiere page_id y element_id', FLAVOR_PLATFORM_TEXT_DOMAIN ),
            );
        }

        $documento = $this->obtener_documento_batch( $page_id );
        $elementos = isset( $documento['elements'] ) && is_array( $documento['elements'] ) ? $documento['elements'] : array();

        $encontrado = $this->eliminar_elemento_recursivo( $elementos, $element_id );

        if ( ! $encontrado ) {
            return array(
                'id'      => $op_id,
                'type'    => 'delete_element',
                'success' => false,
                'error'   => __( 'Elemento no encontrado', FLAVOR_PLATFORM_TEXT_DOMAIN ),
            );
        }

        $documento['elements'] = $elementos;
        $this->guardar_documento_batch( $page_id, $documento );

        return array(
            'id'         => $op_id,
            'type'       => 'delete_element',
            'success'    => true,
            'element_id' => $element_id,
        );
    }

    /**
     * Eliminar elemento recursivamente
     */
    private function eliminar_elemento_recursivo( &$elementos, $id ) {
        foreach ( $elementos as $indice => &$elemento ) {
            if ( isset( $elemento['id'] ) && $elemento['id'] === $id ) {
                array_splice( $elementos, $indice, 1 );
                return true;
            }
            if ( isset( $elemento['children'] ) && is_array( $elemento['children'] ) ) {
                if ( $this->eliminar_elemento_recursivo( $elemento['children'], $id ) ) {
                    return true;
                }
            }
        }
        return false;
    }

    /**
     * Batch: Aplicar estilos
     */
    private function batch_aplicar_estilos( $datos, $op_id ) {
        $page_id    = absint( $datos['page_id'] ?? 0 );
        $element_id = sanitize_text_field( $datos['element_id'] ?? '' );
        $estilos    = $datos['styles'] ?? array();

        if ( ! $page_id || ! $element_id ) {
            return array(
                'id'      => $op_id,
                'type'    => 'apply_styles',
                'success' => false,
                'error'   => __( 'Se requiere page_id y element_id', FLAVOR_PLATFORM_TEXT_DOMAIN ),
            );
        }

        // Actualizar solo los estilos del elemento
        $updates   = array( 'styles' => $estilos );
        $documento = $this->obtener_documento_batch( $page_id );
        $elementos = isset( $documento['elements'] ) && is_array( $documento['elements'] ) ? $documento['elements'] : array();

        $encontrado = $this->actualizar_elemento_recursivo( $elementos, $element_id, $updates );

        if ( ! $encontrado ) {
            return array(
                'id'      => $op_id,
                'type'    => 'apply_styles',
                'success' => false,
                'error'   => __( 'Elemento no encontrado', FLAVOR_PLATFORM_TEXT_DOMAIN ),
            );
        }

        $documento['elements'] = $elementos;
        $this->guardar_documento_batch( $page_id, $documento );

        return array(
            'id'         => $op_id,
            'type'       => 'apply_styles',
            'success'    => true,
            'element_id' => $element_id,
        );
    }

    /**
     * Batch: Publicar página
     */
    private function batch_publicar_pagina( $datos, $op_id ) {
        $page_id = absint( $datos['page_id'] ?? 0 );

        if ( ! $page_id || ! get_post( $page_id ) ) {
            return array(
                'id'      => $op_id,
                'type'    => 'publish_page',
                'success' => false,
                'error'   => __( 'Página no encontrada', FLAVOR_PLATFORM_TEXT_DOMAIN ),
            );
        }

        $resultado = wp_update_post(
            array(
                'ID'          => $page_id,
                'post_status' => 'publish',
            )
        );

        return array(
            'id'      => $op_id,
            'type'    => 'publish_page',
            'success' => ! is_wp_error( $resultado ),
            'page_id' => $page_id,
            'url'     => get_permalink( $page_id ),
        );
    }

    /**
     * Crear múltiples páginas en batch
     *
     * @param WP_REST_Request $request Petición REST.
     * @return WP_REST_Response|WP_Error
     */
    public function crear_paginas_batch( $request ) {
        $paginas = $request->get_param( 'pages' );

        if ( ! is_array( $paginas ) || empty( $paginas ) ) {
            return new WP_Error(
                'paginas_invalidas',
                __( 'Se requiere un array de páginas', FLAVOR_PLATFORM_TEXT_DOMAIN ),
                array( 'status' => 400 )
            );
        }

        $resultados = array();
        $exitos     = 0;
        $errores    = 0;

        foreach ( $paginas as $indice => $pagina ) {
            $resultado = $this->batch_crear_pagina( $pagina, "page_$indice" );
            $resultados[] = $resultado;

            if ( $resultado['success'] ) {
                $exitos++;
            } else {
                $errores++;
            }
        }

        return new WP_REST_Response(
            array(
                'success' => $errores === 0,
                'created' => $exitos,
                'failed'  => $errores,
                'pages'   => $resultados,
            ),
            200
        );
    }

    /**
     * Actualizar múltiples elementos en batch
     *
     * @param WP_REST_Request $request Petición REST.
     * @return WP_REST_Response|WP_Error
     */
    public function actualizar_elementos_batch( $request ) {
        $page_id   = $request->get_param( 'page_id' );
        $elementos = $request->get_param( 'elements' );

        if ( ! $page_id || ! get_post( $page_id ) ) {
            return new WP_Error(
                'pagina_no_encontrada',
                __( 'Página no encontrada', FLAVOR_PLATFORM_TEXT_DOMAIN ),
                array( 'status' => 404 )
            );
        }

        if ( ! is_array( $elementos ) || empty( $elementos ) ) {
            return new WP_Error(
                'elementos_invalidos',
                __( 'Se requiere un array de elementos', FLAVOR_PLATFORM_TEXT_DOMAIN ),
                array( 'status' => 400 )
            );
        }

        $documento        = $this->obtener_documento_batch( $page_id );
        $elementos_actual = isset( $documento['elements'] ) && is_array( $documento['elements'] ) ? $documento['elements'] : array();

        $resultados = array();
        $exitos     = 0;
        $errores    = 0;

        foreach ( $elementos as $elemento ) {
            $element_id = sanitize_text_field( $elemento['id'] ?? '' );
            $updates    = $elemento['updates'] ?? array();

            if ( ! $element_id ) {
                $errores++;
                $resultados[] = array(
                    'element_id' => null,
                    'success'    => false,
                    'error'      => __( 'Se requiere id de elemento', FLAVOR_PLATFORM_TEXT_DOMAIN ),
                );
                continue;
            }

            $encontrado = $this->actualizar_elemento_recursivo( $elementos_actual, $element_id, $updates );

            if ( $encontrado ) {
                $exitos++;
                $resultados[] = array(
                    'element_id' => $element_id,
                    'success'    => true,
                );
            } else {
                $errores++;
                $resultados[] = array(
                    'element_id' => $element_id,
                    'success'    => false,
                    'error'      => __( 'Elemento no encontrado', FLAVOR_PLATFORM_TEXT_DOMAIN ),
                );
            }
        }

        // Guardar cambios en el mismo formato que usa el editor.
        $documento['elements'] = $elementos_actual;
        $this->guardar_documento_batch( $page_id, $documento );

        return new WP_REST_Response(
            array(
                'success'  => $errores === 0,
                'page_id'  => $page_id,
                'updated'  => $exitos,
                'failed'   => $errores,
                'elements' => $resultados,
            ),
            200
        );
    }
}

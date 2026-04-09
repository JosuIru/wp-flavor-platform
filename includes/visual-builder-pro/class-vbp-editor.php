<?php
/**
 * Visual Builder Pro - Editor Principal
 *
 * Controlador principal del editor visual fullscreen tipo Photoshop/Figma.
 * Reemplaza el editor Gutenberg para el CPT flavor_landing.
 *
 * @package Flavor_Chat_IA
 * @subpackage Visual_Builder_Pro
 * @since 2.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Clase principal del Visual Builder Pro
 *
 * @since 2.0.0
 */
class Flavor_VBP_Editor {

    /**
     * Versión del editor
     *
     * @var string
     */
    const VERSION = '2.2.4';

    /**
     * Post types soportados
     *
     * Solo flavor_landing usa el VBP por defecto.
     * Extendido para soportar page y post además de flavor_landing.
     * Las páginas/posts pueden usar VBP o Gutenberg según configuración.
     *
     * @var array
     */
    const POST_TYPES_SOPORTADOS = array( 'flavor_landing', 'page', 'post' );

    /**
     * Meta key para datos del builder
     *
     * @var string
     */
    const META_DATA = '_flavor_vbp_data';

    /**
     * Meta key para configuración
     *
     * @var string
     */
    const META_CONFIG = '_flavor_vbp_config';

    /**
     * Meta key para versión
     *
     * @var string
     */
    const META_VERSION = '_flavor_vbp_version';

    /**
     * Instancia singleton
     *
     * @var Flavor_VBP_Editor|null
     */
    private static $instancia = null;

    /**
     * ID del post actual en edición
     *
     * @var int
     */
    private $post_id_actual = 0;

    /**
     * Indica si estamos en modo editor
     *
     * @var bool
     */
    private $esta_en_editor = false;

    /**
     * Obtiene la instancia singleton
     *
     * @return Flavor_VBP_Editor
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
        $this->inicializar_hooks();
    }

    /**
     * Inicializa los hooks de WordPress
     */
    private function inicializar_hooks() {
        // Deshabilitar Gutenberg para flavor_landing
        add_filter( 'use_block_editor_for_post_type', array( $this, 'deshabilitar_gutenberg' ), 10, 2 );

        // Redirigir a editor fullscreen
        add_action( 'admin_init', array( $this, 'redirigir_a_editor_fullscreen' ) );

        // Registrar página del editor fullscreen
        add_action( 'admin_menu', array( $this, 'registrar_pagina_editor' ) );

        // Cargar assets del editor
        add_action( 'admin_enqueue_scripts', array( $this, 'cargar_assets_editor' ) );

        // Agregar link de edición rápida (posts y páginas)
        add_filter( 'post_row_actions', array( $this, 'agregar_link_edicion_visual' ), 10, 2 );
        add_filter( 'page_row_actions', array( $this, 'agregar_link_edicion_visual' ), 10, 2 );

        // AJAX handlers
        add_action( 'wp_ajax_vbp_guardar_documento', array( $this, 'ajax_guardar_documento' ) );
        add_action( 'wp_ajax_vbp_cargar_documento', array( $this, 'ajax_cargar_documento' ) );
        add_action( 'wp_ajax_vbp_autosave', array( $this, 'ajax_autosave' ) );
        add_action( 'wp_ajax_vbp_render_elemento', array( $this, 'ajax_render_elemento' ) );
        add_action( 'wp_ajax_vbp_publicar_documento', array( $this, 'ajax_publicar_documento' ) );
        add_action( 'wp_ajax_vbp_exportar_template', array( $this, 'ajax_exportar_template' ) );
        add_action( 'wp_ajax_vbp_importar_template', array( $this, 'ajax_importar_template' ) );
        add_action( 'wp_ajax_vbp_obtener_templates', array( $this, 'ajax_obtener_templates' ) );
        add_action( 'wp_ajax_vbp_obtener_bloques', array( $this, 'ajax_obtener_bloques' ) );
    }

    /**
     * Deshabilita el editor Gutenberg para los post types soportados
     *
     * NOTA: Por defecto solo se deshabilita para 'flavor_landing' (CPT propio).
     * Para 'page' y 'post', es opt-in vía filtro o opción.
     *
     * @param bool   $usar_block_editor Si usar el editor de bloques.
     * @param string $tipo_post         El tipo de post.
     * @return bool
     */
    public function deshabilitar_gutenberg( $usar_block_editor, $tipo_post ) {
        // Siempre deshabilitar para flavor_landing (CPT propio del plugin)
        if ( 'flavor_landing' === $tipo_post ) {
            return false;
        }

        // Para page/post, respetar Gutenberg por defecto (opt-in para VBP)
        // Solo deshabilitar si está explícitamente configurado
        $settings = get_option( 'flavor_vbp_settings', array() );
        $reemplazar_gutenberg = isset( $settings['replace_gutenberg'] ) && $settings['replace_gutenberg'];

        /**
         * Filtro para controlar si VBP reemplaza Gutenberg para un post type
         *
         * @param bool   $reemplazar Si reemplazar Gutenberg con VBP.
         * @param string $tipo_post  El tipo de post.
         */
        $reemplazar_gutenberg = apply_filters( 'flavor_vbp_replace_gutenberg', $reemplazar_gutenberg, $tipo_post );

        if ( $reemplazar_gutenberg && in_array( $tipo_post, array( 'page', 'post' ), true ) ) {
            return false;
        }

        return $usar_block_editor;
    }

    /**
     * Redirige al editor fullscreen cuando se edita un flavor_landing
     *
     * NOTA: Por defecto solo redirige para 'flavor_landing'.
     * Para 'page' y 'post', es opt-in vía opción o filtro.
     */
    public function redirigir_a_editor_fullscreen() {
        global $pagenow;

        // Verificar si estamos editando un post
        if ( 'post.php' !== $pagenow && 'post-new.php' !== $pagenow ) {
            return;
        }

        $tipo_post = '';
        $post_id   = 0;

        if ( 'post-new.php' === $pagenow ) {
            $tipo_post = isset( $_GET['post_type'] ) ? sanitize_text_field( wp_unslash( $_GET['post_type'] ) ) : 'post';
        } else {
            $post_id = isset( $_GET['post'] ) ? absint( $_GET['post'] ) : 0;
            if ( $post_id ) {
                $post      = get_post( $post_id );
                $tipo_post = $post ? $post->post_type : '';
            }
        }

        // Determinar si debemos redirigir
        $debe_redirigir = false;

        // Siempre redirigir para flavor_landing (CPT propio)
        if ( 'flavor_landing' === $tipo_post ) {
            $debe_redirigir = true;
        }

        // Para page/post, solo si está configurado (opt-in)
        if ( in_array( $tipo_post, array( 'page', 'post' ), true ) ) {
            $settings = get_option( 'flavor_vbp_settings', array() );
            $debe_redirigir = isset( $settings['replace_gutenberg'] ) && $settings['replace_gutenberg'];

            /** Filtro para controlar redirección */
            $debe_redirigir = apply_filters( 'flavor_vbp_redirect_to_editor', $debe_redirigir, $tipo_post, $post_id );
        }

        // Redirigir si corresponde
        if ( $debe_redirigir ) {
            // Si es post nuevo, primero crear el post
            if ( 'post-new.php' === $pagenow ) {
                $titulo_nuevo = 'page' === $tipo_post
                    ? __( 'Nueva Página', FLAVOR_PLATFORM_TEXT_DOMAIN )
                    : __( 'Nueva Landing Page', FLAVOR_PLATFORM_TEXT_DOMAIN );

                $post_id_nuevo = wp_insert_post(
                    array(
                        'post_type'   => $tipo_post,
                        'post_title'  => $titulo_nuevo,
                        'post_status' => 'draft',
                    )
                );

                if ( $post_id_nuevo && ! is_wp_error( $post_id_nuevo ) ) {
                    $post_id = $post_id_nuevo;
                }
            }

            if ( $post_id ) {
                $url_editor = add_query_arg(
                    array(
                        'page'    => 'vbp-editor',
                        'post_id' => $post_id,
                    ),
                    admin_url( 'admin.php' )
                );
                wp_safe_redirect( $url_editor );
                exit;
            }
        }
    }

    /**
     * Registra la página del editor fullscreen
     */
    public function registrar_pagina_editor() {
        // Página oculta del editor (se accede con post_id)
        add_submenu_page(
            null,
            __( 'Visual Builder Pro', FLAVOR_PLATFORM_TEXT_DOMAIN ),
            __( 'Visual Builder Pro', FLAVOR_PLATFORM_TEXT_DOMAIN ),
            'edit_posts',
            'vbp-editor',
            array( $this, 'renderizar_pagina_editor' )
        );

        // Submenú visible en Flavor Platform
        add_submenu_page(
            'flavor-dashboard',
            __( 'Visual Builder Pro', FLAVOR_PLATFORM_TEXT_DOMAIN ),
            __( '🎨 Visual Builder Pro', FLAVOR_PLATFORM_TEXT_DOMAIN ),
            'edit_posts',
            'vbp-landing-list',
            array( $this, 'renderizar_pagina_listado' )
        );
    }

    /**
     * Renderiza la página de listado de landings
     */
    public function renderizar_pagina_listado() {
        $url_nueva = admin_url( 'post-new.php?post_type=flavor_landing' );
        ?>
        <div class="wrap">
            <h1 class="wp-heading-inline"><?php esc_html_e( 'Visual Builder Pro', FLAVOR_PLATFORM_TEXT_DOMAIN ); ?></h1>
            <a href="<?php echo esc_url( $url_nueva ); ?>" class="page-title-action"><?php esc_html_e( 'Crear Nueva Landing', FLAVOR_PLATFORM_TEXT_DOMAIN ); ?></a>
            <hr class="wp-header-end">

            <div class="vbp-landing-list-wrapper" style="margin-top: 20px;">
                <h2><?php esc_html_e( 'Landing Pages', FLAVOR_PLATFORM_TEXT_DOMAIN ); ?></h2>
                <?php
                $landings = get_posts(
                    array(
                        'post_type'      => 'flavor_landing',
                        'posts_per_page' => 100,
                        'post_status'    => array( 'publish', 'draft', 'pending' ),
                        'orderby'        => 'modified',
                        'order'          => 'DESC',
                    )
                );

                if ( empty( $landings ) ) {
                    echo '<p>' . esc_html__( 'No hay landing pages creadas todavía.', FLAVOR_PLATFORM_TEXT_DOMAIN ) . '</p>';
                } else {
                    echo '<table class="wp-list-table widefat fixed striped">';
                    echo '<thead><tr>';
                    echo '<th>' . esc_html__( 'Título', FLAVOR_PLATFORM_TEXT_DOMAIN ) . '</th>';
                    echo '<th>' . esc_html__( 'Estado', FLAVOR_PLATFORM_TEXT_DOMAIN ) . '</th>';
                    echo '<th>' . esc_html__( 'Fecha', FLAVOR_PLATFORM_TEXT_DOMAIN ) . '</th>';
                    echo '<th>' . esc_html__( 'Acciones', FLAVOR_PLATFORM_TEXT_DOMAIN ) . '</th>';
                    echo '</tr></thead><tbody>';

                    foreach ( $landings as $landing ) {
                        $url_editar = add_query_arg(
                            array(
                                'page'    => 'vbp-editor',
                                'post_id' => $landing->ID,
                            ),
                            admin_url( 'admin.php' )
                        );
                        $url_ver    = get_permalink( $landing->ID );
                        $estado     = get_post_status_object( $landing->post_status );

                        echo '<tr>';
                        echo '<td><strong><a href="' . esc_url( $url_editar ) . '">' . esc_html( $landing->post_title ?: __( '(Sin título)', FLAVOR_PLATFORM_TEXT_DOMAIN ) ) . '</a></strong></td>';
                        echo '<td>' . esc_html( $estado->label ) . '</td>';
                        echo '<td>' . esc_html( get_the_modified_date( '', $landing ) ) . '</td>';
                        echo '<td>';
                        echo '<a href="' . esc_url( $url_editar ) . '" class="button button-primary button-small">' . esc_html__( 'Editar', FLAVOR_PLATFORM_TEXT_DOMAIN ) . '</a> ';
                        if ( 'publish' === $landing->post_status ) {
                            echo '<a href="' . esc_url( $url_ver ) . '" class="button button-small" target="_blank">' . esc_html__( 'Ver', FLAVOR_PLATFORM_TEXT_DOMAIN ) . '</a>';
                        }
                        echo '</td>';
                        echo '</tr>';
                    }

                    echo '</tbody></table>';
                }
                ?>

                <h2 style="margin-top: 30px;"><?php esc_html_e( 'Páginas con Visual Builder', FLAVOR_PLATFORM_TEXT_DOMAIN ); ?></h2>
                <p class="description"><?php esc_html_e( 'También puedes editar páginas normales con el Visual Builder. Usa el link "Editor Visual" en la lista de páginas.', FLAVOR_PLATFORM_TEXT_DOMAIN ); ?></p>
                <p><a href="<?php echo esc_url( admin_url( 'edit.php?post_type=page' ) ); ?>" class="button"><?php esc_html_e( 'Ver Páginas', FLAVOR_PLATFORM_TEXT_DOMAIN ); ?></a></p>
            </div>
        </div>
        <?php
    }

    /**
     * Verifica si estamos en la página del editor
     *
     * @return bool
     */
    public function esta_en_pagina_editor() {
        $pantalla_actual = get_current_screen();
        return $pantalla_actual && 'admin_page_vbp-editor' === $pantalla_actual->id;
    }

    /**
     * Carga los assets del editor
     *
     * @param string $hook_suffix El sufijo del hook actual.
     */
    public function cargar_assets_editor( $hook_suffix ) {
        // Solo cargar en la página del editor
        if ( 'admin_page_vbp-editor' !== $hook_suffix ) {
            return;
        }

        $this->esta_en_editor = true;
        $post_id              = isset( $_GET['post_id'] ) ? absint( $_GET['post_id'] ) : 0;
        $this->post_id_actual = $post_id;
        $editor_features      = $this->obtener_feature_flags_editor( $post_id );

        // Obtener URLs base
        $url_css    = FLAVOR_CHAT_IA_URL . 'assets/vbp/css/';
        $url_js     = FLAVOR_CHAT_IA_URL . 'assets/vbp/js/';
        $url_vendor = FLAVOR_CHAT_IA_URL . 'assets/vbp/vendor/';

        // SortableJS local (sin CDN para mejor rendimiento y privacidad)
        wp_enqueue_script(
            'sortablejs',
            $url_vendor . 'sortable.min.js',
            array(),
            '1.15.2',
            true
        );

        // Alpine.js Collapse plugin (debe cargarse antes de Alpine.js)
        // Incluir solo handles siempre registrados o añadidos explícitamente por feature.
        $alpine_component_dependencies = array(
            'vbp-store',
            'vbp-app',
            'vbp-app-commands',
            'vbp-layers',
            'vbp-inspector',
            'vbp-text-editor',
        );

        if ( ! empty( $editor_features['collaboration'] ) ) {
            $alpine_component_dependencies[] = 'vbp-app-collaboration';
        }

        if ( ! empty( $editor_features['audit_log'] ) ) {
            $alpine_component_dependencies[] = 'vbp-app-audit-log';
        }

        if ( ! empty( $editor_features['workflows'] ) ) {
            $alpine_component_dependencies[] = 'vbp-app-workflows';
        }

        if ( ! empty( $editor_features['multisite'] ) ) {
            $alpine_component_dependencies[] = 'vbp-app-multisite';
        }

        // Incluir módulos de app como dependencias para que estén disponibles cuando Alpine inicialice
        wp_enqueue_script(
            'alpinejs-collapse',
            $url_vendor . 'alpine-collapse.min.js',
            $alpine_component_dependencies,
            '3.14.3',
            true
        );

        // Alpine.js local
        // Se carga DESPUÉS de todos los scripts VBP que definen componentes
        // WordPress lo resolverá automáticamente por las dependencias
        wp_enqueue_script(
            'alpinejs',
            $url_vendor . 'alpine.min.js',
            array( 'alpinejs-collapse' ),
            '3.14.3',
            true
        );

        // Agregar defer a Alpine para asegurar el orden correcto
        add_filter( 'script_loader_tag', array( $this, 'agregar_defer_alpine' ), 10, 2 );

        // CSS de Design Tokens (sistema unificado de diseño)
        $url_css_base = FLAVOR_CHAT_IA_URL . 'assets/css/';
        $design_tokens_css = array(
            'fl-design-tokens'        => 'design-tokens.css',
            'fl-design-tokens-compat' => 'design-tokens-compat.css',
        );

        foreach ( $design_tokens_css as $handle => $archivo ) {
            $ruta_archivo = FLAVOR_CHAT_IA_PATH . 'assets/css/' . $archivo;
            if ( file_exists( $ruta_archivo ) ) {
                wp_enqueue_style(
                    $handle,
                    $url_css_base . $archivo,
                    array(),
                    self::VERSION
                );
            }
        }

        // CSS del editor VBP (depende de design tokens)
        $archivos_css = array(
            'editor-core'            => 'editor-core.css',
            'editor-canvas'          => 'editor-canvas.css',
            'editor-panels'          => 'editor-panels.css',
            'editor-rulers'          => 'editor-rulers.css',
            'editor-toolbar'         => 'editor-toolbar.css',
            'editor-responsive'      => 'editor-responsive.css',
            'editor-selectors'       => 'editor-selectors.css',
            'editor-richtext'        => 'editor-richtext.css',
            'editor-command-palette' => 'editor-command-palette.css',
            'editor-statusbar'       => 'editor-statusbar.css',
            'editor-tooltips'        => 'editor-tooltips.css',
            'editor-toast'           => 'editor-toast.css',
            'vbp-design-tokens'      => 'vbp-design-tokens.css',
            'vbp-mobile'             => 'vbp-mobile.css',
            'vbp-blocks-enhanced'    => 'vbp-blocks-enhanced.css',
            'editor-preview-sections' => 'editor-preview-sections.css',
            'editor-ux-improvements' => 'editor-ux-improvements.css',
            'editor-help-system'     => 'editor-help-system.css',
        );

        if ( ! empty( $editor_features['minimap'] ) ) {
            $archivos_css['editor-minimap'] = 'editor-minimap.css';
        }

        if ( ! empty( $editor_features['ai'] ) ) {
            $archivos_css['editor-ai-assistant'] = 'editor-ai-assistant.css';
        }

        if ( ! empty( $editor_features['collaboration'] ) ) {
            $archivos_css['vbp-collaboration'] = 'vbp-collaboration.css';
        }

        if ( ! empty( $editor_features['audit_log'] ) ) {
            $archivos_css['vbp-audit-log'] = 'vbp-audit-log.css';
        }

        if ( ! empty( $editor_features['workflows'] ) ) {
            $archivos_css['vbp-workflows'] = 'vbp-workflows.css';
        }

        if ( ! empty( $editor_features['multisite'] ) ) {
            $archivos_css['vbp-multisite'] = 'vbp-multisite.css';
        }

        // Cargar Material Icons font (local para mejor rendimiento y privacidad)
        wp_enqueue_style(
            'material-icons',
            $url_vendor . 'material-icons.css',
            array(),
            '142'
        );

        // Cargar Font Awesome 6 Free (local para mejor rendimiento y privacidad)
        wp_enqueue_style(
            'fontawesome',
            $url_vendor . 'fontawesome.min.css',
            array(),
            '6.5.1'
        );

        // Cargar emoji-picker-element desde CDN
        // NOTA: Se mantiene CDN porque es un ES6 module con múltiples dependencias internas.
        // No es crítico para seguridad, solo funcionalidad de emojis en comentarios.
        // TODO: Evaluar alternativa local o lazy-load bajo demanda.
        wp_enqueue_script(
            'emoji-picker-element',
            'https://cdn.jsdelivr.net/npm/emoji-picker-element@1/index.js',
            array(),
            '1.21.3',
            true
        );
        // Agregar type="module" al script de emoji-picker
        add_filter( 'script_loader_tag', array( $this, 'agregar_module_emoji_picker' ), 10, 2 );

        // Usar versiones minificadas de CSS en producción
        $usar_css_minificado = ! defined( 'SCRIPT_DEBUG' ) || ! SCRIPT_DEBUG;

        foreach ( $archivos_css as $handle => $archivo ) {
            // En producción, intentar cargar la versión minificada
            if ( $usar_css_minificado ) {
                $archivo_min = str_replace( '.css', '.min.css', $archivo );
                $ruta_min    = FLAVOR_CHAT_IA_PATH . 'assets/vbp/css/' . $archivo_min;
                if ( file_exists( $ruta_min ) ) {
                    $archivo = $archivo_min;
                }
            }

            $ruta_archivo = FLAVOR_CHAT_IA_PATH . 'assets/vbp/css/' . $archivo;
            if ( file_exists( $ruta_archivo ) ) {
                wp_enqueue_style(
                    'vbp-' . $handle,
                    $url_css . $archivo,
                    array( 'fl-design-tokens-compat' ),
                    self::VERSION
                );
            }
        }

        // Inyectar CSS de Design Settings personalizados
        $this->inyectar_design_settings_css();

        // JavaScript del editor
        // ORDEN DE CARGA:
        // 1. Scripts que definen funciones globales (sin dependencia de Alpine)
        // 2. Alpine.js (depende de los anteriores para encontrar los componentes)
        // 3. Scripts que usan Alpine.store() se registran en el evento 'alpine:init'
        $archivos_js = array(
            'theme'        => array( 'vbp-theme.js', array() ), // Gestión de tema claro/oscuro (primero para evitar flash)
            'performance'  => array( 'vbp-performance.js', array() ), // Utilidades de performance primero
            'store-catalog'=> array( 'vbp-store-catalog.js', array() ),
            'store-style-helpers' => array( 'vbp-store-style-helpers.js', array() ),
            'store-tree-helpers' => array( 'vbp-store-tree-helpers.js', array() ),
            'store-mutation-helpers' => array( 'vbp-store-mutation-helpers.js', array() ),
            'store-history-helpers' => array( 'vbp-store-history-helpers.js', array() ),
            'store'        => array( 'vbp-store.js', array( 'vbp-performance', 'vbp-store-catalog', 'vbp-store-style-helpers', 'vbp-store-tree-helpers', 'vbp-store-mutation-helpers', 'vbp-store-history-helpers' ) ),
            'store-modals' => array( 'vbp-store-modals.js', array( 'vbp-store' ) ),
            'app'          => array( 'vbp-app.js', array( 'vbp-performance' ) ),
            'app-modular'  => array( 'vbp-app-modular.js', array( 'vbp-app' ) ), // Cargador de módulos de app
            // Módulos de app (deben cargarse síncronamente antes de Alpine)
            'app-split-screen'   => array( 'modules/vbp-app-split-screen.js', array( 'vbp-app-modular' ) ),
            'app-page-settings'  => array( 'modules/vbp-app-page-settings.js', array( 'vbp-app-modular' ) ),
            'app-templates'      => array( 'modules/vbp-app-templates.js', array( 'vbp-app-modular' ) ),
            'app-version-history'=> array( 'modules/vbp-app-version-history.js', array( 'vbp-app-modular' ) ),
            'app-unsplash'       => array( 'modules/vbp-app-unsplash.js', array( 'vbp-app-modular' ) ),
            'app-revisions'      => array( 'modules/vbp-app-revisions.js', array( 'vbp-app-modular' ) ),
            'app-import-export'  => array( 'modules/vbp-app-import-export.js', array( 'vbp-app-modular' ) ),
            'app-commands'       => array( 'modules/vbp-app-commands.js', array( 'vbp-app-modular' ) ),
            'app-mobile'         => array( 'modules/vbp-app-mobile.js', array( 'vbp-app-modular' ) ),
            'layers'       => array( 'vbp-layers.js', array() ),
            'inspector'    => array( 'vbp-inspector.js', array() ),
            'inspector-media' => array( 'vbp-inspector-media.js', array( 'vbp-inspector' ) ),
            'inspector-utils' => array( 'vbp-inspector-utils.js', array() ), // Utilidades: copiar/pegar estilos
            'inspector-modals' => array( 'vbp-inspector-modals.js', array( 'vbp-inspector-utils' ) ),
            'link-search'  => array( 'vbp-link-search.js', array() ), // Autocompletado de enlaces
            'richtext'     => array( 'vbp-richtext.js', array() ), // Editor de texto enriquecido
            'command-palette' => array( 'vbp-command-palette.js', array() ), // Paleta de comandos Ctrl+/
            'canvas-utils' => array( 'vbp-canvas-utils.js', array( 'vbp-performance' ) ),
            'canvas'       => array( 'vbp-canvas.js', array( 'sortablejs', 'vbp-performance', 'vbp-canvas-utils' ) ),
            'canvas-resize'=> array( 'vbp-canvas-resize.js', array( 'vbp-canvas' ) ),
            'rulers'       => array( 'vbp-rulers.js', array() ),
            'text-editor'  => array( 'vbp-text-editor.js', array() ),
            'keyboard'     => array( 'vbp-keyboard-modular.js', array() ), // Versión modularizada para carga optimizada
            'history'      => array( 'vbp-history.js', array() ),
            'api'          => array( 'vbp-api.js', array() ),
            'breadcrumbs'  => array( 'vbp-breadcrumbs.js', array() ), // Breadcrumbs y zoom
            'toast'        => array( 'vbp-toast.js', array() ), // Sistema de notificaciones
            'module-preview' => array( 'vbp-module-preview.js', array() ), // Sistema de preview de módulos en canvas
            'inline-editor' => array( 'vbp-inline-editor.js', array() ), // WYSIWYG inline editing en canvas
            'accessibility' => array( 'vbp-accessibility.js', array() ), // Mejoras UX: teclado, ARIA, confirm dialog, indicadores
        );

        if ( ! empty( $editor_features['collaboration'] ) ) {
            $archivos_js['app-collaboration'] = array( 'modules/vbp-app-collaboration.js', array( 'vbp-app-modular' ) );
            $archivos_js['comments']          = array( 'vbp-comments.js', array() );
        }

        if ( ! empty( $editor_features['audit_log'] ) ) {
            $archivos_js['app-audit-log'] = array( 'modules/vbp-app-audit-log.js', array( 'vbp-app-modular' ) );
        }

        if ( ! empty( $editor_features['workflows'] ) ) {
            $archivos_js['app-workflows'] = array( 'modules/vbp-app-workflows.js', array( 'vbp-app-modular' ) );
        }

        if ( ! empty( $editor_features['multisite'] ) ) {
            $archivos_js['app-multisite'] = array( 'modules/vbp-app-multisite.js', array( 'vbp-app-modular' ) );
        }

        if ( ! empty( $editor_features['minimap'] ) ) {
            $archivos_js['minimap'] = array( 'vbp-minimap.js', array() );
        }

        // Usar versiones minificadas en producción
        $usar_minificado = ! defined( 'SCRIPT_DEBUG' ) || ! SCRIPT_DEBUG;

        foreach ( $archivos_js as $handle => $config ) {
            $archivo      = $config[0];
            $dependencias = $config[1];

            // En producción, intentar cargar la versión minificada
            if ( $usar_minificado ) {
                $archivo_min = str_replace( '.js', '.min.js', $archivo );
                $ruta_min    = FLAVOR_CHAT_IA_PATH . 'assets/vbp/js/' . $archivo_min;
                if ( file_exists( $ruta_min ) ) {
                    $archivo = $archivo_min;
                }
            }

            $ruta_archivo = FLAVOR_CHAT_IA_PATH . 'assets/vbp/js/' . $archivo;

            if ( file_exists( $ruta_archivo ) ) {
                wp_enqueue_script(
                    'vbp-' . $handle,
                    $url_js . $archivo,
                    $dependencias,
                    self::VERSION,
                    true
                );
            }
        }

        // Obtener Design Settings
        $design_settings = $this->obtener_design_settings();

        // Obtener bloques dinámicos desde la librería
        $bloques_categorias = array();
        if ( class_exists( 'Flavor_VBP_Block_Library' ) ) {
            $libreria_bloques   = Flavor_VBP_Block_Library::get_instance();
            $bloques_categorias = $libreria_bloques->get_categorias_con_bloques();
        }

        // Obtener templates preconfigurados
        $templates_libreria = array(
            'library' => array(),
            'user'    => array(),
        );
        if ( class_exists( 'Flavor_VBP_REST_API' ) ) {
            $rest_api                     = Flavor_VBP_REST_API::get_instance();
            $templates_libreria['library'] = $rest_api->get_library_templates();
        }

        // Localizar datos para JavaScript
        $datos_localizados = array(
            'ajaxUrl'        => admin_url( 'admin-ajax.php' ),
            'restUrl'        => rest_url( 'flavor-vbp/v1/' ),
            'assetsUrl'      => FLAVOR_CHAT_IA_URL . 'assets/vbp/',
            'settingsUrl'    => admin_url( 'admin.php?page=flavor-platform-settings' ),
            'siteUrl'        => home_url(),
            'nonce'          => wp_create_nonce( 'vbp_editor_nonce' ),
            'restNonce'      => wp_create_nonce( 'wp_rest' ),
            'postId'         => $post_id,
            'postType'       => $post_id ? get_post_type( $post_id ) : 'flavor_landing',
            'returnUrl'      => admin_url( 'edit.php?post_type=flavor_landing' ),
            'previewUrl'     => $post_id ? get_preview_post_link( $post_id ) : '',
            'viewUrl'        => $post_id ? get_permalink( $post_id ) : '',
            'userId'         => get_current_user_id(),
            'isAdmin'        => current_user_can( 'manage_options' ),
            'userCan'        => array(
                'edit_others_posts' => current_user_can( 'edit_others_posts' ),
                'manage_options'    => current_user_can( 'manage_options' ),
            ),
            'features'       => $editor_features,
            'optionalScripts' => array(
                'componentLibrary' => FLAVOR_CHAT_IA_URL . 'assets/vbp/js/' . ( $usar_minificado && file_exists( FLAVOR_CHAT_IA_PATH . 'assets/vbp/js/vbp-component-library.min.js' ) ? 'vbp-component-library.min.js' : 'vbp-component-library.js' ),
                'helpSystem'       => FLAVOR_CHAT_IA_URL . 'assets/vbp/js/' . ( $usar_minificado && file_exists( FLAVOR_CHAT_IA_PATH . 'assets/vbp/js/vbp-help-system.min.js' ) ? 'vbp-help-system.min.js' : 'vbp-help-system.js' ),
                'designTokens'     => FLAVOR_CHAT_IA_URL . 'assets/vbp/js/modules/' . ( $usar_minificado && file_exists( FLAVOR_CHAT_IA_PATH . 'assets/vbp/js/modules/vbp-app-design-tokens.min.js' ) ? 'vbp-app-design-tokens.min.js' : 'vbp-app-design-tokens.js' ),
                'aiAssistant'      => FLAVOR_CHAT_IA_URL . 'assets/vbp/js/' . ( $usar_minificado && file_exists( FLAVOR_CHAT_IA_PATH . 'assets/vbp/js/vbp-ai-assistant.min.js' ) ? 'vbp-ai-assistant.min.js' : 'vbp-ai-assistant.js' ),
            ),
            'designSettings' => $design_settings,
            'blocks'         => $bloques_categorias,
            'templates'      => $templates_libreria,
            'strings'        => array(
                'saved'                  => __( 'Guardado', FLAVOR_PLATFORM_TEXT_DOMAIN ),
                'saving'                 => __( 'Guardando...', FLAVOR_PLATFORM_TEXT_DOMAIN ),
                'error'                  => __( 'Error al guardar', FLAVOR_PLATFORM_TEXT_DOMAIN ),
                'unsavedChanges'         => __( 'Tienes cambios sin guardar. ¿Seguro que quieres salir?', FLAVOR_PLATFORM_TEXT_DOMAIN ),
                'deleteConfirm'          => __( '¿Eliminar este elemento?', FLAVOR_PLATFORM_TEXT_DOMAIN ),
                'duplicated'             => __( 'Elemento duplicado', FLAVOR_PLATFORM_TEXT_DOMAIN ),
                'noSelection'            => __( 'Nada seleccionado', FLAVOR_PLATFORM_TEXT_DOMAIN ),
                'confirmApplyTemplate'   => __( '¿Aplicar este template? Se reemplazará el contenido actual.', FLAVOR_PLATFORM_TEXT_DOMAIN ),
                'confirmDeleteTemplate'  => __( '¿Eliminar este template? Esta acción no se puede deshacer.', FLAVOR_PLATFORM_TEXT_DOMAIN ),
                'confirmImport'          => __( '¿Importar este diseño? Se reemplazará el contenido actual.', FLAVOR_PLATFORM_TEXT_DOMAIN ),
                'templateSaved'          => __( 'Template guardado correctamente', FLAVOR_PLATFORM_TEXT_DOMAIN ),
                'templateApplied'        => __( 'Template aplicado correctamente', FLAVOR_PLATFORM_TEXT_DOMAIN ),
                'exportSuccess'          => __( 'Exportación completada', FLAVOR_PLATFORM_TEXT_DOMAIN ),
                'importSuccess'          => __( 'Importación completada', FLAVOR_PLATFORM_TEXT_DOMAIN ),
            ),
            'breakpoints'    => array(
                'mobile'  => 480,
                'tablet'  => 768,
                'desktop' => 1024,
            ),
            // Configuración de IA para generación de contenido
            'ai'             => array(
                'enabled'    => $this->ai_esta_habilitado(),
                'endpoints'  => array(
                    'generate'    => rest_url( 'flavor-vbp/v1/ai/generate' ),
                    'improve'     => rest_url( 'flavor-vbp/v1/ai/improve' ),
                    'suggestions' => rest_url( 'flavor-vbp/v1/ai/suggestions' ),
                    'translate'   => rest_url( 'flavor-vbp/v1/ai/translate' ),
                    'options'     => rest_url( 'flavor-vbp/v1/ai/options' ),
                ),
                'strings'    => array(
                    'generating'   => __( 'Generando...', FLAVOR_PLATFORM_TEXT_DOMAIN ),
                    'improving'    => __( 'Mejorando...', FLAVOR_PLATFORM_TEXT_DOMAIN ),
                    'generated'    => __( 'Contenido generado', FLAVOR_PLATFORM_TEXT_DOMAIN ),
                    'error'        => __( 'Error al generar', FLAVOR_PLATFORM_TEXT_DOMAIN ),
                    'apply'        => __( 'Aplicar', FLAVOR_PLATFORM_TEXT_DOMAIN ),
                    'regenerate'   => __( 'Regenerar', FLAVOR_PLATFORM_TEXT_DOMAIN ),
                    'generateNew'  => __( 'Generar nuevo', FLAVOR_PLATFORM_TEXT_DOMAIN ),
                    'improveText'  => __( 'Mejorar', FLAVOR_PLATFORM_TEXT_DOMAIN ),
                ),
            ),
            // Configuración de exportación de código (consolidado v3.4.0)
            'codeExport'     => array(
                'enabled'   => class_exists( 'Flavor_VBP_Code_Exporter' ),
                'endpoints' => array(
                    'export'  => rest_url( 'flavor-vbp/v1/export-code' ),
                    'preview' => rest_url( 'flavor-vbp/v1/preview-code' ),
                    'formats' => rest_url( 'flavor-vbp/v1/export-formats' ),
                ),
                'frameworks' => array(
                    'react' => array(
                        'id'    => 'react',
                        'name'  => 'React',
                        'ext'   => 'jsx',
                        'styles' => array( 'css', 'tailwind', 'styled-components' ),
                    ),
                    'vue'   => array(
                        'id'    => 'vue',
                        'name'  => 'Vue 3',
                        'ext'   => 'vue',
                        'styles' => array( 'css', 'tailwind' ),
                    ),
                ),
                'strings'   => array(
                    'exporting'       => __( 'Exportando código...', FLAVOR_PLATFORM_TEXT_DOMAIN ),
                    'exportSuccess'   => __( 'Código exportado', FLAVOR_PLATFORM_TEXT_DOMAIN ),
                    'downloadReady'   => __( 'Descarga lista', FLAVOR_PLATFORM_TEXT_DOMAIN ),
                    'selectFramework' => __( 'Selecciona framework', FLAVOR_PLATFORM_TEXT_DOMAIN ),
                    'selectStyle'     => __( 'Estilo CSS', FLAVOR_PLATFORM_TEXT_DOMAIN ),
                ),
            ),
            // Configuración de importación Figma (consolidado v3.4.0)
            'figmaImport'    => array(
                'enabled'   => $this->figma_esta_configurado(),
                'available' => class_exists( 'Flavor_VBP_Figma_Importer' ),
                'endpoints' => array(
                    'import'  => rest_url( 'flavor-vbp/v1/import-figma' ),
                    'preview' => rest_url( 'flavor-vbp/v1/preview-figma' ),
                    'status'  => rest_url( 'flavor-vbp/v1/figma-status' ),
                ),
                'strings'   => array(
                    'importing'     => __( 'Importando desde Figma...', FLAVOR_PLATFORM_TEXT_DOMAIN ),
                    'importSuccess' => __( 'Diseño importado', FLAVOR_PLATFORM_TEXT_DOMAIN ),
                    'pasteUrl'      => __( 'Pega URL de Figma', FLAVOR_PLATFORM_TEXT_DOMAIN ),
                    'notConfigured' => __( 'Configura tu token de Figma en Ajustes > Chat IA', FLAVOR_PLATFORM_TEXT_DOMAIN ),
                    'configure'     => __( 'Configurar Figma', FLAVOR_PLATFORM_TEXT_DOMAIN ),
                ),
            ),
            // Configuración de historial de versiones (consolidado)
            'versionHistory' => array(
                'enabled'   => true,
                'maxVersions' => 20,
                'endpoints' => array(
                    'list'    => rest_url( 'flavor-vbp/v1/versions/{post_id}' ),
                    'get'     => rest_url( 'flavor-vbp/v1/versions/{post_id}/{version_id}' ),
                    'create'  => rest_url( 'flavor-vbp/v1/versions/{post_id}' ),
                    'restore' => rest_url( 'flavor-vbp/v1/versions/{post_id}/{version_id}/restore' ),
                    'compare' => rest_url( 'flavor-vbp/v1/versions/{post_id}/compare' ),
                    'delete'  => rest_url( 'flavor-vbp/v1/versions/{post_id}/{version_id}' ),
                    'label'   => rest_url( 'flavor-vbp/v1/versions/{post_id}/{version_id}/label' ),
                ),
                'strings'   => array(
                    'loading'      => __( 'Cargando versiones...', FLAVOR_PLATFORM_TEXT_DOMAIN ),
                    'noVersions'   => __( 'Sin versiones guardadas', FLAVOR_PLATFORM_TEXT_DOMAIN ),
                    'restoring'    => __( 'Restaurando...', FLAVOR_PLATFORM_TEXT_DOMAIN ),
                    'restored'     => __( 'Versión restaurada', FLAVOR_PLATFORM_TEXT_DOMAIN ),
                    'comparing'    => __( 'Comparando versiones...', FLAVOR_PLATFORM_TEXT_DOMAIN ),
                    'confirmRestore' => __( '¿Restaurar esta versión? Se guardará la versión actual antes.', FLAVOR_PLATFORM_TEXT_DOMAIN ),
                    'labelUpdated' => __( 'Etiqueta actualizada', FLAVOR_PLATFORM_TEXT_DOMAIN ),
                    'versionSaved' => __( 'Versión guardada', FLAVOR_PLATFORM_TEXT_DOMAIN ),
                    'saveVersion'  => __( 'Guardar versión', FLAVOR_PLATFORM_TEXT_DOMAIN ),
                    'viewHistory'  => __( 'Ver historial', FLAVOR_PLATFORM_TEXT_DOMAIN ),
                ),
            ),
            'collaboration'  => array(
                'enabled'  => ! empty( $editor_features['collaboration'] ),
                'userRole' => current_user_can( 'edit_post', $post_id ) ? 'editor' : ( current_user_can( 'read_post', $post_id ) ? 'viewer' : 'viewer' ),
            ),
        );

        wp_localize_script( 'vbp-store', 'VBP_Config', $datos_localizados );
    }

    /**
     * Obtiene las feature flags activas para el editor actual.
     *
     * @param int $post_id ID del post actual.
     * @return array
     */
    private function obtener_feature_flags_editor( $post_id ) {
        return array(
            'ai'                => $this->ai_esta_habilitado(),
            'minimap'           => true,
            'help_system'       => true,
            'component_library' => class_exists( 'Flavor_VBP_Component_Library' ),
            'collaboration'     => class_exists( 'Flavor_VBP_Comments' ) && class_exists( 'Flavor_VBP_Collaboration_API' ) && current_user_can( 'edit_post', $post_id ),
            'audit_log'         => class_exists( 'Flavor_VBP_Audit_Log' ) && current_user_can( 'manage_options' ),
            'workflows'         => class_exists( 'Flavor_VBP_Workflows' ) && current_user_can( 'edit_others_posts' ),
            'multisite'         => is_multisite() && class_exists( 'Flavor_VBP_Multisite' ),
        );
    }

    /**
     * Obtiene los Design Settings configurados
     *
     * @return array
     */
    private function obtener_design_settings() {
        // Valores por defecto
        $defaults = array(
            'primary_color'        => '#3b82f6',
            'secondary_color'      => '#8b5cf6',
            'accent_color'         => '#f59e0b',
            'success_color'        => '#10b981',
            'warning_color'        => '#f59e0b',
            'error_color'          => '#ef4444',
            'background_color'     => '#ffffff',
            'text_color'           => '#1f2937',
            'text_muted_color'     => '#6b7280',
            'font_family_headings' => 'Inter',
            'font_family_body'     => 'Inter',
            'font_size_base'       => 16,
            'font_size_h1'         => 48,
            'font_size_h2'         => 36,
            'font_size_h3'         => 28,
            'line_height_base'     => 1.5,
            'line_height_headings' => 1.2,
            'container_max_width'  => 1280,
            'section_padding_y'    => 80,
            'section_padding_x'    => 20,
            'grid_gap'             => 24,
            'card_padding'         => 24,
            'button_border_radius' => 8,
            'button_padding_y'     => 12,
            'button_padding_x'     => 24,
            'button_font_size'     => 16,
            'button_font_weight'   => 600,
            'card_border_radius'   => 12,
            'card_shadow'          => 'medium',
            'hero_overlay_opacity' => 0.6,
            'image_border_radius'  => 8,
        );

        // Intentar obtener de Flavor_Design_Settings si está disponible
        if ( class_exists( 'Flavor_Design_Settings' ) && method_exists( 'Flavor_Design_Settings', 'get_instance' ) ) {
            $design_settings_instance = Flavor_Design_Settings::get_instance();
            if ( method_exists( $design_settings_instance, 'get_settings' ) ) {
                $saved_settings = $design_settings_instance->get_settings();
                if ( ! empty( $saved_settings ) ) {
                    return wp_parse_args( $saved_settings, $defaults );
                }
            }
        }

        // Fallback a opción directa
        $saved = get_option( 'flavor_design_settings', array() );
        if ( ! empty( $saved ) ) {
            return wp_parse_args( $saved, $defaults );
        }

        return $defaults;
    }

    /**
     * Verifica si la generación de contenido con IA está disponible
     *
     * @return bool
     */
    private function ai_esta_habilitado() {
        // Verificar si hay un proveedor de IA configurado
        if ( class_exists( 'Flavor_Engine_Manager' ) ) {
            $engine_manager = Flavor_Engine_Manager::get_instance();
            $engine = $engine_manager->get_backend_engine();
            if ( $engine && method_exists( $engine, 'is_configured' ) ) {
                return $engine->is_configured();
            }
        }

        // Fallback: verificar si hay API key configurada
        $settings = get_option( 'flavor_chat_ia_settings', array() );
        $providers = array( 'claude_api_key', 'openai_api_key', 'deepseek_api_key', 'mistral_api_key' );

        foreach ( $providers as $provider_key ) {
            if ( ! empty( $settings[ $provider_key ] ) ) {
                return true;
            }
        }

        return false;
    }

    /**
     * Verifica si Figma está configurado
     *
     * @return bool
     */
    private function figma_esta_configurado() {
        $settings = get_option( 'flavor_chat_ia_settings', array() );
        return ! empty( $settings['figma_personal_token'] );
    }

    /**
     * Inyecta el CSS de Design Settings en el editor
     */
    private function inyectar_design_settings_css() {
        $settings = $this->obtener_design_settings();

        // Mapeo de sombras
        $sombras = array(
            'none'   => 'none',
            'small'  => '0 1px 2px 0 rgba(0, 0, 0, 0.05)',
            'medium' => '0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06)',
            'large'  => '0 10px 15px -3px rgba(0, 0, 0, 0.1), 0 4px 6px -2px rgba(0, 0, 0, 0.05)',
            'xl'     => '0 20px 25px -5px rgba(0, 0, 0, 0.1), 0 10px 10px -5px rgba(0, 0, 0, 0.04)',
        );

        $card_shadow = isset( $sombras[ $settings['card_shadow'] ] ) ? $sombras[ $settings['card_shadow'] ] : $sombras['medium'];

        $css = sprintf(
            '
            :root {
                /* Colores de Design Settings */
                --flavor-primary: %1$s;
                --flavor-secondary: %2$s;
                --flavor-accent: %3$s;
                --flavor-success: %4$s;
                --flavor-warning: %5$s;
                --flavor-error: %6$s;
                --flavor-bg: %7$s;
                --flavor-text: %8$s;
                --flavor-text-muted: %9$s;

                /* Tipografía */
                --flavor-font-headings: "%10$s", -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
                --flavor-font-body: "%11$s", -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
                --flavor-font-size-base: %12$spx;
                --flavor-font-size-h1: %13$spx;
                --flavor-font-size-h2: %14$spx;
                --flavor-font-size-h3: %15$spx;
                --flavor-line-height-base: %16$s;
                --flavor-line-height-headings: %17$s;

                /* Espaciados */
                --flavor-container-max: %18$spx;
                --flavor-section-py: %19$spx;
                --flavor-section-px: %20$spx;
                --flavor-grid-gap: %21$spx;
                --flavor-card-padding: %22$spx;

                /* Botones */
                --flavor-button-radius: %23$spx;
                --flavor-button-py: %24$spx;
                --flavor-button-px: %25$spx;
                --flavor-button-font-size: %26$spx;
                --flavor-button-weight: %27$s;

                /* Componentes */
                --flavor-card-radius: %28$spx;
                --flavor-card-shadow: %29$s;
                --flavor-hero-overlay: %30$s;
                --flavor-image-radius: %31$spx;
            }
            ',
            esc_attr( $settings['primary_color'] ),
            esc_attr( $settings['secondary_color'] ),
            esc_attr( $settings['accent_color'] ),
            esc_attr( $settings['success_color'] ),
            esc_attr( $settings['warning_color'] ),
            esc_attr( $settings['error_color'] ),
            esc_attr( $settings['background_color'] ),
            esc_attr( $settings['text_color'] ),
            esc_attr( $settings['text_muted_color'] ),
            esc_attr( $settings['font_family_headings'] ),
            esc_attr( $settings['font_family_body'] ),
            esc_attr( $settings['font_size_base'] ),
            esc_attr( $settings['font_size_h1'] ),
            esc_attr( $settings['font_size_h2'] ),
            esc_attr( $settings['font_size_h3'] ),
            esc_attr( $settings['line_height_base'] ),
            esc_attr( $settings['line_height_headings'] ),
            esc_attr( $settings['container_max_width'] ),
            esc_attr( $settings['section_padding_y'] ),
            esc_attr( $settings['section_padding_x'] ),
            esc_attr( $settings['grid_gap'] ),
            esc_attr( $settings['card_padding'] ),
            esc_attr( $settings['button_border_radius'] ),
            esc_attr( $settings['button_padding_y'] ),
            esc_attr( $settings['button_padding_x'] ),
            esc_attr( $settings['button_font_size'] ),
            esc_attr( $settings['button_font_weight'] ),
            esc_attr( $settings['card_border_radius'] ),
            $card_shadow,
            esc_attr( $settings['hero_overlay_opacity'] ),
            esc_attr( $settings['image_border_radius'] )
        );

        wp_add_inline_style( 'vbp-editor-core', $css );
    }

    /**
     * Renderiza la página del editor fullscreen
     */
    public function renderizar_pagina_editor() {
        $post_id = isset( $_GET['post_id'] ) ? absint( $_GET['post_id'] ) : 0;

        // Verificar que el post existe y es del tipo correcto
        if ( ! $post_id ) {
            wp_die( esc_html__( 'ID de post no válido.', FLAVOR_PLATFORM_TEXT_DOMAIN ) );
        }

        $post = get_post( $post_id );
        if ( ! $post || ! in_array( $post->post_type, self::POST_TYPES_SOPORTADOS, true ) ) {
            wp_die( esc_html__( 'Este tipo de contenido no está soportado por Visual Builder Pro.', FLAVOR_PLATFORM_TEXT_DOMAIN ) );
        }

        // Verificar permisos
        if ( ! current_user_can( 'edit_post', $post_id ) ) {
            wp_die( esc_html__( 'No tienes permiso para editar este contenido.', FLAVOR_PLATFORM_TEXT_DOMAIN ) );
        }

        $this->post_id_actual = $post_id;

        // Definir constante para indicar contexto de editor (usado por preview cards de módulos)
        if ( ! defined( 'VBP_EDITOR_CONTEXT' ) ) {
            define( 'VBP_EDITOR_CONTEXT', true );
        }

        // Cargar el template del editor fullscreen
        $ruta_template = FLAVOR_CHAT_IA_PATH . 'includes/visual-builder-pro/views/editor-fullscreen.php';
        if ( file_exists( $ruta_template ) ) {
            include $ruta_template;
        } else {
            wp_die( esc_html__( 'Template del editor no encontrado.', FLAVOR_PLATFORM_TEXT_DOMAIN ) );
        }
    }

    /**
     * Agrega link de edición visual en la lista de posts
     *
     * @param array    $acciones Las acciones del row.
     * @param \WP_Post $post     El post actual.
     * @return array
     */
    public function agregar_link_edicion_visual( $acciones, $post ) {
        // Mostrar enlace en flavor_landing y page
        $tipos_con_enlace = array_merge( self::POST_TYPES_SOPORTADOS, array( 'page' ) );

        if ( in_array( $post->post_type, $tipos_con_enlace, true ) ) {
            $url_editor = add_query_arg(
                array(
                    'page'    => 'vbp-editor',
                    'post_id' => $post->ID,
                ),
                admin_url( 'admin.php' )
            );

            $acciones['vbp_edit'] = sprintf(
                '<a href="%s" class="vbp-edit-link">%s</a>',
                esc_url( $url_editor ),
                esc_html__( 'Editor Visual', FLAVOR_PLATFORM_TEXT_DOMAIN )
            );
        }
        return $acciones;
    }

    /**
     * Obtiene los datos del documento
     *
     * @param int $post_id ID del post.
     * @return array
     */
    public function obtener_datos_documento( $post_id ) {
        $datos_guardados = get_post_meta( $post_id, self::META_DATA, true );

        if ( empty( $datos_guardados ) ) {
            // Intentar migrar datos del Visual Builder anterior
            $datos_legacy = get_post_meta( $post_id, '_flavor_vb_data', true );
            if ( ! empty( $datos_legacy ) ) {
                $datos_guardados = $this->migrar_datos_legacy( $datos_legacy );
            }
        }

        $datos_por_defecto = array(
            'version'  => self::VERSION,
            'elements' => array(),
            'settings' => array(
                'pageWidth'       => 1200,
                'backgroundColor' => '#ffffff',
                'customCss'       => '',
            ),
        );

        if ( is_array( $datos_guardados ) ) {
            return wp_parse_args( $datos_guardados, $datos_por_defecto );
        }

        return $datos_por_defecto;
    }

    /**
     * Guarda los datos del documento
     *
     * @param int   $post_id ID del post.
     * @param array $datos   Datos a guardar.
     * @return bool
     */
    public function guardar_datos_documento( $post_id, $datos ) {
        // Verificar permisos
        if ( ! current_user_can( 'edit_post', $post_id ) ) {
            flavor_log_debug( 'guardar_datos_documento: Sin permisos para post ' . $post_id, 'VBP' );
            return false;
        }

        // Verificar que el post existe
        $post = get_post( $post_id );
        if ( ! $post ) {
            flavor_log_debug( 'guardar_datos_documento: Post ' . $post_id . ' no existe', 'VBP' );
            return false;
        }

        // Limpiar _version de los elementos antes de guardar (campo interno de Alpine)
        if ( isset( $datos['elements'] ) && is_array( $datos['elements'] ) ) {
            foreach ( $datos['elements'] as &$elemento ) {
                unset( $elemento['_version'] );
            }
        }

        $datos_actuales = get_post_meta( $post_id, self::META_DATA, true );
        $datos_comparables = $datos;
        $actuales_comparables = is_array( $datos_actuales ) ? $datos_actuales : array();

        $datos_comparables['version'] = self::VERSION;
        unset( $datos_comparables['updatedAt'] );
        unset( $actuales_comparables['updatedAt'] );

        $sin_cambios = ! empty( $actuales_comparables ) && $actuales_comparables === $datos_comparables;
        if ( $sin_cambios ) {
            return true;
        }

        // Agregar versión y timestamp
        $datos['version']   = self::VERSION;
        $datos['updatedAt'] = current_time( 'mysql' );

        // Guardar
        $resultado = update_post_meta( $post_id, self::META_DATA, $datos );

        // Actualizar versión
        update_post_meta( $post_id, self::META_VERSION, self::VERSION );

        // Disparar hook para versionado automático y otras integraciones
        if ( false !== $resultado ) {
            /**
             * Hook: vbp_content_saved
             *
             * Se dispara cuando se guarda contenido VBP exitosamente.
             * Usado por el sistema de versionado automático.
             *
             * @param int   $post_id ID del post.
             * @param array $datos   Datos guardados.
             */
            do_action( 'vbp_content_saved', $post_id, $datos );
        }

        return false !== $resultado;
    }

    /**
     * Migra datos del formato legacy al nuevo formato
     *
     * @param array $datos_legacy Datos en formato antiguo.
     * @return array Datos en formato nuevo.
     */
    private function migrar_datos_legacy( $datos_legacy ) {
        $elementos_nuevos = array();

        if ( isset( $datos_legacy['sections'] ) && is_array( $datos_legacy['sections'] ) ) {
            foreach ( $datos_legacy['sections'] as $indice => $seccion ) {
                $elementos_nuevos[] = array(
                    'id'       => $this->generar_id_elemento(),
                    'type'     => isset( $seccion['type'] ) ? $seccion['type'] : 'section',
                    'variant'  => isset( $seccion['variant'] ) ? $seccion['variant'] : 'default',
                    'name'     => isset( $seccion['name'] ) ? $seccion['name'] : 'Sección ' . ( $indice + 1 ),
                    'visible'  => true,
                    'locked'   => false,
                    'data'     => isset( $seccion['data'] ) ? $seccion['data'] : array(),
                    'styles'   => $this->crear_estilos_por_defecto(),
                    'children' => array(),
                );
            }
        }

        return array(
            'version'  => self::VERSION,
            'elements' => $elementos_nuevos,
            'settings' => array(
                'pageWidth'       => 1200,
                'backgroundColor' => '#ffffff',
                'customCss'       => '',
            ),
            'migratedFrom' => 'vb_legacy',
            'migratedAt'   => current_time( 'mysql' ),
        );
    }

    /**
     * Genera un ID único para elementos
     *
     * @return string
     */
    public function generar_id_elemento() {
        return 'el_' . bin2hex( random_bytes( 6 ) );
    }

    /**
     * Crea la estructura de estilos por defecto
     *
     * @return array
     */
    public function crear_estilos_por_defecto() {
        return array(
            'spacing'    => array(
                'margin'  => array(
                    'top'    => '',
                    'right'  => '',
                    'bottom' => '',
                    'left'   => '',
                ),
                'padding' => array(
                    'top'    => '',
                    'right'  => '',
                    'bottom' => '',
                    'left'   => '',
                ),
            ),
            'colors'     => array(
                'background' => '',
                'text'       => '',
            ),
            'typography' => array(
                'fontSize'   => '',
                'fontWeight' => '',
                'fontFamily' => '',
                'lineHeight' => '',
            ),
            'borders'    => array(
                'radius' => '',
                'width'  => '',
                'color'  => '',
                'style'  => '',
            ),
            'shadows'    => array(
                'boxShadow' => '',
            ),
            'layout'     => array(
                'display'       => '',
                'flexDirection' => '',
                'justifyContent'=> '',
                'alignItems'    => '',
                'gap'           => '',
            ),
            'advanced'   => array(
                'cssId'      => '',
                'cssClasses' => '',
                'customCss'  => '',
            ),
        );
    }

    /**
     * Handler AJAX para guardar documento
     */
    public function ajax_guardar_documento() {
        check_ajax_referer( 'vbp_editor_nonce', 'nonce' );

        $post_id = isset( $_POST['post_id'] ) ? absint( $_POST['post_id'] ) : 0;
        $titulo  = isset( $_POST['title'] ) ? sanitize_text_field( wp_unslash( $_POST['title'] ) ) : '';

        // Decodificar JSON de forma segura
        $datos = array();
        if ( isset( $_POST['data'] ) ) {
            $decoded = json_decode( wp_unslash( $_POST['data'] ), true );
            if ( is_array( $decoded ) && JSON_ERROR_NONE === json_last_error() ) {
                $datos = $decoded;
            }
        }

        if ( ! $post_id || ! current_user_can( 'edit_post', $post_id ) ) {
            wp_send_json_error( array( 'message' => __( 'Sin permiso', FLAVOR_PLATFORM_TEXT_DOMAIN ) ) );
        }

        // Actualizar título si se proporcionó
        if ( $titulo ) {
            wp_update_post(
                array(
                    'ID'         => $post_id,
                    'post_title' => $titulo,
                )
            );
        }

        // Guardar datos del builder
        $guardado = $this->guardar_datos_documento( $post_id, $datos );

        if ( $guardado ) {
            wp_send_json_success(
                array(
                    'message'   => __( 'Guardado correctamente', FLAVOR_PLATFORM_TEXT_DOMAIN ),
                    'updatedAt' => current_time( 'mysql' ),
                )
            );
        } else {
            wp_send_json_error( array( 'message' => __( 'Error al guardar', FLAVOR_PLATFORM_TEXT_DOMAIN ) ) );
        }
    }

    /**
     * Handler AJAX para cargar documento
     */
    public function ajax_cargar_documento() {
        check_ajax_referer( 'vbp_editor_nonce', 'nonce' );

        $post_id = isset( $_POST['post_id'] ) ? absint( $_POST['post_id'] ) : 0;

        if ( ! $post_id || ! current_user_can( 'edit_post', $post_id ) ) {
            wp_send_json_error( array( 'message' => __( 'Sin permiso', FLAVOR_PLATFORM_TEXT_DOMAIN ) ) );
        }

        $post     = get_post( $post_id );
        $datos    = $this->obtener_datos_documento( $post_id );
        $autosave = get_post_meta( $post_id, '_vbp_autosave', true );
        $autosave_time = get_post_meta( $post_id, '_vbp_autosave_time', true );

        if ( ! is_array( $autosave ) ) {
            $autosave = null;
        }

        $autosave_disponible = false;
        if ( $autosave && ! empty( $autosave_time ) ) {
            $documento_time = isset( $datos['updatedAt'] ) ? strtotime( $datos['updatedAt'] ) : 0;
            $snapshot_time  = strtotime( $autosave_time );
            $autosave_disponible = $snapshot_time && $snapshot_time > $documento_time;
        }

        wp_send_json_success(
            array(
                'post'  => array(
                    'id'     => $post->ID,
                    'title'  => $post->post_title,
                    'status' => $post->post_status,
                ),
                'data'  => $datos,
                'autosave' => array(
                    'available' => $autosave_disponible,
                    'time'      => $autosave_time ? $autosave_time : null,
                    'data'      => $autosave_disponible ? $autosave : null,
                ),
            )
        );
    }

    /**
     * Handler AJAX para autosave
     */
    public function ajax_autosave() {
        check_ajax_referer( 'vbp_editor_nonce', 'nonce' );

        $post_id = isset( $_POST['post_id'] ) ? absint( $_POST['post_id'] ) : 0;

        // Decodificar JSON de forma segura
        $datos = array();
        if ( isset( $_POST['data'] ) ) {
            $decoded = json_decode( wp_unslash( $_POST['data'] ), true );
            if ( is_array( $decoded ) && JSON_ERROR_NONE === json_last_error() ) {
                $datos = $decoded;
            }
        }

        if ( ! $post_id || ! current_user_can( 'edit_post', $post_id ) ) {
            wp_send_json_error( array( 'message' => __( 'Sin permiso', FLAVOR_PLATFORM_TEXT_DOMAIN ) ) );
        }

        $guardado = $this->guardar_datos_documento( $post_id, $datos );
        if ( ! $guardado ) {
            wp_send_json_error( array( 'message' => __( 'Error al guardar autosave', FLAVOR_PLATFORM_TEXT_DOMAIN ) ) );
        }

        // Mantener snapshot temporal para posible recuperación/diagnóstico
        update_post_meta( $post_id, '_vbp_autosave', $datos );
        update_post_meta( $post_id, '_vbp_autosave_time', current_time( 'mysql' ) );

        wp_send_json_success(
            array(
                'message'   => __( 'Autosave completado', FLAVOR_PLATFORM_TEXT_DOMAIN ),
                'timestamp' => current_time( 'mysql' ),
            )
        );
    }

    /**
     * Handler AJAX para renderizar elemento
     */
    public function ajax_render_elemento() {
        check_ajax_referer( 'vbp_editor_nonce', 'nonce' );

        $preview_mode = isset( $_POST['preview_mode'] ) ? sanitize_text_field( wp_unslash( $_POST['preview_mode'] ) ) : 'card';

        // Decodificar JSON de forma segura
        $elemento = array();
        if ( isset( $_POST['element'] ) ) {
            $decoded = json_decode( wp_unslash( $_POST['element'] ), true );
            if ( is_array( $decoded ) && JSON_ERROR_NONE === json_last_error() ) {
                $elemento = $decoded;
            }
        }

        if ( empty( $elemento ) ) {
            wp_send_json_error( array( 'message' => __( 'Elemento no válido', FLAVOR_PLATFORM_TEXT_DOMAIN ) ) );
        }

        // Definir contexto de editor para mostrar preview cards en lugar de shortcodes reales
        // Excepto si se solicita preview_mode = 'live' para previsualización real
        if ( 'live' !== $preview_mode && ! defined( 'VBP_EDITOR_CONTEXT' ) ) {
            define( 'VBP_EDITOR_CONTEXT', true );
        }

        // Obtener instancia del canvas renderer
        if ( class_exists( 'Flavor_VBP_Canvas' ) ) {
            $canvas = Flavor_VBP_Canvas::get_instance();
            $html   = $canvas->renderizar_elemento( $elemento );

            wp_send_json_success(
                array(
                    'html' => $html,
                )
            );
        }

        wp_send_json_error( array( 'message' => __( 'Renderer no disponible', FLAVOR_PLATFORM_TEXT_DOMAIN ) ) );
    }

    /**
     * Obtiene el ID del post actual
     *
     * @return int
     */
    public function get_post_id_actual() {
        return $this->post_id_actual;
    }

    /**
     * Agrega el atributo defer a los scripts de Alpine.js
     *
     * @param string $tag    Tag HTML del script.
     * @param string $handle Handle del script.
     * @return string
     */
    public function agregar_defer_alpine( $tag, $handle ) {
        // Agregar defer a Collapse plugin y Alpine.js
        $alpine_handles = array( 'alpinejs-collapse', 'alpinejs' );
        if ( in_array( $handle, $alpine_handles, true ) && strpos( $tag, 'defer' ) === false ) {
            return str_replace( ' src', ' defer src', $tag );
        }
        return $tag;
    }

    /**
     * Agrega el atributo type="module" al script de emoji-picker
     *
     * @param string $tag    Tag HTML del script.
     * @param string $handle Handle del script.
     * @return string
     */
    public function agregar_module_emoji_picker( $tag, $handle ) {
        if ( 'emoji-picker-element' === $handle ) {
            return str_replace( ' src', ' type="module" src', $tag );
        }
        return $tag;
    }

    /**
     * Handler AJAX para publicar documento
     */
    public function ajax_publicar_documento() {
        check_ajax_referer( 'vbp_editor_nonce', 'nonce' );

        $post_id = isset( $_POST['post_id'] ) ? absint( $_POST['post_id'] ) : 0;

        if ( ! $post_id ) {
            wp_send_json_error( array( 'message' => __( 'ID de post requerido', FLAVOR_PLATFORM_TEXT_DOMAIN ) ) );
        }

        // Verificar permiso correcto según el tipo de post
        $post = get_post( $post_id );
        if ( ! $post ) {
            wp_send_json_error( array( 'message' => __( 'Post no encontrado', FLAVOR_PLATFORM_TEXT_DOMAIN ) ) );
        }

        $post_type_obj = get_post_type_object( $post->post_type );
        $capability = $post_type_obj ? $post_type_obj->cap->publish_posts : 'publish_posts';

        if ( ! current_user_can( $capability ) ) {
            wp_send_json_error( array( 'message' => __( 'Sin permiso para publicar', FLAVOR_PLATFORM_TEXT_DOMAIN ) ) );
        }

        $resultado = wp_update_post(
            array(
                'ID'          => $post_id,
                'post_status' => 'publish',
            )
        );

        if ( $resultado && ! is_wp_error( $resultado ) ) {
            wp_send_json_success(
                array(
                    'message' => __( 'Publicado correctamente', FLAVOR_PLATFORM_TEXT_DOMAIN ),
                    'url'     => get_permalink( $post_id ),
                )
            );
        }

        wp_send_json_error( array( 'message' => __( 'Error al publicar', FLAVOR_PLATFORM_TEXT_DOMAIN ) ) );
    }

    /**
     * Handler AJAX para exportar template
     */
    public function ajax_exportar_template() {
        check_ajax_referer( 'vbp_editor_nonce', 'nonce' );

        $nombre = isset( $_POST['name'] ) ? sanitize_text_field( wp_unslash( $_POST['name'] ) ) : __( 'Mi Template', FLAVOR_PLATFORM_TEXT_DOMAIN );

        // Decodificar JSON de forma segura
        $elements = array();
        if ( isset( $_POST['elements'] ) ) {
            $decoded = json_decode( wp_unslash( $_POST['elements'] ), true );
            if ( is_array( $decoded ) && JSON_ERROR_NONE === json_last_error() ) {
                $elements = $decoded;
            }
        }

        if ( empty( $elements ) ) {
            wp_send_json_error( array( 'message' => __( 'No hay elementos para exportar', FLAVOR_PLATFORM_TEXT_DOMAIN ) ) );
        }

        // Crear template como opción
        $templates = get_option( 'vbp_user_templates', array() );

        $nuevo_template = array(
            'id'        => 'tpl_' . bin2hex( random_bytes( 6 ) ),
            'name'      => $nombre,
            'elements'  => $elements,
            'createdAt' => current_time( 'mysql' ),
            'author'    => get_current_user_id(),
        );

        $templates[] = $nuevo_template;
        update_option( 'vbp_user_templates', $templates );

        wp_send_json_success(
            array(
                'message'  => __( 'Template guardado', FLAVOR_PLATFORM_TEXT_DOMAIN ),
                'template' => $nuevo_template,
            )
        );
    }

    /**
     * Handler AJAX para importar template
     */
    public function ajax_importar_template() {
        check_ajax_referer( 'vbp_editor_nonce', 'nonce' );

        $template_id = isset( $_POST['template_id'] ) ? sanitize_text_field( wp_unslash( $_POST['template_id'] ) ) : '';

        if ( empty( $template_id ) ) {
            wp_send_json_error( array( 'message' => __( 'ID de template inválido', FLAVOR_PLATFORM_TEXT_DOMAIN ) ) );
        }

        $templates = get_option( 'vbp_user_templates', array() );

        foreach ( $templates as $template ) {
            if ( isset( $template['id'] ) && $template['id'] === $template_id ) {
                wp_send_json_success(
                    array(
                        'elements' => $template['elements'],
                        'name'     => $template['name'],
                    )
                );
            }
        }

        wp_send_json_error( array( 'message' => __( 'Template no encontrado', FLAVOR_PLATFORM_TEXT_DOMAIN ) ) );
    }

    /**
     * Handler AJAX para obtener templates
     */
    public function ajax_obtener_templates() {
        check_ajax_referer( 'vbp_editor_nonce', 'nonce' );

        $templates = get_option( 'vbp_user_templates', array() );

        // Formatear para respuesta
        $resultado = array();
        foreach ( $templates as $template ) {
            $resultado[] = array(
                'id'        => $template['id'],
                'name'      => $template['name'],
                'createdAt' => $template['createdAt'],
            );
        }

        wp_send_json_success( $resultado );
    }

    /**
     * Handler AJAX para obtener bloques
     */
    public function ajax_obtener_bloques() {
        check_ajax_referer( 'vbp_editor_nonce', 'nonce' );

        if ( class_exists( 'Flavor_VBP_Block_Library' ) ) {
            $libreria   = Flavor_VBP_Block_Library::get_instance();
            $categorias = $libreria->get_categorias_con_bloques();
            wp_send_json_success( $categorias );
        }

        wp_send_json_success( array() );
    }
}

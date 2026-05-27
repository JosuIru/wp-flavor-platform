<?php
/**
 * Visual Builder Pro - Canvas Renderer
 *
 * Renderizado de elementos para el canvas y frontend.
 *
 * @package Flavor_Platform
 * @subpackage Visual_Builder_Pro
 * @since 2.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Clase para renderizar elementos del Visual Builder Pro
 *
 * @since 2.0.0
 */
class Flavor_VBP_Canvas {

    /**
     * Instancia singleton
     *
     * @var Flavor_VBP_Canvas|null
     */
    private static $instancia = null;

    /**
     * Obtiene la instancia singleton
     *
     * @return Flavor_VBP_Canvas
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
        // Filtro para el contenido del post
        add_filter( 'the_content', array( $this, 'renderizar_contenido_landing' ), 20 );

        // Cargar CSS del frontend en landings
        add_action( 'wp_enqueue_scripts', array( $this, 'cargar_css_frontend' ) );
    }

    /**
     * Carga el CSS del frontend para landings
     */
    public function cargar_css_frontend() {
        global $post;

        // Solo cargar en flavor_landing o si hay shortcode VBP
        if ( ! $post || 'flavor_landing' !== $post->post_type ) {
            return;
        }

        // Cargar Font Awesome para iconos
        wp_enqueue_style(
            'font-awesome',
            'https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css',
            array(),
            '6.4.0'
        );

        // Cargar Material Icons para iconos de componentes
        wp_enqueue_style(
            'material-icons',
            'https://fonts.googleapis.com/icon?family=Material+Icons',
            array(),
            null
        );

        $css_url = FLAVOR_PLATFORM_URL . 'assets/vbp/css/frontend-components.css';
        $css_path = FLAVOR_PLATFORM_PATH . 'assets/vbp/css/frontend-components.css';

        if ( file_exists( $css_path ) ) {
            wp_enqueue_style(
                'vbp-frontend-components',
                $css_url,
                array( 'font-awesome' ),
                '2.0.0'
            );
        }

        // Cargar CSS de animaciones
        $anim_css_url = FLAVOR_PLATFORM_URL . 'assets/vbp/css/animations.css';
        $anim_css_path = FLAVOR_PLATFORM_PATH . 'assets/vbp/css/animations.css';

        if ( file_exists( $anim_css_path ) ) {
            wp_enqueue_style(
                'vbp-animations',
                $anim_css_url,
                array( 'vbp-frontend-components' ),
                '2.0.0'
            );
        }

        // Cargar JavaScript para componentes interactivos
        $js_url = FLAVOR_PLATFORM_URL . 'assets/vbp/js/vbp-frontend.js';
        $js_path = FLAVOR_PLATFORM_PATH . 'assets/vbp/js/vbp-frontend.js';

        if ( file_exists( $js_path ) ) {
            wp_enqueue_script(
                'vbp-frontend',
                $js_url,
                array(),
                '2.0.0',
                true
            );

            // Pasar URL de AJAX para formularios + base REST para bloques
            // dinámicos del frontend (Lista Dinámica usa collections/load-more).
            $inline_config = sprintf(
                'window.vbp_ajax_url = "%s"; window.VBP_Config = window.VBP_Config || {}; window.VBP_Config.restUrl = "%s";',
                esc_js( admin_url( 'admin-ajax.php' ) ),
                esc_js( rest_url( 'flavor-vbp/v1/' ) )
            );

            wp_add_inline_script( 'vbp-frontend', $inline_config, 'before' );
        }

        // Cargar JavaScript de animaciones
        $anim_js_url = FLAVOR_PLATFORM_URL . 'assets/vbp/js/vbp-animations.js';
        $anim_js_path = FLAVOR_PLATFORM_PATH . 'assets/vbp/js/vbp-animations.js';

        if ( file_exists( $anim_js_path ) ) {
            wp_enqueue_script(
                'vbp-animations',
                $anim_js_url,
                array( 'vbp-frontend' ),
                '2.0.0',
                true
            );
        }
    }

    /**
     * Cache de mapeo de colores a variables CSS
     *
     * @var array|null
     */
    private $color_to_variable_map = null;

    /**
     * Obtiene el mapeo de colores hex a variables CSS
     *
     * NOTA: NO hacemos mapeo automático basado en el tema activo porque
     * el mismo color puede tener diferentes propósitos según el tema
     * (ej: #ffffff es fondo en tema claro pero texto en tema oscuro).
     *
     * Solo mapeamos colores que son claramente del sistema de diseño
     * (primary, secondary, etc.) basados en los defaults del plugin.
     *
     * @return array Mapeo color_hex => variable_css
     */
    private function get_color_variable_map() {
        if ( null !== $this->color_to_variable_map ) {
            return $this->color_to_variable_map;
        }

        // Mapeo de colores del sistema de diseño por defecto
        // Estos son los colores "canónicos" del tema claro por defecto
        $this->color_to_variable_map = array(
            // Primary (azul por defecto)
            '#3b82f6' => 'var(--flavor-primary)',
            '#2563eb' => 'var(--flavor-primary-dark)',
            '#1d4ed8' => 'var(--flavor-primary-hover)',
            '#dbeafe' => 'var(--flavor-primary-light)',

            // Secondary (morado por defecto)
            '#8b5cf6' => 'var(--flavor-secondary)',

            // Semantic colors
            '#22c55e' => 'var(--flavor-success)',
            '#10b981' => 'var(--flavor-success)',
            '#f59e0b' => 'var(--flavor-warning)',
            '#ef4444' => 'var(--flavor-error)',
            '#dc2626' => 'var(--flavor-error)',

            // Borders
            '#e5e7eb' => 'var(--flavor-border)',
            '#e2e8f0' => 'var(--flavor-border)',

            // Grays for secondary/muted
            '#6b7280' => 'var(--flavor-text-muted)',
            '#64748b' => 'var(--flavor-text-muted)',
            '#9ca3af' => 'var(--flavor-text-muted)',
        );

        // NO mapeamos #ffffff, #000000, ni colores de fondo/texto
        // porque su significado depende del tema (claro vs oscuro)

        return $this->color_to_variable_map;
    }

    /**
     * Convierte un color hex a variable CSS si corresponde
     *
     * @param string $color Color en formato hex.
     * @param bool   $force_variable Si es true, siempre intenta usar variable.
     * @return string Color original o variable CSS.
     */
    public function map_color_to_variable( $color, $force_variable = false ) {
        if ( empty( $color ) || 'transparent' === $color ) {
            return $color;
        }

        // Normalizar color
        $normalized = strtolower( trim( $color ) );

        // Si ya es una variable CSS, devolverla
        if ( strpos( $normalized, 'var(' ) === 0 ) {
            return $color;
        }

        // Obtener mapeo
        $map = $this->get_color_variable_map();

        // Buscar en el mapeo
        if ( isset( $map[ $normalized ] ) ) {
            $var_name = $map[ $normalized ];
            // Si ya es var(...), devolverlo; si no, envolverlo
            if ( strpos( $var_name, 'var(' ) === 0 ) {
                return $var_name;
            }
            return 'var(' . $var_name . ')';
        }

        // No encontrado, devolver color original
        return $color;
    }

    /**
     * Renderiza el contenido de una landing page
     *
     * @param string $content Contenido original.
     * @return string
     */
    public function renderizar_contenido_landing( $content ) {
        global $post;

        if ( ! $post || 'flavor_landing' !== $post->post_type ) {
            return $content;
        }

        // Verificar si estamos en el loop principal
        if ( ! is_main_query() || ! in_the_loop() ) {
            return $content;
        }

        $editor = Flavor_VBP_Editor::get_instance();
        $datos  = $editor->obtener_datos_documento( $post->ID );

        if ( empty( $datos['elements'] ) ) {
            return $content;
        }

        $html = $this->renderizar_documento( $datos );

        return $html;
    }

    /**
     * Renderiza un documento completo
     *
     * @param array $datos Datos del documento.
     * @return string
     */
    public function renderizar_documento( $datos ) {
        $elementos  = isset( $datos['elements'] ) ? $datos['elements'] : array();
        $settings   = isset( $datos['settings'] ) ? $datos['settings'] : array();

        $html = '';

        // Generar CSS global para body y contenedor (si es fullWidth)
        $css_global = $this->generar_css_global( $settings );
        if ( ! empty( $css_global ) ) {
            $html .= '<style>' . $css_global . '</style>';
        }

        // Estilos inline del contenedor (si no es fullWidth)
        $estilos_pagina = $this->generar_estilos_pagina( $settings );
        $style_attr = ! empty( $estilos_pagina ) ? ' style="' . esc_attr( $estilos_pagina ) . '"' : '';

        $html .= '<div class="vbp-landing"' . $style_attr . '>';

        $es_contexto_editor = $this->is_editor_context();

        foreach ( $elementos as $elemento ) {
            if ( isset( $elemento['visible'] ) && false === $elemento['visible'] ) {
                continue;
            }
            $contenido_elemento = $this->renderizar_elemento( $elemento );

            // En contexto editor envolvemos cada elemento para poder identificarlo
            // desde el bridge JS del iframe (seleccion, edicion inline, refresh).
            if ( $es_contexto_editor && ! empty( $elemento['id'] ) ) {
                $html .= sprintf(
                    '<div class="vbp-iframe-element" data-element-id="%s" data-element-type="%s">%s</div>',
                    esc_attr( $elemento['id'] ),
                    esc_attr( $elemento['type'] ?? '' ),
                    $contenido_elemento
                );
            } else {
                $html .= $contenido_elemento;
            }
        }

        $html .= '</div>';

        // Agregar CSS personalizado
        if ( ! empty( $settings['customCss'] ) ) {
            $html .= '<style>' . wp_strip_all_tags( $settings['customCss'] ) . '</style>';
        }

        return $html;
    }

    /**
     * Genera CSS global para la página (body, contenedor, variables)
     *
     * @param array $settings Configuración de la página.
     * @return string
     */
    private function generar_css_global( $settings ) {
        $css = array();

        // Generar variables CSS desde la configuración del tema
        $css_variables = $this->generar_css_variables( $settings );
        if ( ! empty( $css_variables ) ) {
            $css[] = ':root { ' . $css_variables . ' }';
        }

        // Determinar si es full width (acepta boolean, string "true", "1", 1)
        $full_width = isset( $settings['fullWidth'] ) && filter_var( $settings['fullWidth'], FILTER_VALIDATE_BOOLEAN );

        // Estilos del body
        $body_styles = array();
        if ( ! empty( $settings['backgroundColor'] ) ) {
            // NO mapear backgroundColor - usar el color tal cual
            // Los usuarios pueden usar var(--flavor-bg) directamente si quieren theme-awareness
            $body_styles[] = 'background-color: ' . esc_attr( $settings['backgroundColor'] ) . ' !important';
        }
        if ( ! empty( $body_styles ) ) {
            $css[] = 'body.single-flavor_landing { ' . implode( '; ', $body_styles ) . '; }';
        }

        // Si es fullWidth, el contenedor es 100vw
        if ( $full_width ) {
            $css[] = '.vbp-landing { width: 100%; max-width: 100%; margin: 0; padding: 0; }';

            // En fullWidth, limitar el contenido interno de las secciones si hay pageWidth
            if ( ! empty( $settings['pageWidth'] ) ) {
                $max_width = absint( $settings['pageWidth'] ) . 'px';
                $css[] = '.vbp-landing .vbp-features__grid,
                          .vbp-landing .vbp-testimonials__grid,
                          .vbp-landing .vbp-pricing__grid,
                          .vbp-landing .vbp-team__grid,
                          .vbp-landing .vbp-faq__list,
                          .vbp-landing .vbp-cta__content,
                          .vbp-landing .vbp-two-columns,
                          .vbp-landing .vbp-process,
                          .vbp-landing .vbp-timeline__items,
                          .vbp-landing .vbp-product-grid,
                          .vbp-landing .vbp-blog-grid { max-width: ' . $max_width . '; margin-left: auto; margin-right: auto; }';
            }
        }

        return implode( "\n", $css );
    }

    /**
     * Genera variables CSS desde la configuración del tema
     *
     * Obtiene colores de:
     * 1. Settings de la página actual
     * 2. Configuración global del diseño
     * 3. Preset del tema activo
     *
     * @param array $settings Configuración de la página.
     * @return string Variables CSS en formato "--var: value; --var2: value2;"
     */
    private function generar_css_variables( $settings ) {
        $variables = array();

        // Colores desde los settings de la página
        $page_colors = array(
            'primaryColor'   => '--flavor-primary',
            'secondaryColor' => '--flavor-secondary',
            'accentColor'    => '--flavor-accent',
            'textColor'      => '--flavor-text',
            'backgroundColor' => '--flavor-bg',
        );

        foreach ( $page_colors as $setting_key => $var_name ) {
            if ( ! empty( $settings[ $setting_key ] ) ) {
                $color = sanitize_hex_color( $settings[ $setting_key ] );
                if ( $color ) {
                    $variables[] = $var_name . ': ' . $color;
                }
            }
        }

        // Colores desde design settings globales
        $design_settings = get_option( 'flavor_design_settings', array() );
        $global_colors = array(
            'primary_color'    => '--flavor-primary',
            'secondary_color'  => '--flavor-secondary',
            'accent_color'     => '--flavor-accent',
            'text_color'       => '--flavor-text',
            'text_muted_color' => '--flavor-text-muted',
            'background_color' => '--flavor-bg',
            'border_color'     => '--flavor-border',
        );

        foreach ( $global_colors as $setting_key => $var_name ) {
            // Solo añadir si no existe ya (page settings tienen prioridad)
            if ( ! empty( $design_settings[ $setting_key ] ) && ! in_array( $var_name, array_map( function( $v ) { return explode( ':', $v )[0]; }, $variables ), true ) ) {
                $color = sanitize_hex_color( $design_settings[ $setting_key ] );
                if ( $color ) {
                    $variables[] = $var_name . ': ' . $color;
                }
            }
        }

        // Colores desde el preset del tema activo
        $active_theme = get_option( 'flavor_active_theme', '' );
        if ( $active_theme && function_exists( 'flavor_get_theme_presets' ) ) {
            $presets = flavor_get_theme_presets();
            if ( isset( $presets[ $active_theme ]['variables'] ) ) {
                foreach ( $presets[ $active_theme ]['variables'] as $var_name => $var_value ) {
                    // Solo añadir si no existe ya
                    $existing_vars = array_map( function( $v ) { return trim( explode( ':', $v )[0] ); }, $variables );
                    if ( ! in_array( $var_name, $existing_vars, true ) ) {
                        $variables[] = $var_name . ': ' . esc_attr( $var_value );
                    }
                }
            }
        }

        return implode( '; ', $variables );
    }

    /**
     * Genera estilos CSS para la página
     *
     * @param array $settings Configuración de la página.
     * @return string
     */
    private function generar_estilos_pagina( $settings ) {
        $estilos = array();

        // Si no es fullWidth, aplicar estilos tradicionales al contenedor
        // Acepta boolean, string "true", "1", 1
        $full_width = isset( $settings['fullWidth'] ) && filter_var( $settings['fullWidth'], FILTER_VALIDATE_BOOLEAN );

        if ( ! $full_width ) {
            if ( ! empty( $settings['backgroundColor'] ) ) {
                // NO mapear backgroundColor a variables - usar el color tal cual
                // El usuario puede usar var(--flavor-bg) directamente si quiere theme-awareness
                $estilos[] = 'background-color: ' . esc_attr( $settings['backgroundColor'] );
            }

            if ( ! empty( $settings['pageWidth'] ) ) {
                $estilos[] = 'max-width: ' . absint( $settings['pageWidth'] ) . 'px';
                $estilos[] = 'margin: 0 auto';
            }
        }

        return implode( '; ', $estilos );
    }

    /**
     * Renderiza un elemento
     *
     * @param array $elemento Datos del elemento.
     * @return string
     */
    public function renderizar_elemento( $elemento ) {
        $tipo     = isset( $elemento['type'] ) ? $elemento['type'] : 'text';
        $data     = isset( $elemento['data'] ) ? $elemento['data'] : array();
        $estilos  = isset( $elemento['styles'] ) ? $elemento['styles'] : array();
        $variante = isset( $elemento['variant'] ) ? $elemento['variant'] : 'default';

        // Mapear alias de tipos a tipos registrados
        $tipo = $this->mapear_tipo_elemento( $tipo );
        $elemento['type'] = $tipo;

        // Buscar renderizador específico
        $metodo_render = 'render_' . str_replace( '-', '_', $tipo );

        if ( method_exists( $this, $metodo_render ) ) {
            return $this->$metodo_render( $elemento );
        }

        // Intentar con shortcode de módulo
        $libreria = class_exists( 'Flavor_VBP_Block_Library' )
            ? Flavor_VBP_Block_Library::get_instance()
            : null;

        if ( $libreria ) {
            $bloque = $libreria->get_bloque( $tipo );
            if ( $bloque && ! empty( $bloque['shortcode'] ) ) {
                // En el editor, mostrar preview card en lugar de shortcode real
                if ( $this->is_editor_context() ) {
                    return $this->render_module_preview( $elemento, $bloque );
                }
                // Frontend: renderizar shortcode real
                return $this->renderizar_shortcode( $bloque['shortcode'], $data, $estilos );
            }
        }

        // Renderizado genérico
        return $this->render_generico( $elemento );
    }

    /**
     * Mapea alias de tipos de elemento a tipos registrados
     *
     * Los tipos que comienzan con render_ son manejados por métodos locales,
     * los demás buscan en Block Library y luego en render genérico.
     *
     * @param string $tipo Tipo original.
     * @return string Tipo mapeado.
     */
    private function mapear_tipo_elemento( $tipo ) {
        // Alias que mapean a métodos de renderizado locales
        $alias = array(
            // Widgets con métodos de renderizado propios
            'widget_social_feed'      => 'social_feed',      // render_social_feed
            'widget_sello_conciencia' => 'sello_conciencia_widget', // render_sello_conciencia_widget

            // Tipos que tienen método render_* propio
            'product_grid'            => 'product_grid',     // render_product_grid
            'blog_grid'               => 'blog_grid',        // render_blog_grid
            'two_columns'             => 'contact_section',   // render_contact_section (legacy alias)
            'contact_section'         => 'contact_section',   // render_contact_section
            'registration_form'       => 'registration_form',// render_registration_form
            'contact_form'            => 'contact_form',     // render_contact_form
            'contact_info'            => 'contact_info',     // render_contact_info
            'audio'                   => 'audio',            // render_audio
            'embed'                   => 'embed',            // render_embed

            // Alias que mapean a tipos de Block Library (con shortcodes)
            'widget_red_social'       => 'rs-feed',
            'widget_historias'        => 'rs-historias',
            'widget_eventos'          => 'eventos-proximos',
            'widget_socios'           => 'socios-listado',
            'widget_foros'            => 'foros-listado',
            'widget_biblioteca'       => 'biblioteca-catalogo',
            'widget_marketplace'      => 'marketplace-productos',
            'widget_grupos_consumo'   => 'gc-proximos-ciclos',
            'widget_comunidades'      => 'comunidades-listado',
            'widget_carpooling'       => 'carpooling-viajes',
            'widget_encuestas'        => 'encuestas-activas',
            'widget_participacion'    => 'participacion-procesos',
            'widget_transparencia'    => 'transparencia-portal',
            'widget_noticias'         => 'blog_grid',
            'widget_productos'        => 'product_grid',
            'widget_timeline'         => 'timeline',

            // Alias con guiones bajos
            'social_feed'             => 'social_feed',
            'red_social'              => 'rs-feed',
            'grupos_consumo'          => 'gc-proximos-ciclos',

            // Alias simplificados
            'feed_social'             => 'social_feed',
            'feed_comunidad'          => 'comunidades-actividad',
        );

        return isset( $alias[ $tipo ] ) ? $alias[ $tipo ] : $tipo;
    }

    /**
     * Verifica si estamos en contexto de editor
     *
     * @return bool
     */
    private function is_editor_context() {
        return defined( 'VBP_EDITOR_CONTEXT' ) && VBP_EDITOR_CONTEXT;
    }

    /**
     * Atributos para edición inline en contexto editor.
     *
     * @param string $field Campo a editar.
     * @return string
     */
    private function get_editor_contenteditable_attr( $field ) {
        if ( ! $this->is_editor_context() ) {
            return '';
        }

        return ' contenteditable="true" data-field="' . esc_attr( $field ) . '" spellcheck="false"';
    }

    /**
     * Atributos para edición inline con ruta anidada en contexto editor.
     *
     * @param string $path Ruta del dato a editar.
     * @return string
     */
    private function get_editor_contenteditable_path_attr( $path ) {
        if ( ! $this->is_editor_context() ) {
            return '';
        }

        return ' contenteditable="true" data-path="' . esc_attr( $path ) . '" spellcheck="false"';
    }

    /**
     * Atributos seguros para enlaces editables en contexto editor.
     *
     * @param string $field Campo a editar.
     * @return string
     */
    private function get_editor_link_attr( $field ) {
        if ( ! $this->is_editor_context() ) {
            return '';
        }

        return ' contenteditable="true" data-field="' . esc_attr( $field ) . '" spellcheck="false" tabindex="-1" onclick="return false;"';
    }

    /**
     * Atributos seguros para enlaces editables con ruta anidada.
     *
     * @param string $path Ruta del dato a editar.
     * @return string
     */
    private function get_editor_link_path_attr( $path ) {
        if ( ! $this->is_editor_context() ) {
            return '';
        }

        return ' contenteditable="true" data-path="' . esc_attr( $path ) . '" spellcheck="false" tabindex="-1" onclick="return false;"';
    }

    /**
     * Renderiza un encabezado de sección homogéneo y editable en contexto editor.
     *
     * @param string $titulo           Título.
     * @param string $subtitulo        Subtítulo.
     * @param string $titulo_color     Color del título.
     * @param string $subtitulo_color  Color del subtítulo.
     * @param string $titulo_path      Campo o ruta del título.
     * @param string $subtitulo_path   Campo o ruta del subtítulo.
     * @return string
     */
    private function render_section_header( $titulo, $subtitulo, $titulo_color = '', $subtitulo_color = '', $titulo_path = 'titulo', $subtitulo_path = 'subtitulo' ) {
        if ( empty( $titulo ) && empty( $subtitulo ) ) {
            return '';
        }

        $html = '<div class="vbp-section__header">';

        if ( ! empty( $titulo ) ) {
            $titulo_style = $titulo_color ? ' style="color: ' . esc_attr( $titulo_color ) . ';"' : '';
            $titulo_attr  = strpos( $titulo_path, '.' ) !== false
                ? $this->get_editor_contenteditable_path_attr( $titulo_path )
                : $this->get_editor_contenteditable_attr( $titulo_path );
            $html        .= '<h2 class="vbp-section__title"' . $titulo_style . $titulo_attr . '>' . esc_html( $titulo ) . '</h2>';
        }

        if ( ! empty( $subtitulo ) ) {
            $subtitulo_style = $subtitulo_color ? ' style="color: ' . esc_attr( $subtitulo_color ) . ';"' : '';
            $subtitulo_attr  = strpos( $subtitulo_path, '.' ) !== false
                ? $this->get_editor_contenteditable_path_attr( $subtitulo_path )
                : $this->get_editor_contenteditable_attr( $subtitulo_path );
            $html           .= '<p class="vbp-section__subtitle"' . $subtitulo_style . $subtitulo_attr . '>' . esc_html( $subtitulo ) . '</p>';
        }

        $html .= '</div>';

        return $html;
    }

    /**
     * Renderiza una preview card para widgets de módulos
     *
     * @param array $elemento Datos del elemento.
     * @param array $bloque   Información del bloque.
     * @return string
     */
    private function render_module_preview( $elemento, $bloque ) {
        $module_name  = isset( $bloque['module'] ) ? $bloque['module'] : 'módulo';
        $widget_name  = isset( $bloque['name'] ) ? $bloque['name'] : $elemento['type'];
        $icon         = isset( $bloque['icon'] ) ? $bloque['icon'] : '';
        $shortcode    = isset( $bloque['shortcode'] ) ? $bloque['shortcode'] : '';
        $estilos      = isset( $elemento['styles'] ) ? $elemento['styles'] : array();
        $estilos_css  = $this->generar_estilos_elemento( $estilos );
        $data         = isset( $elemento['data'] ) ? $elemento['data'] : array();

        // Determinar color de gradiente basado en categoría
        $categoria = isset( $bloque['category'] ) ? $bloque['category'] : 'modules';
        $colores = array(
            'modules'     => array( 'bg' => '#667eea', 'accent' => '#764ba2' ),
            'maps'        => array( 'bg' => '#11998e', 'accent' => '#38ef7d' ),
            'economy'     => array( 'bg' => '#f093fb', 'accent' => '#f5576c' ),
            'community'   => array( 'bg' => '#4facfe', 'accent' => '#00f2fe' ),
            'social'      => array( 'bg' => '#ff6b6b', 'accent' => '#feca57' ),
            'governance'  => array( 'bg' => '#5f27cd', 'accent' => '#341f97' ),
            'commerce'    => array( 'bg' => '#00d2d3', 'accent' => '#01a3a4' ),
            'education'   => array( 'bg' => '#ff9f43', 'accent' => '#ee5a24' ),
            'dashboard'   => array( 'bg' => '#576574', 'accent' => '#222f3e' ),
        );
        $color = isset( $colores[ $categoria ] ) ? $colores[ $categoria ] : $colores['modules'];

        // Generar preview visual según el tipo de widget
        $preview_content = $this->generar_preview_visual( $elemento['type'], $data, $bloque );

        $html = '<div class="vbp-widget-preview" data-widget-type="' . esc_attr( $elemento['type'] ) . '" style="' . esc_attr( $estilos_css ) . '">';

        // Header del widget
        $html .= '<div class="vbp-widget-preview__header" style="background: linear-gradient(135deg, ' . esc_attr( $color['bg'] ) . ' 0%, ' . esc_attr( $color['accent'] ) . ' 100%);">';
        $html .= '<div class="vbp-widget-preview__icon">' . $icon . '</div>';
        $html .= '<div class="vbp-widget-preview__meta">';
        $html .= '<span class="vbp-widget-preview__name">' . esc_html( $widget_name ) . '</span>';
        $html .= '<span class="vbp-widget-preview__module">' . esc_html( ucfirst( str_replace( '-', ' ', $module_name ) ) ) . '</span>';
        $html .= '</div>';
        if ( $shortcode ) {
            $html .= '<code class="vbp-widget-preview__shortcode">[' . esc_html( $shortcode ) . ']</code>';
        }
        $html .= '</div>';

        // Contenido del preview
        $html .= '<div class="vbp-widget-preview__content">';
        $html .= $preview_content;
        $html .= '</div>';

        // Footer con configuración
        if ( ! empty( $data ) ) {
            $html .= '<div class="vbp-widget-preview__footer">';
            $config_count = 0;
            foreach ( $data as $key => $value ) {
                if ( $config_count >= 4 ) break;
                if ( ! empty( $value ) && is_scalar( $value ) && ! in_array( $key, array( 'titulo', 'subtitulo', 'fondo' ) ) ) {
                    $label = ucfirst( str_replace( '_', ' ', $key ) );
                    $display_value = is_bool( $value ) ? ( $value ? '✓' : '✗' ) : ( strlen( $value ) > 20 ? substr( $value, 0, 17 ) . '...' : $value );
                    $html .= '<span class="vbp-widget-preview__config-item"><strong>' . esc_html( $label ) . ':</strong> ' . esc_html( $display_value ) . '</span>';
                    $config_count++;
                }
            }
            $html .= '</div>';
        }

        $html .= '</div>';

        return $html;
    }

    /**
     * Genera contenido visual de preview para diferentes tipos de widgets
     *
     * @param string $tipo  Tipo de widget.
     * @param array  $data  Datos del widget.
     * @param array  $bloque Información del bloque.
     * @return string HTML del preview visual.
     */
    private function generar_preview_visual( $tipo, $data, $bloque ) {
        $titulo = $data['titulo'] ?? $data['title'] ?? '';
        $subtitulo = $data['subtitulo'] ?? $data['subtitle'] ?? '';

        // Determinar tipo base (sin prefijo widget_)
        $tipo_base = preg_replace( '/^widget_/', '', $tipo );
        $tipo_base = str_replace( '-', '_', $tipo_base );

        // Previews específicos por tipo de widget
        switch ( $tipo_base ) {
            case 'social_feed':
            case 'rs_feed':
            case 'red_social':
                return $this->preview_social_feed( $data );

            case 'eventos':
            case 'eventos_proximos':
                return $this->preview_eventos( $data );

            case 'socios':
            case 'socios_listado':
                return $this->preview_listado_cards( $data, 'usuarios' );

            case 'marketplace':
            case 'marketplace_productos':
                return $this->preview_productos( $data );

            case 'grupos_consumo':
            case 'gc_proximos_ciclos':
                return $this->preview_ciclos( $data );

            case 'foros':
            case 'foros_listado':
                return $this->preview_listado_filas( $data, 'temas' );

            case 'biblioteca':
            case 'biblioteca_catalogo':
                return $this->preview_catalogo( $data );

            case 'cursos':
            case 'cursos_catalogo':
                return $this->preview_cursos( $data );

            case 'encuestas':
            case 'encuestas_activas':
                return $this->preview_encuestas( $data );

            case 'transparencia':
            case 'transparencia_portal':
                return $this->preview_transparencia( $data );

            case 'participacion':
            case 'participacion_procesos':
                return $this->preview_participacion( $data );

            case 'comunidades':
            case 'comunidades_listado':
                return $this->preview_comunidades( $data );

            case 'mapa':
            case 'mapa_actores':
                return $this->preview_mapa( $data );

            case 'sello_conciencia':
                return $this->preview_sello( $data );

            case 'estadisticas':
            case 'stats':
                return $this->preview_stats( $data );

            default:
                return $this->preview_generico( $data, $bloque );
        }
    }

    // =========================================================================
    // Métodos de preview visual para cada tipo de widget
    // =========================================================================

    private function preview_social_feed( $data ) {
        $limite = $data['limite'] ?? $data['mostrar_ultimos'] ?? 3;
        $html = '<div class="vbp-preview-feed">';
        for ( $i = 0; $i < min( $limite, 3 ); $i++ ) {
            $html .= '<div class="vbp-preview-post">';
            $html .= '<div class="vbp-preview-avatar"></div>';
            $html .= '<div class="vbp-preview-post-content">';
            $html .= '<div class="vbp-preview-line w-40"></div>';
            $html .= '<div class="vbp-preview-line w-80"></div>';
            $html .= '<div class="vbp-preview-line w-60"></div>';
            $html .= '</div>';
            $html .= '</div>';
        }
        $html .= '</div>';
        return $html;
    }

    private function preview_eventos( $data ) {
        $html = '<div class="vbp-preview-eventos">';
        for ( $i = 0; $i < 2; $i++ ) {
            $html .= '<div class="vbp-preview-evento">';
            $html .= '<div class="vbp-preview-fecha"><span class="dia">' . ( 15 + $i * 3 ) . '</span><span class="mes">MAR</span></div>';
            $html .= '<div class="vbp-preview-evento-info">';
            $html .= '<div class="vbp-preview-line w-70"></div>';
            $html .= '<div class="vbp-preview-line w-50 light"></div>';
            $html .= '</div>';
            $html .= '</div>';
        }
        $html .= '</div>';
        return $html;
    }

    private function preview_listado_cards( $data, $tipo ) {
        $html = '<div class="vbp-preview-cards">';
        for ( $i = 0; $i < 3; $i++ ) {
            $html .= '<div class="vbp-preview-card">';
            $html .= '<div class="vbp-preview-card-avatar"></div>';
            $html .= '<div class="vbp-preview-line w-60"></div>';
            $html .= '<div class="vbp-preview-line w-40 light"></div>';
            $html .= '</div>';
        }
        $html .= '</div>';
        return $html;
    }

    private function preview_productos( $data ) {
        $html = '<div class="vbp-preview-productos">';
        for ( $i = 0; $i < 3; $i++ ) {
            $html .= '<div class="vbp-preview-producto">';
            $html .= '<div class="vbp-preview-producto-img"></div>';
            $html .= '<div class="vbp-preview-line w-70"></div>';
            $html .= '<div class="vbp-preview-precio"></div>';
            $html .= '</div>';
        }
        $html .= '</div>';
        return $html;
    }

    private function preview_ciclos( $data ) {
        $html = '<div class="vbp-preview-ciclos">';
        $html .= '<div class="vbp-preview-ciclo activo">';
        $html .= '<div class="vbp-preview-ciclo-estado">● Abierto</div>';
        $html .= '<div class="vbp-preview-line w-60"></div>';
        $html .= '<div class="vbp-preview-line w-40 light"></div>';
        $html .= '</div>';
        $html .= '<div class="vbp-preview-ciclo">';
        $html .= '<div class="vbp-preview-ciclo-estado pending">○ Próximo</div>';
        $html .= '<div class="vbp-preview-line w-50"></div>';
        $html .= '</div>';
        $html .= '</div>';
        return $html;
    }

    private function preview_listado_filas( $data, $tipo ) {
        $html = '<div class="vbp-preview-filas">';
        for ( $i = 0; $i < 3; $i++ ) {
            $html .= '<div class="vbp-preview-fila">';
            $html .= '<div class="vbp-preview-fila-icon">💬</div>';
            $html .= '<div class="vbp-preview-fila-content">';
            $html .= '<div class="vbp-preview-line w-' . ( 70 - $i * 10 ) . '"></div>';
            $html .= '<div class="vbp-preview-line w-30 light"></div>';
            $html .= '</div>';
            $html .= '<div class="vbp-preview-badge">' . ( 5 - $i ) . '</div>';
            $html .= '</div>';
        }
        $html .= '</div>';
        return $html;
    }

    private function preview_catalogo( $data ) {
        $html = '<div class="vbp-preview-catalogo">';
        for ( $i = 0; $i < 3; $i++ ) {
            $html .= '<div class="vbp-preview-libro">';
            $html .= '<div class="vbp-preview-libro-cover"></div>';
            $html .= '<div class="vbp-preview-line w-80"></div>';
            $html .= '<div class="vbp-preview-line w-50 light"></div>';
            $html .= '</div>';
        }
        $html .= '</div>';
        return $html;
    }

    private function preview_cursos( $data ) {
        $html = '<div class="vbp-preview-cursos">';
        for ( $i = 0; $i < 2; $i++ ) {
            $html .= '<div class="vbp-preview-curso">';
            $html .= '<div class="vbp-preview-curso-img"></div>';
            $html .= '<div class="vbp-preview-curso-info">';
            $html .= '<div class="vbp-preview-line w-70"></div>';
            $html .= '<div class="vbp-preview-line w-40 light"></div>';
            $html .= '<div class="vbp-preview-progress"><div class="vbp-preview-progress-bar" style="width:' . ( 40 + $i * 30 ) . '%"></div></div>';
            $html .= '</div>';
            $html .= '</div>';
        }
        $html .= '</div>';
        return $html;
    }

    private function preview_encuestas( $data ) {
        $html = '<div class="vbp-preview-encuestas">';
        $html .= '<div class="vbp-preview-encuesta">';
        $html .= '<div class="vbp-preview-line w-80"></div>';
        $html .= '<div class="vbp-preview-opciones">';
        $html .= '<div class="vbp-preview-opcion"><div class="vbp-preview-radio"></div><div class="vbp-preview-line w-50"></div></div>';
        $html .= '<div class="vbp-preview-opcion"><div class="vbp-preview-radio"></div><div class="vbp-preview-line w-60"></div></div>';
        $html .= '<div class="vbp-preview-opcion"><div class="vbp-preview-radio checked"></div><div class="vbp-preview-line w-40"></div></div>';
        $html .= '</div>';
        $html .= '</div>';
        $html .= '</div>';
        return $html;
    }

    private function preview_transparencia( $data ) {
        $html = '<div class="vbp-preview-transparencia">';
        $html .= '<div class="vbp-preview-stat-row">';
        $html .= '<div class="vbp-preview-stat"><div class="vbp-preview-stat-value">€12.5K</div><div class="vbp-preview-stat-label">Ingresos</div></div>';
        $html .= '<div class="vbp-preview-stat"><div class="vbp-preview-stat-value">€8.2K</div><div class="vbp-preview-stat-label">Gastos</div></div>';
        $html .= '</div>';
        $html .= '<div class="vbp-preview-chart"></div>';
        $html .= '</div>';
        return $html;
    }

    private function preview_participacion( $data ) {
        $html = '<div class="vbp-preview-participacion">';
        $html .= '<div class="vbp-preview-proceso">';
        $html .= '<div class="vbp-preview-proceso-estado activo">En curso</div>';
        $html .= '<div class="vbp-preview-line w-70"></div>';
        $html .= '<div class="vbp-preview-votos">';
        $html .= '<span class="vbp-preview-voto si">👍 24</span>';
        $html .= '<span class="vbp-preview-voto no">👎 8</span>';
        $html .= '</div>';
        $html .= '</div>';
        $html .= '</div>';
        return $html;
    }

    private function preview_comunidades( $data ) {
        $html = '<div class="vbp-preview-comunidades">';
        for ( $i = 0; $i < 2; $i++ ) {
            $html .= '<div class="vbp-preview-comunidad">';
            $html .= '<div class="vbp-preview-comunidad-avatar">🏘</div>';
            $html .= '<div class="vbp-preview-comunidad-info">';
            $html .= '<div class="vbp-preview-line w-60"></div>';
            $html .= '<div class="vbp-preview-line w-40 light"></div>';
            $html .= '</div>';
            $html .= '<div class="vbp-preview-miembros">👥 ' . ( 45 - $i * 15 ) . '</div>';
            $html .= '</div>';
        }
        $html .= '</div>';
        return $html;
    }

    private function preview_mapa( $data ) {
        $html = '<div class="vbp-preview-mapa">';
        $html .= '<div class="vbp-preview-mapa-bg">';
        $html .= '<div class="vbp-preview-marker" style="top:30%;left:40%">📍</div>';
        $html .= '<div class="vbp-preview-marker" style="top:50%;left:60%">📍</div>';
        $html .= '<div class="vbp-preview-marker" style="top:70%;left:35%">📍</div>';
        $html .= '</div>';
        $html .= '</div>';
        return $html;
    }

    private function preview_sello( $data ) {
        $html = '<div class="vbp-preview-sello">';
        $html .= '<div class="vbp-preview-sello-badge">🌿</div>';
        $html .= '<div class="vbp-preview-sello-score">';
        $html .= '<div class="vbp-preview-score-circle"><span>75</span>/100</div>';
        $html .= '</div>';
        $html .= '<div class="vbp-preview-sello-criterios">';
        $html .= '<div class="vbp-preview-criterio"><span class="check">✓</span> Ecológico</div>';
        $html .= '<div class="vbp-preview-criterio"><span class="check">✓</span> Local</div>';
        $html .= '<div class="vbp-preview-criterio"><span class="check partial">◐</span> Justo</div>';
        $html .= '</div>';
        $html .= '</div>';
        return $html;
    }

    private function preview_stats( $data ) {
        $items = $data['items'] ?? array();
        $html = '<div class="vbp-preview-stats">';
        if ( ! empty( $items ) ) {
            foreach ( array_slice( $items, 0, 3 ) as $item ) {
                $html .= '<div class="vbp-preview-stat-item">';
                $html .= '<div class="vbp-preview-stat-number">' . esc_html( $item['numero'] ?? '0' ) . '</div>';
                $html .= '<div class="vbp-preview-stat-label">' . esc_html( $item['etiqueta'] ?? '' ) . '</div>';
                $html .= '</div>';
            }
        } else {
            for ( $i = 0; $i < 3; $i++ ) {
                $html .= '<div class="vbp-preview-stat-item">';
                $html .= '<div class="vbp-preview-stat-number">###</div>';
                $html .= '<div class="vbp-preview-line w-60"></div>';
                $html .= '</div>';
            }
        }
        $html .= '</div>';
        return $html;
    }

    private function preview_generico( $data, $bloque ) {
        $titulo = $data['titulo'] ?? $data['title'] ?? '';
        $subtitulo = $data['subtitulo'] ?? $data['subtitle'] ?? '';
        $descripcion = $bloque['description'] ?? '';

        $html = '<div class="vbp-preview-generico">';

        if ( $titulo ) {
            $html .= '<div class="vbp-preview-titulo">' . esc_html( $titulo ) . '</div>';
        }
        if ( $subtitulo ) {
            $html .= '<div class="vbp-preview-subtitulo">' . esc_html( $subtitulo ) . '</div>';
        }
        if ( ! $titulo && ! $subtitulo && $descripcion ) {
            $html .= '<div class="vbp-preview-descripcion">' . esc_html( $descripcion ) . '</div>';
        }

        // Placeholder visual
        $html .= '<div class="vbp-preview-placeholder">';
        $html .= '<div class="vbp-preview-line w-80"></div>';
        $html .= '<div class="vbp-preview-line w-60"></div>';
        $html .= '<div class="vbp-preview-line w-70"></div>';
        $html .= '</div>';

        $html .= '</div>';
        return $html;
    }

    /**
     * Renderiza un shortcode
     *
     * @param string $shortcode Nombre del shortcode.
     * @param array  $data      Atributos.
     * @param array  $estilos   Estilos.
     * @return string
     */
    private function renderizar_shortcode( $shortcode, $data, $estilos ) {
        $atributos = '';
        foreach ( $data as $key => $value ) {
            if ( is_string( $value ) || is_numeric( $value ) ) {
                $atributos .= ' ' . esc_attr( $key ) . '="' . esc_attr( $value ) . '"';
            }
        }

        $estilos_css = $this->generar_estilos_elemento( $estilos );

        $html = '<div class="vbp-element vbp-shortcode" style="' . esc_attr( $estilos_css ) . '">';
        $html .= do_shortcode( '[' . $shortcode . $atributos . ']' );
        $html .= '</div>';

        return $html;
    }

    /**
     * Genera estilos CSS inline para un elemento
     *
     * @param array $estilos Configuración de estilos.
     * @return string
     */
    /**
     * Genera todos los atributos de estilo para un elemento (estilos + animaciones)
     *
     * Devuelve un array con:
     * - 'style' => string de estilos CSS inline
     * - 'class' => string de clases CSS (incluye animaciones)
     * - 'attrs' => string de atributos data-* para animaciones
     *
     * @param array  $estilos   Configuración de estilos del elemento.
     * @param string $clase_base Clase CSS base del elemento.
     * @return array
     */
    public function generar_atributos_completos( $estilos, $clase_base = '' ) {
        $estilos_css    = $this->generar_estilos_elemento( $estilos );
        $estilos_anim   = $this->generar_estilos_animacion( $estilos );
        $clases_anim    = $this->generar_clases_animacion( $estilos );
        $atributos_anim = $this->generar_atributos_animacion( $estilos );

        // Combinar estilos
        $estilo_final = trim( $estilos_css . ( $estilos_anim ? '; ' . $estilos_anim : '' ) );

        // Combinar clases
        $clases_final = trim( $clase_base . ' ' . $clases_anim );

        return array(
            'style' => $estilo_final,
            'class' => $clases_final,
            'attrs' => $atributos_anim,
        );
    }

    /**
     * Genera la cadena de atributos HTML para un elemento
     *
     * @param array  $estilos    Configuración de estilos del elemento.
     * @param string $clase_base Clase CSS base del elemento.
     * @return string Atributos HTML listos para usar (class="..." style="..." data-*...)
     */
    public function generar_atributos_html( $estilos, $clase_base = '' ) {
        $attrs = $this->generar_atributos_completos( $estilos, $clase_base );

        $html = '';
        if ( ! empty( $attrs['class'] ) ) {
            $html .= ' class="' . esc_attr( $attrs['class'] ) . '"';
        }
        if ( ! empty( $attrs['style'] ) ) {
            $html .= ' style="' . esc_attr( $attrs['style'] ) . '"';
        }
        if ( ! empty( $attrs['attrs'] ) ) {
            $html .= ' ' . $attrs['attrs'];
        }

        return trim( $html );
    }

    public function generar_estilos_elemento( $estilos ) {
        $css = array();

        // Spacing
        if ( ! empty( $estilos['spacing'] ) ) {
            $spacing = $estilos['spacing'];

            if ( ! empty( $spacing['margin'] ) ) {
                $m = $spacing['margin'];
                if ( ! empty( $m['top'] ) ) {
                    $css[] = 'margin-top: ' . esc_attr( $m['top'] );
                }
                if ( ! empty( $m['right'] ) ) {
                    $css[] = 'margin-right: ' . esc_attr( $m['right'] );
                }
                if ( ! empty( $m['bottom'] ) ) {
                    $css[] = 'margin-bottom: ' . esc_attr( $m['bottom'] );
                }
                if ( ! empty( $m['left'] ) ) {
                    $css[] = 'margin-left: ' . esc_attr( $m['left'] );
                }
            }

            if ( ! empty( $spacing['padding'] ) ) {
                $p = $spacing['padding'];
                if ( ! empty( $p['top'] ) ) {
                    $css[] = 'padding-top: ' . esc_attr( $p['top'] );
                }
                if ( ! empty( $p['right'] ) ) {
                    $css[] = 'padding-right: ' . esc_attr( $p['right'] );
                }
                if ( ! empty( $p['bottom'] ) ) {
                    $css[] = 'padding-bottom: ' . esc_attr( $p['bottom'] );
                }
                if ( ! empty( $p['left'] ) ) {
                    $css[] = 'padding-left: ' . esc_attr( $p['left'] );
                }
            }
        }

        // Colors - Usar variables CSS cuando el color coincide con el tema
        if ( ! empty( $estilos['colors'] ) ) {
            if ( ! empty( $estilos['colors']['background'] ) ) {
                $bg_color = $this->map_color_to_variable( $estilos['colors']['background'] );
                $css[] = 'background-color: ' . esc_attr( $bg_color );
            }
            if ( ! empty( $estilos['colors']['text'] ) ) {
                $text_color = $this->map_color_to_variable( $estilos['colors']['text'] );
                $css[] = 'color: ' . esc_attr( $text_color );
            }
        }

        // Typography
        if ( ! empty( $estilos['typography'] ) ) {
            $typo = $estilos['typography'];
            if ( ! empty( $typo['fontSize'] ) ) {
                $css[] = 'font-size: ' . esc_attr( $typo['fontSize'] );
            }
            if ( ! empty( $typo['fontWeight'] ) ) {
                $css[] = 'font-weight: ' . esc_attr( $typo['fontWeight'] );
            }
            if ( ! empty( $typo['lineHeight'] ) ) {
                $css[] = 'line-height: ' . esc_attr( $typo['lineHeight'] );
            }
            if ( ! empty( $typo['textAlign'] ) ) {
                $css[] = 'text-align: ' . esc_attr( $typo['textAlign'] );
            }
        }

        // Borders - Usar variables CSS para colores de borde
        if ( ! empty( $estilos['borders'] ) ) {
            $borders = $estilos['borders'];
            if ( ! empty( $borders['radius'] ) ) {
                $css[] = 'border-radius: ' . esc_attr( $borders['radius'] );
            }
            if ( ! empty( $borders['width'] ) && ! empty( $borders['color'] ) ) {
                $estilo_borde = ! empty( $borders['style'] ) ? $borders['style'] : 'solid';
                $border_color = $this->map_color_to_variable( $borders['color'] );
                $css[] = 'border: ' . esc_attr( $borders['width'] ) . ' ' . esc_attr( $estilo_borde ) . ' ' . esc_attr( $border_color );
            }
        }

        // Shadows
        if ( ! empty( $estilos['shadows']['boxShadow'] ) ) {
            $css[] = 'box-shadow: ' . esc_attr( $estilos['shadows']['boxShadow'] );
        }

        // Dimensions
        if ( ! empty( $estilos['dimensions'] ) ) {
            $dims = $estilos['dimensions'];
            if ( ! empty( $dims['width'] ) ) {
                $css[] = 'width: ' . esc_attr( $dims['width'] );
            }
            if ( ! empty( $dims['height'] ) ) {
                $css[] = 'height: ' . esc_attr( $dims['height'] );
            }
            if ( ! empty( $dims['minHeight'] ) ) {
                $css[] = 'min-height: ' . esc_attr( $dims['minHeight'] );
            }
            if ( ! empty( $dims['maxWidth'] ) ) {
                $css[] = 'max-width: ' . esc_attr( $dims['maxWidth'] );
            }
        }

        // Layout (flexbox, grid)
        if ( ! empty( $estilos['layout'] ) ) {
            $layout = $estilos['layout'];
            if ( ! empty( $layout['display'] ) ) {
                $css[] = 'display: ' . esc_attr( $layout['display'] );
            }
            if ( ! empty( $layout['gap'] ) ) {
                $css[] = 'gap: ' . esc_attr( $layout['gap'] );
            }
            if ( ! empty( $layout['flexDirection'] ) ) {
                $css[] = 'flex-direction: ' . esc_attr( $layout['flexDirection'] );
            }
            if ( ! empty( $layout['alignItems'] ) ) {
                $css[] = 'align-items: ' . esc_attr( $layout['alignItems'] );
            }
            if ( ! empty( $layout['justifyContent'] ) ) {
                $css[] = 'justify-content: ' . esc_attr( $layout['justifyContent'] );
            }
            if ( ! empty( $layout['flexWrap'] ) ) {
                $css[] = 'flex-wrap: ' . esc_attr( $layout['flexWrap'] );
            }
            if ( ! empty( $layout['gridTemplateColumns'] ) ) {
                $css[] = 'grid-template-columns: ' . esc_attr( $layout['gridTemplateColumns'] );
            }
        }

        // Position
        if ( ! empty( $estilos['position'] ) ) {
            $pos = $estilos['position'];
            if ( ! empty( $pos['position'] ) ) {
                $css[] = 'position: ' . esc_attr( $pos['position'] );
            }
            if ( isset( $pos['top'] ) && '' !== $pos['top'] ) {
                $css[] = 'top: ' . esc_attr( $pos['top'] );
            }
            if ( isset( $pos['right'] ) && '' !== $pos['right'] ) {
                $css[] = 'right: ' . esc_attr( $pos['right'] );
            }
            if ( isset( $pos['bottom'] ) && '' !== $pos['bottom'] ) {
                $css[] = 'bottom: ' . esc_attr( $pos['bottom'] );
            }
            if ( isset( $pos['left'] ) && '' !== $pos['left'] ) {
                $css[] = 'left: ' . esc_attr( $pos['left'] );
            }
            if ( ! empty( $pos['zIndex'] ) ) {
                $css[] = 'z-index: ' . intval( $pos['zIndex'] );
            }
        }

        // Overflow
        if ( ! empty( $estilos['overflow'] ) ) {
            $css[] = 'overflow: ' . esc_attr( $estilos['overflow'] );
        }

        // Opacity
        if ( isset( $estilos['opacity'] ) && '' !== $estilos['opacity'] ) {
            $css[] = 'opacity: ' . floatval( $estilos['opacity'] );
        }

        // Transform (compatibilidad: string directo)
        if ( ! empty( $estilos['transform'] ) && is_string( $estilos['transform'] ) ) {
            $css[] = 'transform: ' . esc_attr( $estilos['transform'] );
        }

        // Transition
        if ( ! empty( $estilos['transition'] ) ) {
            if ( is_string( $estilos['transition'] ) ) {
                $css[] = 'transition: ' . esc_attr( $estilos['transition'] );
            } elseif ( is_array( $estilos['transition'] ) ) {
                // Construir transition desde objeto {property, duration, timing, delay}
                $transition_config = $estilos['transition'];
                $transition_prop   = isset( $transition_config['property'] ) ? $transition_config['property'] : 'all';
                $transition_dur    = isset( $transition_config['duration'] ) ? $transition_config['duration'] : '0.3s';
                $transition_timing = isset( $transition_config['timing'] ) ? $transition_config['timing'] : 'ease';
                $transition_delay  = isset( $transition_config['delay'] ) ? $transition_config['delay'] : '';
                $transition_value  = esc_attr( $transition_prop ) . ' ' . esc_attr( $transition_dur ) . ' ' . esc_attr( $transition_timing );
                if ( $transition_delay && '0s' !== $transition_delay ) {
                    $transition_value .= ' ' . esc_attr( $transition_delay );
                }
                $css[] = 'transition: ' . $transition_value;
            }
        }

        // Background gradient/image
        if ( ! empty( $estilos['background'] ) ) {
            $bg = $estilos['background'];

            // Tipo de fondo: gradient
            if ( isset( $bg['type'] ) && 'gradient' === $bg['type'] ) {
                $direction = ! empty( $bg['gradientDirection'] ) ? $bg['gradientDirection'] : 'to bottom';
                $start     = ! empty( $bg['gradientStart'] ) ? $bg['gradientStart'] : '#3b82f6';
                $end       = ! empty( $bg['gradientEnd'] ) ? $bg['gradientEnd'] : '#8b5cf6';
                $css[]     = 'background: linear-gradient(' . esc_attr( $direction ) . ', ' . esc_attr( $start ) . ', ' . esc_attr( $end ) . ')';
            }
            // Gradiente directo (compatibilidad hacia atrás)
            elseif ( ! empty( $bg['gradient'] ) ) {
                $css[] = 'background: ' . esc_attr( $bg['gradient'] );
            }

            // Tipo de fondo: image
            if ( ( isset( $bg['type'] ) && 'image' === $bg['type'] ) || ! empty( $bg['image'] ) ) {
                if ( ! empty( $bg['image'] ) ) {
                    $css[] = 'background-image: url(' . esc_url( $bg['image'] ) . ')';
                    if ( ! empty( $bg['size'] ) ) {
                        $css[] = 'background-size: ' . esc_attr( $bg['size'] );
                    }
                    if ( ! empty( $bg['position'] ) ) {
                        $css[] = 'background-position: ' . esc_attr( $bg['position'] );
                    }
                    if ( ! empty( $bg['repeat'] ) ) {
                        $css[] = 'background-repeat: ' . esc_attr( $bg['repeat'] );
                    }
                    if ( ! empty( $bg['fixed'] ) ) {
                        $css[] = 'background-attachment: fixed';
                    }
                }
            }
        }

        // Transform (propiedades individuales como array)
        if ( ! empty( $estilos['transform'] ) && is_array( $estilos['transform'] ) ) {
            $tr         = $estilos['transform'];
            $transforms = array();

            if ( ! empty( $tr['rotate'] ) && '0' !== $tr['rotate'] && '' !== $tr['rotate'] ) {
                $transforms[] = 'rotate(' . esc_attr( $tr['rotate'] ) . 'deg)';
            }
            if ( ! empty( $tr['scale'] ) && '1' !== $tr['scale'] && '' !== $tr['scale'] ) {
                $transforms[] = 'scale(' . esc_attr( $tr['scale'] ) . ')';
            }
            if ( ! empty( $tr['translateX'] ) ) {
                $transforms[] = 'translateX(' . esc_attr( $tr['translateX'] ) . ')';
            }
            if ( ! empty( $tr['translateY'] ) ) {
                $transforms[] = 'translateY(' . esc_attr( $tr['translateY'] ) . ')';
            }
            if ( ! empty( $tr['skewX'] ) ) {
                $transforms[] = 'skewX(' . esc_attr( $tr['skewX'] ) . ')';
            }
            if ( ! empty( $tr['skewY'] ) ) {
                $transforms[] = 'skewY(' . esc_attr( $tr['skewY'] ) . ')';
            }

            if ( ! empty( $transforms ) ) {
                $css[] = 'transform: ' . implode( ' ', $transforms );
            }
        }

        return implode( '; ', $css );
    }

    /**
     * Genera atributos HTML de animación
     *
     * @param array $estilos Configuración de estilos del elemento.
     * @return string Atributos HTML para animaciones.
     */
    public function generar_atributos_animacion( $estilos ) {
        $atributos = array();
        $advanced  = isset( $estilos['advanced'] ) ? $estilos['advanced'] : array();

        // Animación de entrada
        if ( ! empty( $advanced['entranceAnimation'] ) ) {
            $atributos[] = 'data-vbp-entrance="' . esc_attr( $advanced['entranceAnimation'] ) . '"';

            $trigger = isset( $advanced['animTrigger'] ) ? $advanced['animTrigger'] : 'scroll';
            $atributos[] = 'data-vbp-trigger="' . esc_attr( $trigger ) . '"';

            if ( ! empty( $advanced['animDuration'] ) ) {
                $atributos[] = 'data-vbp-duration="' . esc_attr( $advanced['animDuration'] ) . '"';
            }

            if ( ! empty( $advanced['animDelay'] ) ) {
                $atributos[] = 'data-vbp-delay="' . esc_attr( $advanced['animDelay'] ) . '"';
            }

            if ( ! empty( $advanced['animEasing'] ) ) {
                $atributos[] = 'data-vbp-easing="' . esc_attr( $advanced['animEasing'] ) . '"';
            }
        }

        // Parallax
        if ( ! empty( $advanced['parallaxEnabled'] ) ) {
            $speed = isset( $advanced['parallaxSpeed'] ) ? $advanced['parallaxSpeed'] : '0.3';
            $atributos[] = 'data-vbp-parallax="' . esc_attr( $speed ) . '"';
        }

        return implode( ' ', $atributos );
    }

    /**
     * Genera clases CSS de animación
     *
     * @param array $estilos Configuración de estilos del elemento.
     * @return string Clases CSS para animaciones.
     */
    public function generar_clases_animacion( $estilos ) {
        $clases   = array();
        $advanced = isset( $estilos['advanced'] ) ? $estilos['advanced'] : array();

        // Animación hover
        if ( ! empty( $advanced['hoverAnimation'] ) ) {
            $clases[] = 'vbp-hover-' . esc_attr( $advanced['hoverAnimation'] );
        }

        // Animación en bucle
        if ( ! empty( $advanced['loopAnimation'] ) ) {
            $clases[] = 'vbp-loop-' . esc_attr( $advanced['loopAnimation'] );
        }

        // Parallax class
        if ( ! empty( $advanced['parallaxEnabled'] ) ) {
            $clases[] = 'vbp-parallax';
        }

        return implode( ' ', $clases );
    }

    /**
     * Genera estilos CSS de animación inline
     *
     * @param array $estilos Configuración de estilos del elemento.
     * @return string Estilos CSS inline para animaciones.
     */
    public function generar_estilos_animacion( $estilos ) {
        $css      = array();
        $advanced = isset( $estilos['advanced'] ) ? $estilos['advanced'] : array();

        // Duración de animación en bucle
        if ( ! empty( $advanced['loopAnimation'] ) && ! empty( $advanced['loopDuration'] ) ) {
            $css[] = '--vbp-anim-duration: ' . esc_attr( $advanced['loopDuration'] );
        }

        return implode( '; ', $css );
    }

    // =========================================================================
    // Renderizadores específicos
    // =========================================================================

    /**
     * Renderiza Hero
     */
    private function render_hero( $elemento ) {
        $data     = $elemento['data'] ?? array();
        $estilos  = $elemento['styles'] ?? array();
        $variante = $elemento['variant'] ?? 'centered';

        // Soportar ambos formatos: español e inglés
        $titulo        = $data['titulo'] ?? $data['title'] ?? '';
        $subtitulo     = $data['subtitulo'] ?? $data['subtitle'] ?? '';
        $descripcion   = $data['descripcion'] ?? $data['description'] ?? '';
        $boton_texto   = $data['boton_texto'] ?? $data['buttonText'] ?? $data['cta_text'] ?? '';
        $boton_url     = $data['boton_url'] ?? $data['buttonUrl'] ?? $data['cta_url'] ?? '#';
        $boton_2_texto = $data['boton_2_texto'] ?? $data['button2Text'] ?? '';
        $boton_2_url   = $data['boton_2_url'] ?? $data['button2Url'] ?? '#';
        $imagen        = $data['imagen_fondo'] ?? $data['backgroundImage'] ?? $data['background_image'] ?? $data['image'] ?? '';
        $overlay_color = $data['overlay_color'] ?? $data['overlayColor'] ?? $data['background_overlay_color'] ?? '';
        $altura        = $data['altura'] ?? $data['height'] ?? '';
        $color_fondo   = $data['color_fondo'] ?? $data['backgroundColor'] ?? '';

        // Colores específicos de elementos
        $titulo_color      = $data['titulo_color'] ?? $data['titleColor'] ?? '';
        $subtitulo_color   = $data['subtitulo_color'] ?? $data['subtitleColor'] ?? '';
        $descripcion_color = $data['descripcion_color'] ?? $data['descriptionColor'] ?? '';
        $boton_color_fondo = $data['boton_color_fondo'] ?? $data['buttonBg'] ?? $data['boton_bg'] ?? '';
        $boton_color_texto = $data['boton_color_texto'] ?? $data['buttonColor'] ?? $data['boton_color'] ?? '';
        $boton_2_color_fondo = $data['boton_2_color_fondo'] ?? '';
        $boton_2_color_texto = $data['boton_2_color_texto'] ?? '';
        $boton_2_color_borde = $data['boton_2_color_borde'] ?? '';

        $clase_variante = 'vbp-hero--' . esc_attr( $variante );

        // Estilos del contenedor principal
        $estilos_hero = array();
        if ( $imagen ) {
            $estilos_hero[] = 'background-image: url(' . esc_url( $imagen ) . ')';
            $estilos_hero[] = 'background-size: cover';
            $estilos_hero[] = 'background-position: center';
        }
        if ( $color_fondo && ! $imagen ) {
            $estilos_hero[] = 'background-color: ' . esc_attr( $color_fondo );
        }
        if ( $altura ) {
            $estilos_hero[] = 'min-height: ' . esc_attr( $altura );
        }

        $estilos_css = $this->generar_estilos_elemento( $estilos );
        $estilos_hero_str = implode( '; ', $estilos_hero );

        $estilos_combinados = array_filter( array( $estilos_css, $estilos_hero_str ) );
        $estilo_final = implode( '; ', $estilos_combinados );

        $html = '<section class="vbp-hero ' . $clase_variante . '" style="' . esc_attr( $estilo_final ) . '">';

        // Overlay
        if ( $overlay_color ) {
            $html .= '<div class="vbp-hero__overlay" style="background: ' . esc_attr( $overlay_color ) . ';"></div>';
        }

        $html .= '<div class="vbp-hero__content">';

        // Título con color
        if ( $titulo ) {
            $estilo_titulo = $titulo_color ? ' style="color: ' . esc_attr( $titulo_color ) . ';"' : '';
            $html .= '<h1 class="vbp-hero__title"' . $estilo_titulo . $this->get_editor_contenteditable_attr( 'titulo' ) . '>' . wp_kses_post( $titulo ) . '</h1>';
        }

        // Subtítulo con color
        if ( $subtitulo ) {
            $estilo_subtitulo = $subtitulo_color ? ' style="color: ' . esc_attr( $subtitulo_color ) . ';"' : '';
            $html .= '<p class="vbp-hero__subtitle"' . $estilo_subtitulo . $this->get_editor_contenteditable_attr( 'subtitulo' ) . '>' . wp_kses_post( $subtitulo ) . '</p>';
        }

        // Descripción con color
        if ( $descripcion ) {
            $estilo_descripcion = $descripcion_color ? ' style="color: ' . esc_attr( $descripcion_color ) . ';"' : '';
            $html .= '<p class="vbp-hero__description"' . $estilo_descripcion . $this->get_editor_contenteditable_attr( 'descripcion' ) . '>' . wp_kses_post( $descripcion ) . '</p>';
        }

        // Contenedor de botones
        if ( $boton_texto || $boton_2_texto ) {
            $html .= '<div class="vbp-hero__buttons">';

            // Botón principal
            if ( $boton_texto ) {
                $estilos_boton = array();
                if ( $boton_color_texto ) {
                    $estilos_boton[] = 'color: ' . esc_attr( $boton_color_texto );
                }
                if ( $boton_color_fondo ) {
                    $estilos_boton[] = 'background-color: ' . esc_attr( $boton_color_fondo );
                }
                $estilo_boton = ! empty( $estilos_boton ) ? ' style="' . esc_attr( implode( '; ', $estilos_boton ) ) . '"' : '';
                $html .= '<a href="' . esc_url( $boton_url ) . '" class="vbp-hero__button vbp-hero__button--primary"' . $estilo_boton . $this->get_editor_link_attr( 'boton_texto' ) . '>' . esc_html( $boton_texto ) . '</a>';
            }

            // Botón secundario
            if ( $boton_2_texto ) {
                $estilos_boton_2 = array();
                if ( $boton_2_color_texto ) {
                    $estilos_boton_2[] = 'color: ' . esc_attr( $boton_2_color_texto );
                }
                if ( $boton_2_color_fondo && $boton_2_color_fondo !== 'transparent' ) {
                    $estilos_boton_2[] = 'background-color: ' . esc_attr( $boton_2_color_fondo );
                } else {
                    $estilos_boton_2[] = 'background-color: transparent';
                }
                if ( $boton_2_color_borde ) {
                    $estilos_boton_2[] = 'border: 2px solid ' . esc_attr( $boton_2_color_borde );
                }
                $estilo_boton_2 = ! empty( $estilos_boton_2 ) ? ' style="' . esc_attr( implode( '; ', $estilos_boton_2 ) ) . '"' : '';
                $html .= '<a href="' . esc_url( $boton_2_url ) . '" class="vbp-hero__button vbp-hero__button--secondary"' . $estilo_boton_2 . $this->get_editor_link_attr( 'boton_2_texto' ) . '>' . esc_html( $boton_2_texto ) . '</a>';
            }

            $html .= '</div>';
        }

        $html .= '</div>';
        $html .= '</section>';

        return $html;
    }

    /**
     * Renderiza Features
     */
    private function render_features( $elemento ) {
        $data     = $elemento['data'] ?? array();
        $estilos  = $elemento['styles'] ?? array();
        $variante = $elemento['variant'] ?? 'grid';

        $titulo          = $data['titulo'] ?? '';
        $subtitulo       = $data['subtitulo'] ?? '';
        $titulo_color    = $data['titulo_color'] ?? '';
        $subtitulo_color = $data['subtitulo_color'] ?? '';
        $color_fondo     = $data['color_fondo'] ?? '';
        $columnas        = $data['columnas'] ?? 3;
        $items           = $data['items'] ?? array();

        $clase_variante = 'vbp-features--' . esc_attr( $variante );
        $estilos_css    = $this->generar_estilos_elemento( $estilos );

        // Estilos del contenedor
        $estilos_section = array();
        if ( $color_fondo ) {
            $estilos_section[] = 'background-color: ' . esc_attr( $color_fondo );
        }
        $estilos_section_str = implode( '; ', $estilos_section );
        $estilos_combinados = array_filter( array( $estilos_css, $estilos_section_str ) );
        $estilo_final = implode( '; ', $estilos_combinados );

        $html = '<section class="vbp-features ' . $clase_variante . '" style="' . esc_attr( $estilo_final ) . '">';
        $html .= '<div class="vbp-container">';

        // Título con color
        if ( $titulo ) {
            $estilo_titulo = $titulo_color ? ' style="color: ' . esc_attr( $titulo_color ) . ';"' : '';
            $html .= '<h2 class="vbp-features__title"' . $estilo_titulo . $this->get_editor_contenteditable_attr( 'titulo' ) . '>' . wp_kses_post( $titulo ) . '</h2>';
        }

        // Subtítulo con color
        if ( $subtitulo ) {
            $estilo_subtitulo = $subtitulo_color ? ' style="color: ' . esc_attr( $subtitulo_color ) . ';"' : '';
            $html .= '<p class="vbp-features__subtitle"' . $estilo_subtitulo . $this->get_editor_contenteditable_attr( 'subtitulo' ) . '>' . wp_kses_post( $subtitulo ) . '</p>';
        }

        if ( ! empty( $items ) ) {
            $estilo_grid = '--features-columns: ' . intval( $columnas ) . ';';
            $html .= '<div class="vbp-features__grid" style="' . esc_attr( $estilo_grid ) . '">';

            foreach ( $items as $index => $item ) {
                $icono_color = $item['icono_color'] ?? '';
                $html .= '<div class="vbp-feature-card">';
                if ( ! empty( $item['icono'] ) ) {
                    $estilo_icono = $icono_color ? ' style="color: ' . esc_attr( $icono_color ) . ';"' : '';
                    $html .= '<div class="vbp-feature-card__icon"' . $estilo_icono . '>';
                    $icono_bruto = (string) $item['icono'];
                    // Soporta 3 formatos: FontAwesome (fa-*), Material Icons
                    // (slug ASCII como "home_work") y emoji/unicode. Sin el
                    // tercer caso los emojis acaban como clases CSS rotas.
                    if ( 0 === strpos( $icono_bruto, 'fa-' ) ) {
                        $html .= '<i class="fas ' . esc_attr( $icono_bruto ) . '" aria-hidden="true"></i>';
                    } elseif ( preg_match( '/^[a-z0-9_-]+$/', $icono_bruto ) ) {
                        $html .= '<span class="material-icons material-icons-outlined" aria-hidden="true">' . esc_html( $icono_bruto ) . '</span>';
                    } else {
                        $html .= '<span class="vbp-feature-card__icon-emoji" aria-hidden="true" style="font-size:40px;line-height:1;">' . esc_html( $icono_bruto ) . '</span>';
                    }
                    $html .= '</div>';
                }
                $html .= '<h3 class="vbp-feature-card__title"' . $this->get_editor_contenteditable_path_attr( 'items.' . $index . '.titulo' ) . '>' . esc_html( $item['titulo'] ?? $item['title'] ?? '' ) . '</h3>';
                $html .= '<p class="vbp-feature-card__description"' . $this->get_editor_contenteditable_path_attr( 'items.' . $index . '.descripcion' ) . '>' . esc_html( $item['descripcion'] ?? $item['description'] ?? '' ) . '</p>';
                $html .= '</div>';
            }

            $html .= '</div>';
        }

        $html .= '</div>';
        $html .= '</section>';

        return $html;
    }

    /**
     * Renderiza Texto
     */
    private function render_text( $elemento ) {
        $data             = $elemento['data'] ?? array();
        $estilos          = $elemento['styles'] ?? array();
        $estilos_css      = $this->generar_estilos_elemento( $estilos );
        $estilos_anim     = $this->generar_estilos_animacion( $estilos );
        $clases_anim      = $this->generar_clases_animacion( $estilos );
        $atributos_anim   = $this->generar_atributos_animacion( $estilos );

        $texto      = $data['text'] ?? '';
        $estilo_all = trim( $estilos_css . ( $estilos_anim ? '; ' . $estilos_anim : '' ) );
        $clases     = trim( 'vbp-text ' . $clases_anim );

        return '<div class="' . esc_attr( $clases ) . '" style="' . esc_attr( $estilo_all ) . '" ' . $atributos_anim . $this->get_editor_contenteditable_attr( 'text' ) . '>' . wp_kses_post( $texto ) . '</div>';
    }

    /**
     * Renderiza Heading
     */
    private function render_heading( $elemento ) {
        $data             = $elemento['data'] ?? array();
        $estilos          = $elemento['styles'] ?? array();
        $estilos_css      = $this->generar_estilos_elemento( $estilos );
        $estilos_anim     = $this->generar_estilos_animacion( $estilos );
        $clases_anim      = $this->generar_clases_animacion( $estilos );
        $atributos_anim   = $this->generar_atributos_animacion( $estilos );

        $texto      = $data['text'] ?? '';
        $nivel      = $data['level'] ?? 'h2';
        $nivel      = in_array( $nivel, array( 'h1', 'h2', 'h3', 'h4', 'h5', 'h6' ), true ) ? $nivel : 'h2';
        $estilo_all = trim( $estilos_css . ( $estilos_anim ? '; ' . $estilos_anim : '' ) );
        $clases     = trim( 'vbp-heading ' . $clases_anim );

        return '<' . $nivel . ' class="' . esc_attr( $clases ) . '" style="' . esc_attr( $estilo_all ) . '" ' . $atributos_anim . $this->get_editor_contenteditable_attr( 'text' ) . '>' . wp_kses_post( $texto ) . '</' . $nivel . '>';
    }

    /**
     * Renderiza Image
     */
    private function render_image( $elemento ) {
        $data             = $elemento['data'] ?? array();
        $estilos          = $elemento['styles'] ?? array();
        $estilos_css      = $this->generar_estilos_elemento( $estilos );
        $estilos_anim     = $this->generar_estilos_animacion( $estilos );
        $clases_anim      = $this->generar_clases_animacion( $estilos );
        $atributos_anim   = $this->generar_atributos_animacion( $estilos );

        $src        = $data['src'] ?? '';
        $alt        = $data['alt'] ?? '';
        $estilo_all = trim( $estilos_css . ( $estilos_anim ? '; ' . $estilos_anim : '' ) );
        $clases     = trim( 'vbp-image ' . $clases_anim );

        if ( ! $src ) {
            if ( $this->is_editor_context() ) {
                return '<figure class="' . esc_attr( $clases . ' vbp-image--empty' ) . '" style="' . esc_attr( $estilo_all ) . '" ' . $atributos_anim . '><div class="vbp-image__placeholder">Imagen</div></figure>';
            }
            return '';
        }

        $html = '<figure class="' . esc_attr( $clases ) . '" style="' . esc_attr( $estilo_all ) . '" ' . $atributos_anim . '>';
        $html .= '<img src="' . esc_url( $src ) . '" alt="' . esc_attr( $alt ) . '">';

        if ( ! empty( $data['caption'] ) ) {
            $html .= '<figcaption class="vbp-image__caption"' . $this->get_editor_contenteditable_attr( 'caption' ) . '>' . wp_kses_post( $data['caption'] ) . '</figcaption>';
        }

        $html .= '</figure>';

        return $html;
    }

    /**
     * Renderiza Button
     */
    private function render_button( $elemento ) {
        $data             = $elemento['data'] ?? array();
        $estilos          = $elemento['styles'] ?? array();
        $estilos_css      = $this->generar_estilos_elemento( $estilos );
        $estilos_anim     = $this->generar_estilos_animacion( $estilos );
        $clases_anim      = $this->generar_clases_animacion( $estilos );
        $atributos_anim   = $this->generar_atributos_animacion( $estilos );

        $texto      = $data['text'] ?? 'Botón';
        $url        = $data['url'] ?? '#';
        $target     = $data['target'] ?? '_self';
        $estilo_all = trim( $estilos_css . ( $estilos_anim ? '; ' . $estilos_anim : '' ) );
        $clases     = trim( 'vbp-button-wrapper ' . $clases_anim );

        return '<div class="' . esc_attr( $clases ) . '" style="' . esc_attr( $estilo_all ) . '" ' . $atributos_anim . '><a href="' . esc_url( $url ) . '" target="' . esc_attr( $target ) . '" class="vbp-button"' . $this->get_editor_link_attr( 'text' ) . '>' . esc_html( $texto ) . '</a></div>';
    }

    /**
     * Renderiza Divider
     */
    private function render_divider( $elemento ) {
        $estilos          = $elemento['styles'] ?? array();
        $estilos_css      = $this->generar_estilos_elemento( $estilos );
        $clases_anim      = $this->generar_clases_animacion( $estilos );
        $atributos_anim   = $this->generar_atributos_animacion( $estilos );
        $clases           = trim( 'vbp-divider ' . $clases_anim );

        return '<hr class="' . esc_attr( $clases ) . '" style="' . esc_attr( $estilos_css ) . '" ' . $atributos_anim . '>';
    }

    /**
     * Renderiza Spacer
     */
    private function render_spacer( $elemento ) {
        $data   = $elemento['data'] ?? array();
        $altura = $data['height'] ?? '40px';

        return '<div class="vbp-spacer" style="height: ' . esc_attr( $altura ) . ';"></div>';
    }

    /**
     * Renderiza CTA
     */
    private function render_cta( $elemento ) {
        $data        = $elemento['data'] ?? array();
        $estilos     = $elemento['styles'] ?? array();
        $variante    = $elemento['variant'] ?? 'centered';
        $estilos_css = $this->generar_estilos_elemento( $estilos );

        $titulo       = $data['titulo'] ?? '';
        $subtitulo    = $data['subtitulo'] ?? '';
        $boton_texto  = $data['boton_texto'] ?? '';
        $boton_url    = $data['boton_url'] ?? '#';
        $boton_2_texto = $data['boton_2_texto'] ?? '';
        $boton_2_url   = $data['boton_2_url'] ?? '#';

        // Colores
        $color_fondo     = $data['color_fondo'] ?? $data['fondo'] ?? $data['background'] ?? '';
        $titulo_color    = $data['titulo_color'] ?? '';
        $subtitulo_color = $data['subtitulo_color'] ?? '';
        $boton_color_fondo = $data['boton_color_fondo'] ?? $data['boton_bg'] ?? '';
        $boton_color_texto = $data['boton_color_texto'] ?? $data['boton_color'] ?? '';
        $boton_2_color_fondo = $data['boton_2_color_fondo'] ?? '';
        $boton_2_color_texto = $data['boton_2_color_texto'] ?? '';
        $boton_2_color_borde = $data['boton_2_color_borde'] ?? '';

        // Construir estilos del contenedor
        $estilos_cta = array();
        if ( $color_fondo ) {
            if ( strpos( $color_fondo, 'gradient' ) !== false ) {
                $estilos_cta[] = 'background: ' . esc_attr( $color_fondo );
            } else {
                $estilos_cta[] = 'background-color: ' . esc_attr( $color_fondo );
            }
        }
        $estilos_cta_str = implode( '; ', $estilos_cta );

        $estilos_combinados = array_filter( array( $estilos_css, $estilos_cta_str ) );
        $estilo_final = implode( '; ', $estilos_combinados );

        $clase_variante = 'vbp-cta--' . esc_attr( $variante );
        $html = '<section class="vbp-cta ' . $clase_variante . '" style="' . esc_attr( $estilo_final ) . '">';
        $html .= '<div class="vbp-cta__content">';

        if ( $titulo ) {
            $estilo_titulo = $titulo_color ? ' style="color: ' . esc_attr( $titulo_color ) . ';"' : '';
            $html .= '<h2 class="vbp-cta__title"' . $estilo_titulo . $this->get_editor_contenteditable_attr( 'titulo' ) . '>' . wp_kses_post( $titulo ) . '</h2>';
        }

        if ( $subtitulo ) {
            $estilo_subtitulo = $subtitulo_color ? ' style="color: ' . esc_attr( $subtitulo_color ) . ';"' : '';
            $html .= '<p class="vbp-cta__subtitle"' . $estilo_subtitulo . $this->get_editor_contenteditable_attr( 'subtitulo' ) . '>' . wp_kses_post( $subtitulo ) . '</p>';
        }

        // Contenedor de botones
        if ( $boton_texto || $boton_2_texto ) {
            $html .= '<div class="vbp-cta__buttons">';

            // Botón principal
            if ( $boton_texto ) {
                $estilos_boton = array();
                if ( $boton_color_texto ) {
                    $estilos_boton[] = 'color: ' . esc_attr( $boton_color_texto );
                }
                if ( $boton_color_fondo ) {
                    $estilos_boton[] = 'background-color: ' . esc_attr( $boton_color_fondo );
                }
                $estilo_boton = ! empty( $estilos_boton ) ? ' style="' . esc_attr( implode( '; ', $estilos_boton ) ) . '"' : '';
                $html .= '<a href="' . esc_url( $boton_url ) . '" class="vbp-cta__button vbp-cta__button--primary"' . $estilo_boton . $this->get_editor_link_attr( 'boton_texto' ) . '>' . esc_html( $boton_texto ) . '</a>';
            }

            // Botón secundario
            if ( $boton_2_texto ) {
                $estilos_boton_2 = array();
                if ( $boton_2_color_texto ) {
                    $estilos_boton_2[] = 'color: ' . esc_attr( $boton_2_color_texto );
                }
                if ( $boton_2_color_fondo && $boton_2_color_fondo !== 'transparent' ) {
                    $estilos_boton_2[] = 'background-color: ' . esc_attr( $boton_2_color_fondo );
                } else {
                    $estilos_boton_2[] = 'background-color: transparent';
                }
                if ( $boton_2_color_borde ) {
                    $estilos_boton_2[] = 'border: 2px solid ' . esc_attr( $boton_2_color_borde );
                }
                $estilo_boton_2 = ! empty( $estilos_boton_2 ) ? ' style="' . esc_attr( implode( '; ', $estilos_boton_2 ) ) . '"' : '';
                $html .= '<a href="' . esc_url( $boton_2_url ) . '" class="vbp-cta__button vbp-cta__button--secondary"' . $estilo_boton_2 . $this->get_editor_link_attr( 'boton_2_texto' ) . '>' . esc_html( $boton_2_texto ) . '</a>';
            }

            $html .= '</div>';
        }

        $html .= '</div>';
        $html .= '</section>';

        return $html;
    }

    /**
     * Renderiza Testimonials
     */
    private function render_testimonials( $elemento ) {
        $data        = $elemento['data'] ?? array();
        $estilos     = $elemento['styles'] ?? array();
        $variante    = $elemento['variant'] ?? 'cards';
        $estilos_css = $this->generar_estilos_elemento( $estilos );

        $titulo    = $data['titulo'] ?? '';
        $subtitulo = $data['subtitulo'] ?? '';
        // Acepta alias "testimonios" (lo que generan los presets) ademas de "items".
        $items     = ! empty( $data['items'] ) ? $data['items'] : ( $data['testimonios'] ?? array() );

        $html = '<section class="vbp-testimonials vbp-testimonials--' . esc_attr( $variante ) . '" style="' . esc_attr( $estilos_css ) . '">';

        if ( $titulo ) {
            $html .= '<h2 class="vbp-testimonials__title"' . $this->get_editor_contenteditable_attr( 'titulo' ) . '>' . wp_kses_post( $titulo ) . '</h2>';
        }
        if ( $subtitulo ) {
            $html .= '<p class="vbp-testimonials__subtitle"' . $this->get_editor_contenteditable_attr( 'subtitulo' ) . '>' . wp_kses_post( $subtitulo ) . '</p>';
        }

        if ( ! empty( $items ) ) {
            $html .= '<div class="vbp-testimonials__grid">';

            foreach ( $items as $index => $item ) {
                // Alias "autor" o "nombre" (los presets usan "nombre").
                $autor_item = $item['autor'] ?? $item['nombre'] ?? '';
                $html .= '<div class="vbp-testimonial-card">';
                $html .= '<blockquote class="vbp-testimonial-card__quote"' . $this->get_editor_contenteditable_path_attr( 'items.' . $index . '.texto' ) . '>' . wp_kses_post( $item['texto'] ?? '' ) . '</blockquote>';
                $html .= '<div class="vbp-testimonial-card__author">';
                $html .= '<span class="vbp-testimonial-card__name"' . $this->get_editor_contenteditable_path_attr( 'items.' . $index . '.autor' ) . '>' . esc_html( $autor_item ) . '</span>';

                if ( ! empty( $item['cargo'] ) ) {
                    $html .= '<span class="vbp-testimonial-card__role"' . $this->get_editor_contenteditable_path_attr( 'items.' . $index . '.cargo' ) . '>' . esc_html( $item['cargo'] ) . '</span>';
                }

                $html .= '</div>';
                $html .= '</div>';
            }

            $html .= '</div>';
        }

        $html .= '</section>';

        return $html;
    }

    /**
     * Renderiza Pricing
     */
    private function render_pricing( $elemento ) {
        $data        = $elemento['data'] ?? array();
        $estilos     = $elemento['styles'] ?? array();
        $estilos_css = $this->generar_estilos_elemento( $estilos );

        $titulo    = $data['titulo'] ?? '';
        $subtitulo = $data['subtitulo'] ?? '';
        $items     = $data['items'] ?? array();

        $html = '<section class="vbp-pricing" style="' . esc_attr( $estilos_css ) . '">';

        if ( $titulo ) {
            $html .= '<h2 class="vbp-pricing__title"' . $this->get_editor_contenteditable_attr( 'titulo' ) . '>' . wp_kses_post( $titulo ) . '</h2>';
        }

        if ( $subtitulo ) {
            $html .= '<p class="vbp-pricing__subtitle"' . $this->get_editor_contenteditable_attr( 'subtitulo' ) . '>' . wp_kses_post( $subtitulo ) . '</p>';
        }

        if ( ! empty( $items ) ) {
            $html .= '<div class="vbp-pricing__grid">';

            foreach ( $items as $index => $item ) {
                $destacado = ! empty( $item['destacado'] ) ? ' vbp-pricing-card--featured' : '';

                $html .= '<div class="vbp-pricing-card' . $destacado . '">';
                $html .= '<h3 class="vbp-pricing-card__name"' . $this->get_editor_contenteditable_path_attr( 'items.' . $index . '.nombre' ) . '>' . esc_html( $item['nombre'] ?? '' ) . '</h3>';
                $html .= '<div class="vbp-pricing-card__price">';
                $html .= '<span class="vbp-pricing-card__amount" data-strip-prefix="$"' . $this->get_editor_contenteditable_path_attr( 'items.' . $index . '.precio' ) . '>$' . esc_html( $item['precio'] ?? '0' ) . '</span>';

                if ( ! empty( $item['periodo'] ) ) {
                    $html .= '<span class="vbp-pricing-card__period"' . $this->get_editor_contenteditable_path_attr( 'items.' . $index . '.periodo' ) . '>' . esc_html( $item['periodo'] ) . '</span>';
                }

                $html .= '</div>';

                // Características
                if ( ! empty( $item['caracteristicas'] ) ) {
                    $html .= '<ul class="vbp-pricing-card__features">';

                    $caracteristicas = is_array( $item['caracteristicas'] ) ? $item['caracteristicas'] : explode( "\n", $item['caracteristicas'] );

                    foreach ( $caracteristicas as $feature ) {
                        if ( trim( $feature ) ) {
                            $html .= '<li>' . esc_html( trim( $feature ) ) . '</li>';
                        }
                    }

                    $html .= '</ul>';
                }

                $html .= '<a href="#" class="vbp-pricing-card__button">Elegir plan</a>';
                $html .= '</div>';
            }

            $html .= '</div>';
        }

        $html .= '</section>';

        return $html;
    }

    /**
     * Renderiza FAQ
     */
    private function render_faq( $elemento ) {
        $data        = $elemento['data'] ?? array();
        $estilos     = $elemento['styles'] ?? array();
        $estilos_css = $this->generar_estilos_elemento( $estilos );

        $titulo    = $data['titulo'] ?? '';
        $subtitulo = $data['subtitulo'] ?? '';
        // Acepta alias "faqs" (generado por los presets) ademas de "items".
        $items     = ! empty( $data['items'] ) ? $data['items'] : ( $data['faqs'] ?? array() );

        $html = '<section class="vbp-faq" style="' . esc_attr( $estilos_css ) . '">';

        if ( $titulo ) {
            $html .= '<h2 class="vbp-faq__title"' . $this->get_editor_contenteditable_attr( 'titulo' ) . '>' . wp_kses_post( $titulo ) . '</h2>';
        }
        if ( $subtitulo ) {
            $html .= '<p class="vbp-faq__subtitle"' . $this->get_editor_contenteditable_attr( 'subtitulo' ) . '>' . wp_kses_post( $subtitulo ) . '</p>';
        }

        if ( ! empty( $items ) ) {
            $html .= '<div class="vbp-faq__list">';

            foreach ( $items as $index => $item ) {
                $html .= '<details class="vbp-faq-item">';
                $html .= '<summary class="vbp-faq-item__question"' . $this->get_editor_contenteditable_path_attr( 'items.' . $index . '.pregunta' ) . '>' . esc_html( $item['pregunta'] ?? '' ) . '</summary>';
                $html .= '<div class="vbp-faq-item__answer"' . $this->get_editor_contenteditable_path_attr( 'items.' . $index . '.respuesta' ) . '>' . wp_kses_post( $item['respuesta'] ?? '' ) . '</div>';
                $html .= '</details>';
            }

            $html .= '</div>';
        }

        $html .= '</section>';

        return $html;
    }

    /**
     * Renderiza una seccion "Como funciona" con pasos numerados.
     * Acepta data.pasos con items {numero, titulo, descripcion, icono}.
     */
    private function render_como_funciona( $elemento ) {
        $data        = $elemento['data'] ?? array();
        $estilos     = $elemento['styles'] ?? array();
        $variante    = $elemento['variant'] ?? 'timeline';
        $estilos_css = $this->generar_estilos_elemento( $estilos );

        $titulo    = $data['titulo'] ?? '';
        $subtitulo = $data['subtitulo'] ?? '';
        $pasos     = ! empty( $data['pasos'] ) ? $data['pasos'] : ( $data['items'] ?? array() );

        $html = '<section class="vbp-como-funciona vbp-como-funciona--' . esc_attr( $variante ) . '" style="' . esc_attr( $estilos_css ) . '">';
        $html .= '<div class="vbp-container" style="max-width:1200px;margin:0 auto;padding:80px 24px;">';

        if ( $titulo ) {
            $html .= '<h2 class="vbp-como-funciona__title" style="text-align:center;font-size:36px;font-weight:700;margin:0 0 12px;color:#111827;"' . $this->get_editor_contenteditable_attr( 'titulo' ) . '>' . wp_kses_post( $titulo ) . '</h2>';
        }
        if ( $subtitulo ) {
            $html .= '<p class="vbp-como-funciona__subtitle" style="text-align:center;font-size:18px;color:#6b7280;margin:0 0 56px;"' . $this->get_editor_contenteditable_attr( 'subtitulo' ) . '>' . wp_kses_post( $subtitulo ) . '</p>';
        }

        if ( ! empty( $pasos ) ) {
            $columnas_pasos = max( 1, min( 4, count( $pasos ) ) );
            $html .= '<div class="vbp-como-funciona__grid" style="display:grid;grid-template-columns:repeat(' . esc_attr( (string) $columnas_pasos ) . ',minmax(0,1fr));gap:32px;">';

            foreach ( $pasos as $index => $paso_actual ) {
                $numero_paso      = $paso_actual['numero']      ?? (string) ( $index + 1 );
                $titulo_paso      = $paso_actual['titulo']      ?? '';
                $descripcion_paso = $paso_actual['descripcion'] ?? '';
                $icono_paso       = $paso_actual['icono']       ?? '';

                $html .= '<div class="vbp-como-funciona__step" style="text-align:center;">';
                $html .= '<div class="vbp-como-funciona__number" aria-hidden="true" style="display:inline-flex;align-items:center;justify-content:center;width:56px;height:56px;border-radius:50%;background:var(--flavor-primary,#6366f1);color:#fff;font-size:22px;font-weight:700;margin:0 auto 16px;">';
                $html .= $icono_paso !== '' && ! preg_match( '/^[0-9]+$/', (string) $icono_paso )
                    ? esc_html( (string) $icono_paso )
                    : esc_html( (string) $numero_paso );
                $html .= '</div>';
                if ( $titulo_paso !== '' ) {
                    $html .= '<h3 style="font-size:18px;font-weight:600;margin:0 0 8px;color:#111827;"' . $this->get_editor_contenteditable_path_attr( 'pasos.' . $index . '.titulo' ) . '>' . esc_html( $titulo_paso ) . '</h3>';
                }
                if ( $descripcion_paso !== '' ) {
                    $html .= '<p style="font-size:14px;color:#4b5563;line-height:1.6;margin:0;"' . $this->get_editor_contenteditable_path_attr( 'pasos.' . $index . '.descripcion' ) . '>' . wp_kses_post( $descripcion_paso ) . '</p>';
                }
                $html .= '</div>';
            }

            $html .= '</div>';
        }

        $html .= '</div></section>';
        return $html;
    }

    /**
     * Resuelve el array de items de un bloque editorial aceptando tanto
     * $data[$clave] (array nativo; usado cuando el post se crea desde PHP)
     * como $data[$clave.'_json'] (string JSON; usado cuando los datos
     * vienen del inspector del editor con un textarea). Asi los bloques
     * funcionan con ambos caminos sin perder caracteres unicode en el
     * round-trip serialize/unslash de post_meta.
     *
     * @param array  $data  Data del elemento.
     * @param string $clave Nombre base (p.ej. "items" o "meta").
     * @return array
     */
    private function editorial_parse_items( $data, $clave ) {
        $valor_array = $data[ $clave ] ?? null;
        if ( is_array( $valor_array ) ) {
            return $valor_array;
        }
        $cadena_json = $data[ $clave . '_json' ] ?? '[]';
        if ( is_array( $cadena_json ) ) {
            return $cadena_json;
        }
        $decodificado = json_decode( (string) $cadena_json, true );
        return is_array( $decodificado ) ? $decodificado : array();
    }

    /**
     * Renderiza un icono aceptando 4 formatos: clase FontAwesome (fa-*), slug
     * Material Icons (a-z0-9_-), SVG inline (empieza por <svg) o emoji/unicode.
     * Las dependencias (FontAwesome y Material Icons Outlined) las cargan el
     * canvas y editorial_assets_once(); este helper solo pinta el tag correcto.
     *
     * @param string $icono_bruto Texto del icono tal como viene del campo.
     * @return string HTML del icono (ya escapado), o cadena vacia si esta vacio.
     */
    /**
     * Devuelve la clase CSS modificadora a apendar al wrapper editorial
     * segun data.tema ("light" / "dark" / "red"). Por defecto no modifica
     * (tema oscuro = default de .vbp-editorial).
     *
     * @param array $data Array data del elemento VBP.
     * @return string Cadena con prefijo de espacio, o vacia.
     */
    private function editorial_tema_class( $data ) {
        $tema = strtolower( trim( (string) ( $data['tema'] ?? '' ) ) );
        $temas_validos = array( 'light', 'red' );
        if ( ! in_array( $tema, $temas_validos, true ) ) {
            return '';
        }
        return ' vbp-editorial--' . $tema;
    }

    private function render_editorial_icon( $icono_bruto ) {
        $icono_bruto = trim( (string) $icono_bruto );
        if ( '' === $icono_bruto ) {
            return '';
        }
        if ( 0 === strpos( $icono_bruto, 'fa-' ) ) {
            return '<i class="fas ' . esc_attr( $icono_bruto ) . '" aria-hidden="true"></i>';
        }
        if ( 0 === strpos( $icono_bruto, '<svg' ) ) {
            return wp_kses( $icono_bruto, array(
                'svg'     => array( 'xmlns' => true, 'viewbox' => true, 'width' => true, 'height' => true, 'fill' => true, 'stroke' => true, 'stroke-width' => true, 'stroke-linecap' => true, 'stroke-linejoin' => true, 'class' => true, 'aria-hidden' => true ),
                'path'    => array( 'd' => true, 'fill' => true, 'stroke' => true, 'stroke-width' => true, 'stroke-linecap' => true, 'stroke-linejoin' => true ),
                'circle'  => array( 'cx' => true, 'cy' => true, 'r' => true, 'fill' => true, 'stroke' => true, 'stroke-width' => true ),
                'rect'    => array( 'x' => true, 'y' => true, 'width' => true, 'height' => true, 'rx' => true, 'ry' => true, 'fill' => true, 'stroke' => true, 'stroke-width' => true ),
                'line'    => array( 'x1' => true, 'y1' => true, 'x2' => true, 'y2' => true, 'stroke' => true, 'stroke-width' => true ),
                'polyline'=> array( 'points' => true, 'fill' => true, 'stroke' => true, 'stroke-width' => true ),
                'polygon' => array( 'points' => true, 'fill' => true, 'stroke' => true, 'stroke-width' => true ),
                'g'       => array( 'fill' => true, 'stroke' => true, 'stroke-width' => true ),
            ) );
        }
        if ( preg_match( '/^[a-z0-9_-]+$/', $icono_bruto ) ) {
            return '<span class="material-icons material-icons-outlined" aria-hidden="true">' . esc_html( $icono_bruto ) . '</span>';
        }
        return '<span aria-hidden="true">' . esc_html( $icono_bruto ) . '</span>';
    }

    /**
     * Inyecta una sola vez por request los assets comunes del preset editorial:
     * Google Fonts (Playfair Display + Libre Baskerville + IBM Plex Mono) y
     * CSS base con variables, grain overlay y reveal-on-scroll. Los renders
     * editoriales llaman a este metodo antes de emitir su HTML.
     *
     * @return string HTML de <link>/<style>/<script> o cadena vacia si ya
     *                se emitio en esta request.
     */
    private function editorial_assets_once() {
        static $ya_emitido = false;
        if ( $ya_emitido ) {
            return '';
        }
        $ya_emitido = true;

        $fuentes_url = 'https://fonts.googleapis.com/css2?family=Playfair+Display:ital,wght@0,400;0,700;0,900;1,400;1,700&family=IBM+Plex+Mono:wght@400;500&family=Libre+Baskerville:ital,wght@0,400;0,700;1,400&display=swap';

        $html_salida  = '<link rel="preconnect" href="https://fonts.googleapis.com">';
        $html_salida .= '<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>';
        $html_salida .= '<link href="' . esc_url( $fuentes_url ) . '" rel="stylesheet">';
        // Material Icons para iconos en chips/cards (alternativa a emojis).
        $html_salida .= '<link href="https://fonts.googleapis.com/icon?family=Material+Icons+Outlined" rel="stylesheet">';
        $html_salida .= "\n<style id='vbp-editorial-base'>\n";
        $html_salida .= ":root{--ink:#0D0B07;--paper:#F2EDE3;--red:#C8261A;--amber:#D4920A;--muted:#6B6459;--rule:#2A2620;--rule-l:#C4BAA4;--serif:'Playfair Display',Georgia,serif;--body:'Libre Baskerville',Georgia,serif;--mono:'IBM Plex Mono',monospace;}";
        // Tema por defecto = oscuro (el HTML original tiene body ink con islas paper).
        // Cada seccion redefine variables locales para que los estilos internos
        // (rule, body muted, rule-inline) funcionen en ambos temas sin duplicar CSS.
        $html_salida .= ".vbp-editorial{background:var(--ink);color:var(--paper);font-family:var(--body);line-height:1.6;--rule:#2A2620;--body-muted:rgba(242,237,227,.72);--body-muted-strong:rgba(242,237,227,.85);}";
        $html_salida .= ".vbp-editorial *,.vbp-editorial *::before,.vbp-editorial *::after{box-sizing:border-box;}";
        $html_salida .= ".vbp-editorial h1,.vbp-editorial h2,.vbp-editorial h3{font-family:var(--serif);color:inherit;}";
        // Variante light: secciones claras (products first card, news-section, demo-section).
        $html_salida .= ".vbp-editorial--light{background:var(--paper);color:var(--ink);--rule:#C4BAA4;--body-muted:#3A3630;--body-muted-strong:#2A2720;}";
        // Variante red: manifiesto sobre fondo rojo — color texto paper
        $html_salida .= ".vbp-editorial--red{background:var(--red);color:var(--paper);--rule:rgba(242,237,227,.25);--body-muted:rgba(242,237,227,.8);--body-muted-strong:rgba(242,237,227,.9);}";
        // body por defecto en ink cuando hay preset editorial activo
        $html_salida .= "body.vbp-editorial-page{background:var(--ink);color:var(--paper);}";
        // Grain overlay global
        $html_salida .= "body.vbp-editorial-page::before{content:'';position:fixed;inset:0;z-index:9999;pointer-events:none;opacity:.035;background-image:url(\"data:image/svg+xml,%3Csvg viewBox='0 0 256 256' xmlns='http://www.w3.org/2000/svg'%3E%3Cfilter id='n'%3E%3CfeTurbulence type='fractalNoise' baseFrequency='0.9' numOctaves='4' stitchTiles='stitch'/%3E%3C/filter%3E%3Crect width='100%25' height='100%25' filter='url(%23n)'/%3E%3C/svg%3E\");background-size:200px 200px;}";
        // Ticker
        $html_salida .= ".vbp-ticker-wrap{overflow:hidden;border-top:1px solid var(--rule);border-bottom:1px solid var(--rule);padding:.45rem 0;}";
        $html_salida .= ".vbp-ticker{display:flex;gap:3rem;width:max-content;animation:vbpTicker var(--ticker-dur,28s) linear infinite;}";
        $html_salida .= ".vbp-ticker-item{font-family:var(--mono);font-size:.68rem;letter-spacing:.12em;text-transform:uppercase;white-space:nowrap;opacity:.7;}";
        $html_salida .= ".vbp-ticker-item span{color:var(--red);margin-right:.5rem;}";
        $html_salida .= "@keyframes vbpTicker{from{transform:translateX(0);}to{transform:translateX(-50%);}}";
        // Hero editorial
        $html_salida .= ".vbp-hero-edit{padding:3rem 2rem 0;border-bottom:1px solid var(--rule);}";
        $html_salida .= ".vbp-hero-edit__kicker{font-family:var(--mono);font-size:.7rem;letter-spacing:.15em;text-transform:uppercase;color:var(--red);margin-bottom:.75rem;}";
        $html_salida .= ".vbp-hero-edit__title{font-family:var(--serif);font-size:clamp(2.8rem,8vw,7rem);font-weight:900;line-height:.96;letter-spacing:-.02em;max-width:18ch;margin:0;}";
        $html_salida .= ".vbp-hero-edit__title em{font-style:italic;color:var(--red);}";
        $html_salida .= ".vbp-hero-edit__rule{width:100%;height:1px;background:var(--rule);margin:2.5rem 0 0;}";
        $html_salida .= ".vbp-hero-edit__cols{display:grid;grid-template-columns:1fr 1fr;gap:0;}";
        $html_salida .= "@media(max-width:640px){.vbp-hero-edit__cols{grid-template-columns:1fr;}}";
        $html_salida .= ".vbp-hero-edit__lead{padding:2rem 2rem 2.5rem 0;border-right:1px solid var(--rule);}";
        $html_salida .= ".vbp-hero-edit__lead p{font-size:1.05rem;line-height:1.75;color:var(--body-muted-strong);max-width:44ch;}";
        $html_salida .= ".vbp-hero-edit__lead p+p{margin-top:1rem;}";
        $html_salida .= ".vbp-hero-edit__stat{padding:2rem 0 2.5rem 2rem;display:flex;flex-direction:column;justify-content:space-between;gap:2rem;}";
        $html_salida .= ".vbp-hero-edit__pull{font-family:var(--serif);font-size:clamp(1.3rem,2.5vw,1.7rem);font-weight:700;font-style:italic;line-height:1.3;color:var(--ink);border-left:3px solid var(--red);padding-left:1rem;margin:0;}";
        $html_salida .= ".vbp-hero-edit__meta{display:flex;flex-direction:column;gap:.4rem;list-style:none;padding:0;margin:0;}";
        $html_salida .= ".vbp-hero-edit__meta li{font-family:var(--mono);font-size:.7rem;letter-spacing:.08em;text-transform:uppercase;color:var(--muted);}";
        $html_salida .= ".vbp-hero-edit__meta li::before{content:'— ';color:var(--rule);}";
        // Feature numbered
        $html_salida .= ".vbp-feat-num{padding:4rem 2rem;border-bottom:1px solid var(--rule);}";
        $html_salida .= ".vbp-feat-num__label{font-family:var(--mono);font-size:.7rem;letter-spacing:.15em;text-transform:uppercase;color:var(--muted);margin-bottom:2rem;}";
        $html_salida .= ".vbp-feat-num__grid{display:grid;gap:0;}";
        $html_salida .= ".vbp-feat-num__item{padding:1.5rem 1.5rem 1.5rem 0;border-right:1px solid var(--rule);}";
        $html_salida .= ".vbp-feat-num__item:last-child{border-right:none;}";
        $html_salida .= "@media(max-width:900px){.vbp-feat-num__grid{grid-template-columns:1fr 1fr !important;}.vbp-feat-num__item{border-right:none;border-bottom:1px solid var(--rule);}}";
        $html_salida .= ".vbp-feat-num__num{font-family:var(--mono);font-size:.7rem;letter-spacing:.1em;color:var(--red);margin-bottom:.75rem;}";
        $html_salida .= ".vbp-feat-num__title{font-family:var(--serif);font-size:1.15rem;font-weight:700;line-height:1.25;margin:0 0 .75rem;}";
        $html_salida .= ".vbp-feat-num__desc,.vbp-feat-num__desc p{font-size:.9rem;line-height:1.6;color:var(--body-muted-strong);opacity:.85;margin:0;}";
        $html_salida .= ".vbp-feat-num__desc p+p{margin-top:.75rem;}";
        $html_salida .= ".vbp-feat-num__icon{font-size:1.6rem;line-height:1;margin-bottom:.75rem;}";
        $html_salida .= ".vbp-feat-num__tags{display:flex;flex-wrap:wrap;gap:.35rem;margin-top:.9rem;}";
        $html_salida .= ".vbp-feat-num__tag{font-family:var(--mono);font-size:.58rem;letter-spacing:.07em;text-transform:uppercase;border:1px solid var(--rule);padding:.15rem .5rem;color:var(--muted);}";
        // Items linkados: card entera clickable. Mantenemos colores del
        // contenido y solo cambiamos cursor/hover. La flecha aparece a la
        // derecha y se desplaza ligeramente al pasar el cursor.
        $html_salida .= ".vbp-feat-num__item--linked{display:flex;flex-direction:column;text-decoration:none;color:inherit;position:relative;transition:background .15s ease;}";
        $html_salida .= ".vbp-feat-num__item--linked:hover{background:rgba(200,38,26,.04);}";
        $html_salida .= ".vbp-feat-num__item--linked:hover .vbp-feat-num__title{color:var(--red);}";
        $html_salida .= ".vbp-feat-num__arrow{display:none;}";
        $html_salida .= ".vbp-feat-num__item--linked .vbp-feat-num__arrow{display:inline-block;font-family:var(--mono);font-size:1rem;color:var(--red);margin-top:auto;padding-top:1rem;align-self:flex-start;transition:transform .2s ease;}";
        $html_salida .= ".vbp-feat-num__item--linked:hover .vbp-feat-num__arrow{transform:translateX(.35rem);}";
        $html_salida .= ".vbp-feat-num__kicker{font-family:var(--mono);font-size:.58rem;letter-spacing:.2em;text-transform:uppercase;color:var(--red);margin-bottom:.6rem;}";
        // Variante big_numbers: numero grande amber (hero stats)
        $html_salida .= ".vbp-feat-num--big_numbers{padding:2.5rem 2rem 3rem;}";
        $html_salida .= ".vbp-feat-num--big_numbers .vbp-feat-num__item{padding:0 1rem;border-right:1px solid var(--rule);border-left:2px solid var(--rule);}";
        $html_salida .= ".vbp-feat-num--big_numbers .vbp-feat-num__num{font-family:var(--serif);font-size:clamp(2.4rem,5vw,3.6rem);font-weight:900;line-height:1;color:var(--amber);letter-spacing:-.02em;margin-bottom:.4rem;}";
        $html_salida .= ".vbp-feat-num--big_numbers .vbp-feat-num__title{font-family:var(--mono);font-size:.62rem;font-weight:400;letter-spacing:.14em;text-transform:uppercase;color:var(--muted);margin:0;}";
        $html_salida .= ".vbp-feat-num--big_numbers .vbp-feat-num__desc{display:none;}";
        // Variante rule_title: titulo subrayado con borde rojo inferior
        $html_salida .= ".vbp-feat-num--rule_title .vbp-feat-num__title{border-bottom:2px solid var(--red);padding-bottom:.5rem;display:inline-block;}";
        $html_salida .= ".vbp-feat-num--rule_title .vbp-feat-num__num{display:none;}";
        // Chip list
        $html_salida .= ".vbp-chips{display:flex;flex-wrap:wrap;gap:.5rem;padding:1rem 2rem;}";
        $html_salida .= ".vbp-chips__item{font-size:.7rem;letter-spacing:.06em;text-transform:uppercase;border:1px solid var(--rule);padding:.3rem .75rem;color:var(--ink);background:transparent;border-radius:999px;}";
        $html_salida .= ".vbp-chips__item--mono{font-family:var(--mono);}";
        // Principles list
        $html_salida .= ".vbp-princ{padding:4rem 2rem;border-bottom:1px solid var(--rule);}";
        $html_salida .= ".vbp-princ__title{font-family:var(--serif);font-size:2.2rem;font-weight:900;line-height:1.1;margin:0 0 2.5rem;}";
        $html_salida .= ".vbp-princ__list{display:grid;gap:2rem;list-style:none;padding:0;margin:0;}";
        $html_salida .= ".vbp-princ__item{border-top:2px solid var(--ink);padding-top:1rem;}";
        $html_salida .= ".vbp-princ__item strong{display:block;font-family:var(--serif);font-size:1.1rem;font-weight:700;margin-bottom:.5rem;}";
        $html_salida .= ".vbp-princ__item-desc{font-size:.9rem;line-height:1.6;color:var(--body-muted-strong);opacity:.85;}";
        // Masthead
        $html_salida .= ".vbp-masthead{border-bottom:3px double var(--ink);padding:1rem 2rem;display:flex;align-items:center;justify-content:space-between;gap:1rem;flex-wrap:wrap;}";
        $html_salida .= ".vbp-masthead__tagline{font-family:var(--mono);font-size:.65rem;letter-spacing:.12em;text-transform:uppercase;color:var(--muted);}";
        $html_salida .= ".vbp-masthead__links{display:flex;gap:1.5rem;flex-wrap:wrap;}";
        $html_salida .= ".vbp-masthead__links a{font-family:var(--mono);font-size:.6rem;letter-spacing:.12em;text-transform:uppercase;text-decoration:none;color:var(--muted);transition:color .15s;}";
        $html_salida .= ".vbp-masthead__links a:hover{color:var(--ink);}";
        $html_salida .= ".vbp-masthead__badge{font-family:var(--mono);font-size:.65rem;letter-spacing:.1em;text-transform:uppercase;border:1px solid var(--rule);padding:.2rem .6rem;color:var(--muted);}";
        // CTA strip
        $html_salida .= ".vbp-cta-strip{padding:1.2rem 2rem;display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:1rem;border-bottom:1px solid var(--rule);}";
        $html_salida .= ".vbp-cta-strip__text{font-family:var(--mono);font-size:.75rem;letter-spacing:.1em;text-transform:uppercase;opacity:.7;margin:0;}";
        $html_salida .= ".vbp-cta-strip__buttons{display:flex;gap:.75rem;flex-wrap:wrap;}";
        // Editorial buttons (compartidos cta_strip + hosting_dark)
        $html_salida .= ".vbp-ed-btn{font-family:var(--mono);font-size:.72rem;letter-spacing:.1em;text-transform:uppercase;text-decoration:none;padding:.5rem 1.2rem;border:1px solid currentColor;display:inline-block;transition:background .15s,color .15s;}";
        $html_salida .= ".vbp-ed-btn--light{color:inherit;}";
        $html_salida .= ".vbp-ed-btn--light:hover{background:var(--paper);color:var(--ink);}";
        $html_salida .= ".vbp-ed-btn--red{background:var(--red);color:var(--paper);border-color:var(--red);}";
        $html_salida .= ".vbp-ed-btn--red:hover{background:#a51f14;border-color:#a51f14;}";
        // Why / editorial_split_quote
        $html_salida .= ".vbp-why{padding:4rem 2rem;border-bottom:1px solid var(--rule);}";
        $html_salida .= ".vbp-why__grid{display:grid;grid-template-columns:1fr 2fr;gap:4rem;align-items:start;}";
        $html_salida .= "@media(max-width:700px){.vbp-why__grid{grid-template-columns:1fr;gap:2rem;}}";
        $html_salida .= ".vbp-why__aside{position:sticky;top:2rem;}";
        $html_salida .= ".vbp-why__title{font-family:var(--serif);font-size:2.5rem;font-weight:900;line-height:1.1;margin:0 0 1.5rem;}";
        $html_salida .= ".vbp-why__title em{font-style:italic;color:var(--red);}";
        $html_salida .= ".vbp-why__tags{display:flex;flex-wrap:wrap;gap:.4rem;}";
        $html_salida .= ".vbp-why__tag{font-family:var(--mono);font-size:.63rem;letter-spacing:.08em;text-transform:uppercase;border:1px solid var(--rule);padding:.2rem .55rem;color:var(--muted);}";
        $html_salida .= ".vbp-why__body p{font-size:.98rem;line-height:1.85;color:var(--body-muted-strong);margin:0 0 1.25rem;max-width:60ch;}";
        $html_salida .= ".vbp-why__body strong{font-weight:700;color:var(--ink);}";
        $html_salida .= ".vbp-why__quote{border-left:3px solid var(--red);padding:1rem 1.5rem;margin:2rem 0;background:rgba(200,38,26,.04);font-family:var(--serif);font-size:1.1rem;font-style:italic;line-height:1.6;color:var(--ink);}";
        // Principles filled variant (manifiesto rojo)
        $html_salida .= ".vbp-princ--filled{padding:3rem 2rem;}";
        $html_salida .= ".vbp-princ--filled .vbp-princ__item{border-top-color:currentColor;opacity:.9;}";
        $html_salida .= ".vbp-princ--filled .vbp-princ__item strong{font-family:var(--mono);font-size:.9rem;letter-spacing:.1em;text-transform:uppercase;}";
        $html_salida .= ".vbp-princ--filled .vbp-princ__item-desc{font-family:var(--mono);font-size:.78rem;letter-spacing:.05em;line-height:1.6;opacity:.9;}";
        // Relation split (3 columnas con conector central)
        $html_salida .= ".vbp-relation{padding:4rem 2rem;border-bottom:1px solid var(--rule);display:grid;grid-template-columns:2fr 1fr 2fr;gap:0;align-items:stretch;}";
        $html_salida .= "@media(max-width:700px){.vbp-relation{grid-template-columns:1fr;}}";
        $html_salida .= ".vbp-relation__col{padding:2rem;border-right:1px solid var(--rule);}";
        $html_salida .= ".vbp-relation__col:last-child{border-right:none;}";
        $html_salida .= "@media(max-width:700px){.vbp-relation__col{border-right:none;border-bottom:1px solid var(--rule);padding:1.5rem 0;}.vbp-relation__col:last-child{border-bottom:none;}}";
        $html_salida .= ".vbp-relation__title{font-family:var(--serif);font-size:1.3rem;font-weight:700;margin:0 0 .8rem;}";
        $html_salida .= ".vbp-relation__col p{font-size:.9rem;line-height:1.7;color:var(--body-muted);margin:0 0 .75rem;}";
        $html_salida .= ".vbp-relation__connector{display:flex;align-items:center;justify-content:center;font-family:var(--mono);font-size:.7rem;letter-spacing:.1em;text-transform:uppercase;color:var(--red);padding:2rem;text-align:center;line-height:2;border-right:1px solid var(--rule);}";
        $html_salida .= "@media(max-width:700px){.vbp-relation__connector{border-right:none;border-bottom:1px solid var(--rule);padding:1.5rem 0;}}";
        // Hosting dark (seccion oscura con pasos)
        $html_salida .= ".vbp-hosting{padding:4rem 2rem;border-bottom:3px double var(--ink);background:var(--ink);color:var(--paper);}";
        $html_salida .= ".vbp-hosting__inner{display:grid;grid-template-columns:1fr 1fr;gap:4rem;align-items:start;}";
        $html_salida .= "@media(max-width:700px){.vbp-hosting__inner{grid-template-columns:1fr;gap:2rem;}}";
        $html_salida .= ".vbp-hosting__label{font-family:var(--mono);font-size:.65rem;letter-spacing:.2em;text-transform:uppercase;color:rgba(242,237,227,.5);margin-bottom:1rem;}";
        $html_salida .= ".vbp-hosting__title{font-family:var(--serif);font-size:2.2rem;font-weight:900;line-height:1.1;margin:0 0 1rem;}";
        $html_salida .= ".vbp-hosting__title em{font-style:italic;color:var(--red);}";
        $html_salida .= ".vbp-hosting__desc{font-size:.92rem;line-height:1.8;opacity:.75;margin:0 0 1rem;}";
        $html_salida .= ".vbp-hosting__cta{margin-top:2rem;display:flex;gap:.75rem;flex-wrap:wrap;}";
        $html_salida .= ".vbp-hosting__steps{list-style:none;display:flex;flex-direction:column;gap:1.2rem;padding:0;margin:0;}";
        $html_salida .= ".vbp-hosting__step{display:flex;gap:1rem;align-items:flex-start;}";
        $html_salida .= ".vbp-hosting__step-num{font-family:var(--mono);font-size:.7rem;letter-spacing:.1em;color:var(--red);padding-top:.1rem;flex-shrink:0;}";
        $html_salida .= ".vbp-hosting__step-text{font-size:.88rem;line-height:1.6;opacity:.8;}";
        $html_salida .= ".vbp-hosting__step-text strong{opacity:1;color:var(--paper);font-weight:700;}";
        // Footer editorial
        $html_salida .= ".vbp-foot-edit{padding:1.5rem 2rem;display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:1rem;}";
        $html_salida .= ".vbp-foot-edit__logo{font-family:var(--serif);font-size:1rem;font-weight:700;}";
        $html_salida .= ".vbp-foot-edit__logo-em{color:var(--red);}";
        $html_salida .= ".vbp-foot-edit__links{display:flex;gap:1.5rem;flex-wrap:wrap;}";
        $html_salida .= ".vbp-foot-edit__links a{font-family:var(--mono);font-size:.65rem;letter-spacing:.12em;text-transform:uppercase;text-decoration:none;color:var(--muted);transition:color .15s;}";
        $html_salida .= ".vbp-foot-edit__links a:hover{color:var(--ink);}";
        $html_salida .= ".vbp-foot-edit__license{font-family:var(--mono);font-size:.65rem;letter-spacing:.08em;color:var(--muted);}";
        // Code block con syntax highlight (clases code-cmd/flag/url/string/comment)
        $html_salida .= "pre.code-block{background:rgba(255,255,255,.03);border:1px solid var(--rule);padding:1.8rem;border-radius:0;overflow-x:auto;font-family:var(--mono);font-size:.73rem;line-height:1.7;color:rgba(242,237,227,.7);margin:0;}";
        $html_salida .= ".vbp-editorial pre.code-block{background:#111008;color:rgba(242,237,227,.75);}";
        $html_salida .= "pre.code-block .code-comment{color:#4A453D;}";
        $html_salida .= "pre.code-block .code-cmd{color:var(--paper);}";
        $html_salida .= "pre.code-block .code-flag{color:var(--amber);}";
        $html_salida .= "pre.code-block .code-url{color:#7AADCF;}";
        $html_salida .= "pre.code-block .code-string{color:#98C379;}";
        // Reveal on scroll
        $html_salida .= ".vbp-reveal{opacity:0;transform:translateY(20px);transition:opacity .6s ease,transform .6s ease;}";
        $html_salida .= ".vbp-reveal.is-visible{opacity:1;transform:translateY(0);}";
        $html_salida .= "\n</style>\n";
        // Observer script inline (una unica vez por request)
        $html_salida .= "<script>(function(){if(window.__vbpEditorialObs)return;window.__vbpEditorialObs=true;var obs=new IntersectionObserver(function(es){es.forEach(function(e){if(e.isIntersecting){e.target.classList.add('is-visible');obs.unobserve(e.target);}});},{threshold:.12});function boot(){document.querySelectorAll('.vbp-reveal').forEach(function(el){obs.observe(el);});document.body.classList.add('vbp-editorial-page');}if(document.readyState!=='loading')boot();else document.addEventListener('DOMContentLoaded',boot);})();</script>";
        return $html_salida;
    }

    /**
     * Renderiza un ticker horizontal animado (preset editorial).
     *
     * @param array $elemento Elemento a renderizar.
     * @return string
     */
    private function render_ticker( $elemento ) {
        $data            = $elemento['data'] ?? array();
        $items_decoded   = $this->editorial_parse_items( $data, 'items' );
        $duracion_ticker = (int) ( $data['duracion'] ?? 28 );
        if ( $duracion_ticker < 5 ) {
            $duracion_ticker = 28;
        }
        $color_fondo = $data['color_fondo'] ?? '#111008';
        $color_texto = $data['color_texto'] ?? '#F2EDE3';
        $separador   = (string) ( $data['separador'] ?? '◆' );
        $html_sep    = $this->render_editorial_icon( $separador );

        $html_ticker  = $this->editorial_assets_once();
        $html_ticker .= sprintf(
            '<div class="vbp-editorial vbp-ticker-wrap" style="background:%s;color:%s;--ticker-dur:%ds;">',
            esc_attr( $color_fondo ),
            esc_attr( $color_texto ),
            $duracion_ticker
        );
        $html_ticker .= '<div class="vbp-ticker" aria-hidden="true">';
        // Primera copia editable, segunda copia solo para el loop visual.
        foreach ( $items_decoded as $indice_item => $texto_item ) {
            $html_ticker .= '<span class="vbp-ticker-item"' . $this->get_editor_contenteditable_path_attr( 'items.' . $indice_item ) . '>' . $html_sep . ' ' . esc_html( (string) $texto_item ) . '</span>';
        }
        foreach ( $items_decoded as $texto_item_dup ) {
            $html_ticker .= '<span class="vbp-ticker-item" aria-hidden="true">' . $html_sep . ' ' . esc_html( (string) $texto_item_dup ) . '</span>';
        }
        $html_ticker .= '</div></div>';
        return $html_ticker;
    }

    /**
     * Renderiza el hero editorial con kicker + titulo con fragmento "em" en
     * rojo italic + doble columna (lead / pull quote + meta list).
     *
     * @param array $elemento Elemento a renderizar.
     * @return string
     */
    private function render_hero_editorial( $elemento ) {
        $data = $elemento['data'] ?? array();

        $kicker_hero      = (string) ( $data['kicker']      ?? '' );
        $titulo_pre_hero  = (string) ( $data['titulo_pre']  ?? '' );
        $titulo_em_hero   = (string) ( $data['titulo_em']   ?? '' );
        $titulo_post_hero = (string) ( $data['titulo_post'] ?? '' );
        $lead_html_hero   = (string) ( $data['lead_html']   ?? '' );
        $pull_quote_hero  = (string) ( $data['pull_quote']  ?? '' );

        $meta_decoded = $this->editorial_parse_items( $data, 'meta' );

        $html_hero  = $this->editorial_assets_once();
        $html_hero .= '<section class="vbp-editorial vbp-hero-edit' . $this->editorial_tema_class( $data ) . '">';

        if ( $kicker_hero !== '' ) {
            $html_hero .= '<p class="vbp-hero-edit__kicker"' . $this->get_editor_contenteditable_attr( 'kicker' ) . '>' . esc_html( $kicker_hero ) . '</p>';
        }

        if ( $titulo_pre_hero !== '' || $titulo_em_hero !== '' || $titulo_post_hero !== '' ) {
            $html_hero .= '<h1 class="vbp-hero-edit__title">';
            if ( $titulo_pre_hero !== '' ) {
                // Los saltos de linea en pre/post se convierten en <br> para
                // permitir titulares multilinea (p.ej. "Informarse / y luego /
                // actuar." con solo la ultima linea en italic rojo).
                $html_hero .= '<span' . $this->get_editor_contenteditable_attr( 'titulo_pre' ) . '>' . nl2br( esc_html( $titulo_pre_hero ) ) . '</span><br>';
            }
            if ( $titulo_em_hero !== '' ) {
                $html_hero .= '<em' . $this->get_editor_contenteditable_attr( 'titulo_em' ) . '>' . nl2br( esc_html( $titulo_em_hero ) ) . '</em>';
                if ( $titulo_post_hero !== '' ) {
                    $html_hero .= '<br>';
                }
            }
            if ( $titulo_post_hero !== '' ) {
                $html_hero .= '<span' . $this->get_editor_contenteditable_attr( 'titulo_post' ) . '>' . nl2br( esc_html( $titulo_post_hero ) ) . '</span>';
            }
            $html_hero .= '</h1>';
        }

        $html_hero .= '<div class="vbp-hero-edit__rule"></div>';
        $html_hero .= '<div class="vbp-hero-edit__cols">';
        $html_hero .= '<div class="vbp-hero-edit__lead vbp-reveal"' . $this->get_editor_contenteditable_attr( 'lead_html' ) . '>' . wp_kses_post( $lead_html_hero ) . '</div>';

        $html_hero .= '<div class="vbp-hero-edit__stat vbp-reveal">';
        if ( $pull_quote_hero !== '' ) {
            $html_hero .= '<blockquote class="vbp-hero-edit__pull"' . $this->get_editor_contenteditable_attr( 'pull_quote' ) . '>' . wp_kses_post( $pull_quote_hero ) . '</blockquote>';
        }
        if ( ! empty( $meta_decoded ) ) {
            $html_hero .= '<ul class="vbp-hero-edit__meta">';
            foreach ( $meta_decoded as $texto_meta ) {
                $html_hero .= '<li>' . esc_html( (string) $texto_meta ) . '</li>';
            }
            $html_hero .= '</ul>';
        }
        $html_hero .= '</div>';

        $html_hero .= '</div></section>';
        return $html_hero;
    }

    /**
     * Renderiza un grid numerado estilo periodico (01/02/03...), sin iconos,
     * con separadores verticales entre items.
     *
     * @param array $elemento Elemento a renderizar.
     * @return string
     */
    private function render_feature_numbered( $elemento ) {
        $data          = $elemento['data'] ?? array();
        $label_superior = (string) ( $data['label'] ?? '' );
        $columnas_num  = max( 1, min( 6, (int) ( $data['columnas'] ?? 5 ) ) );
        $variante_bloque = (string) ( $elemento['variant'] ?? $data['variant'] ?? 'default' );
        $variantes_admitidas = array( 'default', 'big_numbers', 'rule_title' );
        if ( ! in_array( $variante_bloque, $variantes_admitidas, true ) ) {
            $variante_bloque = 'default';
        }

        $items_decoded = $this->editorial_parse_items( $data, 'items' );

        $html_numbered  = $this->editorial_assets_once();
        $clase_seccion  = 'vbp-editorial vbp-feat-num';
        if ( 'default' !== $variante_bloque ) {
            $clase_seccion .= ' vbp-feat-num--' . $variante_bloque;
        }
        $clase_seccion .= $this->editorial_tema_class( $data );
        $html_numbered .= '<section class="' . esc_attr( $clase_seccion ) . '">';

        if ( $label_superior !== '' ) {
            $html_numbered .= '<div class="vbp-feat-num__label"' . $this->get_editor_contenteditable_attr( 'label' ) . '>' . esc_html( $label_superior ) . '</div>';
        }

        if ( ! empty( $items_decoded ) ) {
            $html_numbered .= sprintf(
                '<div class="vbp-feat-num__grid" style="grid-template-columns:repeat(%d,minmax(0,1fr));">',
                $columnas_num
            );

            foreach ( $items_decoded as $indice => $item_num ) {
                $numero_item      = array_key_exists( 'numero', $item_num ) ? (string) $item_num['numero'] : sprintf( '%02d', $indice + 1 );
                $titulo_item      = $item_num['titulo']      ?? '';
                $descripcion_item = $item_num['descripcion'] ?? '';
                $icono_item       = (string) ( $item_num['icono'] ?? '' );
                $kicker_item      = (string) ( $item_num['kicker'] ?? '' );
                $tags_item        = isset( $item_num['tags'] ) && is_array( $item_num['tags'] ) ? $item_num['tags'] : array();
                $url_item         = trim( (string) ( $item_num['url'] ?? '' ) );
                $abrir_nueva_item = ! empty( $item_num['nueva'] );

                // Si el item lleva url, envolvemos la card entera en <a> para
                // que toda la celda sea pulsable. Mantenemos las clases base y
                // anadimos --linked para que el CSS quite text-decoration y
                // aplique hover sutil sin pisar colores del contenido.
                if ( $url_item !== '' ) {
                    $atributos_link = ' class="vbp-feat-num__item vbp-feat-num__item--linked vbp-reveal" href="' . esc_url( $url_item ) . '"';
                    if ( $abrir_nueva_item ) {
                        $atributos_link .= ' target="_blank" rel="noopener"';
                    }
                    $html_numbered .= '<a' . $atributos_link . '>';
                    $tag_cierre_item = '</a>';
                } else {
                    $html_numbered .= '<div class="vbp-feat-num__item vbp-reveal">';
                    $tag_cierre_item = '</div>';
                }
                if ( $kicker_item !== '' ) {
                    $html_numbered .= '<div class="vbp-feat-num__kicker"' . $this->get_editor_contenteditable_path_attr( 'items.' . $indice . '.kicker' ) . '>' . esc_html( $kicker_item ) . '</div>';
                }
                if ( $icono_item !== '' ) {
                    $html_numbered .= '<div class="vbp-feat-num__icon" aria-hidden="true">' . $this->render_editorial_icon( $icono_item ) . '</div>';
                }
                if ( $numero_item !== '' ) {
                    $html_numbered .= '<div class="vbp-feat-num__num"' . $this->get_editor_contenteditable_path_attr( 'items.' . $indice . '.numero' ) . '>' . esc_html( $numero_item ) . '</div>';
                }
                if ( $titulo_item !== '' ) {
                    $html_numbered .= '<h3 class="vbp-feat-num__title"' . $this->get_editor_contenteditable_path_attr( 'items.' . $indice . '.titulo' ) . '>' . esc_html( (string) $titulo_item ) . '</h3>';
                }
                if ( $descripcion_item !== '' ) {
                    // Contenedor div (no p) para aceptar varios <p> hijos sin
                    // generar HTML invalido, p.ej. en arch-cards con 2 parrafos.
                    $html_numbered .= '<div class="vbp-feat-num__desc"' . $this->get_editor_contenteditable_path_attr( 'items.' . $indice . '.descripcion' ) . '>' . wp_kses_post( (string) $descripcion_item ) . '</div>';
                }
                if ( ! empty( $tags_item ) ) {
                    $html_numbered .= '<div class="vbp-feat-num__tags">';
                    foreach ( $tags_item as $indice_tag => $tag_texto ) {
                        $html_numbered .= '<span class="vbp-feat-num__tag"' . $this->get_editor_contenteditable_path_attr( 'items.' . $indice . '.tags.' . $indice_tag ) . '>' . esc_html( (string) $tag_texto ) . '</span>';
                    }
                    $html_numbered .= '</div>';
                }
                // Indicador visual de "ver mas" en items linkados (flecha mono
                // a la derecha). Solo se muestra en --linked via CSS.
                if ( $url_item !== '' ) {
                    $html_numbered .= '<span class="vbp-feat-num__arrow" aria-hidden="true">→</span>';
                }
                $html_numbered .= $tag_cierre_item;
            }
            $html_numbered .= '</div>';
        }

        $html_numbered .= '</section>';
        return $html_numbered;
    }

    /**
     * Renderiza una lista inline de chips (p.ej. rutas de paginas, etiquetas
     * tecnologicas). Disenado para el preset editorial pero funcional sin el.
     *
     * @param array $elemento Elemento a renderizar.
     * @return string
     */
    private function render_chip_list( $elemento ) {
        $data_chip     = $elemento['data'] ?? array();
        $items_chips   = $this->editorial_parse_items( $data_chip, 'items' );
        $mono_activado = ! empty( $data_chip['mono'] );

        if ( empty( $items_chips ) ) {
            return '';
        }

        $html_chips  = $this->editorial_assets_once();
        $clase_mono  = $mono_activado ? ' vbp-chips__item--mono' : '';
        $html_chips .= '<div class="vbp-editorial vbp-chips">';
        foreach ( $items_chips as $indice_chip => $texto_chip ) {
            $html_chips .= '<span class="vbp-chips__item' . $clase_mono . '"' . $this->get_editor_contenteditable_path_attr( 'items.' . $indice_chip ) . '>' . esc_html( (string) $texto_chip ) . '</span>';
        }
        $html_chips .= '</div>';
        return $html_chips;
    }

    /**
     * Renderiza una lista de principios / manifiesto con titulo y items
     * en N columnas. Cada item: titulo bold + descripcion.
     *
     * @param array $elemento Elemento a renderizar.
     * @return string
     */
    private function render_principles_list( $elemento ) {
        $data            = $elemento['data'] ?? array();
        $titulo_princ    = (string) ( $data['titulo'] ?? '' );
        $columnas_princ  = max( 1, min( 4, (int) ( $data['columnas'] ?? 2 ) ) );
        $color_fondo     = trim( (string) ( $data['color_fondo'] ?? '' ) );
        $color_texto     = trim( (string) ( $data['color_texto'] ?? '' ) );

        $items_decoded = $this->editorial_parse_items( $data, 'items' );

        $html_princ  = $this->editorial_assets_once();

        $clases_princ  = 'vbp-editorial vbp-princ vbp-reveal' . $this->editorial_tema_class( $data );
        $estilo_princ  = '';
        if ( $color_fondo !== '' ) {
            $clases_princ .= ' vbp-princ--filled';
            $estilo_princ .= 'background:' . esc_attr( $color_fondo ) . ';';
        }
        if ( $color_texto !== '' ) {
            $estilo_princ .= 'color:' . esc_attr( $color_texto ) . ';';
        }
        $attr_estilo = $estilo_princ !== '' ? ' style="' . $estilo_princ . '"' : '';

        $html_princ .= '<section class="' . esc_attr( $clases_princ ) . '"' . $attr_estilo . '>';
        if ( $titulo_princ !== '' ) {
            $html_princ .= '<h2 class="vbp-princ__title"' . $this->get_editor_contenteditable_attr( 'titulo' ) . '>' . wp_kses_post( $titulo_princ ) . '</h2>';
        }

        if ( ! empty( $items_decoded ) ) {
            $html_princ .= sprintf(
                '<ul class="vbp-princ__list" style="grid-template-columns:repeat(%d,minmax(0,1fr));">',
                $columnas_princ
            );
            foreach ( $items_decoded as $indice_princ => $item_princ ) {
                $titulo_item      = $item_princ['titulo']      ?? '';
                $descripcion_item = $item_princ['descripcion'] ?? '';
                $html_princ      .= '<li class="vbp-princ__item">';
                if ( $titulo_item !== '' ) {
                    $html_princ .= '<strong' . $this->get_editor_contenteditable_path_attr( 'items.' . $indice_princ . '.titulo' ) . '>' . esc_html( (string) $titulo_item ) . '</strong>';
                }
                if ( $descripcion_item !== '' ) {
                    $html_princ .= '<span class="vbp-princ__item-desc"' . $this->get_editor_contenteditable_path_attr( 'items.' . $indice_princ . '.descripcion' ) . '>' . wp_kses_post( (string) $descripcion_item ) . '</span>';
                }
                $html_princ .= '</li>';
            }
            $html_princ .= '</ul>';
        }

        $html_princ .= '</section>';
        return $html_princ;
    }

    /**
     * Renderiza el masthead editorial: cabecera con tagline + badge y doble
     * rule inferior. Pensado como primer bloque de una landing editorial.
     *
     * @param array $elemento Elemento a renderizar.
     * @return string
     */
    private function render_masthead_editorial( $elemento ) {
        $data    = $elemento['data'] ?? array();
        $tagline = trim( (string) ( $data['tagline'] ?? '' ) );
        $badge   = trim( (string) ( $data['badge']   ?? '' ) );
        $enlaces_masthead = $this->editorial_parse_items( $data, 'links' );

        if ( $tagline === '' && $badge === '' && empty( $enlaces_masthead ) ) {
            return '';
        }

        $html_masthead  = $this->editorial_assets_once();
        $html_masthead .= '<header class="vbp-editorial vbp-masthead' . $this->editorial_tema_class( $data ) . '">';
        if ( $tagline !== '' ) {
            $html_masthead .= '<span class="vbp-masthead__tagline"' . $this->get_editor_contenteditable_attr( 'tagline' ) . '>' . esc_html( $tagline ) . '</span>';
        }
        if ( ! empty( $enlaces_masthead ) ) {
            $html_masthead .= '<nav class="vbp-masthead__links">';
            foreach ( $enlaces_masthead as $enlace_masthead ) {
                $texto_enlace = trim( (string) ( $enlace_masthead['texto'] ?? '' ) );
                $url_enlace   = trim( (string) ( $enlace_masthead['url']   ?? '' ) );
                if ( $texto_enlace === '' ) {
                    continue;
                }
                $nueva_pestana = ! empty( $enlace_masthead['nueva'] );
                $atributos_enlace = 'href="' . esc_url( $url_enlace !== '' ? $url_enlace : '#' ) . '"';
                if ( $nueva_pestana ) {
                    $atributos_enlace .= ' target="_blank" rel="noopener"';
                }
                $html_masthead .= '<a ' . $atributos_enlace . '>' . esc_html( $texto_enlace ) . '</a>';
            }
            $html_masthead .= '</nav>';
        }
        if ( $badge !== '' ) {
            $html_masthead .= '<span class="vbp-masthead__badge"' . $this->get_editor_contenteditable_attr( 'badge' ) . '>' . esc_html( $badge ) . '</span>';
        }
        $html_masthead .= '</header>';
        return $html_masthead;
    }

    /**
     * Helper que genera el HTML de un boton editorial (light o red).
     * Se usa en cta_strip y hosting_dark.
     *
     * @param string $texto  Texto visible del boton.
     * @param string $url    URL destino.
     * @param string $estilo 'light' (borde currentColor) o 'red' (relleno rojo).
     * @param bool   $nueva  Si se abre en nueva pestana.
     * @return string
     */
    private function render_editorial_button( $texto, $url, $estilo = 'light', $nueva = false ) {
        $texto = trim( (string) $texto );
        if ( $texto === '' ) {
            return '';
        }
        $clase_boton = 'vbp-ed-btn vbp-ed-btn--' . ( $estilo === 'red' ? 'red' : 'light' );
        $atributos   = 'class="' . esc_attr( $clase_boton ) . '"';
        if ( $url !== '' ) {
            $atributos .= ' href="' . esc_url( $url ) . '"';
        } else {
            $atributos .= ' href="#"';
        }
        if ( $nueva ) {
            $atributos .= ' target="_blank" rel="noopener"';
        }
        return '<a ' . $atributos . '>' . esc_html( $texto ) . '</a>';
    }

    /**
     * Renderiza una tira horizontal de CTA con texto mono + hasta 2 botones.
     * Normalmente va sobre fondo ink/paper pero acepta cualquier combinacion.
     *
     * @param array $elemento Elemento a renderizar.
     * @return string
     */
    private function render_cta_strip( $elemento ) {
        $data        = $elemento['data'] ?? array();
        $texto_cta   = trim( (string) ( $data['texto'] ?? '' ) );
        $color_fondo = (string) ( $data['color_fondo'] ?? '#111008' );
        $color_texto = (string) ( $data['color_texto'] ?? '#F2EDE3' );

        $html_boton_1 = $this->render_editorial_button(
            $data['boton_1_texto'] ?? '',
            $data['boton_1_url']   ?? '',
            $data['boton_1_estilo'] ?? 'light',
            ! empty( $data['boton_1_nueva'] )
        );
        $html_boton_2 = $this->render_editorial_button(
            $data['boton_2_texto'] ?? '',
            $data['boton_2_url']   ?? '',
            $data['boton_2_estilo'] ?? 'red',
            ! empty( $data['boton_2_nueva'] )
        );

        if ( $texto_cta === '' && $html_boton_1 === '' && $html_boton_2 === '' ) {
            return '';
        }

        $html_cta  = $this->editorial_assets_once();
        $html_cta .= sprintf(
            '<div class="vbp-editorial vbp-cta-strip" style="background:%s;color:%s;">',
            esc_attr( $color_fondo ),
            esc_attr( $color_texto )
        );
        if ( $texto_cta !== '' ) {
            $html_cta .= '<p class="vbp-cta-strip__text"' . $this->get_editor_contenteditable_attr( 'texto' ) . '>' . esc_html( $texto_cta ) . '</p>';
        }
        if ( $html_boton_1 !== '' || $html_boton_2 !== '' ) {
            $html_cta .= '<div class="vbp-cta-strip__buttons">' . $html_boton_1 . $html_boton_2 . '</div>';
        }
        $html_cta .= '</div>';
        return $html_cta;
    }

    /**
     * Renderiza una seccion why-style: aside sticky con titulo (pre + em rojo)
     * y lista de tags, junto a un body con HTML y una quote destacada.
     *
     * @param array $elemento Elemento a renderizar.
     * @return string
     */
    private function render_editorial_split_quote( $elemento ) {
        $data        = $elemento['data'] ?? array();
        $titulo_pre  = trim( (string) ( $data['aside_titulo_pre'] ?? '' ) );
        $titulo_em   = trim( (string) ( $data['aside_titulo_em']  ?? '' ) );
        $body_html   = trim( (string) ( $data['body_html']         ?? '' ) );
        $quote_html  = trim( (string) ( $data['quote_html']        ?? '' ) );
        $tags_lista  = $this->editorial_parse_items( $data, 'aside_tags' );

        $html_why  = $this->editorial_assets_once();
        $html_why .= '<section class="vbp-editorial vbp-why' . $this->editorial_tema_class( $data ) . '">';
        $html_why .= '<div class="vbp-why__grid">';

        $html_why .= '<aside class="vbp-why__aside vbp-reveal">';
        if ( $titulo_pre !== '' || $titulo_em !== '' ) {
            $html_why .= '<h2 class="vbp-why__title">';
            if ( $titulo_pre !== '' ) {
                $html_why .= '<span' . $this->get_editor_contenteditable_attr( 'aside_titulo_pre' ) . '>' . esc_html( $titulo_pre ) . '</span><br>';
            }
            if ( $titulo_em !== '' ) {
                $html_why .= '<em' . $this->get_editor_contenteditable_attr( 'aside_titulo_em' ) . '>' . esc_html( $titulo_em ) . '</em>';
            }
            $html_why .= '</h2>';
        }
        if ( ! empty( $tags_lista ) ) {
            $html_why .= '<div class="vbp-why__tags">';
            foreach ( $tags_lista as $indice_tag_why => $tag_texto ) {
                $html_why .= '<span class="vbp-why__tag"' . $this->get_editor_contenteditable_path_attr( 'aside_tags.' . $indice_tag_why ) . '>' . esc_html( (string) $tag_texto ) . '</span>';
            }
            $html_why .= '</div>';
        }
        $html_why .= '</aside>';

        $html_why .= '<div class="vbp-why__body vbp-reveal">';
        if ( $body_html !== '' ) {
            $html_why .= '<div class="vbp-why__body-inner"' . $this->get_editor_contenteditable_attr( 'body_html' ) . '>' . wp_kses_post( $body_html ) . '</div>';
        }
        if ( $quote_html !== '' ) {
            $html_why .= '<blockquote class="vbp-why__quote"' . $this->get_editor_contenteditable_attr( 'quote_html' ) . '>' . wp_kses_post( $quote_html ) . '</blockquote>';
        }
        $html_why .= '</div>';

        $html_why .= '</div></section>';
        return $html_why;
    }

    /**
     * Renderiza la relacion entre dos proyectos en 3 columnas (izq / conector
     * central en mono / der). El conector admite saltos de linea.
     *
     * @param array $elemento Elemento a renderizar.
     * @return string
     */
    private function render_relation_split( $elemento ) {
        $data        = $elemento['data'] ?? array();
        $izq_titulo  = trim( (string) ( $data['izq_titulo'] ?? '' ) );
        $izq_html    = trim( (string) ( $data['izq_html']   ?? '' ) );
        $der_titulo  = trim( (string) ( $data['der_titulo'] ?? '' ) );
        $der_html    = trim( (string) ( $data['der_html']   ?? '' ) );
        $conector    = trim( (string) ( $data['conector']   ?? '' ) );

        $html_rel  = $this->editorial_assets_once();
        $html_rel .= '<section class="vbp-editorial vbp-relation vbp-reveal' . $this->editorial_tema_class( $data ) . '">';

        $html_rel .= '<div class="vbp-relation__col">';
        if ( $izq_titulo !== '' ) {
            $html_rel .= '<h3 class="vbp-relation__title"' . $this->get_editor_contenteditable_attr( 'izq_titulo' ) . '>' . esc_html( $izq_titulo ) . '</h3>';
        }
        if ( $izq_html !== '' ) {
            $html_rel .= '<div class="vbp-relation__col-body"' . $this->get_editor_contenteditable_attr( 'izq_html' ) . '>' . wp_kses_post( $izq_html ) . '</div>';
        }
        $html_rel .= '</div>';

        $html_rel .= '<div class="vbp-relation__connector"' . $this->get_editor_contenteditable_attr( 'conector' ) . '>';
        if ( $conector !== '' ) {
            $html_rel .= nl2br( esc_html( $conector ) );
        }
        $html_rel .= '</div>';

        $html_rel .= '<div class="vbp-relation__col">';
        if ( $der_titulo !== '' ) {
            $html_rel .= '<h3 class="vbp-relation__title"' . $this->get_editor_contenteditable_attr( 'der_titulo' ) . '>' . esc_html( $der_titulo ) . '</h3>';
        }
        if ( $der_html !== '' ) {
            $html_rel .= '<div class="vbp-relation__col-body"' . $this->get_editor_contenteditable_attr( 'der_html' ) . '>' . wp_kses_post( $der_html ) . '</div>';
        }
        $html_rel .= '</div>';

        $html_rel .= '</section>';
        return $html_rel;
    }

    /**
     * Renderiza una seccion oscura con etiqueta + titulo (pre + em rojo) +
     * descripcion + lista numerada de pasos + 2 botones. Util para cerrar
     * una landing con instrucciones de autohospedaje o siguiente paso.
     *
     * @param array $elemento Elemento a renderizar.
     * @return string
     */
    private function render_hosting_dark( $elemento ) {
        $data         = $elemento['data'] ?? array();
        $label        = trim( (string) ( $data['label']       ?? '' ) );
        $titulo_pre   = trim( (string) ( $data['titulo_pre']  ?? '' ) );
        $titulo_em    = trim( (string) ( $data['titulo_em']   ?? '' ) );
        $descripcion  = trim( (string) ( $data['descripcion'] ?? '' ) );
        $pasos_lista  = $this->editorial_parse_items( $data, 'pasos' );

        $html_boton_1 = $this->render_editorial_button(
            $data['boton_1_texto'] ?? '',
            $data['boton_1_url']   ?? '',
            $data['boton_1_estilo'] ?? 'light',
            ! empty( $data['boton_1_nueva'] )
        );
        $html_boton_2 = $this->render_editorial_button(
            $data['boton_2_texto'] ?? '',
            $data['boton_2_url']   ?? '',
            $data['boton_2_estilo'] ?? 'red',
            ! empty( $data['boton_2_nueva'] )
        );

        $html_host  = $this->editorial_assets_once();
        $html_host .= '<section class="vbp-editorial vbp-hosting">';
        $html_host .= '<div class="vbp-hosting__inner">';

        $html_host .= '<div class="vbp-hosting__intro vbp-reveal">';
        if ( $label !== '' ) {
            $html_host .= '<div class="vbp-hosting__label"' . $this->get_editor_contenteditable_attr( 'label' ) . '>' . esc_html( $label ) . '</div>';
        }
        if ( $titulo_pre !== '' || $titulo_em !== '' ) {
            $html_host .= '<h2 class="vbp-hosting__title">';
            if ( $titulo_pre !== '' ) {
                $html_host .= '<span' . $this->get_editor_contenteditable_attr( 'titulo_pre' ) . '>' . esc_html( $titulo_pre ) . '</span><br>';
            }
            if ( $titulo_em !== '' ) {
                $html_host .= '<em' . $this->get_editor_contenteditable_attr( 'titulo_em' ) . '>' . esc_html( $titulo_em ) . '</em>';
            }
            $html_host .= '</h2>';
        }
        if ( $descripcion !== '' ) {
            $html_host .= '<p class="vbp-hosting__desc"' . $this->get_editor_contenteditable_attr( 'descripcion' ) . '>' . wp_kses_post( $descripcion ) . '</p>';
        }
        if ( $html_boton_1 !== '' || $html_boton_2 !== '' ) {
            $html_host .= '<div class="vbp-hosting__cta">' . $html_boton_1 . $html_boton_2 . '</div>';
        }
        $html_host .= '</div>';

        if ( ! empty( $pasos_lista ) ) {
            $html_host .= '<ol class="vbp-hosting__steps vbp-reveal">';
            foreach ( $pasos_lista as $indice => $paso_item ) {
                $numero_paso      = sprintf( '%02d', $indice + 1 );
                $titulo_paso      = trim( (string) ( $paso_item['titulo']      ?? '' ) );
                $descripcion_paso = trim( (string) ( $paso_item['descripcion'] ?? '' ) );
                $html_host .= '<li class="vbp-hosting__step">';
                $html_host .= '<span class="vbp-hosting__step-num">' . esc_html( $numero_paso ) . ' —</span>';
                $html_host .= '<span class="vbp-hosting__step-text">';
                if ( $titulo_paso !== '' ) {
                    $html_host .= '<strong' . $this->get_editor_contenteditable_path_attr( 'pasos.' . $indice . '.titulo' ) . '>' . esc_html( $titulo_paso ) . '</strong> ';
                }
                if ( $descripcion_paso !== '' ) {
                    $html_host .= '<span' . $this->get_editor_contenteditable_path_attr( 'pasos.' . $indice . '.descripcion' ) . '>' . wp_kses_post( $descripcion_paso ) . '</span>';
                }
                $html_host .= '</span></li>';
            }
            $html_host .= '</ol>';
        }

        $html_host .= '</div></section>';
        return $html_host;
    }

    /**
     * Renderiza el footer editorial con logo tripartido (pre + em coloreado +
     * post), lista de enlaces mono y texto de licencia a la derecha.
     *
     * @param array $elemento Elemento a renderizar.
     * @return string
     */
    private function render_footer_editorial( $elemento ) {
        $data       = $elemento['data'] ?? array();
        $logo_pre   = trim( (string) ( $data['logo_pre']  ?? '' ) );
        $logo_em    = trim( (string) ( $data['logo_em']   ?? '' ) );
        $logo_post  = trim( (string) ( $data['logo_post'] ?? '' ) );
        $license    = trim( (string) ( $data['license']   ?? '' ) );
        $links_list = $this->editorial_parse_items( $data, 'links' );

        $html_foot  = $this->editorial_assets_once();
        $html_foot .= '<footer class="vbp-editorial vbp-foot-edit' . $this->editorial_tema_class( $data ) . '">';

        if ( $logo_pre !== '' || $logo_em !== '' || $logo_post !== '' ) {
            $html_foot .= '<span class="vbp-foot-edit__logo">';
            if ( $logo_pre !== '' ) {
                $html_foot .= '<span' . $this->get_editor_contenteditable_attr( 'logo_pre' ) . '>' . esc_html( $logo_pre ) . '</span>';
            }
            if ( $logo_em !== '' ) {
                $html_foot .= ' <span class="vbp-foot-edit__logo-em"' . $this->get_editor_contenteditable_attr( 'logo_em' ) . '>' . esc_html( $logo_em ) . '</span> ';
            }
            if ( $logo_post !== '' ) {
                $html_foot .= '<span' . $this->get_editor_contenteditable_attr( 'logo_post' ) . '>' . esc_html( $logo_post ) . '</span>';
            }
            $html_foot .= '</span>';
        }

        if ( ! empty( $links_list ) ) {
            $html_foot .= '<nav class="vbp-foot-edit__links">';
            foreach ( $links_list as $indice_link => $link_item ) {
                $texto_link = trim( (string) ( $link_item['texto'] ?? '' ) );
                $url_link   = trim( (string) ( $link_item['url']   ?? '' ) );
                if ( $texto_link === '' ) {
                    continue;
                }
                $abrir_nueva = ! empty( $link_item['nueva'] );
                $rel_attr    = $abrir_nueva ? ' target="_blank" rel="noopener"' : '';
                $url_segura  = $url_link !== '' ? esc_url( $url_link ) : '#';
                $attr_edicion = $this->get_editor_link_path_attr( 'links.' . $indice_link . '.texto' );
                $html_foot  .= '<a href="' . $url_segura . '"' . $rel_attr . $attr_edicion . '>' . esc_html( $texto_link ) . '</a>';
            }
            $html_foot .= '</nav>';
        }

        if ( $license !== '' ) {
            $html_foot .= '<span class="vbp-foot-edit__license"' . $this->get_editor_contenteditable_attr( 'license' ) . '>' . esc_html( $license ) . '</span>';
        }

        $html_foot .= '</footer>';
        return $html_foot;
    }

    /**
     * Renderiza Team
     */
    private function render_team( $elemento ) {
        $data        = $elemento['data'] ?? array();
        $estilos     = $elemento['styles'] ?? array();
        $estilos_css = $this->generar_estilos_elemento( $estilos );

        $titulo = $data['titulo'] ?? '';
        $items  = $data['items'] ?? array();

        $html = '<section class="vbp-team" style="' . esc_attr( $estilos_css ) . '">';

        if ( $titulo ) {
            $html .= '<h2 class="vbp-team__title"' . $this->get_editor_contenteditable_attr( 'titulo' ) . '>' . wp_kses_post( $titulo ) . '</h2>';
        }

        if ( ! empty( $items ) ) {
            $html .= '<div class="vbp-team__grid">';

            foreach ( $items as $index => $item ) {
                $html .= '<div class="vbp-team-member">';

                if ( ! empty( $item['foto'] ) ) {
                    $html .= '<img src="' . esc_url( $item['foto'] ) . '" alt="' . esc_attr( $item['nombre'] ?? '' ) . '" class="vbp-team-member__photo">';
                } else {
                    $inicial = ! empty( $item['nombre'] ) ? strtoupper( substr( $item['nombre'], 0, 1 ) ) : 'M';
                    $html   .= '<div class="vbp-team-member__avatar">' . esc_html( $inicial ) . '</div>';
                }

                $html .= '<h3 class="vbp-team-member__name"' . $this->get_editor_contenteditable_path_attr( 'items.' . $index . '.nombre' ) . '>' . esc_html( $item['nombre'] ?? '' ) . '</h3>';

                if ( ! empty( $item['cargo'] ) ) {
                    $html .= '<p class="vbp-team-member__role"' . $this->get_editor_contenteditable_path_attr( 'items.' . $index . '.cargo' ) . '>' . esc_html( $item['cargo'] ) . '</p>';
                }

                if ( ! empty( $item['bio'] ) ) {
                    $html .= '<p class="vbp-team-member__bio"' . $this->get_editor_contenteditable_path_attr( 'items.' . $index . '.bio' ) . '>' . wp_kses_post( $item['bio'] ) . '</p>';
                }

                $html .= '</div>';
            }

            $html .= '</div>';
        }

        $html .= '</section>';

        return $html;
    }

    /**
     * Renderiza Stats
     */
    private function render_stats( $elemento ) {
        $data        = $elemento['data'] ?? array();
        $estilos     = $elemento['styles'] ?? array();
        $estilos_css = $this->generar_estilos_elemento( $estilos );

        // Soportar ambos nombres de campo: items y stats
        $items = $data['items'] ?? $data['stats'] ?? array();
        $titulo = $data['titulo'] ?? $data['title'] ?? '';

        $html = '<section class="vbp-stats" style="' . esc_attr( $estilos_css ) . '">';

        if ( $titulo ) {
            $html .= '<h2 class="vbp-stats__title"' . $this->get_editor_contenteditable_attr( 'titulo' ) . '>' . wp_kses_post( $titulo ) . '</h2>';
        }

        if ( ! empty( $items ) ) {
            $html .= '<div class="vbp-stats__grid">';

            foreach ( $items as $index => $item ) {
                $html .= '<div class="vbp-stat-item">';
                if ( ! empty( $item['icono'] ) ) {
                    $icono_bruto = (string) $item['icono'];
                    // Detecta si el icono es una clase FontAwesome (slug ASCII
                    // tipo "users" o "fa-users") o un emoji/caracter unicode.
                    // Sin este check, un emoji se inyecta como clase CSS y
                    // FontAwesome intenta resolverlo como glifo inexistente.
                    $es_clase_font_awesome = (bool) preg_match( '/^[a-z0-9-]+$/', $icono_bruto );
                    if ( $es_clase_font_awesome ) {
                        $slug_fa = 0 === strpos( $icono_bruto, 'fa-' ) ? $icono_bruto : 'fa-' . $icono_bruto;
                        $html   .= '<div class="vbp-stat-item__icon"><i class="fas ' . esc_attr( $slug_fa ) . '" aria-hidden="true"></i></div>';
                    } else {
                        $html .= '<div class="vbp-stat-item__icon vbp-stat-item__icon--emoji" aria-hidden="true" style="font-size:40px;line-height:1;">' . esc_html( $icono_bruto ) . '</div>';
                    }
                }
                $html .= '<span class="vbp-stat-item__number"' . $this->get_editor_contenteditable_path_attr( 'items.' . $index . '.numero' ) . '>' . esc_html( $item['numero'] ?? $item['number'] ?? '0' ) . '</span>';
                $html .= '<span class="vbp-stat-item__label"' . $this->get_editor_contenteditable_path_attr( 'items.' . $index . '.label' ) . '>' . esc_html( $item['label'] ?? $item['texto'] ?? '' ) . '</span>';
                $html .= '</div>';
            }

            $html .= '</div>';
        }

        $html .= '</section>';

        return $html;
    }

    /**
     * Renderiza Gallery
     */
    private function render_gallery( $elemento ) {
        $data        = $elemento['data'] ?? array();
        $estilos     = $elemento['styles'] ?? array();
        $estilos_css = $this->generar_estilos_elemento( $estilos );

        $titulo = $data['titulo'] ?? '';
        $items  = $data['items'] ?? array();

        $html = '<section class="vbp-gallery" style="' . esc_attr( $estilos_css ) . '">';

        if ( $titulo ) {
            $html .= '<h2 class="vbp-gallery__title"' . $this->get_editor_contenteditable_attr( 'titulo' ) . '>' . wp_kses_post( $titulo ) . '</h2>';
        }

        if ( ! empty( $items ) ) {
            $html .= '<div class="vbp-gallery__grid">';

            foreach ( $items as $index => $item ) {
                $html .= '<figure class="vbp-gallery-item">';
                $html .= '<img src="' . esc_url( $item['src'] ?? '' ) . '" alt="' . esc_attr( $item['alt'] ?? '' ) . '" loading="lazy">';
                $html .= '</figure>';
            }

            $html .= '</div>';
        }

        $html .= '</section>';

        return $html;
    }

    /**
     * Renderiza Blog
     */
    private function render_blog( $elemento ) {
        $data        = $elemento['data'] ?? array();
        $estilos     = $elemento['styles'] ?? array();
        $estilos_css = $this->generar_estilos_elemento( $estilos );

        $titulo      = $data['titulo'] ?? '';
        $subtitulo   = $data['subtitulo'] ?? '';
        $categoria   = $data['categoria'] ?? '';
        $cantidad    = absint( $data['cantidad'] ?? 6 );
        $columnas    = absint( $data['columnas'] ?? 3 );
        $ordenar_por = $data['ordenar_por'] ?? 'date';
        $orden       = $data['orden'] ?? 'DESC';
        $mostrar_extracto = $data['mostrar_extracto'] ?? true;
        $mostrar_autor    = $data['mostrar_autor'] ?? true;
        $mostrar_fecha    = $data['mostrar_fecha'] ?? true;

        // ID único para carga dinámica
        $blog_id = 'vbp-blog-' . substr( md5( wp_json_encode( $elemento ) ), 0, 8 );

        // Argumentos de consulta
        $args = array(
            'post_type'      => 'post',
            'posts_per_page' => $cantidad,
            'orderby'        => $ordenar_por,
            'order'          => $orden,
            'post_status'    => 'publish',
        );

        if ( ! empty( $categoria ) ) {
            if ( is_numeric( $categoria ) ) {
                $args['cat'] = $categoria;
            } else {
                $args['category_name'] = $categoria;
            }
        }

        $query = new WP_Query( $args );

        $html = '<section id="' . esc_attr( $blog_id ) . '" class="vbp-blog vbp-blog--cols-' . esc_attr( $columnas ) . '" style="' . esc_attr( $estilos_css ) . '">';

        if ( $titulo ) {
            $html .= '<h2 class="vbp-blog__title"' . $this->get_editor_contenteditable_attr( 'titulo' ) . '>' . wp_kses_post( $titulo ) . '</h2>';
        }

        if ( $subtitulo ) {
            $html .= '<p class="vbp-blog__subtitle"' . $this->get_editor_contenteditable_attr( 'subtitulo' ) . '>' . wp_kses_post( $subtitulo ) . '</p>';
        }

        $html .= '<div class="vbp-blog__grid">';

        if ( $query->have_posts() ) {
            while ( $query->have_posts() ) {
                $query->the_post();

                $thumbnail = '';
                if ( has_post_thumbnail() ) {
                    $thumbnail = get_the_post_thumbnail_url( get_the_ID(), 'medium_large' );
                }

                $html .= '<article class="vbp-blog-card">';

                if ( $thumbnail ) {
                    $html .= '<a href="' . esc_url( get_permalink() ) . '" class="vbp-blog-card__image">';
                    $html .= '<img src="' . esc_url( $thumbnail ) . '" alt="' . esc_attr( get_the_title() ) . '" loading="lazy">';
                    $html .= '</a>';
                }

                $html .= '<div class="vbp-blog-card__content">';

                // Categorías
                $categories = get_the_category();
                if ( ! empty( $categories ) ) {
                    $html .= '<div class="vbp-blog-card__categories">';
                    foreach ( array_slice( $categories, 0, 2 ) as $cat ) {
                        $html .= '<a href="' . esc_url( get_category_link( $cat->term_id ) ) . '" class="vbp-blog-card__category">' . esc_html( $cat->name ) . '</a>';
                    }
                    $html .= '</div>';
                }

                $html .= '<h3 class="vbp-blog-card__title">';
                $html .= '<a href="' . esc_url( get_permalink() ) . '">' . esc_html( get_the_title() ) . '</a>';
                $html .= '</h3>';

                if ( $mostrar_extracto ) {
                    $html .= '<p class="vbp-blog-card__excerpt">' . esc_html( wp_trim_words( get_the_excerpt(), 20, '...' ) ) . '</p>';
                }

                // Meta
                if ( $mostrar_autor || $mostrar_fecha ) {
                    $html .= '<div class="vbp-blog-card__meta">';

                    if ( $mostrar_autor ) {
                        $html .= '<span class="vbp-blog-card__author">';
                        $html .= '<img src="' . esc_url( get_avatar_url( get_the_author_meta( 'ID' ), array( 'size' => 32 ) ) ) . '" alt="" class="vbp-blog-card__avatar">';
                        $html .= '<span>' . esc_html( get_the_author() ) . '</span>';
                        $html .= '</span>';
                    }

                    if ( $mostrar_fecha ) {
                        $html .= '<span class="vbp-blog-card__date">' . esc_html( get_the_date() ) . '</span>';
                    }

                    $html .= '</div>';
                }

                $html .= '</div>'; // .vbp-blog-card__content
                $html .= '</article>';
            }
            wp_reset_postdata();
        } else {
            $html .= '<p class="vbp-blog__empty">' . esc_html__( 'No se encontraron artículos.', FLAVOR_PLATFORM_TEXT_DOMAIN ) . '</p>';
        }

        $html .= '</div>'; // .vbp-blog__grid
        $html .= '</section>';

        return $html;
    }

    /**
     * Renderiza Contact
     */
    private function render_contact( $elemento ) {
        global $post;

        $data        = $elemento['data'] ?? array();
        $estilos     = $elemento['styles'] ?? array();
        $estilos_css = $this->generar_estilos_elemento( $estilos );

        $titulo        = $data['titulo'] ?? '';
        $subtitulo     = $data['subtitulo'] ?? '';
        $boton_texto   = $data['boton_texto'] ?? __( 'Enviar mensaje', FLAVOR_PLATFORM_TEXT_DOMAIN );
        $mensaje_exito = $data['mensaje_exito'] ?? __( '¡Mensaje enviado correctamente!', FLAVOR_PLATFORM_TEXT_DOMAIN );

        // ID único para el formulario
        $form_id = 'vbp-contact-' . substr( md5( wp_json_encode( $elemento ) ), 0, 8 );
        $post_id = $post ? $post->ID : 0;

        $html = '<section class="vbp-contact" style="' . esc_attr( $estilos_css ) . '">';

        if ( $titulo ) {
            $html .= '<h2 class="vbp-contact__title"' . $this->get_editor_contenteditable_attr( 'titulo' ) . '>' . wp_kses_post( $titulo ) . '</h2>';
        }

        if ( $subtitulo ) {
            $html .= '<p class="vbp-contact__subtitle"' . $this->get_editor_contenteditable_attr( 'subtitulo' ) . '>' . wp_kses_post( $subtitulo ) . '</p>';
        }

        // Formulario funcional con AJAX
        $html .= '<form class="vbp-contact__form vbp-ajax-form" id="' . esc_attr( $form_id ) . '" data-success-message="' . esc_attr( $mensaje_exito ) . '">';

        // Campos ocultos
        $html .= '<input type="hidden" name="action" value="vbp_submit_form">';
        $html .= '<input type="hidden" name="form_id" value="' . esc_attr( $form_id ) . '">';
        $html .= '<input type="hidden" name="post_id" value="' . esc_attr( $post_id ) . '">';

        // Honeypot (campo oculto para bots)
        $html .= '<div style="position:absolute;left:-9999px;" aria-hidden="true">';
        $html .= '<input type="text" name="website_url" tabindex="-1" autocomplete="off">';
        $html .= '</div>';

        // Campos visibles
        $html .= '<div class="vbp-contact__field">';
        $html .= '<label for="' . esc_attr( $form_id ) . '-name">' . esc_html__( 'Nombre', FLAVOR_PLATFORM_TEXT_DOMAIN ) . ' <span class="required">*</span></label>';
        $html .= '<input type="text" id="' . esc_attr( $form_id ) . '-name" name="name" required>';
        $html .= '<span class="vbp-field-error"></span>';
        $html .= '</div>';

        $html .= '<div class="vbp-contact__field">';
        $html .= '<label for="' . esc_attr( $form_id ) . '-email">' . esc_html__( 'Email', FLAVOR_PLATFORM_TEXT_DOMAIN ) . ' <span class="required">*</span></label>';
        $html .= '<input type="email" id="' . esc_attr( $form_id ) . '-email" name="email" required>';
        $html .= '<span class="vbp-field-error"></span>';
        $html .= '</div>';

        $html .= '<div class="vbp-contact__field">';
        $html .= '<label for="' . esc_attr( $form_id ) . '-message">' . esc_html__( 'Mensaje', FLAVOR_PLATFORM_TEXT_DOMAIN ) . ' <span class="required">*</span></label>';
        $html .= '<textarea id="' . esc_attr( $form_id ) . '-message" name="message" rows="4" required></textarea>';
        $html .= '<span class="vbp-field-error"></span>';
        $html .= '</div>';

        // Estado de envío
        $html .= '<div class="vbp-form-status" aria-live="polite"></div>';

        // Botón de envío
        $html .= '<button type="submit" class="vbp-contact__submit">';
        $html .= '<span class="vbp-btn-text"' . $this->get_editor_contenteditable_attr( 'boton_texto' ) . '>' . esc_html( $boton_texto ) . '</span>';
        $html .= '<span class="vbp-btn-loading" style="display:none;">';
        $html .= '<svg class="vbp-spinner" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">';
        $html .= '<path d="M12 2v4M12 18v4M4.93 4.93l2.83 2.83M16.24 16.24l2.83 2.83M2 12h4M18 12h4M4.93 19.07l2.83-2.83M16.24 7.76l2.83-2.83"/>';
        $html .= '</svg>';
        $html .= esc_html__( 'Enviando...', FLAVOR_PLATFORM_TEXT_DOMAIN );
        $html .= '</span>';
        $html .= '</button>';

        $html .= '</form>';

        // Mensaje de éxito (oculto por defecto)
        $html .= '<div class="vbp-contact__success" style="display:none;">';
        $html .= '<svg width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">';
        $html .= '<path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/>';
        $html .= '<polyline points="22 4 12 14.01 9 11.01"/>';
        $html .= '</svg>';
        $html .= '<h3>' . esc_html__( '¡Gracias por tu mensaje!', FLAVOR_PLATFORM_TEXT_DOMAIN ) . '</h3>';
        $html .= '<p>' . esc_html( $mensaje_exito ) . '</p>';
        $html .= '</div>';

        $html .= '</section>';

        return $html;
    }

    /**
     * Renderiza Video
     */
    private function render_video_embed( $elemento ) {
        $data        = $elemento['data'] ?? array();
        $estilos     = $elemento['styles'] ?? array();
        $estilos_css = $this->generar_estilos_elemento( $estilos );

        $url = $data['video_url'] ?? '';

        if ( ! $url ) {
            if ( $this->is_editor_context() ) {
                return '<div class="vbp-video vbp-video--empty" style="' . esc_attr( $estilos_css ) . '"><div class="vbp-video__placeholder">Añade un video desde la biblioteca o pega una URL</div></div>';
            }
            return '<div class="vbp-video vbp-video--empty" style="' . esc_attr( $estilos_css ) . '">No video URL</div>';
        }

        // Detectar tipo de video
        $embed = $this->get_video_embed( $url );

        $html = '<div class="vbp-video" style="' . esc_attr( $estilos_css ) . '">';
        $html .= '<div class="vbp-video__wrapper">' . $embed . '</div>';
        $html .= '</div>';

        return $html;
    }

    /**
     * Renderiza Video Section
     */
    private function render_video_section( $elemento ) {
        $data        = $elemento['data'] ?? array();
        $estilos     = $elemento['styles'] ?? array();
        $estilos_css = $this->generar_estilos_elemento( $estilos );

        $titulo      = $data['titulo'] ?? '';
        $descripcion = $data['descripcion'] ?? '';
        $url         = $data['video_url'] ?? '';

        $html = '<section class="vbp-video-section" style="' . esc_attr( $estilos_css ) . '">';

        if ( $titulo ) {
            $html .= '<h2 class="vbp-video-section__title"' . $this->get_editor_contenteditable_attr( 'titulo' ) . '>' . wp_kses_post( $titulo ) . '</h2>';
        }

        if ( $descripcion ) {
            $html .= '<p class="vbp-video-section__description"' . $this->get_editor_contenteditable_attr( 'descripcion' ) . '>' . wp_kses_post( $descripcion ) . '</p>';
        }

        if ( $url ) {
            $embed = $this->get_video_embed( $url );
            $html .= '<div class="vbp-video-section__video">' . $embed . '</div>';
        }

        $html .= '</section>';

        return $html;
    }

    /**
     * Obtener embed de video
     */
    private function get_video_embed( $url ) {
        // YouTube
        if ( preg_match( '/(?:youtube\.com\/watch\?v=|youtu\.be\/)([a-zA-Z0-9_-]+)/', $url, $matches ) ) {
            return '<iframe src="https://www.youtube.com/embed/' . esc_attr( $matches[1] ) . '" frameborder="0" allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture" allowfullscreen></iframe>';
        }

        // Vimeo
        if ( preg_match( '/vimeo\.com\/(\d+)/', $url, $matches ) ) {
            return '<iframe src="https://player.vimeo.com/video/' . esc_attr( $matches[1] ) . '" frameborder="0" allow="autoplay; fullscreen; picture-in-picture" allowfullscreen></iframe>';
        }

        // Video directo
        return '<video src="' . esc_url( $url ) . '" controls></video>';
    }

    /**
     * Renderiza Map
     */
    private function render_map( $elemento ) {
        $data        = $elemento['data'] ?? array();
        $estilos     = $elemento['styles'] ?? array();
        $estilos_css = $this->generar_estilos_elemento( $estilos );

        $lat  = $data['lat'] ?? '40.4168';
        $lng  = $data['lng'] ?? '-3.7038';
        $zoom = $data['zoom'] ?? 14;

        // OpenStreetMap embed
        $bbox = $this->calcular_bbox( floatval( $lat ), floatval( $lng ), intval( $zoom ) );

        $html = '<div class="vbp-map" style="' . esc_attr( $estilos_css ) . '">';
        $html .= '<iframe src="https://www.openstreetmap.org/export/embed.html?bbox=' . esc_attr( $bbox ) . '&layer=mapnik&marker=' . esc_attr( $lat ) . '%2C' . esc_attr( $lng ) . '" style="width:100%;height:400px;border:0;" allowfullscreen loading="lazy"></iframe>';
        $html .= '</div>';

        return $html;
    }

    /**
     * Alias para map
     */
    private function render_mapa( $elemento ) {
        return $this->render_map( $elemento );
    }

    /**
     * Calcular bounding box para OpenStreetMap
     */
    private function calcular_bbox( $lat, $lng, $zoom ) {
        $delta = 0.01 * ( 20 - $zoom );
        return ( $lng - $delta ) . '%2C' . ( $lat - $delta ) . '%2C' . ( $lng + $delta ) . '%2C' . ( $lat + $delta );
    }

    /**
     * Renderiza HTML personalizado
     */
    private function render_html( $elemento ) {
        $data        = $elemento['data'] ?? array();
        $estilos     = $elemento['styles'] ?? array();
        $estilos_css = $this->generar_estilos_elemento( $estilos );

        $codigo = $data['code'] ?? '';

        $html = '<div class="vbp-html" style="' . esc_attr( $estilos_css ) . '">';
        $html .= wp_kses_post( $codigo );
        $html .= '</div>';

        return $html;
    }

    /**
     * Renderiza Shortcode
     */
    private function render_shortcode( $elemento ) {
        $data        = $elemento['data'] ?? array();
        $estilos     = $elemento['styles'] ?? array();
        $estilos_css = $this->generar_estilos_elemento( $estilos );

        $shortcode = $data['shortcode'] ?? '';

        if ( ! $shortcode ) {
            return '<div class="vbp-shortcode vbp-shortcode--empty" style="' . esc_attr( $estilos_css ) . '">No shortcode</div>';
        }

        $html = '<div class="vbp-shortcode" style="' . esc_attr( $estilos_css ) . '">';
        $html .= do_shortcode( $shortcode );
        $html .= '</div>';

        return $html;
    }

    /**
     * Renderiza Icon. Acepta clases FontAwesome, slugs Material Icons, SVG
     * inline o emoji/unicode (vease render_editorial_icon).
     */
    private function render_icon( $elemento ) {
        $data        = $elemento['data'] ?? array();
        $estilos     = $elemento['styles'] ?? array();
        $estilos_css = $this->generar_estilos_elemento( $estilos );

        $icono = (string) ( $data['icon'] ?? '⭐' );
        $size  = $data['size'] ?? '48px';

        return '<div class="vbp-icon" style="' . esc_attr( $estilos_css ) . '; font-size: ' . esc_attr( $size ) . ';">' . $this->render_editorial_icon( $icono ) . '</div>';
    }

    /**
     * Renderiza Columns
     */
    private function render_columns( $elemento ) {
        $data        = $elemento['data'] ?? array();
        $estilos     = $elemento['styles'] ?? array();
        $estilos_css = $this->generar_estilos_elemento( $estilos );

        $columnas       = $data['columnas'] ?? ( $data['columns'] ?? 2 );
        $column_widths  = $data['columnWidths'] ?? array();
        $gap            = isset( $data['gap'] ) ? $data['gap'] . 'px' : '20px';
        $vertical_align = $data['align'] ?? ( $data['verticalAlign'] ?? 'start' );
        $stack_mobile   = $data['stack_on'] ?? ( $data['stackOnMobile'] ?? 'mobile' );
        $children       = $elemento['children'] ?? array();

        // Generar ID único para estilos inline
        $element_id = 'vbp-cols-' . substr( md5( wp_json_encode( $elemento ) ), 0, 8 );

        // Generar grid-template-columns
        // Prioridad: gridTemplateColumns directo > columnWidths > distribución equitativa
        if ( ! empty( $data['gridTemplateColumns'] ) ) {
            // Usar gridTemplateColumns directo si viene del inspector
            $grid_columns = $data['gridTemplateColumns'];
        } elseif ( ! empty( $column_widths ) && count( $column_widths ) === intval( $columnas ) ) {
            $grid_columns = implode( ' ', array_map( function( $width ) {
                // Convertir porcentaje a fracción para mejor comportamiento con gap
                $percentage = floatval( str_replace( '%', '', $width ) );
                return $percentage . 'fr';
            }, $column_widths ) );
        } else {
            // Distribución equitativa
            $grid_columns = 'repeat(' . intval( $columnas ) . ', 1fr)';
        }

        // Mapear alineación vertical
        $align_items_map = array(
            'start'   => 'flex-start',
            'center'  => 'center',
            'end'     => 'flex-end',
            'stretch' => 'stretch',
        );
        $align_items = $align_items_map[ $vertical_align ] ?? 'flex-start';

        // CSS inline para grid
        $grid_css = sprintf(
            'display: grid; grid-template-columns: %s; gap: %s; align-items: %s; %s',
            esc_attr( $grid_columns ),
            esc_attr( $gap ),
            esc_attr( $align_items ),
            esc_attr( $estilos_css )
        );

        $html = '<div id="' . esc_attr( $element_id ) . '" class="vbp-columns vbp-columns--' . intval( $columnas ) . '" style="' . esc_attr( $grid_css ) . '">';

        if ( ! empty( $children ) ) {
            foreach ( $children as $index => $hijo ) {
                $column_style = '';
                // Aplicar ancho individual si está definido
                if ( isset( $column_widths[ $index ] ) ) {
                    $column_style = 'min-width: 0;'; // Prevenir overflow en grid
                }
                $html .= '<div class="vbp-column" style="' . esc_attr( $column_style ) . '">';
                $html .= $this->renderizar_elemento( $hijo );
                $html .= '</div>';
            }
        } else {
            // Columnas vacías
            for ( $i = 0; $i < $columnas; $i++ ) {
                $html .= '<div class="vbp-column"></div>';
            }
        }

        $html .= '</div>';

        // CSS responsive para apilar en móvil
        if ( $stack_mobile ) {
            $html .= '<style>
                @media (max-width: 768px) {
                    #' . esc_attr( $element_id ) . ' {
                        grid-template-columns: 1fr !important;
                    }
                }
            </style>';
        }

        return $html;
    }

    /**
     * Alias para row
     */
    private function render_row( $elemento ) {
        return $this->render_columns( $elemento );
    }

    /**
     * Renderiza Container
     */
    private function render_container( $elemento ) {
        $data        = $elemento['data'] ?? array();
        $estilos     = $elemento['styles'] ?? array();
        $estilos_css = $this->generar_estilos_elemento( $estilos );
        $children    = $elemento['children'] ?? array();

        $max_width   = $data['max_width'] ?? '1200px';
        $padding     = $data['padding'] ?? '20px';
        $background  = $data['background'] ?? 'transparent';
        $align       = $data['align'] ?? 'center';
        $full_height = ! empty( $data['full_height'] );

        // Determinar margin según alineación
        $margin = '0 auto'; // Centro por defecto
        if ( 'left' === $align ) {
            $margin = '0 auto 0 0';
        } elseif ( 'right' === $align ) {
            $margin = '0 0 0 auto';
        }

        // Altura completa
        $height_css = $full_height ? 'min-height: 100vh;' : '';

        $container_css = sprintf(
            'max-width: %s; margin: %s; padding: %s; background: %s; %s %s',
            'full' === $max_width ? '100%' : esc_attr( $max_width ),
            esc_attr( $margin ),
            esc_attr( $padding ),
            esc_attr( $background ),
            $height_css,
            esc_attr( $estilos_css )
        );

        $html = '<div class="vbp-container flavor-container" style="' . esc_attr( $container_css ) . '">';

        if ( ! empty( $children ) ) {
            foreach ( $children as $hijo ) {
                $html .= $this->renderizar_elemento( $hijo );
            }
        }

        $html .= '</div>';

        return $html;
    }

    /**
     * Renderiza Grid
     */
    private function render_grid( $elemento ) {
        $data        = $elemento['data'] ?? array();
        $estilos     = $elemento['styles'] ?? array();
        $estilos_css = $this->generar_estilos_elemento( $estilos );
        $children    = $elemento['children'] ?? array();

        $columnas      = $data['columnas'] ?? 3;
        $filas         = $data['filas'] ?? '';
        $gap           = isset( $data['gap'] ) ? $data['gap'] : '24px';
        $auto_fit      = $data['auto_fit'] ?? '';
        $min_col_width = $data['min_col_width'] ?? '200px';

        // Añadir unidad si no tiene
        if ( is_numeric( $gap ) ) {
            $gap .= 'px';
        }

        // Grid template columns
        if ( ! empty( $auto_fit ) ) {
            // Usar auto-fit o auto-fill con minmax
            $grid_cols = sprintf(
                'repeat(%s, minmax(%s, 1fr))',
                esc_attr( $auto_fit ),
                esc_attr( $min_col_width )
            );
        } else {
            // Columnas fijas
            $grid_cols = sprintf( 'repeat(%d, 1fr)', intval( $columnas ) );
        }

        $grid_rows = ! empty( $filas ) ? 'grid-template-rows: repeat(' . intval( $filas ) . ', auto);' : '';

        $grid_css = sprintf(
            'display: grid; grid-template-columns: %s; %s gap: %s; %s',
            $grid_cols,
            $grid_rows,
            esc_attr( $gap ),
            esc_attr( $estilos_css )
        );

        $html = '<div class="vbp-grid flavor-grid" style="' . esc_attr( $grid_css ) . '">';

        if ( ! empty( $children ) ) {
            foreach ( $children as $hijo ) {
                $html .= '<div class="vbp-grid-item">';
                $html .= $this->renderizar_elemento( $hijo );
                $html .= '</div>';
            }
        }

        $html .= '</div>';

        return $html;
    }

    /**
     * Renderiza un widget global
     *
     * Los widgets globales son elementos reutilizables que se almacenan
     * como posts y se referencian por ID.
     *
     * @param array $elemento Datos del elemento.
     * @return string
     */
    private function render_global_widget( $elemento ) {
        $data      = $elemento['data'] ?? array();
        $widget_id = isset( $data['globalWidgetId'] ) ? absint( $data['globalWidgetId'] ) : 0;

        if ( ! $widget_id ) {
            return '<div class="vbp-global-widget vbp-global-widget--error">' .
                   esc_html__( 'Widget global no configurado', FLAVOR_PLATFORM_TEXT_DOMAIN ) .
                   '</div>';
        }

        // Obtener el widget global
        if ( ! class_exists( 'Flavor_VBP_Global_Widgets' ) ) {
            return '<div class="vbp-global-widget vbp-global-widget--error">' .
                   esc_html__( 'Sistema de widgets globales no disponible', FLAVOR_PLATFORM_TEXT_DOMAIN ) .
                   '</div>';
        }

        $global_widgets = Flavor_VBP_Global_Widgets::get_instance();
        $widget_data    = $global_widgets->get_widget_data( $widget_id );

        if ( ! $widget_data ) {
            return '<div class="vbp-global-widget vbp-global-widget--error">' .
                   esc_html__( 'Widget global no encontrado', FLAVOR_PLATFORM_TEXT_DOMAIN ) .
                   '</div>';
        }

        // Renderizar el elemento del widget
        $html = '<div class="vbp-global-widget" data-global-widget-id="' . esc_attr( $widget_id ) . '">';
        $html .= $this->renderizar_elemento( $widget_data );
        $html .= '</div>';

        return $html;
    }

    /**
     * Renderiza Card
     */
    private function render_card( $elemento ) {
        $data         = $elemento['data'] ?? array();
        $estilos      = $elemento['styles'] ?? array();
        $estilos_css  = $this->generar_estilos_elemento( $estilos );
        $estilos_anim = $this->generar_estilos_animacion( $estilos );
        $clases_anim  = $this->generar_clases_animacion( $estilos );
        $atributos    = $this->generar_atributos_animacion( $estilos );
        $children     = $elemento['children'] ?? array();

        // Soportar ambos formatos
        $titulo      = $data['titulo'] ?? $data['title'] ?? '';
        $descripcion = $data['descripcion'] ?? $data['description'] ?? $data['content'] ?? '';
        $icono       = $data['icono'] ?? $data['icon'] ?? '';
        $imagen      = $data['imagen'] ?? $data['image'] ?? '';
        $enlace      = $data['enlace'] ?? $data['url'] ?? $data['link'] ?? '';

        $estilo_all = trim( $estilos_css . ( $estilos_anim ? '; ' . $estilos_anim : '' ) );
        $clases     = trim( 'vbp-card ' . $clases_anim );

        $html = '<div class="' . esc_attr( $clases ) . '" style="' . esc_attr( $estilo_all ) . '" ' . $atributos . '>';

        if ( $imagen ) {
            $html .= '<div class="vbp-card__image"><img src="' . esc_url( $imagen ) . '" alt="' . esc_attr( $titulo ) . '" loading="lazy"></div>';
        }

        if ( $icono ) {
            $html .= '<div class="vbp-card__icon">' . wp_kses_post( $icono ) . '</div>';
        }

        $html .= '<div class="vbp-card__content">';

        if ( $titulo ) {
            if ( $enlace ) {
                $html .= '<h3 class="vbp-card__title"><a href="' . esc_url( $enlace ) . '"' . $this->get_editor_link_attr( 'titulo' ) . '>' . esc_html( $titulo ) . '</a></h3>';
            } else {
                $html .= '<h3 class="vbp-card__title"' . $this->get_editor_contenteditable_attr( 'titulo' ) . '>' . esc_html( $titulo ) . '</h3>';
            }
        }

        if ( $descripcion ) {
            $html .= '<p class="vbp-card__description"' . $this->get_editor_contenteditable_attr( 'descripcion' ) . '>' . wp_kses_post( $descripcion ) . '</p>';
        }

        // Renderizar hijos
        if ( ! empty( $children ) ) {
            foreach ( $children as $hijo ) {
                $html .= $this->renderizar_elemento( $hijo );
            }
        }

        $html .= '</div>';

        if ( $enlace && ! $titulo ) {
            $html .= '<a href="' . esc_url( $enlace ) . '" class="vbp-card__link"></a>';
        }

        $html .= '</div>';

        return $html;
    }

    /**
     * Renderiza Section
     */
    private function render_section( $elemento ) {
        $data         = $elemento['data'] ?? array();
        $estilos      = $elemento['styles'] ?? array();
        $estilos_css  = $this->generar_estilos_elemento( $estilos );
        $estilos_anim = $this->generar_estilos_animacion( $estilos );
        $clases_anim  = $this->generar_clases_animacion( $estilos );
        $atributos    = $this->generar_atributos_animacion( $estilos );
        $children     = $elemento['children'] ?? array();
        $nombre       = $elemento['name'] ?? '';

        // Soportar ambos formatos: español e inglés
        $titulo      = $data['titulo'] ?? $data['title'] ?? '';
        $subtitulo   = $data['subtitulo'] ?? $data['subtitle'] ?? '';
        $contenido   = $data['contenido'] ?? $data['content'] ?? $data['text'] ?? '';
        $html_custom = $data['html'] ?? '';

        $estilo_all = trim( $estilos_css . ( $estilos_anim ? '; ' . $estilos_anim : '' ) );
        $clases     = trim( 'vbp-section ' . $clases_anim );

        $html = '<section class="' . esc_attr( $clases ) . '" style="' . esc_attr( $estilo_all ) . '" ' . $atributos . '>';
        $html .= '<div class="vbp-section__container flavor-container">';

        if ( $titulo ) {
            $html .= '<h2 class="vbp-section__title"' . $this->get_editor_contenteditable_attr( 'titulo' ) . '>' . wp_kses_post( $titulo ) . '</h2>';
        }

        if ( $subtitulo ) {
            $html .= '<p class="vbp-section__subtitle"' . $this->get_editor_contenteditable_attr( 'subtitulo' ) . '>' . wp_kses_post( $subtitulo ) . '</p>';
        }

        if ( $contenido ) {
            $html .= '<div class="vbp-section__content"' . $this->get_editor_contenteditable_attr( 'contenido' ) . '>' . wp_kses_post( $contenido ) . '</div>';
        }

        if ( $html_custom ) {
            $html .= '<div class="vbp-section__html">' . wp_kses_post( $html_custom ) . '</div>';
        }

        // Renderizar hijos
        if ( ! empty( $children ) ) {
            $html .= '<div class="vbp-section__children">';
            foreach ( $children as $hijo ) {
                $html .= $this->renderizar_elemento( $hijo );
            }
            $html .= '</div>';
        }

        // Si no hay contenido, mostrar el nombre como fallback
        if ( empty( $titulo ) && empty( $subtitulo ) && empty( $contenido ) && empty( $html_custom ) && empty( $children ) && $nombre ) {
            $html .= '<div class="vbp-section__placeholder">' . esc_html( $nombre ) . '</div>';
        }

        $html .= '</div>';
        $html .= '</section>';

        return $html;
    }

    /**
     * Renderiza un grid de productos
     *
     * @param array $elemento Datos del elemento.
     * @return string
     */
    private function render_product_grid( $elemento ) {
        $data     = $elemento['data'] ?? array();
        $estilos  = $elemento['styles'] ?? array();
        $items    = $data['items'] ?? array();
        $columnas = $data['columnas'] ?? 4;

        $estilos_css = $this->generar_estilos_elemento( $estilos );

        $html = '<div class="vbp-product-grid vbp-product-grid--cols-' . intval( $columnas ) . '" style="' . esc_attr( $estilos_css ) . '">';

        foreach ( $items as $indice => $item ) {
            $nombre    = $item['nombre'] ?? '';
            $precio    = $item['precio'] ?? '';
            $imagen    = $item['imagen'] ?? '';
            $productor = $item['productor'] ?? '';

            $html .= '<div class="vbp-product-card">';
            if ( $imagen ) {
                $html .= '<div class="vbp-product-card__image">';
                $html .= '<img src="' . esc_url( $imagen ) . '" alt="' . esc_attr( $nombre ) . '" loading="lazy" />';
                $html .= '</div>';
            }
            $html .= '<div class="vbp-product-card__content">';
            $html .= '<h4 class="vbp-product-card__title"' . $this->get_editor_contenteditable_path_attr( 'items.' . $indice . '.nombre' ) . '>' . esc_html( $nombre ) . '</h4>';
            if ( $productor ) {
                $html .= '<p class="vbp-product-card__producer"' . $this->get_editor_contenteditable_path_attr( 'items.' . $indice . '.productor' ) . '>' . esc_html( $productor ) . '</p>';
            }
            if ( $precio ) {
                $html .= '<span class="vbp-product-card__price"' . $this->get_editor_contenteditable_path_attr( 'items.' . $indice . '.precio' ) . '>' . esc_html( $precio ) . '</span>';
            }
            $html .= '</div>';
            $html .= '</div>';
        }

        $html .= '</div>';

        return $html;
    }

    /**
     * Renderiza un grid de blog/noticias
     *
     * @param array $elemento Datos del elemento.
     * @return string
     */
    private function render_blog_grid( $elemento ) {
        $data     = $elemento['data'] ?? array();
        $estilos  = $elemento['styles'] ?? array();
        $items    = $data['items'] ?? array();
        $columnas = $data['columnas'] ?? 3;

        $estilos_css = $this->generar_estilos_elemento( $estilos );

        $html = '<div class="vbp-blog-grid vbp-blog-grid--cols-' . intval( $columnas ) . '" style="' . esc_attr( $estilos_css ) . '">';

        foreach ( $items as $indice => $item ) {
            $titulo    = $item['titulo'] ?? '';
            $extracto  = $item['extracto'] ?? '';
            $imagen    = $item['imagen'] ?? '';
            $fecha     = $item['fecha'] ?? '';
            $categoria = $item['categoria'] ?? '';

            $html .= '<article class="vbp-blog-card">';
            if ( $imagen ) {
                $html .= '<div class="vbp-blog-card__image">';
                $html .= '<img src="' . esc_url( $imagen ) . '" alt="' . esc_attr( $titulo ) . '" loading="lazy" />';
                if ( $categoria ) {
                    $html .= '<span class="vbp-blog-card__category"' . $this->get_editor_contenteditable_path_attr( 'items.' . $indice . '.categoria' ) . '>' . esc_html( $categoria ) . '</span>';
                }
                $html .= '</div>';
            }
            $html .= '<div class="vbp-blog-card__content">';
            $html .= '<h4 class="vbp-blog-card__title"' . $this->get_editor_contenteditable_path_attr( 'items.' . $indice . '.titulo' ) . '>' . esc_html( $titulo ) . '</h4>';
            if ( $fecha ) {
                $html .= '<time class="vbp-blog-card__date"' . $this->get_editor_contenteditable_path_attr( 'items.' . $indice . '.fecha' ) . '>' . esc_html( $fecha ) . '</time>';
            }
            if ( $extracto ) {
                $html .= '<p class="vbp-blog-card__excerpt"' . $this->get_editor_contenteditable_path_attr( 'items.' . $indice . '.extracto' ) . '>' . esc_html( $extracto ) . '</p>';
            }
            $html .= '</div>';
            $html .= '</article>';
        }

        $html .= '</div>';

        return $html;
    }

    /**
     * Renderiza dos columnas
     *
     * @param array $elemento Datos del elemento.
     * @return string
     */
    private function render_two_columns( $elemento ) {
        $data    = $elemento['data'] ?? array();
        $estilos = $elemento['styles'] ?? array();
        $gap     = isset( $data['gap'] ) ? absint( $data['gap'] ) : 24;

        $col_izquierda = $data['columna_izquierda'] ?? array();
        $col_derecha   = $data['columna_derecha'] ?? array();

        $estilos_css = $this->generar_estilos_elemento( $estilos );

        $html = '<div class="vbp-two-columns" style="display: grid; grid-template-columns: 1fr 1fr; gap: ' . $gap . 'px; ' . esc_attr( $estilos_css ) . '">';
        $html .= '<div class="vbp-two-columns__left">';
        $html .= $this->render_column_content( $col_izquierda, 'columna_izquierda.data' );
        $html .= '</div>';
        $html .= '<div class="vbp-two-columns__right">';
        $html .= $this->render_column_content( $col_derecha, 'columna_derecha.data' );
        $html .= '</div>';
        $html .= '</div>';

        return $html;
    }

    /**
     * Renderiza el contenido de una columna en two_columns
     *
     * @param array $col_data Datos de la columna.
     * @return string
     */
    private function render_column_content( $col_data, $base_path = '' ) {
        if ( empty( $col_data ) || empty( $col_data['type'] ) ) {
            return '';
        }

        $tipo      = $col_data['type'];
        $contenido = $col_data['data'] ?? array();

        // Renderizar según tipo
        switch ( $tipo ) {
            case 'contact_info':
                return $this->render_contact_info( array( 'data' => $contenido ) );

            case 'contact_form':
                return $this->render_contact_form( array( 'data' => $contenido ) );

            case 'text':
                $texto = $contenido['contenido'] ?? '';
                return '<div class="vbp-column-text"' . ( $base_path ? $this->get_editor_contenteditable_path_attr( $base_path . '.contenido' ) : '' ) . '>' . wp_kses_post( $texto ) . '</div>';

            case 'image':
                $src = $contenido['src'] ?? '';
                $alt = $contenido['alt'] ?? '';
                if ( $src ) {
                    return '<div class="vbp-column-image"><img src="' . esc_url( $src ) . '" alt="' . esc_attr( $alt ) . '" style="max-width: 100%; height: auto;"></div>';
                }
                return '';

            default:
                // Intentar renderizar como elemento genérico
                return $this->renderizar_elemento( $col_data );
        }
    }

    /**
     * Renderiza lista de beneficios
     *
     * @param array $elemento Datos del elemento.
     * @return string
     */
    private function render_benefits( $elemento ) {
        $data    = $elemento['data'] ?? array();
        $estilos = $elemento['styles'] ?? array();
        $titulo  = $data['titulo'] ?? '';
        $items   = $data['items'] ?? array();
        $nota    = $data['nota'] ?? '';

        $estilos_css = $this->generar_estilos_elemento( $estilos );

        $html = '<div class="vbp-benefits" style="' . esc_attr( $estilos_css ) . '">';

        if ( $titulo ) {
            $html .= '<h3 class="vbp-benefits__title"' . $this->get_editor_contenteditable_attr( 'titulo' ) . '>' . esc_html( $titulo ) . '</h3>';
        }

        $html .= '<ul class="vbp-benefits__list">';
        foreach ( $items as $indice => $item ) {
            $icono = $item['icono'] ?? '✓';
            $texto = $item['texto'] ?? '';
            $html .= '<li class="vbp-benefits__item">';
            $html .= '<span class="vbp-benefits__icon">' . esc_html( $icono ) . '</span>';
            $html .= '<span class="vbp-benefits__text"' . $this->get_editor_contenteditable_path_attr( 'items.' . $indice . '.texto' ) . '>' . esc_html( $texto ) . '</span>';
            $html .= '</li>';
        }
        $html .= '</ul>';

        if ( $nota ) {
            $html .= '<p class="vbp-benefits__note"' . $this->get_editor_contenteditable_attr( 'nota' ) . '>' . esc_html( $nota ) . '</p>';
        }

        $html .= '</div>';

        return $html;
    }

    /**
     * Renderiza formulario de registro
     *
     * @param array $elemento Datos del elemento.
     * @return string
     */
    private function render_registration_form( $elemento ) {
        $data    = $elemento['data'] ?? array();
        $estilos = $elemento['styles'] ?? array();
        $titulo  = $data['titulo'] ?? '';
        $campos  = $data['campos'] ?? array();
        $checkbox_text = $data['checkbox'] ?? '';
        $boton_texto = $data['boton_texto'] ?? __( 'Enviar', FLAVOR_PLATFORM_TEXT_DOMAIN );

        $estilos_css = $this->generar_estilos_elemento( $estilos );

        $html = '<div class="vbp-form vbp-registration-form" style="' . esc_attr( $estilos_css ) . '">';

        if ( $titulo ) {
            $html .= '<h3 class="vbp-form__title"' . $this->get_editor_contenteditable_attr( 'titulo' ) . '>' . esc_html( $titulo ) . '</h3>';
        }

        $html .= '<form class="vbp-form__form" action="" method="post">';

        foreach ( $campos as $campo ) {
            $nombre    = $campo['nombre'] ?? '';
            $label     = $campo['label'] ?? '';
            $tipo      = $campo['tipo'] ?? 'text';
            $requerido = ! empty( $campo['requerido'] );
            $opciones  = $campo['opciones'] ?? array();

            $html .= '<div class="vbp-form__field">';
            $html .= '<label class="vbp-form__label" for="' . esc_attr( $nombre ) . '">' . esc_html( $label );
            if ( $requerido ) {
                $html .= ' <span class="vbp-form__required">*</span>';
            }
            $html .= '</label>';

            if ( 'textarea' === $tipo ) {
                $html .= '<textarea class="vbp-form__input vbp-form__textarea" name="' . esc_attr( $nombre ) . '" id="' . esc_attr( $nombre ) . '"' . ( $requerido ? ' required' : '' ) . '></textarea>';
            } elseif ( 'select' === $tipo ) {
                $html .= '<select class="vbp-form__input vbp-form__select" name="' . esc_attr( $nombre ) . '" id="' . esc_attr( $nombre ) . '"' . ( $requerido ? ' required' : '' ) . '>';
                $html .= '<option value="">' . __( 'Seleccionar...', FLAVOR_PLATFORM_TEXT_DOMAIN ) . '</option>';
                foreach ( $opciones as $opcion ) {
                    $html .= '<option value="' . esc_attr( $opcion ) . '">' . esc_html( $opcion ) . '</option>';
                }
                $html .= '</select>';
            } else {
                $html .= '<input class="vbp-form__input" type="' . esc_attr( $tipo ) . '" name="' . esc_attr( $nombre ) . '" id="' . esc_attr( $nombre ) . '"' . ( $requerido ? ' required' : '' ) . ' />';
            }

            $html .= '</div>';
        }

        if ( $checkbox_text ) {
            $html .= '<div class="vbp-form__field vbp-form__checkbox-field">';
            $html .= '<label class="vbp-form__checkbox-label"' . $this->get_editor_contenteditable_attr( 'checkbox' ) . '>';
            $html .= '<input type="checkbox" name="acepto" required class="vbp-form__checkbox" />';
            $html .= ' ' . esc_html( $checkbox_text );
            $html .= '</label>';
            $html .= '</div>';
        }

        $html .= '<button type="submit" class="vbp-form__submit vbp-button"' . $this->get_editor_link_attr( 'boton_texto' ) . '>' . esc_html( $boton_texto ) . '</button>';
        $html .= '</form>';
        $html .= '</div>';

        return $html;
    }

    /**
     * Renderiza formulario de contacto
     *
     * @param array $elemento Datos del elemento.
     * @return string
     */
    private function render_contact_form( $elemento ) {
        return $this->render_registration_form( $elemento );
    }

    /**
     * Renderiza info de contacto
     *
     * @param array $elemento Datos del elemento.
     * @return string
     */
    private function render_contact_info( $elemento ) {
        $data    = $elemento['data'] ?? array();
        $estilos = $elemento['styles'] ?? array();
        $titulo  = $data['titulo'] ?? '';
        $items   = $data['items'] ?? array();

        $estilos_css = $this->generar_estilos_elemento( $estilos );

        $html = '<div class="vbp-contact-info" style="' . esc_attr( $estilos_css ) . '">';

        if ( $titulo ) {
            $html .= '<h3 class="vbp-contact-info__title"' . $this->get_editor_contenteditable_attr( 'titulo' ) . '>' . esc_html( $titulo ) . '</h3>';
        }

        $html .= '<ul class="vbp-contact-info__list">';
        foreach ( $items as $indice => $item ) {
            $icono  = $item['icono'] ?? '';
            $titulo_item = $item['titulo'] ?? '';
            $valor  = $item['valor'] ?? '';

            $html .= '<li class="vbp-contact-info__item">';
            if ( $icono ) {
                $html .= '<span class="vbp-contact-info__icon">' . esc_html( $icono ) . '</span>';
            }
            $html .= '<div class="vbp-contact-info__content">';
            if ( $titulo_item ) {
                $html .= '<strong class="vbp-contact-info__label"' . $this->get_editor_contenteditable_path_attr( 'items.' . $indice . '.titulo' ) . '>' . esc_html( $titulo_item ) . '</strong>';
            }
            $html .= '<span class="vbp-contact-info__value"' . $this->get_editor_contenteditable_path_attr( 'items.' . $indice . '.valor' ) . '>' . esc_html( $valor ) . '</span>';
            $html .= '</div>';
            $html .= '</li>';
        }
        $html .= '</ul>';
        $html .= '</div>';

        return $html;
    }

    /**
     * Renderiza proceso/pasos
     *
     * @param array $elemento Datos del elemento.
     * @return string
     */
    private function render_process( $elemento ) {
        $data    = $elemento['data'] ?? array();
        $estilos = $elemento['styles'] ?? array();
        $titulo  = $data['titulo'] ?? '';
        $items   = $data['items'] ?? array();

        $estilos_css = $this->generar_estilos_elemento( $estilos );

        $html = '<div class="vbp-process" style="' . esc_attr( $estilos_css ) . '">';

        if ( $titulo ) {
            $html .= '<h3 class="vbp-process__title"' . $this->get_editor_contenteditable_attr( 'titulo' ) . '>' . esc_html( $titulo ) . '</h3>';
        }

        $html .= '<div class="vbp-process__steps">';
        foreach ( $items as $indice => $item ) {
            $paso = $item['paso'] ?? '';
            $titulo_step = $item['titulo'] ?? '';
            $desc = $item['descripcion'] ?? '';

            $html .= '<div class="vbp-process__step">';
            $html .= '<div class="vbp-process__number"' . $this->get_editor_contenteditable_path_attr( 'items.' . $indice . '.paso' ) . '>' . esc_html( $paso ) . '</div>';
            $html .= '<div class="vbp-process__content">';
            $html .= '<h4 class="vbp-process__step-title"' . $this->get_editor_contenteditable_path_attr( 'items.' . $indice . '.titulo' ) . '>' . esc_html( $titulo_step ) . '</h4>';
            if ( $desc ) {
                $html .= '<p class="vbp-process__step-desc"' . $this->get_editor_contenteditable_path_attr( 'items.' . $indice . '.descripcion' ) . '>' . esc_html( $desc ) . '</p>';
            }
            $html .= '</div>';
            $html .= '</div>';
        }
        $html .= '</div>';
        $html .= '</div>';

        return $html;
    }

    /**
     * Renderiza feed social
     *
     * @param array $elemento Datos del elemento.
     * @return string
     */
    private function render_social_feed( $elemento ) {
        $data    = $elemento['data'] ?? array();
        $estilos = $elemento['styles'] ?? array();
        $titulo  = $data['titulo'] ?? '';
        $subtitulo = $data['subtitulo'] ?? '';
        $tipo    = $data['tipo'] ?? 'grid';
        $limite  = $data['mostrar_ultimos'] ?? 6;
        $fondo   = $data['fondo'] ?? '';

        $estilos_css = $this->generar_estilos_elemento( $estilos );
        if ( $fondo ) {
            $fondo_mapped = $this->map_color_to_variable( $fondo );
            $estilos_css .= '; background-color: ' . esc_attr( $fondo_mapped );
        }

        // Intentar usar shortcode si está disponible
        if ( shortcode_exists( 'rs_feed' ) ) {
            $html = '<div class="vbp-social-feed-wrapper" style="' . esc_attr( $estilos_css ) . '">';
            if ( $titulo ) {
                $html .= '<h3 class="vbp-section__title"' . $this->get_editor_contenteditable_attr( 'titulo' ) . '>' . esc_html( $titulo ) . '</h3>';
            }
            if ( $subtitulo ) {
                $html .= '<p class="vbp-section__subtitle"' . $this->get_editor_contenteditable_attr( 'subtitulo' ) . '>' . esc_html( $subtitulo ) . '</p>';
            }
            $html .= do_shortcode( '[rs_feed limite="' . intval( $limite ) . '" tipo="' . esc_attr( $tipo ) . '"]' );
            $html .= '</div>';
            return $html;
        }

        // Fallback: mostrar mensaje de placeholder
        $html = '<div class="vbp-social-feed vbp-placeholder" style="' . esc_attr( $estilos_css ) . '">';
        if ( $titulo ) {
            $html .= '<h3 class="vbp-section__title"' . $this->get_editor_contenteditable_attr( 'titulo' ) . '>' . esc_html( $titulo ) . '</h3>';
        }
        $html .= '<p class="vbp-placeholder__message">' . __( 'Activa el módulo Red Social para ver el feed.', FLAVOR_PLATFORM_TEXT_DOMAIN ) . '</p>';
        $html .= '</div>';

        return $html;
    }

    /**
     * Renderiza sello de conciencia widget
     *
     * @param array $elemento Datos del elemento.
     * @return string
     */
    private function render_sello_conciencia_widget( $elemento ) {
        $data    = $elemento['data'] ?? array();
        $estilos = $elemento['styles'] ?? array();
        $titulo  = $data['titulo'] ?? '';
        $subtitulo = $data['subtitulo'] ?? '';
        $sellos  = $data['sellos'] ?? array();
        $fondo   = $data['fondo'] ?? '';

        $estilos_css = $this->generar_estilos_elemento( $estilos );
        if ( $fondo ) {
            $fondo_mapped = $this->map_color_to_variable( $fondo );
            $estilos_css .= '; background-color: ' . esc_attr( $fondo_mapped );
        }

        // Si hay shortcode de sello conciencia, usarlo
        if ( shortcode_exists( 'sello_conciencia' ) ) {
            $html = '<div class="vbp-sello-wrapper" style="' . esc_attr( $estilos_css ) . '">';
            if ( $titulo ) {
                $html .= '<h3 class="vbp-section__title"' . $this->get_editor_contenteditable_attr( 'titulo' ) . '>' . esc_html( $titulo ) . '</h3>';
            }
            if ( $subtitulo ) {
                $html .= '<p class="vbp-section__subtitle"' . $this->get_editor_contenteditable_attr( 'subtitulo' ) . '>' . esc_html( $subtitulo ) . '</p>';
            }
            $html .= do_shortcode( '[sello_conciencia]' );
            $html .= '</div>';
            return $html;
        }

        // Fallback con sellos manuales
        $html = '<div class="vbp-sellos" style="' . esc_attr( $estilos_css ) . '; padding: 3rem 2rem;">';
        if ( $titulo ) {
            $html .= '<h3 class="vbp-section__title" style="text-align: center; margin-bottom: 0.5rem;"' . $this->get_editor_contenteditable_attr( 'titulo' ) . '>' . esc_html( $titulo ) . '</h3>';
        }
        if ( $subtitulo ) {
            $html .= '<p class="vbp-section__subtitle" style="text-align: center; margin-bottom: 2rem; color: var(--flavor-text-muted, #666);"' . $this->get_editor_contenteditable_attr( 'subtitulo' ) . '>' . esc_html( $subtitulo ) . '</p>';
        }

        if ( ! empty( $sellos ) ) {
            $html .= '<div class="vbp-sellos__grid" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 1.5rem;">';
            foreach ( $sellos as $sello ) {
                $nombre = $sello['nombre'] ?? '';
                $icono  = $sello['icono'] ?? '';
                $desc   = $sello['descripcion'] ?? '';

                $html .= '<div class="vbp-sello-card" style="background: var(--flavor-bg-card, #fff); padding: 1.5rem; border-radius: 12px; text-align: center; box-shadow: 0 2px 8px rgba(0,0,0,0.08);">';
                if ( $icono ) {
                    $html .= '<div class="vbp-sello-card__icon" style="font-size: 2.5rem; margin-bottom: 1rem;">' . esc_html( $icono ) . '</div>';
                }
                $html .= '<h4 class="vbp-sello-card__title" style="font-size: 1.1rem; font-weight: 600; margin-bottom: 0.5rem;">' . esc_html( $nombre ) . '</h4>';
                if ( $desc ) {
                    $html .= '<p class="vbp-sello-card__desc" style="color: var(--flavor-text-muted, #666); font-size: 0.9rem; line-height: 1.5;">' . esc_html( $desc ) . '</p>';
                }
                $html .= '</div>';
            }
            $html .= '</div>';
        }
        $html .= '</div>';

        return $html;
    }

    // =========================================================================
    // Métodos públicos para API
    // =========================================================================

    /**
     * Genera preview HTML de un widget para uso desde API REST
     *
     * @param array $elemento Datos del elemento (type, data, etc).
     * @param array $widget_info Información del widget (name, icon, shortcode, module).
     * @return string HTML del preview.
     */
    public function render_widget_preview_public( $elemento, $widget_info = array() ) {
        // Construir estructura de bloque compatible
        $bloque = array(
            'name'      => $widget_info['name'] ?? $elemento['name'] ?? ucfirst( str_replace( '_', ' ', $elemento['type'] ?? '' ) ),
            'icon'      => $widget_info['icon'] ?? '📦',
            'module'    => $widget_info['module'] ?? '',
            'shortcode' => $widget_info['shortcode'] ?? '',
            'category'  => $widget_info['category'] ?? 'modules',
            'defaults'  => $widget_info['defaults'] ?? array(),
        );

        // Usar el método de preview existente
        return $this->render_module_preview( $elemento, $bloque );
    }

    /**
     * Renderiza una sección de contacto (antes two_columns)
     *
     * @param array $elemento Datos del elemento.
     * @return string HTML de la sección.
     */
    private function render_contact_section( $elemento ) {
        $data    = $elemento['data'] ?? array();
        $estilos = $elemento['styles'] ?? array();
        $attrs   = $this->generar_atributos_completos( $estilos, 'vbp-two-columns vbp-contact-section' );

        $gap           = isset( $data['gap'] ) ? absint( $data['gap'] ) : 24;
        $col_izquierda = $data['columna_izquierda'] ?? array();
        $col_derecha   = $data['columna_derecha'] ?? array();

        // Añadir estilos de grid al style existente
        $estilo_grid  = 'display: grid; grid-template-columns: 1fr 1fr; gap: ' . $gap . 'px';
        $estilo_final = $attrs['style'] ? $attrs['style'] . '; ' . $estilo_grid : $estilo_grid;

        $html = '<div class="' . esc_attr( $attrs['class'] ) . '" style="' . esc_attr( $estilo_final ) . '" ' . $attrs['attrs'] . '>';

        // Columna izquierda
        $html .= '<div class="vbp-column vbp-column--left">';
        $html .= $this->render_column_content( $col_izquierda, 'columna_izquierda.data' );
        $html .= '</div>';

        // Columna derecha
        $html .= '<div class="vbp-column vbp-column--right">';
        $html .= $this->render_column_content( $col_derecha, 'columna_derecha.data' );
        $html .= '</div>';

        $html .= '</div>';

        return $html;
    }

    /**
     * Renderiza columna de información de contacto
     *
     * @param array $content Contenido.
     * @return string HTML.
     */
    private function render_contact_info_column( $content ) {
        $titulo = $content['titulo'] ?? '';
        $items  = $content['items'] ?? array();

        $html = '<div class="vbp-contact-info">';

        if ( $titulo ) {
            $html .= '<h3 class="vbp-contact-info__title"' . $this->get_editor_contenteditable_attr( 'titulo' ) . '>' . esc_html( $titulo ) . '</h3>';
        }

        if ( ! empty( $items ) ) {
            $html .= '<ul class="vbp-contact-info__list">';
            foreach ( $items as $item ) {
                $icono  = $item['icono'] ?? '';
                $label  = $item['titulo'] ?? '';
                $valor  = $item['valor'] ?? '';

                $html .= '<li class="vbp-contact-info__item">';
                if ( $icono ) {
                    $html .= '<span class="vbp-contact-info__icon">' . esc_html( $icono ) . '</span>';
                }
                $html .= '<div class="vbp-contact-info__content">';
                if ( $label ) {
                    $html .= '<strong>' . esc_html( $label ) . '</strong>';
                }
                if ( $valor ) {
                    $html .= '<span>' . esc_html( $valor ) . '</span>';
                }
                $html .= '</div>';
                $html .= '</li>';
            }
            $html .= '</ul>';
        }

        $html .= '</div>';

        return $html;
    }

    /**
     * Renderiza columna de formulario de contacto
     *
     * @param array $content Contenido.
     * @return string HTML.
     */
    private function render_contact_form_column( $content ) {
        $titulo      = $content['titulo'] ?? '';
        $campos      = $content['campos'] ?? array();
        $boton_texto = $content['boton_texto'] ?? __( 'Enviar', FLAVOR_PLATFORM_TEXT_DOMAIN );

        $html = '<div class="vbp-contact-form">';

        if ( $titulo ) {
            $html .= '<h3 class="vbp-contact-form__title"' . $this->get_editor_contenteditable_attr( 'titulo' ) . '>' . esc_html( $titulo ) . '</h3>';
        }

        $html .= '<form class="vbp-contact-form__form" method="post">';

        foreach ( $campos as $campo ) {
            $tipo      = $campo['tipo'] ?? 'text';
            $label     = $campo['label'] ?? '';
            $requerido = ! empty( $campo['requerido'] );
            $name      = sanitize_title( $label );
            $req_attr  = $requerido ? 'required' : '';
            $req_mark  = $requerido ? ' <span class="required">*</span>' : '';

            $html .= '<div class="vbp-contact-form__field">';
            $html .= '<label>' . esc_html( $label ) . $req_mark . '</label>';

            switch ( $tipo ) {
                case 'textarea':
                    $html .= '<textarea name="' . esc_attr( $name ) . '" ' . $req_attr . '></textarea>';
                    break;

                case 'select':
                    $opciones = $campo['opciones'] ?? array();
                    $html .= '<select name="' . esc_attr( $name ) . '" ' . $req_attr . '>';
                    $html .= '<option value="">' . esc_html__( 'Selecciona...', FLAVOR_PLATFORM_TEXT_DOMAIN ) . '</option>';
                    foreach ( $opciones as $opcion ) {
                        $html .= '<option value="' . esc_attr( $opcion ) . '">' . esc_html( $opcion ) . '</option>';
                    }
                    $html .= '</select>';
                    break;

                default:
                    $html .= '<input type="' . esc_attr( $tipo ) . '" name="' . esc_attr( $name ) . '" ' . $req_attr . '>';
            }

            $html .= '</div>';
        }

        $html .= '<button type="submit" class="vbp-contact-form__submit"' . $this->get_editor_link_attr( 'boton_texto' ) . '>' . esc_html( $boton_texto ) . '</button>';
        $html .= '</form>';
        $html .= '</div>';

        return $html;
    }

    /**
     * Renderiza un bloque de audio
     *
     * @param array $elemento Datos del elemento.
     * @return string HTML del audio.
     */
    private function render_audio( $elemento ) {
        $data    = $elemento['data'] ?? array();
        $estilos = $elemento['styles'] ?? array();
        $attrs_html = $this->generar_atributos_html( $estilos, 'vbp-audio' );

        $src      = $data['src'] ?? '';
        $titulo   = $data['titulo'] ?? '';
        $autoplay = ! empty( $data['autoplay'] ) ? 'autoplay' : '';
        $loop     = ! empty( $data['loop'] ) ? 'loop' : '';
        $muted    = ! empty( $data['muted'] ) ? 'muted' : '';
        $controls = ( $data['controls'] ?? true ) !== false ? 'controls' : '';
        $preload  = $data['preload'] ?? 'metadata';

        $html = '<div ' . $attrs_html . '>';

        if ( $titulo ) {
            $html .= '<div class="vbp-audio__title"' . $this->get_editor_contenteditable_attr( 'titulo' ) . '>' . esc_html( $titulo ) . '</div>';
        }

        if ( $src ) {
            $html .= sprintf(
                '<audio src="%s" %s %s %s %s preload="%s" style="width: 100%%;"></audio>',
                esc_url( $src ),
                $controls,
                $autoplay,
                $loop,
                $muted,
                esc_attr( $preload )
            );
        } else {
            $html .= '<div class="vbp-audio__placeholder">Audio no disponible</div>';
        }

        $html .= '</div>';

        return $html;
    }

    /**
     * Renderiza un bloque embed (iframe, video externo, etc.)
     *
     * @param array $elemento Datos del elemento.
     * @return string HTML del embed.
     */
    private function render_embed( $elemento ) {
        $data    = $elemento['data'] ?? array();
        $estilos = $elemento['styles'] ?? array();
        $attrs_html = $this->generar_atributos_html( $estilos, 'vbp-embed' );

        $code         = $data['code'] ?? '';
        $url          = $data['url'] ?? '';
        $width        = $data['width'] ?? '100%';
        $height       = $data['height'] ?? '400px';
        $aspect_ratio = $data['aspect_ratio'] ?? '';
        $lazy_load    = ( $data['lazy_load'] ?? true ) !== false;

        $html = '<div ' . $attrs_html . '>';

        // Si hay código embed directo, usarlo
        if ( $code ) {
            // Contenedor responsive si hay aspect ratio
            if ( $aspect_ratio ) {
                $html .= '<div class="vbp-embed__responsive" style="aspect-ratio: ' . esc_attr( $aspect_ratio ) . '; width: ' . esc_attr( $width ) . ';">';
                $html .= wp_kses(
                    $code,
                    array(
                        'iframe' => array(
                            'src'             => true,
                            'width'           => true,
                            'height'          => true,
                            'frameborder'     => true,
                            'allow'           => true,
                            'allowfullscreen' => true,
                            'loading'         => true,
                            'title'           => true,
                            'style'           => true,
                        ),
                        'video'  => array(
                            'src'      => true,
                            'width'    => true,
                            'height'   => true,
                            'controls' => true,
                            'autoplay' => true,
                            'loop'     => true,
                            'muted'    => true,
                            'poster'   => true,
                        ),
                        'source' => array(
                            'src'  => true,
                            'type' => true,
                        ),
                    )
                );
                $html .= '</div>';
            } else {
                $html .= '<div class="vbp-embed__container" style="width: ' . esc_attr( $width ) . '; height: ' . esc_attr( $height ) . ';">';
                $html .= wp_kses(
                    $code,
                    array(
                        'iframe' => array(
                            'src'             => true,
                            'width'           => true,
                            'height'          => true,
                            'frameborder'     => true,
                            'allow'           => true,
                            'allowfullscreen' => true,
                            'loading'         => true,
                            'title'           => true,
                            'style'           => true,
                        ),
                        'video'  => array(
                            'src'      => true,
                            'width'    => true,
                            'height'   => true,
                            'controls' => true,
                            'autoplay' => true,
                            'loop'     => true,
                            'muted'    => true,
                            'poster'   => true,
                        ),
                        'source' => array(
                            'src'  => true,
                            'type' => true,
                        ),
                    )
                );
                $html .= '</div>';
            }
        } elseif ( $url ) {
            // Convertir URL a embed
            $embed_url = $this->url_to_embed( $url );
            if ( $embed_url ) {
                $loading_attr = $lazy_load ? 'loading="lazy"' : '';
                $aspect_style = $aspect_ratio ? 'aspect-ratio: ' . esc_attr( $aspect_ratio ) . ';' : 'height: ' . esc_attr( $height ) . ';';
                $html .= '<div class="vbp-embed__responsive" style="width: ' . esc_attr( $width ) . '; ' . $aspect_style . '">';
                $html .= sprintf(
                    '<iframe src="%s" width="100%%" height="100%%" frameborder="0" allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture" allowfullscreen %s style="position: absolute; top: 0; left: 0; width: 100%%; height: 100%%;"></iframe>',
                    esc_url( $embed_url ),
                    $loading_attr
                );
                $html .= '</div>';
            } else {
                $html .= '<div class="vbp-embed__placeholder">No se pudo convertir la URL a embed</div>';
            }
        } else {
            $html .= '<div class="vbp-embed__placeholder">Embed no configurado</div>';
        }

        $html .= '</div>';

        return $html;
    }

    /**
     * Convierte una URL de video a formato embed
     *
     * @param string $url URL del video.
     * @return string|false URL de embed o false si no se reconoce.
     */
    private function url_to_embed( $url ) {
        // YouTube
        if ( preg_match( '/(?:youtube\.com\/(?:watch\?v=|embed\/)|youtu\.be\/)([a-zA-Z0-9_-]+)/', $url, $matches ) ) {
            return 'https://www.youtube.com/embed/' . $matches[1];
        }

        // Vimeo
        if ( preg_match( '/vimeo\.com\/(\d+)/', $url, $matches ) ) {
            return 'https://player.vimeo.com/video/' . $matches[1];
        }

        // Spotify
        if ( preg_match( '/open\.spotify\.com\/(track|album|playlist|episode)\/([a-zA-Z0-9]+)/', $url, $matches ) ) {
            return 'https://open.spotify.com/embed/' . $matches[1] . '/' . $matches[2];
        }

        // SoundCloud
        if ( strpos( $url, 'soundcloud.com' ) !== false ) {
            return 'https://w.soundcloud.com/player/?url=' . rawurlencode( $url );
        }

        return false;
    }

    /**
     * Renderiza el bloque Lista Dinámica consultando una Collection.
     *
     * Espera en $elemento['data'] los campos source, query_args_json, template,
     * empty_message. La consulta pasa por el registry para sanear argumentos
     * contra el schema de cada campo antes de llegar a query().
     *
     * @param array $elemento Datos del elemento.
     * @return string HTML renderizado.
     */
    private function render_dynamic_list( $elemento ) {
        $data = isset( $elemento['data'] ) ? (array) $elemento['data'] : array();

        if ( ! class_exists( 'Flavor_VBP_Collection_Registry' ) ) {
            return '<div class="vbp-element vbp-dynamic-list vbp-dynamic-list--error">'
                . esc_html__( 'Sistema de colecciones no disponible', FLAVOR_PLATFORM_TEXT_DOMAIN )
                . '</div>';
        }

        $identificador_fuente = isset( $data['source'] ) ? (string) $data['source'] : '';
        $registry             = Flavor_VBP_Collection_Registry::get_instance();
        $fuente               = $registry->get( $identificador_fuente );

        if ( ! $fuente ) {
            if ( $this->is_editor_context() ) {
                return '<div class="vbp-element vbp-dynamic-list vbp-dynamic-list--placeholder" style="padding:16px;border:1px dashed #cbd5e1;border-radius:8px;color:#64748b;text-align:center;">'
                    . esc_html__( 'Selecciona una colección en el inspector', FLAVOR_PLATFORM_TEXT_DOMAIN )
                    . '</div>';
            }
            return '';
        }

        $raw_query_args = array();
        if ( ! empty( $data['query_args_json'] ) ) {
            $decodificado = json_decode( (string) $data['query_args_json'], true );
            if ( is_array( $decodificado ) ) {
                $raw_query_args = $decodificado;
            }
        }

        $cleaned_args = $registry->sanitize_query_args( $fuente, $raw_query_args );
        $items        = $fuente->query( $cleaned_args );
        $total_items  = $fuente->get_total_count( $cleaned_args );

        $pagina_actual  = isset( $cleaned_args['page'] ) ? (int) $cleaned_args['page'] : 1;
        $items_pagina   = isset( $cleaned_args['limit'] ) ? (int) $cleaned_args['limit'] : count( $items );
        $items_pagina   = max( 1, $items_pagina );
        $total_paginas  = (int) ceil( $total_items / $items_pagina );
        $tiene_mas      = $pagina_actual < $total_paginas;

        if ( empty( $items ) ) {
            $empty_message = isset( $data['empty_message'] ) && $data['empty_message'] !== ''
                ? $data['empty_message']
                : __( 'No hay items disponibles', FLAVOR_PLATFORM_TEXT_DOMAIN );
            return '<div class="vbp-element vbp-dynamic-list vbp-dynamic-list--empty">'
                . esc_html( $empty_message )
                . '</div>';
        }

        // El variant picker del bloque es la fuente autoritativa; si no se
        // ha elegido variante, cae al campo data.template (compat retro).
        $variante_desde_picker = isset( $elemento['variant'] ) ? (string) $elemento['variant'] : '';
        $template_legacy       = isset( $data['template'] ) ? (string) $data['template'] : 'card';
        $template_variant      = $variante_desde_picker !== '' && $variante_desde_picker !== 'default'
            ? $variante_desde_picker
            : $template_legacy;

        $templates_validos = array( 'card', 'list', 'minimal', 'custom' );
        if ( ! in_array( $template_variant, $templates_validos, true ) ) {
            $template_variant = 'card';
        }

        $plantilla_custom_html = '';
        if ( $template_variant === 'custom' ) {
            $plantilla_custom_html = isset( $data['custom_template'] ) ? (string) $data['custom_template'] : '';
            // Si el autor eligió "custom" pero no escribió plantilla,
            // caemos a card para no renderizar vacío.
            if ( trim( $plantilla_custom_html ) === '' ) {
                $template_variant = 'card';
            }
        }

        // Lista de filtros editables por el visitante (frontend). El autor
        // la declara como JSON en data.public_filters; si es un tipo inválido
        // o vacío, no hay filtros visibles al visitante.
        $lista_public_filters = $this->extract_public_filter_names( $data, $fuente );

        $firma_load_more = '';
        if ( ( $tiene_mas || ! empty( $lista_public_filters ) ) && class_exists( 'Flavor_VBP_Query_Signature' ) ) {
            // Se firma siempre que haya paginación o filtros visibles:
            // si hay filtros, el visitante puede disparar una nueva query
            // aunque no haya más páginas por cargar.
            $firma_load_more = Flavor_VBP_Query_Signature::sign(
                $identificador_fuente,
                $cleaned_args,
                $lista_public_filters
            );
        }

        $display_config = $this->extract_dynamic_list_display_config( $data );

        return $this->render_dynamic_list_items(
            $items,
            $template_variant,
            $identificador_fuente,
            array(
                'page'                => $pagina_actual,
                'per_page'            => $items_pagina,
                'total_pages'         => $total_paginas,
                'has_more'            => $tiene_mas,
                'args'                => $cleaned_args,
                'signature'           => $firma_load_more,
                'display'             => $display_config,
                'custom_template'     => $plantilla_custom_html,
                'public_filter_names' => $lista_public_filters,
                'schema_fields'       => $fuente->get_query_fields(),
            )
        );
    }

    /**
     * Parsea data.public_filters y valida contra el schema del source
     * para descartar filtros que no existan (el autor puede haber
     * marcado filtros que el source ya no declara).
     *
     * @param array                          $data    Datos del elemento.
     * @param Flavor_VBP_Collection_Source   $source  Source activa.
     * @return array<int, string> Lista de filter identifiers válidos.
     */
    private function extract_public_filter_names( $data, $source ) {
        $raw = isset( $data['public_filters'] ) ? $data['public_filters'] : '';
        if ( is_array( $raw ) ) {
            $candidatos = $raw;
        } else {
            $decoded = json_decode( (string) $raw, true );
            $candidatos = is_array( $decoded ) ? $decoded : array();
        }

        $schema_fields = $source->get_query_fields();
        $validos = array();
        foreach ( $candidatos as $nombre_filtro ) {
            if ( ! is_string( $nombre_filtro ) ) {
                continue;
            }
            // Excluir page/limit/separators y campos fuera del schema.
            if ( $nombre_filtro === 'page' || $nombre_filtro === 'limit' ) {
                continue;
            }
            if ( substr( $nombre_filtro, 0, 1 ) === '_' ) {
                continue;
            }
            if ( isset( $schema_fields[ $nombre_filtro ] ) ) {
                $validos[] = $nombre_filtro;
            }
        }
        return $validos;
    }

    /**
     * Extrae la configuración de visibilidad de campos del bloque
     * dynamic-list. Mantiene compatibilidad: si el bloque fue guardado
     * antes de existir estos toggles, no tiene las claves y el default
     * es "todo visible".
     *
     * @param array $data Datos del elemento.
     * @return array{show_image: bool, show_date: bool, show_excerpt: bool}
     */
    private function extract_dynamic_list_display_config( $data ) {
        return array(
            'show_image'   => ! isset( $data['show_image'] ) || ! empty( $data['show_image'] ),
            'show_date'    => ! isset( $data['show_date'] ) || ! empty( $data['show_date'] ),
            'show_excerpt' => ! isset( $data['show_excerpt'] ) || ! empty( $data['show_excerpt'] ),
        );
    }

    /**
     * Renderiza los items de una lista dinámica según la plantilla.
     *
     * @param array  $items             Items normalizados devueltos por la Collection.
     * @param string $template_variant  Variante: card|list|minimal.
     * @param string $source_identifier Identificador de la fuente (para data-attributes).
     * @return string
     */
    /**
     * Renderiza un <form> con inputs para los filtros que el visitante
     * puede editar. Cada input corresponde a un schema field y lleva
     * name="f_{nombre}" para poder recuperarlo del querystring.
     *
     * @param array<int, string>              $lista_public_filters
     * @param array<string, array>            $schema_fields
     * @param array<string, mixed>            $valores_actuales  Args saneados (incluye defaults).
     * @return string
     */
    private function render_dynamic_list_public_filters_form( array $lista_public_filters, array $schema_fields, array $valores_actuales ) {
        $html = '<form class="vbp-dynamic-list__filters" role="search" aria-label="' . esc_attr__( 'Filtrar resultados', FLAVOR_PLATFORM_TEXT_DOMAIN ) . '" style="display:flex;flex-wrap:wrap;gap:12px;margin-bottom:20px;padding:16px;background:#f9fafb;border:1px solid #e5e7eb;border-radius:8px;" onsubmit="return false;">';

        foreach ( $lista_public_filters as $nombre_filtro ) {
            if ( ! isset( $schema_fields[ $nombre_filtro ] ) ) {
                continue;
            }
            $config_campo = $schema_fields[ $nombre_filtro ];
            $tipo_campo   = isset( $config_campo['type'] ) ? (string) $config_campo['type'] : 'string';
            $etiqueta     = isset( $config_campo['label'] ) ? (string) $config_campo['label'] : $nombre_filtro;
            $valor_actual = isset( $valores_actuales[ $nombre_filtro ] ) ? $valores_actuales[ $nombre_filtro ] : '';

            $id_input    = 'vbp-filter-' . sanitize_html_class( $nombre_filtro ) . '-' . wp_generate_uuid4();
            $name_input  = 'f_' . $nombre_filtro;

            $html .= '<div class="vbp-dynamic-list__filter-field" style="display:flex;flex-direction:column;gap:4px;min-width:160px;">';
            $html .= '<label for="' . esc_attr( $id_input ) . '" style="font-size:0.8125em;font-weight:600;color:#374151;">' . esc_html( $etiqueta ) . '</label>';

            $html .= $this->render_filter_input_for_type(
                $tipo_campo,
                $id_input,
                $name_input,
                $nombre_filtro,
                $valor_actual,
                $config_campo
            );

            $html .= '</div>';
        }

        $html .= '</form>';
        return $html;
    }

    /**
     * Pinta un input según el tipo de schema field.
     *
     * @param string $tipo_campo
     * @param string $id_input
     * @param string $name_input
     * @param string $filter_name
     * @param mixed  $valor_actual
     * @param array  $config
     * @return string
     */
    private function render_filter_input_for_type( $tipo_campo, $id_input, $name_input, $filter_name, $valor_actual, array $config ) {
        $estilo_base = 'padding:6px 10px;border:1px solid #d1d5db;border-radius:6px;font-size:0.9em;background:#fff;';

        switch ( $tipo_campo ) {
            case 'enum':
                $html = '<select id="' . esc_attr( $id_input ) . '" name="' . esc_attr( $name_input ) . '" data-filter-name="' . esc_attr( $filter_name ) . '" style="' . $estilo_base . '">';
                $opciones = isset( $config['options'] ) ? (array) $config['options'] : array();
                foreach ( $opciones as $opcion ) {
                    $selected = ( (string) $valor_actual === (string) $opcion ) ? ' selected' : '';
                    $html .= '<option value="' . esc_attr( $opcion ) . '"' . $selected . '>' . esc_html( $opcion === '' ? '—' : $opcion ) . '</option>';
                }
                $html .= '</select>';
                return $html;

            case 'int':
                return '<input type="number" id="' . esc_attr( $id_input ) . '" name="' . esc_attr( $name_input ) . '" data-filter-name="' . esc_attr( $filter_name ) . '" value="' . esc_attr( (string) $valor_actual ) . '"'
                    . ( isset( $config['min'] ) ? ' min="' . esc_attr( (string) $config['min'] ) . '"' : '' )
                    . ( isset( $config['max'] ) ? ' max="' . esc_attr( (string) $config['max'] ) . '"' : '' )
                    . ' style="' . $estilo_base . '">';

            case 'date':
                return '<input type="date" id="' . esc_attr( $id_input ) . '" name="' . esc_attr( $name_input ) . '" data-filter-name="' . esc_attr( $filter_name ) . '" value="' . esc_attr( (string) $valor_actual ) . '" style="' . $estilo_base . '">';

            case 'bool':
                $checked = ! empty( $valor_actual ) ? ' checked' : '';
                return '<label style="display:flex;align-items:center;gap:6px;font-size:0.9em;"><input type="checkbox" id="' . esc_attr( $id_input ) . '" name="' . esc_attr( $name_input ) . '" data-filter-name="' . esc_attr( $filter_name ) . '" value="1"' . $checked . '><span>' . esc_html__( 'Sí', FLAVOR_PLATFORM_TEXT_DOMAIN ) . '</span></label>';

            case 'string':
            default:
                return '<input type="search" id="' . esc_attr( $id_input ) . '" name="' . esc_attr( $name_input ) . '" data-filter-name="' . esc_attr( $filter_name ) . '" value="' . esc_attr( (string) $valor_actual ) . '" style="' . $estilo_base . '">';
        }
    }

    /**
     * Sustituye placeholders {{campo}} y {{meta.subcampo}} en una plantilla
     * custom contra el item normalizado. URLs pasan por esc_url; el resto
     * por esc_html. Tras la sustitución, wp_kses_post limpia etiquetas
     * peligrosas que hubiera en el template del autor.
     *
     * @param string $plantilla_html HTML con placeholders.
     * @param array  $item           Item con shape estándar + meta.
     * @return string
     */
    private function apply_dynamic_list_custom_template( $plantilla_html, array $item ) {
        $valor_en_ruta = function ( $ruta ) use ( $item ) {
            $segmentos = explode( '.', $ruta );
            $cursor    = $item;
            foreach ( $segmentos as $segmento ) {
                if ( is_array( $cursor ) && array_key_exists( $segmento, $cursor ) ) {
                    $cursor = $cursor[ $segmento ];
                } else {
                    return '';
                }
            }
            return is_scalar( $cursor ) ? (string) $cursor : '';
        };

        $plantilla_sustituida = preg_replace_callback(
            '/\{\{\s*([a-z0-9_.]+)\s*\}\}/i',
            function ( $coincidencia ) use ( $valor_en_ruta ) {
                $ruta_campo = $coincidencia[1];
                $valor_raw  = $valor_en_ruta( $ruta_campo );

                // URLs e imágenes se escapan como URL; el resto como HTML.
                if ( in_array( $ruta_campo, array( 'url', 'image' ), true ) ) {
                    return esc_url( $valor_raw );
                }
                return esc_html( $valor_raw );
            },
            $plantilla_html
        );

        // Defensa: por defecto wp_kses_post elimina <iframe>, <script> y
        // otros peligrosos. El admin puede permitir iframes específicos
        // vía el filtro flavor_vbp_custom_template_iframe_hosts.
        return $this->sanitize_template_html( $plantilla_sustituida );
    }

    /**
     * Sanea HTML de plantilla custom aplicando allowlist de iframes.
     *
     * Si ningún host está permitido (default), strip-total con wp_kses_post.
     * Si el admin permite hosts, se extiende allowed_html con iframe y
     * luego se filtran post-proceso los iframes cuyo src no coincide.
     *
     * @param string $html HTML con placeholders ya sustituidos.
     * @return string
     */
    private function sanitize_template_html( $html ) {
        $hosts_permitidos = (array) apply_filters( 'flavor_vbp_custom_template_iframe_hosts', array() );
        $hosts_permitidos = array_filter( array_map( 'strval', $hosts_permitidos ) );

        if ( empty( $hosts_permitidos ) ) {
            return wp_kses_post( $html );
        }

        $allowed_tags               = wp_kses_allowed_html( 'post' );
        $allowed_tags['iframe']     = array(
            'src'             => true,
            'width'           => true,
            'height'          => true,
            'frameborder'     => true,
            'allowfullscreen' => true,
            'allow'           => true,
            'title'           => true,
            'loading'         => true,
            'referrerpolicy'  => true,
            'sandbox'         => true,
            'class'           => true,
            'style'           => true,
        );

        $html_sanitizado = wp_kses( $html, $allowed_tags );

        return $this->filter_iframes_by_host_allowlist( $html_sanitizado, $hosts_permitidos );
    }

    /**
     * Elimina <iframe> cuyo host de src NO esté en la allowlist.
     * Los iframes sin src, con src vacío, o con host no coincidente se
     * reemplazan por cadena vacía. Los iframes válidos se preservan.
     *
     * @param string            $html_sanitizado
     * @param array<int,string> $hosts_permitidos Lista de substrings de host.
     * @return string
     */
    private function filter_iframes_by_host_allowlist( $html_sanitizado, array $hosts_permitidos ) {
        return preg_replace_callback(
            '/<iframe\b([^>]*)(\/?>)(?:[^<]*<\/iframe>)?/i',
            function ( $coincidencia ) use ( $hosts_permitidos ) {
                $atributos_iframe = $coincidencia[1];

                if ( ! preg_match( '/\bsrc\s*=\s*["\']([^"\']+)["\']/i', $atributos_iframe, $captura_src ) ) {
                    return '';
                }

                $url_src = $captura_src[1];
                $partes  = wp_parse_url( $url_src );
                $host    = isset( $partes['host'] ) ? strtolower( $partes['host'] ) : '';

                if ( $host === '' ) {
                    return '';
                }

                foreach ( $hosts_permitidos as $host_permitido ) {
                    $host_permitido = strtolower( trim( $host_permitido ) );
                    if ( $host_permitido === '' ) {
                        continue;
                    }
                    // Match exacto o subdominio (p.ej. 'youtube.com' matchea
                    // 'www.youtube.com' y 'www-dev.youtube.com').
                    if ( $host === $host_permitido || substr( $host, -1 * ( strlen( $host_permitido ) + 1 ) ) === '.' . $host_permitido ) {
                        return $coincidencia[0];
                    }
                }

                return '';
            },
            $html_sanitizado
        );
    }

    private function render_dynamic_list_items( array $items, $template_variant, $source_identifier, array $pagination_meta = array() ) {
        $clases_envoltorio = 'vbp-dynamic-list-wrapper';
        $clases_contenedor = 'vbp-element vbp-dynamic-list vbp-dynamic-list--' . sanitize_html_class( $template_variant );

        // Layout distinto según variante: grid responsivo para cards,
        // stack vertical para list, minimal sin estilo.
        $estilos_contenedor = '';
        if ( $template_variant === 'card' ) {
            $estilos_contenedor = 'display:grid;grid-template-columns:repeat(auto-fill,minmax(260px,1fr));gap:20px;';
        }

        // Config de visibilidad de campos del item. Defaults todos true
        // para mantener compatibilidad con bloques antiguos.
        $display_config = isset( $pagination_meta['display'] ) && is_array( $pagination_meta['display'] )
            ? $pagination_meta['display']
            : array( 'show_image' => true, 'show_date' => true, 'show_excerpt' => true );

        $plantilla_custom_html = isset( $pagination_meta['custom_template'] ) ? (string) $pagination_meta['custom_template'] : '';
        $lista_public_filters  = isset( $pagination_meta['public_filter_names'] ) && is_array( $pagination_meta['public_filter_names'] )
            ? $pagination_meta['public_filter_names']
            : array();
        $schema_fields         = isset( $pagination_meta['schema_fields'] ) && is_array( $pagination_meta['schema_fields'] )
            ? $pagination_meta['schema_fields']
            : array();

        // Data-attributes para el botón "Cargar más" del frontend. Se
        // incluyen siempre que haya pagination_meta; si has_more es false
        // el botón no se renderiza, pero los atributos de contexto quedan
        // para debugging y para usos futuros (contador).
        $wrapper_data_attrs = '';
        $signature_load_more = isset( $pagination_meta['signature'] ) ? (string) $pagination_meta['signature'] : '';
        if ( ! empty( $pagination_meta ) ) {
            $args_json     = isset( $pagination_meta['args'] ) ? wp_json_encode( $pagination_meta['args'] ) : '{}';
            $current_page  = isset( $pagination_meta['page'] ) ? (int) $pagination_meta['page'] : 1;
            $total_pages   = isset( $pagination_meta['total_pages'] ) ? (int) $pagination_meta['total_pages'] : 1;
            $display_json  = wp_json_encode( $display_config );
            $wrapper_data_attrs = sprintf(
                ' data-source="%s" data-args="%s" data-signature="%s" data-page="%d" data-total-pages="%d" data-template="%s" data-display="%s"',
                esc_attr( $source_identifier ),
                esc_attr( $args_json ),
                esc_attr( $signature_load_more ),
                $current_page,
                $total_pages,
                esc_attr( $template_variant ),
                esc_attr( $display_json )
            );
            if ( $plantilla_custom_html !== '' ) {
                $wrapper_data_attrs .= ' data-custom-template="' . esc_attr( $plantilla_custom_html ) . '"';
            }
            if ( ! empty( $lista_public_filters ) ) {
                $wrapper_data_attrs .= ' data-public-filters="' . esc_attr( wp_json_encode( $lista_public_filters ) ) . '"';
            }
        }

        $html = '<div class="' . esc_attr( $clases_envoltorio ) . '"' . $wrapper_data_attrs . '>';

        // Form de filtros públicos arriba del grid (si el autor marcó alguno).
        if ( ! empty( $lista_public_filters ) ) {
            $html .= $this->render_dynamic_list_public_filters_form(
                $lista_public_filters,
                $schema_fields,
                isset( $pagination_meta['args'] ) ? (array) $pagination_meta['args'] : array()
            );
        }

        // aria-live="polite" para que los items appendados vía "Cargar más"
        // sean anunciados por screen readers. role=list refuerza la semántica
        // para tecnologías asistivas cuando el grid usa display:grid.
        $html .= '<div class="' . esc_attr( $clases_contenedor ) . '" data-source="' . esc_attr( $source_identifier ) . '" role="list" aria-live="polite" aria-busy="false"';
        if ( $estilos_contenedor !== '' ) {
            $html .= ' style="' . esc_attr( $estilos_contenedor ) . '"';
        }
        $html .= '>';

        foreach ( $items as $item ) {
            if ( $template_variant === 'custom' && $plantilla_custom_html !== '' ) {
                $html .= '<div class="vbp-dynamic-list__item vbp-dynamic-list__item--custom" role="listitem">'
                    . $this->apply_dynamic_list_custom_template( $plantilla_custom_html, $item )
                    . '</div>';
            } else {
                $html .= $this->render_dynamic_list_item( $item, $template_variant, $display_config );
            }
        }

        $html .= '</div>';

        if ( ! empty( $pagination_meta['has_more'] ) ) {
            $html .= '<button type="button" class="vbp-dynamic-list__load-more" style="display:block;margin:24px auto 0;padding:10px 24px;background:#3b82f6;color:#fff;border:0;border-radius:6px;cursor:pointer;font-size:0.95em;">'
                . esc_html__( 'Cargar más', FLAVOR_PLATFORM_TEXT_DOMAIN )
                . '</button>';
        }

        $html .= '</div>';
        return $html;
    }

    /**
     * Versión pública de render_dynamic_list_item para que el endpoint
     * REST "load more" pueda generar HTML del mismo formato sin tener
     * que duplicar la plantilla.
     *
     * @param array  $item
     * @param string $template_variant
     * @return string
     */
    public function render_dynamic_list_item_public( array $item, $template_variant, array $display_config = array(), $custom_template_html = '' ) {
        $templates_validos = array( 'card', 'list', 'minimal', 'custom' );
        if ( ! in_array( $template_variant, $templates_validos, true ) ) {
            $template_variant = 'card';
        }
        if ( empty( $display_config ) ) {
            $display_config = array( 'show_image' => true, 'show_date' => true, 'show_excerpt' => true );
        }

        if ( $template_variant === 'custom' && is_string( $custom_template_html ) && $custom_template_html !== '' ) {
            return '<div class="vbp-dynamic-list__item vbp-dynamic-list__item--custom" role="listitem">'
                . $this->apply_dynamic_list_custom_template( $custom_template_html, $item )
                . '</div>';
        }

        return $this->render_dynamic_list_item( $item, $template_variant === 'custom' ? 'card' : $template_variant, $display_config );
    }

    /**
     * Renderiza un único item según la plantilla y la config de visibilidad.
     *
     * @param array  $item              Item normalizado con keys id, title, excerpt, image, url, date.
     * @param string $template_variant  Variante: card|list|minimal.
     * @param array  $display_config    Toggles show_image, show_date, show_excerpt.
     * @return string
     */
    private function render_dynamic_list_item( array $item, $template_variant, array $display_config = array( 'show_image' => true, 'show_date' => true, 'show_excerpt' => true ) ) {
        $titulo_item   = isset( $item['title'] ) ? (string) $item['title'] : '';
        $extracto_item = isset( $item['excerpt'] ) ? (string) $item['excerpt'] : '';
        $imagen_url    = isset( $item['image'] ) ? (string) $item['image'] : '';
        $enlace_item   = isset( $item['url'] ) ? (string) $item['url'] : '#';
        $fecha_item    = isset( $item['date'] ) ? (string) $item['date'] : '';

        $mostrar_imagen   = ! empty( $display_config['show_image'] );
        $mostrar_fecha    = ! empty( $display_config['show_date'] );
        $mostrar_extracto = ! empty( $display_config['show_excerpt'] );

        $enlace_titulo = '<a href="' . esc_url( $enlace_item ) . '" class="vbp-dynamic-list__title-link">' . esc_html( $titulo_item ) . '</a>';

        if ( $template_variant === 'minimal' ) {
            return '<div class="vbp-dynamic-list__item vbp-dynamic-list__item--minimal" role="listitem"><h4 class="vbp-dynamic-list__title">' . $enlace_titulo . '</h4></div>';
        }

        if ( $template_variant === 'list' ) {
            return '<div class="vbp-dynamic-list__item vbp-dynamic-list__item--list" role="listitem" style="padding:12px 0;border-bottom:1px solid #e5e7eb;">'
                . '<h4 class="vbp-dynamic-list__title" style="margin:0 0 4px;">' . $enlace_titulo . '</h4>'
                . ( $mostrar_fecha && $fecha_item ? '<time class="vbp-dynamic-list__date" style="color:#6b7280;font-size:0.875em;">' . esc_html( $fecha_item ) . '</time>' : '' )
                . ( $mostrar_extracto && $extracto_item ? '<p class="vbp-dynamic-list__excerpt" style="margin:4px 0 0;color:#4b5563;">' . esc_html( $extracto_item ) . '</p>' : '' )
                . '</div>';
        }

        // Card (por defecto)
        $imagen_html = '';
        if ( $mostrar_imagen && $imagen_url ) {
            $imagen_html = '<div class="vbp-dynamic-list__image" style="aspect-ratio:16/9;background:#f3f4f6 url(' . esc_url( $imagen_url ) . ') center/cover no-repeat;border-radius:8px 8px 0 0;"></div>';
        }

        return '<div class="vbp-dynamic-list__item vbp-dynamic-list__item--card" role="listitem" style="border:1px solid #e5e7eb;border-radius:8px;overflow:hidden;background:#fff;display:flex;flex-direction:column;">'
            . $imagen_html
            . '<div class="vbp-dynamic-list__body" style="padding:16px;">'
            . '<h3 class="vbp-dynamic-list__title" style="margin:0 0 8px;font-size:1.125em;">' . $enlace_titulo . '</h3>'
            . ( $mostrar_fecha && $fecha_item ? '<time class="vbp-dynamic-list__date" style="display:block;color:#6b7280;font-size:0.875em;margin-bottom:8px;">' . esc_html( $fecha_item ) . '</time>' : '' )
            . ( $mostrar_extracto && $extracto_item ? '<p class="vbp-dynamic-list__excerpt" style="margin:0;color:#4b5563;">' . esc_html( $extracto_item ) . '</p>' : '' )
            . '</div>'
            . '</div>';
    }

    /**
     * Renderizado genérico
     */
    private function render_generico( $elemento ) {
        $tipo             = $elemento['type'] ?? 'unknown';
        $nombre           = $elemento['name'] ?? $tipo;
        $estilos          = $elemento['styles'] ?? array();
        $estilos_css      = $this->generar_estilos_elemento( $estilos );
        $estilos_anim     = $this->generar_estilos_animacion( $estilos );
        $clases_anim      = $this->generar_clases_animacion( $estilos );
        $atributos_anim   = $this->generar_atributos_animacion( $estilos );

        $estilo_all = trim( $estilos_css . ( $estilos_anim ? '; ' . $estilos_anim : '' ) );
        $clases     = trim( 'vbp-element vbp-element--' . esc_attr( $tipo ) . ' ' . $clases_anim );

        return '<div class="' . esc_attr( $clases ) . '" style="' . esc_attr( $estilo_all ) . '" ' . $atributos_anim . '>' . esc_html( $nombre ) . '</div>';
    }

    /**
     * Renderiza un componente carrusel
     *
     * @param array $elemento Datos del elemento.
     * @return string HTML del carrusel.
     */
    private function render_carousel( $elemento ) {
        $datos           = $elemento['data'] ?? array();
        $variante        = $elemento['variant'] ?? 'simple';
        $estilos         = $elemento['styles'] ?? array();
        $items           = $datos['items'] ?? array();
        $autoplay        = isset( $datos['autoplay'] ) ? $datos['autoplay'] : true;
        $intervalo       = isset( $datos['intervalo'] ) ? intval( $datos['intervalo'] ) : 5;
        $mostrar_flechas = isset( $datos['mostrar_flechas'] ) ? $datos['mostrar_flechas'] : true;
        $mostrar_dots    = isset( $datos['mostrar_dots'] ) ? $datos['mostrar_dots'] : true;
        $loop            = isset( $datos['loop'] ) ? $datos['loop'] : true;
        $slides_visibles = isset( $datos['slides_visibles'] ) ? intval( $datos['slides_visibles'] ) : 1;
        $efecto          = $datos['efecto_transicion'] ?? 'slide';

        $estilos_css     = $this->generar_estilos_elemento( $estilos );
        $clases_base     = 'vbp-carousel vbp-carousel--' . esc_attr( $variante );

        $html = '<div class="' . esc_attr( $clases_base ) . '" style="' . esc_attr( $estilos_css ) . '"';
        $html .= ' data-autoplay="' . ( $autoplay ? 'true' : 'false' ) . '"';
        $html .= ' data-interval="' . esc_attr( $intervalo * 1000 ) . '"';
        $html .= ' data-loop="' . ( $loop ? 'true' : 'false' ) . '"';
        $html .= ' data-slides-visible="' . esc_attr( $slides_visibles ) . '"';
        $html .= ' data-effect="' . esc_attr( $efecto ) . '">';

        // Track de slides
        $html .= '<div class="vbp-carousel__track">';
        foreach ( $items as $indice => $item ) {
            $html .= '<div class="vbp-carousel__slide">';
            if ( ! empty( $item['imagen'] ) ) {
                $html .= '<img src="' . esc_url( $item['imagen'] ) . '" alt="' . esc_attr( $item['titulo'] ?? '' ) . '" class="vbp-carousel__image">';
            }
            if ( ! empty( $item['titulo'] ) || ! empty( $item['descripcion'] ) ) {
                $html .= '<div class="vbp-carousel__content">';
                if ( ! empty( $item['titulo'] ) ) {
                    $html .= '<h3 class="vbp-carousel__title"' . $this->get_editor_contenteditable_path_attr( 'items.' . $indice . '.titulo' ) . '>' . esc_html( $item['titulo'] ) . '</h3>';
                }
                if ( ! empty( $item['descripcion'] ) ) {
                    $html .= '<p class="vbp-carousel__description"' . $this->get_editor_contenteditable_path_attr( 'items.' . $indice . '.descripcion' ) . '>' . wp_kses_post( $item['descripcion'] ) . '</p>';
                }
                if ( ! empty( $item['enlace_url'] ) ) {
                    $texto_enlace = ! empty( $item['enlace_texto'] ) ? $item['enlace_texto'] : __( 'Ver más', FLAVOR_PLATFORM_TEXT_DOMAIN );
                    $html .= '<a href="' . esc_url( $item['enlace_url'] ) . '" class="vbp-carousel__link"' . $this->get_editor_link_path_attr( 'items.' . $indice . '.enlace_texto' ) . '>' . esc_html( $texto_enlace ) . '</a>';
                }
                $html .= '</div>';
            }
            $html .= '</div>';
        }
        $html .= '</div>';

        // Flechas de navegación
        if ( $mostrar_flechas ) {
            $html .= '<button type="button" class="vbp-carousel__arrow vbp-carousel__arrow--prev" aria-label="' . esc_attr__( 'Anterior', FLAVOR_PLATFORM_TEXT_DOMAIN ) . '">';
            $html .= '<svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="15 18 9 12 15 6"/></svg>';
            $html .= '</button>';
            $html .= '<button type="button" class="vbp-carousel__arrow vbp-carousel__arrow--next" aria-label="' . esc_attr__( 'Siguiente', FLAVOR_PLATFORM_TEXT_DOMAIN ) . '">';
            $html .= '<svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="9 18 15 12 9 6"/></svg>';
            $html .= '</button>';
        }

        // Indicadores (dots)
        if ( $mostrar_dots && count( $items ) > 1 ) {
            $html .= '<div class="vbp-carousel__dots">';
            foreach ( $items as $indice => $item ) {
                $activo = 0 === $indice ? ' vbp-carousel__dot--active' : '';
                $html .= '<button type="button" class="vbp-carousel__dot' . $activo . '" data-index="' . esc_attr( $indice ) . '" aria-label="' . esc_attr( sprintf( __( 'Ir al slide %d', FLAVOR_PLATFORM_TEXT_DOMAIN ), $indice + 1 ) ) . '"></button>';
            }
            $html .= '</div>';
        }

        $html .= '</div>';

        return $html;
    }

    /**
     * Renderiza un componente de pestañas (tabs)
     *
     * @param array $elemento Datos del elemento.
     * @return string HTML de las pestañas.
     */
    private function render_tabs( $elemento ) {
        $datos           = $elemento['data'] ?? array();
        $variante        = $elemento['variant'] ?? 'horizontal';
        $estilos         = $elemento['styles'] ?? array();
        $tabs            = $datos['tabs'] ?? array();
        $tab_activa      = isset( $datos['tab_activa_defecto'] ) ? intval( $datos['tab_activa_defecto'] ) : 0;
        $alineacion      = $datos['alineacion_tabs'] ?? 'left';
        $animacion       = isset( $datos['animacion'] ) ? $datos['animacion'] : true;

        // Datos de sección.
        $titulo          = $datos['titulo'] ?? '';
        $titulo_color    = $datos['titulo_color'] ?? '#ffffff';
        $subtitulo       = $datos['subtitulo'] ?? '';
        $subtitulo_color = $datos['subtitulo_color'] ?? '#9CA3AF';
        $color_fondo     = $datos['color_fondo'] ?? 'transparent';

        $estilos_css     = $this->generar_estilos_elemento( $estilos );
        $clases_tabs     = 'vbp-tabs vbp-tabs--' . esc_attr( $variante );
        $clases_tabs    .= ' vbp-tabs--align-' . esc_attr( $alineacion );
        if ( $animacion ) {
            $clases_tabs .= ' vbp-tabs--animated';
        }

        $id_unico = 'vbp-tabs-' . wp_rand( 1000, 9999 );

        // Wrapper de sección.
        $html = '<section class="vbp-section vbp-section--tabs" style="background-color: ' . esc_attr( $color_fondo ) . '; ' . esc_attr( $estilos_css ) . '">';
        $html .= '<div class="vbp-container">';

        $html .= $this->render_section_header( $titulo, $subtitulo, $titulo_color, $subtitulo_color );

        // Tabs content.
        $html .= '<div class="' . esc_attr( $clases_tabs ) . '" id="' . esc_attr( $id_unico ) . '">';

        // Navegación de tabs.
        $html .= '<div class="vbp-tabs__nav" role="tablist">';
        foreach ( $tabs as $indice => $tab ) {
            $activa = $indice === $tab_activa ? ' vbp-tabs__button--active' : '';
            $html .= '<button type="button" class="vbp-tabs__button' . $activa . '" role="tab"';
            $html .= ' aria-selected="' . ( $indice === $tab_activa ? 'true' : 'false' ) . '"';
            $html .= ' aria-controls="' . esc_attr( $id_unico . '-panel-' . $indice ) . '"';
            $html .= ' data-index="' . esc_attr( $indice ) . '">';
            if ( ! empty( $tab['icono'] ) ) {
                $html .= '<span class="vbp-tabs__icon material-icons">' . esc_html( $tab['icono'] ) . '</span>';
            }
            $html .= '<span class="vbp-tabs__label"' . $this->get_editor_contenteditable_path_attr( 'tabs.' . $indice . '.titulo' ) . '>' . esc_html( $tab['titulo'] ?? __( 'Tab', FLAVOR_PLATFORM_TEXT_DOMAIN ) ) . '</span>';
            $html .= '</button>';
        }
        $html .= '</div>';

        // Contenido de tabs.
        $html .= '<div class="vbp-tabs__content">';
        foreach ( $tabs as $indice => $tab ) {
            $activa = $indice === $tab_activa ? ' vbp-tabs__panel--active' : '';
            $html .= '<div class="vbp-tabs__panel' . $activa . '" role="tabpanel"';
            $html .= ' id="' . esc_attr( $id_unico . '-panel-' . $indice ) . '"';
            $html .= ' aria-hidden="' . ( $indice === $tab_activa ? 'false' : 'true' ) . '">';
            $html .= '<div class="vbp-tabs__panel-body"' . $this->get_editor_contenteditable_path_attr( 'tabs.' . $indice . '.contenido' ) . '>' . wp_kses_post( $tab['contenido'] ?? '' ) . '</div>';
            $html .= '</div>';
        }
        $html .= '</div>';

        $html .= '</div>'; // .vbp-tabs
        $html .= '</div>'; // .vbp-container
        $html .= '</section>';

        return $html;
    }

    /**
     * Renderiza un componente acordeón
     *
     * @param array $elemento Datos del elemento.
     * @return string HTML del acordeón.
     */
    private function render_accordion( $elemento ) {
        $datos             = $elemento['data'] ?? array();
        $variante          = $elemento['variant'] ?? 'simple';
        $estilos           = $elemento['styles'] ?? array();
        $items             = $datos['items'] ?? array();
        $multiple_abiertos = isset( $datos['multiple_abiertos'] ) ? $datos['multiple_abiertos'] : false;
        $icono_tipo        = $datos['icono_expandir'] ?? 'chevron';
        $animacion         = isset( $datos['animacion'] ) ? $datos['animacion'] : true;

        // Datos de sección.
        $titulo          = $datos['titulo'] ?? '';
        $titulo_color    = $datos['titulo_color'] ?? '#ffffff';
        $subtitulo       = $datos['subtitulo'] ?? '';
        $subtitulo_color = $datos['subtitulo_color'] ?? '#9CA3AF';
        $color_fondo     = $datos['color_fondo'] ?? 'transparent';

        $estilos_css        = $this->generar_estilos_elemento( $estilos );
        $clases_accordion   = 'vbp-accordion vbp-accordion--' . esc_attr( $variante );
        if ( $animacion ) {
            $clases_accordion .= ' vbp-accordion--animated';
        }

        $icono_svg = $this->get_accordion_icon( $icono_tipo );

        // Wrapper de sección.
        $html = '<section class="vbp-section vbp-section--accordion" style="background-color: ' . esc_attr( $color_fondo ) . '; ' . esc_attr( $estilos_css ) . '">';
        $html .= '<div class="vbp-container">';

        $html .= $this->render_section_header( $titulo, $subtitulo, $titulo_color, $subtitulo_color );

        // Accordion content.
        $html .= '<div class="' . esc_attr( $clases_accordion ) . '"';
        $html .= ' data-allow-multiple="' . ( $multiple_abiertos ? 'true' : 'false' ) . '">';

        foreach ( $items as $indice => $item ) {
            $abierto = isset( $item['abierto'] ) && $item['abierto'] ? true : false;
            $clases_item = 'vbp-accordion__item';
            if ( $abierto ) {
                $clases_item .= ' vbp-accordion__item--open';
            }

            $html .= '<div class="' . esc_attr( $clases_item ) . '">';
            $html .= '<button type="button" class="vbp-accordion__header" aria-expanded="' . ( $abierto ? 'true' : 'false' ) . '">';
            if ( ! empty( $item['icono'] ) ) {
                $html .= '<span class="vbp-accordion__item-icon">' . wp_kses_post( $item['icono'] ) . '</span>';
            }
            $html .= '<span class="vbp-accordion__title"' . $this->get_editor_contenteditable_path_attr( 'items.' . $indice . '.titulo' ) . '>' . esc_html( $item['titulo'] ?? '' ) . '</span>';
            $html .= '<span class="vbp-accordion__icon">' . $icono_svg . '</span>';
            $html .= '</button>';
            $html .= '<div class="vbp-accordion__content"' . ( $abierto ? '' : ' style="display: none;"' ) . '>';
            $html .= '<div class="vbp-accordion__body"' . $this->get_editor_contenteditable_path_attr( 'items.' . $indice . '.contenido' ) . '>' . wp_kses_post( $item['contenido'] ?? '' ) . '</div>';
            $html .= '</div>';
            $html .= '</div>';
        }

        $html .= '</div>'; // .vbp-accordion
        $html .= '</div>'; // .vbp-container
        $html .= '</section>';

        return $html;
    }

    /**
     * Obtiene el SVG del icono del acordeón según el tipo
     *
     * @param string $tipo Tipo de icono.
     * @return string SVG del icono.
     */
    private function get_accordion_icon( $tipo ) {
        switch ( $tipo ) {
            case 'plus':
                return '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>';
            case 'arrow':
                return '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="12" y1="5" x2="12" y2="19"/><polyline points="19 12 12 19 5 12"/></svg>';
            case 'chevron':
            default:
                return '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="6 9 12 15 18 9"/></svg>';
        }
    }

    /**
     * Renderiza un componente línea de tiempo (timeline)
     *
     * @param array $elemento Datos del elemento.
     * @return string HTML de la línea de tiempo.
     */
    private function render_timeline( $elemento ) {
        $datos              = $elemento['data'] ?? array();
        $variante           = $elemento['variant'] ?? 'vertical';
        $estilos            = $elemento['styles'] ?? array();
        $eventos            = $datos['eventos'] ?? array();
        $color_linea        = $datos['color_linea'] ?? '#3b82f6';
        $color_marcador     = $datos['color_marcador'] ?? '#3b82f6';
        $animacion_scroll   = isset( $datos['animacion_scroll'] ) ? $datos['animacion_scroll'] : true;
        $mostrar_conectores = isset( $datos['mostrar_conectores'] ) ? $datos['mostrar_conectores'] : true;

        // Datos de sección.
        $titulo          = $datos['titulo'] ?? '';
        $titulo_color    = $datos['titulo_color'] ?? '#ffffff';
        $subtitulo       = $datos['subtitulo'] ?? '';
        $subtitulo_color = $datos['subtitulo_color'] ?? '#9CA3AF';
        $color_fondo     = $datos['color_fondo'] ?? 'transparent';

        $estilos_css     = $this->generar_estilos_elemento( $estilos );
        $clases_timeline = 'vbp-timeline vbp-timeline--' . esc_attr( $variante );
        if ( $animacion_scroll ) {
            $clases_timeline .= ' vbp-timeline--animated';
        }

        $estilos_timeline = '--timeline-line-color: ' . $this->map_color_to_variable( $color_linea ) . ';';
        $estilos_timeline .= '--timeline-marker-color: ' . $this->map_color_to_variable( $color_marcador ) . ';';

        // Wrapper de sección.
        $html = '<section class="vbp-section vbp-section--timeline" style="background-color: ' . esc_attr( $color_fondo ) . '; ' . esc_attr( $estilos_css ) . '">';
        $html .= '<div class="vbp-container">';

        $html .= $this->render_section_header( $titulo, $subtitulo, $titulo_color, $subtitulo_color );

        // Timeline content.
        $html .= '<div class="' . esc_attr( $clases_timeline ) . '" style="' . esc_attr( $estilos_timeline ) . '">';

        if ( $mostrar_conectores && 'horizontal' !== $variante ) {
            $html .= '<div class="vbp-timeline__line"></div>';
        }

        foreach ( $eventos as $indice => $evento ) {
            $estado       = $evento['estado'] ?? 'completed';
            $color_evento = ! empty( $evento['color'] ) ? $evento['color'] : $color_marcador;
            $clases_item  = 'vbp-timeline__item vbp-timeline__item--' . esc_attr( $estado );

            $html .= '<div class="' . esc_attr( $clases_item ) . '"' . ( $animacion_scroll ? ' data-animation="fade-up"' : '' ) . '>';

            // Marcador.
            $html .= '<div class="vbp-timeline__marker" style="background-color: ' . esc_attr( $this->map_color_to_variable( $color_evento ) ) . ';">';
            if ( ! empty( $evento['icono'] ) ) {
                $icono = $evento['icono'];
                if ( strpos( $icono, 'fa-' ) === 0 ) {
                    // Font Awesome icon
                    $html .= '<span class="vbp-timeline__marker-icon"><i class="fas ' . esc_attr( $icono ) . '"></i></span>';
                } else {
                    // Material Icons
                    $html .= '<span class="vbp-timeline__marker-icon"><span class="material-icons">' . esc_html( $icono ) . '</span></span>';
                }
            }
            $html .= '</div>';

            // Contenido.
            $html .= '<div class="vbp-timeline__content">';
            if ( ! empty( $evento['fecha'] ) ) {
                $html .= '<span class="vbp-timeline__date"' . $this->get_editor_contenteditable_path_attr( 'eventos.' . $indice . '.fecha' ) . '>' . esc_html( $evento['fecha'] ) . '</span>';
            }
            if ( ! empty( $evento['titulo'] ) ) {
                $html .= '<h4 class="vbp-timeline__title"' . $this->get_editor_contenteditable_path_attr( 'eventos.' . $indice . '.titulo' ) . '>' . esc_html( $evento['titulo'] ) . '</h4>';
            }
            if ( ! empty( $evento['descripcion'] ) ) {
                $html .= '<p class="vbp-timeline__description"' . $this->get_editor_contenteditable_path_attr( 'eventos.' . $indice . '.descripcion' ) . '>' . wp_kses_post( $evento['descripcion'] ) . '</p>';
            }
            if ( ! empty( $evento['imagen'] ) ) {
                $html .= '<img src="' . esc_url( $evento['imagen'] ) . '" alt="' . esc_attr( $evento['titulo'] ?? '' ) . '" class="vbp-timeline__image">';
            }
            $html .= '</div>';

            $html .= '</div>';
        }

        $html .= '</div>'; // .vbp-timeline
        $html .= '</div>'; // .vbp-container
        $html .= '</section>';

        return $html;
    }

    /**
     * Renderiza un componente de listado de eventos
     *
     * @param array $elemento Datos del elemento.
     * @return string HTML del listado de eventos.
     */
    private function render_eventos_listado( $elemento ) {
        $datos            = $elemento['data'] ?? array();
        $estilos          = $elemento['styles'] ?? array();
        $titulo           = $datos['titulo'] ?? '';
        $titulo_color     = $datos['titulo_color'] ?? '#ffffff';
        $subtitulo        = $datos['subtitulo'] ?? '';
        $subtitulo_color  = $datos['subtitulo_color'] ?? '#9CA3AF';
        $color_fondo      = $datos['color_fondo'] ?? '#0f0f0f';
        $limite           = isset( $datos['limite'] ) ? intval( $datos['limite'] ) : 6;
        $vista            = $datos['vista'] ?? 'grid';
        $mostrar_filtros  = isset( $datos['mostrar_filtros'] ) ? $datos['mostrar_filtros'] : false;
        $solo_proximos    = isset( $datos['solo_proximos'] ) ? $datos['solo_proximos'] : true;
        $columnas         = isset( $datos['columnas'] ) ? intval( $datos['columnas'] ) : 3;

        $estilos_css      = $this->generar_estilos_elemento( $estilos );
        $clases_base      = 'vbp-eventos-listado vbp-section';

        // Construir atributos del shortcode.
        $shortcode_atts = array(
            'limite'          => $limite,
            'columnas'        => $columnas,
            'mostrar_filtros' => $mostrar_filtros ? 'true' : 'false',
        );

        if ( $solo_proximos ) {
            $shortcode_atts['solo_proximos'] = 'true';
        }

        $atts_string = '';
        foreach ( $shortcode_atts as $key => $value ) {
            $atts_string .= ' ' . $key . '="' . esc_attr( $value ) . '"';
        }

        $html = '<section class="' . esc_attr( $clases_base ) . '" style="background-color: ' . esc_attr( $color_fondo ) . '; ' . esc_attr( $estilos_css ) . '">';
        $html .= '<div class="vbp-container">';

        $html .= $this->render_section_header( $titulo, $subtitulo, $titulo_color, $subtitulo_color );

        // Contenido del shortcode.
        $html .= '<div class="vbp-eventos-listado__content">';
        $html .= do_shortcode( '[eventos_listado' . $atts_string . ']' );
        $html .= '</div>';

        $html .= '</div>';
        $html .= '</section>';

        return $html;
    }

    /**
     * Renderiza un componente de calendario de eventos
     *
     * @param array $elemento Datos del elemento.
     * @return string HTML del calendario de eventos.
     */
    private function render_eventos_calendario( $elemento ) {
        $datos              = $elemento['data'] ?? array();
        $estilos            = $elemento['styles'] ?? array();
        $titulo             = $datos['titulo'] ?? '';
        $titulo_color       = $datos['titulo_color'] ?? '#ffffff';
        $subtitulo          = $datos['subtitulo'] ?? '';
        $subtitulo_color    = $datos['subtitulo_color'] ?? '#9CA3AF';
        $color_fondo        = $datos['color_fondo'] ?? '#111827';
        $vista_inicial      = $datos['vista_inicial'] ?? 'month';
        $mostrar_controles  = isset( $datos['mostrar_controles'] ) ? $datos['mostrar_controles'] : true;

        $estilos_css        = $this->generar_estilos_elemento( $estilos );
        $clases_base        = 'vbp-eventos-calendario vbp-section';

        // Construir atributos del shortcode.
        $shortcode_atts = array(
            'vista'             => $vista_inicial,
            'mostrar_controles' => $mostrar_controles ? 'true' : 'false',
        );

        $atts_string = '';
        foreach ( $shortcode_atts as $key => $value ) {
            $atts_string .= ' ' . $key . '="' . esc_attr( $value ) . '"';
        }

        $html = '<section class="' . esc_attr( $clases_base ) . '" style="background-color: ' . esc_attr( $color_fondo ) . '; ' . esc_attr( $estilos_css ) . '">';
        $html .= '<div class="vbp-container">';

        $html .= $this->render_section_header( $titulo, $subtitulo, $titulo_color, $subtitulo_color );

        // Contenido del shortcode.
        $html .= '<div class="vbp-eventos-calendario__content">';
        $html .= do_shortcode( '[eventos_calendario' . $atts_string . ']' );
        $html .= '</div>';

        $html .= '</div>';
        $html .= '</section>';

        return $html;
    }
}

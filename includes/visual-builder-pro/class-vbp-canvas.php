<?php
/**
 * Visual Builder Pro - Canvas Renderer
 *
 * Renderizado de elementos para el canvas y frontend.
 *
 * @package Flavor_Chat_IA
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

        $css_url = FLAVOR_CHAT_IA_URL . 'assets/vbp/css/frontend-components.css';
        $css_path = FLAVOR_CHAT_IA_PATH . 'assets/vbp/css/frontend-components.css';

        if ( file_exists( $css_path ) ) {
            wp_enqueue_style(
                'vbp-frontend-components',
                $css_url,
                array(),
                '2.0.0'
            );
        }

        // Cargar CSS de animaciones
        $anim_css_url = FLAVOR_CHAT_IA_URL . 'assets/vbp/css/animations.css';
        $anim_css_path = FLAVOR_CHAT_IA_PATH . 'assets/vbp/css/animations.css';

        if ( file_exists( $anim_css_path ) ) {
            wp_enqueue_style(
                'vbp-animations',
                $anim_css_url,
                array( 'vbp-frontend-components' ),
                '2.0.0'
            );
        }

        // Cargar JavaScript para componentes interactivos
        $js_url = FLAVOR_CHAT_IA_URL . 'assets/vbp/js/vbp-frontend.js';
        $js_path = FLAVOR_CHAT_IA_PATH . 'assets/vbp/js/vbp-frontend.js';

        if ( file_exists( $js_path ) ) {
            wp_enqueue_script(
                'vbp-frontend',
                $js_url,
                array(),
                '2.0.0',
                true
            );

            // Pasar URL de AJAX para formularios
            wp_add_inline_script(
                'vbp-frontend',
                'window.vbp_ajax_url = "' . esc_js( admin_url( 'admin-ajax.php' ) ) . '";',
                'before'
            );
        }

        // Cargar JavaScript de animaciones
        $anim_js_url = FLAVOR_CHAT_IA_URL . 'assets/vbp/js/vbp-animations.js';
        $anim_js_path = FLAVOR_CHAT_IA_PATH . 'assets/vbp/js/vbp-animations.js';

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

        foreach ( $elementos as $elemento ) {
            if ( isset( $elemento['visible'] ) && false === $elemento['visible'] ) {
                continue;
            }
            $html .= $this->renderizar_elemento( $elemento );
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
            $css[] = 'transition: ' . esc_attr( $estilos['transition'] );
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
        $imagen        = $data['imagen_fondo'] ?? $data['backgroundImage'] ?? $data['image'] ?? '';
        $overlay_color = $data['overlay_color'] ?? $data['overlayColor'] ?? '';
        $altura        = $data['altura'] ?? $data['height'] ?? '';
        $boton_color   = $data['boton_color'] ?? $data['buttonColor'] ?? '';
        $boton_bg      = $data['boton_bg'] ?? $data['buttonBg'] ?? '';

        $clase_variante = 'vbp-hero--' . esc_attr( $variante );

        // Estilos del contenedor principal (imagen de fondo)
        $estilos_hero = array();
        if ( $imagen ) {
            $estilos_hero[] = 'background-image: url(' . esc_url( $imagen ) . ')';
            $estilos_hero[] = 'background-size: cover';
            $estilos_hero[] = 'background-position: center';
        }
        if ( $altura ) {
            $estilos_hero[] = 'min-height: ' . esc_attr( $altura );
        }

        $estilos_css = $this->generar_estilos_elemento( $estilos );
        $estilos_hero_str = implode( '; ', $estilos_hero );

        // Combinar estilos evitando punto y coma inicial
        $estilos_combinados = array_filter( array( $estilos_css, $estilos_hero_str ) );
        $estilo_final = implode( '; ', $estilos_combinados );

        $html = '<section class="vbp-hero ' . $clase_variante . '" style="' . esc_attr( $estilo_final ) . '">';

        // Estilos del contenedor de contenido (overlay como fondo del contenido)
        $estilos_content = array();
        if ( $overlay_color ) {
            $estilos_content[] = 'background: ' . esc_attr( $overlay_color );
            $estilos_content[] = 'border-radius: 16px';
            $estilos_content[] = 'padding: 40px 60px';
        }
        $estilos_content_str = implode( '; ', $estilos_content );

        $html .= '<div class="vbp-hero__content" style="' . esc_attr( $estilos_content_str ) . '">';

        if ( $titulo ) {
            $html .= '<h1 class="vbp-hero__title">' . wp_kses_post( $titulo ) . '</h1>';
        }

        if ( $subtitulo ) {
            $html .= '<p class="vbp-hero__subtitle">' . wp_kses_post( $subtitulo ) . '</p>';
        }

        if ( $descripcion ) {
            $html .= '<p class="vbp-hero__description">' . wp_kses_post( $descripcion ) . '</p>';
        }

        if ( $boton_texto ) {
            $estilos_boton = array();
            if ( $boton_color ) {
                $estilos_boton[] = 'color: ' . esc_attr( $this->map_color_to_variable( $boton_color ) );
            }
            if ( $boton_bg ) {
                $estilos_boton[] = 'background-color: ' . esc_attr( $this->map_color_to_variable( $boton_bg ) );
            }
            $estilo_boton = ! empty( $estilos_boton ) ? ' style="' . esc_attr( implode( '; ', $estilos_boton ) ) . '"' : '';
            $html .= '<a href="' . esc_url( $boton_url ) . '" class="vbp-hero__button"' . $estilo_boton . '>' . esc_html( $boton_texto ) . '</a>';
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

        $titulo = $data['titulo'] ?? '';
        $items  = $data['items'] ?? array();

        $clase_variante = 'vbp-features--' . esc_attr( $variante );
        $estilos_css    = $this->generar_estilos_elemento( $estilos );

        $html = '<section class="vbp-features ' . $clase_variante . '" style="' . esc_attr( $estilos_css ) . '">';

        if ( $titulo ) {
            $html .= '<h2 class="vbp-features__title">' . wp_kses_post( $titulo ) . '</h2>';
        }

        if ( ! empty( $items ) ) {
            $html .= '<div class="vbp-features__grid">';

            foreach ( $items as $item ) {
                $html .= '<div class="vbp-feature-card">';
                $html .= '<h3 class="vbp-feature-card__title">' . esc_html( $item['titulo'] ?? '' ) . '</h3>';
                $html .= '<p class="vbp-feature-card__description">' . esc_html( $item['descripcion'] ?? '' ) . '</p>';
                $html .= '</div>';
            }

            $html .= '</div>';
        }

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

        return '<div class="' . esc_attr( $clases ) . '" style="' . esc_attr( $estilo_all ) . '" ' . $atributos_anim . '>' . wp_kses_post( $texto ) . '</div>';
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

        return '<' . $nivel . ' class="' . esc_attr( $clases ) . '" style="' . esc_attr( $estilo_all ) . '" ' . $atributos_anim . '>' . wp_kses_post( $texto ) . '</' . $nivel . '>';
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
            return '';
        }

        return '<figure class="' . esc_attr( $clases ) . '" style="' . esc_attr( $estilo_all ) . '" ' . $atributos_anim . '><img src="' . esc_url( $src ) . '" alt="' . esc_attr( $alt ) . '"></figure>';
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

        return '<div class="' . esc_attr( $clases ) . '" style="' . esc_attr( $estilo_all ) . '" ' . $atributos_anim . '><a href="' . esc_url( $url ) . '" target="' . esc_attr( $target ) . '" class="vbp-button">' . esc_html( $texto ) . '</a></div>';
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
        $estilos_css = $this->generar_estilos_elemento( $estilos );

        $titulo       = $data['titulo'] ?? '';
        $subtitulo    = $data['subtitulo'] ?? '';
        $boton_texto  = $data['boton_texto'] ?? '';
        $boton_url    = $data['boton_url'] ?? '#';
        $fondo        = $data['fondo'] ?? $data['background'] ?? '';
        $boton_color  = $data['boton_color'] ?? $data['buttonColor'] ?? '';
        $boton_bg     = $data['boton_bg'] ?? $data['buttonBg'] ?? '';
        $texto_color  = $data['texto_color'] ?? $data['textColor'] ?? '';

        // Construir estilos del contenedor
        $estilos_cta = array();
        if ( $fondo ) {
            // Soportar gradientes y colores sólidos
            if ( strpos( $fondo, 'gradient' ) !== false || strpos( $fondo, 'linear' ) !== false || strpos( $fondo, 'radial' ) !== false ) {
                $estilos_cta[] = 'background: ' . esc_attr( $fondo );
            } else {
                $estilos_cta[] = 'background-color: ' . esc_attr( $this->map_color_to_variable( $fondo ) );
            }
        }
        if ( $texto_color ) {
            $estilos_cta[] = 'color: ' . esc_attr( $this->map_color_to_variable( $texto_color ) );
        }
        $estilos_cta_str = implode( '; ', $estilos_cta );

        // Combinar estilos evitando punto y coma inicial
        $estilos_combinados = array_filter( array( $estilos_css, $estilos_cta_str ) );
        $estilo_final = implode( '; ', $estilos_combinados );

        $html = '<section class="vbp-cta" style="' . esc_attr( $estilo_final ) . '">';
        $html .= '<div class="vbp-cta__content">';

        if ( $titulo ) {
            $html .= '<h2 class="vbp-cta__title">' . wp_kses_post( $titulo ) . '</h2>';
        }

        if ( $subtitulo ) {
            $html .= '<p class="vbp-cta__subtitle">' . wp_kses_post( $subtitulo ) . '</p>';
        }

        if ( $boton_texto ) {
            $estilos_boton = array();
            if ( $boton_color ) {
                $estilos_boton[] = 'color: ' . esc_attr( $this->map_color_to_variable( $boton_color ) );
            }
            if ( $boton_bg ) {
                $estilos_boton[] = 'background-color: ' . esc_attr( $this->map_color_to_variable( $boton_bg ) );
            }
            $estilo_boton = ! empty( $estilos_boton ) ? ' style="' . esc_attr( implode( '; ', $estilos_boton ) ) . '"' : '';
            $html .= '<a href="' . esc_url( $boton_url ) . '" class="vbp-cta__button"' . $estilo_boton . '>' . esc_html( $boton_texto ) . '</a>';
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

        $titulo = $data['titulo'] ?? '';
        $items  = $data['items'] ?? array();

        $html = '<section class="vbp-testimonials vbp-testimonials--' . esc_attr( $variante ) . '" style="' . esc_attr( $estilos_css ) . '">';

        if ( $titulo ) {
            $html .= '<h2 class="vbp-testimonials__title">' . wp_kses_post( $titulo ) . '</h2>';
        }

        if ( ! empty( $items ) ) {
            $html .= '<div class="vbp-testimonials__grid">';

            foreach ( $items as $item ) {
                $html .= '<div class="vbp-testimonial-card">';
                $html .= '<blockquote class="vbp-testimonial-card__quote">' . wp_kses_post( $item['texto'] ?? '' ) . '</blockquote>';
                $html .= '<div class="vbp-testimonial-card__author">';
                $html .= '<span class="vbp-testimonial-card__name">' . esc_html( $item['autor'] ?? '' ) . '</span>';

                if ( ! empty( $item['cargo'] ) ) {
                    $html .= '<span class="vbp-testimonial-card__role">' . esc_html( $item['cargo'] ) . '</span>';
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
            $html .= '<h2 class="vbp-pricing__title">' . wp_kses_post( $titulo ) . '</h2>';
        }

        if ( $subtitulo ) {
            $html .= '<p class="vbp-pricing__subtitle">' . wp_kses_post( $subtitulo ) . '</p>';
        }

        if ( ! empty( $items ) ) {
            $html .= '<div class="vbp-pricing__grid">';

            foreach ( $items as $item ) {
                $destacado = ! empty( $item['destacado'] ) ? ' vbp-pricing-card--featured' : '';

                $html .= '<div class="vbp-pricing-card' . $destacado . '">';
                $html .= '<h3 class="vbp-pricing-card__name">' . esc_html( $item['nombre'] ?? '' ) . '</h3>';
                $html .= '<div class="vbp-pricing-card__price">';
                $html .= '<span class="vbp-pricing-card__amount">$' . esc_html( $item['precio'] ?? '0' ) . '</span>';

                if ( ! empty( $item['periodo'] ) ) {
                    $html .= '<span class="vbp-pricing-card__period">' . esc_html( $item['periodo'] ) . '</span>';
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

        $titulo = $data['titulo'] ?? '';
        $items  = $data['items'] ?? array();

        $html = '<section class="vbp-faq" style="' . esc_attr( $estilos_css ) . '">';

        if ( $titulo ) {
            $html .= '<h2 class="vbp-faq__title">' . wp_kses_post( $titulo ) . '</h2>';
        }

        if ( ! empty( $items ) ) {
            $html .= '<div class="vbp-faq__list">';

            foreach ( $items as $index => $item ) {
                $html .= '<details class="vbp-faq-item">';
                $html .= '<summary class="vbp-faq-item__question">' . esc_html( $item['pregunta'] ?? '' ) . '</summary>';
                $html .= '<div class="vbp-faq-item__answer">' . wp_kses_post( $item['respuesta'] ?? '' ) . '</div>';
                $html .= '</details>';
            }

            $html .= '</div>';
        }

        $html .= '</section>';

        return $html;
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
            $html .= '<h2 class="vbp-team__title">' . wp_kses_post( $titulo ) . '</h2>';
        }

        if ( ! empty( $items ) ) {
            $html .= '<div class="vbp-team__grid">';

            foreach ( $items as $item ) {
                $html .= '<div class="vbp-team-member">';

                if ( ! empty( $item['foto'] ) ) {
                    $html .= '<img src="' . esc_url( $item['foto'] ) . '" alt="' . esc_attr( $item['nombre'] ?? '' ) . '" class="vbp-team-member__photo">';
                } else {
                    $inicial = ! empty( $item['nombre'] ) ? strtoupper( substr( $item['nombre'], 0, 1 ) ) : 'M';
                    $html   .= '<div class="vbp-team-member__avatar">' . esc_html( $inicial ) . '</div>';
                }

                $html .= '<h3 class="vbp-team-member__name">' . esc_html( $item['nombre'] ?? '' ) . '</h3>';

                if ( ! empty( $item['cargo'] ) ) {
                    $html .= '<p class="vbp-team-member__role">' . esc_html( $item['cargo'] ) . '</p>';
                }

                if ( ! empty( $item['bio'] ) ) {
                    $html .= '<p class="vbp-team-member__bio">' . wp_kses_post( $item['bio'] ) . '</p>';
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

        $items = $data['items'] ?? array();

        $html = '<section class="vbp-stats" style="' . esc_attr( $estilos_css ) . '">';

        if ( ! empty( $items ) ) {
            $html .= '<div class="vbp-stats__grid">';

            foreach ( $items as $item ) {
                $html .= '<div class="vbp-stat-item">';
                $html .= '<span class="vbp-stat-item__number">' . esc_html( $item['numero'] ?? '0' ) . '</span>';
                $html .= '<span class="vbp-stat-item__label">' . esc_html( $item['label'] ?? '' ) . '</span>';
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
            $html .= '<h2 class="vbp-gallery__title">' . wp_kses_post( $titulo ) . '</h2>';
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
            $html .= '<h2 class="vbp-blog__title">' . wp_kses_post( $titulo ) . '</h2>';
        }

        if ( $subtitulo ) {
            $html .= '<p class="vbp-blog__subtitle">' . wp_kses_post( $subtitulo ) . '</p>';
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
            $html .= '<p class="vbp-blog__empty">' . esc_html__( 'No se encontraron artículos.', 'flavor-chat-ia' ) . '</p>';
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
        $boton_texto   = $data['boton_texto'] ?? __( 'Enviar mensaje', 'flavor-chat-ia' );
        $mensaje_exito = $data['mensaje_exito'] ?? __( '¡Mensaje enviado correctamente!', 'flavor-chat-ia' );

        // ID único para el formulario
        $form_id = 'vbp-contact-' . substr( md5( wp_json_encode( $elemento ) ), 0, 8 );
        $post_id = $post ? $post->ID : 0;

        $html = '<section class="vbp-contact" style="' . esc_attr( $estilos_css ) . '">';

        if ( $titulo ) {
            $html .= '<h2 class="vbp-contact__title">' . wp_kses_post( $titulo ) . '</h2>';
        }

        if ( $subtitulo ) {
            $html .= '<p class="vbp-contact__subtitle">' . wp_kses_post( $subtitulo ) . '</p>';
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
        $html .= '<label for="' . esc_attr( $form_id ) . '-name">' . esc_html__( 'Nombre', 'flavor-chat-ia' ) . ' <span class="required">*</span></label>';
        $html .= '<input type="text" id="' . esc_attr( $form_id ) . '-name" name="name" required>';
        $html .= '<span class="vbp-field-error"></span>';
        $html .= '</div>';

        $html .= '<div class="vbp-contact__field">';
        $html .= '<label for="' . esc_attr( $form_id ) . '-email">' . esc_html__( 'Email', 'flavor-chat-ia' ) . ' <span class="required">*</span></label>';
        $html .= '<input type="email" id="' . esc_attr( $form_id ) . '-email" name="email" required>';
        $html .= '<span class="vbp-field-error"></span>';
        $html .= '</div>';

        $html .= '<div class="vbp-contact__field">';
        $html .= '<label for="' . esc_attr( $form_id ) . '-message">' . esc_html__( 'Mensaje', 'flavor-chat-ia' ) . ' <span class="required">*</span></label>';
        $html .= '<textarea id="' . esc_attr( $form_id ) . '-message" name="message" rows="4" required></textarea>';
        $html .= '<span class="vbp-field-error"></span>';
        $html .= '</div>';

        // Estado de envío
        $html .= '<div class="vbp-form-status" aria-live="polite"></div>';

        // Botón de envío
        $html .= '<button type="submit" class="vbp-contact__submit">';
        $html .= '<span class="vbp-btn-text">' . esc_html( $boton_texto ) . '</span>';
        $html .= '<span class="vbp-btn-loading" style="display:none;">';
        $html .= '<svg class="vbp-spinner" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">';
        $html .= '<path d="M12 2v4M12 18v4M4.93 4.93l2.83 2.83M16.24 16.24l2.83 2.83M2 12h4M18 12h4M4.93 19.07l2.83-2.83M16.24 7.76l2.83-2.83"/>';
        $html .= '</svg>';
        $html .= esc_html__( 'Enviando...', 'flavor-chat-ia' );
        $html .= '</span>';
        $html .= '</button>';

        $html .= '</form>';

        // Mensaje de éxito (oculto por defecto)
        $html .= '<div class="vbp-contact__success" style="display:none;">';
        $html .= '<svg width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">';
        $html .= '<path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/>';
        $html .= '<polyline points="22 4 12 14.01 9 11.01"/>';
        $html .= '</svg>';
        $html .= '<h3>' . esc_html__( '¡Gracias por tu mensaje!', 'flavor-chat-ia' ) . '</h3>';
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
            $html .= '<h2 class="vbp-video-section__title">' . wp_kses_post( $titulo ) . '</h2>';
        }

        if ( $descripcion ) {
            $html .= '<p class="vbp-video-section__description">' . wp_kses_post( $descripcion ) . '</p>';
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
     * Renderiza Icon
     */
    private function render_icon( $elemento ) {
        $data        = $elemento['data'] ?? array();
        $estilos     = $elemento['styles'] ?? array();
        $estilos_css = $this->generar_estilos_elemento( $estilos );

        $icono = $data['icon'] ?? '⭐';
        $size  = $data['size'] ?? '48px';

        return '<div class="vbp-icon" style="' . esc_attr( $estilos_css ) . '; font-size: ' . esc_attr( $size ) . ';">' . esc_html( $icono ) . '</div>';
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
                   esc_html__( 'Widget global no configurado', 'flavor-chat-ia' ) .
                   '</div>';
        }

        // Obtener el widget global
        if ( ! class_exists( 'Flavor_VBP_Global_Widgets' ) ) {
            return '<div class="vbp-global-widget vbp-global-widget--error">' .
                   esc_html__( 'Sistema de widgets globales no disponible', 'flavor-chat-ia' ) .
                   '</div>';
        }

        $global_widgets = Flavor_VBP_Global_Widgets::get_instance();
        $widget_data    = $global_widgets->get_widget_data( $widget_id );

        if ( ! $widget_data ) {
            return '<div class="vbp-global-widget vbp-global-widget--error">' .
                   esc_html__( 'Widget global no encontrado', 'flavor-chat-ia' ) .
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
                $html .= '<h3 class="vbp-card__title"><a href="' . esc_url( $enlace ) . '">' . esc_html( $titulo ) . '</a></h3>';
            } else {
                $html .= '<h3 class="vbp-card__title">' . esc_html( $titulo ) . '</h3>';
            }
        }

        if ( $descripcion ) {
            $html .= '<p class="vbp-card__description">' . wp_kses_post( $descripcion ) . '</p>';
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
            $html .= '<h2 class="vbp-section__title">' . wp_kses_post( $titulo ) . '</h2>';
        }

        if ( $subtitulo ) {
            $html .= '<p class="vbp-section__subtitle">' . wp_kses_post( $subtitulo ) . '</p>';
        }

        if ( $contenido ) {
            $html .= '<div class="vbp-section__content">' . wp_kses_post( $contenido ) . '</div>';
        }

        if ( $html_custom ) {
            $html .= '<div class="vbp-section__html">' . $html_custom . '</div>';
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
     * Renderiza un timeline/proceso
     *
     * @param array $elemento Datos del elemento.
     * @return string
     */
    private function render_timeline( $elemento ) {
        $data    = $elemento['data'] ?? array();
        $estilos = $elemento['styles'] ?? array();
        $titulo  = $data['titulo'] ?? '';
        $items   = $data['items'] ?? array();

        $estilos_css = $this->generar_estilos_elemento( $estilos );

        $html = '<div class="vbp-timeline" style="' . esc_attr( $estilos_css ) . '">';

        if ( $titulo ) {
            $html .= '<h3 class="vbp-timeline__title">' . esc_html( $titulo ) . '</h3>';
        }

        $html .= '<div class="vbp-timeline__items">';
        foreach ( $items as $item ) {
            $paso   = $item['paso'] ?? '';
            $titulo_item = $item['titulo'] ?? '';
            $desc   = $item['descripcion'] ?? '';
            $icono  = $item['icono'] ?? '';

            $html .= '<div class="vbp-timeline__item">';
            $html .= '<div class="vbp-timeline__marker">';
            if ( $icono ) {
                $html .= '<span class="vbp-timeline__icon">' . esc_html( $icono ) . '</span>';
            } else {
                $html .= '<span class="vbp-timeline__number">' . esc_html( $paso ) . '</span>';
            }
            $html .= '</div>';
            $html .= '<div class="vbp-timeline__content">';
            $html .= '<h4 class="vbp-timeline__item-title">' . esc_html( $titulo_item ) . '</h4>';
            if ( $desc ) {
                $html .= '<p class="vbp-timeline__item-desc">' . esc_html( $desc ) . '</p>';
            }
            $html .= '</div>';
            $html .= '</div>';
        }
        $html .= '</div>';
        $html .= '</div>';

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

        foreach ( $items as $item ) {
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
            $html .= '<h4 class="vbp-product-card__title">' . esc_html( $nombre ) . '</h4>';
            if ( $productor ) {
                $html .= '<p class="vbp-product-card__producer">' . esc_html( $productor ) . '</p>';
            }
            if ( $precio ) {
                $html .= '<span class="vbp-product-card__price">' . esc_html( $precio ) . '</span>';
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

        foreach ( $items as $item ) {
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
                    $html .= '<span class="vbp-blog-card__category">' . esc_html( $categoria ) . '</span>';
                }
                $html .= '</div>';
            }
            $html .= '<div class="vbp-blog-card__content">';
            $html .= '<h4 class="vbp-blog-card__title">' . esc_html( $titulo ) . '</h4>';
            if ( $fecha ) {
                $html .= '<time class="vbp-blog-card__date">' . esc_html( $fecha ) . '</time>';
            }
            if ( $extracto ) {
                $html .= '<p class="vbp-blog-card__excerpt">' . esc_html( $extracto ) . '</p>';
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
        $html .= $this->render_column_content( $col_izquierda );
        $html .= '</div>';
        $html .= '<div class="vbp-two-columns__right">';
        $html .= $this->render_column_content( $col_derecha );
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
    private function render_column_content( $col_data ) {
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
                return '<div class="vbp-column-text">' . wp_kses_post( $texto ) . '</div>';

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
            $html .= '<h3 class="vbp-benefits__title">' . esc_html( $titulo ) . '</h3>';
        }

        $html .= '<ul class="vbp-benefits__list">';
        foreach ( $items as $item ) {
            $icono = $item['icono'] ?? '✓';
            $texto = $item['texto'] ?? '';
            $html .= '<li class="vbp-benefits__item">';
            $html .= '<span class="vbp-benefits__icon">' . esc_html( $icono ) . '</span>';
            $html .= '<span class="vbp-benefits__text">' . esc_html( $texto ) . '</span>';
            $html .= '</li>';
        }
        $html .= '</ul>';

        if ( $nota ) {
            $html .= '<p class="vbp-benefits__note">' . esc_html( $nota ) . '</p>';
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
        $boton_texto = $data['boton_texto'] ?? __( 'Enviar', 'flavor-chat-ia' );

        $estilos_css = $this->generar_estilos_elemento( $estilos );

        $html = '<div class="vbp-form vbp-registration-form" style="' . esc_attr( $estilos_css ) . '">';

        if ( $titulo ) {
            $html .= '<h3 class="vbp-form__title">' . esc_html( $titulo ) . '</h3>';
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
                $html .= '<option value="">' . __( 'Seleccionar...', 'flavor-chat-ia' ) . '</option>';
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
            $html .= '<label class="vbp-form__checkbox-label">';
            $html .= '<input type="checkbox" name="acepto" required class="vbp-form__checkbox" />';
            $html .= ' ' . esc_html( $checkbox_text );
            $html .= '</label>';
            $html .= '</div>';
        }

        $html .= '<button type="submit" class="vbp-form__submit vbp-button">' . esc_html( $boton_texto ) . '</button>';
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
            $html .= '<h3 class="vbp-contact-info__title">' . esc_html( $titulo ) . '</h3>';
        }

        $html .= '<ul class="vbp-contact-info__list">';
        foreach ( $items as $item ) {
            $icono  = $item['icono'] ?? '';
            $titulo_item = $item['titulo'] ?? '';
            $valor  = $item['valor'] ?? '';

            $html .= '<li class="vbp-contact-info__item">';
            if ( $icono ) {
                $html .= '<span class="vbp-contact-info__icon">' . esc_html( $icono ) . '</span>';
            }
            $html .= '<div class="vbp-contact-info__content">';
            if ( $titulo_item ) {
                $html .= '<strong class="vbp-contact-info__label">' . esc_html( $titulo_item ) . '</strong>';
            }
            $html .= '<span class="vbp-contact-info__value">' . esc_html( $valor ) . '</span>';
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
            $html .= '<h3 class="vbp-process__title">' . esc_html( $titulo ) . '</h3>';
        }

        $html .= '<div class="vbp-process__steps">';
        foreach ( $items as $item ) {
            $paso = $item['paso'] ?? '';
            $titulo_step = $item['titulo'] ?? '';
            $desc = $item['descripcion'] ?? '';

            $html .= '<div class="vbp-process__step">';
            $html .= '<div class="vbp-process__number">' . esc_html( $paso ) . '</div>';
            $html .= '<div class="vbp-process__content">';
            $html .= '<h4 class="vbp-process__step-title">' . esc_html( $titulo_step ) . '</h4>';
            if ( $desc ) {
                $html .= '<p class="vbp-process__step-desc">' . esc_html( $desc ) . '</p>';
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
                $html .= '<h3 class="vbp-section__title">' . esc_html( $titulo ) . '</h3>';
            }
            if ( $subtitulo ) {
                $html .= '<p class="vbp-section__subtitle">' . esc_html( $subtitulo ) . '</p>';
            }
            $html .= do_shortcode( '[rs_feed limite="' . intval( $limite ) . '" tipo="' . esc_attr( $tipo ) . '"]' );
            $html .= '</div>';
            return $html;
        }

        // Fallback: mostrar mensaje de placeholder
        $html = '<div class="vbp-social-feed vbp-placeholder" style="' . esc_attr( $estilos_css ) . '">';
        if ( $titulo ) {
            $html .= '<h3 class="vbp-section__title">' . esc_html( $titulo ) . '</h3>';
        }
        $html .= '<p class="vbp-placeholder__message">' . __( 'Activa el módulo Red Social para ver el feed.', 'flavor-chat-ia' ) . '</p>';
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
                $html .= '<h3 class="vbp-section__title">' . esc_html( $titulo ) . '</h3>';
            }
            if ( $subtitulo ) {
                $html .= '<p class="vbp-section__subtitle">' . esc_html( $subtitulo ) . '</p>';
            }
            $html .= do_shortcode( '[sello_conciencia]' );
            $html .= '</div>';
            return $html;
        }

        // Fallback con sellos manuales
        $html = '<div class="vbp-sellos" style="' . esc_attr( $estilos_css ) . '; padding: 3rem 2rem;">';
        if ( $titulo ) {
            $html .= '<h3 class="vbp-section__title" style="text-align: center; margin-bottom: 0.5rem;">' . esc_html( $titulo ) . '</h3>';
        }
        if ( $subtitulo ) {
            $html .= '<p class="vbp-section__subtitle" style="text-align: center; margin-bottom: 2rem; color: var(--flavor-text-muted, #666);">' . esc_html( $subtitulo ) . '</p>';
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
        $html .= $this->render_column_content( $col_izquierda );
        $html .= '</div>';

        // Columna derecha
        $html .= '<div class="vbp-column vbp-column--right">';
        $html .= $this->render_column_content( $col_derecha );
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
            $html .= '<h3 class="vbp-contact-info__title">' . esc_html( $titulo ) . '</h3>';
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
        $boton_texto = $content['boton_texto'] ?? __( 'Enviar', 'flavor-chat-ia' );

        $html = '<div class="vbp-contact-form">';

        if ( $titulo ) {
            $html .= '<h3 class="vbp-contact-form__title">' . esc_html( $titulo ) . '</h3>';
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
                    $html .= '<option value="">' . esc_html__( 'Selecciona...', 'flavor-chat-ia' ) . '</option>';
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

        $html .= '<button type="submit" class="vbp-contact-form__submit">' . esc_html( $boton_texto ) . '</button>';
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
            $html .= '<div class="vbp-audio__title">' . esc_html( $titulo ) . '</div>';
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
}

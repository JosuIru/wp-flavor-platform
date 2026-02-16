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

        $estilos_pagina = $this->generar_estilos_pagina( $settings );

        $html = '<div class="vbp-landing" style="' . esc_attr( $estilos_pagina ) . '">';

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
     * Genera estilos CSS para la página
     *
     * @param array $settings Configuración de la página.
     * @return string
     */
    private function generar_estilos_pagina( $settings ) {
        $estilos = array();

        if ( ! empty( $settings['backgroundColor'] ) ) {
            $estilos[] = 'background-color: ' . esc_attr( $settings['backgroundColor'] );
        }

        if ( ! empty( $settings['pageWidth'] ) ) {
            $estilos[] = 'max-width: ' . absint( $settings['pageWidth'] ) . 'px';
            $estilos[] = 'margin: 0 auto';
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
        $estilos      = isset( $elemento['styles'] ) ? $elemento['styles'] : array();
        $estilos_css  = $this->generar_estilos_elemento( $estilos );

        // Determinar color de gradiente basado en categoría
        $categoria = isset( $bloque['category'] ) ? $bloque['category'] : 'modules';
        $gradientes = array(
            'modules'   => 'linear-gradient(135deg, #667eea 0%, #764ba2 100%)',
            'maps'      => 'linear-gradient(135deg, #11998e 0%, #38ef7d 100%)',
            'economy'   => 'linear-gradient(135deg, #f093fb 0%, #f5576c 100%)',
            'community' => 'linear-gradient(135deg, #4facfe 0%, #00f2fe 100%)',
        );
        $gradiente = isset( $gradientes[ $categoria ] ) ? $gradientes[ $categoria ] : $gradientes['modules'];

        $html = '<div class="vbp-module-preview" style="background: ' . esc_attr( $gradiente ) . '; ' . esc_attr( $estilos_css ) . '">';
        $html .= '<div class="vbp-module-preview__icon">' . $icon . '</div>';
        $html .= '<div class="vbp-module-preview__info">';
        $html .= '<span class="vbp-module-preview__name">' . esc_html( $widget_name ) . '</span>';
        $html .= '<span class="vbp-module-preview__badge">' . esc_html( ucfirst( str_replace( '-', ' ', $module_name ) ) ) . '</span>';
        $html .= '</div>';

        // Mostrar configuración actual si hay datos
        if ( ! empty( $elemento['data'] ) ) {
            $config_items = array();
            foreach ( $elemento['data'] as $key => $value ) {
                if ( ! empty( $value ) && is_scalar( $value ) ) {
                    $label = ucfirst( str_replace( '_', ' ', $key ) );
                    $display_value = is_bool( $value ) ? ( $value ? '✓' : '✗' ) : $value;
                    $config_items[] = '<span class="vbp-config-item">' . esc_html( $label ) . ': ' . esc_html( $display_value ) . '</span>';
                }
            }
            if ( ! empty( $config_items ) ) {
                $html .= '<div class="vbp-module-preview__config">' . implode( '', array_slice( $config_items, 0, 4 ) ) . '</div>';
            }
        }

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

        // Colors
        if ( ! empty( $estilos['colors'] ) ) {
            if ( ! empty( $estilos['colors']['background'] ) ) {
                $css[] = 'background-color: ' . esc_attr( $estilos['colors']['background'] );
            }
            if ( ! empty( $estilos['colors']['text'] ) ) {
                $css[] = 'color: ' . esc_attr( $estilos['colors']['text'] );
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

        // Borders
        if ( ! empty( $estilos['borders'] ) ) {
            $borders = $estilos['borders'];
            if ( ! empty( $borders['radius'] ) ) {
                $css[] = 'border-radius: ' . esc_attr( $borders['radius'] );
            }
            if ( ! empty( $borders['width'] ) && ! empty( $borders['color'] ) ) {
                $estilo_borde = ! empty( $borders['style'] ) ? $borders['style'] : 'solid';
                $css[] = 'border: ' . esc_attr( $borders['width'] ) . ' ' . esc_attr( $estilo_borde ) . ' ' . esc_attr( $borders['color'] );
            }
        }

        // Shadows
        if ( ! empty( $estilos['shadows']['boxShadow'] ) ) {
            $css[] = 'box-shadow: ' . esc_attr( $estilos['shadows']['boxShadow'] );
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

        $titulo      = $data['titulo'] ?? '';
        $subtitulo   = $data['subtitulo'] ?? '';
        $boton_texto = $data['boton_texto'] ?? '';
        $boton_url   = $data['boton_url'] ?? '#';
        $imagen      = $data['imagen_fondo'] ?? '';

        $clase_variante = 'vbp-hero--' . esc_attr( $variante );
        $estilo_fondo   = $imagen ? 'background-image: url(' . esc_url( $imagen ) . ');' : '';
        $estilos_css    = $this->generar_estilos_elemento( $estilos );

        $html = '<section class="vbp-hero ' . $clase_variante . '" style="' . esc_attr( $estilos_css . $estilo_fondo ) . '">';
        $html .= '<div class="vbp-hero__content">';

        if ( $titulo ) {
            $html .= '<h1 class="vbp-hero__title">' . wp_kses_post( $titulo ) . '</h1>';
        }

        if ( $subtitulo ) {
            $html .= '<p class="vbp-hero__subtitle">' . wp_kses_post( $subtitulo ) . '</p>';
        }

        if ( $boton_texto ) {
            $html .= '<a href="' . esc_url( $boton_url ) . '" class="vbp-hero__button">' . esc_html( $boton_texto ) . '</a>';
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

        $titulo      = $data['titulo'] ?? '';
        $subtitulo   = $data['subtitulo'] ?? '';
        $boton_texto = $data['boton_texto'] ?? '';
        $boton_url   = $data['boton_url'] ?? '#';

        $html = '<section class="vbp-cta" style="' . esc_attr( $estilos_css ) . '">';
        $html .= '<div class="vbp-cta__content">';

        if ( $titulo ) {
            $html .= '<h2 class="vbp-cta__title">' . wp_kses_post( $titulo ) . '</h2>';
        }

        if ( $subtitulo ) {
            $html .= '<p class="vbp-cta__subtitle">' . wp_kses_post( $subtitulo ) . '</p>';
        }

        if ( $boton_texto ) {
            $html .= '<a href="' . esc_url( $boton_url ) . '" class="vbp-cta__button">' . esc_html( $boton_texto ) . '</a>';
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

        $columnas       = $data['columns'] ?? 2;
        $column_widths  = $data['columnWidths'] ?? array();
        $gap            = $data['gap'] ?? '20px';
        $vertical_align = $data['verticalAlign'] ?? 'start';
        $stack_mobile   = $data['stackOnMobile'] ?? true;
        $children       = $elemento['children'] ?? array();

        // Generar ID único para estilos inline
        $element_id = 'vbp-cols-' . substr( md5( wp_json_encode( $elemento ) ), 0, 8 );

        // Generar grid-template-columns
        if ( ! empty( $column_widths ) && count( $column_widths ) === intval( $columnas ) ) {
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

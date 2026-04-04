<?php
/**
 * API Pública de Preview para VBP (Visual Builder Pro)
 *
 * Proporciona endpoints públicos (sin autenticación) para previsualizar
 * y validar landing pages VBP. Diseñado para integración con Claude Code.
 *
 * @package Flavor_Chat_IA
 * @subpackage API
 * @since 2.1.0
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Clase para la API de Preview pública
 */
class Flavor_VBP_Preview_API {

    /**
     * Instancia singleton
     *
     * @var Flavor_VBP_Preview_API|null
     */
    private static $instance = null;

    /**
     * Namespace de la API
     *
     * @var string
     */
    const NAMESPACE = 'flavor-vbp/v1';

    /**
     * Rate limiter - requests por IP por minuto
     *
     * @var int
     */
    const RATE_LIMIT = 60;

    /**
     * Obtiene la instancia singleton
     *
     * @return Flavor_VBP_Preview_API
     */
    public static function get_instance() {
        if ( null === self::$instance ) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Constructor
     */
    private function __construct() {
        add_action( 'rest_api_init', array( $this, 'register_routes' ) );
    }

    /**
     * Registra las rutas REST
     */
    public function register_routes() {
        // Preview HTML de una landing (público)
        register_rest_route( self::NAMESPACE, '/preview/(?P<id>\d+)', array(
            'methods'             => WP_REST_Server::READABLE,
            'callback'            => array( $this, 'render_preview' ),
            'permission_callback' => array( $this, 'public_permission_check' ),
            'args'                => array(
                'id' => array(
                    'required'          => true,
                    'validate_callback' => function( $param ) {
                        return is_numeric( $param );
                    },
                ),
                'format' => array(
                    'type'    => 'string',
                    'default' => 'html',
                    'enum'    => array( 'html', 'json' ),
                ),
            ),
        ) );

        // Validar estructura de una landing (público)
        register_rest_route( self::NAMESPACE, '/preview/(?P<id>\d+)/validate', array(
            'methods'             => WP_REST_Server::READABLE,
            'callback'            => array( $this, 'validate_page' ),
            'permission_callback' => array( $this, 'public_permission_check' ),
        ) );

        // Obtener info/metadata de una landing (público)
        register_rest_route( self::NAMESPACE, '/preview/(?P<id>\d+)/info', array(
            'methods'             => WP_REST_Server::READABLE,
            'callback'            => array( $this, 'get_page_info' ),
            'permission_callback' => array( $this, 'public_permission_check' ),
        ) );

        // Preview de elementos específicos (para debug)
        register_rest_route( self::NAMESPACE, '/preview/(?P<id>\d+)/element/(?P<element_id>[a-z0-9_]+)', array(
            'methods'             => WP_REST_Server::READABLE,
            'callback'            => array( $this, 'render_element_preview' ),
            'permission_callback' => array( $this, 'public_permission_check' ),
        ) );
    }

    /**
     * Verificación de permisos públicos con rate limiting
     *
     * @param WP_REST_Request $request Petición REST.
     * @return bool|WP_Error
     */
    public function public_permission_check( $request ) {
        // Obtener IP del cliente
        $client_ip = $this->get_client_ip();
        $cache_key = 'vbp_rate_' . md5( $client_ip );

        // Verificar rate limit
        $requests = get_transient( $cache_key );

        if ( false === $requests ) {
            $requests = 0;
        }

        if ( $requests >= self::RATE_LIMIT ) {
            return new WP_Error(
                'rate_limit_exceeded',
                'Demasiadas solicitudes. Intenta de nuevo en un minuto.',
                array( 'status' => 429 )
            );
        }

        // Incrementar contador
        set_transient( $cache_key, $requests + 1, MINUTE_IN_SECONDS );

        return true;
    }

    /**
     * Obtiene la IP del cliente
     *
     * @return string
     */
    private function get_client_ip() {
        $ip_keys = array(
            'HTTP_CF_CONNECTING_IP', // Cloudflare
            'HTTP_X_FORWARDED_FOR',
            'HTTP_X_REAL_IP',
            'REMOTE_ADDR',
        );

        foreach ( $ip_keys as $key ) {
            if ( ! empty( $_SERVER[ $key ] ) ) {
                $ip = sanitize_text_field( wp_unslash( $_SERVER[ $key ] ) );
                // En caso de múltiples IPs (X-Forwarded-For), tomar la primera
                if ( strpos( $ip, ',' ) !== false ) {
                    $ip = trim( explode( ',', $ip )[0] );
                }
                return $ip;
            }
        }

        return '0.0.0.0';
    }

    /**
     * Renderiza el preview HTML de una landing
     *
     * @param WP_REST_Request $request Petición REST.
     * @return WP_REST_Response|WP_Error
     */
    public function render_preview( $request ) {
        $post_id = absint( $request->get_param( 'id' ) );
        $format = $request->get_param( 'format' );
        $post = get_post( $post_id );

        if ( ! $post ) {
            return new WP_Error(
                'not_found',
                'Landing no encontrada',
                array( 'status' => 404 )
            );
        }

        // Solo permitir preview de landings publicadas o con API key válida
        if ( $post->post_status !== 'publish' ) {
            // Verificar si hay API key para permitir preview de drafts
            $auth_header = $request->get_header( 'X-VBP-Key' );

            if ( ! flavor_verify_vbp_api_key( $auth_header ) ) {
                return new WP_Error(
                    'not_published',
                    'La landing no está publicada. Usa X-VBP-Key para preview de borradores.',
                    array( 'status' => 403 )
                );
            }
        }

        // Obtener datos VBP
        $vbp_data = $this->get_vbp_data( $post_id );

        if ( empty( $vbp_data ) || empty( $vbp_data['elements'] ) ) {
            return new WP_Error(
                'no_content',
                'La landing no tiene contenido VBP',
                array( 'status' => 404 )
            );
        }

        // Renderizar HTML
        $html = $this->render_landing_html( $vbp_data, $post );

        if ( 'json' === $format ) {
            return new WP_REST_Response( array(
                'success'  => true,
                'post_id'  => $post_id,
                'title'    => $post->post_title,
                'status'   => $post->post_status,
                'html'     => $html,
                'elements' => count( $vbp_data['elements'] ),
            ), 200 );
        }

        // Devolver HTML directo con headers apropiados
        $response = new WP_REST_Response( $html, 200 );
        $response->header( 'Content-Type', 'text/html; charset=utf-8' );
        $response->header( 'X-VBP-Post-ID', $post_id );
        $response->header( 'X-VBP-Elements', count( $vbp_data['elements'] ) );

        return $response;
    }

    /**
     * Obtiene datos VBP de un post
     *
     * @param int $post_id ID del post.
     * @return array
     */
    private function get_vbp_data( $post_id ) {
        // Intentar obtener de _flavor_vbp_data (formato nuevo)
        $vbp_data = get_post_meta( $post_id, '_flavor_vbp_data', true );

        if ( ! empty( $vbp_data ) ) {
            return $vbp_data;
        }

        // Intentar obtener de _flavor_vb_data (formato antiguo Visual Builder)
        $vb_data = get_post_meta( $post_id, '_flavor_vb_data', true );

        if ( ! empty( $vb_data ) ) {
            // Convertir formato antiguo si es necesario
            return $this->convert_legacy_format( $vb_data );
        }

        return array();
    }

    /**
     * Convierte formato legacy de Visual Builder a VBP
     *
     * @param array $vb_data Datos en formato antiguo.
     * @return array Datos en formato VBP.
     */
    private function convert_legacy_format( $vb_data ) {
        if ( ! empty( $vb_data['content'] ) ) {
            return array(
                'version'  => $vb_data['version'] ?? '1.0.0',
                'elements' => $vb_data['content'],
                'settings' => array(),
            );
        }

        return $vb_data;
    }

    /**
     * Renderiza HTML de una landing
     *
     * @param array   $vbp_data Datos VBP.
     * @param WP_Post $post     Post de la landing.
     * @return string HTML renderizado.
     */
    private function render_landing_html( $vbp_data, $post ) {
        // Si VBP Canvas está disponible, usarlo
        if ( class_exists( 'Flavor_VBP_Canvas' ) ) {
            $canvas = Flavor_VBP_Canvas::get_instance();
            if ( method_exists( $canvas, 'renderizar_documento' ) ) {
                return $canvas->renderizar_documento( $vbp_data );
            }
        }

        // Renderizado fallback si VBP Canvas no está disponible
        return $this->render_fallback( $vbp_data, $post );
    }

    /**
     * Renderizado fallback cuando VBP Canvas no está disponible
     *
     * @param array   $vbp_data Datos VBP.
     * @param WP_Post $post     Post de la landing.
     * @return string HTML renderizado.
     */
    private function render_fallback( $vbp_data, $post ) {
        $elements = $vbp_data['elements'] ?? array();
        $settings = $vbp_data['settings'] ?? array();

        $styles = array();
        if ( ! empty( $settings['backgroundColor'] ) ) {
            $styles[] = 'background-color: ' . esc_attr( $settings['backgroundColor'] );
        }
        if ( ! empty( $settings['pageWidth'] ) ) {
            $styles[] = 'max-width: ' . absint( $settings['pageWidth'] ) . 'px';
            $styles[] = 'margin: 0 auto';
        }

        $html = '<div class="vbp-landing vbp-preview" style="' . implode( '; ', $styles ) . '">';

        foreach ( $elements as $element ) {
            if ( isset( $element['visible'] ) && false === $element['visible'] ) {
                continue;
            }
            $html .= $this->render_element_fallback( $element );
        }

        $html .= '</div>';

        // Añadir CSS inline básico
        $html .= $this->get_preview_styles();

        return $html;
    }

    /**
     * Renderiza un elemento en modo fallback
     *
     * @param array $element Elemento a renderizar.
     * @return string HTML del elemento.
     */
    private function render_element_fallback( $element ) {
        $type = $element['type'] ?? 'unknown';
        $data = $element['data'] ?? array();
        $element_id = $element['id'] ?? 'el_' . uniqid();
        $styles = $element['styles'] ?? array();

        // Obtener clases de animación
        $animation_classes = $this->get_animation_classes( $styles );
        $animation_attrs = $this->get_animation_attributes( $styles );

        $html = sprintf(
            '<div class="vbp-element vbp-element-%s %s" data-element-id="%s" data-type="%s" %s>',
            esc_attr( $type ),
            esc_attr( $animation_classes ),
            esc_attr( $element_id ),
            esc_attr( $type ),
            $animation_attrs
        );

        // Renderizar según tipo
        switch ( $type ) {
            case 'hero':
                $html .= $this->render_hero_fallback( $data );
                break;

            case 'features':
                $html .= $this->render_features_fallback( $data );
                break;

            case 'cta':
                $html .= $this->render_cta_fallback( $data );
                break;

            case 'testimonials':
                $html .= $this->render_testimonials_fallback( $data );
                break;

            case 'pricing':
                $html .= $this->render_pricing_fallback( $data );
                break;

            case 'faq':
                $html .= $this->render_faq_fallback( $data );
                break;

            case 'stats':
                $html .= $this->render_stats_fallback( $data );
                break;

            case 'text':
                $html .= wp_kses_post( $data['contenido'] ?? '' );
                break;

            default:
                $html .= $this->render_generic_fallback( $type, $data );
                break;
        }

        $html .= '</div>';

        return $html;
    }

    /**
     * Obtiene clases CSS de animación
     *
     * @param array $styles Estilos del elemento.
     * @return string Clases CSS.
     */
    private function get_animation_classes( $styles ) {
        $classes = array();
        $advanced = $styles['advanced'] ?? array();

        if ( ! empty( $advanced['entranceAnimation'] ) ) {
            $classes[] = 'vbp-animate';
            $classes[] = 'vbp-anim-' . sanitize_html_class( $advanced['entranceAnimation'] );
        }

        if ( ! empty( $advanced['hoverAnimation'] ) ) {
            $classes[] = 'vbp-hover-' . sanitize_html_class( $advanced['hoverAnimation'] );
        }

        if ( ! empty( $advanced['cssClasses'] ) ) {
            $classes[] = sanitize_text_field( $advanced['cssClasses'] );
        }

        return implode( ' ', $classes );
    }

    /**
     * Obtiene atributos de animación
     *
     * @param array $styles Estilos del elemento.
     * @return string Atributos HTML.
     */
    private function get_animation_attributes( $styles ) {
        $advanced = $styles['advanced'] ?? array();
        $attrs = array();

        if ( ! empty( $advanced['animDuration'] ) ) {
            $attrs[] = sprintf( 'data-anim-duration="%s"', esc_attr( $advanced['animDuration'] ) );
        }

        if ( ! empty( $advanced['animDelay'] ) ) {
            $attrs[] = sprintf( 'data-anim-delay="%s"', esc_attr( $advanced['animDelay'] ) );
        }

        if ( ! empty( $advanced['animTrigger'] ) ) {
            $attrs[] = sprintf( 'data-anim-trigger="%s"', esc_attr( $advanced['animTrigger'] ) );
        }

        return implode( ' ', $attrs );
    }

    /**
     * Renderiza un hero en modo fallback
     *
     * @param array $data Datos del hero.
     * @return string HTML.
     */
    private function render_hero_fallback( $data ) {
        $titulo = $data['titulo'] ?? '';
        $subtitulo = $data['subtitulo'] ?? '';
        $boton_texto = $data['boton_texto'] ?? '';
        $boton_url = $data['boton_url'] ?? '#';

        $html = '<section class="vbp-hero">';
        $html .= '<div class="vbp-hero-content">';

        if ( $titulo ) {
            $html .= '<h1 class="vbp-hero-title">' . esc_html( $titulo ) . '</h1>';
        }

        if ( $subtitulo ) {
            $html .= '<p class="vbp-hero-subtitle">' . esc_html( $subtitulo ) . '</p>';
        }

        if ( $boton_texto ) {
            $html .= sprintf(
                '<a href="%s" class="vbp-button vbp-button-primary">%s</a>',
                esc_url( $boton_url ),
                esc_html( $boton_texto )
            );
        }

        $html .= '</div>';
        $html .= '</section>';

        return $html;
    }

    /**
     * Renderiza features en modo fallback
     *
     * @param array $data Datos de features.
     * @return string HTML.
     */
    private function render_features_fallback( $data ) {
        $titulo = $data['titulo'] ?? '';
        $items = $data['items'] ?? array();
        $columnas = $data['columnas'] ?? 3;

        $html = '<section class="vbp-features">';

        if ( $titulo ) {
            $html .= '<h2 class="vbp-section-title">' . esc_html( $titulo ) . '</h2>';
        }

        $html .= sprintf( '<div class="vbp-features-grid vbp-cols-%d">', absint( $columnas ) );

        foreach ( $items as $item ) {
            $html .= '<div class="vbp-feature-item">';
            if ( ! empty( $item['icono'] ) ) {
                $html .= '<span class="vbp-feature-icon">' . esc_html( $item['icono'] ) . '</span>';
            }
            if ( ! empty( $item['titulo'] ) ) {
                $html .= '<h3 class="vbp-feature-title">' . esc_html( $item['titulo'] ) . '</h3>';
            }
            if ( ! empty( $item['descripcion'] ) ) {
                $html .= '<p class="vbp-feature-desc">' . esc_html( $item['descripcion'] ) . '</p>';
            }
            $html .= '</div>';
        }

        $html .= '</div>';
        $html .= '</section>';

        return $html;
    }

    /**
     * Renderiza CTA en modo fallback
     *
     * @param array $data Datos del CTA.
     * @return string HTML.
     */
    private function render_cta_fallback( $data ) {
        $titulo = $data['titulo'] ?? '';
        $subtitulo = $data['subtitulo'] ?? '';
        $boton_texto = $data['boton_texto'] ?? '';
        $boton_url = $data['boton_url'] ?? '#';

        $html = '<section class="vbp-cta">';

        if ( $titulo ) {
            $html .= '<h2 class="vbp-cta-title">' . esc_html( $titulo ) . '</h2>';
        }

        if ( $subtitulo ) {
            $html .= '<p class="vbp-cta-subtitle">' . esc_html( $subtitulo ) . '</p>';
        }

        if ( $boton_texto ) {
            $html .= sprintf(
                '<a href="%s" class="vbp-button vbp-button-primary">%s</a>',
                esc_url( $boton_url ),
                esc_html( $boton_texto )
            );
        }

        $html .= '</section>';

        return $html;
    }

    /**
     * Renderiza testimonials en modo fallback
     *
     * @param array $data Datos de testimonials.
     * @return string HTML.
     */
    private function render_testimonials_fallback( $data ) {
        $titulo = $data['titulo'] ?? '';
        $testimonios = $data['testimonios'] ?? array();

        $html = '<section class="vbp-testimonials">';

        if ( $titulo ) {
            $html .= '<h2 class="vbp-section-title">' . esc_html( $titulo ) . '</h2>';
        }

        $html .= '<div class="vbp-testimonials-grid">';

        foreach ( $testimonios as $testimonio ) {
            $html .= '<div class="vbp-testimonial-item">';
            if ( ! empty( $testimonio['texto'] ) ) {
                $html .= '<blockquote class="vbp-testimonial-text">"' . esc_html( $testimonio['texto'] ) . '"</blockquote>';
            }
            $html .= '<div class="vbp-testimonial-author">';
            if ( ! empty( $testimonio['nombre'] ) ) {
                $html .= '<strong>' . esc_html( $testimonio['nombre'] ) . '</strong>';
            }
            if ( ! empty( $testimonio['cargo'] ) ) {
                $html .= '<span>' . esc_html( $testimonio['cargo'] ) . '</span>';
            }
            $html .= '</div>';
            $html .= '</div>';
        }

        $html .= '</div>';
        $html .= '</section>';

        return $html;
    }

    /**
     * Renderiza pricing en modo fallback
     *
     * @param array $data Datos de pricing.
     * @return string HTML.
     */
    private function render_pricing_fallback( $data ) {
        $titulo = $data['titulo'] ?? '';
        $planes = $data['planes'] ?? array();
        $moneda = $data['moneda'] ?? '€';

        $html = '<section class="vbp-pricing">';

        if ( $titulo ) {
            $html .= '<h2 class="vbp-section-title">' . esc_html( $titulo ) . '</h2>';
        }

        $html .= '<div class="vbp-pricing-grid">';

        foreach ( $planes as $plan ) {
            $destacado = ! empty( $plan['destacado'] ) ? ' vbp-plan-featured' : '';
            $html .= '<div class="vbp-plan-item' . $destacado . '">';

            if ( ! empty( $plan['nombre'] ) ) {
                $html .= '<h3 class="vbp-plan-name">' . esc_html( $plan['nombre'] ) . '</h3>';
            }

            if ( isset( $plan['precio'] ) ) {
                $html .= '<div class="vbp-plan-price">';
                $html .= '<span class="vbp-price">' . esc_html( $moneda . $plan['precio'] ) . '</span>';
                $html .= '</div>';
            }

            if ( ! empty( $plan['caracteristicas'] ) ) {
                $html .= '<ul class="vbp-plan-features">';
                $features = is_array( $plan['caracteristicas'] )
                    ? $plan['caracteristicas']
                    : explode( "\n", $plan['caracteristicas'] );
                foreach ( $features as $feature ) {
                    $html .= '<li>' . esc_html( trim( $feature ) ) . '</li>';
                }
                $html .= '</ul>';
            }

            $html .= '</div>';
        }

        $html .= '</div>';
        $html .= '</section>';

        return $html;
    }

    /**
     * Renderiza FAQ en modo fallback
     *
     * @param array $data Datos de FAQ.
     * @return string HTML.
     */
    private function render_faq_fallback( $data ) {
        $titulo = $data['titulo'] ?? '';
        $faqs = $data['faqs'] ?? array();

        $html = '<section class="vbp-faq">';

        if ( $titulo ) {
            $html .= '<h2 class="vbp-section-title">' . esc_html( $titulo ) . '</h2>';
        }

        $html .= '<div class="vbp-faq-list">';

        foreach ( $faqs as $faq ) {
            $html .= '<details class="vbp-faq-item">';
            if ( ! empty( $faq['pregunta'] ) ) {
                $html .= '<summary class="vbp-faq-question">' . esc_html( $faq['pregunta'] ) . '</summary>';
            }
            if ( ! empty( $faq['respuesta'] ) ) {
                $html .= '<div class="vbp-faq-answer">' . wp_kses_post( $faq['respuesta'] ) . '</div>';
            }
            $html .= '</details>';
        }

        $html .= '</div>';
        $html .= '</section>';

        return $html;
    }

    /**
     * Renderiza stats en modo fallback
     *
     * @param array $data Datos de stats.
     * @return string HTML.
     */
    private function render_stats_fallback( $data ) {
        $titulo = $data['titulo'] ?? '';
        $stats = $data['stats'] ?? array();

        $html = '<section class="vbp-stats">';

        if ( $titulo ) {
            $html .= '<h2 class="vbp-section-title">' . esc_html( $titulo ) . '</h2>';
        }

        $html .= '<div class="vbp-stats-grid">';

        foreach ( $stats as $stat ) {
            $html .= '<div class="vbp-stat-item">';
            if ( ! empty( $stat['icono'] ) ) {
                $html .= '<span class="vbp-stat-icon">' . esc_html( $stat['icono'] ) . '</span>';
            }
            if ( ! empty( $stat['numero'] ) ) {
                $html .= '<span class="vbp-stat-number">' . esc_html( $stat['numero'] ) . '</span>';
            }
            if ( ! empty( $stat['label'] ) ) {
                $html .= '<span class="vbp-stat-label">' . esc_html( $stat['label'] ) . '</span>';
            }
            $html .= '</div>';
        }

        $html .= '</div>';
        $html .= '</section>';

        return $html;
    }

    /**
     * Renderiza un elemento genérico en modo fallback
     *
     * @param string $type Tipo de elemento.
     * @param array  $data Datos del elemento.
     * @return string HTML.
     */
    private function render_generic_fallback( $type, $data ) {
        $html = '<div class="vbp-generic-element">';
        $html .= '<small class="vbp-element-type">[' . esc_html( $type ) . ']</small>';

        // Mostrar datos básicos si existen
        if ( ! empty( $data['titulo'] ) ) {
            $html .= '<h3>' . esc_html( $data['titulo'] ) . '</h3>';
        }
        if ( ! empty( $data['contenido'] ) ) {
            $html .= wp_kses_post( $data['contenido'] );
        }

        $html .= '</div>';

        return $html;
    }

    /**
     * Obtiene estilos CSS básicos para preview
     *
     * @return string CSS inline.
     */
    private function get_preview_styles() {
        return '
        <style>
        .vbp-preview { font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Oxygen-Sans, Ubuntu, Cantarell, "Helvetica Neue", sans-serif; }
        .vbp-element { padding: 2rem; }
        .vbp-section-title { text-align: center; margin-bottom: 2rem; }
        .vbp-hero { text-align: center; padding: 4rem 2rem; background: linear-gradient(135deg, #1a1a2e 0%, #16213e 100%); color: white; }
        .vbp-hero-title { font-size: 2.5rem; margin-bottom: 1rem; }
        .vbp-hero-subtitle { font-size: 1.25rem; opacity: 0.9; margin-bottom: 2rem; }
        .vbp-button { display: inline-block; padding: 0.75rem 2rem; border-radius: 4px; text-decoration: none; font-weight: 600; }
        .vbp-button-primary { background: #4f46e5; color: white; }
        .vbp-features-grid { display: grid; gap: 2rem; }
        .vbp-cols-2 { grid-template-columns: repeat(2, 1fr); }
        .vbp-cols-3 { grid-template-columns: repeat(3, 1fr); }
        .vbp-cols-4 { grid-template-columns: repeat(4, 1fr); }
        .vbp-feature-item { text-align: center; padding: 1.5rem; }
        .vbp-feature-icon { font-size: 2.5rem; display: block; margin-bottom: 1rem; }
        .vbp-cta { text-align: center; padding: 4rem 2rem; background: #f8fafc; }
        .vbp-testimonials-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 2rem; }
        .vbp-testimonial-item { padding: 1.5rem; background: #f8fafc; border-radius: 8px; }
        .vbp-testimonial-text { font-style: italic; margin-bottom: 1rem; }
        .vbp-pricing-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 2rem; }
        .vbp-plan-item { padding: 2rem; border: 1px solid #e2e8f0; border-radius: 8px; text-align: center; }
        .vbp-plan-featured { border-color: #4f46e5; box-shadow: 0 4px 6px rgba(79, 70, 229, 0.1); }
        .vbp-plan-price { font-size: 2rem; font-weight: bold; margin: 1rem 0; }
        .vbp-plan-features { list-style: none; padding: 0; text-align: left; }
        .vbp-plan-features li { padding: 0.5rem 0; border-bottom: 1px solid #f1f5f9; }
        .vbp-faq-item { margin-bottom: 0.5rem; }
        .vbp-faq-question { padding: 1rem; background: #f8fafc; cursor: pointer; font-weight: 600; }
        .vbp-faq-answer { padding: 1rem; }
        .vbp-stats-grid { display: flex; justify-content: center; gap: 3rem; flex-wrap: wrap; }
        .vbp-stat-item { text-align: center; }
        .vbp-stat-icon { font-size: 2rem; display: block; }
        .vbp-stat-number { font-size: 2.5rem; font-weight: bold; display: block; }
        .vbp-stat-label { color: #64748b; }
        @media (max-width: 768px) {
            .vbp-cols-3, .vbp-cols-4 { grid-template-columns: 1fr; }
            .vbp-cols-2 { grid-template-columns: 1fr; }
        }
        </style>';
    }

    /**
     * Valida estructura y animaciones de una landing
     *
     * @param WP_REST_Request $request Petición REST.
     * @return WP_REST_Response|WP_Error
     */
    public function validate_page( $request ) {
        $post_id = absint( $request->get_param( 'id' ) );
        $post = get_post( $post_id );

        if ( ! $post ) {
            return new WP_Error( 'not_found', 'Landing no encontrada', array( 'status' => 404 ) );
        }

        $vbp_data = $this->get_vbp_data( $post_id );
        $validation = array(
            'post_id'         => $post_id,
            'title'           => $post->post_title,
            'status'          => $post->post_status,
            'is_valid'        => true,
            'has_content'     => ! empty( $vbp_data['elements'] ),
            'elements_count'  => count( $vbp_data['elements'] ?? array() ),
            'elements'        => array(),
            'animations'      => array(),
            'issues'          => array(),
            'warnings'        => array(),
        );

        if ( empty( $vbp_data['elements'] ) ) {
            $validation['is_valid'] = false;
            $validation['issues'][] = 'La landing no tiene elementos';
            return new WP_REST_Response( $validation, 200 );
        }

        // Validar cada elemento
        foreach ( $vbp_data['elements'] as $index => $element ) {
            $element_validation = $this->validate_element( $element, $index );
            $validation['elements'][] = $element_validation;

            if ( ! empty( $element_validation['issues'] ) ) {
                $validation['issues'] = array_merge( $validation['issues'], $element_validation['issues'] );
            }

            if ( ! empty( $element_validation['animations'] ) ) {
                $validation['animations'][] = $element_validation['animations'];
            }
        }

        // Determinar validez
        $validation['is_valid'] = empty( $validation['issues'] );
        $validation['animations_count'] = count( $validation['animations'] );

        return new WP_REST_Response( $validation, 200 );
    }

    /**
     * Valida un elemento individual
     *
     * @param array $element Elemento a validar.
     * @param int   $index   Índice del elemento.
     * @return array Resultado de validación.
     */
    private function validate_element( $element, $index ) {
        $result = array(
            'index'      => $index,
            'id'         => $element['id'] ?? 'unknown',
            'type'       => $element['type'] ?? 'unknown',
            'name'       => $element['name'] ?? '',
            'visible'    => $element['visible'] ?? true,
            'has_data'   => ! empty( $element['data'] ),
            'has_styles' => ! empty( $element['styles'] ),
            'issues'     => array(),
            'animations' => null,
        );

        // Verificar tipo válido
        $valid_types = array(
            'hero', 'features', 'cta', 'testimonials', 'pricing', 'faq',
            'stats', 'team', 'contact', 'gallery', 'text', 'section',
            'module_grupos_consumo', 'module_eventos', 'module_marketplace', 'module_cursos',
        );

        if ( ! in_array( $result['type'], $valid_types, true ) ) {
            $result['issues'][] = "Tipo de elemento desconocido: {$result['type']}";
        }

        // Verificar animaciones
        $advanced = $element['styles']['advanced'] ?? array();
        if ( ! empty( $advanced['entranceAnimation'] ) || ! empty( $advanced['hoverAnimation'] ) ) {
            $result['animations'] = array(
                'element_id' => $result['id'],
                'entrance'   => $advanced['entranceAnimation'] ?? null,
                'hover'      => $advanced['hoverAnimation'] ?? null,
                'loop'       => $advanced['loopAnimation'] ?? null,
                'duration'   => $advanced['animDuration'] ?? '0.6s',
                'delay'      => $advanced['animDelay'] ?? '0s',
                'trigger'    => $advanced['animTrigger'] ?? 'scroll',
            );
        }

        return $result;
    }

    /**
     * Obtiene información/metadata de una landing
     *
     * @param WP_REST_Request $request Petición REST.
     * @return WP_REST_Response|WP_Error
     */
    public function get_page_info( $request ) {
        $post_id = absint( $request->get_param( 'id' ) );
        $post = get_post( $post_id );

        if ( ! $post ) {
            return new WP_Error( 'not_found', 'Landing no encontrada', array( 'status' => 404 ) );
        }

        $vbp_data = $this->get_vbp_data( $post_id );

        // Contar tipos de elementos
        $element_types = array();
        foreach ( $vbp_data['elements'] ?? array() as $element ) {
            $type = $element['type'] ?? 'unknown';
            if ( ! isset( $element_types[ $type ] ) ) {
                $element_types[ $type ] = 0;
            }
            $element_types[ $type ]++;
        }

        // Contar animaciones
        $animations_count = 0;
        foreach ( $vbp_data['elements'] ?? array() as $element ) {
            $advanced = $element['styles']['advanced'] ?? array();
            if ( ! empty( $advanced['entranceAnimation'] ) || ! empty( $advanced['hoverAnimation'] ) ) {
                $animations_count++;
            }
        }

        $info = array(
            'post'           => array(
                'id'         => $post_id,
                'title'      => $post->post_title,
                'status'     => $post->post_status,
                'created'    => $post->post_date,
                'modified'   => $post->post_modified,
                'author'     => get_the_author_meta( 'display_name', $post->post_author ),
            ),
            'urls'           => array(
                'permalink'  => get_permalink( $post_id ),
                'preview'    => rest_url( self::NAMESPACE . "/preview/{$post_id}" ),
                'validate'   => rest_url( self::NAMESPACE . "/preview/{$post_id}/validate" ),
                'edit'       => admin_url( "admin.php?page=vbp-editor&post_id={$post_id}" ),
            ),
            'vbp'            => array(
                'version'        => $vbp_data['version'] ?? 'unknown',
                'elements_count' => count( $vbp_data['elements'] ?? array() ),
                'element_types'  => $element_types,
                'has_animations' => $animations_count > 0,
                'animations_count' => $animations_count,
                'has_settings'   => ! empty( $vbp_data['settings'] ),
            ),
        );

        return new WP_REST_Response( $info, 200 );
    }

    /**
     * Renderiza preview de un elemento específico
     *
     * @param WP_REST_Request $request Petición REST.
     * @return WP_REST_Response|WP_Error
     */
    public function render_element_preview( $request ) {
        $post_id = absint( $request->get_param( 'id' ) );
        $element_id = sanitize_text_field( $request->get_param( 'element_id' ) );

        $post = get_post( $post_id );
        if ( ! $post ) {
            return new WP_Error( 'not_found', 'Landing no encontrada', array( 'status' => 404 ) );
        }

        $vbp_data = $this->get_vbp_data( $post_id );

        // Buscar el elemento
        $element = null;
        foreach ( $vbp_data['elements'] ?? array() as $el ) {
            if ( ( $el['id'] ?? '' ) === $element_id ) {
                $element = $el;
                break;
            }
        }

        if ( ! $element ) {
            return new WP_Error( 'element_not_found', 'Elemento no encontrado', array( 'status' => 404 ) );
        }

        // Renderizar solo ese elemento
        $html = $this->render_element_fallback( $element );
        $html .= $this->get_preview_styles();

        return new WP_REST_Response( array(
            'success'    => true,
            'element_id' => $element_id,
            'type'       => $element['type'] ?? 'unknown',
            'html'       => $html,
        ), 200 );
    }
}

// Inicializar
Flavor_VBP_Preview_API::get_instance();

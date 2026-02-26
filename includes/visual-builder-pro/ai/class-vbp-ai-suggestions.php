<?php
/**
 * Visual Builder Pro - AI Suggestions
 *
 * Sistema de sugerencias contextuales basadas en IA.
 *
 * @package FlavorChatIA
 * @subpackage Visual_Builder_Pro
 * @since 2.1.0
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Sistema de sugerencias de IA para el Visual Builder Pro
 */
class Flavor_VBP_AI_Suggestions {

    /**
     * Instancia singleton
     *
     * @var Flavor_VBP_AI_Suggestions|null
     */
    private static $instancia = null;

    /**
     * Obtiene la instancia singleton
     *
     * @return Flavor_VBP_AI_Suggestions
     */
    public static function get_instance() {
        if ( null === self::$instancia ) {
            self::$instancia = new self();
        }
        return self::$instancia;
    }

    /**
     * Obtiene sugerencias para un tipo de elemento
     *
     * @param string $element_type Tipo de elemento.
     * @param array  $page_context Contexto de la página.
     * @return array
     */
    public function get_suggestions_for_element( $element_type, $page_context = array() ) {
        $suggestions = array(
            'quick_actions' => $this->get_quick_actions( $element_type ),
            'templates'     => $this->get_content_templates( $element_type, $page_context ),
            'tips'          => $this->get_tips( $element_type ),
        );

        return $suggestions;
    }

    /**
     * Obtiene acciones rápidas para un tipo de elemento
     *
     * @param string $element_type Tipo de elemento.
     * @return array
     */
    private function get_quick_actions( $element_type ) {
        $all_actions = array(
            'hero'        => array(
                array(
                    'id'          => 'generate_title',
                    'label'       => __( 'Generar título impactante', 'flavor-chat-ia' ),
                    'type'        => 'hero_title',
                    'icon'        => 'sparkles',
                ),
                array(
                    'id'          => 'generate_subtitle',
                    'label'       => __( 'Generar subtítulo', 'flavor-chat-ia' ),
                    'type'        => 'hero_subtitle',
                    'icon'        => 'document-text',
                ),
                array(
                    'id'          => 'generate_cta',
                    'label'       => __( 'Sugerir textos CTA', 'flavor-chat-ia' ),
                    'type'        => 'cta_button',
                    'icon'        => 'cursor-click',
                ),
            ),
            'features'    => array(
                array(
                    'id'          => 'generate_features',
                    'label'       => __( 'Generar características', 'flavor-chat-ia' ),
                    'type'        => 'features_list',
                    'icon'        => 'view-grid',
                ),
                array(
                    'id'          => 'improve_descriptions',
                    'label'       => __( 'Mejorar descripciones', 'flavor-chat-ia' ),
                    'action'      => 'rewrite',
                    'icon'        => 'pencil',
                ),
            ),
            'testimonials' => array(
                array(
                    'id'          => 'generate_testimonial',
                    'label'       => __( 'Generar testimonio', 'flavor-chat-ia' ),
                    'type'        => 'testimonial',
                    'icon'        => 'chat',
                ),
            ),
            'cta'         => array(
                array(
                    'id'          => 'generate_cta_text',
                    'label'       => __( 'Generar texto CTA', 'flavor-chat-ia' ),
                    'type'        => 'cta_button',
                    'icon'        => 'cursor-click',
                ),
                array(
                    'id'          => 'make_persuasive',
                    'label'       => __( 'Hacer más persuasivo', 'flavor-chat-ia' ),
                    'action'      => 'persuasive',
                    'icon'        => 'fire',
                ),
            ),
            'stats'       => array(
                array(
                    'id'          => 'generate_stats',
                    'label'       => __( 'Generar estadísticas', 'flavor-chat-ia' ),
                    'type'        => 'stats',
                    'icon'        => 'chart-bar',
                ),
            ),
            'faq'         => array(
                array(
                    'id'          => 'generate_faq',
                    'label'       => __( 'Generar FAQs', 'flavor-chat-ia' ),
                    'type'        => 'faq',
                    'icon'        => 'question-mark-circle',
                ),
            ),
            'text'        => array(
                array(
                    'id'          => 'improve_text',
                    'label'       => __( 'Mejorar texto', 'flavor-chat-ia' ),
                    'action'      => 'rewrite',
                    'icon'        => 'pencil',
                ),
                array(
                    'id'          => 'shorten_text',
                    'label'       => __( 'Acortar texto', 'flavor-chat-ia' ),
                    'action'      => 'shorten',
                    'icon'        => 'scissors',
                ),
                array(
                    'id'          => 'expand_text',
                    'label'       => __( 'Expandir texto', 'flavor-chat-ia' ),
                    'action'      => 'expand',
                    'icon'        => 'document-add',
                ),
            ),
            'button'      => array(
                array(
                    'id'          => 'suggest_cta',
                    'label'       => __( 'Sugerir texto', 'flavor-chat-ia' ),
                    'type'        => 'cta_button',
                    'icon'        => 'sparkles',
                ),
            ),
            'heading'     => array(
                array(
                    'id'          => 'generate_heading',
                    'label'       => __( 'Generar título', 'flavor-chat-ia' ),
                    'type'        => 'hero_title',
                    'icon'        => 'sparkles',
                ),
                array(
                    'id'          => 'improve_heading',
                    'label'       => __( 'Mejorar título', 'flavor-chat-ia' ),
                    'action'      => 'rewrite',
                    'icon'        => 'pencil',
                ),
            ),
        );

        // Acciones por defecto para cualquier elemento
        $default_actions = array(
            array(
                'id'          => 'generate_description',
                'label'       => __( 'Generar descripción', 'flavor-chat-ia' ),
                'type'        => 'description',
                'icon'        => 'document-text',
            ),
            array(
                'id'          => 'translate',
                'label'       => __( 'Traducir contenido', 'flavor-chat-ia' ),
                'action'      => 'translate',
                'icon'        => 'translate',
            ),
        );

        $element_actions = $all_actions[ $element_type ] ?? array();

        return array_merge( $element_actions, $default_actions );
    }

    /**
     * Obtiene plantillas de contenido para un tipo de elemento
     *
     * @param string $element_type Tipo de elemento.
     * @param array  $page_context Contexto de la página.
     * @return array
     */
    private function get_content_templates( $element_type, $page_context = array() ) {
        $industry = $page_context['industry'] ?? 'general';

        $templates = array(
            'hero' => array(
                array(
                    'id'       => 'hero_startup',
                    'name'     => __( 'Startup Tech', 'flavor-chat-ia' ),
                    'content'  => array(
                        'titulo'       => 'El futuro de la productividad',
                        'subtitulo'    => 'La herramienta que tu equipo necesita para trabajar mejor',
                        'boton_texto'  => 'Empezar gratis',
                    ),
                ),
                array(
                    'id'       => 'hero_community',
                    'name'     => __( 'Comunidad', 'flavor-chat-ia' ),
                    'content'  => array(
                        'titulo'       => 'Juntos somos más fuertes',
                        'subtitulo'    => 'Únete a una comunidad que transforma el mundo',
                        'boton_texto'  => 'Unirse ahora',
                    ),
                ),
                array(
                    'id'       => 'hero_ecommerce',
                    'name'     => __( 'E-commerce', 'flavor-chat-ia' ),
                    'content'  => array(
                        'titulo'       => 'Descubre productos únicos',
                        'subtitulo'    => 'Calidad garantizada con envío rápido y devolución fácil',
                        'boton_texto'  => 'Ver catálogo',
                    ),
                ),
            ),
            'features' => array(
                array(
                    'id'      => 'features_saas',
                    'name'    => __( 'SaaS / Tecnología', 'flavor-chat-ia' ),
                    'content' => array(
                        array( 'icono' => '⚡', 'titulo' => 'Rápido', 'descripcion' => 'Implementación en minutos, no días' ),
                        array( 'icono' => '🔒', 'titulo' => 'Seguro', 'descripcion' => 'Protección de datos de nivel empresarial' ),
                        array( 'icono' => '📱', 'titulo' => 'Multiplataforma', 'descripcion' => 'Funciona en cualquier dispositivo' ),
                    ),
                ),
                array(
                    'id'      => 'features_eco',
                    'name'    => __( 'Sostenibilidad', 'flavor-chat-ia' ),
                    'content' => array(
                        array( 'icono' => '🌱', 'titulo' => 'Ecológico', 'descripcion' => 'Materiales 100% reciclables' ),
                        array( 'icono' => '♻️', 'titulo' => 'Circular', 'descripcion' => 'Economía circular integrada' ),
                        array( 'icono' => '🌍', 'titulo' => 'Local', 'descripcion' => 'Producción de proximidad' ),
                    ),
                ),
            ),
            'cta' => array(
                array(
                    'id'       => 'cta_trial',
                    'name'     => __( 'Prueba Gratuita', 'flavor-chat-ia' ),
                    'content'  => array(
                        'titulo'      => '¿Listo para empezar?',
                        'subtitulo'   => 'Prueba gratis durante 14 días, sin compromiso',
                        'boton_texto' => 'Empezar prueba',
                    ),
                ),
                array(
                    'id'       => 'cta_contact',
                    'name'     => __( 'Contacto', 'flavor-chat-ia' ),
                    'content'  => array(
                        'titulo'      => '¿Tienes preguntas?',
                        'subtitulo'   => 'Nuestro equipo está aquí para ayudarte',
                        'boton_texto' => 'Contactar',
                    ),
                ),
                array(
                    'id'       => 'cta_newsletter',
                    'name'     => __( 'Newsletter', 'flavor-chat-ia' ),
                    'content'  => array(
                        'titulo'      => 'No te pierdas nada',
                        'subtitulo'   => 'Recibe las últimas novedades en tu email',
                        'boton_texto' => 'Suscribirse',
                    ),
                ),
            ),
        );

        return $templates[ $element_type ] ?? array();
    }

    /**
     * Obtiene tips/consejos para un tipo de elemento
     *
     * @param string $element_type Tipo de elemento.
     * @return array
     */
    private function get_tips( $element_type ) {
        $tips = array(
            'hero' => array(
                __( 'Un buen título hero tiene máximo 8 palabras y comunica el beneficio principal.', 'flavor-chat-ia' ),
                __( 'El subtítulo debe complementar el título, no repetirlo.', 'flavor-chat-ia' ),
                __( 'Los CTA con verbos de acción ("Empezar", "Descubrir") funcionan mejor.', 'flavor-chat-ia' ),
            ),
            'features' => array(
                __( 'Usa 3-6 características principales, no más.', 'flavor-chat-ia' ),
                __( 'Cada característica debe destacar un beneficio, no solo una función.', 'flavor-chat-ia' ),
                __( 'Los iconos ayudan a escanear rápidamente el contenido.', 'flavor-chat-ia' ),
            ),
            'testimonials' => array(
                __( 'Los testimonios con nombre, foto y cargo generan más confianza.', 'flavor-chat-ia' ),
                __( 'Los números y resultados específicos son más creíbles.', 'flavor-chat-ia' ),
            ),
            'cta' => array(
                __( 'Un CTA efectivo crea urgencia sin ser agresivo.', 'flavor-chat-ia' ),
                __( 'Evita textos genéricos como "Enviar" o "Click aquí".', 'flavor-chat-ia' ),
                __( 'El texto del botón debe decir qué pasará al hacer clic.', 'flavor-chat-ia' ),
            ),
            'text' => array(
                __( 'Los párrafos cortos (3-4 líneas) son más fáciles de leer.', 'flavor-chat-ia' ),
                __( 'Usa negritas para destacar ideas clave.', 'flavor-chat-ia' ),
            ),
        );

        $default_tips = array(
            __( 'Mantén el contenido enfocado en los beneficios para el usuario.', 'flavor-chat-ia' ),
            __( 'Usa un tono consistente en toda la página.', 'flavor-chat-ia' ),
        );

        return $tips[ $element_type ] ?? $default_tips;
    }

    /**
     * Obtiene sugerencias de contenido basadas en el contexto de la página
     *
     * @param array $page_context Contexto de la página.
     * @return array
     */
    public function get_page_suggestions( $page_context ) {
        $title = $page_context['title'] ?? '';
        $industry = $page_context['industry'] ?? 'general';
        $existing_elements = $page_context['elements'] ?? array();

        $suggestions = array();

        // Sugerir elementos que faltan basándose en lo que ya existe
        $element_types = array_column( $existing_elements, 'type' );

        if ( ! in_array( 'hero', $element_types, true ) ) {
            $suggestions[] = array(
                'type'        => 'add_element',
                'element'     => 'hero',
                'reason'      => __( 'Añade una sección hero para captar la atención', 'flavor-chat-ia' ),
                'priority'    => 'high',
            );
        }

        if ( ! in_array( 'features', $element_types, true ) && count( $element_types ) > 0 ) {
            $suggestions[] = array(
                'type'        => 'add_element',
                'element'     => 'features',
                'reason'      => __( 'Destaca los beneficios principales con una sección de características', 'flavor-chat-ia' ),
                'priority'    => 'medium',
            );
        }

        if ( ! in_array( 'cta', $element_types, true ) && count( $element_types ) > 1 ) {
            $suggestions[] = array(
                'type'        => 'add_element',
                'element'     => 'cta',
                'reason'      => __( 'Añade un Call-to-Action para guiar a los visitantes', 'flavor-chat-ia' ),
                'priority'    => 'high',
            );
        }

        if ( ! in_array( 'testimonials', $element_types, true ) && count( $element_types ) > 2 ) {
            $suggestions[] = array(
                'type'        => 'add_element',
                'element'     => 'testimonials',
                'reason'      => __( 'Los testimonios aumentan la confianza y conversión', 'flavor-chat-ia' ),
                'priority'    => 'medium',
            );
        }

        return $suggestions;
    }

    /**
     * Obtiene palabras clave sugeridas basadas en la industria
     *
     * @param string $industry Industria.
     * @return array
     */
    public function get_industry_keywords( $industry ) {
        $keywords = array(
            'tech'          => array( 'innovación', 'eficiencia', 'automatización', 'inteligente', 'escalable' ),
            'ecommerce'     => array( 'exclusivo', 'calidad', 'envío gratis', 'garantía', 'descuento' ),
            'health'        => array( 'bienestar', 'natural', 'cuidado', 'salud', 'profesional' ),
            'community'     => array( 'juntos', 'colaborativo', 'participación', 'comunidad', 'impacto' ),
            'sustainability' => array( 'sostenible', 'ecológico', 'local', 'responsable', 'circular' ),
            'education'     => array( 'aprender', 'crecer', 'conocimiento', 'desarrollo', 'formación' ),
            'finance'       => array( 'seguro', 'rentable', 'transparente', 'confianza', 'inversión' ),
            'food'          => array( 'fresco', 'artesanal', 'local', 'sabor', 'tradición' ),
        );

        return $keywords[ $industry ] ?? array( 'calidad', 'profesional', 'confianza', 'servicio', 'experiencia' );
    }
}

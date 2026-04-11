<?php
/**
 * Visual Builder Pro - AI Layout Assistant
 *
 * Sistema de asistencia de diseño con IA para sugerencias de layout,
 * auto-spacing, colores complementarios y generación de variantes.
 *
 * @package FlavorPlatform
 * @subpackage Visual_Builder_Pro
 * @since 2.2.0
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Controlador de AI Layout Assistant para el Visual Builder Pro
 */
class Flavor_VBP_AI_Layout {

    /**
     * Instancia singleton
     *
     * @var Flavor_VBP_AI_Layout|null
     */
    private static $instancia = null;

    /**
     * Prefijo de cache
     *
     * @var string
     */
    const CACHE_PREFIX = 'vbp_ai_layout_';

    /**
     * Tiempo de cache en segundos (1 hora)
     *
     * @var int
     */
    const CACHE_EXPIRATION = 3600;

    /**
     * Grid base para spacing (8px system)
     *
     * @var int
     */
    const SPACING_GRID_BASE = 8;

    /**
     * Obtiene la instancia singleton
     *
     * @return Flavor_VBP_AI_Layout
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
        $this->register_rest_routes();
    }

    /**
     * Registra rutas REST
     */
    private function register_rest_routes() {
        add_action( 'rest_api_init', array( $this, 'register_routes' ) );
    }

    /**
     * Registra las rutas REST de AI Layout
     */
    public function register_routes() {
        $namespace = 'flavor-vbp/v1';

        // Generar layout desde descripcion natural
        register_rest_route(
            $namespace,
            '/ai/layout/generate',
            array(
                'methods'             => WP_REST_Server::CREATABLE,
                'callback'            => array( $this, 'generate_layout' ),
                'permission_callback' => array( $this, 'check_permission' ),
                'args'                => array(
                    'prompt'  => array(
                        'required'          => true,
                        'type'              => 'string',
                        'sanitize_callback' => 'sanitize_textarea_field',
                    ),
                    'context' => array(
                        'required' => false,
                        'type'     => 'object',
                        'default'  => array(),
                    ),
                ),
            )
        );

        // Auto-spacing para elementos seleccionados
        register_rest_route(
            $namespace,
            '/ai/layout/auto-spacing',
            array(
                'methods'             => WP_REST_Server::CREATABLE,
                'callback'            => array( $this, 'calculate_auto_spacing' ),
                'permission_callback' => array( $this, 'check_permission' ),
                'args'                => array(
                    'elements' => array(
                        'required' => true,
                        'type'     => 'array',
                    ),
                    'gridBase' => array(
                        'required' => false,
                        'type'     => 'integer',
                        'default'  => self::SPACING_GRID_BASE,
                    ),
                ),
            )
        );

        // Sugerir colores complementarios
        register_rest_route(
            $namespace,
            '/ai/layout/colors',
            array(
                'methods'             => WP_REST_Server::CREATABLE,
                'callback'            => array( $this, 'suggest_colors' ),
                'permission_callback' => array( $this, 'check_permission' ),
                'args'                => array(
                    'baseColor' => array(
                        'required'          => true,
                        'type'              => 'string',
                        'sanitize_callback' => 'sanitize_hex_color',
                    ),
                    'scheme'    => array(
                        'required' => false,
                        'type'     => 'string',
                        'default'  => 'complementary',
                        'enum'     => array( 'complementary', 'analogous', 'triadic', 'split-complementary', 'monochromatic' ),
                    ),
                ),
            )
        );

        // Generar variantes de diseño
        register_rest_route(
            $namespace,
            '/ai/layout/variants',
            array(
                'methods'             => WP_REST_Server::CREATABLE,
                'callback'            => array( $this, 'generate_variants' ),
                'permission_callback' => array( $this, 'check_permission' ),
                'args'                => array(
                    'element' => array(
                        'required' => true,
                        'type'     => 'object',
                    ),
                    'count'   => array(
                        'required' => false,
                        'type'     => 'integer',
                        'default'  => 3,
                        'minimum'  => 1,
                        'maximum'  => 5,
                    ),
                ),
            )
        );

        // Analizar y sugerir mejoras
        register_rest_route(
            $namespace,
            '/ai/layout/analyze',
            array(
                'methods'             => WP_REST_Server::CREATABLE,
                'callback'            => array( $this, 'analyze_design' ),
                'permission_callback' => array( $this, 'check_permission' ),
                'args'                => array(
                    'elements' => array(
                        'required' => true,
                        'type'     => 'array',
                    ),
                ),
            )
        );

        // Obtener templates predefinidos
        register_rest_route(
            $namespace,
            '/ai/layout/templates',
            array(
                'methods'             => WP_REST_Server::READABLE,
                'callback'            => array( $this, 'get_layout_templates' ),
                'permission_callback' => array( $this, 'check_permission' ),
                'args'                => array(
                    'category' => array(
                        'required' => false,
                        'type'     => 'string',
                        'default'  => '',
                    ),
                ),
            )
        );

        // Ejecutar comando de IA
        register_rest_route(
            $namespace,
            '/ai/layout/command',
            array(
                'methods'             => WP_REST_Server::CREATABLE,
                'callback'            => array( $this, 'execute_command' ),
                'permission_callback' => array( $this, 'check_permission' ),
                'args'                => array(
                    'command'       => array(
                        'required'          => true,
                        'type'              => 'string',
                        'sanitize_callback' => 'sanitize_text_field',
                    ),
                    'selectedIds'   => array(
                        'required' => false,
                        'type'     => 'array',
                        'default'  => array(),
                    ),
                    'pageContext'   => array(
                        'required' => false,
                        'type'     => 'object',
                        'default'  => array(),
                    ),
                ),
            )
        );

        // Estado de AI Layout (disponibilidad, configuracion)
        register_rest_route(
            $namespace,
            '/ai/layout/status',
            array(
                'methods'             => WP_REST_Server::READABLE,
                'callback'            => array( $this, 'get_status' ),
                'permission_callback' => array( $this, 'check_permission' ),
            )
        );
    }

    /**
     * Verifica permisos
     *
     * @return bool
     */
    public function check_permission() {
        return current_user_can( 'edit_posts' );
    }

    /**
     * Genera layout desde descripcion natural
     *
     * @param WP_REST_Request $request Peticion REST.
     * @return WP_REST_Response|WP_Error
     */
    public function generate_layout( $request ) {
        $prompt = $request->get_param( 'prompt' );
        $context = $request->get_param( 'context' ) ?: array();

        // Verificar cache
        $cache_key = self::CACHE_PREFIX . 'layout_' . md5( $prompt . wp_json_encode( $context ) );
        $cached_result = get_transient( $cache_key );

        if ( false !== $cached_result ) {
            return new WP_REST_Response(
                array(
                    'success' => true,
                    'blocks'  => $cached_result,
                    'cached'  => true,
                ),
                200
            );
        }

        // Parsear comando natural
        $parsed_command = $this->parse_natural_command( $prompt );

        // Si hay una plantilla predefinida que coincide, usarla
        $template_blocks = $this->match_template( $parsed_command );

        if ( ! empty( $template_blocks ) ) {
            // Guardar en cache
            set_transient( $cache_key, $template_blocks, self::CACHE_EXPIRATION );

            return new WP_REST_Response(
                array(
                    'success'  => true,
                    'blocks'   => $template_blocks,
                    'template' => true,
                ),
                200
            );
        }

        // Intentar con IA si esta disponible
        if ( $this->is_ai_available() ) {
            $ai_blocks = $this->generate_with_ai( $prompt, $context );

            if ( ! is_wp_error( $ai_blocks ) ) {
                set_transient( $cache_key, $ai_blocks, self::CACHE_EXPIRATION );

                return new WP_REST_Response(
                    array(
                        'success' => true,
                        'blocks'  => $ai_blocks,
                        'ai'      => true,
                    ),
                    200
                );
            }
        }

        // Fallback a plantilla generica basada en palabras clave
        $fallback_blocks = $this->generate_fallback_layout( $parsed_command );

        return new WP_REST_Response(
            array(
                'success'  => true,
                'blocks'   => $fallback_blocks,
                'fallback' => true,
            ),
            200
        );
    }

    /**
     * Calcula auto-spacing para elementos
     *
     * @param WP_REST_Request $request Peticion REST.
     * @return WP_REST_Response
     */
    public function calculate_auto_spacing( $request ) {
        $elements = $request->get_param( 'elements' );
        $grid_base = $request->get_param( 'gridBase' );

        $spacing_suggestions = array();

        foreach ( $elements as $element ) {
            $element_id = isset( $element['id'] ) ? $element['id'] : '';
            $element_type = isset( $element['type'] ) ? $element['type'] : 'unknown';

            // Calcular spacing recomendado basado en tipo y contexto
            $suggested_spacing = $this->calculate_element_spacing( $element, $grid_base );

            $spacing_suggestions[] = array(
                'elementId' => $element_id,
                'type'      => $element_type,
                'spacing'   => $suggested_spacing,
            );
        }

        return new WP_REST_Response(
            array(
                'success'     => true,
                'suggestions' => $spacing_suggestions,
                'gridBase'    => $grid_base,
            ),
            200
        );
    }

    /**
     * Sugiere colores complementarios
     *
     * @param WP_REST_Request $request Peticion REST.
     * @return WP_REST_Response
     */
    public function suggest_colors( $request ) {
        $base_color = $request->get_param( 'baseColor' );
        $scheme = $request->get_param( 'scheme' );

        // Convertir hex a HSL
        $hsl = $this->hex_to_hsl( $base_color );

        if ( ! $hsl ) {
            return new WP_Error(
                'invalid_color',
                __( 'Color invalido', FLAVOR_PLATFORM_TEXT_DOMAIN ),
                array( 'status' => 400 )
            );
        }

        // Generar paleta segun esquema
        $palette = $this->generate_color_palette( $hsl, $scheme );

        // Generar variaciones (light, dark)
        $variations = $this->generate_color_variations( $base_color );

        return new WP_REST_Response(
            array(
                'success'    => true,
                'baseColor'  => $base_color,
                'scheme'     => $scheme,
                'palette'    => $palette,
                'variations' => $variations,
            ),
            200
        );
    }

    /**
     * Genera variantes de diseno
     *
     * @param WP_REST_Request $request Peticion REST.
     * @return WP_REST_Response|WP_Error
     */
    public function generate_variants( $request ) {
        $element = $request->get_param( 'element' );
        $count = $request->get_param( 'count' );

        $variants = array();
        $element_type = isset( $element['type'] ) ? $element['type'] : 'section';

        // Generar variantes basadas en el tipo
        for ( $i = 0; $i < $count; $i++ ) {
            $variant = $this->create_element_variant( $element, $i );
            $variants[] = $variant;
        }

        return new WP_REST_Response(
            array(
                'success'  => true,
                'variants' => $variants,
            ),
            200
        );
    }

    /**
     * Analiza diseno y sugiere mejoras
     *
     * @param WP_REST_Request $request Peticion REST.
     * @return WP_REST_Response
     */
    public function analyze_design( $request ) {
        $elements = $request->get_param( 'elements' );

        $issues = array();
        $suggestions = array();

        foreach ( $elements as $element ) {
            // Verificar contraste
            $contrast_issues = $this->check_contrast( $element );
            if ( ! empty( $contrast_issues ) ) {
                $issues = array_merge( $issues, $contrast_issues );
            }

            // Verificar spacing inconsistente
            $spacing_issues = $this->check_spacing_consistency( $element );
            if ( ! empty( $spacing_issues ) ) {
                $issues = array_merge( $issues, $spacing_issues );
            }

            // Sugerencias de mejora
            $element_suggestions = $this->get_improvement_suggestions( $element );
            if ( ! empty( $element_suggestions ) ) {
                $suggestions = array_merge( $suggestions, $element_suggestions );
            }
        }

        return new WP_REST_Response(
            array(
                'success'     => true,
                'issues'      => $issues,
                'suggestions' => $suggestions,
                'score'       => $this->calculate_design_score( $elements, $issues ),
            ),
            200
        );
    }

    /**
     * Obtiene templates de layout predefinidos
     *
     * @param WP_REST_Request $request Peticion REST.
     * @return WP_REST_Response
     */
    public function get_layout_templates( $request ) {
        $category = $request->get_param( 'category' );

        $templates = $this->get_predefined_templates();

        if ( ! empty( $category ) ) {
            $templates = array_filter(
                $templates,
                function( $template ) use ( $category ) {
                    return isset( $template['category'] ) && $template['category'] === $category;
                }
            );
        }

        return new WP_REST_Response(
            array(
                'success'   => true,
                'templates' => array_values( $templates ),
            ),
            200
        );
    }

    /**
     * Ejecuta un comando de IA
     *
     * @param WP_REST_Request $request Peticion REST.
     * @return WP_REST_Response|WP_Error
     */
    public function execute_command( $request ) {
        $command = $request->get_param( 'command' );
        $selected_ids = $request->get_param( 'selectedIds' ) ?: array();
        $page_context = $request->get_param( 'pageContext' ) ?: array();

        // Parsear comando
        $parsed = $this->parse_natural_command( $command );

        // Ejecutar accion basada en comando
        $result = $this->execute_parsed_command( $parsed, $selected_ids, $page_context );

        if ( is_wp_error( $result ) ) {
            return $result;
        }

        return new WP_REST_Response(
            array(
                'success' => true,
                'command' => $parsed,
                'result'  => $result,
            ),
            200
        );
    }

    /**
     * Obtiene estado de AI Layout
     *
     * @return WP_REST_Response
     */
    public function get_status() {
        $ai_available = $this->is_ai_available();

        return new WP_REST_Response(
            array(
                'success'          => true,
                'aiAvailable'      => $ai_available,
                'templatesCount'   => count( $this->get_predefined_templates() ),
                'fallbackEnabled'  => true,
                'features'         => array(
                    'generateLayout' => true,
                    'autoSpacing'    => true,
                    'colorSuggest'   => true,
                    'variants'       => true,
                    'analyze'        => true,
                    'commands'       => true,
                ),
            ),
            200
        );
    }

    // ===============================================
    // Metodos privados auxiliares
    // ===============================================

    /**
     * Verifica si la IA esta disponible
     *
     * @return bool
     */
    private function is_ai_available() {
        if ( ! class_exists( 'Flavor_Engine_Manager' ) ) {
            return false;
        }

        $engine_manager = Flavor_Engine_Manager::get_instance();
        $engine = $engine_manager->get_backend_engine();

        return $engine && $engine->is_configured();
    }

    /**
     * Parsea un comando en lenguaje natural
     *
     * @param string $command Comando en lenguaje natural.
     * @return array Comando parseado.
     */
    private function parse_natural_command( $command ) {
        $command_lower = strtolower( trim( $command ) );

        $parsed = array(
            'original'   => $command,
            'action'     => 'generate',
            'target'     => null,
            'modifiers'  => array(),
            'parameters' => array(),
        );

        // Detectar acciones
        $action_patterns = array(
            '/^(crear|generar|anadir|agregar|hacer|añadir)/u' => 'create',
            '/^(centrar|alinear)/u'                           => 'align',
            '/^(reducir|compactar|minimizar)/u'               => 'compact',
            '/^(expandir|ampliar|aumentar)/u'                 => 'expand',
            '/^(sugerir|recomendar|proponer)/u'               => 'suggest',
            '/^(mejorar|optimizar|arreglar)/u'                => 'improve',
            '/^(cambiar|modificar|ajustar)/u'                 => 'modify',
        );

        foreach ( $action_patterns as $pattern => $action ) {
            if ( preg_match( $pattern, $command_lower ) ) {
                $parsed['action'] = $action;
                break;
            }
        }

        // Detectar targets (tipos de elemento)
        $target_patterns = array(
            '/hero(\s+section)?/i'                       => 'hero',
            '/(grid|cuadr[ií]cula)\s*(de)?\s*(\d+)?\s*(columnas)?/i' => 'grid',
            '/columnas?\s*(\d+)?/i'                      => 'columns',
            '/galeria|gallery/i'                         => 'gallery',
            '/testimonios?|testimonials?/i'              => 'testimonials',
            '/precios?|pricing/i'                        => 'pricing',
            '/faq|preguntas/i'                           => 'faq',
            '/cta|llamada|accion/i'                      => 'cta',
            '/features?|caracter[ií]sticas?/i'           => 'features',
            '/footer|pie/i'                              => 'footer',
            '/nav(bar)?|navegaci[oó]n|men[uú]/i'         => 'navbar',
            '/contact[o]?|formulario/i'                  => 'contact',
            '/secci[oó]n|section/i'                      => 'section',
        );

        foreach ( $target_patterns as $pattern => $target ) {
            if ( preg_match( $pattern, $command_lower, $matches ) ) {
                $parsed['target'] = $target;

                // Extraer numero de columnas si existe
                if ( preg_match( '/(\d+)/', $command_lower, $number_matches ) ) {
                    $parsed['parameters']['columns'] = (int) $number_matches[1];
                }
                break;
            }
        }

        // Detectar modificadores de estilo
        $modifier_patterns = array(
            '/compacto|m[ií]nimo/i'         => 'compact',
            '/grande|amplio|espacioso/i'   => 'large',
            '/centrado|centered/i'         => 'centered',
            '/vertical(mente)?/i'          => 'vertical',
            '/horizontal(mente)?/i'        => 'horizontal',
            '/oscuro|dark/i'               => 'dark',
            '/claro|light/i'               => 'light',
            '/gradiente|gradient/i'        => 'gradient',
            '/imagen\s*(de)?\s*fondo/i'    => 'background-image',
            '/minimalista/i'               => 'minimal',
            '/moderno/i'                   => 'modern',
        );

        foreach ( $modifier_patterns as $pattern => $modifier ) {
            if ( preg_match( $pattern, $command_lower ) ) {
                $parsed['modifiers'][] = $modifier;
            }
        }

        return $parsed;
    }

    /**
     * Intenta coincidir con una plantilla predefinida
     *
     * @param array $parsed_command Comando parseado.
     * @return array|null Bloques de la plantilla o null.
     */
    private function match_template( $parsed_command ) {
        if ( empty( $parsed_command['target'] ) ) {
            return null;
        }

        $templates = $this->get_predefined_templates();
        $target = $parsed_command['target'];

        foreach ( $templates as $template ) {
            if ( isset( $template['keywords'] ) ) {
                foreach ( $template['keywords'] as $keyword ) {
                    if ( stripos( $keyword, $target ) !== false || stripos( $target, $keyword ) !== false ) {
                        return $template['blocks'];
                    }
                }
            }
        }

        return null;
    }

    /**
     * Genera layout con IA
     *
     * @param string $prompt Descripcion del layout.
     * @param array  $context Contexto adicional.
     * @return array|WP_Error
     */
    private function generate_with_ai( $prompt, $context = array() ) {
        if ( ! class_exists( 'Flavor_Engine_Manager' ) ) {
            return new WP_Error( 'ai_not_available', __( 'Motor de IA no disponible', FLAVOR_PLATFORM_TEXT_DOMAIN ) );
        }

        $engine_manager = Flavor_Engine_Manager::get_instance();

        $system_prompt = $this->get_layout_system_prompt();

        $user_prompt = $this->build_layout_prompt( $prompt, $context );

        $messages = array(
            array(
                'role'    => 'user',
                'content' => $user_prompt,
            ),
        );

        $response = $engine_manager->send_backend_message( $messages, $system_prompt );

        if ( ! $response['success'] ) {
            return new WP_Error( 'ai_error', $response['error'] ?? __( 'Error al generar layout', FLAVOR_PLATFORM_TEXT_DOMAIN ) );
        }

        // Parsear respuesta JSON
        $content = isset( $response['response'] ) ? $response['response'] : '';

        $json_match = array();
        if ( preg_match( '/\[[\s\S]*\]|\{[\s\S]*\}/', $content, $json_match ) ) {
            $decoded = json_decode( $json_match[0], true );
            if ( json_last_error() === JSON_ERROR_NONE ) {
                return isset( $decoded['blocks'] ) ? $decoded['blocks'] : $decoded;
            }
        }

        return new WP_Error( 'parse_error', __( 'Error al parsear respuesta de IA', FLAVOR_PLATFORM_TEXT_DOMAIN ) );
    }

    /**
     * Obtiene el system prompt para generacion de layouts
     *
     * @return string
     */
    private function get_layout_system_prompt() {
        return "Eres un experto en diseno web y UI/UX. Tu trabajo es generar estructuras de layout para un page builder visual.

FORMATO DE RESPUESTA:
Responde SOLO con JSON valido siguiendo esta estructura:

{
  \"blocks\": [
    {
      \"type\": \"section\",
      \"props\": { \"className\": \"...\", \"id\": \"...\" },
      \"children\": [
        {
          \"type\": \"container\",
          \"children\": [
            {\"type\": \"heading\", \"props\": {\"level\": 1, \"text\": \"...\"}},
            {\"type\": \"text\", \"props\": {\"content\": \"...\"}},
            {\"type\": \"button\", \"props\": {\"text\": \"...\", \"style\": \"primary\"}}
          ]
        }
      ]
    }
  ]
}

TIPOS DE BLOQUES DISPONIBLES:
- section, container, columns, row, grid
- heading (level 1-6), text, button, image, icon
- feature-card, testimonial-card, stat-card, pricing-card
- accordion, tabs, divider, spacer

REGLAS:
1. Usa estructura semantica correcta
2. Aplica spacing consistente usando multiplos de 8px
3. Los textos deben estar en espanol
4. Incluye clases CSS descriptivas en props.className
5. NO incluyas explicaciones, SOLO el JSON";
    }

    /**
     * Construye el prompt para generacion de layout
     *
     * @param string $prompt Descripcion del usuario.
     * @param array  $context Contexto adicional.
     * @return string
     */
    private function build_layout_prompt( $prompt, $context ) {
        $user_prompt = "Genera un layout para: {$prompt}";

        if ( ! empty( $context['industry'] ) ) {
            $user_prompt .= "\nIndustria: {$context['industry']}";
        }

        if ( ! empty( $context['style'] ) ) {
            $user_prompt .= "\nEstilo: {$context['style']}";
        }

        if ( ! empty( $context['colors'] ) ) {
            $user_prompt .= "\nColores: " . implode( ', ', (array) $context['colors'] );
        }

        return $user_prompt;
    }

    /**
     * Genera layout de fallback basado en palabras clave
     *
     * @param array $parsed_command Comando parseado.
     * @return array
     */
    private function generate_fallback_layout( $parsed_command ) {
        $target = isset( $parsed_command['target'] ) ? $parsed_command['target'] : 'section';
        $modifiers = isset( $parsed_command['modifiers'] ) ? $parsed_command['modifiers'] : array();
        $parameters = isset( $parsed_command['parameters'] ) ? $parsed_command['parameters'] : array();

        $templates = $this->get_predefined_templates();

        // Buscar plantilla por target
        foreach ( $templates as $template ) {
            if ( isset( $template['id'] ) && $template['id'] === $target ) {
                return $template['blocks'];
            }
        }

        // Plantilla generica
        return $this->generate_generic_section( $target, $modifiers, $parameters );
    }

    /**
     * Genera seccion generica
     *
     * @param string $target Tipo de seccion.
     * @param array  $modifiers Modificadores.
     * @param array  $parameters Parametros.
     * @return array
     */
    private function generate_generic_section( $target, $modifiers = array(), $parameters = array() ) {
        $section_class = 'vbp-section vbp-' . $target . '-section';

        if ( in_array( 'centered', $modifiers, true ) ) {
            $section_class .= ' vbp-centered';
        }

        if ( in_array( 'dark', $modifiers, true ) ) {
            $section_class .= ' vbp-dark';
        }

        $columns = isset( $parameters['columns'] ) ? $parameters['columns'] : 3;

        return array(
            array(
                'type'     => 'section',
                'props'    => array(
                    'className' => $section_class,
                    'id'        => $target . '-section',
                ),
                'children' => array(
                    array(
                        'type'     => 'container',
                        'children' => array(
                            array(
                                'type'  => 'heading',
                                'props' => array(
                                    'level' => 2,
                                    'text'  => ucfirst( $target ),
                                    'align' => 'center',
                                ),
                            ),
                            array(
                                'type'  => 'text',
                                'props' => array(
                                    'content' => 'Contenido de la seccion ' . $target,
                                    'align'   => 'center',
                                ),
                            ),
                        ),
                    ),
                ),
            ),
        );
    }

    /**
     * Calcula spacing recomendado para un elemento
     *
     * @param array $element Elemento.
     * @param int   $grid_base Base del grid.
     * @return array
     */
    private function calculate_element_spacing( $element, $grid_base ) {
        $element_type = isset( $element['type'] ) ? $element['type'] : 'unknown';

        // Spacing recomendado por tipo
        $type_spacing = array(
            'section'   => array( 'padding' => $grid_base * 8, 'margin' => 0 ),
            'container' => array( 'padding' => $grid_base * 4, 'margin' => 0 ),
            'heading'   => array( 'padding' => 0, 'margin' => $grid_base * 3 ),
            'text'      => array( 'padding' => 0, 'margin' => $grid_base * 2 ),
            'button'    => array( 'padding' => $grid_base * 2, 'margin' => $grid_base * 2 ),
            'image'     => array( 'padding' => 0, 'margin' => $grid_base * 2 ),
            'columns'   => array( 'padding' => 0, 'margin' => $grid_base * 4 ),
            'row'       => array( 'padding' => 0, 'margin' => $grid_base * 3 ),
        );

        if ( isset( $type_spacing[ $element_type ] ) ) {
            return $type_spacing[ $element_type ];
        }

        // Default
        return array(
            'padding' => $grid_base * 2,
            'margin'  => $grid_base * 2,
        );
    }

    /**
     * Convierte hex a HSL
     *
     * @param string $hex Color en hexadecimal.
     * @return array|null
     */
    private function hex_to_hsl( $hex ) {
        $hex = ltrim( $hex, '#' );

        if ( strlen( $hex ) === 3 ) {
            $hex = $hex[0] . $hex[0] . $hex[1] . $hex[1] . $hex[2] . $hex[2];
        }

        if ( strlen( $hex ) !== 6 ) {
            return null;
        }

        $r = hexdec( substr( $hex, 0, 2 ) ) / 255;
        $g = hexdec( substr( $hex, 2, 2 ) ) / 255;
        $b = hexdec( substr( $hex, 4, 2 ) ) / 255;

        $max = max( $r, $g, $b );
        $min = min( $r, $g, $b );
        $l = ( $max + $min ) / 2;

        if ( $max === $min ) {
            $h = 0;
            $s = 0;
        } else {
            $d = $max - $min;
            $s = $l > 0.5 ? $d / ( 2 - $max - $min ) : $d / ( $max + $min );

            switch ( $max ) {
                case $r:
                    $h = ( $g - $b ) / $d + ( $g < $b ? 6 : 0 );
                    break;
                case $g:
                    $h = ( $b - $r ) / $d + 2;
                    break;
                case $b:
                    $h = ( $r - $g ) / $d + 4;
                    break;
            }

            $h = $h / 6;
        }

        return array(
            'h' => round( $h * 360 ),
            's' => round( $s * 100 ),
            'l' => round( $l * 100 ),
        );
    }

    /**
     * Convierte HSL a hex
     *
     * @param array $hsl Color en HSL.
     * @return string
     */
    private function hsl_to_hex( $hsl ) {
        $h = $hsl['h'] / 360;
        $s = $hsl['s'] / 100;
        $l = $hsl['l'] / 100;

        if ( 0 === $s ) {
            $r = $g = $b = $l;
        } else {
            $q = $l < 0.5 ? $l * ( 1 + $s ) : $l + $s - $l * $s;
            $p = 2 * $l - $q;

            $r = $this->hue_to_rgb( $p, $q, $h + 1 / 3 );
            $g = $this->hue_to_rgb( $p, $q, $h );
            $b = $this->hue_to_rgb( $p, $q, $h - 1 / 3 );
        }

        return sprintf( '#%02x%02x%02x', round( $r * 255 ), round( $g * 255 ), round( $b * 255 ) );
    }

    /**
     * Helper para conversion HSL a RGB
     *
     * @param float $p P.
     * @param float $q Q.
     * @param float $t T.
     * @return float
     */
    private function hue_to_rgb( $p, $q, $t ) {
        if ( $t < 0 ) {
            $t += 1;
        }
        if ( $t > 1 ) {
            $t -= 1;
        }
        if ( $t < 1 / 6 ) {
            return $p + ( $q - $p ) * 6 * $t;
        }
        if ( $t < 1 / 2 ) {
            return $q;
        }
        if ( $t < 2 / 3 ) {
            return $p + ( $q - $p ) * ( 2 / 3 - $t ) * 6;
        }
        return $p;
    }

    /**
     * Genera paleta de colores
     *
     * @param array  $base_hsl Color base en HSL.
     * @param string $scheme Esquema de colores.
     * @return array
     */
    private function generate_color_palette( $base_hsl, $scheme ) {
        $palette = array();
        $h = $base_hsl['h'];
        $s = $base_hsl['s'];
        $l = $base_hsl['l'];

        switch ( $scheme ) {
            case 'complementary':
                $palette[] = $this->hsl_to_hex( array( 'h' => $h, 's' => $s, 'l' => $l ) );
                $palette[] = $this->hsl_to_hex( array( 'h' => ( $h + 180 ) % 360, 's' => $s, 'l' => $l ) );
                break;

            case 'analogous':
                $palette[] = $this->hsl_to_hex( array( 'h' => ( $h - 30 + 360 ) % 360, 's' => $s, 'l' => $l ) );
                $palette[] = $this->hsl_to_hex( array( 'h' => $h, 's' => $s, 'l' => $l ) );
                $palette[] = $this->hsl_to_hex( array( 'h' => ( $h + 30 ) % 360, 's' => $s, 'l' => $l ) );
                break;

            case 'triadic':
                $palette[] = $this->hsl_to_hex( array( 'h' => $h, 's' => $s, 'l' => $l ) );
                $palette[] = $this->hsl_to_hex( array( 'h' => ( $h + 120 ) % 360, 's' => $s, 'l' => $l ) );
                $palette[] = $this->hsl_to_hex( array( 'h' => ( $h + 240 ) % 360, 's' => $s, 'l' => $l ) );
                break;

            case 'split-complementary':
                $palette[] = $this->hsl_to_hex( array( 'h' => $h, 's' => $s, 'l' => $l ) );
                $palette[] = $this->hsl_to_hex( array( 'h' => ( $h + 150 ) % 360, 's' => $s, 'l' => $l ) );
                $palette[] = $this->hsl_to_hex( array( 'h' => ( $h + 210 ) % 360, 's' => $s, 'l' => $l ) );
                break;

            case 'monochromatic':
                $palette[] = $this->hsl_to_hex( array( 'h' => $h, 's' => $s, 'l' => max( 0, $l - 30 ) ) );
                $palette[] = $this->hsl_to_hex( array( 'h' => $h, 's' => $s, 'l' => max( 0, $l - 15 ) ) );
                $palette[] = $this->hsl_to_hex( array( 'h' => $h, 's' => $s, 'l' => $l ) );
                $palette[] = $this->hsl_to_hex( array( 'h' => $h, 's' => $s, 'l' => min( 100, $l + 15 ) ) );
                $palette[] = $this->hsl_to_hex( array( 'h' => $h, 's' => $s, 'l' => min( 100, $l + 30 ) ) );
                break;

            default:
                $palette[] = $this->hsl_to_hex( array( 'h' => $h, 's' => $s, 'l' => $l ) );
        }

        return $palette;
    }

    /**
     * Genera variaciones de color (light, dark, accent)
     *
     * @param string $base_color Color base hex.
     * @return array
     */
    private function generate_color_variations( $base_color ) {
        $hsl = $this->hex_to_hsl( $base_color );

        if ( ! $hsl ) {
            return array();
        }

        return array(
            'lightest' => $this->hsl_to_hex( array( 'h' => $hsl['h'], 's' => max( 0, $hsl['s'] - 20 ), 'l' => min( 100, $hsl['l'] + 40 ) ) ),
            'light'    => $this->hsl_to_hex( array( 'h' => $hsl['h'], 's' => max( 0, $hsl['s'] - 10 ), 'l' => min( 100, $hsl['l'] + 20 ) ) ),
            'base'     => $base_color,
            'dark'     => $this->hsl_to_hex( array( 'h' => $hsl['h'], 's' => min( 100, $hsl['s'] + 10 ), 'l' => max( 0, $hsl['l'] - 20 ) ) ),
            'darkest'  => $this->hsl_to_hex( array( 'h' => $hsl['h'], 's' => min( 100, $hsl['s'] + 20 ), 'l' => max( 0, $hsl['l'] - 40 ) ) ),
        );
    }

    /**
     * Crea una variante de un elemento
     *
     * @param array $element Elemento original.
     * @param int   $variant_index Indice de variante.
     * @return array
     */
    private function create_element_variant( $element, $variant_index ) {
        // Clonar elemento
        $variant = json_decode( wp_json_encode( $element ), true );

        $variant['id'] = isset( $element['id'] ) ? $element['id'] . '_variant_' . $variant_index : 'variant_' . $variant_index;

        // Aplicar modificaciones segun indice
        $modifications = array(
            0 => array( // Variante compacta
                'spacing' => array( 'padding' => '16px', 'margin' => '8px' ),
                'style'   => 'compact',
            ),
            1 => array( // Variante espaciosa
                'spacing' => array( 'padding' => '48px', 'margin' => '32px' ),
                'style'   => 'spacious',
            ),
            2 => array( // Variante con fondo
                'background' => 'gradient',
                'style'      => 'highlighted',
            ),
            3 => array( // Variante minimalista
                'spacing' => array( 'padding' => '24px', 'margin' => '16px' ),
                'style'   => 'minimal',
            ),
            4 => array( // Variante con bordes
                'border' => '1px solid #e5e7eb',
                'style'  => 'bordered',
            ),
        );

        $modification = isset( $modifications[ $variant_index ] ) ? $modifications[ $variant_index ] : $modifications[0];

        if ( ! isset( $variant['styles'] ) ) {
            $variant['styles'] = array();
        }

        if ( isset( $modification['spacing'] ) ) {
            $variant['styles']['spacing'] = $modification['spacing'];
        }

        if ( isset( $modification['background'] ) ) {
            $variant['styles']['background'] = $modification['background'];
        }

        if ( isset( $modification['border'] ) ) {
            $variant['styles']['border'] = $modification['border'];
        }

        $variant['variantStyle'] = isset( $modification['style'] ) ? $modification['style'] : 'default';

        return $variant;
    }

    /**
     * Verifica problemas de contraste
     *
     * @param array $element Elemento.
     * @return array
     */
    private function check_contrast( $element ) {
        $issues = array();

        $styles = isset( $element['styles'] ) ? $element['styles'] : array();
        $background = isset( $styles['background'] ) ? $styles['background'] : null;
        $color = isset( $styles['color'] ) ? $styles['color'] : null;

        if ( $background && $color ) {
            $contrast_ratio = $this->calculate_contrast_ratio( $background, $color );

            if ( $contrast_ratio < 4.5 ) {
                $issues[] = array(
                    'type'      => 'contrast',
                    'severity'  => $contrast_ratio < 3 ? 'high' : 'medium',
                    'elementId' => isset( $element['id'] ) ? $element['id'] : null,
                    'message'   => sprintf(
                        __( 'Contraste insuficiente (%.2f:1). Se recomienda al menos 4.5:1.', FLAVOR_PLATFORM_TEXT_DOMAIN ),
                        $contrast_ratio
                    ),
                    'fix'       => array(
                        'action' => 'adjustContrast',
                        'params' => array( 'targetRatio' => 4.5 ),
                    ),
                );
            }
        }

        return $issues;
    }

    /**
     * Calcula ratio de contraste entre dos colores
     *
     * @param string $color1 Color 1 hex.
     * @param string $color2 Color 2 hex.
     * @return float
     */
    private function calculate_contrast_ratio( $color1, $color2 ) {
        $l1 = $this->get_luminance( $color1 );
        $l2 = $this->get_luminance( $color2 );

        $lighter = max( $l1, $l2 );
        $darker = min( $l1, $l2 );

        return ( $lighter + 0.05 ) / ( $darker + 0.05 );
    }

    /**
     * Obtiene luminancia relativa de un color
     *
     * @param string $hex Color hex.
     * @return float
     */
    private function get_luminance( $hex ) {
        $hex = ltrim( $hex, '#' );

        if ( strlen( $hex ) === 3 ) {
            $hex = $hex[0] . $hex[0] . $hex[1] . $hex[1] . $hex[2] . $hex[2];
        }

        $r = hexdec( substr( $hex, 0, 2 ) ) / 255;
        $g = hexdec( substr( $hex, 2, 2 ) ) / 255;
        $b = hexdec( substr( $hex, 4, 2 ) ) / 255;

        $r = $r <= 0.03928 ? $r / 12.92 : pow( ( $r + 0.055 ) / 1.055, 2.4 );
        $g = $g <= 0.03928 ? $g / 12.92 : pow( ( $g + 0.055 ) / 1.055, 2.4 );
        $b = $b <= 0.03928 ? $b / 12.92 : pow( ( $b + 0.055 ) / 1.055, 2.4 );

        return 0.2126 * $r + 0.7152 * $g + 0.0722 * $b;
    }

    /**
     * Verifica consistencia de spacing
     *
     * @param array $element Elemento.
     * @return array
     */
    private function check_spacing_consistency( $element ) {
        $issues = array();

        $styles = isset( $element['styles'] ) ? $element['styles'] : array();
        $spacing = isset( $styles['spacing'] ) ? $styles['spacing'] : array();

        // Verificar si el spacing no es multiplo de 8
        $spacing_values = array_filter( array(
            isset( $spacing['padding'] ) ? $spacing['padding'] : null,
            isset( $spacing['margin'] ) ? $spacing['margin'] : null,
        ) );

        foreach ( $spacing_values as $value ) {
            if ( is_string( $value ) && preg_match( '/(\d+)px/', $value, $matches ) ) {
                $px_value = (int) $matches[1];
                if ( $px_value % 8 !== 0 && $px_value > 0 ) {
                    $issues[] = array(
                        'type'      => 'spacing',
                        'severity'  => 'low',
                        'elementId' => isset( $element['id'] ) ? $element['id'] : null,
                        'message'   => sprintf(
                            __( 'Spacing de %dpx no es multiplo de 8. Considera usar %dpx o %dpx.', FLAVOR_PLATFORM_TEXT_DOMAIN ),
                            $px_value,
                            floor( $px_value / 8 ) * 8,
                            ceil( $px_value / 8 ) * 8
                        ),
                        'fix'       => array(
                            'action' => 'snapToGrid',
                            'params' => array( 'value' => ceil( $px_value / 8 ) * 8 ),
                        ),
                    );
                }
            }
        }

        return $issues;
    }

    /**
     * Obtiene sugerencias de mejora
     *
     * @param array $element Elemento.
     * @return array
     */
    private function get_improvement_suggestions( $element ) {
        $suggestions = array();
        $element_type = isset( $element['type'] ) ? $element['type'] : 'unknown';

        // Sugerencias basadas en tipo
        switch ( $element_type ) {
            case 'heading':
                $data = isset( $element['data'] ) ? $element['data'] : array();
                $text = isset( $data['text'] ) ? $data['text'] : '';

                if ( strlen( $text ) > 60 ) {
                    $suggestions[] = array(
                        'type'      => 'content',
                        'elementId' => isset( $element['id'] ) ? $element['id'] : null,
                        'message'   => __( 'El titulo es muy largo. Considera acortarlo a menos de 60 caracteres.', FLAVOR_PLATFORM_TEXT_DOMAIN ),
                        'action'    => 'shortenText',
                    );
                }
                break;

            case 'button':
                $data = isset( $element['data'] ) ? $element['data'] : array();
                $text = isset( $data['text'] ) ? $data['text'] : '';

                if ( strlen( $text ) < 3 ) {
                    $suggestions[] = array(
                        'type'      => 'content',
                        'elementId' => isset( $element['id'] ) ? $element['id'] : null,
                        'message'   => __( 'El texto del boton es muy corto. Usa verbos de accion claros.', FLAVOR_PLATFORM_TEXT_DOMAIN ),
                        'action'    => 'improveButtonText',
                    );
                }
                break;

            case 'section':
                $children = isset( $element['children'] ) ? $element['children'] : array();

                if ( count( $children ) > 10 ) {
                    $suggestions[] = array(
                        'type'      => 'structure',
                        'elementId' => isset( $element['id'] ) ? $element['id'] : null,
                        'message'   => __( 'La seccion tiene muchos elementos. Considera dividirla en secciones mas pequenas.', FLAVOR_PLATFORM_TEXT_DOMAIN ),
                        'action'    => 'splitSection',
                    );
                }
                break;
        }

        return $suggestions;
    }

    /**
     * Calcula puntuacion de diseno
     *
     * @param array $elements Elementos.
     * @param array $issues Problemas encontrados.
     * @return int Puntuacion 0-100.
     */
    private function calculate_design_score( $elements, $issues ) {
        $base_score = 100;

        foreach ( $issues as $issue ) {
            $severity = isset( $issue['severity'] ) ? $issue['severity'] : 'low';

            switch ( $severity ) {
                case 'high':
                    $base_score -= 15;
                    break;
                case 'medium':
                    $base_score -= 8;
                    break;
                case 'low':
                    $base_score -= 3;
                    break;
            }
        }

        return max( 0, $base_score );
    }

    /**
     * Ejecuta comando parseado
     *
     * @param array $parsed Comando parseado.
     * @param array $selected_ids IDs seleccionados.
     * @param array $page_context Contexto de pagina.
     * @return array|WP_Error
     */
    private function execute_parsed_command( $parsed, $selected_ids, $page_context ) {
        $action = isset( $parsed['action'] ) ? $parsed['action'] : 'generate';
        $target = isset( $parsed['target'] ) ? $parsed['target'] : null;
        $modifiers = isset( $parsed['modifiers'] ) ? $parsed['modifiers'] : array();

        switch ( $action ) {
            case 'create':
                $blocks = $this->generate_fallback_layout( $parsed );
                return array(
                    'action' => 'addBlocks',
                    'blocks' => $blocks,
                );

            case 'align':
                $alignment = in_array( 'vertical', $modifiers, true ) ? 'vertical' : 'horizontal';
                return array(
                    'action'    => 'alignElements',
                    'ids'       => $selected_ids,
                    'alignment' => $alignment,
                    'centered'  => in_array( 'centered', $modifiers, true ),
                );

            case 'compact':
                return array(
                    'action' => 'applySpacing',
                    'ids'    => $selected_ids,
                    'preset' => 'compact',
                );

            case 'expand':
                return array(
                    'action' => 'applySpacing',
                    'ids'    => $selected_ids,
                    'preset' => 'spacious',
                );

            case 'suggest':
                if ( $target === 'colors' || stripos( $parsed['original'], 'color' ) !== false ) {
                    return array(
                        'action' => 'openColorSuggestions',
                    );
                }
                return array(
                    'action' => 'showSuggestions',
                    'target' => $target,
                );

            case 'improve':
                return array(
                    'action' => 'analyzeAndFix',
                    'ids'    => $selected_ids,
                );

            default:
                // Si hay target, intentar generar layout
                if ( $target ) {
                    $blocks = $this->generate_fallback_layout( $parsed );
                    return array(
                        'action' => 'addBlocks',
                        'blocks' => $blocks,
                    );
                }

                return new WP_Error(
                    'unknown_command',
                    __( 'Comando no reconocido', FLAVOR_PLATFORM_TEXT_DOMAIN ),
                    array( 'status' => 400 )
                );
        }
    }

    /**
     * Obtiene plantillas predefinidas
     *
     * @return array
     */
    private function get_predefined_templates() {
        return array(
            // Hero sections
            array(
                'id'       => 'hero',
                'name'     => __( 'Hero Section', FLAVOR_PLATFORM_TEXT_DOMAIN ),
                'category' => 'hero',
                'keywords' => array( 'hero', 'cabecera', 'header', 'principal' ),
                'blocks'   => array(
                    array(
                        'type'     => 'section',
                        'props'    => array(
                            'className' => 'vbp-hero-section',
                            'id'        => 'hero',
                        ),
                        'children' => array(
                            array(
                                'type'     => 'container',
                                'children' => array(
                                    array(
                                        'type'  => 'heading',
                                        'props' => array(
                                            'level' => 1,
                                            'text'  => 'Titulo Principal Impactante',
                                            'align' => 'center',
                                        ),
                                    ),
                                    array(
                                        'type'  => 'text',
                                        'props' => array(
                                            'content' => 'Subtitulo que complementa y expande la propuesta de valor principal.',
                                            'align'   => 'center',
                                        ),
                                    ),
                                    array(
                                        'type'     => 'container',
                                        'props'    => array(
                                            'className' => 'vbp-hero-buttons',
                                        ),
                                        'children' => array(
                                            array(
                                                'type'  => 'button',
                                                'props' => array(
                                                    'text'  => 'Empezar Ahora',
                                                    'style' => 'primary',
                                                    'size'  => 'large',
                                                ),
                                            ),
                                            array(
                                                'type'  => 'button',
                                                'props' => array(
                                                    'text'  => 'Saber Mas',
                                                    'style' => 'secondary',
                                                    'size'  => 'large',
                                                ),
                                            ),
                                        ),
                                    ),
                                ),
                            ),
                        ),
                    ),
                ),
            ),
            // Hero con imagen de fondo
            array(
                'id'       => 'hero-background',
                'name'     => __( 'Hero con Fondo', FLAVOR_PLATFORM_TEXT_DOMAIN ),
                'category' => 'hero',
                'keywords' => array( 'hero fondo', 'hero imagen', 'background' ),
                'blocks'   => array(
                    array(
                        'type'     => 'section',
                        'props'    => array(
                            'className' => 'vbp-hero-section vbp-hero-background',
                            'id'        => 'hero',
                        ),
                        'styles'   => array(
                            'background' => array(
                                'type'    => 'gradient',
                                'from'    => '#1e3a5f',
                                'to'      => '#2d5016',
                                'overlay' => true,
                            ),
                            'minHeight'  => '80vh',
                        ),
                        'children' => array(
                            array(
                                'type'     => 'container',
                                'children' => array(
                                    array(
                                        'type'  => 'heading',
                                        'props' => array(
                                            'level' => 1,
                                            'text'  => 'Titulo Principal',
                                            'align' => 'center',
                                            'color' => '#ffffff',
                                        ),
                                    ),
                                    array(
                                        'type'  => 'text',
                                        'props' => array(
                                            'content' => 'Descripcion breve de la propuesta de valor.',
                                            'align'   => 'center',
                                            'color'   => '#ffffff',
                                        ),
                                    ),
                                    array(
                                        'type'  => 'button',
                                        'props' => array(
                                            'text'  => 'Comenzar',
                                            'style' => 'primary',
                                            'size'  => 'large',
                                        ),
                                    ),
                                ),
                            ),
                        ),
                    ),
                ),
            ),
            // Grid de columnas
            array(
                'id'       => 'grid',
                'name'     => __( 'Grid de Columnas', FLAVOR_PLATFORM_TEXT_DOMAIN ),
                'category' => 'layout',
                'keywords' => array( 'grid', 'columnas', 'columns', 'cuadricula' ),
                'blocks'   => array(
                    array(
                        'type'     => 'section',
                        'props'    => array(
                            'className' => 'vbp-grid-section',
                        ),
                        'children' => array(
                            array(
                                'type'     => 'container',
                                'children' => array(
                                    array(
                                        'type'  => 'columns',
                                        'props' => array(
                                            'columns' => 3,
                                            'gap'     => '24px',
                                        ),
                                        'children' => array(
                                            array(
                                                'type'     => 'container',
                                                'props'    => array( 'className' => 'vbp-column' ),
                                                'children' => array(
                                                    array(
                                                        'type'  => 'heading',
                                                        'props' => array( 'level' => 3, 'text' => 'Columna 1' ),
                                                    ),
                                                    array(
                                                        'type'  => 'text',
                                                        'props' => array( 'content' => 'Contenido de la columna 1.' ),
                                                    ),
                                                ),
                                            ),
                                            array(
                                                'type'     => 'container',
                                                'props'    => array( 'className' => 'vbp-column' ),
                                                'children' => array(
                                                    array(
                                                        'type'  => 'heading',
                                                        'props' => array( 'level' => 3, 'text' => 'Columna 2' ),
                                                    ),
                                                    array(
                                                        'type'  => 'text',
                                                        'props' => array( 'content' => 'Contenido de la columna 2.' ),
                                                    ),
                                                ),
                                            ),
                                            array(
                                                'type'     => 'container',
                                                'props'    => array( 'className' => 'vbp-column' ),
                                                'children' => array(
                                                    array(
                                                        'type'  => 'heading',
                                                        'props' => array( 'level' => 3, 'text' => 'Columna 3' ),
                                                    ),
                                                    array(
                                                        'type'  => 'text',
                                                        'props' => array( 'content' => 'Contenido de la columna 3.' ),
                                                    ),
                                                ),
                                            ),
                                        ),
                                    ),
                                ),
                            ),
                        ),
                    ),
                ),
            ),
            // Features
            array(
                'id'       => 'features',
                'name'     => __( 'Caracteristicas', FLAVOR_PLATFORM_TEXT_DOMAIN ),
                'category' => 'features',
                'keywords' => array( 'features', 'caracteristicas', 'beneficios' ),
                'blocks'   => array(
                    array(
                        'type'     => 'section',
                        'props'    => array(
                            'className' => 'vbp-features-section',
                            'id'        => 'features',
                        ),
                        'children' => array(
                            array(
                                'type'     => 'container',
                                'children' => array(
                                    array(
                                        'type'  => 'heading',
                                        'props' => array(
                                            'level' => 2,
                                            'text'  => 'Nuestras Caracteristicas',
                                            'align' => 'center',
                                        ),
                                    ),
                                    array(
                                        'type'  => 'columns',
                                        'props' => array( 'columns' => 3, 'gap' => '32px' ),
                                        'children' => array(
                                            array(
                                                'type'  => 'feature-card',
                                                'props' => array(
                                                    'icon'        => 'lightning',
                                                    'title'       => 'Rapido',
                                                    'description' => 'Implementacion en minutos.',
                                                ),
                                            ),
                                            array(
                                                'type'  => 'feature-card',
                                                'props' => array(
                                                    'icon'        => 'shield',
                                                    'title'       => 'Seguro',
                                                    'description' => 'Proteccion de nivel empresarial.',
                                                ),
                                            ),
                                            array(
                                                'type'  => 'feature-card',
                                                'props' => array(
                                                    'icon'        => 'heart',
                                                    'title'       => 'Facil',
                                                    'description' => 'Interfaz intuitiva y amigable.',
                                                ),
                                            ),
                                        ),
                                    ),
                                ),
                            ),
                        ),
                    ),
                ),
            ),
            // Testimonials
            array(
                'id'       => 'testimonials',
                'name'     => __( 'Testimonios', FLAVOR_PLATFORM_TEXT_DOMAIN ),
                'category' => 'testimonials',
                'keywords' => array( 'testimonios', 'testimonials', 'opiniones', 'reviews' ),
                'blocks'   => array(
                    array(
                        'type'     => 'section',
                        'props'    => array(
                            'className' => 'vbp-testimonials-section',
                            'id'        => 'testimonials',
                        ),
                        'children' => array(
                            array(
                                'type'     => 'container',
                                'children' => array(
                                    array(
                                        'type'  => 'heading',
                                        'props' => array(
                                            'level' => 2,
                                            'text'  => 'Lo que dicen nuestros clientes',
                                            'align' => 'center',
                                        ),
                                    ),
                                    array(
                                        'type'  => 'columns',
                                        'props' => array( 'columns' => 2, 'gap' => '32px' ),
                                        'children' => array(
                                            array(
                                                'type'  => 'testimonial-card',
                                                'props' => array(
                                                    'quote'  => 'Excelente servicio, superaron mis expectativas.',
                                                    'author' => 'Maria Garcia',
                                                    'role'   => 'CEO, Empresa S.L.',
                                                ),
                                            ),
                                            array(
                                                'type'  => 'testimonial-card',
                                                'props' => array(
                                                    'quote'  => 'La mejor decision que hemos tomado.',
                                                    'author' => 'Carlos Lopez',
                                                    'role'   => 'Director, Startup Inc.',
                                                ),
                                            ),
                                        ),
                                    ),
                                ),
                            ),
                        ),
                    ),
                ),
            ),
            // Pricing
            array(
                'id'       => 'pricing',
                'name'     => __( 'Tabla de Precios', FLAVOR_PLATFORM_TEXT_DOMAIN ),
                'category' => 'pricing',
                'keywords' => array( 'pricing', 'precios', 'planes', 'tarifas' ),
                'blocks'   => array(
                    array(
                        'type'     => 'section',
                        'props'    => array(
                            'className' => 'vbp-pricing-section',
                            'id'        => 'pricing',
                        ),
                        'children' => array(
                            array(
                                'type'     => 'container',
                                'children' => array(
                                    array(
                                        'type'  => 'heading',
                                        'props' => array(
                                            'level' => 2,
                                            'text'  => 'Planes y Precios',
                                            'align' => 'center',
                                        ),
                                    ),
                                    array(
                                        'type'  => 'columns',
                                        'props' => array( 'columns' => 3, 'gap' => '24px' ),
                                        'children' => array(
                                            array(
                                                'type'  => 'pricing-card',
                                                'props' => array(
                                                    'name'     => 'Basico',
                                                    'price'    => '9',
                                                    'period'   => 'mes',
                                                    'features' => array( '1 Usuario', '5GB Almacenamiento', 'Soporte email' ),
                                                    'cta'      => 'Empezar',
                                                ),
                                            ),
                                            array(
                                                'type'  => 'pricing-card',
                                                'props' => array(
                                                    'name'        => 'Pro',
                                                    'price'       => '29',
                                                    'period'      => 'mes',
                                                    'features'    => array( '5 Usuarios', '50GB Almacenamiento', 'Soporte prioritario' ),
                                                    'cta'         => 'Elegir Pro',
                                                    'highlighted' => true,
                                                ),
                                            ),
                                            array(
                                                'type'  => 'pricing-card',
                                                'props' => array(
                                                    'name'     => 'Enterprise',
                                                    'price'    => '99',
                                                    'period'   => 'mes',
                                                    'features' => array( 'Usuarios ilimitados', 'Almacenamiento ilimitado', 'Soporte 24/7' ),
                                                    'cta'      => 'Contactar',
                                                ),
                                            ),
                                        ),
                                    ),
                                ),
                            ),
                        ),
                    ),
                ),
            ),
            // CTA
            array(
                'id'       => 'cta',
                'name'     => __( 'Call to Action', FLAVOR_PLATFORM_TEXT_DOMAIN ),
                'category' => 'cta',
                'keywords' => array( 'cta', 'call to action', 'llamada accion' ),
                'blocks'   => array(
                    array(
                        'type'     => 'section',
                        'props'    => array(
                            'className' => 'vbp-cta-section',
                            'id'        => 'cta',
                        ),
                        'styles'   => array(
                            'background' => '#1e3a5f',
                        ),
                        'children' => array(
                            array(
                                'type'     => 'container',
                                'children' => array(
                                    array(
                                        'type'  => 'heading',
                                        'props' => array(
                                            'level' => 2,
                                            'text'  => 'Listo para empezar?',
                                            'align' => 'center',
                                            'color' => '#ffffff',
                                        ),
                                    ),
                                    array(
                                        'type'  => 'text',
                                        'props' => array(
                                            'content' => 'Unete a miles de usuarios satisfechos.',
                                            'align'   => 'center',
                                            'color'   => '#ffffff',
                                        ),
                                    ),
                                    array(
                                        'type'  => 'button',
                                        'props' => array(
                                            'text'  => 'Empezar Gratis',
                                            'style' => 'primary',
                                            'size'  => 'large',
                                        ),
                                    ),
                                ),
                            ),
                        ),
                    ),
                ),
            ),
            // FAQ
            array(
                'id'       => 'faq',
                'name'     => __( 'Preguntas Frecuentes', FLAVOR_PLATFORM_TEXT_DOMAIN ),
                'category' => 'faq',
                'keywords' => array( 'faq', 'preguntas', 'frecuentes' ),
                'blocks'   => array(
                    array(
                        'type'     => 'section',
                        'props'    => array(
                            'className' => 'vbp-faq-section',
                            'id'        => 'faq',
                        ),
                        'children' => array(
                            array(
                                'type'     => 'container',
                                'children' => array(
                                    array(
                                        'type'  => 'heading',
                                        'props' => array(
                                            'level' => 2,
                                            'text'  => 'Preguntas Frecuentes',
                                            'align' => 'center',
                                        ),
                                    ),
                                    array(
                                        'type'  => 'accordion',
                                        'props' => array(
                                            'items' => array(
                                                array(
                                                    'question' => 'Como puedo empezar?',
                                                    'answer'   => 'Es muy sencillo. Solo registrate y tendras acceso inmediato.',
                                                ),
                                                array(
                                                    'question' => 'Hay periodo de prueba?',
                                                    'answer'   => 'Si, ofrecemos 14 dias de prueba gratuita sin compromiso.',
                                                ),
                                                array(
                                                    'question' => 'Puedo cancelar en cualquier momento?',
                                                    'answer'   => 'Absolutamente. Sin preguntas ni penalizaciones.',
                                                ),
                                            ),
                                        ),
                                    ),
                                ),
                            ),
                        ),
                    ),
                ),
            ),
            // Navbar
            array(
                'id'       => 'navbar',
                'name'     => __( 'Barra de Navegacion', FLAVOR_PLATFORM_TEXT_DOMAIN ),
                'category' => 'navigation',
                'keywords' => array( 'navbar', 'navegacion', 'menu', 'header' ),
                'blocks'   => array(
                    array(
                        'type'     => 'section',
                        'props'    => array(
                            'className' => 'vbp-navbar-section',
                            'id'        => 'navbar',
                        ),
                        'children' => array(
                            array(
                                'type'     => 'container',
                                'props'    => array(
                                    'className' => 'vbp-navbar-container',
                                ),
                                'children' => array(
                                    array(
                                        'type'  => 'text',
                                        'props' => array(
                                            'content'   => 'Logo',
                                            'className' => 'vbp-logo',
                                        ),
                                    ),
                                    array(
                                        'type'  => 'nav',
                                        'props' => array(
                                            'items' => array(
                                                array( 'text' => 'Inicio', 'url' => '#' ),
                                                array( 'text' => 'Servicios', 'url' => '#services' ),
                                                array( 'text' => 'Precios', 'url' => '#pricing' ),
                                                array( 'text' => 'Contacto', 'url' => '#contact' ),
                                            ),
                                        ),
                                    ),
                                    array(
                                        'type'  => 'button',
                                        'props' => array(
                                            'text'  => 'Acceder',
                                            'style' => 'primary',
                                            'size'  => 'small',
                                        ),
                                    ),
                                ),
                            ),
                        ),
                    ),
                ),
            ),
            // Footer
            array(
                'id'       => 'footer',
                'name'     => __( 'Pie de Pagina', FLAVOR_PLATFORM_TEXT_DOMAIN ),
                'category' => 'footer',
                'keywords' => array( 'footer', 'pie', 'pagina' ),
                'blocks'   => array(
                    array(
                        'type'     => 'section',
                        'props'    => array(
                            'className' => 'vbp-footer-section',
                            'id'        => 'footer',
                        ),
                        'styles'   => array(
                            'background' => '#1f2937',
                        ),
                        'children' => array(
                            array(
                                'type'     => 'container',
                                'children' => array(
                                    array(
                                        'type'  => 'columns',
                                        'props' => array( 'columns' => 4, 'gap' => '32px' ),
                                        'children' => array(
                                            array(
                                                'type'     => 'container',
                                                'children' => array(
                                                    array(
                                                        'type'  => 'heading',
                                                        'props' => array(
                                                            'level' => 4,
                                                            'text'  => 'Empresa',
                                                            'color' => '#ffffff',
                                                        ),
                                                    ),
                                                    array(
                                                        'type'  => 'text',
                                                        'props' => array(
                                                            'content' => 'Descripcion breve de la empresa.',
                                                            'color'   => '#9ca3af',
                                                        ),
                                                    ),
                                                ),
                                            ),
                                            array(
                                                'type'     => 'container',
                                                'children' => array(
                                                    array(
                                                        'type'  => 'heading',
                                                        'props' => array(
                                                            'level' => 4,
                                                            'text'  => 'Enlaces',
                                                            'color' => '#ffffff',
                                                        ),
                                                    ),
                                                    array(
                                                        'type'  => 'nav',
                                                        'props' => array(
                                                            'direction' => 'vertical',
                                                            'items'     => array(
                                                                array( 'text' => 'Inicio', 'url' => '#' ),
                                                                array( 'text' => 'Servicios', 'url' => '#' ),
                                                                array( 'text' => 'Blog', 'url' => '#' ),
                                                            ),
                                                        ),
                                                    ),
                                                ),
                                            ),
                                            array(
                                                'type'     => 'container',
                                                'children' => array(
                                                    array(
                                                        'type'  => 'heading',
                                                        'props' => array(
                                                            'level' => 4,
                                                            'text'  => 'Legal',
                                                            'color' => '#ffffff',
                                                        ),
                                                    ),
                                                    array(
                                                        'type'  => 'nav',
                                                        'props' => array(
                                                            'direction' => 'vertical',
                                                            'items'     => array(
                                                                array( 'text' => 'Privacidad', 'url' => '#' ),
                                                                array( 'text' => 'Terminos', 'url' => '#' ),
                                                                array( 'text' => 'Cookies', 'url' => '#' ),
                                                            ),
                                                        ),
                                                    ),
                                                ),
                                            ),
                                            array(
                                                'type'     => 'container',
                                                'children' => array(
                                                    array(
                                                        'type'  => 'heading',
                                                        'props' => array(
                                                            'level' => 4,
                                                            'text'  => 'Contacto',
                                                            'color' => '#ffffff',
                                                        ),
                                                    ),
                                                    array(
                                                        'type'  => 'text',
                                                        'props' => array(
                                                            'content' => 'info@ejemplo.com',
                                                            'color'   => '#9ca3af',
                                                        ),
                                                    ),
                                                ),
                                            ),
                                        ),
                                    ),
                                ),
                            ),
                        ),
                    ),
                ),
            ),
        );
    }
}

// Inicializar
Flavor_VBP_AI_Layout::get_instance();

<?php
/**
 * Visual Builder Pro - AI Prompts Library
 *
 * Biblioteca de prompts optimizados para generación de contenido.
 *
 * @package FlavorPlatform
 * @subpackage Visual_Builder_Pro
 * @since 2.1.0
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Biblioteca de prompts para AI Content
 */
class Flavor_VBP_AI_Prompts {

    /**
     * Instancia singleton
     *
     * @var Flavor_VBP_AI_Prompts|null
     */
    private static $instancia = null;

    /**
     * Obtiene la instancia singleton
     *
     * @return Flavor_VBP_AI_Prompts
     */
    public static function get_instance() {
        if ( null === self::$instancia ) {
            self::$instancia = new self();
        }
        return self::$instancia;
    }

    /**
     * Obtiene el system prompt base para generación de contenido
     *
     * @return string
     */
    public function get_system_prompt() {
        return "Eres un experto en copywriting y marketing digital. Tu objetivo es generar contenido persuasivo, claro y relevante para páginas web.

REGLAS IMPORTANTES:
- Responde SOLO con el contenido solicitado, sin explicaciones adicionales
- Usa un tono profesional pero accesible
- Sé conciso y directo
- Adapta el estilo al contexto proporcionado
- No uses comillas alrededor del texto generado
- Si se pide formato JSON, responde solo con JSON válido";
    }

    /**
     * Obtiene prompt para títulos de hero
     *
     * @param array $context Contexto con industry, tone, etc.
     * @return string
     */
    public function get_hero_title_prompt( $context = array() ) {
        $industry = $context['industry'] ?? 'general';
        $tone = $context['tone'] ?? 'profesional';
        $extra_context = $context['context'] ?? '';

        $prompt = "Genera un título impactante para una sección hero de una página web.

Industria/Sector: {$industry}
Tono: {$tone}
Requisitos:
- Máximo 8 palabras
- Que capture la atención inmediatamente
- Orientado a beneficios o resultado
- Sin puntuación final";

        if ( ! empty( $extra_context ) ) {
            $prompt .= "\n\nContexto adicional: {$extra_context}";
        }

        $prompt .= "\n\nGenera SOLO el título, sin explicaciones.";

        return $prompt;
    }

    /**
     * Obtiene prompt para subtítulos de hero
     *
     * @param array $context Contexto.
     * @return string
     */
    public function get_hero_subtitle_prompt( $context = array() ) {
        $industry = $context['industry'] ?? 'general';
        $tone = $context['tone'] ?? 'profesional';
        $title = $context['title'] ?? '';

        $prompt = "Genera un subtítulo complementario para una sección hero.

Industria/Sector: {$industry}
Tono: {$tone}";

        if ( ! empty( $title ) ) {
            $prompt .= "\nTítulo principal: {$title}";
        }

        $prompt .= "

Requisitos:
- 1-2 líneas máximo (15-25 palabras)
- Complementa y expande el título
- Incluye propuesta de valor clara
- Genera interés para seguir leyendo

Genera SOLO el subtítulo, sin explicaciones.";

        return $prompt;
    }

    /**
     * Obtiene prompt para botones CTA
     *
     * @param array $context Contexto.
     * @return string
     */
    public function get_cta_button_prompt( $context = array() ) {
        $goal = $context['goal'] ?? 'conversión';
        $tone = $context['tone'] ?? 'profesional';
        $count = $context['count'] ?? 3;

        $prompt = "Genera {$count} opciones de texto para un botón CTA (Call-to-Action).

Objetivo: {$goal}
Tono: {$tone}

Requisitos para cada opción:
- Máximo 4 palabras
- Verbos de acción al inicio
- Crear urgencia o deseo
- Sin puntuación

Responde con un JSON array simple:
[\"opción 1\", \"opción 2\", \"opción 3\"]";

        return $prompt;
    }

    /**
     * Obtiene prompt para features/características
     *
     * @param array $context Contexto.
     * @return string
     */
    public function get_feature_prompt( $context = array() ) {
        $product = $context['product'] ?? 'producto/servicio';
        $benefit = $context['benefit'] ?? '';
        $industry = $context['industry'] ?? 'general';

        $prompt = "Genera título y descripción para una característica de producto.

Producto/Servicio: {$product}
Industria: {$industry}";

        if ( ! empty( $benefit ) ) {
            $prompt .= "\nBeneficio clave a destacar: {$benefit}";
        }

        $prompt .= "

Requisitos:
- Título: máximo 5 palabras, orientado a beneficio
- Descripción: máximo 20 palabras, clara y convincente

Responde con JSON:
{\"title\": \"...\", \"description\": \"...\"}";

        return $prompt;
    }

    /**
     * Obtiene prompt para lista de features
     *
     * @param array $context Contexto.
     * @return string
     */
    public function get_features_list_prompt( $context = array() ) {
        $product = $context['product'] ?? 'producto/servicio';
        $count = $context['count'] ?? 3;
        $industry = $context['industry'] ?? 'general';

        $prompt = "Genera {$count} características para destacar un producto/servicio.

Producto/Servicio: {$product}
Industria: {$industry}

Para cada característica incluye:
- icon: emoji representativo
- title: máximo 4 palabras
- description: máximo 15 palabras

Responde con JSON array:
[{\"icon\": \"emoji\", \"title\": \"...\", \"description\": \"...\"}, ...]";

        return $prompt;
    }

    /**
     * Obtiene prompt para testimonios
     *
     * @param array $context Contexto.
     * @return string
     */
    public function get_testimonial_prompt( $context = array() ) {
        $product = $context['product'] ?? 'producto/servicio';
        $industry = $context['industry'] ?? 'general';
        $tone = $context['tone'] ?? 'auténtico';

        $prompt = "Genera un testimonio ficticio pero realista para marketing.

Producto/Servicio: {$product}
Industria: {$industry}
Tono: {$tone}

Requisitos:
- Testimonio de 2-3 frases
- Que suene natural y creíble
- Incluye resultado o beneficio específico
- Nombre y cargo/profesión del testimonio

Responde con JSON:
{\"quote\": \"...\", \"author\": \"...\", \"role\": \"...\"}";

        return $prompt;
    }

    /**
     * Obtiene prompt para mejorar contenido existente
     *
     * @param string $content Contenido original.
     * @param string $action Acción a realizar.
     * @param array  $context Contexto adicional.
     * @return string
     */
    public function get_improve_prompt( $content, $action, $context = array() ) {
        $tone = $context['tone'] ?? 'profesional';

        $actions_map = array(
            'rewrite'   => "Reescribe el siguiente texto para que sea más persuasivo y atractivo, manteniendo el mensaje central.",
            'shorten'   => "Acorta el siguiente texto manteniendo el mensaje esencial. Hazlo más conciso y directo.",
            'expand'    => "Expande el siguiente texto añadiendo más detalles y contexto relevante, sin ser redundante.",
            'formal'    => "Reescribe el siguiente texto con un tono más formal y profesional.",
            'casual'    => "Reescribe el siguiente texto con un tono más casual y cercano.",
            'persuasive' => "Reescribe el siguiente texto para que sea más persuasivo y orientado a la conversión.",
        );

        $instruction = $actions_map[ $action ] ?? $actions_map['rewrite'];

        $prompt = "{$instruction}

Tono deseado: {$tone}

Texto original:
\"{$content}\"

IMPORTANTE: Responde SOLO con el texto mejorado, sin explicaciones ni comillas.";

        return $prompt;
    }

    /**
     * Obtiene prompt para traducción
     *
     * @param string $content Contenido a traducir.
     * @param string $target_language Idioma destino.
     * @return string
     */
    public function get_translate_prompt( $content, $target_language ) {
        $languages_map = array(
            'en' => 'inglés',
            'es' => 'español',
            'fr' => 'francés',
            'de' => 'alemán',
            'it' => 'italiano',
            'pt' => 'portugués',
            'ca' => 'catalán',
            'eu' => 'euskera',
            'gl' => 'gallego',
        );

        $language_name = $languages_map[ $target_language ] ?? $target_language;

        $prompt = "Traduce el siguiente texto al {$language_name}, manteniendo el tono y estilo original.

Texto:
\"{$content}\"

IMPORTANTE: Responde SOLO con la traducción, sin explicaciones ni comillas.";

        return $prompt;
    }

    /**
     * Obtiene prompt para secciones de estadísticas
     *
     * @param array $context Contexto.
     * @return string
     */
    public function get_stats_prompt( $context = array() ) {
        $industry = $context['industry'] ?? 'general';
        $count = $context['count'] ?? 3;

        $prompt = "Genera {$count} estadísticas impactantes para mostrar en una sección de métricas.

Industria: {$industry}

Para cada estadística incluye:
- number: el número/porcentaje (ej: \"10K+\", \"99%\", \"24/7\")
- label: etiqueta corta (2-3 palabras)

Las estadísticas deben ser creíbles y relevantes para el sector.

Responde con JSON array:
[{\"number\": \"...\", \"label\": \"...\"}, ...]";

        return $prompt;
    }

    /**
     * Obtiene prompt para FAQ
     *
     * @param array $context Contexto.
     * @return string
     */
    public function get_faq_prompt( $context = array() ) {
        $product = $context['product'] ?? 'producto/servicio';
        $count = $context['count'] ?? 3;
        $industry = $context['industry'] ?? 'general';

        $prompt = "Genera {$count} preguntas frecuentes con sus respuestas para la sección FAQ.

Producto/Servicio: {$product}
Industria: {$industry}

Requisitos:
- Preguntas que realmente haría un cliente potencial
- Respuestas claras, concisas y útiles (2-3 frases)
- Mezcla de preguntas sobre el producto, proceso y soporte

Responde con JSON array:
[{\"question\": \"...\", \"answer\": \"...\"}, ...]";

        return $prompt;
    }

    /**
     * Obtiene prompt para descripción de producto/servicio
     *
     * @param array $context Contexto.
     * @return string
     */
    public function get_description_prompt( $context = array() ) {
        $product = $context['product'] ?? '';
        $length = $context['length'] ?? 'medium';
        $tone = $context['tone'] ?? 'profesional';

        $lengths_map = array(
            'short'  => '30-50 palabras',
            'medium' => '80-120 palabras',
            'long'   => '150-200 palabras',
        );

        $word_count = $lengths_map[ $length ] ?? $lengths_map['medium'];

        $prompt = "Genera una descripción para un producto/servicio.

Producto/Servicio: {$product}
Tono: {$tone}
Extensión: {$word_count}

Requisitos:
- Destaca beneficios principales
- Incluye llamada a la acción sutil
- Lenguaje persuasivo pero no agresivo

Responde SOLO con la descripción, sin explicaciones.";

        return $prompt;
    }

    /**
     * Obtiene todos los tipos de contenido disponibles
     *
     * @return array
     */
    public function get_content_types() {
        return array(
            'hero_title'    => array(
                'id'          => 'hero_title',
                'name'        => __( 'Título Hero', FLAVOR_PLATFORM_TEXT_DOMAIN ),
                'description' => __( 'Título principal impactante', FLAVOR_PLATFORM_TEXT_DOMAIN ),
            ),
            'hero_subtitle' => array(
                'id'          => 'hero_subtitle',
                'name'        => __( 'Subtítulo Hero', FLAVOR_PLATFORM_TEXT_DOMAIN ),
                'description' => __( 'Subtítulo complementario', FLAVOR_PLATFORM_TEXT_DOMAIN ),
            ),
            'cta_button'    => array(
                'id'          => 'cta_button',
                'name'        => __( 'Botón CTA', FLAVOR_PLATFORM_TEXT_DOMAIN ),
                'description' => __( 'Texto para botones de acción', FLAVOR_PLATFORM_TEXT_DOMAIN ),
            ),
            'feature'       => array(
                'id'          => 'feature',
                'name'        => __( 'Característica', FLAVOR_PLATFORM_TEXT_DOMAIN ),
                'description' => __( 'Título y descripción de feature', FLAVOR_PLATFORM_TEXT_DOMAIN ),
            ),
            'features_list' => array(
                'id'          => 'features_list',
                'name'        => __( 'Lista de Features', FLAVOR_PLATFORM_TEXT_DOMAIN ),
                'description' => __( 'Múltiples características', FLAVOR_PLATFORM_TEXT_DOMAIN ),
            ),
            'testimonial'   => array(
                'id'          => 'testimonial',
                'name'        => __( 'Testimonio', FLAVOR_PLATFORM_TEXT_DOMAIN ),
                'description' => __( 'Testimonio de cliente', FLAVOR_PLATFORM_TEXT_DOMAIN ),
            ),
            'stats'         => array(
                'id'          => 'stats',
                'name'        => __( 'Estadísticas', FLAVOR_PLATFORM_TEXT_DOMAIN ),
                'description' => __( 'Métricas destacadas', FLAVOR_PLATFORM_TEXT_DOMAIN ),
            ),
            'faq'           => array(
                'id'          => 'faq',
                'name'        => __( 'FAQ', FLAVOR_PLATFORM_TEXT_DOMAIN ),
                'description' => __( 'Preguntas frecuentes', FLAVOR_PLATFORM_TEXT_DOMAIN ),
            ),
            'description'   => array(
                'id'          => 'description',
                'name'        => __( 'Descripción', FLAVOR_PLATFORM_TEXT_DOMAIN ),
                'description' => __( 'Descripción de producto/servicio', FLAVOR_PLATFORM_TEXT_DOMAIN ),
            ),
        );
    }

    /**
     * Obtiene las industrias/sectores disponibles
     *
     * @return array
     */
    public function get_industries() {
        return array(
            'general'       => __( 'General', FLAVOR_PLATFORM_TEXT_DOMAIN ),
            'tech'          => __( 'Tecnología / SaaS', FLAVOR_PLATFORM_TEXT_DOMAIN ),
            'ecommerce'     => __( 'E-commerce', FLAVOR_PLATFORM_TEXT_DOMAIN ),
            'health'        => __( 'Salud y Bienestar', FLAVOR_PLATFORM_TEXT_DOMAIN ),
            'finance'       => __( 'Finanzas', FLAVOR_PLATFORM_TEXT_DOMAIN ),
            'education'     => __( 'Educación', FLAVOR_PLATFORM_TEXT_DOMAIN ),
            'realestate'    => __( 'Inmobiliaria', FLAVOR_PLATFORM_TEXT_DOMAIN ),
            'food'          => __( 'Alimentación / Restauración', FLAVOR_PLATFORM_TEXT_DOMAIN ),
            'travel'        => __( 'Turismo / Viajes', FLAVOR_PLATFORM_TEXT_DOMAIN ),
            'creative'      => __( 'Diseño / Creatividad', FLAVOR_PLATFORM_TEXT_DOMAIN ),
            'legal'         => __( 'Legal / Consultoría', FLAVOR_PLATFORM_TEXT_DOMAIN ),
            'nonprofit'     => __( 'ONG / Sin ánimo de lucro', FLAVOR_PLATFORM_TEXT_DOMAIN ),
            'community'     => __( 'Comunidad / Cooperativas', FLAVOR_PLATFORM_TEXT_DOMAIN ),
            'sustainability' => __( 'Sostenibilidad / Ecología', FLAVOR_PLATFORM_TEXT_DOMAIN ),
        );
    }

    /**
     * Obtiene los tonos disponibles
     *
     * @return array
     */
    public function get_tones() {
        return array(
            'profesional'  => __( 'Profesional', FLAVOR_PLATFORM_TEXT_DOMAIN ),
            'casual'       => __( 'Casual / Cercano', FLAVOR_PLATFORM_TEXT_DOMAIN ),
            'formal'       => __( 'Formal', FLAVOR_PLATFORM_TEXT_DOMAIN ),
            'entusiasta'   => __( 'Entusiasta / Energético', FLAVOR_PLATFORM_TEXT_DOMAIN ),
            'minimalista'  => __( 'Minimalista / Conciso', FLAVOR_PLATFORM_TEXT_DOMAIN ),
            'inspirador'   => __( 'Inspirador / Motivacional', FLAVOR_PLATFORM_TEXT_DOMAIN ),
            'tecnico'      => __( 'Técnico / Experto', FLAVOR_PLATFORM_TEXT_DOMAIN ),
            'empatico'     => __( 'Empático / Humano', FLAVOR_PLATFORM_TEXT_DOMAIN ),
        );
    }

    /**
     * Obtiene los tipos de página disponibles para generación completa
     *
     * @return array
     */
    public function get_page_types() {
        return array(
            'landing'       => array(
                'id'               => 'landing',
                'name'             => __( 'Landing Page', FLAVOR_PLATFORM_TEXT_DOMAIN ),
                'description'      => __( 'Página de aterrizaje para captar conversiones', FLAVOR_PLATFORM_TEXT_DOMAIN ),
                'default_sections' => array( 'hero', 'features', 'benefits', 'testimonials', 'cta' ),
            ),
            'about'         => array(
                'id'               => 'about',
                'name'             => __( 'Sobre Nosotros', FLAVOR_PLATFORM_TEXT_DOMAIN ),
                'description'      => __( 'Página para contar la historia de tu empresa', FLAVOR_PLATFORM_TEXT_DOMAIN ),
                'default_sections' => array( 'hero', 'story', 'values', 'team', 'cta' ),
            ),
            'services'      => array(
                'id'               => 'services',
                'name'             => __( 'Servicios', FLAVOR_PLATFORM_TEXT_DOMAIN ),
                'description'      => __( 'Muestra tus servicios de forma atractiva', FLAVOR_PLATFORM_TEXT_DOMAIN ),
                'default_sections' => array( 'hero', 'services_grid', 'process', 'pricing', 'cta' ),
            ),
            'contact'       => array(
                'id'               => 'contact',
                'name'             => __( 'Contacto', FLAVOR_PLATFORM_TEXT_DOMAIN ),
                'description'      => __( 'Página de contacto con formulario', FLAVOR_PLATFORM_TEXT_DOMAIN ),
                'default_sections' => array( 'hero', 'contact_info', 'form', 'map', 'faq' ),
            ),
            'portfolio'     => array(
                'id'               => 'portfolio',
                'name'             => __( 'Portfolio', FLAVOR_PLATFORM_TEXT_DOMAIN ),
                'description'      => __( 'Muestra tus mejores trabajos', FLAVOR_PLATFORM_TEXT_DOMAIN ),
                'default_sections' => array( 'hero', 'work_grid', 'clients', 'testimonials', 'cta' ),
            ),
            'product'       => array(
                'id'               => 'product',
                'name'             => __( 'Producto', FLAVOR_PLATFORM_TEXT_DOMAIN ),
                'description'      => __( 'Landing para un producto específico', FLAVOR_PLATFORM_TEXT_DOMAIN ),
                'default_sections' => array( 'hero', 'features', 'demo', 'pricing', 'faq', 'cta' ),
            ),
            'event'         => array(
                'id'               => 'event',
                'name'             => __( 'Evento', FLAVOR_PLATFORM_TEXT_DOMAIN ),
                'description'      => __( 'Página para promocionar un evento', FLAVOR_PLATFORM_TEXT_DOMAIN ),
                'default_sections' => array( 'hero', 'details', 'speakers', 'schedule', 'registration', 'sponsors' ),
            ),
            'cooperative'   => array(
                'id'               => 'cooperative',
                'name'             => __( 'Cooperativa', FLAVOR_PLATFORM_TEXT_DOMAIN ),
                'description'      => __( 'Página para cooperativas y grupos de consumo', FLAVOR_PLATFORM_TEXT_DOMAIN ),
                'default_sections' => array( 'hero', 'values', 'how_it_works', 'benefits', 'join', 'faq' ),
            ),
            'crowdfunding'  => array(
                'id'               => 'crowdfunding',
                'name'             => __( 'Crowdfunding', FLAVOR_PLATFORM_TEXT_DOMAIN ),
                'description'      => __( 'Página para campañas de financiación', FLAVOR_PLATFORM_TEXT_DOMAIN ),
                'default_sections' => array( 'hero', 'project', 'rewards', 'team', 'updates', 'faq' ),
            ),
            'association'   => array(
                'id'               => 'association',
                'name'             => __( 'Asociación / ONG', FLAVOR_PLATFORM_TEXT_DOMAIN ),
                'description'      => __( 'Página para asociaciones y ONGs', FLAVOR_PLATFORM_TEXT_DOMAIN ),
                'default_sections' => array( 'hero', 'mission', 'impact', 'projects', 'team', 'donate', 'cta' ),
            ),
        );
    }

    /**
     * Obtiene los tipos de sección disponibles
     *
     * @return array
     */
    public function get_section_types() {
        return array(
            'hero'         => array(
                'id'          => 'hero',
                'name'        => __( 'Hero / Cabecera', FLAVOR_PLATFORM_TEXT_DOMAIN ),
                'description' => __( 'Sección principal con título, subtítulo y CTA', FLAVOR_PLATFORM_TEXT_DOMAIN ),
            ),
            'features'     => array(
                'id'          => 'features',
                'name'        => __( 'Características', FLAVOR_PLATFORM_TEXT_DOMAIN ),
                'description' => __( 'Grid de características con iconos', FLAVOR_PLATFORM_TEXT_DOMAIN ),
            ),
            'benefits'     => array(
                'id'          => 'benefits',
                'name'        => __( 'Beneficios', FLAVOR_PLATFORM_TEXT_DOMAIN ),
                'description' => __( 'Lista de beneficios con iconos', FLAVOR_PLATFORM_TEXT_DOMAIN ),
            ),
            'testimonials' => array(
                'id'          => 'testimonials',
                'name'        => __( 'Testimonios', FLAVOR_PLATFORM_TEXT_DOMAIN ),
                'description' => __( 'Testimonios de clientes', FLAVOR_PLATFORM_TEXT_DOMAIN ),
            ),
            'cta'          => array(
                'id'          => 'cta',
                'name'        => __( 'Call to Action', FLAVOR_PLATFORM_TEXT_DOMAIN ),
                'description' => __( 'Sección de llamada a la acción', FLAVOR_PLATFORM_TEXT_DOMAIN ),
            ),
            'story'        => array(
                'id'          => 'story',
                'name'        => __( 'Historia', FLAVOR_PLATFORM_TEXT_DOMAIN ),
                'description' => __( 'Cuenta la historia de tu empresa', FLAVOR_PLATFORM_TEXT_DOMAIN ),
            ),
            'values'       => array(
                'id'          => 'values',
                'name'        => __( 'Valores', FLAVOR_PLATFORM_TEXT_DOMAIN ),
                'description' => __( 'Valores y principios', FLAVOR_PLATFORM_TEXT_DOMAIN ),
            ),
            'team'         => array(
                'id'          => 'team',
                'name'        => __( 'Equipo', FLAVOR_PLATFORM_TEXT_DOMAIN ),
                'description' => __( 'Presenta a tu equipo', FLAVOR_PLATFORM_TEXT_DOMAIN ),
            ),
            'services_grid'=> array(
                'id'          => 'services_grid',
                'name'        => __( 'Grid de Servicios', FLAVOR_PLATFORM_TEXT_DOMAIN ),
                'description' => __( 'Muestra tus servicios en grid', FLAVOR_PLATFORM_TEXT_DOMAIN ),
            ),
            'process'      => array(
                'id'          => 'process',
                'name'        => __( 'Proceso', FLAVOR_PLATFORM_TEXT_DOMAIN ),
                'description' => __( 'Pasos de tu proceso de trabajo', FLAVOR_PLATFORM_TEXT_DOMAIN ),
            ),
            'pricing'      => array(
                'id'          => 'pricing',
                'name'        => __( 'Precios', FLAVOR_PLATFORM_TEXT_DOMAIN ),
                'description' => __( 'Tabla de precios', FLAVOR_PLATFORM_TEXT_DOMAIN ),
            ),
            'faq'          => array(
                'id'          => 'faq',
                'name'        => __( 'FAQ', FLAVOR_PLATFORM_TEXT_DOMAIN ),
                'description' => __( 'Preguntas frecuentes', FLAVOR_PLATFORM_TEXT_DOMAIN ),
            ),
            'contact_info' => array(
                'id'          => 'contact_info',
                'name'        => __( 'Información de Contacto', FLAVOR_PLATFORM_TEXT_DOMAIN ),
                'description' => __( 'Datos de contacto', FLAVOR_PLATFORM_TEXT_DOMAIN ),
            ),
            'form'         => array(
                'id'          => 'form',
                'name'        => __( 'Formulario', FLAVOR_PLATFORM_TEXT_DOMAIN ),
                'description' => __( 'Formulario de contacto', FLAVOR_PLATFORM_TEXT_DOMAIN ),
            ),
            'map'          => array(
                'id'          => 'map',
                'name'        => __( 'Mapa', FLAVOR_PLATFORM_TEXT_DOMAIN ),
                'description' => __( 'Mapa de ubicación', FLAVOR_PLATFORM_TEXT_DOMAIN ),
            ),
            'work_grid'    => array(
                'id'          => 'work_grid',
                'name'        => __( 'Portfolio Grid', FLAVOR_PLATFORM_TEXT_DOMAIN ),
                'description' => __( 'Grid de trabajos realizados', FLAVOR_PLATFORM_TEXT_DOMAIN ),
            ),
            'clients'      => array(
                'id'          => 'clients',
                'name'        => __( 'Clientes', FLAVOR_PLATFORM_TEXT_DOMAIN ),
                'description' => __( 'Logos de clientes', FLAVOR_PLATFORM_TEXT_DOMAIN ),
            ),
            'stats'        => array(
                'id'          => 'stats',
                'name'        => __( 'Estadísticas', FLAVOR_PLATFORM_TEXT_DOMAIN ),
                'description' => __( 'Métricas destacadas', FLAVOR_PLATFORM_TEXT_DOMAIN ),
            ),
            'demo'         => array(
                'id'          => 'demo',
                'name'        => __( 'Demo / Video', FLAVOR_PLATFORM_TEXT_DOMAIN ),
                'description' => __( 'Demostración del producto', FLAVOR_PLATFORM_TEXT_DOMAIN ),
            ),
            'how_it_works' => array(
                'id'          => 'how_it_works',
                'name'        => __( 'Cómo Funciona', FLAVOR_PLATFORM_TEXT_DOMAIN ),
                'description' => __( 'Explica el proceso paso a paso', FLAVOR_PLATFORM_TEXT_DOMAIN ),
            ),
            'join'         => array(
                'id'          => 'join',
                'name'        => __( 'Únete', FLAVOR_PLATFORM_TEXT_DOMAIN ),
                'description' => __( 'Formulario de unión', FLAVOR_PLATFORM_TEXT_DOMAIN ),
            ),
            'mission'      => array(
                'id'          => 'mission',
                'name'        => __( 'Misión', FLAVOR_PLATFORM_TEXT_DOMAIN ),
                'description' => __( 'Declaración de misión', FLAVOR_PLATFORM_TEXT_DOMAIN ),
            ),
            'impact'       => array(
                'id'          => 'impact',
                'name'        => __( 'Impacto', FLAVOR_PLATFORM_TEXT_DOMAIN ),
                'description' => __( 'Impacto y resultados', FLAVOR_PLATFORM_TEXT_DOMAIN ),
            ),
            'projects'     => array(
                'id'          => 'projects',
                'name'        => __( 'Proyectos', FLAVOR_PLATFORM_TEXT_DOMAIN ),
                'description' => __( 'Proyectos destacados', FLAVOR_PLATFORM_TEXT_DOMAIN ),
            ),
            'donate'       => array(
                'id'          => 'donate',
                'name'        => __( 'Donar', FLAVOR_PLATFORM_TEXT_DOMAIN ),
                'description' => __( 'Sección de donaciones', FLAVOR_PLATFORM_TEXT_DOMAIN ),
            ),
            'schedule'     => array(
                'id'          => 'schedule',
                'name'        => __( 'Agenda', FLAVOR_PLATFORM_TEXT_DOMAIN ),
                'description' => __( 'Programa del evento', FLAVOR_PLATFORM_TEXT_DOMAIN ),
            ),
            'speakers'     => array(
                'id'          => 'speakers',
                'name'        => __( 'Ponentes', FLAVOR_PLATFORM_TEXT_DOMAIN ),
                'description' => __( 'Presentadores y ponentes', FLAVOR_PLATFORM_TEXT_DOMAIN ),
            ),
            'registration' => array(
                'id'          => 'registration',
                'name'        => __( 'Registro', FLAVOR_PLATFORM_TEXT_DOMAIN ),
                'description' => __( 'Formulario de registro', FLAVOR_PLATFORM_TEXT_DOMAIN ),
            ),
            'sponsors'     => array(
                'id'          => 'sponsors',
                'name'        => __( 'Patrocinadores', FLAVOR_PLATFORM_TEXT_DOMAIN ),
                'description' => __( 'Logos de patrocinadores', FLAVOR_PLATFORM_TEXT_DOMAIN ),
            ),
            'rewards'      => array(
                'id'          => 'rewards',
                'name'        => __( 'Recompensas', FLAVOR_PLATFORM_TEXT_DOMAIN ),
                'description' => __( 'Niveles de recompensa para crowdfunding', FLAVOR_PLATFORM_TEXT_DOMAIN ),
            ),
            'updates'      => array(
                'id'          => 'updates',
                'name'        => __( 'Actualizaciones', FLAVOR_PLATFORM_TEXT_DOMAIN ),
                'description' => __( 'Noticias y actualizaciones', FLAVOR_PLATFORM_TEXT_DOMAIN ),
            ),
        );
    }

    /**
     * Obtiene las secciones predeterminadas para un tipo de página
     *
     * @param string $page_type Tipo de página.
     * @return array
     */
    public function get_default_sections_for_page_type( $page_type ) {
        $page_types = $this->get_page_types();
        if ( isset( $page_types[ $page_type ]['default_sections'] ) ) {
            return $page_types[ $page_type ]['default_sections'];
        }
        return array( 'hero', 'features', 'cta' );
    }

    /**
     * Obtiene el prompt para generar una página completa
     *
     * @param string $page_type Tipo de página.
     * @param array  $sections Secciones a incluir.
     * @param array  $context Contexto.
     * @return string
     */
    public function get_full_page_prompt( $page_type, $sections, $context = array() ) {
        $industry = $context['industry'] ?? 'general';
        $tone = $context['tone'] ?? 'profesional';
        $company_name = $context['company_name'] ?? 'Mi Empresa';
        $description = $context['description'] ?? '';
        $target_audience = $context['target_audience'] ?? '';

        $page_types = $this->get_page_types();
        $page_type_name = $page_types[ $page_type ]['name'] ?? $page_type;

        $sections_list = implode( ', ', $sections );
        $sections_descriptions = $this->get_sections_structure_description( $sections );

        $prompt = "Genera el contenido completo para una página web tipo \"{$page_type_name}\".

CONTEXTO:
- Empresa/Proyecto: {$company_name}
- Industria/Sector: {$industry}
- Tono de comunicación: {$tone}";

        if ( ! empty( $description ) ) {
            $prompt .= "\n- Descripción: {$description}";
        }

        if ( ! empty( $target_audience ) ) {
            $prompt .= "\n- Público objetivo: {$target_audience}";
        }

        $prompt .= "

SECCIONES A GENERAR: {$sections_list}

{$sections_descriptions}

FORMATO DE RESPUESTA:
Responde con un JSON válido siguiendo esta estructura exacta:

{
  \"title\": \"Título de la página\",
  \"meta_description\": \"Meta descripción SEO (max 160 caracteres)\",
  \"blocks\": [
    {
      \"type\": \"section\",
      \"props\": {
        \"className\": \"vbp-hero-section\",
        \"id\": \"hero\"
      },
      \"children\": [
        {
          \"type\": \"container\",
          \"children\": [
            {\"type\": \"heading\", \"props\": {\"level\": 1, \"text\": \"Título aquí\", \"align\": \"center\"}},
            {\"type\": \"text\", \"props\": {\"content\": \"Subtítulo aquí\", \"align\": \"center\"}},
            {\"type\": \"button\", \"props\": {\"text\": \"CTA aquí\", \"url\": \"#\", \"style\": \"primary\"}}
          ]
        }
      ]
    }
  ]
}

REGLAS:
1. Cada sección solicitada debe ser un bloque \"section\" con su ID correspondiente
2. Usa bloques tipo: heading (nivel 1-6), text, button, image, columns, feature-card, testimonial-card, stat-card, accordion, icon
3. El contenido debe ser persuasivo, específico y relevante para el sector
4. No uses texto genérico como \"Lorem ipsum\"
5. Los textos deben estar en español
6. Genera contenido realista pero ficticio si no tienes información específica
7. RESPONDE SOLO CON EL JSON, sin explicaciones adicionales";

        return $prompt;
    }

    /**
     * Genera descripción de la estructura de secciones
     *
     * @param array $sections Lista de secciones.
     * @return string
     */
    private function get_sections_structure_description( $sections ) {
        $section_specs = array(
            'hero'         => 'Hero: Título principal H1, subtítulo, 1-2 botones CTA',
            'features'     => 'Features: Título H2, grid de 3-4 características con icono, título y descripción corta',
            'benefits'     => 'Benefits: Título H2, lista de 4-6 beneficios con iconos',
            'testimonials' => 'Testimonials: Título H2, 2-3 testimonios con cita, autor y cargo',
            'cta'          => 'CTA: Título, texto breve, botón de acción',
            'story'        => 'Story: Título H2, 2-3 párrafos contando la historia',
            'values'       => 'Values: Título H2, grid de 3-4 valores con icono y descripción',
            'team'         => 'Team: Título H2, grid de 3-4 miembros con nombre, cargo y bio corta',
            'services_grid'=> 'Services: Título H2, grid de 3-6 servicios con icono, título y descripción',
            'process'      => 'Process: Título H2, 3-5 pasos numerados con título y descripción',
            'pricing'      => 'Pricing: Título H2, 2-3 planes con nombre, precio, features y botón',
            'faq'          => 'FAQ: Título H2, 4-6 preguntas con respuestas (acordeón)',
            'contact_info' => 'Contact Info: Dirección, teléfono, email, horarios',
            'form'         => 'Form: Indicador de formulario de contacto',
            'map'          => 'Map: Indicador de mapa de ubicación',
            'stats'        => 'Stats: 3-4 estadísticas con número grande y etiqueta',
            'clients'      => 'Clients: Título H2, indicador de logos de clientes',
            'work_grid'    => 'Portfolio: Título H2, grid de 4-6 proyectos con imagen y título',
            'demo'         => 'Demo: Título H2, texto y área para video/demo',
            'how_it_works' => 'How It Works: Título H2, 3-4 pasos con icono y descripción',
            'join'         => 'Join: Título H2, beneficios de unirse, formulario',
            'mission'      => 'Mission: Declaración de misión clara y potente',
            'impact'       => 'Impact: Título H2, métricas de impacto con estadísticas',
            'projects'     => 'Projects: Título H2, grid de 3-4 proyectos con descripción',
            'donate'       => 'Donate: Título, mensaje emotivo, opciones de donación, botón',
            'schedule'     => 'Schedule: Título H2, agenda del evento con horarios',
            'speakers'     => 'Speakers: Título H2, grid de ponentes con foto, nombre y bio',
            'registration' => 'Registration: Título H2, beneficios, formulario de registro',
            'sponsors'     => 'Sponsors: Título H2, indicador de logos de patrocinadores',
            'rewards'      => 'Rewards: Título H2, 3-4 niveles de recompensa con precio y descripción',
            'updates'      => 'Updates: Título H2, lista de últimas actualizaciones',
        );

        $descriptions = array();
        foreach ( $sections as $section ) {
            if ( isset( $section_specs[ $section ] ) ) {
                $descriptions[] = "- {$section_specs[ $section ]}";
            }
        }

        return "ESTRUCTURA DE CADA SECCIÓN:\n" . implode( "\n", $descriptions );
    }
}

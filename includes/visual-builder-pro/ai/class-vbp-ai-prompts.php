<?php
/**
 * Visual Builder Pro - AI Prompts Library
 *
 * Biblioteca de prompts optimizados para generación de contenido.
 *
 * @package FlavorChatIA
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
                'name'        => __( 'Título Hero', 'flavor-chat-ia' ),
                'description' => __( 'Título principal impactante', 'flavor-chat-ia' ),
            ),
            'hero_subtitle' => array(
                'id'          => 'hero_subtitle',
                'name'        => __( 'Subtítulo Hero', 'flavor-chat-ia' ),
                'description' => __( 'Subtítulo complementario', 'flavor-chat-ia' ),
            ),
            'cta_button'    => array(
                'id'          => 'cta_button',
                'name'        => __( 'Botón CTA', 'flavor-chat-ia' ),
                'description' => __( 'Texto para botones de acción', 'flavor-chat-ia' ),
            ),
            'feature'       => array(
                'id'          => 'feature',
                'name'        => __( 'Característica', 'flavor-chat-ia' ),
                'description' => __( 'Título y descripción de feature', 'flavor-chat-ia' ),
            ),
            'features_list' => array(
                'id'          => 'features_list',
                'name'        => __( 'Lista de Features', 'flavor-chat-ia' ),
                'description' => __( 'Múltiples características', 'flavor-chat-ia' ),
            ),
            'testimonial'   => array(
                'id'          => 'testimonial',
                'name'        => __( 'Testimonio', 'flavor-chat-ia' ),
                'description' => __( 'Testimonio de cliente', 'flavor-chat-ia' ),
            ),
            'stats'         => array(
                'id'          => 'stats',
                'name'        => __( 'Estadísticas', 'flavor-chat-ia' ),
                'description' => __( 'Métricas destacadas', 'flavor-chat-ia' ),
            ),
            'faq'           => array(
                'id'          => 'faq',
                'name'        => __( 'FAQ', 'flavor-chat-ia' ),
                'description' => __( 'Preguntas frecuentes', 'flavor-chat-ia' ),
            ),
            'description'   => array(
                'id'          => 'description',
                'name'        => __( 'Descripción', 'flavor-chat-ia' ),
                'description' => __( 'Descripción de producto/servicio', 'flavor-chat-ia' ),
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
            'general'       => __( 'General', 'flavor-chat-ia' ),
            'tech'          => __( 'Tecnología / SaaS', 'flavor-chat-ia' ),
            'ecommerce'     => __( 'E-commerce', 'flavor-chat-ia' ),
            'health'        => __( 'Salud y Bienestar', 'flavor-chat-ia' ),
            'finance'       => __( 'Finanzas', 'flavor-chat-ia' ),
            'education'     => __( 'Educación', 'flavor-chat-ia' ),
            'realestate'    => __( 'Inmobiliaria', 'flavor-chat-ia' ),
            'food'          => __( 'Alimentación / Restauración', 'flavor-chat-ia' ),
            'travel'        => __( 'Turismo / Viajes', 'flavor-chat-ia' ),
            'creative'      => __( 'Diseño / Creatividad', 'flavor-chat-ia' ),
            'legal'         => __( 'Legal / Consultoría', 'flavor-chat-ia' ),
            'nonprofit'     => __( 'ONG / Sin ánimo de lucro', 'flavor-chat-ia' ),
            'community'     => __( 'Comunidad / Cooperativas', 'flavor-chat-ia' ),
            'sustainability' => __( 'Sostenibilidad / Ecología', 'flavor-chat-ia' ),
        );
    }

    /**
     * Obtiene los tonos disponibles
     *
     * @return array
     */
    public function get_tones() {
        return array(
            'profesional'  => __( 'Profesional', 'flavor-chat-ia' ),
            'casual'       => __( 'Casual / Cercano', 'flavor-chat-ia' ),
            'formal'       => __( 'Formal', 'flavor-chat-ia' ),
            'entusiasta'   => __( 'Entusiasta / Energético', 'flavor-chat-ia' ),
            'minimalista'  => __( 'Minimalista / Conciso', 'flavor-chat-ia' ),
            'inspirador'   => __( 'Inspirador / Motivacional', 'flavor-chat-ia' ),
            'tecnico'      => __( 'Técnico / Experto', 'flavor-chat-ia' ),
            'empatico'     => __( 'Empático / Humano', 'flavor-chat-ia' ),
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
                'name'             => __( 'Landing Page', 'flavor-chat-ia' ),
                'description'      => __( 'Página de aterrizaje para captar conversiones', 'flavor-chat-ia' ),
                'default_sections' => array( 'hero', 'features', 'benefits', 'testimonials', 'cta' ),
            ),
            'about'         => array(
                'id'               => 'about',
                'name'             => __( 'Sobre Nosotros', 'flavor-chat-ia' ),
                'description'      => __( 'Página para contar la historia de tu empresa', 'flavor-chat-ia' ),
                'default_sections' => array( 'hero', 'story', 'values', 'team', 'cta' ),
            ),
            'services'      => array(
                'id'               => 'services',
                'name'             => __( 'Servicios', 'flavor-chat-ia' ),
                'description'      => __( 'Muestra tus servicios de forma atractiva', 'flavor-chat-ia' ),
                'default_sections' => array( 'hero', 'services_grid', 'process', 'pricing', 'cta' ),
            ),
            'contact'       => array(
                'id'               => 'contact',
                'name'             => __( 'Contacto', 'flavor-chat-ia' ),
                'description'      => __( 'Página de contacto con formulario', 'flavor-chat-ia' ),
                'default_sections' => array( 'hero', 'contact_info', 'form', 'map', 'faq' ),
            ),
            'portfolio'     => array(
                'id'               => 'portfolio',
                'name'             => __( 'Portfolio', 'flavor-chat-ia' ),
                'description'      => __( 'Muestra tus mejores trabajos', 'flavor-chat-ia' ),
                'default_sections' => array( 'hero', 'work_grid', 'clients', 'testimonials', 'cta' ),
            ),
            'product'       => array(
                'id'               => 'product',
                'name'             => __( 'Producto', 'flavor-chat-ia' ),
                'description'      => __( 'Landing para un producto específico', 'flavor-chat-ia' ),
                'default_sections' => array( 'hero', 'features', 'demo', 'pricing', 'faq', 'cta' ),
            ),
            'event'         => array(
                'id'               => 'event',
                'name'             => __( 'Evento', 'flavor-chat-ia' ),
                'description'      => __( 'Página para promocionar un evento', 'flavor-chat-ia' ),
                'default_sections' => array( 'hero', 'details', 'speakers', 'schedule', 'registration', 'sponsors' ),
            ),
            'cooperative'   => array(
                'id'               => 'cooperative',
                'name'             => __( 'Cooperativa', 'flavor-chat-ia' ),
                'description'      => __( 'Página para cooperativas y grupos de consumo', 'flavor-chat-ia' ),
                'default_sections' => array( 'hero', 'values', 'how_it_works', 'benefits', 'join', 'faq' ),
            ),
            'crowdfunding'  => array(
                'id'               => 'crowdfunding',
                'name'             => __( 'Crowdfunding', 'flavor-chat-ia' ),
                'description'      => __( 'Página para campañas de financiación', 'flavor-chat-ia' ),
                'default_sections' => array( 'hero', 'project', 'rewards', 'team', 'updates', 'faq' ),
            ),
            'association'   => array(
                'id'               => 'association',
                'name'             => __( 'Asociación / ONG', 'flavor-chat-ia' ),
                'description'      => __( 'Página para asociaciones y ONGs', 'flavor-chat-ia' ),
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
                'name'        => __( 'Hero / Cabecera', 'flavor-chat-ia' ),
                'description' => __( 'Sección principal con título, subtítulo y CTA', 'flavor-chat-ia' ),
            ),
            'features'     => array(
                'id'          => 'features',
                'name'        => __( 'Características', 'flavor-chat-ia' ),
                'description' => __( 'Grid de características con iconos', 'flavor-chat-ia' ),
            ),
            'benefits'     => array(
                'id'          => 'benefits',
                'name'        => __( 'Beneficios', 'flavor-chat-ia' ),
                'description' => __( 'Lista de beneficios con iconos', 'flavor-chat-ia' ),
            ),
            'testimonials' => array(
                'id'          => 'testimonials',
                'name'        => __( 'Testimonios', 'flavor-chat-ia' ),
                'description' => __( 'Testimonios de clientes', 'flavor-chat-ia' ),
            ),
            'cta'          => array(
                'id'          => 'cta',
                'name'        => __( 'Call to Action', 'flavor-chat-ia' ),
                'description' => __( 'Sección de llamada a la acción', 'flavor-chat-ia' ),
            ),
            'story'        => array(
                'id'          => 'story',
                'name'        => __( 'Historia', 'flavor-chat-ia' ),
                'description' => __( 'Cuenta la historia de tu empresa', 'flavor-chat-ia' ),
            ),
            'values'       => array(
                'id'          => 'values',
                'name'        => __( 'Valores', 'flavor-chat-ia' ),
                'description' => __( 'Valores y principios', 'flavor-chat-ia' ),
            ),
            'team'         => array(
                'id'          => 'team',
                'name'        => __( 'Equipo', 'flavor-chat-ia' ),
                'description' => __( 'Presenta a tu equipo', 'flavor-chat-ia' ),
            ),
            'services_grid'=> array(
                'id'          => 'services_grid',
                'name'        => __( 'Grid de Servicios', 'flavor-chat-ia' ),
                'description' => __( 'Muestra tus servicios en grid', 'flavor-chat-ia' ),
            ),
            'process'      => array(
                'id'          => 'process',
                'name'        => __( 'Proceso', 'flavor-chat-ia' ),
                'description' => __( 'Pasos de tu proceso de trabajo', 'flavor-chat-ia' ),
            ),
            'pricing'      => array(
                'id'          => 'pricing',
                'name'        => __( 'Precios', 'flavor-chat-ia' ),
                'description' => __( 'Tabla de precios', 'flavor-chat-ia' ),
            ),
            'faq'          => array(
                'id'          => 'faq',
                'name'        => __( 'FAQ', 'flavor-chat-ia' ),
                'description' => __( 'Preguntas frecuentes', 'flavor-chat-ia' ),
            ),
            'contact_info' => array(
                'id'          => 'contact_info',
                'name'        => __( 'Información de Contacto', 'flavor-chat-ia' ),
                'description' => __( 'Datos de contacto', 'flavor-chat-ia' ),
            ),
            'form'         => array(
                'id'          => 'form',
                'name'        => __( 'Formulario', 'flavor-chat-ia' ),
                'description' => __( 'Formulario de contacto', 'flavor-chat-ia' ),
            ),
            'map'          => array(
                'id'          => 'map',
                'name'        => __( 'Mapa', 'flavor-chat-ia' ),
                'description' => __( 'Mapa de ubicación', 'flavor-chat-ia' ),
            ),
            'work_grid'    => array(
                'id'          => 'work_grid',
                'name'        => __( 'Portfolio Grid', 'flavor-chat-ia' ),
                'description' => __( 'Grid de trabajos realizados', 'flavor-chat-ia' ),
            ),
            'clients'      => array(
                'id'          => 'clients',
                'name'        => __( 'Clientes', 'flavor-chat-ia' ),
                'description' => __( 'Logos de clientes', 'flavor-chat-ia' ),
            ),
            'stats'        => array(
                'id'          => 'stats',
                'name'        => __( 'Estadísticas', 'flavor-chat-ia' ),
                'description' => __( 'Métricas destacadas', 'flavor-chat-ia' ),
            ),
            'demo'         => array(
                'id'          => 'demo',
                'name'        => __( 'Demo / Video', 'flavor-chat-ia' ),
                'description' => __( 'Demostración del producto', 'flavor-chat-ia' ),
            ),
            'how_it_works' => array(
                'id'          => 'how_it_works',
                'name'        => __( 'Cómo Funciona', 'flavor-chat-ia' ),
                'description' => __( 'Explica el proceso paso a paso', 'flavor-chat-ia' ),
            ),
            'join'         => array(
                'id'          => 'join',
                'name'        => __( 'Únete', 'flavor-chat-ia' ),
                'description' => __( 'Formulario de unión', 'flavor-chat-ia' ),
            ),
            'mission'      => array(
                'id'          => 'mission',
                'name'        => __( 'Misión', 'flavor-chat-ia' ),
                'description' => __( 'Declaración de misión', 'flavor-chat-ia' ),
            ),
            'impact'       => array(
                'id'          => 'impact',
                'name'        => __( 'Impacto', 'flavor-chat-ia' ),
                'description' => __( 'Impacto y resultados', 'flavor-chat-ia' ),
            ),
            'projects'     => array(
                'id'          => 'projects',
                'name'        => __( 'Proyectos', 'flavor-chat-ia' ),
                'description' => __( 'Proyectos destacados', 'flavor-chat-ia' ),
            ),
            'donate'       => array(
                'id'          => 'donate',
                'name'        => __( 'Donar', 'flavor-chat-ia' ),
                'description' => __( 'Sección de donaciones', 'flavor-chat-ia' ),
            ),
            'schedule'     => array(
                'id'          => 'schedule',
                'name'        => __( 'Agenda', 'flavor-chat-ia' ),
                'description' => __( 'Programa del evento', 'flavor-chat-ia' ),
            ),
            'speakers'     => array(
                'id'          => 'speakers',
                'name'        => __( 'Ponentes', 'flavor-chat-ia' ),
                'description' => __( 'Presentadores y ponentes', 'flavor-chat-ia' ),
            ),
            'registration' => array(
                'id'          => 'registration',
                'name'        => __( 'Registro', 'flavor-chat-ia' ),
                'description' => __( 'Formulario de registro', 'flavor-chat-ia' ),
            ),
            'sponsors'     => array(
                'id'          => 'sponsors',
                'name'        => __( 'Patrocinadores', 'flavor-chat-ia' ),
                'description' => __( 'Logos de patrocinadores', 'flavor-chat-ia' ),
            ),
            'rewards'      => array(
                'id'          => 'rewards',
                'name'        => __( 'Recompensas', 'flavor-chat-ia' ),
                'description' => __( 'Niveles de recompensa para crowdfunding', 'flavor-chat-ia' ),
            ),
            'updates'      => array(
                'id'          => 'updates',
                'name'        => __( 'Actualizaciones', 'flavor-chat-ia' ),
                'description' => __( 'Noticias y actualizaciones', 'flavor-chat-ia' ),
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

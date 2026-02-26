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
}

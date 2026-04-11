<?php
/**
 * Trait para análisis de contenido VBP
 *
 * Este trait contiene métodos para analizar contenido de páginas VBP:
 * legibilidad, keywords, frases y análisis completo.
 *
 * @package Flavor_Platform
 * @subpackage API\Traits
 * @since 2.2.0
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Trait VBP_API_ContentAnalysis
 *
 * Contiene métodos para:
 * - Análisis de legibilidad (analyze_readability)
 * - Análisis de keywords (analyze_keywords)
 * - Análisis completo (full_content_analysis)
 */
trait VBP_API_ContentAnalysis {

    /**
     * Analiza legibilidad del contenido
     *
     * @param WP_REST_Request $request Petición REST.
     * @return WP_REST_Response
     */
    public function analyze_readability( $request ) {
        $page_id = (int) $request->get_param( 'id' );

        $post = get_post( $page_id );
        if ( ! $this->is_valid_vbp_post( $post ) ) {
            return new WP_REST_Response( array(
                'success' => false,
                'error'   => 'Página no encontrada.',
            ), 404 );
        }

        $elements = get_post_meta( $page_id, '_flavor_vbp_elements', true ) ?: array();
        $all_text = $this->extract_all_text_content( $elements );

        if ( empty( $all_text ) ) {
            return new WP_REST_Response( array(
                'success' => true,
                'page_id' => $page_id,
                'message' => 'No hay contenido de texto para analizar.',
            ), 200 );
        }

        // Calcular métricas
        $sentences = preg_split( '/[.!?]+/', $all_text, -1, PREG_SPLIT_NO_EMPTY );
        $words = str_word_count( $all_text, 1, 'áéíóúñüÁÉÍÓÚÑÜ' );
        $syllables = $this->count_syllables( $all_text );

        $word_count = count( $words );
        $sentence_count = count( $sentences );
        $syllable_count = $syllables;

        // Flesch Reading Ease (adaptado al español)
        $avg_sentence_length = $sentence_count > 0 ? $word_count / $sentence_count : 0;
        $avg_syllables_per_word = $word_count > 0 ? $syllable_count / $word_count : 0;

        $flesch_score = 206.835 - ( 1.015 * $avg_sentence_length ) - ( 84.6 * $avg_syllables_per_word );
        $flesch_score = max( 0, min( 100, $flesch_score ) );

        // Determinar nivel
        $reading_level = 'Muy difícil';
        if ( $flesch_score >= 90 ) $reading_level = 'Muy fácil';
        elseif ( $flesch_score >= 80 ) $reading_level = 'Fácil';
        elseif ( $flesch_score >= 70 ) $reading_level = 'Bastante fácil';
        elseif ( $flesch_score >= 60 ) $reading_level = 'Normal';
        elseif ( $flesch_score >= 50 ) $reading_level = 'Bastante difícil';
        elseif ( $flesch_score >= 30 ) $reading_level = 'Difícil';

        // Sugerencias
        $suggestions = array();
        if ( $avg_sentence_length > 20 ) {
            $suggestions[] = 'Las oraciones son muy largas. Intenta dividirlas.';
        }
        if ( $avg_syllables_per_word > 2.5 ) {
            $suggestions[] = 'Usa palabras más simples y cortas.';
        }
        if ( $word_count < 100 ) {
            $suggestions[] = 'El contenido es muy corto. Considera expandirlo.';
        }

        return new WP_REST_Response( array(
            'success' => true,
            'page_id' => $page_id,
            'metrics' => array(
                'word_count'             => $word_count,
                'sentence_count'         => $sentence_count,
                'syllable_count'         => $syllable_count,
                'avg_sentence_length'    => round( $avg_sentence_length, 1 ),
                'avg_syllables_per_word' => round( $avg_syllables_per_word, 2 ),
            ),
            'readability' => array(
                'flesch_score'  => round( $flesch_score, 1 ),
                'reading_level' => $reading_level,
                'grade'         => $this->flesch_to_grade( $flesch_score ),
            ),
            'suggestions' => $suggestions,
        ), 200 );
    }

    /**
     * Extrae todo el contenido de texto
     *
     * @param array $elements Elementos VBP.
     * @return string
     */
    private function extract_all_text_content( $elements ) {
        $texts = array();

        foreach ( $elements as $element ) {
            $props = $element['props'] ?? array();

            foreach ( array( 'text', 'content', 'title', 'subtitle', 'description', 'label' ) as $prop ) {
                if ( ! empty( $props[ $prop ] ) && is_string( $props[ $prop ] ) ) {
                    $texts[] = strip_tags( $props[ $prop ] );
                }
            }

            if ( ! empty( $element['children'] ) ) {
                $texts[] = $this->extract_all_text_content( $element['children'] );
            }
        }

        return implode( ' ', array_filter( $texts ) );
    }

    /**
     * Cuenta sílabas en español (aproximado)
     *
     * @param string $text Texto.
     * @return int
     */
    private function count_syllables( $text ) {
        $text = mb_strtolower( $text );
        $vowels = array( 'a', 'e', 'i', 'o', 'u', 'á', 'é', 'í', 'ó', 'ú', 'ü' );
        $count = 0;
        $prev_was_vowel = false;

        for ( $i = 0; $i < mb_strlen( $text ); $i++ ) {
            $char = mb_substr( $text, $i, 1 );
            $is_vowel = in_array( $char, $vowels, true );

            if ( $is_vowel && ! $prev_was_vowel ) {
                $count++;
            }

            $prev_was_vowel = $is_vowel;
        }

        return max( 1, $count );
    }

    /**
     * Convierte Flesch score a grado
     *
     * @param float $score Score Flesch.
     * @return string
     */
    private function flesch_to_grade( $score ) {
        if ( $score >= 90 ) return 'A+';
        if ( $score >= 80 ) return 'A';
        if ( $score >= 70 ) return 'B';
        if ( $score >= 60 ) return 'C';
        if ( $score >= 50 ) return 'D';
        return 'F';
    }

    /**
     * Analiza keywords del contenido
     *
     * @param WP_REST_Request $request Petición REST.
     * @return WP_REST_Response
     */
    public function analyze_keywords( $request ) {
        $page_id = (int) $request->get_param( 'id' );

        $post = get_post( $page_id );
        if ( ! $this->is_valid_vbp_post( $post ) ) {
            return new WP_REST_Response( array(
                'success' => false,
                'error'   => 'Página no encontrada.',
            ), 404 );
        }

        $elements = get_post_meta( $page_id, '_flavor_vbp_elements', true ) ?: array();
        $all_text = $this->extract_all_text_content( $elements );
        $all_text = mb_strtolower( $all_text );

        // Stopwords en español
        $stopwords = array(
            'el', 'la', 'los', 'las', 'un', 'una', 'unos', 'unas', 'de', 'del', 'al',
            'a', 'en', 'con', 'por', 'para', 'es', 'son', 'ser', 'que', 'y', 'o',
            'pero', 'si', 'no', 'su', 'sus', 'este', 'esta', 'estos', 'estas',
            'ese', 'esa', 'esos', 'esas', 'como', 'más', 'menos', 'muy', 'todo',
            'todos', 'toda', 'todas', 'ha', 'han', 'he', 'hay', 'se', 'le', 'les',
        );

        $words = str_word_count( $all_text, 1, 'áéíóúñüÁÉÍÓÚÑÜ' );
        $words = array_filter( $words, function( $word ) use ( $stopwords ) {
            return mb_strlen( $word ) > 2 && ! in_array( $word, $stopwords, true );
        } );

        $word_freq = array_count_values( $words );
        arsort( $word_freq );

        // Top keywords
        $keywords = array();
        $i = 0;
        foreach ( $word_freq as $word => $count ) {
            if ( $i >= 20 ) break;
            $keywords[] = array(
                'word'    => $word,
                'count'   => $count,
                'density' => round( ( $count / count( $words ) ) * 100, 2 ),
            );
            $i++;
        }

        // Extraer frases de 2-3 palabras
        $phrases = $this->extract_phrases( $all_text, $stopwords );

        return new WP_REST_Response( array(
            'success'      => true,
            'page_id'      => $page_id,
            'total_words'  => count( $words ),
            'unique_words' => count( $word_freq ),
            'keywords'     => $keywords,
            'phrases'      => array_slice( $phrases, 0, 10 ),
        ), 200 );
    }

    /**
     * Extrae frases comunes
     *
     * @param string $text Texto.
     * @param array  $stopwords Stopwords.
     * @return array
     */
    private function extract_phrases( $text, $stopwords ) {
        $words = str_word_count( $text, 1, 'áéíóúñüÁÉÍÓÚÑÜ' );
        $bigrams = array();
        $trigrams = array();

        for ( $i = 0; $i < count( $words ) - 1; $i++ ) {
            if ( ! in_array( $words[ $i ], $stopwords, true ) && ! in_array( $words[ $i + 1 ], $stopwords, true ) ) {
                $bigram = $words[ $i ] . ' ' . $words[ $i + 1 ];
                $bigrams[] = $bigram;
            }
        }

        $bigram_freq = array_count_values( $bigrams );
        arsort( $bigram_freq );

        $phrases = array();
        foreach ( $bigram_freq as $phrase => $count ) {
            if ( $count >= 2 ) {
                $phrases[] = array(
                    'phrase' => $phrase,
                    'count'  => $count,
                );
            }
        }

        return $phrases;
    }

    /**
     * Análisis completo de contenido
     *
     * @param WP_REST_Request $request Petición REST.
     * @return WP_REST_Response
     */
    public function full_content_analysis( $request ) {
        $page_id = (int) $request->get_param( 'id' );

        // Combinar análisis
        $readability = $this->analyze_readability( $request )->get_data();
        $keywords = $this->analyze_keywords( $request )->get_data();
        $seo = $this->analyze_page_seo( $request )->get_data();

        return new WP_REST_Response( array(
            'success'     => true,
            'page_id'     => $page_id,
            'readability' => $readability['readability'] ?? null,
            'keywords'    => $keywords['keywords'] ?? array(),
            'seo'         => $seo['analysis'] ?? null,
            'overall_score' => $this->calculate_overall_content_score( $readability, $keywords, $seo ),
        ), 200 );
    }

    /**
     * Calcula puntuación general
     *
     * @param array $readability Datos de legibilidad.
     * @param array $keywords Datos de keywords.
     * @param array $seo Datos de SEO.
     * @return array
     */
    private function calculate_overall_content_score( $readability, $keywords, $seo ) {
        $score = 0;
        $max_score = 0;

        // Legibilidad (30 puntos)
        $max_score += 30;
        $flesch = $readability['readability']['flesch_score'] ?? 0;
        $score += min( 30, ( $flesch / 100 ) * 30 );

        // Keywords (20 puntos)
        $max_score += 20;
        $unique = $keywords['unique_words'] ?? 0;
        if ( $unique > 50 ) $score += 20;
        elseif ( $unique > 30 ) $score += 15;
        elseif ( $unique > 15 ) $score += 10;

        // SEO (50 puntos)
        $max_score += 50;
        $seo_score = $seo['analysis']['score'] ?? 0;
        $score += ( $seo_score / 100 ) * 50;

        $percentage = $max_score > 0 ? round( ( $score / $max_score ) * 100 ) : 0;

        return array(
            'score'      => round( $score ),
            'max_score'  => $max_score,
            'percentage' => $percentage,
            'grade'      => $this->percentage_to_grade( $percentage ),
        );
    }

    /**
     * Convierte porcentaje a grado
     *
     * @param int $percentage Porcentaje.
     * @return string
     */
    private function percentage_to_grade( $percentage ) {
        if ( $percentage >= 90 ) return 'A';
        if ( $percentage >= 80 ) return 'B';
        if ( $percentage >= 70 ) return 'C';
        if ( $percentage >= 60 ) return 'D';
        return 'F';
    }
}

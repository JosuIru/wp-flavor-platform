<?php
/**
 * Trait para funcionalidad de multiidioma/traducción VBP
 *
 * Este trait contiene todos los métodos relacionados con traducción
 * y gestión multiidioma de páginas VBP.
 *
 * @package Flavor_Platform
 * @subpackage API\Traits
 * @since 2.2.0
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Trait VBP_API_I18n
 *
 * Contiene métodos para:
 * - Obtener idiomas disponibles (get_languages)
 * - Traducir páginas (translate_page, translate_page_all_languages)
 * - Obtener traducciones existentes (get_page_translations)
 * - Traducir contenido genérico (translate_content)
 * - Crear páginas multilingües (create_multilingual_page)
 */
trait VBP_API_I18n {

    /**
     * Verifica si el addon multilingual está disponible
     *
     * @return bool
     */
    private function is_multilingual_available() {
        return class_exists( 'Flavor_Multilingual' ) && class_exists( 'Flavor_AI_Translator' );
    }

    /**
     * Obtiene los idiomas disponibles
     *
     * @param WP_REST_Request $request Petición REST.
     * @return WP_REST_Response
     */
    public function get_languages( $request ) {
        if ( ! $this->is_multilingual_available() ) {
            return new WP_REST_Response( array(
                'available'  => false,
                'message'    => 'El addon flavor-multilingual no está activo',
                'languages'  => array(
                    array( 'code' => 'es', 'name' => 'Español', 'native_name' => 'Español', 'is_default' => true ),
                    array( 'code' => 'eu', 'name' => 'Euskera', 'native_name' => 'Euskara', 'is_default' => false ),
                    array( 'code' => 'en', 'name' => 'English', 'native_name' => 'English', 'is_default' => false ),
                    array( 'code' => 'fr', 'name' => 'French', 'native_name' => 'Français', 'is_default' => false ),
                ),
            ), 200 );
        }

        $core = Flavor_Multilingual_Core::get_instance();
        $active_languages = $core->get_active_languages();
        $default_language = $core->get_default_language();

        $languages = array();
        foreach ( $active_languages as $code => $lang ) {
            $languages[] = array(
                'code'        => $code,
                'name'        => $lang['name'],
                'native_name' => $lang['native_name'],
                'flag'        => $lang['flag'] ?? null,
                'is_rtl'      => $lang['is_rtl'] ?? false,
                'is_default'  => ( $code === $default_language ),
            );
        }

        return new WP_REST_Response( array(
            'available' => true,
            'default'   => $default_language,
            'languages' => $languages,
        ), 200 );
    }

    /**
     * Traduce una página VBP a un idioma específico
     *
     * @param WP_REST_Request $request Petición REST.
     * @return WP_REST_Response
     */
    public function translate_page( $request ) {
        $page_id = (int) $request->get_param( 'id' );
        $to_lang = sanitize_key( $request->get_param( 'to_lang' ) );
        $save = (bool) $request->get_param( 'save' );
        $create_copy = (bool) $request->get_param( 'create_copy' );

        $post = get_post( $page_id );
        if ( ! $this->is_valid_vbp_post( $post ) ) {
            return new WP_REST_Response( array(
                'success' => false,
                'error'   => 'Página VBP no encontrada',
            ), 404 );
        }

        $elements = get_post_meta( $page_id, '_flavor_vbp_elements', true );
        if ( empty( $elements ) ) {
            $elements = array();
        }

        $translated_title = $this->translate_text_with_ai( $post->post_title, 'es', $to_lang );
        $translated_elements = $this->translate_vbp_elements( $elements, 'es', $to_lang );

        $result = array(
            'original_id' => $page_id,
            'language'    => $to_lang,
            'title'       => $translated_title,
            'elements'    => $translated_elements,
        );

        if ( $save && $this->is_multilingual_available() ) {
            $storage = Flavor_Translation_Storage::get_instance();
            $storage->save_translation( 'post', $page_id, $to_lang, 'title', $translated_title, array(
                'status' => 'published',
                'auto'   => true,
            ) );
            $storage->save_translation( 'post', $page_id, $to_lang, 'vbp_elements', wp_json_encode( $translated_elements ), array(
                'status' => 'published',
                'auto'   => true,
            ) );
            $result['saved'] = true;
        }

        if ( $create_copy ) {
            $new_page_id = $this->create_translated_page_copy( $post, $translated_title, $translated_elements, $to_lang );
            $result['new_page_id'] = $new_page_id;
            $result['new_page_url'] = get_permalink( $new_page_id );
        }

        return new WP_REST_Response( array(
            'success' => true,
            'data'    => $result,
        ), 200 );
    }

    /**
     * Traduce una página a todos los idiomas activos
     *
     * @param WP_REST_Request $request Petición REST.
     * @return WP_REST_Response
     */
    public function translate_page_all_languages( $request ) {
        $page_id = (int) $request->get_param( 'id' );
        $target_languages = $request->get_param( 'languages' );
        $save = (bool) $request->get_param( 'save' );
        $create_copies = (bool) $request->get_param( 'create_copies' );

        $post = get_post( $page_id );
        if ( ! $this->is_valid_vbp_post( $post ) ) {
            return new WP_REST_Response( array(
                'success' => false,
                'error'   => 'Página VBP no encontrada',
            ), 404 );
        }

        if ( empty( $target_languages ) ) {
            if ( $this->is_multilingual_available() ) {
                $core = Flavor_Multilingual_Core::get_instance();
                $active = $core->get_active_languages();
                $default = $core->get_default_language();
                $target_languages = array_filter( array_keys( $active ), function( $code ) use ( $default ) {
                    return $code !== $default;
                } );
            } else {
                $target_languages = array( 'eu', 'en', 'fr' );
            }
        }

        $elements = get_post_meta( $page_id, '_flavor_vbp_elements', true );
        if ( empty( $elements ) ) {
            $elements = array();
        }

        $translations = array();
        $created_pages = array();

        foreach ( $target_languages as $to_lang ) {
            $translated_title = $this->translate_text_with_ai( $post->post_title, 'es', $to_lang );
            $translated_elements = $this->translate_vbp_elements( $elements, 'es', $to_lang );

            $translations[ $to_lang ] = array(
                'title'    => $translated_title,
                'elements' => $translated_elements,
            );

            if ( $save && $this->is_multilingual_available() ) {
                $storage = Flavor_Translation_Storage::get_instance();
                $storage->save_translation( 'post', $page_id, $to_lang, 'title', $translated_title, array(
                    'status' => 'published',
                    'auto'   => true,
                ) );
                $storage->save_translation( 'post', $page_id, $to_lang, 'vbp_elements', wp_json_encode( $translated_elements ), array(
                    'status' => 'published',
                    'auto'   => true,
                ) );
            }

            if ( $create_copies ) {
                $new_page_id = $this->create_translated_page_copy( $post, $translated_title, $translated_elements, $to_lang );
                $created_pages[ $to_lang ] = array(
                    'id'  => $new_page_id,
                    'url' => get_permalink( $new_page_id ),
                );
            }
        }

        return new WP_REST_Response( array(
            'success'       => true,
            'original_id'   => $page_id,
            'languages'     => array_keys( $translations ),
            'translations'  => $translations,
            'created_pages' => $created_pages,
        ), 200 );
    }

    /**
     * Obtiene las traducciones existentes de una página
     *
     * @param WP_REST_Request $request Petición REST.
     * @return WP_REST_Response
     */
    public function get_page_translations( $request ) {
        $page_id = (int) $request->get_param( 'id' );

        $post = get_post( $page_id );
        if ( ! $this->is_valid_vbp_post( $post ) ) {
            return new WP_REST_Response( array(
                'success' => false,
                'error'   => 'Página VBP no encontrada',
            ), 404 );
        }

        if ( ! $this->is_multilingual_available() ) {
            return new WP_REST_Response( array(
                'success'      => true,
                'page_id'      => $page_id,
                'translations' => array(),
                'message'      => 'Addon multilingual no disponible',
            ), 200 );
        }

        $storage = Flavor_Translation_Storage::get_instance();
        $translations = $storage->get_all_translations( 'post', $page_id );

        return new WP_REST_Response( array(
            'success'      => true,
            'page_id'      => $page_id,
            'title'        => $post->post_title,
            'translations' => $translations,
        ), 200 );
    }

    /**
     * Traduce contenido (texto, HTML o elementos VBP)
     *
     * @param WP_REST_Request $request Petición REST.
     * @return WP_REST_Response
     */
    public function translate_content( $request ) {
        $content = $request->get_param( 'content' );
        $from_lang = sanitize_key( $request->get_param( 'from_lang' ) );
        $to_lang = sanitize_key( $request->get_param( 'to_lang' ) );
        $type = sanitize_key( $request->get_param( 'type' ) );

        switch ( $type ) {
            case 'vbp_elements':
                $elements = is_array( $content ) ? $content : json_decode( $content, true );
                $translated = $this->translate_vbp_elements( $elements, $from_lang, $to_lang );
                break;
            case 'html':
                $translated = $this->translate_html_with_ai( $content, $from_lang, $to_lang );
                break;
            case 'text':
            default:
                $translated = $this->translate_text_with_ai( $content, $from_lang, $to_lang );
                break;
        }

        return new WP_REST_Response( array(
            'success'    => true,
            'original'   => $content,
            'translated' => $translated,
            'from'       => $from_lang,
            'to'         => $to_lang,
            'type'       => $type,
        ), 200 );
    }

    /**
     * Crea una página multilingüe con traducciones automáticas
     *
     * @param WP_REST_Request $request Petición REST.
     * @return WP_REST_Response
     */
    public function create_multilingual_page( $request ) {
        $title = sanitize_text_field( $request->get_param( 'title' ) );
        $elements = $request->get_param( 'elements' );
        $base_lang = sanitize_key( $request->get_param( 'base_lang' ) );
        $target_languages = $request->get_param( 'languages' );
        $status = sanitize_key( $request->get_param( 'status' ) );

        if ( ! is_array( $elements ) ) {
            $elements = json_decode( $elements, true );
        }

        // Crear página base
        $base_page = $this->i18n_internal_create_page( $title, $elements, $status );
        if ( is_wp_error( $base_page ) ) {
            return new WP_REST_Response( array(
                'success' => false,
                'error'   => $base_page->get_error_message(),
            ), 500 );
        }

        $base_page_id = $base_page['id'];
        $created_pages = array(
            $base_lang => array(
                'id'    => $base_page_id,
                'url'   => get_permalink( $base_page_id ),
                'title' => $title,
            ),
        );

        foreach ( $target_languages as $to_lang ) {
            if ( $to_lang === $base_lang ) {
                continue;
            }

            $translated_title = $this->translate_text_with_ai( $title, $base_lang, $to_lang );
            $translated_elements = $this->translate_vbp_elements( $elements, $base_lang, $to_lang );

            $translated_page = $this->i18n_internal_create_page( $translated_title, $translated_elements, $status );
            if ( ! is_wp_error( $translated_page ) ) {
                $translated_page_id = $translated_page['id'];
                update_post_meta( $translated_page_id, '_flavor_language', $to_lang );
                update_post_meta( $translated_page_id, '_flavor_translation_of', $base_page_id );

                $created_pages[ $to_lang ] = array(
                    'id'    => $translated_page_id,
                    'url'   => get_permalink( $translated_page_id ),
                    'title' => $translated_title,
                );
            }
        }

        update_post_meta( $base_page_id, '_flavor_translations', wp_json_encode( $created_pages ) );

        return new WP_REST_Response( array(
            'success'       => true,
            'base_language' => $base_lang,
            'pages'         => $created_pages,
            'total'         => count( $created_pages ),
        ), 201 );
    }

    /**
     * Traduce texto usando la IA
     *
     * @param string $text      Texto a traducir.
     * @param string $from_lang Idioma origen.
     * @param string $to_lang   Idioma destino.
     * @return string
     */
    private function translate_text_with_ai( $text, $from_lang, $to_lang ) {
        if ( empty( $text ) || $from_lang === $to_lang ) {
            return $text;
        }

        if ( $this->is_multilingual_available() ) {
            $translator = Flavor_AI_Translator::get_instance();
            $result = $translator->translate_text( $text, $from_lang, $to_lang );
            if ( ! is_wp_error( $result ) && ! empty( $result ) ) {
                return $result;
            }
        }

        if ( class_exists( 'Flavor_Engine_Manager' ) ) {
            $lang_names = $this->get_language_names();
            $from_name = $lang_names[ $from_lang ] ?? $from_lang;
            $to_name = $lang_names[ $to_lang ] ?? $to_lang;

            $system_prompt = "Eres un traductor profesional. Traduce del {$from_name} al {$to_name}. Responde ÚNICAMENTE con la traducción, sin explicaciones.";
            $messages = array( array( 'role' => 'user', 'content' => $text ) );

            try {
                $engine = Flavor_Engine_Manager::get_instance();
                $response = $engine->send_message( $messages, $system_prompt );
                if ( ! empty( $response['success'] ) && ! empty( $response['content'] ) ) {
                    return trim( $response['content'] );
                }
            } catch ( Exception $e ) {
                // Fallback
            }
        }

        return $text;
    }

    /**
     * Traduce HTML usando la IA
     *
     * @param string $html      HTML a traducir.
     * @param string $from_lang Idioma origen.
     * @param string $to_lang   Idioma destino.
     * @return string
     */
    private function translate_html_with_ai( $html, $from_lang, $to_lang ) {
        if ( empty( $html ) || $from_lang === $to_lang ) {
            return $html;
        }

        if ( $this->is_multilingual_available() ) {
            $translator = Flavor_AI_Translator::get_instance();
            $result = $translator->translate_html( $html, $from_lang, $to_lang );
            if ( ! is_wp_error( $result ) && ! empty( $result ) ) {
                return $result;
            }
        }

        if ( class_exists( 'Flavor_Engine_Manager' ) ) {
            $lang_names = $this->get_language_names();
            $from_name = $lang_names[ $from_lang ] ?? $from_lang;
            $to_name = $lang_names[ $to_lang ] ?? $to_lang;

            $system_prompt = "Eres un traductor de HTML. Traduce de {$from_name} a {$to_name}. Mantén las etiquetas HTML intactas.";
            $messages = array( array( 'role' => 'user', 'content' => $html ) );

            try {
                $engine = Flavor_Engine_Manager::get_instance();
                $response = $engine->send_message( $messages, $system_prompt );
                if ( ! empty( $response['success'] ) && ! empty( $response['content'] ) ) {
                    return trim( $response['content'] );
                }
            } catch ( Exception $e ) {
                // Fallback
            }
        }

        return $html;
    }

    /**
     * Traduce elementos VBP recursivamente
     *
     * @param array  $elements  Elementos VBP.
     * @param string $from_lang Idioma origen.
     * @param string $to_lang   Idioma destino.
     * @return array
     */
    private function translate_vbp_elements( $elements, $from_lang, $to_lang ) {
        if ( empty( $elements ) || ! is_array( $elements ) ) {
            return $elements;
        }

        $translated = array();
        foreach ( $elements as $element ) {
            $translated[] = $this->translate_single_element( $element, $from_lang, $to_lang );
        }

        return $translated;
    }

    /**
     * Traduce un elemento VBP individual
     *
     * @param array  $element   Elemento VBP.
     * @param string $from_lang Idioma origen.
     * @param string $to_lang   Idioma destino.
     * @return array
     */
    private function translate_single_element( $element, $from_lang, $to_lang ) {
        if ( ! is_array( $element ) ) {
            return $element;
        }

        $text_fields = array(
            'titulo', 'title', 'subtitulo', 'subtitle',
            'descripcion', 'description', 'texto', 'text',
            'etiqueta', 'label', 'boton_texto', 'button_text',
            'pregunta', 'question', 'respuesta', 'answer',
            'nota', 'note', 'extracto', 'excerpt',
            'nombre', 'name', 'valor', 'value',
            'placeholder', 'mensaje', 'message',
        );

        if ( isset( $element['data'] ) && is_array( $element['data'] ) ) {
            $element['data'] = $this->translate_element_data( $element['data'], $from_lang, $to_lang, $text_fields );
        }

        return $element;
    }

    /**
     * Traduce los datos de un elemento VBP
     *
     * @param array  $data        Datos del elemento.
     * @param string $from_lang   Idioma origen.
     * @param string $to_lang     Idioma destino.
     * @param array  $text_fields Campos de texto a traducir.
     * @return array
     */
    private function translate_element_data( $data, $from_lang, $to_lang, $text_fields ) {
        foreach ( $data as $key => $value ) {
            if ( in_array( $key, $text_fields, true ) && is_string( $value ) && ! empty( $value ) ) {
                if ( ! $this->is_technical_value( $value ) ) {
                    $data[ $key ] = $this->translate_text_with_ai( $value, $from_lang, $to_lang );
                }
            } elseif ( is_array( $value ) ) {
                if ( $this->is_items_array( $value ) ) {
                    $data[ $key ] = array_map( function( $item ) use ( $from_lang, $to_lang, $text_fields ) {
                        if ( is_array( $item ) ) {
                            return $this->translate_element_data( $item, $from_lang, $to_lang, $text_fields );
                        }
                        return $item;
                    }, $value );
                } elseif ( $this->is_nested_object( $value ) ) {
                    $data[ $key ] = $this->translate_element_data( $value, $from_lang, $to_lang, $text_fields );
                }
            }
        }

        return $data;
    }

    /**
     * Verifica si un valor es técnico (URL, código, etc.)
     *
     * @param string $value Valor a verificar.
     * @return bool
     */
    private function is_technical_value( $value ) {
        if ( filter_var( $value, FILTER_VALIDATE_URL ) ) {
            return true;
        }
        if ( filter_var( $value, FILTER_VALIDATE_EMAIL ) ) {
            return true;
        }
        if ( preg_match( '/^#[0-9A-Fa-f]{3,8}$/', $value ) ) {
            return true;
        }
        if ( preg_match( '/^rgba?\(/', $value ) ) {
            return true;
        }
        if ( strpos( $value, 'gradient' ) !== false ) {
            return true;
        }
        if ( preg_match( '/^[\d.]+(px|em|rem|%|vh|vw)?$/', $value ) ) {
            return true;
        }

        return false;
    }

    /**
     * Verifica si es un array de items
     *
     * @param array $value Array a verificar.
     * @return bool
     */
    private function is_items_array( $value ) {
        if ( empty( $value ) ) {
            return false;
        }
        $first = reset( $value );
        return is_array( $first ) && ! isset( $first[0] );
    }

    /**
     * Verifica si es un objeto anidado
     *
     * @param array $value Array a verificar.
     * @return bool
     */
    private function is_nested_object( $value ) {
        if ( empty( $value ) ) {
            return false;
        }
        foreach ( array_keys( $value ) as $key ) {
            if ( is_string( $key ) && ! is_numeric( $key ) ) {
                return true;
            }
        }
        return false;
    }

    /**
     * Crea una copia de la página traducida
     *
     * @param WP_Post $original_post       Post original.
     * @param string  $translated_title    Título traducido.
     * @param array   $translated_elements Elementos traducidos.
     * @param string  $language            Código de idioma.
     * @return int ID del nuevo post.
     */
    private function create_translated_page_copy( $original_post, $translated_title, $translated_elements, $language ) {
        $slug = sanitize_title( $translated_title ) . '-' . $language;

        $new_post_id = wp_insert_post( array(
            'post_title'  => $translated_title,
            'post_name'   => $slug,
            'post_type'   => 'flavor_landing',
            'post_status' => $original_post->post_status,
            'post_author' => $original_post->post_author,
        ) );

        if ( ! is_wp_error( $new_post_id ) ) {
            $design_preset = get_post_meta( $original_post->ID, '_flavor_vbp_design_preset', true );
            if ( $design_preset ) {
                update_post_meta( $new_post_id, '_flavor_vbp_design_preset', $design_preset );
            }
            update_post_meta( $new_post_id, '_flavor_vbp_elements', $translated_elements );
            update_post_meta( $new_post_id, '_flavor_language', $language );
            update_post_meta( $new_post_id, '_flavor_translation_of', $original_post->ID );
        }

        return $new_post_id;
    }

    /**
     * Crea una página internamente para i18n
     *
     * @param string $title    Título.
     * @param array  $elements Elementos.
     * @param string $status   Estado.
     * @return array|WP_Error
     */
    private function i18n_internal_create_page( $title, $elements, $status = 'publish' ) {
        $slug = sanitize_title( $title );

        $post_id = wp_insert_post( array(
            'post_title'  => $title,
            'post_name'   => $slug,
            'post_type'   => 'flavor_landing',
            'post_status' => $status,
        ) );

        if ( is_wp_error( $post_id ) ) {
            return $post_id;
        }

        update_post_meta( $post_id, '_flavor_vbp_elements', $elements );

        return array(
            'id'  => $post_id,
            'url' => get_permalink( $post_id ),
        );
    }

    /**
     * Obtiene nombres de idiomas
     *
     * @return array
     */
    private function get_language_names() {
        return array(
            'es' => 'Español',
            'eu' => 'Euskera',
            'en' => 'Inglés',
            'fr' => 'Francés',
            'de' => 'Alemán',
            'it' => 'Italiano',
            'pt' => 'Portugués',
            'ca' => 'Catalán',
            'gl' => 'Gallego',
        );
    }
}

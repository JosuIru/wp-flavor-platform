<?php
/**
 * Visual Builder Pro - WP-CLI Commands
 *
 * Comandos CLI para interactuar con VBP desde terminal.
 * Permite a Claude Code crear páginas aprovechando los bloques y widgets de módulos.
 *
 * @package Flavor_Platform
 * @subpackage CLI
 * @since 2.1.0
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

if ( ! class_exists( 'WP_CLI' ) ) {
    return;
}

/**
 * Comandos WP-CLI para Visual Builder Pro
 *
 * ## EXAMPLES
 *
 *     # Listar bloques disponibles
 *     wp vbp list-blocks
 *
 *     # Exportar schema de bloques
 *     wp vbp export-schema --output=vbp-schema.json
 *
 *     # Crear página desde JSON
 *     wp vbp create-page --title="Mi Landing" --json=estructura.json
 *
 *     # Listar widgets de módulos activos
 *     wp vbp list-modules
 *
 * @since 2.1.0
 */
class Flavor_VBP_CLI {

    /**
     * Lista todos los bloques disponibles en VBP
     *
     * ## OPTIONS
     *
     * [--category=<category>]
     * : Filtrar por categoría (sections, basic, layout, forms, media, modules, widgets)
     *
     * [--format=<format>]
     * : Formato de salida (table, json, csv, yaml)
     * ---
     * default: table
     * options:
     *   - table
     *   - json
     *   - csv
     *   - yaml
     * ---
     *
     * ## EXAMPLES
     *
     *     wp vbp list-blocks
     *     wp vbp list-blocks --category=modules
     *     wp vbp list-blocks --format=json
     *
     * @param array $args       Argumentos posicionales.
     * @param array $assoc_args Argumentos asociativos.
     */
    public function list_blocks( $args, $assoc_args ) {
        $formato = $assoc_args['format'] ?? 'table';
        $categoria_filtro = $assoc_args['category'] ?? null;

        if ( ! class_exists( 'Flavor_VBP_Block_Library' ) ) {
            WP_CLI::error( 'Visual Builder Pro no está disponible.' );
        }

        $libreria = Flavor_VBP_Block_Library::get_instance();
        $categorias = $libreria->get_categorias_con_bloques();

        $bloques_lista = array();

        foreach ( $categorias as $categoria ) {
            $categoria_slug = sanitize_title( $categoria['name'] );

            if ( $categoria_filtro && $categoria_slug !== $categoria_filtro ) {
                continue;
            }

            foreach ( $categoria['blocks'] as $bloque ) {
                $bloques_lista[] = array(
                    'type'        => $bloque['type'],
                    'name'        => $bloque['name'],
                    'category'    => $categoria['name'],
                    'description' => $bloque['description'] ?? '',
                    'icon'        => $bloque['icon'] ?? '',
                );
            }
        }

        if ( empty( $bloques_lista ) ) {
            WP_CLI::warning( 'No se encontraron bloques.' );
            return;
        }

        if ( 'json' === $formato ) {
            WP_CLI::line( wp_json_encode( $bloques_lista, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE ) );
        } else {
            WP_CLI\Utils\format_items( $formato, $bloques_lista, array( 'type', 'name', 'category', 'description' ) );
        }
    }

    /**
     * Exporta el schema completo de bloques VBP
     *
     * Genera un archivo JSON con la estructura completa de todos los bloques,
     * incluyendo campos, variantes y opciones. Ideal para que Claude Code
     * pueda generar estructuras VBP válidas.
     *
     * ## OPTIONS
     *
     * [--output=<file>]
     * : Archivo de salida (por defecto imprime a stdout)
     *
     * [--include-styles]
     * : Incluir campos de estilo comunes
     *
     * ## EXAMPLES
     *
     *     wp vbp export-schema
     *     wp vbp export-schema --output=vbp-schema.json
     *     wp vbp export-schema --include-styles
     *
     * @param array $args       Argumentos posicionales.
     * @param array $assoc_args Argumentos asociativos.
     */
    public function export_schema( $args, $assoc_args ) {
        $output_file = $assoc_args['output'] ?? null;
        $include_styles = isset( $assoc_args['include-styles'] );

        if ( ! class_exists( 'Flavor_VBP_Block_Library' ) ) {
            WP_CLI::error( 'Visual Builder Pro no está disponible.' );
        }

        $libreria = Flavor_VBP_Block_Library::get_instance();
        $categorias = $libreria->get_categorias_con_bloques();

        $schema = array(
            'version'     => '2.1.0',
            'generated'   => current_time( 'c' ),
            'description' => 'Schema de bloques VBP para integración con Claude Code',
            'categories'  => array(),
            'blocks'      => array(),
        );

        foreach ( $categorias as $categoria ) {
            $categoria_slug = sanitize_title( $categoria['name'] );
            $schema['categories'][ $categoria_slug ] = array(
                'name'   => $categoria['name'],
                'icon'   => $categoria['icon'] ?? '',
                'blocks' => array(),
            );

            foreach ( $categoria['blocks'] as $bloque ) {
                $bloque_schema = array(
                    'type'        => $bloque['type'],
                    'name'        => $bloque['name'],
                    'description' => $bloque['description'] ?? '',
                    'icon'        => $bloque['icon'] ?? '',
                    'category'    => $categoria_slug,
                    'fields'      => array(),
                    'defaults'    => array(),
                );

                // Extraer campos y defaults
                if ( isset( $bloque['fields'] ) && is_array( $bloque['fields'] ) ) {
                    foreach ( $bloque['fields'] as $field_key => $field_config ) {
                        // Omitir separadores si no se incluyen estilos
                        if ( ! $include_styles && isset( $field_config['type'] ) && 'separator' === $field_config['type'] ) {
                            continue;
                        }

                        $bloque_schema['fields'][ $field_key ] = array(
                            'type'    => $field_config['type'] ?? 'text',
                            'label'   => $field_config['label'] ?? $field_key,
                            'default' => $field_config['default'] ?? null,
                        );

                        if ( isset( $field_config['options'] ) ) {
                            $bloque_schema['fields'][ $field_key ]['options'] = $field_config['options'];
                        }

                        if ( isset( $field_config['default'] ) ) {
                            $bloque_schema['defaults'][ $field_key ] = $field_config['default'];
                        }
                    }
                }

                // Incluir defaults del bloque
                if ( isset( $bloque['defaults'] ) ) {
                    $bloque_schema['defaults'] = array_merge(
                        $bloque_schema['defaults'],
                        $bloque['defaults']
                    );
                }

                $schema['blocks'][ $bloque['type'] ] = $bloque_schema;
                $schema['categories'][ $categoria_slug ]['blocks'][] = $bloque['type'];
            }
        }

        $json_output = wp_json_encode( $schema, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE );

        if ( $output_file ) {
            file_put_contents( $output_file, $json_output );
            WP_CLI::success( "Schema exportado a: {$output_file}" );
        } else {
            WP_CLI::line( $json_output );
        }
    }

    /**
     * Lista módulos activos y sus widgets disponibles para VBP
     *
     * ## OPTIONS
     *
     * [--format=<format>]
     * : Formato de salida
     * ---
     * default: table
     * options:
     *   - table
     *   - json
     * ---
     *
     * ## EXAMPLES
     *
     *     wp vbp list-modules
     *     wp vbp list-modules --format=json
     *
     * @param array $args       Argumentos posicionales.
     * @param array $assoc_args Argumentos asociativos.
     */
    public function list_modules( $args, $assoc_args ) {
        $formato = $assoc_args['format'] ?? 'table';

        // Obtener módulos activos
        $modulos_activos = get_option( 'flavor_chat_modules', array() );
        $modulos_lista = array();

        // Obtener bloques de módulos desde VBP
        if ( class_exists( 'Flavor_VBP_Block_Library' ) ) {
            $libreria = Flavor_VBP_Block_Library::get_instance();
            $categorias = $libreria->get_categorias_con_bloques();

            foreach ( $categorias as $categoria ) {
                // Buscar categorías de módulos
                if ( strpos( strtolower( $categoria['name'] ), 'módulos' ) !== false
                     || strpos( strtolower( $categoria['name'] ), 'widget' ) !== false ) {
                    foreach ( $categoria['blocks'] as $bloque ) {
                        // Extraer nombre del módulo del tipo de bloque
                        $modulo_slug = str_replace( array( 'module_', '_widget', '_list', '_grid', '_card' ), '', $bloque['type'] );

                        $modulos_lista[] = array(
                            'module'      => $modulo_slug,
                            'block_type'  => $bloque['type'],
                            'name'        => $bloque['name'],
                            'description' => $bloque['description'] ?? '',
                            'active'      => in_array( $modulo_slug, $modulos_activos, true ) ? 'Sí' : 'No',
                        );
                    }
                }
            }
        }

        // Añadir shortcodes de módulos disponibles
        $shortcodes_modulos = $this->get_module_shortcodes();
        foreach ( $shortcodes_modulos as $modulo => $shortcodes ) {
            foreach ( $shortcodes as $shortcode ) {
                $existe = false;
                foreach ( $modulos_lista as $item ) {
                    if ( $item['block_type'] === $shortcode ) {
                        $existe = true;
                        break;
                    }
                }
                if ( ! $existe ) {
                    $modulos_lista[] = array(
                        'module'      => $modulo,
                        'block_type'  => "shortcode_{$shortcode}",
                        'name'        => "[{$shortcode}]",
                        'description' => "Shortcode del módulo {$modulo}",
                        'active'      => in_array( $modulo, $modulos_activos, true ) ? 'Sí' : 'No',
                    );
                }
            }
        }

        if ( empty( $modulos_lista ) ) {
            WP_CLI::warning( 'No se encontraron widgets de módulos.' );
            return;
        }

        if ( 'json' === $formato ) {
            WP_CLI::line( wp_json_encode( $modulos_lista, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE ) );
        } else {
            WP_CLI\Utils\format_items( $formato, $modulos_lista, array( 'module', 'block_type', 'name', 'active' ) );
        }
    }

    /**
     * Crea una página VBP desde estructura JSON
     *
     * ## OPTIONS
     *
     * --title=<title>
     * : Título de la página
     *
     * [--json=<file>]
     * : Archivo JSON con la estructura de elementos
     *
     * [--template=<template>]
     * : Plantilla predefinida (hero-features-cta, landing-basica, etc.)
     *
     * [--status=<status>]
     * : Estado del post (draft, publish)
     * ---
     * default: draft
     * ---
     *
     * [--type=<type>]
     * : Tipo de post (flavor_landing, page)
     * ---
     * default: flavor_landing
     * ---
     *
     * ## EXAMPLES
     *
     *     wp vbp create-page --title="Mi Landing" --template=hero-features-cta
     *     wp vbp create-page --title="Página Custom" --json=estructura.json
     *     wp vbp create-page --title="Página" --json=- < estructura.json
     *
     * @param array $args       Argumentos posicionales.
     * @param array $assoc_args Argumentos asociativos.
     */
    public function create_page( $args, $assoc_args ) {
        $titulo = $assoc_args['title'] ?? '';
        $json_file = $assoc_args['json'] ?? null;
        $template = $assoc_args['template'] ?? null;
        $status = $assoc_args['status'] ?? 'draft';
        $post_type = $assoc_args['type'] ?? 'flavor_landing';

        if ( empty( $titulo ) ) {
            WP_CLI::error( 'Se requiere un título (--title)' );
        }

        // Obtener estructura de elementos
        $elementos = array();

        if ( $json_file ) {
            if ( '-' === $json_file ) {
                // Leer de stdin
                $json_content = file_get_contents( 'php://stdin' );
            } else {
                if ( ! file_exists( $json_file ) ) {
                    WP_CLI::error( "Archivo no encontrado: {$json_file}" );
                }
                $json_content = file_get_contents( $json_file );
            }

            $datos = json_decode( $json_content, true );
            if ( json_last_error() !== JSON_ERROR_NONE ) {
                WP_CLI::error( 'JSON inválido: ' . json_last_error_msg() );
            }

            $elementos = $datos['elements'] ?? $datos;
        } elseif ( $template ) {
            $elementos = $this->get_template_elements( $template );
            if ( empty( $elementos ) ) {
                WP_CLI::error( "Plantilla no encontrada: {$template}" );
            }
        }

        // Crear el post
        $post_id = wp_insert_post( array(
            'post_title'  => $titulo,
            'post_type'   => $post_type,
            'post_status' => $status,
        ) );

        if ( is_wp_error( $post_id ) ) {
            WP_CLI::error( 'Error al crear la página: ' . $post_id->get_error_message() );
        }

        // Guardar datos VBP
        $vbp_data = array(
            'version'  => '2.0.15',
            'elements' => $elementos,
            'settings' => array(
                'pageWidth'       => 1200,
                'backgroundColor' => '#ffffff',
                'customCss'       => '',
            ),
        );

        update_post_meta( $post_id, '_flavor_vbp_data', $vbp_data );
        update_post_meta( $post_id, '_flavor_vbp_version', '2.0.15' );

        $edit_url = admin_url( "admin.php?page=vbp-editor&post_id={$post_id}" );
        $view_url = get_permalink( $post_id );

        WP_CLI::success( "Página creada con ID: {$post_id}" );
        WP_CLI::line( "Editor: {$edit_url}" );
        if ( 'publish' === $status ) {
            WP_CLI::line( "Ver: {$view_url}" );
        }

        // Devolver ID para uso en scripts
        return $post_id;
    }

    /**
     * Añade un bloque a una página VBP existente
     *
     * ## OPTIONS
     *
     * <post_id>
     * : ID de la página VBP
     *
     * <block_type>
     * : Tipo de bloque a añadir
     *
     * [--data=<json>]
     * : Datos del bloque en formato JSON
     *
     * [--position=<position>]
     * : Posición donde insertar (start, end, o número)
     * ---
     * default: end
     * ---
     *
     * ## EXAMPLES
     *
     *     wp vbp add-block 123 hero --data='{"titulo":"Bienvenido"}'
     *     wp vbp add-block 123 features --position=1
     *
     * @param array $args       Argumentos posicionales.
     * @param array $assoc_args Argumentos asociativos.
     */
    public function add_block( $args, $assoc_args ) {
        $post_id = absint( $args[0] );
        $block_type = $args[1] ?? '';
        $data_json = $assoc_args['data'] ?? '{}';
        $position = $assoc_args['position'] ?? 'end';

        if ( ! $post_id || ! get_post( $post_id ) ) {
            WP_CLI::error( 'Post no encontrado.' );
        }

        if ( empty( $block_type ) ) {
            WP_CLI::error( 'Se requiere el tipo de bloque.' );
        }

        // Obtener datos actuales
        $vbp_data = get_post_meta( $post_id, '_flavor_vbp_data', true );
        if ( empty( $vbp_data ) ) {
            $vbp_data = array(
                'version'  => '2.0.15',
                'elements' => array(),
                'settings' => array(),
            );
        }

        // Parsear datos del bloque
        $block_data = json_decode( $data_json, true );
        if ( json_last_error() !== JSON_ERROR_NONE ) {
            WP_CLI::error( 'JSON inválido: ' . json_last_error_msg() );
        }

        // Crear el elemento
        $elemento = array(
            'id'       => 'el_' . bin2hex( random_bytes( 6 ) ),
            'type'     => $block_type,
            'name'     => ucfirst( str_replace( '_', ' ', $block_type ) ),
            'visible'  => true,
            'locked'   => false,
            'data'     => $block_data,
            'styles'   => $this->get_default_styles(),
            'children' => array(),
        );

        // Insertar en la posición correcta
        if ( 'start' === $position ) {
            array_unshift( $vbp_data['elements'], $elemento );
        } elseif ( 'end' === $position ) {
            $vbp_data['elements'][] = $elemento;
        } else {
            $pos = absint( $position );
            array_splice( $vbp_data['elements'], $pos, 0, array( $elemento ) );
        }

        // Guardar
        update_post_meta( $post_id, '_flavor_vbp_data', $vbp_data );

        WP_CLI::success( "Bloque '{$block_type}' añadido con ID: {$elemento['id']}" );
    }

    /**
     * Muestra información de una página VBP
     *
     * ## OPTIONS
     *
     * <post_id>
     * : ID de la página VBP
     *
     * [--format=<format>]
     * : Formato de salida
     * ---
     * default: yaml
     * options:
     *   - yaml
     *   - json
     * ---
     *
     * ## EXAMPLES
     *
     *     wp vbp info 123
     *     wp vbp info 123 --format=json
     *
     * @param array $args       Argumentos posicionales.
     * @param array $assoc_args Argumentos asociativos.
     */
    public function info( $args, $assoc_args ) {
        $post_id = absint( $args[0] );
        $formato = $assoc_args['format'] ?? 'yaml';

        $post = get_post( $post_id );
        if ( ! $post ) {
            WP_CLI::error( 'Post no encontrado.' );
        }

        $vbp_data = get_post_meta( $post_id, '_flavor_vbp_data', true );

        $info = array(
            'id'             => $post_id,
            'title'          => $post->post_title,
            'status'         => $post->post_status,
            'type'           => $post->post_type,
            'url'            => get_permalink( $post_id ),
            'edit_url'       => admin_url( "admin.php?page=vbp-editor&post_id={$post_id}" ),
            'vbp_version'    => $vbp_data['version'] ?? 'N/A',
            'elements_count' => count( $vbp_data['elements'] ?? array() ),
            'elements'       => array(),
        );

        if ( ! empty( $vbp_data['elements'] ) ) {
            foreach ( $vbp_data['elements'] as $elemento ) {
                $info['elements'][] = array(
                    'id'   => $elemento['id'],
                    'type' => $elemento['type'],
                    'name' => $elemento['name'] ?? $elemento['type'],
                );
            }
        }

        if ( 'json' === $formato ) {
            WP_CLI::line( wp_json_encode( $info, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE ) );
        } else {
            WP_CLI::line( \Spyc::YAMLDump( $info, 2, 0 ) );
        }
    }

    /**
     * Genera una página completa usando IA
     *
     * ## OPTIONS
     *
     * --title=<title>
     * : Título/tema de la página
     *
     * [--industry=<industry>]
     * : Industria (tech, ecommerce, community, health, food, etc.)
     *
     * [--sections=<sections>]
     * : Secciones a incluir separadas por coma (hero,features,cta,testimonials,faq)
     *
     * [--status=<status>]
     * : Estado del post
     * ---
     * default: draft
     * ---
     *
     * ## EXAMPLES
     *
     *     wp vbp generate --title="App de Productividad" --industry=tech
     *     wp vbp generate --title="Tienda Orgánica" --industry=food --sections=hero,features,cta
     *
     * @param array $args       Argumentos posicionales.
     * @param array $assoc_args Argumentos asociativos.
     */
    public function generate( $args, $assoc_args ) {
        $titulo = $assoc_args['title'] ?? '';
        $industry = $assoc_args['industry'] ?? 'general';
        $sections = $assoc_args['sections'] ?? 'hero,features,cta';
        $status = $assoc_args['status'] ?? 'draft';

        if ( empty( $titulo ) ) {
            WP_CLI::error( 'Se requiere un título (--title)' );
        }

        // Verificar si la IA está disponible
        if ( ! class_exists( 'Flavor_VBP_AI_Content' ) ) {
            WP_CLI::error( 'El módulo de IA no está disponible.' );
        }

        $ai_content = Flavor_VBP_AI_Content::get_instance();
        if ( ! $ai_content->is_ai_available() ) {
            WP_CLI::error( 'La IA no está configurada. Configura un proveedor en Ajustes.' );
        }

        WP_CLI::log( "Generando página: {$titulo}" );
        WP_CLI::log( "Industria: {$industry}" );
        WP_CLI::log( "Secciones: {$sections}" );

        $secciones_lista = array_map( 'trim', explode( ',', $sections ) );
        $elementos = array();

        foreach ( $secciones_lista as $seccion ) {
            WP_CLI::log( "  Generando sección: {$seccion}..." );

            $elemento = $this->generate_section_with_ai( $seccion, $titulo, $industry );
            if ( $elemento ) {
                $elementos[] = $elemento;
            }
        }

        if ( empty( $elementos ) ) {
            WP_CLI::error( 'No se pudieron generar las secciones.' );
        }

        // Crear la página
        $post_id = wp_insert_post( array(
            'post_title'  => $titulo,
            'post_type'   => 'flavor_landing',
            'post_status' => $status,
        ) );

        if ( is_wp_error( $post_id ) ) {
            WP_CLI::error( 'Error al crear la página.' );
        }

        // Guardar datos VBP
        $vbp_data = array(
            'version'  => '2.0.15',
            'elements' => $elementos,
            'settings' => array(
                'pageWidth'       => 1200,
                'backgroundColor' => '#ffffff',
            ),
        );

        update_post_meta( $post_id, '_flavor_vbp_data', $vbp_data );
        update_post_meta( $post_id, '_flavor_vbp_version', '2.0.15' );

        $edit_url = admin_url( "admin.php?page=vbp-editor&post_id={$post_id}" );

        WP_CLI::success( "Página generada con ID: {$post_id}" );
        WP_CLI::line( "Editor: {$edit_url}" );
    }

    /**
     * Genera una sección usando IA
     *
     * @param string $type     Tipo de sección.
     * @param string $titulo   Título/tema de la página.
     * @param string $industry Industria.
     * @return array|null
     */
    private function generate_section_with_ai( $type, $titulo, $industry ) {
        $context = array(
            'topic'    => $titulo,
            'industry' => $industry,
        );

        $data = array();

        switch ( $type ) {
            case 'hero':
                // Simular datos por ahora - en producción llamaría a la API de IA
                $data = array(
                    'titulo'       => $titulo,
                    'subtitulo'    => "La mejor solución para tu negocio en {$industry}",
                    'boton_texto'  => 'Empezar ahora',
                    'boton_url'    => '#contacto',
                    'variante'     => 'centered',
                );
                break;

            case 'features':
                $data = array(
                    'titulo'   => 'Características principales',
                    'features' => array(
                        array(
                            'icono'       => '⚡',
                            'titulo'      => 'Rápido',
                            'descripcion' => 'Implementación en minutos',
                        ),
                        array(
                            'icono'       => '🔒',
                            'titulo'      => 'Seguro',
                            'descripcion' => 'Protección de datos',
                        ),
                        array(
                            'icono'       => '📱',
                            'titulo'      => 'Accesible',
                            'descripcion' => 'Desde cualquier dispositivo',
                        ),
                    ),
                    'columnas' => 3,
                );
                break;

            case 'cta':
                $data = array(
                    'titulo'      => '¿Listo para empezar?',
                    'subtitulo'   => 'Únete a miles de usuarios satisfechos',
                    'boton_texto' => 'Contactar',
                    'boton_url'   => '#contacto',
                );
                break;

            case 'testimonials':
                $data = array(
                    'titulo'       => 'Lo que dicen nuestros clientes',
                    'testimonios' => array(
                        array(
                            'texto'  => 'Excelente servicio, muy recomendable.',
                            'autor'  => 'María García',
                            'cargo'  => 'CEO, TechCorp',
                        ),
                    ),
                );
                break;

            case 'faq':
                $data = array(
                    'titulo' => 'Preguntas frecuentes',
                    'faqs'   => array(
                        array(
                            'pregunta'  => '¿Cómo empiezo?',
                            'respuesta' => 'Es muy sencillo, solo necesitas registrarte.',
                        ),
                        array(
                            'pregunta'  => '¿Tiene soporte?',
                            'respuesta' => 'Sí, ofrecemos soporte 24/7.',
                        ),
                    ),
                );
                break;

            default:
                return null;
        }

        return array(
            'id'       => 'el_' . bin2hex( random_bytes( 6 ) ),
            'type'     => $type,
            'name'     => ucfirst( $type ),
            'visible'  => true,
            'locked'   => false,
            'data'     => $data,
            'styles'   => $this->get_default_styles(),
            'children' => array(),
        );
    }

    /**
     * Obtiene elementos de una plantilla predefinida
     *
     * @param string $template Nombre de la plantilla.
     * @return array
     */
    private function get_template_elements( $template ) {
        $templates = array(
            'hero-features-cta' => array(
                $this->create_element( 'hero', 'Hero', array(
                    'titulo'      => 'Tu título aquí',
                    'subtitulo'   => 'Subtítulo descriptivo',
                    'boton_texto' => 'Comenzar',
                    'variante'    => 'centered',
                ) ),
                $this->create_element( 'features', 'Características', array(
                    'titulo'   => 'Nuestras características',
                    'columnas' => 3,
                ) ),
                $this->create_element( 'cta', 'Call to Action', array(
                    'titulo'      => '¿Listo para empezar?',
                    'boton_texto' => 'Contactar',
                ) ),
            ),
            'landing-basica' => array(
                $this->create_element( 'hero', 'Hero', array(
                    'variante' => 'centered',
                ) ),
                $this->create_element( 'text', 'Contenido', array(
                    'contenido' => '<p>Tu contenido aquí...</p>',
                ) ),
            ),
            'grupos-consumo' => array(
                $this->create_element( 'hero', 'Hero Grupos', array(
                    'titulo'    => 'Grupos de Consumo',
                    'subtitulo' => 'Alimentación consciente y sostenible',
                ) ),
                $this->create_element( 'module_grupos_consumo_listado', 'Listado GC', array() ),
                $this->create_element( 'cta', 'CTA', array(
                    'titulo'      => '¿Quieres unirte?',
                    'boton_texto' => 'Ver grupos disponibles',
                ) ),
            ),
        );

        return $templates[ $template ] ?? array();
    }

    /**
     * Crea un elemento con estructura base
     *
     * @param string $type Tipo de elemento.
     * @param string $name Nombre visible.
     * @param array  $data Datos del elemento.
     * @return array
     */
    private function create_element( $type, $name, $data ) {
        return array(
            'id'       => 'el_' . bin2hex( random_bytes( 6 ) ),
            'type'     => $type,
            'name'     => $name,
            'visible'  => true,
            'locked'   => false,
            'data'     => $data,
            'styles'   => $this->get_default_styles(),
            'children' => array(),
        );
    }

    /**
     * Obtiene estilos por defecto
     *
     * @return array
     */
    private function get_default_styles() {
        return array(
            'spacing'    => array(
                'margin'  => array( 'top' => '', 'right' => '', 'bottom' => '', 'left' => '' ),
                'padding' => array( 'top' => '', 'right' => '', 'bottom' => '', 'left' => '' ),
            ),
            'colors'     => array( 'background' => '', 'text' => '' ),
            'typography' => array(),
            'borders'    => array(),
            'shadows'    => array(),
            'layout'     => array(),
            'advanced'   => array( 'cssId' => '', 'cssClasses' => '', 'customCss' => '' ),
        );
    }

    /**
     * Obtiene shortcodes de módulos
     *
     * @return array
     */
    private function get_module_shortcodes() {
        return array(
            'grupos-consumo'    => array( 'flavor_gc_grupos', 'flavor_gc_productos', 'flavor_gc_pedidos' ),
            'eventos'           => array( 'flavor_eventos', 'flavor_eventos_calendario' ),
            'socios'            => array( 'flavor_socios_formulario', 'flavor_socios_listado' ),
            'marketplace'       => array( 'flavor_marketplace', 'flavor_marketplace_categorias' ),
            'cursos'            => array( 'flavor_cursos', 'flavor_cursos_catalogo' ),
            'biblioteca'        => array( 'flavor_biblioteca', 'flavor_biblioteca_catalogo' ),
            'encuestas'         => array( 'flavor_encuesta', 'flavor_encuestas_listado' ),
            'foros'             => array( 'flavor_foros', 'flavor_foro_temas' ),
            'transparencia'     => array( 'flavor_transparencia_portal', 'flavor_transparencia_presupuesto' ),
            'incidencias'       => array( 'flavor_incidencias', 'flavor_incidencias_form' ),
        );
    }
}

// Registrar comandos WP-CLI
if ( defined( 'WP_CLI' ) && WP_CLI ) {
    WP_CLI::add_command( 'vbp', 'Flavor_VBP_CLI' );
}

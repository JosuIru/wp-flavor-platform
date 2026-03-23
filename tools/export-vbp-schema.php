<?php
/**
 * Script para exportar el schema de VBP
 *
 * Ejecutar desde el navegador:
 *   http://sitio-prueba.local/wp-content/plugins/flavor-chat-ia/tools/export-vbp-schema.php?key=flavor2024
 *
 * O desde CLI con WordPress cargado:
 *   php export-vbp-schema.php
 *
 * Genera: vbp-schema.json en la raíz del plugin
 */

// Cargar WordPress si no está cargado
if ( ! defined( 'ABSPATH' ) ) {
    // Buscar wp-load.php
    $wp_load_paths = array(
        dirname( __FILE__ ) . '/../../../../wp-load.php',
        dirname( __FILE__ ) . '/../../../../../wp-load.php',
        '/home/josu/Local Sites/sitio-prueba/app/public/wp-load.php',
    );

    $loaded = false;
    foreach ( $wp_load_paths as $path ) {
        if ( file_exists( $path ) ) {
            require_once $path;
            $loaded = true;
            break;
        }
    }

    if ( ! $loaded ) {
        die( 'No se pudo cargar WordPress' );
    }
}

// Verificar clave de seguridad si se accede via web
if ( php_sapi_name() !== 'cli' ) {
    $key = isset( $_GET['key'] ) ? sanitize_text_field( $_GET['key'] ) : '';
    if ( $key !== 'flavor2024' && ! current_user_can( 'manage_options' ) ) {
        wp_die( 'Acceso denegado. Usa ?key=flavor2024 o accede como admin.' );
    }
}

// Verificar que VBP esté disponible
if ( ! class_exists( 'Flavor_VBP_Block_Library' ) ) {
    // Intentar cargar manualmente
    $loader_path = dirname( __FILE__ ) . '/../includes/visual-builder-pro/class-vbp-loader.php';
    if ( file_exists( $loader_path ) ) {
        require_once $loader_path;
        Flavor_VBP_Loader::get_instance();
    }
}

if ( ! class_exists( 'Flavor_VBP_Block_Library' ) ) {
    die( 'Visual Builder Pro no está disponible' );
}

// Obtener bloques
$libreria = Flavor_VBP_Block_Library::get_instance();
$categorias = $libreria->get_categorias_con_bloques();

// Construir schema
$schema = array(
    'version'     => '2.1.0',
    'generated'   => date( 'c' ),
    'description' => 'Schema de bloques VBP para integración con Claude Code',
    'usage'       => array(
        'create_element' => array(
            'id'       => 'el_[random_12_chars]',
            'type'     => '[block_type]',
            'name'     => '[display_name]',
            'visible'  => true,
            'locked'   => false,
            'data'     => '{ ... campos del bloque ... }',
            'styles'   => '{ spacing, colors, typography, borders, shadows, layout, advanced }',
            'children' => array(),
        ),
    ),
    'categories'  => array(),
    'blocks'      => array(),
);

// Procesar categorías y bloques
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

        // Extraer campos
        if ( isset( $bloque['fields'] ) && is_array( $bloque['fields'] ) ) {
            foreach ( $bloque['fields'] as $field_key => $field_config ) {
                // Omitir separadores
                if ( isset( $field_config['type'] ) && 'separator' === $field_config['type'] ) {
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

        // Defaults adicionales
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

// Añadir módulos activos
$modulos_activos = get_option( 'flavor_chat_modules', array() );
$schema['active_modules'] = $modulos_activos;

// Guardar archivo
$output_path = dirname( __FILE__ ) . '/../vbp-schema.json';
$json_content = json_encode( $schema, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE );
file_put_contents( $output_path, $json_content );

// Output
if ( php_sapi_name() === 'cli' ) {
    echo "Schema exportado a: {$output_path}\n";
    echo "Total bloques: " . count( $schema['blocks'] ) . "\n";
    echo "Categorías: " . implode( ', ', array_keys( $schema['categories'] ) ) . "\n";
} else {
    header( 'Content-Type: application/json; charset=utf-8' );
    echo $json_content;
}

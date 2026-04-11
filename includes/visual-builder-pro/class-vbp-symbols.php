<?php
/**
 * Visual Builder Pro - Sistema de Símbolos con Instancias Sincronizadas
 *
 * Permite crear símbolos reutilizables que mantienen sincronización
 * entre todas sus instancias en diferentes documentos.
 *
 * @package Flavor_Platform
 * @subpackage Visual_Builder_Pro
 * @since 2.0.22
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Clase para gestionar símbolos y sus instancias
 */
class Flavor_VBP_Symbols {

    /**
     * Instancia singleton
     *
     * @var Flavor_VBP_Symbols|null
     */
    private static $instancia = null;

    /**
     * Nombre de la tabla de símbolos
     *
     * @var string
     */
    private $tabla_simbolos;

    /**
     * Nombre de la tabla de instancias
     *
     * @var string
     */
    private $tabla_instancias;

    /**
     * Categorías predefinidas para símbolos
     *
     * @var array
     */
    private $categorias_predefinidas = array(
        'headers'    => array( 'name' => 'Cabeceras', 'icon' => 'layout' ),
        'navigation' => array( 'name' => 'Navegación', 'icon' => 'compass' ),
        'footers'    => array( 'name' => 'Footers', 'icon' => 'menu' ),
        'buttons'    => array( 'name' => 'Botones', 'icon' => 'mouse-pointer' ),
        'cards'      => array( 'name' => 'Tarjetas', 'icon' => 'credit-card' ),
        'forms'      => array( 'name' => 'Formularios', 'icon' => 'edit-3' ),
        'icons'      => array( 'name' => 'Iconos', 'icon' => 'star' ),
        'custom'     => array( 'name' => 'Personalizados', 'icon' => 'folder' ),
    );

    /**
     * Obtiene la instancia singleton
     *
     * @return Flavor_VBP_Symbols
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
        global $wpdb;
        $this->tabla_simbolos   = $wpdb->prefix . 'vbp_symbols';
        $this->tabla_instancias = $wpdb->prefix . 'vbp_symbol_instances';
        $this->crear_tablas();
    }

    /**
     * Crea las tablas necesarias
     */
    private function crear_tablas() {
        global $wpdb;
        $charset_collate = $wpdb->get_charset_collate();

        $tabla_simbolos   = $this->tabla_simbolos;
        $tabla_instancias = $this->tabla_instancias;

        $sql_simbolos = "CREATE TABLE IF NOT EXISTS $tabla_simbolos (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            name varchar(255) NOT NULL,
            slug varchar(255) NOT NULL,
            description text,
            content longtext NOT NULL,
            exposed_properties text,
            thumbnail varchar(500),
            category varchar(100) NOT NULL DEFAULT 'custom',
            variants longtext,
            default_variant varchar(50) DEFAULT 'default',
            author_id bigint(20) unsigned NOT NULL,
            version int(11) NOT NULL DEFAULT 1,
            created_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            UNIQUE KEY slug (slug),
            KEY category (category),
            KEY author_id (author_id)
        ) $charset_collate;";

        // Verificar si las columnas variants y default_variant existen y agregarlas si no
        $this->maybe_add_variant_columns();

        $sql_instancias = "CREATE TABLE IF NOT EXISTS $tabla_instancias (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            symbol_id bigint(20) unsigned NOT NULL,
            document_id bigint(20) unsigned NOT NULL,
            element_id varchar(50) NOT NULL,
            variant varchar(50) DEFAULT 'default',
            overrides text,
            synced_version int(11) NOT NULL DEFAULT 1,
            created_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY symbol_id (symbol_id),
            KEY document_id (document_id),
            UNIQUE KEY document_element (document_id, element_id)
        ) $charset_collate;";

        require_once ABSPATH . 'wp-admin/includes/upgrade.php';
        dbDelta( $sql_simbolos );
        dbDelta( $sql_instancias );
    }

    /**
     * Agrega las columnas de variantes si no existen (para migraciones)
     */
    private function maybe_add_variant_columns() {
        global $wpdb;

        // Verificar columna variants en tabla de símbolos
        $column_exists = $wpdb->get_results(
            $wpdb->prepare(
                "SHOW COLUMNS FROM {$this->tabla_simbolos} LIKE %s",
                'variants'
            )
        );

        if ( empty( $column_exists ) ) {
            $wpdb->query( "ALTER TABLE {$this->tabla_simbolos} ADD COLUMN variants longtext AFTER category" );
            $wpdb->query( "ALTER TABLE {$this->tabla_simbolos} ADD COLUMN default_variant varchar(50) DEFAULT 'default' AFTER variants" );
        }

        // Verificar columna variant en tabla de instancias
        $instance_column_exists = $wpdb->get_results(
            $wpdb->prepare(
                "SHOW COLUMNS FROM {$this->tabla_instancias} LIKE %s",
                'variant'
            )
        );

        if ( empty( $instance_column_exists ) ) {
            $wpdb->query( "ALTER TABLE {$this->tabla_instancias} ADD COLUMN variant varchar(50) DEFAULT 'default' AFTER element_id" );
        }
    }

    // ========================
    // Métodos de Variantes
    // ========================

    /**
     * Obtiene las variantes de un símbolo
     *
     * @param int $symbol_id ID del símbolo.
     * @return array Variantes del símbolo.
     */
    public function get_variants( $symbol_id ) {
        $symbol = $this->get_symbol( $symbol_id );
        if ( ! $symbol ) {
            return array();
        }

        $variants = array();
        if ( ! empty( $symbol['variants'] ) ) {
            $variants = is_string( $symbol['variants'] ) ? json_decode( $symbol['variants'], true ) : $symbol['variants'];
        }

        // Asegurar que siempre exista la variante default
        if ( ! isset( $variants['default'] ) ) {
            $variants['default'] = array(
                'name'      => __( 'Por defecto', FLAVOR_PLATFORM_TEXT_DOMAIN ),
                'overrides' => array(),
            );
        }

        return $variants;
    }

    /**
     * Crear o actualizar una variante de símbolo
     *
     * @param int    $symbol_id    ID del símbolo.
     * @param string $variant_key  Clave de la variante.
     * @param array  $variant_data Datos de la variante (name, overrides).
     * @return bool|WP_Error True si se actualizó, error en caso contrario.
     */
    public function set_variant( $symbol_id, $variant_key, $variant_data ) {
        global $wpdb;

        $symbol = $this->get_symbol( $symbol_id );
        if ( ! $symbol ) {
            return new WP_Error(
                'not_found',
                __( 'Símbolo no encontrado', FLAVOR_PLATFORM_TEXT_DOMAIN )
            );
        }

        // Sanitizar clave de variante
        $variant_key = sanitize_key( $variant_key );
        if ( empty( $variant_key ) ) {
            return new WP_Error(
                'invalid_key',
                __( 'Clave de variante inválida', FLAVOR_PLATFORM_TEXT_DOMAIN )
            );
        }

        // Obtener variantes actuales
        $variants = $this->get_variants( $symbol_id );

        // Actualizar o crear variante
        $variants[ $variant_key ] = array(
            'name'      => isset( $variant_data['name'] ) ? sanitize_text_field( $variant_data['name'] ) : ucfirst( $variant_key ),
            'overrides' => isset( $variant_data['overrides'] ) ? $variant_data['overrides'] : array(),
        );

        $resultado = $wpdb->update(
            $this->tabla_simbolos,
            array( 'variants' => wp_json_encode( $variants ) ),
            array( 'id' => $symbol_id ),
            array( '%s' ),
            array( '%d' )
        );

        return $resultado !== false;
    }

    /**
     * Eliminar una variante de símbolo
     *
     * @param int    $symbol_id   ID del símbolo.
     * @param string $variant_key Clave de la variante.
     * @return bool|WP_Error True si se eliminó, error en caso contrario.
     */
    public function delete_variant( $symbol_id, $variant_key ) {
        global $wpdb;

        // No permitir eliminar la variante default
        if ( 'default' === $variant_key ) {
            return new WP_Error(
                'cannot_delete_default',
                __( 'No se puede eliminar la variante por defecto', FLAVOR_PLATFORM_TEXT_DOMAIN )
            );
        }

        $symbol = $this->get_symbol( $symbol_id );
        if ( ! $symbol ) {
            return new WP_Error(
                'not_found',
                __( 'Símbolo no encontrado', FLAVOR_PLATFORM_TEXT_DOMAIN )
            );
        }

        $variants = $this->get_variants( $symbol_id );

        if ( ! isset( $variants[ $variant_key ] ) ) {
            return new WP_Error(
                'variant_not_found',
                __( 'Variante no encontrada', FLAVOR_PLATFORM_TEXT_DOMAIN )
            );
        }

        unset( $variants[ $variant_key ] );

        $resultado = $wpdb->update(
            $this->tabla_simbolos,
            array( 'variants' => wp_json_encode( $variants ) ),
            array( 'id' => $symbol_id ),
            array( '%s' ),
            array( '%d' )
        );

        // Si alguna instancia usaba esta variante, cambiarla a default
        if ( $resultado !== false ) {
            $wpdb->update(
                $this->tabla_instancias,
                array( 'variant' => 'default' ),
                array(
                    'symbol_id' => $symbol_id,
                    'variant'   => $variant_key,
                ),
                array( '%s' ),
                array( '%d', '%s' )
            );
        }

        return $resultado !== false;
    }

    /**
     * Duplicar una variante
     *
     * @param int    $symbol_id       ID del símbolo.
     * @param string $source_key      Clave de la variante origen.
     * @param string $new_key         Nueva clave de variante.
     * @param string $new_name        Nombre de la nueva variante.
     * @return bool|WP_Error True si se duplicó, error en caso contrario.
     */
    public function duplicate_variant( $symbol_id, $source_key, $new_key, $new_name = '' ) {
        $variants = $this->get_variants( $symbol_id );

        if ( ! isset( $variants[ $source_key ] ) ) {
            return new WP_Error(
                'source_not_found',
                __( 'Variante origen no encontrada', FLAVOR_PLATFORM_TEXT_DOMAIN )
            );
        }

        $new_key = sanitize_key( $new_key );
        if ( empty( $new_key ) || isset( $variants[ $new_key ] ) ) {
            // Generar clave única
            $base_key = $new_key ?: $source_key;
            $counter  = 1;
            $new_key  = $base_key . '-' . $counter;
            while ( isset( $variants[ $new_key ] ) ) {
                $counter++;
                $new_key = $base_key . '-' . $counter;
            }
        }

        $source_variant = $variants[ $source_key ];
        $new_variant    = array(
            'name'      => $new_name ?: $source_variant['name'] . ' (copia)',
            'overrides' => $source_variant['overrides'],
        );

        return $this->set_variant( $symbol_id, $new_key, $new_variant );
    }

    /**
     * Establecer la variante por defecto de un símbolo
     *
     * @param int    $symbol_id   ID del símbolo.
     * @param string $variant_key Clave de la variante.
     * @return bool|WP_Error True si se actualizó, error en caso contrario.
     */
    public function set_default_variant( $symbol_id, $variant_key ) {
        global $wpdb;

        $variants = $this->get_variants( $symbol_id );

        if ( ! isset( $variants[ $variant_key ] ) ) {
            return new WP_Error(
                'variant_not_found',
                __( 'Variante no encontrada', FLAVOR_PLATFORM_TEXT_DOMAIN )
            );
        }

        $resultado = $wpdb->update(
            $this->tabla_simbolos,
            array( 'default_variant' => $variant_key ),
            array( 'id' => $symbol_id ),
            array( '%s' ),
            array( '%d' )
        );

        return $resultado !== false;
    }

    /**
     * Obtener el contenido del símbolo con una variante aplicada
     *
     * @param int    $symbol_id   ID del símbolo.
     * @param string $variant_key Clave de la variante (default: 'default').
     * @return array|null Contenido con la variante aplicada o null.
     */
    public function get_variant_content( $symbol_id, $variant_key = 'default' ) {
        $symbol = $this->get_symbol( $symbol_id );
        if ( ! $symbol || empty( $symbol['content'] ) ) {
            return null;
        }

        $content = $symbol['content'];

        // Si no es la variante default, aplicar overrides de la variante
        if ( 'default' !== $variant_key ) {
            $variants = $this->get_variants( $symbol_id );
            if ( isset( $variants[ $variant_key ]['overrides'] ) && ! empty( $variants[ $variant_key ]['overrides'] ) ) {
                $content = $this->aplicar_overrides( $content, $variants[ $variant_key ]['overrides'] );
            }
        }

        return $content;
    }

    /**
     * Resolver contenido de instancia con variante + overrides
     *
     * @param int    $symbol_id         ID del símbolo.
     * @param string $variant_key       Clave de la variante.
     * @param array  $instance_overrides Overrides adicionales de la instancia.
     * @return array|null Contenido final con todo aplicado.
     */
    public function resolve_instance_with_variant( $symbol_id, $variant_key, $instance_overrides = array() ) {
        // 1. Obtener contenido con variante aplicada
        $content = $this->get_variant_content( $symbol_id, $variant_key );

        if ( ! $content ) {
            return null;
        }

        // 2. Aplicar overrides de instancia sobre la variante
        if ( ! empty( $instance_overrides ) ) {
            $content = $this->aplicar_overrides( $content, $instance_overrides );
        }

        return $content;
    }

    /**
     * Cambiar la variante de una instancia
     *
     * @param int    $instance_id ID de la instancia.
     * @param string $variant_key Nueva clave de variante.
     * @return bool|WP_Error True si se cambió, error en caso contrario.
     */
    public function set_instance_variant( $instance_id, $variant_key ) {
        global $wpdb;

        $instancia = $this->get_symbol_instance( $instance_id );
        if ( ! $instancia ) {
            return new WP_Error(
                'not_found',
                __( 'Instancia no encontrada', FLAVOR_PLATFORM_TEXT_DOMAIN )
            );
        }

        // Verificar que la variante existe en el símbolo
        $variants = $this->get_variants( $instancia['symbol_id'] );
        if ( ! isset( $variants[ $variant_key ] ) ) {
            return new WP_Error(
                'variant_not_found',
                __( 'La variante no existe en el símbolo', FLAVOR_PLATFORM_TEXT_DOMAIN )
            );
        }

        $resultado = $wpdb->update(
            $this->tabla_instancias,
            array( 'variant' => sanitize_key( $variant_key ) ),
            array( 'id' => $instance_id ),
            array( '%s' ),
            array( '%d' )
        );

        return $resultado !== false;
    }

    /**
     * Crear variante desde los overrides de una instancia
     *
     * @param int    $instance_id  ID de la instancia.
     * @param string $variant_name Nombre de la nueva variante.
     * @param string $variant_key  Clave de la variante (opcional).
     * @return string|WP_Error Clave de la variante creada o error.
     */
    public function create_variant_from_instance( $instance_id, $variant_name, $variant_key = '' ) {
        $instancia = $this->get_symbol_instance( $instance_id );
        if ( ! $instancia ) {
            return new WP_Error(
                'not_found',
                __( 'Instancia no encontrada', FLAVOR_PLATFORM_TEXT_DOMAIN )
            );
        }

        // Generar clave si no se proporciona
        if ( empty( $variant_key ) ) {
            $variant_key = sanitize_key( $variant_name );
        }

        // Combinar overrides de variante actual + overrides de instancia
        $symbol_id       = $instancia['symbol_id'];
        $current_variant = isset( $instancia['variant'] ) ? $instancia['variant'] : 'default';
        $variants        = $this->get_variants( $symbol_id );

        $base_overrides     = isset( $variants[ $current_variant ]['overrides'] ) ? $variants[ $current_variant ]['overrides'] : array();
        $instance_overrides = $instancia['overrides'] ?: array();

        // Merge: primero base, luego instancia
        $merged_overrides = array_merge( $base_overrides, $instance_overrides );

        // Crear la nueva variante
        $resultado = $this->set_variant( $symbol_id, $variant_key, array(
            'name'      => $variant_name,
            'overrides' => $merged_overrides,
        ) );

        if ( is_wp_error( $resultado ) ) {
            return $resultado;
        }

        return $variant_key;
    }

    // ========================
    // Métodos de Import/Export
    // ========================

    /**
     * Exportar símbolos como array estructurado para JSON
     *
     * @param array $symbol_ids IDs de símbolos a exportar (vacío = todos).
     * @return array Datos de exportación.
     */
    public function export_symbols( $symbol_ids = array() ) {
        $simbolos_exportar = array();

        if ( empty( $symbol_ids ) ) {
            // Exportar todos
            $simbolos_exportar = $this->get_symbols( array( 'limit' => 1000 ) );
        } else {
            // Exportar específicos
            foreach ( $symbol_ids as $symbol_id ) {
                $simbolo = $this->get_symbol( $symbol_id );
                if ( $simbolo ) {
                    $simbolos_exportar[] = $simbolo;
                }
            }
        }

        $export_data = array(
            'version'     => '1.0',
            'exported_at' => gmdate( 'c' ),
            'site_url'    => get_site_url(),
            'symbols'     => array(),
        );

        foreach ( $simbolos_exportar as $simbolo ) {
            $export_data['symbols'][] = array(
                'name'                => $simbolo['name'],
                'slug'                => $simbolo['slug'],
                'category'            => $simbolo['category'],
                'description'         => isset( $simbolo['description'] ) ? $simbolo['description'] : '',
                'content'             => $simbolo['content'],
                'variants'            => isset( $simbolo['variants'] ) ? $simbolo['variants'] : array(),
                'default_variant'     => isset( $simbolo['default_variant'] ) ? $simbolo['default_variant'] : 'default',
                'exposed_properties'  => isset( $simbolo['exposed_properties'] ) ? $simbolo['exposed_properties'] : array(),
                'thumbnail'           => isset( $simbolo['thumbnail'] ) ? $simbolo['thumbnail'] : '',
            );
        }

        return $export_data;
    }

    /**
     * Importar símbolos desde datos estructurados
     *
     * @param array  $symbols_data Array de símbolos a importar.
     * @param string $mode         'merge' (no sobrescribe existentes) o 'replace' (sobrescribe).
     * @return array Resultado de la importación.
     */
    public function import_symbols( $symbols_data, $mode = 'merge' ) {
        if ( empty( $symbols_data ) || ! is_array( $symbols_data ) ) {
            return array(
                'success'  => false,
                'error'    => __( 'No hay símbolos para importar', FLAVOR_PLATFORM_TEXT_DOMAIN ),
                'imported' => 0,
                'skipped'  => 0,
                'updated'  => 0,
                'errors'   => array(),
            );
        }

        $imported_count = 0;
        $skipped_count  = 0;
        $updated_count  = 0;
        $import_errors  = array();
        $imported_ids   = array();

        foreach ( $symbols_data as $symbol_index => $symbol_data ) {
            // Validar datos mínimos
            if ( empty( $symbol_data['name'] ) || empty( $symbol_data['content'] ) ) {
                $import_errors[] = sprintf(
                    /* translators: %d: symbol index */
                    __( 'Símbolo #%d: nombre y contenido son requeridos', FLAVOR_PLATFORM_TEXT_DOMAIN ),
                    $symbol_index + 1
                );
                continue;
            }

            $symbol_slug = ! empty( $symbol_data['slug'] ) ? sanitize_title( $symbol_data['slug'] ) : sanitize_title( $symbol_data['name'] );
            $existing    = $this->get_symbol_by_slug( $symbol_slug );

            if ( $existing ) {
                if ( 'replace' === $mode ) {
                    // Actualizar símbolo existente
                    $update_data = array(
                        'name'                => sanitize_text_field( $symbol_data['name'] ),
                        'content'             => $symbol_data['content'],
                        'description'         => isset( $symbol_data['description'] ) ? sanitize_textarea_field( $symbol_data['description'] ) : '',
                        'category'            => isset( $symbol_data['category'] ) ? sanitize_text_field( $symbol_data['category'] ) : 'custom',
                        'exposed_properties'  => isset( $symbol_data['exposed_properties'] ) ? $symbol_data['exposed_properties'] : array(),
                    );

                    $resultado = $this->update_symbol( $existing['id'], $update_data );

                    if ( is_wp_error( $resultado ) ) {
                        $import_errors[] = sprintf(
                            /* translators: %s: symbol name */
                            __( 'Error actualizando "%s": %s', FLAVOR_PLATFORM_TEXT_DOMAIN ),
                            $symbol_data['name'],
                            $resultado->get_error_message()
                        );
                    } else {
                        // Importar variantes si existen
                        if ( ! empty( $symbol_data['variants'] ) ) {
                            $this->import_symbol_variants( $existing['id'], $symbol_data['variants'], $symbol_data['default_variant'] ?? 'default' );
                        }

                        $updated_count++;
                        $imported_ids[] = $existing['id'];
                    }
                } else {
                    // Modo merge: saltar existentes
                    $skipped_count++;
                }
            } else {
                // Crear nuevo símbolo
                $options = array(
                    'slug'                => $symbol_slug,
                    'description'         => isset( $symbol_data['description'] ) ? $symbol_data['description'] : '',
                    'category'            => isset( $symbol_data['category'] ) ? $symbol_data['category'] : 'custom',
                    'thumbnail'           => isset( $symbol_data['thumbnail'] ) ? $symbol_data['thumbnail'] : '',
                    'exposed_properties'  => isset( $symbol_data['exposed_properties'] ) ? $symbol_data['exposed_properties'] : array(),
                );

                $new_id = $this->create_symbol(
                    sanitize_text_field( $symbol_data['name'] ),
                    $symbol_data['content'],
                    $options
                );

                if ( is_wp_error( $new_id ) ) {
                    $import_errors[] = sprintf(
                        /* translators: %s: symbol name */
                        __( 'Error creando "%s": %s', FLAVOR_PLATFORM_TEXT_DOMAIN ),
                        $symbol_data['name'],
                        $new_id->get_error_message()
                    );
                } else {
                    // Importar variantes si existen
                    if ( ! empty( $symbol_data['variants'] ) ) {
                        $this->import_symbol_variants( $new_id, $symbol_data['variants'], $symbol_data['default_variant'] ?? 'default' );
                    }

                    $imported_count++;
                    $imported_ids[] = $new_id;
                }
            }
        }

        return array(
            'success'      => ( $imported_count + $updated_count ) > 0 || empty( $import_errors ),
            'imported'     => $imported_count,
            'updated'      => $updated_count,
            'skipped'      => $skipped_count,
            'errors'       => $import_errors,
            'imported_ids' => $imported_ids,
            'message'      => sprintf(
                /* translators: %1$d: imported count, %2$d: updated count, %3$d: skipped count */
                __( 'Importación completada: %1$d creados, %2$d actualizados, %3$d omitidos', FLAVOR_PLATFORM_TEXT_DOMAIN ),
                $imported_count,
                $updated_count,
                $skipped_count
            ),
        );
    }

    /**
     * Importar variantes de un símbolo
     *
     * @param int    $symbol_id       ID del símbolo.
     * @param array  $variants        Variantes a importar.
     * @param string $default_variant Variante por defecto.
     */
    private function import_symbol_variants( $symbol_id, $variants, $default_variant = 'default' ) {
        if ( empty( $variants ) || ! is_array( $variants ) ) {
            return;
        }

        foreach ( $variants as $variant_key => $variant_data ) {
            // Saltar la variante default ya que se crea automáticamente
            if ( 'default' === $variant_key && empty( $variant_data['overrides'] ) ) {
                continue;
            }

            $this->set_variant( $symbol_id, $variant_key, array(
                'name'      => isset( $variant_data['name'] ) ? $variant_data['name'] : ucfirst( $variant_key ),
                'overrides' => isset( $variant_data['overrides'] ) ? $variant_data['overrides'] : array(),
            ) );
        }

        // Establecer variante por defecto si es diferente
        if ( 'default' !== $default_variant ) {
            $this->set_default_variant( $symbol_id, $default_variant );
        }
    }

    /**
     * Validar estructura de datos de importación
     *
     * @param array $import_data Datos a validar.
     * @return array Resultado de validación con 'valid' y 'errors'.
     */
    public function validate_import_data( $import_data ) {
        $validation_errors = array();

        // Verificar estructura básica
        if ( ! is_array( $import_data ) ) {
            return array(
                'valid'  => false,
                'errors' => array( __( 'Formato de datos inválido', FLAVOR_PLATFORM_TEXT_DOMAIN ) ),
            );
        }

        // Verificar array de símbolos
        if ( ! isset( $import_data['symbols'] ) || ! is_array( $import_data['symbols'] ) ) {
            return array(
                'valid'  => false,
                'errors' => array( __( 'El archivo no contiene símbolos válidos', FLAVOR_PLATFORM_TEXT_DOMAIN ) ),
            );
        }

        // Validar cada símbolo
        foreach ( $import_data['symbols'] as $index => $symbol ) {
            $symbol_number = $index + 1;

            if ( empty( $symbol['name'] ) ) {
                $validation_errors[] = sprintf(
                    /* translators: %d: symbol number */
                    __( 'Símbolo #%d: falta el nombre', FLAVOR_PLATFORM_TEXT_DOMAIN ),
                    $symbol_number
                );
            }

            if ( empty( $symbol['content'] ) ) {
                $validation_errors[] = sprintf(
                    /* translators: %d: symbol number */
                    __( 'Símbolo #%d: falta el contenido', FLAVOR_PLATFORM_TEXT_DOMAIN ),
                    $symbol_number
                );
            }

            if ( isset( $symbol['content'] ) && ! is_array( $symbol['content'] ) ) {
                $validation_errors[] = sprintf(
                    /* translators: %d: symbol number */
                    __( 'Símbolo #%d: el contenido debe ser un array', FLAVOR_PLATFORM_TEXT_DOMAIN ),
                    $symbol_number
                );
            }
        }

        return array(
            'valid'        => empty( $validation_errors ),
            'errors'       => $validation_errors,
            'symbol_count' => count( $import_data['symbols'] ),
            'version'      => isset( $import_data['version'] ) ? $import_data['version'] : 'unknown',
        );
    }

    /**
     * Crea un nuevo símbolo
     *
     * @param string $name    Nombre del símbolo.
     * @param array  $content Contenido del símbolo (bloques).
     * @param array  $options Opciones adicionales.
     * @return int|WP_Error ID del símbolo creado o error.
     */
    public function create_symbol( $name, $content, $options = array() ) {
        global $wpdb;

        if ( empty( $name ) || empty( $content ) ) {
            return new WP_Error(
                'invalid_data',
                __( 'Nombre y contenido son requeridos', FLAVOR_PLATFORM_TEXT_DOMAIN )
            );
        }

        // Usar slug de opciones si se proporciona, sino generar desde el nombre
        $slug_base       = ! empty( $options['slug'] ) ? sanitize_title( $options['slug'] ) : sanitize_title( $name );
        $slug_unico      = $this->generar_slug_unico( $slug_base );
        $descripcion     = isset( $options['description'] ) ? sanitize_textarea_field( $options['description'] ) : '';
        $thumbnail       = isset( $options['thumbnail'] ) ? esc_url_raw( $options['thumbnail'] ) : '';
        $category        = isset( $options['category'] ) ? sanitize_text_field( $options['category'] ) : 'custom';
        $exposed_props   = isset( $options['exposed_properties'] ) ? $options['exposed_properties'] : array();

        if ( ! isset( $this->categorias_predefinidas[ $category ] ) ) {
            $category = 'custom';
        }

        $resultado = $wpdb->insert(
            $this->tabla_simbolos,
            array(
                'name'                => sanitize_text_field( $name ),
                'slug'                => $slug_unico,
                'description'         => $descripcion,
                'content'             => wp_json_encode( $content ),
                'exposed_properties'  => wp_json_encode( $exposed_props ),
                'thumbnail'           => $thumbnail,
                'category'            => $category,
                'author_id'           => get_current_user_id(),
                'version'             => 1,
            ),
            array( '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%d', '%d' )
        );

        if ( false === $resultado ) {
            return new WP_Error(
                'db_error',
                __( 'Error al crear el símbolo', FLAVOR_PLATFORM_TEXT_DOMAIN )
            );
        }

        return $wpdb->insert_id;
    }

    /**
     * Genera un slug único para el símbolo
     *
     * @param string $slug_base Slug base.
     * @return string Slug único.
     */
    private function generar_slug_unico( $slug_base ) {
        global $wpdb;
        $tabla = $this->tabla_simbolos;
        $slug  = $slug_base;
        $contador = 1;

        while ( $wpdb->get_var( $wpdb->prepare( "SELECT id FROM $tabla WHERE slug = %s", $slug ) ) ) {
            $slug = $slug_base . '-' . $contador;
            $contador++;
        }

        return $slug;
    }

    /**
     * Obtiene un símbolo por ID
     *
     * @param int $symbol_id ID del símbolo.
     * @return array|null Datos del símbolo o null.
     */
    public function get_symbol( $symbol_id ) {
        global $wpdb;
        $tabla = $this->tabla_simbolos;

        $simbolo = $wpdb->get_row(
            $wpdb->prepare( "SELECT * FROM $tabla WHERE id = %d", $symbol_id ),
            ARRAY_A
        );

        if ( ! $simbolo ) {
            return null;
        }

        $simbolo['content']             = json_decode( $simbolo['content'], true );
        $simbolo['exposed_properties']  = json_decode( $simbolo['exposed_properties'], true );
        $simbolo['variants']            = ! empty( $simbolo['variants'] ) ? json_decode( $simbolo['variants'], true ) : array();
        $simbolo['default_variant']     = ! empty( $simbolo['default_variant'] ) ? $simbolo['default_variant'] : 'default';
        $simbolo['author_name']         = get_the_author_meta( 'display_name', $simbolo['author_id'] );
        $simbolo['instance_count']      = $this->get_instance_count( $symbol_id );

        // Asegurar variante default siempre existe
        if ( ! isset( $simbolo['variants']['default'] ) ) {
            $simbolo['variants']['default'] = array(
                'name'      => __( 'Por defecto', FLAVOR_PLATFORM_TEXT_DOMAIN ),
                'overrides' => array(),
            );
        }

        return $simbolo;
    }

    /**
     * Obtiene un símbolo por slug
     *
     * @param string $slug Slug del símbolo.
     * @return array|null Datos del símbolo o null.
     */
    public function get_symbol_by_slug( $slug ) {
        global $wpdb;
        $tabla = $this->tabla_simbolos;

        $symbol_id = $wpdb->get_var(
            $wpdb->prepare( "SELECT id FROM $tabla WHERE slug = %s", $slug )
        );

        if ( ! $symbol_id ) {
            return null;
        }

        return $this->get_symbol( $symbol_id );
    }

    /**
     * Obtiene todos los símbolos
     *
     * @param array $args Argumentos de filtrado.
     * @return array Lista de símbolos.
     */
    public function get_symbols( $args = array() ) {
        global $wpdb;

        $defaults = array(
            'category'  => null,
            'author_id' => null,
            'search'    => null,
            'orderby'   => 'updated_at',
            'order'     => 'DESC',
            'limit'     => 50,
            'offset'    => 0,
        );

        $args = wp_parse_args( $args, $defaults );
        $where_clauses = array( '1=1' );
        $where_values  = array();

        if ( ! empty( $args['category'] ) ) {
            $where_clauses[] = 'category = %s';
            $where_values[]  = $args['category'];
        }

        if ( ! empty( $args['author_id'] ) ) {
            $where_clauses[] = 'author_id = %d';
            $where_values[]  = $args['author_id'];
        }

        if ( ! empty( $args['search'] ) ) {
            $termino_busqueda = '%' . $wpdb->esc_like( $args['search'] ) . '%';
            $where_clauses[]  = '(name LIKE %s OR description LIKE %s)';
            $where_values[]   = $termino_busqueda;
            $where_values[]   = $termino_busqueda;
        }

        $where_sql        = implode( ' AND ', $where_clauses );
        $columnas_validas = array( 'name', 'category', 'created_at', 'updated_at', 'version' );
        $orderby          = in_array( $args['orderby'], $columnas_validas, true ) ? $args['orderby'] : 'updated_at';
        $order            = strtoupper( $args['order'] ) === 'ASC' ? 'ASC' : 'DESC';
        $tabla            = $this->tabla_simbolos;

        $where_values[] = $args['limit'];
        $where_values[] = $args['offset'];

        $query      = "SELECT * FROM $tabla WHERE $where_sql ORDER BY $orderby $order LIMIT %d OFFSET %d";
        $resultados = $wpdb->get_results( $wpdb->prepare( $query, $where_values ), ARRAY_A );

        foreach ( $resultados as &$simbolo ) {
            $simbolo['content']            = json_decode( $simbolo['content'], true );
            $simbolo['exposed_properties'] = json_decode( $simbolo['exposed_properties'], true );
            $simbolo['variants']           = ! empty( $simbolo['variants'] ) ? json_decode( $simbolo['variants'], true ) : array();
            $simbolo['default_variant']    = ! empty( $simbolo['default_variant'] ) ? $simbolo['default_variant'] : 'default';
            $simbolo['author_name']        = get_the_author_meta( 'display_name', $simbolo['author_id'] );
            $simbolo['instance_count']     = $this->get_instance_count( $simbolo['id'] );

            // Asegurar variante default siempre existe
            if ( ! isset( $simbolo['variants']['default'] ) ) {
                $simbolo['variants']['default'] = array(
                    'name'      => __( 'Por defecto', FLAVOR_PLATFORM_TEXT_DOMAIN ),
                    'overrides' => array(),
                );
            }
        }

        return $resultados;
    }

    /**
     * Actualiza un símbolo
     *
     * @param int   $symbol_id ID del símbolo.
     * @param array $data      Datos a actualizar.
     * @return bool|WP_Error True si se actualizó, error en caso contrario.
     */
    public function update_symbol( $symbol_id, $data ) {
        global $wpdb;

        $simbolo_actual = $this->get_symbol( $symbol_id );
        if ( ! $simbolo_actual ) {
            return new WP_Error(
                'not_found',
                __( 'Símbolo no encontrado', FLAVOR_PLATFORM_TEXT_DOMAIN )
            );
        }

        if ( $simbolo_actual['author_id'] != get_current_user_id() && ! current_user_can( 'manage_options' ) ) {
            return new WP_Error(
                'forbidden',
                __( 'No tienes permisos para editar este símbolo', FLAVOR_PLATFORM_TEXT_DOMAIN )
            );
        }

        $campos             = array();
        $tipos              = array();
        $incrementar_version = false;

        if ( isset( $data['name'] ) ) {
            $campos['name'] = sanitize_text_field( $data['name'] );
            $tipos[]        = '%s';
        }

        if ( isset( $data['description'] ) ) {
            $campos['description'] = sanitize_textarea_field( $data['description'] );
            $tipos[]               = '%s';
        }

        if ( isset( $data['content'] ) ) {
            $campos['content'] = wp_json_encode( $data['content'] );
            $tipos[]           = '%s';
            $incrementar_version = true;
        }

        if ( isset( $data['exposed_properties'] ) ) {
            $campos['exposed_properties'] = wp_json_encode( $data['exposed_properties'] );
            $tipos[]                      = '%s';
        }

        if ( isset( $data['thumbnail'] ) ) {
            $campos['thumbnail'] = esc_url_raw( $data['thumbnail'] );
            $tipos[]             = '%s';
        }

        if ( isset( $data['category'] ) ) {
            $category           = isset( $this->categorias_predefinidas[ $data['category'] ] ) ? $data['category'] : 'custom';
            $campos['category'] = $category;
            $tipos[]            = '%s';
        }

        if ( empty( $campos ) ) {
            return new WP_Error(
                'no_data',
                __( 'No hay datos para actualizar', FLAVOR_PLATFORM_TEXT_DOMAIN )
            );
        }

        if ( $incrementar_version ) {
            $campos['version'] = $simbolo_actual['version'] + 1;
            $tipos[]           = '%d';
        }

        $resultado = $wpdb->update(
            $this->tabla_simbolos,
            $campos,
            array( 'id' => $symbol_id ),
            $tipos,
            array( '%d' )
        );

        return $resultado !== false;
    }

    /**
     * Elimina un símbolo y todas sus instancias
     *
     * @param int $symbol_id ID del símbolo.
     * @return bool|WP_Error True si se eliminó, error en caso contrario.
     */
    public function delete_symbol( $symbol_id ) {
        global $wpdb;

        $simbolo = $this->get_symbol( $symbol_id );
        if ( ! $simbolo ) {
            return new WP_Error(
                'not_found',
                __( 'Símbolo no encontrado', FLAVOR_PLATFORM_TEXT_DOMAIN )
            );
        }

        if ( $simbolo['author_id'] != get_current_user_id() && ! current_user_can( 'manage_options' ) ) {
            return new WP_Error(
                'forbidden',
                __( 'No tienes permisos para eliminar este símbolo', FLAVOR_PLATFORM_TEXT_DOMAIN )
            );
        }

        // Eliminar todas las instancias primero
        $wpdb->delete(
            $this->tabla_instancias,
            array( 'symbol_id' => $symbol_id ),
            array( '%d' )
        );

        // Eliminar el símbolo
        $resultado = $wpdb->delete(
            $this->tabla_simbolos,
            array( 'id' => $symbol_id ),
            array( '%d' )
        );

        return $resultado !== false;
    }

    /**
     * Obtiene las categorías disponibles
     *
     * @return array Lista de categorías con conteo.
     */
    public function get_categories() {
        global $wpdb;
        $tabla   = $this->tabla_simbolos;
        $conteos = $wpdb->get_results( "SELECT category, COUNT(*) as count FROM $tabla GROUP BY category", OBJECT_K );

        $categorias = array();
        foreach ( $this->categorias_predefinidas as $id => $categoria ) {
            $categorias[] = array(
                'id'    => $id,
                'name'  => $categoria['name'],
                'icon'  => $categoria['icon'],
                'count' => isset( $conteos[ $id ] ) ? (int) $conteos[ $id ]->count : 0,
            );
        }

        return $categorias;
    }

    // ========================
    // Métodos de Instancias
    // ========================

    /**
     * Registra una nueva instancia de símbolo
     *
     * @param int    $symbol_id   ID del símbolo.
     * @param int    $document_id ID del documento (post_id).
     * @param string $element_id  ID del elemento en el canvas.
     * @param string $variant     Variante inicial (opcional, default: 'default' o la variante por defecto del símbolo).
     * @return int|WP_Error ID de la instancia creada o error.
     */
    public function register_instance( $symbol_id, $document_id, $element_id, $variant = '' ) {
        global $wpdb;

        $simbolo = $this->get_symbol( $symbol_id );
        if ( ! $simbolo ) {
            return new WP_Error(
                'symbol_not_found',
                __( 'Símbolo no encontrado', FLAVOR_PLATFORM_TEXT_DOMAIN )
            );
        }

        // Verificar si ya existe la instancia
        $existente = $wpdb->get_var(
            $wpdb->prepare(
                "SELECT id FROM {$this->tabla_instancias} WHERE document_id = %d AND element_id = %s",
                $document_id,
                $element_id
            )
        );

        if ( $existente ) {
            return new WP_Error(
                'instance_exists',
                __( 'Ya existe una instancia con ese element_id en el documento', FLAVOR_PLATFORM_TEXT_DOMAIN )
            );
        }

        // Determinar variante inicial
        if ( empty( $variant ) ) {
            $variant = ! empty( $simbolo['default_variant'] ) ? $simbolo['default_variant'] : 'default';
        }

        $resultado = $wpdb->insert(
            $this->tabla_instancias,
            array(
                'symbol_id'      => $symbol_id,
                'document_id'    => $document_id,
                'element_id'     => sanitize_text_field( $element_id ),
                'variant'        => sanitize_key( $variant ),
                'overrides'      => wp_json_encode( array() ),
                'synced_version' => $simbolo['version'],
            ),
            array( '%d', '%d', '%s', '%s', '%s', '%d' )
        );

        if ( false === $resultado ) {
            return new WP_Error(
                'db_error',
                __( 'Error al registrar la instancia', FLAVOR_PLATFORM_TEXT_DOMAIN )
            );
        }

        return $wpdb->insert_id;
    }

    /**
     * Obtiene una instancia de símbolo por ID
     *
     * @param int $instance_id ID de la instancia.
     * @return array|null Datos de la instancia o null.
     */
    public function get_symbol_instance( $instance_id ) {
        global $wpdb;

        $instancia = $wpdb->get_row(
            $wpdb->prepare(
                "SELECT * FROM {$this->tabla_instancias} WHERE id = %d",
                $instance_id
            ),
            ARRAY_A
        );

        if ( ! $instancia ) {
            return null;
        }

        $instancia['overrides'] = json_decode( $instancia['overrides'], true );
        $instancia['variant']   = ! empty( $instancia['variant'] ) ? $instancia['variant'] : 'default';

        // Agregar info del símbolo
        $simbolo = $this->get_symbol( $instancia['symbol_id'] );
        if ( $simbolo ) {
            $instancia['symbol_name']      = $simbolo['name'];
            $instancia['symbol_version']   = $simbolo['version'];
            $instancia['needs_sync']       = $instancia['synced_version'] < $simbolo['version'];
            $instancia['available_variants'] = array_keys( $simbolo['variants'] );
        }

        return $instancia;
    }

    /**
     * Obtiene todas las instancias de un símbolo
     *
     * @param int $symbol_id ID del símbolo.
     * @return array Lista de instancias.
     */
    public function get_symbol_instances( $symbol_id ) {
        global $wpdb;

        $resultados = $wpdb->get_results(
            $wpdb->prepare(
                "SELECT i.*, p.post_title as document_title
                 FROM {$this->tabla_instancias} i
                 LEFT JOIN {$wpdb->posts} p ON i.document_id = p.ID
                 WHERE i.symbol_id = %d
                 ORDER BY i.created_at DESC",
                $symbol_id
            ),
            ARRAY_A
        );

        $simbolo = $this->get_symbol( $symbol_id );

        foreach ( $resultados as &$instancia ) {
            $instancia['overrides']  = json_decode( $instancia['overrides'], true );
            $instancia['needs_sync'] = $simbolo && $instancia['synced_version'] < $simbolo['version'];
        }

        return $resultados;
    }

    /**
     * Obtiene todas las instancias de un documento
     *
     * @param int $document_id ID del documento.
     * @return array Lista de instancias.
     */
    public function get_document_instances( $document_id ) {
        global $wpdb;

        $resultados = $wpdb->get_results(
            $wpdb->prepare(
                "SELECT i.*, s.name as symbol_name, s.version as symbol_version
                 FROM {$this->tabla_instancias} i
                 LEFT JOIN {$this->tabla_simbolos} s ON i.symbol_id = s.id
                 WHERE i.document_id = %d
                 ORDER BY i.created_at ASC",
                $document_id
            ),
            ARRAY_A
        );

        foreach ( $resultados as &$instancia ) {
            $instancia['overrides']  = json_decode( $instancia['overrides'], true );
            $instancia['needs_sync'] = $instancia['synced_version'] < $instancia['symbol_version'];
        }

        return $resultados;
    }

    /**
     * Obtiene el número de instancias de un símbolo
     *
     * @param int $symbol_id ID del símbolo.
     * @return int Número de instancias.
     */
    public function get_instance_count( $symbol_id ) {
        global $wpdb;
        return (int) $wpdb->get_var(
            $wpdb->prepare(
                "SELECT COUNT(*) FROM {$this->tabla_instancias} WHERE symbol_id = %d",
                $symbol_id
            )
        );
    }

    /**
     * Actualiza los overrides de una instancia
     *
     * @param int   $instance_id ID de la instancia.
     * @param array $overrides   Propiedades sobreescritas.
     * @return bool|WP_Error True si se actualizó, error en caso contrario.
     */
    public function update_instance_overrides( $instance_id, $overrides ) {
        global $wpdb;

        $instancia = $this->get_symbol_instance( $instance_id );
        if ( ! $instancia ) {
            return new WP_Error(
                'not_found',
                __( 'Instancia no encontrada', FLAVOR_PLATFORM_TEXT_DOMAIN )
            );
        }

        $resultado = $wpdb->update(
            $this->tabla_instancias,
            array( 'overrides' => wp_json_encode( $overrides ) ),
            array( 'id' => $instance_id ),
            array( '%s' ),
            array( '%d' )
        );

        return $resultado !== false;
    }

    /**
     * Sincroniza todas las instancias de un símbolo a la versión actual
     *
     * @param int $symbol_id ID del símbolo.
     * @return int|WP_Error Número de instancias actualizadas o error.
     */
    public function sync_instances( $symbol_id ) {
        global $wpdb;

        $simbolo = $this->get_symbol( $symbol_id );
        if ( ! $simbolo ) {
            return new WP_Error(
                'not_found',
                __( 'Símbolo no encontrado', FLAVOR_PLATFORM_TEXT_DOMAIN )
            );
        }

        $resultado = $wpdb->update(
            $this->tabla_instancias,
            array( 'synced_version' => $simbolo['version'] ),
            array( 'symbol_id' => $symbol_id ),
            array( '%d' ),
            array( '%d' )
        );

        return $resultado !== false ? $resultado : 0;
    }

    /**
     * Sincroniza una instancia específica a la versión actual del símbolo
     *
     * @param int $instance_id ID de la instancia.
     * @return bool|WP_Error True si se sincronizó, error en caso contrario.
     */
    public function sync_instance( $instance_id ) {
        global $wpdb;

        $instancia = $this->get_symbol_instance( $instance_id );
        if ( ! $instancia ) {
            return new WP_Error(
                'not_found',
                __( 'Instancia no encontrada', FLAVOR_PLATFORM_TEXT_DOMAIN )
            );
        }

        $simbolo = $this->get_symbol( $instancia['symbol_id'] );
        if ( ! $simbolo ) {
            return new WP_Error(
                'symbol_not_found',
                __( 'Símbolo asociado no encontrado', FLAVOR_PLATFORM_TEXT_DOMAIN )
            );
        }

        $resultado = $wpdb->update(
            $this->tabla_instancias,
            array( 'synced_version' => $simbolo['version'] ),
            array( 'id' => $instance_id ),
            array( '%d' ),
            array( '%d' )
        );

        return $resultado !== false;
    }

    /**
     * Desvincula una instancia del símbolo (detach)
     * Convierte la instancia en bloques independientes
     *
     * @param int $instance_id ID de la instancia.
     * @return array|WP_Error Contenido del símbolo con overrides aplicados o error.
     */
    public function detach_instance( $instance_id ) {
        global $wpdb;

        $instancia = $this->get_symbol_instance( $instance_id );
        if ( ! $instancia ) {
            return new WP_Error(
                'not_found',
                __( 'Instancia no encontrada', FLAVOR_PLATFORM_TEXT_DOMAIN )
            );
        }

        $simbolo = $this->get_symbol( $instancia['symbol_id'] );
        if ( ! $simbolo ) {
            return new WP_Error(
                'symbol_not_found',
                __( 'Símbolo asociado no encontrado', FLAVOR_PLATFORM_TEXT_DOMAIN )
            );
        }

        // Aplicar overrides al contenido del símbolo
        $contenido_detached = $this->aplicar_overrides( $simbolo['content'], $instancia['overrides'] );

        // Eliminar la instancia
        $wpdb->delete(
            $this->tabla_instancias,
            array( 'id' => $instance_id ),
            array( '%d' )
        );

        return array(
            'content'     => $contenido_detached,
            'document_id' => $instancia['document_id'],
            'element_id'  => $instancia['element_id'],
        );
    }

    /**
     * Elimina una instancia
     *
     * @param int $instance_id ID de la instancia.
     * @return bool|WP_Error True si se eliminó, error en caso contrario.
     */
    public function delete_instance( $instance_id ) {
        global $wpdb;

        $instancia = $this->get_symbol_instance( $instance_id );
        if ( ! $instancia ) {
            return new WP_Error(
                'not_found',
                __( 'Instancia no encontrada', FLAVOR_PLATFORM_TEXT_DOMAIN )
            );
        }

        $resultado = $wpdb->delete(
            $this->tabla_instancias,
            array( 'id' => $instance_id ),
            array( '%d' )
        );

        return $resultado !== false;
    }

    /**
     * Aplica los overrides al contenido del símbolo
     *
     * @param array $content   Contenido original del símbolo.
     * @param array $overrides Propiedades sobreescritas.
     * @return array Contenido con overrides aplicados.
     */
    private function aplicar_overrides( $content, $overrides ) {
        if ( empty( $overrides ) || ! is_array( $overrides ) ) {
            return $content;
        }

        // Aplicar overrides recursivamente
        return $this->aplicar_overrides_recursivo( $content, $overrides );
    }

    /**
     * Aplica overrides de forma recursiva
     *
     * @param mixed $elemento  Elemento a procesar.
     * @param array $overrides Overrides a aplicar.
     * @return mixed Elemento procesado.
     */
    private function aplicar_overrides_recursivo( $elemento, $overrides ) {
        if ( ! is_array( $elemento ) ) {
            return $elemento;
        }

        // Si el elemento tiene un ID que coincide con un override
        if ( isset( $elemento['id'] ) && isset( $overrides[ $elemento['id'] ] ) ) {
            $override_props = $overrides[ $elemento['id'] ];
            if ( isset( $elemento['props'] ) && is_array( $override_props ) ) {
                $elemento['props'] = array_merge( $elemento['props'], $override_props );
            }
        }

        // Procesar hijos recursivamente
        if ( isset( $elemento['children'] ) && is_array( $elemento['children'] ) ) {
            foreach ( $elemento['children'] as $indice => $hijo ) {
                $elemento['children'][ $indice ] = $this->aplicar_overrides_recursivo( $hijo, $overrides );
            }
        }

        // Procesar bloques si es un array de bloques
        foreach ( $elemento as $clave => $valor ) {
            if ( is_array( $valor ) && ! in_array( $clave, array( 'props', 'style', 'overrides' ), true ) ) {
                $elemento[ $clave ] = $this->aplicar_overrides_recursivo( $valor, $overrides );
            }
        }

        return $elemento;
    }

    /**
     * Obtiene el contenido renderizado de una instancia
     * Aplica: contenido base -> variante -> overrides de instancia
     *
     * @param int $instance_id ID de la instancia.
     * @return array|WP_Error Contenido con overrides aplicados o error.
     */
    public function get_instance_content( $instance_id ) {
        $instancia = $this->get_symbol_instance( $instance_id );
        if ( ! $instancia ) {
            return new WP_Error(
                'not_found',
                __( 'Instancia no encontrada', FLAVOR_PLATFORM_TEXT_DOMAIN )
            );
        }

        $simbolo = $this->get_symbol( $instancia['symbol_id'] );
        if ( ! $simbolo ) {
            return new WP_Error(
                'symbol_not_found',
                __( 'Símbolo asociado no encontrado', FLAVOR_PLATFORM_TEXT_DOMAIN )
            );
        }

        // Usar el método que aplica variante + overrides
        $variant_key = isset( $instancia['variant'] ) ? $instancia['variant'] : 'default';
        return $this->resolve_instance_with_variant(
            $instancia['symbol_id'],
            $variant_key,
            $instancia['overrides']
        );
    }

    // ========================
    // Métodos de Swap Instance
    // ========================

    /**
     * Calcular compatibilidad de overrides entre dos símbolos
     * Retorna qué propiedades son compatibles y cuáles no
     *
     * @param int   $source_symbol_id ID del símbolo fuente.
     * @param int   $target_symbol_id ID del símbolo destino.
     * @param array $overrides        Overrides actuales de la instancia.
     * @return array Resultado con propiedades compatibles e incompatibles.
     */
    public function calculate_override_compatibility( $source_symbol_id, $target_symbol_id, $overrides ) {
        $source_symbol = $this->get_symbol( $source_symbol_id );
        $target_symbol = $this->get_symbol( $target_symbol_id );

        if ( ! $source_symbol || ! $target_symbol ) {
            return array(
                'compatible'          => array(),
                'incompatible'        => array_keys( $overrides ),
                'compatibility_score' => 0,
            );
        }

        // Obtener propiedades expuestas del target
        $target_exposed = is_array( $target_symbol['exposed_properties'] )
            ? $target_symbol['exposed_properties']
            : array();

        $target_content = $target_symbol['content'];

        $compatible_overrides   = array();
        $incompatible_overrides = array();

        foreach ( $overrides as $override_path => $override_value ) {
            // Verificar si el path existe en el contenido del target o está expuesto
            $path_exists = $this->path_exists_in_content( $target_content, $override_path )
                || in_array( $override_path, $target_exposed, true );

            if ( $path_exists ) {
                $compatible_overrides[ $override_path ] = $override_value;
            } else {
                $incompatible_overrides[] = $override_path;
            }
        }

        $total_overrides      = count( $overrides );
        $compatibility_score  = $total_overrides > 0
            ? ( count( $compatible_overrides ) / $total_overrides ) * 100
            : 100;

        return array(
            'compatible'          => $compatible_overrides,
            'incompatible'        => $incompatible_overrides,
            'compatibility_score' => round( $compatibility_score, 2 ),
        );
    }

    /**
     * Verificar si un path existe en el contenido del símbolo
     *
     * @param mixed  $content Contenido del símbolo.
     * @param string $path    Path a verificar (ej: "children.0.props.text").
     * @return bool True si el path existe.
     */
    private function path_exists_in_content( $content, $path ) {
        $path_parts    = explode( '.', $path );
        $current_level = $content;

        foreach ( $path_parts as $path_part ) {
            if ( is_array( $current_level ) ) {
                // Verificar si es un índice numérico
                if ( is_numeric( $path_part ) ) {
                    $numeric_index = (int) $path_part;
                    if ( isset( $current_level[ $numeric_index ] ) ) {
                        $current_level = $current_level[ $numeric_index ];
                        continue;
                    }
                }

                // Verificar si la clave existe directamente
                if ( isset( $current_level[ $path_part ] ) ) {
                    $current_level = $current_level[ $path_part ];
                    continue;
                }

                // Buscar en elementos del array (para estructuras tipo children)
                foreach ( $current_level as $array_element ) {
                    if ( is_array( $array_element ) && isset( $array_element[ $path_part ] ) ) {
                        $current_level = $array_element[ $path_part ];
                        continue 2;
                    }
                    // Verificar por ID del elemento
                    if ( is_array( $array_element ) && isset( $array_element['id'] ) && $array_element['id'] === $path_part ) {
                        $current_level = $array_element;
                        continue 2;
                    }
                }

                return false;
            }

            return false;
        }

        return true;
    }

    /**
     * Obtener símbolos similares para sugerir en swap
     * Basado en: misma categoría, propiedades similares, estructura similar
     *
     * @param int $symbol_id ID del símbolo actual.
     * @param int $limit     Número máximo de resultados.
     * @return array Lista de símbolos similares con puntuación.
     */
    public function get_similar_symbols( $symbol_id, $limit = 10 ) {
        $current_symbol = $this->get_symbol( $symbol_id );
        if ( ! $current_symbol ) {
            return array();
        }

        $all_symbols = $this->get_symbols( array( 'limit' => 100 ) );
        $scored_symbols = array();

        foreach ( $all_symbols as $candidate_symbol ) {
            // Excluir el símbolo actual
            if ( (int) $candidate_symbol['id'] === (int) $symbol_id ) {
                continue;
            }

            $similarity_score = 0;

            // Misma categoría: +30 puntos
            if ( $candidate_symbol['category'] === $current_symbol['category'] ) {
                $similarity_score += 30;
            }

            // Estructura similar (mismo número de elementos raíz): +20 puntos
            $current_content_count   = is_array( $current_symbol['content'] ) ? count( $current_symbol['content'] ) : 0;
            $candidate_content_count = is_array( $candidate_symbol['content'] ) ? count( $candidate_symbol['content'] ) : 0;

            if ( $current_content_count === $candidate_content_count ) {
                $similarity_score += 20;
            } elseif ( abs( $current_content_count - $candidate_content_count ) <= 2 ) {
                // Estructura similar: +10 puntos
                $similarity_score += 10;
            }

            // Mismas propiedades expuestas: +10 por cada coincidencia
            $current_exposed   = is_array( $current_symbol['exposed_properties'] ) ? $current_symbol['exposed_properties'] : array();
            $candidate_exposed = is_array( $candidate_symbol['exposed_properties'] ) ? $candidate_symbol['exposed_properties'] : array();

            $common_exposed_props = array_intersect( $current_exposed, $candidate_exposed );
            $similarity_score    += count( $common_exposed_props ) * 10;

            // Tipos de bloques similares: +5 por cada tipo coincidente
            $current_block_types   = $this->extract_block_types( $current_symbol['content'] );
            $candidate_block_types = $this->extract_block_types( $candidate_symbol['content'] );

            $common_block_types = array_intersect( $current_block_types, $candidate_block_types );
            $similarity_score  += count( $common_block_types ) * 5;

            if ( $similarity_score > 0 ) {
                $scored_symbols[] = array(
                    'symbol' => $candidate_symbol,
                    'score'  => $similarity_score,
                );
            }
        }

        // Ordenar por puntuación descendente
        usort(
            $scored_symbols,
            function ( $symbol_a, $symbol_b ) {
                return $symbol_b['score'] - $symbol_a['score'];
            }
        );

        // Limitar resultados y extraer solo los símbolos
        $limited_results = array_slice( $scored_symbols, 0, $limit );

        return array_map(
            function ( $scored_item ) {
                return array_merge(
                    $scored_item['symbol'],
                    array( 'similarity_score' => $scored_item['score'] )
                );
            },
            $limited_results
        );
    }

    /**
     * Extraer tipos de bloques del contenido de un símbolo
     *
     * @param mixed $content Contenido del símbolo.
     * @return array Lista de tipos de bloques únicos.
     */
    private function extract_block_types( $content ) {
        $block_types = array();

        if ( ! is_array( $content ) ) {
            return $block_types;
        }

        $this->collect_block_types_recursive( $content, $block_types );

        return array_unique( $block_types );
    }

    /**
     * Recolectar tipos de bloques recursivamente
     *
     * @param array $elements    Elementos a procesar.
     * @param array $block_types Array donde agregar los tipos encontrados.
     */
    private function collect_block_types_recursive( $elements, &$block_types ) {
        foreach ( $elements as $element ) {
            if ( is_array( $element ) ) {
                if ( isset( $element['type'] ) ) {
                    $block_types[] = $element['type'];
                }
                if ( isset( $element['children'] ) && is_array( $element['children'] ) ) {
                    $this->collect_block_types_recursive( $element['children'], $block_types );
                }
            }
        }
    }

    /**
     * Ejecutar swap de instancia a otro símbolo
     *
     * @param int  $instance_id         ID de la instancia.
     * @param int  $new_symbol_id       ID del nuevo símbolo.
     * @param bool $preserve_compatible Si preservar overrides compatibles.
     * @return array|WP_Error Resultado del swap o error.
     */
    public function swap_instance( $instance_id, $new_symbol_id, $preserve_compatible = true ) {
        global $wpdb;

        $instance = $this->get_symbol_instance( $instance_id );
        if ( ! $instance ) {
            return new WP_Error(
                'not_found',
                __( 'Instancia no encontrada', FLAVOR_PLATFORM_TEXT_DOMAIN )
            );
        }

        $new_symbol = $this->get_symbol( $new_symbol_id );
        if ( ! $new_symbol ) {
            return new WP_Error(
                'symbol_not_found',
                __( 'Símbolo destino no encontrado', FLAVOR_PLATFORM_TEXT_DOMAIN )
            );
        }

        $old_symbol_id = $instance['symbol_id'];
        $current_overrides = is_array( $instance['overrides'] ) ? $instance['overrides'] : array();
        $new_overrides = array();
        $lost_overrides = array();

        if ( $preserve_compatible && ! empty( $current_overrides ) ) {
            $compatibility_result = $this->calculate_override_compatibility(
                $old_symbol_id,
                $new_symbol_id,
                $current_overrides
            );
            $new_overrides  = $compatibility_result['compatible'];
            $lost_overrides = $compatibility_result['incompatible'];
        }

        // Actualizar la instancia en la base de datos
        $update_result = $wpdb->update(
            $this->tabla_instancias,
            array(
                'symbol_id'      => $new_symbol_id,
                'overrides'      => wp_json_encode( $new_overrides ),
                'synced_version' => $new_symbol['version'],
            ),
            array( 'id' => $instance_id ),
            array( '%d', '%s', '%d' ),
            array( '%d' )
        );

        if ( false === $update_result ) {
            return new WP_Error(
                'db_error',
                __( 'Error al actualizar instancia', FLAVOR_PLATFORM_TEXT_DOMAIN )
            );
        }

        return array(
            'success'             => true,
            'instance_id'         => $instance_id,
            'old_symbol_id'       => $old_symbol_id,
            'new_symbol_id'       => $new_symbol_id,
            'new_symbol_name'     => $new_symbol['name'],
            'new_symbol_version'  => $new_symbol['version'],
            'preserved_overrides' => $new_overrides,
            'lost_overrides'      => $lost_overrides,
            'lost_count'          => count( $lost_overrides ),
        );
    }

    /**
     * Obtener instancia por element_id y document_id
     *
     * @param string $element_id  ID del elemento en el canvas.
     * @param int    $document_id ID del documento.
     * @return array|null Datos de la instancia o null.
     */
    public function get_instance_by_element_id( $element_id, $document_id ) {
        global $wpdb;

        $instance = $wpdb->get_row(
            $wpdb->prepare(
                "SELECT * FROM {$this->tabla_instancias}
                 WHERE element_id = %s AND document_id = %d",
                $element_id,
                $document_id
            ),
            ARRAY_A
        );

        if ( ! $instance ) {
            return null;
        }

        $instance['overrides'] = json_decode( $instance['overrides'], true );

        // Agregar info del símbolo
        $symbol = $this->get_symbol( $instance['symbol_id'] );
        if ( $symbol ) {
            $instance['symbol_name']    = $symbol['name'];
            $instance['symbol_version'] = $symbol['version'];
            $instance['needs_sync']     = $instance['synced_version'] < $symbol['version'];
        }

        return $instance;
    }
}

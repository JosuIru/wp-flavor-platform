<?php
/**
 * Registro de fuentes de datos disponibles para bloques dinámicos de VBP.
 *
 * Los módulos se registran durante la carga del plugin mediante:
 *
 *   Flavor_VBP_Collection_Registry::get_instance()->register( new Mi_Collection() );
 *
 * El registry actúa como singleton y valida que cada fuente implemente la
 * interfaz Flavor_VBP_Collection_Source antes de aceptarla.
 *
 * @package FlavorPlatform
 * @subpackage VisualBuilderPro\Collections
 * @since 3.6.0
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class Flavor_VBP_Collection_Registry {

    /**
     * Instancia singleton.
     *
     * @var self|null
     */
    private static $instance = null;

    /**
     * Colecciones registradas indexadas por identificador.
     *
     * @var array<string, Flavor_VBP_Collection_Source>
     */
    private $sources = array();

    /**
     * Obtiene la instancia singleton.
     *
     * @return self
     */
    public static function get_instance() {
        if ( null === self::$instance ) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Resetea la instancia. Uso exclusivo en tests.
     *
     * @return void
     */
    public static function reset_for_tests() {
        self::$instance = null;
    }

    /**
     * Registra una fuente. Si ya existe una con el mismo identificador, la
     * sustituye (útil para overrides desde addons).
     *
     * @param Flavor_VBP_Collection_Source $source Fuente a registrar.
     * @return bool True si se registró, false si el identificador está vacío.
     */
    public function register( Flavor_VBP_Collection_Source $source ) {
        $identifier = $source->get_identifier();
        if ( ! is_string( $identifier ) || $identifier === '' ) {
            return false;
        }
        $this->sources[ $identifier ] = $source;
        return true;
    }

    /**
     * Elimina una fuente registrada. Retorna true si existía, false si no.
     *
     * @param string $identifier Identificador de la fuente.
     * @return bool
     */
    public function unregister( $identifier ) {
        if ( ! isset( $this->sources[ $identifier ] ) ) {
            return false;
        }
        unset( $this->sources[ $identifier ] );
        return true;
    }

    /**
     * Indica si hay una fuente registrada con ese identificador.
     *
     * @param string $identifier Identificador.
     * @return bool
     */
    public function has( $identifier ) {
        return isset( $this->sources[ $identifier ] );
    }

    /**
     * Devuelve la fuente registrada o null.
     *
     * @param string $identifier Identificador.
     * @return Flavor_VBP_Collection_Source|null
     */
    public function get( $identifier ) {
        return isset( $this->sources[ $identifier ] ) ? $this->sources[ $identifier ] : null;
    }

    /**
     * Devuelve todas las fuentes registradas, ordenadas alfabéticamente por
     * label para presentación estable en la UI.
     *
     * @return array<int, Flavor_VBP_Collection_Source>
     */
    public function all() {
        $sources = array_values( $this->sources );

        usort( $sources, static function ( Flavor_VBP_Collection_Source $a, Flavor_VBP_Collection_Source $b ) {
            return strcasecmp( $a->get_label(), $b->get_label() );
        } );

        return $sources;
    }

    /**
     * Representación ligera de las fuentes registradas para el endpoint REST
     * que lista colecciones (no incluye resultados, solo metadata).
     *
     * @return array<int, array<string, mixed>>
     */
    public function to_public_array() {
        return array_map( static function ( Flavor_VBP_Collection_Source $source ) {
            return array(
                'id'          => $source->get_identifier(),
                'label'       => $source->get_label(),
                'description' => $source->get_description(),
                'fields'      => $source->get_query_fields(),
            );
        }, $this->all() );
    }

    /**
     * Valida y sanea $raw_args contra el schema de la fuente. Campos
     * desconocidos se descartan; valores inválidos caen al default del campo.
     *
     * @param Flavor_VBP_Collection_Source $source   Fuente objetivo.
     * @param array<string, mixed>         $raw_args Argumentos crudos del request.
     * @return array<string, mixed>
     */
    public function sanitize_query_args( Flavor_VBP_Collection_Source $source, array $raw_args ) {
        $schema  = $source->get_query_fields();
        $cleaned = array();

        foreach ( $schema as $field_name => $field_config ) {
            $tipo_campo    = isset( $field_config['type'] ) ? $field_config['type'] : 'string';
            $valor_default = isset( $field_config['default'] ) ? $field_config['default'] : null;
            $valor_raw     = array_key_exists( $field_name, $raw_args ) ? $raw_args[ $field_name ] : null;

            $cleaned[ $field_name ] = $this->coerce_value_to_type(
                $valor_raw,
                $tipo_campo,
                $field_config,
                $valor_default
            );
        }

        return $cleaned;
    }

    /**
     * Convierte un valor crudo al tipo declarado en el schema del campo.
     *
     * @param mixed  $valor_raw        Valor crudo.
     * @param string $tipo_campo       Tipo declarado (string|int|bool|enum|date).
     * @param array  $field_config     Config completa del campo (para options, min, max).
     * @param mixed  $valor_default    Default cuando el valor es inválido o ausente.
     * @return mixed
     */
    private function coerce_value_to_type( $valor_raw, $tipo_campo, array $field_config, $valor_default ) {
        if ( $valor_raw === null || $valor_raw === '' ) {
            return $valor_default;
        }

        switch ( $tipo_campo ) {
            case 'int':
                $numero_entero = is_numeric( $valor_raw ) ? (int) $valor_raw : null;
                if ( $numero_entero === null ) {
                    return $valor_default;
                }
                if ( isset( $field_config['min'] ) ) {
                    $numero_entero = max( (int) $field_config['min'], $numero_entero );
                }
                if ( isset( $field_config['max'] ) ) {
                    $numero_entero = min( (int) $field_config['max'], $numero_entero );
                }
                return $numero_entero;

            case 'bool':
                return filter_var( $valor_raw, FILTER_VALIDATE_BOOLEAN );

            case 'enum':
                $opciones_permitidas = isset( $field_config['options'] ) ? (array) $field_config['options'] : array();
                return in_array( $valor_raw, $opciones_permitidas, true ) ? $valor_raw : $valor_default;

            case 'date':
                $valor_string = (string) $valor_raw;

                // Resolver tokens relativos (@today, @today+7d, @start_of_week…)
                // antes de validar el formato Y-m-d.
                if ( $valor_string !== '' && $valor_string[0] === '@' && class_exists( 'Flavor_VBP_Date_Token_Resolver' ) ) {
                    $valor_string = (string) Flavor_VBP_Date_Token_Resolver::resolve( $valor_string );
                }

                if ( $valor_string === '' ) {
                    return $valor_default;
                }

                $fecha_parseada = DateTime::createFromFormat( 'Y-m-d', $valor_string );
                if ( $fecha_parseada && $fecha_parseada->format( 'Y-m-d' ) === $valor_string ) {
                    return $valor_string;
                }
                return $valor_default;

            case 'string':
            default:
                return is_scalar( $valor_raw ) ? sanitize_text_field( (string) $valor_raw ) : $valor_default;
        }
    }
}

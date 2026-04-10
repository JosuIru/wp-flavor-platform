<?php
/**
 * Module Versioning System
 *
 * Gestiona versiones de modulos de forma independiente,
 * verificacion de dependencias y compatibilidad.
 *
 * @package Flavor_Platform
 * @subpackage Includes
 * @since 3.4.0
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Clase para gestionar el versionado independiente de modulos.
 *
 * @since 3.4.0
 */
class Flavor_Module_Versioning {

    /**
     * Instancia singleton.
     *
     * @var Flavor_Module_Versioning|null
     */
    private static $instance = null;

    /**
     * Cache de modulos cargados.
     *
     * @var array
     */
    private $modules_cache = array();

    /**
     * Directorio base de modulos.
     *
     * @var string
     */
    private $modules_directory;

    /**
     * Schema de modulo cargado.
     *
     * @var array|null
     */
    private $module_schema = null;

    /**
     * Errores de validacion.
     *
     * @var array
     */
    private $validation_errors = array();

    /**
     * Version minima de WordPress por defecto.
     *
     * @var string
     */
    const DEFAULT_WP_VERSION_MIN = '6.0';

    /**
     * Version minima de PHP por defecto.
     *
     * @var string
     */
    const DEFAULT_PHP_VERSION_MIN = '7.4';

    /**
     * Obtiene la instancia singleton.
     *
     * @return Flavor_Module_Versioning
     */
    public static function get_instance() {
        if ( null === self::$instance ) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Constructor privado para singleton.
     */
    private function __construct() {
        $this->modules_directory = dirname( __FILE__ ) . '/modules/';
        $this->load_schema();
    }

    /**
     * Carga el schema JSON de modulos.
     *
     * @return bool True si se cargo correctamente.
     */
    private function load_schema() {
        $schema_path = $this->modules_directory . 'module-schema.json';

        if ( ! file_exists( $schema_path ) ) {
            return false;
        }

        $schema_content = file_get_contents( $schema_path );
        $this->module_schema = json_decode( $schema_content, true );

        return json_last_error() === JSON_ERROR_NONE;
    }

    /**
     * Obtiene informacion de un modulo desde su module.json.
     *
     * @param string $module_id ID del modulo.
     * @return array|WP_Error Datos del modulo o error.
     */
    public function get_module_info( $module_id ) {
        // Verificar cache
        if ( isset( $this->modules_cache[ $module_id ] ) ) {
            return $this->modules_cache[ $module_id ];
        }

        $module_path = $this->modules_directory . $module_id . '/module.json';

        if ( ! file_exists( $module_path ) ) {
            return new WP_Error(
                'module_not_found',
                sprintf(
                    __( 'No se encontro module.json para el modulo: %s', FLAVOR_PLATFORM_TEXT_DOMAIN ),
                    $module_id
                )
            );
        }

        $module_content = file_get_contents( $module_path );
        $module_data = json_decode( $module_content, true );

        if ( json_last_error() !== JSON_ERROR_NONE ) {
            return new WP_Error(
                'invalid_json',
                sprintf(
                    __( 'Error al parsear module.json del modulo %s: %s', FLAVOR_PLATFORM_TEXT_DOMAIN ),
                    $module_id,
                    json_last_error_msg()
                )
            );
        }

        // Validar contra schema
        $validation_result = $this->validate_module_data( $module_data );
        if ( is_wp_error( $validation_result ) ) {
            return $validation_result;
        }

        // Guardar en cache
        $this->modules_cache[ $module_id ] = $module_data;

        return $module_data;
    }

    /**
     * Obtiene la version de un modulo.
     *
     * @param string $module_id ID del modulo.
     * @return string|WP_Error Version del modulo o error.
     */
    public function get_module_version( $module_id ) {
        $module_info = $this->get_module_info( $module_id );

        if ( is_wp_error( $module_info ) ) {
            return $module_info;
        }

        return isset( $module_info['version'] ) ? $module_info['version'] : '0.0.0';
    }

    /**
     * Valida los datos de un modulo contra el schema.
     *
     * @param array $module_data Datos del modulo.
     * @return bool|WP_Error True si es valido, WP_Error si hay errores.
     */
    public function validate_module_data( $module_data ) {
        $this->validation_errors = array();

        // Validar campos requeridos
        $required_fields = array( 'id', 'name', 'version' );
        foreach ( $required_fields as $field ) {
            if ( empty( $module_data[ $field ] ) ) {
                $this->validation_errors[] = sprintf(
                    __( 'Campo requerido faltante: %s', FLAVOR_PLATFORM_TEXT_DOMAIN ),
                    $field
                );
            }
        }

        // Validar formato de version (semver)
        if ( ! empty( $module_data['version'] ) ) {
            if ( ! $this->is_valid_semver( $module_data['version'] ) ) {
                $this->validation_errors[] = sprintf(
                    __( 'Version invalida: %s. Debe seguir el formato semver (ej: 1.0.0)', FLAVOR_PLATFORM_TEXT_DOMAIN ),
                    $module_data['version']
                );
            }
        }

        // Validar formato de ID (slug)
        if ( ! empty( $module_data['id'] ) ) {
            if ( ! preg_match( '/^[a-z][a-z0-9-]*$/', $module_data['id'] ) ) {
                $this->validation_errors[] = sprintf(
                    __( 'ID de modulo invalido: %s. Solo minusculas, numeros y guiones.', FLAVOR_PLATFORM_TEXT_DOMAIN ),
                    $module_data['id']
                );
            }
        }

        // Validar versiones de WordPress y PHP
        if ( ! empty( $module_data['wp_version_min'] ) ) {
            if ( ! preg_match( '/^\d+\.\d+(\.\d+)?$/', $module_data['wp_version_min'] ) ) {
                $this->validation_errors[] = sprintf(
                    __( 'Version de WordPress invalida: %s', FLAVOR_PLATFORM_TEXT_DOMAIN ),
                    $module_data['wp_version_min']
                );
            }
        }

        if ( ! empty( $module_data['php_version_min'] ) ) {
            if ( ! preg_match( '/^\d+\.\d+(\.\d+)?$/', $module_data['php_version_min'] ) ) {
                $this->validation_errors[] = sprintf(
                    __( 'Version de PHP invalida: %s', FLAVOR_PLATFORM_TEXT_DOMAIN ),
                    $module_data['php_version_min']
                );
            }
        }

        if ( ! empty( $this->validation_errors ) ) {
            return new WP_Error(
                'validation_failed',
                implode( '; ', $this->validation_errors )
            );
        }

        return true;
    }

    /**
     * Verifica si una cadena es una version semver valida.
     *
     * @param string $version Version a verificar.
     * @return bool True si es valida.
     */
    public function is_valid_semver( $version ) {
        $semver_pattern = '/^(0|[1-9]\d*)\.(0|[1-9]\d*)\.(0|[1-9]\d*)(?:-((?:0|[1-9]\d*|\d*[a-zA-Z-][0-9a-zA-Z-]*)(?:\.(?:0|[1-9]\d*|\d*[a-zA-Z-][0-9a-zA-Z-]*))*))?(?:\+([0-9a-zA-Z-]+(?:\.[0-9a-zA-Z-]+)*))?$/';
        return (bool) preg_match( $semver_pattern, $version );
    }

    /**
     * Verifica las dependencias de un modulo.
     *
     * @param string $module_id ID del modulo.
     * @return array Array con resultados de verificacion.
     */
    public function verify_dependencies( $module_id ) {
        $module_info = $this->get_module_info( $module_id );

        if ( is_wp_error( $module_info ) ) {
            return array(
                'success' => false,
                'errors'  => array( $module_info->get_error_message() ),
            );
        }

        $result = array(
            'success'      => true,
            'satisfied'    => array(),
            'missing'      => array(),
            'incompatible' => array(),
        );

        // Verificar dependencias requeridas
        if ( ! empty( $module_info['dependencies'] ) ) {
            foreach ( $module_info['dependencies'] as $dependency_id => $version_constraint ) {
                $dependency_check = $this->check_dependency( $dependency_id, $version_constraint );

                if ( $dependency_check['status'] === 'satisfied' ) {
                    $result['satisfied'][] = array(
                        'id'         => $dependency_id,
                        'required'   => $version_constraint,
                        'installed'  => $dependency_check['version'],
                    );
                } elseif ( $dependency_check['status'] === 'missing' ) {
                    $result['missing'][] = array(
                        'id'       => $dependency_id,
                        'required' => $version_constraint,
                    );
                    $result['success'] = false;
                } else {
                    $result['incompatible'][] = array(
                        'id'        => $dependency_id,
                        'required'  => $version_constraint,
                        'installed' => $dependency_check['version'],
                    );
                    $result['success'] = false;
                }
            }
        }

        return $result;
    }

    /**
     * Verifica una dependencia individual.
     *
     * @param string $dependency_id ID del modulo dependencia.
     * @param string $version_constraint Restriccion de version.
     * @return array Estado de la dependencia.
     */
    private function check_dependency( $dependency_id, $version_constraint ) {
        $dependency_info = $this->get_module_info( $dependency_id );

        if ( is_wp_error( $dependency_info ) ) {
            return array(
                'status'  => 'missing',
                'version' => null,
            );
        }

        $installed_version = $dependency_info['version'];

        if ( $this->version_satisfies_constraint( $installed_version, $version_constraint ) ) {
            return array(
                'status'  => 'satisfied',
                'version' => $installed_version,
            );
        }

        return array(
            'status'  => 'incompatible',
            'version' => $installed_version,
        );
    }

    /**
     * Verifica si una version satisface una restriccion.
     *
     * Soporta los siguientes formatos:
     * - Exacta: "1.0.0"
     * - Mayor o igual: ">=1.0.0"
     * - Menor: "<2.0.0"
     * - Caret (compatible): "^1.0.0" (>=1.0.0 <2.0.0)
     * - Tilde (aproximada): "~1.2.0" (>=1.2.0 <1.3.0)
     * - Rango: "1.0.0 - 2.0.0"
     * - Wildcard: "1.*" o "1.2.*"
     *
     * @param string $version Version instalada.
     * @param string $constraint Restriccion de version.
     * @return bool True si la version satisface la restriccion.
     */
    public function version_satisfies_constraint( $version, $constraint ) {
        $constraint = trim( $constraint );

        // Rango: "1.0.0 - 2.0.0"
        if ( strpos( $constraint, ' - ' ) !== false ) {
            list( $min_version, $max_version ) = explode( ' - ', $constraint );
            return version_compare( $version, trim( $min_version ), '>=' ) &&
                   version_compare( $version, trim( $max_version ), '<=' );
        }

        // Caret: "^1.0.0" (compatible)
        if ( strpos( $constraint, '^' ) === 0 ) {
            $min_version = substr( $constraint, 1 );
            $parts = explode( '.', $min_version );

            // Para ^0.x.y, solo incrementa el minor
            // Para ^x.y.z donde x > 0, incrementa el major
            if ( (int) $parts[0] === 0 ) {
                $max_version = $parts[0] . '.' . ( (int) $parts[1] + 1 ) . '.0';
            } else {
                $max_version = ( (int) $parts[0] + 1 ) . '.0.0';
            }

            return version_compare( $version, $min_version, '>=' ) &&
                   version_compare( $version, $max_version, '<' );
        }

        // Tilde: "~1.2.0" (aproximada)
        if ( strpos( $constraint, '~' ) === 0 ) {
            $min_version = substr( $constraint, 1 );
            $parts = explode( '.', $min_version );
            $max_version = $parts[0] . '.' . ( (int) $parts[1] + 1 ) . '.0';

            return version_compare( $version, $min_version, '>=' ) &&
                   version_compare( $version, $max_version, '<' );
        }

        // Wildcard: "1.*" o "1.2.*"
        if ( strpos( $constraint, '*' ) !== false ) {
            $pattern = str_replace( '*', '', rtrim( $constraint, '.' ) );
            return strpos( $version, $pattern ) === 0;
        }

        // Mayor o igual: ">=1.0.0"
        if ( strpos( $constraint, '>=' ) === 0 ) {
            return version_compare( $version, substr( $constraint, 2 ), '>=' );
        }

        // Mayor: ">1.0.0"
        if ( strpos( $constraint, '>' ) === 0 ) {
            return version_compare( $version, substr( $constraint, 1 ), '>' );
        }

        // Menor o igual: "<=1.0.0"
        if ( strpos( $constraint, '<=' ) === 0 ) {
            return version_compare( $version, substr( $constraint, 2 ), '<=' );
        }

        // Menor: "<1.0.0"
        if ( strpos( $constraint, '<' ) === 0 ) {
            return version_compare( $version, substr( $constraint, 1 ), '<' );
        }

        // Version exacta
        return version_compare( $version, $constraint, '==' );
    }

    /**
     * Verifica la compatibilidad del sistema con un modulo.
     *
     * @param string $module_id ID del modulo.
     * @return array Resultado de compatibilidad.
     */
    public function verify_compatibility( $module_id ) {
        $module_info = $this->get_module_info( $module_id );

        if ( is_wp_error( $module_info ) ) {
            return array(
                'compatible' => false,
                'errors'     => array( $module_info->get_error_message() ),
            );
        }

        $result = array(
            'compatible' => true,
            'warnings'   => array(),
            'errors'     => array(),
            'details'    => array(),
        );

        // Verificar version de WordPress
        $wp_version_min = isset( $module_info['wp_version_min'] )
            ? $module_info['wp_version_min']
            : self::DEFAULT_WP_VERSION_MIN;

        global $wp_version;
        $result['details']['wp_version'] = array(
            'required' => $wp_version_min,
            'current'  => $wp_version,
        );

        if ( version_compare( $wp_version, $wp_version_min, '<' ) ) {
            $result['errors'][] = sprintf(
                __( 'WordPress %s requerido. Version actual: %s', FLAVOR_PLATFORM_TEXT_DOMAIN ),
                $wp_version_min,
                $wp_version
            );
            $result['compatible'] = false;
        }

        // Verificar version de PHP
        $php_version_min = isset( $module_info['php_version_min'] )
            ? $module_info['php_version_min']
            : self::DEFAULT_PHP_VERSION_MIN;

        $php_version = phpversion();
        $result['details']['php_version'] = array(
            'required' => $php_version_min,
            'current'  => $php_version,
        );

        if ( version_compare( $php_version, $php_version_min, '<' ) ) {
            $result['errors'][] = sprintf(
                __( 'PHP %s requerido. Version actual: %s', FLAVOR_PLATFORM_TEXT_DOMAIN ),
                $php_version_min,
                $php_version
            );
            $result['compatible'] = false;
        }

        // Verificar version de Flavor Platform
        if ( ! empty( $module_info['flavor_version_min'] ) ) {
            $flavor_version = defined( 'FLAVOR_PLATFORM_VERSION' )
                ? FLAVOR_PLATFORM_VERSION
                : '0.0.0';

            $result['details']['flavor_version'] = array(
                'required' => $module_info['flavor_version_min'],
                'current'  => $flavor_version,
            );

            if ( version_compare( $flavor_version, $module_info['flavor_version_min'], '<' ) ) {
                $result['errors'][] = sprintf(
                    __( 'Flavor Platform %s requerido. Version actual: %s', FLAVOR_PLATFORM_TEXT_DOMAIN ),
                    $module_info['flavor_version_min'],
                    $flavor_version
                );
                $result['compatible'] = false;
            }
        }

        // Verificar "tested up to" de WordPress (warning, no error)
        if ( ! empty( $module_info['wp_version_tested'] ) ) {
            if ( version_compare( $wp_version, $module_info['wp_version_tested'], '>' ) ) {
                $result['warnings'][] = sprintf(
                    __( 'El modulo fue probado hasta WordPress %s. Version actual: %s', FLAVOR_PLATFORM_TEXT_DOMAIN ),
                    $module_info['wp_version_tested'],
                    $wp_version
                );
            }
        }

        // Verificar conflictos
        if ( ! empty( $module_info['conflicts'] ) ) {
            foreach ( $module_info['conflicts'] as $conflict_id => $conflict_version ) {
                $conflict_info = $this->get_module_info( $conflict_id );

                if ( ! is_wp_error( $conflict_info ) ) {
                    if ( $this->version_satisfies_constraint( $conflict_info['version'], $conflict_version ) ) {
                        $result['errors'][] = sprintf(
                            __( 'Conflicto detectado con modulo %s (version %s)', FLAVOR_PLATFORM_TEXT_DOMAIN ),
                            $conflict_id,
                            $conflict_info['version']
                        );
                        $result['compatible'] = false;
                    }
                }
            }
        }

        // Verificar deprecacion
        if ( ! empty( $module_info['deprecated'] ) && $module_info['deprecated'] === true ) {
            $deprecation_message = ! empty( $module_info['deprecated_message'] )
                ? $module_info['deprecated_message']
                : __( 'Este modulo esta deprecado.', FLAVOR_PLATFORM_TEXT_DOMAIN );

            $result['warnings'][] = $deprecation_message;
        }

        return $result;
    }

    /**
     * Obtiene el changelog de un modulo.
     *
     * @param string      $module_id ID del modulo.
     * @param string|null $from_version Version inicial (opcional).
     * @param string|null $to_version Version final (opcional).
     * @return array|WP_Error Changelog filtrado o error.
     */
    public function get_module_changelog( $module_id, $from_version = null, $to_version = null ) {
        $module_info = $this->get_module_info( $module_id );

        if ( is_wp_error( $module_info ) ) {
            return $module_info;
        }

        if ( empty( $module_info['changelog'] ) ) {
            return array();
        }

        $changelog = $module_info['changelog'];

        // Filtrar por rango de versiones si se especifica
        if ( $from_version !== null || $to_version !== null ) {
            $changelog = array_filter( $changelog, function( $entry ) use ( $from_version, $to_version ) {
                $version = $entry['version'];

                if ( $from_version !== null && version_compare( $version, $from_version, '<=' ) ) {
                    return false;
                }

                if ( $to_version !== null && version_compare( $version, $to_version, '>' ) ) {
                    return false;
                }

                return true;
            });
        }

        // Ordenar por version descendente
        usort( $changelog, function( $a, $b ) {
            return version_compare( $b['version'], $a['version'] );
        });

        return array_values( $changelog );
    }

    /**
     * Obtiene todos los modulos con module.json.
     *
     * @return array Lista de modulos con su informacion basica.
     */
    public function get_all_versioned_modules() {
        $modules = array();
        $directories = glob( $this->modules_directory . '*', GLOB_ONLYDIR );

        foreach ( $directories as $directory ) {
            $module_json_path = $directory . '/module.json';

            if ( file_exists( $module_json_path ) ) {
                $module_id = basename( $directory );
                $module_info = $this->get_module_info( $module_id );

                if ( ! is_wp_error( $module_info ) ) {
                    $modules[ $module_id ] = array(
                        'id'          => $module_info['id'],
                        'name'        => $module_info['name'],
                        'version'     => $module_info['version'],
                        'description' => isset( $module_info['description'] ) ? $module_info['description'] : '',
                        'stability'   => isset( $module_info['stability'] ) ? $module_info['stability'] : 'stable',
                        'category'    => isset( $module_info['category'] ) ? $module_info['category'] : 'utilities',
                    );
                }
            }
        }

        return $modules;
    }

    /**
     * Compara dos versiones de modulo.
     *
     * @param string $module_id ID del modulo.
     * @param string $version_a Primera version.
     * @param string $version_b Segunda version.
     * @return int -1 si a < b, 0 si a == b, 1 si a > b.
     */
    public function compare_versions( $module_id, $version_a, $version_b ) {
        return version_compare( $version_a, $version_b );
    }

    /**
     * Verifica si hay actualizacion disponible para un modulo.
     *
     * @param string $module_id ID del modulo.
     * @param string $available_version Version disponible.
     * @return bool True si hay actualizacion disponible.
     */
    public function has_update_available( $module_id, $available_version ) {
        $current_version = $this->get_module_version( $module_id );

        if ( is_wp_error( $current_version ) ) {
            return false;
        }

        return version_compare( $available_version, $current_version, '>' );
    }

    /**
     * Genera un reporte completo de estado de modulos.
     *
     * @return array Reporte con todos los modulos y su estado.
     */
    public function generate_status_report() {
        $modules = $this->get_all_versioned_modules();
        $report = array(
            'generated_at'  => current_time( 'mysql' ),
            'total_modules' => count( $modules ),
            'modules'       => array(),
            'summary'       => array(
                'compatible'   => 0,
                'incompatible' => 0,
                'with_warnings' => 0,
            ),
        );

        foreach ( $modules as $module_id => $basic_info ) {
            $compatibility = $this->verify_compatibility( $module_id );
            $dependencies = $this->verify_dependencies( $module_id );

            $module_status = array(
                'info'          => $basic_info,
                'compatibility' => $compatibility,
                'dependencies'  => $dependencies,
            );

            $report['modules'][ $module_id ] = $module_status;

            if ( $compatibility['compatible'] && $dependencies['success'] ) {
                if ( ! empty( $compatibility['warnings'] ) ) {
                    $report['summary']['with_warnings']++;
                }
                $report['summary']['compatible']++;
            } else {
                $report['summary']['incompatible']++;
            }
        }

        return $report;
    }

    /**
     * Limpia la cache de modulos.
     */
    public function clear_cache() {
        $this->modules_cache = array();
    }

    /**
     * Obtiene los errores de validacion del ultimo proceso.
     *
     * @return array Errores de validacion.
     */
    public function get_validation_errors() {
        return $this->validation_errors;
    }

    /**
     * Obtiene el schema de modulo cargado.
     *
     * @return array|null Schema o null si no se cargo.
     */
    public function get_module_schema() {
        return $this->module_schema;
    }
}

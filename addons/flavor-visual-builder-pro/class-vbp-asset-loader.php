<?php
/**
 * VBP Asset Loader - Sistema de carga optimizada de assets
 *
 * Implementa lazy loading, bundling y carga condicional basada en
 * el manifiesto de assets y feature flags del editor.
 *
 * @package Flavor_Platform
 * @subpackage Visual_Builder_Pro
 * @since 3.5.0
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Clase para carga optimizada de assets VBP
 *
 * @since 3.5.0
 */
class Flavor_VBP_Asset_Loader {

    /**
     * Version del sistema de carga
     *
     * @var string
     */
    const VERSION = '1.0.0';

    /**
     * Instancia singleton
     *
     * @var Flavor_VBP_Asset_Loader|null
     */
    private static $instancia = null;

    /**
     * Manifiesto de assets cacheado
     *
     * @var array|null
     */
    private $manifiesto = null;

    /**
     * URL base de assets VBP
     *
     * @var string
     */
    private $url_base = '';

    /**
     * Path base de assets VBP
     *
     * @var string
     */
    private $path_base = '';

    /**
     * Feature flags activas
     *
     * @var array
     */
    private $feature_flags = array();

    /**
     * Bundles ya cargados
     *
     * @var array
     */
    private $bundles_cargados = array();

    /**
     * Modo de carga (bundled o individual)
     *
     * @var string
     */
    private $modo_carga = 'individual';

    /**
     * Obtiene la instancia singleton
     *
     * @return Flavor_VBP_Asset_Loader
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
        $this->url_base  = FLAVOR_PLATFORM_URL . 'assets/vbp/';
        $this->path_base = FLAVOR_PLATFORM_PATH . 'assets/vbp/';

        // Determinar modo de carga
        $this->modo_carga = $this->determinar_modo_carga();

        // Cargar manifiesto
        $this->cargar_manifiesto();
    }

    /**
     * Determina el modo de carga basado en configuracion y entorno
     *
     * @return string 'bundled' o 'individual'
     */
    private function determinar_modo_carga() {
        // En desarrollo, usar archivos individuales para debugging
        if ( defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG ) {
            return 'individual';
        }

        // Verificar si existen los bundles compilados
        $directorio_dist = $this->path_base . 'dist/';
        if ( ! is_dir( $directorio_dist ) ) {
            return 'individual';
        }

        // Verificar manifiesto de bundles
        $manifiesto_dist = $directorio_dist . 'manifest.json';
        if ( ! file_exists( $manifiesto_dist ) ) {
            return 'individual';
        }

        return 'bundled';
    }

    /**
     * Carga el manifiesto de assets
     */
    private function cargar_manifiesto() {
        $archivo_manifiesto = $this->path_base . 'manifest.json';

        if ( file_exists( $archivo_manifiesto ) ) {
            $contenido = file_get_contents( $archivo_manifiesto ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents
            $this->manifiesto = json_decode( $contenido, true );
        }

        if ( ! is_array( $this->manifiesto ) ) {
            $this->manifiesto = array(
                'bundles' => array( 'js' => array(), 'css' => array() ),
                'lazyLoad' => array( 'triggers' => array(), 'featureFlags' => array() ),
                'preload' => array(),
            );
        }
    }

    /**
     * Establece las feature flags activas
     *
     * @param array $flags Array de feature flags.
     */
    public function establecer_feature_flags( $flags ) {
        $this->feature_flags = $flags;
    }

    /**
     * Encola los assets core (siempre necesarios)
     */
    public function encolar_assets_core() {
        if ( 'bundled' === $this->modo_carga ) {
            $this->encolar_bundle( 'vbp-core', 'js' );
            $this->encolar_bundle( 'vbp-core', 'css' );
        } else {
            $this->encolar_archivos_individuales( 'vbp-core', 'js' );
            $this->encolar_archivos_individuales( 'vbp-core', 'css' );
        }
    }

    /**
     * Encola los assets del editor
     */
    public function encolar_assets_editor() {
        $bundles_editor = array( 'vbp-editor', 'vbp-keyboard', 'vbp-app' );

        foreach ( $bundles_editor as $bundle_nombre ) {
            if ( 'bundled' === $this->modo_carga ) {
                $this->encolar_bundle( $bundle_nombre, 'js' );
                $this->encolar_bundle( $bundle_nombre, 'css' );
            } else {
                $this->encolar_archivos_individuales( $bundle_nombre, 'js' );
                $this->encolar_archivos_individuales( $bundle_nombre, 'css' );
            }
        }
    }

    /**
     * Encola assets basados en feature flags
     */
    public function encolar_assets_por_features() {
        $mapeo_features = $this->manifiesto['lazyLoad']['featureFlags'] ?? array();

        foreach ( $mapeo_features as $flag_nombre => $bundles_relacionados ) {
            if ( ! empty( $this->feature_flags[ $flag_nombre ] ) ) {
                foreach ( $bundles_relacionados as $bundle_nombre ) {
                    // Para features activas, precargar los bundles
                    $this->registrar_bundle_lazy( $bundle_nombre );
                }
            }
        }
    }

    /**
     * Encola un bundle compilado
     *
     * @param string $nombre_bundle Nombre del bundle.
     * @param string $tipo          'js' o 'css'.
     */
    private function encolar_bundle( $nombre_bundle, $tipo ) {
        $clave_bundle = $tipo . ':' . $nombre_bundle;

        if ( in_array( $clave_bundle, $this->bundles_cargados, true ) ) {
            return;
        }

        $config_bundle = $this->manifiesto['bundles'][ $tipo ][ $nombre_bundle ] ?? null;

        if ( ! $config_bundle ) {
            // Fallback a archivos individuales
            $this->encolar_archivos_individuales( $nombre_bundle, $tipo );
            return;
        }

        // Cargar dependencias primero
        $dependencias = $config_bundle['dependencies'] ?? array();
        foreach ( $dependencias as $dependencia ) {
            $this->encolar_bundle( $dependencia, $tipo );
        }

        // Determinar archivo del bundle
        $archivo_bundle = "dist/{$nombre_bundle}.bundle.{$tipo}";
        $ruta_completa  = $this->path_base . $archivo_bundle;

        // Verificar que el bundle existe
        if ( ! file_exists( $ruta_completa ) ) {
            $this->encolar_archivos_individuales( $nombre_bundle, $tipo );
            return;
        }

        // Obtener dependencias de handles de WordPress
        $dependencias_wp = $this->obtener_dependencias_wp( $nombre_bundle, $tipo );

        if ( 'js' === $tipo ) {
            wp_enqueue_script(
                'vbp-bundle-' . $nombre_bundle,
                $this->url_base . $archivo_bundle,
                $dependencias_wp,
                self::VERSION,
                true
            );
        } else {
            wp_enqueue_style(
                'vbp-bundle-' . $nombre_bundle,
                $this->url_base . $archivo_bundle,
                $dependencias_wp,
                self::VERSION
            );
        }

        $this->bundles_cargados[] = $clave_bundle;
    }

    /**
     * Encola archivos individuales de un bundle
     *
     * @param string $nombre_bundle Nombre del bundle.
     * @param string $tipo          'js' o 'css'.
     */
    private function encolar_archivos_individuales( $nombre_bundle, $tipo ) {
        $clave_bundle = $tipo . ':' . $nombre_bundle;

        if ( in_array( $clave_bundle, $this->bundles_cargados, true ) ) {
            return;
        }

        $config_bundle = $this->manifiesto['bundles'][ $tipo ][ $nombre_bundle ] ?? null;

        if ( ! $config_bundle || empty( $config_bundle['files'] ) ) {
            return;
        }

        // Cargar dependencias primero
        $dependencias = $config_bundle['dependencies'] ?? array();
        foreach ( $dependencias as $dependencia ) {
            $this->encolar_archivos_individuales( $dependencia, $tipo );
        }

        // Usar versiones minificadas en produccion
        $usar_minificado = ! defined( 'SCRIPT_DEBUG' ) || ! SCRIPT_DEBUG;

        foreach ( $config_bundle['files'] as $archivo ) {
            $nombre_handle = $this->generar_handle( $archivo, $tipo );
            $ruta_archivo  = $archivo;

            // Intentar cargar version minificada
            if ( $usar_minificado ) {
                $archivo_min = str_replace( ".{$tipo}", ".min.{$tipo}", $archivo );
                if ( file_exists( $this->path_base . $archivo_min ) ) {
                    $ruta_archivo = $archivo_min;
                }
            }

            if ( ! file_exists( $this->path_base . $ruta_archivo ) ) {
                continue;
            }

            $dependencias_archivo = $this->obtener_dependencias_archivo( $archivo, $tipo );

            if ( 'js' === $tipo ) {
                wp_enqueue_script(
                    $nombre_handle,
                    $this->url_base . $ruta_archivo,
                    $dependencias_archivo,
                    self::VERSION,
                    true
                );
            } else {
                wp_enqueue_style(
                    $nombre_handle,
                    $this->url_base . $ruta_archivo,
                    $dependencias_archivo,
                    self::VERSION
                );
            }
        }

        $this->bundles_cargados[] = $clave_bundle;
    }

    /**
     * Registra un bundle para carga lazy
     *
     * @param string $nombre_bundle Nombre del bundle.
     */
    private function registrar_bundle_lazy( $nombre_bundle ) {
        // Los bundles lazy se registran pero no se encolan
        // El lazy loader JS los cargara bajo demanda
        add_filter( 'vbp_lazy_bundles', function( $bundles ) use ( $nombre_bundle ) {
            $bundles[] = $nombre_bundle;
            return array_unique( $bundles );
        });
    }

    /**
     * Genera handle de WordPress para un archivo
     *
     * @param string $archivo Ruta del archivo.
     * @param string $tipo    'js' o 'css'.
     * @return string
     */
    private function generar_handle( $archivo, $tipo ) {
        $nombre_base = basename( $archivo, ".{$tipo}" );
        $nombre_base = str_replace( '.min', '', $nombre_base );

        return 'vbp-' . sanitize_key( $nombre_base );
    }

    /**
     * Obtiene dependencias de WordPress para un bundle
     *
     * @param string $nombre_bundle Nombre del bundle.
     * @param string $tipo          'js' o 'css'.
     * @return array
     */
    private function obtener_dependencias_wp( $nombre_bundle, $tipo ) {
        $dependencias = array();

        $config_bundle = $this->manifiesto['bundles'][ $tipo ][ $nombre_bundle ] ?? null;

        if ( ! $config_bundle ) {
            return $dependencias;
        }

        // Agregar dependencias de bundles VBP
        foreach ( $config_bundle['dependencies'] ?? array() as $dep_bundle ) {
            $dependencias[] = 'vbp-bundle-' . $dep_bundle;
        }

        // Agregar dependencias de vendor
        if ( 'js' === $tipo && 'vbp-core' === $nombre_bundle ) {
            $dependencias[] = 'sortablejs';
        }

        return $dependencias;
    }

    /**
     * Obtiene dependencias para un archivo individual
     *
     * @param string $archivo Ruta del archivo.
     * @param string $tipo    'js' o 'css'.
     * @return array
     */
    private function obtener_dependencias_archivo( $archivo, $tipo ) {
        $dependencias = array();
        $nombre_base  = basename( $archivo, ".{$tipo}" );

        // Mapeo de dependencias conocidas
        $mapeo_dependencias = array(
            'vbp-store.js'          => array( 'vbp-performance', 'vbp-store-catalog' ),
            'vbp-canvas.js'         => array( 'sortablejs', 'vbp-performance', 'vbp-canvas-utils' ),
            'vbp-inspector.js'      => array(),
            'vbp-symbols.js'        => array( 'vbp-store' ),
            'vbp-symbols-panel.js'  => array( 'vbp-symbols' ),
            'vbp-animation-builder.js' => array( 'vbp-store', 'vbp-command-palette' ),
        );

        $nombre_archivo = basename( $archivo );

        return $mapeo_dependencias[ $nombre_archivo ] ?? $dependencias;
    }

    /**
     * Genera los datos de configuracion para lazy loading
     *
     * @return array
     */
    public function generar_config_lazy_loading() {
        $config = array(
            'mode'          => $this->modo_carga,
            'baseUrl'       => $this->url_base,
            'bundles'       => array(),
            'triggers'      => $this->manifiesto['lazyLoad']['triggers'] ?? array(),
            'featureFlags'  => array(),
            'loadedBundles' => $this->bundles_cargados,
        );

        // Agregar informacion de bundles lazy
        foreach ( array( 'js', 'css' ) as $tipo ) {
            foreach ( $this->manifiesto['bundles'][ $tipo ] ?? array() as $nombre => $bundle_config ) {
                if ( ! empty( $bundle_config['lazy'] ) ) {
                    $archivo_bundle = 'bundled' === $this->modo_carga
                        ? "dist/{$nombre}.bundle.{$tipo}"
                        : null;

                    $config['bundles'][ $tipo ][ $nombre ] = array(
                        'file'         => $archivo_bundle,
                        'files'        => $bundle_config['files'] ?? array(),
                        'dependencies' => $bundle_config['dependencies'] ?? array(),
                        'trigger'      => $bundle_config['trigger'] ?? null,
                        'featureFlag'  => $bundle_config['featureFlag'] ?? null,
                    );
                }
            }
        }

        // Agregar feature flags activas
        foreach ( $this->feature_flags as $flag => $activa ) {
            if ( $activa && isset( $this->manifiesto['lazyLoad']['featureFlags'][ $flag ] ) ) {
                $config['featureFlags'][ $flag ] = $this->manifiesto['lazyLoad']['featureFlags'][ $flag ];
            }
        }

        return $config;
    }

    /**
     * Encola el script de lazy loader
     */
    public function encolar_lazy_loader() {
        $archivo_loader = 'bundled' === $this->modo_carga
            ? 'dist/vbp-loader.min.js'
            : 'build/vbp-loader-dev.js';

        $ruta_loader = $this->path_base . $archivo_loader;

        if ( file_exists( $ruta_loader ) ) {
            wp_enqueue_script(
                'vbp-lazy-loader',
                $this->url_base . $archivo_loader,
                array( 'vbp-bundle-vbp-core' ),
                self::VERSION,
                true
            );

            // Pasar configuracion al loader
            wp_localize_script(
                'vbp-lazy-loader',
                'VBP_LazyConfig',
                $this->generar_config_lazy_loading()
            );
        }
    }

    /**
     * Agrega preload hints para bundles criticos
     */
    public function agregar_preload_hints() {
        $preloads = $this->manifiesto['preload'] ?? array();

        foreach ( $preloads as $preload ) {
            $tipo   = $preload['type'];
            $bundle = $preload['bundle'];

            if ( 'bundled' === $this->modo_carga ) {
                $extension = 'script' === $tipo ? 'js' : 'css';
                $archivo   = "dist/{$bundle}.bundle.{$extension}";
                $as_value  = 'script' === $tipo ? 'script' : 'style';

                echo sprintf(
                    '<link rel="preload" href="%s" as="%s">',
                    esc_url( $this->url_base . $archivo ),
                    esc_attr( $as_value )
                );
            }
        }
    }

    /**
     * Obtiene estadisticas de bundles
     *
     * @return array
     */
    public function obtener_estadisticas() {
        $estadisticas = array(
            'modo'           => $this->modo_carga,
            'bundles_js'     => count( $this->manifiesto['bundles']['js'] ?? array() ),
            'bundles_css'    => count( $this->manifiesto['bundles']['css'] ?? array() ),
            'bundles_lazy'   => 0,
            'bundles_cargados' => count( $this->bundles_cargados ),
            'tamano_estimado' => 0,
        );

        // Contar bundles lazy
        foreach ( array( 'js', 'css' ) as $tipo ) {
            foreach ( $this->manifiesto['bundles'][ $tipo ] ?? array() as $config_bundle ) {
                if ( ! empty( $config_bundle['lazy'] ) ) {
                    $estadisticas['bundles_lazy']++;
                }
            }
        }

        return $estadisticas;
    }

    /**
     * Verifica si un bundle esta cargado
     *
     * @param string $nombre_bundle Nombre del bundle.
     * @param string $tipo          'js' o 'css'.
     * @return bool
     */
    public function esta_bundle_cargado( $nombre_bundle, $tipo = 'js' ) {
        return in_array( $tipo . ':' . $nombre_bundle, $this->bundles_cargados, true );
    }

    /**
     * Obtiene el manifiesto completo
     *
     * @return array
     */
    public function obtener_manifiesto() {
        return $this->manifiesto;
    }
}

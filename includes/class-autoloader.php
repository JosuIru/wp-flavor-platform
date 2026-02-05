<?php
/**
 * Autoloader PSR-4 para Flavor Platform
 *
 * Permite la carga automática de clases bajo demanda,
 * reduciendo la memoria utilizada y mejorando el rendimiento.
 *
 * @package FlavorPlatform
 * @subpackage Core
 * @since 3.0.0
 */

// Evitar acceso directo
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Clase Autoloader para carga automática de clases
 *
 * Implementa PSR-4 para cargar clases bajo demanda.
 * Convierte nombres de clase a rutas de archivo siguiendo convenciones WordPress.
 *
 * Ejemplo:
 * - Flavor_Chat_Core → includes/core/class-chat-core.php
 * - Flavor_Module_WooCommerce → includes/modules/woocommerce/class-woocommerce.php
 *
 * @since 3.0.0
 */
class Flavor_Autoloader {

    /**
     * Mapeo de prefijos a directorios base
     *
     * @var array
     */
    private static $prefix_map = [];

    /**
     * Clases ya cargadas (para evitar duplicados)
     *
     * @var array
     */
    private static $loaded_classes = [];

    /**
     * Modo debug
     *
     * @var bool
     */
    private static $debug = false;

    /**
     * Registra el autoloader
     *
     * @param bool $prepend Si debe agregarse al inicio de la cola
     * @return void
     */
    public static function register($prepend = false) {
        self::$debug = defined('FLAVOR_CHAT_IA_DEBUG') && FLAVOR_CHAT_IA_DEBUG;

        // Registrar mapeo de prefijos
        self::register_namespace('Flavor_', FLAVOR_CHAT_IA_PATH . 'includes/');

        // Registrar el autoloader SPL
        spl_autoload_register([__CLASS__, 'autoload'], true, $prepend);

        self::log('Autoloader registrado correctamente');
    }

    /**
     * Registra un namespace/prefijo con su directorio base
     *
     * @param string $prefijo Prefijo de clase (ej: 'Flavor_')
     * @param string $directorio_base Directorio base donde buscar
     * @return void
     */
    public static function register_namespace($prefijo, $directorio_base) {
        self::$prefix_map[$prefijo] = rtrim($directorio_base, '/') . '/';
        self::log("Namespace registrado: {$prefijo} -> {$directorio_base}");
    }

    /**
     * Función autoload principal
     *
     * @param string $nombre_clase Nombre completo de la clase
     * @return bool True si se cargó, false si no
     */
    public static function autoload($nombre_clase) {
        // Solo procesar clases con prefijo Flavor_
        if (strpos($nombre_clase, 'Flavor_') !== 0) {
            return false;
        }

        // Evitar cargar dos veces
        if (isset(self::$loaded_classes[$nombre_clase])) {
            return true;
        }

        // Intentar cargar desde los prefijos registrados
        foreach (self::$prefix_map as $prefijo => $directorio_base) {
            if (strpos($nombre_clase, $prefijo) === 0) {
                $ruta_archivo = self::get_file_path($nombre_clase, $prefijo, $directorio_base);

                if (self::load_file($ruta_archivo)) {
                    self::$loaded_classes[$nombre_clase] = $ruta_archivo;
                    self::log("Clase cargada: {$nombre_clase} desde {$ruta_archivo}");
                    return true;
                }
            }
        }

        self::log("No se pudo cargar la clase: {$nombre_clase}", 'warning');
        return false;
    }

    /**
     * Convierte nombre de clase a ruta de archivo
     *
     * Conversiones:
     * - Flavor_Chat_Core → core/class-chat-core.php
     * - Flavor_Module_WooCommerce → modules/woocommerce/class-woocommerce.php
     * - Flavor_Addon_Web_Builder → addons/web-builder/class-web-builder.php
     *
     * @param string $nombre_clase Nombre de la clase
     * @param string $prefijo Prefijo del namespace
     * @param string $directorio_base Directorio base
     * @return string Ruta del archivo
     */
    private static function get_file_path($nombre_clase, $prefijo, $directorio_base) {
        // Remover el prefijo
        $nombre_relativo = substr($nombre_clase, strlen($prefijo));

        // Dividir por guiones bajos
        $partes = explode('_', $nombre_relativo);

        // Casos especiales para la estructura de Flavor
        $carpeta_especial = '';
        $nombre_archivo = '';

        if (count($partes) >= 2) {
            $primer_segmento = strtolower($partes[0]);

            // Detectar carpeta según primer segmento
            switch ($primer_segmento) {
                case 'chat':
                    $carpeta_especial = 'core/';
                    // Flavor_Chat_Core → class-chat-core.php
                    $nombre_archivo = 'class-' . self::convert_to_filename($partes);
                    break;

                case 'module':
                    $carpeta_especial = 'modules/';
                    // Flavor_Module_WooCommerce → woocommerce/class-woocommerce.php
                    if (count($partes) >= 2) {
                        $nombre_modulo = strtolower($partes[1]);
                        $carpeta_especial .= $nombre_modulo . '/';
                        $nombre_archivo = 'class-' . self::convert_to_filename(array_slice($partes, 1));
                    }
                    break;

                case 'addon':
                    $carpeta_especial = 'addons/';
                    // Flavor_Addon_Web_Builder → web-builder/class-web-builder.php
                    if (count($partes) >= 2) {
                        $nombre_addon = self::convert_to_filename(array_slice($partes, 1));
                        $carpeta_especial .= $nombre_addon . '/';
                        $nombre_archivo = 'class-' . $nombre_addon . '.php';
                    }
                    break;

                case 'engine':
                    $carpeta_especial = 'engines/';
                    // Flavor_Engine_Manager → class-engine-manager.php
                    $nombre_archivo = 'class-' . self::convert_to_filename($partes);
                    break;

                case 'network':
                    $carpeta_especial = 'network/';
                    $nombre_archivo = 'class-' . self::convert_to_filename($partes);
                    break;

                case 'advertising':
                    $carpeta_especial = 'advertising/';
                    $nombre_archivo = 'class-' . self::convert_to_filename($partes);
                    break;

                case 'notification':
                    $carpeta_especial = 'notifications/';
                    $nombre_archivo = 'class-' . self::convert_to_filename($partes);
                    break;

                case 'webhook':
                    $carpeta_especial = 'webhooks/';
                    $nombre_archivo = 'class-' . self::convert_to_filename($partes);
                    break;

                case 'layout':
                    $carpeta_especial = 'layouts/';
                    $nombre_archivo = 'class-' . self::convert_to_filename($partes);
                    break;

                case 'web':
                    if (isset($partes[1]) && strtolower($partes[1]) === 'builder') {
                        $carpeta_especial = 'web-builder/';
                        $nombre_archivo = 'class-' . self::convert_to_filename($partes);
                    }
                    break;

                case 'app':
                    // Flavor_App_Integration → app-integration/class-app-integration.php
                    $carpeta_especial = 'app-integration/';
                    $nombre_archivo = 'class-' . self::convert_to_filename($partes);
                    break;

                case 'editor':
                    $carpeta_especial = 'editor/';
                    $nombre_archivo = 'class-' . self::convert_to_filename($partes);
                    break;

                case 'animation':
                    $carpeta_especial = 'animations/';
                    $nombre_archivo = 'class-' . self::convert_to_filename($partes);
                    break;

                case 'api':
                    $carpeta_especial = 'api/';
                    $nombre_archivo = 'class-' . self::convert_to_filename($partes);
                    break;

                default:
                    // Sin carpeta especial
                    $nombre_archivo = 'class-' . self::convert_to_filename($partes);
                    break;
            }
        } else {
            // Clase simple sin subcarpeta
            $nombre_archivo = 'class-' . self::convert_to_filename($partes);
        }

        return $directorio_base . $carpeta_especial . $nombre_archivo;
    }

    /**
     * Convierte array de partes a nombre de archivo
     *
     * ['Chat', 'Core'] → 'chat-core.php'
     * ['Module', 'WooCommerce'] → 'woocommerce.php'
     *
     * @param array $partes Partes del nombre
     * @return string Nombre de archivo
     */
    private static function convert_to_filename($partes) {
        $nombre = implode('-', array_map('strtolower', $partes));

        // Si no termina en .php, agregarlo
        if (substr($nombre, -4) !== '.php') {
            $nombre .= '.php';
        }

        return $nombre;
    }

    /**
     * Carga un archivo si existe
     *
     * @param string $ruta_archivo Ruta del archivo
     * @return bool True si se cargó, false si no existe
     */
    private static function load_file($ruta_archivo) {
        if (file_exists($ruta_archivo)) {
            require_once $ruta_archivo;
            return true;
        }

        return false;
    }

    /**
     * Obtiene las clases cargadas
     *
     * @return array Array de clases cargadas
     */
    public static function get_loaded_classes() {
        return self::$loaded_classes;
    }

    /**
     * Verifica si una clase fue cargada por el autoloader
     *
     * @param string $nombre_clase Nombre de la clase
     * @return bool True si fue cargada
     */
    public static function is_loaded($nombre_clase) {
        return isset(self::$loaded_classes[$nombre_clase]);
    }

    /**
     * Logging interno
     *
     * @param string $mensaje Mensaje a loguear
     * @param string $nivel Nivel de log
     * @return void
     */
    private static function log($mensaje, $nivel = 'info') {
        if (!self::$debug) {
            return;
        }

        if (function_exists('flavor_chat_ia_log')) {
            flavor_chat_ia_log('[Autoloader] ' . $mensaje, $nivel);
        }
    }
}

<?php
/**
 * Verificador de Dependencias para Flavor Platform
 *
 * Valida que todos los requisitos necesarios estén presentes
 * antes de activar módulos o addons.
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
 * Clase para verificar dependencias de módulos y addons
 *
 * Tipos de dependencias soportadas:
 * - Plugins de WordPress
 * - Versión de PHP
 * - Versión de WordPress
 * - Extensiones PHP
 * - Otros addons de Flavor
 * - Módulos de Flavor
 * - Funciones específicas
 *
 * @since 3.0.0
 */
class Flavor_Dependency_Checker {

    /**
     * Errores encontrados durante la verificación
     *
     * @var array
     */
    private static $errores = [];

    /**
     * Advertencias (dependencias opcionales no satisfechas)
     *
     * @var array
     */
    private static $advertencias = [];

    /**
     * Verifica todas las dependencias de un componente
     *
     * @param array $dependencias Array de dependencias en formato:
     *     [
     *         'required' => [
     *             'plugin:woocommerce' => ['name' => 'WooCommerce', 'version' => '5.0'],
     *             'php' => '7.4',
     *             'wordpress' => '5.8',
     *             'php_extension:curl' => true,
     *             'addon:web-builder' => ['version' => '1.0.0'],
     *             'module:marketplace' => true,
     *             'function:wp_json_encode' => true
     *         ],
     *         'optional' => [
     *             'plugin:wpml' => ['name' => 'WPML', 'feature' => 'Multiidioma']
     *         ]
     *     ]
     * @param string $nombre_componente Nombre del componente para mensajes de error
     * @return bool|WP_Error True si todas las dependencias están OK, WP_Error si no
     */
    public static function check($dependencias, $nombre_componente = '') {
        self::$errores = [];
        self::$advertencias = [];

        if (empty($dependencias)) {
            return true;
        }

        // Verificar dependencias requeridas
        if (!empty($dependencias['required'])) {
            foreach ($dependencias['required'] as $clave_dependencia => $configuracion) {
                $resultado = self::check_single_dependency($clave_dependencia, $configuracion, true);

                if (is_wp_error($resultado)) {
                    self::$errores[] = $resultado->get_error_message();
                }
            }
        }

        // Verificar dependencias opcionales
        if (!empty($dependencias['optional'])) {
            foreach ($dependencias['optional'] as $clave_dependencia => $configuracion) {
                $resultado = self::check_single_dependency($clave_dependencia, $configuracion, false);

                if (is_wp_error($resultado)) {
                    $caracteristica = isset($configuracion['feature']) ? $configuracion['feature'] : '';
                    self::$advertencias[] = [
                        'mensaje' => $resultado->get_error_message(),
                        'caracteristica' => $caracteristica
                    ];
                }
            }
        }

        // Si hay errores, retornar WP_Error
        if (!empty(self::$errores)) {
            $mensaje_error = sprintf(
                __('El componente "%s" no puede activarse debido a dependencias no satisfechas:', FLAVOR_PLATFORM_TEXT_DOMAIN),
                $nombre_componente
            );
            $mensaje_error .= "\n• " . implode("\n• ", self::$errores);

            return new WP_Error('dependencias_no_satisfechas', $mensaje_error);
        }

        return true;
    }

    /**
     * Verifica una dependencia individual
     *
     * @param string $clave Clave de la dependencia (ej: 'plugin:woocommerce', 'php', 'addon:web-builder')
     * @param mixed $configuracion Configuración de la dependencia
     * @param bool $es_requerida Si es requerida o opcional
     * @return bool|WP_Error True si OK, WP_Error si falla
     */
    private static function check_single_dependency($clave, $configuracion, $es_requerida) {
        // Detectar tipo de dependencia por el prefijo
        if (strpos($clave, 'plugin:') === 0) {
            return self::check_plugin_dependency($clave, $configuracion);
        }

        if (strpos($clave, 'addon:') === 0) {
            return self::check_addon_dependency($clave, $configuracion);
        }

        if (strpos($clave, 'module:') === 0) {
            return self::check_module_dependency($clave, $configuracion);
        }

        if (strpos($clave, 'php_extension:') === 0) {
            return self::check_php_extension_dependency($clave, $configuracion);
        }

        if (strpos($clave, 'function:') === 0) {
            return self::check_function_dependency($clave, $configuracion);
        }

        if ($clave === 'php') {
            return self::check_php_version($configuracion);
        }

        if ($clave === 'wordpress') {
            return self::check_wordpress_version($configuracion);
        }

        // Tipo de dependencia desconocido
        return new WP_Error('dependencia_desconocida', sprintf(
            __('Tipo de dependencia desconocido: %s', FLAVOR_PLATFORM_TEXT_DOMAIN),
            $clave
        ));
    }

    /**
     * Verifica dependencia de plugin
     *
     * @param string $clave 'plugin:slug-del-plugin'
     * @param array $configuracion ['name' => 'Nombre', 'version' => '1.0']
     * @return bool|WP_Error
     */
    private static function check_plugin_dependency($clave, $configuracion) {
        $slug_plugin = str_replace('plugin:', '', $clave);
        $nombre_plugin = isset($configuracion['name']) ? $configuracion['name'] : $slug_plugin;
        $version_requerida = isset($configuracion['version']) ? $configuracion['version'] : null;

        // Verificar si el plugin está activo
        if (!function_exists('is_plugin_active')) {
            require_once ABSPATH . 'wp-admin/includes/plugin.php';
        }

        // Intentar con el patrón común: slug/slug.php
        $archivo_plugin = $slug_plugin . '/' . $slug_plugin . '.php';

        if (!is_plugin_active($archivo_plugin)) {
            // Intentar variaciones comunes
            $variaciones = [
                $slug_plugin . '/' . $slug_plugin . '.php',
                $slug_plugin . '/index.php',
                $slug_plugin . '.php',
            ];

            $encontrado = false;
            foreach ($variaciones as $variacion) {
                if (is_plugin_active($variacion)) {
                    $archivo_plugin = $variacion;
                    $encontrado = true;
                    break;
                }
            }

            if (!$encontrado) {
                return new WP_Error('plugin_no_activo', sprintf(
                    __('El plugin "%s" debe estar instalado y activado.', FLAVOR_PLATFORM_TEXT_DOMAIN),
                    $nombre_plugin
                ));
            }
        }

        // Verificar versión si se especificó
        if ($version_requerida) {
            $datos_plugin = get_plugin_data(WP_PLUGIN_DIR . '/' . $archivo_plugin, false, false);
            $version_actual = isset($datos_plugin['Version']) ? $datos_plugin['Version'] : '0.0.0';

            if (version_compare($version_actual, $version_requerida, '<')) {
                return new WP_Error('plugin_version_antigua', sprintf(
                    __('El plugin "%s" requiere versión %s o superior (versión actual: %s).', FLAVOR_PLATFORM_TEXT_DOMAIN),
                    $nombre_plugin,
                    $version_requerida,
                    $version_actual
                ));
            }
        }

        return true;
    }

    /**
     * Verifica dependencia de addon
     *
     * @param string $clave 'addon:web-builder'
     * @param array $configuracion ['version' => '1.0.0']
     * @return bool|WP_Error
     */
    private static function check_addon_dependency($clave, $configuracion) {
        $slug_addon = str_replace('addon:', '', $clave);

        if (!class_exists('Flavor_Addon_Manager')) {
            return new WP_Error('addon_manager_no_disponible',
                __('El sistema de addons no está disponible.', FLAVOR_PLATFORM_TEXT_DOMAIN)
            );
        }

        if (!Flavor_Addon_Manager::is_addon_active($slug_addon)) {
            $nombre_addon = isset($configuracion['name']) ? $configuracion['name'] : $slug_addon;

            return new WP_Error('addon_no_activo', sprintf(
                __('El addon "%s" debe estar instalado y activado.', FLAVOR_PLATFORM_TEXT_DOMAIN),
                $nombre_addon
            ));
        }

        // Verificar versión si se especificó
        if (isset($configuracion['version'])) {
            $info_addon = Flavor_Addon_Manager::get_addon_info($slug_addon);
            $version_actual = isset($info_addon['version']) ? $info_addon['version'] : '0.0.0';

            if (version_compare($version_actual, $configuracion['version'], '<')) {
                return new WP_Error('addon_version_antigua', sprintf(
                    __('El addon "%s" requiere versión %s o superior (versión actual: %s).', FLAVOR_PLATFORM_TEXT_DOMAIN),
                    $slug_addon,
                    $configuracion['version'],
                    $version_actual
                ));
            }
        }

        return true;
    }

    /**
     * Verifica dependencia de módulo
     *
     * @param string $clave 'module:marketplace'
     * @param mixed $configuracion True o array con config
     * @return bool|WP_Error
     */
    private static function check_module_dependency($clave, $configuracion) {
        $id_modulo = str_replace('module:', '', $clave);

        if (!class_exists('Flavor_Chat_Module_Loader')) {
            return new WP_Error('module_loader_no_disponible',
                __('El sistema de módulos no está disponible.', FLAVOR_PLATFORM_TEXT_DOMAIN)
            );
        }

        if (!Flavor_Chat_Module_Loader::is_module_active($id_modulo)) {
            $nombre_modulo = is_array($configuracion) && isset($configuracion['name'])
                ? $configuracion['name']
                : $id_modulo;

            return new WP_Error('modulo_no_activo', sprintf(
                __('El módulo "%s" debe estar activado.', FLAVOR_PLATFORM_TEXT_DOMAIN),
                $nombre_modulo
            ));
        }

        return true;
    }

    /**
     * Verifica extensión PHP
     *
     * @param string $clave 'php_extension:curl'
     * @param mixed $configuracion True o array con config
     * @return bool|WP_Error
     */
    private static function check_php_extension_dependency($clave, $configuracion) {
        $nombre_extension = str_replace('php_extension:', '', $clave);

        if (!extension_loaded($nombre_extension)) {
            return new WP_Error('php_extension_no_disponible', sprintf(
                __('La extensión PHP "%s" es requerida.', FLAVOR_PLATFORM_TEXT_DOMAIN),
                $nombre_extension
            ));
        }

        return true;
    }

    /**
     * Verifica función PHP
     *
     * @param string $clave 'function:nombre_funcion'
     * @param mixed $configuracion True o array con config
     * @return bool|WP_Error
     */
    private static function check_function_dependency($clave, $configuracion) {
        $nombre_funcion = str_replace('function:', '', $clave);

        if (!function_exists($nombre_funcion)) {
            return new WP_Error('funcion_no_disponible', sprintf(
                __('La función "%s" es requerida.', FLAVOR_PLATFORM_TEXT_DOMAIN),
                $nombre_funcion
            ));
        }

        return true;
    }

    /**
     * Verifica versión de PHP
     *
     * @param string $version_requerida Versión mínima (ej: '7.4')
     * @return bool|WP_Error
     */
    private static function check_php_version($version_requerida) {
        $version_actual = PHP_VERSION;

        if (version_compare($version_actual, $version_requerida, '<')) {
            return new WP_Error('php_version_antigua', sprintf(
                __('Se requiere PHP versión %s o superior (versión actual: %s).', FLAVOR_PLATFORM_TEXT_DOMAIN),
                $version_requerida,
                $version_actual
            ));
        }

        return true;
    }

    /**
     * Verifica versión de WordPress
     *
     * @param string $version_requerida Versión mínima (ej: '5.8')
     * @return bool|WP_Error
     */
    private static function check_wordpress_version($version_requerida) {
        global $wp_version;

        if (version_compare($wp_version, $version_requerida, '<')) {
            return new WP_Error('wordpress_version_antigua', sprintf(
                __('Se requiere WordPress versión %s o superior (versión actual: %s).', FLAVOR_PLATFORM_TEXT_DOMAIN),
                $version_requerida,
                $wp_version
            ));
        }

        return true;
    }

    /**
     * Obtiene los errores de la última verificación
     *
     * @return array
     */
    public static function get_errors() {
        return self::$errores;
    }

    /**
     * Obtiene las advertencias de la última verificación
     *
     * @return array
     */
    public static function get_warnings() {
        return self::$advertencias;
    }

    /**
     * Verifica si hay advertencias
     *
     * @return bool
     */
    public static function has_warnings() {
        return !empty(self::$advertencias);
    }

    /**
     * Obtiene un mensaje formateado con las advertencias
     *
     * @return string
     */
    public static function get_warnings_message() {
        if (empty(self::$advertencias)) {
            return '';
        }

        $mensaje = __('Dependencias opcionales no disponibles:', FLAVOR_PLATFORM_TEXT_DOMAIN) . "\n";

        foreach (self::$advertencias as $advertencia) {
            $mensaje .= "• " . $advertencia['mensaje'];
            if (!empty($advertencia['caracteristica'])) {
                $mensaje .= sprintf(
                    __(' (Característica afectada: %s)', FLAVOR_PLATFORM_TEXT_DOMAIN),
                    $advertencia['caracteristica']
                );
            }
            $mensaje .= "\n";
        }

        return $mensaje;
    }
}

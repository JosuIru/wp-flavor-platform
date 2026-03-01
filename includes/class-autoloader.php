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
     * Mapeo especial para clases/traits con nombres de archivo diferentes
     * Formato: 'Nombre_Clase' => 'ruta/relativa/archivo.php'
     *
     * @var array
     */
    private static $special_mappings = [
        'Flavor_Module_Integration_Consumer' => 'modules/trait-module-integrations.php',
        'Flavor_Module_Dashboard_Tabs_Trait' => 'traits/trait-module-dashboard-tabs.php',
        'Flavor_Module_Tab_Integrations_Trait' => 'traits/trait-module-tab-integrations.php',
        'Flavor_Module_Notifications_Trait' => 'modules/trait-module-notifications.php',
        'Flavor_Module_Admin_UI_Trait' => 'modules/trait-module-admin-ui.php',
        'Flavor_Module_Frontend_Actions' => 'modules/trait-module-frontend-actions.php',
        'Flavor_Module_Admin_Pages_Trait' => 'admin/trait-module-admin-pages.php',
        'Flavor_Dashboard_Widget_Trait' => 'modules/trait-dashboard-widget.php',
        'Flavor_WhatsApp_Features' => 'modules/trait-whatsapp-features.php',
        'Flavor_Encuestas_Features' => 'modules/trait-encuestas-features.php',
    ];

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

        // Registrar el autoloader SPL para Flavor_*
        spl_autoload_register([__CLASS__, 'autoload'], true, $prepend);

        // Registrar autoloader PSR-4 para namespace Flavor_Chat_IA
        spl_autoload_register([__CLASS__, 'autoload_psr4'], true, $prepend);

        self::log('Autoloader registrado correctamente');
    }

    /**
     * Autoloader PSR-4 para namespace Flavor_Chat_IA
     *
     * @param string $class_name Nombre completo de la clase con namespace
     * @return bool
     */
    public static function autoload_psr4($class_name) {
        // Solo procesar clases del namespace Flavor_Chat_IA
        $namespace_prefix = 'Flavor_Chat_IA\\';

        if (strpos($class_name, $namespace_prefix) !== 0) {
            return false;
        }

        // Remover el prefijo del namespace
        $relative_class = substr($class_name, strlen($namespace_prefix));

        // Convertir namespace separators a directory separators
        // y convertir a minúsculas con guiones
        $parts = explode('\\', $relative_class);
        $class_file = 'class-' . strtolower(str_replace('_', '-', array_pop($parts))) . '.php';

        // Construir ruta del archivo
        $base_dir = FLAVOR_CHAT_IA_PATH . 'includes/';

        // Si hay subdirectorios en el namespace
        if (!empty($parts)) {
            $subdir = strtolower(implode('/', $parts)) . '/';
            $file_path = $base_dir . $subdir . $class_file;
        } else {
            $file_path = $base_dir . $class_file;
        }

        if (file_exists($file_path)) {
            require_once $file_path;
            self::$loaded_classes[$class_name] = $file_path;
            self::log("Clase PSR-4 cargada: {$class_name} desde {$file_path}");
            return true;
        }

        return false;
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
        // Validar que el nombre de clase no sea null o vacío
        if (empty($nombre_clase) || !is_string($nombre_clase)) {
            return false;
        }

        // Solo procesar clases con prefijo Flavor_
        if (strpos($nombre_clase, 'Flavor_') !== 0) {
            return false;
        }

        // Evitar cargar dos veces
        if (isset(self::$loaded_classes[$nombre_clase])) {
            return true;
        }

        // Verificar mapeo especial primero
        if (isset(self::$special_mappings[$nombre_clase])) {
            $ruta_archivo = FLAVOR_CHAT_IA_PATH . 'includes/' . self::$special_mappings[$nombre_clase];
            if (self::load_file($ruta_archivo)) {
                self::$loaded_classes[$nombre_clase] = $ruta_archivo;
                self::log("Clase cargada (mapeo especial): {$nombre_clase} desde {$ruta_archivo}");
                return true;
            }
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
                    // Verificar si es un Trait o similar
                    $ultimo_segmento = end($partes);
                    $trait_suffixes = ['trait', 'actions', 'integrations', 'features', 'notifications', 'consumer'];

                    if (in_array(strtolower($ultimo_segmento), $trait_suffixes)) {
                        // Es un trait de módulo
                        // Flavor_Module_Frontend_Actions → modules/trait-module-frontend-actions.php
                        $trait_name = strtolower(implode('-', $partes));

                        // Lista de ubicaciones posibles para traits
                        $trait_paths = [
                            'modules/trait-' . $trait_name . '.php',
                            'traits/trait-' . $trait_name . '.php',
                            'admin/trait-' . $trait_name . '.php',
                        ];

                        foreach ($trait_paths as $path) {
                            if (file_exists($directorio_base . $path)) {
                                return $directorio_base . $path;
                            }
                        }

                        // Fallback
                        $carpeta_especial = 'modules/';
                        $nombre_archivo = 'trait-' . $trait_name . '.php';
                    } elseif (count($partes) === 2 && in_array(strtolower($partes[1]), ['renderer', 'navigation', 'shortcodes', 'base'])) {
                        // Clases helper de módulos que están en la raíz de includes:
                        // Flavor_Module_Renderer → class-module-renderer.php
                        // Flavor_Module_Navigation → class-module-navigation.php
                        // Flavor_Module_Shortcodes → class-module-shortcodes.php
                        // Flavor_Module_Base → class-module-base.php
                        $carpeta_especial = '';
                        $nombre_archivo = 'class-' . self::convert_to_filename($partes);
                    } else {
                        // Módulo normal: Flavor_Module_WooCommerce → modules/woocommerce/class-woocommerce.php
                        $carpeta_especial = 'modules/';
                        if (count($partes) >= 2) {
                            $nombre_modulo = strtolower($partes[1]);
                            $carpeta_especial .= $nombre_modulo . '/';
                            $nombre_archivo = 'class-' . self::convert_to_filename(array_slice($partes, 1));
                        }
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
                    // Clases App pueden estar en diferentes ubicaciones
                    // Primero verificar si existe en la raíz de includes
                    $nombre_archivo_temp = 'class-' . self::convert_to_filename($partes);
                    if (file_exists($directorio_base . $nombre_archivo_temp)) {
                        // Flavor_App_Profiles → class-app-profiles.php
                        $carpeta_especial = '';
                        $nombre_archivo = $nombre_archivo_temp;
                    } else {
                        // Flavor_App_Integration → app-integration/class-app-integration.php
                        $carpeta_especial = 'app-integration/';
                        $nombre_archivo = $nombre_archivo_temp;
                    }
                    break;

                case 'editor':
                    $carpeta_especial = 'editor/';
                    $nombre_archivo = 'class-' . self::convert_to_filename($partes);
                    break;

                case 'mi':
                    // Flavor_Mi_Red_Social → frontend/class-mi-red-social.php
                    $carpeta_especial = 'frontend/';
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

                case 'reputation':
                    // Flavor_Reputation_Manager → class-reputation-manager.php
                    // Flavor_Reputation_API → api/class-reputation-api.php
                    $ultimo_segmento = end($partes);
                    if (strtolower($ultimo_segmento) === 'api') {
                        $carpeta_especial = 'api/';
                    } else {
                        $carpeta_especial = '';
                    }
                    $nombre_archivo = 'class-' . self::convert_to_filename($partes);
                    break;

                case 'analytics':
                    // Flavor_Analytics_Dashboard → admin/class-analytics-dashboard.php
                    $carpeta_especial = 'admin/';
                    $nombre_archivo = 'class-' . self::convert_to_filename($partes);
                    break;

                case 'report':
                    // Flavor_Report_Exporter → class-report-exporter.php
                    $carpeta_especial = '';
                    $nombre_archivo = 'class-' . self::convert_to_filename($partes);
                    break;

                case 'client':
                    // Flavor_Client_Dashboard_API → api/class-client-dashboard-api.php
                    $carpeta_especial = 'api/';
                    $nombre_archivo = 'class-' . self::convert_to_filename($partes);
                    break;

                case 'privacy':
                    $carpeta_especial = 'privacy/';
                    // Flavor_Privacy_Manager → class-privacy-manager.php
                    $nombre_archivo = 'class-' . self::convert_to_filename($partes);
                    break;

                case 'data':
                    // Flavor_Data_Exporter → privacy/class-data-exporter.php
                    $carpeta_especial = 'privacy/';
                    $nombre_archivo = 'class-' . self::convert_to_filename($partes);
                    break;

                case 'moderation':
                    // Flavor_Moderation_Manager → moderation/class-moderation-manager.php
                    $carpeta_especial = 'moderation/';
                    $nombre_archivo = 'class-' . self::convert_to_filename($partes);
                    break;

                case 'admin':
                case 'documentation':
                case 'demo':
                case 'unified':
                    // Clases de administración
                    // Flavor_Documentation_Page → admin/class-documentation-page.php
                    // Flavor_Demo_Data_Manager → admin/class-demo-data-manager.php
                    // Flavor_Unified_Admin_Panel → admin/class-unified-admin-panel.php
                    $carpeta_especial = 'admin/';
                    $nombre_archivo = 'class-' . self::convert_to_filename($partes);
                    break;

                case 'dashboard':
                    // Flavor_Dashboard_Widget_Trait → modules/trait-dashboard-widget.php
                    $ultimo_segmento = end($partes);
                    if (strtolower($ultimo_segmento) === 'trait') {
                        $trait_name = strtolower(implode('-', array_slice($partes, 0, -1)));
                        $carpeta_especial = 'modules/';
                        $nombre_archivo = 'trait-' . $trait_name . '.php';
                    } else {
                        $carpeta_especial = 'dashboard/';
                        $nombre_archivo = 'class-' . self::convert_to_filename($partes);
                    }
                    break;

                default:
                    // Verificar si es una API de módulo (ej: Flavor_Incidencias_API, Flavor_Espacios_Comunes_API)
                    $ultimo_segmento = end($partes);
                    $penultimo_segmento = count($partes) >= 2 ? $partes[count($partes) - 2] : '';

                    // Verificar si es Frontend_Controller de un módulo
                    // Flavor_Bicicletas_Compartidas_Frontend_Controller → modules/bicicletas-compartidas/frontend/class-...-frontend-controller.php
                    if (strtolower($ultimo_segmento) === 'controller' && strtolower($penultimo_segmento) === 'frontend' && count($partes) >= 3) {
                        $partes_modulo = array_slice($partes, 0, -2); // Quitar 'Frontend' y 'Controller'
                        $nombre_modulo = strtolower(implode('-', $partes_modulo));
                        $carpeta_especial = 'modules/' . $nombre_modulo . '/frontend/';
                        $nombre_archivo = 'class-' . $nombre_modulo . '-frontend-controller.php';
                    }
                    // Verificar si es Dashboard_Tab de un módulo
                    // Flavor_Cursos_Dashboard_Tab → modules/cursos/class-cursos-dashboard-tab.php
                    elseif (strtolower($ultimo_segmento) === 'tab' && strtolower($penultimo_segmento) === 'dashboard' && count($partes) >= 3) {
                        $partes_modulo = array_slice($partes, 0, -2); // Quitar 'Dashboard' y 'Tab'
                        $nombre_modulo = strtolower(implode('-', $partes_modulo));
                        $carpeta_especial = 'modules/' . $nombre_modulo . '/';
                        $nombre_archivo = 'class-' . $nombre_modulo . '-dashboard-tab.php';
                    }
                    // Verificar si es GC_* (Grupos Consumo - prefijo especial)
                    // Flavor_GC_Dashboard_Widget → modules/grupos-consumo/class-gc-dashboard-widget.php
                    // Flavor_GC_Membership → modules/grupos-consumo/class-gc-membership.php
                    elseif (strtolower($partes[0]) === 'gc' && count($partes) >= 2) {
                        $carpeta_especial = 'modules/grupos-consumo/';
                        $nombre_archivo = 'class-' . strtolower(implode('-', $partes)) . '.php';
                    }
                    // Verificar si es Widget de un módulo
                    // Flavor_Circulos_Cuidados_Widget → modules/circulos-cuidados/class-circulos-cuidados-widget.php
                    elseif (strtolower($ultimo_segmento) === 'widget' && count($partes) >= 2) {
                        $partes_modulo = array_slice($partes, 0, -1); // Quitar 'Widget'
                        $nombre_modulo = strtolower(implode('-', $partes_modulo));
                        $carpeta_especial = 'modules/' . $nombre_modulo . '/';
                        $nombre_archivo = 'class-' . $nombre_modulo . '-widget.php';
                    }
                    // Verificar si es una API de módulo
                    elseif (strtolower($ultimo_segmento) === 'api' && count($partes) >= 2) {
                        // Tomar todas las partes excepto 'API' para formar el nombre del módulo
                        // Flavor_Incidencias_API → modules/incidencias/class-incidencias-api.php
                        // Flavor_Espacios_Comunes_API → modules/espacios-comunes/class-espacios-comunes-api.php
                        $partes_modulo = array_slice($partes, 0, -1); // Quitar 'API'
                        $nombre_modulo = strtolower(implode('-', $partes_modulo));
                        $carpeta_especial = 'modules/' . $nombre_modulo . '/';
                        $nombre_archivo = 'class-' . $nombre_modulo . '-api.php';
                    }
                    // Verificar si es Push_* (clases de notificaciones push)
                    // Flavor_Push_Token_Manager → notifications/class-push-token-manager.php
                    elseif (strtolower($partes[0]) === 'push' && count($partes) >= 2) {
                        $carpeta_especial = 'notifications/';
                        $nombre_archivo = 'class-' . strtolower(implode('-', $partes)) . '.php';
                    }
                    // Verificar si es un Trait de módulos
                    // Flavor_WhatsApp_Features → modules/trait-whatsapp-features.php
                    // Flavor_Encuestas_Features → modules/trait-encuestas-features.php
                    // Flavor_Dashboard_Widget_Trait → modules/trait-dashboard-widget.php
                    // Flavor_Module_Frontend_Actions → modules/trait-module-frontend-actions.php
                    elseif (strtolower($ultimo_segmento) === 'features' ||
                            strtolower($ultimo_segmento) === 'trait' ||
                            strtolower($ultimo_segmento) === 'actions' ||
                            strtolower($ultimo_segmento) === 'integrations') {
                        $carpeta_especial = 'modules/';
                        $nombre_archivo = 'trait-' . strtolower(implode('-', $partes)) . '.php';
                    }
                    // Verificar si es Feature_* (sistema de features compartidas)
                    // Flavor_Feature_Ratings → features/class-feature-ratings.php
                    elseif (strtolower($partes[0]) === 'feature' && count($partes) >= 2) {
                        $carpeta_especial = 'features/';
                        $nombre_archivo = 'class-' . strtolower(implode('-', $partes)) . '.php';
                    }
                    else {
                        // Sin carpeta especial - buscar en raíz de includes
                        $nombre_archivo = 'class-' . self::convert_to_filename($partes);
                    }
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
        // Validar que la ruta no sea null, vacía o inválida
        if (empty($ruta_archivo) || !is_string($ruta_archivo)) {
            return false;
        }

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

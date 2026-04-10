<?php
/**
 * Manejador de errores PHP para el módulo Bug Tracker
 *
 * @package Flavor_Platform
 * @subpackage Bug_Tracker
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Clase que captura automáticamente errores PHP
 *
 * Implementa set_error_handler, set_exception_handler y register_shutdown_function
 * para capturar todos los errores de los plugins Flavor.
 */
class Flavor_Bug_Tracker_Error_Handler {

    /**
     * Instancia del módulo principal
     *
     * @var Flavor_Bug_Tracker_Module
     */
    private $modulo;

    /**
     * Handlers originales para restaurar
     *
     * @var array
     */
    private $handlers_originales = [];

    /**
     * Plugins a monitorizar
     *
     * @var array
     */
    private $plugins_monitorizados = [];

    /**
     * Errores ya procesados (para evitar duplicados en shutdown)
     *
     * @var array
     */
    private $errores_procesados = [];

    /**
     * Mapeo de códigos de error a severidad
     *
     * @var array
     */
    private $mapeo_severidad = [
        E_ERROR => 'critical',
        E_PARSE => 'critical',
        E_CORE_ERROR => 'critical',
        E_COMPILE_ERROR => 'critical',
        E_USER_ERROR => 'critical',
        E_RECOVERABLE_ERROR => 'high',
        E_WARNING => 'medium',
        E_CORE_WARNING => 'medium',
        E_COMPILE_WARNING => 'medium',
        E_USER_WARNING => 'medium',
        E_NOTICE => 'low',
        E_USER_NOTICE => 'low',
        E_STRICT => 'info',
        E_DEPRECATED => 'info',
        E_USER_DEPRECATED => 'info',
    ];

    /**
     * Mapeo de códigos de error a tipo
     *
     * @var array
     */
    private $mapeo_tipo = [
        E_ERROR => 'error_php',
        E_PARSE => 'crash',
        E_CORE_ERROR => 'crash',
        E_COMPILE_ERROR => 'crash',
        E_USER_ERROR => 'error_php',
        E_RECOVERABLE_ERROR => 'error_php',
        E_WARNING => 'warning',
        E_CORE_WARNING => 'warning',
        E_COMPILE_WARNING => 'warning',
        E_USER_WARNING => 'warning',
        E_NOTICE => 'notice',
        E_USER_NOTICE => 'notice',
        E_STRICT => 'warning',
        E_DEPRECATED => 'deprecation',
        E_USER_DEPRECATED => 'deprecation',
    ];

    /**
     * Constructor
     *
     * @param Flavor_Bug_Tracker_Module $modulo Instancia del módulo
     */
    public function __construct(Flavor_Bug_Tracker_Module $modulo) {
        $this->modulo = $modulo;
        $this->plugins_monitorizados = $modulo->get_setting('plugins_monitorizados') ?: [];

        $this->registrar_handlers();
    }

    /**
     * Registra los handlers de errores
     *
     * @return void
     */
    private function registrar_handlers() {
        // Guardar handlers originales
        $this->handlers_originales['error'] = set_error_handler([$this, 'manejar_error']);
        $this->handlers_originales['exception'] = set_exception_handler([$this, 'manejar_excepcion']);

        // Registrar shutdown function para errores fatales
        register_shutdown_function([$this, 'manejar_shutdown']);
    }

    /**
     * Maneja errores PHP
     *
     * @param int    $errno Número de error
     * @param string $errstr Mensaje de error
     * @param string $errfile Archivo donde ocurrió
     * @param int    $errline Línea donde ocurrió
     * @return bool
     */
    public function manejar_error($errno, $errstr, $errfile, $errline) {
        // Verificar si debemos capturar este tipo de error
        if (!$this->debe_capturar_error($errno, $errfile)) {
            // Llamar al handler original si existe
            if ($this->handlers_originales['error']) {
                return call_user_func($this->handlers_originales['error'], $errno, $errstr, $errfile, $errline);
            }
            return false;
        }

        // Verificar si ya procesamos este error
        $hash_error = md5($errfile . $errline . $errstr);
        if (isset($this->errores_procesados[$hash_error])) {
            return true;
        }
        $this->errores_procesados[$hash_error] = true;

        // Preparar datos del error
        $tipo = isset($this->mapeo_tipo[$errno]) ? $this->mapeo_tipo[$errno] : 'error_php';
        $severidad = isset($this->mapeo_severidad[$errno]) ? $this->mapeo_severidad[$errno] : 'medium';

        $titulo = $this->formatear_titulo_error($errno, $errstr);
        $stack_trace = $this->capturar_stack_trace();
        $modulo_id = $this->detectar_modulo($errfile);

        $hash_fingerprint = $this->modulo->generar_fingerprint($tipo, $errstr, $errfile, $errline);

        // Registrar el bug
        $this->modulo->registrar_bug([
            'tipo' => $tipo,
            'severidad' => $severidad,
            'titulo' => $titulo,
            'mensaje' => $errstr,
            'archivo' => $errfile,
            'linea' => $errline,
            'stack_trace' => $stack_trace,
            'modulo_id' => $modulo_id,
            'hash_fingerprint' => $hash_fingerprint,
        ]);

        // Llamar al handler original si existe (para que WP siga su flujo normal)
        if ($this->handlers_originales['error']) {
            return call_user_func($this->handlers_originales['error'], $errno, $errstr, $errfile, $errline);
        }

        return true;
    }

    /**
     * Maneja excepciones no capturadas
     *
     * @param Throwable $excepcion Excepción o Error
     * @return void
     */
    public function manejar_excepcion($excepcion) {
        $archivo = $excepcion->getFile();
        $linea = $excepcion->getLine();
        $mensaje = $excepcion->getMessage();

        // Verificar si es de un plugin Flavor
        if (!$this->es_error_flavor($archivo)) {
            // Llamar al handler original si existe
            if ($this->handlers_originales['exception']) {
                call_user_func($this->handlers_originales['exception'], $excepcion);
            }
            return;
        }

        $clase_excepcion = get_class($excepcion);
        $titulo = "[{$clase_excepcion}] " . mb_substr($mensaje, 0, 200);

        $stack_trace = $excepcion->getTraceAsString();
        $modulo_id = $this->detectar_modulo($archivo);

        $hash_fingerprint = $this->modulo->generar_fingerprint('exception', $mensaje, $archivo, $linea);

        // Determinar severidad según el tipo de excepción
        $severidad = $this->determinar_severidad_excepcion($excepcion);

        // Registrar el bug
        $this->modulo->registrar_bug([
            'tipo' => 'exception',
            'severidad' => $severidad,
            'titulo' => $titulo,
            'mensaje' => $mensaje,
            'archivo' => $archivo,
            'linea' => $linea,
            'stack_trace' => $stack_trace,
            'modulo_id' => $modulo_id,
            'hash_fingerprint' => $hash_fingerprint,
            'contexto_extra' => [
                'clase_excepcion' => $clase_excepcion,
                'codigo_excepcion' => $excepcion->getCode(),
            ],
        ]);

        // Llamar al handler original
        if ($this->handlers_originales['exception']) {
            call_user_func($this->handlers_originales['exception'], $excepcion);
        }
    }

    /**
     * Maneja errores fatales en shutdown
     *
     * @return void
     */
    public function manejar_shutdown() {
        $error = error_get_last();

        if ($error === null) {
            return;
        }

        // Solo capturar errores fatales
        $errores_fatales = [E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR];
        if (!in_array($error['type'], $errores_fatales)) {
            return;
        }

        // Verificar si es de un plugin Flavor
        if (!$this->es_error_flavor($error['file'])) {
            return;
        }

        // Verificar si ya procesamos este error
        $hash_error = md5($error['file'] . $error['line'] . $error['message']);
        if (isset($this->errores_procesados[$hash_error])) {
            return;
        }

        $titulo = $this->formatear_titulo_error($error['type'], $error['message']);
        $modulo_id = $this->detectar_modulo($error['file']);

        $hash_fingerprint = $this->modulo->generar_fingerprint('crash', $error['message'], $error['file'], $error['line']);

        // Registrar el bug
        $this->modulo->registrar_bug([
            'tipo' => 'crash',
            'severidad' => 'critical',
            'titulo' => $titulo,
            'mensaje' => $error['message'],
            'archivo' => $error['file'],
            'linea' => $error['line'],
            'stack_trace' => null,
            'modulo_id' => $modulo_id,
            'hash_fingerprint' => $hash_fingerprint,
        ]);
    }

    /**
     * Verifica si el error es de un plugin Flavor
     *
     * @param string $archivo Ruta del archivo
     * @return bool
     */
    private function es_error_flavor($archivo) {
        if (empty($archivo)) {
            return false;
        }

        foreach ($this->plugins_monitorizados as $plugin) {
            if (strpos($archivo, '/plugins/' . $plugin . '/') !== false) {
                return true;
            }
        }

        return false;
    }

    /**
     * Determina si se debe capturar el error
     *
     * @param int    $errno Código de error
     * @param string $archivo Archivo donde ocurrió
     * @return bool
     */
    private function debe_capturar_error($errno, $archivo) {
        // Verificar si es de un plugin Flavor
        if (!$this->es_error_flavor($archivo)) {
            return false;
        }

        // Aplicar filtro para permitir personalización
        if (!apply_filters('flavor_bug_tracker_debe_capturar', true, $errno, $archivo)) {
            return false;
        }

        // Verificar configuración según tipo de error
        $errores_siempre = [E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR, E_USER_ERROR, E_RECOVERABLE_ERROR];
        if (in_array($errno, $errores_siempre)) {
            return true;
        }

        // Warnings
        $errores_warning = [E_WARNING, E_CORE_WARNING, E_COMPILE_WARNING, E_USER_WARNING, E_STRICT];
        if (in_array($errno, $errores_warning) && !$this->modulo->get_setting('capturar_warnings')) {
            return false;
        }

        // Notices
        $errores_notice = [E_NOTICE, E_USER_NOTICE];
        if (in_array($errno, $errores_notice) && !$this->modulo->get_setting('capturar_notices')) {
            return false;
        }

        // Deprecations
        $errores_deprecated = [E_DEPRECATED, E_USER_DEPRECATED];
        if (in_array($errno, $errores_deprecated) && !$this->modulo->get_setting('capturar_deprecations')) {
            return false;
        }

        return true;
    }

    /**
     * Formatea el título del error
     *
     * @param int    $errno Código de error
     * @param string $mensaje Mensaje de error
     * @return string
     */
    private function formatear_titulo_error($errno, $mensaje) {
        $nombres_error = [
            E_ERROR => 'E_ERROR',
            E_PARSE => 'E_PARSE',
            E_CORE_ERROR => 'E_CORE_ERROR',
            E_COMPILE_ERROR => 'E_COMPILE_ERROR',
            E_USER_ERROR => 'E_USER_ERROR',
            E_RECOVERABLE_ERROR => 'E_RECOVERABLE_ERROR',
            E_WARNING => 'E_WARNING',
            E_CORE_WARNING => 'E_CORE_WARNING',
            E_COMPILE_WARNING => 'E_COMPILE_WARNING',
            E_USER_WARNING => 'E_USER_WARNING',
            E_NOTICE => 'E_NOTICE',
            E_USER_NOTICE => 'E_USER_NOTICE',
            E_STRICT => 'E_STRICT',
            E_DEPRECATED => 'E_DEPRECATED',
            E_USER_DEPRECATED => 'E_USER_DEPRECATED',
        ];

        $nombre_error = isset($nombres_error[$errno]) ? $nombres_error[$errno] : 'ERROR';
        $mensaje_corto = mb_substr($mensaje, 0, 200);

        return "[{$nombre_error}] {$mensaje_corto}";
    }

    /**
     * Captura el stack trace actual
     *
     * @return string
     */
    private function capturar_stack_trace() {
        $trace = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 20);

        // Eliminar las primeras entradas que son del propio handler
        $trace = array_slice($trace, 3);

        $lineas_trace = [];
        foreach ($trace as $indice => $frame) {
            $archivo = isset($frame['file']) ? $frame['file'] : '[internal]';
            $linea = isset($frame['line']) ? $frame['line'] : '?';
            $funcion = isset($frame['function']) ? $frame['function'] : '';
            $clase = isset($frame['class']) ? $frame['class'] : '';
            $tipo = isset($frame['type']) ? $frame['type'] : '';

            $llamada = $clase ? "{$clase}{$tipo}{$funcion}()" : "{$funcion}()";

            $lineas_trace[] = "#{$indice} {$archivo}({$linea}): {$llamada}";
        }

        return implode("\n", $lineas_trace);
    }

    /**
     * Detecta el módulo origen del error
     *
     * @param string $archivo Ruta del archivo
     * @return string|null
     */
    private function detectar_modulo($archivo) {
        // Buscar en la ruta del archivo el nombre del módulo
        if (preg_match('/\/modules\/([a-z0-9-]+)\//', $archivo, $coincidencias)) {
            return $coincidencias[1];
        }

        // Si no es un módulo específico, detectar el plugin
        foreach ($this->plugins_monitorizados as $plugin) {
            if (strpos($archivo, '/plugins/' . $plugin . '/') !== false) {
                return $plugin;
            }
        }

        return null;
    }

    /**
     * Determina la severidad de una excepción
     *
     * @param Throwable $excepcion
     * @return string
     */
    private function determinar_severidad_excepcion($excepcion) {
        $clase = get_class($excepcion);

        // Excepciones críticas
        $excepciones_criticas = [
            'Error',
            'TypeError',
            'ParseError',
            'CompileError',
            'PDOException',
            'mysqli_sql_exception',
        ];

        foreach ($excepciones_criticas as $tipo_critico) {
            if ($excepcion instanceof $tipo_critico || $clase === $tipo_critico) {
                return 'critical';
            }
        }

        // Excepciones de alta severidad
        $excepciones_high = [
            'RuntimeException',
            'LogicException',
            'InvalidArgumentException',
            'OutOfBoundsException',
        ];

        foreach ($excepciones_high as $tipo_high) {
            if ($excepcion instanceof $tipo_high || $clase === $tipo_high) {
                return 'high';
            }
        }

        return 'medium';
    }

    /**
     * Restaura los handlers originales
     *
     * @return void
     */
    public function restaurar_handlers() {
        restore_error_handler();
        restore_exception_handler();
    }
}

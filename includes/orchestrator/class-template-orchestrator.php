<?php
/**
 * Template Orchestrator
 *
 * Orquesta la activacion, desactivacion y gestion de plantillas completas
 * coordinando todos los componentes involucrados: modulos, paginas, landing, config, demo
 *
 * @package FlavorChatIA
 * @subpackage Orchestrator
 */

if (!defined('ABSPATH')) {
    exit;
}

// Cargar el loader de componentes
require_once dirname(__FILE__) . '/class-components-loader.php';

/**
 * Clase Flavor_Template_Orchestrator
 *
 * Singleton que coordina la instalacion/desinstalacion de plantillas
 */
class Flavor_Template_Orchestrator {

    /**
     * Instancia singleton
     *
     * @var Flavor_Template_Orchestrator|null
     */
    private static $instancia = null;

    /**
     * Componentes registrados
     *
     * @var array
     */
    private $componentes = [];

    /**
     * Orden de ejecucion de componentes para instalacion
     *
     * @var array
     */
    private $orden_instalacion = [
        'modulos',
        'tablas',
        'paginas',
        'landing',
        'configuracion',
        'demo',
    ];

    /**
     * Errores acumulados durante la operacion
     *
     * @var array
     */
    private $errores = [];

    /**
     * Advertencias acumuladas durante la operacion
     *
     * @var array
     */
    private $advertencias = [];

    /**
     * Obtiene la instancia singleton
     *
     * @return Flavor_Template_Orchestrator
     */
    public static function get_instance() {
        if (self::$instancia === null) {
            self::$instancia = new self();
        }
        return self::$instancia;
    }

    /**
     * Constructor privado
     */
    private function __construct() {
        $this->registrar_componentes_base();
        $this->init_hooks();
    }

    /**
     * Inicializa los hooks
     */
    private function init_hooks() {
        // Hooks para AJAX si es necesario
        add_action('wp_ajax_flavor_activar_plantilla', [$this, 'ajax_activar_plantilla']);
        add_action('wp_ajax_flavor_desactivar_plantilla', [$this, 'ajax_desactivar_plantilla']);
        add_action('wp_ajax_flavor_preview_plantilla', [$this, 'ajax_preview_plantilla']);
    }

    /**
     * Registra los componentes base del sistema
     */
    private function registrar_componentes_base() {
        // Los componentes se pueden registrar desde fuera via filtros
        $this->componentes = apply_filters('flavor_template_components', []);
    }

    /**
     * Registra un nuevo componente
     *
     * @param string $nombre Nombre del componente
     * @param Flavor_Template_Component_Interface $componente Instancia del componente
     * @return bool
     */
    public function registrar_componente($nombre, Flavor_Template_Component_Interface $componente) {
        $this->componentes[$nombre] = $componente;
        return true;
    }

    /**
     * Activa una plantilla completa
     *
     * @param string $plantilla_id ID de la plantilla
     * @param array  $opciones     Opciones de activacion
     * @return array Resultado detallado
     */
    public function activar_plantilla($plantilla_id, $opciones = []) {
        $this->limpiar_estado();

        // Opciones por defecto
        $opciones = wp_parse_args($opciones, [
            'instalar_paginas' => true,
            'instalar_landing' => true,
            'aplicar_config' => true,
            'instalar_demo' => false,
            'modulos_opcionales' => [],
            'forzar' => false,
        ]);

        /**
         * Hook antes de activar plantilla
         *
         * @param string $plantilla_id ID de la plantilla
         * @param array $opciones Opciones de activacion
         */
        do_action('flavor_antes_activar_plantilla', $plantilla_id, $opciones);

        // Obtener definicion
        $definiciones = Flavor_Template_Definitions::get_instance();
        $definicion = $definiciones->obtener_definicion($plantilla_id);

        if (!$definicion) {
            return $this->respuesta_error(
                sprintf(__('Plantilla no encontrada: %s', 'flavor-chat-ia'), $plantilla_id)
            );
        }

        // Verificar si ya esta activa
        if ($this->esta_activa($plantilla_id) && !$opciones['forzar']) {
            return $this->respuesta_error(
                __('Esta plantilla ya esta activa. Usa la opcion forzar para reinstalar.', 'flavor-chat-ia')
            );
        }

        // Crear snapshot del estado actual para posible rollback
        $snapshot = $this->crear_snapshot_estado($plantilla_id);

        // Resultado acumulado
        $resultado = [
            'plantilla_id' => $plantilla_id,
            'nombre' => $definicion['nombre'],
            'componentes' => [],
            'tiempo_inicio' => microtime(true),
        ];

        // Ejecutar componentes en orden
        foreach ($this->orden_instalacion as $componente_nombre) {
            // Verificar si este componente debe ejecutarse segun opciones
            if (!$this->debe_ejecutar_componente($componente_nombre, $opciones)) {
                continue;
            }

            // Obtener la parte de la definicion para este componente
            $definicion_componente = $this->obtener_definicion_componente($definicion, $componente_nombre, $opciones);

            if (empty($definicion_componente)) {
                continue;
            }

            $resultado_componente = $this->ejecutar_componente_instalacion(
                $componente_nombre,
                $plantilla_id,
                $definicion_componente,
                $opciones
            );

            $resultado['componentes'][$componente_nombre] = $resultado_componente;

            // Si falla un componente critico, detener
            if (!$resultado_componente['success'] && $this->es_componente_critico($componente_nombre)) {
                $this->registrar_error(
                    'componente_critico_fallo',
                    sprintf(__('Componente critico fallo: %s', 'flavor-chat-ia'), $componente_nombre),
                    $resultado_componente
                );

                // Intentar rollback
                $this->rollback($plantilla_id, $snapshot);

                $resultado['success'] = false;
                $resultado['message'] = __('La activacion fallo y se ha revertido', 'flavor-chat-ia');
                $resultado['errores'] = $this->errores;
                $resultado['tiempo_total'] = microtime(true) - $resultado['tiempo_inicio'];

                return $resultado;
            }

            /**
             * Hook despues de instalar cada componente
             *
             * @param string $componente_nombre Nombre del componente
             * @param string $plantilla_id ID de la plantilla
             * @param array $resultado_componente Resultado del componente
             */
            do_action('flavor_componente_instalado', $componente_nombre, $plantilla_id, $resultado_componente);
        }

        // Marcar plantilla como activa
        $this->marcar_plantilla_activa($plantilla_id, $definicion, $opciones);

        // Registrar en activity log si existe
        $this->registrar_actividad('plantilla_activada', [
            'plantilla_id' => $plantilla_id,
            'nombre' => $definicion['nombre'],
            'opciones' => $opciones,
        ]);

        $resultado['success'] = true;
        $resultado['message'] = sprintf(
            __('Plantilla "%s" activada correctamente', 'flavor-chat-ia'),
            $definicion['nombre']
        );
        $resultado['advertencias'] = $this->advertencias;
        $resultado['tiempo_total'] = microtime(true) - $resultado['tiempo_inicio'];

        /**
         * Hook despues de activar plantilla
         *
         * @param string $plantilla_id ID de la plantilla
         * @param array $resultado Resultado de la activacion
         */
        do_action('flavor_plantilla_activada', $plantilla_id, $resultado);

        return $resultado;
    }

    /**
     * Desactiva una plantilla
     *
     * @param string $plantilla_id ID de la plantilla
     * @param array  $opciones     Opciones de desactivacion
     * @return array Resultado detallado
     */
    public function desactivar_plantilla($plantilla_id, $opciones = []) {
        $this->limpiar_estado();

        // Opciones por defecto
        $opciones = wp_parse_args($opciones, [
            'eliminar_paginas' => false,
            'eliminar_datos' => false,
            'mantener_config' => true,
        ]);

        /**
         * Hook antes de desactivar plantilla
         *
         * @param string $plantilla_id ID de la plantilla
         * @param array $opciones Opciones de desactivacion
         */
        do_action('flavor_antes_desactivar_plantilla', $plantilla_id, $opciones);

        // Verificar si esta activa
        if (!$this->esta_activa($plantilla_id)) {
            return $this->respuesta_error(
                __('Esta plantilla no esta activa', 'flavor-chat-ia')
            );
        }

        $definiciones = Flavor_Template_Definitions::get_instance();
        $definicion = $definiciones->obtener_definicion($plantilla_id);

        $resultado = [
            'plantilla_id' => $plantilla_id,
            'nombre' => $definicion['nombre'] ?? $plantilla_id,
            'componentes' => [],
            'tiempo_inicio' => microtime(true),
        ];

        // Ejecutar desinstalacion en orden inverso
        $orden_inverso = array_reverse($this->orden_instalacion);

        foreach ($orden_inverso as $componente_nombre) {
            if (!isset($this->componentes[$componente_nombre])) {
                continue;
            }

            $definicion_componente = $this->obtener_definicion_componente($definicion, $componente_nombre, $opciones);

            $resultado_componente = $this->ejecutar_componente_desinstalacion(
                $componente_nombre,
                $plantilla_id,
                $definicion_componente,
                $opciones
            );

            $resultado['componentes'][$componente_nombre] = $resultado_componente;
        }

        // Desmarcar plantilla como activa
        $this->desmarcar_plantilla_activa($plantilla_id);

        // Registrar en activity log
        $this->registrar_actividad('plantilla_desactivada', [
            'plantilla_id' => $plantilla_id,
            'opciones' => $opciones,
        ]);

        $resultado['success'] = true;
        $resultado['message'] = sprintf(
            __('Plantilla "%s" desactivada correctamente', 'flavor-chat-ia'),
            $definicion['nombre'] ?? $plantilla_id
        );
        $resultado['tiempo_total'] = microtime(true) - $resultado['tiempo_inicio'];

        /**
         * Hook despues de desactivar plantilla
         *
         * @param string $plantilla_id ID de la plantilla
         * @param array $resultado Resultado de la desactivacion
         */
        do_action('flavor_plantilla_desactivada', $plantilla_id, $resultado);

        return $resultado;
    }

    /**
     * Obtiene preview de lo que se instalara
     *
     * @param string $plantilla_id ID de la plantilla
     * @param array  $opciones     Opciones
     * @return array Preview detallado
     */
    public function obtener_preview($plantilla_id, $opciones = []) {
        $definiciones = Flavor_Template_Definitions::get_instance();
        $definicion = $definiciones->obtener_definicion($plantilla_id);

        if (!$definicion) {
            return [
                'success' => false,
                'message' => sprintf(__('Plantilla no encontrada: %s', 'flavor-chat-ia'), $plantilla_id),
            ];
        }

        $opciones = wp_parse_args($opciones, [
            'instalar_paginas' => true,
            'instalar_landing' => true,
            'aplicar_config' => true,
            'instalar_demo' => false,
            'modulos_opcionales' => [],
        ]);

        $preview = [
            'plantilla_id' => $plantilla_id,
            'nombre' => $definicion['nombre'],
            'descripcion' => $definicion['descripcion'],
            'icono' => $definicion['icono'],
            'color' => $definicion['color'],
            'componentes' => [],
        ];

        // Modulos
        $modulos_requeridos = $definicion['modulos']['requeridos'] ?? [];
        $modulos_opcionales_seleccionados = array_intersect(
            $definicion['modulos']['opcionales'] ?? [],
            $opciones['modulos_opcionales']
        );

        $preview['componentes']['modulos'] = [
            'requeridos' => $modulos_requeridos,
            'opcionales_seleccionados' => $modulos_opcionales_seleccionados,
            'total' => count($modulos_requeridos) + count($modulos_opcionales_seleccionados),
        ];

        // Paginas
        if ($opciones['instalar_paginas']) {
            $paginas = $definicion['paginas'] ?? [];
            $preview['componentes']['paginas'] = [
                'total' => count($paginas),
                'lista' => array_map(function($pagina) {
                    return [
                        'titulo' => $pagina['titulo'],
                        'slug' => $pagina['slug'],
                        'es_landing' => $pagina['es_landing'] ?? false,
                    ];
                }, $paginas),
            ];
        }

        // Landing
        if ($opciones['instalar_landing'] && !empty($definicion['landing']['activa'])) {
            $secciones = $definicion['landing']['secciones'] ?? [];
            $preview['componentes']['landing'] = [
                'activa' => true,
                'secciones' => count($secciones),
                'tipos' => array_unique(array_column($secciones, 'tipo')),
            ];
        }

        // Configuracion
        if ($opciones['aplicar_config']) {
            $config = $definicion['configuracion'] ?? [];
            $preview['componentes']['configuracion'] = [
                'modulos_afectados' => array_keys($config),
                'total_opciones' => array_sum(array_map('count', $config)),
            ];
        }

        // Demo
        $preview['componentes']['demo'] = [
            'disponible' => $definicion['demo']['disponible'] ?? false,
            'descripcion' => $definicion['demo']['descripcion'] ?? '',
            'se_instalara' => $opciones['instalar_demo'] && ($definicion['demo']['disponible'] ?? false),
        ];

        $preview['success'] = true;

        return $preview;
    }

    /**
     * Obtiene el estado actual de una plantilla
     *
     * @param string $plantilla_id ID de la plantilla
     * @return array Estado detallado
     */
    public function obtener_estado($plantilla_id) {
        $definiciones = Flavor_Template_Definitions::get_instance();
        $definicion = $definiciones->obtener_definicion($plantilla_id);

        if (!$definicion) {
            return [
                'success' => false,
                'existe' => false,
                'message' => sprintf(__('Plantilla no encontrada: %s', 'flavor-chat-ia'), $plantilla_id),
            ];
        }

        $plantillas_activas = get_option('flavor_plantillas_activas', []);
        $datos_activa = $plantillas_activas[$plantilla_id] ?? null;

        $estado = [
            'plantilla_id' => $plantilla_id,
            'nombre' => $definicion['nombre'],
            'existe' => true,
            'activa' => isset($plantillas_activas[$plantilla_id]),
            'fecha_activacion' => $datos_activa['fecha_activacion'] ?? null,
            'componentes' => [],
        ];

        // Verificar estado de cada componente
        foreach ($this->componentes as $nombre => $componente) {
            $definicion_componente = $this->obtener_definicion_componente($definicion, $nombre, []);
            $estado['componentes'][$nombre] = $componente->verificar_estado($plantilla_id, $definicion_componente);
        }

        $estado['success'] = true;

        return $estado;
    }

    /**
     * Verifica si una plantilla esta activa
     *
     * @param string $plantilla_id ID de la plantilla
     * @return bool
     */
    public function esta_activa($plantilla_id) {
        $plantillas_activas = get_option('flavor_plantillas_activas', []);
        return isset($plantillas_activas[$plantilla_id]);
    }

    /**
     * Obtiene todas las plantillas activas
     *
     * @return array
     */
    public function obtener_plantillas_activas() {
        return get_option('flavor_plantillas_activas', []);
    }

    /**
     * Obtiene la plantilla activa principal (para compatibilidad con app_profile)
     *
     * @return string|null ID de la plantilla activa principal
     */
    public function obtener_plantilla_activa_principal() {
        $plantillas_activas = $this->obtener_plantillas_activas();
        if (empty($plantillas_activas)) {
            return null;
        }

        // Retornar la primera o la marcada como principal
        foreach ($plantillas_activas as $plantilla_id => $datos) {
            if (!empty($datos['es_principal'])) {
                return $plantilla_id;
            }
        }

        // Si ninguna esta marcada como principal, retornar la primera
        return array_key_first($plantillas_activas);
    }

    // =========================================================
    // METODOS AJAX
    // =========================================================

    /**
     * Handler AJAX para activar plantilla
     */
    public function ajax_activar_plantilla() {
        check_ajax_referer('flavor_admin_nonce', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_send_json_error(['message' => __('Permisos insuficientes', 'flavor-chat-ia')]);
        }

        $plantilla_id = sanitize_text_field($_POST['plantilla_id'] ?? '');
        $opciones = isset($_POST['opciones']) ? (array) $_POST['opciones'] : [];

        // Sanitizar opciones
        $opciones_sanitizadas = [
            'instalar_paginas' => !empty($opciones['instalar_paginas']),
            'instalar_landing' => !empty($opciones['instalar_landing']),
            'aplicar_config' => !empty($opciones['aplicar_config']),
            'instalar_demo' => !empty($opciones['instalar_demo']),
            'modulos_opcionales' => array_map('sanitize_text_field', $opciones['modulos_opcionales'] ?? []),
        ];

        $resultado = $this->activar_plantilla($plantilla_id, $opciones_sanitizadas);

        if ($resultado['success']) {
            wp_send_json_success($resultado);
        } else {
            wp_send_json_error($resultado);
        }
    }

    /**
     * Handler AJAX para desactivar plantilla
     */
    public function ajax_desactivar_plantilla() {
        check_ajax_referer('flavor_admin_nonce', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_send_json_error(['message' => __('Permisos insuficientes', 'flavor-chat-ia')]);
        }

        $plantilla_id = sanitize_text_field($_POST['plantilla_id'] ?? '');
        $opciones = isset($_POST['opciones']) ? (array) $_POST['opciones'] : [];

        $resultado = $this->desactivar_plantilla($plantilla_id, $opciones);

        if ($resultado['success']) {
            wp_send_json_success($resultado);
        } else {
            wp_send_json_error($resultado);
        }
    }

    /**
     * Handler AJAX para preview de plantilla
     */
    public function ajax_preview_plantilla() {
        check_ajax_referer('flavor_admin_nonce', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_send_json_error(['message' => __('Permisos insuficientes', 'flavor-chat-ia')]);
        }

        $plantilla_id = sanitize_text_field($_POST['plantilla_id'] ?? '');
        $opciones = isset($_POST['opciones']) ? (array) $_POST['opciones'] : [];

        $resultado = $this->obtener_preview($plantilla_id, $opciones);

        wp_send_json_success($resultado);
    }

    // =========================================================
    // METODOS PRIVADOS
    // =========================================================

    /**
     * Ejecuta la instalacion de un componente
     *
     * @param string $componente_nombre Nombre del componente
     * @param string $plantilla_id ID de la plantilla
     * @param array  $definicion Definicion del componente
     * @param array  $opciones Opciones
     * @return array Resultado
     */
    private function ejecutar_componente_instalacion($componente_nombre, $plantilla_id, $definicion, $opciones) {
        if (!isset($this->componentes[$componente_nombre])) {
            // Si no hay componente registrado, simular exito (componente no implementado)
            return [
                'success' => true,
                'message' => sprintf(__('Componente %s no implementado, omitido', 'flavor-chat-ia'), $componente_nombre),
                'omitido' => true,
            ];
        }

        try {
            $componente = $this->componentes[$componente_nombre];
            return $componente->instalar($plantilla_id, $definicion, $opciones);
        } catch (Exception $exception) {
            $this->registrar_error(
                'componente_excepcion',
                sprintf(__('Excepcion en componente %s: %s', 'flavor-chat-ia'), $componente_nombre, $exception->getMessage())
            );

            return [
                'success' => false,
                'message' => $exception->getMessage(),
                'excepcion' => true,
            ];
        }
    }

    /**
     * Ejecuta la desinstalacion de un componente
     *
     * @param string $componente_nombre Nombre del componente
     * @param string $plantilla_id ID de la plantilla
     * @param array  $definicion Definicion del componente
     * @param array  $opciones Opciones
     * @return array Resultado
     */
    private function ejecutar_componente_desinstalacion($componente_nombre, $plantilla_id, $definicion, $opciones) {
        if (!isset($this->componentes[$componente_nombre])) {
            return [
                'success' => true,
                'message' => sprintf(__('Componente %s no implementado, omitido', 'flavor-chat-ia'), $componente_nombre),
                'omitido' => true,
            ];
        }

        try {
            $componente = $this->componentes[$componente_nombre];
            return $componente->desinstalar($plantilla_id, $definicion, $opciones);
        } catch (Exception $exception) {
            return [
                'success' => false,
                'message' => $exception->getMessage(),
                'excepcion' => true,
            ];
        }
    }

    /**
     * Determina si un componente debe ejecutarse segun las opciones
     *
     * @param string $componente_nombre Nombre del componente
     * @param array  $opciones Opciones
     * @return bool
     */
    private function debe_ejecutar_componente($componente_nombre, $opciones) {
        $mapeo = [
            'modulos' => true, // Siempre se ejecuta
            'tablas' => true,  // Siempre se ejecuta
            'paginas' => $opciones['instalar_paginas'] ?? true,
            'landing' => $opciones['instalar_landing'] ?? true,
            'configuracion' => $opciones['aplicar_config'] ?? true,
            'demo' => $opciones['instalar_demo'] ?? false,
        ];

        return $mapeo[$componente_nombre] ?? true;
    }

    /**
     * Obtiene la parte de la definicion correspondiente a un componente
     *
     * @param array  $definicion Definicion completa
     * @param string $componente_nombre Nombre del componente
     * @param array  $opciones Opciones adicionales
     * @return array
     */
    private function obtener_definicion_componente($definicion, $componente_nombre, $opciones) {
        switch ($componente_nombre) {
            case 'modulos':
                $modulos = $definicion['modulos']['requeridos'] ?? [];
                // Agregar opcionales seleccionados
                if (!empty($opciones['modulos_opcionales'])) {
                    $opcionales_disponibles = $definicion['modulos']['opcionales'] ?? [];
                    $opcionales_seleccionados = array_intersect($opcionales_disponibles, $opciones['modulos_opcionales']);
                    $modulos = array_merge($modulos, $opcionales_seleccionados);
                }
                return ['modulos' => $modulos];

            case 'tablas':
                // Las tablas se derivan de los modulos
                return ['modulos' => $definicion['modulos']['requeridos'] ?? []];

            case 'paginas':
                return ['paginas' => $definicion['paginas'] ?? []];

            case 'landing':
                return $definicion['landing'] ?? [];

            case 'configuracion':
                return ['configuracion' => $definicion['configuracion'] ?? []];

            case 'demo':
                return $definicion['demo'] ?? [];

            default:
                return [];
        }
    }

    /**
     * Verifica si un componente es critico (debe detener si falla)
     *
     * @param string $componente_nombre Nombre del componente
     * @return bool
     */
    private function es_componente_critico($componente_nombre) {
        return in_array($componente_nombre, ['modulos', 'tablas'], true);
    }

    /**
     * Crea un snapshot del estado actual
     *
     * @param string $plantilla_id ID de la plantilla
     * @return array Snapshot
     */
    private function crear_snapshot_estado($plantilla_id) {
        $snapshot = [
            'fecha' => current_time('mysql'),
            'plantilla_id' => $plantilla_id,
            'modulos_activos' => get_option('flavor_chat_ia_settings', [])['active_modules'] ?? [],
            'plantillas_activas' => get_option('flavor_plantillas_activas', []),
        ];

        // Guardar snapshot
        update_option("flavor_template_{$plantilla_id}_snapshot_pre", $snapshot);

        return $snapshot;
    }

    /**
     * Intenta revertir a un estado anterior (rollback)
     *
     * @param string $plantilla_id ID de la plantilla
     * @param array  $snapshot Snapshot guardado
     * @return bool
     */
    private function rollback($plantilla_id, $snapshot) {
        // Restaurar modulos activos
        if (isset($snapshot['modulos_activos'])) {
            $configuracion = get_option('flavor_chat_ia_settings', []);
            $configuracion['active_modules'] = $snapshot['modulos_activos'];
            update_option('flavor_chat_ia_settings', $configuracion);
        }

        // Restaurar plantillas activas
        if (isset($snapshot['plantillas_activas'])) {
            update_option('flavor_plantillas_activas', $snapshot['plantillas_activas']);
        }

        $this->registrar_actividad('rollback_realizado', [
            'plantilla_id' => $plantilla_id,
            'snapshot' => $snapshot,
        ]);

        return true;
    }

    /**
     * Marca una plantilla como activa
     *
     * @param string $plantilla_id ID de la plantilla
     * @param array  $definicion Definicion
     * @param array  $opciones Opciones usadas
     */
    private function marcar_plantilla_activa($plantilla_id, $definicion, $opciones) {
        $plantillas_activas = get_option('flavor_plantillas_activas', []);

        $plantillas_activas[$plantilla_id] = [
            'nombre' => $definicion['nombre'],
            'fecha_activacion' => current_time('mysql'),
            'opciones' => $opciones,
            'modulos' => $definicion['modulos']['requeridos'] ?? [],
            'version' => FLAVOR_CHAT_IA_VERSION ?? '1.0.0',
        ];

        update_option('flavor_plantillas_activas', $plantillas_activas);

        // Actualizar tambien el perfil activo para compatibilidad
        $configuracion = get_option('flavor_chat_ia_settings', []);
        $configuracion['app_profile'] = $plantilla_id;

        // Actualizar modulos activos
        $modulos_actuales = $configuracion['active_modules'] ?? [];
        $modulos_plantilla = $definicion['modulos']['requeridos'] ?? [];
        if (!empty($opciones['modulos_opcionales'])) {
            $modulos_plantilla = array_merge($modulos_plantilla, $opciones['modulos_opcionales']);
        }
        $configuracion['active_modules'] = array_unique(array_merge($modulos_actuales, $modulos_plantilla));

        update_option('flavor_chat_ia_settings', $configuracion);
    }

    /**
     * Desmarca una plantilla como activa
     *
     * @param string $plantilla_id ID de la plantilla
     */
    private function desmarcar_plantilla_activa($plantilla_id) {
        $plantillas_activas = get_option('flavor_plantillas_activas', []);
        unset($plantillas_activas[$plantilla_id]);
        update_option('flavor_plantillas_activas', $plantillas_activas);
    }

    /**
     * Registra actividad en el log
     *
     * @param string $accion Accion realizada
     * @param array  $datos Datos adicionales
     */
    private function registrar_actividad($accion, $datos = []) {
        if (class_exists('Flavor_Activity_Log')) {
            $log = Flavor_Activity_Log::get_instance();
            if (method_exists($log, 'registrar')) {
                $log->registrar('orchestrator', $accion, $datos);
            }
        }

        // Tambien registrar en el log del plugin si existe la funcion
        if (function_exists('flavor_chat_ia_log')) {
            flavor_chat_ia_log("[Orchestrator] {$accion}", 'info', $datos);
        }
    }

    /**
     * Limpia el estado interno
     */
    private function limpiar_estado() {
        $this->errores = [];
        $this->advertencias = [];
    }

    /**
     * Registra un error
     *
     * @param string $codigo Codigo de error
     * @param string $mensaje Mensaje
     * @param array  $contexto Contexto adicional
     */
    private function registrar_error($codigo, $mensaje, $contexto = []) {
        $this->errores[] = [
            'codigo' => $codigo,
            'mensaje' => $mensaje,
            'contexto' => $contexto,
            'tiempo' => current_time('mysql'),
        ];
    }

    /**
     * Registra una advertencia
     *
     * @param string $codigo Codigo
     * @param string $mensaje Mensaje
     */
    private function registrar_advertencia($codigo, $mensaje) {
        $this->advertencias[] = [
            'codigo' => $codigo,
            'mensaje' => $mensaje,
            'tiempo' => current_time('mysql'),
        ];
    }

    /**
     * Genera respuesta de error estandarizada
     *
     * @param string $mensaje Mensaje de error
     * @return array
     */
    private function respuesta_error($mensaje) {
        return [
            'success' => false,
            'message' => $mensaje,
            'errores' => $this->errores,
        ];
    }
}

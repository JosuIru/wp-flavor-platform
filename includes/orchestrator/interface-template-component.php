<?php
/**
 * Interface para Componentes de Plantilla
 *
 * Define el contrato que deben cumplir todos los componentes
 * que participan en la activacion/desactivacion de plantillas
 *
 * @package FlavorChatIA
 * @subpackage Orchestrator
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Interface Flavor_Template_Component_Interface
 *
 * Los componentes son las piezas individuales que se instalan
 * cuando se activa una plantilla: modulos, paginas, tablas, config, etc.
 */
interface Flavor_Template_Component_Interface {

    /**
     * Instala el componente para una plantilla
     *
     * @param string $plantilla_id ID de la plantilla
     * @param array  $definicion   Definicion del componente desde la plantilla
     * @param array  $opciones     Opciones adicionales de instalacion
     * @return array Resultado con 'success', 'message', 'data'
     */
    public function instalar($plantilla_id, $definicion, $opciones = []);

    /**
     * Desinstala el componente de una plantilla
     *
     * @param string $plantilla_id ID de la plantilla
     * @param array  $definicion   Definicion del componente
     * @param array  $opciones     Opciones adicionales
     * @return array Resultado con 'success', 'message', 'data'
     */
    public function desinstalar($plantilla_id, $definicion = [], $opciones = []);

    /**
     * Verifica el estado actual del componente
     *
     * @param string $plantilla_id ID de la plantilla
     * @param array  $definicion   Definicion del componente
     * @return array Estado con 'instalado', 'parcial', 'detalles'
     */
    public function verificar_estado($plantilla_id, $definicion = []);

    /**
     * Obtiene el nombre identificador del componente
     *
     * @return string Nombre unico del componente (ej: 'modulos', 'paginas', 'landing')
     */
    public function get_nombre();
}

/**
 * Clase base abstracta para componentes del orquestador
 *
 * Proporciona funcionalidad comun para todos los componentes
 */
abstract class Flavor_Template_Component_Base implements Flavor_Template_Component_Interface {

    /**
     * ID del componente
     *
     * @var string
     */
    protected $componente_id = '';

    /**
     * Nombre descriptivo del componente
     *
     * @var string
     */
    protected $componente_nombre = '';

    /**
     * Errores acumulados durante la ejecucion
     *
     * @var array
     */
    protected $errores = [];

    /**
     * Advertencias acumuladas durante la ejecucion
     *
     * @var array
     */
    protected $advertencias = [];

    /**
     * Obtiene el nombre del componente
     *
     * @return string
     */
    public function get_nombre() {
        return $this->componente_id;
    }

    /**
     * Obtiene el nombre descriptivo del componente
     *
     * @return string
     */
    public function get_nombre_descriptivo() {
        return $this->componente_nombre;
    }

    /**
     * Registra un error
     *
     * @param string $codigo Codigo de error
     * @param string $mensaje Mensaje de error
     * @param array $contexto Contexto adicional
     * @return void
     */
    protected function registrar_error($codigo, $mensaje, $contexto = []) {
        $this->errores[] = [
            'codigo'   => $codigo,
            'mensaje'  => $mensaje,
            'contexto' => $contexto,
            'tiempo'   => current_time('mysql'),
        ];

        if (function_exists('flavor_chat_ia_log')) {
            flavor_chat_ia_log("[{$this->componente_id}] Error: {$mensaje}", 'error', $contexto);
        }
    }

    /**
     * Registra una advertencia
     *
     * @param string $codigo Codigo de advertencia
     * @param string $mensaje Mensaje de advertencia
     * @param array $contexto Contexto adicional
     * @return void
     */
    protected function registrar_advertencia($codigo, $mensaje, $contexto = []) {
        $this->advertencias[] = [
            'codigo'   => $codigo,
            'mensaje'  => $mensaje,
            'contexto' => $contexto,
            'tiempo'   => current_time('mysql'),
        ];

        if (function_exists('flavor_chat_ia_log')) {
            flavor_chat_ia_log("[{$this->componente_id}] Advertencia: {$mensaje}", 'warning', $contexto);
        }
    }

    /**
     * Limpia los errores y advertencias acumulados
     *
     * @return void
     */
    protected function limpiar_mensajes() {
        $this->errores = [];
        $this->advertencias = [];
    }

    /**
     * Genera una respuesta de exito estandarizada
     *
     * @param string $mensaje Mensaje de exito
     * @param array $datos Datos adicionales
     * @return array
     */
    protected function respuesta_exito($mensaje, $datos = []) {
        return [
            'success'      => true,
            'message'      => $mensaje,
            'data'         => $datos,
            'advertencias' => $this->advertencias,
            'componente'   => $this->componente_id,
        ];
    }

    /**
     * Genera una respuesta de error estandarizada
     *
     * @param string $mensaje Mensaje de error principal
     * @param array $datos Datos adicionales
     * @return array
     */
    protected function respuesta_error($mensaje, $datos = []) {
        return [
            'success'    => false,
            'message'    => $mensaje,
            'data'       => $datos,
            'errores'    => $this->errores,
            'componente' => $this->componente_id,
        ];
    }

    /**
     * Guarda metadatos de instalacion para una plantilla
     *
     * @param string $plantilla_id ID de la plantilla
     * @param string $clave Clave del metadato
     * @param mixed $valor Valor a guardar
     * @return bool
     */
    protected function guardar_meta_instalacion($plantilla_id, $clave, $valor) {
        $opcion_nombre = "flavor_template_{$plantilla_id}_{$this->componente_id}";
        $datos_actuales = get_option($opcion_nombre, []);
        $datos_actuales[$clave] = $valor;
        $datos_actuales['ultima_actualizacion'] = current_time('mysql');
        return update_option($opcion_nombre, $datos_actuales);
    }

    /**
     * Obtiene metadatos de instalacion de una plantilla
     *
     * @param string $plantilla_id ID de la plantilla
     * @param string $clave Clave del metadato (opcional, si no se proporciona retorna todos)
     * @param mixed $valor_defecto Valor por defecto si no existe
     * @return mixed
     */
    protected function obtener_meta_instalacion($plantilla_id, $clave = '', $valor_defecto = null) {
        $opcion_nombre = "flavor_template_{$plantilla_id}_{$this->componente_id}";
        $datos = get_option($opcion_nombre, []);

        if (empty($clave)) {
            return $datos;
        }

        return isset($datos[$clave]) ? $datos[$clave] : $valor_defecto;
    }

    /**
     * Elimina metadatos de instalacion de una plantilla
     *
     * @param string $plantilla_id ID de la plantilla
     * @return bool
     */
    protected function eliminar_meta_instalacion($plantilla_id) {
        $opcion_nombre = "flavor_template_{$plantilla_id}_{$this->componente_id}";
        return delete_option($opcion_nombre);
    }

    /**
     * Verifica si un modulo esta siendo usado por otra plantilla activa
     *
     * @param string $modulo_id ID del modulo
     * @param string $plantilla_excluir ID de plantilla a excluir de la verificacion
     * @return bool True si esta en uso por otra plantilla
     */
    protected function modulo_en_uso_por_otra_plantilla($modulo_id, $plantilla_excluir = '') {
        $plantillas_activas = get_option('flavor_plantillas_activas', []);

        foreach ($plantillas_activas as $plantilla_id => $datos_plantilla) {
            if ($plantilla_id === $plantilla_excluir) {
                continue;
            }

            $modulos_plantilla = $datos_plantilla['modulos'] ?? [];
            if (in_array($modulo_id, $modulos_plantilla, true)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Crea un snapshot del estado actual antes de modificar
     *
     * @param string $plantilla_id ID de la plantilla
     * @param string $tipo Tipo de snapshot
     * @param mixed $datos Datos a guardar
     * @return bool
     */
    protected function crear_snapshot($plantilla_id, $tipo, $datos) {
        $snapshots = get_option("flavor_template_{$plantilla_id}_snapshots", []);
        $snapshots[$this->componente_id][$tipo] = [
            'datos'  => $datos,
            'fecha'  => current_time('mysql'),
        ];
        return update_option("flavor_template_{$plantilla_id}_snapshots", $snapshots);
    }

    /**
     * Obtiene un snapshot guardado
     *
     * @param string $plantilla_id ID de la plantilla
     * @param string $tipo Tipo de snapshot
     * @return mixed|null
     */
    protected function obtener_snapshot($plantilla_id, $tipo) {
        $snapshots = get_option("flavor_template_{$plantilla_id}_snapshots", []);
        return $snapshots[$this->componente_id][$tipo]['datos'] ?? null;
    }
}

<?php
/**
 * Componente Activador de Modulos
 *
 * Gestiona la activacion y desactivacion de modulos para plantillas
 *
 * @package FlavorChatIA
 * @subpackage Orchestrator/Components
 */

if (!defined('ABSPATH')) {
    exit;
}

// Cargar interface si no esta cargada
if (!interface_exists('Flavor_Template_Component_Interface')) {
    require_once dirname(__DIR__) . '/interface-template-component.php';
}

/**
 * Clase Flavor_Module_Activator
 *
 * Activa los modulos requeridos y opcionales de una plantilla
 */
class Flavor_Module_Activator extends Flavor_Template_Component_Base {

    /**
     * Constructor
     */
    public function __construct() {
        $this->componente_id = 'modulos';
        $this->componente_nombre = __('Activador de Modulos', 'flavor-chat-ia');
    }

    /**
     * Instala/activa los modulos de la plantilla
     *
     * @param string $plantilla_id ID de la plantilla
     * @param array $definicion Definicion con 'modulos_requeridos' y 'modulos_opcionales'
     * @param array $opciones Opciones como 'modulos_seleccionados' para opcionales
     * @return array Resultado de la operacion
     */
    public function instalar($plantilla_id, $definicion, $opciones = []) {
        $this->limpiar_mensajes();

        $modulos_requeridos = $definicion['modulos_requeridos'] ?? [];
        $modulos_opcionales = $definicion['modulos_opcionales'] ?? [];
        $modulos_seleccionados = $opciones['modulos_seleccionados'] ?? [];

        // Combinar modulos requeridos con opcionales seleccionados
        $modulos_a_activar = array_unique(array_merge(
            $modulos_requeridos,
            array_intersect($modulos_opcionales, $modulos_seleccionados)
        ));

        if (empty($modulos_a_activar)) {
            return $this->respuesta_exito(
                __('No hay modulos para activar.', 'flavor-chat-ia'),
                ['modulos_activados' => []]
            );
        }

        // Obtener configuracion actual del plugin
        $configuracion_plugin = get_option('flavor_chat_ia_settings', []);
        $modulos_activos_actuales = $configuracion_plugin['active_modules'] ?? [];

        // Crear snapshot del estado actual
        $this->crear_snapshot($plantilla_id, 'modulos_previos', $modulos_activos_actuales);

        // Agregar nuevos modulos a los activos
        $modulos_activados = [];
        $modulos_ya_activos = [];
        $modulos_fallidos = [];

        foreach ($modulos_a_activar as $modulo_id) {
            // Normalizar ID (guiones a guiones bajos)
            $modulo_id_normalizado = str_replace('-', '_', $modulo_id);

            if (in_array($modulo_id_normalizado, $modulos_activos_actuales, true)) {
                $modulos_ya_activos[] = $modulo_id_normalizado;
                continue;
            }

            // Verificar que el modulo existe
            if (!$this->modulo_existe($modulo_id_normalizado)) {
                $this->registrar_advertencia(
                    'modulo_no_existe',
                    sprintf(__('El modulo "%s" no existe en el sistema.', 'flavor-chat-ia'), $modulo_id)
                );
                $modulos_fallidos[] = $modulo_id_normalizado;
                continue;
            }

            $modulos_activos_actuales[] = $modulo_id_normalizado;
            $modulos_activados[] = $modulo_id_normalizado;
        }

        // Guardar configuracion actualizada
        $configuracion_plugin['active_modules'] = array_unique($modulos_activos_actuales);
        update_option('flavor_chat_ia_settings', $configuracion_plugin);

        // Cargar e inicializar los modulos recien activados
        $this->inicializar_modulos($modulos_activados);

        // Guardar registro de modulos instalados por esta plantilla
        $this->guardar_meta_instalacion($plantilla_id, 'modulos_instalados', $modulos_activados);
        $this->guardar_meta_instalacion($plantilla_id, 'modulos_ya_activos', $modulos_ya_activos);

        // Registrar en plantillas activas
        $this->registrar_plantilla_activa($plantilla_id, array_merge($modulos_activados, $modulos_ya_activos));

        $mensaje = sprintf(
            __('Se activaron %d modulos correctamente.', 'flavor-chat-ia'),
            count($modulos_activados)
        );

        if (!empty($modulos_ya_activos)) {
            $mensaje .= ' ' . sprintf(
                __('%d modulos ya estaban activos.', 'flavor-chat-ia'),
                count($modulos_ya_activos)
            );
        }

        return $this->respuesta_exito($mensaje, [
            'modulos_activados'  => $modulos_activados,
            'modulos_ya_activos' => $modulos_ya_activos,
            'modulos_fallidos'   => $modulos_fallidos,
            'total_activos'      => count($configuracion_plugin['active_modules']),
        ]);
    }

    /**
     * Desinstala/desactiva los modulos de la plantilla
     *
     * @param string $plantilla_id ID de la plantilla
     * @param array $definicion Definicion de la plantilla
     * @param array $opciones Opciones adicionales
     * @return array Resultado de la operacion
     */
    public function desinstalar($plantilla_id, $definicion = [], $opciones = []) {
        $this->limpiar_mensajes();

        // Obtener modulos que fueron instalados por esta plantilla
        $modulos_instalados = $this->obtener_meta_instalacion($plantilla_id, 'modulos_instalados', []);

        if (empty($modulos_instalados)) {
            return $this->respuesta_exito(
                __('No hay modulos instalados por esta plantilla para desactivar.', 'flavor-chat-ia'),
                ['modulos_desactivados' => []]
            );
        }

        $configuracion_plugin = get_option('flavor_chat_ia_settings', []);
        $modulos_activos = $configuracion_plugin['active_modules'] ?? [];

        $modulos_desactivados = [];
        $modulos_en_uso = [];

        foreach ($modulos_instalados as $modulo_id) {
            // Verificar si el modulo esta siendo usado por otra plantilla activa
            if ($this->modulo_en_uso_por_otra_plantilla($modulo_id, $plantilla_id)) {
                $modulos_en_uso[] = $modulo_id;
                $this->registrar_advertencia(
                    'modulo_en_uso',
                    sprintf(
                        __('El modulo "%s" esta siendo usado por otra plantilla y no se desactivara.', 'flavor-chat-ia'),
                        $modulo_id
                    )
                );
                continue;
            }

            // Desactivar modulo
            $indice = array_search($modulo_id, $modulos_activos, true);
            if ($indice !== false) {
                unset($modulos_activos[$indice]);
                $modulos_desactivados[] = $modulo_id;
            }
        }

        // Reindexar array y guardar
        $configuracion_plugin['active_modules'] = array_values($modulos_activos);
        update_option('flavor_chat_ia_settings', $configuracion_plugin);

        // Limpiar metadatos
        $this->eliminar_meta_instalacion($plantilla_id);
        $this->desregistrar_plantilla_activa($plantilla_id);

        return $this->respuesta_exito(
            sprintf(__('Se desactivaron %d modulos.', 'flavor-chat-ia'), count($modulos_desactivados)),
            [
                'modulos_desactivados' => $modulos_desactivados,
                'modulos_en_uso'       => $modulos_en_uso,
            ]
        );
    }

    /**
     * Verifica el estado de los modulos para una plantilla
     *
     * @param string $plantilla_id ID de la plantilla
     * @param array $definicion Definicion con modulos esperados
     * @return array Estado de los modulos
     */
    public function verificar_estado($plantilla_id, $definicion = []) {
        $modulos_requeridos = $definicion['modulos_requeridos'] ?? [];
        $modulos_opcionales = $definicion['modulos_opcionales'] ?? [];

        $configuracion_plugin = get_option('flavor_chat_ia_settings', []);
        $modulos_activos = $configuracion_plugin['active_modules'] ?? [];

        $estado_modulos = [
            'activos'    => [],
            'inactivos'  => [],
            'requeridos' => [
                'activos'   => [],
                'faltantes' => [],
            ],
            'opcionales' => [
                'activos'   => [],
                'inactivos' => [],
            ],
        ];

        // Verificar modulos requeridos
        foreach ($modulos_requeridos as $modulo_id) {
            $modulo_normalizado = str_replace('-', '_', $modulo_id);
            if (in_array($modulo_normalizado, $modulos_activos, true)) {
                $estado_modulos['requeridos']['activos'][] = $modulo_normalizado;
                $estado_modulos['activos'][] = $modulo_normalizado;
            } else {
                $estado_modulos['requeridos']['faltantes'][] = $modulo_normalizado;
                $estado_modulos['inactivos'][] = $modulo_normalizado;
            }
        }

        // Verificar modulos opcionales
        foreach ($modulos_opcionales as $modulo_id) {
            $modulo_normalizado = str_replace('-', '_', $modulo_id);
            if (in_array($modulo_normalizado, $modulos_activos, true)) {
                $estado_modulos['opcionales']['activos'][] = $modulo_normalizado;
            } else {
                $estado_modulos['opcionales']['inactivos'][] = $modulo_normalizado;
            }
        }

        // Determinar estado general
        $todos_requeridos_activos = empty($estado_modulos['requeridos']['faltantes']);
        $algunos_activos = !empty($estado_modulos['activos']);

        if ($todos_requeridos_activos && $algunos_activos) {
            $estado = 'completo';
        } elseif ($algunos_activos) {
            $estado = 'parcial';
        } else {
            $estado = 'no_instalado';
        }

        return [
            'estado'   => $estado,
            'detalles' => $estado_modulos,
            'mensaje'  => $this->generar_mensaje_estado($estado_modulos),
        ];
    }

    /**
     * Verifica si un modulo existe en el sistema
     *
     * @param string $modulo_id ID del modulo
     * @return bool
     */
    private function modulo_existe($modulo_id) {
        if (class_exists('Flavor_Chat_Module_Loader')) {
            $loader = Flavor_Chat_Module_Loader::get_instance();
            $modulos_registrados = $loader->get_registered_modules();
            return isset($modulos_registrados[$modulo_id]);
        }

        // Fallback: verificar si existe el archivo del modulo
        $ruta_modulo = FLAVOR_CHAT_IA_PATH . 'includes/modules/' . str_replace('_', '-', $modulo_id);
        return is_dir($ruta_modulo);
    }

    /**
     * Inicializa los modulos recien activados
     *
     * @param array $modulos_ids IDs de modulos a inicializar
     * @return void
     */
    private function inicializar_modulos($modulos_ids) {
        if (empty($modulos_ids)) {
            return;
        }

        if (class_exists('Flavor_Chat_Module_Loader')) {
            $loader = Flavor_Chat_Module_Loader::get_instance();
            $loader->load_active_modules();
        }
    }

    /**
     * Registra la plantilla como activa con sus modulos
     *
     * @param string $plantilla_id ID de la plantilla
     * @param array $modulos Modulos de la plantilla
     * @return void
     */
    private function registrar_plantilla_activa($plantilla_id, $modulos) {
        $plantillas_activas = get_option('flavor_plantillas_activas', []);
        $plantillas_activas[$plantilla_id] = [
            'modulos'           => $modulos,
            'fecha_activacion'  => current_time('mysql'),
        ];
        update_option('flavor_plantillas_activas', $plantillas_activas);
    }

    /**
     * Desregistra la plantilla de las activas
     *
     * @param string $plantilla_id ID de la plantilla
     * @return void
     */
    private function desregistrar_plantilla_activa($plantilla_id) {
        $plantillas_activas = get_option('flavor_plantillas_activas', []);
        unset($plantillas_activas[$plantilla_id]);
        update_option('flavor_plantillas_activas', $plantillas_activas);
    }

    /**
     * Genera un mensaje descriptivo del estado
     *
     * @param array $estado_modulos Estado de los modulos
     * @return string
     */
    private function generar_mensaje_estado($estado_modulos) {
        $total_activos = count($estado_modulos['activos']);
        $total_faltantes = count($estado_modulos['requeridos']['faltantes']);

        if ($total_faltantes === 0 && $total_activos > 0) {
            return sprintf(
                __('Todos los modulos requeridos estan activos (%d modulos).', 'flavor-chat-ia'),
                $total_activos
            );
        }

        if ($total_faltantes > 0) {
            return sprintf(
                __('Faltan %d modulos requeridos por activar: %s', 'flavor-chat-ia'),
                $total_faltantes,
                implode(', ', $estado_modulos['requeridos']['faltantes'])
            );
        }

        return __('No hay modulos configurados para esta plantilla.', 'flavor-chat-ia');
    }
}

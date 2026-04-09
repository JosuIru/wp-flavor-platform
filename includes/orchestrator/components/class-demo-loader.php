<?php
/**
 * Componente Cargador de Datos Demo
 *
 * Gestiona la carga y limpieza de datos de demostracion
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
 * Clase Flavor_Demo_Loader
 *
 * Carga datos de demostracion para los modulos de una plantilla
 */
class Flavor_Demo_Loader extends Flavor_Template_Component_Base {

    /**
     * Instancia del Demo Data Manager
     *
     * @var Flavor_Demo_Data_Manager|null
     */
    private $demo_manager = null;

    /**
     * Constructor
     */
    public function __construct() {
        $this->componente_id = 'demo';
        $this->componente_nombre = __('Cargador de Datos Demo', FLAVOR_PLATFORM_TEXT_DOMAIN);
    }

    /**
     * Obtiene la instancia del Demo Data Manager
     *
     * @return Flavor_Demo_Data_Manager|null
     */
    private function get_demo_manager() {
        if ($this->demo_manager === null) {
            if (class_exists('Flavor_Demo_Data_Manager')) {
                $this->demo_manager = Flavor_Demo_Data_Manager::get_instance();
            }
        }
        return $this->demo_manager;
    }

    /**
     * Carga los datos demo para los modulos de la plantilla
     *
     * @param string $plantilla_id ID de la plantilla
     * @param array $definicion Definicion con 'modulos' o 'demo' config
     * @param array $opciones Opciones como 'solo_modulos' para filtrar
     * @return array Resultado de la operacion
     */
    public function instalar($plantilla_id, $definicion, $opciones = []) {
        $this->limpiar_mensajes();

        $demo_manager = $this->get_demo_manager();

        if (!$demo_manager) {
            return $this->respuesta_error(
                __('El gestor de datos demo no esta disponible.', FLAVOR_PLATFORM_TEXT_DOMAIN),
                ['clase_faltante' => 'Flavor_Demo_Data_Manager']
            );
        }

        // Determinar que modulos necesitan datos demo
        $modulos_demo = $this->obtener_modulos_para_demo($definicion, $opciones);

        if (empty($modulos_demo)) {
            return $this->respuesta_exito(
                __('No hay modulos que requieran datos demo.', FLAVOR_PLATFORM_TEXT_DOMAIN),
                ['modulos_procesados' => []]
            );
        }

        $modulos_cargados = [];
        $modulos_fallidos = [];
        $modulos_omitidos = [];

        foreach ($modulos_demo as $modulo_id) {
            $modulo_normalizado = str_replace('-', '_', $modulo_id);

            // Verificar si ya tiene datos demo
            if ($demo_manager->has_demo_data($modulo_normalizado)) {
                $modulos_omitidos[] = [
                    'modulo' => $modulo_normalizado,
                    'motivo' => __('Ya tiene datos demo cargados', FLAVOR_PLATFORM_TEXT_DOMAIN),
                    'count'  => $demo_manager->get_demo_data_count($modulo_normalizado),
                ];
                continue;
            }

            // Cargar datos demo
            $resultado = $this->cargar_demo_modulo($modulo_normalizado, $demo_manager);

            if ($resultado['success']) {
                $modulos_cargados[] = [
                    'modulo'  => $modulo_normalizado,
                    'count'   => $resultado['count'] ?? 0,
                    'mensaje' => $resultado['message'] ?? '',
                ];
            } else {
                $modulos_fallidos[] = [
                    'modulo' => $modulo_normalizado,
                    'error'  => $resultado['error'] ?? __('Error desconocido', FLAVOR_PLATFORM_TEXT_DOMAIN),
                ];
            }
        }

        // Guardar registro de modulos con datos demo cargados
        $this->guardar_meta_instalacion($plantilla_id, 'modulos_demo', array_column($modulos_cargados, 'modulo'));

        $mensaje = sprintf(
            __('Se cargaron datos demo para %d modulos.', FLAVOR_PLATFORM_TEXT_DOMAIN),
            count($modulos_cargados)
        );

        if (!empty($modulos_omitidos)) {
            $mensaje .= ' ' . sprintf(
                __('%d modulos ya tenian datos demo.', FLAVOR_PLATFORM_TEXT_DOMAIN),
                count($modulos_omitidos)
            );
        }

        if (!empty($modulos_fallidos)) {
            $mensaje .= ' ' . sprintf(
                __('%d modulos con errores.', FLAVOR_PLATFORM_TEXT_DOMAIN),
                count($modulos_fallidos)
            );
        }

        return $this->respuesta_exito($mensaje, [
            'modulos_cargados'  => $modulos_cargados,
            'modulos_omitidos'  => $modulos_omitidos,
            'modulos_fallidos'  => $modulos_fallidos,
        ]);
    }

    /**
     * Limpia los datos demo de los modulos de la plantilla
     *
     * @param string $plantilla_id ID de la plantilla
     * @param array $definicion Definicion de la plantilla
     * @param array $opciones Opciones adicionales
     * @return array Resultado de la operacion
     */
    public function desinstalar($plantilla_id, $definicion = [], $opciones = []) {
        $this->limpiar_mensajes();

        $demo_manager = $this->get_demo_manager();

        if (!$demo_manager) {
            return $this->respuesta_error(
                __('El gestor de datos demo no esta disponible.', FLAVOR_PLATFORM_TEXT_DOMAIN),
                ['clase_faltante' => 'Flavor_Demo_Data_Manager']
            );
        }

        // Obtener modulos con datos demo de esta plantilla
        $modulos_demo = $this->obtener_meta_instalacion($plantilla_id, 'modulos_demo', []);

        if (empty($modulos_demo)) {
            return $this->respuesta_exito(
                __('No hay datos demo registrados para esta plantilla.', FLAVOR_PLATFORM_TEXT_DOMAIN),
                ['modulos_limpiados' => []]
            );
        }

        $modulos_limpiados = [];
        $modulos_fallidos = [];

        foreach ($modulos_demo as $modulo_id) {
            // Verificar si el modulo esta siendo usado por otra plantilla
            if ($this->modulo_demo_en_uso_por_otra_plantilla($modulo_id, $plantilla_id)) {
                $this->registrar_advertencia(
                    'demo_en_uso',
                    sprintf(
                        __('Los datos demo del modulo "%s" estan siendo usados por otra plantilla.', FLAVOR_PLATFORM_TEXT_DOMAIN),
                        $modulo_id
                    )
                );
                continue;
            }

            // Limpiar datos demo
            $resultado = $this->limpiar_demo_modulo($modulo_id, $demo_manager);

            if ($resultado['success']) {
                $modulos_limpiados[] = [
                    'modulo'  => $modulo_id,
                    'count'   => $resultado['count'] ?? 0,
                ];
            } else {
                $modulos_fallidos[] = [
                    'modulo' => $modulo_id,
                    'error'  => $resultado['error'] ?? __('Error desconocido', FLAVOR_PLATFORM_TEXT_DOMAIN),
                ];
            }
        }

        // Limpiar metadatos
        $this->eliminar_meta_instalacion($plantilla_id);

        return $this->respuesta_exito(
            sprintf(
                __('Se limpiaron datos demo de %d modulos.', FLAVOR_PLATFORM_TEXT_DOMAIN),
                count($modulos_limpiados)
            ),
            [
                'modulos_limpiados' => $modulos_limpiados,
                'modulos_fallidos'  => $modulos_fallidos,
            ]
        );
    }

    /**
     * Verifica el estado de los datos demo
     *
     * @param string $plantilla_id ID de la plantilla
     * @param array $definicion Definicion con modulos esperados
     * @return array Estado de los datos demo
     */
    public function verificar_estado($plantilla_id, $definicion = []) {
        $demo_manager = $this->get_demo_manager();

        if (!$demo_manager) {
            return [
                'estado'   => 'error',
                'detalles' => [],
                'mensaje'  => __('El gestor de datos demo no esta disponible.', FLAVOR_PLATFORM_TEXT_DOMAIN),
            ];
        }

        $modulos_esperados = $this->obtener_modulos_para_demo($definicion, []);

        if (empty($modulos_esperados)) {
            return [
                'estado'   => 'no_aplica',
                'detalles' => [],
                'mensaje'  => __('No hay modulos configurados para datos demo.', FLAVOR_PLATFORM_TEXT_DOMAIN),
            ];
        }

        $modulos_con_demo = [];
        $modulos_sin_demo = [];

        foreach ($modulos_esperados as $modulo_id) {
            $modulo_normalizado = str_replace('-', '_', $modulo_id);

            if ($demo_manager->has_demo_data($modulo_normalizado)) {
                $modulos_con_demo[] = [
                    'modulo' => $modulo_normalizado,
                    'count'  => $demo_manager->get_demo_data_count($modulo_normalizado),
                ];
            } else {
                $modulos_sin_demo[] = $modulo_normalizado;
            }
        }

        // Determinar estado
        $total_esperados = count($modulos_esperados);
        $total_con_demo = count($modulos_con_demo);

        if ($total_con_demo === $total_esperados) {
            $estado = 'completo';
        } elseif ($total_con_demo > 0) {
            $estado = 'parcial';
        } else {
            $estado = 'no_instalado';
        }

        return [
            'estado'   => $estado,
            'detalles' => [
                'con_demo'  => $modulos_con_demo,
                'sin_demo'  => $modulos_sin_demo,
            ],
            'mensaje'  => sprintf(
                __('%d de %d modulos tienen datos demo.', FLAVOR_PLATFORM_TEXT_DOMAIN),
                $total_con_demo,
                $total_esperados
            ),
        ];
    }

    /**
     * Obtiene los modulos que deben tener datos demo
     *
     * @param array $definicion Definicion de la plantilla
     * @param array $opciones Opciones de filtrado
     * @return array Lista de IDs de modulos
     */
    private function obtener_modulos_para_demo($definicion, $opciones) {
        // Prioridad 1: Lista especifica en 'demo'
        if (!empty($definicion['demo']['modulos'])) {
            return $definicion['demo']['modulos'];
        }

        // Prioridad 2: Todos los modulos de la plantilla
        $modulos = [];

        if (!empty($definicion['modulos_requeridos'])) {
            $modulos = array_merge($modulos, $definicion['modulos_requeridos']);
        }

        if (!empty($definicion['modulos'])) {
            $modulos = array_merge($modulos, $definicion['modulos']);
        }

        // Prioridad 3: Modulos seleccionados en opciones
        if (!empty($opciones['solo_modulos'])) {
            $modulos = array_intersect($modulos, $opciones['solo_modulos']);
        }

        // Filtrar solo modulos que soportan datos demo
        $modulos_soportados = $this->obtener_modulos_con_soporte_demo();

        return array_intersect(array_unique($modulos), $modulos_soportados);
    }

    /**
     * Obtiene lista de modulos que tienen soporte para datos demo
     *
     * @return array
     */
    private function obtener_modulos_con_soporte_demo() {
        // Lista de modulos que tienen metodos de demo en Flavor_Demo_Data_Manager
        return [
            'banco_tiempo',
            'eventos',
            'marketplace',
            'grupos_consumo',
            'ayuda_vecinal',
            'socios',
            'talleres',
            'incidencias',
            'participacion',
            'biblioteca',
            'carpooling',
            'bicicletas_compartidas',
            'espacios_comunes',
            'huertos_urbanos',
            'compostaje',
        ];
    }

    /**
     * Carga datos demo para un modulo especifico
     *
     * @param string $modulo_id ID del modulo
     * @param Flavor_Demo_Data_Manager $demo_manager Instancia del manager
     * @return array Resultado
     */
    private function cargar_demo_modulo($modulo_id, $demo_manager) {
        try {
            $resultado = $demo_manager->populate_module($modulo_id);

            if (is_array($resultado)) {
                return $resultado;
            }

            // Si el metodo no retorna array, verificar si se cargaron datos
            if ($demo_manager->has_demo_data($modulo_id)) {
                return [
                    'success' => true,
                    'count'   => $demo_manager->get_demo_data_count($modulo_id),
                    'message' => __('Datos demo cargados correctamente.', FLAVOR_PLATFORM_TEXT_DOMAIN),
                ];
            }

            return [
                'success' => false,
                'error'   => __('No se pudieron cargar los datos demo.', FLAVOR_PLATFORM_TEXT_DOMAIN),
            ];

        } catch (Exception $excepcion) {
            $this->registrar_error(
                'demo_load_error',
                sprintf(__('Error cargando datos demo para %s: %s', FLAVOR_PLATFORM_TEXT_DOMAIN), $modulo_id, $excepcion->getMessage())
            );

            return [
                'success' => false,
                'error'   => $excepcion->getMessage(),
            ];
        }
    }

    /**
     * Limpia datos demo de un modulo especifico
     *
     * @param string $modulo_id ID del modulo
     * @param Flavor_Demo_Data_Manager $demo_manager Instancia del manager
     * @return array Resultado
     */
    private function limpiar_demo_modulo($modulo_id, $demo_manager) {
        try {
            $count_antes = $demo_manager->get_demo_data_count($modulo_id);
            $resultado = $demo_manager->clear_module($modulo_id);

            if (is_array($resultado)) {
                return $resultado;
            }

            // Verificar que se limpiaron los datos
            if (!$demo_manager->has_demo_data($modulo_id)) {
                return [
                    'success' => true,
                    'count'   => $count_antes,
                    'message' => __('Datos demo eliminados correctamente.', FLAVOR_PLATFORM_TEXT_DOMAIN),
                ];
            }

            return [
                'success' => false,
                'error'   => __('No se pudieron eliminar todos los datos demo.', FLAVOR_PLATFORM_TEXT_DOMAIN),
            ];

        } catch (Exception $excepcion) {
            $this->registrar_error(
                'demo_clear_error',
                sprintf(__('Error limpiando datos demo de %s: %s', FLAVOR_PLATFORM_TEXT_DOMAIN), $modulo_id, $excepcion->getMessage())
            );

            return [
                'success' => false,
                'error'   => $excepcion->getMessage(),
            ];
        }
    }

    /**
     * Verifica si los datos demo de un modulo estan en uso por otra plantilla
     *
     * @param string $modulo_id ID del modulo
     * @param string $plantilla_excluir ID de plantilla a excluir
     * @return bool
     */
    private function modulo_demo_en_uso_por_otra_plantilla($modulo_id, $plantilla_excluir) {
        $plantillas_activas = get_option('flavor_plantillas_activas', []);

        foreach ($plantillas_activas as $plantilla_id => $datos) {
            if ($plantilla_id === $plantilla_excluir) {
                continue;
            }

            $modulos_demo_plantilla = get_option("flavor_template_{$plantilla_id}_demo", []);
            $modulos_demo = $modulos_demo_plantilla['modulos_demo'] ?? [];

            if (in_array($modulo_id, $modulos_demo, true)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Obtiene estadisticas de datos demo
     *
     * @param array $modulos Lista de modulos a verificar
     * @return array Estadisticas
     */
    public function obtener_estadisticas_demo($modulos = []) {
        $demo_manager = $this->get_demo_manager();

        if (!$demo_manager) {
            return ['error' => __('Demo manager no disponible', FLAVOR_PLATFORM_TEXT_DOMAIN)];
        }

        if (empty($modulos)) {
            $modulos = $this->obtener_modulos_con_soporte_demo();
        }

        $estadisticas = [
            'total_modulos'     => count($modulos),
            'modulos_con_demo'  => 0,
            'total_registros'   => 0,
            'por_modulo'        => [],
        ];

        foreach ($modulos as $modulo_id) {
            $modulo_normalizado = str_replace('-', '_', $modulo_id);

            if ($demo_manager->has_demo_data($modulo_normalizado)) {
                $count = $demo_manager->get_demo_data_count($modulo_normalizado);
                $estadisticas['modulos_con_demo']++;
                $estadisticas['total_registros'] += $count;
                $estadisticas['por_modulo'][$modulo_normalizado] = $count;
            }
        }

        return $estadisticas;
    }
}

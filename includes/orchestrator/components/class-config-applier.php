<?php
/**
 * Componente Aplicador de Configuracion
 *
 * Gestiona la aplicacion de configuraciones predeterminadas para modulos
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
 * Clase Flavor_Config_Applier
 *
 * Aplica configuraciones predeterminadas a los modulos de una plantilla
 */
class Flavor_Config_Applier extends Flavor_Template_Component_Base {

    /**
     * Constructor
     */
    public function __construct() {
        $this->componente_id = 'config';
        $this->componente_nombre = __('Aplicador de Configuracion', FLAVOR_PLATFORM_TEXT_DOMAIN);
    }

    /**
     * Aplica la configuracion predeterminada de la plantilla
     *
     * @param string $plantilla_id ID de la plantilla
     * @param array $definicion Definicion con 'config' por modulo
     * @param array $opciones Opciones adicionales como 'sobrescribir'
     * @return array Resultado de la operacion
     */
    public function instalar($plantilla_id, $definicion, $opciones = []) {
        $this->limpiar_mensajes();

        $configuraciones = $definicion['config'] ?? [];
        $sobrescribir = $opciones['sobrescribir'] ?? false;

        if (empty($configuraciones)) {
            return $this->respuesta_exito(
                __('No hay configuraciones predeterminadas definidas.', FLAVOR_PLATFORM_TEXT_DOMAIN),
                ['modulos_configurados' => []]
            );
        }

        $modulos_configurados = [];
        $modulos_omitidos = [];
        $modulos_fallidos = [];

        // Crear snapshot de configuraciones actuales
        $snapshot_config = [];

        foreach ($configuraciones as $modulo_id => $config_modulo) {
            $modulo_normalizado = str_replace('-', '_', $modulo_id);
            $opcion_nombre = 'flavor_chat_ia_module_' . $modulo_normalizado;

            // Guardar configuracion actual para snapshot
            $config_actual = get_option($opcion_nombre, []);
            $snapshot_config[$modulo_normalizado] = $config_actual;

            // Verificar si ya existe configuracion
            if (!empty($config_actual) && !$sobrescribir) {
                $modulos_omitidos[] = [
                    'modulo'  => $modulo_normalizado,
                    'motivo'  => __('Ya tiene configuracion existente', FLAVOR_PLATFORM_TEXT_DOMAIN),
                ];
                continue;
            }

            // Aplicar configuracion
            $resultado = $this->aplicar_configuracion_modulo($modulo_normalizado, $config_modulo, $config_actual, $sobrescribir);

            if ($resultado['success']) {
                $modulos_configurados[] = [
                    'modulo'   => $modulo_normalizado,
                    'claves'   => array_keys($config_modulo),
                    'modo'     => $sobrescribir ? 'sobrescrito' : 'aplicado',
                ];
            } else {
                $modulos_fallidos[] = [
                    'modulo' => $modulo_normalizado,
                    'error'  => $resultado['error'],
                ];
            }
        }

        // Guardar snapshot
        $this->crear_snapshot($plantilla_id, 'config_anterior', $snapshot_config);

        // Guardar registro de configuraciones aplicadas
        $this->guardar_meta_instalacion($plantilla_id, 'modulos_configurados', array_column($modulos_configurados, 'modulo'));
        $this->guardar_meta_instalacion($plantilla_id, 'configuracion_aplicada', $configuraciones);

        $mensaje = sprintf(
            __('Se configuro %d modulos correctamente.', FLAVOR_PLATFORM_TEXT_DOMAIN),
            count($modulos_configurados)
        );

        if (!empty($modulos_omitidos)) {
            $mensaje .= ' ' . sprintf(
                __('%d modulos omitidos (ya tenian configuracion).', FLAVOR_PLATFORM_TEXT_DOMAIN),
                count($modulos_omitidos)
            );
        }

        return $this->respuesta_exito($mensaje, [
            'modulos_configurados' => $modulos_configurados,
            'modulos_omitidos'     => $modulos_omitidos,
            'modulos_fallidos'     => $modulos_fallidos,
        ]);
    }

    /**
     * Restaura la configuracion anterior
     *
     * @param string $plantilla_id ID de la plantilla
     * @param array $definicion Definicion de la plantilla
     * @param array $opciones Opciones adicionales
     * @return array Resultado de la operacion
     */
    public function desinstalar($plantilla_id, $definicion = [], $opciones = []) {
        $this->limpiar_mensajes();

        $restaurar = $opciones['restaurar_anterior'] ?? true;
        $modulos_configurados = $this->obtener_meta_instalacion($plantilla_id, 'modulos_configurados', []);

        if (empty($modulos_configurados)) {
            return $this->respuesta_exito(
                __('No hay configuraciones registradas para esta plantilla.', FLAVOR_PLATFORM_TEXT_DOMAIN),
                ['modulos_restaurados' => []]
            );
        }

        $modulos_restaurados = [];
        $modulos_limpiados = [];

        if ($restaurar) {
            // Obtener snapshot de configuracion anterior
            $snapshot_config = $this->obtener_snapshot($plantilla_id, 'config_anterior');

            if (!empty($snapshot_config)) {
                foreach ($modulos_configurados as $modulo_id) {
                    $opcion_nombre = 'flavor_chat_ia_module_' . $modulo_id;

                    if (isset($snapshot_config[$modulo_id])) {
                        // Restaurar configuracion anterior
                        update_option($opcion_nombre, $snapshot_config[$modulo_id]);
                        $modulos_restaurados[] = $modulo_id;
                    } else {
                        // No habia configuracion anterior, eliminar la actual
                        delete_option($opcion_nombre);
                        $modulos_limpiados[] = $modulo_id;
                    }
                }
            } else {
                $this->registrar_advertencia(
                    'sin_snapshot',
                    __('No se encontro snapshot de configuracion anterior.', FLAVOR_PLATFORM_TEXT_DOMAIN)
                );

                // Sin snapshot, solo limpiar las configuraciones
                foreach ($modulos_configurados as $modulo_id) {
                    $opcion_nombre = 'flavor_chat_ia_module_' . $modulo_id;
                    delete_option($opcion_nombre);
                    $modulos_limpiados[] = $modulo_id;
                }
            }
        }

        // Limpiar metadatos
        $this->eliminar_meta_instalacion($plantilla_id);

        $mensaje = '';
        if (!empty($modulos_restaurados)) {
            $mensaje .= sprintf(
                __('%d modulos restaurados a configuracion anterior.', FLAVOR_PLATFORM_TEXT_DOMAIN),
                count($modulos_restaurados)
            );
        }
        if (!empty($modulos_limpiados)) {
            $mensaje .= ' ' . sprintf(
                __('%d modulos con configuracion eliminada.', FLAVOR_PLATFORM_TEXT_DOMAIN),
                count($modulos_limpiados)
            );
        }

        if (empty($mensaje)) {
            $mensaje = __('Configuraciones procesadas.', FLAVOR_PLATFORM_TEXT_DOMAIN);
        }

        return $this->respuesta_exito(trim($mensaje), [
            'modulos_restaurados' => $modulos_restaurados,
            'modulos_limpiados'   => $modulos_limpiados,
        ]);
    }

    /**
     * Verifica el estado de las configuraciones
     *
     * @param string $plantilla_id ID de la plantilla
     * @param array $definicion Definicion con configuraciones esperadas
     * @return array Estado de las configuraciones
     */
    public function verificar_estado($plantilla_id, $definicion = []) {
        $configuraciones_esperadas = $definicion['config'] ?? [];

        if (empty($configuraciones_esperadas)) {
            return [
                'estado'   => 'no_aplica',
                'detalles' => [],
                'mensaje'  => __('No hay configuraciones definidas para esta plantilla.', FLAVOR_PLATFORM_TEXT_DOMAIN),
            ];
        }

        $modulos_configurados = [];
        $modulos_sin_configurar = [];
        $modulos_parciales = [];

        foreach ($configuraciones_esperadas as $modulo_id => $config_esperada) {
            $modulo_normalizado = str_replace('-', '_', $modulo_id);
            $opcion_nombre = 'flavor_chat_ia_module_' . $modulo_normalizado;
            $config_actual = get_option($opcion_nombre, []);

            if (empty($config_actual)) {
                $modulos_sin_configurar[] = [
                    'modulo'          => $modulo_normalizado,
                    'claves_faltantes' => array_keys($config_esperada),
                ];
                continue;
            }

            // Comparar configuraciones
            $comparacion = $this->comparar_configuraciones($config_esperada, $config_actual);

            if ($comparacion['completo']) {
                $modulos_configurados[] = [
                    'modulo'         => $modulo_normalizado,
                    'coincidencias'  => $comparacion['coincidencias'],
                ];
            } else {
                $modulos_parciales[] = [
                    'modulo'          => $modulo_normalizado,
                    'configurado'     => $comparacion['coincidencias'],
                    'faltantes'       => $comparacion['faltantes'],
                    'diferentes'      => $comparacion['diferentes'],
                ];
            }
        }

        // Determinar estado
        $total_esperados = count($configuraciones_esperadas);
        $total_completos = count($modulos_configurados);

        if ($total_completos === $total_esperados) {
            $estado = 'completo';
        } elseif ($total_completos > 0 || !empty($modulos_parciales)) {
            $estado = 'parcial';
        } else {
            $estado = 'no_instalado';
        }

        return [
            'estado'   => $estado,
            'detalles' => [
                'configurados'     => $modulos_configurados,
                'parciales'        => $modulos_parciales,
                'sin_configurar'   => $modulos_sin_configurar,
            ],
            'mensaje'  => sprintf(
                __('%d de %d modulos con configuracion completa.', FLAVOR_PLATFORM_TEXT_DOMAIN),
                $total_completos,
                $total_esperados
            ),
        ];
    }

    /**
     * Aplica configuracion a un modulo especifico
     *
     * @param string $modulo_id ID del modulo
     * @param array $config_nueva Configuracion a aplicar
     * @param array $config_actual Configuracion actual
     * @param bool $sobrescribir Si debe sobrescribir valores existentes
     * @return array Resultado
     */
    private function aplicar_configuracion_modulo($modulo_id, $config_nueva, $config_actual, $sobrescribir = false) {
        $opcion_nombre = 'flavor_chat_ia_module_' . $modulo_id;

        try {
            if ($sobrescribir) {
                // Sobrescribir completamente
                $config_final = array_merge($config_actual, $config_nueva);
            } else {
                // Solo aplicar valores que no existen
                $config_final = $config_actual;
                foreach ($config_nueva as $clave => $valor) {
                    if (!isset($config_final[$clave])) {
                        $config_final[$clave] = $valor;
                    }
                }
            }

            // Agregar metadatos de cuando se aplico
            $config_final['_flavor_config_applied'] = current_time('mysql');
            $config_final['_flavor_config_source'] = 'template';

            $resultado = update_option($opcion_nombre, $config_final);

            return [
                'success' => true,
                'config'  => $config_final,
            ];

        } catch (Exception $excepcion) {
            $this->registrar_error(
                'config_error',
                sprintf(__('Error aplicando configuracion a %s: %s', FLAVOR_PLATFORM_TEXT_DOMAIN), $modulo_id, $excepcion->getMessage())
            );

            return [
                'success' => false,
                'error'   => $excepcion->getMessage(),
            ];
        }
    }

    /**
     * Compara configuracion esperada vs actual
     *
     * @param array $esperada Configuracion esperada
     * @param array $actual Configuracion actual
     * @return array Resultado de comparacion
     */
    private function comparar_configuraciones($esperada, $actual) {
        $coincidencias = [];
        $faltantes = [];
        $diferentes = [];

        foreach ($esperada as $clave => $valor_esperado) {
            if (!isset($actual[$clave])) {
                $faltantes[] = $clave;
            } elseif ($actual[$clave] !== $valor_esperado) {
                $diferentes[] = [
                    'clave'    => $clave,
                    'esperado' => $valor_esperado,
                    'actual'   => $actual[$clave],
                ];
            } else {
                $coincidencias[] = $clave;
            }
        }

        return [
            'completo'      => empty($faltantes) && empty($diferentes),
            'coincidencias' => $coincidencias,
            'faltantes'     => $faltantes,
            'diferentes'    => $diferentes,
        ];
    }

    /**
     * Obtiene la configuracion actual de un modulo
     *
     * @param string $modulo_id ID del modulo
     * @return array
     */
    public function obtener_configuracion_modulo($modulo_id) {
        $modulo_normalizado = str_replace('-', '_', $modulo_id);
        $opcion_nombre = 'flavor_chat_ia_module_' . $modulo_normalizado;
        return get_option($opcion_nombre, []);
    }

    /**
     * Valida una configuracion contra un esquema
     *
     * @param array $config Configuracion a validar
     * @param array $esquema Esquema de validacion
     * @return array Resultado con 'valido' y 'errores'
     */
    public function validar_configuracion($config, $esquema) {
        $errores = [];

        foreach ($esquema as $clave => $reglas) {
            $valor = $config[$clave] ?? null;

            // Verificar requerido
            if (!empty($reglas['required']) && $valor === null) {
                $errores[] = sprintf(__('El campo "%s" es requerido.', FLAVOR_PLATFORM_TEXT_DOMAIN), $clave);
                continue;
            }

            if ($valor === null) {
                continue;
            }

            // Verificar tipo
            if (!empty($reglas['type'])) {
                $tipo_valido = $this->verificar_tipo($valor, $reglas['type']);
                if (!$tipo_valido) {
                    $errores[] = sprintf(
                        __('El campo "%s" debe ser de tipo %s.', FLAVOR_PLATFORM_TEXT_DOMAIN),
                        $clave,
                        $reglas['type']
                    );
                }
            }

            // Verificar valores permitidos
            if (!empty($reglas['enum']) && !in_array($valor, $reglas['enum'], true)) {
                $errores[] = sprintf(
                    __('El campo "%s" tiene un valor no permitido.', FLAVOR_PLATFORM_TEXT_DOMAIN),
                    $clave
                );
            }

            // Verificar minimo/maximo
            if (isset($reglas['min']) && $valor < $reglas['min']) {
                $errores[] = sprintf(
                    __('El campo "%s" debe ser mayor o igual a %s.', FLAVOR_PLATFORM_TEXT_DOMAIN),
                    $clave,
                    $reglas['min']
                );
            }

            if (isset($reglas['max']) && $valor > $reglas['max']) {
                $errores[] = sprintf(
                    __('El campo "%s" debe ser menor o igual a %s.', FLAVOR_PLATFORM_TEXT_DOMAIN),
                    $clave,
                    $reglas['max']
                );
            }
        }

        return [
            'valido'  => empty($errores),
            'errores' => $errores,
        ];
    }

    /**
     * Verifica el tipo de un valor
     *
     * @param mixed $valor Valor a verificar
     * @param string $tipo_esperado Tipo esperado
     * @return bool
     */
    private function verificar_tipo($valor, $tipo_esperado) {
        switch ($tipo_esperado) {
            case 'string':
                return is_string($valor);
            case 'integer':
            case 'int':
                return is_int($valor);
            case 'float':
            case 'number':
                return is_numeric($valor);
            case 'boolean':
            case 'bool':
                return is_bool($valor);
            case 'array':
                return is_array($valor);
            default:
                return true;
        }
    }
}

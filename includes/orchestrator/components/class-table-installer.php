<?php
/**
 * Componente Instalador de Tablas
 *
 * Gestiona la creacion de tablas de base de datos para los modulos
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
 * Clase Flavor_Table_Installer
 *
 * Crea las tablas necesarias para los modulos de una plantilla
 */
class Flavor_Table_Installer extends Flavor_Template_Component_Base {

    /**
     * Constructor
     */
    public function __construct() {
        $this->componente_id = 'tablas';
        $this->componente_nombre = __('Instalador de Tablas', 'flavor-chat-ia');
    }

    /**
     * Instala las tablas necesarias para los modulos
     *
     * @param string $plantilla_id ID de la plantilla
     * @param array $definicion Definicion con 'modulos' a instalar tablas
     * @param array $opciones Opciones adicionales
     * @return array Resultado de la operacion
     */
    public function instalar($plantilla_id, $definicion, $opciones = []) {
        $this->limpiar_mensajes();

        // Obtener modulos que necesitan tablas
        $modulos_a_procesar = $definicion['modulos'] ?? [];

        if (empty($modulos_a_procesar)) {
            // Si no se especifican, obtener los modulos activos de la plantilla
            $configuracion_plugin = get_option('flavor_chat_ia_settings', []);
            $modulos_a_procesar = $configuracion_plugin['active_modules'] ?? [];
        }

        if (empty($modulos_a_procesar)) {
            return $this->respuesta_exito(
                __('No hay modulos para los que crear tablas.', 'flavor-chat-ia'),
                ['tablas_creadas' => []]
            );
        }

        $tablas_creadas = [];
        $tablas_existentes = [];
        $tablas_fallidas = [];

        foreach ($modulos_a_procesar as $modulo_id) {
            $modulo_normalizado = str_replace('-', '_', $modulo_id);
            $resultado = $this->crear_tablas_modulo($modulo_normalizado);

            if ($resultado['success']) {
                $tablas_creadas = array_merge($tablas_creadas, $resultado['creadas'] ?? []);
                $tablas_existentes = array_merge($tablas_existentes, $resultado['existentes'] ?? []);
            } else {
                $tablas_fallidas[$modulo_normalizado] = $resultado['error'] ?? __('Error desconocido', 'flavor-chat-ia');
            }
        }

        // Guardar registro de tablas creadas
        $this->guardar_meta_instalacion($plantilla_id, 'tablas_creadas', $tablas_creadas);

        $mensaje = sprintf(
            __('Proceso de tablas completado: %d creadas, %d ya existian.', 'flavor-chat-ia'),
            count($tablas_creadas),
            count($tablas_existentes)
        );

        if (!empty($tablas_fallidas)) {
            $mensaje .= ' ' . sprintf(
                __('%d modulos con errores.', 'flavor-chat-ia'),
                count($tablas_fallidas)
            );
        }

        return $this->respuesta_exito($mensaje, [
            'tablas_creadas'    => $tablas_creadas,
            'tablas_existentes' => $tablas_existentes,
            'tablas_fallidas'   => $tablas_fallidas,
        ]);
    }

    /**
     * Desinstala/marca las tablas como huerfanas
     *
     * NOTA: Por seguridad, NO eliminamos tablas automaticamente.
     * Solo las marcamos como huerfanas para que el admin decida.
     *
     * @param string $plantilla_id ID de la plantilla
     * @param array $definicion Definicion de la plantilla
     * @param array $opciones Opciones adicionales
     * @return array Resultado de la operacion
     */
    public function desinstalar($plantilla_id, $definicion = [], $opciones = []) {
        $this->limpiar_mensajes();

        $tablas_creadas = $this->obtener_meta_instalacion($plantilla_id, 'tablas_creadas', []);

        if (empty($tablas_creadas)) {
            return $this->respuesta_exito(
                __('No hay tablas registradas para esta plantilla.', 'flavor-chat-ia'),
                ['tablas_huerfanas' => []]
            );
        }

        // Verificar cuales tablas ya no estan en uso por otras plantillas
        $tablas_huerfanas = [];
        $tablas_en_uso = [];

        foreach ($tablas_creadas as $nombre_tabla) {
            if ($this->tabla_en_uso_por_otra_plantilla($nombre_tabla, $plantilla_id)) {
                $tablas_en_uso[] = $nombre_tabla;
            } else {
                $tablas_huerfanas[] = $nombre_tabla;
            }
        }

        // Marcar tablas huerfanas en opciones para revision posterior
        if (!empty($tablas_huerfanas)) {
            $tablas_huerfanas_global = get_option('flavor_tablas_huerfanas', []);
            $tablas_huerfanas_global[$plantilla_id] = [
                'tablas' => $tablas_huerfanas,
                'fecha'  => current_time('mysql'),
            ];
            update_option('flavor_tablas_huerfanas', $tablas_huerfanas_global);
        }

        // Limpiar metadatos
        $this->eliminar_meta_instalacion($plantilla_id);

        $this->registrar_advertencia(
            'tablas_no_eliminadas',
            __('Las tablas no se eliminan automaticamente por seguridad. Revise las tablas huerfanas en configuracion.', 'flavor-chat-ia')
        );

        return $this->respuesta_exito(
            sprintf(
                __('%d tablas marcadas como huerfanas. Por seguridad, no se eliminan automaticamente.', 'flavor-chat-ia'),
                count($tablas_huerfanas)
            ),
            [
                'tablas_huerfanas' => $tablas_huerfanas,
                'tablas_en_uso'    => $tablas_en_uso,
            ]
        );
    }

    /**
     * Verifica el estado de las tablas para una plantilla
     *
     * @param string $plantilla_id ID de la plantilla
     * @param array $definicion Definicion con modulos
     * @return array Estado de las tablas
     */
    public function verificar_estado($plantilla_id, $definicion = []) {
        global $wpdb;

        $modulos = $definicion['modulos'] ?? [];

        if (empty($modulos)) {
            $configuracion_plugin = get_option('flavor_chat_ia_settings', []);
            $modulos = $configuracion_plugin['active_modules'] ?? [];
        }

        $tablas_esperadas = [];
        $tablas_existentes = [];
        $tablas_faltantes = [];

        foreach ($modulos as $modulo_id) {
            $modulo_normalizado = str_replace('-', '_', $modulo_id);
            $tablas_modulo = $this->obtener_tablas_esperadas_modulo($modulo_normalizado);

            foreach ($tablas_modulo as $nombre_tabla) {
                $tablas_esperadas[] = $nombre_tabla;

                if (Flavor_Chat_Helpers::tabla_existe($nombre_tabla)) {
                    $tablas_existentes[] = $nombre_tabla;
                } else {
                    $tablas_faltantes[] = $nombre_tabla;
                }
            }
        }

        // Determinar estado
        if (empty($tablas_esperadas)) {
            $estado = 'no_aplica';
        } elseif (empty($tablas_faltantes)) {
            $estado = 'completo';
        } elseif (!empty($tablas_existentes)) {
            $estado = 'parcial';
        } else {
            $estado = 'no_instalado';
        }

        return [
            'estado'   => $estado,
            'detalles' => [
                'esperadas'  => $tablas_esperadas,
                'existentes' => $tablas_existentes,
                'faltantes'  => $tablas_faltantes,
            ],
            'mensaje'  => $this->generar_mensaje_estado($tablas_existentes, $tablas_faltantes),
        ];
    }

    /**
     * Crea las tablas para un modulo especifico
     *
     * @param string $modulo_id ID del modulo
     * @return array Resultado con tablas creadas/existentes
     */
    private function crear_tablas_modulo($modulo_id) {
        $tablas_creadas = [];
        $tablas_existentes = [];

        // Metodo 1: Buscar archivo install.php del modulo
        $ruta_install = FLAVOR_CHAT_IA_PATH . 'includes/modules/' . str_replace('_', '-', $modulo_id) . '/install.php';

        if (file_exists($ruta_install)) {
            require_once $ruta_install;

            $funcion_install = 'flavor_' . $modulo_id . '_install';
            if (function_exists($funcion_install)) {
                try {
                    $resultado_install = call_user_func($funcion_install);
                    if (is_array($resultado_install)) {
                        $tablas_creadas = array_merge($tablas_creadas, $resultado_install['tablas'] ?? []);
                    }
                } catch (Exception $excepcion) {
                    $this->registrar_error(
                        'install_error',
                        sprintf(__('Error ejecutando install.php para %s: %s', 'flavor-chat-ia'), $modulo_id, $excepcion->getMessage())
                    );
                }
            }
        }

        // Metodo 2: Intentar con el metodo create_tables() del modulo
        if (empty($tablas_creadas)) {
            $instancia_modulo = $this->obtener_instancia_modulo($modulo_id);

            if ($instancia_modulo) {
                // Intentar maybe_create_tables primero
                if (method_exists($instancia_modulo, 'maybe_create_tables')) {
                    $instancia_modulo->maybe_create_tables();
                }
                // Luego create_tables si existe
                elseif (method_exists($instancia_modulo, 'create_tables')) {
                    $instancia_modulo->create_tables();
                }

                // Verificar que tablas se crearon
                $tablas_esperadas = $this->obtener_tablas_esperadas_modulo($modulo_id);
                foreach ($tablas_esperadas as $nombre_tabla) {
                    if (Flavor_Chat_Helpers::tabla_existe($nombre_tabla)) {
                        $tablas_creadas[] = $nombre_tabla;
                    }
                }
            }
        }

        // Verificar tablas existentes vs creadas
        $tablas_esperadas = $this->obtener_tablas_esperadas_modulo($modulo_id);
        foreach ($tablas_esperadas as $nombre_tabla) {
            if (Flavor_Chat_Helpers::tabla_existe($nombre_tabla)) {
                if (!in_array($nombre_tabla, $tablas_creadas, true)) {
                    $tablas_existentes[] = $nombre_tabla;
                }
            }
        }

        return [
            'success'    => true,
            'creadas'    => $tablas_creadas,
            'existentes' => $tablas_existentes,
        ];
    }

    /**
     * Obtiene las tablas esperadas para un modulo
     *
     * @param string $modulo_id ID del modulo
     * @return array Nombres de tablas esperadas
     */
    private function obtener_tablas_esperadas_modulo($modulo_id) {
        global $wpdb;

        // Mapeo de modulos a sus tablas
        $mapeo_tablas = [
            'banco_tiempo' => [
                $wpdb->prefix . 'flavor_banco_tiempo_servicios',
                $wpdb->prefix . 'flavor_banco_tiempo_transacciones',
            ],
            'eventos' => [
                $wpdb->prefix . 'flavor_eventos',
                $wpdb->prefix . 'flavor_eventos_asistentes',
            ],
            'ayuda_vecinal' => [
                $wpdb->prefix . 'flavor_ayuda_vecinal_solicitudes',
            ],
            'socios' => [
                $wpdb->prefix . 'flavor_socios',
                $wpdb->prefix . 'flavor_socios_pagos',
            ],
            'fichaje_empleados' => [
                $wpdb->prefix . 'flavor_fichajes',
            ],
            'incidencias' => [
                $wpdb->prefix . 'flavor_incidencias',
            ],
            'participacion' => [
                $wpdb->prefix . 'flavor_propuestas',
                $wpdb->prefix . 'flavor_votos',
            ],
            'presupuestos_participativos' => [
                $wpdb->prefix . 'flavor_presupuestos_proyectos',
                $wpdb->prefix . 'flavor_presupuestos_votos',
            ],
            'biblioteca' => [
                $wpdb->prefix . 'flavor_biblioteca_libros',
                $wpdb->prefix . 'flavor_biblioteca_prestamos',
            ],
            'carpooling' => [
                $wpdb->prefix . 'flavor_carpooling_viajes',
                $wpdb->prefix . 'flavor_carpooling_reservas',
            ],
            'bicicletas_compartidas' => [
                $wpdb->prefix . 'flavor_bicicletas',
                $wpdb->prefix . 'flavor_bicicletas_reservas',
            ],
            'espacios_comunes' => [
                $wpdb->prefix . 'flavor_espacios',
                $wpdb->prefix . 'flavor_espacios_reservas',
            ],
            'huertos_urbanos' => [
                $wpdb->prefix . 'flavor_huertos_parcelas',
                $wpdb->prefix . 'flavor_huertos_cultivos',
            ],
            'compostaje' => [
                $wpdb->prefix . 'flavor_composteras',
                $wpdb->prefix . 'flavor_compostaje_aportes',
            ],
            'cursos' => [
                $wpdb->prefix . 'flavor_cursos',
                $wpdb->prefix . 'flavor_cursos_inscripciones',
            ],
            'talleres' => [
                $wpdb->prefix . 'flavor_talleres',
                $wpdb->prefix . 'flavor_talleres_inscripciones',
            ],
        ];

        return $mapeo_tablas[$modulo_id] ?? [];
    }

    /**
     * Obtiene una instancia del modulo
     *
     * @param string $modulo_id ID del modulo
     * @return object|null
     */
    private function obtener_instancia_modulo($modulo_id) {
        if (!class_exists('Flavor_Chat_Module_Loader')) {
            return null;
        }

        $loader = Flavor_Chat_Module_Loader::get_instance();
        return $loader->get_module($modulo_id);
    }

    /**
     * Verifica si una tabla esta en uso por otra plantilla
     *
     * @param string $nombre_tabla Nombre de la tabla
     * @param string $plantilla_excluir ID de plantilla a excluir
     * @return bool
     */
    private function tabla_en_uso_por_otra_plantilla($nombre_tabla, $plantilla_excluir) {
        $plantillas_activas = get_option('flavor_plantillas_activas', []);

        foreach ($plantillas_activas as $plantilla_id => $datos) {
            if ($plantilla_id === $plantilla_excluir) {
                continue;
            }

            $tablas_plantilla = get_option("flavor_template_{$plantilla_id}_tablas", []);
            $tablas_creadas = $tablas_plantilla['tablas_creadas'] ?? [];

            if (in_array($nombre_tabla, $tablas_creadas, true)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Genera mensaje descriptivo del estado
     *
     * @param array $tablas_existentes Tablas que existen
     * @param array $tablas_faltantes Tablas que faltan
     * @return string
     */
    private function generar_mensaje_estado($tablas_existentes, $tablas_faltantes) {
        $total_existentes = count($tablas_existentes);
        $total_faltantes = count($tablas_faltantes);

        if ($total_faltantes === 0 && $total_existentes > 0) {
            return sprintf(
                __('Todas las tablas estan creadas (%d tablas).', 'flavor-chat-ia'),
                $total_existentes
            );
        }

        if ($total_faltantes > 0) {
            return sprintf(
                __('Faltan %d tablas por crear.', 'flavor-chat-ia'),
                $total_faltantes
            );
        }

        return __('No se requieren tablas para esta configuracion.', 'flavor-chat-ia');
    }
}

<?php
/**
 * Herramientas del Asistente IA para Administradores
 *
 * Define las herramientas que puede usar el asistente para consultar
 * y gestionar el plugin Calendario Experiencias
 *
 * @package ChatIAAddon
 */

if (!defined('ABSPATH')) {
    exit;
}

// Evitar redeclaración si ya existe
if (class_exists('Chat_IA_Admin_Assistant_Tools')) {
    return;
}

class Chat_IA_Admin_Assistant_Tools {

    /**
     * Instancia singleton
     */
    private static $instance = null;

    /**
     * Control de acceso por roles
     */
    private $role_access = null;

    /**
     * Obtiene la instancia singleton
     */
    public static function get_instance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Constructor privado
     */
    private function __construct() {}

    /**
     * Establece la referencia al control de acceso por roles
     */
    public function set_role_access($role_access) {
        $this->role_access = $role_access;
    }

    /**
     * Genera URLs a páginas del admin
     */
    private function get_admin_urls() {
        return [
            'calendario' => admin_url('admin.php?page=calendario_experiencias'),
            'estados' => admin_url('admin.php?page=calendario_experiencias&tab=estados'),
            'tickets' => admin_url('admin.php?page=calendario-gestion-tickets&tab=tipos'),
            'dashboard' => admin_url('admin.php?page=calendario-gestion-tickets'),
            'limites' => admin_url('admin.php?page=calendario-gestion-tickets&tab=limites'),
            'bloqueos' => admin_url('admin.php?page=calendario-gestion-tickets&tab=bloqueos'),
            'asistente' => admin_url('admin.php?page=calendario-asistente-ia'),
        ];
    }

    /**
     * Genera información de contexto para las respuestas
     */
    private function generar_contexto_respuesta($tipo, $datos = []) {
        $urls = $this->get_admin_urls();

        $contexto = [
            'enlaces_relevantes' => [],
            'instrucciones' => '',
        ];

        switch ($tipo) {
            case 'calendario':
                $contexto['enlaces_relevantes'][] = [
                    'texto' => 'Ver calendario',
                    'url' => $urls['calendario'],
                ];
                $contexto['instrucciones'] = 'Refresca la página del calendario para ver los cambios aplicados.';
                break;

            case 'estados':
                $contexto['enlaces_relevantes'][] = [
                    'texto' => 'Ver estados',
                    'url' => $urls['estados'],
                ];
                $contexto['enlaces_relevantes'][] = [
                    'texto' => 'Ver calendario',
                    'url' => $urls['calendario'],
                ];
                $contexto['instrucciones'] = 'Los cambios en estados se reflejan inmediatamente en el calendario.';
                break;

            case 'tickets':
                $contexto['enlaces_relevantes'][] = [
                    'texto' => 'Ver tipos de ticket',
                    'url' => $urls['tickets'],
                ];
                $contexto['instrucciones'] = 'Los cambios en tickets afectan a nuevas reservas. Las reservas existentes no se modifican.';
                break;

            case 'backup':
                $contexto['instrucciones'] = 'Puedes restaurar este backup en cualquier momento diciendo: "restaura el backup [ID]"';
                break;
        }

        return $contexto;
    }

    /**
     * Obtiene la definición de todas las herramientas disponibles
     *
     * @return array
     */
    public function get_tools_definition() {
        return [
            // ==========================================
            // CONSULTAS DE RESERVAS
            // ==========================================
            [
                'name' => 'obtener_reservas_dia',
                'description' => 'Obtiene las reservas para un día específico. Devuelve lista de reservas con detalles como cliente, tipo de ticket, cantidad, estado.',
                'input_schema' => [
                    'type' => 'object',
                    'properties' => [
                        'fecha' => [
                            'type' => 'string',
                            'description' => 'Fecha en formato YYYY-MM-DD',
                        ],
                        'tipo_ticket' => [
                            'type' => 'string',
                            'description' => 'Filtrar por tipo de ticket (slug). Opcional.',
                        ],
                        'estado' => [
                            'type' => 'string',
                            'enum' => ['pendiente', 'usado', 'cancelado', 'todos'],
                            'description' => 'Filtrar por estado del ticket. Por defecto "todos".',
                        ],
                    ],
                    'required' => ['fecha'],
                ],
            ],
            [
                'name' => 'obtener_plazas_disponibles',
                'description' => 'Obtiene las plazas disponibles para un tipo de ticket en una fecha. Muestra plazas totales, vendidas, en carrito y libres.',
                'input_schema' => [
                    'type' => 'object',
                    'properties' => [
                        'fecha' => [
                            'type' => 'string',
                            'description' => 'Fecha en formato YYYY-MM-DD',
                        ],
                        'tipo_ticket' => [
                            'type' => 'string',
                            'description' => 'Slug del tipo de ticket. Si no se especifica, devuelve para todos los tipos.',
                        ],
                    ],
                    'required' => ['fecha'],
                ],
            ],
            [
                'name' => 'buscar_reservas',
                'description' => 'Busca reservas por cliente, código de ticket o número de pedido.',
                'input_schema' => [
                    'type' => 'object',
                    'properties' => [
                        'termino' => [
                            'type' => 'string',
                            'description' => 'Término de búsqueda (nombre cliente, código ticket, número pedido)',
                        ],
                        'limite' => [
                            'type' => 'integer',
                            'description' => 'Número máximo de resultados. Por defecto 20.',
                        ],
                    ],
                    'required' => ['termino'],
                ],
            ],
            [
                'name' => 'obtener_resumen_periodo',
                'description' => 'Obtiene un resumen de reservas e ingresos para un período de tiempo.',
                'input_schema' => [
                    'type' => 'object',
                    'properties' => [
                        'fecha_inicio' => [
                            'type' => 'string',
                            'description' => 'Fecha inicio en formato YYYY-MM-DD',
                        ],
                        'fecha_fin' => [
                            'type' => 'string',
                            'description' => 'Fecha fin en formato YYYY-MM-DD',
                        ],
                        'agrupar_por' => [
                            'type' => 'string',
                            'enum' => ['dia', 'semana', 'mes', 'tipo_ticket'],
                            'description' => 'Cómo agrupar los resultados. Por defecto "dia".',
                        ],
                        'modo' => [
                            'type' => 'string',
                            'enum' => ['normal', 'kpi', 'tabla', 'json'],
                            'description' => 'Modo de respuesta: kpi (una linea), tabla (markdown), json (solo datos). Por defecto "normal".',
                        ],
                    ],
                    'required' => ['fecha_inicio', 'fecha_fin'],
                ],
            ],

            // ==========================================
            // ESTADÍSTICAS Y CONTABILIDAD
            // ==========================================
            [
                'name' => 'obtener_estadisticas_ingresos',
                'description' => 'Obtiene estadísticas de ingresos por período, desglosado por tipo de ticket y complementarios.',
                'input_schema' => [
                    'type' => 'object',
                    'properties' => [
                        'fecha_inicio' => [
                            'type' => 'string',
                            'description' => 'Fecha inicio en formato YYYY-MM-DD',
                        ],
                        'fecha_fin' => [
                            'type' => 'string',
                            'description' => 'Fecha fin en formato YYYY-MM-DD',
                        ],
                        'incluir_iva' => [
                            'type' => 'boolean',
                            'description' => 'Si incluir desglose de IVA. Por defecto true.',
                        ],
                    ],
                    'required' => ['fecha_inicio', 'fecha_fin'],
                ],
            ],
            [
                'name' => 'obtener_tickets_mas_vendidos',
                'description' => 'Obtiene los tipos de ticket más vendidos en un período.',
                'input_schema' => [
                    'type' => 'object',
                    'properties' => [
                        'fecha_inicio' => [
                            'type' => 'string',
                            'description' => 'Fecha inicio en formato YYYY-MM-DD. Si no se indica, últimos 30 días.',
                        ],
                        'fecha_fin' => [
                            'type' => 'string',
                            'description' => 'Fecha fin en formato YYYY-MM-DD',
                        ],
                        'limite' => [
                            'type' => 'integer',
                            'description' => 'Número de resultados. Por defecto 10.',
                        ],
                    ],
                    'required' => [],
                ],
            ],
            [
                'name' => 'obtener_comparativa_periodos',
                'description' => 'Compara reservas e ingresos entre dos períodos de tiempo.',
                'input_schema' => [
                    'type' => 'object',
                    'properties' => [
                        'periodo1_inicio' => [
                            'type' => 'string',
                            'description' => 'Inicio del primer período (YYYY-MM-DD)',
                        ],
                        'periodo1_fin' => [
                            'type' => 'string',
                            'description' => 'Fin del primer período (YYYY-MM-DD)',
                        ],
                        'periodo2_inicio' => [
                            'type' => 'string',
                            'description' => 'Inicio del segundo período (YYYY-MM-DD)',
                        ],
                        'periodo2_fin' => [
                            'type' => 'string',
                            'description' => 'Fin del segundo período (YYYY-MM-DD)',
                        ],
                    ],
                    'required' => ['periodo1_inicio', 'periodo1_fin', 'periodo2_inicio', 'periodo2_fin'],
                ],
            ],

            // ==========================================
            // GESTIÓN DEL CALENDARIO
            // ==========================================
            [
                'name' => 'obtener_estado_calendario',
                'description' => 'Obtiene el estado del calendario para un rango de fechas. Muestra días con disponibilidad, bloqueos, límites especiales.',
                'input_schema' => [
                    'type' => 'object',
                    'properties' => [
                        'fecha_inicio' => [
                            'type' => 'string',
                            'description' => 'Fecha inicio en formato YYYY-MM-DD',
                        ],
                        'fecha_fin' => [
                            'type' => 'string',
                            'description' => 'Fecha fin en formato YYYY-MM-DD',
                        ],
                    ],
                    'required' => ['fecha_inicio', 'fecha_fin'],
                ],
            ],
            [
                'name' => 'modificar_limite_plazas',
                'description' => 'Modifica el límite de plazas para un tipo de ticket en una fecha específica.',
                'input_schema' => [
                    'type' => 'object',
                    'properties' => [
                        'fecha' => [
                            'type' => 'string',
                            'description' => 'Fecha en formato YYYY-MM-DD',
                        ],
                        'tipo_ticket' => [
                            'type' => 'string',
                            'description' => 'Slug del tipo de ticket',
                        ],
                        'nuevo_limite' => [
                            'type' => 'integer',
                            'description' => 'Nuevo límite de plazas',
                        ],
                    ],
                    'required' => ['fecha', 'tipo_ticket', 'nuevo_limite'],
                ],
            ],
            [
                'name' => 'bloquear_ticket',
                'description' => 'Bloquea un ticket específico por su código.',
                'input_schema' => [
                    'type' => 'object',
                    'properties' => [
                        'codigo_ticket' => [
                            'type' => 'string',
                            'description' => 'Código único del ticket a bloquear',
                        ],
                        'razon' => [
                            'type' => 'string',
                            'description' => 'Razón del bloqueo',
                        ],
                    ],
                    'required' => ['codigo_ticket', 'razon'],
                ],
            ],
            [
                'name' => 'desbloquear_ticket',
                'description' => 'Desbloquea un ticket previamente bloqueado.',
                'input_schema' => [
                    'type' => 'object',
                    'properties' => [
                        'codigo_ticket' => [
                            'type' => 'string',
                            'description' => 'Código único del ticket a desbloquear',
                        ],
                    ],
                    'required' => ['codigo_ticket'],
                ],
            ],

            // ==========================================
            // INFORMACIÓN DEL SISTEMA
            // ==========================================
            [
                'name' => 'obtener_tipos_ticket',
                'description' => 'Obtiene la lista de todos los tipos de ticket configurados con sus detalles (precio, plazas, duración, etc.).',
                'input_schema' => [
                    'type' => 'object',
                    'properties' => [
                        'incluir_inactivos' => [
                            'type' => 'boolean',
                            'description' => 'Si incluir tipos inactivos. Por defecto false.',
                        ],
                    ],
                    'required' => [],
                ],
            ],
            [
                'name' => 'obtener_complementarios',
                'description' => 'Obtiene la lista de productos complementarios configurados.',
                'input_schema' => [
                    'type' => 'object',
                    'properties' => [
                        'tipo_ticket' => [
                            'type' => 'string',
                            'description' => 'Filtrar por tipo de ticket específico. Opcional.',
                        ],
                    ],
                    'required' => [],
                ],
            ],
            [
                'name' => 'obtener_configuracion_sistema',
                'description' => 'Obtiene la configuración actual del sistema (modo pruebas, estados del calendario, etc.).',
                'input_schema' => [
                    'type' => 'object',
                    'properties' => (object)[],
                    'required' => [],
                ],
            ],

            // ==========================================
            // AYUDA Y SHORTCODES
            // ==========================================
            [
                'name' => 'obtener_shortcodes_disponibles',
                'description' => 'Obtiene la lista de shortcodes disponibles con ejemplos de uso.',
                'input_schema' => [
                    'type' => 'object',
                    'properties' => (object)[],
                    'required' => [],
                ],
            ],
            [
                'name' => 'generar_shortcode',
                'description' => 'Genera un shortcode personalizado según los parámetros indicados.',
                'input_schema' => [
                    'type' => 'object',
                    'properties' => [
                        'tipo' => [
                            'type' => 'string',
                            'enum' => ['reserva_form', 'reserva_producto', 'limpiar_carrito', 'reservas_carrito'],
                            'description' => 'Tipo de shortcode a generar',
                        ],
                        'parametros' => [
                            'type' => 'object',
                            'description' => 'Parámetros del shortcode (fecha, ticket, estado, etc.)',
                        ],
                    ],
                    'required' => ['tipo'],
                ],
            ],
            [
                'name' => 'explicar_seccion',
                'description' => 'Explica cómo funciona una sección específica del plugin.',
                'input_schema' => [
                    'type' => 'object',
                    'properties' => [
                        'seccion' => [
                            'type' => 'string',
                            'enum' => [
                                'dashboard',
                                'tipos_ticket',
                                'limites_plazas',
                                'bloqueos',
                                'bonos',
                                'complementarios',
                                'shortcodes',
                                'qr_scanner',
                                'contabilidad',
                                'campos_personalizados',
                                'dependencias_tickets'
                            ],
                            'description' => 'Sección sobre la que obtener información',
                        ],
                    ],
                    'required' => ['seccion'],
                ],
            ],

            // ==========================================
            // OPERACIONES RÁPIDAS
            // ==========================================
            [
                'name' => 'obtener_proximas_reservas',
                'description' => 'Obtiene las próximas reservas a partir de hoy.',
                'input_schema' => [
                    'type' => 'object',
                    'properties' => [
                        'dias' => [
                            'type' => 'integer',
                            'description' => 'Número de días a consultar. Por defecto 7.',
                        ],
                        'limite' => [
                            'type' => 'integer',
                            'description' => 'Máximo de reservas a mostrar. Por defecto 20.',
                        ],
                    ],
                    'required' => [],
                ],
            ],
            [
                'name' => 'obtener_resumen_hoy',
                'description' => 'Obtiene un resumen rápido del día de hoy: reservas, check-ins, plazas disponibles.',
                'input_schema' => [
                    'type' => 'object',
                    'properties' => (object)[],
                    'required' => [],
                ],
            ],
            [
                'name' => 'obtener_alertas_sistema',
                'description' => 'Obtiene alertas del sistema: días casi llenos, tickets bloqueados, errores recientes.',
                'input_schema' => [
                    'type' => 'object',
                    'properties' => (object)[],
                    'required' => [],
                ],
            ],

            // ==========================================
            // DATOS DE CLIENTES
            // ==========================================
            [
                'name' => 'obtener_datos_clientes',
                'description' => 'Obtiene datos de clientes con reservas: nombre, email, teléfono. Puede filtrar por fecha o buscar cliente específico.',
                'input_schema' => [
                    'type' => 'object',
                    'properties' => [
                        'fecha' => [
                            'type' => 'string',
                            'description' => 'Filtrar clientes con reservas en esta fecha (YYYY-MM-DD). Opcional.',
                        ],
                        'fecha_inicio' => [
                            'type' => 'string',
                            'description' => 'Inicio del rango de fechas (YYYY-MM-DD). Opcional.',
                        ],
                        'fecha_fin' => [
                            'type' => 'string',
                            'description' => 'Fin del rango de fechas (YYYY-MM-DD). Opcional.',
                        ],
                        'buscar' => [
                            'type' => 'string',
                            'description' => 'Buscar por nombre, email o teléfono. Opcional.',
                        ],
                        'limite' => [
                            'type' => 'integer',
                            'description' => 'Número máximo de resultados. Por defecto 50.',
                        ],
                    ],
                    'required' => [],
                ],
            ],
            [
                'name' => 'obtener_detalle_cliente',
                'description' => 'Obtiene información detallada de un cliente específico: historial de reservas, total gastado, etc.',
                'input_schema' => [
                    'type' => 'object',
                    'properties' => [
                        'email' => [
                            'type' => 'string',
                            'description' => 'Email del cliente',
                        ],
                        'incluir_historial' => [
                            'type' => 'boolean',
                            'description' => 'Si incluir historial de reservas. Por defecto true.',
                        ],
                    ],
                    'required' => ['email'],
                ],
            ],

            // ==========================================
            // EXPORTACIÓN DE DATOS
            // ==========================================
            [
                'name' => 'exportar_datos_csv',
                'description' => 'Genera un archivo CSV con los datos solicitados. El archivo se guarda temporalmente y devuelve URL de descarga.',
                'input_schema' => [
                    'type' => 'object',
                    'properties' => [
                        'tipo_datos' => [
                            'type' => 'string',
                            'enum' => ['reservas', 'clientes', 'ingresos', 'tickets_vendidos'],
                            'description' => 'Tipo de datos a exportar',
                        ],
                        'fecha_inicio' => [
                            'type' => 'string',
                            'description' => 'Fecha inicio en formato YYYY-MM-DD',
                        ],
                        'fecha_fin' => [
                            'type' => 'string',
                            'description' => 'Fecha fin en formato YYYY-MM-DD',
                        ],
                        'incluir_cabeceras' => [
                            'type' => 'boolean',
                            'description' => 'Si incluir fila de cabeceras. Por defecto true.',
                        ],
                    ],
                    'required' => ['tipo_datos', 'fecha_inicio', 'fecha_fin'],
                ],
            ],

            // ==========================================
            // GESTIÓN DEL CALENDARIO - ASIGNAR ESTADOS
            // ==========================================
            [
                'name' => 'asignar_estado_calendario',
                'description' => 'Asigna un estado a un día o rango de fechas del calendario. Permite filtrar por días de la semana. Crea backup automático antes del cambio.',
                'input_schema' => [
                    'type' => 'object',
                    'properties' => [
                        'fecha' => [
                            'type' => 'string',
                            'description' => 'Fecha única en formato YYYY-MM-DD. Usar si se quiere asignar a un solo día.',
                        ],
                        'fecha_inicio' => [
                            'type' => 'string',
                            'description' => 'Fecha de inicio del rango (YYYY-MM-DD). Usar junto con fecha_fin para rangos.',
                        ],
                        'fecha_fin' => [
                            'type' => 'string',
                            'description' => 'Fecha de fin del rango (YYYY-MM-DD). Usar junto con fecha_inicio.',
                        ],
                        'estado' => [
                            'type' => 'string',
                            'description' => 'Slug del estado a asignar (ej: "abierto", "cerrado"). Usar cadena vacía para quitar el estado.',
                        ],
                        'dias_semana' => [
                            'type' => 'array',
                            'items' => ['type' => 'integer'],
                            'description' => 'Filtrar por días de la semana: 1=Lunes, 2=Martes... 7=Domingo. Si no se especifica, aplica a todos los días.',
                        ],
                    ],
                    'required' => ['estado'],
                ],
            ],
            [
                'name' => 'resetear_calendario',
                'description' => 'Elimina todos los estados asignados en el calendario. CUIDADO: Esta acción borra toda la configuración de días. Crea backup automático antes.',
                'input_schema' => [
                    'type' => 'object',
                    'properties' => [
                        'confirmar' => [
                            'type' => 'boolean',
                            'description' => 'Debe ser true para confirmar el reseteo.',
                        ],
                    ],
                    'required' => ['confirmar'],
                ],
            ],

            // ==========================================
            // CRUD DE ESTADOS DEL CALENDARIO
            // ==========================================
            [
                'name' => 'crear_estado_calendario',
                'description' => 'Crea un nuevo estado para el calendario. Crea backup automático antes del cambio.',
                'input_schema' => [
                    'type' => 'object',
                    'properties' => [
                        'slug' => [
                            'type' => 'string',
                            'description' => 'Identificador único del estado (sin espacios, minúsculas). Ej: "abierto-mananas"',
                        ],
                        'nombre' => [
                            'type' => 'string',
                            'description' => 'Nombre visible del estado. Ej: "Abierto solo mañanas"',
                        ],
                        'color' => [
                            'type' => 'string',
                            'description' => 'Color en formato hexadecimal. Ej: "#00ff00"',
                        ],
                        'horario' => [
                            'type' => 'string',
                            'description' => 'Horario asociado al estado. Ej: "10:00-14:00". Opcional.',
                        ],
                        'descripcion' => [
                            'type' => 'string',
                            'description' => 'Descripción del estado. Opcional.',
                        ],
                    ],
                    'required' => ['slug', 'nombre', 'color'],
                ],
            ],
            [
                'name' => 'editar_estado_calendario',
                'description' => 'Modifica un estado existente del calendario. Crea backup automático antes del cambio.',
                'input_schema' => [
                    'type' => 'object',
                    'properties' => [
                        'slug' => [
                            'type' => 'string',
                            'description' => 'Slug del estado a modificar',
                        ],
                        'nombre' => [
                            'type' => 'string',
                            'description' => 'Nuevo nombre del estado. Opcional.',
                        ],
                        'color' => [
                            'type' => 'string',
                            'description' => 'Nuevo color en hexadecimal. Opcional.',
                        ],
                        'horario' => [
                            'type' => 'string',
                            'description' => 'Nuevo horario. Opcional.',
                        ],
                        'descripcion' => [
                            'type' => 'string',
                            'description' => 'Nueva descripción. Opcional.',
                        ],
                    ],
                    'required' => ['slug'],
                ],
            ],
            [
                'name' => 'eliminar_estado_calendario',
                'description' => 'Elimina un estado del calendario. CUIDADO: Los días con este estado quedarán sin asignar. Crea backup automático.',
                'input_schema' => [
                    'type' => 'object',
                    'properties' => [
                        'slug' => [
                            'type' => 'string',
                            'description' => 'Slug del estado a eliminar',
                        ],
                        'confirmar' => [
                            'type' => 'boolean',
                            'description' => 'Debe ser true para confirmar la eliminación.',
                        ],
                    ],
                    'required' => ['slug', 'confirmar'],
                ],
            ],
            [
                'name' => 'listar_estados_calendario',
                'description' => 'Lista todos los estados disponibles del calendario con sus configuraciones.',
                'input_schema' => [
                    'type' => 'object',
                    'properties' => (object)[],
                    'required' => [],
                ],
            ],

            // ==========================================
            // CRUD DE TIPOS DE TICKET
            // ==========================================
            [
                'name' => 'crear_tipo_ticket',
                'description' => 'Crea un nuevo tipo de ticket. Crea backup automático antes del cambio.',
                'input_schema' => [
                    'type' => 'object',
                    'properties' => [
                        'slug' => [
                            'type' => 'string',
                            'description' => 'Identificador único del ticket (sin espacios, minúsculas)',
                        ],
                        'nombre' => [
                            'type' => 'string',
                            'description' => 'Nombre visible del ticket',
                        ],
                        'precio' => [
                            'type' => 'number',
                            'description' => 'Precio del ticket',
                        ],
                        'plazas' => [
                            'type' => 'integer',
                            'description' => 'Número de plazas disponibles por día',
                        ],
                        'descripcion' => [
                            'type' => 'string',
                            'description' => 'Descripción del ticket. Opcional.',
                        ],
                        'iva' => [
                            'type' => 'integer',
                            'description' => 'Porcentaje de IVA (ej: 21). Por defecto 21.',
                        ],
                        'tipo' => [
                            'type' => 'string',
                            'enum' => ['normal', 'rango', 'bono_regalo', 'abono_temporada'],
                            'description' => 'Tipo especial del ticket. Por defecto "normal".',
                        ],
                        'estados_validos' => [
                            'type' => 'string',
                            'description' => 'Slugs de estados donde es válido, separados por coma. Ej: "abierto,festivo"',
                        ],
                    ],
                    'required' => ['slug', 'nombre', 'precio', 'plazas'],
                ],
            ],
            [
                'name' => 'editar_tipo_ticket',
                'description' => 'Modifica un tipo de ticket existente. Crea backup automático antes del cambio.',
                'input_schema' => [
                    'type' => 'object',
                    'properties' => [
                        'slug' => [
                            'type' => 'string',
                            'description' => 'Slug del ticket a modificar',
                        ],
                        'nombre' => [
                            'type' => 'string',
                            'description' => 'Nuevo nombre. Opcional.',
                        ],
                        'precio' => [
                            'type' => 'number',
                            'description' => 'Nuevo precio. Opcional.',
                        ],
                        'plazas' => [
                            'type' => 'integer',
                            'description' => 'Nuevas plazas. Opcional.',
                        ],
                        'descripcion' => [
                            'type' => 'string',
                            'description' => 'Nueva descripción. Opcional.',
                        ],
                        'iva' => [
                            'type' => 'integer',
                            'description' => 'Nuevo IVA. Opcional.',
                        ],
                        'estados_validos' => [
                            'type' => 'string',
                            'description' => 'Nuevos estados válidos. Opcional.',
                        ],
                    ],
                    'required' => ['slug'],
                ],
            ],
            [
                'name' => 'eliminar_tipo_ticket',
                'description' => 'Elimina un tipo de ticket. CUIDADO: No se pueden eliminar tickets con reservas existentes. Crea backup automático.',
                'input_schema' => [
                    'type' => 'object',
                    'properties' => [
                        'slug' => [
                            'type' => 'string',
                            'description' => 'Slug del ticket a eliminar',
                        ],
                        'confirmar' => [
                            'type' => 'boolean',
                            'description' => 'Debe ser true para confirmar la eliminación.',
                        ],
                    ],
                    'required' => ['slug', 'confirmar'],
                ],
            ],

            // ==========================================
            // SISTEMA DE BACKUPS
            // ==========================================
            [
                'name' => 'crear_backup',
                'description' => 'Crea un backup manual de la configuración del sistema.',
                'input_schema' => [
                    'type' => 'object',
                    'properties' => [
                        'descripcion' => [
                            'type' => 'string',
                            'description' => 'Descripción del backup. Opcional.',
                        ],
                    ],
                    'required' => [],
                ],
            ],
            [
                'name' => 'listar_backups',
                'description' => 'Lista los backups disponibles para restaurar.',
                'input_schema' => [
                    'type' => 'object',
                    'properties' => [
                        'limite' => [
                            'type' => 'integer',
                            'description' => 'Número máximo de backups a mostrar. Por defecto 20.',
                        ],
                    ],
                    'required' => [],
                ],
            ],
            [
                'name' => 'restaurar_backup',
                'description' => 'Restaura la configuración desde un backup anterior. Crea backup de seguridad antes de restaurar.',
                'input_schema' => [
                    'type' => 'object',
                    'properties' => [
                        'backup_id' => [
                            'type' => 'string',
                            'description' => 'ID del backup a restaurar (obtenido de listar_backups)',
                        ],
                    ],
                    'required' => ['backup_id'],
                ],
            ],
            [
                'name' => 'obtener_historial_cambios',
                'description' => 'Muestra el historial de cambios y operaciones de backup realizadas.',
                'input_schema' => [
                    'type' => 'object',
                    'properties' => [
                        'limite' => [
                            'type' => 'integer',
                            'description' => 'Número de entradas a mostrar. Por defecto 30.',
                        ],
                    ],
                    'required' => [],
                ],
            ],

            // ==========================================
            // HERRAMIENTAS OPTIMIZADAS (SHORTCUTS)
            // ==========================================
            [
                'name' => 'importar_estados_texto',
                'description' => 'Importa estados del calendario desde texto. Formato: "2024-01-15:abierto,2024-01-16:cerrado" o lineas separadas.',
                'input_schema' => [
                    'type' => 'object',
                    'properties' => [
                        'texto' => [
                            'type' => 'string',
                            'description' => 'Texto con formato fecha:estado separado por comas o saltos de linea',
                        ],
                    ],
                    'required' => ['texto'],
                ],
            ],
            [
                'name' => 'crear_ticket_rapido',
                'description' => 'Crea un tipo de ticket de forma rapida con minimos parametros. Genera slug automaticamente.',
                'input_schema' => [
                    'type' => 'object',
                    'properties' => [
                        'nombre' => [
                            'type' => 'string',
                            'description' => 'Nombre del ticket',
                        ],
                        'precio' => [
                            'type' => 'number',
                            'description' => 'Precio del ticket',
                        ],
                        'plazas' => [
                            'type' => 'integer',
                            'description' => 'Numero de plazas. Por defecto 50.',
                        ],
                        'iva' => [
                            'type' => 'integer',
                            'description' => 'Porcentaje de IVA. Por defecto 21.',
                        ],
                    ],
                    'required' => ['nombre', 'precio'],
                ],
            ],
            [
                'name' => 'actualizar_precio_ticket',
                'description' => 'Actualiza rapidamente el precio de un tipo de ticket existente.',
                'input_schema' => [
                    'type' => 'object',
                    'properties' => [
                        'ticket_slug' => [
                            'type' => 'string',
                            'description' => 'Slug del ticket a actualizar',
                        ],
                        'precio' => [
                            'type' => 'number',
                            'description' => 'Nuevo precio',
                        ],
                    ],
                    'required' => ['ticket_slug', 'precio'],
                ],
            ],
            [
                'name' => 'obtener_dashboard_compacto',
                'description' => 'Obtiene un dashboard pre-formateado optimizado para mostrar KPIs rapidamente.',
                'input_schema' => [
                    'type' => 'object',
                    'properties' => [
                        'formato' => [
                            'type' => 'string',
                            'enum' => ['tabla', 'kpis', 'json'],
                            'description' => 'Formato de salida. Por defecto "kpis".',
                        ],
                        'periodo' => [
                            'type' => 'string',
                            'enum' => ['hoy', 'semana', 'mes'],
                            'description' => 'Periodo del dashboard. Por defecto "hoy".',
                        ],
                    ],
                    'required' => [],
                ],
            ],
            [
                'name' => 'obtener_comparativa_rapida',
                'description' => 'Obtiene comparativa pre-calculada entre periodos.',
                'input_schema' => [
                    'type' => 'object',
                    'properties' => [
                        'tipo' => [
                            'type' => 'string',
                            'enum' => ['hoy_vs_ayer', 'semana_vs_anterior', 'mes_vs_anterior'],
                            'description' => 'Tipo de comparativa',
                        ],
                    ],
                    'required' => ['tipo'],
                ],
            ],
        ];
    }

    /**
     * Ejecuta una herramienta con los argumentos dados
     *
     * @param string $tool_name Nombre de la herramienta
     * @param array $arguments Argumentos de la herramienta
     * @return array Resultado de la ejecución
     */
    public function execute_tool($tool_name, $arguments) {
        $method = 'tool_' . $tool_name;

        if (!method_exists($this, $method)) {
            return [
                'success' => false,
                'error' => "Herramienta no encontrada: {$tool_name}",
            ];
        }

        // Verificar permisos de rol
        if ($this->role_access && !$this->role_access->can_use_tool($tool_name)) {
            return [
                'success' => false,
                'error' => $this->role_access->get_restriction_message('tool'),
                'access_denied' => true,
            ];
        }

        // Asegurar que arguments sea array (puede llegar como stdClass desde JSON)
        if (is_object($arguments)) {
            $arguments = (array) $arguments;
        }
        if (!is_array($arguments)) {
            $arguments = [];
        }

        try {
            $result = $this->$method($arguments);

            // Filtrar resultado según permisos
            if ($this->role_access && is_array($result)) {
                $result = $this->role_access->filter_tool_result($result, $tool_name);
            }

            return $result;
        } catch (Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage(),
            ];
        }
    }

    // ==========================================
    // IMPLEMENTACIÓN DE HERRAMIENTAS
    // ==========================================

    /**
     * Obtiene reservas para un día específico
     */
    private function tool_obtener_reservas_dia($args) {
        global $wpdb;
        $tabla = $wpdb->prefix . 'reservas_tickets';

        $fecha = sanitize_text_field($args['fecha'] ?? '');
        $tipo_ticket = sanitize_text_field($args['tipo_ticket'] ?? '');
        $estado = sanitize_text_field($args['estado'] ?? 'todos');

        if (empty($fecha)) {
            return ['success' => false, 'error' => __('Fecha requerida', 'flavor-chat-ia')];
        }

        $where = ["fecha = %s"];
        $params = [$fecha];

        if (!empty($tipo_ticket)) {
            $where[] = "ticket_slug = %s";
            $params[] = $tipo_ticket;
        }

        if ($estado !== 'todos') {
            $where[] = "estado = %s";
            $params[] = $estado;
        }

        $where_clause = implode(' AND ', $where);
        $query = $wpdb->prepare(
            "SELECT id, cliente, ticket_slug, fecha, estado, ticket_code, checkin, blocked
             FROM {$tabla}
             WHERE {$where_clause}
             ORDER BY id DESC",
            ...$params
        );

        $reservas = $wpdb->get_results($query, ARRAY_A);

        // Obtener nombres de tipos de ticket
        $tipos = get_option('calendario_experiencias_ticket_types', []);

        foreach ($reservas as &$reserva) {
            $slug = $reserva['ticket_slug'];
            $reserva['tipo_ticket_nombre'] = $tipos[$slug]['name'] ?? $slug;
        }

        return [
            'success' => true,
            'fecha' => $fecha,
            'total' => count($reservas),
            'reservas' => $reservas,
        ];
    }

    /**
     * Obtiene plazas disponibles
     */
    private function tool_obtener_plazas_disponibles($args) {
        global $wpdb;
        $fecha = sanitize_text_field($args['fecha'] ?? '');
        $tipo_ticket_filtro = sanitize_text_field($args['tipo_ticket'] ?? '');

        if (empty($fecha)) {
            return ['success' => false, 'error' => __('Fecha requerida', 'flavor-chat-ia')];
        }

        $tipos = get_option('calendario_experiencias_ticket_types', []);
        $tabla_tickets = $wpdb->prefix . 'reservas_tickets';
        $tabla_limites = $wpdb->prefix . 'reservas_limites';

        $resultados = [];

        foreach ($tipos as $slug => $tipo) {
            if (!empty($tipo_ticket_filtro) && $slug !== $tipo_ticket_filtro) {
                continue;
            }

            $plazas_base = intval($tipo['plazas'] ?? 0);

            // Verificar límite especial
            $limite_especial = $wpdb->get_var($wpdb->prepare(
                "SELECT plazas FROM {$tabla_limites} WHERE ticket_slug = %s AND fecha = %s",
                $slug, $fecha
            ));

            $plazas_totales = $limite_especial !== null ? intval($limite_especial) : $plazas_base;

            // Contar vendidas
            $vendidas = $wpdb->get_var($wpdb->prepare(
                "SELECT COUNT(*) FROM {$tabla_tickets}
                 WHERE ticket_slug = %s AND fecha = %s AND estado != 'cancelado'",
                $slug, $fecha
            ));

            // Contar en carrito (aproximación desde WooCommerce)
            $en_carrito = 0;
            if (class_exists('WC') && WC()->cart) {
                foreach (WC()->cart->get_cart() as $item) {
                    $item_slug = $item['reserva_data']['ticket_slug'] ?? '';
                    $item_fecha = $item['reserva_data']['fecha'] ?? '';
                    if ($item_slug === $slug && $item_fecha === $fecha) {
                        $en_carrito += intval($item['quantity']);
                    }
                }
            }

            $libres = max(0, $plazas_totales - intval($vendidas) - $en_carrito);

            $resultados[] = [
                'tipo_ticket' => $slug,
                'nombre' => $tipo['name'] ?? $slug,
                'plazas_base' => $plazas_base,
                'plazas_totales' => $plazas_totales,
                'limite_especial' => $limite_especial !== null,
                'vendidas' => intval($vendidas),
                'en_carrito' => $en_carrito,
                'libres' => $libres,
                'porcentaje_ocupacion' => $plazas_totales > 0
                    ? round((intval($vendidas) / $plazas_totales) * 100, 1)
                    : 0,
            ];
        }

        return [
            'success' => true,
            'fecha' => $fecha,
            'disponibilidad' => $resultados,
        ];
    }

    /**
     * Busca reservas
     */
    private function tool_buscar_reservas($args) {
        global $wpdb;
        $tabla = $wpdb->prefix . 'reservas_tickets';

        $termino = sanitize_text_field($args['termino'] ?? '');
        $limite = intval($args['limite'] ?? 20);

        if (empty($termino)) {
            return ['success' => false, 'error' => __('Término de búsqueda requerido', 'flavor-chat-ia')];
        }

        $like = '%' . $wpdb->esc_like($termino) . '%';

        $reservas = $wpdb->get_results($wpdb->prepare(
            "SELECT id, cliente, ticket_slug, fecha, estado, ticket_code, checkin
             FROM {$tabla}
             WHERE cliente LIKE %s OR ticket_code LIKE %s
             ORDER BY fecha DESC, id DESC
             LIMIT %d",
            $like, $like, $limite
        ), ARRAY_A);

        $tipos = get_option('calendario_experiencias_ticket_types', []);
        foreach ($reservas as &$reserva) {
            $slug = $reserva['ticket_slug'];
            $reserva['tipo_ticket_nombre'] = $tipos[$slug]['name'] ?? $slug;
        }

        return [
            'success' => true,
            'termino' => $termino,
            'total' => count($reservas),
            'reservas' => $reservas,
        ];
    }

    /**
     * Obtiene resumen de período
     */
    private function tool_obtener_resumen_periodo($args) {
        global $wpdb;
        $tabla = $wpdb->prefix . 'reservas_tickets';

        $fecha_inicio = sanitize_text_field($args['fecha_inicio'] ?? '');
        $fecha_fin = sanitize_text_field($args['fecha_fin'] ?? '');
        $agrupar = sanitize_text_field($args['agrupar_por'] ?? 'dia');
        $modo = sanitize_text_field($args['modo'] ?? 'normal');

        if (empty($fecha_inicio) || empty($fecha_fin)) {
            return ['success' => false, 'error' => __('Fechas de inicio y fin requeridas', 'flavor-chat-ia')];
        }

        $tipos = get_option('calendario_experiencias_ticket_types', []);

        // Total de reservas
        $total_reservas = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM {$tabla}
             WHERE fecha BETWEEN %s AND %s AND estado != 'cancelado'",
            $fecha_inicio, $fecha_fin
        ));

        // Por tipo de ticket
        $por_tipo = $wpdb->get_results($wpdb->prepare(
            "SELECT ticket_slug, COUNT(*) as cantidad
             FROM {$tabla}
             WHERE fecha BETWEEN %s AND %s AND estado != 'cancelado'
             GROUP BY ticket_slug
             ORDER BY cantidad DESC",
            $fecha_inicio, $fecha_fin
        ), ARRAY_A);

        foreach ($por_tipo as &$item) {
            $slug = $item['ticket_slug'];
            $item['nombre'] = $tipos[$slug]['name'] ?? $slug;
            $item['precio'] = floatval($tipos[$slug]['precio'] ?? 0);
            $item['ingresos_estimados'] = $item['cantidad'] * $item['precio'];
        }

        // Por estado
        $por_estado = $wpdb->get_results($wpdb->prepare(
            "SELECT estado, COUNT(*) as cantidad
             FROM {$tabla}
             WHERE fecha BETWEEN %s AND %s
             GROUP BY estado",
            $fecha_inicio, $fecha_fin
        ), ARRAY_A);

        // Calcular ingresos totales estimados
        $ingresos_totales = array_sum(array_column($por_tipo, 'ingresos_estimados'));

        // Datos base
        $data = [
            'success' => true,
            'periodo' => [
                'inicio' => $fecha_inicio,
                'fin' => $fecha_fin,
            ],
            'total_reservas' => intval($total_reservas),
            'ingresos_estimados' => $ingresos_totales,
            'por_tipo_ticket' => $por_tipo,
            'por_estado' => $por_estado,
        ];

        // Formatear segun modo solicitado
        switch ($modo) {
            case 'kpi':
                // Una linea compacta
                $kpi_line = sprintf(
                    "%d reservas | %.2f€ | %s a %s",
                    $data['total_reservas'],
                    $data['ingresos_estimados'],
                    $fecha_inicio,
                    $fecha_fin
                );
                return [
                    'success' => true,
                    'kpi' => $kpi_line,
                    'data' => $data,
                ];

            case 'tabla':
                // Tabla markdown pre-formateada
                $tabla_md = "| Metrica | Valor |\n|---------|-------|\n";
                $tabla_md .= "| Periodo | {$fecha_inicio} a {$fecha_fin} |\n";
                $tabla_md .= "| Reservas | {$data['total_reservas']} |\n";
                $tabla_md .= "| Ingresos | " . number_format($data['ingresos_estimados'], 2, ',', '.') . "€ |\n";

                if (!empty($por_tipo)) {
                    $tabla_md .= "\n**Por tipo:**\n| Ticket | Cantidad | Ingresos |\n|--------|----------|----------|\n";
                    foreach (array_slice($por_tipo, 0, 5) as $t) {
                        $tabla_md .= "| {$t['nombre']} | {$t['cantidad']} | " . number_format($t['ingresos_estimados'], 2) . "€ |\n";
                    }
                }

                return [
                    'success' => true,
                    'tabla' => $tabla_md,
                    'data' => $data,
                ];

            case 'json':
                // Solo datos, sin texto adicional
                return ['success' => true, 'data' => $data];

            default:
                // Respuesta completa (modo normal)
                return $data;
        }
    }

    /**
     * Obtiene estadísticas de ingresos
     */
    private function tool_obtener_estadisticas_ingresos($args) {
        global $wpdb;
        $tabla_tickets = $wpdb->prefix . 'reservas_tickets';
        $tabla_complementarios = $wpdb->prefix . 'reservas_complementarios_vendidos';

        $fecha_inicio = sanitize_text_field($args['fecha_inicio'] ?? '');
        $fecha_fin = sanitize_text_field($args['fecha_fin'] ?? '');
        $incluir_iva = $args['incluir_iva'] ?? true;

        if (empty($fecha_inicio) || empty($fecha_fin)) {
            return ['success' => false, 'error' => __('Fechas requeridas', 'flavor-chat-ia')];
        }

        $tipos = get_option('calendario_experiencias_ticket_types', []);

        // Ingresos por tickets
        $tickets_vendidos = $wpdb->get_results($wpdb->prepare(
            "SELECT ticket_slug, COUNT(*) as cantidad
             FROM {$tabla_tickets}
             WHERE fecha BETWEEN %s AND %s AND estado != 'cancelado'
             GROUP BY ticket_slug",
            $fecha_inicio, $fecha_fin
        ), ARRAY_A);

        $ingresos_tickets = 0;
        $iva_tickets = 0;
        $desglose_tickets = [];

        foreach ($tickets_vendidos as $item) {
            $slug = $item['ticket_slug'];
            $tipo = $tipos[$slug] ?? [];
            $precio = floatval($tipo['precio'] ?? 0);
            $iva_porcentaje = floatval($tipo['iva'] ?? 21);
            $cantidad = intval($item['cantidad']);

            $total = $precio * $cantidad;
            $iva = $total * ($iva_porcentaje / (100 + $iva_porcentaje));

            $ingresos_tickets += $total;
            $iva_tickets += $iva;

            $desglose_tickets[] = [
                'tipo' => $slug,
                'nombre' => $tipo['name'] ?? $slug,
                'cantidad' => $cantidad,
                'precio_unitario' => $precio,
                'total' => $total,
                'iva' => round($iva, 2),
            ];
        }

        // Ingresos por complementarios
        $ingresos_complementarios = $wpdb->get_var($wpdb->prepare(
            "SELECT COALESCE(SUM(precio_total), 0)
             FROM {$tabla_complementarios}
             WHERE created_at BETWEEN %s AND %s",
            $fecha_inicio . ' 00:00:00', $fecha_fin . ' 23:59:59'
        ));

        return [
            'success' => true,
            'periodo' => ['inicio' => $fecha_inicio, 'fin' => $fecha_fin],
            'ingresos_tickets' => round($ingresos_tickets, 2),
            'ingresos_complementarios' => round(floatval($ingresos_complementarios), 2),
            'ingresos_totales' => round($ingresos_tickets + floatval($ingresos_complementarios), 2),
            'iva_tickets' => $incluir_iva ? round($iva_tickets, 2) : null,
            'desglose_tickets' => $desglose_tickets,
        ];
    }

    /**
     * Obtiene tickets más vendidos
     */
    private function tool_obtener_tickets_mas_vendidos($args) {
        global $wpdb;
        $tabla = $wpdb->prefix . 'reservas_tickets';

        $fecha_inicio = $args['fecha_inicio'] ?? date('Y-m-d', strtotime('-30 days'));
        $fecha_fin = $args['fecha_fin'] ?? date('Y-m-d');
        $limite = intval($args['limite'] ?? 10);

        $tipos = get_option('calendario_experiencias_ticket_types', []);

        $resultados = $wpdb->get_results($wpdb->prepare(
            "SELECT ticket_slug, COUNT(*) as cantidad
             FROM {$tabla}
             WHERE fecha BETWEEN %s AND %s AND estado != 'cancelado'
             GROUP BY ticket_slug
             ORDER BY cantidad DESC
             LIMIT %d",
            $fecha_inicio, $fecha_fin, $limite
        ), ARRAY_A);

        foreach ($resultados as &$item) {
            $slug = $item['ticket_slug'];
            $tipo = $tipos[$slug] ?? [];
            $item['nombre'] = $tipo['name'] ?? $slug;
            $item['precio'] = floatval($tipo['precio'] ?? 0);
            $item['ingresos'] = $item['cantidad'] * $item['precio'];
        }

        return [
            'success' => true,
            'periodo' => ['inicio' => $fecha_inicio, 'fin' => $fecha_fin],
            'ranking' => $resultados,
        ];
    }

    /**
     * Compara dos períodos
     */
    private function tool_obtener_comparativa_periodos($args) {
        // Obtener resumen de ambos períodos
        $periodo1 = $this->tool_obtener_resumen_periodo([
            'fecha_inicio' => $args['periodo1_inicio'],
            'fecha_fin' => $args['periodo1_fin'],
        ]);

        $periodo2 = $this->tool_obtener_resumen_periodo([
            'fecha_inicio' => $args['periodo2_inicio'],
            'fecha_fin' => $args['periodo2_fin'],
        ]);

        if (!$periodo1['success'] || !$periodo2['success']) {
            return ['success' => false, 'error' => __('Error al obtener datos de períodos', 'flavor-chat-ia')];
        }

        $reservas_diff = $periodo2['total_reservas'] - $periodo1['total_reservas'];
        $ingresos_diff = $periodo2['ingresos_estimados'] - $periodo1['ingresos_estimados'];

        return [
            'success' => true,
            'periodo1' => [
                'fechas' => $periodo1['periodo'],
                'reservas' => $periodo1['total_reservas'],
                'ingresos' => $periodo1['ingresos_estimados'],
            ],
            'periodo2' => [
                'fechas' => $periodo2['periodo'],
                'reservas' => $periodo2['total_reservas'],
                'ingresos' => $periodo2['ingresos_estimados'],
            ],
            'diferencias' => [
                'reservas' => $reservas_diff,
                'reservas_porcentaje' => $periodo1['total_reservas'] > 0
                    ? round(($reservas_diff / $periodo1['total_reservas']) * 100, 1)
                    : 0,
                'ingresos' => $ingresos_diff,
                'ingresos_porcentaje' => $periodo1['ingresos_estimados'] > 0
                    ? round(($ingresos_diff / $periodo1['ingresos_estimados']) * 100, 1)
                    : 0,
            ],
        ];
    }

    /**
     * Obtiene estado del calendario
     */
    private function tool_obtener_estado_calendario($args) {
        global $wpdb;
        $fecha_inicio = sanitize_text_field($args['fecha_inicio'] ?? '');
        $fecha_fin = sanitize_text_field($args['fecha_fin'] ?? '');

        if (empty($fecha_inicio) || empty($fecha_fin)) {
            return ['success' => false, 'error' => __('Fechas requeridas', 'flavor-chat-ia')];
        }

        $tipos = get_option('calendario_experiencias_ticket_types', []);
        $tabla_tickets = $wpdb->prefix . 'reservas_tickets';
        $tabla_limites = $wpdb->prefix . 'reservas_limites';

        $dias = [];
        $fecha_actual = new DateTime($fecha_inicio);
        $fecha_final = new DateTime($fecha_fin);

        while ($fecha_actual <= $fecha_final) {
            $fecha_str = $fecha_actual->format('Y-m-d');

            $reservas_dia = $wpdb->get_var($wpdb->prepare(
                "SELECT COUNT(*) FROM {$tabla_tickets}
                 WHERE fecha = %s AND estado != 'cancelado'",
                $fecha_str
            ));

            $limites_especiales = $wpdb->get_results($wpdb->prepare(
                "SELECT ticket_slug, plazas FROM {$tabla_limites} WHERE fecha = %s",
                $fecha_str
            ), ARRAY_A);

            $dias[] = [
                'fecha' => $fecha_str,
                'dia_semana' => $fecha_actual->format('l'),
                'reservas' => intval($reservas_dia),
                'limites_especiales' => $limites_especiales,
            ];

            $fecha_actual->modify('+1 day');
        }

        return [
            'success' => true,
            'periodo' => ['inicio' => $fecha_inicio, 'fin' => $fecha_fin],
            'dias' => $dias,
        ];
    }

    /**
     * Modifica límite de plazas
     */
    private function tool_modificar_limite_plazas($args) {
        global $wpdb;
        $tabla = $wpdb->prefix . 'reservas_limites';

        $fecha = sanitize_text_field($args['fecha'] ?? '');
        $tipo_ticket = sanitize_text_field($args['tipo_ticket'] ?? '');
        $nuevo_limite = intval($args['nuevo_limite'] ?? 0);

        if (empty($fecha) || empty($tipo_ticket)) {
            return ['success' => false, 'error' => __('Fecha y tipo de ticket requeridos', 'flavor-chat-ia')];
        }

        // Verificar que el tipo de ticket existe
        $tipos = get_option('calendario_experiencias_ticket_types', []);
        if (!isset($tipos[$tipo_ticket])) {
            return ['success' => false, 'error' => "Tipo de ticket '{$tipo_ticket}' no encontrado"];
        }

        // Insertar o actualizar
        $existe = $wpdb->get_var($wpdb->prepare(
            "SELECT id FROM {$tabla} WHERE fecha = %s AND ticket_slug = %s",
            $fecha, $tipo_ticket
        ));

        if ($existe) {
            $wpdb->update(
                $tabla,
                ['plazas' => $nuevo_limite],
                ['fecha' => $fecha, 'ticket_slug' => $tipo_ticket],
                ['%d'],
                ['%s', '%s']
            );
        } else {
            $wpdb->insert(
                $tabla,
                [
                    'fecha' => $fecha,
                    'ticket_slug' => $tipo_ticket,
                    'plazas' => $nuevo_limite,
                ],
                ['%s', '%s', '%d']
            );
        }

        return [
            'success' => true,
            'mensaje' => "Límite actualizado a {$nuevo_limite} plazas para '{$tipos[$tipo_ticket]['name']}' el {$fecha}",
            'fecha' => $fecha,
            'tipo_ticket' => $tipo_ticket,
            'nuevo_limite' => $nuevo_limite,
        ];
    }

    /**
     * Bloquea un ticket
     */
    private function tool_bloquear_ticket($args) {
        global $wpdb;
        $tabla = $wpdb->prefix . 'reservas_tickets';

        $codigo = sanitize_text_field($args['codigo_ticket'] ?? '');
        $razon = sanitize_text_field($args['razon'] ?? '');

        if (empty($codigo)) {
            return ['success' => false, 'error' => __('Código de ticket requerido', 'flavor-chat-ia')];
        }

        $ticket = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$tabla} WHERE ticket_code = %s",
            $codigo
        ), ARRAY_A);

        if (!$ticket) {
            return ['success' => false, 'error' => "Ticket con código '{$codigo}' no encontrado"];
        }

        if ($ticket['blocked']) {
            return ['success' => false, 'error' => __('El ticket ya está bloqueado', 'flavor-chat-ia')];
        }

        $wpdb->update(
            $tabla,
            [
                'blocked' => 1,
                'blocked_at' => current_time('mysql'),
                'blocked_by' => get_current_user_id(),
            ],
            ['ticket_code' => $codigo],
            ['%d', '%s', '%d'],
            ['%s']
        );

        return [
            'success' => true,
            'mensaje' => "Ticket {$codigo} bloqueado correctamente",
            'ticket' => [
                'codigo' => $codigo,
                'cliente' => $ticket['cliente'],
                'fecha' => $ticket['fecha'],
            ],
        ];
    }

    /**
     * Desbloquea un ticket
     */
    private function tool_desbloquear_ticket($args) {
        global $wpdb;
        $tabla = $wpdb->prefix . 'reservas_tickets';

        $codigo = sanitize_text_field($args['codigo_ticket'] ?? '');

        if (empty($codigo)) {
            return ['success' => false, 'error' => __('Código de ticket requerido', 'flavor-chat-ia')];
        }

        $ticket = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$tabla} WHERE ticket_code = %s",
            $codigo
        ), ARRAY_A);

        if (!$ticket) {
            return ['success' => false, 'error' => "Ticket con código '{$codigo}' no encontrado"];
        }

        if (!$ticket['blocked']) {
            return ['success' => false, 'error' => __('El ticket no está bloqueado', 'flavor-chat-ia')];
        }

        $wpdb->update(
            $tabla,
            ['blocked' => 0, 'blocked_at' => null, 'blocked_by' => null],
            ['ticket_code' => $codigo],
            ['%d', '%s', '%s'],
            ['%s']
        );

        return [
            'success' => true,
            'mensaje' => "Ticket {$codigo} desbloqueado correctamente",
        ];
    }

    /**
     * Obtiene tipos de ticket
     */
    private function tool_obtener_tipos_ticket($args) {
        $tipos = get_option('calendario_experiencias_ticket_types', []);
        $incluir_inactivos = $args['incluir_inactivos'] ?? false;

        $resultado = [];
        foreach ($tipos as $slug => $tipo) {
            $resultado[] = [
                'slug' => $slug,
                'nombre' => $tipo['name'] ?? $slug,
                'descripcion' => $tipo['descripcion'] ?? '',
                'precio' => floatval($tipo['precio'] ?? 0),
                'iva' => intval($tipo['iva'] ?? 21),
                'plazas' => intval($tipo['plazas'] ?? 0),
                'duracion' => $tipo['duracion'] ?? 'dia',
                'tipo_especial' => $tipo['tipo'] ?? 'normal',
                'requiere' => $tipo['requires'] ?? null,
                'estados_validos' => $tipo['estados_validos'] ?? '',
                'campos_personalizados' => count($tipo['campos_personalizados'] ?? []),
            ];
        }

        return [
            'success' => true,
            'total' => count($resultado),
            'tipos_ticket' => $resultado,
        ];
    }

    /**
     * Obtiene complementarios
     */
    private function tool_obtener_complementarios($args) {
        global $wpdb;
        $tabla = $wpdb->prefix . 'reservas_complementarios';
        $tabla_rel = $wpdb->prefix . 'reservas_ticket_complementarios';

        $tipo_ticket = sanitize_text_field($args['tipo_ticket'] ?? '');

        if (!empty($tipo_ticket)) {
            $complementarios = $wpdb->get_results($wpdb->prepare(
                "SELECT c.*, tc.obligatorio
                 FROM {$tabla} c
                 JOIN {$tabla_rel} tc ON c.id = tc.complementario_id
                 WHERE tc.ticket_slug = %s AND c.activo = 1
                 ORDER BY c.orden",
                $tipo_ticket
            ), ARRAY_A);
        } else {
            $complementarios = $wpdb->get_results(
                "SELECT * FROM {$tabla} WHERE activo = 1 ORDER BY orden",
                ARRAY_A
            );
        }

        return [
            'success' => true,
            'filtrado_por' => $tipo_ticket ?: 'todos',
            'total' => count($complementarios),
            'complementarios' => $complementarios,
        ];
    }

    /**
     * Obtiene configuración del sistema
     */
    private function tool_obtener_configuracion_sistema($args) {
        $opciones_calendario = get_option('calendario_experiencias_options', []);
        $opciones_reservas = get_option('reservas_addon_settings', []);

        // Estados del calendario
        $estados = [];
        if (isset($opciones_calendario['estados']) && is_array($opciones_calendario['estados'])) {
            foreach ($opciones_calendario['estados'] as $estado) {
                $estados[] = [
                    'slug' => $estado['slug'] ?? '',
                    'nombre' => $estado['nombre'] ?? '',
                    'color' => $estado['color'] ?? '#ccc',
                    'reservable' => !empty($estado['reservable']),
                ];
            }
        }

        return [
            'success' => true,
            'modo_pruebas' => !empty($opciones_reservas['modo_pruebas']),
            'mensaje_modo_pruebas' => $opciones_reservas['mensaje_modo_pruebas'] ?? '',
            'estados_calendario' => $estados,
            'total_tipos_ticket' => count(get_option('calendario_experiencias_ticket_types', [])),
        ];
    }

    /**
     * Obtiene shortcodes disponibles
     */
    private function tool_obtener_shortcodes_disponibles($args) {
        return [
            'success' => true,
            'shortcodes' => [
                [
                    'nombre' => 'reserva_form',
                    'descripcion' => 'Muestra el formulario de reserva con selector de tickets',
                    'ejemplo' => '[reserva_form fecha="2024-03-15" estado="disponible"]',
                    'parametros' => [
                        'fecha' => 'Fecha inicial (YYYY-MM-DD)',
                        'estado' => 'Filtrar por estado del calendario',
                        'filter_type' => 'Filtrar por tipo de ticket',
                    ],
                ],
                [
                    'nombre' => 'reserva_producto',
                    'descripcion' => 'Muestra información de un producto/ticket específico',
                    'ejemplo' => '[reserva_producto ticket="entrada-general" fecha="2024-03-15" mostrar_boton="si"]',
                    'parametros' => [
                        'ticket' => 'Slug del tipo de ticket (requerido)',
                        'fecha' => 'Fecha de la reserva (requerido)',
                        'mostrar_boton' => 'Mostrar botón de compra (si/no)',
                    ],
                ],
                [
                    'nombre' => 'limpiar_carrito_wc',
                    'descripcion' => 'Muestra un botón para limpiar el carrito de WooCommerce',
                    'ejemplo' => '[limpiar_carrito_wc]',
                    'parametros' => [],
                ],
                [
                    'nombre' => 'reservas_carrito',
                    'descripcion' => 'Muestra el widget del carrito flotante',
                    'ejemplo' => '[reservas_carrito]',
                    'parametros' => [],
                ],
            ],
        ];
    }

    /**
     * Genera un shortcode personalizado
     */
    private function tool_generar_shortcode($args) {
        $tipo = sanitize_text_field($args['tipo'] ?? '');
        $parametros = $args['parametros'] ?? [];

        $shortcodes = [
            'reserva_form' => ['fecha', 'estado', 'filter_type'],
            'reserva_producto' => ['ticket', 'fecha', 'mostrar_boton'],
            'limpiar_carrito' => [],
            'reservas_carrito' => [],
        ];

        if (!isset($shortcodes[$tipo])) {
            return ['success' => false, 'error' => "Tipo de shortcode '{$tipo}' no válido"];
        }

        $atributos = [];
        foreach ($shortcodes[$tipo] as $param) {
            if (!empty($parametros[$param])) {
                $atributos[] = $param . '="' . esc_attr($parametros[$param]) . '"';
            }
        }

        $shortcode = '[' . $tipo . (count($atributos) > 0 ? ' ' . implode(' ', $atributos) : '') . ']';

        return [
            'success' => true,
            'shortcode' => $shortcode,
            'tipo' => $tipo,
            'parametros_usados' => $parametros,
        ];
    }

    /**
     * Explica una sección del plugin
     */
    private function tool_explicar_seccion($args) {
        $seccion = sanitize_text_field($args['seccion'] ?? '');

        $explicaciones = [
            'dashboard' => [
                'titulo' => 'Dashboard de Reservas',
                'descripcion' => 'Panel principal que muestra métricas y estadísticas de reservas.',
                'funcionalidades' => [
                    'Ver reservas del día actual y próximas',
                    'Gráficos de ingresos y reservas por período',
                    'Estadísticas por tipo de ticket',
                    'Filtros por rango de fechas',
                    'Indicadores de ocupación',
                ],
                'ubicacion' => 'Calendario Experiencias > Dashboard Tickets',
            ],
            'tipos_ticket' => [
                'titulo' => 'Gestión de Tipos de Ticket',
                'descripcion' => 'Configuración de los diferentes tipos de entrada o reserva disponibles.',
                'funcionalidades' => [
                    'Crear, editar y eliminar tipos de ticket',
                    'Configurar precio, IVA y plazas disponibles',
                    'Definir si es ticket de día o de rango (varias noches)',
                    'Añadir campos personalizados (nombre, email, etc.)',
                    'Establecer dependencias entre tickets',
                    'Configurar tipos especiales (bonos, abonos)',
                    'Reordenar con drag & drop',
                ],
                'ubicacion' => 'Calendario Experiencias > Tipos de Ticket',
            ],
            'limites_plazas' => [
                'titulo' => 'Límites de Plazas',
                'descripcion' => 'Permite establecer límites especiales de plazas para fechas específicas.',
                'funcionalidades' => [
                    'Sobreescribir plazas base para días concretos',
                    'Útil para eventos especiales o temporadas',
                    'Gestión por tipo de ticket',
                ],
                'ubicacion' => 'Calendario Experiencias > Límites de Plazas',
            ],
            'bloqueos' => [
                'titulo' => 'Bloqueos de Tickets',
                'descripcion' => 'Sistema para bloquear tickets específicos impidiendo su uso.',
                'funcionalidades' => [
                    'Bloquear tickets por código',
                    'Registrar razón del bloqueo',
                    'Auditoría de quién y cuándo bloqueó',
                    'Desbloqueo de tickets',
                ],
                'ubicacion' => 'Calendario Experiencias > Bloqueos',
            ],
            'bonos' => [
                'titulo' => 'Bonos y Abonos',
                'descripcion' => 'Sistema de tickets especiales como vales regalo o abonos de temporada.',
                'funcionalidades' => [
                    'Bonos regalo canjeables',
                    'Abonos de temporada con validez',
                    'Validación por estado del calendario',
                ],
                'ubicacion' => 'Calendario Experiencias > Bonos',
            ],
            'complementarios' => [
                'titulo' => 'Productos Complementarios',
                'descripcion' => 'Productos adicionales que se pueden añadir a las reservas.',
                'funcionalidades' => [
                    'Crear productos extra (comidas, equipamiento, etc.)',
                    'Asignar a tipos de ticket específicos',
                    'Configurar si son obligatorios u opcionales',
                    'Multiplicar precio por número de noches',
                    'Tipos: checkbox, número o selector',
                ],
                'ubicacion' => 'Calendario Experiencias > Complementarios',
            ],
            'shortcodes' => [
                'titulo' => 'Generador de Shortcodes',
                'descripcion' => 'Herramienta para crear shortcodes personalizados.',
                'funcionalidades' => [
                    '[reserva_form] - Formulario de reserva',
                    '[reserva_producto] - Info de producto específico',
                    '[limpiar_carrito_wc] - Botón limpiar carrito',
                    '[reservas_carrito] - Widget del carrito',
                ],
                'ubicacion' => 'Calendario Experiencias > Shortcodes',
            ],
            'qr_scanner' => [
                'titulo' => 'Sistema QR',
                'descripcion' => 'Sistema de escaneo de códigos QR para check-in de reservas.',
                'funcionalidades' => [
                    'Escaneo desde USB/Arduino',
                    'Scanner móvil desde navegador',
                    'Check-in automático',
                    'Validación de tickets',
                    'Historial de escaneos',
                ],
                'ubicacion' => 'Calendario Experiencias > QR Scanner',
            ],
            'contabilidad' => [
                'titulo' => 'Contabilidad y Métricas',
                'descripcion' => 'Seguimiento de ingresos y estadísticas financieras.',
                'funcionalidades' => [
                    'Ingresos por período',
                    'Desglose por tipo de ticket',
                    'Ingresos por complementarios',
                    'Cálculo de IVA',
                    'Comparativas entre períodos',
                ],
                'ubicacion' => 'Dashboard de Tickets > Pestaña Ingresos',
            ],
            'campos_personalizados' => [
                'titulo' => 'Campos Personalizados',
                'descripcion' => 'Campos adicionales que se solicitan al cliente durante la reserva.',
                'funcionalidades' => [
                    'Tipos: texto, email, selector',
                    'Configurar por tipo de ticket',
                    'Marcar como obligatorios',
                    'Se guardan en el pedido de WooCommerce',
                ],
                'ubicacion' => 'Tipos de Ticket > Editar > Campos Personalizados',
            ],
            'dependencias_tickets' => [
                'titulo' => 'Dependencias entre Tickets',
                'descripcion' => 'Sistema para requerir la compra de un ticket antes de poder añadir otro.',
                'funcionalidades' => [
                    'Un ticket puede requerir otro en el carrito',
                    'Validación en carrito y checkout',
                    'Útil para extras que necesitan ticket principal',
                ],
                'ubicacion' => 'Tipos de Ticket > Editar > Campo "Requiere"',
            ],
        ];

        if (!isset($explicaciones[$seccion])) {
            return [
                'success' => false,
                'error' => "Sección '{$seccion}' no encontrada",
                'secciones_disponibles' => array_keys($explicaciones),
            ];
        }

        return [
            'success' => true,
            'seccion' => $seccion,
            'info' => $explicaciones[$seccion],
        ];
    }

    /**
     * Obtiene próximas reservas
     */
    private function tool_obtener_proximas_reservas($args) {
        global $wpdb;
        $tabla = $wpdb->prefix . 'reservas_tickets';

        $dias = intval($args['dias'] ?? 7);
        $limite = intval($args['limite'] ?? 20);

        $fecha_hoy = date('Y-m-d');
        $fecha_fin = date('Y-m-d', strtotime("+{$dias} days"));

        $tipos = get_option('calendario_experiencias_ticket_types', []);

        $reservas = $wpdb->get_results($wpdb->prepare(
            "SELECT id, cliente, ticket_slug, fecha, estado, ticket_code
             FROM {$tabla}
             WHERE fecha BETWEEN %s AND %s AND estado != 'cancelado'
             ORDER BY fecha ASC, id ASC
             LIMIT %d",
            $fecha_hoy, $fecha_fin, $limite
        ), ARRAY_A);

        foreach ($reservas as &$reserva) {
            $slug = $reserva['ticket_slug'];
            $reserva['tipo_ticket_nombre'] = $tipos[$slug]['name'] ?? $slug;
        }

        return [
            'success' => true,
            'periodo' => ['desde' => $fecha_hoy, 'hasta' => $fecha_fin],
            'total' => count($reservas),
            'reservas' => $reservas,
        ];
    }

    /**
     * Obtiene resumen de hoy
     */
    private function tool_obtener_resumen_hoy($args) {
        $hoy = date('Y-m-d');

        // Obtener reservas de hoy
        $reservas = $this->tool_obtener_reservas_dia(['fecha' => $hoy]);

        // Obtener disponibilidad
        $disponibilidad = $this->tool_obtener_plazas_disponibles(['fecha' => $hoy]);

        // Contar check-ins
        global $wpdb;
        $tabla = $wpdb->prefix . 'reservas_tickets';
        $checkins_hoy = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM {$tabla}
             WHERE fecha = %s AND checkin IS NOT NULL",
            $hoy
        ));

        // Obtener tipos de ticket para calcular ingresos
        $tipos = get_option('calendario_experiencias_ticket_types', []);

        // Por tipo de ticket con ingresos
        $por_tipo = $wpdb->get_results($wpdb->prepare(
            "SELECT ticket_slug, COUNT(*) as cantidad
             FROM {$tabla}
             WHERE fecha = %s AND estado != 'cancelado'
             GROUP BY ticket_slug
             ORDER BY cantidad DESC",
            $hoy
        ), ARRAY_A);

        $ingresos_totales = 0;
        foreach ($por_tipo as &$item) {
            $slug = $item['ticket_slug'];
            $item['nombre'] = $tipos[$slug]['name'] ?? $slug;
            $item['precio'] = floatval($tipos[$slug]['precio'] ?? 0);
            $item['ingresos_estimados'] = $item['cantidad'] * $item['precio'];
            $ingresos_totales += $item['ingresos_estimados'];
        }

        $total_reservas = $reservas['total'] ?? 0;
        $check_ins = intval($checkins_hoy);

        // Estructura consistente con obtener_resumen_periodo
        return [
            'success' => true,
            'periodo' => [
                'inicio' => $hoy,
                'fin' => $hoy,
            ],
            'fecha' => $hoy,
            'total_reservas' => $total_reservas,
            'ingresos_estimados' => $ingresos_totales,
            'por_tipo_ticket' => $por_tipo,
            'check_ins_realizados' => $check_ins,
            'pendientes_checkin' => $total_reservas - $check_ins,
            'disponibilidad' => $disponibilidad['disponibilidad'] ?? [],
            'ultimas_reservas' => array_slice($reservas['reservas'] ?? [], 0, 5),
        ];
    }

    /**
     * Obtiene alertas del sistema
     */
    private function tool_obtener_alertas_sistema($args) {
        global $wpdb;
        $tabla_tickets = $wpdb->prefix . 'reservas_tickets';

        $alertas = [];
        $hoy = date('Y-m-d');
        $proximos_7_dias = date('Y-m-d', strtotime('+7 days'));

        $tipos = get_option('calendario_experiencias_ticket_types', []);

        // Días casi llenos (>80% ocupación)
        foreach ($tipos as $slug => $tipo) {
            $plazas = intval($tipo['plazas'] ?? 0);
            if ($plazas === 0) continue;

            $ocupacion = $wpdb->get_results($wpdb->prepare(
                "SELECT fecha, COUNT(*) as cantidad
                 FROM {$tabla_tickets}
                 WHERE ticket_slug = %s AND fecha BETWEEN %s AND %s AND estado != 'cancelado'
                 GROUP BY fecha
                 HAVING cantidad >= %d",
                $slug, $hoy, $proximos_7_dias, floor($plazas * 0.8)
            ), ARRAY_A);

            foreach ($ocupacion as $dia) {
                $porcentaje = round(($dia['cantidad'] / $plazas) * 100);
                $alertas[] = [
                    'tipo' => 'ocupacion_alta',
                    'nivel' => $porcentaje >= 100 ? 'critico' : 'advertencia',
                    'mensaje' => "{$tipo['name']} al {$porcentaje}% el {$dia['fecha']}",
                    'fecha' => $dia['fecha'],
                ];
            }
        }

        // Tickets bloqueados
        $bloqueados = $wpdb->get_var(
            "SELECT COUNT(*) FROM {$tabla_tickets} WHERE blocked = 1"
        );
        if ($bloqueados > 0) {
            $alertas[] = [
                'tipo' => 'tickets_bloqueados',
                'nivel' => 'info',
                'mensaje' => "{$bloqueados} ticket(s) bloqueado(s)",
            ];
        }

        return [
            'success' => true,
            'total_alertas' => count($alertas),
            'alertas' => $alertas,
        ];
    }

    // ==========================================
    // DATOS DE CLIENTES
    // ==========================================

    /**
     * Obtiene datos de clientes con reservas
     */
    private function tool_obtener_datos_clientes($args) {
        global $wpdb;
        $tabla = $wpdb->prefix . 'reservas_tickets';

        $fecha = sanitize_text_field($args['fecha'] ?? '');
        $fecha_inicio = sanitize_text_field($args['fecha_inicio'] ?? '');
        $fecha_fin = sanitize_text_field($args['fecha_fin'] ?? '');
        $buscar = sanitize_text_field($args['buscar'] ?? '');
        $limite = intval($args['limite'] ?? 50);

        $where = ["1=1"];
        $params = [];

        // Filtro por fecha específica
        if (!empty($fecha)) {
            $where[] = "fecha = %s";
            $params[] = $fecha;
        }

        // Filtro por rango de fechas
        if (!empty($fecha_inicio) && !empty($fecha_fin)) {
            $where[] = "fecha BETWEEN %s AND %s";
            $params[] = $fecha_inicio;
            $params[] = $fecha_fin;
        }

        // Búsqueda por término
        if (!empty($buscar)) {
            $where[] = "(cliente LIKE %s OR cliente LIKE %s)";
            $params[] = '%' . $wpdb->esc_like($buscar) . '%';
            $params[] = '%' . $wpdb->esc_like($buscar) . '%';
        }

        $where_clause = implode(' AND ', $where);

        // Obtener clientes únicos con sus datos
        $query = "SELECT cliente,
                         COUNT(*) as total_reservas,
                         MIN(fecha) as primera_reserva,
                         MAX(fecha) as ultima_reserva
                  FROM {$tabla}
                  WHERE {$where_clause}
                  GROUP BY cliente
                  ORDER BY ultima_reserva DESC
                  LIMIT %d";

        $params[] = $limite;

        $clientes_raw = $wpdb->get_results(
            $wpdb->prepare($query, ...$params),
            ARRAY_A
        );

        // Parsear datos de cliente (formato JSON en campo 'cliente')
        $clientes = [];
        foreach ($clientes_raw as $row) {
            $cliente_data = json_decode($row['cliente'], true);
            if (!$cliente_data) {
                // Intentar como string simple
                $cliente_data = ['nombre' => $row['cliente']];
            }

            $clientes[] = [
                'nombre' => $cliente_data['nombre'] ?? $cliente_data['name'] ?? 'Sin nombre',
                'email' => $cliente_data['email'] ?? '',
                'telefono' => $cliente_data['telefono'] ?? $cliente_data['phone'] ?? '',
                'total_reservas' => intval($row['total_reservas']),
                'primera_reserva' => $row['primera_reserva'],
                'ultima_reserva' => $row['ultima_reserva'],
            ];
        }

        return [
            'success' => true,
            'total' => count($clientes),
            'clientes' => $clientes,
        ];
    }

    /**
     * Obtiene detalle de un cliente específico
     */
    private function tool_obtener_detalle_cliente($args) {
        global $wpdb;
        $tabla = $wpdb->prefix . 'reservas_tickets';

        $email = sanitize_email($args['email'] ?? '');
        $incluir_historial = $args['incluir_historial'] ?? true;

        if (empty($email)) {
            return ['success' => false, 'error' => __('Email requerido', 'flavor-chat-ia')];
        }

        // Buscar por email en el campo cliente JSON
        $reservas = $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM {$tabla}
             WHERE cliente LIKE %s
             ORDER BY fecha DESC",
            '%"email":"' . $wpdb->esc_like($email) . '"%'
        ), ARRAY_A);

        if (empty($reservas)) {
            return [
                'success' => false,
                'error' => __('No se encontró cliente con ese email', 'flavor-chat-ia'),
            ];
        }

        // Extraer datos del cliente de la primera reserva
        $cliente_data = json_decode($reservas[0]['cliente'], true) ?? [];

        // Calcular estadísticas
        $total_reservas = count($reservas);
        $total_personas = 0;
        $reservas_usadas = 0;
        $reservas_canceladas = 0;

        $tipos = get_option('calendario_experiencias_ticket_types', []);

        foreach ($reservas as $r) {
            if ($r['estado'] === 'usado') $reservas_usadas++;
            if ($r['estado'] === 'cancelado') $reservas_canceladas++;
        }

        $detalle = [
            'success' => true,
            'cliente' => [
                'nombre' => $cliente_data['nombre'] ?? $cliente_data['name'] ?? 'Sin nombre',
                'email' => $cliente_data['email'] ?? $email,
                'telefono' => $cliente_data['telefono'] ?? $cliente_data['phone'] ?? '',
            ],
            'estadisticas' => [
                'total_reservas' => $total_reservas,
                'reservas_usadas' => $reservas_usadas,
                'reservas_canceladas' => $reservas_canceladas,
                'primera_reserva' => end($reservas)['fecha'],
                'ultima_reserva' => $reservas[0]['fecha'],
            ],
        ];

        if ($incluir_historial) {
            $historial = [];
            foreach (array_slice($reservas, 0, 20) as $r) {
                $slug = $r['ticket_slug'];
                $historial[] = [
                    'fecha' => $r['fecha'],
                    'tipo_ticket' => $tipos[$slug]['name'] ?? $slug,
                    'estado' => $r['estado'],
                    'codigo' => $r['ticket_code'],
                ];
            }
            $detalle['historial'] = $historial;
        }

        return $detalle;
    }

    // ==========================================
    // EXPORTACIÓN DE DATOS
    // ==========================================

    /**
     * Genera archivo CSV con los datos solicitados
     */
    private function tool_exportar_datos_csv($args) {
        $tipo_datos = sanitize_text_field($args['tipo_datos'] ?? '');
        $fecha_inicio = sanitize_text_field($args['fecha_inicio'] ?? '');
        $fecha_fin = sanitize_text_field($args['fecha_fin'] ?? '');
        $incluir_cabeceras = $args['incluir_cabeceras'] ?? true;

        if (empty($tipo_datos) || empty($fecha_inicio) || empty($fecha_fin)) {
            return ['success' => false, 'error' => __('Faltan parámetros requeridos', 'flavor-chat-ia')];
        }

        // Obtener datos según tipo
        $datos = [];
        $cabeceras = [];

        switch ($tipo_datos) {
            case 'reservas':
                $result = $this->get_reservas_para_csv($fecha_inicio, $fecha_fin);
                $datos = $result['datos'];
                $cabeceras = ['Fecha', 'Código', 'Tipo Ticket', 'Cliente', 'Email', 'Teléfono', 'Estado', 'Check-in'];
                break;

            case 'clientes':
                $result = $this->get_clientes_para_csv($fecha_inicio, $fecha_fin);
                $datos = $result['datos'];
                $cabeceras = ['Nombre', 'Email', 'Teléfono', 'Total Reservas', 'Primera Reserva', 'Última Reserva'];
                break;

            case 'ingresos':
                $result = $this->get_ingresos_para_csv($fecha_inicio, $fecha_fin);
                $datos = $result['datos'];
                $cabeceras = ['Fecha', 'Tipo Ticket', 'Cantidad', 'Precio Unitario', 'Total'];
                break;

            case 'tickets_vendidos':
                $result = $this->get_tickets_vendidos_para_csv($fecha_inicio, $fecha_fin);
                $datos = $result['datos'];
                $cabeceras = ['Fecha', 'Tipo Ticket', 'Cantidad Vendida', 'Plazas Totales', '% Ocupación'];
                break;

            default:
                return ['success' => false, 'error' => __('Tipo de datos no válido', 'flavor-chat-ia')];
        }

        if (empty($datos)) {
            return ['success' => false, 'error' => __('No hay datos para el período seleccionado', 'flavor-chat-ia')];
        }

        // Generar CSV
        $csv_content = '';
        if ($incluir_cabeceras) {
            $csv_content .= implode(';', $cabeceras) . "\n";
        }

        foreach ($datos as $fila) {
            $csv_content .= implode(';', array_map(function($val) {
                // Escapar valores con comillas si contienen punto y coma
                if (strpos($val, ';') !== false || strpos($val, '"') !== false) {
                    return '"' . str_replace('"', '""', $val) . '"';
                }
                return $val;
            }, $fila)) . "\n";
        }

        // Guardar archivo temporal
        $upload_dir = wp_upload_dir();
        $filename = 'export_' . $tipo_datos . '_' . date('Y-m-d_His') . '.csv';
        $filepath = $upload_dir['basedir'] . '/chat-ia-exports/' . $filename;

        // Crear directorio si no existe
        wp_mkdir_p(dirname($filepath));

        // Añadir BOM para Excel (UTF-8)
        $csv_with_bom = "\xEF\xBB\xBF" . $csv_content;
        file_put_contents($filepath, $csv_with_bom);

        // URL de descarga
        $download_url = $upload_dir['baseurl'] . '/chat-ia-exports/' . $filename;

        return [
            'success' => true,
            'mensaje' => "Archivo CSV generado correctamente con " . count($datos) . " filas",
            'archivo' => $filename,
            'url_descarga' => $download_url,
            'filas' => count($datos),
            'periodo' => ['desde' => $fecha_inicio, 'hasta' => $fecha_fin],
        ];
    }

    /**
     * Obtiene reservas formateadas para CSV
     */
    private function get_reservas_para_csv($fecha_inicio, $fecha_fin) {
        global $wpdb;
        $tabla = $wpdb->prefix . 'reservas_tickets';

        $reservas = $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM {$tabla}
             WHERE fecha BETWEEN %s AND %s
             ORDER BY fecha ASC, id ASC",
            $fecha_inicio, $fecha_fin
        ), ARRAY_A);

        $tipos = get_option('calendario_experiencias_ticket_types', []);
        $datos = [];

        foreach ($reservas as $r) {
            $cliente = json_decode($r['cliente'], true) ?? [];
            $slug = $r['ticket_slug'];

            $datos[] = [
                $r['fecha'],
                $r['ticket_code'],
                $tipos[$slug]['name'] ?? $slug,
                $cliente['nombre'] ?? $cliente['name'] ?? 'Sin nombre',
                $cliente['email'] ?? '',
                $cliente['telefono'] ?? $cliente['phone'] ?? '',
                $r['estado'],
                $r['checkin'] ? 'Sí' : 'No',
            ];
        }

        return ['datos' => $datos];
    }

    /**
     * Obtiene clientes formateados para CSV
     */
    private function get_clientes_para_csv($fecha_inicio, $fecha_fin) {
        $result = $this->tool_obtener_datos_clientes([
            'fecha_inicio' => $fecha_inicio,
            'fecha_fin' => $fecha_fin,
            'limite' => 10000,
        ]);

        $datos = [];
        foreach ($result['clientes'] ?? [] as $c) {
            $datos[] = [
                $c['nombre'],
                $c['email'],
                $c['telefono'],
                $c['total_reservas'],
                $c['primera_reserva'],
                $c['ultima_reserva'],
            ];
        }

        return ['datos' => $datos];
    }

    /**
     * Obtiene ingresos formateados para CSV
     */
    private function get_ingresos_para_csv($fecha_inicio, $fecha_fin) {
        global $wpdb;
        $tabla = $wpdb->prefix . 'reservas_tickets';

        $reservas = $wpdb->get_results($wpdb->prepare(
            "SELECT fecha, ticket_slug, COUNT(*) as cantidad
             FROM {$tabla}
             WHERE fecha BETWEEN %s AND %s AND estado != 'cancelado'
             GROUP BY fecha, ticket_slug
             ORDER BY fecha ASC",
            $fecha_inicio, $fecha_fin
        ), ARRAY_A);

        $tipos = get_option('calendario_experiencias_ticket_types', []);
        $datos = [];

        foreach ($reservas as $r) {
            $slug = $r['ticket_slug'];
            $precio = floatval($tipos[$slug]['precio'] ?? 0);
            $cantidad = intval($r['cantidad']);
            $total = $precio * $cantidad;

            $datos[] = [
                $r['fecha'],
                $tipos[$slug]['name'] ?? $slug,
                $cantidad,
                number_format($precio, 2, ',', '.') . '€',
                number_format($total, 2, ',', '.') . '€',
            ];
        }

        return ['datos' => $datos];
    }

    /**
     * Obtiene tickets vendidos formateados para CSV
     */
    private function get_tickets_vendidos_para_csv($fecha_inicio, $fecha_fin) {
        global $wpdb;
        $tabla = $wpdb->prefix . 'reservas_tickets';

        $ventas = $wpdb->get_results($wpdb->prepare(
            "SELECT fecha, ticket_slug, COUNT(*) as cantidad
             FROM {$tabla}
             WHERE fecha BETWEEN %s AND %s AND estado != 'cancelado'
             GROUP BY fecha, ticket_slug
             ORDER BY fecha ASC",
            $fecha_inicio, $fecha_fin
        ), ARRAY_A);

        $tipos = get_option('calendario_experiencias_ticket_types', []);
        $datos = [];

        foreach ($ventas as $v) {
            $slug = $v['ticket_slug'];
            $plazas_totales = intval($tipos[$slug]['plazas'] ?? 0);
            $cantidad = intval($v['cantidad']);
            $ocupacion = $plazas_totales > 0 ? round(($cantidad / $plazas_totales) * 100, 1) : 0;

            $datos[] = [
                $v['fecha'],
                $tipos[$slug]['name'] ?? $slug,
                $cantidad,
                $plazas_totales,
                $ocupacion . '%',
            ];
        }

        return ['datos' => $datos];
    }

    // ==========================================
    // IMPLEMENTACIÓN: GESTIÓN DEL CALENDARIO
    // ==========================================

    /**
     * Asigna estado a día(s) del calendario
     */
    private function tool_asignar_estado_calendario($args) {
        $fecha = sanitize_text_field($args['fecha'] ?? '');
        $fecha_inicio = sanitize_text_field($args['fecha_inicio'] ?? '');
        $fecha_fin = sanitize_text_field($args['fecha_fin'] ?? '');
        $estado = sanitize_text_field($args['estado'] ?? '');
        $dias_semana = $args['dias_semana'] ?? [];

        // Validar que haya al menos una fecha
        if (empty($fecha) && (empty($fecha_inicio) || empty($fecha_fin))) {
            return [
                'success' => false,
                'error' => __('Debes especificar una fecha única o un rango (fecha_inicio y fecha_fin)', 'flavor-chat-ia'),
            ];
        }

        // Si es fecha única, convertir a rango de un día
        if (!empty($fecha)) {
            $fecha_inicio = $fecha;
            $fecha_fin = $fecha;
        }

        // Validar estado existe (si no es vacío)
        if (!empty($estado)) {
            $estados_disponibles = get_option('calendario_experiencias_estados', []);
            if (!isset($estados_disponibles[$estado])) {
                $slugs_disponibles = array_keys($estados_disponibles);
                return [
                    'success' => false,
                    'error' => "Estado '{$estado}' no existe. Estados disponibles: " . implode(', ', $slugs_disponibles),
                ];
            }
        }

        // Crear backup automático
        $backup = Chat_IA_Admin_Backup::get_instance()->crear_backup_automatico(
            "Asignar estado '{$estado}' a calendario ({$fecha_inicio} - {$fecha_fin})",
            'calendario'
        );

        // Obtener días configurados
        $dias_configurados = get_option('calendario_experiencias_dias', []);

        // Generar rango de fechas
        $fecha_actual = new DateTime($fecha_inicio);
        $fecha_final = new DateTime($fecha_fin);
        $dias_afectados = 0;
        $fechas_modificadas = [];

        while ($fecha_actual <= $fecha_final) {
            $fecha_str = $fecha_actual->format('Y-m-d');
            $dia_semana_num = (int) $fecha_actual->format('N'); // 1=Lun, 7=Dom

            // Filtrar por día de semana si se especificó
            if (empty($dias_semana) || in_array($dia_semana_num, $dias_semana)) {
                if (empty($estado)) {
                    // Quitar estado
                    unset($dias_configurados[$fecha_str]);
                } else {
                    $dias_configurados[$fecha_str] = $estado;
                }
                $dias_afectados++;
                $fechas_modificadas[] = $fecha_str;
            }

            $fecha_actual->modify('+1 day');
        }

        // Guardar cambios
        update_option('calendario_experiencias_dias', $dias_configurados);

        // Disparar hook para que otros sistemas se actualicen
        do_action('calendario_experiencias_dias_actualizados', $fechas_modificadas, $estado);

        $nombre_estado = $estado ?: '(sin estado)';
        $accion = empty($estado) ? 'quitado' : 'asignado';

        $contexto = $this->generar_contexto_respuesta('calendario');

        return [
            'success' => true,
            'mensaje' => "Estado '{$nombre_estado}' {$accion} a {$dias_afectados} día(s)",
            'dias_afectados' => $dias_afectados,
            'rango' => ['desde' => $fecha_inicio, 'hasta' => $fecha_fin],
            'estado' => $estado,
            'backup_id' => $backup['backup_id'] ?? null,
            'contexto' => $contexto,
        ];
    }

    /**
     * Resetea todo el calendario
     */
    private function tool_resetear_calendario($args) {
        $confirmar = $args['confirmar'] ?? false;

        if (!$confirmar) {
            return [
                'success' => false,
                'error' => __('Debes confirmar el reseteo estableciendo confirmar=true', 'flavor-chat-ia'),
            ];
        }

        // Crear backup antes de resetear
        $backup = Chat_IA_Admin_Backup::get_instance()->crear_backup_automatico(
            'Resetear calendario completo',
            'calendario'
        );

        $dias_anteriores = get_option('calendario_experiencias_dias', []);
        $total_dias = count($dias_anteriores);

        // Resetear
        update_option('calendario_experiencias_dias', []);

        return [
            'success' => true,
            'mensaje' => "Calendario reseteado. Se eliminaron {$total_dias} día(s) configurados.",
            'dias_eliminados' => $total_dias,
            'backup_id' => $backup['backup_id'] ?? null,
        ];
    }

    // ==========================================
    // IMPLEMENTACIÓN: CRUD ESTADOS
    // ==========================================

    /**
     * Lista estados del calendario
     */
    private function tool_listar_estados_calendario($args) {
        $estados = get_option('calendario_experiencias_estados', []);

        $lista = [];
        foreach ($estados as $slug => $config) {
            $lista[] = [
                'slug' => $slug,
                'nombre' => $config['nombre'] ?? $config['title'] ?? $slug,
                'color' => $config['color'] ?? '#cccccc',
                'horario' => $config['horario'] ?? '',
                'descripcion' => $config['descripcion'] ?? '',
            ];
        }

        return [
            'success' => true,
            'total' => count($lista),
            'estados' => $lista,
        ];
    }

    /**
     * Crea un estado del calendario
     */
    private function tool_crear_estado_calendario($args) {
        $slug = sanitize_title($args['slug'] ?? '');
        $nombre = sanitize_text_field($args['nombre'] ?? '');
        $color = sanitize_hex_color($args['color'] ?? '#cccccc') ?: '#cccccc';
        $horario = sanitize_text_field($args['horario'] ?? '');
        $descripcion = sanitize_text_field($args['descripcion'] ?? '');

        if (empty($slug) || empty($nombre)) {
            return ['success' => false, 'error' => __('Slug y nombre son requeridos', 'flavor-chat-ia')];
        }

        $estados = get_option('calendario_experiencias_estados', []);

        if (isset($estados[$slug])) {
            return ['success' => false, 'error' => "Ya existe un estado con slug '{$slug}'"];
        }

        // Backup antes del cambio
        $backup = Chat_IA_Admin_Backup::get_instance()->crear_backup_automatico(
            "Crear estado '{$slug}'",
            'estados'
        );

        $estados[$slug] = [
            'title' => $nombre,
            'nombre' => $nombre,
            'color' => $color,
            'horario' => $horario,
            'descripcion' => $descripcion,
        ];

        update_option('calendario_experiencias_estados', $estados);

        $contexto = $this->generar_contexto_respuesta('estados');

        return [
            'success' => true,
            'mensaje' => "Estado '{$nombre}' creado correctamente",
            'estado' => [
                'slug' => $slug,
                'nombre' => $nombre,
                'color' => $color,
            ],
            'backup_id' => $backup['backup_id'] ?? null,
            'contexto' => $contexto,
        ];
    }

    /**
     * Edita un estado del calendario
     */
    private function tool_editar_estado_calendario($args) {
        $slug = sanitize_title($args['slug'] ?? '');

        if (empty($slug)) {
            return ['success' => false, 'error' => __('Slug requerido', 'flavor-chat-ia')];
        }

        $estados = get_option('calendario_experiencias_estados', []);

        if (!isset($estados[$slug])) {
            return ['success' => false, 'error' => "Estado '{$slug}' no encontrado"];
        }

        // Backup antes del cambio
        $backup = Chat_IA_Admin_Backup::get_instance()->crear_backup_automatico(
            "Editar estado '{$slug}'",
            'estados'
        );

        // Actualizar solo los campos proporcionados
        if (isset($args['nombre'])) {
            $estados[$slug]['nombre'] = sanitize_text_field($args['nombre']);
            $estados[$slug]['title'] = sanitize_text_field($args['nombre']);
        }
        if (isset($args['color'])) {
            $estados[$slug]['color'] = sanitize_hex_color($args['color']) ?: $estados[$slug]['color'];
        }
        if (isset($args['horario'])) {
            $estados[$slug]['horario'] = sanitize_text_field($args['horario']);
        }
        if (isset($args['descripcion'])) {
            $estados[$slug]['descripcion'] = sanitize_text_field($args['descripcion']);
        }

        update_option('calendario_experiencias_estados', $estados);

        $contexto = $this->generar_contexto_respuesta('estados');

        return [
            'success' => true,
            'mensaje' => "Estado '{$slug}' actualizado",
            'estado' => $estados[$slug],
            'backup_id' => $backup['backup_id'] ?? null,
            'contexto' => $contexto,
        ];
    }

    /**
     * Elimina un estado del calendario
     */
    private function tool_eliminar_estado_calendario($args) {
        $slug = sanitize_title($args['slug'] ?? '');
        $confirmar = $args['confirmar'] ?? false;

        if (empty($slug)) {
            return ['success' => false, 'error' => __('Slug requerido', 'flavor-chat-ia')];
        }

        if (!$confirmar) {
            return ['success' => false, 'error' => __('Debes confirmar la eliminación', 'flavor-chat-ia')];
        }

        $estados = get_option('calendario_experiencias_estados', []);

        if (!isset($estados[$slug])) {
            return ['success' => false, 'error' => "Estado '{$slug}' no encontrado"];
        }

        // Verificar si hay días usando este estado
        $dias = get_option('calendario_experiencias_dias', []);
        $dias_con_estado = array_filter($dias, function($e) use ($slug) {
            return $e === $slug;
        });

        // Backup antes del cambio
        $backup = Chat_IA_Admin_Backup::get_instance()->crear_backup_automatico(
            "Eliminar estado '{$slug}'",
            'estados'
        );

        $nombre = $estados[$slug]['nombre'] ?? $slug;
        unset($estados[$slug]);

        update_option('calendario_experiencias_estados', $estados);

        // Limpiar días que tenían este estado
        if (!empty($dias_con_estado)) {
            foreach (array_keys($dias_con_estado) as $fecha) {
                unset($dias[$fecha]);
            }
            update_option('calendario_experiencias_dias', $dias);
        }

        $contexto = $this->generar_contexto_respuesta('estados');

        return [
            'success' => true,
            'mensaje' => "Estado '{$nombre}' eliminado",
            'dias_afectados' => count($dias_con_estado),
            'backup_id' => $backup['backup_id'] ?? null,
            'contexto' => $contexto,
        ];
    }

    // ==========================================
    // IMPLEMENTACIÓN: CRUD TIPOS DE TICKET
    // ==========================================

    /**
     * Crea un tipo de ticket
     */
    private function tool_crear_tipo_ticket($args) {
        $slug = sanitize_title($args['slug'] ?? '');
        $nombre = sanitize_text_field($args['nombre'] ?? '');
        $precio = floatval($args['precio'] ?? 0);
        $plazas = intval($args['plazas'] ?? 0);
        $descripcion = sanitize_textarea_field($args['descripcion'] ?? '');
        $iva = intval($args['iva'] ?? 21);
        $tipo = sanitize_text_field($args['tipo'] ?? 'normal');
        $estados_validos = sanitize_text_field($args['estados_validos'] ?? '');

        if (empty($slug) || empty($nombre)) {
            return ['success' => false, 'error' => __('Slug y nombre son requeridos', 'flavor-chat-ia')];
        }

        if ($precio < 0) {
            return ['success' => false, 'error' => __('El precio no puede ser negativo', 'flavor-chat-ia')];
        }

        if ($plazas < 0) {
            return ['success' => false, 'error' => __('Las plazas no pueden ser negativas', 'flavor-chat-ia')];
        }

        $tipos = get_option('calendario_experiencias_ticket_types', []);

        if (isset($tipos[$slug])) {
            return ['success' => false, 'error' => "Ya existe un ticket con slug '{$slug}'"];
        }

        // Backup antes del cambio
        $backup = Chat_IA_Admin_Backup::get_instance()->crear_backup_automatico(
            "Crear tipo de ticket '{$slug}'",
            'tickets'
        );

        $tipos[$slug] = [
            'name' => $nombre,
            'nombre' => $nombre,
            'descripcion' => $descripcion,
            'precio' => $precio,
            'plazas' => $plazas,
            'iva' => $iva,
            'tipo' => $tipo,
            'duracion' => 'dia',
            'estados_validos' => $estados_validos,
            'campos_personalizados' => [],
        ];

        update_option('calendario_experiencias_ticket_types', $tipos);

        $contexto = $this->generar_contexto_respuesta('tickets');

        return [
            'success' => true,
            'mensaje' => "Tipo de ticket '{$nombre}' creado correctamente",
            'ticket' => [
                'slug' => $slug,
                'nombre' => $nombre,
                'precio' => $precio,
                'plazas' => $plazas,
            ],
            'backup_id' => $backup['backup_id'] ?? null,
            'contexto' => $contexto,
        ];
    }

    /**
     * Edita un tipo de ticket
     */
    private function tool_editar_tipo_ticket($args) {
        $slug = sanitize_title($args['slug'] ?? '');

        if (empty($slug)) {
            return ['success' => false, 'error' => __('Slug requerido', 'flavor-chat-ia')];
        }

        $tipos = get_option('calendario_experiencias_ticket_types', []);

        if (!isset($tipos[$slug])) {
            return ['success' => false, 'error' => "Tipo de ticket '{$slug}' no encontrado"];
        }

        // Backup antes del cambio
        $backup = Chat_IA_Admin_Backup::get_instance()->crear_backup_automatico(
            "Editar tipo de ticket '{$slug}'",
            'tickets'
        );

        // Actualizar solo los campos proporcionados
        if (isset($args['nombre'])) {
            $tipos[$slug]['name'] = sanitize_text_field($args['nombre']);
            $tipos[$slug]['nombre'] = sanitize_text_field($args['nombre']);
        }
        if (isset($args['precio'])) {
            $tipos[$slug]['precio'] = floatval($args['precio']);
        }
        if (isset($args['plazas'])) {
            $tipos[$slug]['plazas'] = intval($args['plazas']);
        }
        if (isset($args['descripcion'])) {
            $tipos[$slug]['descripcion'] = sanitize_textarea_field($args['descripcion']);
        }
        if (isset($args['iva'])) {
            $tipos[$slug]['iva'] = intval($args['iva']);
        }
        if (isset($args['estados_validos'])) {
            $tipos[$slug]['estados_validos'] = sanitize_text_field($args['estados_validos']);
        }

        update_option('calendario_experiencias_ticket_types', $tipos);

        $contexto = $this->generar_contexto_respuesta('tickets');

        return [
            'success' => true,
            'mensaje' => "Tipo de ticket '{$slug}' actualizado",
            'ticket' => $tipos[$slug],
            'backup_id' => $backup['backup_id'] ?? null,
            'contexto' => $contexto,
        ];
    }

    /**
     * Elimina un tipo de ticket
     */
    private function tool_eliminar_tipo_ticket($args) {
        global $wpdb;

        $slug = sanitize_title($args['slug'] ?? '');
        $confirmar = $args['confirmar'] ?? false;

        if (empty($slug)) {
            return ['success' => false, 'error' => __('Slug requerido', 'flavor-chat-ia')];
        }

        if (!$confirmar) {
            return ['success' => false, 'error' => __('Debes confirmar la eliminación', 'flavor-chat-ia')];
        }

        $tipos = get_option('calendario_experiencias_ticket_types', []);

        if (!isset($tipos[$slug])) {
            return ['success' => false, 'error' => "Tipo de ticket '{$slug}' no encontrado"];
        }

        // Verificar si hay reservas con este tipo
        $tabla = $wpdb->prefix . 'reservas_tickets';
        $reservas_existentes = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM {$tabla} WHERE ticket_slug = %s",
            $slug
        ));

        if ($reservas_existentes > 0) {
            return [
                'success' => false,
                'error' => "No se puede eliminar: existen {$reservas_existentes} reservas con este tipo de ticket",
            ];
        }

        // Backup antes del cambio
        $backup = Chat_IA_Admin_Backup::get_instance()->crear_backup_automatico(
            "Eliminar tipo de ticket '{$slug}'",
            'tickets'
        );

        $nombre = $tipos[$slug]['name'] ?? $slug;
        unset($tipos[$slug]);

        update_option('calendario_experiencias_ticket_types', $tipos);

        // Limpiar mapeo de estados si existe
        $mapping = get_option('calendario_experiencias_state_ticket_mapping', []);
        foreach ($mapping as $estado => $tickets) {
            if (is_array($tickets) && in_array($slug, $tickets)) {
                $mapping[$estado] = array_values(array_diff($tickets, [$slug]));
            }
        }
        update_option('calendario_experiencias_state_ticket_mapping', $mapping);

        $contexto = $this->generar_contexto_respuesta('tickets');

        return [
            'success' => true,
            'mensaje' => "Tipo de ticket '{$nombre}' eliminado",
            'backup_id' => $backup['backup_id'] ?? null,
            'contexto' => $contexto,
        ];
    }

    // ==========================================
    // IMPLEMENTACIÓN: BACKUPS
    // ==========================================

    /**
     * Crea un backup manual
     */
    private function tool_crear_backup($args) {
        $descripcion = sanitize_text_field($args['descripcion'] ?? '');

        $backup = Chat_IA_Admin_Backup::get_instance();
        return $backup->crear_backup_manual($descripcion);
    }

    /**
     * Lista backups disponibles
     */
    private function tool_listar_backups($args) {
        $limite = intval($args['limite'] ?? 20);

        $backup = Chat_IA_Admin_Backup::get_instance();
        return $backup->listar_backups($limite);
    }

    /**
     * Restaura un backup
     */
    private function tool_restaurar_backup($args) {
        $backup_id = sanitize_text_field($args['backup_id'] ?? '');

        if (empty($backup_id)) {
            return ['success' => false, 'error' => __('backup_id requerido', 'flavor-chat-ia')];
        }

        $backup = Chat_IA_Admin_Backup::get_instance();
        return $backup->restaurar_backup($backup_id);
    }

    /**
     * Obtiene historial de cambios
     */
    private function tool_obtener_historial_cambios($args) {
        $limite = intval($args['limite'] ?? 30);

        $backup = Chat_IA_Admin_Backup::get_instance();
        return $backup->obtener_historial($limite);
    }

    // ==========================================
    // IMPLEMENTACION: HERRAMIENTAS OPTIMIZADAS
    // ==========================================

    /**
     * Cache de herramientas read-only
     * @var array Lista de herramientas cacheables
     */
    private static $cacheable_tools = [
        'obtener_tipos_ticket',
        'listar_estados_calendario',
        'obtener_shortcodes_disponibles',
        'obtener_configuracion_sistema',
    ];

    /**
     * Ejecuta herramienta con cache para read-only
     *
     * @param string $tool_name Nombre de la herramienta
     * @param array $arguments Argumentos de la herramienta
     * @return array Resultado de la ejecucion
     */
    public function execute_tool_cached($tool_name, $arguments) {
        // Verificar si es cacheable
        if (in_array($tool_name, self::$cacheable_tools)) {
            $cache_key = 'tool_cache_' . md5($tool_name . serialize($arguments));
            $cached = get_transient($cache_key);

            if ($cached !== false) {
                $cached['_cached'] = true;
                return $cached;
            }

            $result = $this->execute_tool($tool_name, $arguments);

            if ($result['success'] ?? false) {
                set_transient($cache_key, $result, 300); // 5 minutos
            }

            return $result;
        }

        return $this->execute_tool($tool_name, $arguments);
    }

    /**
     * Importa estados del calendario desde texto
     */
    private function tool_importar_estados_texto($args) {
        $texto = $args['texto'] ?? '';

        if (empty($texto)) {
            return ['success' => false, 'error' => __('Texto requerido', 'flavor-chat-ia')];
        }

        // Validar estados disponibles
        $estados_disponibles = get_option('calendario_experiencias_estados', []);
        $slugs_validos = array_keys($estados_disponibles);

        // Parsear texto: aceptar comas, saltos de linea
        $lineas = preg_split('/[\n,]+/', $texto);
        $importaciones = [];
        $errores = [];

        foreach ($lineas as $linea) {
            $linea = trim($linea);
            if (empty($linea)) continue;

            // Formato esperado: fecha:estado o fecha=estado
            if (preg_match('/^(\d{4}-\d{2}-\d{2})[:\s=]+(\w+)$/i', $linea, $matches)) {
                $fecha = $matches[1];
                $estado = strtolower($matches[2]);

                // Validar fecha
                if (!$this->validar_fecha($fecha)) {
                    $errores[] = "Fecha invalida: {$fecha}";
                    continue;
                }

                // Validar estado
                if (!in_array($estado, $slugs_validos)) {
                    $errores[] = "Estado invalido '{$estado}' para {$fecha}. Disponibles: " . implode(', ', $slugs_validos);
                    continue;
                }

                $importaciones[$fecha] = $estado;
            } else {
                $errores[] = "Formato no reconocido: {$linea}";
            }
        }

        if (empty($importaciones)) {
            return [
                'success' => false,
                'error' => __('No se encontraron entradas validas', 'flavor-chat-ia'),
                'errores' => $errores,
            ];
        }

        // Crear backup antes de importar
        $backup = Chat_IA_Admin_Backup::get_instance()->crear_backup_automatico(
            'Importar estados desde texto (' . count($importaciones) . ' dias)',
            'calendario'
        );

        // Obtener dias actuales y fusionar
        $dias_actuales = get_option('calendario_experiencias_dias', []);
        $dias_actuales = array_merge($dias_actuales, $importaciones);

        update_option('calendario_experiencias_dias', $dias_actuales);

        // Disparar hook
        do_action('calendario_experiencias_dias_actualizados', array_keys($importaciones), 'importacion');

        return [
            'success' => true,
            'mensaje' => __('Importacion completada', 'chat-ia-addon'),
            'dias_importados' => count($importaciones),
            'errores' => $errores,
            'preview' => array_slice($importaciones, 0, 5, true),
            'backup_id' => $backup['backup_id'] ?? null,
        ];
    }

    /**
     * Valida formato de fecha
     */
    private function validar_fecha($fecha) {
        $d = DateTime::createFromFormat('Y-m-d', $fecha);
        return $d && $d->format('Y-m-d') === $fecha;
    }

    /**
     * Crea un tipo de ticket de forma rapida
     */
    private function tool_crear_ticket_rapido($args) {
        $nombre = sanitize_text_field($args['nombre'] ?? '');
        $precio = floatval($args['precio'] ?? 0);
        $plazas = intval($args['plazas'] ?? 50);
        $iva = intval($args['iva'] ?? 21);

        if (empty($nombre)) {
            return ['success' => false, 'error' => __('Nombre requerido', 'flavor-chat-ia')];
        }

        if ($precio < 0) {
            return ['success' => false, 'error' => __('El precio no puede ser negativo', 'flavor-chat-ia')];
        }

        // Generar slug automaticamente
        $slug = sanitize_title($nombre);

        // Verificar unicidad
        $tipos = get_option('calendario_experiencias_ticket_types', []);
        $slug_base = $slug;
        $contador = 1;

        while (isset($tipos[$slug])) {
            $slug = $slug_base . '-' . $contador;
            $contador++;
        }

        // Usar la herramienta existente
        return $this->tool_crear_tipo_ticket([
            'slug' => $slug,
            'nombre' => $nombre,
            'precio' => $precio,
            'plazas' => $plazas,
            'iva' => $iva,
            'tipo' => 'normal',
        ]);
    }

    /**
     * Actualiza precio de un ticket existente
     */
    private function tool_actualizar_precio_ticket($args) {
        $ticket_slug = sanitize_title($args['ticket_slug'] ?? '');
        $precio = floatval($args['precio'] ?? 0);

        if (empty($ticket_slug)) {
            return ['success' => false, 'error' => __('ticket_slug requerido', 'flavor-chat-ia')];
        }

        if ($precio < 0) {
            return ['success' => false, 'error' => __('El precio no puede ser negativo', 'flavor-chat-ia')];
        }

        $tipos = get_option('calendario_experiencias_ticket_types', []);

        if (!isset($tipos[$ticket_slug])) {
            // Intentar buscar por nombre parcial
            $encontrado = null;
            foreach ($tipos as $slug => $tipo) {
                if (stripos($slug, $ticket_slug) !== false ||
                    stripos($tipo['name'] ?? '', $ticket_slug) !== false) {
                    $encontrado = $slug;
                    break;
                }
            }

            if ($encontrado) {
                $ticket_slug = $encontrado;
            } else {
                $slugs_disponibles = array_keys($tipos);
                return [
                    'success' => false,
                    'error' => "Ticket '{$ticket_slug}' no encontrado",
                    'tickets_disponibles' => $slugs_disponibles,
                ];
            }
        }

        $precio_anterior = $tipos[$ticket_slug]['precio'] ?? 0;

        // Usar la herramienta existente
        $result = $this->tool_editar_tipo_ticket([
            'slug' => $ticket_slug,
            'precio' => $precio,
        ]);

        if ($result['success']) {
            $result['precio_anterior'] = $precio_anterior;
            $result['precio_nuevo'] = $precio;
            $result['diferencia'] = $precio - $precio_anterior;
        }

        return $result;
    }

    /**
     * Obtiene dashboard compacto pre-formateado
     */
    private function tool_obtener_dashboard_compacto($args) {
        $formato = sanitize_text_field($args['formato'] ?? 'kpis');
        $periodo = sanitize_text_field($args['periodo'] ?? 'hoy');

        // Determinar fechas segun periodo
        $hoy = date('Y-m-d');
        switch ($periodo) {
            case 'semana':
                $fecha_inicio = date('Y-m-d', strtotime('monday this week'));
                $fecha_fin = date('Y-m-d', strtotime('sunday this week'));
                break;
            case 'mes':
                $fecha_inicio = date('Y-m-01');
                $fecha_fin = date('Y-m-t');
                break;
            default: // hoy
                $fecha_inicio = $hoy;
                $fecha_fin = $hoy;
        }

        // Obtener datos
        $resumen = $this->tool_obtener_resumen_periodo([
            'fecha_inicio' => $fecha_inicio,
            'fecha_fin' => $fecha_fin,
        ]);

        $disponibilidad = $this->tool_obtener_plazas_disponibles([
            'fecha' => $hoy,
        ]);

        if (!($resumen['success'] ?? false)) {
            return $resumen;
        }

        // Calcular KPIs adicionales
        $total_reservas = $resumen['total_reservas'] ?? 0;
        $ingresos = $resumen['ingresos_estimados'] ?? 0;
        $ticket_promedio = $total_reservas > 0 ? $ingresos / $total_reservas : 0;

        // Ocupacion media
        $ocupacion_total = 0;
        $tipos_con_plazas = 0;
        foreach ($disponibilidad['disponibilidad'] ?? [] as $disp) {
            if (($disp['plazas_totales'] ?? 0) > 0) {
                $ocupacion_total += $disp['porcentaje_ocupacion'] ?? 0;
                $tipos_con_plazas++;
            }
        }
        $ocupacion_media = $tipos_con_plazas > 0 ? round($ocupacion_total / $tipos_con_plazas, 1) : 0;

        $data = [
            'periodo' => $periodo,
            'fechas' => ['inicio' => $fecha_inicio, 'fin' => $fecha_fin],
            'kpis' => [
                'reservas' => $total_reservas,
                'ingresos' => round($ingresos, 2),
                'ticket_promedio' => round($ticket_promedio, 2),
                'ocupacion_media_hoy' => $ocupacion_media,
            ],
            'por_tipo' => $resumen['por_tipo_ticket'] ?? [],
            'disponibilidad_hoy' => $disponibilidad['disponibilidad'] ?? [],
        ];

        // Formatear segun tipo solicitado
        switch ($formato) {
            case 'kpis':
                // Una linea compacta
                $kpi_line = sprintf(
                    "%d reservas | %.2f€ | Ticket medio: %.2f€ | Ocupacion hoy: %.1f%%",
                    $data['kpis']['reservas'],
                    $data['kpis']['ingresos'],
                    $data['kpis']['ticket_promedio'],
                    $data['kpis']['ocupacion_media_hoy']
                );
                return [
                    'success' => true,
                    'kpi' => $kpi_line,
                    'data' => $data,
                ];

            case 'tabla':
                // Tabla markdown
                $tabla = "| Metrica | Valor |\n|---------|-------|\n";
                $tabla .= "| Reservas | {$data['kpis']['reservas']} |\n";
                $tabla .= "| Ingresos | " . number_format($data['kpis']['ingresos'], 2, ',', '.') . "€ |\n";
                $tabla .= "| Ticket medio | " . number_format($data['kpis']['ticket_promedio'], 2, ',', '.') . "€ |\n";
                $tabla .= "| Ocupacion hoy | {$data['kpis']['ocupacion_media_hoy']}% |\n";

                return [
                    'success' => true,
                    'tabla' => $tabla,
                    'data' => $data,
                ];

            case 'json':
            default:
                return [
                    'success' => true,
                    'data' => $data,
                ];
        }
    }

    /**
     * Obtiene comparativa rapida entre periodos
     */
    private function tool_obtener_comparativa_rapida($args) {
        $tipo = sanitize_text_field($args['tipo'] ?? 'hoy_vs_ayer');

        $hoy = date('Y-m-d');

        switch ($tipo) {
            case 'hoy_vs_ayer':
                $periodo1_inicio = date('Y-m-d', strtotime('-1 day'));
                $periodo1_fin = date('Y-m-d', strtotime('-1 day'));
                $periodo2_inicio = $hoy;
                $periodo2_fin = $hoy;
                $label_p1 = 'Ayer';
                $label_p2 = 'Hoy';
                break;

            case 'semana_vs_anterior':
                $periodo1_inicio = date('Y-m-d', strtotime('monday last week'));
                $periodo1_fin = date('Y-m-d', strtotime('sunday last week'));
                $periodo2_inicio = date('Y-m-d', strtotime('monday this week'));
                $periodo2_fin = date('Y-m-d', strtotime('sunday this week'));
                $label_p1 = 'Semana pasada';
                $label_p2 = 'Esta semana';
                break;

            case 'mes_vs_anterior':
                $periodo1_inicio = date('Y-m-01', strtotime('first day of last month'));
                $periodo1_fin = date('Y-m-t', strtotime('last day of last month'));
                $periodo2_inicio = date('Y-m-01');
                $periodo2_fin = date('Y-m-t');
                $label_p1 = 'Mes pasado';
                $label_p2 = 'Este mes';
                break;

            default:
                return ['success' => false, 'error' => __('Tipo de comparativa no valido', 'flavor-chat-ia')];
        }

        // Usar herramienta existente
        $comparativa = $this->tool_obtener_comparativa_periodos([
            'periodo1_inicio' => $periodo1_inicio,
            'periodo1_fin' => $periodo1_fin,
            'periodo2_inicio' => $periodo2_inicio,
            'periodo2_fin' => $periodo2_fin,
        ]);

        if (!($comparativa['success'] ?? false)) {
            return $comparativa;
        }

        // Agregar labels
        $comparativa['labels'] = [
            'periodo1' => $label_p1,
            'periodo2' => $label_p2,
        ];

        // Generar resumen textual
        $diff_reservas = $comparativa['diferencias']['reservas'] ?? 0;
        $diff_pct = $comparativa['diferencias']['reservas_porcentaje'] ?? 0;
        $signo = $diff_reservas >= 0 ? '+' : '';

        $comparativa['resumen'] = sprintf(
            "%s vs %s: %s%d reservas (%s%.1f%%)",
            $label_p2,
            $label_p1,
            $signo,
            $diff_reservas,
            $signo,
            $diff_pct
        );

        return $comparativa;
    }

    /**
     * Invalida cache de herramientas
     */
    public function invalidate_tools_cache() {
        global $wpdb;
        $wpdb->query(
            "DELETE FROM {$wpdb->options}
             WHERE option_name LIKE '_transient_tool_cache_%'
             OR option_name LIKE '_transient_timeout_tool_cache_%'"
        );
    }
}

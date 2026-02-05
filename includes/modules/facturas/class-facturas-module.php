<?php
/**
 * Módulo de Facturas para Chat IA
 *
 * @package FlavorChatIA
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Módulo de Facturas - Gestión de facturación desde apps móviles
 */
class Flavor_Chat_Facturas_Module extends Flavor_Chat_Module_Base {

    /**
     * Constructor
     */
    public function __construct() {
        $this->id = 'facturas';
        $this->name = __('Facturas', 'flavor-chat-ia');
        $this->description = __('Gestión de facturas y facturación para administradores desde la app móvil.', 'flavor-chat-ia');

        parent::__construct();
    }

    /**
     * {@inheritdoc}
     */
    public function can_activate() {
        // Verificar si existe la tabla de facturas
        global $wpdb;
        $tabla_facturas = $wpdb->prefix . 'flavor_facturas';

        return Flavor_Chat_Helpers::tabla_existe($tabla_facturas);
    }

    /**
     * {@inheritdoc}
     */
    public function get_activation_error() {
        if (!$this->can_activate()) {
            return __('Las tablas de Facturas no están creadas. Activa el módulo para crearlas automáticamente.', 'flavor-chat-ia');
        }
        return '';
    }

    /**
     * {@inheritdoc}
     */
    protected function get_default_settings() {
        return [
            'serie_predeterminada' => 'F',
            'numeracion_inicial' => 1,
            'iva_predeterminado' => 21,
            'requiere_aprobacion' => false,
            'enviar_email_automatico' => true,
            'formato_numero' => 'F-{YEAR}-{NUM}', // F-2025-001
            'retenciones' => [
                'ninguna' => __('Sin retención', 'flavor-chat-ia'),
                'irpf_15' => __('IRPF 15%', 'flavor-chat-ia'),
                'irpf_7' => __('IRPF 7%', 'flavor-chat-ia'),
            ],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function init() {
        add_action('init', [$this, 'maybe_create_tables']);
    }

    /**
     * Crea las tablas si no existen
     */
    public function maybe_create_tables() {
        global $wpdb;
        $tabla_facturas = $wpdb->prefix . 'flavor_facturas';

        if (!Flavor_Chat_Helpers::tabla_existe($tabla_facturas)) {
            $this->create_tables();
        }
    }

    /**
     * Crea las tablas necesarias
     */
    private function create_tables() {
        global $wpdb;
        $charset_collate = $wpdb->get_charset_collate();

        $tabla_facturas = $wpdb->prefix . 'flavor_facturas';
        $tabla_lineas = $wpdb->prefix . 'flavor_facturas_lineas';

        $sql_facturas = "CREATE TABLE IF NOT EXISTS $tabla_facturas (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            numero_factura varchar(50) NOT NULL,
            serie varchar(10) DEFAULT 'F',
            cliente_id bigint(20) unsigned DEFAULT NULL,
            cliente_nombre varchar(255) NOT NULL,
            cliente_nif varchar(50) DEFAULT NULL,
            cliente_direccion text DEFAULT NULL,
            fecha_emision date NOT NULL,
            fecha_vencimiento date DEFAULT NULL,
            base_imponible decimal(10,2) NOT NULL DEFAULT 0.00,
            iva decimal(10,2) NOT NULL DEFAULT 0.00,
            retencion decimal(10,2) DEFAULT 0.00,
            total decimal(10,2) NOT NULL DEFAULT 0.00,
            estado enum('borrador','emitida','cobrada','cancelada') DEFAULT 'borrador',
            observaciones text DEFAULT NULL,
            metodo_pago varchar(50) DEFAULT NULL,
            creado_por bigint(20) unsigned DEFAULT NULL,
            fecha_creacion datetime DEFAULT CURRENT_TIMESTAMP,
            fecha_actualizacion datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            UNIQUE KEY numero_factura (numero_factura),
            KEY cliente_id (cliente_id),
            KEY estado (estado),
            KEY fecha_emision (fecha_emision)
        ) $charset_collate;";

        $sql_lineas = "CREATE TABLE IF NOT EXISTS $tabla_lineas (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            factura_id bigint(20) unsigned NOT NULL,
            concepto varchar(255) NOT NULL,
            descripcion text DEFAULT NULL,
            cantidad decimal(10,2) NOT NULL DEFAULT 1.00,
            precio_unitario decimal(10,2) NOT NULL DEFAULT 0.00,
            descuento decimal(5,2) DEFAULT 0.00,
            iva_porcentaje decimal(5,2) NOT NULL DEFAULT 21.00,
            total_linea decimal(10,2) NOT NULL DEFAULT 0.00,
            orden int(11) DEFAULT 0,
            PRIMARY KEY (id),
            KEY factura_id (factura_id)
        ) $charset_collate;";

        require_once ABSPATH . 'wp-admin/includes/upgrade.php';
        dbDelta($sql_facturas);
        dbDelta($sql_lineas);
    }

    /**
     * {@inheritdoc}
     */
    public function get_actions() {
        return [
            'listar_facturas' => [
                'description' => 'Listar facturas filtradas por estado, cliente o fecha',
                'params' => ['estado', 'cliente_id', 'desde', 'hasta', 'limite'],
            ],
            'ver_factura' => [
                'description' => 'Ver detalles completos de una factura',
                'params' => ['factura_id'],
            ],
            'crear_factura' => [
                'description' => 'Crear una nueva factura',
                'params' => ['cliente_nombre', 'cliente_nif', 'lineas', 'fecha_emision', 'observaciones'],
            ],
            'actualizar_estado' => [
                'description' => 'Cambiar el estado de una factura',
                'params' => ['factura_id', 'nuevo_estado'],
            ],
            'enviar_email' => [
                'description' => 'Enviar factura por email al cliente',
                'params' => ['factura_id', 'email_cliente'],
            ],
            'estadisticas' => [
                'description' => 'Obtener estadísticas de facturación',
                'params' => ['periodo'],
            ],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function execute_action($action_name, $params) {
        $metodo_accion = 'action_' . $action_name;

        if (method_exists($this, $metodo_accion)) {
            return $this->$metodo_accion($params);
        }

        return [
            'success' => false,
            'error' => "Acción no implementada: {$action_name}",
        ];
    }

    /**
     * Acción: Listar facturas
     */
    private function action_listar_facturas($params) {
        if (!current_user_can('manage_options')) {
            return [
                'success' => false,
                'error' => 'No tienes permisos para ver facturas.',
            ];
        }

        global $wpdb;
        $tabla_facturas = $wpdb->prefix . 'flavor_facturas';

        $where = ['1=1'];
        $prepare_values = [];

        if (!empty($params['estado'])) {
            $where[] = 'estado = %s';
            $prepare_values[] = $params['estado'];
        }

        if (!empty($params['cliente_id'])) {
            $where[] = 'cliente_id = %d';
            $prepare_values[] = $params['cliente_id'];
        }

        if (!empty($params['desde'])) {
            $where[] = 'fecha_emision >= %s';
            $prepare_values[] = $params['desde'];
        }

        if (!empty($params['hasta'])) {
            $where[] = 'fecha_emision <= %s';
            $prepare_values[] = $params['hasta'];
        }

        $limite = absint($params['limite'] ?? 20);
        $sql_where = implode(' AND ', $where);

        $sql = "SELECT * FROM $tabla_facturas WHERE $sql_where ORDER BY fecha_emision DESC LIMIT %d";
        $prepare_values[] = $limite;

        $facturas = $wpdb->get_results($wpdb->prepare($sql, ...$prepare_values));

        return [
            'success' => true,
            'total' => count($facturas),
            'facturas' => array_map(function($f) {
                return [
                    'id' => $f->id,
                    'numero' => $f->numero_factura,
                    'cliente' => $f->cliente_nombre,
                    'fecha' => $f->fecha_emision,
                    'total' => floatval($f->total),
                    'estado' => $f->estado,
                ];
            }, $facturas),
        ];
    }

    /**
     * Acción: Ver factura
     */
    private function action_ver_factura($params) {
        if (!current_user_can('manage_options')) {
            return [
                'success' => false,
                'error' => 'No tienes permisos para ver facturas.',
            ];
        }

        global $wpdb;
        $factura_id = absint($params['factura_id'] ?? 0);

        if (!$factura_id) {
            return [
                'success' => false,
                'error' => 'ID de factura inválido.',
            ];
        }

        $tabla_facturas = $wpdb->prefix . 'flavor_facturas';
        $tabla_lineas = $wpdb->prefix . 'flavor_facturas_lineas';

        $factura = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM $tabla_facturas WHERE id = %d",
            $factura_id
        ));

        if (!$factura) {
            return [
                'success' => false,
                'error' => 'Factura no encontrada.',
            ];
        }

        $lineas = $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM $tabla_lineas WHERE factura_id = %d ORDER BY orden",
            $factura_id
        ));

        return [
            'success' => true,
            'factura' => [
                'id' => $factura->id,
                'numero' => $factura->numero_factura,
                'cliente' => [
                    'nombre' => $factura->cliente_nombre,
                    'nif' => $factura->cliente_nif,
                    'direccion' => $factura->cliente_direccion,
                ],
                'fecha_emision' => $factura->fecha_emision,
                'base_imponible' => floatval($factura->base_imponible),
                'iva' => floatval($factura->iva),
                'retencion' => floatval($factura->retencion),
                'total' => floatval($factura->total),
                'estado' => $factura->estado,
                'observaciones' => $factura->observaciones,
                'lineas' => array_map(function($l) {
                    return [
                        'concepto' => $l->concepto,
                        'cantidad' => floatval($l->cantidad),
                        'precio_unitario' => floatval($l->precio_unitario),
                        'total' => floatval($l->total_linea),
                    ];
                }, $lineas),
            ],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function get_tool_definitions() {
        return [
            [
                'name' => 'facturas_listar',
                'description' => 'Lista las facturas del sistema con filtros opcionales',
                'input_schema' => [
                    'type' => 'object',
                    'properties' => [
                        'estado' => [
                            'type' => 'string',
                            'description' => 'Filtrar por estado',
                            'enum' => ['borrador', 'emitida', 'cobrada', 'cancelada'],
                        ],
                        'limite' => [
                            'type' => 'integer',
                            'description' => 'Número máximo de facturas',
                        ],
                    ],
                ],
            ],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function get_knowledge_base() {
        return <<<KNOWLEDGE
**Sistema de Facturas**

Gestión completa de facturación desde la app móvil para administradores.

**Funcionalidades:**
- Crear y editar facturas
- Múltiples líneas de factura con IVA configurable
- Estados: Borrador, Emitida, Cobrada, Cancelada
- Envío automático por email
- Estadísticas de facturación
- Gestión de clientes y datos fiscales

**Formato de numeración:**
Las facturas se numeran automáticamente según el formato configurado (ej: F-2025-001)
KNOWLEDGE;
    }

    /**
     * {@inheritdoc}
     */
    public function get_faqs() {
        return [
            [
                'pregunta' => '¿Cómo crear una factura desde la app?',
                'respuesta' => 'Accede a la sección de Facturas, pulsa "Nueva factura", añade los datos del cliente y las líneas de factura.',
            ],
            [
                'pregunta' => '¿Puedo enviar facturas por email?',
                'respuesta' => 'Sí, las facturas emitidas se pueden enviar automáticamente por email al cliente.',
            ],
        ];
    }

    /**
     * Configuración de formularios del módulo
     *
     * @param string $action_name Nombre de la acción
     * @return array Configuración del formulario
     */
    public function get_form_config($action_name) {
        $configs = [
            'crear_factura' => [
                'title' => __('Crear Nueva Factura', 'flavor-chat-ia'),
                'description' => __('Completa los datos para generar una nueva factura', 'flavor-chat-ia'),
                'fields' => [
                    'cliente_nombre' => [
                        'type' => 'text',
                        'label' => __('Nombre del cliente', 'flavor-chat-ia'),
                        'required' => true,
                        'placeholder' => __('Empresa o nombre completo', 'flavor-chat-ia'),
                    ],
                    'cliente_nif' => [
                        'type' => 'text',
                        'label' => __('NIF/CIF', 'flavor-chat-ia'),
                        'required' => true,
                        'placeholder' => __('B12345678', 'flavor-chat-ia'),
                    ],
                    'cliente_direccion' => [
                        'type' => 'textarea',
                        'label' => __('Dirección del cliente', 'flavor-chat-ia'),
                        'rows' => 3,
                        'placeholder' => __('Calle, número, piso, ciudad, CP', 'flavor-chat-ia'),
                    ],
                    'fecha_emision' => [
                        'type' => 'date',
                        'label' => __('Fecha de emisión', 'flavor-chat-ia'),
                        'required' => true,
                        'default' => date('Y-m-d'),
                    ],
                    'fecha_vencimiento' => [
                        'type' => 'date',
                        'label' => __('Fecha de vencimiento', 'flavor-chat-ia'),
                        'description' => __('Opcional: fecha límite de pago', 'flavor-chat-ia'),
                    ],
                    'concepto' => [
                        'type' => 'text',
                        'label' => __('Concepto principal', 'flavor-chat-ia'),
                        'required' => true,
                        'placeholder' => __('Servicios de...', 'flavor-chat-ia'),
                    ],
                    'cantidad' => [
                        'type' => 'number',
                        'label' => __('Cantidad', 'flavor-chat-ia'),
                        'required' => true,
                        'min' => 0.01,
                        'step' => '0.01',
                        'default' => 1,
                    ],
                    'precio_unitario' => [
                        'type' => 'number',
                        'label' => __('Precio unitario (€)', 'flavor-chat-ia'),
                        'required' => true,
                        'min' => 0,
                        'step' => '0.01',
                        'placeholder' => __('100.00', 'flavor-chat-ia'),
                    ],
                    'iva_porcentaje' => [
                        'type' => 'select',
                        'label' => __('IVA', 'flavor-chat-ia'),
                        'required' => true,
                        'options' => [
                            '0' => __('0% (Exento)', 'flavor-chat-ia'),
                            '4' => __('4% (Superreducido)', 'flavor-chat-ia'),
                            '10' => __('10% (Reducido)', 'flavor-chat-ia'),
                            '21' => __('21% (General)', 'flavor-chat-ia'),
                        ],
                        'default' => '21',
                    ],
                    'retencion' => [
                        'type' => 'select',
                        'label' => __('Retención IRPF', 'flavor-chat-ia'),
                        'options' => [
                            '0' => __('Sin retención', 'flavor-chat-ia'),
                            '7' => __('7% IRPF', 'flavor-chat-ia'),
                            '15' => __('15% IRPF', 'flavor-chat-ia'),
                        ],
                        'default' => '0',
                    ],
                    'metodo_pago' => [
                        'type' => 'select',
                        'label' => __('Método de pago', 'flavor-chat-ia'),
                        'options' => [
                            'transferencia' => __('Transferencia bancaria', 'flavor-chat-ia'),
                            'efectivo' => __('Efectivo', 'flavor-chat-ia'),
                            'tarjeta' => __('Tarjeta', 'flavor-chat-ia'),
                            'bizum' => __('Bizum', 'flavor-chat-ia'),
                        ],
                        'default' => 'transferencia',
                    ],
                    'observaciones' => [
                        'type' => 'textarea',
                        'label' => __('Observaciones', 'flavor-chat-ia'),
                        'rows' => 3,
                        'placeholder' => __('Notas adicionales para el cliente...', 'flavor-chat-ia'),
                    ],
                ],
                'submit_text' => __('Crear Factura', 'flavor-chat-ia'),
                'success_message' => __('Factura creada correctamente', 'flavor-chat-ia'),
                'redirect_url' => '/facturas/mis-facturas/',
            ],
            'buscar_facturas' => [
                'title' => __('Buscar Facturas', 'flavor-chat-ia'),
                'fields' => [
                    'numero_factura' => [
                        'type' => 'text',
                        'label' => __('Número de factura', 'flavor-chat-ia'),
                        'placeholder' => __('F-2025-001', 'flavor-chat-ia'),
                    ],
                    'cliente_nombre' => [
                        'type' => 'text',
                        'label' => __('Nombre del cliente', 'flavor-chat-ia'),
                        'placeholder' => __('Buscar por nombre...', 'flavor-chat-ia'),
                    ],
                    'estado' => [
                        'type' => 'select',
                        'label' => __('Estado', 'flavor-chat-ia'),
                        'options' => [
                            '' => __('Todos los estados', 'flavor-chat-ia'),
                            'borrador' => __('Borrador', 'flavor-chat-ia'),
                            'emitida' => __('Emitida', 'flavor-chat-ia'),
                            'cobrada' => __('Cobrada', 'flavor-chat-ia'),
                            'cancelada' => __('Cancelada', 'flavor-chat-ia'),
                        ],
                    ],
                    'desde' => [
                        'type' => 'date',
                        'label' => __('Desde fecha', 'flavor-chat-ia'),
                    ],
                    'hasta' => [
                        'type' => 'date',
                        'label' => __('Hasta fecha', 'flavor-chat-ia'),
                    ],
                ],
                'submit_text' => __('Buscar', 'flavor-chat-ia'),
            ],
        ];

        return $configs[$action_name] ?? [];
    }

    /**
     * Componentes web del módulo
     */
    public function get_web_components() {
        return [
            'hero' => [
                'label' => __('Hero Facturación', 'flavor-chat-ia'),
                'description' => __('Sección hero con resumen de facturación', 'flavor-chat-ia'),
                'category' => 'hero',
                'icon' => 'dashicons-media-spreadsheet',
                'fields' => [
                    'titulo' => [
                        'type' => 'text',
                        'label' => __('Título', 'flavor-chat-ia'),
                        'default' => __('Sistema de Facturación', 'flavor-chat-ia'),
                    ],
                    'subtitulo' => [
                        'type' => 'textarea',
                        'label' => __('Subtítulo', 'flavor-chat-ia'),
                        'default' => __('Gestiona tus facturas de forma sencilla', 'flavor-chat-ia'),
                    ],
                    'mostrar_resumen' => [
                        'type' => 'toggle',
                        'label' => __('Mostrar resumen mensual', 'flavor-chat-ia'),
                        'default' => true,
                    ],
                ],
                'template' => 'facturas/hero',
            ],
            'facturas_lista' => [
                'label' => __('Lista de Facturas', 'flavor-chat-ia'),
                'description' => __('Tabla de facturas emitidas', 'flavor-chat-ia'),
                'category' => 'listings',
                'icon' => 'dashicons-list-view',
                'fields' => [
                    'titulo' => [
                        'type' => 'text',
                        'label' => __('Título', 'flavor-chat-ia'),
                        'default' => __('Mis Facturas', 'flavor-chat-ia'),
                    ],
                    'estado' => [
                        'type' => 'select',
                        'label' => __('Filtrar por estado', 'flavor-chat-ia'),
                        'options' => ['todas', 'pendientes', 'pagadas', 'vencidas'],
                        'default' => 'todas',
                    ],
                    'limite' => [
                        'type' => 'number',
                        'label' => __('Número máximo', 'flavor-chat-ia'),
                        'default' => 20,
                    ],
                ],
                'template' => 'facturas/facturas-lista',
            ],
            'estadisticas' => [
                'label' => __('Estadísticas Facturación', 'flavor-chat-ia'),
                'description' => __('Gráficos y métricas de facturación', 'flavor-chat-ia'),
                'category' => 'content',
                'icon' => 'dashicons-chart-line',
                'fields' => [
                    'titulo' => [
                        'type' => 'text',
                        'label' => __('Título', 'flavor-chat-ia'),
                        'default' => __('Resumen de Facturación', 'flavor-chat-ia'),
                    ],
                    'periodo' => [
                        'type' => 'select',
                        'label' => __('Periodo', 'flavor-chat-ia'),
                        'options' => ['mes', 'trimestre', 'año'],
                        'default' => 'mes',
                    ],
                ],
                'template' => 'facturas/estadisticas',
            ],
            'cta_nueva' => [
                'label' => __('CTA Nueva Factura', 'flavor-chat-ia'),
                'description' => __('Botón para crear nueva factura', 'flavor-chat-ia'),
                'category' => 'cta',
                'icon' => 'dashicons-plus-alt',
                'fields' => [
                    'titulo' => [
                        'type' => 'text',
                        'label' => __('Título', 'flavor-chat-ia'),
                        'default' => __('Crea tu factura en segundos', 'flavor-chat-ia'),
                    ],
                    'boton_texto' => [
                        'type' => 'text',
                        'label' => __('Texto del botón', 'flavor-chat-ia'),
                        'default' => __('Nueva Factura', 'flavor-chat-ia'),
                    ],
                ],
                'template' => 'facturas/cta-nueva',
            ],
        ];
    }
}

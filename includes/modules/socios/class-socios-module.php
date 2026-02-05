<?php
/**
 * Módulo de Gestión de Socios para Chat IA
 *
 * @package FlavorChatIA
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Módulo de Gestión de Socios - Control de socios/miembros de la cooperativa
 */
class Flavor_Chat_Socios_Module extends Flavor_Chat_Module_Base {

    /**
     * Constructor
     */
    public function __construct() {
        $this->id = 'socios';
        $this->name = __('Gestión de Socios', 'flavor-chat-ia');
        $this->description = __('Gestión de socios, cuotas y membresías desde la app móvil.', 'flavor-chat-ia');

        parent::__construct();
    }

    /**
     * {@inheritdoc}
     */
    public function can_activate() {
        global $wpdb;
        $tabla_socios = $wpdb->prefix . 'flavor_socios';

        return Flavor_Chat_Helpers::tabla_existe($tabla_socios);
    }

    /**
     * {@inheritdoc}
     */
    public function get_activation_error() {
        if (!$this->can_activate()) {
            return __('Las tablas de Socios no están creadas. Activa el módulo para crearlas automáticamente.', 'flavor-chat-ia');
        }
        return '';
    }

    /**
     * {@inheritdoc}
     */
    protected function get_default_settings() {
        return [
            'cuota_mensual' => 30.00,
            'cuota_anual' => 300.00,
            'dia_cargo' => 1, // Día del mes para cargo
            'permite_cuota_reducida' => true,
            'requiere_validacion_alta' => true,
            'tipos_socio' => [
                'consumidor' => __('Socio Consumidor', 'flavor-chat-ia'),
                'trabajador' => __('Socio Trabajador', 'flavor-chat-ia'),
                'colaborador' => __('Socio Colaborador', 'flavor-chat-ia'),
            ],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function init() {
        add_action('init', [$this, 'maybe_create_tables']);

        // Cargar sistema de cuotas periodicas
        $ruta_archivo_subscriptions = FLAVOR_CHAT_IA_PATH . 'includes/modules/socios/class-socios-subscriptions.php';
        if ( file_exists( $ruta_archivo_subscriptions ) ) {
            require_once $ruta_archivo_subscriptions;
            Flavor_Socios_Subscriptions::get_instance();
        }
    }

    /**
     * Crea las tablas si no existen
     */
    public function maybe_create_tables() {
        global $wpdb;
        $tabla_socios = $wpdb->prefix . 'flavor_socios';

        if (!Flavor_Chat_Helpers::tabla_existe($tabla_socios)) {
            $this->create_tables();
        }
    }

    /**
     * Crea las tablas necesarias
     */
    private function create_tables() {
        global $wpdb;
        $charset_collate = $wpdb->get_charset_collate();

        $tabla_socios = $wpdb->prefix . 'flavor_socios';
        $tabla_cuotas = $wpdb->prefix . 'flavor_socios_cuotas';

        $sql_socios = "CREATE TABLE IF NOT EXISTS $tabla_socios (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            usuario_id bigint(20) unsigned NOT NULL,
            numero_socio varchar(50) NOT NULL,
            tipo_socio varchar(50) DEFAULT 'consumidor',
            fecha_alta date NOT NULL,
            fecha_baja date DEFAULT NULL,
            estado enum('activo','suspendido','baja') DEFAULT 'activo',
            cuota_mensual decimal(10,2) NOT NULL DEFAULT 30.00,
            cuota_reducida tinyint(1) DEFAULT 0,
            datos_bancarios text DEFAULT NULL,
            notas text DEFAULT NULL,
            fecha_creacion datetime DEFAULT CURRENT_TIMESTAMP,
            fecha_actualizacion datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            UNIQUE KEY usuario_id (usuario_id),
            UNIQUE KEY numero_socio (numero_socio),
            KEY tipo_socio (tipo_socio),
            KEY estado (estado)
        ) $charset_collate;";

        $sql_cuotas = "CREATE TABLE IF NOT EXISTS $tabla_cuotas (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            socio_id bigint(20) unsigned NOT NULL,
            periodo varchar(20) NOT NULL,
            importe decimal(10,2) NOT NULL,
            fecha_cargo date NOT NULL,
            fecha_pago date DEFAULT NULL,
            estado enum('pendiente','pagada','vencida','condonada') DEFAULT 'pendiente',
            metodo_pago varchar(50) DEFAULT NULL,
            referencia_pago varchar(100) DEFAULT NULL,
            notas text DEFAULT NULL,
            fecha_creacion datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            UNIQUE KEY socio_periodo (socio_id, periodo),
            KEY socio_id (socio_id),
            KEY estado (estado),
            KEY fecha_cargo (fecha_cargo)
        ) $charset_collate;";

        require_once ABSPATH . 'wp-admin/includes/upgrade.php';
        dbDelta($sql_socios);
        dbDelta($sql_cuotas);
    }

    /**
     * {@inheritdoc}
     */
    public function get_actions() {
        return [
            'mi_perfil_socio' => [
                'description' => 'Ver mi información como socio',
                'params' => [],
            ],
            'mis_cuotas' => [
                'description' => 'Ver estado de mis cuotas',
                'params' => ['estado', 'limite'],
            ],
            'pagar_cuota' => [
                'description' => 'Registrar pago de una cuota',
                'params' => ['cuota_id', 'metodo_pago', 'referencia'],
            ],
            'listar_socios' => [
                'description' => 'Listar socios (solo admin)',
                'params' => ['tipo', 'estado', 'limite'],
            ],
            'dar_alta_socio' => [
                'description' => 'Dar de alta un nuevo socio (solo admin)',
                'params' => ['usuario_id', 'tipo_socio', 'cuota_mensual'],
            ],
            'dar_baja_socio' => [
                'description' => 'Dar de baja a un socio (solo admin)',
                'params' => ['socio_id', 'motivo'],
            ],
            'estadisticas_socios' => [
                'description' => 'Obtener estadísticas de socios (solo admin)',
                'params' => [],
            ],
            'actualizar_datos' => [
                'description' => 'Actualizar datos personales del socio',
                'params' => ['telefono', 'direccion', 'iban', 'notas'],
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
     * Acción: Ver mi perfil de socio
     */
    private function action_mi_perfil_socio($params) {
        $usuario_id = get_current_user_id();

        if (!$usuario_id) {
            return [
                'success' => false,
                'error' => 'Debes iniciar sesión.',
            ];
        }

        global $wpdb;
        $tabla_socios = $wpdb->prefix . 'flavor_socios';

        $socio = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM $tabla_socios WHERE usuario_id = %d",
            $usuario_id
        ));

        if (!$socio) {
            return [
                'success' => false,
                'error' => 'No estás registrado como socio.',
            ];
        }

        $usuario = get_userdata($usuario_id);

        return [
            'success' => true,
            'socio' => [
                'numero' => $socio->numero_socio,
                'nombre' => $usuario->display_name,
                'email' => $usuario->user_email,
                'tipo' => $socio->tipo_socio,
                'fecha_alta' => date('d/m/Y', strtotime($socio->fecha_alta)),
                'estado' => $socio->estado,
                'cuota_mensual' => floatval($socio->cuota_mensual),
                'cuota_reducida' => (bool) $socio->cuota_reducida,
            ],
        ];
    }

    /**
     * Acción: Ver mis cuotas
     */
    private function action_mis_cuotas($params) {
        $usuario_id = get_current_user_id();

        if (!$usuario_id) {
            return [
                'success' => false,
                'error' => 'Debes iniciar sesión.',
            ];
        }

        global $wpdb;
        $tabla_socios = $wpdb->prefix . 'flavor_socios';
        $tabla_cuotas = $wpdb->prefix . 'flavor_socios_cuotas';

        $socio = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM $tabla_socios WHERE usuario_id = %d",
            $usuario_id
        ));

        if (!$socio) {
            return [
                'success' => false,
                'error' => 'No estás registrado como socio.',
            ];
        }

        $where = ['socio_id = %d'];
        $prepare_values = [$socio->id];

        if (!empty($params['estado'])) {
            $where[] = 'estado = %s';
            $prepare_values[] = $params['estado'];
        }

        $limite = absint($params['limite'] ?? 12);
        $sql_where = implode(' AND ', $where);

        $sql = "SELECT * FROM $tabla_cuotas WHERE $sql_where ORDER BY fecha_cargo DESC LIMIT %d";
        $prepare_values[] = $limite;

        $cuotas = $wpdb->get_results($wpdb->prepare($sql, ...$prepare_values));

        $cuotas_pendientes = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM $tabla_cuotas WHERE socio_id = %d AND estado = 'pendiente'",
            $socio->id
        ));

        $total_pendiente = $wpdb->get_var($wpdb->prepare(
            "SELECT IFNULL(SUM(importe), 0) FROM $tabla_cuotas WHERE socio_id = %d AND estado = 'pendiente'",
            $socio->id
        ));

        return [
            'success' => true,
            'resumen' => [
                'cuotas_pendientes' => $cuotas_pendientes,
                'total_pendiente' => floatval($total_pendiente),
            ],
            'cuotas' => array_map(function($c) {
                return [
                    'id' => $c->id,
                    'periodo' => $c->periodo,
                    'importe' => floatval($c->importe),
                    'fecha_cargo' => date('d/m/Y', strtotime($c->fecha_cargo)),
                    'estado' => $c->estado,
                    'fecha_pago' => $c->fecha_pago ? date('d/m/Y', strtotime($c->fecha_pago)) : null,
                ];
            }, $cuotas),
        ];
    }

    /**
     * Acción: Estadísticas de socios (solo admin)
     */
    private function action_estadisticas_socios($params) {
        if (!current_user_can('manage_options')) {
            return [
                'success' => false,
                'error' => 'No tienes permisos para ver estadísticas.',
            ];
        }

        global $wpdb;
        $tabla_socios = $wpdb->prefix . 'flavor_socios';
        $tabla_cuotas = $wpdb->prefix . 'flavor_socios_cuotas';

        $total_socios = $wpdb->get_var("SELECT COUNT(*) FROM $tabla_socios WHERE estado = 'activo'");
        $socios_por_tipo = $wpdb->get_results(
            "SELECT tipo_socio, COUNT(*) as total FROM $tabla_socios WHERE estado = 'activo' GROUP BY tipo_socio"
        );
        $cuotas_pendientes = $wpdb->get_var(
            "SELECT COUNT(*) FROM $tabla_cuotas WHERE estado = 'pendiente'"
        );
        $importe_pendiente = $wpdb->get_var(
            "SELECT IFNULL(SUM(importe), 0) FROM $tabla_cuotas WHERE estado = 'pendiente'"
        );

        return [
            'success' => true,
            'estadisticas' => [
                'total_socios' => $total_socios,
                'socios_por_tipo' => $socios_por_tipo,
                'cuotas_pendientes' => $cuotas_pendientes,
                'importe_pendiente' => floatval($importe_pendiente),
            ],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function get_tool_definitions() {
        return [
            [
                'name' => 'socios_mi_perfil',
                'description' => 'Ver mi información como socio de la cooperativa',
                'input_schema' => [
                    'type' => 'object',
                    'properties' => [],
                ],
            ],
            [
                'name' => 'socios_mis_cuotas',
                'description' => 'Ver el estado de mis cuotas de socio',
                'input_schema' => [
                    'type' => 'object',
                    'properties' => [
                        'estado' => [
                            'type' => 'string',
                            'description' => 'Filtrar por estado',
                            'enum' => ['pendiente', 'pagada', 'vencida'],
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
**Sistema de Gestión de Socios**

Control completo de socios, cuotas y membresías de la cooperativa.

**Funcionalidades:**
- Registro de socios
- Gestión de cuotas mensuales/anuales
- Control de pagos
- Tipos de socio configurables
- Cuotas reducidas
- Estadísticas y reportes
- Altas y bajas

**Tipos de socio:**
- Consumidor: Socio que consume productos/servicios
- Trabajador: Socio que trabaja en la cooperativa
- Colaborador: Socio que colabora sin trabajar

**Gestión de cuotas:**
- Generación automática mensual
- Control de pagos y vencimientos
- Recordatorios automáticos
- Historial completo
KNOWLEDGE;
    }

    /**
     * {@inheritdoc}
     */
    public function get_faqs() {
        return [
            [
                'pregunta' => '¿Cómo pago mi cuota de socio?',
                'respuesta' => 'Puedes pagar desde la app, sección Socios, o mediante transferencia bancaria a la cuenta de la cooperativa.',
            ],
            [
                'pregunta' => '¿Qué pasa si no pago mi cuota?',
                'respuesta' => 'Tras varios meses impagados, tu estado de socio puede ser suspendido hasta regularizar la situación.',
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
            'dar_alta_socio' => [
                'title' => __('Hazte Socio', 'flavor-chat-ia'),
                'description' => __('Únete a nuestra comunidad y disfruta de todos los beneficios', 'flavor-chat-ia'),
                'fields' => [
                    'tipo_socio' => [
                        'type' => 'select',
                        'label' => __('Tipo de socio', 'flavor-chat-ia'),
                        'required' => true,
                        'options' => [
                            'consumidor' => __('Socio Consumidor - Acceso a grupo de consumo', 'flavor-chat-ia'),
                            'trabajador' => __('Socio Trabajador - Trabajas en la cooperativa', 'flavor-chat-ia'),
                            'colaborador' => __('Socio Colaborador - Apoyas el proyecto', 'flavor-chat-ia'),
                        ],
                        'default' => 'consumidor',
                    ],
                    'nombre_completo' => [
                        'type' => 'text',
                        'label' => __('Nombre completo', 'flavor-chat-ia'),
                        'required' => true,
                        'placeholder' => __('Tu nombre y apellidos', 'flavor-chat-ia'),
                    ],
                    'dni_nif' => [
                        'type' => 'text',
                        'label' => __('DNI/NIE/NIF', 'flavor-chat-ia'),
                        'required' => true,
                        'placeholder' => __('12345678X', 'flavor-chat-ia'),
                    ],
                    'email' => [
                        'type' => 'email',
                        'label' => __('Email', 'flavor-chat-ia'),
                        'required' => true,
                        'placeholder' => __('tu@email.com', 'flavor-chat-ia'),
                    ],
                    'telefono' => [
                        'type' => 'tel',
                        'label' => __('Teléfono', 'flavor-chat-ia'),
                        'required' => true,
                        'placeholder' => __('600123456', 'flavor-chat-ia'),
                    ],
                    'direccion' => [
                        'type' => 'textarea',
                        'label' => __('Dirección completa', 'flavor-chat-ia'),
                        'required' => true,
                        'rows' => 3,
                        'placeholder' => __('Calle, número, piso, ciudad, CP', 'flavor-chat-ia'),
                    ],
                    'iban' => [
                        'type' => 'text',
                        'label' => __('IBAN para domiciliación de cuotas', 'flavor-chat-ia'),
                        'required' => true,
                        'placeholder' => __('ES00 0000 0000 0000 0000 0000', 'flavor-chat-ia'),
                        'description' => __('Necesario para el pago automático de cuotas', 'flavor-chat-ia'),
                    ],
                    'cuota_reducida' => [
                        'type' => 'checkbox',
                        'label' => __('Solicitar cuota reducida', 'flavor-chat-ia'),
                        'checkbox_label' => __('Por situación económica, solicito cuota reducida (requiere justificación)', 'flavor-chat-ia'),
                    ],
                    'motivo_adhesion' => [
                        'type' => 'textarea',
                        'label' => __('¿Por qué quieres ser socio?', 'flavor-chat-ia'),
                        'rows' => 4,
                        'placeholder' => __('Cuéntanos tus motivaciones...', 'flavor-chat-ia'),
                    ],
                    'acepto_estatutos' => [
                        'type' => 'checkbox',
                        'label' => __('Acepto los estatutos', 'flavor-chat-ia'),
                        'checkbox_label' => __('He leído y acepto los estatutos de la cooperativa', 'flavor-chat-ia'),
                        'required' => true,
                    ],
                ],
                'submit_text' => __('Solicitar Alta como Socio', 'flavor-chat-ia'),
                'success_message' => __('¡Solicitud recibida! Te contactaremos en breve para completar el proceso.', 'flavor-chat-ia'),
                'redirect_url' => '/socios/bienvenida/',
            ],
            'pagar_cuota' => [
                'title' => __('Pagar Cuota', 'flavor-chat-ia'),
                'description' => __('Registra el pago de tu cuota de socio', 'flavor-chat-ia'),
                'fields' => [
                    'cuota_id' => [
                        'type' => 'hidden',
                        'required' => true,
                    ],
                    'metodo_pago' => [
                        'type' => 'select',
                        'label' => __('Método de pago', 'flavor-chat-ia'),
                        'required' => true,
                        'options' => [
                            'transferencia' => __('Transferencia bancaria', 'flavor-chat-ia'),
                            'efectivo' => __('Efectivo', 'flavor-chat-ia'),
                            'bizum' => __('Bizum', 'flavor-chat-ia'),
                            'domiciliacion' => __('Domiciliación bancaria', 'flavor-chat-ia'),
                        ],
                    ],
                    'referencia' => [
                        'type' => 'text',
                        'label' => __('Referencia de pago', 'flavor-chat-ia'),
                        'placeholder' => __('Nº de operación, recibo, etc.', 'flavor-chat-ia'),
                        'description' => __('Si el pago fue por transferencia o Bizum', 'flavor-chat-ia'),
                    ],
                    'fecha_pago' => [
                        'type' => 'date',
                        'label' => __('Fecha de pago', 'flavor-chat-ia'),
                        'required' => true,
                        'default' => date('Y-m-d'),
                    ],
                ],
                'submit_text' => __('Registrar Pago', 'flavor-chat-ia'),
                'success_message' => __('Pago registrado correctamente. Gracias!', 'flavor-chat-ia'),
            ],
            'actualizar_datos' => [
                'title' => __('Actualizar Mis Datos', 'flavor-chat-ia'),
                'description' => __('Mantén tu información actualizada', 'flavor-chat-ia'),
                'fields' => [
                    'telefono' => [
                        'type' => 'tel',
                        'label' => __('Teléfono', 'flavor-chat-ia'),
                        'placeholder' => __('600123456', 'flavor-chat-ia'),
                    ],
                    'direccion' => [
                        'type' => 'textarea',
                        'label' => __('Dirección', 'flavor-chat-ia'),
                        'rows' => 3,
                    ],
                    'iban' => [
                        'type' => 'text',
                        'label' => __('IBAN', 'flavor-chat-ia'),
                        'placeholder' => __('ES00 0000 0000 0000 0000 0000', 'flavor-chat-ia'),
                    ],
                    'notas' => [
                        'type' => 'textarea',
                        'label' => __('Notas o comentarios', 'flavor-chat-ia'),
                        'rows' => 3,
                        'placeholder' => __('Cualquier información relevante...', 'flavor-chat-ia'),
                    ],
                ],
                'submit_text' => __('Actualizar Datos', 'flavor-chat-ia'),
                'success_message' => __('Datos actualizados correctamente', 'flavor-chat-ia'),
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
                'label' => __('Hero Socios', 'flavor-chat-ia'),
                'description' => __('Sección hero con información de membresía', 'flavor-chat-ia'),
                'category' => 'hero',
                'icon' => 'dashicons-groups',
                'fields' => [
                    'titulo' => [
                        'type' => 'text',
                        'label' => __('Título', 'flavor-chat-ia'),
                        'default' => __('Hazte Socio', 'flavor-chat-ia'),
                    ],
                    'subtitulo' => [
                        'type' => 'textarea',
                        'label' => __('Subtítulo', 'flavor-chat-ia'),
                        'default' => __('Únete a nuestra comunidad y disfruta de todos los beneficios', 'flavor-chat-ia'),
                    ],
                    'mostrar_contador' => [
                        'type' => 'toggle',
                        'label' => __('Mostrar número de socios', 'flavor-chat-ia'),
                        'default' => true,
                    ],
                ],
                'template' => 'socios/hero',
            ],
            'beneficios' => [
                'label' => __('Beneficios de Socios', 'flavor-chat-ia'),
                'description' => __('Grid de ventajas de ser socio', 'flavor-chat-ia'),
                'category' => 'features',
                'icon' => 'dashicons-star-filled',
                'fields' => [
                    'titulo' => [
                        'type' => 'text',
                        'label' => __('Título', 'flavor-chat-ia'),
                        'default' => __('Beneficios', 'flavor-chat-ia'),
                    ],
                    'columnas' => [
                        'type' => 'select',
                        'label' => __('Columnas', 'flavor-chat-ia'),
                        'options' => [2, 3, 4],
                        'default' => 3,
                    ],
                ],
                'template' => 'socios/beneficios',
            ],
            'tipos_membresia' => [
                'label' => __('Tipos de Membresía', 'flavor-chat-ia'),
                'description' => __('Planes y cuotas disponibles', 'flavor-chat-ia'),
                'category' => 'content',
                'icon' => 'dashicons-id-alt',
                'fields' => [
                    'titulo' => [
                        'type' => 'text',
                        'label' => __('Título', 'flavor-chat-ia'),
                        'default' => __('Tipos de Membresía', 'flavor-chat-ia'),
                    ],
                    'mostrar_precios' => [
                        'type' => 'toggle',
                        'label' => __('Mostrar precios', 'flavor-chat-ia'),
                        'default' => true,
                    ],
                ],
                'template' => 'socios/tipos-membresia',
            ],
            'formulario_alta' => [
                'label' => __('Formulario de Alta', 'flavor-chat-ia'),
                'description' => __('Formulario para hacerse socio', 'flavor-chat-ia'),
                'category' => 'forms',
                'icon' => 'dashicons-edit',
                'fields' => [
                    'titulo' => [
                        'type' => 'text',
                        'label' => __('Título', 'flavor-chat-ia'),
                        'default' => __('Únete Ahora', 'flavor-chat-ia'),
                    ],
                    'campos_extra' => [
                        'type' => 'toggle',
                        'label' => __('Mostrar campos adicionales', 'flavor-chat-ia'),
                        'default' => false,
                    ],
                ],
                'template' => 'socios/formulario-alta',
            ],
            'testimonios' => [
                'label' => __('Testimonios de Socios', 'flavor-chat-ia'),
                'description' => __('Opiniones de socios actuales', 'flavor-chat-ia'),
                'category' => 'testimonials',
                'icon' => 'dashicons-format-quote',
                'fields' => [
                    'titulo' => [
                        'type' => 'text',
                        'label' => __('Título', 'flavor-chat-ia'),
                        'default' => __('Lo que dicen nuestros socios', 'flavor-chat-ia'),
                    ],
                    'limite' => [
                        'type' => 'number',
                        'label' => __('Número máximo', 'flavor-chat-ia'),
                        'default' => 3,
                    ],
                ],
                'template' => 'socios/testimonios',
            ],
        ];
    }

    /**
     * Accion: Actualizar datos del socio
     */
    private function action_actualizar_datos($params) {
        $usuario_id = get_current_user_id();

        if (!$usuario_id) {
            return [
                'success' => false,
                'error' => 'Debes iniciar sesion.',
            ];
        }

        global $wpdb;
        $tabla_socios = $wpdb->prefix . 'flavor_socios';

        $socio = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM $tabla_socios WHERE usuario_id = %d AND estado = 'activo'",
            $usuario_id
        ));

        if (!$socio) {
            return [
                'success' => false,
                'error' => 'No se encontro registro de socio activo.',
            ];
        }

        $datos_actualizar = [];
        $campos_permitidos = ['telefono', 'direccion', 'iban'];

        foreach ($campos_permitidos as $campo) {
            if (isset($params[$campo]) && $params[$campo] !== '') {
                $datos_actualizar[$campo] = sanitize_text_field($params[$campo]);
            }
        }

        if (empty($datos_actualizar)) {
            return [
                'success' => false,
                'error' => 'No se proporcionaron datos para actualizar.',
            ];
        }

        $resultado = $wpdb->update(
            $tabla_socios,
            $datos_actualizar,
            ['id' => $socio->id]
        );

        if ($resultado === false) {
            return [
                'success' => false,
                'error' => 'Error al actualizar los datos.',
            ];
        }

        return [
            'success' => true,
            'mensaje' => 'Datos actualizados correctamente.',
        ];
    }
}

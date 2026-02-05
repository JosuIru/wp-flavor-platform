<?php
/**
 * Módulo Banco de Tiempo para Chat IA
 *
 * @package FlavorChatIA
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Módulo de Banco de Tiempo - Intercambio de servicios por horas
 */
class Flavor_Chat_Banco_Tiempo_Module extends Flavor_Chat_Module_Base {

    /**
     * Constructor
     */
    public function __construct() {
        $this->id = 'banco_tiempo';
        $this->name = __('Banco de Tiempo', 'flavor-chat-ia');
        $this->description = __('Sistema de intercambio de servicios por horas entre miembros.', 'flavor-chat-ia');

        parent::__construct();
    }

    /**
     * {@inheritdoc}
     */
    public function can_activate() {
        // Verificar si existe la tabla de banco de tiempo
        global $wpdb;
        $tabla_servicios = $wpdb->prefix . 'flavor_banco_tiempo_servicios';

        return Flavor_Chat_Helpers::tabla_existe($tabla_servicios);
    }

    /**
     * {@inheritdoc}
     */
    public function get_activation_error() {
        if (!$this->can_activate()) {
            return __('Las tablas del Banco de Tiempo no están creadas. Se crearán automáticamente al activar.', 'flavor-chat-ia');
        }
        return '';
    }

    /**
     * {@inheritdoc}
     */
    protected function get_default_settings() {
        return [
            'hora_minima_intercambio' => 0.5, // 30 minutos mínimo
            'hora_maxima_intercambio' => 8, // 8 horas máximo por servicio
            'requiere_validacion' => true,
            'categorias_servicios' => [
                'cuidados' => __('Cuidados', 'flavor-chat-ia'),
                'educacion' => __('Educación', 'flavor-chat-ia'),
                'bricolaje' => __('Bricolaje', 'flavor-chat-ia'),
                'tecnologia' => __('Tecnología', 'flavor-chat-ia'),
                'transporte' => __('Transporte', 'flavor-chat-ia'),
                'otros' => __('Otros', 'flavor-chat-ia'),
            ],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function init() {
        add_action('init', [$this, 'maybe_create_tables']);
        add_action('flavor_banco_tiempo_servicio_completado', [$this, 'on_servicio_completado'], 10, 2);
    }

    /**
     * Crea las tablas si no existen
     */
    public function maybe_create_tables() {
        global $wpdb;
        $tabla_servicios = $wpdb->prefix . 'flavor_banco_tiempo_servicios';

        if (!Flavor_Chat_Helpers::tabla_existe($tabla_servicios)) {
            $this->create_tables();
        }
    }

    /**
     * Crea las tablas necesarias
     */
    private function create_tables() {
        global $wpdb;
        $charset_collate = $wpdb->get_charset_collate();

        $tabla_servicios = $wpdb->prefix . 'flavor_banco_tiempo_servicios';
        $tabla_transacciones = $wpdb->prefix . 'flavor_banco_tiempo_transacciones';

        $sql_servicios = "CREATE TABLE IF NOT EXISTS $tabla_servicios (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            usuario_id bigint(20) unsigned NOT NULL,
            titulo varchar(255) NOT NULL,
            descripcion text NOT NULL,
            categoria varchar(100) DEFAULT 'otros',
            horas_estimadas decimal(5,2) DEFAULT 1.00,
            ubicacion varchar(500) DEFAULT NULL,
            disponibilidad text DEFAULT NULL,
            estado enum('activo','pausado','completado','cancelado') DEFAULT 'activo',
            fecha_publicacion datetime DEFAULT CURRENT_TIMESTAMP,
            fecha_actualizacion datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY usuario_id (usuario_id),
            KEY categoria (categoria),
            KEY estado (estado)
        ) $charset_collate;";

        $sql_transacciones = "CREATE TABLE IF NOT EXISTS $tabla_transacciones (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            servicio_id bigint(20) unsigned NOT NULL,
            usuario_solicitante_id bigint(20) unsigned NOT NULL,
            usuario_receptor_id bigint(20) unsigned NOT NULL,
            horas decimal(5,2) NOT NULL,
            mensaje text DEFAULT NULL,
            fecha_preferida datetime DEFAULT NULL,
            valoracion_solicitante tinyint(1) DEFAULT NULL,
            valoracion_receptor tinyint(1) DEFAULT NULL,
            comentario_solicitante text DEFAULT NULL,
            comentario_receptor text DEFAULT NULL,
            estado enum('pendiente','aceptado','en_curso','completado','cancelado','rechazado') DEFAULT 'pendiente',
            fecha_solicitud datetime DEFAULT CURRENT_TIMESTAMP,
            fecha_completado datetime DEFAULT NULL,
            PRIMARY KEY (id),
            KEY servicio_id (servicio_id),
            KEY usuario_solicitante_id (usuario_solicitante_id),
            KEY usuario_receptor_id (usuario_receptor_id),
            KEY estado (estado)
        ) $charset_collate;";

        require_once ABSPATH . 'wp-admin/includes/upgrade.php';
        dbDelta($sql_servicios);
        dbDelta($sql_transacciones);
    }

    /**
     * Callback cuando un servicio se completa
     */
    public function on_servicio_completado($intercambio_id, $horas) {
        // Lógica adicional al completar servicio
        do_action('flavor_chat_ia_log_event', 'banco_tiempo_servicio_completado', [
            'intercambio_id' => $intercambio_id,
            'horas' => $horas,
        ]);
    }

    /**
     * {@inheritdoc}
     */
    public function get_actions() {
        return [
            'ver_saldo' => [
                'description' => 'Ver saldo de horas del usuario',
                'params' => ['usuario_id'],
            ],
            'ofrecer_servicio' => [
                'description' => 'Publicar un servicio que puedes ofrecer',
                'params' => ['titulo', 'descripcion', 'categoria', 'horas_estimadas'],
            ],
            'buscar_servicios' => [
                'description' => 'Buscar servicios disponibles',
                'params' => ['busqueda', 'categoria', 'limite'],
            ],
            'solicitar_servicio' => [
                'description' => 'Solicitar un servicio a otro usuario',
                'params' => ['servicio_id', 'mensaje', 'fecha_preferida'],
            ],
            'confirmar_intercambio' => [
                'description' => 'Confirmar que un intercambio se realizó',
                'params' => ['intercambio_id', 'horas_reales', 'valoracion'],
            ],
            'ver_mis_servicios' => [
                'description' => 'Ver servicios que he ofrecido',
                'params' => ['estado'],
            ],
            'ver_intercambios' => [
                'description' => 'Ver historial de intercambios',
                'params' => ['tipo', 'estado'],
            ],
            'cancelar_intercambio' => [
                'description' => 'Cancelar un intercambio pendiente',
                'params' => ['intercambio_id', 'motivo'],
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
     * Acción: Ver saldo de horas
     */
    private function action_ver_saldo($params) {
        $usuario_id = $params['usuario_id'] ?? get_current_user_id();

        if (!$usuario_id) {
            return [
                'success' => false,
                'error' => 'Usuario no identificado.',
            ];
        }

        global $wpdb;
        $tabla_transacciones = $wpdb->prefix . 'flavor_banco_tiempo_transacciones';

        // Calcular saldo
        $horas_ganadas = (float) $wpdb->get_var($wpdb->prepare(
            "SELECT IFNULL(SUM(horas), 0) FROM $tabla_transacciones
            WHERE usuario_receptor_id = %d AND estado = 'completado'",
            $usuario_id
        ));

        $horas_gastadas = (float) $wpdb->get_var($wpdb->prepare(
            "SELECT IFNULL(SUM(horas), 0) FROM $tabla_transacciones
            WHERE usuario_solicitante_id = %d AND estado = 'completado'",
            $usuario_id
        ));

        $saldo_actual = $horas_ganadas - $horas_gastadas;

        // Intercambios pendientes
        $pendientes = (int) $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM $tabla_transacciones
            WHERE (usuario_receptor_id = %d OR usuario_solicitante_id = %d)
            AND estado IN ('pendiente', 'aceptado')",
            $usuario_id,
            $usuario_id
        ));

        return [
            'success' => true,
            'saldo' => [
                'horas_ganadas' => $horas_ganadas,
                'horas_gastadas' => $horas_gastadas,
                'saldo_actual' => $saldo_actual,
                'intercambios_pendientes' => $pendientes,
                'mensaje' => sprintf(
                    'Tu saldo actual es de %.1f horas. Has ganado %.1f horas y gastado %.1f horas.',
                    $saldo_actual,
                    $horas_ganadas,
                    $horas_gastadas
                ),
            ],
        ];
    }

    /**
     * Acción: Buscar servicios
     */
    private function action_buscar_servicios($params) {
        global $wpdb;
        $tabla_servicios = $wpdb->prefix . 'flavor_banco_tiempo_servicios';

        $busqueda = $params['busqueda'] ?? '';
        $categoria = $params['categoria'] ?? '';
        $limite = absint($params['limite'] ?? 10);

        $where = ["estado = 'activo'"];
        $preparar_valores = [];

        if (!empty($busqueda)) {
            $where[] = "(titulo LIKE %s OR descripcion LIKE %s)";
            $busqueda_like = '%' . $wpdb->esc_like($busqueda) . '%';
            $preparar_valores[] = $busqueda_like;
            $preparar_valores[] = $busqueda_like;
        }

        if (!empty($categoria)) {
            $where[] = "categoria = %s";
            $preparar_valores[] = $categoria;
        }

        $sql_where = implode(' AND ', $where);
        $sql = "SELECT * FROM $tabla_servicios WHERE $sql_where ORDER BY fecha_publicacion DESC LIMIT %d";
        $preparar_valores[] = $limite;

        $servicios_encontrados = $wpdb->get_results($wpdb->prepare($sql, ...$preparar_valores));

        $servicios_formateados = array_map(function($servicio) {
            $usuario = get_userdata($servicio->usuario_id);
            return [
                'id' => $servicio->id,
                'titulo' => $servicio->titulo,
                'descripcion' => $servicio->descripcion,
                'categoria' => $servicio->categoria,
                'horas_estimadas' => floatval($servicio->horas_estimadas),
                'usuario' => [
                    'id' => $servicio->usuario_id,
                    'nombre' => $usuario ? $usuario->display_name : 'Usuario',
                ],
                'fecha_publicacion' => $servicio->fecha_publicacion,
            ];
        }, $servicios_encontrados);

        return [
            'success' => true,
            'total' => count($servicios_formateados),
            'servicios' => $servicios_formateados,
            'mensaje' => sprintf(
                'Se encontraron %d servicios%s.',
                count($servicios_formateados),
                !empty($busqueda) ? " para '$busqueda'" : ''
            ),
        ];
    }

    /**
     * Acción: Ofrecer servicio
     */
    private function action_ofrecer_servicio($params) {
        $usuario_id = get_current_user_id();

        if (!$usuario_id) {
            return [
                'success' => false,
                'error' => 'Debes iniciar sesión para ofrecer servicios.',
            ];
        }

        $titulo = sanitize_text_field($params['titulo'] ?? '');
        $descripcion = sanitize_textarea_field($params['descripcion'] ?? '');
        $categoria = sanitize_text_field($params['categoria'] ?? 'otros');
        $horas_estimadas = floatval($params['horas_estimadas'] ?? 1);

        if (empty($titulo) || empty($descripcion)) {
            return [
                'success' => false,
                'error' => 'El título y la descripción son obligatorios.',
            ];
        }

        global $wpdb;
        $tabla_servicios = $wpdb->prefix . 'flavor_banco_tiempo_servicios';

        $resultado = $wpdb->insert(
            $tabla_servicios,
            [
                'usuario_id' => $usuario_id,
                'titulo' => $titulo,
                'descripcion' => $descripcion,
                'categoria' => $categoria,
                'horas_estimadas' => $horas_estimadas,
                'estado' => 'activo',
                'fecha_publicacion' => current_time('mysql'),
            ],
            ['%d', '%s', '%s', '%s', '%f', '%s', '%s']
        );

        if ($resultado === false) {
            return [
                'success' => false,
                'error' => 'Error al publicar el servicio.',
            ];
        }

        return [
            'success' => true,
            'servicio_id' => $wpdb->insert_id,
            'mensaje' => "¡Servicio '$titulo' publicado con éxito! Ahora otros usuarios podrán solicitarlo.",
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function get_tool_definitions() {
        return [
            [
                'name' => 'banco_tiempo_ver_saldo',
                'description' => 'Consulta el saldo de horas del usuario en el banco de tiempo',
                'input_schema' => [
                    'type' => 'object',
                    'properties' => [
                        'usuario_id' => [
                            'type' => 'integer',
                            'description' => 'ID del usuario (opcional, por defecto el usuario actual)',
                        ],
                    ],
                ],
            ],
            [
                'name' => 'banco_tiempo_buscar_servicios',
                'description' => 'Busca servicios disponibles en el banco de tiempo',
                'input_schema' => [
                    'type' => 'object',
                    'properties' => [
                        'busqueda' => [
                            'type' => 'string',
                            'description' => 'Término de búsqueda',
                        ],
                        'categoria' => [
                            'type' => 'string',
                            'description' => 'Categoría del servicio',
                            'enum' => ['cuidados', 'educacion', 'bricolaje', 'tecnologia', 'transporte', 'otros'],
                        ],
                        'limite' => [
                            'type' => 'integer',
                            'description' => 'Número máximo de resultados',
                        ],
                    ],
                ],
            ],
            [
                'name' => 'banco_tiempo_ofrecer_servicio',
                'description' => 'Publica un nuevo servicio que el usuario puede ofrecer',
                'input_schema' => [
                    'type' => 'object',
                    'properties' => [
                        'titulo' => [
                            'type' => 'string',
                            'description' => 'Título del servicio',
                        ],
                        'descripcion' => [
                            'type' => 'string',
                            'description' => 'Descripción detallada del servicio',
                        ],
                        'categoria' => [
                            'type' => 'string',
                            'description' => 'Categoría del servicio',
                        ],
                        'horas_estimadas' => [
                            'type' => 'number',
                            'description' => 'Horas estimadas que tomará el servicio',
                        ],
                    ],
                    'required' => ['titulo', 'descripcion'],
                ],
            ],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function get_knowledge_base() {
        return <<<KNOWLEDGE
**Banco de Tiempo**

Un banco de tiempo es un sistema de intercambio de servicios donde el tiempo es la moneda.
Cada hora de servicio vale lo mismo, independientemente del tipo de servicio.

**Cómo funciona:**
1. Los usuarios ofrecen servicios que pueden realizar
2. Otros usuarios solicitan esos servicios
3. Al completarse el servicio, se transfieren horas entre usuarios
4. El tiempo que das ayudando a otros lo puedes usar para recibir ayuda

**Categorías de servicios:**
- Cuidados: niños, mayores, mascotas
- Educación: clases, idiomas, tutorías
- Bricolaje: reparaciones, mantenimiento
- Tecnología: informática, web, reparaciones
- Transporte: llevar a sitios, mudanzas
- Otros: cualquier otro servicio

**Importante:**
- Una hora siempre vale una hora, sin importar el servicio
- Los intercambios deben ser confirmados por ambas partes
- El sistema fomenta la reciprocidad y la comunidad
KNOWLEDGE;
    }

    /**
     * {@inheritdoc}
     */
    public function get_faqs() {
        return [
            [
                'pregunta' => '¿Cómo funciona el banco de tiempo?',
                'respuesta' => 'Es un sistema de intercambio donde ofreces tus habilidades y recibes servicios de otros. Cada hora vale lo mismo.',
            ],
            [
                'pregunta' => '¿Cómo puedo ganar horas?',
                'respuesta' => 'Ofreciendo servicios a otros usuarios. Cuando completes un servicio, recibirás las horas correspondientes.',
            ],
            [
                'pregunta' => '¿Puedo ofrecer cualquier servicio?',
                'respuesta' => 'Sí, puedes ofrecer cualquier habilidad o servicio que puedas realizar de forma segura y legal.',
            ],
        ];
    }

    /**
     * Componentes web del módulo
     */
    public function get_web_components() {
        return [
            'hero' => [
                'label' => __('Hero Banco de Tiempo', 'flavor-chat-ia'),
                'description' => __('Sección hero con estadísticas del banco de tiempo', 'flavor-chat-ia'),
                'category' => 'hero',
                'icon' => 'dashicons-clock',
                'fields' => [
                    'titulo' => [
                        'type' => 'text',
                        'label' => __('Título', 'flavor-chat-ia'),
                        'default' => __('Banco de Tiempo', 'flavor-chat-ia'),
                    ],
                    'subtitulo' => [
                        'type' => 'textarea',
                        'label' => __('Subtítulo', 'flavor-chat-ia'),
                        'default' => __('Intercambia habilidades con tu comunidad', 'flavor-chat-ia'),
                    ],
                    'mostrar_estadisticas' => [
                        'type' => 'toggle',
                        'label' => __('Mostrar estadísticas', 'flavor-chat-ia'),
                        'default' => true,
                    ],
                ],
                'template' => 'banco-tiempo/hero',
            ],
            'servicios_grid' => [
                'label' => __('Grid de Servicios', 'flavor-chat-ia'),
                'description' => __('Listado de servicios ofrecidos y demandados', 'flavor-chat-ia'),
                'category' => 'listings',
                'icon' => 'dashicons-grid-view',
                'fields' => [
                    'titulo' => [
                        'type' => 'text',
                        'label' => __('Título', 'flavor-chat-ia'),
                        'default' => __('Servicios Disponibles', 'flavor-chat-ia'),
                    ],
                    'tipo' => [
                        'type' => 'select',
                        'label' => __('Tipo', 'flavor-chat-ia'),
                        'options' => ['todos', 'ofertas', 'demandas'],
                        'default' => 'todos',
                    ],
                    'limite' => [
                        'type' => 'number',
                        'label' => __('Número máximo', 'flavor-chat-ia'),
                        'default' => 8,
                    ],
                ],
                'template' => 'banco-tiempo/servicios-grid',
            ],
            'como_funciona' => [
                'label' => __('Cómo Funciona', 'flavor-chat-ia'),
                'description' => __('Explicación del sistema de intercambio', 'flavor-chat-ia'),
                'category' => 'features',
                'icon' => 'dashicons-info',
                'fields' => [
                    'titulo' => [
                        'type' => 'text',
                        'label' => __('Título', 'flavor-chat-ia'),
                        'default' => __('¿Cómo funciona?', 'flavor-chat-ia'),
                    ],
                ],
                'template' => 'banco-tiempo/como-funciona',
            ],
            'categorias' => [
                'label' => __('Categorías de Servicios', 'flavor-chat-ia'),
                'description' => __('Grid de categorías disponibles', 'flavor-chat-ia'),
                'category' => 'listings',
                'icon' => 'dashicons-category',
                'fields' => [
                    'titulo' => [
                        'type' => 'text',
                        'label' => __('Título', 'flavor-chat-ia'),
                        'default' => __('Explora por Categoría', 'flavor-chat-ia'),
                    ],
                ],
                'template' => 'banco-tiempo/categorias',
            ],
        ];
    }
}

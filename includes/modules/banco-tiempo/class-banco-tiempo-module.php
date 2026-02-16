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

    use Flavor_Module_Admin_Pages_Trait;
    use Flavor_Module_Notifications_Trait;

    /**
     * Constructor
     */
    public function __construct() {
        $this->id = 'banco_tiempo';
        $this->name = 'Banco de Tiempo'; // Translation loaded on init
        $this->description = 'Sistema de intercambio de servicios por horas entre miembros.'; // Translation loaded on init

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
     * Verifica si el módulo está activo
     */
    public function is_active() {
        return $this->can_activate();
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
        add_action('init', [$this, 'maybe_create_pages']);
        add_action('flavor_banco_tiempo_servicio_completado', [$this, 'on_servicio_completado'], 10, 2);

        // Registrar en Panel Unificado de Gestión
        $this->registrar_en_panel_unificado();

        // Cargar funcionalidades del Sello de Conciencia (+3 pts)
        $this->cargar_funcionalidades_conciencia();
    }

    /**
     * Carga las funcionalidades del Sello de Conciencia
     * Sistema de Reputación, Integración Solidaria y Dashboard de Sostenibilidad
     */
    private function cargar_funcionalidades_conciencia() {
        $archivo_conciencia = dirname(__FILE__) . '/class-bt-conciencia-features.php';

        if (file_exists($archivo_conciencia)) {
            require_once $archivo_conciencia;

            if (class_exists('Flavor_BT_Conciencia_Features')) {
                Flavor_BT_Conciencia_Features::get_instance();
            }
        }
    }

    /**
     * Crea las tablas si no existen
     */
    public function maybe_create_tables() {
        global $wpdb;

        $db_version = get_option('flavor_banco_tiempo_db_version', '0');
        $current_version = '4.2.0';

        // Si la versión es antigua, crear/actualizar todas las tablas
        if (version_compare($db_version, $current_version, '<')) {
            $this->create_tables();
            update_option('flavor_banco_tiempo_db_version', $current_version);
        }
    }

    /**
     * Crea/actualiza páginas de banco de tiempo si es necesario
     */
    public function maybe_create_pages() {
        if (!class_exists('Flavor_Page_Creator')) {
            return;
        }

        // En admin: refrescar páginas del módulo
        if (is_admin()) {
            Flavor_Page_Creator::refresh_module_pages('banco_tiempo');
            return;
        }

        // En frontend: crear páginas si no existen (solo una vez)
        $pagina = get_page_by_path('banco-tiempo');
        if (!$pagina && !get_option('flavor_banco_tiempo_pages_created')) {
            Flavor_Page_Creator::create_pages_for_modules(['banco_tiempo']);
            update_option('flavor_banco_tiempo_pages_created', 1, false);
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
        $tabla_reputacion = $wpdb->prefix . 'flavor_banco_tiempo_reputacion';
        $tabla_donaciones = $wpdb->prefix . 'flavor_banco_tiempo_donaciones';
        $tabla_metricas = $wpdb->prefix . 'flavor_banco_tiempo_metricas';
        $tabla_valoraciones = $wpdb->prefix . 'flavor_banco_tiempo_valoraciones';
        $tabla_limites = $wpdb->prefix . 'flavor_banco_tiempo_limites';

        $sql_servicios = "CREATE TABLE $tabla_servicios (
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

        $sql_transacciones = "CREATE TABLE $tabla_transacciones (
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
            motivo_cancelacion text DEFAULT NULL,
            fecha_solicitud datetime DEFAULT CURRENT_TIMESTAMP,
            fecha_aceptacion datetime DEFAULT NULL,
            fecha_completado datetime DEFAULT NULL,
            fecha_cancelacion datetime DEFAULT NULL,
            PRIMARY KEY (id),
            KEY servicio_id (servicio_id),
            KEY usuario_solicitante_id (usuario_solicitante_id),
            KEY usuario_receptor_id (usuario_receptor_id),
            KEY estado (estado),
            KEY fecha_solicitud (fecha_solicitud)
        ) $charset_collate;";

        $sql_reputacion = "CREATE TABLE $tabla_reputacion (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            usuario_id bigint(20) unsigned NOT NULL,
            total_intercambios_completados int(11) DEFAULT 0,
            total_horas_dadas decimal(8,2) DEFAULT 0.00,
            total_horas_recibidas decimal(8,2) DEFAULT 0.00,
            rating_promedio decimal(3,2) DEFAULT 0.00,
            rating_puntualidad decimal(3,2) DEFAULT 0.00,
            rating_calidad decimal(3,2) DEFAULT 0.00,
            rating_comunicacion decimal(3,2) DEFAULT 0.00,
            fecha_primer_intercambio datetime DEFAULT NULL,
            fecha_ultimo_intercambio datetime DEFAULT NULL,
            estado_verificacion enum('pendiente','verificado','destacado','mentor') DEFAULT 'pendiente',
            fecha_verificacion datetime DEFAULT NULL,
            verificado_por bigint(20) unsigned DEFAULT NULL,
            badges longtext DEFAULT NULL,
            nivel int(3) DEFAULT 1,
            puntos_confianza int(11) DEFAULT 0,
            PRIMARY KEY (id),
            UNIQUE KEY usuario_id (usuario_id),
            KEY estado_verificacion (estado_verificacion),
            KEY rating_promedio (rating_promedio),
            KEY nivel (nivel)
        ) $charset_collate;";

        $sql_donaciones = "CREATE TABLE $tabla_donaciones (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            donante_id bigint(20) unsigned NOT NULL,
            beneficiario_id bigint(20) unsigned DEFAULT NULL,
            tipo enum('fondo_comunitario','regalo_directo','emergencia') DEFAULT 'fondo_comunitario',
            horas decimal(6,2) NOT NULL,
            motivo varchar(255) DEFAULT NULL,
            mensaje text DEFAULT NULL,
            estado enum('pendiente','aceptada','rechazada','utilizada') DEFAULT 'pendiente',
            fecha_donacion datetime DEFAULT NULL,
            fecha_utilizacion datetime DEFAULT NULL,
            utilizada_por bigint(20) unsigned DEFAULT NULL,
            transaccion_origen_id bigint(20) unsigned DEFAULT NULL,
            PRIMARY KEY (id),
            KEY donante_id (donante_id),
            KEY beneficiario_id (beneficiario_id),
            KEY tipo (tipo),
            KEY estado (estado),
            KEY fecha_donacion (fecha_donacion)
        ) $charset_collate;";

        $sql_metricas = "CREATE TABLE $tabla_metricas (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            periodo_inicio date NOT NULL,
            periodo_fin date NOT NULL,
            tipo_periodo enum('diario','semanal','mensual','trimestral','anual') DEFAULT 'mensual',
            total_usuarios_activos int(11) DEFAULT 0,
            nuevos_usuarios int(11) DEFAULT 0,
            total_intercambios int(11) DEFAULT 0,
            total_horas_intercambiadas decimal(10,2) DEFAULT 0.00,
            horas_donadas_periodo decimal(10,2) DEFAULT 0.00,
            fondo_comunitario_actual decimal(10,2) DEFAULT 0.00,
            indice_equidad decimal(5,4) DEFAULT 0.00,
            categoria_mas_demandada varchar(50) DEFAULT NULL,
            categoria_menos_demandada varchar(50) DEFAULT NULL,
            ratio_oferta_demanda longtext DEFAULT NULL,
            alertas_generadas longtext DEFAULT NULL,
            usuarios_con_deuda_alta int(11) DEFAULT 0,
            usuarios_con_excedente_alto int(11) DEFAULT 0,
            puntuacion_sostenibilidad int(3) DEFAULT 0,
            datos_detalle longtext DEFAULT NULL,
            fecha_calculo datetime DEFAULT NULL,
            PRIMARY KEY (id),
            KEY periodo_inicio (periodo_inicio),
            KEY tipo_periodo (tipo_periodo),
            KEY puntuacion_sostenibilidad (puntuacion_sostenibilidad)
        ) $charset_collate;";

        $sql_valoraciones = "CREATE TABLE $tabla_valoraciones (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            transaccion_id bigint(20) unsigned NOT NULL,
            valorador_id bigint(20) unsigned NOT NULL,
            valorado_id bigint(20) unsigned NOT NULL,
            rol_valorador enum('solicitante','receptor') NOT NULL,
            rating_general tinyint(1) NOT NULL,
            rating_puntualidad tinyint(1) DEFAULT NULL,
            rating_calidad tinyint(1) DEFAULT NULL,
            rating_comunicacion tinyint(1) DEFAULT NULL,
            comentario text DEFAULT NULL,
            es_publica tinyint(1) DEFAULT 1,
            fecha_valoracion datetime DEFAULT NULL,
            PRIMARY KEY (id),
            UNIQUE KEY transaccion_valorador (transaccion_id, valorador_id),
            KEY valorado_id (valorado_id),
            KEY rating_general (rating_general),
            KEY fecha_valoracion (fecha_valoracion)
        ) $charset_collate;";

        $sql_limites = "CREATE TABLE $tabla_limites (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            usuario_id bigint(20) unsigned NOT NULL,
            saldo_actual decimal(8,2) DEFAULT 0.00,
            limite_deuda decimal(8,2) DEFAULT -20.00,
            limite_acumulacion decimal(8,2) DEFAULT 100.00,
            alerta_activa tinyint(1) DEFAULT 0,
            tipo_alerta varchar(50) DEFAULT NULL,
            fecha_ultima_alerta datetime DEFAULT NULL,
            en_plan_equilibrio tinyint(1) DEFAULT 0,
            notas_plan text DEFAULT NULL,
            fecha_actualizacion datetime DEFAULT NULL,
            PRIMARY KEY (id),
            UNIQUE KEY usuario_id (usuario_id),
            KEY saldo_actual (saldo_actual),
            KEY alerta_activa (alerta_activa)
        ) $charset_collate;";

        require_once ABSPATH . 'wp-admin/includes/upgrade.php';
        dbDelta($sql_servicios);
        dbDelta($sql_transacciones);
        dbDelta($sql_reputacion);
        dbDelta($sql_donaciones);
        dbDelta($sql_metricas);
        dbDelta($sql_valoraciones);
        dbDelta($sql_limites);
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
            'crear_servicio' => [
                'description' => 'Crear un nuevo servicio (alias de ofrecer_servicio)',
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
        // Alias: crear_servicio es lo mismo que ofrecer_servicio
        if ($action_name === 'crear_servicio') {
            $action_name = 'ofrecer_servicio';
        }

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
                'error' => __('Debes iniciar sesión para ver tu saldo.', 'flavor-chat-ia'),
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
                'error' => __('Debes iniciar sesión para ofrecer un servicio.', 'flavor-chat-ia'),
            ];
        }

        $titulo = sanitize_text_field($params['titulo'] ?? '');
        $descripcion = sanitize_textarea_field($params['descripcion'] ?? '');
        $categoria = sanitize_text_field($params['categoria'] ?? 'otros');
        $horas_estimadas = floatval($params['horas_estimadas'] ?? 1);

        if (empty($titulo) || empty($descripcion)) {
            return [
                'success' => false,
                'error' => __('El título y la descripción son obligatorios.', 'flavor-chat-ia'),
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
                'error' => __('Error al crear el servicio. Por favor, inténtalo de nuevo.', 'flavor-chat-ia'),
            ];
        }

        return [
            'success' => true,
            'servicio_id' => $wpdb->insert_id,
            'mensaje' => sprintf(
                /* translators: %s: título del servicio */
                __('¡Servicio "%s" publicado con éxito! Ahora otros usuarios podrán solicitarlo.', 'flavor-chat-ia'),
                $titulo
            ),
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
     * Configuración de formularios del módulo
     */
    public function get_form_config($action_name) {
        $settings = $this->get_default_settings();

        $configs = [
            'ofrecer_servicio' => [
                'title' => __('Ofrecer un Servicio', 'flavor-chat-ia'),
                'description' => __('Publica un servicio que puedes ofrecer a la comunidad', 'flavor-chat-ia'),
                'fields' => [
                    'titulo' => [
                        'type' => 'text',
                        'label' => __('Título del servicio', 'flavor-chat-ia'),
                        'required' => true,
                        'placeholder' => __('Ej: Clases de guitarra', 'flavor-chat-ia'),
                    ],
                    'descripcion' => [
                        'type' => 'textarea',
                        'label' => __('Descripción', 'flavor-chat-ia'),
                        'required' => true,
                        'rows' => 5,
                        'placeholder' => __('Describe tu servicio en detalle...', 'flavor-chat-ia'),
                    ],
                    'categoria' => [
                        'type' => 'select',
                        'label' => __('Categoría', 'flavor-chat-ia'),
                        'required' => true,
                        'options' => $settings['categorias_servicios'],
                    ],
                    'horas_estimadas' => [
                        'type' => 'number',
                        'label' => __('Horas estimadas', 'flavor-chat-ia'),
                        'required' => true,
                        'min' => $settings['hora_minima_intercambio'],
                        'max' => $settings['hora_maxima_intercambio'],
                        'step' => 0.5,
                        'default' => 1,
                        'description' => sprintf(
                            __('Mínimo %s horas, máximo %s horas', 'flavor-chat-ia'),
                            $settings['hora_minima_intercambio'],
                            $settings['hora_maxima_intercambio']
                        ),
                    ],
                    'disponibilidad' => [
                        'type' => 'textarea',
                        'label' => __('Disponibilidad', 'flavor-chat-ia'),
                        'rows' => 3,
                        'placeholder' => __('Ej: Tardes entre semana, fines de semana...', 'flavor-chat-ia'),
                    ],
                ],
                'submit_text' => __('Publicar Servicio', 'flavor-chat-ia'),
            ],
            'solicitar_servicio' => [
                'title' => __('Solicitar un Servicio', 'flavor-chat-ia'),
                'description' => __('Solicita este servicio al usuario que lo ofrece', 'flavor-chat-ia'),
                'fields' => [
                    'servicio_id' => [
                        'type' => 'hidden',
                        'required' => true,
                    ],
                    'mensaje' => [
                        'type' => 'textarea',
                        'label' => __('Mensaje', 'flavor-chat-ia'),
                        'required' => true,
                        'rows' => 4,
                        'placeholder' => __('Explica qué necesitas y cuándo...', 'flavor-chat-ia'),
                    ],
                    'fecha_preferida' => [
                        'type' => 'date',
                        'label' => __('Fecha preferida', 'flavor-chat-ia'),
                        'description' => __('¿Cuándo necesitas este servicio?', 'flavor-chat-ia'),
                    ],
                    'horas_solicitadas' => [
                        'type' => 'number',
                        'label' => __('Horas solicitadas', 'flavor-chat-ia'),
                        'min' => $settings['hora_minima_intercambio'],
                        'max' => $settings['hora_maxima_intercambio'],
                        'step' => 0.5,
                        'default' => 1,
                    ],
                ],
                'submit_text' => __('Enviar Solicitud', 'flavor-chat-ia'),
            ],
            'crear_servicio' => [
                'title' => __('Crear Servicio', 'flavor-chat-ia'),
                'description' => __('Publica un nuevo servicio que puedes ofrecer', 'flavor-chat-ia'),
                'fields' => [
                    'titulo' => [
                        'type' => 'text',
                        'label' => __('Título del servicio', 'flavor-chat-ia'),
                        'required' => true,
                    ],
                    'descripcion' => [
                        'type' => 'textarea',
                        'label' => __('Descripción', 'flavor-chat-ia'),
                        'required' => true,
                        'rows' => 5,
                    ],
                    'categoria' => [
                        'type' => 'select',
                        'label' => __('Categoría', 'flavor-chat-ia'),
                        'required' => true,
                        'options' => $settings['categorias_servicios'],
                    ],
                    'horas_estimadas' => [
                        'type' => 'number',
                        'label' => __('Horas estimadas', 'flavor-chat-ia'),
                        'required' => true,
                        'min' => $settings['hora_minima_intercambio'],
                        'max' => $settings['hora_maxima_intercambio'],
                        'step' => 0.5,
                        'default' => 1,
                    ],
                ],
                'submit_text' => __('Publicar Servicio', 'flavor-chat-ia'),
            ],
        ];

        // Alias: crear_servicio es lo mismo que ofrecer_servicio
        if ($action_name === 'crear_servicio' && !isset($configs['crear_servicio'])) {
            $action_name = 'ofrecer_servicio';
        }

        return $configs[$action_name] ?? [];
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

    // ─── Panel Unificado de Gestión ─────────────────────────────

    /**
     * Configuración para el Panel Unificado de Gestión
     *
     * @return array Configuración del módulo
     */
    protected function get_admin_config() {
        return [
            'id' => 'banco_tiempo',
            'label' => __('Banco de Tiempo', 'flavor-chat-ia'),
            'icon' => 'dashicons-backup',
            'capability' => 'manage_options',
            'categoria' => 'comunidad',
            'paginas' => [
                [
                    'slug' => 'banco-tiempo-dashboard',
                    'titulo' => __('Dashboard', 'flavor-chat-ia'),
                    'callback' => [$this, 'render_admin_dashboard'],
                ],
                [
                    'slug' => 'banco-tiempo-intercambios',
                    'titulo' => __('Intercambios', 'flavor-chat-ia'),
                    'callback' => [$this, 'render_admin_intercambios'],
                    'badge' => [$this, 'contar_intercambios_pendientes'],
                ],
                [
                    'slug' => 'banco-tiempo-miembros',
                    'titulo' => __('Miembros', 'flavor-chat-ia'),
                    'callback' => [$this, 'render_admin_miembros'],
                ],
                [
                    'slug' => 'banco-tiempo-servicios',
                    'titulo' => __('Servicios', 'flavor-chat-ia'),
                    'callback' => [$this, 'render_admin_servicios'],
                ],
                [
                    'slug' => 'banco-tiempo-config',
                    'titulo' => __('Configuración', 'flavor-chat-ia'),
                    'callback' => [$this, 'render_admin_config'],
                ],
            ],
            'estadisticas' => [$this, 'get_estadisticas_dashboard'],
        ];
    }

    /**
     * Cuenta intercambios pendientes
     *
     * @return int
     */
    public function contar_intercambios_pendientes() {
        // Verificar que el módulo esté activo
        if (!$this->can_activate()) {
            return 0;
        }

        global $wpdb;
        $tabla_transacciones = $wpdb->prefix . 'flavor_banco_tiempo_transacciones';

        if (!Flavor_Chat_Helpers::tabla_existe($tabla_transacciones)) {
            return 0;
        }

        return (int) $wpdb->get_var(
            "SELECT COUNT(*) FROM $tabla_transacciones WHERE estado IN ('pendiente', 'aceptado')"
        );
    }

    /**
     * Estadísticas para el dashboard unificado
     *
     * @return array
     */
    public function get_estadisticas_dashboard() {
        global $wpdb;
        $tabla_servicios = $wpdb->prefix . 'flavor_banco_tiempo_servicios';
        $tabla_transacciones = $wpdb->prefix . 'flavor_banco_tiempo_transacciones';
        $estadisticas = [];

        if (!Flavor_Chat_Helpers::tabla_existe($tabla_transacciones)) {
            return $estadisticas;
        }

        // Intercambios activos (pendientes o en curso)
        $intercambios_activos = (int) $wpdb->get_var(
            "SELECT COUNT(*) FROM $tabla_transacciones WHERE estado IN ('pendiente', 'aceptado', 'en_curso')"
        );
        $estadisticas[] = [
            'icon' => 'dashicons-update',
            'valor' => $intercambios_activos,
            'label' => __('Intercambios activos', 'flavor-chat-ia'),
            'color' => $intercambios_activos > 0 ? 'blue' : 'gray',
            'enlace' => admin_url('admin.php?page=banco-tiempo-intercambios'),
        ];

        // Total de miembros participantes
        $total_miembros = (int) $wpdb->get_var(
            "SELECT COUNT(DISTINCT usuario_id) FROM $tabla_servicios"
        );
        $estadisticas[] = [
            'icon' => 'dashicons-groups',
            'valor' => $total_miembros,
            'label' => __('Miembros', 'flavor-chat-ia'),
            'color' => $total_miembros > 0 ? 'green' : 'gray',
            'enlace' => admin_url('admin.php?page=banco-tiempo-miembros'),
        ];

        return $estadisticas;
    }

    /**
     * Renderiza el dashboard del banco de tiempo
     */
    public function render_admin_dashboard() {
        global $wpdb;
        $tabla_servicios = $wpdb->prefix . 'flavor_banco_tiempo_servicios';
        $tabla_transacciones = $wpdb->prefix . 'flavor_banco_tiempo_transacciones';

        echo '<div class="wrap flavor-modulo-page">';
        $this->render_page_header(__('Dashboard de Banco de Tiempo', 'flavor-chat-ia'), [
            ['label' => __('Nuevo Servicio', 'flavor-chat-ia'), 'url' => '#', 'class' => 'button-primary'],
        ]);

        // Estadísticas generales
        $total_servicios = Flavor_Chat_Helpers::tabla_existe($tabla_servicios)
            ? (int) $wpdb->get_var("SELECT COUNT(*) FROM $tabla_servicios WHERE estado = 'activo'")
            : 0;

        $total_intercambios = Flavor_Chat_Helpers::tabla_existe($tabla_transacciones)
            ? (int) $wpdb->get_var("SELECT COUNT(*) FROM $tabla_transacciones WHERE estado = 'completado'")
            : 0;

        $horas_intercambiadas = Flavor_Chat_Helpers::tabla_existe($tabla_transacciones)
            ? (float) $wpdb->get_var("SELECT COALESCE(SUM(horas), 0) FROM $tabla_transacciones WHERE estado = 'completado'")
            : 0;

        $intercambios_pendientes = $this->contar_intercambios_pendientes();

        echo '<div class="flavor-stats-grid">';
        echo '<div class="flavor-stat-card"><span class="stat-number">' . esc_html($total_servicios) . '</span><span class="stat-label">' . __('Servicios Activos', 'flavor-chat-ia') . '</span></div>';
        echo '<div class="flavor-stat-card"><span class="stat-number">' . esc_html($total_intercambios) . '</span><span class="stat-label">' . __('Intercambios Completados', 'flavor-chat-ia') . '</span></div>';
        echo '<div class="flavor-stat-card"><span class="stat-number">' . esc_html(number_format($horas_intercambiadas, 1)) . 'h</span><span class="stat-label">' . __('Horas Intercambiadas', 'flavor-chat-ia') . '</span></div>';
        echo '<div class="flavor-stat-card"><span class="stat-number">' . esc_html($intercambios_pendientes) . '</span><span class="stat-label">' . __('Pendientes', 'flavor-chat-ia') . '</span></div>';
        echo '</div>';

        echo '<p>' . __('El Banco de Tiempo es un sistema de intercambio de servicios donde el tiempo es la moneda. Cada hora vale lo mismo, independientemente del tipo de servicio.', 'flavor-chat-ia') . '</p>';
        echo '</div>';
    }

    /**
     * Renderiza la página de intercambios
     */
    public function render_admin_intercambios() {
        global $wpdb;
        $tabla_transacciones = $wpdb->prefix . 'flavor_banco_tiempo_transacciones';
        $tabla_servicios = $wpdb->prefix . 'flavor_banco_tiempo_servicios';

        echo '<div class="wrap flavor-modulo-page">';
        $this->render_page_header(__('Gestión de Intercambios', 'flavor-chat-ia'));

        if (!Flavor_Chat_Helpers::tabla_existe($tabla_transacciones)) {
            echo '<p>' . __('Las tablas no están creadas.', 'flavor-chat-ia') . '</p>';
            echo '</div>';
            return;
        }

        $intercambios = $wpdb->get_results(
            "SELECT t.*, s.titulo as servicio_titulo
             FROM $tabla_transacciones t
             LEFT JOIN $tabla_servicios s ON t.servicio_id = s.id
             ORDER BY t.fecha_solicitud DESC
             LIMIT 50",
            ARRAY_A
        );

        if (!empty($intercambios)) {
            echo '<table class="wp-list-table widefat fixed striped">';
            echo '<thead><tr>';
            echo '<th>' . __('Servicio', 'flavor-chat-ia') . '</th>';
            echo '<th>' . __('Solicitante', 'flavor-chat-ia') . '</th>';
            echo '<th>' . __('Receptor', 'flavor-chat-ia') . '</th>';
            echo '<th>' . __('Horas', 'flavor-chat-ia') . '</th>';
            echo '<th>' . __('Estado', 'flavor-chat-ia') . '</th>';
            echo '<th>' . __('Fecha', 'flavor-chat-ia') . '</th>';
            echo '<th>' . __('Acciones', 'flavor-chat-ia') . '</th>';
            echo '</tr></thead>';
            echo '<tbody>';

            foreach ($intercambios as $intercambio) {
                $solicitante = get_userdata($intercambio['usuario_solicitante_id']);
                $receptor = get_userdata($intercambio['usuario_receptor_id']);
                $clase_estado = $this->obtener_clase_estado_intercambio($intercambio['estado']);

                echo '<tr>';
                echo '<td><strong>' . esc_html($intercambio['servicio_titulo'] ?? __('Servicio eliminado', 'flavor-chat-ia')) . '</strong></td>';
                echo '<td>' . esc_html($solicitante ? $solicitante->display_name : __('Usuario', 'flavor-chat-ia')) . '</td>';
                echo '<td>' . esc_html($receptor ? $receptor->display_name : __('Usuario', 'flavor-chat-ia')) . '</td>';
                echo '<td>' . esc_html(number_format((float)$intercambio['horas'], 1)) . 'h</td>';
                echo '<td><span class="' . esc_attr($clase_estado) . '">' . esc_html(ucfirst($intercambio['estado'])) . '</span></td>';
                echo '<td>' . esc_html(date_i18n('d/m/Y H:i', strtotime($intercambio['fecha_solicitud']))) . '</td>';
                echo '<td><a href="#" class="button button-small">' . __('Ver', 'flavor-chat-ia') . '</a></td>';
                echo '</tr>';
            }

            echo '</tbody></table>';
        } else {
            echo '<p>' . __('No hay intercambios registrados.', 'flavor-chat-ia') . '</p>';
        }

        echo '</div>';
    }

    /**
     * Renderiza la página de miembros
     */
    public function render_admin_miembros() {
        global $wpdb;
        $tabla_servicios = $wpdb->prefix . 'flavor_banco_tiempo_servicios';
        $tabla_transacciones = $wpdb->prefix . 'flavor_banco_tiempo_transacciones';

        echo '<div class="wrap flavor-modulo-page">';
        $this->render_page_header(__('Miembros del Banco de Tiempo', 'flavor-chat-ia'));

        if (!Flavor_Chat_Helpers::tabla_existe($tabla_servicios)) {
            echo '<p>' . __('Las tablas no están creadas.', 'flavor-chat-ia') . '</p>';
            echo '</div>';
            return;
        }

        // Obtener usuarios únicos que participan en el banco de tiempo
        $miembros = $wpdb->get_results(
            "SELECT usuario_id, COUNT(*) as total_servicios
             FROM $tabla_servicios
             GROUP BY usuario_id
             ORDER BY total_servicios DESC
             LIMIT 50",
            ARRAY_A
        );

        if (!empty($miembros)) {
            echo '<table class="wp-list-table widefat fixed striped">';
            echo '<thead><tr>';
            echo '<th>' . __('Usuario', 'flavor-chat-ia') . '</th>';
            echo '<th>' . __('Servicios Ofrecidos', 'flavor-chat-ia') . '</th>';
            echo '<th>' . __('Horas Dadas', 'flavor-chat-ia') . '</th>';
            echo '<th>' . __('Horas Recibidas', 'flavor-chat-ia') . '</th>';
            echo '<th>' . __('Saldo', 'flavor-chat-ia') . '</th>';
            echo '<th>' . __('Acciones', 'flavor-chat-ia') . '</th>';
            echo '</tr></thead>';
            echo '<tbody>';

            foreach ($miembros as $miembro) {
                $usuario = get_userdata($miembro['usuario_id']);

                // Calcular saldo
                $horas_dadas = (float) $wpdb->get_var($wpdb->prepare(
                    "SELECT COALESCE(SUM(horas), 0) FROM $tabla_transacciones
                     WHERE usuario_receptor_id = %d AND estado = 'completado'",
                    $miembro['usuario_id']
                ));

                $horas_recibidas = (float) $wpdb->get_var($wpdb->prepare(
                    "SELECT COALESCE(SUM(horas), 0) FROM $tabla_transacciones
                     WHERE usuario_solicitante_id = %d AND estado = 'completado'",
                    $miembro['usuario_id']
                ));

                $saldo = $horas_dadas - $horas_recibidas;
                $clase_saldo = $saldo >= 0 ? 'status-positive' : 'status-negative';

                echo '<tr>';
                echo '<td><strong>' . esc_html($usuario ? $usuario->display_name : __('Usuario', 'flavor-chat-ia')) . '</strong></td>';
                echo '<td>' . esc_html($miembro['total_servicios']) . '</td>';
                echo '<td>' . esc_html(number_format($horas_dadas, 1)) . 'h</td>';
                echo '<td>' . esc_html(number_format($horas_recibidas, 1)) . 'h</td>';
                echo '<td><span class="' . esc_attr($clase_saldo) . '">' . esc_html(($saldo >= 0 ? '+' : '') . number_format($saldo, 1)) . 'h</span></td>';
                echo '<td><a href="#" class="button button-small">' . __('Ver Perfil', 'flavor-chat-ia') . '</a></td>';
                echo '</tr>';
            }

            echo '</tbody></table>';
        } else {
            echo '<p>' . __('No hay miembros registrados en el banco de tiempo.', 'flavor-chat-ia') . '</p>';
        }

        echo '</div>';
    }

    /**
     * Renderiza la página de servicios
     */
    public function render_admin_servicios() {
        global $wpdb;
        $tabla_servicios = $wpdb->prefix . 'flavor_banco_tiempo_servicios';

        echo '<div class="wrap flavor-modulo-page">';
        $this->render_page_header(__('Servicios del Banco de Tiempo', 'flavor-chat-ia'), [
            ['label' => __('Nuevo Servicio', 'flavor-chat-ia'), 'url' => '#', 'class' => 'button-primary'],
        ]);

        if (!Flavor_Chat_Helpers::tabla_existe($tabla_servicios)) {
            echo '<p>' . __('Las tablas no están creadas.', 'flavor-chat-ia') . '</p>';
            echo '</div>';
            return;
        }

        $servicios = $wpdb->get_results(
            "SELECT * FROM $tabla_servicios ORDER BY fecha_publicacion DESC LIMIT 50",
            ARRAY_A
        );

        if (!empty($servicios)) {
            echo '<table class="wp-list-table widefat fixed striped">';
            echo '<thead><tr>';
            echo '<th>' . __('Título', 'flavor-chat-ia') . '</th>';
            echo '<th>' . __('Usuario', 'flavor-chat-ia') . '</th>';
            echo '<th>' . __('Categoría', 'flavor-chat-ia') . '</th>';
            echo '<th>' . __('Horas Est.', 'flavor-chat-ia') . '</th>';
            echo '<th>' . __('Estado', 'flavor-chat-ia') . '</th>';
            echo '<th>' . __('Fecha', 'flavor-chat-ia') . '</th>';
            echo '<th>' . __('Acciones', 'flavor-chat-ia') . '</th>';
            echo '</tr></thead>';
            echo '<tbody>';

            foreach ($servicios as $servicio) {
                $usuario = get_userdata($servicio['usuario_id']);
                $clase_estado = $servicio['estado'] === 'activo' ? 'status-active' : 'status-inactive';

                echo '<tr>';
                echo '<td><strong>' . esc_html($servicio['titulo']) . '</strong></td>';
                echo '<td>' . esc_html($usuario ? $usuario->display_name : __('Usuario', 'flavor-chat-ia')) . '</td>';
                echo '<td>' . esc_html(ucfirst($servicio['categoria'])) . '</td>';
                echo '<td>' . esc_html(number_format((float)$servicio['horas_estimadas'], 1)) . 'h</td>';
                echo '<td><span class="' . esc_attr($clase_estado) . '">' . esc_html(ucfirst($servicio['estado'])) . '</span></td>';
                echo '<td>' . esc_html(date_i18n('d/m/Y', strtotime($servicio['fecha_publicacion']))) . '</td>';
                echo '<td><a href="#" class="button button-small">' . __('Editar', 'flavor-chat-ia') . '</a></td>';
                echo '</tr>';
            }

            echo '</tbody></table>';
        } else {
            echo '<p>' . __('No hay servicios publicados.', 'flavor-chat-ia') . '</p>';
        }

        echo '</div>';
    }

    /**
     * Renderiza la configuración del módulo
     */
    public function render_admin_config() {
        echo '<div class="wrap flavor-modulo-page">';
        $this->render_page_header(__('Configuración de Banco de Tiempo', 'flavor-chat-ia'));

        $configuracion_actual = $this->get_default_settings();

        echo '<form method="post" action="">';
        echo '<table class="form-table">';

        echo '<tr><th scope="row"><label for="hora_minima_intercambio">' . __('Horas mínimas por intercambio', 'flavor-chat-ia') . '</label></th>';
        echo '<td><input type="number" name="hora_minima_intercambio" id="hora_minima_intercambio" value="' . esc_attr($configuracion_actual['hora_minima_intercambio']) . '" step="0.5" min="0.5" class="small-text" />';
        echo '<p class="description">' . __('Tiempo mínimo para un intercambio (en horas).', 'flavor-chat-ia') . '</p></td></tr>';

        echo '<tr><th scope="row"><label for="hora_maxima_intercambio">' . __('Horas máximas por intercambio', 'flavor-chat-ia') . '</label></th>';
        echo '<td><input type="number" name="hora_maxima_intercambio" id="hora_maxima_intercambio" value="' . esc_attr($configuracion_actual['hora_maxima_intercambio']) . '" step="0.5" min="1" class="small-text" />';
        echo '<p class="description">' . __('Tiempo máximo para un solo intercambio (en horas).', 'flavor-chat-ia') . '</p></td></tr>';

        echo '<tr><th scope="row"><label for="requiere_validacion">' . __('Requiere validación', 'flavor-chat-ia') . '</label></th>';
        echo '<td><input type="checkbox" name="requiere_validacion" id="requiere_validacion" ' . checked($configuracion_actual['requiere_validacion'], true, false) . ' />';
        echo '<p class="description">' . __('Los intercambios requieren validación de un administrador.', 'flavor-chat-ia') . '</p></td></tr>';

        echo '</table>';
        echo '<p class="submit"><input type="submit" name="guardar_config" class="button-primary" value="' . __('Guardar Configuración', 'flavor-chat-ia') . '" /></p>';
        echo '</form>';
        echo '</div>';
    }

    /**
     * Obtiene la clase CSS según el estado del intercambio
     *
     * @param string $estado Estado del intercambio
     * @return string Clase CSS
     */
    private function obtener_clase_estado_intercambio($estado) {
        $clases = [
            'pendiente' => 'status-pending',
            'aceptado' => 'status-accepted',
            'en_curso' => 'status-in-progress',
            'completado' => 'status-completed',
            'cancelado' => 'status-cancelled',
            'rechazado' => 'status-rejected',
        ];
        return $clases[$estado] ?? 'status-default';
    }

    /**
     * Define las páginas del módulo (Page Creator V3)
     *
     * @return array Definiciones de páginas
     */
    public function get_pages_definition() {
        return [
            // Página principal
            [
                'title' => __('Banco de Tiempo', 'flavor-chat-ia'),
                'slug' => 'banco-tiempo',
                'content' => '<h1>' . __('Banco de Tiempo', 'flavor-chat-ia') . '</h1>
<p>' . __('Intercambia servicios y tiempo con tu comunidad', 'flavor-chat-ia') . '</p>

[flavor_module_listing module="banco_tiempo" action="listar_servicios" columnas="3" limite="12"]',
                'parent' => 0,
            ],
            // Ofrecer servicio
            [
                'title' => __('Ofrecer Servicio', 'flavor-chat-ia'),
                'slug' => 'ofrecer',
                'content' => '<h1>' . __('Ofrecer un Servicio', 'flavor-chat-ia') . '</h1>
<p>' . __('Comparte tus habilidades con la comunidad', 'flavor-chat-ia') . '</p>

[flavor_module_form module="banco_tiempo" action="crear_servicio"]',
                'parent' => 'banco-tiempo',
            ],
            // Solicitar servicio
            [
                'title' => __('Solicitar Servicio', 'flavor-chat-ia'),
                'slug' => 'solicitar',
                'content' => '<h1>' . __('Solicitar un Servicio', 'flavor-chat-ia') . '</h1>

[flavor_module_form module="banco_tiempo" action="solicitar_servicio"]',
                'parent' => 'banco-tiempo',
            ],
            // Mis intercambios
            [
                'title' => __('Mis Intercambios', 'flavor-chat-ia'),
                'slug' => 'mis-intercambios',
                'content' => '<h1>' . __('Mis Intercambios', 'flavor-chat-ia') . '</h1>

[flavor_module_dashboard module="banco_tiempo"]',
                'parent' => 'banco-tiempo',
            ],
        ];
    }
}

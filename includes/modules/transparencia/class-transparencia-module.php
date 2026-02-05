<?php
/**
 * Modulo de Transparencia para Chat IA
 *
 * @package FlavorChatIA
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Modulo de Transparencia - Portal de transparencia y rendicion de cuentas
 */
class Flavor_Chat_Transparencia_Module extends Flavor_Chat_Module_Base {

    /**
     * Constructor
     */
    public function __construct() {
        $this->id = 'transparencia';
        $this->name = __('Portal de Transparencia', 'flavor-chat-ia');
        $this->description = __('Portal de transparencia con datos publicos, presupuestos, contratos y rendicion de cuentas.', 'flavor-chat-ia');

        parent::__construct();
    }

    /**
     * {@inheritdoc}
     */
    public function can_activate() {
        global $wpdb;
        $tabla_datos = $wpdb->prefix . 'flavor_transparencia_datos';

        return Flavor_Chat_Helpers::tabla_existe($tabla_datos);
    }

    /**
     * {@inheritdoc}
     */
    public function get_activation_error() {
        if (!$this->can_activate()) {
            return __('Las tablas de Transparencia no estan creadas. Se crearan automaticamente al activar.', 'flavor-chat-ia');
        }
        return '';
    }

    /**
     * {@inheritdoc}
     */
    protected function get_default_settings() {
        return [
            'disponible_app' => 'cliente',
            'permite_solicitudes_anonimas' => false,
            'dias_plazo_respuesta' => 30,
            'publicacion_automatica' => false,
            'requiere_aprobacion_publicacion' => true,
            'notificar_nuevas_solicitudes' => true,
            'categorias_habilitadas' => ['presupuestos', 'contratos', 'subvenciones', 'normativa', 'actas', 'personal', 'indicadores'],
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
        $tabla_datos = $wpdb->prefix . 'flavor_transparencia_datos';

        if (!Flavor_Chat_Helpers::tabla_existe($tabla_datos)) {
            $this->create_tables();
        }
    }

    /**
     * Crea las tablas necesarias
     */
    private function create_tables() {
        global $wpdb;
        $charset_collate = $wpdb->get_charset_collate();

        $tabla_datos = $wpdb->prefix . 'flavor_transparencia_datos';
        $tabla_solicitudes = $wpdb->prefix . 'flavor_transparencia_solicitudes';

        $sql_datos = "CREATE TABLE IF NOT EXISTS $tabla_datos (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            categoria enum('presupuestos','contratos','subvenciones','normativa','actas','personal','indicadores') NOT NULL,
            titulo varchar(255) NOT NULL,
            descripcion text DEFAULT NULL,
            contenido text DEFAULT NULL,
            documentos json DEFAULT NULL,
            importe decimal(15,2) DEFAULT NULL,
            periodo varchar(100) DEFAULT NULL,
            entidad varchar(255) DEFAULT NULL,
            estado enum('borrador','publicado','archivado') DEFAULT 'borrador',
            fecha_publicacion datetime DEFAULT NULL,
            fecha_creacion datetime DEFAULT CURRENT_TIMESTAMP,
            fecha_actualizacion datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY categoria (categoria),
            KEY estado (estado),
            KEY fecha_publicacion (fecha_publicacion)
        ) $charset_collate;";

        $sql_solicitudes = "CREATE TABLE IF NOT EXISTS $tabla_solicitudes (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            user_id bigint(20) unsigned DEFAULT NULL,
            titulo varchar(255) NOT NULL,
            descripcion text NOT NULL,
            categoria varchar(100) DEFAULT NULL,
            estado enum('recibida','en_tramite','resuelta','denegada') DEFAULT 'recibida',
            respuesta text DEFAULT NULL,
            documentos_adjuntos json DEFAULT NULL,
            fecha_solicitud datetime DEFAULT CURRENT_TIMESTAMP,
            fecha_resolucion datetime DEFAULT NULL,
            PRIMARY KEY (id),
            KEY user_id (user_id),
            KEY estado (estado),
            KEY categoria (categoria)
        ) $charset_collate;";

        require_once ABSPATH . 'wp-admin/includes/upgrade.php';
        dbDelta($sql_datos);
        dbDelta($sql_solicitudes);
    }

    /**
     * {@inheritdoc}
     */
    public function get_actions() {
        return [
            'consultar_datos' => [
                'description' => 'Consultar datos publicos de transparencia',
                'params' => ['categoria', 'periodo', 'entidad'],
            ],
            'solicitar_informacion' => [
                'description' => 'Solicitar informacion publica',
                'params' => ['titulo', 'descripcion', 'categoria'],
            ],
            'presupuestos' => [
                'description' => 'Consultar presupuestos publicados',
                'params' => ['periodo', 'entidad'],
            ],
            'contratos' => [
                'description' => 'Consultar contratos publicos',
                'params' => ['periodo', 'entidad'],
            ],
            'ver_indicadores' => [
                'description' => 'Ver indicadores de gestion y rendicion de cuentas',
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
            'error' => "Accion no implementada: {$action_name}",
        ];
    }

    /**
     * Accion: Consultar datos publicos
     */
    private function action_consultar_datos($params) {
        global $wpdb;
        $tabla_datos = $wpdb->prefix . 'flavor_transparencia_datos';

        $where = ["estado = 'publicado'"];
        $prepare_values = [];

        if (!empty($params['categoria'])) {
            $where[] = 'categoria = %s';
            $prepare_values[] = sanitize_text_field($params['categoria']);
        }

        if (!empty($params['periodo'])) {
            $where[] = 'periodo = %s';
            $prepare_values[] = sanitize_text_field($params['periodo']);
        }

        if (!empty($params['entidad'])) {
            $where[] = 'entidad LIKE %s';
            $prepare_values[] = '%' . $wpdb->esc_like(sanitize_text_field($params['entidad'])) . '%';
        }

        $sql = "SELECT id, categoria, titulo, descripcion, importe, periodo, entidad, fecha_publicacion
                FROM $tabla_datos
                WHERE " . implode(' AND ', $where) . "
                ORDER BY fecha_publicacion DESC
                LIMIT 50";

        if (!empty($prepare_values)) {
            $datos_publicos = $wpdb->get_results($wpdb->prepare($sql, ...$prepare_values));
        } else {
            $datos_publicos = $wpdb->get_results($sql);
        }

        return [
            'success' => true,
            'datos' => array_map(function($dato) {
                return [
                    'id' => $dato->id,
                    'categoria' => $dato->categoria,
                    'titulo' => $dato->titulo,
                    'descripcion' => wp_trim_words($dato->descripcion, 30),
                    'importe' => $dato->importe ? floatval($dato->importe) : null,
                    'periodo' => $dato->periodo,
                    'entidad' => $dato->entidad,
                    'fecha_publicacion' => $dato->fecha_publicacion ? date('d/m/Y', strtotime($dato->fecha_publicacion)) : null,
                ];
            }, $datos_publicos),
        ];
    }

    /**
     * Accion: Solicitar informacion publica
     */
    private function action_solicitar_informacion($params) {
        global $wpdb;
        $tabla_solicitudes = $wpdb->prefix . 'flavor_transparencia_solicitudes';

        if (empty($params['titulo']) || empty($params['descripcion'])) {
            return [
                'success' => false,
                'error' => 'El titulo y la descripcion son obligatorios.',
            ];
        }

        $usuario_actual_id = get_current_user_id();

        $resultado_insercion = $wpdb->insert($tabla_solicitudes, [
            'user_id' => $usuario_actual_id ?: null,
            'titulo' => sanitize_text_field($params['titulo']),
            'descripcion' => sanitize_textarea_field($params['descripcion']),
            'categoria' => !empty($params['categoria']) ? sanitize_text_field($params['categoria']) : null,
            'estado' => 'recibida',
            'fecha_solicitud' => current_time('mysql'),
        ]);

        if ($resultado_insercion === false) {
            return [
                'success' => false,
                'error' => 'No se pudo registrar la solicitud.',
            ];
        }

        return [
            'success' => true,
            'message' => 'Solicitud de informacion registrada correctamente.',
            'solicitud_id' => $wpdb->insert_id,
        ];
    }

    /**
     * Accion: Consultar presupuestos
     */
    private function action_presupuestos($params) {
        $params['categoria'] = 'presupuestos';
        return $this->action_consultar_datos($params);
    }

    /**
     * Accion: Consultar contratos
     */
    private function action_contratos($params) {
        $params['categoria'] = 'contratos';
        return $this->action_consultar_datos($params);
    }

    /**
     * Accion: Ver indicadores de gestion
     */
    private function action_ver_indicadores($params) {
        $params['categoria'] = 'indicadores';
        return $this->action_consultar_datos($params);
    }

    /**
     * Componentes web del modulo
     *
     * IA Features futuras:
     * - Analisis automatico de datos presupuestarios
     * - Generacion de informes de transparencia
     * - Comparativas interanuales automaticas
     * - Alertas de cumplimiento normativo
     */
    public function get_web_components() {
        return [
            'hero_transparencia' => [
                'label' => __('Hero Transparencia', 'flavor-chat-ia'),
                'category' => 'hero',
                'icon' => 'dashicons-visibility',
                'fields' => [
                    'titulo' => ['type' => 'text', 'default' => __('Portal de Transparencia', 'flavor-chat-ia')],
                    'subtitulo' => ['type' => 'textarea', 'default' => __('Acceso a la informacion publica y rendicion de cuentas', 'flavor-chat-ia')],
                    'imagen_fondo' => ['type' => 'image', 'default' => ''],
                    'mostrar_buscador' => ['type' => 'toggle', 'default' => true],
                ],
                'template' => 'transparencia/hero',
            ],
            'datos_publicos_grid' => [
                'label' => __('Grid de Datos Publicos', 'flavor-chat-ia'),
                'category' => 'listings',
                'icon' => 'dashicons-media-spreadsheet',
                'fields' => [
                    'titulo' => ['type' => 'text', 'default' => __('Datos Publicos', 'flavor-chat-ia')],
                    'columnas' => ['type' => 'select', 'options' => [2, 3, 4], 'default' => 3],
                    'limite' => ['type' => 'number', 'default' => 9],
                    'categoria' => ['type' => 'text', 'default' => ''],
                    'mostrar_importe' => ['type' => 'toggle', 'default' => true],
                ],
                'template' => 'transparencia/grid',
            ],
            'indicadores_widget' => [
                'label' => __('Widget de Indicadores', 'flavor-chat-ia'),
                'category' => 'widgets',
                'icon' => 'dashicons-chart-bar',
                'fields' => [
                    'titulo' => ['type' => 'text', 'default' => __('Indicadores de Gestion', 'flavor-chat-ia')],
                    'periodo' => ['type' => 'text', 'default' => ''],
                    'mostrar_graficos' => ['type' => 'toggle', 'default' => true],
                    'estilo' => ['type' => 'select', 'options' => ['cards', 'tabla', 'graficos'], 'default' => 'cards'],
                ],
                'template' => 'transparencia/indicadores',
            ],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function get_tool_definitions() {
        return [
            [
                'name' => 'consultar_datos',
                'description' => 'Consultar datos publicos de transparencia',
                'input_schema' => [
                    'type' => 'object',
                    'properties' => [
                        'categoria' => ['type' => 'string', 'description' => 'Categoria de datos (presupuestos, contratos, subvenciones, normativa, actas, personal, indicadores)'],
                        'periodo' => ['type' => 'string', 'description' => 'Periodo de consulta'],
                    ],
                ],
            ],
            [
                'name' => 'solicitar_informacion',
                'description' => 'Solicitar informacion publica',
                'input_schema' => [
                    'type' => 'object',
                    'properties' => [
                        'titulo' => ['type' => 'string', 'description' => 'Titulo de la solicitud'],
                        'descripcion' => ['type' => 'string', 'description' => 'Descripcion detallada de la informacion solicitada'],
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
**Portal de Transparencia**

Acceso a la informacion publica y rendicion de cuentas.

**Categorias de datos publicos:**
- Presupuestos: Presupuestos anuales, ejecucion presupuestaria, liquidaciones
- Contratos: Contratos publicos, licitaciones, adjudicaciones
- Subvenciones: Subvenciones concedidas y recibidas
- Normativa: Ordenanzas, reglamentos, acuerdos plenarios
- Actas: Actas de pleno, juntas de gobierno, comisiones
- Personal: Plantilla, retribuciones, oferta publica de empleo
- Indicadores: Indicadores de gestion y calidad de servicios

**Derecho de acceso a la informacion:**
- Toda persona tiene derecho a acceder a la informacion publica
- Se puede solicitar informacion sin necesidad de motivar la solicitud
- Plazo maximo de respuesta: 30 dias habiles
- La denegacion debe ser motivada

**Como solicitar informacion:**
1. Indica el tema o datos que necesitas
2. Describe con detalle la informacion solicitada
3. Recibiras confirmacion de tu solicitud
4. Se tramitara en un plazo maximo de 30 dias

**Datos disponibles:**
- Presupuestos y cuentas anuales
- Contratos y licitaciones
- Subvenciones y ayudas
- Normativa vigente
- Actas y acuerdos
- Informacion institucional
- Indicadores de gestion
KNOWLEDGE;
    }

    /**
     * {@inheritdoc}
     */
    public function get_faqs() {
        return [
            [
                'pregunta' => 'Como puedo acceder a los presupuestos?',
                'respuesta' => 'Los presupuestos estan disponibles en la seccion de datos publicos. Puedes consultar presupuestos por periodo y entidad.',
            ],
            [
                'pregunta' => 'Puedo solicitar informacion que no esta publicada?',
                'respuesta' => 'Si, puedes realizar una solicitud de acceso a informacion publica. El plazo de respuesta es de 30 dias habiles.',
            ],
            [
                'pregunta' => 'Donde puedo ver los contratos publicos?',
                'respuesta' => 'Los contratos estan en la categoria de contratos del portal de transparencia, con detalle de adjudicatario, importe y periodo.',
            ],
            [
                'pregunta' => 'Que son los indicadores de gestion?',
                'respuesta' => 'Son metricas que miden la calidad y eficiencia de los servicios publicos, como tiempos de respuesta, satisfaccion ciudadana y ejecucion presupuestaria.',
            ],
        ];
    }
}

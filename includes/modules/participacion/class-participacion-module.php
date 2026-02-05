<?php
/**
 * Módulo de Participación Ciudadana para Chat IA
 *
 * @package FlavorChatIA
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Módulo de Participación Ciudadana - Votaciones, encuestas y propuestas
 */
class Flavor_Chat_Participacion_Module extends Flavor_Chat_Module_Base {

    /**
     * Constructor
     */
    public function __construct() {
        $this->id = 'participacion';
        $this->name = __('Participación Ciudadana', 'flavor-chat-ia');
        $this->description = __('Votaciones, encuestas, propuestas y consultas ciudadanas desde la app.', 'flavor-chat-ia');

        parent::__construct();
    }

    /**
     * {@inheritdoc}
     */
    public function can_activate() {
        global $wpdb;
        $tabla_propuestas = $wpdb->prefix . 'flavor_propuestas';

        return Flavor_Chat_Helpers::tabla_existe($tabla_propuestas);
    }

    /**
     * {@inheritdoc}
     */
    public function get_activation_error() {
        if (!$this->can_activate()) {
            return __('Las tablas de Participación no están creadas. Activa el módulo para crearlas automáticamente.', 'flavor-chat-ia');
        }
        return '';
    }

    /**
     * {@inheritdoc}
     */
    protected function get_default_settings() {
        return [
            'requiere_verificacion' => true,
            'votos_necesarios_propuesta' => 10,
            'permite_propuestas_ciudadanas' => true,
            'moderacion_propuestas' => true,
            'duracion_votacion_dias' => 7,
            'quorum_minimo' => 0, // % mínimo de participación
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
        $tabla_propuestas = $wpdb->prefix . 'flavor_propuestas';

        if (!Flavor_Chat_Helpers::tabla_existe($tabla_propuestas)) {
            $this->create_tables();
        }
    }

    /**
     * Crea las tablas necesarias
     */
    private function create_tables() {
        global $wpdb;
        $charset_collate = $wpdb->get_charset_collate();

        $tabla_propuestas = $wpdb->prefix . 'flavor_propuestas';
        $tabla_votaciones = $wpdb->prefix . 'flavor_votaciones';
        $tabla_votos = $wpdb->prefix . 'flavor_votos';

        $sql_propuestas = "CREATE TABLE IF NOT EXISTS $tabla_propuestas (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            titulo varchar(255) NOT NULL,
            descripcion text NOT NULL,
            categoria varchar(50) DEFAULT 'general',
            proponente_id bigint(20) unsigned DEFAULT NULL,
            estado enum('borrador','pendiente_validacion','activa','aprobada','rechazada','implementada') DEFAULT 'pendiente_validacion',
            tipo enum('propuesta','consulta','iniciativa') DEFAULT 'propuesta',
            votos_favor int(11) DEFAULT 0,
            votos_contra int(11) DEFAULT 0,
            votos_abstencion int(11) DEFAULT 0,
            fecha_creacion datetime DEFAULT CURRENT_TIMESTAMP,
            fecha_publicacion datetime DEFAULT NULL,
            fecha_finalizacion datetime DEFAULT NULL,
            ambito varchar(100) DEFAULT NULL,
            presupuesto_estimado decimal(10,2) DEFAULT NULL,
            documentos text DEFAULT NULL,
            PRIMARY KEY (id),
            KEY proponente_id (proponente_id),
            KEY estado (estado),
            KEY categoria (categoria)
        ) $charset_collate;";

        $sql_votaciones = "CREATE TABLE IF NOT EXISTS $tabla_votaciones (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            titulo varchar(255) NOT NULL,
            descripcion text NOT NULL,
            tipo enum('referendum','consulta','encuesta') DEFAULT 'consulta',
            fecha_inicio datetime NOT NULL,
            fecha_fin datetime NOT NULL,
            estado enum('programada','activa','finalizada','cancelada') DEFAULT 'programada',
            opciones text NOT NULL,
            total_votos int(11) DEFAULT 0,
            es_anonima tinyint(1) DEFAULT 1,
            permite_multiples tinyint(1) DEFAULT 0,
            creado_por bigint(20) unsigned DEFAULT NULL,
            fecha_creacion datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY estado (estado),
            KEY fecha_inicio (fecha_inicio)
        ) $charset_collate;";

        $sql_votos = "CREATE TABLE IF NOT EXISTS $tabla_votos (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            votacion_id bigint(20) unsigned DEFAULT NULL,
            propuesta_id bigint(20) unsigned DEFAULT NULL,
            usuario_id bigint(20) unsigned NOT NULL,
            voto varchar(100) NOT NULL,
            es_anonimo tinyint(1) DEFAULT 1,
            ip_address varchar(45) DEFAULT NULL,
            fecha_voto datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            UNIQUE KEY votacion_usuario (votacion_id, usuario_id),
            UNIQUE KEY propuesta_usuario (propuesta_id, usuario_id),
            KEY usuario_id (usuario_id)
        ) $charset_collate;";

        require_once ABSPATH . 'wp-admin/includes/upgrade.php';
        dbDelta($sql_propuestas);
        dbDelta($sql_votaciones);
        dbDelta($sql_votos);
    }

    /**
     * {@inheritdoc}
     */
    public function get_actions() {
        return [
            'crear_propuesta' => [
                'description' => 'Crear una propuesta ciudadana',
                'params' => ['titulo', 'descripcion', 'categoria', 'presupuesto_estimado'],
            ],
            'listar_propuestas' => [
                'description' => 'Listar propuestas activas',
                'params' => ['estado', 'categoria', 'limite'],
            ],
            'ver_propuesta' => [
                'description' => 'Ver detalles de una propuesta',
                'params' => ['propuesta_id'],
            ],
            'apoyar_propuesta' => [
                'description' => 'Apoyar una propuesta con tu voto',
                'params' => ['propuesta_id'],
            ],
            'votar' => [
                'description' => 'Votar en una votación activa',
                'params' => ['votacion_id', 'opcion'],
            ],
            'listar_votaciones' => [
                'description' => 'Ver votaciones activas',
                'params' => ['estado'],
            ],
            'resultados_votacion' => [
                'description' => 'Ver resultados de una votación',
                'params' => ['votacion_id'],
            ],
            'mis_propuestas' => [
                'description' => 'Ver propuestas que he creado',
                'params' => [],
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
     * Acción: Crear propuesta
     */
    private function action_crear_propuesta($params) {
        $usuario_id = get_current_user_id();

        if (!$usuario_id) {
            return [
                'success' => false,
                'error' => 'Debes iniciar sesión para crear propuestas.',
            ];
        }

        $titulo = sanitize_text_field($params['titulo'] ?? '');
        $descripcion = sanitize_textarea_field($params['descripcion'] ?? '');

        if (empty($titulo) || empty($descripcion)) {
            return [
                'success' => false,
                'error' => 'Título y descripción son obligatorios.',
            ];
        }

        global $wpdb;
        $tabla_propuestas = $wpdb->prefix . 'flavor_propuestas';

        $settings = $this->settings;
        $estado_inicial = $settings['moderacion_propuestas'] ? 'pendiente_validacion' : 'activa';

        $resultado = $wpdb->insert(
            $tabla_propuestas,
            [
                'titulo' => $titulo,
                'descripcion' => $descripcion,
                'categoria' => sanitize_text_field($params['categoria'] ?? 'general'),
                'proponente_id' => $usuario_id,
                'estado' => $estado_inicial,
                'tipo' => 'propuesta',
                'presupuesto_estimado' => !empty($params['presupuesto_estimado']) ? floatval($params['presupuesto_estimado']) : null,
            ],
            ['%s', '%s', '%s', '%d', '%s', '%s', '%f']
        );

        if ($resultado === false) {
            return [
                'success' => false,
                'error' => 'Error al crear la propuesta.',
            ];
        }

        $mensaje = $estado_inicial === 'pendiente_validacion'
            ? '¡Propuesta creada! Está pendiente de validación por el ayuntamiento.'
            : '¡Propuesta publicada! Ya puede recibir apoyos de otros vecinos.';

        return [
            'success' => true,
            'propuesta_id' => $wpdb->insert_id,
            'mensaje' => $mensaje,
        ];
    }

    /**
     * Acción: Listar propuestas
     */
    private function action_listar_propuestas($params) {
        global $wpdb;
        $tabla_propuestas = $wpdb->prefix . 'flavor_propuestas';

        $where = ['1=1'];
        $prepare_values = [];

        if (!empty($params['estado'])) {
            $where[] = 'estado = %s';
            $prepare_values[] = $params['estado'];
        } else {
            // Por defecto solo activas
            $where[] = "estado IN ('activa', 'aprobada')";
        }

        if (!empty($params['categoria'])) {
            $where[] = 'categoria = %s';
            $prepare_values[] = $params['categoria'];
        }

        $limite = absint($params['limite'] ?? 20);
        $sql_where = implode(' AND ', $where);

        $sql = "SELECT * FROM $tabla_propuestas WHERE $sql_where ORDER BY votos_favor DESC, fecha_creacion DESC LIMIT %d";
        $prepare_values[] = $limite;

        $propuestas = $wpdb->get_results($wpdb->prepare($sql, ...$prepare_values));

        return [
            'success' => true,
            'total' => count($propuestas),
            'propuestas' => array_map(function($p) {
                $usuario = get_userdata($p->proponente_id);
                return [
                    'id' => $p->id,
                    'titulo' => $p->titulo,
                    'descripcion' => wp_trim_words($p->descripcion, 30),
                    'categoria' => $p->categoria,
                    'estado' => $p->estado,
                    'votos' => [
                        'favor' => $p->votos_favor,
                        'contra' => $p->votos_contra,
                        'total' => $p->votos_favor + $p->votos_contra + $p->votos_abstencion,
                    ],
                    'proponente' => $usuario ? $usuario->display_name : 'Usuario',
                    'fecha' => date('d/m/Y', strtotime($p->fecha_creacion)),
                ];
            }, $propuestas),
        ];
    }

    /**
     * Acción: Apoyar propuesta
     */
    private function action_apoyar_propuesta($params) {
        $usuario_id = get_current_user_id();

        if (!$usuario_id) {
            return [
                'success' => false,
                'error' => 'Debes iniciar sesión para apoyar propuestas.',
            ];
        }

        $propuesta_id = absint($params['propuesta_id'] ?? 0);

        if (!$propuesta_id) {
            return [
                'success' => false,
                'error' => 'ID de propuesta inválido.',
            ];
        }

        global $wpdb;
        $tabla_votos = $wpdb->prefix . 'flavor_votos';
        $tabla_propuestas = $wpdb->prefix . 'flavor_propuestas';

        // Verificar que no haya votado ya
        $ya_voto = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM $tabla_votos WHERE propuesta_id = %d AND usuario_id = %d",
            $propuesta_id,
            $usuario_id
        ));

        if ($ya_voto) {
            return [
                'success' => false,
                'error' => 'Ya has apoyado esta propuesta.',
            ];
        }

        // Registrar voto
        $wpdb->insert(
            $tabla_votos,
            [
                'propuesta_id' => $propuesta_id,
                'usuario_id' => $usuario_id,
                'voto' => 'favor',
                'es_anonimo' => 1,
            ],
            ['%d', '%d', '%s', '%d']
        );

        // Actualizar contador
        $wpdb->query($wpdb->prepare(
            "UPDATE $tabla_propuestas SET votos_favor = votos_favor + 1 WHERE id = %d",
            $propuesta_id
        ));

        $votos_actuales = $wpdb->get_var($wpdb->prepare(
            "SELECT votos_favor FROM $tabla_propuestas WHERE id = %d",
            $propuesta_id
        ));

        return [
            'success' => true,
            'votos_favor' => $votos_actuales,
            'mensaje' => '¡Gracias por tu apoyo! La propuesta ahora tiene ' . $votos_actuales . ' apoyos.',
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function get_tool_definitions() {
        return [
            [
                'name' => 'participacion_crear_propuesta',
                'description' => 'Crear una propuesta ciudadana para el barrio',
                'input_schema' => [
                    'type' => 'object',
                    'properties' => [
                        'titulo' => [
                            'type' => 'string',
                            'description' => 'Título de la propuesta',
                        ],
                        'descripcion' => [
                            'type' => 'string',
                            'description' => 'Descripción detallada',
                        ],
                        'categoria' => [
                            'type' => 'string',
                            'description' => 'Categoría de la propuesta',
                        ],
                    ],
                    'required' => ['titulo', 'descripcion'],
                ],
            ],
            [
                'name' => 'participacion_listar',
                'description' => 'Ver propuestas ciudadanas activas',
                'input_schema' => [
                    'type' => 'object',
                    'properties' => [
                        'categoria' => [
                            'type' => 'string',
                            'description' => 'Filtrar por categoría',
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
**Sistema de Participación Ciudadana**

Permite a los vecinos proponer, votar y decidir sobre asuntos del barrio.

**Tipos de participación:**
- Propuestas ciudadanas: Ideas para mejorar el barrio
- Consultas: Preguntar opinión sobre decisiones
- Votaciones: Decidir entre opciones
- Iniciativas: Proyectos con presupuesto

**Proceso de propuestas:**
1. Cualquier vecino puede crear una propuesta
2. Otros vecinos la apoyan con votos
3. Si alcanza los votos mínimos, pasa a estudio
4. El ayuntamiento evalúa viabilidad
5. Se implementa o se explica por qué no

**Votaciones:**
- Pueden ser anónimas o públicas
- Tienen fecha de inicio y fin
- Resultados visibles en tiempo real
- Pueden tener quórum mínimo
KNOWLEDGE;
    }

    /**
     * {@inheritdoc}
     */
    public function get_faqs() {
        return [
            [
                'pregunta' => '¿Cómo creo una propuesta?',
                'respuesta' => 'Ve a Participación, pulsa "Nueva propuesta", describe tu idea y publícala. Otros vecinos podrán apoyarla.',
            ],
            [
                'pregunta' => '¿Cuántos apoyos necesita una propuesta?',
                'respuesta' => 'Depende de la configuración del ayuntamiento. Normalmente entre 10-50 apoyos para ser evaluada.',
            ],
        ];
    }

    /**
     * Componentes web del módulo
     */
    public function get_web_components() {
        return [
            'hero' => [
                'label' => __('Hero Participación', 'flavor-chat-ia'),
                'description' => __('Sección hero con propuestas destacadas', 'flavor-chat-ia'),
                'category' => 'hero',
                'icon' => 'dashicons-megaphone',
                'fields' => [
                    'titulo' => [
                        'type' => 'text',
                        'label' => __('Título', 'flavor-chat-ia'),
                        'default' => __('Participación Ciudadana', 'flavor-chat-ia'),
                    ],
                    'subtitulo' => [
                        'type' => 'textarea',
                        'label' => __('Subtítulo', 'flavor-chat-ia'),
                        'default' => __('Tu voz importa. Propón y decide el futuro de tu comunidad', 'flavor-chat-ia'),
                    ],
                    'mostrar_estadisticas' => [
                        'type' => 'toggle',
                        'label' => __('Mostrar estadísticas', 'flavor-chat-ia'),
                        'default' => true,
                    ],
                ],
                'template' => 'participacion/hero',
            ],
            'propuestas_grid' => [
                'label' => __('Grid de Propuestas', 'flavor-chat-ia'),
                'description' => __('Listado de propuestas ciudadanas', 'flavor-chat-ia'),
                'category' => 'listings',
                'icon' => 'dashicons-lightbulb',
                'fields' => [
                    'titulo' => [
                        'type' => 'text',
                        'label' => __('Título', 'flavor-chat-ia'),
                        'default' => __('Propuestas Activas', 'flavor-chat-ia'),
                    ],
                    'estado' => [
                        'type' => 'select',
                        'label' => __('Filtrar por estado', 'flavor-chat-ia'),
                        'options' => ['todas', 'activas', 'aprobadas', 'en_estudio'],
                        'default' => 'activas',
                    ],
                    'ordenar' => [
                        'type' => 'select',
                        'label' => __('Ordenar por', 'flavor-chat-ia'),
                        'options' => ['recientes', 'apoyos', 'comentarios'],
                        'default' => 'apoyos',
                    ],
                    'limite' => [
                        'type' => 'number',
                        'label' => __('Número máximo', 'flavor-chat-ia'),
                        'default' => 9,
                    ],
                ],
                'template' => 'participacion/propuestas-grid',
            ],
            'consultas' => [
                'label' => __('Consultas Ciudadanas', 'flavor-chat-ia'),
                'description' => __('Listado de consultas y votaciones', 'flavor-chat-ia'),
                'category' => 'listings',
                'icon' => 'dashicons-forms',
                'fields' => [
                    'titulo' => [
                        'type' => 'text',
                        'label' => __('Título', 'flavor-chat-ia'),
                        'default' => __('Consultas Abiertas', 'flavor-chat-ia'),
                    ],
                    'limite' => [
                        'type' => 'number',
                        'label' => __('Número máximo', 'flavor-chat-ia'),
                        'default' => 4,
                    ],
                ],
                'template' => 'participacion/consultas',
            ],
            'cta_propuesta' => [
                'label' => __('CTA Nueva Propuesta', 'flavor-chat-ia'),
                'description' => __('Llamada a acción para crear propuesta', 'flavor-chat-ia'),
                'category' => 'cta',
                'icon' => 'dashicons-plus-alt',
                'fields' => [
                    'titulo' => [
                        'type' => 'text',
                        'label' => __('Título', 'flavor-chat-ia'),
                        'default' => __('¿Tienes una idea para mejorar tu barrio?', 'flavor-chat-ia'),
                    ],
                    'descripcion' => [
                        'type' => 'textarea',
                        'label' => __('Descripción', 'flavor-chat-ia'),
                        'default' => __('Comparte tu propuesta y busca apoyos entre tus vecinos', 'flavor-chat-ia'),
                    ],
                    'boton_texto' => [
                        'type' => 'text',
                        'label' => __('Texto del botón', 'flavor-chat-ia'),
                        'default' => __('Crear Propuesta', 'flavor-chat-ia'),
                    ],
                ],
                'template' => 'participacion/cta-propuesta',
            ],
        ];
    }
}

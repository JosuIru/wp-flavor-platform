<?php
/**
 * Módulo de Avisos Municipales para Chat IA
 *
 * @package FlavorChatIA
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Módulo de Avisos Municipales - Comunicados oficiales y notificaciones
 */
class Flavor_Chat_Avisos_Municipales_Module extends Flavor_Chat_Module_Base {

    /**
     * Constructor
     */
    public function __construct() {
        $this->id = 'avisos_municipales';
        $this->name = __('Avisos Municipales', 'flavor-chat-ia');
        $this->description = __('Comunicados oficiales, cortes de servicio, eventos y notificaciones del ayuntamiento.', 'flavor-chat-ia');

        parent::__construct();
    }

    /**
     * {@inheritdoc}
     */
    public function can_activate() {
        global $wpdb;
        $tabla_avisos = $wpdb->prefix . 'flavor_avisos_municipales';

        return Flavor_Chat_Helpers::tabla_existe($tabla_avisos);
    }

    /**
     * {@inheritdoc}
     */
    public function get_activation_error() {
        if (!$this->can_activate()) {
            return __('Las tablas de Avisos Municipales no están creadas. Activa el módulo para crearlas automáticamente.', 'flavor-chat-ia');
        }
        return '';
    }

    /**
     * {@inheritdoc}
     */
    protected function get_default_settings() {
        return [
            'enviar_push_notifications' => true,
            'requiere_confirmacion_lectura' => false,
            'categorias' => [
                'urgente' => __('Urgente', 'flavor-chat-ia'),
                'corte_servicio' => __('Corte de servicio', 'flavor-chat-ia'),
                'evento' => __('Evento', 'flavor-chat-ia'),
                'informativo' => __('Informativo', 'flavor-chat-ia'),
                'trafico' => __('Tráfico', 'flavor-chat-ia'),
                'obras' => __('Obras', 'flavor-chat-ia'),
                'convocatoria' => __('Convocatoria', 'flavor-chat-ia'),
            ],
            'ambitos' => [
                'todo_municipio' => __('Todo el municipio', 'flavor-chat-ia'),
                'distrito' => __('Por distrito', 'flavor-chat-ia'),
                'barrio' => __('Por barrio', 'flavor-chat-ia'),
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
        $tabla_avisos = $wpdb->prefix . 'flavor_avisos_municipales';

        if (!Flavor_Chat_Helpers::tabla_existe($tabla_avisos)) {
            $this->create_tables();
        }
    }

    /**
     * Crea las tablas necesarias
     */
    private function create_tables() {
        global $wpdb;
        $charset_collate = $wpdb->get_charset_collate();

        $tabla_avisos = $wpdb->prefix . 'flavor_avisos_municipales';
        $tabla_lecturas = $wpdb->prefix . 'flavor_avisos_lecturas';

        $sql_avisos = "CREATE TABLE IF NOT EXISTS $tabla_avisos (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            titulo varchar(255) NOT NULL,
            contenido text NOT NULL,
            categoria varchar(50) NOT NULL,
            prioridad enum('baja','media','alta','urgente') DEFAULT 'media',
            ambito varchar(50) DEFAULT 'todo_municipio',
            ubicacion_especifica varchar(500) DEFAULT NULL,
            fecha_inicio datetime NOT NULL,
            fecha_fin datetime DEFAULT NULL,
            publicado tinyint(1) DEFAULT 1,
            adjuntos text DEFAULT NULL,
            enlace_externo varchar(500) DEFAULT NULL,
            autor_id bigint(20) unsigned DEFAULT NULL,
            departamento varchar(100) DEFAULT NULL,
            visualizaciones int(11) DEFAULT 0,
            confirmaciones_lectura int(11) DEFAULT 0,
            fecha_creacion datetime DEFAULT CURRENT_TIMESTAMP,
            fecha_actualizacion datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY categoria (categoria),
            KEY prioridad (prioridad),
            KEY fecha_inicio (fecha_inicio),
            KEY publicado (publicado)
        ) $charset_collate;";

        $sql_lecturas = "CREATE TABLE IF NOT EXISTS $tabla_lecturas (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            aviso_id bigint(20) unsigned NOT NULL,
            usuario_id bigint(20) unsigned NOT NULL,
            leido tinyint(1) DEFAULT 1,
            confirmado tinyint(1) DEFAULT 0,
            fecha_lectura datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            UNIQUE KEY aviso_usuario (aviso_id, usuario_id),
            KEY aviso_id (aviso_id),
            KEY usuario_id (usuario_id)
        ) $charset_collate;";

        require_once ABSPATH . 'wp-admin/includes/upgrade.php';
        dbDelta($sql_avisos);
        dbDelta($sql_lecturas);
    }

    /**
     * {@inheritdoc}
     */
    public function get_actions() {
        return [
            'listar_avisos' => [
                'description' => 'Listar avisos municipales activos',
                'params' => ['categoria', 'prioridad', 'no_leidos', 'limite'],
            ],
            'ver_aviso' => [
                'description' => 'Ver detalles de un aviso',
                'params' => ['aviso_id'],
            ],
            'marcar_leido' => [
                'description' => 'Marcar un aviso como leído',
                'params' => ['aviso_id'],
            ],
            'confirmar_lectura' => [
                'description' => 'Confirmar lectura de aviso importante',
                'params' => ['aviso_id'],
            ],
            'avisos_no_leidos' => [
                'description' => 'Ver avisos que no he leído',
                'params' => [],
            ],
            'avisos_urgentes' => [
                'description' => 'Ver avisos urgentes activos',
                'params' => [],
            ],
            'crear_aviso' => [
                'description' => 'Crear nuevo aviso municipal (solo admin)',
                'params' => ['titulo', 'contenido', 'categoria', 'prioridad'],
            ],
            'estadisticas_aviso' => [
                'description' => 'Ver estadísticas de un aviso (solo admin)',
                'params' => ['aviso_id'],
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
     * Acción: Listar avisos
     */
    private function action_listar_avisos($params) {
        global $wpdb;
        $tabla_avisos = $wpdb->prefix . 'flavor_avisos_municipales';

        $where = ['publicado = 1', 'fecha_inicio <= NOW()', '(fecha_fin IS NULL OR fecha_fin >= NOW())'];
        $prepare_values = [];

        if (!empty($params['categoria'])) {
            $where[] = 'categoria = %s';
            $prepare_values[] = $params['categoria'];
        }

        if (!empty($params['prioridad'])) {
            $where[] = 'prioridad = %s';
            $prepare_values[] = $params['prioridad'];
        }

        $limite = absint($params['limite'] ?? 20);
        $sql_where = implode(' AND ', $where);

        $sql = "SELECT * FROM $tabla_avisos WHERE $sql_where ORDER BY prioridad DESC, fecha_inicio DESC LIMIT %d";
        $prepare_values[] = $limite;

        $avisos = $wpdb->get_results($wpdb->prepare($sql, ...$prepare_values));

        // Si hay usuario, marcar cuáles ha leído
        $usuario_id = get_current_user_id();
        if ($usuario_id) {
            $tabla_lecturas = $wpdb->prefix . 'flavor_avisos_lecturas';
            $avisos_leidos = $wpdb->get_col($wpdb->prepare(
                "SELECT aviso_id FROM $tabla_lecturas WHERE usuario_id = %d",
                $usuario_id
            ));
        } else {
            $avisos_leidos = [];
        }

        return [
            'success' => true,
            'total' => count($avisos),
            'avisos' => array_map(function($a) use ($avisos_leidos) {
                return [
                    'id' => $a->id,
                    'titulo' => $a->titulo,
                    'contenido' => wp_trim_words($a->contenido, 30),
                    'categoria' => $a->categoria,
                    'prioridad' => $a->prioridad,
                    'fecha' => date('d/m/Y H:i', strtotime($a->fecha_inicio)),
                    'leido' => in_array($a->id, $avisos_leidos),
                    'visualizaciones' => $a->visualizaciones,
                ];
            }, $avisos),
        ];
    }

    /**
     * Acción: Ver aviso
     */
    private function action_ver_aviso($params) {
        $aviso_id = absint($params['aviso_id'] ?? 0);

        if (!$aviso_id) {
            return [
                'success' => false,
                'error' => 'ID de aviso inválido.',
            ];
        }

        global $wpdb;
        $tabla_avisos = $wpdb->prefix . 'flavor_avisos_municipales';

        $aviso = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM $tabla_avisos WHERE id = %d AND publicado = 1",
            $aviso_id
        ));

        if (!$aviso) {
            return [
                'success' => false,
                'error' => 'Aviso no encontrado.',
            ];
        }

        // Incrementar visualizaciones
        $wpdb->query($wpdb->prepare(
            "UPDATE $tabla_avisos SET visualizaciones = visualizaciones + 1 WHERE id = %d",
            $aviso_id
        ));

        // Marcar como leído automáticamente
        $usuario_id = get_current_user_id();
        if ($usuario_id) {
            $this->marcar_como_leido($aviso_id, $usuario_id);
        }

        return [
            'success' => true,
            'aviso' => [
                'id' => $aviso->id,
                'titulo' => $aviso->titulo,
                'contenido' => $aviso->contenido,
                'categoria' => $aviso->categoria,
                'prioridad' => $aviso->prioridad,
                'ambito' => $aviso->ambito,
                'ubicacion' => $aviso->ubicacion_especifica,
                'fecha_inicio' => date('d/m/Y H:i', strtotime($aviso->fecha_inicio)),
                'fecha_fin' => $aviso->fecha_fin ? date('d/m/Y H:i', strtotime($aviso->fecha_fin)) : null,
                'enlace' => $aviso->enlace_externo,
                'departamento' => $aviso->departamento,
            ],
        ];
    }

    /**
     * Acción: Avisos no leídos
     */
    private function action_avisos_no_leidos($params) {
        $usuario_id = get_current_user_id();

        if (!$usuario_id) {
            return [
                'success' => false,
                'error' => 'Debes iniciar sesión.',
            ];
        }

        global $wpdb;
        $tabla_avisos = $wpdb->prefix . 'flavor_avisos_municipales';
        $tabla_lecturas = $wpdb->prefix . 'flavor_avisos_lecturas';

        $sql = "SELECT a.* FROM $tabla_avisos a
                LEFT JOIN $tabla_lecturas l ON a.id = l.aviso_id AND l.usuario_id = %d
                WHERE a.publicado = 1
                AND a.fecha_inicio <= NOW()
                AND (a.fecha_fin IS NULL OR a.fecha_fin >= NOW())
                AND l.id IS NULL
                ORDER BY a.prioridad DESC, a.fecha_inicio DESC
                LIMIT 20";

        $avisos = $wpdb->get_results($wpdb->prepare($sql, $usuario_id));

        return [
            'success' => true,
            'total_no_leidos' => count($avisos),
            'avisos' => array_map(function($a) {
                return [
                    'id' => $a->id,
                    'titulo' => $a->titulo,
                    'categoria' => $a->categoria,
                    'prioridad' => $a->prioridad,
                    'fecha' => date('d/m/Y H:i', strtotime($a->fecha_inicio)),
                ];
            }, $avisos),
        ];
    }

    /**
     * Marca aviso como leído
     */
    private function marcar_como_leido($aviso_id, $usuario_id) {
        global $wpdb;
        $tabla_lecturas = $wpdb->prefix . 'flavor_avisos_lecturas';

        $wpdb->replace(
            $tabla_lecturas,
            [
                'aviso_id' => $aviso_id,
                'usuario_id' => $usuario_id,
                'leido' => 1,
            ],
            ['%d', '%d', '%d']
        );
    }

    /**
     * {@inheritdoc}
     */
    public function get_tool_definitions() {
        return [
            [
                'name' => 'avisos_listar',
                'description' => 'Ver avisos municipales activos',
                'input_schema' => [
                    'type' => 'object',
                    'properties' => [
                        'categoria' => [
                            'type' => 'string',
                            'description' => 'Filtrar por categoría',
                            'enum' => ['urgente', 'corte_servicio', 'evento', 'informativo', 'trafico', 'obras', 'convocatoria'],
                        ],
                        'prioridad' => [
                            'type' => 'string',
                            'description' => 'Filtrar por prioridad',
                            'enum' => ['baja', 'media', 'alta', 'urgente'],
                        ],
                    ],
                ],
            ],
            [
                'name' => 'avisos_no_leidos',
                'description' => 'Ver avisos que no he leído',
                'input_schema' => [
                    'type' => 'object',
                    'properties' => [],
                ],
            ],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function get_knowledge_base() {
        return <<<KNOWLEDGE
**Sistema de Avisos Municipales**

Canal oficial de comunicación del ayuntamiento con los vecinos.

**Tipos de avisos:**
- Urgente: Alertas importantes que requieren atención inmediata
- Corte de servicio: Agua, luz, transporte, etc.
- Evento: Actividades municipales, fiestas, celebraciones
- Informativo: Noticias, cambios normativos
- Tráfico: Cortes de calle, obras, desvíos
- Obras: Trabajos en vía pública
- Convocatoria: Plenos, asambleas, consultas

**Prioridades:**
- Urgente: Rojo, requiere acción inmediata
- Alta: Naranja, importante
- Media: Amarillo, informativo
- Baja: Verde, opcional

**Notificaciones:**
- Los avisos urgentes envían notificación push
- Puedes marcar avisos como leídos
- Algunos requieren confirmación de lectura
KNOWLEDGE;
    }

    /**
     * {@inheritdoc}
     */
    public function get_faqs() {
        return [
            [
                'pregunta' => '¿Cómo sé si hay avisos nuevos?',
                'respuesta' => 'Recibirás notificaciones push en la app. También aparecen en la sección de Avisos con un indicador de "no leído".',
            ],
            [
                'pregunta' => '¿Puedo ver avisos antiguos?',
                'respuesta' => 'Sí, puedes ver el historial de avisos desde la app filtrando por fecha o categoría.',
            ],
        ];
    }

    /**
     * Componentes web del módulo
     */
    public function get_web_components() {
        return [
            'hero' => [
                'label' => __('Hero Avisos Municipales', 'flavor-chat-ia'),
                'description' => __('Sección hero con avisos destacados', 'flavor-chat-ia'),
                'category' => 'hero',
                'icon' => 'dashicons-megaphone',
                'fields' => [
                    'titulo' => [
                        'type' => 'text',
                        'label' => __('Título', 'flavor-chat-ia'),
                        'default' => __('Avisos Municipales', 'flavor-chat-ia'),
                    ],
                    'subtitulo' => [
                        'type' => 'textarea',
                        'label' => __('Subtítulo', 'flavor-chat-ia'),
                        'default' => __('Mantente informado de las novedades de tu municipio', 'flavor-chat-ia'),
                    ],
                    'mostrar_urgentes' => [
                        'type' => 'toggle',
                        'label' => __('Destacar avisos urgentes', 'flavor-chat-ia'),
                        'default' => true,
                    ],
                ],
                'template' => 'avisos-municipales/hero',
            ],
            'avisos_lista' => [
                'label' => __('Lista de Avisos', 'flavor-chat-ia'),
                'description' => __('Listado cronológico de avisos', 'flavor-chat-ia'),
                'category' => 'listings',
                'icon' => 'dashicons-list-view',
                'fields' => [
                    'titulo' => [
                        'type' => 'text',
                        'label' => __('Título', 'flavor-chat-ia'),
                        'default' => __('Últimos Avisos', 'flavor-chat-ia'),
                    ],
                    'mostrar_fecha' => [
                        'type' => 'toggle',
                        'label' => __('Mostrar fecha', 'flavor-chat-ia'),
                        'default' => true,
                    ],
                    'limite' => [
                        'type' => 'number',
                        'label' => __('Número máximo', 'flavor-chat-ia'),
                        'default' => 10,
                    ],
                ],
                'template' => 'avisos-municipales/avisos-lista',
            ],
            'categorias' => [
                'label' => __('Categorías de Avisos', 'flavor-chat-ia'),
                'description' => __('Filtros por tipo de aviso', 'flavor-chat-ia'),
                'category' => 'navigation',
                'icon' => 'dashicons-category',
                'fields' => [
                    'titulo' => [
                        'type' => 'text',
                        'label' => __('Título', 'flavor-chat-ia'),
                        'default' => __('Filtrar por Categoría', 'flavor-chat-ia'),
                    ],
                ],
                'template' => 'avisos-municipales/categorias',
            ],
            'suscripcion' => [
                'label' => __('Suscripción a Avisos', 'flavor-chat-ia'),
                'description' => __('Formulario para recibir notificaciones', 'flavor-chat-ia'),
                'category' => 'forms',
                'icon' => 'dashicons-email',
                'fields' => [
                    'titulo' => [
                        'type' => 'text',
                        'label' => __('Título', 'flavor-chat-ia'),
                        'default' => __('Recibe Avisos en tu Email', 'flavor-chat-ia'),
                    ],
                    'descripcion' => [
                        'type' => 'textarea',
                        'label' => __('Descripción', 'flavor-chat-ia'),
                        'default' => __('Suscríbete y no te pierdas ninguna novedad', 'flavor-chat-ia'),
                    ],
                ],
                'template' => 'avisos-municipales/suscripcion',
            ],
        ];
    }
}

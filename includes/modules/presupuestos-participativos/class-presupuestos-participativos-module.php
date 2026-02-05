<?php
/**
 * Módulo de Presupuestos Participativos para Chat IA
 *
 * @package FlavorChatIA
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Módulo de Presupuestos Participativos - Democracia económica directa
 */
class Flavor_Chat_Presupuestos_Participativos_Module extends Flavor_Chat_Module_Base {

    /**
     * Constructor
     */
    public function __construct() {
        $this->id = 'presupuestos_participativos';
        $this->name = __('Presupuestos Participativos', 'flavor-chat-ia');
        $this->description = __('Democracia participativa: los vecinos deciden en qué invertir el presupuesto del barrio.', 'flavor-chat-ia');

        parent::__construct();
    }

    /**
     * {@inheritdoc}
     */
    public function can_activate() {
        global $wpdb;
        $tabla_proyectos = $wpdb->prefix . 'flavor_pp_proyectos';

        return Flavor_Chat_Helpers::tabla_existe($tabla_proyectos);
    }

    /**
     * {@inheritdoc}
     */
    public function get_activation_error() {
        if (!$this->can_activate()) {
            return __('Las tablas de Presupuestos Participativos no están creadas. Activa el módulo para crearlas automáticamente.', 'flavor-chat-ia');
        }
        return '';
    }

    /**
     * {@inheritdoc}
     */
    protected function get_default_settings() {
        return [
            'presupuesto_anual' => 100000.00,
            'presupuesto_minimo_proyecto' => 1000.00,
            'presupuesto_maximo_proyecto' => 50000.00,
            'votos_maximos_por_persona' => 3,
            'requiere_verificacion' => true,
            'fase_actual' => 'cerrada', // propuestas, votacion, implementacion, cerrada
            'fecha_inicio_propuestas' => null,
            'fecha_fin_propuestas' => null,
            'fecha_inicio_votacion' => null,
            'fecha_fin_votacion' => null,
            'categorias' => [
                'infraestructura' => __('Infraestructura', 'flavor-chat-ia'),
                'medio_ambiente' => __('Medio Ambiente', 'flavor-chat-ia'),
                'cultura' => __('Cultura y Ocio', 'flavor-chat-ia'),
                'deporte' => __('Deporte', 'flavor-chat-ia'),
                'social' => __('Social', 'flavor-chat-ia'),
                'educacion' => __('Educación', 'flavor-chat-ia'),
                'accesibilidad' => __('Accesibilidad', 'flavor-chat-ia'),
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
        $tabla_proyectos = $wpdb->prefix . 'flavor_pp_proyectos';

        if (!Flavor_Chat_Helpers::tabla_existe($tabla_proyectos)) {
            $this->create_tables();
        }
    }

    /**
     * Crea las tablas necesarias
     */
    private function create_tables() {
        global $wpdb;
        $charset_collate = $wpdb->get_charset_collate();

        $tabla_proyectos = $wpdb->prefix . 'flavor_pp_proyectos';
        $tabla_votos = $wpdb->prefix . 'flavor_pp_votos';
        $tabla_ediciones = $wpdb->prefix . 'flavor_pp_ediciones';

        $sql_ediciones = "CREATE TABLE IF NOT EXISTS $tabla_ediciones (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            anio int(11) NOT NULL,
            presupuesto_total decimal(12,2) NOT NULL,
            presupuesto_gastado decimal(12,2) DEFAULT 0.00,
            fase enum('propuestas','evaluacion','votacion','implementacion','cerrada') DEFAULT 'propuestas',
            fecha_inicio_propuestas date DEFAULT NULL,
            fecha_fin_propuestas date DEFAULT NULL,
            fecha_inicio_votacion date DEFAULT NULL,
            fecha_fin_votacion date DEFAULT NULL,
            total_proyectos int(11) DEFAULT 0,
            total_votantes int(11) DEFAULT 0,
            participacion_porcentaje decimal(5,2) DEFAULT 0.00,
            fecha_creacion datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            UNIQUE KEY anio (anio)
        ) $charset_collate;";

        $sql_proyectos = "CREATE TABLE IF NOT EXISTS $tabla_proyectos (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            edicion_id bigint(20) unsigned NOT NULL,
            titulo varchar(255) NOT NULL,
            descripcion text NOT NULL,
            categoria varchar(50) NOT NULL,
            ambito varchar(100) DEFAULT NULL,
            ubicacion varchar(500) DEFAULT NULL,
            latitud decimal(10,8) DEFAULT NULL,
            longitud decimal(11,8) DEFAULT NULL,
            presupuesto_solicitado decimal(12,2) NOT NULL,
            presupuesto_aprobado decimal(12,2) DEFAULT NULL,
            proponente_id bigint(20) unsigned DEFAULT NULL,
            proponente_grupo varchar(255) DEFAULT NULL,
            estado enum('borrador','pendiente_validacion','validado','en_votacion','seleccionado','en_ejecucion','ejecutado','rechazado') DEFAULT 'pendiente_validacion',
            votos_recibidos int(11) DEFAULT 0,
            ranking int(11) DEFAULT 0,
            es_viable tinyint(1) DEFAULT NULL,
            motivo_no_viable text DEFAULT NULL,
            fecha_validacion datetime DEFAULT NULL,
            fecha_inicio_ejecucion datetime DEFAULT NULL,
            fecha_fin_ejecucion datetime DEFAULT NULL,
            porcentaje_ejecucion int(11) DEFAULT 0,
            imagenes text DEFAULT NULL,
            documentos text DEFAULT NULL,
            fecha_creacion datetime DEFAULT CURRENT_TIMESTAMP,
            fecha_actualizacion datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY edicion_id (edicion_id),
            KEY proponente_id (proponente_id),
            KEY estado (estado),
            KEY categoria (categoria)
        ) $charset_collate;";

        $sql_votos = "CREATE TABLE IF NOT EXISTS $tabla_votos (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            proyecto_id bigint(20) unsigned NOT NULL,
            edicion_id bigint(20) unsigned NOT NULL,
            usuario_id bigint(20) unsigned NOT NULL,
            prioridad int(11) DEFAULT 1,
            fecha_voto datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            UNIQUE KEY usuario_proyecto (usuario_id, proyecto_id),
            KEY proyecto_id (proyecto_id),
            KEY edicion_id (edicion_id),
            KEY usuario_id (usuario_id)
        ) $charset_collate;";

        require_once ABSPATH . 'wp-admin/includes/upgrade.php';
        dbDelta($sql_ediciones);
        dbDelta($sql_proyectos);
        dbDelta($sql_votos);
    }

    /**
     * {@inheritdoc}
     */
    public function get_actions() {
        return [
            'info_edicion_actual' => [
                'description' => 'Ver información de la edición actual de presupuestos participativos',
                'params' => [],
            ],
            'proponer_proyecto' => [
                'description' => 'Proponer un proyecto para presupuestos participativos',
                'params' => ['titulo', 'descripcion', 'categoria', 'presupuesto', 'ubicacion'],
            ],
            'listar_proyectos' => [
                'description' => 'Ver proyectos propuestos',
                'params' => ['categoria', 'estado', 'limite'],
            ],
            'ver_proyecto' => [
                'description' => 'Ver detalles de un proyecto',
                'params' => ['proyecto_id'],
            ],
            'votar_proyectos' => [
                'description' => 'Votar tus proyectos favoritos (hasta 3)',
                'params' => ['proyecto_ids'],
            ],
            'mis_votos' => [
                'description' => 'Ver qué proyectos he votado',
                'params' => [],
            ],
            'resultados' => [
                'description' => 'Ver resultados de la votación',
                'params' => ['edicion_id'],
            ],
            'seguimiento_proyecto' => [
                'description' => 'Ver estado de ejecución de un proyecto aprobado',
                'params' => ['proyecto_id'],
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
     * Acción: Info edición actual
     */
    private function action_info_edicion_actual($params) {
        global $wpdb;
        $tabla_ediciones = $wpdb->prefix . 'flavor_pp_ediciones';

        $edicion = $wpdb->get_row(
            "SELECT * FROM $tabla_ediciones WHERE fase != 'cerrada' ORDER BY anio DESC LIMIT 1"
        );

        if (!$edicion) {
            return [
                'success' => true,
                'activa' => false,
                'mensaje' => 'No hay una edición de presupuestos participativos activa actualmente.',
            ];
        }

        $fases_texto = [
            'propuestas' => 'Fase de propuestas ciudadanas',
            'evaluacion' => 'Fase de evaluación técnica',
            'votacion' => 'Fase de votación',
            'implementacion' => 'Fase de implementación',
        ];

        return [
            'success' => true,
            'activa' => true,
            'edicion' => [
                'anio' => $edicion->anio,
                'presupuesto_total' => floatval($edicion->presupuesto_total),
                'presupuesto_disponible' => floatval($edicion->presupuesto_total - $edicion->presupuesto_gastado),
                'fase' => $edicion->fase,
                'fase_texto' => $fases_texto[$edicion->fase] ?? $edicion->fase,
                'fechas' => [
                    'propuestas' => [
                        'inicio' => $edicion->fecha_inicio_propuestas,
                        'fin' => $edicion->fecha_fin_propuestas,
                    ],
                    'votacion' => [
                        'inicio' => $edicion->fecha_inicio_votacion,
                        'fin' => $edicion->fecha_fin_votacion,
                    ],
                ],
                'total_proyectos' => $edicion->total_proyectos,
                'total_votantes' => $edicion->total_votantes,
                'participacion' => floatval($edicion->participacion_porcentaje) . '%',
            ],
        ];
    }

    /**
     * Acción: Proponer proyecto
     */
    private function action_proponer_proyecto($params) {
        $usuario_id = get_current_user_id();

        if (!$usuario_id) {
            return [
                'success' => false,
                'error' => 'Debes iniciar sesión para proponer proyectos.',
            ];
        }

        // Verificar que estamos en fase de propuestas
        global $wpdb;
        $tabla_ediciones = $wpdb->prefix . 'flavor_pp_ediciones';
        $tabla_proyectos = $wpdb->prefix . 'flavor_pp_proyectos';

        $edicion = $wpdb->get_row(
            "SELECT * FROM $tabla_ediciones WHERE fase = 'propuestas' ORDER BY anio DESC LIMIT 1"
        );

        if (!$edicion) {
            return [
                'success' => false,
                'error' => 'No estamos en fase de recogida de propuestas actualmente.',
            ];
        }

        $titulo = sanitize_text_field($params['titulo'] ?? '');
        $descripcion = sanitize_textarea_field($params['descripcion'] ?? '');
        $presupuesto = floatval($params['presupuesto'] ?? 0);

        if (empty($titulo) || empty($descripcion)) {
            return [
                'success' => false,
                'error' => 'Título y descripción son obligatorios.',
            ];
        }

        $settings = $this->settings;
        if ($presupuesto < $settings['presupuesto_minimo_proyecto'] || $presupuesto > $settings['presupuesto_maximo_proyecto']) {
            return [
                'success' => false,
                'error' => sprintf(
                    'El presupuesto debe estar entre %s€ y %s€.',
                    number_format($settings['presupuesto_minimo_proyecto'], 0, ',', '.'),
                    number_format($settings['presupuesto_maximo_proyecto'], 0, ',', '.')
                ),
            ];
        }

        $resultado = $wpdb->insert(
            $tabla_proyectos,
            [
                'edicion_id' => $edicion->id,
                'titulo' => $titulo,
                'descripcion' => $descripcion,
                'categoria' => sanitize_text_field($params['categoria'] ?? 'social'),
                'ubicacion' => sanitize_text_field($params['ubicacion'] ?? ''),
                'presupuesto_solicitado' => $presupuesto,
                'proponente_id' => $usuario_id,
                'estado' => 'pendiente_validacion',
            ],
            ['%d', '%s', '%s', '%s', '%s', '%f', '%d', '%s']
        );

        if ($resultado === false) {
            return [
                'success' => false,
                'error' => 'Error al registrar el proyecto.',
            ];
        }

        return [
            'success' => true,
            'proyecto_id' => $wpdb->insert_id,
            'mensaje' => '¡Proyecto propuesto! Será evaluado técnicamente antes de pasar a votación.',
        ];
    }

    /**
     * Acción: Listar proyectos
     */
    private function action_listar_proyectos($params) {
        global $wpdb;
        $tabla_proyectos = $wpdb->prefix . 'flavor_pp_proyectos';
        $tabla_ediciones = $wpdb->prefix . 'flavor_pp_ediciones';

        // Obtener edición actual
        $edicion = $wpdb->get_row(
            "SELECT * FROM $tabla_ediciones WHERE fase != 'cerrada' ORDER BY anio DESC LIMIT 1"
        );

        if (!$edicion) {
            return [
                'success' => true,
                'total' => 0,
                'proyectos' => [],
            ];
        }

        $where = ['edicion_id = %d'];
        $prepare_values = [$edicion->id];

        if (!empty($params['categoria'])) {
            $where[] = 'categoria = %s';
            $prepare_values[] = $params['categoria'];
        }

        if (!empty($params['estado'])) {
            $where[] = 'estado = %s';
            $prepare_values[] = $params['estado'];
        } else {
            // Por defecto, mostrar proyectos validados o en votación
            $where[] = "estado IN ('validado', 'en_votacion', 'seleccionado')";
        }

        $limite = absint($params['limite'] ?? 20);
        $sql_where = implode(' AND ', $where);

        $sql = "SELECT * FROM $tabla_proyectos WHERE $sql_where ORDER BY votos_recibidos DESC, fecha_creacion DESC LIMIT %d";
        $prepare_values[] = $limite;

        $proyectos = $wpdb->get_results($wpdb->prepare($sql, ...$prepare_values));

        return [
            'success' => true,
            'edicion' => $edicion->anio,
            'fase' => $edicion->fase,
            'total' => count($proyectos),
            'proyectos' => array_map(function($p) {
                return [
                    'id' => $p->id,
                    'titulo' => $p->titulo,
                    'descripcion' => wp_trim_words($p->descripcion, 30),
                    'categoria' => $p->categoria,
                    'presupuesto' => floatval($p->presupuesto_solicitado),
                    'ubicacion' => $p->ubicacion,
                    'votos' => $p->votos_recibidos,
                    'ranking' => $p->ranking,
                    'estado' => $p->estado,
                    'porcentaje_ejecucion' => $p->porcentaje_ejecucion,
                ];
            }, $proyectos),
        ];
    }

    /**
     * Acción: Votar proyectos
     */
    private function action_votar_proyectos($params) {
        $usuario_id = get_current_user_id();

        if (!$usuario_id) {
            return [
                'success' => false,
                'error' => 'Debes iniciar sesión para votar.',
            ];
        }

        global $wpdb;
        $tabla_ediciones = $wpdb->prefix . 'flavor_pp_ediciones';

        // Verificar fase de votación
        $edicion = $wpdb->get_row(
            "SELECT * FROM $tabla_ediciones WHERE fase = 'votacion' ORDER BY anio DESC LIMIT 1"
        );

        if (!$edicion) {
            return [
                'success' => false,
                'error' => 'No estamos en fase de votación actualmente.',
            ];
        }

        $proyecto_ids = $params['proyecto_ids'] ?? [];
        if (!is_array($proyecto_ids)) {
            $proyecto_ids = [$proyecto_ids];
        }

        $settings = $this->settings;
        $max_votos = $settings['votos_maximos_por_persona'];

        if (count($proyecto_ids) > $max_votos) {
            return [
                'success' => false,
                'error' => "Solo puedes votar hasta {$max_votos} proyectos.",
            ];
        }

        // Limpiar votos anteriores
        $tabla_votos = $wpdb->prefix . 'flavor_pp_votos';
        $wpdb->delete(
            $tabla_votos,
            ['usuario_id' => $usuario_id, 'edicion_id' => $edicion->id],
            ['%d', '%d']
        );

        // Registrar nuevos votos
        $votos_registrados = 0;
        foreach ($proyecto_ids as $prioridad => $proyecto_id) {
            $resultado = $wpdb->insert(
                $tabla_votos,
                [
                    'proyecto_id' => absint($proyecto_id),
                    'edicion_id' => $edicion->id,
                    'usuario_id' => $usuario_id,
                    'prioridad' => $prioridad + 1,
                ],
                ['%d', '%d', '%d', '%d']
            );

            if ($resultado !== false) {
                $votos_registrados++;
            }
        }

        // Actualizar contadores
        $this->actualizar_contadores_votos($edicion->id);

        return [
            'success' => true,
            'votos_registrados' => $votos_registrados,
            'mensaje' => "¡Voto registrado! Has votado {$votos_registrados} proyectos.",
        ];
    }

    /**
     * Actualiza contadores de votos
     */
    private function actualizar_contadores_votos($edicion_id) {
        global $wpdb;
        $tabla_votos = $wpdb->prefix . 'flavor_pp_votos';
        $tabla_proyectos = $wpdb->prefix . 'flavor_pp_proyectos';

        // Actualizar votos por proyecto
        $wpdb->query("
            UPDATE $tabla_proyectos p
            SET votos_recibidos = (
                SELECT COUNT(*) FROM $tabla_votos v
                WHERE v.proyecto_id = p.id AND v.edicion_id = $edicion_id
            )
            WHERE p.edicion_id = $edicion_id
        ");
    }

    /**
     * {@inheritdoc}
     */
    public function get_tool_definitions() {
        return [
            [
                'name' => 'pp_info',
                'description' => 'Ver información de los presupuestos participativos actuales',
                'input_schema' => [
                    'type' => 'object',
                    'properties' => [],
                ],
            ],
            [
                'name' => 'pp_proponer',
                'description' => 'Proponer un proyecto para presupuestos participativos',
                'input_schema' => [
                    'type' => 'object',
                    'properties' => [
                        'titulo' => ['type' => 'string', 'description' => 'Título del proyecto'],
                        'descripcion' => ['type' => 'string', 'description' => 'Descripción detallada'],
                        'categoria' => ['type' => 'string', 'description' => 'Categoría'],
                        'presupuesto' => ['type' => 'number', 'description' => 'Presupuesto estimado en euros'],
                    ],
                    'required' => ['titulo', 'descripcion', 'presupuesto'],
                ],
            ],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function get_knowledge_base() {
        return <<<KNOWLEDGE
**Presupuestos Participativos**

Democracia económica directa: los vecinos deciden en qué se invierte parte del presupuesto municipal.

**Fases del proceso:**
1. Propuestas: Cualquier vecino puede proponer proyectos
2. Evaluación: Técnicos evalúan viabilidad y coste
3. Votación: Los vecinos votan sus proyectos favoritos
4. Implementación: Se ejecutan los proyectos ganadores

**Tipos de proyectos:**
- Infraestructura: Arreglos, mejoras urbanas
- Medio ambiente: Zonas verdes, reciclaje
- Cultura: Actividades, espacios culturales
- Deporte: Instalaciones deportivas
- Social: Servicios sociales, inclusión
- Educación: Formación, bibliotecas
- Accesibilidad: Rampas, adaptaciones

**Reglas:**
- Cada vecino puede votar hasta 3 proyectos
- Los proyectos se ordenan por votos recibidos
- Se aprueban en orden hasta agotar presupuesto
- Cada proyecto tiene un presupuesto mínimo y máximo
KNOWLEDGE;
    }

    /**
     * {@inheritdoc}
     */
    public function get_faqs() {
        return [
            [
                'pregunta' => '¿Qué son los presupuestos participativos?',
                'respuesta' => 'Es un proceso donde los vecinos proponemos y votamos proyectos para el barrio, y el ayuntamiento ejecuta los más votados.',
            ],
            [
                'pregunta' => '¿Cuántos proyectos puedo votar?',
                'respuesta' => 'Normalmente puedes votar hasta 3 proyectos, eligiendo tus favoritos.',
            ],
        ];
    }

    /**
     * Componentes web del módulo
     */
    public function get_web_components() {
        return [
            'hero' => [
                'label' => __('Hero Presupuestos', 'flavor-chat-ia'),
                'description' => __('Sección hero con fase actual del proceso', 'flavor-chat-ia'),
                'category' => 'hero',
                'icon' => 'dashicons-money-alt',
                'fields' => [
                    'titulo' => [
                        'type' => 'text',
                        'label' => __('Título', 'flavor-chat-ia'),
                        'default' => __('Presupuestos Participativos', 'flavor-chat-ia'),
                    ],
                    'subtitulo' => [
                        'type' => 'textarea',
                        'label' => __('Subtítulo', 'flavor-chat-ia'),
                        'default' => __('Decide en qué se invierte el presupuesto de tu barrio', 'flavor-chat-ia'),
                    ],
                    'mostrar_fase' => [
                        'type' => 'toggle',
                        'label' => __('Mostrar fase actual', 'flavor-chat-ia'),
                        'default' => true,
                    ],
                    'mostrar_presupuesto' => [
                        'type' => 'toggle',
                        'label' => __('Mostrar presupuesto total', 'flavor-chat-ia'),
                        'default' => true,
                    ],
                ],
                'template' => 'presupuestos-participativos/hero',
            ],
            'proyectos_grid' => [
                'label' => __('Grid de Proyectos', 'flavor-chat-ia'),
                'description' => __('Listado de proyectos propuestos', 'flavor-chat-ia'),
                'category' => 'listings',
                'icon' => 'dashicons-portfolio',
                'fields' => [
                    'titulo' => [
                        'type' => 'text',
                        'label' => __('Título', 'flavor-chat-ia'),
                        'default' => __('Proyectos Propuestos', 'flavor-chat-ia'),
                    ],
                    'categoria' => [
                        'type' => 'select',
                        'label' => __('Filtrar por categoría', 'flavor-chat-ia'),
                        'options' => ['todas', 'infraestructura', 'cultura', 'medio_ambiente', 'social'],
                        'default' => 'todas',
                    ],
                    'ordenar' => [
                        'type' => 'select',
                        'label' => __('Ordenar por', 'flavor-chat-ia'),
                        'options' => ['votos', 'coste', 'recientes'],
                        'default' => 'votos',
                    ],
                    'limite' => [
                        'type' => 'number',
                        'label' => __('Número máximo', 'flavor-chat-ia'),
                        'default' => 9,
                    ],
                ],
                'template' => 'presupuestos-participativos/proyectos-grid',
            ],
            'fases_proceso' => [
                'label' => __('Fases del Proceso', 'flavor-chat-ia'),
                'description' => __('Timeline del proceso participativo', 'flavor-chat-ia'),
                'category' => 'content',
                'icon' => 'dashicons-backup',
                'fields' => [
                    'titulo' => [
                        'type' => 'text',
                        'label' => __('Título', 'flavor-chat-ia'),
                        'default' => __('¿Cómo funciona?', 'flavor-chat-ia'),
                    ],
                ],
                'template' => 'presupuestos-participativos/fases',
            ],
            'resultados' => [
                'label' => __('Resultados Votación', 'flavor-chat-ia'),
                'description' => __('Proyectos ganadores y estadísticas', 'flavor-chat-ia'),
                'category' => 'content',
                'icon' => 'dashicons-chart-bar',
                'fields' => [
                    'titulo' => [
                        'type' => 'text',
                        'label' => __('Título', 'flavor-chat-ia'),
                        'default' => __('Resultados', 'flavor-chat-ia'),
                    ],
                    'edicion' => [
                        'type' => 'select',
                        'label' => __('Edición', 'flavor-chat-ia'),
                        'options' => ['actual', 'anterior'],
                        'default' => 'actual',
                    ],
                ],
                'template' => 'presupuestos-participativos/resultados',
            ],
            'cta_proponer' => [
                'label' => __('CTA Proponer Proyecto', 'flavor-chat-ia'),
                'description' => __('Llamada a acción para proponer proyecto', 'flavor-chat-ia'),
                'category' => 'cta',
                'icon' => 'dashicons-plus-alt',
                'fields' => [
                    'titulo' => [
                        'type' => 'text',
                        'label' => __('Título', 'flavor-chat-ia'),
                        'default' => __('¿Tienes un proyecto para el barrio?', 'flavor-chat-ia'),
                    ],
                    'boton_texto' => [
                        'type' => 'text',
                        'label' => __('Texto del botón', 'flavor-chat-ia'),
                        'default' => __('Proponer Proyecto', 'flavor-chat-ia'),
                    ],
                ],
                'template' => 'presupuestos-participativos/cta-proponer',
            ],
        ];
    }
}

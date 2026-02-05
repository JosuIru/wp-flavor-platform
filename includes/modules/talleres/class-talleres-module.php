<?php
/**
 * Módulo de Talleres para Chat IA
 *
 * @package FlavorChatIA
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Módulo de Talleres - Talleres prácticos comunitarios
 */
class Flavor_Chat_Talleres_Module extends Flavor_Chat_Module_Base {

    /**
     * Constructor
     */
    public function __construct() {
        $this->id = 'talleres';
        $this->name = __('Talleres Prácticos', 'flavor-chat-ia');
        $this->description = __('Talleres prácticos y workshops organizados por y para la comunidad.', 'flavor-chat-ia');

        parent::__construct();
    }

    /**
     * {@inheritdoc}
     */
    public function can_activate() {
        global $wpdb;
        $tabla_talleres = $wpdb->prefix . 'flavor_talleres';

        return Flavor_Chat_Helpers::tabla_existe($tabla_talleres);
    }

    /**
     * {@inheritdoc}
     */
    public function get_activation_error() {
        if (!$this->can_activate()) {
            return __('Las tablas de Talleres no están creadas. Se crearán automáticamente al activar.', 'flavor-chat-ia');
        }
        return '';
    }

    /**
     * {@inheritdoc}
     */
    protected function get_default_settings() {
        return [
            'disponible_app' => 'cliente',
            'requiere_aprobacion_organizadores' => false,
            'permite_talleres_gratuitos' => true,
            'permite_talleres_pago' => true,
            'comision_talleres_pago' => 10,
            'max_participantes_por_taller' => 20,
            'min_participantes_para_confirmar' => 5,
            'permite_lista_espera' => true,
            'dias_anticipacion_cancelacion' => 2,
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
        $tabla_talleres = $wpdb->prefix . 'flavor_talleres';

        if (!Flavor_Chat_Helpers::tabla_existe($tabla_talleres)) {
            $this->create_tables();
        }
    }

    /**
     * Crea las tablas necesarias
     */
    private function create_tables() {
        global $wpdb;
        $charset_collate = $wpdb->get_charset_collate();

        $tabla_talleres = $wpdb->prefix . 'flavor_talleres';
        $tabla_sesiones = $wpdb->prefix . 'flavor_talleres_sesiones';
        $tabla_inscripciones = $wpdb->prefix . 'flavor_talleres_inscripciones';

        $sql_talleres = "CREATE TABLE IF NOT EXISTS $tabla_talleres (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            organizador_id bigint(20) unsigned NOT NULL,
            titulo varchar(255) NOT NULL,
            descripcion text NOT NULL,
            categoria varchar(100) DEFAULT NULL,
            tipo enum('puntual','serie') DEFAULT 'puntual',
            nivel enum('principiante','intermedio','avanzado','todos') DEFAULT 'todos',
            duracion_horas int(11) NOT NULL,
            max_participantes int(11) DEFAULT 20,
            min_participantes int(11) DEFAULT 5,
            precio decimal(10,2) DEFAULT 0,
            materiales_incluidos tinyint(1) DEFAULT 0,
            materiales_necesarios text DEFAULT NULL,
            que_aprenderas text DEFAULT NULL,
            requisitos text DEFAULT NULL,
            imagen_portada varchar(500) DEFAULT NULL,
            ubicacion varchar(500) DEFAULT NULL,
            ubicacion_lat decimal(10,7) DEFAULT NULL,
            ubicacion_lng decimal(10,7) DEFAULT NULL,
            inscritos_actuales int(11) DEFAULT 0,
            valoracion_media decimal(3,2) DEFAULT 0,
            numero_valoraciones int(11) DEFAULT 0,
            estado enum('borrador','publicado','confirmado','en_curso','finalizado','cancelado') DEFAULT 'borrador',
            fecha_creacion datetime DEFAULT CURRENT_TIMESTAMP,
            fecha_actualizacion datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY organizador_id (organizador_id),
            KEY categoria (categoria),
            KEY estado (estado)
        ) $charset_collate;";

        $sql_sesiones = "CREATE TABLE IF NOT EXISTS $tabla_sesiones (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            taller_id bigint(20) unsigned NOT NULL,
            numero_sesion int(11) DEFAULT 1,
            fecha_hora datetime NOT NULL,
            duracion_minutos int(11) NOT NULL,
            ubicacion varchar(500) DEFAULT NULL,
            ubicacion_lat decimal(10,7) DEFAULT NULL,
            ubicacion_lng decimal(10,7) DEFAULT NULL,
            notas text DEFAULT NULL,
            asistencia_confirmada int(11) DEFAULT 0,
            estado enum('programada','en_curso','finalizada','cancelada') DEFAULT 'programada',
            fecha_creacion datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY taller_id (taller_id),
            KEY fecha_hora (fecha_hora),
            KEY estado (estado)
        ) $charset_collate;";

        $sql_inscripciones = "CREATE TABLE IF NOT EXISTS $tabla_inscripciones (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            taller_id bigint(20) unsigned NOT NULL,
            participante_id bigint(20) unsigned NOT NULL,
            precio_pagado decimal(10,2) DEFAULT 0,
            estado_pago enum('pendiente','pagado','reembolsado') DEFAULT 'pendiente',
            lista_espera tinyint(1) DEFAULT 0,
            posicion_espera int(11) DEFAULT NULL,
            asistencias int(11) DEFAULT 0,
            sesiones_totales int(11) DEFAULT 0,
            valoracion int(11) DEFAULT NULL,
            comentario_valoracion text DEFAULT NULL,
            estado enum('confirmada','cancelada','completada','ausente') DEFAULT 'confirmada',
            fecha_inscripcion datetime DEFAULT CURRENT_TIMESTAMP,
            fecha_cancelacion datetime DEFAULT NULL,
            PRIMARY KEY (id),
            UNIQUE KEY taller_participante (taller_id, participante_id),
            KEY participante_id (participante_id),
            KEY estado (estado)
        ) $charset_collate;";

        require_once ABSPATH . 'wp-admin/includes/upgrade.php';
        dbDelta($sql_talleres);
        dbDelta($sql_sesiones);
        dbDelta($sql_inscripciones);
    }

    /**
     * {@inheritdoc}
     */
    public function get_actions() {
        return [
            'talleres_disponibles' => [
                'description' => 'Listar talleres disponibles',
                'params' => ['categoria', 'fecha_desde'],
            ],
            'detalle_taller' => [
                'description' => 'Ver detalles del taller',
                'params' => ['taller_id'],
            ],
            'inscribirse' => [
                'description' => 'Inscribirse en taller',
                'params' => ['taller_id'],
            ],
            'cancelar_inscripcion' => [
                'description' => 'Cancelar inscripción',
                'params' => ['inscripcion_id'],
            ],
            'mis_talleres_inscritos' => [
                'description' => 'Talleres en los que estoy inscrito',
                'params' => [],
            ],
            'mis_talleres_organizador' => [
                'description' => 'Talleres que organizo',
                'params' => [],
            ],
            'marcar_asistencia' => [
                'description' => 'Marcar asistencia a sesión (organizador)',
                'params' => ['sesion_id', 'participante_id'],
            ],
            'valorar_taller' => [
                'description' => 'Valorar taller completado',
                'params' => ['taller_id', 'valoracion', 'comentario'],
            ],
            // Admin/Organizador actions
            'crear_taller' => [
                'description' => 'Crear nuevo taller (organizador)',
                'params' => ['titulo', 'descripcion', 'categoria'],
            ],
            'estadisticas_taller' => [
                'description' => 'Estadísticas del taller (admin)',
                'params' => ['taller_id'],
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
     * Acción: Talleres disponibles
     */
    private function action_talleres_disponibles($params) {
        global $wpdb;
        $tabla_talleres = $wpdb->prefix . 'flavor_talleres';
        $tabla_sesiones = $wpdb->prefix . 'flavor_talleres_sesiones';

        $where = ["t.estado IN ('publicado', 'confirmado')"];
        $prepare_values = [];

        if (!empty($params['categoria'])) {
            $where[] = 't.categoria = %s';
            $prepare_values[] = sanitize_text_field($params['categoria']);
        }

        if (!empty($params['fecha_desde'])) {
            $where[] = 's.fecha_hora >= %s';
            $prepare_values[] = sanitize_text_field($params['fecha_desde']);
        } else {
            $where[] = 's.fecha_hora >= NOW()';
        }

        $sql = "SELECT t.*, s.fecha_hora as proxima_sesion
                FROM $tabla_talleres t
                INNER JOIN $tabla_sesiones s ON t.id = s.taller_id
                WHERE " . implode(' AND ', $where) . "
                AND s.estado = 'programada'
                GROUP BY t.id
                ORDER BY s.fecha_hora ASC
                LIMIT 50";

        if (!empty($prepare_values)) {
            $talleres = $wpdb->get_results($wpdb->prepare($sql, ...$prepare_values));
        } else {
            $talleres = $wpdb->get_results($sql);
        }

        return [
            'success' => true,
            'talleres' => array_map(function($t) {
                $organizador = get_userdata($t->organizador_id);
                return [
                    'id' => $t->id,
                    'titulo' => $t->titulo,
                    'descripcion' => wp_trim_words($t->descripcion, 30),
                    'organizador' => $organizador ? $organizador->display_name : 'Organizador',
                    'categoria' => $t->categoria,
                    'nivel' => $t->nivel,
                    'duracion_horas' => $t->duracion_horas,
                    'precio' => floatval($t->precio),
                    'plazas_disponibles' => max(0, $t->max_participantes - $t->inscritos_actuales),
                    'inscritos' => $t->inscritos_actuales,
                    'max_participantes' => $t->max_participantes,
                    'proxima_sesion' => date('d/m/Y H:i', strtotime($t->proxima_sesion)),
                    'ubicacion' => $t->ubicacion,
                    'valoracion' => floatval($t->valoracion_media),
                    'imagen' => $t->imagen_portada,
                ];
            }, $talleres),
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
            'inscribirse' => [
                'title' => __('Inscribirse en Taller', 'flavor-chat-ia'),
                'description' => __('Completa el formulario para inscribirte en este taller', 'flavor-chat-ia'),
                'fields' => [
                    'taller_id' => [
                        'type' => 'hidden',
                        'required' => true,
                    ],
                    'nombre_completo' => [
                        'type' => 'text',
                        'label' => __('Nombre completo', 'flavor-chat-ia'),
                        'required' => true,
                        'placeholder' => __('Tu nombre y apellidos', 'flavor-chat-ia'),
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
                        'placeholder' => __('600123456', 'flavor-chat-ia'),
                        'description' => __('Para contactarte en caso necesario', 'flavor-chat-ia'),
                    ],
                    'alergias' => [
                        'type' => 'textarea',
                        'label' => __('Alergias o requisitos especiales', 'flavor-chat-ia'),
                        'rows' => 3,
                        'placeholder' => __('Déjanos saber si tienes alguna alergia, restricción alimentaria o necesidad especial', 'flavor-chat-ia'),
                    ],
                    'acepto_condiciones' => [
                        'type' => 'checkbox',
                        'label' => __('Acepto las condiciones', 'flavor-chat-ia'),
                        'checkbox_label' => __('He leído y acepto las condiciones de participación', 'flavor-chat-ia'),
                        'required' => true,
                    ],
                ],
                'submit_text' => __('Confirmar Inscripción', 'flavor-chat-ia'),
                'success_message' => __('¡Inscripción confirmada! Recibirás un email de confirmación.', 'flavor-chat-ia'),
                'redirect_url' => '/talleres/mis-talleres/',
            ],
            'crear_taller' => [
                'title' => __('Crear Nuevo Taller', 'flavor-chat-ia'),
                'description' => __('Comparte tu conocimiento organizando un taller para la comunidad', 'flavor-chat-ia'),
                'fields' => [
                    'titulo' => [
                        'type' => 'text',
                        'label' => __('Título del taller', 'flavor-chat-ia'),
                        'required' => true,
                        'placeholder' => __('Ej: Introducción a la Costura', 'flavor-chat-ia'),
                    ],
                    'descripcion' => [
                        'type' => 'textarea',
                        'label' => __('Descripción', 'flavor-chat-ia'),
                        'required' => true,
                        'rows' => 5,
                        'placeholder' => __('Describe qué aprenderán los participantes...', 'flavor-chat-ia'),
                    ],
                    'categoria' => [
                        'type' => 'select',
                        'label' => __('Categoría', 'flavor-chat-ia'),
                        'required' => true,
                        'options' => [
                            'artesania' => __('Artesanía y manualidades', 'flavor-chat-ia'),
                            'cocina' => __('Cocina y conservas', 'flavor-chat-ia'),
                            'huerto' => __('Huerto urbano y jardinería', 'flavor-chat-ia'),
                            'tecnologia' => __('Tecnología y digital', 'flavor-chat-ia'),
                            'costura' => __('Costura y textil', 'flavor-chat-ia'),
                            'carpinteria' => __('Carpintería básica', 'flavor-chat-ia'),
                            'reparaciones' => __('Reparaciones domésticas', 'flavor-chat-ia'),
                            'reciclaje' => __('Reciclaje creativo', 'flavor-chat-ia'),
                        ],
                    ],
                    'nivel' => [
                        'type' => 'select',
                        'label' => __('Nivel', 'flavor-chat-ia'),
                        'required' => true,
                        'options' => [
                            'todos' => __('Todos los niveles', 'flavor-chat-ia'),
                            'principiante' => __('Principiante', 'flavor-chat-ia'),
                            'intermedio' => __('Intermedio', 'flavor-chat-ia'),
                            'avanzado' => __('Avanzado', 'flavor-chat-ia'),
                        ],
                        'default' => 'todos',
                    ],
                    'fecha_hora' => [
                        'type' => 'datetime-local',
                        'label' => __('Fecha y hora', 'flavor-chat-ia'),
                        'required' => true,
                    ],
                    'duracion_horas' => [
                        'type' => 'number',
                        'label' => __('Duración (horas)', 'flavor-chat-ia'),
                        'required' => true,
                        'min' => 1,
                        'max' => 8,
                        'default' => 2,
                    ],
                    'max_participantes' => [
                        'type' => 'number',
                        'label' => __('Máximo de participantes', 'flavor-chat-ia'),
                        'required' => true,
                        'min' => 3,
                        'max' => 50,
                        'default' => 15,
                    ],
                    'precio' => [
                        'type' => 'number',
                        'label' => __('Precio (€)', 'flavor-chat-ia'),
                        'step' => '0.01',
                        'min' => 0,
                        'default' => 0,
                        'description' => __('Deja en 0 si es gratuito', 'flavor-chat-ia'),
                    ],
                    'materiales_incluidos' => [
                        'type' => 'checkbox',
                        'label' => __('Materiales incluidos', 'flavor-chat-ia'),
                        'checkbox_label' => __('Los materiales están incluidos en el precio', 'flavor-chat-ia'),
                    ],
                    'materiales_necesarios' => [
                        'type' => 'textarea',
                        'label' => __('Materiales que deben traer', 'flavor-chat-ia'),
                        'rows' => 3,
                        'placeholder' => __('Lista de materiales que los participantes deben traer', 'flavor-chat-ia'),
                    ],
                    'ubicacion' => [
                        'type' => 'text',
                        'label' => __('Ubicación', 'flavor-chat-ia'),
                        'required' => true,
                        'placeholder' => __('Centro comunitario, Calle...', 'flavor-chat-ia'),
                    ],
                ],
                'submit_text' => __('Crear Taller', 'flavor-chat-ia'),
                'success_message' => __('Taller creado correctamente. Pendiente de aprobación por los coordinadores.', 'flavor-chat-ia'),
                'redirect_url' => '/talleres/mis-talleres-organizador/',
            ],
            'valorar_taller' => [
                'title' => __('Valorar Taller', 'flavor-chat-ia'),
                'fields' => [
                    'taller_id' => [
                        'type' => 'hidden',
                        'required' => true,
                    ],
                    'valoracion' => [
                        'type' => 'number',
                        'label' => __('Valoración', 'flavor-chat-ia'),
                        'required' => true,
                        'min' => 1,
                        'max' => 5,
                        'description' => __('De 1 a 5 estrellas', 'flavor-chat-ia'),
                    ],
                    'comentario' => [
                        'type' => 'textarea',
                        'label' => __('Comentario', 'flavor-chat-ia'),
                        'rows' => 4,
                        'placeholder' => __('Cuéntanos tu experiencia...', 'flavor-chat-ia'),
                    ],
                ],
                'submit_text' => __('Enviar Valoración', 'flavor-chat-ia'),
                'success_message' => __('¡Gracias por tu valoración!', 'flavor-chat-ia'),
            ],
        ];

        return $configs[$action_name] ?? [];
    }

    /**
     * Componentes web del módulo
     *
     * IA Features futuras:
     * - Recomendación de talleres según intereses del usuario
     * - Sugerencia de horarios óptimos según disponibilidad
     * - Matching entre instructores y aprendices
     * - Generación automática de certificados
     */
    public function get_web_components() {
        return [
            'hero_talleres' => [
                'label' => __('Hero Talleres', 'flavor-chat-ia'),
                'category' => 'hero',
                'icon' => 'dashicons-welcome-learn-more',
                'fields' => [
                    'titulo' => ['type' => 'text', 'default' => __('Talleres Prácticos', 'flavor-chat-ia')],
                    'subtitulo' => ['type' => 'textarea', 'default' => __('Aprende nuevas habilidades con tu comunidad', 'flavor-chat-ia')],
                    'imagen_fondo' => ['type' => 'image', 'default' => ''],
                    'mostrar_buscador' => ['type' => 'toggle', 'default' => true],
                ],
                'template' => 'talleres/hero',
            ],
            'talleres_grid' => [
                'label' => __('Grid de Talleres', 'flavor-chat-ia'),
                'category' => 'listings',
                'icon' => 'dashicons-grid-view',
                'fields' => [
                    'titulo' => ['type' => 'text', 'default' => __('Próximos Talleres', 'flavor-chat-ia')],
                    'columnas' => ['type' => 'select', 'options' => [2, 3, 4], 'default' => 3],
                    'limite' => ['type' => 'number', 'default' => 9],
                    'categoria' => ['type' => 'text', 'default' => ''],
                    'mostrar_instructor' => ['type' => 'toggle', 'default' => true],
                ],
                'template' => 'talleres/grid',
            ],
            'categorias_talleres' => [
                'label' => __('Categorías de Talleres', 'flavor-chat-ia'),
                'category' => 'navigation',
                'icon' => 'dashicons-category',
                'fields' => [
                    'titulo' => ['type' => 'text', 'default' => __('Explora por Categoría', 'flavor-chat-ia')],
                    'estilo' => ['type' => 'select', 'options' => ['grid', 'carrusel'], 'default' => 'grid'],
                    'mostrar_contador' => ['type' => 'toggle', 'default' => true],
                ],
                'template' => 'talleres/categorias',
            ],
            'cta_instructor' => [
                'label' => __('CTA Ser Instructor', 'flavor-chat-ia'),
                'category' => 'cta',
                'icon' => 'dashicons-megaphone',
                'fields' => [
                    'titulo' => ['type' => 'text', 'default' => __('Comparte tu Conocimiento', 'flavor-chat-ia')],
                    'descripcion' => ['type' => 'textarea', 'default' => __('Organiza tu propio taller y enseña a tu comunidad', 'flavor-chat-ia')],
                    'boton_texto' => ['type' => 'text', 'default' => __('Crear Taller', 'flavor-chat-ia')],
                    'boton_url' => ['type' => 'url', 'default' => '#'],
                    'color_fondo' => ['type' => 'color', 'default' => '#3b82f6'],
                ],
                'template' => 'talleres/cta-instructor',
            ],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function get_tool_definitions() {
        return [
            [
                'name' => 'talleres_disponibles',
                'description' => 'Ver talleres disponibles',
                'input_schema' => [
                    'type' => 'object',
                    'properties' => [
                        'categoria' => ['type' => 'string', 'description' => 'Categoría del taller'],
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
**Talleres Prácticos Comunitarios**

Aprende haciendo en talleres organizados por vecinos expertos.

**Categorías de talleres:**
- Artesanía y manualidades
- Reparaciones domésticas
- Cocina y conservas
- Huerto urbano y jardinería
- Tecnología y digital
- Costura y textil
- Carpintería básica
- Bricolaje
- Reciclaje creativo

**Tipos de talleres:**
- Puntuales: Una sesión única
- Serie: Varias sesiones consecutivas

**Qué incluyen:**
- Instrucción práctica
- Materiales (según taller)
- Espacio comunitario
- Grupo reducido
- Certificado de asistencia

**Organiza tu taller:**
1. Propón tu tema y fecha
2. Define materiales y precio
3. Espera confirmación
4. ¡Comparte tu conocimiento!

**Ventajas:**
- Aprendizaje práctico
- Grupos pequeños
- Precios accesibles
- Conoce a tus vecinos
- Desarrolla habilidades útiles
KNOWLEDGE;
    }

    /**
     * {@inheritdoc}
     */
    public function get_faqs() {
        return [
            [
                'pregunta' => '¿Qué pasa si el taller se cancela?',
                'respuesta' => 'Se cancela si no hay mínimo de participantes. Se reembolsa el 100% del importe.',
            ],
            [
                'pregunta' => '¿Están incluidos los materiales?',
                'respuesta' => 'Depende del taller. En la descripción se indica qué está incluido y qué debes traer.',
            ],
            [
                'pregunta' => '¿Puedo organizar un taller?',
                'respuesta' => 'Sí, cualquier vecino con conocimientos puede proponer un taller. Se revisa antes de publicar.',
            ],
        ];
    }
}

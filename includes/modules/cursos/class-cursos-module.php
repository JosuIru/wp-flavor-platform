<?php
/**
 * Módulo de Cursos y Formación para Chat IA
 *
 * @package FlavorChatIA
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Módulo de Cursos - Plataforma de formación comunitaria
 */
class Flavor_Chat_Cursos_Module extends Flavor_Chat_Module_Base {

    /**
     * Constructor
     */
    public function __construct() {
        $this->id = 'cursos';
        $this->name = __('Cursos y Formación', 'flavor-chat-ia');
        $this->description = __('Plataforma de cursos y formación comunitaria - aprende y enseña en tu comunidad.', 'flavor-chat-ia');

        parent::__construct();
    }

    /**
     * {@inheritdoc}
     */
    public function can_activate() {
        global $wpdb;
        $tabla_cursos = $wpdb->prefix . 'flavor_cursos';

        return Flavor_Chat_Helpers::tabla_existe($tabla_cursos);
    }

    /**
     * {@inheritdoc}
     */
    public function get_activation_error() {
        if (!$this->can_activate()) {
            return __('Las tablas de Cursos no están creadas. Se crearán automáticamente al activar.', 'flavor-chat-ia');
        }
        return '';
    }

    /**
     * {@inheritdoc}
     */
    protected function get_default_settings() {
        return [
            'disponible_app' => 'cliente',
            'requiere_aprobacion_instructores' => true,
            'permite_cursos_gratuitos' => true,
            'permite_cursos_pago' => true,
            'comision_cursos_pago' => 15,
            'max_alumnos_por_curso' => 30,
            'permite_certificados' => true,
            'requiere_evaluacion' => true,
            'permite_cursos_online' => true,
            'permite_cursos_presenciales' => true,
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
        $tabla_cursos = $wpdb->prefix . 'flavor_cursos';

        if (!Flavor_Chat_Helpers::tabla_existe($tabla_cursos)) {
            $this->create_tables();
        }
    }

    /**
     * Crea las tablas necesarias
     */
    private function create_tables() {
        global $wpdb;
        $charset_collate = $wpdb->get_charset_collate();

        $tabla_cursos = $wpdb->prefix . 'flavor_cursos';
        $tabla_lecciones = $wpdb->prefix . 'flavor_cursos_lecciones';
        $tabla_inscripciones = $wpdb->prefix . 'flavor_cursos_inscripciones';
        $tabla_progreso = $wpdb->prefix . 'flavor_cursos_progreso';
        $tabla_certificados = $wpdb->prefix . 'flavor_cursos_certificados';

        $sql_cursos = "CREATE TABLE IF NOT EXISTS $tabla_cursos (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            instructor_id bigint(20) unsigned NOT NULL,
            titulo varchar(255) NOT NULL,
            descripcion text NOT NULL,
            categoria varchar(100) DEFAULT NULL,
            nivel enum('principiante','intermedio','avanzado','todos') DEFAULT 'todos',
            modalidad enum('online','presencial','mixto') DEFAULT 'online',
            duracion_horas int(11) NOT NULL,
            max_alumnos int(11) DEFAULT 30,
            precio decimal(10,2) DEFAULT 0,
            es_gratuito tinyint(1) DEFAULT 1,
            requisitos text DEFAULT NULL,
            que_aprenderas text DEFAULT NULL,
            imagen_portada varchar(500) DEFAULT NULL,
            video_presentacion varchar(500) DEFAULT NULL,
            fecha_inicio datetime DEFAULT NULL,
            fecha_fin datetime DEFAULT NULL,
            horario varchar(255) DEFAULT NULL,
            ubicacion varchar(500) DEFAULT NULL,
            alumnos_inscritos int(11) DEFAULT 0,
            valoracion_media decimal(3,2) DEFAULT 0,
            numero_valoraciones int(11) DEFAULT 0,
            estado enum('borrador','publicado','en_curso','finalizado','cancelado') DEFAULT 'borrador',
            fecha_creacion datetime DEFAULT CURRENT_TIMESTAMP,
            fecha_actualizacion datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY instructor_id (instructor_id),
            KEY categoria (categoria),
            KEY estado (estado),
            KEY fecha_inicio (fecha_inicio)
        ) $charset_collate;";

        $sql_lecciones = "CREATE TABLE IF NOT EXISTS $tabla_lecciones (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            curso_id bigint(20) unsigned NOT NULL,
            numero_orden int(11) NOT NULL,
            titulo varchar(255) NOT NULL,
            descripcion text DEFAULT NULL,
            tipo enum('video','texto','quiz','archivo','enlace') DEFAULT 'texto',
            contenido longtext DEFAULT NULL,
            video_url varchar(500) DEFAULT NULL,
            archivo_url varchar(500) DEFAULT NULL,
            duracion_minutos int(11) DEFAULT NULL,
            es_gratuita tinyint(1) DEFAULT 0,
            fecha_publicacion datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY curso_id (curso_id),
            KEY numero_orden (numero_orden)
        ) $charset_collate;";

        $sql_inscripciones = "CREATE TABLE IF NOT EXISTS $tabla_inscripciones (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            curso_id bigint(20) unsigned NOT NULL,
            alumno_id bigint(20) unsigned NOT NULL,
            precio_pagado decimal(10,2) DEFAULT 0,
            progreso_porcentaje decimal(5,2) DEFAULT 0,
            fecha_ultima_actividad datetime DEFAULT NULL,
            estado enum('activa','completada','abandonada','suspendida') DEFAULT 'activa',
            certificado_emitido tinyint(1) DEFAULT 0,
            valoracion int(11) DEFAULT NULL,
            comentario_valoracion text DEFAULT NULL,
            fecha_inscripcion datetime DEFAULT CURRENT_TIMESTAMP,
            fecha_completado datetime DEFAULT NULL,
            PRIMARY KEY (id),
            UNIQUE KEY curso_alumno (curso_id, alumno_id),
            KEY alumno_id (alumno_id),
            KEY estado (estado)
        ) $charset_collate;";

        $sql_progreso = "CREATE TABLE IF NOT EXISTS $tabla_progreso (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            inscripcion_id bigint(20) unsigned NOT NULL,
            leccion_id bigint(20) unsigned NOT NULL,
            completada tinyint(1) DEFAULT 0,
            tiempo_dedicado_minutos int(11) DEFAULT 0,
            puntuacion decimal(5,2) DEFAULT NULL,
            fecha_inicio datetime DEFAULT CURRENT_TIMESTAMP,
            fecha_completado datetime DEFAULT NULL,
            PRIMARY KEY (id),
            UNIQUE KEY inscripcion_leccion (inscripcion_id, leccion_id),
            KEY leccion_id (leccion_id)
        ) $charset_collate;";

        $sql_certificados = "CREATE TABLE IF NOT EXISTS $tabla_certificados (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            inscripcion_id bigint(20) unsigned NOT NULL,
            curso_id bigint(20) unsigned NOT NULL,
            alumno_id bigint(20) unsigned NOT NULL,
            codigo_verificacion varchar(100) NOT NULL UNIQUE,
            nota_final decimal(5,2) DEFAULT NULL,
            fecha_emision datetime DEFAULT CURRENT_TIMESTAMP,
            pdf_url varchar(500) DEFAULT NULL,
            PRIMARY KEY (id),
            UNIQUE KEY inscripcion_id (inscripcion_id),
            KEY codigo_verificacion (codigo_verificacion),
            KEY alumno_id (alumno_id)
        ) $charset_collate;";

        require_once ABSPATH . 'wp-admin/includes/upgrade.php';
        dbDelta($sql_cursos);
        dbDelta($sql_lecciones);
        dbDelta($sql_inscripciones);
        dbDelta($sql_progreso);
        dbDelta($sql_certificados);
    }

    /**
     * {@inheritdoc}
     */
    public function get_actions() {
        return [
            'catalogo_cursos' => [
                'description' => 'Ver catálogo de cursos',
                'params' => ['categoria', 'nivel', 'modalidad'],
            ],
            'detalle_curso' => [
                'description' => 'Ver detalles del curso',
                'params' => ['curso_id'],
            ],
            'inscribirse' => [
                'description' => 'Inscribirse en curso',
                'params' => ['curso_id'],
            ],
            'mis_cursos' => [
                'description' => 'Mis cursos inscritos',
                'params' => [],
            ],
            'mis_cursos_instructor' => [
                'description' => 'Cursos que imparto',
                'params' => [],
            ],
            'ver_leccion' => [
                'description' => 'Ver lección del curso',
                'params' => ['leccion_id'],
            ],
            'marcar_completada' => [
                'description' => 'Marcar lección completada',
                'params' => ['leccion_id'],
            ],
            'valorar_curso' => [
                'description' => 'Valorar curso completado',
                'params' => ['curso_id', 'valoracion', 'comentario'],
            ],
            'solicitar_certificado' => [
                'description' => 'Solicitar certificado',
                'params' => ['curso_id'],
            ],
            // Admin/Instructor actions
            'crear_curso' => [
                'description' => 'Crear nuevo curso (instructor)',
                'params' => ['titulo', 'descripcion', 'categoria'],
            ],
            'estadisticas_curso' => [
                'description' => 'Estadísticas del curso (admin)',
                'params' => ['curso_id'],
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
     * Acción: Ver catálogo
     */
    private function action_catalogo_cursos($params) {
        global $wpdb;
        $tabla_cursos = $wpdb->prefix . 'flavor_cursos';

        $where = ["estado = 'publicado'"];
        $prepare_values = [];

        if (!empty($params['categoria'])) {
            $where[] = 'categoria = %s';
            $prepare_values[] = sanitize_text_field($params['categoria']);
        }

        if (!empty($params['nivel'])) {
            $where[] = 'nivel = %s';
            $prepare_values[] = sanitize_text_field($params['nivel']);
        }

        if (!empty($params['modalidad'])) {
            $where[] = 'modalidad = %s';
            $prepare_values[] = sanitize_text_field($params['modalidad']);
        }

        $sql = "SELECT * FROM $tabla_cursos WHERE " . implode(' AND ', $where) . " ORDER BY fecha_inicio DESC LIMIT 50";

        if (!empty($prepare_values)) {
            $cursos = $wpdb->get_results($wpdb->prepare($sql, ...$prepare_values));
        } else {
            $cursos = $wpdb->get_results($sql);
        }

        return [
            'success' => true,
            'cursos' => array_map(function($c) {
                $instructor = get_userdata($c->instructor_id);
                return [
                    'id' => $c->id,
                    'titulo' => $c->titulo,
                    'descripcion' => wp_trim_words($c->descripcion, 30),
                    'instructor' => $instructor ? $instructor->display_name : 'Instructor',
                    'categoria' => $c->categoria,
                    'nivel' => $c->nivel,
                    'modalidad' => $c->modalidad,
                    'duracion_horas' => $c->duracion_horas,
                    'precio' => floatval($c->precio),
                    'es_gratuito' => (bool)$c->es_gratuito,
                    'alumnos' => $c->alumnos_inscritos,
                    'valoracion' => floatval($c->valoracion_media),
                    'imagen' => $c->imagen_portada,
                ];
            }, $cursos),
        ];
    }

    /**
     * Componentes web del módulo
     */
    public function get_web_components() {
        return [
            'hero_cursos' => [
                'label' => __('Hero Cursos', 'flavor-chat-ia'),
                'description' => __('Sección hero para plataforma de cursos', 'flavor-chat-ia'),
                'category' => 'hero',
                'icon' => 'dashicons-welcome-learn-more',
                'fields' => [
                    'titulo' => ['type' => 'text', 'label' => __('Título', 'flavor-chat-ia'), 'default' => __('Aprende con Tu Comunidad', 'flavor-chat-ia')],
                    'subtitulo' => ['type' => 'textarea', 'label' => __('Subtítulo', 'flavor-chat-ia'), 'default' => __('Cursos impartidos por vecinos expertos', 'flavor-chat-ia')],
                    'imagen_fondo' => ['type' => 'image', 'label' => __('Imagen de fondo', 'flavor-chat-ia'), 'default' => ''],
                    'mostrar_buscador' => ['type' => 'toggle', 'label' => __('Mostrar buscador', 'flavor-chat-ia'), 'default' => true],
                ],
                'template' => 'cursos/hero',
            ],
            'cursos_grid' => [
                'label' => __('Grid de Cursos', 'flavor-chat-ia'),
                'description' => __('Catálogo de cursos disponibles', 'flavor-chat-ia'),
                'category' => 'listings',
                'icon' => 'dashicons-grid-view',
                'fields' => [
                    'titulo' => ['type' => 'text', 'label' => __('Título', 'flavor-chat-ia'), 'default' => __('Cursos Disponibles', 'flavor-chat-ia')],
                    'columnas' => ['type' => 'select', 'label' => __('Columnas', 'flavor-chat-ia'), 'options' => [2, 3, 4], 'default' => 3],
                    'limite' => ['type' => 'number', 'label' => __('Cantidad', 'flavor-chat-ia'), 'default' => 9],
                    'filtro_categoria' => ['type' => 'text', 'label' => __('Categoría', 'flavor-chat-ia'), 'default' => ''],
                    'filtro_nivel' => ['type' => 'select', 'label' => __('Nivel', 'flavor-chat-ia'), 'options' => ['', 'principiante', 'intermedio', 'avanzado'], 'default' => ''],
                ],
                'template' => 'cursos/grid',
            ],
            'categorias_cursos' => [
                'label' => __('Categorías de Cursos', 'flavor-chat-ia'),
                'description' => __('Navegación por categorías', 'flavor-chat-ia'),
                'category' => 'navigation',
                'icon' => 'dashicons-category',
                'fields' => [
                    'titulo' => ['type' => 'text', 'label' => __('Título', 'flavor-chat-ia'), 'default' => __('Explora por Categoría', 'flavor-chat-ia')],
                    'estilo' => ['type' => 'select', 'label' => __('Estilo', 'flavor-chat-ia'), 'options' => ['tarjetas', 'iconos'], 'default' => 'tarjetas'],
                ],
                'template' => 'cursos/categorias',
            ],
            'cta_instructor' => [
                'label' => __('CTA Instructor', 'flavor-chat-ia'),
                'description' => __('Llamada a la acción para instructores', 'flavor-chat-ia'),
                'category' => 'cta',
                'icon' => 'dashicons-welcome-learn-more',
                'fields' => [
                    'titulo' => ['type' => 'text', 'label' => __('Título', 'flavor-chat-ia'), 'default' => __('Comparte tu Conocimiento', 'flavor-chat-ia')],
                    'texto' => ['type' => 'textarea', 'label' => __('Texto', 'flavor-chat-ia'), 'default' => __('Conviértete en instructor y enseña a tu comunidad', 'flavor-chat-ia')],
                    'boton_texto' => ['type' => 'text', 'label' => __('Texto botón', 'flavor-chat-ia'), 'default' => __('Crear Curso', 'flavor-chat-ia')],
                    'boton_url' => ['type' => 'url', 'label' => __('URL botón', 'flavor-chat-ia'), 'default' => '#'],
                    'color_fondo' => ['type' => 'color', 'label' => __('Color de fondo', 'flavor-chat-ia'), 'default' => '#10b981'],
                ],
                'template' => 'cursos/cta-instructor',
            ],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function get_tool_definitions() {
        return [
            [
                'name' => 'cursos_catalogo',
                'description' => 'Ver catálogo de cursos disponibles',
                'input_schema' => [
                    'type' => 'object',
                    'properties' => [
                        'categoria' => ['type' => 'string', 'description' => 'Categoría del curso'],
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
**Plataforma de Cursos Comunitarios**

Aprende nuevas habilidades o comparte tu conocimiento con la comunidad.

**Categorías disponibles:**
- Tecnología y programación
- Idiomas
- Cocina y gastronomía
- Artesanía y manualidades
- Jardinería urbana
- Reparaciones domésticas
- Música y arte
- Salud y bienestar

**Modalidades:**
- Online: Aprende a tu ritmo
- Presencial: Clases en espacios comunitarios
- Mixto: Lo mejor de ambos

**Ventajas:**
- Cursos impartidos por vecinos expertos
- Precios accesibles o gratuitos
- Certificados de finalización
- Comunidad de apoyo
- Aprende haciendo

**Conviértete en instructor:**
1. Propón tu curso
2. Crea el contenido
3. Establece fechas y precio
4. Comparte tu conocimiento
5. Gana reconocimiento (y dinero)
KNOWLEDGE;
    }

    /**
     * {@inheritdoc}
     */
    public function get_faqs() {
        return [
            [
                'pregunta' => '¿Obtengo un certificado?',
                'respuesta' => 'Sí, al completar el curso y aprobar la evaluación recibes un certificado digital verificable.',
            ],
            [
                'pregunta' => '¿Puedo enseñar un curso?',
                'respuesta' => 'Sí, cualquier vecino con conocimientos puede proponer un curso. Debe ser aprobado primero.',
            ],
            [
                'pregunta' => '¿Qué pasa si no puedo asistir a todas las clases?',
                'respuesta' => 'Los cursos online quedan grabados. En presenciales, depende del instructor.',
            ],
        ];
    }
}

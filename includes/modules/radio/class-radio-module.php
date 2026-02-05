<?php
/**
 * Módulo de Radio Comunitaria para Chat IA
 *
 * @package FlavorChatIA
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Módulo de Radio - Emisora de radio comunitaria en streaming
 */
class Flavor_Chat_Radio_Module extends Flavor_Chat_Module_Base {

    /**
     * Constructor
     */
    public function __construct() {
        $this->id = 'radio';
        $this->name = __('Radio Comunitaria', 'flavor-chat-ia');
        $this->description = __('Emisora de radio comunitaria en streaming con programación y participación ciudadana.', 'flavor-chat-ia');

        parent::__construct();
    }

    /**
     * {@inheritdoc}
     */
    public function can_activate() {
        global $wpdb;
        $tabla_programas = $wpdb->prefix . 'flavor_radio_programas';

        return Flavor_Chat_Helpers::tabla_existe($tabla_programas);
    }

    /**
     * {@inheritdoc}
     */
    public function get_activation_error() {
        if (!$this->can_activate()) {
            return __('Las tablas de Radio no están creadas. Se crearán automáticamente al activar.', 'flavor-chat-ia');
        }
        return '';
    }

    /**
     * {@inheritdoc}
     */
    protected function get_default_settings() {
        return [
            'url_stream' => '',
            'frecuencia_fm' => '',
            'permite_locutores_comunidad' => true,
            'duracion_maxima_programa' => 120,
            'requiere_aprobacion_programas' => true,
            'permite_dedicatorias' => true,
            'chat_en_vivo' => true,
            'grabacion_automatica' => true,
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
        $tabla_programas = $wpdb->prefix . 'flavor_radio_programas';

        if (!Flavor_Chat_Helpers::tabla_existe($tabla_programas)) {
            $this->create_tables();
        }
    }

    /**
     * Crea las tablas necesarias
     */
    private function create_tables() {
        global $wpdb;
        $charset_collate = $wpdb->get_charset_collate();

        $tabla_programas = $wpdb->prefix . 'flavor_radio_programas';
        $tabla_emision = $wpdb->prefix . 'flavor_radio_programacion';
        $tabla_dedicatorias = $wpdb->prefix . 'flavor_radio_dedicatorias';

        $sql_programas = "CREATE TABLE IF NOT EXISTS $tabla_programas (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            nombre varchar(255) NOT NULL,
            descripcion text NOT NULL,
            locutor_id bigint(20) unsigned NOT NULL,
            imagen_url varchar(500) DEFAULT NULL,
            categoria varchar(100) DEFAULT NULL,
            frecuencia enum('semanal','quincenal','mensual','especial') DEFAULT 'semanal',
            dia_semana tinyint(1) DEFAULT NULL COMMENT '1=Lunes, 7=Domingo',
            hora_inicio time DEFAULT NULL,
            duracion_minutos int(11) DEFAULT 60,
            estado enum('activo','pausado','finalizado') DEFAULT 'activo',
            oyentes_promedio int(11) DEFAULT 0,
            fecha_creacion datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY locutor_id (locutor_id),
            KEY estado (estado)
        ) $charset_collate;";

        $sql_emision = "CREATE TABLE IF NOT EXISTS $tabla_emision (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            programa_id bigint(20) unsigned DEFAULT NULL,
            tipo enum('programa','musica','noticia','anuncio') DEFAULT 'programa',
            titulo varchar(255) NOT NULL,
            descripcion text DEFAULT NULL,
            fecha_hora_inicio datetime NOT NULL,
            fecha_hora_fin datetime NOT NULL,
            archivo_url varchar(500) DEFAULT NULL,
            en_vivo tinyint(1) DEFAULT 0,
            oyentes_pico int(11) DEFAULT 0,
            estado enum('programado','en_emision','finalizado','cancelado') DEFAULT 'programado',
            fecha_creacion datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY programa_id (programa_id),
            KEY fecha_hora_inicio (fecha_hora_inicio),
            KEY estado (estado)
        ) $charset_collate;";

        $sql_dedicatorias = "CREATE TABLE IF NOT EXISTS $tabla_dedicatorias (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            usuario_id bigint(20) unsigned NOT NULL,
            de varchar(100) NOT NULL,
            para varchar(100) NOT NULL,
            mensaje text NOT NULL,
            cancion_titulo varchar(255) DEFAULT NULL,
            cancion_artista varchar(255) DEFAULT NULL,
            estado enum('pendiente','aprobada','rechazada','emitida') DEFAULT 'pendiente',
            emision_id bigint(20) unsigned DEFAULT NULL,
            fecha_solicitud datetime DEFAULT CURRENT_TIMESTAMP,
            fecha_emision datetime DEFAULT NULL,
            PRIMARY KEY (id),
            KEY usuario_id (usuario_id),
            KEY estado (estado)
        ) $charset_collate;";

        require_once ABSPATH . 'wp-admin/includes/upgrade.php';
        dbDelta($sql_programas);
        dbDelta($sql_emision);
        dbDelta($sql_dedicatorias);
    }

    /**
     * {@inheritdoc}
     */
    public function get_actions() {
        return [
            'en_vivo' => [
                'description' => 'Obtener stream y programa actual',
                'params' => [],
            ],
            'programacion' => [
                'description' => 'Ver programación de la semana',
                'params' => ['fecha_inicio'],
            ],
            'programas' => [
                'description' => 'Listar programas de radio',
                'params' => ['categoria'],
            ],
            'enviar_dedicatoria' => [
                'description' => 'Enviar dedicatoria musical',
                'params' => ['de', 'para', 'mensaje', 'cancion'],
            ],
            'dedicatorias_pendientes' => [
                'description' => 'Ver dedicatorias pendientes (locutor)',
                'params' => [],
            ],
            'estadisticas_oyentes' => [
                'description' => 'Estadísticas de audiencia (admin)',
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
     * Acción: Obtener emisión en vivo
     */
    private function action_en_vivo($params) {
        $settings = $this->settings;

        global $wpdb;
        $tabla_emision = $wpdb->prefix . 'flavor_radio_programacion';

        // Buscar programa en emisión ahora
        $ahora = current_time('mysql');
        $en_vivo = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM $tabla_emision
             WHERE estado = 'en_emision'
             AND fecha_hora_inicio <= %s
             AND fecha_hora_fin >= %s
             ORDER BY fecha_hora_inicio DESC
             LIMIT 1",
            $ahora,
            $ahora
        ));

        return [
            'success' => true,
            'stream_url' => $settings['url_stream'] ?? '',
            'frecuencia_fm' => $settings['frecuencia_fm'] ?? '',
            'en_vivo' => $en_vivo ? [
                'titulo' => $en_vivo->titulo,
                'descripcion' => $en_vivo->descripcion,
                'inicio' => date('H:i', strtotime($en_vivo->fecha_hora_inicio)),
                'fin' => date('H:i', strtotime($en_vivo->fecha_hora_fin)),
                'oyentes' => $en_vivo->oyentes_pico,
            ] : null,
        ];
    }

    /**
     * Componentes web del módulo
     *
     * IA Features futuras:
     * - Recomendaciones de programas según gustos del oyente
     * - Transcripción automática de programas
     * - Generación automática de resúmenes de episodios
     * - Análisis de audiencia y preferencias
     */
    public function get_web_components() {
        return [
            'hero_radio' => [
                'label' => __('Hero Radio', 'flavor-chat-ia'),
                'category' => 'hero',
                'icon' => 'dashicons-microphone',
                'fields' => [
                    'titulo' => ['type' => 'text', 'default' => __('Radio Comunitaria', 'flavor-chat-ia')],
                    'subtitulo' => ['type' => 'textarea', 'default' => __('La voz de tu barrio', 'flavor-chat-ia')],
                    'imagen_fondo' => ['type' => 'image', 'default' => ''],
                    'mostrar_reproductor' => ['type' => 'toggle', 'default' => true],
                ],
                'template' => 'radio/hero',
            ],
            'reproductor_radio' => [
                'label' => __('Reproductor de Radio', 'flavor-chat-ia'),
                'category' => 'content',
                'icon' => 'dashicons-controls-play',
                'fields' => [
                    'titulo' => ['type' => 'text', 'default' => __('En Directo', 'flavor-chat-ia')],
                    'mostrar_programa_actual' => ['type' => 'toggle', 'default' => true],
                    'mostrar_oyentes' => ['type' => 'toggle', 'default' => true],
                    'estilo' => ['type' => 'select', 'options' => ['compacto', 'completo'], 'default' => 'completo'],
                ],
                'template' => 'radio/reproductor',
            ],
            'programacion' => [
                'label' => __('Parrilla de Programación', 'flavor-chat-ia'),
                'category' => 'content',
                'icon' => 'dashicons-calendar-alt',
                'fields' => [
                    'titulo' => ['type' => 'text', 'default' => __('Programación', 'flavor-chat-ia')],
                    'vista' => ['type' => 'select', 'options' => ['dia', 'semana'], 'default' => 'semana'],
                    'mostrar_descripcion' => ['type' => 'toggle', 'default' => true],
                ],
                'template' => 'radio/programacion',
            ],
            'cta_locutor' => [
                'label' => __('CTA Ser Locutor', 'flavor-chat-ia'),
                'category' => 'cta',
                'icon' => 'dashicons-megaphone',
                'fields' => [
                    'titulo' => ['type' => 'text', 'default' => __('¿Quieres Tener tu Programa?', 'flavor-chat-ia')],
                    'descripcion' => ['type' => 'textarea', 'default' => __('Comparte tu voz y contenido en nuestra radio comunitaria', 'flavor-chat-ia')],
                    'boton_texto' => ['type' => 'text', 'default' => __('Proponer Programa', 'flavor-chat-ia')],
                    'boton_url' => ['type' => 'url', 'default' => '#'],
                    'color_fondo' => ['type' => 'color', 'default' => '#8b5cf6'],
                ],
                'template' => 'radio/cta-locutor',
            ],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function get_tool_definitions() {
        return [
            [
                'name' => 'radio_en_vivo',
                'description' => 'Escuchar radio comunitaria en vivo',
                'input_schema' => [
                    'type' => 'object',
                    'properties' => [],
                ],
            ],
            [
                'name' => 'radio_dedicatoria',
                'description' => 'Enviar dedicatoria musical',
                'input_schema' => [
                    'type' => 'object',
                    'properties' => [
                        'de' => ['type' => 'string', 'description' => 'Tu nombre'],
                        'para' => ['type' => 'string', 'description' => 'Destinatario'],
                        'mensaje' => ['type' => 'string', 'description' => 'Mensaje de dedicatoria'],
                    ],
                    'required' => ['de', 'para', 'mensaje'],
                ],
            ],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function get_knowledge_base() {
        return <<<KNOWLEDGE
**Radio Comunitaria**

Emisora de radio del barrio gestionada por y para la comunidad.

**Funcionalidades:**
- Escucha en vivo desde la app o FM
- Programación variada: noticias, música, debates
- Envía dedicatorias musicales
- Chat en vivo con locutores
- Participa como locutor

**Programación:**
- Lunes a Viernes: Noticias locales, entrevistas
- Fines de semana: Música, programas especiales
- Programas vecinales temáticos

**Cómo participar:**
- Propón tu programa
- Envía dedicatorias
- Llama o chatea durante emisiones
KNOWLEDGE;
    }

    /**
     * {@inheritdoc}
     */
    public function get_faqs() {
        return [
            [
                'pregunta' => '¿Cómo puedo tener mi programa?',
                'respuesta' => 'Envía tu propuesta desde la app con horario, temática y presentación.',
            ],
            [
                'pregunta' => '¿Las dedicatorias son gratuitas?',
                'respuesta' => 'Sí, enviar dedicatorias es completamente gratuito para todos los vecinos.',
            ],
        ];
    }
}

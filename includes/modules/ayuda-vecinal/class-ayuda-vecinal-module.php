<?php
/**
 * Módulo de Ayuda Vecinal para Chat IA
 *
 * @package FlavorChatIA
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Módulo de Ayuda Vecinal - Red de ayuda mutua entre vecinos
 */
class Flavor_Chat_Ayuda_Vecinal_Module extends Flavor_Chat_Module_Base {

    /**
     * Constructor
     */
    public function __construct() {
        $this->id = 'ayuda_vecinal';
        $this->name = __('Ayuda Vecinal', 'flavor-chat-ia');
        $this->description = __('Red de ayuda mutua entre vecinos - ofrece y solicita ayuda en tu comunidad.', 'flavor-chat-ia');

        parent::__construct();
    }

    /**
     * {@inheritdoc}
     */
    public function can_activate() {
        global $wpdb;
        $tabla_solicitudes = $wpdb->prefix . 'flavor_ayuda_solicitudes';

        return Flavor_Chat_Helpers::tabla_existe($tabla_solicitudes);
    }

    /**
     * {@inheritdoc}
     */
    public function get_activation_error() {
        if (!$this->can_activate()) {
            return __('Las tablas de Ayuda Vecinal no están creadas. Se crearán automáticamente al activar.', 'flavor-chat-ia');
        }
        return '';
    }

    /**
     * {@inheritdoc}
     */
    protected function get_default_settings() {
        return [
            'disponible_app' => 'cliente',
            'requiere_verificacion_usuarios' => true,
            'permite_valoraciones' => true,
            'sistema_puntos_solidaridad' => true,
            'puntos_por_ayuda' => 10,
            'categorias_ayuda' => [
                'compras',
                'cuidado_mayores',
                'cuidado_ninos',
                'mascotas',
                'transporte',
                'tecnologia',
                'tramites',
                'reparaciones',
                'companía',
                'otro',
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
        $tabla_solicitudes = $wpdb->prefix . 'flavor_ayuda_solicitudes';

        if (!Flavor_Chat_Helpers::tabla_existe($tabla_solicitudes)) {
            $this->create_tables();
        }
    }

    /**
     * Crea las tablas necesarias
     */
    private function create_tables() {
        global $wpdb;
        $charset_collate = $wpdb->get_charset_collate();

        $tabla_solicitudes = $wpdb->prefix . 'flavor_ayuda_solicitudes';
        $tabla_ofertas = $wpdb->prefix . 'flavor_ayuda_ofertas';
        $tabla_respuestas = $wpdb->prefix . 'flavor_ayuda_respuestas';
        $tabla_valoraciones = $wpdb->prefix . 'flavor_ayuda_valoraciones';

        $sql_solicitudes = "CREATE TABLE IF NOT EXISTS $tabla_solicitudes (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            solicitante_id bigint(20) unsigned NOT NULL,
            categoria varchar(100) NOT NULL,
            titulo varchar(255) NOT NULL,
            descripcion text NOT NULL,
            urgencia enum('baja','media','alta','urgente') DEFAULT 'media',
            ubicacion varchar(500) DEFAULT NULL,
            ubicacion_lat decimal(10,7) DEFAULT NULL,
            ubicacion_lng decimal(10,7) DEFAULT NULL,
            fecha_necesaria datetime DEFAULT NULL,
            duracion_estimada_minutos int(11) DEFAULT NULL,
            necesita_desplazamiento tinyint(1) DEFAULT 0,
            requiere_habilidad_especifica tinyint(1) DEFAULT 0,
            habilidades_requeridas text DEFAULT NULL,
            num_personas_necesarias int(11) DEFAULT 1,
            compensacion text DEFAULT NULL,
            estado enum('abierta','asignada','en_curso','completada','cancelada','expirada') DEFAULT 'abierta',
            ayudante_id bigint(20) unsigned DEFAULT NULL,
            fecha_solicitud datetime DEFAULT CURRENT_TIMESTAMP,
            fecha_asignacion datetime DEFAULT NULL,
            fecha_completado datetime DEFAULT NULL,
            PRIMARY KEY (id),
            KEY solicitante_id (solicitante_id),
            KEY ayudante_id (ayudante_id),
            KEY categoria (categoria),
            KEY urgencia (urgencia),
            KEY estado (estado),
            KEY fecha_necesaria (fecha_necesaria),
            KEY ubicacion (ubicacion_lat, ubicacion_lng)
        ) $charset_collate;";

        $sql_ofertas = "CREATE TABLE IF NOT EXISTS $tabla_ofertas (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            usuario_id bigint(20) unsigned NOT NULL,
            categoria varchar(100) NOT NULL,
            titulo varchar(255) NOT NULL,
            descripcion text NOT NULL,
            habilidades text DEFAULT NULL COMMENT 'JSON',
            disponibilidad text DEFAULT NULL COMMENT 'JSON dias y horarios',
            radio_km int(11) DEFAULT 5,
            tiene_vehiculo tinyint(1) DEFAULT 0,
            activa tinyint(1) DEFAULT 1,
            fecha_creacion datetime DEFAULT CURRENT_TIMESTAMP,
            fecha_actualizacion datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY usuario_id (usuario_id),
            KEY categoria (categoria),
            KEY activa (activa)
        ) $charset_collate;";

        $sql_respuestas = "CREATE TABLE IF NOT EXISTS $tabla_respuestas (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            solicitud_id bigint(20) unsigned NOT NULL,
            ayudante_id bigint(20) unsigned NOT NULL,
            mensaje text DEFAULT NULL,
            disponibilidad_propuesta datetime DEFAULT NULL,
            estado enum('pendiente','aceptada','rechazada','retirada') DEFAULT 'pendiente',
            fecha_respuesta datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY solicitud_id (solicitud_id),
            KEY ayudante_id (ayudante_id),
            KEY estado (estado)
        ) $charset_collate;";

        $sql_valoraciones = "CREATE TABLE IF NOT EXISTS $tabla_valoraciones (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            solicitud_id bigint(20) unsigned NOT NULL,
            valorador_id bigint(20) unsigned NOT NULL,
            valorado_id bigint(20) unsigned NOT NULL,
            tipo enum('ayudante','solicitante') NOT NULL,
            puntuacion int(11) NOT NULL,
            aspectos text DEFAULT NULL COMMENT 'JSON: puntualidad, amabilidad, calidad',
            comentario text DEFAULT NULL,
            puntos_solidaridad_otorgados int(11) DEFAULT 0,
            fecha_valoracion datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            UNIQUE KEY solicitud_valorador (solicitud_id, valorador_id),
            KEY valorado_id (valorado_id)
        ) $charset_collate;";

        require_once ABSPATH . 'wp-admin/includes/upgrade.php';
        dbDelta($sql_solicitudes);
        dbDelta($sql_ofertas);
        dbDelta($sql_respuestas);
        dbDelta($sql_valoraciones);
    }

    /**
     * {@inheritdoc}
     */
    public function get_actions() {
        return [
            'solicitudes_activas' => [
                'description' => 'Ver solicitudes de ayuda activas',
                'params' => ['categoria', 'urgencia'],
            ],
            'solicitudes_cercanas' => [
                'description' => 'Solicitudes cercanas a mi ubicación',
                'params' => ['lat', 'lng', 'radio_km'],
            ],
            'crear_solicitud' => [
                'description' => 'Crear solicitud de ayuda',
                'params' => ['categoria', 'titulo', 'descripcion', 'urgencia'],
            ],
            'mis_solicitudes' => [
                'description' => 'Mis solicitudes de ayuda',
                'params' => [],
            ],
            'ofrecer_ayuda' => [
                'description' => 'Ofrecer ayuda en solicitud',
                'params' => ['solicitud_id', 'mensaje'],
            ],
            'aceptar_ayudante' => [
                'description' => 'Aceptar ayudante para mi solicitud',
                'params' => ['respuesta_id'],
            ],
            'publicar_oferta' => [
                'description' => 'Publicar oferta de ayuda permanente',
                'params' => ['categoria', 'titulo', 'descripcion'],
            ],
            'mis_ayudas_realizadas' => [
                'description' => 'Ayudas que he realizado',
                'params' => [],
            ],
            'marcar_completada' => [
                'description' => 'Marcar ayuda como completada',
                'params' => ['solicitud_id'],
            ],
            'valorar_ayuda' => [
                'description' => 'Valorar ayuda recibida/dada',
                'params' => ['solicitud_id', 'puntuacion', 'comentario'],
            ],
            'mis_puntos_solidaridad' => [
                'description' => 'Ver mis puntos de solidaridad',
                'params' => [],
            ],
            // Admin actions
            'estadisticas_ayuda' => [
                'description' => 'Estadísticas de ayuda mutua (admin)',
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
     * Acción: Solicitudes activas
     */
    private function action_solicitudes_activas($params) {
        global $wpdb;
        $tabla_solicitudes = $wpdb->prefix . 'flavor_ayuda_solicitudes';

        $where = ["estado = 'abierta'"];
        $prepare_values = [];

        if (!empty($params['categoria'])) {
            $where[] = 'categoria = %s';
            $prepare_values[] = sanitize_text_field($params['categoria']);
        }

        if (!empty($params['urgencia'])) {
            $where[] = 'urgencia = %s';
            $prepare_values[] = sanitize_text_field($params['urgencia']);
        }

        $sql = "SELECT * FROM $tabla_solicitudes WHERE " . implode(' AND ', $where) . " ORDER BY urgencia DESC, fecha_solicitud DESC LIMIT 50";

        if (!empty($prepare_values)) {
            $solicitudes = $wpdb->get_results($wpdb->prepare($sql, ...$prepare_values));
        } else {
            $solicitudes = $wpdb->get_results($sql);
        }

        return [
            'success' => true,
            'solicitudes' => array_map(function($s) {
                $solicitante = get_userdata($s->solicitante_id);
                return [
                    'id' => $s->id,
                    'titulo' => $s->titulo,
                    'descripcion' => $s->descripcion,
                    'categoria' => $s->categoria,
                    'urgencia' => $s->urgencia,
                    'solicitante' => $solicitante ? $solicitante->display_name : 'Vecino',
                    'fecha_necesaria' => $s->fecha_necesaria ? date('d/m/Y H:i', strtotime($s->fecha_necesaria)) : null,
                    'duracion_estimada' => $s->duracion_estimada_minutos,
                    'ubicacion' => $s->ubicacion,
                    'tiempo_publicada' => human_time_diff(strtotime($s->fecha_solicitud), current_time('timestamp')) . ' atrás',
                ];
            }, $solicitudes),
        ];
    }

    /**
     * Componentes web del módulo
     *
     * IA Features futuras:
     * - Matching inteligente entre solicitudes y voluntarios
     * - Sugerencias de ayuda según habilidades del usuario
     * - Detección de necesidades urgentes prioritarias
     * - Sistema de reputación y confianza
     */
    public function get_web_components() {
        return [
            'hero_ayuda' => [
                'label' => __('Hero Ayuda Vecinal', 'flavor-chat-ia'),
                'category' => 'hero',
                'icon' => 'dashicons-heart',
                'fields' => [
                    'titulo' => ['type' => 'text', 'default' => __('Red de Ayuda Vecinal', 'flavor-chat-ia')],
                    'subtitulo' => ['type' => 'textarea', 'default' => __('Unidos somos más fuertes. Pide o presta ayuda en tu comunidad', 'flavor-chat-ia')],
                    'imagen_fondo' => ['type' => 'image', 'default' => ''],
                    'mostrar_estadisticas' => ['type' => 'toggle', 'default' => true],
                ],
                'template' => 'ayuda-vecinal/hero',
            ],
            'solicitudes_grid' => [
                'label' => __('Grid de Solicitudes', 'flavor-chat-ia'),
                'category' => 'listings',
                'icon' => 'dashicons-grid-view',
                'fields' => [
                    'titulo' => ['type' => 'text', 'default' => __('Solicitudes Activas', 'flavor-chat-ia')],
                    'columnas' => ['type' => 'select', 'options' => [2, 3], 'default' => 2],
                    'limite' => ['type' => 'number', 'default' => 6],
                    'categoria' => ['type' => 'text', 'default' => ''],
                    'mostrar_urgencia' => ['type' => 'toggle', 'default' => true],
                ],
                'template' => 'ayuda-vecinal/solicitudes-grid',
            ],
            'categorias_ayuda' => [
                'label' => __('Categorías de Ayuda', 'flavor-chat-ia'),
                'category' => 'features',
                'icon' => 'dashicons-category',
                'fields' => [
                    'titulo' => ['type' => 'text', 'default' => __('¿Cómo Puedes Ayudar?', 'flavor-chat-ia')],
                    'mostrar_iconos' => ['type' => 'toggle', 'default' => true],
                    'estilo' => ['type' => 'select', 'options' => ['grid', 'lista'], 'default' => 'grid'],
                ],
                'template' => 'ayuda-vecinal/categorias',
            ],
            'cta_voluntario' => [
                'label' => __('CTA Ser Voluntario', 'flavor-chat-ia'),
                'category' => 'cta',
                'icon' => 'dashicons-megaphone',
                'fields' => [
                    'titulo' => ['type' => 'text', 'default' => __('Únete como Voluntario', 'flavor-chat-ia')],
                    'descripcion' => ['type' => 'textarea', 'default' => __('Ayuda a tus vecinos y fortalece tu comunidad', 'flavor-chat-ia')],
                    'boton_texto' => ['type' => 'text', 'default' => __('Quiero Ayudar', 'flavor-chat-ia')],
                    'boton_url' => ['type' => 'url', 'default' => '#'],
                    'color_fondo' => ['type' => 'color', 'default' => '#ef4444'],
                ],
                'template' => 'ayuda-vecinal/cta-voluntario',
            ],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function get_tool_definitions() {
        return [
            [
                'name' => 'ayuda_solicitudes',
                'description' => 'Ver solicitudes de ayuda activas',
                'input_schema' => [
                    'type' => 'object',
                    'properties' => [
                        'categoria' => ['type' => 'string', 'description' => 'Categoría de ayuda'],
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
**Red de Ayuda Vecinal**

Comunidad de apoyo mutuo donde vecinos se ayudan entre sí.

**Tipos de ayuda:**
- Compras y recados
- Cuidado de mayores
- Cuidado de niños
- Cuidado de mascotas
- Transporte y desplazamientos
- Ayuda con tecnología
- Trámites y gestiones
- Reparaciones menores
- Compañía y conversación

**Cómo funciona:**

**Si necesitas ayuda:**
1. Publica tu solicitud
2. Describe qué necesitas
3. Indica urgencia y disponibilidad
4. Espera ofertas de vecinos
5. Acepta la mejor opción
6. Coordina los detalles
7. Valora la ayuda recibida

**Si puedes ayudar:**
1. Mira solicitudes activas
2. Ofrécete para ayudar
3. Coordina con el solicitante
4. Presta tu ayuda
5. Recibe valoración y puntos

**Sistema de puntos:**
- Ganas puntos por ayudar
- Reconocimiento comunitario
- Ranking de solidaridad
- Sin intercambio monetario

**Principios:**
- Ayuda desinteresada
- Reciprocidad natural
- Sin ánimo de lucro
- Confianza y respeto
- Comunidad más fuerte

**Categorías urgencia:**
- Urgente: Necesidad inmediata
- Alta: En 24 horas
- Media: En pocos días
- Baja: Cuando sea posible
KNOWLEDGE;
    }

    /**
     * {@inheritdoc}
     */
    public function get_faqs() {
        return [
            [
                'pregunta' => '¿Tengo que pagar por la ayuda?',
                'respuesta' => 'No, es ayuda gratuita entre vecinos. Puedes agradecer como quieras pero no hay pago obligatorio.',
            ],
            [
                'pregunta' => '¿Y si nadie me ayuda?',
                'respuesta' => 'Intenta reformular tu solicitud o ampliar el radio. También puedes contactar con el coordinador.',
            ],
            [
                'pregunta' => '¿Estoy obligado a ayudar?',
                'respuesta' => 'No, solo si quieres y puedes. La ayuda debe ser siempre voluntaria.',
            ],
        ];
    }
}

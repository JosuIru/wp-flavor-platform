<?php
/**
 * Módulo de Espacios Comunes para Chat IA
 *
 * @package FlavorChatIA
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Módulo de Espacios Comunes - Reserva de espacios comunitarios
 */
class Flavor_Chat_Espacios_Comunes_Module extends Flavor_Chat_Module_Base {

    /**
     * Constructor
     */
    public function __construct() {
        $this->id = 'espacios_comunes';
        $this->name = __('Espacios Comunes', 'flavor-chat-ia');
        $this->description = __('Sistema de reserva y gestión de espacios comunes y equipamientos de la comunidad.', 'flavor-chat-ia');

        parent::__construct();
    }

    /**
     * {@inheritdoc}
     */
    public function can_activate() {
        global $wpdb;
        $tabla_espacios = $wpdb->prefix . 'flavor_espacios_comunes';

        return Flavor_Chat_Helpers::tabla_existe($tabla_espacios);
    }

    /**
     * {@inheritdoc}
     */
    public function get_activation_error() {
        if (!$this->can_activate()) {
            return __('Las tablas de Espacios Comunes no están creadas. Se crearán automáticamente al activar.', 'flavor-chat-ia');
        }
        return '';
    }

    /**
     * {@inheritdoc}
     */
    protected function get_default_settings() {
        return [
            'disponible_app' => 'cliente',
            'requiere_fianza' => true,
            'importe_fianza_predeterminado' => 50,
            'horas_anticipacion_minima' => 24,
            'dias_anticipacion_maxima' => 90,
            'horas_anticipacion_cancelacion' => 24,
            'permite_reservas_recurrentes' => true,
            'duracion_maxima_horas' => 8,
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
        $tabla_espacios = $wpdb->prefix . 'flavor_espacios_comunes';

        if (!Flavor_Chat_Helpers::tabla_existe($tabla_espacios)) {
            $this->create_tables();
        }
    }

    /**
     * Crea las tablas necesarias
     */
    private function create_tables() {
        global $wpdb;
        $charset_collate = $wpdb->get_charset_collate();

        $tabla_espacios = $wpdb->prefix . 'flavor_espacios_comunes';
        $tabla_reservas = $wpdb->prefix . 'flavor_espacios_reservas';
        $tabla_equipamiento = $wpdb->prefix . 'flavor_espacios_equipamiento';

        $sql_espacios = "CREATE TABLE IF NOT EXISTS $tabla_espacios (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            nombre varchar(255) NOT NULL,
            descripcion text NOT NULL,
            tipo enum('salon_eventos','sala_reuniones','cocina','taller','terraza','jardin','gimnasio','ludoteca','otro') DEFAULT 'salon_eventos',
            ubicacion varchar(500) NOT NULL,
            latitud decimal(10,7) DEFAULT NULL,
            longitud decimal(10,7) DEFAULT NULL,
            capacidad_personas int(11) NOT NULL,
            superficie_m2 decimal(10,2) DEFAULT NULL,
            equipamiento text DEFAULT NULL COMMENT 'JSON',
            normas_uso text DEFAULT NULL,
            precio_hora decimal(10,2) DEFAULT 0,
            precio_dia decimal(10,2) DEFAULT 0,
            requiere_fianza tinyint(1) DEFAULT 1,
            importe_fianza decimal(10,2) DEFAULT 50,
            horario_apertura time DEFAULT '08:00:00',
            horario_cierre time DEFAULT '22:00:00',
            dias_disponibles varchar(50) DEFAULT 'L,M,X,J,V,S,D',
            fotos text DEFAULT NULL COMMENT 'JSON array URLs',
            responsable_id bigint(20) unsigned DEFAULT NULL,
            instrucciones_acceso text DEFAULT NULL,
            valoracion_media decimal(3,2) DEFAULT 0,
            numero_valoraciones int(11) DEFAULT 0,
            estado enum('disponible','mantenimiento','inactivo') DEFAULT 'disponible',
            fecha_creacion datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY tipo (tipo),
            KEY responsable_id (responsable_id),
            KEY estado (estado)
        ) $charset_collate;";

        $sql_reservas = "CREATE TABLE IF NOT EXISTS $tabla_reservas (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            espacio_id bigint(20) unsigned NOT NULL,
            usuario_id bigint(20) unsigned NOT NULL,
            fecha_inicio datetime NOT NULL,
            fecha_fin datetime NOT NULL,
            num_asistentes int(11) DEFAULT NULL,
            motivo varchar(500) DEFAULT NULL,
            tipo_evento varchar(100) DEFAULT NULL,
            equipamiento_adicional text DEFAULT NULL COMMENT 'JSON',
            precio_total decimal(10,2) DEFAULT 0,
            fianza decimal(10,2) DEFAULT NULL,
            fianza_devuelta tinyint(1) DEFAULT 0,
            instrucciones_especiales text DEFAULT NULL,
            valoracion int(11) DEFAULT NULL,
            comentario_valoracion text DEFAULT NULL,
            estado enum('solicitada','confirmada','en_curso','finalizada','cancelada','rechazada') DEFAULT 'solicitada',
            fecha_solicitud datetime DEFAULT CURRENT_TIMESTAMP,
            fecha_confirmacion datetime DEFAULT NULL,
            fecha_cancelacion datetime DEFAULT NULL,
            PRIMARY KEY (id),
            KEY espacio_id (espacio_id),
            KEY usuario_id (usuario_id),
            KEY fecha_inicio (fecha_inicio),
            KEY estado (estado)
        ) $charset_collate;";

        $sql_equipamiento = "CREATE TABLE IF NOT EXISTS $tabla_equipamiento (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            nombre varchar(255) NOT NULL,
            descripcion text DEFAULT NULL,
            categoria enum('mobiliario','audiovisual','cocina','deportivo','herramientas','juegos','otro') DEFAULT 'otro',
            cantidad int(11) DEFAULT 1,
            ubicacion_predeterminada bigint(20) unsigned DEFAULT NULL COMMENT 'espacio_id',
            requiere_reserva tinyint(1) DEFAULT 0,
            precio_reserva decimal(10,2) DEFAULT 0,
            instrucciones_uso text DEFAULT NULL,
            foto_url varchar(500) DEFAULT NULL,
            estado enum('disponible','reservado','mantenimiento','fuera_servicio') DEFAULT 'disponible',
            fecha_creacion datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY categoria (categoria),
            KEY ubicacion_predeterminada (ubicacion_predeterminada),
            KEY estado (estado)
        ) $charset_collate;";

        require_once ABSPATH . 'wp-admin/includes/upgrade.php';
        dbDelta($sql_espacios);
        dbDelta($sql_reservas);
        dbDelta($sql_equipamiento);
    }

    /**
     * {@inheritdoc}
     */
    public function get_actions() {
        return [
            'listar_espacios' => [
                'description' => 'Listar espacios disponibles',
                'params' => ['tipo', 'fecha'],
            ],
            'detalle_espacio' => [
                'description' => 'Ver detalles del espacio',
                'params' => ['espacio_id'],
            ],
            'disponibilidad' => [
                'description' => 'Ver disponibilidad de espacio',
                'params' => ['espacio_id', 'fecha_desde', 'fecha_hasta'],
            ],
            'crear_reserva' => [
                'description' => 'Crear reserva',
                'params' => ['espacio_id', 'fecha_inicio', 'fecha_fin', 'motivo'],
            ],
            'mis_reservas' => [
                'description' => 'Mis reservas activas',
                'params' => [],
            ],
            'cancelar_reserva' => [
                'description' => 'Cancelar reserva',
                'params' => ['reserva_id'],
            ],
            'valorar_espacio' => [
                'description' => 'Valorar espacio usado',
                'params' => ['reserva_id', 'valoracion', 'comentario'],
            ],
            'equipamiento_disponible' => [
                'description' => 'Ver equipamiento disponible',
                'params' => ['categoria'],
            ],
            'reportar_incidencia' => [
                'description' => 'Reportar problema en espacio',
                'params' => ['espacio_id', 'descripcion'],
            ],
            // Admin actions
            'estadisticas_espacios' => [
                'description' => 'Estadísticas de uso (admin)',
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
     * Acción: Listar espacios
     */
    private function action_listar_espacios($params) {
        global $wpdb;
        $tabla_espacios = $wpdb->prefix . 'flavor_espacios_comunes';

        $where = ["estado = 'disponible'"];
        $prepare_values = [];

        if (!empty($params['tipo'])) {
            $where[] = 'tipo = %s';
            $prepare_values[] = sanitize_text_field($params['tipo']);
        }

        $sql = "SELECT * FROM $tabla_espacios WHERE " . implode(' AND ', $where) . " ORDER BY nombre";

        if (!empty($prepare_values)) {
            $espacios = $wpdb->get_results($wpdb->prepare($sql, ...$prepare_values));
        } else {
            $espacios = $wpdb->get_results($sql);
        }

        return [
            'success' => true,
            'espacios' => array_map(function($e) {
                $fotos = json_decode($e->fotos, true) ?: [];
                $equipamiento = json_decode($e->equipamiento, true) ?: [];

                return [
                    'id' => $e->id,
                    'nombre' => $e->nombre,
                    'descripcion' => wp_trim_words($e->descripcion, 30),
                    'tipo' => $e->tipo,
                    'capacidad' => $e->capacidad_personas,
                    'superficie_m2' => floatval($e->superficie_m2),
                    'precio_hora' => floatval($e->precio_hora),
                    'precio_dia' => floatval($e->precio_dia),
                    'requiere_fianza' => (bool)$e->requiere_fianza,
                    'equipamiento' => $equipamiento,
                    'foto_principal' => !empty($fotos) ? $fotos[0] : null,
                    'valoracion' => floatval($e->valoracion_media),
                ];
            }, $espacios),
        ];
    }

    /**
     * Componentes web del módulo
     *
     * IA Features futuras:
     * - Sugerencia de configuración de espacio según evento
     * - Predicción de disponibilidad y horarios óptimos
     * - Recomendación de espacios según necesidades
     */
    public function get_web_components() {
        return [
            'hero_espacios' => [
                'label' => __('Hero Espacios', 'flavor-chat-ia'),
                'category' => 'hero',
                'icon' => 'dashicons-building',
                'fields' => [
                    'titulo' => ['type' => 'text', 'default' => __('Espacios Comunes', 'flavor-chat-ia')],
                    'subtitulo' => ['type' => 'textarea', 'default' => __('Reserva espacios para tus eventos y actividades', 'flavor-chat-ia')],
                    'imagen_fondo' => ['type' => 'image', 'default' => ''],
                ],
                'template' => 'espacios/hero',
            ],
            'espacios_grid' => [
                'label' => __('Grid de Espacios', 'flavor-chat-ia'),
                'category' => 'listings',
                'icon' => 'dashicons-grid-view',
                'fields' => [
                    'titulo' => ['type' => 'text', 'default' => __('Nuestros Espacios', 'flavor-chat-ia')],
                    'columnas' => ['type' => 'select', 'options' => [2, 3, 4], 'default' => 3],
                    'tipo_filtro' => ['type' => 'text', 'default' => ''],
                    'mostrar_disponibilidad' => ['type' => 'toggle', 'default' => true],
                ],
                'template' => 'espacios/grid',
            ],
            'calendario_disponibilidad' => [
                'label' => __('Calendario Disponibilidad', 'flavor-chat-ia'),
                'category' => 'content',
                'icon' => 'dashicons-calendar-alt',
                'fields' => [
                    'espacio_id' => ['type' => 'number', 'default' => 0],
                    'vista_defecto' => ['type' => 'select', 'options' => ['mes', 'semana'], 'default' => 'semana'],
                ],
                'template' => 'espacios/calendario',
            ],
            'proceso_reserva' => [
                'label' => __('Proceso de Reserva', 'flavor-chat-ia'),
                'category' => 'content',
                'icon' => 'dashicons-yes',
                'fields' => [
                    'titulo' => ['type' => 'text', 'default' => __('Cómo Reservar', 'flavor-chat-ia')],
                ],
                'template' => 'espacios/proceso',
            ],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function get_tool_definitions() {
        return [
            [
                'name' => 'espacios_listar',
                'description' => 'Ver espacios comunes disponibles',
                'input_schema' => [
                    'type' => 'object',
                    'properties' => [
                        'tipo' => ['type' => 'string', 'description' => 'Tipo de espacio'],
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
**Espacios Comunes de la Comunidad**

Reserva espacios compartidos para tus eventos y actividades.

**Tipos de espacios:**
- Salón de eventos (fiestas, reuniones grandes)
- Salas de reuniones (juntas, talleres)
- Cocina comunitaria (cenas, talleres cocina)
- Taller (bricolaje, reparaciones)
- Terraza/Jardín (eventos al aire libre)
- Gimnasio (deportes, yoga)
- Ludoteca (cumpleaños infantiles)

**Equipamiento disponible:**
- Mesas y sillas
- Sistema de sonido
- Proyector y pantalla
- Cocina equipada
- Vajilla y cubiertos
- Juegos y juguetes
- Material deportivo

**Cómo reservar:**
1. Consulta disponibilidad
2. Solicita tu reserva
3. Espera confirmación
4. Paga fianza si requerido
5. Recoge llaves día del evento
6. Disfruta el espacio
7. Deja todo limpio y ordenado
8. Recupera tu fianza

**Normas generales:**
- Respeta horarios
- Deja el espacio limpio
- No excedas capacidad
- No hacer ruido excesivo
- Respeta vecinos
- Avisa si hay daños

**Precios:**
- Gratuitos o precio simbólico
- Fianza reembolsable
- Descuentos por reservas largas
KNOWLEDGE;
    }

    /**
     * {@inheritdoc}
     */
    public function get_faqs() {
        return [
            [
                'pregunta' => '¿Cuánto cuesta reservar un espacio?',
                'respuesta' => 'Depende del espacio. Muchos son gratuitos con fianza reembolsable, otros tienen precio simbólico.',
            ],
            [
                'pregunta' => '¿Puedo cancelar mi reserva?',
                'respuesta' => 'Sí, con al menos 24h de antelación sin penalización. Cancelaciones tardías pierden la fianza.',
            ],
            [
                'pregunta' => '¿Qué pasa si rompo algo?',
                'respuesta' => 'Se descuenta de la fianza. Si el daño supera la fianza, debes pagar la diferencia.',
            ],
        ];
    }
}

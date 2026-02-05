<?php
/**
 * Módulo de Huertos Urbanos para Chat IA
 *
 * @package FlavorChatIA
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Módulo de Huertos Urbanos - Gestión de huertos comunitarios
 */
class Flavor_Chat_Huertos_Urbanos_Module extends Flavor_Chat_Module_Base {

    /**
     * Constructor
     */
    public function __construct() {
        $this->id = 'huertos_urbanos';
        $this->name = __('Huertos Urbanos', 'flavor-chat-ia');
        $this->description = __('Gestión de huertos urbanos comunitarios - cultiva, comparte y aprende sobre agricultura urbana.', 'flavor-chat-ia');

        parent::__construct();
    }

    /**
     * {@inheritdoc}
     */
    public function can_activate() {
        global $wpdb;
        $tabla_huertos = $wpdb->prefix . 'flavor_huertos';

        return Flavor_Chat_Helpers::tabla_existe($tabla_huertos);
    }

    /**
     * {@inheritdoc}
     */
    public function get_activation_error() {
        if (!$this->can_activate()) {
            return __('Las tablas de Huertos Urbanos no están creadas. Se crearán automáticamente al activar.', 'flavor-chat-ia');
        }
        return '';
    }

    /**
     * {@inheritdoc}
     */
    protected function get_default_settings() {
        return [
            'disponible_app' => 'cliente',
            'permite_solicitar_parcela' => true,
            'precio_parcela_anual' => 0,
            'requiere_compromiso_asistencia' => true,
            'horas_minimas_mes' => 4,
            'permite_intercambio_cosechas' => true,
            'sistema_turnos_riego' => true,
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
        $tabla_huertos = $wpdb->prefix . 'flavor_huertos';

        if (!Flavor_Chat_Helpers::tabla_existe($tabla_huertos)) {
            $this->create_tables();
        }
    }

    /**
     * Crea las tablas necesarias
     */
    private function create_tables() {
        global $wpdb;
        $charset_collate = $wpdb->get_charset_collate();

        $tabla_huertos = $wpdb->prefix . 'flavor_huertos';
        $tabla_parcelas = $wpdb->prefix . 'flavor_huertos_parcelas';
        $tabla_cultivos = $wpdb->prefix . 'flavor_huertos_cultivos';
        $tabla_actividades = $wpdb->prefix . 'flavor_huertos_actividades';
        $tabla_turnos_riego = $wpdb->prefix . 'flavor_huertos_turnos_riego';

        $sql_huertos = "CREATE TABLE IF NOT EXISTS $tabla_huertos (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            nombre varchar(255) NOT NULL,
            descripcion text DEFAULT NULL,
            direccion varchar(500) NOT NULL,
            latitud decimal(10,7) NOT NULL,
            longitud decimal(10,7) NOT NULL,
            superficie_m2 int(11) NOT NULL,
            num_parcelas int(11) NOT NULL,
            parcelas_disponibles int(11) DEFAULT 0,
            coordinador_id bigint(20) unsigned DEFAULT NULL,
            equipamiento text DEFAULT NULL COMMENT 'JSON: herramientas, riego, compostadora',
            normas text DEFAULT NULL,
            horario_acceso text DEFAULT NULL,
            foto_url varchar(500) DEFAULT NULL,
            estado enum('activo','mantenimiento','inactivo') DEFAULT 'activo',
            fecha_creacion datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY coordinador_id (coordinador_id),
            KEY ubicacion (latitud, longitud),
            KEY estado (estado)
        ) $charset_collate;";

        $sql_parcelas = "CREATE TABLE IF NOT EXISTS $tabla_parcelas (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            huerto_id bigint(20) unsigned NOT NULL,
            numero_parcela varchar(20) NOT NULL,
            superficie_m2 decimal(10,2) NOT NULL,
            hortelano_id bigint(20) unsigned DEFAULT NULL,
            fecha_asignacion datetime DEFAULT NULL,
            fecha_fin_asignacion datetime DEFAULT NULL,
            tipo_suelo varchar(100) DEFAULT NULL,
            orientacion enum('norte','sur','este','oeste') DEFAULT NULL,
            tiene_sombra tinyint(1) DEFAULT 0,
            estado enum('disponible','ocupada','mantenimiento') DEFAULT 'disponible',
            notas text DEFAULT NULL,
            PRIMARY KEY (id),
            UNIQUE KEY huerto_numero (huerto_id, numero_parcela),
            KEY hortelano_id (hortelano_id),
            KEY estado (estado)
        ) $charset_collate;";

        $sql_cultivos = "CREATE TABLE IF NOT EXISTS $tabla_cultivos (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            parcela_id bigint(20) unsigned NOT NULL,
            nombre_cultivo varchar(255) NOT NULL,
            variedad varchar(255) DEFAULT NULL,
            fecha_siembra datetime NOT NULL,
            fecha_cosecha_estimada datetime DEFAULT NULL,
            fecha_cosecha_real datetime DEFAULT NULL,
            cantidad_kg decimal(10,2) DEFAULT NULL,
            notas text DEFAULT NULL,
            foto_url varchar(500) DEFAULT NULL,
            estado enum('sembrado','crecimiento','floracion','cosecha','finalizado') DEFAULT 'sembrado',
            fecha_creacion datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY parcela_id (parcela_id),
            KEY estado (estado),
            KEY fecha_siembra (fecha_siembra)
        ) $charset_collate;";

        $sql_actividades = "CREATE TABLE IF NOT EXISTS $tabla_actividades (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            huerto_id bigint(20) unsigned DEFAULT NULL,
            parcela_id bigint(20) unsigned DEFAULT NULL,
            usuario_id bigint(20) unsigned NOT NULL,
            tipo enum('riego','abonado','poda','cosecha','limpieza','mantenimiento','taller','otro') DEFAULT 'otro',
            descripcion text NOT NULL,
            duracion_minutos int(11) DEFAULT NULL,
            foto_url varchar(500) DEFAULT NULL,
            fecha_actividad datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY huerto_id (huerto_id),
            KEY parcela_id (parcela_id),
            KEY usuario_id (usuario_id),
            KEY tipo (tipo),
            KEY fecha_actividad (fecha_actividad)
        ) $charset_collate;";

        $sql_turnos_riego = "CREATE TABLE IF NOT EXISTS $tabla_turnos_riego (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            huerto_id bigint(20) unsigned NOT NULL,
            usuario_id bigint(20) unsigned NOT NULL,
            fecha_turno date NOT NULL,
            hora_inicio time DEFAULT '08:00:00',
            hora_fin time DEFAULT '10:00:00',
            completado tinyint(1) DEFAULT 0,
            fecha_completado datetime DEFAULT NULL,
            notas text DEFAULT NULL,
            PRIMARY KEY (id),
            KEY huerto_id (huerto_id),
            KEY usuario_id (usuario_id),
            KEY fecha_turno (fecha_turno)
        ) $charset_collate;";

        require_once ABSPATH . 'wp-admin/includes/upgrade.php';
        dbDelta($sql_huertos);
        dbDelta($sql_parcelas);
        dbDelta($sql_cultivos);
        dbDelta($sql_actividades);
        dbDelta($sql_turnos_riego);
    }

    /**
     * {@inheritdoc}
     */
    public function get_actions() {
        return [
            'listar_huertos' => [
                'description' => 'Listar huertos comunitarios',
                'params' => ['lat', 'lng'],
            ],
            'detalle_huerto' => [
                'description' => 'Ver detalles del huerto',
                'params' => ['huerto_id'],
            ],
            'solicitar_parcela' => [
                'description' => 'Solicitar parcela en huerto',
                'params' => ['huerto_id'],
            ],
            'mi_parcela' => [
                'description' => 'Ver mi parcela',
                'params' => [],
            ],
            'registrar_cultivo' => [
                'description' => 'Registrar nuevo cultivo',
                'params' => ['parcela_id', 'nombre', 'fecha_siembra'],
            ],
            'registrar_actividad' => [
                'description' => 'Registrar actividad en huerto',
                'params' => ['parcela_id', 'tipo', 'descripcion'],
            ],
            'calendario_riego' => [
                'description' => 'Ver calendario de turnos de riego',
                'params' => ['huerto_id'],
            ],
            'marcar_riego_completado' => [
                'description' => 'Marcar turno de riego completado',
                'params' => ['turno_id'],
            ],
            'intercambio_cosechas' => [
                'description' => 'Ver intercambios de cosechas',
                'params' => [],
            ],
            'guia_cultivos' => [
                'description' => 'Guía de cultivos por temporada',
                'params' => ['mes'],
            ],
            // Admin actions
            'estadisticas_huerto' => [
                'description' => 'Estadísticas del huerto (admin)',
                'params' => ['huerto_id'],
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
     * Acción: Listar huertos
     */
    private function action_listar_huertos($params) {
        global $wpdb;
        $tabla_huertos = $wpdb->prefix . 'flavor_huertos';

        $lat = floatval($params['lat'] ?? 0);
        $lng = floatval($params['lng'] ?? 0);

        if ($lat != 0 && $lng != 0) {
            $sql = "SELECT *,
                    (6371 * acos(cos(radians(%f)) * cos(radians(latitud)) * cos(radians(longitud) - radians(%f)) + sin(radians(%f)) * sin(radians(latitud)))) AS distancia
                    FROM $tabla_huertos
                    WHERE estado = 'activo'
                    ORDER BY distancia ASC";

            $huertos = $wpdb->get_results($wpdb->prepare($sql, $lat, $lng, $lat));
        } else {
            $huertos = $wpdb->get_results("SELECT * FROM $tabla_huertos WHERE estado = 'activo' ORDER BY nombre");
        }

        return [
            'success' => true,
            'huertos' => array_map(function($h) {
                return [
                    'id' => $h->id,
                    'nombre' => $h->nombre,
                    'descripcion' => $h->descripcion,
                    'direccion' => $h->direccion,
                    'superficie_m2' => $h->superficie_m2,
                    'parcelas_disponibles' => $h->parcelas_disponibles,
                    'parcelas_totales' => $h->num_parcelas,
                    'foto' => $h->foto_url,
                    'distancia_km' => isset($h->distancia) ? round($h->distancia, 2) : null,
                ];
            }, $huertos),
        ];
    }

    /**
     * Componentes web del módulo
     *
     * IA Features futuras:
     * - Sugerencias de cultivos por temporada según clima local
     * - Recordatorios automáticos de riego y cuidados
     * - Comunidad de hortelanos con recomendaciones personalizadas
     * - Predicción de cosechas y mejores épocas de siembra
     */
    public function get_web_components() {
        return [
            'hero_huertos' => [
                'label' => __('Hero Huertos', 'flavor-chat-ia'),
                'category' => 'hero',
                'icon' => 'dashicons-carrot',
                'fields' => [
                    'titulo' => ['type' => 'text', 'default' => __('Huertos Urbanos', 'flavor-chat-ia')],
                    'subtitulo' => ['type' => 'textarea', 'default' => __('Cultiva tus propios alimentos en comunidad', 'flavor-chat-ia')],
                    'imagen_fondo' => ['type' => 'image', 'default' => ''],
                    'mostrar_estadisticas' => ['type' => 'toggle', 'default' => true],
                ],
                'template' => 'huertos-urbanos/hero',
            ],
            'mapa_huertos' => [
                'label' => __('Mapa de Huertos', 'flavor-chat-ia'),
                'category' => 'content',
                'icon' => 'dashicons-location',
                'fields' => [
                    'titulo' => ['type' => 'text', 'default' => __('Encuentra tu Huerto', 'flavor-chat-ia')],
                    'altura_mapa' => ['type' => 'number', 'default' => 500],
                    'zoom_inicial' => ['type' => 'number', 'default' => 12],
                    'mostrar_disponibilidad' => ['type' => 'toggle', 'default' => true],
                ],
                'template' => 'huertos-urbanos/mapa',
            ],
            'parcelas_disponibles' => [
                'label' => __('Parcelas Disponibles', 'flavor-chat-ia'),
                'category' => 'listings',
                'icon' => 'dashicons-grid-view',
                'fields' => [
                    'titulo' => ['type' => 'text', 'default' => __('Parcelas Disponibles', 'flavor-chat-ia')],
                    'huerto_id' => ['type' => 'number', 'default' => 0],
                    'columnas' => ['type' => 'select', 'options' => [2, 3, 4], 'default' => 3],
                    'mostrar_caracteristicas' => ['type' => 'toggle', 'default' => true],
                ],
                'template' => 'huertos-urbanos/parcelas',
            ],
            'calendario_cultivos' => [
                'label' => __('Calendario de Cultivos', 'flavor-chat-ia'),
                'category' => 'content',
                'icon' => 'dashicons-calendar-alt',
                'fields' => [
                    'titulo' => ['type' => 'text', 'default' => __('Qué Plantar Este Mes', 'flavor-chat-ia')],
                    'vista_tipo' => ['type' => 'select', 'options' => ['mensual', 'anual'], 'default' => 'mensual'],
                    'mostrar_consejos' => ['type' => 'toggle', 'default' => true],
                ],
                'template' => 'huertos-urbanos/calendario',
            ],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function get_tool_definitions() {
        return [
            [
                'name' => 'huertos_listar',
                'description' => 'Ver huertos urbanos comunitarios',
                'input_schema' => [
                    'type' => 'object',
                    'properties' => [
                        'lat' => ['type' => 'number', 'description' => 'Latitud'],
                        'lng' => ['type' => 'number', 'description' => 'Longitud'],
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
**Huertos Urbanos Comunitarios**

Cultiva tus propios alimentos en espacios compartidos de la comunidad.

**Qué son:**
- Espacios verdes comunitarios
- Parcelas individuales o compartidas
- Gestión colectiva
- Aprendizaje mutuo

**Beneficios:**
- Alimentos frescos y ecológicos
- Ejercicio al aire libre
- Conexión con la naturaleza
- Comunidad y cooperación
- Educación ambiental

**Cómo participar:**
1. Solicita una parcela disponible
2. Asiste a la sesión de bienvenida
3. Cultiva según calendario
4. Participa en turnos de riego
5. Comparte conocimientos y cosechas

**Compromisos:**
- Cuidar tu parcela
- Cumplir turnos de riego
- Asistir a jornadas comunitarias
- Usar métodos ecológicos
- Respetar normas del huerto

**Actividades comunitarias:**
- Talleres de agricultura ecológica
- Intercambio de semillas
- Compostaje comunitario
- Fiestas de la cosecha
- Banco de herramientas

**Qué puedes cultivar:**
- Hortalizas de temporada
- Hierbas aromáticas
- Flores comestibles
- Frutos pequeños
KNOWLEDGE;
    }

    /**
     * {@inheritdoc}
     */
    public function get_faqs() {
        return [
            [
                'pregunta' => '¿Necesito experiencia previa?',
                'respuesta' => 'No, hay talleres y hortelanos experimentados que te ayudarán a empezar.',
            ],
            [
                'pregunta' => '¿Cuánto tiempo requiere?',
                'respuesta' => 'Mínimo 4 horas al mes, más tu turno de riego. Puedes dedicar más si quieres.',
            ],
            [
                'pregunta' => '¿Qué pasa con mi cosecha?',
                'respuesta' => 'Es tuya, pero también puedes intercambiarla con otros hortelanos.',
            ],
        ];
    }
}

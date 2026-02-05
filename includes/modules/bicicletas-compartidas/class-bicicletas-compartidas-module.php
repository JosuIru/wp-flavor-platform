<?php
/**
 * Módulo de Bicicletas Compartidas para Chat IA
 *
 * @package FlavorChatIA
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Módulo de Bicicletas Compartidas - Sistema de bici-sharing comunitario
 */
class Flavor_Chat_Bicicletas_Compartidas_Module extends Flavor_Chat_Module_Base {

    /**
     * Constructor
     */
    public function __construct() {
        $this->id = 'bicicletas_compartidas';
        $this->name = __('Bicicletas Compartidas', 'flavor-chat-ia');
        $this->description = __('Sistema de bicicletas compartidas gestionado por la comunidad.', 'flavor-chat-ia');

        parent::__construct();
    }

    /**
     * {@inheritdoc}
     */
    public function can_activate() {
        global $wpdb;
        $tabla_bicicletas = $wpdb->prefix . 'flavor_bicicletas';

        return Flavor_Chat_Helpers::tabla_existe($tabla_bicicletas);
    }

    /**
     * {@inheritdoc}
     */
    public function get_activation_error() {
        if (!$this->can_activate()) {
            return __('Las tablas de Bicicletas Compartidas no están creadas. Se crearán automáticamente al activar.', 'flavor-chat-ia');
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
            'importe_fianza' => 50,
            'precio_hora' => 0,
            'precio_dia' => 0,
            'precio_mes' => 10,
            'duracion_maxima_prestamo_dias' => 7,
            'permite_reservas' => true,
            'horas_anticipacion_reserva' => 2,
            'requiere_verificacion_usuario' => true,
            'notificar_mantenimiento' => true,
            'permite_reportar_problemas' => true,
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
        $tabla_bicicletas = $wpdb->prefix . 'flavor_bicicletas';

        if (!Flavor_Chat_Helpers::tabla_existe($tabla_bicicletas)) {
            $this->create_tables();
        }
    }

    /**
     * Crea las tablas necesarias
     */
    private function create_tables() {
        global $wpdb;
        $charset_collate = $wpdb->get_charset_collate();

        $tabla_bicicletas = $wpdb->prefix . 'flavor_bicicletas';
        $tabla_prestamos = $wpdb->prefix . 'flavor_bicicletas_prestamos';
        $tabla_estaciones = $wpdb->prefix . 'flavor_bicicletas_estaciones';
        $tabla_mantenimiento = $wpdb->prefix . 'flavor_bicicletas_mantenimiento';

        $sql_bicicletas = "CREATE TABLE $tabla_bicicletas (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            codigo varchar(50) NOT NULL,
            tipo varchar(20) DEFAULT 'urbana',
            marca varchar(100) DEFAULT NULL,
            modelo varchar(100) DEFAULT NULL,
            color varchar(50) DEFAULT NULL,
            talla varchar(5) DEFAULT 'M',
            estacion_actual_id bigint(20) unsigned DEFAULT NULL,
            estado varchar(20) DEFAULT 'disponible',
            kilometros_acumulados int(11) DEFAULT 0,
            ultima_revision datetime DEFAULT NULL,
            proximo_mantenimiento_km int(11) DEFAULT 500,
            foto_url varchar(500) DEFAULT NULL,
            equipamiento text DEFAULT NULL,
            fecha_alta datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY  (id),
            UNIQUE KEY codigo (codigo),
            KEY estacion_actual_id (estacion_actual_id),
            KEY estado (estado),
            KEY tipo (tipo)
        ) $charset_collate;";

        $sql_prestamos = "CREATE TABLE $tabla_prestamos (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            bicicleta_id bigint(20) unsigned NOT NULL,
            usuario_id bigint(20) unsigned NOT NULL,
            estacion_salida_id bigint(20) unsigned NOT NULL,
            estacion_llegada_id bigint(20) unsigned DEFAULT NULL,
            fecha_inicio datetime NOT NULL,
            fecha_fin datetime DEFAULT NULL,
            duracion_minutos int(11) DEFAULT NULL,
            kilometros_recorridos decimal(10,2) DEFAULT NULL,
            coste_total decimal(10,2) DEFAULT 0,
            fianza decimal(10,2) DEFAULT NULL,
            fianza_devuelta tinyint(1) DEFAULT 0,
            incidencias text DEFAULT NULL,
            valoracion int(11) DEFAULT NULL,
            estado varchar(20) DEFAULT 'activo',
            fecha_creacion datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY  (id),
            KEY bicicleta_id (bicicleta_id),
            KEY usuario_id (usuario_id),
            KEY estado (estado),
            KEY fecha_inicio (fecha_inicio)
        ) $charset_collate;";

        $sql_estaciones = "CREATE TABLE $tabla_estaciones (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            nombre varchar(255) NOT NULL,
            direccion varchar(500) NOT NULL,
            latitud decimal(10,7) NOT NULL,
            longitud decimal(10,7) NOT NULL,
            capacidad_total int(11) NOT NULL,
            bicicletas_disponibles int(11) DEFAULT 0,
            tipo varchar(20) DEFAULT 'publica',
            horario_apertura time DEFAULT NULL,
            horario_cierre time DEFAULT NULL,
            servicios text DEFAULT NULL,
            foto_url varchar(500) DEFAULT NULL,
            estado varchar(20) DEFAULT 'activa',
            fecha_creacion datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY  (id),
            KEY latitud (latitud),
            KEY estado (estado)
        ) $charset_collate;";

        $sql_mantenimiento = "CREATE TABLE $tabla_mantenimiento (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            bicicleta_id bigint(20) unsigned NOT NULL,
            tipo varchar(20) DEFAULT 'revision',
            descripcion text NOT NULL,
            reportado_por bigint(20) unsigned DEFAULT NULL,
            tecnico_asignado bigint(20) unsigned DEFAULT NULL,
            fecha_reporte datetime DEFAULT CURRENT_TIMESTAMP,
            fecha_inicio datetime DEFAULT NULL,
            fecha_fin datetime DEFAULT NULL,
            coste decimal(10,2) DEFAULT NULL,
            piezas_cambiadas text DEFAULT NULL,
            estado varchar(20) DEFAULT 'pendiente',
            PRIMARY KEY  (id),
            KEY bicicleta_id (bicicleta_id),
            KEY estado (estado),
            KEY fecha_reporte (fecha_reporte)
        ) $charset_collate;";

        require_once ABSPATH . 'wp-admin/includes/upgrade.php';
        dbDelta($sql_bicicletas);
        dbDelta($sql_prestamos);
        dbDelta($sql_estaciones);
        dbDelta($sql_mantenimiento);
    }

    /**
     * {@inheritdoc}
     */
    public function get_actions() {
        return [
            'estaciones' => [
                'description' => 'Listar estaciones cercanas',
                'params' => ['lat', 'lng', 'radio_km'],
            ],
            'bicicletas_disponibles' => [
                'description' => 'Ver bicicletas disponibles en estación',
                'params' => ['estacion_id'],
            ],
            'iniciar_prestamo' => [
                'description' => 'Retirar bicicleta',
                'params' => ['bicicleta_id'],
            ],
            'finalizar_prestamo' => [
                'description' => 'Devolver bicicleta',
                'params' => ['prestamo_id', 'estacion_id', 'kilometros'],
            ],
            'mis_prestamos' => [
                'description' => 'Historial de préstamos',
                'params' => [],
            ],
            'reportar_problema' => [
                'description' => 'Reportar problema con bicicleta',
                'params' => ['bicicleta_id', 'descripcion'],
            ],
            'reservar_bicicleta' => [
                'description' => 'Reservar bicicleta',
                'params' => ['bicicleta_id', 'estacion_id', 'fecha_hora'],
            ],
            // Admin actions
            'estadisticas_uso' => [
                'description' => 'Estadísticas de uso (admin)',
                'params' => ['periodo'],
            ],
            'gestion_mantenimiento' => [
                'description' => 'Gestión de mantenimiento (admin)',
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
     * Acción: Listar estaciones
     */
    private function action_estaciones($params) {
        global $wpdb;
        $tabla_estaciones = $wpdb->prefix . 'flavor_bicicletas_estaciones';

        $lat = floatval($params['lat'] ?? 0);
        $lng = floatval($params['lng'] ?? 0);
        $radio_km = absint($params['radio_km'] ?? 5);

        if ($lat == 0 || $lng == 0) {
            // Sin ubicación, devolver todas las estaciones activas
            $estaciones = $wpdb->get_results("SELECT * FROM $tabla_estaciones WHERE estado = 'activa' ORDER BY nombre");
        } else {
            // Con ubicación, calcular distancia
            $sql = "SELECT *,
                    (6371 * acos(cos(radians(%f)) * cos(radians(latitud)) * cos(radians(longitud) - radians(%f)) + sin(radians(%f)) * sin(radians(latitud)))) AS distancia
                    FROM $tabla_estaciones
                    WHERE estado = 'activa'
                    HAVING distancia <= %d
                    ORDER BY distancia ASC";

            $estaciones = $wpdb->get_results($wpdb->prepare($sql, $lat, $lng, $lat, $radio_km));
        }

        return [
            'success' => true,
            'estaciones' => array_map(function($e) {
                return [
                    'id' => $e->id,
                    'nombre' => $e->nombre,
                    'direccion' => $e->direccion,
                    'lat' => floatval($e->latitud),
                    'lng' => floatval($e->longitud),
                    'bicicletas_disponibles' => $e->bicicletas_disponibles,
                    'capacidad_total' => $e->capacidad_total,
                    'distancia_km' => isset($e->distancia) ? round($e->distancia, 2) : null,
                ];
            }, $estaciones),
        ];
    }

    /**
     * Componentes web del módulo
     *
     * IA Features futuras:
     * - Predicción de disponibilidad en tiempo real
     * - Sugerencia de rutas optimizadas
     * - Recomendación de tipo de bici según destino
     */
    public function get_web_components() {
        return [
            'hero_bicis' => [
                'label' => __('Hero Bicicletas', 'flavor-chat-ia'),
                'category' => 'hero',
                'icon' => 'dashicons-admin-site',
                'fields' => [
                    'titulo' => ['type' => 'text', 'default' => __('Bicicletas Compartidas', 'flavor-chat-ia')],
                    'subtitulo' => ['type' => 'textarea', 'default' => __('Movilidad sostenible y saludable', 'flavor-chat-ia')],
                    'imagen_fondo' => ['type' => 'image', 'default' => ''],
                    'mostrar_mapa_estaciones' => ['type' => 'toggle', 'default' => true],
                ],
                'template' => 'bicicletas/hero',
            ],
            'mapa_estaciones' => [
                'label' => __('Mapa de Estaciones', 'flavor-chat-ia'),
                'category' => 'content',
                'icon' => 'dashicons-location',
                'fields' => [
                    'altura_mapa' => ['type' => 'number', 'default' => 500],
                    'zoom_inicial' => ['type' => 'number', 'default' => 13],
                    'mostrar_disponibilidad' => ['type' => 'toggle', 'default' => true],
                ],
                'template' => 'bicicletas/mapa',
            ],
            'tipos_bicicletas' => [
                'label' => __('Tipos de Bicicletas', 'flavor-chat-ia'),
                'category' => 'features',
                'icon' => 'dashicons-admin-site',
                'fields' => [
                    'titulo' => ['type' => 'text', 'default' => __('Elige tu Bicicleta', 'flavor-chat-ia')],
                    'mostrar_precios' => ['type' => 'toggle', 'default' => true],
                ],
                'template' => 'bicicletas/tipos',
            ],
            'como_usar' => [
                'label' => __('Cómo Usar', 'flavor-chat-ia'),
                'category' => 'content',
                'icon' => 'dashicons-info',
                'fields' => [
                    'titulo' => ['type' => 'text', 'default' => __('¿Cómo funciona?', 'flavor-chat-ia')],
                    'paso1' => ['type' => 'text', 'default' => __('Encuentra estación cercana', 'flavor-chat-ia')],
                    'paso2' => ['type' => 'text', 'default' => __('Escanea código QR', 'flavor-chat-ia')],
                    'paso3' => ['type' => 'text', 'default' => __('¡Pedalea!', 'flavor-chat-ia')],
                ],
                'template' => 'bicicletas/como-usar',
            ],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function get_tool_definitions() {
        return [
            [
                'name' => 'bicicletas_estaciones',
                'description' => 'Ver estaciones de bicicletas cercanas',
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
**Bicicletas Compartidas**

Sistema de préstamo de bicicletas gestionado por la comunidad.

**Tipos de bicicletas:**
- Urbanas
- De montaña
- Eléctricas
- Infantiles
- De carga

**Cómo funciona:**
1. Encuentra estación cercana
2. Elige bicicleta disponible
3. Escanea código QR o introduce número
4. Disfruta tu viaje
5. Devuelve en cualquier estación

**Tarifas:**
- Gratis las primeras 2 horas
- Tarifa por hora después
- Abonos mensuales disponibles
- Fianza reembolsable

**Equipamiento incluido:**
- Casco obligatorio
- Candado de seguridad
- Luces delanteras y traseras
- Kit de herramientas básico (estaciones)
KNOWLEDGE;
    }

    /**
     * {@inheritdoc}
     */
    public function get_faqs() {
        return [
            [
                'pregunta' => '¿Qué pasa si la bicicleta se avería?',
                'respuesta' => 'Reporta el problema desde la app inmediatamente. No pagarás por el tiempo de avería.',
            ],
            [
                'pregunta' => '¿Puedo reservar una bicicleta?',
                'respuesta' => 'Sí, puedes reservar con hasta 2 horas de antelación.',
            ],
            [
                'pregunta' => '¿Dónde puedo devolverla?',
                'respuesta' => 'En cualquier estación con espacio disponible, no tiene que ser la misma.',
            ],
        ];
    }
}

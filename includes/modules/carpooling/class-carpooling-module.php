<?php
/**
 * Módulo de Carpooling para Chat IA
 *
 * @package FlavorChatIA
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Módulo de Carpooling - Sistema de viajes compartidos
 */
class Flavor_Chat_Carpooling_Module extends Flavor_Chat_Module_Base {

    /**
     * Constructor
     */
    public function __construct() {
        $this->id = 'carpooling';
        $this->name = __('Carpooling', 'flavor-chat-ia');
        $this->description = __('Sistema de viajes compartidos entre vecinos para reducir costes y emisiones.', 'flavor-chat-ia');

        parent::__construct();
    }

    /**
     * {@inheritdoc}
     */
    public function can_activate() {
        global $wpdb;
        $tabla_viajes = $wpdb->prefix . 'flavor_carpooling_viajes';

        return Flavor_Chat_Helpers::tabla_existe($tabla_viajes);
    }

    /**
     * {@inheritdoc}
     */
    public function get_activation_error() {
        if (!$this->can_activate()) {
            return __('Las tablas de Carpooling no están creadas. Se crearán automáticamente al activar.', 'flavor-chat-ia');
        }
        return '';
    }

    /**
     * {@inheritdoc}
     */
    protected function get_default_settings() {
        return [
            'disponible_app' => 'cliente',
            'requiere_verificacion_conductor' => true,
            'permite_valoraciones' => true,
            'dias_anticipacion_maxima' => 30,
            'max_pasajeros_por_viaje' => 4,
            'permite_mascotas' => true,
            'permite_equipaje_grande' => true,
            'radio_busqueda_km' => 5,
            'calculo_coste_automatico' => true,
            'precio_por_km' => 0.15,
            'comision_plataforma_porcentaje' => 0,
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
        $tabla_viajes = $wpdb->prefix . 'flavor_carpooling_viajes';

        if (!Flavor_Chat_Helpers::tabla_existe($tabla_viajes)) {
            $this->create_tables();
        }
    }

    /**
     * Crea las tablas necesarias
     */
    private function create_tables() {
        global $wpdb;
        $charset_collate = $wpdb->get_charset_collate();

        $tabla_viajes = $wpdb->prefix . 'flavor_carpooling_viajes';
        $tabla_reservas = $wpdb->prefix . 'flavor_carpooling_reservas';
        $tabla_valoraciones = $wpdb->prefix . 'flavor_carpooling_valoraciones';

        $sql_viajes = "CREATE TABLE IF NOT EXISTS $tabla_viajes (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            conductor_id bigint(20) unsigned NOT NULL,
            origen varchar(255) NOT NULL,
            origen_lat decimal(10,7) NOT NULL,
            origen_lng decimal(10,7) NOT NULL,
            destino varchar(255) NOT NULL,
            destino_lat decimal(10,7) NOT NULL,
            destino_lng decimal(10,7) NOT NULL,
            fecha_hora datetime NOT NULL,
            plazas_disponibles int(11) NOT NULL,
            plazas_totales int(11) NOT NULL,
            precio_por_plaza decimal(10,2) NOT NULL,
            vehiculo_marca varchar(100) DEFAULT NULL,
            vehiculo_modelo varchar(100) DEFAULT NULL,
            vehiculo_color varchar(50) DEFAULT NULL,
            vehiculo_matricula varchar(20) DEFAULT NULL,
            permite_fumar tinyint(1) DEFAULT 0,
            permite_mascotas tinyint(1) DEFAULT 0,
            permite_equipaje_grande tinyint(1) DEFAULT 0,
            preferencias text DEFAULT NULL COMMENT 'JSON',
            notas text DEFAULT NULL,
            es_recurrente tinyint(1) DEFAULT 0,
            recurrencia_dias text DEFAULT NULL COMMENT 'JSON',
            estado enum('publicado','completo','en_curso','finalizado','cancelado') DEFAULT 'publicado',
            fecha_creacion datetime DEFAULT CURRENT_TIMESTAMP,
            fecha_actualizacion datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY conductor_id (conductor_id),
            KEY fecha_hora (fecha_hora),
            KEY estado (estado),
            KEY origen_coords (origen_lat, origen_lng),
            KEY destino_coords (destino_lat, destino_lng)
        ) $charset_collate;";

        $sql_reservas = "CREATE TABLE IF NOT EXISTS $tabla_reservas (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            viaje_id bigint(20) unsigned NOT NULL,
            pasajero_id bigint(20) unsigned NOT NULL,
            plazas_reservadas int(11) DEFAULT 1,
            punto_recogida varchar(255) DEFAULT NULL,
            punto_recogida_lat decimal(10,7) DEFAULT NULL,
            punto_recogida_lng decimal(10,7) DEFAULT NULL,
            punto_bajada varchar(255) DEFAULT NULL,
            punto_bajada_lat decimal(10,7) DEFAULT NULL,
            punto_bajada_lng decimal(10,7) DEFAULT NULL,
            coste_total decimal(10,2) NOT NULL,
            estado enum('solicitada','confirmada','rechazada','cancelada','completada') DEFAULT 'solicitada',
            valoracion_conductor int(11) DEFAULT NULL,
            valoracion_pasajero int(11) DEFAULT NULL,
            comentario_pasajero text DEFAULT NULL,
            fecha_solicitud datetime DEFAULT CURRENT_TIMESTAMP,
            fecha_confirmacion datetime DEFAULT NULL,
            PRIMARY KEY (id),
            KEY viaje_id (viaje_id),
            KEY pasajero_id (pasajero_id),
            KEY estado (estado)
        ) $charset_collate;";

        $sql_valoraciones = "CREATE TABLE IF NOT EXISTS $tabla_valoraciones (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            viaje_id bigint(20) unsigned NOT NULL,
            reserva_id bigint(20) unsigned NOT NULL,
            valorador_id bigint(20) unsigned NOT NULL,
            valorado_id bigint(20) unsigned NOT NULL,
            tipo enum('conductor','pasajero') NOT NULL,
            puntuacion int(11) NOT NULL,
            comentario text DEFAULT NULL,
            aspectos text DEFAULT NULL COMMENT 'JSON: puntualidad, amabilidad, limpieza, conduccion',
            fecha_creacion datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            UNIQUE KEY valoracion_unica (reserva_id, valorador_id),
            KEY viaje_id (viaje_id),
            KEY valorado_id (valorado_id)
        ) $charset_collate;";

        require_once ABSPATH . 'wp-admin/includes/upgrade.php';
        dbDelta($sql_viajes);
        dbDelta($sql_reservas);
        dbDelta($sql_valoraciones);
    }

    /**
     * {@inheritdoc}
     */
    public function get_actions() {
        return [
            'buscar_viajes' => [
                'description' => 'Buscar viajes disponibles',
                'params' => ['origen_lat', 'origen_lng', 'destino_lat', 'destino_lng', 'fecha', 'radio_km'],
            ],
            'publicar_viaje' => [
                'description' => 'Publicar nuevo viaje',
                'params' => ['origen', 'destino', 'fecha_hora', 'plazas', 'precio'],
            ],
            'reservar_plaza' => [
                'description' => 'Reservar plaza en viaje',
                'params' => ['viaje_id', 'plazas'],
            ],
            'mis_viajes_conductor' => [
                'description' => 'Mis viajes como conductor',
                'params' => [],
            ],
            'mis_viajes_pasajero' => [
                'description' => 'Mis viajes como pasajero',
                'params' => [],
            ],
            'confirmar_reserva' => [
                'description' => 'Confirmar o rechazar reserva (conductor)',
                'params' => ['reserva_id', 'accion'],
            ],
            'valorar_viaje' => [
                'description' => 'Valorar conductor o pasajero',
                'params' => ['reserva_id', 'puntuacion', 'comentario'],
            ],
            'historial_viajes' => [
                'description' => 'Historial de viajes realizados',
                'params' => ['usuario_id'],
            ],
            // Admin actions
            'estadisticas_carpooling' => [
                'description' => 'Estadísticas generales (admin)',
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
     * Acción: Buscar viajes
     */
    private function action_buscar_viajes($params) {
        global $wpdb;
        $tabla_viajes = $wpdb->prefix . 'flavor_carpooling_viajes';

        $origen_lat = floatval($params['origen_lat'] ?? 0);
        $origen_lng = floatval($params['origen_lng'] ?? 0);
        $destino_lat = floatval($params['destino_lat'] ?? 0);
        $destino_lng = floatval($params['destino_lng'] ?? 0);
        $fecha = sanitize_text_field($params['fecha'] ?? date('Y-m-d'));
        $radio_km = absint($params['radio_km'] ?? $this->settings['radio_busqueda_km']);

        // Búsqueda con haversine formula para calcular distancia
        $sql = "SELECT *,
                (6371 * acos(cos(radians(%f)) * cos(radians(origen_lat)) * cos(radians(origen_lng) - radians(%f)) + sin(radians(%f)) * sin(radians(origen_lat)))) AS distancia_origen,
                (6371 * acos(cos(radians(%f)) * cos(radians(destino_lat)) * cos(radians(destino_lng) - radians(%f)) + sin(radians(%f)) * sin(radians(destino_lat)))) AS distancia_destino
                FROM $tabla_viajes
                WHERE estado = 'publicado'
                AND plazas_disponibles > 0
                AND DATE(fecha_hora) = %s
                HAVING distancia_origen <= %d AND distancia_destino <= %d
                ORDER BY fecha_hora ASC";

        $viajes = $wpdb->get_results($wpdb->prepare(
            $sql,
            $origen_lat, $origen_lng, $origen_lat,
            $destino_lat, $destino_lng, $destino_lat,
            $fecha, $radio_km, $radio_km
        ));

        return [
            'success' => true,
            'viajes' => array_map(function($v) {
                $conductor = get_userdata($v->conductor_id);
                return [
                    'id' => $v->id,
                    'conductor' => [
                        'id' => $v->conductor_id,
                        'nombre' => $conductor ? $conductor->display_name : 'Usuario',
                        'avatar' => get_avatar_url($v->conductor_id),
                    ],
                    'origen' => $v->origen,
                    'destino' => $v->destino,
                    'fecha_hora' => date('d/m/Y H:i', strtotime($v->fecha_hora)),
                    'plazas_disponibles' => $v->plazas_disponibles,
                    'precio_por_plaza' => floatval($v->precio_por_plaza),
                    'vehiculo' => $v->vehiculo_marca . ' ' . $v->vehiculo_modelo,
                    'distancia_origen_km' => round($v->distancia_origen, 1),
                    'distancia_destino_km' => round($v->distancia_destino, 1),
                ];
            }, $viajes),
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function get_tool_definitions() {
        return [
            [
                'name' => 'carpooling_buscar',
                'description' => 'Buscar viajes compartidos',
                'input_schema' => [
                    'type' => 'object',
                    'properties' => [
                        'origen_lat' => ['type' => 'number', 'description' => 'Latitud origen'],
                        'origen_lng' => ['type' => 'number', 'description' => 'Longitud origen'],
                        'destino_lat' => ['type' => 'number', 'description' => 'Latitud destino'],
                        'destino_lng' => ['type' => 'number', 'description' => 'Longitud destino'],
                        'fecha' => ['type' => 'string', 'description' => 'Fecha YYYY-MM-DD'],
                    ],
                    'required' => ['origen_lat', 'origen_lng', 'destino_lat', 'destino_lng'],
                ],
            ],
        ];
    }

    /**
     * Componentes web del módulo
     */
    public function get_web_components() {
        return [
            'hero' => [
                'label' => __('Hero Carpooling', 'flavor-chat-ia'),
                'description' => __('Sección hero principal con buscador de viajes', 'flavor-chat-ia'),
                'category' => 'hero',
                'icon' => 'dashicons-car',
                'fields' => [
                    'titulo' => [
                        'type' => 'text',
                        'label' => __('Título', 'flavor-chat-ia'),
                        'default' => __('Comparte tu viaje, ahorra dinero', 'flavor-chat-ia'),
                    ],
                    'subtitulo' => [
                        'type' => 'textarea',
                        'label' => __('Subtítulo', 'flavor-chat-ia'),
                        'default' => __('Viaja de forma económica y sostenible con tus vecinos', 'flavor-chat-ia'),
                    ],
                    'imagen_fondo' => [
                        'type' => 'image',
                        'label' => __('Imagen de fondo', 'flavor-chat-ia'),
                        'default' => '',
                    ],
                    'mostrar_buscador' => [
                        'type' => 'toggle',
                        'label' => __('Mostrar buscador', 'flavor-chat-ia'),
                        'default' => true,
                    ],
                ],
                'template' => 'carpooling/hero',
                'preview' => '',
            ],
            'viajes_grid' => [
                'label' => __('Grid de Viajes', 'flavor-chat-ia'),
                'description' => __('Listado de viajes disponibles en formato tarjetas', 'flavor-chat-ia'),
                'category' => 'listings',
                'icon' => 'dashicons-grid-view',
                'fields' => [
                    'titulo' => [
                        'type' => 'text',
                        'label' => __('Título de sección', 'flavor-chat-ia'),
                        'default' => __('Viajes Disponibles', 'flavor-chat-ia'),
                    ],
                    'columnas' => [
                        'type' => 'select',
                        'label' => __('Columnas', 'flavor-chat-ia'),
                        'options' => [2, 3, 4],
                        'default' => 3,
                    ],
                    'filtro_categoria' => [
                        'type' => 'select',
                        'label' => __('Filtrar por', 'flavor-chat-ia'),
                        'options' => ['todos', 'proximos', 'populares'],
                        'default' => 'proximos',
                    ],
                    'mostrar_avatares' => [
                        'type' => 'toggle',
                        'label' => __('Mostrar avatares conductores', 'flavor-chat-ia'),
                        'default' => true,
                    ],
                    'fuente_datos' => [
                        'type' => 'data_source',
                        'label' => __('Fuente de datos', 'flavor-chat-ia'),
                        'post_types' => [],
                        'items_field' => 'items',
                        'default' => 'manual',
                    ],
                    'items' => [
                        'type' => 'repeater',
                        'label' => __('Viajes', 'flavor-chat-ia'),
                        'fields' => [
                            'origen' => ['type' => 'text', 'label' => __('Origen', 'flavor-chat-ia'), 'default' => ''],
                            'destino' => ['type' => 'text', 'label' => __('Destino', 'flavor-chat-ia'), 'default' => ''],
                            'fecha' => ['type' => 'text', 'label' => __('Fecha', 'flavor-chat-ia'), 'default' => ''],
                            'hora' => ['type' => 'text', 'label' => __('Hora', 'flavor-chat-ia'), 'default' => ''],
                            'precio' => ['type' => 'text', 'label' => __('Precio', 'flavor-chat-ia'), 'default' => ''],
                            'plazas' => ['type' => 'number', 'label' => __('Plazas disponibles', 'flavor-chat-ia'), 'default' => 3],
                            'conductor' => ['type' => 'text', 'label' => __('Nombre del conductor', 'flavor-chat-ia'), 'default' => ''],
                        ],
                        'default' => [],
                        'max_items' => 12,
                    ],
                ],
                'template' => 'carpooling/viajes-grid',
            ],
            'como_funciona' => [
                'label' => __('Cómo Funciona', 'flavor-chat-ia'),
                'description' => __('Pasos explicativos del proceso', 'flavor-chat-ia'),
                'category' => 'features',
                'icon' => 'dashicons-info',
                'fields' => [
                    'titulo' => [
                        'type' => 'text',
                        'label' => __('Título', 'flavor-chat-ia'),
                        'default' => __('¿Cómo funciona?', 'flavor-chat-ia'),
                    ],
                    'paso1_titulo' => [
                        'type' => 'text',
                        'label' => __('Paso 1 - Título', 'flavor-chat-ia'),
                        'default' => __('Busca tu viaje', 'flavor-chat-ia'),
                    ],
                    'paso1_texto' => [
                        'type' => 'textarea',
                        'label' => __('Paso 1 - Texto', 'flavor-chat-ia'),
                        'default' => __('Introduce origen, destino y fecha', 'flavor-chat-ia'),
                    ],
                    'paso2_titulo' => [
                        'type' => 'text',
                        'label' => __('Paso 2 - Título', 'flavor-chat-ia'),
                        'default' => __('Reserva tu plaza', 'flavor-chat-ia'),
                    ],
                    'paso2_texto' => [
                        'type' => 'textarea',
                        'label' => __('Paso 2 - Texto', 'flavor-chat-ia'),
                        'default' => __('Selecciona el viaje que mejor se ajuste', 'flavor-chat-ia'),
                    ],
                    'paso3_titulo' => [
                        'type' => 'text',
                        'label' => __('Paso 3 - Título', 'flavor-chat-ia'),
                        'default' => __('¡Viaja!', 'flavor-chat-ia'),
                    ],
                    'paso3_texto' => [
                        'type' => 'textarea',
                        'label' => __('Paso 3 - Texto', 'flavor-chat-ia'),
                        'default' => __('Comparte tu viaje y ahorra', 'flavor-chat-ia'),
                    ],
                ],
                'template' => 'carpooling/como-funciona',
            ],
            'cta_conductor' => [
                'label' => __('CTA Conductor', 'flavor-chat-ia'),
                'description' => __('Llamada a la acción para publicar viajes', 'flavor-chat-ia'),
                'category' => 'cta',
                'icon' => 'dashicons-megaphone',
                'fields' => [
                    'titulo' => [
                        'type' => 'text',
                        'label' => __('Título', 'flavor-chat-ia'),
                        'default' => __('¿Tienes coche? Comparte tus viajes', 'flavor-chat-ia'),
                    ],
                    'texto' => [
                        'type' => 'textarea',
                        'label' => __('Texto', 'flavor-chat-ia'),
                        'default' => __('Recupera parte del coste de tus desplazamientos habituales', 'flavor-chat-ia'),
                    ],
                    'boton_texto' => [
                        'type' => 'text',
                        'label' => __('Texto del botón', 'flavor-chat-ia'),
                        'default' => __('Publicar Viaje', 'flavor-chat-ia'),
                    ],
                    'boton_url' => [
                        'type' => 'url',
                        'label' => __('URL del botón', 'flavor-chat-ia'),
                        'default' => '#',
                    ],
                    'color_fondo' => [
                        'type' => 'color',
                        'label' => __('Color de fondo', 'flavor-chat-ia'),
                        'default' => '#3b82f6',
                    ],
                ],
                'template' => 'carpooling/cta-conductor',
            ],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function get_knowledge_base() {
        return <<<KNOWLEDGE
**Carpooling Comunitario**

Sistema de viajes compartidos entre vecinos para reducir costes y emisiones.

**Características:**
- Publica o busca viajes
- Filtro por origen, destino y fecha
- Cálculo automático de costes
- Sistema de valoraciones
- Viajes recurrentes (trabajo, universidad)
- Chat con conductor/pasajeros

**Seguridad:**
- Verificación de conductores
- Valoraciones y reputación
- Información del vehículo
- Puntos de recogida seguros

**Beneficios:**
- Ahorro en combustible y peajes
- Reduce tráfico y emisiones
- Conoce a tus vecinos
- Viajes más sostenibles
KNOWLEDGE;
    }

    /**
     * {@inheritdoc}
     */
    public function get_faqs() {
        return [
            [
                'pregunta' => '¿Cómo se calcula el precio?',
                'respuesta' => 'El conductor puede establecer el precio o usar nuestro cálculo automático basado en distancia y gastos reales.',
            ],
            [
                'pregunta' => '¿Qué pasa si cancelo?',
                'respuesta' => 'Las cancelaciones afectan tu reputación. Intenta avisar con al menos 24h de antelación.',
            ],
        ];
    }
}

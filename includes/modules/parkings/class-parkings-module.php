<?php
/**
 * Módulo de Parkings Compartidos para Chat IA
 *
 * @package FlavorChatIA
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Módulo de Parkings - Gestión y alquiler de plazas de parking
 */
class Flavor_Chat_Parkings_Module extends Flavor_Chat_Module_Base {

    /**
     * Constructor
     */
    public function __construct() {
        $this->id = 'parkings';
        $this->name = __('Parkings Compartidos', 'flavor-chat-ia');
        $this->description = __('Sistema de gestión y alquiler de plazas de parking entre vecinos.', 'flavor-chat-ia');

        parent::__construct();
    }

    /**
     * {@inheritdoc}
     */
    public function can_activate() {
        global $wpdb;
        $tabla_parkings = $wpdb->prefix . 'flavor_parkings';

        return Flavor_Chat_Helpers::tabla_existe($tabla_parkings);
    }

    /**
     * {@inheritdoc}
     */
    public function get_activation_error() {
        if (!$this->can_activate()) {
            return __('Las tablas de Parkings no están creadas. Se crearán automáticamente al activar.', 'flavor-chat-ia');
        }
        return '';
    }

    /**
     * {@inheritdoc}
     */
    protected function get_default_settings() {
        return [
            'disponible_app' => 'cliente',
            'permite_alquiler_temporal' => true,
            'permite_alquiler_permanente' => true,
            'duracion_minima_horas' => 1,
            'precio_medio_hora' => 1.5,
            'precio_medio_dia' => 10,
            'precio_medio_mes' => 80,
            'comision_plataforma_porcentaje' => 10,
            'requiere_fotos' => true,
            'permite_reservas_anticipadas' => true,
            'dias_anticipacion_maxima' => 90,
            'notificar_liberacion' => true,
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
        $tabla_parkings = $wpdb->prefix . 'flavor_parkings';

        if (!Flavor_Chat_Helpers::tabla_existe($tabla_parkings)) {
            $this->create_tables();
        }
    }

    /**
     * Crea las tablas necesarias
     */
    private function create_tables() {
        global $wpdb;
        $charset_collate = $wpdb->get_charset_collate();

        $tabla_parkings = $wpdb->prefix . 'flavor_parkings';
        $tabla_alquileres = $wpdb->prefix . 'flavor_parkings_alquileres';
        $tabla_disponibilidad = $wpdb->prefix . 'flavor_parkings_disponibilidad';

        $sql_parkings = "CREATE TABLE IF NOT EXISTS $tabla_parkings (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            propietario_id bigint(20) unsigned NOT NULL,
            numero_plaza varchar(50) NOT NULL,
            direccion varchar(500) NOT NULL,
            latitud decimal(10,7) NOT NULL,
            longitud decimal(10,7) NOT NULL,
            tipo enum('cubierto','descubierto','garaje_privado','parking_publico') DEFAULT 'cubierto',
            tipo_vehiculo enum('coche','moto','bici','mixto') DEFAULT 'coche',
            caracteristicas text DEFAULT NULL COMMENT 'JSON',
            medidas_largo_cm int(11) DEFAULT NULL,
            medidas_ancho_cm int(11) DEFAULT NULL,
            medidas_alto_cm int(11) DEFAULT NULL,
            acceso enum('remoto','presencial','codigo','tarjeta') DEFAULT 'codigo',
            instrucciones_acceso text DEFAULT NULL,
            precio_hora decimal(10,2) DEFAULT NULL,
            precio_dia decimal(10,2) DEFAULT NULL,
            precio_mes decimal(10,2) DEFAULT NULL,
            disponible_temporal tinyint(1) DEFAULT 1,
            disponible_permanente tinyint(1) DEFAULT 0,
            fotos text DEFAULT NULL COMMENT 'JSON array de URLs',
            valoracion_media decimal(3,2) DEFAULT 0,
            numero_valoraciones int(11) DEFAULT 0,
            estado enum('activo','ocupado','pausado','inactivo') DEFAULT 'activo',
            fecha_creacion datetime DEFAULT CURRENT_TIMESTAMP,
            fecha_actualizacion datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY propietario_id (propietario_id),
            KEY ubicacion (latitud, longitud),
            KEY estado (estado),
            KEY tipo (tipo)
        ) $charset_collate;";

        $sql_alquileres = "CREATE TABLE IF NOT EXISTS $tabla_alquileres (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            parking_id bigint(20) unsigned NOT NULL,
            arrendatario_id bigint(20) unsigned NOT NULL,
            tipo_alquiler enum('temporal','permanente') DEFAULT 'temporal',
            fecha_inicio datetime NOT NULL,
            fecha_fin datetime DEFAULT NULL,
            precio_acordado decimal(10,2) NOT NULL,
            comision decimal(10,2) DEFAULT 0,
            fianza decimal(10,2) DEFAULT NULL,
            fianza_devuelta tinyint(1) DEFAULT 0,
            vehiculo_matricula varchar(20) DEFAULT NULL,
            vehiculo_marca varchar(100) DEFAULT NULL,
            vehiculo_modelo varchar(100) DEFAULT NULL,
            codigo_acceso varchar(50) DEFAULT NULL,
            notas text DEFAULT NULL,
            estado enum('confirmado','activo','finalizado','cancelado') DEFAULT 'confirmado',
            valoracion int(11) DEFAULT NULL,
            comentario_valoracion text DEFAULT NULL,
            fecha_creacion datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY parking_id (parking_id),
            KEY arrendatario_id (arrendatario_id),
            KEY estado (estado),
            KEY fecha_inicio (fecha_inicio)
        ) $charset_collate;";

        $sql_disponibilidad = "CREATE TABLE IF NOT EXISTS $tabla_disponibilidad (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            parking_id bigint(20) unsigned NOT NULL,
            tipo enum('disponible','no_disponible','reservado') DEFAULT 'disponible',
            fecha_desde datetime NOT NULL,
            fecha_hasta datetime NOT NULL,
            repetir_semanal tinyint(1) DEFAULT 0,
            dias_semana text DEFAULT NULL COMMENT 'JSON array 0=domingo, 6=sabado',
            motivo varchar(255) DEFAULT NULL,
            fecha_creacion datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY parking_id (parking_id),
            KEY fecha_desde (fecha_desde),
            KEY fecha_hasta (fecha_hasta)
        ) $charset_collate;";

        require_once ABSPATH . 'wp-admin/includes/upgrade.php';
        dbDelta($sql_parkings);
        dbDelta($sql_alquileres);
        dbDelta($sql_disponibilidad);
    }

    /**
     * {@inheritdoc}
     */
    public function get_actions() {
        return [
            'buscar_parkings' => [
                'description' => 'Buscar parkings disponibles',
                'params' => ['lat', 'lng', 'fecha_inicio', 'fecha_fin', 'tipo_vehiculo'],
            ],
            'detalle_parking' => [
                'description' => 'Ver detalles de parking',
                'params' => ['parking_id'],
            ],
            'mis_parkings' => [
                'description' => 'Mis plazas de parking publicadas',
                'params' => [],
            ],
            'publicar_parking' => [
                'description' => 'Publicar plaza de parking',
                'params' => ['direccion', 'tipo', 'precios'],
            ],
            'alquilar_parking' => [
                'description' => 'Alquilar plaza',
                'params' => ['parking_id', 'fecha_inicio', 'fecha_fin'],
            ],
            'mis_alquileres' => [
                'description' => 'Mis alquileres activos',
                'params' => [],
            ],
            'gestionar_disponibilidad' => [
                'description' => 'Establecer disponibilidad',
                'params' => ['parking_id', 'fechas'],
            ],
            'valorar_parking' => [
                'description' => 'Valorar plaza alquilada',
                'params' => ['alquiler_id', 'valoracion', 'comentario'],
            ],
            // Admin actions
            'estadisticas_parkings' => [
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
     * Acción: Buscar parkings
     */
    private function action_buscar_parkings($params) {
        global $wpdb;
        $tabla_parkings = $wpdb->prefix . 'flavor_parkings';
        $tabla_alquileres = $wpdb->prefix . 'flavor_parkings_alquileres';

        $lat = floatval($params['lat'] ?? 0);
        $lng = floatval($params['lng'] ?? 0);
        $fecha_inicio = sanitize_text_field($params['fecha_inicio'] ?? date('Y-m-d H:i:s'));
        $fecha_fin = sanitize_text_field($params['fecha_fin'] ?? date('Y-m-d H:i:s', strtotime('+1 day')));
        $tipo_vehiculo = sanitize_text_field($params['tipo_vehiculo'] ?? 'coche');

        // Buscar parkings disponibles en el rango de fechas
        $sql = "SELECT p.*,
                (6371 * acos(cos(radians(%f)) * cos(radians(latitud)) * cos(radians(longitud) - radians(%f)) + sin(radians(%f)) * sin(radians(latitud)))) AS distancia
                FROM $tabla_parkings p
                WHERE p.estado = 'activo'
                AND (p.tipo_vehiculo = %s OR p.tipo_vehiculo = 'mixto')
                AND p.id NOT IN (
                    SELECT parking_id FROM $tabla_alquileres
                    WHERE estado IN ('confirmado', 'activo')
                    AND (
                        (fecha_inicio <= %s AND (fecha_fin IS NULL OR fecha_fin >= %s))
                        OR (fecha_inicio <= %s AND (fecha_fin IS NULL OR fecha_fin >= %s))
                    )
                )
                ORDER BY distancia ASC
                LIMIT 20";

        $parkings = $wpdb->get_results($wpdb->prepare(
            $sql,
            $lat, $lng, $lat,
            $tipo_vehiculo,
            $fecha_inicio, $fecha_inicio,
            $fecha_fin, $fecha_fin
        ));

        return [
            'success' => true,
            'parkings' => array_map(function($p) {
                $propietario = get_userdata($p->propietario_id);
                $fotos = json_decode($p->fotos, true) ?: [];

                return [
                    'id' => $p->id,
                    'numero_plaza' => $p->numero_plaza,
                    'direccion' => $p->direccion,
                    'tipo' => $p->tipo,
                    'distancia_km' => round($p->distancia, 2),
                    'precio_hora' => floatval($p->precio_hora),
                    'precio_dia' => floatval($p->precio_dia),
                    'precio_mes' => floatval($p->precio_mes),
                    'propietario' => $propietario ? $propietario->display_name : 'Propietario',
                    'valoracion' => floatval($p->valoracion_media),
                    'foto_principal' => !empty($fotos) ? $fotos[0] : null,
                ];
            }, $parkings),
        ];
    }

    /**
     * Componentes web del módulo
     *
     * IA Features futuras:
     * - Predicción de disponibilidad según patrones de uso
     * - Sugerencias de precio según ubicación y demanda
     * - Alertas de plazas libres cerca de tu destino
     * - Optimización de rutas incluyendo parking
     */
    public function get_web_components() {
        return [
            'hero_parkings' => [
                'label' => __('Hero Parkings', 'flavor-chat-ia'),
                'category' => 'hero',
                'icon' => 'dashicons-admin-multisite',
                'fields' => [
                    'titulo' => ['type' => 'text', 'default' => __('Parkings Compartidos', 'flavor-chat-ia')],
                    'subtitulo' => ['type' => 'textarea', 'default' => __('Alquila o comparte tu plaza de parking', 'flavor-chat-ia')],
                    'imagen_fondo' => ['type' => 'image', 'default' => ''],
                    'mostrar_buscador' => ['type' => 'toggle', 'default' => true],
                ],
                'template' => 'parkings/hero',
            ],
            'parkings_mapa' => [
                'label' => __('Mapa de Parkings', 'flavor-chat-ia'),
                'category' => 'content',
                'icon' => 'dashicons-location',
                'fields' => [
                    'titulo' => ['type' => 'text', 'default' => __('Parkings Disponibles', 'flavor-chat-ia')],
                    'altura_mapa' => ['type' => 'number', 'default' => 500],
                    'mostrar_precios' => ['type' => 'toggle', 'default' => true],
                    'radio_km' => ['type' => 'number', 'default' => 5],
                ],
                'template' => 'parkings/mapa',
            ],
            'parkings_grid' => [
                'label' => __('Grid de Parkings', 'flavor-chat-ia'),
                'category' => 'listings',
                'icon' => 'dashicons-grid-view',
                'fields' => [
                    'titulo' => ['type' => 'text', 'default' => __('Plazas Disponibles', 'flavor-chat-ia')],
                    'columnas' => ['type' => 'select', 'options' => [2, 3, 4], 'default' => 3],
                    'limite' => ['type' => 'number', 'default' => 9],
                    'tipo' => ['type' => 'text', 'default' => ''],
                ],
                'template' => 'parkings/grid',
            ],
            'cta_propietario' => [
                'label' => __('CTA Publicar Plaza', 'flavor-chat-ia'),
                'category' => 'cta',
                'icon' => 'dashicons-megaphone',
                'fields' => [
                    'titulo' => ['type' => 'text', 'default' => __('¿Tienes una Plaza Libre?', 'flavor-chat-ia')],
                    'descripcion' => ['type' => 'textarea', 'default' => __('Genera ingresos extras compartiendo tu parking', 'flavor-chat-ia')],
                    'boton_texto' => ['type' => 'text', 'default' => __('Publicar mi Plaza', 'flavor-chat-ia')],
                    'boton_url' => ['type' => 'url', 'default' => '#'],
                    'color_fondo' => ['type' => 'color', 'default' => '#10b981'],
                ],
                'template' => 'parkings/cta-propietario',
            ],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function get_tool_definitions() {
        return [
            [
                'name' => 'parkings_buscar',
                'description' => 'Buscar plazas de parking disponibles',
                'input_schema' => [
                    'type' => 'object',
                    'properties' => [
                        'lat' => ['type' => 'number', 'description' => 'Latitud'],
                        'lng' => ['type' => 'number', 'description' => 'Longitud'],
                        'fecha_inicio' => ['type' => 'string', 'description' => 'Fecha inicio'],
                        'fecha_fin' => ['type' => 'string', 'description' => 'Fecha fin'],
                    ],
                    'required' => ['lat', 'lng'],
                ],
            ],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function get_knowledge_base() {
        return <<<KNOWLEDGE
**Parkings Compartidos**

Alquila o comparte tu plaza de parking con vecinos cuando no la uses.

**Tipos de alquiler:**
- Por horas (mínimo 1h)
- Por días
- Mensual (permanente)

**Tipos de parking:**
- Cubierto
- Descubierto
- Garaje privado
- Parking público

**Cómo funciona:**
1. Publica tu plaza indicando disponibilidad
2. Establece tus precios
3. Los vecinos reservan cuando lo necesitan
4. Recibes el pago automáticamente
5. Sistema de valoraciones mutuas

**Seguridad:**
- Verificación de usuarios
- Información del vehículo
- Código de acceso temporal
- Seguro de responsabilidad civil
- Sistema de valoraciones

**Gana dinero extra:**
- Tu plaza trabajando mientras no la usas
- Precios competitivos sin intermediarios
- Pago seguro a través de la plataforma
KNOWLEDGE;
    }

    /**
     * {@inheritdoc}
     */
    public function get_faqs() {
        return [
            [
                'pregunta' => '¿Qué pasa si hay un problema con mi vehículo?',
                'respuesta' => 'Todos los alquileres incluyen seguro de responsabilidad civil. Contacta al propietario inmediatamente.',
            ],
            [
                'pregunta' => '¿Puedo cancelar una reserva?',
                'respuesta' => 'Sí, con al menos 24h de antelación sin coste. Cancelaciones tardías tienen penalización.',
            ],
            [
                'pregunta' => '¿Cómo recibo el pago?',
                'respuesta' => 'Los pagos se procesan automáticamente y se transfieren a tu cuenta tras cada alquiler.',
            ],
        ];
    }
}

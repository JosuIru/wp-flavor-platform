<?php
/**
 * Módulo de Compostaje Comunitario para Chat IA
 *
 * @package FlavorChatIA
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Módulo de Compostaje - Gestión de compostaje comunitario
 */
class Flavor_Chat_Compostaje_Module extends Flavor_Chat_Module_Base {

    /**
     * Constructor
     */
    public function __construct() {
        $this->id = 'compostaje';
        $this->name = __('Compostaje Comunitario', 'flavor-chat-ia');
        $this->description = __('Sistema de compostaje comunitario - convierte residuos orgánicos en abono natural.', 'flavor-chat-ia');

        parent::__construct();
    }

    /**
     * {@inheritdoc}
     */
    public function can_activate() {
        global $wpdb;
        $tabla_composteras = $wpdb->prefix . 'flavor_composteras';

        return Flavor_Chat_Helpers::tabla_existe($tabla_composteras);
    }

    /**
     * {@inheritdoc}
     */
    public function get_activation_error() {
        if (!$this->can_activate()) {
            return __('Las tablas de Compostaje no están creadas. Se crearán automáticamente al activar.', 'flavor-chat-ia');
        }
        return '';
    }

    /**
     * {@inheritdoc}
     */
    protected function get_default_settings() {
        return [
            'disponible_app' => 'cliente',
            'permite_recoger_compost' => true,
            'kg_minimos_recogida' => 5,
            'puntos_por_kg_depositado' => 5,
            'sistema_turnos_volteo' => true,
            'notificar_compost_listo' => true,
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
        $tabla_composteras = $wpdb->prefix . 'flavor_composteras';

        if (!Flavor_Chat_Helpers::tabla_existe($tabla_composteras)) {
            $this->create_tables();
        }
    }

    /**
     * Crea las tablas necesarias
     */
    private function create_tables() {
        global $wpdb;
        $charset_collate = $wpdb->get_charset_collate();

        $tabla_composteras = $wpdb->prefix . 'flavor_composteras';
        $tabla_depositos = $wpdb->prefix . 'flavor_compostaje_depositos';
        $tabla_recogidas = $wpdb->prefix . 'flavor_compostaje_recogidas';
        $tabla_mantenimiento = $wpdb->prefix . 'flavor_compostaje_mantenimiento';

        $sql_composteras = "CREATE TABLE IF NOT EXISTS $tabla_composteras (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            nombre varchar(255) NOT NULL,
            ubicacion varchar(500) NOT NULL,
            latitud decimal(10,7) NOT NULL,
            longitud decimal(10,7) NOT NULL,
            tipo enum('comunitaria','domestica','industrial') DEFAULT 'comunitaria',
            capacidad_litros int(11) NOT NULL,
            nivel_actual int(11) DEFAULT 0 COMMENT 'Porcentaje 0-100',
            temperatura_actual decimal(5,2) DEFAULT NULL COMMENT 'Celsius',
            humedad_actual int(11) DEFAULT NULL COMMENT 'Porcentaje',
            fase enum('llenado','maduracion','listo','vacio') DEFAULT 'llenado',
            fecha_inicio_maduracion datetime DEFAULT NULL,
            fecha_compost_listo datetime DEFAULT NULL,
            responsable_id bigint(20) unsigned DEFAULT NULL,
            instrucciones text DEFAULT NULL,
            estado enum('activa','mantenimiento','inactiva') DEFAULT 'activa',
            fecha_creacion datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY ubicacion (latitud, longitud),
            KEY responsable_id (responsable_id),
            KEY fase (fase),
            KEY estado (estado)
        ) $charset_collate;";

        $sql_depositos = "CREATE TABLE IF NOT EXISTS $tabla_depositos (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            compostera_id bigint(20) unsigned NOT NULL,
            usuario_id bigint(20) unsigned NOT NULL,
            tipo_residuo enum('frutas_verduras','restos_cocina','papel_carton','hojas_jardin','otro') DEFAULT 'frutas_verduras',
            cantidad_kg decimal(10,2) NOT NULL,
            puntos_ganados int(11) DEFAULT 0,
            notas text DEFAULT NULL,
            fecha_deposito datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY compostera_id (compostera_id),
            KEY usuario_id (usuario_id),
            KEY fecha_deposito (fecha_deposito)
        ) $charset_collate;";

        $sql_recogidas = "CREATE TABLE IF NOT EXISTS $tabla_recogidas (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            compostera_id bigint(20) unsigned NOT NULL,
            usuario_id bigint(20) unsigned NOT NULL,
            cantidad_kg decimal(10,2) NOT NULL,
            uso_destino varchar(255) DEFAULT NULL,
            puntos_usados int(11) DEFAULT 0,
            fecha_recogida datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY compostera_id (compostera_id),
            KEY usuario_id (usuario_id),
            KEY fecha_recogida (fecha_recogida)
        ) $charset_collate;";

        $sql_mantenimiento = "CREATE TABLE IF NOT EXISTS $tabla_mantenimiento (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            compostera_id bigint(20) unsigned NOT NULL,
            usuario_id bigint(20) unsigned NOT NULL,
            tipo enum('volteo','riego','medicion','tamizado','otro') DEFAULT 'volteo',
            temperatura decimal(5,2) DEFAULT NULL,
            humedad int(11) DEFAULT NULL,
            observaciones text DEFAULT NULL,
            foto_url varchar(500) DEFAULT NULL,
            fecha_mantenimiento datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY compostera_id (compostera_id),
            KEY usuario_id (usuario_id),
            KEY tipo (tipo),
            KEY fecha_mantenimiento (fecha_mantenimiento)
        ) $charset_collate;";

        require_once ABSPATH . 'wp-admin/includes/upgrade.php';
        dbDelta($sql_composteras);
        dbDelta($sql_depositos);
        dbDelta($sql_recogidas);
        dbDelta($sql_mantenimiento);
    }

    /**
     * {@inheritdoc}
     */
    public function get_actions() {
        return [
            'composteras_cercanas' => [
                'description' => 'Encontrar composteras cercanas',
                'params' => ['lat', 'lng'],
            ],
            'estado_compostera' => [
                'description' => 'Ver estado de compostera',
                'params' => ['compostera_id'],
            ],
            'registrar_deposito' => [
                'description' => 'Registrar depósito de orgánicos',
                'params' => ['compostera_id', 'tipo_residuo', 'cantidad_kg'],
            ],
            'solicitar_compost' => [
                'description' => 'Solicitar compost terminado',
                'params' => ['compostera_id', 'cantidad_kg'],
            ],
            'mis_depositos' => [
                'description' => 'Historial de mis depósitos',
                'params' => [],
            ],
            'mis_puntos_compost' => [
                'description' => 'Ver mis puntos de compostaje',
                'params' => [],
            ],
            'registrar_mantenimiento' => [
                'description' => 'Registrar tarea de mantenimiento',
                'params' => ['compostera_id', 'tipo', 'observaciones'],
            ],
            'guia_compostaje' => [
                'description' => 'Guía de qué compostar',
                'params' => [],
            ],
            'calendario_volteo' => [
                'description' => 'Ver calendario de volteo',
                'params' => ['compostera_id'],
            ],
            // Admin actions
            'estadisticas_compostaje' => [
                'description' => 'Estadísticas de compostaje (admin)',
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
     * Acción: Composteras cercanas
     */
    private function action_composteras_cercanas($params) {
        global $wpdb;
        $tabla_composteras = $wpdb->prefix . 'flavor_composteras';

        $lat = floatval($params['lat'] ?? 0);
        $lng = floatval($params['lng'] ?? 0);

        if ($lat != 0 && $lng != 0) {
            $sql = "SELECT *,
                    (6371 * acos(cos(radians(%f)) * cos(radians(latitud)) * cos(radians(longitud) - radians(%f)) + sin(radians(%f)) * sin(radians(latitud)))) AS distancia
                    FROM $tabla_composteras
                    WHERE estado = 'activa'
                    ORDER BY distancia ASC
                    LIMIT 20";

            $composteras = $wpdb->get_results($wpdb->prepare($sql, $lat, $lng, $lat));
        } else {
            $composteras = $wpdb->get_results("SELECT * FROM $tabla_composteras WHERE estado = 'activa' ORDER BY nombre");
        }

        return [
            'success' => true,
            'composteras' => array_map(function($c) {
                return [
                    'id' => $c->id,
                    'nombre' => $c->nombre,
                    'ubicacion' => $c->ubicacion,
                    'tipo' => $c->tipo,
                    'nivel_actual' => $c->nivel_actual,
                    'fase' => $c->fase,
                    'acepta_depositos' => in_array($c->fase, ['llenado', 'maduracion']),
                    'compost_disponible' => $c->fase == 'listo',
                    'distancia_km' => isset($c->distancia) ? round($c->distancia, 2) : null,
                ];
            }, $composteras),
        ];
    }

    /**
     * Componentes web del módulo
     *
     * IA Features futuras:
     * - Predicción de maduración del compost según temperatura y humedad
     * - Sugerencias de equilibrio verde/marrón (relación C/N)
     * - Alertas de condiciones óptimas (temperatura, humedad, pH)
     * - Recordatorios inteligentes de volteo según fase
     */
    public function get_web_components() {
        return [
            'hero_compostaje' => [
                'label' => __('Hero Compostaje', 'flavor-chat-ia'),
                'category' => 'hero',
                'icon' => 'dashicons-carrot',
                'fields' => [
                    'titulo' => ['type' => 'text', 'default' => __('Compostaje Comunitario', 'flavor-chat-ia')],
                    'subtitulo' => ['type' => 'textarea', 'default' => __('Convierte residuos orgánicos en abono natural', 'flavor-chat-ia')],
                    'imagen_fondo' => ['type' => 'image', 'default' => ''],
                    'mostrar_impacto' => ['type' => 'toggle', 'default' => true],
                ],
                'template' => 'compostaje/hero',
            ],
            'mapa_composteras' => [
                'label' => __('Mapa de Composteras', 'flavor-chat-ia'),
                'category' => 'content',
                'icon' => 'dashicons-location',
                'fields' => [
                    'titulo' => ['type' => 'text', 'default' => __('Encuentra tu Compostera', 'flavor-chat-ia')],
                    'altura_mapa' => ['type' => 'number', 'default' => 500],
                    'mostrar_estado' => ['type' => 'toggle', 'default' => true],
                    'mostrar_nivel' => ['type' => 'toggle', 'default' => true],
                ],
                'template' => 'compostaje/mapa',
            ],
            'guia_compostaje' => [
                'label' => __('Guía de Compostaje', 'flavor-chat-ia'),
                'category' => 'content',
                'icon' => 'dashicons-book',
                'fields' => [
                    'titulo' => ['type' => 'text', 'default' => __('Qué Compostar', 'flavor-chat-ia')],
                    'mostrar_si' => ['type' => 'toggle', 'default' => true],
                    'mostrar_no' => ['type' => 'toggle', 'default' => true],
                    'estilo' => ['type' => 'select', 'options' => ['lista', 'tarjetas'], 'default' => 'tarjetas'],
                ],
                'template' => 'compostaje/guia',
            ],
            'proceso_compostaje' => [
                'label' => __('Proceso de Compostaje', 'flavor-chat-ia'),
                'category' => 'features',
                'icon' => 'dashicons-update',
                'fields' => [
                    'titulo' => ['type' => 'text', 'default' => __('Cómo Funciona', 'flavor-chat-ia')],
                    'mostrar_fases' => ['type' => 'toggle', 'default' => true],
                    'mostrar_tiempos' => ['type' => 'toggle', 'default' => true],
                ],
                'template' => 'compostaje/proceso',
            ],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function get_tool_definitions() {
        return [
            [
                'name' => 'compostaje_composteras',
                'description' => 'Encontrar composteras comunitarias cercanas',
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
**Compostaje Comunitario**

Convierte tus residuos orgánicos en abono natural de calidad.

**Qué es el compostaje:**
- Proceso natural de descomposición
- Convierte orgánicos en abono
- Reduce basura hasta un 40%
- Mejora la tierra del jardín/huerto

**Qué SÍ compostar:**
- Restos de frutas y verduras
- Posos de café y té
- Cáscaras de huevo trituradas
- Hojas secas
- Papel y cartón sin tinta
- Restos de poda triturados

**Qué NO compostar:**
- Carne, pescado, lácteos
- Aceites y grasas
- Plantas enfermas
- Excrementos de mascotas
- Cenizas de carbón
- Cítricos en exceso

**Cómo funciona:**
1. Deposita orgánicos en compostera
2. Sistema registra tu aporte
3. Ganas puntos por kg depositado
4. Mantenimiento colectivo rotativo
5. Recoges compost cuando está listo

**Fases del compost:**
- Llenado (2-3 meses)
- Maduración (3-4 meses)
- Listo (compost terminado)

**Usos del compost:**
- Abono para huertos
- Mejora suelos de jardines
- Macetas y plantas
- Proyectos comunitarios

**Sistema de puntos:**
- Gana puntos por depositar
- Usa puntos para recoger compost
- Fomenta la participación
KNOWLEDGE;
    }

    /**
     * {@inheritdoc}
     */
    public function get_faqs() {
        return [
            [
                'pregunta' => '¿Huele mal el compostaje?',
                'respuesta' => 'No, si se hace bien. Un compost equilibrado huele a tierra de bosque.',
            ],
            [
                'pregunta' => '¿Cuánto tarda en estar listo?',
                'respuesta' => 'Entre 4 y 6 meses normalmente, dependiendo de temperatura y mantenimiento.',
            ],
            [
                'pregunta' => '¿Puedo compostar cítricos?',
                'respuesta' => 'Sí, pero en pequeñas cantidades. Su acidez puede ralentizar el proceso.',
            ],
        ];
    }
}

<?php
/**
 * Módulo de Reciclaje para Chat IA
 *
 * @package FlavorChatIA
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Módulo de Reciclaje - Gestión de reciclaje comunitario
 */
class Flavor_Chat_Reciclaje_Module extends Flavor_Chat_Module_Base {

    /**
     * Constructor
     */
    public function __construct() {
        $this->id = 'reciclaje';
        $this->name = __('Reciclaje Comunitario', 'flavor-chat-ia');
        $this->description = __('Sistema de gestión de reciclaje, puntos limpios y economía circular en la comunidad.', 'flavor-chat-ia');

        parent::__construct();
    }

    /**
     * {@inheritdoc}
     */
    public function can_activate() {
        global $wpdb;
        $tabla_puntos_reciclaje = $wpdb->prefix . 'flavor_reciclaje_puntos';

        return Flavor_Chat_Helpers::tabla_existe($tabla_puntos_reciclaje);
    }

    /**
     * {@inheritdoc}
     */
    public function get_activation_error() {
        if (!$this->can_activate()) {
            return __('Las tablas de Reciclaje no están creadas. Se crearán automáticamente al activar.', 'flavor-chat-ia');
        }
        return '';
    }

    /**
     * {@inheritdoc}
     */
    protected function get_default_settings() {
        return [
            'disponible_app' => 'cliente',
            'puntos_por_kg' => 10,
            'permite_canje_puntos' => true,
            'notificar_recogidas' => true,
            'permite_reportar_contenedores' => true,
            'categorias_reciclaje' => ['papel', 'plastico', 'vidrio', 'organico', 'electronico', 'ropa', 'aceite', 'pilas'],
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
        $tabla_puntos_reciclaje = $wpdb->prefix . 'flavor_reciclaje_puntos';

        if (!Flavor_Chat_Helpers::tabla_existe($tabla_puntos_reciclaje)) {
            $this->create_tables();
        }
    }

    /**
     * Crea las tablas necesarias
     */
    private function create_tables() {
        global $wpdb;
        $charset_collate = $wpdb->get_charset_collate();

        $tabla_puntos_reciclaje = $wpdb->prefix . 'flavor_reciclaje_puntos';
        $tabla_depositos = $wpdb->prefix . 'flavor_reciclaje_depositos';
        $tabla_recogidas = $wpdb->prefix . 'flavor_reciclaje_recogidas';
        $tabla_contenedores = $wpdb->prefix . 'flavor_reciclaje_contenedores';

        $sql_puntos = "CREATE TABLE IF NOT EXISTS $tabla_puntos_reciclaje (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            nombre varchar(255) NOT NULL,
            tipo enum('punto_limpio','contenedor_comunitario','centro_acopio','movil') DEFAULT 'contenedor_comunitario',
            direccion varchar(500) NOT NULL,
            latitud decimal(10,7) NOT NULL,
            longitud decimal(10,7) NOT NULL,
            materiales_aceptados text NOT NULL COMMENT 'JSON array',
            horario text DEFAULT NULL,
            contacto varchar(255) DEFAULT NULL,
            instrucciones text DEFAULT NULL,
            foto_url varchar(500) DEFAULT NULL,
            estado enum('activo','lleno','mantenimiento','inactivo') DEFAULT 'activo',
            fecha_creacion datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY ubicacion (latitud, longitud),
            KEY tipo (tipo),
            KEY estado (estado)
        ) $charset_collate;";

        $sql_depositos = "CREATE TABLE IF NOT EXISTS $tabla_depositos (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            usuario_id bigint(20) unsigned NOT NULL,
            punto_reciclaje_id bigint(20) unsigned NOT NULL,
            tipo_material varchar(50) NOT NULL,
            cantidad_kg decimal(10,2) NOT NULL,
            puntos_ganados int(11) DEFAULT 0,
            foto_url varchar(500) DEFAULT NULL,
            verificado tinyint(1) DEFAULT 0,
            verificado_por bigint(20) unsigned DEFAULT NULL,
            fecha_deposito datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY usuario_id (usuario_id),
            KEY punto_reciclaje_id (punto_reciclaje_id),
            KEY tipo_material (tipo_material),
            KEY fecha_deposito (fecha_deposito)
        ) $charset_collate;";

        $sql_recogidas = "CREATE TABLE IF NOT EXISTS $tabla_recogidas (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            tipo_recogida enum('programada','a_demanda','urgente') DEFAULT 'programada',
            zona varchar(255) NOT NULL,
            tipos_residuos text NOT NULL COMMENT 'JSON',
            fecha_programada datetime NOT NULL,
            hora_inicio time DEFAULT NULL,
            hora_fin time DEFAULT NULL,
            ruta text DEFAULT NULL COMMENT 'JSON de coordenadas',
            notas text DEFAULT NULL,
            estado enum('programada','en_curso','completada','cancelada') DEFAULT 'programada',
            fecha_creacion datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY fecha_programada (fecha_programada),
            KEY estado (estado)
        ) $charset_collate;";

        $sql_contenedores = "CREATE TABLE IF NOT EXISTS $tabla_contenedores (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            punto_reciclaje_id bigint(20) unsigned NOT NULL,
            tipo_residuo varchar(50) NOT NULL,
            capacidad_litros int(11) DEFAULT NULL,
            nivel_llenado int(11) DEFAULT 0 COMMENT 'Porcentaje 0-100',
            necesita_vaciado tinyint(1) DEFAULT 0,
            ultima_recogida datetime DEFAULT NULL,
            reportes_problema int(11) DEFAULT 0,
            estado enum('operativo','lleno','danado','fuera_servicio') DEFAULT 'operativo',
            fecha_instalacion datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY punto_reciclaje_id (punto_reciclaje_id),
            KEY tipo_residuo (tipo_residuo),
            KEY necesita_vaciado (necesita_vaciado)
        ) $charset_collate;";

        require_once ABSPATH . 'wp-admin/includes/upgrade.php';
        dbDelta($sql_puntos);
        dbDelta($sql_depositos);
        dbDelta($sql_recogidas);
        dbDelta($sql_contenedores);
    }

    /**
     * {@inheritdoc}
     */
    public function get_actions() {
        return [
            'puntos_cercanos' => [
                'description' => 'Encontrar puntos de reciclaje cercanos',
                'params' => ['lat', 'lng', 'tipo_material'],
            ],
            'calendario_recogidas' => [
                'description' => 'Ver calendario de recogidas',
                'params' => ['zona', 'tipo_residuo'],
            ],
            'registrar_deposito' => [
                'description' => 'Registrar depósito de material',
                'params' => ['punto_id', 'tipo_material', 'cantidad_kg'],
            ],
            'mis_puntos_reciclaje' => [
                'description' => 'Ver mis puntos acumulados',
                'params' => [],
            ],
            'canje_puntos' => [
                'description' => 'Canjear puntos por recompensas',
                'params' => ['recompensa_id'],
            ],
            'reportar_contenedor' => [
                'description' => 'Reportar problema con contenedor',
                'params' => ['contenedor_id', 'problema'],
            ],
            'guia_reciclaje' => [
                'description' => 'Guía de qué reciclar y cómo',
                'params' => ['tipo_material'],
            ],
            // Admin actions
            'estadisticas_reciclaje' => [
                'description' => 'Estadísticas de reciclaje (admin)',
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
     * Acción: Puntos cercanos
     */
    private function action_puntos_cercanos($params) {
        global $wpdb;
        $tabla_puntos = $wpdb->prefix . 'flavor_reciclaje_puntos';

        $lat = floatval($params['lat'] ?? 0);
        $lng = floatval($params['lng'] ?? 0);
        $tipo_material = sanitize_text_field($params['tipo_material'] ?? '');

        $where = "estado = 'activo'";
        if (!empty($tipo_material)) {
            $where .= $wpdb->prepare(" AND materiales_aceptados LIKE %s", '%' . $wpdb->esc_like($tipo_material) . '%');
        }

        if ($lat != 0 && $lng != 0) {
            $sql = "SELECT *,
                    (6371 * acos(cos(radians(%f)) * cos(radians(latitud)) * cos(radians(longitud) - radians(%f)) + sin(radians(%f)) * sin(radians(latitud)))) AS distancia
                    FROM $tabla_puntos
                    WHERE $where
                    ORDER BY distancia ASC
                    LIMIT 20";

            $puntos = $wpdb->get_results($wpdb->prepare($sql, $lat, $lng, $lat));
        } else {
            $puntos = $wpdb->get_results("SELECT * FROM $tabla_puntos WHERE $where ORDER BY nombre LIMIT 20");
        }

        return [
            'success' => true,
            'puntos' => array_map(function($p) {
                return [
                    'id' => $p->id,
                    'nombre' => $p->nombre,
                    'tipo' => $p->tipo,
                    'direccion' => $p->direccion,
                    'lat' => floatval($p->latitud),
                    'lng' => floatval($p->longitud),
                    'materiales' => json_decode($p->materiales_aceptados, true),
                    'horario' => $p->horario,
                    'distancia_km' => isset($p->distancia) ? round($p->distancia, 2) : null,
                ];
            }, $puntos),
        ];
    }

    /**
     * Componentes web del módulo
     *
     * IA Features futuras:
     * - Reconocimiento de materiales por foto (clasificación automática)
     * - Rutas optimizadas a puntos de reciclaje cercanos
     * - Sugerencias de reciclaje según historial del usuario
     * - Chatbot para dudas de reciclaje
     */
    public function get_web_components() {
        return [
            'hero_reciclaje' => [
                'label' => __('Hero Reciclaje', 'flavor-chat-ia'),
                'category' => 'hero',
                'icon' => 'dashicons-admin-site',
                'fields' => [
                    'titulo' => ['type' => 'text', 'default' => __('Reciclaje Comunitario', 'flavor-chat-ia')],
                    'subtitulo' => ['type' => 'textarea', 'default' => __('Recicla, gana puntos y cuida el planeta', 'flavor-chat-ia')],
                    'imagen_fondo' => ['type' => 'image', 'default' => ''],
                    'mostrar_puntos' => ['type' => 'toggle', 'default' => true],
                ],
                'template' => 'reciclaje/hero',
            ],
            'puntos_reciclaje' => [
                'label' => __('Puntos de Reciclaje', 'flavor-chat-ia'),
                'category' => 'content',
                'icon' => 'dashicons-location',
                'fields' => [
                    'titulo' => ['type' => 'text', 'default' => __('Encuentra tu Punto de Reciclaje', 'flavor-chat-ia')],
                    'altura_mapa' => ['type' => 'number', 'default' => 500],
                    'mostrar_materiales' => ['type' => 'toggle', 'default' => true],
                    'filtrar_por_tipo' => ['type' => 'toggle', 'default' => true],
                ],
                'template' => 'reciclaje/puntos',
            ],
            'calendario_recogidas' => [
                'label' => __('Calendario de Recogidas', 'flavor-chat-ia'),
                'category' => 'content',
                'icon' => 'dashicons-calendar-alt',
                'fields' => [
                    'titulo' => ['type' => 'text', 'default' => __('Calendario de Recogidas', 'flavor-chat-ia')],
                    'vista' => ['type' => 'select', 'options' => ['mensual', 'semanal'], 'default' => 'mensual'],
                    'mostrar_zona' => ['type' => 'toggle', 'default' => true],
                ],
                'template' => 'reciclaje/calendario',
            ],
            'guia_reciclaje' => [
                'label' => __('Guía de Reciclaje', 'flavor-chat-ia'),
                'category' => 'content',
                'icon' => 'dashicons-book-alt',
                'fields' => [
                    'titulo' => ['type' => 'text', 'default' => __('Qué va en cada Contenedor', 'flavor-chat-ia')],
                    'estilo' => ['type' => 'select', 'options' => ['tarjetas', 'acordeon'], 'default' => 'tarjetas'],
                    'mostrar_colores' => ['type' => 'toggle', 'default' => true],
                ],
                'template' => 'reciclaje/guia',
            ],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function get_tool_definitions() {
        return [
            [
                'name' => 'reciclaje_puntos_cercanos',
                'description' => 'Encontrar puntos de reciclaje cercanos',
                'input_schema' => [
                    'type' => 'object',
                    'properties' => [
                        'lat' => ['type' => 'number', 'description' => 'Latitud'],
                        'lng' => ['type' => 'number', 'description' => 'Longitud'],
                        'tipo_material' => ['type' => 'string', 'description' => 'Tipo de material a reciclar'],
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
**Reciclaje Comunitario**

Sistema integral de gestión de reciclaje con recompensas por participar.

**Tipos de reciclaje:**
- Papel y cartón
- Plástico y envases
- Vidrio
- Orgánico
- Electrónico (RAEE)
- Ropa y textil
- Aceite usado
- Pilas y baterías

**Puntos de reciclaje:**
- Puntos limpios municipales
- Contenedores comunitarios
- Centros de acopio especializados
- Recogida móvil

**Sistema de puntos:**
- Gana puntos por reciclar
- Canjea por descuentos locales
- Premios comunitarios
- Rankings de reciclaje

**Calendario de recogidas:**
- Recogidas programadas por zona
- Alertas personalizadas
- Recogida de voluminosos
- Residuos especiales

**Guías de reciclaje:**
- Qué va en cada contenedor
- Cómo preparar los residuos
- Qué NO reciclar
- Alternativas de reutilización
KNOWLEDGE;
    }

    /**
     * {@inheritdoc}
     */
    public function get_faqs() {
        return [
            [
                'pregunta' => '¿Dónde reciclo aparatos electrónicos?',
                'respuesta' => 'En los puntos limpios municipales o en recogidas especiales de RAEE.',
            ],
            [
                'pregunta' => '¿Cómo funcionan los puntos?',
                'respuesta' => 'Ganas puntos por cada kg de material reciclado. Pueden canjearse por descuentos en comercios locales.',
            ],
            [
                'pregunta' => '¿Qué hago con el aceite usado?',
                'respuesta' => 'Nunca por el fregadero. Guárdalo en botellas y llévalo a puntos de recogida de aceite.',
            ],
        ];
    }
}

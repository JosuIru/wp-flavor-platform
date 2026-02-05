<?php
/**
 * Módulo de Incidencias Urbanas para Chat IA
 *
 * @package FlavorChatIA
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Módulo de Incidencias Urbanas - Reportar problemas del barrio/ciudad
 */
class Flavor_Chat_Incidencias_Module extends Flavor_Chat_Module_Base {

    /**
     * Constructor
     */
    public function __construct() {
        $this->id = 'incidencias';
        $this->name = __('Incidencias Urbanas', 'flavor-chat-ia');
        $this->description = __('Reportar y gestionar incidencias del barrio: baches, alumbrado, limpieza, etc.', 'flavor-chat-ia');

        parent::__construct();
    }

    /**
     * {@inheritdoc}
     */
    public function can_activate() {
        global $wpdb;
        $tabla_incidencias = $wpdb->prefix . 'flavor_incidencias';

        return Flavor_Chat_Helpers::tabla_existe($tabla_incidencias);
    }

    /**
     * {@inheritdoc}
     */
    public function get_activation_error() {
        if (!$this->can_activate()) {
            return __('Las tablas de Incidencias no están creadas. Activa el módulo para crearlas automáticamente.', 'flavor-chat-ia');
        }
        return '';
    }

    /**
     * {@inheritdoc}
     */
    protected function get_default_settings() {
        return [
            'requiere_validacion' => false,
            'permite_anonimo' => true,
            'tiempo_respuesta_objetivo' => 48, // horas
            'notificar_actualizaciones' => true,
            'categorias' => [
                'alumbrado' => __('Alumbrado público', 'flavor-chat-ia'),
                'limpieza' => __('Limpieza y residuos', 'flavor-chat-ia'),
                'via_publica' => __('Vía pública (baches, aceras)', 'flavor-chat-ia'),
                'mobiliario' => __('Mobiliario urbano', 'flavor-chat-ia'),
                'parques' => __('Parques y jardines', 'flavor-chat-ia'),
                'ruido' => __('Ruidos y molestias', 'flavor-chat-ia'),
                'agua' => __('Agua y alcantarillado', 'flavor-chat-ia'),
                'señalizacion' => __('Señalización', 'flavor-chat-ia'),
                'otros' => __('Otros', 'flavor-chat-ia'),
            ],
            'prioridades' => [
                'baja' => __('Baja', 'flavor-chat-ia'),
                'media' => __('Media', 'flavor-chat-ia'),
                'alta' => __('Alta', 'flavor-chat-ia'),
                'urgente' => __('Urgente', 'flavor-chat-ia'),
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
        $tabla_incidencias = $wpdb->prefix . 'flavor_incidencias';

        if (!Flavor_Chat_Helpers::tabla_existe($tabla_incidencias)) {
            $this->create_tables();
        }
    }

    /**
     * Crea las tablas necesarias
     */
    private function create_tables() {
        global $wpdb;
        $charset_collate = $wpdb->get_charset_collate();

        $tabla_incidencias = $wpdb->prefix . 'flavor_incidencias';
        $tabla_seguimiento = $wpdb->prefix . 'flavor_incidencias_seguimiento';

        $sql_incidencias = "CREATE TABLE IF NOT EXISTS $tabla_incidencias (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            numero_incidencia varchar(50) NOT NULL,
            usuario_id bigint(20) unsigned DEFAULT NULL,
            categoria varchar(50) NOT NULL,
            titulo varchar(255) NOT NULL,
            descripcion text NOT NULL,
            direccion varchar(500) DEFAULT NULL,
            latitud decimal(10,8) DEFAULT NULL,
            longitud decimal(11,8) DEFAULT NULL,
            prioridad enum('baja','media','alta','urgente') DEFAULT 'media',
            estado enum('pendiente','en_proceso','resuelta','cerrada','rechazada') DEFAULT 'pendiente',
            imagenes text DEFAULT NULL,
            fecha_reporte datetime NOT NULL,
            fecha_asignacion datetime DEFAULT NULL,
            fecha_resolucion datetime DEFAULT NULL,
            asignado_a bigint(20) unsigned DEFAULT NULL,
            departamento varchar(100) DEFAULT NULL,
            votos_ciudadanos int(11) DEFAULT 0,
            visibilidad enum('publica','privada') DEFAULT 'publica',
            notas_internas text DEFAULT NULL,
            fecha_creacion datetime DEFAULT CURRENT_TIMESTAMP,
            fecha_actualizacion datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            UNIQUE KEY numero_incidencia (numero_incidencia),
            KEY usuario_id (usuario_id),
            KEY categoria (categoria),
            KEY estado (estado),
            KEY prioridad (prioridad),
            KEY fecha_reporte (fecha_reporte)
        ) $charset_collate;";

        $sql_seguimiento = "CREATE TABLE IF NOT EXISTS $tabla_seguimiento (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            incidencia_id bigint(20) unsigned NOT NULL,
            usuario_id bigint(20) unsigned DEFAULT NULL,
            tipo enum('comentario','cambio_estado','asignacion','resolucion') NOT NULL,
            contenido text NOT NULL,
            estado_anterior varchar(50) DEFAULT NULL,
            estado_nuevo varchar(50) DEFAULT NULL,
            es_publico tinyint(1) DEFAULT 1,
            fecha_creacion datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY incidencia_id (incidencia_id),
            KEY usuario_id (usuario_id)
        ) $charset_collate;";

        require_once ABSPATH . 'wp-admin/includes/upgrade.php';
        dbDelta($sql_incidencias);
        dbDelta($sql_seguimiento);
    }

    /**
     * {@inheritdoc}
     */
    public function get_actions() {
        return [
            'reportar_incidencia' => [
                'description' => 'Reportar una nueva incidencia',
                'params' => ['categoria', 'titulo', 'descripcion', 'direccion', 'latitud', 'longitud'],
            ],
            'listar_incidencias' => [
                'description' => 'Listar incidencias por estado o categoría',
                'params' => ['estado', 'categoria', 'limite'],
            ],
            'ver_incidencia' => [
                'description' => 'Ver detalles de una incidencia',
                'params' => ['incidencia_id'],
            ],
            'mis_incidencias' => [
                'description' => 'Ver incidencias que he reportado',
                'params' => ['estado'],
            ],
            'seguir_incidencia' => [
                'description' => 'Seguir el estado de una incidencia',
                'params' => ['incidencia_id'],
            ],
            'votar_incidencia' => [
                'description' => 'Votar una incidencia como importante',
                'params' => ['incidencia_id'],
            ],
            'actualizar_estado' => [
                'description' => 'Actualizar estado de incidencia (solo admin)',
                'params' => ['incidencia_id', 'nuevo_estado', 'comentario'],
            ],
            'mapa_incidencias' => [
                'description' => 'Ver incidencias en el mapa',
                'params' => ['categoria', 'estado'],
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
     * Acción: Reportar incidencia
     */
    private function action_reportar_incidencia($params) {
        $usuario_id = get_current_user_id();

        $categoria = sanitize_text_field($params['categoria'] ?? '');
        $titulo = sanitize_text_field($params['titulo'] ?? '');
        $descripcion = sanitize_textarea_field($params['descripcion'] ?? '');

        if (empty($categoria) || empty($titulo) || empty($descripcion)) {
            return [
                'success' => false,
                'error' => 'Categoría, título y descripción son obligatorios.',
            ];
        }

        global $wpdb;
        $tabla_incidencias = $wpdb->prefix . 'flavor_incidencias';

        // Generar número de incidencia
        $numero = $this->generar_numero_incidencia();

        $resultado = $wpdb->insert(
            $tabla_incidencias,
            [
                'numero_incidencia' => $numero,
                'usuario_id' => $usuario_id ?: null,
                'categoria' => $categoria,
                'titulo' => $titulo,
                'descripcion' => $descripcion,
                'direccion' => sanitize_text_field($params['direccion'] ?? ''),
                'latitud' => !empty($params['latitud']) ? floatval($params['latitud']) : null,
                'longitud' => !empty($params['longitud']) ? floatval($params['longitud']) : null,
                'prioridad' => 'media',
                'estado' => 'pendiente',
                'fecha_reporte' => current_time('mysql'),
                'visibilidad' => 'publica',
            ],
            ['%s', '%d', '%s', '%s', '%s', '%s', '%f', '%f', '%s', '%s', '%s', '%s']
        );

        if ($resultado === false) {
            return [
                'success' => false,
                'error' => 'Error al registrar la incidencia.',
            ];
        }

        return [
            'success' => true,
            'incidencia_id' => $wpdb->insert_id,
            'numero_incidencia' => $numero,
            'mensaje' => sprintf(
                '¡Incidencia reportada con éxito! Número de seguimiento: %s. Te notificaremos cuando sea atendida.',
                $numero
            ),
        ];
    }

    /**
     * Acción: Listar incidencias
     */
    private function action_listar_incidencias($params) {
        global $wpdb;
        $tabla_incidencias = $wpdb->prefix . 'flavor_incidencias';

        $where = ["visibilidad = 'publica'"];
        $prepare_values = [];

        if (!empty($params['estado'])) {
            $where[] = 'estado = %s';
            $prepare_values[] = $params['estado'];
        }

        if (!empty($params['categoria'])) {
            $where[] = 'categoria = %s';
            $prepare_values[] = $params['categoria'];
        }

        $limite = absint($params['limite'] ?? 20);
        $sql_where = implode(' AND ', $where);

        $sql = "SELECT * FROM $tabla_incidencias WHERE $sql_where ORDER BY fecha_reporte DESC LIMIT %d";
        $prepare_values[] = $limite;

        $incidencias = $wpdb->get_results($wpdb->prepare($sql, ...$prepare_values));

        return [
            'success' => true,
            'total' => count($incidencias),
            'incidencias' => array_map(function($i) {
                return [
                    'id' => $i->id,
                    'numero' => $i->numero_incidencia,
                    'titulo' => $i->titulo,
                    'categoria' => $i->categoria,
                    'estado' => $i->estado,
                    'prioridad' => $i->prioridad,
                    'fecha' => date('d/m/Y H:i', strtotime($i->fecha_reporte)),
                    'votos' => $i->votos_ciudadanos,
                    'ubicacion' => [
                        'direccion' => $i->direccion,
                        'lat' => $i->latitud,
                        'lng' => $i->longitud,
                    ],
                ];
            }, $incidencias),
        ];
    }

    /**
     * Acción: Ver detalles de incidencia
     */
    private function action_ver_incidencia($params) {
        $incidencia_id = absint($params['incidencia_id'] ?? 0);

        if (!$incidencia_id) {
            return [
                'success' => false,
                'error' => 'ID de incidencia inválido.',
            ];
        }

        global $wpdb;
        $tabla_incidencias = $wpdb->prefix . 'flavor_incidencias';
        $tabla_seguimiento = $wpdb->prefix . 'flavor_incidencias_seguimiento';

        $incidencia = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM $tabla_incidencias WHERE id = %d",
            $incidencia_id
        ));

        if (!$incidencia) {
            return [
                'success' => false,
                'error' => 'Incidencia no encontrada.',
            ];
        }

        // Obtener seguimiento
        $seguimiento = $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM $tabla_seguimiento WHERE incidencia_id = %d AND es_publico = 1 ORDER BY fecha_creacion DESC",
            $incidencia_id
        ));

        return [
            'success' => true,
            'incidencia' => [
                'id' => $incidencia->id,
                'numero' => $incidencia->numero_incidencia,
                'titulo' => $incidencia->titulo,
                'descripcion' => $incidencia->descripcion,
                'categoria' => $incidencia->categoria,
                'estado' => $incidencia->estado,
                'prioridad' => $incidencia->prioridad,
                'direccion' => $incidencia->direccion,
                'ubicacion' => [
                    'lat' => floatval($incidencia->latitud),
                    'lng' => floatval($incidencia->longitud),
                ],
                'fecha_reporte' => date('d/m/Y H:i', strtotime($incidencia->fecha_reporte)),
                'fecha_resolucion' => $incidencia->fecha_resolucion ? date('d/m/Y H:i', strtotime($incidencia->fecha_resolucion)) : null,
                'votos' => $incidencia->votos_ciudadanos,
                'seguimiento' => array_map(function($s) {
                    return [
                        'tipo' => $s->tipo,
                        'contenido' => $s->contenido,
                        'fecha' => date('d/m/Y H:i', strtotime($s->fecha_creacion)),
                    ];
                }, $seguimiento),
            ],
        ];
    }

    /**
     * Genera número de incidencia único
     */
    private function generar_numero_incidencia() {
        global $wpdb;
        $tabla_incidencias = $wpdb->prefix . 'flavor_incidencias';

        $anio = date('Y');
        $ultimo = $wpdb->get_var($wpdb->prepare(
            "SELECT numero_incidencia FROM $tabla_incidencias WHERE numero_incidencia LIKE %s ORDER BY id DESC LIMIT 1",
            'INC-' . $anio . '-%'
        ));

        if ($ultimo) {
            preg_match('/INC-\d+-(\d+)/', $ultimo, $matches);
            $numero = isset($matches[1]) ? intval($matches[1]) + 1 : 1;
        } else {
            $numero = 1;
        }

        return sprintf('INC-%s-%04d', $anio, $numero);
    }

    /**
     * {@inheritdoc}
     */
    public function get_tool_definitions() {
        return [
            [
                'name' => 'incidencias_reportar',
                'description' => 'Reportar una incidencia del barrio (bache, farola rota, basura, etc.)',
                'input_schema' => [
                    'type' => 'object',
                    'properties' => [
                        'categoria' => [
                            'type' => 'string',
                            'description' => 'Categoría de la incidencia',
                            'enum' => ['alumbrado', 'limpieza', 'via_publica', 'mobiliario', 'parques', 'ruido', 'agua', 'señalizacion', 'otros'],
                        ],
                        'titulo' => [
                            'type' => 'string',
                            'description' => 'Título breve de la incidencia',
                        ],
                        'descripcion' => [
                            'type' => 'string',
                            'description' => 'Descripción detallada del problema',
                        ],
                        'direccion' => [
                            'type' => 'string',
                            'description' => 'Dirección o ubicación',
                        ],
                    ],
                    'required' => ['categoria', 'titulo', 'descripcion'],
                ],
            ],
            [
                'name' => 'incidencias_listar',
                'description' => 'Ver incidencias reportadas en el barrio',
                'input_schema' => [
                    'type' => 'object',
                    'properties' => [
                        'estado' => [
                            'type' => 'string',
                            'description' => 'Filtrar por estado',
                            'enum' => ['pendiente', 'en_proceso', 'resuelta', 'cerrada'],
                        ],
                        'categoria' => [
                            'type' => 'string',
                            'description' => 'Filtrar por categoría',
                        ],
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
**Sistema de Incidencias Urbanas**

Permite a los vecinos reportar problemas del barrio y hacer seguimiento.

**Tipos de incidencias:**
- Alumbrado público: farolas rotas, cables sueltos
- Limpieza: basura, contenedores llenos
- Vía pública: baches, aceras rotas, señalización
- Mobiliario urbano: bancos, papeleras, fuentes
- Parques y jardines: árboles, césped, juegos infantiles
- Ruidos y molestias
- Agua y alcantarillado: fugas, atascos
- Otros

**Proceso:**
1. El vecino reporta la incidencia con foto y ubicación
2. Se asigna un número de seguimiento
3. El ayuntamiento valida y asigna al departamento
4. Se actualiza el estado hasta la resolución
5. El vecino recibe notificaciones

**Estados:**
- Pendiente: Recién reportada
- En proceso: Asignada y en gestión
- Resuelta: Problema solucionado
- Cerrada: Verificada y cerrada
KNOWLEDGE;
    }

    /**
     * {@inheritdoc}
     */
    public function get_faqs() {
        return [
            [
                'pregunta' => '¿Cómo reporto un problema del barrio?',
                'respuesta' => 'Desde la app, ve a Incidencias, pulsa "Nueva incidencia", selecciona la categoría y describe el problema con ubicación.',
            ],
            [
                'pregunta' => '¿Cuánto tardan en resolver una incidencia?',
                'respuesta' => 'Depende de la prioridad y el tipo. Las urgentes se atienden en 24-48h. Puedes hacer seguimiento con tu número de incidencia.',
            ],
        ];
    }

    /**
     * Componentes web del módulo
     */
    public function get_web_components() {
        return [
            'hero' => [
                'label' => __('Hero Incidencias', 'flavor-chat-ia'),
                'description' => __('Sección hero con formulario rápido de reporte', 'flavor-chat-ia'),
                'category' => 'hero',
                'icon' => 'dashicons-warning',
                'fields' => [
                    'titulo' => [
                        'type' => 'text',
                        'label' => __('Título', 'flavor-chat-ia'),
                        'default' => __('Reportar Incidencias', 'flavor-chat-ia'),
                    ],
                    'subtitulo' => [
                        'type' => 'textarea',
                        'label' => __('Subtítulo', 'flavor-chat-ia'),
                        'default' => __('Ayúdanos a mejorar tu barrio', 'flavor-chat-ia'),
                    ],
                    'mostrar_formulario' => [
                        'type' => 'toggle',
                        'label' => __('Mostrar formulario rápido', 'flavor-chat-ia'),
                        'default' => true,
                    ],
                ],
                'template' => 'incidencias/hero',
            ],
            'mapa_incidencias' => [
                'label' => __('Mapa de Incidencias', 'flavor-chat-ia'),
                'description' => __('Mapa interactivo con ubicación de reportes', 'flavor-chat-ia'),
                'category' => 'content',
                'icon' => 'dashicons-location',
                'fields' => [
                    'titulo' => [
                        'type' => 'text',
                        'label' => __('Título', 'flavor-chat-ia'),
                        'default' => __('Mapa de Incidencias', 'flavor-chat-ia'),
                    ],
                    'altura_mapa' => [
                        'type' => 'number',
                        'label' => __('Altura del mapa (px)', 'flavor-chat-ia'),
                        'default' => 400,
                    ],
                ],
                'template' => 'incidencias/mapa',
            ],
            'incidencias_grid' => [
                'label' => __('Grid de Incidencias', 'flavor-chat-ia'),
                'description' => __('Listado de incidencias recientes', 'flavor-chat-ia'),
                'category' => 'listings',
                'icon' => 'dashicons-list-view',
                'fields' => [
                    'titulo' => [
                        'type' => 'text',
                        'label' => __('Título', 'flavor-chat-ia'),
                        'default' => __('Incidencias Recientes', 'flavor-chat-ia'),
                    ],
                    'mostrar_estado' => [
                        'type' => 'toggle',
                        'label' => __('Mostrar estado', 'flavor-chat-ia'),
                        'default' => true,
                    ],
                    'limite' => [
                        'type' => 'number',
                        'label' => __('Número máximo', 'flavor-chat-ia'),
                        'default' => 6,
                    ],
                ],
                'template' => 'incidencias/incidencias-grid',
            ],
            'estadisticas' => [
                'label' => __('Estadísticas Incidencias', 'flavor-chat-ia'),
                'description' => __('Métricas de resolución y tiempos', 'flavor-chat-ia'),
                'category' => 'content',
                'icon' => 'dashicons-chart-bar',
                'fields' => [
                    'titulo' => [
                        'type' => 'text',
                        'label' => __('Título', 'flavor-chat-ia'),
                        'default' => __('Estadísticas', 'flavor-chat-ia'),
                    ],
                ],
                'template' => 'incidencias/estadisticas',
            ],
            'categorias' => [
                'label' => __('Categorías de Incidencias', 'flavor-chat-ia'),
                'description' => __('Tipos de problemas que se pueden reportar', 'flavor-chat-ia'),
                'category' => 'listings',
                'icon' => 'dashicons-category',
                'fields' => [
                    'titulo' => [
                        'type' => 'text',
                        'label' => __('Título', 'flavor-chat-ia'),
                        'default' => __('¿Qué puedes reportar?', 'flavor-chat-ia'),
                    ],
                ],
                'template' => 'incidencias/categorias',
            ],
        ];
    }
}

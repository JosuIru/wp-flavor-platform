<?php
/**
 * Módulo Kulturaka - Red Cultural Descentralizada
 *
 * Orquesta las 3 vistas principales (Espacio, Artista, Comunidad)
 * integrando módulos existentes del ecosistema Flavor.
 *
 * @package FlavorChatIA
 * @subpackage Modules\Kulturaka
 * @since 3.3.0
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Clase principal del módulo Kulturaka
 */
class Flavor_Chat_Kulturaka_Module extends Flavor_Chat_Module_Base {

    use Flavor_Module_Admin_Pages_Trait;
    use Flavor_Module_Notifications_Trait;

    /**
     * Constructor
     */
    public function __construct() {
        $this->id = 'kulturaka';
        $this->name = __('Kulturaka', 'flavor-chat-ia');
        $this->description = __('Red cultural descentralizada que conecta artistas, espacios y comunidades. Integra eventos, crowdfunding, banco de tiempo y economía solidaria.', 'flavor-chat-ia');
        $this->module_role = 'ecosystem';
        $this->ecosystem_requires_modules = ['eventos', 'espacios-comunes', 'socios'];
        $this->ecosystem_supports_modules = ['crowdfunding', 'banco-tiempo', 'comunidades', 'colectivos'];
        $this->dashboard_parent_module = 'kulturaka';
        $this->dashboard_satellite_priority = 5;
        $this->dashboard_client_contexts = ['kulturaka', 'cultura', 'artistas', 'espacios'];
        $this->dashboard_admin_contexts = ['kulturaka', 'red-cultural', 'admin'];

        $this->gailu_principios = ['economia_solidaria', 'cooperacion', 'cultura_accesible'];
        $this->gailu_contribuye_a = ['cultura', 'redistribucion', 'cohesion'];

        parent::__construct();
    }

    /**
     * {@inheritdoc}
     */
    public function can_activate() {
        // Kulturaka requiere que eventos y espacios-comunes estén activos
        $eventos_activo = class_exists('Flavor_Chat_Eventos_Module');
        $espacios_activo = class_exists('Flavor_Chat_Espacios_Comunes_Module');
        $socios_activo = class_exists('Flavor_Chat_Socios_Module');

        return $eventos_activo && $espacios_activo && $socios_activo;
    }

    /**
     * {@inheritdoc}
     */
    public function get_activation_error() {
        if (!$this->can_activate()) {
            return __('Kulturaka requiere los módulos Eventos, Espacios Comunes y Miembros activos.', 'flavor-chat-ia');
        }
        return '';
    }

    /**
     * {@inheritdoc}
     */
    protected function get_default_settings() {
        return [
            // Distribución de ingresos por defecto (estilo Kulturaka)
            'distribucion_ingresos' => [
                'artista' => 70,
                'espacio' => 10,
                'comunidad' => 10,
                'plataforma' => 5,
                'emergencia' => 5,
            ],

            // Tipos de espacio cultural
            'tipos_espacio' => [
                'gaztetxe' => __('Gaztetxe', 'flavor-chat-ia'),
                'sala_conciertos' => __('Sala de conciertos', 'flavor-chat-ia'),
                'teatro' => __('Teatro', 'flavor-chat-ia'),
                'galeria' => __('Galería', 'flavor-chat-ia'),
                'centro_cultural' => __('Centro cultural', 'flavor-chat-ia'),
                'espacio_publico' => __('Espacio público', 'flavor-chat-ia'),
                'online' => __('Online', 'flavor-chat-ia'),
            ],

            // Tipos de evento cultural
            'tipos_evento' => [
                'concierto' => __('Concierto', 'flavor-chat-ia'),
                'teatro' => __('Teatro', 'flavor-chat-ia'),
                'danza' => __('Danza', 'flavor-chat-ia'),
                'exposicion' => __('Exposición', 'flavor-chat-ia'),
                'cine' => __('Cine', 'flavor-chat-ia'),
                'poesia' => __('Poesía', 'flavor-chat-ia'),
                'taller' => __('Taller', 'flavor-chat-ia'),
                'festival' => __('Festival', 'flavor-chat-ia'),
            ],

            // Nodos geográficos iniciales
            'nodos_default' => [
                ['nombre' => 'Bilbao', 'lat' => 43.2630, 'lng' => -2.9350],
                ['nombre' => 'Donostia', 'lat' => 43.3183, 'lng' => -1.9812],
                ['nombre' => 'Gasteiz', 'lat' => 42.8467, 'lng' => -2.6716],
                ['nombre' => 'Iruña', 'lat' => 42.8125, 'lng' => -1.6458],
            ],

            // Métricas de impacto
            'co2_por_km' => 0.12, // kg CO2 evitado por km de desplazamiento evitado
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function init() {
        $this->maybe_create_tables();

        add_action('init', [$this, 'register_shortcodes']);
        add_action('rest_api_init', [$this, 'register_rest_routes']);
        add_action('wp_enqueue_scripts', [$this, 'enqueue_frontend_assets']);

        // Panel Unificado y Dashboard
        $this->inicializar_dashboard_tab();
        $this->registrar_en_panel_unificado();

        // Cargar vistas
        $this->cargar_vistas();

        // Admin
        add_action('admin_menu', [$this, 'registrar_paginas_admin']);

        // Integración con otros módulos
        $this->registrar_integraciones();
    }

    /**
     * Crea las tablas específicas de Kulturaka
     */
    public function maybe_create_tables() {
        if (!get_option('flavor_kulturaka_db_version')) {
            require_once dirname(__FILE__) . '/install.php';
            flavor_kulturaka_crear_tablas();
        }
    }

    /**
     * Carga las clases de vistas
     */
    private function cargar_vistas() {
        $vistas = ['espacio', 'artista', 'comunidad'];

        foreach ($vistas as $vista) {
            $archivo = dirname(__FILE__) . "/class-kulturaka-vista-{$vista}.php";
            if (file_exists($archivo)) {
                require_once $archivo;
            }
        }
    }

    /**
     * Registra integraciones con otros módulos
     */
    private function registrar_integraciones() {
        // Cuando se crea un evento, verificar si es cultural
        add_action('flavor_evento_creado', [$this, 'procesar_evento_cultural'], 10, 2);

        // Cuando un artista hace una propuesta
        add_action('flavor_evento_propuesta_enviada', [$this, 'notificar_propuesta_espacio'], 10, 2);

        // Métricas de impacto
        add_action('flavor_evento_finalizado', [$this, 'calcular_impacto_evento'], 10, 2);
    }

    // =========================================================
    // Vista Espacio
    // =========================================================

    /**
     * Obtiene datos para la vista de espacio
     */
    public function get_vista_espacio_data($espacio_id) {
        global $wpdb;

        // Espacio base
        $espacio = $this->get_espacio_cultural($espacio_id);
        if (!$espacio) return null;

        // Calendario de disponibilidad
        $calendario = $this->get_calendario_espacio($espacio_id);

        // Propuestas recibidas (eventos con estado propuesta)
        $propuestas = $this->get_propuestas_espacio($espacio_id);

        // Estadísticas de impacto
        $metricas = $this->get_metricas_espacio($espacio_id);

        // Eventos pasados
        $historial = $this->get_historial_eventos_espacio($espacio_id, 5);

        return [
            'espacio' => $espacio,
            'calendario' => $calendario,
            'propuestas' => $propuestas,
            'metricas' => $metricas,
            'historial' => $historial,
        ];
    }

    /**
     * Obtiene un espacio cultural con datos extendidos
     */
    public function get_espacio_cultural($espacio_id) {
        global $wpdb;

        $espacio = $wpdb->get_row($wpdb->prepare("
            SELECT e.*,
                   (SELECT COUNT(*) FROM {$wpdb->prefix}flavor_eventos WHERE espacio_id = e.id AND estado = 'publicado') as total_eventos,
                   (SELECT COALESCE(SUM(inscritos_count), 0) FROM {$wpdb->prefix}flavor_eventos WHERE espacio_id = e.id) as audiencia_total
            FROM {$wpdb->prefix}flavor_espacios_comunes e
            WHERE e.id = %d
        ", $espacio_id));

        if ($espacio) {
            // Añadir rating de artistas
            $espacio->rating_artistas = $this->get_rating_espacio($espacio_id);

            // Tipo label
            $tipos = $this->get_setting('tipos_espacio', []);
            $espacio->tipo_label = $tipos[$espacio->tipo] ?? $espacio->tipo;
        }

        return $espacio;
    }

    /**
     * Obtiene propuestas pendientes para un espacio
     */
    public function get_propuestas_espacio($espacio_id, $limite = 10) {
        global $wpdb;

        $propuestas = $wpdb->get_results($wpdb->prepare("
            SELECT e.*,
                   a.nombre_artistico, a.nivel_artista, a.rating as artista_rating, a.slug as artista_slug,
                   u.display_name as organizador_nombre
            FROM {$wpdb->prefix}flavor_eventos e
            LEFT JOIN {$wpdb->prefix}flavor_socios_artistas a ON e.organizador_id = a.usuario_id
            LEFT JOIN {$wpdb->users} u ON e.organizador_id = u.ID
            WHERE e.metadata LIKE %s
            AND e.estado = 'borrador'
            AND JSON_EXTRACT(e.metadata, '$.es_propuesta') = true
            ORDER BY e.created_at DESC
            LIMIT %d
        ", '%"espacio_destino":' . $espacio_id . '%', $limite));

        return $propuestas ?: [];
    }

    /**
     * Obtiene métricas de impacto de un espacio
     */
    public function get_metricas_espacio($espacio_id) {
        global $wpdb;

        $metricas = $wpdb->get_row($wpdb->prepare("
            SELECT
                COUNT(*) as eventos_realizados,
                COALESCE(SUM(inscritos_count), 0) as audiencia_total,
                COUNT(DISTINCT organizador_id) as artistas_acogidos,
                COALESCE(SUM(JSON_EXTRACT(metadata, '$.co2_evitado')), 0) as co2_evitado
            FROM {$wpdb->prefix}flavor_eventos
            WHERE espacio_id = %d
            AND estado IN ('publicado', 'finalizado')
        ", $espacio_id));

        // Índice de cooperación (calculado)
        $metricas->indice_cooperacion = $this->calcular_indice_cooperacion_espacio($espacio_id);

        return $metricas;
    }

    // =========================================================
    // Vista Artista
    // =========================================================

    /**
     * Obtiene datos para la vista de artista
     */
    public function get_vista_artista_data($artista_id) {
        // Usar el sistema de perfiles de artista de socios
        require_once FLAVOR_CHAT_IA_PATH . 'includes/modules/socios/class-socios-artista-profile.php';
        $artista_manager = Flavor_Socios_Artista_Profile::get_instance();

        $artista = $artista_manager->get_artista($artista_id);
        if (!$artista) return null;

        // Próximos eventos (gira)
        $gira = $this->get_gira_artista($artista->usuario_id);

        // Propuestas enviadas
        $propuestas = $this->get_propuestas_artista($artista->usuario_id);

        // Artistas que sigue
        $siguiendo = $this->get_artistas_siguiendo($artista->usuario_id);

        // Proyectos de crowdfunding
        $proyectos_crowdfunding = $this->get_crowdfunding_artista($artista->usuario_id);

        return [
            'artista' => $artista,
            'gira' => $gira,
            'propuestas' => $propuestas,
            'siguiendo' => $siguiendo,
            'crowdfunding' => $proyectos_crowdfunding,
        ];
    }

    /**
     * Obtiene la gira de un artista
     */
    public function get_gira_artista($usuario_id, $limite = 10) {
        global $wpdb;

        return $wpdb->get_results($wpdb->prepare("
            SELECT e.*,
                   ec.nombre as espacio_nombre, ec.direccion as espacio_direccion,
                   ec.latitud, ec.longitud
            FROM {$wpdb->prefix}flavor_eventos e
            LEFT JOIN {$wpdb->prefix}flavor_espacios_comunes ec ON e.espacio_id = ec.id
            WHERE e.organizador_id = %d
            AND e.estado IN ('publicado', 'confirmado')
            AND e.fecha_inicio >= NOW()
            ORDER BY e.fecha_inicio ASC
            LIMIT %d
        ", $usuario_id, $limite)) ?: [];
    }

    /**
     * Obtiene propuestas enviadas por un artista
     */
    public function get_propuestas_artista($usuario_id) {
        global $wpdb;

        return $wpdb->get_results($wpdb->prepare("
            SELECT e.*,
                   ec.nombre as espacio_nombre,
                   JSON_EXTRACT(e.metadata, '$.estado_propuesta') as estado_propuesta
            FROM {$wpdb->prefix}flavor_eventos e
            LEFT JOIN {$wpdb->prefix}flavor_espacios_comunes ec ON JSON_EXTRACT(e.metadata, '$.espacio_destino') = ec.id
            WHERE e.organizador_id = %d
            AND e.estado = 'borrador'
            AND JSON_EXTRACT(e.metadata, '$.es_propuesta') = true
            ORDER BY e.created_at DESC
        ", $usuario_id)) ?: [];
    }

    /**
     * Obtiene proyectos de crowdfunding de un artista
     */
    public function get_crowdfunding_artista($usuario_id) {
        global $wpdb;
        $tabla = $wpdb->prefix . 'flavor_crowdfunding_proyectos';

        if (!Flavor_Chat_Helpers::tabla_existe($wpdb->prefix . 'flavor_crowdfunding_proyectos')) {
            return [];
        }

        return $wpdb->get_results($wpdb->prepare("
            SELECT * FROM $tabla
            WHERE creador_id = %d
            ORDER BY created_at DESC
        ", $usuario_id)) ?: [];
    }

    // =========================================================
    // Vista Comunidad
    // =========================================================

    /**
     * Obtiene datos para la vista de comunidad
     */
    public function get_vista_comunidad_data($comunidad_id = null, $usuario_id = null) {
        $usuario_id = $usuario_id ?: get_current_user_id();

        // Eventos cercanos
        $eventos_cercanos = $this->get_eventos_cercanos($usuario_id, $comunidad_id);

        // Por categorías
        $categorias = $this->get_categorias_culturales();

        // Proyectos de crowdfunding activos
        $proyectos_activos = $this->get_crowdfunding_activos($comunidad_id);

        // Muro de agradecimientos
        $agradecimientos = $this->get_muro_agradecimientos($comunidad_id);

        // Red de nodos
        $nodos = $this->get_red_nodos();

        // Eventos en otros nodos
        $eventos_otros_nodos = $this->get_eventos_otros_nodos($comunidad_id);

        return [
            'eventos_cercanos' => $eventos_cercanos,
            'categorias' => $categorias,
            'proyectos_activos' => $proyectos_activos,
            'agradecimientos' => $agradecimientos,
            'nodos' => $nodos,
            'eventos_otros_nodos' => $eventos_otros_nodos,
        ];
    }

    /**
     * Obtiene eventos cercanos
     */
    public function get_eventos_cercanos($usuario_id, $comunidad_id = null, $limite = 12) {
        global $wpdb;

        // Por ahora, obtener eventos próximos de la comunidad o todos
        $where_comunidad = $comunidad_id ? $wpdb->prepare("AND e.comunidad_id = %d", $comunidad_id) : "";

        return $wpdb->get_results("
            SELECT e.*,
                   ec.nombre as espacio_nombre,
                   a.nombre_artistico, a.slug as artista_slug
            FROM {$wpdb->prefix}flavor_eventos e
            LEFT JOIN {$wpdb->prefix}flavor_espacios_comunes ec ON e.espacio_id = ec.id
            LEFT JOIN {$wpdb->prefix}flavor_socios_artistas a ON e.organizador_id = a.usuario_id
            WHERE e.estado = 'publicado'
            AND e.fecha_inicio >= NOW()
            $where_comunidad
            ORDER BY e.fecha_inicio ASC
            LIMIT $limite
        ") ?: [];
    }

    /**
     * Obtiene proyectos de crowdfunding activos
     */
    public function get_crowdfunding_activos($comunidad_id = null, $limite = 6) {
        global $wpdb;
        $tabla = $wpdb->prefix . 'flavor_crowdfunding_proyectos';

        if (!Flavor_Chat_Helpers::tabla_existe($wpdb->prefix . 'flavor_crowdfunding_proyectos')) {
            return [];
        }

        $where_comunidad = $comunidad_id ? $wpdb->prepare("AND comunidad_id = %d", $comunidad_id) : "";

        return $wpdb->get_results("
            SELECT *,
                   ROUND((recaudado_eur / NULLIF(objetivo_eur, 0)) * 100, 1) as porcentaje
            FROM $tabla
            WHERE estado = 'activo'
            $where_comunidad
            ORDER BY recaudado_eur DESC
            LIMIT $limite
        ") ?: [];
    }

    /**
     * Obtiene el muro de agradecimientos
     */
    public function get_muro_agradecimientos($comunidad_id = null, $limite = 20) {
        global $wpdb;
        $tabla = $wpdb->prefix . 'flavor_kulturaka_agradecimientos';

        if (!Flavor_Chat_Helpers::tabla_existe($wpdb->prefix . 'flavor_kulturaka_agradecimientos')) {
            return [];
        }

        $where_comunidad = $comunidad_id ? $wpdb->prepare("WHERE g.comunidad_id = %d", $comunidad_id) : "";

        return $wpdb->get_results("
            SELECT g.*,
                   u_from.display_name as de_nombre,
                   u_to.display_name as para_nombre,
                   e.titulo as evento_titulo
            FROM $tabla g
            LEFT JOIN {$wpdb->users} u_from ON g.de_usuario_id = u_from.ID
            LEFT JOIN {$wpdb->users} u_to ON g.para_usuario_id = u_to.ID
            LEFT JOIN {$wpdb->prefix}flavor_eventos e ON g.evento_id = e.id
            $where_comunidad
            ORDER BY g.created_at DESC
            LIMIT $limite
        ") ?: [];
    }

    /**
     * Obtiene la red de nodos
     */
    public function get_red_nodos() {
        global $wpdb;
        $tabla = $wpdb->prefix . 'flavor_kulturaka_nodos';

        if (!Flavor_Chat_Helpers::tabla_existe($wpdb->prefix . 'flavor_kulturaka_nodos')) {
            // Devolver nodos por defecto
            return $this->get_setting('nodos_default', []);
        }

        $nodos = $wpdb->get_results("
            SELECT n.*,
                   (SELECT COUNT(*) FROM {$wpdb->prefix}flavor_espacios_comunes WHERE JSON_EXTRACT(metadata, '$.nodo_id') = n.id) as espacios_count,
                   (SELECT COUNT(*) FROM {$wpdb->prefix}flavor_socios_artistas WHERE JSON_EXTRACT(metadata, '$.nodo_id') = n.id) as artistas_count
            FROM $tabla n
            WHERE n.activo = 1
            ORDER BY n.nombre
        ") ?: [];

        return $nodos;
    }

    /**
     * Obtiene categorías culturales con contador
     */
    public function get_categorias_culturales() {
        global $wpdb;

        $categorias_config = $this->get_setting('tipos_evento', []);

        $resultados = [];
        foreach ($categorias_config as $slug => $nombre) {
            $count = $wpdb->get_var($wpdb->prepare("
                SELECT COUNT(*) FROM {$wpdb->prefix}flavor_eventos
                WHERE tipo = %s AND estado = 'publicado' AND fecha_inicio >= NOW()
            ", $slug));

            $resultados[] = [
                'slug' => $slug,
                'nombre' => $nombre,
                'count' => (int) $count,
            ];
        }

        return $resultados;
    }

    // =========================================================
    // Propuestas y Negociación
    // =========================================================

    /**
     * Crea una propuesta de evento de artista a espacio
     */
    public function crear_propuesta($datos) {
        global $wpdb;

        $artista_id = get_current_user_id();
        $espacio_id = absint($datos['espacio_id']);

        // Verificar que el artista tiene perfil
        require_once FLAVOR_CHAT_IA_PATH . 'includes/modules/socios/class-socios-artista-profile.php';
        $artista_manager = Flavor_Socios_Artista_Profile::get_instance();
        $artista = $artista_manager->get_artista_by_usuario($artista_id);

        if (!$artista) {
            return new WP_Error('sin_perfil_artista', __('Necesitas un perfil de artista para enviar propuestas.', 'flavor-chat-ia'));
        }

        // Crear evento en estado borrador con metadata de propuesta
        $datos_evento = [
            'titulo' => sanitize_text_field($datos['titulo']),
            'descripcion' => sanitize_textarea_field($datos['descripcion']),
            'tipo' => sanitize_key($datos['tipo'] ?? 'concierto'),
            'fecha_inicio' => sanitize_text_field($datos['fecha_propuesta']),
            'organizador_id' => $artista_id,
            'estado' => 'borrador',
            'metadata' => wp_json_encode([
                'es_propuesta' => true,
                'espacio_destino' => $espacio_id,
                'estado_propuesta' => 'pendiente',
                'condiciones_artista' => [
                    'cache_solicitado' => floatval($datos['cache'] ?? 0),
                    'acepta_semilla' => !empty($datos['acepta_semilla']),
                    'acepta_hours' => !empty($datos['acepta_hours']),
                    'necesidades_tecnicas' => sanitize_textarea_field($datos['necesidades_tecnicas'] ?? ''),
                ],
            ]),
        ];

        $tabla_eventos = $wpdb->prefix . 'flavor_eventos';
        $insertado = $wpdb->insert($tabla_eventos, $datos_evento);

        if (!$insertado) {
            return new WP_Error('error_crear', __('Error al crear la propuesta.', 'flavor-chat-ia'));
        }

        $propuesta_id = $wpdb->insert_id;

        // Notificar al espacio
        do_action('flavor_kulturaka_propuesta_creada', $propuesta_id, $espacio_id, $artista_id);

        return $propuesta_id;
    }

    /**
     * Responde a una propuesta (espacio)
     */
    public function responder_propuesta($propuesta_id, $respuesta, $datos = []) {
        global $wpdb;
        $tabla = $wpdb->prefix . 'flavor_eventos';

        $propuesta = $wpdb->get_row($wpdb->prepare("SELECT * FROM $tabla WHERE id = %d", $propuesta_id));

        if (!$propuesta) {
            return new WP_Error('no_existe', __('Propuesta no encontrada.', 'flavor-chat-ia'));
        }

        $metadata = json_decode($propuesta->metadata, true) ?: [];

        switch ($respuesta) {
            case 'aceptar':
                $metadata['estado_propuesta'] = 'aceptada';
                $metadata['respuesta_espacio'] = sanitize_textarea_field($datos['mensaje'] ?? '');

                // Confirmar el evento
                $wpdb->update($tabla, [
                    'estado' => 'publicado',
                    'espacio_id' => $metadata['espacio_destino'],
                    'metadata' => wp_json_encode($metadata),
                ], ['id' => $propuesta_id]);

                do_action('flavor_kulturaka_propuesta_aceptada', $propuesta_id);
                break;

            case 'negociar':
                $metadata['estado_propuesta'] = 'en_negociacion';
                $metadata['contrapropuesta'] = [
                    'fecha_alternativa' => sanitize_text_field($datos['fecha_alternativa'] ?? ''),
                    'condiciones' => sanitize_textarea_field($datos['condiciones'] ?? ''),
                    'mensaje' => sanitize_textarea_field($datos['mensaje'] ?? ''),
                ];

                $wpdb->update($tabla, [
                    'metadata' => wp_json_encode($metadata),
                ], ['id' => $propuesta_id]);

                do_action('flavor_kulturaka_propuesta_negociacion', $propuesta_id);
                break;

            case 'rechazar':
                $metadata['estado_propuesta'] = 'rechazada';
                $metadata['motivo_rechazo'] = sanitize_textarea_field($datos['motivo'] ?? '');

                $wpdb->update($tabla, [
                    'metadata' => wp_json_encode($metadata),
                ], ['id' => $propuesta_id]);

                do_action('flavor_kulturaka_propuesta_rechazada', $propuesta_id);
                break;
        }

        return true;
    }

    // =========================================================
    // Métricas e Impacto
    // =========================================================

    /**
     * Calcula el índice de cooperación de un espacio
     */
    private function calcular_indice_cooperacion_espacio($espacio_id) {
        global $wpdb;

        // Factores: variedad de artistas, eventos con economía solidaria, ratings positivos
        $total_eventos = $wpdb->get_var($wpdb->prepare("
            SELECT COUNT(*) FROM {$wpdb->prefix}flavor_eventos WHERE espacio_id = %d
        ", $espacio_id)) ?: 1;

        $artistas_unicos = $wpdb->get_var($wpdb->prepare("
            SELECT COUNT(DISTINCT organizador_id) FROM {$wpdb->prefix}flavor_eventos WHERE espacio_id = %d
        ", $espacio_id)) ?: 0;

        $eventos_solidarios = $wpdb->get_var($wpdb->prepare("
            SELECT COUNT(*) FROM {$wpdb->prefix}flavor_eventos
            WHERE espacio_id = %d AND (precio = 0 OR JSON_EXTRACT(metadata, '$.acepta_semilla') = true)
        ", $espacio_id)) ?: 0;

        // Calcular índice (0-100)
        $diversidad = min(($artistas_unicos / max($total_eventos, 1)) * 100, 50);
        $solidaridad = min(($eventos_solidarios / max($total_eventos, 1)) * 100, 50);

        return round($diversidad + $solidaridad, 1);
    }

    /**
     * Calcula impacto ambiental de un evento
     */
    public function calcular_impacto_evento($evento_id, $datos_asistencia) {
        global $wpdb;

        // Calcular CO2 evitado por eventos locales vs desplazamiento
        $evento = $wpdb->get_row($wpdb->prepare("
            SELECT e.*, ec.latitud, ec.longitud
            FROM {$wpdb->prefix}flavor_eventos e
            LEFT JOIN {$wpdb->prefix}flavor_espacios_comunes ec ON e.espacio_id = ec.id
            WHERE e.id = %d
        ", $evento_id));

        if (!$evento) return;

        // Simplificación: asumimos que los asistentes locales ahorran 10km de media
        $asistentes = $datos_asistencia['confirmados'] ?? $evento->inscritos_count;
        $km_ahorrados = $asistentes * 10;
        $co2_evitado = $km_ahorrados * $this->get_setting('co2_por_km', 0.12);

        $metadata = json_decode($evento->metadata, true) ?: [];
        $metadata['co2_evitado'] = $co2_evitado;
        $metadata['impacto_calculado_at'] = current_time('mysql');

        $wpdb->update($wpdb->prefix . 'flavor_eventos', [
            'metadata' => wp_json_encode($metadata),
        ], ['id' => $evento_id]);
    }

    // =========================================================
    // Agradecimientos (Micorriza Cultural)
    // =========================================================

    /**
     * Envía un agradecimiento
     */
    public function enviar_agradecimiento($datos) {
        global $wpdb;
        $tabla = $wpdb->prefix . 'flavor_kulturaka_agradecimientos';

        if (!Flavor_Chat_Helpers::tabla_existe($wpdb->prefix . 'flavor_kulturaka_agradecimientos')) {
            return new WP_Error('tabla_no_existe', __('El sistema de agradecimientos no está configurado.', 'flavor-chat-ia'));
        }

        $de_usuario_id = get_current_user_id();
        if (!$de_usuario_id) {
            return new WP_Error('sin_usuario', __('Debes iniciar sesión.', 'flavor-chat-ia'));
        }

        $datos_insertar = [
            'de_usuario_id' => $de_usuario_id,
            'para_usuario_id' => absint($datos['para_usuario_id']),
            'evento_id' => absint($datos['evento_id'] ?? 0) ?: null,
            'tipo' => in_array($datos['tipo'] ?? '', ['gracias', 'apoyo', 'colaboracion']) ? $datos['tipo'] : 'gracias',
            'mensaje' => sanitize_textarea_field($datos['mensaje'] ?? ''),
            'publico' => !empty($datos['publico']) ? 1 : 0,
        ];

        $insertado = $wpdb->insert($tabla, $datos_insertar);

        if ($insertado) {
            do_action('flavor_kulturaka_agradecimiento_enviado', $wpdb->insert_id, $datos_insertar);
        }

        return $insertado ? $wpdb->insert_id : new WP_Error('error', __('Error al enviar agradecimiento.', 'flavor-chat-ia'));
    }

    // =========================================================
    // Shortcodes
    // =========================================================

    /**
     * Registra shortcodes
     */
    public function register_shortcodes() {
        add_shortcode('kulturaka_dashboard', [$this, 'shortcode_dashboard']);
        add_shortcode('kulturaka_vista_espacio', [$this, 'shortcode_vista_espacio']);
        add_shortcode('kulturaka_vista_artista', [$this, 'shortcode_vista_artista']);
        add_shortcode('kulturaka_vista_comunidad', [$this, 'shortcode_vista_comunidad']);
        add_shortcode('kulturaka_mapa_nodos', [$this, 'shortcode_mapa_nodos']);
        add_shortcode('kulturaka_muro_agradecimientos', [$this, 'shortcode_muro']);
    }

    /**
     * Shortcode: Dashboard principal con 3 vistas
     */
    public function shortcode_dashboard($atributos) {
        $atributos = shortcode_atts([
            'vista_inicial' => 'comunidad',
        ], $atributos);

        $usuario_id = get_current_user_id();

        // Determinar qué vistas mostrar según el perfil del usuario
        $es_artista = $this->usuario_es_artista($usuario_id);
        $es_espacio = $this->usuario_es_espacio($usuario_id);

        $vista_inicial = $atributos['vista_inicial'];

        // Obtener datos de cada vista disponible
        $datos_comunidad = $this->get_vista_comunidad_data();
        $datos_artista = $es_artista ? $this->get_vista_artista_data($this->get_artista_id_usuario($usuario_id)) : null;
        $datos_espacio = $es_espacio ? $this->get_vista_espacio_data($this->get_espacio_id_usuario($usuario_id)) : null;

        ob_start();
        include dirname(__FILE__) . '/views/dashboard.php';
        return ob_get_clean();
    }

    /**
     * Verifica si el usuario tiene perfil de artista
     */
    private function usuario_es_artista($usuario_id) {
        if (!$usuario_id) return false;

        global $wpdb;
        return (bool) $wpdb->get_var($wpdb->prepare(
            "SELECT id FROM {$wpdb->prefix}flavor_socios_artistas WHERE usuario_id = %d AND activo = 1",
            $usuario_id
        ));
    }

    /**
     * Verifica si el usuario gestiona un espacio
     */
    private function usuario_es_espacio($usuario_id) {
        if (!$usuario_id) return false;

        global $wpdb;
        return (bool) $wpdb->get_var($wpdb->prepare(
            "SELECT id FROM {$wpdb->prefix}flavor_espacios_comunes WHERE responsable_id = %d",
            $usuario_id
        ));
    }

    /**
     * Obtiene el ID del artista del usuario
     */
    private function get_artista_id_usuario($usuario_id) {
        global $wpdb;
        return $wpdb->get_var($wpdb->prepare(
            "SELECT id FROM {$wpdb->prefix}flavor_socios_artistas WHERE usuario_id = %d",
            $usuario_id
        ));
    }

    /**
     * Obtiene el ID del espacio del usuario
     */
    private function get_espacio_id_usuario($usuario_id) {
        global $wpdb;
        return $wpdb->get_var($wpdb->prepare(
            "SELECT id FROM {$wpdb->prefix}flavor_espacios_comunes WHERE responsable_id = %d",
            $usuario_id
        ));
    }

    // =========================================================
    // Helpers
    // =========================================================

    private function get_rating_espacio($espacio_id) {
        // Por implementar: rating que dan los artistas a los espacios
        return null;
    }

    private function get_artistas_siguiendo($usuario_id) {
        global $wpdb;
        $tabla = $wpdb->prefix . 'flavor_artistas_seguidores';

        if (!Flavor_Chat_Helpers::tabla_existe($wpdb->prefix . 'flavor_artistas_seguidores')) {
            return [];
        }

        return $wpdb->get_results($wpdb->prepare("
            SELECT a.*
            FROM $tabla s
            JOIN {$wpdb->prefix}flavor_socios_artistas a ON s.artista_id = a.id
            WHERE s.seguidor_id = %d
            ORDER BY s.created_at DESC
            LIMIT 10
        ", $usuario_id)) ?: [];
    }

    private function get_calendario_espacio($espacio_id) {
        global $wpdb;

        // Próximos 30 días
        $eventos = $wpdb->get_results($wpdb->prepare("
            SELECT id, titulo, fecha_inicio, fecha_fin, estado
            FROM {$wpdb->prefix}flavor_eventos
            WHERE espacio_id = %d
            AND fecha_inicio BETWEEN NOW() AND DATE_ADD(NOW(), INTERVAL 30 DAY)
            ORDER BY fecha_inicio
        ", $espacio_id)) ?: [];

        $bloqueos = $wpdb->get_results($wpdb->prepare("
            SELECT id, motivo, fecha_inicio, fecha_fin, tipo
            FROM {$wpdb->prefix}flavor_espacios_bloqueos
            WHERE espacio_id = %d
            AND fecha_inicio BETWEEN NOW() AND DATE_ADD(NOW(), INTERVAL 30 DAY)
        ", $espacio_id)) ?: [];

        return [
            'eventos' => $eventos,
            'bloqueos' => $bloqueos,
        ];
    }

    private function get_historial_eventos_espacio($espacio_id, $limite = 5) {
        global $wpdb;

        return $wpdb->get_results($wpdb->prepare("
            SELECT e.*, a.nombre_artistico
            FROM {$wpdb->prefix}flavor_eventos e
            LEFT JOIN {$wpdb->prefix}flavor_socios_artistas a ON e.organizador_id = a.usuario_id
            WHERE e.espacio_id = %d
            AND e.fecha_fin < NOW()
            ORDER BY e.fecha_inicio DESC
            LIMIT %d
        ", $espacio_id, $limite)) ?: [];
    }

    private function get_eventos_otros_nodos($comunidad_id = null) {
        // Por implementar: eventos en nodos distintos al actual
        return [];
    }

    // =========================================================
    // Admin Pages
    // =========================================================

    /**
     * Registra páginas de administración
     */
    public function registrar_paginas_admin() {
        static $registered = false;
        if ($registered) {
            return;
        }
        $registered = true;


        add_menu_page(
            __('Kulturaka', 'flavor-chat-ia'),
            __('Kulturaka', 'flavor-chat-ia'),
            'manage_options',
            'flavor-kulturaka',
            [$this, 'render_pagina_admin'],
            'dashicons-heart',
            26
        );

        add_submenu_page(
            'flavor-kulturaka',
            __('Dashboard', 'flavor-chat-ia'),
            __('Dashboard', 'flavor-chat-ia'),
            'manage_options',
            'flavor-kulturaka',
            [$this, 'render_pagina_admin']
        );

        add_submenu_page(
            'flavor-kulturaka',
            __('Nodos de la Red', 'flavor-chat-ia'),
            __('Nodos', 'flavor-chat-ia'),
            'manage_options',
            'flavor-kulturaka-nodos',
            [$this, 'render_pagina_nodos']
        );

        add_submenu_page(
            'flavor-kulturaka',
            __('Propuestas', 'flavor-chat-ia'),
            __('Propuestas', 'flavor-chat-ia'),
            'manage_options',
            'flavor-kulturaka-propuestas',
            [$this, 'render_pagina_propuestas']
        );

        add_submenu_page(
            'flavor-kulturaka',
            __('Métricas', 'flavor-chat-ia'),
            __('Métricas', 'flavor-chat-ia'),
            'manage_options',
            'flavor-kulturaka-metricas',
            [$this, 'render_pagina_metricas']
        );
    }

    /**
     * Renderiza página principal de admin
     */
    public function render_pagina_admin() {
        $vista = isset($_GET['vista']) ? sanitize_text_field($_GET['vista']) : 'comunidad';
        include dirname(__FILE__) . '/views/dashboard.php';
    }

    /**
     * Renderiza página de nodos
     */
    public function render_pagina_nodos() {
        include dirname(__FILE__) . '/views/vista-red.php';
    }

    /**
     * Renderiza página de propuestas
     */
    public function render_pagina_propuestas() {
        global $wpdb;
        $tabla_propuestas = $wpdb->prefix . 'flavor_kulturaka_propuestas';

        $propuestas = $wpdb->get_results("
            SELECT p.*,
                   a.nombre_artistico,
                   n.nombre as espacio_nombre
            FROM $tabla_propuestas p
            LEFT JOIN {$wpdb->prefix}flavor_socios_artistas a ON p.artista_id = a.id
            LEFT JOIN {$wpdb->prefix}flavor_kulturaka_nodos n ON p.nodo_id = n.id
            ORDER BY p.created_at DESC
            LIMIT 50
        ");

        echo '<div class="wrap"><h1>' . esc_html__('Propuestas Kulturaka', 'flavor-chat-ia') . '</h1>';
        echo '<table class="wp-list-table widefat fixed striped">';
        echo '<thead><tr><th>Artista</th><th>Espacio</th><th>Título</th><th>Estado</th><th>Fecha</th></tr></thead><tbody>';

        if ($propuestas) {
            foreach ($propuestas as $prop) {
                echo '<tr>';
                echo '<td>' . esc_html($prop->nombre_artistico ?: 'N/A') . '</td>';
                echo '<td>' . esc_html($prop->espacio_nombre ?: 'N/A') . '</td>';
                echo '<td>' . esc_html($prop->titulo) . '</td>';
                echo '<td>' . esc_html(ucfirst($prop->estado)) . '</td>';
                echo '<td>' . esc_html(date_i18n('d/m/Y H:i', strtotime($prop->created_at))) . '</td>';
                echo '</tr>';
            }
        } else {
            echo '<tr><td colspan="5">No hay propuestas</td></tr>';
        }

        echo '</tbody></table></div>';
    }

    /**
     * Renderiza página de métricas
     */
    public function render_pagina_metricas() {
        global $wpdb;

        $tabla_metricas = $wpdb->prefix . 'flavor_kulturaka_metricas';
        $tabla_nodos = $wpdb->prefix . 'flavor_kulturaka_nodos';

        $stats = [
            'total_nodos' => $wpdb->get_var("SELECT COUNT(*) FROM $tabla_nodos WHERE estado = 'activo'") ?: 0,
            'total_eventos' => $wpdb->get_var("SELECT SUM(eventos_realizados) FROM $tabla_nodos") ?: 0,
            'total_artistas' => $wpdb->get_var("SELECT SUM(artistas_apoyados) FROM $tabla_nodos") ?: 0,
            'fondos_totales' => $wpdb->get_var("SELECT SUM(fondos_recaudados) FROM $tabla_nodos") ?: 0,
        ];

        echo '<div class="wrap">';
        echo '<h1>' . esc_html__('Métricas de la Red Kulturaka', 'flavor-chat-ia') . '</h1>';

        echo '<div style="display:grid;grid-template-columns:repeat(4,1fr);gap:20px;margin:20px 0;">';
        foreach ([
            'total_nodos' => ['Nodos activos', 'dashicons-networking'],
            'total_eventos' => ['Eventos realizados', 'dashicons-calendar-alt'],
            'total_artistas' => ['Artistas apoyados', 'dashicons-admin-users'],
            'fondos_totales' => ['Fondos recaudados', 'dashicons-money-alt'],
        ] as $key => $info) {
            $valor = $key === 'fondos_totales' ? number_format($stats[$key], 0, ',', '.') . '€' : number_format($stats[$key]);
            echo '<div style="background:#fff;padding:20px;border-radius:8px;box-shadow:0 2px 4px rgba(0,0,0,0.1);">';
            echo '<span class="dashicons ' . esc_attr($info[1]) . '" style="font-size:32px;color:#ec4899;"></span>';
            echo '<div style="font-size:28px;font-weight:700;margin:10px 0;">' . esc_html($valor) . '</div>';
            echo '<div style="color:#6b7280;">' . esc_html($info[0]) . '</div>';
            echo '</div>';
        }
        echo '</div>';

        echo '</div>';
    }

    // =========================================================
    // REST API
    // =========================================================

    /**
     * Registra rutas REST
     */
    public function register_rest_routes() {
        register_rest_route('flavor/v1', '/kulturaka/nodos', [
            'methods' => 'GET',
            'callback' => [$this, 'api_get_nodos'],
            'permission_callback' => '__return_true',
        ]);

        register_rest_route('flavor/v1', '/kulturaka/propuestas', [
            'methods' => 'POST',
            'callback' => [$this, 'api_crear_propuesta'],
            'permission_callback' => function() {
                return is_user_logged_in();
            },
        ]);

        register_rest_route('flavor/v1', '/kulturaka/agradecimientos', [
            'methods' => 'POST',
            'callback' => [$this, 'api_enviar_agradecimiento'],
            'permission_callback' => function() {
                return is_user_logged_in();
            },
        ]);
    }

    /**
     * API: Obtener nodos
     */
    public function api_get_nodos($request) {
        $nodos = $this->get_red_nodos();
        return rest_ensure_response($nodos);
    }

    /**
     * API: Crear propuesta
     */
    public function api_crear_propuesta($request) {
        $resultado = $this->crear_propuesta($request->get_params());

        if (is_wp_error($resultado)) {
            return $resultado;
        }

        return rest_ensure_response([
            'success' => true,
            'propuesta_id' => $resultado,
        ]);
    }

    /**
     * API: Enviar agradecimiento
     */
    public function api_enviar_agradecimiento($request) {
        $resultado = $this->enviar_agradecimiento($request->get_params());

        if (is_wp_error($resultado)) {
            return $resultado;
        }

        return rest_ensure_response([
            'success' => true,
            'agradecimiento_id' => $resultado,
        ]);
    }

    // =========================================================
    // Assets
    // =========================================================

    /**
     * Encola assets frontend
     */
    public function enqueue_frontend_assets() {
        if (!$this->should_load_assets()) {
            return;
        }

        wp_enqueue_style(
            'flavor-kulturaka',
            FLAVOR_CHAT_IA_URL . 'includes/modules/kulturaka/assets/kulturaka.css',
            [],
            FLAVOR_CHAT_IA_VERSION
        );

        wp_enqueue_script(
            'flavor-kulturaka',
            FLAVOR_CHAT_IA_URL . 'includes/modules/kulturaka/assets/kulturaka.js',
            ['jquery'],
            FLAVOR_CHAT_IA_VERSION,
            true
        );

        wp_localize_script('flavor-kulturaka', 'kulturaka', [
            'ajaxurl' => admin_url('admin-ajax.php'),
            'resturl' => rest_url('flavor/v1/kulturaka/'),
            'nonce' => wp_create_nonce('wp_rest'),
        ]);
    }

    /**
     * Determina si cargar assets
     */
    private function should_load_assets() {
        global $post;

        if (is_admin()) {
            return false;
        }

        if ($post && has_shortcode($post->post_content, 'kulturaka')) {
            return true;
        }

        return false;
    }
}

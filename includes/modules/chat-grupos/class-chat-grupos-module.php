<?php
/**
 * Módulo de Chat de Grupos para Chat IA
 *
 * @package FlavorChatIA
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Módulo de Chat de Grupos - Grupos de conversación temáticos
 */
class Flavor_Chat_Chat_Grupos_Module extends Flavor_Chat_Module_Base {

    use Flavor_Module_Admin_Pages_Trait;
    use Flavor_Module_Notifications_Trait;
    use Flavor_WhatsApp_Features;
    use Flavor_Encuestas_Features;

    /**
     * Constructor
     */
    public function __construct() {
        $this->id = 'chat_grupos';
        $this->name = 'Chat de Grupos'; // Translation loaded on init
        $this->description = 'Grupos de conversación temáticos para la comunidad con canales y temas organizados.'; // Translation loaded on init

        parent::__construct();
    }

    /**
     * {@inheritdoc}
     */
    public function can_activate() {
        global $wpdb;
        $tabla_grupos = $wpdb->prefix . 'flavor_chat_grupos';
        return Flavor_Chat_Helpers::tabla_existe($tabla_grupos);
    }

    /**
     * {@inheritdoc}
     */
    public function get_activation_error() {
        if (!$this->can_activate()) {
            return __('Las tablas de Chat de Grupos no están creadas. Se crearán automáticamente al activar.', 'flavor-chat-ia');
        }
        
    return '';
    }

/**
     * Verifica si el módulo está activo
     */
    public function is_active() {
        return $this->can_activate();
    }

    /**
     * {@inheritdoc}
     */
    protected function get_default_settings() {
        return [
            'disponible_app' => 'ambas',
            'max_miembros_por_grupo' => 500,
            'permite_crear_grupos' => true,
            'requiere_aprobacion_grupos' => false,
            'permite_grupos_privados' => true,
            'permite_archivos' => true,
            'max_archivo_mb' => 10,
            'permite_videollamadas' => false,
            'permite_encuestas' => true,
            'historial_mensajes_dias' => 365,
            'notificaciones_push' => true,
            'mensajes_por_pagina' => 50,
            'permite_reacciones' => true,
            'permite_hilos' => true,
        ];
    }

    /**
     * Define los tabs que este módulo inyecta en otros módulos
     *
     * Cuando chat-grupos está activo, puede mostrar un tab de "Chat"
     * en los dashboards de grupos de consumo, eventos, comunidades, etc.
     *
     * @return array Configuración de tabs por módulo destino
     */
    public function get_tab_integrations() {
        return [
            // Tab de chat para Grupos de Consumo
            'grupos_consumo' => [
                'id'       => 'chat-grupo',
                'label'    => __('Chat', 'flavor-chat-ia'),
                'icon'     => 'dashicons-format-status',
                'content'  => '[flavor_chat_grupo_integrado entidad="grupo_consumo" entidad_id="{entity_id}"]',
                'priority' => 95,
                'badge'    => function($contexto) {
                    return $this->contar_mensajes_no_leidos_entidad('grupo_consumo', $contexto['entity_id'], $contexto['user_id']);
                },
            ],

            // Tab de chat para Eventos
            'eventos' => [
                'id'       => 'chat-evento',
                'label'    => __('Chat', 'flavor-chat-ia'),
                'icon'     => 'dashicons-format-status',
                'content'  => '[flavor_chat_grupo_integrado entidad="evento" entidad_id="{entity_id}"]',
                'priority' => 95,
                'badge'    => function($contexto) {
                    return $this->contar_mensajes_no_leidos_entidad('evento', $contexto['entity_id'], $contexto['user_id']);
                },
            ],

            // Tab de chat para Comunidades
            'comunidades' => [
                'id'       => 'chat-comunidad',
                'label'    => __('Chat', 'flavor-chat-ia'),
                'icon'     => 'dashicons-format-status',
                'content'  => '[flavor_chat_grupo_integrado entidad="comunidad" entidad_id="{entity_id}"]',
                'priority' => 85,
                'badge'    => function($contexto) {
                    return $this->contar_mensajes_no_leidos_entidad('comunidad', $contexto['entity_id'], $contexto['user_id']);
                },
            ],

            'incidencias' => [
                'id'       => 'chat-incidencia',
                'label'    => __('Chat', 'flavor-chat-ia'),
                'icon'     => 'dashicons-format-status',
                'content'  => '[flavor_chat_grupo_integrado entidad="incidencia" entidad_id="{entity_id}"]',
                'priority' => 90,
                'badge'    => function($contexto) {
                    return $this->contar_mensajes_no_leidos_entidad('incidencia', $contexto['entity_id'], $contexto['user_id']);
                },
            ],

            'documentacion_legal' => [
                'id'       => 'chat-documento-legal',
                'label'    => __('Chat', 'flavor-chat-ia'),
                'icon'     => 'dashicons-format-status',
                'content'  => '[flavor_chat_grupo_integrado entidad="documento_legal" entidad_id="{entity_id}"]',
                'priority' => 90,
                'badge'    => function($contexto) {
                    return $this->contar_mensajes_no_leidos_entidad('documento_legal', $contexto['entity_id'], $contexto['user_id']);
                },
            ],

            'presupuestos_participativos' => [
                'id'       => 'chat-pp-proyecto',
                'label'    => __('Chat', 'flavor-chat-ia'),
                'icon'     => 'dashicons-format-status',
                'content'  => '[flavor_chat_grupo_integrado entidad="pp_proyecto" entidad_id="{entity_id}"]',
                'priority' => 90,
                'badge'    => function($contexto) {
                    return $this->contar_mensajes_no_leidos_entidad('pp_proyecto', $contexto['entity_id'], $contexto['user_id']);
                },
            ],

            'saberes_ancestrales' => [
                'id'       => 'chat-saber-ancestral',
                'label'    => __('Chat', 'flavor-chat-ia'),
                'icon'     => 'dashicons-format-status',
                'content'  => '[flavor_chat_grupo_integrado entidad="saber_ancestral" entidad_id="{entity_id}"]',
                'priority' => 90,
                'badge'    => function($contexto) {
                    return $this->contar_mensajes_no_leidos_entidad('saber_ancestral', $contexto['entity_id'], $contexto['user_id']);
                },
            ],

            'transparencia' => [
                'id'       => 'chat-documento-transparencia',
                'label'    => __('Chat', 'flavor-chat-ia'),
                'icon'     => 'dashicons-format-status',
                'content'  => '[flavor_chat_grupo_integrado entidad="documento_transparencia" entidad_id="{entity_id}"]',
                'priority' => 90,
                'badge'    => function($contexto) {
                    return $this->contar_mensajes_no_leidos_entidad('documento_transparencia', $contexto['entity_id'], $contexto['user_id']);
                },
            ],

            'avisos_municipales' => [
                'id'       => 'chat-aviso-municipal',
                'label'    => __('Chat', 'flavor-chat-ia'),
                'icon'     => 'dashicons-format-status',
                'content'  => '[flavor_chat_grupo_integrado entidad="aviso_municipal" entidad_id="{entity_id}"]',
                'priority' => 90,
                'badge'    => function($contexto) {
                    return $this->contar_mensajes_no_leidos_entidad('aviso_municipal', $contexto['entity_id'], $contexto['user_id']);
                },
            ],

            'economia_don' => [
                'id'       => 'chat-economia-don',
                'label'    => __('Chat', 'flavor-chat-ia'),
                'icon'     => 'dashicons-format-status',
                'content'  => '[flavor_chat_grupo_integrado entidad="economia_don" entidad_id="{entity_id}"]',
                'priority' => 90,
                'badge'    => function($contexto) {
                    return $this->contar_mensajes_no_leidos_entidad('economia_don', $contexto['entity_id'], $contexto['user_id']);
                },
            ],

            'advertising' => [
                'id'       => 'chat-advertising-ad',
                'label'    => __('Chat', 'flavor-chat-ia'),
                'icon'     => 'dashicons-format-status',
                'content'  => '[flavor_chat_grupo_integrado entidad="advertising_ad" entidad_id="{entity_id}"]',
                'priority' => 90,
                'badge'    => function($contexto) {
                    return $this->contar_mensajes_no_leidos_entidad('advertising_ad', $contexto['entity_id'], $contexto['user_id']);
                },
            ],

            'radio' => [
                'id'       => 'chat-radio-programa',
                'label'    => __('Chat', 'flavor-chat-ia'),
                'icon'     => 'dashicons-format-status',
                'content'  => '[flavor_chat_grupo_integrado entidad="radio_programa" entidad_id="{entity_id}"]',
                'priority' => 90,
                'badge'    => function($contexto) {
                    return $this->contar_mensajes_no_leidos_entidad('radio_programa', $contexto['entity_id'], $contexto['user_id']);
                },
            ],

            'energia_comunitaria' => [
                'id'       => 'chat-energia-comunidad',
                'label'    => __('Chat', 'flavor-chat-ia'),
                'icon'     => 'dashicons-format-status',
                'content'  => '[flavor_chat_grupo_integrado entidad="energia_comunidad" entidad_id="{entity_id}"]',
                'priority' => 90,
                'badge'    => function($contexto) {
                    return $this->contar_mensajes_no_leidos_entidad('energia_comunidad', $contexto['entity_id'], $contexto['user_id']);
                },
            ],

            // Tab de chat para Cursos
            'cursos' => [
                'id'       => 'chat-curso',
                'label'    => __('Chat del Curso', 'flavor-chat-ia'),
                'icon'     => 'dashicons-format-status',
                'content'  => '[flavor_chat_grupo_integrado entidad="curso" entidad_id="{entity_id}"]',
                'priority' => 95,
                'badge'    => function($contexto) {
                    return $this->contar_mensajes_no_leidos_entidad('curso', $contexto['entity_id'], $contexto['user_id']);
                },
            ],

            // Tab de chat para Talleres
            'talleres' => [
                'id'       => 'chat-taller',
                'label'    => __('Chat del Taller', 'flavor-chat-ia'),
                'icon'     => 'dashicons-format-status',
                'content'  => '[flavor_chat_grupo_integrado entidad="taller" entidad_id="{entity_id}"]',
                'priority' => 95,
                'badge'    => function($contexto) {
                    return $this->contar_mensajes_no_leidos_entidad('taller', $contexto['entity_id'], $contexto['user_id']);
                },
            ],

            // Tab de chat para Colectivos
            'colectivos' => [
                'id'       => 'chat-colectivo',
                'label'    => __('Chat', 'flavor-chat-ia'),
                'icon'     => 'dashicons-format-status',
                'content'  => '[flavor_chat_grupo_integrado entidad="colectivo" entidad_id="{entity_id}"]',
                'priority' => 95,
                'badge'    => function($contexto) {
                    return $this->contar_mensajes_no_leidos_entidad('colectivo', $contexto['entity_id'], $contexto['user_id']);
                },
            ],

            // Tab de chat para Círculos de Cuidados
            'circulos_cuidados' => [
                'id'       => 'chat-circulo',
                'label'    => __('Chat', 'flavor-chat-ia'),
                'icon'     => 'dashicons-format-status',
                'content'  => '[flavor_chat_grupo_integrado entidad="circulo" entidad_id="{entity_id}"]',
                'priority' => 95,
                'badge'    => function($contexto) {
                    return $this->contar_mensajes_no_leidos_entidad('circulo', $contexto['entity_id'], $contexto['user_id']);
                },
            ],

            // Tab de chat para Banco de Tiempo
            'banco_tiempo' => [
                'id'       => 'chat-servicio',
                'label'    => __('Chat', 'flavor-chat-ia'),
                'icon'     => 'dashicons-format-status',
                'content'  => '[flavor_chat_grupo_integrado entidad="servicio_bt" entidad_id="{entity_id}"]',
                'priority' => 95,
                'badge'    => function($contexto) {
                    return $this->contar_mensajes_no_leidos_entidad('servicio_bt', $contexto['entity_id'], $contexto['user_id']);
                },
            ],

            // Tab de chat para Trabajo Digno
            'trabajo_digno' => [
                'id'       => 'chat-oferta-trabajo',
                'label'    => __('Chat', 'flavor-chat-ia'),
                'icon'     => 'dashicons-format-status',
                'content'  => '[flavor_chat_grupo_integrado entidad="trabajo_digno_oferta" entidad_id="{entity_id}"]',
                'priority' => 95,
                'badge'    => function($contexto) {
                    return $this->contar_mensajes_no_leidos_entidad('trabajo_digno_oferta', $contexto['entity_id'], $contexto['user_id']);
                },
            ],

            // Tab de chat para Huertos Urbanos
            'huertos_urbanos' => [
                'id'       => 'chat-huerto',
                'label'    => __('Chat', 'flavor-chat-ia'),
                'icon'     => 'dashicons-format-status',
                'content'  => '[flavor_chat_grupo_integrado entidad="huerto" entidad_id="{entity_id}"]',
                'priority' => 95,
                'badge'    => function($contexto) {
                    return $this->contar_mensajes_no_leidos_entidad('huerto', $contexto['entity_id'], $contexto['user_id']);
                },
            ],
            'participacion' => [
                'id'       => 'chat-propuesta',
                'label'    => __('Chat', 'flavor-chat-ia'),
                'icon'     => 'dashicons-format-status',
                'content'  => '[flavor_chat_grupo_integrado entidad="participacion_propuesta" entidad_id="{entity_id}"]',
                'priority' => 95,
                'badge'    => function($contexto) {
                    return $this->contar_mensajes_no_leidos_entidad('participacion_propuesta', $contexto['entity_id'], $contexto['user_id']);
                },
            ],
            'economia_suficiencia' => [
                'id'       => 'chat-recurso-suficiencia',
                'label'    => __('Chat', 'flavor-chat-ia'),
                'icon'     => 'dashicons-format-status',
                'content'  => '[flavor_chat_grupo_integrado entidad="es_recurso" entidad_id="{entity_id}"]',
                'priority' => 95,
                'badge'    => function($contexto) {
                    return $this->contar_mensajes_no_leidos_entidad('es_recurso', $contexto['entity_id'], $contexto['user_id']);
                },
            ],
            'justicia_restaurativa' => [
                'id'       => 'chat-proceso-restaurativo',
                'label'    => __('Chat', 'flavor-chat-ia'),
                'icon'     => 'dashicons-format-status',
                'content'  => '[flavor_chat_grupo_integrado entidad="jr_proceso" entidad_id="{entity_id}"]',
                'priority' => 95,
                'badge'    => function($contexto) {
                    return $this->contar_mensajes_no_leidos_entidad('jr_proceso', $contexto['entity_id'], $contexto['user_id']);
                },
            ],
        ];
    }

    /**
     * Cuenta mensajes no leídos de un chat asociado a una entidad
     *
     * @param string $tipo_entidad Tipo de entidad
     * @param int    $entidad_id   ID de la entidad
     * @param int    $user_id      ID del usuario
     * @return int Número de mensajes no leídos
     */
    public function contar_mensajes_no_leidos_entidad($tipo_entidad, $entidad_id, $user_id) {
        global $wpdb;

        if (!$entidad_id || !$user_id) {
            return 0;
        }

        $tabla_grupos = $wpdb->prefix . 'flavor_chat_grupos';
        $tabla_mensajes = $wpdb->prefix . 'flavor_chat_grupos_mensajes';
        $tabla_leidos = $wpdb->prefix . 'flavor_chat_grupos_leidos';

        // Verificar si las tablas existen
        if (!Flavor_Chat_Helpers::tabla_existe($tabla_grupos)) {
            return 0;
        }

        // Obtener el grupo asociado a la entidad
        $grupo_id = $wpdb->get_var($wpdb->prepare(
            "SELECT id FROM {$tabla_grupos}
             WHERE entidad_tipo = %s AND entidad_id = %d",
            $tipo_entidad,
            $entidad_id
        ));

        if (!$grupo_id) {
            return 0;
        }

        // Contar mensajes no leídos
        $ultimo_leido = $wpdb->get_var($wpdb->prepare(
            "SELECT ultimo_mensaje_id FROM {$tabla_leidos}
             WHERE grupo_id = %d AND usuario_id = %d",
            $grupo_id,
            $user_id
        ));

        if (!$ultimo_leido) {
            $ultimo_leido = 0;
        }

        $no_leidos = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM {$tabla_mensajes}
             WHERE grupo_id = %d AND id > %d AND usuario_id != %d",
            $grupo_id,
            $ultimo_leido,
            $user_id
        ));

        return intval($no_leidos);
    }

    /**
     * {@inheritdoc}
     */
    public function init() {
        add_action('init', [$this, 'maybe_create_pages']);
        add_action('init', [$this, 'maybe_create_tables']);
        add_action('init', [$this, 'maybe_migrate_tables']);
        add_action('init', [$this, 'register_shortcodes']);
        add_action('rest_api_init', [$this, 'register_rest_routes']);
        add_action('wp_ajax_flavor_chat_grupos_send', [$this, 'ajax_enviar_mensaje']);
        add_action('wp_ajax_flavor_chat_grupos_messages', [$this, 'ajax_obtener_mensajes']);
        add_action('wp_ajax_flavor_chat_grupos_mark_read', [$this, 'ajax_marcar_leido']);
        add_action('wp_ajax_flavor_chat_grupos_typing', [$this, 'ajax_typing']);
        add_action('wp_ajax_flavor_chat_grupos_react', [$this, 'ajax_reaccionar']);
        add_action('wp_ajax_flavor_chat_grupos_search', [$this, 'ajax_buscar_mensajes']);
        add_action('wp_ajax_flavor_chat_grupos_upload', [$this, 'ajax_subir_archivo']);
        add_action('wp_ajax_flavor_chat_grupos_create', [$this, 'ajax_crear_grupo']);
        add_action('wp_ajax_flavor_chat_grupos_join', [$this, 'ajax_unirse_grupo']);
        add_action('wp_ajax_flavor_chat_grupos_leave', [$this, 'ajax_salir_grupo']);
        add_action('wp_ajax_flavor_chat_grupos_invite', [$this, 'ajax_invitar']);
        add_action('wp_ajax_flavor_chat_grupos_kick', [$this, 'ajax_expulsar']);
        add_action('wp_ajax_flavor_chat_grupos_role', [$this, 'ajax_cambiar_rol']);
        add_action('wp_ajax_flavor_chat_grupos_settings', [$this, 'ajax_actualizar_config']);
        add_action('wp_ajax_flavor_chat_grupos_poll_create', [$this, 'ajax_crear_encuesta']);
        add_action('wp_ajax_flavor_chat_grupos_poll_vote', [$this, 'ajax_votar_encuesta']);
        add_action('wp_ajax_flavor_chat_grupos_delete_msg', [$this, 'ajax_eliminar_mensaje']);
        add_action('wp_ajax_flavor_chat_grupos_edit_msg', [$this, 'ajax_editar_mensaje']);
        add_action('wp_ajax_flavor_chat_grupos_pin', [$this, 'ajax_fijar_mensaje']);

        // Dashboard integration
        add_filter('flavor_user_dashboard_tabs', [$this, 'add_dashboard_tab']);

        // Enqueue assets
        add_action('wp_enqueue_scripts', [$this, 'enqueue_assets']);

        // Panel de administración unificado
        $this->registrar_en_panel_unificado();
        // Cargar Dashboard Tab
        $this->inicializar_dashboard_tab();

        // Inicializar funcionalidades WhatsApp (doble check, link preview, mensajes temporales)
        $this->init_whatsapp_features();

        // Integrar funcionalidades de encuestas centralizadas
        $this->init_encuestas_features('chat_grupo');
    }

    /**
     * Obtiene el nombre de la tabla de mensajes
     * Requerido por Flavor_WhatsApp_Features trait
     *
     * @return string
     */
    protected function get_mensajes_table() {
        global $wpdb;
        return $wpdb->prefix . 'flavor_chat_grupos_mensajes';
    }

    /**
     * Crea las tablas si no existen
     */
    public function maybe_create_tables() {
        global $wpdb;
        $tabla_grupos = $wpdb->prefix . 'flavor_chat_grupos';

        if (!Flavor_Chat_Helpers::tabla_existe($tabla_grupos)) {
            $this->create_tables();
        }
    }

    /**
     * Verifica y aplica migraciones de base de datos
     */
    public function maybe_migrate_tables() {
        $version_actual = get_option('flavor_chat_grupos_db_version', '1.0.0');

        if (version_compare($version_actual, '1.1.0', '<')) {
            $this->migrate_to_1_1_0();
            update_option('flavor_chat_grupos_db_version', '1.1.0');
        }

        if (version_compare($version_actual, '1.2.0', '<')) {
            $this->migrate_to_1_2_0();
            update_option('flavor_chat_grupos_db_version', '1.2.0');
        }

        if (version_compare($version_actual, '1.3.0', '<')) {
            $this->migrate_to_1_3_0();
            update_option('flavor_chat_grupos_db_version', '1.3.0');
        }
    }

    /**
     * Migración a versión 1.2.0 - Añade columna comunidad_id para integración
     */
    private function migrate_to_1_2_0() {
        global $wpdb;
        $tabla_grupos = $wpdb->prefix . 'flavor_chat_grupos';

        if (!Flavor_Chat_Helpers::tabla_existe($tabla_grupos)) {
            return;
        }

        // Verificar y añadir columna comunidad_id
        $comunidad_id_existe = $wpdb->get_results(
            $wpdb->prepare("SHOW COLUMNS FROM $tabla_grupos LIKE %s", 'comunidad_id')
        );

        if (empty($comunidad_id_existe)) {
            $wpdb->query("ALTER TABLE $tabla_grupos ADD COLUMN comunidad_id bigint(20) unsigned DEFAULT NULL AFTER creador_id");
            $wpdb->query("ALTER TABLE $tabla_grupos ADD INDEX idx_comunidad_id (comunidad_id)");
        }
    }

    /**
     * Migración a versión 1.3.0 - Añade campos entidad_tipo y entidad_id para integración universal
     */
    private function migrate_to_1_3_0() {
        global $wpdb;
        $tabla_grupos = $wpdb->prefix . 'flavor_chat_grupos';

        if (!Flavor_Chat_Helpers::tabla_existe($tabla_grupos)) {
            return;
        }

        // Verificar y añadir columna entidad_tipo
        $entidad_tipo_existe = $wpdb->get_results(
            $wpdb->prepare("SHOW COLUMNS FROM $tabla_grupos LIKE %s", 'entidad_tipo')
        );

        if (empty($entidad_tipo_existe)) {
            $wpdb->query("ALTER TABLE $tabla_grupos ADD COLUMN entidad_tipo varchar(50) DEFAULT NULL AFTER comunidad_id");
            $wpdb->query("ALTER TABLE $tabla_grupos ADD COLUMN entidad_id bigint(20) unsigned DEFAULT NULL AFTER entidad_tipo");
            $wpdb->query("ALTER TABLE $tabla_grupos ADD INDEX idx_entidad (entidad_tipo, entidad_id)");
        }
    }

    /**
     * Migración a versión 1.1.0 - Añade columnas slug y color
     */
    private function migrate_to_1_1_0() {
        global $wpdb;
        $tabla_grupos = $wpdb->prefix . 'flavor_chat_grupos';

        if (!Flavor_Chat_Helpers::tabla_existe($tabla_grupos)) {
            return;
        }

        // Verificar y añadir columna slug
        $slug_existe = $wpdb->get_results(
            $wpdb->prepare("SHOW COLUMNS FROM $tabla_grupos LIKE %s", 'slug')
        );
        if (empty($slug_existe)) {
            $wpdb->query("ALTER TABLE $tabla_grupos ADD COLUMN slug varchar(255) NOT NULL DEFAULT '' AFTER nombre");
            $wpdb->query("ALTER TABLE $tabla_grupos ADD UNIQUE KEY slug (slug)");

            // Generar slugs para grupos existentes
            $grupos = $wpdb->get_results("SELECT id, nombre FROM $tabla_grupos WHERE slug = '' OR slug IS NULL");
            foreach ($grupos as $grupo) {
                $slug = sanitize_title($grupo->nombre);
                $slug_base = $slug;
                $contador = 2;
                while ($wpdb->get_var($wpdb->prepare("SELECT id FROM $tabla_grupos WHERE slug = %s AND id != %d", $slug, $grupo->id))) {
                    $slug = $slug_base . '-' . $contador++;
                }
                $wpdb->update($tabla_grupos, ['slug' => $slug], ['id' => $grupo->id]);
            }
        }

        // Verificar y añadir columna color
        $color_existe = $wpdb->get_results(
            $wpdb->prepare("SHOW COLUMNS FROM $tabla_grupos LIKE %s", 'color')
        );
        if (empty($color_existe)) {
            $wpdb->query("ALTER TABLE $tabla_grupos ADD COLUMN color varchar(7) DEFAULT '#2271b1' AFTER imagen_url");
        }
    }

    /**
     * Crea las tablas necesarias
     */
    private function create_tables() {
        global $wpdb;
        $charset_collate = $wpdb->get_charset_collate();

        $tabla_grupos = $wpdb->prefix . 'flavor_chat_grupos';
        $tabla_miembros = $wpdb->prefix . 'flavor_chat_grupos_miembros';
        $tabla_mensajes = $wpdb->prefix . 'flavor_chat_grupos_mensajes';
        $tabla_invitaciones = $wpdb->prefix . 'flavor_chat_grupos_invitaciones';
        $tabla_reacciones = $wpdb->prefix . 'flavor_chat_grupos_reacciones';
        $tabla_encuestas = $wpdb->prefix . 'flavor_chat_grupos_encuestas';
        $tabla_votos = $wpdb->prefix . 'flavor_chat_grupos_votos';
        $tabla_fijados = $wpdb->prefix . 'flavor_chat_grupos_fijados';

        $sql_grupos = "CREATE TABLE IF NOT EXISTS $tabla_grupos (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            nombre varchar(255) NOT NULL,
            slug varchar(255) NOT NULL,
            descripcion text DEFAULT NULL,
            imagen_url varchar(500) DEFAULT NULL,
            color varchar(7) DEFAULT '#2271b1',
            creador_id bigint(20) unsigned NOT NULL,
            tipo enum('publico','privado','secreto') DEFAULT 'publico',
            categoria varchar(100) DEFAULT NULL,
            max_miembros int(11) DEFAULT 500,
            miembros_count int(11) DEFAULT 0,
            mensajes_count int(11) DEFAULT 0,
            permite_archivos tinyint(1) DEFAULT 1,
            permite_encuestas tinyint(1) DEFAULT 1,
            solo_admins_publican tinyint(1) DEFAULT 0,
            estado enum('activo','archivado','bloqueado') DEFAULT 'activo',
            fecha_creacion datetime DEFAULT CURRENT_TIMESTAMP,
            fecha_actualizacion datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            ultimo_mensaje_id bigint(20) DEFAULT NULL,
            PRIMARY KEY (id),
            UNIQUE KEY slug (slug),
            KEY creador_id (creador_id),
            KEY tipo (tipo),
            KEY categoria (categoria),
            KEY estado (estado)
        ) $charset_collate;";

        $sql_miembros = "CREATE TABLE IF NOT EXISTS $tabla_miembros (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            grupo_id bigint(20) unsigned NOT NULL,
            usuario_id bigint(20) unsigned NOT NULL,
            rol enum('miembro','moderador','admin') DEFAULT 'miembro',
            notificaciones enum('todas','menciones','ninguna') DEFAULT 'todas',
            silenciado_hasta datetime DEFAULT NULL,
            ultimo_mensaje_leido bigint(20) DEFAULT 0,
            escribiendo tinyint(1) DEFAULT 0,
            escribiendo_timestamp datetime DEFAULT NULL,
            fecha_ingreso datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            UNIQUE KEY grupo_usuario (grupo_id, usuario_id),
            KEY usuario_id (usuario_id),
            KEY rol (rol)
        ) $charset_collate;";

        $sql_mensajes = "CREATE TABLE IF NOT EXISTS $tabla_mensajes (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            grupo_id bigint(20) unsigned NOT NULL,
            usuario_id bigint(20) unsigned NOT NULL,
            mensaje text NOT NULL,
            mensaje_html text DEFAULT NULL,
            tipo enum('texto','imagen','archivo','sistema','encuesta','hilo') DEFAULT 'texto',
            adjuntos text DEFAULT NULL COMMENT 'JSON',
            responde_a bigint(20) unsigned DEFAULT NULL,
            hilo_padre bigint(20) unsigned DEFAULT NULL,
            menciones text DEFAULT NULL COMMENT 'JSON de user_ids',
            reacciones_count int(11) DEFAULT 0,
            respuestas_count int(11) DEFAULT 0,
            editado tinyint(1) DEFAULT 0,
            eliminado tinyint(1) DEFAULT 0,
            fecha_creacion datetime DEFAULT CURRENT_TIMESTAMP,
            fecha_edicion datetime DEFAULT NULL,
            PRIMARY KEY (id),
            KEY grupo_id (grupo_id),
            KEY usuario_id (usuario_id),
            KEY fecha_creacion (fecha_creacion),
            KEY responde_a (responde_a),
            KEY hilo_padre (hilo_padre),
            FULLTEXT KEY mensaje (mensaje)
        ) $charset_collate;";

        $sql_invitaciones = "CREATE TABLE IF NOT EXISTS $tabla_invitaciones (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            grupo_id bigint(20) unsigned NOT NULL,
            invitado_id bigint(20) unsigned DEFAULT NULL,
            invitado_email varchar(255) DEFAULT NULL,
            invitador_id bigint(20) unsigned NOT NULL,
            codigo varchar(64) DEFAULT NULL,
            estado enum('pendiente','aceptada','rechazada','expirada') DEFAULT 'pendiente',
            fecha_creacion datetime DEFAULT CURRENT_TIMESTAMP,
            fecha_expiracion datetime DEFAULT NULL,
            PRIMARY KEY (id),
            UNIQUE KEY codigo (codigo),
            KEY grupo_id (grupo_id),
            KEY invitado_id (invitado_id)
        ) $charset_collate;";

        $sql_reacciones = "CREATE TABLE IF NOT EXISTS $tabla_reacciones (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            mensaje_id bigint(20) unsigned NOT NULL,
            usuario_id bigint(20) unsigned NOT NULL,
            emoji varchar(32) NOT NULL,
            fecha_creacion datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            UNIQUE KEY mensaje_usuario_emoji (mensaje_id, usuario_id, emoji),
            KEY mensaje_id (mensaje_id)
        ) $charset_collate;";

        $sql_encuestas = "CREATE TABLE IF NOT EXISTS $tabla_encuestas (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            mensaje_id bigint(20) unsigned NOT NULL,
            grupo_id bigint(20) unsigned NOT NULL,
            pregunta varchar(500) NOT NULL,
            opciones text NOT NULL COMMENT 'JSON',
            multiple tinyint(1) DEFAULT 0,
            anonima tinyint(1) DEFAULT 0,
            fecha_cierre datetime DEFAULT NULL,
            cerrada tinyint(1) DEFAULT 0,
            votos_totales int(11) DEFAULT 0,
            fecha_creacion datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY mensaje_id (mensaje_id),
            KEY grupo_id (grupo_id)
        ) $charset_collate;";

        $sql_votos = "CREATE TABLE IF NOT EXISTS $tabla_votos (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            encuesta_id bigint(20) unsigned NOT NULL,
            usuario_id bigint(20) unsigned NOT NULL,
            opcion_index int(11) NOT NULL,
            fecha_creacion datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY encuesta_id (encuesta_id),
            UNIQUE KEY encuesta_usuario_opcion (encuesta_id, usuario_id, opcion_index)
        ) $charset_collate;";

        $sql_fijados = "CREATE TABLE IF NOT EXISTS $tabla_fijados (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            grupo_id bigint(20) unsigned NOT NULL,
            mensaje_id bigint(20) unsigned NOT NULL,
            fijado_por bigint(20) unsigned NOT NULL,
            fecha_creacion datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            UNIQUE KEY grupo_mensaje (grupo_id, mensaje_id)
        ) $charset_collate;";

        require_once ABSPATH . 'wp-admin/includes/upgrade.php';
        dbDelta($sql_grupos);
        dbDelta($sql_miembros);
        dbDelta($sql_mensajes);
        dbDelta($sql_invitaciones);
        dbDelta($sql_reacciones);
        dbDelta($sql_encuestas);
        dbDelta($sql_votos);
        dbDelta($sql_fijados);
    }

    /**
     * Registrar shortcodes
     */
    public function register_shortcodes() {
        add_shortcode('flavor_chat_grupos', [$this, 'shortcode_chat_grupos']);
        add_shortcode('flavor_chat_grupo', [$this, 'shortcode_chat_grupo']);
        add_shortcode('flavor_grupos_lista', [$this, 'shortcode_grupos_lista']);
        add_shortcode('flavor_grupos_explorar', [$this, 'shortcode_grupos_explorar']);
        add_shortcode('flavor_grupos_crear', [$this, 'shortcode_crear_grupo']);
        add_shortcode('chat_grupos_crear', [$this, 'shortcode_chat_grupos_crear_alias']);
        add_shortcode('chat_grupos_mis_grupos', [$this, 'shortcode_chat_grupos_mis_grupos_alias']);
        add_shortcode('chat_grupos_mensajes', [$this, 'shortcode_chat_grupos_mensajes_alias']);
        add_shortcode('chat_grupos_sin_leer', [$this, 'shortcode_sin_leer']);
        add_shortcode('chat_mensajes_sin_leer', [$this, 'shortcode_mensajes_sin_leer']);
        // Shortcode de integración para tabs de otros módulos
        add_shortcode('flavor_chat_grupo_integrado', [$this, 'shortcode_chat_integrado']);
        // Shortcode para mostrar grupos activos (con actividad reciente)
        add_shortcode('chat_grupos_activos', [$this, 'shortcode_grupos_activos']);
    }

    /**
     * Enqueue assets
     */
    public function enqueue_assets() {
        if (!$this->should_load_assets()) {
            return;
        }

        wp_enqueue_style(
            'flavor-chat-grupos',
            FLAVOR_CHAT_IA_URL . 'includes/modules/chat-grupos/assets/css/chat-grupos.css',
            [],
            FLAVOR_CHAT_IA_VERSION
        );

        wp_enqueue_script(
            'flavor-chat-grupos',
            FLAVOR_CHAT_IA_URL . 'includes/modules/chat-grupos/assets/js/chat-grupos.js',
            ['jquery'],
            FLAVOR_CHAT_IA_VERSION,
            true
        );

        wp_localize_script('flavor-chat-grupos', 'flavorChatGrupos', [
            'ajaxurl' => admin_url('admin-ajax.php'),
            'resturl' => rest_url('flavor/v1/chat-grupos/'),
            'nonce' => wp_create_nonce('flavor_chat_grupos_nonce'),
            'user_id' => get_current_user_id(),
            'user_name' => wp_get_current_user()->display_name,
            'user_avatar' => get_avatar_url(get_current_user_id(), ['size' => 48]),
            'polling_interval' => 3000,
            'typing_timeout' => 3000,
            'strings' => [
                'escribiendo' => __('escribiendo...', 'flavor-chat-ia'),
                'tu' => __('Tú', 'flavor-chat-ia'),
                'ahora' => __('ahora', 'flavor-chat-ia'),
                'ayer' => __('ayer', 'flavor-chat-ia'),
                'mensaje_eliminado' => __('Mensaje eliminado', 'flavor-chat-ia'),
                'mensaje_editado' => __('editado', 'flavor-chat-ia'),
                'sin_mensajes' => __('No hay mensajes aún. ¡Sé el primero en escribir!', 'flavor-chat-ia'),
                'cargando' => __('Cargando...', 'flavor-chat-ia'),
                'error' => __('Error al procesar la solicitud', 'flavor-chat-ia'),
                // Strings para modal de crear grupo
                'crear_grupo' => __('Crear nuevo grupo', 'flavor-chat-ia'),
                'nombre' => __('Nombre del grupo', 'flavor-chat-ia'),
                'nombre_placeholder' => __('Ej: Vecinos del barrio', 'flavor-chat-ia'),
                'descripcion' => __('Descripción', 'flavor-chat-ia'),
                'descripcion_placeholder' => __('Describe el propósito del grupo...', 'flavor-chat-ia'),
                'tipo' => __('Tipo de grupo', 'flavor-chat-ia'),
                'tipo_abierto' => __('Abierto', 'flavor-chat-ia'),
                'tipo_cerrado' => __('Cerrado', 'flavor-chat-ia'),
                'tipo_privado' => __('Privado', 'flavor-chat-ia'),
                'color' => __('Color', 'flavor-chat-ia'),
                'categoria' => __('Categoría', 'flavor-chat-ia'),
                'seleccionar' => __('Seleccionar...', 'flavor-chat-ia'),
                'cat_vecinos' => __('Vecinos', 'flavor-chat-ia'),
                'cat_deportes' => __('Deportes', 'flavor-chat-ia'),
                'cat_cultura' => __('Cultura', 'flavor-chat-ia'),
                'cat_educacion' => __('Educación', 'flavor-chat-ia'),
                'cat_trabajo' => __('Trabajo', 'flavor-chat-ia'),
                'cat_ocio' => __('Ocio', 'flavor-chat-ia'),
                'cat_otros' => __('Otros', 'flavor-chat-ia'),
                'cancelar' => __('Cancelar', 'flavor-chat-ia'),
                'crear' => __('Crear grupo', 'flavor-chat-ia'),
                'creando' => __('Creando...', 'flavor-chat-ia'),
                'grupo_creado' => __('Grupo creado correctamente', 'flavor-chat-ia'),
                'error_crear' => __('Error al crear el grupo', 'flavor-chat-ia'),
                'confirmar' => __('Confirmar', 'flavor-chat-ia'),
                'confirmar_eliminar' => __('¿Eliminar este mensaje?', 'flavor-chat-ia'),
            ],
        ]);
    }

    /**
     * Verifica si debe cargar assets
     */
    private function should_load_assets() {
        global $post;

        // Cargar en páginas dinámicas del portal (mi-portal/chat-grupos/*)
        $uri = isset($_SERVER['REQUEST_URI']) ? $_SERVER['REQUEST_URI'] : '';
        if (strpos($uri, '/chat-grupos') !== false || strpos($uri, '/grupos') !== false) {
            return true;
        }

        // Cargar si hay parámetro de grupo en la URL
        if (isset($_GET['grupo_id']) || isset($_GET['tab']) && $_GET['tab'] === 'chat-grupos') {
            return true;
        }

        // También cargar si estamos en un contexto de tab integrado
        if (did_action('flavor_rendering_tab_integrado')) {
            return true;
        }

        // Verificar shortcodes en el contenido del post
        if ($post) {
            $shortcodes = ['flavor_chat_grupos', 'flavor_chat_grupo', 'flavor_grupos_lista', 'flavor_grupos_explorar', 'flavor_grupos_crear', 'flavor_chat_grupo_integrado', 'chat_grupos_activos', 'chat_grupos_crear'];
            foreach ($shortcodes as $shortcode) {
                if (has_shortcode($post->post_content, $shortcode)) {
                    return true;
                }
            }
        }

        return false;
    }

    /**
     * {@inheritdoc}
     */
    public function get_actions() {
        return [
            'mis_grupos' => [
                'description' => 'Listar mis grupos de chat',
                'params' => [],
            ],
            'grupos_publicos' => [
                'description' => 'Buscar grupos públicos',
                'params' => ['categoria', 'busqueda', 'pagina'],
            ],
            'crear_grupo' => [
                'description' => 'Crear nuevo grupo',
                'params' => ['nombre', 'descripcion', 'tipo', 'categoria', 'color'],
            ],
            'editar_grupo' => [
                'description' => 'Editar grupo existente',
                'params' => ['grupo_id', 'nombre', 'descripcion', 'tipo'],
            ],
            'eliminar_grupo' => [
                'description' => 'Eliminar/archivar grupo',
                'params' => ['grupo_id'],
            ],
            'unirse_grupo' => [
                'description' => 'Unirse a un grupo',
                'params' => ['grupo_id', 'codigo_invitacion'],
            ],
            'salir_grupo' => [
                'description' => 'Salir de un grupo',
                'params' => ['grupo_id'],
            ],
            'mensajes' => [
                'description' => 'Ver mensajes de un grupo',
                'params' => ['grupo_id', 'desde', 'limite', 'antes_de'],
            ],
            'enviar_mensaje' => [
                'description' => 'Enviar mensaje al grupo',
                'params' => ['grupo_id', 'mensaje', 'responde_a', 'adjuntos'],
            ],
            'editar_mensaje' => [
                'description' => 'Editar mensaje propio',
                'params' => ['mensaje_id', 'mensaje'],
            ],
            'eliminar_mensaje' => [
                'description' => 'Eliminar mensaje',
                'params' => ['mensaje_id'],
            ],
            'info_grupo' => [
                'description' => 'Ver información del grupo',
                'params' => ['grupo_id'],
            ],
            'miembros_grupo' => [
                'description' => 'Ver miembros del grupo',
                'params' => ['grupo_id'],
            ],
            'invitar_miembro' => [
                'description' => 'Invitar usuario al grupo',
                'params' => ['grupo_id', 'usuario_id', 'email'],
            ],
            'expulsar_miembro' => [
                'description' => 'Expulsar miembro del grupo',
                'params' => ['grupo_id', 'usuario_id'],
            ],
            'cambiar_rol' => [
                'description' => 'Cambiar rol de miembro',
                'params' => ['grupo_id', 'usuario_id', 'rol'],
            ],
            'silenciar_grupo' => [
                'description' => 'Silenciar notificaciones',
                'params' => ['grupo_id', 'duracion_horas'],
            ],
            'buscar_mensajes' => [
                'description' => 'Buscar en mensajes del grupo',
                'params' => ['grupo_id', 'query'],
            ],
            'crear_encuesta' => [
                'description' => 'Crear encuesta en grupo',
                'params' => ['grupo_id', 'pregunta', 'opciones', 'multiple'],
            ],
            'votar_encuesta' => [
                'description' => 'Votar en encuesta',
                'params' => ['encuesta_id', 'opcion'],
            ],
            'reaccionar' => [
                'description' => 'Añadir reacción a mensaje',
                'params' => ['mensaje_id', 'emoji'],
            ],
            'fijar_mensaje' => [
                'description' => 'Fijar/desfijar mensaje',
                'params' => ['mensaje_id'],
            ],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function execute_action($action_name, $params) {
        $aliases = [
            'listar' => 'grupos_publicos',
            'listado' => 'grupos_publicos',
            'explorar' => 'grupos_publicos',
            'crear' => 'crear_grupo',
            'mis_items' => 'mis_grupos',
            'mis-grupos' => 'mis_grupos',
            'miembros' => 'miembros_grupo',
            'detalle' => 'info_grupo',
            'ver' => 'info_grupo',
            'mensajes' => 'mensajes',
            'buscar' => 'buscar_mensajes',
            'unirse' => 'unirse_grupo',
            'salir' => 'salir_grupo',
        ];

        $action_name = $aliases[$action_name] ?? $action_name;
        $metodo_accion = 'action_' . $action_name;

        if (method_exists($this, $metodo_accion)) {
            return $this->$metodo_accion($params);
        }

        return [
            'success' => false,
            'error' => __('La vista solicitada no está disponible en Chat Grupos.', 'flavor-chat-ia'),
        ];
    }

    /**
     * Wrapper del renderer: crear grupo.
     *
     * @param array $atts
     * @return string
     */
    public function shortcode_chat_grupos_crear_alias($atts) {
        return $this->shortcode_crear_grupo($atts);
    }

    /**
     * Wrapper del renderer: mis grupos.
     *
     * @param array $atts
     * @return string
     */
    public function shortcode_chat_grupos_mis_grupos_alias($atts) {
        return $this->shortcode_chat_grupos($atts);
    }

    /**
     * Wrapper del renderer: mensajes de grupos.
     *
     * @param array $atts
     * @return string
     */
    public function shortcode_chat_grupos_mensajes_alias($atts) {
        return $this->shortcode_chat_grupos($atts);
    }

    /**
     * Acción: Mis grupos
     */
    private function action_mis_grupos($params) {
        $usuario_id = !empty($params['usuario_id']) ? absint($params['usuario_id']) : get_current_user_id();
        if (!$usuario_id) {
            return ['success' => false, 'error' => __('Debes iniciar sesión.', 'flavor-chat-ia')];
        }

        global $wpdb;
        $tabla_grupos = $wpdb->prefix . 'flavor_chat_grupos';
        $tabla_miembros = $wpdb->prefix . 'flavor_chat_grupos_miembros';
        $tabla_mensajes = $wpdb->prefix . 'flavor_chat_grupos_mensajes';

        $grupos = $wpdb->get_results($wpdb->prepare(
            "SELECT g.*, m.rol, m.notificaciones, m.ultimo_mensaje_leido, m.silenciado_hasta
             FROM $tabla_grupos g
             INNER JOIN $tabla_miembros m ON g.id = m.grupo_id
             WHERE m.usuario_id = %d AND g.estado = 'activo'
             ORDER BY g.fecha_actualizacion DESC",
            $usuario_id
        ));

        $resultado = [];
        foreach ($grupos as $g) {
            $no_leidos = $wpdb->get_var($wpdb->prepare(
                "SELECT COUNT(*) FROM $tabla_mensajes
                 WHERE grupo_id = %d AND id > %d AND eliminado = 0",
                $g->id, $g->ultimo_mensaje_leido
            ));

            $ultimo_msg = $wpdb->get_row($wpdb->prepare(
                "SELECT m.*, u.display_name as autor_nombre
                 FROM $tabla_mensajes m
                 LEFT JOIN {$wpdb->users} u ON m.usuario_id = u.ID
                 WHERE m.grupo_id = %d AND m.eliminado = 0
                 ORDER BY m.id DESC LIMIT 1",
                $g->id
            ));

            $resultado[] = [
                'id' => (int) $g->id,
                'nombre' => $g->nombre,
                'slug' => $g->slug,
                'descripcion' => wp_trim_words($g->descripcion, 20),
                'imagen_url' => $g->imagen_url,
                'color' => $g->color,
                'tipo' => $g->tipo,
                'entidad_tipo' => sanitize_key($g->entidad_tipo ?? ''),
                'entidad_id' => (int) ($g->entidad_id ?? 0),
                'miembros' => (int) $g->miembros_count,
                'mensajes_no_leidos' => (int) $no_leidos,
                'mi_rol' => $g->rol,
                'silenciado' => $g->silenciado_hasta && strtotime($g->silenciado_hasta) > time(),
                'ultimo_mensaje' => $ultimo_msg ? [
                    'texto' => wp_trim_words($ultimo_msg->mensaje, 10),
                    'autor' => $ultimo_msg->autor_nombre,
                    'fecha' => $this->tiempo_relativo($ultimo_msg->fecha_creacion),
                    'fecha_iso' => mysql2date('c', $ultimo_msg->fecha_creacion, false),
                    'timestamp' => strtotime($ultimo_msg->fecha_creacion),
                ] : null,
            ];
        }

        return ['success' => true, 'grupos' => $resultado];
    }

    /**
     * Obtiene los grupos del usuario actual o de un usuario específico.
     *
     * @param int $usuario_id
     * @return array
     */
    public function get_grupos_usuario($usuario_id) {
        $respuesta = $this->action_mis_grupos([
            'usuario_id' => absint($usuario_id),
        ]);

        return !empty($respuesta['success']) && !empty($respuesta['grupos'])
            ? (array) $respuesta['grupos']
            : [];
    }

    /**
     * Acción: Grupos públicos
     */
    private function action_grupos_publicos($params) {
        global $wpdb;
        $tabla_grupos = $wpdb->prefix . 'flavor_chat_grupos';

        $where = ["g.tipo = 'publico'", "g.estado = 'activo'"];
        $where_values = [];

        if (!empty($params['categoria'])) {
            $where[] = "g.categoria = %s";
            $where_values[] = sanitize_text_field($params['categoria']);
        }

        if (!empty($params['busqueda'])) {
            $where[] = "(g.nombre LIKE %s OR g.descripcion LIKE %s)";
            $busqueda = '%' . $wpdb->esc_like(sanitize_text_field($params['busqueda'])) . '%';
            $where_values[] = $busqueda;
            $where_values[] = $busqueda;
        }

        $pagina = max(1, intval($params['pagina'] ?? 1));
        $limite = 20;
        $offset = ($pagina - 1) * $limite;

        $where_sql = implode(' AND ', $where);

        $total = $wpdb->get_var(
            $wpdb->prepare(
                "SELECT COUNT(*) FROM $tabla_grupos g WHERE $where_sql",
                ...$where_values
            )
        );

        $grupos = $wpdb->get_results(
            $wpdb->prepare(
                "SELECT g.* FROM $tabla_grupos g
                 WHERE $where_sql
                 ORDER BY g.miembros_count DESC, g.mensajes_count DESC
                 LIMIT %d OFFSET %d",
                array_merge($where_values, [$limite, $offset])
            )
        );

        $usuario_id = get_current_user_id();
        $tabla_miembros = $wpdb->prefix . 'flavor_chat_grupos_miembros';

        $resultado = [];
        foreach ($grupos as $g) {
            $es_miembro = false;
            if ($usuario_id) {
                $es_miembro = $wpdb->get_var($wpdb->prepare(
                    "SELECT COUNT(*) FROM $tabla_miembros WHERE grupo_id = %d AND usuario_id = %d",
                    $g->id, $usuario_id
                )) > 0;
            }

            $resultado[] = [
                'id' => (int) $g->id,
                'nombre' => $g->nombre,
                'slug' => $g->slug,
                'descripcion' => wp_trim_words($g->descripcion, 30),
                'imagen_url' => $g->imagen_url,
                'color' => $g->color,
                'categoria' => $g->categoria,
                'miembros' => (int) $g->miembros_count,
                'mensajes' => (int) $g->mensajes_count,
                'es_miembro' => $es_miembro,
            ];
        }

        return [
            'success' => true,
            'grupos' => $resultado,
            'total' => (int) $total,
            'paginas' => ceil($total / $limite),
            'pagina_actual' => $pagina,
        ];
    }

    /**
     * Acción: Crear grupo
     */
    private function action_crear_grupo($params) {
        $usuario_id = get_current_user_id();
        if (!$usuario_id) {
            return ['success' => false, 'error' => __('Debes iniciar sesión', 'flavor-chat-ia')];
        }

        $settings = $this->get_settings();
        if (!$settings['permite_crear_grupos']) {
            return ['success' => false, 'error' => __('No tienes permisos para crear grupos', 'flavor-chat-ia')];
        }

        $nombre = sanitize_text_field($params['nombre'] ?? '');
        if (strlen($nombre) < 3) {
            return ['success' => false, 'error' => __('El nombre del grupo debe tener al menos 3 caracteres', 'flavor-chat-ia')];
        }

        $tipo = in_array($params['tipo'] ?? '', ['publico', 'privado', 'secreto']) ? $params['tipo'] : 'publico';
        if ($tipo !== 'publico' && !$settings['permite_grupos_privados']) {
            $tipo = 'publico';
        }

        global $wpdb;
        $tabla_grupos = $wpdb->prefix . 'flavor_chat_grupos';
        $tabla_miembros = $wpdb->prefix . 'flavor_chat_grupos_miembros';

        $slug = sanitize_title($nombre);
        $slug_base = $slug;
        $contador = 1;
        while ($wpdb->get_var($wpdb->prepare("SELECT id FROM $tabla_grupos WHERE slug = %s", $slug))) {
            $slug = $slug_base . '-' . $contador++;
        }

        $insertado = $wpdb->insert($tabla_grupos, [
            'nombre' => $nombre,
            'slug' => $slug,
            'descripcion' => sanitize_textarea_field($params['descripcion'] ?? ''),
            'color' => sanitize_hex_color($params['color'] ?? '#2271b1') ?: '#2271b1',
            'creador_id' => $usuario_id,
            'tipo' => $tipo,
            'categoria' => sanitize_text_field($params['categoria'] ?? ''),
            'max_miembros' => $settings['max_miembros_por_grupo'],
            'miembros_count' => 1,
        ]);

        if (!$insertado) {
            return ['success' => false, 'error' => __('Error al crear el grupo', 'flavor-chat-ia')];
        }

        $grupo_id = $wpdb->insert_id;

        // Añadir creador como admin
        $wpdb->insert($tabla_miembros, [
            'grupo_id' => $grupo_id,
            'usuario_id' => $usuario_id,
            'rol' => 'admin',
        ]);

        // Mensaje de sistema
        $this->crear_mensaje_sistema($grupo_id, 'grupo_creado', [
            'usuario_id' => $usuario_id,
            'usuario_nombre' => wp_get_current_user()->display_name,
        ]);

        do_action('flavor_gamificacion_agregar_puntos', $usuario_id, 10, 'crear_grupo_chat');

        return [
            'success' => true,
            'grupo_id' => $grupo_id,
            'slug' => $slug,
            'mensaje' => __('Grupo creado correctamente', 'flavor-chat-ia'),
        ];
    }

    /**
     * Acción: Unirse a grupo
     */
    private function action_unirse_grupo($params) {
        $usuario_id = get_current_user_id();
        if (!$usuario_id) {
            return ['success' => false, 'error' => __('Debes iniciar sesión', 'flavor-chat-ia')];
        }

        $grupo_id = intval($params['grupo_id'] ?? 0);
        if (!$grupo_id) {
            return ['success' => false, 'error' => __('Grupo no especificado', 'flavor-chat-ia')];
        }

        global $wpdb;
        $tabla_grupos = $wpdb->prefix . 'flavor_chat_grupos';
        $tabla_miembros = $wpdb->prefix . 'flavor_chat_grupos_miembros';
        $tabla_invitaciones = $wpdb->prefix . 'flavor_chat_grupos_invitaciones';

        $grupo = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM $tabla_grupos WHERE id = %d AND estado = 'activo'",
            $grupo_id
        ));

        if (!$grupo) {
            return ['success' => false, 'error' => __('El grupo no existe o no está activo', 'flavor-chat-ia')];
        }

        // Verificar si ya es miembro
        $es_miembro = $wpdb->get_var($wpdb->prepare(
            "SELECT id FROM $tabla_miembros WHERE grupo_id = %d AND usuario_id = %d",
            $grupo_id, $usuario_id
        ));

        if ($es_miembro) {
            return ['success' => false, 'error' => __('Ya eres miembro de este grupo', 'flavor-chat-ia')];
        }

        // Verificar límite de miembros
        if ($grupo->miembros_count >= $grupo->max_miembros) {
            return ['success' => false, 'error' => __('El grupo ha alcanzado el límite de miembros', 'flavor-chat-ia')];
        }

        // Para grupos privados/secretos, verificar invitación
        if ($grupo->tipo !== 'publico') {
            $codigo = sanitize_text_field($params['codigo_invitacion'] ?? '');
            $invitacion = null;

            if ($codigo) {
                $invitacion = $wpdb->get_row($wpdb->prepare(
                    "SELECT * FROM $tabla_invitaciones
                     WHERE grupo_id = %d AND codigo = %s AND estado = 'pendiente'
                     AND (fecha_expiracion IS NULL OR fecha_expiracion > NOW())",
                    $grupo_id, $codigo
                ));
            } else {
                $invitacion = $wpdb->get_row($wpdb->prepare(
                    "SELECT * FROM $tabla_invitaciones
                     WHERE grupo_id = %d AND invitado_id = %d AND estado = 'pendiente'
                     AND (fecha_expiracion IS NULL OR fecha_expiracion > NOW())",
                    $grupo_id, $usuario_id
                ));
            }

            if (!$invitacion) {
                return ['success' => false, 'error' => __('Necesitas una invitación para unirte a este grupo', 'flavor-chat-ia')];
            }

            // Marcar invitación como aceptada
            $wpdb->update($tabla_invitaciones, ['estado' => 'aceptada'], ['id' => $invitacion->id]);
        }

        // Añadir como miembro
        $wpdb->insert($tabla_miembros, [
            'grupo_id' => $grupo_id,
            'usuario_id' => $usuario_id,
            'rol' => 'miembro',
        ]);

        // Actualizar contador
        $wpdb->query($wpdb->prepare(
            "UPDATE $tabla_grupos SET miembros_count = miembros_count + 1 WHERE id = %d",
            $grupo_id
        ));

        // Mensaje de sistema
        $this->crear_mensaje_sistema($grupo_id, 'usuario_unido', [
            'usuario_id' => $usuario_id,
            'usuario_nombre' => wp_get_current_user()->display_name,
        ]);

        return ['success' => true, 'mensaje' => __('Te has unido al grupo.', 'flavor-chat-ia')];
    }

    /**
     * Acción: Salir del grupo
     */
    private function action_salir_grupo($params) {
        $usuario_id = get_current_user_id();
        if (!$usuario_id) {
            return ['success' => false, 'error' => __('Debes iniciar sesión', 'flavor-chat-ia')];
        }

        $grupo_id = intval($params['grupo_id'] ?? 0);

        global $wpdb;
        $tabla_grupos = $wpdb->prefix . 'flavor_chat_grupos';
        $tabla_miembros = $wpdb->prefix . 'flavor_chat_grupos_miembros';

        $miembro = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM $tabla_miembros WHERE grupo_id = %d AND usuario_id = %d",
            $grupo_id, $usuario_id
        ));

        if (!$miembro) {
            return ['success' => false, 'error' => __('No eres miembro de este grupo', 'flavor-chat-ia')];
        }

        // Si es el único admin, no puede salir
        if ($miembro->rol === 'admin') {
            $otros_admins = $wpdb->get_var($wpdb->prepare(
                "SELECT COUNT(*) FROM $tabla_miembros
                 WHERE grupo_id = %d AND rol = 'admin' AND usuario_id != %d",
                $grupo_id, $usuario_id
            ));

            if (!$otros_admins) {
                return ['success' => false, 'error' => __('Debes asignar otro administrador antes de salir', 'flavor-chat-ia')];
            }
        }

        $wpdb->delete($tabla_miembros, ['grupo_id' => $grupo_id, 'usuario_id' => $usuario_id]);

        $wpdb->query($wpdb->prepare(
            "UPDATE $tabla_grupos SET miembros_count = GREATEST(0, miembros_count - 1) WHERE id = %d",
            $grupo_id
        ));

        $this->crear_mensaje_sistema($grupo_id, 'usuario_salio', [
            'usuario_id' => $usuario_id,
            'usuario_nombre' => wp_get_current_user()->display_name,
        ]);

        return ['success' => true, 'mensaje' => __('Has salido del grupo', 'flavor-chat-ia')];
    }

    /**
     * Acción: Ver mensajes
     */
    private function action_mensajes($params) {
        $usuario_id = get_current_user_id();
        $grupo_id = intval($params['grupo_id'] ?? 0);

        if (!$this->usuario_puede_ver_grupo($usuario_id, $grupo_id)) {
            return ['success' => false, 'error' => __('No tienes acceso a este grupo', 'flavor-chat-ia')];
        }

        global $wpdb;
        $tabla_mensajes = $wpdb->prefix . 'flavor_chat_grupos_mensajes';
        $tabla_reacciones = $wpdb->prefix . 'flavor_chat_grupos_reacciones';

        $limite = min(100, max(10, intval($params['limite'] ?? 50)));
        $antes_de = intval($params['antes_de'] ?? 0);

        $where = "m.grupo_id = %d AND m.hilo_padre IS NULL";
        $where_values = [$grupo_id];

        if ($antes_de) {
            $where .= " AND m.id < %d";
            $where_values[] = $antes_de;
        }

        $mensajes = $wpdb->get_results($wpdb->prepare(
            "SELECT m.*, u.display_name as autor_nombre
             FROM $tabla_mensajes m
             LEFT JOIN {$wpdb->users} u ON m.usuario_id = u.ID
             WHERE $where
             ORDER BY m.id DESC
             LIMIT %d",
            array_merge($where_values, [$limite])
        ));

        $mensajes = array_reverse($mensajes);
        $resultado = [];

        foreach ($mensajes as $msg) {
            // Obtener reacciones agrupadas
            $reacciones = $wpdb->get_results($wpdb->prepare(
                "SELECT emoji, COUNT(*) as count,
                 GROUP_CONCAT(usuario_id) as usuarios
                 FROM $tabla_reacciones
                 WHERE mensaje_id = %d
                 GROUP BY emoji",
                $msg->id
            ));

            $reacciones_formato = [];
            foreach ($reacciones as $r) {
                $usuarios_ids = explode(',', $r->usuarios);
                $reacciones_formato[] = [
                    'emoji' => $r->emoji,
                    'count' => (int) $r->count,
                    'yo_reaccione' => in_array($usuario_id, $usuarios_ids),
                ];
            }

            $resultado[] = [
                'id' => (int) $msg->id,
                'usuario_id' => (int) $msg->usuario_id,
                'autor_nombre' => $msg->autor_nombre ?: 'Usuario',
                'autor_avatar' => get_avatar_url($msg->usuario_id, ['size' => 48]),
                'mensaje' => $msg->eliminado ? '' : $msg->mensaje,
                'mensaje_html' => $msg->eliminado ? '' : $this->formatear_mensaje($msg->mensaje),
                'tipo' => $msg->tipo,
                'adjuntos' => $msg->adjuntos ? json_decode($msg->adjuntos, true) : [],
                'responde_a' => $msg->responde_a ? (int) $msg->responde_a : null,
                'menciones' => $msg->menciones ? json_decode($msg->menciones, true) : [],
                'reacciones' => $reacciones_formato,
                'respuestas_count' => (int) $msg->respuestas_count,
                'editado' => (bool) $msg->editado,
                'eliminado' => (bool) $msg->eliminado,
                'fecha' => $msg->fecha_creacion,
                'fecha_humana' => $this->tiempo_relativo($msg->fecha_creacion),
                'es_mio' => $msg->usuario_id == $usuario_id,
            ];
        }

        // Marcar como leídos
        if ($usuario_id && !empty($resultado)) {
            $tabla_miembros = $wpdb->prefix . 'flavor_chat_grupos_miembros';
            $ultimo_id = end($resultado)['id'];
            $wpdb->update(
                $tabla_miembros,
                ['ultimo_mensaje_leido' => $ultimo_id],
                ['grupo_id' => $grupo_id, 'usuario_id' => $usuario_id]
            );
        }

        return [
            'success' => true,
            'mensajes' => $resultado,
            'hay_mas' => count($mensajes) === $limite,
        ];
    }

    /**
     * Acción: Enviar mensaje
     */
    private function action_enviar_mensaje($params) {
        $usuario_id = get_current_user_id();
        if (!$usuario_id) {
            return ['success' => false, 'error' => __('Debes iniciar sesión', 'flavor-chat-ia')];
        }

        $grupo_id = intval($params['grupo_id'] ?? 0);

        if (!$this->usuario_puede_escribir($usuario_id, $grupo_id)) {
            return ['success' => false, 'error' => __('No puedes escribir en este grupo', 'flavor-chat-ia')];
        }

        $mensaje = trim($params['mensaje'] ?? '');
        if (empty($mensaje) && empty($params['adjuntos'])) {
            return ['success' => false, 'error' => __('El mensaje no puede estar vacío', 'flavor-chat-ia')];
        }

        if (strlen($mensaje) > 5000) {
            return ['success' => false, 'error' => __('El mensaje es demasiado largo (máximo 5000 caracteres)', 'flavor-chat-ia')];
        }

        global $wpdb;
        $tabla_mensajes = $wpdb->prefix . 'flavor_chat_grupos_mensajes';
        $tabla_grupos = $wpdb->prefix . 'flavor_chat_grupos';
        $tabla_miembros = $wpdb->prefix . 'flavor_chat_grupos_miembros';

        // Detectar menciones
        $menciones = [];
        if (preg_match_all('/@(\w+)/', $mensaje, $matches)) {
            foreach ($matches[1] as $username) {
                $user = get_user_by('login', $username);
                if ($user) {
                    $menciones[] = $user->ID;
                }
            }
        }

        $tipo = 'texto';
        $adjuntos = null;
        if (!empty($params['adjuntos'])) {
            $adjuntos = json_encode($params['adjuntos']);
            $tipo = 'archivo';
        }

        $responde_a = !empty($params['responde_a']) ? intval($params['responde_a']) : null;

        $wpdb->insert($tabla_mensajes, [
            'grupo_id' => $grupo_id,
            'usuario_id' => $usuario_id,
            'mensaje' => sanitize_textarea_field($mensaje),
            'mensaje_html' => $this->formatear_mensaje($mensaje),
            'tipo' => $tipo,
            'adjuntos' => $adjuntos,
            'responde_a' => $responde_a,
            'menciones' => !empty($menciones) ? json_encode($menciones) : null,
        ]);

        $mensaje_id = $wpdb->insert_id;

        // Actualizar grupo
        $wpdb->query($wpdb->prepare(
            "UPDATE $tabla_grupos SET
             mensajes_count = mensajes_count + 1,
             ultimo_mensaje_id = %d,
             fecha_actualizacion = NOW()
             WHERE id = %d",
            $mensaje_id, $grupo_id
        ));

        // Actualizar último leído del autor
        $wpdb->update(
            $tabla_miembros,
            ['ultimo_mensaje_leido' => $mensaje_id],
            ['grupo_id' => $grupo_id, 'usuario_id' => $usuario_id]
        );

        // Si responde a otro mensaje, incrementar contador de respuestas
        if ($responde_a) {
            $wpdb->query($wpdb->prepare(
                "UPDATE $tabla_mensajes SET respuestas_count = respuestas_count + 1 WHERE id = %d",
                $responde_a
            ));
        }

        // Notificar menciones
        foreach ($menciones as $mencionado_id) {
            if ($mencionado_id != $usuario_id) {
                do_action('flavor_notificacion_enviar', $mencionado_id, 'chat_mencion', [
                    'grupo_id' => $grupo_id,
                    'mensaje_id' => $mensaje_id,
                    'autor_id' => $usuario_id,
                    'autor_nombre' => wp_get_current_user()->display_name,
                ]);
            }
        }

        // Puntos por participar
        do_action('flavor_gamificacion_agregar_puntos', $usuario_id, 1, 'mensaje_chat_grupo');

        return [
            'success' => true,
            'mensaje_id' => $mensaje_id,
            'mensaje' => [
                'id' => $mensaje_id,
                'usuario_id' => $usuario_id,
                'autor_nombre' => wp_get_current_user()->display_name,
                'autor_avatar' => get_avatar_url($usuario_id, ['size' => 48]),
                'mensaje' => $mensaje,
                'mensaje_html' => $this->formatear_mensaje($mensaje),
                'tipo' => $tipo,
                'adjuntos' => $params['adjuntos'] ?? [],
                'responde_a' => $responde_a,
                'menciones' => $menciones,
                'reacciones' => [],
                'editado' => false,
                'eliminado' => false,
                'fecha' => current_time('mysql'),
                'fecha_humana' => __('ahora', 'flavor-chat-ia'),
                'es_mio' => true,
            ],
        ];
    }

    /**
     * Acción: Info del grupo
     */
    private function action_info_grupo($params) {
        $usuario_id = get_current_user_id();
        $grupo_id = intval($params['grupo_id'] ?? 0);

        if (!$this->usuario_puede_ver_grupo($usuario_id, $grupo_id)) {
            return ['success' => false, 'error' => __('No tienes acceso a este grupo', 'flavor-chat-ia')];
        }

        global $wpdb;
        $tabla_grupos = $wpdb->prefix . 'flavor_chat_grupos';
        $tabla_miembros = $wpdb->prefix . 'flavor_chat_grupos_miembros';
        $tabla_fijados = $wpdb->prefix . 'flavor_chat_grupos_fijados';
        $tabla_mensajes = $wpdb->prefix . 'flavor_chat_grupos_mensajes';

        $grupo = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM $tabla_grupos WHERE id = %d",
            $grupo_id
        ));

        if (!$grupo) {
            return ['success' => false, 'error' => __('Grupo no encontrado', 'flavor-chat-ia')];
        }

        // Obtener mi membresía
        $mi_membresia = null;
        if ($usuario_id) {
            $mi_membresia = $wpdb->get_row($wpdb->prepare(
                "SELECT * FROM $tabla_miembros WHERE grupo_id = %d AND usuario_id = %d",
                $grupo_id, $usuario_id
            ));
        }

        // Obtener admins y moderadores
        $admins = $wpdb->get_results($wpdb->prepare(
            "SELECT m.*, u.display_name, u.user_email
             FROM $tabla_miembros m
             JOIN {$wpdb->users} u ON m.usuario_id = u.ID
             WHERE m.grupo_id = %d AND m.rol IN ('admin', 'moderador')
             ORDER BY m.rol DESC, m.fecha_ingreso ASC",
            $grupo_id
        ));

        // Mensajes fijados
        $fijados = $wpdb->get_results($wpdb->prepare(
            "SELECT f.*, m.mensaje, m.usuario_id, u.display_name as autor_nombre
             FROM $tabla_fijados f
             JOIN $tabla_mensajes m ON f.mensaje_id = m.id
             JOIN {$wpdb->users} u ON m.usuario_id = u.ID
             WHERE f.grupo_id = %d
             ORDER BY f.fecha_creacion DESC",
            $grupo_id
        ));

        $creador = get_userdata($grupo->creador_id);

        return [
            'success' => true,
            'grupo' => [
                'id' => (int) $grupo->id,
                'nombre' => $grupo->nombre,
                'slug' => $grupo->slug,
                'descripcion' => $grupo->descripcion,
                'imagen_url' => $grupo->imagen_url,
                'color' => $grupo->color,
                'tipo' => $grupo->tipo,
                'categoria' => $grupo->categoria,
                'miembros_count' => (int) $grupo->miembros_count,
                'mensajes_count' => (int) $grupo->mensajes_count,
                'max_miembros' => (int) $grupo->max_miembros,
                'creador' => [
                    'id' => (int) $grupo->creador_id,
                    'nombre' => $creador ? $creador->display_name : 'Usuario',
                    'avatar' => get_avatar_url($grupo->creador_id, ['size' => 48]),
                ],
                'fecha_creacion' => $grupo->fecha_creacion,
                'permite_archivos' => (bool) $grupo->permite_archivos,
                'permite_encuestas' => (bool) $grupo->permite_encuestas,
                'solo_admins_publican' => (bool) $grupo->solo_admins_publican,
            ],
            'mi_membresia' => $mi_membresia ? [
                'rol' => $mi_membresia->rol,
                'notificaciones' => $mi_membresia->notificaciones,
                'silenciado_hasta' => $mi_membresia->silenciado_hasta,
                'fecha_ingreso' => $mi_membresia->fecha_ingreso,
            ] : null,
            'admins' => array_map(function($a) {
                return [
                    'id' => (int) $a->usuario_id,
                    'nombre' => $a->display_name,
                    'avatar' => get_avatar_url($a->usuario_id, ['size' => 48]),
                    'rol' => $a->rol,
                ];
            }, $admins),
            'mensajes_fijados' => array_map(function($f) {
                return [
                    'mensaje_id' => (int) $f->mensaje_id,
                    'mensaje' => wp_trim_words($f->mensaje, 20),
                    'autor_nombre' => $f->autor_nombre,
                ];
            }, $fijados),
        ];
    }

    /**
     * Acción: Miembros del grupo
     */
    private function action_miembros_grupo($params) {
        $usuario_id = get_current_user_id();
        $grupo_id = intval($params['grupo_id'] ?? 0);

        if (!$this->usuario_puede_ver_grupo($usuario_id, $grupo_id)) {
            return ['success' => false, 'error' => __('No tienes acceso a este grupo', 'flavor-chat-ia')];
        }

        global $wpdb;
        $tabla_miembros = $wpdb->prefix . 'flavor_chat_grupos_miembros';

        $miembros = $wpdb->get_results($wpdb->prepare(
            "SELECT m.*, u.display_name, u.user_email
             FROM $tabla_miembros m
             JOIN {$wpdb->users} u ON m.usuario_id = u.ID
             WHERE m.grupo_id = %d
             ORDER BY FIELD(m.rol, 'admin', 'moderador', 'miembro'), m.fecha_ingreso ASC",
            $grupo_id
        ));

        return [
            'success' => true,
            'miembros' => array_map(function($m) {
                return [
                    'id' => (int) $m->usuario_id,
                    'nombre' => $m->display_name,
                    'avatar' => get_avatar_url($m->usuario_id, ['size' => 48]),
                    'rol' => $m->rol,
                    'fecha_ingreso' => $m->fecha_ingreso,
                    'online' => $this->usuario_online($m->usuario_id),
                ];
            }, $miembros),
            'total' => count($miembros),
        ];
    }

    /**
     * Acción: Invitar miembro
     */
    private function action_invitar_miembro($params) {
        $usuario_id = get_current_user_id();
        if (!$usuario_id) {
            return ['success' => false, 'error' => __('Debes iniciar sesión', 'flavor-chat-ia')];
        }

        $grupo_id = intval($params['grupo_id'] ?? 0);

        if (!$this->usuario_es_admin_o_mod($usuario_id, $grupo_id)) {
            return ['success' => false, 'error' => __('No tienes permisos para invitar miembros', 'flavor-chat-ia')];
        }

        global $wpdb;
        $tabla_invitaciones = $wpdb->prefix . 'flavor_chat_grupos_invitaciones';
        $tabla_miembros = $wpdb->prefix . 'flavor_chat_grupos_miembros';

        $invitado_id = intval($params['usuario_id'] ?? 0);
        $email = sanitize_email($params['email'] ?? '');

        if (!$invitado_id && !$email) {
            return ['success' => false, 'error' => __('Debes especificar un usuario o email', 'flavor-chat-ia')];
        }

        // Verificar si ya es miembro
        if ($invitado_id) {
            $ya_miembro = $wpdb->get_var($wpdb->prepare(
                "SELECT id FROM $tabla_miembros WHERE grupo_id = %d AND usuario_id = %d",
                $grupo_id, $invitado_id
            ));
            if ($ya_miembro) {
                return ['success' => false, 'error' => __('El usuario ya es miembro del grupo', 'flavor-chat-ia')];
            }
        }

        $codigo = wp_generate_password(32, false);

        $wpdb->insert($tabla_invitaciones, [
            'grupo_id' => $grupo_id,
            'invitado_id' => $invitado_id ?: null,
            'invitado_email' => $email ?: null,
            'invitador_id' => $usuario_id,
            'codigo' => $codigo,
            'fecha_expiracion' => date('Y-m-d H:i:s', strtotime('+7 days')),
        ]);

        // Notificar al invitado
        if ($invitado_id) {
            do_action('flavor_notificacion_enviar', $invitado_id, 'chat_invitacion', [
                'grupo_id' => $grupo_id,
                'invitador_id' => $usuario_id,
                'codigo' => $codigo,
            ]);
        }

        return [
            'success' => true,
            'codigo' => $codigo,
            'mensaje' => __('Invitación enviada correctamente', 'flavor-chat-ia'),
        ];
    }

    /**
     * Acción: Expulsar miembro
     */
    private function action_expulsar_miembro($params) {
        $usuario_id = get_current_user_id();
        if (!$usuario_id) {
            return ['success' => false, 'error' => __('Debes iniciar sesión', 'flavor-chat-ia')];
        }

        $grupo_id = intval($params['grupo_id'] ?? 0);
        $expulsado_id = intval($params['usuario_id'] ?? 0);

        if (!$this->usuario_es_admin_o_mod($usuario_id, $grupo_id)) {
            return ['success' => false, 'error' => __('No tienes permisos para expulsar miembros', 'flavor-chat-ia')];
        }

        if ($expulsado_id === $usuario_id) {
            return ['success' => false, 'error' => __('No puedes expulsarte a ti mismo', 'flavor-chat-ia')];
        }

        global $wpdb;
        $tabla_miembros = $wpdb->prefix . 'flavor_chat_grupos_miembros';
        $tabla_grupos = $wpdb->prefix . 'flavor_chat_grupos';

        // No permitir expulsar admins si no eres admin
        $mi_rol = $this->obtener_rol_usuario($usuario_id, $grupo_id);
        $su_rol = $this->obtener_rol_usuario($expulsado_id, $grupo_id);

        if ($su_rol === 'admin' && $mi_rol !== 'admin') {
            return ['success' => false, 'error' => __('No puedes expulsar a un administrador', 'flavor-chat-ia')];
        }

        $wpdb->delete($tabla_miembros, ['grupo_id' => $grupo_id, 'usuario_id' => $expulsado_id]);

        $wpdb->query($wpdb->prepare(
            "UPDATE $tabla_grupos SET miembros_count = GREATEST(0, miembros_count - 1) WHERE id = %d",
            $grupo_id
        ));

        $expulsado = get_userdata($expulsado_id);
        $this->crear_mensaje_sistema($grupo_id, 'usuario_expulsado', [
            'usuario_id' => $expulsado_id,
            'usuario_nombre' => $expulsado ? $expulsado->display_name : 'Usuario',
            'por_id' => $usuario_id,
            'por_nombre' => wp_get_current_user()->display_name,
        ]);

        return ['success' => true, 'mensaje' => __('Usuario expulsado del grupo', 'flavor-chat-ia')];
    }

    /**
     * Acción: Cambiar rol
     */
    private function action_cambiar_rol($params) {
        $usuario_id = get_current_user_id();
        if (!$usuario_id) {
            return ['success' => false, 'error' => __('Debes iniciar sesión', 'flavor-chat-ia')];
        }

        $grupo_id = intval($params['grupo_id'] ?? 0);
        $miembro_id = intval($params['usuario_id'] ?? 0);
        $nuevo_rol = $params['rol'] ?? '';

        if (!in_array($nuevo_rol, ['miembro', 'moderador', 'admin'])) {
            return ['success' => false, 'error' => __('Rol no válido', 'flavor-chat-ia')];
        }

        if (!$this->usuario_es_admin($usuario_id, $grupo_id)) {
            return ['success' => false, 'error' => __('Solo los administradores pueden cambiar roles', 'flavor-chat-ia')];
        }

        global $wpdb;
        $tabla_miembros = $wpdb->prefix . 'flavor_chat_grupos_miembros';

        $wpdb->update(
            $tabla_miembros,
            ['rol' => $nuevo_rol],
            ['grupo_id' => $grupo_id, 'usuario_id' => $miembro_id]
        );

        return ['success' => true, 'mensaje' => __('Rol actualizado correctamente', 'flavor-chat-ia')];
    }

    /**
     * Acción: Silenciar grupo
     */
    private function action_silenciar_grupo($params) {
        $usuario_id = get_current_user_id();
        if (!$usuario_id) {
            return ['success' => false, 'error' => __('Debes iniciar sesión', 'flavor-chat-ia')];
        }

        $grupo_id = intval($params['grupo_id'] ?? 0);
        $horas = intval($params['duracion_horas'] ?? 8);

        global $wpdb;
        $tabla_miembros = $wpdb->prefix . 'flavor_chat_grupos_miembros';

        $silenciado_hasta = $horas > 0 ? date('Y-m-d H:i:s', strtotime("+{$horas} hours")) : null;

        $wpdb->update(
            $tabla_miembros,
            ['silenciado_hasta' => $silenciado_hasta],
            ['grupo_id' => $grupo_id, 'usuario_id' => $usuario_id]
        );

        return [
            'success' => true,
            'silenciado_hasta' => $silenciado_hasta,
            'mensaje' => $horas > 0 ? "Notificaciones silenciadas por {$horas} horas." : 'Notificaciones activadas.',
        ];
    }

    /**
     * Acción: Buscar mensajes
     */
    private function action_buscar_mensajes($params) {
        $usuario_id = get_current_user_id();
        $grupo_id = intval($params['grupo_id'] ?? 0);

        if (!$this->usuario_puede_ver_grupo($usuario_id, $grupo_id)) {
            return ['success' => false, 'error' => __('No tienes acceso a este grupo', 'flavor-chat-ia')];
        }

        $query = sanitize_text_field($params['query'] ?? '');
        if (strlen($query) < 3) {
            return ['success' => false, 'error' => __('La búsqueda debe tener al menos 3 caracteres', 'flavor-chat-ia')];
        }

        global $wpdb;
        $tabla_mensajes = $wpdb->prefix . 'flavor_chat_grupos_mensajes';

        $resultados = $wpdb->get_results($wpdb->prepare(
            "SELECT m.*, u.display_name as autor_nombre
             FROM $tabla_mensajes m
             LEFT JOIN {$wpdb->users} u ON m.usuario_id = u.ID
             WHERE m.grupo_id = %d
             AND m.eliminado = 0
             AND MATCH(m.mensaje) AGAINST(%s IN NATURAL LANGUAGE MODE)
             ORDER BY m.fecha_creacion DESC
             LIMIT 50",
            $grupo_id, $query
        ));

        return [
            'success' => true,
            'resultados' => array_map(function($m) {
                return [
                    'id' => (int) $m->id,
                    'mensaje' => wp_trim_words($m->mensaje, 30),
                    'autor_nombre' => $m->autor_nombre,
                    'fecha' => $m->fecha_creacion,
                    'fecha_humana' => $this->tiempo_relativo($m->fecha_creacion),
                ];
            }, $resultados),
            'total' => count($resultados),
        ];
    }

    /**
     * Acción: Crear encuesta
     */
    private function action_crear_encuesta($params) {
        $usuario_id = get_current_user_id();
        if (!$usuario_id) {
            return ['success' => false, 'error' => __('Debes iniciar sesión', 'flavor-chat-ia')];
        }

        $grupo_id = intval($params['grupo_id'] ?? 0);

        if (!$this->usuario_puede_escribir($usuario_id, $grupo_id)) {
            return ['success' => false, 'error' => __('No puedes escribir en este grupo', 'flavor-chat-ia')];
        }

        global $wpdb;
        $tabla_grupos = $wpdb->prefix . 'flavor_chat_grupos';

        $grupo = $wpdb->get_row($wpdb->prepare(
            "SELECT permite_encuestas FROM $tabla_grupos WHERE id = %d",
            $grupo_id
        ));

        if (!$grupo || !$grupo->permite_encuestas) {
            return ['success' => false, 'error' => __('Las encuestas no están permitidas en este grupo', 'flavor-chat-ia')];
        }

        $pregunta = sanitize_text_field($params['pregunta'] ?? '');
        $opciones = $params['opciones'] ?? [];

        if (strlen($pregunta) < 5) {
            return ['success' => false, 'error' => __('La pregunta debe tener al menos 5 caracteres', 'flavor-chat-ia')];
        }

        if (!is_array($opciones) || count($opciones) < 2) {
            return ['success' => false, 'error' => __('La encuesta debe tener al menos 2 opciones', 'flavor-chat-ia')];
        }

        $opciones = array_map('sanitize_text_field', array_slice($opciones, 0, 10));

        $tabla_mensajes = $wpdb->prefix . 'flavor_chat_grupos_mensajes';
        $tabla_encuestas = $wpdb->prefix . 'flavor_chat_grupos_encuestas';

        // Crear mensaje de encuesta
        $wpdb->insert($tabla_mensajes, [
            'grupo_id' => $grupo_id,
            'usuario_id' => $usuario_id,
            'mensaje' => $pregunta,
            'tipo' => 'encuesta',
        ]);
        $mensaje_id = $wpdb->insert_id;

        // Crear encuesta
        $wpdb->insert($tabla_encuestas, [
            'mensaje_id' => $mensaje_id,
            'grupo_id' => $grupo_id,
            'pregunta' => $pregunta,
            'opciones' => json_encode($opciones),
            'multiple' => !empty($params['multiple']) ? 1 : 0,
            'anonima' => !empty($params['anonima']) ? 1 : 0,
        ]);

        $encuesta_id = $wpdb->insert_id;

        // Actualizar grupo
        $wpdb->query($wpdb->prepare(
            "UPDATE $tabla_grupos SET mensajes_count = mensajes_count + 1, fecha_actualizacion = NOW() WHERE id = %d",
            $grupo_id
        ));

        return [
            'success' => true,
            'encuesta_id' => $encuesta_id,
            'mensaje_id' => $mensaje_id,
            'mensaje' => __('encuesta_id', 'flavor-chat-ia'),
        ];
    }

    /**
     * Acción: Votar encuesta
     */
    private function action_votar_encuesta($params) {
        $usuario_id = get_current_user_id();
        if (!$usuario_id) {
            return ['success' => false, 'error' => __('Debes iniciar sesión', 'flavor-chat-ia')];
        }

        $encuesta_id = intval($params['encuesta_id'] ?? 0);
        $opcion = intval($params['opcion'] ?? -1);

        global $wpdb;
        $tabla_encuestas = $wpdb->prefix . 'flavor_chat_grupos_encuestas';
        $tabla_votos = $wpdb->prefix . 'flavor_chat_grupos_votos';

        $encuesta = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM $tabla_encuestas WHERE id = %d",
            $encuesta_id
        ));

        if (!$encuesta) {
            return ['success' => false, 'error' => __('Encuesta no encontrada', 'flavor-chat-ia')];
        }

        if ($encuesta->cerrada) {
            return ['success' => false, 'error' => __('Esta encuesta está cerrada', 'flavor-chat-ia')];
        }

        $opciones = json_decode($encuesta->opciones, true);
        if ($opcion < 0 || $opcion >= count($opciones)) {
            return ['success' => false, 'error' => __('Opción no válida', 'flavor-chat-ia')];
        }

        // Si no es múltiple, eliminar voto anterior
        if (!$encuesta->multiple) {
            $wpdb->delete($tabla_votos, [
                'encuesta_id' => $encuesta_id,
                'usuario_id' => $usuario_id,
            ]);
        }

        // Verificar si ya votó esta opción
        $ya_voto = $wpdb->get_var($wpdb->prepare(
            "SELECT id FROM $tabla_votos WHERE encuesta_id = %d AND usuario_id = %d AND opcion_index = %d",
            $encuesta_id, $usuario_id, $opcion
        ));

        if ($ya_voto) {
            // Quitar voto
            $wpdb->delete($tabla_votos, ['id' => $ya_voto]);
            $wpdb->query($wpdb->prepare(
                "UPDATE $tabla_encuestas SET votos_totales = GREATEST(0, votos_totales - 1) WHERE id = %d",
                $encuesta_id
            ));
        } else {
            // Añadir voto
            $wpdb->insert($tabla_votos, [
                'encuesta_id' => $encuesta_id,
                'usuario_id' => $usuario_id,
                'opcion_index' => $opcion,
            ]);
            $wpdb->query($wpdb->prepare(
                "UPDATE $tabla_encuestas SET votos_totales = votos_totales + 1 WHERE id = %d",
                $encuesta_id
            ));
        }

        // Obtener resultados actualizados
        $votos = $wpdb->get_results($wpdb->prepare(
            "SELECT opcion_index, COUNT(*) as count FROM $tabla_votos WHERE encuesta_id = %d GROUP BY opcion_index",
            $encuesta_id
        ));

        $resultados = array_fill(0, count($opciones), 0);
        foreach ($votos as $v) {
            $resultados[$v->opcion_index] = (int) $v->count;
        }

        return [
            'success' => true,
            'resultados' => $resultados,
            'mi_voto' => $ya_voto ? null : $opcion,
        ];
    }

    /**
     * Acción: Reaccionar a mensaje
     */
    private function action_reaccionar($params) {
        $usuario_id = get_current_user_id();
        if (!$usuario_id) {
            return ['success' => false, 'error' => __('Debes iniciar sesión', 'flavor-chat-ia')];
        }

        $mensaje_id = intval($params['mensaje_id'] ?? 0);
        $emoji = sanitize_text_field($params['emoji'] ?? '');

        if (!$emoji) {
            return ['success' => false, 'error' => __('Emoji no especificado', 'flavor-chat-ia')];
        }

        global $wpdb;
        $tabla_reacciones = $wpdb->prefix . 'flavor_chat_grupos_reacciones';
        $tabla_mensajes = $wpdb->prefix . 'flavor_chat_grupos_mensajes';

        // Verificar que existe el mensaje
        $mensaje = $wpdb->get_row($wpdb->prepare(
            "SELECT grupo_id FROM $tabla_mensajes WHERE id = %d AND eliminado = 0",
            $mensaje_id
        ));

        if (!$mensaje) {
            return ['success' => false, 'error' => __('Mensaje no encontrado', 'flavor-chat-ia')];
        }

        // Verificar acceso al grupo
        if (!$this->usuario_puede_ver_grupo($usuario_id, $mensaje->grupo_id)) {
            return ['success' => false, 'error' => __('No tienes acceso a este grupo', 'flavor-chat-ia')];
        }

        // Toggle reacción
        $existente = $wpdb->get_var($wpdb->prepare(
            "SELECT id FROM $tabla_reacciones WHERE mensaje_id = %d AND usuario_id = %d AND emoji = %s",
            $mensaje_id, $usuario_id, $emoji
        ));

        if ($existente) {
            $wpdb->delete($tabla_reacciones, ['id' => $existente]);
            $wpdb->query($wpdb->prepare(
                "UPDATE $tabla_mensajes SET reacciones_count = GREATEST(0, reacciones_count - 1) WHERE id = %d",
                $mensaje_id
            ));
            $agregada = false;
        } else {
            $wpdb->insert($tabla_reacciones, [
                'mensaje_id' => $mensaje_id,
                'usuario_id' => $usuario_id,
                'emoji' => $emoji,
            ]);
            $wpdb->query($wpdb->prepare(
                "UPDATE $tabla_mensajes SET reacciones_count = reacciones_count + 1 WHERE id = %d",
                $mensaje_id
            ));
            $agregada = true;
        }

        return [
            'success' => true,
            'agregada' => $agregada,
            'emoji' => $emoji,
        ];
    }

    /**
     * Acción: Fijar mensaje
     */
    private function action_fijar_mensaje($params) {
        $usuario_id = get_current_user_id();
        if (!$usuario_id) {
            return ['success' => false, 'error' => __('Debes iniciar sesión', 'flavor-chat-ia')];
        }

        $mensaje_id = intval($params['mensaje_id'] ?? 0);

        global $wpdb;
        $tabla_mensajes = $wpdb->prefix . 'flavor_chat_grupos_mensajes';
        $tabla_fijados = $wpdb->prefix . 'flavor_chat_grupos_fijados';

        $mensaje = $wpdb->get_row($wpdb->prepare(
            "SELECT grupo_id FROM $tabla_mensajes WHERE id = %d",
            $mensaje_id
        ));

        if (!$mensaje) {
            return ['success' => false, 'error' => __('Mensaje no encontrado', 'flavor-chat-ia')];
        }

        if (!$this->usuario_es_admin_o_mod($usuario_id, $mensaje->grupo_id)) {
            return ['success' => false, 'error' => __('Solo moderadores y admins pueden fijar mensajes', 'flavor-chat-ia')];
        }

        // Toggle fijado
        $fijado = $wpdb->get_var($wpdb->prepare(
            "SELECT id FROM $tabla_fijados WHERE grupo_id = %d AND mensaje_id = %d",
            $mensaje->grupo_id, $mensaje_id
        ));

        if ($fijado) {
            $wpdb->delete($tabla_fijados, ['id' => $fijado]);
            $esta_fijado = false;
        } else {
            $wpdb->insert($tabla_fijados, [
                'grupo_id' => $mensaje->grupo_id,
                'mensaje_id' => $mensaje_id,
                'fijado_por' => $usuario_id,
            ]);
            $esta_fijado = true;
        }

        return [
            'success' => true,
            'fijado' => $esta_fijado,
        ];
    }

    // ==================== REST API ====================

    /**
     * Registrar rutas REST
     */
    public function register_rest_routes() {
        register_rest_route('flavor/v1', '/chat-grupos', [
            'methods' => 'GET',
            'callback' => [$this, 'rest_mis_grupos'],
            'permission_callback' => [$this, 'public_permission_check'],
        ]);

        register_rest_route('flavor/v1', '/chat-grupos/explorar', [
            'methods' => 'GET',
            'callback' => [$this, 'rest_explorar_grupos'],
            'permission_callback' => [$this, 'public_permission_check'],
        ]);

        register_rest_route('flavor/v1', '/chat-grupos/(?P<id>\d+)', [
            'methods' => 'GET',
            'callback' => [$this, 'rest_info_grupo'],
            'permission_callback' => [$this, 'public_permission_check'],
        ]);

        register_rest_route('flavor/v1', '/chat-grupos/(?P<id>\d+)/mensajes', [
            'methods' => 'GET',
            'callback' => [$this, 'rest_mensajes'],
            'permission_callback' => [$this, 'public_permission_check'],
        ]);

        register_rest_route('flavor/v1', '/chat-grupos/(?P<id>\d+)/mensajes', [
            'methods' => 'POST',
            'callback' => [$this, 'rest_enviar_mensaje'],
            'permission_callback' => 'is_user_logged_in',
        ]);

        register_rest_route('flavor/v1', '/chat-grupos/(?P<id>\d+)/miembros', [
            'methods' => 'GET',
            'callback' => [$this, 'rest_miembros'],
            'permission_callback' => [$this, 'public_permission_check'],
        ]);

        register_rest_route('flavor/v1', '/chat-grupos/crear', [
            'methods' => 'POST',
            'callback' => [$this, 'rest_crear_grupo'],
            'permission_callback' => 'is_user_logged_in',
        ]);

        register_rest_route('flavor/v1', '/chat-grupos/(?P<id>\d+)/unirse', [
            'methods' => 'POST',
            'callback' => [$this, 'rest_unirse'],
            'permission_callback' => 'is_user_logged_in',
        ]);

        register_rest_route('flavor/v1', '/chat-grupos/(?P<id>\d+)/salir', [
            'methods' => 'POST',
            'callback' => [$this, 'rest_salir'],
            'permission_callback' => 'is_user_logged_in',
        ]);

        register_rest_route('flavor/v1', '/chat-grupos/(?P<id>\d+)/typing', [
            'methods' => 'POST',
            'callback' => [$this, 'rest_typing'],
            'permission_callback' => 'is_user_logged_in',
        ]);

        register_rest_route('flavor/v1', '/chat-grupos/mensaje/(?P<id>\d+)/reaccion', [
            'methods' => 'POST',
            'callback' => [$this, 'rest_reaccionar'],
            'permission_callback' => 'is_user_logged_in',
        ]);
    }

    public function rest_mis_grupos($request) {
        return rest_ensure_response($this->sanitize_public_chat_response($this->action_mis_grupos([])));
    }

    public function rest_explorar_grupos($request) {
        $respuesta = $this->action_grupos_publicos([
            'categoria' => $request->get_param('categoria'),
            'busqueda' => $request->get_param('busqueda'),
            'pagina' => $request->get_param('pagina'),
        ]);

        return rest_ensure_response($this->sanitize_public_chat_response($respuesta));
    }

    public function rest_info_grupo($request) {
        $respuesta = $this->action_info_grupo([
            'grupo_id' => $request->get_param('id'),
        ]);

        return rest_ensure_response($this->sanitize_public_chat_response($respuesta));
    }

    public function rest_mensajes($request) {
        $respuesta = $this->action_mensajes([
            'grupo_id' => $request->get_param('id'),
            'antes_de' => $request->get_param('antes_de'),
            'limite' => $request->get_param('limite'),
        ]);

        return rest_ensure_response($this->sanitize_public_chat_response($respuesta));
    }

    public function rest_enviar_mensaje($request) {
        return rest_ensure_response($this->action_enviar_mensaje([
            'grupo_id' => $request->get_param('id'),
            'mensaje' => $request->get_param('mensaje'),
            'responde_a' => $request->get_param('responde_a'),
            'adjuntos' => $request->get_param('adjuntos'),
        ]));
    }

    public function rest_miembros($request) {
        $respuesta = $this->action_miembros_grupo([
            'grupo_id' => $request->get_param('id'),
        ]);

        return rest_ensure_response($this->sanitize_public_chat_response($respuesta));
    }

    public function rest_crear_grupo($request) {
        return rest_ensure_response($this->action_crear_grupo([
            'nombre' => $request->get_param('nombre'),
            'descripcion' => $request->get_param('descripcion'),
            'tipo' => $request->get_param('tipo'),
            'categoria' => $request->get_param('categoria'),
            'color' => $request->get_param('color'),
        ]));
    }

    public function rest_unirse($request) {
        return rest_ensure_response($this->action_unirse_grupo([
            'grupo_id' => $request->get_param('id'),
            'codigo_invitacion' => $request->get_param('codigo'),
        ]));
    }

    public function rest_salir($request) {
        return rest_ensure_response($this->action_salir_grupo([
            'grupo_id' => $request->get_param('id'),
        ]));
    }

    public function rest_typing($request) {
        $usuario_id = get_current_user_id();
        $grupo_id = $request->get_param('id');

        global $wpdb;
        $tabla_miembros = $wpdb->prefix . 'flavor_chat_grupos_miembros';

        $wpdb->update($tabla_miembros, [
            'escribiendo' => 1,
            'escribiendo_timestamp' => current_time('mysql'),
        ], [
            'grupo_id' => $grupo_id,
            'usuario_id' => $usuario_id,
        ]);

        return rest_ensure_response(['success' => true]);
    }

    public function rest_reaccionar($request) {
        return rest_ensure_response($this->action_reaccionar([
            'mensaje_id' => $request->get_param('id'),
            'emoji' => $request->get_param('emoji'),
        ]));
    }

    private function sanitize_public_chat_response($respuesta) {
        if (is_user_logged_in() || empty($respuesta['success'])) {
            return $respuesta;
        }

        if (!empty($respuesta['grupos']) && is_array($respuesta['grupos'])) {
            $respuesta['grupos'] = array_map([$this, 'sanitize_public_group'], $respuesta['grupos']);
        }

        if (!empty($respuesta['mensajes']) && is_array($respuesta['mensajes'])) {
            $respuesta['mensajes'] = array_map([$this, 'sanitize_public_message'], $respuesta['mensajes']);
        }

        if (!empty($respuesta['miembros']) && is_array($respuesta['miembros'])) {
            $respuesta['miembros'] = array_map([$this, 'sanitize_public_member'], $respuesta['miembros']);
        }

        if (!empty($respuesta['grupo']) && is_array($respuesta['grupo'])) {
            $respuesta['grupo'] = $this->sanitize_public_group($respuesta['grupo']);
        }

        return $respuesta;
    }

    private function sanitize_public_message($mensaje) {
        if (!is_array($mensaje)) {
            return $mensaje;
        }

        unset($mensaje['usuario_id'], $mensaje['menciones']);
        $mensaje['autor_avatar'] = '';
        $mensaje['es_mio'] = false;

        return $mensaje;
    }

    private function sanitize_public_member($miembro) {
        if (!is_array($miembro)) {
            return $miembro;
        }

        unset($miembro['id']);
        $miembro['avatar'] = '';

        return $miembro;
    }

    private function sanitize_public_group($grupo) {
        if (!is_array($grupo)) {
            return $grupo;
        }

        if (!empty($grupo['creador']) && is_array($grupo['creador'])) {
            unset($grupo['creador']['id']);
            $grupo['creador']['avatar'] = '';
        }

        if (!empty($grupo['admins']) && is_array($grupo['admins'])) {
            $grupo['admins'] = array_map(function($admin) {
                if (!is_array($admin)) {
                    return $admin;
                }

                unset($admin['id']);
                $admin['avatar'] = '';
                return $admin;
            }, $grupo['admins']);
        }

        return $grupo;
    }
    // ==================== Shortcodes ====================

    public function shortcode_chat_grupos($atts) {
        $atts = shortcode_atts([
            'grupo_id' => 0,
        ], $atts);

        if (!is_user_logged_in()) {
            return '<div class="cg-login-required"><p>' . __('Inicia sesión para acceder al chat de grupos.', 'flavor-chat-ia') . '</p></div>';
        }

        $grupo_id_inicial = absint($atts['grupo_id'] ?? 0);
        if ($grupo_id_inicial <= 0) {
            $grupo_id_inicial = absint($_GET['grupo_id'] ?? 0);
        }

        ob_start();
        ?>
        <div id="flavor-chat-grupos-app" class="cg-app" data-user-id="<?php echo get_current_user_id(); ?>">
            <div class="cg-sidebar">
                <div class="cg-sidebar-header">
                    <h3><?php _e('Mis Grupos', 'flavor-chat-ia'); ?></h3>
                    <button class="cg-btn-crear" title="<?php esc_attr_e('Crear grupo', 'flavor-chat-ia'); ?>">
                        <span class="dashicons dashicons-plus-alt2"></span>
                    </button>
                </div>
                <div class="cg-grupos-lista" id="cg-mis-grupos">
                    <div class="cg-loading"><?php _e('Cargando...', 'flavor-chat-ia'); ?></div>
                </div>
                <div class="cg-sidebar-footer">
                    <a href="#explorar" class="cg-link-explorar">
                        <span class="dashicons dashicons-search"></span>
                        <?php _e('Explorar grupos', 'flavor-chat-ia'); ?>
                    </a>
                </div>
            </div>
            <div class="cg-main">
                <div class="cg-no-grupo-seleccionado">
                    <span class="dashicons dashicons-format-chat"></span>
                    <h3><?php _e('Selecciona un grupo', 'flavor-chat-ia'); ?></h3>
                    <p><?php _e('Elige un grupo de la lista para ver los mensajes', 'flavor-chat-ia'); ?></p>
                </div>
                <div class="cg-chat-container" style="display:none;">
                    <div class="cg-chat-header">
                        <div class="cg-grupo-info">
                            <div class="cg-grupo-avatar"></div>
                            <div class="cg-grupo-datos">
                                <h4 class="cg-grupo-nombre"></h4>
                                <span class="cg-grupo-miembros"></span>
                            </div>
                        </div>
                        <div class="cg-chat-acciones">
                            <button class="cg-btn-buscar" title="<?php esc_attr_e('Buscar', 'flavor-chat-ia'); ?>">
                                <span class="dashicons dashicons-search"></span>
                            </button>
                            <button class="cg-btn-info" title="<?php esc_attr_e('Info del grupo', 'flavor-chat-ia'); ?>">
                                <span class="dashicons dashicons-info"></span>
                            </button>
                        </div>
                    </div>
                    <div class="cg-mensajes-container" id="cg-mensajes">
                        <div class="cg-loading"><?php _e('Cargando mensajes...', 'flavor-chat-ia'); ?></div>
                    </div>
                    <div class="cg-escribiendo" style="display:none;"></div>
                    <div class="cg-input-container">
                        <button class="cg-btn-adjuntar" title="<?php esc_attr_e('Adjuntar archivo', 'flavor-chat-ia'); ?>">
                            <span class="dashicons dashicons-paperclip"></span>
                        </button>
                        <div class="cg-input-wrapper">
                            <textarea id="cg-mensaje-input" placeholder="<?php esc_attr_e('Escribe un mensaje...', 'flavor-chat-ia'); ?>" rows="1"></textarea>
                        </div>
                        <button class="cg-btn-enviar" title="<?php esc_attr_e('Enviar', 'flavor-chat-ia'); ?>">
                            <span class="dashicons dashicons-arrow-right-alt"></span>
                        </button>
                    </div>
                </div>
            </div>
            <div class="cg-panel-info" style="display:none;">
                <!-- Panel lateral de info del grupo -->
            </div>
        </div>
        <?php if ($grupo_id_inicial > 0): ?>
        <script>
        document.addEventListener('DOMContentLoaded', function() {
            if (window.FlavorChatGrupos && typeof window.FlavorChatGrupos.abrirGrupo === 'function') {
                window.FlavorChatGrupos.abrirGrupo(<?php echo intval($grupo_id_inicial); ?>);
            }
        });
        </script>
        <?php endif; ?>
        <?php
        return ob_get_clean();
    }

    public function shortcode_chat_grupo($atts) {
        $atts = shortcode_atts([
            'id' => 0,
            'slug' => '',
            'embebido' => 0,
            'altura' => '500px',
        ], $atts);

        $grupo_id = intval($atts['id']);
        if (!$grupo_id && $atts['slug']) {
            global $wpdb;
            $grupo_id = $wpdb->get_var($wpdb->prepare(
                "SELECT id FROM {$wpdb->prefix}flavor_chat_grupos WHERE slug = %s",
                sanitize_title($atts['slug'])
            ));
        }

        if (!$grupo_id) {
            return '<p>' . __('Grupo no encontrado.', 'flavor-chat-ia') . '</p>';
        }

        if (!is_user_logged_in()) {
            return '<div class="cg-login-required"><p>' . __('Inicia sesión para acceder al chat.', 'flavor-chat-ia') . '</p></div>';
        }

        // Modo embebido: interfaz compacta sin sidebar
        if ($atts['embebido']) {
            return $this->render_chat_embebido($grupo_id, $atts['altura']);
        }

        // Modo normal: carga el chat completo y abre el grupo
        return $this->shortcode_chat_grupos([]) . "<script>document.addEventListener('DOMContentLoaded', function() { if(window.FlavorChatGrupos) FlavorChatGrupos.abrirGrupo({$grupo_id}); });</script>";
    }

    /**
     * Shortcode: Chat integrado para tabs de otros módulos
     *
     * Muestra un chat asociado a una entidad específica.
     * Si no existe el grupo de chat, lo crea automáticamente.
     *
     * @param array $atts Atributos del shortcode
     * @return string HTML
     */
    public function shortcode_chat_integrado($atts) {
        $atts = shortcode_atts([
            'entidad'    => '',
            'entidad_id' => 0,
            'altura'     => '450px',
        ], $atts);

        if (!is_user_logged_in()) {
            return '<div class="cg-login-required"><p>' . __('Inicia sesión para acceder al chat.', 'flavor-chat-ia') . '</p></div>';
        }

        $entidad_tipo = sanitize_key($atts['entidad']);
        $entidad_id = absint($atts['entidad_id']);

        if (!$entidad_tipo || !$entidad_id) {
            return '<p class="cg-aviso">' . __('Configuración del chat incompleta.', 'flavor-chat-ia') . '</p>';
        }

        global $wpdb;
        $tabla_grupos = $wpdb->prefix . 'flavor_chat_grupos';

        // Buscar grupo existente para esta entidad
        $grupo_id = $wpdb->get_var($wpdb->prepare(
            "SELECT id FROM {$tabla_grupos}
             WHERE entidad_tipo = %s AND entidad_id = %d AND estado = 'activo'",
            $entidad_tipo,
            $entidad_id
        ));

        // Si no existe, intentar crear automáticamente
        if (!$grupo_id) {
            $grupo_id = $this->crear_grupo_para_entidad($entidad_tipo, $entidad_id);
        }

        if (!$grupo_id) {
            return '<div class="cg-no-grupo">
                <span class="dashicons dashicons-format-status"></span>
                <p>' . __('El chat de este grupo aún no está disponible.', 'flavor-chat-ia') . '</p>
            </div>';
        }

        // Verificar/añadir usuario como miembro
        $this->asegurar_membresia_entidad($grupo_id, get_current_user_id(), $entidad_tipo, $entidad_id);

        // Usar el render embebido existente
        return $this->render_chat_embebido($grupo_id, $atts['altura']);
    }

    /**
     * Crea un grupo de chat para una entidad
     *
     * @param string $entidad_tipo Tipo de entidad
     * @param int    $entidad_id   ID de la entidad
     * @return int|false ID del grupo creado o false
     */
    private function crear_grupo_para_entidad($entidad_tipo, $entidad_id) {
        global $wpdb;

        // Obtener nombre de la entidad
        $nombre_grupo = $this->obtener_nombre_entidad($entidad_tipo, $entidad_id);
        if (!$nombre_grupo) {
            return false;
        }

        $tabla_grupos = $wpdb->prefix . 'flavor_chat_grupos';

        $resultado = $wpdb->insert($tabla_grupos, [
            'nombre'        => sprintf(__('Chat: %s', 'flavor-chat-ia'), $nombre_grupo),
            'slug'          => sanitize_title($entidad_tipo . '-' . $entidad_id),
            'descripcion'   => sprintf(__('Grupo de chat para %s', 'flavor-chat-ia'), $nombre_grupo),
            'tipo'          => 'entidad',
            'privacidad'    => 'miembros',
            'entidad_tipo'  => $entidad_tipo,
            'entidad_id'    => $entidad_id,
            'creador_id'    => get_current_user_id() ?: 1,
            'estado'        => 'activo',
            'fecha_creacion' => current_time('mysql'),
        ]);

        return $resultado ? $wpdb->insert_id : false;
    }

    /**
     * Obtiene el nombre de una entidad
     */
    private function obtener_nombre_entidad($tipo, $id) {
        global $wpdb;

        $tablas = [
            'grupo_consumo' => ['tabla' => 'flavor_grupos_consumo', 'campo' => 'nombre'],
            'evento'        => ['tabla' => 'flavor_eventos', 'campo' => 'titulo'],
            'comunidad'     => ['tabla' => 'flavor_comunidades', 'campo' => 'nombre'],
            'incidencia'    => ['tabla' => 'flavor_incidencias', 'campo' => 'titulo'],
            'documento_legal' => ['tabla' => 'flavor_documentacion_legal', 'campo' => 'titulo'],
            'pp_proyecto' => ['tabla' => 'flavor_pp_proyectos', 'campo' => 'titulo'],
            'saber_ancestral' => ['tabla' => null, 'campo' => 'post_title'],
            'documento_transparencia' => ['tabla' => 'flavor_transparencia_documentos_publicos', 'campo' => 'titulo'],
            'aviso_municipal' => ['tabla' => 'flavor_avisos_municipales', 'campo' => 'titulo'],
            'economia_don' => ['tabla' => 'flavor_economia_dones', 'campo' => 'titulo'],
            'energia_comunidad' => ['tabla' => 'flavor_energia_comunidades', 'campo' => 'nombre'],
            'curso'         => ['tabla' => 'flavor_cursos', 'campo' => 'titulo'],
            'taller'        => ['tabla' => 'flavor_talleres', 'campo' => 'titulo'],
            'libro_biblioteca' => ['tabla' => 'flavor_biblioteca_libros', 'campo' => 'titulo'],
            'marketplace_anuncio' => ['tabla' => 'flavor_marketplace_anuncios', 'campo' => 'titulo'],
            'advertising_ad' => ['tabla' => null, 'campo' => 'post_title'],
            'radio_programa' => ['tabla' => 'flavor_radio_programas', 'campo' => 'nombre'],
            'trabajo_digno_oferta' => ['tabla' => null, 'campo' => 'post_title'],
            'participacion_propuesta' => ['tabla' => 'flavor_propuestas', 'campo' => 'titulo'],
            'es_recurso'     => ['tabla' => null, 'campo' => 'post_title'],
            'jr_proceso'     => ['tabla' => null, 'campo' => 'post_title'],
            'huerto'         => ['tabla' => 'flavor_huertos', 'campo' => 'nombre'],
            'colectivo'     => ['tabla' => 'flavor_colectivos', 'campo' => 'nombre'],
            'circulo'       => ['tabla' => 'flavor_circulos_cuidados', 'campo' => 'nombre'],
        ];

        if (in_array($tipo, ['trabajo_digno_oferta', 'es_recurso', 'jr_proceso', 'saber_ancestral', 'advertising_ad'], true)) {
            $post = get_post($id);
            return ($post && in_array($post->post_type, ['td_oferta', 'es_recurso', 'jr_proceso', 'sa_saber', 'flavor_ad'], true))
                ? $post->post_title
                : ucfirst(str_replace('_', ' ', $tipo)) . ' #' . $id;
        }

        if (!isset($tablas[$tipo])) {
            return ucfirst(str_replace('_', ' ', $tipo)) . ' #' . $id;
        }

        $config = $tablas[$tipo];
        $tabla_completa = $wpdb->prefix . $config['tabla'];

        return $wpdb->get_var($wpdb->prepare(
            "SELECT {$config['campo']} FROM {$tabla_completa} WHERE id = %d",
            $id
        )) ?: ucfirst(str_replace('_', ' ', $tipo)) . ' #' . $id;
    }

    /**
     * Asegura que el usuario sea miembro del grupo (si pertenece a la entidad)
     */
    private function asegurar_membresia_entidad($grupo_id, $usuario_id, $entidad_tipo, $entidad_id) {
        global $wpdb;
        $tabla_miembros = $wpdb->prefix . 'flavor_chat_grupos_miembros';

        // Verificar si ya es miembro
        $existe = $wpdb->get_var($wpdb->prepare(
            "SELECT id FROM {$tabla_miembros} WHERE grupo_id = %d AND usuario_id = %d",
            $grupo_id,
            $usuario_id
        ));

        if ($existe) {
            return true;
        }

        // Verificar si el usuario pertenece a la entidad antes de añadirlo
        if (!$this->usuario_pertenece_a_entidad($usuario_id, $entidad_tipo, $entidad_id)) {
            return false;
        }

        $wpdb->insert($tabla_miembros, [
            'grupo_id'       => $grupo_id,
            'usuario_id'     => $usuario_id,
            'rol'            => 'miembro',
            'fecha_union'    => current_time('mysql'),
        ]);

        return true;
    }

    /**
     * Verifica si un usuario pertenece a una entidad
     *
     * @param int    $usuario_id   ID del usuario
     * @param string $entidad_tipo Tipo de entidad (comunidad, grupo_consumo, colectivo, etc.)
     * @param int    $entidad_id   ID de la entidad
     * @return bool
     */
    private function usuario_pertenece_a_entidad($usuario_id, $entidad_tipo, $entidad_id) {
        global $wpdb;

        // Mapeo de tipos de entidad a tablas de membresía
        $tablas_membresia = [
            'comunidad'      => ['tabla' => 'flavor_comunidades_miembros', 'campo_entidad' => 'comunidad_id'],
            'grupo_consumo'  => ['tabla' => 'flavor_gc_miembros', 'campo_entidad' => 'grupo_id'],
            'colectivo'      => ['tabla' => 'flavor_colectivos_miembros', 'campo_entidad' => 'colectivo_id'],
            'circulo'        => ['tabla' => 'flavor_circulos_miembros', 'campo_entidad' => 'circulo_id'],
            'huerto'         => ['tabla' => 'flavor_huertos_parcelas', 'campo_entidad' => 'huerto_id'],
        ];

        if (!isset($tablas_membresia[$entidad_tipo])) {
            // Si no hay tabla de membresía definida, permitir (compatibilidad)
            return true;
        }

        $config = $tablas_membresia[$entidad_tipo];
        $tabla = $wpdb->prefix . $config['tabla'];
        $campo_entidad = $config['campo_entidad'];

        // Verificar si la tabla existe
        if ($wpdb->get_var("SHOW TABLES LIKE '{$tabla}'") !== $tabla) {
            return true; // Tabla no existe, permitir por compatibilidad
        }

        // Verificar membresía
        $es_miembro = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM {$tabla} WHERE {$campo_entidad} = %d AND usuario_id = %d",
            $entidad_id,
            $usuario_id
        ));

        return $es_miembro > 0;
    }

    /**
     * Renderiza un chat de grupo en modo embebido (sin sidebar)
     *
     * @param int    $grupo_id ID del grupo
     * @param string $altura   Altura del contenedor CSS
     * @return string HTML del chat embebido
     */
    private function render_chat_embebido($grupo_id, $altura) {
        global $wpdb;
        $tabla_grupos = $wpdb->prefix . 'flavor_chat_grupos';
        $tabla_miembros = $wpdb->prefix . 'flavor_chat_grupos_miembros';

        $grupo = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM $tabla_grupos WHERE id = %d AND estado = 'activo'",
            $grupo_id
        ));

        if (!$grupo) {
            return '<p>' . __('Grupo no disponible.', 'flavor-chat-ia') . '</p>';
        }

        // Verificar membresía
        $usuario_id = get_current_user_id();
        $es_miembro = $wpdb->get_var($wpdb->prepare(
            "SELECT id FROM $tabla_miembros WHERE grupo_id = %d AND usuario_id = %d",
            $grupo_id, $usuario_id
        ));

        if (!$es_miembro) {
            return '<div class="cg-no-acceso"><p>' . __('No tienes acceso a este chat.', 'flavor-chat-ia') . '</p></div>';
        }

        $altura_css = esc_attr($altura);

        ob_start();
        ?>
        <div class="cg-embebido" data-grupo-id="<?php echo esc_attr($grupo_id); ?>" data-user-id="<?php echo esc_attr($usuario_id); ?>" style="height: <?php echo $altura_css; ?>;">
            <div class="cg-embebido-header">
                <div class="cg-grupo-avatar">
                    <?php if ($grupo->imagen): ?>
                        <img src="<?php echo esc_url($grupo->imagen); ?>" alt="">
                    <?php else: ?>
                        <span class="dashicons dashicons-groups"></span>
                    <?php endif; ?>
                </div>
                <div class="cg-grupo-datos">
                    <h4 class="cg-grupo-nombre"><?php echo esc_html($grupo->nombre); ?></h4>
                    <span class="cg-grupo-miembros"><?php printf(__('%d miembros', 'flavor-chat-ia'), $grupo->miembros_count); ?></span>
                </div>
                <div class="cg-embebido-acciones">
                    <button class="cg-btn-buscar-emb" title="<?php esc_attr_e('Buscar', 'flavor-chat-ia'); ?>">
                        <span class="dashicons dashicons-search"></span>
                    </button>
                </div>
            </div>
            <div class="cg-embebido-mensajes" id="cg-emb-mensajes-<?php echo esc_attr($grupo_id); ?>">
                <div class="cg-loading"><?php _e('Cargando mensajes...', 'flavor-chat-ia'); ?></div>
            </div>
            <div class="cg-embebido-escribiendo" style="display:none;"></div>
            <div class="cg-embebido-input">
                <button class="cg-btn-adjuntar-emb" title="<?php esc_attr_e('Adjuntar', 'flavor-chat-ia'); ?>">
                    <span class="dashicons dashicons-paperclip"></span>
                </button>
                <div class="cg-input-wrapper">
                    <textarea class="cg-emb-mensaje-input" placeholder="<?php esc_attr_e('Escribe un mensaje...', 'flavor-chat-ia'); ?>" rows="1"></textarea>
                </div>
                <button class="cg-btn-enviar-emb" title="<?php esc_attr_e('Enviar', 'flavor-chat-ia'); ?>">
                    <span class="dashicons dashicons-arrow-right-alt"></span>
                </button>
            </div>
        </div>
        <script>
        document.addEventListener('DOMContentLoaded', function() {
            if (window.FlavorChatGruposEmbebido) {
                FlavorChatGruposEmbebido.init(<?php echo $grupo_id; ?>);
            } else if (window.FlavorChatGrupos) {
                // Fallback: usar el controlador principal si existe
                FlavorChatGrupos.initEmbebido(<?php echo $grupo_id; ?>);
            }
        });
        </script>
        <?php
        return ob_get_clean();
    }

    public function shortcode_grupos_lista($atts) {
        $atts = shortcode_atts([
            'limite' => 6,
            'categoria' => '',
            'columnas' => 3,
        ], $atts);

        $resultado = $this->action_grupos_publicos([
            'categoria' => $atts['categoria'],
            'pagina' => 1,
        ]);

        if (!$resultado['success'] || empty($resultado['grupos'])) {
            return '<p>' . __('No hay grupos disponibles.', 'flavor-chat-ia') . '</p>';
        }

        $grupos = array_slice($resultado['grupos'], 0, intval($atts['limite']));

        ob_start();
        ?>
        <div class="cg-grupos-grid columnas-<?php echo intval($atts['columnas']); ?>">
            <?php foreach ($grupos as $grupo): ?>
            <div class="cg-grupo-card">
                <div class="cg-grupo-card-header" style="background-color: <?php echo esc_attr($grupo['color']); ?>">
                    <?php if ($grupo['imagen_url']): ?>
                        <img src="<?php echo esc_url($grupo['imagen_url']); ?>" alt="">
                    <?php endif; ?>
                </div>
                <div class="cg-grupo-card-body">
                    <h4><?php echo esc_html($grupo['nombre']); ?></h4>
                    <p><?php echo esc_html($grupo['descripcion']); ?></p>
                    <div class="cg-grupo-card-meta">
                        <span><span class="dashicons dashicons-groups"></span> <?php echo $grupo['miembros']; ?></span>
                        <span><span class="dashicons dashicons-admin-comments"></span> <?php echo $grupo['mensajes']; ?></span>
                    </div>
                </div>
                <div class="cg-grupo-card-footer">
                    <?php if ($grupo['es_miembro']): ?>
                        <a href="<?php echo esc_url(home_url('/mi-portal/chat-grupos/mensajes/?grupo_id=' . intval($grupo['id']))); ?>" class="cg-btn cg-btn-primary"><?php _e('Abrir', 'flavor-chat-ia'); ?></a>
                    <?php else: ?>
                        <button class="cg-btn cg-btn-outline cg-btn-unirse" data-id="<?php echo $grupo['id']; ?>"><?php _e('Unirse', 'flavor-chat-ia'); ?></button>
                    <?php endif; ?>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
        <?php
        return ob_get_clean();
    }

    /**
     * Shortcode para mostrar grupos activos (con actividad reciente)
     * Uso: [chat_grupos_activos limit="4" columnas="2"]
     */
    public function shortcode_grupos_activos($atts) {
        $atts = shortcode_atts([
            'limit' => 4,
            'columnas' => 2,
            'mostrar_actividad' => true,
        ], $atts);

        global $wpdb;
        $tabla_grupos = $wpdb->prefix . 'flavor_chat_grupos';
        $tabla_mensajes = $wpdb->prefix . 'flavor_chat_grupos_mensajes';
        $tabla_miembros = $wpdb->prefix . 'flavor_chat_grupos_miembros';

        // Obtener grupos con actividad reciente (últimos 7 días)
        $limite = intval($atts['limit']);
        $grupos_activos = $wpdb->get_results($wpdb->prepare("
            SELECT g.*,
                   COUNT(DISTINCT m.id) as mensajes_recientes,
                   MAX(m.created_at) as ultimo_mensaje,
                   (SELECT COUNT(*) FROM {$tabla_miembros} WHERE grupo_id = g.id) as total_miembros
            FROM {$tabla_grupos} g
            LEFT JOIN {$tabla_mensajes} m ON g.id = m.grupo_id AND m.created_at > DATE_SUB(NOW(), INTERVAL 7 DAY)
            WHERE g.estado = 'activo' AND g.tipo = 'publico'
            GROUP BY g.id
            HAVING mensajes_recientes > 0 OR ultimo_mensaje IS NOT NULL
            ORDER BY mensajes_recientes DESC, g.miembros_count DESC
            LIMIT %d
        ", $limite));

        // Si no hay grupos activos, mostrar los más populares
        if (empty($grupos_activos)) {
            $grupos_activos = $wpdb->get_results($wpdb->prepare("
                SELECT g.*, 0 as mensajes_recientes, NULL as ultimo_mensaje,
                       (SELECT COUNT(*) FROM {$tabla_miembros} WHERE grupo_id = g.id) as total_miembros
                FROM {$tabla_grupos} g
                WHERE g.estado = 'activo' AND g.tipo = 'publico'
                ORDER BY g.miembros_count DESC
                LIMIT %d
            ", $limite));
        }

        if (empty($grupos_activos)) {
            return '<p class="cg-no-grupos">' . __('No hay grupos activos disponibles.', 'flavor-chat-ia') . '</p>';
        }

        $usuario_id = get_current_user_id();
        $tabla_miembros_check = $wpdb->prefix . 'flavor_chat_grupos_miembros';

        ob_start();
        ?>
        <div class="cg-grupos-activos cg-grupos-grid columnas-<?php echo intval($atts['columnas']); ?>">
            <?php foreach ($grupos_activos as $grupo):
                $es_miembro = false;
                if ($usuario_id) {
                    $es_miembro = $wpdb->get_var($wpdb->prepare(
                        "SELECT COUNT(*) FROM {$tabla_miembros_check} WHERE grupo_id = %d AND usuario_id = %d",
                        $grupo->id, $usuario_id
                    )) > 0;
                }
                $tiempo_ultimo = $grupo->ultimo_mensaje ? human_time_diff(strtotime($grupo->ultimo_mensaje)) : '';
            ?>
            <div class="cg-grupo-card cg-grupo-activo">
                <div class="cg-grupo-card-header" style="background-color: <?php echo esc_attr($grupo->color ?: '#3b82f6'); ?>">
                    <?php if (!empty($grupo->imagen)): ?>
                        <img src="<?php echo esc_url($grupo->imagen); ?>" alt="">
                    <?php else: ?>
                        <span class="cg-grupo-icono"><?php echo esc_html(mb_substr($grupo->nombre, 0, 1)); ?></span>
                    <?php endif; ?>
                    <?php if ($grupo->mensajes_recientes > 0): ?>
                        <span class="cg-badge-activo" title="<?php esc_attr_e('Actividad reciente', 'flavor-chat-ia'); ?>">
                            <span class="dashicons dashicons-format-chat"></span>
                            <?php echo intval($grupo->mensajes_recientes); ?>
                        </span>
                    <?php endif; ?>
                </div>
                <div class="cg-grupo-card-body">
                    <h4><?php echo esc_html($grupo->nombre); ?></h4>
                    <?php if (!empty($grupo->descripcion)): ?>
                        <p><?php echo esc_html(wp_trim_words($grupo->descripcion, 12)); ?></p>
                    <?php endif; ?>
                    <div class="cg-grupo-card-meta">
                        <span><span class="dashicons dashicons-groups"></span> <?php echo intval($grupo->total_miembros ?: $grupo->miembros); ?></span>
                        <?php if ($tiempo_ultimo && $atts['mostrar_actividad']): ?>
                            <span class="cg-tiempo-actividad"><span class="dashicons dashicons-clock"></span> <?php echo esc_html($tiempo_ultimo); ?></span>
                        <?php endif; ?>
                    </div>
                </div>
                <div class="cg-grupo-card-footer">
                    <?php if ($es_miembro): ?>
                        <a href="<?php echo esc_url(home_url('/mi-portal/chat-grupos/mensajes/?grupo_id=' . intval($grupo->id))); ?>" class="cg-btn cg-btn-primary"><?php _e('Abrir', 'flavor-chat-ia'); ?></a>
                    <?php elseif (is_user_logged_in()): ?>
                        <button class="cg-btn cg-btn-outline cg-btn-unirse" data-id="<?php echo intval($grupo->id); ?>"><?php _e('Unirse', 'flavor-chat-ia'); ?></button>
                    <?php else: ?>
                        <a href="<?php echo esc_url(wp_login_url(get_permalink())); ?>" class="cg-btn cg-btn-outline"><?php _e('Inicia sesión', 'flavor-chat-ia'); ?></a>
                    <?php endif; ?>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
        <?php
        return ob_get_clean();
    }

    public function shortcode_grupos_explorar($atts) {
        ob_start();
        ?>
        <div id="cg-explorar" class="cg-explorar">
            <div class="cg-explorar-header">
                <h2><?php _e('Explorar Grupos', 'flavor-chat-ia'); ?></h2>
                <div class="cg-explorar-busqueda">
                    <input type="text" id="cg-buscar-grupos" placeholder="<?php esc_attr_e('Buscar grupos...', 'flavor-chat-ia'); ?>">
                    <select id="cg-filtro-categoria">
                        <option value=""><?php _e('Todas las categorías', 'flavor-chat-ia'); ?></option>
                        <option value="<?php echo esc_attr__('general', 'flavor-chat-ia'); ?>"><?php _e('General', 'flavor-chat-ia'); ?></option>
                        <option value="<?php echo esc_attr__('deportes', 'flavor-chat-ia'); ?>"><?php _e('Deportes', 'flavor-chat-ia'); ?></option>
                        <option value="<?php echo esc_attr__('cultura', 'flavor-chat-ia'); ?>"><?php _e('Cultura', 'flavor-chat-ia'); ?></option>
                        <option value="<?php echo esc_attr__('vecinal', 'flavor-chat-ia'); ?>"><?php _e('Vecinal', 'flavor-chat-ia'); ?></option>
                    </select>
                </div>
            </div>
            <div id="cg-explorar-resultados" class="cg-grupos-grid columnas-3">
                <div class="cg-loading"><?php _e('Cargando grupos...', 'flavor-chat-ia'); ?></div>
            </div>
            <div class="cg-explorar-paginacion" id="cg-paginacion"></div>
        </div>
        <?php
        return ob_get_clean();
    }

    public function shortcode_crear_grupo($atts) {
        if (!is_user_logged_in()) {
            return '<p>' . __('Inicia sesión para crear un grupo.', 'flavor-chat-ia') . '</p>';
        }

        $settings = $this->get_settings();
        if (!$settings['permite_crear_grupos']) {
            return '<p>' . __('La creación de grupos está deshabilitada.', 'flavor-chat-ia') . '</p>';
        }

        ob_start();
        ?>
        <div class="cg-crear-grupo-form">
            <h3><?php _e('Crear Nuevo Grupo', 'flavor-chat-ia'); ?></h3>
            <form id="cg-form-crear">
                <div class="cg-form-field">
                    <label for="cg-nombre"><?php _e('Nombre del grupo', 'flavor-chat-ia'); ?> *</label>
                    <input type="text" id="cg-nombre" name="nombre" required minlength="3" maxlength="100">
                </div>
                <div class="cg-form-field">
                    <label for="cg-descripcion"><?php _e('Descripción', 'flavor-chat-ia'); ?></label>
                    <textarea id="cg-descripcion" name="descripcion" rows="3" maxlength="500"></textarea>
                </div>
                <div class="cg-form-row">
                    <div class="cg-form-field">
                        <label for="cg-tipo"><?php _e('Tipo', 'flavor-chat-ia'); ?></label>
                        <select id="cg-tipo" name="tipo">
                            <option value="<?php echo esc_attr__('publico', 'flavor-chat-ia'); ?>"><?php _e('Público', 'flavor-chat-ia'); ?></option>
                            <?php if ($settings['permite_grupos_privados']): ?>
                            <option value="<?php echo esc_attr__('privado', 'flavor-chat-ia'); ?>"><?php _e('Privado', 'flavor-chat-ia'); ?></option>
                            <option value="<?php echo esc_attr__('secreto', 'flavor-chat-ia'); ?>"><?php _e('Secreto', 'flavor-chat-ia'); ?></option>
                            <?php endif; ?>
                        </select>
                    </div>
                    <div class="cg-form-field">
                        <label for="cg-categoria"><?php _e('Categoría', 'flavor-chat-ia'); ?></label>
                        <select id="cg-categoria" name="categoria">
                            <option value=""><?php _e('Sin categoría', 'flavor-chat-ia'); ?></option>
                            <option value="<?php echo esc_attr__('general', 'flavor-chat-ia'); ?>"><?php _e('General', 'flavor-chat-ia'); ?></option>
                            <option value="<?php echo esc_attr__('deportes', 'flavor-chat-ia'); ?>"><?php _e('Deportes', 'flavor-chat-ia'); ?></option>
                            <option value="<?php echo esc_attr__('cultura', 'flavor-chat-ia'); ?>"><?php _e('Cultura', 'flavor-chat-ia'); ?></option>
                            <option value="<?php echo esc_attr__('vecinal', 'flavor-chat-ia'); ?>"><?php _e('Vecinal', 'flavor-chat-ia'); ?></option>
                        </select>
                    </div>
                </div>
                <div class="cg-form-field">
                    <label for="cg-color"><?php _e('Color', 'flavor-chat-ia'); ?></label>
                    <input type="color" id="cg-color" name="color" value="<?php echo esc_attr__('#2271b1', 'flavor-chat-ia'); ?>">
                </div>
                <div class="cg-form-actions">
                    <button type="submit" class="cg-btn cg-btn-primary"><?php _e('Crear Grupo', 'flavor-chat-ia'); ?></button>
                </div>
            </form>
        </div>
        <?php
        return ob_get_clean();
    }

    /**
     * Shortcode: Grupos con mensajes sin leer (widget)
     * Muestra la cantidad de grupos con mensajes pendientes
     */
    public function shortcode_sin_leer($atts) {
        if (!is_user_logged_in()) {
            return '';
        }

        $atts = shortcode_atts([
            'mostrar_lista' => 'true',
            'limite' => 3,
        ], $atts);

        global $wpdb;
        $tabla_grupos = $wpdb->prefix . 'flavor_chat_grupos';
        $tabla_miembros = $wpdb->prefix . 'flavor_chat_grupos_miembros';
        $tabla_mensajes = $wpdb->prefix . 'flavor_chat_grupos_mensajes';
        $usuario_id = get_current_user_id();

        if (!Flavor_Chat_Helpers::tabla_existe($tabla_grupos)) {
            return '';
        }

        // Obtener grupos con mensajes sin leer
        $limite = intval($atts['limite']);
        $grupos_sin_leer = $wpdb->get_results($wpdb->prepare(
            "SELECT g.id, g.nombre, g.color,
                    (SELECT COUNT(*) FROM $tabla_mensajes m
                     WHERE m.grupo_id = g.id
                     AND m.fecha_creacion > COALESCE(mem.ultima_lectura, '1970-01-01')
                     AND m.usuario_id != %d) as sin_leer
             FROM $tabla_grupos g
             INNER JOIN $tabla_miembros mem ON g.id = mem.grupo_id AND mem.usuario_id = %d
             HAVING sin_leer > 0
             ORDER BY sin_leer DESC
             LIMIT %d",
            $usuario_id,
            $usuario_id,
            $limite
        ));

        $total_sin_leer = array_sum(wp_list_pluck($grupos_sin_leer, 'sin_leer'));

        if (empty($grupos_sin_leer)) {
            return '';
        }

        ob_start();
        ?>
        <div class="chat-grupos-sin-leer">
            <div class="sin-leer-badge">
                <span class="badge-numero"><?php echo esc_html($total_sin_leer); ?></span>
                <span class="badge-texto"><?php esc_html_e('mensajes sin leer', 'flavor-chat-ia'); ?></span>
            </div>

            <?php if ($atts['mostrar_lista'] === 'true'): ?>
            <ul class="grupos-sin-leer-lista">
                <?php foreach ($grupos_sin_leer as $grupo): ?>
                <li>
                    <a href="<?php echo esc_url(home_url('/mi-portal/chat-grupos/mensajes/?grupo_id=' . $grupo->id)); ?>">
                        <span class="grupo-color" style="background:<?php echo esc_attr($grupo->color ?: '#2271b1'); ?>"></span>
                        <span class="grupo-nombre"><?php echo esc_html($grupo->nombre); ?></span>
                        <span class="grupo-sin-leer"><?php echo esc_html($grupo->sin_leer); ?></span>
                    </a>
                </li>
                <?php endforeach; ?>
            </ul>
            <?php endif; ?>
        </div>
        <?php
        return ob_get_clean();
    }

    /**
     * Shortcode: Total mensajes sin leer (badge simple)
     * Muestra solo el número de mensajes pendientes
     */
    public function shortcode_mensajes_sin_leer($atts) {
        if (!is_user_logged_in()) {
            return '';
        }

        $atts = shortcode_atts([
            'formato' => 'badge', // badge, texto, numero
        ], $atts);

        global $wpdb;
        $tabla_grupos = $wpdb->prefix . 'flavor_chat_grupos';
        $tabla_miembros = $wpdb->prefix . 'flavor_chat_grupos_miembros';
        $tabla_mensajes = $wpdb->prefix . 'flavor_chat_grupos_mensajes';
        $usuario_id = get_current_user_id();

        if (!Flavor_Chat_Helpers::tabla_existe($tabla_mensajes)) {
            return '';
        }

        // Contar todos los mensajes sin leer
        $total = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*)
             FROM $tabla_mensajes m
             INNER JOIN $tabla_miembros mem ON m.grupo_id = mem.grupo_id AND mem.usuario_id = %d
             WHERE m.fecha_creacion > COALESCE(mem.ultima_lectura, '1970-01-01')
             AND m.usuario_id != %d",
            $usuario_id,
            $usuario_id
        ));

        $total = intval($total);

        if ($total === 0) {
            return '';
        }

        switch ($atts['formato']) {
            case 'numero':
                return esc_html($total);

            case 'texto':
                return sprintf(esc_html__('%d mensajes sin leer', 'flavor-chat-ia'), $total);

            case 'badge':
            default:
                return '<span class="chat-mensajes-badge">' . esc_html($total) . '</span>';
        }
    }

    // ==================== Dashboard Integration ====================

    public function add_dashboard_tab($tabs) {
        $tabs['chat-grupos'] = [
            'label' => __('Mis Grupos', 'flavor-chat-ia'),
            'icon' => 'format-chat',
            'callback' => [$this, 'render_dashboard_tab'],
            'orden' => 30,
        ];
        return $tabs;
    }

    public function render_dashboard_tab() {
        echo do_shortcode('[flavor_chat_grupos]');
    }

    // ==================== Helper Methods ====================

    private function usuario_puede_ver_grupo($usuario_id, $grupo_id) {
        global $wpdb;
        $tabla_grupos = $wpdb->prefix . 'flavor_chat_grupos';
        $tabla_miembros = $wpdb->prefix . 'flavor_chat_grupos_miembros';

        $grupo = $wpdb->get_row($wpdb->prepare(
            "SELECT tipo FROM $tabla_grupos WHERE id = %d AND estado = 'activo'",
            $grupo_id
        ));

        if (!$grupo) return false;

        // Grupos públicos pueden ser vistos por todos
        if ($grupo->tipo === 'publico') return true;

        // Para privados/secretos, debe ser miembro
        if (!$usuario_id) return false;

        return $wpdb->get_var($wpdb->prepare(
            "SELECT id FROM $tabla_miembros WHERE grupo_id = %d AND usuario_id = %d",
            $grupo_id, $usuario_id
        )) > 0;
    }

    private function usuario_puede_escribir($usuario_id, $grupo_id) {
        if (!$usuario_id) return false;

        global $wpdb;
        $tabla_grupos = $wpdb->prefix . 'flavor_chat_grupos';
        $tabla_miembros = $wpdb->prefix . 'flavor_chat_grupos_miembros';

        $miembro = $wpdb->get_row($wpdb->prepare(
            "SELECT m.rol, g.solo_admins_publican
             FROM $tabla_miembros m
             JOIN $tabla_grupos g ON m.grupo_id = g.id
             WHERE m.grupo_id = %d AND m.usuario_id = %d AND g.estado = 'activo'",
            $grupo_id, $usuario_id
        ));

        if (!$miembro) return false;

        // Si solo admins pueden publicar
        if ($miembro->solo_admins_publican && !in_array($miembro->rol, ['admin', 'moderador'])) {
            return false;
        }

        return true;
    }

    private function usuario_es_admin($usuario_id, $grupo_id) {
        return $this->obtener_rol_usuario($usuario_id, $grupo_id) === 'admin';
    }

    private function usuario_es_admin_o_mod($usuario_id, $grupo_id) {
        $rol = $this->obtener_rol_usuario($usuario_id, $grupo_id);
        return in_array($rol, ['admin', 'moderador']);
    }

    private function obtener_rol_usuario($usuario_id, $grupo_id) {
        global $wpdb;
        return $wpdb->get_var($wpdb->prepare(
            "SELECT rol FROM {$wpdb->prefix}flavor_chat_grupos_miembros WHERE grupo_id = %d AND usuario_id = %d",
            $grupo_id, $usuario_id
        ));
    }

    private function formatear_mensaje($texto) {
        $texto = esc_html($texto);

        // URLs a links
        $texto = preg_replace(
            '/(https?:\/\/[^\s<]+)/i',
            '<a href="$1" target="_blank" rel="noopener">$1</a>',
            $texto
        );

        // Menciones
        $texto = preg_replace(
            '/@(\w+)/',
            '<span class="cg-mencion">@$1</span>',
            $texto
        );

        // Emojis básicos
        $emojis = [':)' => '😊', ':(' => '😢', ':D' => '😄', ';)' => '😉', '<3' => '❤️'];
        $texto = str_replace(array_keys($emojis), array_values($emojis), $texto);

        // Negrita **texto**
        $texto = preg_replace('/\*\*(.+?)\*\*/', '<strong>$1</strong>', $texto);

        // Cursiva _texto_
        $texto = preg_replace('/\_(.+?)\_/', '<em>$1</em>', $texto);

        // Saltos de línea
        $texto = nl2br($texto);

        return $texto;
    }

    private function tiempo_relativo($fecha) {
        $timestamp = strtotime($fecha);
        $diff = time() - $timestamp;

        if ($diff < 60) return __('ahora', 'flavor-chat-ia');
        if ($diff < 3600) return sprintf(__('hace %d min', 'flavor-chat-ia'), floor($diff / 60));
        if ($diff < 86400) return sprintf(__('hace %d h', 'flavor-chat-ia'), floor($diff / 3600));
        if ($diff < 172800) return __('ayer', 'flavor-chat-ia');
        if ($diff < 604800) return sprintf(__('hace %d días', 'flavor-chat-ia'), floor($diff / 86400));

        return date_i18n('j M', $timestamp);
    }

    private function usuario_online($usuario_id) {
        $last_activity = get_user_meta($usuario_id, 'last_activity', true);
        if (!$last_activity) return false;
        return (time() - strtotime($last_activity)) < 300; // 5 minutos
    }

    // ==================== Web Components ====================

    public function get_web_components() {
        return [
            'hero_chat_grupos' => [
                'label' => __('Hero Chat Grupos', 'flavor-chat-ia'),
                'category' => 'hero',
                'icon' => 'dashicons-format-chat',
                'fields' => [
                    'titulo' => ['type' => 'text', 'default' => __('Chat de Grupos', 'flavor-chat-ia')],
                    'subtitulo' => ['type' => 'textarea', 'default' => __('Comunícate en tiempo real', 'flavor-chat-ia')],
                ],
                'template' => 'chat-grupos/hero',
            ],
            'grupos_destacados' => [
                'label' => __('Grupos Destacados', 'flavor-chat-ia'),
                'category' => 'listings',
                'icon' => 'dashicons-groups',
                'fields' => [
                    'titulo' => ['type' => 'text', 'default' => __('Grupos Populares', 'flavor-chat-ia')],
                    'columnas' => ['type' => 'select', 'options' => [2, 3, 4], 'default' => 3],
                    'limite' => ['type' => 'number', 'default' => 6],
                ],
                'template' => 'chat-grupos/grupos-destacados',
            ],
        ];
    }

    public function get_tool_definitions() {
        return [
            [
                'name' => 'chat_grupos_mis_grupos',
                'description' => 'Ver mis grupos de chat',
                'input_schema' => ['type' => 'object', 'properties' => []],
            ],
        ];
    }

    public function get_knowledge_base() {
        return <<<KNOWLEDGE
**Chat de Grupos Comunitarios**

Sistema de mensajería grupal organizado por temas e intereses.

**Tipos de grupos:**
- Públicos: Cualquiera puede unirse
- Privados: Requieren invitación
- Secretos: Solo por invitación, no aparecen en búsqueda

**Funcionalidades:**
- Chat en tiempo real
- Menciones (@usuario)
- Respuestas a mensajes
- Reacciones con emojis
- Compartir archivos
- Encuestas
- Mensajes fijados
- Búsqueda de mensajes
KNOWLEDGE;
    }

    public function get_faqs() {
        return [
            ['pregunta' => '¿Cuántos grupos puedo crear?', 'respuesta' => 'No hay límite.'],
            ['pregunta' => '¿Los mensajes se guardan?', 'respuesta' => 'Sí, el historial se mantiene.'],
        ];
    }

    /**
     * Configuración para el Panel de Administración Unificado
     *
     * @return array
     */
    protected function get_admin_config() {
        return [
            'id' => 'chat_grupos',
            'label' => __('Chat de Grupos', 'flavor-chat-ia'),
            'icon' => 'dashicons-format-chat',
            'capability' => 'manage_options',
            'categoria' => 'comunicacion',
            'paginas' => [
                [
                    'slug' => 'flavor-chat-grupos-dashboard',
                    'titulo' => __('Dashboard', 'flavor-chat-ia'),
                    'callback' => [$this, 'render_admin_dashboard'],
                ],
                [
                    'slug' => 'flavor-chat-grupos-grupos',
                    'titulo' => __('Grupos', 'flavor-chat-ia'),
                    'callback' => [$this, 'render_admin_grupos'],
                    'badge' => [$this, 'contar_grupos_pendientes'],
                ],
                [
                    'slug' => 'flavor-chat-grupos-moderacion',
                    'titulo' => __('Moderación', 'flavor-chat-ia'),
                    'callback' => [$this, 'render_admin_moderacion'],
                    'badge' => [$this, 'contar_reportes_pendientes'],
                ],
            ],
            'dashboard_widget' => [$this, 'render_dashboard_widget'],
            'estadisticas' => [$this, 'get_estadisticas_admin'],
        ];
    }

    /**
     * Renderiza el dashboard de administración del módulo
     */
    public function render_admin_dashboard() {
        $is_dashboard_viewer = current_user_can('flavor_ver_dashboard') && !current_user_can('manage_options');
        $estadisticas = $this->get_estadisticas_admin();
        ?>
        <div class="wrap flavor-admin-page">
            <?php
            $acciones = $is_dashboard_viewer
                ? [
                    [
                        'label' => __('Ver en portal', 'flavor-chat-ia'),
                        'url' => home_url('/mi-portal/chat-grupos/'),
                        'class' => '',
                    ],
                ]
                : [
                    [
                        'label' => __('Gestionar grupos', 'flavor-chat-ia'),
                        'url' => admin_url('admin.php?page=flavor-chat-grupos-listado'),
                        'class' => 'button-primary',
                    ],
                    [
                        'label' => __('Moderación', 'flavor-chat-ia'),
                        'url' => admin_url('admin.php?page=flavor-chat-grupos-moderacion'),
                        'class' => '',
                    ],
                ];
            $this->render_page_header(__('Dashboard de Chat Grupos', 'flavor-chat-ia'), $acciones);
            if ($is_dashboard_viewer) :
            ?>
                <div class="notice notice-info"><p><?php esc_html_e('Vista resumida para gestor de grupos. La gestión detallada y la moderación siguen reservadas a administración.', 'flavor-chat-ia'); ?></p></div>
            <?php endif; ?>

            <div class="flavor-stats-grid">
                <div class="stat-card">
                    <span class="dashicons dashicons-groups"></span>
                    <div class="stat-value"><?php echo intval($estadisticas['total_grupos'] ?? 0); ?></div>
                    <div class="stat-label"><?php esc_html_e('Grupos activos', 'flavor-chat-ia'); ?></div>
                </div>
                <div class="stat-card">
                    <span class="dashicons dashicons-format-chat"></span>
                    <div class="stat-value"><?php echo intval($estadisticas['total_mensajes'] ?? 0); ?></div>
                    <div class="stat-label"><?php esc_html_e('Mensajes', 'flavor-chat-ia'); ?></div>
                </div>
                <div class="stat-card">
                    <span class="dashicons dashicons-admin-users"></span>
                    <div class="stat-value"><?php echo intval($estadisticas['total_miembros'] ?? 0); ?></div>
                    <div class="stat-label"><?php esc_html_e('Miembros', 'flavor-chat-ia'); ?></div>
                </div>
                <div class="stat-card">
                    <span class="dashicons dashicons-clock"></span>
                    <div class="stat-value"><?php echo intval($estadisticas['mensajes_hoy'] ?? 0); ?></div>
                    <div class="stat-label"><?php esc_html_e('Mensajes hoy', 'flavor-chat-ia'); ?></div>
                </div>
            </div>

            <div class="flavor-admin-section">
                <h2><?php esc_html_e('Resumen operativo', 'flavor-chat-ia'); ?></h2>
                <ul class="flavor-widget-stats">
                    <li><span class="label"><?php esc_html_e('Grupos pendientes:', 'flavor-chat-ia'); ?></span> <span class="value"><?php echo intval($this->contar_grupos_pendientes()); ?></span></li>
                    <li><span class="label"><?php esc_html_e('Reportes pendientes:', 'flavor-chat-ia'); ?></span> <span class="value"><?php echo intval($this->contar_reportes_pendientes()); ?></span></li>
                </ul>
            </div>
        </div>
        <?php
    }

    /**
     * Renderiza la gestión de grupos
     */
    public function render_admin_grupos() {
        include FLAVOR_CHAT_IA_PATH . 'includes/modules/chat-grupos/admin/views/grupos.php';
    }

    /**
     * Renderiza la página de moderación
     */
    public function render_admin_moderacion() {
        include FLAVOR_CHAT_IA_PATH . 'includes/modules/chat-grupos/admin/views/moderacion.php';
    }

    /**
     * Renderiza el widget del dashboard unificado
     */
    public function render_dashboard_widget() {
        $estadisticas = $this->get_estadisticas_admin();
        include FLAVOR_CHAT_IA_PATH . 'includes/modules/chat-grupos/admin/views/widget.php';
    }

    /**
     * Obtiene estadísticas para el panel de administración
     *
     * @return array
     */
    public function get_estadisticas_admin() {
        global $wpdb;
        $tabla_grupos = $wpdb->prefix . 'flavor_chat_grupos';
        $tabla_mensajes = $wpdb->prefix . 'flavor_chat_grupos_mensajes';
        $tabla_miembros = $wpdb->prefix . 'flavor_chat_grupos_miembros';

        $total_grupos = (int) $wpdb->get_var("SELECT COUNT(*) FROM $tabla_grupos WHERE estado = 'activo'");
        $total_mensajes = (int) $wpdb->get_var("SELECT COUNT(*) FROM $tabla_mensajes");
        $total_miembros = (int) $wpdb->get_var("SELECT COUNT(DISTINCT usuario_id) FROM $tabla_miembros");
        $mensajes_hoy = (int) $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM $tabla_mensajes WHERE DATE(fecha_creacion) = %s",
            current_time('Y-m-d')
        ));

        return [
            'total_grupos' => $total_grupos,
            'total_mensajes' => $total_mensajes,
            'total_miembros' => $total_miembros,
            'mensajes_hoy' => $mensajes_hoy,
        ];
    }

    /**
     * Cuenta grupos pendientes de aprobación
     *
     * @return int
     */
    public function contar_grupos_pendientes() {
        // Verificar que el módulo esté activo
        if (!$this->can_activate()) {
            return 0;
        }

        global $wpdb;
        $tabla_grupos = $wpdb->prefix . 'flavor_chat_grupos';
        return (int) $wpdb->get_var("SELECT COUNT(*) FROM $tabla_grupos WHERE estado = 'pendiente'");
    }

    /**
     * Cuenta reportes de moderación pendientes
     *
     * @return int
     */
    public function contar_reportes_pendientes() {
        // Verificar que el módulo esté activo
        if (!$this->can_activate()) {
            return 0;
        }

        global $wpdb;
        $tabla_reportes = $wpdb->prefix . 'flavor_chat_grupos_reportes';

        if (!Flavor_Chat_Helpers::tabla_existe($tabla_reportes)) {
            return 0;
        }

        return (int) $wpdb->get_var("SELECT COUNT(*) FROM $tabla_reportes WHERE estado = 'pendiente'");
    }

    public function public_permission_check($request) {
        $method = strtoupper($request->get_method());
        $tipo = in_array($method, ['POST', 'PUT', 'DELETE'], true) ? 'post' : 'get';
        return Flavor_API_Rate_Limiter::check_rate_limit($tipo);
    }

    /**
     * Crea páginas frontend automáticamente
     */
    public function maybe_create_pages() {
        if (!class_exists('Flavor_Page_Creator')) {
            return;
        }

        // En admin: refrescar páginas del módulo
        if (is_admin()) {
            Flavor_Page_Creator::refresh_module_pages('chat_grupos');
            return;
        }

        // En frontend: crear páginas si no existen
        $pagina = get_page_by_path('chat-grupos');
        if (!$pagina && !get_option('flavor_chat_grupos_pages_created')) {
            Flavor_Page_Creator::create_pages_for_modules(['chat_grupos']);
            update_option('flavor_chat_grupos_pages_created', 1, false);
        }
    }

    /**
     * Agrega un miembro a un grupo programáticamente
     *
     * Usado por otros módulos (como Comunidades) para sincronizar membresías.
     *
     * @param int    $grupo_id   ID del grupo
     * @param int    $usuario_id ID del usuario a agregar
     * @param string $rol        Rol del usuario (miembro, moderador, admin)
     * @return array Resultado de la operación
     */
    public function agregar_miembro_programatico($grupo_id, $usuario_id, $rol = 'miembro') {
        global $wpdb;
        $tabla_grupos = $wpdb->prefix . 'flavor_chat_grupos';
        $tabla_miembros = $wpdb->prefix . 'flavor_chat_grupos_miembros';

        // Verificar que el grupo existe
        $grupo = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM $tabla_grupos WHERE id = %d AND estado = 'activo'",
            $grupo_id
        ));

        if (!$grupo) {
            return ['success' => false, 'error' => __('Grupo no encontrado.', 'flavor-chat-ia')];
        }

        // Verificar si ya es miembro
        $membresia_existente = $wpdb->get_var($wpdb->prepare(
            "SELECT id FROM $tabla_miembros WHERE grupo_id = %d AND usuario_id = %d",
            $grupo_id, $usuario_id
        ));

        if ($membresia_existente) {
            return ['success' => true, 'mensaje' => __('El usuario ya es miembro del grupo.', 'flavor-chat-ia')];
        }

        // Verificar límite de miembros
        if ($grupo->miembros_count >= $grupo->max_miembros) {
            return ['success' => false, 'error' => __('El grupo ha alcanzado el límite de miembros.', 'flavor-chat-ia')];
        }

        // Añadir como miembro
        $insertado = $wpdb->insert($tabla_miembros, [
            'grupo_id' => $grupo_id,
            'usuario_id' => $usuario_id,
            'rol' => $rol,
        ]);

        if (!$insertado) {
            return ['success' => false, 'error' => __('Error al agregar miembro.', 'flavor-chat-ia')];
        }

        // Actualizar contador
        $wpdb->query($wpdb->prepare(
            "UPDATE $tabla_grupos SET miembros_count = miembros_count + 1 WHERE id = %d",
            $grupo_id
        ));

        // Mensaje de sistema (silencioso para sincronización)
        $usuario = get_userdata($usuario_id);
        if ($usuario) {
            $this->crear_mensaje_sistema($grupo_id, 'usuario_unido', [
                'usuario_id' => $usuario_id,
                'usuario_nombre' => $usuario->display_name,
            ]);
        }

        return ['success' => true, 'mensaje' => __('Miembro agregado correctamente.', 'flavor-chat-ia')];
    }

    /**
     * Quita un miembro de un grupo programáticamente
     *
     * Usado por otros módulos (como Comunidades) para sincronizar membresías.
     *
     * @param int $grupo_id   ID del grupo
     * @param int $usuario_id ID del usuario a quitar
     * @return array Resultado de la operación
     */
    public function quitar_miembro_programatico($grupo_id, $usuario_id) {
        global $wpdb;
        $tabla_grupos = $wpdb->prefix . 'flavor_chat_grupos';
        $tabla_miembros = $wpdb->prefix . 'flavor_chat_grupos_miembros';

        // Verificar si es miembro
        $miembro = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM $tabla_miembros WHERE grupo_id = %d AND usuario_id = %d",
            $grupo_id, $usuario_id
        ));

        if (!$miembro) {
            return ['success' => true, 'mensaje' => __('El usuario no es miembro del grupo.', 'flavor-chat-ia')];
        }

        // Eliminar membresía
        $eliminado = $wpdb->delete($tabla_miembros, [
            'grupo_id' => $grupo_id,
            'usuario_id' => $usuario_id,
        ]);

        if (!$eliminado) {
            return ['success' => false, 'error' => __('Error al quitar miembro.', 'flavor-chat-ia')];
        }

        // Actualizar contador
        $wpdb->query($wpdb->prepare(
            "UPDATE $tabla_grupos SET miembros_count = GREATEST(miembros_count - 1, 0) WHERE id = %d",
            $grupo_id
        ));

        // Mensaje de sistema
        $usuario = get_userdata($usuario_id);
        if ($usuario) {
            $this->crear_mensaje_sistema($grupo_id, 'usuario_salio', [
                'usuario_id' => $usuario_id,
                'usuario_nombre' => $usuario->display_name,
            ]);
        }

        return ['success' => true, 'mensaje' => __('Miembro eliminado correctamente.', 'flavor-chat-ia')];
    }

    /**
     * Obtiene estadísticas para el dashboard del cliente
     *
     * @return array Estadísticas del módulo
     */
    public function get_estadisticas_dashboard() {
        global $wpdb;
        $estadisticas = [];

        $tabla_grupos = $wpdb->prefix . 'flavor_chat_grupos';
        $tabla_miembros = $wpdb->prefix . 'flavor_chat_grupos_miembros';
        $tabla_mensajes = $wpdb->prefix . 'flavor_chat_grupos_mensajes';

        if (!Flavor_Chat_Helpers::tabla_existe($tabla_grupos)) {
            return $estadisticas;
        }

        $usuario_id = get_current_user_id();
        if ($usuario_id && Flavor_Chat_Helpers::tabla_existe($tabla_miembros)) {
            // Mis grupos (todos los registros en miembros son activos)
            $mis_grupos = (int) $wpdb->get_var($wpdb->prepare(
                "SELECT COUNT(*) FROM {$tabla_miembros}
                 WHERE usuario_id = %d",
                $usuario_id
            ));

            $estadisticas['mis_grupos'] = [
                'icon' => 'dashicons-groups',
                'valor' => $mis_grupos,
                'label' => __('Mis grupos', 'flavor-chat-ia'),
                'color' => $mis_grupos > 0 ? 'purple' : 'gray',
            ];

            // Mensajes sin leer (comparando IDs de mensaje)
            if (Flavor_Chat_Helpers::tabla_existe($tabla_mensajes)) {
                $sin_leer = (int) $wpdb->get_var($wpdb->prepare(
                    "SELECT COUNT(*) FROM {$tabla_mensajes} m
                     INNER JOIN {$tabla_miembros} mb ON m.grupo_id = mb.grupo_id
                     WHERE mb.usuario_id = %d
                     AND m.id > COALESCE(mb.ultimo_mensaje_leido, 0)
                     AND m.usuario_id != %d",
                    $usuario_id,
                    $usuario_id
                ));

                if ($sin_leer > 0) {
                    $estadisticas['sin_leer'] = [
                        'icon' => 'dashicons-email',
                        'valor' => $sin_leer,
                        'label' => __('Sin leer', 'flavor-chat-ia'),
                        'color' => 'orange',
                    ];
                }
            }
        }

        return $estadisticas;
    }

    /**
     * Define las páginas del módulo (Page Creator V3)
     *
     * @return array Definiciones de páginas
     */
    public function get_pages_definition() {
        return [
            [
                'title' => __('Chat en Grupos', 'flavor-chat-ia'),
                'slug' => 'chat-grupos',
                'content' => '<h1>' . __('Chat en Grupos', 'flavor-chat-ia') . '</h1>
<p>' . __('Únete a grupos de chat, comparte mensajes y conecta con comunidades de interés.', 'flavor-chat-ia') . '</p>

[flavor_module_listing module="chat_grupos" action="listar" columnas="3" limite="12"]',
                'parent' => 0,
            ],
            [
                'title' => __('Crear Grupo', 'flavor-chat-ia'),
                'slug' => 'crear-grupo',
                'content' => '<h1>' . __('Crear Grupo de Chat', 'flavor-chat-ia') . '</h1>
<p>' . __('Crea tu propio grupo de chat y reúne a personas con intereses comunes.', 'flavor-chat-ia') . '</p>

[flavor_module_listing module="chat_grupos" action="crear"]',
                'parent' => 'chat-grupos',
            ],
            [
                'title' => __('Mis Grupos', 'flavor-chat-ia'),
                'slug' => 'mis-grupos',
                'content' => '<h1>' . __('Mis Grupos', 'flavor-chat-ia') . '</h1>
<p>' . __('Gestiona los grupos de chat a los que perteneces y los que has creado.', 'flavor-chat-ia') . '</p>

[flavor_module_listing module="chat_grupos" action="mis_grupos" columnas="3" limite="12"]',
                'parent' => 'chat-grupos',
            ],
        ];
    }

    /**
     * Configuración para el Module Renderer
     *
     * @return array
     */
    public static function get_renderer_config(): array {
        return [
            'module'   => 'chat-grupos',
            'title'    => __('Chat en Grupos', 'flavor-chat-ia'),
            'subtitle' => __('Comunicación en tiempo real por grupos temáticos', 'flavor-chat-ia'),
            'icon'     => '💬',
            'color'    => 'primary', // Usa variable CSS --flavor-primary del tema

            'database' => [
                'table'       => 'flavor_chat_grupos',
                'primary_key' => 'id',
            ],

            'fields' => [
                'nombre'       => ['type' => 'text', 'label' => __('Nombre del grupo', 'flavor-chat-ia'), 'required' => true],
                'descripcion'  => ['type' => 'textarea', 'label' => __('Descripción', 'flavor-chat-ia')],
                'tipo'         => ['type' => 'select', 'label' => __('Tipo', 'flavor-chat-ia'), 'options' => ['publico', 'privado', 'secreto']],
                'categoria'    => ['type' => 'select', 'label' => __('Categoría', 'flavor-chat-ia')],
                'imagen'       => ['type' => 'image', 'label' => __('Imagen', 'flavor-chat-ia')],
                'max_miembros' => ['type' => 'number', 'label' => __('Máx. miembros', 'flavor-chat-ia')],
            ],

            'estados' => [
                'activo'    => ['label' => __('Activo', 'flavor-chat-ia'), 'color' => 'green', 'icon' => '🟢'],
                'pausado'   => ['label' => __('Pausado', 'flavor-chat-ia'), 'color' => 'yellow', 'icon' => '⏸️'],
                'archivado' => ['label' => __('Archivado', 'flavor-chat-ia'), 'color' => 'gray', 'icon' => '🗄️'],
            ],

            'stats' => [
                'grupos_activos'   => ['label' => __('Grupos activos', 'flavor-chat-ia'), 'icon' => '💬', 'color' => 'sky'],
                'miembros_totales' => ['label' => __('Miembros', 'flavor-chat-ia'), 'icon' => '👥', 'color' => 'blue'],
                'mensajes_hoy'     => ['label' => __('Mensajes hoy', 'flavor-chat-ia'), 'icon' => '📝', 'color' => 'green'],
                'usuarios_online'  => ['label' => __('Online ahora', 'flavor-chat-ia'), 'icon' => '🟢', 'color' => 'emerald'],
            ],

            'card' => [
                'template'     => 'grupo-card',
                'title_field'  => 'nombre',
                'subtitle_field' => 'tipo',
                'meta_fields'  => ['miembros_count', 'mensajes_sin_leer'],
                'show_imagen'  => true,
                'show_estado'  => true,
            ],

            'tabs' => [
                'grupos' => [
                    'label'   => __('Grupos', 'flavor-chat-ia'),
                    'icon'    => 'dashicons-groups',
                    'content' => 'template:_archive.php',
                    'public'  => true,
                ],
                'crear' => [
                    'label'      => __('Crear grupo', 'flavor-chat-ia'),
                    'icon'       => 'dashicons-plus-alt',
                    'content'    => 'shortcode:chat_grupos_crear',
                    'requires_login' => true,
                ],
                'mis-grupos' => [
                    'label'      => __('Mis grupos', 'flavor-chat-ia'),
                    'icon'       => 'dashicons-admin-users',
                    'content'    => 'shortcode:chat_grupos_mis_grupos',
                    'requires_login' => true,
                ],
                'mensajes' => [
                    'label'      => __('Mensajes', 'flavor-chat-ia'),
                    'icon'       => 'dashicons-email',
                    'content'    => 'shortcode:chat_grupos_mensajes',
                    'requires_login' => true,
                    'hidden_nav' => true,
                ],
            ],

            'archive' => [
                'columns'    => 3,
                'per_page'   => 12,
                'order_by'   => 'nombre',
                'order'      => 'ASC',
                'filterable' => ['tipo', 'categoria'],
            ],

            'dashboard' => [
                'widgets' => ['grupos_recientes', 'mensajes_sin_leer', 'usuarios_online', 'stats'],
                'actions' => [
                    'crear'  => ['label' => __('Crear grupo', 'flavor-chat-ia'), 'icon' => '➕', 'color' => 'sky'],
                    'buscar' => ['label' => __('Buscar grupos', 'flavor-chat-ia'), 'icon' => '🔍', 'color' => 'blue'],
                ],
            ],

            'features' => [
                'tiempo_real'    => true,
                'archivos'       => true,
                'menciones'      => true,
                'reacciones'     => true,
                'moderacion'     => true,
            ],
        ];
    }


    /**
     * Inicializa el dashboard tab del módulo
     */
    private function inicializar_dashboard_tab() {
        $archivo = dirname(__FILE__) . '/class-chat-grupos-dashboard-tab.php';
        if (file_exists($archivo)) {
            require_once $archivo;
            if (class_exists('Flavor_Chat_Grupos_Dashboard_Tab')) {
                Flavor_Chat_Grupos_Dashboard_Tab::get_instance();
            }
        }
    }

    /**
     * Crea un mensaje de sistema en un grupo
     *
     * @param int    $grupo_id ID del grupo
     * @param string $tipo     Tipo de mensaje (grupo_creado, usuario_unido, usuario_salio, etc.)
     * @param array  $datos    Datos adicionales
     * @return int|false ID del mensaje o false si falla
     */
    private function crear_mensaje_sistema($grupo_id, $tipo, $datos = []) {
        global $wpdb;
        $tabla_mensajes = $wpdb->prefix . 'flavor_chat_grupos_mensajes';

        $mensajes = [
            'grupo_creado'  => sprintf(__('%s ha creado el grupo', 'flavor-chat-ia'), $datos['usuario_nombre'] ?? __('Un usuario', 'flavor-chat-ia')),
            'usuario_unido' => sprintf(__('%s se ha unido al grupo', 'flavor-chat-ia'), $datos['usuario_nombre'] ?? __('Un usuario', 'flavor-chat-ia')),
            'usuario_salio' => sprintf(__('%s ha salido del grupo', 'flavor-chat-ia'), $datos['usuario_nombre'] ?? __('Un usuario', 'flavor-chat-ia')),
            'usuario_expulsado' => sprintf(__('%s ha sido expulsado del grupo', 'flavor-chat-ia'), $datos['usuario_nombre'] ?? __('Un usuario', 'flavor-chat-ia')),
            'rol_cambiado'  => sprintf(__('%s ahora es %s', 'flavor-chat-ia'), $datos['usuario_nombre'] ?? __('Un usuario', 'flavor-chat-ia'), $datos['nuevo_rol'] ?? 'miembro'),
        ];

        $mensaje_texto = $mensajes[$tipo] ?? $tipo;

        $insertado = $wpdb->insert($tabla_mensajes, [
            'grupo_id'   => $grupo_id,
            'usuario_id' => $datos['usuario_id'] ?? 0,
            'mensaje'    => $mensaje_texto,
            'tipo'       => 'sistema',
        ]);

        return $insertado ? $wpdb->insert_id : false;
    }

    /* ========================================
       AJAX Handlers
    ======================================== */

    /**
     * AJAX: Crear grupo
     */
    public function ajax_crear_grupo() {
        check_ajax_referer('flavor_chat_grupos_nonce', 'nonce');

        // Mapear tipos del frontend a tipos del backend
        $tipo_frontend = sanitize_text_field($_POST['tipo'] ?? 'abierto');
        $mapeo_tipos = [
            'abierto' => 'publico',
            'cerrado' => 'privado',
            'privado' => 'secreto',
            // Mantener compatibilidad con valores directos
            'publico' => 'publico',
            'secreto' => 'secreto',
        ];
        $tipo_backend = $mapeo_tipos[$tipo_frontend] ?? 'publico';

        $resultado = $this->action_crear_grupo([
            'nombre'      => sanitize_text_field($_POST['nombre'] ?? ''),
            'descripcion' => sanitize_textarea_field($_POST['descripcion'] ?? ''),
            'tipo'        => $tipo_backend,
            'categoria'   => sanitize_text_field($_POST['categoria'] ?? ''),
            'color'       => sanitize_hex_color($_POST['color'] ?? '#2271b1'),
        ]);

        wp_send_json($resultado);
    }

    /**
     * AJAX: Unirse a grupo
     */
    public function ajax_unirse_grupo() {
        check_ajax_referer('flavor_chat_grupos_nonce', 'nonce');

        $resultado = $this->action_unirse_grupo([
            'grupo_id'          => intval($_POST['grupo_id'] ?? 0),
            'codigo_invitacion' => sanitize_text_field($_POST['codigo'] ?? ''),
        ]);

        wp_send_json($resultado);
    }

    /**
     * AJAX: Salir de grupo
     */
    public function ajax_salir_grupo() {
        check_ajax_referer('flavor_chat_grupos_nonce', 'nonce');

        $grupo_id = intval($_POST['grupo_id'] ?? 0);
        $usuario_id = get_current_user_id();

        if (!$grupo_id || !$usuario_id) {
            wp_send_json(['success' => false, 'error' => __('Datos incompletos', 'flavor-chat-ia')]);
            return;
        }

        global $wpdb;
        $tabla_miembros = $wpdb->prefix . 'flavor_chat_grupos_miembros';
        $tabla_grupos = $wpdb->prefix . 'flavor_chat_grupos';

        // Verificar que es miembro
        $miembro = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM $tabla_miembros WHERE grupo_id = %d AND usuario_id = %d",
            $grupo_id, $usuario_id
        ));

        if (!$miembro) {
            wp_send_json(['success' => false, 'error' => __('No eres miembro de este grupo', 'flavor-chat-ia')]);
            return;
        }

        // No permitir salir si es el único admin
        if ($miembro->rol === 'admin') {
            $otros_admins = $wpdb->get_var($wpdb->prepare(
                "SELECT COUNT(*) FROM $tabla_miembros WHERE grupo_id = %d AND rol = 'admin' AND usuario_id != %d",
                $grupo_id, $usuario_id
            ));

            if (!$otros_admins) {
                wp_send_json(['success' => false, 'error' => __('Debes asignar otro administrador antes de salir', 'flavor-chat-ia')]);
                return;
            }
        }

        // Eliminar de miembros
        $wpdb->delete($tabla_miembros, ['grupo_id' => $grupo_id, 'usuario_id' => $usuario_id]);

        // Actualizar contador
        $wpdb->query($wpdb->prepare(
            "UPDATE $tabla_grupos SET miembros_count = miembros_count - 1 WHERE id = %d",
            $grupo_id
        ));

        // Mensaje de sistema
        $this->crear_mensaje_sistema($grupo_id, 'usuario_salio', [
            'usuario_id' => $usuario_id,
            'usuario_nombre' => wp_get_current_user()->display_name,
        ]);

        wp_send_json(['success' => true]);
    }

    /**
     * AJAX: Enviar mensaje
     */
    public function ajax_enviar_mensaje() {
        check_ajax_referer('flavor_chat_grupos_nonce', 'nonce');

        $grupo_id = intval($_POST['grupo_id'] ?? 0);
        $mensaje_texto = sanitize_textarea_field($_POST['mensaje'] ?? $_POST['contenido'] ?? '');
        $responde_a = intval($_POST['responde_a'] ?? 0);
        $adjuntos_raw = isset($_POST['adjuntos']) ? json_decode(stripslashes($_POST['adjuntos']), true) : [];

        if (!$grupo_id || empty($mensaje_texto)) {
            wp_send_json(['success' => false, 'error' => __('Datos incompletos', 'flavor-chat-ia')]);
            return;
        }

        // Limitar longitud del mensaje
        if (mb_strlen($mensaje_texto) > 5000) {
            wp_send_json(['success' => false, 'error' => __('El mensaje es demasiado largo (máximo 5000 caracteres)', 'flavor-chat-ia')]);
            return;
        }

        $usuario_id = get_current_user_id();
        if (!$usuario_id) {
            wp_send_json(['success' => false, 'error' => __('Debes iniciar sesión', 'flavor-chat-ia')]);
            return;
        }

        // Rate limiting: máximo 30 mensajes por minuto
        $rate_limit_key = "cg_rate_{$usuario_id}_{$grupo_id}";
        $mensajes_recientes = get_transient($rate_limit_key) ?: 0;
        if ($mensajes_recientes >= 30) {
            wp_send_json(['success' => false, 'error' => __('Estás enviando mensajes muy rápido. Espera un momento.', 'flavor-chat-ia')]);
            return;
        }
        set_transient($rate_limit_key, $mensajes_recientes + 1, 60);

        // Verificar que es miembro
        global $wpdb;
        $tabla_miembros = $wpdb->prefix . 'flavor_chat_grupos_miembros';
        $es_miembro = $wpdb->get_var($wpdb->prepare(
            "SELECT id FROM $tabla_miembros WHERE grupo_id = %d AND usuario_id = %d",
            $grupo_id, $usuario_id
        ));

        if (!$es_miembro) {
            wp_send_json(['success' => false, 'error' => __('No eres miembro de este grupo', 'flavor-chat-ia')]);
            return;
        }

        // Validar y sanitizar adjuntos
        $adjuntos = [];
        if (!empty($adjuntos_raw) && is_array($adjuntos_raw)) {
            foreach ($adjuntos_raw as $adj) {
                if (!is_array($adj)) continue;

                // Solo permitir adjuntos con ID válido (subidos via WordPress)
                $adj_id = intval($adj['id'] ?? 0);
                if (!$adj_id) continue;

                // Verificar que el attachment existe y pertenece al usuario
                $attachment = get_post($adj_id);
                if (!$attachment || $attachment->post_type !== 'attachment') continue;
                if (intval($attachment->post_author) !== $usuario_id) continue;

                $adjuntos[] = [
                    'id'        => $adj_id,
                    'url'       => esc_url(wp_get_attachment_url($adj_id)),
                    'nombre'    => sanitize_file_name($adj['nombre'] ?? basename(get_attached_file($adj_id))),
                    'es_imagen' => wp_attachment_is_image($adj_id),
                ];
            }
        }

        // Crear mensaje
        $tabla_mensajes = $wpdb->prefix . 'flavor_chat_grupos_mensajes';
        $mensaje_procesado = $this->procesar_contenido_mensaje($mensaje_texto);
        $adjuntos_json = !empty($adjuntos) ? wp_json_encode($adjuntos) : null;

        $insertado = $wpdb->insert($tabla_mensajes, [
            'grupo_id'    => $grupo_id,
            'usuario_id'  => $usuario_id,
            'mensaje'     => $mensaje_procesado['texto'],
            'mensaje_html'=> $mensaje_procesado['html'],
            'responde_a'  => $responde_a ?: null,
            'adjuntos'    => $adjuntos_json,
            'tipo'        => 'mensaje',
        ]);

        if (!$insertado) {
            wp_send_json(['success' => false, 'error' => __('Error al guardar mensaje', 'flavor-chat-ia')]);
            return;
        }

        $mensaje_id = $wpdb->insert_id;
        $usuario = wp_get_current_user();

        // Actualizar último mensaje del grupo
        $tabla_grupos = $wpdb->prefix . 'flavor_chat_grupos';
        $wpdb->update($tabla_grupos, ['ultimo_mensaje_at' => current_time('mysql')], ['id' => $grupo_id]);

        $mensaje_data = [
            'id'           => $mensaje_id,
            'grupo_id'     => $grupo_id,
            'usuario_id'   => $usuario_id,
            'autor_nombre' => $usuario->display_name,
            'autor_avatar' => get_avatar_url($usuario_id, ['size' => 48]),
            'mensaje'      => $mensaje_procesado['texto'],
            'mensaje_html' => $mensaje_procesado['html'],
            'fecha'        => current_time('mysql'),
            'fecha_humana' => __('ahora', 'flavor-chat-ia'),
            'es_mio'       => true,
            'responde_a'   => $responde_a ?: null,
            'adjuntos'     => $adjuntos,
            'reacciones'   => [],
        ];

        wp_send_json(['success' => true, 'mensaje' => $mensaje_data]);
    }

    /**
     * AJAX: Obtener mensajes
     */
    public function ajax_obtener_mensajes() {
        $grupo_id = intval($_GET['grupo_id'] ?? $_POST['grupo_id'] ?? 0);
        $limite = min(intval($_GET['limite'] ?? $_POST['limite'] ?? 50), 100); // Máximo 100 mensajes
        $desde = intval($_GET['desde'] ?? $_POST['desde_id'] ?? 0);
        $antes_de = intval($_GET['antes_de'] ?? 0);

        if (!$grupo_id) {
            wp_send_json(['success' => false, 'error' => __('Grupo no especificado', 'flavor-chat-ia')]);
            return;
        }

        $usuario_id = get_current_user_id();
        global $wpdb;
        $tabla_mensajes = $wpdb->prefix . 'flavor_chat_grupos_mensajes';
        $tabla_miembros = $wpdb->prefix . 'flavor_chat_grupos_miembros';
        $tabla_grupos = $wpdb->prefix . 'flavor_chat_grupos';

        // Obtener info del grupo para verificar tipo de acceso
        $grupo = $wpdb->get_row($wpdb->prepare(
            "SELECT tipo, estado FROM $tabla_grupos WHERE id = %d",
            $grupo_id
        ));

        if (!$grupo || $grupo->estado !== 'activo') {
            wp_send_json(['success' => false, 'error' => __('Grupo no encontrado', 'flavor-chat-ia')]);
            return;
        }

        // Verificar acceso según tipo de grupo
        $es_miembro = false;
        if ($usuario_id) {
            $es_miembro = (bool) $wpdb->get_var($wpdb->prepare(
                "SELECT id FROM $tabla_miembros WHERE grupo_id = %d AND usuario_id = %d",
                $grupo_id, $usuario_id
            ));
        }

        // Grupos privados/secretos requieren membresía
        if (in_array($grupo->tipo, ['privado', 'secreto'], true) && !$es_miembro) {
            wp_send_json(['success' => false, 'error' => __('No tienes acceso a este grupo', 'flavor-chat-ia')]);
            return;
        }

        // Grupos públicos: usuarios no logueados solo ven mensajes limitados
        if ($grupo->tipo === 'publico' && !$usuario_id) {
            $limite = min($limite, 20); // Solo 20 mensajes para usuarios no autenticados
        }

        // Construir query
        $condiciones = ["grupo_id = %d", "eliminado = 0"];
        $parametros = [$grupo_id];

        if ($desde > 0) {
            $condiciones[] = "id > %d";
            $parametros[] = $desde;
        }

        if ($antes_de > 0) {
            $condiciones[] = "id < %d";
            $parametros[] = $antes_de;
        }

        $where = implode(' AND ', $condiciones);
        $orden = $antes_de > 0 ? 'DESC' : 'ASC';

        $mensajes_raw = $wpdb->get_results($wpdb->prepare(
            "SELECT m.*, u.display_name as autor_nombre
             FROM $tabla_mensajes m
             LEFT JOIN {$wpdb->users} u ON m.usuario_id = u.ID
             WHERE $where
             ORDER BY m.id $orden
             LIMIT %d",
            array_merge($parametros, [$limite])
        ));

        if ($antes_de > 0) {
            $mensajes_raw = array_reverse($mensajes_raw);
        }

        $mensajes = [];
        foreach ($mensajes_raw as $msg) {
            $mensajes[] = [
                'id'           => $msg->id,
                'grupo_id'     => $msg->grupo_id,
                'usuario_id'   => $msg->usuario_id,
                'autor_nombre' => $msg->autor_nombre ?: __('Usuario', 'flavor-chat-ia'),
                'autor_avatar' => get_avatar_url($msg->usuario_id, ['size' => 48]),
                'mensaje'      => $msg->mensaje,
                'mensaje_html' => $msg->mensaje_html ?: esc_html($msg->mensaje),
                'fecha'        => $msg->fecha_creacion,
                'fecha_humana' => human_time_diff(strtotime($msg->fecha_creacion), current_time('timestamp')),
                'es_mio'       => $usuario_id && intval($msg->usuario_id) === $usuario_id,
                'responde_a'   => $msg->responde_a,
                'adjuntos'     => $msg->adjuntos ? json_decode($msg->adjuntos, true) : [],
                'reacciones'   => $this->obtener_reacciones_mensaje($msg->id, $usuario_id),
                'tipo'         => $msg->tipo,
                'eliminado'    => (bool) $msg->eliminado,
                'editado'      => (bool) $msg->editado,
            ];
        }

        wp_send_json(['success' => true, 'mensajes' => $mensajes]);
    }

    /**
     * AJAX: Marcar como leído
     */
    public function ajax_marcar_leido() {
        check_ajax_referer('flavor_chat_grupos_nonce', 'nonce');

        $grupo_id = intval($_POST['grupo_id'] ?? 0);
        $mensaje_id = intval($_POST['mensaje_id'] ?? 0);
        $usuario_id = get_current_user_id();

        if (!$grupo_id || !$usuario_id) {
            wp_send_json(['success' => false]);
            return;
        }

        global $wpdb;
        $tabla_leidos = $wpdb->prefix . 'flavor_chat_grupos_leidos';

        $wpdb->replace($tabla_leidos, [
            'grupo_id'          => $grupo_id,
            'usuario_id'        => $usuario_id,
            'ultimo_mensaje_id' => $mensaje_id,
            'leido_at'          => current_time('mysql'),
        ]);

        wp_send_json(['success' => true]);
    }

    /**
     * AJAX: Indicador de escritura
     */
    public function ajax_typing() {
        check_ajax_referer('flavor_chat_grupos_nonce', 'nonce');

        $grupo_id = intval($_POST['grupo_id'] ?? 0);
        $usuario_id = get_current_user_id();

        if (!$grupo_id || !$usuario_id) {
            wp_send_json(['success' => false]);
            return;
        }

        $transient_key = "cg_typing_{$grupo_id}";
        $escribiendo = get_transient($transient_key) ?: [];

        // Añadir usuario actual
        $usuario = wp_get_current_user();
        $escribiendo[$usuario_id] = [
            'id'     => $usuario_id,
            'nombre' => $usuario->display_name,
            'time'   => time(),
        ];

        // Limpiar usuarios inactivos (más de 5 segundos)
        $ahora = time();
        $escribiendo = array_filter($escribiendo, function($e) use ($ahora, $usuario_id) {
            return ($ahora - $e['time']) < 5 || $e['id'] === $usuario_id;
        });

        set_transient($transient_key, $escribiendo, 10);

        // Devolver otros usuarios que están escribiendo (excepto el actual)
        $otros = array_filter($escribiendo, function($e) use ($usuario_id) {
            return $e['id'] !== $usuario_id;
        });

        wp_send_json(['success' => true, 'data' => ['escribiendo' => array_values($otros)]]);
    }

    /**
     * AJAX: Reaccionar a mensaje
     */
    public function ajax_reaccionar() {
        check_ajax_referer('flavor_chat_grupos_nonce', 'nonce');

        $mensaje_id = intval($_POST['mensaje_id'] ?? 0);
        $emoji = sanitize_text_field($_POST['emoji'] ?? '');
        $usuario_id = get_current_user_id();

        if (!$mensaje_id || !$emoji || !$usuario_id) {
            wp_send_json(['success' => false, 'error' => __('Datos incompletos', 'flavor-chat-ia')]);
            return;
        }

        global $wpdb;
        $tabla_reacciones = $wpdb->prefix . 'flavor_chat_grupos_reacciones';

        // Verificar si ya existe la reacción
        $existente = $wpdb->get_var($wpdb->prepare(
            "SELECT id FROM $tabla_reacciones WHERE mensaje_id = %d AND usuario_id = %d AND emoji = %s",
            $mensaje_id, $usuario_id, $emoji
        ));

        if ($existente) {
            // Eliminar reacción
            $wpdb->delete($tabla_reacciones, ['id' => $existente]);
            $accion = 'eliminada';
        } else {
            // Añadir reacción
            $wpdb->insert($tabla_reacciones, [
                'mensaje_id' => $mensaje_id,
                'usuario_id' => $usuario_id,
                'emoji'      => $emoji,
            ]);
            $accion = 'añadida';
        }

        wp_send_json(['success' => true, 'accion' => $accion]);
    }

    /**
     * AJAX: Eliminar mensaje
     */
    public function ajax_eliminar_mensaje() {
        check_ajax_referer('flavor_chat_grupos_nonce', 'nonce');

        $mensaje_id = intval($_POST['mensaje_id'] ?? 0);
        $usuario_id = get_current_user_id();

        if (!$mensaje_id || !$usuario_id) {
            wp_send_json(['success' => false, 'error' => __('Datos incompletos', 'flavor-chat-ia')]);
            return;
        }

        global $wpdb;
        $tabla_mensajes = $wpdb->prefix . 'flavor_chat_grupos_mensajes';

        // Verificar que el mensaje pertenece al usuario
        $mensaje = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM $tabla_mensajes WHERE id = %d",
            $mensaje_id
        ));

        if (!$mensaje) {
            wp_send_json(['success' => false, 'error' => __('Mensaje no encontrado', 'flavor-chat-ia')]);
            return;
        }

        // Solo el autor o admin puede eliminar
        $es_autor = intval($mensaje->usuario_id) === $usuario_id;
        $es_admin = $this->es_admin_grupo($mensaje->grupo_id, $usuario_id);

        if (!$es_autor && !$es_admin) {
            wp_send_json(['success' => false, 'error' => __('No tienes permiso para eliminar este mensaje', 'flavor-chat-ia')]);
            return;
        }

        // Marcar como eliminado (soft delete)
        $wpdb->update($tabla_mensajes, ['eliminado' => 1], ['id' => $mensaje_id]);

        wp_send_json(['success' => true]);
    }

    /**
     * Verifica si el usuario es admin de un grupo
     */
    private function es_admin_grupo($grupo_id, $usuario_id) {
        global $wpdb;
        $tabla_miembros = $wpdb->prefix . 'flavor_chat_grupos_miembros';

        $rol = $wpdb->get_var($wpdb->prepare(
            "SELECT rol FROM $tabla_miembros WHERE grupo_id = %d AND usuario_id = %d",
            $grupo_id, $usuario_id
        ));

        return in_array($rol, ['admin', 'moderador'], true);
    }

    /**
     * Obtiene las reacciones de un mensaje
     */
    private function obtener_reacciones_mensaje($mensaje_id, $usuario_id = 0) {
        global $wpdb;
        $tabla_reacciones = $wpdb->prefix . 'flavor_chat_grupos_reacciones';

        if (!Flavor_Chat_Helpers::tabla_existe($tabla_reacciones)) {
            return [];
        }

        $reacciones_raw = $wpdb->get_results($wpdb->prepare(
            "SELECT emoji, COUNT(*) as count, GROUP_CONCAT(usuario_id) as usuarios
             FROM $tabla_reacciones
             WHERE mensaje_id = %d
             GROUP BY emoji",
            $mensaje_id
        ));

        $reacciones = [];
        foreach ($reacciones_raw as $r) {
            $usuarios_ids = explode(',', $r->usuarios);
            $reacciones[] = [
                'emoji'        => $r->emoji,
                'count'        => intval($r->count),
                'yo_reaccione' => $usuario_id && in_array($usuario_id, $usuarios_ids),
            ];
        }

        return $reacciones;
    }

    /**
     * Procesa el contenido de un mensaje (menciones, enlaces, emojis)
     */
    private function procesar_contenido_mensaje($texto) {
        // Sanitizar el texto primero
        $texto = wp_kses($texto, []);
        $html = esc_html($texto);

        // Convertir URLs en enlaces (solo http/https, escapando la URL)
        $html = preg_replace_callback(
            '/(https?:\/\/[^\s<>"\']+)/i',
            function($matches) {
                $url = esc_url($matches[1]);
                // Verificar que es una URL válida después de escapar
                if (empty($url) || strpos($url, 'http') !== 0) {
                    return esc_html($matches[1]);
                }
                return '<a href="' . $url . '" target="_blank" rel="noopener noreferrer nofollow">' . esc_html($matches[1]) . '</a>';
            },
            $html
        );

        // Convertir menciones @usuario (solo alfanuméricos y guión bajo)
        $html = preg_replace(
            '/@([a-zA-Z0-9_]{1,50})/',
            '<span class="cg-mencion">@$1</span>',
            $html
        );

        // Convertir saltos de línea
        $html = nl2br($html);

        return [
            'texto' => $texto,
            'html'  => $html,
        ];
    }

    /**
     * AJAX: Buscar mensajes
     */
    public function ajax_buscar_mensajes() {
        $grupo_id = intval($_GET['grupo_id'] ?? $_POST['grupo_id'] ?? 0);
        $query = sanitize_text_field($_GET['q'] ?? $_POST['q'] ?? '');
        $usuario_id = get_current_user_id();

        if (!$grupo_id || strlen($query) < 2) {
            wp_send_json(['success' => false, 'error' => __('Búsqueda muy corta', 'flavor-chat-ia')]);
            return;
        }

        // Requiere autenticación para buscar
        if (!$usuario_id) {
            wp_send_json(['success' => false, 'error' => __('Debes iniciar sesión', 'flavor-chat-ia')]);
            return;
        }

        global $wpdb;
        $tabla_mensajes = $wpdb->prefix . 'flavor_chat_grupos_mensajes';
        $tabla_miembros = $wpdb->prefix . 'flavor_chat_grupos_miembros';

        // Verificar que es miembro del grupo
        $es_miembro = $wpdb->get_var($wpdb->prepare(
            "SELECT id FROM $tabla_miembros WHERE grupo_id = %d AND usuario_id = %d",
            $grupo_id, $usuario_id
        ));

        if (!$es_miembro) {
            wp_send_json(['success' => false, 'error' => __('No tienes acceso a este grupo', 'flavor-chat-ia')]);
            return;
        }

        $mensajes = $wpdb->get_results($wpdb->prepare(
            "SELECT m.*, u.display_name as autor_nombre
             FROM $tabla_mensajes m
             LEFT JOIN {$wpdb->users} u ON m.usuario_id = u.ID
             WHERE m.grupo_id = %d AND m.mensaje LIKE %s AND m.eliminado = 0
             ORDER BY m.id DESC
             LIMIT 20",
            $grupo_id, '%' . $wpdb->esc_like($query) . '%'
        ));

        $resultados = [];
        foreach ($mensajes as $msg) {
            $resultados[] = [
                'id'           => $msg->id,
                'autor_nombre' => $msg->autor_nombre,
                'mensaje'      => wp_trim_words($msg->mensaje, 20),
                'fecha'        => $msg->fecha_creacion,
            ];
        }

        wp_send_json(['success' => true, 'resultados' => $resultados]);
    }

    /**
     * AJAX: Subir archivo
     */
    public function ajax_subir_archivo() {
        check_ajax_referer('flavor_chat_grupos_nonce', 'nonce');

        if (empty($_FILES['archivo'])) {
            wp_send_json(['success' => false, 'data' => __('No se recibió ningún archivo', 'flavor-chat-ia')]);
            return;
        }

        $grupo_id = intval($_POST['grupo_id'] ?? 0);
        $usuario_id = get_current_user_id();

        if (!$grupo_id || !$usuario_id) {
            wp_send_json(['success' => false, 'data' => __('Datos incompletos', 'flavor-chat-ia')]);
            return;
        }

        // Verificar que es miembro
        global $wpdb;
        $tabla_miembros = $wpdb->prefix . 'flavor_chat_grupos_miembros';
        $es_miembro = $wpdb->get_var($wpdb->prepare(
            "SELECT id FROM $tabla_miembros WHERE grupo_id = %d AND usuario_id = %d",
            $grupo_id, $usuario_id
        ));

        if (!$es_miembro) {
            wp_send_json(['success' => false, 'data' => __('No eres miembro del grupo', 'flavor-chat-ia')]);
            return;
        }

        // Verificar configuración
        $settings = $this->get_settings();
        if (!$settings['permite_archivos']) {
            wp_send_json(['success' => false, 'data' => __('No se permiten archivos en este grupo', 'flavor-chat-ia')]);
            return;
        }

        // Verificar tamaño
        $max_bytes = ($settings['max_archivo_mb'] ?? 10) * 1024 * 1024;
        if ($_FILES['archivo']['size'] > $max_bytes) {
            wp_send_json(['success' => false, 'data' => sprintf(__('El archivo excede el límite de %d MB', 'flavor-chat-ia'), $settings['max_archivo_mb'])]);
            return;
        }

        require_once(ABSPATH . 'wp-admin/includes/file.php');
        require_once(ABSPATH . 'wp-admin/includes/media.php');
        require_once(ABSPATH . 'wp-admin/includes/image.php');

        $attachment_id = media_handle_upload('archivo', 0);

        if (is_wp_error($attachment_id)) {
            wp_send_json(['success' => false, 'data' => $attachment_id->get_error_message()]);
            return;
        }

        $url = wp_get_attachment_url($attachment_id);
        $es_imagen = wp_attachment_is_image($attachment_id);

        wp_send_json([
            'success' => true,
            'data'    => [
                'id'        => $attachment_id,
                'url'       => $url,
                'nombre'    => basename($_FILES['archivo']['name']),
                'es_imagen' => $es_imagen,
            ],
        ]);
    }

    /**
     * AJAX: Invitar usuario
     */
    public function ajax_invitar() {
        check_ajax_referer('flavor_chat_grupos_nonce', 'nonce');
        wp_send_json(['success' => false, 'error' => __('Función no implementada', 'flavor-chat-ia')]);
    }

    /**
     * AJAX: Expulsar usuario
     */
    public function ajax_expulsar() {
        check_ajax_referer('flavor_chat_grupos_nonce', 'nonce');
        wp_send_json(['success' => false, 'error' => __('Función no implementada', 'flavor-chat-ia')]);
    }

    /**
     * AJAX: Cambiar rol de usuario
     */
    public function ajax_cambiar_rol() {
        check_ajax_referer('flavor_chat_grupos_nonce', 'nonce');
        wp_send_json(['success' => false, 'error' => __('Función no implementada', 'flavor-chat-ia')]);
    }

    /**
     * AJAX: Actualizar configuración del grupo
     */
    public function ajax_actualizar_config() {
        check_ajax_referer('flavor_chat_grupos_nonce', 'nonce');
        wp_send_json(['success' => false, 'error' => __('Función no implementada', 'flavor-chat-ia')]);
    }

    /**
     * AJAX: Crear encuesta
     */
    public function ajax_crear_encuesta() {
        check_ajax_referer('flavor_chat_grupos_nonce', 'nonce');
        wp_send_json(['success' => false, 'error' => __('Función no implementada', 'flavor-chat-ia')]);
    }

    /**
     * AJAX: Votar en encuesta
     */
    public function ajax_votar_encuesta() {
        check_ajax_referer('flavor_chat_grupos_nonce', 'nonce');
        wp_send_json(['success' => false, 'error' => __('Función no implementada', 'flavor-chat-ia')]);
    }

    /**
     * AJAX: Editar mensaje
     */
    public function ajax_editar_mensaje() {
        check_ajax_referer('flavor_chat_grupos_nonce', 'nonce');

        $mensaje_id = intval($_POST['mensaje_id'] ?? 0);
        $nuevo_texto = sanitize_textarea_field($_POST['mensaje'] ?? '');
        $usuario_id = get_current_user_id();

        if (!$mensaje_id || empty($nuevo_texto) || !$usuario_id) {
            wp_send_json(['success' => false, 'error' => __('Datos incompletos', 'flavor-chat-ia')]);
            return;
        }

        global $wpdb;
        $tabla_mensajes = $wpdb->prefix . 'flavor_chat_grupos_mensajes';

        // Verificar que el mensaje pertenece al usuario
        $mensaje = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM $tabla_mensajes WHERE id = %d AND usuario_id = %d",
            $mensaje_id, $usuario_id
        ));

        if (!$mensaje) {
            wp_send_json(['success' => false, 'error' => __('No puedes editar este mensaje', 'flavor-chat-ia')]);
            return;
        }

        $procesado = $this->procesar_contenido_mensaje($nuevo_texto);

        $wpdb->update($tabla_mensajes, [
            'mensaje'      => $procesado['texto'],
            'mensaje_html' => $procesado['html'],
            'editado'      => 1,
            'editado_at'   => current_time('mysql'),
        ], ['id' => $mensaje_id]);

        wp_send_json(['success' => true]);
    }

    /**
     * AJAX: Fijar mensaje
     */
    public function ajax_fijar_mensaje() {
        check_ajax_referer('flavor_chat_grupos_nonce', 'nonce');
        wp_send_json(['success' => false, 'error' => __('Función no implementada', 'flavor-chat-ia')]);
    }
}

<?php
/**
 * Modulo de Red Social Comunitaria para Chat IA
 *
 * @package FlavorChatIA
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Modulo de Red Social - Alternativa social media para la comunidad
 *
 * INTEGRACIONES:
 * - Este modulo es PROVIDER de publicaciones
 * - Las publicaciones pueden vincularse a productos, eventos, etc.
 */
class Flavor_Chat_Red_Social_Module extends Flavor_Chat_Module_Base {

    use Flavor_Module_Admin_Pages_Trait;
    use Flavor_Module_Notifications_Trait;
    use Flavor_Module_Integration_Provider;
    use Flavor_Encuestas_Features;

    /** @var string Version del modulo */
    const VERSION = '2.0.0';

    /** @var array Tipos de reaccion permitidos */
    const TIPOS_REACCION = ['me_gusta', 'me_encanta', 'me_divierte', 'me_entristece', 'me_enfada'];

    /** @var array Tipos de notificacion */
    const TIPOS_NOTIFICACION = ['like', 'comentario', 'seguidor', 'mencion', 'compartido', 'historia'];

    /** @var array Puntos por tipo de acción */
    const PUNTOS_ACCION = [
        'publicacion' => 10,
        'comentario' => 5,
        'like_recibido' => 2,
        'like_dado' => 1,
        'compartido' => 8,
        'seguidor_ganado' => 3,
        'seguir_usuario' => 1,
        'historia' => 5,
        'mencion_recibida' => 2,
        'login_diario' => 2,
        'primera_publicacion' => 25,
        'verificacion_perfil' => 50,
        'invitar_usuario' => 15,
        'badge_obtenido' => 10,
    ];

    /** @var array Niveles de reputación con puntos mínimos */
    const NIVELES_REPUTACION = [
        'nuevo' => ['min' => 0, 'label' => 'Nuevo', 'icono' => '🌱', 'color' => '#9ca3af'],
        'activo' => ['min' => 50, 'label' => 'Activo', 'icono' => '⭐', 'color' => '#3b82f6'],
        'contribuidor' => ['min' => 200, 'label' => 'Contribuidor', 'icono' => '🌟', 'color' => '#8b5cf6'],
        'experto' => ['min' => 500, 'label' => 'Experto', 'icono' => '💫', 'color' => '#f59e0b'],
        'lider' => ['min' => 1000, 'label' => 'Líder', 'icono' => '🏆', 'color' => '#ef4444'],
        'embajador' => ['min' => 2500, 'label' => 'Embajador', 'icono' => '👑', 'color' => '#10b981'],
        'leyenda' => ['min' => 5000, 'label' => 'Leyenda', 'icono' => '🔥', 'color' => '#ec4899'],
    ];

    /**
     * Constructor
     */
    public function __construct() {
        $this->id = 'red_social';
        $this->name = 'Red Social Comunitaria'; // Translation loaded on init
        $this->description = 'Red social alternativa sin publicidad, centrada en la comunidad y sus intereses.'; // Translation loaded on init

        parent::__construct();

        // Admin pages
        add_action('admin_menu', [$this, 'registrar_paginas_admin']);
    }

    /**
     * {@inheritdoc}
     */
    public function can_activate() {
        global $wpdb;
        $tabla_publicaciones = $wpdb->prefix . 'flavor_social_publicaciones';
        return Flavor_Chat_Helpers::tabla_existe($tabla_publicaciones);
    }

    /**
     * {@inheritdoc}
     */
    public function get_activation_error() {
        if (!$this->can_activate()) {
            return __('Las tablas de Red Social no estan creadas. Se crearan automaticamente al activar.', FLAVOR_PLATFORM_TEXT_DOMAIN);
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
    public function get_table_schema() {
        global $wpdb;
        $charset_collate = $wpdb->get_charset_collate();

        $tabla_publicaciones = $wpdb->prefix . 'flavor_social_publicaciones';
        $tabla_comentarios = $wpdb->prefix . 'flavor_social_comentarios';
        $tabla_reacciones = $wpdb->prefix . 'flavor_social_reacciones';
        $tabla_seguimientos = $wpdb->prefix . 'flavor_social_seguimientos';
        $tabla_hashtags = $wpdb->prefix . 'flavor_social_hashtags';
        $tabla_hashtags_posts = $wpdb->prefix . 'flavor_social_hashtags_posts';
        $tabla_historias = $wpdb->prefix . 'flavor_social_historias';
        $tabla_notificaciones = $wpdb->prefix . 'flavor_social_notificaciones';
        $tabla_guardados = $wpdb->prefix . 'flavor_social_guardados';
        $tabla_perfiles = $wpdb->prefix . 'flavor_social_perfiles';
        $tabla_engagement = $wpdb->prefix . 'flavor_social_engagement';

        return [
            $tabla_publicaciones => "CREATE TABLE $tabla_publicaciones (
                id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
                autor_id bigint(20) unsigned NOT NULL,
                contenido text NOT NULL,
                tipo enum('texto','imagen','video','enlace','evento','compartido') DEFAULT 'texto',
                adjuntos longtext DEFAULT NULL COMMENT 'JSON con URLs de archivos',
                visibilidad enum('publica','comunidad','seguidores','privada') DEFAULT 'comunidad',
                ubicacion varchar(255) DEFAULT NULL,
                estado enum('borrador','publicado','moderacion','oculto','eliminado') DEFAULT 'publicado',
                publicacion_original_id bigint(20) unsigned DEFAULT NULL,
                es_fijado tinyint(1) DEFAULT 0,
                me_gusta int(11) DEFAULT 0,
                comentarios int(11) DEFAULT 0,
                compartidos int(11) DEFAULT 0,
                vistas int(11) DEFAULT 0,
                fecha_publicacion datetime DEFAULT CURRENT_TIMESTAMP,
                fecha_actualizacion datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                PRIMARY KEY (id),
                KEY autor_id (autor_id),
                KEY estado (estado),
                KEY fecha_publicacion (fecha_publicacion),
                KEY visibilidad (visibilidad),
                FULLTEXT KEY contenido (contenido)
            ) $charset_collate;",

            $tabla_comentarios => "CREATE TABLE $tabla_comentarios (
                id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
                publicacion_id bigint(20) unsigned NOT NULL,
                autor_id bigint(20) unsigned NOT NULL,
                comentario_padre_id bigint(20) unsigned DEFAULT NULL,
                contenido text NOT NULL,
                me_gusta int(11) DEFAULT 0,
                estado enum('publicado','moderacion','oculto','eliminado') DEFAULT 'publicado',
                fecha_creacion datetime DEFAULT CURRENT_TIMESTAMP,
                fecha_actualizacion datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                PRIMARY KEY (id),
                KEY publicacion_id (publicacion_id),
                KEY autor_id (autor_id),
                KEY comentario_padre_id (comentario_padre_id),
                KEY estado (estado)
            ) $charset_collate;",

            $tabla_reacciones => "CREATE TABLE $tabla_reacciones (
                id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
                publicacion_id bigint(20) unsigned DEFAULT NULL,
                comentario_id bigint(20) unsigned DEFAULT NULL,
                usuario_id bigint(20) unsigned NOT NULL,
                tipo enum('me_gusta','me_encanta','me_divierte','me_entristece','me_enfada') DEFAULT 'me_gusta',
                fecha_creacion datetime DEFAULT CURRENT_TIMESTAMP,
                PRIMARY KEY (id),
                UNIQUE KEY publicacion_usuario (publicacion_id, usuario_id),
                UNIQUE KEY comentario_usuario (comentario_id, usuario_id),
                KEY usuario_id (usuario_id),
                KEY tipo (tipo)
            ) $charset_collate;",

            $tabla_seguimientos => "CREATE TABLE $tabla_seguimientos (
                id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
                seguidor_id bigint(20) unsigned NOT NULL,
                seguido_id bigint(20) unsigned NOT NULL,
                notificaciones_activas tinyint(1) DEFAULT 1,
                fecha_seguimiento datetime DEFAULT CURRENT_TIMESTAMP,
                PRIMARY KEY (id),
                UNIQUE KEY seguidor_seguido (seguidor_id, seguido_id),
                KEY seguido_id (seguido_id),
                KEY fecha_seguimiento (fecha_seguimiento)
            ) $charset_collate;",

            $tabla_hashtags => "CREATE TABLE $tabla_hashtags (
                id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
                hashtag varchar(100) NOT NULL,
                total_usos int(11) DEFAULT 0,
                fecha_creacion datetime DEFAULT CURRENT_TIMESTAMP,
                fecha_ultimo_uso datetime DEFAULT CURRENT_TIMESTAMP,
                PRIMARY KEY (id),
                UNIQUE KEY hashtag (hashtag),
                KEY total_usos (total_usos)
            ) $charset_collate;",

            $tabla_hashtags_posts => "CREATE TABLE $tabla_hashtags_posts (
                id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
                hashtag_id bigint(20) unsigned NOT NULL,
                publicacion_id bigint(20) unsigned NOT NULL,
                fecha_creacion datetime DEFAULT CURRENT_TIMESTAMP,
                PRIMARY KEY (id),
                UNIQUE KEY hashtag_publicacion (hashtag_id, publicacion_id),
                KEY publicacion_id (publicacion_id)
            ) $charset_collate;",

            $tabla_historias => "CREATE TABLE $tabla_historias (
                id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
                autor_id bigint(20) unsigned NOT NULL,
                tipo enum('imagen','video','texto') DEFAULT 'imagen',
                contenido_url varchar(500) DEFAULT NULL,
                texto text DEFAULT NULL,
                color_fondo varchar(20) DEFAULT NULL,
                vistas int(11) DEFAULT 0,
                fecha_creacion datetime DEFAULT CURRENT_TIMESTAMP,
                fecha_expiracion datetime NOT NULL,
                PRIMARY KEY (id),
                KEY autor_id (autor_id),
                KEY fecha_expiracion (fecha_expiracion)
            ) $charset_collate;",

            $tabla_notificaciones => "CREATE TABLE $tabla_notificaciones (
                id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
                usuario_id bigint(20) unsigned NOT NULL,
                actor_id bigint(20) unsigned NOT NULL,
                tipo enum('like','comentario','seguidor','mencion','compartido','historia') NOT NULL,
                referencia_id bigint(20) unsigned DEFAULT NULL,
                referencia_tipo varchar(50) DEFAULT NULL,
                mensaje text DEFAULT NULL,
                leida tinyint(1) DEFAULT 0,
                fecha_creacion datetime DEFAULT CURRENT_TIMESTAMP,
                PRIMARY KEY (id),
                KEY usuario_id (usuario_id),
                KEY leida (leida),
                KEY fecha_creacion (fecha_creacion)
            ) $charset_collate;",

            $tabla_guardados => "CREATE TABLE $tabla_guardados (
                id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
                usuario_id bigint(20) unsigned NOT NULL,
                publicacion_id bigint(20) unsigned NOT NULL,
                fecha_guardado datetime DEFAULT CURRENT_TIMESTAMP,
                PRIMARY KEY (id),
                UNIQUE KEY usuario_publicacion (usuario_id, publicacion_id),
                KEY publicacion_id (publicacion_id)
            ) $charset_collate;",

            $tabla_perfiles => "CREATE TABLE $tabla_perfiles (
                id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
                usuario_id bigint(20) unsigned NOT NULL,
                nombre_completo varchar(255) DEFAULT NULL,
                bio text DEFAULT NULL,
                ubicacion varchar(255) DEFAULT NULL,
                sitio_web varchar(255) DEFAULT NULL,
                fecha_nacimiento date DEFAULT NULL,
                cover_url varchar(500) DEFAULT NULL,
                es_verificado tinyint(1) DEFAULT 0,
                es_privado tinyint(1) DEFAULT 0,
                total_publicaciones int(11) DEFAULT 0,
                total_seguidores int(11) DEFAULT 0,
                total_siguiendo int(11) DEFAULT 0,
                fecha_creacion datetime DEFAULT CURRENT_TIMESTAMP,
                fecha_actualizacion datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                PRIMARY KEY (id),
                UNIQUE KEY usuario_id (usuario_id)
            ) $charset_collate;",

            $tabla_engagement => "CREATE TABLE $tabla_engagement (
                id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
                usuario_id bigint(20) unsigned NOT NULL,
                autor_id bigint(20) unsigned NOT NULL,
                tipo_interaccion enum('like','comentario','compartido','guardado','clic','tiempo_lectura') NOT NULL,
                contenido_id bigint(20) unsigned DEFAULT NULL,
                peso decimal(5,2) DEFAULT 1.00,
                fecha_interaccion datetime DEFAULT CURRENT_TIMESTAMP,
                PRIMARY KEY (id),
                KEY usuario_autor (usuario_id, autor_id),
                KEY autor_id (autor_id),
                KEY fecha_interaccion (fecha_interaccion),
                KEY tipo_interaccion (tipo_interaccion)
            ) $charset_collate;",

            $wpdb->prefix . 'flavor_social_reputacion' => "CREATE TABLE {$wpdb->prefix}flavor_social_reputacion (
                id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
                usuario_id bigint(20) unsigned NOT NULL,
                puntos_totales int(11) DEFAULT 0,
                nivel varchar(50) DEFAULT 'nuevo',
                puntos_semana int(11) DEFAULT 0,
                puntos_mes int(11) DEFAULT 0,
                racha_dias int(11) DEFAULT 0,
                ultima_actividad datetime DEFAULT NULL,
                fecha_actualizacion datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                PRIMARY KEY (id),
                UNIQUE KEY usuario_id (usuario_id),
                KEY nivel (nivel),
                KEY puntos_totales (puntos_totales)
            ) $charset_collate;",

            $wpdb->prefix . 'flavor_social_badges' => "CREATE TABLE {$wpdb->prefix}flavor_social_badges (
                id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
                nombre varchar(100) NOT NULL,
                slug varchar(100) NOT NULL,
                descripcion text,
                icono varchar(255) DEFAULT NULL,
                color varchar(20) DEFAULT '#3b82f6',
                categoria enum('participacion','creacion','comunidad','especial','temporal') DEFAULT 'participacion',
                puntos_requeridos int(11) DEFAULT 0,
                condicion_especial text DEFAULT NULL COMMENT 'JSON con condiciones especiales',
                es_unico tinyint(1) DEFAULT 0 COMMENT 'Solo se puede obtener una vez',
                activo tinyint(1) DEFAULT 1,
                orden int(11) DEFAULT 0,
                fecha_creacion datetime DEFAULT CURRENT_TIMESTAMP,
                PRIMARY KEY (id),
                UNIQUE KEY slug (slug),
                KEY categoria (categoria),
                KEY activo (activo)
            ) $charset_collate;",

            $wpdb->prefix . 'flavor_social_usuario_badges' => "CREATE TABLE {$wpdb->prefix}flavor_social_usuario_badges (
                id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
                usuario_id bigint(20) unsigned NOT NULL,
                badge_id bigint(20) unsigned NOT NULL,
                fecha_obtenido datetime DEFAULT CURRENT_TIMESTAMP,
                destacado tinyint(1) DEFAULT 0 COMMENT 'Mostrar en perfil',
                PRIMARY KEY (id),
                UNIQUE KEY usuario_badge (usuario_id, badge_id),
                KEY badge_id (badge_id),
                KEY fecha_obtenido (fecha_obtenido)
            ) $charset_collate;",

            $wpdb->prefix . 'flavor_social_historial_puntos' => "CREATE TABLE {$wpdb->prefix}flavor_social_historial_puntos (
                id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
                usuario_id bigint(20) unsigned NOT NULL,
                puntos int(11) NOT NULL,
                tipo_accion varchar(50) NOT NULL,
                descripcion varchar(255) DEFAULT NULL,
                referencia_id bigint(20) unsigned DEFAULT NULL,
                referencia_tipo varchar(50) DEFAULT NULL,
                fecha_creacion datetime DEFAULT CURRENT_TIMESTAMP,
                PRIMARY KEY (id),
                KEY usuario_id (usuario_id),
                KEY tipo_accion (tipo_accion),
                KEY fecha_creacion (fecha_creacion)
            ) $charset_collate;"
        ];
    }

    /**
     * {@inheritdoc}
     */
    protected function get_default_settings() {
        return [
            'disponible_app' => 'cliente',
            'publicaciones_publicas' => true,
            'requiere_moderacion' => false,
            'max_caracteres_publicacion' => 5000,
            'permite_imagenes' => true,
            'permite_videos' => true,
            'max_imagenes_por_post' => 10,
            'permite_hashtags' => true,
            'permite_menciones' => true,
            'permite_compartir' => true,
            'permite_historias' => true,
            'duracion_historia_horas' => 24,
            'timeline_algoritmo' => 'cronologico',
            'notificaciones_email' => true,
            'max_seguidores_sugeridos' => 10,
        ];
    }

    /**
     * Define el tipo de contenido que ofrece este modulo
     */
    protected function get_integration_content_type() {
        return [
            'id'         => 'publicaciones',
            'label'      => __('Publicaciones', FLAVOR_PLATFORM_TEXT_DOMAIN),
            'icon'       => 'dashicons-share',
            'table'      => 'flavor_social_publicaciones',
            'capability' => 'edit_posts',
        ];
    }

    /**
     * Define los tabs que este módulo inyecta en otros módulos
     *
     * Cuando red-social está activo, puede mostrar un tab de "Publicaciones"
     * en los dashboards de grupos de consumo, eventos, comunidades, etc.
     *
     * @return array Configuración de tabs por módulo destino
     */
    public function get_tab_integrations() {
        return [
            // Tab de publicaciones para Grupos de Consumo
            'grupos_consumo' => [
                'id'       => 'posts-grupo',
                'label'    => __('Publicaciones', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'icon'     => 'dashicons-share',
                'content'  => '[flavor_social_feed entidad="grupo_consumo" entidad_id="{entity_id}"]',
                'priority' => 90,
                'badge'    => function($contexto) {
                    return $this->contar_publicaciones_entidad('grupo_consumo', $contexto['entity_id']);
                },
            ],

            // Tab de publicaciones para Eventos
            'eventos' => [
                'id'       => 'posts-evento',
                'label'    => __('Publicaciones', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'icon'     => 'dashicons-share',
                'content'  => '[flavor_social_feed entidad="evento" entidad_id="{entity_id}"]',
                'priority' => 90,
                'badge'    => function($contexto) {
                    return $this->contar_publicaciones_entidad('evento', $contexto['entity_id']);
                },
            ],

            // Tab de publicaciones para Comunidades
            'comunidades' => [
                'id'       => 'feed-comunidad',
                'label'    => __('Feed', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'icon'     => 'dashicons-share',
                'content'  => '[flavor_social_feed entidad="comunidad" entidad_id="{entity_id}"]',
                'priority' => 80,
                'badge'    => function($contexto) {
                    return $this->contar_publicaciones_entidad('comunidad', $contexto['entity_id']);
                },
            ],

            'incidencias' => [
                'id'       => 'feed-incidencia',
                'label'    => __('Actividad', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'icon'     => 'dashicons-share',
                'content'  => '[flavor_social_feed entidad="incidencia" entidad_id="{entity_id}"]',
                'priority' => 85,
                'badge'    => function($contexto) {
                    return $this->contar_publicaciones_entidad('incidencia', $contexto['entity_id']);
                },
            ],

            'documentacion_legal' => [
                'id'       => 'feed-documento-legal',
                'label'    => __('Actividad', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'icon'     => 'dashicons-share',
                'content'  => '[flavor_social_feed entidad="documento_legal" entidad_id="{entity_id}"]',
                'priority' => 85,
                'badge'    => function($contexto) {
                    return $this->contar_publicaciones_entidad('documento_legal', $contexto['entity_id']);
                },
            ],

            'presupuestos_participativos' => [
                'id'       => 'feed-pp-proyecto',
                'label'    => __('Actividad', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'icon'     => 'dashicons-share',
                'content'  => '[flavor_social_feed entidad="pp_proyecto" entidad_id="{entity_id}"]',
                'priority' => 85,
                'badge'    => function($contexto) {
                    return $this->contar_publicaciones_entidad('pp_proyecto', $contexto['entity_id']);
                },
            ],

            'saberes_ancestrales' => [
                'id'       => 'feed-saber-ancestral',
                'label'    => __('Actividad', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'icon'     => 'dashicons-share',
                'content'  => '[flavor_social_feed entidad="saber_ancestral" entidad_id="{entity_id}"]',
                'priority' => 85,
                'badge'    => function($contexto) {
                    return $this->contar_publicaciones_entidad('saber_ancestral', $contexto['entity_id']);
                },
            ],

            'transparencia' => [
                'id'       => 'feed-documento-transparencia',
                'label'    => __('Actividad', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'icon'     => 'dashicons-share',
                'content'  => '[flavor_social_feed entidad="documento_transparencia" entidad_id="{entity_id}"]',
                'priority' => 85,
                'badge'    => function($contexto) {
                    return $this->contar_publicaciones_entidad('documento_transparencia', $contexto['entity_id']);
                },
            ],

            'avisos_municipales' => [
                'id'       => 'feed-aviso-municipal',
                'label'    => __('Actividad', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'icon'     => 'dashicons-share',
                'content'  => '[flavor_social_feed entidad="aviso_municipal" entidad_id="{entity_id}"]',
                'priority' => 85,
                'badge'    => function($contexto) {
                    return $this->contar_publicaciones_entidad('aviso_municipal', $contexto['entity_id']);
                },
            ],

            'economia_don' => [
                'id'       => 'feed-economia-don',
                'label'    => __('Actividad', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'icon'     => 'dashicons-share',
                'content'  => '[flavor_social_feed entidad="economia_don" entidad_id="{entity_id}"]',
                'priority' => 85,
                'badge'    => function($contexto) {
                    return $this->contar_publicaciones_entidad('economia_don', $contexto['entity_id']);
                },
            ],

            'advertising' => [
                'id'       => 'feed-advertising-ad',
                'label'    => __('Actividad', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'icon'     => 'dashicons-share',
                'content'  => '[flavor_social_feed entidad="advertising_ad" entidad_id="{entity_id}"]',
                'priority' => 85,
                'badge'    => function($contexto) {
                    return $this->contar_publicaciones_entidad('advertising_ad', $contexto['entity_id']);
                },
            ],

            'radio' => [
                'id'       => 'feed-radio-programa',
                'label'    => __('Actividad', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'icon'     => 'dashicons-share',
                'content'  => '[flavor_social_feed entidad="radio_programa" entidad_id="{entity_id}"]',
                'priority' => 85,
                'badge'    => function($contexto) {
                    return $this->contar_publicaciones_entidad('radio_programa', $contexto['entity_id']);
                },
            ],

            'energia_comunitaria' => [
                'id'       => 'feed-energia-comunidad',
                'label'    => __('Actividad', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'icon'     => 'dashicons-share',
                'content'  => '[flavor_social_feed entidad="energia_comunidad" entidad_id="{entity_id}"]',
                'priority' => 85,
                'badge'    => function($contexto) {
                    return $this->contar_publicaciones_entidad('energia_comunidad', $contexto['entity_id']);
                },
            ],

            // Tab de publicaciones para Talleres
            'talleres' => [
                'id'       => 'posts-taller',
                'label'    => __('Publicaciones', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'icon'     => 'dashicons-share',
                'content'  => '[flavor_social_feed entidad="taller" entidad_id="{entity_id}"]',
                'priority' => 90,
                'badge'    => function($contexto) {
                    return $this->contar_publicaciones_entidad('taller', $contexto['entity_id']);
                },
            ],

            // Tab de publicaciones para Trabajo Digno
            'trabajo_digno' => [
                'id'       => 'posts-oferta-trabajo',
                'label'    => __('Publicaciones', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'icon'     => 'dashicons-share',
                'content'  => '[flavor_social_feed entidad="trabajo_digno_oferta" entidad_id="{entity_id}"]',
                'priority' => 90,
                'badge'    => function($contexto) {
                    return $this->contar_publicaciones_entidad('trabajo_digno_oferta', $contexto['entity_id']);
                },
            ],

            // Tab de publicaciones para Huertos Urbanos
            'huertos_urbanos' => [
                'id'       => 'posts-huerto',
                'label'    => __('Actividad', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'icon'     => 'dashicons-share',
                'content'  => '[flavor_social_feed entidad="huerto" entidad_id="{entity_id}"]',
                'priority' => 90,
                'badge'    => function($contexto) {
                    return $this->contar_publicaciones_entidad('huerto', $contexto['entity_id']);
                },
            ],
            'participacion' => [
                'id'       => 'posts-propuesta',
                'label'    => __('Actividad', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'icon'     => 'dashicons-share',
                'content'  => '[flavor_social_feed entidad="participacion_propuesta" entidad_id="{entity_id}"]',
                'priority' => 90,
                'badge'    => function($contexto) {
                    return $this->contar_publicaciones_entidad('participacion_propuesta', $contexto['entity_id']);
                },
            ],
            'economia_suficiencia' => [
                'id'       => 'posts-recurso-suficiencia',
                'label'    => __('Actividad', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'icon'     => 'dashicons-share',
                'content'  => '[flavor_social_feed entidad="es_recurso" entidad_id="{entity_id}"]',
                'priority' => 90,
                'badge'    => function($contexto) {
                    return $this->contar_publicaciones_entidad('es_recurso', $contexto['entity_id']);
                },
            ],
            'justicia_restaurativa' => [
                'id'       => 'posts-proceso-restaurativo',
                'label'    => __('Actividad', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'icon'     => 'dashicons-share',
                'content'  => '[flavor_social_feed entidad="jr_proceso" entidad_id="{entity_id}"]',
                'priority' => 90,
                'badge'    => function($contexto) {
                    return $this->contar_publicaciones_entidad('jr_proceso', $contexto['entity_id']);
                },
            ],

            // Tab de publicaciones para Colectivos
            'colectivos' => [
                'id'       => 'posts-colectivo',
                'label'    => __('Publicaciones', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'icon'     => 'dashicons-share',
                'content'  => '[flavor_social_feed entidad="colectivo" entidad_id="{entity_id}"]',
                'priority' => 90,
                'badge'    => function($contexto) {
                    return $this->contar_publicaciones_entidad('colectivo', $contexto['entity_id']);
                },
            ],

            // Tab para Círculos de Cuidados
            'circulos_cuidados' => [
                'id'       => 'posts-circulo',
                'label'    => __('Actividad', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'icon'     => 'dashicons-share',
                'content'  => '[flavor_social_feed entidad="circulo" entidad_id="{entity_id}"]',
                'priority' => 90,
                'badge'    => function($contexto) {
                    return $this->contar_publicaciones_entidad('circulo', $contexto['entity_id']);
                },
            ],

            // Tab para Banco de Tiempo
            'banco_tiempo' => [
                'id'       => 'posts-servicio',
                'label'    => __('Publicaciones', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'icon'     => 'dashicons-share',
                'content'  => '[flavor_social_feed entidad="servicio_bt" entidad_id="{entity_id}"]',
                'priority' => 90,
                'badge'    => function($contexto) {
                    return $this->contar_publicaciones_entidad('servicio_bt', $contexto['entity_id']);
                },
            ],
        ];
    }

    /**
     * Cuenta publicaciones asociadas a una entidad
     *
     * @param string $tipo_entidad Tipo de entidad
     * @param int    $entidad_id   ID de la entidad
     * @return int Número de publicaciones
     */
    public function contar_publicaciones_entidad($tipo_entidad, $entidad_id) {
        global $wpdb;

        if (!$entidad_id) {
            return 0;
        }

        $tabla = $wpdb->prefix . 'flavor_social_publicaciones';

        // Verificar si la tabla existe
        if (!Flavor_Chat_Helpers::tabla_existe($tabla)) {
            return 0;
        }

        $total = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM {$tabla}
             WHERE entidad_tipo = %s AND entidad_id = %d AND estado = 'publicado'",
            $tipo_entidad,
            $entidad_id
        ));

        return intval($total);
    }

    /**
     * {@inheritdoc}
     */
    public function init() {
        // Registrar como proveedor de integraciones
        $this->register_as_integration_provider();

        add_action('init', [$this, 'maybe_create_pages']);
        // Registrar en panel de administración unificado
        
        // Cargar Dashboard Tab
        $this->inicializar_dashboard_tab();
        $this->registrar_en_panel_unificado();

        add_action('init', [$this, 'maybe_create_tables']);
        add_action('init', [$this, 'register_shortcodes']);
        add_action('wp_enqueue_scripts', [$this, 'enqueue_assets']);

        // AJAX handlers
        add_action('wp_ajax_rs_crear_publicacion', [$this, 'ajax_crear_publicacion']);
        add_action('wp_ajax_rs_toggle_like', [$this, 'ajax_toggle_like']);
        add_action('wp_ajax_rs_crear_comentario', [$this, 'ajax_crear_comentario']);
        add_action('wp_ajax_rs_obtener_comentarios', [$this, 'ajax_obtener_comentarios']);
        add_action('wp_ajax_rs_like_comentario', [$this, 'ajax_like_comentario']);
        add_action('wp_ajax_rs_toggle_seguir', [$this, 'ajax_toggle_seguir']);
        add_action('wp_ajax_rs_cargar_feed', [$this, 'ajax_cargar_feed']);
        add_action('wp_ajax_rs_buscar_usuarios', [$this, 'ajax_buscar_usuarios']);
        add_action('wp_ajax_rs_obtener_historias', [$this, 'ajax_obtener_historias']);
        add_action('wp_ajax_rs_crear_historia', [$this, 'ajax_crear_historia']);
        add_action('wp_ajax_rs_guardar_post', [$this, 'ajax_guardar_post']);
        add_action('wp_ajax_rs_obtener_notificaciones', [$this, 'ajax_obtener_notificaciones']);
        add_action('wp_ajax_rs_marcar_notificacion_leida', [$this, 'ajax_marcar_notificacion_leida']);
        add_action('wp_ajax_rs_obtener_perfil', [$this, 'ajax_obtener_perfil']);
        add_action('wp_ajax_rs_actualizar_perfil', [$this, 'ajax_actualizar_perfil']);
        add_action('wp_ajax_rs_eliminar_publicacion', [$this, 'ajax_eliminar_publicacion']);
        add_action('wp_ajax_rs_reportar_contenido', [$this, 'ajax_reportar_contenido']);

        // REST API
        add_action('rest_api_init', [$this, 'register_rest_routes']);

        // Cron para limpiar historias expiradas
        add_action('rs_limpiar_historias_expiradas', [$this, 'limpiar_historias_expiradas']);
        if (!wp_next_scheduled('rs_limpiar_historias_expiradas')) {
            wp_schedule_event(time(), 'hourly', 'rs_limpiar_historias_expiradas');
        }

        // Integrar funcionalidades de encuestas
        $this->init_encuestas_features('red_social');
    }

    /**
     * Registra los shortcodes
     */
    public function register_shortcodes() {
        add_shortcode('rs_feed', [$this, 'shortcode_feed']);
        add_shortcode('rs_perfil', [$this, 'shortcode_perfil']);
        add_shortcode('rs_explorar', [$this, 'shortcode_explorar']);
        add_shortcode('rs_crear_publicacion', [$this, 'shortcode_crear_publicacion']);
        add_shortcode('rs_notificaciones', [$this, 'shortcode_notificaciones']);
        add_shortcode('rs_historias', [$this, 'shortcode_historias']);
        add_shortcode('rs_reputacion', [$this, 'shortcode_reputacion']);
        add_shortcode('rs_ranking', [$this, 'shortcode_ranking']);
        add_shortcode('rs_badges', [$this, 'shortcode_badges']);
        add_shortcode('rs_mi_actividad', [$this, 'shortcode_mi_actividad']);

        // Shortcode de integración para tabs de otros módulos
        add_shortcode('flavor_social_feed', [$this, 'shortcode_feed_integrado']);
    }

    /**
     * Encola assets
     */
    public function enqueue_assets() {
        $modulo_url = plugin_dir_url(__FILE__);

        wp_enqueue_style(
            'flavor-red-social',
            $modulo_url . 'assets/css/red-social.css',
            [],
            self::VERSION
        );

        wp_enqueue_script(
            'flavor-red-social',
            $modulo_url . 'assets/js/red-social.js',
            ['jquery'],
            self::VERSION,
            true
        );

        wp_localize_script('flavor-red-social', 'flavorRedSocial', [
            'ajaxUrl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('rs_nonce'),
            'userId' => get_current_user_id(),
            'maxCaracteres' => $this->get_setting('max_caracteres_publicacion'),
            'maxImagenes' => $this->get_setting('max_imagenes_por_post'),
        ]);
    }

    /**
     * Crea las tablas necesarias
     */
    private function create_tables() {
        $esquemas = $this->get_table_schema();

        if (empty($esquemas)) {
            return;
        }

        require_once ABSPATH . 'wp-admin/includes/upgrade.php';

        foreach ($esquemas as $tabla => $sql) {
            dbDelta($sql);
        }
    }

    /**
     * Crea las tablas si no existen usando SQL directo (más confiable que dbDelta)
     */
    public function maybe_create_tables() {
        global $wpdb;

        $esquemas = $this->get_table_schema();

        if (empty($esquemas)) {
            return;
        }

        foreach ($esquemas as $tabla => $sql) {
            // Verificar si la tabla existe
            if (Flavor_Chat_Helpers::tabla_existe($tabla)) {
                continue;
            }

            // Convertir CREATE TABLE a CREATE TABLE IF NOT EXISTS para evitar errores
            $sql = str_replace('CREATE TABLE ', 'CREATE TABLE IF NOT EXISTS ', $sql);

            // Ejecutar con query directo en lugar de dbDelta
            $wpdb->query($sql);
        }
    }

    // ========================================
    // AJAX Handlers
    // ========================================

    /**
     * AJAX: Crear publicacion
     */
    public function ajax_crear_publicacion() {
        check_ajax_referer('rs_nonce', 'nonce');

        $usuario_id = get_current_user_id();
        if (!$usuario_id) {
            wp_send_json_error(['message' => __('Debes iniciar sesion', FLAVOR_PLATFORM_TEXT_DOMAIN)]);
        }

        $contenido = sanitize_textarea_field($_POST['contenido'] ?? '');
        $visibilidad = sanitize_text_field($_POST['visibilidad'] ?? 'comunidad');
        $tipo = 'texto';
        $adjuntos_json = null;

        if (empty($contenido) && empty($_FILES['adjuntos'])) {
            wp_send_json_error(['message' => __('El contenido no puede estar vacío', FLAVOR_PLATFORM_TEXT_DOMAIN)]);
        }

        $max_caracteres = $this->get_setting('max_caracteres_publicacion');
        if (mb_strlen($contenido) > $max_caracteres) {
            wp_send_json_error(['message' => "Maximo {$max_caracteres} caracteres"]);
        }

        // Procesar adjuntos
        if (!empty($_FILES['adjuntos'])) {
            $adjuntos = $this->procesar_adjuntos($_FILES['adjuntos']);
            if (!empty($adjuntos)) {
                $adjuntos_json = wp_json_encode($adjuntos);
                $tipo = 'imagen';
            }
        }

        // Moderacion
        $estado = 'publicado';
        if ($this->get_setting('requiere_moderacion')) {
            $estado = 'moderacion';
        }

        global $wpdb;
        $tabla_publicaciones = $wpdb->prefix . 'flavor_social_publicaciones';

        $resultado_insercion = $wpdb->insert($tabla_publicaciones, [
            'autor_id' => $usuario_id,
            'contenido' => $contenido,
            'tipo' => $tipo,
            'adjuntos' => $adjuntos_json,
            'visibilidad' => $visibilidad,
            'estado' => $estado,
            'fecha_publicacion' => current_time('mysql'),
        ], ['%d', '%s', '%s', '%s', '%s', '%s', '%s']);

        if ($resultado_insercion === false) {
            wp_send_json_error(['message' => __('Error al crear la publicación', FLAVOR_PLATFORM_TEXT_DOMAIN)]);
        }

        $publicacion_id = $wpdb->insert_id;

        // Procesar hashtags
        $this->procesar_hashtags($contenido, $publicacion_id);

        // Procesar menciones
        $this->procesar_menciones($contenido, $publicacion_id, $usuario_id);

        // Actualizar contador de perfil
        $this->actualizar_contador_perfil($usuario_id, 'total_publicaciones', 1);

        // Añadir puntos de reputación
        $total_publicaciones = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM $tabla_publicaciones WHERE autor_id = %d AND estado = 'publicado'",
            $usuario_id
        ));
        if ((int) $total_publicaciones === 1) {
            $this->agregar_puntos_reputacion($usuario_id, 'primera_publicacion', $publicacion_id, 'publicacion');
        } else {
            $this->agregar_puntos_reputacion($usuario_id, 'publicacion', $publicacion_id, 'publicacion');
        }

        // Obtener HTML de la publicacion
        $publicacion_html = $this->renderizar_publicacion($publicacion_id);

        wp_send_json_success([
            'message' => __('Publicacion creada', FLAVOR_PLATFORM_TEXT_DOMAIN),
            'publicacion_id' => $publicacion_id,
            'html' => $publicacion_html,
        ]);
    }

    /**
     * AJAX: Toggle like
     */
    public function ajax_toggle_like() {
        check_ajax_referer('rs_nonce', 'nonce');

        $usuario_id = get_current_user_id();
        if (!$usuario_id) {
            wp_send_json_error(['message' => __('Debes iniciar sesion', FLAVOR_PLATFORM_TEXT_DOMAIN)]);
        }

        $publicacion_id = absint($_POST['publicacion_id'] ?? 0);
        $tipo_reaccion = sanitize_text_field($_POST['tipo'] ?? 'me_gusta');

        if (!$publicacion_id) {
            wp_send_json_error(['message' => __('Publicación no válida', FLAVOR_PLATFORM_TEXT_DOMAIN)]);
        }

        if (!in_array($tipo_reaccion, self::TIPOS_REACCION)) {
            $tipo_reaccion = 'me_gusta';
        }

        global $wpdb;
        $tabla_reacciones = $wpdb->prefix . 'flavor_social_reacciones';
        $tabla_publicaciones = $wpdb->prefix . 'flavor_social_publicaciones';

        // Verificar si ya existe la reaccion
        $reaccion_existente = $wpdb->get_row($wpdb->prepare(
            "SELECT id FROM $tabla_reacciones WHERE publicacion_id = %d AND usuario_id = %d",
            $publicacion_id,
            $usuario_id
        ));

        if ($reaccion_existente) {
            // Eliminar reaccion
            $wpdb->delete($tabla_reacciones, [
                'publicacion_id' => $publicacion_id,
                'usuario_id' => $usuario_id,
            ], ['%d', '%d']);

            $wpdb->query($wpdb->prepare(
                "UPDATE $tabla_publicaciones SET me_gusta = me_gusta - 1 WHERE id = %d AND me_gusta > 0",
                $publicacion_id
            ));

            $accion_realizada = 'eliminado';
        } else {
            // Crear reaccion
            $wpdb->insert($tabla_reacciones, [
                'publicacion_id' => $publicacion_id,
                'usuario_id' => $usuario_id,
                'tipo' => $tipo_reaccion,
                'fecha_creacion' => current_time('mysql'),
            ], ['%d', '%d', '%s', '%s']);

            $wpdb->query($wpdb->prepare(
                "UPDATE $tabla_publicaciones SET me_gusta = me_gusta + 1 WHERE id = %d",
                $publicacion_id
            ));

            // Notificar al autor
            $publicacion = $wpdb->get_row($wpdb->prepare(
                "SELECT autor_id FROM $tabla_publicaciones WHERE id = %d",
                $publicacion_id
            ));

            if ($publicacion && (int) $publicacion->autor_id !== $usuario_id) {
                $this->crear_notificacion($publicacion->autor_id, $usuario_id, 'like', $publicacion_id, 'publicacion');
                // Registrar engagement para feed inteligente
                $this->registrar_engagement($usuario_id, $publicacion->autor_id, 'like', $publicacion_id);
                // Puntos de reputación: autor recibe puntos por like
                $this->agregar_puntos_reputacion($publicacion->autor_id, 'like_recibido', $publicacion_id, 'publicacion');
            }
            // Puntos por dar like
            $this->agregar_puntos_reputacion($usuario_id, 'like_dado', $publicacion_id, 'publicacion');

            $accion_realizada = 'agregado';
        }

        $total_likes = $wpdb->get_var($wpdb->prepare(
            "SELECT me_gusta FROM $tabla_publicaciones WHERE id = %d",
            $publicacion_id
        ));

        wp_send_json_success([
            'accion' => $accion_realizada,
            'total' => (int) $total_likes,
        ]);
    }

    /**
     * AJAX: Crear comentario
     */
    public function ajax_crear_comentario() {
        check_ajax_referer('rs_nonce', 'nonce');

        $usuario_id = get_current_user_id();
        if (!$usuario_id) {
            wp_send_json_error(['message' => __('Debes iniciar sesion', FLAVOR_PLATFORM_TEXT_DOMAIN)]);
        }

        $publicacion_id = absint($_POST['publicacion_id'] ?? 0);
        $contenido = sanitize_textarea_field($_POST['contenido'] ?? '');
        $padre_id = absint($_POST['padre_id'] ?? 0);

        if (!$publicacion_id || empty($contenido)) {
            wp_send_json_error(['message' => __('Publicación y contenido son requeridos', FLAVOR_PLATFORM_TEXT_DOMAIN)]);
        }

        global $wpdb;
        $tabla_comentarios = $wpdb->prefix . 'flavor_social_comentarios';
        $tabla_publicaciones = $wpdb->prefix . 'flavor_social_publicaciones';

        $resultado_insercion = $wpdb->insert($tabla_comentarios, [
            'publicacion_id' => $publicacion_id,
            'autor_id' => $usuario_id,
            'comentario_padre_id' => $padre_id ?: null,
            'contenido' => $contenido,
            'estado' => 'publicado',
            'fecha_creacion' => current_time('mysql'),
        ], ['%d', '%d', '%d', '%s', '%s', '%s']);

        if ($resultado_insercion === false) {
            wp_send_json_error(['message' => __('Error al crear el comentario', FLAVOR_PLATFORM_TEXT_DOMAIN)]);
        }

        $comentario_id = $wpdb->insert_id;

        // Actualizar contador
        $wpdb->query($wpdb->prepare(
            "UPDATE $tabla_publicaciones SET comentarios = comentarios + 1 WHERE id = %d",
            $publicacion_id
        ));

        // Notificar al autor de la publicacion
        $publicacion = $wpdb->get_row($wpdb->prepare(
            "SELECT autor_id FROM $tabla_publicaciones WHERE id = %d",
            $publicacion_id
        ));

        if ($publicacion && (int) $publicacion->autor_id !== $usuario_id) {
            $this->crear_notificacion($publicacion->autor_id, $usuario_id, 'comentario', $publicacion_id, 'publicacion');
            // Registrar engagement para feed inteligente (comentarios tienen más peso)
            $this->registrar_engagement($usuario_id, $publicacion->autor_id, 'comentario', $publicacion_id);
        }

        // Procesar menciones en comentario
        $this->procesar_menciones($contenido, $publicacion_id, $usuario_id);

        // Puntos de reputación por comentar
        $this->agregar_puntos_reputacion($usuario_id, 'comentario', $comentario_id, 'comentario');

        $comentario_html = $this->renderizar_comentario($comentario_id);

        wp_send_json_success([
            'comentario_id' => $comentario_id,
            'html' => $comentario_html,
        ]);
    }

    /**
     * AJAX: Obtener comentarios
     */
    public function ajax_obtener_comentarios() {
        check_ajax_referer('rs_nonce', 'nonce');

        $publicacion_id = absint($_POST['publicacion_id'] ?? 0);
        $limite = absint($_POST['limite'] ?? 10);
        $offset = absint($_POST['offset'] ?? 0);

        if (!$publicacion_id) {
            wp_send_json_error(['message' => __('Publicación no válida', FLAVOR_PLATFORM_TEXT_DOMAIN)]);
        }

        global $wpdb;
        $tabla_comentarios = $wpdb->prefix . 'flavor_social_comentarios';

        $comentarios = $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM $tabla_comentarios
            WHERE publicacion_id = %d AND estado = 'publicado' AND comentario_padre_id IS NULL
            ORDER BY fecha_creacion DESC
            LIMIT %d OFFSET %d",
            $publicacion_id,
            $limite,
            $offset
        ));

        $comentarios_html = '';
        foreach ($comentarios as $comentario) {
            $comentarios_html .= $this->renderizar_comentario($comentario->id);
        }

        $total_comentarios = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM $tabla_comentarios WHERE publicacion_id = %d AND estado = 'publicado'",
            $publicacion_id
        ));

        $seccion_comentarios_html = $this->get_seccion_comentarios_html($comentarios_html, $publicacion_id);

        wp_send_json_success([
            'html' => $seccion_comentarios_html,
            'total' => (int) $total_comentarios,
            'hay_mas' => ($offset + $limite) < $total_comentarios,
        ]);
    }

    /**
     * AJAX: Like comentario
     */
    public function ajax_like_comentario() {
        check_ajax_referer('rs_nonce', 'nonce');

        $usuario_id = get_current_user_id();
        if (!$usuario_id) {
            wp_send_json_error(['message' => __('Debes iniciar sesion', FLAVOR_PLATFORM_TEXT_DOMAIN)]);
        }

        $comentario_id = absint($_POST['comentario_id'] ?? 0);

        if (!$comentario_id) {
            wp_send_json_error(['message' => __('Debes iniciar sesion', FLAVOR_PLATFORM_TEXT_DOMAIN)]);
        }

        global $wpdb;
        $tabla_reacciones = $wpdb->prefix . 'flavor_social_reacciones';
        $tabla_comentarios = $wpdb->prefix . 'flavor_social_comentarios';

        $reaccion_existente = $wpdb->get_row($wpdb->prepare(
            "SELECT id FROM $tabla_reacciones WHERE comentario_id = %d AND usuario_id = %d",
            $comentario_id,
            $usuario_id
        ));

        if ($reaccion_existente) {
            $wpdb->delete($tabla_reacciones, [
                'comentario_id' => $comentario_id,
                'usuario_id' => $usuario_id,
            ], ['%d', '%d']);

            $wpdb->query($wpdb->prepare(
                "UPDATE $tabla_comentarios SET me_gusta = me_gusta - 1 WHERE id = %d AND me_gusta > 0",
                $comentario_id
            ));
        } else {
            $wpdb->insert($tabla_reacciones, [
                'comentario_id' => $comentario_id,
                'usuario_id' => $usuario_id,
                'tipo' => 'me_gusta',
                'fecha_creacion' => current_time('mysql'),
            ], ['%d', '%d', '%s', '%s']);

            $wpdb->query($wpdb->prepare(
                "UPDATE $tabla_comentarios SET me_gusta = me_gusta + 1 WHERE id = %d",
                $comentario_id
            ));
        }

        $total_likes = $wpdb->get_var($wpdb->prepare(
            "SELECT me_gusta FROM $tabla_comentarios WHERE id = %d",
            $comentario_id
        ));

        wp_send_json_success(['total' => (int) $total_likes]);
    }

    /**
     * AJAX: Toggle seguir
     */
    public function ajax_toggle_seguir() {
        check_ajax_referer('rs_nonce', 'nonce');

        $seguidor_id = get_current_user_id();
        if (!$seguidor_id) {
            wp_send_json_error(['message' => __('total_seguidores', FLAVOR_PLATFORM_TEXT_DOMAIN)]);
        }

        $seguido_id = absint($_POST['usuario_id'] ?? 0);

        if (!$seguido_id || $seguidor_id === $seguido_id) {
            wp_send_json_error(['message' => __('Debes iniciar sesion', FLAVOR_PLATFORM_TEXT_DOMAIN)]);
        }

        global $wpdb;
        $tabla_seguimientos = $wpdb->prefix . 'flavor_social_seguimientos';

        $seguimiento_existente = $wpdb->get_row($wpdb->prepare(
            "SELECT id FROM $tabla_seguimientos WHERE seguidor_id = %d AND seguido_id = %d",
            $seguidor_id,
            $seguido_id
        ));

        if ($seguimiento_existente) {
            $wpdb->delete($tabla_seguimientos, [
                'seguidor_id' => $seguidor_id,
                'seguido_id' => $seguido_id,
            ], ['%d', '%d']);

            $this->actualizar_contador_perfil($seguido_id, 'total_seguidores', -1);
            $this->actualizar_contador_perfil($seguidor_id, 'total_siguiendo', -1);

            $accion_realizada = 'dejado_de_seguir';
        } else {
            $wpdb->insert($tabla_seguimientos, [
                'seguidor_id' => $seguidor_id,
                'seguido_id' => $seguido_id,
                'fecha_seguimiento' => current_time('mysql'),
            ], ['%d', '%d', '%s']);

            $this->actualizar_contador_perfil($seguido_id, 'total_seguidores', 1);
            $this->actualizar_contador_perfil($seguidor_id, 'total_siguiendo', 1);

            $this->crear_notificacion($seguido_id, $seguidor_id, 'seguidor', $seguidor_id, 'usuario');

            // Puntos de reputación por seguir y ser seguido
            $this->agregar_puntos_reputacion($seguidor_id, 'seguir_usuario', $seguido_id, 'usuario');
            $this->agregar_puntos_reputacion($seguido_id, 'seguidor_ganado', $seguidor_id, 'usuario');

            $accion_realizada = 'siguiendo';
        }

        $total_seguidores = $this->obtener_total_seguidores($seguido_id);

        wp_send_json_success([
            'accion' => $accion_realizada,
            'seguidores' => $total_seguidores,
        ]);
    }

    /**
     * AJAX: Cargar feed
     */
    public function ajax_cargar_feed() {
        check_ajax_referer('rs_nonce', 'nonce');

        $usuario_id = get_current_user_id();
        $desde_id = absint($_POST['desde'] ?? 0);
        $tipo_feed = sanitize_text_field($_POST['tipo'] ?? 'timeline');
        $limite = 10;

        $publicaciones = $this->obtener_publicaciones_feed($tipo_feed, $usuario_id, $desde_id, $limite);

        $publicaciones_html = '';
        $ultimo_id = 0;

        foreach ($publicaciones as $publicacion) {
            $publicaciones_html .= $this->renderizar_publicacion($publicacion->id);
            $ultimo_id = $publicacion->id;
        }

        wp_send_json_success([
            'html' => $publicaciones_html,
            'posts' => $publicaciones,
            'ultimo_id' => $ultimo_id,
            'hay_mas' => count($publicaciones) === $limite,
        ]);
    }

    /**
     * AJAX: Buscar usuarios
     */
    public function ajax_buscar_usuarios() {
        check_ajax_referer('rs_nonce', 'nonce');

        $query = sanitize_text_field($_POST['query'] ?? '');
        $limite = absint($_POST['limite'] ?? 10);

        if (strlen($query) < 2) {
            wp_send_json_error(['message' => __('Usuario invalido', FLAVOR_PLATFORM_TEXT_DOMAIN)]);
        }

        global $wpdb;

        $usuarios = $wpdb->get_results($wpdb->prepare(
            "SELECT ID, display_name, user_login FROM {$wpdb->users}
            WHERE display_name LIKE %s OR user_login LIKE %s
            LIMIT %d",
            '%' . $wpdb->esc_like($query) . '%',
            '%' . $wpdb->esc_like($query) . '%',
            $limite
        ));

        $resultado_usuarios = array_map(function($usuario) {
            return [
                'id' => $usuario->ID,
                'nombre' => $usuario->display_name,
                'username' => $usuario->user_login,
                'avatar' => get_avatar_url($usuario->ID, ['size' => 50]),
            ];
        }, $usuarios);

        wp_send_json_success(['usuarios' => $resultado_usuarios]);
    }

    /**
     * AJAX: Obtener historias
     */
    public function ajax_obtener_historias() {
        check_ajax_referer('rs_nonce', 'nonce');

        $usuario_id = absint($_POST['usuario_id'] ?? 0);

        if (!$usuario_id) {
            wp_send_json_error(['message' => __('Debes iniciar sesion', FLAVOR_PLATFORM_TEXT_DOMAIN)]);
        }

        global $wpdb;
        $tabla_historias = $wpdb->prefix . 'flavor_social_historias';

        $historias = $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM $tabla_historias
            WHERE autor_id = %d AND fecha_expiracion > NOW()
            ORDER BY fecha_creacion ASC",
            $usuario_id
        ));

        $usuario = get_userdata($usuario_id);
        $historias_formateadas = array_map(function($historia) {
            return [
                'id' => $historia->id,
                'tipo' => $historia->tipo,
                'url' => $historia->contenido_url,
                'texto' => $historia->texto,
                'color' => $historia->color_fondo,
                'tiempo' => human_time_diff(strtotime($historia->fecha_creacion), current_time('timestamp')),
                'vistas' => $historia->vistas,
            ];
        }, $historias);

        wp_send_json_success([
            'usuario' => [
                'id' => $usuario_id,
                'nombre' => $usuario ? $usuario->display_name : 'Usuario',
                'avatar' => get_avatar_url($usuario_id, ['size' => 50]),
            ],
            'historias' => $historias_formateadas,
        ]);
    }

    /**
     * AJAX: Crear historia
     */
    public function ajax_crear_historia() {
        check_ajax_referer('rs_nonce', 'nonce');

        $usuario_id = get_current_user_id();
        if (!$usuario_id) {
            wp_send_json_error(['message' => __('fecha_creacion', FLAVOR_PLATFORM_TEXT_DOMAIN)]);
        }

        if (!$this->get_setting('permite_historias')) {
            wp_send_json_error(['message' => __('Historia creada', FLAVOR_PLATFORM_TEXT_DOMAIN)]);
        }

        $tipo_historia = sanitize_text_field($_POST['tipo'] ?? 'imagen');
        $texto = sanitize_textarea_field($_POST['texto'] ?? '');
        $color_fondo = sanitize_hex_color($_POST['color'] ?? '#6366f1');
        $contenido_url = null;

        if (!empty($_FILES['archivo'])) {
            $archivo_subido = $this->subir_archivo($_FILES['archivo']);
            if ($archivo_subido) {
                $contenido_url = $archivo_subido;
            }
        }

        if (empty($contenido_url) && empty($texto)) {
            wp_send_json_error(['message' => __('Debes iniciar sesion', FLAVOR_PLATFORM_TEXT_DOMAIN)]);
        }

        global $wpdb;
        $tabla_historias = $wpdb->prefix . 'flavor_social_historias';

        $duracion_horas = $this->get_setting('duracion_historia_horas');
        $fecha_expiracion = date('Y-m-d H:i:s', strtotime("+{$duracion_horas} hours"));

        $wpdb->insert($tabla_historias, [
            'autor_id' => $usuario_id,
            'tipo' => $tipo_historia,
            'contenido_url' => $contenido_url,
            'texto' => $texto,
            'color_fondo' => $color_fondo,
            'fecha_creacion' => current_time('mysql'),
            'fecha_expiracion' => $fecha_expiracion,
        ], ['%d', '%s', '%s', '%s', '%s', '%s', '%s']);

        wp_send_json_success(['message' => __('Historia creada', FLAVOR_PLATFORM_TEXT_DOMAIN)]);
    }

    /**
     * AJAX: Guardar post
     */
    public function ajax_guardar_post() {
        check_ajax_referer('rs_nonce', 'nonce');

        $usuario_id = get_current_user_id();
        if (!$usuario_id) {
            wp_send_json_error(['message' => __('Debes iniciar sesion', FLAVOR_PLATFORM_TEXT_DOMAIN)]);
        }

        $publicacion_id = absint($_POST['publicacion_id'] ?? 0);

        if (!$publicacion_id) {
            wp_send_json_error(['message' => __('Debes iniciar sesion', FLAVOR_PLATFORM_TEXT_DOMAIN)]);
        }

        global $wpdb;
        $tabla_guardados = $wpdb->prefix . 'flavor_social_guardados';

        $guardado_existente = $wpdb->get_row($wpdb->prepare(
            "SELECT id FROM $tabla_guardados WHERE usuario_id = %d AND publicacion_id = %d",
            $usuario_id,
            $publicacion_id
        ));

        if ($guardado_existente) {
            $wpdb->delete($tabla_guardados, [
                'usuario_id' => $usuario_id,
                'publicacion_id' => $publicacion_id,
            ], ['%d', '%d']);
            $guardado = false;
        } else {
            $wpdb->insert($tabla_guardados, [
                'usuario_id' => $usuario_id,
                'publicacion_id' => $publicacion_id,
                'fecha_guardado' => current_time('mysql'),
            ], ['%d', '%d', '%s']);
            $guardado = true;
        }

        wp_send_json_success(['guardado' => $guardado]);
    }

    /**
     * AJAX: Obtener notificaciones
     */
    public function ajax_obtener_notificaciones() {
        check_ajax_referer('rs_nonce', 'nonce');

        $usuario_id = get_current_user_id();
        if (!$usuario_id) {
            wp_send_json_error(['message' => __('SELECT COUNT(*) FROM $tabla_notificaciones WHERE usuario_id = %d AND leida = 0', FLAVOR_PLATFORM_TEXT_DOMAIN)]);
        }

        $limite = absint($_POST['limite'] ?? 20);

        global $wpdb;
        $tabla_notificaciones = $wpdb->prefix . 'flavor_social_notificaciones';

        $notificaciones = $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM $tabla_notificaciones
            WHERE usuario_id = %d
            ORDER BY fecha_creacion DESC
            LIMIT %d",
            $usuario_id,
            $limite
        ));

        $notificaciones_formateadas = array_map(function($notificacion) {
            $actor = get_userdata($notificacion->actor_id);
            return [
                'id' => $notificacion->id,
                'tipo' => $notificacion->tipo,
                'actor' => [
                    'id' => $notificacion->actor_id,
                    'nombre' => $actor ? $actor->display_name : 'Usuario',
                    'avatar' => get_avatar_url($notificacion->actor_id, ['size' => 50]),
                ],
                'mensaje' => $this->generar_mensaje_notificacion($notificacion),
                'referencia_id' => $notificacion->referencia_id,
                'leida' => (bool) $notificacion->leida,
                'tiempo' => human_time_diff(strtotime($notificacion->fecha_creacion), current_time('timestamp')),
            ];
        }, $notificaciones);

        $no_leidas = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM $tabla_notificaciones WHERE usuario_id = %d AND leida = 0",
            $usuario_id
        ));

        wp_send_json_success([
            'notificaciones' => $notificaciones_formateadas,
            'no_leidas' => (int) $no_leidas,
        ]);
    }

    /**
     * AJAX: Marcar notificacion como leida
     */
    public function ajax_marcar_notificacion_leida() {
        check_ajax_referer('rs_nonce', 'nonce');

        $usuario_id = get_current_user_id();
        if (!$usuario_id) {
            wp_send_json_error(['message' => __('Debes iniciar sesion', FLAVOR_PLATFORM_TEXT_DOMAIN)]);
        }

        $notificacion_id = absint($_POST['notificacion_id'] ?? 0);
        $todas = isset($_POST['todas']) && $_POST['todas'] === 'true';

        global $wpdb;
        $tabla_notificaciones = $wpdb->prefix . 'flavor_social_notificaciones';

        if ($todas) {
            $wpdb->update(
                $tabla_notificaciones,
                ['leida' => 1],
                ['usuario_id' => $usuario_id],
                ['%d'],
                ['%d']
            );
        } elseif ($notificacion_id) {
            $wpdb->update(
                $tabla_notificaciones,
                ['leida' => 1],
                ['id' => $notificacion_id, 'usuario_id' => $usuario_id],
                ['%d'],
                ['%d', '%d']
            );
        }

        wp_send_json_success(['message' => __('Historia creada', FLAVOR_PLATFORM_TEXT_DOMAIN)]);
    }

    /**
     * AJAX: Obtener perfil
     */
    public function ajax_obtener_perfil() {
        check_ajax_referer('rs_nonce', 'nonce');

        $usuario_id = absint($_POST['usuario_id'] ?? 0);
        $usuario_actual = get_current_user_id();

        if (!$usuario_id) {
            wp_send_json_error(['message' => __('Debes iniciar sesion', FLAVOR_PLATFORM_TEXT_DOMAIN)]);
        }

        $perfil = $this->obtener_perfil_completo($usuario_id);

        if (!$perfil) {
            wp_send_json_error(['message' => __('sitio_web', FLAVOR_PLATFORM_TEXT_DOMAIN)]);
        }

        $perfil['es_propio'] = ($usuario_id === $usuario_actual);
        $perfil['siguiendo'] = $this->esta_siguiendo($usuario_actual, $usuario_id);

        wp_send_json_success($perfil);
    }

    /**
     * AJAX: Actualizar perfil
     */
    public function ajax_actualizar_perfil() {
        check_ajax_referer('rs_nonce', 'nonce');

        $usuario_id = get_current_user_id();
        if (!$usuario_id) {
            wp_send_json_error(['message' => __('Debes iniciar sesion', FLAVOR_PLATFORM_TEXT_DOMAIN)]);
        }

        $bio = sanitize_textarea_field($_POST['bio'] ?? '');
        $ubicacion = sanitize_text_field($_POST['ubicacion'] ?? '');
        $sitio_web = esc_url_raw($_POST['sitio_web'] ?? '');

        global $wpdb;
        $tabla_perfiles = $wpdb->prefix . 'flavor_social_perfiles';

        $perfil_existente = $wpdb->get_row($wpdb->prepare(
            "SELECT id FROM $tabla_perfiles WHERE usuario_id = %d",
            $usuario_id
        ));

        $datos_perfil = [
            'bio' => $bio,
            'ubicacion' => $ubicacion,
            'sitio_web' => $sitio_web,
            'fecha_actualizacion' => current_time('mysql'),
        ];

        if ($perfil_existente) {
            $wpdb->update($tabla_perfiles, $datos_perfil, ['usuario_id' => $usuario_id]);
        } else {
            $datos_perfil['usuario_id'] = $usuario_id;
            $datos_perfil['fecha_creacion'] = current_time('mysql');
            $wpdb->insert($tabla_perfiles, $datos_perfil);
        }

        // Procesar cover si se subio
        if (!empty($_FILES['cover'])) {
            $cover_url = $this->subir_archivo($_FILES['cover']);
            if ($cover_url) {
                $wpdb->update($tabla_perfiles, ['cover_url' => $cover_url], ['usuario_id' => $usuario_id]);
            }
        }

        wp_send_json_success(['message' => __('Historia creada', FLAVOR_PLATFORM_TEXT_DOMAIN)]);
    }

    /**
     * AJAX: Eliminar publicacion
     */
    public function ajax_eliminar_publicacion() {
        check_ajax_referer('rs_nonce', 'nonce');

        $usuario_id = get_current_user_id();
        if (!$usuario_id) {
            wp_send_json_error(['message' => __('Debes iniciar sesion', FLAVOR_PLATFORM_TEXT_DOMAIN)]);
        }

        $publicacion_id = absint($_POST['publicacion_id'] ?? 0);

        if (!$publicacion_id) {
            wp_send_json_error(['message' => __('Debes iniciar sesion', FLAVOR_PLATFORM_TEXT_DOMAIN)]);
        }

        global $wpdb;
        $tabla_publicaciones = $wpdb->prefix . 'flavor_social_publicaciones';

        $publicacion = $wpdb->get_row($wpdb->prepare(
            "SELECT autor_id FROM $tabla_publicaciones WHERE id = %d",
            $publicacion_id
        ));

        if (!$publicacion || ($publicacion->autor_id != $usuario_id && !current_user_can('manage_options'))) {
            wp_send_json_error(['message' => __('Debes iniciar sesion', FLAVOR_PLATFORM_TEXT_DOMAIN)]);
        }

        $wpdb->update(
            $tabla_publicaciones,
            ['estado' => 'eliminado'],
            ['id' => $publicacion_id],
            ['%s'],
            ['%d']
        );

        $this->actualizar_contador_perfil($publicacion->autor_id, 'total_publicaciones', -1);

        wp_send_json_success(['message' => __('Historia creada', FLAVOR_PLATFORM_TEXT_DOMAIN)]);
    }

    /**
     * AJAX: Reportar contenido
     */
    public function ajax_reportar_contenido() {
        check_ajax_referer('rs_nonce', 'nonce');

        $usuario_id = get_current_user_id();
        if (!$usuario_id) {
            wp_send_json_error(['message' => __('Debes iniciar sesion', FLAVOR_PLATFORM_TEXT_DOMAIN)]);
        }

        $tipo_contenido = sanitize_text_field($_POST['tipo'] ?? '');
        $contenido_id = absint($_POST['contenido_id'] ?? 0);
        $motivo = sanitize_textarea_field($_POST['motivo'] ?? '');

        if (!$tipo_contenido || !$contenido_id) {
            wp_send_json_error(['message' => __('Debes iniciar sesion', FLAVOR_PLATFORM_TEXT_DOMAIN)]);
        }

        // Aqui se guardaria el reporte en una tabla de reportes
        // Por ahora solo enviamos notificacion al admin
        $admin_email = get_option('admin_email');
        $asunto = sprintf('[Red Social] Reporte de %s #%d', $tipo_contenido, $contenido_id);
        $mensaje = sprintf(
            "Usuario #%d ha reportado un %s (ID: %d)\n\nMotivo: %s",
            $usuario_id,
            $tipo_contenido,
            $contenido_id,
            $motivo
        );

        wp_mail($admin_email, $asunto, $mensaje);

        wp_send_json_success(['message' => __('Historia creada', FLAVOR_PLATFORM_TEXT_DOMAIN)]);
    }

    // ========================================
    // REST API
    // ========================================

    /**
     * Registra las rutas REST
     */
    public function register_rest_routes() {
        $namespace = 'flavor-chat/v1';

        register_rest_route($namespace, '/red-social/feed', [
            'methods' => 'GET',
            'callback' => [$this, 'rest_obtener_feed'],
            'permission_callback' => [$this, 'public_permission_check'],
        ]);

        register_rest_route($namespace, '/red-social/publicacion', [
            'methods' => 'POST',
            'callback' => [$this, 'rest_crear_publicacion'],
            'permission_callback' => [$this, 'rest_check_auth'],
        ]);

        register_rest_route($namespace, '/red-social/publicacion/(?P<id>\d+)', [
            'methods' => 'GET',
            'callback' => [$this, 'rest_obtener_publicacion'],
            'permission_callback' => [$this, 'public_permission_check'],
        ]);

        register_rest_route($namespace, '/red-social/perfil/(?P<id>\d+)', [
            'methods' => 'GET',
            'callback' => [$this, 'rest_obtener_perfil'],
            'permission_callback' => [$this, 'public_permission_check'],
        ]);

        register_rest_route($namespace, '/red-social/trending', [
            'methods' => 'GET',
            'callback' => [$this, 'rest_obtener_trending'],
            'permission_callback' => [$this, 'public_permission_check'],
        ]);
    }

    /**
     * Verifica autenticacion REST
     */
    public function rest_check_auth() {
        return is_user_logged_in();
    }

    /**
     * REST: Obtener feed
     */
    public function rest_obtener_feed($request) {
        $tipo = $request->get_param('tipo') ?? 'comunidad';
        $desde = $request->get_param('desde') ?? 0;
        $limite = $request->get_param('limite') ?? 20;
        $usuario_id = get_current_user_id();

        $publicaciones = $this->obtener_publicaciones_feed($tipo, $usuario_id, $desde, $limite);

        $respuesta = [
            'success' => true,
            'publicaciones' => array_map([$this, 'formatear_publicacion_api'], $publicaciones),
        ];

        return new WP_REST_Response($this->sanitize_public_social_response($respuesta), 200);
    }

    /**
     * REST: Crear publicacion
     */
    public function rest_crear_publicacion($request) {
        $usuario_id = get_current_user_id();
        $contenido = $request->get_param('contenido');
        $visibilidad = $request->get_param('visibilidad') ?? 'comunidad';

        if (empty($contenido)) {
            return new WP_REST_Response(['success' => false, 'message' => __('Contenido vacio', FLAVOR_PLATFORM_TEXT_DOMAIN)], 400);
        }

        global $wpdb;
        $tabla_publicaciones = $wpdb->prefix . 'flavor_social_publicaciones';

        $wpdb->insert($tabla_publicaciones, [
            'autor_id' => $usuario_id,
            'contenido' => sanitize_textarea_field($contenido),
            'visibilidad' => $visibilidad,
            'estado' => 'publicado',
            'fecha_publicacion' => current_time('mysql'),
        ]);

        $publicacion_id = $wpdb->insert_id;
        $this->procesar_hashtags($contenido, $publicacion_id);

        return new WP_REST_Response([
            'success' => true,
            'publicacion_id' => $publicacion_id,
        ], 201);
    }

    /**
     * REST: Obtener publicacion
     */
    public function rest_obtener_publicacion($request) {
        $publicacion_id = $request->get_param('id');

        global $wpdb;
        $tabla_publicaciones = $wpdb->prefix . 'flavor_social_publicaciones';

        $publicacion = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM $tabla_publicaciones WHERE id = %d AND estado = 'publicado'",
            $publicacion_id
        ));

        if (!$publicacion) {
            return new WP_REST_Response(['success' => false, 'message' => __('trending', FLAVOR_PLATFORM_TEXT_DOMAIN)], 404);
        }

        $respuesta = [
            'success' => true,
            'publicacion' => $this->formatear_publicacion_api($publicacion),
        ];

        return new WP_REST_Response($this->sanitize_public_social_response($respuesta), 200);
    }

    /**
     * REST: Obtener perfil
     */
    public function rest_obtener_perfil($request) {
        $usuario_id = $request->get_param('id');
        $perfil = $this->obtener_perfil_completo($usuario_id);

        if (!$perfil) {
            return new WP_REST_Response(['success' => false, 'message' => __('No encontrada', FLAVOR_PLATFORM_TEXT_DOMAIN)], 404);
        }

        $respuesta = [
            'success' => true,
            'perfil' => $perfil,
        ];

        return new WP_REST_Response($this->sanitize_public_social_response($respuesta), 200);
    }

    /**
     * REST: Obtener trending
     */
    public function rest_obtener_trending($request) {
        $limite = $request->get_param('limite') ?? 10;
        $trending = $this->obtener_hashtags_trending($limite);

        return new WP_REST_Response([
            'success' => true,
            'trending' => $trending,
        ], 200);
    }

    private function sanitize_public_social_response($respuesta) {
        if (is_user_logged_in() || empty($respuesta['success'])) {
            return $respuesta;
        }

        if (!empty($respuesta['publicaciones']) && is_array($respuesta['publicaciones'])) {
            $respuesta['publicaciones'] = array_map([$this, 'sanitize_public_publicacion'], $respuesta['publicaciones']);
        }

        if (!empty($respuesta['publicacion']) && is_array($respuesta['publicacion'])) {
            $respuesta['publicacion'] = $this->sanitize_public_publicacion($respuesta['publicacion']);
        }

        if (!empty($respuesta['perfil']) && is_array($respuesta['perfil'])) {
            $respuesta['perfil'] = $this->sanitize_public_perfil($respuesta['perfil']);
        }

        return $respuesta;
    }

    private function sanitize_public_publicacion($publicacion) {
        if (!is_array($publicacion)) {
            return $publicacion;
        }

        if (!empty($publicacion['autor']) && is_array($publicacion['autor'])) {
            unset($publicacion['autor']['id'], $publicacion['autor']['username']);
            $publicacion['autor']['avatar'] = '';
        }

        return $publicacion;
    }

    private function sanitize_public_perfil($perfil) {
        if (!is_array($perfil)) {
            return $perfil;
        }

        unset(
            $perfil['id'],
            $perfil['username'],
            $perfil['ubicacion'],
            $perfil['sitio_web'],
            $perfil['fecha_registro']
        );
        $perfil['avatar'] = '';
        $perfil['cover_url'] = '';

        return $perfil;
    }

    // ========================================
    // Shortcodes
    // ========================================

    /**
     * Shortcode: Feed
     */
    public function shortcode_feed($atts) {
        $atts = shortcode_atts([
            'tipo' => 'timeline',
            'limite' => 10,
            'mostrar_crear' => true,
        ], $atts);

        $usuario_id = get_current_user_id();
        $publicacion_destacada = 0;
        if (isset($_GET['rs_publicacion'])) {
            $publicacion_destacada = absint($_GET['rs_publicacion']);
        } elseif (isset($_GET['publicacion_id'])) {
            $publicacion_destacada = absint($_GET['publicacion_id']);
        }

        if ($publicacion_destacada) {
            $publicacion_html = $this->renderizar_publicacion($publicacion_destacada);
            if (!empty($publicacion_html)) {
                ob_start();
                ?>
                <div class="rs-container">
                    <div class="rs-layout rs-layout-two-col">
                        <main class="rs-feed-main">
                            <div class="rs-feed">
                                <?php echo $publicacion_html; ?>
                            </div>
                        </main>

                        <aside class="rs-sidebar-right">
                            <?php echo $this->renderizar_widget_sugerencias(); ?>
                            <?php echo $this->renderizar_widget_trending(); ?>
                        </aside>
                    </div>
                </div>
                <?php
                return ob_get_clean();
            }
        }

        $publicaciones = $this->obtener_publicaciones_feed($atts['tipo'], $usuario_id, 0, $atts['limite']);

        ob_start();
        ?>
        <div class="rs-container">
            <div class="rs-layout rs-layout-two-col">
                <main class="rs-feed-main">
                    <?php if ($atts['mostrar_crear'] && $usuario_id): ?>
                        <?php echo $this->renderizar_crear_publicacion(); ?>
                    <?php endif; ?>

                    <div class="rs-feed-header">
                        <div class="rs-feed-tabs">
                            <button class="rs-feed-tab <?php echo $atts['tipo'] === 'timeline' ? 'active' : ''; ?>" data-tipo="timeline"><?php echo esc_html__('Para ti', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></button>
                            <button class="rs-feed-tab <?php echo $atts['tipo'] === 'comunidad' ? 'active' : ''; ?>" data-tipo="comunidad"><?php echo esc_html__('Comunidad', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></button>
                            <button class="rs-feed-tab <?php echo $atts['tipo'] === 'trending' ? 'active' : ''; ?>" data-tipo="trending"><?php echo esc_html__('Trending', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></button>
                        </div>
                    </div>

                    <div class="rs-feed">
                        <?php foreach ($publicaciones as $publicacion): ?>
                            <?php echo $this->renderizar_publicacion($publicacion->id); ?>
                        <?php endforeach; ?>
                    </div>

                    <div class="rs-loading" style="display:none;">
                        <div class="rs-spinner"></div>
                    </div>
                </main>

                <aside class="rs-sidebar-right">
                    <?php echo $this->renderizar_widget_sugerencias(); ?>
                    <?php echo $this->renderizar_widget_trending(); ?>
                </aside>
            </div>
        </div>
        <?php
        return ob_get_clean();
    }

    /**
     * Shortcode: Perfil
     */
    public function shortcode_perfil($atts) {
        $atts = shortcode_atts([
            'usuario_id' => get_current_user_id(),
        ], $atts);

        // Permitir parametro GET
        if (isset($_GET['rs_perfil'])) {
            $atts['usuario_id'] = absint($_GET['rs_perfil']);
        } elseif (isset($_GET['usuario_id'])) {
            $atts['usuario_id'] = absint($_GET['usuario_id']);
        }

        $perfil = $this->obtener_perfil_completo($atts['usuario_id']);
        if (!$perfil) {
            return '<p>Perfil no encontrado</p>';
        }

        $usuario_actual = get_current_user_id();
        $es_propio = ($atts['usuario_id'] === $usuario_actual);
        $esta_siguiendo = $this->esta_siguiendo($usuario_actual, $atts['usuario_id']);

        ob_start();
        ?>
        <div class="rs-container">
            <div class="rs-perfil">
                <div class="rs-perfil-cover">
                    <?php if ($perfil['cover_url']): ?>
                        <img src="<?php echo esc_url($perfil['cover_url']); ?>" alt="">
                    <?php endif; ?>
                    <div class="rs-perfil-avatar-wrapper">
                        <img class="rs-perfil-avatar" src="<?php echo esc_url($perfil['avatar']); ?>" alt="">
                    </div>
                </div>

                <div class="rs-perfil-info">
                    <div class="rs-perfil-header">
                        <div>
                            <h1 class="rs-perfil-nombre"><?php echo esc_html($perfil['nombre']); ?></h1>
                            <span class="rs-perfil-username">@<?php echo esc_html($perfil['username']); ?></span>
                        </div>
                        <?php if (!$es_propio && $usuario_actual): ?>
                            <button class="rs-btn-seguir <?php echo $esta_siguiendo ? 'rs-siguiendo' : ''; ?>"
                                    data-usuario-id="<?php echo esc_attr($atts['usuario_id']); ?>">
                                <?php echo $esta_siguiendo ? 'Siguiendo' : 'Seguir'; ?>
                            </button>
                        <?php endif; ?>
                    </div>

                    <?php if ($perfil['bio']): ?>
                        <p class="rs-perfil-bio"><?php echo esc_html($perfil['bio']); ?></p>
                    <?php endif; ?>

                    <div class="rs-perfil-stats">
                        <div class="rs-perfil-stat">
                            <span class="rs-perfil-stat-num"><?php echo number_format($perfil['total_publicaciones']); ?></span>
                            <span class="rs-perfil-stat-label"><?php echo esc_html__('Publicaciones', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></span>
                        </div>
                        <div class="rs-perfil-stat" data-tipo="seguidores">
                            <span class="rs-perfil-stat-num"><?php echo number_format($perfil['total_seguidores']); ?></span>
                            <span class="rs-perfil-stat-label"><?php echo esc_html__('Seguidores', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></span>
                        </div>
                        <div class="rs-perfil-stat">
                            <span class="rs-perfil-stat-num"><?php echo number_format($perfil['total_siguiendo']); ?></span>
                            <span class="rs-perfil-stat-label"><?php echo esc_html__('Siguiendo', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></span>
                        </div>
                    </div>
                </div>
            </div>

            <div class="rs-feed" style="margin-top: 24px;">
                <?php
                $publicaciones = $this->obtener_publicaciones_usuario($atts['usuario_id'], 0, 20);
                foreach ($publicaciones as $publicacion):
                    echo $this->renderizar_publicacion($publicacion->id);
                endforeach;
                ?>
            </div>
        </div>
        <?php
        return ob_get_clean();
    }

    /**
     * Shortcode: Explorar
     */
    public function shortcode_explorar($atts) {
        $atts = shortcode_atts([
            'limite' => 30,
        ], $atts);

        // Si hay hashtag en URL
        $hashtag_filtro = '';
        if (isset($_GET['rs_hashtag'])) {
            $hashtag_filtro = sanitize_text_field($_GET['rs_hashtag']);
        } elseif (isset($_GET['hashtag'])) {
            $hashtag_filtro = sanitize_text_field($_GET['hashtag']);
        }

        ob_start();
        ?>
        <div class="rs-container">
            <div class="rs-explorar">
                <?php if ($hashtag_filtro): ?>
                    <h2 class="rs-explorar-titulo">#<?php echo esc_html($hashtag_filtro); ?></h2>
                    <?php
                    $publicaciones = $this->obtener_publicaciones_por_hashtag($hashtag_filtro, $atts['limite']);
                    ?>
                    <div class="rs-feed">
                        <?php foreach ($publicaciones as $publicacion): ?>
                            <?php echo $this->renderizar_publicacion($publicacion->id); ?>
                        <?php endforeach; ?>
                    </div>
                <?php else: ?>
                    <div class="rs-explorar-grid">
                        <?php
                        $publicaciones = $this->obtener_publicaciones_con_media($atts['limite']);
                        foreach ($publicaciones as $publicacion):
                            $adjuntos = json_decode($publicacion->adjuntos, true);
                            if (!empty($adjuntos)):
                        ?>
                            <div class="rs-explorar-item" data-post-id="<?php echo esc_attr($publicacion->id); ?>">
                                <img src="<?php echo esc_url($adjuntos[0]); ?>" alt="">
                                <div class="rs-explorar-overlay">
                                    <span class="rs-explorar-stat">
                                        <svg width="20" height="20" viewBox="0 0 24 24" fill="white"><path d="M12 21.35l-1.45-1.32C5.4 15.36 2 12.28 2 8.5 2 5.42 4.42 3 7.5 3c1.74 0 3.41.81 4.5 2.09C13.09 3.81 14.76 3 16.5 3 19.58 3 22 5.42 22 8.5c0 3.78-3.4 6.86-8.55 11.54L12 21.35z"/></svg>
                                        <?php echo $publicacion->me_gusta; ?>
                                    </span>
                                    <span class="rs-explorar-stat">
                                        <svg width="20" height="20" viewBox="0 0 24 24" fill="white"><path d="M21 6h-2V4.73c0-.17-.02-.35-.05-.52-.33-1.75-1.95-3.04-3.74-2.9-1.62.13-2.98 1.49-3.21 3.11V6h-2V4.42c-.23-1.62-1.59-2.98-3.21-3.11C4.99.17 3.37 1.46 3.05 3.21 3.02 3.38 3 3.56 3 3.73V6H1v13h8v-1.73c0-.17.02-.35.05-.52.33-1.75 1.95-3.04 3.74-2.9 1.62.13 2.98 1.49 3.21 3.11V19h5V6z"/></svg>
                                        <?php echo $publicacion->comentarios; ?>
                                    </span>
                                </div>
                            </div>
                        <?php
                            endif;
                        endforeach;
                        ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
        <?php
        return ob_get_clean();
    }

    /**
     * Shortcode: Crear publicacion
     */
    public function shortcode_crear_publicacion($atts) {
        if (!is_user_logged_in()) {
            return '<p>Debes iniciar sesion para publicar.</p>';
        }

        return $this->renderizar_crear_publicacion();
    }

    /**
     * Shortcode: Notificaciones
     */
    public function shortcode_notificaciones($atts) {
        if (!is_user_logged_in()) {
            return '<p>Debes iniciar sesion.</p>';
        }

        $usuario_id = get_current_user_id();

        global $wpdb;
        $tabla_notificaciones = $wpdb->prefix . 'flavor_social_notificaciones';

        $notificaciones = $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM $tabla_notificaciones WHERE usuario_id = %d ORDER BY fecha_creacion DESC LIMIT 50",
            $usuario_id
        ));

        ob_start();
        ?>
        <div class="rs-container">
            <div class="rs-notificaciones">
                <?php if (empty($notificaciones)): ?>
                    <p style="text-align: center; padding: 40px; color: var(--rs-text-muted);"><?php echo esc_html__('No tienes notificaciones', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></p>
                <?php else: ?>
                    <?php foreach ($notificaciones as $notificacion): ?>
                        <?php
                        $actor = get_userdata($notificacion->actor_id);
                        $mensaje = $this->generar_mensaje_notificacion($notificacion);
                        ?>
                        <div class="rs-notificacion <?php echo !$notificacion->leida ? 'rs-no-leida' : ''; ?>"
                             data-notificacion-id="<?php echo esc_attr($notificacion->id); ?>">
                            <img class="rs-notificacion-avatar"
                                 src="<?php echo esc_url(get_avatar_url($notificacion->actor_id, ['size' => 50])); ?>"
                                 alt="">
                            <div class="rs-notificacion-contenido">
                                <p class="rs-notificacion-texto">
                                    <strong><?php echo esc_html($actor ? $actor->display_name : 'Usuario'); ?></strong>
                                    <?php echo esc_html($mensaje); ?>
                                </p>
                                <span class="rs-notificacion-tiempo">
                                    <?php echo human_time_diff(strtotime($notificacion->fecha_creacion), current_time('timestamp')); ?>
                                </span>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>
        <?php
        return ob_get_clean();
    }

    /**
     * Shortcode: Historias
     */
    public function shortcode_historias($atts) {
        $usuario_id = get_current_user_id();

        global $wpdb;
        $tabla_historias = $wpdb->prefix . 'flavor_social_historias';
        $tabla_seguimientos = $wpdb->prefix . 'flavor_social_seguimientos';

        // Obtener historias de usuarios seguidos
        if ($usuario_id) {
            $usuarios_con_historias = $wpdb->get_results($wpdb->prepare(
                "SELECT DISTINCT h.autor_id, MAX(h.fecha_creacion) as ultima
                FROM $tabla_historias h
                INNER JOIN $tabla_seguimientos s ON h.autor_id = s.seguido_id
                WHERE s.seguidor_id = %d AND h.fecha_expiracion > NOW()
                GROUP BY h.autor_id
                ORDER BY ultima DESC
                LIMIT 20",
                $usuario_id
            ));
        } else {
            $usuarios_con_historias = $wpdb->get_results(
                "SELECT DISTINCT autor_id, MAX(fecha_creacion) as ultima
                FROM $tabla_historias
                WHERE fecha_expiracion > NOW()
                GROUP BY autor_id
                ORDER BY ultima DESC
                LIMIT 20"
            );
        }

        ob_start();
        ?>
        <div class="rs-historias">
            <?php if ($usuario_id): ?>
                <div class="rs-historia rs-historia-crear">
                    <div class="rs-historia-avatar">
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <line x1="12" y1="5" x2="12" y2="19"></line>
                            <line x1="5" y1="12" x2="19" y2="12"></line>
                        </svg>
                    </div>
                    <span class="rs-historia-nombre"><?php echo esc_html__('Tu historia', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></span>
                </div>
            <?php endif; ?>

            <?php foreach ($usuarios_con_historias as $usuario_historia): ?>
                <?php $usuario = get_userdata($usuario_historia->autor_id); ?>
                <div class="rs-historia" data-usuario-id="<?php echo esc_attr($usuario_historia->autor_id); ?>">
                    <div class="rs-historia-avatar">
                        <img src="<?php echo esc_url(get_avatar_url($usuario_historia->autor_id, ['size' => 70])); ?>" alt="">
                    </div>
                    <span class="rs-historia-nombre"><?php echo esc_html($usuario ? $usuario->display_name : 'Usuario'); ?></span>
                </div>
            <?php endforeach; ?>
        </div>
        <?php
        return ob_get_clean();
    }

    // ========================================
    // Metodos auxiliares
    // ========================================

    /**
     * Procesa los adjuntos subidos
     */
    private function procesar_adjuntos($archivos) {
        $adjuntos_url = [];
        $max_imagenes = $this->get_setting('max_imagenes_por_post');

        if (!function_exists('wp_handle_upload')) {
            require_once ABSPATH . 'wp-admin/includes/file.php';
        }

        $total_archivos = count($archivos['name']);
        $archivos_procesados = 0;

        for ($i = 0; $i < $total_archivos && $archivos_procesados < $max_imagenes; $i++) {
            if ($archivos['error'][$i] !== UPLOAD_ERR_OK) {
                continue;
            }

            $archivo = [
                'name' => $archivos['name'][$i],
                'type' => $archivos['type'][$i],
                'tmp_name' => $archivos['tmp_name'][$i],
                'error' => $archivos['error'][$i],
                'size' => $archivos['size'][$i],
            ];

            $resultado_subida = wp_handle_upload($archivo, ['test_form' => false]);

            if (!empty($resultado_subida['url'])) {
                $adjuntos_url[] = $resultado_subida['url'];
                $archivos_procesados++;
            }
        }

        return $adjuntos_url;
    }

    /**
     * Sube un archivo individual
     */
    private function subir_archivo($archivo) {
        if (!function_exists('wp_handle_upload')) {
            require_once ABSPATH . 'wp-admin/includes/file.php';
        }

        $resultado_subida = wp_handle_upload($archivo, ['test_form' => false]);

        return $resultado_subida['url'] ?? null;
    }

    /**
     * Procesa hashtags en el contenido
     */
    private function procesar_hashtags($contenido, $publicacion_id) {
        preg_match_all('/#([a-zA-Z0-9_\p{L}]+)/u', $contenido, $coincidencias);

        if (empty($coincidencias[1])) {
            return;
        }

        global $wpdb;
        $tabla_hashtags = $wpdb->prefix . 'flavor_social_hashtags';
        $tabla_hashtags_posts = $wpdb->prefix . 'flavor_social_hashtags_posts';

        foreach (array_unique($coincidencias[1]) as $hashtag) {
            $hashtag = mb_strtolower($hashtag);

            // Insertar o actualizar hashtag
            $wpdb->query($wpdb->prepare(
                "INSERT INTO $tabla_hashtags (hashtag, total_usos, fecha_ultimo_uso)
                VALUES (%s, 1, NOW())
                ON DUPLICATE KEY UPDATE total_usos = total_usos + 1, fecha_ultimo_uso = NOW()",
                $hashtag
            ));

            $hashtag_id = $wpdb->get_var($wpdb->prepare(
                "SELECT id FROM $tabla_hashtags WHERE hashtag = %s",
                $hashtag
            ));

            if ($hashtag_id) {
                $wpdb->insert($tabla_hashtags_posts, [
                    'hashtag_id' => $hashtag_id,
                    'publicacion_id' => $publicacion_id,
                    'fecha_creacion' => current_time('mysql'),
                ], ['%d', '%d', '%s']);
            }
        }
    }

    /**
     * Procesa menciones en el contenido
     */
    private function procesar_menciones($contenido, $publicacion_id, $autor_id) {
        preg_match_all('/@([a-zA-Z0-9_]+)/', $contenido, $coincidencias);

        if (empty($coincidencias[1])) {
            return;
        }

        foreach (array_unique($coincidencias[1]) as $username) {
            $usuario = get_user_by('login', $username);
            if ($usuario && $usuario->ID != $autor_id) {
                $this->crear_notificacion($usuario->ID, $autor_id, 'mencion', $publicacion_id, 'publicacion');
            }
        }
    }

    /**
     * Crea una notificacion
     */
    private function crear_notificacion($usuario_id, $actor_id, $tipo, $referencia_id, $referencia_tipo) {
        global $wpdb;
        $tabla_notificaciones = $wpdb->prefix . 'flavor_social_notificaciones';

        $wpdb->insert($tabla_notificaciones, [
            'usuario_id' => $usuario_id,
            'actor_id' => $actor_id,
            'tipo' => $tipo,
            'referencia_id' => $referencia_id,
            'referencia_tipo' => $referencia_tipo,
            'fecha_creacion' => current_time('mysql'),
        ], ['%d', '%d', '%s', '%d', '%s', '%s']);
    }

    /**
     * Genera mensaje de notificacion
     */
    private function generar_mensaje_notificacion($notificacion) {
        $mensajes = [
            'like' => 'le gusta tu publicacion',
            'comentario' => 'comento en tu publicacion',
            'seguidor' => 'empezo a seguirte',
            'mencion' => 'te menciono en una publicacion',
            'compartido' => 'compartio tu publicacion',
            'historia' => 'publico una nueva historia',
        ];

        return $mensajes[$notificacion->tipo] ?? 'interactuo contigo';
    }

    /**
     * Actualiza contador en perfil
     */
    private function actualizar_contador_perfil($usuario_id, $campo, $incremento) {
        global $wpdb;
        $tabla_perfiles = $wpdb->prefix . 'flavor_social_perfiles';

        // Asegurar que existe el perfil
        $this->asegurar_perfil_existe($usuario_id);

        $operador = $incremento >= 0 ? '+' : '-';
        $valor = abs($incremento);

        $wpdb->query($wpdb->prepare(
            "UPDATE $tabla_perfiles SET $campo = GREATEST(0, $campo $operador %d) WHERE usuario_id = %d",
            $valor,
            $usuario_id
        ));
    }

    /**
     * Asegura que existe el perfil del usuario
     */
    private function asegurar_perfil_existe($usuario_id) {
        global $wpdb;
        $tabla_perfiles = $wpdb->prefix . 'flavor_social_perfiles';

        $existe = $wpdb->get_var($wpdb->prepare(
            "SELECT id FROM $tabla_perfiles WHERE usuario_id = %d",
            $usuario_id
        ));

        if (!$existe) {
            $wpdb->insert($tabla_perfiles, [
                'usuario_id' => $usuario_id,
                'fecha_creacion' => current_time('mysql'),
            ], ['%d', '%s']);
        }
    }

    /**
     * Obtiene publicaciones del feed
     */
    private function obtener_publicaciones_feed($tipo, $usuario_id, $desde_id, $limite) {
        global $wpdb;
        $tabla_publicaciones = $wpdb->prefix . 'flavor_social_publicaciones';
        $tabla_seguimientos = $wpdb->prefix . 'flavor_social_seguimientos';
        $tabla_engagement = $wpdb->prefix . 'flavor_social_engagement';

        $desde_id = $desde_id > 0 ? $desde_id : PHP_INT_MAX;

        // Verificar si usar algoritmo inteligente
        $algoritmo_configurado = $this->get_setting('timeline_algoritmo');

        switch ($tipo) {
            case 'timeline':
                // Si está configurado como inteligente y hay usuario logueado
                if ($algoritmo_configurado === 'inteligente' && $usuario_id) {
                    return $this->obtener_feed_inteligente($usuario_id, $desde_id, $limite);
                }

                if ($usuario_id) {
                    $sql = "SELECT p.* FROM $tabla_publicaciones p
                            WHERE p.estado = 'publicado'
                            AND p.id < %d
                            AND (
                                p.autor_id = %d
                                OR p.autor_id IN (SELECT seguido_id FROM $tabla_seguimientos WHERE seguidor_id = %d)
                                OR p.visibilidad IN ('publica', 'comunidad')
                            )
                            ORDER BY p.fecha_publicacion DESC
                            LIMIT %d";
                    return $wpdb->get_results($wpdb->prepare($sql, $desde_id, $usuario_id, $usuario_id, $limite));
                }
                // Fall through para usuarios no logueados

            case 'comunidad':
                $sql = "SELECT * FROM $tabla_publicaciones
                        WHERE estado = 'publicado'
                        AND visibilidad IN ('publica', 'comunidad')
                        AND id < %d
                        ORDER BY fecha_publicacion DESC
                        LIMIT %d";
                return $wpdb->get_results($wpdb->prepare($sql, $desde_id, $limite));

            case 'trending':
                $sql = "SELECT * FROM $tabla_publicaciones
                        WHERE estado = 'publicado'
                        AND visibilidad IN ('publica', 'comunidad')
                        AND fecha_publicacion > DATE_SUB(NOW(), INTERVAL 7 DAY)
                        AND id < %d
                        ORDER BY (me_gusta + comentarios * 2 + compartidos * 3) DESC, fecha_publicacion DESC
                        LIMIT %d";
                return $wpdb->get_results($wpdb->prepare($sql, $desde_id, $limite));

            case 'inteligente':
                if ($usuario_id) {
                    return $this->obtener_feed_inteligente($usuario_id, $desde_id, $limite);
                }
                // Fallback a cronológico si no hay usuario
                return $this->obtener_publicaciones_feed('comunidad', 0, $desde_id, $limite);

            default:
                return [];
        }
    }

    /**
     * Obtiene el feed con algoritmo inteligente personalizado
     *
     * El score se calcula en base a:
     * - Afinidad con el autor (interacciones previas)
     * - Engagement de la publicación (likes, comentarios, compartidos)
     * - Decadencia temporal (publicaciones más recientes tienen mayor peso)
     * - Diversidad (evitar demasiadas publicaciones del mismo autor)
     */
    private function obtener_feed_inteligente($usuario_id, $desde_id, $limite) {
        global $wpdb;
        $tabla_publicaciones = $wpdb->prefix . 'flavor_social_publicaciones';
        $tabla_seguimientos = $wpdb->prefix . 'flavor_social_seguimientos';
        $tabla_engagement = $wpdb->prefix . 'flavor_social_engagement';

        $desde_id = $desde_id > 0 ? $desde_id : PHP_INT_MAX;

        // Query con score calculado
        // Score = (afinidad_autor * 10) + (engagement_pub * 2) + (recencia * 5)
        // - afinidad_autor: suma de interacciones del usuario con el autor en últimos 30 días
        // - engagement_pub: (likes + comentarios*2 + compartidos*3) / LOG(edad_horas + 2)
        // - recencia: 1 / LOG(edad_horas + 2)
        $sql = "SELECT p.*,
                    COALESCE(afinidad.total_interacciones, 0) as afinidad_autor,
                    (p.me_gusta + p.comentarios * 2 + p.compartidos * 3) as engagement_raw,
                    TIMESTAMPDIFF(HOUR, p.fecha_publicacion, NOW()) as edad_horas,
                    (
                        COALESCE(afinidad.total_interacciones, 0) * 10 +
                        (p.me_gusta + p.comentarios * 2 + p.compartidos * 3) / LOG(TIMESTAMPDIFF(HOUR, p.fecha_publicacion, NOW()) + 2) +
                        100 / LOG(TIMESTAMPDIFF(HOUR, p.fecha_publicacion, NOW()) + 2) +
                        CASE WHEN p.autor_id IN (SELECT seguido_id FROM $tabla_seguimientos WHERE seguidor_id = %d) THEN 50 ELSE 0 END +
                        CASE WHEN p.adjuntos IS NOT NULL AND p.adjuntos != '' AND p.adjuntos != '[]' THEN 10 ELSE 0 END
                    ) as score_total
                FROM $tabla_publicaciones p
                LEFT JOIN (
                    SELECT autor_id, SUM(peso) as total_interacciones
                    FROM $tabla_engagement
                    WHERE usuario_id = %d
                    AND fecha_interaccion > DATE_SUB(NOW(), INTERVAL 30 DAY)
                    GROUP BY autor_id
                ) afinidad ON p.autor_id = afinidad.autor_id
                WHERE p.estado = 'publicado'
                AND p.id < %d
                AND (
                    p.autor_id = %d
                    OR p.autor_id IN (SELECT seguido_id FROM $tabla_seguimientos WHERE seguidor_id = %d)
                    OR p.visibilidad IN ('publica', 'comunidad')
                )
                AND p.fecha_publicacion > DATE_SUB(NOW(), INTERVAL 14 DAY)
                ORDER BY score_total DESC, p.fecha_publicacion DESC
                LIMIT %d";

        $publicaciones = $wpdb->get_results($wpdb->prepare(
            $sql,
            $usuario_id,
            $usuario_id,
            $desde_id,
            $usuario_id,
            $usuario_id,
            $limite * 2 // Obtener más para aplicar diversidad
        ));

        // Aplicar diversidad: no más de 3 publicaciones seguidas del mismo autor
        $publicaciones_filtradas = $this->aplicar_diversidad_feed($publicaciones, 3);

        return array_slice($publicaciones_filtradas, 0, $limite);
    }

    /**
     * Aplica diversidad al feed limitando publicaciones consecutivas del mismo autor
     */
    private function aplicar_diversidad_feed($publicaciones, $max_consecutivas) {
        $resultado = [];
        $contador_autor = [];
        $ultimo_autor = null;
        $consecutivas = 0;

        foreach ($publicaciones as $publicacion) {
            $autor_id = $publicacion->autor_id;

            if ($autor_id === $ultimo_autor) {
                $consecutivas++;
                if ($consecutivas > $max_consecutivas) {
                    continue; // Saltar esta publicación
                }
            } else {
                $consecutivas = 1;
                $ultimo_autor = $autor_id;
            }

            $resultado[] = $publicacion;
        }

        return $resultado;
    }

    /**
     * Registra una interacción para el algoritmo de feed
     */
    public function registrar_engagement($usuario_id, $autor_id, $tipo_interaccion, $contenido_id = null) {
        global $wpdb;
        $tabla_engagement = $wpdb->prefix . 'flavor_social_engagement';

        // Pesos por tipo de interacción
        $pesos_interaccion = [
            'like' => 1.0,
            'comentario' => 2.0,
            'compartido' => 3.0,
            'guardado' => 2.5,
            'clic' => 0.5,
            'tiempo_lectura' => 0.3,
        ];

        $peso = $pesos_interaccion[$tipo_interaccion] ?? 1.0;

        $wpdb->insert(
            $tabla_engagement,
            [
                'usuario_id' => $usuario_id,
                'autor_id' => $autor_id,
                'tipo_interaccion' => $tipo_interaccion,
                'contenido_id' => $contenido_id,
                'peso' => $peso,
                'fecha_interaccion' => current_time('mysql'),
            ],
            ['%d', '%d', '%s', '%d', '%f', '%s']
        );
    }

    /**
     * Obtiene el score de afinidad entre dos usuarios
     */
    public function obtener_afinidad_usuario($usuario_id, $autor_id) {
        global $wpdb;
        $tabla_engagement = $wpdb->prefix . 'flavor_social_engagement';

        return (float) $wpdb->get_var($wpdb->prepare(
            "SELECT COALESCE(SUM(peso), 0)
             FROM $tabla_engagement
             WHERE usuario_id = %d AND autor_id = %d
             AND fecha_interaccion > DATE_SUB(NOW(), INTERVAL 30 DAY)",
            $usuario_id,
            $autor_id
        ));
    }

    /**
     * Obtiene publicaciones de un usuario
     */
    private function obtener_publicaciones_usuario($usuario_id, $desde_id, $limite) {
        global $wpdb;
        $tabla_publicaciones = $wpdb->prefix . 'flavor_social_publicaciones';

        $desde_id = $desde_id > 0 ? $desde_id : PHP_INT_MAX;

        return $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM $tabla_publicaciones
            WHERE autor_id = %d AND estado = 'publicado' AND id < %d
            ORDER BY fecha_publicacion DESC
            LIMIT %d",
            $usuario_id,
            $desde_id,
            $limite
        ));
    }

    /**
     * Obtiene publicaciones con media
     */
    private function obtener_publicaciones_con_media($limite) {
        global $wpdb;
        $tabla_publicaciones = $wpdb->prefix . 'flavor_social_publicaciones';

        return $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM $tabla_publicaciones
            WHERE estado = 'publicado'
            AND visibilidad IN ('publica', 'comunidad')
            AND adjuntos IS NOT NULL AND adjuntos != ''
            ORDER BY fecha_publicacion DESC
            LIMIT %d",
            $limite
        ));
    }

    /**
     * Obtiene publicaciones por hashtag
     */
    private function obtener_publicaciones_por_hashtag($hashtag, $limite) {
        global $wpdb;
        $tabla_publicaciones = $wpdb->prefix . 'flavor_social_publicaciones';
        $tabla_hashtags = $wpdb->prefix . 'flavor_social_hashtags';
        $tabla_hashtags_posts = $wpdb->prefix . 'flavor_social_hashtags_posts';

        return $wpdb->get_results($wpdb->prepare(
            "SELECT p.* FROM $tabla_publicaciones p
            INNER JOIN $tabla_hashtags_posts hp ON p.id = hp.publicacion_id
            INNER JOIN $tabla_hashtags h ON hp.hashtag_id = h.id
            WHERE h.hashtag = %s AND p.estado = 'publicado'
            ORDER BY p.fecha_publicacion DESC
            LIMIT %d",
            mb_strtolower($hashtag),
            $limite
        ));
    }

    /**
     * Obtiene hashtags trending
     */
    private function obtener_hashtags_trending($limite = 10) {
        global $wpdb;
        $tabla_hashtags = $wpdb->prefix . 'flavor_social_hashtags';

        return $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM $tabla_hashtags
            WHERE fecha_ultimo_uso > DATE_SUB(NOW(), INTERVAL 7 DAY)
            ORDER BY total_usos DESC
            LIMIT %d",
            $limite
        ));
    }

    /**
     * Obtiene total de seguidores
     */
    private function obtener_total_seguidores($usuario_id) {
        global $wpdb;
        $tabla_seguimientos = $wpdb->prefix . 'flavor_social_seguimientos';

        return (int) $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM $tabla_seguimientos WHERE seguido_id = %d",
            $usuario_id
        ));
    }

    /**
     * Verifica si un usuario sigue a otro
     */
    private function esta_siguiendo($seguidor_id, $seguido_id) {
        if (!$seguidor_id || !$seguido_id) {
            return false;
        }

        global $wpdb;
        $tabla_seguimientos = $wpdb->prefix . 'flavor_social_seguimientos';

        return (bool) $wpdb->get_var($wpdb->prepare(
            "SELECT id FROM $tabla_seguimientos WHERE seguidor_id = %d AND seguido_id = %d",
            $seguidor_id,
            $seguido_id
        ));
    }

    /**
     * Obtiene perfil completo
     */
    private function obtener_perfil_completo($usuario_id) {
        $usuario = get_userdata($usuario_id);
        if (!$usuario) {
            return null;
        }

        global $wpdb;
        $tabla_perfiles = $wpdb->prefix . 'flavor_social_perfiles';

        $perfil_datos = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM $tabla_perfiles WHERE usuario_id = %d",
            $usuario_id
        ));

        return [
            'id' => $usuario_id,
            'nombre' => $usuario->display_name,
            'username' => $usuario->user_login,
            'avatar' => get_avatar_url($usuario_id, ['size' => 200]),
            'cover_url' => $perfil_datos->cover_url ?? null,
            'bio' => $perfil_datos->bio ?? '',
            'ubicacion' => $perfil_datos->ubicacion ?? '',
            'sitio_web' => $perfil_datos->sitio_web ?? '',
            'es_verificado' => (bool) ($perfil_datos->es_verificado ?? false),
            'total_publicaciones' => (int) ($perfil_datos->total_publicaciones ?? 0),
            'total_seguidores' => (int) ($perfil_datos->total_seguidores ?? 0),
            'total_siguiendo' => (int) ($perfil_datos->total_siguiendo ?? 0),
            'fecha_registro' => $usuario->user_registered,
        ];
    }

    /**
     * Formatea publicacion para API
     */
    private function formatear_publicacion_api($publicacion) {
        $autor = get_userdata($publicacion->autor_id);

        return [
            'id' => $publicacion->id,
            'autor' => [
                'id' => $publicacion->autor_id,
                'nombre' => $autor ? $autor->display_name : 'Usuario',
                'username' => $autor ? $autor->user_login : '',
                'avatar' => get_avatar_url($publicacion->autor_id, ['size' => 50]),
            ],
            'contenido' => $publicacion->contenido,
            'tipo' => $publicacion->tipo,
            'adjuntos' => json_decode($publicacion->adjuntos, true),
            'visibilidad' => $publicacion->visibilidad,
            'me_gusta' => (int) $publicacion->me_gusta,
            'comentarios' => (int) $publicacion->comentarios,
            'compartidos' => (int) $publicacion->compartidos,
            'fecha' => $publicacion->fecha_publicacion,
            'fecha_humana' => human_time_diff(strtotime($publicacion->fecha_publicacion), current_time('timestamp')),
        ];
    }

    // ========================================
    // Renderizado HTML
    // ========================================

    /**
     * Renderiza formulario de crear publicacion
     */
    private function renderizar_crear_publicacion() {
        $usuario_id = get_current_user_id();
        $avatar = get_avatar_url($usuario_id, ['size' => 50]);

        ob_start();
        ?>
        <div class="rs-crear-post">
            <form class="rs-crear-post-form" enctype="multipart/form-data">
                <div class="rs-crear-post-header">
                    <img class="rs-crear-post-avatar" src="<?php echo esc_url($avatar); ?>" alt="">
                    <div class="rs-crear-post-input">
                        <textarea class="rs-crear-post-textarea"
                                  placeholder="<?php echo esc_attr__('¿Que quieres compartir con la comunidad?', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>"
                                  maxlength="<?php echo esc_attr($this->get_setting('max_caracteres_publicacion')); ?>"></textarea>
                    </div>
                </div>
                <div class="rs-crear-post-acciones">
                    <div class="rs-crear-post-adjuntos">
                        <?php if ($this->get_setting('permite_imagenes')): ?>
                        <button type="button" class="rs-adjunto-btn" data-tipo="imagen">
                            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <rect x="3" y="3" width="18" height="18" rx="2"/><circle cx="8.5" cy="8.5" r="1.5"/>
                                <path d="M21 15l-5-5L5 21"/>
                            </svg>
                            <?php echo esc_html__('Foto', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                        </button>
                        <?php endif; ?>
                        <?php if ($this->get_setting('permite_videos')): ?>
                        <button type="button" class="rs-adjunto-btn" data-tipo="video">
                            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <polygon points="23 7 16 12 23 17 23 7"/><rect x="1" y="5" width="15" height="14" rx="2"/>
                            </svg>
                            <?php echo esc_html__('Video', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                        </button>
                        <?php endif; ?>
                    </div>
                    <input type="hidden" name="visibilidad" value="<?php echo esc_attr__('comunidad', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>">
                    <button type="submit" class="rs-btn-publicar"><?php echo esc_html__('Publicar', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></button>
                </div>
            </form>
        </div>
        <?php
        return ob_get_clean();
    }

    /**
     * Renderiza una publicacion
     */
    private function renderizar_publicacion($publicacion_id) {
        global $wpdb;
        $tabla_publicaciones = $wpdb->prefix . 'flavor_social_publicaciones';
        $tabla_reacciones = $wpdb->prefix . 'flavor_social_reacciones';

        $publicacion = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM $tabla_publicaciones WHERE id = %d",
            $publicacion_id
        ));

        if (!$publicacion) {
            return '';
        }

        $autor = get_userdata($publicacion->autor_id);
        $usuario_actual = get_current_user_id();
        $adjuntos_raw = is_string($publicacion->adjuntos) ? $publicacion->adjuntos : '';
        $adjuntos = $adjuntos_raw !== '' ? json_decode($adjuntos_raw, true) : [];
        if (!is_array($adjuntos)) {
            $adjuntos = [];
        }

        // Verificar si el usuario actual dio like
        $usuario_dio_like = false;
        if ($usuario_actual) {
            $usuario_dio_like = (bool) $wpdb->get_var($wpdb->prepare(
                "SELECT id FROM $tabla_reacciones WHERE publicacion_id = %d AND usuario_id = %d",
                $publicacion_id,
                $usuario_actual
            ));
        }

        // Procesar contenido con hashtags y menciones clickeables
        $contenido_procesado = $this->procesar_contenido_html($publicacion->contenido);

        ob_start();
        ?>
        <article class="rs-post" data-post-id="<?php echo esc_attr($publicacion->id); ?>">
            <header class="rs-post-header">
                <div class="rs-post-autor">
                    <img class="rs-post-avatar"
                         src="<?php echo esc_url(get_avatar_url($publicacion->autor_id, ['size' => 50])); ?>"
                         alt="">
                    <div class="rs-post-autor-info">
                        <h4><a href="<?php echo esc_url(add_query_arg('usuario_id', intval($publicacion->autor_id), home_url('/mi-portal/red-social/perfil/'))); ?>">
                            <?php echo esc_html($autor ? $autor->display_name : 'Usuario'); ?>
                        </a></h4>
                        <div class="rs-post-meta">
                            <span>@<?php echo esc_html($autor ? $autor->user_login : ''); ?></span>
                            <span class="rs-post-tiempo"><?php echo human_time_diff(strtotime($publicacion->fecha_publicacion), current_time('timestamp')); ?></span>
                        </div>
                    </div>
                </div>
                <button class="rs-post-menu-btn">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="currentColor">
                        <circle cx="12" cy="5" r="2"/><circle cx="12" cy="12" r="2"/><circle cx="12" cy="19" r="2"/>
                    </svg>
                </button>
            </header>

            <div class="rs-post-contenido">
                <p class="rs-post-texto"><?php echo $contenido_procesado; ?></p>
            </div>

            <?php if (!empty($adjuntos)): ?>
                <div class="rs-post-media">
                    <?php $total_adjuntos = count($adjuntos); ?>
                    <div class="rs-post-media-grid <?php echo $total_adjuntos > 1 ? 'rs-media-' . min($total_adjuntos, 4) : ''; ?>">
                        <?php foreach (array_slice($adjuntos, 0, 4) as $index => $adjunto): ?>
                            <img src="<?php echo esc_url($adjunto); ?>" alt="" loading="lazy">
                        <?php endforeach; ?>
                    </div>
                </div>
            <?php endif; ?>

            <?php if ($publicacion->me_gusta > 0 || $publicacion->comentarios > 0): ?>
            <div class="rs-post-stats">
                <div class="rs-post-likes-count">
                    <?php if ($publicacion->me_gusta > 0): ?>
                        <span><?php echo number_format($publicacion->me_gusta); ?> me gusta</span>
                    <?php endif; ?>
                </div>
                <div>
                    <?php if ($publicacion->comentarios > 0): ?>
                        <span><?php echo number_format($publicacion->comentarios); ?> comentarios</span>
                    <?php endif; ?>
                </div>
            </div>
            <?php endif; ?>

            <div class="rs-post-acciones">
                <button class="rs-post-accion <?php echo $usuario_dio_like ? 'rs-liked' : ''; ?>" data-accion="like">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="<?php echo $usuario_dio_like ? 'currentColor' : 'none'; ?>" stroke="currentColor" stroke-width="2">
                        <path d="M12 21.35l-1.45-1.32C5.4 15.36 2 12.28 2 8.5 2 5.42 4.42 3 7.5 3c1.74 0 3.41.81 4.5 2.09C13.09 3.81 14.76 3 16.5 3 19.58 3 22 5.42 22 8.5c0 3.78-3.4 6.86-8.55 11.54L12 21.35z"/>
                    </svg>
                    <span class="rs-like-count"><?php echo $publicacion->me_gusta ?: ''; ?></span>
                </button>
                <button class="rs-post-accion" data-accion="comentar">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M21 15a2 2 0 01-2 2H7l-4 4V5a2 2 0 012-2h14a2 2 0 012 2z"/>
                    </svg>
                    <span><?php echo $publicacion->comentarios ?: ''; ?></span>
                </button>
                <button class="rs-post-accion" data-accion="compartir">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <circle cx="18" cy="5" r="3"/><circle cx="6" cy="12" r="3"/><circle cx="18" cy="19" r="3"/>
                        <line x1="8.59" y1="13.51" x2="15.42" y2="17.49"/><line x1="15.41" y1="6.51" x2="8.59" y2="10.49"/>
                    </svg>
                    <span><?php echo esc_html__('Compartir', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></span>
                </button>
                <button class="rs-post-accion" data-accion="guardar">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M19 21l-7-5-7 5V5a2 2 0 012-2h10a2 2 0 012 2z"/>
                    </svg>
                </button>
            </div>
        </article>
        <?php
        return ob_get_clean();
    }

    /**
     * Procesa contenido para HTML (hashtags y menciones)
     */
    private function procesar_contenido_html($contenido) {
        $contenido = esc_html($contenido);

        // Hashtags
        $contenido = preg_replace(
            '/#([a-zA-Z0-9_\p{L}]+)/u',
            '<span class="rs-hashtag" data-tag="$1">#$1</span>',
            $contenido
        );

        // Menciones
        $contenido = preg_replace(
            '/@([a-zA-Z0-9_]+)/',
            '<span class="rs-mencion" data-usuario="$1">@$1</span>',
            $contenido
        );

        return $contenido;
    }

    /**
     * Renderiza un comentario
     */
    private function renderizar_comentario($comentario_id) {
        global $wpdb;
        $tabla_comentarios = $wpdb->prefix . 'flavor_social_comentarios';

        $comentario = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM $tabla_comentarios WHERE id = %d",
            $comentario_id
        ));

        if (!$comentario) {
            return '';
        }

        $autor = get_userdata($comentario->autor_id);

        ob_start();
        ?>
        <div class="rs-comentario" data-comentario-id="<?php echo esc_attr($comentario->id); ?>">
            <img class="rs-comentario-avatar"
                 src="<?php echo esc_url(get_avatar_url($comentario->autor_id, ['size' => 40])); ?>"
                 alt="">
            <div class="rs-comentario-contenido">
                <div class="rs-comentario-burbuja">
                    <div class="rs-comentario-autor">
                        <?php echo esc_html($autor ? $autor->display_name : 'Usuario'); ?>
                    </div>
                    <p class="rs-comentario-texto"><?php echo esc_html($comentario->contenido); ?></p>
                </div>
                <div class="rs-comentario-acciones">
                    <button class="rs-comentario-like"><?php echo $comentario->me_gusta > 0 ? $comentario->me_gusta . ' Me gusta' : 'Me gusta'; ?></button>
                    <button class="rs-comentario-responder"><?php echo esc_html__('Responder', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></button>
                    <span><?php echo human_time_diff(strtotime($comentario->fecha_creacion), current_time('timestamp')); ?></span>
                </div>
            </div>
        </div>
        <?php
        return ob_get_clean();
    }

    /**
     * Obtiene HTML de seccion de comentarios
     */
    private function get_seccion_comentarios_html($comentarios_html, $publicacion_id) {
        $usuario_id = get_current_user_id();

        ob_start();
        ?>
        <div class="rs-comentarios">
            <div class="rs-comentarios-lista">
                <?php echo $comentarios_html; ?>
            </div>
            <?php if ($usuario_id): ?>
            <form class="rs-comentar-form">
                <img class="rs-comentario-avatar"
                     src="<?php echo esc_url(get_avatar_url($usuario_id, ['size' => 40])); ?>"
                     alt="">
                <input type="text" class="rs-comentar-input" placeholder="<?php echo esc_attr__('Escribe un comentario...', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>">
                <button type="submit" class="rs-comentar-enviar"><?php echo esc_html__('Enviar', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></button>
            </form>
            <?php endif; ?>
        </div>
        <?php
        return ob_get_clean();
    }

    /**
     * Renderiza widget de sugerencias
     */
    private function renderizar_widget_sugerencias() {
        $usuario_id = get_current_user_id();
        if (!$usuario_id) {
            return '';
        }

        $sugerencias = $this->obtener_sugerencias_usuarios($usuario_id, 5);

        if (empty($sugerencias)) {
            return '';
        }

        ob_start();
        ?>
        <div class="rs-widget">
            <h3 class="rs-widget-titulo"><?php echo esc_html__('Sugerencias para ti', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></h3>
            <div class="rs-sugerencias-lista">
                <?php foreach ($sugerencias as $sugerencia): ?>
                    <?php $usuario = get_userdata($sugerencia->ID); ?>
                    <div class="rs-sugerencia">
                        <img class="rs-sugerencia-avatar"
                             src="<?php echo esc_url(get_avatar_url($sugerencia->ID, ['size' => 50])); ?>"
                             alt="">
                        <div class="rs-sugerencia-info">
                            <h4 class="rs-sugerencia-nombre"><?php echo esc_html($usuario->display_name); ?></h4>
                            <span class="rs-sugerencia-motivo"><?php echo esc_html__('Sugerido para ti', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></span>
                        </div>
                        <button class="rs-btn-seguir" data-usuario-id="<?php echo esc_attr($sugerencia->ID); ?>">
                            <?php echo esc_html__('Seguir', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                        </button>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
        <?php
        return ob_get_clean();
    }

    /**
     * Obtiene sugerencias de usuarios
     */
    private function obtener_sugerencias_usuarios($usuario_id, $limite) {
        global $wpdb;
        $tabla_seguimientos = $wpdb->prefix . 'flavor_social_seguimientos';

        return $wpdb->get_results($wpdb->prepare(
            "SELECT u.ID FROM {$wpdb->users} u
            WHERE u.ID != %d
            AND u.ID NOT IN (SELECT seguido_id FROM $tabla_seguimientos WHERE seguidor_id = %d)
            ORDER BY RAND()
            LIMIT %d",
            $usuario_id,
            $usuario_id,
            $limite
        ));
    }

    /**
     * Renderiza widget de trending
     */
    private function renderizar_widget_trending() {
        $trending = $this->obtener_hashtags_trending(5);

        if (empty($trending)) {
            return '';
        }

        ob_start();
        ?>
        <div class="rs-widget">
            <h3 class="rs-widget-titulo"><?php echo esc_html__('Tendencias', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></h3>
            <div class="rs-trending-lista">
                <?php foreach ($trending as $index => $hashtag): ?>
                    <div class="rs-trending-item" onclick="window.location='<?php echo esc_url(add_query_arg('hashtag', $hashtag->hashtag, home_url('/mi-portal/red-social/explorar/'))); ?>'">
                        <div class="rs-trending-categoria"><?php echo $index + 1; ?>. Tendencia</div>
                        <div class="rs-trending-hashtag">#<?php echo esc_html($hashtag->hashtag); ?></div>
                        <div class="rs-trending-posts"><?php echo number_format($hashtag->total_usos); ?> publicaciones</div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
        <?php
        return ob_get_clean();
    }

    /**
     * Limpia historias expiradas (cron)
     */
    public function limpiar_historias_expiradas() {
        global $wpdb;
        $tabla_historias = $wpdb->prefix . 'flavor_social_historias';

        $wpdb->query("DELETE FROM $tabla_historias WHERE fecha_expiracion < NOW()");
    }

    // ========================================
    // Metodos heredados (Module Base)
    // ========================================

    /**
     * {@inheritdoc}
     */
    public function get_actions() {
        return [
            'timeline' => [
                'description' => 'Ver timeline personal',
                'params' => ['desde', 'limite'],
            ],
            'publicar' => [
                'description' => 'Crear nueva publicacion',
                'params' => ['contenido', 'tipo', 'adjuntos', 'visibilidad'],
            ],
            'comentar' => [
                'description' => 'Comentar publicacion',
                'params' => ['publicacion_id', 'contenido'],
            ],
            'reaccionar' => [
                'description' => 'Dar me gusta o reaccionar',
                'params' => ['publicacion_id', 'tipo'],
            ],
            'seguir' => [
                'description' => 'Seguir a un usuario',
                'params' => ['usuario_id'],
            ],
            'perfil' => [
                'description' => 'Ver perfil de usuario',
                'params' => ['usuario_id'],
            ],
            'buscar' => [
                'description' => 'Buscar publicaciones o usuarios',
                'params' => ['query', 'tipo'],
            ],
            'trending' => [
                'description' => 'Ver publicaciones populares',
                'params' => ['periodo'],
            ],
            'historias' => [
                'description' => 'Ver historias de usuarios',
                'params' => ['usuario_id'],
            ],
            'notificaciones' => [
                'description' => 'Ver notificaciones',
                'params' => ['limite'],
            ],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function execute_action($action_name, $params) {
        $aliases = [
            'listar' => 'timeline',
            'listado' => 'timeline',
            'feed' => 'timeline',
            'explorar' => 'timeline',
            'buscar' => 'timeline',
            'perfil' => 'perfil',
            'mi-perfil' => 'perfil',
            'mensajes' => 'mensajes',
            'notificaciones' => 'notificaciones',
            'historias' => 'historias',
            'actividad' => 'mi_actividad',
            'mi-actividad' => 'mi_actividad',
            'reputacion' => 'reputacion',
            'ranking' => 'ranking',
            'badges' => 'badges',
            'crear' => 'crear_publicacion',
            'publicar' => 'crear_publicacion',
            'nuevo' => 'crear_publicacion',
            'trending' => 'explorar',
        ];

        $action_name = $aliases[$action_name] ?? $action_name;
        $metodo_accion = 'action_' . $action_name;

        if (method_exists($this, $metodo_accion)) {
            return $this->$metodo_accion($params);
        }

        return [
            'success' => false,
            'error' => __('La vista solicitada no esta disponible en Red Social.', FLAVOR_PLATFORM_TEXT_DOMAIN),
        ];
    }

    /**
     * Accion: Ver timeline
     */
    private function action_timeline($params) {
        $usuario_id = get_current_user_id();

        if (!$usuario_id) {
            return ['success' => false, 'error' => __('Debes iniciar sesión para ver tu timeline.', FLAVOR_PLATFORM_TEXT_DOMAIN)];
        }

        $limite = absint($params['limite'] ?? 20);
        $desde_id = absint($params['desde'] ?? 0);

        $publicaciones = $this->obtener_publicaciones_feed('timeline', $usuario_id, $desde_id, $limite);

        return [
            'success' => true,
            'publicaciones' => array_map([$this, 'formatear_publicacion_api'], $publicaciones),
        ];
    }

    private function action_perfil($params) {
        $usuario_id = absint($params['usuario_id'] ?? get_current_user_id());
        return do_shortcode('[rs_perfil usuario_id="' . $usuario_id . '"]');
    }

    private function action_explorar($params) {
        $limite = absint($params['limite'] ?? 20);
        return do_shortcode('[rs_explorar limite="' . $limite . '"]');
    }

    private function action_crear_publicacion($params) {
        return do_shortcode('[rs_crear_publicacion]');
    }

    private function action_notificaciones($params) {
        return do_shortcode('[rs_notificaciones]');
    }

    private function action_mensajes($params) {
        if (!is_user_logged_in()) {
            return ['success' => false, 'error' => __('Debes iniciar sesión para ver tus mensajes.', FLAVOR_PLATFORM_TEXT_DOMAIN)];
        }

        return do_shortcode('[chat_interno_conversaciones]');
    }

    private function action_historias($params) {
        return do_shortcode('[rs_historias]');
    }

    private function action_reputacion($params) {
        $usuario_id = absint($params['usuario_id'] ?? get_current_user_id());
        return do_shortcode('[rs_reputacion usuario_id="' . $usuario_id . '"]');
    }

    private function action_ranking($params) {
        $limite = absint($params['limite'] ?? 10);
        return do_shortcode('[rs_ranking limite="' . $limite . '"]');
    }

    private function action_badges($params) {
        return do_shortcode('[rs_badges]');
    }

    private function action_mi_actividad($params) {
        return do_shortcode('[rs_mi_actividad]');
    }

    /**
     * Componentes web del modulo
     */
    public function get_web_components() {
        return [
            'hero_social' => [
                'label' => __('Hero Red Social', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'category' => 'hero',
                'icon' => 'dashicons-groups',
                'fields' => [
                    'titulo' => ['type' => 'text', 'default' => __('Red Social Comunitaria', FLAVOR_PLATFORM_TEXT_DOMAIN)],
                    'subtitulo' => ['type' => 'textarea', 'default' => __('Conecta con tus vecinos de forma privada y segura', FLAVOR_PLATFORM_TEXT_DOMAIN)],
                    'imagen_fondo' => ['type' => 'image', 'default' => ''],
                    'mostrar_cta' => ['type' => 'toggle', 'default' => true],
                ],
                'template' => 'red-social/hero',
            ],
            'timeline_feed' => [
                'label' => __('Feed de Publicaciones', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'category' => 'content',
                'icon' => 'dashicons-rss',
                'fields' => [
                    'titulo' => ['type' => 'text', 'default' => __('Ultimas Publicaciones', FLAVOR_PLATFORM_TEXT_DOMAIN)],
                    'limite' => ['type' => 'number', 'default' => 10],
                    'mostrar_formulario' => ['type' => 'toggle', 'default' => true],
                    'tipo_feed' => ['type' => 'select', 'options' => ['timeline', 'comunidad', 'trending'], 'default' => 'timeline'],
                ],
                'template' => 'red-social/feed',
            ],
            'stats_comunidad' => [
                'label' => __('Estadisticas de la Comunidad', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'category' => 'content',
                'icon' => 'dashicons-chart-area',
                'fields' => [
                    'titulo' => ['type' => 'text', 'default' => __('Nuestra Comunidad', FLAVOR_PLATFORM_TEXT_DOMAIN)],
                    'mostrar_miembros' => ['type' => 'toggle', 'default' => true],
                    'mostrar_publicaciones' => ['type' => 'toggle', 'default' => true],
                    'mostrar_actividad' => ['type' => 'toggle', 'default' => true],
                ],
                'template' => 'red-social/stats',
            ],
            'sugerencias_usuarios' => [
                'label' => __('Sugerencias de Conexion', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'category' => 'listings',
                'icon' => 'dashicons-admin-users',
                'fields' => [
                    'titulo' => ['type' => 'text', 'default' => __('Vecinos que Quizas Conozcas', FLAVOR_PLATFORM_TEXT_DOMAIN)],
                    'limite' => ['type' => 'number', 'default' => 6],
                    'criterio' => ['type' => 'select', 'options' => ['cercania', 'intereses', 'aleatorio'], 'default' => 'cercania'],
                ],
                'template' => 'red-social/sugerencias',
            ],
            'historias_carousel' => [
                'label' => __('Carrusel de Historias', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'category' => 'content',
                'icon' => 'dashicons-format-gallery',
                'fields' => [
                    'mostrar_crear' => ['type' => 'toggle', 'default' => true],
                    'limite' => ['type' => 'number', 'default' => 10],
                ],
                'template' => 'red-social/historias',
            ],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function get_tool_definitions() {
        return [
            [
                'name' => 'social_timeline',
                'description' => 'Ver timeline de la red social',
                'input_schema' => [
                    'type' => 'object',
                    'properties' => [
                        'tipo' => ['type' => 'string', 'description' => 'Tipo de feed: timeline, comunidad, trending'],
                        'limite' => ['type' => 'integer', 'description' => 'Numero de publicaciones'],
                    ],
                ],
            ],
            [
                'name' => 'social_publicar',
                'description' => 'Crear nueva publicacion',
                'input_schema' => [
                    'type' => 'object',
                    'properties' => [
                        'contenido' => ['type' => 'string', 'description' => 'Contenido de la publicacion'],
                        'visibilidad' => ['type' => 'string', 'description' => 'Visibilidad: publica, comunidad, seguidores, privada'],
                    ],
                    'required' => ['contenido'],
                ],
            ],
            [
                'name' => 'social_perfil',
                'description' => 'Ver perfil de un usuario',
                'input_schema' => [
                    'type' => 'object',
                    'properties' => [
                        'usuario_id' => ['type' => 'integer', 'description' => 'ID del usuario'],
                    ],
                    'required' => ['usuario_id'],
                ],
            ],
            [
                'name' => 'social_buscar',
                'description' => 'Buscar usuarios o publicaciones',
                'input_schema' => [
                    'type' => 'object',
                    'properties' => [
                        'query' => ['type' => 'string', 'description' => 'Termino de busqueda'],
                        'tipo' => ['type' => 'string', 'description' => 'Tipo: usuarios, publicaciones, hashtags'],
                    ],
                    'required' => ['query'],
                ],
            ],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function get_knowledge_base() {
        return <<<KNOWLEDGE
**Red Social Comunitaria**

Plataforma social alternativa sin publicidad, algoritmos transparentes y centrada en la comunidad.

**Caracteristicas principales:**
- Timeline cronologico (sin algoritmos ocultos)
- Publicaciones de texto, fotos y videos
- Comentarios y reacciones (me gusta, me encanta, etc.)
- Hashtags para categorizar contenido
- Menciones a otros usuarios (@usuario)
- Historias que desaparecen en 24 horas
- Sistema de seguidores/siguiendo
- Notificaciones en tiempo real
- Perfiles personalizables con biografia y cover

**Tipos de visibilidad:**
- Publica: Visible para todos
- Comunidad: Solo miembros registrados
- Seguidores: Solo tus seguidores
- Privada: Solo tu

**Privacidad y seguridad:**
- Sin venta de datos personales
- Sin rastreo publicitario
- Control total sobre quien ve tu contenido
- Moderacion comunitaria
- Datos alojados en servidores locales

**Diferencias con redes comerciales:**
- Sin publicidad ni contenido patrocinado
- Sin algoritmos de manipulacion
- Propiedad y control comunitario
- Transparencia total en el funcionamiento
- Enfoque en conexiones reales, no en engagement

**Shortcodes disponibles:**
- [rs_feed] - Muestra el feed de publicaciones
- [rs_perfil] - Muestra el perfil de usuario
- [rs_explorar] - Pagina de exploracion y trending
- [rs_crear_publicacion] - Formulario para crear post
- [rs_notificaciones] - Lista de notificaciones
- [rs_historias] - Carrusel de historias
KNOWLEDGE;
    }

    /**
     * {@inheritdoc}
     */
    public function get_faqs() {
        return [
            [
                'pregunta' => '¿Es segura mi informacion?',
                'respuesta' => 'Si, tus datos estan en servidores comunitarios y no se venden a terceros. Tienes control total sobre la visibilidad de tu contenido.',
            ],
            [
                'pregunta' => '¿Por que no hay publicidad?',
                'respuesta' => 'Es una red social comunitaria autofinanciada, no comercial. No necesitamos vender tus datos ni mostrarte anuncios.',
            ],
            [
                'pregunta' => '¿Como funcionan las historias?',
                'respuesta' => 'Las historias son publicaciones temporales que desaparecen automaticamente despues de 24 horas. Puedes subir fotos, videos o texto.',
            ],
            [
                'pregunta' => '¿Puedo hacer mi perfil privado?',
                'respuesta' => 'Si, puedes configurar tu perfil como privado para que solo tus seguidores aprobados vean tu contenido.',
            ],
            [
                'pregunta' => '¿Como uso los hashtags?',
                'respuesta' => 'Usa # seguido de una palabra (ej: #comunidad) para categorizar tu publicacion. Los hashtags populares aparecen en tendencias.',
            ],
        ];
    }

    // =========================================================================
    // PANEL DE ADMINISTRACIÓN UNIFICADO
    // =========================================================================

    /**
     * Configuración para el Panel de Administración Unificado
     *
     * @return array
     */
    protected function get_admin_config() {
        return [
            'id' => 'red_social',
            'label' => __('Red Social', FLAVOR_PLATFORM_TEXT_DOMAIN),
            'icon' => 'dashicons-share',
            'capability' => 'manage_options',
            'categoria' => 'comunidad',
            'paginas' => [
                [
                    'slug' => 'flavor-red-social-dashboard',
                    'titulo' => __('Dashboard', FLAVOR_PLATFORM_TEXT_DOMAIN),
                    'callback' => [$this, 'render_pagina_dashboard'],
                ],
                [
                    'slug' => 'flavor-red-social-publicaciones',
                    'titulo' => __('Publicaciones', FLAVOR_PLATFORM_TEXT_DOMAIN),
                    'callback' => [$this, 'render_admin_publicaciones'],
                    'badge' => [$this, 'contar_publicaciones_pendientes'],
                ],
                [
                    'slug' => 'flavor-red-social-moderacion',
                    'titulo' => __('Moderación', FLAVOR_PLATFORM_TEXT_DOMAIN),
                    'callback' => [$this, 'render_admin_moderacion'],
                    'badge' => [$this, 'contar_reportes_pendientes'],
                ],
            ],
            'dashboard_widget' => [$this, 'render_dashboard_widget'],
            'estadisticas' => [$this, 'get_estadisticas_admin'],
        ];
    }

    /**
     * Renderizar dashboard del panel unificado
     */
    public function render_admin_dashboard() {
        $ruta_template = FLAVOR_CHAT_IA_PATH . 'includes/modules/red-social/views/dashboard.php';
        if (file_exists($ruta_template)) {
            include $ruta_template;
        } else {
            $this->render_admin_dashboard_fallback();
        }
    }

    /**
     * Renderizar dashboard fallback cuando no existe template
     */
    private function render_admin_dashboard_fallback() {
        global $wpdb;
        $tabla_publicaciones = $wpdb->prefix . 'flavor_social_publicaciones';
        $tabla_perfiles = $wpdb->prefix . 'flavor_social_perfiles';

        $total_publicaciones = (int) $wpdb->get_var("SELECT COUNT(*) FROM $tabla_publicaciones");
        $total_usuarios = (int) $wpdb->get_var("SELECT COUNT(*) FROM $tabla_perfiles");
        $publicaciones_hoy = (int) $wpdb->get_var(
            $wpdb->prepare(
                "SELECT COUNT(*) FROM $tabla_publicaciones WHERE DATE(fecha_publicacion) = %s",
                current_time('Y-m-d')
            )
        );
        ?>
        <div class="wrap">
            <h1><?php esc_html_e('Dashboard Red Social', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></h1>

            <div class="flavor-admin-stats" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 20px; margin-top: 20px;">
                <div class="flavor-stat-card" style="background: #fff; padding: 20px; border-radius: 8px; box-shadow: 0 1px 3px rgba(0,0,0,0.1);">
                    <span class="dashicons dashicons-format-aside" style="font-size: 32px; color: #0073aa;"></span>
                    <h3 style="margin: 10px 0 5px;"><?php echo esc_html($total_publicaciones); ?></h3>
                    <p style="margin: 0; color: #666;"><?php esc_html_e('Total Publicaciones', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></p>
                </div>

                <div class="flavor-stat-card" style="background: #fff; padding: 20px; border-radius: 8px; box-shadow: 0 1px 3px rgba(0,0,0,0.1);">
                    <span class="dashicons dashicons-groups" style="font-size: 32px; color: #00a32a;"></span>
                    <h3 style="margin: 10px 0 5px;"><?php echo esc_html($total_usuarios); ?></h3>
                    <p style="margin: 0; color: #666;"><?php esc_html_e('Usuarios Activos', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></p>
                </div>

                <div class="flavor-stat-card" style="background: #fff; padding: 20px; border-radius: 8px; box-shadow: 0 1px 3px rgba(0,0,0,0.1);">
                    <span class="dashicons dashicons-calendar-alt" style="font-size: 32px; color: #dba617;"></span>
                    <h3 style="margin: 10px 0 5px;"><?php echo esc_html($publicaciones_hoy); ?></h3>
                    <p style="margin: 0; color: #666;"><?php esc_html_e('Publicaciones Hoy', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></p>
                </div>
            </div>

            <div class="flavor-admin-section" style="margin-top: 30px;">
                <h2><?php esc_html_e('Acciones Rápidas', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></h2>
                <p>
                    <a href="<?php echo esc_url(admin_url('admin.php?page=flavor-red-social-publicaciones')); ?>" class="button button-primary">
                        <?php esc_html_e('Ver Publicaciones', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                    </a>
                    <a href="<?php echo esc_url(admin_url('admin.php?page=flavor-red-social-moderacion')); ?>" class="button">
                        <?php esc_html_e('Moderar Contenido', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                    </a>
                </p>
            </div>
        </div>
        <?php
    }

    /**
     * Renderizar página de publicaciones del panel unificado
     */
    public function render_admin_publicaciones() {
        $ruta_template = FLAVOR_CHAT_IA_PATH . 'includes/modules/red-social/views/publicaciones.php';
        if (file_exists($ruta_template)) {
            include $ruta_template;
        } else {
            $this->render_admin_publicaciones_fallback();
        }
    }

    /**
     * Renderizar publicaciones fallback cuando no existe template
     */
    private function render_admin_publicaciones_fallback() {
        global $wpdb;
        $tabla_publicaciones = $wpdb->prefix . 'flavor_social_publicaciones';

        $publicaciones = $wpdb->get_results(
            "SELECT p.*, u.display_name as autor_nombre
             FROM $tabla_publicaciones p
             LEFT JOIN {$wpdb->users} u ON p.usuario_id = u.ID
             ORDER BY p.fecha_creacion DESC
             LIMIT 50"
        );
        ?>
        <div class="wrap">
            <h1><?php esc_html_e('Publicaciones', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></h1>

            <table class="wp-list-table widefat fixed striped">
                <thead>
                    <tr>
                        <th><?php esc_html_e('ID', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></th>
                        <th><?php esc_html_e('Autor', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></th>
                        <th><?php esc_html_e('Contenido', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></th>
                        <th><?php esc_html_e('Estado', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></th>
                        <th><?php esc_html_e('Fecha', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></th>
                        <th><?php esc_html_e('Acciones', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($publicaciones)): ?>
                        <tr>
                            <td colspan="6"><?php esc_html_e('No hay publicaciones.', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($publicaciones as $publicacion): ?>
                            <tr>
                                <td><?php echo esc_html($publicacion->id); ?></td>
                                <td><?php echo esc_html($publicacion->autor_nombre ?: __('Usuario eliminado', FLAVOR_PLATFORM_TEXT_DOMAIN)); ?></td>
                                <td><?php echo esc_html(wp_trim_words(strip_tags($publicacion->contenido), 15)); ?></td>
                                <td>
                                    <span class="status-<?php echo esc_attr($publicacion->estado ?? 'publicado'); ?>">
                                        <?php echo esc_html(ucfirst($publicacion->estado ?? 'publicado')); ?>
                                    </span>
                                </td>
                                <td><?php echo esc_html(date_i18n(get_option('date_format'), strtotime($publicacion->fecha_creacion))); ?></td>
                                <td>
                                    <button class="button button-small rs-toggle-detalle-publicacion" type="button" data-target="rs-publicacion-<?php echo esc_attr($publicacion->id); ?>">
                                        <?php esc_html_e('Ver', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                                    </button>
                                </td>
                            </tr>
                            <tr id="rs-publicacion-<?php echo esc_attr($publicacion->id); ?>" class="rs-detalle-publicacion" style="display:none;">
                                <td colspan="6">
                                    <div class="rs-detalle-publicacion__body">
                                        <h4><?php esc_html_e('Detalle de la publicación', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></h4>
                                        <div class="rs-detalle-publicacion__content">
                                            <?php echo wp_kses_post(wpautop($publicacion->contenido)); ?>
                                        </div>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
        <style>
            .rs-detalle-publicacion__body {
                padding: 12px 4px;
            }
            .rs-detalle-publicacion__body h4 {
                margin: 0 0 8px;
            }
            .rs-detalle-publicacion__content {
                background: #fff;
                border: 1px solid #e0e0e0;
                border-radius: 8px;
                padding: 12px;
                color: #1f2937;
            }
        </style>
        <script>
            document.addEventListener('DOMContentLoaded', function() {
                document.querySelectorAll('.rs-toggle-detalle-publicacion').forEach(function(btn) {
                    btn.addEventListener('click', function() {
                        var target = document.getElementById(btn.dataset.target);
                        if (!target) return;
                        target.style.display = target.style.display === 'none' ? 'table-row' : 'none';
                    });
                });
            });
        </script>
        <?php
    }

    /**
     * Renderizar página de moderación del panel unificado
     */
    public function render_admin_moderacion() {
        $ruta_template = FLAVOR_CHAT_IA_PATH . 'includes/modules/red-social/views/moderacion.php';
        if (file_exists($ruta_template)) {
            include $ruta_template;
        } else {
            $this->render_admin_moderacion_fallback();
        }
    }

    /**
     * Renderizar moderación fallback cuando no existe template
     */
    private function render_admin_moderacion_fallback() {
        global $wpdb;
        $tabla_reportes = $wpdb->prefix . 'flavor_social_reportes';

        $tabla_existe = $wpdb->get_var($wpdb->prepare("SHOW TABLES LIKE %s", $tabla_reportes));
        $reportes = [];

        if ($tabla_existe) {
            $reportes = $wpdb->get_results(
                "SELECT r.*, u.display_name as reportador_nombre
                 FROM $tabla_reportes r
                 LEFT JOIN {$wpdb->users} u ON r.usuario_id = u.ID
                 WHERE r.estado = 'pendiente'
                 ORDER BY r.fecha_creacion DESC
                 LIMIT 50"
            );
        }
        ?>
        <div class="wrap">
            <h1><?php esc_html_e('Moderación de Contenido', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></h1>

            <div class="notice notice-info">
                <p><?php esc_html_e('Revisa y gestiona los reportes de contenido de la red social.', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></p>
            </div>

            <?php if (empty($reportes)): ?>
                <div class="notice notice-success">
                    <p><?php esc_html_e('No hay reportes pendientes de revisión.', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></p>
                </div>
            <?php else: ?>
                <table class="wp-list-table widefat fixed striped">
                    <thead>
                        <tr>
                            <th><?php esc_html_e('ID', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></th>
                            <th><?php esc_html_e('Tipo', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></th>
                            <th><?php esc_html_e('Motivo', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></th>
                            <th><?php esc_html_e('Reportado por', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></th>
                            <th><?php esc_html_e('Fecha', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></th>
                            <th><?php esc_html_e('Acciones', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($reportes as $reporte): ?>
                            <tr>
                                <td><?php echo esc_html($reporte->id); ?></td>
                                <td><?php echo esc_html($reporte->tipo_contenido ?? 'publicacion'); ?></td>
                                <td><?php echo esc_html($reporte->motivo ?? '-'); ?></td>
                                <td><?php echo esc_html($reporte->reportador_nombre ?: __('Anónimo', FLAVOR_PLATFORM_TEXT_DOMAIN)); ?></td>
                                <td><?php echo esc_html(date_i18n(get_option('date_format'), strtotime($reporte->fecha_creacion))); ?></td>
                                <td>
                                    <button class="button button-small"><?php esc_html_e('Revisar', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></button>
                                    <button class="button button-small"><?php esc_html_e('Descartar', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></button>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </div>
        <?php
    }

    /**
     * Contar publicaciones pendientes de moderación
     *
     * @return int
     */
    public function contar_publicaciones_pendientes() {
        // Verificar que el módulo esté activo
        if (!$this->can_activate()) {
            return 0;
        }

        global $wpdb;
        $tabla_publicaciones = $wpdb->prefix . 'flavor_social_publicaciones';

        $tabla_existe = $wpdb->get_var($wpdb->prepare("SHOW TABLES LIKE %s", $tabla_publicaciones));
        if (!$tabla_existe) {
            return 0;
        }

        return (int) $wpdb->get_var(
            "SELECT COUNT(*) FROM $tabla_publicaciones WHERE estado = 'pendiente'"
        );
    }

    /**
     * Contar reportes pendientes de revisión
     *
     * @return int
     */
    public function contar_reportes_pendientes() {
        // Verificar que el módulo esté activo
        if (!$this->can_activate()) {
            return 0;
        }

        global $wpdb;
        $tabla_reportes = $wpdb->prefix . 'flavor_social_reportes';

        $tabla_existe = $wpdb->get_var($wpdb->prepare("SHOW TABLES LIKE %s", $tabla_reportes));
        if (!$tabla_existe) {
            return 0;
        }

        return (int) $wpdb->get_var(
            "SELECT COUNT(*) FROM $tabla_reportes WHERE estado = 'pendiente'"
        );
    }

    /**
     * Renderizar widget del dashboard unificado
     */
    public function render_dashboard_widget() {
        global $wpdb;
        $tabla_publicaciones = $wpdb->prefix . 'flavor_social_publicaciones';
        $tabla_perfiles = $wpdb->prefix . 'flavor_social_perfiles';

        $total_publicaciones = 0;
        $total_usuarios = 0;
        $publicaciones_semana = 0;

        if (Flavor_Chat_Helpers::tabla_existe($tabla_publicaciones)) {
            $total_publicaciones = (int) $wpdb->get_var("SELECT COUNT(*) FROM $tabla_publicaciones");
            $publicaciones_semana = (int) $wpdb->get_var(
                "SELECT COUNT(*) FROM $tabla_publicaciones WHERE fecha_publicacion >= DATE_SUB(NOW(), INTERVAL 7 DAY)"
            );
        }

        if (Flavor_Chat_Helpers::tabla_existe($tabla_perfiles)) {
            $total_usuarios = (int) $wpdb->get_var("SELECT COUNT(*) FROM $tabla_perfiles");
        }
        ?>
        <div class="flavor-widget-stats">
            <div class="stat-item">
                <span class="stat-numero"><?php echo esc_html($total_publicaciones); ?></span>
                <span class="stat-etiqueta"><?php esc_html_e('Publicaciones', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></span>
            </div>
            <div class="stat-item">
                <span class="stat-numero"><?php echo esc_html($total_usuarios); ?></span>
                <span class="stat-etiqueta"><?php esc_html_e('Usuarios', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></span>
            </div>
            <div class="stat-item">
                <span class="stat-numero"><?php echo esc_html($publicaciones_semana); ?></span>
                <span class="stat-etiqueta"><?php esc_html_e('Esta semana', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></span>
            </div>
        </div>
        <p>
            <a href="<?php echo esc_url(admin_url('admin.php?page=flavor-red-social-dashboard')); ?>" class="button">
                <?php esc_html_e('Ver Dashboard', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
            </a>
        </p>
        <?php
    }

    /**
     * Obtener estadísticas para el panel unificado
     *
     * @return array
     */
    public function get_estadisticas_admin() {
        global $wpdb;
        $tabla_publicaciones = $wpdb->prefix . 'flavor_social_publicaciones';
        $tabla_perfiles = $wpdb->prefix . 'flavor_social_perfiles';

        $estadisticas = [
            'total_publicaciones' => 0,
            'total_usuarios' => 0,
            'publicaciones_hoy' => 0,
            'publicaciones_semana' => 0,
            'nuevos_usuarios_semana' => 0,
        ];

        if (Flavor_Chat_Helpers::tabla_existe($tabla_publicaciones)) {
            $estadisticas['total_publicaciones'] = (int) $wpdb->get_var("SELECT COUNT(*) FROM $tabla_publicaciones");
            $estadisticas['publicaciones_hoy'] = (int) $wpdb->get_var(
                $wpdb->prepare(
                    "SELECT COUNT(*) FROM $tabla_publicaciones WHERE DATE(fecha_publicacion) = %s",
                    current_time('Y-m-d')
                )
            );
            $estadisticas['publicaciones_semana'] = (int) $wpdb->get_var(
                "SELECT COUNT(*) FROM $tabla_publicaciones WHERE fecha_publicacion >= DATE_SUB(NOW(), INTERVAL 7 DAY)"
            );
        }

        if (Flavor_Chat_Helpers::tabla_existe($tabla_perfiles)) {
            $estadisticas['total_usuarios'] = (int) $wpdb->get_var("SELECT COUNT(*) FROM $tabla_perfiles");
            $estadisticas['nuevos_usuarios_semana'] = (int) $wpdb->get_var(
                "SELECT COUNT(*) FROM $tabla_perfiles WHERE fecha_registro >= DATE_SUB(NOW(), INTERVAL 7 DAY)"
            );
        }

        return $estadisticas;
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
            Flavor_Page_Creator::refresh_module_pages('red_social');
            return;
        }

        // En frontend: crear páginas si no existen
        $pagina = get_page_by_path('red-social');
        if (!$pagina && !get_option('flavor_red_social_pages_created')) {
            Flavor_Page_Creator::create_pages_for_modules(['red_social']);
            update_option('flavor_red_social_pages_created', 1, false);
        }
    }

    /**
     * Obtiene estadísticas para el dashboard del cliente
     *
     * @return array Estadísticas del módulo
     */
    public function get_estadisticas_dashboard() {
        global $wpdb;
        $estadisticas = [];

        $tabla_publicaciones = $wpdb->prefix . 'flavor_social_publicaciones';
        $tabla_seguimientos = $wpdb->prefix . 'flavor_social_seguimientos';

        $usuario_id = get_current_user_id();
        if (!$usuario_id) {
            return $estadisticas;
        }

        if (Flavor_Chat_Helpers::tabla_existe($tabla_publicaciones)) {
            // Mis publicaciones
            $mis_publicaciones = (int) $wpdb->get_var($wpdb->prepare(
                "SELECT COUNT(*) FROM {$tabla_publicaciones}
                 WHERE autor_id = %d AND estado = 'publicado'",
                $usuario_id
            ));

            $estadisticas['mis_publicaciones'] = [
                'icon' => 'dashicons-format-status',
                'valor' => $mis_publicaciones,
                'label' => __('Publicaciones', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'color' => 'blue',
            ];
        }

        if (Flavor_Chat_Helpers::tabla_existe($tabla_seguimientos)) {
            // Seguidores
            $seguidores = (int) $wpdb->get_var($wpdb->prepare(
                "SELECT COUNT(*) FROM {$tabla_seguimientos}
                 WHERE seguido_id = %d AND estado = 'activo'",
                $usuario_id
            ));

            $estadisticas['seguidores'] = [
                'icon' => 'dashicons-groups',
                'valor' => $seguidores,
                'label' => __('Seguidores', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'color' => $seguidores > 0 ? 'green' : 'gray',
            ];

            // Siguiendo
            $siguiendo = (int) $wpdb->get_var($wpdb->prepare(
                "SELECT COUNT(*) FROM {$tabla_seguimientos}
                 WHERE seguidor_id = %d AND estado = 'activo'",
                $usuario_id
            ));

            $estadisticas['siguiendo'] = [
                'icon' => 'dashicons-admin-users',
                'valor' => $siguiendo,
                'label' => __('Siguiendo', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'color' => 'purple',
            ];
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
                'title' => __('Red Social', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'slug' => 'red-social',
                'content' => '<h1>' . __('Red Social', FLAVOR_PLATFORM_TEXT_DOMAIN) . '</h1>
<p>' . __('Conecta con tu comunidad, comparte publicaciones y sigue a otros usuarios.', FLAVOR_PLATFORM_TEXT_DOMAIN) . '</p>

[flavor_module_listing module="red_social" action="listar" columnas="3" limite="12"]',
                'parent' => 0,
            ],
            [
                'title' => __('Mi Perfil', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'slug' => 'mi-perfil',
                'content' => '<h1>' . __('Mi Perfil', FLAVOR_PLATFORM_TEXT_DOMAIN) . '</h1>
<p>' . __('Gestiona tu perfil, tus publicaciones y tu información personal.', FLAVOR_PLATFORM_TEXT_DOMAIN) . '</p>

[flavor_module_listing module="red_social" action="mi_perfil"]',
                'parent' => 'red-social',
            ],
            [
                'title' => __('Amigos', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'slug' => 'amigos',
                'content' => '<h1>' . __('Amigos', FLAVOR_PLATFORM_TEXT_DOMAIN) . '</h1>
<p>' . __('Descubre personas, gestiona tus seguidores y a quiénes sigues.', FLAVOR_PLATFORM_TEXT_DOMAIN) . '</p>

[flavor_module_listing module="red_social" action="amigos" columnas="4" limite="20"]',
                'parent' => 'red-social',
            ],
            [
                'title' => __('Mensajes', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'slug' => 'mensajes',
                'content' => '<h1>' . __('Mensajes', FLAVOR_PLATFORM_TEXT_DOMAIN) . '</h1>
<p>' . __('Envía y recibe mensajes privados con otros usuarios de la comunidad.', FLAVOR_PLATFORM_TEXT_DOMAIN) . '</p>

[flavor_module_listing module="red_social" action="mensajes"]',
                'parent' => 'red-social',
            ],
        ];
    }

    // =========================================================================
    // SISTEMA DE REPUTACIÓN / KARMA
    // =========================================================================

    /**
     * Añade puntos de reputación a un usuario
     *
     * @param int    $usuario_id  ID del usuario
     * @param string $tipo_accion Tipo de acción (ver PUNTOS_ACCION)
     * @param int    $referencia_id ID de referencia opcional
     * @param string $referencia_tipo Tipo de referencia opcional
     * @return int|false Nuevos puntos totales o false en error
     */
    public function agregar_puntos_reputacion($usuario_id, $tipo_accion, $referencia_id = null, $referencia_tipo = null) {
        if (!isset(self::PUNTOS_ACCION[$tipo_accion])) {
            return false;
        }

        $puntos = self::PUNTOS_ACCION[$tipo_accion];

        global $wpdb;
        $tabla_reputacion = $wpdb->prefix . 'flavor_social_reputacion';
        $tabla_historial = $wpdb->prefix . 'flavor_social_historial_puntos';

        // Asegurar que existe el registro de reputación
        $this->asegurar_registro_reputacion($usuario_id);

        // Registrar en historial
        $wpdb->insert(
            $tabla_historial,
            [
                'usuario_id' => $usuario_id,
                'puntos' => $puntos,
                'tipo_accion' => $tipo_accion,
                'descripcion' => $this->get_descripcion_accion($tipo_accion),
                'referencia_id' => $referencia_id,
                'referencia_tipo' => $referencia_tipo,
                'fecha_creacion' => current_time('mysql'),
            ],
            ['%d', '%d', '%s', '%s', '%d', '%s', '%s']
        );

        // Actualizar puntos totales
        $wpdb->query($wpdb->prepare(
            "UPDATE $tabla_reputacion
             SET puntos_totales = puntos_totales + %d,
                 puntos_semana = puntos_semana + %d,
                 puntos_mes = puntos_mes + %d,
                 ultima_actividad = NOW(),
                 racha_dias = CASE
                     WHEN DATE(ultima_actividad) = CURDATE() - INTERVAL 1 DAY THEN racha_dias + 1
                     WHEN DATE(ultima_actividad) = CURDATE() THEN racha_dias
                     ELSE 1
                 END
             WHERE usuario_id = %d",
            $puntos,
            $puntos,
            $puntos,
            $usuario_id
        ));

        // Obtener nuevos puntos y actualizar nivel si es necesario
        $datos_reputacion = $wpdb->get_row($wpdb->prepare(
            "SELECT puntos_totales, nivel FROM $tabla_reputacion WHERE usuario_id = %d",
            $usuario_id
        ));

        if ($datos_reputacion) {
            $nuevo_nivel = $this->calcular_nivel($datos_reputacion->puntos_totales);
            if ($nuevo_nivel !== $datos_reputacion->nivel) {
                $wpdb->update(
                    $tabla_reputacion,
                    ['nivel' => $nuevo_nivel],
                    ['usuario_id' => $usuario_id],
                    ['%s'],
                    ['%d']
                );

                // Notificar subida de nivel
                $this->notificar_subida_nivel($usuario_id, $nuevo_nivel);
            }

            // Verificar si obtiene algún badge
            $this->verificar_badges_usuario($usuario_id);

            return $datos_reputacion->puntos_totales + $puntos;
        }

        return false;
    }

    /**
     * Asegura que existe el registro de reputación del usuario
     */
    private function asegurar_registro_reputacion($usuario_id) {
        global $wpdb;
        $tabla_reputacion = $wpdb->prefix . 'flavor_social_reputacion';

        $existe = $wpdb->get_var($wpdb->prepare(
            "SELECT id FROM $tabla_reputacion WHERE usuario_id = %d",
            $usuario_id
        ));

        if (!$existe) {
            $wpdb->insert(
                $tabla_reputacion,
                [
                    'usuario_id' => $usuario_id,
                    'puntos_totales' => 0,
                    'nivel' => 'nuevo',
                    'puntos_semana' => 0,
                    'puntos_mes' => 0,
                    'racha_dias' => 0,
                    'ultima_actividad' => current_time('mysql'),
                ],
                ['%d', '%d', '%s', '%d', '%d', '%d', '%s']
            );
        }
    }

    /**
     * Calcula el nivel basado en puntos totales
     */
    private function calcular_nivel($puntos_totales) {
        $nivel_actual = 'nuevo';

        foreach (self::NIVELES_REPUTACION as $nivel => $config) {
            if ($puntos_totales >= $config['min']) {
                $nivel_actual = $nivel;
            }
        }

        return $nivel_actual;
    }

    /**
     * Obtiene la descripción de una acción
     */
    private function get_descripcion_accion($tipo_accion) {
        $descripciones = [
            'publicacion' => __('Publicación creada', FLAVOR_PLATFORM_TEXT_DOMAIN),
            'comentario' => __('Comentario realizado', FLAVOR_PLATFORM_TEXT_DOMAIN),
            'like_recibido' => __('Like recibido', FLAVOR_PLATFORM_TEXT_DOMAIN),
            'like_dado' => __('Like dado', FLAVOR_PLATFORM_TEXT_DOMAIN),
            'compartido' => __('Contenido compartido', FLAVOR_PLATFORM_TEXT_DOMAIN),
            'seguidor_ganado' => __('Nuevo seguidor', FLAVOR_PLATFORM_TEXT_DOMAIN),
            'seguir_usuario' => __('Usuario seguido', FLAVOR_PLATFORM_TEXT_DOMAIN),
            'historia' => __('Historia publicada', FLAVOR_PLATFORM_TEXT_DOMAIN),
            'mencion_recibida' => __('Mencionado', FLAVOR_PLATFORM_TEXT_DOMAIN),
            'login_diario' => __('Actividad diaria', FLAVOR_PLATFORM_TEXT_DOMAIN),
            'primera_publicacion' => __('Primera publicación', FLAVOR_PLATFORM_TEXT_DOMAIN),
            'verificacion_perfil' => __('Perfil verificado', FLAVOR_PLATFORM_TEXT_DOMAIN),
            'invitar_usuario' => __('Usuario invitado', FLAVOR_PLATFORM_TEXT_DOMAIN),
            'badge_obtenido' => __('Badge obtenido', FLAVOR_PLATFORM_TEXT_DOMAIN),
        ];

        return $descripciones[$tipo_accion] ?? $tipo_accion;
    }

    /**
     * Notifica al usuario que subió de nivel
     */
    private function notificar_subida_nivel($usuario_id, $nuevo_nivel) {
        $info_nivel = self::NIVELES_REPUTACION[$nuevo_nivel] ?? null;
        if (!$info_nivel) {
            return;
        }

        // Crear notificación interna
        $this->crear_notificacion(
            $usuario_id,
            0, // Sistema
            'nivel_subido',
            0,
            'reputacion'
        );

        // Hook para otras integraciones
        do_action('flavor_social_nivel_subido', $usuario_id, $nuevo_nivel, $info_nivel);
    }

    /**
     * Obtiene la información de reputación de un usuario
     */
    public function obtener_reputacion_usuario($usuario_id) {
        global $wpdb;
        $tabla_reputacion = $wpdb->prefix . 'flavor_social_reputacion';

        $this->asegurar_registro_reputacion($usuario_id);

        $datos = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM $tabla_reputacion WHERE usuario_id = %d",
            $usuario_id
        ));

        if (!$datos) {
            return null;
        }

        $nivel_info = self::NIVELES_REPUTACION[$datos->nivel] ?? self::NIVELES_REPUTACION['nuevo'];
        $siguiente_nivel = $this->obtener_siguiente_nivel($datos->nivel);

        return [
            'puntos_totales' => (int) $datos->puntos_totales,
            'nivel' => $datos->nivel,
            'nivel_label' => $nivel_info['label'],
            'nivel_icono' => $nivel_info['icono'],
            'nivel_color' => $nivel_info['color'],
            'puntos_semana' => (int) $datos->puntos_semana,
            'puntos_mes' => (int) $datos->puntos_mes,
            'racha_dias' => (int) $datos->racha_dias,
            'siguiente_nivel' => $siguiente_nivel ? $siguiente_nivel['nombre'] : null,
            'puntos_para_siguiente' => $siguiente_nivel ? $siguiente_nivel['min'] - $datos->puntos_totales : 0,
            'progreso_nivel' => $siguiente_nivel ? $this->calcular_progreso_nivel($datos->puntos_totales, $datos->nivel) : 100,
            'badges' => $this->obtener_badges_usuario($usuario_id),
        ];
    }

    /**
     * Obtiene el siguiente nivel
     */
    private function obtener_siguiente_nivel($nivel_actual) {
        $niveles = array_keys(self::NIVELES_REPUTACION);
        $indice_actual = array_search($nivel_actual, $niveles);

        if ($indice_actual === false || $indice_actual >= count($niveles) - 1) {
            return null;
        }

        $siguiente = $niveles[$indice_actual + 1];
        return [
            'nombre' => $siguiente,
            'min' => self::NIVELES_REPUTACION[$siguiente]['min'],
            'label' => self::NIVELES_REPUTACION[$siguiente]['label'],
        ];
    }

    /**
     * Calcula el progreso hacia el siguiente nivel (0-100%)
     */
    private function calcular_progreso_nivel($puntos_totales, $nivel_actual) {
        $nivel_info = self::NIVELES_REPUTACION[$nivel_actual] ?? null;
        $siguiente = $this->obtener_siguiente_nivel($nivel_actual);

        if (!$nivel_info || !$siguiente) {
            return 100;
        }

        $puntos_nivel_actual = $nivel_info['min'];
        $puntos_siguiente = $siguiente['min'];
        $rango = $puntos_siguiente - $puntos_nivel_actual;

        if ($rango <= 0) {
            return 100;
        }

        $progreso = $puntos_totales - $puntos_nivel_actual;
        return min(100, max(0, ($progreso / $rango) * 100));
    }

    /**
     * Obtiene los badges de un usuario
     */
    public function obtener_badges_usuario($usuario_id) {
        global $wpdb;
        $tabla_badges = $wpdb->prefix . 'flavor_social_badges';
        $tabla_usuario_badges = $wpdb->prefix . 'flavor_social_usuario_badges';

        return $wpdb->get_results($wpdb->prepare(
            "SELECT b.*, ub.fecha_obtenido, ub.destacado
             FROM $tabla_badges b
             INNER JOIN $tabla_usuario_badges ub ON b.id = ub.badge_id
             WHERE ub.usuario_id = %d AND b.activo = 1
             ORDER BY ub.destacado DESC, ub.fecha_obtenido DESC",
            $usuario_id
        ));
    }

    /**
     * Verifica y otorga badges al usuario si cumple condiciones
     */
    public function verificar_badges_usuario($usuario_id) {
        global $wpdb;
        $tabla_badges = $wpdb->prefix . 'flavor_social_badges';
        $tabla_usuario_badges = $wpdb->prefix . 'flavor_social_usuario_badges';
        $tabla_reputacion = $wpdb->prefix . 'flavor_social_reputacion';

        // Obtener datos del usuario
        $reputacion = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM $tabla_reputacion WHERE usuario_id = %d",
            $usuario_id
        ));

        if (!$reputacion) {
            return;
        }

        // Obtener badges que el usuario no tiene
        $badges_disponibles = $wpdb->get_results($wpdb->prepare(
            "SELECT b.* FROM $tabla_badges b
             WHERE b.activo = 1
             AND b.id NOT IN (SELECT badge_id FROM $tabla_usuario_badges WHERE usuario_id = %d)",
            $usuario_id
        ));

        foreach ($badges_disponibles as $badge) {
            $otorgar = false;

            // Verificar por puntos requeridos
            if ($badge->puntos_requeridos > 0 && $reputacion->puntos_totales >= $badge->puntos_requeridos) {
                $otorgar = true;
            }

            // Verificar condiciones especiales
            if (!$otorgar && !empty($badge->condicion_especial)) {
                $condicion = json_decode($badge->condicion_especial, true);
                $otorgar = $this->verificar_condicion_badge($usuario_id, $condicion);
            }

            if ($otorgar) {
                $this->otorgar_badge($usuario_id, $badge->id);
            }
        }
    }

    /**
     * Verifica una condición especial de badge
     */
    private function verificar_condicion_badge($usuario_id, $condicion) {
        if (!is_array($condicion) || empty($condicion['tipo'])) {
            return false;
        }

        global $wpdb;

        switch ($condicion['tipo']) {
            case 'publicaciones_count':
                $count = $wpdb->get_var($wpdb->prepare(
                    "SELECT COUNT(*) FROM {$wpdb->prefix}flavor_social_publicaciones
                     WHERE autor_id = %d AND estado = 'publicado'",
                    $usuario_id
                ));
                return $count >= ($condicion['valor'] ?? 0);

            case 'seguidores_count':
                $count = $wpdb->get_var($wpdb->prepare(
                    "SELECT COUNT(*) FROM {$wpdb->prefix}flavor_social_seguimientos
                     WHERE seguido_id = %d",
                    $usuario_id
                ));
                return $count >= ($condicion['valor'] ?? 0);

            case 'racha_dias':
                $reputacion = $wpdb->get_var($wpdb->prepare(
                    "SELECT racha_dias FROM {$wpdb->prefix}flavor_social_reputacion
                     WHERE usuario_id = %d",
                    $usuario_id
                ));
                return $reputacion >= ($condicion['valor'] ?? 0);

            case 'comentarios_count':
                $count = $wpdb->get_var($wpdb->prepare(
                    "SELECT COUNT(*) FROM {$wpdb->prefix}flavor_social_comentarios
                     WHERE autor_id = %d AND estado = 'publicado'",
                    $usuario_id
                ));
                return $count >= ($condicion['valor'] ?? 0);

            default:
                return false;
        }
    }

    /**
     * Otorga un badge a un usuario
     */
    public function otorgar_badge($usuario_id, $badge_id) {
        global $wpdb;
        $tabla_usuario_badges = $wpdb->prefix . 'flavor_social_usuario_badges';

        // Verificar si ya lo tiene
        $existe = $wpdb->get_var($wpdb->prepare(
            "SELECT id FROM $tabla_usuario_badges WHERE usuario_id = %d AND badge_id = %d",
            $usuario_id,
            $badge_id
        ));

        if ($existe) {
            return false;
        }

        // Otorgar badge
        $resultado = $wpdb->insert(
            $tabla_usuario_badges,
            [
                'usuario_id' => $usuario_id,
                'badge_id' => $badge_id,
                'fecha_obtenido' => current_time('mysql'),
                'destacado' => 0,
            ],
            ['%d', '%d', '%s', '%d']
        );

        if ($resultado) {
            // Añadir puntos por badge
            $this->agregar_puntos_reputacion($usuario_id, 'badge_obtenido', $badge_id, 'badge');

            // Hook para notificaciones
            do_action('flavor_social_badge_otorgado', $usuario_id, $badge_id);

            return true;
        }

        return false;
    }

    /**
     * Obtiene el ranking de usuarios por reputación
     */
    public function obtener_ranking_reputacion($limite = 10, $periodo = 'total') {
        global $wpdb;
        $tabla_reputacion = $wpdb->prefix . 'flavor_social_reputacion';

        $campo_ordenar = 'puntos_totales';
        if ($periodo === 'semana') {
            $campo_ordenar = 'puntos_semana';
        } elseif ($periodo === 'mes') {
            $campo_ordenar = 'puntos_mes';
        }

        $usuarios = $wpdb->get_results($wpdb->prepare(
            "SELECT r.*, u.display_name, u.user_login
             FROM $tabla_reputacion r
             INNER JOIN {$wpdb->users} u ON r.usuario_id = u.ID
             ORDER BY r.{$campo_ordenar} DESC
             LIMIT %d",
            $limite
        ));

        $ranking = [];
        $posicion = 1;

        foreach ($usuarios as $usuario) {
            $nivel_info = self::NIVELES_REPUTACION[$usuario->nivel] ?? self::NIVELES_REPUTACION['nuevo'];

            $ranking[] = [
                'posicion' => $posicion++,
                'usuario_id' => $usuario->usuario_id,
                'nombre' => $usuario->display_name,
                'username' => $usuario->user_login,
                'avatar' => get_avatar_url($usuario->usuario_id, ['size' => 50]),
                'puntos' => (int) $usuario->$campo_ordenar,
                'puntos_totales' => (int) $usuario->puntos_totales,
                'nivel' => $usuario->nivel,
                'nivel_label' => $nivel_info['label'],
                'nivel_icono' => $nivel_info['icono'],
                'nivel_color' => $nivel_info['color'],
                'racha_dias' => (int) $usuario->racha_dias,
            ];
        }

        return $ranking;
    }

    /**
     * Instala los badges predeterminados
     */
    public function instalar_badges_predeterminados() {
        global $wpdb;
        $tabla_badges = $wpdb->prefix . 'flavor_social_badges';

        $badges_predeterminados = [
            [
                'nombre' => 'Primeros Pasos',
                'slug' => 'primeros-pasos',
                'descripcion' => 'Completaste tu perfil y publicaste tu primer contenido',
                'icono' => '🎯',
                'color' => '#3b82f6',
                'categoria' => 'participacion',
                'puntos_requeridos' => 25,
                'orden' => 1,
            ],
            [
                'nombre' => 'Comunicador',
                'slug' => 'comunicador',
                'descripcion' => 'Has comentado en más de 50 publicaciones',
                'icono' => '💬',
                'color' => '#8b5cf6',
                'categoria' => 'participacion',
                'condicion_especial' => json_encode(['tipo' => 'comentarios_count', 'valor' => 50]),
                'orden' => 2,
            ],
            [
                'nombre' => 'Creador Activo',
                'slug' => 'creador-activo',
                'descripcion' => 'Has creado más de 20 publicaciones',
                'icono' => '✍️',
                'color' => '#f59e0b',
                'categoria' => 'creacion',
                'condicion_especial' => json_encode(['tipo' => 'publicaciones_count', 'valor' => 20]),
                'orden' => 3,
            ],
            [
                'nombre' => 'Influencer',
                'slug' => 'influencer',
                'descripcion' => 'Has conseguido más de 100 seguidores',
                'icono' => '⭐',
                'color' => '#ec4899',
                'categoria' => 'comunidad',
                'condicion_especial' => json_encode(['tipo' => 'seguidores_count', 'valor' => 100]),
                'orden' => 4,
            ],
            [
                'nombre' => 'Constante',
                'slug' => 'constante',
                'descripcion' => 'Has mantenido una racha de 7 días de actividad',
                'icono' => '🔥',
                'color' => '#ef4444',
                'categoria' => 'participacion',
                'condicion_especial' => json_encode(['tipo' => 'racha_dias', 'valor' => 7]),
                'orden' => 5,
            ],
            [
                'nombre' => 'Maratonista',
                'slug' => 'maratonista',
                'descripcion' => 'Has mantenido una racha de 30 días de actividad',
                'icono' => '🏃',
                'color' => '#10b981',
                'categoria' => 'participacion',
                'condicion_especial' => json_encode(['tipo' => 'racha_dias', 'valor' => 30]),
                'orden' => 6,
            ],
            [
                'nombre' => 'Centenario',
                'slug' => 'centenario',
                'descripcion' => 'Has alcanzado 100 puntos de reputación',
                'icono' => '💯',
                'color' => '#6366f1',
                'categoria' => 'participacion',
                'puntos_requeridos' => 100,
                'orden' => 7,
            ],
            [
                'nombre' => 'Veterano',
                'slug' => 'veterano',
                'descripcion' => 'Has alcanzado 1000 puntos de reputación',
                'icono' => '🏆',
                'color' => '#f97316',
                'categoria' => 'especial',
                'puntos_requeridos' => 1000,
                'orden' => 8,
            ],
        ];

        foreach ($badges_predeterminados as $badge) {
            $existe = $wpdb->get_var($wpdb->prepare(
                "SELECT id FROM $tabla_badges WHERE slug = %s",
                $badge['slug']
            ));

            if (!$existe) {
                $wpdb->insert(
                    $tabla_badges,
                    array_merge($badge, [
                        'es_unico' => 1,
                        'activo' => 1,
                        'fecha_creacion' => current_time('mysql'),
                    ])
                );
            }
        }
    }

    // =========================================================================
    // SHORTCODES DE REPUTACIÓN
    // =========================================================================

    /**
     * Shortcode: Mostrar tarjeta de reputación del usuario
     * [rs_reputacion usuario_id="123"]
     */
    public function shortcode_reputacion($atts) {
        $atts = shortcode_atts([
            'usuario_id' => get_current_user_id(),
            'mostrar_badges' => 'true',
            'mostrar_progreso' => 'true',
        ], $atts, 'rs_reputacion');

        $usuario_id = absint($atts['usuario_id']);
        if (!$usuario_id) {
            return '<p class="rs-mensaje">' . __('Usuario no encontrado', FLAVOR_PLATFORM_TEXT_DOMAIN) . '</p>';
        }

        $reputacion = $this->obtener_reputacion_usuario($usuario_id);
        if (!$reputacion) {
            return '';
        }

        $usuario = get_userdata($usuario_id);
        $mostrar_badges = $atts['mostrar_badges'] === 'true';
        $mostrar_progreso = $atts['mostrar_progreso'] === 'true';

        ob_start();
        ?>
        <div class="rs-reputacion-card">
            <div class="rs-reputacion-header">
                <div class="rs-reputacion-avatar">
                    <img src="<?php echo esc_url(get_avatar_url($usuario_id, ['size' => 80])); ?>" alt="">
                    <span class="rs-nivel-badge" style="background: <?php echo esc_attr($reputacion['nivel_color']); ?>">
                        <?php echo esc_html($reputacion['nivel_icono']); ?>
                    </span>
                </div>
                <div class="rs-reputacion-info">
                    <h3><?php echo esc_html($usuario->display_name); ?></h3>
                    <span class="rs-nivel-label" style="color: <?php echo esc_attr($reputacion['nivel_color']); ?>">
                        <?php echo esc_html($reputacion['nivel_label']); ?>
                    </span>
                    <div class="rs-puntos-total">
                        <strong><?php echo number_format($reputacion['puntos_totales']); ?></strong>
                        <?php esc_html_e('puntos', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                    </div>
                </div>
            </div>

            <?php if ($mostrar_progreso && $reputacion['siguiente_nivel']): ?>
            <div class="rs-reputacion-progreso">
                <div class="rs-progreso-bar">
                    <div class="rs-progreso-fill" style="width: <?php echo esc_attr($reputacion['progreso_nivel']); ?>%"></div>
                </div>
                <div class="rs-progreso-texto">
                    <?php printf(
                        esc_html__('%d puntos para %s', FLAVOR_PLATFORM_TEXT_DOMAIN),
                        $reputacion['puntos_para_siguiente'],
                        $reputacion['siguiente_nivel']
                    ); ?>
                </div>
            </div>
            <?php endif; ?>

            <div class="rs-reputacion-stats">
                <div class="rs-stat">
                    <span class="rs-stat-valor"><?php echo number_format($reputacion['puntos_semana']); ?></span>
                    <span class="rs-stat-label"><?php esc_html_e('Esta semana', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></span>
                </div>
                <div class="rs-stat">
                    <span class="rs-stat-valor"><?php echo number_format($reputacion['puntos_mes']); ?></span>
                    <span class="rs-stat-label"><?php esc_html_e('Este mes', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></span>
                </div>
                <div class="rs-stat">
                    <span class="rs-stat-valor"><?php echo $reputacion['racha_dias']; ?> 🔥</span>
                    <span class="rs-stat-label"><?php esc_html_e('Racha', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></span>
                </div>
            </div>

            <?php if ($mostrar_badges && !empty($reputacion['badges'])): ?>
            <div class="rs-badges-lista">
                <h4><?php esc_html_e('Badges', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></h4>
                <div class="rs-badges-grid">
                    <?php foreach (array_slice($reputacion['badges'], 0, 6) as $badge): ?>
                    <div class="rs-badge-item" title="<?php echo esc_attr($badge->descripcion); ?>">
                        <span class="rs-badge-icono" style="background: <?php echo esc_attr($badge->color); ?>">
                            <?php echo esc_html($badge->icono); ?>
                        </span>
                        <span class="rs-badge-nombre"><?php echo esc_html($badge->nombre); ?></span>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
            <?php endif; ?>
        </div>
        <?php
        return ob_get_clean();
    }

    /**
     * Shortcode: Ranking de reputación
     * [rs_ranking limite="10" periodo="total"]
     */
    public function shortcode_ranking($atts) {
        $atts = shortcode_atts([
            'limite' => 10,
            'periodo' => 'total', // total, semana, mes
        ], $atts, 'rs_ranking');

        $ranking = $this->obtener_ranking_reputacion(absint($atts['limite']), $atts['periodo']);

        if (empty($ranking)) {
            return '<p class="rs-mensaje">' . __('No hay datos de ranking', FLAVOR_PLATFORM_TEXT_DOMAIN) . '</p>';
        }

        $periodo_labels = [
            'total' => __('Total', FLAVOR_PLATFORM_TEXT_DOMAIN),
            'semana' => __('Esta semana', FLAVOR_PLATFORM_TEXT_DOMAIN),
            'mes' => __('Este mes', FLAVOR_PLATFORM_TEXT_DOMAIN),
        ];

        ob_start();
        ?>
        <div class="rs-ranking">
            <h3 class="rs-ranking-titulo">
                🏆 <?php esc_html_e('Ranking de reputación', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                <span class="rs-ranking-periodo"><?php echo esc_html($periodo_labels[$atts['periodo']] ?? ''); ?></span>
            </h3>
            <div class="rs-ranking-lista">
                <?php foreach ($ranking as $usuario): ?>
                <div class="rs-ranking-item <?php echo $usuario['posicion'] <= 3 ? 'rs-ranking-top' : ''; ?>">
                    <span class="rs-ranking-posicion">
                        <?php
                        if ($usuario['posicion'] === 1) echo '🥇';
                        elseif ($usuario['posicion'] === 2) echo '🥈';
                        elseif ($usuario['posicion'] === 3) echo '🥉';
                        else echo '#' . $usuario['posicion'];
                        ?>
                    </span>
                    <img class="rs-ranking-avatar" src="<?php echo esc_url($usuario['avatar']); ?>" alt="">
                    <div class="rs-ranking-info">
                        <span class="rs-ranking-nombre"><?php echo esc_html($usuario['nombre']); ?></span>
                        <span class="rs-ranking-nivel" style="color: <?php echo esc_attr($usuario['nivel_color']); ?>">
                            <?php echo esc_html($usuario['nivel_icono'] . ' ' . $usuario['nivel_label']); ?>
                        </span>
                    </div>
                    <div class="rs-ranking-puntos">
                        <strong><?php echo number_format($usuario['puntos']); ?></strong>
                        <span><?php esc_html_e('pts', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></span>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
        <?php
        return ob_get_clean();
    }

    /**
     * Shortcode: Mostrar badges disponibles
     * [rs_badges categoria="todos"]
     */
    public function shortcode_badges($atts) {
        $atts = shortcode_atts([
            'categoria' => 'todos',
        ], $atts, 'rs_badges');

        global $wpdb;
        $tabla_badges = $wpdb->prefix . 'flavor_social_badges';

        $where = "WHERE activo = 1";
        if ($atts['categoria'] !== 'todos') {
            $where .= $wpdb->prepare(" AND categoria = %s", $atts['categoria']);
        }

        $badges = $wpdb->get_results("SELECT * FROM $tabla_badges {$where} ORDER BY orden ASC");

        if (empty($badges)) {
            return '<p class="rs-mensaje">' . __('No hay badges disponibles', FLAVOR_PLATFORM_TEXT_DOMAIN) . '</p>';
        }

        $usuario_id = get_current_user_id();
        $badges_usuario = [];
        if ($usuario_id) {
            $badges_obtenidos = $this->obtener_badges_usuario($usuario_id);
            $badges_usuario = array_column($badges_obtenidos, 'id');
        }

        ob_start();
        ?>
        <div class="rs-badges-catalogo">
            <h3><?php esc_html_e('Badges disponibles', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></h3>
            <div class="rs-badges-grid-large">
                <?php foreach ($badges as $badge): ?>
                <?php $obtenido = in_array($badge->id, $badges_usuario); ?>
                <div class="rs-badge-card <?php echo $obtenido ? 'rs-badge-obtenido' : 'rs-badge-bloqueado'; ?>">
                    <div class="rs-badge-icono-large" style="background: <?php echo esc_attr($badge->color); ?>">
                        <?php echo esc_html($badge->icono); ?>
                    </div>
                    <h4><?php echo esc_html($badge->nombre); ?></h4>
                    <p><?php echo esc_html($badge->descripcion); ?></p>
                    <?php if ($obtenido): ?>
                    <span class="rs-badge-estado">✓ <?php esc_html_e('Obtenido', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></span>
                    <?php elseif ($badge->puntos_requeridos > 0): ?>
                    <span class="rs-badge-requisito"><?php printf(esc_html__('%d puntos requeridos', FLAVOR_PLATFORM_TEXT_DOMAIN), $badge->puntos_requeridos); ?></span>
                    <?php endif; ?>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
        <?php
        return ob_get_clean();
    }

    /**
     * Shortcode: Mi actividad social
     * Muestra resumen de actividad reciente del usuario en la red social
     */
    public function shortcode_mi_actividad($atts) {
        $atts = shortcode_atts([
            'limite' => 5,
        ], $atts);

        if (!is_user_logged_in()) {
            return '<p class="rs-login-required">' . __('Inicia sesión para ver tu actividad.', FLAVOR_PLATFORM_TEXT_DOMAIN) . '</p>';
        }

        $usuario_id = get_current_user_id();
        $limite = absint($atts['limite']);

        global $wpdb;
        $tabla_publicaciones = $wpdb->prefix . 'flavor_rs_publicaciones';
        $tabla_seguidores = $wpdb->prefix . 'flavor_rs_seguidores';
        $tabla_reacciones = $wpdb->prefix . 'flavor_rs_reacciones';

        // Estadísticas del usuario
        $num_publicaciones = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM $tabla_publicaciones WHERE autor_id = %d",
            $usuario_id
        ));

        $num_seguidores = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM $tabla_seguidores WHERE seguido_id = %d",
            $usuario_id
        ));

        $num_siguiendo = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM $tabla_seguidores WHERE seguidor_id = %d",
            $usuario_id
        ));

        // Últimas publicaciones
        $publicaciones_recientes = $wpdb->get_results($wpdb->prepare(
            "SELECT id, contenido, fecha_creacion,
                    (SELECT COUNT(*) FROM $tabla_reacciones WHERE publicacion_id = p.id) as num_reacciones
             FROM $tabla_publicaciones p
             WHERE autor_id = %d
             ORDER BY fecha_creacion DESC
             LIMIT %d",
            $usuario_id,
            $limite
        ));

        ob_start();
        ?>
        <div class="rs-mi-actividad">
            <div class="rs-actividad-stats">
                <div class="stat-item">
                    <span class="stat-numero"><?php echo esc_html(number_format_i18n($num_publicaciones)); ?></span>
                    <span class="stat-label"><?php esc_html_e('Publicaciones', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></span>
                </div>
                <div class="stat-item">
                    <span class="stat-numero"><?php echo esc_html(number_format_i18n($num_seguidores)); ?></span>
                    <span class="stat-label"><?php esc_html_e('Seguidores', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></span>
                </div>
                <div class="stat-item">
                    <span class="stat-numero"><?php echo esc_html(number_format_i18n($num_siguiendo)); ?></span>
                    <span class="stat-label"><?php esc_html_e('Siguiendo', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></span>
                </div>
            </div>

            <?php if (!empty($publicaciones_recientes)): ?>
            <div class="rs-actividad-reciente">
                <h4><?php esc_html_e('Actividad reciente', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></h4>
                <ul class="rs-lista-publicaciones">
                    <?php foreach ($publicaciones_recientes as $pub): ?>
                    <li>
                        <span class="pub-contenido"><?php echo esc_html(wp_trim_words($pub->contenido, 10)); ?></span>
                        <span class="pub-meta">
                            <?php echo esc_html(human_time_diff(strtotime($pub->fecha_creacion))); ?>
                            <?php if ($pub->num_reacciones > 0): ?>
                                · <span class="dashicons dashicons-heart"></span> <?php echo esc_html($pub->num_reacciones); ?>
                            <?php endif; ?>
                        </span>
                    </li>
                    <?php endforeach; ?>
                </ul>
            </div>
            <?php else: ?>
            <p class="rs-sin-actividad"><?php esc_html_e('Aún no has publicado nada.', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></p>
            <?php endif; ?>

            <a href="<?php echo esc_url(home_url('/mi-portal/red-social/perfil/')); ?>" class="rs-ver-perfil">
                <?php esc_html_e('Ver mi perfil completo', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
            </a>
        </div>
        <?php
        return ob_get_clean();
    }

    /**
     * Shortcode de integración para tabs de otros módulos
     *
     * Muestra el feed social asociado a una entidad (grupo, comunidad, etc.)
     * Usado en el sistema de tabs universales.
     *
     * @param array $atts Atributos del shortcode
     * @return string HTML del feed social
     */
    public function shortcode_feed_integrado($atts) {
        $atributos = shortcode_atts([
            'entidad'           => '',
            'entidad_id'        => 0,
            'limite'            => 10,
            'permite_publicar'  => 'true',
            'mostrar_reacciones' => 'true',
            'mostrar_comentarios' => 'true',
        ], $atts);

        $entidad = sanitize_text_field($atributos['entidad']);
        $entidad_id = absint($atributos['entidad_id']);
        $limite = absint($atributos['limite']);
        $permite_publicar = filter_var($atributos['permite_publicar'], FILTER_VALIDATE_BOOLEAN);
        $mostrar_reacciones = filter_var($atributos['mostrar_reacciones'], FILTER_VALIDATE_BOOLEAN);
        $mostrar_comentarios = filter_var($atributos['mostrar_comentarios'], FILTER_VALIDATE_BOOLEAN);

        if (empty($entidad) || $entidad_id === 0) {
            return '<div class="flavor-notice warning">' . __('Configuración de feed social incompleta.', FLAVOR_PLATFORM_TEXT_DOMAIN) . '</div>';
        }

        // Obtener publicaciones asociadas a esta entidad
        $publicaciones = $this->obtener_publicaciones_entidad($entidad, $entidad_id, $limite);
        $puede_publicar = $permite_publicar && is_user_logged_in() && $this->usuario_puede_publicar_entidad($entidad, $entidad_id);
        $usuario_actual = get_current_user_id();

        ob_start();
        ?>
        <div class="flavor-social-feed-integrado" data-entidad="<?php echo esc_attr($entidad); ?>" data-entidad-id="<?php echo esc_attr($entidad_id); ?>">

            <?php if ($puede_publicar): ?>
            <!-- Formulario de nueva publicación -->
            <div class="feed-nueva-publicacion">
                <div class="publicacion-avatar">
                    <?php echo get_avatar($usuario_actual, 40); ?>
                </div>
                <form class="form-publicar" data-entidad="<?php echo esc_attr($entidad); ?>" data-entidad-id="<?php echo esc_attr($entidad_id); ?>">
                    <textarea name="contenido" placeholder="<?php esc_attr_e('¿Qué quieres compartir con el grupo?', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>" rows="2"></textarea>
                    <div class="publicacion-acciones">
                        <div class="publicacion-adjuntos">
                            <button type="button" class="btn-adjuntar-imagen" title="<?php esc_attr_e('Añadir imagen', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>">
                                <span class="dashicons dashicons-format-image"></span>
                            </button>
                            <input type="file" name="imagen" accept="image/*" style="display:none;">
                        </div>
                        <button type="submit" class="flavor-btn flavor-btn--primary flavor-btn--sm">
                            <?php _e('Publicar', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                        </button>
                    </div>
                    <div class="preview-imagen" style="display:none;">
                        <img src="" alt="">
                        <button type="button" class="btn-quitar-imagen">&times;</button>
                    </div>
                </form>
            </div>
            <?php endif; ?>

            <?php if (empty($publicaciones)): ?>
            <div class="feed-vacio">
                <span class="dashicons dashicons-format-status"></span>
                <p><?php _e('No hay publicaciones todavía.', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></p>
                <?php if ($puede_publicar): ?>
                <p class="texto-secundario"><?php _e('¡Sé el primero en compartir algo!', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></p>
                <?php endif; ?>
            </div>
            <?php else: ?>

            <div class="feed-publicaciones">
                <?php foreach ($publicaciones as $publicacion): ?>
                <?php
                $autor = get_userdata($publicacion->usuario_id);
                $es_autor = $usuario_actual === (int)$publicacion->usuario_id;
                $reacciones = $this->obtener_reacciones_publicacion($publicacion->id);
                $mi_reaccion = $usuario_actual ? $this->obtener_mi_reaccion($publicacion->id, $usuario_actual) : null;
                ?>
                <article class="feed-post" data-post-id="<?php echo esc_attr($publicacion->id); ?>">
                    <header class="post-header">
                        <a href="<?php echo esc_url(add_query_arg('usuario_id', intval($publicacion->usuario_id), home_url('/mi-portal/red-social/perfil/'))); ?>" class="post-avatar">
                            <?php echo get_avatar($publicacion->usuario_id, 44); ?>
                        </a>
                        <div class="post-meta">
                            <span class="post-autor"><?php echo esc_html($autor ? $autor->display_name : __('Usuario', FLAVOR_PLATFORM_TEXT_DOMAIN)); ?></span>
                            <span class="post-fecha"><?php echo human_time_diff(strtotime($publicacion->fecha_creacion), current_time('timestamp')); ?></span>
                        </div>
                        <?php if ($es_autor): ?>
                        <div class="post-opciones">
                            <button class="btn-opciones-post"><span class="dashicons dashicons-ellipsis"></span></button>
                            <div class="menu-opciones" style="display:none;">
                                <button class="btn-editar-post"><?php _e('Editar', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></button>
                                <button class="btn-eliminar-post"><?php _e('Eliminar', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></button>
                            </div>
                        </div>
                        <?php endif; ?>
                    </header>

                    <div class="post-contenido">
                        <?php echo wp_kses_post(nl2br($publicacion->contenido)); ?>
                    </div>

                    <?php if (!empty($publicacion->imagenes)): ?>
                    <?php $imagenes = maybe_unserialize($publicacion->imagenes); ?>
                    <?php if (is_array($imagenes) && count($imagenes) > 0): ?>
                    <div class="post-imagenes <?php echo count($imagenes) > 1 ? 'galeria-grid' : ''; ?>">
                        <?php foreach ($imagenes as $imagen): ?>
                        <img src="<?php echo esc_url($imagen); ?>" alt="" loading="lazy" class="post-imagen">
                        <?php endforeach; ?>
                    </div>
                    <?php endif; ?>
                    <?php endif; ?>

                    <?php if ($mostrar_reacciones): ?>
                    <div class="post-reacciones">
                        <div class="reacciones-resumen">
                            <?php if (!empty($reacciones)): ?>
                            <?php
                            $emojis_reacciones = ['like' => '👍', 'love' => '❤️', 'haha' => '😄', 'wow' => '😮', 'sad' => '😢', 'angry' => '😠'];
                            $total_reacciones = array_sum($reacciones);
                            ?>
                            <span class="reacciones-iconos">
                                <?php foreach (array_slice($reacciones, 0, 3) as $tipo => $cantidad): ?>
                                <span><?php echo $emojis_reacciones[$tipo] ?? '👍'; ?></span>
                                <?php endforeach; ?>
                            </span>
                            <span class="reacciones-count"><?php echo number_format_i18n($total_reacciones); ?></span>
                            <?php endif; ?>
                        </div>
                        <?php if ($mostrar_comentarios): ?>
                        <span class="comentarios-count">
                            <?php echo number_format_i18n($publicacion->num_comentarios ?? 0); ?> <?php _e('comentarios', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                        </span>
                        <?php endif; ?>
                    </div>

                    <div class="post-acciones-bar">
                        <button class="btn-reaccionar <?php echo $mi_reaccion ? 'activo' : ''; ?>" data-post-id="<?php echo esc_attr($publicacion->id); ?>">
                            <span class="dashicons dashicons-thumbs-up"></span>
                            <?php _e('Me gusta', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                        </button>
                        <?php if ($mostrar_comentarios): ?>
                        <button class="btn-comentar" data-post-id="<?php echo esc_attr($publicacion->id); ?>">
                            <span class="dashicons dashicons-admin-comments"></span>
                            <?php _e('Comentar', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                        </button>
                        <?php endif; ?>
                        <button class="btn-compartir" data-post-id="<?php echo esc_attr($publicacion->id); ?>">
                            <span class="dashicons dashicons-share"></span>
                            <?php _e('Compartir', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                        </button>
                    </div>
                    <?php endif; ?>

                    <?php if ($mostrar_comentarios): ?>
                    <div class="post-comentarios" style="display:none;">
                        <div class="comentarios-lista"></div>
                        <?php if (is_user_logged_in()): ?>
                        <form class="form-comentar">
                            <input type="hidden" name="post_id" value="<?php echo esc_attr($publicacion->id); ?>">
                            <?php echo get_avatar($usuario_actual, 32); ?>
                            <input type="text" name="comentario" placeholder="<?php esc_attr_e('Escribe un comentario...', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>">
                            <button type="submit"><span class="dashicons dashicons-arrow-right-alt2"></span></button>
                        </form>
                        <?php endif; ?>
                    </div>
                    <?php endif; ?>
                </article>
                <?php endforeach; ?>
            </div>

            <div class="feed-cargar-mas" style="display:none;">
                <button class="flavor-btn flavor-btn--secondary" data-offset="<?php echo esc_attr($limite); ?>">
                    <?php _e('Cargar más publicaciones', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                </button>
            </div>

            <?php endif; ?>
        </div>

        <style>
        .flavor-social-feed-integrado { max-width: 100%; }

        .feed-nueva-publicacion { display: flex; gap: 0.75rem; padding: 1rem; background: var(--flavor-bg-card, #fff); border-radius: 8px; border: 1px solid var(--flavor-border, #e0e0e0); margin-bottom: 1rem; }
        .publicacion-avatar img { border-radius: 50%; }
        .form-publicar { flex: 1; }
        .form-publicar textarea { width: 100%; border: 1px solid var(--flavor-border, #ddd); border-radius: 20px; padding: 0.75rem 1rem; resize: none; font-family: inherit; }
        .form-publicar textarea:focus { outline: none; border-color: var(--flavor-primary, #4f46e5); }
        .rs-inline-notice { display: none; margin-bottom: 0.75rem; padding: 0.75rem 1rem; border-radius: 10px; font-size: 0.95rem; }
        .rs-inline-notice--error { background: #fee2e2; color: #991b1b; }
        .rs-inline-notice--success { background: #dcfce7; color: #166534; }
        .publicacion-acciones { display: flex; justify-content: space-between; align-items: center; margin-top: 0.5rem; }
        .publicacion-adjuntos { display: flex; gap: 0.5rem; }
        .publicacion-adjuntos button { background: none; border: none; cursor: pointer; padding: 0.5rem; border-radius: 50%; color: var(--flavor-text-secondary, #666); }
        .publicacion-adjuntos button:hover { background: var(--flavor-bg-secondary, #f0f0f0); }
        .preview-imagen { position: relative; margin-top: 0.5rem; }
        .preview-imagen img { max-height: 150px; border-radius: 8px; }
        .btn-quitar-imagen { position: absolute; top: 0; right: 0; background: rgba(0,0,0,0.6); color: #fff; border: none; border-radius: 50%; width: 24px; height: 24px; cursor: pointer; }

        .feed-vacio { text-align: center; padding: 3rem; color: var(--flavor-text-secondary, #666); }
        .feed-vacio .dashicons { font-size: 3rem; width: 3rem; height: 3rem; margin-bottom: 1rem; opacity: 0.5; }

        .feed-publicaciones { display: flex; flex-direction: column; gap: 1rem; }

        .feed-post { background: var(--flavor-bg-card, #fff); border-radius: 8px; border: 1px solid var(--flavor-border, #e0e0e0); overflow: hidden; }
        .post-header { display: flex; align-items: center; gap: 0.75rem; padding: 1rem; }
        .post-avatar img { border-radius: 50%; }
        .post-meta { flex: 1; }
        .post-autor { display: block; font-weight: 600; color: var(--flavor-text-primary, #333); }
        .post-fecha { font-size: 0.8rem; color: var(--flavor-text-muted, #888); }
        .post-opciones { position: relative; }
        .btn-opciones-post { background: none; border: none; cursor: pointer; padding: 0.5rem; border-radius: 50%; }
        .btn-opciones-post:hover { background: var(--flavor-bg-secondary, #f0f0f0); }
        .menu-opciones { position: absolute; right: 0; top: 100%; background: var(--flavor-bg-card, #fff); border: 1px solid var(--flavor-border, #ddd); border-radius: 8px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); z-index: 10; }
        .menu-opciones button { display: block; width: 100%; padding: 0.75rem 1rem; text-align: left; border: none; background: none; cursor: pointer; }
        .menu-opciones button:hover { background: var(--flavor-bg-secondary, #f0f0f0); }

        .post-contenido { padding: 0 1rem 1rem; line-height: 1.5; }
        .post-imagenes { padding: 0 1rem 1rem; }
        .post-imagenes img { width: 100%; border-radius: 8px; cursor: pointer; }
        .galeria-grid { display: grid; grid-template-columns: repeat(2, 1fr); gap: 0.5rem; }

        .post-reacciones { display: flex; justify-content: space-between; padding: 0.5rem 1rem; border-top: 1px solid var(--flavor-border, #e0e0e0); font-size: 0.85rem; color: var(--flavor-text-secondary, #666); }
        .reacciones-iconos span { margin-right: -4px; }
        .reacciones-count { margin-left: 0.5rem; }

        .post-acciones-bar { display: flex; justify-content: space-around; padding: 0.5rem; border-top: 1px solid var(--flavor-border, #e0e0e0); }
        .post-acciones-bar button { flex: 1; display: flex; align-items: center; justify-content: center; gap: 0.5rem; padding: 0.75rem; background: none; border: none; cursor: pointer; color: var(--flavor-text-secondary, #666); font-size: 0.9rem; border-radius: 4px; transition: background 0.2s; }
        .post-acciones-bar button:hover { background: var(--flavor-bg-secondary, #f0f0f0); }
        .post-acciones-bar button.activo { color: var(--flavor-primary, #4f46e5); }

        .post-comentarios { padding: 1rem; background: var(--flavor-bg-secondary, #f5f5f5); }
        .comentarios-lista { margin-bottom: 1rem; }
        .form-comentar { display: flex; align-items: center; gap: 0.5rem; }
        .form-comentar img { border-radius: 50%; }
        .form-comentar input[type="text"] { flex: 1; padding: 0.5rem 1rem; border: 1px solid var(--flavor-border, #ddd); border-radius: 20px; background: var(--flavor-bg-card, #fff); }
        .form-comentar button { background: none; border: none; cursor: pointer; color: var(--flavor-primary, #4f46e5); }

        .feed-cargar-mas { text-align: center; padding: 1rem; }
        </style>

        <script>
        (function($) {
            // Verificar que flavorRedSocial existe antes de usar sus propiedades
            if (typeof flavorRedSocial === 'undefined') {
                console.warn('RedSocial: flavorRedSocial no definido, funcionalidades AJAX desactivadas');
                return;
            }

            var $container = $('.flavor-social-feed-integrado[data-entidad-id="<?php echo esc_js($entidad_id); ?>"]');
            var entidad = '<?php echo esc_js($entidad); ?>';
            var entidadId = <?php echo intval($entidad_id); ?>;

            // Toggle comentarios
            $container.on('click', '.btn-comentar', function() {
                $(this).closest('.feed-post').find('.post-comentarios').slideToggle();
            });

            // Toggle menu opciones
            $container.on('click', '.btn-opciones-post', function(e) {
                e.stopPropagation();
                $(this).siblings('.menu-opciones').toggle();
            });

            // Cerrar menus al hacer click fuera
            $(document).on('click', function() {
                $container.find('.menu-opciones').hide();
            });

            // Reaccionar
            $container.on('click', '.btn-reaccionar', function() {
                var $btn = $(this);
                var postId = $btn.data('post-id');

                $.post(flavorRedSocial.ajaxUrl, {
                    action: 'rs_reaccionar',
                    nonce: flavorRedSocial.nonce,
                    post_id: postId,
                    tipo: 'like'
                }, function(response) {
                    if (response.success) {
                        $btn.toggleClass('activo');
                    }
                });
            });

            // Adjuntar imagen
            $container.find('.btn-adjuntar-imagen').on('click', function() {
                $(this).siblings('input[type="file"]').click();
            });

            $container.find('input[name="imagen"]').on('change', function() {
                var file = this.files[0];
                if (file) {
                    var reader = new FileReader();
                    reader.onload = function(e) {
                        $container.find('.preview-imagen img').attr('src', e.target.result);
                        $container.find('.preview-imagen').show();
                    };
                    reader.readAsDataURL(file);
                }
            });

            $container.find('.btn-quitar-imagen').on('click', function() {
                $container.find('input[name="imagen"]').val('');
                $container.find('.preview-imagen').hide();
            });

            function mostrarAviso(mensaje, tipo) {
                var $notice = $container.find('.rs-inline-notice');
                if (!$notice.length) {
                    $notice = $('<div class="rs-inline-notice" />').prependTo($container);
                }

                $notice
                    .removeClass('rs-inline-notice--error rs-inline-notice--success')
                    .addClass(tipo === 'success' ? 'rs-inline-notice--success' : 'rs-inline-notice--error')
                    .text(mensaje)
                    .show();
            }

            // Publicar
            $container.find('.form-publicar').on('submit', function(e) {
                e.preventDefault();
                var $form = $(this);
                var contenido = $form.find('textarea').val().trim();

                if (!contenido) return;

                var formData = new FormData(this);
                formData.append('action', 'rs_crear_publicacion');
                formData.append('nonce', flavorRedSocial.nonce);
                formData.append('entidad', entidad);
                formData.append('entidad_id', entidadId);
                formData.append('contenido', contenido);

                $.ajax({
                    url: flavorRedSocial.ajaxUrl,
                    type: 'POST',
                    data: formData,
                    processData: false,
                    contentType: false,
                    success: function(response) {
                        if (response.success) {
                            location.reload();
                        } else {
                            mostrarAviso(response.data?.message || 'Error al publicar', 'error');
                        }
                    },
                    error: function() {
                        mostrarAviso('Error de conexion al publicar.', 'error');
                    }
                });
            });

            // Comentar
            $container.on('submit', '.form-comentar', function(e) {
                e.preventDefault();
                var $form = $(this);
                var postId = $form.find('input[name="post_id"]').val();
                var comentario = $form.find('input[name="comentario"]').val().trim();

                if (!comentario) return;

                $.post(flavorRedSocial.ajaxUrl, {
                    action: 'rs_comentar',
                    nonce: flavorRedSocial.nonce,
                    post_id: postId,
                    comentario: comentario
                }, function(response) {
                    if (response.success) {
                        $form.find('input[name="comentario"]').val('');
                        // Recargar comentarios
                        location.reload();
                    }
                });
            });
        })(jQuery);
        </script>
        <?php
        return ob_get_clean();
    }

    /**
     * Obtiene publicaciones asociadas a una entidad
     *
     * @param string $entidad    Tipo de entidad
     * @param int    $entidad_id ID de la entidad
     * @param int    $limite     Número máximo de publicaciones
     * @return array Lista de publicaciones
     */
    private function obtener_publicaciones_entidad($entidad, $entidad_id, $limite = 10) {
        global $wpdb;

        $tabla_publicaciones = $wpdb->prefix . 'flavor_social_posts';

        return $wpdb->get_results($wpdb->prepare(
            "SELECT p.*,
                    (SELECT COUNT(*) FROM {$wpdb->prefix}flavor_social_comentarios WHERE post_id = p.id) as num_comentarios
             FROM {$tabla_publicaciones} p
             WHERE p.entidad_tipo = %s AND p.entidad_id = %d AND p.estado = 'publicado'
             ORDER BY p.fecha_creacion DESC
             LIMIT %d",
            $entidad,
            $entidad_id,
            $limite
        ));
    }

    /**
     * Verifica si el usuario puede publicar en una entidad
     *
     * @param string $entidad    Tipo de entidad
     * @param int    $entidad_id ID de la entidad
     * @return bool
     */
    private function usuario_puede_publicar_entidad($entidad, $entidad_id) {
        if (!is_user_logged_in()) {
            return false;
        }

        $usuario_id = get_current_user_id();

        // Verificar permisos según el tipo de entidad
        $modulo_entidad = Flavor_Chat_Module_Loader::get_instance()->get_module($entidad);
        if ($modulo_entidad && method_exists($modulo_entidad, 'usuario_es_miembro')) {
            return $modulo_entidad->usuario_es_miembro($entidad_id, $usuario_id);
        }

        return true;
    }

    /**
     * Obtiene las reacciones de una publicación agrupadas por tipo
     *
     * @param int $post_id ID de la publicación
     * @return array Reacciones agrupadas
     */
    private function obtener_reacciones_publicacion($post_id) {
        global $wpdb;

        $tabla_reacciones = $wpdb->prefix . 'flavor_social_reacciones';

        $resultados = $wpdb->get_results($wpdb->prepare(
            "SELECT tipo, COUNT(*) as cantidad FROM {$tabla_reacciones}
             WHERE post_id = %d GROUP BY tipo",
            $post_id
        ), ARRAY_A);

        $reacciones = [];
        foreach ($resultados as $fila) {
            $reacciones[$fila['tipo']] = (int) $fila['cantidad'];
        }

        return $reacciones;
    }

    /**
     * Obtiene la reacción del usuario actual a una publicación
     *
     * @param int $post_id    ID de la publicación
     * @param int $usuario_id ID del usuario
     * @return string|null Tipo de reacción o null
     */
    private function obtener_mi_reaccion($post_id, $usuario_id) {
        global $wpdb;

        $tabla_reacciones = $wpdb->prefix . 'flavor_social_reacciones';

        return $wpdb->get_var($wpdb->prepare(
            "SELECT tipo FROM {$tabla_reacciones}
             WHERE post_id = %d AND usuario_id = %d",
            $post_id,
            $usuario_id
        ));
    }

    /**
     * Registrar páginas de administración
     */
    public function registrar_paginas_admin() {
        static $registered = false;
        if ($registered) {
            return;
        }
        $registered = true;


        $capability = 'manage_options';

        // Páginas ocultas (sin menú visible en el sidebar)
        add_submenu_page(
            null,
            __('Red Social - Estadísticas', FLAVOR_PLATFORM_TEXT_DOMAIN),
            __('Estadísticas', FLAVOR_PLATFORM_TEXT_DOMAIN),
            $capability,
            'flavor-red-social-estadisticas',
            [$this, 'render_pagina_estadisticas']
        );

        add_submenu_page(
            null,
            __('Red Social - Moderación', FLAVOR_PLATFORM_TEXT_DOMAIN),
            __('Moderación', FLAVOR_PLATFORM_TEXT_DOMAIN),
            $capability,
            'flavor-red-social-moderacion',
            [$this, 'render_pagina_moderacion']
        );

        add_submenu_page(
            null,
            __('Red Social - Publicaciones', FLAVOR_PLATFORM_TEXT_DOMAIN),
            __('Publicaciones', FLAVOR_PLATFORM_TEXT_DOMAIN),
            $capability,
            'flavor-red-social-publicaciones',
            [$this, 'render_pagina_publicaciones']
        );

        add_submenu_page(
            null,
            __('Red Social - Usuarios', FLAVOR_PLATFORM_TEXT_DOMAIN),
            __('Usuarios', FLAVOR_PLATFORM_TEXT_DOMAIN),
            $capability,
            'flavor-red-social-usuarios',
            [$this, 'render_pagina_usuarios']
        );
    }

    /**
     * Renderizar página dashboard
     */
    public function render_pagina_dashboard() {
        $views_path = dirname(__FILE__) . '/views/dashboard.php';
        if (file_exists($views_path)) {
            include $views_path;
        } else {
            echo '<div class="wrap"><h1>' . esc_html__('Dashboard Red Social', FLAVOR_PLATFORM_TEXT_DOMAIN) . '</h1>';
            echo '<p>' . esc_html__('Panel de administración del módulo de red social.', FLAVOR_PLATFORM_TEXT_DOMAIN) . '</p></div>';
        }
    }

    /**
     * Renderizar página de estadísticas
     */
    public function render_pagina_estadisticas() {
        $views_path = dirname(__FILE__) . '/views/estadisticas.php';
        if (file_exists($views_path)) {
            include $views_path;
        } else {
            echo '<div class="wrap"><h1>' . esc_html__('Estadísticas Red Social', FLAVOR_PLATFORM_TEXT_DOMAIN) . '</h1>';
            echo '<p>' . esc_html__('Estadísticas y métricas de la red social comunitaria.', FLAVOR_PLATFORM_TEXT_DOMAIN) . '</p></div>';
        }
    }

    /**
     * Renderizar página de publicaciones
     */
    public function render_pagina_publicaciones() {
        $views_path = dirname(__FILE__) . '/views/publicaciones.php';
        if (file_exists($views_path)) {
            include $views_path;
        } else {
            echo '<div class="wrap"><h1>' . esc_html__('Gestión de Publicaciones', FLAVOR_PLATFORM_TEXT_DOMAIN) . '</h1></div>';
        }
    }

    /**
     * Renderizar página de usuarios
     */
    public function render_pagina_usuarios() {
        $views_path = dirname(__FILE__) . '/views/usuarios.php';
        if (file_exists($views_path)) {
            include $views_path;
        } else {
            echo '<div class="wrap"><h1>' . esc_html__('Gestión de Usuarios', FLAVOR_PLATFORM_TEXT_DOMAIN) . '</h1></div>';
        }
    }

    /**
     * Renderizar página de moderación
     */
    public function render_pagina_moderacion() {
        $views_path = dirname(__FILE__) . '/views/moderacion.php';
        if (file_exists($views_path)) {
            include $views_path;
        } else {
            echo '<div class="wrap"><h1>' . esc_html__('Moderación de Contenido', FLAVOR_PLATFORM_TEXT_DOMAIN) . '</h1></div>';
        }
    }


    /**
     * Inicializa el dashboard tab del módulo
     */
    private function inicializar_dashboard_tab() {
        $archivo_tab = dirname(__FILE__) . '/class-red-social-dashboard-tab.php';
        if (file_exists($archivo_tab)) {
            require_once $archivo_tab;
            Flavor_Red_Social_Dashboard_Tab::get_instance();
        }
    }
}

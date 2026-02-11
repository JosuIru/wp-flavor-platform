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
 */
class Flavor_Chat_Red_Social_Module extends Flavor_Chat_Module_Base {

    use Flavor_Module_Admin_Pages_Trait;

    /** @var string Version del modulo */
    const VERSION = '2.0.0';

    /** @var array Tipos de reaccion permitidos */
    const TIPOS_REACCION = ['me_gusta', 'me_encanta', 'me_divierte', 'me_entristece', 'me_enfada'];

    /** @var array Tipos de notificacion */
    const TIPOS_NOTIFICACION = ['like', 'comentario', 'seguidor', 'mencion', 'compartido', 'historia'];

    /**
     * Constructor
     */
    public function __construct() {
        $this->id = 'red_social';
        $this->name = 'Red Social Comunitaria'; // Translation loaded on init
        $this->description = 'Red social alternativa sin publicidad, centrada en la comunidad y sus intereses.'; // Translation loaded on init

        parent::__construct();
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
            return __('Las tablas de Red Social no estan creadas. Se crearan automaticamente al activar.', 'flavor-chat-ia');
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
     * {@inheritdoc}
     */
    public function init() {
        // Registrar en panel de administración unificado
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
            wp_send_json_error(['message' => __('Debes iniciar sesion', 'flavor-chat-ia')]);
        }

        $contenido = sanitize_textarea_field($_POST['contenido'] ?? '');
        $visibilidad = sanitize_text_field($_POST['visibilidad'] ?? 'comunidad');
        $tipo = 'texto';
        $adjuntos_json = null;

        if (empty($contenido) && empty($_FILES['adjuntos'])) {
            wp_send_json_error(['message' => __('Debes iniciar sesion', 'flavor-chat-ia')]);
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
            wp_send_json_error(['message' => __('publicacion_id', 'flavor-chat-ia')]);
        }

        $publicacion_id = $wpdb->insert_id;

        // Procesar hashtags
        $this->procesar_hashtags($contenido, $publicacion_id);

        // Procesar menciones
        $this->procesar_menciones($contenido, $publicacion_id, $usuario_id);

        // Actualizar contador de perfil
        $this->actualizar_contador_perfil($usuario_id, 'total_publicaciones', 1);

        // Obtener HTML de la publicacion
        $publicacion_html = $this->renderizar_publicacion($publicacion_id);

        wp_send_json_success([
            'message' => __('Publicacion creada', 'flavor-chat-ia'),
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
            wp_send_json_error(['message' => __('Debes iniciar sesion', 'flavor-chat-ia')]);
        }

        $publicacion_id = absint($_POST['publicacion_id'] ?? 0);
        $tipo_reaccion = sanitize_text_field($_POST['tipo'] ?? 'me_gusta');

        if (!$publicacion_id) {
            wp_send_json_error(['message' => __('usuario_id', 'flavor-chat-ia')]);
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

            if ($publicacion && $publicacion->autor_id != $usuario_id) {
                $this->crear_notificacion($publicacion->autor_id, $usuario_id, 'like', $publicacion_id, 'publicacion');
            }

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
            wp_send_json_error(['message' => __('Debes iniciar sesion', 'flavor-chat-ia')]);
        }

        $publicacion_id = absint($_POST['publicacion_id'] ?? 0);
        $contenido = sanitize_textarea_field($_POST['contenido'] ?? '');
        $padre_id = absint($_POST['padre_id'] ?? 0);

        if (!$publicacion_id || empty($contenido)) {
            wp_send_json_error(['message' => __('publicacion', 'flavor-chat-ia')]);
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
            wp_send_json_error(['message' => __('Debes iniciar sesion', 'flavor-chat-ia')]);
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

        if ($publicacion && $publicacion->autor_id != $usuario_id) {
            $this->crear_notificacion($publicacion->autor_id, $usuario_id, 'comentario', $publicacion_id, 'publicacion');
        }

        // Procesar menciones en comentario
        $this->procesar_menciones($contenido, $publicacion_id, $usuario_id);

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
            wp_send_json_error(['message' => __('Debes iniciar sesion', 'flavor-chat-ia')]);
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
            wp_send_json_error(['message' => __('Debes iniciar sesion', 'flavor-chat-ia')]);
        }

        $comentario_id = absint($_POST['comentario_id'] ?? 0);

        if (!$comentario_id) {
            wp_send_json_error(['message' => __('Debes iniciar sesion', 'flavor-chat-ia')]);
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
            wp_send_json_error(['message' => __('total_seguidores', 'flavor-chat-ia')]);
        }

        $seguido_id = absint($_POST['usuario_id'] ?? 0);

        if (!$seguido_id || $seguidor_id === $seguido_id) {
            wp_send_json_error(['message' => __('Debes iniciar sesion', 'flavor-chat-ia')]);
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
            wp_send_json_error(['message' => __('Usuario invalido', 'flavor-chat-ia')]);
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
            wp_send_json_error(['message' => __('Debes iniciar sesion', 'flavor-chat-ia')]);
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
            wp_send_json_error(['message' => __('fecha_creacion', 'flavor-chat-ia')]);
        }

        if (!$this->get_setting('permite_historias')) {
            wp_send_json_error(['message' => __('Historia creada', 'flavor-chat-ia')]);
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
            wp_send_json_error(['message' => __('Debes iniciar sesion', 'flavor-chat-ia')]);
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

        wp_send_json_success(['message' => __('Historia creada', 'flavor-chat-ia')]);
    }

    /**
     * AJAX: Guardar post
     */
    public function ajax_guardar_post() {
        check_ajax_referer('rs_nonce', 'nonce');

        $usuario_id = get_current_user_id();
        if (!$usuario_id) {
            wp_send_json_error(['message' => __('Debes iniciar sesion', 'flavor-chat-ia')]);
        }

        $publicacion_id = absint($_POST['publicacion_id'] ?? 0);

        if (!$publicacion_id) {
            wp_send_json_error(['message' => __('Debes iniciar sesion', 'flavor-chat-ia')]);
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
            wp_send_json_error(['message' => __('SELECT COUNT(*) FROM $tabla_notificaciones WHERE usuario_id = %d AND leida = 0', 'flavor-chat-ia')]);
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
            wp_send_json_error(['message' => __('Debes iniciar sesion', 'flavor-chat-ia')]);
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

        wp_send_json_success(['message' => __('Historia creada', 'flavor-chat-ia')]);
    }

    /**
     * AJAX: Obtener perfil
     */
    public function ajax_obtener_perfil() {
        check_ajax_referer('rs_nonce', 'nonce');

        $usuario_id = absint($_POST['usuario_id'] ?? 0);
        $usuario_actual = get_current_user_id();

        if (!$usuario_id) {
            wp_send_json_error(['message' => __('Debes iniciar sesion', 'flavor-chat-ia')]);
        }

        $perfil = $this->obtener_perfil_completo($usuario_id);

        if (!$perfil) {
            wp_send_json_error(['message' => __('sitio_web', 'flavor-chat-ia')]);
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
            wp_send_json_error(['message' => __('Debes iniciar sesion', 'flavor-chat-ia')]);
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

        wp_send_json_success(['message' => __('Historia creada', 'flavor-chat-ia')]);
    }

    /**
     * AJAX: Eliminar publicacion
     */
    public function ajax_eliminar_publicacion() {
        check_ajax_referer('rs_nonce', 'nonce');

        $usuario_id = get_current_user_id();
        if (!$usuario_id) {
            wp_send_json_error(['message' => __('Debes iniciar sesion', 'flavor-chat-ia')]);
        }

        $publicacion_id = absint($_POST['publicacion_id'] ?? 0);

        if (!$publicacion_id) {
            wp_send_json_error(['message' => __('Debes iniciar sesion', 'flavor-chat-ia')]);
        }

        global $wpdb;
        $tabla_publicaciones = $wpdb->prefix . 'flavor_social_publicaciones';

        $publicacion = $wpdb->get_row($wpdb->prepare(
            "SELECT autor_id FROM $tabla_publicaciones WHERE id = %d",
            $publicacion_id
        ));

        if (!$publicacion || ($publicacion->autor_id != $usuario_id && !current_user_can('manage_options'))) {
            wp_send_json_error(['message' => __('Debes iniciar sesion', 'flavor-chat-ia')]);
        }

        $wpdb->update(
            $tabla_publicaciones,
            ['estado' => 'eliminado'],
            ['id' => $publicacion_id],
            ['%s'],
            ['%d']
        );

        $this->actualizar_contador_perfil($publicacion->autor_id, 'total_publicaciones', -1);

        wp_send_json_success(['message' => __('Historia creada', 'flavor-chat-ia')]);
    }

    /**
     * AJAX: Reportar contenido
     */
    public function ajax_reportar_contenido() {
        check_ajax_referer('rs_nonce', 'nonce');

        $usuario_id = get_current_user_id();
        if (!$usuario_id) {
            wp_send_json_error(['message' => __('Debes iniciar sesion', 'flavor-chat-ia')]);
        }

        $tipo_contenido = sanitize_text_field($_POST['tipo'] ?? '');
        $contenido_id = absint($_POST['contenido_id'] ?? 0);
        $motivo = sanitize_textarea_field($_POST['motivo'] ?? '');

        if (!$tipo_contenido || !$contenido_id) {
            wp_send_json_error(['message' => __('Debes iniciar sesion', 'flavor-chat-ia')]);
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

        wp_send_json_success(['message' => __('Historia creada', 'flavor-chat-ia')]);
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
            return new WP_REST_Response(['success' => false, 'message' => __('Contenido vacio', 'flavor-chat-ia')], 400);
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
            return new WP_REST_Response(['success' => false, 'message' => __('trending', 'flavor-chat-ia')], 404);
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
            return new WP_REST_Response(['success' => false, 'message' => __('No encontrada', 'flavor-chat-ia')], 404);
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
                            <button class="rs-feed-tab <?php echo $atts['tipo'] === 'timeline' ? 'active' : ''; ?>" data-tipo="timeline"><?php echo esc_html__('Para ti', 'flavor-chat-ia'); ?></button>
                            <button class="rs-feed-tab <?php echo $atts['tipo'] === 'comunidad' ? 'active' : ''; ?>" data-tipo="comunidad"><?php echo esc_html__('Comunidad', 'flavor-chat-ia'); ?></button>
                            <button class="rs-feed-tab <?php echo $atts['tipo'] === 'trending' ? 'active' : ''; ?>" data-tipo="trending"><?php echo esc_html__('Trending', 'flavor-chat-ia'); ?></button>
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
                            <span class="rs-perfil-stat-label"><?php echo esc_html__('Publicaciones', 'flavor-chat-ia'); ?></span>
                        </div>
                        <div class="rs-perfil-stat" data-tipo="seguidores">
                            <span class="rs-perfil-stat-num"><?php echo number_format($perfil['total_seguidores']); ?></span>
                            <span class="rs-perfil-stat-label"><?php echo esc_html__('Seguidores', 'flavor-chat-ia'); ?></span>
                        </div>
                        <div class="rs-perfil-stat">
                            <span class="rs-perfil-stat-num"><?php echo number_format($perfil['total_siguiendo']); ?></span>
                            <span class="rs-perfil-stat-label"><?php echo esc_html__('Siguiendo', 'flavor-chat-ia'); ?></span>
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
        $hashtag_filtro = isset($_GET['rs_hashtag']) ? sanitize_text_field($_GET['rs_hashtag']) : '';

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
                    <p style="text-align: center; padding: 40px; color: var(--rs-text-muted);"><?php echo esc_html__('No tienes notificaciones', 'flavor-chat-ia'); ?></p>
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
                    <span class="rs-historia-nombre"><?php echo esc_html__('Tu historia', 'flavor-chat-ia'); ?></span>
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

        $desde_id = $desde_id > 0 ? $desde_id : PHP_INT_MAX;

        switch ($tipo) {
            case 'timeline':
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

            default:
                return [];
        }
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
                                  placeholder="<?php echo esc_attr__('¿Que quieres compartir con la comunidad?', 'flavor-chat-ia'); ?>"
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
                            <?php echo esc_html__('Foto', 'flavor-chat-ia'); ?>
                        </button>
                        <?php endif; ?>
                        <?php if ($this->get_setting('permite_videos')): ?>
                        <button type="button" class="rs-adjunto-btn" data-tipo="video">
                            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <polygon points="23 7 16 12 23 17 23 7"/><rect x="1" y="5" width="15" height="14" rx="2"/>
                            </svg>
                            <?php echo esc_html__('Video', 'flavor-chat-ia'); ?>
                        </button>
                        <?php endif; ?>
                    </div>
                    <input type="hidden" name="visibilidad" value="<?php echo esc_attr__('comunidad', 'flavor-chat-ia'); ?>">
                    <button type="submit" class="rs-btn-publicar"><?php echo esc_html__('Publicar', 'flavor-chat-ia'); ?></button>
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
        $adjuntos = json_decode($publicacion->adjuntos, true);

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
                        <h4><a href="?rs_perfil=<?php echo esc_attr($publicacion->autor_id); ?>">
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
                    <span><?php echo esc_html__('Compartir', 'flavor-chat-ia'); ?></span>
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
                    <button class="rs-comentario-responder"><?php echo esc_html__('Responder', 'flavor-chat-ia'); ?></button>
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
                <input type="text" class="rs-comentar-input" placeholder="<?php echo esc_attr__('Escribe un comentario...', 'flavor-chat-ia'); ?>">
                <button type="submit" class="rs-comentar-enviar"><?php echo esc_html__('Enviar', 'flavor-chat-ia'); ?></button>
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
            <h3 class="rs-widget-titulo"><?php echo esc_html__('Sugerencias para ti', 'flavor-chat-ia'); ?></h3>
            <div class="rs-sugerencias-lista">
                <?php foreach ($sugerencias as $sugerencia): ?>
                    <?php $usuario = get_userdata($sugerencia->ID); ?>
                    <div class="rs-sugerencia">
                        <img class="rs-sugerencia-avatar"
                             src="<?php echo esc_url(get_avatar_url($sugerencia->ID, ['size' => 50])); ?>"
                             alt="">
                        <div class="rs-sugerencia-info">
                            <h4 class="rs-sugerencia-nombre"><?php echo esc_html($usuario->display_name); ?></h4>
                            <span class="rs-sugerencia-motivo"><?php echo esc_html__('Sugerido para ti', 'flavor-chat-ia'); ?></span>
                        </div>
                        <button class="rs-btn-seguir" data-usuario-id="<?php echo esc_attr($sugerencia->ID); ?>">
                            <?php echo esc_html__('Seguir', 'flavor-chat-ia'); ?>
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
            <h3 class="rs-widget-titulo"><?php echo esc_html__('Tendencias', 'flavor-chat-ia'); ?></h3>
            <div class="rs-trending-lista">
                <?php foreach ($trending as $index => $hashtag): ?>
                    <div class="rs-trending-item" onclick="window.location='?rs_hashtag=<?php echo esc_attr($hashtag->hashtag); ?>'">
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
        $metodo_accion = 'action_' . $action_name;

        if (method_exists($this, $metodo_accion)) {
            return $this->$metodo_accion($params);
        }

        return [
            'success' => false,
            'error' => "Accion no implementada: {$action_name}",
        ];
    }

    /**
     * Accion: Ver timeline
     */
    private function action_timeline($params) {
        $usuario_id = get_current_user_id();

        if (!$usuario_id) {
            return ['success' => false, 'error' => __('fields', 'flavor-chat-ia')];
        }

        $limite = absint($params['limite'] ?? 20);
        $desde_id = absint($params['desde'] ?? 0);

        $publicaciones = $this->obtener_publicaciones_feed('timeline', $usuario_id, $desde_id, $limite);

        return [
            'success' => true,
            'publicaciones' => array_map([$this, 'formatear_publicacion_api'], $publicaciones),
        ];
    }

    /**
     * Componentes web del modulo
     */
    public function get_web_components() {
        return [
            'hero_social' => [
                'label' => __('Hero Red Social', 'flavor-chat-ia'),
                'category' => 'hero',
                'icon' => 'dashicons-groups',
                'fields' => [
                    'titulo' => ['type' => 'text', 'default' => __('Red Social Comunitaria', 'flavor-chat-ia')],
                    'subtitulo' => ['type' => 'textarea', 'default' => __('Conecta con tus vecinos de forma privada y segura', 'flavor-chat-ia')],
                    'imagen_fondo' => ['type' => 'image', 'default' => ''],
                    'mostrar_cta' => ['type' => 'toggle', 'default' => true],
                ],
                'template' => 'red-social/hero',
            ],
            'timeline_feed' => [
                'label' => __('Feed de Publicaciones', 'flavor-chat-ia'),
                'category' => 'content',
                'icon' => 'dashicons-rss',
                'fields' => [
                    'titulo' => ['type' => 'text', 'default' => __('Ultimas Publicaciones', 'flavor-chat-ia')],
                    'limite' => ['type' => 'number', 'default' => 10],
                    'mostrar_formulario' => ['type' => 'toggle', 'default' => true],
                    'tipo_feed' => ['type' => 'select', 'options' => ['timeline', 'comunidad', 'trending'], 'default' => 'timeline'],
                ],
                'template' => 'red-social/feed',
            ],
            'stats_comunidad' => [
                'label' => __('Estadisticas de la Comunidad', 'flavor-chat-ia'),
                'category' => 'content',
                'icon' => 'dashicons-chart-area',
                'fields' => [
                    'titulo' => ['type' => 'text', 'default' => __('Nuestra Comunidad', 'flavor-chat-ia')],
                    'mostrar_miembros' => ['type' => 'toggle', 'default' => true],
                    'mostrar_publicaciones' => ['type' => 'toggle', 'default' => true],
                    'mostrar_actividad' => ['type' => 'toggle', 'default' => true],
                ],
                'template' => 'red-social/stats',
            ],
            'sugerencias_usuarios' => [
                'label' => __('Sugerencias de Conexion', 'flavor-chat-ia'),
                'category' => 'listings',
                'icon' => 'dashicons-admin-users',
                'fields' => [
                    'titulo' => ['type' => 'text', 'default' => __('Vecinos que Quizas Conozcas', 'flavor-chat-ia')],
                    'limite' => ['type' => 'number', 'default' => 6],
                    'criterio' => ['type' => 'select', 'options' => ['cercania', 'intereses', 'aleatorio'], 'default' => 'cercania'],
                ],
                'template' => 'red-social/sugerencias',
            ],
            'historias_carousel' => [
                'label' => __('Carrusel de Historias', 'flavor-chat-ia'),
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
            'label' => __('Red Social', 'flavor-chat-ia'),
            'icon' => 'dashicons-share',
            'capability' => 'manage_options',
            'categoria' => 'comunidad',
            'paginas' => [
                [
                    'slug' => 'flavor-red-social-dashboard',
                    'titulo' => __('Dashboard', 'flavor-chat-ia'),
                    'callback' => [$this, 'render_admin_dashboard'],
                ],
                [
                    'slug' => 'flavor-red-social-publicaciones',
                    'titulo' => __('Publicaciones', 'flavor-chat-ia'),
                    'callback' => [$this, 'render_admin_publicaciones'],
                    'badge' => [$this, 'contar_publicaciones_pendientes'],
                ],
                [
                    'slug' => 'flavor-red-social-moderacion',
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
            <h1><?php esc_html_e('Dashboard Red Social', 'flavor-chat-ia'); ?></h1>

            <div class="flavor-admin-stats" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 20px; margin-top: 20px;">
                <div class="flavor-stat-card" style="background: #fff; padding: 20px; border-radius: 8px; box-shadow: 0 1px 3px rgba(0,0,0,0.1);">
                    <span class="dashicons dashicons-format-aside" style="font-size: 32px; color: #0073aa;"></span>
                    <h3 style="margin: 10px 0 5px;"><?php echo esc_html($total_publicaciones); ?></h3>
                    <p style="margin: 0; color: #666;"><?php esc_html_e('Total Publicaciones', 'flavor-chat-ia'); ?></p>
                </div>

                <div class="flavor-stat-card" style="background: #fff; padding: 20px; border-radius: 8px; box-shadow: 0 1px 3px rgba(0,0,0,0.1);">
                    <span class="dashicons dashicons-groups" style="font-size: 32px; color: #00a32a;"></span>
                    <h3 style="margin: 10px 0 5px;"><?php echo esc_html($total_usuarios); ?></h3>
                    <p style="margin: 0; color: #666;"><?php esc_html_e('Usuarios Activos', 'flavor-chat-ia'); ?></p>
                </div>

                <div class="flavor-stat-card" style="background: #fff; padding: 20px; border-radius: 8px; box-shadow: 0 1px 3px rgba(0,0,0,0.1);">
                    <span class="dashicons dashicons-calendar-alt" style="font-size: 32px; color: #dba617;"></span>
                    <h3 style="margin: 10px 0 5px;"><?php echo esc_html($publicaciones_hoy); ?></h3>
                    <p style="margin: 0; color: #666;"><?php esc_html_e('Publicaciones Hoy', 'flavor-chat-ia'); ?></p>
                </div>
            </div>

            <div class="flavor-admin-section" style="margin-top: 30px;">
                <h2><?php esc_html_e('Acciones Rápidas', 'flavor-chat-ia'); ?></h2>
                <p>
                    <a href="<?php echo esc_url(admin_url('admin.php?page=flavor-red-social-publicaciones')); ?>" class="button button-primary">
                        <?php esc_html_e('Ver Publicaciones', 'flavor-chat-ia'); ?>
                    </a>
                    <a href="<?php echo esc_url(admin_url('admin.php?page=flavor-red-social-moderacion')); ?>" class="button">
                        <?php esc_html_e('Moderar Contenido', 'flavor-chat-ia'); ?>
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
            <h1><?php esc_html_e('Publicaciones', 'flavor-chat-ia'); ?></h1>

            <table class="wp-list-table widefat fixed striped">
                <thead>
                    <tr>
                        <th><?php esc_html_e('ID', 'flavor-chat-ia'); ?></th>
                        <th><?php esc_html_e('Autor', 'flavor-chat-ia'); ?></th>
                        <th><?php esc_html_e('Contenido', 'flavor-chat-ia'); ?></th>
                        <th><?php esc_html_e('Estado', 'flavor-chat-ia'); ?></th>
                        <th><?php esc_html_e('Fecha', 'flavor-chat-ia'); ?></th>
                        <th><?php esc_html_e('Acciones', 'flavor-chat-ia'); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($publicaciones)): ?>
                        <tr>
                            <td colspan="6"><?php esc_html_e('No hay publicaciones.', 'flavor-chat-ia'); ?></td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($publicaciones as $publicacion): ?>
                            <tr>
                                <td><?php echo esc_html($publicacion->id); ?></td>
                                <td><?php echo esc_html($publicacion->autor_nombre ?: __('Usuario eliminado', 'flavor-chat-ia')); ?></td>
                                <td><?php echo esc_html(wp_trim_words(strip_tags($publicacion->contenido), 15)); ?></td>
                                <td>
                                    <span class="status-<?php echo esc_attr($publicacion->estado ?? 'publicado'); ?>">
                                        <?php echo esc_html(ucfirst($publicacion->estado ?? 'publicado')); ?>
                                    </span>
                                </td>
                                <td><?php echo esc_html(date_i18n(get_option('date_format'), strtotime($publicacion->fecha_creacion))); ?></td>
                                <td>
                                    <button class="button button-small" onclick="alert('Ver publicación #<?php echo esc_js($publicacion->id); ?>')">
                                        <?php esc_html_e('Ver', 'flavor-chat-ia'); ?>
                                    </button>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
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
            <h1><?php esc_html_e('Moderación de Contenido', 'flavor-chat-ia'); ?></h1>

            <div class="notice notice-info">
                <p><?php esc_html_e('Revisa y gestiona los reportes de contenido de la red social.', 'flavor-chat-ia'); ?></p>
            </div>

            <?php if (empty($reportes)): ?>
                <div class="notice notice-success">
                    <p><?php esc_html_e('No hay reportes pendientes de revisión.', 'flavor-chat-ia'); ?></p>
                </div>
            <?php else: ?>
                <table class="wp-list-table widefat fixed striped">
                    <thead>
                        <tr>
                            <th><?php esc_html_e('ID', 'flavor-chat-ia'); ?></th>
                            <th><?php esc_html_e('Tipo', 'flavor-chat-ia'); ?></th>
                            <th><?php esc_html_e('Motivo', 'flavor-chat-ia'); ?></th>
                            <th><?php esc_html_e('Reportado por', 'flavor-chat-ia'); ?></th>
                            <th><?php esc_html_e('Fecha', 'flavor-chat-ia'); ?></th>
                            <th><?php esc_html_e('Acciones', 'flavor-chat-ia'); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($reportes as $reporte): ?>
                            <tr>
                                <td><?php echo esc_html($reporte->id); ?></td>
                                <td><?php echo esc_html($reporte->tipo_contenido ?? 'publicacion'); ?></td>
                                <td><?php echo esc_html($reporte->motivo ?? '-'); ?></td>
                                <td><?php echo esc_html($reporte->reportador_nombre ?: __('Anónimo', 'flavor-chat-ia')); ?></td>
                                <td><?php echo esc_html(date_i18n(get_option('date_format'), strtotime($reporte->fecha_creacion))); ?></td>
                                <td>
                                    <button class="button button-small"><?php esc_html_e('Revisar', 'flavor-chat-ia'); ?></button>
                                    <button class="button button-small"><?php esc_html_e('Descartar', 'flavor-chat-ia'); ?></button>
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
                <span class="stat-etiqueta"><?php esc_html_e('Publicaciones', 'flavor-chat-ia'); ?></span>
            </div>
            <div class="stat-item">
                <span class="stat-numero"><?php echo esc_html($total_usuarios); ?></span>
                <span class="stat-etiqueta"><?php esc_html_e('Usuarios', 'flavor-chat-ia'); ?></span>
            </div>
            <div class="stat-item">
                <span class="stat-numero"><?php echo esc_html($publicaciones_semana); ?></span>
                <span class="stat-etiqueta"><?php esc_html_e('Esta semana', 'flavor-chat-ia'); ?></span>
            </div>
        </div>
        <p>
            <a href="<?php echo esc_url(admin_url('admin.php?page=flavor-red-social-dashboard')); ?>" class="button">
                <?php esc_html_e('Ver Dashboard', 'flavor-chat-ia'); ?>
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
}

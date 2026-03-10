<?php
/**
 * Integración de Posts de WordPress con la Red Social
 *
 * Permite compartir posts de WP en la red social del plugin,
 * con opción de federación a nodos públicos.
 *
 * @package FlavorChatIA
 * @since 4.2.0
 */

if (!defined('ABSPATH')) {
    exit;
}

class Flavor_WP_Social_Share {

    /**
     * Instancia singleton
     */
    private static $instance = null;

    /**
     * Tabla de publicaciones sociales
     */
    private $tabla_publicaciones;

    /**
     * Post types habilitados para compartir
     */
    private $post_types_habilitados = ['post', 'page'];

    /**
     * Obtiene la instancia singleton
     */
    public static function get_instance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Constructor
     */
    private function __construct() {
        global $wpdb;
        $this->tabla_publicaciones = $wpdb->prefix . 'flavor_social_publicaciones';

        // Permitir filtrar post types habilitados
        $this->post_types_habilitados = apply_filters(
            'flavor_social_share_post_types',
            $this->post_types_habilitados
        );

        $this->init_hooks();
    }

    /**
     * Inicializa hooks
     */
    private function init_hooks() {
        // Metabox en el editor
        add_action('add_meta_boxes', [$this, 'registrar_metabox']);

        // Guardar meta al guardar post
        add_action('save_post', [$this, 'guardar_meta'], 10, 2);

        // Hook al publicar post
        add_action('transition_post_status', [$this, 'al_cambiar_estado'], 10, 3);

        // Assets para el metabox
        add_action('admin_enqueue_scripts', [$this, 'cargar_assets_admin']);

        // Assets para frontend
        add_action('wp_enqueue_scripts', [$this, 'cargar_assets_frontend']);

        // AJAX para compartir posts existentes (admin y frontend)
        add_action('wp_ajax_flavor_compartir_post_social', [$this, 'ajax_compartir_post']);

        // Shortcode para botón de compartir en frontend
        add_shortcode('flavor_compartir_social', [$this, 'shortcode_boton_compartir']);

        // Filtro para añadir botón después del contenido
        add_filter('the_content', [$this, 'agregar_boton_compartir_contenido'], 99);
    }

    /**
     * Registra el metabox en el editor
     */
    public function registrar_metabox() {
        foreach ($this->post_types_habilitados as $post_type) {
            add_meta_box(
                'flavor_social_share',
                __('Compartir en Red Social', 'flavor-chat-ia'),
                [$this, 'renderizar_metabox'],
                $post_type,
                'side',
                'default'
            );
        }
    }

    /**
     * Renderiza el contenido del metabox
     */
    public function renderizar_metabox($post) {
        wp_nonce_field('flavor_social_share_nonce', 'flavor_social_share_nonce');

        $compartir_habilitado = get_post_meta($post->ID, '_flavor_compartir_social', true);
        $visibilidad = get_post_meta($post->ID, '_flavor_social_visibilidad', true) ?: 'publico';
        $mensaje_personalizado = get_post_meta($post->ID, '_flavor_social_mensaje', true);
        $federar = get_post_meta($post->ID, '_flavor_social_federar', true);
        $ya_compartido = get_post_meta($post->ID, '_flavor_social_compartido', true);
        $publicacion_social_id = get_post_meta($post->ID, '_flavor_social_publicacion_id', true);

        ?>
        <div class="flavor-social-share-metabox">
            <?php if ($ya_compartido && $publicacion_social_id): ?>
                <div class="flavor-social-compartido-notice" style="background:#d4edda;padding:8px;border-radius:4px;margin-bottom:10px;">
                    <span class="dashicons dashicons-yes-alt" style="color:#28a745;"></span>
                    <?php printf(
                        __('Compartido en red social (ID: %d)', 'flavor-chat-ia'),
                        $publicacion_social_id
                    ); ?>
                </div>
            <?php endif; ?>

            <p>
                <label>
                    <input type="checkbox"
                           name="flavor_compartir_social"
                           value="1"
                           <?php checked($compartir_habilitado, '1'); ?>
                           <?php disabled($ya_compartido); ?>>
                    <?php esc_html_e('Compartir al publicar', 'flavor-chat-ia'); ?>
                </label>
            </p>

            <p>
                <label for="flavor_social_visibilidad">
                    <?php esc_html_e('Visibilidad:', 'flavor-chat-ia'); ?>
                </label>
                <select name="flavor_social_visibilidad"
                        id="flavor_social_visibilidad"
                        style="width:100%;"
                        <?php disabled($ya_compartido); ?>>
                    <option value="publico" <?php selected($visibilidad, 'publico'); ?>>
                        <?php esc_html_e('Público', 'flavor-chat-ia'); ?>
                    </option>
                    <option value="seguidores" <?php selected($visibilidad, 'seguidores'); ?>>
                        <?php esc_html_e('Solo seguidores', 'flavor-chat-ia'); ?>
                    </option>
                    <option value="privado" <?php selected($visibilidad, 'privado'); ?>>
                        <?php esc_html_e('Privado', 'flavor-chat-ia'); ?>
                    </option>
                </select>
            </p>

            <p>
                <label for="flavor_social_mensaje">
                    <?php esc_html_e('Mensaje personalizado (opcional):', 'flavor-chat-ia'); ?>
                </label>
                <textarea name="flavor_social_mensaje"
                          id="flavor_social_mensaje"
                          rows="3"
                          style="width:100%;"
                          placeholder="<?php esc_attr_e('Añade un comentario...', 'flavor-chat-ia'); ?>"
                          <?php disabled($ya_compartido); ?>><?php echo esc_textarea($mensaje_personalizado); ?></textarea>
            </p>

            <p>
                <label>
                    <input type="checkbox"
                           name="flavor_social_federar"
                           value="1"
                           <?php checked($federar, '1'); ?>
                           <?php disabled($ya_compartido); ?>>
                    <?php esc_html_e('Federar a nodos públicos', 'flavor-chat-ia'); ?>
                </label>
                <span class="dashicons dashicons-info-outline"
                      title="<?php esc_attr_e('Comparte también en la red de nodos conectados', 'flavor-chat-ia'); ?>"
                      style="color:#666;cursor:help;"></span>
            </p>

            <?php if ($post->post_status === 'publish' && !$ya_compartido): ?>
                <hr style="margin:15px 0;">
                <button type="button"
                        class="button button-primary"
                        id="flavor-compartir-ahora"
                        data-post-id="<?php echo esc_attr($post->ID); ?>"
                        style="width:100%;">
                    <span class="dashicons dashicons-share" style="margin-top:4px;"></span>
                    <?php esc_html_e('Compartir ahora', 'flavor-chat-ia'); ?>
                </button>
            <?php endif; ?>
        </div>

        <style>
            .flavor-social-share-metabox label {
                display: block;
                margin-bottom: 5px;
            }
            .flavor-social-share-metabox p {
                margin-bottom: 12px;
            }
        </style>
        <?php
    }

    /**
     * Guarda los meta datos del metabox
     */
    public function guardar_meta($post_id, $post) {
        // Verificar nonce
        if (!isset($_POST['flavor_social_share_nonce']) ||
            !wp_verify_nonce($_POST['flavor_social_share_nonce'], 'flavor_social_share_nonce')) {
            return;
        }

        // Verificar autosave
        if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
            return;
        }

        // Verificar permisos
        if (!current_user_can('edit_post', $post_id)) {
            return;
        }

        // Verificar post type
        if (!in_array($post->post_type, $this->post_types_habilitados)) {
            return;
        }

        // No sobrescribir si ya fue compartido
        if (get_post_meta($post_id, '_flavor_social_compartido', true)) {
            return;
        }

        // Guardar opciones
        $compartir = isset($_POST['flavor_compartir_social']) ? '1' : '';
        $visibilidad = sanitize_text_field($_POST['flavor_social_visibilidad'] ?? 'publico');
        $mensaje = sanitize_textarea_field($_POST['flavor_social_mensaje'] ?? '');
        $federar = isset($_POST['flavor_social_federar']) ? '1' : '';

        update_post_meta($post_id, '_flavor_compartir_social', $compartir);
        update_post_meta($post_id, '_flavor_social_visibilidad', $visibilidad);
        update_post_meta($post_id, '_flavor_social_mensaje', $mensaje);
        update_post_meta($post_id, '_flavor_social_federar', $federar);
    }

    /**
     * Hook al cambiar estado del post
     */
    public function al_cambiar_estado($nuevo_estado, $estado_anterior, $post) {
        // Solo actuar cuando se publica
        if ($nuevo_estado !== 'publish') {
            return;
        }

        // Evitar re-publicaciones
        if ($estado_anterior === 'publish') {
            return;
        }

        // Verificar post type
        if (!in_array($post->post_type, $this->post_types_habilitados)) {
            return;
        }

        // Verificar si está habilitado compartir
        $compartir = get_post_meta($post->ID, '_flavor_compartir_social', true);
        if ($compartir !== '1') {
            return;
        }

        // Verificar si ya fue compartido
        if (get_post_meta($post->ID, '_flavor_social_compartido', true)) {
            return;
        }

        // Compartir en red social
        $this->compartir_post($post->ID);
    }

    /**
     * Comparte un post en la red social
     *
     * @param int $post_id ID del post
     * @return int|WP_Error ID de la publicación social o error
     */
    public function compartir_post($post_id) {
        global $wpdb;

        $post = get_post($post_id);
        if (!$post) {
            return new WP_Error('post_no_existe', __('El post no existe', 'flavor-chat-ia'));
        }

        // Obtener datos del post
        $titulo = get_the_title($post_id);
        $extracto = has_excerpt($post_id)
            ? get_the_excerpt($post_id)
            : wp_trim_words(strip_shortcodes($post->post_content), 30);
        $enlace = get_permalink($post_id);
        $imagen = get_the_post_thumbnail_url($post_id, 'large') ?: '';

        // Obtener opciones guardadas
        $visibilidad = get_post_meta($post_id, '_flavor_social_visibilidad', true) ?: 'publico';
        $mensaje = get_post_meta($post_id, '_flavor_social_mensaje', true);
        $federar = get_post_meta($post_id, '_flavor_social_federar', true) === '1';

        // Construir contenido
        $contenido = $mensaje ?: $titulo;
        if ($mensaje && $titulo) {
            $contenido = $mensaje . "\n\n📝 " . $titulo;
        }

        // Obtener autor (usar autor del post o admin actual)
        $autor_id = $post->post_author;
        if (!$autor_id) {
            $autor_id = get_current_user_id();
        }

        // Verificar que la tabla existe
        $tabla_existe = $wpdb->get_var("SHOW TABLES LIKE '{$this->tabla_publicaciones}'");
        if (!$tabla_existe) {
            return new WP_Error('tabla_no_existe', __('La tabla de red social no existe', 'flavor-chat-ia'));
        }

        // Insertar publicación social
        $resultado = $wpdb->insert(
            $this->tabla_publicaciones,
            [
                'usuario_id'         => $autor_id,
                'contenido'          => $contenido,
                'tipo'               => 'enlace',
                'enlace_url'         => $enlace,
                'enlace_titulo'      => $titulo,
                'enlace_descripcion' => $extracto,
                'enlace_imagen'      => $imagen,
                'privacidad'         => $visibilidad,
                'estado'             => 'publicado',
                'fecha_creacion'     => current_time('mysql'),
            ],
            ['%d', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s']
        );

        if ($resultado === false) {
            return new WP_Error('error_insercion', $wpdb->last_error);
        }

        $publicacion_id = $wpdb->insert_id;

        // Marcar como compartido
        update_post_meta($post_id, '_flavor_social_compartido', '1');
        update_post_meta($post_id, '_flavor_social_publicacion_id', $publicacion_id);
        update_post_meta($post_id, '_flavor_social_fecha_compartido', current_time('mysql'));

        // Procesar hashtags del contenido
        $this->procesar_hashtags($contenido, $publicacion_id);

        // Federar si está habilitado
        if ($federar) {
            $this->federar_publicacion($publicacion_id, $post_id);
        }

        // Hook para extensiones
        do_action('flavor_post_compartido_social', $publicacion_id, $post_id, $federar);

        return $publicacion_id;
    }

    /**
     * Procesa hashtags del contenido
     */
    private function procesar_hashtags($contenido, $publicacion_id) {
        global $wpdb;

        preg_match_all('/#(\w+)/u', $contenido, $matches);
        if (empty($matches[1])) {
            return;
        }

        $tabla_hashtags = $wpdb->prefix . 'flavor_social_hashtags';
        $tabla_relacion = $wpdb->prefix . 'flavor_social_publicaciones_hashtags';

        foreach ($matches[1] as $tag) {
            $tag = mb_strtolower($tag);

            // Insertar o actualizar hashtag
            $wpdb->query($wpdb->prepare(
                "INSERT INTO {$tabla_hashtags} (nombre, usos_count) VALUES (%s, 1)
                 ON DUPLICATE KEY UPDATE usos_count = usos_count + 1",
                $tag
            ));

            $hashtag_id = $wpdb->get_var($wpdb->prepare(
                "SELECT id FROM {$tabla_hashtags} WHERE nombre = %s",
                $tag
            ));

            if ($hashtag_id) {
                $wpdb->insert($tabla_relacion, [
                    'publicacion_id' => $publicacion_id,
                    'hashtag_id'     => $hashtag_id,
                ], ['%d', '%d']);
            }
        }
    }

    /**
     * Federa la publicación a nodos conectados
     */
    private function federar_publicacion($publicacion_id, $post_id) {
        global $wpdb;

        // Obtener nodos públicos activos
        $tabla_nodos = $wpdb->prefix . 'flavor_network_nodes';
        $nodos = $wpdb->get_results(
            "SELECT * FROM {$tabla_nodos}
             WHERE activo = 1 AND es_nodo_local = 0 AND estado = 'conectado'"
        );

        if (empty($nodos)) {
            return;
        }

        // Obtener datos de la publicación
        $publicacion = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$this->tabla_publicaciones} WHERE id = %d",
            $publicacion_id
        ));

        if (!$publicacion) {
            return;
        }

        // Preparar payload para federación
        $payload = [
            'tipo'        => 'publicacion_compartida',
            'origen'      => home_url(),
            'publicacion' => [
                'contenido'          => $publicacion->contenido,
                'tipo'               => $publicacion->tipo,
                'enlace_url'         => $publicacion->enlace_url,
                'enlace_titulo'      => $publicacion->enlace_titulo,
                'enlace_descripcion' => $publicacion->enlace_descripcion,
                'enlace_imagen'      => $publicacion->enlace_imagen,
                'autor_nombre'       => get_the_author_meta('display_name', $publicacion->usuario_id),
                'fecha'              => $publicacion->fecha_creacion,
            ],
        ];

        // Enviar a cada nodo
        foreach ($nodos as $nodo) {
            $this->enviar_a_nodo($nodo, $payload);
        }

        // Marcar como federado
        update_post_meta($post_id, '_flavor_social_federado', '1');
        update_post_meta($post_id, '_flavor_social_nodos_count', count($nodos));
    }

    /**
     * Envía publicación a un nodo
     */
    private function enviar_a_nodo($nodo, $payload) {
        $url = trailingslashit($nodo->api_url) . 'federation/receive';

        $response = wp_remote_post($url, [
            'timeout' => 15,
            'headers' => [
                'Content-Type'  => 'application/json',
                'X-Node-Token'  => $nodo->token ?? '',
                'X-Origin-Node' => home_url(),
            ],
            'body' => wp_json_encode($payload),
        ]);

        if (is_wp_error($response)) {
            error_log("Flavor Social: Error federando a {$nodo->nombre}: " . $response->get_error_message());
        }

        return $response;
    }

    /**
     * AJAX: Compartir post existente
     */
    public function ajax_compartir_post() {
        check_ajax_referer('flavor_social_share_ajax', 'nonce');

        if (!current_user_can('edit_posts')) {
            wp_send_json_error(['message' => __('Sin permisos', 'flavor-chat-ia')]);
        }

        $post_id = intval($_POST['post_id'] ?? 0);
        if (!$post_id) {
            wp_send_json_error(['message' => __('ID de post inválido', 'flavor-chat-ia')]);
        }

        // Verificar si ya fue compartido
        if (get_post_meta($post_id, '_flavor_social_compartido', true)) {
            wp_send_json_error(['message' => __('Este post ya fue compartido', 'flavor-chat-ia')]);
        }

        // Guardar opciones desde AJAX
        $visibilidad = sanitize_text_field($_POST['visibilidad'] ?? 'publico');
        $mensaje = sanitize_textarea_field($_POST['mensaje'] ?? '');
        $federar = isset($_POST['federar']) && $_POST['federar'] === '1';

        update_post_meta($post_id, '_flavor_social_visibilidad', $visibilidad);
        update_post_meta($post_id, '_flavor_social_mensaje', $mensaje);
        update_post_meta($post_id, '_flavor_social_federar', $federar ? '1' : '');

        // Procesar integraciones de módulos si se enviaron
        if (!empty($_POST['integraciones']) && is_array($_POST['integraciones'])) {
            $integraciones = [];
            foreach ($_POST['integraciones'] as $modulo => $config) {
                $modulo = sanitize_key($modulo);

                // Soportar formato simple (valor = '1') y formato completo (array con enabled y elemento_id)
                if (is_array($config)) {
                    if (!empty($config['enabled'])) {
                        $integraciones[$modulo] = [
                            'enabled'     => true,
                            'elemento_id' => sanitize_text_field($config['elemento_id'] ?? ''),
                        ];
                    }
                } elseif ($config === '1') {
                    $integraciones[$modulo] = [
                        'enabled'     => true,
                        'elemento_id' => '',
                    ];
                }
            }
            if (!empty($integraciones)) {
                update_post_meta($post_id, '_flavor_integraciones_modulos', $integraciones);
            }
        }

        // Compartir
        $resultado = $this->compartir_post($post_id);

        if (is_wp_error($resultado)) {
            wp_send_json_error(['message' => $resultado->get_error_message()]);
        }

        wp_send_json_success([
            'message'        => __('Post compartido en la red social', 'flavor-chat-ia'),
            'publicacion_id' => $resultado,
        ]);
    }

    /**
     * Carga assets en admin
     */
    public function cargar_assets_admin($hook) {
        if (!in_array($hook, ['post.php', 'post-new.php'])) {
            return;
        }

        global $post;
        if (!$post || !in_array($post->post_type, $this->post_types_habilitados)) {
            return;
        }

        wp_enqueue_script(
            'flavor-social-share-admin',
            FLAVOR_CHAT_IA_URL . 'assets/js/wp-social-share-admin.js',
            ['jquery'],
            FLAVOR_CHAT_IA_VERSION,
            true
        );

        wp_localize_script('flavor-social-share-admin', 'flavorSocialShare', [
            'ajaxUrl' => admin_url('admin-ajax.php'),
            'nonce'   => wp_create_nonce('flavor_social_share_ajax'),
            'i18n'    => [
                'compartiendo' => __('Compartiendo...', 'flavor-chat-ia'),
                'compartido'   => __('Compartido!', 'flavor-chat-ia'),
                'error'        => __('Error al compartir', 'flavor-chat-ia'),
            ],
        ]);
    }

    /**
     * Carga assets en frontend
     */
    public function cargar_assets_frontend() {
        // Solo en singular de post types habilitados
        if (!is_singular($this->post_types_habilitados)) {
            return;
        }

        // Solo para usuarios logueados
        if (!is_user_logged_in()) {
            return;
        }

        wp_enqueue_script(
            'flavor-social-share-frontend',
            FLAVOR_CHAT_IA_URL . 'assets/js/wp-social-share-frontend.js',
            ['jquery'],
            FLAVOR_CHAT_IA_VERSION,
            true
        );

        wp_localize_script('flavor-social-share-frontend', 'flavorSocialShareFront', [
            'ajaxUrl' => admin_url('admin-ajax.php'),
            'nonce'   => wp_create_nonce('flavor_social_share_ajax'),
            'i18n'    => [
                'compartiendo' => __('Compartiendo...', 'flavor-chat-ia'),
                'compartido'   => __('¡Compartido en la comunidad!', 'flavor-chat-ia'),
                'error'        => __('Error al compartir', 'flavor-chat-ia'),
                'btnCompartir' => __('Compartir', 'flavor-chat-ia'),
            ],
        ]);
    }

    /**
     * Shortcode para botón de compartir
     */
    public function shortcode_boton_compartir($atts) {
        $atts = shortcode_atts([
            'post_id' => get_the_ID(),
            'texto'   => __('Compartir en comunidad', 'flavor-chat-ia'),
            'class'   => 'flavor-btn-compartir-social',
        ], $atts);

        if (!is_user_logged_in()) {
            return '';
        }

        $post_id = intval($atts['post_id']);
        $ya_compartido = get_post_meta($post_id, '_flavor_social_compartido', true);

        if ($ya_compartido) {
            return '<span class="flavor-ya-compartido">' .
                   '<span class="dashicons dashicons-yes"></span> ' .
                   __('Compartido', 'flavor-chat-ia') .
                   '</span>';
        }

        return sprintf(
            '<button type="button" class="%s" data-post-id="%d">
                <span class="dashicons dashicons-share"></span> %s
            </button>',
            esc_attr($atts['class']),
            $post_id,
            esc_html($atts['texto'])
        );
    }

    /**
     * Agrega botón de compartir después del contenido
     */
    public function agregar_boton_compartir_contenido($content) {
        // Solo en single de post types habilitados
        if (!is_singular($this->post_types_habilitados)) {
            return $content;
        }

        // Verificar si está habilitado globalmente
        $mostrar_boton = apply_filters('flavor_social_mostrar_boton_compartir', true);
        if (!$mostrar_boton) {
            return $content;
        }

        // Solo para usuarios logueados
        if (!is_user_logged_in()) {
            return $content;
        }

        $post_id = get_the_ID();
        $ya_compartido = get_post_meta($post_id, '_flavor_social_compartido', true);

        if ($ya_compartido) {
            $html = '<div class="flavor-social-share-wrapper flavor-ya-compartido-wrapper">';
            $html .= '<span class="flavor-ya-compartido">';
            $html .= '<span class="dashicons dashicons-yes"></span> ';
            $html .= __('Compartido en la comunidad', 'flavor-chat-ia');
            $html .= '</span>';
            $html .= '</div>';
            return $content . $html;
        }

        $html = '<div class="flavor-social-share-wrapper">';
        $html .= '<p class="flavor-share-intro">' . __('¿Te ha gustado? Compártelo con la comunidad', 'flavor-chat-ia') . '</p>';
        $html .= '<button type="button" class="flavor-btn-compartir-social" data-post-id="' . esc_attr($post_id) . '">';
        $html .= '<span class="dashicons dashicons-share"></span> ';
        $html .= __('Compartir en comunidad', 'flavor-chat-ia');
        $html .= '</button>';
        $html .= '</div>';

        // Añadir modal
        $html .= $this->renderizar_modal_compartir($post_id);

        return $content . $html;
    }

    /**
     * Renderiza el modal de compartir
     */
    private function renderizar_modal_compartir($post_id) {
        $post = get_post($post_id);
        $titulo = get_the_title($post_id);
        $imagen = get_the_post_thumbnail_url($post_id, 'medium') ?: '';
        $extracto = has_excerpt($post_id)
            ? get_the_excerpt($post_id)
            : wp_trim_words(strip_shortcodes($post->post_content), 20);

        ob_start();
        ?>
        <div id="flavor-modal-compartir" class="flavor-modal" style="display:none;">
            <div class="flavor-modal-overlay"></div>
            <div class="flavor-modal-content">
                <button type="button" class="flavor-modal-close">&times;</button>

                <h3 class="flavor-modal-title">
                    <span class="dashicons dashicons-share"></span>
                    <?php esc_html_e('Compartir en la comunidad', 'flavor-chat-ia'); ?>
                </h3>

                <!-- Preview del post -->
                <div class="flavor-share-preview">
                    <?php if ($imagen): ?>
                        <img src="<?php echo esc_url($imagen); ?>" alt="" class="flavor-share-preview-img">
                    <?php endif; ?>
                    <div class="flavor-share-preview-content">
                        <strong><?php echo esc_html($titulo); ?></strong>
                        <p><?php echo esc_html($extracto); ?></p>
                    </div>
                </div>

                <form id="flavor-form-compartir" data-post-id="<?php echo esc_attr($post_id); ?>">
                    <!-- Mensaje personalizado -->
                    <div class="flavor-form-group">
                        <label for="flavor-share-mensaje">
                            <?php esc_html_e('Añade un comentario (opcional)', 'flavor-chat-ia'); ?>
                        </label>
                        <textarea id="flavor-share-mensaje"
                                  name="mensaje"
                                  rows="3"
                                  placeholder="<?php esc_attr_e('¿Qué opinas sobre esto?', 'flavor-chat-ia'); ?>"></textarea>
                    </div>

                    <!-- Visibilidad -->
                    <div class="flavor-form-group">
                        <label><?php esc_html_e('¿Quién puede verlo?', 'flavor-chat-ia'); ?></label>
                        <div class="flavor-visibility-options">
                            <label class="flavor-radio-card selected">
                                <input type="radio" name="visibilidad" value="publico" checked>
                                <span class="dashicons dashicons-admin-site"></span>
                                <span class="flavor-radio-label"><?php esc_html_e('Público', 'flavor-chat-ia'); ?></span>
                                <small><?php esc_html_e('Todos pueden ver', 'flavor-chat-ia'); ?></small>
                            </label>
                            <label class="flavor-radio-card">
                                <input type="radio" name="visibilidad" value="seguidores">
                                <span class="dashicons dashicons-groups"></span>
                                <span class="flavor-radio-label"><?php esc_html_e('Seguidores', 'flavor-chat-ia'); ?></span>
                                <small><?php esc_html_e('Solo mis seguidores', 'flavor-chat-ia'); ?></small>
                            </label>
                            <label class="flavor-radio-card">
                                <input type="radio" name="visibilidad" value="privado">
                                <span class="dashicons dashicons-lock"></span>
                                <span class="flavor-radio-label"><?php esc_html_e('Privado', 'flavor-chat-ia'); ?></span>
                                <small><?php esc_html_e('Solo yo', 'flavor-chat-ia'); ?></small>
                            </label>
                        </div>
                    </div>

                    <!-- Hook para integraciones adicionales de módulos -->
                    <?php do_action('flavor_modal_compartir_opciones', $post_id); ?>

                    <!-- Federación -->
                    <div class="flavor-form-group flavor-federar-group">
                        <label class="flavor-checkbox-inline">
                            <input type="checkbox" name="federar" value="1">
                            <span class="dashicons dashicons-networking"></span>
                            <?php esc_html_e('Compartir también en la red de comunidades conectadas', 'flavor-chat-ia'); ?>
                        </label>
                    </div>

                    <!-- Botones -->
                    <div class="flavor-modal-actions">
                        <button type="button" class="flavor-btn-cancelar">
                            <?php esc_html_e('Cancelar', 'flavor-chat-ia'); ?>
                        </button>
                        <button type="submit" class="flavor-btn-compartir-submit">
                            <span class="dashicons dashicons-share"></span>
                            <?php esc_html_e('Compartir', 'flavor-chat-ia'); ?>
                        </button>
                    </div>
                </form>
            </div>
        </div>
        <?php
        return ob_get_clean();
    }
}

// Inicializar
add_action('plugins_loaded', function() {
    // Solo si el módulo de red social está activo
    // Usar función centralizada si está disponible
    $modulo_activo = false;

    if (function_exists('flavor_is_module_active')) {
        $modulo_activo = flavor_is_module_active('red_social') || flavor_is_module_active('red-social');
    } else {
        // Fallback: verificar en ambas opciones
        $settings = get_option('flavor_chat_ia_settings', []);
        $active_modules = $settings['active_modules'] ?? [];

        $modulos_legacy = get_option('flavor_active_modules', []);
        if (!empty($modulos_legacy)) {
            $active_modules = array_unique(array_merge($active_modules, $modulos_legacy));
        }

        $modulo_activo = in_array('red_social', $active_modules, true)
                      || in_array('red-social', $active_modules, true);
    }

    if ($modulo_activo) {
        Flavor_WP_Social_Share::get_instance();
    }
}, 20);

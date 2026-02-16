<?php
/**
 * Módulo Economía del Don
 *
 * Sistema de donaciones y regalos sin expectativa de retorno.
 * Facilita ofrecer y recibir sin contabilidad ni intercambio.
 *
 * Valoración de Conciencia: 94/100
 * - conciencia_fundamental: 0.25 (Dar por el placer de dar)
 * - abundancia_organizable: 0.30 (Lo que sobra para quien lo necesita)
 * - interdependencia_radical: 0.20 (Red de apoyo incondicional)
 * - madurez_ciclica: 0.10 (Flujo natural de dar/recibir)
 * - valor_intrinseco: 0.15 (El valor está en el acto de dar)
 *
 * @package FlavorChatIA
 */

if (!defined('ABSPATH')) {
    exit;
}

class Flavor_Chat_Economia_Don_Module extends Flavor_Chat_Module_Base {

    /**
     * Categorías de dones
     */
    const CATEGORIAS_DON = [
        'objetos' => [
            'nombre' => 'Objetos y cosas',
            'icono' => 'dashicons-archive',
            'color' => '#3498db',
            'descripcion' => 'Ropa, muebles, electrodomésticos, juguetes...',
        ],
        'alimentos' => [
            'nombre' => 'Alimentos',
            'icono' => 'dashicons-carrot',
            'color' => '#27ae60',
            'descripcion' => 'Comida casera, excedentes de huerta, conservas...',
        ],
        'servicios' => [
            'nombre' => 'Servicios y habilidades',
            'icono' => 'dashicons-admin-tools',
            'color' => '#9b59b6',
            'descripcion' => 'Clases, reparaciones, traducciones, diseño...',
        ],
        'tiempo' => [
            'nombre' => 'Tiempo y compañía',
            'icono' => 'dashicons-clock',
            'color' => '#e74c3c',
            'descripcion' => 'Acompañamiento, escucha, paseos, cuidados...',
        ],
        'conocimiento' => [
            'nombre' => 'Conocimiento',
            'icono' => 'dashicons-book',
            'color' => '#f39c12',
            'descripcion' => 'Tutorías, mentorías, consejos, experiencia...',
        ],
        'espacios' => [
            'nombre' => 'Espacios',
            'icono' => 'dashicons-admin-home',
            'color' => '#1abc9c',
            'descripcion' => 'Uso temporal de espacios, alojamiento, local...',
        ],
    ];

    /**
     * Estados del don
     */
    const ESTADOS_DON = [
        'disponible' => ['nombre' => 'Disponible', 'color' => '#27ae60'],
        'reservado' => ['nombre' => 'Reservado', 'color' => '#f39c12'],
        'entregado' => ['nombre' => 'Entregado', 'color' => '#3498db'],
        'recibido' => ['nombre' => 'Recibido con gratitud', 'color' => '#9b59b6'],
    ];

    /**
     * Constructor
     */
    public function __construct() {
        $this->id = 'economia_don';
        $this->name = __('Economía del Don', 'flavor-chat-ia');
        $this->description = __('Dar y recibir sin esperar nada a cambio.', 'flavor-chat-ia');
        $this->icon = 'dashicons-heart';
        $this->color = '#e74c3c';

        parent::__construct();
    }

    /**
     * {@inheritdoc}
     */
    public function can_activate() {
        return true;
    }

    /**
     * {@inheritdoc}
     */
    protected function get_default_settings() {
        return [
            'categorias_habilitadas' => array_keys(self::CATEGORIAS_DON),
            'permitir_anonimato' => true,
            'notificar_nuevos_dones' => true,
            'mostrar_mapa' => true,
            'radio_busqueda_km' => 10,
            'mostrar_en_dashboard' => true,
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function init() {
        // Custom Post Types
        add_action('init', [$this, 'registrar_post_types']);
        add_action('init', [$this, 'registrar_taxonomias']);

        // Meta boxes
        add_action('add_meta_boxes', [$this, 'registrar_meta_boxes']);
        add_action('save_post_ed_don', [$this, 'guardar_meta_don']);

        // Shortcodes
        $this->register_shortcodes();

        // AJAX
        add_action('wp_ajax_ed_solicitar_don', [$this, 'ajax_solicitar_don']);
        add_action('wp_ajax_ed_confirmar_entrega', [$this, 'ajax_confirmar_entrega']);
        add_action('wp_ajax_ed_agradecer', [$this, 'ajax_agradecer']);
        add_action('wp_ajax_ed_publicar_don', [$this, 'ajax_publicar_don']);

        // Dashboard widget
        add_action('flavor_register_dashboard_widgets', [$this, 'register_dashboard_widget']);

        // Assets
        add_action('wp_enqueue_scripts', [$this, 'enqueue_assets']);
    }

    /**
     * Registra shortcodes del módulo
     */
    public function register_shortcodes() {
        add_shortcode('economia_don', [$this, 'shortcode_listado']);
        add_shortcode('mis_dones', [$this, 'shortcode_mis_dones']);
        add_shortcode('ofrecer_don', [$this, 'shortcode_ofrecer']);
        add_shortcode('muro_gratitud', [$this, 'shortcode_muro_gratitud']);
    }

    /**
     * Registra Custom Post Types
     */
    public function registrar_post_types() {
        // CPT: Dones (objetos/servicios ofrecidos)
        register_post_type('ed_don', [
            'labels' => [
                'name' => __('Dones', 'flavor-chat-ia'),
                'singular_name' => __('Don', 'flavor-chat-ia'),
                'add_new' => __('Ofrecer Don', 'flavor-chat-ia'),
                'add_new_item' => __('Ofrecer Nuevo Don', 'flavor-chat-ia'),
                'edit_item' => __('Editar Don', 'flavor-chat-ia'),
            ],
            'public' => true,
            'has_archive' => true,
            'supports' => ['title', 'editor', 'thumbnail'],
            'menu_icon' => 'dashicons-heart',
            'show_in_rest' => true,
            'rewrite' => ['slug' => 'economia-don'],
        ]);

        // CPT: Solicitudes de don
        register_post_type('ed_solicitud', [
            'labels' => [
                'name' => __('Solicitudes', 'flavor-chat-ia'),
                'singular_name' => __('Solicitud', 'flavor-chat-ia'),
            ],
            'public' => false,
            'show_ui' => true,
            'show_in_menu' => 'edit.php?post_type=ed_don',
            'supports' => ['title'],
        ]);

        // CPT: Gratitudes
        register_post_type('ed_gratitud', [
            'labels' => [
                'name' => __('Gratitudes', 'flavor-chat-ia'),
                'singular_name' => __('Gratitud', 'flavor-chat-ia'),
            ],
            'public' => true,
            'has_archive' => true,
            'supports' => ['title', 'editor'],
            'menu_icon' => 'dashicons-smiley',
            'rewrite' => ['slug' => 'muro-gratitud'],
        ]);
    }

    /**
     * Registra taxonomías
     */
    public function registrar_taxonomias() {
        register_taxonomy('ed_categoria', 'ed_don', [
            'labels' => [
                'name' => __('Categorías de Don', 'flavor-chat-ia'),
                'singular_name' => __('Categoría', 'flavor-chat-ia'),
            ],
            'hierarchical' => true,
            'show_in_rest' => true,
        ]);
    }

    /**
     * Registra meta boxes
     */
    public function registrar_meta_boxes() {
        add_meta_box(
            'ed_don_datos',
            __('Datos del Don', 'flavor-chat-ia'),
            [$this, 'render_meta_box_don'],
            'ed_don',
            'normal',
            'high'
        );
    }

    /**
     * Renderiza meta box del don
     */
    public function render_meta_box_don($post) {
        wp_nonce_field('ed_don_nonce', 'ed_don_nonce_field');

        $categoria = get_post_meta($post->ID, '_ed_categoria', true);
        $estado = get_post_meta($post->ID, '_ed_estado', true) ?: 'disponible';
        $ubicacion = get_post_meta($post->ID, '_ed_ubicacion', true);
        $anonimo = get_post_meta($post->ID, '_ed_anonimo', true);
        $disponibilidad = get_post_meta($post->ID, '_ed_disponibilidad', true);
        $condiciones = get_post_meta($post->ID, '_ed_condiciones', true);
        ?>
        <table class="form-table">
            <tr>
                <th><label for="ed_categoria"><?php esc_html_e('Categoría', 'flavor-chat-ia'); ?></label></th>
                <td>
                    <select name="ed_categoria" id="ed_categoria" class="regular-text">
                        <?php foreach (self::CATEGORIAS_DON as $id => $data) : ?>
                        <option value="<?php echo esc_attr($id); ?>" <?php selected($categoria, $id); ?>>
                            <?php echo esc_html($data['nombre']); ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                </td>
            </tr>
            <tr>
                <th><label for="ed_estado"><?php esc_html_e('Estado', 'flavor-chat-ia'); ?></label></th>
                <td>
                    <select name="ed_estado" id="ed_estado">
                        <?php foreach (self::ESTADOS_DON as $id => $data) : ?>
                        <option value="<?php echo esc_attr($id); ?>" <?php selected($estado, $id); ?>>
                            <?php echo esc_html($data['nombre']); ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                </td>
            </tr>
            <tr>
                <th><label for="ed_ubicacion"><?php esc_html_e('Ubicación/Zona', 'flavor-chat-ia'); ?></label></th>
                <td>
                    <input type="text" name="ed_ubicacion" id="ed_ubicacion"
                           value="<?php echo esc_attr($ubicacion); ?>" class="regular-text"
                           placeholder="<?php esc_attr_e('Ej: Centro, Barrio Norte...', 'flavor-chat-ia'); ?>">
                </td>
            </tr>
            <tr>
                <th><label for="ed_disponibilidad"><?php esc_html_e('Disponibilidad', 'flavor-chat-ia'); ?></label></th>
                <td>
                    <input type="text" name="ed_disponibilidad" id="ed_disponibilidad"
                           value="<?php echo esc_attr($disponibilidad); ?>" class="regular-text"
                           placeholder="<?php esc_attr_e('Ej: Tardes de 17-20h', 'flavor-chat-ia'); ?>">
                </td>
            </tr>
            <tr>
                <th><label for="ed_condiciones"><?php esc_html_e('Condiciones (opcional)', 'flavor-chat-ia'); ?></label></th>
                <td>
                    <textarea name="ed_condiciones" id="ed_condiciones" rows="2" class="large-text"
                              placeholder="<?php esc_attr_e('Ej: Recoger en mi domicilio', 'flavor-chat-ia'); ?>"><?php echo esc_textarea($condiciones); ?></textarea>
                    <p class="description"><?php esc_html_e('Requisitos para recibir el don (no monetarios)', 'flavor-chat-ia'); ?></p>
                </td>
            </tr>
            <tr>
                <th><label for="ed_anonimo"><?php esc_html_e('Donación anónima', 'flavor-chat-ia'); ?></label></th>
                <td>
                    <label>
                        <input type="checkbox" name="ed_anonimo" id="ed_anonimo" value="1"
                               <?php checked($anonimo, '1'); ?>>
                        <?php esc_html_e('No mostrar mi nombre públicamente', 'flavor-chat-ia'); ?>
                    </label>
                </td>
            </tr>
        </table>
        <?php
    }

    /**
     * Guarda meta del don
     */
    public function guardar_meta_don($post_id) {
        if (!isset($_POST['ed_don_nonce_field']) ||
            !wp_verify_nonce($_POST['ed_don_nonce_field'], 'ed_don_nonce')) {
            return;
        }

        if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) return;
        if (!current_user_can('edit_post', $post_id)) return;

        $campos = ['categoria', 'estado', 'ubicacion', 'disponibilidad', 'condiciones'];
        foreach ($campos as $campo) {
            if (isset($_POST['ed_' . $campo])) {
                update_post_meta($post_id, '_ed_' . $campo, sanitize_text_field($_POST['ed_' . $campo]));
            }
        }

        update_post_meta($post_id, '_ed_anonimo', isset($_POST['ed_anonimo']) ? '1' : '0');
    }

    /**
     * AJAX: Solicitar un don
     */
    public function ajax_solicitar_don() {
        check_ajax_referer('ed_nonce', 'nonce');

        $don_id = absint($_POST['don_id'] ?? 0);
        $mensaje = sanitize_textarea_field($_POST['mensaje'] ?? '');
        $user_id = get_current_user_id();

        if (!$don_id || !$user_id) {
            wp_send_json_error(['message' => __('Datos inválidos', 'flavor-chat-ia')]);
        }

        // Verificar que el don está disponible
        $estado = get_post_meta($don_id, '_ed_estado', true);
        if ($estado !== 'disponible') {
            wp_send_json_error(['message' => __('Este don ya no está disponible', 'flavor-chat-ia')]);
        }

        // Crear solicitud
        $solicitud_id = wp_insert_post([
            'post_type' => 'ed_solicitud',
            'post_title' => sprintf(
                __('Solicitud de %s', 'flavor-chat-ia'),
                get_userdata($user_id)->display_name
            ),
            'post_status' => 'publish',
            'post_author' => $user_id,
        ]);

        if ($solicitud_id) {
            update_post_meta($solicitud_id, '_ed_don_id', $don_id);
            update_post_meta($solicitud_id, '_ed_mensaje', $mensaje);
            update_post_meta($solicitud_id, '_ed_estado', 'pendiente');

            // Notificar al donante
            $donante_id = get_post_field('post_author', $don_id);
            $this->notificar_donante($donante_id, $don_id, $user_id);

            // Marcar don como reservado
            update_post_meta($don_id, '_ed_estado', 'reservado');
            update_post_meta($don_id, '_ed_receptor_id', $user_id);

            wp_send_json_success([
                'message' => __('¡Solicitud enviada! El donante se pondrá en contacto contigo.', 'flavor-chat-ia'),
            ]);
        }

        wp_send_json_error(['message' => __('Error al procesar la solicitud', 'flavor-chat-ia')]);
    }

    /**
     * AJAX: Confirmar entrega
     */
    public function ajax_confirmar_entrega() {
        check_ajax_referer('ed_nonce', 'nonce');

        $don_id = absint($_POST['don_id'] ?? 0);
        $user_id = get_current_user_id();

        if (!$don_id || !$user_id) {
            wp_send_json_error(['message' => __('Datos inválidos', 'flavor-chat-ia')]);
        }

        // Verificar que es el donante
        $donante_id = get_post_field('post_author', $don_id);
        if ($donante_id != $user_id) {
            wp_send_json_error(['message' => __('No tienes permiso', 'flavor-chat-ia')]);
        }

        update_post_meta($don_id, '_ed_estado', 'entregado');
        update_post_meta($don_id, '_ed_fecha_entrega', current_time('mysql'));

        // Actualizar estadísticas del donante
        $dones_dados = absint(get_user_meta($user_id, '_ed_dones_dados', true));
        update_user_meta($user_id, '_ed_dones_dados', $dones_dados + 1);

        // Notificar al receptor para que agradezca
        $receptor_id = get_post_meta($don_id, '_ed_receptor_id', true);
        if ($receptor_id) {
            $this->notificar_receptor_entrega($receptor_id, $don_id);
        }

        wp_send_json_success([
            'message' => __('¡Entrega confirmada! Gracias por tu generosidad.', 'flavor-chat-ia'),
        ]);
    }

    /**
     * AJAX: Agradecer un don
     */
    public function ajax_agradecer() {
        check_ajax_referer('ed_nonce', 'nonce');

        $don_id = absint($_POST['don_id'] ?? 0);
        $mensaje = sanitize_textarea_field($_POST['mensaje'] ?? '');
        $user_id = get_current_user_id();

        if (!$don_id || !$user_id || !$mensaje) {
            wp_send_json_error(['message' => __('Datos inválidos', 'flavor-chat-ia')]);
        }

        // Verificar que es el receptor
        $receptor_id = get_post_meta($don_id, '_ed_receptor_id', true);
        if ($receptor_id != $user_id) {
            wp_send_json_error(['message' => __('No tienes permiso', 'flavor-chat-ia')]);
        }

        // Crear gratitud
        $gratitud_id = wp_insert_post([
            'post_type' => 'ed_gratitud',
            'post_title' => sprintf(__('Gratitud por "%s"', 'flavor-chat-ia'), get_the_title($don_id)),
            'post_content' => $mensaje,
            'post_status' => 'publish',
            'post_author' => $user_id,
        ]);

        if ($gratitud_id) {
            update_post_meta($gratitud_id, '_ed_don_id', $don_id);
            update_post_meta($don_id, '_ed_estado', 'recibido');
            update_post_meta($don_id, '_ed_gratitud_id', $gratitud_id);

            // Actualizar estadísticas del receptor
            $dones_recibidos = absint(get_user_meta($user_id, '_ed_dones_recibidos', true));
            update_user_meta($user_id, '_ed_dones_recibidos', $dones_recibidos + 1);

            wp_send_json_success([
                'message' => __('¡Gracias por tu gratitud! Se ha publicado en el muro.', 'flavor-chat-ia'),
            ]);
        }

        wp_send_json_error(['message' => __('Error al publicar gratitud', 'flavor-chat-ia')]);
    }

    /**
     * AJAX: Publicar nuevo don
     */
    public function ajax_publicar_don() {
        check_ajax_referer('ed_nonce', 'nonce');

        if (!is_user_logged_in()) {
            wp_send_json_error(['message' => __('Debes iniciar sesión', 'flavor-chat-ia')]);
        }

        $titulo = sanitize_text_field($_POST['titulo'] ?? '');
        $descripcion = sanitize_textarea_field($_POST['descripcion'] ?? '');
        $categoria = sanitize_text_field($_POST['categoria'] ?? 'objetos');
        $ubicacion = sanitize_text_field($_POST['ubicacion'] ?? '');
        $disponibilidad = sanitize_text_field($_POST['disponibilidad'] ?? '');
        $anonimo = isset($_POST['anonimo']);
        $user_id = get_current_user_id();

        if (!$titulo) {
            wp_send_json_error(['message' => __('El título es obligatorio', 'flavor-chat-ia')]);
        }

        $don_id = wp_insert_post([
            'post_type' => 'ed_don',
            'post_title' => $titulo,
            'post_content' => $descripcion,
            'post_status' => 'publish',
            'post_author' => $user_id,
        ]);

        if ($don_id) {
            update_post_meta($don_id, '_ed_categoria', $categoria);
            update_post_meta($don_id, '_ed_estado', 'disponible');
            update_post_meta($don_id, '_ed_ubicacion', $ubicacion);
            update_post_meta($don_id, '_ed_disponibilidad', $disponibilidad);
            update_post_meta($don_id, '_ed_anonimo', $anonimo ? '1' : '0');

            wp_send_json_success([
                'message' => __('¡Don publicado! Gracias por tu generosidad.', 'flavor-chat-ia'),
                'don_id' => $don_id,
                'url' => get_permalink($don_id),
            ]);
        }

        wp_send_json_error(['message' => __('Error al publicar el don', 'flavor-chat-ia')]);
    }

    /**
     * Notifica al donante de una solicitud
     */
    private function notificar_donante($donante_id, $don_id, $solicitante_id) {
        if (!class_exists('Flavor_Notification_Center')) {
            return;
        }

        $solicitante = get_userdata($solicitante_id);
        $nc = Flavor_Notification_Center::get_instance();

        $nc->send(
            $donante_id,
            __('Alguien quiere recibir tu don', 'flavor-chat-ia'),
            sprintf(
                __('%s ha solicitado "%s"', 'flavor-chat-ia'),
                $solicitante->display_name,
                get_the_title($don_id)
            ),
            [
                'module_id' => $this->id,
                'type' => 'success',
                'link' => get_permalink($don_id),
            ]
        );
    }

    /**
     * Notifica al receptor que se ha entregado
     */
    private function notificar_receptor_entrega($receptor_id, $don_id) {
        if (!class_exists('Flavor_Notification_Center')) {
            return;
        }

        $nc = Flavor_Notification_Center::get_instance();

        $nc->send(
            $receptor_id,
            __('¡Has recibido un don!', 'flavor-chat-ia'),
            sprintf(
                __('El donante ha confirmado la entrega de "%s". ¡No olvides agradecer!', 'flavor-chat-ia'),
                get_the_title($don_id)
            ),
            [
                'module_id' => $this->id,
                'type' => 'info',
                'link' => get_permalink($don_id),
            ]
        );
    }

    /**
     * Shortcode: Listado de dones
     */
    public function shortcode_listado($atts) {
        $atts = shortcode_atts([
            'categoria' => '',
            'limite' => 12,
        ], $atts);

        ob_start();
        include dirname(__FILE__) . '/templates/listado-dones.php';
        return ob_get_clean();
    }

    /**
     * Shortcode: Mis dones
     */
    public function shortcode_mis_dones($atts) {
        if (!is_user_logged_in()) {
            return '<p>' . __('Inicia sesión para ver tus dones.', 'flavor-chat-ia') . '</p>';
        }

        ob_start();
        include dirname(__FILE__) . '/templates/mis-dones.php';
        return ob_get_clean();
    }

    /**
     * Shortcode: Ofrecer don
     */
    public function shortcode_ofrecer($atts) {
        if (!is_user_logged_in()) {
            return '<p>' . __('Inicia sesión para ofrecer un don.', 'flavor-chat-ia') . '</p>';
        }

        ob_start();
        include dirname(__FILE__) . '/templates/ofrecer-don.php';
        return ob_get_clean();
    }

    /**
     * Shortcode: Muro de gratitud
     */
    public function shortcode_muro_gratitud($atts) {
        $atts = shortcode_atts([
            'limite' => 20,
        ], $atts);

        ob_start();
        include dirname(__FILE__) . '/templates/muro-gratitud.php';
        return ob_get_clean();
    }

    /**
     * Registra widget de dashboard
     */
    public function register_dashboard_widget($registry) {
        $settings = $this->get_settings();
        if (empty($settings['mostrar_en_dashboard'])) {
            return;
        }

        $widget_path = dirname(__FILE__) . '/class-economia-don-widget.php';
        if (!class_exists('Flavor_Economia_Don_Widget') && file_exists($widget_path)) {
            require_once $widget_path;
        }

        if (class_exists('Flavor_Economia_Don_Widget')) {
            $registry->register(new Flavor_Economia_Don_Widget($this));
        }
    }

    /**
     * Encola assets
     */
    public function enqueue_assets() {
        if (!is_singular('ed_don') &&
            !is_post_type_archive('ed_don') &&
            !has_shortcode(get_post()->post_content ?? '', 'economia_don')) {
            return;
        }

        wp_enqueue_style(
            'flavor-economia-don',
            FLAVOR_CHAT_IA_URL . 'includes/modules/economia-don/assets/css/economia-don.css',
            [],
            '1.0.0'
        );

        wp_enqueue_script(
            'flavor-economia-don',
            FLAVOR_CHAT_IA_URL . 'includes/modules/economia-don/assets/js/economia-don.js',
            ['jquery'],
            '1.0.0',
            true
        );

        wp_localize_script('flavor-economia-don', 'edData', [
            'ajaxUrl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('ed_nonce'),
            'i18n' => [
                'confirmSolicitar' => __('¿Deseas solicitar este don?', 'flavor-chat-ia'),
                'confirmEntrega' => __('¿Confirmas que has entregado este don?', 'flavor-chat-ia'),
                'gracias' => __('¡Gracias por tu generosidad!', 'flavor-chat-ia'),
            ],
        ]);
    }

    /**
     * Obtiene estadísticas del usuario
     */
    public function get_estadisticas_usuario($user_id = null) {
        if (!$user_id) {
            $user_id = get_current_user_id();
        }

        $dones_dados = absint(get_user_meta($user_id, '_ed_dones_dados', true));
        $dones_recibidos = absint(get_user_meta($user_id, '_ed_dones_recibidos', true));

        // Dones activos
        $dones_activos = get_posts([
            'post_type' => 'ed_don',
            'author' => $user_id,
            'post_status' => 'publish',
            'meta_query' => [
                ['key' => '_ed_estado', 'value' => 'disponible'],
            ],
            'posts_per_page' => -1,
            'fields' => 'ids',
        ]);

        return [
            'dones_dados' => $dones_dados,
            'dones_recibidos' => $dones_recibidos,
            'dones_activos' => count($dones_activos),
        ];
    }

    /**
     * Valoración para el Sello de Conciencia
     */
    public function get_consciousness_valuation() {
        return [
            'nombre' => 'Economía del Don',
            'puntuacion' => 94,
            'premisas' => [
                'abundancia_organizable' => 0.30,
                'conciencia_fundamental' => 0.25,
                'interdependencia_radical' => 0.20,
                'valor_intrinseco' => 0.15,
                'madurez_ciclica' => 0.10,
            ],
            'descripcion_contribucion' => 'Facilita el flujo de recursos sin contabilidad ni intercambio, reconociendo que dar es un acto de abundancia y no de pérdida.',
            'categoria' => 'economia_alternativa',
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function get_knowledge_base() {
        return <<<KNOWLEDGE
**Economía del Don - Guía de Uso**

La Economía del Don es un sistema donde se ofrece y se recibe sin esperar nada a cambio.

**Principios:**
- Dar por el placer de dar, no para recibir
- Lo que me sobra puede serle útil a otra persona
- No hay contabilidad ni puntos ni saldo
- La gratitud es el único retorno esperado

**Cómo funciona:**
1. Ofrece lo que te sobra o puedes compartir
2. Alguien lo solicita si lo necesita
3. Coordináis la entrega
4. El receptor expresa su gratitud en el muro

**Categorías de dones:**
- Objetos y cosas
- Alimentos
- Servicios y habilidades
- Tiempo y compañía
- Conocimiento
- Espacios

**Valores:**
- La abundancia se crea compartiendo
- Todos tenemos algo que ofrecer
- Recibir también es un acto generoso
KNOWLEDGE;
    }
}

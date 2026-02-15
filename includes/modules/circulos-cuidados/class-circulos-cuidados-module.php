<?php
/**
 * Módulo Círculos de Cuidados
 *
 * Organiza redes de apoyo mutuo para situaciones vitales:
 * - Acompañamiento a personas mayores
 * - Cuidado compartido de infancia
 * - Apoyo en enfermedad/duelo
 * - Bancos de horas de cuidado
 *
 * @package FlavorChatIA
 * @subpackage Modules\CirculosCuidados
 * @since 4.2.0
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Módulo Círculos de Cuidados
 */
class Flavor_Chat_Circulos_Cuidados_Module extends Flavor_Chat_Module_Base {

    /**
     * Tipos de círculos de cuidado
     */
    const TIPOS_CIRCULO = [
        'mayores' => [
            'nombre' => 'Acompañamiento Mayores',
            'descripcion' => 'Visitas, compañía, ayuda con gestiones',
            'icono' => 'dashicons-groups',
            'color' => '#9b59b6',
        ],
        'infancia' => [
            'nombre' => 'Cuidado Infancia',
            'descripcion' => 'Cuidado compartido, recogidas escolares, actividades',
            'icono' => 'dashicons-heart',
            'color' => '#e91e63',
        ],
        'enfermedad' => [
            'nombre' => 'Apoyo Enfermedad',
            'descripcion' => 'Acompañamiento médico, comidas, ayuda doméstica',
            'icono' => 'dashicons-plus-alt',
            'color' => '#00bcd4',
        ],
        'duelo' => [
            'nombre' => 'Acompañamiento Duelo',
            'descripcion' => 'Presencia, escucha, apoyo emocional',
            'icono' => 'dashicons-admin-users',
            'color' => '#607d8b',
        ],
        'maternidad' => [
            'nombre' => 'Red de Maternidad',
            'descripcion' => 'Apoyo embarazo, postparto, crianza',
            'icono' => 'dashicons-admin-home',
            'color' => '#ff9800',
        ],
        'diversidad' => [
            'nombre' => 'Diversidad Funcional',
            'descripcion' => 'Apoyo a personas con necesidades especiales',
            'icono' => 'dashicons-universal-access',
            'color' => '#4caf50',
        ],
    ];

    /**
     * Constructor
     */
    public function __construct() {
        $this->id = 'circulos_cuidados';
        $this->name = __('Círculos de Cuidados', 'flavor-chat-ia');
        $this->description = __('Organiza redes de apoyo mutuo para situaciones vitales.', 'flavor-chat-ia');

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
            'tipos_habilitados' => array_keys(self::TIPOS_CIRCULO),
            'horas_minimas_compromiso' => 2,
            'notificar_necesidades_urgentes' => true,
            'permitir_anonimato' => true,
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
        add_action('save_post_cc_circulo', [$this, 'guardar_meta_circulo']);
        add_action('save_post_cc_necesidad', [$this, 'guardar_meta_necesidad']);

        // Shortcodes
        add_shortcode('circulos_cuidados', [$this, 'shortcode_listado']);
        add_shortcode('mis_cuidados', [$this, 'shortcode_mis_cuidados']);
        add_shortcode('necesidades_cuidados', [$this, 'shortcode_necesidades']);

        // AJAX
        add_action('wp_ajax_cc_unirse_circulo', [$this, 'ajax_unirse_circulo']);
        add_action('wp_ajax_cc_ofrecer_ayuda', [$this, 'ajax_ofrecer_ayuda']);
        add_action('wp_ajax_cc_registrar_horas', [$this, 'ajax_registrar_horas']);
        add_action('wp_ajax_cc_crear_necesidad', [$this, 'ajax_crear_necesidad']);

        // Dashboard widget
        add_action('flavor_register_dashboard_widgets', [$this, 'register_dashboard_widget']);

        // Notificaciones
        add_action('cc_necesidad_urgente', [$this, 'notificar_necesidad_urgente'], 10, 2);

        // Cron para recordatorios
        add_action('cc_recordatorio_cuidados', [$this, 'enviar_recordatorios']);
        if (!wp_next_scheduled('cc_recordatorio_cuidados')) {
            wp_schedule_event(time(), 'daily', 'cc_recordatorio_cuidados');
        }

        // Assets
        add_action('wp_enqueue_scripts', [$this, 'enqueue_assets']);
    }

    /**
     * Registra Custom Post Types
     */
    public function registrar_post_types() {
        // CPT: Círculos de Cuidado
        register_post_type('cc_circulo', [
            'labels' => [
                'name' => __('Círculos de Cuidado', 'flavor-chat-ia'),
                'singular_name' => __('Círculo', 'flavor-chat-ia'),
                'add_new' => __('Crear Círculo', 'flavor-chat-ia'),
                'add_new_item' => __('Crear Nuevo Círculo', 'flavor-chat-ia'),
                'edit_item' => __('Editar Círculo', 'flavor-chat-ia'),
            ],
            'public' => true,
            'has_archive' => true,
            'supports' => ['title', 'editor', 'thumbnail'],
            'menu_icon' => 'dashicons-heart',
            'show_in_rest' => true,
            'rewrite' => ['slug' => 'circulos-cuidados'],
        ]);

        // CPT: Necesidades de Cuidado
        register_post_type('cc_necesidad', [
            'labels' => [
                'name' => __('Necesidades de Cuidado', 'flavor-chat-ia'),
                'singular_name' => __('Necesidad', 'flavor-chat-ia'),
                'add_new' => __('Solicitar Ayuda', 'flavor-chat-ia'),
            ],
            'public' => true,
            'has_archive' => true,
            'supports' => ['title', 'editor'],
            'menu_icon' => 'dashicons-sos',
            'show_in_rest' => true,
            'rewrite' => ['slug' => 'necesidades-cuidado'],
        ]);

        // CPT: Registro de Horas de Cuidado
        register_post_type('cc_registro_horas', [
            'labels' => [
                'name' => __('Registro de Horas', 'flavor-chat-ia'),
                'singular_name' => __('Registro', 'flavor-chat-ia'),
            ],
            'public' => false,
            'show_ui' => true,
            'supports' => ['title'],
            'menu_icon' => 'dashicons-clock',
        ]);
    }

    /**
     * Registra taxonomías
     */
    public function registrar_taxonomias() {
        register_taxonomy('cc_tipo_circulo', ['cc_circulo', 'cc_necesidad'], [
            'labels' => [
                'name' => __('Tipos de Círculo', 'flavor-chat-ia'),
                'singular_name' => __('Tipo', 'flavor-chat-ia'),
            ],
            'hierarchical' => true,
            'show_in_rest' => true,
        ]);

        register_taxonomy('cc_tipo_ayuda', 'cc_necesidad', [
            'labels' => [
                'name' => __('Tipos de Ayuda', 'flavor-chat-ia'),
                'singular_name' => __('Tipo de Ayuda', 'flavor-chat-ia'),
            ],
            'hierarchical' => false,
            'show_in_rest' => true,
        ]);
    }

    /**
     * Registra meta boxes
     */
    public function registrar_meta_boxes() {
        add_meta_box(
            'cc_circulo_datos',
            __('Datos del Círculo', 'flavor-chat-ia'),
            [$this, 'render_meta_box_circulo'],
            'cc_circulo',
            'normal',
            'high'
        );

        add_meta_box(
            'cc_necesidad_datos',
            __('Datos de la Necesidad', 'flavor-chat-ia'),
            [$this, 'render_meta_box_necesidad'],
            'cc_necesidad',
            'normal',
            'high'
        );

        add_meta_box(
            'cc_circulo_miembros',
            __('Miembros del Círculo', 'flavor-chat-ia'),
            [$this, 'render_meta_box_miembros'],
            'cc_circulo',
            'side',
            'default'
        );
    }

    /**
     * Renderiza meta box del círculo
     */
    public function render_meta_box_circulo($post) {
        wp_nonce_field('cc_circulo_nonce', 'cc_circulo_nonce_field');

        $tipo = get_post_meta($post->ID, '_cc_tipo', true);
        $coordinador = get_post_meta($post->ID, '_cc_coordinador', true);
        $max_miembros = get_post_meta($post->ID, '_cc_max_miembros', true) ?: 15;
        $zona = get_post_meta($post->ID, '_cc_zona', true);
        $privacidad = get_post_meta($post->ID, '_cc_privacidad', true) ?: 'publico';
        ?>
        <table class="form-table">
            <tr>
                <th><label for="cc_tipo"><?php esc_html_e('Tipo de Círculo', 'flavor-chat-ia'); ?></label></th>
                <td>
                    <select name="cc_tipo" id="cc_tipo" class="regular-text">
                        <?php foreach (self::TIPOS_CIRCULO as $id => $data) : ?>
                        <option value="<?php echo esc_attr($id); ?>" <?php selected($tipo, $id); ?>>
                            <?php echo esc_html($data['nombre']); ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                </td>
            </tr>
            <tr>
                <th><label for="cc_zona"><?php esc_html_e('Zona/Barrio', 'flavor-chat-ia'); ?></label></th>
                <td>
                    <input type="text" name="cc_zona" id="cc_zona"
                           value="<?php echo esc_attr($zona); ?>" class="regular-text">
                </td>
            </tr>
            <tr>
                <th><label for="cc_max_miembros"><?php esc_html_e('Máximo miembros', 'flavor-chat-ia'); ?></label></th>
                <td>
                    <input type="number" name="cc_max_miembros" id="cc_max_miembros"
                           value="<?php echo esc_attr($max_miembros); ?>" min="3" max="50">
                </td>
            </tr>
            <tr>
                <th><label for="cc_privacidad"><?php esc_html_e('Privacidad', 'flavor-chat-ia'); ?></label></th>
                <td>
                    <select name="cc_privacidad" id="cc_privacidad">
                        <option value="publico" <?php selected($privacidad, 'publico'); ?>>
                            <?php esc_html_e('Público - Cualquiera puede unirse', 'flavor-chat-ia'); ?>
                        </option>
                        <option value="solicitud" <?php selected($privacidad, 'solicitud'); ?>>
                            <?php esc_html_e('Por solicitud - Requiere aprobación', 'flavor-chat-ia'); ?>
                        </option>
                        <option value="invitacion" <?php selected($privacidad, 'invitacion'); ?>>
                            <?php esc_html_e('Solo invitación', 'flavor-chat-ia'); ?>
                        </option>
                    </select>
                </td>
            </tr>
        </table>
        <?php
    }

    /**
     * Renderiza meta box de necesidad
     */
    public function render_meta_box_necesidad($post) {
        wp_nonce_field('cc_necesidad_nonce', 'cc_necesidad_nonce_field');

        $urgencia = get_post_meta($post->ID, '_cc_urgencia', true) ?: 'normal';
        $fecha_inicio = get_post_meta($post->ID, '_cc_fecha_inicio', true);
        $fecha_fin = get_post_meta($post->ID, '_cc_fecha_fin', true);
        $horas_necesarias = get_post_meta($post->ID, '_cc_horas_necesarias', true);
        $anonimo = get_post_meta($post->ID, '_cc_anonimo', true);
        $estado = get_post_meta($post->ID, '_cc_estado', true) ?: 'abierta';
        ?>
        <table class="form-table">
            <tr>
                <th><label for="cc_urgencia"><?php esc_html_e('Urgencia', 'flavor-chat-ia'); ?></label></th>
                <td>
                    <select name="cc_urgencia" id="cc_urgencia">
                        <option value="baja" <?php selected($urgencia, 'baja'); ?>>
                            <?php esc_html_e('Baja - Puede esperar', 'flavor-chat-ia'); ?>
                        </option>
                        <option value="normal" <?php selected($urgencia, 'normal'); ?>>
                            <?php esc_html_e('Normal', 'flavor-chat-ia'); ?>
                        </option>
                        <option value="alta" <?php selected($urgencia, 'alta'); ?>>
                            <?php esc_html_e('Alta - Próximos días', 'flavor-chat-ia'); ?>
                        </option>
                        <option value="urgente" <?php selected($urgencia, 'urgente'); ?>>
                            <?php esc_html_e('Urgente - Hoy/Mañana', 'flavor-chat-ia'); ?>
                        </option>
                    </select>
                </td>
            </tr>
            <tr>
                <th><label for="cc_fecha_inicio"><?php esc_html_e('Fecha inicio', 'flavor-chat-ia'); ?></label></th>
                <td>
                    <input type="datetime-local" name="cc_fecha_inicio" id="cc_fecha_inicio"
                           value="<?php echo esc_attr($fecha_inicio); ?>">
                </td>
            </tr>
            <tr>
                <th><label for="cc_fecha_fin"><?php esc_html_e('Fecha fin', 'flavor-chat-ia'); ?></label></th>
                <td>
                    <input type="datetime-local" name="cc_fecha_fin" id="cc_fecha_fin"
                           value="<?php echo esc_attr($fecha_fin); ?>">
                </td>
            </tr>
            <tr>
                <th><label for="cc_horas"><?php esc_html_e('Horas necesarias', 'flavor-chat-ia'); ?></label></th>
                <td>
                    <input type="number" name="cc_horas_necesarias" id="cc_horas"
                           value="<?php echo esc_attr($horas_necesarias); ?>" min="0.5" step="0.5">
                </td>
            </tr>
            <tr>
                <th><label for="cc_anonimo"><?php esc_html_e('Solicitud anónima', 'flavor-chat-ia'); ?></label></th>
                <td>
                    <label>
                        <input type="checkbox" name="cc_anonimo" id="cc_anonimo" value="1"
                               <?php checked($anonimo, '1'); ?>>
                        <?php esc_html_e('No mostrar mi nombre públicamente', 'flavor-chat-ia'); ?>
                    </label>
                </td>
            </tr>
            <tr>
                <th><label for="cc_estado"><?php esc_html_e('Estado', 'flavor-chat-ia'); ?></label></th>
                <td>
                    <select name="cc_estado" id="cc_estado">
                        <option value="abierta" <?php selected($estado, 'abierta'); ?>>
                            <?php esc_html_e('Abierta', 'flavor-chat-ia'); ?>
                        </option>
                        <option value="en_proceso" <?php selected($estado, 'en_proceso'); ?>>
                            <?php esc_html_e('En proceso', 'flavor-chat-ia'); ?>
                        </option>
                        <option value="cubierta" <?php selected($estado, 'cubierta'); ?>>
                            <?php esc_html_e('Cubierta', 'flavor-chat-ia'); ?>
                        </option>
                        <option value="cerrada" <?php selected($estado, 'cerrada'); ?>>
                            <?php esc_html_e('Cerrada', 'flavor-chat-ia'); ?>
                        </option>
                    </select>
                </td>
            </tr>
        </table>
        <?php
    }

    /**
     * Renderiza meta box de miembros
     */
    public function render_meta_box_miembros($post) {
        $miembros = get_post_meta($post->ID, '_cc_miembros', true) ?: [];
        $coordinador = get_post_meta($post->ID, '_cc_coordinador', true);

        if (empty($miembros)) {
            echo '<p>' . esc_html__('No hay miembros aún.', 'flavor-chat-ia') . '</p>';
            return;
        }

        echo '<ul class="cc-miembros-lista">';
        foreach ($miembros as $user_id) {
            $user = get_userdata($user_id);
            if (!$user) continue;

            $es_coordinador = ($user_id == $coordinador);
            $rol = $es_coordinador ? __('Coordinador', 'flavor-chat-ia') : __('Miembro', 'flavor-chat-ia');

            echo '<li>';
            echo esc_html($user->display_name);
            echo ' <small>(' . esc_html($rol) . ')</small>';
            echo '</li>';
        }
        echo '</ul>';
        echo '<p><strong>' . count($miembros) . '</strong> ' . esc_html__('miembros', 'flavor-chat-ia') . '</p>';
    }

    /**
     * Guarda meta del círculo
     */
    public function guardar_meta_circulo($post_id) {
        if (!isset($_POST['cc_circulo_nonce_field']) ||
            !wp_verify_nonce($_POST['cc_circulo_nonce_field'], 'cc_circulo_nonce')) {
            return;
        }

        if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) return;
        if (!current_user_can('edit_post', $post_id)) return;

        $campos = ['tipo', 'zona', 'max_miembros', 'privacidad'];
        foreach ($campos as $campo) {
            if (isset($_POST['cc_' . $campo])) {
                update_post_meta($post_id, '_cc_' . $campo, sanitize_text_field($_POST['cc_' . $campo]));
            }
        }

        // Si es nuevo, asignar al creador como coordinador y primer miembro
        if (get_post_meta($post_id, '_cc_coordinador', true) === '') {
            $user_id = get_current_user_id();
            update_post_meta($post_id, '_cc_coordinador', $user_id);
            update_post_meta($post_id, '_cc_miembros', [$user_id]);
        }
    }

    /**
     * Guarda meta de necesidad
     */
    public function guardar_meta_necesidad($post_id) {
        if (!isset($_POST['cc_necesidad_nonce_field']) ||
            !wp_verify_nonce($_POST['cc_necesidad_nonce_field'], 'cc_necesidad_nonce')) {
            return;
        }

        if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) return;
        if (!current_user_can('edit_post', $post_id)) return;

        $campos = ['urgencia', 'fecha_inicio', 'fecha_fin', 'horas_necesarias', 'estado'];
        foreach ($campos as $campo) {
            if (isset($_POST['cc_' . $campo])) {
                update_post_meta($post_id, '_cc_' . $campo, sanitize_text_field($_POST['cc_' . $campo]));
            }
        }

        update_post_meta($post_id, '_cc_anonimo', isset($_POST['cc_anonimo']) ? '1' : '0');

        // Disparar acción si es urgente
        $urgencia = $_POST['cc_urgencia'] ?? 'normal';
        if ($urgencia === 'urgente') {
            do_action('cc_necesidad_urgente', $post_id, get_current_user_id());
        }
    }

    /**
     * AJAX: Unirse a un círculo
     */
    public function ajax_unirse_circulo() {
        check_ajax_referer('cc_nonce', 'nonce');

        $circulo_id = absint($_POST['circulo_id'] ?? 0);
        $user_id = get_current_user_id();

        if (!$circulo_id || !$user_id) {
            wp_send_json_error(['message' => __('Datos inválidos', 'flavor-chat-ia')]);
        }

        $miembros = get_post_meta($circulo_id, '_cc_miembros', true) ?: [];
        $max_miembros = get_post_meta($circulo_id, '_cc_max_miembros', true) ?: 15;

        if (in_array($user_id, $miembros)) {
            wp_send_json_error(['message' => __('Ya eres miembro de este círculo', 'flavor-chat-ia')]);
        }

        if (count($miembros) >= $max_miembros) {
            wp_send_json_error(['message' => __('El círculo está lleno', 'flavor-chat-ia')]);
        }

        $miembros[] = $user_id;
        update_post_meta($circulo_id, '_cc_miembros', $miembros);

        wp_send_json_success([
            'message' => __('Te has unido al círculo', 'flavor-chat-ia'),
            'miembros' => count($miembros),
        ]);
    }

    /**
     * AJAX: Ofrecer ayuda a una necesidad
     */
    public function ajax_ofrecer_ayuda() {
        check_ajax_referer('cc_nonce', 'nonce');

        $necesidad_id = absint($_POST['necesidad_id'] ?? 0);
        $horas = floatval($_POST['horas'] ?? 0);
        $mensaje = sanitize_textarea_field($_POST['mensaje'] ?? '');
        $user_id = get_current_user_id();

        if (!$necesidad_id || !$user_id || $horas <= 0) {
            wp_send_json_error(['message' => __('Datos inválidos', 'flavor-chat-ia')]);
        }

        $ayudantes = get_post_meta($necesidad_id, '_cc_ayudantes', true) ?: [];
        $ayudantes[] = [
            'user_id' => $user_id,
            'horas' => $horas,
            'mensaje' => $mensaje,
            'fecha' => current_time('mysql'),
            'estado' => 'pendiente',
        ];
        update_post_meta($necesidad_id, '_cc_ayudantes', $ayudantes);

        // Actualizar estado si hay suficiente ayuda
        $horas_necesarias = floatval(get_post_meta($necesidad_id, '_cc_horas_necesarias', true));
        $horas_ofrecidas = array_sum(array_column($ayudantes, 'horas'));

        if ($horas_ofrecidas >= $horas_necesarias) {
            update_post_meta($necesidad_id, '_cc_estado', 'cubierta');
        } else {
            update_post_meta($necesidad_id, '_cc_estado', 'en_proceso');
        }

        // Notificar al solicitante
        $solicitante_id = get_post_field('post_author', $necesidad_id);
        $this->notificar_oferta_ayuda($necesidad_id, $user_id, $solicitante_id);

        wp_send_json_success([
            'message' => __('Gracias por ofrecer tu ayuda', 'flavor-chat-ia'),
        ]);
    }

    /**
     * AJAX: Registrar horas de cuidado realizadas
     */
    public function ajax_registrar_horas() {
        check_ajax_referer('cc_nonce', 'nonce');

        $necesidad_id = absint($_POST['necesidad_id'] ?? 0);
        $horas = floatval($_POST['horas'] ?? 0);
        $descripcion = sanitize_textarea_field($_POST['descripcion'] ?? '');
        $user_id = get_current_user_id();

        if (!$necesidad_id || !$user_id || $horas <= 0) {
            wp_send_json_error(['message' => __('Datos inválidos', 'flavor-chat-ia')]);
        }

        // Crear registro de horas
        $registro_id = wp_insert_post([
            'post_type' => 'cc_registro_horas',
            'post_title' => sprintf(
                __('%s - %s horas', 'flavor-chat-ia'),
                get_userdata($user_id)->display_name,
                $horas
            ),
            'post_status' => 'publish',
            'post_author' => $user_id,
        ]);

        if ($registro_id) {
            update_post_meta($registro_id, '_cc_necesidad_id', $necesidad_id);
            update_post_meta($registro_id, '_cc_horas', $horas);
            update_post_meta($registro_id, '_cc_descripcion', $descripcion);
            update_post_meta($registro_id, '_cc_fecha', current_time('mysql'));

            // Actualizar total de horas del usuario
            $horas_totales = floatval(get_user_meta($user_id, '_cc_horas_totales', true));
            update_user_meta($user_id, '_cc_horas_totales', $horas_totales + $horas);
        }

        wp_send_json_success([
            'message' => __('Horas registradas correctamente', 'flavor-chat-ia'),
            'horas_totales' => $horas_totales + $horas,
        ]);
    }

    /**
     * Notifica necesidad urgente
     */
    public function notificar_necesidad_urgente($necesidad_id, $user_id) {
        if (!class_exists('Flavor_Notification_Center')) {
            return;
        }

        $necesidad = get_post($necesidad_id);
        $nc = Flavor_Notification_Center::get_instance();

        // Obtener miembros del círculo relacionado
        $circulo_id = get_post_meta($necesidad_id, '_cc_circulo_id', true);
        if ($circulo_id) {
            $miembros = get_post_meta($circulo_id, '_cc_miembros', true) ?: [];

            foreach ($miembros as $miembro_id) {
                if ($miembro_id == $user_id) continue;

                $nc->send(
                    $miembro_id,
                    __('Necesidad urgente de cuidados', 'flavor-chat-ia'),
                    sprintf(__('Se necesita ayuda urgente: %s', 'flavor-chat-ia'), $necesidad->post_title),
                    [
                        'module_id' => $this->id,
                        'type' => 'warning',
                        'link' => get_permalink($necesidad_id),
                    ]
                );
            }
        }
    }

    /**
     * Notifica oferta de ayuda
     */
    private function notificar_oferta_ayuda($necesidad_id, $ayudante_id, $solicitante_id) {
        if (!class_exists('Flavor_Notification_Center')) {
            return;
        }

        $ayudante = get_userdata($ayudante_id);
        $nc = Flavor_Notification_Center::get_instance();

        $nc->send(
            $solicitante_id,
            __('Alguien quiere ayudarte', 'flavor-chat-ia'),
            sprintf(__('%s se ha ofrecido a ayudarte', 'flavor-chat-ia'), $ayudante->display_name),
            [
                'module_id' => $this->id,
                'type' => 'success',
                'link' => get_permalink($necesidad_id),
            ]
        );
    }

    /**
     * Shortcode: Listado de círculos
     */
    public function shortcode_listado($atts) {
        $atts = shortcode_atts([
            'tipo' => '',
            'limite' => 12,
        ], $atts);

        ob_start();
        include dirname(__FILE__) . '/templates/listado-circulos.php';
        return ob_get_clean();
    }

    /**
     * Shortcode: Mis cuidados
     */
    public function shortcode_mis_cuidados($atts) {
        if (!is_user_logged_in()) {
            return '<p>' . __('Inicia sesión para ver tus cuidados.', 'flavor-chat-ia') . '</p>';
        }

        ob_start();
        include dirname(__FILE__) . '/templates/mis-cuidados.php';
        return ob_get_clean();
    }

    /**
     * Shortcode: Necesidades de cuidados
     */
    public function shortcode_necesidades($atts) {
        $atts = shortcode_atts([
            'estado' => 'abierta',
            'urgencia' => '',
            'limite' => 10,
        ], $atts);

        ob_start();
        include dirname(__FILE__) . '/templates/necesidades.php';
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

        $widget_path = dirname(__FILE__) . '/class-circulos-cuidados-widget.php';
        if (!class_exists('Flavor_Circulos_Cuidados_Widget') && file_exists($widget_path)) {
            require_once $widget_path;
        }

        if (class_exists('Flavor_Circulos_Cuidados_Widget')) {
            $registry->register(new Flavor_Circulos_Cuidados_Widget($this));
        }
    }

    /**
     * Enqueue assets
     */
    public function enqueue_assets() {
        if (!is_singular(['cc_circulo', 'cc_necesidad']) &&
            !has_shortcode(get_post()->post_content ?? '', 'circulos_cuidados')) {
            return;
        }

        wp_enqueue_style(
            'flavor-circulos-cuidados',
            FLAVOR_CHAT_IA_URL . 'includes/modules/circulos-cuidados/assets/css/circulos-cuidados.css',
            [],
            '1.0.0'
        );

        wp_enqueue_script(
            'flavor-circulos-cuidados',
            FLAVOR_CHAT_IA_URL . 'includes/modules/circulos-cuidados/assets/js/circulos-cuidados.js',
            ['jquery'],
            '1.0.0',
            true
        );

        wp_localize_script('flavor-circulos-cuidados', 'ccData', [
            'ajaxUrl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('cc_nonce'),
            'i18n' => [
                'confirmUnirse' => __('¿Quieres unirte a este círculo?', 'flavor-chat-ia'),
                'gracias' => __('¡Gracias por cuidar!', 'flavor-chat-ia'),
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

        global $wpdb;

        // Círculos donde participa
        $circulos = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(DISTINCT pm.post_id)
             FROM {$wpdb->postmeta} pm
             JOIN {$wpdb->posts} p ON pm.post_id = p.ID
             WHERE pm.meta_key = '_cc_miembros'
               AND pm.meta_value LIKE %s
               AND p.post_type = 'cc_circulo'
               AND p.post_status = 'publish'",
            '%"' . $user_id . '"%'
        ));

        // Horas totales de cuidado
        $horas = floatval(get_user_meta($user_id, '_cc_horas_totales', true));

        // Necesidades ayudadas
        $ayudadas = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(DISTINCT pm.post_id)
             FROM {$wpdb->postmeta} pm
             JOIN {$wpdb->posts} p ON pm.post_id = p.ID
             WHERE pm.meta_key = '_cc_ayudantes'
               AND pm.meta_value LIKE %s
               AND p.post_type = 'cc_necesidad'",
            '%user_id";i:' . $user_id . '%'
        ));

        return [
            'circulos' => (int) $circulos,
            'horas_cuidado' => $horas,
            'necesidades_ayudadas' => (int) $ayudadas,
        ];
    }

    /**
     * Valoración para el Sello de Conciencia
     */
    public function get_consciousness_valuation() {
        return [
            'nombre' => 'Círculos de Cuidados',
            'puntuacion' => 96,
            'premisas' => [
                'conciencia_fundamental' => 0.35,
                'interdependencia_radical' => 0.30,
                'valor_intrinseco' => 0.20,
                'abundancia_organizable' => 0.15,
            ],
            'descripcion_contribucion' => 'Reconoce el valor del trabajo de cuidados, organiza el apoyo mutuo, distribuye la responsabilidad colectivamente y dignifica tanto a quien cuida como a quien es cuidado.',
            'categoria' => 'cuidados',
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function get_actions() {
        return [
            'listar_circulos' => [
                'description' => 'Ver círculos de cuidados disponibles',
                'params' => ['tipo'],
            ],
            'ver_necesidades' => [
                'description' => 'Ver necesidades de cuidado abiertas',
                'params' => ['urgencia'],
            ],
            'mis_cuidados' => [
                'description' => 'Ver mis estadísticas de cuidados',
                'params' => [],
            ],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function get_knowledge_base() {
        return <<<KNOWLEDGE
**Círculos de Cuidados - Guía de Uso**

Los Círculos de Cuidados son redes de apoyo mutuo para situaciones vitales.

**Tipos de círculos:**
- Acompañamiento a mayores
- Cuidado compartido de infancia
- Apoyo en enfermedad
- Acompañamiento en duelo
- Red de maternidad
- Diversidad funcional

**Cómo funciona:**
1. Únete a un círculo de tu zona o interés
2. Cuando necesites ayuda, publica una necesidad
3. Otros miembros se ofrecerán a ayudar
4. Registra las horas de cuidado que das
5. Cuando otros necesiten, ayuda tú también

**Valores:**
- El cuidado es responsabilidad colectiva
- Todas las horas de cuidado valen igual
- La ayuda se da sin esperar retorno inmediato
- Se respeta la dignidad de quien cuida y quien es cuidado
KNOWLEDGE;
    }
}

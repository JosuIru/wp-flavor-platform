<?php
/**
 * Frontend Controller para Eventos
 *
 * @package FlavorChatIA
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Controlador Frontend para el módulo de Eventos
 * Gestiona shortcodes, assets y dashboard tabs del frontend
 */
class Flavor_Eventos_Frontend_Controller {

    /**
     * Instancia única
     */
    private static $instance = null;

    /**
     * Obtener instancia singleton
     */
    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Constructor
     */
    private function __construct() {
        $this->init();
    }

    /**
     * Inicializar hooks y filtros
     */
    private function init() {
        // Registrar assets
        add_action('wp_enqueue_scripts', [$this, 'registrar_assets']);

        // Registrar shortcodes
        add_action('init', [$this, 'registrar_shortcodes']);

        // AJAX handlers
        add_action('wp_ajax_eventos_inscribirse', [$this, 'ajax_inscribirse']);
        add_action('wp_ajax_eventos_cancelar_inscripcion', [$this, 'ajax_cancelar_inscripcion']);
        add_action('wp_ajax_eventos_filtrar', [$this, 'ajax_filtrar']);
        add_action('wp_ajax_nopriv_eventos_filtrar', [$this, 'ajax_filtrar']);
        add_action('wp_ajax_eventos_compartir', [$this, 'ajax_compartir']);

        // Dashboard tabs
        add_filter('flavor_user_dashboard_tabs', [$this, 'registrar_tabs'], 10, 1);

        // Template overrides
        add_filter('template_include', [$this, 'cargar_template']);
    }

    /**
     * Verifica si se deben cargar los assets del módulo
     *
     * @return bool
     */
    private function should_load_assets() {
        global $post;

        if (!$post) {
            return false;
        }

        $shortcodes_modulo = [
            'eventos_listado',
            'eventos_calendario',
            'eventos_mis_inscripciones',
            'eventos_detalle',
            'eventos_proximos',
            'eventos_destacados',
            'flavor_eventos_acciones',
            'eventos_inscribirse',
            'eventos_mapa',
            'eventos_proximo',
        ];

        foreach ($shortcodes_modulo as $shortcode) {
            if (has_shortcode($post->post_content, $shortcode)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Registrar assets CSS y JS
     */
    public function registrar_assets() {
        if (!$this->should_load_assets()) {
            return;
        }

        $base_url = plugins_url('', dirname(dirname(__FILE__)));
        $version = FLAVOR_CHAT_IA_VERSION ?? '1.0.0';

        // CSS
        wp_enqueue_style(
            'flavor-eventos',
            $base_url . '/assets/css/eventos.css',
            ['flavor-modules-common'],
            $version
        );

        // JS
        wp_enqueue_script(
            'flavor-eventos',
            $base_url . '/assets/js/eventos.js',
            ['jquery'],
            $version,
            true
        );

        // Localizar script
        wp_localize_script('flavor-eventos', 'flavorEventos', [
            'ajaxUrl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('eventos_nonce'),
            'i18n' => [
                'inscrito' => __('Te has inscrito correctamente', 'flavor-chat-ia'),
                'cancelado' => __('Inscripción cancelada', 'flavor-chat-ia'),
                'error' => __('Ha ocurrido un error', 'flavor-chat-ia'),
                'confirmacion' => __('¿Estás seguro?', 'flavor-chat-ia'),
                'cargando' => __('Cargando...', 'flavor-chat-ia'),
                'sin_plazas' => __('No hay plazas disponibles', 'flavor-chat-ia'),
                'lista_espera' => __('Te has añadido a la lista de espera', 'flavor-chat-ia'),
            ],
        ]);
    }

    /**
     * Encolar assets cuando sea necesario
     */
    public function encolar_assets() {
        wp_enqueue_style('flavor-eventos');
        wp_enqueue_script('flavor-eventos');
    }

    /**
     * Registrar shortcodes del módulo
     */
    public function registrar_shortcodes() {
        $shortcodes = [
            'eventos_listado' => 'shortcode_listado',
            'eventos_calendario' => 'shortcode_calendario',
            'eventos_mis_inscripciones' => 'shortcode_mis_inscripciones',
            'eventos_detalle' => 'shortcode_detalle',
            'eventos_proximos' => 'shortcode_proximos',
            'eventos_destacados' => 'shortcode_destacados',
            'flavor_eventos_acciones' => 'shortcode_acciones',
            // Nuevos shortcodes
            'eventos_inscribirse' => 'shortcode_inscribirse',
            'eventos_mapa' => 'shortcode_mapa',
            // Alias singular
            'eventos_proximo' => 'shortcode_proximo',
        ];
        foreach ($shortcodes as $tag => $method) {
            if (!shortcode_exists($tag)) {
                add_shortcode($tag, [$this, $method]);
            }
        }
    }

    /**
     * Renderiza un estado de login con acción explícita.
     *
     * @param string $mensaje Mensaje principal.
     * @return string
     */
    private function render_login_required($mensaje) {
        return '<div class="flavor-empty-state flavor-empty-state-login">' .
            '<p>' . esc_html($mensaje) . '</p>' .
            '<a href="' . esc_url(wp_login_url(flavor_current_request_url())) . '" class="flavor-btn flavor-btn-primary">' .
            esc_html__('Iniciar sesión', 'flavor-chat-ia') .
            '</a></div>';
    }

    /**
     * Shortcode: Próximo evento (widget compacto)
     */
    public function shortcode_proximo($atts) {
        $atts = shortcode_atts(['limite' => 1], $atts);
        return $this->shortcode_proximos(['limite' => 1, 'compact' => 'true']);
    }

    /**
     * Shortcode: Listado de eventos
     */
    public function shortcode_listado($atts) {
        $this->encolar_assets();

        $atts = shortcode_atts([
            'tipo' => '',
            'categoria' => '',
            'limite' => 12,
            'columnas' => 3,
            'mostrar_filtros' => 'true',
            'comunidad_id' => 0,
            'comunidad_ids' => '',
            // Parámetros visuales (VBP)
            'esquema_color' => 'default',
            'estilo_tarjeta' => 'elevated',
            'radio_bordes' => 'lg',
            'animacion_entrada' => 'fade',
            'orderby' => 'fecha_inicio',
            'order' => 'ASC',
        ], $atts);

        ob_start();
        $this->render_listado($atts);
        return ob_get_clean();
    }

    /**
     * Shortcode: Calendario de eventos
     */
    public function shortcode_calendario($atts) {
        $this->encolar_assets();

        $atts = shortcode_atts([
            'mes' => date('m'),
            'anio' => date('Y'),
            'vista' => 'mes',
        ], $atts);

        ob_start();
        $this->render_calendario($atts);
        return ob_get_clean();
    }

    /**
     * Shortcode: Mis inscripciones
     */
    public function shortcode_mis_inscripciones($atts) {
        if (!is_user_logged_in()) {
            return $this->render_login_required(__('Inicia sesión para ver tus inscripciones.', 'flavor-chat-ia'));
        }

        $this->encolar_assets();

        $atts = shortcode_atts([
            'estado' => '',
            'limite' => 20,
        ], $atts);

        ob_start();
        $this->render_mis_inscripciones($atts);
        return ob_get_clean();
    }

    /**
     * Shortcode: Detalle de evento
     */
    public function shortcode_detalle($atts) {
        $this->encolar_assets();

        $atts = shortcode_atts([
            'id' => 0,
        ], $atts);

        $evento_id = $atts['id'] ?: (isset($_GET['evento_id']) ? absint($_GET['evento_id']) : 0);

        if (!$evento_id) {
            return '<div class="flavor-empty-state">' .
                '<p>' . esc_html__('Evento no especificado.', 'flavor-chat-ia') . '</p>' .
                '<a href="' . esc_url(home_url('/mi-portal/eventos/')) . '" class="flavor-btn flavor-btn-primary">' .
                esc_html__('Volver a eventos', 'flavor-chat-ia') .
                '</a></div>';
        }

        ob_start();
        $this->render_detalle($evento_id);
        return ob_get_clean();
    }

    /**
     * Shortcode: Próximos eventos
     */
    public function shortcode_proximos($atts) {
        $this->encolar_assets();

        $atts = shortcode_atts([
            'limite' => 6,
            'columnas' => 3,
        ], $atts);

        ob_start();
        $this->render_proximos($atts);
        return ob_get_clean();
    }

    /**
     * Shortcode: Eventos destacados
     */
    public function shortcode_destacados($atts) {
        $this->encolar_assets();

        $atts = shortcode_atts([
            'limite' => 4,
        ], $atts);

        ob_start();
        $this->render_destacados($atts);
        return ob_get_clean();
    }

    /**
     * Renderizar listado de eventos
     */
    private function render_listado($atts) {
        global $wpdb;
        $tabla_eventos = $wpdb->prefix . 'flavor_eventos';

        if (!Flavor_Chat_Helpers::tabla_existe($tabla_eventos)) {
            echo '<p class="flavor-error">' . __('El módulo de eventos no está configurado.', 'flavor-chat-ia') . '</p>';
            return;
        }

        // Clases CSS para estilos visuales
        $visual_classes = ['flavor-eventos-listado'];
        if (!empty($atts['esquema_color']) && $atts['esquema_color'] !== 'default') {
            $visual_classes[] = 'flavor-scheme-' . sanitize_html_class($atts['esquema_color']);
        }
        if (!empty($atts['estilo_tarjeta'])) {
            $visual_classes[] = 'flavor-card-' . sanitize_html_class($atts['estilo_tarjeta']);
        }
        if (!empty($atts['radio_bordes'])) {
            $visual_classes[] = 'flavor-radius-' . sanitize_html_class($atts['radio_bordes']);
        }
        if (!empty($atts['animacion_entrada']) && $atts['animacion_entrada'] !== 'none') {
            $visual_classes[] = 'flavor-animate-' . sanitize_html_class($atts['animacion_entrada']);
        }
        $visual_class_str = implode(' ', $visual_classes);

        $where = ["estado = 'publicado'", "fecha_inicio >= NOW()"];
        $params = [];

        if (!empty($atts['tipo'])) {
            $where[] = "tipo = %s";
            $params[] = $atts['tipo'];
        }

        if (!empty($atts['categoria'])) {
            $where[] = "categoria = %s";
            $params[] = $atts['categoria'];
        }

        $comunidad_id = absint($atts['comunidad_id']);
        if ($comunidad_id > 0) {
            $where[] = "comunidad_id = %d";
            $params[] = $comunidad_id;
        } elseif (!empty($atts['comunidad_ids'])) {
            $comunidad_ids = array_values(array_filter(array_map('absint', explode(',', (string) $atts['comunidad_ids']))));
            if (!empty($comunidad_ids)) {
                $where[] = 'comunidad_id IN (' . implode(',', array_fill(0, count($comunidad_ids), '%d')) . ')';
                $params = array_merge($params, $comunidad_ids);
            }
        }

        // Ordenamiento dinámico
        $orderby_map = [
            'fecha_inicio' => 'fecha_inicio',
            'date' => 'fecha_inicio',
            'title' => 'titulo',
            'titulo' => 'titulo',
            'modified' => 'updated_at',
        ];
        $order_column = isset($orderby_map[$atts['orderby']]) ? $orderby_map[$atts['orderby']] : 'fecha_inicio';
        $order_dir = strtoupper($atts['order']) === 'DESC' ? 'DESC' : 'ASC';

        $sql = "SELECT * FROM $tabla_eventos WHERE " . implode(' AND ', $where) . " ORDER BY {$order_column} {$order_dir} LIMIT %d";
        $params[] = intval($atts['limite']);

        $eventos = $wpdb->get_results($wpdb->prepare($sql, ...$params));

        $template = dirname(__FILE__) . '/archive.php';
        if (file_exists($template)) {
            include $template;
        } else {
            echo '<div class="flavor-eventos-grid ' . esc_attr($visual_class_str) . ' grid-' . esc_attr($atts['columnas']) . '">';
            foreach ($eventos as $evento) {
                $this->render_evento_card($evento);
            }
            echo '</div>';
        }
    }

    /**
     * Renderizar calendario
     */
    private function render_calendario($atts) {
        $template = dirname(__FILE__) . '/../templates/calendario.php';
        if (file_exists($template)) {
            include $template;
        }
    }

    /**
     * Renderizar mis inscripciones
     */
    private function render_mis_inscripciones($atts) {
        global $wpdb;
        $tabla_inscripciones = $wpdb->prefix . 'flavor_eventos_inscripciones';
        $tabla_eventos = $wpdb->prefix . 'flavor_eventos';
        $usuario_id = get_current_user_id();

        $inscripciones = $wpdb->get_results($wpdb->prepare(
            "SELECT i.*, e.titulo, e.fecha_inicio, e.fecha_fin, e.ubicacion, e.imagen
             FROM $tabla_inscripciones i
             JOIN $tabla_eventos e ON i.evento_id = e.id
             WHERE i.user_id = %d
             ORDER BY e.fecha_inicio DESC
             LIMIT %d",
            $usuario_id,
            intval($atts['limite'])
        ));

        $template = dirname(__FILE__) . '/../templates/mis-inscripciones.php';
        if (file_exists($template)) {
            include $template;
        } else {
            ?>
            <div class="flavor-eventos-mis-inscripciones">
                <?php if (empty($inscripciones)) : ?>
                    <p class="no-resultados"><?php _e('No tienes inscripciones a eventos.', 'flavor-chat-ia'); ?></p>
                <?php else : ?>
                    <div class="inscripciones-lista">
                        <?php foreach ($inscripciones as $inscripcion) : ?>
                            <div class="inscripcion-item">
                                <div class="evento-info">
                                    <h4><?php echo esc_html($inscripcion->titulo); ?></h4>
                                    <span class="fecha"><?php echo esc_html(date_i18n('d M Y - H:i', strtotime($inscripcion->fecha_inicio))); ?></span>
                                    <span class="ubicacion"><?php echo esc_html($inscripcion->ubicacion); ?></span>
                                </div>
                                <div class="inscripcion-estado">
                                    <span class="estado estado-<?php echo esc_attr($inscripcion->estado); ?>">
                                        <?php echo esc_html(ucfirst($inscripcion->estado)); ?>
                                    </span>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
            <?php
        }
    }

    /**
     * Renderizar detalle de evento
     */
    private function render_detalle($evento_id) {
        global $wpdb;
        $tabla_eventos = $wpdb->prefix . 'flavor_eventos';

        $evento = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM $tabla_eventos WHERE id = %d",
            $evento_id
        ));

        if (!$evento) {
            echo '<p class="flavor-error">' . __('Evento no encontrado.', 'flavor-chat-ia') . '</p>';
            return;
        }

        $template = dirname(__FILE__) . '/single.php';
        if (file_exists($template)) {
            include $template;
        }
    }

    /**
     * Renderizar próximos eventos
     */
    private function render_proximos($atts) {
        global $wpdb;
        $tabla_eventos = $wpdb->prefix . 'flavor_eventos';

        $eventos = $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM $tabla_eventos
             WHERE estado = 'publicado' AND fecha_inicio >= NOW()
             ORDER BY fecha_inicio ASC
             LIMIT %d",
            intval($atts['limite'])
        ));

        echo '<div class="flavor-eventos-proximos grid-' . esc_attr($atts['columnas']) . '">';
        foreach ($eventos as $evento) {
            $this->render_evento_card($evento);
        }
        echo '</div>';
    }

    /**
     * Renderizar eventos destacados
     */
    private function render_destacados($atts) {
        global $wpdb;
        $tabla_eventos = $wpdb->prefix . 'flavor_eventos';

        $eventos = $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM $tabla_eventos
             WHERE estado = 'publicado' AND fecha_inicio >= NOW() AND destacado = 1
             ORDER BY fecha_inicio ASC
             LIMIT %d",
            intval($atts['limite'])
        ));

        echo '<div class="flavor-eventos-destacados">';
        foreach ($eventos as $evento) {
            $this->render_evento_card($evento, true);
        }
        echo '</div>';
    }

    /**
     * Registrar tabs en el dashboard de usuario
     */
    public function registrar_tabs($tabs) {
        $tabs['eventos-inscripciones'] = [
            'titulo' => __('Mis Eventos', 'flavor-chat-ia'),
            'icono' => 'dashicons-calendar-alt',
            'callback' => [$this, 'render_tab_inscripciones'],
            'orden' => 25,
            'modulo' => 'eventos',
        ];

        return $tabs;
    }

    /**
     * Renderizar tab de inscripciones
     */
    public function render_tab_inscripciones() {
        $this->encolar_assets();
        $this->render_mis_inscripciones(['estado' => '', 'limite' => 20]);
    }

    /**
     * Cargar templates personalizados
     */
    public function cargar_template($template) {
        return $template;
    }

    /**
     * AJAX: Inscribirse a evento
     */
    public function ajax_inscribirse() {
        check_ajax_referer('eventos_nonce', 'nonce');

        $evento_id = isset($_POST['evento_id']) ? absint($_POST['evento_id']) : 0;
        $numero_plazas = isset($_POST['num_plazas']) ? absint($_POST['num_plazas']) : 1;
        $nombre_inscrito = isset($_POST['nombre']) ? sanitize_text_field($_POST['nombre']) : '';
        $email_inscrito = isset($_POST['email']) ? sanitize_email($_POST['email']) : '';
        $telefono_inscrito = isset($_POST['telefono']) ? sanitize_text_field($_POST['telefono']) : '';
        $notas_inscripcion = isset($_POST['notas']) ? sanitize_textarea_field($_POST['notas']) : '';
        $usuario_id = get_current_user_id();

        // Validar datos requeridos
        if (!$evento_id) {
            wp_send_json_error(__('Evento no valido', 'flavor-chat-ia'));
        }

        // Si el usuario esta logueado, obtener sus datos
        if ($usuario_id && (empty($nombre_inscrito) || empty($email_inscrito))) {
            $usuario_actual = wp_get_current_user();
            if (empty($nombre_inscrito)) {
                $nombre_inscrito = $usuario_actual->display_name;
            }
            if (empty($email_inscrito)) {
                $email_inscrito = $usuario_actual->user_email;
            }
        }

        // Validar nombre y email obligatorios
        if (empty($nombre_inscrito) || empty($email_inscrito)) {
            wp_send_json_error(__('Nombre y email son obligatorios', 'flavor-chat-ia'));
        }

        global $wpdb;
        $tabla_inscripciones = $wpdb->prefix . 'flavor_eventos_inscripciones';
        $tabla_eventos = $wpdb->prefix . 'flavor_eventos';

        // Verificar si ya esta inscrito (por user_id o por email)
        if ($usuario_id) {
            $inscripcion_existente = $wpdb->get_var($wpdb->prepare(
                "SELECT id FROM $tabla_inscripciones WHERE evento_id = %d AND user_id = %d AND estado != 'cancelada'",
                $evento_id,
                $usuario_id
            ));
        } else {
            $inscripcion_existente = $wpdb->get_var($wpdb->prepare(
                "SELECT id FROM $tabla_inscripciones WHERE evento_id = %d AND email = %s AND estado != 'cancelada'",
                $evento_id,
                $email_inscrito
            ));
        }

        if ($inscripcion_existente) {
            wp_send_json_error(__('Ya estas inscrito en este evento', 'flavor-chat-ia'));
        }

        // Verificar que el evento existe y esta publicado
        $evento = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM $tabla_eventos WHERE id = %d AND estado = 'publicado'",
            $evento_id
        ));

        if (!$evento) {
            wp_send_json_error(__('Evento no encontrado o no disponible', 'flavor-chat-ia'));
        }

        // Verificar plazas disponibles
        $total_inscritos = (int) $wpdb->get_var($wpdb->prepare(
            "SELECT COALESCE(SUM(num_plazas), 0) FROM $tabla_inscripciones WHERE evento_id = %d AND estado IN ('confirmada', 'pendiente')",
            $evento_id
        ));

        $estado_inscripcion = 'confirmada';
        $es_lista_espera = false;

        if ((int) $evento->aforo_maximo > 0) {
            $plazas_disponibles = (int) $evento->aforo_maximo - $total_inscritos;

            if ($plazas_disponibles < $numero_plazas) {
                // No hay suficientes plazas, verificar lista de espera
                $permite_lista_espera = true; // Por defecto permitir
                if (isset($evento->permite_lista_espera)) {
                    $permite_lista_espera = (bool) $evento->permite_lista_espera;
                }

                if ($permite_lista_espera) {
                    $estado_inscripcion = 'lista_espera';
                    $es_lista_espera = true;
                } else {
                    wp_send_json_error(__('No hay plazas disponibles', 'flavor-chat-ia'));
                }
            }
        }

        // Realizar inscripcion
        $datos_inscripcion = [
            'evento_id'  => $evento_id,
            'user_id'    => $usuario_id ?: null,
            'nombre'     => $nombre_inscrito,
            'email'      => $email_inscrito,
            'telefono'   => $telefono_inscrito,
            'num_plazas' => $numero_plazas,
            'estado'     => $estado_inscripcion,
            'notas'      => $notas_inscripcion,
        ];

        $resultado_insercion = $wpdb->insert($tabla_inscripciones, $datos_inscripcion);

        if ($resultado_insercion) {
            // Actualizar contador de inscritos en el evento
            $wpdb->query($wpdb->prepare(
                "UPDATE $tabla_eventos SET inscritos_count = inscritos_count + %d WHERE id = %d",
                $numero_plazas,
                $evento_id
            ));

            $mensaje_exito = $es_lista_espera
                ? __('Te has anadido a la lista de espera', 'flavor-chat-ia')
                : __('Te has inscrito correctamente', 'flavor-chat-ia');

            wp_send_json_success([
                'mensaje' => $mensaje_exito,
                'lista_espera' => $es_lista_espera,
                'inscripcion_id' => $wpdb->insert_id,
            ]);
        } else {
            wp_send_json_error(__('Error al procesar la inscripcion', 'flavor-chat-ia'));
        }
    }

    /**
     * AJAX: Cancelar inscripcion
     */
    public function ajax_cancelar_inscripcion() {
        check_ajax_referer('eventos_nonce', 'nonce');

        if (!is_user_logged_in()) {
            wp_send_json_error(__('Debes iniciar sesion', 'flavor-chat-ia'));
        }

        $inscripcion_id = isset($_POST['inscripcion_id']) ? absint($_POST['inscripcion_id']) : 0;
        $usuario_id = get_current_user_id();

        if (!$inscripcion_id) {
            wp_send_json_error(__('Inscripcion no valida', 'flavor-chat-ia'));
        }

        global $wpdb;
        $tabla_inscripciones = $wpdb->prefix . 'flavor_eventos_inscripciones';
        $tabla_eventos = $wpdb->prefix . 'flavor_eventos';

        // Obtener la inscripcion antes de cancelar para actualizar contador
        $inscripcion_actual = $wpdb->get_row($wpdb->prepare(
            "SELECT evento_id, num_plazas FROM $tabla_inscripciones WHERE id = %d AND user_id = %d AND estado != 'cancelada'",
            $inscripcion_id,
            $usuario_id
        ));

        if (!$inscripcion_actual) {
            wp_send_json_error(__('Inscripcion no encontrada o ya cancelada', 'flavor-chat-ia'));
        }

        $resultado_actualizacion = $wpdb->update(
            $tabla_inscripciones,
            ['estado' => 'cancelada', 'updated_at' => current_time('mysql')],
            ['id' => $inscripcion_id, 'user_id' => $usuario_id]
        );

        if ($resultado_actualizacion !== false) {
            // Decrementar contador de inscritos
            $wpdb->query($wpdb->prepare(
                "UPDATE $tabla_eventos SET inscritos_count = GREATEST(0, inscritos_count - %d) WHERE id = %d",
                $inscripcion_actual->num_plazas,
                $inscripcion_actual->evento_id
            ));

            wp_send_json_success(['mensaje' => __('Inscripcion cancelada correctamente', 'flavor-chat-ia')]);
        } else {
            wp_send_json_error(__('Error al cancelar', 'flavor-chat-ia'));
        }
    }

    /**
     * AJAX: Filtrar eventos
     */
    public function ajax_filtrar() {
        $tipo = isset($_POST['tipo']) ? sanitize_text_field($_POST['tipo']) : '';
        $categoria = isset($_POST['categoria']) ? sanitize_text_field($_POST['categoria']) : '';
        $busqueda = isset($_POST['busqueda']) ? sanitize_text_field($_POST['busqueda']) : '';
        $fecha_desde = isset($_POST['fecha_desde']) ? sanitize_text_field($_POST['fecha_desde']) : '';

        global $wpdb;
        $tabla_eventos = $wpdb->prefix . 'flavor_eventos';

        $where = ["estado = 'publicado'"];
        $params = [];

        if (!empty($tipo)) {
            $where[] = "tipo = %s";
            $params[] = $tipo;
        }

        if (!empty($categoria)) {
            $where[] = "categoria = %s";
            $params[] = $categoria;
        }

        if (!empty($busqueda)) {
            $where[] = "(titulo LIKE %s OR descripcion LIKE %s)";
            $like = '%' . $wpdb->esc_like($busqueda) . '%';
            $params[] = $like;
            $params[] = $like;
        }

        if (!empty($fecha_desde)) {
            $where[] = "fecha_inicio >= %s";
            $params[] = $fecha_desde;
        } else {
            $where[] = "fecha_inicio >= NOW()";
        }

        $sql = "SELECT * FROM $tabla_eventos WHERE " . implode(' AND ', $where) . " ORDER BY fecha_inicio ASC LIMIT 50";

        if (!empty($params)) {
            $eventos = $wpdb->get_results($wpdb->prepare($sql, ...$params));
        } else {
            $eventos = $wpdb->get_results($sql);
        }

        ob_start();
        if (!empty($eventos)) {
            foreach ($eventos as $evento) {
                $this->render_evento_card($evento);
            }
        } else {
            echo '<p class="no-resultados">' . __('No se encontraron eventos', 'flavor-chat-ia') . '</p>';
        }
        $html = ob_get_clean();

        wp_send_json_success(['html' => $html, 'total' => count($eventos)]);
    }

    /**
     * AJAX: Compartir evento
     */
    public function ajax_compartir() {
        check_ajax_referer('eventos_nonce', 'nonce');

        $evento_id = isset($_POST['evento_id']) ? absint($_POST['evento_id']) : 0;
        $red_social = isset($_POST['red']) ? sanitize_text_field($_POST['red']) : '';

        if (!$evento_id) {
            wp_send_json_error(__('Evento no válido', 'flavor-chat-ia'));
        }

        global $wpdb;
        $tabla_eventos = $wpdb->prefix . 'flavor_eventos';

        $evento = $wpdb->get_row($wpdb->prepare(
            "SELECT titulo, descripcion FROM $tabla_eventos WHERE id = %d",
            $evento_id
        ));

        if (!$evento) {
            wp_send_json_error(__('Evento no encontrado', 'flavor-chat-ia'));
        }

        $url = add_query_arg('evento_id', $evento_id, home_url('/mi-portal/eventos/detalle/'));
        $texto = urlencode($evento->titulo);

        $share_urls = [
            'facebook' => 'https://www.facebook.com/sharer/sharer.php?u=' . urlencode($url),
            'twitter' => 'https://twitter.com/intent/tweet?text=' . $texto . '&url=' . urlencode($url),
            'whatsapp' => 'https://wa.me/?text=' . $texto . '%20' . urlencode($url),
            'telegram' => 'https://t.me/share/url?url=' . urlencode($url) . '&text=' . $texto,
        ];

        if (isset($share_urls[$red_social])) {
            wp_send_json_success(['url' => $share_urls[$red_social]]);
        } else {
            wp_send_json_error(__('Red social no soportada', 'flavor-chat-ia'));
        }
    }

    /**
     * Renderizar tarjeta de evento
     */
    private function render_evento_card($evento, $destacado = false) {
        ?>
        <div class="flavor-evento-card <?php echo $destacado ? 'destacado' : ''; ?>" data-id="<?php echo esc_attr($evento->id); ?>">
            <?php if (!empty($evento->imagen)) : ?>
                <div class="evento-imagen">
                    <img src="<?php echo esc_url($evento->imagen); ?>" alt="<?php echo esc_attr($evento->titulo); ?>">
                </div>
            <?php endif; ?>
            <div class="evento-contenido">
                <div class="evento-meta">
                    <span class="fecha">
                        <strong><?php echo esc_html(date_i18n('d', strtotime($evento->fecha_inicio))); ?></strong>
                        <?php echo esc_html(date_i18n('M', strtotime($evento->fecha_inicio))); ?>
                    </span>
                    <?php if (!empty($evento->tipo)) : ?>
                        <span class="tipo"><?php echo esc_html(ucfirst($evento->tipo)); ?></span>
                    <?php endif; ?>
                </div>
                <h3><?php echo esc_html($evento->titulo); ?></h3>
                <p class="evento-ubicacion"><?php echo esc_html($evento->ubicacion); ?></p>
                <p class="evento-descripcion"><?php echo esc_html(wp_trim_words($evento->descripcion, 15)); ?></p>
                <div class="evento-acciones">
                    <a href="<?php echo esc_url(add_query_arg('evento_id', $evento->id, home_url('/mi-portal/eventos/detalle/'))); ?>" class="flavor-btn flavor-btn-primary">
                        <?php _e('Ver Evento', 'flavor-chat-ia'); ?>
                    </a>
                </div>
            </div>
        </div>
        <?php
    }

    /**
     * Shortcode: Panel de acciones rápidas de eventos
     *
     * @param array $atts Atributos del shortcode
     * @return string HTML del panel de acciones
     */
    public function shortcode_acciones($atts = []) {
        $this->encolar_assets();

        $atts = shortcode_atts([
            'mostrar_proximos' => 'true',
            'mostrar_mis_inscripciones' => 'true',
            'limite_proximos' => 3,
            'estilo' => 'completo', // completo, compacto
        ], $atts);

        global $wpdb;
        $tabla_eventos = $wpdb->prefix . 'flavor_eventos';
        $tabla_inscripciones = $wpdb->prefix . 'flavor_eventos_inscripciones';

        // Obtener próximos eventos
        $proximos_eventos = [];
        if ($atts['mostrar_proximos'] === 'true' && Flavor_Chat_Helpers::tabla_existe($tabla_eventos)) {
            $proximos_eventos = $wpdb->get_results($wpdb->prepare(
                "SELECT id, titulo, fecha_inicio, ubicacion, tipo
                 FROM $tabla_eventos
                 WHERE estado = 'publicado' AND fecha_inicio > NOW()
                 ORDER BY fecha_inicio ASC
                 LIMIT %d",
                absint($atts['limite_proximos'])
            ));
        }

        // Obtener inscripciones del usuario actual
        $mis_inscripciones = [];
        if ($atts['mostrar_mis_inscripciones'] === 'true' && is_user_logged_in() && Flavor_Chat_Helpers::tabla_existe($tabla_inscripciones)) {
            $usuario_id = get_current_user_id();
            $mis_inscripciones = $wpdb->get_results($wpdb->prepare(
                "SELECT i.*, e.titulo, e.fecha_inicio, e.ubicacion
                 FROM $tabla_inscripciones i
                 JOIN $tabla_eventos e ON i.evento_id = e.id
                 WHERE i.user_id = %d AND i.estado IN ('confirmada', 'pendiente') AND e.fecha_inicio > NOW()
                 ORDER BY e.fecha_inicio ASC
                 LIMIT 5",
                $usuario_id
            ));
        }

        ob_start();
        ?>
        <div class="flavor-eventos-acciones estilo-<?php echo esc_attr($atts['estilo']); ?>">
            <?php if (!empty($proximos_eventos)): ?>
            <div class="eventos-proximos-resumen">
                <h4><?php esc_html_e('Próximos eventos', 'flavor-chat-ia'); ?></h4>
                <div class="eventos-lista-mini">
                    <?php foreach ($proximos_eventos as $evento): ?>
                        <div class="evento-mini-card">
                            <div class="evento-fecha">
                                <span class="dia"><?php echo esc_html(date_i18n('d', strtotime($evento->fecha_inicio))); ?></span>
                                <span class="mes"><?php echo esc_html(date_i18n('M', strtotime($evento->fecha_inicio))); ?></span>
                            </div>
                            <div class="evento-info">
                                <strong><?php echo esc_html($evento->titulo); ?></strong>
                                <span class="ubicacion"><?php echo esc_html($evento->ubicacion); ?></span>
                            </div>
                            <a href="<?php echo esc_url(add_query_arg('evento_id', $evento->id, home_url('/mi-portal/eventos/detalle/'))); ?>" class="flavor-btn flavor-btn-small">
                                <?php esc_html_e('Ver', 'flavor-chat-ia'); ?>
                            </a>
                        </div>
                    <?php endforeach; ?>
                </div>
                <a href="<?php echo esc_url(home_url('/mi-portal/eventos/')); ?>" class="ver-todos-link">
                    <?php esc_html_e('Ver todos los eventos', 'flavor-chat-ia'); ?> &rarr;
                </a>
            </div>
            <?php endif; ?>

            <?php if (is_user_logged_in() && !empty($mis_inscripciones)): ?>
            <div class="mis-inscripciones-resumen">
                <h4><?php esc_html_e('Mis inscripciones', 'flavor-chat-ia'); ?></h4>
                <div class="inscripciones-lista-mini">
                    <?php foreach ($mis_inscripciones as $inscripcion): ?>
                        <div class="inscripcion-mini-card">
                            <div class="evento-info">
                                <strong><?php echo esc_html($inscripcion->titulo); ?></strong>
                                <span class="fecha"><?php echo esc_html(date_i18n('d M Y - H:i', strtotime($inscripcion->fecha_inicio))); ?></span>
                            </div>
                            <span class="estado-badge estado-<?php echo esc_attr($inscripcion->estado); ?>">
                                <?php echo esc_html($inscripcion->estado === 'confirmada' ? __('Confirmada', 'flavor-chat-ia') : __('Pendiente', 'flavor-chat-ia')); ?>
                            </span>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
            <?php elseif (is_user_logged_in()): ?>
            <div class="sin-inscripciones">
                <p><?php esc_html_e('No tienes inscripciones a próximos eventos.', 'flavor-chat-ia'); ?></p>
            </div>
            <?php endif; ?>

            <div class="eventos-acciones-botones">
                <a href="<?php echo esc_url(home_url('/mi-portal/eventos/')); ?>" class="flavor-btn flavor-btn-primary">
                    <span class="dashicons dashicons-calendar-alt"></span>
                    <?php esc_html_e('Explorar Eventos', 'flavor-chat-ia'); ?>
                </a>
                <?php if (is_user_logged_in()): ?>
                <a href="<?php echo esc_url(home_url('/mi-portal/eventos/mis-inscripciones/')); ?>" class="flavor-btn flavor-btn-secondary">
                    <span class="dashicons dashicons-list-view"></span>
                    <?php esc_html_e('Mis Inscripciones', 'flavor-chat-ia'); ?>
                </a>
                <?php endif; ?>
            </div>
        </div>
        <?php
        return ob_get_clean();
    }

    /**
     * Shortcode: Formulario de inscripcion a evento
     *
     * Uso: [eventos_inscribirse id="X"]
     *
     * @param array $atributos_shortcode Atributos del shortcode
     * @return string HTML del formulario de inscripcion
     */
    public function shortcode_inscribirse($atributos_shortcode = []) {
        $this->encolar_assets();

        $atributos_shortcode = shortcode_atts([
            'id' => 0,
            'mostrar_evento' => 'true',
            'redirigir' => '',
        ], $atributos_shortcode);

        $evento_id = absint($atributos_shortcode['id']);
        if (!$evento_id) {
            $evento_id = isset($_GET['evento_id']) ? absint($_GET['evento_id']) : 0;
        }

        if (!$evento_id) {
            return '<div class="flavor-error">' . esc_html__('Evento no especificado.', 'flavor-chat-ia') . '</div>';
        }

        global $wpdb;
        $tabla_eventos = $wpdb->prefix . 'flavor_eventos';
        $tabla_inscripciones = $wpdb->prefix . 'flavor_eventos_inscripciones';

        if (!Flavor_Chat_Helpers::tabla_existe($tabla_eventos)) {
            return '<div class="flavor-error">' . esc_html__('El modulo de eventos no esta configurado.', 'flavor-chat-ia') . '</div>';
        }

        // Obtener datos del evento
        $evento = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM $tabla_eventos WHERE id = %d AND estado = 'publicado'",
            $evento_id
        ));

        if (!$evento) {
            return '<div class="flavor-error">' . esc_html__('Evento no encontrado o no disponible.', 'flavor-chat-ia') . '</div>';
        }

        // Verificar si el evento ya paso
        $fecha_evento = strtotime($evento->fecha_inicio);
        if ($fecha_evento < time()) {
            return '<div class="flavor-warning">' . esc_html__('Este evento ya ha finalizado.', 'flavor-chat-ia') . '</div>';
        }

        // Calcular plazas disponibles
        $inscritos_actuales = (int) $wpdb->get_var($wpdb->prepare(
            "SELECT COALESCE(SUM(num_plazas), 0) FROM $tabla_inscripciones WHERE evento_id = %d AND estado IN ('confirmada', 'pendiente')",
            $evento_id
        ));

        $aforo_maximo = (int) $evento->aforo_maximo;
        $plazas_disponibles = ($aforo_maximo > 0) ? max(0, $aforo_maximo - $inscritos_actuales) : -1;
        $evento_lleno = ($aforo_maximo > 0 && $plazas_disponibles <= 0);

        // Verificar si el usuario ya esta inscrito
        $usuario_inscrito = false;
        $inscripcion_existente = null;
        if (is_user_logged_in()) {
            $usuario_id = get_current_user_id();
            $inscripcion_existente = $wpdb->get_row($wpdb->prepare(
                "SELECT * FROM $tabla_inscripciones WHERE evento_id = %d AND user_id = %d AND estado != 'cancelada'",
                $evento_id,
                $usuario_id
            ));
            $usuario_inscrito = !empty($inscripcion_existente);
        }

        // Datos del usuario logueado
        $usuario_actual = wp_get_current_user();
        $nombre_usuario = $usuario_actual->ID ? $usuario_actual->display_name : '';
        $email_usuario = $usuario_actual->ID ? $usuario_actual->user_email : '';

        ob_start();
        ?>
        <div class="flavor-eventos-inscripcion-wrapper" data-evento-id="<?php echo esc_attr($evento_id); ?>">

            <?php if ($atributos_shortcode['mostrar_evento'] === 'true') : ?>
            <div class="evento-resumen-card">
                <?php if (!empty($evento->imagen)) : ?>
                    <div class="evento-imagen-mini">
                        <img src="<?php echo esc_url($evento->imagen); ?>" alt="<?php echo esc_attr($evento->titulo); ?>">
                    </div>
                <?php endif; ?>
                <div class="evento-info-resumen">
                    <h3><?php echo esc_html($evento->titulo); ?></h3>
                    <div class="evento-meta-inline">
                        <span class="fecha-hora">
                            <span class="dashicons dashicons-calendar-alt"></span>
                            <?php echo esc_html(date_i18n('l, j \d\e F Y - H:i', strtotime($evento->fecha_inicio))); ?>
                        </span>
                        <?php if (!empty($evento->ubicacion)) : ?>
                            <span class="ubicacion">
                                <span class="dashicons dashicons-location"></span>
                                <?php echo esc_html($evento->ubicacion); ?>
                            </span>
                        <?php endif; ?>
                        <?php if ((float) $evento->precio > 0) : ?>
                            <span class="precio">
                                <span class="dashicons dashicons-tickets-alt"></span>
                                <?php echo esc_html(number_format((float) $evento->precio, 2, ',', '.') . ' EUR'); ?>
                            </span>
                        <?php else : ?>
                            <span class="precio gratuito">
                                <span class="dashicons dashicons-yes-alt"></span>
                                <?php esc_html_e('Gratuito', 'flavor-chat-ia'); ?>
                            </span>
                        <?php endif; ?>
                    </div>
                    <?php if ($aforo_maximo > 0) : ?>
                        <div class="plazas-info">
                            <div class="plazas-barra" style="--plazas-porcentaje: <?php echo esc_attr(min(100, ($inscritos_actuales / $aforo_maximo) * 100)); ?>%;">
                                <div class="plazas-ocupadas"></div>
                            </div>
                            <span class="plazas-texto">
                                <?php
                                if ($plazas_disponibles > 0) {
                                    printf(
                                        esc_html__('%d plazas disponibles de %d', 'flavor-chat-ia'),
                                        $plazas_disponibles,
                                        $aforo_maximo
                                    );
                                } else {
                                    esc_html_e('Evento completo', 'flavor-chat-ia');
                                }
                                ?>
                            </span>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
            <?php endif; ?>

            <?php if ($usuario_inscrito && $inscripcion_existente) : ?>
                <div class="flavor-notice flavor-notice-success">
                    <span class="dashicons dashicons-yes-alt"></span>
                    <div class="notice-content">
                        <strong><?php esc_html_e('Ya estas inscrito en este evento', 'flavor-chat-ia'); ?></strong>
                        <p>
                            <?php
                            printf(
                                esc_html__('Estado: %s | Plazas reservadas: %d', 'flavor-chat-ia'),
                                ucfirst($inscripcion_existente->estado),
                                $inscripcion_existente->num_plazas
                            );
                            ?>
                        </p>
                        <button type="button" class="flavor-btn flavor-btn-danger flavor-btn-small btn-cancelar-inscripcion" data-inscripcion-id="<?php echo esc_attr($inscripcion_existente->id); ?>">
                            <span class="dashicons dashicons-dismiss"></span>
                            <?php esc_html_e('Cancelar inscripcion', 'flavor-chat-ia'); ?>
                        </button>
                    </div>
                </div>

            <?php elseif ($evento_lleno) : ?>
                <div class="flavor-notice flavor-notice-warning">
                    <span class="dashicons dashicons-warning"></span>
                    <div class="notice-content">
                        <strong><?php esc_html_e('Evento completo', 'flavor-chat-ia'); ?></strong>
                        <p><?php esc_html_e('No hay plazas disponibles. Puedes inscribirte en la lista de espera.', 'flavor-chat-ia'); ?></p>
                    </div>
                </div>
                <?php $this->render_formulario_inscripcion($evento, $nombre_usuario, $email_usuario, true); ?>

            <?php else : ?>
                <?php $this->render_formulario_inscripcion($evento, $nombre_usuario, $email_usuario, false); ?>
            <?php endif; ?>

        </div>

        <style>
        .flavor-eventos-inscripcion-wrapper {
            max-width: 600px;
            margin: 0 auto;
        }
        .evento-resumen-card {
            display: flex;
            gap: 20px;
            padding: 20px;
            background: #f8fafc;
            border-radius: 12px;
            margin-bottom: 24px;
            border: 1px solid #e2e8f0;
        }
        .evento-imagen-mini {
            flex-shrink: 0;
            width: 120px;
            height: 120px;
            border-radius: 8px;
            overflow: hidden;
        }
        .evento-imagen-mini img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }
        .evento-info-resumen h3 {
            margin: 0 0 12px;
            font-size: 1.25rem;
            color: #1e293b;
        }
        .evento-meta-inline {
            display: flex;
            flex-wrap: wrap;
            gap: 12px;
            font-size: 0.875rem;
            color: #64748b;
        }
        .evento-meta-inline span {
            display: inline-flex;
            align-items: center;
            gap: 4px;
        }
        .evento-meta-inline .dashicons {
            font-size: 16px;
            width: 16px;
            height: 16px;
        }
        .precio.gratuito {
            color: #059669;
            font-weight: 600;
        }
        .plazas-info {
            margin-top: 12px;
        }
        .plazas-barra {
            height: 6px;
            background: #e2e8f0;
            border-radius: 3px;
            overflow: hidden;
            margin-bottom: 6px;
        }
        .plazas-ocupadas {
            height: 100%;
            width: var(--plazas-porcentaje);
            background: linear-gradient(90deg, #3b82f6, #8b5cf6);
            border-radius: 3px;
            transition: width 0.3s ease;
        }
        .plazas-texto {
            font-size: 0.75rem;
            color: #64748b;
        }
        .flavor-notice {
            display: flex;
            gap: 12px;
            padding: 16px;
            border-radius: 8px;
            margin-bottom: 20px;
        }
        .flavor-notice-success {
            background: #ecfdf5;
            border: 1px solid #a7f3d0;
            color: #065f46;
        }
        .flavor-notice-warning {
            background: #fffbeb;
            border: 1px solid #fcd34d;
            color: #92400e;
        }
        .flavor-notice .dashicons {
            font-size: 24px;
            width: 24px;
            height: 24px;
            flex-shrink: 0;
        }
        .notice-content strong {
            display: block;
            margin-bottom: 4px;
        }
        .notice-content p {
            margin: 0 0 12px;
            font-size: 0.875rem;
        }
        .formulario-inscripcion {
            background: #ffffff;
            padding: 24px;
            border-radius: 12px;
            border: 1px solid #e2e8f0;
        }
        .formulario-inscripcion h4 {
            margin: 0 0 20px;
            padding-bottom: 12px;
            border-bottom: 2px solid #e2e8f0;
            color: #1e293b;
        }
        .campo-formulario {
            margin-bottom: 16px;
        }
        .campo-formulario label {
            display: block;
            margin-bottom: 6px;
            font-weight: 500;
            color: #374151;
            font-size: 0.875rem;
        }
        .campo-formulario label .requerido {
            color: #ef4444;
        }
        .campo-formulario input,
        .campo-formulario select,
        .campo-formulario textarea {
            width: 100%;
            padding: 10px 14px;
            border: 1px solid #d1d5db;
            border-radius: 6px;
            font-size: 1rem;
            transition: border-color 0.2s, box-shadow 0.2s;
        }
        .campo-formulario input:focus,
        .campo-formulario select:focus,
        .campo-formulario textarea:focus {
            outline: none;
            border-color: #3b82f6;
            box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
        }
        .campo-formulario textarea {
            min-height: 80px;
            resize: vertical;
        }
        .campo-formulario .ayuda {
            font-size: 0.75rem;
            color: #6b7280;
            margin-top: 4px;
        }
        .campos-fila {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 16px;
        }
        .btn-inscribirse {
            width: 100%;
            padding: 14px 24px;
            font-size: 1rem;
            font-weight: 600;
            margin-top: 8px;
        }
        .btn-inscribirse.lista-espera {
            background: #f59e0b;
            border-color: #f59e0b;
        }
        .btn-inscribirse.lista-espera:hover {
            background: #d97706;
            border-color: #d97706;
        }
        .flavor-btn-danger {
            background: #ef4444;
            border-color: #ef4444;
            color: #fff;
        }
        .flavor-btn-danger:hover {
            background: #dc2626;
            border-color: #dc2626;
        }
        @media (max-width: 600px) {
            .evento-resumen-card {
                flex-direction: column;
            }
            .evento-imagen-mini {
                width: 100%;
                height: 160px;
            }
            .campos-fila {
                grid-template-columns: 1fr;
            }
        }
        </style>

        <script>
        jQuery(document).ready(function($) {
            // Manejar envio del formulario de inscripcion
            $('.form-inscripcion-evento').on('submit', function(e) {
                e.preventDefault();

                var $formulario = $(this);
                var $botonEnviar = $formulario.find('.btn-inscribirse');
                var textoOriginal = $botonEnviar.html();

                $botonEnviar.prop('disabled', true).html('<span class="dashicons dashicons-update spin"></span> <?php esc_html_e('Procesando...', 'flavor-chat-ia'); ?>');

                $.ajax({
                    url: flavorEventos.ajaxUrl,
                    type: 'POST',
                    data: {
                        action: 'eventos_inscribirse',
                        nonce: flavorEventos.nonce,
                        evento_id: $formulario.find('[name="evento_id"]').val(),
                        nombre: $formulario.find('[name="nombre"]').val(),
                        email: $formulario.find('[name="email"]').val(),
                        telefono: $formulario.find('[name="telefono"]').val(),
                        num_plazas: $formulario.find('[name="num_plazas"]').val(),
                        notas: $formulario.find('[name="notas"]').val()
                    },
                    success: function(respuesta) {
                        if (respuesta.success) {
                            $formulario.html('<div class="flavor-notice flavor-notice-success"><span class="dashicons dashicons-yes-alt"></span><div class="notice-content"><strong>' + respuesta.data.mensaje + '</strong></div></div>');
                            setTimeout(function() {
                                location.reload();
                            }, 2000);
                        } else {
                            alert(respuesta.data || flavorEventos.i18n.error);
                            $botonEnviar.prop('disabled', false).html(textoOriginal);
                        }
                    },
                    error: function() {
                        alert(flavorEventos.i18n.error);
                        $botonEnviar.prop('disabled', false).html(textoOriginal);
                    }
                });
            });

            // Manejar cancelacion de inscripcion
            $('.btn-cancelar-inscripcion').on('click', function() {
                if (!confirm('<?php esc_html_e('Estas seguro de que quieres cancelar tu inscripcion?', 'flavor-chat-ia'); ?>')) {
                    return;
                }

                var $boton = $(this);
                var inscripcionId = $boton.data('inscripcion-id');

                $boton.prop('disabled', true).html('<span class="dashicons dashicons-update spin"></span>');

                $.ajax({
                    url: flavorEventos.ajaxUrl,
                    type: 'POST',
                    data: {
                        action: 'eventos_cancelar_inscripcion',
                        nonce: flavorEventos.nonce,
                        inscripcion_id: inscripcionId
                    },
                    success: function(respuesta) {
                        if (respuesta.success) {
                            location.reload();
                        } else {
                            alert(respuesta.data || flavorEventos.i18n.error);
                            $boton.prop('disabled', false).html('<?php esc_html_e('Cancelar inscripcion', 'flavor-chat-ia'); ?>');
                        }
                    },
                    error: function() {
                        alert(flavorEventos.i18n.error);
                        $boton.prop('disabled', false).html('<?php esc_html_e('Cancelar inscripcion', 'flavor-chat-ia'); ?>');
                    }
                });
            });
        });
        </script>
        <?php
        return ob_get_clean();
    }

    /**
     * Renderizar formulario de inscripcion
     *
     * @param object $evento Datos del evento
     * @param string $nombre_usuario Nombre del usuario actual
     * @param string $email_usuario Email del usuario actual
     * @param bool $es_lista_espera Si es inscripcion en lista de espera
     */
    private function render_formulario_inscripcion($evento, $nombre_usuario, $email_usuario, $es_lista_espera = false) {
        ?>
        <form class="formulario-inscripcion form-inscripcion-evento" method="post">
            <input type="hidden" name="evento_id" value="<?php echo esc_attr($evento->id); ?>">

            <h4>
                <?php
                if ($es_lista_espera) {
                    esc_html_e('Inscribirse en lista de espera', 'flavor-chat-ia');
                } else {
                    esc_html_e('Formulario de inscripcion', 'flavor-chat-ia');
                }
                ?>
            </h4>

            <div class="campos-fila">
                <div class="campo-formulario">
                    <label for="insc-nombre">
                        <?php esc_html_e('Nombre completo', 'flavor-chat-ia'); ?>
                        <span class="requerido">*</span>
                    </label>
                    <input type="text" id="insc-nombre" name="nombre" value="<?php echo esc_attr($nombre_usuario); ?>" required>
                </div>

                <div class="campo-formulario">
                    <label for="insc-email">
                        <?php esc_html_e('Email', 'flavor-chat-ia'); ?>
                        <span class="requerido">*</span>
                    </label>
                    <input type="email" id="insc-email" name="email" value="<?php echo esc_attr($email_usuario); ?>" required>
                </div>
            </div>

            <div class="campos-fila">
                <div class="campo-formulario">
                    <label for="insc-telefono">
                        <?php esc_html_e('Telefono', 'flavor-chat-ia'); ?>
                    </label>
                    <input type="tel" id="insc-telefono" name="telefono" placeholder="<?php esc_attr_e('Opcional', 'flavor-chat-ia'); ?>">
                </div>

                <div class="campo-formulario">
                    <label for="insc-plazas">
                        <?php esc_html_e('Numero de plazas', 'flavor-chat-ia'); ?>
                    </label>
                    <select id="insc-plazas" name="num_plazas">
                        <?php for ($contador_plazas = 1; $contador_plazas <= 5; $contador_plazas++) : ?>
                            <option value="<?php echo esc_attr($contador_plazas); ?>"><?php echo esc_html($contador_plazas); ?></option>
                        <?php endfor; ?>
                    </select>
                    <span class="ayuda"><?php esc_html_e('Maximo 5 plazas por inscripcion', 'flavor-chat-ia'); ?></span>
                </div>
            </div>

            <div class="campo-formulario">
                <label for="insc-notas">
                    <?php esc_html_e('Notas adicionales', 'flavor-chat-ia'); ?>
                </label>
                <textarea id="insc-notas" name="notas" placeholder="<?php esc_attr_e('Alergias, necesidades especiales, comentarios...', 'flavor-chat-ia'); ?>"></textarea>
            </div>

            <button type="submit" class="flavor-btn flavor-btn-primary btn-inscribirse <?php echo $es_lista_espera ? 'lista-espera' : ''; ?>">
                <span class="dashicons dashicons-yes"></span>
                <?php
                if ($es_lista_espera) {
                    esc_html_e('Unirme a la lista de espera', 'flavor-chat-ia');
                } else {
                    esc_html_e('Confirmar inscripcion', 'flavor-chat-ia');
                }
                ?>
            </button>

            <?php if (!is_user_logged_in()) : ?>
                <p class="ayuda" style="text-align: center; margin-top: 12px;">
                    <?php
                    printf(
                        esc_html__('Ya tienes cuenta? %sInicia sesion%s para inscribirte mas rapido.', 'flavor-chat-ia'),
                        '<a href="' . esc_url(wp_login_url(flavor_current_request_url())) . '">',
                        '</a>'
                    );
                    ?>
                </p>
            <?php endif; ?>
        </form>
        <?php
    }

    /**
     * Shortcode: Mapa de eventos
     *
     * Uso: [eventos_mapa limite="20" altura="400"]
     *
     * @param array $atributos_mapa Atributos del shortcode
     * @return string HTML del mapa de eventos
     */
    public function shortcode_mapa($atributos_mapa = []) {
        $this->encolar_assets();

        $atributos_mapa = shortcode_atts([
            'limite' => 50,
            'altura' => 450,
            'tipo' => '',
            'mostrar_lista' => 'true',
            'centro_lat' => '',
            'centro_lng' => '',
            'zoom' => 12,
        ], $atributos_mapa);

        global $wpdb;
        $tabla_eventos = $wpdb->prefix . 'flavor_eventos';

        if (!Flavor_Chat_Helpers::tabla_existe($tabla_eventos)) {
            return '<div class="flavor-error">' . esc_html__('El modulo de eventos no esta configurado.', 'flavor-chat-ia') . '</div>';
        }

        // Construir consulta
        $condiciones_where = ["estado = 'publicado'", "fecha_inicio >= NOW()"];
        $parametros_consulta = [];

        // Filtrar por tipo si se especifica
        if (!empty($atributos_mapa['tipo'])) {
            $condiciones_where[] = "tipo = %s";
            $parametros_consulta[] = sanitize_text_field($atributos_mapa['tipo']);
        }

        // Filtrar solo eventos con coordenadas
        $condiciones_where[] = "coordenadas_lat IS NOT NULL AND coordenadas_lng IS NOT NULL";

        $consulta_sql = "SELECT id, titulo, descripcion, tipo, fecha_inicio, fecha_fin, ubicacion, direccion,
                                coordenadas_lat, coordenadas_lng, imagen, precio
                         FROM $tabla_eventos
                         WHERE " . implode(' AND ', $condiciones_where) . "
                         ORDER BY fecha_inicio ASC
                         LIMIT %d";
        $parametros_consulta[] = absint($atributos_mapa['limite']);

        $eventos_con_ubicacion = $wpdb->get_results($wpdb->prepare($consulta_sql, ...$parametros_consulta));

        // Preparar datos de marcadores para JavaScript
        $marcadores_json = [];
        foreach ($eventos_con_ubicacion as $evento) {
            if (!empty($evento->coordenadas_lat) && !empty($evento->coordenadas_lng)) {
                $marcadores_json[] = [
                    'id' => (int) $evento->id,
                    'titulo' => esc_html($evento->titulo),
                    'tipo' => esc_html($evento->tipo),
                    'fecha' => date_i18n('d M Y - H:i', strtotime($evento->fecha_inicio)),
                    'ubicacion' => esc_html($evento->ubicacion),
                    'direccion' => esc_html($evento->direccion),
                    'lat' => (float) $evento->coordenadas_lat,
                    'lng' => (float) $evento->coordenadas_lng,
                    'imagen' => esc_url($evento->imagen),
                    'precio' => (float) $evento->precio,
                    'url' => add_query_arg('evento_id', $evento->id, home_url('/mi-portal/eventos/detalle/')),
                ];
            }
        }

        // Calcular centro del mapa si no se especifica
        $centro_latitud = !empty($atributos_mapa['centro_lat']) ? (float) $atributos_mapa['centro_lat'] : 40.4168;
        $centro_longitud = !empty($atributos_mapa['centro_lng']) ? (float) $atributos_mapa['centro_lng'] : -3.7038;

        if (empty($atributos_mapa['centro_lat']) && !empty($marcadores_json)) {
            $suma_latitudes = 0;
            $suma_longitudes = 0;
            foreach ($marcadores_json as $marcador) {
                $suma_latitudes += $marcador['lat'];
                $suma_longitudes += $marcador['lng'];
            }
            $centro_latitud = $suma_latitudes / count($marcadores_json);
            $centro_longitud = $suma_longitudes / count($marcadores_json);
        }

        $id_mapa_unico = 'eventos-mapa-' . wp_rand(1000, 9999);

        ob_start();
        ?>
        <div class="flavor-eventos-mapa-wrapper">
            <div id="<?php echo esc_attr($id_mapa_unico); ?>" class="flavor-eventos-mapa" style="height: <?php echo esc_attr($atributos_mapa['altura']); ?>px;">
                <!-- El mapa se renderizara aqui -->
                <div class="mapa-placeholder">
                    <span class="dashicons dashicons-location-alt"></span>
                    <p><?php esc_html_e('Cargando mapa...', 'flavor-chat-ia'); ?></p>
                </div>
            </div>

            <?php if ($atributos_mapa['mostrar_lista'] === 'true' && !empty($eventos_con_ubicacion)) : ?>
            <div class="eventos-lista-lateral">
                <h4><?php esc_html_e('Eventos en el mapa', 'flavor-chat-ia'); ?> <span class="contador">(<?php echo count($eventos_con_ubicacion); ?>)</span></h4>
                <div class="eventos-scroll-lista">
                    <?php foreach ($eventos_con_ubicacion as $evento) : ?>
                        <div class="evento-mapa-item" data-lat="<?php echo esc_attr($evento->coordenadas_lat); ?>" data-lng="<?php echo esc_attr($evento->coordenadas_lng); ?>" data-id="<?php echo esc_attr($evento->id); ?>">
                            <div class="evento-fecha-mini">
                                <span class="dia"><?php echo esc_html(date_i18n('d', strtotime($evento->fecha_inicio))); ?></span>
                                <span class="mes"><?php echo esc_html(date_i18n('M', strtotime($evento->fecha_inicio))); ?></span>
                            </div>
                            <div class="evento-info-mini">
                                <strong><?php echo esc_html($evento->titulo); ?></strong>
                                <span class="ubicacion-texto"><?php echo esc_html($evento->ubicacion); ?></span>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
            <?php endif; ?>

            <?php if (empty($eventos_con_ubicacion)) : ?>
            <div class="sin-eventos-mapa">
                <span class="dashicons dashicons-calendar"></span>
                <p><?php esc_html_e('No hay eventos con ubicacion disponibles.', 'flavor-chat-ia'); ?></p>
            </div>
            <?php endif; ?>
        </div>

        <style>
        .flavor-eventos-mapa-wrapper {
            display: grid;
            grid-template-columns: 1fr 300px;
            gap: 20px;
            margin: 20px 0;
        }
        .flavor-eventos-mapa {
            border-radius: 12px;
            overflow: hidden;
            background: #f1f5f9;
            position: relative;
        }
        .mapa-placeholder {
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            text-align: center;
            color: #64748b;
        }
        .mapa-placeholder .dashicons {
            font-size: 48px;
            width: 48px;
            height: 48px;
            margin-bottom: 12px;
        }
        .eventos-lista-lateral {
            background: #f8fafc;
            border-radius: 12px;
            padding: 16px;
            border: 1px solid #e2e8f0;
        }
        .eventos-lista-lateral h4 {
            margin: 0 0 12px;
            font-size: 1rem;
            color: #1e293b;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .eventos-lista-lateral .contador {
            font-weight: normal;
            color: #64748b;
            font-size: 0.875rem;
        }
        .eventos-scroll-lista {
            max-height: 380px;
            overflow-y: auto;
        }
        .evento-mapa-item {
            display: flex;
            gap: 12px;
            padding: 12px;
            background: #fff;
            border-radius: 8px;
            margin-bottom: 8px;
            cursor: pointer;
            transition: all 0.2s ease;
            border: 1px solid transparent;
        }
        .evento-mapa-item:hover {
            border-color: #3b82f6;
            box-shadow: 0 2px 8px rgba(59, 130, 246, 0.15);
        }
        .evento-mapa-item.activo {
            border-color: #3b82f6;
            background: #eff6ff;
        }
        .evento-fecha-mini {
            flex-shrink: 0;
            width: 44px;
            text-align: center;
            background: linear-gradient(135deg, #3b82f6, #8b5cf6);
            color: #fff;
            border-radius: 6px;
            padding: 6px 4px;
        }
        .evento-fecha-mini .dia {
            display: block;
            font-size: 1.125rem;
            font-weight: 700;
            line-height: 1;
        }
        .evento-fecha-mini .mes {
            display: block;
            font-size: 0.625rem;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        .evento-info-mini strong {
            display: block;
            font-size: 0.875rem;
            color: #1e293b;
            line-height: 1.3;
        }
        .evento-info-mini .ubicacion-texto {
            font-size: 0.75rem;
            color: #64748b;
        }
        .sin-eventos-mapa {
            grid-column: 1 / -1;
            text-align: center;
            padding: 40px;
            background: #f8fafc;
            border-radius: 12px;
            color: #64748b;
        }
        .sin-eventos-mapa .dashicons {
            font-size: 48px;
            width: 48px;
            height: 48px;
            margin-bottom: 12px;
        }
        @media (max-width: 900px) {
            .flavor-eventos-mapa-wrapper {
                grid-template-columns: 1fr;
            }
            .eventos-lista-lateral {
                order: 2;
            }
            .eventos-scroll-lista {
                max-height: 250px;
            }
        }
        </style>

        <script>
        jQuery(document).ready(function($) {
            var mapaContenedor = document.getElementById('<?php echo esc_js($id_mapa_unico); ?>');
            var marcadoresEventos = <?php echo wp_json_encode($marcadores_json); ?>;
            var centroMapa = { lat: <?php echo esc_js($centro_latitud); ?>, lng: <?php echo esc_js($centro_longitud); ?> };
            var nivelZoom = <?php echo esc_js((int) $atributos_mapa['zoom']); ?>;

            // Intentar usar Leaflet si esta disponible
            if (typeof L !== 'undefined') {
                inicializarMapaLeaflet();
            }
            // Intentar usar Google Maps si esta disponible
            else if (typeof google !== 'undefined' && typeof google.maps !== 'undefined') {
                inicializarGoogleMaps();
            }
            // Mostrar mensaje si no hay libreria de mapas
            else {
                mapaContenedor.innerHTML = '<div class="mapa-no-disponible" style="display:flex;flex-direction:column;align-items:center;justify-content:center;height:100%;background:#f1f5f9;color:#64748b;"><span class="dashicons dashicons-location-alt" style="font-size:48px;margin-bottom:12px;"></span><p><?php esc_html_e('Mapa no disponible. Configura Leaflet o Google Maps.', 'flavor-chat-ia'); ?></p></div>';
            }

            function inicializarMapaLeaflet() {
                var mapa = L.map('<?php echo esc_js($id_mapa_unico); ?>').setView([centroMapa.lat, centroMapa.lng], nivelZoom);

                L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                    attribution: '&copy; OpenStreetMap contributors'
                }).addTo(mapa);

                var marcadoresGrupo = [];
                marcadoresEventos.forEach(function(evento) {
                    var popupContenido = '<div class="popup-evento">' +
                        '<strong>' + evento.titulo + '</strong><br>' +
                        '<small>' + evento.fecha + '</small><br>' +
                        '<small>' + evento.ubicacion + '</small><br>' +
                        '<a href="' + evento.url + '" class="ver-evento-link">Ver evento</a>' +
                        '</div>';

                    var marcador = L.marker([evento.lat, evento.lng])
                        .addTo(mapa)
                        .bindPopup(popupContenido);

                    marcadoresGrupo.push(marcador);
                });

                // Ajustar vista a todos los marcadores
                if (marcadoresGrupo.length > 0) {
                    var grupo = L.featureGroup(marcadoresGrupo);
                    mapa.fitBounds(grupo.getBounds().pad(0.1));
                }

                // Click en lista lateral
                $('.evento-mapa-item').on('click', function() {
                    var lat = parseFloat($(this).data('lat'));
                    var lng = parseFloat($(this).data('lng'));
                    mapa.setView([lat, lng], 15);
                    $('.evento-mapa-item').removeClass('activo');
                    $(this).addClass('activo');
                });
            }

            function inicializarGoogleMaps() {
                var mapa = new google.maps.Map(mapaContenedor, {
                    center: centroMapa,
                    zoom: nivelZoom,
                    mapTypeControl: false,
                    streetViewControl: false
                });

                var infoWindow = new google.maps.InfoWindow();
                var bounds = new google.maps.LatLngBounds();

                marcadoresEventos.forEach(function(evento) {
                    var posicion = { lat: evento.lat, lng: evento.lng };
                    var marcador = new google.maps.Marker({
                        position: posicion,
                        map: mapa,
                        title: evento.titulo
                    });

                    bounds.extend(posicion);

                    marcador.addListener('click', function() {
                        var contenido = '<div class="popup-evento">' +
                            '<strong>' + evento.titulo + '</strong><br>' +
                            '<small>' + evento.fecha + '</small><br>' +
                            '<small>' + evento.ubicacion + '</small><br>' +
                            '<a href="' + evento.url + '">Ver evento</a>' +
                            '</div>';
                        infoWindow.setContent(contenido);
                        infoWindow.open(mapa, marcador);
                    });
                });

                if (marcadoresEventos.length > 0) {
                    mapa.fitBounds(bounds);
                }

                // Click en lista lateral
                $('.evento-mapa-item').on('click', function() {
                    var lat = parseFloat($(this).data('lat'));
                    var lng = parseFloat($(this).data('lng'));
                    mapa.setCenter({ lat: lat, lng: lng });
                    mapa.setZoom(15);
                    $('.evento-mapa-item').removeClass('activo');
                    $(this).addClass('activo');
                });
            }
        });
        </script>
        <?php
        return ob_get_clean();
    }
}

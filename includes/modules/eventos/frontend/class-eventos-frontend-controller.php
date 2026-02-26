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
     * Registrar assets CSS y JS
     */
    public function registrar_assets() {
        $base_url = plugins_url('', dirname(__FILE__));
        $version = FLAVOR_CHAT_IA_VERSION ?? '1.0.0';

        // CSS
        wp_register_style(
            'flavor-eventos',
            $base_url . '/assets/css/eventos.css',
            [],
            $version
        );

        // JS
        wp_register_script(
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
        add_shortcode('eventos_listado', [$this, 'shortcode_listado']);
        add_shortcode('eventos_calendario', [$this, 'shortcode_calendario']);
        add_shortcode('eventos_mis_inscripciones', [$this, 'shortcode_mis_inscripciones']);
        add_shortcode('eventos_detalle', [$this, 'shortcode_detalle']);
        add_shortcode('eventos_proximos', [$this, 'shortcode_proximos']);
        add_shortcode('eventos_destacados', [$this, 'shortcode_destacados']);
        add_shortcode('flavor_eventos_acciones', [$this, 'shortcode_acciones']);

        // Alias singular
        add_shortcode('eventos_proximo', [$this, 'shortcode_proximo']);
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
            return '<p class="flavor-login-required">' . __('Inicia sesión para ver tus inscripciones.', 'flavor-chat-ia') . '</p>';
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
            return '<p class="flavor-error">' . __('Evento no especificado.', 'flavor-chat-ia') . '</p>';
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

        $sql = "SELECT * FROM $tabla_eventos WHERE " . implode(' AND ', $where) . " ORDER BY fecha_inicio ASC LIMIT %d";
        $params[] = intval($atts['limite']);

        $eventos = $wpdb->get_results($wpdb->prepare($sql, ...$params));

        $template = dirname(__FILE__) . '/archive.php';
        if (file_exists($template)) {
            include $template;
        } else {
            echo '<div class="flavor-eventos-grid grid-' . esc_attr($atts['columnas']) . '">';
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
             WHERE i.usuario_id = %d
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

        if (!is_user_logged_in()) {
            wp_send_json_error(__('Debes iniciar sesión', 'flavor-chat-ia'));
        }

        $evento_id = isset($_POST['evento_id']) ? absint($_POST['evento_id']) : 0;
        $num_plazas = isset($_POST['num_plazas']) ? absint($_POST['num_plazas']) : 1;
        $usuario_id = get_current_user_id();

        if (!$evento_id) {
            wp_send_json_error(__('Evento no válido', 'flavor-chat-ia'));
        }

        global $wpdb;
        $tabla_inscripciones = $wpdb->prefix . 'flavor_eventos_inscripciones';
        $tabla_eventos = $wpdb->prefix . 'flavor_eventos';

        // Verificar si ya está inscrito
        $existe = $wpdb->get_var($wpdb->prepare(
            "SELECT id FROM $tabla_inscripciones WHERE evento_id = %d AND usuario_id = %d AND estado != 'cancelada'",
            $evento_id,
            $usuario_id
        ));

        if ($existe) {
            wp_send_json_error(__('Ya estás inscrito en este evento', 'flavor-chat-ia'));
        }

        // Verificar plazas disponibles
        $evento = $wpdb->get_row($wpdb->prepare("SELECT * FROM $tabla_eventos WHERE id = %d", $evento_id));

        if (!$evento) {
            wp_send_json_error(__('Evento no encontrado', 'flavor-chat-ia'));
        }

        $inscritos = $wpdb->get_var($wpdb->prepare(
            "SELECT SUM(num_plazas) FROM $tabla_inscripciones WHERE evento_id = %d AND estado = 'confirmada'",
            $evento_id
        ));

        $en_lista_espera = false;
        if ($evento->aforo_maximo > 0 && ($inscritos + $num_plazas) > $evento->aforo_maximo) {
            // Verificar si permite lista de espera
            if ($evento->permite_lista_espera) {
                $en_lista_espera = true;
            } else {
                wp_send_json_error(__('No hay plazas disponibles', 'flavor-chat-ia'));
            }
        }

        // Realizar inscripción
        $resultado = $wpdb->insert($tabla_inscripciones, [
            'evento_id' => $evento_id,
            'usuario_id' => $usuario_id,
            'num_plazas' => $num_plazas,
            'estado' => $en_lista_espera ? 'lista_espera' : 'confirmada',
            'fecha_inscripcion' => current_time('mysql'),
        ]);

        if ($resultado) {
            $mensaje = $en_lista_espera
                ? __('Te has añadido a la lista de espera', 'flavor-chat-ia')
                : __('Te has inscrito correctamente', 'flavor-chat-ia');

            wp_send_json_success(['mensaje' => $mensaje, 'lista_espera' => $en_lista_espera]);
        } else {
            wp_send_json_error(__('Error al procesar la inscripción', 'flavor-chat-ia'));
        }
    }

    /**
     * AJAX: Cancelar inscripción
     */
    public function ajax_cancelar_inscripcion() {
        check_ajax_referer('eventos_nonce', 'nonce');

        if (!is_user_logged_in()) {
            wp_send_json_error(__('Debes iniciar sesión', 'flavor-chat-ia'));
        }

        $inscripcion_id = isset($_POST['inscripcion_id']) ? absint($_POST['inscripcion_id']) : 0;
        $usuario_id = get_current_user_id();

        global $wpdb;
        $tabla_inscripciones = $wpdb->prefix . 'flavor_eventos_inscripciones';

        $resultado = $wpdb->update(
            $tabla_inscripciones,
            ['estado' => 'cancelada', 'fecha_cancelacion' => current_time('mysql')],
            ['id' => $inscripcion_id, 'usuario_id' => $usuario_id]
        );

        if ($resultado !== false) {
            wp_send_json_success(['mensaje' => __('Inscripción cancelada', 'flavor-chat-ia')]);
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

        $url = home_url('/eventos/?evento_id=' . $evento_id);
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
                    <a href="<?php echo esc_url(home_url('/eventos/?evento_id=' . $evento->id)); ?>" class="flavor-btn flavor-btn-primary">
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
                 WHERE i.usuario_id = %d AND i.estado IN ('confirmada', 'pendiente') AND e.fecha_inicio > NOW()
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
                            <a href="<?php echo esc_url(home_url('/eventos/?evento_id=' . $evento->id)); ?>" class="flavor-btn flavor-btn-small">
                                <?php esc_html_e('Ver', 'flavor-chat-ia'); ?>
                            </a>
                        </div>
                    <?php endforeach; ?>
                </div>
                <a href="<?php echo esc_url(home_url('/eventos/')); ?>" class="ver-todos-link">
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
                <a href="<?php echo esc_url(home_url('/eventos/')); ?>" class="flavor-btn flavor-btn-primary">
                    <span class="dashicons dashicons-calendar-alt"></span>
                    <?php esc_html_e('Explorar Eventos', 'flavor-chat-ia'); ?>
                </a>
                <?php if (is_user_logged_in()): ?>
                <a href="<?php echo esc_url(home_url('/eventos/mis-inscripciones/')); ?>" class="flavor-btn flavor-btn-secondary">
                    <span class="dashicons dashicons-list-view"></span>
                    <?php esc_html_e('Mis Inscripciones', 'flavor-chat-ia'); ?>
                </a>
                <?php endif; ?>
            </div>
        </div>
        <?php
        return ob_get_clean();
    }
}

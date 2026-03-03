<?php
/**
 * Frontend Controller para Talleres
 *
 * @package FlavorChatIA
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Controlador Frontend para el módulo de Talleres
 * Gestiona shortcodes, assets y dashboard tabs del frontend
 */
class Flavor_Talleres_Frontend_Controller {

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
        add_action('wp_ajax_talleres_inscribirse', [$this, 'ajax_inscribirse']);
        add_action('wp_ajax_talleres_cancelar_inscripcion', [$this, 'ajax_cancelar_inscripcion']);
        add_action('wp_ajax_talleres_valorar', [$this, 'ajax_valorar']);
        add_action('wp_ajax_talleres_filtrar', [$this, 'ajax_filtrar']);

        // Dashboard tabs
        add_filter('flavor_user_dashboard_tabs', [$this, 'registrar_tabs'], 10, 1);

        // Template overrides
        add_filter('template_include', [$this, 'cargar_template']);
    }

    /**
     * Registrar assets CSS y JS
     */
    public function registrar_assets() {
        $base_url = plugins_url('', dirname(dirname(__FILE__)));
        $version = FLAVOR_CHAT_IA_VERSION ?? '1.0.0';

        // CSS
        wp_register_style(
            'flavor-talleres',
            $base_url . '/assets/css/talleres.css',
            [],
            $version
        );

        // JS
        wp_register_script(
            'flavor-talleres',
            $base_url . '/assets/js/talleres.js',
            ['jquery'],
            $version,
            true
        );

        // Localizar script
        wp_localize_script('flavor-talleres', 'flavorTalleres', [
            'ajaxUrl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('talleres_nonce'),
            'i18n' => [
                'inscrito' => __('Te has inscrito correctamente', 'flavor-chat-ia'),
                'cancelado' => __('Inscripción cancelada', 'flavor-chat-ia'),
                'error' => __('Ha ocurrido un error', 'flavor-chat-ia'),
                'confirmacion' => __('¿Estás seguro?', 'flavor-chat-ia'),
                'cargando' => __('Cargando...', 'flavor-chat-ia'),
                'sin_plazas' => __('No hay plazas disponibles', 'flavor-chat-ia'),
            ],
        ]);
    }

    /**
     * Encolar assets cuando sea necesario
     */
    public function encolar_assets() {
        wp_enqueue_style('flavor-talleres');
        wp_enqueue_script('flavor-talleres');
    }

    /**
     * Registrar shortcodes del módulo
     */
    public function registrar_shortcodes() {
        add_shortcode('talleres_catalogo', [$this, 'shortcode_catalogo']);
        add_shortcode('talleres_mis_inscripciones', [$this, 'shortcode_mis_inscripciones']);
        add_shortcode('talleres_calendario', [$this, 'shortcode_calendario']);
        add_shortcode('talleres_proponer', [$this, 'shortcode_proponer']);
        add_shortcode('talleres_detalle', [$this, 'shortcode_detalle']);
        add_shortcode('talleres_organizador', [$this, 'shortcode_organizador']);
    }

    /**
     * Shortcode: Catálogo de talleres
     */
    public function shortcode_catalogo($atts) {
        $this->encolar_assets();

        $atts = shortcode_atts([
            'categoria' => '',
            'limite' => 12,
            'columnas' => 3,
            'mostrar_filtros' => 'true',
        ], $atts);

        ob_start();
        $this->render_catalogo($atts);
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
            'estado' => 'todos',
            'limite' => 20,
        ], $atts);

        ob_start();
        $this->render_mis_inscripciones($atts);
        return ob_get_clean();
    }

    /**
     * Shortcode: Calendario de talleres
     */
    public function shortcode_calendario($atts) {
        $this->encolar_assets();

        $atts = shortcode_atts([
            'mes' => date('m'),
            'anio' => date('Y'),
        ], $atts);

        ob_start();
        $this->render_calendario($atts);
        return ob_get_clean();
    }

    /**
     * Shortcode: Proponer taller
     */
    public function shortcode_proponer($atts) {
        if (!is_user_logged_in()) {
            return '<p class="flavor-login-required">' . __('Inicia sesión para proponer un taller.', 'flavor-chat-ia') . '</p>';
        }

        $this->encolar_assets();

        ob_start();
        $this->render_proponer_taller();
        return ob_get_clean();
    }

    /**
     * Shortcode: Detalle de taller
     */
    public function shortcode_detalle($atts) {
        $this->encolar_assets();

        $atts = shortcode_atts([
            'id' => 0,
        ], $atts);

        $taller_id = $atts['id'] ?: (isset($_GET['taller_id']) ? absint($_GET['taller_id']) : 0);

        if (!$taller_id) {
            return '<p class="flavor-error">' . __('Taller no especificado.', 'flavor-chat-ia') . '</p>';
        }

        ob_start();
        $this->render_detalle_taller($taller_id);
        return ob_get_clean();
    }

    /**
     * Shortcode: Panel de organizador
     */
    public function shortcode_organizador($atts) {
        if (!is_user_logged_in()) {
            return '<p class="flavor-login-required">' . __('Inicia sesión para acceder al panel de organizador.', 'flavor-chat-ia') . '</p>';
        }

        $this->encolar_assets();

        ob_start();
        $this->render_panel_organizador();
        return ob_get_clean();
    }

    /**
     * Renderizar catálogo de talleres
     */
    private function render_catalogo($atts) {
        global $wpdb;
        $tabla_talleres = $wpdb->prefix . 'flavor_talleres';

        if (!Flavor_Chat_Helpers::tabla_existe($tabla_talleres)) {
            echo '<p class="flavor-error">' . __('El módulo de talleres no está configurado.', 'flavor-chat-ia') . '</p>';
            return;
        }

        $where = ["estado = 'publicado'", "fecha_inicio >= NOW()"];
        $params = [];

        if (!empty($atts['categoria'])) {
            $where[] = "categoria = %s";
            $params[] = $atts['categoria'];
        }

        $sql = "SELECT * FROM $tabla_talleres WHERE " . implode(' AND ', $where) . " ORDER BY fecha_inicio ASC LIMIT %d";
        $params[] = intval($atts['limite']);

        $talleres = $wpdb->get_results($wpdb->prepare($sql, ...$params));

        include dirname(__FILE__) . '/../templates/catalogo.php';
    }

    /**
     * Renderizar mis inscripciones
     */
    private function render_mis_inscripciones($atts) {
        global $wpdb;
        $tabla_inscripciones = $wpdb->prefix . 'flavor_talleres_inscripciones';
        $tabla_talleres = $wpdb->prefix . 'flavor_talleres';
        $usuario_id = get_current_user_id();

        $inscripciones = $wpdb->get_results($wpdb->prepare(
            "SELECT i.*, t.titulo, t.fecha_inicio, t.ubicacion, t.imagen
             FROM $tabla_inscripciones i
             JOIN $tabla_talleres t ON i.taller_id = t.id
             WHERE i.usuario_id = %d
             ORDER BY t.fecha_inicio DESC
             LIMIT %d",
            $usuario_id,
            intval($atts['limite'])
        ));

        include dirname(__FILE__) . '/../templates/mis-inscripciones.php';
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
     * Renderizar formulario para proponer taller
     */
    private function render_proponer_taller() {
        $template = dirname(__FILE__) . '/../templates/proponer-taller.php';
        if (file_exists($template)) {
            include $template;
        } else {
            echo '<div class="flavor-proponer-taller">';
            echo '<h3>' . __('Proponer un Taller', 'flavor-chat-ia') . '</h3>';
            echo '<p>' . __('¿Tienes una habilidad que quieres compartir? Propón un taller para la comunidad.', 'flavor-chat-ia') . '</p>';
            echo '<form class="flavor-form" id="form-proponer-taller">';
            echo '<p><label>' . __('Título del taller', 'flavor-chat-ia') . '</label>';
            echo '<input type="text" name="titulo" required></p>';
            echo '<p><label>' . __('Descripción', 'flavor-chat-ia') . '</label>';
            echo '<textarea name="descripcion" rows="4" required></textarea></p>';
            echo '<p><label>' . __('Categoría', 'flavor-chat-ia') . '</label>';
            echo '<select name="categoria">';
            echo '<option value="artesania">' . __('Artesanía', 'flavor-chat-ia') . '</option>';
            echo '<option value="cocina">' . __('Cocina', 'flavor-chat-ia') . '</option>';
            echo '<option value="tecnologia">' . __('Tecnología', 'flavor-chat-ia') . '</option>';
            echo '<option value="huerto">' . __('Huerto', 'flavor-chat-ia') . '</option>';
            echo '<option value="otros">' . __('Otros', 'flavor-chat-ia') . '</option>';
            echo '</select></p>';
            echo '<p><button type="submit" class="flavor-btn flavor-btn-primary">' . __('Enviar Propuesta', 'flavor-chat-ia') . '</button></p>';
            echo '</form>';
            echo '</div>';
        }
    }

    /**
     * Renderizar detalle de taller
     */
    private function render_detalle_taller($taller_id) {
        global $wpdb;
        $tabla_talleres = $wpdb->prefix . 'flavor_talleres';

        $taller = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM $tabla_talleres WHERE id = %d",
            $taller_id
        ));

        if (!$taller) {
            echo '<p class="flavor-error">' . __('Taller no encontrado.', 'flavor-chat-ia') . '</p>';
            return;
        }

        $template = dirname(__FILE__) . '/single.php';
        if (file_exists($template)) {
            include $template;
        } else {
            // Fallback básico
            echo '<div class="flavor-taller-detalle">';
            echo '<h2>' . esc_html($taller->titulo) . '</h2>';
            echo '<div class="taller-meta">';
            echo '<span class="fecha"><strong>' . __('Fecha:', 'flavor-chat-ia') . '</strong> ' . esc_html(date_i18n('d/m/Y H:i', strtotime($taller->fecha_inicio))) . '</span>';
            echo '<span class="ubicacion"><strong>' . __('Lugar:', 'flavor-chat-ia') . '</strong> ' . esc_html($taller->ubicacion) . '</span>';
            echo '</div>';
            echo '<div class="taller-descripcion">' . wp_kses_post($taller->descripcion) . '</div>';
            echo '</div>';
        }
    }

    /**
     * Renderizar panel de organizador
     */
    private function render_panel_organizador() {
        global $wpdb;
        $tabla_talleres = $wpdb->prefix . 'flavor_talleres';
        $usuario_id = get_current_user_id();

        $mis_talleres = $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM $tabla_talleres WHERE organizador_id = %d ORDER BY fecha_inicio DESC",
            $usuario_id
        ));

        $template = dirname(__FILE__) . '/../templates/panel-organizador.php';
        if (file_exists($template)) {
            include $template;
        }
    }

    /**
     * Registrar tabs en el dashboard de usuario
     */
    public function registrar_tabs($tabs) {
        $tabs['talleres-inscripciones'] = [
            'titulo' => __('Mis Talleres', 'flavor-chat-ia'),
            'icono' => 'dashicons-hammer',
            'callback' => [$this, 'render_tab_inscripciones'],
            'orden' => 35,
            'modulo' => 'talleres',
        ];

        // Tab adicional para organizadores
        if ($this->usuario_es_organizador()) {
            $tabs['talleres-organizador'] = [
                'titulo' => __('Organizar Talleres', 'flavor-chat-ia'),
                'icono' => 'dashicons-groups',
                'callback' => [$this, 'render_tab_organizador'],
                'orden' => 36,
                'modulo' => 'talleres',
            ];
        }

        return $tabs;
    }

    /**
     * Verificar si el usuario es organizador
     */
    private function usuario_es_organizador() {
        if (!is_user_logged_in()) {
            return false;
        }

        global $wpdb;
        $tabla_talleres = $wpdb->prefix . 'flavor_talleres';
        $usuario_id = get_current_user_id();

        $cuenta = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM $tabla_talleres WHERE organizador_id = %d",
            $usuario_id
        ));

        return $cuenta > 0 || current_user_can('manage_options');
    }

    /**
     * Renderizar tab de inscripciones
     */
    public function render_tab_inscripciones() {
        $this->encolar_assets();
        $this->render_mis_inscripciones(['estado' => 'todos', 'limite' => 20]);
    }

    /**
     * Renderizar tab de organizador
     */
    public function render_tab_organizador() {
        $this->encolar_assets();
        $this->render_panel_organizador();
    }

    /**
     * Cargar templates personalizados
     */
    public function cargar_template($template) {
        if (is_singular() && get_query_var('post_type') === 'flavor_taller') {
            $custom_template = dirname(__FILE__) . '/single.php';
            if (file_exists($custom_template)) {
                return $custom_template;
            }
        }

        return $template;
    }

    /**
     * AJAX: Inscribirse a taller
     */
    public function ajax_inscribirse() {
        check_ajax_referer('talleres_nonce', 'nonce');

        if (!is_user_logged_in()) {
            wp_send_json_error(__('Debes iniciar sesión', 'flavor-chat-ia'));
        }

        $taller_id = isset($_POST['taller_id']) ? absint($_POST['taller_id']) : 0;
        $usuario_id = get_current_user_id();

        if (!$taller_id) {
            wp_send_json_error(__('Taller no válido', 'flavor-chat-ia'));
        }

        global $wpdb;
        $tabla_inscripciones = $wpdb->prefix . 'flavor_talleres_inscripciones';
        $tabla_talleres = $wpdb->prefix . 'flavor_talleres';

        // Verificar si ya está inscrito
        $existe = $wpdb->get_var($wpdb->prepare(
            "SELECT id FROM $tabla_inscripciones WHERE taller_id = %d AND usuario_id = %d",
            $taller_id,
            $usuario_id
        ));

        if ($existe) {
            wp_send_json_error(__('Ya estás inscrito en este taller', 'flavor-chat-ia'));
        }

        // Verificar plazas disponibles
        $taller = $wpdb->get_row($wpdb->prepare("SELECT * FROM $tabla_talleres WHERE id = %d", $taller_id));

        if (!$taller) {
            wp_send_json_error(__('Taller no encontrado', 'flavor-chat-ia'));
        }

        $inscritos = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM $tabla_inscripciones WHERE taller_id = %d AND estado = 'confirmada'",
            $taller_id
        ));

        if ($taller->max_participantes > 0 && $inscritos >= $taller->max_participantes) {
            wp_send_json_error(__('No hay plazas disponibles', 'flavor-chat-ia'));
        }

        // Realizar inscripción
        $resultado = $wpdb->insert($tabla_inscripciones, [
            'taller_id' => $taller_id,
            'usuario_id' => $usuario_id,
            'estado' => 'confirmada',
            'fecha_inscripcion' => current_time('mysql'),
        ]);

        if ($resultado) {
            wp_send_json_success([
                'mensaje' => __('Te has inscrito correctamente', 'flavor-chat-ia'),
            ]);
        } else {
            wp_send_json_error(__('Error al procesar la inscripción', 'flavor-chat-ia'));
        }
    }

    /**
     * AJAX: Cancelar inscripción
     */
    public function ajax_cancelar_inscripcion() {
        check_ajax_referer('talleres_nonce', 'nonce');

        if (!is_user_logged_in()) {
            wp_send_json_error(__('Debes iniciar sesión', 'flavor-chat-ia'));
        }

        $inscripcion_id = isset($_POST['inscripcion_id']) ? absint($_POST['inscripcion_id']) : 0;
        $usuario_id = get_current_user_id();

        global $wpdb;
        $tabla_inscripciones = $wpdb->prefix . 'flavor_talleres_inscripciones';

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
     * AJAX: Valorar taller
     */
    public function ajax_valorar() {
        check_ajax_referer('talleres_nonce', 'nonce');

        if (!is_user_logged_in()) {
            wp_send_json_error(__('Debes iniciar sesión', 'flavor-chat-ia'));
        }

        $taller_id = isset($_POST['taller_id']) ? absint($_POST['taller_id']) : 0;
        $valoracion = isset($_POST['valoracion']) ? intval($_POST['valoracion']) : 0;
        $comentario = isset($_POST['comentario']) ? sanitize_textarea_field($_POST['comentario']) : '';
        $usuario_id = get_current_user_id();

        if (!$taller_id || $valoracion < 1 || $valoracion > 5) {
            wp_send_json_error(__('Datos no válidos', 'flavor-chat-ia'));
        }

        global $wpdb;
        $tabla_valoraciones = $wpdb->prefix . 'flavor_talleres_valoraciones';

        $resultado = $wpdb->replace($tabla_valoraciones, [
            'taller_id' => $taller_id,
            'usuario_id' => $usuario_id,
            'valoracion' => $valoracion,
            'comentario' => $comentario,
            'fecha' => current_time('mysql'),
        ]);

        if ($resultado) {
            wp_send_json_success(['mensaje' => __('Gracias por tu valoración', 'flavor-chat-ia')]);
        } else {
            wp_send_json_error(__('Error al guardar valoración', 'flavor-chat-ia'));
        }
    }

    /**
     * AJAX: Filtrar talleres
     */
    public function ajax_filtrar() {
        check_ajax_referer('talleres_nonce', 'nonce');

        $categoria = isset($_POST['categoria']) ? sanitize_text_field($_POST['categoria']) : '';
        $busqueda = isset($_POST['busqueda']) ? sanitize_text_field($_POST['busqueda']) : '';
        $fecha_desde = isset($_POST['fecha_desde']) ? sanitize_text_field($_POST['fecha_desde']) : '';

        global $wpdb;
        $tabla_talleres = $wpdb->prefix . 'flavor_talleres';

        $where = ["estado = 'publicado'"];
        $params = [];

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
        }

        $sql = "SELECT * FROM $tabla_talleres WHERE " . implode(' AND ', $where) . " ORDER BY fecha_inicio ASC LIMIT 50";

        if (!empty($params)) {
            $talleres = $wpdb->get_results($wpdb->prepare($sql, ...$params));
        } else {
            $talleres = $wpdb->get_results($sql);
        }

        ob_start();
        if (!empty($talleres)) {
            foreach ($talleres as $taller) {
                $this->render_taller_card($taller);
            }
        } else {
            echo '<p class="no-resultados">' . __('No se encontraron talleres', 'flavor-chat-ia') . '</p>';
        }
        $html = ob_get_clean();

        wp_send_json_success(['html' => $html, 'total' => count($talleres)]);
    }

    /**
     * Renderizar tarjeta de taller
     */
    private function render_taller_card($taller) {
        ?>
        <div class="flavor-taller-card" data-id="<?php echo esc_attr($taller->id); ?>">
            <?php if (!empty($taller->imagen)) : ?>
                <div class="taller-imagen">
                    <img src="<?php echo esc_url($taller->imagen); ?>" alt="<?php echo esc_attr($taller->titulo); ?>">
                </div>
            <?php endif; ?>
            <div class="taller-contenido">
                <h3><?php echo esc_html($taller->titulo); ?></h3>
                <div class="taller-meta">
                    <span class="fecha"><?php echo esc_html(date_i18n('d M Y - H:i', strtotime($taller->fecha_inicio))); ?></span>
                    <span class="categoria"><?php echo esc_html($taller->categoria); ?></span>
                </div>
                <p class="taller-descripcion"><?php echo esc_html(wp_trim_words($taller->descripcion, 20)); ?></p>
                <a href="<?php echo esc_url(home_url('/talleres/?taller_id=' . $taller->id)); ?>" class="flavor-btn flavor-btn-sm">
                    <?php _e('Ver Detalles', 'flavor-chat-ia'); ?>
                </a>
            </div>
        </div>
        <?php
    }
}

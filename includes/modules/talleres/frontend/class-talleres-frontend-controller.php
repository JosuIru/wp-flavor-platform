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
                'inscrito' => __('Te has inscrito correctamente', 'flavor-platform'),
                'cancelado' => __('Inscripción cancelada', 'flavor-platform'),
                'error' => __('Ha ocurrido un error', 'flavor-platform'),
                'confirmacion' => __('¿Estás seguro?', 'flavor-platform'),
                'cargando' => __('Cargando...', 'flavor-platform'),
                'sin_plazas' => __('No hay plazas disponibles', 'flavor-platform'),
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
        $shortcodes = [
            'talleres_catalogo' => 'shortcode_catalogo',
            'talleres_mis_inscripciones' => 'shortcode_mis_inscripciones',
            'talleres_calendario' => 'shortcode_calendario',
            'talleres_proponer' => 'shortcode_proponer',
            'talleres_detalle' => 'shortcode_detalle',
            'talleres_organizador' => 'shortcode_organizador',
            'talleres_proximo' => 'shortcode_proximo',
            'talleres_materiales' => 'shortcode_materiales',
        ];
        foreach ($shortcodes as $tag => $method) {
            if (!shortcode_exists($tag)) {
                add_shortcode($tag, [$this, $method]);
            }
        }
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
            // Parámetros visuales (VBP)
            'esquema_color' => 'default',
            'estilo_tarjeta' => 'elevated',
            'radio_bordes' => 'lg',
            'animacion_entrada' => 'fade',
            'orderby' => 'fecha_inicio',
            'order' => 'ASC',
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
            return '<p class="flavor-login-required">' . __('Inicia sesión para ver tus inscripciones.', 'flavor-platform') . '</p>';
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
            return '<p class="flavor-login-required">' . __('Inicia sesión para proponer un taller.', 'flavor-platform') . '</p>';
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
            return '<p class="flavor-error">' . __('Taller no especificado.', 'flavor-platform') . '</p>';
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
            return '<p class="flavor-login-required">' . __('Inicia sesión para acceder al panel de organizador.', 'flavor-platform') . '</p>';
        }

        $this->encolar_assets();

        ob_start();
        $this->render_panel_organizador();
        return ob_get_clean();
    }

    /**
     * Shortcode: Próximo taller (widget compacto para dashboard)
     */
    public function shortcode_proximo($atts) {
        global $wpdb;
        $tabla_talleres = $wpdb->prefix . 'flavor_talleres';

        $atts = shortcode_atts([
            'limite' => 1,
        ], $atts);

        if (!Flavor_Chat_Helpers::tabla_existe($tabla_talleres)) {
            return '<p class="fmd-widget-empty">' . __('Módulo no configurado.', 'flavor-platform') . '</p>';
        }

        $proximo = $wpdb->get_row(
            "SELECT * FROM $tabla_talleres
             WHERE estado = 'publicado' AND fecha_inicio >= NOW()
             ORDER BY fecha_inicio ASC
             LIMIT 1"
        );

        if (!$proximo) {
            return '<div class="fmd-widget-empty-state">
                <span class="dashicons dashicons-calendar-alt"></span>
                <p>' . __('No hay talleres próximos programados.', 'flavor-platform') . '</p>
            </div>';
        }

        $fecha = date_i18n('j M Y', strtotime($proximo->fecha_inicio));
        $hora = date_i18n('H:i', strtotime($proximo->fecha_inicio));
        $plazas_disponibles = max(0, ($proximo->plazas_maximas ?? 0) - ($proximo->inscritos ?? 0));
        $porcentaje_ocupacion = ($proximo->plazas_maximas > 0)
            ? round((($proximo->inscritos ?? 0) / $proximo->plazas_maximas) * 100)
            : 0;

        ob_start();
        ?>
        <div class="fmd-proximo-item">
            <div class="fmd-proximo-fecha">
                <span class="fmd-proximo-dia"><?php echo date_i18n('j', strtotime($proximo->fecha_inicio)); ?></span>
                <span class="fmd-proximo-mes"><?php echo date_i18n('M', strtotime($proximo->fecha_inicio)); ?></span>
            </div>
            <div class="fmd-proximo-info">
                <h5 class="fmd-proximo-titulo"><?php echo esc_html($proximo->titulo); ?></h5>
                <div class="fmd-proximo-meta">
                    <span><span class="dashicons dashicons-clock"></span> <?php echo esc_html($hora); ?></span>
                    <?php if (!empty($proximo->ubicacion)): ?>
                        <span><span class="dashicons dashicons-location"></span> <?php echo esc_html($proximo->ubicacion); ?></span>
                    <?php endif; ?>
                </div>
                <div class="fmd-proximo-plazas">
                    <div class="fmd-progress-bar">
                        <div class="fmd-progress-fill" style="width: <?php echo $porcentaje_ocupacion; ?>%"></div>
                    </div>
                    <span class="fmd-plazas-texto"><?php echo sprintf(__('%d plazas disponibles', 'flavor-platform'), $plazas_disponibles); ?></span>
                </div>
            </div>
        </div>
        <style>
        .fmd-proximo-item { display: flex; gap: 1rem; align-items: flex-start; }
        .fmd-proximo-fecha { background: var(--flavor-primary, #6366f1); color: #fff; border-radius: 8px; padding: 0.5rem 0.75rem; text-align: center; min-width: 50px; }
        .fmd-proximo-dia { display: block; font-size: 1.25rem; font-weight: 700; line-height: 1; }
        .fmd-proximo-mes { display: block; font-size: 0.7rem; text-transform: uppercase; opacity: 0.9; }
        .fmd-proximo-info { flex: 1; }
        .fmd-proximo-titulo { margin: 0 0 0.25rem; font-size: 0.95rem; font-weight: 600; color: #1f2937; }
        .fmd-proximo-meta { display: flex; gap: 0.75rem; font-size: 0.8rem; color: #6b7280; margin-bottom: 0.5rem; }
        .fmd-proximo-meta .dashicons { font-size: 14px; width: 14px; height: 14px; vertical-align: middle; }
        .fmd-proximo-plazas { font-size: 0.75rem; color: #9ca3af; }
        .fmd-progress-bar { height: 4px; background: #e5e7eb; border-radius: 2px; margin-bottom: 0.25rem; }
        .fmd-progress-fill { height: 100%; background: var(--flavor-primary, #6366f1); border-radius: 2px; transition: width 0.3s; }
        .fmd-widget-empty-state { text-align: center; padding: 1.5rem; color: #9ca3af; }
        .fmd-widget-empty-state .dashicons { font-size: 32px; width: 32px; height: 32px; margin-bottom: 0.5rem; opacity: 0.5; }
        </style>
        <?php
        return ob_get_clean();
    }

    /**
     * Shortcode: Materiales de talleres (archivos descargables)
     */
    public function shortcode_materiales($atts) {
        if (!is_user_logged_in()) {
            return '<p class="flavor-login-required">' . __('Inicia sesión para ver los materiales.', 'flavor-platform') . '</p>';
        }

        global $wpdb;
        $tabla_materiales = $wpdb->prefix . 'flavor_talleres_materiales';
        $tabla_inscripciones = $wpdb->prefix . 'flavor_talleres_inscripciones';
        $tabla_talleres = $wpdb->prefix . 'flavor_talleres';
        $usuario_id = get_current_user_id();

        if (!Flavor_Chat_Helpers::tabla_existe($tabla_materiales)) {
            return '<p class="flavor-error">' . __('El módulo no está configurado.', 'flavor-platform') . '</p>';
        }

        // Obtener materiales de talleres en los que está inscrito el usuario
        $materiales = $wpdb->get_results($wpdb->prepare(
            "SELECT m.*, t.titulo as taller_titulo, t.id as taller_id
             FROM $tabla_materiales m
             INNER JOIN $tabla_talleres t ON m.taller_id = t.id
             INNER JOIN $tabla_inscripciones i ON t.id = i.taller_id AND i.usuario_id = %d
             WHERE (m.solo_inscritos = 0 OR i.estado = 'confirmada')
             ORDER BY m.fecha_subida DESC",
            $usuario_id
        ));

        ob_start();
        ?>
        <div class="talleres-materiales-lista">
            <?php if (empty($materiales)): ?>
                <div class="fmd-empty-state">
                    <span class="dashicons dashicons-media-document"></span>
                    <p><?php _e('No hay materiales disponibles.', 'flavor-platform'); ?></p>
                    <small><?php _e('Los materiales aparecerán aquí cuando te inscribas en talleres que los incluyan.', 'flavor-platform'); ?></small>
                </div>
            <?php else: ?>
                <?php
                $materiales_por_taller = [];
                foreach ($materiales as $material) {
                    $materiales_por_taller[$material->taller_id]['titulo'] = $material->taller_titulo;
                    $materiales_por_taller[$material->taller_id]['items'][] = $material;
                }
                ?>
                <?php foreach ($materiales_por_taller as $taller_id => $grupo): ?>
                    <div class="talleres-materiales-grupo">
                        <h4 class="talleres-materiales-taller"><?php echo esc_html($grupo['titulo']); ?></h4>
                        <ul class="talleres-materiales-items">
                            <?php foreach ($grupo['items'] as $mat): ?>
                                <li class="talleres-material-item">
                                    <span class="dashicons <?php echo $this->get_material_icon($mat->tipo_archivo ?? 'file'); ?>"></span>
                                    <div class="talleres-material-info">
                                        <span class="talleres-material-titulo"><?php echo esc_html($mat->titulo); ?></span>
                                        <?php if (!empty($mat->descripcion)): ?>
                                            <small class="talleres-material-desc"><?php echo esc_html($mat->descripcion); ?></small>
                                        <?php endif; ?>
                                    </div>
                                    <a href="<?php echo esc_url($mat->archivo_url); ?>"
                                       class="talleres-material-download"
                                       target="_blank"
                                       download>
                                        <span class="dashicons dashicons-download"></span>
                                    </a>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
        <style>
        .talleres-materiales-lista { }
        .talleres-materiales-grupo { margin-bottom: 1.5rem; }
        .talleres-materiales-taller { font-size: 1rem; font-weight: 600; color: var(--module-color, #6366f1); margin: 0 0 0.75rem; padding-bottom: 0.5rem; border-bottom: 1px solid #e5e7eb; }
        .talleres-materiales-items { list-style: none; margin: 0; padding: 0; }
        .talleres-material-item { display: flex; align-items: center; gap: 0.75rem; padding: 0.75rem; background: #f9fafb; border-radius: 8px; margin-bottom: 0.5rem; }
        .talleres-material-item > .dashicons { color: #6b7280; font-size: 20px; width: 20px; height: 20px; }
        .talleres-material-info { flex: 1; }
        .talleres-material-titulo { display: block; font-weight: 500; color: #1f2937; }
        .talleres-material-desc { display: block; color: #6b7280; font-size: 0.8rem; }
        .talleres-material-download { display: flex; align-items: center; justify-content: center; width: 36px; height: 36px; background: var(--module-color, #6366f1); color: white !important; border-radius: 8px; transition: all 0.2s; }
        .talleres-material-download:hover { filter: brightness(1.1); transform: scale(1.05); }
        .fmd-empty-state { text-align: center; padding: 2rem; color: #6b7280; }
        .fmd-empty-state .dashicons { font-size: 48px; width: 48px; height: 48px; opacity: 0.3; margin-bottom: 1rem; }
        .fmd-empty-state p { margin: 0 0 0.5rem; font-weight: 500; }
        .fmd-empty-state small { opacity: 0.7; }
        </style>
        <?php
        return ob_get_clean();
    }

    /**
     * Obtener icono según tipo de archivo
     */
    private function get_material_icon($tipo) {
        $iconos = [
            'pdf' => 'dashicons-pdf',
            'doc' => 'dashicons-media-document',
            'docx' => 'dashicons-media-document',
            'xls' => 'dashicons-media-spreadsheet',
            'xlsx' => 'dashicons-media-spreadsheet',
            'ppt' => 'dashicons-media-interactive',
            'pptx' => 'dashicons-media-interactive',
            'zip' => 'dashicons-media-archive',
            'rar' => 'dashicons-media-archive',
            'mp4' => 'dashicons-media-video',
            'mp3' => 'dashicons-media-audio',
            'jpg' => 'dashicons-format-image',
            'jpeg' => 'dashicons-format-image',
            'png' => 'dashicons-format-image',
            'gif' => 'dashicons-format-image',
        ];
        return $iconos[$tipo] ?? 'dashicons-media-default';
    }

    /**
     * Renderizar catálogo de talleres
     */
    private function render_catalogo($atts) {
        global $wpdb;
        $tabla_talleres = $wpdb->prefix . 'flavor_talleres';

        // Generar clases CSS visuales (VBP)
        $visual_classes = [];
        if (!empty($atts['esquema_color']) && $atts['esquema_color'] !== 'default') {
            $visual_classes[] = 'flavor-scheme-' . sanitize_html_class($atts['esquema_color']);
        }
        if (!empty($atts['estilo_tarjeta']) && $atts['estilo_tarjeta'] !== 'elevated') {
            $visual_classes[] = 'flavor-card-' . sanitize_html_class($atts['estilo_tarjeta']);
        }
        if (!empty($atts['radio_bordes']) && $atts['radio_bordes'] !== 'lg') {
            $visual_classes[] = 'flavor-radius-' . sanitize_html_class($atts['radio_bordes']);
        }
        if (!empty($atts['animacion_entrada']) && $atts['animacion_entrada'] !== 'none') {
            $visual_classes[] = 'flavor-animate-' . sanitize_html_class($atts['animacion_entrada']);
        }
        $atts['visual_class_string'] = implode(' ', $visual_classes);

        if (!Flavor_Chat_Helpers::tabla_existe($tabla_talleres)) {
            echo '<p class="flavor-error">' . __('El módulo de talleres no está configurado.', 'flavor-platform') . '</p>';
            return;
        }

        $where = ["estado = 'publicado'", "fecha_inicio >= NOW()"];
        $params = [];

        if (!empty($atts['categoria'])) {
            $where[] = "categoria = %s";
            $params[] = $atts['categoria'];
        }

        // Mapeo de orderby para talleres
        $orderby_map = [
            'fecha_inicio' => 'fecha_inicio',
            'titulo' => 'titulo',
            'title' => 'titulo',
            'date' => 'created_at',
            'plazas' => 'plazas_maximas',
        ];
        $orderby_column = $orderby_map[$atts['orderby']] ?? 'fecha_inicio';
        $order = strtoupper($atts['order']) === 'DESC' ? 'DESC' : 'ASC';

        $sql = "SELECT * FROM $tabla_talleres WHERE " . implode(' AND ', $where) . " ORDER BY {$orderby_column} {$order} LIMIT %d";
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
            echo '<h3>' . __('Proponer un Taller', 'flavor-platform') . '</h3>';
            echo '<p>' . __('¿Tienes una habilidad que quieres compartir? Propón un taller para la comunidad.', 'flavor-platform') . '</p>';
            echo '<form class="flavor-form" id="form-proponer-taller">';
            echo '<p><label>' . __('Título del taller', 'flavor-platform') . '</label>';
            echo '<input type="text" name="titulo" required></p>';
            echo '<p><label>' . __('Descripción', 'flavor-platform') . '</label>';
            echo '<textarea name="descripcion" rows="4" required></textarea></p>';
            echo '<p><label>' . __('Categoría', 'flavor-platform') . '</label>';
            echo '<select name="categoria">';
            echo '<option value="artesania">' . __('Artesanía', 'flavor-platform') . '</option>';
            echo '<option value="cocina">' . __('Cocina', 'flavor-platform') . '</option>';
            echo '<option value="tecnologia">' . __('Tecnología', 'flavor-platform') . '</option>';
            echo '<option value="huerto">' . __('Huerto', 'flavor-platform') . '</option>';
            echo '<option value="otros">' . __('Otros', 'flavor-platform') . '</option>';
            echo '</select></p>';
            echo '<p><button type="submit" class="flavor-btn flavor-btn-primary">' . __('Enviar Propuesta', 'flavor-platform') . '</button></p>';
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
            echo '<p class="flavor-error">' . __('Taller no encontrado.', 'flavor-platform') . '</p>';
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
            echo '<span class="fecha"><strong>' . __('Fecha:', 'flavor-platform') . '</strong> ' . esc_html(date_i18n('d/m/Y H:i', strtotime($taller->fecha_inicio))) . '</span>';
            echo '<span class="ubicacion"><strong>' . __('Lugar:', 'flavor-platform') . '</strong> ' . esc_html($taller->ubicacion) . '</span>';
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
            'titulo' => __('Mis Talleres', 'flavor-platform'),
            'icono' => 'dashicons-hammer',
            'callback' => [$this, 'render_tab_inscripciones'],
            'orden' => 35,
            'modulo' => 'talleres',
        ];

        // Tab adicional para organizadores
        if ($this->usuario_es_organizador()) {
            $tabs['talleres-organizador'] = [
                'titulo' => __('Organizar Talleres', 'flavor-platform'),
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
            wp_send_json_error(__('Debes iniciar sesión', 'flavor-platform'));
        }

        $taller_id = isset($_POST['taller_id']) ? absint($_POST['taller_id']) : 0;
        $usuario_id = get_current_user_id();

        if (!$taller_id) {
            wp_send_json_error(__('Taller no válido', 'flavor-platform'));
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
            wp_send_json_error(__('Ya estás inscrito en este taller', 'flavor-platform'));
        }

        // Verificar plazas disponibles
        $taller = $wpdb->get_row($wpdb->prepare("SELECT * FROM $tabla_talleres WHERE id = %d", $taller_id));

        if (!$taller) {
            wp_send_json_error(__('Taller no encontrado', 'flavor-platform'));
        }

        $inscritos = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM $tabla_inscripciones WHERE taller_id = %d AND estado = 'confirmada'",
            $taller_id
        ));

        if ($taller->max_participantes > 0 && $inscritos >= $taller->max_participantes) {
            wp_send_json_error(__('No hay plazas disponibles', 'flavor-platform'));
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
                'mensaje' => __('Te has inscrito correctamente', 'flavor-platform'),
            ]);
        } else {
            wp_send_json_error(__('Error al procesar la inscripción', 'flavor-platform'));
        }
    }

    /**
     * AJAX: Cancelar inscripción
     */
    public function ajax_cancelar_inscripcion() {
        check_ajax_referer('talleres_nonce', 'nonce');

        if (!is_user_logged_in()) {
            wp_send_json_error(__('Debes iniciar sesión', 'flavor-platform'));
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
            wp_send_json_success(['mensaje' => __('Inscripción cancelada', 'flavor-platform')]);
        } else {
            wp_send_json_error(__('Error al cancelar', 'flavor-platform'));
        }
    }

    /**
     * AJAX: Valorar taller
     */
    public function ajax_valorar() {
        check_ajax_referer('talleres_nonce', 'nonce');

        if (!is_user_logged_in()) {
            wp_send_json_error(__('Debes iniciar sesión', 'flavor-platform'));
        }

        $taller_id = isset($_POST['taller_id']) ? absint($_POST['taller_id']) : 0;
        $valoracion = isset($_POST['valoracion']) ? intval($_POST['valoracion']) : 0;
        $comentario = isset($_POST['comentario']) ? sanitize_textarea_field($_POST['comentario']) : '';
        $usuario_id = get_current_user_id();

        if (!$taller_id || $valoracion < 1 || $valoracion > 5) {
            wp_send_json_error(__('Datos no válidos', 'flavor-platform'));
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
            wp_send_json_success(['mensaje' => __('Gracias por tu valoración', 'flavor-platform')]);
        } else {
            wp_send_json_error(__('Error al guardar valoración', 'flavor-platform'));
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
            echo '<p class="no-resultados">' . __('No se encontraron talleres', 'flavor-platform') . '</p>';
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
                    <?php _e('Ver Detalles', 'flavor-platform'); ?>
                </a>
            </div>
        </div>
        <?php
    }
}

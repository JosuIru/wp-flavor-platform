<?php
/**
 * Frontend Controller para Ayuda Vecinal
 *
 * @package FlavorChatIA
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Controlador Frontend para el módulo de Ayuda Vecinal
 * Gestiona shortcodes, assets y dashboard tabs del frontend
 */
class Flavor_Ayuda_Vecinal_Frontend_Controller {

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
        add_action('wp_ajax_ayuda_vecinal_solicitar', [$this, 'ajax_solicitar_ayuda']);
        add_action('wp_ajax_ayuda_vecinal_ofrecer', [$this, 'ajax_ofrecer_ayuda']);
        add_action('wp_ajax_ayuda_vecinal_aceptar', [$this, 'ajax_aceptar_solicitud']);
        add_action('wp_ajax_ayuda_vecinal_completar', [$this, 'ajax_completar_ayuda']);
        add_action('wp_ajax_ayuda_vecinal_valorar', [$this, 'ajax_valorar']);
        add_action('wp_ajax_ayuda_vecinal_filtrar', [$this, 'ajax_filtrar']);
        add_action('wp_ajax_nopriv_ayuda_vecinal_filtrar', [$this, 'ajax_filtrar']);

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
            'flavor-ayuda-vecinal',
            $base_url . '/assets/css/ayuda-vecinal.css',
            [],
            $version
        );

        // JS
        wp_register_script(
            'flavor-ayuda-vecinal',
            $base_url . '/assets/js/ayuda-vecinal.js',
            ['jquery'],
            $version,
            true
        );

        // Localizar script
        wp_localize_script('flavor-ayuda-vecinal', 'flavorAyudaVecinal', [
            'ajaxUrl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('ayuda_vecinal_nonce'),
            'i18n' => [
                'solicitud_enviada' => __('Solicitud de ayuda enviada', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'oferta_enviada' => __('Oferta de ayuda registrada', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'ayuda_completada' => __('Ayuda completada', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'error' => __('Ha ocurrido un error', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'confirmacion' => __('¿Estás seguro?', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'cargando' => __('Cargando...', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'gracias' => __('¡Gracias por tu ayuda!', FLAVOR_PLATFORM_TEXT_DOMAIN),
            ],
        ]);
    }

    /**
     * Encolar assets cuando sea necesario
     */
    public function encolar_assets() {
        wp_enqueue_style('flavor-ayuda-vecinal');
        wp_enqueue_script('flavor-ayuda-vecinal');
    }

    /**
     * Registrar shortcodes del módulo
     */
    public function registrar_shortcodes() {
        $shortcodes = [
            'ayuda_vecinal_solicitudes' => 'shortcode_solicitudes',
            'ayuda_vecinal_voluntarios' => 'shortcode_voluntarios',
            'ayuda_vecinal_solicitar' => 'shortcode_solicitar',
            'ayuda_vecinal_ofrecer' => 'shortcode_ofrecer',
            'ayuda_vecinal_mis_solicitudes' => 'shortcode_mis_solicitudes',
            'ayuda_vecinal_mis_ayudas' => 'shortcode_mis_ayudas',
            'ayuda_vecinal_estadisticas' => 'shortcode_estadisticas',
            'ayuda_vecinal_cercana' => 'shortcode_cercana',
        ];

        foreach ($shortcodes as $tag => $method) {
            if (!shortcode_exists($tag)) {
                add_shortcode($tag, [$this, $method]);
            }
        }
    }

    /**
     * Shortcode: Listado de solicitudes de ayuda
     */
    public function shortcode_solicitudes($atts) {
        $this->encolar_assets();

        $atts = shortcode_atts([
            'categoria' => '',
            'urgencia' => '',
            'limite' => 12,
            'mostrar_filtros' => 'true',
        ], $atts);

        ob_start();
        $this->render_solicitudes($atts);
        return ob_get_clean();
    }

    /**
     * Shortcode: Listado de voluntarios
     */
    public function shortcode_voluntarios($atts) {
        $this->encolar_assets();

        $atts = shortcode_atts([
            'categoria' => '',
            'limite' => 20,
        ], $atts);

        ob_start();
        $this->render_voluntarios($atts);
        return ob_get_clean();
    }

    /**
     * Shortcode: Formulario solicitar ayuda
     */
    public function shortcode_solicitar($atts) {
        if (!is_user_logged_in()) {
            return '<p class="flavor-login-required">' . __('Inicia sesión para solicitar ayuda.', FLAVOR_PLATFORM_TEXT_DOMAIN) . '</p>';
        }

        $this->encolar_assets();

        ob_start();
        $this->render_formulario_solicitar();
        return ob_get_clean();
    }

    /**
     * Shortcode: Formulario ofrecer ayuda
     */
    public function shortcode_ofrecer($atts) {
        if (!is_user_logged_in()) {
            return '<p class="flavor-login-required">' . __('Inicia sesión para ofrecer ayuda.', FLAVOR_PLATFORM_TEXT_DOMAIN) . '</p>';
        }

        $this->encolar_assets();

        ob_start();
        $this->render_formulario_ofrecer();
        return ob_get_clean();
    }

    /**
     * Shortcode: Mis solicitudes
     */
    public function shortcode_mis_solicitudes($atts) {
        if (!is_user_logged_in()) {
            return '<p class="flavor-login-required">' . __('Inicia sesión para ver tus solicitudes.', FLAVOR_PLATFORM_TEXT_DOMAIN) . '</p>';
        }

        $this->encolar_assets();

        $atts = shortcode_atts([
            'estado' => '',
            'limite' => 20,
        ], $atts);

        ob_start();
        $this->render_mis_solicitudes($atts);
        return ob_get_clean();
    }

    /**
     * Shortcode: Mis ayudas ofrecidas
     */
    public function shortcode_mis_ayudas($atts) {
        if (!is_user_logged_in()) {
            return '<p class="flavor-login-required">' . __('Inicia sesión para ver tus ayudas.', FLAVOR_PLATFORM_TEXT_DOMAIN) . '</p>';
        }

        $this->encolar_assets();

        $atts = shortcode_atts([
            'estado' => '',
            'limite' => 20,
        ], $atts);

        ob_start();
        $this->render_mis_ayudas($atts);
        return ob_get_clean();
    }

    /**
     * Shortcode: Estadísticas de la comunidad
     */
    public function shortcode_estadisticas($atts) {
        $this->encolar_assets();

        ob_start();
        $this->render_estadisticas();
        return ob_get_clean();
    }

    /**
     * Shortcode: Solicitudes de ayuda cercanas (widget compacto)
     * Muestra solicitudes de ayuda próximas al usuario
     */
    public function shortcode_cercana($atts) {
        $this->encolar_assets();

        $atts = shortcode_atts([
            'limite' => 3,
            'radio_km' => 5,
        ], $atts);

        global $wpdb;
        $tabla_solicitudes = $wpdb->prefix . 'flavor_ayuda_vecinal_solicitudes';

        if (!Flavor_Chat_Helpers::tabla_existe($tabla_solicitudes)) {
            return '<p class="flavor-error-mini">' . __('Módulo no disponible.', FLAVOR_PLATFORM_TEXT_DOMAIN) . '</p>';
        }

        // Obtener solicitudes abiertas recientes
        $limite = intval($atts['limite']);
        $solicitudes = $wpdb->get_results($wpdb->prepare(
            "SELECT s.*, u.display_name as solicitante_nombre
             FROM $tabla_solicitudes s
             LEFT JOIN {$wpdb->users} u ON s.usuario_id = u.ID
             WHERE s.estado = 'abierta'
             ORDER BY
                CASE s.urgencia WHEN 'alta' THEN 1 WHEN 'media' THEN 2 ELSE 3 END,
                s.fecha_creacion DESC
             LIMIT %d",
            $limite
        ));

        if (empty($solicitudes)) {
            return '<div class="ayuda-cercana-vacio"><p>' . __('No hay solicitudes de ayuda cercanas.', FLAVOR_PLATFORM_TEXT_DOMAIN) . '</p></div>';
        }

        ob_start();
        ?>
        <div class="ayuda-vecinal-cercana">
            <ul class="lista-solicitudes-mini">
                <?php foreach ($solicitudes as $solicitud): ?>
                <li class="solicitud-mini urgencia-<?php echo esc_attr($solicitud->urgencia); ?>">
                    <span class="solicitud-categoria"><?php echo esc_html(ucfirst($solicitud->categoria)); ?></span>
                    <span class="solicitud-titulo"><?php echo esc_html(wp_trim_words($solicitud->titulo, 8)); ?></span>
                    <span class="solicitud-meta">
                        <?php echo esc_html(human_time_diff(strtotime($solicitud->fecha_creacion))); ?>
                        <?php if ($solicitud->urgencia === 'alta'): ?>
                            <span class="badge-urgente"><?php esc_html_e('Urgente', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></span>
                        <?php endif; ?>
                    </span>
                </li>
                <?php endforeach; ?>
            </ul>
            <a href="<?php echo esc_url(home_url('/ayuda-vecinal/')); ?>" class="ver-mas-link">
                <?php esc_html_e('Ver todas las solicitudes', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
            </a>
        </div>
        <?php
        return ob_get_clean();
    }

    /**
     * Renderizar listado de solicitudes
     */
    private function render_solicitudes($atts) {
        global $wpdb;
        $tabla_solicitudes = $wpdb->prefix . 'flavor_ayuda_vecinal_solicitudes';

        if (!Flavor_Chat_Helpers::tabla_existe($tabla_solicitudes)) {
            echo '<p class="flavor-error">' . __('El módulo de ayuda vecinal no está configurado.', FLAVOR_PLATFORM_TEXT_DOMAIN) . '</p>';
            return;
        }

        $where = ["estado = 'abierta'"];
        $params = [];

        if (!empty($atts['categoria'])) {
            $where[] = "categoria = %s";
            $params[] = $atts['categoria'];
        }

        if (!empty($atts['urgencia'])) {
            $where[] = "urgencia = %s";
            $params[] = $atts['urgencia'];
        }

        $sql = "SELECT * FROM $tabla_solicitudes WHERE " . implode(' AND ', $where) . " ORDER BY fecha_creacion DESC LIMIT %d";
        $params[] = intval($atts['limite']);

        $solicitudes = $wpdb->get_results($wpdb->prepare($sql, ...$params));

        ?>
        <div class="flavor-ayuda-solicitudes">
            <?php if ($atts['mostrar_filtros'] === 'true') : ?>
                <div class="flavor-filtros">
                    <select id="filtro-categoria" class="filtro-ayuda">
                        <option value=""><?php _e('Todas las categorías', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></option>
                        <option value="compras"><?php _e('Compras', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></option>
                        <option value="transporte"><?php _e('Transporte', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></option>
                        <option value="compania"><?php _e('Compañía', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></option>
                        <option value="tareas"><?php _e('Tareas domésticas', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></option>
                        <option value="tecnologia"><?php _e('Tecnología', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></option>
                        <option value="otros"><?php _e('Otros', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></option>
                    </select>
                    <select id="filtro-urgencia" class="filtro-ayuda">
                        <option value=""><?php _e('Cualquier urgencia', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></option>
                        <option value="alta"><?php _e('Urgente', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></option>
                        <option value="media"><?php _e('Media', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></option>
                        <option value="baja"><?php _e('Baja', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></option>
                    </select>
                </div>
            <?php endif; ?>

            <div class="solicitudes-lista" id="solicitudes-lista">
                <?php if (empty($solicitudes)) : ?>
                    <p class="no-resultados"><?php _e('No hay solicitudes de ayuda activas.', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></p>
                <?php else : ?>
                    <?php foreach ($solicitudes as $solicitud) : ?>
                        <?php $this->render_solicitud_card($solicitud); ?>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>
        <?php
    }

    /**
     * Renderizar listado de voluntarios
     */
    private function render_voluntarios($atts) {
        global $wpdb;
        $tabla_voluntarios = $wpdb->prefix . 'flavor_ayuda_vecinal_voluntarios';

        $voluntarios = $wpdb->get_results($wpdb->prepare(
            "SELECT v.*, u.display_name, u.user_email
             FROM $tabla_voluntarios v
             JOIN {$wpdb->users} u ON v.usuario_id = u.ID
             WHERE v.estado = 'activo'
             ORDER BY v.total_ayudas DESC
             LIMIT %d",
            intval($atts['limite'])
        ));

        ?>
        <div class="flavor-ayuda-voluntarios">
            <h3><?php _e('Voluntarios de la Comunidad', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></h3>
            <div class="voluntarios-grid">
                <?php foreach ($voluntarios as $voluntario) : ?>
                    <div class="voluntario-card">
                        <div class="voluntario-avatar">
                            <?php echo get_avatar($voluntario->usuario_id, 64); ?>
                        </div>
                        <div class="voluntario-info">
                            <h4><?php echo esc_html($voluntario->display_name); ?></h4>
                            <div class="voluntario-stats">
                                <span class="ayudas"><?php printf(__('%d ayudas', FLAVOR_PLATFORM_TEXT_DOMAIN), $voluntario->total_ayudas); ?></span>
                                <?php if ($voluntario->valoracion_promedio > 0) : ?>
                                    <span class="valoracion">★ <?php echo esc_html(number_format($voluntario->valoracion_promedio, 1)); ?></span>
                                <?php endif; ?>
                            </div>
                            <?php if (!empty($voluntario->categorias)) : ?>
                                <div class="voluntario-categorias">
                                    <?php
                                    $categorias = maybe_unserialize($voluntario->categorias);
                                    if (is_array($categorias)) {
                                        foreach (array_slice($categorias, 0, 3) as $cat) {
                                            echo '<span class="categoria">' . esc_html(ucfirst($cat)) . '</span>';
                                        }
                                    }
                                    ?>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
        <?php
    }

    /**
     * Renderizar formulario solicitar ayuda
     */
    private function render_formulario_solicitar() {
        ?>
        <div class="flavor-ayuda-form">
            <h3><?php _e('Solicitar Ayuda', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></h3>
            <p class="form-intro"><?php _e('¿Necesitas ayuda? Describe tu situación y un vecino voluntario te contactará.', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></p>

            <form id="form-solicitar-ayuda" class="flavor-form">
                <?php wp_nonce_field('ayuda_vecinal_nonce', 'av_nonce_field'); ?>

                <div class="form-group">
                    <label><?php _e('Título', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></label>
                    <input type="text" name="titulo" required placeholder="<?php _e('Ej: Necesito ayuda con la compra', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>">
                </div>

                <div class="form-group">
                    <label><?php _e('Descripción', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></label>
                    <textarea name="descripcion" rows="4" required placeholder="<?php _e('Describe qué tipo de ayuda necesitas...', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>"></textarea>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label><?php _e('Categoría', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></label>
                        <select name="categoria" required>
                            <option value=""><?php _e('Selecciona...', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></option>
                            <option value="compras"><?php _e('Compras', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></option>
                            <option value="transporte"><?php _e('Transporte', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></option>
                            <option value="compania"><?php _e('Compañía', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></option>
                            <option value="tareas"><?php _e('Tareas domésticas', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></option>
                            <option value="tecnologia"><?php _e('Tecnología', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></option>
                            <option value="otros"><?php _e('Otros', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label><?php _e('Urgencia', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></label>
                        <select name="urgencia" required>
                            <option value="baja"><?php _e('Baja - puede esperar', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></option>
                            <option value="media"><?php _e('Media - esta semana', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></option>
                            <option value="alta"><?php _e('Alta - urgente', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></option>
                        </select>
                    </div>
                </div>

                <div class="form-group">
                    <label><?php _e('Ubicación / Zona', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></label>
                    <input type="text" name="ubicacion" placeholder="<?php _e('Barrio o zona aproximada', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>">
                </div>

                <div class="form-actions">
                    <button type="submit" class="flavor-btn flavor-btn-primary">
                        <?php _e('Enviar Solicitud', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                    </button>
                </div>
            </form>
        </div>
        <?php
    }

    /**
     * Renderizar formulario ofrecer ayuda
     */
    private function render_formulario_ofrecer() {
        ?>
        <div class="flavor-ayuda-form">
            <h3><?php _e('Ofrecer Ayuda', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></h3>
            <p class="form-intro"><?php _e('Regístrate como voluntario para ayudar a tus vecinos.', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></p>

            <form id="form-ofrecer-ayuda" class="flavor-form">
                <?php wp_nonce_field('ayuda_vecinal_nonce', 'av_nonce_field'); ?>

                <div class="form-group">
                    <label><?php _e('¿En qué puedes ayudar?', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></label>
                    <div class="checkbox-group">
                        <label><input type="checkbox" name="categorias[]" value="compras"> <?php _e('Compras', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></label>
                        <label><input type="checkbox" name="categorias[]" value="transporte"> <?php _e('Transporte', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></label>
                        <label><input type="checkbox" name="categorias[]" value="compania"> <?php _e('Compañía', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></label>
                        <label><input type="checkbox" name="categorias[]" value="tareas"> <?php _e('Tareas domésticas', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></label>
                        <label><input type="checkbox" name="categorias[]" value="tecnologia"> <?php _e('Tecnología', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></label>
                        <label><input type="checkbox" name="categorias[]" value="otros"> <?php _e('Otros', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></label>
                    </div>
                </div>

                <div class="form-group">
                    <label><?php _e('Disponibilidad', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></label>
                    <textarea name="disponibilidad" rows="2" placeholder="<?php _e('Ej: Mañanas entre semana, fines de semana...', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>"></textarea>
                </div>

                <div class="form-group">
                    <label><?php _e('Zona donde puedes ayudar', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></label>
                    <input type="text" name="zona" placeholder="<?php _e('Barrios o zonas donde puedes desplazarte', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>">
                </div>

                <div class="form-group">
                    <label><?php _e('Presentación (opcional)', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></label>
                    <textarea name="presentacion" rows="3" placeholder="<?php _e('Cuéntanos un poco sobre ti...', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>"></textarea>
                </div>

                <div class="form-actions">
                    <button type="submit" class="flavor-btn flavor-btn-primary">
                        <?php _e('Registrarme como Voluntario', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                    </button>
                </div>
            </form>
        </div>
        <?php
    }

    /**
     * Renderizar mis solicitudes
     */
    private function render_mis_solicitudes($atts) {
        global $wpdb;
        $tabla_solicitudes = $wpdb->prefix . 'flavor_ayuda_vecinal_solicitudes';
        $usuario_id = get_current_user_id();

        $solicitudes = $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM $tabla_solicitudes WHERE usuario_id = %d ORDER BY fecha_creacion DESC LIMIT %d",
            $usuario_id,
            intval($atts['limite'])
        ));

        ?>
        <div class="flavor-mis-solicitudes">
            <?php if (empty($solicitudes)) : ?>
                <p class="no-resultados"><?php _e('No has creado solicitudes de ayuda.', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></p>
            <?php else : ?>
                <div class="solicitudes-lista">
                    <?php foreach ($solicitudes as $solicitud) : ?>
                        <div class="solicitud-item estado-<?php echo esc_attr($solicitud->estado); ?>">
                            <div class="solicitud-header">
                                <h4><?php echo esc_html($solicitud->titulo); ?></h4>
                                <span class="estado"><?php echo esc_html(ucfirst($solicitud->estado)); ?></span>
                            </div>
                            <p class="solicitud-descripcion"><?php echo esc_html(wp_trim_words($solicitud->descripcion, 20)); ?></p>
                            <div class="solicitud-meta">
                                <span class="categoria"><?php echo esc_html(ucfirst($solicitud->categoria)); ?></span>
                                <span class="fecha"><?php echo esc_html(date_i18n('d/m/Y', strtotime($solicitud->fecha_creacion))); ?></span>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
        <?php
    }

    /**
     * Renderizar mis ayudas ofrecidas
     */
    private function render_mis_ayudas($atts) {
        global $wpdb;
        $tabla_asignaciones = $wpdb->prefix . 'flavor_ayuda_vecinal_asignaciones';
        $tabla_solicitudes = $wpdb->prefix . 'flavor_ayuda_vecinal_solicitudes';
        $usuario_id = get_current_user_id();

        $ayudas = $wpdb->get_results($wpdb->prepare(
            "SELECT a.*, s.titulo, s.categoria
             FROM $tabla_asignaciones a
             JOIN $tabla_solicitudes s ON a.solicitud_id = s.id
             WHERE a.voluntario_id = %d
             ORDER BY a.fecha_asignacion DESC
             LIMIT %d",
            $usuario_id,
            intval($atts['limite'])
        ));

        ?>
        <div class="flavor-mis-ayudas">
            <?php if (empty($ayudas)) : ?>
                <p class="no-resultados"><?php _e('Aún no has ofrecido ayuda a ninguna solicitud.', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></p>
            <?php else : ?>
                <div class="ayudas-lista">
                    <?php foreach ($ayudas as $ayuda) : ?>
                        <div class="ayuda-item estado-<?php echo esc_attr($ayuda->estado); ?>">
                            <div class="ayuda-info">
                                <h4><?php echo esc_html($ayuda->titulo); ?></h4>
                                <span class="categoria"><?php echo esc_html(ucfirst($ayuda->categoria)); ?></span>
                            </div>
                            <div class="ayuda-estado">
                                <span class="estado"><?php echo esc_html(ucfirst($ayuda->estado)); ?></span>
                                <span class="fecha"><?php echo esc_html(date_i18n('d/m/Y', strtotime($ayuda->fecha_asignacion))); ?></span>
                            </div>
                            <?php if ($ayuda->estado === 'en_curso') : ?>
                                <button class="flavor-btn flavor-btn-sm flavor-btn-success btn-completar-ayuda"
                                        data-id="<?php echo esc_attr($ayuda->id); ?>">
                                    <?php _e('Marcar Completada', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                                </button>
                            <?php endif; ?>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
        <?php
    }

    /**
     * Renderizar estadísticas
     */
    private function render_estadisticas() {
        global $wpdb;
        $tabla_solicitudes = $wpdb->prefix . 'flavor_ayuda_vecinal_solicitudes';
        $tabla_voluntarios = $wpdb->prefix . 'flavor_ayuda_vecinal_voluntarios';

        $total_solicitudes = $wpdb->get_var("SELECT COUNT(*) FROM $tabla_solicitudes");
        $completadas = $wpdb->get_var("SELECT COUNT(*) FROM $tabla_solicitudes WHERE estado = 'completada'");
        $abiertas = $wpdb->get_var("SELECT COUNT(*) FROM $tabla_solicitudes WHERE estado = 'abierta'");
        $voluntarios = $wpdb->get_var("SELECT COUNT(*) FROM $tabla_voluntarios WHERE estado = 'activo'");

        ?>
        <div class="flavor-ayuda-stats">
            <h3><?php _e('Impacto en la Comunidad', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></h3>
            <div class="stats-grid">
                <div class="stat-card">
                    <span class="stat-number"><?php echo esc_html($completadas); ?></span>
                    <span class="stat-label"><?php _e('Ayudas Completadas', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></span>
                </div>
                <div class="stat-card">
                    <span class="stat-number"><?php echo esc_html($abiertas); ?></span>
                    <span class="stat-label"><?php _e('Solicitudes Abiertas', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></span>
                </div>
                <div class="stat-card">
                    <span class="stat-number"><?php echo esc_html($voluntarios); ?></span>
                    <span class="stat-label"><?php _e('Voluntarios Activos', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></span>
                </div>
                <div class="stat-card">
                    <span class="stat-number"><?php echo esc_html($total_solicitudes); ?></span>
                    <span class="stat-label"><?php _e('Total Solicitudes', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></span>
                </div>
            </div>
        </div>
        <?php
    }

    /**
     * Registrar tabs en el dashboard de usuario
     */
    public function registrar_tabs($tabs) {
        $tabs['ayuda-vecinal-solicitudes'] = [
            'titulo' => __('Mis Solicitudes', FLAVOR_PLATFORM_TEXT_DOMAIN),
            'icono' => 'dashicons-heart',
            'callback' => [$this, 'render_tab_solicitudes'],
            'orden' => 50,
            'modulo' => 'ayuda_vecinal',
        ];

        $tabs['ayuda-vecinal-ayudas'] = [
            'titulo' => __('Mis Ayudas', FLAVOR_PLATFORM_TEXT_DOMAIN),
            'icono' => 'dashicons-admin-users',
            'callback' => [$this, 'render_tab_ayudas'],
            'orden' => 51,
            'modulo' => 'ayuda_vecinal',
        ];

        return $tabs;
    }

    /**
     * Renderizar tab de solicitudes
     */
    public function render_tab_solicitudes() {
        $this->encolar_assets();
        $this->render_mis_solicitudes(['estado' => '', 'limite' => 20]);
    }

    /**
     * Renderizar tab de ayudas
     */
    public function render_tab_ayudas() {
        $this->encolar_assets();
        $this->render_mis_ayudas(['estado' => '', 'limite' => 20]);
    }

    /**
     * Cargar templates personalizados
     */
    public function cargar_template($template) {
        return $template;
    }

    /**
     * AJAX: Solicitar ayuda
     */
    public function ajax_solicitar_ayuda() {
        check_ajax_referer('ayuda_vecinal_nonce', 'nonce');

        if (!is_user_logged_in()) {
            wp_send_json_error(__('Debes iniciar sesión', FLAVOR_PLATFORM_TEXT_DOMAIN));
        }

        $titulo = isset($_POST['titulo']) ? sanitize_text_field($_POST['titulo']) : '';
        $descripcion = isset($_POST['descripcion']) ? sanitize_textarea_field($_POST['descripcion']) : '';
        $categoria = isset($_POST['categoria']) ? sanitize_text_field($_POST['categoria']) : '';
        $urgencia = isset($_POST['urgencia']) ? sanitize_text_field($_POST['urgencia']) : 'media';
        $ubicacion = isset($_POST['ubicacion']) ? sanitize_text_field($_POST['ubicacion']) : '';
        $usuario_id = get_current_user_id();

        if (empty($titulo) || empty($descripcion) || empty($categoria)) {
            wp_send_json_error(__('Todos los campos son obligatorios', FLAVOR_PLATFORM_TEXT_DOMAIN));
        }

        global $wpdb;
        $tabla_solicitudes = $wpdb->prefix . 'flavor_ayuda_vecinal_solicitudes';

        $resultado = $wpdb->insert($tabla_solicitudes, [
            'usuario_id' => $usuario_id,
            'titulo' => $titulo,
            'descripcion' => $descripcion,
            'categoria' => $categoria,
            'urgencia' => $urgencia,
            'ubicacion' => $ubicacion,
            'estado' => 'abierta',
            'fecha_creacion' => current_time('mysql'),
        ]);

        if ($resultado) {
            wp_send_json_success([
                'mensaje' => __('Solicitud de ayuda enviada', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'solicitud_id' => $wpdb->insert_id,
            ]);
        } else {
            wp_send_json_error(__('Error al enviar solicitud', FLAVOR_PLATFORM_TEXT_DOMAIN));
        }
    }

    /**
     * AJAX: Ofrecer ayuda (registrarse como voluntario)
     */
    public function ajax_ofrecer_ayuda() {
        check_ajax_referer('ayuda_vecinal_nonce', 'nonce');

        if (!is_user_logged_in()) {
            wp_send_json_error(__('Debes iniciar sesión', FLAVOR_PLATFORM_TEXT_DOMAIN));
        }

        $categorias = isset($_POST['categorias']) ? array_map('sanitize_text_field', $_POST['categorias']) : [];
        $disponibilidad = isset($_POST['disponibilidad']) ? sanitize_textarea_field($_POST['disponibilidad']) : '';
        $zona = isset($_POST['zona']) ? sanitize_text_field($_POST['zona']) : '';
        $presentacion = isset($_POST['presentacion']) ? sanitize_textarea_field($_POST['presentacion']) : '';
        $usuario_id = get_current_user_id();

        if (empty($categorias)) {
            wp_send_json_error(__('Selecciona al menos una categoría', FLAVOR_PLATFORM_TEXT_DOMAIN));
        }

        global $wpdb;
        $tabla_voluntarios = $wpdb->prefix . 'flavor_ayuda_vecinal_voluntarios';

        // Verificar si ya es voluntario
        $existe = $wpdb->get_var($wpdb->prepare(
            "SELECT id FROM $tabla_voluntarios WHERE usuario_id = %d",
            $usuario_id
        ));

        $datos = [
            'usuario_id' => $usuario_id,
            'categorias' => maybe_serialize($categorias),
            'disponibilidad' => $disponibilidad,
            'zona' => $zona,
            'presentacion' => $presentacion,
            'estado' => 'activo',
            'fecha_registro' => current_time('mysql'),
        ];

        if ($existe) {
            $resultado = $wpdb->update($tabla_voluntarios, $datos, ['id' => $existe]);
        } else {
            $resultado = $wpdb->insert($tabla_voluntarios, $datos);
        }

        if ($resultado !== false) {
            wp_send_json_success(['mensaje' => __('Te has registrado como voluntario', FLAVOR_PLATFORM_TEXT_DOMAIN)]);
        } else {
            wp_send_json_error(__('Error al registrar', FLAVOR_PLATFORM_TEXT_DOMAIN));
        }
    }

    /**
     * AJAX: Aceptar solicitud
     */
    public function ajax_aceptar_solicitud() {
        check_ajax_referer('ayuda_vecinal_nonce', 'nonce');

        if (!is_user_logged_in()) {
            wp_send_json_error(__('Debes iniciar sesión', FLAVOR_PLATFORM_TEXT_DOMAIN));
        }

        $solicitud_id = isset($_POST['solicitud_id']) ? absint($_POST['solicitud_id']) : 0;
        $usuario_id = get_current_user_id();

        global $wpdb;
        $tabla_asignaciones = $wpdb->prefix . 'flavor_ayuda_vecinal_asignaciones';
        $tabla_solicitudes = $wpdb->prefix . 'flavor_ayuda_vecinal_solicitudes';

        // Crear asignación
        $resultado = $wpdb->insert($tabla_asignaciones, [
            'solicitud_id' => $solicitud_id,
            'voluntario_id' => $usuario_id,
            'estado' => 'en_curso',
            'fecha_asignacion' => current_time('mysql'),
        ]);

        if ($resultado) {
            // Actualizar estado de la solicitud
            $wpdb->update($tabla_solicitudes, ['estado' => 'en_curso'], ['id' => $solicitud_id]);

            wp_send_json_success(['mensaje' => __('Has aceptado ayudar', FLAVOR_PLATFORM_TEXT_DOMAIN)]);
        } else {
            wp_send_json_error(__('Error al aceptar', FLAVOR_PLATFORM_TEXT_DOMAIN));
        }
    }

    /**
     * AJAX: Completar ayuda
     */
    public function ajax_completar_ayuda() {
        check_ajax_referer('ayuda_vecinal_nonce', 'nonce');

        if (!is_user_logged_in()) {
            wp_send_json_error(__('Debes iniciar sesión', FLAVOR_PLATFORM_TEXT_DOMAIN));
        }

        $asignacion_id = isset($_POST['asignacion_id']) ? absint($_POST['asignacion_id']) : 0;
        $usuario_id = get_current_user_id();

        global $wpdb;
        $tabla_asignaciones = $wpdb->prefix . 'flavor_ayuda_vecinal_asignaciones';
        $tabla_solicitudes = $wpdb->prefix . 'flavor_ayuda_vecinal_solicitudes';
        $tabla_voluntarios = $wpdb->prefix . 'flavor_ayuda_vecinal_voluntarios';

        $asignacion = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM $tabla_asignaciones WHERE id = %d AND voluntario_id = %d",
            $asignacion_id,
            $usuario_id
        ));

        if (!$asignacion) {
            wp_send_json_error(__('Asignación no encontrada', FLAVOR_PLATFORM_TEXT_DOMAIN));
        }

        // Actualizar asignación
        $wpdb->update($tabla_asignaciones,
            ['estado' => 'completada', 'fecha_completado' => current_time('mysql')],
            ['id' => $asignacion_id]
        );

        // Actualizar solicitud
        $wpdb->update($tabla_solicitudes, ['estado' => 'completada'], ['id' => $asignacion->solicitud_id]);

        // Incrementar contador del voluntario
        $wpdb->query($wpdb->prepare(
            "UPDATE $tabla_voluntarios SET total_ayudas = total_ayudas + 1 WHERE usuario_id = %d",
            $usuario_id
        ));

        wp_send_json_success(['mensaje' => __('¡Ayuda completada! Gracias por tu solidaridad', FLAVOR_PLATFORM_TEXT_DOMAIN)]);
    }

    /**
     * AJAX: Valorar
     */
    public function ajax_valorar() {
        check_ajax_referer('ayuda_vecinal_nonce', 'nonce');

        if (!is_user_logged_in()) {
            wp_send_json_error(__('Debes iniciar sesión', FLAVOR_PLATFORM_TEXT_DOMAIN));
        }

        $asignacion_id = isset($_POST['asignacion_id']) ? absint($_POST['asignacion_id']) : 0;
        $valoracion = isset($_POST['valoracion']) ? intval($_POST['valoracion']) : 0;
        $comentario = isset($_POST['comentario']) ? sanitize_textarea_field($_POST['comentario']) : '';

        if ($valoracion < 1 || $valoracion > 5) {
            wp_send_json_error(__('Valoración no válida', FLAVOR_PLATFORM_TEXT_DOMAIN));
        }

        global $wpdb;
        $tabla_valoraciones = $wpdb->prefix . 'flavor_ayuda_vecinal_valoraciones';

        $resultado = $wpdb->insert($tabla_valoraciones, [
            'asignacion_id' => $asignacion_id,
            'usuario_id' => get_current_user_id(),
            'valoracion' => $valoracion,
            'comentario' => $comentario,
            'fecha' => current_time('mysql'),
        ]);

        if ($resultado) {
            wp_send_json_success(['mensaje' => __('Gracias por tu valoración', FLAVOR_PLATFORM_TEXT_DOMAIN)]);
        } else {
            wp_send_json_error(__('Error al guardar valoración', FLAVOR_PLATFORM_TEXT_DOMAIN));
        }
    }

    /**
     * AJAX: Filtrar solicitudes
     */
    public function ajax_filtrar() {
        $categoria = isset($_POST['categoria']) ? sanitize_text_field($_POST['categoria']) : '';
        $urgencia = isset($_POST['urgencia']) ? sanitize_text_field($_POST['urgencia']) : '';

        global $wpdb;
        $tabla_solicitudes = $wpdb->prefix . 'flavor_ayuda_vecinal_solicitudes';

        $where = ["estado = 'abierta'"];
        $params = [];

        if (!empty($categoria)) {
            $where[] = "categoria = %s";
            $params[] = $categoria;
        }

        if (!empty($urgencia)) {
            $where[] = "urgencia = %s";
            $params[] = $urgencia;
        }

        $sql = "SELECT * FROM $tabla_solicitudes WHERE " . implode(' AND ', $where) . " ORDER BY fecha_creacion DESC LIMIT 50";

        if (!empty($params)) {
            $solicitudes = $wpdb->get_results($wpdb->prepare($sql, ...$params));
        } else {
            $solicitudes = $wpdb->get_results($sql);
        }

        ob_start();
        if (!empty($solicitudes)) {
            foreach ($solicitudes as $solicitud) {
                $this->render_solicitud_card($solicitud);
            }
        } else {
            echo '<p class="no-resultados">' . __('No se encontraron solicitudes', FLAVOR_PLATFORM_TEXT_DOMAIN) . '</p>';
        }
        $html = ob_get_clean();

        wp_send_json_success(['html' => $html, 'total' => count($solicitudes)]);
    }

    /**
     * Renderizar tarjeta de solicitud
     */
    private function render_solicitud_card($solicitud) {
        $usuario = get_userdata($solicitud->usuario_id);
        $urgencia_class = [
            'alta' => 'urgencia-alta',
            'media' => 'urgencia-media',
            'baja' => 'urgencia-baja',
        ];
        ?>
        <div class="solicitud-card <?php echo esc_attr($urgencia_class[$solicitud->urgencia] ?? ''); ?>" data-id="<?php echo esc_attr($solicitud->id); ?>">
            <div class="solicitud-header">
                <span class="categoria"><?php echo esc_html(ucfirst($solicitud->categoria)); ?></span>
                <span class="urgencia urgencia-<?php echo esc_attr($solicitud->urgencia); ?>">
                    <?php echo esc_html(ucfirst($solicitud->urgencia)); ?>
                </span>
            </div>
            <h4><?php echo esc_html($solicitud->titulo); ?></h4>
            <p class="solicitud-descripcion"><?php echo esc_html(wp_trim_words($solicitud->descripcion, 25)); ?></p>
            <div class="solicitud-footer">
                <div class="solicitud-autor">
                    <?php echo get_avatar($solicitud->usuario_id, 24); ?>
                    <span><?php echo esc_html($usuario ? $usuario->display_name : __('Anónimo', FLAVOR_PLATFORM_TEXT_DOMAIN)); ?></span>
                </div>
                <span class="fecha"><?php echo esc_html(human_time_diff(strtotime($solicitud->fecha_creacion), current_time('timestamp'))); ?></span>
            </div>
            <?php if (is_user_logged_in() && $solicitud->usuario_id != get_current_user_id()) : ?>
                <button class="flavor-btn flavor-btn-primary flavor-btn-block btn-ofrecer-ayuda" data-id="<?php echo esc_attr($solicitud->id); ?>">
                    <?php _e('Ofrecer Ayuda', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                </button>
            <?php endif; ?>
        </div>
        <?php
    }
}

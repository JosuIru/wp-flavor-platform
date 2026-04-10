<?php
/**
 * Frontend Controller para Participación Ciudadana
 *
 * @package FlavorPlatform
 * @subpackage Modules\Participacion
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Controlador Frontend para el módulo de Participación
 */
class Flavor_Participacion_Frontend_Controller {

    /**
     * Instancia singleton
     */
    private static $instance = null;

    /**
     * Obtener instancia
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
     * Inicializar hooks
     */
    private function init() {
        add_action('wp_enqueue_scripts', [$this, 'registrar_assets']);
        add_action('init', [$this, 'registrar_shortcodes']);

        // AJAX handlers
        add_action('wp_ajax_participacion_votar_encuesta', [$this, 'ajax_votar_encuesta']);
        add_action('wp_ajax_participacion_firmar_peticion', [$this, 'ajax_firmar_peticion']);
        add_action('wp_ajax_participacion_crear_peticion', [$this, 'ajax_crear_peticion']);
        add_action('wp_ajax_participacion_participar_debate', [$this, 'ajax_participar_debate']);
        add_action('wp_ajax_participacion_obtener_encuestas', [$this, 'ajax_obtener_encuestas']);
        add_action('wp_ajax_nopriv_participacion_obtener_encuestas', [$this, 'ajax_obtener_encuestas']);
    }

    /**
     * Registrar assets
     */
    public function registrar_assets() {
        $base_url = plugins_url('assets/', dirname(dirname(__FILE__)));
        $version = FLAVOR_PLATFORM_VERSION ?? '1.0.0';

        wp_register_style(
            'flavor-participacion',
            $base_url . 'css/participacion.css',
            [],
            $version
        );

        wp_register_script(
            'flavor-participacion',
            $base_url . 'js/participacion.js',
            ['jquery'],
            $version,
            true
        );

        wp_localize_script('flavor-participacion', 'flavorParticipacion', [
            'ajaxUrl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('participacion_nonce'),
            'i18n' => [
                'voto_registrado' => __('Tu voto ha sido registrado', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'firma_registrada' => __('Tu firma ha sido registrada', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'peticion_creada' => __('Petición creada correctamente', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'error' => __('Ha ocurrido un error', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'cargando' => __('Cargando...', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'confirmar_voto' => __('¿Confirmas tu voto?', FLAVOR_PLATFORM_TEXT_DOMAIN),
            ],
        ]);
    }

    /**
     * Encolar assets
     */
    public function encolar_assets() {
        wp_enqueue_style('flavor-participacion');
        wp_enqueue_script('flavor-participacion');
    }

    /**
     * Registrar shortcodes
     */
    public function registrar_shortcodes() {
        $shortcodes = [
            'participacion_encuestas' => 'shortcode_encuestas',
            'participacion_encuesta' => 'shortcode_encuesta',
            'participacion_peticiones' => 'shortcode_peticiones',
            'participacion_peticion' => 'shortcode_peticion',
            'participacion_crear_peticion' => 'shortcode_crear_peticion',
            'participacion_debates' => 'shortcode_debates',
            'participacion_debate' => 'shortcode_debate',
            'participacion_resumen' => 'shortcode_resumen',
        ];
        foreach ($shortcodes as $tag => $method) {
            if (!shortcode_exists($tag)) {
                add_shortcode($tag, [$this, $method]);
            }
        }
    }

    /**
     * Devuelve la URL actual para redirects y compartición.
     *
     * @return string
     */
    private function get_current_request_url() {
        return function_exists('flavor_current_request_url')
            ? flavor_current_request_url()
            : home_url('/');
    }

    /**
     * Renderiza un estado de login con acción.
     *
     * @param string $mensaje
     * @return string
     */
    private function render_login_required($mensaje) {
        return '<div class="flavor-empty-state">' .
            '<p>' . esc_html($mensaje) . '</p>' .
            '<a href="' . esc_url(wp_login_url($this->get_current_request_url())) . '" class="flavor-btn flavor-btn-primary">' .
            esc_html__('Iniciar sesión', FLAVOR_PLATFORM_TEXT_DOMAIN) .
            '</a></div>';
    }

    /**
     * Construye una URL del portal para Participación.
     *
     * @param string $action Acción/tab del portal.
     * @param array  $args   Query args.
     * @return string
     */
    private function get_portal_url($action, array $args = []) {
        $url = home_url('/mi-portal/participacion/' . trim($action, '/') . '/');

        if (!empty($args)) {
            $url = add_query_arg($args, $url);
        }

        return $url;
    }

    /**
     * Shortcode: Listado de encuestas
     */
    public function shortcode_encuestas($atts) {
        $this->encolar_assets();

        $atts = shortcode_atts([
            'estado' => 'activa',
            'limite' => 10,
            'mostrar_resultados' => 'false',
            // Parámetros visuales (VBP)
            'esquema_color' => 'default',
            'estilo_tarjeta' => 'elevated',
            'radio_bordes' => 'lg',
            'animacion_entrada' => 'fade',
        ], $atts);

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
        $visual_class_string = implode(' ', $visual_classes);

        global $wpdb;
        $tabla_encuestas = $wpdb->prefix . 'flavor_participacion_encuestas';

        if (!Flavor_Platform_Helpers::tabla_existe($tabla_encuestas)) {
            return '<p class="flavor-error">' . __('El módulo no está configurado.', FLAVOR_PLATFORM_TEXT_DOMAIN) . '</p>';
        }

        $where = "1=1";
        $params = [];

        if ($atts['estado'] === 'activa') {
            $where .= " AND estado = 'activa' AND (fecha_fin IS NULL OR fecha_fin >= CURDATE())";
        } elseif ($atts['estado'] === 'finalizada') {
            $where .= " AND (estado = 'finalizada' OR fecha_fin < CURDATE())";
        }

        $sql = "SELECT * FROM {$tabla_encuestas} WHERE {$where} ORDER BY created_at DESC LIMIT %d";
        $params[] = intval($atts['limite']);

        $encuestas = $wpdb->get_results($wpdb->prepare($sql, ...$params));

        ob_start();
        ?>
        <div class="flavor-participacion-encuestas <?php echo esc_attr($visual_class_string); ?>">
            <h2><?php esc_html_e('Encuestas', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></h2>

            <?php if (empty($encuestas)): ?>
                <div class="flavor-empty-state">
                    <span class="dashicons dashicons-chart-bar"></span>
                    <p><?php esc_html_e('No hay encuestas disponibles.', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></p>
                </div>
            <?php else: ?>
                <div class="encuestas-lista">
                    <?php foreach ($encuestas as $encuesta): ?>
                        <div class="encuesta-card">
                            <div class="encuesta-header">
                                <?php if ($encuesta->estado === 'activa'): ?>
                                    <span class="flavor-badge flavor-badge-success"><?php esc_html_e('Activa', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></span>
                                <?php else: ?>
                                    <span class="flavor-badge flavor-badge-secondary"><?php esc_html_e('Finalizada', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></span>
                                <?php endif; ?>
                                <?php if ($encuesta->fecha_fin): ?>
                                    <span class="encuesta-fecha">
                                        <?php esc_html_e('Hasta:', FLAVOR_PLATFORM_TEXT_DOMAIN); ?> <?php echo date_i18n('d/m/Y', strtotime($encuesta->fecha_fin)); ?>
                                    </span>
                                <?php endif; ?>
                            </div>
                            <h3><?php echo esc_html($encuesta->titulo); ?></h3>
                            <?php if ($encuesta->descripcion): ?>
                                <p class="encuesta-descripcion"><?php echo esc_html(wp_trim_words($encuesta->descripcion, 25)); ?></p>
                            <?php endif; ?>
                            <div class="encuesta-stats">
                                <span><span class="dashicons dashicons-groups"></span> <?php echo number_format_i18n($encuesta->total_votos ?? 0); ?> <?php esc_html_e('votos', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></span>
                            </div>
                            <a href="<?php echo esc_url($this->get_portal_url('encuesta', ['encuesta_slug' => $encuesta->slug])); ?>" class="flavor-btn flavor-btn-primary">
                                <?php echo $encuesta->estado === 'activa' ? esc_html__('Participar', FLAVOR_PLATFORM_TEXT_DOMAIN) : esc_html__('Ver Resultados', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                            </a>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
        <?php
        return ob_get_clean();
    }

    /**
     * Shortcode: Encuesta individual
     */
    public function shortcode_encuesta($atts) {
        $this->encolar_assets();

        $atts = shortcode_atts([
            'id' => 0,
            'slug' => '',
        ], $atts);

        global $wpdb;
        $tabla_encuestas = $wpdb->prefix . 'flavor_participacion_encuestas';
        $tabla_opciones = $wpdb->prefix . 'flavor_participacion_opciones';
        $tabla_votos = $wpdb->prefix . 'flavor_participacion_votos';

        $encuesta = null;
        if ($atts['id']) {
            $encuesta = $wpdb->get_row($wpdb->prepare(
                "SELECT * FROM {$tabla_encuestas} WHERE id = %d", $atts['id']
            ));
        } elseif ($atts['slug']) {
            $encuesta = $wpdb->get_row($wpdb->prepare(
                "SELECT * FROM {$tabla_encuestas} WHERE slug = %s", $atts['slug']
            ));
        } elseif (!empty($_GET['encuesta_slug'])) {
            $encuesta = $wpdb->get_row($wpdb->prepare(
                "SELECT * FROM {$tabla_encuestas} WHERE slug = %s", sanitize_text_field(wp_unslash($_GET['encuesta_slug']))
            ));
        } elseif (!empty($_GET['encuesta_id'])) {
            $encuesta = $wpdb->get_row($wpdb->prepare(
                "SELECT * FROM {$tabla_encuestas} WHERE id = %d", absint($_GET['encuesta_id'])
            ));
        }

        if (!$encuesta) {
            return '<p class="flavor-error">' . __('Encuesta no encontrada.', FLAVOR_PLATFORM_TEXT_DOMAIN) . '</p>';
        }

        $opciones = $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM {$tabla_opciones} WHERE encuesta_id = %d ORDER BY orden",
            $encuesta->id
        ));

        // Verificar si ya votó
        $ya_voto = false;
        $voto_usuario = null;
        if (is_user_logged_in()) {
            $voto_usuario = $wpdb->get_var($wpdb->prepare(
                "SELECT opcion_id FROM {$tabla_votos} WHERE encuesta_id = %d AND usuario_id = %d",
                $encuesta->id,
                get_current_user_id()
            ));
            $ya_voto = (bool) $voto_usuario;
        }

        $puede_votar = $encuesta->estado === 'activa' && !$ya_voto &&
            (!$encuesta->fecha_fin || strtotime($encuesta->fecha_fin) >= time());

        // Calcular porcentajes
        $total_votos = array_sum(array_column($opciones, 'votos'));

        ob_start();
        ?>
        <div class="flavor-participacion-encuesta" data-id="<?php echo esc_attr($encuesta->id); ?>">
            <div class="encuesta-header">
                <h1><?php echo esc_html($encuesta->titulo); ?></h1>
                <?php if ($encuesta->descripcion): ?>
                    <p class="encuesta-descripcion"><?php echo wp_kses_post($encuesta->descripcion); ?></p>
                <?php endif; ?>
                <div class="encuesta-meta">
                    <?php if ($encuesta->fecha_inicio): ?>
                        <span><?php esc_html_e('Inicio:', FLAVOR_PLATFORM_TEXT_DOMAIN); ?> <?php echo date_i18n('d/m/Y', strtotime($encuesta->fecha_inicio)); ?></span>
                    <?php endif; ?>
                    <?php if ($encuesta->fecha_fin): ?>
                        <span><?php esc_html_e('Fin:', FLAVOR_PLATFORM_TEXT_DOMAIN); ?> <?php echo date_i18n('d/m/Y', strtotime($encuesta->fecha_fin)); ?></span>
                    <?php endif; ?>
                    <span><?php echo number_format_i18n($total_votos); ?> <?php esc_html_e('participantes', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></span>
                </div>
            </div>

            <?php if ($puede_votar && is_user_logged_in()): ?>
                <form id="form-votar-encuesta" class="encuesta-votar">
                    <?php wp_nonce_field('participacion_nonce', 'participacion_nonce_field'); ?>
                    <input type="hidden" name="encuesta_id" value="<?php echo esc_attr($encuesta->id); ?>">

                    <div class="opciones-lista">
                        <?php foreach ($opciones as $opcion): ?>
                            <label class="opcion-item">
                                <input type="<?php echo $encuesta->tipo === 'multiple' ? 'checkbox' : 'radio'; ?>"
                                    name="opcion_id<?php echo $encuesta->tipo === 'multiple' ? '[]' : ''; ?>"
                                    value="<?php echo esc_attr($opcion->id); ?>">
                                <span class="opcion-texto"><?php echo esc_html($opcion->texto); ?></span>
                            </label>
                        <?php endforeach; ?>
                    </div>

                    <div class="encuesta-acciones">
                        <button type="submit" class="flavor-btn flavor-btn-primary">
                            <?php esc_html_e('Enviar Voto', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                        </button>
                    </div>
                </form>
            <?php elseif (!is_user_logged_in() && $encuesta->estado === 'activa'): ?>
                <?php echo $this->render_login_required(__('Inicia sesión para participar en esta encuesta.', FLAVOR_PLATFORM_TEXT_DOMAIN)); ?>
            <?php endif; ?>

            <?php if ($ya_voto || $encuesta->estado !== 'activa' || $encuesta->mostrar_resultados_parciales): ?>
                <div class="encuesta-resultados">
                    <h3><?php esc_html_e('Resultados', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></h3>
                    <?php foreach ($opciones as $opcion):
                        $porcentaje = $total_votos > 0 ? ($opcion->votos / $total_votos * 100) : 0;
                        $es_mi_voto = $voto_usuario == $opcion->id;
                    ?>
                        <div class="resultado-item <?php echo $es_mi_voto ? 'mi-voto' : ''; ?>">
                            <div class="resultado-texto">
                                <span><?php echo esc_html($opcion->texto); ?></span>
                                <?php if ($es_mi_voto): ?>
                                    <span class="mi-voto-badge"><?php esc_html_e('Tu voto', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></span>
                                <?php endif; ?>
                            </div>
                            <div class="resultado-barra">
                                <div class="barra-progreso" style="width: <?php echo esc_attr($porcentaje); ?>%;"></div>
                            </div>
                            <div class="resultado-datos">
                                <span class="porcentaje"><?php echo number_format_i18n($porcentaje, 1); ?>%</span>
                                <span class="votos"><?php echo number_format_i18n($opcion->votos); ?> <?php esc_html_e('votos', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></span>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
        <?php
        return ob_get_clean();
    }

    /**
     * Shortcode: Listado de peticiones
     */
    public function shortcode_peticiones($atts) {
        $this->encolar_assets();

        $atts = shortcode_atts([
            'estado' => 'activa',
            'limite' => 10,
            'orden' => 'recientes',
        ], $atts);

        global $wpdb;
        $tabla_peticiones = $wpdb->prefix . 'flavor_participacion_peticiones';

        if (!Flavor_Platform_Helpers::tabla_existe($tabla_peticiones)) {
            return '';
        }

        $orden_sql = $atts['orden'] === 'firmas' ? 'firmas_count DESC' : 'created_at DESC';

        $peticiones = $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM {$tabla_peticiones}
             WHERE estado IN ('activa', 'lograda')
             ORDER BY {$orden_sql}
             LIMIT %d",
            intval($atts['limite'])
        ));

        ob_start();
        ?>
        <div class="flavor-participacion-peticiones">
            <div class="peticiones-header">
                <h2><?php esc_html_e('Peticiones Ciudadanas', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></h2>
                <?php if (is_user_logged_in()): ?>
                    <a href="<?php echo esc_url($this->get_portal_url('crear-peticion')); ?>" class="flavor-btn flavor-btn-primary">
                        <span class="dashicons dashicons-plus-alt2"></span>
                        <?php esc_html_e('Nueva Petición', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                    </a>
                <?php endif; ?>
            </div>

            <?php if (empty($peticiones)): ?>
                <div class="flavor-empty-state">
                    <p><?php esc_html_e('No hay peticiones activas.', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></p>
                </div>
            <?php else: ?>
                <div class="peticiones-lista">
                    <?php foreach ($peticiones as $peticion): ?>
                        <?php
                        $progreso = $peticion->objetivo_firmas > 0
                            ? min(100, ($peticion->firmas_count / $peticion->objetivo_firmas) * 100)
                            : 0;
                        ?>
                        <div class="peticion-card <?php echo $peticion->estado === 'lograda' ? 'lograda' : ''; ?>">
                            <?php if ($peticion->estado === 'lograda'): ?>
                                <span class="peticion-lograda-badge">
                                    <span class="dashicons dashicons-yes"></span>
                                    <?php esc_html_e('¡Lograda!', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                                </span>
                            <?php endif; ?>

                            <h3><?php echo esc_html($peticion->titulo); ?></h3>
                            <p class="peticion-extracto"><?php echo esc_html(wp_trim_words($peticion->descripcion, 30)); ?></p>

                            <div class="peticion-progreso">
                                <div class="progreso-barra">
                                    <div class="progreso-relleno" style="width: <?php echo esc_attr($progreso); ?>%;"></div>
                                </div>
                                <div class="progreso-datos">
                                    <span class="firmas-actuales"><?php echo number_format_i18n($peticion->firmas_count); ?></span>
                                    <span class="firmas-objetivo">de <?php echo number_format_i18n($peticion->objetivo_firmas); ?> firmas</span>
                                </div>
                            </div>

                            <a href="<?php echo esc_url($this->get_portal_url('peticion', ['peticion_slug' => $peticion->slug])); ?>" class="flavor-btn flavor-btn-outline">
                                <?php esc_html_e('Ver y Firmar', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                            </a>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
        <?php
        return ob_get_clean();
    }

    /**
     * Shortcode: Petición individual
     */
    public function shortcode_peticion($atts) {
        $this->encolar_assets();

        $atts = shortcode_atts([
            'id' => 0,
            'slug' => '',
        ], $atts);

        global $wpdb;
        $tabla_peticiones = $wpdb->prefix . 'flavor_participacion_peticiones';
        $tabla_firmas = $wpdb->prefix . 'flavor_participacion_firmas';

        $peticion = null;
        if ($atts['id']) {
            $peticion = $wpdb->get_row($wpdb->prepare(
                "SELECT * FROM {$tabla_peticiones} WHERE id = %d", $atts['id']
            ));
        } elseif ($atts['slug']) {
            $peticion = $wpdb->get_row($wpdb->prepare(
                "SELECT * FROM {$tabla_peticiones} WHERE slug = %s", $atts['slug']
            ));
        } elseif (!empty($_GET['peticion_slug'])) {
            $peticion = $wpdb->get_row($wpdb->prepare(
                "SELECT * FROM {$tabla_peticiones} WHERE slug = %s", sanitize_text_field(wp_unslash($_GET['peticion_slug']))
            ));
        } elseif (!empty($_GET['peticion_id'])) {
            $peticion = $wpdb->get_row($wpdb->prepare(
                "SELECT * FROM {$tabla_peticiones} WHERE id = %d", absint($_GET['peticion_id'])
            ));
        }

        if (!$peticion) {
            return '<p class="flavor-error">' . __('Petición no encontrada.', FLAVOR_PLATFORM_TEXT_DOMAIN) . '</p>';
        }

        // Verificar si ya firmó
        $ya_firmo = false;
        if (is_user_logged_in()) {
            $ya_firmo = (bool) $wpdb->get_var($wpdb->prepare(
                "SELECT id FROM {$tabla_firmas} WHERE peticion_id = %d AND usuario_id = %d",
                $peticion->id,
                get_current_user_id()
            ));
        }

        $progreso = $peticion->objetivo_firmas > 0
            ? min(100, ($peticion->firmas_count / $peticion->objetivo_firmas) * 100)
            : 0;

        // Últimas firmas
        $ultimas_firmas = $wpdb->get_results($wpdb->prepare(
            "SELECT f.*, u.display_name
             FROM {$tabla_firmas} f
             LEFT JOIN {$wpdb->users} u ON f.usuario_id = u.ID
             WHERE f.peticion_id = %d AND f.publico = 1
             ORDER BY f.created_at DESC
             LIMIT 10",
            $peticion->id
        ));

        $creador = get_userdata($peticion->autor_id);

        ob_start();
        ?>
        <div class="flavor-participacion-peticion" data-id="<?php echo esc_attr($peticion->id); ?>">
            <div class="peticion-header">
                <?php if ($peticion->estado === 'lograda'): ?>
                    <div class="peticion-lograda-banner">
                        <span class="dashicons dashicons-yes-alt"></span>
                        <?php esc_html_e('¡Esta petición ha alcanzado su objetivo!', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                    </div>
                <?php endif; ?>

                <h1><?php echo esc_html($peticion->titulo); ?></h1>

                <div class="peticion-meta">
                    <span class="peticion-autor">
                        <?php esc_html_e('Iniciada por', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                        <?php echo esc_html($creador ? $creador->display_name : __('Anónimo', FLAVOR_PLATFORM_TEXT_DOMAIN)); ?>
                    </span>
                    <span class="peticion-fecha">
                        <?php echo date_i18n('d M Y', strtotime($peticion->created_at)); ?>
                    </span>
                    <?php if ($peticion->categoria): ?>
                        <span class="peticion-categoria"><?php echo esc_html($peticion->categoria); ?></span>
                    <?php endif; ?>
                </div>
            </div>

            <div class="peticion-progreso-grande">
                <div class="progreso-circular" data-porcentaje="<?php echo esc_attr($progreso); ?>">
                    <svg viewBox="0 0 100 100">
                        <circle class="progreso-fondo" cx="50" cy="50" r="45"/>
                        <circle class="progreso-valor" cx="50" cy="50" r="45"
                            stroke-dasharray="<?php echo 283 * $progreso / 100; ?> 283"/>
                    </svg>
                    <div class="progreso-texto">
                        <span class="firmas-numero"><?php echo number_format_i18n($peticion->firmas_count); ?></span>
                        <span class="firmas-label"><?php esc_html_e('firmas', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></span>
                    </div>
                </div>
                <div class="progreso-info">
                    <p class="objetivo">
                        <?php printf(
                            esc_html__('Objetivo: %s firmas', FLAVOR_PLATFORM_TEXT_DOMAIN),
                            number_format_i18n($peticion->objetivo_firmas)
                        ); ?>
                    </p>
                    <p class="faltan">
                        <?php
                        $faltan = max(0, $peticion->objetivo_firmas - $peticion->firmas_count);
                        printf(
                            esc_html__('Faltan %s firmas', FLAVOR_PLATFORM_TEXT_DOMAIN),
                            number_format_i18n($faltan)
                        );
                        ?>
                    </p>
                </div>
            </div>

            <?php if ($peticion->estado === 'activa' && is_user_logged_in() && !$ya_firmo): ?>
                <div class="peticion-firmar">
                    <form id="form-firmar-peticion" class="form-firmar">
                        <?php wp_nonce_field('participacion_nonce', 'participacion_nonce_field'); ?>
                        <input type="hidden" name="peticion_id" value="<?php echo esc_attr($peticion->id); ?>">

                        <div class="firmar-opciones">
                            <label class="checkbox-publico">
                                <input type="checkbox" name="publico" value="1" checked>
                                <?php esc_html_e('Mostrar mi nombre públicamente', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                            </label>
                        </div>

                        <button type="submit" class="flavor-btn flavor-btn-primary flavor-btn-lg">
                            <span class="dashicons dashicons-edit"></span>
                            <?php esc_html_e('Firmar esta Petición', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                        </button>
                    </form>
                </div>
            <?php elseif ($ya_firmo): ?>
                <div class="peticion-ya-firmada">
                    <span class="dashicons dashicons-yes-alt"></span>
                    <?php esc_html_e('Ya has firmado esta petición', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                </div>
            <?php elseif (!is_user_logged_in() && $peticion->estado === 'activa'): ?>
                <?php echo $this->render_login_required(__('Inicia sesión para firmar esta petición.', FLAVOR_PLATFORM_TEXT_DOMAIN)); ?>
            <?php endif; ?>

            <div class="peticion-contenido">
                <h2><?php esc_html_e('Descripción', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></h2>
                <?php echo wp_kses_post($peticion->descripcion); ?>

                <?php if ($peticion->destinatario): ?>
                    <h3><?php esc_html_e('Dirigida a', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></h3>
                    <p class="peticion-destinatario"><?php echo esc_html($peticion->destinatario); ?></p>
                <?php endif; ?>
            </div>

            <?php if (!empty($ultimas_firmas)): ?>
                <div class="peticion-firmantes">
                    <h3><?php esc_html_e('Últimas firmas', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></h3>
                    <div class="firmantes-lista">
                        <?php foreach ($ultimas_firmas as $firma): ?>
                            <div class="firmante">
                                <?php echo get_avatar($firma->usuario_id, 40); ?>
                                <span><?php echo esc_html($firma->display_name); ?></span>
                                <span class="firmante-fecha"><?php echo human_time_diff(strtotime($firma->created_at)); ?></span>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            <?php endif; ?>

            <div class="peticion-compartir">
                <h3><?php esc_html_e('Comparte esta petición', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></h3>
                <div class="compartir-botones">
                    <a href="https://twitter.com/intent/tweet?url=<?php echo urlencode($this->get_current_request_url()); ?>&text=<?php echo urlencode($peticion->titulo); ?>"
                        class="btn-compartir twitter" target="_blank" rel="noopener">
                        Twitter
                    </a>
                    <a href="https://www.facebook.com/sharer/sharer.php?u=<?php echo urlencode($this->get_current_request_url()); ?>"
                        class="btn-compartir facebook" target="_blank" rel="noopener">
                        Facebook
                    </a>
                    <a href="https://api.whatsapp.com/send?text=<?php echo urlencode($peticion->titulo . ' ' . $this->get_current_request_url()); ?>"
                        class="btn-compartir whatsapp" target="_blank" rel="noopener">
                        WhatsApp
                    </a>
                </div>
            </div>
        </div>
        <?php
        return ob_get_clean();
    }

    /**
     * Shortcode: Crear petición
     */
    public function shortcode_crear_peticion($atts) {
        if (!is_user_logged_in()) {
            return $this->render_login_required(__('Debes iniciar sesión para crear una petición.', FLAVOR_PLATFORM_TEXT_DOMAIN));
        }

        $this->encolar_assets();

        $categorias = [
            'Medio Ambiente', 'Movilidad', 'Servicios Públicos', 'Educación',
            'Cultura', 'Deportes', 'Urbanismo', 'Seguridad', 'Otros'
        ];

        ob_start();
        ?>
        <div class="flavor-participacion-crear-peticion">
            <h2><?php esc_html_e('Crear Nueva Petición', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></h2>

            <form id="form-crear-peticion" class="flavor-form">
                <?php wp_nonce_field('participacion_nonce', 'participacion_nonce_field'); ?>

                <div class="flavor-form-group">
                    <label for="titulo"><?php esc_html_e('Título de la petición', FLAVOR_PLATFORM_TEXT_DOMAIN); ?> *</label>
                    <input type="text" name="titulo" id="titulo" required maxlength="200">
                    <p class="flavor-form-help"><?php esc_html_e('Claro y conciso, máximo 200 caracteres', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></p>
                </div>

                <div class="flavor-form-row">
                    <div class="flavor-form-group flavor-form-col-6">
                        <label for="categoria"><?php esc_html_e('Categoría', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></label>
                        <select name="categoria" id="categoria">
                            <option value=""><?php esc_html_e('Selecciona...', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></option>
                            <?php foreach ($categorias as $cat): ?>
                                <option value="<?php echo esc_attr($cat); ?>"><?php echo esc_html($cat); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="flavor-form-group flavor-form-col-6">
                        <label for="destinatario"><?php esc_html_e('Dirigida a', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></label>
                        <input type="text" name="destinatario" id="destinatario" placeholder="<?php esc_attr_e('Ej: Ayuntamiento, Gobierno...', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>">
                    </div>
                </div>

                <div class="flavor-form-group">
                    <label for="descripcion"><?php esc_html_e('Descripción', FLAVOR_PLATFORM_TEXT_DOMAIN); ?> *</label>
                    <textarea name="descripcion" id="descripcion" rows="8" required></textarea>
                    <p class="flavor-form-help"><?php esc_html_e('Explica el problema y qué cambios propones', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></p>
                </div>

                <div class="flavor-form-group">
                    <label for="objetivo_firmas"><?php esc_html_e('Objetivo de firmas', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></label>
                    <input type="number" name="objetivo_firmas" id="objetivo_firmas" min="10" max="100000" value="500">
                </div>

                <div class="flavor-form-actions">
                    <button type="submit" class="flavor-btn flavor-btn-primary">
                        <?php esc_html_e('Crear Petición', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                    </button>
                </div>
            </form>
        </div>
        <?php
        return ob_get_clean();
    }

    /**
     * Shortcode: Debates
     */
    public function shortcode_debates($atts) {
        $this->encolar_assets();

        $atts = shortcode_atts([
            'limite' => 10,
        ], $atts);

        global $wpdb;
        $tabla_debates = $wpdb->prefix . 'flavor_participacion_debates';

        if (!Flavor_Platform_Helpers::tabla_existe($tabla_debates)) {
            return '';
        }

        $debates = $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM {$tabla_debates}
             WHERE estado = 'activo'
             ORDER BY created_at DESC
             LIMIT %d",
            intval($atts['limite'])
        ));

        ob_start();
        ?>
        <div class="flavor-participacion-debates">
            <h2><?php esc_html_e('Debates Ciudadanos', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></h2>

            <?php if (empty($debates)): ?>
                <div class="flavor-empty-state">
                    <p><?php esc_html_e('No hay debates abiertos actualmente.', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></p>
                </div>
            <?php else: ?>
                <div class="debates-lista">
                    <?php foreach ($debates as $debate): ?>
                        <div class="debate-card">
                            <h3><?php echo esc_html($debate->titulo); ?></h3>
                            <?php if ($debate->descripcion): ?>
                                <p><?php echo esc_html(wp_trim_words($debate->descripcion, 25)); ?></p>
                            <?php endif; ?>
                            <div class="debate-stats">
                                <span><span class="dashicons dashicons-admin-comments"></span> <?php echo number_format_i18n($debate->comentarios_count ?? 0); ?> <?php esc_html_e('comentarios', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></span>
                                <span><span class="dashicons dashicons-groups"></span> <?php echo number_format_i18n($debate->participantes_count ?? 0); ?> <?php esc_html_e('participantes', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></span>
                            </div>
                            <a href="<?php echo esc_url($this->get_portal_url('debate', ['debate_slug' => $debate->slug])); ?>" class="flavor-btn flavor-btn-outline">
                                <?php esc_html_e('Participar', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                            </a>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
        <?php
        return ob_get_clean();
    }

    /**
     * Shortcode: Debate individual
     */
    public function shortcode_debate($atts) {
        $this->encolar_assets();

        $atts = shortcode_atts([
            'id' => 0,
            'slug' => '',
        ], $atts);

        global $wpdb;
        $tabla_debates = $wpdb->prefix . 'flavor_participacion_debates';
        $tabla_comentarios = $wpdb->prefix . 'flavor_participacion_comentarios_debate';

        $debate = null;
        if ($atts['id']) {
            $debate = $wpdb->get_row($wpdb->prepare(
                "SELECT * FROM {$tabla_debates} WHERE id = %d", $atts['id']
            ));
        } elseif ($atts['slug']) {
            $debate = $wpdb->get_row($wpdb->prepare(
                "SELECT * FROM {$tabla_debates} WHERE slug = %s", $atts['slug']
            ));
        } elseif (!empty($_GET['debate_slug'])) {
            $debate = $wpdb->get_row($wpdb->prepare(
                "SELECT * FROM {$tabla_debates} WHERE slug = %s", sanitize_text_field(wp_unslash($_GET['debate_slug']))
            ));
        } elseif (!empty($_GET['debate_id'])) {
            $debate = $wpdb->get_row($wpdb->prepare(
                "SELECT * FROM {$tabla_debates} WHERE id = %d", absint($_GET['debate_id'])
            ));
        }

        if (!$debate) {
            return '<p class="flavor-error">' . __('Debate no encontrado.', FLAVOR_PLATFORM_TEXT_DOMAIN) . '</p>';
        }

        $comentarios = [];
        if (Flavor_Platform_Helpers::tabla_existe($tabla_comentarios)) {
            $comentarios = $wpdb->get_results($wpdb->prepare(
                "SELECT c.*, u.display_name as autor_nombre
                 FROM {$tabla_comentarios} c
                 LEFT JOIN {$wpdb->users} u ON c.autor_id = u.ID
                 WHERE c.debate_id = %d AND c.estado = 'aprobado' AND c.parent_id = 0
                 ORDER BY c.votos_positivos DESC, c.created_at DESC
                 LIMIT 100",
                $debate->id
            ));
        }

        ob_start();
        ?>
        <div class="flavor-participacion-debate" data-id="<?php echo esc_attr($debate->id); ?>">
            <div class="debate-header">
                <h1><?php echo esc_html($debate->titulo); ?></h1>
                <?php if ($debate->descripcion): ?>
                    <div class="debate-descripcion"><?php echo wp_kses_post($debate->descripcion); ?></div>
                <?php endif; ?>
            </div>

            <?php if (is_user_logged_in() && $debate->estado === 'activo'): ?>
                <div class="debate-participar">
                    <form id="form-comentar-debate" class="form-comentar">
                        <?php wp_nonce_field('participacion_nonce', 'participacion_nonce_field'); ?>
                        <input type="hidden" name="debate_id" value="<?php echo esc_attr($debate->id); ?>">
                        <textarea name="contenido" rows="3" placeholder="<?php esc_attr_e('Comparte tu opinión...', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>" required></textarea>
                        <button type="submit" class="flavor-btn flavor-btn-primary">
                            <?php esc_html_e('Publicar', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                        </button>
                    </form>
                </div>
            <?php endif; ?>

            <div class="debate-comentarios">
                <h3><?php echo number_format_i18n(count($comentarios)); ?> <?php esc_html_e('comentarios', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></h3>

                <?php if (empty($comentarios)): ?>
                    <p class="sin-comentarios"><?php esc_html_e('Sé el primero en comentar.', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></p>
                <?php else: ?>
                    <?php foreach ($comentarios as $comentario): ?>
                        <div class="comentario-debate">
                            <div class="comentario-votos">
                                <button class="btn-votar-up" data-comentario="<?php echo esc_attr($comentario->id); ?>">
                                    <span class="dashicons dashicons-arrow-up-alt2"></span>
                                </button>
                                <span class="votos-count"><?php echo intval($comentario->votos_positivos - $comentario->votos_negativos); ?></span>
                                <button class="btn-votar-down" data-comentario="<?php echo esc_attr($comentario->id); ?>">
                                    <span class="dashicons dashicons-arrow-down-alt2"></span>
                                </button>
                            </div>
                            <div class="comentario-contenido">
                                <div class="comentario-meta">
                                    <span class="autor"><?php echo esc_html($comentario->autor_nombre); ?></span>
                                    <span class="fecha"><?php echo human_time_diff(strtotime($comentario->created_at)); ?></span>
                                </div>
                                <p><?php echo esc_html($comentario->contenido); ?></p>
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
     * Shortcode: Resumen de participación
     */
    public function shortcode_resumen($atts) {
        $this->encolar_assets();

        global $wpdb;

        $stats = [
            'encuestas' => 0,
            'peticiones' => 0,
            'debates' => 0,
            'participantes' => 0,
        ];

        $tabla_encuestas = $wpdb->prefix . 'flavor_participacion_encuestas';
        if (Flavor_Platform_Helpers::tabla_existe($tabla_encuestas)) {
            $stats['encuestas'] = (int) $wpdb->get_var("SELECT COUNT(*) FROM {$tabla_encuestas} WHERE estado = 'activa'");
        }

        $tabla_peticiones = $wpdb->prefix . 'flavor_participacion_peticiones';
        if (Flavor_Platform_Helpers::tabla_existe($tabla_peticiones)) {
            $stats['peticiones'] = (int) $wpdb->get_var("SELECT COUNT(*) FROM {$tabla_peticiones} WHERE estado = 'activa'");
        }

        $tabla_debates = $wpdb->prefix . 'flavor_participacion_debates';
        if (Flavor_Platform_Helpers::tabla_existe($tabla_debates)) {
            $stats['debates'] = (int) $wpdb->get_var("SELECT COUNT(*) FROM {$tabla_debates} WHERE estado = 'activo'");
        }

        ob_start();
        ?>
        <div class="flavor-participacion-resumen">
            <div class="resumen-grid">
                <a href="<?php echo esc_url($this->get_portal_url('votaciones')); ?>" class="resumen-card">
                    <span class="dashicons dashicons-chart-bar"></span>
                    <span class="resumen-numero"><?php echo number_format_i18n($stats['encuestas']); ?></span>
                    <span class="resumen-label"><?php esc_html_e('Encuestas Activas', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></span>
                </a>
                <a href="<?php echo esc_url($this->get_portal_url('peticiones')); ?>" class="resumen-card">
                    <span class="dashicons dashicons-edit"></span>
                    <span class="resumen-numero"><?php echo number_format_i18n($stats['peticiones']); ?></span>
                    <span class="resumen-label"><?php esc_html_e('Peticiones', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></span>
                </a>
                <a href="<?php echo esc_url($this->get_portal_url('debates')); ?>" class="resumen-card">
                    <span class="dashicons dashicons-format-chat"></span>
                    <span class="resumen-numero"><?php echo number_format_i18n($stats['debates']); ?></span>
                    <span class="resumen-label"><?php esc_html_e('Debates', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></span>
                </a>
            </div>
        </div>
        <?php
        return ob_get_clean();
    }

    // ================================
    // AJAX HANDLERS
    // ================================

    /**
     * AJAX: Votar encuesta
     */
    public function ajax_votar_encuesta() {
        check_ajax_referer('participacion_nonce', 'participacion_nonce_field');

        if (!is_user_logged_in()) {
            wp_send_json_error(__('Debes iniciar sesión', FLAVOR_PLATFORM_TEXT_DOMAIN));
        }

        $encuesta_id = isset($_POST['encuesta_id']) ? absint($_POST['encuesta_id']) : 0;
        $opcion_id = isset($_POST['opcion_id']) ? $_POST['opcion_id'] : null;
        $usuario_id = get_current_user_id();

        if (!$encuesta_id || !$opcion_id) {
            wp_send_json_error(__('Selecciona una opción', FLAVOR_PLATFORM_TEXT_DOMAIN));
        }

        global $wpdb;
        $tabla_votos = $wpdb->prefix . 'flavor_participacion_votos';
        $tabla_opciones = $wpdb->prefix . 'flavor_participacion_opciones';
        $tabla_encuestas = $wpdb->prefix . 'flavor_participacion_encuestas';

        // Verificar si ya votó
        $ya_voto = $wpdb->get_var($wpdb->prepare(
            "SELECT id FROM {$tabla_votos} WHERE encuesta_id = %d AND usuario_id = %d",
            $encuesta_id,
            $usuario_id
        ));

        if ($ya_voto) {
            wp_send_json_error(__('Ya has votado en esta encuesta', FLAVOR_PLATFORM_TEXT_DOMAIN));
        }

        // Registrar voto(s)
        $opciones = is_array($opcion_id) ? $opcion_id : [$opcion_id];

        foreach ($opciones as $op_id) {
            $wpdb->insert($tabla_votos, [
                'encuesta_id' => $encuesta_id,
                'opcion_id' => absint($op_id),
                'usuario_id' => $usuario_id,
                'created_at' => current_time('mysql'),
            ]);

            $wpdb->query($wpdb->prepare(
                "UPDATE {$tabla_opciones} SET votos = votos + 1 WHERE id = %d",
                absint($op_id)
            ));
        }

        // Actualizar total de la encuesta
        $wpdb->query($wpdb->prepare(
            "UPDATE {$tabla_encuestas} SET total_votos = total_votos + 1 WHERE id = %d",
            $encuesta_id
        ));

        wp_send_json_success(['mensaje' => __('Tu voto ha sido registrado', FLAVOR_PLATFORM_TEXT_DOMAIN)]);
    }

    /**
     * AJAX: Firmar petición
     */
    public function ajax_firmar_peticion() {
        check_ajax_referer('participacion_nonce', 'participacion_nonce_field');

        if (!is_user_logged_in()) {
            wp_send_json_error(__('Debes iniciar sesión', FLAVOR_PLATFORM_TEXT_DOMAIN));
        }

        $peticion_id = isset($_POST['peticion_id']) ? absint($_POST['peticion_id']) : 0;
        $publico = isset($_POST['publico']) ? 1 : 0;
        $usuario_id = get_current_user_id();

        if (!$peticion_id) {
            wp_send_json_error(__('Petición no válida', FLAVOR_PLATFORM_TEXT_DOMAIN));
        }

        global $wpdb;
        $tabla_firmas = $wpdb->prefix . 'flavor_participacion_firmas';
        $tabla_peticiones = $wpdb->prefix . 'flavor_participacion_peticiones';

        // Verificar si ya firmó
        $ya_firmo = $wpdb->get_var($wpdb->prepare(
            "SELECT id FROM {$tabla_firmas} WHERE peticion_id = %d AND usuario_id = %d",
            $peticion_id,
            $usuario_id
        ));

        if ($ya_firmo) {
            wp_send_json_error(__('Ya has firmado esta petición', FLAVOR_PLATFORM_TEXT_DOMAIN));
        }

        $resultado = $wpdb->insert($tabla_firmas, [
            'peticion_id' => $peticion_id,
            'usuario_id' => $usuario_id,
            'publico' => $publico,
            'created_at' => current_time('mysql'),
        ]);

        if ($resultado) {
            // Incrementar contador
            $wpdb->query($wpdb->prepare(
                "UPDATE {$tabla_peticiones} SET firmas_count = firmas_count + 1 WHERE id = %d",
                $peticion_id
            ));

            // Verificar si se alcanzó el objetivo
            $peticion = $wpdb->get_row($wpdb->prepare(
                "SELECT firmas_count, objetivo_firmas FROM {$tabla_peticiones} WHERE id = %d",
                $peticion_id
            ));

            if ($peticion && $peticion->firmas_count >= $peticion->objetivo_firmas) {
                $wpdb->update($tabla_peticiones, ['estado' => 'lograda'], ['id' => $peticion_id]);
            }

            wp_send_json_success([
                'mensaje' => __('¡Gracias por firmar!', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'firmas_total' => intval($peticion->firmas_count) + 1,
            ]);
        } else {
            wp_send_json_error(__('Error al registrar firma', FLAVOR_PLATFORM_TEXT_DOMAIN));
        }
    }

    /**
     * AJAX: Crear petición
     */
    public function ajax_crear_peticion() {
        check_ajax_referer('participacion_nonce', 'participacion_nonce_field');

        if (!is_user_logged_in()) {
            wp_send_json_error(__('Debes iniciar sesión', FLAVOR_PLATFORM_TEXT_DOMAIN));
        }

        $titulo = isset($_POST['titulo']) ? sanitize_text_field($_POST['titulo']) : '';
        $descripcion = isset($_POST['descripcion']) ? wp_kses_post($_POST['descripcion']) : '';
        $categoria = isset($_POST['categoria']) ? sanitize_text_field($_POST['categoria']) : '';
        $destinatario = isset($_POST['destinatario']) ? sanitize_text_field($_POST['destinatario']) : '';
        $objetivo = isset($_POST['objetivo_firmas']) ? absint($_POST['objetivo_firmas']) : 500;

        if (empty($titulo) || empty($descripcion)) {
            wp_send_json_error(__('Título y descripción son obligatorios', FLAVOR_PLATFORM_TEXT_DOMAIN));
        }

        global $wpdb;
        $tabla_peticiones = $wpdb->prefix . 'flavor_participacion_peticiones';

        $slug = sanitize_title($titulo);
        $base_slug = $slug;
        $contador = 1;
        while ($wpdb->get_var($wpdb->prepare("SELECT id FROM {$tabla_peticiones} WHERE slug = %s", $slug))) {
            $slug = $base_slug . '-' . $contador++;
        }

        $resultado = $wpdb->insert($tabla_peticiones, [
            'titulo' => $titulo,
            'slug' => $slug,
            'descripcion' => $descripcion,
            'categoria' => $categoria,
            'destinatario' => $destinatario,
            'objetivo_firmas' => $objetivo,
            'firmas_count' => 1, // La firma del creador
            'autor_id' => get_current_user_id(),
            'estado' => 'activa',
            'created_at' => current_time('mysql'),
        ]);

        if ($resultado) {
            $peticion_id = $wpdb->insert_id;

            // Auto-firmar
            $tabla_firmas = $wpdb->prefix . 'flavor_participacion_firmas';
            $wpdb->insert($tabla_firmas, [
                'peticion_id' => $peticion_id,
                'usuario_id' => get_current_user_id(),
                'publico' => 1,
                'created_at' => current_time('mysql'),
            ]);

            wp_send_json_success([
                'mensaje' => __('Petición creada correctamente', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'redirect' => $this->get_portal_url('peticion', ['peticion_slug' => $slug]),
            ]);
        } else {
            wp_send_json_error(__('Error al crear la petición', FLAVOR_PLATFORM_TEXT_DOMAIN));
        }
    }

    /**
     * AJAX: Participar en debate
     */
    public function ajax_participar_debate() {
        check_ajax_referer('participacion_nonce', 'participacion_nonce_field');

        if (!is_user_logged_in()) {
            wp_send_json_error(__('Debes iniciar sesión', FLAVOR_PLATFORM_TEXT_DOMAIN));
        }

        $debate_id = isset($_POST['debate_id']) ? absint($_POST['debate_id']) : 0;
        $contenido = isset($_POST['contenido']) ? sanitize_textarea_field($_POST['contenido']) : '';

        if (!$debate_id || empty($contenido)) {
            wp_send_json_error(__('Contenido no válido', FLAVOR_PLATFORM_TEXT_DOMAIN));
        }

        global $wpdb;
        $tabla_comentarios = $wpdb->prefix . 'flavor_participacion_comentarios_debate';
        $tabla_debates = $wpdb->prefix . 'flavor_participacion_debates';

        $resultado = $wpdb->insert($tabla_comentarios, [
            'debate_id' => $debate_id,
            'autor_id' => get_current_user_id(),
            'contenido' => $contenido,
            'estado' => 'aprobado',
            'created_at' => current_time('mysql'),
        ]);

        if ($resultado) {
            $wpdb->query($wpdb->prepare(
                "UPDATE {$tabla_debates} SET comentarios_count = comentarios_count + 1 WHERE id = %d",
                $debate_id
            ));

            wp_send_json_success(['mensaje' => __('Comentario publicado', FLAVOR_PLATFORM_TEXT_DOMAIN)]);
        } else {
            wp_send_json_error(__('Error al publicar', FLAVOR_PLATFORM_TEXT_DOMAIN));
        }
    }

    /**
     * AJAX: Obtener encuestas
     */
    public function ajax_obtener_encuestas() {
        global $wpdb;
        $tabla_encuestas = $wpdb->prefix . 'flavor_participacion_encuestas';

        $encuestas = $wpdb->get_results(
            "SELECT id, titulo, slug, estado, total_votos
             FROM {$tabla_encuestas}
             WHERE estado = 'activa'
             ORDER BY created_at DESC
             LIMIT 20"
        );

        wp_send_json_success(['encuestas' => $encuestas]);
    }
}

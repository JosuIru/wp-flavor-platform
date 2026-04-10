<?php
/**
 * Frontend Controller para Huertos Urbanos
 *
 * @package FlavorPlatform
 * @subpackage Modules\HuertosUrbanos
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Controlador Frontend para el módulo de Huertos Urbanos
 */
class Flavor_Huertos_Urbanos_Frontend_Controller {

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
        add_action('wp_ajax_huertos_solicitar_parcela', [$this, 'ajax_solicitar_parcela']);
        add_action('wp_ajax_huertos_registrar_actividad', [$this, 'ajax_registrar_actividad']);
        add_action('wp_ajax_huertos_obtener_huertos', [$this, 'ajax_obtener_huertos']);
        add_action('wp_ajax_nopriv_huertos_obtener_huertos', [$this, 'ajax_obtener_huertos']);
        add_action('wp_ajax_huertos_registrar_cultivo', [$this, 'ajax_registrar_cultivo']);
        add_action('wp_ajax_huertos_actualizar_cultivo', [$this, 'ajax_actualizar_cultivo']);
    }

    /**
     * Registrar assets
     */
    public function registrar_assets() {
        $base_url = plugins_url('assets/', dirname(dirname(__FILE__)));
        $version = FLAVOR_PLATFORM_VERSION ?? '1.0.0';

        wp_register_style(
            'flavor-huertos-urbanos',
            $base_url . 'css/huertos.css',
            [],
            $version
        );

        wp_register_script(
            'flavor-huertos-urbanos',
            $base_url . 'js/huertos.js',
            ['jquery'],
            $version,
            true
        );

        wp_localize_script('flavor-huertos-urbanos', 'flavorHuertos', [
            'ajaxUrl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('huertos_nonce'),
            'i18n' => [
                'solicitud_enviada' => __('Solicitud enviada correctamente', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'actividad_registrada' => __('Actividad registrada', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'error' => __('Ha ocurrido un error', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'cargando' => __('Cargando...', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'confirmar' => __('¿Estás seguro?', FLAVOR_PLATFORM_TEXT_DOMAIN),
            ],
        ]);
    }

    /**
     * Encolar assets
     */
    public function encolar_assets() {
        wp_enqueue_style('flavor-huertos-urbanos');
        wp_enqueue_script('flavor-huertos-urbanos');
    }

    /**
     * Registrar shortcodes
     */
    public function registrar_shortcodes() {
        $shortcodes = [
            'huertos_listado' => 'shortcode_listado',
            'huertos_mapa' => 'shortcode_mapa',
            'huertos_detalle' => 'shortcode_detalle',
            'huertos_solicitar' => 'shortcode_solicitar',
            'huertos_mi_parcela' => 'shortcode_mi_parcela',
            'huertos_diario' => 'shortcode_diario',
            'huertos_cultivos' => 'shortcode_cultivos',
        ];

        foreach ($shortcodes as $tag => $method) {
            if (!shortcode_exists($tag)) {
                add_shortcode($tag, [$this, $method]);
            }
        }
    }

    /**
     * Shortcode: Listado de huertos
     */
    public function shortcode_listado($atts) {
        $this->encolar_assets();

        $atts = shortcode_atts([
            'estado' => 'activo',
            'limite' => 12,
            'mostrar_mapa' => 'false',
        ], $atts);

        global $wpdb;
        $tabla_huertos = $wpdb->prefix . 'flavor_huertos_urbanos';

        if (!Flavor_Platform_Helpers::tabla_existe($tabla_huertos)) {
            return '<p class="flavor-error">' . __('El módulo de huertos no está configurado.', FLAVOR_PLATFORM_TEXT_DOMAIN) . '</p>';
        }

        $huertos = $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM {$tabla_huertos}
             WHERE estado = %s
             ORDER BY nombre ASC
             LIMIT %d",
            $atts['estado'],
            intval($atts['limite'])
        ));

        ob_start();
        ?>
        <div class="flavor-huertos-listado">
            <?php if (empty($huertos)): ?>
                <div class="flavor-empty-state">
                    <span class="dashicons dashicons-carrot"></span>
                    <p><?php esc_html_e('No hay huertos disponibles en este momento.', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></p>
                </div>
            <?php else: ?>
                <div class="flavor-grid flavor-grid-3">
                    <?php foreach ($huertos as $huerto): ?>
                        <div class="flavor-card flavor-huerto-card">
                            <?php if (!empty($huerto->imagen_destacada)): ?>
                                <div class="flavor-card-image">
                                    <img src="<?php echo esc_url($huerto->imagen_destacada); ?>" alt="<?php echo esc_attr($huerto->nombre); ?>">
                                </div>
                            <?php endif; ?>
                            <div class="flavor-card-body">
                                <h3><?php echo esc_html($huerto->nombre); ?></h3>
                                <p class="flavor-text-muted"><?php echo esc_html($huerto->direccion); ?></p>
                                <div class="huerto-stats">
                                    <span class="stat">
                                        <span class="dashicons dashicons-grid-view"></span>
                                        <?php echo intval($huerto->total_parcelas); ?> <?php esc_html_e('parcelas', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                                    </span>
                                    <span class="stat stat-success">
                                        <span class="dashicons dashicons-yes"></span>
                                        <?php echo intval($huerto->parcelas_disponibles); ?> <?php esc_html_e('disponibles', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                                    </span>
                                </div>
                                <?php if ($huerto->cuota_mensual > 0): ?>
                                    <p class="huerto-cuota">
                                        <strong><?php echo number_format_i18n($huerto->cuota_mensual, 2); ?> €</strong>/<?php esc_html_e('mes', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                                    </p>
                                <?php else: ?>
                                    <p class="huerto-cuota gratuito"><?php esc_html_e('Gratuito', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></p>
                                <?php endif; ?>
                            </div>
                            <div class="flavor-card-footer">
                                <a href="<?php echo esc_url(home_url('/huertos/' . $huerto->slug)); ?>" class="flavor-btn flavor-btn-primary flavor-btn-block">
                                    <?php esc_html_e('Ver Huerto', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                                </a>
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
     * Shortcode: Mapa de huertos
     */
    public function shortcode_mapa($atts) {
        $this->encolar_assets();
        wp_enqueue_script('leaflet');
        wp_enqueue_style('leaflet');

        $atts = shortcode_atts([
            'altura' => '500px',
            'zoom' => 13,
        ], $atts);

        global $wpdb;
        $tabla_huertos = $wpdb->prefix . 'flavor_huertos_urbanos';

        $huertos = [];
        if (Flavor_Platform_Helpers::tabla_existe($tabla_huertos)) {
            $huertos = $wpdb->get_results(
                "SELECT id, nombre, slug, direccion, latitud, longitud, parcelas_disponibles
                 FROM {$tabla_huertos}
                 WHERE estado = 'activo' AND latitud IS NOT NULL"
            );
        }

        $huertos_json = wp_json_encode(array_map(function($huerto) {
            return [
                'id' => $huerto->id,
                'nombre' => $huerto->nombre,
                'slug' => $huerto->slug,
                'direccion' => $huerto->direccion,
                'lat' => floatval($huerto->latitud),
                'lng' => floatval($huerto->longitud),
                'disponibles' => intval($huerto->parcelas_disponibles),
            ];
        }, $huertos));

        ob_start();
        ?>
        <div class="flavor-huertos-mapa-container">
            <div id="mapa-huertos" style="height: <?php echo esc_attr($atts['altura']); ?>;" data-huertos='<?php echo esc_attr($huertos_json); ?>' data-zoom="<?php echo intval($atts['zoom']); ?>"></div>
        </div>
        <?php
        return ob_get_clean();
    }

    /**
     * Shortcode: Detalle de huerto
     */
    public function shortcode_detalle($atts) {
        $this->encolar_assets();

        $atts = shortcode_atts([
            'id' => 0,
            'slug' => '',
        ], $atts);

        global $wpdb;
        $tabla_huertos = $wpdb->prefix . 'flavor_huertos_urbanos';
        $tabla_parcelas = $wpdb->prefix . 'flavor_huertos_parcelas';

        $huerto = null;
        if ($atts['id']) {
            $huerto = $wpdb->get_row($wpdb->prepare(
                "SELECT * FROM {$tabla_huertos} WHERE id = %d",
                $atts['id']
            ));
        } elseif ($atts['slug']) {
            $huerto = $wpdb->get_row($wpdb->prepare(
                "SELECT * FROM {$tabla_huertos} WHERE slug = %s",
                $atts['slug']
            ));
        }

        if (!$huerto) {
            return '<p class="flavor-error">' . __('Huerto no encontrado.', FLAVOR_PLATFORM_TEXT_DOMAIN) . '</p>';
        }

        $parcelas = [];
        if (Flavor_Platform_Helpers::tabla_existe($tabla_parcelas)) {
            $parcelas = $wpdb->get_results($wpdb->prepare(
                "SELECT * FROM {$tabla_parcelas} WHERE huerto_id = %d ORDER BY numero ASC",
                $huerto->id
            ));
        }

        ob_start();
        ?>
        <div class="flavor-huerto-detalle">
            <div class="huerto-header">
                <?php if (!empty($huerto->imagen_destacada)): ?>
                    <div class="huerto-imagen">
                        <img src="<?php echo esc_url($huerto->imagen_destacada); ?>" alt="<?php echo esc_attr($huerto->nombre); ?>">
                    </div>
                <?php endif; ?>
                <div class="huerto-info">
                    <h1><?php echo esc_html($huerto->nombre); ?></h1>
                    <p class="huerto-direccion">
                        <span class="dashicons dashicons-location"></span>
                        <?php echo esc_html($huerto->direccion); ?>
                    </p>
                    <?php if ($huerto->superficie_total): ?>
                        <p><strong><?php esc_html_e('Superficie:', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></strong> <?php echo number_format_i18n($huerto->superficie_total, 0); ?> m²</p>
                    <?php endif; ?>
                    <div class="huerto-caracteristicas">
                        <?php if ($huerto->acceso_agua): ?>
                            <span class="caracteristica"><span class="dashicons dashicons-yes-alt"></span> <?php esc_html_e('Acceso a agua', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></span>
                        <?php endif; ?>
                        <?php if ($huerto->herramientas_comunes): ?>
                            <span class="caracteristica"><span class="dashicons dashicons-yes-alt"></span> <?php esc_html_e('Herramientas comunes', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></span>
                        <?php endif; ?>
                        <?php if ($huerto->compostaje_comunitario): ?>
                            <span class="caracteristica"><span class="dashicons dashicons-yes-alt"></span> <?php esc_html_e('Compostaje', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></span>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <?php if ($huerto->descripcion): ?>
                <div class="huerto-descripcion">
                    <?php echo wp_kses_post($huerto->descripcion); ?>
                </div>
            <?php endif; ?>

            <?php if ($huerto->parcelas_disponibles > 0 && is_user_logged_in()): ?>
                <div class="huerto-cta">
                    <a href="<?php echo esc_url(home_url('/huertos/' . $huerto->slug . '/solicitar')); ?>" class="flavor-btn flavor-btn-primary flavor-btn-lg">
                        <span class="dashicons dashicons-plus-alt2"></span>
                        <?php esc_html_e('Solicitar Parcela', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                    </a>
                </div>
            <?php endif; ?>

            <?php if (!empty($parcelas)): ?>
                <div class="huerto-parcelas">
                    <h2><?php esc_html_e('Parcelas', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></h2>
                    <div class="parcelas-grid">
                        <?php foreach ($parcelas as $parcela): ?>
                            <div class="parcela-item parcela-<?php echo esc_attr($parcela->estado); ?>">
                                <span class="parcela-numero"><?php echo esc_html($parcela->numero); ?></span>
                                <span class="parcela-estado"><?php echo esc_html(ucfirst(str_replace('_', ' ', $parcela->estado))); ?></span>
                                <?php if ($parcela->superficie): ?>
                                    <span class="parcela-superficie"><?php echo number_format_i18n($parcela->superficie, 0); ?> m²</span>
                                <?php endif; ?>
                            </div>
                        <?php endforeach; ?>
                    </div>
                    <div class="parcelas-leyenda">
                        <span class="leyenda-item disponible"><span class="dot"></span> <?php esc_html_e('Disponible', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></span>
                        <span class="leyenda-item ocupada"><span class="dot"></span> <?php esc_html_e('Ocupada', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></span>
                        <span class="leyenda-item reservada"><span class="dot"></span> <?php esc_html_e('Reservada', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></span>
                    </div>
                </div>
            <?php endif; ?>

            <?php if ($huerto->normas): ?>
                <div class="huerto-normas">
                    <h3><?php esc_html_e('Normas del Huerto', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></h3>
                    <?php echo wp_kses_post($huerto->normas); ?>
                </div>
            <?php endif; ?>
        </div>
        <?php
        return ob_get_clean();
    }

    /**
     * Shortcode: Formulario de solicitud
     */
    public function shortcode_solicitar($atts) {
        if (!is_user_logged_in()) {
            return '<p class="flavor-login-required">' . __('Debes iniciar sesión para solicitar una parcela.', FLAVOR_PLATFORM_TEXT_DOMAIN) . '</p>';
        }

        $this->encolar_assets();

        $atts = shortcode_atts([
            'huerto_id' => 0,
        ], $atts);

        $huerto_id = $atts['huerto_id'] ?: (isset($_GET['huerto_id']) ? absint($_GET['huerto_id']) : 0);

        global $wpdb;
        $tabla_huertos = $wpdb->prefix . 'flavor_huertos_urbanos';

        $huertos = $wpdb->get_results(
            "SELECT id, nombre FROM {$tabla_huertos} WHERE estado = 'activo' AND parcelas_disponibles > 0 ORDER BY nombre"
        );

        ob_start();
        ?>
        <div class="flavor-huertos-solicitar">
            <h2><?php esc_html_e('Solicitar Parcela', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></h2>
            <form id="form-solicitar-parcela" class="flavor-form" method="post">
                <?php wp_nonce_field('huertos_nonce', 'huertos_nonce_field'); ?>

                <div class="flavor-form-group">
                    <label for="huerto_id"><?php esc_html_e('Huerto', FLAVOR_PLATFORM_TEXT_DOMAIN); ?> *</label>
                    <select name="huerto_id" id="huerto_id" required>
                        <option value=""><?php esc_html_e('Selecciona un huerto...', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></option>
                        <?php foreach ($huertos as $huerto): ?>
                            <option value="<?php echo esc_attr($huerto->id); ?>" <?php selected($huerto_id, $huerto->id); ?>>
                                <?php echo esc_html($huerto->nombre); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="flavor-form-group">
                    <label for="tipo_parcela"><?php esc_html_e('Tipo de parcela preferida', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></label>
                    <select name="tipo_parcela_preferido" id="tipo_parcela">
                        <option value=""><?php esc_html_e('Sin preferencia', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></option>
                        <option value="individual"><?php esc_html_e('Individual', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></option>
                        <option value="compartida"><?php esc_html_e('Compartida', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></option>
                        <option value="comunitaria"><?php esc_html_e('Comunitaria', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></option>
                    </select>
                </div>

                <div class="flavor-form-group">
                    <label for="experiencia"><?php esc_html_e('Experiencia previa en horticultura', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></label>
                    <textarea name="experiencia_previa" id="experiencia" rows="3" placeholder="<?php esc_attr_e('Describe tu experiencia cultivando...', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>"></textarea>
                </div>

                <div class="flavor-form-group">
                    <label for="motivacion"><?php esc_html_e('¿Por qué quieres una parcela?', FLAVOR_PLATFORM_TEXT_DOMAIN); ?> *</label>
                    <textarea name="motivacion" id="motivacion" rows="4" required></textarea>
                </div>

                <div class="flavor-form-group">
                    <label for="disponibilidad"><?php esc_html_e('Disponibilidad horaria', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></label>
                    <textarea name="disponibilidad_horaria" id="disponibilidad" rows="2" placeholder="<?php esc_attr_e('Ej: Mañanas de lunes a viernes', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>"></textarea>
                </div>

                <div class="flavor-form-group flavor-checkbox-group">
                    <label>
                        <input type="checkbox" name="acepta_normas" value="1" required>
                        <?php esc_html_e('Acepto las normas del huerto', FLAVOR_PLATFORM_TEXT_DOMAIN); ?> *
                    </label>
                </div>

                <div class="flavor-form-actions">
                    <button type="submit" class="flavor-btn flavor-btn-primary">
                        <span class="dashicons dashicons-yes"></span>
                        <?php esc_html_e('Enviar Solicitud', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                    </button>
                </div>
            </form>
        </div>
        <?php
        return ob_get_clean();
    }

    /**
     * Shortcode: Mi parcela
     */
    public function shortcode_mi_parcela($atts) {
        if (!is_user_logged_in()) {
            return '<p class="flavor-login-required">' . __('Debes iniciar sesión.', FLAVOR_PLATFORM_TEXT_DOMAIN) . '</p>';
        }

        $this->encolar_assets();
        $usuario_id = get_current_user_id();

        global $wpdb;
        $tabla_asignaciones = $wpdb->prefix . 'flavor_huertos_asignaciones';
        $tabla_parcelas = $wpdb->prefix . 'flavor_huertos_parcelas';
        $tabla_huertos = $wpdb->prefix . 'flavor_huertos_urbanos';

        $asignacion = null;
        if (Flavor_Platform_Helpers::tabla_existe($tabla_asignaciones)) {
            $asignacion = $wpdb->get_row($wpdb->prepare(
                "SELECT a.*, p.numero, p.superficie, p.huerto_id, h.nombre as huerto_nombre, h.slug as huerto_slug
                 FROM {$tabla_asignaciones} a
                 JOIN {$tabla_parcelas} p ON a.parcela_id = p.id
                 JOIN {$tabla_huertos} h ON p.huerto_id = h.id
                 WHERE a.usuario_id = %d AND a.estado = 'activa'
                 LIMIT 1",
                $usuario_id
            ));
        }

        ob_start();
        ?>
        <div class="flavor-mi-parcela">
            <?php if (!$asignacion): ?>
                <div class="flavor-empty-state">
                    <span class="dashicons dashicons-carrot"></span>
                    <p><?php esc_html_e('No tienes ninguna parcela asignada.', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></p>
                    <a href="<?php echo esc_url(home_url('/huertos/')); ?>" class="flavor-btn flavor-btn-primary">
                        <?php esc_html_e('Ver Huertos Disponibles', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                    </a>
                </div>
            <?php else: ?>
                <div class="parcela-info-card">
                    <h3>
                        <?php esc_html_e('Parcela', FLAVOR_PLATFORM_TEXT_DOMAIN); ?> <?php echo esc_html($asignacion->numero); ?>
                        <span class="badge-huerto"><?php echo esc_html($asignacion->huerto_nombre); ?></span>
                    </h3>
                    <div class="parcela-datos">
                        <p><strong><?php esc_html_e('Superficie:', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></strong> <?php echo number_format_i18n($asignacion->superficie ?? 0, 0); ?> m²</p>
                        <p><strong><?php esc_html_e('Desde:', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></strong> <?php echo date_i18n('d/m/Y', strtotime($asignacion->fecha_asignacion)); ?></p>
                    </div>
                    <div class="parcela-acciones">
                        <a href="<?php echo esc_url(home_url('/mi-portal/huertos/diario/')); ?>" class="flavor-btn flavor-btn-primary">
                            <span class="dashicons dashicons-edit"></span>
                            <?php esc_html_e('Diario de Actividades', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                        </a>
                        <a href="<?php echo esc_url(home_url('/mi-portal/huertos/cultivos/')); ?>" class="flavor-btn flavor-btn-secondary">
                            <span class="dashicons dashicons-carrot"></span>
                            <?php esc_html_e('Mis Cultivos', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                        </a>
                    </div>
                </div>
            <?php endif; ?>
        </div>
        <?php
        return ob_get_clean();
    }

    /**
     * Shortcode: Diario de actividades
     */
    public function shortcode_diario($atts) {
        if (!is_user_logged_in()) {
            return '<p class="flavor-login-required">' . __('Debes iniciar sesión.', FLAVOR_PLATFORM_TEXT_DOMAIN) . '</p>';
        }

        $this->encolar_assets();
        $usuario_id = get_current_user_id();

        global $wpdb;
        $tabla_actividades = $wpdb->prefix . 'flavor_huertos_actividades';

        $actividades = [];
        if (Flavor_Platform_Helpers::tabla_existe($tabla_actividades)) {
            $actividades = $wpdb->get_results($wpdb->prepare(
                "SELECT * FROM {$tabla_actividades}
                 WHERE usuario_id = %d
                 ORDER BY fecha_actividad DESC
                 LIMIT 30",
                $usuario_id
            ));
        }

        $tipos_actividad = [
            'siembra' => ['icon' => 'carrot', 'label' => __('Siembra', FLAVOR_PLATFORM_TEXT_DOMAIN)],
            'riego' => ['icon' => 'water', 'label' => __('Riego', FLAVOR_PLATFORM_TEXT_DOMAIN)],
            'cosecha' => ['icon' => 'products', 'label' => __('Cosecha', FLAVOR_PLATFORM_TEXT_DOMAIN)],
            'tratamiento' => ['icon' => 'shield', 'label' => __('Tratamiento', FLAVOR_PLATFORM_TEXT_DOMAIN)],
            'mantenimiento' => ['icon' => 'admin-tools', 'label' => __('Mantenimiento', FLAVOR_PLATFORM_TEXT_DOMAIN)],
            'otro' => ['icon' => 'edit', 'label' => __('Otro', FLAVOR_PLATFORM_TEXT_DOMAIN)],
        ];

        ob_start();
        ?>
        <div class="flavor-huertos-diario">
            <div class="diario-header">
                <h2><?php esc_html_e('Diario de Actividades', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></h2>
                <button type="button" class="flavor-btn flavor-btn-primary" data-toggle="modal" data-target="#modal-nueva-actividad">
                    <span class="dashicons dashicons-plus-alt2"></span>
                    <?php esc_html_e('Nueva Actividad', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                </button>
            </div>

            <?php if (empty($actividades)): ?>
                <div class="flavor-empty-state">
                    <span class="dashicons dashicons-edit"></span>
                    <p><?php esc_html_e('No has registrado actividades todavía.', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></p>
                </div>
            <?php else: ?>
                <div class="actividades-timeline">
                    <?php foreach ($actividades as $actividad): ?>
                        <div class="actividad-item tipo-<?php echo esc_attr($actividad->tipo); ?>">
                            <div class="actividad-fecha">
                                <?php echo date_i18n('d M', strtotime($actividad->fecha_actividad)); ?>
                            </div>
                            <div class="actividad-contenido">
                                <span class="actividad-tipo">
                                    <span class="dashicons dashicons-<?php echo esc_attr($tipos_actividad[$actividad->tipo]['icon'] ?? 'edit'); ?>"></span>
                                    <?php echo esc_html($tipos_actividad[$actividad->tipo]['label'] ?? ucfirst($actividad->tipo)); ?>
                                </span>
                                <?php if ($actividad->titulo): ?>
                                    <h4><?php echo esc_html($actividad->titulo); ?></h4>
                                <?php endif; ?>
                                <?php if ($actividad->descripcion): ?>
                                    <p><?php echo esc_html($actividad->descripcion); ?></p>
                                <?php endif; ?>
                                <?php if ($actividad->cultivo): ?>
                                    <span class="actividad-cultivo"><?php echo esc_html($actividad->cultivo); ?></span>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>

            <!-- Modal Nueva Actividad -->
            <div id="modal-nueva-actividad" class="flavor-modal" style="display:none;">
                <div class="flavor-modal-content">
                    <h3><?php esc_html_e('Registrar Actividad', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></h3>
                    <form id="form-nueva-actividad" class="flavor-form">
                        <?php wp_nonce_field('huertos_nonce', 'actividad_nonce'); ?>

                        <div class="flavor-form-group">
                            <label><?php esc_html_e('Tipo', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></label>
                            <select name="tipo" required>
                                <?php foreach ($tipos_actividad as $key => $tipo): ?>
                                    <option value="<?php echo esc_attr($key); ?>"><?php echo esc_html($tipo['label']); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="flavor-form-group">
                            <label><?php esc_html_e('Fecha', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></label>
                            <input type="date" name="fecha_actividad" value="<?php echo date('Y-m-d'); ?>" required>
                        </div>

                        <div class="flavor-form-group">
                            <label><?php esc_html_e('Descripción', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></label>
                            <textarea name="descripcion" rows="3"></textarea>
                        </div>

                        <div class="flavor-form-group">
                            <label><?php esc_html_e('Cultivo relacionado', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></label>
                            <input type="text" name="cultivo" placeholder="<?php esc_attr_e('Ej: Tomates, Lechugas...', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>">
                        </div>

                        <div class="flavor-form-actions">
                            <button type="button" class="flavor-btn flavor-btn-secondary" onclick="this.closest('.flavor-modal').style.display='none'">
                                <?php esc_html_e('Cancelar', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                            </button>
                            <button type="submit" class="flavor-btn flavor-btn-primary">
                                <?php esc_html_e('Guardar', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
        <?php
        return ob_get_clean();
    }

    /**
     * Shortcode: Cultivos
     */
    public function shortcode_cultivos($atts) {
        if (!is_user_logged_in()) {
            return '<p class="flavor-login-required">' . __('Debes iniciar sesión.', FLAVOR_PLATFORM_TEXT_DOMAIN) . '</p>';
        }

        $this->encolar_assets();
        $usuario_id = get_current_user_id();

        global $wpdb;
        $tabla_cultivos = $wpdb->prefix . 'flavor_huertos_cultivos';
        $tabla_asignaciones = $wpdb->prefix . 'flavor_huertos_asignaciones';

        $cultivos = [];
        if (Flavor_Platform_Helpers::tabla_existe($tabla_cultivos) && Flavor_Platform_Helpers::tabla_existe($tabla_asignaciones)) {
            $cultivos = $wpdb->get_results($wpdb->prepare(
                "SELECT c.* FROM {$tabla_cultivos} c
                 JOIN {$tabla_asignaciones} a ON c.parcela_id = a.parcela_id
                 WHERE a.usuario_id = %d AND a.estado = 'activa'
                 ORDER BY c.fecha_siembra DESC",
                $usuario_id
            ));
        }

        $estados_cultivo = [
            'sembrado' => ['color' => 'info', 'label' => __('Sembrado', FLAVOR_PLATFORM_TEXT_DOMAIN)],
            'creciendo' => ['color' => 'primary', 'label' => __('Creciendo', FLAVOR_PLATFORM_TEXT_DOMAIN)],
            'listo' => ['color' => 'success', 'label' => __('Listo para cosechar', FLAVOR_PLATFORM_TEXT_DOMAIN)],
            'cosechado' => ['color' => 'secondary', 'label' => __('Cosechado', FLAVOR_PLATFORM_TEXT_DOMAIN)],
            'fallido' => ['color' => 'danger', 'label' => __('Fallido', FLAVOR_PLATFORM_TEXT_DOMAIN)],
        ];

        ob_start();
        ?>
        <div class="flavor-huertos-cultivos">
            <div class="cultivos-header">
                <h2><?php esc_html_e('Mis Cultivos', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></h2>
                <button type="button" class="flavor-btn flavor-btn-primary" data-toggle="modal" data-target="#modal-nuevo-cultivo">
                    <span class="dashicons dashicons-plus-alt2"></span>
                    <?php esc_html_e('Nuevo Cultivo', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                </button>
            </div>

            <?php if (empty($cultivos)): ?>
                <div class="flavor-empty-state">
                    <span class="dashicons dashicons-carrot"></span>
                    <p><?php esc_html_e('No tienes cultivos registrados.', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></p>
                </div>
            <?php else: ?>
                <div class="flavor-grid flavor-grid-3">
                    <?php foreach ($cultivos as $cultivo): ?>
                        <div class="flavor-card cultivo-card">
                            <div class="flavor-card-body">
                                <h4><?php echo esc_html($cultivo->nombre); ?></h4>
                                <?php if ($cultivo->variedad): ?>
                                    <p class="cultivo-variedad"><?php echo esc_html($cultivo->variedad); ?></p>
                                <?php endif; ?>
                                <span class="flavor-badge flavor-badge-<?php echo esc_attr($estados_cultivo[$cultivo->estado]['color'] ?? 'secondary'); ?>">
                                    <?php echo esc_html($estados_cultivo[$cultivo->estado]['label'] ?? ucfirst($cultivo->estado)); ?>
                                </span>
                                <div class="cultivo-fechas">
                                    <?php if ($cultivo->fecha_siembra): ?>
                                        <p><small><?php esc_html_e('Siembra:', FLAVOR_PLATFORM_TEXT_DOMAIN); ?> <?php echo date_i18n('d/m/Y', strtotime($cultivo->fecha_siembra)); ?></small></p>
                                    <?php endif; ?>
                                    <?php if ($cultivo->fecha_cosecha_estimada): ?>
                                        <p><small><?php esc_html_e('Cosecha est.:', FLAVOR_PLATFORM_TEXT_DOMAIN); ?> <?php echo date_i18n('d/m/Y', strtotime($cultivo->fecha_cosecha_estimada)); ?></small></p>
                                    <?php endif; ?>
                                </div>
                            </div>
                            <div class="flavor-card-footer">
                                <button class="flavor-btn flavor-btn-sm flavor-btn-outline btn-actualizar-cultivo" data-id="<?php echo esc_attr($cultivo->id); ?>">
                                    <?php esc_html_e('Actualizar', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                                </button>
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
     * AJAX: Solicitar parcela
     */
    public function ajax_solicitar_parcela() {
        check_ajax_referer('huertos_nonce', 'huertos_nonce_field');

        if (!is_user_logged_in()) {
            wp_send_json_error(__('Debes iniciar sesión', FLAVOR_PLATFORM_TEXT_DOMAIN));
        }

        $huerto_id = isset($_POST['huerto_id']) ? absint($_POST['huerto_id']) : 0;
        $motivacion = isset($_POST['motivacion']) ? sanitize_textarea_field($_POST['motivacion']) : '';
        $experiencia = isset($_POST['experiencia_previa']) ? sanitize_textarea_field($_POST['experiencia_previa']) : '';
        $disponibilidad = isset($_POST['disponibilidad_horaria']) ? sanitize_textarea_field($_POST['disponibilidad_horaria']) : '';
        $tipo_preferido = isset($_POST['tipo_parcela_preferido']) ? sanitize_text_field($_POST['tipo_parcela_preferido']) : '';
        $acepta_normas = isset($_POST['acepta_normas']) ? 1 : 0;

        if (!$huerto_id || empty($motivacion)) {
            wp_send_json_error(__('Datos incompletos', FLAVOR_PLATFORM_TEXT_DOMAIN));
        }

        if (!$acepta_normas) {
            wp_send_json_error(__('Debes aceptar las normas', FLAVOR_PLATFORM_TEXT_DOMAIN));
        }

        global $wpdb;
        $tabla_solicitudes = $wpdb->prefix . 'flavor_huertos_solicitudes';
        $usuario_id = get_current_user_id();

        // Verificar solicitud existente
        $existente = $wpdb->get_var($wpdb->prepare(
            "SELECT id FROM {$tabla_solicitudes}
             WHERE huerto_id = %d AND usuario_id = %d AND estado IN ('pendiente', 'lista_espera')",
            $huerto_id,
            $usuario_id
        ));

        if ($existente) {
            wp_send_json_error(__('Ya tienes una solicitud pendiente para este huerto', FLAVOR_PLATFORM_TEXT_DOMAIN));
        }

        $resultado = $wpdb->insert($tabla_solicitudes, [
            'huerto_id' => $huerto_id,
            'usuario_id' => $usuario_id,
            'tipo_parcela_preferido' => $tipo_preferido,
            'experiencia_previa' => $experiencia,
            'motivacion' => $motivacion,
            'disponibilidad_horaria' => $disponibilidad,
            'acepta_normas' => $acepta_normas,
            'estado' => 'pendiente',
            'created_at' => current_time('mysql'),
        ]);

        if ($resultado) {
            do_action('huerto_solicitud_created', $wpdb->insert_id, $usuario_id, $huerto_id);
            wp_send_json_success([
                'mensaje' => __('Solicitud enviada correctamente. Te notificaremos cuando sea revisada.', FLAVOR_PLATFORM_TEXT_DOMAIN),
            ]);
        } else {
            wp_send_json_error(__('Error al enviar solicitud', FLAVOR_PLATFORM_TEXT_DOMAIN));
        }
    }

    /**
     * AJAX: Registrar actividad
     */
    public function ajax_registrar_actividad() {
        check_ajax_referer('huertos_nonce', 'actividad_nonce');

        if (!is_user_logged_in()) {
            wp_send_json_error(__('Debes iniciar sesión', FLAVOR_PLATFORM_TEXT_DOMAIN));
        }

        $usuario_id = get_current_user_id();
        $tipo = isset($_POST['tipo']) ? sanitize_text_field($_POST['tipo']) : 'otro';
        $descripcion = isset($_POST['descripcion']) ? sanitize_textarea_field($_POST['descripcion']) : '';
        $fecha = isset($_POST['fecha_actividad']) ? sanitize_text_field($_POST['fecha_actividad']) : date('Y-m-d');
        $cultivo = isset($_POST['cultivo']) ? sanitize_text_field($_POST['cultivo']) : '';

        global $wpdb;
        $tabla_asignaciones = $wpdb->prefix . 'flavor_huertos_asignaciones';
        $tabla_actividades = $wpdb->prefix . 'flavor_huertos_actividades';

        // Obtener parcela activa
        $asignacion = $wpdb->get_row($wpdb->prepare(
            "SELECT parcela_id FROM {$tabla_asignaciones}
             WHERE usuario_id = %d AND estado = 'activa' LIMIT 1",
            $usuario_id
        ));

        if (!$asignacion) {
            wp_send_json_error(__('No tienes una parcela asignada', FLAVOR_PLATFORM_TEXT_DOMAIN));
        }

        $resultado = $wpdb->insert($tabla_actividades, [
            'parcela_id' => $asignacion->parcela_id,
            'usuario_id' => $usuario_id,
            'tipo' => $tipo,
            'descripcion' => $descripcion,
            'fecha_actividad' => $fecha,
            'cultivo' => $cultivo,
            'created_at' => current_time('mysql'),
        ]);

        if ($resultado) {
            wp_send_json_success(['mensaje' => __('Actividad registrada', FLAVOR_PLATFORM_TEXT_DOMAIN)]);
        } else {
            wp_send_json_error(__('Error al registrar actividad', FLAVOR_PLATFORM_TEXT_DOMAIN));
        }
    }

    /**
     * AJAX: Obtener huertos
     */
    public function ajax_obtener_huertos() {
        global $wpdb;
        $tabla_huertos = $wpdb->prefix . 'flavor_huertos_urbanos';

        $huertos = $wpdb->get_results(
            "SELECT id, nombre, slug, direccion, latitud, longitud, parcelas_disponibles, imagen_destacada
             FROM {$tabla_huertos}
             WHERE estado = 'activo'
             ORDER BY nombre"
        );

        wp_send_json_success(['huertos' => $huertos]);
    }

    /**
     * AJAX: Registrar cultivo
     */
    public function ajax_registrar_cultivo() {
        check_ajax_referer('huertos_nonce', 'nonce');

        if (!is_user_logged_in()) {
            wp_send_json_error(__('Debes iniciar sesión', FLAVOR_PLATFORM_TEXT_DOMAIN));
        }

        $usuario_id = get_current_user_id();
        $nombre = isset($_POST['nombre']) ? sanitize_text_field($_POST['nombre']) : '';
        $variedad = isset($_POST['variedad']) ? sanitize_text_field($_POST['variedad']) : '';
        $fecha_siembra = isset($_POST['fecha_siembra']) ? sanitize_text_field($_POST['fecha_siembra']) : date('Y-m-d');
        $fecha_cosecha = isset($_POST['fecha_cosecha_estimada']) ? sanitize_text_field($_POST['fecha_cosecha_estimada']) : null;
        $cantidad = isset($_POST['cantidad_sembrada']) ? sanitize_text_field($_POST['cantidad_sembrada']) : '';

        if (empty($nombre)) {
            wp_send_json_error(__('El nombre es obligatorio', FLAVOR_PLATFORM_TEXT_DOMAIN));
        }

        global $wpdb;
        $tabla_asignaciones = $wpdb->prefix . 'flavor_huertos_asignaciones';
        $tabla_cultivos = $wpdb->prefix . 'flavor_huertos_cultivos';

        $asignacion = $wpdb->get_row($wpdb->prepare(
            "SELECT parcela_id FROM {$tabla_asignaciones}
             WHERE usuario_id = %d AND estado = 'activa' LIMIT 1",
            $usuario_id
        ));

        if (!$asignacion) {
            wp_send_json_error(__('No tienes una parcela asignada', FLAVOR_PLATFORM_TEXT_DOMAIN));
        }

        $resultado = $wpdb->insert($tabla_cultivos, [
            'parcela_id' => $asignacion->parcela_id,
            'nombre' => $nombre,
            'variedad' => $variedad,
            'fecha_siembra' => $fecha_siembra,
            'fecha_cosecha_estimada' => $fecha_cosecha,
            'cantidad_sembrada' => $cantidad,
            'estado' => 'sembrado',
            'created_at' => current_time('mysql'),
        ]);

        if ($resultado) {
            wp_send_json_success(['mensaje' => __('Cultivo registrado', FLAVOR_PLATFORM_TEXT_DOMAIN), 'id' => $wpdb->insert_id]);
        } else {
            wp_send_json_error(__('Error al registrar cultivo', FLAVOR_PLATFORM_TEXT_DOMAIN));
        }
    }

    /**
     * AJAX: Actualizar cultivo
     */
    public function ajax_actualizar_cultivo() {
        check_ajax_referer('huertos_nonce', 'nonce');

        if (!is_user_logged_in()) {
            wp_send_json_error(__('Debes iniciar sesión', FLAVOR_PLATFORM_TEXT_DOMAIN));
        }

        $cultivo_id = isset($_POST['cultivo_id']) ? absint($_POST['cultivo_id']) : 0;
        $estado = isset($_POST['estado']) ? sanitize_text_field($_POST['estado']) : '';

        if (!$cultivo_id || empty($estado)) {
            wp_send_json_error(__('Datos incompletos', FLAVOR_PLATFORM_TEXT_DOMAIN));
        }

        global $wpdb;
        $tabla_cultivos = $wpdb->prefix . 'flavor_huertos_cultivos';

        $datos_actualizar = ['estado' => $estado, 'updated_at' => current_time('mysql')];

        if ($estado === 'cosechado') {
            $datos_actualizar['fecha_cosecha_real'] = isset($_POST['fecha_cosecha']) ? sanitize_text_field($_POST['fecha_cosecha']) : date('Y-m-d');
            $datos_actualizar['cantidad_cosechada'] = isset($_POST['cantidad_cosechada']) ? sanitize_text_field($_POST['cantidad_cosechada']) : '';
        }

        $resultado = $wpdb->update($tabla_cultivos, $datos_actualizar, ['id' => $cultivo_id]);

        if ($resultado !== false) {
            wp_send_json_success(['mensaje' => __('Cultivo actualizado', FLAVOR_PLATFORM_TEXT_DOMAIN)]);
        } else {
            wp_send_json_error(__('Error al actualizar', FLAVOR_PLATFORM_TEXT_DOMAIN));
        }
    }
}

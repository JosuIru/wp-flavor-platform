<?php
/**
 * Frontend Controller para Espacios Comunes
 *
 * @package FlavorChatIA
 * @subpackage Modules\EspaciosComunes
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Controlador Frontend para el módulo de Espacios Comunes
 */
class Flavor_Espacios_Comunes_Frontend_Controller {

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
        add_action('wp_ajax_espacios_reservar', [$this, 'ajax_reservar']);
        add_action('wp_ajax_espacios_cancelar_reserva', [$this, 'ajax_cancelar_reserva']);
        add_action('wp_ajax_espacios_obtener_disponibilidad', [$this, 'ajax_obtener_disponibilidad']);
        add_action('wp_ajax_nopriv_espacios_obtener_disponibilidad', [$this, 'ajax_obtener_disponibilidad']);
        add_action('wp_ajax_espacios_obtener_espacios', [$this, 'ajax_obtener_espacios']);
        add_action('wp_ajax_nopriv_espacios_obtener_espacios', [$this, 'ajax_obtener_espacios']);
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
            'espacios_listado',
            'espacios_detalle',
            'espacios_reservar',
            'espacios_mis_reservas',
            'espacios_calendario',
            'espacios_proxima_reserva',
            'espacios_equipamiento',
        ];

        foreach ($shortcodes_modulo as $shortcode) {
            if (has_shortcode($post->post_content, $shortcode)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Registrar assets
     */
    public function registrar_assets() {
        if (!$this->should_load_assets()) {
            return;
        }

        $base_url = plugins_url('assets/', dirname(dirname(__FILE__)));
        $version = FLAVOR_CHAT_IA_VERSION ?? '1.0.0';

        wp_enqueue_style(
            'flavor-espacios-comunes',
            $base_url . 'css/espacios-frontend.css',
            [],
            $version
        );

        wp_enqueue_script(
            'flavor-espacios-comunes',
            $base_url . 'js/espacios-frontend.js',
            ['jquery'],
            $version,
            true
        );

        wp_localize_script('flavor-espacios-comunes', 'flavorEspacios', [
            'ajaxUrl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('espacios_nonce'),
            'i18n' => [
                'reserva_confirmada' => __('Reserva confirmada', 'flavor-platform'),
                'reserva_cancelada' => __('Reserva cancelada', 'flavor-platform'),
                'error' => __('Ha ocurrido un error', 'flavor-platform'),
                'cargando' => __('Cargando...', 'flavor-platform'),
                'confirmar_cancelar' => __('¿Confirmas que quieres cancelar esta reserva?', 'flavor-platform'),
                'selecciona_fecha' => __('Selecciona una fecha', 'flavor-platform'),
                'selecciona_horario' => __('Selecciona un horario', 'flavor-platform'),
            ],
        ]);
    }

    /**
     * Encolar assets
     */
    public function encolar_assets() {
        wp_enqueue_style('flavor-espacios-comunes');
        wp_enqueue_script('flavor-espacios-comunes');
    }

    /**
     * Registrar shortcodes
     */
    public function registrar_shortcodes() {
        $shortcodes = [
            'espacios_listado' => 'shortcode_listado',
            'espacios_detalle' => 'shortcode_detalle',
            'espacios_reservar' => 'shortcode_reservar',
            'espacios_mis_reservas' => 'shortcode_mis_reservas',
            'espacios_calendario' => 'shortcode_calendario',
            'espacios_proxima_reserva' => 'shortcode_proxima_reserva',
        ];

        foreach ($shortcodes as $tag => $method) {
            if (!shortcode_exists($tag)) {
                add_shortcode($tag, [$this, $method]);
            }
        }
    }

    /**
     * Shortcode: Próxima reserva del usuario
     */
    public function shortcode_proxima_reserva($atts) {
        if (!is_user_logged_in()) {
            return '';
        }

        global $wpdb;
        $tabla_reservas = $wpdb->prefix . 'flavor_reservas_espacios';
        $tabla_espacios = $wpdb->prefix . 'flavor_espacios_comunes';
        $user_id = get_current_user_id();

        if (!Flavor_Chat_Helpers::tabla_existe($tabla_reservas)) {
            return '';
        }

        $proxima = $wpdb->get_row($wpdb->prepare(
            "SELECT r.*, e.nombre as espacio_nombre, e.tipo as espacio_tipo
             FROM {$tabla_reservas} r
             LEFT JOIN {$tabla_espacios} e ON r.espacio_id = e.id
             WHERE r.usuario_id = %d
             AND r.fecha_inicio > NOW()
             AND r.estado IN ('confirmada', 'pendiente')
             ORDER BY r.fecha_inicio ASC
             LIMIT 1",
            $user_id
        ));

        if (!$proxima) {
            return '<div class="flavor-widget flavor-widget--empty">
                <span class="dashicons dashicons-calendar-alt"></span>
                <p>' . esc_html__('No tienes reservas próximas', 'flavor-platform') . '</p>
            </div>';
        }

        $fecha = date_i18n('d M', strtotime($proxima->fecha_inicio));
        $hora = date_i18n('H:i', strtotime($proxima->fecha_inicio));

        ob_start();
        ?>
        <div class="flavor-widget flavor-proxima-reserva">
            <div class="flavor-proxima-reserva__icon">
                <span class="dashicons dashicons-calendar-alt"></span>
            </div>
            <div class="flavor-proxima-reserva__info">
                <span class="flavor-proxima-reserva__espacio"><?php echo esc_html($proxima->espacio_nombre); ?></span>
                <span class="flavor-proxima-reserva__fecha"><?php echo esc_html($fecha); ?> - <?php echo esc_html($hora); ?></span>
            </div>
        </div>
        <?php
        return ob_get_clean();
    }

    /**
     * Shortcode: Listado de espacios
     */
    public function shortcode_listado($atts) {
        $this->encolar_assets();

        $atts = shortcode_atts([
            'tipo' => '',
            'limite' => 12,
        ], $atts);

        global $wpdb;
        $tabla_espacios = $wpdb->prefix . 'flavor_espacios_comunes';

        if (!Flavor_Chat_Helpers::tabla_existe($tabla_espacios)) {
            return '<p class="flavor-error">' . __('El módulo no está configurado.', 'flavor-platform') . '</p>';
        }

        $where = "estado = 'disponible'";
        $params = [];

        if (!empty($atts['tipo'])) {
            $where .= " AND tipo = %s";
            $params[] = $atts['tipo'];
        }

        $sql = "SELECT * FROM {$tabla_espacios} WHERE {$where} ORDER BY nombre ASC LIMIT %d";
        $params[] = intval($atts['limite']);

        $espacios = $wpdb->get_results($wpdb->prepare($sql, ...$params));

        $tipos_iconos = [
            'sala_reuniones' => 'groups',
            'aula' => 'welcome-learn-more',
            'auditorio' => 'megaphone',
            'cocina' => 'carrot',
            'taller' => 'hammer',
            'terraza' => 'palmtree',
            'patio' => 'admin-home',
            'deportivo' => 'superhero',
            'otro' => 'building',
        ];

        ob_start();
        ?>
        <div class="flavor-espacios-listado">
            <?php if (empty($espacios)): ?>
                <div class="flavor-empty-state">
                    <span class="dashicons dashicons-building"></span>
                    <p><?php esc_html_e('No hay espacios disponibles.', 'flavor-platform'); ?></p>
                </div>
            <?php else: ?>
                <div class="flavor-grid flavor-grid-3">
                    <?php foreach ($espacios as $espacio): ?>
                        <div class="flavor-card espacio-card">
                            <?php if (!empty($espacio->imagen_destacada)): ?>
                                <div class="flavor-card-image">
                                    <img src="<?php echo esc_url($espacio->imagen_destacada); ?>" alt="<?php echo esc_attr($espacio->nombre); ?>">
                                </div>
                            <?php else: ?>
                                <div class="flavor-card-image espacio-placeholder">
                                    <span class="dashicons dashicons-<?php echo esc_attr($tipos_iconos[$espacio->tipo] ?? 'building'); ?>"></span>
                                </div>
                            <?php endif; ?>

                            <div class="flavor-card-body">
                                <span class="espacio-tipo"><?php echo esc_html(ucfirst(str_replace('_', ' ', $espacio->tipo))); ?></span>
                                <h3><?php echo esc_html($espacio->nombre); ?></h3>

                                <div class="espacio-info">
                                    <?php if ($espacio->capacidad): ?>
                                        <span class="info-item">
                                            <span class="dashicons dashicons-groups"></span>
                                            <?php echo intval($espacio->capacidad); ?> <?php esc_html_e('personas', 'flavor-platform'); ?>
                                        </span>
                                    <?php endif; ?>
                                    <?php if ($espacio->superficie): ?>
                                        <span class="info-item">
                                            <span class="dashicons dashicons-editor-expand"></span>
                                            <?php echo number_format_i18n($espacio->superficie, 0); ?> m²
                                        </span>
                                    <?php endif; ?>
                                </div>

                                <?php if ($espacio->tarifa_hora > 0): ?>
                                    <p class="espacio-tarifa">
                                        <strong><?php echo number_format_i18n($espacio->tarifa_hora, 2); ?> €</strong>/<?php esc_html_e('hora', 'flavor-platform'); ?>
                                    </p>
                                <?php else: ?>
                                    <p class="espacio-tarifa gratuito"><?php esc_html_e('Gratuito', 'flavor-platform'); ?></p>
                                <?php endif; ?>
                            </div>

                            <div class="flavor-card-footer">
                                <a href="<?php echo esc_url(home_url('/espacios/' . $espacio->slug)); ?>" class="flavor-btn flavor-btn-outline">
                                    <?php esc_html_e('Ver Detalles', 'flavor-platform'); ?>
                                </a>
                                <?php if (is_user_logged_in()): ?>
                                    <a href="<?php echo esc_url(home_url('/espacios/' . $espacio->slug . '/reservar')); ?>" class="flavor-btn flavor-btn-primary">
                                        <?php esc_html_e('Reservar', 'flavor-platform'); ?>
                                    </a>
                                <?php endif; ?>
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
     * Shortcode: Detalle de espacio
     */
    public function shortcode_detalle($atts) {
        $this->encolar_assets();

        $atts = shortcode_atts([
            'id' => 0,
            'slug' => '',
        ], $atts);

        global $wpdb;
        $tabla_espacios = $wpdb->prefix . 'flavor_espacios_comunes';

        $espacio = null;
        if ($atts['id']) {
            $espacio = $wpdb->get_row($wpdb->prepare(
                "SELECT * FROM {$tabla_espacios} WHERE id = %d",
                $atts['id']
            ));
        } elseif ($atts['slug']) {
            $espacio = $wpdb->get_row($wpdb->prepare(
                "SELECT * FROM {$tabla_espacios} WHERE slug = %s",
                $atts['slug']
            ));
        }

        if (!$espacio) {
            return '<p class="flavor-error">' . __('Espacio no encontrado.', 'flavor-platform') . '</p>';
        }

        $equipamiento = !empty($espacio->equipamiento) ? json_decode($espacio->equipamiento, true) : [];
        $galeria = !empty($espacio->galeria) ? json_decode($espacio->galeria, true) : [];

        ob_start();
        ?>
        <div class="flavor-espacio-detalle">
            <div class="espacio-header">
                <?php if (!empty($espacio->imagen_destacada)): ?>
                    <div class="espacio-imagen-principal">
                        <img src="<?php echo esc_url($espacio->imagen_destacada); ?>" alt="<?php echo esc_attr($espacio->nombre); ?>">
                    </div>
                <?php endif; ?>

                <div class="espacio-info-header">
                    <span class="espacio-tipo-badge"><?php echo esc_html(ucfirst(str_replace('_', ' ', $espacio->tipo))); ?></span>
                    <h1><?php echo esc_html($espacio->nombre); ?></h1>

                    <?php if ($espacio->ubicacion): ?>
                        <p class="espacio-ubicacion">
                            <span class="dashicons dashicons-location"></span>
                            <?php echo esc_html($espacio->ubicacion); ?>
                        </p>
                    <?php endif; ?>
                </div>
            </div>

            <div class="espacio-stats">
                <?php if ($espacio->capacidad): ?>
                    <div class="stat-card">
                        <span class="dashicons dashicons-groups"></span>
                        <div>
                            <span class="stat-value"><?php echo intval($espacio->capacidad); ?></span>
                            <span class="stat-label"><?php esc_html_e('Capacidad', 'flavor-platform'); ?></span>
                        </div>
                    </div>
                <?php endif; ?>
                <?php if ($espacio->superficie): ?>
                    <div class="stat-card">
                        <span class="dashicons dashicons-editor-expand"></span>
                        <div>
                            <span class="stat-value"><?php echo number_format_i18n($espacio->superficie, 0); ?> m²</span>
                            <span class="stat-label"><?php esc_html_e('Superficie', 'flavor-platform'); ?></span>
                        </div>
                    </div>
                <?php endif; ?>
                <div class="stat-card">
                    <span class="dashicons dashicons-money-alt"></span>
                    <div>
                        <?php if ($espacio->tarifa_hora > 0): ?>
                            <span class="stat-value"><?php echo number_format_i18n($espacio->tarifa_hora, 2); ?> €/h</span>
                        <?php else: ?>
                            <span class="stat-value gratuito"><?php esc_html_e('Gratuito', 'flavor-platform'); ?></span>
                        <?php endif; ?>
                        <span class="stat-label"><?php esc_html_e('Tarifa', 'flavor-platform'); ?></span>
                    </div>
                </div>
            </div>

            <?php if ($espacio->descripcion): ?>
                <div class="espacio-descripcion">
                    <h2><?php esc_html_e('Descripción', 'flavor-platform'); ?></h2>
                    <?php echo wp_kses_post($espacio->descripcion); ?>
                </div>
            <?php endif; ?>

            <?php if (!empty($equipamiento)): ?>
                <div class="espacio-equipamiento">
                    <h2><?php esc_html_e('Equipamiento', 'flavor-platform'); ?></h2>
                    <ul class="equipamiento-lista">
                        <?php foreach ($equipamiento as $item): ?>
                            <li><span class="dashicons dashicons-yes-alt"></span> <?php echo esc_html($item); ?></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            <?php endif; ?>

            <?php if ($espacio->horario_apertura && $espacio->horario_cierre): ?>
                <div class="espacio-horarios">
                    <h2><?php esc_html_e('Horarios', 'flavor-platform'); ?></h2>
                    <p>
                        <span class="dashicons dashicons-clock"></span>
                        <?php echo esc_html($espacio->horario_apertura); ?> - <?php echo esc_html($espacio->horario_cierre); ?>
                    </p>
                    <?php if ($espacio->dias_disponibles): ?>
                        <p class="dias-disponibles"><?php echo esc_html($espacio->dias_disponibles); ?></p>
                    <?php endif; ?>
                </div>
            <?php endif; ?>

            <?php if (!empty($galeria)): ?>
                <div class="espacio-galeria">
                    <h2><?php esc_html_e('Galería', 'flavor-platform'); ?></h2>
                    <div class="galeria-grid">
                        <?php foreach ($galeria as $imagen): ?>
                            <a href="<?php echo esc_url($imagen); ?>" class="galeria-item" data-lightbox="espacio">
                                <img src="<?php echo esc_url($imagen); ?>" alt="">
                            </a>
                        <?php endforeach; ?>
                    </div>
                </div>
            <?php endif; ?>

            <?php if ($espacio->normas): ?>
                <div class="espacio-normas">
                    <h3><?php esc_html_e('Normas de Uso', 'flavor-platform'); ?></h3>
                    <?php echo wp_kses_post($espacio->normas); ?>
                </div>
            <?php endif; ?>

            <?php if (is_user_logged_in() && $espacio->estado === 'disponible'): ?>
                <div class="espacio-cta">
                    <a href="<?php echo esc_url(home_url('/espacios/' . $espacio->slug . '/reservar')); ?>" class="flavor-btn flavor-btn-primary flavor-btn-lg">
                        <span class="dashicons dashicons-calendar-alt"></span>
                        <?php esc_html_e('Reservar este Espacio', 'flavor-platform'); ?>
                    </a>
                </div>
            <?php elseif (!is_user_logged_in()): ?>
                <div class="espacio-cta">
                    <p><?php esc_html_e('Inicia sesión para reservar este espacio.', 'flavor-platform'); ?></p>
                </div>
            <?php endif; ?>
        </div>
        <?php
        return ob_get_clean();
    }

    /**
     * Shortcode: Formulario de reserva
     */
    public function shortcode_reservar($atts) {
        if (!is_user_logged_in()) {
            return '<p class="flavor-login-required">' . __('Debes iniciar sesión para reservar.', 'flavor-platform') . '</p>';
        }

        $this->encolar_assets();

        $atts = shortcode_atts([
            'espacio_id' => 0,
            'espacio_slug' => '',
        ], $atts);

        global $wpdb;
        $tabla_espacios = $wpdb->prefix . 'flavor_espacios_comunes';

        $espacio = null;
        if ($atts['espacio_id']) {
            $espacio = $wpdb->get_row($wpdb->prepare(
                "SELECT * FROM {$tabla_espacios} WHERE id = %d AND estado = 'disponible'",
                $atts['espacio_id']
            ));
        } elseif ($atts['espacio_slug']) {
            $espacio = $wpdb->get_row($wpdb->prepare(
                "SELECT * FROM {$tabla_espacios} WHERE slug = %s AND estado = 'disponible'",
                $atts['espacio_slug']
            ));
        }

        if (!$espacio) {
            return '<p class="flavor-error">' . __('Espacio no disponible para reservas.', 'flavor-platform') . '</p>';
        }

        // Generar franjas horarias
        $hora_inicio = $espacio->horario_apertura ? strtotime($espacio->horario_apertura) : strtotime('08:00');
        $hora_fin = $espacio->horario_cierre ? strtotime($espacio->horario_cierre) : strtotime('21:00');
        $intervalo = ($espacio->duracion_minima ?? 60) * 60; // en segundos

        $franjas = [];
        for ($hora = $hora_inicio; $hora < $hora_fin; $hora += $intervalo) {
            $franjas[] = date('H:i', $hora);
        }

        ob_start();
        ?>
        <div class="flavor-espacios-reservar">
            <div class="reserva-header">
                <h2><?php esc_html_e('Reservar', 'flavor-platform'); ?> <?php echo esc_html($espacio->nombre); ?></h2>
                <?php if ($espacio->tarifa_hora > 0): ?>
                    <p class="reserva-tarifa">
                        <span class="dashicons dashicons-money-alt"></span>
                        <?php echo number_format_i18n($espacio->tarifa_hora, 2); ?> €/<?php esc_html_e('hora', 'flavor-platform'); ?>
                    </p>
                <?php endif; ?>
            </div>

            <form id="form-reservar-espacio" class="flavor-form">
                <?php wp_nonce_field('espacios_nonce', 'espacios_nonce_field'); ?>
                <input type="hidden" name="espacio_id" value="<?php echo esc_attr($espacio->id); ?>">

                <div class="flavor-form-row">
                    <div class="flavor-form-group flavor-form-col-6">
                        <label for="fecha"><?php esc_html_e('Fecha', 'flavor-platform'); ?> *</label>
                        <input type="date" name="fecha" id="fecha" required
                            min="<?php echo date('Y-m-d'); ?>"
                            max="<?php echo date('Y-m-d', strtotime('+' . ($espacio->dias_antelacion_max ?? 30) . ' days')); ?>">
                    </div>
                </div>

                <div class="flavor-form-row">
                    <div class="flavor-form-group flavor-form-col-6">
                        <label for="hora_inicio"><?php esc_html_e('Hora inicio', 'flavor-platform'); ?> *</label>
                        <select name="hora_inicio" id="hora_inicio" required>
                            <option value=""><?php esc_html_e('Selecciona...', 'flavor-platform'); ?></option>
                            <?php foreach ($franjas as $franja): ?>
                                <option value="<?php echo esc_attr($franja); ?>"><?php echo esc_html($franja); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="flavor-form-group flavor-form-col-6">
                        <label for="hora_fin"><?php esc_html_e('Hora fin', 'flavor-platform'); ?> *</label>
                        <select name="hora_fin" id="hora_fin" required>
                            <option value=""><?php esc_html_e('Selecciona...', 'flavor-platform'); ?></option>
                            <?php foreach ($franjas as $index => $franja): ?>
                                <?php if ($index > 0): ?>
                                    <option value="<?php echo esc_attr($franja); ?>"><?php echo esc_html($franja); ?></option>
                                <?php endif; ?>
                            <?php endforeach; ?>
                            <option value="<?php echo date('H:i', $hora_fin); ?>"><?php echo date('H:i', $hora_fin); ?></option>
                        </select>
                    </div>
                </div>

                <div class="flavor-form-group">
                    <label for="motivo"><?php esc_html_e('Motivo de la reserva', 'flavor-platform'); ?> *</label>
                    <textarea name="motivo" id="motivo" rows="3" required></textarea>
                </div>

                <div class="flavor-form-group">
                    <label for="asistentes"><?php esc_html_e('Número de asistentes', 'flavor-platform'); ?></label>
                    <input type="number" name="asistentes" id="asistentes" min="1" max="<?php echo intval($espacio->capacidad ?? 100); ?>">
                    <?php if ($espacio->capacidad): ?>
                        <p class="flavor-form-help"><?php printf(esc_html__('Capacidad máxima: %d personas', 'flavor-platform'), intval($espacio->capacidad)); ?></p>
                    <?php endif; ?>
                </div>

                <div class="flavor-form-group">
                    <label for="notas"><?php esc_html_e('Notas adicionales', 'flavor-platform'); ?></label>
                    <textarea name="notas" id="notas" rows="2"></textarea>
                </div>

                <?php if ($espacio->tarifa_hora > 0): ?>
                    <div class="reserva-resumen" id="resumen-reserva" style="display:none;">
                        <h4><?php esc_html_e('Resumen', 'flavor-platform'); ?></h4>
                        <p><?php esc_html_e('Duración:', 'flavor-platform'); ?> <span id="duracion-horas">-</span> <?php esc_html_e('horas', 'flavor-platform'); ?></p>
                        <p><?php esc_html_e('Total estimado:', 'flavor-platform'); ?> <strong><span id="total-importe">-</span> €</strong></p>
                    </div>
                <?php endif; ?>

                <div class="flavor-form-actions">
                    <button type="submit" class="flavor-btn flavor-btn-primary">
                        <span class="dashicons dashicons-calendar-alt"></span>
                        <?php esc_html_e('Confirmar Reserva', 'flavor-platform'); ?>
                    </button>
                </div>
            </form>

            <div id="disponibilidad-horaria" class="disponibilidad-panel" style="display:none;">
                <h4><?php esc_html_e('Horarios ocupados', 'flavor-platform'); ?></h4>
                <div class="horarios-ocupados"></div>
            </div>
        </div>
        <?php
        return ob_get_clean();
    }

    /**
     * Shortcode: Mis reservas
     */
    public function shortcode_mis_reservas($atts) {
        if (!is_user_logged_in()) {
            return '<p class="flavor-login-required">' . __('Debes iniciar sesión.', 'flavor-platform') . '</p>';
        }

        $this->encolar_assets();
        $usuario_id = get_current_user_id();

        global $wpdb;
        $tabla_reservas = $wpdb->prefix . 'flavor_espacios_reservas';
        $tabla_espacios = $wpdb->prefix . 'flavor_espacios_comunes';

        $reservas = [];
        if (Flavor_Chat_Helpers::tabla_existe($tabla_reservas)) {
            $reservas = $wpdb->get_results($wpdb->prepare(
                "SELECT r.*, e.nombre as espacio_nombre, e.slug as espacio_slug, e.imagen_destacada
                 FROM {$tabla_reservas} r
                 JOIN {$tabla_espacios} e ON r.espacio_id = e.id
                 WHERE r.usuario_id = %d
                 ORDER BY r.fecha DESC, r.hora_inicio DESC
                 LIMIT 50",
                $usuario_id
            ));
        }

        $estados_colores = [
            'pendiente' => 'warning',
            'confirmada' => 'success',
            'cancelada' => 'danger',
            'completada' => 'secondary',
            'rechazada' => 'danger',
        ];

        ob_start();
        ?>
        <div class="flavor-espacios-mis-reservas">
            <h2><?php esc_html_e('Mis Reservas', 'flavor-platform'); ?></h2>

            <?php if (empty($reservas)): ?>
                <div class="flavor-empty-state">
                    <span class="dashicons dashicons-calendar-alt"></span>
                    <p><?php esc_html_e('No tienes reservas.', 'flavor-platform'); ?></p>
                    <a href="<?php echo esc_url(home_url('/espacios/')); ?>" class="flavor-btn flavor-btn-primary">
                        <?php esc_html_e('Ver Espacios Disponibles', 'flavor-platform'); ?>
                    </a>
                </div>
            <?php else: ?>
                <div class="reservas-lista">
                    <?php
                    $hoy = date('Y-m-d');
                    $reservas_futuras = array_filter($reservas, fn($r) => $r->fecha >= $hoy && $r->estado !== 'cancelada');
                    $reservas_pasadas = array_filter($reservas, fn($r) => $r->fecha < $hoy || $r->estado === 'cancelada');
                    ?>

                    <?php if (!empty($reservas_futuras)): ?>
                        <h3><?php esc_html_e('Próximas Reservas', 'flavor-platform'); ?></h3>
                        <div class="reservas-grid">
                            <?php foreach ($reservas_futuras as $reserva): ?>
                                <div class="reserva-card">
                                    <?php if (!empty($reserva->imagen_destacada)): ?>
                                        <div class="reserva-imagen">
                                            <img src="<?php echo esc_url($reserva->imagen_destacada); ?>" alt="">
                                        </div>
                                    <?php endif; ?>
                                    <div class="reserva-info">
                                        <h4><?php echo esc_html($reserva->espacio_nombre); ?></h4>
                                        <p class="reserva-fecha">
                                            <span class="dashicons dashicons-calendar-alt"></span>
                                            <?php echo date_i18n('l, d M Y', strtotime($reserva->fecha)); ?>
                                        </p>
                                        <p class="reserva-horario">
                                            <span class="dashicons dashicons-clock"></span>
                                            <?php echo esc_html(substr($reserva->hora_inicio, 0, 5)); ?> - <?php echo esc_html(substr($reserva->hora_fin, 0, 5)); ?>
                                        </p>
                                        <span class="flavor-badge flavor-badge-<?php echo esc_attr($estados_colores[$reserva->estado] ?? 'secondary'); ?>">
                                            <?php echo esc_html(ucfirst($reserva->estado)); ?>
                                        </span>
                                    </div>
                                    <div class="reserva-acciones">
                                        <?php if ($reserva->estado === 'pendiente' || $reserva->estado === 'confirmada'): ?>
                                            <button type="button" class="flavor-btn flavor-btn-sm flavor-btn-danger btn-cancelar-reserva" data-reserva="<?php echo esc_attr($reserva->id); ?>">
                                                <?php esc_html_e('Cancelar', 'flavor-platform'); ?>
                                            </button>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>

                    <?php if (!empty($reservas_pasadas)): ?>
                        <h3><?php esc_html_e('Historial', 'flavor-platform'); ?></h3>
                        <div class="flavor-table-responsive">
                            <table class="flavor-table">
                                <thead>
                                    <tr>
                                        <th><?php esc_html_e('Espacio', 'flavor-platform'); ?></th>
                                        <th><?php esc_html_e('Fecha', 'flavor-platform'); ?></th>
                                        <th><?php esc_html_e('Horario', 'flavor-platform'); ?></th>
                                        <th><?php esc_html_e('Estado', 'flavor-platform'); ?></th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($reservas_pasadas as $reserva): ?>
                                        <tr>
                                            <td><?php echo esc_html($reserva->espacio_nombre); ?></td>
                                            <td><?php echo date_i18n('d/m/Y', strtotime($reserva->fecha)); ?></td>
                                            <td><?php echo esc_html(substr($reserva->hora_inicio, 0, 5)); ?> - <?php echo esc_html(substr($reserva->hora_fin, 0, 5)); ?></td>
                                            <td>
                                                <span class="flavor-badge flavor-badge-<?php echo esc_attr($estados_colores[$reserva->estado] ?? 'secondary'); ?>">
                                                    <?php echo esc_html(ucfirst($reserva->estado)); ?>
                                                </span>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
        </div>
        <?php
        return ob_get_clean();
    }

    /**
     * Shortcode: Calendario
     */
    public function shortcode_calendario($atts) {
        $this->encolar_assets();

        $atts = shortcode_atts([
            'espacio_id' => 0,
        ], $atts);

        global $wpdb;
        $tabla_espacios = $wpdb->prefix . 'flavor_espacios_comunes';

        $espacios = $wpdb->get_results(
            "SELECT id, nombre, slug FROM {$tabla_espacios} WHERE estado = 'disponible' ORDER BY nombre"
        );

        ob_start();
        ?>
        <div class="flavor-espacios-calendario">
            <div class="calendario-filtros">
                <select id="filtro-espacio-calendario">
                    <option value=""><?php esc_html_e('Todos los espacios', 'flavor-platform'); ?></option>
                    <?php foreach ($espacios as $espacio): ?>
                        <option value="<?php echo esc_attr($espacio->id); ?>" <?php selected($atts['espacio_id'], $espacio->id); ?>>
                            <?php echo esc_html($espacio->nombre); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div id="calendario-espacios" class="calendario-container" data-espacio="<?php echo intval($atts['espacio_id']); ?>">
                <!-- Calendario renderizado por JS -->
                <p class="cargando"><?php esc_html_e('Cargando calendario...', 'flavor-platform'); ?></p>
            </div>

            <div class="calendario-leyenda">
                <span class="leyenda-item disponible"><span class="dot"></span> <?php esc_html_e('Disponible', 'flavor-platform'); ?></span>
                <span class="leyenda-item ocupado"><span class="dot"></span> <?php esc_html_e('Ocupado', 'flavor-platform'); ?></span>
                <span class="leyenda-item mi-reserva"><span class="dot"></span> <?php esc_html_e('Mi reserva', 'flavor-platform'); ?></span>
            </div>
        </div>
        <?php
        return ob_get_clean();
    }

    // ================================
    // AJAX HANDLERS
    // ================================

    /**
     * AJAX: Reservar espacio
     */
    public function ajax_reservar() {
        check_ajax_referer('espacios_nonce', 'espacios_nonce_field');

        if (!is_user_logged_in()) {
            wp_send_json_error(__('Debes iniciar sesión', 'flavor-platform'));
        }

        $espacio_id = isset($_POST['espacio_id']) ? absint($_POST['espacio_id']) : 0;
        $fecha = isset($_POST['fecha']) ? sanitize_text_field($_POST['fecha']) : '';
        $hora_inicio = isset($_POST['hora_inicio']) ? sanitize_text_field($_POST['hora_inicio']) : '';
        $hora_fin = isset($_POST['hora_fin']) ? sanitize_text_field($_POST['hora_fin']) : '';
        $motivo = isset($_POST['motivo']) ? sanitize_textarea_field($_POST['motivo']) : '';
        $asistentes = isset($_POST['asistentes']) ? absint($_POST['asistentes']) : null;
        $notas = isset($_POST['notas']) ? sanitize_textarea_field($_POST['notas']) : '';

        if (!$espacio_id || empty($fecha) || empty($hora_inicio) || empty($hora_fin) || empty($motivo)) {
            wp_send_json_error(__('Todos los campos obligatorios deben completarse', 'flavor-platform'));
        }

        // Validar fecha
        if (strtotime($fecha) < strtotime('today')) {
            wp_send_json_error(__('No puedes reservar en fechas pasadas', 'flavor-platform'));
        }

        global $wpdb;
        $tabla_espacios = $wpdb->prefix . 'flavor_espacios_comunes';
        $tabla_reservas = $wpdb->prefix . 'flavor_espacios_reservas';

        $espacio = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$tabla_espacios} WHERE id = %d AND estado = 'disponible'",
            $espacio_id
        ));

        if (!$espacio) {
            wp_send_json_error(__('Espacio no disponible', 'flavor-platform'));
        }

        // Verificar conflictos
        $conflicto = $wpdb->get_var($wpdb->prepare(
            "SELECT id FROM {$tabla_reservas}
             WHERE espacio_id = %d AND fecha = %s AND estado IN ('pendiente', 'confirmada')
             AND ((hora_inicio < %s AND hora_fin > %s) OR (hora_inicio < %s AND hora_fin > %s) OR (hora_inicio >= %s AND hora_fin <= %s))",
            $espacio_id, $fecha,
            $hora_fin, $hora_inicio,
            $hora_fin, $hora_inicio,
            $hora_inicio, $hora_fin
        ));

        if ($conflicto) {
            wp_send_json_error(__('El horario seleccionado no está disponible', 'flavor-platform'));
        }

        // Calcular importe
        $horas = (strtotime($hora_fin) - strtotime($hora_inicio)) / 3600;
        $importe = $espacio->tarifa_hora ? $espacio->tarifa_hora * $horas : 0;

        $usuario_id = get_current_user_id();

        $resultado = $wpdb->insert($tabla_reservas, [
            'espacio_id' => $espacio_id,
            'usuario_id' => $usuario_id,
            'fecha' => $fecha,
            'hora_inicio' => $hora_inicio,
            'hora_fin' => $hora_fin,
            'motivo' => $motivo,
            'asistentes' => $asistentes,
            'notas' => $notas,
            'importe' => $importe,
            'estado' => $espacio->requiere_aprobacion ? 'pendiente' : 'confirmada',
            'created_at' => current_time('mysql'),
        ]);

        if ($resultado) {
            $reserva_id = $wpdb->insert_id;
            do_action('espacio_reserva_created', $reserva_id, $usuario_id, $espacio_id);

            $mensaje = $espacio->requiere_aprobacion
                ? __('Reserva enviada. Recibirás confirmación pronto.', 'flavor-platform')
                : __('¡Reserva confirmada!', 'flavor-platform');

            wp_send_json_success([
                'mensaje' => $mensaje,
                'reserva_id' => $reserva_id,
                'redirect' => home_url('/mi-portal/espacios/'),
            ]);
        } else {
            wp_send_json_error(__('Error al crear la reserva', 'flavor-platform'));
        }
    }

    /**
     * AJAX: Cancelar reserva
     */
    public function ajax_cancelar_reserva() {
        check_ajax_referer('espacios_nonce', 'nonce');

        if (!is_user_logged_in()) {
            wp_send_json_error(__('Debes iniciar sesión', 'flavor-platform'));
        }

        $reserva_id = isset($_POST['reserva_id']) ? absint($_POST['reserva_id']) : 0;
        $usuario_id = get_current_user_id();

        if (!$reserva_id) {
            wp_send_json_error(__('Reserva no válida', 'flavor-platform'));
        }

        global $wpdb;
        $tabla_reservas = $wpdb->prefix . 'flavor_espacios_reservas';

        // Verificar que la reserva pertenece al usuario
        $reserva = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$tabla_reservas} WHERE id = %d AND usuario_id = %d",
            $reserva_id,
            $usuario_id
        ));

        if (!$reserva) {
            wp_send_json_error(__('Reserva no encontrada', 'flavor-platform'));
        }

        if ($reserva->estado === 'cancelada') {
            wp_send_json_error(__('Esta reserva ya está cancelada', 'flavor-platform'));
        }

        // Verificar que no sea muy tarde para cancelar
        $fecha_reserva = $reserva->fecha . ' ' . $reserva->hora_inicio;
        if (strtotime($fecha_reserva) < time()) {
            wp_send_json_error(__('No puedes cancelar una reserva pasada', 'flavor-platform'));
        }

        $resultado = $wpdb->update(
            $tabla_reservas,
            [
                'estado' => 'cancelada',
                'updated_at' => current_time('mysql'),
            ],
            ['id' => $reserva_id]
        );

        if ($resultado !== false) {
            do_action('espacio_reserva_cancelled', $reserva_id, $usuario_id);
            wp_send_json_success(['mensaje' => __('Reserva cancelada', 'flavor-platform')]);
        } else {
            wp_send_json_error(__('Error al cancelar la reserva', 'flavor-platform'));
        }
    }

    /**
     * AJAX: Obtener disponibilidad
     */
    public function ajax_obtener_disponibilidad() {
        $espacio_id = isset($_POST['espacio_id']) ? absint($_POST['espacio_id']) : 0;
        $fecha = isset($_POST['fecha']) ? sanitize_text_field($_POST['fecha']) : '';

        if (!$espacio_id || empty($fecha)) {
            wp_send_json_error(__('Datos incompletos', 'flavor-platform'));
        }

        global $wpdb;
        $tabla_reservas = $wpdb->prefix . 'flavor_espacios_reservas';

        $reservas = $wpdb->get_results($wpdb->prepare(
            "SELECT hora_inicio, hora_fin, estado FROM {$tabla_reservas}
             WHERE espacio_id = %d AND fecha = %s AND estado IN ('pendiente', 'confirmada')
             ORDER BY hora_inicio",
            $espacio_id,
            $fecha
        ));

        $ocupados = array_map(function($reserva) {
            return [
                'inicio' => substr($reserva->hora_inicio, 0, 5),
                'fin' => substr($reserva->hora_fin, 0, 5),
            ];
        }, $reservas);

        wp_send_json_success(['ocupados' => $ocupados]);
    }

    /**
     * AJAX: Obtener espacios
     */
    public function ajax_obtener_espacios() {
        global $wpdb;
        $tabla_espacios = $wpdb->prefix . 'flavor_espacios_comunes';

        $espacios = $wpdb->get_results(
            "SELECT id, nombre, slug, tipo, capacidad, tarifa_hora, imagen_destacada
             FROM {$tabla_espacios}
             WHERE estado = 'disponible'
             ORDER BY nombre"
        );

        wp_send_json_success(['espacios' => $espacios]);
    }
}

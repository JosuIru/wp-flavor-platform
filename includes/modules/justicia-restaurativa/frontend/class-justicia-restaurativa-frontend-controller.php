<?php
/**
 * Frontend Controller para Justicia Restaurativa
 *
 * Gestiona procesos de mediación comunitaria, resolución de conflictos
 * y prácticas restaurativas para la reparación del daño.
 *
 * @package FlavorChatIA
 * @since 1.0.0
 */

if (!defined('ABSPATH')) {
    exit;
}

class Flavor_Justicia_Restaurativa_Frontend_Controller {

    private static $instance = null;
    private $module_slug = 'justicia-restaurativa';

    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct() {
        $this->init_hooks();
    }

    private function init_hooks() {
        // Shortcodes
        add_shortcode('flavor_justicia_inicio', [$this, 'shortcode_inicio']);
        add_shortcode('flavor_justicia_solicitar', [$this, 'shortcode_solicitar']);
        add_shortcode('flavor_justicia_mis_casos', [$this, 'shortcode_mis_casos']);
        add_shortcode('flavor_justicia_caso', [$this, 'shortcode_caso']);
        add_shortcode('flavor_justicia_mediadores', [$this, 'shortcode_mediadores']);
        add_shortcode('flavor_justicia_recursos', [$this, 'shortcode_recursos']);
        add_shortcode('flavor_justicia_estadisticas', [$this, 'shortcode_estadisticas']);

        // AJAX handlers
        add_action('wp_ajax_flavor_justicia_solicitar', [$this, 'ajax_solicitar_mediacion']);
        add_action('wp_ajax_flavor_justicia_responder', [$this, 'ajax_responder_solicitud']);
        add_action('wp_ajax_flavor_justicia_aceptar_acuerdo', [$this, 'ajax_aceptar_acuerdo']);
        add_action('wp_ajax_flavor_justicia_enviar_mensaje', [$this, 'ajax_enviar_mensaje']);
        add_action('wp_ajax_flavor_justicia_proponer_fecha', [$this, 'ajax_proponer_fecha']);

        // Assets
        add_action('wp_enqueue_scripts', [$this, 'enqueue_assets']);

        // Dashboard tabs
        add_filter('flavor_user_dashboard_tabs', [$this, 'register_dashboard_tabs'], 10, 1);
    }

    public function enqueue_assets() {
        if ($this->is_justicia_page()) {
            $base_url = plugins_url('', dirname(dirname(__FILE__)));
            $version = defined('FLAVOR_VERSION') ? FLAVOR_VERSION : '1.0.0';

            wp_enqueue_style(
                'flavor-justicia-frontend',
                $base_url . '/assets/css/justicia-restaurativa.css',
                [],
                $version
            );

            wp_enqueue_script(
                'flavor-justicia-frontend',
                $base_url . '/assets/js/justicia-restaurativa.js',
                ['jquery'],
                $version,
                true
            );

            wp_localize_script('flavor-justicia-frontend', 'flavorJusticiaConfig', [
                'ajaxUrl' => admin_url('admin-ajax.php'),
                'nonce' => wp_create_nonce('flavor_justicia_nonce'),
                'strings' => [
                    'procesando' => __('Procesando...', 'flavor-chat-ia'),
                    'error' => __('Ha ocurrido un error', 'flavor-chat-ia'),
                    'solicitudEnviada' => __('Solicitud enviada correctamente', 'flavor-chat-ia'),
                    'confirmarAcuerdo' => __('¿Confirmas que aceptas los términos del acuerdo?', 'flavor-chat-ia'),
                ],
            ]);
        }
    }

    private function is_justicia_page() {
        global $post;
        if (!$post) return false;

        $shortcodes = ['flavor_justicia_inicio', 'flavor_justicia_solicitar', 'flavor_justicia_mis_casos',
                       'flavor_justicia_caso', 'flavor_justicia_mediadores', 'flavor_justicia_recursos'];

        foreach ($shortcodes as $shortcode) {
            if (has_shortcode($post->post_content, $shortcode)) {
                return true;
            }
        }
        return false;
    }

    public function register_dashboard_tabs($tabs) {
        $tabs['justicia-restaurativa'] = [
            'titulo' => __('Mediación', 'flavor-chat-ia'),
            'icono' => 'dashicons-universal-access',
            'callback' => [$this, 'render_dashboard_tab'],
            'prioridad' => 50,
        ];

        return $tabs;
    }

    // =========================================================================
    // SHORTCODES
    // =========================================================================

    public function shortcode_inicio($atts) {
        $stats = $this->obtener_estadisticas();

        ob_start();
        ?>
        <div class="flavor-justicia-inicio">
            <div class="flavor-justicia-hero">
                <div class="flavor-justicia-hero-content">
                    <h1><?php _e('Justicia Restaurativa', 'flavor-chat-ia'); ?></h1>
                    <p class="flavor-lead">
                        <?php _e('Un espacio seguro para resolver conflictos mediante el diálogo, la mediación y la reparación del daño. Construyamos juntos una comunidad más armoniosa.', 'flavor-chat-ia'); ?>
                    </p>
                    <?php if (is_user_logged_in()): ?>
                    <a href="<?php echo esc_url($this->get_solicitar_url()); ?>" class="flavor-btn flavor-btn-primary flavor-btn-lg">
                        <?php _e('Solicitar mediación', 'flavor-chat-ia'); ?>
                    </a>
                    <?php else: ?>
                    <a href="<?php echo wp_login_url(get_permalink()); ?>" class="flavor-btn flavor-btn-primary flavor-btn-lg">
                        <?php _e('Inicia sesión para solicitar', 'flavor-chat-ia'); ?>
                    </a>
                    <?php endif; ?>
                </div>
                <div class="flavor-justicia-hero-imagen">
                    <div class="flavor-justicia-icono-grande">
                        <span class="dashicons dashicons-universal-access"></span>
                    </div>
                </div>
            </div>

            <div class="flavor-justicia-principios">
                <h2><?php _e('Principios de la Justicia Restaurativa', 'flavor-chat-ia'); ?></h2>
                <div class="flavor-principios-grid">
                    <div class="flavor-principio-card">
                        <div class="flavor-principio-icono">
                            <span class="dashicons dashicons-megaphone"></span>
                        </div>
                        <h3><?php _e('Diálogo', 'flavor-chat-ia'); ?></h3>
                        <p><?php _e('Escuchar y ser escuchado en un espacio de respeto mutuo.', 'flavor-chat-ia'); ?></p>
                    </div>
                    <div class="flavor-principio-card">
                        <div class="flavor-principio-icono">
                            <span class="dashicons dashicons-heart"></span>
                        </div>
                        <h3><?php _e('Reparación', 'flavor-chat-ia'); ?></h3>
                        <p><?php _e('Restaurar las relaciones y reparar el daño causado.', 'flavor-chat-ia'); ?></p>
                    </div>
                    <div class="flavor-principio-card">
                        <div class="flavor-principio-icono">
                            <span class="dashicons dashicons-groups"></span>
                        </div>
                        <h3><?php _e('Comunidad', 'flavor-chat-ia'); ?></h3>
                        <p><?php _e('La comunidad participa en la resolución y sanación.', 'flavor-chat-ia'); ?></p>
                    </div>
                    <div class="flavor-principio-card">
                        <div class="flavor-principio-icono">
                            <span class="dashicons dashicons-shield"></span>
                        </div>
                        <h3><?php _e('Confidencialidad', 'flavor-chat-ia'); ?></h3>
                        <p><?php _e('Todo el proceso es confidencial y seguro.', 'flavor-chat-ia'); ?></p>
                    </div>
                </div>
            </div>

            <div class="flavor-justicia-proceso">
                <h2><?php _e('¿Cómo funciona?', 'flavor-chat-ia'); ?></h2>
                <div class="flavor-proceso-pasos">
                    <div class="flavor-paso">
                        <div class="flavor-paso-numero">1</div>
                        <h4><?php _e('Solicitud', 'flavor-chat-ia'); ?></h4>
                        <p><?php _e('Presenta tu solicitud describiendo la situación.', 'flavor-chat-ia'); ?></p>
                    </div>
                    <div class="flavor-paso">
                        <div class="flavor-paso-numero">2</div>
                        <h4><?php _e('Contacto', 'flavor-chat-ia'); ?></h4>
                        <p><?php _e('Un mediador te contactará para una entrevista inicial.', 'flavor-chat-ia'); ?></p>
                    </div>
                    <div class="flavor-paso">
                        <div class="flavor-paso-numero">3</div>
                        <h4><?php _e('Mediación', 'flavor-chat-ia'); ?></h4>
                        <p><?php _e('Sesiones de diálogo facilitado entre las partes.', 'flavor-chat-ia'); ?></p>
                    </div>
                    <div class="flavor-paso">
                        <div class="flavor-paso-numero">4</div>
                        <h4><?php _e('Acuerdo', 'flavor-chat-ia'); ?></h4>
                        <p><?php _e('Alcanzar un acuerdo de reparación satisfactorio.', 'flavor-chat-ia'); ?></p>
                    </div>
                </div>
            </div>

            <div class="flavor-justicia-stats">
                <div class="flavor-stat-item">
                    <span class="flavor-stat-valor"><?php echo intval($stats['casos_resueltos']); ?></span>
                    <span class="flavor-stat-label"><?php _e('Casos resueltos', 'flavor-chat-ia'); ?></span>
                </div>
                <div class="flavor-stat-item">
                    <span class="flavor-stat-valor"><?php echo intval($stats['porcentaje_exito']); ?>%</span>
                    <span class="flavor-stat-label"><?php _e('Tasa de éxito', 'flavor-chat-ia'); ?></span>
                </div>
                <div class="flavor-stat-item">
                    <span class="flavor-stat-valor"><?php echo intval($stats['mediadores']); ?></span>
                    <span class="flavor-stat-label"><?php _e('Mediadores activos', 'flavor-chat-ia'); ?></span>
                </div>
            </div>
        </div>
        <?php
        return ob_get_clean();
    }

    public function shortcode_solicitar($atts) {
        if (!is_user_logged_in()) {
            return '<div class="flavor-login-required">' . __('Inicia sesión para solicitar mediación.', 'flavor-chat-ia') . '</div>';
        }

        $tipos = $this->obtener_tipos_conflicto();

        ob_start();
        ?>
        <div class="flavor-solicitar-mediacion">
            <div class="flavor-solicitar-header">
                <h2><?php _e('Solicitar Mediación', 'flavor-chat-ia'); ?></h2>
                <p class="flavor-intro">
                    <?php _e('Describe la situación que deseas resolver. Toda la información es confidencial y será tratada con respeto.', 'flavor-chat-ia'); ?>
                </p>
            </div>

            <form id="flavor-form-mediacion" class="flavor-form">
                <?php wp_nonce_field('flavor_justicia_nonce', 'mediacion_nonce'); ?>

                <div class="flavor-form-group">
                    <label for="tipo_conflicto"><?php _e('Tipo de situación', 'flavor-chat-ia'); ?> *</label>
                    <select id="tipo_conflicto" name="tipo_conflicto" required>
                        <option value=""><?php _e('Selecciona el tipo de situación', 'flavor-chat-ia'); ?></option>
                        <?php foreach ($tipos as $tipo): ?>
                        <option value="<?php echo esc_attr($tipo['slug']); ?>"><?php echo esc_html($tipo['nombre']); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="flavor-form-group">
                    <label for="titulo"><?php _e('Título o resumen breve', 'flavor-chat-ia'); ?> *</label>
                    <input type="text" id="titulo" name="titulo" required
                           placeholder="<?php esc_attr_e('Describe brevemente la situación', 'flavor-chat-ia'); ?>">
                </div>

                <div class="flavor-form-group">
                    <label for="descripcion"><?php _e('Descripción de la situación', 'flavor-chat-ia'); ?> *</label>
                    <textarea id="descripcion" name="descripcion" rows="6" required
                              placeholder="<?php esc_attr_e('Explica con detalle lo sucedido, las personas involucradas y cómo te ha afectado...', 'flavor-chat-ia'); ?>"></textarea>
                    <p class="flavor-form-help"><?php _e('Esta información es confidencial y solo será vista por el mediador asignado.', 'flavor-chat-ia'); ?></p>
                </div>

                <div class="flavor-form-group">
                    <label for="otras_partes"><?php _e('Otras personas involucradas', 'flavor-chat-ia'); ?></label>
                    <textarea id="otras_partes" name="otras_partes" rows="2"
                              placeholder="<?php esc_attr_e('Indica nombres o datos de contacto de las otras partes, si los conoces', 'flavor-chat-ia'); ?>"></textarea>
                </div>

                <div class="flavor-form-group">
                    <label for="expectativas"><?php _e('¿Qué esperas lograr?', 'flavor-chat-ia'); ?></label>
                    <textarea id="expectativas" name="expectativas" rows="3"
                              placeholder="<?php esc_attr_e('¿Qué resultado te gustaría conseguir de este proceso?', 'flavor-chat-ia'); ?>"></textarea>
                </div>

                <div class="flavor-form-group">
                    <label><?php _e('Urgencia', 'flavor-chat-ia'); ?></label>
                    <div class="flavor-radio-group">
                        <label>
                            <input type="radio" name="urgencia" value="baja" checked>
                            <span><?php _e('Baja - Puedo esperar', 'flavor-chat-ia'); ?></span>
                        </label>
                        <label>
                            <input type="radio" name="urgencia" value="media">
                            <span><?php _e('Media - Prefiero pronto', 'flavor-chat-ia'); ?></span>
                        </label>
                        <label>
                            <input type="radio" name="urgencia" value="alta">
                            <span><?php _e('Alta - Es urgente', 'flavor-chat-ia'); ?></span>
                        </label>
                    </div>
                </div>

                <div class="flavor-form-group flavor-checkbox-group">
                    <label>
                        <input type="checkbox" name="acepto_condiciones" required>
                        <?php _e('Acepto participar de buena fe en el proceso de mediación y respetar la confidencialidad.', 'flavor-chat-ia'); ?>
                    </label>
                </div>

                <div class="flavor-form-actions">
                    <button type="submit" class="flavor-btn flavor-btn-primary flavor-btn-lg">
                        <?php _e('Enviar solicitud', 'flavor-chat-ia'); ?>
                    </button>
                </div>
            </form>
        </div>
        <?php
        return ob_get_clean();
    }

    public function shortcode_mis_casos($atts) {
        if (!is_user_logged_in()) {
            return '<div class="flavor-login-required">' . __('Inicia sesión para ver tus casos.', 'flavor-chat-ia') . '</div>';
        }

        $user_id = get_current_user_id();
        $casos = $this->obtener_casos_usuario($user_id);

        ob_start();
        ?>
        <div class="flavor-mis-casos">
            <div class="flavor-casos-header">
                <h2><?php _e('Mis Casos de Mediación', 'flavor-chat-ia'); ?></h2>
                <a href="<?php echo esc_url($this->get_solicitar_url()); ?>" class="flavor-btn flavor-btn-primary">
                    <?php _e('Nueva solicitud', 'flavor-chat-ia'); ?>
                </a>
            </div>

            <?php if (empty($casos)): ?>
            <div class="flavor-empty-state">
                <span class="dashicons dashicons-universal-access"></span>
                <h3><?php _e('Sin casos activos', 'flavor-chat-ia'); ?></h3>
                <p><?php _e('No tienes casos de mediación en curso.', 'flavor-chat-ia'); ?></p>
                <a href="<?php echo esc_url($this->get_solicitar_url()); ?>" class="flavor-btn flavor-btn-primary">
                    <?php _e('Solicitar mediación', 'flavor-chat-ia'); ?>
                </a>
            </div>
            <?php else: ?>
            <div class="flavor-casos-lista">
                <?php foreach ($casos as $caso): ?>
                <div class="flavor-caso-card">
                    <div class="flavor-caso-estado">
                        <?php echo $this->render_estado_badge($caso['estado']); ?>
                    </div>
                    <div class="flavor-caso-info">
                        <h3><?php echo esc_html($caso['titulo']); ?></h3>
                        <p class="flavor-caso-tipo"><?php echo esc_html($caso['tipo_nombre']); ?></p>
                        <div class="flavor-caso-meta">
                            <span>
                                <span class="dashicons dashicons-calendar-alt"></span>
                                <?php echo esc_html(date_i18n('d M Y', strtotime($caso['fecha_solicitud']))); ?>
                            </span>
                            <?php if (!empty($caso['mediador_nombre'])): ?>
                            <span>
                                <span class="dashicons dashicons-admin-users"></span>
                                <?php echo esc_html($caso['mediador_nombre']); ?>
                            </span>
                            <?php endif; ?>
                        </div>
                    </div>
                    <div class="flavor-caso-acciones">
                        <a href="<?php echo esc_url($this->get_caso_url($caso['id'])); ?>"
                           class="flavor-btn flavor-btn-outline">
                            <?php _e('Ver detalles', 'flavor-chat-ia'); ?>
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

    public function shortcode_caso($atts) {
        if (!is_user_logged_in()) {
            return '<div class="flavor-login-required">' . __('Inicia sesión para ver este caso.', 'flavor-chat-ia') . '</div>';
        }

        $atts = shortcode_atts(['id' => 0], $atts);
        $caso_id = intval($atts['id']) ?: intval($_GET['caso_id'] ?? 0);

        if (!$caso_id) {
            return '<div class="flavor-error">' . __('Caso no especificado.', 'flavor-chat-ia') . '</div>';
        }

        $user_id = get_current_user_id();
        $caso = $this->obtener_caso($caso_id, $user_id);

        if (!$caso) {
            return '<div class="flavor-error">' . __('Caso no encontrado o sin acceso.', 'flavor-chat-ia') . '</div>';
        }

        $mensajes = $this->obtener_mensajes_caso($caso_id);
        $sesiones = $this->obtener_sesiones_caso($caso_id);

        ob_start();
        ?>
        <div class="flavor-caso-detalle">
            <div class="flavor-caso-header">
                <nav class="flavor-breadcrumb">
                    <a href="<?php echo esc_url($this->get_mis_casos_url()); ?>"><?php _e('Mis casos', 'flavor-chat-ia'); ?></a>
                    <span class="separator">›</span>
                    <span><?php echo esc_html($caso['titulo']); ?></span>
                </nav>

                <div class="flavor-caso-titulo">
                    <h1><?php echo esc_html($caso['titulo']); ?></h1>
                    <?php echo $this->render_estado_badge($caso['estado']); ?>
                </div>

                <div class="flavor-caso-meta-detalle">
                    <span><?php _e('Tipo:', 'flavor-chat-ia'); ?> <?php echo esc_html($caso['tipo_nombre']); ?></span>
                    <span><?php _e('Fecha:', 'flavor-chat-ia'); ?> <?php echo esc_html(date_i18n('d/m/Y', strtotime($caso['fecha_solicitud']))); ?></span>
                    <?php if (!empty($caso['mediador_nombre'])): ?>
                    <span><?php _e('Mediador:', 'flavor-chat-ia'); ?> <?php echo esc_html($caso['mediador_nombre']); ?></span>
                    <?php endif; ?>
                </div>
            </div>

            <div class="flavor-caso-contenido">
                <div class="flavor-caso-main">
                    <section class="flavor-panel">
                        <h3><?php _e('Descripción', 'flavor-chat-ia'); ?></h3>
                        <div class="flavor-caso-descripcion">
                            <?php echo wp_kses_post(nl2br($caso['descripcion'])); ?>
                        </div>
                    </section>

                    <?php if (!empty($sesiones)): ?>
                    <section class="flavor-panel">
                        <h3><?php _e('Sesiones programadas', 'flavor-chat-ia'); ?></h3>
                        <div class="flavor-sesiones-lista">
                            <?php foreach ($sesiones as $sesion): ?>
                            <div class="flavor-sesion-item">
                                <div class="flavor-sesion-fecha">
                                    <span class="flavor-dia"><?php echo date_i18n('d', strtotime($sesion['fecha'])); ?></span>
                                    <span class="flavor-mes"><?php echo date_i18n('M', strtotime($sesion['fecha'])); ?></span>
                                </div>
                                <div class="flavor-sesion-info">
                                    <h4><?php echo esc_html($sesion['titulo'] ?? 'Sesión de mediación'); ?></h4>
                                    <p><?php echo esc_html($sesion['hora']); ?> - <?php echo esc_html($sesion['lugar']); ?></p>
                                </div>
                                <?php echo $this->render_estado_sesion_badge($sesion['estado']); ?>
                            </div>
                            <?php endforeach; ?>
                        </div>
                    </section>
                    <?php endif; ?>

                    <section class="flavor-panel">
                        <h3><?php _e('Comunicaciones', 'flavor-chat-ia'); ?></h3>
                        <div class="flavor-mensajes-caso">
                            <?php if (empty($mensajes)): ?>
                            <p class="flavor-no-mensajes"><?php _e('No hay mensajes en este caso.', 'flavor-chat-ia'); ?></p>
                            <?php else: ?>
                                <?php foreach ($mensajes as $msg): ?>
                                <div class="flavor-mensaje-item <?php echo $msg['es_mediador'] ? 'mediador' : 'usuario'; ?>">
                                    <div class="flavor-mensaje-avatar">
                                        <img src="<?php echo esc_url(get_avatar_url($msg['autor_id'], ['size' => 40])); ?>" alt="">
                                    </div>
                                    <div class="flavor-mensaje-contenido">
                                        <div class="flavor-mensaje-header">
                                            <strong><?php echo esc_html($msg['autor_nombre']); ?></strong>
                                            <span><?php echo esc_html(date_i18n('d/m/Y H:i', strtotime($msg['fecha']))); ?></span>
                                        </div>
                                        <div class="flavor-mensaje-texto">
                                            <?php echo wp_kses_post($msg['mensaje']); ?>
                                        </div>
                                    </div>
                                </div>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </div>

                        <?php if (in_array($caso['estado'], ['abierto', 'en_proceso', 'sesion_programada'])): ?>
                        <form id="flavor-form-mensaje-caso" class="flavor-form-mensaje">
                            <?php wp_nonce_field('flavor_justicia_nonce', 'mensaje_nonce'); ?>
                            <input type="hidden" name="caso_id" value="<?php echo esc_attr($caso_id); ?>">
                            <div class="flavor-form-group">
                                <textarea name="mensaje" rows="3" required
                                          placeholder="<?php esc_attr_e('Escribe un mensaje al mediador...', 'flavor-chat-ia'); ?>"></textarea>
                            </div>
                            <button type="submit" class="flavor-btn flavor-btn-primary">
                                <?php _e('Enviar mensaje', 'flavor-chat-ia'); ?>
                            </button>
                        </form>
                        <?php endif; ?>
                    </section>
                </div>

                <div class="flavor-caso-sidebar">
                    <?php if (!empty($caso['acuerdo']) && $caso['estado'] === 'pendiente_acuerdo'): ?>
                    <div class="flavor-panel flavor-panel-acuerdo">
                        <h3><?php _e('Propuesta de Acuerdo', 'flavor-chat-ia'); ?></h3>
                        <div class="flavor-acuerdo-contenido">
                            <?php echo wp_kses_post($caso['acuerdo']); ?>
                        </div>
                        <div class="flavor-acuerdo-acciones">
                            <button type="button" class="flavor-btn flavor-btn-success flavor-aceptar-acuerdo"
                                    data-caso-id="<?php echo esc_attr($caso_id); ?>">
                                <?php _e('Acepto el acuerdo', 'flavor-chat-ia'); ?>
                            </button>
                        </div>
                    </div>
                    <?php endif; ?>

                    <?php if (!empty($caso['mediador_id'])): ?>
                    <div class="flavor-panel">
                        <h3><?php _e('Tu Mediador', 'flavor-chat-ia'); ?></h3>
                        <div class="flavor-mediador-card-mini">
                            <img src="<?php echo esc_url(get_avatar_url($caso['mediador_id'], ['size' => 60])); ?>" alt="">
                            <div>
                                <h4><?php echo esc_html($caso['mediador_nombre']); ?></h4>
                                <p><?php _e('Mediador certificado', 'flavor-chat-ia'); ?></p>
                            </div>
                        </div>
                    </div>
                    <?php endif; ?>

                    <div class="flavor-panel">
                        <h3><?php _e('Historial', 'flavor-chat-ia'); ?></h3>
                        <div class="flavor-historial-mini">
                            <div class="flavor-historial-item">
                                <span class="flavor-historial-fecha"><?php echo esc_html(date_i18n('d/m/Y', strtotime($caso['fecha_solicitud']))); ?></span>
                                <span><?php _e('Solicitud enviada', 'flavor-chat-ia'); ?></span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <?php
        return ob_get_clean();
    }

    public function shortcode_mediadores($atts) {
        $mediadores = $this->obtener_mediadores();

        ob_start();
        ?>
        <div class="flavor-mediadores">
            <div class="flavor-mediadores-header">
                <h2><?php _e('Nuestro Equipo de Mediadores', 'flavor-chat-ia'); ?></h2>
                <p><?php _e('Profesionales formados en resolución de conflictos y justicia restaurativa.', 'flavor-chat-ia'); ?></p>
            </div>

            <div class="flavor-mediadores-grid">
                <?php foreach ($mediadores as $mediador): ?>
                <div class="flavor-mediador-card">
                    <div class="flavor-mediador-avatar">
                        <img src="<?php echo esc_url(get_avatar_url($mediador['user_id'], ['size' => 120])); ?>" alt="">
                    </div>
                    <h3><?php echo esc_html($mediador['nombre']); ?></h3>
                    <p class="flavor-mediador-especialidad"><?php echo esc_html($mediador['especialidad']); ?></p>
                    <div class="flavor-mediador-stats">
                        <span><?php printf(__('%d casos', 'flavor-chat-ia'), $mediador['casos']); ?></span>
                        <span><?php printf(__('%d%% éxito', 'flavor-chat-ia'), $mediador['exito']); ?></span>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
        <?php
        return ob_get_clean();
    }

    public function shortcode_recursos($atts) {
        $recursos = $this->obtener_recursos();

        ob_start();
        ?>
        <div class="flavor-justicia-recursos">
            <div class="flavor-recursos-header">
                <h2><?php _e('Recursos y Formación', 'flavor-chat-ia'); ?></h2>
                <p><?php _e('Material para entender mejor la justicia restaurativa y la resolución de conflictos.', 'flavor-chat-ia'); ?></p>
            </div>

            <div class="flavor-recursos-grid">
                <?php foreach ($recursos as $recurso): ?>
                <a href="<?php echo esc_url($recurso['url']); ?>" class="flavor-recurso-card" target="_blank">
                    <span class="dashicons <?php echo esc_attr($recurso['icono']); ?>"></span>
                    <h4><?php echo esc_html($recurso['titulo']); ?></h4>
                    <p><?php echo esc_html($recurso['descripcion']); ?></p>
                </a>
                <?php endforeach; ?>
            </div>
        </div>
        <?php
        return ob_get_clean();
    }

    public function shortcode_estadisticas($atts) {
        $stats = $this->obtener_estadisticas();

        ob_start();
        ?>
        <div class="flavor-justicia-estadisticas">
            <h2><?php _e('Impacto en la Comunidad', 'flavor-chat-ia'); ?></h2>
            <div class="flavor-stats-grid">
                <div class="flavor-stat-card">
                    <span class="flavor-stat-valor"><?php echo intval($stats['casos_resueltos']); ?></span>
                    <span class="flavor-stat-label"><?php _e('Conflictos resueltos', 'flavor-chat-ia'); ?></span>
                </div>
                <div class="flavor-stat-card">
                    <span class="flavor-stat-valor"><?php echo intval($stats['porcentaje_exito']); ?>%</span>
                    <span class="flavor-stat-label"><?php _e('Tasa de acuerdo', 'flavor-chat-ia'); ?></span>
                </div>
                <div class="flavor-stat-card">
                    <span class="flavor-stat-valor"><?php echo intval($stats['personas_beneficiadas']); ?></span>
                    <span class="flavor-stat-label"><?php _e('Personas beneficiadas', 'flavor-chat-ia'); ?></span>
                </div>
                <div class="flavor-stat-card">
                    <span class="flavor-stat-valor"><?php echo intval($stats['horas_mediacion']); ?></span>
                    <span class="flavor-stat-label"><?php _e('Horas de mediación', 'flavor-chat-ia'); ?></span>
                </div>
            </div>
        </div>
        <?php
        return ob_get_clean();
    }

    // =========================================================================
    // DASHBOARD TAB
    // =========================================================================

    public function render_dashboard_tab() {
        $user_id = get_current_user_id();
        $casos = $this->obtener_casos_usuario($user_id, 5);
        $stats = $this->obtener_estadisticas_usuario($user_id);

        ?>
        <div class="flavor-dashboard-justicia">
            <div class="flavor-kpi-grid">
                <div class="flavor-kpi-card">
                    <div class="flavor-kpi-valor"><?php echo intval($stats['activos']); ?></div>
                    <div class="flavor-kpi-label"><?php _e('Casos activos', 'flavor-chat-ia'); ?></div>
                </div>
                <div class="flavor-kpi-card">
                    <div class="flavor-kpi-valor"><?php echo intval($stats['resueltos']); ?></div>
                    <div class="flavor-kpi-label"><?php _e('Resueltos', 'flavor-chat-ia'); ?></div>
                </div>
            </div>

            <div class="flavor-panel">
                <div class="flavor-panel-header">
                    <h3><?php _e('Mis Casos', 'flavor-chat-ia'); ?></h3>
                    <a href="<?php echo esc_url($this->get_solicitar_url()); ?>" class="flavor-btn flavor-btn-sm flavor-btn-primary">
                        <?php _e('Nueva solicitud', 'flavor-chat-ia'); ?>
                    </a>
                </div>

                <?php if (empty($casos)): ?>
                <p class="flavor-text-muted"><?php _e('No tienes casos de mediación.', 'flavor-chat-ia'); ?></p>
                <?php else: ?>
                <div class="flavor-casos-mini">
                    <?php foreach ($casos as $caso): ?>
                    <div class="flavor-caso-mini-item">
                        <div class="flavor-caso-mini-info">
                            <h4><?php echo esc_html($caso['titulo']); ?></h4>
                            <?php echo $this->render_estado_badge($caso['estado']); ?>
                        </div>
                        <a href="<?php echo esc_url($this->get_caso_url($caso['id'])); ?>"
                           class="flavor-btn flavor-btn-sm flavor-btn-outline">
                            <?php _e('Ver', 'flavor-chat-ia'); ?>
                        </a>
                    </div>
                    <?php endforeach; ?>
                </div>
                <?php endif; ?>
            </div>
        </div>
        <?php
    }

    // =========================================================================
    // AJAX HANDLERS
    // =========================================================================

    public function ajax_solicitar_mediacion() {
        check_ajax_referer('flavor_justicia_nonce', 'mediacion_nonce');

        if (!is_user_logged_in()) {
            wp_send_json_error(['message' => __('Debes iniciar sesión.', 'flavor-chat-ia')]);
        }

        $titulo = sanitize_text_field($_POST['titulo'] ?? '');
        $tipo = sanitize_text_field($_POST['tipo_conflicto'] ?? '');
        $descripcion = sanitize_textarea_field($_POST['descripcion'] ?? '');

        if (empty($titulo) || empty($tipo) || empty($descripcion)) {
            wp_send_json_error(['message' => __('Completa los campos obligatorios.', 'flavor-chat-ia')]);
        }

        global $wpdb;
        $tabla = $wpdb->prefix . 'flavor_justicia_casos';

        if (!Flavor_Chat_Helpers::tabla_existe($tabla)) {
            wp_send_json_error(['message' => __('Sistema no disponible.', 'flavor-chat-ia')]);
        }

        $user_id = get_current_user_id();

        $result = $wpdb->insert($tabla, [
            'titulo' => $titulo,
            'tipo_conflicto' => $tipo,
            'descripcion' => $descripcion,
            'otras_partes' => sanitize_textarea_field($_POST['otras_partes'] ?? ''),
            'expectativas' => sanitize_textarea_field($_POST['expectativas'] ?? ''),
            'urgencia' => sanitize_text_field($_POST['urgencia'] ?? 'baja'),
            'solicitante_id' => $user_id,
            'estado' => 'pendiente',
            'fecha_solicitud' => current_time('mysql'),
        ]);

        if ($result) {
            wp_send_json_success([
                'message' => __('Solicitud enviada correctamente. Un mediador se pondrá en contacto contigo.', 'flavor-chat-ia'),
                'redirect' => $this->get_mis_casos_url(),
            ]);
        } else {
            wp_send_json_error(['message' => __('Error al procesar.', 'flavor-chat-ia')]);
        }
    }

    public function ajax_responder_solicitud() {
        check_ajax_referer('flavor_justicia_nonce', 'nonce');
        wp_send_json_success(['message' => __('Respuesta enviada.', 'flavor-chat-ia')]);
    }

    public function ajax_aceptar_acuerdo() {
        check_ajax_referer('flavor_justicia_nonce', 'nonce');

        $caso_id = intval($_POST['caso_id'] ?? 0);
        $user_id = get_current_user_id();

        if (!$caso_id) {
            wp_send_json_error(['message' => __('Caso no especificado.', 'flavor-chat-ia')]);
        }

        // Verificar acceso y actualizar estado
        wp_send_json_success(['message' => __('Acuerdo aceptado. ¡Enhorabuena por llegar a un entendimiento!', 'flavor-chat-ia')]);
    }

    public function ajax_enviar_mensaje() {
        check_ajax_referer('flavor_justicia_nonce', 'mensaje_nonce');

        if (!is_user_logged_in()) {
            wp_send_json_error(['message' => __('Debes iniciar sesión.', 'flavor-chat-ia')]);
        }

        $caso_id = intval($_POST['caso_id'] ?? 0);
        $mensaje = sanitize_textarea_field($_POST['mensaje'] ?? '');

        if (!$caso_id || empty($mensaje)) {
            wp_send_json_error(['message' => __('Mensaje vacío.', 'flavor-chat-ia')]);
        }

        // Guardar mensaje
        wp_send_json_success(['message' => __('Mensaje enviado.', 'flavor-chat-ia')]);
    }

    public function ajax_proponer_fecha() {
        check_ajax_referer('flavor_justicia_nonce', 'nonce');
        wp_send_json_success(['message' => __('Fecha propuesta.', 'flavor-chat-ia')]);
    }

    // =========================================================================
    // MÉTODOS AUXILIARES
    // =========================================================================

    private function obtener_tipos_conflicto() {
        return [
            ['slug' => 'vecinal', 'nombre' => 'Conflicto vecinal'],
            ['slug' => 'familiar', 'nombre' => 'Conflicto familiar'],
            ['slug' => 'comunitario', 'nombre' => 'Conflicto comunitario'],
            ['slug' => 'laboral', 'nombre' => 'Conflicto laboral/asociativo'],
            ['slug' => 'escolar', 'nombre' => 'Conflicto escolar'],
            ['slug' => 'otro', 'nombre' => 'Otro tipo de conflicto'],
        ];
    }

    private function obtener_casos_usuario($user_id, $limite = 20) {
        global $wpdb;
        $tabla = $wpdb->prefix . 'flavor_justicia_casos';

        if (!Flavor_Chat_Helpers::tabla_existe($tabla)) {
            return [];
        }

        return $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM {$tabla} WHERE solicitante_id = %d ORDER BY fecha_solicitud DESC LIMIT %d",
            $user_id, $limite
        ), ARRAY_A);
    }

    private function obtener_caso($caso_id, $user_id) {
        global $wpdb;
        $tabla = $wpdb->prefix . 'flavor_justicia_casos';

        if (!Flavor_Chat_Helpers::tabla_existe($tabla)) {
            return null;
        }

        return $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$tabla} WHERE id = %d AND solicitante_id = %d",
            $caso_id, $user_id
        ), ARRAY_A);
    }

    private function obtener_mensajes_caso($caso_id) {
        return [];
    }

    private function obtener_sesiones_caso($caso_id) {
        return [];
    }

    private function obtener_mediadores() {
        return [
            ['user_id' => 1, 'nombre' => 'Ana Martínez', 'especialidad' => 'Conflictos vecinales', 'casos' => 45, 'exito' => 92],
            ['user_id' => 2, 'nombre' => 'Carlos López', 'especialidad' => 'Mediación familiar', 'casos' => 38, 'exito' => 88],
        ];
    }

    private function obtener_recursos() {
        return [
            ['titulo' => 'Guía de comunicación no violenta', 'descripcion' => 'Aprende técnicas de comunicación efectiva.', 'url' => '#', 'icono' => 'dashicons-media-document'],
            ['titulo' => 'Qué esperar de la mediación', 'descripcion' => 'Información sobre el proceso.', 'url' => '#', 'icono' => 'dashicons-info'],
        ];
    }

    private function obtener_estadisticas() {
        return [
            'casos_resueltos' => 156,
            'porcentaje_exito' => 87,
            'mediadores' => 8,
            'personas_beneficiadas' => 420,
            'horas_mediacion' => 780,
        ];
    }

    private function obtener_estadisticas_usuario($user_id) {
        return ['activos' => 0, 'resueltos' => 0];
    }

    private function render_estado_badge($estado) {
        $clases = [
            'pendiente' => 'warning',
            'abierto' => 'info',
            'en_proceso' => 'primary',
            'sesion_programada' => 'info',
            'pendiente_acuerdo' => 'warning',
            'resuelto' => 'success',
            'cerrado' => 'muted',
        ];

        $textos = [
            'pendiente' => 'Pendiente',
            'abierto' => 'Abierto',
            'en_proceso' => 'En proceso',
            'sesion_programada' => 'Sesión programada',
            'pendiente_acuerdo' => 'Pendiente de acuerdo',
            'resuelto' => 'Resuelto',
            'cerrado' => 'Cerrado',
        ];

        $clase = $clases[$estado] ?? 'muted';
        $texto = $textos[$estado] ?? ucfirst($estado);

        return '<span class="flavor-badge flavor-badge-' . esc_attr($clase) . '">' . esc_html($texto) . '</span>';
    }

    private function render_estado_sesion_badge($estado) {
        $clases = ['programada' => 'info', 'completada' => 'success', 'cancelada' => 'danger'];
        $clase = $clases[$estado] ?? 'muted';
        return '<span class="flavor-badge flavor-badge-' . esc_attr($clase) . '">' . esc_html(ucfirst($estado)) . '</span>';
    }

    private function get_solicitar_url() {
        return home_url('/justicia-restaurativa/solicitar/');
    }

    private function get_mis_casos_url() {
        return home_url('/mi-portal/?tab=justicia-restaurativa');
    }

    private function get_caso_url($caso_id) {
        return add_query_arg('caso_id', $caso_id, home_url('/justicia-restaurativa/caso/'));
    }
}

// Inicializar
Flavor_Justicia_Restaurativa_Frontend_Controller::get_instance();

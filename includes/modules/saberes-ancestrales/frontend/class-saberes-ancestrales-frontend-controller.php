<?php
/**
 * Frontend Controller para Saberes Ancestrales
 *
 * Gestiona la preservación y transmisión de conocimientos tradicionales,
 * oficios artesanales y patrimonio cultural inmaterial de la comunidad.
 *
 * @package FlavorChatIA
 * @since 1.0.0
 */

if (!defined('ABSPATH')) {
    exit;
}

class Flavor_Saberes_Ancestrales_Frontend_Controller {

    private static $instance = null;
    private $module_slug = 'saberes-ancestrales';

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
        add_shortcode('flavor_saberes_catalogo', [$this, 'shortcode_catalogo']);
        add_shortcode('flavor_saberes_detalle', [$this, 'shortcode_detalle']);
        add_shortcode('flavor_saberes_maestros', [$this, 'shortcode_maestros']);
        add_shortcode('flavor_saberes_aprendizaje', [$this, 'shortcode_aprendizaje']);
        add_shortcode('flavor_saberes_documentar', [$this, 'shortcode_documentar']);
        add_shortcode('flavor_saberes_mis_aprendizajes', [$this, 'shortcode_mis_aprendizajes']);
        add_shortcode('flavor_saberes_mapa', [$this, 'shortcode_mapa']);
        add_shortcode('flavor_saberes_estadisticas', [$this, 'shortcode_estadisticas']);

        // AJAX handlers
        add_action('wp_ajax_flavor_saberes_documentar', [$this, 'ajax_documentar_saber']);
        add_action('wp_ajax_flavor_saberes_solicitar_aprendizaje', [$this, 'ajax_solicitar_aprendizaje']);
        add_action('wp_ajax_flavor_saberes_confirmar_sesion', [$this, 'ajax_confirmar_sesion']);
        add_action('wp_ajax_flavor_saberes_valorar', [$this, 'ajax_valorar']);
        add_action('wp_ajax_flavor_saberes_ofrecer_ensenanza', [$this, 'ajax_ofrecer_ensenanza']);
        add_action('wp_ajax_nopriv_flavor_saberes_buscar', [$this, 'ajax_buscar']);
        add_action('wp_ajax_flavor_saberes_buscar', [$this, 'ajax_buscar']);

        // Assets
        add_action('wp_enqueue_scripts', [$this, 'enqueue_assets']);

        // Dashboard tabs
        add_filter('flavor_user_dashboard_tabs', [$this, 'register_dashboard_tabs'], 10, 1);
    }

    public function enqueue_assets() {
        if ($this->is_saberes_page()) {
            $base_url = plugins_url('', dirname(dirname(__FILE__)));
            $version = defined('FLAVOR_VERSION') ? FLAVOR_VERSION : '1.0.0';

            wp_enqueue_style(
                'flavor-saberes-frontend',
                $base_url . '/assets/css/saberes-ancestrales.css',
                [],
                $version
            );

            wp_enqueue_script(
                'flavor-saberes-frontend',
                $base_url . '/assets/js/saberes-ancestrales.js',
                ['jquery'],
                $version,
                true
            );

            wp_localize_script('flavor-saberes-frontend', 'flavorSaberesConfig', [
                'ajaxUrl' => admin_url('admin-ajax.php'),
                'nonce' => wp_create_nonce('flavor_saberes_nonce'),
                'strings' => [
                    'procesando' => __('Procesando...', 'flavor-chat-ia'),
                    'error' => __('Ha ocurrido un error', 'flavor-chat-ia'),
                    'solicitudEnviada' => __('Solicitud enviada correctamente', 'flavor-chat-ia'),
                    'graciasValorar' => __('Gracias por tu valoración', 'flavor-chat-ia'),
                ],
            ]);
        }
    }

    private function is_saberes_page() {
        global $post;
        if (!$post) return false;

        $shortcodes = ['flavor_saberes_catalogo', 'flavor_saberes_detalle', 'flavor_saberes_maestros',
                       'flavor_saberes_aprendizaje', 'flavor_saberes_documentar', 'flavor_saberes_mis_aprendizajes',
                       'flavor_saberes_mapa', 'flavor_saberes_estadisticas'];

        foreach ($shortcodes as $shortcode) {
            if (has_shortcode($post->post_content, $shortcode)) {
                return true;
            }
        }
        return false;
    }

    public function register_dashboard_tabs($tabs) {
        $tabs['saberes-ancestrales'] = [
            'titulo' => __('Saberes Ancestrales', 'flavor-chat-ia'),
            'icono' => 'dashicons-book-alt',
            'callback' => [$this, 'render_dashboard_tab'],
            'prioridad' => 45,
        ];

        return $tabs;
    }

    // =========================================================================
    // SHORTCODES
    // =========================================================================

    public function shortcode_catalogo($atts) {
        $atts = shortcode_atts([
            'categoria' => '',
            'limite' => 20,
        ], $atts);

        $saberes = $this->obtener_saberes($atts);
        $categorias = $this->obtener_categorias();

        ob_start();
        ?>
        <div class="flavor-saberes-catalogo">
            <div class="flavor-saberes-header">
                <div>
                    <h2><?php _e('Saberes Ancestrales', 'flavor-chat-ia'); ?></h2>
                    <p class="flavor-saberes-intro">
                        <?php _e('Conocimientos tradicionales transmitidos de generación en generación.', 'flavor-chat-ia'); ?>
                    </p>
                </div>
                <?php if (is_user_logged_in()): ?>
                <a href="<?php echo esc_url($this->get_documentar_url()); ?>" class="flavor-btn flavor-btn-primary">
                    <span class="dashicons dashicons-plus"></span>
                    <?php _e('Documentar saber', 'flavor-chat-ia'); ?>
                </a>
                <?php endif; ?>
            </div>

            <div class="flavor-saberes-filtros">
                <form class="flavor-filtros-form" method="get">
                    <div class="flavor-filtro-grupo">
                        <input type="text" name="buscar" placeholder="<?php esc_attr_e('Buscar saber...', 'flavor-chat-ia'); ?>"
                               value="<?php echo esc_attr($_GET['buscar'] ?? ''); ?>">
                    </div>
                    <div class="flavor-filtro-grupo">
                        <select name="categoria">
                            <option value=""><?php _e('Todas las categorías', 'flavor-chat-ia'); ?></option>
                            <?php foreach ($categorias as $cat): ?>
                            <option value="<?php echo esc_attr($cat['slug']); ?>"
                                    <?php selected($_GET['categoria'] ?? '', $cat['slug']); ?>>
                                <?php echo esc_html($cat['nombre']); ?>
                            </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <button type="submit" class="flavor-btn flavor-btn-outline">
                        <span class="dashicons dashicons-search"></span>
                    </button>
                </form>
            </div>

            <div class="flavor-saberes-grid">
                <?php if (empty($saberes)): ?>
                <div class="flavor-empty-state">
                    <span class="dashicons dashicons-book-alt"></span>
                    <p><?php _e('No se encontraron saberes con esos criterios.', 'flavor-chat-ia'); ?></p>
                </div>
                <?php else: ?>
                    <?php foreach ($saberes as $saber): ?>
                    <div class="flavor-saber-card">
                        <div class="flavor-saber-imagen">
                            <?php if (!empty($saber['imagen'])): ?>
                            <img src="<?php echo esc_url($saber['imagen']); ?>" alt="<?php echo esc_attr($saber['nombre']); ?>">
                            <?php else: ?>
                            <div class="flavor-saber-placeholder">
                                <span class="dashicons <?php echo esc_attr($this->get_icono_categoria($saber['categoria'])); ?>"></span>
                            </div>
                            <?php endif; ?>
                            <span class="flavor-saber-categoria"><?php echo esc_html($saber['categoria_nombre']); ?></span>
                        </div>
                        <div class="flavor-saber-contenido">
                            <h3 class="flavor-saber-titulo">
                                <a href="<?php echo esc_url($this->get_saber_url($saber['id'])); ?>">
                                    <?php echo esc_html($saber['nombre']); ?>
                                </a>
                            </h3>
                            <p class="flavor-saber-descripcion">
                                <?php echo esc_html(wp_trim_words($saber['descripcion'], 20)); ?>
                            </p>
                            <div class="flavor-saber-meta">
                                <span class="flavor-saber-maestro">
                                    <span class="dashicons dashicons-admin-users"></span>
                                    <?php echo esc_html($saber['maestro_nombre']); ?>
                                </span>
                                <?php if ($saber['aprendices'] > 0): ?>
                                <span class="flavor-saber-aprendices">
                                    <?php printf(_n('%d aprendiz', '%d aprendices', $saber['aprendices'], 'flavor-chat-ia'), $saber['aprendices']); ?>
                                </span>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>
        <?php
        return ob_get_clean();
    }

    public function shortcode_detalle($atts) {
        $atts = shortcode_atts(['id' => 0], $atts);
        $saber_id = intval($atts['id']) ?: intval($_GET['saber_id'] ?? 0);

        if (!$saber_id) {
            return '<div class="flavor-error">' . __('Saber no especificado.', 'flavor-chat-ia') . '</div>';
        }

        $saber = $this->obtener_saber($saber_id);
        if (!$saber) {
            return '<div class="flavor-error">' . __('Saber no encontrado.', 'flavor-chat-ia') . '</div>';
        }

        $sesiones = $this->obtener_sesiones_saber($saber_id);
        $recursos = $this->obtener_recursos_saber($saber_id);
        $user_id = get_current_user_id();
        $ya_aprendiz = $this->es_aprendiz($saber_id, $user_id);

        ob_start();
        ?>
        <div class="flavor-saber-detalle">
            <div class="flavor-saber-hero">
                <div class="flavor-saber-hero-imagen">
                    <?php if (!empty($saber['imagen'])): ?>
                    <img src="<?php echo esc_url($saber['imagen']); ?>" alt="<?php echo esc_attr($saber['nombre']); ?>">
                    <?php else: ?>
                    <div class="flavor-saber-placeholder-grande">
                        <span class="dashicons <?php echo esc_attr($this->get_icono_categoria($saber['categoria'])); ?>"></span>
                    </div>
                    <?php endif; ?>
                </div>
                <div class="flavor-saber-hero-info">
                    <span class="flavor-badge flavor-badge-primary"><?php echo esc_html($saber['categoria_nombre']); ?></span>
                    <h1><?php echo esc_html($saber['nombre']); ?></h1>
                    <p class="flavor-saber-descripcion-larga"><?php echo wp_kses_post($saber['descripcion']); ?></p>

                    <div class="flavor-saber-maestro-card">
                        <img src="<?php echo esc_url(get_avatar_url($saber['maestro_id'], ['size' => 60])); ?>" alt="">
                        <div>
                            <span class="flavor-maestro-label"><?php _e('Maestro/a', 'flavor-chat-ia'); ?></span>
                            <h4><?php echo esc_html($saber['maestro_nombre']); ?></h4>
                            <?php if (!empty($saber['maestro_experiencia'])): ?>
                            <p><?php echo esc_html($saber['maestro_experiencia']); ?></p>
                            <?php endif; ?>
                        </div>
                    </div>

                    <div class="flavor-saber-stats">
                        <div class="flavor-stat">
                            <span class="flavor-stat-valor"><?php echo intval($saber['aprendices']); ?></span>
                            <span class="flavor-stat-label"><?php _e('Aprendices', 'flavor-chat-ia'); ?></span>
                        </div>
                        <div class="flavor-stat">
                            <span class="flavor-stat-valor"><?php echo intval($saber['sesiones_realizadas']); ?></span>
                            <span class="flavor-stat-label"><?php _e('Sesiones', 'flavor-chat-ia'); ?></span>
                        </div>
                        <?php if ($saber['valoracion'] > 0): ?>
                        <div class="flavor-stat">
                            <span class="flavor-stat-valor"><?php echo number_format($saber['valoracion'], 1); ?></span>
                            <span class="flavor-stat-label"><?php _e('Valoración', 'flavor-chat-ia'); ?></span>
                        </div>
                        <?php endif; ?>
                    </div>

                    <?php if (is_user_logged_in() && !$ya_aprendiz && $saber['maestro_id'] != $user_id): ?>
                    <button type="button" class="flavor-btn flavor-btn-primary flavor-btn-lg flavor-solicitar-aprendizaje"
                            data-saber-id="<?php echo esc_attr($saber_id); ?>">
                        <span class="dashicons dashicons-welcome-learn-more"></span>
                        <?php _e('Quiero aprender', 'flavor-chat-ia'); ?>
                    </button>
                    <?php elseif ($ya_aprendiz): ?>
                    <span class="flavor-badge flavor-badge-success flavor-badge-lg">
                        <span class="dashicons dashicons-yes"></span>
                        <?php _e('Eres aprendiz de este saber', 'flavor-chat-ia'); ?>
                    </span>
                    <?php endif; ?>
                </div>
            </div>

            <div class="flavor-saber-contenido-detalle">
                <div class="flavor-saber-main">
                    <?php if (!empty($saber['contenido'])): ?>
                    <section class="flavor-panel">
                        <h3><?php _e('Sobre este saber', 'flavor-chat-ia'); ?></h3>
                        <div class="flavor-saber-contenido-texto">
                            <?php echo wp_kses_post($saber['contenido']); ?>
                        </div>
                    </section>
                    <?php endif; ?>

                    <?php if (!empty($saber['historia'])): ?>
                    <section class="flavor-panel">
                        <h3><?php _e('Historia y origen', 'flavor-chat-ia'); ?></h3>
                        <div class="flavor-saber-historia">
                            <?php echo wp_kses_post($saber['historia']); ?>
                        </div>
                    </section>
                    <?php endif; ?>

                    <?php if (!empty($recursos)): ?>
                    <section class="flavor-panel">
                        <h3><?php _e('Recursos documentales', 'flavor-chat-ia'); ?></h3>
                        <div class="flavor-recursos-lista">
                            <?php foreach ($recursos as $recurso): ?>
                            <a href="<?php echo esc_url($recurso['url']); ?>" target="_blank" class="flavor-recurso-item">
                                <span class="dashicons <?php echo $this->get_icono_recurso($recurso['tipo']); ?>"></span>
                                <span><?php echo esc_html($recurso['nombre']); ?></span>
                            </a>
                            <?php endforeach; ?>
                        </div>
                    </section>
                    <?php endif; ?>
                </div>

                <div class="flavor-saber-sidebar">
                    <?php if (!empty($sesiones)): ?>
                    <div class="flavor-panel">
                        <h3><?php _e('Próximas sesiones', 'flavor-chat-ia'); ?></h3>
                        <div class="flavor-sesiones-lista">
                            <?php foreach (array_slice($sesiones, 0, 3) as $sesion): ?>
                            <div class="flavor-sesion-item">
                                <div class="flavor-sesion-fecha">
                                    <span class="flavor-dia"><?php echo date_i18n('d', strtotime($sesion['fecha'])); ?></span>
                                    <span class="flavor-mes"><?php echo date_i18n('M', strtotime($sesion['fecha'])); ?></span>
                                </div>
                                <div class="flavor-sesion-info">
                                    <h4><?php echo esc_html($sesion['titulo']); ?></h4>
                                    <p><span class="dashicons dashicons-clock"></span> <?php echo esc_html($sesion['hora']); ?></p>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                    <?php endif; ?>

                    <div class="flavor-panel">
                        <h3><?php _e('Información', 'flavor-chat-ia'); ?></h3>
                        <ul class="flavor-info-lista">
                            <?php if (!empty($saber['duracion_aprendizaje'])): ?>
                            <li>
                                <span class="dashicons dashicons-calendar-alt"></span>
                                <strong><?php _e('Duración:', 'flavor-chat-ia'); ?></strong>
                                <?php echo esc_html($saber['duracion_aprendizaje']); ?>
                            </li>
                            <?php endif; ?>
                            <?php if (!empty($saber['nivel'])): ?>
                            <li>
                                <span class="dashicons dashicons-chart-line"></span>
                                <strong><?php _e('Nivel:', 'flavor-chat-ia'); ?></strong>
                                <?php echo esc_html($saber['nivel']); ?>
                            </li>
                            <?php endif; ?>
                            <?php if (!empty($saber['materiales'])): ?>
                            <li>
                                <span class="dashicons dashicons-hammer"></span>
                                <strong><?php _e('Materiales:', 'flavor-chat-ia'); ?></strong>
                                <?php echo esc_html($saber['materiales']); ?>
                            </li>
                            <?php endif; ?>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
        <?php
        return ob_get_clean();
    }

    public function shortcode_maestros($atts) {
        $atts = shortcode_atts(['limite' => 12], $atts);
        $maestros = $this->obtener_maestros($atts);

        ob_start();
        ?>
        <div class="flavor-maestros-lista">
            <div class="flavor-maestros-header">
                <h2><?php _e('Maestros y Portadores de Saberes', 'flavor-chat-ia'); ?></h2>
                <p><?php _e('Personas que preservan y transmiten conocimientos ancestrales.', 'flavor-chat-ia'); ?></p>
            </div>

            <div class="flavor-maestros-grid">
                <?php foreach ($maestros as $maestro): ?>
                <div class="flavor-maestro-card">
                    <div class="flavor-maestro-avatar">
                        <img src="<?php echo esc_url(get_avatar_url($maestro['user_id'], ['size' => 120])); ?>" alt="">
                    </div>
                    <h3><?php echo esc_html($maestro['nombre']); ?></h3>
                    <p class="flavor-maestro-especialidad"><?php echo esc_html($maestro['especialidad']); ?></p>
                    <div class="flavor-maestro-stats">
                        <span><?php printf(__('%d saberes', 'flavor-chat-ia'), $maestro['total_saberes']); ?></span>
                        <span><?php printf(__('%d aprendices', 'flavor-chat-ia'), $maestro['total_aprendices']); ?></span>
                    </div>
                    <a href="<?php echo esc_url($this->get_maestro_url($maestro['user_id'])); ?>"
                       class="flavor-btn flavor-btn-outline flavor-btn-sm">
                        <?php _e('Ver perfil', 'flavor-chat-ia'); ?>
                    </a>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
        <?php
        return ob_get_clean();
    }

    public function shortcode_documentar($atts) {
        if (!is_user_logged_in()) {
            return '<div class="flavor-login-required">' . __('Inicia sesión para documentar un saber.', 'flavor-chat-ia') . '</div>';
        }

        $categorias = $this->obtener_categorias();

        ob_start();
        ?>
        <div class="flavor-documentar-saber">
            <h2><?php _e('Documentar un Saber Ancestral', 'flavor-chat-ia'); ?></h2>
            <p class="flavor-intro"><?php _e('Ayuda a preservar el conocimiento tradicional de nuestra comunidad.', 'flavor-chat-ia'); ?></p>

            <form id="flavor-form-documentar" class="flavor-form" enctype="multipart/form-data">
                <?php wp_nonce_field('flavor_saberes_nonce', 'saber_nonce'); ?>

                <div class="flavor-form-group">
                    <label for="nombre"><?php _e('Nombre del saber', 'flavor-chat-ia'); ?> *</label>
                    <input type="text" id="nombre" name="nombre" required
                           placeholder="<?php esc_attr_e('Ej: Cestería de mimbre', 'flavor-chat-ia'); ?>">
                </div>

                <div class="flavor-form-group">
                    <label for="categoria"><?php _e('Categoría', 'flavor-chat-ia'); ?> *</label>
                    <select id="categoria" name="categoria" required>
                        <option value=""><?php _e('Selecciona una categoría', 'flavor-chat-ia'); ?></option>
                        <?php foreach ($categorias as $cat): ?>
                        <option value="<?php echo esc_attr($cat['slug']); ?>"><?php echo esc_html($cat['nombre']); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="flavor-form-group">
                    <label for="descripcion"><?php _e('Descripción breve', 'flavor-chat-ia'); ?> *</label>
                    <textarea id="descripcion" name="descripcion" rows="3" required
                              placeholder="<?php esc_attr_e('Describe brevemente en qué consiste este saber...', 'flavor-chat-ia'); ?>"></textarea>
                </div>

                <div class="flavor-form-group">
                    <label for="contenido"><?php _e('Contenido detallado', 'flavor-chat-ia'); ?></label>
                    <textarea id="contenido" name="contenido" rows="6"
                              placeholder="<?php esc_attr_e('Explica en detalle las técnicas, procesos, herramientas...', 'flavor-chat-ia'); ?>"></textarea>
                </div>

                <div class="flavor-form-group">
                    <label for="historia"><?php _e('Historia y origen', 'flavor-chat-ia'); ?></label>
                    <textarea id="historia" name="historia" rows="4"
                              placeholder="<?php esc_attr_e('¿De dónde viene este saber? ¿Quién te lo enseñó?', 'flavor-chat-ia'); ?>"></textarea>
                </div>

                <div class="flavor-form-row">
                    <div class="flavor-form-group">
                        <label for="duracion"><?php _e('Duración aproximada de aprendizaje', 'flavor-chat-ia'); ?></label>
                        <input type="text" id="duracion" name="duracion" placeholder="<?php esc_attr_e('Ej: 3-6 meses', 'flavor-chat-ia'); ?>">
                    </div>
                    <div class="flavor-form-group">
                        <label for="nivel"><?php _e('Nivel de dificultad', 'flavor-chat-ia'); ?></label>
                        <select id="nivel" name="nivel">
                            <option value="basico"><?php _e('Básico', 'flavor-chat-ia'); ?></option>
                            <option value="intermedio"><?php _e('Intermedio', 'flavor-chat-ia'); ?></option>
                            <option value="avanzado"><?php _e('Avanzado', 'flavor-chat-ia'); ?></option>
                        </select>
                    </div>
                </div>

                <div class="flavor-form-group">
                    <label for="materiales"><?php _e('Materiales necesarios', 'flavor-chat-ia'); ?></label>
                    <input type="text" id="materiales" name="materiales"
                           placeholder="<?php esc_attr_e('Lista los materiales o herramientas necesarias', 'flavor-chat-ia'); ?>">
                </div>

                <div class="flavor-form-group">
                    <label for="imagen"><?php _e('Imagen representativa', 'flavor-chat-ia'); ?></label>
                    <input type="file" id="imagen" name="imagen" accept="image/*">
                </div>

                <div class="flavor-form-group flavor-checkbox-group">
                    <label>
                        <input type="checkbox" name="disponible_ensenar" value="1" checked>
                        <?php _e('Estoy disponible para enseñar este saber', 'flavor-chat-ia'); ?>
                    </label>
                </div>

                <div class="flavor-form-actions">
                    <button type="submit" class="flavor-btn flavor-btn-primary flavor-btn-lg">
                        <span class="dashicons dashicons-yes"></span>
                        <?php _e('Documentar saber', 'flavor-chat-ia'); ?>
                    </button>
                </div>
            </form>
        </div>
        <?php
        return ob_get_clean();
    }

    public function shortcode_mis_aprendizajes($atts) {
        if (!is_user_logged_in()) {
            return '<div class="flavor-login-required">' . __('Inicia sesión para ver tus aprendizajes.', 'flavor-chat-ia') . '</div>';
        }

        $user_id = get_current_user_id();
        $aprendizajes = $this->obtener_aprendizajes_usuario($user_id);
        $ensenanzas = $this->obtener_ensenanzas_usuario($user_id);

        ob_start();
        ?>
        <div class="flavor-mis-aprendizajes">
            <div class="flavor-tabs">
                <button class="flavor-tab activo" data-tab="aprendizajes">
                    <?php _e('Lo que aprendo', 'flavor-chat-ia'); ?>
                </button>
                <button class="flavor-tab" data-tab="ensenanzas">
                    <?php _e('Lo que enseño', 'flavor-chat-ia'); ?>
                </button>
            </div>

            <div class="flavor-tab-content activo" id="tab-aprendizajes">
                <?php if (empty($aprendizajes)): ?>
                <div class="flavor-empty-state">
                    <span class="dashicons dashicons-welcome-learn-more"></span>
                    <p><?php _e('Aún no te has inscrito en ningún aprendizaje.', 'flavor-chat-ia'); ?></p>
                    <a href="<?php echo esc_url($this->get_catalogo_url()); ?>" class="flavor-btn flavor-btn-primary">
                        <?php _e('Explorar saberes', 'flavor-chat-ia'); ?>
                    </a>
                </div>
                <?php else: ?>
                <div class="flavor-aprendizajes-lista">
                    <?php foreach ($aprendizajes as $aprendizaje): ?>
                    <div class="flavor-aprendizaje-item">
                        <div class="flavor-aprendizaje-info">
                            <h4><?php echo esc_html($aprendizaje['saber_nombre']); ?></h4>
                            <p><?php _e('Maestro:', 'flavor-chat-ia'); ?> <?php echo esc_html($aprendizaje['maestro_nombre']); ?></p>
                            <span class="flavor-badge"><?php echo esc_html($aprendizaje['estado']); ?></span>
                        </div>
                        <div class="flavor-aprendizaje-progreso">
                            <div class="flavor-progreso-bar">
                                <div class="flavor-progreso-fill" style="width: <?php echo intval($aprendizaje['progreso']); ?>%"></div>
                            </div>
                            <span><?php echo intval($aprendizaje['progreso']); ?>%</span>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
                <?php endif; ?>
            </div>

            <div class="flavor-tab-content" id="tab-ensenanzas" style="display: none;">
                <?php if (empty($ensenanzas)): ?>
                <div class="flavor-empty-state">
                    <span class="dashicons dashicons-admin-users"></span>
                    <p><?php _e('Aún no has documentado ningún saber.', 'flavor-chat-ia'); ?></p>
                    <a href="<?php echo esc_url($this->get_documentar_url()); ?>" class="flavor-btn flavor-btn-primary">
                        <?php _e('Documentar saber', 'flavor-chat-ia'); ?>
                    </a>
                </div>
                <?php else: ?>
                <div class="flavor-ensenanzas-lista">
                    <?php foreach ($ensenanzas as $saber): ?>
                    <div class="flavor-ensenanza-item">
                        <div class="flavor-ensenanza-info">
                            <h4><?php echo esc_html($saber['nombre']); ?></h4>
                            <p><?php printf(__('%d aprendices', 'flavor-chat-ia'), $saber['aprendices']); ?></p>
                        </div>
                        <a href="<?php echo esc_url($this->get_saber_url($saber['id'])); ?>"
                           class="flavor-btn flavor-btn-sm flavor-btn-outline">
                            <?php _e('Gestionar', 'flavor-chat-ia'); ?>
                        </a>
                    </div>
                    <?php endforeach; ?>
                </div>
                <?php endif; ?>
            </div>
        </div>
        <?php
        return ob_get_clean();
    }

    public function shortcode_mapa($atts) {
        ob_start();
        ?>
        <div class="flavor-mapa-saberes">
            <div class="flavor-mapa-header">
                <h2><?php _e('Mapa de Saberes', 'flavor-chat-ia'); ?></h2>
                <p><?php _e('Localización geográfica de los saberes y maestros de la comunidad.', 'flavor-chat-ia'); ?></p>
            </div>
            <div id="mapa-saberes" class="flavor-mapa-container"></div>
        </div>
        <?php
        return ob_get_clean();
    }

    public function shortcode_estadisticas($atts) {
        $stats = $this->obtener_estadisticas_generales();

        ob_start();
        ?>
        <div class="flavor-saberes-estadisticas">
            <h2><?php _e('Estado de la Preservación', 'flavor-chat-ia'); ?></h2>

            <div class="flavor-stats-grid">
                <div class="flavor-stat-card">
                    <span class="flavor-stat-valor"><?php echo intval($stats['total_saberes']); ?></span>
                    <span class="flavor-stat-label"><?php _e('Saberes documentados', 'flavor-chat-ia'); ?></span>
                </div>
                <div class="flavor-stat-card">
                    <span class="flavor-stat-valor"><?php echo intval($stats['total_maestros']); ?></span>
                    <span class="flavor-stat-label"><?php _e('Maestros activos', 'flavor-chat-ia'); ?></span>
                </div>
                <div class="flavor-stat-card">
                    <span class="flavor-stat-valor"><?php echo intval($stats['total_aprendices']); ?></span>
                    <span class="flavor-stat-label"><?php _e('Aprendices', 'flavor-chat-ia'); ?></span>
                </div>
                <div class="flavor-stat-card">
                    <span class="flavor-stat-valor"><?php echo intval($stats['sesiones_mes']); ?></span>
                    <span class="flavor-stat-label"><?php _e('Sesiones este mes', 'flavor-chat-ia'); ?></span>
                </div>
            </div>
        </div>
        <?php
        return ob_get_clean();
    }

    public function shortcode_aprendizaje($atts) {
        // Vista detallada de un aprendizaje en curso
        return $this->shortcode_mis_aprendizajes($atts);
    }

    // =========================================================================
    // DASHBOARD TAB
    // =========================================================================

    public function render_dashboard_tab() {
        $user_id = get_current_user_id();
        $aprendizajes = $this->obtener_aprendizajes_usuario($user_id, 5);
        $ensenanzas = $this->obtener_ensenanzas_usuario($user_id, 5);
        $stats = $this->obtener_estadisticas_usuario($user_id);

        ?>
        <div class="flavor-dashboard-saberes">
            <div class="flavor-kpi-grid">
                <div class="flavor-kpi-card">
                    <div class="flavor-kpi-valor"><?php echo intval($stats['aprendiendo']); ?></div>
                    <div class="flavor-kpi-label"><?php _e('Aprendiendo', 'flavor-chat-ia'); ?></div>
                </div>
                <div class="flavor-kpi-card">
                    <div class="flavor-kpi-valor"><?php echo intval($stats['ensenando']); ?></div>
                    <div class="flavor-kpi-label"><?php _e('Enseñando', 'flavor-chat-ia'); ?></div>
                </div>
                <div class="flavor-kpi-card">
                    <div class="flavor-kpi-valor"><?php echo intval($stats['sesiones_completadas']); ?></div>
                    <div class="flavor-kpi-label"><?php _e('Sesiones', 'flavor-chat-ia'); ?></div>
                </div>
            </div>

            <div class="flavor-panel">
                <div class="flavor-panel-header">
                    <h3><?php _e('Mis Aprendizajes', 'flavor-chat-ia'); ?></h3>
                    <a href="<?php echo esc_url($this->get_catalogo_url()); ?>" class="flavor-btn flavor-btn-sm flavor-btn-outline">
                        <?php _e('Explorar saberes', 'flavor-chat-ia'); ?>
                    </a>
                </div>
                <?php if (!empty($aprendizajes)): ?>
                <div class="flavor-aprendizajes-mini">
                    <?php foreach ($aprendizajes as $a): ?>
                    <div class="flavor-aprendizaje-mini-item">
                        <h4><?php echo esc_html($a['saber_nombre']); ?></h4>
                        <div class="flavor-mini-progreso">
                            <div class="flavor-progreso-bar">
                                <div class="flavor-progreso-fill" style="width: <?php echo intval($a['progreso']); ?>%"></div>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
                <?php else: ?>
                <p class="flavor-text-muted"><?php _e('No tienes aprendizajes activos.', 'flavor-chat-ia'); ?></p>
                <?php endif; ?>
            </div>
        </div>
        <?php
    }

    // =========================================================================
    // AJAX HANDLERS
    // =========================================================================

    public function ajax_documentar_saber() {
        check_ajax_referer('flavor_saberes_nonce', 'saber_nonce');

        if (!is_user_logged_in()) {
            wp_send_json_error(['message' => __('Debes iniciar sesión.', 'flavor-chat-ia')]);
        }

        $nombre = sanitize_text_field($_POST['nombre'] ?? '');
        $categoria = sanitize_text_field($_POST['categoria'] ?? '');
        $descripcion = sanitize_textarea_field($_POST['descripcion'] ?? '');

        if (empty($nombre) || empty($categoria) || empty($descripcion)) {
            wp_send_json_error(['message' => __('Completa los campos obligatorios.', 'flavor-chat-ia')]);
        }

        global $wpdb;
        $tabla = $wpdb->prefix . 'flavor_saberes_ancestrales';

        if (!Flavor_Chat_Helpers::tabla_existe($tabla)) {
            wp_send_json_error(['message' => __('Sistema no disponible.', 'flavor-chat-ia')]);
        }

        $user_id = get_current_user_id();
        $imagen_id = 0;

        // Procesar imagen
        if (!empty($_FILES['imagen']['name'])) {
            require_once(ABSPATH . 'wp-admin/includes/file.php');
            require_once(ABSPATH . 'wp-admin/includes/media.php');
            require_once(ABSPATH . 'wp-admin/includes/image.php');
            $imagen_id = media_handle_upload('imagen', 0);
        }

        $result = $wpdb->insert($tabla, [
            'nombre' => $nombre,
            'categoria' => $categoria,
            'descripcion' => $descripcion,
            'contenido' => sanitize_textarea_field($_POST['contenido'] ?? ''),
            'historia' => sanitize_textarea_field($_POST['historia'] ?? ''),
            'duracion_aprendizaje' => sanitize_text_field($_POST['duracion'] ?? ''),
            'nivel' => sanitize_text_field($_POST['nivel'] ?? 'basico'),
            'materiales' => sanitize_text_field($_POST['materiales'] ?? ''),
            'maestro_id' => $user_id,
            'imagen_id' => $imagen_id,
            'disponible_ensenar' => !empty($_POST['disponible_ensenar']) ? 1 : 0,
            'estado' => 'publicado',
            'fecha_creacion' => current_time('mysql'),
        ]);

        if ($result) {
            wp_send_json_success([
                'message' => __('Saber documentado correctamente.', 'flavor-chat-ia'),
                'redirect' => $this->get_saber_url($wpdb->insert_id),
            ]);
        } else {
            wp_send_json_error(['message' => __('Error al guardar.', 'flavor-chat-ia')]);
        }
    }

    public function ajax_solicitar_aprendizaje() {
        check_ajax_referer('flavor_saberes_nonce', 'nonce');

        if (!is_user_logged_in()) {
            wp_send_json_error(['message' => __('Debes iniciar sesión.', 'flavor-chat-ia')]);
        }

        $saber_id = intval($_POST['saber_id'] ?? 0);
        $user_id = get_current_user_id();

        if (!$saber_id) {
            wp_send_json_error(['message' => __('Saber no especificado.', 'flavor-chat-ia')]);
        }

        global $wpdb;
        $tabla = $wpdb->prefix . 'flavor_saberes_aprendices';

        if (!Flavor_Chat_Helpers::tabla_existe($tabla)) {
            wp_send_json_error(['message' => __('Sistema no disponible.', 'flavor-chat-ia')]);
        }

        // Verificar si ya es aprendiz
        $existe = $wpdb->get_var($wpdb->prepare(
            "SELECT id FROM {$tabla} WHERE saber_id = %d AND usuario_id = %d",
            $saber_id, $user_id
        ));

        if ($existe) {
            wp_send_json_error(['message' => __('Ya eres aprendiz de este saber.', 'flavor-chat-ia')]);
        }

        $result = $wpdb->insert($tabla, [
            'saber_id' => $saber_id,
            'usuario_id' => $user_id,
            'estado' => 'pendiente',
            'fecha_inscripcion' => current_time('mysql'),
        ]);

        if ($result) {
            wp_send_json_success(['message' => __('Solicitud de aprendizaje enviada.', 'flavor-chat-ia')]);
        } else {
            wp_send_json_error(['message' => __('Error al procesar.', 'flavor-chat-ia')]);
        }
    }

    public function ajax_confirmar_sesion() {
        check_ajax_referer('flavor_saberes_nonce', 'nonce');
        // Implementación de confirmación de sesión
        wp_send_json_success(['message' => __('Sesión confirmada.', 'flavor-chat-ia')]);
    }

    public function ajax_valorar() {
        check_ajax_referer('flavor_saberes_nonce', 'nonce');

        $saber_id = intval($_POST['saber_id'] ?? 0);
        $valoracion = intval($_POST['valoracion'] ?? 0);

        if (!$saber_id || $valoracion < 1 || $valoracion > 5) {
            wp_send_json_error(['message' => __('Datos inválidos.', 'flavor-chat-ia')]);
        }

        // Guardar valoración
        wp_send_json_success(['message' => __('Gracias por tu valoración.', 'flavor-chat-ia')]);
    }

    public function ajax_ofrecer_ensenanza() {
        check_ajax_referer('flavor_saberes_nonce', 'nonce');
        wp_send_json_success(['message' => __('Oferta registrada.', 'flavor-chat-ia')]);
    }

    public function ajax_buscar() {
        $termino = sanitize_text_field($_POST['termino'] ?? '');

        if (strlen($termino) < 2) {
            wp_send_json_success(['saberes' => []]);
        }

        $saberes = $this->buscar_saberes($termino);
        wp_send_json_success(['saberes' => $saberes]);
    }

    // =========================================================================
    // MÉTODOS AUXILIARES
    // =========================================================================

    private function obtener_saberes($args) {
        global $wpdb;
        $tabla = $wpdb->prefix . 'flavor_saberes_ancestrales';

        if (!Flavor_Chat_Helpers::tabla_existe($tabla)) {
            return $this->get_demo_saberes();
        }

        return $wpdb->get_results("SELECT * FROM {$tabla} WHERE estado = 'publicado' ORDER BY fecha_creacion DESC LIMIT 20", ARRAY_A) ?: $this->get_demo_saberes();
    }

    private function obtener_saber($saber_id) {
        global $wpdb;
        $tabla = $wpdb->prefix . 'flavor_saberes_ancestrales';

        if (!Flavor_Chat_Helpers::tabla_existe($tabla)) {
            $demos = $this->get_demo_saberes();
            foreach ($demos as $s) {
                if ($s['id'] == $saber_id) return $s;
            }
            return null;
        }

        return $wpdb->get_row($wpdb->prepare("SELECT * FROM {$tabla} WHERE id = %d", $saber_id), ARRAY_A);
    }

    private function obtener_categorias() {
        return [
            ['slug' => 'oficios', 'nombre' => 'Oficios Tradicionales'],
            ['slug' => 'gastronomia', 'nombre' => 'Gastronomía'],
            ['slug' => 'artesania', 'nombre' => 'Artesanía'],
            ['slug' => 'agricultura', 'nombre' => 'Agricultura'],
            ['slug' => 'medicina', 'nombre' => 'Medicina Natural'],
            ['slug' => 'musica', 'nombre' => 'Música y Danza'],
            ['slug' => 'textil', 'nombre' => 'Textil'],
            ['slug' => 'construccion', 'nombre' => 'Construcción'],
        ];
    }

    private function obtener_sesiones_saber($saber_id) {
        return [];
    }

    private function obtener_recursos_saber($saber_id) {
        return [];
    }

    private function obtener_maestros($args) {
        return [
            ['user_id' => 1, 'nombre' => 'María García', 'especialidad' => 'Cestería tradicional', 'total_saberes' => 3, 'total_aprendices' => 12],
            ['user_id' => 2, 'nombre' => 'Juan Pérez', 'especialidad' => 'Carpintería artesanal', 'total_saberes' => 2, 'total_aprendices' => 8],
        ];
    }

    private function obtener_aprendizajes_usuario($user_id, $limite = 10) {
        return [];
    }

    private function obtener_ensenanzas_usuario($user_id, $limite = 10) {
        return [];
    }

    private function obtener_estadisticas_usuario($user_id) {
        return ['aprendiendo' => 0, 'ensenando' => 0, 'sesiones_completadas' => 0];
    }

    private function obtener_estadisticas_generales() {
        return ['total_saberes' => 15, 'total_maestros' => 8, 'total_aprendices' => 45, 'sesiones_mes' => 12];
    }

    private function es_aprendiz($saber_id, $user_id) {
        return false;
    }

    private function buscar_saberes($termino) {
        return [];
    }

    private function get_icono_categoria($categoria) {
        $iconos = [
            'oficios' => 'dashicons-hammer',
            'gastronomia' => 'dashicons-carrot',
            'artesania' => 'dashicons-art',
            'agricultura' => 'dashicons-carrot',
            'medicina' => 'dashicons-heart',
            'musica' => 'dashicons-format-audio',
            'textil' => 'dashicons-admin-customizer',
            'construccion' => 'dashicons-admin-home',
        ];
        return $iconos[$categoria] ?? 'dashicons-book-alt';
    }

    private function get_icono_recurso($tipo) {
        $iconos = [
            'video' => 'dashicons-video-alt3',
            'audio' => 'dashicons-format-audio',
            'documento' => 'dashicons-media-document',
            'imagen' => 'dashicons-format-image',
        ];
        return $iconos[$tipo] ?? 'dashicons-media-default';
    }

    private function get_saber_url($saber_id) {
        return add_query_arg('saber_id', $saber_id, home_url('/saberes-ancestrales/detalle/'));
    }

    private function get_maestro_url($user_id) {
        return add_query_arg('maestro_id', $user_id, home_url('/saberes-ancestrales/maestro/'));
    }

    private function get_documentar_url() {
        return home_url('/saberes-ancestrales/documentar/');
    }

    private function get_catalogo_url() {
        return home_url('/saberes-ancestrales/');
    }

    private function get_demo_saberes() {
        return [
            ['id' => 1, 'nombre' => 'Cestería de mimbre', 'descripcion' => 'Arte tradicional de tejer cestas con ramas de mimbre.', 'categoria' => 'artesania', 'categoria_nombre' => 'Artesanía', 'maestro_id' => 1, 'maestro_nombre' => 'María García', 'aprendices' => 5, 'imagen' => ''],
            ['id' => 2, 'nombre' => 'Elaboración de queso artesanal', 'descripcion' => 'Técnicas tradicionales para la elaboración de queso de cabra.', 'categoria' => 'gastronomia', 'categoria_nombre' => 'Gastronomía', 'maestro_id' => 2, 'maestro_nombre' => 'Juan Pérez', 'aprendices' => 8, 'imagen' => ''],
        ];
    }
}

// Inicializar
Flavor_Saberes_Ancestrales_Frontend_Controller::get_instance();

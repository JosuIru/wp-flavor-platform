<?php
/**
 * Frontend Controller para Trámites Municipales
 *
 * Gestiona la interfaz pública del módulo de trámites administrativos,
 * incluyendo solicitudes, seguimiento, documentación y citas.
 *
 * @package FlavorChatIA
 * @since 1.0.0
 */

if (!defined('ABSPATH')) {
    exit;
}

class Flavor_Tramites_Frontend_Controller {

    private static $instance = null;
    private $module_slug = 'tramites';

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
        add_shortcode('flavor_tramites_catalogo', [$this, 'shortcode_catalogo']);
        add_shortcode('flavor_tramites_detalle', [$this, 'shortcode_detalle']);
        add_shortcode('flavor_tramites_solicitar', [$this, 'shortcode_solicitar']);
        add_shortcode('flavor_tramites_mis_solicitudes', [$this, 'shortcode_mis_solicitudes']);
        add_shortcode('flavor_tramites_seguimiento', [$this, 'shortcode_seguimiento']);
        add_shortcode('flavor_tramites_citas', [$this, 'shortcode_citas']);
        add_shortcode('flavor_tramites_documentos', [$this, 'shortcode_documentos']);
        add_shortcode('flavor_tramites_buscar', [$this, 'shortcode_buscar']);

        // AJAX handlers
        add_action('wp_ajax_flavor_tramites_iniciar', [$this, 'ajax_iniciar_tramite']);
        add_action('wp_ajax_flavor_tramites_subir_documento', [$this, 'ajax_subir_documento']);
        add_action('wp_ajax_flavor_tramites_solicitar_cita', [$this, 'ajax_solicitar_cita']);
        add_action('wp_ajax_flavor_tramites_cancelar', [$this, 'ajax_cancelar_tramite']);
        add_action('wp_ajax_flavor_tramites_consultar_estado', [$this, 'ajax_consultar_estado']);
        add_action('wp_ajax_flavor_tramites_enviar_mensaje', [$this, 'ajax_enviar_mensaje']);
        add_action('wp_ajax_flavor_tramites_obtener_horarios', [$this, 'ajax_obtener_horarios']);
        add_action('wp_ajax_nopriv_flavor_tramites_buscar', [$this, 'ajax_buscar_tramites']);
        add_action('wp_ajax_flavor_tramites_buscar', [$this, 'ajax_buscar_tramites']);

        // Assets
        add_action('wp_enqueue_scripts', [$this, 'enqueue_assets']);

        // Dashboard tabs
        add_filter('flavor_user_dashboard_tabs', [$this, 'register_dashboard_tabs'], 10, 1);
    }

    public function enqueue_assets() {
        if ($this->is_tramites_page()) {
            $base_url = plugins_url('', dirname(__FILE__));
            $version = defined('FLAVOR_VERSION') ? FLAVOR_VERSION : '1.0.0';

            wp_enqueue_style(
                'flavor-tramites-frontend',
                $base_url . '/assets/css/tramites-frontend.css',
                [],
                $version
            );

            wp_enqueue_script(
                'flavor-tramites-frontend',
                $base_url . '/assets/js/tramites-frontend.js',
                ['jquery'],
                $version,
                true
            );

            wp_localize_script('flavor-tramites-frontend', 'flavorTramitesConfig', [
                'ajaxUrl' => admin_url('admin-ajax.php'),
                'nonce' => wp_create_nonce('flavor_tramites_nonce'),
                'strings' => [
                    'procesando' => __('Procesando...', 'flavor-chat-ia'),
                    'error' => __('Ha ocurrido un error', 'flavor-chat-ia'),
                    'confirmarCancelar' => __('¿Seguro que deseas cancelar este trámite?', 'flavor-chat-ia'),
                    'documentoSubido' => __('Documento subido correctamente', 'flavor-chat-ia'),
                    'citaReservada' => __('Cita reservada correctamente', 'flavor-chat-ia'),
                    'seleccionaHora' => __('Selecciona una hora disponible', 'flavor-chat-ia'),
                ],
            ]);
        }
    }

    private function is_tramites_page() {
        global $post;
        if (!$post) return false;

        return has_shortcode($post->post_content, 'flavor_tramites_catalogo') ||
               has_shortcode($post->post_content, 'flavor_tramites_detalle') ||
               has_shortcode($post->post_content, 'flavor_tramites_solicitar') ||
               has_shortcode($post->post_content, 'flavor_tramites_mis_solicitudes') ||
               has_shortcode($post->post_content, 'flavor_tramites_seguimiento') ||
               has_shortcode($post->post_content, 'flavor_tramites_citas') ||
               has_shortcode($post->post_content, 'flavor_tramites_documentos') ||
               has_shortcode($post->post_content, 'flavor_tramites_buscar');
    }

    public function register_dashboard_tabs($tabs) {
        $tabs['tramites'] = [
            'titulo' => __('Mis Trámites', 'flavor-chat-ia'),
            'icono' => 'dashicons-clipboard',
            'callback' => [$this, 'render_dashboard_tab'],
            'prioridad' => 30,
        ];

        $tabs['tramites-citas'] = [
            'titulo' => __('Mis Citas', 'flavor-chat-ia'),
            'icono' => 'dashicons-calendar-alt',
            'callback' => [$this, 'render_dashboard_citas_tab'],
            'prioridad' => 31,
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
            'mostrar_filtros' => 'si',
        ], $atts);

        $tramites = $this->obtener_catalogo_tramites($atts);
        $categorias = $this->obtener_categorias_tramites();

        ob_start();
        ?>
        <div class="flavor-tramites-catalogo">
            <div class="flavor-tramites-header">
                <h2><?php _e('Catálogo de Trámites', 'flavor-chat-ia'); ?></h2>
                <p class="flavor-tramites-intro">
                    <?php _e('Encuentra el trámite que necesitas realizar y sigue los pasos para completarlo.', 'flavor-chat-ia'); ?>
                </p>
            </div>

            <?php if ($atts['mostrar_filtros'] === 'si'): ?>
            <div class="flavor-tramites-filtros">
                <form class="flavor-filtros-form" method="get">
                    <div class="flavor-filtro-grupo">
                        <input type="text" name="buscar" placeholder="<?php esc_attr_e('Buscar trámite...', 'flavor-chat-ia'); ?>"
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
                    <button type="submit" class="flavor-btn flavor-btn-primary">
                        <span class="dashicons dashicons-search"></span>
                        <?php _e('Buscar', 'flavor-chat-ia'); ?>
                    </button>
                </form>
            </div>
            <?php endif; ?>

            <?php if (!empty($categorias) && empty($atts['categoria'])): ?>
            <div class="flavor-tramites-categorias">
                <?php foreach ($categorias as $cat): ?>
                <a href="?categoria=<?php echo esc_attr($cat['slug']); ?>" class="flavor-categoria-card">
                    <span class="flavor-categoria-icono">
                        <span class="dashicons <?php echo esc_attr($cat['icono'] ?? 'dashicons-category'); ?>"></span>
                    </span>
                    <span class="flavor-categoria-nombre"><?php echo esc_html($cat['nombre']); ?></span>
                    <span class="flavor-categoria-count"><?php echo intval($cat['total']); ?> trámites</span>
                </a>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>

            <div class="flavor-tramites-grid">
                <?php if (empty($tramites)): ?>
                <div class="flavor-empty-state">
                    <span class="dashicons dashicons-clipboard"></span>
                    <p><?php _e('No se encontraron trámites con los criterios seleccionados.', 'flavor-chat-ia'); ?></p>
                </div>
                <?php else: ?>
                    <?php foreach ($tramites as $tramite): ?>
                    <div class="flavor-tramite-card">
                        <div class="flavor-tramite-header">
                            <span class="flavor-tramite-icono">
                                <span class="dashicons <?php echo esc_attr($tramite['icono'] ?? 'dashicons-media-document'); ?>"></span>
                            </span>
                            <?php if ($tramite['online']): ?>
                            <span class="flavor-badge flavor-badge-success">Online</span>
                            <?php endif; ?>
                        </div>
                        <h3 class="flavor-tramite-titulo">
                            <a href="<?php echo esc_url($this->get_tramite_url($tramite['id'])); ?>">
                                <?php echo esc_html($tramite['nombre']); ?>
                            </a>
                        </h3>
                        <p class="flavor-tramite-descripcion">
                            <?php echo esc_html(wp_trim_words($tramite['descripcion'], 20)); ?>
                        </p>
                        <div class="flavor-tramite-meta">
                            <span class="flavor-tramite-tiempo">
                                <span class="dashicons dashicons-clock"></span>
                                <?php echo esc_html($tramite['tiempo_estimado']); ?>
                            </span>
                            <?php if ($tramite['precio'] > 0): ?>
                            <span class="flavor-tramite-precio">
                                <?php echo esc_html(number_format($tramite['precio'], 2)); ?> €
                            </span>
                            <?php else: ?>
                            <span class="flavor-tramite-precio flavor-gratuito">Gratuito</span>
                            <?php endif; ?>
                        </div>
                        <a href="<?php echo esc_url($this->get_tramite_url($tramite['id'])); ?>"
                           class="flavor-btn flavor-btn-outline flavor-btn-block">
                            <?php _e('Ver detalles', 'flavor-chat-ia'); ?>
                        </a>
                    </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>
        <?php
        return ob_get_clean();
    }

    public function shortcode_detalle($atts) {
        $atts = shortcode_atts([
            'id' => 0,
        ], $atts);

        $tramite_id = intval($atts['id']) ?: intval($_GET['tramite_id'] ?? 0);

        if (!$tramite_id) {
            return '<div class="flavor-error">' . __('Trámite no especificado.', 'flavor-chat-ia') . '</div>';
        }

        $tramite = $this->obtener_tramite($tramite_id);

        if (!$tramite) {
            return '<div class="flavor-error">' . __('Trámite no encontrado.', 'flavor-chat-ia') . '</div>';
        }

        $documentos_requeridos = $this->obtener_documentos_requeridos($tramite_id);
        $pasos = $this->obtener_pasos_tramite($tramite_id);

        ob_start();
        ?>
        <div class="flavor-tramite-detalle">
            <div class="flavor-tramite-hero">
                <div class="flavor-tramite-hero-content">
                    <nav class="flavor-breadcrumb">
                        <a href="<?php echo esc_url($this->get_catalogo_url()); ?>"><?php _e('Trámites', 'flavor-chat-ia'); ?></a>
                        <span class="separator">›</span>
                        <span><?php echo esc_html($tramite['categoria_nombre'] ?? ''); ?></span>
                    </nav>
                    <h1><?php echo esc_html($tramite['nombre']); ?></h1>
                    <p class="flavor-tramite-descripcion-larga">
                        <?php echo wp_kses_post($tramite['descripcion']); ?>
                    </p>
                    <div class="flavor-tramite-badges">
                        <?php if ($tramite['online']): ?>
                        <span class="flavor-badge flavor-badge-success">
                            <span class="dashicons dashicons-laptop"></span> Disponible online
                        </span>
                        <?php endif; ?>
                        <?php if ($tramite['cita_previa']): ?>
                        <span class="flavor-badge flavor-badge-info">
                            <span class="dashicons dashicons-calendar-alt"></span> Requiere cita previa
                        </span>
                        <?php endif; ?>
                    </div>
                </div>
                <div class="flavor-tramite-hero-aside">
                    <div class="flavor-tramite-resumen-card">
                        <div class="flavor-resumen-item">
                            <span class="flavor-resumen-label"><?php _e('Tiempo estimado', 'flavor-chat-ia'); ?></span>
                            <span class="flavor-resumen-valor"><?php echo esc_html($tramite['tiempo_estimado']); ?></span>
                        </div>
                        <div class="flavor-resumen-item">
                            <span class="flavor-resumen-label"><?php _e('Precio', 'flavor-chat-ia'); ?></span>
                            <span class="flavor-resumen-valor">
                                <?php echo $tramite['precio'] > 0 ? esc_html(number_format($tramite['precio'], 2)) . ' €' : 'Gratuito'; ?>
                            </span>
                        </div>
                        <div class="flavor-resumen-item">
                            <span class="flavor-resumen-label"><?php _e('Documentos', 'flavor-chat-ia'); ?></span>
                            <span class="flavor-resumen-valor"><?php echo count($documentos_requeridos); ?> requeridos</span>
                        </div>
                        <?php if (is_user_logged_in()): ?>
                        <a href="<?php echo esc_url($this->get_solicitar_url($tramite_id)); ?>"
                           class="flavor-btn flavor-btn-primary flavor-btn-block flavor-btn-lg">
                            <?php _e('Iniciar trámite', 'flavor-chat-ia'); ?>
                        </a>
                        <?php else: ?>
                        <a href="<?php echo wp_login_url(get_permalink()); ?>"
                           class="flavor-btn flavor-btn-primary flavor-btn-block flavor-btn-lg">
                            <?php _e('Inicia sesión para tramitar', 'flavor-chat-ia'); ?>
                        </a>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <div class="flavor-tramite-contenido">
                <div class="flavor-tramite-main">
                    <?php if (!empty($pasos)): ?>
                    <section class="flavor-tramite-section">
                        <h2><?php _e('Pasos del trámite', 'flavor-chat-ia'); ?></h2>
                        <div class="flavor-pasos-lista">
                            <?php foreach ($pasos as $index => $paso): ?>
                            <div class="flavor-paso-item">
                                <div class="flavor-paso-numero"><?php echo $index + 1; ?></div>
                                <div class="flavor-paso-contenido">
                                    <h4><?php echo esc_html($paso['titulo']); ?></h4>
                                    <p><?php echo esc_html($paso['descripcion']); ?></p>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        </div>
                    </section>
                    <?php endif; ?>

                    <?php if (!empty($documentos_requeridos)): ?>
                    <section class="flavor-tramite-section">
                        <h2><?php _e('Documentación necesaria', 'flavor-chat-ia'); ?></h2>
                        <div class="flavor-documentos-lista">
                            <?php foreach ($documentos_requeridos as $doc): ?>
                            <div class="flavor-documento-item">
                                <span class="flavor-documento-icono">
                                    <span class="dashicons dashicons-media-default"></span>
                                </span>
                                <div class="flavor-documento-info">
                                    <span class="flavor-documento-nombre"><?php echo esc_html($doc['nombre']); ?></span>
                                    <?php if (!empty($doc['descripcion'])): ?>
                                    <span class="flavor-documento-desc"><?php echo esc_html($doc['descripcion']); ?></span>
                                    <?php endif; ?>
                                </div>
                                <?php if ($doc['obligatorio']): ?>
                                <span class="flavor-badge flavor-badge-danger">Obligatorio</span>
                                <?php else: ?>
                                <span class="flavor-badge flavor-badge-muted">Opcional</span>
                                <?php endif; ?>
                            </div>
                            <?php endforeach; ?>
                        </div>
                    </section>
                    <?php endif; ?>

                    <?php if (!empty($tramite['normativa'])): ?>
                    <section class="flavor-tramite-section">
                        <h2><?php _e('Normativa aplicable', 'flavor-chat-ia'); ?></h2>
                        <div class="flavor-normativa">
                            <?php echo wp_kses_post($tramite['normativa']); ?>
                        </div>
                    </section>
                    <?php endif; ?>
                </div>

                <div class="flavor-tramite-sidebar">
                    <div class="flavor-sidebar-card">
                        <h3><?php _e('¿Necesitas ayuda?', 'flavor-chat-ia'); ?></h3>
                        <p><?php _e('Si tienes dudas sobre este trámite, contacta con nosotros.', 'flavor-chat-ia'); ?></p>
                        <div class="flavor-contacto-opciones">
                            <?php if (!empty($tramite['telefono'])): ?>
                            <a href="tel:<?php echo esc_attr($tramite['telefono']); ?>" class="flavor-contacto-item">
                                <span class="dashicons dashicons-phone"></span>
                                <?php echo esc_html($tramite['telefono']); ?>
                            </a>
                            <?php endif; ?>
                            <?php if (!empty($tramite['email'])): ?>
                            <a href="mailto:<?php echo esc_attr($tramite['email']); ?>" class="flavor-contacto-item">
                                <span class="dashicons dashicons-email"></span>
                                <?php echo esc_html($tramite['email']); ?>
                            </a>
                            <?php endif; ?>
                        </div>
                    </div>

                    <?php if ($tramite['cita_previa']): ?>
                    <div class="flavor-sidebar-card">
                        <h3><?php _e('Cita previa', 'flavor-chat-ia'); ?></h3>
                        <p><?php _e('Este trámite requiere cita previa para atención presencial.', 'flavor-chat-ia'); ?></p>
                        <a href="<?php echo esc_url($this->get_citas_url($tramite_id)); ?>"
                           class="flavor-btn flavor-btn-outline flavor-btn-block">
                            <?php _e('Solicitar cita', 'flavor-chat-ia'); ?>
                        </a>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        <?php
        return ob_get_clean();
    }

    public function shortcode_solicitar($atts) {
        if (!is_user_logged_in()) {
            return '<div class="flavor-login-required">' .
                   __('Debes iniciar sesión para realizar un trámite.', 'flavor-chat-ia') .
                   ' <a href="' . wp_login_url(get_permalink()) . '">' . __('Iniciar sesión', 'flavor-chat-ia') . '</a></div>';
        }

        $atts = shortcode_atts([
            'id' => 0,
        ], $atts);

        $tramite_id = intval($atts['id']) ?: intval($_GET['tramite_id'] ?? 0);

        if (!$tramite_id) {
            return '<div class="flavor-error">' . __('Trámite no especificado.', 'flavor-chat-ia') . '</div>';
        }

        $tramite = $this->obtener_tramite($tramite_id);

        if (!$tramite) {
            return '<div class="flavor-error">' . __('Trámite no encontrado.', 'flavor-chat-ia') . '</div>';
        }

        $documentos_requeridos = $this->obtener_documentos_requeridos($tramite_id);
        $user_id = get_current_user_id();

        ob_start();
        ?>
        <div class="flavor-solicitar-tramite">
            <div class="flavor-solicitar-header">
                <nav class="flavor-breadcrumb">
                    <a href="<?php echo esc_url($this->get_catalogo_url()); ?>"><?php _e('Trámites', 'flavor-chat-ia'); ?></a>
                    <span class="separator">›</span>
                    <a href="<?php echo esc_url($this->get_tramite_url($tramite_id)); ?>"><?php echo esc_html($tramite['nombre']); ?></a>
                    <span class="separator">›</span>
                    <span><?php _e('Solicitar', 'flavor-chat-ia'); ?></span>
                </nav>
                <h1><?php printf(__('Solicitar: %s', 'flavor-chat-ia'), esc_html($tramite['nombre'])); ?></h1>
            </div>

            <div class="flavor-solicitar-pasos">
                <div class="flavor-paso-indicador activo" data-paso="1">
                    <span class="flavor-paso-num">1</span>
                    <span class="flavor-paso-texto"><?php _e('Datos', 'flavor-chat-ia'); ?></span>
                </div>
                <div class="flavor-paso-indicador" data-paso="2">
                    <span class="flavor-paso-num">2</span>
                    <span class="flavor-paso-texto"><?php _e('Documentos', 'flavor-chat-ia'); ?></span>
                </div>
                <div class="flavor-paso-indicador" data-paso="3">
                    <span class="flavor-paso-num">3</span>
                    <span class="flavor-paso-texto"><?php _e('Confirmar', 'flavor-chat-ia'); ?></span>
                </div>
            </div>

            <form id="flavor-form-tramite" class="flavor-form" enctype="multipart/form-data">
                <?php wp_nonce_field('flavor_tramites_nonce', 'tramite_nonce'); ?>
                <input type="hidden" name="tramite_id" value="<?php echo esc_attr($tramite_id); ?>">

                <!-- Paso 1: Datos del solicitante -->
                <div class="flavor-form-paso" data-paso="1">
                    <h2><?php _e('Datos del solicitante', 'flavor-chat-ia'); ?></h2>

                    <div class="flavor-form-row">
                        <div class="flavor-form-group">
                            <label for="nombre_completo"><?php _e('Nombre completo', 'flavor-chat-ia'); ?> *</label>
                            <input type="text" id="nombre_completo" name="nombre_completo" required
                                   value="<?php echo esc_attr(wp_get_current_user()->display_name); ?>">
                        </div>
                        <div class="flavor-form-group">
                            <label for="dni"><?php _e('DNI/NIE', 'flavor-chat-ia'); ?> *</label>
                            <input type="text" id="dni" name="dni" required
                                   pattern="[0-9]{8}[A-Za-z]|[XYZ][0-9]{7}[A-Za-z]">
                        </div>
                    </div>

                    <div class="flavor-form-row">
                        <div class="flavor-form-group">
                            <label for="email"><?php _e('Email', 'flavor-chat-ia'); ?> *</label>
                            <input type="email" id="email" name="email" required
                                   value="<?php echo esc_attr(wp_get_current_user()->user_email); ?>">
                        </div>
                        <div class="flavor-form-group">
                            <label for="telefono"><?php _e('Teléfono', 'flavor-chat-ia'); ?> *</label>
                            <input type="tel" id="telefono" name="telefono" required>
                        </div>
                    </div>

                    <div class="flavor-form-group">
                        <label for="direccion"><?php _e('Dirección', 'flavor-chat-ia'); ?> *</label>
                        <input type="text" id="direccion" name="direccion" required>
                    </div>

                    <div class="flavor-form-group">
                        <label for="motivo"><?php _e('Motivo de la solicitud', 'flavor-chat-ia'); ?></label>
                        <textarea id="motivo" name="motivo" rows="4"
                                  placeholder="<?php esc_attr_e('Explica brevemente el motivo de tu solicitud...', 'flavor-chat-ia'); ?>"></textarea>
                    </div>

                    <div class="flavor-form-actions">
                        <button type="button" class="flavor-btn flavor-btn-primary flavor-btn-siguiente" data-siguiente="2">
                            <?php _e('Siguiente', 'flavor-chat-ia'); ?>
                            <span class="dashicons dashicons-arrow-right-alt"></span>
                        </button>
                    </div>
                </div>

                <!-- Paso 2: Documentos -->
                <div class="flavor-form-paso" data-paso="2" style="display: none;">
                    <h2><?php _e('Documentación', 'flavor-chat-ia'); ?></h2>

                    <?php if (!empty($documentos_requeridos)): ?>
                    <div class="flavor-documentos-upload">
                        <?php foreach ($documentos_requeridos as $doc): ?>
                        <div class="flavor-documento-upload-item" data-documento-id="<?php echo esc_attr($doc['id']); ?>">
                            <div class="flavor-documento-upload-info">
                                <span class="flavor-documento-nombre"><?php echo esc_html($doc['nombre']); ?></span>
                                <?php if ($doc['obligatorio']): ?>
                                <span class="flavor-badge flavor-badge-danger">Obligatorio</span>
                                <?php endif; ?>
                                <?php if (!empty($doc['descripcion'])): ?>
                                <p class="flavor-documento-desc"><?php echo esc_html($doc['descripcion']); ?></p>
                                <?php endif; ?>
                            </div>
                            <div class="flavor-documento-upload-zona">
                                <input type="file" name="documento_<?php echo esc_attr($doc['id']); ?>"
                                       id="doc_<?php echo esc_attr($doc['id']); ?>"
                                       <?php echo $doc['obligatorio'] ? 'required' : ''; ?>
                                       accept=".pdf,.jpg,.jpeg,.png,.doc,.docx">
                                <label for="doc_<?php echo esc_attr($doc['id']); ?>" class="flavor-upload-label">
                                    <span class="dashicons dashicons-upload"></span>
                                    <span><?php _e('Seleccionar archivo', 'flavor-chat-ia'); ?></span>
                                </label>
                                <span class="flavor-archivo-seleccionado"></span>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                    <?php else: ?>
                    <div class="flavor-info-box">
                        <span class="dashicons dashicons-info"></span>
                        <p><?php _e('Este trámite no requiere documentación adicional.', 'flavor-chat-ia'); ?></p>
                    </div>
                    <?php endif; ?>

                    <div class="flavor-form-actions">
                        <button type="button" class="flavor-btn flavor-btn-outline flavor-btn-anterior" data-anterior="1">
                            <span class="dashicons dashicons-arrow-left-alt"></span>
                            <?php _e('Anterior', 'flavor-chat-ia'); ?>
                        </button>
                        <button type="button" class="flavor-btn flavor-btn-primary flavor-btn-siguiente" data-siguiente="3">
                            <?php _e('Siguiente', 'flavor-chat-ia'); ?>
                            <span class="dashicons dashicons-arrow-right-alt"></span>
                        </button>
                    </div>
                </div>

                <!-- Paso 3: Confirmar -->
                <div class="flavor-form-paso" data-paso="3" style="display: none;">
                    <h2><?php _e('Confirmar solicitud', 'flavor-chat-ia'); ?></h2>

                    <div class="flavor-resumen-solicitud">
                        <div class="flavor-resumen-seccion">
                            <h3><?php _e('Trámite', 'flavor-chat-ia'); ?></h3>
                            <p><strong><?php echo esc_html($tramite['nombre']); ?></strong></p>
                            <?php if ($tramite['precio'] > 0): ?>
                            <p><?php _e('Precio:', 'flavor-chat-ia'); ?> <?php echo esc_html(number_format($tramite['precio'], 2)); ?> €</p>
                            <?php endif; ?>
                        </div>

                        <div class="flavor-resumen-seccion">
                            <h3><?php _e('Datos del solicitante', 'flavor-chat-ia'); ?></h3>
                            <div id="resumen-datos"></div>
                        </div>

                        <div class="flavor-resumen-seccion">
                            <h3><?php _e('Documentos adjuntos', 'flavor-chat-ia'); ?></h3>
                            <div id="resumen-documentos"></div>
                        </div>
                    </div>

                    <div class="flavor-form-group flavor-checkbox-group">
                        <label>
                            <input type="checkbox" name="acepto_condiciones" required>
                            <?php _e('He leído y acepto las condiciones del trámite y la política de privacidad.', 'flavor-chat-ia'); ?>
                        </label>
                    </div>

                    <div class="flavor-form-group flavor-checkbox-group">
                        <label>
                            <input type="checkbox" name="declaro_veracidad" required>
                            <?php _e('Declaro que los datos y documentos aportados son veraces.', 'flavor-chat-ia'); ?>
                        </label>
                    </div>

                    <div class="flavor-form-actions">
                        <button type="button" class="flavor-btn flavor-btn-outline flavor-btn-anterior" data-anterior="2">
                            <span class="dashicons dashicons-arrow-left-alt"></span>
                            <?php _e('Anterior', 'flavor-chat-ia'); ?>
                        </button>
                        <button type="submit" class="flavor-btn flavor-btn-primary flavor-btn-lg">
                            <span class="dashicons dashicons-yes"></span>
                            <?php _e('Enviar solicitud', 'flavor-chat-ia'); ?>
                        </button>
                    </div>
                </div>
            </form>
        </div>
        <?php
        return ob_get_clean();
    }

    public function shortcode_mis_solicitudes($atts) {
        if (!is_user_logged_in()) {
            return '<div class="flavor-login-required">' .
                   __('Debes iniciar sesión para ver tus solicitudes.', 'flavor-chat-ia') . '</div>';
        }

        $user_id = get_current_user_id();
        $solicitudes = $this->obtener_solicitudes_usuario($user_id);

        ob_start();
        ?>
        <div class="flavor-mis-solicitudes">
            <div class="flavor-solicitudes-header">
                <h2><?php _e('Mis Solicitudes de Trámites', 'flavor-chat-ia'); ?></h2>
                <a href="<?php echo esc_url($this->get_catalogo_url()); ?>" class="flavor-btn flavor-btn-primary">
                    <span class="dashicons dashicons-plus"></span>
                    <?php _e('Nuevo trámite', 'flavor-chat-ia'); ?>
                </a>
            </div>

            <?php if (empty($solicitudes)): ?>
            <div class="flavor-empty-state">
                <span class="dashicons dashicons-clipboard"></span>
                <h3><?php _e('No tienes solicitudes', 'flavor-chat-ia'); ?></h3>
                <p><?php _e('Aún no has realizado ninguna solicitud de trámite.', 'flavor-chat-ia'); ?></p>
                <a href="<?php echo esc_url($this->get_catalogo_url()); ?>" class="flavor-btn flavor-btn-primary">
                    <?php _e('Ver catálogo de trámites', 'flavor-chat-ia'); ?>
                </a>
            </div>
            <?php else: ?>
            <div class="flavor-solicitudes-tabla">
                <table class="flavor-table">
                    <thead>
                        <tr>
                            <th><?php _e('Nº Expediente', 'flavor-chat-ia'); ?></th>
                            <th><?php _e('Trámite', 'flavor-chat-ia'); ?></th>
                            <th><?php _e('Fecha', 'flavor-chat-ia'); ?></th>
                            <th><?php _e('Estado', 'flavor-chat-ia'); ?></th>
                            <th><?php _e('Acciones', 'flavor-chat-ia'); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($solicitudes as $sol): ?>
                        <tr>
                            <td>
                                <strong><?php echo esc_html($sol['numero_expediente']); ?></strong>
                            </td>
                            <td><?php echo esc_html($sol['tramite_nombre']); ?></td>
                            <td><?php echo esc_html(date_i18n('d/m/Y', strtotime($sol['fecha_solicitud']))); ?></td>
                            <td>
                                <?php echo $this->render_estado_badge($sol['estado']); ?>
                            </td>
                            <td>
                                <a href="<?php echo esc_url($this->get_seguimiento_url($sol['id'])); ?>"
                                   class="flavor-btn flavor-btn-sm flavor-btn-outline">
                                    <?php _e('Ver', 'flavor-chat-ia'); ?>
                                </a>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <?php endif; ?>
        </div>
        <?php
        return ob_get_clean();
    }

    public function shortcode_seguimiento($atts) {
        if (!is_user_logged_in()) {
            return '<div class="flavor-login-required">' . __('Debes iniciar sesión.', 'flavor-chat-ia') . '</div>';
        }

        $atts = shortcode_atts(['id' => 0], $atts);
        $solicitud_id = intval($atts['id']) ?: intval($_GET['solicitud_id'] ?? 0);

        if (!$solicitud_id) {
            return '<div class="flavor-error">' . __('Solicitud no especificada.', 'flavor-chat-ia') . '</div>';
        }

        $user_id = get_current_user_id();
        $solicitud = $this->obtener_solicitud($solicitud_id, $user_id);

        if (!$solicitud) {
            return '<div class="flavor-error">' . __('Solicitud no encontrada.', 'flavor-chat-ia') . '</div>';
        }

        $historial = $this->obtener_historial_solicitud($solicitud_id);
        $documentos = $this->obtener_documentos_solicitud($solicitud_id);
        $mensajes = $this->obtener_mensajes_solicitud($solicitud_id);

        ob_start();
        ?>
        <div class="flavor-seguimiento-tramite">
            <div class="flavor-seguimiento-header">
                <nav class="flavor-breadcrumb">
                    <a href="<?php echo esc_url($this->get_mis_solicitudes_url()); ?>"><?php _e('Mis solicitudes', 'flavor-chat-ia'); ?></a>
                    <span class="separator">›</span>
                    <span><?php echo esc_html($solicitud['numero_expediente']); ?></span>
                </nav>
                <div class="flavor-seguimiento-titulo">
                    <h1><?php echo esc_html($solicitud['tramite_nombre']); ?></h1>
                    <?php echo $this->render_estado_badge($solicitud['estado']); ?>
                </div>
                <p class="flavor-expediente-numero">
                    <?php _e('Expediente:', 'flavor-chat-ia'); ?>
                    <strong><?php echo esc_html($solicitud['numero_expediente']); ?></strong>
                </p>
            </div>

            <div class="flavor-seguimiento-contenido">
                <div class="flavor-seguimiento-main">
                    <!-- Timeline del trámite -->
                    <section class="flavor-panel">
                        <h3><?php _e('Historial del trámite', 'flavor-chat-ia'); ?></h3>
                        <div class="flavor-timeline">
                            <?php foreach ($historial as $evento): ?>
                            <div class="flavor-timeline-item <?php echo $evento['actual'] ? 'activo' : ''; ?>">
                                <div class="flavor-timeline-marker">
                                    <span class="dashicons <?php echo esc_attr($evento['icono']); ?>"></span>
                                </div>
                                <div class="flavor-timeline-contenido">
                                    <span class="flavor-timeline-fecha">
                                        <?php echo esc_html(date_i18n('d M Y, H:i', strtotime($evento['fecha']))); ?>
                                    </span>
                                    <h4><?php echo esc_html($evento['titulo']); ?></h4>
                                    <?php if (!empty($evento['descripcion'])): ?>
                                    <p><?php echo esc_html($evento['descripcion']); ?></p>
                                    <?php endif; ?>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        </div>
                    </section>

                    <!-- Mensajes -->
                    <section class="flavor-panel">
                        <h3><?php _e('Comunicaciones', 'flavor-chat-ia'); ?></h3>
                        <div class="flavor-mensajes-lista">
                            <?php if (empty($mensajes)): ?>
                            <p class="flavor-no-mensajes"><?php _e('No hay mensajes en este expediente.', 'flavor-chat-ia'); ?></p>
                            <?php else: ?>
                                <?php foreach ($mensajes as $msg): ?>
                                <div class="flavor-mensaje-item <?php echo $msg['es_admin'] ? 'admin' : 'usuario'; ?>">
                                    <div class="flavor-mensaje-header">
                                        <strong><?php echo esc_html($msg['autor']); ?></strong>
                                        <span><?php echo esc_html(date_i18n('d/m/Y H:i', strtotime($msg['fecha']))); ?></span>
                                    </div>
                                    <div class="flavor-mensaje-cuerpo">
                                        <?php echo wp_kses_post($msg['mensaje']); ?>
                                    </div>
                                </div>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </div>

                        <?php if ($solicitud['estado'] !== 'finalizado' && $solicitud['estado'] !== 'cancelado'): ?>
                        <form id="flavor-form-mensaje" class="flavor-form-mensaje">
                            <?php wp_nonce_field('flavor_tramites_nonce', 'mensaje_nonce'); ?>
                            <input type="hidden" name="solicitud_id" value="<?php echo esc_attr($solicitud_id); ?>">
                            <div class="flavor-form-group">
                                <textarea name="mensaje" rows="3" required
                                          placeholder="<?php esc_attr_e('Escribe tu mensaje...', 'flavor-chat-ia'); ?>"></textarea>
                            </div>
                            <button type="submit" class="flavor-btn flavor-btn-primary">
                                <?php _e('Enviar mensaje', 'flavor-chat-ia'); ?>
                            </button>
                        </form>
                        <?php endif; ?>
                    </section>
                </div>

                <div class="flavor-seguimiento-sidebar">
                    <!-- Documentos -->
                    <div class="flavor-panel">
                        <h3><?php _e('Documentos', 'flavor-chat-ia'); ?></h3>
                        <?php if (!empty($documentos)): ?>
                        <div class="flavor-documentos-lista-mini">
                            <?php foreach ($documentos as $doc): ?>
                            <a href="<?php echo esc_url($doc['url']); ?>" target="_blank" class="flavor-documento-link">
                                <span class="dashicons dashicons-media-default"></span>
                                <?php echo esc_html($doc['nombre']); ?>
                            </a>
                            <?php endforeach; ?>
                        </div>
                        <?php else: ?>
                        <p><?php _e('No hay documentos adjuntos.', 'flavor-chat-ia'); ?></p>
                        <?php endif; ?>

                        <?php if ($solicitud['estado'] === 'requiere_documentacion'): ?>
                        <div class="flavor-subir-documento">
                            <h4><?php _e('Subir documento', 'flavor-chat-ia'); ?></h4>
                            <form id="flavor-form-subir-doc" enctype="multipart/form-data">
                                <?php wp_nonce_field('flavor_tramites_nonce', 'doc_nonce'); ?>
                                <input type="hidden" name="solicitud_id" value="<?php echo esc_attr($solicitud_id); ?>">
                                <input type="file" name="documento" required accept=".pdf,.jpg,.jpeg,.png,.doc,.docx">
                                <button type="submit" class="flavor-btn flavor-btn-sm flavor-btn-primary">
                                    <?php _e('Subir', 'flavor-chat-ia'); ?>
                                </button>
                            </form>
                        </div>
                        <?php endif; ?>
                    </div>

                    <!-- Acciones -->
                    <?php if ($solicitud['estado'] !== 'finalizado' && $solicitud['estado'] !== 'cancelado'): ?>
                    <div class="flavor-panel">
                        <h3><?php _e('Acciones', 'flavor-chat-ia'); ?></h3>
                        <button type="button" class="flavor-btn flavor-btn-danger flavor-btn-block flavor-cancelar-tramite"
                                data-solicitud-id="<?php echo esc_attr($solicitud_id); ?>">
                            <?php _e('Cancelar trámite', 'flavor-chat-ia'); ?>
                        </button>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        <?php
        return ob_get_clean();
    }

    public function shortcode_citas($atts) {
        if (!is_user_logged_in()) {
            return '<div class="flavor-login-required">' . __('Debes iniciar sesión.', 'flavor-chat-ia') . '</div>';
        }

        $atts = shortcode_atts([
            'tramite_id' => 0,
        ], $atts);

        $tramite_id = intval($atts['tramite_id']) ?: intval($_GET['tramite_id'] ?? 0);
        $user_id = get_current_user_id();
        $citas = $this->obtener_citas_usuario($user_id);

        ob_start();
        ?>
        <div class="flavor-tramites-citas">
            <div class="flavor-citas-header">
                <h2><?php _e('Gestión de Citas', 'flavor-chat-ia'); ?></h2>
            </div>

            <?php if ($tramite_id): ?>
            <div class="flavor-solicitar-cita">
                <h3><?php _e('Solicitar nueva cita', 'flavor-chat-ia'); ?></h3>
                <form id="flavor-form-cita" class="flavor-form">
                    <?php wp_nonce_field('flavor_tramites_nonce', 'cita_nonce'); ?>
                    <input type="hidden" name="tramite_id" value="<?php echo esc_attr($tramite_id); ?>">

                    <div class="flavor-form-row">
                        <div class="flavor-form-group">
                            <label for="fecha_cita"><?php _e('Fecha', 'flavor-chat-ia'); ?> *</label>
                            <input type="date" id="fecha_cita" name="fecha_cita" required
                                   min="<?php echo date('Y-m-d', strtotime('+1 day')); ?>">
                        </div>
                        <div class="flavor-form-group">
                            <label for="hora_cita"><?php _e('Hora', 'flavor-chat-ia'); ?> *</label>
                            <select id="hora_cita" name="hora_cita" required>
                                <option value=""><?php _e('Selecciona fecha primero', 'flavor-chat-ia'); ?></option>
                            </select>
                        </div>
                    </div>

                    <div class="flavor-form-group">
                        <label for="motivo_cita"><?php _e('Motivo', 'flavor-chat-ia'); ?></label>
                        <textarea id="motivo_cita" name="motivo_cita" rows="3"></textarea>
                    </div>

                    <button type="submit" class="flavor-btn flavor-btn-primary">
                        <?php _e('Reservar cita', 'flavor-chat-ia'); ?>
                    </button>
                </form>
            </div>
            <?php endif; ?>

            <div class="flavor-mis-citas">
                <h3><?php _e('Mis citas', 'flavor-chat-ia'); ?></h3>
                <?php if (empty($citas)): ?>
                <div class="flavor-empty-state">
                    <span class="dashicons dashicons-calendar-alt"></span>
                    <p><?php _e('No tienes citas programadas.', 'flavor-chat-ia'); ?></p>
                </div>
                <?php else: ?>
                <div class="flavor-citas-lista">
                    <?php foreach ($citas as $cita): ?>
                    <div class="flavor-cita-card <?php echo $cita['pasada'] ? 'pasada' : ''; ?>">
                        <div class="flavor-cita-fecha">
                            <span class="flavor-cita-dia"><?php echo esc_html(date_i18n('d', strtotime($cita['fecha']))); ?></span>
                            <span class="flavor-cita-mes"><?php echo esc_html(date_i18n('M', strtotime($cita['fecha']))); ?></span>
                        </div>
                        <div class="flavor-cita-info">
                            <h4><?php echo esc_html($cita['tramite_nombre']); ?></h4>
                            <p>
                                <span class="dashicons dashicons-clock"></span>
                                <?php echo esc_html($cita['hora']); ?>
                            </p>
                            <?php if (!empty($cita['ubicacion'])): ?>
                            <p>
                                <span class="dashicons dashicons-location"></span>
                                <?php echo esc_html($cita['ubicacion']); ?>
                            </p>
                            <?php endif; ?>
                        </div>
                        <div class="flavor-cita-estado">
                            <?php echo $this->render_estado_cita_badge($cita['estado']); ?>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
                <?php endif; ?>
            </div>
        </div>
        <?php
        return ob_get_clean();
    }

    public function shortcode_documentos($atts) {
        $atts = shortcode_atts([
            'categoria' => '',
        ], $atts);

        $documentos = $this->obtener_documentos_publicos($atts['categoria']);
        $categorias = $this->obtener_categorias_documentos();

        ob_start();
        ?>
        <div class="flavor-documentos-publicos">
            <div class="flavor-documentos-header">
                <h2><?php _e('Documentos y Formularios', 'flavor-chat-ia'); ?></h2>
                <p><?php _e('Descarga los formularios y documentos necesarios para tus trámites.', 'flavor-chat-ia'); ?></p>
            </div>

            <div class="flavor-documentos-filtros">
                <select id="filtro-categoria-docs">
                    <option value=""><?php _e('Todas las categorías', 'flavor-chat-ia'); ?></option>
                    <?php foreach ($categorias as $cat): ?>
                    <option value="<?php echo esc_attr($cat['slug']); ?>"><?php echo esc_html($cat['nombre']); ?></option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="flavor-documentos-grid">
                <?php foreach ($documentos as $doc): ?>
                <div class="flavor-documento-card" data-categoria="<?php echo esc_attr($doc['categoria']); ?>">
                    <div class="flavor-documento-icono">
                        <span class="dashicons <?php echo $this->get_icono_tipo_archivo($doc['tipo']); ?>"></span>
                    </div>
                    <div class="flavor-documento-info">
                        <h4><?php echo esc_html($doc['nombre']); ?></h4>
                        <p class="flavor-documento-meta">
                            <?php echo esc_html(strtoupper($doc['tipo'])); ?> • <?php echo esc_html($doc['tamanio']); ?>
                        </p>
                    </div>
                    <a href="<?php echo esc_url($doc['url']); ?>" download class="flavor-btn flavor-btn-sm flavor-btn-outline">
                        <span class="dashicons dashicons-download"></span>
                    </a>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
        <?php
        return ob_get_clean();
    }

    public function shortcode_buscar($atts) {
        ob_start();
        ?>
        <div class="flavor-buscar-tramites">
            <form class="flavor-buscar-form" action="" method="get">
                <div class="flavor-buscar-input-grupo">
                    <input type="text" name="q" placeholder="<?php esc_attr_e('¿Qué trámite necesitas realizar?', 'flavor-chat-ia'); ?>"
                           value="<?php echo esc_attr($_GET['q'] ?? ''); ?>">
                    <button type="submit" class="flavor-btn flavor-btn-primary">
                        <span class="dashicons dashicons-search"></span>
                        <?php _e('Buscar', 'flavor-chat-ia'); ?>
                    </button>
                </div>
            </form>

            <?php if (!empty($_GET['q'])): ?>
            <div class="flavor-resultados-busqueda" id="resultados-tramites">
                <?php
                $resultados = $this->buscar_tramites($_GET['q']);
                if (empty($resultados)):
                ?>
                <div class="flavor-no-resultados">
                    <p><?php _e('No se encontraron trámites con ese criterio de búsqueda.', 'flavor-chat-ia'); ?></p>
                </div>
                <?php else: ?>
                    <?php foreach ($resultados as $tramite): ?>
                    <a href="<?php echo esc_url($this->get_tramite_url($tramite['id'])); ?>" class="flavor-resultado-item">
                        <span class="dashicons <?php echo esc_attr($tramite['icono'] ?? 'dashicons-clipboard'); ?>"></span>
                        <div>
                            <strong><?php echo esc_html($tramite['nombre']); ?></strong>
                            <span><?php echo esc_html(wp_trim_words($tramite['descripcion'], 15)); ?></span>
                        </div>
                    </a>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
            <?php endif; ?>
        </div>
        <?php
        return ob_get_clean();
    }

    // =========================================================================
    // DASHBOARD TABS
    // =========================================================================

    public function render_dashboard_tab() {
        $user_id = get_current_user_id();
        $solicitudes = $this->obtener_solicitudes_usuario($user_id, 10);
        $stats = $this->obtener_estadisticas_usuario($user_id);

        ?>
        <div class="flavor-dashboard-tramites">
            <div class="flavor-kpi-grid">
                <div class="flavor-kpi-card">
                    <div class="flavor-kpi-valor"><?php echo intval($stats['total']); ?></div>
                    <div class="flavor-kpi-label"><?php _e('Trámites totales', 'flavor-chat-ia'); ?></div>
                </div>
                <div class="flavor-kpi-card">
                    <div class="flavor-kpi-valor"><?php echo intval($stats['en_proceso']); ?></div>
                    <div class="flavor-kpi-label"><?php _e('En proceso', 'flavor-chat-ia'); ?></div>
                </div>
                <div class="flavor-kpi-card">
                    <div class="flavor-kpi-valor"><?php echo intval($stats['finalizados']); ?></div>
                    <div class="flavor-kpi-label"><?php _e('Finalizados', 'flavor-chat-ia'); ?></div>
                </div>
                <div class="flavor-kpi-card">
                    <div class="flavor-kpi-valor"><?php echo intval($stats['pendiente_doc']); ?></div>
                    <div class="flavor-kpi-label"><?php _e('Requieren documentación', 'flavor-chat-ia'); ?></div>
                </div>
            </div>

            <div class="flavor-panel">
                <div class="flavor-panel-header">
                    <h3><?php _e('Mis últimos trámites', 'flavor-chat-ia'); ?></h3>
                    <a href="<?php echo esc_url($this->get_catalogo_url()); ?>" class="flavor-btn flavor-btn-sm flavor-btn-primary">
                        <?php _e('Nuevo trámite', 'flavor-chat-ia'); ?>
                    </a>
                </div>

                <?php if (empty($solicitudes)): ?>
                <div class="flavor-empty-state">
                    <p><?php _e('No has realizado ningún trámite aún.', 'flavor-chat-ia'); ?></p>
                </div>
                <?php else: ?>
                <table class="flavor-table">
                    <thead>
                        <tr>
                            <th><?php _e('Expediente', 'flavor-chat-ia'); ?></th>
                            <th><?php _e('Trámite', 'flavor-chat-ia'); ?></th>
                            <th><?php _e('Estado', 'flavor-chat-ia'); ?></th>
                            <th><?php _e('Fecha', 'flavor-chat-ia'); ?></th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($solicitudes as $sol): ?>
                        <tr>
                            <td><strong><?php echo esc_html($sol['numero_expediente']); ?></strong></td>
                            <td><?php echo esc_html($sol['tramite_nombre']); ?></td>
                            <td><?php echo $this->render_estado_badge($sol['estado']); ?></td>
                            <td><?php echo esc_html(date_i18n('d/m/Y', strtotime($sol['fecha_solicitud']))); ?></td>
                            <td>
                                <a href="<?php echo esc_url($this->get_seguimiento_url($sol['id'])); ?>"
                                   class="flavor-btn flavor-btn-sm flavor-btn-outline">
                                    <?php _e('Ver', 'flavor-chat-ia'); ?>
                                </a>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
                <?php endif; ?>
            </div>
        </div>
        <?php
    }

    public function render_dashboard_citas_tab() {
        $user_id = get_current_user_id();
        $citas = $this->obtener_citas_usuario($user_id);

        ?>
        <div class="flavor-dashboard-citas">
            <div class="flavor-panel">
                <div class="flavor-panel-header">
                    <h3><?php _e('Mis citas programadas', 'flavor-chat-ia'); ?></h3>
                </div>

                <?php if (empty($citas)): ?>
                <div class="flavor-empty-state">
                    <span class="dashicons dashicons-calendar-alt"></span>
                    <p><?php _e('No tienes citas programadas.', 'flavor-chat-ia'); ?></p>
                </div>
                <?php else: ?>
                <div class="flavor-citas-lista">
                    <?php foreach ($citas as $cita): ?>
                    <div class="flavor-cita-item <?php echo $cita['pasada'] ? 'pasada' : ''; ?>">
                        <div class="flavor-cita-fecha-box">
                            <span class="flavor-dia"><?php echo esc_html(date_i18n('d', strtotime($cita['fecha']))); ?></span>
                            <span class="flavor-mes"><?php echo esc_html(date_i18n('M', strtotime($cita['fecha']))); ?></span>
                        </div>
                        <div class="flavor-cita-detalles">
                            <h4><?php echo esc_html($cita['tramite_nombre']); ?></h4>
                            <p><span class="dashicons dashicons-clock"></span> <?php echo esc_html($cita['hora']); ?></p>
                            <?php if (!empty($cita['ubicacion'])): ?>
                            <p><span class="dashicons dashicons-location"></span> <?php echo esc_html($cita['ubicacion']); ?></p>
                            <?php endif; ?>
                        </div>
                        <?php echo $this->render_estado_cita_badge($cita['estado']); ?>
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

    public function ajax_iniciar_tramite() {
        check_ajax_referer('flavor_tramites_nonce', 'tramite_nonce');

        if (!is_user_logged_in()) {
            wp_send_json_error(['message' => __('Debes iniciar sesión.', 'flavor-chat-ia')]);
        }

        $tramite_id = intval($_POST['tramite_id'] ?? 0);
        $user_id = get_current_user_id();

        if (!$tramite_id) {
            wp_send_json_error(['message' => __('Trámite no especificado.', 'flavor-chat-ia')]);
        }

        $datos = [
            'nombre_completo' => sanitize_text_field($_POST['nombre_completo'] ?? ''),
            'dni' => sanitize_text_field($_POST['dni'] ?? ''),
            'email' => sanitize_email($_POST['email'] ?? ''),
            'telefono' => sanitize_text_field($_POST['telefono'] ?? ''),
            'direccion' => sanitize_text_field($_POST['direccion'] ?? ''),
            'motivo' => sanitize_textarea_field($_POST['motivo'] ?? ''),
        ];

        // Validaciones
        if (empty($datos['nombre_completo']) || empty($datos['dni']) || empty($datos['email'])) {
            wp_send_json_error(['message' => __('Completa todos los campos obligatorios.', 'flavor-chat-ia')]);
        }

        global $wpdb;
        $tabla = $wpdb->prefix . 'flavor_tramites_solicitudes';

        if (!Flavor_Chat_Helpers::tabla_existe($tabla)) {
            wp_send_json_error(['message' => __('Sistema no disponible temporalmente.', 'flavor-chat-ia')]);
        }

        // Generar número de expediente
        $numero_expediente = 'EXP-' . date('Y') . '-' . str_pad(mt_rand(1, 99999), 5, '0', STR_PAD_LEFT);

        $result = $wpdb->insert($tabla, [
            'tramite_id' => $tramite_id,
            'usuario_id' => $user_id,
            'numero_expediente' => $numero_expediente,
            'datos_solicitante' => json_encode($datos),
            'estado' => 'pendiente',
            'fecha_solicitud' => current_time('mysql'),
        ]);

        if ($result) {
            $solicitud_id = $wpdb->insert_id;

            // Procesar documentos
            $this->procesar_documentos_solicitud($solicitud_id);

            // Registrar en historial
            $this->registrar_historial($solicitud_id, 'solicitud_iniciada', 'Solicitud registrada correctamente');

            wp_send_json_success([
                'message' => __('Solicitud registrada correctamente.', 'flavor-chat-ia'),
                'numero_expediente' => $numero_expediente,
                'redirect' => $this->get_seguimiento_url($solicitud_id),
            ]);
        } else {
            wp_send_json_error(['message' => __('Error al registrar la solicitud.', 'flavor-chat-ia')]);
        }
    }

    public function ajax_subir_documento() {
        check_ajax_referer('flavor_tramites_nonce', 'doc_nonce');

        if (!is_user_logged_in()) {
            wp_send_json_error(['message' => __('Debes iniciar sesión.', 'flavor-chat-ia')]);
        }

        $solicitud_id = intval($_POST['solicitud_id'] ?? 0);
        $user_id = get_current_user_id();

        // Verificar que la solicitud pertenece al usuario
        $solicitud = $this->obtener_solicitud($solicitud_id, $user_id);
        if (!$solicitud) {
            wp_send_json_error(['message' => __('Solicitud no encontrada.', 'flavor-chat-ia')]);
        }

        if (empty($_FILES['documento'])) {
            wp_send_json_error(['message' => __('No se ha seleccionado ningún archivo.', 'flavor-chat-ia')]);
        }

        require_once(ABSPATH . 'wp-admin/includes/file.php');
        require_once(ABSPATH . 'wp-admin/includes/media.php');
        require_once(ABSPATH . 'wp-admin/includes/image.php');

        $attachment_id = media_handle_upload('documento', 0);

        if (is_wp_error($attachment_id)) {
            wp_send_json_error(['message' => $attachment_id->get_error_message()]);
        }

        global $wpdb;
        $tabla = $wpdb->prefix . 'flavor_tramites_documentos';

        $wpdb->insert($tabla, [
            'solicitud_id' => $solicitud_id,
            'attachment_id' => $attachment_id,
            'nombre' => sanitize_file_name($_FILES['documento']['name']),
            'tipo' => 'adjunto',
            'fecha_subida' => current_time('mysql'),
        ]);

        $this->registrar_historial($solicitud_id, 'documento_subido', 'Documento adjuntado: ' . $_FILES['documento']['name']);

        wp_send_json_success(['message' => __('Documento subido correctamente.', 'flavor-chat-ia')]);
    }

    public function ajax_solicitar_cita() {
        check_ajax_referer('flavor_tramites_nonce', 'cita_nonce');

        if (!is_user_logged_in()) {
            wp_send_json_error(['message' => __('Debes iniciar sesión.', 'flavor-chat-ia')]);
        }

        $tramite_id = intval($_POST['tramite_id'] ?? 0);
        $fecha = sanitize_text_field($_POST['fecha_cita'] ?? '');
        $hora = sanitize_text_field($_POST['hora_cita'] ?? '');
        $motivo = sanitize_textarea_field($_POST['motivo_cita'] ?? '');
        $user_id = get_current_user_id();

        if (!$tramite_id || !$fecha || !$hora) {
            wp_send_json_error(['message' => __('Completa todos los campos.', 'flavor-chat-ia')]);
        }

        // Verificar disponibilidad
        if (!$this->verificar_disponibilidad_cita($fecha, $hora)) {
            wp_send_json_error(['message' => __('La hora seleccionada ya no está disponible.', 'flavor-chat-ia')]);
        }

        global $wpdb;
        $tabla = $wpdb->prefix . 'flavor_tramites_citas';

        if (!Flavor_Chat_Helpers::tabla_existe($tabla)) {
            wp_send_json_error(['message' => __('Sistema no disponible.', 'flavor-chat-ia')]);
        }

        $result = $wpdb->insert($tabla, [
            'tramite_id' => $tramite_id,
            'usuario_id' => $user_id,
            'fecha' => $fecha,
            'hora' => $hora,
            'motivo' => $motivo,
            'estado' => 'confirmada',
            'fecha_creacion' => current_time('mysql'),
        ]);

        if ($result) {
            wp_send_json_success(['message' => __('Cita reservada correctamente.', 'flavor-chat-ia')]);
        } else {
            wp_send_json_error(['message' => __('Error al reservar la cita.', 'flavor-chat-ia')]);
        }
    }

    public function ajax_cancelar_tramite() {
        check_ajax_referer('flavor_tramites_nonce', 'nonce');

        if (!is_user_logged_in()) {
            wp_send_json_error(['message' => __('Debes iniciar sesión.', 'flavor-chat-ia')]);
        }

        $solicitud_id = intval($_POST['solicitud_id'] ?? 0);
        $user_id = get_current_user_id();

        $solicitud = $this->obtener_solicitud($solicitud_id, $user_id);
        if (!$solicitud) {
            wp_send_json_error(['message' => __('Solicitud no encontrada.', 'flavor-chat-ia')]);
        }

        if (in_array($solicitud['estado'], ['finalizado', 'cancelado'])) {
            wp_send_json_error(['message' => __('Este trámite no se puede cancelar.', 'flavor-chat-ia')]);
        }

        global $wpdb;
        $tabla = $wpdb->prefix . 'flavor_tramites_solicitudes';

        $wpdb->update(
            $tabla,
            ['estado' => 'cancelado', 'fecha_actualizacion' => current_time('mysql')],
            ['id' => $solicitud_id]
        );

        $this->registrar_historial($solicitud_id, 'cancelado', 'Trámite cancelado por el usuario');

        wp_send_json_success(['message' => __('Trámite cancelado.', 'flavor-chat-ia')]);
    }

    public function ajax_consultar_estado() {
        $numero_expediente = sanitize_text_field($_POST['numero_expediente'] ?? '');

        if (empty($numero_expediente)) {
            wp_send_json_error(['message' => __('Número de expediente requerido.', 'flavor-chat-ia')]);
        }

        global $wpdb;
        $tabla = $wpdb->prefix . 'flavor_tramites_solicitudes';

        $solicitud = $wpdb->get_row($wpdb->prepare(
            "SELECT s.*, t.nombre as tramite_nombre
             FROM {$tabla} s
             LEFT JOIN {$wpdb->prefix}flavor_tramites t ON s.tramite_id = t.id
             WHERE s.numero_expediente = %s",
            $numero_expediente
        ), ARRAY_A);

        if (!$solicitud) {
            wp_send_json_error(['message' => __('Expediente no encontrado.', 'flavor-chat-ia')]);
        }

        wp_send_json_success([
            'numero_expediente' => $solicitud['numero_expediente'],
            'tramite' => $solicitud['tramite_nombre'],
            'estado' => $solicitud['estado'],
            'estado_texto' => $this->get_estado_texto($solicitud['estado']),
            'fecha' => date_i18n('d/m/Y', strtotime($solicitud['fecha_solicitud'])),
        ]);
    }

    public function ajax_enviar_mensaje() {
        check_ajax_referer('flavor_tramites_nonce', 'mensaje_nonce');

        if (!is_user_logged_in()) {
            wp_send_json_error(['message' => __('Debes iniciar sesión.', 'flavor-chat-ia')]);
        }

        $solicitud_id = intval($_POST['solicitud_id'] ?? 0);
        $mensaje = sanitize_textarea_field($_POST['mensaje'] ?? '');
        $user_id = get_current_user_id();

        if (!$solicitud_id || empty($mensaje)) {
            wp_send_json_error(['message' => __('Mensaje requerido.', 'flavor-chat-ia')]);
        }

        $solicitud = $this->obtener_solicitud($solicitud_id, $user_id);
        if (!$solicitud) {
            wp_send_json_error(['message' => __('Solicitud no encontrada.', 'flavor-chat-ia')]);
        }

        global $wpdb;
        $tabla = $wpdb->prefix . 'flavor_tramites_mensajes';

        if (!Flavor_Chat_Helpers::tabla_existe($tabla)) {
            wp_send_json_error(['message' => __('Sistema no disponible.', 'flavor-chat-ia')]);
        }

        $result = $wpdb->insert($tabla, [
            'solicitud_id' => $solicitud_id,
            'usuario_id' => $user_id,
            'mensaje' => $mensaje,
            'es_admin' => 0,
            'fecha' => current_time('mysql'),
        ]);

        if ($result) {
            wp_send_json_success(['message' => __('Mensaje enviado.', 'flavor-chat-ia')]);
        } else {
            wp_send_json_error(['message' => __('Error al enviar el mensaje.', 'flavor-chat-ia')]);
        }
    }

    public function ajax_obtener_horarios() {
        $fecha = sanitize_text_field($_POST['fecha'] ?? '');
        $tramite_id = intval($_POST['tramite_id'] ?? 0);

        if (!$fecha) {
            wp_send_json_error(['message' => __('Fecha requerida.', 'flavor-chat-ia')]);
        }

        $horarios = $this->obtener_horarios_disponibles($fecha, $tramite_id);

        wp_send_json_success(['horarios' => $horarios]);
    }

    public function ajax_buscar_tramites() {
        $termino = sanitize_text_field($_POST['termino'] ?? $_GET['q'] ?? '');

        if (strlen($termino) < 2) {
            wp_send_json_success(['tramites' => []]);
        }

        $resultados = $this->buscar_tramites($termino);

        wp_send_json_success(['tramites' => $resultados]);
    }

    // =========================================================================
    // MÉTODOS AUXILIARES
    // =========================================================================

    private function obtener_catalogo_tramites($args = []) {
        global $wpdb;
        $tabla = $wpdb->prefix . 'flavor_tramites';

        if (!Flavor_Chat_Helpers::tabla_existe($tabla)) {
            return $this->get_demo_tramites();
        }

        $where = "WHERE activo = 1";
        $params = [];

        if (!empty($args['categoria'])) {
            $where .= " AND categoria = %s";
            $params[] = $args['categoria'];
        }

        if (!empty($_GET['buscar'])) {
            $where .= " AND (nombre LIKE %s OR descripcion LIKE %s)";
            $buscar = '%' . $wpdb->esc_like($_GET['buscar']) . '%';
            $params[] = $buscar;
            $params[] = $buscar;
        }

        $limit = intval($args['limite'] ?? 20);

        $query = "SELECT * FROM {$tabla} {$where} ORDER BY nombre ASC LIMIT {$limit}";

        if (!empty($params)) {
            $query = $wpdb->prepare($query, $params);
        }

        $tramites = $wpdb->get_results($query, ARRAY_A);

        return !empty($tramites) ? $tramites : $this->get_demo_tramites();
    }

    private function obtener_tramite($tramite_id) {
        global $wpdb;
        $tabla = $wpdb->prefix . 'flavor_tramites';

        if (!Flavor_Chat_Helpers::tabla_existe($tabla)) {
            $demos = $this->get_demo_tramites();
            foreach ($demos as $t) {
                if ($t['id'] == $tramite_id) return $t;
            }
            return null;
        }

        return $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$tabla} WHERE id = %d",
            $tramite_id
        ), ARRAY_A);
    }

    private function obtener_categorias_tramites() {
        return [
            ['slug' => 'empadronamiento', 'nombre' => 'Empadronamiento', 'icono' => 'dashicons-id', 'total' => 5],
            ['slug' => 'urbanismo', 'nombre' => 'Urbanismo', 'icono' => 'dashicons-admin-home', 'total' => 8],
            ['slug' => 'tributos', 'nombre' => 'Tributos', 'icono' => 'dashicons-money-alt', 'total' => 12],
            ['slug' => 'social', 'nombre' => 'Servicios Sociales', 'icono' => 'dashicons-groups', 'total' => 7],
            ['slug' => 'medioambiente', 'nombre' => 'Medio Ambiente', 'icono' => 'dashicons-palmtree', 'total' => 4],
            ['slug' => 'cultura', 'nombre' => 'Cultura y Deportes', 'icono' => 'dashicons-tickets-alt', 'total' => 6],
        ];
    }

    private function obtener_documentos_requeridos($tramite_id) {
        return [
            ['id' => 1, 'nombre' => 'DNI/NIE del solicitante', 'descripcion' => 'Copia del documento de identidad por ambas caras', 'obligatorio' => true],
            ['id' => 2, 'nombre' => 'Justificante de domicilio', 'descripcion' => 'Factura de servicios o contrato de alquiler', 'obligatorio' => true],
            ['id' => 3, 'nombre' => 'Documentación adicional', 'descripcion' => 'Cualquier documento que considere relevante', 'obligatorio' => false],
        ];
    }

    private function obtener_pasos_tramite($tramite_id) {
        return [
            ['titulo' => 'Reúne la documentación', 'descripcion' => 'Prepara todos los documentos necesarios antes de iniciar el trámite.'],
            ['titulo' => 'Completa el formulario', 'descripcion' => 'Rellena el formulario online con tus datos personales.'],
            ['titulo' => 'Adjunta los documentos', 'descripcion' => 'Sube la documentación requerida en formato PDF o imagen.'],
            ['titulo' => 'Envía la solicitud', 'descripcion' => 'Revisa toda la información y confirma el envío.'],
            ['titulo' => 'Seguimiento', 'descripcion' => 'Podrás consultar el estado de tu trámite en cualquier momento.'],
        ];
    }

    private function obtener_solicitudes_usuario($user_id, $limite = 50) {
        global $wpdb;
        $tabla = $wpdb->prefix . 'flavor_tramites_solicitudes';

        if (!Flavor_Chat_Helpers::tabla_existe($tabla)) {
            return [];
        }

        return $wpdb->get_results($wpdb->prepare(
            "SELECT s.*, t.nombre as tramite_nombre
             FROM {$tabla} s
             LEFT JOIN {$wpdb->prefix}flavor_tramites t ON s.tramite_id = t.id
             WHERE s.usuario_id = %d
             ORDER BY s.fecha_solicitud DESC
             LIMIT %d",
            $user_id, $limite
        ), ARRAY_A);
    }

    private function obtener_solicitud($solicitud_id, $user_id) {
        global $wpdb;
        $tabla = $wpdb->prefix . 'flavor_tramites_solicitudes';

        if (!Flavor_Chat_Helpers::tabla_existe($tabla)) {
            return null;
        }

        return $wpdb->get_row($wpdb->prepare(
            "SELECT s.*, t.nombre as tramite_nombre
             FROM {$tabla} s
             LEFT JOIN {$wpdb->prefix}flavor_tramites t ON s.tramite_id = t.id
             WHERE s.id = %d AND s.usuario_id = %d",
            $solicitud_id, $user_id
        ), ARRAY_A);
    }

    private function obtener_historial_solicitud($solicitud_id) {
        global $wpdb;
        $tabla = $wpdb->prefix . 'flavor_tramites_historial';

        if (!Flavor_Chat_Helpers::tabla_existe($tabla)) {
            return [
                ['fecha' => current_time('mysql'), 'titulo' => 'Solicitud registrada', 'descripcion' => 'Tu solicitud ha sido recibida', 'icono' => 'dashicons-yes', 'actual' => true],
            ];
        }

        return $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM {$tabla} WHERE solicitud_id = %d ORDER BY fecha ASC",
            $solicitud_id
        ), ARRAY_A);
    }

    private function obtener_documentos_solicitud($solicitud_id) {
        global $wpdb;
        $tabla = $wpdb->prefix . 'flavor_tramites_documentos';

        if (!Flavor_Chat_Helpers::tabla_existe($tabla)) {
            return [];
        }

        $docs = $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM {$tabla} WHERE solicitud_id = %d",
            $solicitud_id
        ), ARRAY_A);

        foreach ($docs as &$doc) {
            $doc['url'] = wp_get_attachment_url($doc['attachment_id']);
        }

        return $docs;
    }

    private function obtener_mensajes_solicitud($solicitud_id) {
        global $wpdb;
        $tabla = $wpdb->prefix . 'flavor_tramites_mensajes';

        if (!Flavor_Chat_Helpers::tabla_existe($tabla)) {
            return [];
        }

        $mensajes = $wpdb->get_results($wpdb->prepare(
            "SELECT m.*, u.display_name as autor
             FROM {$tabla} m
             LEFT JOIN {$wpdb->users} u ON m.usuario_id = u.ID
             WHERE m.solicitud_id = %d
             ORDER BY m.fecha ASC",
            $solicitud_id
        ), ARRAY_A);

        return $mensajes;
    }

    private function obtener_citas_usuario($user_id) {
        global $wpdb;
        $tabla = $wpdb->prefix . 'flavor_tramites_citas';

        if (!Flavor_Chat_Helpers::tabla_existe($tabla)) {
            return [];
        }

        $citas = $wpdb->get_results($wpdb->prepare(
            "SELECT c.*, t.nombre as tramite_nombre
             FROM {$tabla} c
             LEFT JOIN {$wpdb->prefix}flavor_tramites t ON c.tramite_id = t.id
             WHERE c.usuario_id = %d
             ORDER BY c.fecha ASC, c.hora ASC",
            $user_id
        ), ARRAY_A);

        $hoy = date('Y-m-d');
        foreach ($citas as &$cita) {
            $cita['pasada'] = $cita['fecha'] < $hoy;
        }

        return $citas;
    }

    private function obtener_estadisticas_usuario($user_id) {
        global $wpdb;
        $tabla = $wpdb->prefix . 'flavor_tramites_solicitudes';

        if (!Flavor_Chat_Helpers::tabla_existe($tabla)) {
            return ['total' => 0, 'en_proceso' => 0, 'finalizados' => 0, 'pendiente_doc' => 0];
        }

        $stats = $wpdb->get_row($wpdb->prepare(
            "SELECT
                COUNT(*) as total,
                SUM(CASE WHEN estado IN ('pendiente', 'en_tramite', 'en_revision') THEN 1 ELSE 0 END) as en_proceso,
                SUM(CASE WHEN estado = 'finalizado' THEN 1 ELSE 0 END) as finalizados,
                SUM(CASE WHEN estado = 'requiere_documentacion' THEN 1 ELSE 0 END) as pendiente_doc
             FROM {$tabla}
             WHERE usuario_id = %d",
            $user_id
        ), ARRAY_A);

        return $stats ?: ['total' => 0, 'en_proceso' => 0, 'finalizados' => 0, 'pendiente_doc' => 0];
    }

    private function obtener_documentos_publicos($categoria = '') {
        return [
            ['nombre' => 'Solicitud de empadronamiento', 'tipo' => 'pdf', 'tamanio' => '125 KB', 'categoria' => 'empadronamiento', 'url' => '#'],
            ['nombre' => 'Declaración responsable', 'tipo' => 'pdf', 'tamanio' => '89 KB', 'categoria' => 'urbanismo', 'url' => '#'],
            ['nombre' => 'Solicitud de bonificación tributaria', 'tipo' => 'pdf', 'tamanio' => '156 KB', 'categoria' => 'tributos', 'url' => '#'],
            ['nombre' => 'Instancia general', 'tipo' => 'doc', 'tamanio' => '45 KB', 'categoria' => 'general', 'url' => '#'],
        ];
    }

    private function obtener_categorias_documentos() {
        return [
            ['slug' => 'general', 'nombre' => 'General'],
            ['slug' => 'empadronamiento', 'nombre' => 'Empadronamiento'],
            ['slug' => 'urbanismo', 'nombre' => 'Urbanismo'],
            ['slug' => 'tributos', 'nombre' => 'Tributos'],
        ];
    }

    private function buscar_tramites($termino) {
        global $wpdb;
        $tabla = $wpdb->prefix . 'flavor_tramites';

        if (!Flavor_Chat_Helpers::tabla_existe($tabla)) {
            $demos = $this->get_demo_tramites();
            return array_filter($demos, function($t) use ($termino) {
                return stripos($t['nombre'], $termino) !== false || stripos($t['descripcion'], $termino) !== false;
            });
        }

        $buscar = '%' . $wpdb->esc_like($termino) . '%';

        return $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM {$tabla} WHERE activo = 1 AND (nombre LIKE %s OR descripcion LIKE %s) LIMIT 10",
            $buscar, $buscar
        ), ARRAY_A);
    }

    private function obtener_horarios_disponibles($fecha, $tramite_id = 0) {
        $horarios_base = ['09:00', '09:30', '10:00', '10:30', '11:00', '11:30', '12:00', '12:30', '16:00', '16:30', '17:00', '17:30'];

        // Simular algunos ocupados
        $ocupados = ['10:00', '11:30'];

        return array_values(array_diff($horarios_base, $ocupados));
    }

    private function verificar_disponibilidad_cita($fecha, $hora) {
        $disponibles = $this->obtener_horarios_disponibles($fecha);
        return in_array($hora, $disponibles);
    }

    private function procesar_documentos_solicitud($solicitud_id) {
        if (empty($_FILES)) return;

        require_once(ABSPATH . 'wp-admin/includes/file.php');
        require_once(ABSPATH . 'wp-admin/includes/media.php');
        require_once(ABSPATH . 'wp-admin/includes/image.php');

        global $wpdb;
        $tabla = $wpdb->prefix . 'flavor_tramites_documentos';

        foreach ($_FILES as $key => $file) {
            if (strpos($key, 'documento_') !== 0 || empty($file['name'])) continue;

            $attachment_id = media_handle_upload($key, 0);

            if (!is_wp_error($attachment_id)) {
                $doc_id = str_replace('documento_', '', $key);

                $wpdb->insert($tabla, [
                    'solicitud_id' => $solicitud_id,
                    'documento_tipo_id' => intval($doc_id),
                    'attachment_id' => $attachment_id,
                    'nombre' => sanitize_file_name($file['name']),
                    'tipo' => 'requerido',
                    'fecha_subida' => current_time('mysql'),
                ]);
            }
        }
    }

    private function registrar_historial($solicitud_id, $tipo, $descripcion) {
        global $wpdb;
        $tabla = $wpdb->prefix . 'flavor_tramites_historial';

        if (!Flavor_Chat_Helpers::tabla_existe($tabla)) return;

        $titulos = [
            'solicitud_iniciada' => 'Solicitud registrada',
            'documento_subido' => 'Documento adjuntado',
            'en_revision' => 'En revisión',
            'requiere_documentacion' => 'Documentación requerida',
            'en_tramite' => 'En tramitación',
            'finalizado' => 'Trámite finalizado',
            'cancelado' => 'Trámite cancelado',
        ];

        $iconos = [
            'solicitud_iniciada' => 'dashicons-yes',
            'documento_subido' => 'dashicons-media-default',
            'en_revision' => 'dashicons-visibility',
            'requiere_documentacion' => 'dashicons-warning',
            'en_tramite' => 'dashicons-admin-tools',
            'finalizado' => 'dashicons-awards',
            'cancelado' => 'dashicons-dismiss',
        ];

        $wpdb->insert($tabla, [
            'solicitud_id' => $solicitud_id,
            'tipo' => $tipo,
            'titulo' => $titulos[$tipo] ?? $tipo,
            'descripcion' => $descripcion,
            'icono' => $iconos[$tipo] ?? 'dashicons-marker',
            'fecha' => current_time('mysql'),
            'actual' => 1,
        ]);

        // Marcar anteriores como no actuales
        $wpdb->query($wpdb->prepare(
            "UPDATE {$tabla} SET actual = 0 WHERE solicitud_id = %d AND id != %d",
            $solicitud_id, $wpdb->insert_id
        ));
    }

    private function render_estado_badge($estado) {
        $clases = [
            'pendiente' => 'warning',
            'en_revision' => 'info',
            'requiere_documentacion' => 'danger',
            'en_tramite' => 'primary',
            'finalizado' => 'success',
            'cancelado' => 'muted',
        ];

        $textos = [
            'pendiente' => 'Pendiente',
            'en_revision' => 'En revisión',
            'requiere_documentacion' => 'Requiere documentación',
            'en_tramite' => 'En tramitación',
            'finalizado' => 'Finalizado',
            'cancelado' => 'Cancelado',
        ];

        $clase = $clases[$estado] ?? 'muted';
        $texto = $textos[$estado] ?? ucfirst($estado);

        return '<span class="flavor-badge flavor-badge-' . esc_attr($clase) . '">' . esc_html($texto) . '</span>';
    }

    private function render_estado_cita_badge($estado) {
        $clases = [
            'pendiente' => 'warning',
            'confirmada' => 'success',
            'cancelada' => 'danger',
            'completada' => 'muted',
        ];

        $clase = $clases[$estado] ?? 'muted';

        return '<span class="flavor-badge flavor-badge-' . esc_attr($clase) . '">' . esc_html(ucfirst($estado)) . '</span>';
    }

    private function get_estado_texto($estado) {
        $textos = [
            'pendiente' => 'Pendiente de revisión',
            'en_revision' => 'En revisión',
            'requiere_documentacion' => 'Se requiere documentación adicional',
            'en_tramite' => 'En tramitación',
            'finalizado' => 'Trámite finalizado',
            'cancelado' => 'Trámite cancelado',
        ];

        return $textos[$estado] ?? $estado;
    }

    private function get_icono_tipo_archivo($tipo) {
        $iconos = [
            'pdf' => 'dashicons-pdf',
            'doc' => 'dashicons-media-document',
            'docx' => 'dashicons-media-document',
            'xls' => 'dashicons-media-spreadsheet',
            'xlsx' => 'dashicons-media-spreadsheet',
        ];

        return $iconos[$tipo] ?? 'dashicons-media-default';
    }

    private function get_tramite_url($tramite_id) {
        return add_query_arg('tramite_id', $tramite_id, home_url('/tramites/detalle/'));
    }

    private function get_solicitar_url($tramite_id) {
        return add_query_arg('tramite_id', $tramite_id, home_url('/tramites/solicitar/'));
    }

    private function get_seguimiento_url($solicitud_id) {
        return add_query_arg('solicitud_id', $solicitud_id, home_url('/tramites/seguimiento/'));
    }

    private function get_citas_url($tramite_id = 0) {
        $url = home_url('/tramites/citas/');
        if ($tramite_id) {
            $url = add_query_arg('tramite_id', $tramite_id, $url);
        }
        return $url;
    }

    private function get_catalogo_url() {
        return home_url('/tramites/');
    }

    private function get_mis_solicitudes_url() {
        return home_url('/mi-portal/?tab=tramites');
    }

    private function get_demo_tramites() {
        return [
            ['id' => 1, 'nombre' => 'Alta en el Padrón Municipal', 'descripcion' => 'Inscripción en el padrón de habitantes del municipio.', 'categoria' => 'empadronamiento', 'icono' => 'dashicons-id', 'tiempo_estimado' => '5-7 días', 'precio' => 0, 'online' => true, 'cita_previa' => false],
            ['id' => 2, 'nombre' => 'Licencia de Obras Menores', 'descripcion' => 'Solicitud de licencia para obras de reforma menores.', 'categoria' => 'urbanismo', 'icono' => 'dashicons-admin-home', 'tiempo_estimado' => '15-30 días', 'precio' => 45.00, 'online' => true, 'cita_previa' => false],
            ['id' => 3, 'nombre' => 'Bonificación del IBI', 'descripcion' => 'Solicitud de bonificación en el Impuesto de Bienes Inmuebles.', 'categoria' => 'tributos', 'icono' => 'dashicons-money-alt', 'tiempo_estimado' => '30 días', 'precio' => 0, 'online' => true, 'cita_previa' => true],
            ['id' => 4, 'nombre' => 'Ayuda Social de Emergencia', 'descripcion' => 'Solicitud de ayudas para situaciones de emergencia social.', 'categoria' => 'social', 'icono' => 'dashicons-heart', 'tiempo_estimado' => '7-15 días', 'precio' => 0, 'online' => false, 'cita_previa' => true],
        ];
    }
}

// Inicializar
Flavor_Tramites_Frontend_Controller::get_instance();

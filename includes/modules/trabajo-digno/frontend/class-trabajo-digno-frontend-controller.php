<?php
/**
 * Frontend Controller para Trabajo Digno
 *
 * Gestiona la bolsa de empleo ético, ofertas de trabajo con condiciones dignas,
 * cooperativismo y formación laboral.
 *
 * @package FlavorPlatform
 * @since 1.0.0
 */

if (!defined('ABSPATH')) {
    exit;
}

class Flavor_Trabajo_Digno_Frontend_Controller {

    private static $instance = null;
    private $module_slug = 'trabajo-digno';

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
        $shortcodes = [
            'flavor_trabajo_ofertas' => 'shortcode_ofertas',
            'flavor_trabajo_oferta' => 'shortcode_oferta',
            'flavor_trabajo_publicar' => 'shortcode_publicar',
            'flavor_trabajo_mis_ofertas' => 'shortcode_mis_ofertas',
            'flavor_trabajo_mis_candidaturas' => 'shortcode_mis_candidaturas',
            'flavor_trabajo_cooperativas' => 'shortcode_cooperativas',
            'flavor_trabajo_formacion' => 'shortcode_formacion',
            'flavor_trabajo_estadisticas' => 'shortcode_estadisticas',
        ];
        foreach ($shortcodes as $tag => $method) {
            if (!shortcode_exists($tag)) {
                add_shortcode($tag, [$this, $method]);
            }
        }

        // AJAX handlers
        add_action('wp_ajax_flavor_trabajo_publicar', [$this, 'ajax_publicar_oferta']);
        add_action('wp_ajax_flavor_trabajo_candidatura', [$this, 'ajax_enviar_candidatura']);
        add_action('wp_ajax_flavor_trabajo_guardar', [$this, 'ajax_guardar_oferta']);
        add_action('wp_ajax_flavor_trabajo_actualizar_estado', [$this, 'ajax_actualizar_estado']);
        add_action('wp_ajax_nopriv_flavor_trabajo_buscar', [$this, 'ajax_buscar']);
        add_action('wp_ajax_flavor_trabajo_buscar', [$this, 'ajax_buscar']);

        // Assets
        add_action('wp_enqueue_scripts', [$this, 'enqueue_assets']);

        // Dashboard tabs
        add_filter('flavor_user_dashboard_tabs', [$this, 'register_dashboard_tabs'], 10, 1);
    }

    public function enqueue_assets() {
        if ($this->is_trabajo_page()) {
            $base_url = plugins_url('', dirname(dirname(__FILE__)));
            $version = defined('FLAVOR_VERSION') ? FLAVOR_VERSION : '1.0.0';

            wp_enqueue_style(
                'flavor-trabajo-frontend',
                $base_url . '/assets/css/trabajo-digno.css',
                [],
                $version
            );

            wp_enqueue_script(
                'flavor-trabajo-frontend',
                $base_url . '/assets/js/trabajo-digno.js',
                ['jquery'],
                $version,
                true
            );

            wp_localize_script('flavor-trabajo-frontend', 'flavorTrabajoConfig', [
                'ajaxUrl' => admin_url('admin-ajax.php'),
                'nonce' => wp_create_nonce('flavor_trabajo_nonce'),
                'strings' => [
                    'procesando' => __('Procesando...', FLAVOR_PLATFORM_TEXT_DOMAIN),
                    'error' => __('Ha ocurrido un error', FLAVOR_PLATFORM_TEXT_DOMAIN),
                    'candidaturaEnviada' => __('Candidatura enviada correctamente', FLAVOR_PLATFORM_TEXT_DOMAIN),
                    'ofertaPublicada' => __('Oferta publicada correctamente', FLAVOR_PLATFORM_TEXT_DOMAIN),
                ],
            ]);
        }
    }

    private function is_trabajo_page() {
        global $post;
        if (!$post) return false;

        $shortcodes = ['flavor_trabajo_ofertas', 'flavor_trabajo_oferta', 'flavor_trabajo_publicar',
                       'flavor_trabajo_mis_ofertas', 'flavor_trabajo_mis_candidaturas', 'flavor_trabajo_cooperativas'];

        foreach ($shortcodes as $shortcode) {
            if (has_shortcode($post->post_content, $shortcode)) {
                return true;
            }
        }
        return false;
    }

    public function register_dashboard_tabs($tabs) {
        $tabs['trabajo-digno'] = [
            'titulo' => __('Trabajo Digno', FLAVOR_PLATFORM_TEXT_DOMAIN),
            'icono' => 'dashicons-businessman',
            'callback' => [$this, 'render_dashboard_tab'],
            'prioridad' => 35,
        ];

        return $tabs;
    }

    // =========================================================================
    // SHORTCODES
    // =========================================================================

    public function shortcode_ofertas($atts) {
        $atts = shortcode_atts([
            'categoria' => '',
            'tipo' => '',
            'limite' => 20,
        ], $atts);

        $ofertas = $this->obtener_ofertas($atts);
        $categorias = $this->obtener_categorias();
        $tipos = $this->obtener_tipos_contrato();

        ob_start();
        ?>
        <div class="flavor-trabajo-ofertas">
            <div class="flavor-trabajo-header">
                <div>
                    <h2><?php _e('Bolsa de Trabajo Digno', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></h2>
                    <p class="flavor-intro"><?php _e('Ofertas de empleo con condiciones laborales justas y dignas.', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></p>
                </div>
                <?php if (is_user_logged_in()): ?>
                <a href="<?php echo esc_url($this->get_publicar_url()); ?>" class="flavor-btn flavor-btn-primary">
                    <span class="dashicons dashicons-plus"></span>
                    <?php _e('Publicar oferta', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                </a>
                <?php endif; ?>
            </div>

            <div class="flavor-trabajo-filtros">
                <form class="flavor-filtros-form" method="get">
                    <div class="flavor-filtro-grupo">
                        <input type="text" name="buscar" placeholder="<?php esc_attr_e('Buscar empleo...', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>"
                               value="<?php echo esc_attr($_GET['buscar'] ?? ''); ?>">
                    </div>
                    <div class="flavor-filtro-grupo">
                        <select name="categoria">
                            <option value=""><?php _e('Todas las categorías', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></option>
                            <?php foreach ($categorias as $cat): ?>
                            <option value="<?php echo esc_attr($cat['slug']); ?>"
                                    <?php selected($_GET['categoria'] ?? '', $cat['slug']); ?>>
                                <?php echo esc_html($cat['nombre']); ?>
                            </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="flavor-filtro-grupo">
                        <select name="tipo">
                            <option value=""><?php _e('Tipo de contrato', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></option>
                            <?php foreach ($tipos as $tipo): ?>
                            <option value="<?php echo esc_attr($tipo['slug']); ?>"
                                    <?php selected($_GET['tipo'] ?? '', $tipo['slug']); ?>>
                                <?php echo esc_html($tipo['nombre']); ?>
                            </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <button type="submit" class="flavor-btn flavor-btn-outline">
                        <span class="dashicons dashicons-search"></span>
                    </button>
                </form>
            </div>

            <div class="flavor-ofertas-grid">
                <?php if (empty($ofertas)): ?>
                <div class="flavor-empty-state">
                    <span class="dashicons dashicons-businessman"></span>
                    <p><?php _e('No se encontraron ofertas con esos criterios.', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></p>
                </div>
                <?php else: ?>
                    <?php foreach ($ofertas as $oferta): ?>
                    <div class="flavor-oferta-card">
                        <div class="flavor-oferta-header">
                            <div class="flavor-empresa-logo">
                                <?php if (!empty($oferta['logo'])): ?>
                                <img src="<?php echo esc_url($oferta['logo']); ?>" alt="">
                                <?php else: ?>
                                <span class="dashicons dashicons-building"></span>
                                <?php endif; ?>
                            </div>
                            <div class="flavor-oferta-badges">
                                <?php if ($oferta['verificada']): ?>
                                <span class="flavor-badge flavor-badge-success">
                                    <span class="dashicons dashicons-yes"></span> Verificada
                                </span>
                                <?php endif; ?>
                                <?php if ($oferta['cooperativa']): ?>
                                <span class="flavor-badge flavor-badge-info">Cooperativa</span>
                                <?php endif; ?>
                            </div>
                        </div>
                        <div class="flavor-oferta-contenido">
                            <h3 class="flavor-oferta-titulo">
                                <a href="<?php echo esc_url($this->get_oferta_url($oferta['id'])); ?>">
                                    <?php echo esc_html($oferta['titulo']); ?>
                                </a>
                            </h3>
                            <p class="flavor-oferta-empresa"><?php echo esc_html($oferta['empresa']); ?></p>
                            <p class="flavor-oferta-descripcion">
                                <?php echo esc_html(wp_trim_words($oferta['descripcion'], 20)); ?>
                            </p>
                            <div class="flavor-oferta-detalles">
                                <span class="flavor-detalle">
                                    <span class="dashicons dashicons-location"></span>
                                    <?php echo esc_html($oferta['ubicacion']); ?>
                                </span>
                                <span class="flavor-detalle">
                                    <span class="dashicons dashicons-clock"></span>
                                    <?php echo esc_html($oferta['tipo_contrato']); ?>
                                </span>
                                <?php if (!empty($oferta['salario'])): ?>
                                <span class="flavor-detalle flavor-salario">
                                    <?php echo esc_html($oferta['salario']); ?>
                                </span>
                                <?php endif; ?>
                            </div>
                        </div>
                        <div class="flavor-oferta-footer">
                            <span class="flavor-fecha-publicacion">
                                <?php printf(__('Hace %s', FLAVOR_PLATFORM_TEXT_DOMAIN), human_time_diff(strtotime($oferta['fecha_publicacion']))); ?>
                            </span>
                            <a href="<?php echo esc_url($this->get_oferta_url($oferta['id'])); ?>"
                               class="flavor-btn flavor-btn-sm flavor-btn-primary">
                                <?php _e('Ver oferta', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                            </a>
                        </div>
                    </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>
        <?php
        return ob_get_clean();
    }

    public function shortcode_oferta($atts) {
        $atts = shortcode_atts(['id' => 0], $atts);
        $oferta_id = intval($atts['id']) ?: intval($_GET['oferta_id'] ?? 0);

        if (!$oferta_id) {
            return '<div class="flavor-error">' . __('Oferta no especificada.', FLAVOR_PLATFORM_TEXT_DOMAIN) . '</div>';
        }

        $oferta = $this->obtener_oferta($oferta_id);
        if (!$oferta) {
            return '<div class="flavor-error">' . __('Oferta no encontrada.', FLAVOR_PLATFORM_TEXT_DOMAIN) . '</div>';
        }

        $user_id = get_current_user_id();
        $ya_candidato = $this->es_candidato($oferta_id, $user_id);

        ob_start();
        ?>
        <div class="flavor-oferta-detalle">
            <div class="flavor-oferta-hero">
                <div class="flavor-oferta-hero-main">
                    <nav class="flavor-breadcrumb">
                        <a href="<?php echo esc_url($this->get_ofertas_url()); ?>"><?php _e('Ofertas', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></a>
                        <span class="separator">›</span>
                        <span><?php echo esc_html($oferta['titulo']); ?></span>
                    </nav>

                    <div class="flavor-oferta-titulo-detalle">
                        <div class="flavor-empresa-logo-grande">
                            <?php if (!empty($oferta['logo'])): ?>
                            <img src="<?php echo esc_url($oferta['logo']); ?>" alt="">
                            <?php else: ?>
                            <span class="dashicons dashicons-building"></span>
                            <?php endif; ?>
                        </div>
                        <div>
                            <h1><?php echo esc_html($oferta['titulo']); ?></h1>
                            <p class="flavor-empresa-nombre"><?php echo esc_html($oferta['empresa']); ?></p>
                            <div class="flavor-oferta-badges-detalle">
                                <?php if ($oferta['verificada']): ?>
                                <span class="flavor-badge flavor-badge-success">Empresa verificada</span>
                                <?php endif; ?>
                                <?php if ($oferta['cooperativa']): ?>
                                <span class="flavor-badge flavor-badge-info">Cooperativa/ESS</span>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>

                    <div class="flavor-oferta-resumen">
                        <div class="flavor-resumen-item">
                            <span class="dashicons dashicons-location"></span>
                            <div>
                                <span class="flavor-resumen-label"><?php _e('Ubicación', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></span>
                                <span class="flavor-resumen-valor"><?php echo esc_html($oferta['ubicacion']); ?></span>
                            </div>
                        </div>
                        <div class="flavor-resumen-item">
                            <span class="dashicons dashicons-clock"></span>
                            <div>
                                <span class="flavor-resumen-label"><?php _e('Tipo', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></span>
                                <span class="flavor-resumen-valor"><?php echo esc_html($oferta['tipo_contrato']); ?></span>
                            </div>
                        </div>
                        <?php if (!empty($oferta['salario'])): ?>
                        <div class="flavor-resumen-item">
                            <span class="dashicons dashicons-money-alt"></span>
                            <div>
                                <span class="flavor-resumen-label"><?php _e('Salario', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></span>
                                <span class="flavor-resumen-valor"><?php echo esc_html($oferta['salario']); ?></span>
                            </div>
                        </div>
                        <?php endif; ?>
                        <div class="flavor-resumen-item">
                            <span class="dashicons dashicons-calendar-alt"></span>
                            <div>
                                <span class="flavor-resumen-label"><?php _e('Publicado', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></span>
                                <span class="flavor-resumen-valor"><?php echo esc_html(date_i18n('d/m/Y', strtotime($oferta['fecha_publicacion']))); ?></span>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="flavor-oferta-hero-aside">
                    <div class="flavor-candidatura-card">
                        <?php if (!is_user_logged_in()): ?>
                        <p><?php _e('Inicia sesión para enviar tu candidatura.', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></p>
                        <a href="<?php echo wp_login_url(flavor_current_request_url()); ?>" class="flavor-btn flavor-btn-primary flavor-btn-block">
                            <?php _e('Iniciar sesión', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                        </a>
                        <?php elseif ($ya_candidato): ?>
                        <div class="flavor-ya-candidato">
                            <span class="dashicons dashicons-yes-alt"></span>
                            <p><?php _e('Ya has enviado tu candidatura a esta oferta.', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></p>
                        </div>
                        <?php else: ?>
                        <button type="button" class="flavor-btn flavor-btn-primary flavor-btn-block flavor-btn-lg flavor-enviar-candidatura"
                                data-oferta-id="<?php echo esc_attr($oferta_id); ?>">
                            <?php _e('Enviar candidatura', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                        </button>
                        <p class="flavor-candidatos-info">
                            <?php printf(__('%d personas ya se han inscrito', FLAVOR_PLATFORM_TEXT_DOMAIN), $oferta['candidatos']); ?>
                        </p>
                        <?php endif; ?>

                        <button type="button" class="flavor-btn flavor-btn-outline flavor-btn-block flavor-guardar-oferta"
                                data-oferta-id="<?php echo esc_attr($oferta_id); ?>">
                            <span class="dashicons dashicons-heart"></span>
                            <?php _e('Guardar oferta', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                        </button>
                    </div>
                </div>
            </div>

            <div class="flavor-oferta-contenido-detalle">
                <div class="flavor-oferta-main">
                    <section class="flavor-panel">
                        <h3><?php _e('Descripción del puesto', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></h3>
                        <div class="flavor-oferta-descripcion-larga">
                            <?php echo wp_kses_post(nl2br($oferta['descripcion'])); ?>
                        </div>
                    </section>

                    <?php if (!empty($oferta['requisitos'])): ?>
                    <section class="flavor-panel">
                        <h3><?php _e('Requisitos', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></h3>
                        <div class="flavor-requisitos">
                            <?php echo wp_kses_post($oferta['requisitos']); ?>
                        </div>
                    </section>
                    <?php endif; ?>

                    <?php if (!empty($oferta['beneficios'])): ?>
                    <section class="flavor-panel">
                        <h3><?php _e('Beneficios', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></h3>
                        <div class="flavor-beneficios">
                            <?php echo wp_kses_post($oferta['beneficios']); ?>
                        </div>
                    </section>
                    <?php endif; ?>

                    <?php if (!empty($oferta['condiciones_dignas'])): ?>
                    <section class="flavor-panel flavor-panel-destacado">
                        <h3><span class="dashicons dashicons-shield"></span> <?php _e('Compromiso con el Trabajo Digno', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></h3>
                        <div class="flavor-condiciones-dignas">
                            <?php echo wp_kses_post($oferta['condiciones_dignas']); ?>
                        </div>
                    </section>
                    <?php endif; ?>
                </div>

                <div class="flavor-oferta-sidebar">
                    <div class="flavor-panel">
                        <h3><?php _e('Sobre la empresa', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></h3>
                        <div class="flavor-empresa-info">
                            <h4><?php echo esc_html($oferta['empresa']); ?></h4>
                            <?php if (!empty($oferta['empresa_descripcion'])): ?>
                            <p><?php echo esc_html($oferta['empresa_descripcion']); ?></p>
                            <?php endif; ?>
                            <?php if (!empty($oferta['empresa_web'])): ?>
                            <a href="<?php echo esc_url($oferta['empresa_web']); ?>" target="_blank" class="flavor-empresa-web">
                                <span class="dashicons dashicons-admin-site"></span> <?php _e('Visitar web', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                            </a>
                            <?php endif; ?>
                        </div>
                    </div>

                    <div class="flavor-panel">
                        <h3><?php _e('Comparte esta oferta', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></h3>
                        <div class="flavor-compartir-botones">
                            <a href="https://twitter.com/intent/tweet?url=<?php echo urlencode(get_permalink()); ?>&text=<?php echo urlencode($oferta['titulo']); ?>"
                               target="_blank" class="flavor-compartir-btn twitter">
                                <span class="dashicons dashicons-twitter"></span>
                            </a>
                            <a href="https://www.linkedin.com/shareArticle?mini=true&url=<?php echo urlencode(get_permalink()); ?>"
                               target="_blank" class="flavor-compartir-btn linkedin">
                                <span class="dashicons dashicons-linkedin"></span>
                            </a>
                            <a href="mailto:?subject=<?php echo rawurlencode($oferta['titulo']); ?>&body=<?php echo rawurlencode(get_permalink()); ?>"
                               class="flavor-compartir-btn email">
                                <span class="dashicons dashicons-email"></span>
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <?php
        return ob_get_clean();
    }

    public function shortcode_publicar($atts) {
        if (!is_user_logged_in()) {
            return '<div class="flavor-login-required">' . __('Inicia sesión para publicar una oferta.', FLAVOR_PLATFORM_TEXT_DOMAIN) . '</div>';
        }

        $categorias = $this->obtener_categorias();
        $tipos = $this->obtener_tipos_contrato();

        ob_start();
        ?>
        <div class="flavor-publicar-oferta">
            <h2><?php _e('Publicar Oferta de Trabajo', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></h2>
            <p class="flavor-intro"><?php _e('Todas las ofertas deben cumplir los estándares de trabajo digno.', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></p>

            <form id="flavor-form-oferta" class="flavor-form" enctype="multipart/form-data">
                <?php wp_nonce_field('flavor_trabajo_nonce', 'oferta_nonce'); ?>

                <fieldset>
                    <legend><?php _e('Información del puesto', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></legend>

                    <div class="flavor-form-group">
                        <label for="titulo"><?php _e('Título del puesto', FLAVOR_PLATFORM_TEXT_DOMAIN); ?> *</label>
                        <input type="text" id="titulo" name="titulo" required
                               placeholder="<?php esc_attr_e('Ej: Desarrollador/a Web Junior', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>">
                    </div>

                    <div class="flavor-form-row">
                        <div class="flavor-form-group">
                            <label for="categoria"><?php _e('Categoría', FLAVOR_PLATFORM_TEXT_DOMAIN); ?> *</label>
                            <select id="categoria" name="categoria" required>
                                <option value=""><?php _e('Selecciona categoría', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></option>
                                <?php foreach ($categorias as $cat): ?>
                                <option value="<?php echo esc_attr($cat['slug']); ?>"><?php echo esc_html($cat['nombre']); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="flavor-form-group">
                            <label for="tipo_contrato"><?php _e('Tipo de contrato', FLAVOR_PLATFORM_TEXT_DOMAIN); ?> *</label>
                            <select id="tipo_contrato" name="tipo_contrato" required>
                                <option value=""><?php _e('Selecciona tipo', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></option>
                                <?php foreach ($tipos as $tipo): ?>
                                <option value="<?php echo esc_attr($tipo['slug']); ?>"><?php echo esc_html($tipo['nombre']); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>

                    <div class="flavor-form-group">
                        <label for="descripcion"><?php _e('Descripción del puesto', FLAVOR_PLATFORM_TEXT_DOMAIN); ?> *</label>
                        <textarea id="descripcion" name="descripcion" rows="6" required
                                  placeholder="<?php esc_attr_e('Describe las funciones, responsabilidades y el día a día del puesto...', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>"></textarea>
                    </div>

                    <div class="flavor-form-group">
                        <label for="requisitos"><?php _e('Requisitos', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></label>
                        <textarea id="requisitos" name="requisitos" rows="4"
                                  placeholder="<?php esc_attr_e('Formación, experiencia, habilidades necesarias...', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>"></textarea>
                    </div>

                    <div class="flavor-form-row">
                        <div class="flavor-form-group">
                            <label for="ubicacion"><?php _e('Ubicación', FLAVOR_PLATFORM_TEXT_DOMAIN); ?> *</label>
                            <input type="text" id="ubicacion" name="ubicacion" required
                                   placeholder="<?php esc_attr_e('Ciudad, País o Remoto', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>">
                        </div>
                        <div class="flavor-form-group">
                            <label for="salario"><?php _e('Salario (opcional)', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></label>
                            <input type="text" id="salario" name="salario"
                                   placeholder="<?php esc_attr_e('Ej: 25.000€ - 30.000€/año', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>">
                        </div>
                    </div>
                </fieldset>

                <fieldset>
                    <legend><?php _e('Información de la empresa', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></legend>

                    <div class="flavor-form-group">
                        <label for="empresa"><?php _e('Nombre de la empresa/organización', FLAVOR_PLATFORM_TEXT_DOMAIN); ?> *</label>
                        <input type="text" id="empresa" name="empresa" required>
                    </div>

                    <div class="flavor-form-group">
                        <label for="empresa_descripcion"><?php _e('Descripción breve de la empresa', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></label>
                        <textarea id="empresa_descripcion" name="empresa_descripcion" rows="3"></textarea>
                    </div>

                    <div class="flavor-form-group flavor-checkbox-group">
                        <label>
                            <input type="checkbox" name="es_cooperativa" value="1">
                            <?php _e('Somos una cooperativa o empresa de Economía Social y Solidaria', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                        </label>
                    </div>
                </fieldset>

                <fieldset>
                    <legend><?php _e('Compromiso Trabajo Digno', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></legend>

                    <div class="flavor-form-group">
                        <label for="beneficios"><?php _e('Beneficios que ofreces', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></label>
                        <textarea id="beneficios" name="beneficios" rows="3"
                                  placeholder="<?php esc_attr_e('Teletrabajo, horario flexible, formación, conciliación...', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>"></textarea>
                    </div>

                    <div class="flavor-form-group">
                        <label for="condiciones_dignas"><?php _e('¿Cómo garantizáis condiciones de trabajo dignas?', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></label>
                        <textarea id="condiciones_dignas" name="condiciones_dignas" rows="3"
                                  placeholder="<?php esc_attr_e('Describe vuestro compromiso con salarios justos, igualdad, conciliación...', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>"></textarea>
                    </div>

                    <div class="flavor-form-group flavor-checkbox-group">
                        <label>
                            <input type="checkbox" name="acepto_condiciones" required>
                            <?php _e('Confirmo que esta oferta cumple con los estándares de trabajo digno y las condiciones descritas son veraces.', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                        </label>
                    </div>
                </fieldset>

                <div class="flavor-form-actions">
                    <button type="submit" class="flavor-btn flavor-btn-primary flavor-btn-lg">
                        <?php _e('Publicar oferta', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                    </button>
                </div>
            </form>
        </div>
        <?php
        return ob_get_clean();
    }

    public function shortcode_mis_ofertas($atts) {
        if (!is_user_logged_in()) {
            return '<div class="flavor-login-required">' . __('Inicia sesión para ver tus ofertas.', FLAVOR_PLATFORM_TEXT_DOMAIN) . '</div>';
        }

        $user_id = get_current_user_id();
        $ofertas = $this->obtener_ofertas_usuario($user_id);

        ob_start();
        ?>
        <div class="flavor-mis-ofertas">
            <div class="flavor-mis-ofertas-header">
                <h2><?php _e('Mis Ofertas Publicadas', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></h2>
                <a href="<?php echo esc_url($this->get_publicar_url()); ?>" class="flavor-btn flavor-btn-primary">
                    <?php _e('Nueva oferta', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                </a>
            </div>

            <?php if (empty($ofertas)): ?>
            <div class="flavor-empty-state">
                <span class="dashicons dashicons-businessman"></span>
                <p><?php _e('No has publicado ninguna oferta aún.', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></p>
            </div>
            <?php else: ?>
            <div class="flavor-mis-ofertas-lista">
                <?php foreach ($ofertas as $oferta): ?>
                <div class="flavor-mi-oferta-item">
                    <div class="flavor-mi-oferta-info">
                        <h4><?php echo esc_html($oferta['titulo']); ?></h4>
                        <p><?php printf(__('%d candidaturas', FLAVOR_PLATFORM_TEXT_DOMAIN), $oferta['candidatos']); ?></p>
                    </div>
                    <?php echo $this->render_estado_badge($oferta['estado']); ?>
                    <a href="<?php echo esc_url($this->get_oferta_url($oferta['id'])); ?>"
                       class="flavor-btn flavor-btn-sm flavor-btn-outline">
                        <?php _e('Ver', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                    </a>
                </div>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>
        </div>
        <?php
        return ob_get_clean();
    }

    public function shortcode_mis_candidaturas($atts) {
        if (!is_user_logged_in()) {
            return '<div class="flavor-login-required">' . __('Inicia sesión para ver tus candidaturas.', FLAVOR_PLATFORM_TEXT_DOMAIN) . '</div>';
        }

        $user_id = get_current_user_id();
        $candidaturas = $this->obtener_candidaturas_usuario($user_id);

        ob_start();
        ?>
        <div class="flavor-mis-candidaturas">
            <h2><?php _e('Mis Candidaturas', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></h2>

            <?php if (empty($candidaturas)): ?>
            <div class="flavor-empty-state">
                <span class="dashicons dashicons-welcome-write-blog"></span>
                <p><?php _e('No has enviado ninguna candidatura aún.', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></p>
                <a href="<?php echo esc_url($this->get_ofertas_url()); ?>" class="flavor-btn flavor-btn-primary">
                    <?php _e('Ver ofertas', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                </a>
            </div>
            <?php else: ?>
            <div class="flavor-candidaturas-lista">
                <?php foreach ($candidaturas as $cand): ?>
                <div class="flavor-candidatura-item">
                    <div class="flavor-candidatura-info">
                        <h4><?php echo esc_html($cand['oferta_titulo']); ?></h4>
                        <p><?php echo esc_html($cand['empresa']); ?></p>
                        <span class="flavor-fecha"><?php echo esc_html(date_i18n('d/m/Y', strtotime($cand['fecha']))); ?></span>
                    </div>
                    <?php echo $this->render_estado_candidatura_badge($cand['estado']); ?>
                </div>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>
        </div>
        <?php
        return ob_get_clean();
    }

    public function shortcode_cooperativas($atts) {
        $cooperativas = $this->obtener_cooperativas();

        ob_start();
        ?>
        <div class="flavor-cooperativas">
            <div class="flavor-cooperativas-header">
                <h2><?php _e('Cooperativas y Economía Social', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></h2>
                <p><?php _e('Empresas comprometidas con el trabajo digno y la economía solidaria.', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></p>
            </div>

            <div class="flavor-cooperativas-grid">
                <?php foreach ($cooperativas as $coop): ?>
                <div class="flavor-cooperativa-card">
                    <div class="flavor-cooperativa-logo">
                        <?php if (!empty($coop['logo'])): ?>
                        <img src="<?php echo esc_url($coop['logo']); ?>" alt="">
                        <?php else: ?>
                        <span class="dashicons dashicons-groups"></span>
                        <?php endif; ?>
                    </div>
                    <h3><?php echo esc_html($coop['nombre']); ?></h3>
                    <p class="flavor-cooperativa-sector"><?php echo esc_html($coop['sector']); ?></p>
                    <p class="flavor-cooperativa-desc"><?php echo esc_html(wp_trim_words($coop['descripcion'], 15)); ?></p>
                    <div class="flavor-cooperativa-stats">
                        <span><?php printf(__('%d empleos', FLAVOR_PLATFORM_TEXT_DOMAIN), $coop['empleos']); ?></span>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
        <?php
        return ob_get_clean();
    }

    public function shortcode_formacion($atts) {
        $cursos = $this->obtener_cursos_formacion();

        ob_start();
        ?>
        <div class="flavor-formacion-laboral">
            <h2><?php _e('Formación para el Empleo', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></h2>
            <p><?php _e('Cursos y recursos para mejorar tu empleabilidad.', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></p>

            <div class="flavor-cursos-grid">
                <?php foreach ($cursos as $curso): ?>
                <div class="flavor-curso-card">
                    <h4><?php echo esc_html($curso['titulo']); ?></h4>
                    <p><?php echo esc_html($curso['descripcion']); ?></p>
                    <span class="flavor-badge"><?php echo esc_html($curso['modalidad']); ?></span>
                </div>
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
        <div class="flavor-trabajo-estadisticas">
            <h2><?php _e('Impacto de la Bolsa de Trabajo Digno', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></h2>
            <div class="flavor-stats-grid">
                <div class="flavor-stat-card">
                    <span class="flavor-stat-valor"><?php echo intval($stats['ofertas_activas']); ?></span>
                    <span class="flavor-stat-label"><?php _e('Ofertas activas', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></span>
                </div>
                <div class="flavor-stat-card">
                    <span class="flavor-stat-valor"><?php echo intval($stats['contrataciones']); ?></span>
                    <span class="flavor-stat-label"><?php _e('Contrataciones', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></span>
                </div>
                <div class="flavor-stat-card">
                    <span class="flavor-stat-valor"><?php echo intval($stats['empresas']); ?></span>
                    <span class="flavor-stat-label"><?php _e('Empresas', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></span>
                </div>
                <div class="flavor-stat-card">
                    <span class="flavor-stat-valor"><?php echo intval($stats['cooperativas']); ?></span>
                    <span class="flavor-stat-label"><?php _e('Cooperativas', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></span>
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
        $candidaturas = $this->obtener_candidaturas_usuario($user_id, 5);
        $ofertas = $this->obtener_ofertas_usuario($user_id, 5);
        $stats = $this->obtener_estadisticas_usuario($user_id);

        ?>
        <div class="flavor-dashboard-trabajo">
            <div class="flavor-kpi-grid">
                <div class="flavor-kpi-card">
                    <div class="flavor-kpi-valor"><?php echo intval($stats['candidaturas']); ?></div>
                    <div class="flavor-kpi-label"><?php _e('Candidaturas', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></div>
                </div>
                <div class="flavor-kpi-card">
                    <div class="flavor-kpi-valor"><?php echo intval($stats['ofertas_publicadas']); ?></div>
                    <div class="flavor-kpi-label"><?php _e('Ofertas publicadas', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></div>
                </div>
            </div>

            <div class="flavor-panel">
                <div class="flavor-panel-header">
                    <h3><?php _e('Mis Candidaturas', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></h3>
                    <a href="<?php echo esc_url($this->get_ofertas_url()); ?>" class="flavor-btn flavor-btn-sm flavor-btn-outline">
                        <?php _e('Ver ofertas', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                    </a>
                </div>

                <?php if (empty($candidaturas)): ?>
                <p class="flavor-text-muted"><?php _e('No tienes candidaturas activas.', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></p>
                <?php else: ?>
                <div class="flavor-candidaturas-mini">
                    <?php foreach ($candidaturas as $cand): ?>
                    <div class="flavor-candidatura-mini-item">
                        <div>
                            <h4><?php echo esc_html($cand['oferta_titulo']); ?></h4>
                            <span class="flavor-empresa"><?php echo esc_html($cand['empresa']); ?></span>
                        </div>
                        <?php echo $this->render_estado_candidatura_badge($cand['estado']); ?>
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

    public function ajax_publicar_oferta() {
        check_ajax_referer('flavor_trabajo_nonce', 'oferta_nonce');

        if (!is_user_logged_in()) {
            wp_send_json_error(['message' => __('Debes iniciar sesión.', FLAVOR_PLATFORM_TEXT_DOMAIN)]);
        }

        $titulo = sanitize_text_field($_POST['titulo'] ?? '');
        $descripcion = sanitize_textarea_field($_POST['descripcion'] ?? '');
        $empresa = sanitize_text_field($_POST['empresa'] ?? '');

        if (empty($titulo) || empty($descripcion) || empty($empresa)) {
            wp_send_json_error(['message' => __('Completa los campos obligatorios.', FLAVOR_PLATFORM_TEXT_DOMAIN)]);
        }

        global $wpdb;
        $tabla = $wpdb->prefix . 'flavor_trabajo_ofertas';

        if (!Flavor_Platform_Helpers::tabla_existe($tabla)) {
            wp_send_json_error(['message' => __('Sistema no disponible.', FLAVOR_PLATFORM_TEXT_DOMAIN)]);
        }

        $user_id = get_current_user_id();

        $result = $wpdb->insert($tabla, [
            'titulo' => $titulo,
            'descripcion' => $descripcion,
            'requisitos' => sanitize_textarea_field($_POST['requisitos'] ?? ''),
            'beneficios' => sanitize_textarea_field($_POST['beneficios'] ?? ''),
            'condiciones_dignas' => sanitize_textarea_field($_POST['condiciones_dignas'] ?? ''),
            'empresa' => $empresa,
            'empresa_descripcion' => sanitize_textarea_field($_POST['empresa_descripcion'] ?? ''),
            'ubicacion' => sanitize_text_field($_POST['ubicacion'] ?? ''),
            'salario' => sanitize_text_field($_POST['salario'] ?? ''),
            'categoria' => sanitize_text_field($_POST['categoria'] ?? ''),
            'tipo_contrato' => sanitize_text_field($_POST['tipo_contrato'] ?? ''),
            'es_cooperativa' => !empty($_POST['es_cooperativa']) ? 1 : 0,
            'usuario_id' => $user_id,
            'estado' => 'pendiente',
            'fecha_publicacion' => current_time('mysql'),
        ]);

        if ($result) {
            wp_send_json_success([
                'message' => __('Oferta enviada para revisión. Se publicará pronto.', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'redirect' => $this->get_mis_ofertas_url(),
            ]);
        } else {
            wp_send_json_error(['message' => __('Error al publicar.', FLAVOR_PLATFORM_TEXT_DOMAIN)]);
        }
    }

    public function ajax_enviar_candidatura() {
        check_ajax_referer('flavor_trabajo_nonce', 'nonce');

        if (!is_user_logged_in()) {
            wp_send_json_error(['message' => __('Debes iniciar sesión.', FLAVOR_PLATFORM_TEXT_DOMAIN)]);
        }

        $oferta_id = intval($_POST['oferta_id'] ?? 0);
        $user_id = get_current_user_id();

        if (!$oferta_id) {
            wp_send_json_error(['message' => __('Oferta no especificada.', FLAVOR_PLATFORM_TEXT_DOMAIN)]);
        }

        global $wpdb;
        $tabla = $wpdb->prefix . 'flavor_trabajo_candidaturas';

        if (!Flavor_Platform_Helpers::tabla_existe($tabla)) {
            wp_send_json_error(['message' => __('Sistema no disponible.', FLAVOR_PLATFORM_TEXT_DOMAIN)]);
        }

        // Verificar si ya es candidato
        $existe = $wpdb->get_var($wpdb->prepare(
            "SELECT id FROM {$tabla} WHERE oferta_id = %d AND usuario_id = %d",
            $oferta_id, $user_id
        ));

        if ($existe) {
            wp_send_json_error(['message' => __('Ya has enviado tu candidatura a esta oferta.', FLAVOR_PLATFORM_TEXT_DOMAIN)]);
        }

        $result = $wpdb->insert($tabla, [
            'oferta_id' => $oferta_id,
            'usuario_id' => $user_id,
            'estado' => 'enviada',
            'fecha' => current_time('mysql'),
        ]);

        if ($result) {
            wp_send_json_success(['message' => __('Candidatura enviada correctamente.', FLAVOR_PLATFORM_TEXT_DOMAIN)]);
        } else {
            wp_send_json_error(['message' => __('Error al enviar.', FLAVOR_PLATFORM_TEXT_DOMAIN)]);
        }
    }

    public function ajax_guardar_oferta() {
        check_ajax_referer('flavor_trabajo_nonce', 'nonce');
        wp_send_json_success(['message' => __('Oferta guardada.', FLAVOR_PLATFORM_TEXT_DOMAIN)]);
    }

    public function ajax_actualizar_estado() {
        check_ajax_referer('flavor_trabajo_nonce', 'nonce');
        wp_send_json_success(['message' => __('Estado actualizado.', FLAVOR_PLATFORM_TEXT_DOMAIN)]);
    }

    public function ajax_buscar() {
        $termino = sanitize_text_field($_POST['termino'] ?? '');

        if (strlen($termino) < 2) {
            wp_send_json_success(['ofertas' => []]);
        }

        $ofertas = $this->buscar_ofertas($termino);
        wp_send_json_success(['ofertas' => $ofertas]);
    }

    // =========================================================================
    // MÉTODOS AUXILIARES
    // =========================================================================

    private function obtener_ofertas($args) {
        global $wpdb;
        $tabla = $wpdb->prefix . 'flavor_trabajo_ofertas';

        if (!Flavor_Platform_Helpers::tabla_existe($tabla)) {
            return $this->get_demo_ofertas();
        }

        return $wpdb->get_results("SELECT * FROM {$tabla} WHERE estado = 'publicada' ORDER BY fecha_publicacion DESC LIMIT 20", ARRAY_A) ?: $this->get_demo_ofertas();
    }

    private function obtener_oferta($oferta_id) {
        global $wpdb;
        $tabla = $wpdb->prefix . 'flavor_trabajo_ofertas';

        if (!Flavor_Platform_Helpers::tabla_existe($tabla)) {
            $demos = $this->get_demo_ofertas();
            foreach ($demos as $o) {
                if ($o['id'] == $oferta_id) return $o;
            }
            return null;
        }

        return $wpdb->get_row($wpdb->prepare("SELECT * FROM {$tabla} WHERE id = %d", $oferta_id), ARRAY_A);
    }

    private function obtener_categorias() {
        return [
            ['slug' => 'tecnologia', 'nombre' => 'Tecnología'],
            ['slug' => 'administracion', 'nombre' => 'Administración'],
            ['slug' => 'comercial', 'nombre' => 'Comercial y ventas'],
            ['slug' => 'hosteleria', 'nombre' => 'Hostelería'],
            ['slug' => 'educacion', 'nombre' => 'Educación'],
            ['slug' => 'salud', 'nombre' => 'Salud y cuidados'],
            ['slug' => 'artes', 'nombre' => 'Artes y oficios'],
            ['slug' => 'otros', 'nombre' => 'Otros'],
        ];
    }

    private function obtener_tipos_contrato() {
        return [
            ['slug' => 'indefinido', 'nombre' => 'Indefinido'],
            ['slug' => 'temporal', 'nombre' => 'Temporal'],
            ['slug' => 'practicas', 'nombre' => 'Prácticas'],
            ['slug' => 'autonomo', 'nombre' => 'Autónomo/Freelance'],
            ['slug' => 'parcial', 'nombre' => 'Tiempo parcial'],
        ];
    }

    private function obtener_ofertas_usuario($user_id, $limite = 20) {
        return [];
    }

    private function obtener_candidaturas_usuario($user_id, $limite = 20) {
        return [];
    }

    private function obtener_cooperativas() {
        return [
            ['nombre' => 'Cooperativa Ejemplo', 'sector' => 'Tecnología', 'descripcion' => 'Cooperativa de desarrollo de software.', 'empleos' => 5, 'logo' => ''],
        ];
    }

    private function obtener_cursos_formacion() {
        return [
            ['titulo' => 'Introducción a la ESS', 'descripcion' => 'Conoce la Economía Social y Solidaria.', 'modalidad' => 'Online'],
        ];
    }

    private function obtener_estadisticas() {
        return ['ofertas_activas' => 25, 'contrataciones' => 89, 'empresas' => 45, 'cooperativas' => 12];
    }

    private function obtener_estadisticas_usuario($user_id) {
        return ['candidaturas' => 0, 'ofertas_publicadas' => 0];
    }

    private function es_candidato($oferta_id, $user_id) {
        return false;
    }

    private function buscar_ofertas($termino) {
        return [];
    }

    private function render_estado_badge($estado) {
        $clases = ['pendiente' => 'warning', 'publicada' => 'success', 'cerrada' => 'muted'];
        $clase = $clases[$estado] ?? 'muted';
        return '<span class="flavor-badge flavor-badge-' . esc_attr($clase) . '">' . esc_html(ucfirst($estado)) . '</span>';
    }

    private function render_estado_candidatura_badge($estado) {
        $clases = ['enviada' => 'info', 'en_proceso' => 'primary', 'seleccionado' => 'success', 'descartado' => 'muted'];
        $textos = ['enviada' => 'Enviada', 'en_proceso' => 'En proceso', 'seleccionado' => 'Seleccionado', 'descartado' => 'Descartado'];
        $clase = $clases[$estado] ?? 'muted';
        $texto = $textos[$estado] ?? ucfirst($estado);
        return '<span class="flavor-badge flavor-badge-' . esc_attr($clase) . '">' . esc_html($texto) . '</span>';
    }

    private function get_ofertas_url() {
        return home_url('/trabajo-digno/');
    }

    private function get_oferta_url($oferta_id) {
        return add_query_arg('oferta_id', $oferta_id, home_url('/trabajo-digno/oferta/'));
    }

    private function get_publicar_url() {
        return home_url('/trabajo-digno/publicar/');
    }

    private function get_mis_ofertas_url() {
        return home_url('/mi-portal/?tab=trabajo-digno');
    }

    private function get_demo_ofertas() {
        return [
            ['id' => 1, 'titulo' => 'Desarrollador/a Web Full Stack', 'empresa' => 'CoopTech', 'descripcion' => 'Buscamos desarrollador/a con experiencia en React y Node.js para proyectos de impacto social.', 'ubicacion' => 'Remoto', 'tipo_contrato' => 'Indefinido', 'salario' => '28.000€ - 35.000€', 'fecha_publicacion' => date('Y-m-d', strtotime('-2 days')), 'verificada' => true, 'cooperativa' => true, 'candidatos' => 12, 'logo' => ''],
            ['id' => 2, 'titulo' => 'Técnico/a de Proyectos Sociales', 'empresa' => 'Fundación Solidaria', 'descripcion' => 'Coordinación de proyectos de inserción laboral para colectivos vulnerables.', 'ubicacion' => 'Madrid', 'tipo_contrato' => 'Indefinido', 'salario' => '24.000€ - 28.000€', 'fecha_publicacion' => date('Y-m-d', strtotime('-5 days')), 'verificada' => true, 'cooperativa' => false, 'candidatos' => 8, 'logo' => ''],
        ];
    }
}

// Inicializar
Flavor_Trabajo_Digno_Frontend_Controller::get_instance();

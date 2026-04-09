<?php
/**
 * Shortcode para renderizar Landing Pages
 *
 * Proporciona el shortcode [flavor_landing] para renderizar
 * las landing pages definidas en las plantillas
 *
 * @package FlavorChatIA
 * @subpackage Frontend
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Clase Flavor_Landing_Shortcode
 *
 * Renderiza landing pages basadas en definiciones de plantillas
 */
class Flavor_Landing_Shortcode {

    /**
     * Instancia singleton
     *
     * @var Flavor_Landing_Shortcode|null
     */
    private static $instancia = null;

    /**
     * Obtiene la instancia singleton
     *
     * @return Flavor_Landing_Shortcode
     */
    public static function get_instance() {
        if (self::$instancia === null) {
            self::$instancia = new self();
        }
        return self::$instancia;
    }

    /**
     * Constructor privado
     */
    private function __construct() {
        if (!shortcode_exists('flavor_landing')) {
            add_shortcode('flavor_landing', [$this, 'renderizar_landing']);
        }
    }

    /**
     * Renderiza la landing page
     *
     * @param array $atts Atributos del shortcode
     * @return string HTML de la landing
     */
    public function renderizar_landing($atts) {
        $atts = shortcode_atts([
            'plantilla' => '',
            'seccion' => '', // Para renderizar una seccion especifica
        ], $atts);

        $plantilla_id = sanitize_key($atts['plantilla']);

        if (empty($plantilla_id)) {
            return $this->mensaje_error(__('Error: Plantilla no especificada', FLAVOR_PLATFORM_TEXT_DOMAIN));
        }

        // Obtener definicion de la plantilla
        $definicion = $this->obtener_definicion_plantilla($plantilla_id);

        if (!$definicion) {
            return $this->mensaje_error(
                sprintf(__('Error: Plantilla "%s" no encontrada', FLAVOR_PLATFORM_TEXT_DOMAIN), esc_html($plantilla_id))
            );
        }

        // Verificar que la landing esta activa
        $config_landing = $definicion['landing'] ?? [];
        if (empty($config_landing['activa'])) {
            return $this->mensaje_error(__('Error: Esta plantilla no tiene landing activa', FLAVOR_PLATFORM_TEXT_DOMAIN));
        }

        $secciones = $config_landing['secciones'] ?? [];
        if (empty($secciones)) {
            return $this->mensaje_error(__('Error: No hay secciones definidas para esta landing', FLAVOR_PLATFORM_TEXT_DOMAIN));
        }

        // Si se especifica una seccion, renderizar solo esa
        if (!empty($atts['seccion'])) {
            return $this->renderizar_seccion_especifica($secciones, $atts['seccion'], $plantilla_id);
        }

        // Renderizar todas las secciones
        return $this->renderizar_todas_secciones($secciones, $plantilla_id);
    }

    /**
     * Obtiene la definicion de una plantilla
     *
     * @param string $plantilla_id ID de la plantilla
     * @return array|null
     */
    private function obtener_definicion_plantilla($plantilla_id) {
        // Intentar obtener desde Flavor_Template_Definitions
        if (class_exists('Flavor_Template_Definitions')) {
            $definitions = Flavor_Template_Definitions::get_instance();
            return $definitions->obtener_definicion($plantilla_id);
        }

        // Fallback: obtener desde el filtro
        $definiciones = apply_filters('flavor_template_definitions', []);
        return $definiciones[$plantilla_id] ?? null;
    }

    /**
     * Renderiza todas las secciones
     *
     * @param array $secciones Array de secciones
     * @param string $plantilla_id ID de la plantilla
     * @return string HTML
     */
    private function renderizar_todas_secciones($secciones, $plantilla_id) {
        $html = sprintf(
            '<div class="flavor-landing flavor-landing--%s">',
            esc_attr($plantilla_id)
        );

        foreach ($secciones as $indice => $seccion) {
            $html .= $this->renderizar_seccion($seccion, $plantilla_id, $indice);
        }

        $html .= '</div>';

        return $html;
    }

    /**
     * Renderiza una seccion especifica por ID o tipo
     *
     * @param array $secciones Todas las secciones
     * @param string $identificador ID o tipo de la seccion
     * @param string $plantilla_id ID de la plantilla
     * @return string HTML
     */
    private function renderizar_seccion_especifica($secciones, $identificador, $plantilla_id) {
        foreach ($secciones as $indice => $seccion) {
            $id_seccion = $seccion['id'] ?? $seccion['tipo'] ?? '';
            if ($id_seccion === $identificador || $seccion['tipo'] === $identificador) {
                return $this->renderizar_seccion($seccion, $plantilla_id, $indice);
            }
        }

        return $this->mensaje_error(
            sprintf(__('Error: Seccion "%s" no encontrada', FLAVOR_PLATFORM_TEXT_DOMAIN), esc_html($identificador))
        );
    }

    /**
     * Renderiza una seccion individual
     *
     * @param array $seccion Datos de la seccion
     * @param string $plantilla_id ID de la plantilla
     * @param int $indice Indice de la seccion
     * @return string HTML
     */
    private function renderizar_seccion($seccion, $plantilla_id, $indice) {
        $tipo = $seccion['tipo'] ?? 'generic';
        $variante = $seccion['variante'] ?? 'default';
        $datos = $seccion['datos'] ?? [];
        $id_seccion = $seccion['id'] ?? $tipo . '_' . $indice;

        // Intentar cargar template parcial primero
        $template_path = $this->buscar_template_seccion($tipo, $variante);

        if ($template_path) {
            return $this->renderizar_template($template_path, [
                'seccion' => $seccion,
                'datos' => $datos,
                'tipo' => $tipo,
                'variante' => $variante,
                'id_seccion' => $id_seccion,
                'plantilla_id' => $plantilla_id,
                'indice' => $indice,
            ]);
        }

        // Fallback: renderizado interno
        return $this->renderizar_seccion_interna($tipo, $variante, $datos, $id_seccion);
    }

    /**
     * Busca el template de una seccion
     *
     * @param string $tipo Tipo de seccion
     * @param string $variante Variante
     * @return string|null Path del template o null
     */
    private function buscar_template_seccion($tipo, $variante) {
        $paths = [
            // Tema hijo
            get_stylesheet_directory() . "/flavor/landing/{$tipo}-{$variante}.php",
            get_stylesheet_directory() . "/flavor/landing/{$tipo}.php",
            // Tema padre
            get_template_directory() . "/flavor/landing/{$tipo}-{$variante}.php",
            get_template_directory() . "/flavor/landing/{$tipo}.php",
            // Plugin
            FLAVOR_CHAT_IA_PATH . "templates/frontend/landing/{$tipo}-{$variante}.php",
            FLAVOR_CHAT_IA_PATH . "templates/frontend/landing/{$tipo}.php",
        ];

        foreach ($paths as $path) {
            if (file_exists($path)) {
                return $path;
            }
        }

        return null;
    }

    /**
     * Renderiza un template con variables
     *
     * @param string $path Path del template
     * @param array $vars Variables para el template
     * @return string HTML
     */
    private function renderizar_template($path, $vars) {
        extract($vars);
        ob_start();
        include $path;
        return ob_get_clean();
    }

    /**
     * Renderiza una seccion usando metodos internos
     *
     * @param string $tipo Tipo de seccion
     * @param string $variante Variante
     * @param array $datos Datos de la seccion
     * @param string $id_seccion ID de la seccion
     * @return string HTML
     */
    private function renderizar_seccion_interna($tipo, $variante, $datos, $id_seccion) {
        $metodo = 'render_' . $tipo;

        if (method_exists($this, $metodo)) {
            return $this->$metodo($datos, $variante, $id_seccion);
        }

        // Seccion generica
        return $this->render_generic($datos, $variante, $id_seccion, $tipo);
    }

    /**
     * Renderiza seccion Hero
     */
    private function render_hero($datos, $variante, $id_seccion) {
        $titulo = $datos['titulo'] ?? '';
        $subtitulo = $datos['subtitulo'] ?? '';
        $cta_texto = $datos['cta_texto'] ?? '';
        $cta_url = $datos['cta_url'] ?? '#';
        $imagen = $datos['imagen'] ?? '';

        $clases = ['flavor-landing__section', 'flavor-hero', "flavor-hero--{$variante}"];

        ob_start();
        ?>
        <section id="<?php echo esc_attr($id_seccion); ?>" class="<?php echo esc_attr(implode(' ', $clases)); ?>">
            <div class="flavor-hero__container">
                <div class="flavor-hero__content">
                    <?php if ($titulo) : ?>
                        <h1 class="flavor-hero__title"><?php echo esc_html($titulo); ?></h1>
                    <?php endif; ?>

                    <?php if ($subtitulo) : ?>
                        <p class="flavor-hero__subtitle"><?php echo esc_html($subtitulo); ?></p>
                    <?php endif; ?>

                    <?php if ($cta_texto) : ?>
                        <div class="flavor-hero__actions">
                            <a href="<?php echo esc_url($cta_url); ?>" class="flavor-btn flavor-btn--primary flavor-hero__cta">
                                <?php echo esc_html($cta_texto); ?>
                            </a>
                        </div>
                    <?php endif; ?>
                </div>

                <?php if ($imagen) : ?>
                    <div class="flavor-hero__image">
                        <img src="<?php echo esc_url($imagen); ?>" alt="<?php echo esc_attr($titulo); ?>">
                    </div>
                <?php endif; ?>
            </div>
        </section>
        <?php
        return ob_get_clean();
    }

    /**
     * Renderiza seccion Features
     */
    private function render_features($datos, $variante, $id_seccion) {
        $titulo = $datos['titulo'] ?? '';
        $items = $datos['items'] ?? [];

        $clases = ['flavor-landing__section', 'flavor-features', "flavor-features--{$variante}"];

        ob_start();
        ?>
        <section id="<?php echo esc_attr($id_seccion); ?>" class="<?php echo esc_attr(implode(' ', $clases)); ?>">
            <div class="flavor-features__container">
                <?php if ($titulo) : ?>
                    <h2 class="flavor-features__title"><?php echo esc_html($titulo); ?></h2>
                <?php endif; ?>

                <?php if (!empty($items)) : ?>
                    <div class="flavor-features__grid">
                        <?php foreach ($items as $item) : ?>
                            <div class="flavor-feature">
                                <?php if (!empty($item['icono'])) : ?>
                                    <div class="flavor-feature__icon">
                                        <span class="dashicons dashicons-<?php echo esc_attr($item['icono']); ?>"></span>
                                    </div>
                                <?php endif; ?>

                                <?php if (!empty($item['titulo'])) : ?>
                                    <h3 class="flavor-feature__title"><?php echo esc_html($item['titulo']); ?></h3>
                                <?php endif; ?>

                                <?php if (!empty($item['descripcion'])) : ?>
                                    <p class="flavor-feature__description"><?php echo esc_html($item['descripcion']); ?></p>
                                <?php endif; ?>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </section>
        <?php
        return ob_get_clean();
    }

    /**
     * Renderiza seccion Grid
     */
    private function render_grid($datos, $variante, $id_seccion) {
        $titulo = $datos['titulo'] ?? '';
        $shortcode = $datos['shortcode'] ?? '';

        $clases = ['flavor-landing__section', 'flavor-grid', "flavor-grid--{$variante}"];

        ob_start();
        ?>
        <section id="<?php echo esc_attr($id_seccion); ?>" class="<?php echo esc_attr(implode(' ', $clases)); ?>">
            <div class="flavor-grid__container">
                <?php if ($titulo) : ?>
                    <h2 class="flavor-grid__title"><?php echo esc_html($titulo); ?></h2>
                <?php endif; ?>

                <?php if ($shortcode) : ?>
                    <div class="flavor-grid__content">
                        <?php echo do_shortcode($shortcode); ?>
                    </div>
                <?php endif; ?>
            </div>
        </section>
        <?php
        return ob_get_clean();
    }

    /**
     * Renderiza seccion Listing
     */
    private function render_listing($datos, $variante, $id_seccion) {
        $titulo = $datos['titulo'] ?? '';
        $shortcode = $datos['shortcode'] ?? '';

        $clases = ['flavor-landing__section', 'flavor-listing', "flavor-listing--{$variante}"];

        ob_start();
        ?>
        <section id="<?php echo esc_attr($id_seccion); ?>" class="<?php echo esc_attr(implode(' ', $clases)); ?>">
            <div class="flavor-listing__container">
                <?php if ($titulo) : ?>
                    <h2 class="flavor-listing__title"><?php echo esc_html($titulo); ?></h2>
                <?php endif; ?>

                <?php if ($shortcode) : ?>
                    <div class="flavor-listing__content">
                        <?php echo do_shortcode($shortcode); ?>
                    </div>
                <?php endif; ?>
            </div>
        </section>
        <?php
        return ob_get_clean();
    }

    /**
     * Renderiza seccion CTA
     */
    private function render_cta($datos, $variante, $id_seccion) {
        $titulo = $datos['titulo'] ?? '';
        $descripcion = $datos['descripcion'] ?? '';
        $boton_texto = $datos['boton_texto'] ?? '';
        $boton_url = $datos['boton_url'] ?? '#';

        $clases = ['flavor-landing__section', 'flavor-cta', "flavor-cta--{$variante}"];

        ob_start();
        ?>
        <section id="<?php echo esc_attr($id_seccion); ?>" class="<?php echo esc_attr(implode(' ', $clases)); ?>">
            <div class="flavor-cta__container">
                <?php if ($titulo) : ?>
                    <h2 class="flavor-cta__title"><?php echo esc_html($titulo); ?></h2>
                <?php endif; ?>

                <?php if ($descripcion) : ?>
                    <p class="flavor-cta__description"><?php echo esc_html($descripcion); ?></p>
                <?php endif; ?>

                <?php if ($boton_texto) : ?>
                    <div class="flavor-cta__actions">
                        <a href="<?php echo esc_url($boton_url); ?>" class="flavor-btn flavor-btn--primary flavor-cta__button">
                            <?php echo esc_html($boton_texto); ?>
                        </a>
                    </div>
                <?php endif; ?>
            </div>
        </section>
        <?php
        return ob_get_clean();
    }

    /**
     * Renderiza seccion generica
     */
    private function render_generic($datos, $variante, $id_seccion, $tipo = 'generic') {
        $titulo = $datos['titulo'] ?? '';
        $contenido = $datos['contenido'] ?? $datos['descripcion'] ?? '';
        $shortcode = $datos['shortcode'] ?? '';

        $clases = ['flavor-landing__section', "flavor-section--{$tipo}", "flavor-section--{$variante}"];

        ob_start();
        ?>
        <section id="<?php echo esc_attr($id_seccion); ?>" class="<?php echo esc_attr(implode(' ', $clases)); ?>">
            <div class="flavor-section__container">
                <?php if ($titulo) : ?>
                    <h2 class="flavor-section__title"><?php echo esc_html($titulo); ?></h2>
                <?php endif; ?>

                <?php if ($contenido) : ?>
                    <div class="flavor-section__content">
                        <?php echo wp_kses_post($contenido); ?>
                    </div>
                <?php endif; ?>

                <?php if ($shortcode) : ?>
                    <div class="flavor-section__shortcode">
                        <?php echo do_shortcode($shortcode); ?>
                    </div>
                <?php endif; ?>
            </div>
        </section>
        <?php
        return ob_get_clean();
    }

    /**
     * Genera mensaje de error
     *
     * @param string $mensaje Mensaje de error
     * @return string HTML
     */
    private function mensaje_error($mensaje) {
        if (current_user_can('manage_options')) {
            return sprintf(
                '<div class="flavor-landing-error" style="background:#fee2e2;color:#991b1b;padding:1rem;border-radius:0.5rem;margin:1rem 0;">%s</div>',
                esc_html($mensaje)
            );
        }
        return '';
    }
}

// Inicializar
Flavor_Landing_Shortcode::get_instance();

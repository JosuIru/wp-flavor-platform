<?php
/**
 * Widget Shortcodes - Expone widgets del Dashboard como shortcodes
 *
 * Permite usar los widgets del Dashboard Unificado en cualquier página
 * mediante shortcodes, integrándose con el Visual Builder Pro.
 *
 * @package FlavorPlatform
 * @subpackage Dashboard
 * @since 4.2.0
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Clase para gestionar shortcodes de widgets
 *
 * @since 4.2.0
 */
class Flavor_Widget_Shortcodes {

    /**
     * Instancia singleton
     *
     * @var Flavor_Widget_Shortcodes|null
     */
    private static $instance = null;

    /**
     * Flag para saber si ya se encolaron los assets
     *
     * @var bool
     */
    private $assets_enqueued = false;

    /**
     * Obtiene la instancia singleton
     *
     * @return Flavor_Widget_Shortcodes
     */
    public static function get_instance(): Flavor_Widget_Shortcodes {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Constructor privado
     */
    private function __construct() {
        add_action('init', [$this, 'register_shortcodes'], 25);
        add_action('wp_enqueue_scripts', [$this, 'register_assets']);
    }

    /**
     * Registra los shortcodes de widgets
     *
     * @return void
     */
    public function register_shortcodes(): void {
        // Shortcode principal para un widget específico
        add_shortcode('flavor_widget', [$this, 'render_widget_shortcode']);

        // Shortcode para mostrar múltiples widgets
        add_shortcode('flavor_widgets', [$this, 'render_widgets_shortcode']);

        // Shortcode para mostrar widgets por categoría
        add_shortcode('flavor_widgets_categoria', [$this, 'render_category_shortcode']);

        // Shortcode para el selector de widgets (para administradores)
        add_shortcode('flavor_widget_selector', [$this, 'render_selector_shortcode']);
    }

    /**
     * Registra los assets necesarios
     *
     * @return void
     */
    public function register_assets(): void {
        wp_register_style(
            'flavor-widget-shortcodes',
            FLAVOR_PLATFORM_URL . 'assets/css/modules/widget-shortcodes.css',
            ['flavor-base-css'],
            FLAVOR_PLATFORM_VERSION
        );

        wp_register_script(
            'flavor-widget-shortcodes',
            FLAVOR_PLATFORM_URL . 'assets/js/widget-shortcodes.js',
            ['jquery'],
            FLAVOR_PLATFORM_VERSION,
            true
        );

        wp_localize_script('flavor-widget-shortcodes', 'flavorWidgetShortcodes', [
            'ajaxUrl' => admin_url('admin-ajax.php'),
            'nonce'   => wp_create_nonce('flavor_widget_shortcode'),
            'i18n'    => [
                'loading'    => __('Cargando...', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'error'      => __('Error al cargar el widget', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'retry'      => __('Reintentar', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'noAccess'   => __('No tienes acceso a este widget', FLAVOR_PLATFORM_TEXT_DOMAIN),
            ],
        ]);
    }

    /**
     * Encola los assets cuando se necesitan
     *
     * @return void
     */
    private function enqueue_assets(): void {
        if ($this->assets_enqueued) {
            return;
        }

        wp_enqueue_style('flavor-widget-shortcodes');
        wp_enqueue_script('flavor-widget-shortcodes');

        // También encolar estilos del dashboard unificado
        if (file_exists(FLAVOR_PLATFORM_PATH . 'assets/css/layouts/unified-dashboard.css')) {
            wp_enqueue_style(
                'flavor-unified-dashboard',
                FLAVOR_PLATFORM_URL . 'assets/css/layouts/unified-dashboard.css',
                [],
                FLAVOR_PLATFORM_VERSION
            );
        }

        $this->assets_enqueued = true;
    }

    /**
     * Renderiza un widget individual
     *
     * [flavor_widget id="eventos" titulo="Próximos Eventos" estilo="elevated"]
     *
     * @param array $atts Atributos del shortcode
     * @return string HTML del widget
     */
    public function render_widget_shortcode($atts): string {
        $atts = shortcode_atts([
            'id'              => '',
            'titulo'          => '',
            'titulo_visible'  => 'true',
            'estilo'          => 'elevated', // elevated, outlined, flat, glass
            'animacion'       => 'true',
            'acciones'        => 'true',
            'refresh'         => 'false',
            'cache'           => 'true',
            'class'           => '',
        ], $atts, 'flavor_widget');

        // Validar ID
        if (empty($atts['id'])) {
            return $this->render_error(__('ID de widget no especificado', FLAVOR_PLATFORM_TEXT_DOMAIN));
        }

        // Obtener el registro de widgets
        $registry = Flavor_Widget_Registry::get_instance();
        $widget = $registry->get_widget($atts['id']);

        if (!$widget) {
            return $this->render_error(
                sprintf(__('Widget "%s" no encontrado', FLAVOR_PLATFORM_TEXT_DOMAIN), esc_html($atts['id']))
            );
        }

        // Verificar permisos si el widget los requiere
        $config = $widget->get_widget_config();
        if (!$this->user_can_view_widget($widget, $config)) {
            return $this->render_login_required($config);
        }

        // Encolar assets
        $this->enqueue_assets();

        // Construir clases CSS
        $classes = ['fws-widget', 'fws-widget--' . $atts['estilo']];
        if ($atts['animacion'] === 'true') {
            $classes[] = 'fws-widget--animated';
        }
        if (!empty($atts['class'])) {
            $classes[] = esc_attr($atts['class']);
        }

        // Título personalizado o del widget
        $titulo = !empty($atts['titulo']) ? $atts['titulo'] : ($config['title'] ?? '');
        $mostrar_titulo = $atts['titulo_visible'] === 'true';
        $mostrar_acciones = $atts['acciones'] === 'true' && !empty($config['actions']);

        // Renderizar
        ob_start();
        ?>
        <div class="<?php echo esc_attr(implode(' ', $classes)); ?>"
             data-widget-id="<?php echo esc_attr($atts['id']); ?>"
             data-refresh="<?php echo esc_attr($atts['refresh']); ?>">

            <?php if ($mostrar_titulo || $mostrar_acciones): ?>
            <header class="fws-widget__header">
                <?php if ($mostrar_titulo): ?>
                <div class="fws-widget__title-wrap">
                    <?php if (!empty($config['icon'])): ?>
                    <span class="fws-widget__icon">
                        <span class="dashicons <?php echo esc_attr($config['icon']); ?>"></span>
                    </span>
                    <?php endif; ?>
                    <h3 class="fws-widget__title"><?php echo esc_html($titulo); ?></h3>
                </div>
                <?php endif; ?>

                <?php if ($mostrar_acciones): ?>
                <nav class="fws-widget__actions">
                    <?php foreach ($config['actions'] as $action): ?>
                    <a href="<?php echo esc_url($action['url'] ?? '#'); ?>"
                       class="fws-widget__action"
                       title="<?php echo esc_attr($action['label'] ?? ''); ?>">
                        <?php if (!empty($action['icon'])): ?>
                        <span class="dashicons <?php echo esc_attr($action['icon']); ?>"></span>
                        <?php endif; ?>
                        <?php if (!empty($action['label']) && ($action['show_label'] ?? false)): ?>
                        <span class="fws-widget__action-label"><?php echo esc_html($action['label']); ?></span>
                        <?php endif; ?>
                    </a>
                    <?php endforeach; ?>
                </nav>
                <?php endif; ?>
            </header>
            <?php endif; ?>

            <div class="fws-widget__body">
                <?php
                try {
                    $widget->render_widget();
                } catch (Exception $e) {
                    echo $this->render_widget_error($e->getMessage());
                }
                ?>
            </div>
        </div>
        <?php
        return ob_get_clean();
    }

    /**
     * Renderiza múltiples widgets
     *
     * [flavor_widgets ids="eventos,reservas,socios" columnas="3"]
     *
     * @param array $atts Atributos del shortcode
     * @return string HTML de los widgets
     */
    public function render_widgets_shortcode($atts): string {
        $atts = shortcode_atts([
            'ids'       => '',
            'columnas'  => '2',
            'gap'       => 'normal', // compact, normal, comfortable
            'estilo'    => 'elevated',
            'animacion' => 'true',
        ], $atts, 'flavor_widgets');

        if (empty($atts['ids'])) {
            return $this->render_error(__('No se especificaron widgets', FLAVOR_PLATFORM_TEXT_DOMAIN));
        }

        $widget_ids = array_map('trim', explode(',', $atts['ids']));

        if (empty($widget_ids)) {
            return '';
        }

        $this->enqueue_assets();

        $gap_class = 'fws-grid--gap-' . $atts['gap'];
        $col_class = 'fws-grid--cols-' . min(4, max(1, intval($atts['columnas'])));

        ob_start();
        ?>
        <div class="fws-widgets-grid <?php echo esc_attr($col_class . ' ' . $gap_class); ?>">
            <?php
            foreach ($widget_ids as $widget_id) {
                echo do_shortcode(sprintf(
                    '[flavor_widget id="%s" estilo="%s" animacion="%s"]',
                    esc_attr($widget_id),
                    esc_attr($atts['estilo']),
                    esc_attr($atts['animacion'])
                ));
            }
            ?>
        </div>
        <?php
        return ob_get_clean();
    }

    /**
     * Renderiza widgets de una categoría
     *
     * [flavor_widgets_categoria categoria="economia" limite="4"]
     *
     * @param array $atts Atributos del shortcode
     * @return string HTML de los widgets
     */
    public function render_category_shortcode($atts): string {
        $atts = shortcode_atts([
            'categoria' => '',
            'limite'    => '4',
            'columnas'  => '2',
            'gap'       => 'normal',
            'estilo'    => 'elevated',
            'animacion' => 'true',
            'titulo'    => 'true',
        ], $atts, 'flavor_widgets_categoria');

        if (empty($atts['categoria'])) {
            return $this->render_error(__('Categoría no especificada', FLAVOR_PLATFORM_TEXT_DOMAIN));
        }

        $registry = Flavor_Widget_Registry::get_instance();
        $widgets = $registry->get_by_category($atts['categoria']);

        if (empty($widgets)) {
            return $this->render_empty(__('No hay widgets en esta categoría', FLAVOR_PLATFORM_TEXT_DOMAIN));
        }

        // Limitar cantidad
        $limite = intval($atts['limite']);
        if ($limite > 0) {
            $widgets = array_slice($widgets, 0, $limite, true);
        }

        $widget_ids = array_keys($widgets);

        $this->enqueue_assets();

        $categories = $registry->get_categories();
        $cat_info = $categories[$atts['categoria']] ?? null;

        ob_start();

        if ($atts['titulo'] === 'true' && $cat_info): ?>
        <div class="fws-category-header">
            <span class="fws-category-icon" style="background-color: <?php echo esc_attr($cat_info['color'] ?? '#6b7280'); ?>15; color: <?php echo esc_attr($cat_info['color'] ?? '#6b7280'); ?>;">
                <span class="dashicons <?php echo esc_attr($cat_info['icon'] ?? 'dashicons-category'); ?>"></span>
            </span>
            <h2 class="fws-category-title"><?php echo esc_html($cat_info['label'] ?? $atts['categoria']); ?></h2>
            <?php if (!empty($cat_info['description'])): ?>
            <p class="fws-category-description"><?php echo esc_html($cat_info['description']); ?></p>
            <?php endif; ?>
        </div>
        <?php endif;

        echo do_shortcode(sprintf(
            '[flavor_widgets ids="%s" columnas="%s" gap="%s" estilo="%s" animacion="%s"]',
            esc_attr(implode(',', $widget_ids)),
            esc_attr($atts['columnas']),
            esc_attr($atts['gap']),
            esc_attr($atts['estilo']),
            esc_attr($atts['animacion'])
        ));

        return ob_get_clean();
    }

    /**
     * Renderiza un selector de widgets (para admin/desarrollo)
     *
     * [flavor_widget_selector]
     *
     * @param array $atts Atributos del shortcode
     * @return string HTML del selector
     */
    public function render_selector_shortcode($atts): string {
        // Solo para administradores
        if (!current_user_can('manage_options')) {
            return '';
        }

        $registry = Flavor_Widget_Registry::get_instance();
        $all_widgets = $registry->get_all(true);
        $categories = $registry->get_categories();

        $this->enqueue_assets();

        ob_start();
        ?>
        <div class="fws-selector">
            <h3 class="fws-selector__title">
                <span class="dashicons dashicons-screenoptions"></span>
                <?php _e('Widgets Disponibles', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
            </h3>
            <p class="fws-selector__description">
                <?php _e('Copia el shortcode del widget que quieras insertar:', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
            </p>

            <?php foreach ($categories as $cat_id => $cat_info):
                $cat_widgets = array_filter($all_widgets, function($w) use ($cat_id) {
                    return ($w['config']['category'] ?? '') === $cat_id;
                });

                if (empty($cat_widgets)) continue;
            ?>
            <div class="fws-selector__category">
                <h4 class="fws-selector__category-title" style="color: <?php echo esc_attr($cat_info['color']); ?>;">
                    <span class="dashicons <?php echo esc_attr($cat_info['icon']); ?>"></span>
                    <?php echo esc_html($cat_info['label']); ?>
                </h4>
                <div class="fws-selector__widgets">
                    <?php foreach ($cat_widgets as $widget_id => $widget_data): ?>
                    <div class="fws-selector__widget">
                        <div class="fws-selector__widget-info">
                            <?php if (!empty($widget_data['config']['icon'])): ?>
                            <span class="dashicons <?php echo esc_attr($widget_data['config']['icon']); ?>"></span>
                            <?php endif; ?>
                            <span class="fws-selector__widget-name">
                                <?php echo esc_html($widget_data['config']['title'] ?? $widget_id); ?>
                            </span>
                        </div>
                        <code class="fws-selector__shortcode" onclick="this.select(); document.execCommand('copy');">
                            [flavor_widget id="<?php echo esc_attr($widget_id); ?>"]
                        </code>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
            <?php endforeach; ?>

            <div class="fws-selector__tips">
                <h4><?php _e('Ejemplos de uso:', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></h4>
                <code>[flavor_widget id="eventos" estilo="glass"]</code>
                <code>[flavor_widgets ids="eventos,reservas,socios" columnas="3"]</code>
                <code>[flavor_widgets_categoria categoria="economia" limite="4"]</code>
            </div>
        </div>
        <?php
        return ob_get_clean();
    }

    /**
     * Verifica si el usuario puede ver el widget
     *
     * @param Flavor_Dashboard_Widget_Interface $widget Widget a verificar
     * @param array $config Configuración del widget
     * @return bool
     */
    private function user_can_view_widget($widget, array $config): bool {
        // Si el widget requiere login
        $requires_login = $config['requires_login'] ?? true;

        if ($requires_login && !is_user_logged_in()) {
            return false;
        }

        // Si tiene capability requerida
        $capability = $config['capability'] ?? null;
        if ($capability && !current_user_can($capability)) {
            return false;
        }

        // Filtro para verificaciones personalizadas
        return apply_filters('flavor_widget_shortcode_user_can_view', true, $widget, $config);
    }

    /**
     * Renderiza mensaje de error
     *
     * @param string $message Mensaje de error
     * @return string HTML
     */
    private function render_error(string $message): string {
        return sprintf(
            '<div class="fws-error"><span class="dashicons dashicons-warning"></span> %s</div>',
            esc_html($message)
        );
    }

    /**
     * Renderiza error dentro del widget
     *
     * @param string $message Mensaje de error
     * @return string HTML
     */
    private function render_widget_error(string $message): string {
        return sprintf(
            '<div class="fws-widget-error"><span class="dashicons dashicons-warning"></span><p>%s</p></div>',
            esc_html($message)
        );
    }

    /**
     * Renderiza estado vacío
     *
     * @param string $message Mensaje
     * @return string HTML
     */
    private function render_empty(string $message): string {
        return sprintf(
            '<div class="fws-empty"><span class="dashicons dashicons-info-outline"></span> %s</div>',
            esc_html($message)
        );
    }

    /**
     * Renderiza mensaje de login requerido
     *
     * @param array $config Configuración del widget
     * @return string HTML
     */
    private function render_login_required(array $config): string {
        $login_url = wp_login_url(get_permalink());

        return sprintf(
            '<div class="fws-login-required">
                <span class="dashicons dashicons-lock"></span>
                <p>%s</p>
                <a href="%s" class="fws-login-button">%s</a>
            </div>',
            esc_html__('Inicia sesión para ver este contenido', FLAVOR_PLATFORM_TEXT_DOMAIN),
            esc_url($login_url),
            esc_html__('Iniciar sesión', FLAVOR_PLATFORM_TEXT_DOMAIN)
        );
    }

    /**
     * Obtiene la lista de widgets disponibles para VBP
     *
     * @return array Lista de widgets con su configuración
     */
    public static function get_widgets_for_vbp(): array {
        $registry = Flavor_Widget_Registry::get_instance();
        $all_widgets = $registry->get_all(true);
        $categories = $registry->get_categories();

        $vbp_widgets = [];

        foreach ($all_widgets as $widget_id => $widget_data) {
            $config = $widget_data['config'];
            $category = $config['category'] ?? 'sistema';
            $cat_info = $categories[$category] ?? ['label' => $category, 'icon' => 'dashicons-admin-generic'];

            $vbp_widgets[] = [
                'id'          => $widget_id,
                'title'       => $config['title'] ?? $widget_id,
                'icon'        => $config['icon'] ?? 'dashicons-screenoptions',
                'category'    => $category,
                'category_label' => $cat_info['label'],
                'shortcode'   => sprintf('[flavor_widget id="%s"]', $widget_id),
                'description' => $config['description'] ?? '',
            ];
        }

        return $vbp_widgets;
    }
}

// Inicializar
add_action('plugins_loaded', function() {
    Flavor_Widget_Shortcodes::get_instance();
}, 15);

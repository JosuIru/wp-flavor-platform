<?php
/**
 * Renderizador de Widgets del Dashboard Unificado
 *
 * Encapsula la logica de renderizado de widgets con estructura
 * HTML estandarizada, soportando diferentes tamanos y layouts.
 *
 * @package FlavorChatIA
 * @subpackage Dashboard
 * @since 4.0.0
 */

// Evitar acceso directo
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Clase Widget Renderer
 *
 * @since 4.0.0
 */
class Flavor_Widget_Renderer {

    /**
     * Instancia singleton
     *
     * @var Flavor_Widget_Renderer|null
     */
    private static $instance = null;

    /**
     * Mapeo de tamanos a clases CSS
     *
     * @var array
     */
    private $size_classes = [
        'small'  => 'fud-widget--small',   // 1 columna
        'medium' => 'fud-widget--medium',  // 2 columnas
        'large'  => 'fud-widget--large',   // 3 columnas (ancho completo)
    ];

    /**
     * Obtiene la instancia singleton
     *
     * @return Flavor_Widget_Renderer
     */
    public static function get_instance(): Flavor_Widget_Renderer {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Constructor privado
     */
    private function __construct() {
        // Constructor vacio - singleton
    }

    /**
     * Renderiza un widget completo
     *
     * @param Flavor_Dashboard_Widget_Interface $widget Widget a renderizar
     * @param array $options Opciones de renderizado
     * @return string HTML del widget
     */
    public function render(Flavor_Dashboard_Widget_Interface $widget, array $options = []): string {
        $config = $widget->get_widget_config();

        $options = wp_parse_args($options, [
            'draggable'   => true,
            'collapsible' => true,
            'closable'    => false,
            'lazy_load'   => false,
        ]);

        ob_start();
        $this->render_widget_wrapper($widget, $config, $options);
        return ob_get_clean();
    }

    /**
     * Renderiza el wrapper del widget
     *
     * Incluye atributos ARIA para accesibilidad WCAG 2.1 AA.
     *
     * @param Flavor_Dashboard_Widget_Interface $widget Widget
     * @param array $config Configuracion
     * @param array $options Opciones
     * @return void
     */
    private function render_widget_wrapper(
        Flavor_Dashboard_Widget_Interface $widget,
        array $config,
        array $options
    ): void {
        $widget_id = esc_attr($config['id'] ?? '');
        $size_class = $this->size_classes[$config['size'] ?? 'medium'] ?? $this->size_classes['medium'];
        $category = esc_attr($config['category'] ?? 'sistema');
        $refreshable = !empty($config['refreshable']) ? 'true' : 'false';
        $title = esc_attr($config['title'] ?? '');
        $description = esc_attr($config['description'] ?? '');
        $level_class = esc_attr($config['level_class'] ?? 'fl-widget--standard');

        // IDs unicos para ARIA
        $header_id = 'fl-widget-header-' . $widget_id;
        $body_id = 'fl-widget-body-' . $widget_id;

        $wrapper_classes = [
            'fud-widget',
            'fl-widget',
            $size_class,
            $level_class,
            'fud-widget--' . $category,
            'fl-widget--' . $category,
        ];

        if ($options['draggable']) {
            $wrapper_classes[] = 'fud-widget--draggable';
            $wrapper_classes[] = 'fl-widget--draggable';
        }

        if ($options['lazy_load']) {
            $wrapper_classes[] = 'fud-widget--lazy';
            $wrapper_classes[] = 'fl-widget--lazy';
        }

        $wrapper_class = implode(' ', array_map('esc_attr', $wrapper_classes));

        // Construir atributos ARIA
        $aria_attrs = [
            'role'            => 'region',
            'aria-labelledby' => $header_id,
        ];

        if (!empty($description)) {
            $aria_attrs['aria-description'] = $description;
        }

        if ($options['lazy_load']) {
            $aria_attrs['aria-busy'] = 'true';
        }

        $aria_string = '';
        foreach ($aria_attrs as $aria_attr => $aria_valor) {
            $aria_string .= sprintf(' %s="%s"', esc_attr($aria_attr), esc_attr($aria_valor));
        }

        ?>
        <article class="<?php echo $wrapper_class; ?>"
             data-widget-id="<?php echo $widget_id; ?>"
             data-category="<?php echo $category; ?>"
             data-severity="<?php echo esc_attr($config['severity_slug'] ?? ''); ?>"
             data-refreshable="<?php echo $refreshable; ?>"
             <?php echo $aria_string; ?>
             tabindex="0">

            <?php $this->render_widget_header($config, $options, $header_id); ?>

            <div class="fud-widget__body fl-widget__body"
                 id="<?php echo esc_attr($body_id); ?>">
                <?php if ($options['lazy_load']): ?>
                    <div class="fud-widget__loading fl-widget__loading" role="status">
                        <span class="fud-loading-spinner fl-loading-spinner" aria-hidden="true"></span>
                        <span class="fud-loading-text fl-loading-text"><?php esc_html_e('Cargando...', 'flavor-chat-ia'); ?></span>
                        <span class="fl-sr-only"><?php printf(esc_html__('Cargando contenido de %s', 'flavor-chat-ia'), $title); ?></span>
                    </div>
                <?php else: ?>
                    <?php $widget->render_widget(); ?>
                <?php endif; ?>
            </div>

        </article>
        <?php
    }

    /**
     * Renderiza el header del widget
     *
     * Incluye atributos ARIA para accesibilidad.
     *
     * @param array $config Configuracion del widget
     * @param array $options Opciones de renderizado
     * @param string $header_id ID para aria-labelledby
     * @return void
     */
    private function render_widget_header(array $config, array $options, string $header_id = ''): void {
        $title = esc_html($config['title'] ?? '');
        $icon = esc_attr($config['icon'] ?? 'dashicons-admin-generic');
        $actions = $config['actions'] ?? [];
        $widget_id = esc_attr($config['id'] ?? '');
        $module_id = sanitize_key(str_replace('-', '_', (string) ($config['module'] ?? '')));
        $semantics = $this->get_widget_semantics($module_id);
        $severity_slug = sanitize_key((string) ($config['severity_slug'] ?? ''));
        $severity_label = (string) ($config['severity_label'] ?? '');
        $severity_reason = (string) ($config['severity_reason'] ?? '');

        if (empty($header_id)) {
            $header_id = 'fl-widget-header-' . $widget_id;
        }
        ?>
        <header class="fud-widget__header fl-widget__header">
            <div class="fud-widget__title-wrap fl-widget__title-wrap">
                <?php if ($options['draggable']): ?>
                    <button type="button"
                            class="fud-widget__drag-handle fl-widget__drag-handle"
                            aria-label="<?php printf(esc_attr__('Arrastrar %s para reordenar', 'flavor-chat-ia'), $title); ?>"
                            aria-describedby="fl-drag-instructions"
                            tabindex="0">
                        <span class="dashicons dashicons-move" aria-hidden="true"></span>
                    </button>
                <?php endif; ?>

                <span class="fud-widget__icon fl-widget__icon" aria-hidden="true">
                    <span class="dashicons <?php echo $icon; ?>"></span>
                </span>
                <div class="fud-widget__title-block fl-widget__title-block">
                    <?php if (!empty($semantics['kind']) || !empty($semantics['context'])) : ?>
                        <div class="fud-widget__meta fl-widget__meta">
                            <?php if (!empty($semantics['kind'])) : ?>
                                <span class="fud-widget__kind fud-widget__kind--<?php echo esc_attr($semantics['kind_slug']); ?>">
                                    <?php echo esc_html($semantics['kind']); ?>
                                </span>
                            <?php endif; ?>
                            <?php if (!empty($semantics['context'])) : ?>
                                <span class="fud-widget__context"><?php echo esc_html($semantics['context']); ?></span>
                            <?php endif; ?>
                            <?php if ($severity_slug !== '' && $severity_label !== '') : ?>
                                <span class="fud-widget__severity fud-widget__severity--<?php echo esc_attr($severity_slug); ?>" <?php if ($severity_reason !== '') : ?>title="<?php echo esc_attr($severity_reason); ?>"<?php endif; ?>>
                                    <?php echo esc_html($severity_label); ?>
                                </span>
                            <?php endif; ?>
                        </div>
                    <?php endif; ?>
                    <h3 class="fud-widget__title fl-widget__title" id="<?php echo esc_attr($header_id); ?>">
                        <?php echo $title; ?>
                    </h3>
                </div>
            </div>

            <nav class="fud-widget__actions fl-widget__actions" aria-label="<?php printf(esc_attr__('Acciones de %s', 'flavor-chat-ia'), $title); ?>">
                <?php $this->render_header_actions($actions, $widget_id, $options, $title); ?>
            </nav>
        </header>
        <?php
    }

    /**
     * Obtiene una semántica corta para el widget según el módulo.
     *
     * @param string $module_id
     * @return array<string,string>
     */
    private function get_widget_semantics(string $module_id): array {
        if ($module_id === '' || !class_exists('Flavor_Chat_Module_Loader')) {
            return [];
        }

        $loader = Flavor_Chat_Module_Loader::get_instance();
        $registered_modules = $loader ? $loader->get_registered_modules() : [];
        $module_data = $registered_modules[$module_id] ?? null;

        if (!is_array($module_data)) {
            return [];
        }

        $ecosystem = is_array($module_data['ecosystem'] ?? null) ? $module_data['ecosystem'] : [];
        $dashboard = is_array($module_data['dashboard'] ?? null) ? $module_data['dashboard'] : [];
        $role = (string) ($ecosystem['module_role'] ?? 'vertical');
        $display_role = (string) ($ecosystem['display_role'] ?? $role);

        $kind_map = [
            'base' => __('Coordinar', 'flavor-chat-ia'),
            'vertical' => __('Operar', 'flavor-chat-ia'),
            'transversal' => __('Entender', 'flavor-chat-ia'),
            'standalone' => __('Gestionar', 'flavor-chat-ia'),
            'base-standalone' => __('Gestionar', 'flavor-chat-ia'),
        ];

        $context_labels = [
            'comunidad' => __('Comunidad', 'flavor-chat-ia'),
            'gobernanza' => __('Gobernanza', 'flavor-chat-ia'),
            'participacion' => __('Participación', 'flavor-chat-ia'),
            'transparencia' => __('Transparencia', 'flavor-chat-ia'),
            'energia' => __('Energía', 'flavor-chat-ia'),
            'consumo' => __('Consumo local', 'flavor-chat-ia'),
            'cuidados' => __('Cuidados', 'flavor-chat-ia'),
            'sostenibilidad' => __('Sostenibilidad', 'flavor-chat-ia'),
            'impacto' => __('Impacto', 'flavor-chat-ia'),
            'aprendizaje' => __('Aprendizaje', 'flavor-chat-ia'),
            'saberes' => __('Saberes', 'flavor-chat-ia'),
            'agenda' => __('Agenda', 'flavor-chat-ia'),
            'eventos' => __('Encuentros', 'flavor-chat-ia'),
            'socios' => __('Socios', 'flavor-chat-ia'),
            'membresia' => __('Membresía', 'flavor-chat-ia'),
            'cuenta' => __('Cuenta', 'flavor-chat-ia'),
            'colectivos' => __('Colectivos', 'flavor-chat-ia'),
            'asociacion' => __('Asociación', 'flavor-chat-ia'),
            'coordinacion' => __('Coordinación', 'flavor-chat-ia'),
        ];

        $contexts = (array) ($dashboard['admin_contexts'] ?? $dashboard['client_contexts'] ?? []);
        $primary_context = (string) reset($contexts);
        $kind_slug = $display_role !== '' ? $display_role : 'vertical';

        return [
            'kind' => $kind_map[$kind_slug] ?? __('Operar', 'flavor-chat-ia'),
            'kind_slug' => sanitize_html_class($kind_slug),
            'context' => $context_labels[$primary_context] ?? (
                $primary_context !== ''
                    ? ucwords(str_replace('_', ' ', $primary_context))
                    : ''
            ),
        ];
    }

    /**
     * Renderiza las acciones del header
     *
     * Incluye aria-labels descriptivos para accesibilidad.
     *
     * @param array $actions Acciones
     * @param string $widget_id ID del widget
     * @param array $options Opciones
     * @param string $widget_title Titulo del widget para aria-labels contextuales
     * @return void
     */
    private function render_header_actions(array $actions, string $widget_id, array $options, string $widget_title = ''): void {
        // Acciones personalizadas del widget
        foreach ($actions as $action) {
            $action_id = esc_attr($action['id'] ?? '');
            $action_icon = esc_attr($action['icon'] ?? 'dashicons-admin-generic');
            $action_title = esc_attr($action['title'] ?? '');
            $action_type = esc_attr($action['type'] ?? 'custom');
            $action_url = esc_url($action['url'] ?? '#');

            // Crear aria-label contextual
            $aria_label = !empty($widget_title)
                ? sprintf('%s - %s', $action_title, $widget_title)
                : $action_title;

            if ($action_type === 'link') {
                printf(
                    '<a href="%s"
                        class="fud-widget__action fl-widget__action fud-widget__action--%s fl-widget__action--%s"
                        aria-label="%s"
                        title="%s">
                        <span class="dashicons %s" aria-hidden="true"></span>
                    </a>',
                    $action_url,
                    $action_id,
                    $action_id,
                    esc_attr($aria_label),
                    $action_title,
                    $action_icon
                );
            } else {
                printf(
                    '<button type="button"
                            class="fud-widget__action fl-widget__action fud-widget__action--%s fl-widget__action--%s"
                            data-action="%s"
                            data-widget="%s"
                            aria-label="%s"
                            title="%s">
                        <span class="dashicons %s" aria-hidden="true"></span>
                    </button>',
                    $action_id,
                    $action_id,
                    $action_type,
                    $widget_id,
                    esc_attr($aria_label),
                    $action_title,
                    $action_icon
                );
            }
        }

        // Accion de colapsar
        if ($options['collapsible']) {
            $collapse_label = !empty($widget_title)
                ? sprintf(__('Colapsar o expandir %s', 'flavor-chat-ia'), $widget_title)
                : __('Colapsar o expandir widget', 'flavor-chat-ia');
            ?>
            <button type="button"
                    class="fud-widget__action fl-widget__action fud-widget__action--collapse fl-widget__action--collapse"
                    data-action="collapse"
                    data-widget="<?php echo $widget_id; ?>"
                    aria-label="<?php echo esc_attr($collapse_label); ?>"
                    aria-expanded="true"
                    aria-controls="fl-widget-body-<?php echo $widget_id; ?>"
                    title="<?php esc_attr_e('Colapsar/Expandir', 'flavor-chat-ia'); ?>">
                <span class="dashicons dashicons-arrow-up-alt2" aria-hidden="true"></span>
            </button>
            <?php
        }

        // Accion de cerrar
        if ($options['closable']) {
            $close_label = !empty($widget_title)
                ? sprintf(__('Ocultar %s', 'flavor-chat-ia'), $widget_title)
                : __('Ocultar widget', 'flavor-chat-ia');
            ?>
            <button type="button"
                    class="fud-widget__action fl-widget__action fud-widget__action--close fl-widget__action--close"
                    data-action="close"
                    data-widget="<?php echo $widget_id; ?>"
                    aria-label="<?php echo esc_attr($close_label); ?>"
                    title="<?php esc_attr_e('Ocultar widget', 'flavor-chat-ia'); ?>">
                <span class="dashicons dashicons-no-alt" aria-hidden="true"></span>
            </button>
            <?php
        }
    }

    /**
     * Renderiza multiples widgets en un grid
     *
     * @param array $widgets Array de widgets
     * @param array $options Opciones globales
     * @return string HTML del grid
     */
    public function render_grid(array $widgets, array $options = []): string {
        $options = wp_parse_args($options, [
            'columns'     => 3,
            'draggable'   => true,
            'collapsible' => true,
            'closable'    => false,
            'lazy_load'   => false,
        ]);

        ob_start();
        ?>
        <div class="fud-widgets-grid fud-widgets-grid--cols-<?php echo absint($options['columns']); ?>"
             id="fud-widgets-container">
            <?php
            foreach ($widgets as $widget) {
                if ($widget instanceof Flavor_Dashboard_Widget_Interface) {
                    echo $this->render($widget, $options);
                }
            }
            ?>
        </div>
        <?php
        return ob_get_clean();
    }

    /**
     * Renderiza widgets agrupados por categoria
     *
     * @param array $widgets_por_categoria Widgets agrupados
     * @param array $categorias Definicion de categorias
     * @param array $options Opciones
     * @return string HTML
     */
    public function render_grouped(
        array $widgets_por_categoria,
        array $categorias,
        array $options = []
    ): string {
        ob_start();
        ?>
        <div class="fud-widgets-grouped">
            <?php foreach ($widgets_por_categoria as $categoria_id => $widgets): ?>
                <?php
                if (empty($widgets)) {
                    continue;
                }

                $categoria_info = $categorias[$categoria_id] ?? [
                    'label' => ucfirst($categoria_id),
                    'icon'  => 'dashicons-admin-generic',
                ];
                ?>
                <div class="fud-widget-group" data-category="<?php echo esc_attr($categoria_id); ?>">
                    <div class="fud-widget-group__header">
                        <span class="dashicons <?php echo esc_attr($categoria_info['icon']); ?>"></span>
                        <h2 class="fud-widget-group__title">
                            <?php echo esc_html($categoria_info['label']); ?>
                            <span class="fud-widget-group__count">(<?php echo count($widgets); ?>)</span>
                        </h2>
                    </div>
                    <div class="fud-widget-group__content fud-widgets-grid">
                        <?php
                        foreach ($widgets as $widget) {
                            echo $this->render($widget, $options);
                        }
                        ?>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
        <?php
        return ob_get_clean();
    }

    /**
     * Renderiza un widget skeleton para lazy loading
     *
     * @param string $widget_id ID del widget
     * @param string $size Tamano del widget
     * @return string HTML del skeleton
     */
    public function render_skeleton(string $widget_id, string $size = 'medium'): string {
        $size_class = $this->size_classes[$size] ?? $this->size_classes['medium'];

        ob_start();
        ?>
        <div class="fud-widget fud-widget--skeleton <?php echo esc_attr($size_class); ?>"
             data-widget-id="<?php echo esc_attr($widget_id); ?>">
            <div class="fud-widget__header fud-skeleton">
                <div class="fud-skeleton__line fud-skeleton__line--short"></div>
            </div>
            <div class="fud-widget__body fud-skeleton">
                <div class="fud-skeleton__line"></div>
                <div class="fud-skeleton__line fud-skeleton__line--medium"></div>
                <div class="fud-skeleton__line fud-skeleton__line--short"></div>
            </div>
        </div>
        <?php
        return ob_get_clean();
    }

    /**
     * Renderiza un mensaje de estado vacio
     *
     * @param string $message Mensaje a mostrar
     * @param string $icon Icono (clase dashicons)
     * @param array $action Accion opcional (label, url)
     * @return string HTML
     */
    public function render_empty_state(
        string $message,
        string $icon = 'dashicons-info',
        array $action = []
    ): string {
        ob_start();
        ?>
        <div class="fud-empty-state">
            <span class="fud-empty-state__icon dashicons <?php echo esc_attr($icon); ?>"></span>
            <p class="fud-empty-state__message"><?php echo esc_html($message); ?></p>
            <?php if (!empty($action['label']) && !empty($action['url'])): ?>
                <a href="<?php echo esc_url($action['url']); ?>" class="fud-empty-state__action button">
                    <?php echo esc_html($action['label']); ?>
                </a>
            <?php endif; ?>
        </div>
        <?php
        return ob_get_clean();
    }

    /**
     * Renderiza un estado de error
     *
     * @param string $message Mensaje de error
     * @param bool $retry Si mostrar boton de reintentar
     * @return string HTML
     */
    public function render_error_state(string $message, bool $retry = true): string {
        ob_start();
        ?>
        <div class="fud-error-state">
            <span class="fud-error-state__icon dashicons dashicons-warning"></span>
            <p class="fud-error-state__message"><?php echo esc_html($message); ?></p>
            <?php if ($retry): ?>
                <button type="button" class="fud-error-state__retry button">
                    <span class="dashicons dashicons-update"></span>
                    <?php esc_html_e('Reintentar', 'flavor-chat-ia'); ?>
                </button>
            <?php endif; ?>
        </div>
        <?php
        return ob_get_clean();
    }

    /**
     * Renderiza un indicador de carga
     *
     * @param string $message Mensaje de carga
     * @return string HTML
     */
    public function render_loading(string $message = ''): string {
        if (empty($message)) {
            $message = __('Cargando...', 'flavor-chat-ia');
        }

        ob_start();
        ?>
        <div class="fud-loading-state">
            <span class="fud-loading-spinner"></span>
            <span class="fud-loading-text"><?php echo esc_html($message); ?></span>
        </div>
        <?php
        return ob_get_clean();
    }

    /**
     * Renderiza una tarjeta de estadistica
     *
     * @param array $stat Datos de la estadistica
     * @return string HTML
     */
    public function render_stat_card(array $stat): string {
        $icon  = esc_attr($stat['icon'] ?? 'dashicons-chart-bar');
        $valor = esc_html($stat['valor'] ?? '0');
        $label = esc_html($stat['label'] ?? '');
        $color = esc_attr($stat['color'] ?? 'primary');
        $trend = $stat['trend'] ?? null;
        $url   = !empty($stat['url']) ? esc_url($stat['url']) : '';

        ob_start();
        ?>
        <div class="fud-stat-card fud-stat-card--<?php echo $color; ?>">
            <div class="fud-stat-card__icon">
                <span class="dashicons <?php echo $icon; ?>"></span>
            </div>
            <div class="fud-stat-card__content">
                <span class="fud-stat-card__value"><?php echo $valor; ?></span>
                <span class="fud-stat-card__label"><?php echo $label; ?></span>
                <?php if ($trend !== null): ?>
                    <span class="fud-stat-card__trend fud-trend--<?php echo $trend >= 0 ? 'up' : 'down'; ?>">
                        <span class="dashicons <?php echo $trend >= 0 ? 'dashicons-arrow-up-alt' : 'dashicons-arrow-down-alt'; ?>"></span>
                        <?php echo esc_html(abs($trend)) . '%'; ?>
                    </span>
                <?php endif; ?>
            </div>
            <?php if ($url): ?>
                <a href="<?php echo $url; ?>" class="fud-stat-card__link" aria-label="<?php echo $label; ?>">
                    <span class="dashicons dashicons-arrow-right-alt2"></span>
                </a>
            <?php endif; ?>
        </div>
        <?php
        return ob_get_clean();
    }

    /**
     * Renderiza una lista de items
     *
     * @param array $items Items a mostrar
     * @param int $limit Limite de items
     * @return string HTML
     */
    public function render_item_list(array $items, int $limit = 5): string {
        $items = array_slice($items, 0, $limit);

        if (empty($items)) {
            return $this->render_empty_state(__('No hay elementos', 'flavor-chat-ia'));
        }

        ob_start();
        ?>
        <ul class="fud-item-list">
            <?php foreach ($items as $item): ?>
                <?php
                $icon  = esc_attr($item['icon'] ?? 'dashicons-marker');
                $title = esc_html($item['title'] ?? '');
                $meta  = esc_html($item['meta'] ?? '');
                $url   = !empty($item['url']) ? esc_url($item['url']) : '#';
                $badge = $item['badge'] ?? '';
                $badge_color = esc_attr($item['badge_color'] ?? 'default');
                ?>
                <li class="fud-item-list__item">
                    <a href="<?php echo $url; ?>" class="fud-item-list__link">
                        <span class="fud-item-list__icon dashicons <?php echo $icon; ?>"></span>
                        <span class="fud-item-list__content">
                            <span class="fud-item-list__title"><?php echo $title; ?></span>
                            <?php if ($meta): ?>
                                <span class="fud-item-list__meta"><?php echo $meta; ?></span>
                            <?php endif; ?>
                        </span>
                        <?php if ($badge): ?>
                            <span class="fud-item-list__badge fud-badge--<?php echo $badge_color; ?>">
                                <?php echo esc_html($badge); ?>
                            </span>
                        <?php endif; ?>
                    </a>
                </li>
            <?php endforeach; ?>
        </ul>
        <?php
        return ob_get_clean();
    }
}

/**
 * Funcion helper para obtener el renderer
 *
 * @return Flavor_Widget_Renderer
 */
function flavor_widget_renderer(): Flavor_Widget_Renderer {
    return Flavor_Widget_Renderer::get_instance();
}

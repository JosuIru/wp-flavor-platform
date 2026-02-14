<?php
/**
 * Trait para Registrar Widgets de Dashboard
 *
 * Proporciona funcionalidad compartida para que los modulos
 * registren sus widgets en el Dashboard Unificado.
 *
 * @package FlavorChatIA
 * @subpackage Modules
 * @since 4.0.0
 */

// Evitar acceso directo
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Trait Flavor_Dashboard_Widget_Trait
 *
 * Uso en modulos:
 *   class Mi_Modulo extends Flavor_Chat_Module_Base {
 *       use Flavor_Dashboard_Widget_Trait;
 *
 *       public function init() {
 *           parent::init();
 *           $this->register_dashboard_widget();
 *       }
 *   }
 *
 * @since 4.0.0
 */
trait Flavor_Dashboard_Widget_Trait {

    /**
     * Categoria del widget en el dashboard
     *
     * @var string
     */
    protected $dashboard_widget_category = 'gestion';

    /**
     * Tamano del widget
     *
     * @var string
     */
    protected $dashboard_widget_size = 'medium';

    /**
     * Prioridad del widget (menor = aparece primero)
     *
     * @var int
     */
    protected $dashboard_widget_priority = 50;

    /**
     * Si el widget soporta actualizacion AJAX
     *
     * @var bool
     */
    protected $dashboard_widget_refreshable = true;

    /**
     * Tiempo de cache en segundos
     *
     * @var int
     */
    protected $dashboard_widget_cache_time = 300;

    /**
     * Registra el widget del modulo en el Dashboard Unificado
     *
     * Llamar este metodo en el init() del modulo
     *
     * @return void
     */
    public function register_dashboard_widget(): void {
        add_action('flavor_register_dashboard_widgets', [$this, 'do_register_dashboard_widget']);
    }

    /**
     * Ejecuta el registro del widget
     *
     * @param Flavor_Widget_Registry $registry Registro de widgets
     * @return void
     */
    public function do_register_dashboard_widget(Flavor_Widget_Registry $registry): void {
        // Solo registrar si el modulo esta activo
        if (!$this->can_activate()) {
            return;
        }

        $widget = $this->create_dashboard_widget();

        if ($widget instanceof Flavor_Dashboard_Widget_Interface) {
            $registry->register($widget);
        }
    }

    /**
     * Crea el widget del modulo
     *
     * Los modulos pueden sobrescribir este metodo para personalizar completamente
     *
     * @return Flavor_Dashboard_Widget_Interface
     */
    protected function create_dashboard_widget(): Flavor_Dashboard_Widget_Interface {
        return new Flavor_Module_Widget([
            'id'              => $this->get_dashboard_widget_id(),
            'title'           => $this->get_dashboard_widget_title(),
            'icon'            => $this->get_dashboard_widget_icon(),
            'size'            => $this->dashboard_widget_size,
            'category'        => $this->dashboard_widget_category,
            'priority'        => $this->dashboard_widget_priority,
            'refreshable'     => $this->dashboard_widget_refreshable,
            'cache_time'      => $this->dashboard_widget_cache_time,
            'module'          => $this,
            'data_callback'   => [$this, 'get_dashboard_widget_data'],
            'render_callback' => [$this, 'render_dashboard_widget'],
        ]);
    }

    /**
     * Obtiene el ID del widget
     *
     * Por defecto usa el ID del modulo prefijado
     *
     * @return string
     */
    protected function get_dashboard_widget_id(): string {
        return 'module-' . $this->get_id();
    }

    /**
     * Obtiene el titulo del widget
     *
     * Por defecto usa el nombre del modulo
     *
     * @return string
     */
    protected function get_dashboard_widget_title(): string {
        return $this->get_name();
    }

    /**
     * Obtiene el icono del widget
     *
     * Los modulos pueden sobrescribir este metodo
     *
     * @return string Clase dashicons
     */
    protected function get_dashboard_widget_icon(): string {
        // Intentar obtener el icono del modulo si existe el metodo
        if (method_exists($this, 'get_icon')) {
            return $this->get_icon();
        }

        // Icono por defecto segun categoria
        $iconos_categoria = [
            'gestion'      => 'dashicons-clipboard',
            'comunicacion' => 'dashicons-megaphone',
            'economia'     => 'dashicons-chart-line',
            'comunidad'    => 'dashicons-groups',
            'red'          => 'dashicons-networking',
            'sistema'      => 'dashicons-admin-generic',
        ];

        return $iconos_categoria[$this->dashboard_widget_category] ?? 'dashicons-admin-generic';
    }

    /**
     * Obtiene los datos del widget para el dashboard
     *
     * Este metodo debe ser sobrescrito por los modulos
     * o pueden usar get_estadisticas_dashboard() si existe
     *
     * @return array
     */
    public function get_dashboard_widget_data(): array {
        // Si el modulo tiene get_estadisticas_dashboard(), usarlo
        if (method_exists($this, 'get_estadisticas_dashboard')) {
            $estadisticas = $this->get_estadisticas_dashboard();
            return $this->transform_estadisticas_to_widget_data($estadisticas);
        }

        // Datos por defecto vacios
        return [
            'stats'       => [],
            'items'       => [],
            'actions'     => [],
            'empty_state' => sprintf(
                __('No hay datos de %s', 'flavor-chat-ia'),
                $this->get_name()
            ),
            'footer'      => $this->get_dashboard_widget_footer(),
        ];
    }

    /**
     * Transforma las estadisticas del formato antiguo al nuevo
     *
     * Convierte el array de get_estadisticas_dashboard() al formato
     * requerido por el Dashboard Unificado
     *
     * @param array $estadisticas Estadisticas en formato antiguo
     * @return array Datos en formato de widget
     */
    protected function transform_estadisticas_to_widget_data(array $estadisticas): array {
        $stats = [];

        foreach ($estadisticas as $stat) {
            $stats[] = [
                'icon'  => $stat['icon'] ?? 'dashicons-chart-bar',
                'valor' => $stat['valor'] ?? 0,
                'label' => $stat['label'] ?? '',
                'color' => $this->get_color_from_stat($stat),
                'url'   => $stat['url'] ?? '',
            ];
        }

        return [
            'stats'       => $stats,
            'items'       => $this->get_dashboard_widget_items(),
            'actions'     => $this->get_dashboard_widget_actions(),
            'empty_state' => '',
            'footer'      => $this->get_dashboard_widget_footer(),
        ];
    }

    /**
     * Obtiene el color de una estadistica
     *
     * @param array $stat Datos de la estadistica
     * @return string
     */
    protected function get_color_from_stat(array $stat): string {
        // Si ya tiene color, usarlo
        if (!empty($stat['color'])) {
            return $stat['color'];
        }

        // Color basado en tipo
        $tipo = $stat['tipo'] ?? 'default';
        $colores = [
            'success'  => 'success',
            'warning'  => 'warning',
            'danger'   => 'danger',
            'error'    => 'danger',
            'info'     => 'info',
            'primary'  => 'primary',
            'default'  => 'primary',
        ];

        return $colores[$tipo] ?? 'primary';
    }

    /**
     * Obtiene items para mostrar en el widget
     *
     * Los modulos pueden sobrescribir este metodo
     *
     * @return array
     */
    protected function get_dashboard_widget_items(): array {
        return [];
    }

    /**
     * Obtiene acciones rapidas del widget
     *
     * Los modulos pueden sobrescribir este metodo
     *
     * @return array
     */
    protected function get_dashboard_widget_actions(): array {
        // Si el modulo tiene get_quick_actions(), usarlo
        if (method_exists($this, 'get_quick_actions')) {
            return $this->get_quick_actions();
        }

        return [];
    }

    /**
     * Obtiene enlaces del footer del widget
     *
     * @return array
     */
    protected function get_dashboard_widget_footer(): array {
        $module_id = $this->get_id();

        return [
            [
                'label' => __('Ver mas', 'flavor-chat-ia'),
                'url'   => admin_url('admin.php?page=flavor-' . str_replace('_', '-', $module_id)),
                'icon'  => 'dashicons-arrow-right-alt2',
            ],
        ];
    }

    /**
     * Renderiza el contenido del widget
     *
     * Los modulos pueden sobrescribir este metodo para personalizar
     *
     * @param array $data Datos del widget
     * @param Flavor_Module_Widget $widget Instancia del widget
     * @return void
     */
    public function render_dashboard_widget(array $data, $widget = null): void {
        // Usar renderizado estandar del widget base
        if ($widget instanceof Flavor_Dashboard_Widget_Base) {
            // El widget base ya sabe renderizarse
            return;
        }

        // Renderizado manual si no hay widget
        $this->render_dashboard_widget_stats($data['stats'] ?? []);
        $this->render_dashboard_widget_items($data['items'] ?? []);
    }

    /**
     * Renderiza las estadisticas del widget
     *
     * @param array $stats Estadisticas
     * @return void
     */
    protected function render_dashboard_widget_stats(array $stats): void {
        if (empty($stats)) {
            return;
        }
        ?>
        <div class="fud-widget-stats">
            <?php foreach ($stats as $stat): ?>
                <div class="fud-stat-item fud-stat--<?php echo esc_attr($stat['color'] ?? 'primary'); ?>">
                    <span class="fud-stat-icon dashicons <?php echo esc_attr($stat['icon'] ?? 'dashicons-chart-bar'); ?>"></span>
                    <span class="fud-stat-value"><?php echo esc_html($stat['valor'] ?? '0'); ?></span>
                    <span class="fud-stat-label"><?php echo esc_html($stat['label'] ?? ''); ?></span>
                </div>
            <?php endforeach; ?>
        </div>
        <?php
    }

    /**
     * Renderiza los items del widget
     *
     * @param array $items Items
     * @return void
     */
    protected function render_dashboard_widget_items(array $items): void {
        if (empty($items)) {
            return;
        }
        ?>
        <ul class="fud-widget-items">
            <?php foreach (array_slice($items, 0, 5) as $item): ?>
                <li class="fud-widget-item">
                    <a href="<?php echo esc_url($item['url'] ?? '#'); ?>">
                        <span class="fud-item-icon dashicons <?php echo esc_attr($item['icon'] ?? 'dashicons-marker'); ?>"></span>
                        <span class="fud-item-content">
                            <span class="fud-item-title"><?php echo esc_html($item['title'] ?? ''); ?></span>
                            <?php if (!empty($item['meta'])): ?>
                                <span class="fud-item-meta"><?php echo esc_html($item['meta']); ?></span>
                            <?php endif; ?>
                        </span>
                    </a>
                </li>
            <?php endforeach; ?>
        </ul>
        <?php
    }

    /**
     * Configura las propiedades del widget
     *
     * Metodo de conveniencia para configurar multiples propiedades
     *
     * @param array $config Configuracion
     * @return void
     */
    protected function configure_dashboard_widget(array $config): void {
        if (isset($config['category'])) {
            $this->dashboard_widget_category = sanitize_key($config['category']);
        }

        if (isset($config['size']) && in_array($config['size'], ['small', 'medium', 'large'], true)) {
            $this->dashboard_widget_size = $config['size'];
        }

        if (isset($config['priority'])) {
            $this->dashboard_widget_priority = absint($config['priority']);
        }

        if (isset($config['refreshable'])) {
            $this->dashboard_widget_refreshable = (bool) $config['refreshable'];
        }

        if (isset($config['cache_time'])) {
            $this->dashboard_widget_cache_time = absint($config['cache_time']);
        }
    }
}

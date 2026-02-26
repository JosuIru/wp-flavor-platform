<?php
/**
 * Clase Archive Renderer
 *
 * Renderiza páginas de archivo de módulos usando los componentes shared,
 * eliminando la duplicación de código entre módulos.
 *
 * @package FlavorChatIA
 * @since 5.0.0
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Class Flavor_Archive_Renderer
 *
 * Proporciona una forma unificada de renderizar páginas de archivo
 * para cualquier módulo del sistema.
 *
 * Uso básico:
 *   $renderer = new Flavor_Archive_Renderer();
 *   echo $renderer->render([
 *       'module'  => 'incidencias',
 *       'title'   => 'Incidencias del Barrio',
 *       'color'   => 'red',
 *       'items'   => $incidencias,
 *       'stats'   => [...],
 *   ]);
 *
 * @since 5.0.0
 */
class Flavor_Archive_Renderer {

    /**
     * Ruta base de componentes
     *
     * @var string
     */
    private $components_path;

    /**
     * Configuración por defecto
     *
     * @var array
     */
    private $defaults = [
        'module'           => '',
        'title'            => '',
        'subtitle'         => '',
        'icon'             => '',
        'color'            => 'blue',
        'items'            => [],
        'total'            => 0,
        'per_page'         => 12,
        'current_page'     => 1,
        'stats'            => [],
        'filters'          => [],
        'columns'          => 3,
        'layout'           => 'grid',
        'show_header'      => true,
        'show_stats'       => true,
        'show_filters'     => true,
        'show_pagination'  => true,
        'stats_layout'     => 'horizontal',
        'card_template'    => '',
        'card_callback'    => null,
        'cta_text'         => '',
        'cta_action'       => '',
        'cta_url'          => '',
        'cta_icon'         => '',
        'badge'            => '',
        'filter_data_attr' => 'filter',
        'empty_state'      => [],
        'extra_content'    => '',
        'wrapper_class'    => '',
        'base_url'         => '',
    ];

    /**
     * Constructor
     */
    public function __construct() {
        $this->components_path = FLAVOR_PLUGIN_PATH . 'templates/components/shared/';

        // Cargar funciones helper
        if (!function_exists('flavor_render_component')) {
            require_once $this->components_path . '_functions.php';
        }
    }

    /**
     * Renderiza una página de archivo completa
     *
     * @param array $config Configuración del archive
     * @return string HTML del archive
     */
    public function render(array $config): string {
        $config = wp_parse_args($config, $this->defaults);

        // Si no se especifica total, usar count de items
        if (empty($config['total']) && !empty($config['items'])) {
            $config['total'] = count($config['items']);
        }

        // Si no se especifica badge, generarlo automáticamente
        if (empty($config['badge']) && $config['total'] > 0) {
            $config['badge'] = sprintf(
                _n('%d registrado', '%d registrados', $config['total'], 'flavor-chat-ia'),
                $config['total']
            );
        }

        // Template de card por defecto según módulo
        if (empty($config['card_template']) && !empty($config['module'])) {
            $config['card_template'] = $config['module'] . '/card';
        }

        ob_start();

        // Wrapper principal
        $wrapper_classes = 'flavor-frontend flavor-' . sanitize_html_class($config['module']) . '-archive';
        if ($config['wrapper_class']) {
            $wrapper_classes .= ' ' . esc_attr($config['wrapper_class']);
        }
        ?>
        <div class="<?php echo esc_attr($wrapper_classes); ?>">

            <?php
            // Header
            if ($config['show_header']) {
                $this->render_component('archive-header', [
                    'title'      => $config['title'],
                    'subtitle'   => $config['subtitle'],
                    'icon'       => $config['icon'],
                    'color'      => $config['color'],
                    'badge'      => $config['badge'],
                    'cta_text'   => $config['cta_text'],
                    'cta_action' => $config['cta_action'],
                    'cta_url'    => $config['cta_url'],
                    'cta_icon'   => $config['cta_icon'],
                ]);
            }

            // Stats
            if ($config['show_stats'] && !empty($config['stats'])) {
                $this->render_component('stats-grid', [
                    'stats'   => $config['stats'],
                    'columns' => count($config['stats']) <= 2 ? 2 : 4,
                    'layout'  => $config['stats_layout'],
                ]);
            }

            // Contenido extra (ej: "Cómo funciona" en marketplace)
            if (!empty($config['extra_content'])) {
                if (is_callable($config['extra_content'])) {
                    call_user_func($config['extra_content']);
                } else {
                    echo wp_kses_post($config['extra_content']);
                }
            }

            // Filtros
            if ($config['show_filters'] && !empty($config['filters'])) {
                $this->render_component('filter-pills', [
                    'filters'   => $config['filters'],
                    'color'     => $config['color'],
                    'data_attr' => $config['filter_data_attr'],
                    'target'    => '.flavor-items-grid',
                ]);
            }

            // Grid de items
            $this->render_component('items-grid', [
                'items'         => $config['items'],
                'columns'       => $config['columns'],
                'layout'        => $config['layout'],
                'card_template' => $config['card_template'],
                'card_callback' => $config['card_callback'],
                'data_attr'     => $config['filter_data_attr'],
                'empty_state'   => wp_parse_args($config['empty_state'], [
                    'icon'       => $config['icon'] ?: '📭',
                    'title'      => sprintf(__('No hay %s', 'flavor-chat-ia'), strtolower($config['title'] ?: 'elementos')),
                    'cta_text'   => $config['cta_text'],
                    'cta_action' => $config['cta_action'],
                    'cta_url'    => $config['cta_url'],
                    'color'      => $config['color'],
                ]),
            ]);

            // Paginación
            if ($config['show_pagination'] && $config['total'] > $config['per_page']) {
                $this->render_component('pagination', [
                    'total'      => $config['total'],
                    'per_page'   => $config['per_page'],
                    'current'    => $config['current_page'],
                    'color'      => $config['color'],
                    'base_url'   => $config['base_url'],
                ]);
            }
            ?>

        </div>
        <?php

        return ob_get_clean();
    }

    /**
     * Renderiza un componente shared
     *
     * @param string $component Nombre del componente
     * @param array  $args      Argumentos del componente
     * @return void
     */
    protected function render_component(string $component, array $args = []): void {
        $file = $this->components_path . $component . '.php';

        if (!file_exists($file)) {
            if (defined('WP_DEBUG') && WP_DEBUG) {
                echo "<!-- Componente no encontrado: {$component} -->";
            }
            return;
        }

        extract($args, EXTR_SKIP);
        include $file;
    }

    /**
     * Obtiene la configuración para un módulo específico
     *
     * Proporciona valores predefinidos para módulos conocidos.
     *
     * @param string $module ID del módulo
     * @return array Configuración base del módulo
     */
    public static function get_module_config(string $module): array {
        $configs = [
            'incidencias' => [
                'title'     => __('Incidencias del Barrio', 'flavor-chat-ia'),
                'subtitle'  => __('Reporta y consulta problemas en espacios públicos', 'flavor-chat-ia'),
                'icon'      => '⚠️',
                'color'     => 'red',
                'cta_text'  => __('Reportar incidencia', 'flavor-chat-ia'),
                'cta_icon'  => '📝',
                'filters'   => [
                    ['id' => 'todos', 'label' => __('Todas', 'flavor-chat-ia'), 'active' => true],
                    ['id' => 'pendiente', 'label' => __('Pendientes', 'flavor-chat-ia'), 'icon' => '🔴'],
                    ['id' => 'en_proceso', 'label' => __('En proceso', 'flavor-chat-ia'), 'icon' => '🟡'],
                    ['id' => 'resuelto', 'label' => __('Resueltas', 'flavor-chat-ia'), 'icon' => '🟢'],
                ],
                'filter_data_attr' => 'estado',
            ],

            'marketplace' => [
                'title'     => __('Marketplace Local', 'flavor-chat-ia'),
                'subtitle'  => __('Compra, vende e intercambia productos en tu comunidad', 'flavor-chat-ia'),
                'icon'      => '🛒',
                'color'     => 'green',
                'cta_text'  => __('Publicar Anuncio', 'flavor-chat-ia'),
                'cta_icon'  => '📢',
                'stats_layout' => 'vertical',
            ],

            'eventos' => [
                'title'     => __('Eventos del Barrio', 'flavor-chat-ia'),
                'subtitle'  => __('Descubre y participa en actividades locales', 'flavor-chat-ia'),
                'icon'      => '📅',
                'color'     => 'purple',
                'cta_text'  => __('Crear evento', 'flavor-chat-ia'),
                'cta_icon'  => '➕',
            ],

            'comunidades' => [
                'title'     => __('Comunidades', 'flavor-chat-ia'),
                'subtitle'  => __('Conecta con grupos de tu zona', 'flavor-chat-ia'),
                'icon'      => '👥',
                'color'     => 'blue',
                'cta_text'  => __('Crear comunidad', 'flavor-chat-ia'),
                'cta_icon'  => '➕',
            ],

            'colectivos' => [
                'title'     => __('Colectivos', 'flavor-chat-ia'),
                'subtitle'  => __('Organizaciones y grupos del territorio', 'flavor-chat-ia'),
                'icon'      => '🏛️',
                'color'     => 'indigo',
            ],

            'banco_tiempo' => [
                'title'     => __('Banco del Tiempo', 'flavor-chat-ia'),
                'subtitle'  => __('Intercambia servicios con tus vecinos', 'flavor-chat-ia'),
                'icon'      => '⏰',
                'color'     => 'teal',
                'cta_text'  => __('Ofrecer servicio', 'flavor-chat-ia'),
                'cta_icon'  => '🤝',
            ],

            'cursos' => [
                'title'     => __('Cursos y Talleres', 'flavor-chat-ia'),
                'subtitle'  => __('Aprende nuevas habilidades', 'flavor-chat-ia'),
                'icon'      => '📚',
                'color'     => 'orange',
            ],

            'reciclaje' => [
                'title'     => __('Puntos de Reciclaje', 'flavor-chat-ia'),
                'subtitle'  => __('Encuentra dónde reciclar cerca de ti', 'flavor-chat-ia'),
                'icon'      => '♻️',
                'color'     => 'green',
            ],

            'biodiversidad' => [
                'title'     => __('Biodiversidad Local', 'flavor-chat-ia'),
                'subtitle'  => __('Flora y fauna de nuestro entorno', 'flavor-chat-ia'),
                'icon'      => '🌿',
                'color'     => 'green',
            ],
        ];

        return $configs[$module] ?? [];
    }

    /**
     * Renderiza un archive de módulo con configuración automática
     *
     * @param string $module ID del módulo
     * @param array  $data   Datos del módulo (items, stats, etc.)
     * @param array  $config Configuración adicional (sobrescribe defaults)
     * @return string HTML del archive
     */
    public function render_module(string $module, array $data = [], array $config = []): string {
        // Obtener config base del módulo
        $module_config = self::get_module_config($module);

        // Merge: defaults < module_config < data < config
        $final_config = wp_parse_args($config, $data);
        $final_config = wp_parse_args($final_config, $module_config);
        $final_config['module'] = $module;

        return $this->render($final_config);
    }
}

/**
 * Función helper para acceso rápido al renderer
 *
 * @param array $config Configuración del archive
 * @return string HTML del archive
 */
function flavor_render_archive(array $config): string {
    static $renderer = null;

    if ($renderer === null) {
        $renderer = new Flavor_Archive_Renderer();
    }

    return $renderer->render($config);
}

/**
 * Función helper para renderizar archive de un módulo
 *
 * @param string $module ID del módulo
 * @param array  $data   Datos del módulo
 * @param array  $config Configuración adicional
 * @return string HTML del archive
 */
function flavor_render_module_archive(string $module, array $data = [], array $config = []): string {
    static $renderer = null;

    if ($renderer === null) {
        $renderer = new Flavor_Archive_Renderer();
    }

    return $renderer->render_module($module, $data, $config);
}

<?php
/**
 * Funciones Helper para Componentes Shared
 *
 * @package FlavorPlatform
 * @since 5.0.0
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Obtiene el color hex del tema para un color semántico
 *
 * @param string $color Color semántico (primary, secondary, success, warning, error, info)
 * @return string|null Color hex o null si no existe
 */
function flavor_get_theme_color_hex(string $color): ?string {
    // Mapeo de nombres semánticos a variables CSS del tema
    $css_var_map = [
        'primary'   => '--flavor-primary',
        'secondary' => '--flavor-secondary',
        'success'   => '--flavor-success',
        'warning'   => '--flavor-warning',
        'error'     => '--flavor-error',
        'info'      => '--flavor-info',
        'accent'    => '--flavor-primary', // Accent usa primary como fallback
    ];

    // Intentar obtener del Theme Manager directamente
    if (class_exists('Flavor_Theme_Manager')) {
        $theme_manager = Flavor_Theme_Manager::get_instance();
        $active_theme_id = get_option('flavor_active_theme', 'default');
        $theme = $theme_manager->get_theme($active_theme_id);

        if ($theme && isset($theme['variables'])) {
            $var_name = $css_var_map[$color] ?? null;
            if ($var_name && isset($theme['variables'][$var_name])) {
                return $theme['variables'][$var_name];
            }
        }
    }

    // Fallback: obtener de design settings
    if (function_exists('flavor_design_get')) {
        $setting_key = "{$color}_color";
        $hex = flavor_design_get($setting_key, null);
        if ($hex) {
            return $hex;
        }
    }

    return null;
}

/**
 * Resuelve un color semántico (primary, secondary, etc.) al color real del tema
 *
 * @param string $color Color semántico o color Tailwind directo
 * @return string Color Tailwind resuelto
 */
function flavor_resolve_theme_color(string $color): string {
    // Si es un color semántico, obtener el color del tema
    $semantic_colors = ['primary', 'secondary', 'accent', 'success', 'warning', 'error', 'info'];

    if (in_array($color, $semantic_colors, true)) {
        $hex_color = flavor_get_theme_color_hex($color);
        if ($hex_color) {
            return flavor_hex_to_tailwind_color($hex_color);
        }

        // Fallback: mapeo de colores semánticos a colores Tailwind
        $semantic_map = [
            'primary'   => 'blue',
            'secondary' => 'gray',
            'accent'    => 'amber',
            'success'   => 'green',
            'warning'   => 'amber',
            'error'     => 'red',
            'info'      => 'blue',
        ];

        return $semantic_map[$color] ?? 'blue';
    }

    // Si no es semántico, devolver el color tal cual
    return $color;
}

/**
 * Genera estilos inline usando variables CSS del tema
 *
 * @param string $color Color semántico (primary, secondary, etc.)
 * @param string $property Propiedad CSS (background, color, border-color)
 * @return string Estilo inline
 */
function flavor_get_theme_style(string $color, string $property = 'background'): string {
    $css_var_map = [
        'primary'   => '--flavor-primary',
        'secondary' => '--flavor-secondary',
        'success'   => '--flavor-success',
        'warning'   => '--flavor-warning',
        'error'     => '--flavor-error',
        'info'      => '--flavor-info',
    ];

    $var = $css_var_map[$color] ?? '--flavor-primary';
    return "{$property}: var({$var});";
}

/**
 * Convierte un color hexadecimal al color Tailwind más cercano
 *
 * @param string $hex Color hexadecimal (#3b82f6, #ef4444, etc.)
 * @return string Nombre de color Tailwind (blue, red, green, etc.)
 */
function flavor_hex_to_tailwind_color(string $hex): string {
    // Normalizar hex
    $hex = ltrim($hex, '#');
    if (strlen($hex) === 3) {
        $hex = $hex[0].$hex[0].$hex[1].$hex[1].$hex[2].$hex[2];
    }

    // Colores Tailwind principales (500)
    $tailwind_colors = [
        'red'     => 'ef4444',
        'orange'  => 'f97316',
        'amber'   => 'f59e0b',
        'yellow'  => 'eab308',
        'lime'    => '84cc16',
        'green'   => '22c55e',
        'emerald' => '10b981',
        'teal'    => '14b8a6',
        'cyan'    => '06b6d4',
        'sky'     => '0ea5e9',
        'blue'    => '3b82f6',
        'indigo'  => '6366f1',
        'violet'  => '8b5cf6',
        'purple'  => 'a855f7',
        'fuchsia' => 'd946ef',
        'pink'    => 'ec4899',
        'rose'    => 'f43f5e',
        'slate'   => '64748b',
        'gray'    => '6b7280',
    ];

    // Encontrar el color más cercano
    $hex_rgb = [
        hexdec(substr($hex, 0, 2)),
        hexdec(substr($hex, 2, 2)),
        hexdec(substr($hex, 4, 2)),
    ];

    $closest = 'blue';
    $min_distance = PHP_INT_MAX;

    foreach ($tailwind_colors as $name => $tw_hex) {
        $tw_rgb = [
            hexdec(substr($tw_hex, 0, 2)),
            hexdec(substr($tw_hex, 2, 2)),
            hexdec(substr($tw_hex, 4, 2)),
        ];

        // Distancia euclidiana en el espacio RGB
        $distance = sqrt(
            pow($hex_rgb[0] - $tw_rgb[0], 2) +
            pow($hex_rgb[1] - $tw_rgb[1], 2) +
            pow($hex_rgb[2] - $tw_rgb[2], 2)
        );

        if ($distance < $min_distance) {
            $min_distance = $distance;
            $closest = $name;
        }
    }

    return $closest;
}

/**
 * Obtiene la clase de color para un gradiente Tailwind
 *
 * @param string $color Color base (red, green, blue, yellow, etc.) o semántico (primary, secondary)
 * @return array Classes para from y to del gradiente
 */
function flavor_get_gradient_classes(string $color): array {
    // Resolver colores semánticos primero
    $resolved_color = flavor_resolve_theme_color($color);

    $gradientes = [
        'red'     => ['from' => 'from-red-500', 'to' => 'to-rose-500'],
        'green'   => ['from' => 'from-lime-500', 'to' => 'to-green-600'],
        'blue'    => ['from' => 'from-blue-500', 'to' => 'to-indigo-600'],
        'yellow'  => ['from' => 'from-amber-400', 'to' => 'to-orange-500'],
        'purple'  => ['from' => 'from-purple-500', 'to' => 'to-pink-500'],
        'cyan'    => ['from' => 'from-cyan-400', 'to' => 'to-teal-500'],
        'orange'  => ['from' => 'from-orange-400', 'to' => 'to-red-500'],
        'teal'    => ['from' => 'from-teal-400', 'to' => 'to-emerald-600'],
        'pink'    => ['from' => 'from-pink-400', 'to' => 'to-rose-500'],
        'indigo'  => ['from' => 'from-indigo-500', 'to' => 'to-purple-600'],
        'gray'    => ['from' => 'from-gray-500', 'to' => 'to-slate-600'],
        'slate'   => ['from' => 'from-slate-500', 'to' => 'to-gray-600'],
        'emerald' => ['from' => 'from-emerald-500', 'to' => 'to-teal-600'],
        'lime'    => ['from' => 'from-lime-400', 'to' => 'to-green-500'],
        'amber'   => ['from' => 'from-amber-400', 'to' => 'to-orange-500'],
        'rose'    => ['from' => 'from-rose-500', 'to' => 'to-pink-500'],
        'sky'     => ['from' => 'from-sky-400', 'to' => 'to-blue-500'],
        'violet'  => ['from' => 'from-violet-500', 'to' => 'to-purple-600'],
        'fuchsia' => ['from' => 'from-fuchsia-500', 'to' => 'to-pink-600'],
    ];

    return $gradientes[$resolved_color] ?? $gradientes['blue'];
}

/**
 * Obtiene classes de color para badges/elementos según el color
 *
 * @param string $color Color (red, green, blue, etc.) o semántico (primary, secondary)
 * @return array Classes bg, text, hover
 */
function flavor_get_color_classes(string $color): array {
    // Resolver colores semánticos primero
    $resolved_color = flavor_resolve_theme_color($color);

    $colores = [
        'red'     => ['bg' => 'bg-red-100', 'text' => 'text-red-700', 'hover' => 'hover:bg-red-200', 'bg_solid' => 'bg-red-500', 'border' => 'border-red-300'],
        'green'   => ['bg' => 'bg-green-100', 'text' => 'text-green-700', 'hover' => 'hover:bg-green-200', 'bg_solid' => 'bg-green-500', 'border' => 'border-green-300'],
        'lime'    => ['bg' => 'bg-lime-100', 'text' => 'text-lime-700', 'hover' => 'hover:bg-lime-200', 'bg_solid' => 'bg-lime-500', 'border' => 'border-lime-300'],
        'blue'    => ['bg' => 'bg-blue-100', 'text' => 'text-blue-700', 'hover' => 'hover:bg-blue-200', 'bg_solid' => 'bg-blue-500', 'border' => 'border-blue-300'],
        'yellow'  => ['bg' => 'bg-yellow-100', 'text' => 'text-yellow-700', 'hover' => 'hover:bg-yellow-200', 'bg_solid' => 'bg-yellow-500', 'border' => 'border-yellow-300'],
        'amber'   => ['bg' => 'bg-amber-100', 'text' => 'text-amber-700', 'hover' => 'hover:bg-amber-200', 'bg_solid' => 'bg-amber-500', 'border' => 'border-amber-300'],
        'purple'  => ['bg' => 'bg-purple-100', 'text' => 'text-purple-700', 'hover' => 'hover:bg-purple-200', 'bg_solid' => 'bg-purple-500', 'border' => 'border-purple-300'],
        'violet'  => ['bg' => 'bg-violet-100', 'text' => 'text-violet-700', 'hover' => 'hover:bg-violet-200', 'bg_solid' => 'bg-violet-500', 'border' => 'border-violet-300'],
        'cyan'    => ['bg' => 'bg-cyan-100', 'text' => 'text-cyan-700', 'hover' => 'hover:bg-cyan-200', 'bg_solid' => 'bg-cyan-500', 'border' => 'border-cyan-300'],
        'orange'  => ['bg' => 'bg-orange-100', 'text' => 'text-orange-700', 'hover' => 'hover:bg-orange-200', 'bg_solid' => 'bg-orange-500', 'border' => 'border-orange-300'],
        'gray'    => ['bg' => 'bg-gray-100', 'text' => 'text-gray-700', 'hover' => 'hover:bg-gray-200', 'bg_solid' => 'bg-gray-500', 'border' => 'border-gray-300'],
        'slate'   => ['bg' => 'bg-slate-100', 'text' => 'text-slate-700', 'hover' => 'hover:bg-slate-200', 'bg_solid' => 'bg-slate-500', 'border' => 'border-slate-300'],
        'teal'    => ['bg' => 'bg-teal-100', 'text' => 'text-teal-700', 'hover' => 'hover:bg-teal-200', 'bg_solid' => 'bg-teal-500', 'border' => 'border-teal-300'],
        'emerald' => ['bg' => 'bg-emerald-100', 'text' => 'text-emerald-700', 'hover' => 'hover:bg-emerald-200', 'bg_solid' => 'bg-emerald-500', 'border' => 'border-emerald-300'],
        'indigo'  => ['bg' => 'bg-indigo-100', 'text' => 'text-indigo-700', 'hover' => 'hover:bg-indigo-200', 'bg_solid' => 'bg-indigo-500', 'border' => 'border-indigo-300'],
        'pink'    => ['bg' => 'bg-pink-100', 'text' => 'text-pink-700', 'hover' => 'hover:bg-pink-200', 'bg_solid' => 'bg-pink-500', 'border' => 'border-pink-300'],
        'rose'    => ['bg' => 'bg-rose-100', 'text' => 'text-rose-700', 'hover' => 'hover:bg-rose-200', 'bg_solid' => 'bg-rose-500', 'border' => 'border-rose-300'],
        'fuchsia' => ['bg' => 'bg-fuchsia-100', 'text' => 'text-fuchsia-700', 'hover' => 'hover:bg-fuchsia-200', 'bg_solid' => 'bg-fuchsia-500', 'border' => 'border-fuchsia-300'],
        'sky'     => ['bg' => 'bg-sky-100', 'text' => 'text-sky-700', 'hover' => 'hover:bg-sky-200', 'bg_solid' => 'bg-sky-500', 'border' => 'border-sky-300'],
    ];

    return $colores[$resolved_color] ?? $colores['blue'];
}

/**
 * Renderiza un componente shared
 *
 * @param string $component Nombre del componente (sin .php)
 * @param array  $args      Argumentos para el componente
 * @return void
 */
function flavor_render_component(string $component, array $args = []): void {
    $file = FLAVOR_PLATFORM_PATH . "templates/components/shared/{$component}.php";

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
 * Obtiene el contenido de un componente shared como string
 *
 * @param string $component Nombre del componente (sin .php)
 * @param array  $args      Argumentos para el componente
 * @return string HTML del componente
 */
function flavor_get_component(string $component, array $args = []): string {
    ob_start();
    flavor_render_component($component, $args);
    return ob_get_clean();
}

/**
 * Genera un ID único para elementos interactivos
 *
 * @param string $prefix Prefijo opcional
 * @return string ID único
 */
function flavor_unique_id(string $prefix = 'fl'): string {
    static $counter = 0;
    $counter++;
    return $prefix . '_' . $counter . '_' . wp_rand(100, 999);
}

/**
 * Sanitiza y valida la configuración de un componente
 *
 * @param array $args     Argumentos recibidos
 * @param array $defaults Valores por defecto
 * @return array Argumentos sanitizados
 */
function flavor_parse_component_args(array $args, array $defaults): array {
    $parsed = wp_parse_args($args, $defaults);

    // Sanitizar strings comunes
    if (isset($parsed['title'])) {
        $parsed['title'] = sanitize_text_field($parsed['title']);
    }
    if (isset($parsed['subtitle'])) {
        $parsed['subtitle'] = sanitize_text_field($parsed['subtitle']);
    }
    if (isset($parsed['color'])) {
        $parsed['color'] = sanitize_key($parsed['color']);
    }

    return $parsed;
}

/**
 * Renderiza un grid de items con el componente especificado.
 *
 * @param array  $items     Array de items
 * @param string $component Nombre del componente para cada item
 * @param array  $config    Configuración adicional (columns, gap, etc.)
 * @return void
 */
function flavor_render_grid(array $items, string $component = 'generic-card', array $config = []): void {
    $defaults = [
        'columns'     => 3,
        'gap'         => 4,
        'item_config' => [],
    ];
    $config = wp_parse_args($config, $defaults);

    $grid_classes = "grid grid-cols-1 md:grid-cols-2 lg:grid-cols-{$config['columns']} gap-{$config['gap']}";
    ?>
    <div class="<?php echo esc_attr($grid_classes); ?>">
        <?php foreach ($items as $item):
            flavor_render_component($component, array_merge(
                ['item' => $item],
                $config['item_config']
            ));
        endforeach; ?>
    </div>
    <?php
}

/**
 * Renderiza un formulario basado en configuración.
 *
 * @param array $config Configuración del formulario
 * @return void
 */
function flavor_render_form(array $config): void {
    $defaults = [
        'fields'      => [],
        'action'      => '',
        'method'      => 'POST',
        'ajax'        => false,
        'ajax_action' => '',
        'submit_text' => __('Guardar', FLAVOR_PLATFORM_TEXT_DOMAIN),
        'color'       => 'blue',
        'layout'      => 'vertical',
    ];
    $config = wp_parse_args($config, $defaults);

    flavor_render_component('form-builder', $config);
}

/**
 * Renderiza una tabla de datos.
 *
 * @param array $columns Definición de columnas
 * @param array $rows    Datos de las filas
 * @param array $config  Configuración adicional
 * @return void
 */
function flavor_render_table(array $columns, array $rows, array $config = []): void {
    $defaults = [
        'color'       => 'blue',
        'striped'     => true,
        'hoverable'   => true,
        'selectable'  => false,
        'row_actions' => [],
        'empty_text'  => __('No hay datos disponibles', FLAVOR_PLATFORM_TEXT_DOMAIN),
    ];
    $config = wp_parse_args($config, $defaults);

    flavor_render_component('data-table', array_merge(
        ['columns' => $columns, 'rows' => $rows],
        $config
    ));
}

/**
 * Renderiza una notificación/alerta.
 *
 * @param string $message Mensaje
 * @param string $type    Tipo: info, success, warning, error
 * @param array  $config  Configuración adicional
 * @return void
 */
function flavor_render_notification(string $message, string $type = 'info', array $config = []): void {
    $defaults = [
        'title'       => '',
        'dismissible' => true,
        'auto_close'  => 0,
        'position'    => 'inline',
    ];
    $config = wp_parse_args($config, $defaults);

    flavor_render_component('notification', array_merge(
        ['message' => $message, 'type' => $type],
        $config
    ));
}

/**
 * Renderiza un modal.
 *
 * @param string $id      ID único del modal
 * @param string $title   Título
 * @param string $content Contenido HTML
 * @param array  $config  Configuración adicional
 * @return void
 */
function flavor_render_modal(string $id, string $title, string $content = '', array $config = []): void {
    $defaults = [
        'size'     => 'md',
        'color'    => 'blue',
        'closable' => true,
        'footer'   => [],
    ];
    $config = wp_parse_args($config, $defaults);

    flavor_render_component('modal', array_merge(
        ['id' => $id, 'title' => $title, 'content' => $content],
        $config
    ));
}

/**
 * Renderiza tabs.
 *
 * @param array  $tabs   Array de tabs
 * @param string $active Tab activa
 * @param array  $config Configuración adicional
 * @return void
 */
function flavor_render_tabs(array $tabs, string $active = '', array $config = []): void {
    $defaults = [
        'color'    => 'blue',
        'style'    => 'underline',
        'vertical' => false,
    ];
    $config = wp_parse_args($config, $defaults);

    if (!$active && !empty($tabs)) {
        $active = $tabs[0]['id'] ?? '';
    }

    flavor_render_component('tabs', array_merge(
        ['tabs' => $tabs, 'active' => $active],
        $config
    ));
}

/**
 * Renderiza un acordeón.
 *
 * @param array $items  Items del acordeón
 * @param array $config Configuración adicional
 * @return void
 */
function flavor_render_accordion(array $items, array $config = []): void {
    $defaults = [
        'color'          => 'blue',
        'allow_multiple' => false,
        'bordered'       => true,
        'separated'      => false,
    ];
    $config = wp_parse_args($config, $defaults);

    flavor_render_component('accordion', array_merge(
        ['items' => $items],
        $config
    ));
}

/**
 * Renderiza una barra de búsqueda.
 *
 * @param array $config Configuración
 * @return void
 */
function flavor_render_search(array $config = []): void {
    $defaults = [
        'placeholder'  => __('Buscar...', FLAVOR_PLATFORM_TEXT_DOMAIN),
        'color'        => 'blue',
        'filters'      => [],
        'live_search'  => false,
        'size'         => 'md',
    ];
    $config = wp_parse_args($config, $defaults);

    flavor_render_component('search-bar', $config);
}

/**
 * Renderiza una timeline.
 *
 * @param array $items  Items de la timeline
 * @param array $config Configuración adicional
 * @return void
 */
function flavor_render_timeline(array $items, array $config = []): void {
    $defaults = [
        'color'     => 'blue',
        'layout'    => 'left',
        'show_line' => true,
        'compact'   => false,
    ];
    $config = wp_parse_args($config, $defaults);

    flavor_render_component('timeline', array_merge(
        ['items' => $items],
        $config
    ));
}

/**
 * Renderiza un breadcrumb.
 *
 * @param array $items  Items del breadcrumb
 * @param array $config Configuración adicional
 * @return void
 */
function flavor_render_breadcrumb(array $items, array $config = []): void {
    $defaults = [
        'color'     => 'blue',
        'separator' => '/',
    ];
    $config = wp_parse_args($config, $defaults);

    flavor_render_component('breadcrumb', array_merge(
        ['items' => $items],
        $config
    ));
}

/**
 * Renderiza un perfil de usuario.
 *
 * @param array $user   Datos del usuario
 * @param array $config Configuración adicional
 * @return void
 */
function flavor_render_user_profile(array $user, array $config = []): void {
    $defaults = [
        'color'    => 'blue',
        'layout'   => 'full',
        'editable' => false,
        'stats'    => [],
        'badges'   => [],
        'actions'  => [],
    ];
    $config = wp_parse_args($config, $defaults);

    flavor_render_component('user-profile', array_merge(
        ['user' => $user],
        $config
    ));
}

/**
 * Renderiza un widget de sidebar.
 *
 * @param string $title  Título del widget
 * @param array  $config Configuración adicional
 * @return void
 */
function flavor_render_sidebar_widget(string $title, array $config = []): void {
    $defaults = [
        'icon'    => '',
        'color'   => 'blue',
        'type'    => 'default',
        'items'   => [],
        'actions' => [],
        'cta'     => [],
    ];
    $config = wp_parse_args($config, $defaults);

    flavor_render_component('sidebar-widget', array_merge(
        ['title' => $title],
        $config
    ));
}

/**
 * Renderiza una sección de dashboard.
 *
 * @param string $title  Título de la sección
 * @param array  $config Configuración adicional
 * @return void
 */
function flavor_render_dashboard_section(string $title, array $config = []): void {
    $defaults = [
        'subtitle'    => '',
        'icon'        => '',
        'color'       => 'blue',
        'actions'     => [],
        'collapsible' => false,
        'collapsed'   => false,
        'content'     => '',
    ];
    $config = wp_parse_args($config, $defaults);

    flavor_render_component('dashboard-section', array_merge(
        ['title' => $title],
        $config
    ));
}

/**
 * Renderiza tarjetas de acción rápida en un grid.
 *
 * @param array $actions Array de acciones
 * @param array $config  Configuración del grid
 * @return void
 */
function flavor_render_action_cards(array $actions, array $config = []): void {
    $defaults = [
        'columns' => 4,
        'gap'     => 4,
        'size'    => 'md',
        'layout'  => 'vertical',
    ];
    $config = wp_parse_args($config, $defaults);

    $grid_classes = "grid grid-cols-2 md:grid-cols-{$config['columns']} gap-{$config['gap']}";
    ?>
    <div class="<?php echo esc_attr($grid_classes); ?>">
        <?php foreach ($actions as $action):
            flavor_render_component('action-card', array_merge(
                $action,
                ['size' => $config['size'], 'layout' => $config['layout']]
            ));
        endforeach; ?>
    </div>
    <?php
}

/**
 * Renderiza un grid de KPIs.
 *
 * @param array $kpis   Array de KPIs
 * @param array $config Configuración del grid
 * @return void
 */
function flavor_render_kpis(array $kpis, array $config = []): void {
    $defaults = [
        'columns' => 4,
        'size'    => 'md',
        'compact' => false,
    ];
    $config = wp_parse_args($config, $defaults);

    flavor_render_component('kpi-grid', array_merge(
        ['kpis' => $kpis],
        $config
    ));
}

/**
 * Renderiza un gráfico con Chart.js.
 *
 * @param array $data   Datos del gráfico
 * @param array $config Configuración
 * @return void
 */
function flavor_render_chart(array $data, array $config = []): void {
    $defaults = [
        'title'   => '',
        'type'    => 'line',
        'color'   => 'blue',
        'height'  => 300,
        'filters' => [],
    ];
    $config = wp_parse_args($config, $defaults);

    flavor_render_component('chart-container', array_merge(
        ['data' => $data],
        $config
    ));
}

/**
 * Renderiza un feed de actividad.
 *
 * @param array $items  Items del feed
 * @param array $config Configuración
 * @return void
 */
function flavor_render_activity_feed(array $items, array $config = []): void {
    $defaults = [
        'title'   => __('Actividad Reciente', FLAVOR_PLATFORM_TEXT_DOMAIN),
        'color'   => 'blue',
        'limit'   => 10,
        'compact' => false,
    ];
    $config = wp_parse_args($config, $defaults);

    flavor_render_component('activity-feed', array_merge(
        ['items' => $items],
        $config
    ));
}

/**
 * Renderiza una lista de tareas.
 *
 * @param array $items  Items de la lista
 * @param array $config Configuración
 * @return void
 */
function flavor_render_todo_list(array $items, array $config = []): void {
    $defaults = [
        'title'       => __('Tareas Pendientes', FLAVOR_PLATFORM_TEXT_DOMAIN),
        'color'       => 'blue',
        'interactive' => true,
    ];
    $config = wp_parse_args($config, $defaults);

    flavor_render_component('todo-list', array_merge(
        ['items' => $items],
        $config
    ));
}

/**
 * Renderiza un mini calendario.
 *
 * @param array $events Eventos del calendario
 * @param array $config Configuración
 * @return void
 */
function flavor_render_calendar(array $events = [], array $config = []): void {
    $defaults = [
        'month'    => current_time('Y-m'),
        'color'    => 'blue',
        'show_nav' => true,
    ];
    $config = wp_parse_args($config, $defaults);

    flavor_render_component('mini-calendar', array_merge(
        ['events' => $events],
        $config
    ));
}

/**
 * Renderiza un resumen de usuario.
 *
 * @param array $user   Datos del usuario
 * @param array $config Configuración
 * @return void
 */
function flavor_render_user_summary(array $user = [], array $config = []): void {
    $defaults = [
        'color'  => 'blue',
        'layout' => 'horizontal',
        'stats'  => [],
        'badges' => [],
    ];
    $config = wp_parse_args($config, $defaults);

    flavor_render_component('user-summary', array_merge(
        ['user' => $user],
        $config
    ));
}

/**
 * Renderiza un tracker de progreso.
 *
 * @param int|array $value  Valor o pasos del progreso
 * @param array     $config Configuración
 * @return void
 */
function flavor_render_progress($value, array $config = []): void {
    $defaults = [
        'type'       => 'bar',
        'max'        => 100,
        'color'      => 'blue',
        'size'       => 'md',
        'show_value' => true,
        'format'     => 'percent',
    ];
    $config = wp_parse_args($config, $defaults);

    $args = is_array($value) ? ['steps' => $value] : ['value' => $value];

    flavor_render_component('progress-tracker', array_merge(
        $args,
        $config
    ));
}

/**
 * Renderiza un panel de configuración.
 *
 * @param array $sections Secciones de configuración
 * @param array $config   Configuración del panel
 * @return void
 */
function flavor_render_settings(array $sections, array $config = []): void {
    $defaults = [
        'title'       => __('Configuración', FLAVOR_PLATFORM_TEXT_DOMAIN),
        'color'       => 'blue',
        'ajax'        => true,
        'save_label'  => __('Guardar cambios', FLAVOR_PLATFORM_TEXT_DOMAIN),
    ];
    $config = wp_parse_args($config, $defaults);

    flavor_render_component('settings-panel', array_merge(
        ['sections' => $sections],
        $config
    ));
}

/**
 * Renderiza una barra de acciones.
 *
 * @param array $config Configuración de la barra
 * @return void
 */
function flavor_render_action_bar(array $config = []): void {
    $defaults = [
        'bulk_actions' => [],
        'filters'      => [],
        'show_search'  => true,
        'view_modes'   => [],
        'actions'      => [],
        'color'        => 'blue',
    ];
    $config = wp_parse_args($config, $defaults);

    flavor_render_component('action-bar', $config);
}

/**
 * Renderiza una comparación de estadísticas.
 *
 * @param array $current  Datos actuales
 * @param array $previous Datos anteriores
 * @param array $config   Configuración
 * @return void
 */
function flavor_render_stat_comparison(array $current, array $previous, array $config = []): void {
    $defaults = [
        'title'  => '',
        'format' => 'number',
        'color'  => 'blue',
        'layout' => 'horizontal',
    ];
    $config = wp_parse_args($config, $defaults);

    flavor_render_component('stat-comparison', array_merge(
        ['current' => $current, 'previous' => $previous],
        $config
    ));
}

/**
 * Renderiza una lista de usuarios.
 *
 * @param array $users  Lista de usuarios
 * @param array $config Configuración
 * @return void
 */
function flavor_render_user_list(array $users, array $config = []): void {
    $defaults = [
        'title'      => __('Usuarios', FLAVOR_PLATFORM_TEXT_DOMAIN),
        'columns'    => ['avatar', 'name', 'email', 'role', 'status', 'actions'],
        'actions'    => [],
        'color'      => 'blue',
        'selectable' => false,
        'searchable' => true,
    ];
    $config = wp_parse_args($config, $defaults);

    flavor_render_component('user-list', array_merge(
        ['users' => $users],
        $config
    ));
}

/**
 * Renderiza un banner de alerta.
 *
 * @param string $message Mensaje
 * @param string $type    Tipo: info, success, warning, error, announcement
 * @param array  $config  Configuración
 * @return void
 */
function flavor_render_alert(string $message, string $type = 'info', array $config = []): void {
    $defaults = [
        'title'       => '',
        'dismissible' => true,
        'actions'     => [],
        'position'    => 'inline',
    ];
    $config = wp_parse_args($config, $defaults);

    flavor_render_component('alert-banner', array_merge(
        ['message' => $message, 'type' => $type],
        $config
    ));
}

/**
 * Renderiza un formulario wizard multi-paso.
 *
 * @param array $steps  Pasos del wizard
 * @param array $config Configuración
 * @return void
 */
function flavor_render_wizard(array $steps, array $config = []): void {
    $defaults = [
        'title'        => '',
        'color'        => 'blue',
        'submit_label' => __('Finalizar', FLAVOR_PLATFORM_TEXT_DOMAIN),
        'show_progress'=> true,
        'allow_skip'   => false,
    ];
    $config = wp_parse_args($config, $defaults);

    flavor_render_component('form-wizard', array_merge(
        ['steps' => $steps],
        $config
    ));
}

/**
 * Renderiza una lista rápida.
 *
 * @param array $items  Items de la lista
 * @param array $config Configuración
 * @return void
 */
function flavor_render_quick_list(array $items, array $config = []): void {
    $defaults = [
        'title'    => '',
        'color'    => 'blue',
        'numbered' => false,
        'bordered' => true,
        'actions'  => [],
    ];
    $config = wp_parse_args($config, $defaults);

    flavor_render_component('quick-list', array_merge(
        ['items' => $items],
        $config
    ));
}

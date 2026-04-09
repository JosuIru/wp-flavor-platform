<?php
/**
 * Module Renderer - Sistema de plantillas dinámicas para módulos
 *
 * Renderiza cualquier vista de cualquier módulo usando configuración dinámica.
 * Elimina la duplicación de código entre módulos.
 *
 * @package FlavorChatIA
 * @since 5.0.0
 */

if (!defined('ABSPATH')) {
    exit;
}

class Flavor_Module_Renderer {

    /**
     * Instancia singleton
     */
    private static $instance = null;

    /**
     * Path a las plantillas
     */
    private $templates_path;

    /**
     * Path a los componentes
     */
    private $components_path;

    /**
     * Cache de configuraciones de módulos
     */
    private $module_configs = [];

    /**
     * Obtener instancia singleton
     */
    public static function instance(): self {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Constructor
     */
    private function __construct() {
        $this->templates_path = FLAVOR_CHAT_IA_PATH . 'templates/frontend/';
        $this->components_path = FLAVOR_CHAT_IA_PATH . 'templates/components/shared/';

        // Cargar funciones helper
        if (!function_exists('flavor_render_component')) {
            require_once $this->components_path . '_functions.php';
        }
    }

    /**
     * Renderiza una vista de módulo
     *
     * @param string $module    ID del módulo (ej: 'incidencias', 'marketplace')
     * @param string $view      Vista a renderizar: 'dashboard', 'archive', 'single', 'form', 'tab'
     * @param array  $params    Parámetros adicionales (id, tab_id, etc.)
     * @return string HTML renderizado
     */
    public function render(string $module, string $view, array $params = []): string {
        $config = $this->get_module_config($module);

        if (empty($config)) {
            return $this->render_error(__('Módulo no configurado', FLAVOR_PLATFORM_TEXT_DOMAIN));
        }

        // Añadir módulo y config a params
        $params['module'] = $module;
        $params['module_config'] = $config;

        switch ($view) {
            case 'dashboard':
                return $this->render_dashboard($module, $config, $params);

            case 'archive':
            case 'listado':
                return $this->render_archive($module, $config, $params);

            case 'single':
            case 'detalle':
                return $this->render_single($module, $config, $params);

            case 'form':
            case 'formulario':
                return $this->render_form($module, $config, $params);

            case 'tab':
                return $this->render_tab($module, $config, $params);

            default:
                return $this->render_error(sprintf(__('Vista "%s" no reconocida', FLAVOR_PLATFORM_TEXT_DOMAIN), $view));
        }
    }

    /**
     * Renderiza el dashboard de un módulo
     */
    public function render_dashboard(string $module, array $config, array $params = []): string {
        $dashboard_config = $config['dashboard'] ?? [];

        // Obtener datos del módulo
        $data = $this->get_module_data($module, 'dashboard', $params);

        ob_start();
        ?>
        <div class="flavor-module-dashboard flavor-<?php echo esc_attr($module); ?>-dashboard">
            <?php
            // Header del dashboard
            if (!empty($dashboard_config['show_header']) || !isset($dashboard_config['show_header'])) {
                $this->render_component('dashboard-header', [
                    'title'    => $config['title'] ?? ucfirst($module),
                    'subtitle' => $config['subtitle'] ?? '',
                    'icon'     => $config['icon'] ?? '',
                    'color'    => $config['color'] ?? 'blue',
                    'actions'  => $dashboard_config['header_actions'] ?? [],
                ]);
            }

            // Stats/KPIs
            if (!empty($data['stats'])) {
                $this->render_component('stats-grid', [
                    'stats'   => $data['stats'],
                    'columns' => count($data['stats']) <= 2 ? 2 : 4,
                    'layout'  => $dashboard_config['stats_layout'] ?? 'horizontal',
                ]);
            }

            // Acciones rápidas
            if (!empty($dashboard_config['quick_actions'])) {
                echo '<div class="my-6">';
                $this->render_component('action-cards', [
                    'actions' => $dashboard_config['quick_actions'],
                    'columns' => 4,
                ]);
                echo '</div>';
            }

            // Grid de widgets
            if (!empty($dashboard_config['widgets'])) {
                echo '<div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mt-6">';
                foreach ($dashboard_config['widgets'] as $widget) {
                    $this->render_widget($module, $widget, $data);
                }
                echo '</div>';
            }

            // Items recientes
            if (!empty($data['items']) && ($dashboard_config['show_recent'] ?? true)) {
                echo '<div class="mt-6">';
                $this->render_component('dashboard-section', [
                    'title'   => $dashboard_config['recent_title'] ?? __('Recientes', FLAVOR_PLATFORM_TEXT_DOMAIN),
                    'icon'    => 'dashicons-clock',
                    'content' => $this->render_items_grid($data['items'], $config, ['limit' => 6]),
                ]);
                echo '</div>';
            }
            ?>
        </div>
        <?php
        return ob_get_clean();
    }

    /**
     * Renderiza un archive/listado de un módulo
     */
    public function render_archive(string $module, array $config, array $params = []): string {
        $archive_config = $config['archive'] ?? [];

        // Obtener datos
        $data = $this->get_module_data($module, 'archive', $params);

        // Usar Archive Renderer existente
        if (!class_exists('Flavor_Archive_Renderer')) {
            require_once FLAVOR_CHAT_IA_PATH . 'includes/class-archive-renderer.php';
        }

        $renderer = new Flavor_Archive_Renderer();

        return $renderer->render([
            'module'           => $module,
            'title'            => $config['title'] ?? ucfirst($module),
            'subtitle'         => $config['subtitle'] ?? '',
            'icon'             => $config['icon'] ?? '',
            'color'            => $config['color'] ?? 'blue',
            'items'            => $data['items'] ?? [],
            'total'            => $data['total'] ?? 0,
            'per_page'         => $params['per_page'] ?? 12,
            'current_page'     => $params['page'] ?? 1,
            'stats'            => $data['stats'] ?? [],
            'filters'          => $archive_config['filters'] ?? $this->get_default_filters($module, $config),
            'filter_data_attr' => $archive_config['filter_field'] ?? 'estado',
            'columns'          => $archive_config['columns'] ?? 3,
            'card_config'      => $config['card'] ?? null,
            'cta_text'         => $archive_config['cta_text'] ?? '',
            'cta_url'          => $archive_config['cta_url'] ?? '',
            'cta_icon'         => $archive_config['cta_icon'] ?? '',
            'empty_state'      => $archive_config['empty_state'] ?? [],
        ]);
    }

    /**
     * Renderiza la vista single/detalle de un item
     */
    public function render_single(string $module, array $config, array $params = []): string {
        $single_config = $config['single'] ?? [];
        $item_id = $params['id'] ?? 0;

        if (!$item_id) {
            return $this->render_error(__('ID no especificado', FLAVOR_PLATFORM_TEXT_DOMAIN));
        }

        // Obtener item
        $item = $this->get_single_item($module, $item_id, $config);

        if (!$item) {
            return $this->render_error(__('Elemento no encontrado', FLAVOR_PLATFORM_TEXT_DOMAIN));
        }

        $color_classes = function_exists('flavor_get_color_classes')
            ? flavor_get_color_classes($config['color'] ?? 'blue')
            : ['bg' => 'bg-blue-100', 'text' => 'text-blue-700'];

        ob_start();
        ?>
        <div class="flavor-module-single flavor-<?php echo esc_attr($module); ?>-single">
            <!-- Breadcrumb -->
            <nav class="mb-4 text-sm">
                <a href="<?php echo esc_url(Flavor_Chat_Helpers::get_action_url(str_replace('-', '_', $module), '')); ?>"
                   class="text-gray-500 hover:text-gray-700">
                    <?php echo esc_html($config['title'] ?? ucfirst($module)); ?>
                </a>
                <span class="mx-2 text-gray-400">/</span>
                <span class="text-gray-900"><?php echo esc_html($item['titulo'] ?? $item['title'] ?? '#' . $item_id); ?></span>
            </nav>

            <div class="bg-white rounded-2xl shadow-sm overflow-hidden">
                <!-- Header con imagen -->
                <?php if (!empty($item['imagen']) || !empty($item['image'])): ?>
                    <div class="aspect-video bg-gray-100 relative">
                        <img src="<?php echo esc_url($item['imagen'] ?? $item['image']); ?>"
                             alt="<?php echo esc_attr($item['titulo'] ?? ''); ?>"
                             class="w-full h-full object-cover">
                        <?php if (!empty($item['estado']) || !empty($item['status'])): ?>
                            <span class="absolute top-4 right-4 px-3 py-1 rounded-full text-sm font-medium <?php echo esc_attr($color_classes['bg'] . ' ' . $color_classes['text']); ?>">
                                <?php echo esc_html(ucfirst($item['estado'] ?? $item['status'])); ?>
                            </span>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>

                <div class="p-6 lg:p-8">
                    <!-- Título y meta -->
                    <div class="mb-6">
                        <h1 class="text-2xl lg:text-3xl font-bold text-gray-900 mb-2">
                            <?php echo esc_html($item['titulo'] ?? $item['title'] ?? ''); ?>
                        </h1>

                        <?php if (!empty($single_config['meta_fields'])): ?>
                            <div class="flex flex-wrap gap-4 text-sm text-gray-500">
                                <?php foreach ($single_config['meta_fields'] as $meta): ?>
                                    <?php $value = $item[$meta['field']] ?? ''; ?>
                                    <?php if ($value): ?>
                                        <span class="flex items-center gap-1">
                                            <?php if (!empty($meta['icon'])): ?>
                                                <span><?php echo esc_html($meta['icon']); ?></span>
                                            <?php endif; ?>
                                            <?php echo esc_html($meta['prefix'] ?? ''); ?>
                                            <?php echo esc_html($value); ?>
                                            <?php echo esc_html($meta['suffix'] ?? ''); ?>
                                        </span>
                                    <?php endif; ?>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                    </div>

                    <!-- Contenido principal -->
                    <?php if (!empty($item['descripcion']) || !empty($item['content'])): ?>
                        <div class="prose prose-lg max-w-none mb-6">
                            <?php echo wp_kses_post($item['descripcion'] ?? $item['content'] ?? ''); ?>
                        </div>
                    <?php endif; ?>

                    <!-- Campos adicionales -->
                    <?php if (!empty($single_config['detail_fields'])): ?>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4 p-4 bg-gray-50 rounded-xl mb-6">
                            <?php foreach ($single_config['detail_fields'] as $field): ?>
                                <?php $value = $item[$field['field']] ?? ''; ?>
                                <?php if ($value): ?>
                                    <div>
                                        <dt class="text-sm text-gray-500"><?php echo esc_html($field['label']); ?></dt>
                                        <dd class="font-medium text-gray-900"><?php echo esc_html($value); ?></dd>
                                    </div>
                                <?php endif; ?>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>

                    <!-- Acciones -->
                    <?php if (!empty($single_config['actions'])): ?>
                        <div class="flex flex-wrap gap-3 pt-4 border-t border-gray-100">
                            <?php foreach ($single_config['actions'] as $action): ?>
                                <?php
                                $bg_solid = isset($color_classes['bg_solid']) ? $color_classes['bg_solid'] : 'bg-blue-500';
                                $btn_class = ($action['primary'] ?? false)
                                    ? "px-4 py-2 rounded-xl text-white {$bg_solid} hover:opacity-90"
                                    : "px-4 py-2 rounded-xl {$color_classes['bg']} {$color_classes['text']} hover:opacity-80";
                                ?>
                                <button class="<?php echo esc_attr($btn_class); ?>"
                                        <?php if (!empty($action['action'])): ?>
                                        onclick="<?php echo esc_attr(str_replace('{id}', $item_id, $action['action'])); ?>"
                                        <?php endif; ?>>
                                    <?php if (!empty($action['icon'])): ?>
                                        <span class="mr-1"><?php echo esc_html($action['icon']); ?></span>
                                    <?php endif; ?>
                                    <?php echo esc_html($action['label']); ?>
                                </button>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Sidebar / Relacionados -->
            <?php if (!empty($single_config['sidebar'])): ?>
                <div class="mt-6 grid grid-cols-1 lg:grid-cols-3 gap-6">
                    <?php foreach ($single_config['sidebar'] as $sidebar_widget): ?>
                        <div class="bg-white rounded-xl p-4 shadow-sm">
                            <?php $this->render_sidebar_widget($module, $sidebar_widget, $item); ?>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
        <?php
        return ob_get_clean();
    }

    /**
     * Renderiza un formulario de módulo
     */
    public function render_form(string $module, array $config, array $params = []): string {
        $form_config = $config['form'] ?? [];
        $item_id = $params['id'] ?? 0;
        $item = $item_id ? $this->get_single_item($module, $item_id, $config) : [];

        $is_edit = !empty($item);
        $form_title = $is_edit
            ? ($form_config['edit_title'] ?? __('Editar', FLAVOR_PLATFORM_TEXT_DOMAIN))
            : ($form_config['create_title'] ?? __('Crear nuevo', FLAVOR_PLATFORM_TEXT_DOMAIN));

        $color_classes = function_exists('flavor_get_color_classes')
            ? flavor_get_color_classes($config['color'] ?? 'blue')
            : ['bg_solid' => 'bg-blue-500'];

        ob_start();
        ?>
        <div class="flavor-module-form flavor-<?php echo esc_attr($module); ?>-form">
            <div class="bg-white rounded-2xl shadow-sm p-6 lg:p-8">
                <!-- Header -->
                <div class="mb-6">
                    <h2 class="text-xl font-bold text-gray-900 flex items-center gap-2">
                        <?php if (!empty($config['icon'])): ?>
                            <span><?php echo esc_html($config['icon']); ?></span>
                        <?php endif; ?>
                        <?php echo esc_html($form_title); ?>
                    </h2>
                    <?php if (!empty($form_config['description'])): ?>
                        <p class="text-gray-500 mt-1"><?php echo esc_html($form_config['description']); ?></p>
                    <?php endif; ?>
                </div>

                <!-- Formulario -->
                <form id="flavor-form-<?php echo esc_attr($module); ?>"
                      class="space-y-6"
                      data-module="<?php echo esc_attr($module); ?>"
                      data-item-id="<?php echo esc_attr($item_id); ?>">

                    <?php wp_nonce_field("flavor_{$module}_form", "flavor_{$module}_nonce"); ?>

                    <?php if (!empty($form_config['fields'])): ?>
                        <?php foreach ($form_config['fields'] as $field): ?>
                            <?php $this->render_form_field($field, $item); ?>
                        <?php endforeach; ?>
                    <?php endif; ?>

                    <!-- Botones -->
                    <div class="flex justify-end gap-3 pt-4 border-t border-gray-100">
                        <a href="<?php echo esc_url(Flavor_Chat_Helpers::get_action_url(str_replace('-', '_', $module), '')); ?>"
                           class="px-4 py-2 rounded-xl text-gray-600 hover:bg-gray-100 transition-colors">
                            <?php _e('Cancelar', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                        </a>
                        <button type="submit"
                                class="px-6 py-2 rounded-xl text-white <?php echo esc_attr($color_classes['bg_solid']); ?> hover:opacity-90 transition-colors">
                            <?php echo $is_edit ? __('Guardar cambios', FLAVOR_PLATFORM_TEXT_DOMAIN) : __('Crear', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <script>
        document.getElementById('flavor-form-<?php echo esc_js($module); ?>').addEventListener('submit', function(e) {
            e.preventDefault();
            const form = e.target;
            const formData = new FormData(form);
            formData.append('action', 'flavor_<?php echo esc_js($module); ?>_save');

            fetch(flavorAjax.url, {
                method: 'POST',
                body: formData,
                credentials: 'same-origin'
            })
            .then(r => r.json())
            .then(data => {
                if (data.success) {
                    window.location.href = data.data.redirect || '<?php echo esc_js(Flavor_Chat_Helpers::get_action_url(str_replace('-', '_', $module), '')); ?>';
                } else {
                    alert(data.data.message || 'Error al guardar');
                }
            })
            .catch(err => {
                console.error(err);
                alert('Error de conexión');
            });
        });
        </script>
        <?php
        return ob_get_clean();
    }

    /**
     * Renderiza una tab específica
     */
    public function render_tab(string $module, array $config, array $params = []): string {
        $tab_id = $params['tab'] ?? 'listado';
        $tabs_config = $config['tabs'] ?? [];

        // Buscar la tab
        $tab = $tabs_config[$tab_id] ?? null;

        if (!$tab) {
            // Tab por defecto: archive
            return $this->render_archive($module, $config, $params);
        }

        // Si la tab tiene content tipo template
        if (!empty($tab['content'])) {
            $content = $tab['content'];

            // Template directo
            if (strpos($content, 'template:') === 0) {
                $template_name = substr($content, 9);
                return $this->render_template($module, $template_name, $params);
            }

            // Shortcode con formato shortcode:nombre
            if (strpos($content, 'shortcode:') === 0) {
                $shortcode_name = substr($content, 10);
                return do_shortcode('[' . $shortcode_name . ']');
            }

            // Vista específica
            if (in_array($content, ['dashboard', 'archive', 'single', 'form'])) {
                return $this->render($module, $content, $params);
            }

            // Shortcode directo con corchetes
            if (strpos($content, '[') === 0) {
                return do_shortcode($content);
            }
        }

        // Renderizar según el tipo de tab
        $tab_type = $tab['type'] ?? 'archive';
        return $this->render($module, $tab_type, $params);
    }

    /**
     * Obtiene datos del módulo desde la BD
     */
    private function get_module_data(string $module, string $context, array $params = []): array {
        global $wpdb;

        $config = $this->get_module_config($module);
        $db_config = $config['database'] ?? [];

        if (empty($db_config['table'])) {
            return ['items' => [], 'stats' => [], 'total' => 0];
        }

        $table = $wpdb->prefix . $db_config['table'];

        // Verificar tabla
        if (!Flavor_Chat_Helpers::tabla_existe($table)) {
            return ['items' => [], 'stats' => [], 'total' => 0];
        }

        // Paginación
        $per_page = $params['per_page'] ?? 12;
        $page = $params['page'] ?? max(1, intval($_GET['pag'] ?? 1));
        $offset = ($page - 1) * $per_page;

        // Construir WHERE
        $where = ['1=1'];
        $query_params = [];

        if (!empty($db_config['exclude_status'])) {
            $status_field = $db_config['status_field'] ?? 'estado';
            $where[] = "{$status_field} != %s";
            $query_params[] = $db_config['exclude_status'];
        }

        // Filtros desde URL
        if (!empty($db_config['filter_fields'])) {
            foreach ($db_config['filter_fields'] as $field) {
                if (!empty($_GET[$field]) && $_GET[$field] !== 'todos') {
                    $where[] = "{$field} = %s";
                    $query_params[] = sanitize_text_field($_GET[$field]);
                }
            }
        }

        $where_sql = implode(' AND ', $where);
        $order_by = $db_config['order_by'] ?? 'created_at DESC';

        // Total
        $count_sql = "SELECT COUNT(*) FROM {$table} WHERE {$where_sql}";
        $total = !empty($query_params)
            ? (int) $wpdb->get_var($wpdb->prepare($count_sql, $query_params))
            : (int) $wpdb->get_var($count_sql);

        // Items
        $limit_clause = $context === 'dashboard' ? 'LIMIT 10' : "LIMIT %d OFFSET %d";
        $items_sql = "SELECT * FROM {$table} WHERE {$where_sql} ORDER BY {$order_by} {$limit_clause}";

        if ($context !== 'dashboard') {
            $query_params[] = $per_page;
            $query_params[] = $offset;
        }

        $rows = !empty($query_params)
            ? $wpdb->get_results($wpdb->prepare($items_sql, $query_params))
            : $wpdb->get_results($items_sql);

        // Transformar items
        $items = $this->transform_items($rows, $config);

        // Stats
        $stats = $this->get_module_stats($table, $config);

        return [
            'items'        => $items,
            'total'        => $total,
            'stats'        => $stats,
            'per_page'     => $per_page,
            'current_page' => $page,
        ];
    }

    /**
     * Obtiene un item individual
     */
    private function get_single_item(string $module, int $id, array $config): ?array {
        global $wpdb;

        $db_config = $config['database'] ?? [];
        if (empty($db_config['table'])) {
            return null;
        }

        $table = $wpdb->prefix . $db_config['table'];
        $row = $wpdb->get_row($wpdb->prepare("SELECT * FROM {$table} WHERE id = %d", $id));

        if (!$row) {
            return null;
        }

        $items = $this->transform_items([$row], $config);
        return $items[0] ?? null;
    }

    /**
     * Transforma filas de BD a items
     */
    private function transform_items(array $rows, array $config): array {
        $items = [];
        $field_map = $config['fields'] ?? [];
        $module = $config['module'] ?? '';

        foreach ($rows as $row) {
            $item = ['id' => $row->id ?? 0];

            // Mapear campos
            foreach ($field_map as $target => $source) {
                if (isset($row->$source)) {
                    $item[$target] = $row->$source;
                }
            }

            // URL
            if (!isset($item['url']) && $module) {
                $item['url'] = Flavor_Chat_Helpers::get_item_url(str_replace('-', '_', $module), $item['id'], '');
            }

            // Fecha formateada
            if (isset($row->created_at)) {
                $item['fecha'] = date_i18n(get_option('date_format'), strtotime($row->created_at));
                $item['fecha_relativa'] = human_time_diff(strtotime($row->created_at), current_time('timestamp'));
            }

            $items[] = $item;
        }

        return $items;
    }

    /**
     * Obtiene estadísticas del módulo
     */
    private function get_module_stats(string $table, array $config): array {
        global $wpdb;

        $stats_config = $config['stats'] ?? [];
        if (empty($stats_config)) {
            return [];
        }

        $stats = [];
        $count_where_stats = [];
        $custom_query_stats = [];

        // Separar stats por tipo para optimizar
        foreach ($stats_config as $index => $stat) {
            if (!empty($stat['query'])) {
                $custom_query_stats[$index] = $stat;
            } elseif (!empty($stat['count_where'])) {
                $count_where_stats[$index] = $stat;
            }
        }

        // Consolidar count_where en una sola query usando CASE WHEN
        $count_results = [];
        if (!empty($count_where_stats)) {
            $case_parts = [];
            foreach ($count_where_stats as $index => $stat) {
                $case_parts[] = "SUM(CASE WHEN {$stat['count_where']} THEN 1 ELSE 0 END) AS stat_{$index}";
            }

            $consolidated_query = "SELECT " . implode(', ', $case_parts) . " FROM {$table}";
            $result = $wpdb->get_row($consolidated_query);

            if ($result) {
                foreach ($count_where_stats as $index => $stat) {
                    $count_results[$index] = (int) ($result->{"stat_{$index}"} ?? 0);
                }
            }
        }

        // Ejecutar queries personalizadas (no consolidables)
        $custom_results = [];
        foreach ($custom_query_stats as $index => $stat) {
            $custom_results[$index] = $wpdb->get_var(str_replace('{table}', $table, $stat['query']));
        }

        // Reconstruir stats en orden original
        foreach ($stats_config as $index => $stat) {
            $value = 0;
            if (isset($count_results[$index])) {
                $value = $count_results[$index];
            } elseif (isset($custom_results[$index])) {
                $value = $custom_results[$index];
            }

            $stats[] = [
                'value' => $value ?? 0,
                'label' => $stat['label'] ?? '',
                'icon'  => $stat['icon'] ?? '',
                'color' => $stat['color'] ?? 'blue',
            ];
        }

        return $stats;
    }

    /**
     * Renderiza un campo de formulario
     */
    private function render_form_field(array $field, array $item = []): void {
        $name = $field['name'] ?? '';
        $type = $field['type'] ?? 'text';
        $label = $field['label'] ?? '';
        $value = $item[$name] ?? $field['default'] ?? '';
        $required = $field['required'] ?? false;
        $placeholder = $field['placeholder'] ?? '';
        $options = $field['options'] ?? [];

        $field_id = 'field_' . $name;
        ?>
        <div class="flavor-form-field">
            <?php if ($label): ?>
                <label for="<?php echo esc_attr($field_id); ?>"
                       class="block text-sm font-medium text-gray-700 mb-1">
                    <?php echo esc_html($label); ?>
                    <?php if ($required): ?>
                        <span class="text-red-500">*</span>
                    <?php endif; ?>
                </label>
            <?php endif; ?>

            <?php
            switch ($type) {
                case 'textarea':
                    ?>
                    <textarea id="<?php echo esc_attr($field_id); ?>"
                              name="<?php echo esc_attr($name); ?>"
                              rows="<?php echo esc_attr($field['rows'] ?? 4); ?>"
                              placeholder="<?php echo esc_attr($placeholder); ?>"
                              class="w-full px-4 py-2 border border-gray-300 rounded-xl focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                              <?php echo $required ? 'required' : ''; ?>><?php echo esc_textarea($value); ?></textarea>
                    <?php
                    break;

                case 'select':
                    ?>
                    <select id="<?php echo esc_attr($field_id); ?>"
                            name="<?php echo esc_attr($name); ?>"
                            class="w-full px-4 py-2 border border-gray-300 rounded-xl focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                            <?php echo $required ? 'required' : ''; ?>>
                        <?php if ($placeholder): ?>
                            <option value=""><?php echo esc_html($placeholder); ?></option>
                        <?php endif; ?>
                        <?php foreach ($options as $opt_value => $opt_label): ?>
                            <option value="<?php echo esc_attr($opt_value); ?>"
                                    <?php selected($value, $opt_value); ?>>
                                <?php echo esc_html($opt_label); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <?php
                    break;

                case 'checkbox':
                    ?>
                    <label class="flex items-center gap-2 cursor-pointer">
                        <input type="checkbox"
                               id="<?php echo esc_attr($field_id); ?>"
                               name="<?php echo esc_attr($name); ?>"
                               value="1"
                               class="w-4 h-4 text-blue-600 rounded focus:ring-blue-500"
                               <?php checked($value, '1'); ?>>
                        <span class="text-sm text-gray-600"><?php echo esc_html($field['checkbox_label'] ?? ''); ?></span>
                    </label>
                    <?php
                    break;

                case 'image':
                    ?>
                    <div class="flex items-center gap-4">
                        <?php if ($value): ?>
                            <img src="<?php echo esc_url($value); ?>" class="w-20 h-20 object-cover rounded-lg">
                        <?php endif; ?>
                        <input type="file"
                               id="<?php echo esc_attr($field_id); ?>"
                               name="<?php echo esc_attr($name); ?>"
                               accept="image/*"
                               class="text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-xl file:border-0 file:bg-blue-50 file:text-blue-700 hover:file:bg-blue-100">
                    </div>
                    <?php
                    break;

                case 'hidden':
                    ?>
                    <input type="hidden"
                           id="<?php echo esc_attr($field_id); ?>"
                           name="<?php echo esc_attr($name); ?>"
                           value="<?php echo esc_attr($value); ?>">
                    <?php
                    break;

                default: // text, email, number, date, etc.
                    ?>
                    <input type="<?php echo esc_attr($type); ?>"
                           id="<?php echo esc_attr($field_id); ?>"
                           name="<?php echo esc_attr($name); ?>"
                           value="<?php echo esc_attr($value); ?>"
                           placeholder="<?php echo esc_attr($placeholder); ?>"
                           class="w-full px-4 py-2 border border-gray-300 rounded-xl focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                           <?php echo $required ? 'required' : ''; ?>
                           <?php if (!empty($field['min'])): ?>min="<?php echo esc_attr($field['min']); ?>"<?php endif; ?>
                           <?php if (!empty($field['max'])): ?>max="<?php echo esc_attr($field['max']); ?>"<?php endif; ?>>
                    <?php
            }

            if (!empty($field['help'])):
                ?>
                <p class="text-xs text-gray-500 mt-1"><?php echo esc_html($field['help']); ?></p>
            <?php endif; ?>
        </div>
        <?php
    }

    /**
     * Renderiza un widget del dashboard
     */
    private function render_widget(string $module, array $widget, array $data): void {
        $type = $widget['type'] ?? 'list';
        $title = $widget['title'] ?? '';

        echo '<div class="bg-white rounded-xl shadow-sm p-4">';

        if ($title) {
            echo '<h3 class="font-semibold text-gray-900 mb-3 flex items-center gap-2">';
            if (!empty($widget['icon'])) {
                echo '<span>' . esc_html($widget['icon']) . '</span>';
            }
            echo esc_html($title);
            echo '</h3>';
        }

        switch ($type) {
            case 'stats':
                $this->render_component('stats-grid', [
                    'stats'   => $widget['stats'] ?? [],
                    'columns' => 2,
                    'compact' => true,
                ]);
                break;

            case 'list':
                $this->render_component('quick-list', [
                    'items' => array_slice($data['items'] ?? [], 0, $widget['limit'] ?? 5),
                ]);
                break;

            case 'chart':
                $this->render_component('chart-container', $widget);
                break;

            case 'activity':
                $this->render_component('activity-feed', [
                    'items'   => $widget['items'] ?? [],
                    'compact' => true,
                ]);
                break;

            case 'shortcode':
                echo do_shortcode($widget['shortcode'] ?? '');
                break;

            default:
                if (!empty($widget['content'])) {
                    echo wp_kses_post($widget['content']);
                }
        }

        echo '</div>';
    }

    /**
     * Renderiza un widget de sidebar
     */
    private function render_sidebar_widget(string $module, array $widget, array $item): void {
        $type = $widget['type'] ?? 'info';

        switch ($type) {
            case 'author':
                // Info del autor
                $user_id = $item['user_id'] ?? $item['autor_id'] ?? 0;
                if ($user_id) {
                    $user = get_userdata($user_id);
                    if ($user) {
                        echo '<div class="text-center">';
                        echo get_avatar($user_id, 64, '', '', ['class' => 'rounded-full mx-auto mb-2']);
                        echo '<p class="font-medium">' . esc_html($user->display_name) . '</p>';
                        echo '</div>';
                    }
                }
                break;

            case 'related':
                // Items relacionados
                $related = $this->get_related_items($module, $item, $widget['limit'] ?? 3);
                if ($related) {
                    echo '<h4 class="font-medium mb-2">' . esc_html($widget['title'] ?? __('Relacionados', FLAVOR_PLATFORM_TEXT_DOMAIN)) . '</h4>';
                    echo '<ul class="space-y-2">';
                    foreach ($related as $rel) {
                        echo '<li><a href="' . esc_url($rel['url']) . '" class="text-sm text-blue-600 hover:underline">' . esc_html($rel['titulo'] ?? $rel['title']) . '</a></li>';
                    }
                    echo '</ul>';
                }
                break;

            case 'map':
                // Mini mapa
                if (!empty($item['latitud']) && !empty($item['longitud'])) {
                    echo '<div id="mini-map" class="h-40 rounded-lg bg-gray-100"></div>';
                }
                break;

            default:
                // Info genérica
                if (!empty($widget['fields'])) {
                    echo '<dl class="space-y-2">';
                    foreach ($widget['fields'] as $field) {
                        $value = $item[$field['field']] ?? '';
                        if ($value) {
                            echo '<div>';
                            echo '<dt class="text-xs text-gray-500">' . esc_html($field['label']) . '</dt>';
                            echo '<dd class="font-medium">' . esc_html($value) . '</dd>';
                            echo '</div>';
                        }
                    }
                    echo '</dl>';
                }
        }
    }

    /**
     * Obtiene items relacionados
     */
    private function get_related_items(string $module, array $item, int $limit = 3): array {
        global $wpdb;

        $config = $this->get_module_config($module);
        $db_config = $config['database'] ?? [];

        if (empty($db_config['table'])) {
            return [];
        }

        $table = $wpdb->prefix . $db_config['table'];
        $id = $item['id'] ?? 0;

        // Relacionar por categoría si existe
        $where = "id != %d";
        $params = [$id];

        if (!empty($item['categoria'])) {
            $where .= " AND categoria = %s";
            $params[] = $item['categoria'];
        }

        $params[] = $limit;

        $rows = $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM {$table} WHERE {$where} ORDER BY created_at DESC LIMIT %d",
            $params
        ));

        return $this->transform_items($rows, $config);
    }

    /**
     * Renderiza un grid de items
     */
    private function render_items_grid(array $items, array $config, array $params = []): string {
        ob_start();
        $this->render_component('items-grid', [
            'items'       => array_slice($items, 0, $params['limit'] ?? 12),
            'columns'     => $params['columns'] ?? 3,
            'card_config' => $config['card'] ?? null,
        ]);
        return ob_get_clean();
    }

    /**
     * Renderiza un template de módulo
     */
    private function render_template(string $module, string $template, array $params = []): string {
        $paths = [
            $this->templates_path . "{$module}/{$template}",
            $this->templates_path . "_templates/{$template}",
            $this->templates_path . "{$template}",
        ];

        foreach ($paths as $path) {
            if (file_exists($path)) {
                ob_start();
                extract($params, EXTR_SKIP);
                include $path;
                return ob_get_clean();
            }
        }

        return $this->render_error(sprintf(__('Template no encontrado: %s', FLAVOR_PLATFORM_TEXT_DOMAIN), $template));
    }

    /**
     * Renderiza un componente
     */
    private function render_component(string $component, array $args = []): void {
        if (function_exists('flavor_render_component')) {
            flavor_render_component($component, $args);
        }
    }

    /**
     * Renderiza un mensaje de error
     */
    private function render_error(string $message): string {
        return '<div class="flavor-error bg-red-50 text-red-700 p-4 rounded-xl">' . esc_html($message) . '</div>';
    }

    /**
     * Obtiene filtros por defecto
     */
    private function get_default_filters(string $module, array $config): array {
        $filters = [
            ['id' => 'todos', 'label' => __('Todos', FLAVOR_PLATFORM_TEXT_DOMAIN), 'active' => true],
        ];

        // Añadir filtros según estados configurados
        if (!empty($config['estados'])) {
            foreach ($config['estados'] as $estado_id => $estado) {
                $filters[] = [
                    'id'    => $estado_id,
                    'label' => $estado['label'] ?? ucfirst($estado_id),
                    'icon'  => $estado['icon'] ?? '',
                ];
            }
        }

        return $filters;
    }

    /**
     * Obtiene la configuración de un módulo
     *
     * Busca la configuración en el siguiente orden:
     * 1. Cache local
     * 2. Método estático get_renderer_config() del módulo
     * 3. Configuración por defecto
     *
     * @param string $module ID del módulo
     * @return array Configuración del módulo
     */
    public function get_module_config(string $module): array {
        // Verificar cache
        if (isset($this->module_configs[$module])) {
            return $this->module_configs[$module];
        }

        // Buscar la clase del módulo
        $module_class = $this->get_module_class($module);

        if ($module_class && method_exists($module_class, 'get_renderer_config')) {
            $config = $module_class::get_renderer_config();
            $this->module_configs[$module] = $config;
            return $config;
        }

        // Intentar obtener config de instancia del módulo
        $module_instance = $this->get_module_instance($module);
        if ($module_instance && method_exists($module_instance, 'get_renderer_config')) {
            $config = $module_instance->get_renderer_config();
            $this->module_configs[$module] = $config;
            return $config;
        }

        // Fallback: configuración por defecto
        $config = $this->get_default_config($module);
        $this->module_configs[$module] = $config;
        return $config;
    }

    /**
     * Obtiene el nombre de clase del módulo
     *
     * @param string $module ID del módulo
     * @return string|null Nombre de la clase o null
     */
    private function get_module_class(string $module): ?string {
        // Convertir ID a nombre de clase: incidencias → Flavor_Chat_Incidencias_Module
        $module_name = str_replace('-', '_', $module);
        $module_name = implode('_', array_map('ucfirst', explode('_', $module_name)));

        $class_names = [
            "Flavor_Chat_{$module_name}_Module",
            "Flavor_{$module_name}_Module",
            "Flavor_Module_{$module_name}",
        ];

        foreach ($class_names as $class_name) {
            if (class_exists($class_name)) {
                return $class_name;
            }
        }

        return null;
    }

    /**
     * Obtiene la instancia del módulo
     *
     * @param string $module ID del módulo
     * @return object|null Instancia del módulo o null
     */
    private function get_module_instance(string $module): ?object {
        // Intentar obtener del manager global
        if (class_exists('Flavor_Chat_Module_Manager')) {
            $manager = Flavor_Chat_Module_Manager::get_instance();
            if ($manager && method_exists($manager, 'get_module')) {
                $instance = $manager->get_module($module);
                if ($instance) {
                    return $instance;
                }
            }
        }

        // Fallback: crear instancia temporal
        $class_name = $this->get_module_class($module);
        if ($class_name && class_exists($class_name)) {
            // Verificar si tiene método estático (no necesita instancia)
            if (method_exists($class_name, 'get_renderer_config')) {
                return null; // Se usará el método estático directamente
            }
        }

        return null;
    }

    /**
     * Configuración por defecto para módulos sin config
     */
    private function get_default_config(string $module): array {
        return [
            'module'   => $module,
            'title'    => ucfirst(str_replace('-', ' ', $module)),
            'subtitle' => '',
            'icon'     => '📦',
            'color'    => 'blue',
            'database' => [
                'table' => "flavor_{$module}",
            ],
            'fields'   => [
                'titulo'      => 'titulo',
                'descripcion' => 'descripcion',
                'estado'      => 'estado',
                'imagen'      => 'imagen',
            ],
            'tabs'     => [
                'listado' => ['label' => __('Listado', FLAVOR_PLATFORM_TEXT_DOMAIN), 'type' => 'archive'],
            ],
        ];
    }
}

/**
 * Función helper para acceso rápido al renderer
 */
function flavor_render_module(string $module, string $view, array $params = []): string {
    return Flavor_Module_Renderer::instance()->render($module, $view, $params);
}

/**
 * Función helper para obtener config de módulo
 */
function flavor_get_module_config(string $module): array {
    return Flavor_Module_Renderer::instance()->get_module_config($module);
}

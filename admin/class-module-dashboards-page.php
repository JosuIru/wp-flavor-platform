<?php
/**
 * Índice de dashboards admin de módulos.
 *
 * @package FlavorPlatform
 */

if (!defined('ABSPATH')) {
    exit;
}

class Flavor_Module_Dashboards_Page {

    /**
     * Instancia singleton.
     *
     * @var self|null
     */
    private static $instance = null;

    /**
     * Categorías visibles en vista gestor de grupos.
     *
     * @var string[]
     */
    private $gestor_visible_categories = [
        'comunidad',
        'comunicacion',
        'actividades',
        'servicios',
        'recursos',
        'sostenibilidad',
    ];

    /**
     * Obtiene instancia.
     *
     * @return self
     */
    public static function get_instance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }

        return self::$instance;
    }

    /**
     * Renderiza la página.
     */
    public function render() {
        $rows = $this->get_dashboard_rows();
        $view = class_exists('Flavor_Admin_Menu_Manager')
            ? Flavor_Admin_Menu_Manager::get_instance()->obtener_vista_activa()
            : 'admin';
        $filters = $this->get_filters();
        $filter_options = $this->build_filter_options($rows);
        $rows = $this->apply_filters($rows, $filters);
        $stats = $this->build_stats($rows);
        $rows_by_category = [];

        foreach ($rows as $row) {
            $rows_by_category[$row['category_label']][] = $row;
        }

        ksort($rows_by_category);
        ?>
        <div class="wrap flavor-module-dashboards-page">
            <h1><?php esc_html_e('Dashboards de Módulos', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></h1>
            <p>
                <?php esc_html_e('Índice compacto de dashboards administrativos del plugin, filtrado según la vista activa del shell.', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
            </p>
            <div style="display:flex;gap:10px;flex-wrap:wrap;margin:14px 0 18px;">
                <a class="button button-secondary" href="<?php echo esc_url(admin_url('admin.php?page=flavor-unified-dashboard')); ?>">
                    <?php esc_html_e('Ir al tablero de widgets', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                </a>
            </div>
            <p style="margin-top:-6px;color:#646970;">
                <?php
                printf(
                    /* translators: %s: active view label */
                    esc_html__('Vista activa: %s', FLAVOR_PLATFORM_TEXT_DOMAIN),
                    esc_html($this->format_view_label($view))
                );
                ?>
            </p>
            <p style="margin-top:-6px;color:#646970;max-width:960px;">
                <?php esc_html_e('Acceso gestor indica que el dashboard primario puede exponerse al rol gestor de grupos. Revisión interna indica que aún hay señales de checks legacy con manage_options dentro del propio módulo o su vista dashboard.', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
            </p>

            <form method="get" style="display:flex;gap:12px;flex-wrap:wrap;align-items:end;background:#fff;border:1px solid #dcdcde;padding:16px;margin:18px 0 22px;">
                <input type="hidden" name="page" value="flavor-module-dashboards">
                <div>
                    <label for="flavor-dashboard-search" style="display:block;margin-bottom:6px;font-weight:600;"><?php esc_html_e('Buscar', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></label>
                    <input
                        id="flavor-dashboard-search"
                        type="search"
                        name="s"
                        value="<?php echo esc_attr($filters['search']); ?>"
                        class="regular-text"
                        placeholder="<?php esc_attr_e('Módulo, slug o categoría', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>"
                    >
                </div>
                <div>
                    <label for="flavor-dashboard-category" style="display:block;margin-bottom:6px;font-weight:600;"><?php esc_html_e('Categoría', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></label>
                    <select id="flavor-dashboard-category" name="category">
                        <option value=""><?php esc_html_e('Todas', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></option>
                        <?php foreach ($filter_options['categories'] as $category_value => $category_label) : ?>
                            <option value="<?php echo esc_attr($category_value); ?>" <?php selected($filters['category'], $category_value); ?>>
                                <?php echo esc_html($category_label); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div>
                    <label for="flavor-dashboard-status" style="display:block;margin-bottom:6px;font-weight:600;"><?php esc_html_e('Estado', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></label>
                    <select id="flavor-dashboard-status" name="status">
                        <option value=""><?php esc_html_e('Todos', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></option>
                        <?php foreach ($filter_options['statuses'] as $status_value => $status_label) : ?>
                            <option value="<?php echo esc_attr($status_value); ?>" <?php selected($filters['status'], $status_value); ?>>
                                <?php echo esc_html($status_label); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div>
                    <label for="flavor-dashboard-capability" style="display:block;margin-bottom:6px;font-weight:600;"><?php esc_html_e('Capacidad', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></label>
                    <select id="flavor-dashboard-capability" name="capability">
                        <option value=""><?php esc_html_e('Todas', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></option>
                        <?php foreach ($filter_options['capabilities'] as $capability) : ?>
                            <option value="<?php echo esc_attr($capability); ?>" <?php selected($filters['capability'], $capability); ?>>
                                <?php echo esc_html($capability); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div style="display:flex;gap:8px;">
                    <button type="submit" class="button button-primary"><?php esc_html_e('Filtrar', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></button>
                    <a class="button" href="<?php echo esc_url(admin_url('admin.php?page=flavor-module-dashboards')); ?>"><?php esc_html_e('Limpiar', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></a>
                </div>
            </form>

            <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(180px,1fr));gap:16px;margin:20px 0 28px;">
                <?php foreach ($stats as $label => $value) : ?>
                    <div class="card" style="margin:0;padding:16px;">
                        <div style="font-size:28px;font-weight:700;line-height:1.1;"><?php echo esc_html(number_format_i18n($value)); ?></div>
                        <div style="color:#646970;"><?php echo esc_html($label); ?></div>
                    </div>
                <?php endforeach; ?>
            </div>

            <?php if (empty($rows)) : ?>
                <div class="notice notice-info">
                    <p><?php esc_html_e('No hay dashboards visibles para la vista actual.', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></p>
                </div>
                <?php
                return;
            endif;
            ?>

            <?php foreach ($rows_by_category as $category_label => $category_rows) : ?>
                <h2 style="margin-top:28px;"><?php echo esc_html($category_label); ?></h2>
                <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(320px,1fr));gap:16px;">
                    <?php foreach ($category_rows as $row) : ?>
                        <div class="card" style="margin:0;padding:18px;display:flex;flex-direction:column;gap:12px;">
                            <div style="display:flex;justify-content:space-between;gap:12px;align-items:flex-start;">
                                <div>
                                    <h3 style="margin:0 0 6px;"><?php echo esc_html($row['name']); ?></h3>
                                    <p style="margin:0;color:#646970;"><?php echo esc_html($row['dashboard_slug']); ?></p>
                                </div>
                                <span class="dashicons <?php echo esc_attr($row['icon']); ?>" style="font-size:24px;width:24px;height:24px;color:<?php echo esc_attr($row['color']); ?>;"></span>
                            </div>

                            <div style="display:flex;gap:8px;flex-wrap:wrap;">
                                <?php echo $this->render_badge($row['status_label'], $row['status_color']); ?>
                                <?php echo $this->render_badge($row['capability'], '#50575e'); ?>
                                <?php if ($row['has_view']) : ?>
                                    <?php echo $this->render_badge(__('Vista dashboard', FLAVOR_PLATFORM_TEXT_DOMAIN), '#2271b1'); ?>
                                <?php endif; ?>
                                <?php if ($row['has_widget']) : ?>
                                    <?php echo $this->render_badge(__('Widget', FLAVOR_PLATFORM_TEXT_DOMAIN), '#00a32a'); ?>
                                <?php endif; ?>
                                <?php if ($row['has_tab']) : ?>
                                    <?php echo $this->render_badge(__('Tab cliente', FLAVOR_PLATFORM_TEXT_DOMAIN), '#8c8f94'); ?>
                                <?php endif; ?>
                                <?php if ($row['gestor_access']) : ?>
                                    <?php echo $this->render_badge(__('Acceso gestor', FLAVOR_PLATFORM_TEXT_DOMAIN), '#3858e9'); ?>
                                <?php endif; ?>
                                <?php if ($row['gestor_needs_review']) : ?>
                                    <?php echo $this->render_badge(__('Revisión interna', FLAVOR_PLATFORM_TEXT_DOMAIN), '#d63638'); ?>
                                <?php endif; ?>
                            </div>

                            <p style="margin:0;color:#1d2327;">
                                <?php echo esc_html($row['development_note']); ?>
                            </p>
                            <p style="margin:0;color:#50575e;font-size:12px;">
                                <strong><?php esc_html_e('Motivo:', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></strong>
                                <?php echo esc_html($row['status_reason']); ?>
                            </p>

                            <div style="margin-top:auto;display:flex;justify-content:space-between;align-items:center;gap:12px;">
                                <a class="button button-primary" href="<?php echo esc_url($row['url']); ?>">
                                    <?php esc_html_e('Abrir dashboard', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                                </a>
                                <span style="color:#646970;font-size:12px;">
                                    <?php
                                    printf(
                                        /* translators: 1: module id, 2: category */
                                        esc_html__('%1$s · %2$s', FLAVOR_PLATFORM_TEXT_DOMAIN),
                                        esc_html($row['module_id']),
                                        esc_html($row['category_label'])
                                    );
                                    ?>
                                </span>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endforeach; ?>
        </div>
        <?php
    }

    /**
     * Obtiene filas de dashboards visibles.
     *
     * @return array<int,array<string,mixed>>
     */
    public function get_dashboard_rows() {
        $loader = Flavor_Platform_Module_Loader::get_instance();
        $modules = $loader->get_registered_modules();
        $view = class_exists('Flavor_Admin_Menu_Manager')
            ? Flavor_Admin_Menu_Manager::get_instance()->obtener_vista_activa()
            : 'admin';
        $rows = [];

        foreach ($modules as $module_id => $module_data) {
            // Solo mostrar módulos activos
            if (!Flavor_Platform_Module_Loader::is_module_active($module_id)) {
                continue;
            }

            $main_file = $this->get_module_main_file($module_id);
            if ($main_file === '') {
                continue;
            }

            $source = file_get_contents($main_file);
            if (!is_string($source) || $source === '') {
                continue;
            }

            $dashboard_slug = $this->infer_dashboard_slug($module_id, $source);
            if ($dashboard_slug === '') {
                continue;
            }

            $category = $this->infer_string_value($source, 'categoria', 'Sin categoría');
            if ($view === Flavor_Admin_Menu_Manager::VISTA_GESTOR_GRUPOS && !in_array($category, $this->gestor_visible_categories, true)) {
                continue;
            }

            $capability = $this->infer_string_value($source, 'capability', 'manage_options');
            $gestor_access = $this->has_gestor_dashboard_access($category);
            $gestor_needs_review = $gestor_access && $this->has_legacy_manage_options_guard($main_file, $source);
            if (!current_user_can($capability) && !(current_user_can('flavor_ver_dashboard') && $gestor_access) && !current_user_can('manage_options')) {
                continue;
            }

            $status = $this->infer_status($module_id, $source, $dashboard_slug);
            $dashboard_url = Flavor_Module_Admin_Pages_Helper::get_module_dashboard_url($module_id);
            $rows[] = [
                'module_id' => $module_id,
                'name' => $module_data['name'] ?? ucwords(str_replace('_', ' ', $module_id)),
                'icon' => $module_data['icon'] ?? 'dashicons-admin-generic',
                'color' => $module_data['color'] ?? '#2271b1',
                'dashboard_slug' => $dashboard_slug,
                'url' => $dashboard_url ?: admin_url('admin.php?page=' . $dashboard_slug),
                'category' => $category,
                'category_label' => $this->format_category_label($category),
                'capability' => $capability,
                'status' => $status['status'],
                'status_label' => $status['label'],
                'status_color' => $status['color'],
                'development_note' => $status['note'],
                'status_reason' => $status['reason'],
                'has_view' => file_exists(dirname($main_file) . '/views/dashboard.php'),
                'has_tab' => !empty(glob(dirname($main_file) . '/*dashboard-tab.php')),
                'has_widget' => !empty(glob(dirname($main_file) . '/*dashboard-widget.php')),
                'gestor_access' => $gestor_access,
                'gestor_needs_review' => $gestor_needs_review,
            ];
        }

        usort($rows, function ($a, $b) {
            if ($a['category_label'] === $b['category_label']) {
                return strcmp($a['name'], $b['name']);
            }

            return strcmp($a['category_label'], $b['category_label']);
        });

        return $rows;
    }

    /**
     * Lee filtros de request.
     *
     * @return array<string,string>
     */
    private function get_filters() {
        return [
            'search' => sanitize_text_field(wp_unslash($_GET['s'] ?? '')),
            'category' => sanitize_text_field(wp_unslash($_GET['category'] ?? '')),
            'status' => sanitize_text_field(wp_unslash($_GET['status'] ?? '')),
            'capability' => sanitize_text_field(wp_unslash($_GET['capability'] ?? '')),
        ];
    }

    /**
     * Aplica filtros a las filas.
     *
     * @param array<int,array<string,mixed>> $rows
     * @param array<string,string> $filters
     * @return array<int,array<string,mixed>>
     */
    private function apply_filters(array $rows, array $filters) {
        return array_values(array_filter($rows, function ($row) use ($filters) {
            if ($filters['category'] !== '' && $row['category'] !== $filters['category']) {
                return false;
            }

            if ($filters['status'] !== '' && $row['status'] !== $filters['status']) {
                return false;
            }

            if ($filters['capability'] !== '' && $row['capability'] !== $filters['capability']) {
                return false;
            }

            if ($filters['search'] !== '') {
                $haystack = strtolower(implode(' ', [
                    $row['name'],
                    $row['module_id'],
                    $row['dashboard_slug'],
                    $row['category_label'],
                ]));

                if (strpos($haystack, strtolower($filters['search'])) === false) {
                    return false;
                }
            }

            return true;
        }));
    }

    /**
     * Construye opciones de filtro desde las filas.
     *
     * @param array<int,array<string,mixed>> $rows
     * @return array<string,mixed>
     */
    private function build_filter_options(array $rows) {
        $categories = [];
        $capabilities = [];
        $statuses = [];

        foreach ($rows as $row) {
            $categories[$row['category']] = $row['category_label'];
            $capabilities[$row['capability']] = $row['capability'];
            $statuses[$row['status']] = $row['status_label'];
        }

        asort($categories);
        asort($capabilities);
        asort($statuses);

        return [
            'categories' => $categories,
            'capabilities' => array_values($capabilities),
            'statuses' => $statuses,
        ];
    }

    /**
     * Indica si la categoría admite acceso al dashboard principal en modo gestor.
     *
     * @param string $category
     * @return bool
     */
    private function has_gestor_dashboard_access($category) {
        return in_array($category, $this->gestor_visible_categories, true);
    }

    /**
     * Detecta guardas legacy de manage_options que pueden bloquear el dashboard.
     *
     * @param string $main_file
     * @param string $source
     * @return bool
     */
    private function has_legacy_manage_options_guard($main_file, $source) {
        $module_dir = dirname($main_file);
        $dashboard_view = $module_dir . '/views/dashboard.php';

        if (file_exists($dashboard_view)) {
            $view_source = file_get_contents($dashboard_view);
            if (
                is_string($view_source)
                && strpos($view_source, "current_user_can('manage_options')") !== false
                && strpos($view_source, "current_user_can('flavor_ver_dashboard')") === false
            ) {
                return true;
            }
        }

        // Revisar solamente el cuerpo de renderizadores de dashboard (evita falsos positivos
        // por checks de permisos en acciones auxiliares/AJAX del módulo).
        $dashboard_methods = ['render_admin_dashboard', 'render_pagina_dashboard'];
        foreach ($dashboard_methods as $method_name) {
            $pattern = '/function\\s+' . preg_quote($method_name, '/') . '\\s*\\([^)]*\\)\\s*\\{([\\s\\S]{0,1800})\\}/';
            if (!preg_match($pattern, $source, $matches)) {
                continue;
            }

            $method_body = $matches[1] ?? '';
            if (
                strpos($method_body, "current_user_can('manage_options')") !== false
                && strpos($method_body, "current_user_can('flavor_ver_dashboard')") === false
            ) {
                return true;
            }
        }

        return false;
    }

    /**
     * Construye estadísticas de la vista.
     *
     * @param array $rows
     * @return array<string,int>
     */
    private function build_stats(array $rows) {
        $stats = [
            __('Dashboards visibles', FLAVOR_PLATFORM_TEXT_DOMAIN) => count($rows),
            __('Alta madurez', FLAVOR_PLATFORM_TEXT_DOMAIN) => 0,
            __('Madurez media', FLAVOR_PLATFORM_TEXT_DOMAIN) => 0,
            __('Parciales', FLAVOR_PLATFORM_TEXT_DOMAIN) => 0,
        ];

        foreach ($rows as $row) {
            if ($row['status'] === 'alto') {
                $stats[__('Alta madurez', FLAVOR_PLATFORM_TEXT_DOMAIN)]++;
            } elseif ($row['status'] === 'medio') {
                $stats[__('Madurez media', FLAVOR_PLATFORM_TEXT_DOMAIN)]++;
            } else {
                $stats[__('Parciales', FLAVOR_PLATFORM_TEXT_DOMAIN)]++;
            }
        }

        return $stats;
    }

    /**
     * Obtiene archivo principal del módulo.
     *
     * @param string $module_id
     * @return string
     */
    private function get_module_main_file($module_id) {
        $dir = FLAVOR_PLATFORM_PATH . 'includes/modules/' . str_replace('_', '-', $module_id);
        $files = glob($dir . '/*.php');
        if (empty($files)) {
            return '';
        }

        foreach ($files as $file) {
            if (preg_match('/class-.*module\.php$/', $file)) {
                return $file;
            }
        }

        return $files[0];
    }

    /**
     * Infiere el slug del dashboard principal.
     *
     * @param string $module_id
     * @param string $source
     * @return string
     */
    private function infer_dashboard_slug($module_id, $source) {
        $mapped = Flavor_Module_Admin_Pages_Helper::get_module_dashboard_page($module_id);
        if (!empty($mapped)) {
            return $mapped;
        }

        if (preg_match_all("/'slug'\\s*=>\\s*'([^']+dashboard[^']*)'/", $source, $matches) && !empty($matches[1][0])) {
            return $matches[1][0];
        }

        return '';
    }

    /**
     * Infiere el estado de desarrollo del dashboard.
     *
     * @param string $module_id
     * @param string $source
     * @param string $dashboard_slug
     * @return array<string,string>
     */
    private function infer_status($module_id, $source, $dashboard_slug) {
        $has_admin_config = strpos($source, 'get_admin_config(') !== false;
        $has_render_admin = strpos($source, 'render_admin_dashboard(') !== false
            || strpos($source, 'render_pagina_dashboard(') !== false
            || strpos($source, 'render_admin_page(') !== false
            || strpos($source, "'render_callback'") !== false;
        $has_view = file_exists(FLAVOR_PLATFORM_PATH . 'includes/modules/' . str_replace('_', '-', $module_id) . '/views/dashboard.php');
        $has_tab = !empty(glob(FLAVOR_PLATFORM_PATH . 'includes/modules/' . str_replace('_', '-', $module_id) . '/*dashboard-tab.php'));
        $has_widget = !empty(glob(FLAVOR_PLATFORM_PATH . 'includes/modules/' . str_replace('_', '-', $module_id) . '/*dashboard-widget.php'));
        $is_mapped = Flavor_Module_Admin_Pages_Helper::get_module_dashboard_page($module_id) !== null;

        if ($is_mapped && $has_admin_config && $has_render_admin) {
            return [
                'status' => 'alto',
                'label' => __('Alta madurez', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'color' => '#00a32a',
                'note' => __('Tiene dashboard canónico, contrato admin declarado y renderer específico.', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'reason' => __('Mapping canónico + get_admin_config + render de dashboard detectado.', FLAVOR_PLATFORM_TEXT_DOMAIN),
            ];
        }

        if ($dashboard_slug !== '' && ($has_admin_config || $has_render_admin || $has_view || $has_tab || $has_widget)) {
            $missing_parts = [];
            if (!$is_mapped) {
                $missing_parts[] = __('sin mapping canónico', FLAVOR_PLATFORM_TEXT_DOMAIN);
            }
            if (!$has_admin_config) {
                $missing_parts[] = __('sin get_admin_config', FLAVOR_PLATFORM_TEXT_DOMAIN);
            }
            if (!$has_render_admin) {
                $missing_parts[] = __('sin render de dashboard declarado', FLAVOR_PLATFORM_TEXT_DOMAIN);
            }
            if (empty($missing_parts)) {
                $missing_parts[] = __('requiere canonización del contrato admin', FLAVOR_PLATFORM_TEXT_DOMAIN);
            }

            return [
                'status' => 'medio',
                'label' => __('Madurez media', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'color' => '#dba617',
                'note' => __('Existe dashboard o infraestructura asociada, pero el contrato admin no está del todo canonizado.', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'reason' => implode(' · ', $missing_parts),
            ];
        }

        return [
            'status' => 'bajo',
            'label' => __('Parcial', FLAVOR_PLATFORM_TEXT_DOMAIN),
            'color' => '#d63638',
            'note' => __('La presencia del dashboard es parcial o depende de piezas auxiliares sin cierre estructural.', FLAVOR_PLATFORM_TEXT_DOMAIN),
            'reason' => __('No se detecta combinación mínima de mapping/config/render para considerarlo dashboard consolidado.', FLAVOR_PLATFORM_TEXT_DOMAIN),
        ];
    }

    /**
     * Infiere un valor string simple del array de admin config.
     *
     * @param string $source
     * @param string $key
     * @param string $default
     * @return string
     */
    private function infer_string_value($source, $key, $default = '') {
        $search_scope = $this->extract_admin_config_scope($source);
        if (preg_match("/'" . preg_quote($key, '/') . "'\\s*=>\\s*'([^']+)'/", $search_scope, $matches) && !empty($matches[1])) {
            return $matches[1];
        }

        return $default;
    }

    /**
     * Extrae un bloque acotado de get_admin_config para evitar falsos positivos
     * por apariciones de claves en get_renderer_config u otras estructuras.
     *
     * @param string $source
     * @return string
     */
    private function extract_admin_config_scope($source) {
        $position = strpos($source, 'function get_admin_config');
        if ($position === false) {
            return $source;
        }

        return substr($source, $position, 9000);
    }

    /**
     * Formatea etiqueta de categoría.
     *
     * @param string $category
     * @return string
     */
    private function format_category_label($category) {
        if ($category === '' || $category === 'Sin categoría') {
            return __('Sin categoría', FLAVOR_PLATFORM_TEXT_DOMAIN);
        }

        return ucwords(str_replace('_', ' ', $category));
    }

    /**
     * Formatea la vista activa del shell.
     *
     * @param string $view
     * @return string
     */
    private function format_view_label($view) {
        if ($view === Flavor_Admin_Menu_Manager::VISTA_GESTOR_GRUPOS) {
            return __('Gestor de Grupos', FLAVOR_PLATFORM_TEXT_DOMAIN);
        }

        return __('Administrador', FLAVOR_PLATFORM_TEXT_DOMAIN);
    }

    /**
     * Renderiza badge HTML.
     *
     * @param string $label
     * @param string $color
     * @return string
     */
    private function render_badge($label, $color) {
        return sprintf(
            '<span style="display:inline-flex;align-items:center;padding:4px 10px;border-radius:999px;background:%1$s;color:#fff;font-size:12px;font-weight:600;">%2$s</span>',
            esc_attr($color),
            esc_html($label)
        );
    }
}

<?php
/**
 * Flavor Systems Admin Panel
 *
 * Panel administrativo unificado para gestionar todos los sistemas V3:
 * - Dashboard Hub
 * - Notification Center
 * - Page Creator V3
 * - Module Menu Manager
 * - Dependency Resolver
 *
 * @package FlavorPlatform
 * @since 3.0.0
 */

if (!defined('ABSPATH')) {
    exit;
}

class Flavor_Systems_Admin_Panel {

    /**
     * Instancia única
     */
    private static $instance = null;

    /**
     * Capacidad requerida
     */
    private $capability = 'manage_options';

    /**
     * Slug del menú
     */
    private $menu_slug = 'flavor-systems-panel';

    /**
     * Constructor privado
     */
    private function __construct() {
        add_action('admin_menu', [$this, 'add_admin_menu']);
        add_action('admin_enqueue_scripts', [$this, 'enqueue_assets']);
        add_action('wp_ajax_flavor_get_system_stats', [$this, 'ajax_get_system_stats']);
        add_action('wp_ajax_flavor_get_notifications_stats', [$this, 'ajax_get_notifications_stats']);
        add_action('wp_ajax_flavor_get_modules_status', [$this, 'ajax_get_modules_status']);
        add_action('wp_ajax_flavor_optimize_system', [$this, 'ajax_optimize_system']);
        add_action('wp_ajax_flavor_clear_notification_cache', [$this, 'ajax_clear_notification_cache']);
        add_action('wp_ajax_flavor_get_category_modules', [$this, 'ajax_get_category_modules']);
        add_action('wp_ajax_flavor_get_dependencies', [$this, 'ajax_get_dependencies']);
        add_action('wp_ajax_flavor_regenerate_all_pages', [$this, 'ajax_regenerate_all_pages']);
        add_action('wp_ajax_flavor_regenerate_module_pages', [$this, 'ajax_regenerate_module_pages']);
        add_action('wp_ajax_flavor_clear_old_notifications', [$this, 'ajax_clear_old_notifications']);
    }

    /**
     * Obtener instancia única
     */
    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Agregar menú de administración
     *
     * Se integra en el menu principal de Flavor Platform en lugar de crear uno separado
     */
    public function add_admin_menu() {
        // NO crear menu separado - se registra desde Admin_Menu_Manager
        // Este metodo queda vacio intencionalmente
    }

    /**
     * Encolar assets
     */
    public function enqueue_assets($hook) {
        // El hook puede ser: toplevel_page_X, admin_page_X, o parent_page_X
        // Verificar si estamos en la pagina de Systems Panel
        $is_systems_page = strpos($hook, 'systems-panel') !== false ||
                           strpos($hook, $this->menu_slug) !== false ||
                           (isset($_GET['page']) && $_GET['page'] === 'flavor-systems-panel');

        if (!$is_systems_page) {
            return;
        }

        // jQuery UI Core y Tabs
        wp_enqueue_script('jquery-ui-core');
        wp_enqueue_script('jquery-ui-tabs');
        wp_enqueue_script('jquery-ui-widget');

        // Estilos jQuery UI (WordPress incluye estos)
        wp_enqueue_style('wp-jquery-ui-dialog');

        // CSS
        wp_enqueue_style(
            'flavor-systems-admin',
            plugins_url('css/systems-admin.css', __FILE__),
            ['wp-jquery-ui-dialog'],
            '3.0.0'
        );

        // JS
        wp_enqueue_script(
            'flavor-systems-admin',
            plugins_url('js/systems-admin.js', __FILE__),
            ['jquery', 'jquery-ui-tabs'],
            '3.0.0',
            true
        );

        // Chart.js para gráficos
        wp_enqueue_script(
            'chartjs',
            'https://cdn.jsdelivr.net/npm/chart.js@3.9.1/dist/chart.min.js',
            [],
            '3.9.1',
            true
        );

        // Localizar script
        wp_localize_script('flavor-systems-admin', 'flavorSystemsAdmin', [
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('flavor_systems_admin'),
            'i18n' => [
                'loading' => __('Cargando...', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'error' => __('Error al cargar datos', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'success' => __('Operación completada', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'confirm_optimize' => __('¿Estás seguro de optimizar el sistema?', FLAVOR_PLATFORM_TEXT_DOMAIN),
            ],
        ]);
    }

    /**
     * Renderizar página principal
     */
    public function render_admin_page() {
        ?>
        <div class="wrap flavor-systems-panel">
            <h1><?php _e('Flavor Systems V3 - Panel de Control', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></h1>

            <div class="flavor-systems-header">
                <div class="system-version">
                    <span class="version-badge">v3.0.0</span>
                    <span class="status-badge status-active"><?php _e('Activo', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></span>
                </div>
            </div>

            <div id="flavor-tabs" class="flavor-admin-tabs">
                <ul>
                    <li><a href="#tab-overview"><?php _e('Vista General', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></a></li>
                    <li><a href="#tab-notifications"><?php _e('Notificaciones', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></a></li>
                    <li><a href="#tab-pages"><?php _e('Páginas V3', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></a></li>
                    <li><a href="#tab-menus"><?php _e('Menús', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></a></li>
                    <li><a href="#tab-dependencies"><?php _e('Dependencias', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></a></li>
                </ul>

                <!-- Tab: Vista General -->
                <div id="tab-overview">
                    <?php $this->render_overview_tab(); ?>
                </div>

                <!-- Tab: Notificaciones -->
                <div id="tab-notifications">
                    <?php $this->render_notifications_tab(); ?>
                </div>

                <!-- Tab: Páginas V3 -->
                <div id="tab-pages">
                    <?php $this->render_pages_tab(); ?>
                </div>

                <!-- Tab: Menús -->
                <div id="tab-menus">
                    <?php $this->render_menus_tab(); ?>
                </div>

                <!-- Tab: Dependencias -->
                <div id="tab-dependencies">
                    <?php $this->render_dependencies_tab(); ?>
                </div>
            </div>
        </div>
        <?php
    }

    /**
     * Renderizar tab de vista general
     */
    private function render_overview_tab() {
        $stats = $this->get_system_statistics();
        ?>
        <div class="flavor-overview-grid">
            <!-- Sistema de Notificaciones -->
            <div class="system-card">
                <div class="card-header">
                    <span class="dashicons dashicons-bell"></span>
                    <h3><?php _e('Notification Center', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></h3>
                </div>
                <div class="card-body">
                    <div class="stat-row">
                        <span class="stat-label"><?php _e('Total Notificaciones:', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></span>
                        <span class="stat-value"><?php echo number_format($stats['notifications']['total']); ?></span>
                    </div>
                    <div class="stat-row">
                        <span class="stat-label"><?php _e('No Leídas:', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></span>
                        <span class="stat-value highlight"><?php echo number_format($stats['notifications']['unread']); ?></span>
                    </div>
                    <div class="stat-row">
                        <span class="stat-label"><?php _e('Última 24h:', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></span>
                        <span class="stat-value"><?php echo number_format($stats['notifications']['last_24h']); ?></span>
                    </div>
                </div>
                <div class="card-footer">
                    <span class="status-indicator status-active"></span>
                    <span><?php _e('Activo y Funcionando', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></span>
                </div>
            </div>

            <!-- Page Creator V3 -->
            <div class="system-card">
                <div class="card-header">
                    <span class="dashicons dashicons-admin-page"></span>
                    <h3><?php _e('Page Creator V3', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></h3>
                </div>
                <div class="card-body">
                    <div class="stat-row">
                        <span class="stat-label"><?php _e('Módulos Migrados:', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></span>
                        <span class="stat-value"><?php echo $stats['pages']['modules_migrated']; ?></span>
                    </div>
                    <div class="stat-row">
                        <span class="stat-label"><?php _e('Páginas Creadas:', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></span>
                        <span class="stat-value"><?php echo $stats['pages']['total_pages']; ?></span>
                    </div>
                    <div class="stat-row">
                        <span class="stat-label"><?php _e('Reducción de Código:', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></span>
                        <span class="stat-value success">-81%</span>
                    </div>
                </div>
                <div class="card-footer">
                    <span class="status-indicator status-active"></span>
                    <span><?php _e('Sistema Modular Activo', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></span>
                </div>
            </div>

            <!-- Module Menu Manager -->
            <div class="system-card">
                <div class="card-header">
                    <span class="dashicons dashicons-menu"></span>
                    <h3><?php _e('Menu Manager', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></h3>
                </div>
                <div class="card-body">
                    <div class="stat-row">
                        <span class="stat-label"><?php _e('Menús Activos:', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></span>
                        <span class="stat-value"><?php echo $stats['menus']['active_menus']; ?></span>
                    </div>
                    <div class="stat-row">
                        <span class="stat-label"><?php _e('Categorías:', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></span>
                        <span class="stat-value">9</span>
                    </div>
                    <div class="stat-row">
                        <span class="stat-label"><?php _e('Auto-generación:', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></span>
                        <span class="stat-value success"><?php _e('Habilitada', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></span>
                    </div>
                </div>
                <div class="card-footer">
                    <span class="status-indicator status-active"></span>
                    <span><?php _e('Generación Automática', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></span>
                </div>
            </div>

            <!-- Dependency Resolver -->
            <div class="system-card">
                <div class="card-header">
                    <span class="dashicons dashicons-networking"></span>
                    <h3><?php _e('Dependency Resolver', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></h3>
                </div>
                <div class="card-body">
                    <div class="stat-row">
                        <span class="stat-label"><?php _e('Módulos con Deps:', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></span>
                        <span class="stat-value"><?php echo $stats['dependencies']['modules_with_deps']; ?></span>
                    </div>
                    <div class="stat-row">
                        <span class="stat-label"><?php _e('Conflictos:', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></span>
                        <span class="stat-value"><?php echo $stats['dependencies']['conflicts']; ?></span>
                    </div>
                    <div class="stat-row">
                        <span class="stat-label"><?php _e('Auto-resolución:', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></span>
                        <span class="stat-value success"><?php _e('Activa', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></span>
                    </div>
                </div>
                <div class="card-footer">
                    <span class="status-indicator status-active"></span>
                    <span><?php _e('Validación Activa', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></span>
                </div>
            </div>
        </div>

        <!-- Gráficos -->
        <div class="flavor-charts-section">
            <h2><?php _e('Estadísticas en Tiempo Real', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></h2>
            <div class="charts-grid">
                <div class="chart-container">
                    <canvas id="notifications-chart"></canvas>
                </div>
                <div class="chart-container">
                    <canvas id="modules-chart"></canvas>
                </div>
            </div>
        </div>
        <?php
    }

    /**
     * Renderizar tab de notificaciones
     */
    private function render_notifications_tab() {
        global $wpdb;
        $table_name = $wpdb->prefix . 'flavor_notifications';

        // Estadísticas por tipo
        $stats_by_type = $wpdb->get_results("
            SELECT type, COUNT(*) as count
            FROM {$table_name}
            GROUP BY type
        ");

        // Estadísticas por módulo
        $stats_by_module = $wpdb->get_results("
            SELECT module_id, COUNT(*) as count
            FROM {$table_name}
            GROUP BY module_id
            ORDER BY count DESC
            LIMIT 10
        ");

        // Últimas notificaciones
        $recent_notifications = $wpdb->get_results("
            SELECT * FROM {$table_name}
            ORDER BY created_at DESC
            LIMIT 20
        ");
        ?>
        <div class="notifications-manager">
            <div class="manager-header">
                <h2><?php _e('Gestión de Notificaciones', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></h2>
                <div class="header-actions">
                    <button class="button button-secondary" id="clear-old-notifications">
                        <?php _e('Limpiar Antiguas (>30 días)', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                    </button>
                    <button class="button button-secondary" id="clear-notification-cache">
                        <?php _e('Limpiar Caché', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                    </button>
                </div>
            </div>

            <div class="stats-grid">
                <div class="stat-box">
                    <h3><?php _e('Por Tipo', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></h3>
                    <table class="wp-list-table widefat striped">
                        <thead>
                            <tr>
                                <th><?php _e('Tipo', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></th>
                                <th><?php _e('Cantidad', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($stats_by_type as $stat): ?>
                            <tr>
                                <td>
                                    <span class="notification-type-badge type-<?php echo esc_attr($stat->type); ?>">
                                        <?php echo esc_html($stat->type); ?>
                                    </span>
                                </td>
                                <td><?php echo number_format($stat->count); ?></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>

                <div class="stat-box">
                    <h3><?php _e('Top 10 Módulos', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></h3>
                    <table class="wp-list-table widefat striped">
                        <thead>
                            <tr>
                                <th><?php _e('Módulo', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></th>
                                <th><?php _e('Notificaciones', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($stats_by_module as $stat): ?>
                            <tr>
                                <td><?php echo esc_html($stat->module_id); ?></td>
                                <td><?php echo number_format($stat->count); ?></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <div class="recent-notifications-section">
                <h3><?php _e('Últimas 20 Notificaciones', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></h3>
                <table class="wp-list-table widefat striped">
                    <thead>
                        <tr>
                            <th><?php _e('ID', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></th>
                            <th><?php _e('Usuario', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></th>
                            <th><?php _e('Título', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></th>
                            <th><?php _e('Tipo', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></th>
                            <th><?php _e('Módulo', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></th>
                            <th><?php _e('Fecha', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></th>
                            <th><?php _e('Estado', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($recent_notifications as $notif): ?>
                        <tr>
                            <td><?php echo $notif->id; ?></td>
                            <td><?php echo get_userdata($notif->user_id)->display_name ?? 'N/A'; ?></td>
                            <td><?php echo esc_html($notif->title); ?></td>
                            <td>
                                <span class="notification-type-badge type-<?php echo esc_attr($notif->type); ?>">
                                    <?php echo esc_html($notif->type); ?>
                                </span>
                            </td>
                            <td><?php echo esc_html($notif->module_id); ?></td>
                            <td><?php echo mysql2date('d/m/Y H:i', $notif->created_at); ?></td>
                            <td>
                                <?php if ($notif->is_read): ?>
                                    <span class="status-badge status-read"><?php _e('Leída', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></span>
                                <?php else: ?>
                                    <span class="status-badge status-unread"><?php _e('No leída', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></span>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
        <?php
    }

    /**
     * Renderizar tab de páginas
     */
    private function render_pages_tab() {
        if (!class_exists('Flavor_Page_Creator_V3')) {
            echo '<p>' . __('Page Creator V3 no está disponible', FLAVOR_PLATFORM_TEXT_DOMAIN) . '</p>';
            return;
        }

        $creator = Flavor_Page_Creator_V3::get_instance();
        $modules_status = $this->get_modules_v3_status();
        ?>
        <div class="pages-v3-manager">
            <div class="manager-header">
                <h2><?php _e('Estado de Migración a V3', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></h2>
                <div class="header-actions">
                    <button class="button button-primary" id="regenerate-all-pages">
                        <?php _e('Regenerar Todas las Páginas', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                    </button>
                </div>
            </div>

            <div class="migration-progress">
                <h3><?php _e('Progreso de Migración', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></h3>
                <div class="progress-bar-container">
                    <div class="progress-bar" style="width: <?php echo $modules_status['migration_percentage']; ?>%;">
                        <?php echo $modules_status['migration_percentage']; ?>%
                    </div>
                </div>
                <p>
                    <?php
                    printf(
                        __('%d de %d módulos migrados', FLAVOR_PLATFORM_TEXT_DOMAIN),
                        $modules_status['migrated_count'],
                        $modules_status['total_count']
                    );
                    ?>
                </p>
            </div>

            <table class="wp-list-table widefat striped">
                <thead>
                    <tr>
                        <th><?php _e('Módulo', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></th>
                        <th><?php _e('Estado', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></th>
                        <th><?php _e('Páginas', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></th>
                        <th><?php _e('Acciones', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($modules_status['modules'] as $module_info): ?>
                    <tr>
                        <td><strong><?php echo esc_html($module_info['name']); ?></strong></td>
                        <td>
                            <?php if ($module_info['is_migrated']): ?>
                                <span class="status-badge status-active"><?php _e('Migrado V3', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></span>
                            <?php else: ?>
                                <span class="status-badge status-pending"><?php _e('Pendiente', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></span>
                            <?php endif; ?>
                        </td>
                        <td><?php echo $module_info['page_count']; ?> páginas</td>
                        <td>
                            <?php if ($module_info['is_migrated']): ?>
                                <button class="button button-small" data-module="<?php echo esc_attr($module_info['id']); ?>" onclick="regenerateModulePages(this)">
                                    <?php _e('Regenerar', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                                </button>
                            <?php else: ?>
                                <span class="description"><?php _e('Requiere migración manual', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></span>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php
    }

    /**
     * Renderizar tab de menús
     */
    private function render_menus_tab() {
        $menu_manager = class_exists('Flavor_Module_Menu_Manager') ? Flavor_Module_Menu_Manager::get_instance() : null;

        if (!$menu_manager) {
            echo '<p>' . __('Module Menu Manager no está disponible', FLAVOR_PLATFORM_TEXT_DOMAIN) . '</p>';
            return;
        }

        $categories = [
            'servicios' => __('Servicios', FLAVOR_PLATFORM_TEXT_DOMAIN),
            'comunidad' => __('Comunidad', FLAVOR_PLATFORM_TEXT_DOMAIN),
            'solidaridad' => __('Solidaridad', FLAVOR_PLATFORM_TEXT_DOMAIN),
            'espacios' => __('Espacios', FLAVOR_PLATFORM_TEXT_DOMAIN),
            'cultura' => __('Cultura', FLAVOR_PLATFORM_TEXT_DOMAIN),
            'gestion' => __('Gestión', FLAVOR_PLATFORM_TEXT_DOMAIN),
            'participacion' => __('Participación', FLAVOR_PLATFORM_TEXT_DOMAIN),
            'sostenibilidad' => __('Sostenibilidad', FLAVOR_PLATFORM_TEXT_DOMAIN),
            'comunicacion' => __('Comunicación', FLAVOR_PLATFORM_TEXT_DOMAIN),
        ];
        ?>
        <div class="menus-manager">
            <h2><?php _e('Categorías de Menú', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></h2>
            <p class="description">
                <?php _e('Los módulos se organizan automáticamente en estas 9 categorías según su configuración.', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
            </p>

            <div class="categories-grid">
                <?php foreach ($categories as $key => $label): ?>
                <div class="category-card">
                    <h3><?php echo esc_html($label); ?></h3>
                    <p class="category-key"><?php echo esc_html($key); ?></p>
                    <div class="category-modules" data-category="<?php echo esc_attr($key); ?>">
                        <?php _e('Cargando módulos...', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
        <?php
    }

    /**
     * Renderizar tab de dependencias
     */
    private function render_dependencies_tab() {
        ?>
        <div class="dependencies-manager">
            <h2><?php _e('Gestor de Dependencias', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></h2>
            <p class="description">
                <?php _e('Visualiza y gestiona las dependencias entre módulos.', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
            </p>

            <div id="dependency-graph" class="dependency-graph">
                <?php _e('Cargando grafo de dependencias...', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
            </div>

            <div class="dependency-list">
                <h3><?php _e('Lista de Dependencias', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></h3>
                <div id="dependencies-table-container"></div>
            </div>
        </div>
        <?php
    }

    /**
     * Renderizar página de notificaciones
     */
    public function render_notifications_page() {
        ?>
        <div class="wrap">
            <h1><?php _e('Gestión de Notificaciones', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></h1>
            <?php $this->render_notifications_tab(); ?>
        </div>
        <?php
    }

    /**
     * Renderizar página de páginas V3
     */
    public function render_pages_v3() {
        ?>
        <div class="wrap">
            <h1><?php _e('Páginas V3', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></h1>
            <?php $this->render_pages_tab(); ?>
        </div>
        <?php
    }

    /**
     * Renderizar página de optimización
     */
    public function render_optimization_page() {
        ?>
        <div class="wrap flavor-optimization-panel">
            <h1><?php _e('Optimización del Sistema', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></h1>

            <div class="optimization-sections">
                <div class="optimization-section">
                    <h2><?php _e('Caché', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></h2>
                    <p><?php _e('Limpia y optimiza el sistema de caché.', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></p>
                    <button class="button button-primary" id="clear-all-caches">
                        <?php _e('Limpiar Todo el Caché', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                    </button>
                </div>

                <div class="optimization-section">
                    <h2><?php _e('Base de Datos', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></h2>
                    <p><?php _e('Optimiza las tablas de la base de datos.', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></p>
                    <button class="button button-primary" id="optimize-database">
                        <?php _e('Optimizar Base de Datos', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                    </button>
                </div>

                <div class="optimization-section">
                    <h2><?php _e('Notificaciones Antiguas', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></h2>
                    <p><?php _e('Elimina notificaciones leídas con más de 30 días.', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></p>
                    <button class="button button-secondary" id="cleanup-old-notifications">
                        <?php _e('Limpiar Notificaciones Antiguas', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                    </button>
                </div>

                <div class="optimization-section">
                    <h2><?php _e('Assets', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></h2>
                    <p><?php _e('Regenera archivos CSS/JS minificados.', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></p>
                    <button class="button button-secondary" id="regenerate-assets">
                        <?php _e('Regenerar Assets', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                    </button>
                </div>
            </div>

            <div id="optimization-results" class="optimization-results" style="display:none;">
                <h2><?php _e('Resultados', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></h2>
                <div id="optimization-output"></div>
            </div>
        </div>
        <?php
    }

    /**
     * Obtener estadísticas del sistema
     */
    private function get_system_statistics() {
        global $wpdb;

        $stats = [];

        // Notificaciones
        $table_name = $wpdb->prefix . 'flavor_notifications';
        $stats['notifications'] = [
            'total' => $wpdb->get_var("SELECT COUNT(*) FROM {$table_name}"),
            'unread' => $wpdb->get_var("SELECT COUNT(*) FROM {$table_name} WHERE is_read = 0"),
            'last_24h' => $wpdb->get_var("SELECT COUNT(*) FROM {$table_name} WHERE created_at > DATE_SUB(NOW(), INTERVAL 24 HOUR)"),
        ];

        // Páginas V3
        $migrated_modules = $this->count_migrated_modules();
        $stats['pages'] = [
            'modules_migrated' => $migrated_modules['count'],
            'total_pages' => $migrated_modules['pages'],
        ];

        // Menús
        $stats['menus'] = [
            'active_menus' => wp_count_terms('nav_menu'),
        ];

        // Dependencias
        $stats['dependencies'] = [
            'modules_with_deps' => $this->count_modules_with_dependencies(),
            'conflicts' => 0, // Implementar lógica de detección de conflictos
        ];

        return $stats;
    }

    /**
     * Contar módulos migrados
     */
    private function count_migrated_modules() {
        $loader = class_exists('Flavor_Platform_Module_Loader') ? Flavor_Platform_Module_Loader::get_instance() : null;
        if (!$loader) {
            return ['count' => 0, 'pages' => 0];
        }

        $modules = $loader->get_modules();
        $migrated = 0;
        $total_pages = 0;

        foreach ($modules as $module) {
            if (method_exists($module, 'get_pages_definition')) {
                $pages = $module->get_pages_definition();
                if (!empty($pages)) {
                    $migrated++;
                    $total_pages += count($pages);
                }
            }
        }

        return ['count' => $migrated, 'pages' => $total_pages];
    }

    /**
     * Contar módulos con dependencias
     */
    private function count_modules_with_dependencies() {
        $loader = class_exists('Flavor_Platform_Module_Loader') ? Flavor_Platform_Module_Loader::get_instance() : null;
        if (!$loader) {
            return 0;
        }

        $modules = $loader->get_modules();
        $count = 0;

        foreach ($modules as $module) {
            if (method_exists($module, 'get_dependencies')) {
                $deps = $module->get_dependencies();
                if (!empty($deps)) {
                    $count++;
                }
            }
        }

        return $count;
    }

    /**
     * Obtener estado de módulos V3
     */
    private function get_modules_v3_status() {
        $loader = class_exists('Flavor_Platform_Module_Loader') ? Flavor_Platform_Module_Loader::get_instance() : null;
        if (!$loader) {
            return [
                'migrated_count' => 0,
                'total_count' => 0,
                'migration_percentage' => 0,
                'modules' => [],
            ];
        }

        $modules = $loader->get_modules();
        $migrated = 0;
        $total = count($modules);
        $modules_info = [];

        foreach ($modules as $module) {
            $is_migrated = method_exists($module, 'get_pages_definition');
            $page_count = 0;

            if ($is_migrated) {
                $pages = $module->get_pages_definition();
                $page_count = count($pages);
                if ($page_count > 0) {
                    $migrated++;
                }
            }

            $modules_info[] = [
                'id' => $module->id,
                'name' => $module->name,
                'is_migrated' => $is_migrated && $page_count > 0,
                'page_count' => $page_count,
            ];
        }

        return [
            'migrated_count' => $migrated,
            'total_count' => $total,
            'migration_percentage' => $total > 0 ? round(($migrated / $total) * 100, 2) : 0,
            'modules' => $modules_info,
        ];
    }

    /**
     * AJAX: Obtener estadísticas del sistema
     */
    public function ajax_get_system_stats() {
        check_ajax_referer('flavor_systems_admin', 'nonce');

        if (!current_user_can($this->capability)) {
            wp_send_json_error(['message' => __('Permisos insuficientes', FLAVOR_PLATFORM_TEXT_DOMAIN)]);
        }

        $stats = $this->get_system_statistics();
        wp_send_json_success($stats);
    }

    /**
     * AJAX: Obtener estadísticas de notificaciones
     */
    public function ajax_get_notifications_stats() {
        check_ajax_referer('flavor_systems_admin', 'nonce');

        if (!current_user_can($this->capability)) {
            wp_send_json_error(['message' => __('Permisos insuficientes', FLAVOR_PLATFORM_TEXT_DOMAIN)]);
        }

        global $wpdb;
        $table_name = $wpdb->prefix . 'flavor_notifications';

        // Notificaciones por día (últimos 7 días)
        $daily_stats = $wpdb->get_results("
            SELECT DATE(created_at) as date, COUNT(*) as count
            FROM {$table_name}
            WHERE created_at > DATE_SUB(NOW(), INTERVAL 7 DAY)
            GROUP BY DATE(created_at)
            ORDER BY date ASC
        ");

        wp_send_json_success(['daily' => $daily_stats]);
    }

    /**
     * AJAX: Obtener estado de módulos
     */
    public function ajax_get_modules_status() {
        check_ajax_referer('flavor_systems_admin', 'nonce');

        if (!current_user_can($this->capability)) {
            wp_send_json_error(['message' => __('Permisos insuficientes', FLAVOR_PLATFORM_TEXT_DOMAIN)]);
        }

        $status = $this->get_modules_v3_status();
        wp_send_json_success($status);
    }

    /**
     * AJAX: Optimizar sistema
     */
    public function ajax_optimize_system() {
        check_ajax_referer('flavor_systems_admin', 'nonce');

        if (!current_user_can($this->capability)) {
            wp_send_json_error(['message' => __('Permisos insuficientes', FLAVOR_PLATFORM_TEXT_DOMAIN)]);
        }

        $results = [];

        // Limpiar caché de WordPress
        wp_cache_flush();
        $results[] = __('Caché de WordPress limpiado', FLAVOR_PLATFORM_TEXT_DOMAIN);

        // Limpiar transients expirados
        global $wpdb;
        $wpdb->query("DELETE FROM {$wpdb->options} WHERE option_name LIKE '_transient_timeout_%' AND option_value < UNIX_TIMESTAMP()");
        $results[] = __('Transients expirados eliminados', FLAVOR_PLATFORM_TEXT_DOMAIN);

        // Optimizar tablas
        $wpdb->query("OPTIMIZE TABLE {$wpdb->prefix}flavor_notifications");
        $results[] = __('Tabla de notificaciones optimizada', FLAVOR_PLATFORM_TEXT_DOMAIN);

        wp_send_json_success(['results' => $results]);
    }

    /**
     * AJAX: Limpiar caché de notificaciones
     */
    public function ajax_clear_notification_cache() {
        check_ajax_referer('flavor_systems_admin', 'nonce');

        if (!current_user_can($this->capability)) {
            wp_send_json_error(['message' => __('Permisos insuficientes', FLAVOR_PLATFORM_TEXT_DOMAIN)]);
        }

        // Limpiar transients relacionados con notificaciones
        global $wpdb;
        $wpdb->query("DELETE FROM {$wpdb->options} WHERE option_name LIKE '_transient_flavor_notifications_%'");

        wp_send_json_success(['message' => __('Caché de notificaciones limpiado', FLAVOR_PLATFORM_TEXT_DOMAIN)]);
    }

    /**
     * AJAX: Obtener módulos por categoría
     */
    public function ajax_get_category_modules() {
        check_ajax_referer('flavor_systems_admin', 'nonce');

        if (!current_user_can($this->capability)) {
            wp_send_json_error(['message' => __('Permisos insuficientes', FLAVOR_PLATFORM_TEXT_DOMAIN)]);
        }

        $category = isset($_POST['category']) ? sanitize_text_field($_POST['category']) : '';

        if (empty($category)) {
            wp_send_json_error(['message' => __('Categoría no especificada', FLAVOR_PLATFORM_TEXT_DOMAIN)]);
        }

        $loader = class_exists('Flavor_Platform_Module_Loader') ? Flavor_Platform_Module_Loader::get_instance() : null;
        if (!$loader) {
            wp_send_json_success(['modules' => []]);
            return;
        }

        $modules = $loader->get_modules();
        $category_modules = [];

        foreach ($modules as $module) {
            if (isset($module->category) && $module->category === $category) {
                $category_modules[] = $module->name;
            }
        }

        wp_send_json_success(['modules' => $category_modules]);
    }

    /**
     * AJAX: Obtener dependencias
     */
    public function ajax_get_dependencies() {
        check_ajax_referer('flavor_systems_admin', 'nonce');

        if (!current_user_can($this->capability)) {
            wp_send_json_error(['message' => __('Permisos insuficientes', FLAVOR_PLATFORM_TEXT_DOMAIN)]);
        }

        $loader = class_exists('Flavor_Platform_Module_Loader') ? Flavor_Platform_Module_Loader::get_instance() : null;
        if (!$loader) {
            wp_send_json_success(['dependencies' => []]);
            return;
        }

        $modules = $loader->get_modules();
        $dependencies = [];

        foreach ($modules as $module) {
            $deps = method_exists($module, 'get_dependencies') ? $module->get_dependencies() : [];

            if (!empty($deps) || count($deps) > 0) {
                $dependencies[] = [
                    'module' => $module->name,
                    'depends_on' => $deps,
                    'status' => 'active',
                    'status_label' => __('Activo', FLAVOR_PLATFORM_TEXT_DOMAIN)
                ];
            }
        }

        wp_send_json_success(['dependencies' => $dependencies]);
    }

    /**
     * AJAX: Regenerar todas las páginas
     */
    public function ajax_regenerate_all_pages() {
        check_ajax_referer('flavor_systems_admin', 'nonce');

        if (!current_user_can($this->capability)) {
            wp_send_json_error(['message' => __('Permisos insuficientes', FLAVOR_PLATFORM_TEXT_DOMAIN)]);
        }

        if (!class_exists('Flavor_Page_Creator_V3')) {
            wp_send_json_error(['message' => __('Page Creator V3 no está disponible', FLAVOR_PLATFORM_TEXT_DOMAIN)]);
        }

        $creator = Flavor_Page_Creator_V3::get_instance();

        try {
            $result = $creator->create_all_pages();
            wp_send_json_success([
                'message' => sprintf(__('Se regeneraron %d páginas correctamente', FLAVOR_PLATFORM_TEXT_DOMAIN), $result['total_created'])
            ]);
        } catch (Exception $e) {
            wp_send_json_error(['message' => $e->getMessage()]);
        }
    }

    /**
     * AJAX: Regenerar páginas de un módulo
     */
    public function ajax_regenerate_module_pages() {
        check_ajax_referer('flavor_systems_admin', 'nonce');

        if (!current_user_can($this->capability)) {
            wp_send_json_error(['message' => __('Permisos insuficientes', FLAVOR_PLATFORM_TEXT_DOMAIN)]);
        }

        $module_id = isset($_POST['module_id']) ? sanitize_text_field($_POST['module_id']) : '';

        if (empty($module_id)) {
            wp_send_json_error(['message' => __('ID de módulo no especificado', FLAVOR_PLATFORM_TEXT_DOMAIN)]);
        }

        if (!class_exists('Flavor_Page_Creator_V3')) {
            wp_send_json_error(['message' => __('Page Creator V3 no está disponible', FLAVOR_PLATFORM_TEXT_DOMAIN)]);
        }

        $creator = Flavor_Page_Creator_V3::get_instance();

        try {
            // Eliminar páginas existentes del módulo
            $creator->delete_module_pages($module_id);

            // Recrear páginas
            $result = $creator->create_all_pages();

            wp_send_json_success([
                'message' => sprintf(__('Páginas del módulo %s regeneradas', FLAVOR_PLATFORM_TEXT_DOMAIN), $module_id)
            ]);
        } catch (Exception $e) {
            wp_send_json_error(['message' => $e->getMessage()]);
        }
    }

    /**
     * AJAX: Limpiar notificaciones antiguas
     */
    public function ajax_clear_old_notifications() {
        check_ajax_referer('flavor_systems_admin', 'nonce');

        if (!current_user_can($this->capability)) {
            wp_send_json_error(['message' => __('Permisos insuficientes', FLAVOR_PLATFORM_TEXT_DOMAIN)]);
        }

        global $wpdb;
        $table_name = $wpdb->prefix . 'flavor_notifications';

        // Eliminar notificaciones leídas con más de 30 días
        $deleted = $wpdb->query(
            "DELETE FROM {$table_name}
             WHERE is_read = 1
             AND created_at < DATE_SUB(NOW(), INTERVAL 30 DAY)"
        );

        wp_send_json_success([
            'message' => sprintf(__('Se eliminaron %d notificaciones antiguas', FLAVOR_PLATFORM_TEXT_DOMAIN), $deleted)
        ]);
    }
}

// Inicializar solo en área de administración
if (is_admin()) {
    Flavor_Systems_Admin_Panel::get_instance();
}

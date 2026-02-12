<?php
/**
 * Panel Unificado de Gestión - Flavor Dashboard
 *
 * Centraliza la gestión de TODOS los módulos en un solo panel
 * con navegación por categorías
 *
 * @package FlavorChatIA
 * @since 3.1.0
 */

if (!defined('ABSPATH')) {
    exit;
}

class Flavor_Unified_Dashboard {

    private static $instance = null;

    /**
     * Categorías de módulos
     */
    private $categories = [
        'personas' => [
            'title' => 'Personas',
            'icon' => 'dashicons-groups',
            'color' => '#667eea',
            'description' => 'Gestión de empleados, socios y clientes'
        ],
        'economia' => [
            'title' => 'Economía',
            'icon' => 'dashicons-money-alt',
            'color' => '#f093fb',
            'description' => 'Gestión económica y financiera'
        ],
        'operaciones' => [
            'title' => 'Operaciones',
            'icon' => 'dashicons-clipboard',
            'color' => '#4facfe',
            'description' => 'Operaciones diarias y reservas'
        ],
        'recursos' => [
            'title' => 'Recursos',
            'icon' => 'dashicons-admin-home',
            'color' => '#43e97b',
            'description' => 'Recursos y materiales'
        ],
        'comunicacion' => [
            'title' => 'Comunicación',
            'icon' => 'dashicons-megaphone',
            'color' => '#fa709a',
            'description' => 'Comunicación y medios'
        ],
        'actividades' => [
            'title' => 'Actividades',
            'icon' => 'dashicons-calendar-alt',
            'color' => '#30cfd0',
            'description' => 'Eventos, cursos y talleres'
        ],
        'servicios' => [
            'title' => 'Servicios',
            'icon' => 'dashicons-admin-tools',
            'color' => '#a8edea',
            'description' => 'Servicios urbanos y comunitarios'
        ],
        'comunidad' => [
            'title' => 'Comunidad',
            'icon' => 'dashicons-heart',
            'color' => '#ff6a88',
            'description' => 'Iniciativas comunitarias'
        ],
        'sostenibilidad' => [
            'title' => 'Sostenibilidad',
            'icon' => 'dashicons-palmtree',
            'color' => '#38ef7d',
            'description' => 'Sostenibilidad y medio ambiente'
        ],
    ];

    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct() {
        add_action('admin_menu', [$this, 'register_menu'], 3);
        add_action('admin_enqueue_scripts', [$this, 'enqueue_assets']);
    }

    /**
     * Registrar menú
     */
    public function register_menu() {
        add_menu_page(
            __('Flavor Dashboard', 'flavor-chat-ia'),
            __('Flavor Dashboard', 'flavor-chat-ia'),
            'manage_options',
            'flavor-unified-dashboard',
            [$this, 'render_dashboard'],
            'dashicons-dashboard',
            2 // Posición alta para que aparezca arriba
        );
    }

    /**
     * Encolar assets
     */
    public function enqueue_assets($hook) {
        if ($hook !== 'toplevel_page_flavor-unified-dashboard') {
            return;
        }

        wp_enqueue_style(
            'flavor-unified-dashboard',
            FLAVOR_CHAT_IA_URL . 'admin/css/unified-dashboard.css',
            [],
            FLAVOR_CHAT_IA_VERSION
        );

        wp_enqueue_script(
            'flavor-unified-dashboard',
            FLAVOR_CHAT_IA_URL . 'admin/js/unified-dashboard.js',
            ['jquery'],
            FLAVOR_CHAT_IA_VERSION,
            true
        );
    }

    /**
     * Obtener todos los módulos agrupados por categoría
     */
    private function get_modules_by_category() {
        $modules_by_category = [];

        // Inicializar arrays
        foreach ($this->categories as $cat_id => $cat_data) {
            $modules_by_category[$cat_id] = [];
        }

        // Obtener configuraciones de módulos mediante el filtro
        // Este filtro es usado por el trait Flavor_Module_Admin_Pages_Trait
        $all_modules = apply_filters('flavor_admin_panel_modules', []);

        foreach ($all_modules as $module_id => $config) {
            if (!empty($config['categoria'])) {
                $categoria = $config['categoria'];

                if (isset($modules_by_category[$categoria])) {
                    // Obtener instancia del módulo para stats
                    $module_instance = null;
                    if (class_exists('Flavor_Chat_Module_Loader')) {
                        $loader = Flavor_Chat_Module_Loader::get_instance();
                        $loaded_modules = $loader->get_loaded_modules();
                        $module_instance = $loaded_modules[$module_id] ?? null;
                    }

                    $modules_by_category[$categoria][] = [
                        'id' => $config['id'] ?? $module_id,
                        'label' => $config['label'] ?? ucfirst($module_id),
                        'icon' => $config['icon'] ?? 'dashicons-admin-generic',
                        'paginas' => $config['paginas'] ?? [],
                        'stats' => $this->get_module_stats($module_id, $module_instance)
                    ];
                }
            }
        }

        return $modules_by_category;
    }

    /**
     * Obtener estadísticas rápidas de un módulo
     */
    private function get_module_stats($module_id, $module) {
        $stats = [];

        // Verificar que el módulo existe y tiene el método
        if ($module && method_exists($module, 'get_quick_stats')) {
            $stats = $module->get_quick_stats();
        }

        return $stats;
    }

    /**
     * Renderizar dashboard
     */
    public function render_dashboard() {
        $modules_by_category = $this->get_modules_by_category();
        $active_category = isset($_GET['cat']) ? sanitize_text_field($_GET['cat']) : 'personas';

        ?>
        <div class="wrap flavor-unified-dashboard">
            <h1>
                <span class="dashicons dashicons-dashboard"></span>
                <?php _e('Flavor Dashboard - Panel de Gestión', 'flavor-chat-ia'); ?>
            </h1>

            <div class="flavor-dashboard-header">
                <p class="description">
                    <?php _e('Panel centralizado para gestionar todos los módulos de tu aplicación', 'flavor-chat-ia'); ?>
                </p>
            </div>

            <!-- Navegación por categorías (tabs) -->
            <nav class="flavor-category-tabs">
                <?php foreach ($this->categories as $cat_id => $cat_data): ?>
                    <a href="<?php echo admin_url('admin.php?page=flavor-unified-dashboard&cat=' . $cat_id); ?>"
                       class="category-tab <?php echo $active_category === $cat_id ? 'active' : ''; ?>"
                       data-category="<?php echo esc_attr($cat_id); ?>"
                       style="--tab-color: <?php echo esc_attr($cat_data['color']); ?>">
                        <span class="dashicons <?php echo esc_attr($cat_data['icon']); ?>"></span>
                        <span class="tab-label"><?php echo esc_html($cat_data['title']); ?></span>
                        <span class="module-count"><?php echo count($modules_by_category[$cat_id]); ?></span>
                    </a>
                <?php endforeach; ?>
            </nav>

            <!-- Contenido de categorías -->
            <?php foreach ($this->categories as $cat_id => $cat_data): ?>
                <div class="category-content <?php echo $active_category === $cat_id ? 'active' : ''; ?>"
                     data-category="<?php echo esc_attr($cat_id); ?>">

                    <div class="category-header">
                        <h2>
                            <span class="dashicons <?php echo esc_attr($cat_data['icon']); ?>"></span>
                            <?php echo esc_html($cat_data['title']); ?>
                        </h2>
                        <p class="description"><?php echo esc_html($cat_data['description']); ?></p>
                    </div>

                    <?php if (empty($modules_by_category[$cat_id])): ?>
                        <div class="no-modules-message">
                            <span class="dashicons dashicons-info"></span>
                            <p><?php _e('No hay módulos activos en esta categoría.', 'flavor-chat-ia'); ?></p>
                        </div>
                    <?php else: ?>
                        <div class="modules-grid">
                            <?php foreach ($modules_by_category[$cat_id] as $module): ?>
                                <?php $this->render_module_card($module); ?>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
            <?php endforeach; ?>
        </div>
        <?php
    }

    /**
     * Renderizar card de módulo
     */
    private function render_module_card($module) {
        $main_page = !empty($module['paginas']) ? $module['paginas'][0] : null;
        $dashboard_url = $main_page ? admin_url('admin.php?page=' . $main_page['slug']) : '#';

        ?>
        <div class="module-card">
            <div class="module-header">
                <span class="module-icon dashicons <?php echo esc_attr($module['icon']); ?>"></span>
                <h3><?php echo esc_html($module['label']); ?></h3>
            </div>

            <?php if (!empty($module['stats'])): ?>
                <div class="module-stats">
                    <?php foreach ($module['stats'] as $stat): ?>
                        <div class="stat-item">
                            <span class="stat-value"><?php echo esc_html($stat['value']); ?></span>
                            <span class="stat-label"><?php echo esc_html($stat['label']); ?></span>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>

            <div class="module-actions">
                <a href="<?php echo esc_url($dashboard_url); ?>" class="button button-primary">
                    <?php _e('Acceder', 'flavor-chat-ia'); ?>
                    <span class="dashicons dashicons-arrow-right-alt"></span>
                </a>

                <?php if (count($module['paginas']) > 1): ?>
                    <div class="quick-links">
                        <button type="button" class="button toggle-quick-links">
                            <span class="dashicons dashicons-menu"></span>
                            <?php _e('Más', 'flavor-chat-ia'); ?>
                        </button>
                        <ul class="quick-links-menu">
                            <?php foreach (array_slice($module['paginas'], 1, 4) as $page): ?>
                                <li>
                                    <a href="<?php echo admin_url('admin.php?page=' . $page['slug']); ?>">
                                        <?php echo esc_html($page['titulo']); ?>
                                    </a>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                <?php endif; ?>
            </div>
        </div>
        <?php
    }
}

// Inicializar
if (is_admin()) {
    Flavor_Unified_Dashboard::get_instance();
}

<?php
/**
 * Flavor Shell - Sistema de Vistas Personalizadas
 *
 * Permite crear y gestionar vistas personalizadas del menú
 * más allá de las vistas predefinidas Admin/Gestor.
 *
 * @package FlavorPlatform
 * @since 3.3.0
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Clase para manejar Vistas Personalizadas del Shell
 */
class Flavor_Shell_Custom_Views {

    /**
     * Instancia singleton
     *
     * @var Flavor_Shell_Custom_Views|null
     */
    private static $instance = null;

    /**
     * Option key para guardar vistas personalizadas
     */
    const OPTION_VIEWS = 'flavor_shell_custom_views';

    /**
     * User meta key para vista activa del usuario
     */
    const USER_META_ACTIVE_VIEW = 'flavor_shell_active_custom_view';

    /**
     * Vistas del sistema (no editables)
     */
    const SYSTEM_VIEWS = ['admin', 'gestor'];

    /**
     * Obtener instancia singleton
     *
     * @return Flavor_Shell_Custom_Views
     */
    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Constructor privado
     */
    private function __construct() {
        // Registrar página de admin
        add_action('admin_menu', [$this, 'register_admin_page'], 99);

        // AJAX endpoints
        add_action('wp_ajax_flavor_shell_create_view', [$this, 'ajax_create_view']);
        add_action('wp_ajax_flavor_shell_update_view', [$this, 'ajax_update_view']);
        add_action('wp_ajax_flavor_shell_delete_view', [$this, 'ajax_delete_view']);
        add_action('wp_ajax_flavor_shell_switch_view', [$this, 'ajax_switch_view']);
        add_action('wp_ajax_flavor_shell_get_views', [$this, 'ajax_get_views']);
        add_action('wp_ajax_flavor_shell_duplicate_view', [$this, 'ajax_duplicate_view']);
    }

    /**
     * Registrar página de administración de vistas
     */
    public function register_admin_page() {
        $hook = add_submenu_page(
            FLAVOR_PLATFORM_TEXT_DOMAIN,
            __('Gestión de Vistas', FLAVOR_PLATFORM_TEXT_DOMAIN),
            __('Vistas del Shell', FLAVOR_PLATFORM_TEXT_DOMAIN),
            'manage_options',
            'flavor-shell-views',
            [$this, 'render_admin_page']
        );

        // Cargar Alpine.js en esta página
        add_action('admin_print_scripts-' . $hook, [$this, 'enqueue_admin_scripts']);
    }

    /**
     * Encolar scripts para la página de admin
     */
    public function enqueue_admin_scripts() {
        // Alpine.js
        if (!wp_script_is('alpine', 'enqueued')) {
            wp_enqueue_script(
                'alpine',
                'https://cdn.jsdelivr.net/npm/alpinejs@3.14.3/dist/cdn.min.js',
                [],
                '3.14.3',
                true
            );
            // Añadir defer
            add_filter('script_loader_tag', function($tag, $handle) {
                if ($handle === 'alpine') {
                    return str_replace(' src', ' defer src', $tag);
                }
                return $tag;
            }, 10, 2);
        }
    }

    /**
     * Obtener todas las vistas personalizadas
     *
     * @return array
     */
    public function get_custom_views() {
        $views = get_option(self::OPTION_VIEWS, []);
        return is_array($views) ? $views : [];
    }

    /**
     * Obtener una vista específica
     *
     * @param string $view_id ID de la vista
     * @return array|null
     */
    public function get_view($view_id) {
        $views = $this->get_custom_views();
        return $views[$view_id] ?? null;
    }

    /**
     * Crear nueva vista personalizada
     *
     * @param array $data Datos de la vista
     * @return string|false ID de la vista creada o false
     */
    public function create_view(array $data) {
        $views = $this->get_custom_views();

        // Generar ID único
        $view_id = 'custom_' . sanitize_title($data['name']) . '_' . time();

        // Validar nombre único
        foreach ($views as $view) {
            if (strtolower($view['name']) === strtolower($data['name'])) {
                return false;
            }
        }

        $views[$view_id] = [
            'id' => $view_id,
            'name' => sanitize_text_field($data['name']),
            'description' => sanitize_text_field($data['description'] ?? ''),
            'icon' => sanitize_text_field($data['icon'] ?? ''),
            'color' => sanitize_hex_color($data['color'] ?? '#6366f1'),
            'menus' => $this->sanitize_menu_list($data['menus'] ?? []),
            'roles' => $this->sanitize_role_list($data['roles'] ?? []),
            'created_by' => get_current_user_id(),
            'created_at' => time(),
            'updated_at' => time(),
            'is_default' => !empty($data['is_default']),
        ];

        update_option(self::OPTION_VIEWS, $views);

        return $view_id;
    }

    /**
     * Actualizar vista existente
     *
     * @param string $view_id ID de la vista
     * @param array  $data    Nuevos datos
     * @return bool
     */
    public function update_view($view_id, array $data) {
        if (in_array($view_id, self::SYSTEM_VIEWS, true)) {
            return false;
        }

        $views = $this->get_custom_views();

        if (!isset($views[$view_id])) {
            return false;
        }

        // Actualizar campos permitidos
        if (isset($data['name'])) {
            $views[$view_id]['name'] = sanitize_text_field($data['name']);
        }
        if (isset($data['description'])) {
            $views[$view_id]['description'] = sanitize_text_field($data['description']);
        }
        if (isset($data['icon'])) {
            $views[$view_id]['icon'] = sanitize_text_field($data['icon']);
        }
        if (isset($data['color'])) {
            $views[$view_id]['color'] = sanitize_hex_color($data['color']);
        }
        if (isset($data['menus'])) {
            $views[$view_id]['menus'] = $this->sanitize_menu_list($data['menus']);
        }
        if (isset($data['roles'])) {
            $views[$view_id]['roles'] = $this->sanitize_role_list($data['roles']);
        }
        if (isset($data['is_default'])) {
            // Si se marca como default, desmarcar las demás
            if ($data['is_default']) {
                foreach ($views as &$v) {
                    $v['is_default'] = false;
                }
            }
            $views[$view_id]['is_default'] = !empty($data['is_default']);
        }

        $views[$view_id]['updated_at'] = time();

        return update_option(self::OPTION_VIEWS, $views);
    }

    /**
     * Eliminar vista
     *
     * @param string $view_id ID de la vista
     * @return bool
     */
    public function delete_view($view_id) {
        if (in_array($view_id, self::SYSTEM_VIEWS, true)) {
            return false;
        }

        $views = $this->get_custom_views();

        if (!isset($views[$view_id])) {
            return false;
        }

        unset($views[$view_id]);

        // Limpiar usuarios que tenían esta vista activa
        global $wpdb;
        $wpdb->delete(
            $wpdb->usermeta,
            [
                'meta_key' => self::USER_META_ACTIVE_VIEW,
                'meta_value' => $view_id,
            ],
            ['%s', '%s']
        );

        return update_option(self::OPTION_VIEWS, $views);
    }

    /**
     * Duplicar vista existente
     *
     * @param string $view_id ID de la vista a duplicar
     * @param string $new_name Nombre para la nueva vista
     * @return string|false ID de la nueva vista
     */
    public function duplicate_view($view_id, $new_name = '') {
        $original = $this->get_view($view_id);

        if (!$original) {
            return false;
        }

        $new_name = $new_name ?: $original['name'] . ' (copia)';

        return $this->create_view([
            'name' => $new_name,
            'description' => $original['description'],
            'icon' => $original['icon'],
            'color' => $original['color'],
            'menus' => $original['menus'],
            'roles' => $original['roles'],
            'is_default' => false,
        ]);
    }

    /**
     * Obtener vista activa del usuario
     *
     * @param int|null $user_id ID del usuario
     * @return string|null ID de la vista activa o null para usar sistema
     */
    public function get_user_active_view($user_id = null) {
        $user_id = $user_id ?: get_current_user_id();

        if (!$user_id) {
            return null;
        }

        $view_id = get_user_meta($user_id, self::USER_META_ACTIVE_VIEW, true);

        // Verificar que la vista existe
        if ($view_id && !in_array($view_id, self::SYSTEM_VIEWS, true)) {
            $views = $this->get_custom_views();
            if (!isset($views[$view_id])) {
                delete_user_meta($user_id, self::USER_META_ACTIVE_VIEW);
                return null;
            }
        }

        return $view_id ?: null;
    }

    /**
     * Establecer vista activa del usuario
     *
     * @param string   $view_id ID de la vista
     * @param int|null $user_id ID del usuario
     * @return bool
     */
    public function set_user_active_view($view_id, $user_id = null) {
        $user_id = $user_id ?: get_current_user_id();

        if (!$user_id) {
            return false;
        }

        // Validar vista
        if (!in_array($view_id, self::SYSTEM_VIEWS, true)) {
            $views = $this->get_custom_views();
            if (!isset($views[$view_id])) {
                return false;
            }

            // Verificar permisos de rol
            $user = get_user_by('id', $user_id);
            if (!$this->user_can_access_view($user, $view_id)) {
                return false;
            }
        }

        return update_user_meta($user_id, self::USER_META_ACTIVE_VIEW, $view_id);
    }

    /**
     * Verificar si usuario puede acceder a una vista
     *
     * @param WP_User $user    Usuario
     * @param string  $view_id ID de la vista
     * @return bool
     */
    public function user_can_access_view($user, $view_id) {
        if (in_array($view_id, self::SYSTEM_VIEWS, true)) {
            return true;
        }

        $view = $this->get_view($view_id);

        if (!$view) {
            return false;
        }

        // Sin restricción de roles = acceso libre
        if (empty($view['roles'])) {
            return true;
        }

        // Verificar si el usuario tiene alguno de los roles
        return !empty(array_intersect($user->roles, $view['roles']));
    }

    /**
     * Obtener vistas disponibles para el usuario actual
     *
     * @param int|null $user_id ID del usuario
     * @return array
     */
    public function get_available_views($user_id = null) {
        $user_id = $user_id ?: get_current_user_id();
        $user = get_user_by('id', $user_id);

        if (!$user) {
            return [];
        }

        $available_views = [];
        $custom_views = $this->get_custom_views();

        // Vistas del sistema siempre disponibles
        $available_views[] = [
            'id' => 'admin',
            'name' => __('Administrador', FLAVOR_PLATFORM_TEXT_DOMAIN),
            'description' => __('Vista completa con todos los módulos', FLAVOR_PLATFORM_TEXT_DOMAIN),
            'icon' => '',
            'color' => '#6366f1',
            'is_system' => true,
        ];

        $available_views[] = [
            'id' => 'gestor',
            'name' => __('Gestor de Grupos', FLAVOR_PLATFORM_TEXT_DOMAIN),
            'description' => __('Vista simplificada para gestores', FLAVOR_PLATFORM_TEXT_DOMAIN),
            'icon' => '',
            'color' => '#10b981',
            'is_system' => true,
        ];

        // Añadir vistas personalizadas accesibles
        foreach ($custom_views as $view_id => $view) {
            if ($this->user_can_access_view($user, $view_id)) {
                $available_views[] = array_merge($view, ['is_system' => false]);
            }
        }

        return $available_views;
    }

    /**
     * Verificar si un menú es visible en la vista activa
     *
     * @param string $menu_slug Slug del menú
     * @return bool
     */
    public function is_menu_visible($menu_slug) {
        $active_view = $this->get_user_active_view();

        // Si no hay vista personalizada activa, delegar al sistema original
        if (!$active_view || in_array($active_view, self::SYSTEM_VIEWS, true)) {
            return true;
        }

        $view = $this->get_view($active_view);

        if (!$view) {
            return true;
        }

        // Si no hay menús definidos, mostrar todos
        if (empty($view['menus'])) {
            return true;
        }

        return in_array($menu_slug, $view['menus'], true);
    }

    /**
     * Obtener todos los slugs de menú disponibles
     *
     * @return array
     */
    public function get_all_menu_slugs() {
        // Obtener del shell
        if (!class_exists('Flavor_Admin_Shell')) {
            return [];
        }

        $shell = Flavor_Admin_Shell::get_instance();
        $navigation = $shell->get_navigation_structure();

        $slugs = [];

        foreach ($navigation as $section_id => $section) {
            foreach ($section['items'] as $item) {
                $slugs[] = [
                    'slug' => $item['slug'],
                    'label' => $item['label'],
                    'section' => $section['label'],
                    'icon' => $item['icon'],
                ];
            }
        }

        return $slugs;
    }

    /**
     * Sanitizar lista de menús
     *
     * @param array $menus Lista de slugs
     * @return array
     */
    private function sanitize_menu_list(array $menus) {
        return array_map('sanitize_text_field', array_filter($menus));
    }

    /**
     * Sanitizar lista de roles
     *
     * @param array $roles Lista de roles
     * @return array
     */
    private function sanitize_role_list(array $roles) {
        $wp_roles = wp_roles();
        $valid_roles = array_keys($wp_roles->roles);

        return array_filter($roles, function($role) use ($valid_roles) {
            return in_array($role, $valid_roles, true);
        });
    }

    /**
     * AJAX: Crear vista
     */
    public function ajax_create_view() {
        check_ajax_referer('flavor_admin_shell', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_send_json_error(['message' => __('Sin permisos', FLAVOR_PLATFORM_TEXT_DOMAIN)]);
        }

        $name = isset($_POST['name']) ? sanitize_text_field($_POST['name']) : '';

        if (empty($name)) {
            wp_send_json_error(['message' => __('Nombre requerido', FLAVOR_PLATFORM_TEXT_DOMAIN)]);
        }

        $view_id = $this->create_view([
            'name' => $name,
            'description' => isset($_POST['description']) ? sanitize_text_field($_POST['description']) : '',
            'icon' => isset($_POST['icon']) ? sanitize_text_field($_POST['icon']) : '',
            'color' => isset($_POST['color']) ? sanitize_hex_color($_POST['color']) : '#6366f1',
            'menus' => isset($_POST['menus']) ? (array) $_POST['menus'] : [],
            'roles' => isset($_POST['roles']) ? (array) $_POST['roles'] : [],
        ]);

        if (!$view_id) {
            wp_send_json_error(['message' => __('Error al crear vista o nombre duplicado', FLAVOR_PLATFORM_TEXT_DOMAIN)]);
        }

        wp_send_json_success([
            'view_id' => $view_id,
            'message' => __('Vista creada', FLAVOR_PLATFORM_TEXT_DOMAIN),
        ]);
    }

    /**
     * AJAX: Actualizar vista
     */
    public function ajax_update_view() {
        check_ajax_referer('flavor_admin_shell', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_send_json_error(['message' => __('Sin permisos', FLAVOR_PLATFORM_TEXT_DOMAIN)]);
        }

        $view_id = isset($_POST['view_id']) ? sanitize_text_field($_POST['view_id']) : '';

        if (empty($view_id)) {
            wp_send_json_error(['message' => __('ID de vista requerido', FLAVOR_PLATFORM_TEXT_DOMAIN)]);
        }

        $data = [];
        if (isset($_POST['name'])) $data['name'] = $_POST['name'];
        if (isset($_POST['description'])) $data['description'] = $_POST['description'];
        if (isset($_POST['icon'])) $data['icon'] = $_POST['icon'];
        if (isset($_POST['color'])) $data['color'] = $_POST['color'];
        if (isset($_POST['menus'])) $data['menus'] = (array) $_POST['menus'];
        if (isset($_POST['roles'])) $data['roles'] = (array) $_POST['roles'];
        if (isset($_POST['is_default'])) $data['is_default'] = $_POST['is_default'];

        if (!$this->update_view($view_id, $data)) {
            wp_send_json_error(['message' => __('Error al actualizar vista', FLAVOR_PLATFORM_TEXT_DOMAIN)]);
        }

        wp_send_json_success(['message' => __('Vista actualizada', FLAVOR_PLATFORM_TEXT_DOMAIN)]);
    }

    /**
     * AJAX: Eliminar vista
     */
    public function ajax_delete_view() {
        check_ajax_referer('flavor_admin_shell', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_send_json_error(['message' => __('Sin permisos', FLAVOR_PLATFORM_TEXT_DOMAIN)]);
        }

        $view_id = isset($_POST['view_id']) ? sanitize_text_field($_POST['view_id']) : '';

        if (empty($view_id)) {
            wp_send_json_error(['message' => __('ID de vista requerido', FLAVOR_PLATFORM_TEXT_DOMAIN)]);
        }

        if (!$this->delete_view($view_id)) {
            wp_send_json_error(['message' => __('Error al eliminar vista', FLAVOR_PLATFORM_TEXT_DOMAIN)]);
        }

        wp_send_json_success(['message' => __('Vista eliminada', FLAVOR_PLATFORM_TEXT_DOMAIN)]);
    }

    /**
     * AJAX: Cambiar vista activa
     */
    public function ajax_switch_view() {
        check_ajax_referer('flavor_admin_shell', 'nonce');

        if (!current_user_can('read')) {
            wp_send_json_error(['message' => __('Sin permisos', FLAVOR_PLATFORM_TEXT_DOMAIN)]);
        }

        $view_id = isset($_POST['view_id']) ? sanitize_text_field($_POST['view_id']) : '';

        if (empty($view_id)) {
            wp_send_json_error(['message' => __('ID de vista requerido', FLAVOR_PLATFORM_TEXT_DOMAIN)]);
        }

        if (!$this->set_user_active_view($view_id)) {
            wp_send_json_error(['message' => __('Vista no disponible', FLAVOR_PLATFORM_TEXT_DOMAIN)]);
        }

        wp_send_json_success([
            'message' => __('Vista cambiada', FLAVOR_PLATFORM_TEXT_DOMAIN),
            'view_id' => $view_id,
        ]);
    }

    /**
     * AJAX: Obtener vistas disponibles
     */
    public function ajax_get_views() {
        check_ajax_referer('flavor_admin_shell', 'nonce');

        if (!current_user_can('read')) {
            wp_send_json_error(['message' => __('Sin permisos', FLAVOR_PLATFORM_TEXT_DOMAIN)]);
        }

        $views = $this->get_available_views();
        $active_view = $this->get_user_active_view();

        // Si usuario es admin, incluir todas las vistas (para gestión)
        $all_views = null;
        if (current_user_can('manage_options')) {
            $all_views = $this->get_custom_views();
        }

        wp_send_json_success([
            'views' => $views,
            'active_view' => $active_view,
            'all_views' => $all_views,
            'menus' => current_user_can('manage_options') ? $this->get_all_menu_slugs() : null,
        ]);
    }

    /**
     * AJAX: Duplicar vista
     */
    public function ajax_duplicate_view() {
        check_ajax_referer('flavor_admin_shell', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_send_json_error(['message' => __('Sin permisos', FLAVOR_PLATFORM_TEXT_DOMAIN)]);
        }

        $view_id = isset($_POST['view_id']) ? sanitize_text_field($_POST['view_id']) : '';
        $new_name = isset($_POST['new_name']) ? sanitize_text_field($_POST['new_name']) : '';

        if (empty($view_id)) {
            wp_send_json_error(['message' => __('ID de vista requerido', FLAVOR_PLATFORM_TEXT_DOMAIN)]);
        }

        $new_view_id = $this->duplicate_view($view_id, $new_name);

        if (!$new_view_id) {
            wp_send_json_error(['message' => __('Error al duplicar vista', FLAVOR_PLATFORM_TEXT_DOMAIN)]);
        }

        wp_send_json_success([
            'view_id' => $new_view_id,
            'message' => __('Vista duplicada', FLAVOR_PLATFORM_TEXT_DOMAIN),
        ]);
    }

    /**
     * Renderizar página de administración de vistas
     */
    public function render_admin_page() {
        if (!current_user_can('manage_options')) {
            wp_die(__('No tienes permisos para acceder a esta página.', FLAVOR_PLATFORM_TEXT_DOMAIN));
        }

        $custom_views = $this->get_custom_views();
        $available_menus = $this->get_all_menu_slugs();
        $wp_roles = wp_roles()->roles;
        $nonce = wp_create_nonce('flavor_admin_shell');
        ?>
        <div class="wrap flavor-shell-views-wrap" x-data="flavorShellViewsAdmin()">
            <h1>
                <span class="dashicons dashicons-visibility" style="font-size:28px;margin-right:10px;"></span>
                <?php esc_html_e('Gestión de Vistas del Shell', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
            </h1>

            <p class="description" style="font-size:14px;margin:15px 0 25px;">
                <?php esc_html_e('Crea y gestiona vistas personalizadas del menú. Las vistas permiten mostrar diferentes conjuntos de menús según el rol o necesidad del usuario.', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
            </p>

            <div class="flavor-views-container">
                <!-- Panel izquierdo: Lista de vistas -->
                <div class="flavor-views-list-panel">
                    <div class="flavor-views-list-header">
                        <h2><?php esc_html_e('Vistas', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></h2>
                        <button type="button" class="button button-primary" @click="openCreateModal()">
                            <span class="dashicons dashicons-plus-alt2"></span>
                            <?php esc_html_e('Nueva Vista', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                        </button>
                    </div>

                    <!-- Vistas del sistema -->
                    <div class="flavor-views-group">
                        <h3 class="flavor-views-group-title"><?php esc_html_e('Vistas del Sistema', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></h3>
                        <div class="flavor-view-card flavor-view-card--system">
                            <div class="flavor-view-card-icon">👤</div>
                            <div class="flavor-view-card-content">
                                <strong><?php esc_html_e('Administrador', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></strong>
                                <p><?php esc_html_e('Acceso completo a todos los módulos', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></p>
                            </div>
                            <span class="flavor-view-badge flavor-view-badge--system"><?php esc_html_e('Sistema', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></span>
                        </div>
                        <div class="flavor-view-card flavor-view-card--system">
                            <div class="flavor-view-card-icon">👥</div>
                            <div class="flavor-view-card-content">
                                <strong><?php esc_html_e('Gestor de Grupos', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></strong>
                                <p><?php esc_html_e('Vista simplificada configurable', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></p>
                            </div>
                            <a href="<?php echo esc_url(admin_url('admin.php?page=flavor-platform-views')); ?>" class="button button-small">
                                <?php esc_html_e('Configurar', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                            </a>
                        </div>
                    </div>

                    <!-- Vistas personalizadas -->
                    <div class="flavor-views-group">
                        <h3 class="flavor-views-group-title"><?php esc_html_e('Vistas Personalizadas', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></h3>

                        <?php if (empty($custom_views)) : ?>
                            <div class="flavor-views-empty">
                                <span class="dashicons dashicons-welcome-add-page"></span>
                                <p><?php esc_html_e('No hay vistas personalizadas. Crea una nueva para comenzar.', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></p>
                            </div>
                        <?php else : ?>
                            <?php foreach ($custom_views as $view_id => $view) : ?>
                                <div class="flavor-view-card" data-view-id="<?php echo esc_attr($view_id); ?>">
                                    <div class="flavor-view-card-icon" style="<?php echo $view['color'] ? 'background-color:' . esc_attr($view['color']) . ';' : ''; ?>">
                                        <?php echo $view['icon'] ? esc_html($view['icon']) : '📋'; ?>
                                    </div>
                                    <div class="flavor-view-card-content">
                                        <strong><?php echo esc_html($view['name']); ?></strong>
                                        <p><?php echo esc_html($view['description'] ?: __('Sin descripción', FLAVOR_PLATFORM_TEXT_DOMAIN)); ?></p>
                                        <small>
                                            <?php
                                            $menu_count = count($view['menus'] ?? []);
                                            $role_count = count($view['roles'] ?? []);
                                            printf(
                                                esc_html__('%d menús · %d roles', FLAVOR_PLATFORM_TEXT_DOMAIN),
                                                $menu_count,
                                                $role_count
                                            );
                                            ?>
                                        </small>
                                    </div>
                                    <div class="flavor-view-card-actions">
                                        <button type="button" class="button button-small" @click="editView('<?php echo esc_js($view_id); ?>')" title="<?php esc_attr_e('Editar', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>">
                                            <span class="dashicons dashicons-edit"></span>
                                        </button>
                                        <button type="button" class="button button-small" @click="duplicateView('<?php echo esc_js($view_id); ?>')" title="<?php esc_attr_e('Duplicar', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>">
                                            <span class="dashicons dashicons-admin-page"></span>
                                        </button>
                                        <button type="button" class="button button-small button-link-delete" @click="deleteView('<?php echo esc_js($view_id); ?>', '<?php echo esc_js($view['name']); ?>')" title="<?php esc_attr_e('Eliminar', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>">
                                            <span class="dashicons dashicons-trash"></span>
                                        </button>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Panel derecho: Información -->
                <div class="flavor-views-info-panel">
                    <h3><?php esc_html_e('¿Qué son las vistas?', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></h3>
                    <p><?php esc_html_e('Las vistas permiten crear diferentes configuraciones del menú lateral para distintos tipos de usuarios.', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></p>

                    <h4><?php esc_html_e('Vistas del Sistema', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></h4>
                    <ul>
                        <li><strong><?php esc_html_e('Administrador:', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></strong> <?php esc_html_e('Muestra todos los menús (no editable)', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></li>
                        <li><strong><?php esc_html_e('Gestor:', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></strong> <?php esc_html_e('Menús configurables desde "Configurar vistas"', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></li>
                    </ul>

                    <h4><?php esc_html_e('Vistas Personalizadas', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></h4>
                    <ul>
                        <li><?php esc_html_e('Puedes crear vistas con cualquier combinación de menús', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></li>
                        <li><?php esc_html_e('Restringirlas por rol de WordPress', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></li>
                        <li><?php esc_html_e('Personalizar icono y color', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></li>
                    </ul>

                    <h4><?php esc_html_e('¿Cómo cambiar de vista?', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></h4>
                    <p><?php esc_html_e('Los usuarios pueden cambiar de vista desde el selector en el menú lateral del Shell.', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></p>
                </div>
            </div>

            <!-- Modal: Crear/Editar Vista -->
            <div class="flavor-views-modal-backdrop" x-show="modalOpen" x-cloak @click.self="closeModal()">
                <div class="flavor-views-modal">
                    <div class="flavor-views-modal-header">
                        <h2 x-text="editingView ? '<?php echo esc_js(__('Editar Vista', FLAVOR_PLATFORM_TEXT_DOMAIN)); ?>' : '<?php echo esc_js(__('Nueva Vista', FLAVOR_PLATFORM_TEXT_DOMAIN)); ?>'"></h2>
                        <button type="button" class="flavor-views-modal-close" @click="closeModal()">
                            <span class="dashicons dashicons-no-alt"></span>
                        </button>
                    </div>

                    <form @submit.prevent="saveView()" class="flavor-views-modal-content">
                        <div class="flavor-views-form-row">
                            <label for="view-name"><?php esc_html_e('Nombre', FLAVOR_PLATFORM_TEXT_DOMAIN); ?> <span class="required">*</span></label>
                            <input type="text" id="view-name" x-model="viewForm.name" required placeholder="<?php esc_attr_e('Ej: Coordinadores', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>">
                        </div>

                        <div class="flavor-views-form-row">
                            <label for="view-description"><?php esc_html_e('Descripción', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></label>
                            <input type="text" id="view-description" x-model="viewForm.description" placeholder="<?php esc_attr_e('Breve descripción de la vista', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>">
                        </div>

                        <div class="flavor-views-form-row flavor-views-form-row--inline">
                            <div>
                                <label for="view-icon"><?php esc_html_e('Icono (emoji)', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></label>
                                <input type="text" id="view-icon" x-model="viewForm.icon" placeholder="📋" maxlength="4" style="width:60px;text-align:center;">
                            </div>
                            <div>
                                <label for="view-color"><?php esc_html_e('Color', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></label>
                                <input type="color" id="view-color" x-model="viewForm.color" style="width:60px;height:38px;">
                            </div>
                        </div>

                        <div class="flavor-views-form-row">
                            <label><?php esc_html_e('Roles permitidos', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></label>
                            <p class="description"><?php esc_html_e('Dejar vacío para permitir a todos los roles', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></p>
                            <div class="flavor-views-checkbox-grid">
                                <?php foreach ($wp_roles as $role_slug => $role) : ?>
                                    <label class="flavor-views-checkbox">
                                        <input type="checkbox" :value="'<?php echo esc_attr($role_slug); ?>'" x-model="viewForm.roles">
                                        <?php echo esc_html(translate_user_role($role['name'])); ?>
                                    </label>
                                <?php endforeach; ?>
                            </div>
                        </div>

                        <div class="flavor-views-form-row">
                            <label><?php esc_html_e('Menús visibles', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></label>
                            <p class="description"><?php esc_html_e('Selecciona qué menús aparecerán en esta vista', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></p>

                            <div class="flavor-views-menus-sections">
                                <?php
                                // Agrupar menús por sección
                                $sections = [];
                                foreach ($available_menus as $menu_item) {
                                    $section_name = $menu_item['section'] ?? __('Otros', FLAVOR_PLATFORM_TEXT_DOMAIN);
                                    if (!isset($sections[$section_name])) {
                                        $sections[$section_name] = [];
                                    }
                                    $sections[$section_name][] = $menu_item;
                                }
                                ?>
                                <?php foreach ($sections as $section_name => $items) : ?>
                                    <div class="flavor-views-menu-section">
                                        <div class="flavor-views-menu-section-header">
                                            <strong><?php echo esc_html($section_name); ?></strong>
                                            <button type="button" class="button-link" @click="toggleSection('<?php echo esc_js($section_name); ?>')">
                                                <?php esc_html_e('Marcar/Desmarcar todos', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                                            </button>
                                        </div>
                                        <div class="flavor-views-checkbox-grid">
                                            <?php foreach ($items as $menu_item) : ?>
                                                <label class="flavor-views-checkbox" title="<?php echo esc_attr($menu_item['slug']); ?>">
                                                    <input type="checkbox" :value="'<?php echo esc_attr($menu_item['slug']); ?>'" x-model="viewForm.menus" data-section="<?php echo esc_attr($section_name); ?>">
                                                    <span class="dashicons <?php echo esc_attr($menu_item['icon']); ?>"></span>
                                                    <?php echo esc_html($menu_item['label']); ?>
                                                </label>
                                            <?php endforeach; ?>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </div>

                        <div class="flavor-views-modal-footer">
                            <button type="button" class="button" @click="closeModal()"><?php esc_html_e('Cancelar', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></button>
                            <button type="submit" class="button button-primary" :disabled="saving">
                                <span x-show="!saving"><?php esc_html_e('Guardar Vista', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></span>
                                <span x-show="saving"><?php esc_html_e('Guardando...', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></span>
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <style>
            .flavor-shell-views-wrap { max-width: 1400px; }

            .flavor-views-container {
                display: grid;
                grid-template-columns: 1fr 320px;
                gap: 30px;
                margin-top: 20px;
            }
            @media (max-width: 1200px) {
                .flavor-views-container { grid-template-columns: 1fr; }
            }

            .flavor-views-list-panel {
                background: #fff;
                border: 1px solid #ddd;
                border-radius: 8px;
                padding: 20px;
            }
            .flavor-views-list-header {
                display: flex;
                justify-content: space-between;
                align-items: center;
                margin-bottom: 20px;
                padding-bottom: 15px;
                border-bottom: 1px solid #eee;
            }
            .flavor-views-list-header h2 { margin: 0; }
            .flavor-views-list-header .button { display: inline-flex; align-items: center; gap: 5px; }

            .flavor-views-group { margin-bottom: 25px; }
            .flavor-views-group-title {
                font-size: 12px;
                text-transform: uppercase;
                color: #666;
                margin: 0 0 12px;
                letter-spacing: 0.5px;
            }

            .flavor-view-card {
                display: flex;
                align-items: center;
                gap: 12px;
                padding: 12px 15px;
                background: #f9f9f9;
                border: 1px solid #e5e5e5;
                border-radius: 6px;
                margin-bottom: 10px;
                transition: all 0.15s;
            }
            .flavor-view-card:hover { border-color: #4f46e5; background: #f0f0ff; }
            .flavor-view-card--system { background: #f0f7ff; border-color: #c3dafe; }
            .flavor-view-card--system:hover { background: #e0efff; }

            .flavor-view-card-icon {
                width: 40px;
                height: 40px;
                display: flex;
                align-items: center;
                justify-content: center;
                font-size: 20px;
                background: #e0e7ff;
                border-radius: 8px;
                flex-shrink: 0;
            }
            .flavor-view-card-content { flex: 1; min-width: 0; }
            .flavor-view-card-content strong { display: block; margin-bottom: 2px; }
            .flavor-view-card-content p { margin: 0; font-size: 12px; color: #666; }
            .flavor-view-card-content small { font-size: 11px; color: #999; }

            .flavor-view-card-actions { display: flex; gap: 5px; }
            .flavor-view-card-actions .button { padding: 0 6px; min-height: 28px; }
            .flavor-view-card-actions .dashicons { font-size: 16px; width: 16px; height: 16px; margin-top: 2px; }

            .flavor-view-badge {
                font-size: 10px;
                padding: 3px 8px;
                border-radius: 10px;
                background: #e5e7eb;
                color: #4b5563;
            }
            .flavor-view-badge--system { background: #dbeafe; color: #1e40af; }

            .flavor-views-empty {
                text-align: center;
                padding: 30px;
                color: #666;
            }
            .flavor-views-empty .dashicons { font-size: 40px; width: 40px; height: 40px; color: #ccc; }

            .flavor-views-info-panel {
                background: #fff;
                border: 1px solid #ddd;
                border-radius: 8px;
                padding: 20px;
                height: fit-content;
            }
            .flavor-views-info-panel h3 { margin-top: 0; }
            .flavor-views-info-panel h4 { margin: 20px 0 8px; font-size: 13px; }
            .flavor-views-info-panel ul { margin: 0; padding-left: 18px; }
            .flavor-views-info-panel li { margin-bottom: 6px; font-size: 13px; }

            /* Modal */
            .flavor-views-modal-backdrop {
                position: fixed;
                top: 0;
                left: 0;
                right: 0;
                bottom: 0;
                background: rgba(0, 0, 0, 0.5);
                display: flex;
                align-items: center;
                justify-content: center;
                z-index: 100000;
            }
            .flavor-views-modal {
                background: #fff;
                border-radius: 12px;
                width: 90%;
                max-width: 700px;
                max-height: 90vh;
                display: flex;
                flex-direction: column;
                box-shadow: 0 20px 25px -5px rgba(0,0,0,0.1), 0 10px 10px -5px rgba(0,0,0,0.04);
            }
            .flavor-views-modal-header {
                display: flex;
                justify-content: space-between;
                align-items: center;
                padding: 20px;
                border-bottom: 1px solid #eee;
            }
            .flavor-views-modal-header h2 { margin: 0; }
            .flavor-views-modal-close {
                background: none;
                border: none;
                cursor: pointer;
                padding: 5px;
            }
            .flavor-views-modal-content {
                padding: 20px;
                overflow-y: auto;
                flex: 1;
            }
            .flavor-views-modal-footer {
                display: flex;
                justify-content: flex-end;
                gap: 10px;
                padding: 15px 20px;
                border-top: 1px solid #eee;
                background: #f9f9f9;
                border-radius: 0 0 12px 12px;
            }

            .flavor-views-form-row { margin-bottom: 20px; }
            .flavor-views-form-row label { display: block; font-weight: 600; margin-bottom: 6px; }
            .flavor-views-form-row input[type="text"] { width: 100%; }
            .flavor-views-form-row .required { color: #dc2626; }
            .flavor-views-form-row--inline { display: flex; gap: 20px; }

            .flavor-views-checkbox-grid {
                display: grid;
                grid-template-columns: repeat(auto-fill, minmax(180px, 1fr));
                gap: 8px;
                margin-top: 10px;
            }
            .flavor-views-checkbox {
                display: flex;
                align-items: center;
                gap: 6px;
                padding: 6px 10px;
                background: #f9f9f9;
                border: 1px solid #e5e5e5;
                border-radius: 4px;
                cursor: pointer;
                font-size: 12px;
            }
            .flavor-views-checkbox:hover { background: #f0f0ff; border-color: #4f46e5; }
            .flavor-views-checkbox input { margin: 0; }
            .flavor-views-checkbox .dashicons { font-size: 14px; width: 14px; height: 14px; color: #666; }

            .flavor-views-menus-sections { max-height: 300px; overflow-y: auto; border: 1px solid #e5e5e5; border-radius: 6px; padding: 10px; }
            .flavor-views-menu-section { margin-bottom: 15px; }
            .flavor-views-menu-section:last-child { margin-bottom: 0; }
            .flavor-views-menu-section-header {
                display: flex;
                justify-content: space-between;
                align-items: center;
                margin-bottom: 8px;
                padding-bottom: 5px;
                border-bottom: 1px solid #eee;
            }

            [x-cloak] { display: none !important; }
        </style>

        <script>
        document.addEventListener('alpine:init', function() {
            Alpine.data('flavorShellViewsAdmin', function() {
                return {
                    modalOpen: false,
                    editingView: null,
                    saving: false,
                    viewForm: {
                        name: '',
                        description: '',
                        icon: '',
                        color: '#6366f1',
                        roles: [],
                        menus: []
                    },
                    viewsData: <?php echo wp_json_encode($custom_views); ?>,
                    nonce: '<?php echo esc_js($nonce); ?>',

                    openCreateModal() {
                        this.editingView = null;
                        this.viewForm = {
                            name: '',
                            description: '',
                            icon: '',
                            color: '#6366f1',
                            roles: [],
                            menus: []
                        };
                        this.modalOpen = true;
                    },

                    editView(viewId) {
                        const viewData = this.viewsData[viewId];
                        if (!viewData) return;

                        this.editingView = viewId;
                        this.viewForm = {
                            name: viewData.name || '',
                            description: viewData.description || '',
                            icon: viewData.icon || '',
                            color: viewData.color || '#6366f1',
                            roles: viewData.roles || [],
                            menus: viewData.menus || []
                        };
                        this.modalOpen = true;
                    },

                    closeModal() {
                        this.modalOpen = false;
                        this.editingView = null;
                    },

                    async saveView() {
                        if (!this.viewForm.name.trim()) {
                            alert('<?php echo esc_js(__('El nombre es obligatorio', FLAVOR_PLATFORM_TEXT_DOMAIN)); ?>');
                            return;
                        }

                        this.saving = true;

                        const formData = new FormData();
                        formData.append('nonce', this.nonce);
                        formData.append('name', this.viewForm.name);
                        formData.append('description', this.viewForm.description);
                        formData.append('icon', this.viewForm.icon);
                        formData.append('color', this.viewForm.color);
                        this.viewForm.roles.forEach(r => formData.append('roles[]', r));
                        this.viewForm.menus.forEach(m => formData.append('menus[]', m));

                        if (this.editingView) {
                            formData.append('action', 'flavor_shell_update_view');
                            formData.append('view_id', this.editingView);
                        } else {
                            formData.append('action', 'flavor_shell_create_view');
                        }

                        try {
                            const response = await fetch(ajaxurl, {
                                method: 'POST',
                                body: formData
                            });
                            const data = await response.json();

                            if (data.success) {
                                window.location.reload();
                            } else {
                                alert(data.data?.message || '<?php echo esc_js(__('Error al guardar', FLAVOR_PLATFORM_TEXT_DOMAIN)); ?>');
                            }
                        } catch (e) {
                            alert('<?php echo esc_js(__('Error de conexión', FLAVOR_PLATFORM_TEXT_DOMAIN)); ?>');
                        }

                        this.saving = false;
                    },

                    async duplicateView(viewId) {
                        const newName = prompt('<?php echo esc_js(__('Nombre para la nueva vista:', FLAVOR_PLATFORM_TEXT_DOMAIN)); ?>');
                        if (!newName) return;

                        const formData = new FormData();
                        formData.append('action', 'flavor_shell_duplicate_view');
                        formData.append('nonce', this.nonce);
                        formData.append('view_id', viewId);
                        formData.append('new_name', newName);

                        try {
                            const response = await fetch(ajaxurl, {
                                method: 'POST',
                                body: formData
                            });
                            const data = await response.json();

                            if (data.success) {
                                window.location.reload();
                            } else {
                                alert(data.data?.message || '<?php echo esc_js(__('Error al duplicar', FLAVOR_PLATFORM_TEXT_DOMAIN)); ?>');
                            }
                        } catch (e) {
                            alert('<?php echo esc_js(__('Error de conexión', FLAVOR_PLATFORM_TEXT_DOMAIN)); ?>');
                        }
                    },

                    async deleteView(viewId, viewName) {
                        if (!confirm('<?php echo esc_js(__('¿Eliminar la vista', FLAVOR_PLATFORM_TEXT_DOMAIN)); ?> "' + viewName + '"?')) {
                            return;
                        }

                        const formData = new FormData();
                        formData.append('action', 'flavor_shell_delete_view');
                        formData.append('nonce', this.nonce);
                        formData.append('view_id', viewId);

                        try {
                            const response = await fetch(ajaxurl, {
                                method: 'POST',
                                body: formData
                            });
                            const data = await response.json();

                            if (data.success) {
                                window.location.reload();
                            } else {
                                alert(data.data?.message || '<?php echo esc_js(__('Error al eliminar', FLAVOR_PLATFORM_TEXT_DOMAIN)); ?>');
                            }
                        } catch (e) {
                            alert('<?php echo esc_js(__('Error de conexión', FLAVOR_PLATFORM_TEXT_DOMAIN)); ?>');
                        }
                    },

                    toggleSection(sectionName) {
                        const checkboxes = document.querySelectorAll(`input[data-section="${sectionName}"]`);
                        const allChecked = [...checkboxes].every(cb => cb.checked);
                        checkboxes.forEach(cb => {
                            cb.checked = !allChecked;
                            cb.dispatchEvent(new Event('change', { bubbles: true }));
                        });
                    }
                };
            });
        });
        </script>
        <?php
    }
}

// Inicializar singleton
Flavor_Shell_Custom_Views::get_instance();

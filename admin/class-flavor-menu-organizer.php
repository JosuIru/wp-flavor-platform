<?php
/**
 * Organizador de Menús por Categoría
 *
 * Reorganiza el menú gigante "Gestión" en múltiples menús por categoría
 *
 * @package FlavorChatIA
 * @since 3.1.0
 */

if (!defined('ABSPATH')) {
    exit;
}

class Flavor_Menu_Organizer {

    /**
     * Instancia singleton
     */
    private static $instance = null;

    /**
     * Configuración de categorías
     */
    private $categories = [];

    /**
     * Categorías visibles por vista admin.
     *
     * @var array
     */
    private $categories_por_vista = [];

    /**
     * Items de Flavor Platform que NO deben moverse
     * (configuración de plataforma, no módulos de negocio)
     */
    private $platform_items = [
        'flavor-dashboard',
        'flavor-module-dashboards',
        'flavor-design-settings',
        'flavor-create-pages',
        'flavor-landings',
        'flavor-permissions',
        'flavor-chat-config',
        'flavor-chat-ia-escalations',
        'flavor-apps-config',
        'flavor-deep-links',
        'flavor-network',
        'flavor-addons',
        'flavor-marketplace',
        'flavor-newsletter',
        'flavor-export-import',
        'flavor-health-check',
        'flavor-activity-log',
        'flavor-api-docs',
        'flavor-documentation',
        'flavor-tours',
        'flavor-layouts', // herramienta de diseño
        'flavor-chat-ia', // dashboard principal
    ];

    /**
     * Obtener instancia
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
        // Configurar categorías
        $this->setup_categories();
        $this->setup_categories_por_vista();

        // Reorganizar menús con prioridad baja para ejecutar después de todos los módulos
        add_action('admin_menu', [$this, 'reorganize_menus'], 999);
        add_action('admin_head', [$this, 'output_scroll_css'], 20);
    }

    /**
     * Configurar categorías
     */
    private function setup_categories() {
        $this->categories = [
            'personas' => [
                'title' => __('Personas', 'flavor-chat-ia'),
                'icon' => 'dashicons-groups',
                'position' => 25,
                'patterns' => ['fichaje', 'socios', 'clientes'],
                'description' => __('Gestión de personas: empleados, socios y clientes', 'flavor-chat-ia')
            ],
            'economia' => [
                'title' => __('Economía', 'flavor-chat-ia'),
                'icon' => 'dashicons-money-alt',
                'position' => 26,
                'patterns' => ['woocommerce', 'marketplace', '^pp-', 'empresarial', 'trading', 'transparencia', 'facturas'],
                'description' => __('Gestión económica y financiera', 'flavor-chat-ia')
            ],
            'operaciones' => [
                'title' => __('Operaciones', 'flavor-chat-ia'),
                'icon' => 'dashicons-clipboard',
                'position' => 27,
                'patterns' => ['reservas'],
                'description' => __('Operaciones diarias y reservas', 'flavor-chat-ia')
            ],
            'recursos' => [
                'title' => __('Recursos', 'flavor-chat-ia'),
                'icon' => 'dashicons-admin-home',
                'position' => 28,
                'patterns' => ['multimedia', 'themacle', 'biblioteca'],
                'description' => __('Recursos y materiales', 'flavor-chat-ia')
            ],
            'comunicacion' => [
                'title' => __('Comunicación', 'flavor-chat-ia'),
                'icon' => 'dashicons-megaphone',
                'position' => 29,
                'patterns' => ['avisos', 'chat-grupos', 'chat-interno', 'podcast', 'radio'],
                'description' => __('Comunicación y medios', 'flavor-chat-ia')
            ],
            'actividades' => [
                'title' => __('Actividades', 'flavor-chat-ia'),
                'icon' => 'dashicons-calendar-alt',
                'position' => 30,
                'patterns' => ['eventos', 'cursos', 'talleres'],
                'description' => __('Eventos, cursos y talleres', 'flavor-chat-ia')
            ],
            'servicios' => [
                'title' => __('Servicios', 'flavor-chat-ia'),
                'icon' => 'dashicons-admin-tools',
                'position' => 31,
                'patterns' => ['incidencias', 'parkings', 'tramites', 'bares', 'espacios-comunes'],
                'description' => __('Servicios urbanos y comunitarios', 'flavor-chat-ia')
            ],
            'comunidad' => [
                'title' => __('Comunidad', 'flavor-chat-ia'),
                'icon' => 'dashicons-heart',
                'position' => 32,
                'patterns' => ['banco-tiempo', '^gc-', 'grupos-consumo', 'participacion', 'ayuda-vecinal', 'colectivos', 'comunidades'],
                'description' => __('Iniciativas comunitarias', 'flavor-chat-ia')
            ],
            'sostenibilidad' => [
                'title' => __('Sostenibilidad', 'flavor-chat-ia'),
                'icon' => 'dashicons-palmtree',
                'position' => 33,
                'patterns' => ['bicicletas', 'carpooling', 'compostaje', 'huertos', 'reciclaje'],
                'description' => __('Sostenibilidad y medio ambiente', 'flavor-chat-ia')
            ],
        ];
    }

    /**
     * Configurar categorías visibles por vista.
     */
    private function setup_categories_por_vista() {
        $this->categories_por_vista = [
            'admin' => 'all',
            'gestor_grupos' => [
                'comunidad',
                'comunicacion',
                'actividades',
                'servicios',
                'recursos',
                'sostenibilidad',
            ],
        ];

        $this->categories_por_vista = apply_filters(
            'flavor_category_menus_por_vista',
            $this->categories_por_vista
        );
    }

    /**
     * Obtener vista activa del admin.
     *
     * @return string
     */
    private function get_active_view() {
        if (class_exists('Flavor_Admin_Menu_Manager')) {
            return Flavor_Admin_Menu_Manager::get_instance()->obtener_vista_activa();
        }

        return 'admin';
    }

    /**
     * Verificar si una categoría debe mostrarse en la vista actual.
     *
     * @param string $category_slug
     * @return bool
     */
    private function category_visible_in_view($category_slug) {
        $view = $this->get_active_view();
        $visible_categories = $this->categories_por_vista[$view] ?? 'all';

        if ($visible_categories === 'all') {
            return true;
        }

        return in_array($category_slug, $visible_categories, true);
    }

    /**
     * Obtener capacidad de menú para la vista actual.
     *
     * @return string
     */
    private function get_menu_capability() {
        return $this->get_active_view() === 'gestor_grupos' ? 'read' : 'manage_options';
    }

    /**
     * Reorganizar menús
     */
    public function reorganize_menus() {
        global $menu, $submenu;

        // 1. Guardar submenús del menú "Flavor Platform"
        // Los módulos se registran en 'flavor-chat-ia', no en 'flavor-gestion'
        $platform_submenus = isset($submenu['flavor-chat-ia']) ? $submenu['flavor-chat-ia'] : [];

        // Si no hay submenús, no hacer nada
        if (empty($platform_submenus)) {
            return;
        }

        // 2. Crear menús por categoría
        $this->create_category_menus();

        // 3. Reasignar submenús a categorías (solo los que son módulos de negocio)
        $this->reassign_submenus($platform_submenus);

        // NOTA: NO removemos flavor-chat-ia porque contiene configuración de plataforma
        // El cleaner se encargará de limpiar solo los módulos de negocio
    }

    /**
     * Crear menús por categoría
     */
    private function create_category_menus() {
        $capability = $this->get_menu_capability();

        foreach ($this->categories as $slug => $config) {
            if (!$this->category_visible_in_view($slug)) {
                continue;
            }

            add_menu_page(
                $config['title'],
                $config['title'],
                $capability,
                'flavor-cat-' . $slug,
                function() use ($slug, $config) {
                    $this->render_category_dashboard($slug, $config);
                },
                $config['icon'],
                $config['position']
            );

            // Agregar dashboard como primer submenú
            add_submenu_page(
                'flavor-cat-' . $slug,
                $config['title'] . ' - Dashboard',
                __('Dashboard', 'flavor-chat-ia'),
                $capability,
                'flavor-cat-' . $slug,
                function() use ($slug, $config) {
                    $this->render_category_dashboard($slug, $config);
                }
            );
        }
    }

    /**
     * Reasignar submenús a categorías
     */
    private function reassign_submenus($platform_submenus) {
        global $submenu, $_registered_pages, $_parent_pages;

        $assigned = [];
        $unassigned = [];
        $skipped = [];

        foreach ($platform_submenus as $item) {
            // $item[0] = título, $item[1] = capacidad, $item[2] = slug
            $title = strip_tags($item[0]); // Limpiar HTML/dashicons del título
            $capability = $item[1];
            $slug = $item[2];

            // Saltar separadores
            if (strpos($slug, 'separator') !== false || strpos($slug, '-sep-') !== false) {
                continue;
            }

            // Saltar items de configuración de plataforma
            if (in_array($slug, $this->platform_items)) {
                $skipped[] = $slug;
                continue;
            }

            // Detectar categoría para módulos de negocio
            $category = $this->detect_category($slug);

            if ($category) {
                if (!$this->category_visible_in_view($category)) {
                    continue;
                }

                $category_slug = 'flavor-cat-' . $category;

                // Obtener el hookname del slug original
                $hookname = get_plugin_page_hookname($slug, 'flavor-chat-ia');

                // Copiar el item completo al nuevo menú
                if (!isset($submenu[$category_slug])) {
                    $submenu[$category_slug] = [];
                }

                // Agregar el item completo
                $submenu[$category_slug][] = $item;

                // Registrar el parent correcto para que WordPress encuentre la página
                if ($hookname && isset($_registered_pages[$hookname])) {
                    // Crear también un hookname para el nuevo parent
                    $new_hookname = get_plugin_page_hookname($slug, $category_slug);
                    if ($new_hookname) {
                        $_registered_pages[$new_hookname] = $_registered_pages[$hookname];
                        $_parent_pages[$slug] = $category_slug;
                    }
                }

                $assigned[] = ['slug' => $slug, 'category' => $category];
            } else {
                $unassigned[] = $slug;
            }
        }

        // Log para debugging
        if (!empty($assigned)) {
            flavor_log_debug( 'Items asignados: ' . count($assigned), 'MenuOrganizer' );
        }
        if (!empty($unassigned)) {
            flavor_log_debug( 'Items sin asignar: ' . implode(', ', $unassigned), 'MenuOrganizer' );
        }
        if (!empty($skipped)) {
            flavor_log_debug( 'Items de plataforma (conservados): ' . count($skipped), 'MenuOrganizer' );
        }
    }

    /**
     * Detectar categoría de un slug
     */
    private function detect_category($slug) {
        foreach ($this->categories as $category_slug => $config) {
            foreach ($config['patterns'] as $pattern) {
                if (preg_match('/' . $pattern . '/i', $slug)) {
                    return $category_slug;
                }
            }
        }

        return null;
    }

    /**
     * Renderizar dashboard de categoría
     */
    private function render_category_dashboard($category_slug, $config) {
        global $submenu;
        $menu_slug = 'flavor-cat-' . $category_slug;
        $category_submenus = isset($submenu[$menu_slug]) ? $submenu[$menu_slug] : [];

        ?>
        <div class="wrap flavor-category-dashboard">
            <h1>
                <span class="dashicons <?php echo esc_attr($config['icon']); ?>"></span>
                <?php echo esc_html($config['title']); ?>
            </h1>

            <p class="description" style="font-size: 16px; margin-bottom: 30px;">
                <?php echo esc_html($config['description']); ?>
            </p>

            <div class="flavor-category-grid" style="display: grid; grid-template-columns: repeat(auto-fill, minmax(300px, 1fr)); gap: 20px; margin-top: 30px;">
                <?php
                // Mostrar módulos de esta categoría
                $module_count = 0;
                foreach ($category_submenus as $item) {
                    $title = strip_tags($item[0]);
                    $slug = $item[2];

                    // Saltar el dashboard mismo
                    if ($slug === $menu_slug) {
                        continue;
                    }

                    $module_count++;
                    $url = admin_url('admin.php?page=' . $slug);

                    // Extraer dashicon si existe
                    $icon_class = 'dashicons-admin-generic';
                    if (preg_match('/dashicons-([a-z\-]+)/', $item[0], $matches)) {
                        $icon_class = 'dashicons-' . $matches[1];
                    }

                    ?>
                    <a href="<?php echo esc_url($url); ?>" class="flavor-module-card" style="display: block; padding: 25px; background: white; border: 1px solid #ddd; border-radius: 8px; text-decoration: none; transition: all 0.2s; box-shadow: 0 1px 3px rgba(0,0,0,0.1);">
                        <div style="display: flex; align-items: center; margin-bottom: 15px;">
                            <span class="dashicons <?php echo esc_attr($icon_class); ?>" style="font-size: 32px; width: 32px; height: 32px; color: #2271b1;"></span>
                            <h3 style="margin: 0 0 0 15px; font-size: 18px; color: #2c3338;">
                                <?php echo esc_html($title); ?>
                            </h3>
                        </div>
                        <p style="margin: 0; color: #646970; font-size: 14px;">
                            <?php _e('Acceder al módulo', 'flavor-chat-ia'); ?> →
                        </p>
                    </a>
                    <?php
                }

                if ($module_count === 0) {
                    echo '<p>' . __('No hay módulos activos en esta categoría.', 'flavor-chat-ia') . '</p>';
                }
                ?>
            </div>

            <style>
                .flavor-module-card:hover {
                    border-color: #2271b1;
                    box-shadow: 0 4px 12px rgba(0,0,0,0.15);
                    transform: translateY(-2px);
                }
                .flavor-module-card h3 {
                    transition: color 0.2s;
                }
                .flavor-module-card:hover h3 {
                    color: #2271b1;
                }
            </style>
        </div>
        <?php
    }

    /**
     * Obtener contador de items por categoría
     */
    public function get_category_counts() {
        global $submenu;

        $counts = [];
        foreach ($this->categories as $slug => $config) {
            $menu_slug = 'flavor-cat-' . $slug;
            $items = isset($submenu[$menu_slug]) ? $submenu[$menu_slug] : [];
            $counts[$slug] = count($items) - 1; // -1 para excluir el dashboard
        }

        return $counts;
    }

    /**
     * Limita la altura de submenús largos del ecosistema Flavor en el admin clásico.
     */
    public function output_scroll_css() {
        ?>
        <style id="flavor-category-menu-scroll">
            #adminmenu li.toplevel_page_flavor-chat-ia .wp-submenu,
            #adminmenu li[id^="toplevel_page_flavor-cat-"] .wp-submenu {
                max-height: calc(100vh - 96px);
                overflow-y: auto;
                overscroll-behavior: contain;
            }
        </style>
        <?php
    }
}

// Inicializar solo en admin
if (is_admin()) {
    Flavor_Menu_Organizer::get_instance();
}

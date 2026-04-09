<?php
/**
 * Helper visual ligero para cabeceras y navegación compacta del admin.
 *
 * @package FlavorPlatform
 * @subpackage Admin
 * @since 4.3.0
 */

if (!defined('ABSPATH')) {
    exit;
}

$registry_file = dirname(__FILE__) . '/class-admin-navigation-registry.php';
if (!class_exists('Flavor_Admin_Navigation_Registry') && file_exists($registry_file)) {
    require_once $registry_file;
}

class Flavor_Admin_Page_Chrome {

    /**
     * Devuelve los enlaces compactos para una sección admin.
     *
     * @param string $section
     * @param string $active_slug
     * @return array<int,array<string,mixed>>
     */
    public static function get_section_links($section, $active_slug = '') {
        $registry_links = self::get_registry_section_links($section, $active_slug);
        if (!empty($registry_links)) {
            return $registry_links;
        }

        $definitions = [
            'home' => [
                ['slug' => 'flavor-unified-dashboard', 'label' => __('Widgets', FLAVOR_PLATFORM_TEXT_DOMAIN), 'icon' => 'dashicons-grid-view'],
                ['slug' => 'flavor-app-composer', 'label' => __('Ecosistema', FLAVOR_PLATFORM_TEXT_DOMAIN), 'icon' => 'dashicons-screenoptions'],
                ['slug' => 'flavor-design-settings', 'label' => __('Configuración', FLAVOR_PLATFORM_TEXT_DOMAIN), 'icon' => 'dashicons-admin-generic'],
                ['slug' => 'flavor-platform-health-check', 'label' => __('Sistema', FLAVOR_PLATFORM_TEXT_DOMAIN), 'icon' => 'dashicons-admin-tools'],
            ],
            'ecosystem' => [
                ['slug' => 'flavor-module-dashboards', 'label' => __('Catálogo', FLAVOR_PLATFORM_TEXT_DOMAIN), 'icon' => 'dashicons-screenoptions'],
                ['slug' => 'flavor-addons', 'label' => __('Addons', FLAVOR_PLATFORM_TEXT_DOMAIN), 'icon' => 'dashicons-admin-plugins'],
                ['slug' => 'flavor-marketplace', 'label' => __('Marketplace', FLAVOR_PLATFORM_TEXT_DOMAIN), 'icon' => 'dashicons-store'],
            ],
            'configuration' => [
                ['slug' => 'flavor-design-settings', 'label' => __('Diseño', FLAVOR_PLATFORM_TEXT_DOMAIN), 'icon' => 'dashicons-art'],
                ['slug' => 'flavor-layouts', 'label' => __('Layouts', FLAVOR_PLATFORM_TEXT_DOMAIN), 'icon' => 'dashicons-layout'],
                ['slug' => 'flavor-create-pages', 'label' => __('Páginas', FLAVOR_PLATFORM_TEXT_DOMAIN), 'icon' => 'dashicons-admin-page'],
                ['slug' => 'flavor-permissions', 'label' => __('Permisos', FLAVOR_PLATFORM_TEXT_DOMAIN), 'icon' => 'dashicons-lock'],
                ['slug' => 'flavor-platform-settings', 'label' => __('Ajustes IA', FLAVOR_PLATFORM_TEXT_DOMAIN), 'icon' => 'dashicons-admin-generic'],
                ['slug' => 'flavor-platform-apps', 'label' => __('Apps Móviles', FLAVOR_PLATFORM_TEXT_DOMAIN), 'icon' => 'dashicons-smartphone'],
                ['slug' => 'flavor-platform-deep-links', 'label' => __('Deep Links', FLAVOR_PLATFORM_TEXT_DOMAIN), 'icon' => 'dashicons-admin-links'],
                ['slug' => 'flavor-platform-network', 'label' => __('Red de Nodos', FLAVOR_PLATFORM_TEXT_DOMAIN), 'icon' => 'dashicons-networking'],
            ],
            'system' => [
                ['slug' => 'flavor-platform-health-check', 'label' => __('Diagnóstico', FLAVOR_PLATFORM_TEXT_DOMAIN), 'icon' => 'dashicons-heart'],
                ['slug' => 'flavor-platform-export-import', 'label' => __('Export / Import', FLAVOR_PLATFORM_TEXT_DOMAIN), 'icon' => 'dashicons-migrate'],
                ['slug' => 'flavor-platform-activity-log', 'label' => __('Actividad', FLAVOR_PLATFORM_TEXT_DOMAIN), 'icon' => 'dashicons-backup'],
                ['slug' => 'flavor-platform-docs', 'label' => __('Documentación', FLAVOR_PLATFORM_TEXT_DOMAIN), 'icon' => 'dashicons-book'],
                ['slug' => 'flavor-tours', 'label' => __('Tours', FLAVOR_PLATFORM_TEXT_DOMAIN), 'icon' => 'dashicons-location'],
            ],
        ];

        $links = [];
        foreach ($definitions[$section] ?? [] as $definition) {
            $slug = isset($definition['slug']) ? (string) $definition['slug'] : '';
            if ($slug === '') {
                continue;
            }

            $links[] = [
                'label' => $definition['label'],
                'url' => admin_url('admin.php?page=' . rawurlencode($slug)),
                'icon' => $definition['icon'],
                'active' => $slug === $active_slug,
            ];
        }

        return $links;
    }

    /**
     * Genera enlaces compactos a partir del registro central.
     *
     * @param string $section
     * @param string $active_slug
     * @return array<int,array<string,mixed>>
     */
    private static function get_registry_section_links($section, $active_slug = '') {
        if (!class_exists('Flavor_Admin_Navigation_Registry')) {
            return [];
        }

        $registry = Flavor_Admin_Navigation_Registry::get_instance();
        $pages = $registry->get_compact_nav_pages($section);
        $active_canonical_slug = $registry->resolve_canonical_slug($active_slug);

        if (empty($pages)) {
            return [];
        }

        $links = [];
        foreach ($pages as $slug => $page) {
            $links[] = [
                'label' => $page['label'] ?? $slug,
                'url' => $registry->get_admin_url($slug),
                'icon' => $page['icon'] ?? 'dashicons-admin-page',
                'active' => $slug === $active_canonical_slug,
            ];
        }

        return $links;
    }

    /**
     * Renderiza breadcrumbs simples para páginas admin canónicas.
     *
     * @param string $section
     * @param string $active_slug
     * @param string $current_title
     * @return void
     */
    public static function render_breadcrumbs($section, $active_slug = '', $current_title = '') {
        $breadcrumbs = self::get_breadcrumb_items($section, $active_slug, $current_title);
        if (empty($breadcrumbs)) {
            return;
        }

        self::enqueue_styles();
        ?>
        <nav class="flavor-admin-breadcrumbs" aria-label="<?php echo esc_attr__('Breadcrumbs', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>">
            <?php foreach ($breadcrumbs as $index => $item): ?>
                <?php
                $label = isset($item['label']) ? (string) $item['label'] : '';
                $url = isset($item['url']) ? (string) $item['url'] : '';
                $current = !empty($item['current']);
                if ($label === '') {
                    continue;
                }
                ?>
                <?php if ($index > 0): ?>
                    <span class="flavor-admin-breadcrumbs__sep" aria-hidden="true">/</span>
                <?php endif; ?>
                <?php if ($current || $url === ''): ?>
                    <span class="flavor-admin-breadcrumbs__current"><?php echo esc_html($label); ?></span>
                <?php else: ?>
                    <a href="<?php echo esc_url($url); ?>" class="flavor-admin-breadcrumbs__link"><?php echo esc_html($label); ?></a>
                <?php endif; ?>
            <?php endforeach; ?>
        </nav>
        <?php
    }

    /**
     * Renderiza una barra compacta de navegación entre páginas admin.
     *
     * @param array<int,array<string,mixed>> $links
     * @return void
     */
    public static function render_compact_nav(array $links) {
        if (empty($links)) {
            return;
        }
        self::enqueue_styles();
        ?>
        <div class="flavor-admin-compact-nav">
            <?php foreach ($links as $link): ?>
                <?php
                $label = isset($link['label']) ? (string) $link['label'] : '';
                $url = isset($link['url']) ? (string) $link['url'] : '';
                $icon = isset($link['icon']) ? (string) $link['icon'] : 'dashicons-admin-page';
                $active = !empty($link['active']);

                if ($label === '' || $url === '') {
                    continue;
                }
                ?>
                <a href="<?php echo esc_url($url); ?>"
                   class="button <?php echo $active ? 'button-primary' : ''; ?> flavor-admin-compact-nav__link">
                    <span class="dashicons <?php echo esc_attr($icon); ?>"></span>
                    <span><?php echo esc_html($label); ?></span>
                </a>
            <?php endforeach; ?>
        </div>
        <?php
    }

    /**
     * Encola e imprime los estilos base una sola vez.
     *
     * @return void
     */
    public static function print_styles() {
        self::enqueue_styles();
    }

    /**
     * Encola estilos del helper.
     *
     * @return void
     */
    private static function enqueue_styles() {
        static $printed = false;

        if ($printed) {
            return;
        }

        $printed = true;
        $handle = 'flavor-admin-page-chrome';
        $path = FLAVOR_CHAT_IA_PATH . 'admin/css/admin-page-chrome.css';
        $url = FLAVOR_CHAT_IA_URL . 'admin/css/admin-page-chrome.css';
        $version = file_exists($path) ? (string) filemtime($path) : (defined('FLAVOR_CHAT_IA_VERSION') ? FLAVOR_CHAT_IA_VERSION : null);

        wp_enqueue_style($handle, $url, [], $version);
        wp_print_styles([$handle]);
    }

    /**
     * Construye breadcrumbs simples desde el registro central.
     *
     * @param string $section
     * @param string $active_slug
     * @param string $current_title
     * @return array<int,array<string,mixed>>
     */
    private static function get_breadcrumb_items($section, $active_slug = '', $current_title = '') {
        if (!class_exists('Flavor_Admin_Navigation_Registry')) {
            return [];
        }

        $registry = Flavor_Admin_Navigation_Registry::get_instance();
        $sections = $registry->get_sections();
        $active_canonical_slug = $registry->resolve_canonical_slug($active_slug);
        $active_page = $registry->get_page($active_canonical_slug);
        $home_url = $registry->get_admin_url('flavor-dashboard');

        $items = [];

        if ($home_url !== '') {
            $items[] = [
                'label' => __('Flavor', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'url' => $home_url,
                'current' => false,
            ];
        }

        if (isset($sections[$section]['label'])) {
            $section_label = (string) $sections[$section]['label'];
            $section_url = $section === 'home' ? $home_url : '';

            $items[] = [
                'label' => $section_label,
                'url' => $section_url,
                'current' => empty($active_page) && $current_title === '',
            ];
        }

        if (!empty($active_page)) {
            $items[] = [
                'label' => $current_title !== '' ? $current_title : (string) ($active_page['label'] ?? $active_canonical_slug),
                'url' => '',
                'current' => true,
            ];
        } elseif ($current_title !== '') {
            $items[] = [
                'label' => $current_title,
                'url' => '',
                'current' => true,
            ];
        }

        return $items;
    }
}

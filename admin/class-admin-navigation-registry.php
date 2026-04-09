<?php
/**
 * Registro central de navegación admin para Flavor Platform.
 *
 * Define una arquitectura canónica de secciones, grupos y páginas
 * sin forzar todavía la migración completa de slugs legacy.
 *
 * @package FlavorPlatform
 * @subpackage Admin
 * @since 4.3.0
 */

if (!defined('ABSPATH')) {
    exit;
}

class Flavor_Admin_Navigation_Registry {

    /**
     * Instancia singleton.
     *
     * @var self|null
     */
    private static $instance = null;

    /**
     * Estructura registrada.
     *
     * @var array<string,mixed>
     */
    private $registry = [];

    /**
     * Caché agrupada por request.
     *
     * @var array<string,mixed>|null
     */
    private $grouped_pages_cache = null;

    /**
     * Caché de navegación compacta por sección.
     *
     * @var array<string,array<string,array<string,mixed>>>
     */
    private $compact_nav_cache = [];

    /**
     * Obtiene la instancia singleton.
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
     * Constructor privado.
     */
    private function __construct() {
        $this->registry = $this->build_registry();
    }

    /**
     * Devuelve el registro completo.
     *
     * @return array<string,mixed>
     */
    public function get_registry() {
        return $this->registry;
    }

    /**
     * Devuelve las secciones registradas.
     *
     * @return array<string,mixed>
     */
    public function get_sections() {
        return $this->registry['sections'] ?? [];
    }

    /**
     * Devuelve las páginas registradas.
     *
     * @return array<string,mixed>
     */
    public function get_pages() {
        return $this->registry['pages'] ?? [];
    }

    /**
     * Devuelve solo las páginas visibles en navegación principal.
     *
     * @return array<string,mixed>
     */
    public function get_visible_pages() {
        $pages = $this->get_pages();

        return array_filter($pages, static function($page) {
            return ($page['menu_visibility'] ?? 'visible') !== 'hidden';
        });
    }

    /**
     * Devuelve páginas por rol de navegación.
     *
     * @param string $role
     * @return array<string,mixed>
     */
    public function get_pages_by_role($role) {
        $role = sanitize_key((string) $role);
        if ($role === '') {
            return [];
        }

        return array_filter($this->get_pages(), static function($page) use ($role) {
            return ($page['navigation_role'] ?? 'primary') === $role;
        });
    }

    /**
     * Devuelve los slugs legacy agrupados por slug canónico.
     *
     * @return array<string,array<int,string>>
     */
    public function get_legacy_aliases() {
        $aliases = [];

        foreach ($this->get_pages() as $slug => $page) {
            $page_aliases = array_values(array_filter((array) ($page['legacy_aliases'] ?? [])));
            if (!empty($page_aliases)) {
                $aliases[$slug] = $page_aliases;
            }
        }

        return $aliases;
    }

    /**
     * Resuelve un slug legacy o canónico a su slug canónico.
     *
     * @param string $slug
     * @return string
     */
    public function resolve_canonical_slug($slug) {
        $slug = sanitize_key((string) $slug);
        if ($slug === '') {
            return '';
        }

        $pages = $this->get_pages();
        if (isset($pages[$slug])) {
            return $slug;
        }

        foreach ($this->get_legacy_aliases() as $canonical_slug => $legacy_aliases) {
            if (in_array($slug, array_map('sanitize_key', (array) $legacy_aliases), true)) {
                return sanitize_key((string) $canonical_slug);
            }
        }

        return '';
    }

    /**
     * Devuelve la URL admin canónica para un slug registrado.
     *
     * @param string $slug
     * @param array<string,mixed> $query_args
     * @return string
     */
    public function get_admin_url($slug, array $query_args = []) {
        $canonical_slug = $this->resolve_canonical_slug($slug);
        if ($canonical_slug === '') {
            return '';
        }

        $query_args = array_merge(['page' => $canonical_slug], $query_args);

        return add_query_arg($query_args, admin_url('admin.php'));
    }

    /**
     * Obtiene una página concreta por slug.
     *
     * @param string $slug
     * @return array<string,mixed>|null
     */
    public function get_page($slug) {
        $slug = sanitize_key((string) $slug);
        $pages = $this->get_pages();

        return $pages[$slug] ?? null;
    }

    /**
     * Devuelve las páginas agrupadas por sección y grupo.
     *
     * @return array<string,mixed>
     */
    public function get_grouped_pages() {
        if ($this->grouped_pages_cache !== null) {
            return $this->grouped_pages_cache;
        }

        $sections = $this->get_sections();
        $pages = $this->get_visible_pages();
        $grouped = [];

        foreach ($sections as $section_id => $section) {
            $grouped[$section_id] = [
                'label' => $section['label'],
                'groups' => [],
            ];
        }

        foreach ($pages as $slug => $page) {
            if (($page['menu_visibility'] ?? 'visible') === 'hidden') {
                continue;
            }

            $section_id = $page['section'] ?? 'system';
            $group_id = $page['group'] ?? 'misc';

            if (!isset($grouped[$section_id])) {
                $grouped[$section_id] = [
                    'label' => ucfirst($section_id),
                    'groups' => [],
                ];
            }

            if (!isset($grouped[$section_id]['groups'][$group_id])) {
                $grouped[$section_id]['groups'][$group_id] = [
                    'label' => $page['group_label'] ?? ucfirst(str_replace('-', ' ', $group_id)),
                    'items' => [],
                ];
            }

            $grouped[$section_id]['groups'][$group_id]['items'][$slug] = $page;
        }

        foreach ($grouped as &$section) {
            foreach ($section['groups'] as &$group) {
                uasort($group['items'], static function($a, $b) {
                    return (int) ($a['priority'] ?? 999) <=> (int) ($b['priority'] ?? 999);
                });
            }
        }

        $this->grouped_pages_cache = $grouped;

        return $this->grouped_pages_cache;
    }

    /**
     * Devuelve las páginas marcadas para navegación compacta de una sección.
     *
     * @param string $section_id
     * @return array<string,array<string,mixed>>
     */
    public function get_compact_nav_pages($section_id) {
        $section_id = sanitize_key((string) $section_id);
        if ($section_id === '') {
            return [];
        }

        if (isset($this->compact_nav_cache[$section_id])) {
            return $this->compact_nav_cache[$section_id];
        }

        $pages = $this->get_pages();
        $compact_pages = [];

        foreach ($pages as $slug => $page) {
            if (($page['section'] ?? '') !== $section_id) {
                continue;
            }

            if (empty($page['compact_nav'])) {
                continue;
            }

            $compact_pages[$slug] = $page;
        }

        uasort($compact_pages, static function($a, $b) {
            return (int) ($a['priority'] ?? 999) <=> (int) ($b['priority'] ?? 999);
        });

        $this->compact_nav_cache[$section_id] = $compact_pages;

        return $this->compact_nav_cache[$section_id];
    }

    /**
     * Construye el registro canónico.
     *
     * @return array<string,mixed>
     */
    private function build_registry() {
        $sections = [
            'home' => [
                'label' => __('Inicio', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'priority' => 10,
            ],
            'ecosystem' => [
                'label' => __('Ecosistema', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'priority' => 20,
            ],
            'configuration' => [
                'label' => __('Configuración', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'priority' => 30,
            ],
            'system' => [
                'label' => __('Sistema', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'priority' => 40,
            ],
        ];

        $pages = [
            'flavor-dashboard' => $this->page('home', 'summary', __('Inicio', FLAVOR_PLATFORM_TEXT_DOMAIN), __('Inicio', FLAVOR_PLATFORM_TEXT_DOMAIN), 10, ['flavor-home'], ['navigation_role' => 'primary']),
            'flavor-unified-dashboard' => $this->page('home', 'summary', __('Widgets', FLAVOR_PLATFORM_TEXT_DOMAIN), __('Inicio', FLAVOR_PLATFORM_TEXT_DOMAIN), 20, [], ['compact_nav' => true, 'icon' => 'dashicons-grid-view', 'menu_visibility' => 'hidden', 'navigation_role' => 'auxiliary']),

            'flavor-app-composer' => $this->page('ecosystem', 'catalog', __('Compositor de Módulos', FLAVOR_PLATFORM_TEXT_DOMAIN), __('Catálogo', FLAVOR_PLATFORM_TEXT_DOMAIN), 10, [], ['compact_nav' => true, 'icon' => 'dashicons-screenoptions', 'navigation_role' => 'primary']),
            'flavor-addons' => $this->page('ecosystem', 'catalog', __('Addons', FLAVOR_PLATFORM_TEXT_DOMAIN), __('Catálogo', FLAVOR_PLATFORM_TEXT_DOMAIN), 20, [], ['compact_nav' => true, 'icon' => 'dashicons-admin-plugins', 'navigation_role' => 'primary']),
            'flavor-marketplace' => $this->page('ecosystem', 'catalog', __('Marketplace', FLAVOR_PLATFORM_TEXT_DOMAIN), __('Catálogo', FLAVOR_PLATFORM_TEXT_DOMAIN), 30, [], ['compact_nav' => true, 'icon' => 'dashicons-store', 'navigation_role' => 'primary']),
            'flavor-platform-license' => $this->page('ecosystem', 'extensions', __('Licencia', FLAVOR_PLATFORM_TEXT_DOMAIN), __('Extensiones', FLAVOR_PLATFORM_TEXT_DOMAIN), 35, ['flavor-license'], ['compact_nav' => true, 'icon' => 'dashicons-admin-network', 'navigation_role' => 'primary']),
            'flavor-module-relations' => $this->page('ecosystem', 'relations', __('Relaciones', FLAVOR_PLATFORM_TEXT_DOMAIN), __('Relaciones', FLAVOR_PLATFORM_TEXT_DOMAIN), 40, [], ['compact_nav' => true, 'icon' => 'dashicons-share', 'navigation_role' => 'primary']),
            'flavor-module-dashboards' => $this->page('ecosystem', 'dashboards', __('Dashboards de Módulos', FLAVOR_PLATFORM_TEXT_DOMAIN), __('Dashboards', FLAVOR_PLATFORM_TEXT_DOMAIN), 50, [], ['navigation_role' => 'primary']),
            'flavor-bundles' => $this->page('ecosystem', 'bundles', __('Bundles', FLAVOR_PLATFORM_TEXT_DOMAIN), __('Bundles', FLAVOR_PLATFORM_TEXT_DOMAIN), 60, [], ['compact_nav' => true, 'icon' => 'dashicons-category', 'navigation_role' => 'primary']),
            'flavor-newsletter' => $this->page('ecosystem', 'extensions', __('Newsletter', FLAVOR_PLATFORM_TEXT_DOMAIN), __('Extensiones', FLAVOR_PLATFORM_TEXT_DOMAIN), 70, [], ['menu_visibility' => 'hidden', 'navigation_role' => 'auxiliary']),

            'flavor-design-settings' => $this->page('configuration', 'experience', __('Diseño y Apariencia', FLAVOR_PLATFORM_TEXT_DOMAIN), __('Experiencia', FLAVOR_PLATFORM_TEXT_DOMAIN), 10, [], ['compact_nav' => true, 'icon' => 'dashicons-art', 'navigation_role' => 'primary']),
            'flavor-layouts' => $this->page('configuration', 'experience', __('Layouts', FLAVOR_PLATFORM_TEXT_DOMAIN), __('Experiencia', FLAVOR_PLATFORM_TEXT_DOMAIN), 20, [], ['compact_nav' => true, 'icon' => 'dashicons-layout', 'navigation_role' => 'primary']),
            'flavor-create-pages' => $this->page('configuration', 'pages', __('Páginas', FLAVOR_PLATFORM_TEXT_DOMAIN), __('Páginas y Landings', FLAVOR_PLATFORM_TEXT_DOMAIN), 30, [], ['compact_nav' => true, 'icon' => 'dashicons-admin-page', 'navigation_role' => 'primary']),
            // @deprecated 3.4.0 - Redirige a VBP Editor
            'flavor-landing-editor' => $this->page('configuration', 'pages', __('Editor Visual', FLAVOR_PLATFORM_TEXT_DOMAIN), __('Páginas y Landings', FLAVOR_PLATFORM_TEXT_DOMAIN), 40, [], ['menu_visibility' => 'hidden', 'navigation_role' => 'deprecated', 'redirect_to' => 'vbp-editor']),
            'flavor-permissions' => $this->page('configuration', 'permissions', __('Permisos', FLAVOR_PLATFORM_TEXT_DOMAIN), __('Permisos', FLAVOR_PLATFORM_TEXT_DOMAIN), 50, [], ['compact_nav' => true, 'icon' => 'dashicons-lock', 'navigation_role' => 'primary']),
            'flavor-platform-settings' => $this->page('configuration', 'settings', __('Configuración IA', FLAVOR_PLATFORM_TEXT_DOMAIN), __('Ajustes', FLAVOR_PLATFORM_TEXT_DOMAIN), 60, ['flavor-chat-config'], ['compact_nav' => true, 'icon' => 'dashicons-admin-generic', 'navigation_role' => 'primary']),
            'flavor-platform-escalations' => $this->page('configuration', 'settings', __('Escalados', FLAVOR_PLATFORM_TEXT_DOMAIN), __('Ajustes', FLAVOR_PLATFORM_TEXT_DOMAIN), 70, ['flavor-chat-ia-escalations'], ['compact_nav' => true, 'icon' => 'dashicons-warning', 'navigation_role' => 'primary']),
            'flavor-platform-apps' => $this->page('configuration', 'profile', __('Apps Móviles', FLAVOR_PLATFORM_TEXT_DOMAIN), __('Perfil y App', FLAVOR_PLATFORM_TEXT_DOMAIN), 80, ['flavor-apps-config'], ['compact_nav' => true, 'icon' => 'dashicons-smartphone', 'navigation_role' => 'primary']),
            'flavor-platform-app-menu' => $this->page('configuration', 'profile', __('Menú App', FLAVOR_PLATFORM_TEXT_DOMAIN), __('Perfil y App', FLAVOR_PLATFORM_TEXT_DOMAIN), 85, ['flavor-app-menu'], ['compact_nav' => true, 'icon' => 'dashicons-menu', 'navigation_role' => 'primary']),
            'flavor-platform-deep-links' => $this->page('configuration', 'profile', __('Deep Links', FLAVOR_PLATFORM_TEXT_DOMAIN), __('Perfil y App', FLAVOR_PLATFORM_TEXT_DOMAIN), 90, ['flavor-deep-links'], ['compact_nav' => true, 'icon' => 'dashicons-admin-links', 'navigation_role' => 'primary']),
            'flavor-platform-network' => $this->page('configuration', 'profile', __('Red de Nodos', FLAVOR_PLATFORM_TEXT_DOMAIN), __('Perfil y App', FLAVOR_PLATFORM_TEXT_DOMAIN), 100, ['flavor-network'], ['compact_nav' => true, 'icon' => 'dashicons-networking', 'navigation_role' => 'primary']),
            'flavor-platform-views' => $this->page('configuration', 'settings', __('Configuración de Vistas', FLAVOR_PLATFORM_TEXT_DOMAIN), __('Ajustes', FLAVOR_PLATFORM_TEXT_DOMAIN), 110, ['flavor-config-vistas'], ['menu_visibility' => 'hidden', 'navigation_role' => 'auxiliary']),
            'flavor-app-cpts' => $this->page('configuration', 'profile', __('Contenido de Apps', FLAVOR_PLATFORM_TEXT_DOMAIN), __('Perfil y App', FLAVOR_PLATFORM_TEXT_DOMAIN), 120, [], ['menu_visibility' => 'hidden', 'navigation_role' => 'auxiliary']),

            'flavor-platform-demo-data' => $this->page('system', 'maintenance', __('Datos Demo', FLAVOR_PLATFORM_TEXT_DOMAIN), __('Mantenimiento', FLAVOR_PLATFORM_TEXT_DOMAIN), 5, ['flavor-demo-data', 'flavor-demo-data-legacy'], ['compact_nav' => true, 'icon' => 'dashicons-database-import', 'navigation_role' => 'primary']),
            'flavor-platform-export-import' => $this->page('system', 'maintenance', __('Exportar / Importar', FLAVOR_PLATFORM_TEXT_DOMAIN), __('Mantenimiento', FLAVOR_PLATFORM_TEXT_DOMAIN), 10, ['flavor-export-import'], ['compact_nav' => true, 'icon' => 'dashicons-migrate', 'navigation_role' => 'primary']),
            'flavor-platform-health-check' => $this->page('system', 'maintenance', __('Diagnóstico', FLAVOR_PLATFORM_TEXT_DOMAIN), __('Mantenimiento', FLAVOR_PLATFORM_TEXT_DOMAIN), 20, ['flavor-health-check'], ['compact_nav' => true, 'icon' => 'dashicons-heart', 'navigation_role' => 'primary']),
            'flavor-platform-activity-log' => $this->page('system', 'maintenance', __('Registro de Actividad', FLAVOR_PLATFORM_TEXT_DOMAIN), __('Mantenimiento', FLAVOR_PLATFORM_TEXT_DOMAIN), 30, ['flavor-activity-log'], ['compact_nav' => true, 'icon' => 'dashicons-backup', 'navigation_role' => 'primary']),
            'flavor-analytics' => $this->page('system', 'maintenance', __('Analytics', FLAVOR_PLATFORM_TEXT_DOMAIN), __('Mantenimiento', FLAVOR_PLATFORM_TEXT_DOMAIN), 40, [], ['menu_visibility' => 'hidden', 'navigation_role' => 'auxiliary']),
            'flavor-api-docs' => $this->page('system', 'maintenance', __('API Docs', FLAVOR_PLATFORM_TEXT_DOMAIN), __('Mantenimiento', FLAVOR_PLATFORM_TEXT_DOMAIN), 50, [], ['menu_visibility' => 'hidden', 'navigation_role' => 'auxiliary']),
            'flavor-systems-panel' => $this->page('system', 'maintenance', __('Panel de Sistemas', FLAVOR_PLATFORM_TEXT_DOMAIN), __('Mantenimiento', FLAVOR_PLATFORM_TEXT_DOMAIN), 60, [], ['menu_visibility' => 'hidden', 'navigation_role' => 'legacy_bridge']),
            'flavor-platform-docs' => $this->page('system', 'help', __('Documentación', FLAVOR_PLATFORM_TEXT_DOMAIN), __('Ayuda', FLAVOR_PLATFORM_TEXT_DOMAIN), 70, ['flavor-documentation', 'flavor-documentacion', 'flavor-docs'], ['compact_nav' => true, 'icon' => 'dashicons-book', 'navigation_role' => 'primary']),
            'flavor-tours' => $this->page('system', 'help', __('Tours Guiados', FLAVOR_PLATFORM_TEXT_DOMAIN), __('Ayuda', FLAVOR_PLATFORM_TEXT_DOMAIN), 80, [], ['compact_nav' => true, 'icon' => 'dashicons-location', 'navigation_role' => 'primary']),
            'flavor-setup-wizard' => $this->page('system', 'onboarding', __('Asistente de Configuración', FLAVOR_PLATFORM_TEXT_DOMAIN), __('Onboarding', FLAVOR_PLATFORM_TEXT_DOMAIN), 90, ['flavor-onboarding'], ['menu_visibility' => 'hidden', 'navigation_role' => 'auxiliary']),
            'flavor-newsletter-editor' => $this->page('system', 'help', __('Editor de Campaña', FLAVOR_PLATFORM_TEXT_DOMAIN), __('Ayuda', FLAVOR_PLATFORM_TEXT_DOMAIN), 100, [], ['menu_visibility' => 'hidden', 'navigation_role' => 'auxiliary']),
            'sello-conciencia' => $this->page('system', 'help', __('Sello de Conciencia', FLAVOR_PLATFORM_TEXT_DOMAIN), __('Ayuda', FLAVOR_PLATFORM_TEXT_DOMAIN), 110, [], ['menu_visibility' => 'hidden', 'navigation_role' => 'auxiliary']),
        ];

        return [
            'sections' => $sections,
            'pages' => apply_filters('flavor_admin_navigation_registry_pages', $pages),
        ];
    }

    /**
     * Crea la configuración básica de una página.
     *
     * @param string $section
     * @param string $group
     * @param string $label
     * @param string $group_label
     * @param int    $priority
     * @param array<int,string> $legacy_aliases
     * @param array<string,mixed> $extra
     * @return array<string,mixed>
     */
    private function page($section, $group, $label, $group_label, $priority, array $legacy_aliases = [], array $extra = []) {
        return array_merge([
            'section' => $section,
            'group' => $group,
            'label' => $label,
            'group_label' => $group_label,
            'priority' => $priority,
            'legacy_aliases' => $legacy_aliases,
        ], $extra);
    }
}

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
                'label' => __('Inicio', 'flavor-chat-ia'),
                'priority' => 10,
            ],
            'ecosystem' => [
                'label' => __('Ecosistema', 'flavor-chat-ia'),
                'priority' => 20,
            ],
            'configuration' => [
                'label' => __('Configuración', 'flavor-chat-ia'),
                'priority' => 30,
            ],
            'system' => [
                'label' => __('Sistema', 'flavor-chat-ia'),
                'priority' => 40,
            ],
        ];

        $pages = [
            'flavor-dashboard' => $this->page('home', 'summary', __('Inicio', 'flavor-chat-ia'), __('Inicio', 'flavor-chat-ia'), 10, ['flavor-home'], ['navigation_role' => 'primary']),
            'flavor-unified-dashboard' => $this->page('home', 'summary', __('Widgets', 'flavor-chat-ia'), __('Inicio', 'flavor-chat-ia'), 20, [], ['compact_nav' => true, 'icon' => 'dashicons-grid-view', 'menu_visibility' => 'hidden', 'navigation_role' => 'auxiliary']),

            'flavor-app-composer' => $this->page('ecosystem', 'catalog', __('Compositor de Módulos', 'flavor-chat-ia'), __('Catálogo', 'flavor-chat-ia'), 10, [], ['compact_nav' => true, 'icon' => 'dashicons-screenoptions', 'navigation_role' => 'primary']),
            'flavor-addons' => $this->page('ecosystem', 'catalog', __('Addons', 'flavor-chat-ia'), __('Catálogo', 'flavor-chat-ia'), 20, [], ['compact_nav' => true, 'icon' => 'dashicons-admin-plugins', 'navigation_role' => 'primary']),
            'flavor-marketplace' => $this->page('ecosystem', 'catalog', __('Marketplace', 'flavor-chat-ia'), __('Catálogo', 'flavor-chat-ia'), 30, [], ['compact_nav' => true, 'icon' => 'dashicons-store', 'navigation_role' => 'primary']),
            'flavor-module-relations' => $this->page('ecosystem', 'relations', __('Relaciones', 'flavor-chat-ia'), __('Relaciones', 'flavor-chat-ia'), 40, [], ['compact_nav' => true, 'icon' => 'dashicons-share', 'navigation_role' => 'primary']),
            'flavor-module-dashboards' => $this->page('ecosystem', 'dashboards', __('Dashboards de Módulos', 'flavor-chat-ia'), __('Dashboards', 'flavor-chat-ia'), 50, [], ['navigation_role' => 'primary']),
            'flavor-bundles' => $this->page('ecosystem', 'bundles', __('Bundles', 'flavor-chat-ia'), __('Bundles', 'flavor-chat-ia'), 60, [], ['compact_nav' => true, 'icon' => 'dashicons-category', 'navigation_role' => 'primary']),
            'flavor-newsletter' => $this->page('ecosystem', 'extensions', __('Newsletter', 'flavor-chat-ia'), __('Extensiones', 'flavor-chat-ia'), 70, [], ['menu_visibility' => 'hidden', 'navigation_role' => 'auxiliary']),

            'flavor-design-settings' => $this->page('configuration', 'experience', __('Diseño y Apariencia', 'flavor-chat-ia'), __('Experiencia', 'flavor-chat-ia'), 10, [], ['compact_nav' => true, 'icon' => 'dashicons-art', 'navigation_role' => 'primary']),
            'flavor-layouts' => $this->page('configuration', 'experience', __('Layouts', 'flavor-chat-ia'), __('Experiencia', 'flavor-chat-ia'), 20, [], ['compact_nav' => true, 'icon' => 'dashicons-layout', 'navigation_role' => 'primary']),
            'flavor-create-pages' => $this->page('configuration', 'pages', __('Páginas', 'flavor-chat-ia'), __('Páginas y Landings', 'flavor-chat-ia'), 30, [], ['compact_nav' => true, 'icon' => 'dashicons-admin-page', 'navigation_role' => 'primary']),
            // @deprecated 3.4.0 - Redirige a VBP Editor
            'flavor-landing-editor' => $this->page('configuration', 'pages', __('Editor Visual', 'flavor-chat-ia'), __('Páginas y Landings', 'flavor-chat-ia'), 40, [], ['menu_visibility' => 'hidden', 'navigation_role' => 'deprecated', 'redirect_to' => 'vbp-editor']),
            'flavor-permissions' => $this->page('configuration', 'permissions', __('Permisos', 'flavor-chat-ia'), __('Permisos', 'flavor-chat-ia'), 50, [], ['compact_nav' => true, 'icon' => 'dashicons-lock', 'navigation_role' => 'primary']),
            'flavor-chat-config' => $this->page('configuration', 'settings', __('Configuración IA', 'flavor-chat-ia'), __('Ajustes', 'flavor-chat-ia'), 60, [], ['compact_nav' => true, 'icon' => 'dashicons-admin-generic', 'navigation_role' => 'primary']),
            'flavor-chat-ia-escalations' => $this->page('configuration', 'settings', __('Escalados', 'flavor-chat-ia'), __('Ajustes', 'flavor-chat-ia'), 70, [], ['compact_nav' => true, 'icon' => 'dashicons-warning', 'navigation_role' => 'primary']),
            'flavor-apps-config' => $this->page('configuration', 'profile', __('Apps Móviles', 'flavor-chat-ia'), __('Perfil y App', 'flavor-chat-ia'), 80, [], ['compact_nav' => true, 'icon' => 'dashicons-smartphone', 'navigation_role' => 'primary']),
            'flavor-app-menu' => $this->page('configuration', 'profile', __('Menú App', 'flavor-chat-ia'), __('Perfil y App', 'flavor-chat-ia'), 85, [], ['compact_nav' => true, 'icon' => 'dashicons-menu', 'navigation_role' => 'primary']),
            'flavor-deep-links' => $this->page('configuration', 'profile', __('Deep Links', 'flavor-chat-ia'), __('Perfil y App', 'flavor-chat-ia'), 90, [], ['compact_nav' => true, 'icon' => 'dashicons-admin-links', 'navigation_role' => 'primary']),
            'flavor-network' => $this->page('configuration', 'profile', __('Red de Nodos', 'flavor-chat-ia'), __('Perfil y App', 'flavor-chat-ia'), 100, [], ['compact_nav' => true, 'icon' => 'dashicons-networking', 'navigation_role' => 'primary']),
            'flavor-config-vistas' => $this->page('configuration', 'settings', __('Configuración de Vistas', 'flavor-chat-ia'), __('Ajustes', 'flavor-chat-ia'), 110, [], ['menu_visibility' => 'hidden', 'navigation_role' => 'auxiliary']),
            'flavor-app-cpts' => $this->page('configuration', 'profile', __('Contenido de Apps', 'flavor-chat-ia'), __('Perfil y App', 'flavor-chat-ia'), 120, [], ['menu_visibility' => 'hidden', 'navigation_role' => 'auxiliary']),

            'flavor-export-import' => $this->page('system', 'maintenance', __('Exportar / Importar', 'flavor-chat-ia'), __('Mantenimiento', 'flavor-chat-ia'), 10, [], ['compact_nav' => true, 'icon' => 'dashicons-migrate', 'navigation_role' => 'primary']),
            'flavor-health-check' => $this->page('system', 'maintenance', __('Diagnóstico', 'flavor-chat-ia'), __('Mantenimiento', 'flavor-chat-ia'), 20, [], ['compact_nav' => true, 'icon' => 'dashicons-heart', 'navigation_role' => 'primary']),
            'flavor-activity-log' => $this->page('system', 'maintenance', __('Registro de Actividad', 'flavor-chat-ia'), __('Mantenimiento', 'flavor-chat-ia'), 30, [], ['compact_nav' => true, 'icon' => 'dashicons-backup', 'navigation_role' => 'primary']),
            'flavor-analytics' => $this->page('system', 'maintenance', __('Analytics', 'flavor-chat-ia'), __('Mantenimiento', 'flavor-chat-ia'), 40, [], ['menu_visibility' => 'hidden', 'navigation_role' => 'auxiliary']),
            'flavor-api-docs' => $this->page('system', 'maintenance', __('API Docs', 'flavor-chat-ia'), __('Mantenimiento', 'flavor-chat-ia'), 50, [], ['menu_visibility' => 'hidden', 'navigation_role' => 'auxiliary']),
            'flavor-systems-panel' => $this->page('system', 'maintenance', __('Panel de Sistemas', 'flavor-chat-ia'), __('Mantenimiento', 'flavor-chat-ia'), 60, [], ['menu_visibility' => 'hidden', 'navigation_role' => 'legacy_bridge']),
            'flavor-documentation' => $this->page('system', 'help', __('Documentación', 'flavor-chat-ia'), __('Ayuda', 'flavor-chat-ia'), 70, ['flavor-documentacion', 'flavor-docs'], ['compact_nav' => true, 'icon' => 'dashicons-book', 'navigation_role' => 'primary']),
            'flavor-tours' => $this->page('system', 'help', __('Tours Guiados', 'flavor-chat-ia'), __('Ayuda', 'flavor-chat-ia'), 80, [], ['compact_nav' => true, 'icon' => 'dashicons-location', 'navigation_role' => 'primary']),
            'flavor-setup-wizard' => $this->page('system', 'onboarding', __('Asistente de Configuración', 'flavor-chat-ia'), __('Onboarding', 'flavor-chat-ia'), 90, ['flavor-onboarding'], ['menu_visibility' => 'hidden', 'navigation_role' => 'auxiliary']),
            'flavor-newsletter-editor' => $this->page('system', 'help', __('Editor de Campaña', 'flavor-chat-ia'), __('Ayuda', 'flavor-chat-ia'), 100, [], ['menu_visibility' => 'hidden', 'navigation_role' => 'auxiliary']),
            'sello-conciencia' => $this->page('system', 'help', __('Sello de Conciencia', 'flavor-chat-ia'), __('Ayuda', 'flavor-chat-ia'), 110, [], ['menu_visibility' => 'hidden', 'navigation_role' => 'auxiliary']),
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

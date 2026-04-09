<?php
/**
 * Flavor Admin Shell - Sistema de navegación admin elegante
 *
 * Reemplaza el sidebar de WordPress con una interfaz moderna y elegante
 * cuando el usuario está en páginas del plugin Flavor.
 *
 * @package FlavorChatIA
 * @since 3.2.0
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Clase principal del Admin Shell
 */
class Flavor_Admin_Shell {

    /**
     * Instancia singleton
     */
    private static $instance = null;

    /**
     * Prefijos de páginas Flavor
     */
    const PAGE_PREFIXES = [
        // Prefijos core
        'flavor-',
        'gc-',

        // Comunidad
        'socios',
        'comunidades',
        'colectivos',
        'foros',
        'actores',

        // Actividades
        'eventos',
        'cursos',
        'talleres',
        'reservas',

        // Servicios
        'tramites',
        'incidencias',
        'ayuda-',
        'participacion',
        'presupuestos',
        'transparencia',
        'denuncias',
        'documentos',
        'avisos',

        // Economía
        'marketplace',
        'banco-tiempo',
        'contabilidad',
        'economia-don',
        'suficiencia',

        // Recursos
        'huertos',
        'espacios',
        'biblioteca',
        'carpooling',
        'fichaje',
        'parkings',
        'bares',
        'recetas',

        // Sostenibilidad
        'reciclaje',
        'compostaje',
        'bicicletas',
        'biodiversidad',
        'circulos-cuidados',
        'huella-ecologica',
        'justicia-restaurativa',
        'saberes',
        'trabajo-digno',
        'sello-conciencia',

        // Comunicación
        'multimedia',
        'podcast',
        'radio',
        'campanias',
        'email-marketing',
        'encuestas',

        // Chat
        'chat-grupos',
        'chat-interno',
        'chat-estados',

        // Negocios
        'clientes',
        'facturas',
        'empresas',
        'empresarial',
        'crowdfunding',
        'themacle',
        'trading-ia',
        'dex-solana',

        // Admin
        'advertising',
    ];

    /**
     * Prefijos de CPTs del ecosistema Flavor
     */
    const POST_TYPE_PREFIXES = [
        'flavor_',
        'gc_',
        'bl_',
        'cc_',
        'ed_',
        'es_',
        'he_',
        'jr_',
        'sa_',
        'td_',
        'marketplace_',
        'mi_',
        'guia_',
        'recompensa_',
    ];

    /**
     * Prefijos de taxonomías del ecosistema Flavor
     */
    const TAXONOMY_PREFIXES = [
        'flavor_',
        'gc_',
        'bl_',
        'cc_',
        'ed_',
        'marketplace_',
        'mi_',
        'receta_',
        'sa_',
        'td_',
        'ad_',
        'categoria_',
        'tipo_',
    ];

    /**
     * Meta key para estado del shell por usuario
     */
    const USER_META_KEY = 'flavor_admin_shell_disabled';

    /**
     * Obtener instancia singleton
     */
    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Constructor
     */
    private function __construct() {
        if (!is_admin()) {
            return;
        }

        add_action('admin_init', [$this, 'init']);
        add_action('admin_enqueue_scripts', [$this, 'enqueue_assets'], 5);
        add_action('admin_footer', [$this, 'render_shell'], 5);
        add_action('wp_ajax_flavor_toggle_admin_shell', [$this, 'ajax_toggle_shell']);
    }

    /**
     * Inicialización
     */
    public function init() {
        // Nada adicional por ahora
    }

    /**
     * Verifica si estamos en una página Flavor
     *
     * @return bool
     */
    public function is_flavor_page() {
        $page = isset($_GET['page']) ? sanitize_text_field($_GET['page']) : '';

        $page_prefixes = apply_filters('flavor_admin_shell_page_prefixes', self::PAGE_PREFIXES);

        if ($page !== '' && $this->is_registered_flavor_admin_page($page)) {
            return true;
        }

        // Verificar prefijos de páginas del ecosistema Flavor y dashboards de módulos
        foreach ($page_prefixes as $prefix) {
            if (strpos($page, $prefix) === 0) {
                return true;
            }
        }

        // Verificar CPTs y taxonomías del ecosistema
        $screen = get_current_screen();
        if ($screen) {
            $post_type = $screen->post_type ?? '';
            $taxonomy = $screen->taxonomy ?? '';

            $post_type_prefixes = apply_filters('flavor_admin_shell_post_type_prefixes', self::POST_TYPE_PREFIXES);
            foreach ($post_type_prefixes as $prefix) {
                if ($post_type && strpos($post_type, $prefix) === 0) {
                    return true;
                }
            }

            $taxonomy_prefixes = apply_filters('flavor_admin_shell_taxonomy_prefixes', self::TAXONOMY_PREFIXES);
            foreach ($taxonomy_prefixes as $prefix) {
                if ($taxonomy && strpos($taxonomy, $prefix) === 0) {
                    return true;
                }
            }

            $screen_id = $screen->id ?? '';
            $screen_base = $screen->base ?? '';
            $screen_parent = $screen->parent_base ?? '';

            foreach ($page_prefixes as $prefix) {
                if (
                    ($screen_id && strpos($screen_id, $prefix) !== false) ||
                    ($screen_base && strpos($screen_base, $prefix) !== false) ||
                    ($screen_parent && strpos($screen_parent, $prefix) !== false)
                ) {
                    return true;
                }
            }
        }

        return (bool) apply_filters('flavor_admin_shell_is_flavor_page', false, $page, $screen ?? null);
    }

    /**
     * Determina si un slug pertenece a la navegación admin registrada del plugin.
     *
     * Esto evita depender solo de prefijos hardcodeados cuando existen páginas
     * canónicas o subpáginas registradas fuera de los patrones legacy.
     *
     * @param string $page Slug actual de admin.php?page=...
     * @return bool
     */
    private function is_registered_flavor_admin_page($page) {
        $page = sanitize_key((string) $page);
        if ($page === '') {
            return false;
        }

        if (has_action('admin_page_' . $page)) {
            return true;
        }

        if (class_exists('Flavor_Admin_Navigation_Registry')) {
            $admin_registry = Flavor_Admin_Navigation_Registry::get_instance();
            if ($admin_registry->resolve_canonical_slug($page) !== '') {
                return true;
            }
        }

        if (class_exists('Flavor_Shell_Navigation_Registry')) {
            $shell_registry = Flavor_Shell_Navigation_Registry::get_instance();

            if ($shell_registry->get_parent_dashboard($page) !== null) {
                return true;
            }
        }

        $registered_modules = apply_filters('flavor_admin_panel_modules', []);
        if (is_array($registered_modules)) {
            foreach ($registered_modules as $module_config) {
                $pages = isset($module_config['paginas']) && is_array($module_config['paginas'])
                    ? $module_config['paginas']
                    : [];

                foreach ($pages as $module_page) {
                    $module_slug = isset($module_page['slug']) ? sanitize_key((string) $module_page['slug']) : '';
                    if ($module_slug !== '' && $module_slug === $page) {
                        return true;
                    }
                }
            }
        }

        global $menu, $submenu;

        foreach ((array) $menu as $menu_item) {
            $menu_slug = isset($menu_item[2]) ? sanitize_key((string) $menu_item[2]) : '';
            if ($menu_slug !== '' && $menu_slug === $page) {
                return true;
            }
        }

        foreach ((array) $submenu as $submenu_group) {
            foreach ((array) $submenu_group as $submenu_item) {
                $submenu_slug = isset($submenu_item[2]) ? sanitize_key((string) $submenu_item[2]) : '';
                if ($submenu_slug !== '' && $submenu_slug === $page) {
                    return true;
                }
            }
        }

        return false;
    }

    /**
     * Verifica si el shell está habilitado para el usuario actual
     *
     * @return bool
     */
    public function is_shell_enabled() {
        $user_id = get_current_user_id();
        if (!$user_id) {
            return false;
        }

        // En administración del plugin preferimos no dejar a los admins "bloqueados"
        // fuera del shell por un meta obsoleto tras migraciones o merges.
        if (current_user_can('manage_options')) {
            return true;
        }

        // Verificar si el usuario lo ha deshabilitado
        $is_disabled = get_user_meta($user_id, self::USER_META_KEY, true);

        return !$is_disabled;
    }

    /**
     * Verifica si estamos en el editor Visual Builder Pro
     *
     * @return bool
     */
    public function is_vbp_editor() {
        $page = isset($_GET['page']) ? sanitize_text_field($_GET['page']) : '';

        // El editor VBP usa page=vbp-editor
        return $page === 'vbp-editor';
    }

    /**
     * Verifica si el shell debe mostrarse
     *
     * @return bool
     */
    public function should_show_shell() {
        // No mostrar shell en el editor VBP (tiene su propia interfaz fullscreen)
        if ($this->is_vbp_editor()) {
            return false;
        }

        return $this->is_flavor_page() && $this->is_shell_enabled();
    }

    /**
     * Encolar assets CSS y JS
     */
    public function enqueue_assets() {
        if (!$this->should_show_shell()) {
            return;
        }

        // CSS del shell
        $shell_css_path = FLAVOR_CHAT_IA_PATH . 'admin/css/admin-shell.css';
        $shell_css_ver = file_exists($shell_css_path) ? (string) filemtime($shell_css_path) : FLAVOR_CHAT_IA_VERSION;
        wp_enqueue_style(
            'flavor-admin-shell',
            FLAVOR_CHAT_IA_URL . 'admin/css/admin-shell.css',
            [],
            $shell_css_ver
        );

        // Alpine.js (si no está ya cargado)
        if (!wp_script_is('alpine', 'enqueued')) {
            wp_enqueue_script(
                'alpine',
                FLAVOR_CHAT_IA_URL . 'assets/vbp/vendor/alpine.min.js',
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

        // JS del shell
        $shell_js_path = FLAVOR_CHAT_IA_PATH . 'admin/js/admin-shell.js';
        $shell_js_ver = file_exists($shell_js_path) ? (string) filemtime($shell_js_path) : FLAVOR_CHAT_IA_VERSION;
        wp_enqueue_script(
            'flavor-admin-shell',
            FLAVOR_CHAT_IA_URL . 'admin/js/admin-shell.js',
            ['alpine'],
            $shell_js_ver,
            true
        );

        // Localizar datos
        $search_catalog = $this->build_search_catalog();
        wp_localize_script('flavor-admin-shell', 'flavorAdminShell', [
            'ajaxUrl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('flavor_admin_shell'),
            'nonceVista' => wp_create_nonce('flavor_cambiar_vista'),
            'currentPage' => isset($_GET['page']) ? sanitize_text_field($_GET['page']) : '',
            'dashboardUrl' => admin_url('admin.php?page=flavor-dashboard'),
            'wpDashboardUrl' => admin_url(),
            'searchCatalog' => $search_catalog,
            'i18n' => [
                'collapse' => __('Colapsar menú', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'expand' => __('Expandir menú', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'backToWP' => __('Volver a WordPress', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'disableShell' => __('Desactivar shell', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'darkMode' => __('Modo oscuro', FLAVOR_PLATFORM_TEXT_DOMAIN),
            ],
        ]);

        // Añadir clase al body
        add_filter('admin_body_class', function($classes) {
            return $classes . ' fls-shell-active';
        });
    }

    /**
     * Renderizar el shell
     */
    public function render_shell() {
        if (!$this->should_show_shell()) {
            return;
        }

        $navigation = $this->get_navigation_structure();
        $current_page = isset($_GET['page']) ? sanitize_text_field($_GET['page']) : '';
        $is_dark_mode = $this->is_dark_mode();

        include FLAVOR_CHAT_IA_PATH . 'admin/views/shell-sidebar.php';
    }

    /**
     * Construye un catálogo de búsqueda canónico para el buscador del Shell.
     *
     * @return array<int, array<string, string>>
     */
    private function build_search_catalog() {
        $catalog = [];
        $navigation = $this->get_navigation_structure();

        foreach ($navigation as $section) {
            $section_label = isset($section['label']) ? (string) $section['label'] : '';
            $items = isset($section['items']) && is_array($section['items']) ? $section['items'] : [];

            foreach ($items as $item) {
                $slug = isset($item['slug']) ? (string) $item['slug'] : '';
                if ($slug === '') {
                    continue;
                }

                $catalog[] = [
                    'slug' => $slug,
                    'label' => isset($item['label']) ? (string) $item['label'] : $slug,
                    'icon' => isset($item['icon']) ? (string) $item['icon'] : 'dashicons-admin-page',
                    'section' => $section_label,
                    'url' => !empty($item['url']) ? (string) $item['url'] : admin_url('admin.php?page=' . rawurlencode($slug)),
                    'type' => 'page',
                ];

                if (!empty($item['subpages']) && is_array($item['subpages'])) {
                    foreach ($item['subpages'] as $subpage) {
                        $sub_slug = isset($subpage['slug']) ? (string) $subpage['slug'] : '';
                        if ($sub_slug === '') {
                            continue;
                        }

                        $catalog[] = [
                            'slug' => $sub_slug,
                            'label' => isset($subpage['label']) ? (string) $subpage['label'] : $sub_slug,
                            'icon' => isset($subpage['icon']) ? (string) $subpage['icon'] : 'dashicons-arrow-right-alt2',
                            'section' => isset($item['label']) ? (string) $item['label'] : $section_label,
                            'url' => admin_url('admin.php?page=' . rawurlencode($sub_slug)),
                            'type' => 'subpage',
                        ];
                    }
                }
            }
        }

        return $catalog;
    }

    /**
     * Página actual
     *
     * @var string
     */
    private $current_page = '';

    /**
     * Dashboard padre activo (si estamos en un módulo)
     *
     * @var string|null
     */
    private $active_parent_dashboard = null;

    /**
     * Obtener estructura de navegación
     *
     * Filtra los menús según la vista activa del usuario
     *
     * @return array
     */
    public function get_navigation_structure() {
        // Obtener página actual y detectar si estamos en un módulo
        $this->current_page = isset($_GET['page']) ? sanitize_text_field($_GET['page']) : '';
        $this->detect_active_module();

        // Estructura completa de navegación
        $estructura_completa = [
            'mi_app' => [
                'label' => __('Mi App', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'icon' => 'dashicons-admin-home',
                'items' => [
                    ['slug' => 'flavor-dashboard', 'label' => __('Dashboard', FLAVOR_PLATFORM_TEXT_DOMAIN), 'icon' => 'dashicons-dashboard'],
                    ['slug' => 'flavor-unified-dashboard', 'label' => __('Widgets', FLAVOR_PLATFORM_TEXT_DOMAIN), 'icon' => 'dashicons-grid-view'],
                    ['slug' => 'flavor-module-dashboards', 'label' => __('Dashboards', FLAVOR_PLATFORM_TEXT_DOMAIN), 'icon' => 'dashicons-screenoptions'],
                    ['slug' => 'flavor-app-composer', 'label' => __('Módulos', FLAVOR_PLATFORM_TEXT_DOMAIN), 'icon' => 'dashicons-admin-generic'],
                    ['slug' => 'flavor-design-settings', 'label' => __('Diseño', FLAVOR_PLATFORM_TEXT_DOMAIN), 'icon' => 'dashicons-art'],
                    ['slug' => 'flavor-layouts', 'label' => __('Layouts', FLAVOR_PLATFORM_TEXT_DOMAIN), 'icon' => 'dashicons-layout'],
                    ['slug' => 'flavor-create-pages', 'label' => __('Páginas', FLAVOR_PLATFORM_TEXT_DOMAIN), 'icon' => 'dashicons-admin-page'],
                    ['slug' => 'flavor-landings', 'url' => 'edit.php?post_type=flavor_landing', 'label' => __('Editor Visual', FLAVOR_PLATFORM_TEXT_DOMAIN), 'icon' => 'dashicons-edit'],
                    ['slug' => 'flavor-permissions', 'label' => __('Permisos', FLAVOR_PLATFORM_TEXT_DOMAIN), 'icon' => 'dashicons-lock'],
                ],
            ],
            'mod_comunidad' => [
                'label' => __('Comunidad', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'icon' => 'dashicons-groups',
                'items' => [
                    ['slug' => 'socios-dashboard', 'label' => __('Miembros', FLAVOR_PLATFORM_TEXT_DOMAIN), 'icon' => 'dashicons-id-alt'],
                    ['slug' => 'flavor-colectivos-dashboard', 'label' => __('Colectivos', FLAVOR_PLATFORM_TEXT_DOMAIN), 'icon' => 'dashicons-networking'],
                    ['slug' => 'comunidades-dashboard', 'label' => __('Comunidades', FLAVOR_PLATFORM_TEXT_DOMAIN), 'icon' => 'dashicons-admin-multisite'],
                    ['slug' => 'foros-dashboard', 'label' => __('Foros', FLAVOR_PLATFORM_TEXT_DOMAIN), 'icon' => 'dashicons-format-chat'],
                    ['slug' => 'flavor-red-social-dashboard', 'label' => __('Red Social', FLAVOR_PLATFORM_TEXT_DOMAIN), 'icon' => 'dashicons-share'],
                ],
            ],
            'mod_economia' => [
                'label' => __('Economía', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'icon' => 'dashicons-money-alt',
                'items' => [
                    ['slug' => 'gc-dashboard', 'label' => __('Grupos Consumo', FLAVOR_PLATFORM_TEXT_DOMAIN), 'icon' => 'dashicons-cart'],
                    ['slug' => 'marketplace-dashboard', 'label' => __('Marketplace', FLAVOR_PLATFORM_TEXT_DOMAIN), 'icon' => 'dashicons-store'],
                    ['slug' => 'banco-tiempo-dashboard', 'label' => __('Banco Tiempo', FLAVOR_PLATFORM_TEXT_DOMAIN), 'icon' => 'dashicons-clock'],
                    ['slug' => 'contabilidad-dashboard', 'label' => __('Contabilidad', FLAVOR_PLATFORM_TEXT_DOMAIN), 'icon' => 'dashicons-chart-pie'],
                    ['slug' => 'economia-don-dashboard', 'label' => __('Economía Don', FLAVOR_PLATFORM_TEXT_DOMAIN), 'icon' => 'dashicons-heart'],
                ],
            ],
            'mod_empresarial' => [
                'label' => __('Empresarial', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'icon' => 'dashicons-building',
                'items' => [
                    [
                        'slug' => 'empresas-dashboard',
                        'label' => __('Empresas', FLAVOR_PLATFORM_TEXT_DOMAIN),
                        'icon' => 'dashicons-building',
                        'subpages' => [
                            ['slug' => 'empresas-listado', 'label' => __('Listado', FLAVOR_PLATFORM_TEXT_DOMAIN), 'icon' => 'dashicons-list-view'],
                            ['slug' => 'empresas-solicitudes', 'label' => __('Solicitudes', FLAVOR_PLATFORM_TEXT_DOMAIN), 'icon' => 'dashicons-clipboard'],
                            ['slug' => 'empresas-config', 'label' => __('Configuración', FLAVOR_PLATFORM_TEXT_DOMAIN), 'icon' => 'dashicons-admin-generic'],
                        ],
                    ],
                    ['slug' => 'clientes-dashboard', 'label' => __('Clientes', FLAVOR_PLATFORM_TEXT_DOMAIN), 'icon' => 'dashicons-businessman'],
                    ['slug' => 'facturas-dashboard', 'label' => __('Facturas', FLAVOR_PLATFORM_TEXT_DOMAIN), 'icon' => 'dashicons-media-text'],
                    ['slug' => 'presupuestos-dashboard', 'label' => __('Presupuestos', FLAVOR_PLATFORM_TEXT_DOMAIN), 'icon' => 'dashicons-chart-pie'],
                    ['slug' => 'fichaje-dashboard', 'label' => __('Fichaje', FLAVOR_PLATFORM_TEXT_DOMAIN), 'icon' => 'dashicons-clock'],
                    ['slug' => 'flavor-woocommerce-dashboard', 'label' => __('WooCommerce', FLAVOR_PLATFORM_TEXT_DOMAIN), 'icon' => 'dashicons-cart'],
                    ['slug' => 'flavor-crowdfunding', 'label' => __('Crowdfunding', FLAVOR_PLATFORM_TEXT_DOMAIN), 'icon' => 'dashicons-money-alt'],
                    ['slug' => 'themacle-dashboard', 'label' => __('Themacle', FLAVOR_PLATFORM_TEXT_DOMAIN), 'icon' => 'dashicons-art'],
                    ['slug' => 'trading-ia-dashboard', 'label' => __('Trading IA', FLAVOR_PLATFORM_TEXT_DOMAIN), 'icon' => 'dashicons-chart-line'],
                    ['slug' => 'dex-solana-dashboard', 'label' => __('DEX Solana', FLAVOR_PLATFORM_TEXT_DOMAIN), 'icon' => 'dashicons-money'],
                ],
            ],
            'mod_actividades' => [
                'label' => __('Actividades', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'icon' => 'dashicons-calendar-alt',
                'items' => [
                    ['slug' => 'eventos-dashboard', 'label' => __('Eventos', FLAVOR_PLATFORM_TEXT_DOMAIN), 'icon' => 'dashicons-calendar'],
                    ['slug' => 'cursos-dashboard', 'label' => __('Cursos', FLAVOR_PLATFORM_TEXT_DOMAIN), 'icon' => 'dashicons-welcome-learn-more'],
                    ['slug' => 'talleres-dashboard', 'label' => __('Talleres', FLAVOR_PLATFORM_TEXT_DOMAIN), 'icon' => 'dashicons-hammer'],
                    ['slug' => 'reservas-dashboard', 'label' => __('Reservas', FLAVOR_PLATFORM_TEXT_DOMAIN), 'icon' => 'dashicons-tickets-alt'],
                ],
            ],
            'mod_servicios' => [
                'label' => __('Servicios', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'icon' => 'dashicons-admin-tools',
                'items' => [
                    ['slug' => 'tramites-dashboard', 'label' => __('Trámites', FLAVOR_PLATFORM_TEXT_DOMAIN), 'icon' => 'dashicons-clipboard'],
                    ['slug' => 'incidencias-dashboard', 'label' => __('Incidencias', FLAVOR_PLATFORM_TEXT_DOMAIN), 'icon' => 'dashicons-warning'],
                    ['slug' => 'ayuda-dashboard', 'label' => __('Ayuda Vecinal', FLAVOR_PLATFORM_TEXT_DOMAIN), 'icon' => 'dashicons-sos'],
                    ['slug' => 'participacion-dashboard', 'label' => __('Participación', FLAVOR_PLATFORM_TEXT_DOMAIN), 'icon' => 'dashicons-megaphone'],
                    ['slug' => 'transparencia-dashboard', 'label' => __('Transparencia', FLAVOR_PLATFORM_TEXT_DOMAIN), 'icon' => 'dashicons-visibility'],
                    ['slug' => 'avisos-dashboard', 'label' => __('Avisos', FLAVOR_PLATFORM_TEXT_DOMAIN), 'icon' => 'dashicons-megaphone'],
                    ['slug' => 'denuncias-dashboard', 'label' => __('Denuncias', FLAVOR_PLATFORM_TEXT_DOMAIN), 'icon' => 'dashicons-flag'],
                    ['slug' => 'documentos-dashboard', 'label' => __('Documentación', FLAVOR_PLATFORM_TEXT_DOMAIN), 'icon' => 'dashicons-media-document'],
                ],
            ],
            'mod_recursos' => [
                'label' => __('Recursos', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'icon' => 'dashicons-building',
                'items' => [
                    ['slug' => 'huertos-dashboard', 'label' => __('Huertos', FLAVOR_PLATFORM_TEXT_DOMAIN), 'icon' => 'dashicons-carrot'],
                    ['slug' => 'espacios-dashboard', 'label' => __('Espacios', FLAVOR_PLATFORM_TEXT_DOMAIN), 'icon' => 'dashicons-building'],
                    ['slug' => 'biblioteca-dashboard', 'label' => __('Biblioteca', FLAVOR_PLATFORM_TEXT_DOMAIN), 'icon' => 'dashicons-book-alt'],
                    ['slug' => 'carpooling-dashboard', 'label' => __('Carpooling', FLAVOR_PLATFORM_TEXT_DOMAIN), 'icon' => 'dashicons-car'],
                    ['slug' => 'parkings-dashboard', 'label' => __('Parkings', FLAVOR_PLATFORM_TEXT_DOMAIN), 'icon' => 'dashicons-location'],
                    ['slug' => 'actores-dashboard', 'label' => __('Mapa Actores', FLAVOR_PLATFORM_TEXT_DOMAIN), 'icon' => 'dashicons-location-alt'],
                    ['slug' => 'bares-dashboard', 'label' => __('Bares', FLAVOR_PLATFORM_TEXT_DOMAIN), 'icon' => 'dashicons-store'],
                    ['slug' => 'recetas-dashboard', 'label' => __('Recetas', FLAVOR_PLATFORM_TEXT_DOMAIN), 'icon' => 'dashicons-carrot'],
                ],
            ],
            'mod_sostenibilidad' => [
                'label' => __('Sostenibilidad', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'icon' => 'dashicons-palmtree',
                'items' => [
                    ['slug' => 'reciclaje-dashboard', 'label' => __('Reciclaje', FLAVOR_PLATFORM_TEXT_DOMAIN), 'icon' => 'dashicons-image-rotate'],
                    ['slug' => 'compostaje-dashboard', 'label' => __('Compostaje', FLAVOR_PLATFORM_TEXT_DOMAIN), 'icon' => 'dashicons-carrot'],
                    ['slug' => 'flavor-energia-dashboard', 'label' => __('Energía', FLAVOR_PLATFORM_TEXT_DOMAIN), 'icon' => 'dashicons-lightbulb'],
                    ['slug' => 'bicicletas-dashboard', 'label' => __('Bicicletas', FLAVOR_PLATFORM_TEXT_DOMAIN), 'icon' => 'dashicons-performance'],
                    ['slug' => 'biodiversidad-dashboard', 'label' => __('Biodiversidad', FLAVOR_PLATFORM_TEXT_DOMAIN), 'icon' => 'dashicons-palmtree'],
                    ['slug' => 'huella-ecologica-dashboard', 'label' => __('Huella Ecológica', FLAVOR_PLATFORM_TEXT_DOMAIN), 'icon' => 'dashicons-chart-line'],
                    ['slug' => 'saberes-dashboard', 'label' => __('Saberes', FLAVOR_PLATFORM_TEXT_DOMAIN), 'icon' => 'dashicons-welcome-learn-more'],
                    ['slug' => 'suficiencia-dashboard', 'label' => __('Suficiencia', FLAVOR_PLATFORM_TEXT_DOMAIN), 'icon' => 'dashicons-heart'],
                    ['slug' => 'circulos-cuidados-dashboard', 'label' => __('Círculos Cuidados', FLAVOR_PLATFORM_TEXT_DOMAIN), 'icon' => 'dashicons-groups'],
                    ['slug' => 'trabajo-digno-dashboard', 'label' => __('Trabajo Digno', FLAVOR_PLATFORM_TEXT_DOMAIN), 'icon' => 'dashicons-businessman'],
                    ['slug' => 'justicia-restaurativa-dashboard', 'label' => __('Justicia Rest.', FLAVOR_PLATFORM_TEXT_DOMAIN), 'icon' => 'dashicons-shield'],
                ],
            ],
            'mod_comunicacion' => [
                'label' => __('Comunicación', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'icon' => 'dashicons-megaphone',
                'items' => [
                    ['slug' => 'multimedia-dashboard', 'label' => __('Multimedia', FLAVOR_PLATFORM_TEXT_DOMAIN), 'icon' => 'dashicons-format-video'],
                    ['slug' => 'flavor-radio-dashboard', 'label' => __('Radio', FLAVOR_PLATFORM_TEXT_DOMAIN), 'icon' => 'dashicons-controls-volumeon'],
                    ['slug' => 'podcast-dashboard', 'label' => __('Podcast', FLAVOR_PLATFORM_TEXT_DOMAIN), 'icon' => 'dashicons-microphone'],
                    ['slug' => 'campanias-dashboard', 'label' => __('Campañas', FLAVOR_PLATFORM_TEXT_DOMAIN), 'icon' => 'dashicons-email-alt'],
                    ['slug' => 'email-marketing-dashboard', 'label' => __('Email Marketing', FLAVOR_PLATFORM_TEXT_DOMAIN), 'icon' => 'dashicons-email'],
                    ['slug' => 'encuestas-dashboard', 'label' => __('Encuestas', FLAVOR_PLATFORM_TEXT_DOMAIN), 'icon' => 'dashicons-forms'],
                ],
            ],
            'mod_chat' => [
                'label' => __('Chat', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'icon' => 'dashicons-format-chat',
                'items' => [
                    ['slug' => 'chat-grupos-dashboard', 'label' => __('Grupos', FLAVOR_PLATFORM_TEXT_DOMAIN), 'icon' => 'dashicons-groups'],
                    ['slug' => 'chat-interno-dashboard', 'label' => __('Chat Interno', FLAVOR_PLATFORM_TEXT_DOMAIN), 'icon' => 'dashicons-testimonial'],
                ],
            ],
            'chat_ia' => [
                'label' => __('Asistente IA', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'icon' => 'dashicons-admin-comments',
                'items' => [
                    ['slug' => 'flavor-platform-settings', 'label' => __('Configuración', FLAVOR_PLATFORM_TEXT_DOMAIN), 'icon' => 'dashicons-admin-settings'],
                    ['slug' => 'flavor-platform-escalations', 'label' => __('Escalados', FLAVOR_PLATFORM_TEXT_DOMAIN), 'icon' => 'dashicons-warning'],
                ],
            ],
            'apps' => [
                'label' => __('Apps', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'icon' => 'dashicons-smartphone',
                'items' => [
                    ['slug' => 'flavor-platform-apps', 'label' => __('Apps Móviles', FLAVOR_PLATFORM_TEXT_DOMAIN), 'icon' => 'dashicons-smartphone'],
                    ['slug' => 'flavor-app-generator', 'label' => __('Generador Apps', FLAVOR_PLATFORM_TEXT_DOMAIN), 'icon' => 'dashicons-admin-customizer'],
                    ['slug' => 'flavor-platform-deep-links', 'label' => __('Deep Links', FLAVOR_PLATFORM_TEXT_DOMAIN), 'icon' => 'dashicons-admin-links'],
                    ['slug' => 'flavor-platform-network', 'label' => __('Red', FLAVOR_PLATFORM_TEXT_DOMAIN), 'icon' => 'dashicons-networking'],
                ],
            ],
            'extensiones' => [
                'label' => __('Extensiones', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'icon' => 'dashicons-plugins-checked',
                'items' => [
                    ['slug' => 'flavor-addons', 'label' => __('Addons', FLAVOR_PLATFORM_TEXT_DOMAIN), 'icon' => 'dashicons-admin-plugins'],
                    ['slug' => 'flavor-marketplace', 'label' => __('Marketplace', FLAVOR_PLATFORM_TEXT_DOMAIN), 'icon' => 'dashicons-store'],
                    ['slug' => 'flavor-newsletter', 'label' => __('Newsletter', FLAVOR_PLATFORM_TEXT_DOMAIN), 'icon' => 'dashicons-email'],
                    ['slug' => 'flavor-platform-license', 'label' => __('Licencia', FLAVOR_PLATFORM_TEXT_DOMAIN), 'icon' => 'dashicons-admin-network'],
                ],
            ],
            'herramientas' => [
                'label' => __('Herramientas', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'icon' => 'dashicons-admin-tools',
                'items' => [
                    ['slug' => 'flavor-ai-tools', 'label' => __('Herramientas IA', FLAVOR_PLATFORM_TEXT_DOMAIN), 'icon' => 'dashicons-lightbulb'],
                    ['slug' => 'flavor-platform-demo-data', 'label' => __('Datos Demo', FLAVOR_PLATFORM_TEXT_DOMAIN), 'icon' => 'dashicons-database-import'],
                    ['slug' => 'flavor-platform-export-import', 'label' => __('Export/Import', FLAVOR_PLATFORM_TEXT_DOMAIN), 'icon' => 'dashicons-migrate'],
                    ['slug' => 'flavor-platform-health-check', 'label' => __('Diagnóstico', FLAVOR_PLATFORM_TEXT_DOMAIN), 'icon' => 'dashicons-heart'],
                    ['slug' => 'flavor-platform-activity-log', 'label' => __('Actividad', FLAVOR_PLATFORM_TEXT_DOMAIN), 'icon' => 'dashicons-backup'],
                    ['slug' => 'flavor-analytics', 'label' => __('Analytics', FLAVOR_PLATFORM_TEXT_DOMAIN), 'icon' => 'dashicons-chart-area'],
                    ['slug' => 'flavor-api-docs', 'label' => __('API Docs', FLAVOR_PLATFORM_TEXT_DOMAIN), 'icon' => 'dashicons-rest-api'],
                    ['slug' => 'flavor-systems-panel', 'label' => __('Sistemas V3', FLAVOR_PLATFORM_TEXT_DOMAIN), 'icon' => 'dashicons-admin-generic'],
                    [
                        'slug' => 'flavor-platform-apps',
                        'label' => __('Datos Demo en Apps', FLAVOR_PLATFORM_TEXT_DOMAIN),
                        'icon' => 'dashicons-smartphone',
                        'url' => 'admin.php?page=flavor-platform-apps&tab=tools',
                    ],
                ],
            ],
            'administracion' => [
                'label' => __('Administración', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'icon' => 'dashicons-admin-settings',
                'items' => [
                    ['slug' => 'flavor-moderation', 'label' => __('Moderación', FLAVOR_PLATFORM_TEXT_DOMAIN), 'icon' => 'dashicons-shield'],
                    ['slug' => 'advertising-dashboard', 'label' => __('Anuncios', FLAVOR_PLATFORM_TEXT_DOMAIN), 'icon' => 'dashicons-megaphone'],
                    ['slug' => 'flavor-shell-views', 'label' => __('Vistas Shell', FLAVOR_PLATFORM_TEXT_DOMAIN), 'icon' => 'dashicons-visibility'],
                    ['slug' => 'flavor-integraciones-posts', 'label' => __('Integraciones Posts', FLAVOR_PLATFORM_TEXT_DOMAIN), 'icon' => 'dashicons-admin-post'],
                    ['slug' => 'flavor-integraciones-config', 'label' => __('Config. Integraciones', FLAVOR_PLATFORM_TEXT_DOMAIN), 'icon' => 'dashicons-admin-settings'],
                ],
            ],
            'ayuda' => [
                'label' => __('Ayuda', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'icon' => 'dashicons-editor-help',
                'items' => [
                    ['slug' => 'flavor-platform-docs', 'label' => __('Documentación', FLAVOR_PLATFORM_TEXT_DOMAIN), 'icon' => 'dashicons-book'],
                    ['slug' => 'flavor-tours', 'label' => __('Tours', FLAVOR_PLATFORM_TEXT_DOMAIN), 'icon' => 'dashicons-location'],
                ],
            ],
        ];

        // Obtener el menu manager para filtrar según vista
        if (!class_exists('Flavor_Admin_Menu_Manager')) {
            return $this->inject_subpages_and_badges($estructura_completa);
        }

        $menu_manager = Flavor_Admin_Menu_Manager::get_instance();
        $estructura_filtrada = [];

        foreach ($estructura_completa as $seccion_id => $seccion) {
            $items_filtrados = [];

            foreach ($seccion['items'] as $item) {
                // Verificar si el menú es visible en la vista actual
                if ($menu_manager->menu_visible_en_vista($item['slug'])) {
                    $items_filtrados[] = $item;
                }
            }

            // Solo incluir sección si tiene items visibles
            if (!empty($items_filtrados)) {
                $estructura_filtrada[$seccion_id] = [
                    'label' => $seccion['label'],
                    'icon' => $seccion['icon'],
                    'items' => $items_filtrados,
                ];
            }
        }

        // Inyectar subpáginas y badges
        return $this->inject_subpages_and_badges($estructura_filtrada);
    }

    /**
     * Detectar si estamos en un módulo con subpáginas
     *
     * @return void
     */
    private function detect_active_module() {
        if (!class_exists('Flavor_Shell_Navigation_Registry')) {
            return;
        }

        $registry = Flavor_Shell_Navigation_Registry::get_instance();
        $this->active_parent_dashboard = $registry->get_parent_dashboard($this->current_page);
    }

    /**
     * Inyectar subpáginas y badges en la estructura de navegación
     *
     * @param array $estructura Estructura de navegación
     * @return array Estructura con subpáginas y badges
     */
    private function inject_subpages_and_badges(array $estructura) {
        if (!class_exists('Flavor_Shell_Navigation_Registry')) {
            return $estructura;
        }

        $registry = Flavor_Shell_Navigation_Registry::get_instance();

        foreach ($estructura as $seccion_id => &$seccion) {
            foreach ($seccion['items'] as &$item) {
                $item_slug = $item['slug'];

                // Inyectar badge agregado
                $badge = $registry->get_aggregated_badge($item_slug);
                if ($badge) {
                    $item['badge'] = $badge;
                }

                // Si este item es el dashboard padre activo, inyectar subpáginas
                if ($this->active_parent_dashboard === $item_slug) {
                    $subpages = $registry->get_module_subpages($item_slug);

                    if (!empty($subpages)) {
                        // Añadir badges a cada subpágina
                        foreach ($subpages as &$subpage) {
                            $sub_badge = $registry->get_badge($subpage['slug']);
                            if ($sub_badge) {
                                $subpage['badge'] = $sub_badge;
                            }
                        }
                        $item['subpages'] = $subpages;
                        $item['is_expanded'] = true;
                    }
                }
            }
        }

        return $estructura;
    }

    /**
     * Verificar si estamos en una subpágina de un módulo
     *
     * @param string $subpage_slug Slug de la subpágina
     * @return bool
     */
    public function is_subpage_active($subpage_slug) {
        return $this->current_page === $subpage_slug;
    }

    /**
     * Obtener el dashboard padre activo
     *
     * @return string|null
     */
    public function get_active_parent_dashboard() {
        return $this->active_parent_dashboard;
    }

    /**
     * Obtener tooltip descriptivo para un badge
     *
     * @param string $slug  Slug de la página
     * @param array  $badge Datos del badge
     * @return string|null
     */
    public static function get_badge_tooltip($slug, $badge) {
        $tooltips = [
            // Eventos
            'eventos-dashboard' => __('eventos esta semana', FLAVOR_PLATFORM_TEXT_DOMAIN),
            'eventos-asistentes' => __('inscripciones pendientes', FLAVOR_PLATFORM_TEXT_DOMAIN),
            // Trámites
            'tramites-dashboard' => __('solicitudes en curso', FLAVOR_PLATFORM_TEXT_DOMAIN),
            'tramites-pendientes' => __('urgentes sin cerrar', FLAVOR_PLATFORM_TEXT_DOMAIN),
            // Incidencias
            'incidencias-dashboard' => __('incidencias abiertas', FLAVOR_PLATFORM_TEXT_DOMAIN),
            'incidencias-abiertas' => __('sin asignar', FLAVOR_PLATFORM_TEXT_DOMAIN),
            // Socios
            'socios-solicitudes' => __('solicitudes de alta', FLAVOR_PLATFORM_TEXT_DOMAIN),
            // Marketplace
            'marketplace-anuncios' => __('pendientes de aprobar', FLAVOR_PLATFORM_TEXT_DOMAIN),
            // Reservas
            'reservas-pendientes' => __('reservas pendientes', FLAVOR_PLATFORM_TEXT_DOMAIN),
            // Foros
            'foros-temas' => __('sin respuesta', FLAVOR_PLATFORM_TEXT_DOMAIN),
            // Participación
            'participacion-votaciones' => __('en votación activa', FLAVOR_PLATFORM_TEXT_DOMAIN),
            // Huertos
            'huertos-parcelas' => __('solicitudes pendientes', FLAVOR_PLATFORM_TEXT_DOMAIN),
            // Colectivos
            'colectivos-solicitudes' => __('solicitudes de unión', FLAVOR_PLATFORM_TEXT_DOMAIN),
            // Banco de Tiempo
            'banco-tiempo-intercambios' => __('intercambios pendientes', FLAVOR_PLATFORM_TEXT_DOMAIN),
            // Biblioteca
            'biblioteca-prestamos' => __('préstamos vencidos', FLAVOR_PLATFORM_TEXT_DOMAIN),
            // Chat IA
            'flavor-platform-escalations' => __('escalados sin atender', FLAVOR_PLATFORM_TEXT_DOMAIN),
        ];

        if (isset($tooltips[$slug])) {
            return $badge['count'] . ' ' . $tooltips[$slug];
        }

        return null;
    }

    /**
     * Verifica si el modo oscuro está activo
     *
     * @return bool
     */
    public function is_dark_mode() {
        // Verificar si el tema activo es oscuro
        $active_theme = get_option('flavor_active_theme', 'default');
        $dark_themes = ['dark-mode', 'themacle-dark', 'campi'];

        return in_array($active_theme, $dark_themes, true);
    }

    /**
     * AJAX handler para toggle del shell
     */
    public function ajax_toggle_shell() {
        check_ajax_referer('flavor_admin_shell', 'nonce');

        $user_id = get_current_user_id();
        if (!$user_id) {
            wp_send_json_error(['message' => __('Usuario no autenticado', FLAVOR_PLATFORM_TEXT_DOMAIN)]);
        }

        $action = isset($_POST['shell_action']) ? sanitize_text_field($_POST['shell_action']) : '';

        if ($action === 'disable') {
            update_user_meta($user_id, self::USER_META_KEY, '1');
            wp_send_json_success(['message' => __('Shell desactivado', FLAVOR_PLATFORM_TEXT_DOMAIN)]);
        } elseif ($action === 'enable') {
            delete_user_meta($user_id, self::USER_META_KEY);
            wp_send_json_success(['message' => __('Shell activado', FLAVOR_PLATFORM_TEXT_DOMAIN)]);
        }

        wp_send_json_error(['message' => __('Acción no válida', FLAVOR_PLATFORM_TEXT_DOMAIN)]);
    }
}

// Inicializar
Flavor_Admin_Shell::get_instance();

<?php
/**
 * Flavor Admin Shell - Sistema de navegación admin elegante
 *
 * Reemplaza el sidebar de WordPress con una interfaz moderna y elegante
 * cuando el usuario está en páginas del plugin Flavor.
 *
 * @package FlavorPlatform
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
     * Obtener sufijo para assets según modo debug
     *
     * @return string '.min' en producción, '' en desarrollo
     */
    private function get_asset_suffix() {
        return (defined('SCRIPT_DEBUG') && SCRIPT_DEBUG) ? '' : '.min';
    }

    /**
     * Mapeo de slugs de dashboard a módulos requeridos
     * Si el módulo no está activo, el item no se muestra
     */
    const SLUG_TO_MODULE = [
        // Comunidad
        'socios-dashboard'              => 'socios',
        'flavor-colectivos-dashboard'   => 'colectivos',
        'comunidades-dashboard'         => 'comunidades',
        'foros-dashboard'               => 'foros',
        'flavor-red-social-dashboard'   => 'red-social',
        // Economía
        'gc-dashboard'                  => 'grupos-consumo',
        'marketplace-dashboard'         => 'marketplace',
        'banco-tiempo-dashboard'        => 'banco-tiempo',
        'contabilidad-dashboard'        => 'contabilidad',
        'economia-don-dashboard'        => 'economia-don',
        // Empresarial
        'empresas-dashboard'            => 'empresas',
        'clientes-dashboard'            => 'clientes',
        'facturas-dashboard'            => 'facturas',
        'presupuestos-dashboard'        => 'presupuestos-clientes',
        'fichaje-dashboard'             => 'fichaje',
        'flavor-woocommerce-dashboard'  => 'woocommerce',
        'flavor-crowdfunding'           => 'crowdfunding',
        'themacle-dashboard'            => 'themacle',
        'trading-ia-dashboard'          => 'trading-ia',
        'dex-solana-dashboard'          => 'dex-solana',
        // Actividades
        'eventos-dashboard'             => 'eventos',
        'cursos-dashboard'              => 'cursos',
        'talleres-dashboard'            => 'talleres',
        'reservas-dashboard'            => 'reservas',
        // Servicios
        'tramites-dashboard'            => 'tramites',
        'incidencias-dashboard'         => 'incidencias',
        'ayuda-dashboard'               => 'ayuda-vecinal',
        'participacion-dashboard'       => 'participacion',
        'transparencia-dashboard'       => 'transparencia',
        'avisos-dashboard'              => 'avisos-municipales',
        'denuncias-dashboard'           => 'denuncias',
        'documentos-dashboard'          => 'documentacion-legal',
        // Recursos
        'huertos-dashboard'             => 'huertos-urbanos',
        'espacios-dashboard'            => 'espacios-comunes',
        'biblioteca-dashboard'          => 'biblioteca',
        'carpooling-dashboard'          => 'carpooling',
        'parkings-dashboard'            => 'parkings',
        'actores-dashboard'             => 'mapa-actores',
        'bares-dashboard'               => 'bares',
        'recetas-dashboard'             => 'recetas',
        // Sostenibilidad
        'reciclaje-dashboard'           => 'reciclaje',
        'compostaje-dashboard'          => 'compostaje',
        'flavor-energia-dashboard'      => 'energia',
        'bicicletas-dashboard'          => 'bicicletas-compartidas',
        'biodiversidad-dashboard'       => 'biodiversidad',
        'huella-ecologica-dashboard'    => 'huella-ecologica',
        'saberes-dashboard'             => 'saberes',
        'suficiencia-dashboard'         => 'suficiencia',
        'circulos-cuidados-dashboard'   => 'circulos-cuidados',
        'trabajo-digno-dashboard'       => 'trabajo-digno',
        'justicia-restaurativa-dashboard' => 'justicia-restaurativa',
        // Comunicación
        'multimedia-dashboard'          => 'multimedia',
        'flavor-radio-dashboard'        => 'radio',
        'podcast-dashboard'             => 'podcast',
        'campanias-dashboard'           => 'campanias',
        'email-marketing-dashboard'     => 'email-marketing',
        'encuestas-dashboard'           => 'encuestas',
        // Chat
        'chat-grupos-dashboard'         => 'chat-grupos',
        'chat-interno-dashboard'        => 'chat-interno',
        // Publicidad
        'advertising-dashboard'         => 'advertising',
    ];

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
        add_action('admin_head', [$this, 'inject_critical_css'], 1);
        add_action('admin_enqueue_scripts', [$this, 'enqueue_assets'], 5);
        add_action('admin_footer', [$this, 'render_shell'], 5);
        add_action('wp_ajax_flavor_toggle_admin_shell', [$this, 'ajax_toggle_shell']);

        // Detectar navegación AJAX y modificar output
        add_action('admin_init', [$this, 'handle_ajax_navigation'], 1);

        // Endpoints AJAX para lazy loading
        add_action('wp_ajax_flavor_shell_get_subpages', [$this, 'ajax_get_subpages']);
        add_action('wp_ajax_flavor_shell_get_badges_lazy', [$this, 'ajax_get_badges_lazy']);

        // Optimización: Deshabilitar scripts de Gutenberg innecesarios en páginas Flavor
        add_action('admin_enqueue_scripts', [$this, 'dequeue_unnecessary_scripts'], 999);
    }

    /**
     * Inicialización
     */
    public function init() {
        // Nada adicional por ahora
    }

    /**
     * Inyectar CSS crítico inline para prevenir FOUC
     *
     * Este CSS se carga inmediatamente en el head antes que los CSS externos,
     * asegurando que la página tenga estilos básicos mientras carga el resto.
     */
    public function inject_critical_css() {
        if (!$this->should_show_shell()) {
            return;
        }
        ?>
        <style id="flavor-critical-css">
            /* CSS Crítico para prevenir FOUC en Flavor Admin Shell */

            /* Ocultar menú original de WordPress */
            #adminmenuwrap, #adminmenuback, #adminmenumain {
                display: none !important;
            }

            /* Ajustar contenido principal */
            #wpcontent, #wpbody {
                margin-left: 60px !important;
            }

            #wpbody-content {
                padding: 20px;
                background: #f8fafc;
                min-height: 100vh;
                box-sizing: border-box;
            }

            /* Estilos base para contenido mientras carga */
            .wrap {
                max-width: 1400px;
                margin: 0 auto;
            }

            /* Loading state - ocultar contenido brevemente */
            .flavor-settings-hub,
            [class*="flavor-"][class*="dashboard"],
            .flavor-unified-dashboard {
                opacity: 0;
                animation: flavorFadeIn 0.3s ease-out 0.1s forwards;
            }

            @keyframes flavorFadeIn {
                to { opacity: 1; }
            }

            /* Sidebar placeholder */
            body.fls-shell-active::before {
                content: '';
                position: fixed;
                left: 0;
                top: 32px;
                width: 60px;
                height: calc(100vh - 32px);
                background: linear-gradient(180deg, #1e1b4b 0%, #312e81 100%);
                z-index: 9999;
            }
        </style>
        <?php
    }

    /**
     * Manejar peticiones de navegación AJAX
     *
     * Si detectamos el header X-Flavor-Ajax-Navigation, capturamos
     * solo el contenido de #wpbody-content y lo devolvemos.
     */
    public function handle_ajax_navigation() {
        // Verificar header de navegación AJAX
        $is_ajax_nav = isset($_SERVER['HTTP_X_FLAVOR_AJAX_NAVIGATION'])
            && $_SERVER['HTTP_X_FLAVOR_AJAX_NAVIGATION'] === '1';

        if (!$is_ajax_nav) {
            return;
        }

        // Verificar que estamos en una página Flavor
        if (!$this->is_flavor_page()) {
            return;
        }

        // Iniciar buffer para capturar output
        ob_start();

        // Hook para capturar después de que se renderice el contenido
        add_action('admin_footer', [$this, 'capture_ajax_content'], 999);
    }

    /**
     * Capturar y devolver solo el contenido para navegación AJAX
     */
    public function capture_ajax_content() {
        $full_output = ob_get_clean();

        // Extraer contenido de #wpbody-content
        $content = $this->extract_wpbody_content($full_output);

        // Enviar headers
        header('X-Flavor-Ajax-Response: 1');
        header('Content-Type: text/html; charset=utf-8');

        // Devolver solo el contenido
        echo $content;
        exit;
    }

    /**
     * Extraer contenido de #wpbody-content del HTML completo
     *
     * @param string $html HTML completo de la página
     * @return string Contenido extraído
     */
    private function extract_wpbody_content($html) {
        // Usar DOMDocument para extraer el contenido
        $dom = new DOMDocument();

        // Suprimir warnings de HTML5
        libxml_use_internal_errors(true);
        $dom->loadHTML('<?xml encoding="UTF-8">' . $html, LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD);
        libxml_clear_errors();

        $xpath = new DOMXPath($dom);

        // Buscar #wpbody-content
        $content_node = $xpath->query("//*[@id='wpbody-content']")->item(0);

        if (!$content_node) {
            // Fallback: devolver todo el body
            $body = $xpath->query("//body")->item(0);
            if ($body) {
                return $dom->saveHTML($body);
            }
            return $html;
        }

        // Extraer innerHTML de wpbody-content
        $inner_html = '';
        foreach ($content_node->childNodes as $child) {
            $inner_html .= $dom->saveHTML($child);
        }

        return $inner_html;
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

        $suffix = $this->get_asset_suffix();

        // CSS del shell (minificado en producción)
        $shell_css_file = "admin-shell{$suffix}.css";
        $shell_css_path = FLAVOR_PLATFORM_PATH . 'admin/css/' . $shell_css_file;
        $shell_css_ver = file_exists($shell_css_path) ? (string) filemtime($shell_css_path) : FLAVOR_PLATFORM_VERSION;
        wp_enqueue_style(
            'flavor-admin-shell',
            FLAVOR_PLATFORM_URL . 'admin/css/' . $shell_css_file,
            [],
            $shell_css_ver
        );

        // JS del shell (minificado en producción)
        // IMPORTANTE: Debe cargarse ANTES de Alpine para que el listener
        // de 'alpine:init' esté registrado cuando Alpine auto-inicialice.
        $shell_js_file = "admin-shell{$suffix}.js";
        $shell_js_path = FLAVOR_PLATFORM_PATH . 'admin/js/' . $shell_js_file;
        $shell_js_ver = file_exists($shell_js_path) ? (string) filemtime($shell_js_path) : FLAVOR_PLATFORM_VERSION;
        wp_enqueue_script(
            'flavor-admin-shell',
            FLAVOR_PLATFORM_URL . 'admin/js/' . $shell_js_file,
            [],
            $shell_js_ver,
            true
        );

        // Alpine.js - Carga DESPUÉS de admin-shell.js
        // Al depender de 'flavor-admin-shell', Alpine se ejecutará después,
        // encontrando los componentes ya registrados vía 'alpine:init'.
        if (!wp_script_is('alpine', 'enqueued')) {
            wp_enqueue_script(
                'alpine',
                FLAVOR_PLATFORM_URL . 'assets/vbp/vendor/alpine.min.js',
                ['flavor-admin-shell'],
                '3.14.3',
                true
            );
        }

        // Añadir defer solo a ajax-navigation (Alpine y admin-shell NO pueden tener defer)
        add_filter('script_loader_tag', [$this, 'add_defer_attribute'], 10, 2);

        // JS de navegación AJAX (carga después de Alpine)
        // Orden de carga: admin-shell → alpine → ajax-navigation
        $ajax_nav_path = FLAVOR_PLATFORM_PATH . 'admin/js/ajax-navigation.js';
        $ajax_nav_ver = file_exists($ajax_nav_path) ? (string) filemtime($ajax_nav_path) : FLAVOR_PLATFORM_VERSION;
        wp_enqueue_script(
            'flavor-ajax-navigation',
            FLAVOR_PLATFORM_URL . 'admin/js/ajax-navigation.js',
            ['alpine'],
            $ajax_nav_ver,
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
     * Deshabilitar scripts de Gutenberg/Block Editor innecesarios en páginas Flavor
     *
     * Estos scripts (React, Block Editor, etc.) no se usan en el admin shell
     * y añaden ~46 requests extra con un peso significativo.
     */
    public function dequeue_unnecessary_scripts() {
        if (!$this->should_show_shell()) {
            return;
        }

        // Scripts de Gutenberg/Block Editor que no necesitamos
        $unnecessary_scripts = [
            // React y amigos
            'react',
            'react-dom',
            'react-jsx-runtime',

            // Block Editor
            'wp-block-editor',
            'wp-blocks',
            'wp-block-library',
            'wp-block-serialization-default-parser',

            // Gutenberg core
            'wp-editor',
            'wp-edit-post',
            'wp-element',
            'wp-components',
            'wp-compose',
            'wp-data',
            'wp-rich-text',
            'wp-primitives',

            // Data stores
            'wp-core-data',
            'wp-core-commands',
            'wp-commands',

            // Otros no necesarios
            'wp-preferences',
            'wp-preferences-persistence',
            'wp-keyboard-shortcuts',
            'wp-router',
            'wp-notices',
            'wp-style-engine',
            'wp-autop',
            'wp-blob',
            'wp-shortcode',
            'wp-token-list',
        ];

        foreach ($unnecessary_scripts as $handle) {
            wp_dequeue_script($handle);
            wp_deregister_script($handle);
        }

        // También deshabilitar estilos de Gutenberg
        $unnecessary_styles = [
            'wp-block-editor',
            'wp-block-library',
            'wp-components',
            'wp-edit-post',
            'wp-editor',
        ];

        foreach ($unnecessary_styles as $handle) {
            wp_dequeue_style($handle);
            wp_deregister_style($handle);
        }
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

        include FLAVOR_PLATFORM_PATH . 'admin/views/shell-sidebar.php';
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

        // Estructura consolidada de navegación (8 secciones)
        // Reducida de 16 a 8 secciones para mejor UX
        $estructura_completa = [
            // =================================================================
            // 1. MI APP - Dashboard principal y configuración general
            // =================================================================
            'mi_app' => [
                'label' => __('Mi App', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'icon' => 'dashicons-admin-home',
                'items' => [
                    ['slug' => 'flavor-dashboard', 'label' => __('Dashboard', FLAVOR_PLATFORM_TEXT_DOMAIN), 'icon' => 'dashicons-dashboard'],
                    ['slug' => 'flavor-settings-hub', 'label' => __('Configuración', FLAVOR_PLATFORM_TEXT_DOMAIN), 'icon' => 'dashicons-admin-settings'],
                    ['slug' => 'flavor-unified-dashboard', 'label' => __('Widgets', FLAVOR_PLATFORM_TEXT_DOMAIN), 'icon' => 'dashicons-grid-view'],
                    ['slug' => 'flavor-app-composer', 'label' => __('Módulos', FLAVOR_PLATFORM_TEXT_DOMAIN), 'icon' => 'dashicons-admin-generic'],
                    ['slug' => 'flavor-design-settings', 'label' => __('Diseño', FLAVOR_PLATFORM_TEXT_DOMAIN), 'icon' => 'dashicons-art'],
                    ['slug' => 'flavor-landings', 'url' => 'edit.php?post_type=flavor_landing', 'label' => __('Editor Visual', FLAVOR_PLATFORM_TEXT_DOMAIN), 'icon' => 'dashicons-edit'],
                ],
            ],

            // =================================================================
            // 2. COMUNIDAD - Personas, grupos y comunicación social
            // =================================================================
            'comunidad' => [
                'label' => __('Comunidad', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'icon' => 'dashicons-groups',
                'items' => [
                    // Miembros y grupos
                    ['slug' => 'socios-dashboard', 'label' => __('Miembros', FLAVOR_PLATFORM_TEXT_DOMAIN), 'icon' => 'dashicons-id-alt'],
                    ['slug' => 'flavor-colectivos-dashboard', 'label' => __('Colectivos', FLAVOR_PLATFORM_TEXT_DOMAIN), 'icon' => 'dashicons-networking'],
                    ['slug' => 'comunidades-dashboard', 'label' => __('Comunidades', FLAVOR_PLATFORM_TEXT_DOMAIN), 'icon' => 'dashicons-admin-multisite'],
                    // Interacción social
                    ['slug' => 'foros-dashboard', 'label' => __('Foros', FLAVOR_PLATFORM_TEXT_DOMAIN), 'icon' => 'dashicons-format-chat'],
                    ['slug' => 'flavor-red-social-dashboard', 'label' => __('Red Social', FLAVOR_PLATFORM_TEXT_DOMAIN), 'icon' => 'dashicons-share'],
                    // Chat (fusionado desde mod_chat)
                    ['slug' => 'chat-grupos-dashboard', 'label' => __('Chat Grupos', FLAVOR_PLATFORM_TEXT_DOMAIN), 'icon' => 'dashicons-groups'],
                    ['slug' => 'chat-interno-dashboard', 'label' => __('Chat Interno', FLAVOR_PLATFORM_TEXT_DOMAIN), 'icon' => 'dashicons-testimonial'],
                ],
            ],

            // =================================================================
            // 3. ECONOMÍA - Marketplace, empresas, finanzas
            // (Fusiona mod_economia + mod_empresarial)
            // =================================================================
            'economia' => [
                'label' => __('Economía', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'icon' => 'dashicons-money-alt',
                'items' => [
                    // Economía social
                    ['slug' => 'gc-dashboard', 'label' => __('Grupos Consumo', FLAVOR_PLATFORM_TEXT_DOMAIN), 'icon' => 'dashicons-cart'],
                    ['slug' => 'marketplace-dashboard', 'label' => __('Marketplace', FLAVOR_PLATFORM_TEXT_DOMAIN), 'icon' => 'dashicons-store'],
                    ['slug' => 'banco-tiempo-dashboard', 'label' => __('Banco Tiempo', FLAVOR_PLATFORM_TEXT_DOMAIN), 'icon' => 'dashicons-clock'],
                    ['slug' => 'economia-don-dashboard', 'label' => __('Economía Don', FLAVOR_PLATFORM_TEXT_DOMAIN), 'icon' => 'dashicons-heart'],
                    // Empresarial
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
                    ['slug' => 'contabilidad-dashboard', 'label' => __('Contabilidad', FLAVOR_PLATFORM_TEXT_DOMAIN), 'icon' => 'dashicons-chart-pie'],
                    // Integraciones comerciales
                    ['slug' => 'flavor-woocommerce-dashboard', 'label' => __('WooCommerce', FLAVOR_PLATFORM_TEXT_DOMAIN), 'icon' => 'dashicons-cart'],
                    ['slug' => 'flavor-crowdfunding', 'label' => __('Crowdfunding', FLAVOR_PLATFORM_TEXT_DOMAIN), 'icon' => 'dashicons-money-alt'],
                    // Avanzado
                    ['slug' => 'themacle-dashboard', 'label' => __('Themacle', FLAVOR_PLATFORM_TEXT_DOMAIN), 'icon' => 'dashicons-art'],
                    ['slug' => 'trading-ia-dashboard', 'label' => __('Trading IA', FLAVOR_PLATFORM_TEXT_DOMAIN), 'icon' => 'dashicons-chart-line'],
                    ['slug' => 'dex-solana-dashboard', 'label' => __('DEX Solana', FLAVOR_PLATFORM_TEXT_DOMAIN), 'icon' => 'dashicons-money'],
                ],
            ],

            // =================================================================
            // 4. ACTIVIDADES - Eventos, formación, reservas
            // =================================================================
            'actividades' => [
                'label' => __('Actividades', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'icon' => 'dashicons-calendar-alt',
                'items' => [
                    ['slug' => 'eventos-dashboard', 'label' => __('Eventos', FLAVOR_PLATFORM_TEXT_DOMAIN), 'icon' => 'dashicons-calendar'],
                    ['slug' => 'cursos-dashboard', 'label' => __('Cursos', FLAVOR_PLATFORM_TEXT_DOMAIN), 'icon' => 'dashicons-welcome-learn-more'],
                    ['slug' => 'talleres-dashboard', 'label' => __('Talleres', FLAVOR_PLATFORM_TEXT_DOMAIN), 'icon' => 'dashicons-hammer'],
                    ['slug' => 'reservas-dashboard', 'label' => __('Reservas', FLAVOR_PLATFORM_TEXT_DOMAIN), 'icon' => 'dashicons-tickets-alt'],
                    ['slug' => 'fichaje-dashboard', 'label' => __('Fichaje', FLAVOR_PLATFORM_TEXT_DOMAIN), 'icon' => 'dashicons-clock'],
                ],
            ],

            // =================================================================
            // 5. SERVICIOS - Trámites, participación, recursos
            // (Fusiona mod_servicios + mod_recursos)
            // =================================================================
            'servicios' => [
                'label' => __('Servicios', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'icon' => 'dashicons-admin-tools',
                'items' => [
                    // Gestión ciudadana
                    ['slug' => 'tramites-dashboard', 'label' => __('Trámites', FLAVOR_PLATFORM_TEXT_DOMAIN), 'icon' => 'dashicons-clipboard'],
                    ['slug' => 'incidencias-dashboard', 'label' => __('Incidencias', FLAVOR_PLATFORM_TEXT_DOMAIN), 'icon' => 'dashicons-warning'],
                    ['slug' => 'ayuda-dashboard', 'label' => __('Ayuda Vecinal', FLAVOR_PLATFORM_TEXT_DOMAIN), 'icon' => 'dashicons-sos'],
                    ['slug' => 'participacion-dashboard', 'label' => __('Participación', FLAVOR_PLATFORM_TEXT_DOMAIN), 'icon' => 'dashicons-megaphone'],
                    ['slug' => 'transparencia-dashboard', 'label' => __('Transparencia', FLAVOR_PLATFORM_TEXT_DOMAIN), 'icon' => 'dashicons-visibility'],
                    ['slug' => 'avisos-dashboard', 'label' => __('Avisos', FLAVOR_PLATFORM_TEXT_DOMAIN), 'icon' => 'dashicons-megaphone'],
                    ['slug' => 'denuncias-dashboard', 'label' => __('Denuncias', FLAVOR_PLATFORM_TEXT_DOMAIN), 'icon' => 'dashicons-flag'],
                    ['slug' => 'documentos-dashboard', 'label' => __('Documentación', FLAVOR_PLATFORM_TEXT_DOMAIN), 'icon' => 'dashicons-media-document'],
                    // Recursos compartidos
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

            // =================================================================
            // 6. SOSTENIBILIDAD - Ecología y comunicación
            // (Fusiona mod_sostenibilidad + mod_comunicacion)
            // =================================================================
            'sostenibilidad' => [
                'label' => __('Sostenibilidad', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'icon' => 'dashicons-palmtree',
                'items' => [
                    // Ecología
                    ['slug' => 'reciclaje-dashboard', 'label' => __('Reciclaje', FLAVOR_PLATFORM_TEXT_DOMAIN), 'icon' => 'dashicons-image-rotate'],
                    ['slug' => 'compostaje-dashboard', 'label' => __('Compostaje', FLAVOR_PLATFORM_TEXT_DOMAIN), 'icon' => 'dashicons-carrot'],
                    ['slug' => 'flavor-energia-dashboard', 'label' => __('Energía', FLAVOR_PLATFORM_TEXT_DOMAIN), 'icon' => 'dashicons-lightbulb'],
                    ['slug' => 'bicicletas-dashboard', 'label' => __('Bicicletas', FLAVOR_PLATFORM_TEXT_DOMAIN), 'icon' => 'dashicons-performance'],
                    ['slug' => 'biodiversidad-dashboard', 'label' => __('Biodiversidad', FLAVOR_PLATFORM_TEXT_DOMAIN), 'icon' => 'dashicons-palmtree'],
                    ['slug' => 'huella-ecologica-dashboard', 'label' => __('Huella Ecológica', FLAVOR_PLATFORM_TEXT_DOMAIN), 'icon' => 'dashicons-chart-line'],
                    // Social/Cuidados
                    ['slug' => 'saberes-dashboard', 'label' => __('Saberes', FLAVOR_PLATFORM_TEXT_DOMAIN), 'icon' => 'dashicons-welcome-learn-more'],
                    ['slug' => 'suficiencia-dashboard', 'label' => __('Suficiencia', FLAVOR_PLATFORM_TEXT_DOMAIN), 'icon' => 'dashicons-heart'],
                    ['slug' => 'circulos-cuidados-dashboard', 'label' => __('Círculos Cuidados', FLAVOR_PLATFORM_TEXT_DOMAIN), 'icon' => 'dashicons-groups'],
                    ['slug' => 'trabajo-digno-dashboard', 'label' => __('Trabajo Digno', FLAVOR_PLATFORM_TEXT_DOMAIN), 'icon' => 'dashicons-businessman'],
                    ['slug' => 'justicia-restaurativa-dashboard', 'label' => __('Justicia Rest.', FLAVOR_PLATFORM_TEXT_DOMAIN), 'icon' => 'dashicons-shield'],
                    // Comunicación (fusionado)
                    ['slug' => 'multimedia-dashboard', 'label' => __('Multimedia', FLAVOR_PLATFORM_TEXT_DOMAIN), 'icon' => 'dashicons-format-video'],
                    ['slug' => 'flavor-radio-dashboard', 'label' => __('Radio', FLAVOR_PLATFORM_TEXT_DOMAIN), 'icon' => 'dashicons-controls-volumeon'],
                    ['slug' => 'podcast-dashboard', 'label' => __('Podcast', FLAVOR_PLATFORM_TEXT_DOMAIN), 'icon' => 'dashicons-microphone'],
                    ['slug' => 'campanias-dashboard', 'label' => __('Campañas', FLAVOR_PLATFORM_TEXT_DOMAIN), 'icon' => 'dashicons-email-alt'],
                    ['slug' => 'email-marketing-dashboard', 'label' => __('Email Marketing', FLAVOR_PLATFORM_TEXT_DOMAIN), 'icon' => 'dashicons-email'],
                    ['slug' => 'encuestas-dashboard', 'label' => __('Encuestas', FLAVOR_PLATFORM_TEXT_DOMAIN), 'icon' => 'dashicons-forms'],
                ],
            ],

            // =================================================================
            // 7. CONFIGURACIÓN - Apps, extensiones, IA, administración
            // (Fusiona chat_ia + apps + extensiones + administracion)
            // =================================================================
            'configuracion' => [
                'label' => __('Configuración', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'icon' => 'dashicons-admin-settings',
                'items' => [
                    // Asistente IA
                    ['slug' => 'flavor-platform-settings', 'label' => __('Asistente IA', FLAVOR_PLATFORM_TEXT_DOMAIN), 'icon' => 'dashicons-admin-comments'],
                    ['slug' => 'flavor-platform-escalations', 'label' => __('Escalados IA', FLAVOR_PLATFORM_TEXT_DOMAIN), 'icon' => 'dashicons-warning'],
                    // Apps
                    ['slug' => 'flavor-platform-apps', 'label' => __('Apps Móviles', FLAVOR_PLATFORM_TEXT_DOMAIN), 'icon' => 'dashicons-smartphone'],
                    ['slug' => 'flavor-app-generator', 'label' => __('Generador Apps', FLAVOR_PLATFORM_TEXT_DOMAIN), 'icon' => 'dashicons-admin-customizer'],
                    ['slug' => 'flavor-platform-deep-links', 'label' => __('Deep Links', FLAVOR_PLATFORM_TEXT_DOMAIN), 'icon' => 'dashicons-admin-links'],
                    ['slug' => 'flavor-platform-network', 'label' => __('Red', FLAVOR_PLATFORM_TEXT_DOMAIN), 'icon' => 'dashicons-networking'],
                    // Extensiones
                    ['slug' => 'flavor-addons', 'label' => __('Addons', FLAVOR_PLATFORM_TEXT_DOMAIN), 'icon' => 'dashicons-admin-plugins'],
                    ['slug' => 'flavor-marketplace', 'label' => __('Marketplace Addons', FLAVOR_PLATFORM_TEXT_DOMAIN), 'icon' => 'dashicons-store'],
                    ['slug' => 'flavor-newsletter', 'label' => __('Newsletter', FLAVOR_PLATFORM_TEXT_DOMAIN), 'icon' => 'dashicons-email'],
                    ['slug' => 'flavor-platform-license', 'label' => __('Licencia', FLAVOR_PLATFORM_TEXT_DOMAIN), 'icon' => 'dashicons-admin-network'],
                    // Administración
                    ['slug' => 'flavor-moderation', 'label' => __('Moderación', FLAVOR_PLATFORM_TEXT_DOMAIN), 'icon' => 'dashicons-shield'],
                    ['slug' => 'advertising-dashboard', 'label' => __('Anuncios', FLAVOR_PLATFORM_TEXT_DOMAIN), 'icon' => 'dashicons-megaphone'],
                    ['slug' => 'flavor-shell-views', 'label' => __('Vistas Shell', FLAVOR_PLATFORM_TEXT_DOMAIN), 'icon' => 'dashicons-visibility'],
                    ['slug' => 'flavor-integraciones-posts', 'label' => __('Integraciones Posts', FLAVOR_PLATFORM_TEXT_DOMAIN), 'icon' => 'dashicons-admin-post'],
                    ['slug' => 'flavor-integraciones-config', 'label' => __('Config. Integraciones', FLAVOR_PLATFORM_TEXT_DOMAIN), 'icon' => 'dashicons-admin-settings'],
                ],
            ],

            // =================================================================
            // 8. HERRAMIENTAS - Diagnóstico, utilidades, ayuda
            // (Fusiona herramientas + ayuda)
            // =================================================================
            'herramientas' => [
                'label' => __('Herramientas', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'icon' => 'dashicons-admin-tools',
                'items' => [
                    // Utilidades
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
                    // Ayuda (fusionado)
                    ['slug' => 'flavor-platform-docs', 'label' => __('Documentación', FLAVOR_PLATFORM_TEXT_DOMAIN), 'icon' => 'dashicons-book'],
                    ['slug' => 'flavor-tours', 'label' => __('Tours', FLAVOR_PLATFORM_TEXT_DOMAIN), 'icon' => 'dashicons-location'],
                ],
            ],
        ];

        // Obtener el menu manager para filtrar según vista
        $menu_manager = class_exists('Flavor_Admin_Menu_Manager')
            ? Flavor_Admin_Menu_Manager::get_instance()
            : null;

        // Obtener módulos activos para filtrar
        $modulos_activos = $this->get_active_modules();

        $estructura_filtrada = [];

        foreach ($estructura_completa as $seccion_id => $seccion) {
            $items_filtrados = [];

            foreach ($seccion['items'] as $item) {
                // 1. Verificar si el módulo requerido está activo
                if (!$this->is_module_item_visible($item['slug'], $modulos_activos)) {
                    continue;
                }

                // 2. Verificar si el menú es visible en la vista actual
                if ($menu_manager && !$menu_manager->menu_visible_en_vista($item['slug'])) {
                    continue;
                }

                $items_filtrados[] = $item;
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

    /**
     * AJAX handler para obtener subpáginas de un módulo (lazy loading)
     */
    public function ajax_get_subpages() {
        check_ajax_referer('flavor_admin_shell', 'nonce');

        $parent_slug = isset($_POST['parent_slug']) ? sanitize_text_field($_POST['parent_slug']) : '';

        if (empty($parent_slug)) {
            wp_send_json_error(['message' => __('Slug del módulo requerido', FLAVOR_PLATFORM_TEXT_DOMAIN)]);
        }

        // Obtener subpáginas del registro de navegación
        $subpages = [];

        if (class_exists('Flavor_Shell_Navigation_Registry')) {
            $registry = Flavor_Shell_Navigation_Registry::get_instance();
            $registered_subpages = $registry->get_subpages($parent_slug);

            foreach ($registered_subpages as $subpage) {
                $subpages[] = [
                    'slug' => $subpage['slug'] ?? '',
                    'label' => $subpage['label'] ?? '',
                    'icon' => $subpage['icon'] ?? 'dashicons-arrow-right-alt2',
                    'url' => admin_url('admin.php?page=' . ($subpage['slug'] ?? '')),
                    'badge' => $subpage['badge'] ?? null,
                ];
            }
        }

        wp_send_json_success([
            'parent_slug' => $parent_slug,
            'subpages' => $subpages,
        ]);
    }

    /**
     * AJAX handler para obtener badges (lazy loading)
     */
    public function ajax_get_badges_lazy() {
        check_ajax_referer('flavor_admin_shell', 'nonce');

        $slugs = isset($_POST['slugs']) ? array_map('sanitize_text_field', (array) $_POST['slugs']) : [];

        if (empty($slugs)) {
            wp_send_json_success(['badges' => []]);
        }

        // Usar caché para badges
        $cache_key = 'shell_badges_' . md5(implode(',', $slugs));
        $badges = Flavor_Cache_Manager::remember($cache_key, function() use ($slugs) {
            $result = [];

            if (class_exists('Flavor_Shell_Navigation_Registry')) {
                $registry = Flavor_Shell_Navigation_Registry::get_instance();

                foreach ($slugs as $slug) {
                    $badge_data = $registry->get_badge($slug);
                    if ($badge_data && isset($badge_data['count']) && $badge_data['count'] > 0) {
                        $result[$slug] = [
                            'count' => (int) $badge_data['count'],
                            'severity' => $badge_data['severity'] ?? 'info',
                            'tooltip' => $badge_data['tooltip'] ?? '',
                        ];
                    }
                }
            }

            return $result;
        }, Flavor_Cache_Manager::BADGES_TTL);

        wp_send_json_success(['badges' => $badges]);
    }

    /**
     * Añadir atributo defer a scripts del shell
     *
     * @param string $tag    Tag HTML del script
     * @param string $handle Handle del script
     * @return string Tag modificado
     */
    public function add_defer_attribute($tag, $handle) {
        // NOTA: NO añadir defer a 'alpine' ni 'flavor-admin-shell' porque
        // los componentes Alpine deben registrarse ANTES de que Alpine
        // procese el DOM. Solo ajax-navigation puede tener defer porque
        // no contiene componentes Alpine.
        $defer_handles = ['flavor-ajax-navigation'];

        if (in_array($handle, $defer_handles, true)) {
            // Evitar duplicar defer si ya existe
            if (strpos($tag, ' defer') === false) {
                $tag = str_replace(' src', ' defer src', $tag);
            }
        }

        return $tag;
    }

    /**
     * Obtiene la lista de módulos activos
     *
     * @return array Lista de IDs de módulos activos
     */
    private function get_active_modules() {
        static $modulos_activos = null;

        if ($modulos_activos === null) {
            $modulos_activos = get_option('flavor_active_modules', []);
            if (!is_array($modulos_activos)) {
                $modulos_activos = [];
            }
        }

        return $modulos_activos;
    }

    /**
     * Verifica si un item del menú debe mostrarse según módulos activos
     *
     * @param string $slug            Slug del item
     * @param array  $modulos_activos Lista de módulos activos
     * @return bool True si debe mostrarse
     */
    private function is_module_item_visible($slug, $modulos_activos) {
        // Si no hay mapeo para este slug, siempre es visible (items core)
        if (!isset(self::SLUG_TO_MODULE[$slug])) {
            return true;
        }

        $modulo_requerido = self::SLUG_TO_MODULE[$slug];

        // Verificar si el módulo está activo
        return in_array($modulo_requerido, $modulos_activos, true);
    }
}

// Inicializar
Flavor_Admin_Shell::get_instance();

<?php
/**
 * Gestor Centralizado del Menú Admin de Flavor Platform
 *
 * Registra TODOS los submenús en un solo lugar para mantener orden.
 * Los módulos NO deben registrar sus propios menús en el menú principal.
 *
 * @package FlavorPlatform
 * @subpackage Admin
 * @since 3.1.0
 */

if (!defined('ABSPATH')) {
    exit;
}

class Flavor_Admin_Menu_Manager {

    /**
     * Instancia singleton
     */
    private static $instancia = null;

    /**
     * Slug del menú principal
     */
    const MENU_SLUG = 'flavor-chat-ia';

    /**
     * Obtiene la instancia singleton
     */
    public static function get_instance() {
        if (self::$instancia === null) {
            self::$instancia = new self();
        }
        return self::$instancia;
    }

    /**
     * Constructor privado
     */
    private function __construct() {
        // Registrar menús con prioridad alta para ejecutar antes que otros
        add_action('admin_menu', [$this, 'registrar_menus'], 5);
        // Limpiar menús duplicados después de que todos se registren
        add_action('admin_menu', [$this, 'limpiar_menus_duplicados'], 999);
        // CSS para separadores del menú en todas las páginas admin
        add_action('admin_head', [$this, 'encolar_css_separadores'], 5);
        // Estilos base para dashboards de módulos
        add_action('admin_enqueue_scripts', [$this, 'enqueue_module_dashboard_styles']);
        // Boton volver al dashboard en dashboards de modulos
        add_action('admin_footer', [$this, 'render_back_to_dashboard_button']);
    }

    /**
     * Registra todos los menús de Flavor Platform
     */
    public function registrar_menus() {
        // ══════════════════════════════════════════════════════════════
        // MENÚ PRINCIPAL
        // ══════════════════════════════════════════════════════════════
        add_menu_page(
            __('Flavor Platform', 'flavor-chat-ia'),
            __('Flavor Platform', 'flavor-chat-ia'),
            'manage_options',
            self::MENU_SLUG,
            [$this, 'callback_dashboard'],
            'dashicons-superhero-alt',
            30
        );

        // Dashboard (pos 0)
        add_submenu_page(
            self::MENU_SLUG,
            __('Dashboard', 'flavor-chat-ia'),
            __('Dashboard', 'flavor-chat-ia'),
            'manage_options',
            'flavor-dashboard',
            [$this, 'callback_dashboard'],
            0
        );

        // Dashboard Unificado (pos 1) - Vista centralizada de todos los módulos
        add_submenu_page(
            self::MENU_SLUG,
            __('Dashboard Unificado', 'flavor-chat-ia'),
            __('Unificado', 'flavor-chat-ia'),
            'read',
            'flavor-unified-dashboard',
            [$this, 'callback_unified_dashboard'],
            1
        );

        // Eliminar el submenú duplicado que WP crea
        remove_submenu_page(self::MENU_SLUG, self::MENU_SLUG);

        // ══════════════════════════════════════════════════════════════
        // SECCIÓN: MI APP (10-19)
        // ══════════════════════════════════════════════════════════════
        $this->agregar_separador(__('Mi App', 'flavor-chat-ia'), 10);

        add_submenu_page(
            self::MENU_SLUG,
            __('Compositor & Módulos', 'flavor-chat-ia'),
            __('Compositor', 'flavor-chat-ia'),
            'manage_options',
            'flavor-app-composer',
            [$this, 'callback_app_composer'],
            11
        );

        add_submenu_page(
            self::MENU_SLUG,
            __('Diseño y Apariencia', 'flavor-chat-ia'),
            __('Diseño', 'flavor-chat-ia'),
            'manage_options',
            'flavor-design-settings',
            [$this, 'callback_design_settings'],
            12
        );

        add_submenu_page(
            self::MENU_SLUG,
            __('Menús y Footers', 'flavor-chat-ia'),
            __('Layouts', 'flavor-chat-ia'),
            'manage_options',
            'flavor-layouts',
            [$this, 'callback_layouts'],
            13
        );

        add_submenu_page(
            self::MENU_SLUG,
            __('Crear Páginas', 'flavor-chat-ia'),
            __('Páginas', 'flavor-chat-ia'),
            'manage_options',
            'flavor-create-pages',
            [$this, 'callback_pages'],
            14
        );

        add_submenu_page(
            self::MENU_SLUG,
            __('Editor Visual', 'flavor-chat-ia'),
            __('Editor Visual', 'flavor-chat-ia'),
            'manage_options',
            'flavor-landing-editor',
            [$this, 'callback_landing_editor'],
            15
        );

        add_submenu_page(
            self::MENU_SLUG,
            __('Permisos', 'flavor-chat-ia'),
            __('Permisos', 'flavor-chat-ia'),
            'manage_options',
            'flavor-permissions',
            [$this, 'callback_permissions'],
            16
        );

        // ══════════════════════════════════════════════════════════════
        // SECCIÓN: CHAT IA (20-29)
        // ══════════════════════════════════════════════════════════════
        $this->agregar_separador(__('Chat IA', 'flavor-chat-ia'), 20);

        add_submenu_page(
            self::MENU_SLUG,
            __('Configuración IA', 'flavor-chat-ia'),
            __('Configuración', 'flavor-chat-ia'),
            'manage_options',
            'flavor-chat-config',
            [$this, 'callback_chat_settings'],
            21
        );

        add_submenu_page(
            self::MENU_SLUG,
            __('Escalados', 'flavor-chat-ia'),
            __('Escalados', 'flavor-chat-ia'),
            'manage_options',
            'flavor-chat-ia-escalations',
            [$this, 'callback_escalations'],
            22
        );

        // ══════════════════════════════════════════════════════════════
        // SECCIÓN: APPS Y CONECTIVIDAD (30-39)
        // ══════════════════════════════════════════════════════════════
        $this->agregar_separador(__('Apps', 'flavor-chat-ia'), 30);

        add_submenu_page(
            self::MENU_SLUG,
            __('Apps Móviles', 'flavor-chat-ia'),
            __('Apps Móviles', 'flavor-chat-ia'),
            'manage_options',
            'flavor-apps-config',
            [$this, 'callback_apps_config'],
            31
        );

        add_submenu_page(
            self::MENU_SLUG,
            __('Deep Links', 'flavor-chat-ia'),
            __('Deep Links', 'flavor-chat-ia'),
            'manage_options',
            'flavor-deep-links',
            [$this, 'callback_deep_links'],
            33
        );

        add_submenu_page(
            self::MENU_SLUG,
            __('Red de Nodos', 'flavor-chat-ia'),
            __('Red', 'flavor-chat-ia'),
            'manage_options',
            'flavor-network',
            [$this, 'callback_network'],
            35
        );

        // ══════════════════════════════════════════════════════════════
        // SECCIÓN: EXTENSIONES (40-49)
        // ══════════════════════════════════════════════════════════════
        $this->agregar_separador(__('Extensiones', 'flavor-chat-ia'), 40);

        add_submenu_page(
            self::MENU_SLUG,
            __('Addons', 'flavor-chat-ia'),
            __('Addons', 'flavor-chat-ia'),
            'manage_options',
            'flavor-addons',
            [$this, 'callback_addons'],
            41
        );

        add_submenu_page(
            self::MENU_SLUG,
            __('Marketplace', 'flavor-chat-ia'),
            __('Marketplace', 'flavor-chat-ia'),
            'manage_options',
            'flavor-marketplace',
            [$this, 'callback_marketplace'],
            42
        );

        add_submenu_page(
            self::MENU_SLUG,
            __('Newsletter', 'flavor-chat-ia'),
            __('Newsletter', 'flavor-chat-ia'),
            'manage_options',
            'flavor-newsletter',
            [$this, 'callback_newsletter'],
            43
        );

        // ══════════════════════════════════════════════════════════════
        // SECCIÓN: HERRAMIENTAS (50-59)
        // ══════════════════════════════════════════════════════════════
        $this->agregar_separador(__('Herramientas', 'flavor-chat-ia'), 50);

        add_submenu_page(
            self::MENU_SLUG,
            __('Exportar / Importar', 'flavor-chat-ia'),
            __('Export / Import', 'flavor-chat-ia'),
            'manage_options',
            'flavor-export-import',
            [$this, 'callback_export_import'],
            51
        );

        add_submenu_page(
            self::MENU_SLUG,
            __('Diagnóstico', 'flavor-chat-ia'),
            __('Diagnóstico', 'flavor-chat-ia'),
            'manage_options',
            'flavor-health-check',
            [$this, 'callback_health_check'],
            52
        );

        add_submenu_page(
            self::MENU_SLUG,
            __('Registro de Actividad', 'flavor-chat-ia'),
            __('Actividad', 'flavor-chat-ia'),
            'manage_options',
            'flavor-activity-log',
            [$this, 'callback_activity_log'],
            53
        );

        add_submenu_page(
            self::MENU_SLUG,
            __('API Docs', 'flavor-chat-ia'),
            __('API Docs', 'flavor-chat-ia'),
            'manage_options',
            'flavor-api-docs',
            [$this, 'callback_api_docs'],
            54
        );

        add_submenu_page(
            self::MENU_SLUG,
            __('Panel de Sistemas', 'flavor-chat-ia'),
            __('Sistemas V3', 'flavor-chat-ia'),
            'manage_options',
            'flavor-systems-panel',
            [$this, 'callback_systems_panel'],
            55
        );

        // ══════════════════════════════════════════════════════════════
        // SECCIÓN: AYUDA (60-69)
        // ══════════════════════════════════════════════════════════════
        $this->agregar_separador(__('Ayuda', 'flavor-chat-ia'), 60);

        add_submenu_page(
            self::MENU_SLUG,
            __('Documentación', 'flavor-chat-ia'),
            __('Documentación', 'flavor-chat-ia'),
            'manage_options',
            'flavor-documentation',
            [$this, 'callback_documentation'],
            61
        );

        add_submenu_page(
            self::MENU_SLUG,
            __('Tours Guiados', 'flavor-chat-ia'),
            __('Tours', 'flavor-chat-ia'),
            'manage_options',
            'flavor-tours',
            [$this, 'callback_tours'],
            62
        );

        // ══════════════════════════════════════════════════════════════
        // PÁGINAS OCULTAS (sin mostrar en menú)
        // ══════════════════════════════════════════════════════════════

        // Contenido Apps
        add_submenu_page(
            null,
            __('Contenido de Apps', 'flavor-chat-ia'),
            '',
            'manage_options',
            'flavor-app-cpts',
            [$this, 'callback_app_cpts']
        );

        // Newsletter Editor
        add_submenu_page(
            null,
            __('Editor de Campaña', 'flavor-chat-ia'),
            '',
            'manage_options',
            'flavor-newsletter-editor',
            [$this, 'callback_newsletter_editor']
        );

        // Setup Wizard
        add_submenu_page(
            null,
            __('Asistente de Configuración', 'flavor-chat-ia'),
            '',
            'manage_options',
            'flavor-setup-wizard',
            [$this, 'callback_setup_wizard']
        );

        // Redirigir menú principal al dashboard
        global $submenu;
        if (isset($submenu[self::MENU_SLUG])) {
            foreach ($submenu[self::MENU_SLUG] as &$item) {
                if ($item[2] === self::MENU_SLUG) {
                    $item[2] = 'flavor-dashboard';
                    break;
                }
            }
        }
    }

    /**
     * Limpia menús duplicados añadidos por módulos
     */
    public function limpiar_menus_duplicados() {
        global $submenu;

        if (!isset($submenu[self::MENU_SLUG])) {
            return;
        }

        // Slugs que ya están en el menú centralizado
        $slugs_centralizados = [
            'flavor-dashboard',
            'flavor-unified-dashboard',
            'flavor-app-composer',
            'flavor-design-settings',
            'flavor-create-pages',
            'flavor-landing-editor',
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
        ];

        // Slugs a remover (menús duplicados que no deberían estar en el menú principal)
        // NOTA: Los módulos como grupos-consumo, email-marketing, multimedia, radio, reciclaje
        // tienen sus propios menús de nivel superior y NO deben añadir submenús aquí.
        $slugs_a_remover = [
            // Cualquier duplicado del menú principal
            self::MENU_SLUG,
            // Módulos que se movieron a categorías
            'flavor-multimedia',
            'flavor-radio',
            'flavor-reciclaje',
        ];

        foreach ($slugs_a_remover as $slug) {
            remove_submenu_page(self::MENU_SLUG, $slug);
        }

        // También ordenar los elementos del submenú según su posición
        $this->ordenar_submenu();
    }

    /**
     * Ordena los elementos del submenú según su posición numérica
     */
    private function ordenar_submenu() {
        global $submenu;

        if (!isset($submenu[self::MENU_SLUG])) {
            return;
        }

        // WordPress usa el índice 2 para el slug y el índice 10 (si existe) para la posición
        // Pero add_submenu_page no siempre respeta el parámetro de posición
        // Así que reordenamos manualmente basándonos en los slugs conocidos

        $orden_deseado = [
            'flavor-dashboard' => 0,
            'flavor-unified-dashboard' => 1,
            'flavor-separator-10' => 10,
            'flavor-app-composer' => 11,
            'flavor-design-settings' => 12,
            'flavor-layouts' => 13,
            'flavor-create-pages' => 14,
            'flavor-landing-editor' => 15,
            'flavor-permissions' => 16,
            'flavor-separator-20' => 20,
            'flavor-chat-config' => 21,
            'flavor-chat-ia-escalations' => 22,
            'flavor-separator-30' => 30,
            'flavor-apps-config' => 31,
            'flavor-deep-links' => 33,
            'flavor-network' => 35,
            'flavor-separator-40' => 40,
            'flavor-addons' => 41,
            'flavor-marketplace' => 42,
            'flavor-newsletter' => 43,
            'flavor-separator-50' => 50,
            'flavor-export-import' => 51,
            'flavor-health-check' => 52,
            'flavor-activity-log' => 53,
            'flavor-api-docs' => 54,
            'flavor-separator-60' => 60,
            'flavor-documentation' => 61,
            'flavor-tours' => 62,
        ];

        usort($submenu[self::MENU_SLUG], function($a, $b) use ($orden_deseado) {
            $slug_a = $a[2] ?? '';
            $slug_b = $b[2] ?? '';

            $pos_a = $orden_deseado[$slug_a] ?? 999;
            $pos_b = $orden_deseado[$slug_b] ?? 999;

            return $pos_a - $pos_b;
        });
    }

    /**
     * Agrega separador visual
     */
    private function agregar_separador($etiqueta, $posicion) {
        add_submenu_page(
            self::MENU_SLUG,
            '',
            '<span class="flavor-menu-separator">' . esc_html($etiqueta) . '</span>',
            'manage_options',
            'flavor-separator-' . $posicion,
            '__return_empty_string',
            $posicion
        );
    }

    /**
     * Encola el CSS de los separadores del menú (llamado en admin_head)
     */
    public function encolar_css_separadores() {
        static $css_encolado = false;

        if ($css_encolado) {
            return;
        }
        $css_encolado = true;

        ?>
        <style id="flavor-menu-separators">
            /* Separadores del menú Flavor Platform */
            .flavor-menu-separator {
                display: block;
                padding: 10px 0 6px;
                margin: 5px 0 0;
                font-size: 11px;
                font-weight: 600;
                text-transform: uppercase;
                letter-spacing: 0.5px;
                color: #a7aaad;
                pointer-events: none;
                cursor: default;
                border-top: 1px solid rgba(255,255,255,0.1);
            }
            #adminmenu .wp-submenu a[href*='flavor-separator-'] {
                pointer-events: none !important;
                cursor: default !important;
                height: auto !important;
                line-height: 1 !important;
                padding-left: 12px !important;
            }
            #adminmenu .wp-submenu a[href*='flavor-separator-']:hover,
            #adminmenu .wp-submenu a[href*='flavor-separator-']:focus {
                background: transparent !important;
                color: #a7aaad !important;
            }
            #adminmenu .wp-submenu li a[href*='flavor-separator-']::before {
                display: none;
            }
            /* Hacer los separadores más visibles */
            #adminmenu .wp-submenu li.wp-not-current-submenu a[href*='flavor-separator-'] {
                opacity: 0.9;
            }
        </style>
        <?php
    }

    // ══════════════════════════════════════════════════════════════
    // CALLBACKS
    // ══════════════════════════════════════════════════════════════

    public function callback_dashboard() {
        if (class_exists('Flavor_Dashboard')) {
            Flavor_Dashboard::get_instance()->render_dashboard_page();
        } else {
            echo '<div class="wrap"><h1>Dashboard</h1><p>Cargando...</p></div>';
        }
    }

    public function callback_unified_dashboard() {
        // Cargar estilos del dashboard (el hook admin_enqueue_scripts ya paso)
        $this->enqueue_unified_dashboard_assets();

        // Cargar el Dashboard Unificado
        if (!class_exists('Flavor_Unified_Dashboard')) {
            $dashboard_path = FLAVOR_CHAT_IA_PATH . 'includes/dashboard/';
            if (file_exists($dashboard_path . 'class-unified-dashboard.php')) {
                require_once $dashboard_path . 'interface-dashboard-widget.php';
                require_once $dashboard_path . 'class-widget-registry.php';
                require_once $dashboard_path . 'class-widget-renderer.php';
                require_once $dashboard_path . 'class-unified-dashboard.php';
            }
        }

        if (class_exists('Flavor_Unified_Dashboard')) {
            Flavor_Unified_Dashboard::get_instance()->render();
        } else {
            echo '<div class="wrap"><h1>' . esc_html__('Dashboard Unificado', 'flavor-chat-ia') . '</h1>';
            echo '<p>' . esc_html__('Error al cargar el Dashboard Unificado.', 'flavor-chat-ia') . '</p></div>';
        }
    }

    /**
     * Encola los assets del Dashboard Unificado
     *
     * Sistema de Diseño Unificado v4.1.0
     */
    private function enqueue_unified_dashboard_assets() {
        $version = defined('FLAVOR_CHAT_IA_VERSION') ? FLAVOR_CHAT_IA_VERSION : '4.1.0';
        $plugin_url = FLAVOR_CHAT_IA_URL;

        // =====================================================================
        // CSS - Sistema de Diseño Unificado (v4.1.0)
        // =====================================================================

        // 1. Design Tokens (variables CSS base)
        wp_enqueue_style(
            'fl-design-tokens',
            $plugin_url . 'assets/css/design-tokens.css',
            [],
            $version
        );

        // 2. Compatibilidad con variables antiguas
        wp_enqueue_style(
            'fl-design-tokens-compat',
            $plugin_url . 'assets/css/design-tokens-compat.css',
            ['fl-design-tokens'],
            $version
        );

        // 3. CSS Base del dashboard
        wp_enqueue_style(
            'fud-dashboard-base',
            $plugin_url . 'assets/css/dashboard-base.css',
            ['fl-design-tokens-compat'],
            $version
        );

        // 4. Widgets y niveles
        wp_enqueue_style(
            'fl-dashboard-widgets',
            $plugin_url . 'assets/css/dashboard-widgets.css',
            ['fud-dashboard-base'],
            $version
        );

        // 5. Grupos y categorías
        wp_enqueue_style(
            'fl-dashboard-groups',
            $plugin_url . 'assets/css/dashboard-groups.css',
            ['fl-dashboard-widgets'],
            $version
        );

        // 6. Estados visuales
        wp_enqueue_style(
            'fl-dashboard-states',
            $plugin_url . 'assets/css/dashboard-states.css',
            ['fl-dashboard-widgets'],
            $version
        );

        // 7. Accesibilidad
        wp_enqueue_style(
            'fl-dashboard-a11y',
            $plugin_url . 'assets/css/dashboard-a11y.css',
            ['fl-dashboard-widgets'],
            $version
        );

        // 8. Responsive
        wp_enqueue_style(
            'fl-dashboard-responsive',
            $plugin_url . 'assets/css/dashboard-responsive.css',
            ['fl-dashboard-groups'],
            $version
        );

        // 9. Breadcrumbs
        wp_enqueue_style(
            'fl-breadcrumbs',
            $plugin_url . 'assets/css/breadcrumbs.css',
            ['fl-design-tokens'],
            $version
        );

        // 10. CSS Componentes (legacy)
        wp_enqueue_style(
            'fud-dashboard-components',
            $plugin_url . 'assets/css/dashboard-components.css',
            ['fl-dashboard-responsive'],
            $version
        );

        // 11. CSS Unificado (admin)
        if (file_exists(FLAVOR_CHAT_IA_PATH . 'admin/css/unified-dashboard.css')) {
            wp_enqueue_style(
                'fud-unified-dashboard',
                $plugin_url . 'admin/css/unified-dashboard.css',
                ['fud-dashboard-components'],
                $version
            );
        }

        // =====================================================================
        // JavaScript - Sistema de Drag & Drop (v4.1.0)
        // =====================================================================

        // SortableJS desde CDN
        wp_enqueue_script(
            'sortablejs',
            'https://cdn.jsdelivr.net/npm/sortablejs@1.15.2/Sortable.min.js',
            [],
            '1.15.2',
            true
        );

        // Dashboard Sortable (nuevo sistema)
        if (file_exists(FLAVOR_CHAT_IA_PATH . 'assets/js/dashboard-sortable.js')) {
            wp_enqueue_script(
                'fl-dashboard-sortable',
                $plugin_url . 'assets/js/dashboard-sortable.js',
                ['sortablejs'],
                $version,
                true
            );
        }

        // jQuery UI Sortable como fallback
        wp_enqueue_script('jquery-ui-sortable');
        wp_enqueue_script('jquery-ui-draggable');

        // JS del dashboard (legacy)
        if (file_exists(FLAVOR_CHAT_IA_PATH . 'admin/js/unified-dashboard.js')) {
            wp_enqueue_script(
                'fud-unified-dashboard',
                $plugin_url . 'admin/js/unified-dashboard.js',
                ['jquery', 'jquery-ui-sortable', 'fl-dashboard-sortable'],
                $version,
                true
            );
        }

        // =====================================================================
        // Localización de scripts
        // =====================================================================
        $dashboard_config = [
            'ajaxUrl'         => admin_url('admin-ajax.php'),
            'restUrl'         => rest_url('flavor/v1/dashboard/'),
            'nonce'           => wp_create_nonce('fud_nonce'),
            'refreshInterval' => 120000, // 2 minutos
            'features'        => [
                'sortable'      => true,
                'groups'        => true,
                'levels'        => true,
                'accessibility' => true,
            ],
            'i18n'            => [
                'loading'      => __('Cargando...', 'flavor-chat-ia'),
                'error'        => __('Error al cargar', 'flavor-chat-ia'),
                'saved'        => __('Cambios guardados', 'flavor-chat-ia'),
                'refreshing'   => __('Actualizando...', 'flavor-chat-ia'),
                'layoutSaved'  => __('Disposición guardada', 'flavor-chat-ia'),
                'dragStart'    => __('Arrastrando widget', 'flavor-chat-ia'),
                'dragEnd'      => __('Widget soltado', 'flavor-chat-ia'),
            ],
        ];

        wp_localize_script('fl-dashboard-sortable', 'flDashboard', $dashboard_config);
        wp_localize_script('fud-unified-dashboard', 'fudConfig', $dashboard_config);
    }

    /**
     * Carga estilos base para dashboards de modulos
     *
     * Sistema de Diseño Unificado v4.1.0
     *
     * @param string $hook Hook de la pagina actual
     */
    public function enqueue_module_dashboard_styles($hook) {
        // Solo cargar en paginas que terminan en -dashboard
        $current_page = isset($_GET['page']) ? sanitize_text_field($_GET['page']) : '';

        if (empty($current_page) || strpos($current_page, '-dashboard') === false) {
            return;
        }

        // No cargar en el Dashboard Unificado (tiene sus propios estilos completos)
        if ($current_page === 'flavor-unified-dashboard') {
            return;
        }

        $version = defined('FLAVOR_CHAT_IA_VERSION') ? FLAVOR_CHAT_IA_VERSION : '4.1.0';
        $plugin_url = FLAVOR_CHAT_IA_URL;

        // =====================================================================
        // CSS - Sistema de Diseño Unificado (v4.1.0)
        // =====================================================================

        // 1. Design Tokens (variables CSS base)
        wp_enqueue_style(
            'fl-design-tokens',
            $plugin_url . 'assets/css/design-tokens.css',
            [],
            $version
        );

        // 2. Compatibilidad con variables antiguas
        wp_enqueue_style(
            'fl-design-tokens-compat',
            $plugin_url . 'assets/css/design-tokens-compat.css',
            ['fl-design-tokens'],
            $version
        );

        // 3. CSS Base compartido para dashboards de modulos
        wp_enqueue_style(
            'flavor-module-dashboard-base',
            $plugin_url . 'assets/css/dashboard-base.css',
            ['fl-design-tokens-compat'],
            $version
        );

        // 4. Widgets
        wp_enqueue_style(
            'fl-dashboard-widgets',
            $plugin_url . 'assets/css/dashboard-widgets.css',
            ['flavor-module-dashboard-base'],
            $version
        );

        // 5. Estados
        wp_enqueue_style(
            'fl-dashboard-states',
            $plugin_url . 'assets/css/dashboard-states.css',
            ['fl-dashboard-widgets'],
            $version
        );

        // 6. Accesibilidad
        wp_enqueue_style(
            'fl-dashboard-a11y',
            $plugin_url . 'assets/css/dashboard-a11y.css',
            ['fl-dashboard-widgets'],
            $version
        );

        // 7. Responsive
        wp_enqueue_style(
            'fl-dashboard-responsive',
            $plugin_url . 'assets/css/dashboard-responsive.css',
            ['fl-dashboard-widgets'],
            $version
        );

        // 8. Breadcrumbs
        wp_enqueue_style(
            'fl-breadcrumbs',
            $plugin_url . 'assets/css/breadcrumbs.css',
            ['fl-design-tokens'],
            $version
        );

        // 9. Componentes
        wp_enqueue_style(
            'flavor-module-dashboard-components',
            $plugin_url . 'assets/css/dashboard-components.css',
            ['fl-dashboard-responsive'],
            $version
        );

        // 10. Estilos adicionales para dashboards de modulos
        if (file_exists(FLAVOR_CHAT_IA_PATH . 'admin/css/module-dashboard.css')) {
            wp_enqueue_style(
                'flavor-module-dashboard',
                $plugin_url . 'admin/css/module-dashboard.css',
                ['flavor-module-dashboard-components'],
                $version
            );
        }
    }

    /**
     * Renderiza boton para volver al Dashboard Unificado
     */
    public function render_back_to_dashboard_button() {
        $current_page = isset($_GET['page']) ? sanitize_text_field($_GET['page']) : '';

        // Solo mostrar en dashboards de modulos
        if (empty($current_page) || strpos($current_page, '-dashboard') === false) {
            return;
        }

        // No mostrar en el Dashboard Unificado ni en el principal
        if ($current_page === 'flavor-unified-dashboard' || $current_page === 'flavor-dashboard') {
            return;
        }

        $dashboard_url = admin_url('admin.php?page=flavor-unified-dashboard');
        ?>
        <div class="flavor-back-to-dashboard">
            <a href="<?php echo esc_url($dashboard_url); ?>" class="flavor-back-btn">
                <span class="dashicons dashicons-arrow-left-alt"></span>
                <?php esc_html_e('Volver al Dashboard', 'flavor-chat-ia'); ?>
            </a>
        </div>
        <style>
            .flavor-back-to-dashboard {
                position: fixed;
                bottom: 20px;
                left: 180px;
                z-index: 9999;
            }
            body.folded .flavor-back-to-dashboard {
                left: 56px;
            }
            @media (max-width: 782px) {
                .flavor-back-to-dashboard {
                    left: 20px;
                }
            }
            .flavor-back-btn {
                display: inline-flex;
                align-items: center;
                gap: 8px;
                padding: 12px 20px;
                background: #4f46e5;
                color: #fff;
                text-decoration: none;
                border-radius: 8px;
                font-size: 14px;
                font-weight: 500;
                box-shadow: 0 4px 12px rgba(79, 70, 229, 0.4);
                transition: all 0.2s;
            }
            .flavor-back-btn:hover {
                background: #4338ca;
                color: #fff;
                transform: translateY(-2px);
                box-shadow: 0 6px 16px rgba(79, 70, 229, 0.5);
            }
            .flavor-back-btn .dashicons {
                font-size: 18px;
                width: 18px;
                height: 18px;
            }
        </style>
        <?php
    }

    public function callback_app_composer() {
        if (class_exists('Flavor_App_Profile_Admin')) {
            Flavor_App_Profile_Admin::get_instance()->renderizar_pagina_perfil();
        }
    }

    public function callback_design_settings() {
        if (class_exists('Flavor_Design_Settings')) {
            Flavor_Design_Settings::get_instance()->render_settings_page();
        }
    }

    public function callback_pages() {
        if (class_exists('Flavor_Pages_Admin')) {
            Flavor_Pages_Admin::get_instance()->render_admin_page();
        }
    }

    public function callback_landing_editor() {
        if (class_exists('Flavor_Landing_Editor')) {
            Flavor_Landing_Editor::get_instance()->render_editor_page();
        }
    }

    public function callback_permissions() {
        if (class_exists('Flavor_Permissions_Admin')) {
            Flavor_Permissions_Admin::get_instance()->render_pagina();
        }
    }

    public function callback_chat_settings() {
        if (class_exists('Flavor_Chat_Settings')) {
            Flavor_Chat_Settings::get_instance()->render_settings_page();
        }
    }

    public function callback_escalations() {
        if (class_exists('Flavor_Chat_Settings')) {
            Flavor_Chat_Settings::get_instance()->render_escalations_page();
        }
    }

    public function callback_apps_config() {
        if (class_exists('Flavor_App_Config_Admin')) {
            Flavor_App_Config_Admin::get_instance()->render_page();
        }
    }

    public function callback_deep_links() {
        if (class_exists('Flavor_Deep_Link_Manager')) {
            Flavor_Deep_Link_Manager::get_instance()->render_admin_page();
        }
    }

    public function callback_network() {
        if (class_exists('Flavor_Network_Admin')) {
            Flavor_Network_Admin::get_instance()->render_main_page();
        } elseif (file_exists(FLAVOR_CHAT_IA_PATH . 'includes/network/class-network-admin.php')) {
            require_once FLAVOR_CHAT_IA_PATH . 'includes/network/class-network-admin.php';
            if (class_exists('Flavor_Network_Admin')) {
                Flavor_Network_Admin::get_instance()->render_main_page();
            }
        }
    }

    public function callback_addons() {
        if (class_exists('Flavor_Addon_Admin')) {
            Flavor_Addon_Admin::get_instance()->render_addons_page();
        }
    }

    public function callback_marketplace() {
        if (class_exists('Flavor_Addon_Marketplace')) {
            Flavor_Addon_Marketplace::get_instance()->render_marketplace_page();
        }
    }

    public function callback_newsletter() {
        if (class_exists('Flavor_Newsletter_Admin')) {
            Flavor_Newsletter_Admin::get_instance()->callback_pagina_principal();
        }
    }

    public function callback_export_import() {
        if (class_exists('Flavor_Export_Import')) {
            Flavor_Export_Import::get_instance()->renderizar_pagina();
        }
    }

    public function callback_health_check() {
        if (class_exists('Flavor_Health_Check')) {
            Flavor_Health_Check::get_instance()->render_page();
        }
    }

    public function callback_activity_log() {
        if (class_exists('Flavor_Activity_Log_Page')) {
            Flavor_Activity_Log_Page::get_instance()->renderizar_pagina();
        }
    }

    public function callback_api_docs() {
        if (class_exists('Flavor_API_Documentation')) {
            Flavor_API_Documentation::get_instance()->render_page();
        }
    }

    public function callback_systems_panel() {
        if (class_exists('Flavor_Systems_Admin_Panel')) {
            Flavor_Systems_Admin_Panel::get_instance()->render_admin_page();
        } else {
            // Intentar cargar la clase
            $panel_path = FLAVOR_CHAT_IA_PATH . 'admin/class-flavor-systems-admin-panel.php';
            if (file_exists($panel_path)) {
                require_once $panel_path;
                if (class_exists('Flavor_Systems_Admin_Panel')) {
                    Flavor_Systems_Admin_Panel::get_instance()->render_admin_page();
                    return;
                }
            }
            echo '<div class="wrap"><h1>' . esc_html__('Panel de Sistemas', 'flavor-chat-ia') . '</h1>';
            echo '<p>' . esc_html__('Error al cargar el Panel de Sistemas.', 'flavor-chat-ia') . '</p></div>';
        }
    }

    public function callback_documentation() {
        if (class_exists('Flavor_Documentation_Admin')) {
            Flavor_Documentation_Admin::get_instance()->renderizar_pagina();
        }
    }

    public function callback_tours() {
        if (class_exists('Flavor_Guided_Tours')) {
            Flavor_Guided_Tours::get_instance()->render_tours_panel();
        }
    }

    public function callback_app_cpts() {
        if (class_exists('Flavor_App_CPT_Settings')) {
            Flavor_App_CPT_Settings::get_instance()->render_page();
        }
    }

    public function callback_layouts() {
        if (class_exists('Flavor_Layout_Admin')) {
            Flavor_Layout_Admin::get_instance()->render_admin_page();
        }
    }

    public function callback_newsletter_editor() {
        if (class_exists('Flavor_Newsletter_Admin')) {
            Flavor_Newsletter_Admin::get_instance()->renderizar_pagina_editor();
        }
    }

    public function callback_setup_wizard() {
        if (class_exists('Flavor_Setup_Wizard')) {
            Flavor_Setup_Wizard::get_instance()->render_wizard();
        }
    }
}

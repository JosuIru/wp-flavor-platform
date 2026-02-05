<?php
/**
 * Gestor Centralizado del Menú Admin de Flavor Platform
 *
 * Registra TODOS los submenús en un solo hook admin_menu.
 * Las clases individuales ya no registran sus propios menús.
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
     *
     * @var Flavor_Admin_Menu_Manager|null
     */
    private static $instancia = null;

    /**
     * Slug del menú principal
     */
    const MENU_SLUG = 'flavor-chat-ia';

    /**
     * Obtiene la instancia singleton
     *
     * @return Flavor_Admin_Menu_Manager
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
        add_action('admin_menu', [$this, 'registrar_menus'], 5);
        add_action('admin_enqueue_scripts', [$this, 'encolar_assets_globales']);
    }

    /**
     * Registra todos los menús de Flavor Platform
     *
     * @return void
     */
    public function registrar_menus() {
        // ── Menú principal de nivel superior ──
        add_menu_page(
            __('Flavor Platform', 'flavor-chat-ia'),
            __('Flavor Platform', 'flavor-chat-ia'),
            'manage_options',
            self::MENU_SLUG,
            [$this, 'callback_dashboard'],
            'dashicons-superhero-alt',
            30
        );

        // ── Dashboard (pos 0) ──
        add_submenu_page(
            self::MENU_SLUG,
            __('Dashboard', 'flavor-chat-ia'),
            __('Dashboard', 'flavor-chat-ia'),
            'manage_options',
            'flavor-dashboard',
            [$this, 'callback_dashboard'],
            0
        );

        // Eliminar el submenú duplicado que WP crea automáticamente
        remove_submenu_page(self::MENU_SLUG, self::MENU_SLUG);

        // ════════════════════════════════════════
        // ── Separador: Mi App (pos 10) ──
        // ════════════════════════════════════════
        $this->agregar_separador(__('Mi App', 'flavor-chat-ia'), 10);

        // Compositor & Módulos (pos 11)
        add_submenu_page(
            self::MENU_SLUG,
            __('Compositor & Módulos', 'flavor-chat-ia'),
            __('Compositor & Módulos', 'flavor-chat-ia'),
            'manage_options',
            'flavor-app-composer',
            [$this, 'callback_app_composer'],
            11
        );

        // Diseño (pos 12)
        add_submenu_page(
            self::MENU_SLUG,
            __('Diseño y Apariencia', 'flavor-chat-ia'),
            __('Diseño', 'flavor-chat-ia'),
            'manage_options',
            'flavor-design-settings',
            [$this, 'callback_design_settings'],
            12
        );

        // Páginas (pos 13)
        add_submenu_page(
            self::MENU_SLUG,
            __('Crear Páginas', 'flavor-chat-ia'),
            __('Páginas', 'flavor-chat-ia'),
            'manage_options',
            'flavor-create-pages',
            [$this, 'callback_pages'],
            13
        );

        // ════════════════════════════════════════
        // ── Separador: Chat IA (pos 20) ──
        // ════════════════════════════════════════
        $this->agregar_separador(__('Chat IA', 'flavor-chat-ia'), 20);

        // Configuración (pos 21)
        add_submenu_page(
            self::MENU_SLUG,
            __('Configuración', 'flavor-chat-ia'),
            __('Configuración', 'flavor-chat-ia'),
            'manage_options',
            'flavor-chat-config',
            [$this, 'callback_chat_settings'],
            21
        );

        // Escalados (pos 22)
        add_submenu_page(
            self::MENU_SLUG,
            __('Escalados', 'flavor-chat-ia'),
            __('Escalados', 'flavor-chat-ia'),
            'manage_options',
            'flavor-chat-ia-escalations',
            [$this, 'callback_escalations'],
            22
        );

        // ════════════════════════════════════════
        // ── Separador: Extensiones (pos 30) ──
        // ════════════════════════════════════════
        $this->agregar_separador(__('Extensiones', 'flavor-chat-ia'), 30);

        // Addons (pos 31)
        add_submenu_page(
            self::MENU_SLUG,
            __('Addons', 'flavor-chat-ia'),
            __('Addons', 'flavor-chat-ia'),
            'manage_options',
            'flavor-addons',
            [$this, 'callback_addons'],
            31
        );

        // Marketplace (pos 32)
        add_submenu_page(
            self::MENU_SLUG,
            __('Marketplace', 'flavor-chat-ia'),
            __('Marketplace', 'flavor-chat-ia'),
            'manage_options',
            'flavor-marketplace',
            [$this, 'callback_marketplace'],
            32
        );
        // Newsletter (pos 33)
        add_submenu_page(
            self::MENU_SLUG,
            __('Newsletter', 'flavor-chat-ia'),
            __('Newsletter', 'flavor-chat-ia'),
            'manage_options',
            'flavor-newsletter',
            [$this, 'callback_newsletter'],
            33
        );


        // ════════════════════════════════════════
        // ── Separador: Herramientas (pos 40) ──
        // ════════════════════════════════════════
        $this->agregar_separador(__('Herramientas', 'flavor-chat-ia'), 40);

        // Export / Import (pos 41)
        add_submenu_page(
            self::MENU_SLUG,
            __('Exportar / Importar', 'flavor-chat-ia'),
            __('Export / Import', 'flavor-chat-ia'),
            'manage_options',
            'flavor-export-import',
            [$this, 'callback_export_import'],
            41
        );

        // Diagnóstico (pos 42)
        add_submenu_page(
            self::MENU_SLUG,
            __('Diagnóstico', 'flavor-chat-ia'),
            __('Diagnóstico', 'flavor-chat-ia'),
            'manage_options',
            'flavor-health-check',
            [$this, 'callback_health_check'],
            42
        );

        // Actividad (pos 43)
        add_submenu_page(
            self::MENU_SLUG,
            __('Registro de Actividad', 'flavor-chat-ia'),
            __('Actividad', 'flavor-chat-ia'),
            'manage_options',
            'flavor-activity-log',
            [$this, 'callback_activity_log'],
            43
        );

        // ════════════════════════════════════════
        // ── Separador: Ayuda (pos 50) ──
        // ════════════════════════════════════════
        $this->agregar_separador(__('Ayuda', 'flavor-chat-ia'), 50);

        // Documentación (pos 51)
        add_submenu_page(
            self::MENU_SLUG,
            __('Documentación', 'flavor-chat-ia'),
            __('Documentación', 'flavor-chat-ia'),
            'manage_options',
            'flavor-documentation',
            [$this, 'callback_documentation'],
            51
        );

        // ════════════════════════════════════════
        // ── Páginas ocultas (sin parent) ──
        // ════════════════════════════════════════

        // Contenido Apps (hidden page)
        add_submenu_page(
            null,
            __('Contenido de Apps', 'flavor-chat-ia'),
            __('Contenido Apps', 'flavor-chat-ia'),
            'manage_options',
            'flavor-app-cpts',
            [$this, 'callback_app_cpts']
        );

        // Layouts (hidden page - accesible desde Diseño)
        add_submenu_page(
            null,
            __('Layouts y Diseño', 'flavor-chat-ia'),
            __('Layouts', 'flavor-chat-ia'),
            'manage_options',
            'flavor-layouts',
            [$this, 'callback_layouts']
        );

        // Newsletter Editor (hidden page)
        add_submenu_page(
            null,
            __('Editor de Campana', 'flavor-chat-ia'),
            __('Editor Newsletter', 'flavor-chat-ia'),
            'manage_options',
            'flavor-newsletter-editor',
            [$this, 'callback_newsletter_editor']
        );

        // Redirigir el menú principal al dashboard
        global $submenu;
        if (isset($submenu[self::MENU_SLUG])) {
            foreach ($submenu[self::MENU_SLUG] as &$item_submenu) {
                if ($item_submenu[2] === self::MENU_SLUG) {
                    $item_submenu[2] = 'flavor-dashboard';
                    break;
                }
            }
        }
    }

    /**
     * Agrega un separador visual al submenú
     *
     * @param string $etiqueta Texto del separador
     * @param int    $posicion Posición en el menú
     * @return void
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
     * Encola assets globales para páginas de Flavor
     *
     * @param string $sufijo_hook Hook suffix de la página actual
     * @return void
     */
    public function encolar_assets_globales($sufijo_hook) {
        $sufijo_hook = (string) $sufijo_hook;
        // Solo en páginas de Flavor Platform
        if (strpos($sufijo_hook, 'flavor') === false &&
            strpos($sufijo_hook, self::MENU_SLUG) === false) {
            return;
        }

        $sufijo_asset = defined('WP_DEBUG') && WP_DEBUG ? '' : '.min';

        // Design tokens CSS
        wp_enqueue_style(
            'flavor-design-tokens',
            FLAVOR_CHAT_IA_URL . "admin/css/design-tokens{$sufijo_asset}.css",
            [],
            FLAVOR_CHAT_IA_VERSION
        );

        // Admin CSS principal
        wp_enqueue_style(
            'flavor-admin',
            FLAVOR_CHAT_IA_URL . "admin/css/admin{$sufijo_asset}.css",
            ['flavor-design-tokens'],
            FLAVOR_CHAT_IA_VERSION
        );

        // CSS para separadores del menú (siempre)
        $css_separadores = "
            .flavor-menu-separator {
                display: block;
                padding: 8px 0 4px;
                margin: 4px 0 0;
                font-size: 11px;
                font-weight: 600;
                text-transform: uppercase;
                letter-spacing: 0.5px;
                color: #a7aaad;
                pointer-events: none;
                cursor: default;
                border-top: 1px solid rgba(255, 255, 255, 0.1);
            }
            #adminmenu .wp-submenu a[href*='flavor-separator-'] {
                pointer-events: none;
                cursor: default;
            }
            #adminmenu .wp-submenu a[href*='flavor-separator-']:hover {
                background: transparent !important;
                color: #a7aaad !important;
            }
        ";
        wp_add_inline_style('wp-admin', $css_separadores);
    }

    // ════════════════════════════════════════════════════════════
    // Callbacks: delegan a la clase correspondiente
    // ════════════════════════════════════════════════════════════

    /**
     * Callback: Dashboard
     */
    public function callback_dashboard() {
        if (class_exists('Flavor_Dashboard')) {
            Flavor_Dashboard::get_instance()->render_dashboard_page();
        }
    }

    /**
     * Callback: App Composer (antes Perfil App)
     */
    public function callback_app_composer() {
        if (class_exists('Flavor_App_Profile_Admin')) {
            Flavor_App_Profile_Admin::get_instance()->renderizar_pagina_perfil();
        }
    }

    /**
     * Callback: Design Settings
     */
    public function callback_design_settings() {
        if (class_exists('Flavor_Design_Settings')) {
            Flavor_Design_Settings::get_instance()->render_settings_page();
        }
    }

    /**
     * Callback: Pages Admin
     */
    public function callback_pages() {
        if (class_exists('Flavor_Pages_Admin')) {
            Flavor_Pages_Admin::get_instance()->render_admin_page();
        }
    }

    /**
     * Callback: Chat Settings (Configuración)
     */
    public function callback_chat_settings() {
        if (class_exists('Flavor_Chat_Settings')) {
            Flavor_Chat_Settings::get_instance()->render_settings_page();
        }
    }

    /**
     * Callback: Escalations
     */
    public function callback_escalations() {
        if (class_exists('Flavor_Chat_Settings')) {
            Flavor_Chat_Settings::get_instance()->render_escalations_page();
        }
    }

    /**
     * Callback: Addons
     */
    public function callback_addons() {
        if (class_exists('Flavor_Addon_Admin')) {
            Flavor_Addon_Admin::get_instance()->render_addons_page();
        }
    }

    /**
     * Callback: Marketplace
     */
    public function callback_marketplace() {
        if (class_exists('Flavor_Addon_Marketplace')) {
            Flavor_Addon_Marketplace::get_instance()->render_marketplace_page();
        }
    }

    /**
     * Callback: Export / Import
     */
    public function callback_export_import() {
        if (class_exists('Flavor_Export_Import')) {
            Flavor_Export_Import::get_instance()->renderizar_pagina();
        }
    }

    /**
     * Callback: Health Check
     */
    public function callback_health_check() {
        if (class_exists('Flavor_Health_Check')) {
            Flavor_Health_Check::get_instance()->render_page();
        }
    }

    /**
     * Callback: Activity Log
     */
    public function callback_activity_log() {
        if (class_exists('Flavor_Activity_Log_Page')) {
            Flavor_Activity_Log_Page::get_instance()->renderizar_pagina();
        }
    }

    /**
     * Callback: App CPT Settings (hidden)
     */
    public function callback_app_cpts() {
        if (class_exists('Flavor_App_CPT_Settings')) {
            Flavor_App_CPT_Settings::get_instance()->render_page();
        }
    }

    /**
     * Callback: Layouts (hidden)
     */
    public function callback_layouts() {
        if (class_exists('Flavor_Layout_Admin')) {
            Flavor_Layout_Admin::get_instance()->render_admin_page();
        }
    }

    /**
     * Callback: Newsletter
     */
    public function callback_newsletter() {
        if (class_exists('Flavor_Newsletter_Admin')) {
            Flavor_Newsletter_Admin::get_instance()->callback_pagina_principal();
        }
    }

    /**
     * Callback: Newsletter Editor (hidden)
     */
    public function callback_newsletter_editor() {
        if (class_exists('Flavor_Newsletter_Admin')) {
            Flavor_Newsletter_Admin::get_instance()->renderizar_pagina_editor();
        }
    }

    /**
     * Callback: Documentation
     */
    public function callback_documentation() {
        if (class_exists('Flavor_Documentation_Admin')) {
            Flavor_Documentation_Admin::get_instance()->renderizar_pagina();
        }
    }
}
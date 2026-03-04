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
        'flavor-',
        'gc-',
        'pp-',
        'bt-',
        'hu-',
        'part-',
        'socios',
        'comunidades',
        'colectivos',
        'foros',
        'eventos',
        'cursos',
        'talleres',
        'reservas',
        'tramites',
        'incidencias',
        'marketplace',
        'ayuda-',
        'banco-tiempo',
        'huertos',
        'espacios',
        'biblioteca',
        'carpooling',
        'bicicletas',
        'reciclaje',
        'compostaje',
        'multimedia',
        'podcast',
        'radio',
        'campanias',
        'encuestas',
        'economia-don',
        'suficiencia',
        'energia',
        'actores',
        'documentos',
        'transparencia',
        'denuncias',
        'advertising',
        'avisos',
        'fichaje',
        'parkings',
        'biodiversidad',
        'circulos-cuidados',
        'huella-ecologica',
        'justicia-restaurativa',
        'saberes',
        'sello-conciencia',
        'trabajo-digno',
        'trading-ia',
        'recetas',
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
     * Verifica si el shell está habilitado para el usuario actual
     *
     * @return bool
     */
    public function is_shell_enabled() {
        $user_id = get_current_user_id();
        if (!$user_id) {
            return false;
        }

        // Verificar si el usuario lo ha deshabilitado
        $is_disabled = get_user_meta($user_id, self::USER_META_KEY, true);

        return !$is_disabled;
    }

    /**
     * Verifica si el shell debe mostrarse
     *
     * @return bool
     */
    public function should_show_shell() {
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
        wp_enqueue_style(
            'flavor-admin-shell',
            FLAVOR_CHAT_IA_URL . 'admin/css/admin-shell.css',
            [],
            FLAVOR_CHAT_IA_VERSION
        );

        // Alpine.js (si no está ya cargado)
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

        // JS del shell
        wp_enqueue_script(
            'flavor-admin-shell',
            FLAVOR_CHAT_IA_URL . 'admin/js/admin-shell.js',
            ['alpine'],
            FLAVOR_CHAT_IA_VERSION,
            true
        );

        // Localizar datos
        wp_localize_script('flavor-admin-shell', 'flavorAdminShell', [
            'ajaxUrl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('flavor_admin_shell'),
            'nonceVista' => wp_create_nonce('flavor_cambiar_vista'),
            'currentPage' => isset($_GET['page']) ? sanitize_text_field($_GET['page']) : '',
            'dashboardUrl' => admin_url('admin.php?page=flavor-dashboard'),
            'wpDashboardUrl' => admin_url(),
            'i18n' => [
                'collapse' => __('Colapsar menú', 'flavor-chat-ia'),
                'expand' => __('Expandir menú', 'flavor-chat-ia'),
                'backToWP' => __('Volver a WordPress', 'flavor-chat-ia'),
                'disableShell' => __('Desactivar shell', 'flavor-chat-ia'),
                'darkMode' => __('Modo oscuro', 'flavor-chat-ia'),
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
     * Obtener estructura de navegación
     *
     * Filtra los menús según la vista activa del usuario
     *
     * @return array
     */
    public function get_navigation_structure() {
        // Estructura completa de navegación
        $estructura_completa = [
            'mi_app' => [
                'label' => __('Mi App', 'flavor-chat-ia'),
                'icon' => 'dashicons-admin-home',
                'items' => [
                    ['slug' => 'flavor-dashboard', 'label' => __('Dashboard', 'flavor-chat-ia'), 'icon' => 'dashicons-dashboard'],
                    ['slug' => 'flavor-unified-dashboard', 'label' => __('Widgets', 'flavor-chat-ia'), 'icon' => 'dashicons-grid-view'],
                    ['slug' => 'flavor-module-dashboards', 'label' => __('Módulos', 'flavor-chat-ia'), 'icon' => 'dashicons-screenoptions'],
                    ['slug' => 'flavor-design-settings', 'label' => __('Diseño', 'flavor-chat-ia'), 'icon' => 'dashicons-art'],
                    ['slug' => 'flavor-create-pages', 'label' => __('Páginas', 'flavor-chat-ia'), 'icon' => 'dashicons-admin-page'],
                    ['slug' => 'flavor-landing-editor', 'label' => __('Editor Visual', 'flavor-chat-ia'), 'icon' => 'dashicons-edit'],
                ],
            ],
            'mod_comunidad' => [
                'label' => __('Comunidad', 'flavor-chat-ia'),
                'icon' => 'dashicons-groups',
                'items' => [
                    ['slug' => 'socios-dashboard', 'label' => __('Socios', 'flavor-chat-ia'), 'icon' => 'dashicons-id-alt'],
                    ['slug' => 'flavor-colectivos-dashboard', 'label' => __('Colectivos', 'flavor-chat-ia'), 'icon' => 'dashicons-networking'],
                    ['slug' => 'comunidades-dashboard', 'label' => __('Comunidades', 'flavor-chat-ia'), 'icon' => 'dashicons-admin-multisite'],
                    ['slug' => 'foros-dashboard', 'label' => __('Foros', 'flavor-chat-ia'), 'icon' => 'dashicons-format-chat'],
                    ['slug' => 'flavor-red-social-dashboard', 'label' => __('Red Social', 'flavor-chat-ia'), 'icon' => 'dashicons-share'],
                ],
            ],
            'mod_economia' => [
                'label' => __('Economía', 'flavor-chat-ia'),
                'icon' => 'dashicons-money-alt',
                'items' => [
                    ['slug' => 'gc-dashboard', 'label' => __('Grupos Consumo', 'flavor-chat-ia'), 'icon' => 'dashicons-cart'],
                    ['slug' => 'marketplace-dashboard', 'label' => __('Marketplace', 'flavor-chat-ia'), 'icon' => 'dashicons-store'],
                    ['slug' => 'banco-tiempo-dashboard', 'label' => __('Banco Tiempo', 'flavor-chat-ia'), 'icon' => 'dashicons-clock'],
                    ['slug' => 'economia-don-dashboard', 'label' => __('Economía Don', 'flavor-chat-ia'), 'icon' => 'dashicons-heart'],
                ],
            ],
            'mod_actividades' => [
                'label' => __('Actividades', 'flavor-chat-ia'),
                'icon' => 'dashicons-calendar-alt',
                'items' => [
                    ['slug' => 'eventos-dashboard', 'label' => __('Eventos', 'flavor-chat-ia'), 'icon' => 'dashicons-calendar'],
                    ['slug' => 'cursos-dashboard', 'label' => __('Cursos', 'flavor-chat-ia'), 'icon' => 'dashicons-welcome-learn-more'],
                    ['slug' => 'talleres-dashboard', 'label' => __('Talleres', 'flavor-chat-ia'), 'icon' => 'dashicons-hammer'],
                    ['slug' => 'reservas-dashboard', 'label' => __('Reservas', 'flavor-chat-ia'), 'icon' => 'dashicons-tickets-alt'],
                ],
            ],
            'mod_servicios' => [
                'label' => __('Servicios', 'flavor-chat-ia'),
                'icon' => 'dashicons-admin-tools',
                'items' => [
                    ['slug' => 'tramites-dashboard', 'label' => __('Trámites', 'flavor-chat-ia'), 'icon' => 'dashicons-clipboard'],
                    ['slug' => 'incidencias-dashboard', 'label' => __('Incidencias', 'flavor-chat-ia'), 'icon' => 'dashicons-warning'],
                    ['slug' => 'ayuda-dashboard', 'label' => __('Ayuda Vecinal', 'flavor-chat-ia'), 'icon' => 'dashicons-sos'],
                    ['slug' => 'participacion-dashboard', 'label' => __('Participación', 'flavor-chat-ia'), 'icon' => 'dashicons-megaphone'],
                ],
            ],
            'mod_recursos' => [
                'label' => __('Recursos', 'flavor-chat-ia'),
                'icon' => 'dashicons-building',
                'items' => [
                    ['slug' => 'huertos-dashboard', 'label' => __('Huertos', 'flavor-chat-ia'), 'icon' => 'dashicons-carrot'],
                    ['slug' => 'espacios-dashboard', 'label' => __('Espacios', 'flavor-chat-ia'), 'icon' => 'dashicons-building'],
                    ['slug' => 'biblioteca-dashboard', 'label' => __('Biblioteca', 'flavor-chat-ia'), 'icon' => 'dashicons-book-alt'],
                    ['slug' => 'carpooling-dashboard', 'label' => __('Carpooling', 'flavor-chat-ia'), 'icon' => 'dashicons-car'],
                ],
            ],
            'mod_sostenibilidad' => [
                'label' => __('Sostenibilidad', 'flavor-chat-ia'),
                'icon' => 'dashicons-palmtree',
                'items' => [
                    ['slug' => 'reciclaje-dashboard', 'label' => __('Reciclaje', 'flavor-chat-ia'), 'icon' => 'dashicons-image-rotate'],
                    ['slug' => 'compostaje-dashboard', 'label' => __('Compostaje', 'flavor-chat-ia'), 'icon' => 'dashicons-carrot'],
                    ['slug' => 'flavor-energia-dashboard', 'label' => __('Energía', 'flavor-chat-ia'), 'icon' => 'dashicons-lightbulb'],
                    ['slug' => 'bicicletas-dashboard', 'label' => __('Bicicletas', 'flavor-chat-ia'), 'icon' => 'dashicons-performance'],
                ],
            ],
            'mod_comunicacion' => [
                'label' => __('Comunicación', 'flavor-chat-ia'),
                'icon' => 'dashicons-megaphone',
                'items' => [
                    ['slug' => 'multimedia-dashboard', 'label' => __('Multimedia', 'flavor-chat-ia'), 'icon' => 'dashicons-format-video'],
                    ['slug' => 'flavor-radio-dashboard', 'label' => __('Radio', 'flavor-chat-ia'), 'icon' => 'dashicons-controls-volumeon'],
                    ['slug' => 'podcast-dashboard', 'label' => __('Podcast', 'flavor-chat-ia'), 'icon' => 'dashicons-microphone'],
                    ['slug' => 'campanias-dashboard', 'label' => __('Campañas', 'flavor-chat-ia'), 'icon' => 'dashicons-email-alt'],
                ],
            ],
            'chat_ia' => [
                'label' => __('Chat IA', 'flavor-chat-ia'),
                'icon' => 'dashicons-format-chat',
                'items' => [
                    ['slug' => 'flavor-chat-config', 'label' => __('Configuración', 'flavor-chat-ia'), 'icon' => 'dashicons-admin-settings'],
                    ['slug' => 'flavor-chat-ia-escalations', 'label' => __('Escalados', 'flavor-chat-ia'), 'icon' => 'dashicons-warning'],
                ],
            ],
            'apps' => [
                'label' => __('Apps', 'flavor-chat-ia'),
                'icon' => 'dashicons-smartphone',
                'items' => [
                    ['slug' => 'flavor-apps-config', 'label' => __('Apps Móviles', 'flavor-chat-ia'), 'icon' => 'dashicons-smartphone'],
                    ['slug' => 'flavor-deep-links', 'label' => __('Deep Links', 'flavor-chat-ia'), 'icon' => 'dashicons-admin-links'],
                    ['slug' => 'flavor-network', 'label' => __('Red', 'flavor-chat-ia'), 'icon' => 'dashicons-networking'],
                ],
            ],
            'extensiones' => [
                'label' => __('Extensiones', 'flavor-chat-ia'),
                'icon' => 'dashicons-plugins-checked',
                'items' => [
                    ['slug' => 'flavor-addons', 'label' => __('Addons', 'flavor-chat-ia'), 'icon' => 'dashicons-admin-plugins'],
                    ['slug' => 'flavor-marketplace', 'label' => __('Marketplace', 'flavor-chat-ia'), 'icon' => 'dashicons-store'],
                    ['slug' => 'flavor-newsletter', 'label' => __('Newsletter', 'flavor-chat-ia'), 'icon' => 'dashicons-email'],
                ],
            ],
            'herramientas' => [
                'label' => __('Herramientas', 'flavor-chat-ia'),
                'icon' => 'dashicons-admin-tools',
                'items' => [
                    ['slug' => 'flavor-export-import', 'label' => __('Export/Import', 'flavor-chat-ia'), 'icon' => 'dashicons-migrate'],
                    ['slug' => 'flavor-health-check', 'label' => __('Diagnóstico', 'flavor-chat-ia'), 'icon' => 'dashicons-heart'],
                    ['slug' => 'flavor-activity-log', 'label' => __('Actividad', 'flavor-chat-ia'), 'icon' => 'dashicons-backup'],
                    ['slug' => 'flavor-api-docs', 'label' => __('API Docs', 'flavor-chat-ia'), 'icon' => 'dashicons-rest-api'],
                ],
            ],
            'ayuda' => [
                'label' => __('Ayuda', 'flavor-chat-ia'),
                'icon' => 'dashicons-editor-help',
                'items' => [
                    ['slug' => 'flavor-documentation', 'label' => __('Documentación', 'flavor-chat-ia'), 'icon' => 'dashicons-book'],
                    ['slug' => 'flavor-tours', 'label' => __('Tours', 'flavor-chat-ia'), 'icon' => 'dashicons-location'],
                ],
            ],
        ];

        // Obtener el menu manager para filtrar según vista
        if (!class_exists('Flavor_Admin_Menu_Manager')) {
            return $estructura_completa;
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

        return $estructura_filtrada;
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
            wp_send_json_error(['message' => __('Usuario no autenticado', 'flavor-chat-ia')]);
        }

        $action = isset($_POST['shell_action']) ? sanitize_text_field($_POST['shell_action']) : '';

        if ($action === 'disable') {
            update_user_meta($user_id, self::USER_META_KEY, '1');
            wp_send_json_success(['message' => __('Shell desactivado', 'flavor-chat-ia')]);
        } elseif ($action === 'enable') {
            delete_user_meta($user_id, self::USER_META_KEY);
            wp_send_json_success(['message' => __('Shell activado', 'flavor-chat-ia')]);
        }

        wp_send_json_error(['message' => __('Acción no válida', 'flavor-chat-ia')]);
    }
}

// Inicializar
Flavor_Admin_Shell::get_instance();

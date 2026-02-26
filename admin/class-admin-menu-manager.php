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
     * Vistas disponibles del menú
     */
    const VISTA_ADMIN = 'admin';
    const VISTA_GESTOR_GRUPOS = 'gestor_grupos';

    /**
     * Meta key para almacenar la vista activa del usuario
     */
    const META_VISTA_ACTIVA = 'flavor_admin_vista_activa';

    /**
     * Menús visibles para cada vista
     *
     * @var array
     */
    private $menus_por_vista = [];

    /**
     * Vista activa actual
     *
     * @var string
     */
    private $vista_activa = null;

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
        // Inicializar configuración de menús por vista
        $this->inicializar_menus_por_vista();

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

        // Selector de vista en admin bar
        add_action('admin_bar_menu', [$this, 'agregar_selector_vista'], 100);

        // AJAX para cambiar de vista
        add_action('wp_ajax_flavor_cambiar_vista_admin', [$this, 'ajax_cambiar_vista']);

        // AJAX para guardar configuración de menús por vista
        add_action('wp_ajax_flavor_guardar_config_vistas', [$this, 'ajax_guardar_config_vistas']);

        // CSS/JS para el selector de vista
        add_action('admin_head', [$this, 'encolar_css_selector_vista']);

        // Registrar rol de gestor de grupos si no existe
        add_action('init', [$this, 'registrar_rol_gestor_grupos'], 5);
    }

    /**
     * Registra el rol de Gestor de Grupos si no existe
     */
    public function registrar_rol_gestor_grupos() {
        // Verificar si el rol ya existe
        $rol = get_role('flavor_gestor_grupos');

        if (!$rol) {
            // Crear el rol basado en 'editor' pero con capacidades limitadas
            add_role(
                'flavor_gestor_grupos',
                __('Gestor de Grupos', 'flavor-chat-ia'),
                [
                    'read'                   => true,
                    'edit_posts'             => true,
                    'edit_published_posts'   => true,
                    'publish_posts'          => true,
                    'delete_posts'           => true,
                    'upload_files'           => true,
                    'moderate_comments'      => true,
                    // Capacidades específicas de Flavor
                    'flavor_gestor_grupos'   => true,
                    'flavor_ver_dashboard'   => true,
                    'flavor_gestionar_grupo' => true,
                ]
            );
        }

        // Asegurar que admins también tienen la capacidad
        $admin_role = get_role('administrator');
        if ($admin_role && !$admin_role->has_cap('flavor_gestor_grupos')) {
            $admin_role->add_cap('flavor_gestor_grupos');
            $admin_role->add_cap('flavor_ver_dashboard');
            $admin_role->add_cap('flavor_gestionar_grupo');
        }
    }

    /**
     * Inicializa la configuración de menús visibles por vista
     */
    private function inicializar_menus_por_vista() {
        // Vista Admin: ve TODOS los menús
        $this->menus_por_vista[self::VISTA_ADMIN] = 'all';

        // Intentar cargar configuración guardada
        $config_guardada = get_option('flavor_menus_por_vista_config', []);

        if (!empty($config_guardada[self::VISTA_GESTOR_GRUPOS])) {
            // Usar configuración guardada
            $this->menus_por_vista[self::VISTA_GESTOR_GRUPOS] = $config_guardada[self::VISTA_GESTOR_GRUPOS];
        } else {
            // Configuración por defecto
            $this->menus_por_vista[self::VISTA_GESTOR_GRUPOS] = [
                // Dashboards
                'flavor-dashboard',
                'flavor-unified-dashboard',

                // Gestión básica de la app
                'flavor-app-composer',      // Para ver módulos activos
                'flavor-create-pages',      // Para crear páginas de grupos

                // Herramientas útiles
                'flavor-activity-log',      // Ver actividad

                // Ayuda
                'flavor-documentation',
                'flavor-tours',
            ];
        }

        // Permitir que otros plugins/temas modifiquen los menús por vista
        $this->menus_por_vista = apply_filters('flavor_menus_por_vista', $this->menus_por_vista);
    }

    /**
     * Obtiene la lista de todos los menús disponibles para configurar
     *
     * @return array
     */
    public function obtener_menus_disponibles() {
        return [
            // Sección: Principal
            'principal' => [
                'label' => __('Principal', 'flavor-chat-ia'),
                'items' => [
                    'flavor-dashboard'         => __('Dashboard', 'flavor-chat-ia'),
                    'flavor-unified-dashboard' => __('Dashboard Unificado', 'flavor-chat-ia'),
                ],
            ],
            // Sección: Mi App
            'mi_app' => [
                'label' => __('Mi App', 'flavor-chat-ia'),
                'items' => [
                    'flavor-app-composer'    => __('Compositor & Módulos', 'flavor-chat-ia'),
                    'flavor-design-settings' => __('Diseño y Apariencia', 'flavor-chat-ia'),
                    'flavor-layouts'         => __('Layouts (Menús y Footers)', 'flavor-chat-ia'),
                    'flavor-create-pages'    => __('Crear Páginas', 'flavor-chat-ia'),
                    'flavor-landing-editor'  => __('Editor Visual', 'flavor-chat-ia'),
                    'flavor-permissions'     => __('Permisos', 'flavor-chat-ia'),
                ],
            ],
            // Sección: Chat IA
            'chat_ia' => [
                'label' => __('Chat IA', 'flavor-chat-ia'),
                'items' => [
                    'flavor-chat-config'        => __('Configuración IA', 'flavor-chat-ia'),
                    'flavor-chat-ia-escalations' => __('Escalados', 'flavor-chat-ia'),
                ],
            ],
            // Sección: Apps
            'apps' => [
                'label' => __('Apps', 'flavor-chat-ia'),
                'items' => [
                    'flavor-apps-config' => __('Apps Móviles', 'flavor-chat-ia'),
                    'flavor-deep-links'  => __('Deep Links', 'flavor-chat-ia'),
                    'flavor-network'     => __('Red de Nodos', 'flavor-chat-ia'),
                ],
            ],
            // Sección: Extensiones
            'extensiones' => [
                'label' => __('Extensiones', 'flavor-chat-ia'),
                'items' => [
                    'flavor-addons'      => __('Addons', 'flavor-chat-ia'),
                    'flavor-marketplace' => __('Marketplace', 'flavor-chat-ia'),
                    'flavor-newsletter'  => __('Newsletter', 'flavor-chat-ia'),
                ],
            ],
            // Sección: Herramientas
            'herramientas' => [
                'label' => __('Herramientas', 'flavor-chat-ia'),
                'items' => [
                    'flavor-export-import'  => __('Exportar / Importar', 'flavor-chat-ia'),
                    'flavor-health-check'   => __('Diagnóstico', 'flavor-chat-ia'),
                    'flavor-activity-log'   => __('Registro de Actividad', 'flavor-chat-ia'),
                    'flavor-analytics'      => __('Analytics', 'flavor-chat-ia'),
                    'flavor-api-docs'       => __('API Docs', 'flavor-chat-ia'),
                    'flavor-systems-panel'  => __('Panel de Sistemas', 'flavor-chat-ia'),
                ],
            ],
            // Sección: Ayuda
            'ayuda' => [
                'label' => __('Ayuda', 'flavor-chat-ia'),
                'items' => [
                    'flavor-documentation' => __('Documentación', 'flavor-chat-ia'),
                    'flavor-tours'         => __('Tours Guiados', 'flavor-chat-ia'),
                ],
            ],
        ];
    }

    /**
     * Guarda la configuración de menús por vista
     *
     * @param string $vista
     * @param array $menus
     * @return bool
     */
    public function guardar_config_menus_vista($vista, $menus) {
        if (!current_user_can('manage_options')) {
            return false;
        }

        $config = get_option('flavor_menus_por_vista_config', []);
        $config[$vista] = array_map('sanitize_text_field', $menus);

        return update_option('flavor_menus_por_vista_config', $config);
    }

    /**
     * Obtiene la vista activa del usuario actual
     *
     * @return string
     */
    public function obtener_vista_activa() {
        if ($this->vista_activa !== null) {
            return $this->vista_activa;
        }

        $usuario_actual = get_current_user_id();

        if (!$usuario_actual) {
            return self::VISTA_ADMIN;
        }

        // Si el usuario NO es admin, forzar vista de gestor
        if (!current_user_can('manage_options')) {
            // Verificar si tiene el rol de gestor de grupos
            if (current_user_can('flavor_gestor_grupos') || current_user_can('edit_posts')) {
                $this->vista_activa = self::VISTA_GESTOR_GRUPOS;
            } else {
                // Sin permisos suficientes
                $this->vista_activa = self::VISTA_GESTOR_GRUPOS;
            }
            return $this->vista_activa;
        }

        // Si es admin, puede elegir la vista
        $vista_guardada = get_user_meta($usuario_actual, self::META_VISTA_ACTIVA, true);

        if (empty($vista_guardada) || !in_array($vista_guardada, [self::VISTA_ADMIN, self::VISTA_GESTOR_GRUPOS])) {
            $vista_guardada = self::VISTA_ADMIN;
        }

        $this->vista_activa = $vista_guardada;
        return $this->vista_activa;
    }

    /**
     * Guarda la vista activa para el usuario
     *
     * @param string $vista
     * @return bool
     */
    public function guardar_vista_activa($vista) {
        if (!in_array($vista, [self::VISTA_ADMIN, self::VISTA_GESTOR_GRUPOS])) {
            return false;
        }

        $usuario_actual = get_current_user_id();
        if (!$usuario_actual || !current_user_can('manage_options')) {
            return false;
        }

        update_user_meta($usuario_actual, self::META_VISTA_ACTIVA, $vista);
        $this->vista_activa = $vista;

        return true;
    }

    /**
     * Verifica si un menú debe mostrarse en la vista actual
     *
     * @param string $slug
     * @return bool
     */
    public function menu_visible_en_vista($slug) {
        $vista = $this->obtener_vista_activa();

        // Vista admin ve todo
        if ($vista === self::VISTA_ADMIN) {
            return true;
        }

        $menus_permitidos = $this->menus_por_vista[$vista] ?? [];

        // Si es 'all', mostrar todo
        if ($menus_permitidos === 'all') {
            return true;
        }

        // Verificar si el slug está en la lista
        return in_array($slug, $menus_permitidos);
    }

    /**
     * Verifica si una sección tiene al menos un item visible
     *
     * @param array $slugs Lista de slugs de la sección
     * @return bool
     */
    private function seccion_tiene_items_visibles($slugs) {
        foreach ($slugs as $slug) {
            if ($this->menu_visible_en_vista($slug)) {
                return true;
            }
        }
        return false;
    }

    /**
     * Obtiene la capacidad requerida según la vista
     *
     * @param string $cap_original
     * @return string
     */
    private function obtener_capacidad_para_vista($cap_original) {
        $vista = $this->obtener_vista_activa();

        // En vista admin, mantener capacidades originales
        if ($vista === self::VISTA_ADMIN) {
            return $cap_original;
        }

        // En vista gestor, usar 'read' para los menús permitidos
        // (el filtrado real se hace en menu_visible_en_vista)
        return 'read';
    }

    /**
     * Handler AJAX para cambiar de vista
     */
    public function ajax_cambiar_vista() {
        check_ajax_referer('flavor_cambiar_vista', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_send_json_error(['message' => __('No tienes permisos suficientes.', 'flavor-chat-ia')]);
        }

        $nueva_vista = isset($_POST['vista']) ? sanitize_text_field($_POST['vista']) : '';

        if ($this->guardar_vista_activa($nueva_vista)) {
            wp_send_json_success([
                'message' => __('Vista cambiada correctamente.', 'flavor-chat-ia'),
                'vista'   => $nueva_vista,
                'reload'  => true,
            ]);
        } else {
            wp_send_json_error(['message' => __('Vista no válida.', 'flavor-chat-ia')]);
        }
    }

    /**
     * Agrega selector de vista en la admin bar (solo para admins)
     *
     * @param WP_Admin_Bar $admin_bar
     */
    public function agregar_selector_vista($admin_bar) {
        if (!is_admin() || !current_user_can('manage_options')) {
            return;
        }

        $vista_activa = $this->obtener_vista_activa();

        // Determinar icono y título según vista activa
        $icono = $vista_activa === self::VISTA_ADMIN ? '👤' : '👥';
        $titulo_actual = $vista_activa === self::VISTA_ADMIN
            ? __('Vista: Administrador', 'flavor-chat-ia')
            : __('Vista: Gestor de Grupos', 'flavor-chat-ia');

        // Nodo principal
        $admin_bar->add_node([
            'id'    => 'flavor-vista-selector',
            'title' => '<span class="flavor-vista-icono">' . $icono . '</span> ' . esc_html($titulo_actual),
            'href'  => '#',
            'meta'  => [
                'class' => 'flavor-vista-selector-wrapper',
                'title' => __('Cambiar vista del panel', 'flavor-chat-ia'),
            ],
        ]);

        // Opción: Vista Admin
        $admin_bar->add_node([
            'id'     => 'flavor-vista-admin',
            'parent' => 'flavor-vista-selector',
            'title'  => ($vista_activa === self::VISTA_ADMIN ? '✓ ' : '') . __('Administrador (completa)', 'flavor-chat-ia'),
            'href'   => '#',
            'meta'   => [
                'class'   => 'flavor-vista-opcion' . ($vista_activa === self::VISTA_ADMIN ? ' activa' : ''),
                'onclick' => 'flavorCambiarVista("' . self::VISTA_ADMIN . '"); return false;',
            ],
        ]);

        // Opción: Vista Gestor de Grupos
        $admin_bar->add_node([
            'id'     => 'flavor-vista-gestor',
            'parent' => 'flavor-vista-selector',
            'title'  => ($vista_activa === self::VISTA_GESTOR_GRUPOS ? '✓ ' : '') . __('Gestor de Grupos', 'flavor-chat-ia'),
            'href'   => '#',
            'meta'   => [
                'class'   => 'flavor-vista-opcion' . ($vista_activa === self::VISTA_GESTOR_GRUPOS ? ' activa' : ''),
                'onclick' => 'flavorCambiarVista("' . self::VISTA_GESTOR_GRUPOS . '"); return false;',
            ],
        ]);

        // Separador
        $admin_bar->add_node([
            'id'     => 'flavor-vista-sep',
            'parent' => 'flavor-vista-selector',
            'title'  => '<hr style="margin:5px 0;border:0;border-top:1px solid rgba(255,255,255,0.2);">',
            'href'   => false,
            'meta'   => ['class' => 'flavor-vista-separador'],
        ]);

        // Enlace a configuración
        $admin_bar->add_node([
            'id'     => 'flavor-vista-config',
            'parent' => 'flavor-vista-selector',
            'title'  => '⚙️ ' . __('Configurar menús por vista', 'flavor-chat-ia'),
            'href'   => admin_url('admin.php?page=flavor-config-vistas'),
            'meta'   => ['class' => 'flavor-vista-config-link'],
        ]);
    }

    /**
     * Encola CSS y JS para el selector de vista
     */
    public function encolar_css_selector_vista() {
        if (!current_user_can('manage_options')) {
            return;
        }

        $nonce = wp_create_nonce('flavor_cambiar_vista');
        ?>
        <style id="flavor-vista-selector-css">
            /* Selector de vista en admin bar */
            #wpadminbar .flavor-vista-selector-wrapper > .ab-item {
                display: flex;
                align-items: center;
                gap: 6px;
            }
            #wpadminbar .flavor-vista-icono {
                font-size: 14px;
                line-height: 1;
            }
            #wpadminbar .flavor-vista-opcion.activa > .ab-item {
                font-weight: 600;
                color: #72aee6;
            }
            #wpadminbar .flavor-vista-opcion > .ab-item:hover {
                color: #72aee6;
            }
            /* Indicador visual de vista activa */
            body.flavor-vista-gestor-grupos #adminmenu {
                border-left: 3px solid #8b5cf6;
            }
            body.flavor-vista-gestor-grupos #adminmenu > li.wp-has-current-submenu > a,
            body.flavor-vista-gestor-grupos #adminmenu > li.current > a {
                background: linear-gradient(90deg, rgba(139,92,246,0.15) 0%, transparent 100%);
            }
        </style>
        <script id="flavor-vista-selector-js">
            function flavorCambiarVista(vista) {
                if (!vista) return;

                // Mostrar loading
                var selector = document.querySelector('#wp-admin-bar-flavor-vista-selector > .ab-item');
                if (selector) {
                    selector.innerHTML = '<span class="flavor-vista-icono">⏳</span> <?php echo esc_js(__('Cambiando...', 'flavor-chat-ia')); ?>';
                }

                // Petición AJAX
                var xhr = new XMLHttpRequest();
                xhr.open('POST', '<?php echo admin_url('admin-ajax.php'); ?>', true);
                xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
                xhr.onload = function() {
                    if (xhr.status === 200) {
                        try {
                            var response = JSON.parse(xhr.responseText);
                            if (response.success && response.data.reload) {
                                window.location.reload();
                            }
                        } catch(e) {
                            console.error('Error parsing response', e);
                        }
                    }
                };
                xhr.send('action=flavor_cambiar_vista_admin&nonce=<?php echo $nonce; ?>&vista=' + encodeURIComponent(vista));
            }
        </script>
        <?php

        // Añadir clase al body según vista activa
        $vista_activa = $this->obtener_vista_activa();
        if ($vista_activa === self::VISTA_GESTOR_GRUPOS) {
            add_filter('admin_body_class', function($classes) {
                return $classes . ' flavor-vista-gestor-grupos';
            });
        }
    }

    /**
     * Registra todos los menús de Flavor Platform
     */
    public function registrar_menus() {
        // Determinar capacidad base según vista
        $vista_activa = $this->obtener_vista_activa();
        $cap_menu_principal = $vista_activa === self::VISTA_GESTOR_GRUPOS ? 'read' : 'manage_options';

        // ══════════════════════════════════════════════════════════════
        // MENÚ PRINCIPAL
        // ══════════════════════════════════════════════════════════════
        add_menu_page(
            __('Flavor Platform', 'flavor-chat-ia'),
            __('Flavor Platform', 'flavor-chat-ia'),
            $cap_menu_principal,
            self::MENU_SLUG,
            [$this, 'callback_dashboard'],
            'dashicons-superhero-alt',
            30
        );

        // Dashboard (pos 0) - Siempre visible
        if ($this->menu_visible_en_vista('flavor-dashboard')) {
            add_submenu_page(
                self::MENU_SLUG,
                __('Dashboard', 'flavor-chat-ia'),
                __('Dashboard', 'flavor-chat-ia'),
                $cap_menu_principal,
                'flavor-dashboard',
                [$this, 'callback_dashboard'],
                0
            );
        }

        // Dashboard Unificado (pos 1) - Siempre visible
        if ($this->menu_visible_en_vista('flavor-unified-dashboard')) {
            add_submenu_page(
                self::MENU_SLUG,
                __('Dashboard Unificado', 'flavor-chat-ia'),
                __('Unificado', 'flavor-chat-ia'),
                'read',
                'flavor-unified-dashboard',
                [$this, 'callback_unified_dashboard'],
                1
            );
        }

        // Eliminar el submenú duplicado que WP crea
        remove_submenu_page(self::MENU_SLUG, self::MENU_SLUG);

        // ══════════════════════════════════════════════════════════════
        // SECCIÓN: MI APP (10-19)
        // ══════════════════════════════════════════════════════════════
        if ($this->seccion_tiene_items_visibles(['flavor-app-composer', 'flavor-design-settings', 'flavor-layouts', 'flavor-create-pages', 'flavor-landing-editor', 'flavor-permissions'])) {
            $this->agregar_separador(__('Mi App', 'flavor-chat-ia'), 10);
        }

        if ($this->menu_visible_en_vista('flavor-app-composer')) {
            add_submenu_page(
                self::MENU_SLUG,
                __('Compositor & Módulos', 'flavor-chat-ia'),
                __('Compositor', 'flavor-chat-ia'),
                $cap_menu_principal,
                'flavor-app-composer',
                [$this, 'callback_app_composer'],
                11
            );
        }

        if ($this->menu_visible_en_vista('flavor-design-settings')) {
            add_submenu_page(
                self::MENU_SLUG,
                __('Diseño y Apariencia', 'flavor-chat-ia'),
                __('Diseño', 'flavor-chat-ia'),
                'manage_options',
                'flavor-design-settings',
                [$this, 'callback_design_settings'],
                12
            );
        }

        if ($this->menu_visible_en_vista('flavor-layouts')) {
            add_submenu_page(
                self::MENU_SLUG,
                __('Menús y Footers', 'flavor-chat-ia'),
                __('Layouts', 'flavor-chat-ia'),
                'manage_options',
                'flavor-layouts',
                [$this, 'callback_layouts'],
                13
            );
        }

        if ($this->menu_visible_en_vista('flavor-create-pages')) {
            add_submenu_page(
                self::MENU_SLUG,
                __('Crear Páginas', 'flavor-chat-ia'),
                __('Páginas', 'flavor-chat-ia'),
                $cap_menu_principal,
                'flavor-create-pages',
                [$this, 'callback_pages'],
                14
            );
        }

        if ($this->menu_visible_en_vista('flavor-landing-editor')) {
            add_submenu_page(
                self::MENU_SLUG,
                __('Editor Visual', 'flavor-chat-ia'),
                __('Editor Visual', 'flavor-chat-ia'),
                'manage_options',
                'flavor-landing-editor',
                [$this, 'callback_landing_editor'],
                15
            );
        }

        if ($this->menu_visible_en_vista('flavor-permissions')) {
            add_submenu_page(
                self::MENU_SLUG,
                __('Permisos', 'flavor-chat-ia'),
                __('Permisos', 'flavor-chat-ia'),
                'manage_options',
                'flavor-permissions',
                [$this, 'callback_permissions'],
                16
            );
        }

        // ══════════════════════════════════════════════════════════════
        // SECCIÓN: CHAT IA (20-29)
        // ══════════════════════════════════════════════════════════════
        if ($this->seccion_tiene_items_visibles(['flavor-chat-config', 'flavor-chat-ia-escalations'])) {
            $this->agregar_separador(__('Chat IA', 'flavor-chat-ia'), 20);
        }

        if ($this->menu_visible_en_vista('flavor-chat-config')) {
            add_submenu_page(
                self::MENU_SLUG,
                __('Configuración IA', 'flavor-chat-ia'),
                __('Configuración', 'flavor-chat-ia'),
                'manage_options',
                'flavor-chat-config',
                [$this, 'callback_chat_settings'],
                21
            );
        }

        if ($this->menu_visible_en_vista('flavor-chat-ia-escalations')) {
            add_submenu_page(
                self::MENU_SLUG,
                __('Escalados', 'flavor-chat-ia'),
                __('Escalados', 'flavor-chat-ia'),
                'manage_options',
                'flavor-chat-ia-escalations',
                [$this, 'callback_escalations'],
                22
            );
        }

        // ══════════════════════════════════════════════════════════════
        // SECCIÓN: APPS Y CONECTIVIDAD (30-39)
        // ══════════════════════════════════════════════════════════════
        if ($this->seccion_tiene_items_visibles(['flavor-apps-config', 'flavor-deep-links', 'flavor-network'])) {
            $this->agregar_separador(__('Apps', 'flavor-chat-ia'), 30);
        }

        if ($this->menu_visible_en_vista('flavor-apps-config')) {
            add_submenu_page(
                self::MENU_SLUG,
                __('Apps Móviles', 'flavor-chat-ia'),
                __('Apps Móviles', 'flavor-chat-ia'),
                'manage_options',
                'flavor-apps-config',
                [$this, 'callback_apps_config'],
                31
            );
        }

        if ($this->menu_visible_en_vista('flavor-deep-links')) {
            add_submenu_page(
                self::MENU_SLUG,
                __('Deep Links', 'flavor-chat-ia'),
                __('Deep Links', 'flavor-chat-ia'),
                'manage_options',
                'flavor-deep-links',
                [$this, 'callback_deep_links'],
                33
            );
        }

        if ($this->menu_visible_en_vista('flavor-network')) {
            add_submenu_page(
                self::MENU_SLUG,
                __('Red de Nodos', 'flavor-chat-ia'),
                __('Red', 'flavor-chat-ia'),
                'manage_options',
                'flavor-network',
                [$this, 'callback_network'],
                35
            );
        }

        // ══════════════════════════════════════════════════════════════
        // SECCIÓN: EXTENSIONES (40-49)
        // ══════════════════════════════════════════════════════════════
        if ($this->seccion_tiene_items_visibles(['flavor-addons', 'flavor-marketplace', 'flavor-newsletter'])) {
            $this->agregar_separador(__('Extensiones', 'flavor-chat-ia'), 40);
        }

        if ($this->menu_visible_en_vista('flavor-addons')) {
            add_submenu_page(
                self::MENU_SLUG,
                __('Addons', 'flavor-chat-ia'),
                __('Addons', 'flavor-chat-ia'),
                'manage_options',
                'flavor-addons',
                [$this, 'callback_addons'],
                41
            );
        }

        if ($this->menu_visible_en_vista('flavor-marketplace')) {
            add_submenu_page(
                self::MENU_SLUG,
                __('Marketplace', 'flavor-chat-ia'),
                __('Marketplace', 'flavor-chat-ia'),
                'manage_options',
                'flavor-marketplace',
                [$this, 'callback_marketplace'],
                42
            );
        }

        if ($this->menu_visible_en_vista('flavor-newsletter')) {
            add_submenu_page(
                self::MENU_SLUG,
                __('Newsletter', 'flavor-chat-ia'),
                __('Newsletter', 'flavor-chat-ia'),
                'manage_options',
                'flavor-newsletter',
                [$this, 'callback_newsletter'],
                43
            );
        }

        // ══════════════════════════════════════════════════════════════
        // SECCIÓN: HERRAMIENTAS (50-59)
        // ══════════════════════════════════════════════════════════════
        if ($this->seccion_tiene_items_visibles(['flavor-export-import', 'flavor-health-check', 'flavor-activity-log', 'flavor-analytics', 'flavor-api-docs', 'flavor-systems-panel'])) {
            $this->agregar_separador(__('Herramientas', 'flavor-chat-ia'), 50);
        }

        if ($this->menu_visible_en_vista('flavor-export-import')) {
            add_submenu_page(
                self::MENU_SLUG,
                __('Exportar / Importar', 'flavor-chat-ia'),
                __('Export / Import', 'flavor-chat-ia'),
                'manage_options',
                'flavor-export-import',
                [$this, 'callback_export_import'],
                51
            );
        }

        if ($this->menu_visible_en_vista('flavor-health-check')) {
            add_submenu_page(
                self::MENU_SLUG,
                __('Diagnóstico', 'flavor-chat-ia'),
                __('Diagnóstico', 'flavor-chat-ia'),
                'manage_options',
                'flavor-health-check',
                [$this, 'callback_health_check'],
                52
            );
        }

        if ($this->menu_visible_en_vista('flavor-activity-log')) {
            add_submenu_page(
                self::MENU_SLUG,
                __('Registro de Actividad', 'flavor-chat-ia'),
                __('Actividad', 'flavor-chat-ia'),
                $cap_menu_principal,
                'flavor-activity-log',
                [$this, 'callback_activity_log'],
                53
            );
        }

        if ($this->menu_visible_en_vista('flavor-analytics')) {
            add_submenu_page(
                self::MENU_SLUG,
                __('Analytics Dashboard', 'flavor-chat-ia'),
                __('Analytics', 'flavor-chat-ia'),
                'manage_options',
                'flavor-analytics',
                [$this, 'callback_analytics'],
                54
            );
        }

        if ($this->menu_visible_en_vista('flavor-api-docs')) {
            add_submenu_page(
                self::MENU_SLUG,
                __('API Docs', 'flavor-chat-ia'),
                __('API Docs', 'flavor-chat-ia'),
                'manage_options',
                'flavor-api-docs',
                [$this, 'callback_api_docs'],
                54
            );
        }

        if ($this->menu_visible_en_vista('flavor-systems-panel')) {
            add_submenu_page(
                self::MENU_SLUG,
                __('Panel de Sistemas', 'flavor-chat-ia'),
                __('Sistemas V3', 'flavor-chat-ia'),
                'manage_options',
                'flavor-systems-panel',
                [$this, 'callback_systems_panel'],
                55
            );
        }

        // ══════════════════════════════════════════════════════════════
        // SECCIÓN: AYUDA (60-69)
        // ══════════════════════════════════════════════════════════════
        if ($this->seccion_tiene_items_visibles(['flavor-documentation', 'flavor-tours'])) {
            $this->agregar_separador(__('Ayuda', 'flavor-chat-ia'), 60);
        }

        if ($this->menu_visible_en_vista('flavor-documentation')) {
            add_submenu_page(
                self::MENU_SLUG,
                __('Documentación', 'flavor-chat-ia'),
                __('Documentación', 'flavor-chat-ia'),
                $cap_menu_principal,
                'flavor-documentation',
                [$this, 'callback_documentation'],
                61
            );
        }

        if ($this->menu_visible_en_vista('flavor-tours')) {
            add_submenu_page(
                self::MENU_SLUG,
                __('Tours Guiados', 'flavor-chat-ia'),
                __('Tours', 'flavor-chat-ia'),
                $cap_menu_principal,
                'flavor-tours',
                [$this, 'callback_tours'],
                62
            );
        }

        // ══════════════════════════════════════════════════════════════
        // PÁGINAS OCULTAS (sin mostrar en menú)
        // ══════════════════════════════════════════════════════════════

        // Configuración de Vistas del Menú
        add_submenu_page(
            null,
            __('Configuración de Vistas', 'flavor-chat-ia'),
            '',
            'manage_options',
            'flavor-config-vistas',
            [$this, 'callback_config_vistas']
        );

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

        // Sello de Conciencia
        add_submenu_page(
            null,
            __('Sello de Conciencia', 'flavor-chat-ia'),
            '',
            'manage_options',
            'sello-conciencia',
            [$this, 'callback_sello_conciencia']
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
            'flavor-layouts',
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
            'flavor-systems-panel',
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

    public function callback_analytics() {
        if (class_exists('Flavor_Analytics_Dashboard')) {
            Flavor_Analytics_Dashboard::get_instance()->render_page();
        } else {
            // Intentar cargar la clase
            $dashboard_path = FLAVOR_CHAT_IA_PATH . 'admin/class-analytics-dashboard.php';
            if (file_exists($dashboard_path)) {
                require_once $dashboard_path;
                if (class_exists('Flavor_Analytics_Dashboard')) {
                    Flavor_Analytics_Dashboard::get_instance()->render_page();
                    return;
                }
            }
            echo '<div class="wrap"><h1>' . esc_html__('Analytics Dashboard', 'flavor-chat-ia') . '</h1>';
            echo '<p>' . esc_html__('Error al cargar el Dashboard de Analytics.', 'flavor-chat-ia') . '</p></div>';
        }
    }

    public function callback_api_docs() {
        if (class_exists('Flavor_API_Docs')) {
            Flavor_API_Docs::get_instance()->renderizar_pagina();
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
        // Intentar cargar la clase si no existe
        if (!class_exists('Flavor_Documentation_Page')) {
            $doc_path = FLAVOR_CHAT_IA_PATH . 'includes/admin/class-documentation-page.php';
            if (file_exists($doc_path)) {
                require_once $doc_path;
            }
        }

        if (class_exists('Flavor_Documentation_Page')) {
            Flavor_Documentation_Page::get_instance()->render_page();
        } else {
            // Fallback: mostrar mensaje de error
            echo '<div class="wrap">';
            echo '<h1>' . esc_html__('Documentación', 'flavor-chat-ia') . '</h1>';
            echo '<div class="notice notice-error"><p>' . esc_html__('Error al cargar la página de documentación.', 'flavor-chat-ia') . '</p></div>';
            echo '</div>';
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

    public function callback_sello_conciencia() {
        // Verificar si el módulo está activo
        $esta_activo = Flavor_Chat_Module_Loader::is_module_active('sello_conciencia');

        if (!$esta_activo) {
            $url_compositor = admin_url('admin.php?page=flavor-app-composer');
            echo '<div class="wrap">';
            echo '<h1>' . esc_html__('Sello de Conciencia', 'flavor-chat-ia') . '</h1>';
            echo '<div class="notice notice-warning" style="padding: 15px;">';
            echo '<p><strong>' . esc_html__('El módulo Sello de Conciencia no está activo.', 'flavor-chat-ia') . '</strong></p>';
            echo '<p>' . esc_html__('Para usar esta funcionalidad, debes activar el módulo desde el Compositor de App.', 'flavor-chat-ia') . '</p>';
            echo '<p><a href="' . esc_url($url_compositor) . '" class="button button-primary">';
            echo '<span class="dashicons dashicons-admin-plugins" style="margin-top: 4px;"></span> ';
            echo esc_html__('Ir al Compositor de App', 'flavor-chat-ia') . '</a></p>';
            echo '</div></div>';
            return;
        }

        // Obtener el módulo del loader
        $loader = Flavor_Chat_Module_Loader::get_instance();
        $modulo = $loader->get_module('sello_conciencia');

        // Si no está cargado, intentar cargar manualmente
        if (!$modulo) {
            $archivo_modulo = FLAVOR_CHAT_IA_PATH . 'includes/modules/sello-conciencia/class-sello-conciencia-module.php';
            if (file_exists($archivo_modulo)) {
                require_once $archivo_modulo;
                if (class_exists('Flavor_Chat_Sello_Conciencia_Module')) {
                    $modulo = new Flavor_Chat_Sello_Conciencia_Module();
                    $modulo->init();
                }
            }
        }

        if ($modulo && method_exists($modulo, 'render_admin_dashboard')) {
            $modulo->render_admin_dashboard();
        } else {
            // El módulo está activo pero hay un error al cargarlo
            echo '<div class="wrap">';
            echo '<h1>' . esc_html__('Sello de Conciencia', 'flavor-chat-ia') . '</h1>';
            echo '<div class="notice notice-error" style="padding: 15px;">';
            echo '<p><strong>' . esc_html__('Error al cargar el módulo.', 'flavor-chat-ia') . '</strong></p>';
            echo '<p>' . esc_html__('El módulo está activo pero no se pudo cargar. Verifica los logs de error de PHP.', 'flavor-chat-ia') . '</p>';
            echo '</div></div>';
        }
    }

    /**
     * Callback para la página de configuración de vistas del menú
     */
    public function callback_config_vistas() {
        if (!current_user_can('manage_options')) {
            wp_die(__('No tienes permisos para acceder a esta página.', 'flavor-chat-ia'));
        }

        $menus_disponibles = $this->obtener_menus_disponibles();
        $config_guardada = get_option('flavor_menus_por_vista_config', []);
        $menus_gestor = $config_guardada[self::VISTA_GESTOR_GRUPOS] ?? $this->menus_por_vista[self::VISTA_GESTOR_GRUPOS];

        ?>
        <div class="wrap flavor-config-vistas-wrap">
            <h1>
                <span class="dashicons dashicons-visibility" style="font-size:28px;margin-right:10px;"></span>
                <?php esc_html_e('Configuración de Vistas del Menú', 'flavor-chat-ia'); ?>
            </h1>

            <p class="description" style="font-size:14px;margin:15px 0 25px;">
                <?php esc_html_e('Configura qué opciones del menú son visibles para cada tipo de vista. Los administradores siempre ven todos los menús, pero pueden cambiar a la vista de "Gestor de Grupos" para ver una interfaz simplificada.', 'flavor-chat-ia'); ?>
            </p>

            <div class="flavor-config-vistas-container">
                <!-- Panel de Vista Gestor de Grupos -->
                <div class="flavor-vista-config-panel">
                    <div class="flavor-vista-config-header">
                        <h2>
                            <span style="font-size:20px;">👥</span>
                            <?php esc_html_e('Vista: Gestor de Grupos', 'flavor-chat-ia'); ?>
                        </h2>
                        <p class="description">
                            <?php esc_html_e('Selecciona los menús que serán visibles cuando se active esta vista.', 'flavor-chat-ia'); ?>
                        </p>
                    </div>

                    <form id="flavor-config-vistas-form" method="post">
                        <?php wp_nonce_field('flavor_config_vistas', 'flavor_config_vistas_nonce'); ?>

                        <div class="flavor-menus-grid">
                            <?php foreach ($menus_disponibles as $seccion_id => $seccion) : ?>
                                <div class="flavor-menu-seccion">
                                    <h3 class="flavor-menu-seccion-titulo">
                                        <?php echo esc_html($seccion['label']); ?>
                                    </h3>
                                    <div class="flavor-menu-items">
                                        <?php foreach ($seccion['items'] as $slug => $label) : ?>
                                            <label class="flavor-menu-item">
                                                <input type="checkbox"
                                                       name="menus_gestor[]"
                                                       value="<?php echo esc_attr($slug); ?>"
                                                       <?php checked(in_array($slug, $menus_gestor)); ?>>
                                                <span class="flavor-menu-item-label"><?php echo esc_html($label); ?></span>
                                                <code class="flavor-menu-item-slug"><?php echo esc_html($slug); ?></code>
                                            </label>
                                        <?php endforeach; ?>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>

                        <div class="flavor-config-vistas-actions">
                            <button type="submit" class="button button-primary button-large">
                                <span class="dashicons dashicons-saved" style="margin-top:4px;"></span>
                                <?php esc_html_e('Guardar Configuración', 'flavor-chat-ia'); ?>
                            </button>

                            <button type="button" class="button button-secondary" id="flavor-reset-default">
                                <span class="dashicons dashicons-undo" style="margin-top:4px;"></span>
                                <?php esc_html_e('Restaurar por Defecto', 'flavor-chat-ia'); ?>
                            </button>

                            <a href="<?php echo esc_url(admin_url('admin.php?page=flavor-dashboard')); ?>" class="button">
                                <?php esc_html_e('Volver al Dashboard', 'flavor-chat-ia'); ?>
                            </a>

                            <span class="flavor-save-status" style="display:none;margin-left:15px;color:#46b450;">
                                <span class="dashicons dashicons-yes-alt"></span>
                                <?php esc_html_e('Guardado', 'flavor-chat-ia'); ?>
                            </span>
                        </div>
                    </form>
                </div>

                <!-- Panel de información -->
                <div class="flavor-vista-info-panel">
                    <h3><?php esc_html_e('¿Cómo funciona?', 'flavor-chat-ia'); ?></h3>
                    <ul>
                        <li><strong><?php esc_html_e('Vista Administrador:', 'flavor-chat-ia'); ?></strong> <?php esc_html_e('Acceso completo a todas las opciones del plugin.', 'flavor-chat-ia'); ?></li>
                        <li><strong><?php esc_html_e('Vista Gestor de Grupos:', 'flavor-chat-ia'); ?></strong> <?php esc_html_e('Interfaz simplificada con solo las opciones seleccionadas aquí.', 'flavor-chat-ia'); ?></li>
                    </ul>

                    <h3><?php esc_html_e('Cambiar de vista', 'flavor-chat-ia'); ?></h3>
                    <p><?php esc_html_e('Los administradores pueden cambiar de vista usando el selector en la barra superior de WordPress.', 'flavor-chat-ia'); ?></p>

                    <h3><?php esc_html_e('Rol "Gestor de Grupos"', 'flavor-chat-ia'); ?></h3>
                    <p><?php esc_html_e('Los usuarios con el rol "Gestor de Grupos" automáticamente verán solo los menús configurados aquí, sin posibilidad de cambiar a la vista completa.', 'flavor-chat-ia'); ?></p>
                </div>
            </div>
        </div>

        <style>
            .flavor-config-vistas-wrap {
                max-width: 1400px;
            }
            .flavor-config-vistas-container {
                display: grid;
                grid-template-columns: 1fr 320px;
                gap: 30px;
                margin-top: 20px;
            }
            @media (max-width: 1200px) {
                .flavor-config-vistas-container {
                    grid-template-columns: 1fr;
                }
            }
            .flavor-vista-config-panel {
                background: #fff;
                border: 1px solid #ddd;
                border-radius: 8px;
                padding: 25px;
            }
            .flavor-vista-config-header {
                margin-bottom: 25px;
                padding-bottom: 15px;
                border-bottom: 1px solid #eee;
            }
            .flavor-vista-config-header h2 {
                margin: 0 0 8px;
                display: flex;
                align-items: center;
                gap: 10px;
            }
            .flavor-menus-grid {
                display: grid;
                grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
                gap: 20px;
                margin-bottom: 25px;
            }
            .flavor-menu-seccion {
                background: #f9f9f9;
                border: 1px solid #e5e5e5;
                border-radius: 6px;
                padding: 15px;
            }
            .flavor-menu-seccion-titulo {
                margin: 0 0 12px;
                padding-bottom: 8px;
                border-bottom: 2px solid #4f46e5;
                font-size: 14px;
                color: #1d2327;
            }
            .flavor-menu-items {
                display: flex;
                flex-direction: column;
                gap: 8px;
            }
            .flavor-menu-item {
                display: flex;
                align-items: center;
                gap: 8px;
                padding: 8px 10px;
                background: #fff;
                border: 1px solid #ddd;
                border-radius: 4px;
                cursor: pointer;
                transition: all 0.15s;
            }
            .flavor-menu-item:hover {
                border-color: #4f46e5;
                background: #f0f0ff;
            }
            .flavor-menu-item input[type="checkbox"] {
                margin: 0;
            }
            .flavor-menu-item input[type="checkbox"]:checked + .flavor-menu-item-label {
                font-weight: 600;
                color: #4f46e5;
            }
            .flavor-menu-item-label {
                flex: 1;
                font-size: 13px;
            }
            .flavor-menu-item-slug {
                font-size: 10px;
                color: #999;
                background: #f0f0f0;
                padding: 2px 6px;
                border-radius: 3px;
            }
            .flavor-config-vistas-actions {
                display: flex;
                align-items: center;
                gap: 10px;
                padding-top: 20px;
                border-top: 1px solid #eee;
            }
            .flavor-config-vistas-actions .button {
                display: inline-flex;
                align-items: center;
                gap: 5px;
            }
            .flavor-vista-info-panel {
                background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
                border-radius: 8px;
                padding: 25px;
                color: #fff;
            }
            .flavor-vista-info-panel h3 {
                color: #fff;
                margin: 0 0 10px;
                font-size: 14px;
                border-bottom: 1px solid rgba(255,255,255,0.2);
                padding-bottom: 8px;
            }
            .flavor-vista-info-panel h3:not(:first-child) {
                margin-top: 20px;
            }
            .flavor-vista-info-panel ul {
                margin: 0;
                padding-left: 18px;
            }
            .flavor-vista-info-panel li {
                margin-bottom: 8px;
                font-size: 13px;
            }
            .flavor-vista-info-panel p {
                font-size: 13px;
                opacity: 0.9;
            }
        </style>

        <script>
        jQuery(document).ready(function($) {
            var $form = $('#flavor-config-vistas-form');
            var $status = $('.flavor-save-status');

            // Guardar con AJAX
            $form.on('submit', function(e) {
                e.preventDefault();

                var menus = [];
                $('input[name="menus_gestor[]"]:checked').each(function() {
                    menus.push($(this).val());
                });

                $.ajax({
                    url: ajaxurl,
                    type: 'POST',
                    data: {
                        action: 'flavor_guardar_config_vistas',
                        nonce: $('#flavor_config_vistas_nonce').val(),
                        menus: menus
                    },
                    beforeSend: function() {
                        $form.find('button[type="submit"]').prop('disabled', true).text('<?php echo esc_js(__('Guardando...', 'flavor-chat-ia')); ?>');
                    },
                    success: function(response) {
                        if (response.success) {
                            $status.fadeIn().delay(2000).fadeOut();
                        } else {
                            alert(response.data.message || '<?php echo esc_js(__('Error al guardar', 'flavor-chat-ia')); ?>');
                        }
                    },
                    error: function() {
                        alert('<?php echo esc_js(__('Error de conexión', 'flavor-chat-ia')); ?>');
                    },
                    complete: function() {
                        $form.find('button[type="submit"]').prop('disabled', false).html('<span class="dashicons dashicons-saved" style="margin-top:4px;"></span> <?php echo esc_js(__('Guardar Configuración', 'flavor-chat-ia')); ?>');
                    }
                });
            });

            // Restaurar por defecto
            $('#flavor-reset-default').on('click', function() {
                if (!confirm('<?php echo esc_js(__('¿Restaurar la configuración por defecto? Esto sobrescribirá tus cambios.', 'flavor-chat-ia')); ?>')) {
                    return;
                }

                // Valores por defecto
                var defaults = [
                    'flavor-dashboard',
                    'flavor-unified-dashboard',
                    'flavor-app-composer',
                    'flavor-create-pages',
                    'flavor-activity-log',
                    'flavor-documentation',
                    'flavor-tours'
                ];

                $('input[name="menus_gestor[]"]').each(function() {
                    $(this).prop('checked', defaults.indexOf($(this).val()) !== -1);
                });

                // Guardar automáticamente
                $form.submit();
            });
        });
        </script>
        <?php
    }

    /**
     * Handler AJAX para guardar la configuración de vistas
     */
    public function ajax_guardar_config_vistas() {
        check_ajax_referer('flavor_config_vistas', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_send_json_error(['message' => __('No tienes permisos suficientes.', 'flavor-chat-ia')]);
        }

        $menus = isset($_POST['menus']) ? array_map('sanitize_text_field', (array) $_POST['menus']) : [];

        // Asegurar que al menos dashboard está incluido
        if (!in_array('flavor-dashboard', $menus)) {
            $menus[] = 'flavor-dashboard';
        }
        if (!in_array('flavor-unified-dashboard', $menus)) {
            $menus[] = 'flavor-unified-dashboard';
        }

        if ($this->guardar_config_menus_vista(self::VISTA_GESTOR_GRUPOS, $menus)) {
            wp_send_json_success([
                'message' => __('Configuración guardada correctamente.', 'flavor-chat-ia'),
                'menus'   => $menus,
            ]);
        } else {
            wp_send_json_error(['message' => __('Error al guardar la configuración.', 'flavor-chat-ia')]);
        }
    }
}

// Inicializar el gestor de menús
Flavor_Admin_Menu_Manager::get_instance();

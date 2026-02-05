<?php
/**
 * Dashboard de usuario frontend "Mi Cuenta"
 *
 * Renderiza el area de usuario logueado con tabs dinamicos
 * y permite a modulos registrar sus propias secciones.
 *
 * @package FlavorChatIA
 * @subpackage Frontend
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Clase principal del dashboard de usuario frontend
 */
class Flavor_User_Dashboard {

    /**
     * Indica si el shortcode esta presente en la pagina actual
     *
     * @var bool
     */
    private $shortcode_presente_en_pagina = false;

    /**
     * Tab activo actual
     *
     * @var string
     */
    private $tab_activo = 'perfil';

    /**
     * Tabs registrados (cache en memoria)
     *
     * @var array
     */
    private $tabs_registrados = [];

    /**
     * Constructor: registra shortcode, hooks y assets
     */
    public function __construct() {
        add_shortcode('flavor_mi_cuenta', [$this, 'render_dashboard']);
        add_action('wp', [$this, 'detectar_shortcode_en_pagina']);
        add_action('wp_enqueue_scripts', [$this, 'encolar_assets_condicional']);
        add_action('wp_ajax_flavor_dashboard_cambiar_tab', [$this, 'ajax_cambiar_tab']);
    }

    /**
     * Detecta si el shortcode [flavor_mi_cuenta] esta presente en la pagina actual
     */
    public function detectar_shortcode_en_pagina() {
        global $post;

        if (!$post || !is_singular()) {
            return;
        }

        if (has_shortcode($post->post_content, 'flavor_mi_cuenta')) {
            $this->shortcode_presente_en_pagina = true;
        }
    }

    /**
     * Encola CSS y JS solo cuando el shortcode esta presente en la pagina
     */
    public function encolar_assets_condicional() {
        if (!$this->shortcode_presente_en_pagina) {
            return;
        }

        $sufijo_asset = defined('WP_DEBUG') && WP_DEBUG ? '' : '.min';

        wp_enqueue_style(
            'flavor-user-dashboard',
            FLAVOR_CHAT_IA_URL . "assets/css/user-dashboard{$sufijo_asset}.css",
            [],
            FLAVOR_CHAT_IA_VERSION
        );

        wp_enqueue_script(
            'flavor-user-dashboard',
            FLAVOR_CHAT_IA_URL . "assets/js/user-dashboard{$sufijo_asset}.js",
            ['jquery'],
            FLAVOR_CHAT_IA_VERSION,
            true
        );

        $usuario_actual = wp_get_current_user();
        $datos_localizados = [
            'ajaxUrl'         => admin_url('admin-ajax.php'),
            'nonce'           => wp_create_nonce('flavor_user_dashboard'),
            'userId'          => get_current_user_id(),
            'userName'        => $usuario_actual->exists() ? $usuario_actual->display_name : '',
            'pollingInterval' => 60000,
            'i18n'            => [
                'guardando'            => __('Guardando...', 'flavor-chat-ia'),
                'guardado'             => __('Cambios guardados correctamente', 'flavor-chat-ia'),
                'error_guardar'        => __('Error al guardar los cambios', 'flavor-chat-ia'),
                'cargando'             => __('Cargando...', 'flavor-chat-ia'),
                'error_conexion'       => __('Error de conexion. Intentalo de nuevo.', 'flavor-chat-ia'),
                'confirmar_password'   => __('Las contrasenas no coinciden', 'flavor-chat-ia'),
                'password_corto'       => __('La contrasena debe tener al menos 8 caracteres', 'flavor-chat-ia'),
                'sin_notificaciones'   => __('No tienes notificaciones', 'flavor-chat-ia'),
                'marcar_leida'         => __('Marcar como leida', 'flavor-chat-ia'),
                'marcar_todas_leidas'  => __('Marcar todas como leidas', 'flavor-chat-ia'),
                'perfil_actualizado'   => __('Perfil actualizado correctamente', 'flavor-chat-ia'),
                'password_actualizado' => __('Contrasena actualizada correctamente', 'flavor-chat-ia'),
                'error_email'          => __('El email introducido no es valido', 'flavor-chat-ia'),
            ],
        ];

        wp_localize_script('flavor-user-dashboard', 'flavorDashboardData', $datos_localizados);
    }

    /**
     * Obtiene los tabs disponibles (fijos + dinamicos de modulos)
     *
     * @return array Lista de tabs con clave, label, icono y callback
     */
    private function obtener_tabs() {
        if (!empty($this->tabs_registrados)) {
            return $this->tabs_registrados;
        }

        $tabs_del_sistema = [
            'perfil' => [
                'label'    => __('Mi Perfil', 'flavor-chat-ia'),
                'icon'     => 'user',
                'callback' => [$this, 'render_tab_perfil'],
                'orden'    => 10,
            ],
            'notificaciones' => [
                'label'    => __('Notificaciones', 'flavor-chat-ia'),
                'icon'     => 'bell',
                'callback' => [$this, 'render_tab_notificaciones'],
                'orden'    => 90,
            ],
        ];

        /**
         * Filtro para que los modulos registren sus tabs dinamicos.
         *
         * Ejemplo de uso desde un modulo:
         *   add_filter('flavor_user_dashboard_tabs', function($tabs) {
         *       $tabs['mis-pedidos'] = [
         *           'label'    => 'Mis Pedidos',
         *           'icon'     => 'cart',
         *           'callback' => [$this, 'render_tab'],
         *           'orden'    => 30,
         *       ];
         *       return $tabs;
         *   });
         *
         * @param array $tabs Tabs actuales del dashboard
         * @return array Tabs modificados
         */
        $tabs_combinados = apply_filters('flavor_user_dashboard_tabs', $tabs_del_sistema);

        uasort($tabs_combinados, function ($tab_a, $tab_b) {
            $orden_tab_a = $tab_a['orden'] ?? 50;
            $orden_tab_b = $tab_b['orden'] ?? 50;
            return $orden_tab_a - $orden_tab_b;
        });

        $this->tabs_registrados = $tabs_combinados;

        return $this->tabs_registrados;
    }

    /**
     * Renderiza el dashboard completo o el formulario de login
     *
     * @param array $atributos_shortcode Atributos del shortcode
     * @return string HTML del dashboard o formulario de login
     */
    public function render_dashboard($atributos_shortcode = []) {
        $this->shortcode_presente_en_pagina = true;

        if (!is_user_logged_in()) {
            return $this->render_formulario_login();
        }

        $tab_solicitado = isset($_GET['tab']) ? sanitize_key($_GET['tab']) : 'perfil';
        $todos_los_tabs = $this->obtener_tabs();

        if (!isset($todos_los_tabs[$tab_solicitado])) {
            $tab_solicitado = 'perfil';
        }

        $this->tab_activo = $tab_solicitado;

        $usuario_actual  = wp_get_current_user();
        $avatar_url      = get_avatar_url($usuario_actual->ID, ['size' => 96]);
        $nombre_completo = trim($usuario_actual->first_name . ' ' . $usuario_actual->last_name);

        if (empty($nombre_completo)) {
            $nombre_completo = $usuario_actual->display_name;
        }

        $cantidad_notificaciones_sin_leer = 0;
        if (class_exists('Flavor_Notification_Manager')) {
            $gestor_notificaciones = Flavor_Notification_Manager::get_instance();
            $cantidad_notificaciones_sin_leer = $gestor_notificaciones->get_unread_count($usuario_actual->ID);
        }

        ob_start();

        $datos_template = [
            'usuario'                 => $usuario_actual,
            'avatar_url'              => $avatar_url,
            'nombre_completo'         => $nombre_completo,
            'tabs'                    => $todos_los_tabs,
            'tab_activo'              => $this->tab_activo,
            'notificaciones_sin_leer' => $cantidad_notificaciones_sin_leer,
            'dashboard_instance'      => $this,
        ];

        $ruta_template_dashboard = FLAVOR_CHAT_IA_PATH . 'templates/frontend/mi-cuenta/dashboard.php';
        if (file_exists($ruta_template_dashboard)) {
            extract($datos_template);
            include $ruta_template_dashboard;
        }

        return ob_get_clean();
    }

    /**
     * Renderiza el formulario de login para usuarios no logueados
     *
     * @return string HTML del formulario de login
     */
    private function render_formulario_login() {
        ob_start();
        ?>
        <div class="flavor-dashboard flavor-dashboard--login">
            <div class="flavor-dashboard-login-wrapper">
                <div class="flavor-dashboard-login-header">
                    <div class="flavor-dashboard-login-icon">
                        <svg width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/>
                            <circle cx="12" cy="7" r="4"/>
                        </svg>
                    </div>
                    <h2><?php esc_html_e('Accede a tu cuenta', 'flavor-chat-ia'); ?></h2>
                    <p><?php esc_html_e('Inicia sesion para acceder a tu panel personal', 'flavor-chat-ia'); ?></p>
                </div>

                <div class="flavor-dashboard-login-form">
                    <?php
                    $argumentos_login = [
                        'echo'           => true,
                        'redirect'       => get_permalink(),
                        'form_id'        => 'flavor-login-form',
                        'label_username' => __('Usuario o email', 'flavor-chat-ia'),
                        'label_password' => __('Contrasena', 'flavor-chat-ia'),
                        'label_remember' => __('Recuerdame', 'flavor-chat-ia'),
                        'label_log_in'   => __('Iniciar sesion', 'flavor-chat-ia'),
                        'remember'       => true,
                    ];
                    wp_login_form($argumentos_login);
                    ?>
                </div>

                <div class="flavor-dashboard-login-footer">
                    <a href="<?php echo esc_url(wp_lostpassword_url(get_permalink())); ?>" class="flavor-dashboard-link">
                        <?php esc_html_e('Olvidaste tu contrasena?', 'flavor-chat-ia'); ?>
                    </a>

                    <?php if (get_option('users_can_register')) : ?>
                        <span class="flavor-dashboard-separator">|</span>
                        <a href="<?php echo esc_url(wp_registration_url()); ?>" class="flavor-dashboard-link">
                            <?php esc_html_e('Crear una cuenta', 'flavor-chat-ia'); ?>
                        </a>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        <?php
        return ob_get_clean();
    }

    /**
     * Renderiza el contenido del tab activo
     *
     * Llamado desde el template dashboard.php
     *
     * @param string $identificador_tab Identificador del tab a renderizar
     */
    public function render_contenido_tab($identificador_tab) {
        $todos_los_tabs = $this->obtener_tabs();

        if (!isset($todos_los_tabs[$identificador_tab])) {
            echo '<div class="flavor-dashboard-tab-vacio">';
            esc_html_e('Seccion no encontrada', 'flavor-chat-ia');
            echo '</div>';
            return;
        }

        $configuracion_tab = $todos_los_tabs[$identificador_tab];

        if (isset($configuracion_tab['callback']) && is_callable($configuracion_tab['callback'])) {
            call_user_func($configuracion_tab['callback']);
        }
    }

    /**
     * Renderiza el tab "Mi Perfil"
     */
    public function render_tab_perfil() {
        $usuario_actual = wp_get_current_user();

        $datos_perfil = [
            'usuario'        => $usuario_actual,
            'nombre'         => $usuario_actual->first_name,
            'apellido'       => $usuario_actual->last_name,
            'email'          => $usuario_actual->user_email,
            'telefono'       => get_user_meta($usuario_actual->ID, 'billing_phone', true),
            'avatar_url'     => get_avatar_url($usuario_actual->ID, ['size' => 128]),
            'nombre_mostrar' => $usuario_actual->display_name,
        ];

        $ruta_template_perfil = FLAVOR_CHAT_IA_PATH . 'templates/frontend/mi-cuenta/perfil.php';
        if (file_exists($ruta_template_perfil)) {
            extract($datos_perfil);
            include $ruta_template_perfil;
        }
    }

    /**
     * Renderiza el tab "Notificaciones"
     */
    public function render_tab_notificaciones() {
        $usuario_actual = wp_get_current_user();

        $lista_notificaciones = [];
        $cantidad_sin_leer    = 0;

        if (class_exists('Flavor_Notification_Manager')) {
            $gestor_notificaciones = Flavor_Notification_Manager::get_instance();
            $lista_notificaciones  = $gestor_notificaciones->get_user_notifications($usuario_actual->ID, [
                'limit' => 30,
            ]);
            $cantidad_sin_leer = $gestor_notificaciones->get_unread_count($usuario_actual->ID);
        }

        $datos_notificaciones = [
            'notificaciones' => $lista_notificaciones,
            'sin_leer'       => $cantidad_sin_leer,
            'usuario'        => $usuario_actual,
        ];

        $ruta_template_notificaciones = FLAVOR_CHAT_IA_PATH . 'templates/frontend/mi-cuenta/notificaciones.php';
        if (file_exists($ruta_template_notificaciones)) {
            extract($datos_notificaciones);
            include $ruta_template_notificaciones;
        }
    }

    /**
     * Obtiene la URL del dashboard con un tab especifico
     *
     * @param string $identificador_tab Identificador del tab
     * @return string URL completa del tab
     */
    public function obtener_url_tab($identificador_tab) {
        $pagina_mi_cuenta = get_page_by_path('mi-cuenta');
        $url_base = $pagina_mi_cuenta ? get_permalink($pagina_mi_cuenta->ID) : home_url('/mi-cuenta/');

        if ($identificador_tab === 'perfil') {
            return $url_base;
        }

        return add_query_arg('tab', $identificador_tab, $url_base);
    }

    /**
     * Genera el icono SVG para un tab dado su nombre de icono
     *
     * @param string $nombre_icono Nombre del icono (user, bell, cart, etc.)
     * @return string SVG del icono
     */
    public function obtener_icono_svg($nombre_icono) {
        $iconos_disponibles = [
            'user'     => '<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>',
            'bell'     => '<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M18 8A6 6 0 0 0 6 8c0 7-3 9-3 9h18s-3-2-3-9"/><path d="M13.73 21a2 2 0 0 1-3.46 0"/></svg>',
            'cart'     => '<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="9" cy="21" r="1"/><circle cx="20" cy="21" r="1"/><path d="M1 1h4l2.68 13.39a2 2 0 0 0 2 1.61h9.72a2 2 0 0 0 2-1.61L23 6H6"/></svg>',
            'calendar' => '<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="4" width="18" height="18" rx="2" ry="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/></svg>',
            'users'    => '<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/></svg>',
            'heart'    => '<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M20.84 4.61a5.5 5.5 0 0 0-7.78 0L12 5.67l-1.06-1.06a5.5 5.5 0 0 0-7.78 7.78l1.06 1.06L12 21.23l7.78-7.78 1.06-1.06a5.5 5.5 0 0 0 0-7.78z"/></svg>',
            'settings' => '<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="3"/><path d="M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 0 1 0 2.83 2 2 0 0 1-2.83 0l-.06-.06a1.65 1.65 0 0 0-1.82-.33 1.65 1.65 0 0 0-1 1.51V21a2 2 0 0 1-4 0v-.09A1.65 1.65 0 0 0 9 19.4a1.65 1.65 0 0 0-1.82.33l-.06.06a2 2 0 0 1-2.83-2.83l.06-.06A1.65 1.65 0 0 0 4.68 15a1.65 1.65 0 0 0-1.51-1H3a2 2 0 0 1 0-4h.09A1.65 1.65 0 0 0 4.6 9a1.65 1.65 0 0 0-.33-1.82l-.06-.06a2 2 0 0 1 2.83-2.83l.06.06A1.65 1.65 0 0 0 9 4.68a1.65 1.65 0 0 0 1-1.51V3a2 2 0 0 1 4 0v.09a1.65 1.65 0 0 0 1 1.51 1.65 1.65 0 0 0 1.82-.33l.06-.06a2 2 0 0 1 2.83 2.83l-.06.06A1.65 1.65 0 0 0 19.4 9a1.65 1.65 0 0 0 1.51 1H21a2 2 0 0 1 0 4h-.09a1.65 1.65 0 0 0-1.51 1z"/></svg>',
            'file'     => '<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/></svg>',
            'clock'    => '<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>',
            'star'     => '<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"/></svg>',
            'map'      => '<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"/><circle cx="12" cy="10" r="3"/></svg>',
            'box'      => '<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 16V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16z"/></svg>',
            'book'     => '<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M4 19.5A2.5 2.5 0 0 1 6.5 17H20"/><path d="M6.5 2H20v20H6.5A2.5 2.5 0 0 1 4 19.5v-15A2.5 2.5 0 0 1 6.5 2z"/></svg>',
            'message'  => '<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/></svg>',
            'logout'   => '<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/><polyline points="16 17 21 12 16 7"/><line x1="21" y1="12" x2="9" y2="12"/></svg>',
        ];

        return $iconos_disponibles[$nombre_icono] ?? $iconos_disponibles['file'];
    }

    /**
     * AJAX: Cambiar de tab (para carga dinamica sin recarga de pagina)
     */
    public function ajax_cambiar_tab() {
        check_ajax_referer('flavor_user_dashboard', 'nonce');

        if (!is_user_logged_in()) {
            wp_send_json_error(['message' => __('No autorizado', 'flavor-chat-ia')]);
        }

        $identificador_tab = isset($_POST['tab']) ? sanitize_key($_POST['tab']) : 'perfil';
        $todos_los_tabs    = $this->obtener_tabs();

        if (!isset($todos_los_tabs[$identificador_tab])) {
            wp_send_json_error(['message' => __('Seccion no encontrada', 'flavor-chat-ia')]);
        }

        ob_start();
        $this->render_contenido_tab($identificador_tab);
        $contenido_html = ob_get_clean();

        wp_send_json_success([
            'html' => $contenido_html,
            'tab'  => $identificador_tab,
        ]);
    }
}

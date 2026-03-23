<?php
/**
 * Sistema de Control de Acceso para Módulos
 *
 * Gestiona la visibilidad y permisos de acceso a los módulos de Flavor Platform.
 *
 * @package FlavorChatIA
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Clase para control de acceso a módulos
 */
class Flavor_Module_Access_Control {

    /**
     * Instancia singleton
     */
    private static $instance = null;

    /**
     * Cache de verificaciones de acceso
     */
    private $cache_acceso = [];

    /**
     * Tipos de visibilidad disponibles
     */
    const VISIBILIDAD_PUBLICA = 'public';
    const VISIBILIDAD_PRIVADA = 'private';
    const VISIBILIDAD_SOLO_MIEMBROS = 'members_only';

    /**
     * Roles considerados como miembros/socios
     */
    private $roles_miembros = [
        'subscriber',
        'member',
        'socio',
        'miembro',
        'contributor',
        'author',
        'editor',
        'administrator',
    ];

    /**
     * Obtiene la instancia singleton
     *
     * @return Flavor_Module_Access_Control
     */
    public static function get_instance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Constructor privado
     */
    private function __construct() {
        // Permitir personalizar roles de miembros
        $this->roles_miembros = apply_filters('flavor_member_roles', $this->roles_miembros);

        // Registrar hooks
        add_action('init', [$this, 'registrar_capacidades_personalizadas']);
    }

    /**
     * Registra capacidades personalizadas para módulos
     */
    public function registrar_capacidades_personalizadas() {
        // Capacidad especial para acceso a módulos de socios
        $capacidades_modulos = [
            'flavor_access_members_content' => __('Acceder a contenido de miembros', 'flavor-chat-ia'),
            'flavor_access_private_modules' => __('Acceder a módulos privados', 'flavor-chat-ia'),
            'flavor_manage_module_access' => __('Gestionar acceso a módulos', 'flavor-chat-ia'),
        ];

        // Añadir capacidades al rol de administrador si no existen
        $rol_administrador = get_role('administrator');
        if ($rol_administrador) {
            foreach (array_keys($capacidades_modulos) as $capacidad) {
                if (!$rol_administrador->has_cap($capacidad)) {
                    $rol_administrador->add_cap($capacidad);
                }
            }
        }
    }

    /**
     * Verifica si un usuario puede acceder a un módulo
     *
     * @param string $module_slug Slug/ID del módulo
     * @param int|null $user_id ID del usuario (null para usuario actual)
     * @return bool
     */
    public function user_can_access($module_slug, $user_id = null) {
        // Obtener usuario
        if ($user_id === null) {
            $user_id = get_current_user_id();
        }

        // Verificar cache
        $clave_cache = $module_slug . '_' . $user_id;
        if (isset($this->cache_acceso[$clave_cache])) {
            return $this->cache_acceso[$clave_cache];
        }

        // Verificar si el módulo está disponible según la licencia del sitio
        if (!$this->is_module_licensed($module_slug)) {
            $this->cache_acceso[$clave_cache] = false;
            return false;
        }

        // Obtener visibilidad del módulo
        $visibilidad = $this->obtener_visibilidad_modulo($module_slug);
        $capacidad_requerida = $this->obtener_capacidad_modulo($module_slug);

        // Evaluar acceso según visibilidad
        $tiene_acceso = $this->evaluar_acceso($visibilidad, $capacidad_requerida, $user_id);

        // Permitir filtrar el resultado
        $tiene_acceso = apply_filters('flavor_module_access', $tiene_acceso, $module_slug, $user_id);
        $tiene_acceso = apply_filters("flavor_module_access_{$module_slug}", $tiene_acceso, $user_id);

        // Guardar en cache
        $this->cache_acceso[$clave_cache] = $tiene_acceso;

        return $tiene_acceso;
    }

    /**
     * Verifica si un módulo está disponible según la licencia del sitio
     *
     * @param string $module_slug Slug del módulo
     * @return bool
     */
    public function is_module_licensed($module_slug) {
        // Si no existe el gestor de licencias, permitir todo (compatibilidad hacia atrás)
        if (!function_exists('flavor_can_use_module')) {
            return true;
        }

        // Verificar con el sistema de licencias
        $is_licensed = flavor_can_use_module($module_slug);

        // Permitir filtrar el resultado
        return apply_filters('flavor_module_licensed', $is_licensed, $module_slug);
    }

    /**
     * Evalúa el acceso según visibilidad y capacidad
     *
     * @param string $visibilidad Tipo de visibilidad
     * @param string $capacidad Capacidad requerida
     * @param int $user_id ID del usuario
     * @return bool
     */
    private function evaluar_acceso($visibilidad, $capacidad, $user_id) {
        switch ($visibilidad) {
            case self::VISIBILIDAD_PUBLICA:
                return true;

            case self::VISIBILIDAD_PRIVADA:
                // Requiere estar logueado y tener la capacidad específica
                if (!$user_id) {
                    return false;
                }
                return user_can($user_id, $capacidad) || user_can($user_id, 'flavor_access_private_modules');

            case self::VISIBILIDAD_SOLO_MIEMBROS:
                // Requiere ser miembro/socio
                if (!$user_id) {
                    return false;
                }
                return $this->es_miembro($user_id) || user_can($user_id, 'flavor_access_members_content');

            default:
                return true;
        }
    }

    /**
     * Verifica si un usuario es miembro/socio
     *
     * @param int $user_id ID del usuario
     * @return bool
     */
    public function es_miembro($user_id) {
        if (!$user_id) {
            return false;
        }

        $usuario = get_userdata($user_id);
        if (!$usuario) {
            return false;
        }

        // Verificar roles
        $roles_usuario = $usuario->roles;
        $es_miembro = !empty(array_intersect($roles_usuario, $this->roles_miembros));

        // Verificar meta personalizado de socio
        $es_socio_meta = get_user_meta($user_id, 'es_socio', true);
        $estado_membresia = get_user_meta($user_id, 'estado_membresia', true);

        // Considerar socio si tiene el meta o si su membresía está activa
        if ($es_socio_meta === '1' || $es_socio_meta === 'yes' || $estado_membresia === 'activo') {
            $es_miembro = true;
        }

        // Permitir filtrar
        return apply_filters('flavor_user_is_member', $es_miembro, $user_id);
    }

    /**
     * Obtiene la visibilidad configurada de un módulo
     *
     * @param string $module_slug Slug del módulo
     * @return string
     */
    public function obtener_visibilidad_modulo($module_slug) {
        // Usar caché centralizada del Module Loader
        if (class_exists('Flavor_Chat_Module_Loader')) {
            $visibilidades = Flavor_Chat_Module_Loader::get_visibility_settings_cached();
        } else {
            $visibilidades = get_option('flavor_modules_visibility', []);
        }

        if (isset($visibilidades[$module_slug])) {
            return $visibilidades[$module_slug];
        }

        // Obtener visibilidad por defecto del módulo
        return $this->obtener_visibilidad_por_defecto($module_slug);
    }

    /**
     * Obtiene la capacidad requerida de un módulo
     *
     * @param string $module_slug Slug del módulo
     * @return string
     */
    public function obtener_capacidad_modulo($module_slug) {
        // Usar caché centralizada del Module Loader
        if (class_exists('Flavor_Chat_Module_Loader')) {
            $capacidades = Flavor_Chat_Module_Loader::get_capabilities_settings_cached();
        } else {
            $capacidades = get_option('flavor_modules_capabilities', []);
        }

        if (isset($capacidades[$module_slug])) {
            return $capacidades[$module_slug];
        }

        // Capacidad por defecto según el módulo
        return $this->obtener_capacidad_por_defecto($module_slug);
    }

    /**
     * Obtiene la visibilidad por defecto de un módulo
     *
     * @param string $module_slug Slug del módulo
     * @return string
     */
    private function obtener_visibilidad_por_defecto($module_slug) {
        // Mapeo de visibilidades por defecto para módulos específicos
        $visibilidades_por_defecto = [
            // Módulos públicos - cualquiera puede ver
            'eventos' => self::VISIBILIDAD_PUBLICA,
            'biblioteca' => self::VISIBILIDAD_PUBLICA,
            'avisos_municipales' => self::VISIBILIDAD_PUBLICA,
            'transparencia' => self::VISIBILIDAD_PUBLICA,
            'participacion' => self::VISIBILIDAD_PUBLICA,
            'presupuestos_participativos' => self::VISIBILIDAD_PUBLICA,
            'tramites' => self::VISIBILIDAD_PUBLICA,
            'incidencias' => self::VISIBILIDAD_PUBLICA,
            'cursos' => self::VISIBILIDAD_PUBLICA,
            'talleres' => self::VISIBILIDAD_PUBLICA,
            'reciclaje' => self::VISIBILIDAD_PUBLICA,
            'radio' => self::VISIBILIDAD_PUBLICA,
            'podcast' => self::VISIBILIDAD_PUBLICA,
            'marketplace' => self::VISIBILIDAD_PUBLICA,
            'advertising' => self::VISIBILIDAD_PUBLICA,

            // Módulos solo para miembros/socios
            'socios' => self::VISIBILIDAD_SOLO_MIEMBROS,
            'grupos_consumo' => self::VISIBILIDAD_SOLO_MIEMBROS,
            'banco_tiempo' => self::VISIBILIDAD_SOLO_MIEMBROS,
            'ayuda_vecinal' => self::VISIBILIDAD_SOLO_MIEMBROS,
            'huertos_urbanos' => self::VISIBILIDAD_SOLO_MIEMBROS,
            'compostaje' => self::VISIBILIDAD_SOLO_MIEMBROS,
            'bicicletas_compartidas' => self::VISIBILIDAD_SOLO_MIEMBROS,
            'carpooling' => self::VISIBILIDAD_SOLO_MIEMBROS,
            'espacios_comunes' => self::VISIBILIDAD_SOLO_MIEMBROS,
            'reservas' => self::VISIBILIDAD_SOLO_MIEMBROS,
            'chat_grupos' => self::VISIBILIDAD_SOLO_MIEMBROS,
            'chat_interno' => self::VISIBILIDAD_SOLO_MIEMBROS,
            'red_social' => self::VISIBILIDAD_SOLO_MIEMBROS,
            'foros' => self::VISIBILIDAD_SOLO_MIEMBROS,
            'colectivos' => self::VISIBILIDAD_SOLO_MIEMBROS,
            'comunidades' => self::VISIBILIDAD_SOLO_MIEMBROS,
            'parkings' => self::VISIBILIDAD_SOLO_MIEMBROS,
            'multimedia' => self::VISIBILIDAD_SOLO_MIEMBROS,

            // Módulos privados - solo empleados/staff
            'fichaje_empleados' => self::VISIBILIDAD_PRIVADA,
            'facturas' => self::VISIBILIDAD_PRIVADA,
            'clientes' => self::VISIBILIDAD_PRIVADA,
            'empresarial' => self::VISIBILIDAD_PRIVADA,
            'email_marketing' => self::VISIBILIDAD_PRIVADA,
            'trading_ia' => self::VISIBILIDAD_PRIVADA,
            'dex_solana' => self::VISIBILIDAD_PRIVADA,
            'themacle' => self::VISIBILIDAD_PRIVADA,
            'bares' => self::VISIBILIDAD_PRIVADA,
            'woocommerce' => self::VISIBILIDAD_PRIVADA,
        ];

        // Permitir filtrar los valores por defecto
        $visibilidades_por_defecto = apply_filters(
            'flavor_default_module_visibility',
            $visibilidades_por_defecto
        );

        return $visibilidades_por_defecto[$module_slug] ?? self::VISIBILIDAD_PUBLICA;
    }

    /**
     * Obtiene la capacidad por defecto de un módulo
     *
     * @param string $module_slug Slug del módulo
     * @return string
     */
    private function obtener_capacidad_por_defecto($module_slug) {
        // Mapeo de capacidades por defecto para módulos específicos
        $capacidades_por_defecto = [
            // Módulos públicos - solo requieren leer
            'eventos' => 'read',
            'biblioteca' => 'read',
            'avisos_municipales' => 'read',
            'transparencia' => 'read',
            'participacion' => 'read',
            'presupuestos_participativos' => 'read',
            'cursos' => 'read',
            'talleres' => 'read',
            'reciclaje' => 'read',
            'radio' => 'read',
            'podcast' => 'read',
            'marketplace' => 'read',
            'advertising' => 'read',

            // Módulos que requieren capacidades específicas
            'fichaje_empleados' => 'flavor_fichaje_acceso',
            'facturas' => 'edit_posts',
            'clientes' => 'edit_posts',
            'empresarial' => 'manage_options',
            'trading_ia' => 'manage_options',
            'dex_solana' => 'manage_options',
            'email_marketing' => 'edit_others_posts',
            'woocommerce' => 'manage_woocommerce',
        ];

        // Permitir filtrar los valores por defecto
        $capacidades_por_defecto = apply_filters(
            'flavor_default_module_capability',
            $capacidades_por_defecto
        );

        return $capacidades_por_defecto[$module_slug] ?? 'read';
    }

    /**
     * Obtiene todos los módulos accesibles para un usuario
     *
     * @param int|null $user_id ID del usuario (null para usuario actual)
     * @return array Array de IDs de módulos accesibles
     */
    public function get_accessible_modules($user_id = null) {
        if ($user_id === null) {
            $user_id = get_current_user_id();
        }

        $modulos_accesibles = [];

        // Obtener loader de módulos
        $loader = Flavor_Chat_Module_Loader::get_instance();
        $modulos_registrados = $loader->get_registered_modules();

        foreach ($modulos_registrados as $module_id => $modulo_info) {
            if ($this->user_can_access($module_id, $user_id)) {
                $modulos_accesibles[] = $module_id;
            }
        }

        return $modulos_accesibles;
    }

    /**
     * Renderiza mensaje de acceso denegado
     *
     * @param string $module_slug Slug del módulo
     * @return string HTML del mensaje
     */
    public function render_access_denied($module_slug) {
        $visibilidad = $this->obtener_visibilidad_modulo($module_slug);

        // Intentar cargar template
        $ruta_template = $this->obtener_ruta_template_acceso($visibilidad);

        if (file_exists($ruta_template)) {
            ob_start();
            include $ruta_template;
            return ob_get_clean();
        }

        // Fallback: generar HTML directamente
        return $this->generar_mensaje_acceso_denegado($module_slug, $visibilidad);
    }

    /**
     * Renderiza formulario de login si es necesario
     *
     * @param string $redirect_url URL de redirección tras login
     * @return string HTML del formulario
     */
    public function render_login_required($redirect_url = '') {
        if (empty($redirect_url)) {
            $redirect_url = $this->obtener_url_actual();
        }

        // Intentar cargar template
        $ruta_template = FLAVOR_CHAT_IA_PATH . 'templates/access/login-required.php';

        if (file_exists($ruta_template)) {
            ob_start();
            include $ruta_template;
            return ob_get_clean();
        }

        // Fallback: generar HTML directamente
        return $this->generar_formulario_login($redirect_url);
    }

    /**
     * Obtiene la ruta del template según tipo de acceso
     *
     * @param string $visibilidad Tipo de visibilidad
     * @return string Ruta al template
     */
    private function obtener_ruta_template_acceso($visibilidad) {
        $mapeo_templates = [
            self::VISIBILIDAD_SOLO_MIEMBROS => 'members-only.php',
            self::VISIBILIDAD_PRIVADA => 'access-denied.php',
        ];

        $archivo = $mapeo_templates[$visibilidad] ?? 'access-denied.php';

        // Primero buscar en tema
        $ruta_tema = get_stylesheet_directory() . '/flavor-chat-ia/access/' . $archivo;
        if (file_exists($ruta_tema)) {
            return $ruta_tema;
        }

        // Luego en plugin
        return FLAVOR_CHAT_IA_PATH . 'templates/access/' . $archivo;
    }

    /**
     * Genera HTML de mensaje de acceso denegado
     *
     * @param string $module_slug Slug del módulo
     * @param string $visibilidad Tipo de visibilidad
     * @return string HTML
     */
    private function generar_mensaje_acceso_denegado($module_slug, $visibilidad) {
        $clases_contenedor = 'flavor-access-denied flavor-access-' . esc_attr($visibilidad);

        switch ($visibilidad) {
            case self::VISIBILIDAD_SOLO_MIEMBROS:
                $icono = '<svg xmlns="http://www.w3.org/2000/svg" width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path><circle cx="9" cy="7" r="4"></circle><path d="M23 21v-2a4 4 0 0 0-3-3.87"></path><path d="M16 3.13a4 4 0 0 1 0 7.75"></path></svg>';
                $titulo = __('Contenido exclusivo para miembros', 'flavor-chat-ia');
                $mensaje = __('Este contenido solo esta disponible para miembros registrados de nuestra comunidad.', 'flavor-chat-ia');
                $cta_texto = __('Hazte miembro', 'flavor-chat-ia');
                $cta_url = apply_filters('flavor_membership_url', home_url('/hazte-socio/'));
                break;

            case self::VISIBILIDAD_PRIVADA:
                $icono = '<svg xmlns="http://www.w3.org/2000/svg" width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="11" width="18" height="11" rx="2" ry="2"></rect><path d="M7 11V7a5 5 0 0 1 10 0v4"></path></svg>';
                $titulo = __('Acceso restringido', 'flavor-chat-ia');
                $mensaje = __('No tienes permisos para acceder a este contenido. Si crees que esto es un error, contacta con el administrador.', 'flavor-chat-ia');
                $cta_texto = __('Contactar', 'flavor-chat-ia');
                $cta_url = apply_filters('flavor_contact_url', home_url('/contacto/'));
                break;

            default:
                $icono = '<svg xmlns="http://www.w3.org/2000/svg" width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"></circle><line x1="12" y1="8" x2="12" y2="12"></line><line x1="12" y1="16" x2="12.01" y2="16"></line></svg>';
                $titulo = __('Contenido no disponible', 'flavor-chat-ia');
                $mensaje = __('Este contenido no esta disponible en este momento.', 'flavor-chat-ia');
                $cta_texto = __('Volver al inicio', 'flavor-chat-ia');
                $cta_url = home_url('/');
        }

        $html = '<div class="' . $clases_contenedor . '">';
        $html .= '<div class="flavor-access-icon">' . $icono . '</div>';
        $html .= '<h3 class="flavor-access-title">' . esc_html($titulo) . '</h3>';
        $html .= '<p class="flavor-access-message">' . esc_html($mensaje) . '</p>';

        if (!is_user_logged_in()) {
            $url_login = wp_login_url($this->obtener_url_actual());
            $html .= '<div class="flavor-access-actions">';
            $html .= '<a href="' . esc_url($url_login) . '" class="flavor-btn flavor-btn-primary">';
            $html .= esc_html__('Iniciar sesion', 'flavor-chat-ia');
            $html .= '</a>';
            $html .= '<a href="' . esc_url($cta_url) . '" class="flavor-btn flavor-btn-secondary">';
            $html .= esc_html($cta_texto);
            $html .= '</a>';
            $html .= '</div>';
        } else {
            $html .= '<div class="flavor-access-actions">';
            $html .= '<a href="' . esc_url($cta_url) . '" class="flavor-btn flavor-btn-primary">';
            $html .= esc_html($cta_texto);
            $html .= '</a>';
            $html .= '</div>';
        }

        $html .= '</div>';

        // Agregar estilos inline
        $html .= $this->obtener_estilos_acceso();

        return $html;
    }

    /**
     * Genera formulario de login
     *
     * @param string $redirect_url URL de redirección
     * @return string HTML
     */
    private function generar_formulario_login($redirect_url) {
        $html = '<div class="flavor-login-required">';
        $html .= '<div class="flavor-login-icon">';
        $html .= '<svg xmlns="http://www.w3.org/2000/svg" width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M15 3h4a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2h-4"></path><polyline points="10 17 15 12 10 7"></polyline><line x1="15" y1="12" x2="3" y2="12"></line></svg>';
        $html .= '</div>';
        $html .= '<h3 class="flavor-login-title">' . esc_html__('Inicia sesion para continuar', 'flavor-chat-ia') . '</h3>';
        $html .= '<p class="flavor-login-message">' . esc_html__('Necesitas iniciar sesion para acceder a este contenido.', 'flavor-chat-ia') . '</p>';

        // Formulario de WordPress
        $argumentos_formulario = [
            'redirect' => $redirect_url,
            'form_id' => 'flavor-login-form',
            'label_username' => __('Usuario o email', 'flavor-chat-ia'),
            'label_password' => __('Contrasena', 'flavor-chat-ia'),
            'label_remember' => __('Recordarme', 'flavor-chat-ia'),
            'label_log_in' => __('Iniciar sesion', 'flavor-chat-ia'),
            'remember' => true,
        ];

        $html .= '<div class="flavor-login-form-wrapper">';
        $html .= wp_login_form(array_merge($argumentos_formulario, ['echo' => false]));
        $html .= '</div>';

        // Enlaces adicionales
        $html .= '<div class="flavor-login-links">';
        $html .= '<a href="' . esc_url(wp_lostpassword_url($redirect_url)) . '">';
        $html .= esc_html__('Olvidaste tu contrasena?', 'flavor-chat-ia');
        $html .= '</a>';

        if (get_option('users_can_register')) {
            $html .= ' | <a href="' . esc_url(wp_registration_url()) . '">';
            $html .= esc_html__('Registrarse', 'flavor-chat-ia');
            $html .= '</a>';
        }

        $html .= '</div>';
        $html .= '</div>';

        // Agregar estilos inline
        $html .= $this->obtener_estilos_acceso();

        return $html;
    }

    /**
     * Obtiene la URL actual
     *
     * @return string
     */
    private function obtener_url_actual() {
        global $wp;
        return home_url(add_query_arg([], $wp->request));
    }

    /**
     * Obtiene estilos CSS para los mensajes de acceso
     *
     * @return string CSS inline
     */
    private function obtener_estilos_acceso() {
        return '
        <style>
            .flavor-access-denied,
            .flavor-login-required {
                max-width: 480px;
                margin: 2rem auto;
                padding: 2.5rem;
                text-align: center;
                background: linear-gradient(135deg, #f8f9fa 0%, #ffffff 100%);
                border-radius: 16px;
                box-shadow: 0 4px 20px rgba(0, 0, 0, 0.08);
                border: 1px solid rgba(0, 0, 0, 0.06);
            }

            .flavor-access-icon,
            .flavor-login-icon {
                margin-bottom: 1.5rem;
                color: #6c757d;
            }

            .flavor-access-members .flavor-access-icon {
                color: #0d6efd;
            }

            .flavor-access-private .flavor-access-icon {
                color: #dc3545;
            }

            .flavor-access-title,
            .flavor-login-title {
                margin: 0 0 1rem;
                font-size: 1.5rem;
                font-weight: 600;
                color: #212529;
            }

            .flavor-access-message,
            .flavor-login-message {
                margin: 0 0 1.5rem;
                color: #6c757d;
                line-height: 1.6;
            }

            .flavor-access-actions {
                display: flex;
                gap: 0.75rem;
                justify-content: center;
                flex-wrap: wrap;
            }

            .flavor-btn {
                display: inline-flex;
                align-items: center;
                justify-content: center;
                padding: 0.75rem 1.5rem;
                font-size: 0.95rem;
                font-weight: 500;
                text-decoration: none;
                border-radius: 8px;
                transition: all 0.2s ease;
                cursor: pointer;
                border: none;
            }

            .flavor-btn-primary {
                background: #0d6efd;
                color: #ffffff;
            }

            .flavor-btn-primary:hover {
                background: #0b5ed7;
                color: #ffffff;
                transform: translateY(-1px);
            }

            .flavor-btn-secondary {
                background: #e9ecef;
                color: #495057;
            }

            .flavor-btn-secondary:hover {
                background: #dee2e6;
                color: #212529;
            }

            .flavor-login-form-wrapper {
                margin-bottom: 1.5rem;
            }

            .flavor-login-form-wrapper form {
                text-align: left;
            }

            .flavor-login-form-wrapper label {
                display: block;
                margin-bottom: 0.5rem;
                font-weight: 500;
                color: #495057;
            }

            .flavor-login-form-wrapper input[type="text"],
            .flavor-login-form-wrapper input[type="password"] {
                width: 100%;
                padding: 0.75rem 1rem;
                margin-bottom: 1rem;
                border: 1px solid #ced4da;
                border-radius: 8px;
                font-size: 1rem;
                transition: border-color 0.2s, box-shadow 0.2s;
            }

            .flavor-login-form-wrapper input[type="text"]:focus,
            .flavor-login-form-wrapper input[type="password"]:focus {
                border-color: #0d6efd;
                outline: none;
                box-shadow: 0 0 0 3px rgba(13, 110, 253, 0.15);
            }

            .flavor-login-form-wrapper input[type="submit"] {
                width: 100%;
                padding: 0.75rem;
                background: #0d6efd;
                color: #ffffff;
                border: none;
                border-radius: 8px;
                font-size: 1rem;
                font-weight: 500;
                cursor: pointer;
                transition: background 0.2s;
            }

            .flavor-login-form-wrapper input[type="submit"]:hover {
                background: #0b5ed7;
            }

            .flavor-login-links {
                font-size: 0.9rem;
                color: #6c757d;
            }

            .flavor-login-links a {
                color: #0d6efd;
                text-decoration: none;
            }

            .flavor-login-links a:hover {
                text-decoration: underline;
            }
        </style>';
    }

    /**
     * Limpia la cache de acceso
     *
     * @param int|null $user_id ID del usuario (null para limpiar todo)
     */
    public function limpiar_cache($user_id = null) {
        if ($user_id === null) {
            $this->cache_acceso = [];
        } else {
            // Limpiar solo entradas de este usuario
            foreach (array_keys($this->cache_acceso) as $clave) {
                if (strpos($clave, '_' . $user_id) !== false) {
                    unset($this->cache_acceso[$clave]);
                }
            }
        }
    }

    /**
     * Obtiene los tipos de visibilidad disponibles
     *
     * @return array
     */
    public static function get_visibility_types() {
        return [
            self::VISIBILIDAD_PUBLICA => __('Publico - Cualquier visitante', 'flavor-chat-ia'),
            self::VISIBILIDAD_SOLO_MIEMBROS => __('Solo miembros - Usuarios registrados', 'flavor-chat-ia'),
            self::VISIBILIDAD_PRIVADA => __('Privado - Solo usuarios con permisos especificos', 'flavor-chat-ia'),
        ];
    }

    /**
     * Obtiene las capacidades disponibles de WordPress
     *
     * @return array
     */
    public static function get_available_capabilities() {
        return [
            'read' => __('Leer (cualquier usuario registrado)', 'flavor-chat-ia'),
            'edit_posts' => __('Editar publicaciones (colaboradores+)', 'flavor-chat-ia'),
            'edit_others_posts' => __('Editar publicaciones de otros (editores+)', 'flavor-chat-ia'),
            'manage_options' => __('Gestionar opciones (solo administradores)', 'flavor-chat-ia'),
            'manage_woocommerce' => __('Gestionar WooCommerce (solo gerentes de tienda)', 'flavor-chat-ia'),
            'flavor_access_members_content' => __('Acceso a contenido de miembros', 'flavor-chat-ia'),
            'flavor_access_private_modules' => __('Acceso a modulos privados', 'flavor-chat-ia'),
            'flavor_fichaje_acceso' => __('Acceso al sistema de fichaje', 'flavor-chat-ia'),
        ];
    }
}

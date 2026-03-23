<?php
/**
 * Sistema de Páginas Dinámicas
 *
 * Maneja todas las páginas de módulos desde un solo punto,
 * eliminando la necesidad de crear páginas individuales para cada módulo.
 *
 * Rutas manejadas:
 * - /app/                     → Dashboard principal
 * - /app/mi-cuenta/           → Dashboard de usuario
 * - /app/{modulo}/            → Vista principal del módulo
 * - /app/{modulo}/{accion}/   → Acción específica (crear, editar, ver)
 * - /app/{modulo}/{id}/       → Ver elemento específico
 *
 * @package FlavorChatIA
 * @since 3.1.0
 */

if (!defined('ABSPATH')) {
    exit;
}

class Flavor_Dynamic_Pages {

    /**
     * Instancia singleton
     */
    private static $instance = null;

    /**
     * Prefijo de ruta base
     * Usar 'mi-portal' para evitar conflictos con páginas existentes
     */
    private $base_path = 'mi-portal';

    /**
     * Módulo actual
     */
    private $current_module = null;

    /**
     * Acción actual
     */
    private $current_action = null;

    /**
     * ID del elemento actual
     */
    private $current_item_id = null;

    /**
     * Obtiene la URL actual para redirects de login en páginas dinámicas.
     */
    private function get_current_request_url(): string {
        $request_uri = isset($_SERVER['REQUEST_URI']) ? wp_unslash((string) $_SERVER['REQUEST_URI']) : '/';
        $request_uri = '/' . ltrim($request_uri, '/');

        return home_url($request_uri);
    }

    /**
     * Obtener instancia singleton
     */
    public static function get_instance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Slugs de módulos para rutas directas
     * Mapea slug URL -> module_id
     */
    private $module_slugs = [
        // Eventos y Actividades
        'eventos' => 'eventos',
        'talleres' => 'talleres',
        'cursos' => 'cursos',
        // Reservas y Espacios
        'reservas' => 'reservas',
        'espacios' => 'espacios_comunes',
        'espacios-comunes' => 'espacios_comunes',
        'huertos' => 'huertos_urbanos',
        'huertos-urbanos' => 'huertos_urbanos',
        // Movilidad
        'bicicletas' => 'bicicletas_compartidas',
        'bicicletas-compartidas' => 'bicicletas_compartidas',
        'carpooling' => 'carpooling',
        'parkings' => 'parkings',
        // Comunidad y Social
        'comunidades' => 'comunidades',
        'colectivos' => 'colectivos',
        'foros' => 'foros',
        'red-social' => 'red_social',
        'chat' => 'chat_interno',
        'chat-interno' => 'chat_interno',
        'chat-grupos' => 'chat_grupos',
        'grupos' => 'chat_grupos',
        // Incidencias y Participación
        'incidencias' => 'incidencias',
        'participacion' => 'participacion',
        'presupuestos' => 'presupuestos_participativos',
        'presupuestos-participativos' => 'presupuestos_participativos',
        // Comercio y Economía
        'marketplace' => 'marketplace',
        'grupos-consumo' => 'grupos_consumo',
        'banco-tiempo' => 'banco_tiempo',
        'banco-de-tiempo' => 'banco_tiempo',
        'economia-don' => 'economia_don',
        'economia-suficiencia' => 'economia_suficiencia',
        'energia-comunitaria' => 'energia_comunitaria',
        // Biblioteca y Multimedia
        'biblioteca' => 'biblioteca',
        'multimedia' => 'multimedia',
        'podcast' => 'podcast',
        'radio' => 'radio',
        'recetas' => 'recetas',
        // Ayuda y Cuidados
        'ayuda-vecinal' => 'ayuda_vecinal',
        'circulos-cuidados' => 'circulos_cuidados',
        'justicia-restaurativa' => 'justicia_restaurativa',
        // Ecología
        'compostaje' => 'compostaje',
        'reciclaje' => 'reciclaje',
        'huella-ecologica' => 'huella_ecologica',
        'biodiversidad-local' => 'biodiversidad_local',
        'biodiversidad' => 'biodiversidad_local',
        // Cultura y Saberes
        'saberes-ancestrales' => 'saberes_ancestrales',
        'saberes' => 'saberes_ancestrales',
        // Trámites y Administración
        'tramites' => 'tramites',
        'avisos' => 'avisos_municipales',
        'avisos-municipales' => 'avisos_municipales',
        'transparencia' => 'transparencia',
        'seguimiento-denuncias' => 'seguimiento_denuncias',
        'denuncias' => 'seguimiento_denuncias',
        'documentacion-legal' => 'documentacion_legal',
        // Campañas y Mapeo
        'campanias' => 'campanias',
        'mapa-actores' => 'mapa_actores',
        // Empleo y Trabajo
        'trabajo-digno' => 'trabajo_digno',
        'fichaje' => 'fichaje_empleados',
        'fichaje-empleados' => 'fichaje_empleados',
        // Socios y Membresías
        'socios' => 'socios',
        'facturas' => 'facturas',
        'clientes' => 'clientes',
        // Servicios Locales
        'bares' => 'bares',
        // Sistema
        'sello-conciencia' => 'sello_conciencia',
        // Empresarial y Marketing
        'advertising' => 'advertising',
        'publicidad' => 'advertising',
        'empresarial' => 'empresarial',
        'email-marketing' => 'email_marketing',
        // Otros
        'dex-solana' => 'dex_solana',
        'trading' => 'trading_ia',
        'trading-ia' => 'trading_ia',
        'themacle' => 'themacle',
        'mi-red' => 'mi_red',
    ];

    /**
     * Constructor
     */
    private function __construct() {
        add_action('init', [$this, 'add_rewrite_rules'], 10);
        add_filter('query_vars', [$this, 'add_query_vars']);
        add_action('template_redirect', [$this, 'handle_dynamic_page'], 5); // Prioridad alta
        add_filter('document_title_parts', [$this, 'filter_page_title']);

        // Interceptar páginas que podrían conflictuar con nuestras rutas
        add_action('template_redirect', [$this, 'intercept_portal_page'], 1);

        // Interceptar rutas directas de módulos (ej: /eventos/, /bicicletas/)
        add_action('template_redirect', [$this, 'intercept_direct_module_routes'], 2);

        // Shortcode para embeber el sistema en cualquier página
        add_shortcode('flavor_app', [$this, 'render_app']);

        // Flush rewrite rules si es necesario (una vez)
        add_action('init', [$this, 'maybe_flush_rules'], 999);

        // AJAX para guardar configuración de usuario
        add_action('wp_ajax_flavor_save_user_settings', [$this, 'ajax_save_user_settings']);
        add_action('wp_ajax_flavor_delete_user_account', [$this, 'ajax_delete_user_account']);

        // Filtro para avatar personalizado
        add_filter('get_avatar_url', [$this, 'filter_custom_avatar_url'], 10, 3);
    }

    /**
     * Filtro para usar avatar personalizado si existe
     */
    public function filter_custom_avatar_url($url, $id_or_email, $args) {
        $user_id = 0;

        if (is_numeric($id_or_email)) {
            $user_id = (int) $id_or_email;
        } elseif (is_object($id_or_email)) {
            if (!empty($id_or_email->user_id)) {
                $user_id = (int) $id_or_email->user_id;
            } elseif (!empty($id_or_email->ID)) {
                $user_id = (int) $id_or_email->ID;
            }
        } elseif (is_string($id_or_email) && is_email($id_or_email)) {
            $user = get_user_by('email', $id_or_email);
            if ($user) {
                $user_id = $user->ID;
            }
        }

        if ($user_id > 0) {
            $custom_avatar = get_user_meta($user_id, 'flavor_custom_avatar', true);
            if (!empty($custom_avatar)) {
                return $custom_avatar;
            }
        }

        return $url;
    }

    /**
     * Intercepta la página mi-portal si existe para redirigir al sistema dinámico
     */
    public function intercept_portal_page() {
        global $post;

        // Si estamos en una página con slug que comienza con mi-portal
        if (is_page() && $post && strpos($post->post_name, 'mi-portal') === 0) {
            // Obtener la URL actual
            $request_uri = trim($_SERVER['REQUEST_URI'], '/');
            $base = $this->base_path;

            // Si la URL tiene más segmentos después de mi-portal (módulo, acción, etc.)
            if (preg_match("#^{$base}/([^/]+)#", $request_uri, $matches)) {
                // Parsear la URL manualmente
                $parts = explode('/', $request_uri);
                array_shift($parts); // Quitar "mi-portal"

                $module = $parts[0] ?? '';
                $action = $parts[1] ?? 'index';
                $item_id = isset($parts[2]) ? absint($parts[2]) : 0;

                // Si la acción es numérica, es un ID, no una acción
                if (is_numeric($action)) {
                    $item_id = absint($action);
                    $action = 'ver';
                }

                if ($module) {
                    // Resetear estado 404 de WordPress
                    global $wp_query;
                    $wp_query->is_404 = false;
                    $wp_query->is_page = true;
                    $wp_query->is_singular = true;

                    // Setear variables y manejar como página dinámica
                    $this->current_module = sanitize_key($module);
                    $this->current_action = sanitize_key($action);
                    $this->current_item_id = $item_id;

                    $this->disable_shortcode_unautop();
                    $this->enqueue_assets();
                    $this->render_page();
                    exit;
                }
            }
        }
    }

    /**
     * Intercepta rutas directas de módulos (ej: /eventos/, /bicicletas/)
     * Esto permite acceder a los módulos sin pasar por /mi-portal/
     */
    public function intercept_direct_module_routes() {
        // Solo procesar si es un 404
        if (!is_404()) {
            return;
        }

        // Obtener la ruta solicitada
        $request_uri = trim($_SERVER['REQUEST_URI'], '/');

        // Quitar query strings
        $request_uri = strtok($request_uri, '?');

        // Parsear la ruta
        $parts = explode('/', $request_uri);
        $slug = $parts[0] ?? '';

        if (empty($slug)) {
            return;
        }

        // Verificar si el slug corresponde a un módulo
        $module_id = $this->get_module_id_from_slug($slug);

        if (!$module_id) {
            return;
        }

        // Verificar que el módulo existe y está activo
        if (!$this->is_module_available($module_id)) {
            return;
        }

        // Parsear acción e ID
        $action = isset($parts[1]) && !empty($parts[1]) ? sanitize_key($parts[1]) : 'index';
        $item_id = isset($parts[2]) ? absint($parts[2]) : 0;

        // Si la acción es numérica, es un ID, no una acción
        if (is_numeric($action)) {
            $item_id = absint($action);
            $action = 'ver';
        }

        // Resetear estado 404 de WordPress
        global $wp_query;
        $wp_query->is_404 = false;
        $wp_query->is_page = true;
        $wp_query->is_singular = true;

        // Setear variables del módulo
        $this->current_module = $module_id;
        $this->current_action = $action;
        $this->current_item_id = $item_id;

        // Cargar assets y renderizar
        $this->disable_shortcode_unautop();
        $this->enqueue_assets();
        $this->render_page();
        exit;
    }

    /**
     * Obtiene el ID del módulo a partir de un slug URL
     *
     * @param string $slug
     * @return string|null
     */
    private function get_module_id_from_slug($slug) {
        $slug = sanitize_key($slug);

        // Buscar en el mapeo directo
        if (isset($this->module_slugs[$slug])) {
            return $this->module_slugs[$slug];
        }

        // Intentar convertir guiones a guiones bajos (formato de ID)
        $module_id = str_replace('-', '_', $slug);

        // Verificar si es un ID válido de módulo
        if ($this->is_module_available($module_id)) {
            return $module_id;
        }

        return null;
    }

    /**
     * Verifica si un módulo está disponible (existe y puede activarse)
     *
     * @param string $module_id
     * @return bool
     */
    private function is_module_available($module_id) {
        // Verificar si el módulo existe en el loader
        if (class_exists('Flavor_Chat_Module_Loader')) {
            $loader = Flavor_Chat_Module_Loader::get_instance();

            // Verificar si el módulo está registrado
            $registered_modules = $loader->get_registered_modules();
            if (!isset($registered_modules[$module_id])) {
                return false;
            }

            // Verificar si el módulo está cargado o puede cargarse
            if ($loader->is_module_loaded($module_id)) {
                return true;
            }

            // Intentar verificar si puede activarse
            $module = $loader->get_module_instance($module_id);
            if ($module && method_exists($module, 'can_activate')) {
                return $module->can_activate();
            }

            return true; // Asumir disponible si está registrado
        }

        return false;
    }

    /**
     * Flush rewrite rules si es la primera vez o si se solicita
     */
    public function maybe_flush_rules() {
        // Forzar flush cuando cambia la ruta base o la versión
        $current_key = FLAVOR_CHAT_IA_VERSION . '_' . $this->base_path . '_v20_404_fix';
        if (get_option('flavor_dynamic_pages_rules_flushed') !== $current_key) {
            flush_rewrite_rules();
            update_option('flavor_dynamic_pages_rules_flushed', $current_key);
        }
    }

    /**
     * Añade las reglas de reescritura
     */
    public function add_rewrite_rules() {
        $base = $this->base_path;

        // /app/ - Dashboard principal
        add_rewrite_rule(
            "^{$base}/?$",
            'index.php?flavor_app=1&flavor_section=dashboard',
            'top'
        );

        // /app/mi-cuenta/ - Dashboard de usuario
        add_rewrite_rule(
            "^{$base}/mi-cuenta/?$",
            'index.php?flavor_app=1&flavor_section=mi-cuenta',
            'top'
        );

        // /app/{modulo}/ - Vista principal del módulo
        add_rewrite_rule(
            "^{$base}/([^/]+)/?$",
            'index.php?flavor_app=1&flavor_module=$matches[1]',
            'top'
        );

        // /app/{modulo}/{accion}/ - Acción específica
        add_rewrite_rule(
            "^{$base}/([^/]+)/([^/]+)/?$",
            'index.php?flavor_app=1&flavor_module=$matches[1]&flavor_action=$matches[2]',
            'top'
        );

        // /app/{modulo}/{accion}/{id}/ - Elemento específico
        add_rewrite_rule(
            "^{$base}/([^/]+)/([^/]+)/([0-9]+)/?$",
            'index.php?flavor_app=1&flavor_module=$matches[1]&flavor_action=$matches[2]&flavor_item_id=$matches[3]',
            'top'
        );
    }

    /**
     * Añade las variables de query
     */
    public function add_query_vars($vars) {
        $vars[] = 'flavor_app';
        $vars[] = 'flavor_section';
        $vars[] = 'flavor_module';
        $vars[] = 'flavor_action';
        $vars[] = 'flavor_item_id';
        return $vars;
    }

    /**
     * Maneja la página dinámica
     */
    public function handle_dynamic_page() {
        if (!get_query_var('flavor_app')) {
            return;
        }

        // Resetear el estado 404 de WordPress
        // WordPress marca como 404 porque no encuentra una página real,
        // pero nosotros manejamos estas rutas dinámicamente
        global $wp_query;
        $wp_query->is_404 = false;
        $wp_query->is_page = true;
        $wp_query->is_singular = true;

        // Parsear variables
        $this->current_module = sanitize_key(get_query_var('flavor_module', ''));
        $this->current_action = sanitize_key(get_query_var('flavor_action', 'index'));
        $this->current_item_id = absint(get_query_var('flavor_item_id', 0));
        $section = sanitize_key(get_query_var('flavor_section', ''));

        // Si la acción es numérica, es un ID, no una acción
        if (is_numeric($this->current_action)) {
            $this->current_item_id = absint($this->current_action);
            $this->current_action = 'ver';
        }

        // Cargar assets
        $this->disable_shortcode_unautop();
        $this->enqueue_assets();

        // Renderizar página
        $this->render_page($section);
        exit;
    }

    /**
     * Encola los assets necesarios
     */
    private function enqueue_assets() {
        wp_enqueue_style('dashicons');

        // Alpine.js para interactividad
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

        // CSS global del portal (incluye variables de Design Settings)
        if (file_exists(FLAVOR_CHAT_IA_PATH . 'assets/css/layouts/portal.css')) {
            wp_enqueue_style(
                'flavor-portal',
                FLAVOR_CHAT_IA_URL . 'assets/css/layouts/portal.css',
                [],
                FLAVOR_CHAT_IA_VERSION
            );
        }

        // CSS del dashboard
        if (file_exists(FLAVOR_CHAT_IA_PATH . 'assets/css/layouts/dashboard-vb-widgets.css')) {
            wp_enqueue_style(
                'flavor-dynamic-pages',
                FLAVOR_CHAT_IA_URL . 'assets/css/layouts/dashboard-vb-widgets.css',
                ['flavor-portal'],
                FLAVOR_CHAT_IA_VERSION
            );
        }

        // CSS del portal unificado (sistema de layouts)
        $sufijo_min = (defined('WP_DEBUG') && WP_DEBUG) ? '' : '.min';
        $unified_portal_css = FLAVOR_CHAT_IA_PATH . "assets/css/layouts/unified-portal{$sufijo_min}.css";
        if (file_exists($unified_portal_css)) {
            wp_enqueue_style(
                'flavor-unified-portal',
                FLAVOR_CHAT_IA_URL . "assets/css/layouts/unified-portal{$sufijo_min}.css",
                ['flavor-portal'],
                FLAVOR_CHAT_IA_VERSION
            );
        }

        // CSS del dashboard unificado (paneles de prioridad, Gailu, social)
        $unified_dashboard_css = FLAVOR_CHAT_IA_PATH . "assets/css/layouts/unified-dashboard{$sufijo_min}.css";
        if (file_exists($unified_dashboard_css)) {
            wp_enqueue_style(
                'flavor-unified-dashboard',
                FLAVOR_CHAT_IA_URL . "assets/css/layouts/unified-dashboard{$sufijo_min}.css",
                ['flavor-portal'],
                FLAVOR_CHAT_IA_VERSION
            );
        }

        // CSS del módulo específico (si existe)
        $module = $this->current_module ?? '';
        if ($module) {
            // Bypass explícito para módulos con formularios AJAX críticos.
            // Evita depender de auto-detección de rutas de assets en entornos con rutas híbridas.
            if (in_array($module, ['trabajo_digno', 'trabajo-digno'], true)) {
                wp_enqueue_script(
                    'flavor-trabajo-digno-direct',
                    FLAVOR_CHAT_IA_URL . 'includes/modules/trabajo-digno/assets/js/trabajo-digno.js',
                    ['jquery'],
                    FLAVOR_CHAT_IA_VERSION,
                    true
                );
                wp_localize_script('flavor-trabajo-digno-direct', 'flavorTrabajoDigno', [
                    'ajaxurl' => admin_url('admin-ajax.php'),
                    'nonce' => wp_create_nonce('trabajo_digno_nonce'),
                    'i18n' => [
                        'error' => __('Error al procesar la solicitud', 'flavor-chat-ia'),
                        'confirm_postular' => __('¿Confirmas tu postulación?', 'flavor-chat-ia'),
                    ],
                ]);
            } elseif (in_array($module, ['economia_don', 'economia-don'], true)) {
                wp_enqueue_script(
                    'flavor-economia-don-direct',
                    FLAVOR_CHAT_IA_URL . 'includes/modules/economia-don/assets/js/economia-don.js',
                    ['jquery'],
                    FLAVOR_CHAT_IA_VERSION,
                    true
                );
                wp_localize_script('flavor-economia-don-direct', 'edData', [
                    'ajaxUrl' => admin_url('admin-ajax.php'),
                    'nonce' => wp_create_nonce('ed_nonce'),
                    'i18n' => [
                        'confirmSolicitar' => __('¿Deseas solicitar este don?', 'flavor-chat-ia'),
                        'confirmEntrega' => __('¿Confirmas que has entregado este don?', 'flavor-chat-ia'),
                        'gracias' => __('¡Gracias por tu generosidad!', 'flavor-chat-ia'),
                    ],
                ]);
            } elseif (in_array($module, ['biodiversidad_local', 'biodiversidad-local'], true)) {
                wp_enqueue_script(
                    'flavor-biodiversidad-direct',
                    FLAVOR_CHAT_IA_URL . 'includes/modules/biodiversidad-local/assets/js/biodiversidad-local.js',
                    ['jquery'],
                    FLAVOR_CHAT_IA_VERSION,
                    true
                );
                wp_localize_script('flavor-biodiversidad-direct', 'flavorBiodiversidad', [
                    'ajaxurl' => admin_url('admin-ajax.php'),
                    'nonce' => wp_create_nonce('biodiversidad_nonce'),
                    'categorias' => [],
                    'estados' => [],
                    'habitats' => [],
                    'i18n' => [
                        'error' => __('Error al procesar la solicitud', 'flavor-chat-ia'),
                        'success' => __('Operación completada', 'flavor-chat-ia'),
                        'confirm_avistamiento' => __('¿Registrar este avistamiento?', 'flavor-chat-ia'),
                    ],
                ]);
            } elseif (in_array($module, ['recetas'], true)) {
                wp_enqueue_script(
                    'flavor-recetas-direct',
                    FLAVOR_CHAT_IA_URL . 'includes/modules/recetas/assets/js/recetas-frontend.js',
                    ['jquery'],
                    FLAVOR_CHAT_IA_VERSION,
                    true
                );
                wp_localize_script('flavor-recetas-direct', 'flavorRecetasConfig', [
                    'ajaxUrl' => admin_url('admin-ajax.php'),
                    'nonce' => wp_create_nonce('flavor_recetas_nonce'),
                    'strings' => [
                        'error' => __('Error al procesar', 'flavor-chat-ia'),
                    ],
                ]);
            }

            // Convertir module_id (guión_bajo) a slug de directorio (guión)
            $module_dir = str_replace('_', '-', $module);

            // Paths posibles para el CSS del módulo
            $module_css_paths = [
                // Con formato de directorio (guiones)
                FLAVOR_CHAT_IA_PATH . "includes/modules/{$module_dir}/assets/css/{$module_dir}.css",
                FLAVOR_CHAT_IA_PATH . "includes/modules/{$module_dir}/assets/css/{$module_dir}-frontend.css",
                FLAVOR_CHAT_IA_PATH . "includes/modules/{$module_dir}/assets/frontend.css",
                FLAVOR_CHAT_IA_PATH . "includes/modules/{$module_dir}/assets/{$module_dir}-frontend.css",
                FLAVOR_CHAT_IA_PATH . "includes/modules/{$module_dir}/assets/{$module_dir}.css",
                FLAVOR_CHAT_IA_PATH . "includes/modules/{$module_dir}/assets/gc-frontend.css",
                FLAVOR_CHAT_IA_PATH . "includes/modules/{$module_dir}/assets/gc-catalogo.css",
                // Con formato de ID (guiones bajos) como fallback
                FLAVOR_CHAT_IA_PATH . "includes/modules/{$module}/assets/frontend.css",
                FLAVOR_CHAT_IA_PATH . "includes/modules/{$module}/assets/{$module}-frontend.css",
            ];

            foreach ($module_css_paths as $css_path) {
                if (file_exists($css_path)) {
                    $css_handle = 'flavor-module-' . basename($css_path, '.css');
                    $css_url = str_replace(FLAVOR_CHAT_IA_PATH, FLAVOR_CHAT_IA_URL, $css_path);
                    wp_enqueue_style(
                        $css_handle,
                        $css_url,
                        [],
                        FLAVOR_CHAT_IA_VERSION
                    );
                }
            }
        }

        // CSS específico para Biodiversidad Local
        if (in_array($module, ['biodiversidad-local', 'biodiversidad_local'])) {
            wp_enqueue_style(
                'flavor-biodiversidad-local',
                FLAVOR_CHAT_IA_URL . 'includes/modules/biodiversidad-local/assets/css/biodiversidad-local.css',
                [],
                FLAVOR_CHAT_IA_VERSION
            );
        }

        // CSS específico para Mi Red Social
        if (in_array($module, ['mi-red', 'mi_red'])) {
            $mi_red_css = FLAVOR_CHAT_IA_PATH . 'assets/css/modules/mi-red-social.css';
            if (file_exists($mi_red_css)) {
                wp_enqueue_style(
                    'flavor-mi-red-social',
                    FLAVOR_CHAT_IA_URL . 'assets/css/modules/mi-red-social.css',
                    ['flavor-portal'],
                    FLAVOR_CHAT_IA_VERSION
                );
            }

            // JS de Mi Red Social
            $mi_red_js = FLAVOR_CHAT_IA_PATH . 'assets/js/mi-red-social.js';
            if (file_exists($mi_red_js)) {
                wp_enqueue_script(
                    'flavor-mi-red-social',
                    FLAVOR_CHAT_IA_URL . 'assets/js/mi-red-social.js',
                    ['jquery'],
                    FLAVOR_CHAT_IA_VERSION,
                    true
                );

                // Localizar script con variables necesarias
                wp_localize_script('flavor-mi-red-social', 'flavorMiRed', [
                    'ajaxUrl' => admin_url('admin-ajax.php'),
                    'restUrl' => rest_url('flavor-chat/v1/mi-red/'),
                    'nonce' => wp_create_nonce('mi_red_nonce'),
                    'restNonce' => wp_create_nonce('wp_rest'),
                    'userId' => get_current_user_id(),
                    'i18n' => [
                        'cargando' => __('Cargando...', 'flavor-chat-ia'),
                        'error' => __('Error al cargar', 'flavor-chat-ia'),
                        'sinResultados' => __('No hay resultados', 'flavor-chat-ia'),
                        'publicado' => __('Publicado correctamente', 'flavor-chat-ia'),
                        'comentarioEnviado' => __('Comentario enviado', 'flavor-chat-ia'),
                        'meGusta' => __('Me gusta', 'flavor-chat-ia'),
                        'comentar' => __('Comentar', 'flavor-chat-ia'),
                        'compartir' => __('Compartir', 'flavor-chat-ia'),
                        'guardar' => __('Guardar', 'flavor-chat-ia'),
                        'verMas' => __('Ver más', 'flavor-chat-ia'),
                        'cargarMas' => __('Cargar más', 'flavor-chat-ia'),
                    ],
                ]);
            }
        }

        // JS del portal unificado
        $unified_portal_js = FLAVOR_CHAT_IA_PATH . "assets/js/unified-portal{$sufijo_min}.js";
        if (file_exists($unified_portal_js)) {
            wp_enqueue_script(
                'flavor-unified-portal',
                FLAVOR_CHAT_IA_URL . "assets/js/unified-portal{$sufijo_min}.js",
                ['jquery'],
                FLAVOR_CHAT_IA_VERSION,
                true
            );

            wp_localize_script('flavor-unified-portal', 'flavorUnifiedPortal', [
                'ajaxUrl'     => admin_url('admin-ajax.php'),
                'nonce'       => wp_create_nonce('flavor_unified_portal'),
                'userId'      => get_current_user_id(),
                'settingsUrl' => home_url('/mi-portal/configuracion/'),
                'i18n'        => [
                    'loading'     => __('Cargando...', 'flavor-chat-ia'),
                    'error'       => __('Error al cargar datos', 'flavor-chat-ia'),
                    'noModules'   => __('No hay módulos activos', 'flavor-chat-ia'),
                    'layoutSaved' => __('Vista guardada', 'flavor-chat-ia'),
                ],
            ]);
        }

        // CSS adicional inline
        wp_register_style('flavor-dynamic-pages-inline', false);
        wp_enqueue_style('flavor-dynamic-pages-inline');
        wp_add_inline_style('flavor-dynamic-pages-inline', $this->get_inline_styles());

        // JS del módulo específico (si existe)
        if ($module) {
            // Paths posibles para el JS del módulo
            $module_js_paths = [
                // Con formato de directorio (guiones)
                FLAVOR_CHAT_IA_PATH . "includes/modules/{$module_dir}/assets/js/{$module_dir}.js",
                FLAVOR_CHAT_IA_PATH . "includes/modules/{$module_dir}/assets/js/{$module_dir}-frontend.js",
                FLAVOR_CHAT_IA_PATH . "includes/modules/{$module_dir}/assets/js/frontend.js",
                FLAVOR_CHAT_IA_PATH . "includes/modules/{$module_dir}/assets/frontend.js",
                FLAVOR_CHAT_IA_PATH . "includes/modules/{$module_dir}/assets/{$module_dir}-frontend.js",
                FLAVOR_CHAT_IA_PATH . "includes/modules/{$module_dir}/assets/gc-frontend.js",
                // Con formato de ID (guiones bajos) como fallback
                FLAVOR_CHAT_IA_PATH . "includes/modules/{$module}/assets/js/{$module}.js",
                FLAVOR_CHAT_IA_PATH . "includes/modules/{$module}/assets/js/{$module}-frontend.js",
                FLAVOR_CHAT_IA_PATH . "includes/modules/{$module}/assets/frontend.js",
                FLAVOR_CHAT_IA_PATH . "includes/modules/{$module}/assets/{$module}-frontend.js",
            ];

            foreach ($module_js_paths as $js_path) {
                if (file_exists($js_path)) {
                    $js_handle = 'flavor-module-' . basename($js_path, '.js');
                    wp_enqueue_script(
                        $js_handle,
                        str_replace(FLAVOR_CHAT_IA_PATH, FLAVOR_CHAT_IA_URL, $js_path),
                        ['jquery'],
                        FLAVOR_CHAT_IA_VERSION,
                        true // In footer
                    );

                    // Solo grupos-consumo usa este contrato global legacy.
                    if ($module_dir === 'grupos-consumo') {
                        $scripts = wp_scripts();
                        $existing_data = $scripts ? (string) $scripts->get_data($js_handle, 'data') : '';

                        if (strpos($existing_data, 'var gcFrontend =') === false) {
                            wp_localize_script($js_handle, 'gcFrontend', [
                                'ajaxUrl'   => admin_url('admin-ajax.php'),
                                'restUrl'   => rest_url('flavor/v1/grupos-consumo/'),
                                'nonce'     => wp_create_nonce('gc_nonce'),
                                'restNonce' => wp_create_nonce('wp_rest'),
                                'isLoggedIn' => is_user_logged_in(),
                                'loginUrl'  => wp_login_url(home_url($_SERVER['REQUEST_URI'] ?? '')),
                                'i18n'      => [
                                    'agregado'        => __('Producto agregado a la lista', 'flavor-chat-ia'),
                                    'eliminado'       => __('Producto eliminado de la lista', 'flavor-chat-ia'),
                                    'error'           => __('Ha ocurrido un error', 'flavor-chat-ia'),
                                    'confirmarEliminar' => __('¿Eliminar este producto?', 'flavor-chat-ia'),
                                    'pedidoCreado'    => __('Pedido creado correctamente', 'flavor-chat-ia'),
                                    'cargando'        => __('Cargando...', 'flavor-chat-ia'),
                                    'sinProductos'    => __('Tu lista está vacía', 'flavor-chat-ia'),
                                ],
                            ]);
                        }
                    } elseif ($module_dir === 'trabajo-digno') {
                        wp_localize_script($js_handle, 'flavorTrabajoDigno', [
                            'ajaxurl' => admin_url('admin-ajax.php'),
                            'nonce' => wp_create_nonce('trabajo_digno_nonce'),
                            'i18n' => [
                                'error' => __('Error al procesar la solicitud', 'flavor-chat-ia'),
                                'confirm_postular' => __('¿Confirmas tu postulación?', 'flavor-chat-ia'),
                            ],
                        ]);
                    } elseif ($module_dir === 'biodiversidad-local') {
                        wp_localize_script($js_handle, 'flavorBiodiversidad', [
                            'ajaxurl' => admin_url('admin-ajax.php'),
                            'nonce' => wp_create_nonce('biodiversidad_nonce'),
                            'categorias' => [],
                            'estados' => [],
                            'habitats' => [],
                            'i18n' => [
                                'error' => __('Error al procesar la solicitud', 'flavor-chat-ia'),
                                'success' => __('Operación completada', 'flavor-chat-ia'),
                                'confirm_avistamiento' => __('¿Registrar este avistamiento?', 'flavor-chat-ia'),
                            ],
                        ]);
                    } elseif ($module_dir === 'economia-don') {
                        wp_localize_script($js_handle, 'edData', [
                            'ajaxUrl' => admin_url('admin-ajax.php'),
                            'nonce' => wp_create_nonce('ed_nonce'),
                            'i18n' => [
                                'confirmSolicitar' => __('¿Deseas solicitar este don?', 'flavor-chat-ia'),
                                'confirmEntrega' => __('¿Confirmas que has entregado este don?', 'flavor-chat-ia'),
                                'gracias' => __('¡Gracias por tu generosidad!', 'flavor-chat-ia'),
                            ],
                        ]);
                    } elseif ($module_dir === 'recetas') {
                        wp_localize_script($js_handle, 'flavorRecetasConfig', [
                            'ajaxUrl' => admin_url('admin-ajax.php'),
                            'nonce' => wp_create_nonce('flavor_recetas_nonce'),
                            'strings' => [
                                'error' => __('Error al procesar', 'flavor-chat-ia'),
                            ],
                        ]);
                    }
                }
            }
        }
    }

    /**
     * Desactiva shortcode_unautop en el portal dinámico para evitar regex gigantes
     * cuando WordPress intenta procesar cientos de shortcodes registrados.
     *
     * @return void
     */
    private function disable_shortcode_unautop() {
        static $disabled = false;

        if ($disabled) {
            return;
        }

        remove_filter('the_content', 'shortcode_unautop');
        $disabled = true;
    }

    /**
     * Ajusta wp_head para páginas dinámicas y evita metadatos heredados del post base.
     */
    private function prepare_dynamic_wp_head(): void {
        static $prepared = false;

        if ($prepared) {
            return;
        }

        // Evita que WordPress emita canonical, shortlink y discovery del post base
        // cuando en realidad estamos renderizando una ruta dinámica del portal.
        remove_action('wp_head', 'rel_canonical');
        remove_action('wp_head', 'wp_shortlink_wp_head', 10);
        remove_action('wp_head', 'rest_output_link_wp_head', 10);
        remove_action('wp_head', 'wp_oembed_add_discovery_links', 10);
        remove_action('wp_head', 'feed_links', 2);
        remove_action('wp_head', 'feed_links_extra', 3);

        add_action('wp_head', [$this, 'output_dynamic_head_links'], 1);

        $prepared = true;
    }

    /**
     * Emite metadatos básicos correctos para la ruta dinámica actual.
     */
    public function output_dynamic_head_links(): void {
        $canonical_url = $this->get_current_request_url();

        echo '<link rel="canonical" href="' . esc_url($canonical_url) . '" />' . "\n";
        echo '<meta property="og:url" content="' . esc_url($canonical_url) . '" />' . "\n";
    }

    /**
     * Renderiza la página completa
     */
    private function render_page($section = '') {
        // Headers
        status_header(200);
        nocache_headers();
        $this->prepare_dynamic_wp_head();

        ?>
        <!DOCTYPE html>
        <html <?php language_attributes(); ?>>
        <head>
            <meta charset="<?php bloginfo('charset'); ?>">
            <meta name="viewport" content="width=device-width, initial-scale=1.0">
            <title><?php echo esc_html($this->get_page_title()); ?> - <?php bloginfo('name'); ?></title>
            <?php wp_head(); ?>
        </head>
        <body <?php body_class('flavor-app-page flavor-dynamic-page'); ?>>
            <?php wp_body_open(); ?>

            <div class="flavor-app-container">
                <?php
                // Usar el sistema de layouts de Flavor para el header
                if (has_action('flavor_header')) {
                    do_action('flavor_header');
                } else {
                    $this->render_header_fallback();
                }
                ?>

                <div class="flavor-app-layout">
                    <?php $this->render_sidebar(); ?>

                    <main class="flavor-app-main">
                        <?php $this->render_content($section); ?>
                    </main>
                </div>

                <?php
                // Usar el sistema de layouts de Flavor para el footer
                if (has_action('flavor_footer')) {
                    do_action('flavor_footer');
                } else {
                    $this->render_footer_fallback();
                }
                ?>
            </div>

            <?php $this->render_filtered_wp_footer(); ?>
        </body>
        </html>
        <?php
    }

    /**
     * Renderiza wp_footer filtrando widgets flotantes externos que ensucian el portal.
     */
    private function render_filtered_wp_footer(): void {
        ob_start();
        wp_footer();
        $footer_html = ob_get_clean();

        echo $this->strip_external_chat_widget($footer_html); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
    }

    /**
     * Elimina el widget flotante legacy de chat-ia-addon en páginas dinámicas del portal.
     */
    private function strip_external_chat_widget(string $html): string {
        if (strpos($html, '/wp-content/plugins/wp-calendario-experiencias/addons/chat-ia-addon/') === false) {
            return $html;
        }

        $patterns = [
            '/<!-- Widget del chat -->[\s\S]*?(?=<script id="chat-ia-widget-js-extra">)/i',
            '/<script id="chat-ia-widget-js-extra">[\s\S]*?<\/script>\s*/i',
            '/<script[^>]+id="chat-ia-widget-js"[^>]*><\/script>\s*/i',
            '/<link[^>]+id="chat-ia-widget-css"[^>]*>\s*/i',
        ];

        return preg_replace($patterns, '', $html) ?? $html;
    }

    /**
     * Renderiza el header de fallback (cuando no hay sistema de layouts)
     */
    private function render_header_fallback() {
        $site_name = get_bloginfo('name');
        $user = wp_get_current_user();
        $app_config = get_option('flavor_apps_config', []);

        // Obtener la ubicación del menú configurada
        $menu_source = $app_config['web_sections_menu'] ?? '';
        $menu_location = 'primary';
        $menu_id = null;

        // Parsear la fuente del menú
        if ($menu_source && strpos($menu_source, 'location:') === 0) {
            $menu_location = substr($menu_source, strlen('location:'));
        } elseif ($menu_source && strpos($menu_source, 'menu:') === 0) {
            $menu_id = intval(substr($menu_source, strlen('menu:')));
        }
        ?>
        <?php
        // Obtener logo de Flavor Platform (prioridad) o custom logo del tema
        $flavor_logo_url = '';
        if (class_exists('Flavor_Chat_Helpers')) {
            $flavor_logo_url = Flavor_Chat_Helpers::get_site_logo();
        }
        ?>
        <header class="flavor-app-header">
            <div class="fah-left">
                <a href="<?php echo esc_url(home_url('/')); ?>" class="fah-logo">
                    <?php if ($flavor_logo_url): ?>
                        <img src="<?php echo esc_url($flavor_logo_url); ?>" alt="<?php echo esc_attr($site_name); ?>" class="fah-logo-img">
                    <?php elseif (has_custom_logo()): ?>
                        <?php the_custom_logo(); ?>
                    <?php else: ?>
                        <span class="fah-site-name"><?php echo esc_html($site_name); ?></span>
                    <?php endif; ?>
                </a>
            </div>

            <div class="fah-center">
                <nav class="fah-nav fah-nav-wp">
                    <?php
                    // Usar el menú de WordPress configurado
                    $menu_args = [
                        'container' => false,
                        'menu_class' => 'fah-wp-menu',
                        'fallback_cb' => false,
                        'depth' => 2,
                        'walker' => new Flavor_Dynamic_Menu_Walker(),
                    ];

                    if ($menu_id) {
                        $menu_args['menu'] = $menu_id;
                    } else {
                        $menu_args['theme_location'] = $menu_location;
                    }

                    // Intentar mostrar el menú de WordPress
                    if (!wp_nav_menu($menu_args)) {
                        // Fallback: mostrar navegación básica
                        $this->render_fallback_nav();
                    }
                    ?>
                </nav>
            </div>

            <div class="fah-right">
                <?php if (is_user_logged_in()): ?>
                    <div class="fah-user">
                        <?php echo get_avatar($user->ID, 32); ?>
                        <span class="fah-user-name"><?php echo esc_html($user->display_name); ?></span>
                        <a href="<?php echo esc_url(home_url('/' . $this->base_path . '/')); ?>" class="fah-dashboard-link" title="<?php esc_attr_e('Mi Portal', 'flavor-chat-ia'); ?>">
                            <span class="dashicons dashicons-dashboard"></span>
                        </a>
                        <a href="<?php echo esc_url(wp_logout_url(home_url())); ?>" class="fah-logout" title="<?php esc_attr_e('Cerrar sesión', 'flavor-chat-ia'); ?>">
                            <span class="dashicons dashicons-exit"></span>
                        </a>
                    </div>
                <?php else: ?>
                    <a href="<?php echo esc_url(wp_login_url(home_url('/' . $this->base_path . '/'))); ?>" class="fah-login-btn">
                        <?php esc_html_e('Iniciar sesión', 'flavor-chat-ia'); ?>
                    </a>
                <?php endif; ?>
            </div>
        </header>
        <?php
    }

    /**
     * Renderiza navegación de fallback si no hay menú de WordPress
     */
    private function render_fallback_nav() {
        ?>
        <a href="<?php echo esc_url(home_url('/')); ?>" class="fah-nav-item">
            <span class="dashicons dashicons-admin-home"></span>
            <?php esc_html_e('Inicio', 'flavor-chat-ia'); ?>
        </a>
        <a href="<?php echo esc_url(home_url('/' . $this->base_path . '/')); ?>" class="fah-nav-item <?php echo empty($this->current_module) ? 'active' : ''; ?>">
            <span class="dashicons dashicons-dashboard"></span>
            <?php esc_html_e('Mi Portal', 'flavor-chat-ia'); ?>
        </a>
        <?php $this->render_module_nav(); ?>
        <?php
    }

    /**
     * Renderiza la navegación de módulos en el header
     */
    private function render_module_nav() {
        $modules = $this->get_active_modules();
        $shown = 0;
        $max_shown = 5;

        foreach ($modules as $id => $module) {
            if ($shown >= $max_shown) break;

            $is_active = $this->current_module === $id;
            $url = home_url('/' . $this->base_path . '/' . $id . '/');
            $name = $module['name'] ?? ucfirst(str_replace(['-', '_'], ' ', $id));
            ?>
            <a href="<?php echo esc_url($url); ?>" class="fah-nav-item <?php echo $is_active ? 'active' : ''; ?>">
                <?php echo esc_html($name); ?>
            </a>
            <?php
            $shown++;
        }

        if (count($modules) > $max_shown): ?>
            <div class="fah-nav-more">
                <button class="fah-nav-item fah-nav-dropdown-trigger">
                    <?php esc_html_e('Más', 'flavor-chat-ia'); ?>
                    <span class="dashicons dashicons-arrow-down-alt2"></span>
                </button>
                <div class="fah-nav-dropdown">
                    <?php
                    $count = 0;
                    foreach ($modules as $id => $module):
                        $count++;
                        if ($count <= $max_shown) continue;

                        $url = home_url('/' . $this->base_path . '/' . $id . '/');
                        $name = $module['name'] ?? ucfirst(str_replace(['-', '_'], ' ', $id));
                        ?>
                        <a href="<?php echo esc_url($url); ?>"><?php echo esc_html($name); ?></a>
                    <?php endforeach; ?>
                </div>
            </div>
        <?php endif;
    }

    /**
     * Renderiza el sidebar
     */
    private function render_sidebar() {
        if (empty($this->current_module)) {
            return; // No sidebar en dashboard principal
        }

        $module = $this->get_module_instance($this->current_module);
        if (!$module) {
            return;
        }

        $actions = $this->get_composed_module_actions($this->current_module, $module);
        ?>
        <aside class="flavor-app-sidebar">
            <nav class="fas-nav">
                <?php if ($this->should_render_sidebar_root_link($this->current_module, $actions)): ?>
                    <a href="<?php echo esc_url(home_url('/' . $this->base_path . '/' . $this->current_module . '/')); ?>"
                       class="fas-nav-item <?php echo $this->current_action === 'index' ? 'active' : ''; ?>">
                        <span class="dashicons dashicons-list-view"></span>
                        <?php esc_html_e('Ver todos', 'flavor-chat-ia'); ?>
                    </a>
                <?php endif; ?>

                <?php foreach ($actions as $action_id => $action): ?>
                    <?php $action_url = $this->get_sidebar_action_url($action_id, $action); ?>
                    <a href="<?php echo esc_url($action_url); ?>"
                       class="fas-nav-item <?php echo $this->current_action === $action_id ? 'active' : ''; ?>">
                        <span class="dashicons <?php echo esc_attr($action['icon'] ?? 'dashicons-admin-generic'); ?>"></span>
                        <?php echo esc_html($this->normalize_module_ui_label($action['label'] ?? '', $action_id, 'action')); ?>
                    </a>
                <?php endforeach; ?>

                <?php
                // Enlace de administración para gestores/admins
                $admin_url = $this->get_module_admin_url($this->current_module, $module);
                if ($admin_url):
                ?>
                <div class="fas-nav-separator"></div>
                <a href="<?php echo esc_url($admin_url); ?>"
                   class="fas-nav-item fas-nav-item--admin"
                   title="<?php esc_attr_e('Ir a la administración de este módulo', 'flavor-chat-ia'); ?>">
                    <span class="dashicons dashicons-admin-settings"></span>
                    <?php esc_html_e('Administrar', 'flavor-chat-ia'); ?>
                </a>
                <?php endif; ?>
            </nav>
        </aside>
        <?php
    }

    /**
     * Obtiene las acciones del sidebar priorizando tabs reales del modulo.
     *
     * Esto reduce desalineaciones entre el aside y el sistema actual del modulo,
     * manteniendo como complemento algunas acciones legacy utiles.
     *
     * @param string $module_id Slug del modulo.
     * @param object $module Instancia del modulo.
     * @return array
     */
    private function get_composed_module_actions($module_id, $module) {
        $module_slug = str_replace('_', '-', $module_id);
        $tabs = $this->get_module_tabs($module);
        $legacy_priority_actions = $this->get_legacy_priority_actions($module_slug);
        $legacy_actions = $this->get_legacy_module_actions($module_slug);

        $sidebar_actions = [];
        $seen_ids = [];
        $seen_labels = [];

        $add_action = function ($action_id, $action) use (&$sidebar_actions, &$seen_ids, &$seen_labels, $module_slug, $tabs) {
            $action_id = str_replace('_', '-', (string) $action_id);

            $canonical_duplicates = [
                'grupos-consumo' => [
                    'catalogo' => 'productos',
                    'pedidos' => 'mis-pedidos',
                ],
            ];

            if (!empty($canonical_duplicates[$module_slug][$action_id])) {
                $canonical_id = $canonical_duplicates[$module_slug][$action_id];
                if (isset($tabs[$canonical_id])) {
                    return;
                }
            }

            if ($this->should_skip_sidebar_action($action_id, $module_slug, $action)) {
                return;
            }

            $label = $this->normalize_module_ui_label($action['label'] ?? '', $action_id, 'action');
            $normalized_label = sanitize_title(wp_strip_all_tags((string) $label));

            if (isset($seen_ids[$action_id]) || ($normalized_label !== '' && isset($seen_labels[$normalized_label]))) {
                return;
            }

            $action['label'] = $label;
            $action['icon'] = $action['icon'] ?? 'dashicons-admin-generic';

            $sidebar_actions[$action_id] = $action;
            $seen_ids[$action_id] = true;

            if ($normalized_label !== '') {
                $seen_labels[$normalized_label] = true;
            }
        };

        foreach ($tabs as $tab_id => $tab) {
            $add_action($tab_id, $tab);
        }

        foreach ($legacy_priority_actions as $action_id => $action) {
            $add_action($action_id, $action);
        }

        if (empty($tabs)) {
            foreach ($legacy_actions as $action_id => $action) {
                if (!$this->is_priority_sidebar_action($action_id)) {
                    $add_action($action_id, $action);
                }
            }
        }

        return $sidebar_actions;
    }

    /**
     * Obtiene solo las acciones legacy prioritarias para complementar tabs reales.
     *
     * @param string $module_id
     * @return array
     */
    private function get_legacy_priority_actions($module_id) {
        if ($this->module_uses_modern_sidebar_only($module_id)) {
            return [];
        }

        $priority_actions = [];

        foreach ($this->get_legacy_module_actions($module_id) as $action_id => $action) {
            if ($this->is_priority_sidebar_action($action_id)) {
                $priority_actions[$action_id] = $action;
            }
        }

        return $priority_actions;
    }

    /**
     * Módulos ya saneados donde el sidebar debe salir solo del contrato moderno.
     *
     * @param string $module_id
     * @return bool
     */
    private function module_uses_modern_sidebar_only($module_id) {
        static $modern_only_modules = [
            'banco-tiempo',
            'marketplace',
            'chat-interno',
            'chat-grupos',
            'parkings',
            'facturas',
            'advertising',
            'socios',
            'bares',
            'grupos-consumo',
            'reservas',
            'tramites',
            'participacion',
            'espacios-comunes',
            'red-social',
            'comunidades',
            'multimedia',
        ];

        return in_array((string) $module_id, $modern_only_modules, true);
    }

    /**
     * Alias semantico para el sidebar.
     *
     * @param string $module_id
     * @param object $module
     * @return array
     */
    private function get_sidebar_actions($module_id, $module) {
        return $this->get_composed_module_actions($module_id, $module);
    }

    /**
     * Obtiene la URL real de una acción del sidebar.
     *
     * Permite que tabs/acciones modernas definan una URL explícita sin quedar
     * forzadas al patrón /mi-portal/{modulo}/{accion}/.
     *
     * @param string $action_id
     * @param array  $action
     * @return string
     */
    private function get_sidebar_action_url($action_id, array $action): string {
        if (!empty($action['url'])) {
            return (string) $action['url'];
        }

        if (!empty($action['href'])) {
            return (string) $action['href'];
        }

        return home_url('/' . $this->base_path . '/' . $this->current_module . '/' . $action_id . '/');
    }

    /**
     * Obtiene la URL de administración del módulo si el usuario tiene permisos.
     *
     * Solo muestra el enlace a usuarios con capacidad de gestión:
     * - Administradores (manage_options)
     * - Gestores de la comunidad/nodo (flavor_gestor_comunidad)
     * - Gestores específicos del módulo (flavor_gestionar_{modulo})
     *
     * @param string $module_id ID del módulo (con guiones)
     * @param object $module Instancia del módulo
     * @return string|null URL de admin o null si no tiene permisos
     */
    private function get_module_admin_url($module_id, $module): ?string {
        // Normalizar ID del módulo
        $module_id_normalized = str_replace('-', '_', (string) $module_id);
        $module_id_slug = str_replace('_', '-', (string) $module_id);

        // Verificar permisos
        $puede_administrar = false;

        // Admin general
        if (current_user_can('manage_options')) {
            $puede_administrar = true;
        }

        // Gestor de comunidad/nodo
        if (!$puede_administrar && current_user_can('flavor_gestor_comunidad')) {
            $puede_administrar = true;
        }

        // Gestor de grupos (vista reducida)
        if (!$puede_administrar && current_user_can('flavor_gestor_grupos')) {
            $puede_administrar = true;
        }

        // Capability específica del módulo
        if (!$puede_administrar && current_user_can('flavor_gestionar_' . $module_id_normalized)) {
            $puede_administrar = true;
        }

        // Verificar capability del módulo si tiene método
        if (!$puede_administrar && method_exists($module, 'get_admin_capability')) {
            $admin_cap = $module->get_admin_capability();
            if ($admin_cap && current_user_can($admin_cap)) {
                $puede_administrar = true;
            }
        }

        if (!$puede_administrar) {
            return null;
        }

        // Usar el trait centralizado que mapea módulos a sus dashboards
        // Esto garantiza que las URLs sean correctas según el registro canónico
        if (class_exists('Flavor_Module_Admin_Pages_Trait') || trait_exists('Flavor_Module_Admin_Pages_Trait')) {
            $dashboard_url = Flavor_Module_Admin_Pages_Helper::get_module_dashboard_url($module_id_normalized);
            if ($dashboard_url) {
                return $dashboard_url;
            }
        }

        // Fallback: intentar patrones comunes
        // El trait no tiene mapping para este módulo, intentar inferir
        $possible_slugs = [
            $module_id_slug . '-dashboard',           // eventos-dashboard
            'flavor-' . $module_id_slug . '-dashboard', // flavor-radio-dashboard
        ];

        foreach ($possible_slugs as $slug) {
            // Verificar si la página existe comprobando el menú de admin
            global $submenu;
            if (!empty($submenu['flavor-platform'])) {
                foreach ($submenu['flavor-platform'] as $item) {
                    if (isset($item[2]) && $item[2] === $slug) {
                        return admin_url('admin.php?page=' . $slug);
                    }
                }
            }
        }

        // Último fallback: usar el primer patrón más común
        return admin_url('admin.php?page=' . $module_id_slug . '-dashboard');
    }

    /**
     * Determina si debe mostrarse el enlace raíz "Ver todos" del sidebar.
     *
     * Algunos módulos modernos ya usan como primera tab la misma vista raíz y
     * el enlace fijo solo introduce duplicados semánticos en el aside.
     *
     * @param string $module_id
     * @param array  $actions
     * @return bool
     */
    private function should_render_sidebar_root_link($module_id, array $actions): bool {
        static $modules_with_duplicated_root = [
            'banco-tiempo',
            'comunidades',
            'grupos-consumo',
            'chat-grupos',
            'multimedia',
            'red-social',
        ];

        return !in_array((string) $module_id, $modules_with_duplicated_root, true);
    }

    /**
     * Determina si una accion debe priorizarse en el sidebar.
     *
     * @param string $action_id
     * @return bool
     */
    private function is_priority_sidebar_action($action_id) {
        return (bool) preg_match('/^(crear|nuevo|nueva|publicar|registrar|reportar|ofrecer|solicitar|proponer|reservar|alquilar|explorar|buscar)/', (string) $action_id);
    }

    /**
     * Filtra acciones redundantes para el enlace raiz del modulo.
     *
     * @param string $action_id
     * @param string $module_slug
     * @param array  $action
     * @return bool
     */
    private function should_skip_sidebar_action($action_id, $module_slug, array $action) {
        $action_id = str_replace('_', '-', (string) $action_id);

        if (!empty($action['hidden_nav'])) {
            return true;
        }

        if (!empty($action['cap']) && !current_user_can($action['cap'])) {
            return true;
        }

        if (!empty($action['requires_login']) && !is_user_logged_in()) {
            return true;
        }

        if (in_array($action_id, ['index', 'listado', 'todos', 'todo'], true)) {
            return true;
        }

        if ($action_id === $module_slug) {
            return true;
        }

        $label = sanitize_title(wp_strip_all_tags((string) ($action['label'] ?? '')));

        return in_array($label, ['ver-todos', 'todos', 'todo'], true);
    }

    /**
     * Renderiza el contenido principal
     */
    private function render_content($section = '') {
        // Sección especial (mi-cuenta, dashboard)
        if (!empty($section)) {
            $this->render_section($section);
            return;
        }

        // Módulo específico
        if (!empty($this->current_module)) {
            $this->render_module_content();
            return;
        }

        // Dashboard principal
        $this->render_dashboard();
    }

    /**
     * Renderiza una sección especial
     */
    private function render_section($section) {
        switch ($section) {
            case 'mi-cuenta':
                $this->render_user_dashboard();
                break;

            case 'dashboard':
            default:
                $this->render_dashboard();
                break;
        }
    }

    /**
     * Renderiza el dashboard principal con widgets de todos los módulos
     */
    private function render_dashboard() {
        // Usar el shortcode del portal unificado (incluye sistema de layouts)
        if (shortcode_exists('flavor_portal_unificado')) {
            echo do_shortcode('[flavor_portal_unificado]');
        } else {
            ?>
            <div class="flavor-dashboard-header">
                <h1><?php esc_html_e('Mi portal', 'flavor-chat-ia'); ?></h1>
                <p><?php esc_html_e('Resumen de tus espacios, módulos activos y capas de participación.', 'flavor-chat-ia'); ?></p>
            </div>
            <?php
            $this->render_modules_grid();
        }
    }

    /**
     * Renderiza el dashboard de usuario
     */
    private function render_user_dashboard() {
        if (!is_user_logged_in()) {
            $this->render_login_required();
            return;
        }

        // Usar el shortcode de mi cuenta si existe
        if (shortcode_exists('flavor_mi_cuenta')) {
            echo do_shortcode('[flavor_mi_cuenta]');
        } else {
            ?>
            <div class="flavor-dashboard-header">
                <h1><?php esc_html_e('Mi Cuenta', 'flavor-chat-ia'); ?></h1>
            </div>
            <p><?php esc_html_e('Tu espacio personal todavía no está disponible en este entorno.', 'flavor-chat-ia'); ?></p>
            <?php
        }
    }

    /**
     * Renderiza Mi Red Social
     *
     * Usa el sistema de Mi Red Social para renderizar la interfaz unificada de módulos sociales.
     */
    private function render_mi_red_social() {
        // Indicar que estamos en el contexto de dynamic-pages
        // Esto evita que layout.php use get_header()/get_footer() duplicados
        $GLOBALS['flavor_dynamic_pages'] = true;

        // Cargar la clase si no está cargada
        if (!class_exists('Flavor_Mi_Red_Social')) {
            $class_path = FLAVOR_CHAT_IA_PATH . 'includes/frontend/class-mi-red-social.php';
            if (file_exists($class_path)) {
                require_once $class_path;
            }
        }

        // Verificar que la clase existe
        if (!class_exists('Flavor_Mi_Red_Social')) {
            echo '<div class="flavor-error">';
            echo '<p>' . esc_html__('La red social del nodo no está disponible en este momento.', 'flavor-chat-ia') . '</p>';
            echo '</div>';
            return;
        }

        // Obtener la instancia y renderizar
        $mi_red = Flavor_Mi_Red_Social::get_instance();

        // Mapear la acción a la vista
        $vista = $this->current_action;
        if ($vista === 'index' || empty($vista)) {
            $vista = 'feed';
        }

        // Pasar parámetros adicionales (id de perfil, término de búsqueda, etc.)
        $params = [];
        if ($this->current_item_id) {
            $params['id'] = $this->current_item_id;
        }
        if (isset($_GET['q'])) {
            $params['q'] = sanitize_text_field($_GET['q']);
        }
        if (isset($_GET['tipo'])) {
            $params['tipo'] = sanitize_key($_GET['tipo']);
        }

        // Renderizar la vista
        $mi_red->render($vista, $params);
    }

    /**
     * Renderiza el contenido de un módulo
     */
    private function render_module_content() {
        // Verificar si es Mi Red Social
        if (in_array($this->current_module, ['mi-red', 'mi_red'])) {
            $this->render_mi_red_social();
            return;
        }

        // Verificar si es una sección especial del usuario (no requiere módulo)
        $secciones_especiales = $this->get_special_sections();
        if (isset($secciones_especiales[$this->current_module])) {
            $this->render_special_section($this->current_module, $secciones_especiales[$this->current_module]);
            return;
        }

        $module = $this->get_module_instance($this->current_module);

        if (!$module) {
            $this->render_module_not_found();
            return;
        }

        $module_name = $module->name ?? ucfirst(str_replace(['-', '_'], ' ', $this->current_module));
        $module_color = $this->get_module_color($this->current_module);
        $module_icon = $this->get_module_icon($this->current_module);
        $module_role_label = $this->get_module_ecosystem_role_label($module);
        $module_context_label = $this->get_module_context_label($module, $module_name);

        ?>
        <div class="flavor-module-dashboard" style="--module-color: <?php echo esc_attr($module_color); ?>;">

            <!-- Header del módulo -->
            <div class="fmd-header">
                <div class="fmd-header-left">
                    <div class="fmd-breadcrumb">
                        <a href="<?php echo esc_url(home_url('/' . $this->base_path . '/')); ?>">
                            <?php esc_html_e('Mi portal', 'flavor-chat-ia'); ?>
                        </a>
                        <span>›</span>
                        <?php if ($this->current_action && $this->current_action !== 'index'): ?>
                            <a href="<?php echo esc_url(home_url('/' . $this->base_path . '/' . str_replace('_', '-', $this->current_module) . '/')); ?>">
                                <?php echo esc_html($module_name); ?>
                            </a>
                            <span>›</span>
                            <span><?php echo esc_html($this->get_action_label($this->current_action)); ?></span>
                        <?php else: ?>
                            <span><?php echo esc_html($module_name); ?></span>
                        <?php endif; ?>
                    </div>
                    <div class="fmd-title-row">
                        <div class="fmd-icon">
                            <span class="dashicons <?php echo esc_attr($module_icon); ?>"></span>
                        </div>
                        <div>
                            <div class="fmd-eyebrow"><?php echo esc_html($module_role_label); ?></div>
                            <h1><?php echo esc_html($module_name); ?></h1>
                            <p class="fmd-subtitle"><?php echo esc_html($module_context_label); ?></p>
                        </div>
                    </div>
                </div>
                <div class="fmd-header-actions">
                    <?php $this->render_module_quick_actions(); ?>
                </div>
            </div>

            <?php if ($this->current_action === 'index' || empty($this->current_action)): ?>

                <?php
                // Paneles de señales y acciones - información relevante del módulo
                // Se pueden activar/desactivar con filtro: flavor_module_dashboard_panels
                $show_panels = apply_filters('flavor_module_dashboard_panels', [
                    'priority' => true,  // Señales del nodo y próximas acciones
                    'gailu'    => false, // Impacto regenerativo (solo en dashboard principal)
                    'social'   => false, // Pulso social (solo en dashboard principal)
                ], $this->current_module);

                if ($show_panels['priority'] ?? false) {
                    $this->render_priority_panels();
                }
                ?>

                <!-- Estadísticas del módulo -->
                <div class="fmd-stats-grid">
                    <?php $this->render_module_stats(); ?>
                </div>

                <!-- Widgets específicos del módulo -->
                <?php $this->render_module_specific_widgets($module); ?>

                <?php
                // Panel Gailu opcional en vistas de módulo
                if (($show_panels['gailu'] ?? false) && class_exists('Flavor_Unified_Dashboard')) {
                    $dashboard = Flavor_Unified_Dashboard::get_instance();
                    $dashboard->render_gailu_impact_panel(true);
                }
                ?>

                <!-- Tabs de contenido (solo si hay tabs configurados) -->
                <?php $tabs = $this->get_module_tabs($module); ?>
                <?php if (!empty($tabs)): ?>
                <?php
                // Separar tabs base de tabs de integración
                $tabs_base = [];
                $tabs_integracion = [];
                foreach ($tabs as $tab_id => $tab_info) {
                    if (!empty($tab_info['is_integration'])) {
                        $tabs_integracion[$tab_id] = $tab_info;
                    } else {
                        $tabs_base[$tab_id] = $tab_info;
                    }
                }
                $tabs_base_visibles = array_filter($tabs_base, static function($tab_info) {
                    return empty($tab_info['hidden_nav']);
                });
                $tabs_integracion_visibles = array_filter($tabs_integracion, static function($tab_info) {
                    return empty($tab_info['hidden_nav']);
                });
                $tabs_visibles = array_merge($tabs_base_visibles, $tabs_integracion_visibles);
                $tiene_integraciones = !empty($tabs_integracion_visibles);
                ?>
                <div class="fmd-tabs <?php echo $tiene_integraciones ? 'has-integrations' : ''; ?>">
                    <nav class="fmd-tabs-nav">
                        <?php
                        $is_first = true;
                        foreach ($tabs_base_visibles as $tab_id => $tab_info):
                            $badge = $this->get_tab_badge_value($tab_info);
                        ?>
                            <button class="fmd-tab <?php echo $is_first ? 'active' : ''; ?>" data-tab="<?php echo esc_attr($tab_id); ?>">
                                <span class="dashicons <?php echo esc_attr($tab_info['icon']); ?>"></span>
                                <?php echo esc_html($this->normalize_module_ui_label($tab_info['label'] ?? '', $tab_id, 'tab')); ?>
                                <?php if ($badge > 0): ?>
                                    <span class="fmd-tab-badge"><?php echo esc_html($badge); ?></span>
                                <?php endif; ?>
                            </button>
                        <?php
                            $is_first = false;
                        endforeach;

                        // Tabs de integración (módulos de red)
                        if (!empty($tabs_integracion_visibles)):
                        ?>
                        <span class="fmd-tabs-separator" title="<?php esc_attr_e('Módulos de red', 'flavor-chat-ia'); ?>"></span>
                        <?php
                        foreach ($tabs_integracion_visibles as $tab_id => $tab_info):
                            $badge = $this->get_tab_badge_value($tab_info);
                        ?>
                            <button class="fmd-tab fmd-tab--integration" data-tab="<?php echo esc_attr($tab_id); ?>" data-source="<?php echo esc_attr($tab_info['source_module'] ?? ''); ?>">
                                <span class="dashicons <?php echo esc_attr($tab_info['icon']); ?>"></span>
                                <?php echo esc_html($this->normalize_module_ui_label($tab_info['label'] ?? '', $tab_id, 'tab')); ?>
                                <?php if ($badge > 0): ?>
                                    <span class="fmd-tab-badge"><?php echo esc_html($badge); ?></span>
                                <?php endif; ?>
                            </button>
                        <?php
                        endforeach;
                        endif;
                        ?>
                    </nav>

                    <div class="fmd-tab-panels">
                        <?php
                        $is_first = true;
                        foreach ($tabs_visibles as $tab_id => $tab_info):
                        ?>
                            <div class="fmd-tab-panel <?php echo $is_first ? 'active' : ''; ?>" data-panel="<?php echo esc_attr($tab_id); ?>">
                                <?php $this->render_tab_content($tab_id, $tab_info, $module); ?>
                            </div>
                        <?php
                            $is_first = false;
                        endforeach;
                        ?>
                    </div>
                </div>
                <?php endif; ?>

            <?php elseif ($this->is_create_action($this->current_action) && !$this->current_action_uses_module_tab_renderer($module)): ?>
                <div class="fmd-form-container">
                    <?php
                    // Usar CRUD dinámico para formularios
                    if (class_exists('Flavor_Dynamic_CRUD')) {
                        $crud = Flavor_Dynamic_CRUD::get_instance();
                        $form_output = $crud->render_form($this->current_module, 0);
                        echo $form_output;
                    } else {
                        // CRUD no disponible - mostrar mensaje informativo
                        ?>
                        <div class="fmd-no-crud">
                            <span class="dashicons dashicons-info"></span>
                            <p><?php esc_html_e('Esta acción todavía no está lista. Recarga la página o vuelve a intentarlo en unos momentos.', 'flavor-chat-ia'); ?></p>
                        </div>
                        <?php
                    }
                    ?>
                </div>

            <?php elseif ($this->current_action === 'editar' && $this->current_item_id > 0): ?>
                <div class="fmd-form-container">
                    <?php
                    // Usar CRUD dinámico para edición
                    if (class_exists('Flavor_Dynamic_CRUD')) {
                        $crud = Flavor_Dynamic_CRUD::get_instance();
                        echo $crud->render_form($this->current_module, $this->current_item_id);
                    } else {
                        // CRUD no disponible - mostrar mensaje informativo
                        ?>
                        <div class="fmd-no-crud">
                            <span class="dashicons dashicons-info"></span>
                            <p><?php esc_html_e('La edición todavía no está disponible. Recarga la página o vuelve a intentarlo en unos momentos.', 'flavor-chat-ia'); ?></p>
                        </div>
                        <?php
                    }
                    ?>
                </div>

            <?php elseif ($this->current_item_id > 0): ?>
                <?php $this->render_module_item_detail(); ?>

            <?php else: ?>
                <div class="fmd-action-content">
                    <?php $this->render_module_action_content(); ?>
                </div>
            <?php endif; ?>

        </div>

        <?php $this->render_module_dashboard_scripts(); ?>
        <?php
    }

    /**
     * Renderiza paneles de prioridad: señales del nodo y próximas acciones
     *
     * Muestra información relevante y actual de todos los módulos:
     * - Notificaciones urgentes (avisos, incidencias, cuotas)
     * - Acciones próximas (eventos, reservas, votaciones)
     * - Novedades (biblioteca, podcast, foros, red social)
     */
    private function render_priority_panels() {
        if (!is_user_logged_in()) {
            return;
        }

        // Obtener las señales y acciones usando Flavor_Portal_Shortcodes
        $notifications_html = '';
        $actions_html = '';

        if (class_exists('Flavor_Portal_Shortcodes')) {
            $portal_shortcodes = Flavor_Portal_Shortcodes::get_instance();

            if (method_exists($portal_shortcodes, 'render_shared_notifications_bar')) {
                $notifications_html = (string) $portal_shortcodes->render_shared_notifications_bar();
            }

            if (method_exists($portal_shortcodes, 'render_shared_upcoming_actions')) {
                $actions_html = (string) $portal_shortcodes->render_shared_upcoming_actions();
            }
        }

        // Si no hay contenido, no renderizar los paneles
        if (empty($notifications_html) && empty($actions_html)) {
            return;
        }
        ?>
        <div class="fmd-priority-panels">
            <?php if (!empty($notifications_html)): ?>
            <div class="fmd-panel fmd-panel--signals">
                <div class="fmd-panel__header">
                    <span class="fmd-panel__icon">📡</span>
                    <h3 class="fmd-panel__title"><?php esc_html_e('Señales del nodo', 'flavor-chat-ia'); ?></h3>
                </div>
                <?php echo $notifications_html; ?>
            </div>
            <?php endif; ?>

            <?php if (!empty($actions_html)): ?>
            <div class="fmd-panel fmd-panel--actions">
                <div class="fmd-panel__header">
                    <span class="fmd-panel__icon">⚡</span>
                    <h3 class="fmd-panel__title"><?php esc_html_e('Próximas acciones', 'flavor-chat-ia'); ?></h3>
                </div>
                <?php echo $actions_html; ?>
            </div>
            <?php endif; ?>
        </div>
        <?php
    }

    /**
     * Renderiza acciones rápidas del módulo
     */
    private function render_module_quick_actions() {
        $module = $this->get_module_instance($this->current_module);
        $actions = $this->get_composed_module_actions($this->current_module, $module);
        $primary_action = array_key_first($actions);

        if ($primary_action && isset($actions[$primary_action])): ?>
            <a href="<?php echo esc_url($this->get_sidebar_action_url($primary_action, $actions[$primary_action])); ?>"
               class="fmd-primary-btn">
                <span class="dashicons <?php echo esc_attr($actions[$primary_action]['icon'] ?? 'dashicons-plus-alt'); ?>"></span>
                <?php echo esc_html($actions[$primary_action]['label']); ?>
            </a>
        <?php endif;
    }

    /**
     * Obtiene una etiqueta corta de rol ecosistémico para el header del módulo.
     *
     * @param object $module
     * @return string
     */
    private function get_module_ecosystem_role_label($module) {
        if (!is_object($module) || !method_exists($module, 'get_ecosystem_metadata')) {
            return __('Módulo activo', 'flavor-chat-ia');
        }

        $ecosystem = (array) $module->get_ecosystem_metadata();
        if (!empty($ecosystem['display_role_label'])) {
            $display_role = sanitize_key((string) ($ecosystem['display_role'] ?? ''));
            if ($display_role === 'base') {
                return __('Base comunitaria', 'flavor-chat-ia');
            }
            if ($display_role === 'base-standalone') {
                return __('Base local', 'flavor-chat-ia');
            }
        }

        $role = $ecosystem['module_role'] ?? 'vertical';

        switch ($role) {
            case 'base':
                return __('Base comunitaria', 'flavor-chat-ia');
            case 'transversal':
                return __('Capa transversal', 'flavor-chat-ia');
            case 'vertical':
            default:
                return __('Servicio operativo', 'flavor-chat-ia');
        }
    }

    /**
     * Obtiene una descripción corta basada en contexto para el header del módulo.
     *
     * @param object $module
     * @param string $module_name
     * @return string
     */
    private function get_module_context_label($module, $module_name) {
        $fallback = method_exists($module, 'get_description')
            ? (string) $module->get_description()
            : '';

        if (!is_object($module) || !method_exists($module, 'get_dashboard_metadata')) {
            return $fallback ?: $module_name;
        }

        $dashboard = (array) $module->get_dashboard_metadata();
        $contexts = array_values(array_filter((array) ($dashboard['client_contexts'] ?? [])));

        if (empty($contexts)) {
            return $fallback ?: $module_name;
        }

        $context_labels = [
            'comunidad' => __('Comunidad viva', 'flavor-chat-ia'),
            'miembro' => __('Vínculo activo', 'flavor-chat-ia'),
            'membresia' => __('Vínculo activo', 'flavor-chat-ia'),
            'socios' => __('Red de miembros', 'flavor-chat-ia'),
            'colectivos' => __('Coordinación colectiva', 'flavor-chat-ia'),
            'gobernanza' => __('Gobernanza compartida', 'flavor-chat-ia'),
            'participacion' => __('Participación activa', 'flavor-chat-ia'),
            'transparencia' => __('Transparencia abierta', 'flavor-chat-ia'),
            'energia' => __('Energía local', 'flavor-chat-ia'),
            'consumo' => __('Consumo responsable', 'flavor-chat-ia'),
            'suficiencia' => __('Suficiencia cotidiana', 'flavor-chat-ia'),
            'cuidados' => __('Red de cuidados', 'flavor-chat-ia'),
            'solidaridad' => __('Solidaridad cercana', 'flavor-chat-ia'),
            'eventos' => __('Encuentros y agenda', 'flavor-chat-ia'),
            'agenda' => __('Encuentros y agenda', 'flavor-chat-ia'),
            'actividad' => __('Actividad compartida', 'flavor-chat-ia'),
            'impacto' => __('Impacto común', 'flavor-chat-ia'),
            'sostenibilidad' => __('Sostenibilidad local', 'flavor-chat-ia'),
            'aprendizaje' => __('Aprendizaje compartido', 'flavor-chat-ia'),
            'cultura' => __('Cultura viva', 'flavor-chat-ia'),
            'saberes' => __('Saberes compartidos', 'flavor-chat-ia'),
            'cuenta' => __('Espacio personal', 'flavor-chat-ia'),
            'perfil' => __('Espacio personal', 'flavor-chat-ia'),
        ];

        $human_contexts = array_map(static function ($context) use ($context_labels) {
            $context = (string) $context;

            if (isset($context_labels[$context])) {
                return $context_labels[$context];
            }

            $context = str_replace('_', ' ', $context);
            return function_exists('mb_convert_case')
                ? mb_convert_case($context, MB_CASE_TITLE, 'UTF-8')
                : ucwords($context);
        }, array_slice($contexts, 0, 2));

        return sprintf(
            __('Espacio de %s dentro de tu ecosistema activo.', 'flavor-chat-ia'),
            implode(' · ', $human_contexts)
        );
    }

    /**
     * Renderiza estadísticas del módulo
     */
    private function render_module_stats() {
        $stats = $this->get_module_statistics();

        foreach ($stats as $stat): ?>
            <div class="fmd-stat-card">
                <div class="fmd-stat-icon" style="background: <?php echo esc_attr($stat['color'] ?? 'var(--module-color)'); ?>;">
                    <span class="dashicons <?php echo esc_attr($stat['icon']); ?>"></span>
                </div>
                <div class="fmd-stat-content">
                    <span class="fmd-stat-value"><?php echo esc_html($stat['value']); ?></span>
                    <span class="fmd-stat-label"><?php echo esc_html($stat['label']); ?></span>
                </div>
                <?php if (!empty($stat['trend'])): ?>
                    <span class="fmd-stat-trend <?php echo $stat['trend'] > 0 ? 'positive' : 'negative'; ?>">
                        <span class="dashicons dashicons-arrow-<?php echo $stat['trend'] > 0 ? 'up' : 'down'; ?>-alt"></span>
                        <?php echo abs($stat['trend']); ?>%
                    </span>
                <?php endif; ?>
            </div>
        <?php endforeach;
    }

    /**
     * Renderiza widgets específicos del módulo
     *
     * @param object $module Instancia del módulo
     */
    private function render_module_specific_widgets($module) {
        $widgets = $this->get_module_widgets($module);

        if (empty($widgets)) {
            return;
        }

        $module_id = str_replace('_', '-', $this->current_module);
        // Usar la ruta dentro de mi-portal para mantener consistencia con el sistema dinámico
        $base_url = home_url('/' . $this->base_path . '/' . $module_id . '/');
        ?>
        <div class="fmd-module-widgets">
            <div class="fmd-widgets-grid">
                <?php foreach ($widgets as $widget):
                    // Determinar URL del widget
                    $widget_url = $widget['link'] ?? $base_url;
                    $widget_action = $widget['action'] ?? '';
                    if ($widget_action) {
                        // Limpiar barras para evitar doble //
                        $widget_action = trim($widget_action, '/');
                        $widget_url = trailingslashit($base_url . $widget_action);
                    }
                ?>
                    <div class="fmd-widget fmd-widget--<?php echo esc_attr($widget['size'] ?? 'medium'); ?>">
                        <a href="<?php echo esc_url($widget_url); ?>" class="fmd-widget-link">
                            <div class="fmd-widget-header">
                                <span class="dashicons <?php echo esc_attr($widget['icon'] ?? 'dashicons-admin-generic'); ?>"></span>
                                <h4><?php echo esc_html($widget['title']); ?></h4>
                                <span class="fmd-widget-arrow dashicons dashicons-arrow-right-alt2"></span>
                            </div>
                        </a>
                        <div class="fmd-widget-content">
                            <?php
                            if (!empty($widget['callback']) && is_callable($widget['callback'])) {
                                call_user_func($widget['callback'], get_current_user_id());
                            } elseif (!empty($widget['shortcode'])) {
                                echo do_shortcode($widget['shortcode']);
                            } elseif (!empty($widget['html'])) {
                                echo wp_kses_post($widget['html']);
                            }
                            ?>
                        </div>
                        <?php if (!empty($widget['actions'])): ?>
                        <div class="fmd-widget-footer">
                            <?php foreach ($widget['actions'] as $action_key => $action): ?>
                                <?php
                                $widget_action_url = $action['url'] ?? $action['href'] ?? ($base_url . $action_key . '/');
                                ?>
                                <a href="<?php echo esc_url($widget_action_url); ?>" class="fmd-widget-btn">
                                    <?php if (!empty($action['icon'])): ?>
                                        <span class="dashicons <?php echo esc_attr($action['icon']); ?>"></span>
                                    <?php endif; ?>
                                    <?php echo esc_html($action['label']); ?>
                                </a>
                            <?php endforeach; ?>
                        </div>
                        <?php else: ?>
                        <div class="fmd-widget-footer">
                            <a href="<?php echo esc_url($widget_url); ?>" class="fmd-widget-btn fmd-widget-btn--primary">
                                <span class="dashicons dashicons-visibility"></span>
                                <?php esc_html_e('Ver más', 'flavor-chat-ia'); ?>
                            </a>
                        </div>
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
        <?php
    }

    /**
     * Obtiene los widgets específicos del módulo
     *
     * @param object $module Instancia del módulo
     * @return array Widgets del módulo
     */
    private function get_module_widgets($module) {
        // Si el módulo tiene método get_dashboard_widgets(), usarlo
        if ($module && method_exists($module, 'get_dashboard_widgets')) {
            return $module->get_dashboard_widgets();
        }

        // Widgets específicos por módulo
        $module_id = str_replace('_', '-', $this->current_module);

        // ============================================================
        // WIDGETS: Resumen rápido y accesos directos (máximo 2-3)
        // Los widgets muestran información condensada y destacada
        // Los tabs (abajo) ofrecen el contenido completo organizado
        // ============================================================
        $widgets_config = [
            // === GRUPOS DE CONSUMO ===
            // Widget: Ciclo actual (resumen) + Mi Pedido (resumen breve)
            // Tabs: Catálogo completo, Pedidos, Productores, Ciclos
            // NOTA: Usamos callback para renderizar resúmenes breves, no el formulario completo
            'grupos-consumo' => [
                ['title' => __('Ciclo Actual', 'flavor-chat-ia'), 'icon' => 'dashicons-update', 'size' => 'medium', 'callback' => [$this, 'render_gc_ciclo_widget'], 'action' => 'ciclos'],
                ['title' => __('Mi Pedido', 'flavor-chat-ia'), 'icon' => 'dashicons-cart', 'size' => 'medium', 'callback' => [$this, 'render_gc_pedido_widget'], 'action' => 'mi-pedido'],
            ],

            // === EVENTOS ===
            // Widget: Resumen personal | Tabs: Listados completos
            'eventos' => [
                ['title' => __('Próximo Evento', 'flavor-chat-ia'), 'icon' => 'dashicons-calendar', 'size' => 'medium', 'callback' => [$this, 'render_eventos_proximo_widget'], 'action' => 'proximos'],
                ['title' => __('Mis Inscripciones', 'flavor-chat-ia'), 'icon' => 'dashicons-tickets-alt', 'size' => 'medium', 'callback' => [$this, 'render_eventos_inscripciones_widget'], 'action' => 'inscripciones'],
            ],

            // === RESERVAS ===
            // Widget: Próxima reserva | Tabs: Listados y calendario
            'reservas' => [
                ['title' => __('Próxima Reserva', 'flavor-chat-ia'), 'icon' => 'dashicons-calendar-alt', 'size' => 'medium', 'callback' => [$this, 'render_reservas_proxima_widget'], 'action' => 'mis-reservas'],
                ['title' => __('Mis Reservas', 'flavor-chat-ia'), 'icon' => 'dashicons-list-view', 'size' => 'medium', 'callback' => [$this, 'render_reservas_stats_widget'], 'action' => 'listado'],
            ],

            // === ESPACIOS COMUNES ===
            // Widget: Próxima reserva | Tabs: Espacios y calendario
            'espacios-comunes' => [
                ['title' => __('Próxima Reserva', 'flavor-chat-ia'), 'icon' => 'dashicons-calendar-alt', 'size' => 'medium', 'shortcode' => '[espacios_proxima_reserva]', 'action' => 'mis-reservas'],
                ['title' => __('Calendario', 'flavor-chat-ia'), 'icon' => 'dashicons-calendar', 'size' => 'large', 'shortcode' => '[espacios_calendario_mini]', 'action' => 'calendario'],
            ],

            // === HUERTOS URBANOS ===
            // Widget: Estado de mi parcela | Tabs: Listado y mapa
            'huertos-urbanos' => [
                ['title' => __('Mi Parcela', 'flavor-chat-ia'), 'icon' => 'dashicons-admin-site-alt3', 'size' => 'large', 'shortcode' => '[mi_parcela_resumen]', 'action' => 'mi-parcela'],
                ['title' => __('Calendario', 'flavor-chat-ia'), 'icon' => 'dashicons-calendar', 'size' => 'medium', 'shortcode' => '[huertos_calendario]', 'action' => 'calendario'],
            ],

            // === BIBLIOTECA ===
            // Widget: Préstamos activos | Tabs: Catálogo completo
            'biblioteca' => [
                ['title' => __('Préstamos Activos', 'flavor-chat-ia'), 'icon' => 'dashicons-book', 'size' => 'medium', 'callback' => [$this, 'render_biblioteca_prestamos_widget'], 'action' => 'mis-prestamos'],
                ['title' => __('Mi Actividad', 'flavor-chat-ia'), 'icon' => 'dashicons-chart-bar', 'size' => 'medium', 'callback' => [$this, 'render_biblioteca_stats_widget'], 'action' => 'catalogo'],
            ],

            // === MARKETPLACE ===
            // Widget: Estadísticas | Tabs: Listados
            'marketplace' => [
                ['title' => __('Mis Anuncios', 'flavor-chat-ia'), 'icon' => 'dashicons-megaphone', 'size' => 'medium', 'callback' => [$this, 'render_marketplace_anuncios_widget'], 'action' => 'mis-anuncios'],
                ['title' => __('Actividad', 'flavor-chat-ia'), 'icon' => 'dashicons-chart-bar', 'size' => 'medium', 'callback' => [$this, 'render_marketplace_stats_widget'], 'action' => 'listado'],
            ],

            // === INCIDENCIAS ===
            // Widget: Resumen estado | Tabs: Listados y mapa
            'incidencias' => [
                ['title' => __('Mis Reportes', 'flavor-chat-ia'), 'icon' => 'dashicons-flag', 'size' => 'medium', 'callback' => [$this, 'render_incidencias_mis_reportes_widget'], 'action' => 'mis-reportes'],
                ['title' => __('Estado General', 'flavor-chat-ia'), 'icon' => 'dashicons-chart-pie', 'size' => 'medium', 'callback' => [$this, 'render_incidencias_stats_widget'], 'action' => 'listado'],
            ],

            // === BANCO DE TIEMPO ===
            // Widget: Mi saldo y estadísticas | Tabs: Listados
            'banco-tiempo' => [
                ['title' => __('Mi Saldo', 'flavor-chat-ia'), 'icon' => 'dashicons-clock', 'size' => 'medium', 'callback' => [$this, 'render_banco_tiempo_saldo_widget'], 'action' => 'mi-saldo'],
                ['title' => __('Intercambios', 'flavor-chat-ia'), 'icon' => 'dashicons-randomize', 'size' => 'medium', 'callback' => [$this, 'render_banco_tiempo_stats_widget'], 'action' => 'intercambios'],
            ],

            // === BICICLETAS COMPARTIDAS ===
            // Widget: Estado préstamo | Tabs: Disponibilidad y mapa
            'bicicletas-compartidas' => [
                ['title' => __('Mi Préstamo Actual', 'flavor-chat-ia'), 'icon' => 'dashicons-dashboard', 'size' => 'medium', 'shortcode' => '[bicicletas_prestamo_actual]', 'action' => 'mis-prestamos'],
                ['title' => __('Estaciones Cercanas', 'flavor-chat-ia'), 'icon' => 'dashicons-location', 'size' => 'large', 'shortcode' => '[bicicletas_estaciones_cercanas]', 'action' => 'mapa'],
            ],

            // === PARKINGS ===
            // Widget: Estado en tiempo real | Tabs: Reservas y mapa
            'parkings' => [
                ['title' => __('Ocupación Actual', 'flavor-chat-ia'), 'icon' => 'dashicons-chart-bar', 'size' => 'medium', 'shortcode' => '[parking_ocupacion_actual]', 'action' => 'disponibilidad'],
                ['title' => __('Mi Reserva Activa', 'flavor-chat-ia'), 'icon' => 'dashicons-calendar-alt', 'size' => 'medium', 'shortcode' => '[parking_reserva_activa]', 'action' => 'mis-reservas'],
            ],

            // === CARPOOLING ===
            // Widget: Próximo viaje | Tabs: Búsqueda y ofertas
            'carpooling' => [
                ['title' => __('Próximo Viaje', 'flavor-chat-ia'), 'icon' => 'dashicons-car', 'size' => 'large', 'shortcode' => '[carpooling_proximo_viaje]', 'action' => 'mis-viajes'],
                ['title' => __('Búsqueda Rápida', 'flavor-chat-ia'), 'icon' => 'dashicons-search', 'size' => 'medium', 'shortcode' => '[carpooling_busqueda_rapida]', 'action' => 'buscar'],
            ],

            // === RECICLAJE ===
            // Widget: Mi impacto | Tabs: Puntos y guía
            'reciclaje' => [
                ['title' => __('Mi Impacto', 'flavor-chat-ia'), 'icon' => 'dashicons-chart-bar', 'size' => 'large', 'shortcode' => '[reciclaje_mi_impacto]', 'action' => 'mis-puntos'],
                ['title' => __('Punto Más Cercano', 'flavor-chat-ia'), 'icon' => 'dashicons-location', 'size' => 'medium', 'shortcode' => '[reciclaje_punto_cercano]', 'action' => 'puntos-cercanos'],
            ],

            // === COMPOSTAJE ===
            // Widget: Estadísticas | Tabs: Mapa y aportaciones
            'compostaje' => [
                ['title' => __('Mi Balance', 'flavor-chat-ia'), 'icon' => 'dashicons-chart-area', 'size' => 'medium', 'shortcode' => '[compostaje_mi_balance]', 'action' => 'mis-aportaciones'],
                ['title' => __('Compostera Cercana', 'flavor-chat-ia'), 'icon' => 'dashicons-location-alt', 'size' => 'medium', 'shortcode' => '[compostaje_cercana]', 'action' => 'mapa'],
            ],

            // === BARES / COMERCIOS ===
            // Widget: Destacados | Tabs: Listado y mapa
            'bares' => [
                ['title' => __('Destacados', 'flavor-chat-ia'), 'icon' => 'dashicons-star-filled', 'size' => 'large', 'shortcode' => '[bares_destacados limit="4"]', 'action' => 'listado'],
            ],

            // === CURSOS ===
            // Widget: Progreso actual | Tabs: Catálogo
            'cursos' => [
                ['title' => __('Mi Progreso', 'flavor-chat-ia'), 'icon' => 'dashicons-awards', 'size' => 'medium', 'shortcode' => '[cursos_mi_progreso]', 'action' => 'mis-cursos'],
                ['title' => __('Próximos a Comenzar', 'flavor-chat-ia'), 'icon' => 'dashicons-calendar', 'size' => 'large', 'shortcode' => '[cursos_proximos limit="3"]', 'action' => 'catalogo'],
            ],

            // === TALLERES ===
            // Widget: Próximo taller | Tabs: Catálogo
            'talleres' => [
                ['title' => __('Próximo Taller', 'flavor-chat-ia'), 'icon' => 'dashicons-calendar', 'size' => 'medium', 'shortcode' => '[talleres_proximo]', 'action' => 'proximos'],
                ['title' => __('Mis Inscripciones', 'flavor-chat-ia'), 'icon' => 'dashicons-tickets-alt', 'size' => 'medium', 'shortcode' => '[talleres_mis_inscripciones limite="3"]', 'action' => 'inscripciones'],
            ],

            // === COLECTIVOS ===
            // Widget: Mi actividad | Tabs: Listados
            'colectivos' => [
                ['title' => __('Mis Colectivos', 'flavor-chat-ia'), 'icon' => 'dashicons-groups', 'size' => 'medium', 'callback' => [$this, 'render_colectivos_mis_widget'], 'action' => 'mis-colectivos'],
                ['title' => __('Actividad', 'flavor-chat-ia'), 'icon' => 'dashicons-chart-bar', 'size' => 'medium', 'callback' => [$this, 'render_colectivos_stats_widget'], 'action' => 'listado'],
            ],

            // === COMUNIDADES ===
            // Widget: Mi comunidad | Tabs: Directorio y mapa
            'comunidades' => [
                ['title' => __('Mis Espacios', 'flavor-chat-ia'), 'icon' => 'dashicons-admin-multisite', 'size' => 'medium', 'callback' => [$this, 'render_comunidades_mis_widget'], 'action' => 'mis-comunidades'],
                ['title' => __('Actividad', 'flavor-chat-ia'), 'icon' => 'dashicons-chart-bar', 'size' => 'medium', 'callback' => [$this, 'render_comunidades_stats_widget'], 'action' => 'actividad'],
            ],

            // === SOCIOS ===
            // Widget: Estado membresía | Tabs: Directorio
            'socios' => [
                ['title' => __('Mi Membresía', 'flavor-chat-ia'), 'icon' => 'dashicons-id', 'size' => 'medium', 'callback' => [$this, 'render_socios_membresia_widget'], 'action' => 'mi-membresia'],
                ['title' => __('Beneficios', 'flavor-chat-ia'), 'icon' => 'dashicons-awards', 'size' => 'medium', 'callback' => [$this, 'render_socios_beneficios_widget'], 'action' => 'beneficios'],
            ],

            // === FOROS ===
            // Widget: Actividad reciente | Tabs: Discusiones
            'foros' => [
                ['title' => __('Mi Actividad', 'flavor-chat-ia'), 'icon' => 'dashicons-format-chat', 'size' => 'medium', 'callback' => [$this, 'render_foros_actividad_widget'], 'action' => 'mis-hilos'],
                ['title' => __('Estadísticas', 'flavor-chat-ia'), 'icon' => 'dashicons-chart-bar', 'size' => 'medium', 'callback' => [$this, 'render_foros_stats_widget'], 'action' => 'hilos'],
            ],

            // === CHAT GRUPOS ===
            // Widget: Mensajes sin leer | Tabs: Grupos
            'chat-grupos' => [
                ['title' => __('Mensajes', 'flavor-chat-ia'), 'icon' => 'dashicons-email-alt', 'size' => 'medium', 'callback' => [$this, 'render_chat_grupos_mensajes_widget'], 'action' => 'mensajes'],
                ['title' => __('Mis Grupos', 'flavor-chat-ia'), 'icon' => 'dashicons-groups', 'size' => 'medium', 'callback' => [$this, 'render_chat_grupos_stats_widget'], 'action' => 'mis-grupos'],
            ],

            // === CHAT INTERNO ===
            // Widget: Mensajes sin leer | Tabs: Bandeja
            'chat-interno' => [
                ['title' => __('Bandeja', 'flavor-chat-ia'), 'icon' => 'dashicons-email', 'size' => 'medium', 'callback' => [$this, 'render_chat_interno_widget'], 'action' => 'mensajes'],
            ],

            // === RED SOCIAL ===
            // Widget: Notificaciones | Tabs: Feed
            'red-social' => [
                ['title' => __('Mi Perfil', 'flavor-chat-ia'), 'icon' => 'dashicons-admin-users', 'size' => 'medium', 'shortcode' => '[rs_perfil]', 'action' => 'mi-perfil'],
                ['title' => __('Mi Actividad', 'flavor-chat-ia'), 'icon' => 'dashicons-rss', 'size' => 'medium', 'shortcode' => '[rs_mi_actividad]', 'action' => 'mi-actividad'],
            ],

            // === PARTICIPACIÓN ===
            // Widget: Votaciones activas | Tabs: Propuestas
            'participacion' => [
                ['title' => __('Decisiones Activas', 'flavor-chat-ia'), 'icon' => 'dashicons-thumbs-up', 'size' => 'medium', 'shortcode' => '[votaciones_activas]', 'action' => 'votaciones'],
                ['title' => __('Iniciativas en Marcha', 'flavor-chat-ia'), 'icon' => 'dashicons-clipboard', 'size' => 'medium', 'shortcode' => '[mis_propuestas_resumen]', 'action' => 'propuestas'],
            ],

            // === PRESUPUESTOS PARTICIPATIVOS ===
            // Widget: Estado actual | Tabs: Proyectos
            'presupuestos-participativos' => [
                ['title' => __('Estado Actual', 'flavor-chat-ia'), 'icon' => 'dashicons-chart-pie', 'size' => 'large', 'shortcode' => '[presupuesto_estado_actual]', 'action' => 'presupuesto'],
            ],

            // === AVISOS MUNICIPALES ===
            // Widget: Urgentes | Tabs: Listado completo
            'avisos-municipales' => [
                ['title' => __('Avisos Urgentes', 'flavor-chat-ia'), 'icon' => 'dashicons-warning', 'size' => 'large', 'shortcode' => '[avisos_urgentes]', 'action' => 'urgentes'],
            ],

            // === AYUDA VECINAL ===
            // Widget: Solicitudes cercanas | Tabs: Listados
            'ayuda-vecinal' => [
                ['title' => __('Ayuda Cercana', 'flavor-chat-ia'), 'icon' => 'dashicons-location', 'size' => 'large', 'shortcode' => '[ayuda_vecinal_cercana]', 'action' => 'mapa'],
            ],

            // === TRÁMITES ===
            // Widget: Expedientes pendientes | Tabs: Catálogo
            'tramites' => [
                ['title' => __('Mis Trámites', 'flavor-chat-ia'), 'icon' => 'dashicons-clipboard', 'size' => 'medium', 'shortcode' => '[tramites_pendientes]', 'action' => 'mis-tramites'],
                ['title' => __('Más Solicitados', 'flavor-chat-ia'), 'icon' => 'dashicons-chart-bar', 'size' => 'large', 'shortcode' => '[catalogo_tramites limite="4" mostrar_filtros="false" mostrar_buscador="false"]', 'action' => 'catalogo'],
            ],

            // === TRANSPARENCIA ===
            // Widget: Resumen presupuesto | Tabs: Portal completo
            'transparencia' => [
                ['title' => __('Recursos Comunes', 'flavor-chat-ia'), 'icon' => 'dashicons-chart-pie', 'size' => 'large', 'shortcode' => '[transparencia_presupuesto_actual mostrar_grafico="false"]', 'action' => 'presupuestos'],
            ],

            // === FICHAJE EMPLEADOS ===
            // Widget: Panel de fichaje | Tab: Historial
            'fichaje-empleados' => [
                ['title' => __('Fichar', 'flavor-chat-ia'), 'icon' => 'dashicons-clock', 'size' => 'large', 'shortcode' => '[fichaje_boton]', 'action' => 'fichar'],
            ],

            // === MULTIMEDIA ===
            // Widget: Últimas subidas | Tabs: Galerías
            'multimedia' => [
                ['title' => __('Mis Subidas', 'flavor-chat-ia'), 'icon' => 'dashicons-format-gallery', 'size' => 'medium', 'shortcode' => '[multimedia_mis_subidas limite="4"]', 'action' => 'mi-galeria'],
                ['title' => __('Recientes', 'flavor-chat-ia'), 'icon' => 'dashicons-images-alt2', 'size' => 'large', 'shortcode' => '[multimedia_recientes limit="6"]', 'action' => 'galeria'],
            ],

            // === PODCAST ===
            // Widget: Reproductor | Tabs: Episodios
            'podcast' => [
                ['title' => __('Último Episodio', 'flavor-chat-ia'), 'icon' => 'dashicons-microphone', 'size' => 'large', 'shortcode' => '[podcast_ultimo_episodio]', 'action' => 'episodios'],
            ],

            // === RADIO ===
            // Widget: En directo | Tabs: Programación
            'radio' => [
                ['title' => __('En Directo', 'flavor-chat-ia'), 'icon' => 'dashicons-controls-volumeon', 'size' => 'large', 'shortcode' => '[radio_en_directo]', 'action' => 'en-directo'],
            ],

            // === FACTURAS ===
            // Widget: Resumen | Tabs: Historial
            'facturas' => [
                ['title' => __('Pendientes', 'flavor-chat-ia'), 'icon' => 'dashicons-warning', 'size' => 'medium', 'shortcode' => '[facturas_pendientes]', 'action' => 'mis-facturas'],
                ['title' => __('Último Pago', 'flavor-chat-ia'), 'icon' => 'dashicons-yes', 'size' => 'medium', 'shortcode' => '[ultimo_pago]', 'action' => 'historial'],
            ],

            // === TRADING IA ===
            // Widget: Resumen portfolio | Tabs: Dashboard
            'trading-ia' => [
                ['title' => __('Balance', 'flavor-chat-ia'), 'icon' => 'dashicons-chart-line', 'size' => 'large', 'shortcode' => '[trading_balance]', 'action' => 'dashboard'],
            ],

            // === ADVERTISING ===
            // Widget: Rendimiento | Tabs: Campañas
            'advertising' => [
                ['title' => __('Rendimiento', 'flavor-chat-ia'), 'icon' => 'dashicons-chart-bar', 'size' => 'large', 'shortcode' => '[ads_rendimiento]', 'action' => 'dashboard'],
            ],

            // === EMAIL MARKETING ===
            // Widget: Estado suscripción | Tabs: Archivo
            'email-marketing' => [
                ['title' => __('Mi Suscripción', 'flavor-chat-ia'), 'icon' => 'dashicons-email', 'size' => 'large', 'shortcode' => '[newsletter_mi_estado]', 'action' => 'suscripcion'],
            ],

            // === WOOCOMMERCE ===
            // Widget: Últimos pedidos | Tabs: Cuenta
            'woocommerce' => [
                ['title' => __('Último Pedido', 'flavor-chat-ia'), 'icon' => 'dashicons-cart', 'size' => 'medium', 'shortcode' => '[woo_ultimo_pedido]', 'action' => 'mis-pedidos'],
                ['title' => __('Ofertas', 'flavor-chat-ia'), 'icon' => 'dashicons-tag', 'size' => 'large', 'shortcode' => '[products on_sale="true" limit="4"]', 'action' => 'productos'],
            ],

            // === DEX SOLANA ===
            // Widget: Balance | Tabs: Trading
            'dex-solana' => [
                ['title' => __('Balance', 'flavor-chat-ia'), 'icon' => 'dashicons-chart-line', 'size' => 'large', 'shortcode' => '[dex_balance]', 'action' => 'dashboard'],
            ],

            // === EMPRESARIAL ===
            // Widget: Mi empresa | Tabs: Directorio
            'empresarial' => [
                ['title' => __('Mi Empresa', 'flavor-chat-ia'), 'icon' => 'dashicons-building', 'size' => 'large', 'shortcode' => '[empresa_mi_ficha]', 'action' => 'mi-empresa'],
            ],

            // === CLIENTES (CRM) ===
            // Widget: Estadísticas | Tabs: Listado
            'clientes' => [
                ['title' => __('Resumen CRM', 'flavor-chat-ia'), 'icon' => 'dashicons-chart-bar', 'size' => 'large', 'shortcode' => '[crm_resumen]', 'action' => 'estadisticas'],
            ],

            // === HUELLA ECOLÓGICA ===
            'huella-ecologica' => [
                ['title' => __('Mi Huella', 'flavor-chat-ia'), 'icon' => 'dashicons-chart-line', 'size' => 'medium', 'shortcode' => '[flavor_huella_mis_registros]', 'action' => 'mis-registros'],
                ['title' => __('Calculadora', 'flavor-chat-ia'), 'icon' => 'dashicons-calculator', 'size' => 'large', 'shortcode' => '[flavor_huella_calculadora]', 'action' => 'calculadora'],
            ],

            // === SABERES ANCESTRALES ===
            'saberes-ancestrales' => [
                ['title' => __('Mis Saberes', 'flavor-chat-ia'), 'icon' => 'dashicons-share', 'size' => 'medium', 'shortcode' => '[flavor_mis_saberes]', 'action' => 'mis-saberes'],
                ['title' => __('Catálogo', 'flavor-chat-ia'), 'icon' => 'dashicons-book-alt', 'size' => 'large', 'shortcode' => '[flavor_saberes_catalogo limit="6"]', 'action' => 'catalogo'],
            ],

            // === ECONOMÍA DEL DON ===
            'economia-don' => [
                ['title' => __('Mis Dones', 'flavor-chat-ia'), 'icon' => 'dashicons-admin-users', 'size' => 'medium', 'shortcode' => '[flavor_don_mis_dones]', 'action' => 'mis-dones'],
                ['title' => __('Dones Disponibles', 'flavor-chat-ia'), 'icon' => 'dashicons-heart', 'size' => 'large', 'shortcode' => '[flavor_don_listado limit="6"]', 'action' => 'listado'],
            ],

            // === ECONOMÍA DE SUFICIENCIA ===
            'economia-suficiencia' => [
                ['title' => __('Mi Camino', 'flavor-chat-ia'), 'icon' => 'dashicons-chart-line', 'size' => 'large', 'shortcode' => '[flavor_suficiencia_mi_camino]', 'action' => 'mi-camino'],
                ['title' => __('Biblioteca', 'flavor-chat-ia'), 'icon' => 'dashicons-book', 'size' => 'medium', 'shortcode' => '[flavor_suficiencia_biblioteca]', 'action' => 'biblioteca'],
            ],
            'energia-comunitaria' => [
                ['title' => __('Resumen Energético', 'flavor-chat-ia'), 'icon' => 'dashicons-lightbulb', 'size' => 'large', 'shortcode' => '[flavor_energia_dashboard]', 'action' => 'panel'],
                ['title' => __('Infraestructura', 'flavor-chat-ia'), 'icon' => 'dashicons-admin-tools', 'size' => 'medium', 'shortcode' => '[flavor_energia_instalaciones]', 'action' => 'instalaciones'],
                ['title' => __('Comunidad Energética', 'flavor-chat-ia'), 'icon' => 'dashicons-admin-users', 'size' => 'medium', 'shortcode' => '[flavor_energia_participantes]', 'action' => 'participantes'],
                ['title' => __('Cierres y Repartos', 'flavor-chat-ia'), 'icon' => 'dashicons-archive', 'size' => 'medium', 'shortcode' => '[flavor_energia_cierres]', 'action' => 'cierres'],
            ],

            // === TRABAJO DIGNO ===
            'trabajo-digno' => [
                ['title' => __('Mi Perfil', 'flavor-chat-ia'), 'icon' => 'dashicons-id', 'size' => 'medium', 'shortcode' => '[flavor_trabajo_mi_perfil]', 'action' => 'mi-perfil'],
                ['title' => __('Ofertas', 'flavor-chat-ia'), 'icon' => 'dashicons-businessman', 'size' => 'large', 'shortcode' => '[flavor_trabajo_ofertas limit="6"]', 'action' => 'ofertas'],
            ],

            // === CÍRCULOS DE CUIDADOS ===
            'circulos-cuidados' => [
                ['title' => __('Mis Cuidados', 'flavor-chat-ia'), 'icon' => 'dashicons-heart', 'size' => 'medium', 'shortcode' => '[flavor_circulos_mis_cuidados]', 'action' => 'mis-cuidados'],
                ['title' => __('Círculos', 'flavor-chat-ia'), 'icon' => 'dashicons-groups', 'size' => 'large', 'shortcode' => '[flavor_circulos_listado limit="6"]', 'action' => 'listado'],
            ],

            // === JUSTICIA RESTAURATIVA ===
            'justicia-restaurativa' => [
                ['title' => __('Mis Procesos', 'flavor-chat-ia'), 'icon' => 'dashicons-clipboard', 'size' => 'large', 'shortcode' => '[flavor_justicia_mis_procesos]', 'action' => 'mis-procesos'],
                ['title' => __('Mediadores', 'flavor-chat-ia'), 'icon' => 'dashicons-groups', 'size' => 'medium', 'shortcode' => '[flavor_justicia_mediadores]', 'action' => 'mediadores'],
            ],

            // === SELLO CONCIENCIA ===
            'sello-conciencia' => [
                ['title' => __('Mi Badge', 'flavor-chat-ia'), 'icon' => 'dashicons-awards', 'size' => 'medium', 'shortcode' => '[flavor_sello_badge]', 'action' => 'badge'],
                ['title' => __('Premisas', 'flavor-chat-ia'), 'icon' => 'dashicons-lightbulb', 'size' => 'large', 'shortcode' => '[flavor_sello_premisas]', 'action' => 'premisas'],
            ],
        ];

        return $widgets_config[$module_id] ?? [];
    }

    /**
     * Renderiza widget de resumen del ciclo actual (Grupos de Consumo)
     *
     * @param int $user_id ID del usuario
     */
    public function render_gc_ciclo_widget($user_id) {
        // Buscar ciclo activo
        $args = [
            'post_type'      => 'gc_ciclo',
            'post_status'    => 'publish',
            'posts_per_page' => 1,
            'meta_query'     => [
                [
                    'key'   => '_gc_estado',
                    'value' => 'abierto',
                ],
            ],
        ];
        $query = new WP_Query($args);

        if ($query->have_posts()) {
            $ciclo = $query->posts[0];
            $fecha_cierre = get_post_meta($ciclo->ID, '_gc_fecha_cierre', true);
            $fecha_entrega = get_post_meta($ciclo->ID, '_gc_fecha_entrega', true);
            ?>
            <div class="fmd-widget-summary fmd-widget-summary--success">
                <div class="fmd-summary-status">
                    <span class="fmd-status-dot fmd-status-dot--success"></span>
                    <strong><?php echo esc_html($ciclo->post_title); ?></strong>
                </div>
                <?php if ($fecha_cierre): ?>
                <p class="fmd-summary-detail">
                    <span class="dashicons dashicons-clock"></span>
                    <?php printf(__('Cierra: %s', 'flavor-chat-ia'), esc_html(date_i18n('j M, H:i', strtotime($fecha_cierre)))); ?>
                </p>
                <?php endif; ?>
                <?php if ($fecha_entrega): ?>
                <p class="fmd-summary-detail">
                    <span class="dashicons dashicons-location"></span>
                    <?php printf(__('Entrega: %s', 'flavor-chat-ia'), esc_html(date_i18n('j M', strtotime($fecha_entrega)))); ?>
                </p>
                <?php endif; ?>
            </div>
            <?php
        } else {
            ?>
            <p class="fmd-widget-empty"><?php _e('No hay ningún ciclo abierto actualmente.', 'flavor-chat-ia'); ?></p>
            <?php
        }
        wp_reset_postdata();
    }

    /**
     * Renderiza widget de resumen del pedido (Grupos de Consumo)
     *
     * @param int $user_id ID del usuario
     */
    public function render_gc_pedido_widget($user_id) {
        if (!$user_id) {
            echo '<p class="fmd-widget-empty">' . __('Inicia sesión para ver tu pedido.', 'flavor-chat-ia') . '</p>';
            return;
        }

        global $wpdb;
        $tabla_lista = $wpdb->prefix . 'flavor_gc_lista_compra';

        // Verificar si tabla existe
        $tabla_existe = $wpdb->get_var($wpdb->prepare('SHOW TABLES LIKE %s', $tabla_lista)) === $tabla_lista;

        if (!$tabla_existe) {
            echo '<p class="fmd-widget-empty">' . __('No hay productos en tu cesta.', 'flavor-chat-ia') . '</p>';
            return;
        }

        // Contar items y calcular total
        $stats = $wpdb->get_row($wpdb->prepare(
            "SELECT COUNT(*) as total_items, COALESCE(SUM(cantidad * precio_unitario), 0) as total_importe
             FROM $tabla_lista
             WHERE usuario_id = %d",
            $user_id
        ));

        $total_items = (int) ($stats->total_items ?? 0);
        $total_importe = (float) ($stats->total_importe ?? 0);

        if ($total_items === 0) {
            ?>
            <div class="fmd-widget-summary fmd-widget-summary--empty">
                <span class="dashicons dashicons-cart"></span>
                <p><?php _e('Tu cesta está vacía', 'flavor-chat-ia'); ?></p>
                <a href="<?php echo esc_url(home_url('/mi-portal/grupos-consumo/productos/')); ?>" class="fmd-link-action">
                    <?php _e('Explorar productos', 'flavor-chat-ia'); ?>
                </a>
            </div>
            <?php
        } else {
            ?>
            <div class="fmd-widget-summary">
                <div class="fmd-summary-stat fmd-summary-stat--primary">
                    <span class="fmd-stat-number"><?php echo esc_html($total_items); ?></span>
                    <span class="fmd-stat-label"><?php echo _n('producto', 'productos', $total_items, 'flavor-chat-ia'); ?></span>
                </div>
                <div class="fmd-summary-stat fmd-summary-stat--highlight">
                    <span class="fmd-stat-number"><?php echo number_format($total_importe, 2, ',', '.'); ?> €</span>
                    <span class="fmd-stat-label"><?php _e('total', 'flavor-chat-ia'); ?></span>
                </div>
            </div>
            <?php
        }
    }

    // =========================================================================
    // CALLBACKS DE WIDGETS: Renderizado de resúmenes breves para cada módulo
    // =========================================================================

    /**
     * Widget: Próximo evento inscrito (Eventos)
     */
    public function render_eventos_proximo_widget($user_id) {
        if (!$user_id) {
            echo '<p class="fmd-widget-empty">' . __('Inicia sesión para ver tus eventos.', 'flavor-chat-ia') . '</p>';
            return;
        }

        global $wpdb;
        $tabla = $wpdb->prefix . 'flavor_eventos_inscripciones';

        // Verificar tabla
        if ($wpdb->get_var($wpdb->prepare('SHOW TABLES LIKE %s', $tabla)) !== $tabla) {
            echo '<p class="fmd-widget-empty">' . __('No tienes eventos próximos.', 'flavor-chat-ia') . '</p>';
            return;
        }

        // Obtener próxima inscripción
        $inscripcion = $wpdb->get_row($wpdb->prepare(
            "SELECT i.*, p.post_title
             FROM $tabla i
             JOIN {$wpdb->posts} p ON i.evento_id = p.ID
             WHERE i.usuario_id = %d
             AND p.post_status = 'publish'
             ORDER BY i.fecha_inscripcion DESC
             LIMIT 1",
            $user_id
        ));

        if ($inscripcion) {
            $fecha_evento = get_post_meta($inscripcion->evento_id, '_evento_fecha', true);
            ?>
            <div class="fmd-widget-summary fmd-widget-summary--info">
                <div class="fmd-summary-status">
                    <span class="fmd-status-dot fmd-status-dot--info"></span>
                    <strong><?php echo esc_html($inscripcion->post_title); ?></strong>
                </div>
                <?php if ($fecha_evento): ?>
                <p class="fmd-summary-detail">
                    <span class="dashicons dashicons-calendar"></span>
                    <?php echo esc_html(date_i18n('j M, H:i', strtotime($fecha_evento))); ?>
                </p>
                <?php endif; ?>
            </div>
            <?php
        } else {
            ?>
            <div class="fmd-widget-summary fmd-widget-summary--empty">
                <span class="dashicons dashicons-calendar-alt"></span>
                <p><?php _e('Sin eventos próximos', 'flavor-chat-ia'); ?></p>
            </div>
            <?php
        }
    }

    /**
     * Widget: Contador de inscripciones (Eventos)
     */
    public function render_eventos_inscripciones_widget($user_id) {
        if (!$user_id) {
            echo '<p class="fmd-widget-empty">' . __('Inicia sesión para ver tus inscripciones.', 'flavor-chat-ia') . '</p>';
            return;
        }

        global $wpdb;
        $tabla = $wpdb->prefix . 'flavor_eventos_inscripciones';

        if ($wpdb->get_var($wpdb->prepare('SHOW TABLES LIKE %s', $tabla)) !== $tabla) {
            $total = 0;
        } else {
            $total = (int) $wpdb->get_var($wpdb->prepare(
                "SELECT COUNT(*) FROM $tabla WHERE usuario_id = %d",
                $user_id
            ));
        }

        ?>
        <div class="fmd-widget-summary">
            <div class="fmd-summary-stat fmd-summary-stat--primary">
                <span class="fmd-stat-number"><?php echo esc_html($total); ?></span>
                <span class="fmd-stat-label"><?php echo _n('inscripción', 'inscripciones', $total, 'flavor-chat-ia'); ?></span>
            </div>
        </div>
        <?php
    }

    /**
     * Widget: Próxima reserva (Reservas)
     */
    public function render_reservas_proxima_widget($user_id) {
        if (!$user_id) {
            echo '<p class="fmd-widget-empty">' . __('Inicia sesión para ver tus reservas.', 'flavor-chat-ia') . '</p>';
            return;
        }

        global $wpdb;
        $tabla = $wpdb->prefix . 'flavor_reservas';

        if ($wpdb->get_var($wpdb->prepare('SHOW TABLES LIKE %s', $tabla)) !== $tabla) {
            echo '<p class="fmd-widget-empty">' . __('No tienes reservas próximas.', 'flavor-chat-ia') . '</p>';
            return;
        }

        $reserva = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM $tabla
             WHERE usuario_id = %d AND fecha_inicio >= NOW() AND estado = 'confirmada'
             ORDER BY fecha_inicio ASC
             LIMIT 1",
            $user_id
        ));

        if ($reserva) {
            ?>
            <div class="fmd-widget-summary fmd-widget-summary--success">
                <div class="fmd-summary-status">
                    <span class="fmd-status-dot fmd-status-dot--success"></span>
                    <strong><?php echo esc_html($reserva->recurso_nombre ?? __('Reserva confirmada', 'flavor-chat-ia')); ?></strong>
                </div>
                <p class="fmd-summary-detail">
                    <span class="dashicons dashicons-calendar"></span>
                    <?php echo esc_html(date_i18n('j M, H:i', strtotime($reserva->fecha_inicio))); ?>
                </p>
            </div>
            <?php
        } else {
            ?>
            <div class="fmd-widget-summary fmd-widget-summary--empty">
                <span class="dashicons dashicons-calendar-alt"></span>
                <p><?php _e('Sin reservas próximas', 'flavor-chat-ia'); ?></p>
            </div>
            <?php
        }
    }

    /**
     * Widget: Estadísticas de reservas (Reservas)
     */
    public function render_reservas_stats_widget($user_id) {
        if (!$user_id) {
            echo '<p class="fmd-widget-empty">' . __('Inicia sesión para ver tus reservas.', 'flavor-chat-ia') . '</p>';
            return;
        }

        global $wpdb;
        $tabla = $wpdb->prefix . 'flavor_reservas';

        if ($wpdb->get_var($wpdb->prepare('SHOW TABLES LIKE %s', $tabla)) !== $tabla) {
            $total = 0;
            $pendientes = 0;
        } else {
            $total = (int) $wpdb->get_var($wpdb->prepare(
                "SELECT COUNT(*) FROM $tabla WHERE usuario_id = %d",
                $user_id
            ));
            $pendientes = (int) $wpdb->get_var($wpdb->prepare(
                "SELECT COUNT(*) FROM $tabla WHERE usuario_id = %d AND fecha_inicio >= NOW()",
                $user_id
            ));
        }

        ?>
        <div class="fmd-widget-summary">
            <div class="fmd-summary-stat fmd-summary-stat--primary">
                <span class="fmd-stat-number"><?php echo esc_html($pendientes); ?></span>
                <span class="fmd-stat-label"><?php _e('próximas', 'flavor-chat-ia'); ?></span>
            </div>
            <div class="fmd-summary-stat">
                <span class="fmd-stat-number"><?php echo esc_html($total); ?></span>
                <span class="fmd-stat-label"><?php _e('total', 'flavor-chat-ia'); ?></span>
            </div>
        </div>
        <?php
    }

    /**
     * Widget: Préstamos activos (Biblioteca)
     */
    public function render_biblioteca_prestamos_widget($user_id) {
        if (!$user_id) {
            echo '<p class="fmd-widget-empty">' . __('Inicia sesión para ver tus préstamos.', 'flavor-chat-ia') . '</p>';
            return;
        }

        global $wpdb;
        $tabla = $wpdb->prefix . 'flavor_biblioteca_prestamos';

        if ($wpdb->get_var($wpdb->prepare('SHOW TABLES LIKE %s', $tabla)) !== $tabla) {
            echo '<p class="fmd-widget-empty">' . __('No tienes préstamos activos.', 'flavor-chat-ia') . '</p>';
            return;
        }

        $prestamos_activos = (int) $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM $tabla WHERE usuario_id = %d AND estado = 'activo'",
            $user_id
        ));

        $proximo_vencimiento = $wpdb->get_var($wpdb->prepare(
            "SELECT MIN(fecha_devolucion) FROM $tabla WHERE usuario_id = %d AND estado = 'activo'",
            $user_id
        ));

        ?>
        <div class="fmd-widget-summary">
            <div class="fmd-summary-stat fmd-summary-stat--primary">
                <span class="fmd-stat-number"><?php echo esc_html($prestamos_activos); ?></span>
                <span class="fmd-stat-label"><?php echo _n('préstamo activo', 'préstamos activos', $prestamos_activos, 'flavor-chat-ia'); ?></span>
            </div>
            <?php if ($proximo_vencimiento): ?>
            <p class="fmd-summary-detail">
                <span class="dashicons dashicons-clock"></span>
                <?php printf(__('Próx. devolución: %s', 'flavor-chat-ia'), esc_html(date_i18n('j M', strtotime($proximo_vencimiento)))); ?>
            </p>
            <?php endif; ?>
        </div>
        <?php
    }

    /**
     * Widget: Estadísticas biblioteca (Biblioteca)
     */
    public function render_biblioteca_stats_widget($user_id) {
        if (!$user_id) {
            echo '<p class="fmd-widget-empty">' . __('Inicia sesión para ver tu actividad.', 'flavor-chat-ia') . '</p>';
            return;
        }

        global $wpdb;
        $tabla = $wpdb->prefix . 'flavor_biblioteca_prestamos';

        if ($wpdb->get_var($wpdb->prepare('SHOW TABLES LIKE %s', $tabla)) !== $tabla) {
            $total = 0;
        } else {
            $total = (int) $wpdb->get_var($wpdb->prepare(
                "SELECT COUNT(*) FROM $tabla WHERE usuario_id = %d",
                $user_id
            ));
        }

        ?>
        <div class="fmd-widget-summary">
            <div class="fmd-summary-stat fmd-summary-stat--primary">
                <span class="fmd-stat-number"><?php echo esc_html($total); ?></span>
                <span class="fmd-stat-label"><?php echo _n('libro leído', 'libros leídos', $total, 'flavor-chat-ia'); ?></span>
            </div>
        </div>
        <?php
    }

    /**
     * Widget: Mis anuncios (Marketplace)
     */
    public function render_marketplace_anuncios_widget($user_id) {
        if (!$user_id) {
            echo '<p class="fmd-widget-empty">' . __('Inicia sesión para ver tus anuncios.', 'flavor-chat-ia') . '</p>';
            return;
        }

        $args = [
            'post_type'      => 'marketplace_item',
            'post_status'    => 'publish',
            'author'         => $user_id,
            'posts_per_page' => -1,
        ];
        $query = new WP_Query($args);
        $total = $query->found_posts;
        wp_reset_postdata();

        ?>
        <div class="fmd-widget-summary">
            <div class="fmd-summary-stat fmd-summary-stat--primary">
                <span class="fmd-stat-number"><?php echo esc_html($total); ?></span>
                <span class="fmd-stat-label"><?php echo _n('anuncio activo', 'anuncios activos', $total, 'flavor-chat-ia'); ?></span>
            </div>
        </div>
        <?php
    }

    /**
     * Widget: Estadísticas marketplace (Marketplace)
     */
    public function render_marketplace_stats_widget($user_id) {
        global $wpdb;

        // Contar total de anuncios en el marketplace
        $total_anuncios = (int) $wpdb->get_var(
            "SELECT COUNT(*) FROM {$wpdb->posts} WHERE post_type = 'marketplace_item' AND post_status = 'publish'"
        );

        ?>
        <div class="fmd-widget-summary">
            <div class="fmd-summary-stat fmd-summary-stat--primary">
                <span class="fmd-stat-number"><?php echo esc_html($total_anuncios); ?></span>
                <span class="fmd-stat-label"><?php _e('anuncios disponibles', 'flavor-chat-ia'); ?></span>
            </div>
        </div>
        <?php
    }

    /**
     * Widget: Mis reportes (Incidencias)
     */
    public function render_incidencias_mis_reportes_widget($user_id) {
        if (!$user_id) {
            echo '<p class="fmd-widget-empty">' . __('Inicia sesión para ver tus reportes.', 'flavor-chat-ia') . '</p>';
            return;
        }

        global $wpdb;
        $tabla = $wpdb->prefix . 'flavor_incidencias';

        if ($wpdb->get_var($wpdb->prepare('SHOW TABLES LIKE %s', $tabla)) !== $tabla) {
            $total = 0;
            $pendientes = 0;
        } else {
            $total = (int) $wpdb->get_var($wpdb->prepare(
                "SELECT COUNT(*) FROM $tabla WHERE usuario_id = %d",
                $user_id
            ));
            $pendientes = (int) $wpdb->get_var($wpdb->prepare(
                "SELECT COUNT(*) FROM $tabla WHERE usuario_id = %d AND estado IN ('pendiente', 'en_proceso')",
                $user_id
            ));
        }

        ?>
        <div class="fmd-widget-summary">
            <div class="fmd-summary-stat fmd-summary-stat--primary">
                <span class="fmd-stat-number"><?php echo esc_html($total); ?></span>
                <span class="fmd-stat-label"><?php echo _n('reporte', 'reportes', $total, 'flavor-chat-ia'); ?></span>
            </div>
            <?php if ($pendientes > 0): ?>
            <p class="fmd-summary-detail fmd-summary-detail--warning">
                <span class="dashicons dashicons-warning"></span>
                <?php printf(_n('%d pendiente', '%d pendientes', $pendientes, 'flavor-chat-ia'), $pendientes); ?>
            </p>
            <?php endif; ?>
        </div>
        <?php
    }

    /**
     * Widget: Estado general incidencias (Incidencias)
     */
    public function render_incidencias_stats_widget($user_id) {
        global $wpdb;
        $tabla = $wpdb->prefix . 'flavor_incidencias';

        if ($wpdb->get_var($wpdb->prepare('SHOW TABLES LIKE %s', $tabla)) !== $tabla) {
            $total = 0;
            $resueltas = 0;
        } else {
            $total = (int) $wpdb->get_var("SELECT COUNT(*) FROM $tabla");
            $resueltas = (int) $wpdb->get_var("SELECT COUNT(*) FROM $tabla WHERE estado = 'resuelta'");
        }

        $porcentaje = $total > 0 ? round(($resueltas / $total) * 100) : 0;

        ?>
        <div class="fmd-widget-summary">
            <div class="fmd-summary-stat fmd-summary-stat--primary">
                <span class="fmd-stat-number"><?php echo esc_html($porcentaje); ?>%</span>
                <span class="fmd-stat-label"><?php _e('resueltas', 'flavor-chat-ia'); ?></span>
            </div>
            <p class="fmd-summary-detail">
                <?php printf(__('%d de %d incidencias', 'flavor-chat-ia'), $resueltas, $total); ?>
            </p>
        </div>
        <?php
    }

    /**
     * Widget: Mi saldo (Banco de Tiempo)
     */
    public function render_banco_tiempo_saldo_widget($user_id) {
        if (!$user_id) {
            echo '<p class="fmd-widget-empty">' . __('Inicia sesión para ver tu saldo.', 'flavor-chat-ia') . '</p>';
            return;
        }

        global $wpdb;
        $tabla = $wpdb->prefix . 'flavor_banco_tiempo_saldos';

        if ($wpdb->get_var($wpdb->prepare('SHOW TABLES LIKE %s', $tabla)) !== $tabla) {
            $saldo = 0;
        } else {
            $saldo = (int) $wpdb->get_var($wpdb->prepare(
                "SELECT saldo FROM $tabla WHERE usuario_id = %d",
                $user_id
            ));
        }

        $horas = floor($saldo / 60);
        $minutos = $saldo % 60;

        ?>
        <div class="fmd-widget-summary">
            <div class="fmd-summary-stat fmd-summary-stat--primary">
                <span class="fmd-stat-number"><?php echo esc_html($horas); ?>h <?php echo esc_html($minutos); ?>m</span>
                <span class="fmd-stat-label"><?php _e('disponibles', 'flavor-chat-ia'); ?></span>
            </div>
        </div>
        <?php
    }

    /**
     * Widget: Estadísticas intercambios (Banco de Tiempo)
     */
    public function render_banco_tiempo_stats_widget($user_id) {
        if (!$user_id) {
            echo '<p class="fmd-widget-empty">' . __('Inicia sesión para ver tus intercambios.', 'flavor-chat-ia') . '</p>';
            return;
        }

        global $wpdb;
        $tabla = $wpdb->prefix . 'flavor_banco_tiempo_intercambios';

        if ($wpdb->get_var($wpdb->prepare('SHOW TABLES LIKE %s', $tabla)) !== $tabla) {
            $total = 0;
        } else {
            $total = (int) $wpdb->get_var($wpdb->prepare(
                "SELECT COUNT(*) FROM $tabla WHERE usuario_ofertante = %d OR usuario_solicitante = %d",
                $user_id, $user_id
            ));
        }

        ?>
        <div class="fmd-widget-summary">
            <div class="fmd-summary-stat fmd-summary-stat--primary">
                <span class="fmd-stat-number"><?php echo esc_html($total); ?></span>
                <span class="fmd-stat-label"><?php echo _n('intercambio', 'intercambios', $total, 'flavor-chat-ia'); ?></span>
            </div>
        </div>
        <?php
    }

    /**
     * Widget: Mis colectivos (Colectivos)
     */
    public function render_colectivos_mis_widget($user_id) {
        if (!$user_id) {
            echo '<p class="fmd-widget-empty">' . __('Inicia sesión para ver tus colectivos.', 'flavor-chat-ia') . '</p>';
            return;
        }

        global $wpdb;
        $tabla = $wpdb->prefix . 'flavor_colectivos_miembros';

        if ($wpdb->get_var($wpdb->prepare('SHOW TABLES LIKE %s', $tabla)) !== $tabla) {
            $total = 0;
        } else {
            $total = (int) $wpdb->get_var($wpdb->prepare(
                "SELECT COUNT(*) FROM $tabla WHERE usuario_id = %d AND estado = 'activo'",
                $user_id
            ));
        }

        ?>
        <div class="fmd-widget-summary">
            <div class="fmd-summary-stat fmd-summary-stat--primary">
                <span class="fmd-stat-number"><?php echo esc_html($total); ?></span>
                <span class="fmd-stat-label"><?php echo _n('colectivo', 'colectivos', $total, 'flavor-chat-ia'); ?></span>
            </div>
        </div>
        <?php
    }

    /**
     * Widget: Estadísticas colectivos (Colectivos)
     */
    public function render_colectivos_stats_widget($user_id) {
        global $wpdb;

        $total_colectivos = (int) $wpdb->get_var(
            "SELECT COUNT(*) FROM {$wpdb->posts} WHERE post_type = 'colectivo' AND post_status = 'publish'"
        );

        ?>
        <div class="fmd-widget-summary">
            <div class="fmd-summary-stat fmd-summary-stat--primary">
                <span class="fmd-stat-number"><?php echo esc_html($total_colectivos); ?></span>
                <span class="fmd-stat-label"><?php _e('colectivos activos', 'flavor-chat-ia'); ?></span>
            </div>
        </div>
        <?php
    }

    /**
     * Widget: Mis comunidades (Comunidades)
     */
    public function render_comunidades_mis_widget($user_id) {
        if (!$user_id) {
            echo '<p class="fmd-widget-empty">' . __('Inicia sesión para ver tus comunidades.', 'flavor-chat-ia') . '</p>';
            return;
        }

        global $wpdb;
        $tabla = $wpdb->prefix . 'flavor_comunidades_miembros';

        if ($wpdb->get_var($wpdb->prepare('SHOW TABLES LIKE %s', $tabla)) !== $tabla) {
            $total = 0;
        } else {
            $total = (int) $wpdb->get_var($wpdb->prepare(
                "SELECT COUNT(*) FROM $tabla WHERE usuario_id = %d AND estado = 'activo'",
                $user_id
            ));
        }

        ?>
        <div class="fmd-widget-summary">
            <div class="fmd-summary-stat fmd-summary-stat--primary">
                <span class="fmd-stat-number"><?php echo esc_html($total); ?></span>
                <span class="fmd-stat-label"><?php echo _n('comunidad', 'comunidades', $total, 'flavor-chat-ia'); ?></span>
            </div>
        </div>
        <?php
    }

    /**
     * Widget: Estadísticas comunidades (Comunidades)
     */
    public function render_comunidades_stats_widget($user_id) {
        global $wpdb;

        $total_comunidades = (int) $wpdb->get_var(
            "SELECT COUNT(*) FROM {$wpdb->posts} WHERE post_type = 'comunidad' AND post_status = 'publish'"
        );

        ?>
        <div class="fmd-widget-summary">
            <div class="fmd-summary-stat fmd-summary-stat--primary">
                <span class="fmd-stat-number"><?php echo esc_html($total_comunidades); ?></span>
                <span class="fmd-stat-label"><?php _e('comunidades activas', 'flavor-chat-ia'); ?></span>
            </div>
        </div>
        <?php
    }

    /**
     * Widget: Mi membresía (Socios)
     */
    public function render_socios_membresia_widget($user_id) {
        if (!$user_id) {
            echo '<p class="fmd-widget-empty">' . __('Inicia sesión para ver tu membresía.', 'flavor-chat-ia') . '</p>';
            return;
        }

        global $wpdb;
        $tabla = $wpdb->prefix . 'flavor_socios';

        if ($wpdb->get_var($wpdb->prepare('SHOW TABLES LIKE %s', $tabla)) !== $tabla) {
            $socio = null;
        } else {
            $socio = $wpdb->get_row($wpdb->prepare(
                "SELECT * FROM $tabla WHERE usuario_id = %d",
                $user_id
            ));
        }

        if ($socio) {
            $estado_class = $socio->estado === 'activo' ? 'success' : 'warning';
            ?>
            <div class="fmd-widget-summary fmd-widget-summary--<?php echo esc_attr($estado_class); ?>">
                <div class="fmd-summary-status">
                    <span class="fmd-status-dot fmd-status-dot--<?php echo esc_attr($estado_class); ?>"></span>
                    <strong><?php echo esc_html(ucfirst($socio->estado)); ?></strong>
                </div>
                <?php if (!empty($socio->numero_socio)): ?>
                <p class="fmd-summary-detail">
                    <?php printf(__('Nº Socio: %s', 'flavor-chat-ia'), esc_html($socio->numero_socio)); ?>
                </p>
                <?php endif; ?>
            </div>
            <?php
        } else {
            ?>
            <div class="fmd-widget-summary fmd-widget-summary--empty">
                <span class="dashicons dashicons-id"></span>
                <p><?php _e('No eres miembro todavía', 'flavor-chat-ia'); ?></p>
            </div>
            <?php
        }
    }

    /**
     * Widget: Beneficios socios (Socios)
     */
    public function render_socios_beneficios_widget($user_id) {
        // Mostrar resumen de beneficios disponibles
        ?>
        <div class="fmd-widget-summary">
            <p class="fmd-summary-detail">
                <span class="dashicons dashicons-yes-alt"></span>
                <?php _e('Descuentos exclusivos', 'flavor-chat-ia'); ?>
            </p>
            <p class="fmd-summary-detail">
                <span class="dashicons dashicons-yes-alt"></span>
                <?php _e('Acceso preferente', 'flavor-chat-ia'); ?>
            </p>
        </div>
        <?php
    }

    /**
     * Widget: Mi actividad foros (Foros)
     */
    public function render_foros_actividad_widget($user_id) {
        if (!$user_id) {
            echo '<p class="fmd-widget-empty">' . __('Inicia sesión para ver tu actividad.', 'flavor-chat-ia') . '</p>';
            return;
        }

        global $wpdb;
        $tabla_hilos = $wpdb->prefix . 'flavor_foros_hilos';
        $tabla_respuestas = $wpdb->prefix . 'flavor_foros_respuestas';

        $mis_hilos = 0;
        $mis_respuestas = 0;

        if ($wpdb->get_var($wpdb->prepare('SHOW TABLES LIKE %s', $tabla_hilos)) === $tabla_hilos) {
            $mis_hilos = (int) $wpdb->get_var($wpdb->prepare(
                "SELECT COUNT(*) FROM $tabla_hilos WHERE autor_id = %d",
                $user_id
            ));
        }

        if ($wpdb->get_var($wpdb->prepare('SHOW TABLES LIKE %s', $tabla_respuestas)) === $tabla_respuestas) {
            $mis_respuestas = (int) $wpdb->get_var($wpdb->prepare(
                "SELECT COUNT(*) FROM $tabla_respuestas WHERE autor_id = %d",
                $user_id
            ));
        }

        ?>
        <div class="fmd-widget-summary">
            <div class="fmd-summary-stat fmd-summary-stat--primary">
                <span class="fmd-stat-number"><?php echo esc_html($mis_hilos); ?></span>
                <span class="fmd-stat-label"><?php echo _n('hilo', 'hilos', $mis_hilos, 'flavor-chat-ia'); ?></span>
            </div>
            <div class="fmd-summary-stat">
                <span class="fmd-stat-number"><?php echo esc_html($mis_respuestas); ?></span>
                <span class="fmd-stat-label"><?php echo _n('respuesta', 'respuestas', $mis_respuestas, 'flavor-chat-ia'); ?></span>
            </div>
        </div>
        <?php
    }

    /**
     * Widget: Estadísticas foros (Foros)
     */
    public function render_foros_stats_widget($user_id) {
        global $wpdb;
        $tabla_hilos = $wpdb->prefix . 'flavor_foros_hilos';

        if ($wpdb->get_var($wpdb->prepare('SHOW TABLES LIKE %s', $tabla_hilos)) !== $tabla_hilos) {
            $total = 0;
        } else {
            $total = (int) $wpdb->get_var("SELECT COUNT(*) FROM $tabla_hilos");
        }

        ?>
        <div class="fmd-widget-summary">
            <div class="fmd-summary-stat fmd-summary-stat--primary">
                <span class="fmd-stat-number"><?php echo esc_html($total); ?></span>
                <span class="fmd-stat-label"><?php _e('hilos activos', 'flavor-chat-ia'); ?></span>
            </div>
        </div>
        <?php
    }

    /**
     * Widget: Mensajes sin leer (Chat Grupos)
     */
    public function render_chat_grupos_mensajes_widget($user_id) {
        if (!$user_id) {
            echo '<p class="fmd-widget-empty">' . __('Inicia sesión para ver tus mensajes.', 'flavor-chat-ia') . '</p>';
            return;
        }

        global $wpdb;
        $tabla = $wpdb->prefix . 'flavor_chat_grupos_mensajes';

        if ($wpdb->get_var($wpdb->prepare('SHOW TABLES LIKE %s', $tabla)) !== $tabla) {
            $sin_leer = 0;
        } else {
            // Contar mensajes no leídos en grupos donde es miembro
            $sin_leer = (int) $wpdb->get_var($wpdb->prepare(
                "SELECT COUNT(*) FROM $tabla m
                 JOIN {$wpdb->prefix}flavor_chat_grupos_miembros gm ON m.grupo_id = gm.grupo_id
                 WHERE gm.usuario_id = %d AND m.autor_id != %d AND m.leido = 0",
                $user_id, $user_id
            ));
        }

        if ($sin_leer > 0) {
            ?>
            <div class="fmd-widget-summary fmd-widget-summary--info">
                <div class="fmd-summary-stat fmd-summary-stat--primary">
                    <span class="fmd-stat-number"><?php echo esc_html($sin_leer); ?></span>
                    <span class="fmd-stat-label"><?php echo _n('mensaje nuevo', 'mensajes nuevos', $sin_leer, 'flavor-chat-ia'); ?></span>
                </div>
            </div>
            <?php
        } else {
            ?>
            <div class="fmd-widget-summary fmd-widget-summary--empty">
                <span class="dashicons dashicons-email"></span>
                <p><?php _e('Sin mensajes nuevos', 'flavor-chat-ia'); ?></p>
            </div>
            <?php
        }
    }

    /**
     * Widget: Mis grupos (Chat Grupos)
     */
    public function render_chat_grupos_stats_widget($user_id) {
        if (!$user_id) {
            echo '<p class="fmd-widget-empty">' . __('Inicia sesión para ver tus grupos.', 'flavor-chat-ia') . '</p>';
            return;
        }

        global $wpdb;
        $tabla = $wpdb->prefix . 'flavor_chat_grupos_miembros';

        if ($wpdb->get_var($wpdb->prepare('SHOW TABLES LIKE %s', $tabla)) !== $tabla) {
            $total = 0;
        } else {
            $total = (int) $wpdb->get_var($wpdb->prepare(
                "SELECT COUNT(*) FROM $tabla WHERE usuario_id = %d",
                $user_id
            ));
        }

        ?>
        <div class="fmd-widget-summary">
            <div class="fmd-summary-stat fmd-summary-stat--primary">
                <span class="fmd-stat-number"><?php echo esc_html($total); ?></span>
                <span class="fmd-stat-label"><?php echo _n('grupo', 'grupos', $total, 'flavor-chat-ia'); ?></span>
            </div>
        </div>
        <?php
    }

    /**
     * Widget: Bandeja de entrada (Chat Interno)
     */
    public function render_chat_interno_widget($user_id) {
        if (!$user_id) {
            echo '<p class="fmd-widget-empty">' . __('Inicia sesión para ver tus mensajes.', 'flavor-chat-ia') . '</p>';
            return;
        }

        global $wpdb;
        $tabla = $wpdb->prefix . 'flavor_chat_mensajes';

        if ($wpdb->get_var($wpdb->prepare('SHOW TABLES LIKE %s', $tabla)) !== $tabla) {
            $sin_leer = 0;
            $total = 0;
        } else {
            $sin_leer = (int) $wpdb->get_var($wpdb->prepare(
                "SELECT COUNT(*) FROM $tabla WHERE destinatario_id = %d AND leido = 0",
                $user_id
            ));
            $total = (int) $wpdb->get_var($wpdb->prepare(
                "SELECT COUNT(*) FROM $tabla WHERE destinatario_id = %d OR remitente_id = %d",
                $user_id, $user_id
            ));
        }

        if ($sin_leer > 0) {
            ?>
            <div class="fmd-widget-summary fmd-widget-summary--info">
                <div class="fmd-summary-stat fmd-summary-stat--primary">
                    <span class="fmd-stat-number"><?php echo esc_html($sin_leer); ?></span>
                    <span class="fmd-stat-label"><?php echo _n('mensaje nuevo', 'mensajes nuevos', $sin_leer, 'flavor-chat-ia'); ?></span>
                </div>
            </div>
            <?php
        } else {
            ?>
            <div class="fmd-widget-summary fmd-widget-summary--empty">
                <span class="dashicons dashicons-email"></span>
                <p><?php _e('Sin mensajes nuevos', 'flavor-chat-ia'); ?></p>
            </div>
            <?php
        }
    }

    // =========================================================================
    // FIN CALLBACKS DE WIDGETS
    // =========================================================================

    /**
     * Obtiene el label legible de una acción
     *
     * @param string $action Slug de la acción
     * @return string Label legible
     */
    private function get_action_label($action) {
        if ($action === 'anuncios' && $this->current_module === 'marketplace') {
            return __('Anuncios', 'flavor-chat-ia');
        }

        $labels = [
            'dashboard'      => __('Resumen', 'flavor-chat-ia'),
            'panel'          => __('Resumen', 'flavor-chat-ia'),
            'comunidades'    => __('Red energética', 'flavor-chat-ia'),
            'mis-comunidades'=> __('Mis espacios', 'flavor-chat-ia'),
            'actividad'      => __('Pulso comunitario', 'flavor-chat-ia'),
            'eventos'        => __('Encuentros', 'flavor-chat-ia'),
            'anuncios'       => __('Avisos', 'flavor-chat-ia'),
            'recursos'       => __('Recursos compartidos', 'flavor-chat-ia'),
            'propuestas'     => __('Iniciativas', 'flavor-chat-ia'),
            'votaciones'     => __('Decisiones', 'flavor-chat-ia'),
            'resultados'     => __('Acuerdos', 'flavor-chat-ia'),
            'debates'        => __('Conversaciones', 'flavor-chat-ia'),
            'reuniones'      => __('Encuentros', 'flavor-chat-ia'),
            'portal'         => __('Visión general', 'flavor-chat-ia'),
            'presupuesto'    => __('Recursos comunes', 'flavor-chat-ia'),
            'actas'          => __('Memoria y actas', 'flavor-chat-ia'),
            'contratos'      => __('Compromisos y contratos', 'flavor-chat-ia'),
            'indicadores'    => __('Indicadores compartidos', 'flavor-chat-ia'),
            'instalaciones'  => __('Infraestructura', 'flavor-chat-ia'),
            'participantes'  => __('Comunidad energética', 'flavor-chat-ia'),
            'registrar-lectura' => __('Registrar producción', 'flavor-chat-ia'),
            'mantenimiento'  => __('Cuidados técnicos', 'flavor-chat-ia'),
            'balance'        => __('Balance energético', 'flavor-chat-ia'),
            'cierres'        => __('Cierres y repartos', 'flavor-chat-ia'),
            'mi-pedido'      => __('Pedido actual', 'flavor-chat-ia'),
            'mi-cesta'       => __('Mi Cesta', 'flavor-chat-ia'),
            'productos'      => __('Productos', 'flavor-chat-ia'),
            'productores'    => __('Productores', 'flavor-chat-ia'),
            'servicios'      => __('Servicios', 'flavor-chat-ia'),
            'buscar'         => __('Buscar servicios', 'flavor-chat-ia'),
            'mi-saldo'       => __('Mi Saldo', 'flavor-chat-ia'),
            'intercambios'   => __('Intercambios', 'flavor-chat-ia'),
            'reputacion'     => __('Mi Reputación', 'flavor-chat-ia'),
            'ofrecer'        => __('Ofrecer', 'flavor-chat-ia'),
            'mensajes'       => __('Mensajes', 'flavor-chat-ia'),
            'ciclos'         => __('Ciclos', 'flavor-chat-ia'),
            'mapa'           => __('Mapa', 'flavor-chat-ia'),
            'calendario'     => __('Calendario', 'flavor-chat-ia'),
            'listado'        => __('Explorar', 'flavor-chat-ia'),
            'crear'          => __('Nueva acción', 'flavor-chat-ia'),
            'nuevo'          => __('Nuevo elemento', 'flavor-chat-ia'),
            'nueva'          => __('Nueva acción', 'flavor-chat-ia'),
            'editar'         => __('Editar', 'flavor-chat-ia'),
            'ver'            => __('Detalle', 'flavor-chat-ia'),
            'mis-reservas'   => __('Mis Reservas', 'flavor-chat-ia'),
            'mis-pedidos'    => __('Historial', 'flavor-chat-ia'),
            'mis-reportes'   => __('Mis Reportes', 'flavor-chat-ia'),
            'inscripciones'  => __('Inscripciones', 'flavor-chat-ia'),
            'suscripciones'  => __('Suscripciones', 'flavor-chat-ia'),
            'estadisticas'   => __('Estadísticas', 'flavor-chat-ia'),
            'configuracion'  => __('Configuración', 'flavor-chat-ia'),
        ];

        // Buscar en el array o convertir slug a texto legible
        if (isset($labels[$action])) {
            return $labels[$action];
        }

        // Fallback: convertir slug a texto (mi-pedido → Mi Pedido)
        return ucwords(str_replace('-', ' ', $action));
    }

    /**
     * Normaliza labels visibles de tabs y acciones sin romper labels específicos.
     *
     * @param string $label
     * @param string $id
     * @param string $context
     * @return string
     */
    private function normalize_module_ui_label($label, $id, $context = 'action') {
        $label = trim((string) $label);
        $id = str_replace('_', '-', (string) $id);

        if ($label === '') {
            return $this->get_action_label($id);
        }

        $normalized = sanitize_title($label);

        if ($normalized === 'anuncios' && $this->current_module === 'marketplace') {
            return __('Anuncios', 'flavor-chat-ia');
        }

        $replacements = [
            'dashboard' => __('Resumen', 'flavor-chat-ia'),
            'panel' => __('Resumen', 'flavor-chat-ia'),
            'listado' => __('Explorar', 'flavor-chat-ia'),
            'todos' => __('Explorar', 'flavor-chat-ia'),
            'configuracion' => __('Ajustes', 'flavor-chat-ia'),
            'configuración' => __('Ajustes', 'flavor-chat-ia'),
            'mis-comunidades' => __('Mis espacios', 'flavor-chat-ia'),
            'actividad-reciente' => __('Pulso comunitario', 'flavor-chat-ia'),
            'eventos' => __('Encuentros', 'flavor-chat-ia'),
            'anuncios' => __('Avisos', 'flavor-chat-ia'),
            'recursos' => __('Recursos compartidos', 'flavor-chat-ia'),
            'propuestas' => __('Iniciativas', 'flavor-chat-ia'),
            'votaciones' => __('Decisiones', 'flavor-chat-ia'),
            'resultados' => __('Acuerdos', 'flavor-chat-ia'),
            'debates' => __('Conversaciones', 'flavor-chat-ia'),
            'reuniones' => __('Encuentros', 'flavor-chat-ia'),
            'portal' => __('Visión general', 'flavor-chat-ia'),
            'presupuesto' => __('Recursos comunes', 'flavor-chat-ia'),
            'actas' => __('Memoria y actas', 'flavor-chat-ia'),
            'contratos' => __('Compromisos y contratos', 'flavor-chat-ia'),
            'indicadores' => __('Indicadores compartidos', 'flavor-chat-ia'),
            'instalaciones' => __('Infraestructura', 'flavor-chat-ia'),
            'participantes' => __('Comunidad energética', 'flavor-chat-ia'),
            'registrar-lectura' => __('Registrar producción', 'flavor-chat-ia'),
            'mantenimiento' => __('Cuidados técnicos', 'flavor-chat-ia'),
            'balance' => __('Balance energético', 'flavor-chat-ia'),
            'cierres' => __('Cierres y repartos', 'flavor-chat-ia'),
        ];

        if (isset($replacements[$normalized])) {
            return $replacements[$normalized];
        }

        if (in_array($normalized, ['nuevo', 'nueva', 'crear'], true)) {
            return __('Nueva acción', 'flavor-chat-ia');
        }

        return $label;
    }

    /**
     * Obtiene los tabs del módulo
     *
     * Prioridad de fuentes:
     * 1. get_dashboard_tabs() - Método legacy del módulo
     * 2. get_renderer_config()['tabs'] - Nuevo sistema de configuración
     * 3. $tabs_config hardcodeado - Fallback legacy
     *
     * @param object $module Instancia del módulo
     * @return array Tabs del módulo
     */
    private function get_module_tabs($module) {
        $tabs_modulo = [];
        $tabs_renderer = [];
        $module_id = str_replace('_', '-', $this->current_module);

        // PRIORIDAD 1: Método get_dashboard_tabs() del módulo
        if ($module && method_exists($module, 'get_dashboard_tabs')) {
            $tabs_modulo = $module->get_dashboard_tabs() ?: [];
        }

        // PRIORIDAD 2: Nuevo sistema - get_renderer_config()['tabs']
        if ($module && method_exists($module, 'get_renderer_config')) {
            $config = $module->get_renderer_config();
            if (!empty($config['tabs'])) {
                $tabs_renderer = $this->convert_renderer_tabs_to_legacy($config['tabs'], $config);
            }
        }

        if ($this->module_uses_modern_sidebar_only($module_id) && !empty($tabs_renderer)) {
            return $tabs_renderer;
        }

        if (!empty($tabs_modulo) || !empty($tabs_renderer)) {
            return $this->merge_module_tabs($tabs_modulo, $tabs_renderer);
        }

        // PRIORIDAD 3: Tabs específicos por módulo (fallback legacy)

        // ============================================================
        // TABS POR MÓDULO: Complementan los widgets con vistas completas
        // Widgets = Resumen rápido | Tabs = Contenido completo organizado
        // ============================================================
        $tabs_config = [
            // === GRUPOS DE CONSUMO ===
            // Widgets: Mi Pedido (resumen), Productos (destacados)
            // Tabs: Navegación completa del ciclo de consumo
            'grupos-consumo' => [
                'productos'   => ['label' => __('Explorar catálogo', 'flavor-chat-ia'), 'icon' => 'dashicons-products'],
                'pedidos'     => ['label' => __('Mi consumo', 'flavor-chat-ia'), 'icon' => 'dashicons-cart'],
                'productores' => ['label' => __('Red de productores', 'flavor-chat-ia'), 'icon' => 'dashicons-groups'],
                'ciclos'      => ['label' => __('Ciclo actual', 'flavor-chat-ia'), 'icon' => 'dashicons-calendar-alt'],
                // Integraciones
                'foro'        => ['label' => __('Foro', 'flavor-chat-ia'), 'icon' => 'dashicons-admin-comments', 'is_integration' => true, 'source_module' => 'foros'],
                'recetas'     => ['label' => __('Recetas', 'flavor-chat-ia'), 'icon' => 'dashicons-carrot', 'is_integration' => true, 'source_module' => 'recetas'],
                'trueques'    => ['label' => __('Trueques', 'flavor-chat-ia'), 'icon' => 'dashicons-randomize', 'is_integration' => true, 'content' => '[gc_trueques]'],
            ],

            // === EVENTOS ===
            // Widgets: Inscripciones (resumen), Próximos (destacados)
            // Tab listado usa Archive Renderer via template genérico
            'eventos' => [
                'listado'       => ['label' => __('Todos', 'flavor-chat-ia'), 'icon' => 'dashicons-list-view', 'content' => 'template:_archive.php'],
                'proximos'      => ['label' => __('Próximos', 'flavor-chat-ia'), 'icon' => 'dashicons-calendar'],
                'inscripciones' => ['label' => __('Mis Inscripciones', 'flavor-chat-ia'), 'icon' => 'dashicons-tickets-alt'],
                'calendario'    => ['label' => __('Calendario', 'flavor-chat-ia'), 'icon' => 'dashicons-calendar-alt'],
                'mapa'          => ['label' => __('Mapa', 'flavor-chat-ia'), 'icon' => 'dashicons-location'],
                // Integraciones
                'multimedia'    => ['label' => __('Fotos', 'flavor-chat-ia'), 'icon' => 'dashicons-format-gallery', 'is_integration' => true, 'source_module' => 'multimedia'],
                'comentarios'   => ['label' => __('Comentarios', 'flavor-chat-ia'), 'icon' => 'dashicons-admin-comments', 'is_integration' => true, 'source_module' => 'foros'],
            ],

            // === RESERVAS ===
            'reservas' => [
                'recursos'       => ['label' => __('Espacios disponibles', 'flavor-chat-ia'), 'icon' => 'dashicons-admin-home'],
                'mis-reservas'   => ['label' => __('Mis Reservas', 'flavor-chat-ia'), 'icon' => 'dashicons-calendar-alt'],
                'calendario'     => ['label' => __('Calendario', 'flavor-chat-ia'), 'icon' => 'dashicons-calendar'],
                'nueva-reserva'  => ['label' => __('Reservar', 'flavor-chat-ia'), 'icon' => 'dashicons-plus-alt'],
            ],

            // === ESPACIOS COMUNES ===
            'espacios-comunes' => [
                'espacios'     => ['label' => __('Espacios', 'flavor-chat-ia'), 'icon' => 'dashicons-admin-home'],
                'mis-reservas' => ['label' => __('Mis Reservas', 'flavor-chat-ia'), 'icon' => 'dashicons-calendar-alt'],
                'calendario'   => ['label' => __('Calendario', 'flavor-chat-ia'), 'icon' => 'dashicons-calendar'],
                // Integraciones
                'normas'       => ['label' => __('Normas', 'flavor-chat-ia'), 'icon' => 'dashicons-clipboard', 'is_integration' => true, 'content' => '[ec_normas_uso]'],
                'foro'         => ['label' => __('Foro', 'flavor-chat-ia'), 'icon' => 'dashicons-admin-comments', 'is_integration' => true, 'source_module' => 'foros'],
            ],

            // === HUERTOS URBANOS ===
            'huertos-urbanos' => [
                'listado'  => ['label' => __('Huertos', 'flavor-chat-ia'), 'icon' => 'dashicons-admin-site-alt3'],
                'mi-parcela' => ['label' => __('Mi Parcela', 'flavor-chat-ia'), 'icon' => 'dashicons-admin-home'],
                'mapa'     => ['label' => __('Mapa', 'flavor-chat-ia'), 'icon' => 'dashicons-location'],
                'calendario' => ['label' => __('Calendario', 'flavor-chat-ia'), 'icon' => 'dashicons-calendar'],
                // Integraciones
                'banco-semillas' => ['label' => __('Banco Semillas', 'flavor-chat-ia'), 'icon' => 'dashicons-archive', 'is_integration' => true, 'content' => '[huertos_banco_semillas]'],
                'foro'           => ['label' => __('Foro', 'flavor-chat-ia'), 'icon' => 'dashicons-admin-comments', 'is_integration' => true, 'source_module' => 'foros'],
                'recetas'        => ['label' => __('Recetas', 'flavor-chat-ia'), 'icon' => 'dashicons-carrot', 'is_integration' => true, 'source_module' => 'recetas'],
            ],

            // === BIBLIOTECA ===
            'biblioteca' => [
                'catalogo'      => ['label' => __('Catálogo', 'flavor-chat-ia'), 'icon' => 'dashicons-book-alt'],
                'mis-prestamos' => ['label' => __('Mis Préstamos', 'flavor-chat-ia'), 'icon' => 'dashicons-book'],
                'novedades'     => ['label' => __('Novedades', 'flavor-chat-ia'), 'icon' => 'dashicons-star-filled'],
                // Integraciones
                'resenas'       => ['label' => __('Reseñas', 'flavor-chat-ia'), 'icon' => 'dashicons-admin-comments', 'is_integration' => true, 'source_module' => 'foros'],
                'clubes-lectura' => ['label' => __('Clubes', 'flavor-chat-ia'), 'icon' => 'dashicons-groups', 'is_integration' => true, 'content' => '[biblioteca_clubes]'],
            ],

            // === MARKETPLACE ===
            // Tab listado usa Archive Renderer via template genérico
            'marketplace' => [
                'listado'      => ['label' => __('Explorar anuncios', 'flavor-chat-ia'), 'icon' => 'dashicons-megaphone', 'content' => 'template:_archive.php'],
                'mis-anuncios' => ['label' => __('Mi actividad', 'flavor-chat-ia'), 'icon' => 'dashicons-welcome-write-blog'],
                'categorias'   => ['label' => __('Categorías', 'flavor-chat-ia'), 'icon' => 'dashicons-category'],
                // Integraciones
                'favoritos'    => ['label' => __('Guardados', 'flavor-chat-ia'), 'icon' => 'dashicons-heart', 'is_integration' => true, 'content' => '[marketplace_favoritos]'],
                'mensajes'     => ['label' => __('Conversaciones', 'flavor-chat-ia'), 'icon' => 'dashicons-email-alt', 'is_integration' => true, 'source_module' => 'chat-interno'],
            ],

            // === INCIDENCIAS ===
            // Widgets: Mis Incidencias (resumen), Mapa (vista rápida)
            // Tab listado usa Archive Renderer via template, los demás usan legacy por ahora
            'incidencias' => [
                'listado'      => ['label' => __('Todas', 'flavor-chat-ia'), 'icon' => 'dashicons-list-view', 'content' => 'template:archive.php'],
                'mis-reportes' => ['label' => __('Mis Reportes', 'flavor-chat-ia'), 'icon' => 'dashicons-flag'],
                'mapa'         => ['label' => __('Mapa', 'flavor-chat-ia'), 'icon' => 'dashicons-location-alt'],
                // Integraciones
                'estadisticas' => ['label' => __('Estadísticas', 'flavor-chat-ia'), 'icon' => 'dashicons-chart-bar', 'is_integration' => true, 'content' => '[incidencias_estadisticas]'],
                'categorias'   => ['label' => __('Categorías', 'flavor-chat-ia'), 'icon' => 'dashicons-category', 'is_integration' => true, 'content' => '[incidencias_categorias]'],
            ],

            // === BANCO DE TIEMPO ===
            // Tabs principales cargan templates específicos del módulo
            'banco-tiempo' => [
                'servicios'    => ['label' => __('Servicios', 'flavor-chat-ia'), 'icon' => 'dashicons-admin-users', 'content' => '[banco_tiempo_servicios]'],
                'mi-saldo'     => ['label' => __('Mi Saldo', 'flavor-chat-ia'), 'icon' => 'dashicons-clock', 'content' => '[banco_tiempo_mi_saldo]'],
                'intercambios' => ['label' => __('Intercambios', 'flavor-chat-ia'), 'icon' => 'dashicons-randomize', 'content' => '[banco_tiempo_mis_intercambios]'],
                'ranking'      => ['label' => __('Ranking', 'flavor-chat-ia'), 'icon' => 'dashicons-awards', 'content' => '[banco_tiempo_ranking]'],
                // Integraciones
                'reputacion'   => ['label' => __('Mi Reputación', 'flavor-chat-ia'), 'icon' => 'dashicons-star-filled', 'content' => 'template:mi-reputacion.php'],
                'mensajes'     => ['label' => __('Mensajes', 'flavor-chat-ia'), 'icon' => 'dashicons-email-alt', 'is_integration' => true, 'source_module' => 'chat-interno'],
                'ofrecer'      => ['label' => __('Ofrecer', 'flavor-chat-ia'), 'icon' => 'dashicons-plus-alt', 'content' => '[banco_tiempo_ofrecer]', 'requires_login' => true],
                'buscar'       => ['label' => __('Buscar servicios', 'flavor-chat-ia'), 'icon' => 'dashicons-search', 'content' => '[banco_tiempo_servicios]'],
            ],

            // === BICICLETAS COMPARTIDAS ===
            'bicicletas-compartidas' => [
                'disponibles'   => ['label' => __('Disponibles', 'flavor-chat-ia'), 'icon' => 'dashicons-admin-site'],
                'mis-prestamos' => ['label' => __('Mis Préstamos', 'flavor-chat-ia'), 'icon' => 'dashicons-dashboard'],
                'mapa'          => ['label' => __('Mapa', 'flavor-chat-ia'), 'icon' => 'dashicons-location'],
                // Integraciones
                'estaciones'    => ['label' => __('Estaciones', 'flavor-chat-ia'), 'icon' => 'dashicons-location-alt', 'is_integration' => true, 'content' => '[bicicletas_estaciones]'],
                'incidencias'   => ['label' => __('Incidencias', 'flavor-chat-ia'), 'icon' => 'dashicons-warning', 'is_integration' => true, 'source_module' => 'incidencias'],
                'estadisticas'  => ['label' => __('Estadísticas', 'flavor-chat-ia'), 'icon' => 'dashicons-chart-bar', 'is_integration' => true, 'content' => '[bicicletas_estadisticas]'],
            ],

            // === PARKINGS ===
            'parkings' => [
                'disponibilidad' => ['label' => __('Disponibilidad', 'flavor-chat-ia'), 'icon' => 'dashicons-visibility'],
                'mis-reservas'   => ['label' => __('Mis Reservas', 'flavor-chat-ia'), 'icon' => 'dashicons-calendar-alt'],
                'mapa'           => ['label' => __('Mapa', 'flavor-chat-ia'), 'icon' => 'dashicons-location'],
                // Integraciones
                'tarifas'        => ['label' => __('Tarifas', 'flavor-chat-ia'), 'icon' => 'dashicons-money-alt', 'is_integration' => true, 'content' => '[parkings_tarifas]'],
                'ocupacion'      => ['label' => __('Ocupación', 'flavor-chat-ia'), 'icon' => 'dashicons-chart-line', 'is_integration' => true, 'content' => '[parkings_ocupacion]'],
            ],

            // === CARPOOLING ===
            'carpooling' => [
                'buscar'     => ['label' => __('Buscar Viaje', 'flavor-chat-ia'), 'icon' => 'dashicons-search'],
                'mis-viajes' => ['label' => __('Mis Viajes', 'flavor-chat-ia'), 'icon' => 'dashicons-car'],
                'ofrecer'    => ['label' => __('Ofrecer', 'flavor-chat-ia'), 'icon' => 'dashicons-plus-alt'],
                // Integraciones
                'rutas'      => ['label' => __('Rutas Frecuentes', 'flavor-chat-ia'), 'icon' => 'dashicons-randomize', 'is_integration' => true, 'content' => '[carpooling_rutas]'],
                'valoraciones' => ['label' => __('Valoraciones', 'flavor-chat-ia'), 'icon' => 'dashicons-star-filled', 'is_integration' => true, 'content' => '[carpooling_valoraciones]'],
                'mensajes'   => ['label' => __('Mensajes', 'flavor-chat-ia'), 'icon' => 'dashicons-email-alt', 'is_integration' => true, 'source_module' => 'chat-interno'],
            ],

            // === RECICLAJE ===
            'reciclaje' => [
                'puntos-cercanos' => ['label' => __('Puntos', 'flavor-chat-ia'), 'icon' => 'dashicons-location'],
                'mis-puntos'      => ['label' => __('Mi Impacto', 'flavor-chat-ia'), 'icon' => 'dashicons-chart-bar'],
                'guia'            => ['label' => __('Guía', 'flavor-chat-ia'), 'icon' => 'dashicons-info'],
                // Integraciones
                'ranking'         => ['label' => __('Ranking', 'flavor-chat-ia'), 'icon' => 'dashicons-awards', 'is_integration' => true, 'content' => '[reciclaje_ranking]'],
                'recompensas'     => ['label' => __('Recompensas', 'flavor-chat-ia'), 'icon' => 'dashicons-star-filled', 'is_integration' => true, 'content' => '[reciclaje_recompensas]'],
                'calendario'      => ['label' => __('Calendario', 'flavor-chat-ia'), 'icon' => 'dashicons-calendar', 'is_integration' => true, 'content' => '[reciclaje_calendario]'],
            ],

            // === COMPOSTAJE ===
            'compostaje' => [
                'mapa'             => ['label' => __('Composteras', 'flavor-chat-ia'), 'icon' => 'dashicons-location-alt'],
                'mis-aportaciones' => ['label' => __('Mis Aportaciones', 'flavor-chat-ia'), 'icon' => 'dashicons-admin-site-alt3'],
                'estadisticas'     => ['label' => __('Estadísticas', 'flavor-chat-ia'), 'icon' => 'dashicons-chart-area'],
                // Integraciones
                'comunidad'        => ['label' => __('Comunidad', 'flavor-chat-ia'), 'icon' => 'dashicons-groups', 'is_integration' => true, 'content' => '[compostaje_comunidad]'],
                'ranking'          => ['label' => __('Ranking', 'flavor-chat-ia'), 'icon' => 'dashicons-awards', 'is_integration' => true, 'content' => '[compostaje_ranking]'],
                'guias'            => ['label' => __('Guías', 'flavor-chat-ia'), 'icon' => 'dashicons-book', 'is_integration' => true, 'content' => '[compostaje_guias]'],
            ],

            // === BARES / COMERCIOS ===
            'bares' => [
                'listado' => ['label' => __('Directorio', 'flavor-chat-ia'), 'icon' => 'dashicons-store'],
                'mapa'    => ['label' => __('Mapa', 'flavor-chat-ia'), 'icon' => 'dashicons-location'],
                // Integraciones
                'eventos'     => ['label' => __('Eventos', 'flavor-chat-ia'), 'icon' => 'dashicons-calendar-alt', 'is_integration' => true, 'source_module' => 'eventos'],
                'opiniones'   => ['label' => __('Opiniones', 'flavor-chat-ia'), 'icon' => 'dashicons-star-filled', 'is_integration' => true, 'content' => '[bares_opiniones]'],
                'promociones' => ['label' => __('Promociones', 'flavor-chat-ia'), 'icon' => 'dashicons-megaphone', 'is_integration' => true, 'content' => '[bares_promociones]'],
            ],

            // === CURSOS ===
            'cursos' => [
                'catalogo'   => ['label' => __('Catálogo', 'flavor-chat-ia'), 'icon' => 'dashicons-welcome-learn-more', 'content' => '[cursos_catalogo]'],
                'mis-cursos' => ['label' => __('Mis Cursos', 'flavor-chat-ia'), 'icon' => 'dashicons-awards', 'content' => '[cursos_mis_inscripciones]'],
                'calendario' => ['label' => __('Calendario', 'flavor-chat-ia'), 'icon' => 'dashicons-calendar', 'content' => '[cursos_calendario]'],
                'aula'       => ['label' => __('Aula Virtual', 'flavor-chat-ia'), 'icon' => 'dashicons-desktop', 'content' => '[cursos_aula]', 'requires_login' => true],
                // Integraciones
                'multimedia' => ['label' => __('Videos', 'flavor-chat-ia'), 'icon' => 'dashicons-video-alt3', 'is_integration' => true, 'source_module' => 'multimedia'],
                'foro'       => ['label' => __('Foro', 'flavor-chat-ia'), 'icon' => 'dashicons-admin-comments', 'is_integration' => true, 'source_module' => 'foros'],
            ],

            // === TALLERES ===
            'talleres' => [
                'proximos'      => ['label' => __('Próximos', 'flavor-chat-ia'), 'icon' => 'dashicons-calendar'],
                'inscripciones' => ['label' => __('Inscripciones', 'flavor-chat-ia'), 'icon' => 'dashicons-tickets-alt'],
                'calendario'    => ['label' => __('Calendario', 'flavor-chat-ia'), 'icon' => 'dashicons-calendar-alt'],
                // Integraciones
                'materiales'    => ['label' => __('Materiales', 'flavor-chat-ia'), 'icon' => 'dashicons-media-document', 'is_integration' => true, 'content' => '[talleres_materiales]'],
                'multimedia'    => ['label' => __('Multimedia', 'flavor-chat-ia'), 'icon' => 'dashicons-format-gallery', 'is_integration' => true, 'source_module' => 'multimedia'],
                'foro'          => ['label' => __('Foro', 'flavor-chat-ia'), 'icon' => 'dashicons-admin-comments', 'is_integration' => true, 'source_module' => 'foros'],
            ],

            // === COLECTIVOS ===
            'colectivos' => [
                'listado'        => ['label' => __('Colectivos', 'flavor-chat-ia'), 'icon' => 'dashicons-groups'],
                'mis-colectivos' => ['label' => __('Mis Colectivos', 'flavor-chat-ia'), 'icon' => 'dashicons-admin-users'],
                // Integraciones
                'proyectos'      => ['label' => __('Proyectos', 'flavor-chat-ia'), 'icon' => 'dashicons-portfolio', 'is_integration' => true, 'content' => '[colectivos_proyectos]'],
                'asambleas'      => ['label' => __('Asambleas', 'flavor-chat-ia'), 'icon' => 'dashicons-groups', 'is_integration' => true, 'content' => '[colectivos_asambleas]'],
                'multimedia'     => ['label' => __('Multimedia', 'flavor-chat-ia'), 'icon' => 'dashicons-format-gallery', 'is_integration' => true, 'source_module' => 'multimedia'],
                'eventos'        => ['label' => __('Eventos', 'flavor-chat-ia'), 'icon' => 'dashicons-calendar-alt', 'is_integration' => true, 'source_module' => 'eventos'],
                'documentos'     => ['label' => __('Documentos', 'flavor-chat-ia'), 'icon' => 'dashicons-media-document', 'is_integration' => true, 'source_module' => 'multimedia'],
            ],

            // === COMUNIDADES ===
            'comunidades' => [
                'comunidades'     => ['label' => __('Explorar comunidades', 'flavor-chat-ia'), 'icon' => 'dashicons-list-view', 'content' => 'callback:render_tab_comunidades'],
                'crear'           => ['label' => __('Crear comunidad', 'flavor-chat-ia'), 'icon' => 'dashicons-plus-alt', 'content' => '[comunidades_crear]', 'requires_login' => true],
                'mis-comunidades' => ['label' => __('Mis espacios', 'flavor-chat-ia'), 'icon' => 'dashicons-groups', 'content' => 'callback:render_tab_mis_comunidades', 'requires_login' => true],
                'actividad'       => ['label' => __('Pulso comunitario', 'flavor-chat-ia'), 'icon' => 'dashicons-rss', 'content' => 'callback:render_tab_actividad', 'requires_login' => true],
                'foros'           => ['label' => __('Foros', 'flavor-chat-ia'), 'icon' => 'dashicons-admin-comments', 'is_integration' => true, 'source_module' => 'foros'],
                'multimedia'      => ['label' => __('Multimedia', 'flavor-chat-ia'), 'icon' => 'dashicons-format-gallery', 'is_integration' => true, 'source_module' => 'multimedia'],
                'eventos'         => ['label' => __('Encuentros', 'flavor-chat-ia'), 'icon' => 'dashicons-calendar-alt', 'content' => 'callback:render_tab_eventos'],
                'anuncios'        => ['label' => __('Avisos', 'flavor-chat-ia'), 'icon' => 'dashicons-megaphone', 'content' => '[comunidades_tablon limite="20" incluir_red="true"]'],
                'recursos'        => ['label' => __('Recursos compartidos', 'flavor-chat-ia'), 'icon' => 'dashicons-media-document', 'content' => '[comunidades_recursos_compartidos]'],
            ],

            // === SOCIOS ===
            'socios' => [
                'mi-membresia' => ['label' => __('Mi vínculo', 'flavor-chat-ia'), 'icon' => 'dashicons-id-alt'],
                'cuotas'       => ['label' => __('Aportaciones', 'flavor-chat-ia'), 'icon' => 'dashicons-money-alt'],
                'directorio'   => ['label' => __('Red de miembros', 'flavor-chat-ia'), 'icon' => 'dashicons-groups'],
                'beneficios'   => ['label' => __('Beneficios', 'flavor-chat-ia'), 'icon' => 'dashicons-awards'],
                'carnet'       => ['label' => __('Identidad', 'flavor-chat-ia'), 'icon' => 'dashicons-id'],
                'historial'    => ['label' => __('Historial', 'flavor-chat-ia'), 'icon' => 'dashicons-backup'],
                'eventos'      => ['label' => __('Eventos', 'flavor-chat-ia'), 'icon' => 'dashicons-calendar-alt', 'is_integration' => true, 'source_module' => 'eventos'],
            ],

            // === FOROS ===
            'foros' => [
                'temas-recientes' => ['label' => __('Temas Recientes', 'flavor-chat-ia'), 'icon' => 'dashicons-format-chat'],
                'mis-hilos'       => ['label' => __('Mis Hilos', 'flavor-chat-ia'), 'icon' => 'dashicons-admin-comments'],
                'nuevo-tema'      => ['label' => __('Nuevo Tema', 'flavor-chat-ia'), 'icon' => 'dashicons-plus-alt'],
                'categorias'      => ['label' => __('Categorías', 'flavor-chat-ia'), 'icon' => 'dashicons-category'],
            ],

            // === CHAT GRUPOS ===
            'chat-grupos' => [
                'mis-grupos' => ['label' => __('Mis Grupos', 'flavor-chat-ia'), 'icon' => 'dashicons-groups'],
                'explorar'   => ['label' => __('Explorar', 'flavor-chat-ia'), 'icon' => 'dashicons-search'],
                'crear'      => ['label' => __('Crear Grupo', 'flavor-chat-ia'), 'icon' => 'dashicons-plus-alt', 'content' => '[chat_grupos_crear]'],
                'mensajes'   => ['label' => __('Mensajes', 'flavor-chat-ia'), 'icon' => 'dashicons-format-chat'],
            ],

            // === RED SOCIAL ===
            'red-social' => [
                'feed'        => ['label' => __('Feed', 'flavor-chat-ia'), 'icon' => 'dashicons-rss', 'content' => 'template:feed.php'],
                'explorar'    => ['label' => __('Explorar', 'flavor-chat-ia'), 'icon' => 'dashicons-search', 'content' => 'template:explorar.php'],
                'amigos'      => ['label' => __('Amigos', 'flavor-chat-ia'), 'icon' => 'dashicons-groups', 'content' => 'template:amigos.php'],
                'historias'   => ['label' => __('Historias', 'flavor-chat-ia'), 'icon' => 'dashicons-format-video', 'content' => 'template:historias.php'],
                'mi-actividad' => ['label' => __('Mi Actividad', 'flavor-chat-ia'), 'icon' => 'dashicons-chart-line', 'content' => 'template:mi-actividad.php', 'requires_login' => true],
                // Integraciones
                'mensajes'    => ['label' => __('Mensajes', 'flavor-chat-ia'), 'icon' => 'dashicons-email-alt', 'is_integration' => true, 'source_module' => 'chat-interno'],
            ],

            // === PARTICIPACIÓN ===
            'participacion' => [
                'propuestas' => ['label' => __('Iniciativas', 'flavor-chat-ia'), 'icon' => 'dashicons-lightbulb'],
                'votaciones' => ['label' => __('Decisiones', 'flavor-chat-ia'), 'icon' => 'dashicons-thumbs-up'],
                'resultados' => ['label' => __('Acuerdos', 'flavor-chat-ia'), 'icon' => 'dashicons-chart-bar'],
                // Integraciones
                'debates'    => ['label' => __('Conversaciones', 'flavor-chat-ia'), 'icon' => 'dashicons-admin-comments', 'is_integration' => true, 'source_module' => 'foros'],
                'reuniones'  => ['label' => __('Encuentros', 'flavor-chat-ia'), 'icon' => 'dashicons-groups', 'is_integration' => true, 'source_module' => 'eventos'],
            ],

            // === PRESUPUESTOS PARTICIPATIVOS ===
            'presupuestos-participativos' => [
                'proyectos'  => ['label' => __('Proyectos', 'flavor-chat-ia'), 'icon' => 'dashicons-portfolio', 'content' => '[presupuestos_listado]'],
                'votaciones' => ['label' => __('Votaciones', 'flavor-chat-ia'), 'icon' => 'dashicons-thumbs-up', 'content' => '[presupuestos_votar]'],
                'fases'      => ['label' => __('Fases', 'flavor-chat-ia'), 'icon' => 'dashicons-chart-line', 'content' => '[presupuesto_estado_actual]'],
                'mis-propuestas' => ['label' => __('Mis Propuestas', 'flavor-chat-ia'), 'icon' => 'dashicons-edit', 'content' => '[presupuestos_mi_proyecto]'],
                'resultados' => ['label' => __('Resultados', 'flavor-chat-ia'), 'icon' => 'dashicons-chart-bar', 'content' => '[presupuestos_resultados]'],
                // Integraciones
                'seguimiento' => ['label' => __('Seguimiento', 'flavor-chat-ia'), 'icon' => 'dashicons-visibility', 'is_integration' => true, 'content' => '[presupuestos_seguimiento]'],
                'transparencia' => ['label' => __('Transparencia', 'flavor-chat-ia'), 'icon' => 'dashicons-clipboard', 'is_integration' => true, 'source_module' => 'transparencia'],
            ],

            // === AVISOS MUNICIPALES ===
            'avisos-municipales' => [
                'activos'   => ['label' => __('Activos', 'flavor-chat-ia'), 'icon' => 'dashicons-megaphone'],
                'urgentes'  => ['label' => __('Urgentes', 'flavor-chat-ia'), 'icon' => 'dashicons-warning'],
                'historial' => ['label' => __('Historial', 'flavor-chat-ia'), 'icon' => 'dashicons-backup'],
                // Integraciones
                'suscripciones' => ['label' => __('Suscripciones', 'flavor-chat-ia'), 'icon' => 'dashicons-email', 'is_integration' => true, 'content' => '[avisos_suscripciones]'],
                'categorias'    => ['label' => __('Categorías', 'flavor-chat-ia'), 'icon' => 'dashicons-category', 'is_integration' => true, 'content' => '[avisos_categorias]'],
            ],

            // === AYUDA VECINAL ===
            'ayuda-vecinal' => [
                'solicitudes'  => ['label' => __('Solicitudes', 'flavor-chat-ia'), 'icon' => 'dashicons-sos', 'content' => '[ayuda_vecinal_solicitudes]'],
                'ofrecer'      => ['label' => __('Ofrecer Ayuda', 'flavor-chat-ia'), 'icon' => 'dashicons-heart', 'content' => '[ayuda_vecinal_ofrecer]'],
                'solicitar'    => ['label' => __('Pedir Ayuda', 'flavor-chat-ia'), 'icon' => 'dashicons-plus-alt', 'content' => '[ayuda_vecinal_solicitar]', 'requires_login' => true],
                'mis-ayudas'   => ['label' => __('Mis Ayudas', 'flavor-chat-ia'), 'icon' => 'dashicons-admin-users', 'content' => '[ayuda_vecinal_mis_ayudas]', 'requires_login' => true],
                'mapa'         => ['label' => __('Mapa', 'flavor-chat-ia'), 'icon' => 'dashicons-location', 'content' => '[ayuda_vecinal_mapa]'],
                'estadisticas' => ['label' => __('Estadísticas', 'flavor-chat-ia'), 'icon' => 'dashicons-chart-bar', 'content' => '[ayuda_vecinal_estadisticas]'],
            ],

            // === TRÁMITES ===
            'tramites' => [
                'listado'      => ['label' => __('Catálogo', 'flavor-chat-ia'), 'icon' => 'dashicons-media-document', 'content' => '[flavor_tramites_catalogo]'],
                'iniciar'      => ['label' => __('Iniciar trámite', 'flavor-chat-ia'), 'icon' => 'dashicons-plus-alt', 'content' => '[flavor_tramites_solicitar]', 'requires_login' => true],
                'mis-tramites' => ['label' => __('Mis trámites', 'flavor-chat-ia'), 'icon' => 'dashicons-portfolio', 'content' => '[flavor_tramites_mis_solicitudes]', 'requires_login' => true],
                'seguimiento'  => ['label' => __('Seguimiento', 'flavor-chat-ia'), 'icon' => 'dashicons-search', 'content' => '[flavor_tramites_seguimiento]'],
            ],

            // === TRANSPARENCIA ===
            'transparencia' => [
                'portal'      => ['label' => __('Visión general', 'flavor-chat-ia'), 'icon' => 'dashicons-visibility'],
                'presupuesto' => ['label' => __('Recursos comunes', 'flavor-chat-ia'), 'icon' => 'dashicons-chart-pie'],
                'actas'       => ['label' => __('Memoria y actas', 'flavor-chat-ia'), 'icon' => 'dashicons-media-document'],
                // Integraciones
                'contratos'   => ['label' => __('Compromisos y contratos', 'flavor-chat-ia'), 'icon' => 'dashicons-portfolio', 'is_integration' => true, 'content' => '[transparencia_contratos]'],
                'indicadores' => ['label' => __('Indicadores compartidos', 'flavor-chat-ia'), 'icon' => 'dashicons-chart-bar', 'is_integration' => true, 'content' => '[transparencia_indicadores]'],
            ],

            // === FICHAJE EMPLEADOS ===
            'fichaje-empleados' => [
                'fichar'    => ['label' => __('Fichar', 'flavor-chat-ia'), 'icon' => 'dashicons-clock'],
                'historial' => ['label' => __('Historial', 'flavor-chat-ia'), 'icon' => 'dashicons-backup'],
            ],

            // === MULTIMEDIA ===
            'multimedia' => [
                'galeria'    => ['label' => __('Galería', 'flavor-chat-ia'), 'icon' => 'dashicons-format-gallery'],
                'mi-galeria' => ['label' => __('Mi Galería', 'flavor-chat-ia'), 'icon' => 'dashicons-images-alt2'],
                'albumes'    => ['label' => __('Álbumes', 'flavor-chat-ia'), 'icon' => 'dashicons-portfolio'],
                // Integraciones
                'comunidades' => ['label' => __('Comunidades', 'flavor-chat-ia'), 'icon' => 'dashicons-groups', 'is_integration' => true, 'source_module' => 'comunidades'],
                'colectivos'  => ['label' => __('Colectivos', 'flavor-chat-ia'), 'icon' => 'dashicons-networking', 'is_integration' => true, 'source_module' => 'colectivos'],
                'eventos'     => ['label' => __('Eventos', 'flavor-chat-ia'), 'icon' => 'dashicons-calendar-alt', 'is_integration' => true, 'source_module' => 'eventos'],
            ],

            // === PODCAST ===
            'podcast' => [
                'episodios' => ['label' => __('Episodios', 'flavor-chat-ia'), 'icon' => 'dashicons-microphone'],
                'series'    => ['label' => __('Series', 'flavor-chat-ia'), 'icon' => 'dashicons-playlist-audio'],
                // Integraciones
                'suscripciones' => ['label' => __('Suscripciones', 'flavor-chat-ia'), 'icon' => 'dashicons-rss', 'is_integration' => true, 'content' => '[podcast_suscripciones]'],
                'estadisticas' => ['label' => __('Estadísticas', 'flavor-chat-ia'), 'icon' => 'dashicons-chart-bar', 'is_integration' => true, 'content' => '[podcast_estadisticas]'],
            ],

            // === RADIO ===
            'radio' => [
                'en-directo'   => ['label' => __('En Directo', 'flavor-chat-ia'), 'icon' => 'dashicons-controls-volumeon'],
                'programacion' => ['label' => __('Programación', 'flavor-chat-ia'), 'icon' => 'dashicons-calendar-alt'],
                'podcasts'     => ['label' => __('Podcasts', 'flavor-chat-ia'), 'icon' => 'dashicons-microphone'],
                // Integraciones
                'archivo'      => ['label' => __('Archivo', 'flavor-chat-ia'), 'icon' => 'dashicons-archive', 'is_integration' => true, 'content' => '[radio_archivo]'],
                'chat'         => ['label' => __('Chat', 'flavor-chat-ia'), 'icon' => 'dashicons-admin-comments', 'is_integration' => true, 'source_module' => 'chat-grupos'],
                'colaboradores' => ['label' => __('Colaboradores', 'flavor-chat-ia'), 'icon' => 'dashicons-groups', 'is_integration' => true, 'content' => '[radio_colaboradores]'],
            ],

            // === FACTURAS ===
            'facturas' => [
                'mis-facturas' => ['label' => __('Mis Facturas', 'flavor-chat-ia'), 'icon' => 'dashicons-media-document'],
                'historial'    => ['label' => __('Historial', 'flavor-chat-ia'), 'icon' => 'dashicons-backup'],
            ],

            // === WOOCOMMERCE ===
            'woocommerce' => [
                'mis-pedidos' => ['label' => __('Mis Pedidos', 'flavor-chat-ia'), 'icon' => 'dashicons-cart'],
                'productos'   => ['label' => __('Productos', 'flavor-chat-ia'), 'icon' => 'dashicons-products'],
            ],

            // === TRADING IA ===
            'trading-ia' => [
                'dashboard'  => ['label' => __('Dashboard', 'flavor-chat-ia'), 'icon' => 'dashicons-chart-line'],
                'portfolio'  => ['label' => __('Portfolio', 'flavor-chat-ia'), 'icon' => 'dashicons-portfolio'],
                'historial'  => ['label' => __('Historial', 'flavor-chat-ia'), 'icon' => 'dashicons-backup'],
            ],

            // === ADVERTISING ===
            'advertising' => [
                'dashboard' => ['label' => __('Dashboard', 'flavor-chat-ia'), 'icon' => 'dashicons-chart-area'],
                'campanas'  => ['label' => __('Campañas', 'flavor-chat-ia'), 'icon' => 'dashicons-megaphone'],
                'ingresos'  => ['label' => __('Ingresos', 'flavor-chat-ia'), 'icon' => 'dashicons-money-alt'],
            ],

            // === EMAIL MARKETING ===
            'email-marketing' => [
                'suscripcion'  => ['label' => __('Suscripción', 'flavor-chat-ia'), 'icon' => 'dashicons-email'],
                'preferencias' => ['label' => __('Preferencias', 'flavor-chat-ia'), 'icon' => 'dashicons-admin-settings'],
                'archivo'      => ['label' => __('Archivo', 'flavor-chat-ia'), 'icon' => 'dashicons-archive'],
            ],

            // === DEX SOLANA ===
            'dex-solana' => [
                'dashboard' => ['label' => __('Dashboard', 'flavor-chat-ia'), 'icon' => 'dashicons-chart-line'],
                'swap'      => ['label' => __('Swap', 'flavor-chat-ia'), 'icon' => 'dashicons-randomize'],
                'pools'     => ['label' => __('Pools', 'flavor-chat-ia'), 'icon' => 'dashicons-networking'],
            ],

            // === EMPRESARIAL ===
            'empresarial' => [
                'directorio' => ['label' => __('Directorio', 'flavor-chat-ia'), 'icon' => 'dashicons-building'],
                'servicios'  => ['label' => __('Servicios', 'flavor-chat-ia'), 'icon' => 'dashicons-hammer'],
                'categorias' => ['label' => __('Categorías', 'flavor-chat-ia'), 'icon' => 'dashicons-category'],
            ],

            // === CLIENTES (CRM) ===
            'clientes' => [
                'listado'      => ['label' => __('Clientes', 'flavor-chat-ia'), 'icon' => 'dashicons-groups'],
                'estadisticas' => ['label' => __('Estadísticas', 'flavor-chat-ia'), 'icon' => 'dashicons-chart-bar'],
            ],

            // === HUELLA ECOLÓGICA ===
            'huella-ecologica' => [
                'calculadora' => ['label' => __('Calculadora', 'flavor-chat-ia'), 'icon' => 'dashicons-performance'],
                'mis-registros' => ['label' => __('Mis Registros', 'flavor-chat-ia'), 'icon' => 'dashicons-chart-line'],
                'logros'      => ['label' => __('Logros', 'flavor-chat-ia'), 'icon' => 'dashicons-awards'],
                // Integraciones
                'proyectos'   => ['label' => __('Proyectos', 'flavor-chat-ia'), 'icon' => 'dashicons-portfolio', 'is_integration' => true, 'content' => '[huella_ecologica_proyectos]'],
                'comunidad'   => ['label' => __('Comunidad', 'flavor-chat-ia'), 'icon' => 'dashicons-networking', 'is_integration' => true, 'content' => '[huella_ecologica_comunidad]'],
                'retos'       => ['label' => __('Retos', 'flavor-chat-ia'), 'icon' => 'dashicons-flag', 'is_integration' => true, 'content' => '[huella_ecologica_retos]'],
            ],

            // === SABERES ANCESTRALES ===
            'saberes-ancestrales' => [
                'catalogo'  => ['label' => __('Catálogo', 'flavor-chat-ia'), 'icon' => 'dashicons-book-alt'],
                'compartir' => ['label' => __('Compartir', 'flavor-chat-ia'), 'icon' => 'dashicons-share'],
                'talleres'  => ['label' => __('Talleres', 'flavor-chat-ia'), 'icon' => 'dashicons-welcome-learn-more'],
                // Integraciones
                'maestros'  => ['label' => __('Maestros', 'flavor-chat-ia'), 'icon' => 'dashicons-businessman', 'is_integration' => true, 'content' => '[saberes_ancestrales_maestros]'],
                'multimedia' => ['label' => __('Multimedia', 'flavor-chat-ia'), 'icon' => 'dashicons-format-gallery', 'is_integration' => true, 'source_module' => 'multimedia'],
                'foro'      => ['label' => __('Foro', 'flavor-chat-ia'), 'icon' => 'dashicons-admin-comments', 'is_integration' => true, 'source_module' => 'foros'],
                'recetas'   => ['label' => __('Recetas', 'flavor-chat-ia'), 'icon' => 'dashicons-carrot', 'is_integration' => true, 'source_module' => 'recetas'],
            ],

            // === ECONOMÍA DEL DON ===
            'economia-don' => [
                'dones'     => ['label' => __('Dones', 'flavor-chat-ia'), 'icon' => 'dashicons-heart'],
                'mis-dones' => ['label' => __('Mis Dones', 'flavor-chat-ia'), 'icon' => 'dashicons-admin-users'],
                'gratitud'  => ['label' => __('Muro Gratitud', 'flavor-chat-ia'), 'icon' => 'dashicons-format-status'],
                // Integraciones
                'mapa'      => ['label' => __('Mapa', 'flavor-chat-ia'), 'icon' => 'dashicons-location-alt', 'is_integration' => true, 'content' => '[economia_don_mapa]'],
                'foro'      => ['label' => __('Foro', 'flavor-chat-ia'), 'icon' => 'dashicons-admin-comments', 'is_integration' => true, 'source_module' => 'foros'],
            ],

            // === ECONOMÍA DE SUFICIENCIA ===
            'economia-suficiencia' => [
                'introduccion' => ['label' => __('Introducción', 'flavor-chat-ia'), 'icon' => 'dashicons-info'],
                'mi-camino'    => ['label' => __('Mi Camino', 'flavor-chat-ia'), 'icon' => 'dashicons-admin-users'],
                'biblioteca'   => ['label' => __('Biblioteca', 'flavor-chat-ia'), 'icon' => 'dashicons-book-alt'],
                // Integraciones
                'retos'        => ['label' => __('Retos', 'flavor-chat-ia'), 'icon' => 'dashicons-awards', 'is_integration' => true, 'content' => '[economia_suficiencia_retos]'],
                'comunidad'    => ['label' => __('Comunidad', 'flavor-chat-ia'), 'icon' => 'dashicons-groups', 'is_integration' => true, 'content' => '[economia_suficiencia_comunidad]'],
                'huella'       => ['label' => __('Mi Huella', 'flavor-chat-ia'), 'icon' => 'dashicons-chart-area', 'is_integration' => true, 'source_module' => 'huella-ecologica'],
            ],

            // === TRABAJO DIGNO ===
            'trabajo-digno' => [
                'ofertas'    => ['label' => __('Ofertas', 'flavor-chat-ia'), 'icon' => 'dashicons-businessman'],
                'mi-perfil'  => ['label' => __('Mi Perfil', 'flavor-chat-ia'), 'icon' => 'dashicons-admin-users'],
                'formacion'  => ['label' => __('Formación', 'flavor-chat-ia'), 'icon' => 'dashicons-welcome-learn-more'],
                // Integraciones
                'emprendimientos' => ['label' => __('Emprendimientos', 'flavor-chat-ia'), 'icon' => 'dashicons-store', 'is_integration' => true, 'content' => '[trabajo_digno_emprendimientos]'],
                'alertas'    => ['label' => __('Alertas', 'flavor-chat-ia'), 'icon' => 'dashicons-bell', 'is_integration' => true, 'content' => '[trabajo_digno_alertas]'],
                'cursos'     => ['label' => __('Cursos', 'flavor-chat-ia'), 'icon' => 'dashicons-book-alt', 'is_integration' => true, 'source_module' => 'cursos'],
            ],

            // === CÍRCULOS DE CUIDADOS ===
            'circulos-cuidados' => [
                'circulos'     => ['label' => __('Círculos', 'flavor-chat-ia'), 'icon' => 'dashicons-groups'],
                'mis-cuidados' => ['label' => __('Mis Cuidados', 'flavor-chat-ia'), 'icon' => 'dashicons-heart'],
                'necesidades'  => ['label' => __('Necesidades', 'flavor-chat-ia'), 'icon' => 'dashicons-sos'],
                // Integraciones
                'calendario'   => ['label' => __('Calendario', 'flavor-chat-ia'), 'icon' => 'dashicons-calendar', 'is_integration' => true, 'content' => '[circulos_cuidados_calendario]'],
                'recursos'     => ['label' => __('Recursos', 'flavor-chat-ia'), 'icon' => 'dashicons-media-document', 'is_integration' => true, 'content' => '[circulos_cuidados_recursos]'],
                'foro'         => ['label' => __('Foro', 'flavor-chat-ia'), 'icon' => 'dashicons-admin-comments', 'is_integration' => true, 'source_module' => 'foros'],
            ],

            // === JUSTICIA RESTAURATIVA ===
            'justicia-restaurativa' => [
                'informacion'  => ['label' => __('Información', 'flavor-chat-ia'), 'icon' => 'dashicons-info'],
                'mis-procesos' => ['label' => __('Mis Procesos', 'flavor-chat-ia'), 'icon' => 'dashicons-portfolio'],
                'mediadores'   => ['label' => __('Mediadores', 'flavor-chat-ia'), 'icon' => 'dashicons-groups'],
                // Integraciones
                'recursos'     => ['label' => __('Recursos', 'flavor-chat-ia'), 'icon' => 'dashicons-book', 'is_integration' => true, 'content' => '[justicia_restaurativa_recursos]'],
                'formacion'    => ['label' => __('Formación', 'flavor-chat-ia'), 'icon' => 'dashicons-welcome-learn-more', 'is_integration' => true, 'source_module' => 'cursos'],
            ],

            // === SELLO CONCIENCIA ===
            'sello-conciencia' => [
                'mi-badge' => ['label' => __('Mi Badge', 'flavor-chat-ia'), 'icon' => 'dashicons-awards'],
                'premisas' => ['label' => __('Premisas', 'flavor-chat-ia'), 'icon' => 'dashicons-editor-ul'],
            ],

            // === CHAT INTERNO ===
            'chat-interno' => [
                'bandeja'  => ['label' => __('Bandeja', 'flavor-chat-ia'), 'icon' => 'dashicons-email-alt'],
                'contactos'=> ['label' => __('Contactos', 'flavor-chat-ia'), 'icon' => 'dashicons-groups'],
            ],

            // === BIODIVERSIDAD LOCAL ===
            'biodiversidad-local' => [
                'especies' => ['label' => __('Especies', 'flavor-chat-ia'), 'icon' => 'dashicons-palmtree'],
                'mapa'     => ['label' => __('Mapa', 'flavor-chat-ia'), 'icon' => 'dashicons-location'],
                'avistamientos' => ['label' => __('Avistamientos', 'flavor-chat-ia'), 'icon' => 'dashicons-visibility'],
                // Integraciones
                'galeria'   => ['label' => __('Galería', 'flavor-chat-ia'), 'icon' => 'dashicons-format-gallery', 'is_integration' => true, 'source_module' => 'multimedia'],
                'proyectos' => ['label' => __('Proyectos', 'flavor-chat-ia'), 'icon' => 'dashicons-portfolio', 'is_integration' => true, 'content' => '[biodiversidad_proyectos]'],
                'foro'      => ['label' => __('Foro', 'flavor-chat-ia'), 'icon' => 'dashicons-admin-comments', 'is_integration' => true, 'source_module' => 'foros'],
            ],

            // === RECETAS ===
            'recetas' => [
                'listado'     => ['label' => __('Recetas', 'flavor-chat-ia'), 'icon' => 'dashicons-carrot', 'content' => '[flavor_recetas_dashboard]'],
                'mis-recetas' => ['label' => __('Mis Recetas', 'flavor-chat-ia'), 'icon' => 'dashicons-welcome-write-blog', 'content' => '[flavor_recetas_mis_recetas]'],
                'crear'       => ['label' => __('Nueva Receta', 'flavor-chat-ia'), 'icon' => 'dashicons-plus-alt', 'content' => '[flavor_recetas_crear]'],
                'favoritas'   => ['label' => __('Favoritas', 'flavor-chat-ia'), 'icon' => 'dashicons-heart', 'content' => '[flavor_recetas_favoritas]'],
                'categorias'  => ['label' => __('Categorías', 'flavor-chat-ia'), 'icon' => 'dashicons-category', 'content' => '[flavor_recetas_categorias]'],
                // Integraciones
                'ingredientes' => ['label' => __('Ingredientes', 'flavor-chat-ia'), 'icon' => 'dashicons-list-view', 'is_integration' => true, 'content' => '[recetas_ingredientes]'],
                'temporada'    => ['label' => __('De temporada', 'flavor-chat-ia'), 'icon' => 'dashicons-calendar', 'is_integration' => true, 'content' => '[recetas_temporada]'],
                'huertos'      => ['label' => __('Huertos', 'flavor-chat-ia'), 'icon' => 'dashicons-admin-site-alt3', 'is_integration' => true, 'source_module' => 'huertos-urbanos'],
                'grupos-consumo' => ['label' => __('G. Consumo', 'flavor-chat-ia'), 'icon' => 'dashicons-store', 'is_integration' => true, 'source_module' => 'grupos-consumo'],
            ],
        ];

        // Tabs por defecto para módulos sin configuración específica
        $tabs_default = [
            'listado'   => ['label' => __('Listado', 'flavor-chat-ia'), 'icon' => 'dashicons-list-view'],
            'actividad' => ['label' => __('Actividad', 'flavor-chat-ia'), 'icon' => 'dashicons-bell'],
        ];

        return $tabs_config[$module_id] ?? $tabs_default;
    }

    /**
     * Fusiona tabs legacy del modulo con tabs del renderer moderno.
     *
     * Los tabs del renderer completan o corrigen campos del tab legacy
     * manteniendo el orden principal definido por el módulo.
     *
     * @param array $dashboard_tabs
     * @param array $renderer_tabs
     * @return array
     */
    private function merge_module_tabs(array $dashboard_tabs, array $renderer_tabs): array {
        if (empty($dashboard_tabs)) {
            return $renderer_tabs;
        }

        if (empty($renderer_tabs)) {
            return $dashboard_tabs;
        }

        $merged_tabs = $dashboard_tabs;

        foreach ($renderer_tabs as $tab_id => $tab_config) {
            if (isset($merged_tabs[$tab_id]) && is_array($merged_tabs[$tab_id])) {
                $merged_tabs[$tab_id] = array_merge($merged_tabs[$tab_id], $tab_config);
                continue;
            }

            $merged_tabs[$tab_id] = $tab_config;
        }

        return $merged_tabs;
    }

    /**
     * Convierte tabs del nuevo formato (get_renderer_config) al formato legacy
     *
     * Nuevo formato:
     * [
     *     'listado' => [
     *         'label'   => 'Todas',
     *         'icon'    => '📋',
     *         'content' => 'template:archive.php',
     *     ],
     * ]
     *
     * Formato legacy:
     * [
     *     'listado' => [
     *         'label'   => 'Todas',
     *         'icon'    => 'dashicons-list-view',
     *         'content' => 'template:archive.php',
     *     ],
     * ]
     *
     * @param array $tabs   Tabs del nuevo sistema
     * @param array $config Configuración completa del módulo
     * @return array Tabs en formato legacy
     */
    private function convert_renderer_tabs_to_legacy(array $tabs, array $config): array {
        $legacy_tabs = [];
        $module_id = $config['module'] ?? '';

        foreach ($tabs as $tab_id => $tab_config) {
            $legacy_tab = [
                'label' => $tab_config['label'] ?? ucfirst($tab_id),
            ];

            // Convertir icono emoji a dashicon si es necesario
            if (!empty($tab_config['icon'])) {
                $icon = $tab_config['icon'];
                // Si ya es un dashicon, usarlo directamente
                if (strpos($icon, 'dashicons-') === 0) {
                    $legacy_tab['icon'] = $icon;
                } else {
                    // Es un emoji, mapear a dashicon equivalente o usar uno por defecto
                    $legacy_tab['icon'] = $this->map_emoji_to_dashicon($icon, $tab_id);
                    // Guardar el emoji original para uso en templates modernos
                    $legacy_tab['emoji'] = $icon;
                }
            }

            // Contenido del tab
            if (!empty($tab_config['content'])) {
                $legacy_tab['content'] = $tab_config['content'];
            } elseif (!empty($tab_config['shortcode'])) {
                $legacy_tab['content'] = '[' . $tab_config['shortcode'] . ']';
            } elseif (!empty($tab_config['template'])) {
                $legacy_tab['content'] = 'template:' . $tab_config['template'];
            }

            // Integraciones
            if (!empty($tab_config['is_integration'])) {
                $legacy_tab['is_integration'] = true;
            }
            if (!empty($tab_config['source_module'])) {
                $legacy_tab['source_module'] = $tab_config['source_module'];
            }
            if (!empty($tab_config['requires_login'])) {
                $legacy_tab['requires_login'] = true;
            }
            if (!empty($tab_config['hidden_nav'])) {
                $legacy_tab['hidden_nav'] = true;
            }
            if (array_key_exists('public', $tab_config)) {
                $legacy_tab['public'] = (bool) $tab_config['public'];
            }
            if (!empty($tab_config['description'])) {
                $legacy_tab['description'] = $tab_config['description'];
            }
            if (!empty($tab_config['cap'])) {
                $legacy_tab['cap'] = $tab_config['cap'];
            }

            $legacy_tabs[$tab_id] = $legacy_tab;
        }

        return $legacy_tabs;
    }

    /**
     * Mapea un emoji a un dashicon equivalente
     *
     * @param string $emoji  El emoji a mapear
     * @param string $tab_id ID del tab como fallback
     * @return string Clase de dashicon
     */
    private function map_emoji_to_dashicon(string $emoji, string $tab_id = ''): string {
        $emoji_map = [
            // Listados y vistas
            '📋' => 'dashicons-list-view',
            '📝' => 'dashicons-edit',
            '📊' => 'dashicons-chart-bar',
            '📈' => 'dashicons-chart-line',
            '📉' => 'dashicons-chart-area',

            // Ubicación y mapas
            '📍' => 'dashicons-location',
            '🗺️' => 'dashicons-location-alt',
            '🗺' => 'dashicons-location-alt',

            // Personas y grupos
            '👤' => 'dashicons-admin-users',
            '👥' => 'dashicons-groups',
            '🧑' => 'dashicons-businessman',

            // Alertas y estados
            '⚠️' => 'dashicons-warning',
            '🔴' => 'dashicons-marker',
            '🟡' => 'dashicons-flag',
            '🟢' => 'dashicons-yes-alt',
            '✅' => 'dashicons-yes',
            '❌' => 'dashicons-no',

            // Categorías y filtros
            '🏷️' => 'dashicons-tag',
            '📁' => 'dashicons-category',
            '🔍' => 'dashicons-search',

            // Tiempo y calendario
            '📅' => 'dashicons-calendar',
            '🕐' => 'dashicons-clock',
            '⏰' => 'dashicons-backup',

            // Comercio y dinero
            '💰' => 'dashicons-money-alt',
            '🛒' => 'dashicons-cart',
            '🏪' => 'dashicons-store',
            '📦' => 'dashicons-products',

            // Comunicación
            '💬' => 'dashicons-admin-comments',
            '✉️' => 'dashicons-email-alt',
            '📧' => 'dashicons-email',
            '🔔' => 'dashicons-bell',

            // Multimedia
            '📷' => 'dashicons-camera',
            '🖼️' => 'dashicons-format-gallery',
            '🎬' => 'dashicons-video-alt3',
            '🎵' => 'dashicons-format-audio',

            // Naturaleza
            '🌿' => 'dashicons-palmtree',
            '🌱' => 'dashicons-admin-site-alt3',
            '🌳' => 'dashicons-admin-site',

            // Corazones y favoritos
            '❤️' => 'dashicons-heart',
            '⭐' => 'dashicons-star-filled',
            '🏆' => 'dashicons-awards',

            // Configuración
            '⚙️' => 'dashicons-admin-settings',
            '🔧' => 'dashicons-admin-tools',

            // Documentos
            '📄' => 'dashicons-media-document',
            '📚' => 'dashicons-book-alt',

            // Default según tab_id
        ];

        if (isset($emoji_map[$emoji])) {
            return $emoji_map[$emoji];
        }

        // Fallback según tab_id común
        $tab_icons = [
            'listado'      => 'dashicons-list-view',
            'mapa'         => 'dashicons-location-alt',
            'estadisticas' => 'dashicons-chart-bar',
            'categorias'   => 'dashicons-category',
            'calendario'   => 'dashicons-calendar',
            'configuracion'=> 'dashicons-admin-settings',
            'actividad'    => 'dashicons-bell',
            'favoritos'    => 'dashicons-heart',
            'mensajes'     => 'dashicons-email-alt',
        ];

        return $tab_icons[$tab_id] ?? 'dashicons-admin-generic';
    }

    /**
     * Renderiza el contenido de un tab
     *
     * Soporta múltiples tipos de contenido:
     * - 'content' como shortcode: '[shortcode_name args]'
     * - 'content' como template: 'template:nombre-archivo.php'
     * - 'content' como método: 'nombre_metodo' (del módulo)
     * - 'content' como callable: function($tab_id, $module) {}
     * - Sin 'content': usa sistema legacy de switch/case
     *
     * @param string $tab_id    ID del tab
     * @param array  $tab_info  Información del tab
     * @param object $module    Instancia del módulo
     */
    private function render_tab_content($tab_id, $tab_info, $module) {
        // PRIORIDAD 0: Si es una integración con source_module, usar ese módulo directamente
        if (!empty($tab_info['is_integration']) && !empty($tab_info['source_module'])) {
            $source_module = $tab_info['source_module'];
            $source_module_slug = str_replace('_', '-', $source_module);
            $source_normalized = str_replace('-', '_', $source_module);
            $contextual_shortcode = $this->get_contextual_integration_shortcode($source_module_slug);
            ?>
            <div class="fmd-tab-integration" data-source-module="<?php echo esc_attr($source_module_slug); ?>">
                <?php
                if ($contextual_shortcode !== null) {
                    do_action('flavor_rendering_tab_integrado', $source_module_slug, $this->current_module, $this->current_item_id);
                    $this->ensure_integration_assets_loaded($source_module_slug);
                    echo do_shortcode($contextual_shortcode);
                    ?>
                    </div>
                    <?php
                    return;
                }

                // Intentar usar el shortcode nativo del módulo fuente
                $shortcode_candidates = [
                    'flavor_' . $source_normalized . '_listado',
                    'flavor_' . $source_module_slug . '_listado',
                    'flavor_' . $source_normalized,
                    'flavor_' . $source_module_slug,
                    $source_normalized . '_listado',
                ];

                $shortcode_found = false;
                foreach ($shortcode_candidates as $shortcode_name) {
                    if (shortcode_exists($shortcode_name)) {
                        echo do_shortcode('[' . $shortcode_name . ' cantidad="12" limit="12"]');
                        $shortcode_found = true;
                        break;
                    }
                }

                if (!$shortcode_found) {
                    // Fallback al shortcode unificado
                    echo do_shortcode('[flavor module="' . esc_attr($source_module_slug) . '" view="listado" header="no" limit="12"]');
                }
                ?>
            </div>
            <?php
            return;
        }

        // PRIORIDAD 1: Si el tab tiene 'content' definido, usarlo
        if (!empty($tab_info['content'])) {
            $this->render_tab_content_dynamic($tab_id, $tab_info, $module);
            return;
        }

        // PRIORIDAD 2: Si el módulo tiene método render_tab_{tab_id}(), usarlo
        $method_name = 'render_tab_' . str_replace('-', '_', $tab_id);
        if ($module && method_exists($module, $method_name)) {
            $module->$method_name(get_current_user_id());
            return;
        }

        // PRIORIDAD 3: Sistema legacy - renderizado genérico según el tipo de tab
        $this->render_tab_content_legacy($tab_id, $tab_info, $module);
    }

    /**
     * Renderiza contenido dinámico de un tab (nuevo sistema)
     *
     * @param string $tab_id    ID del tab
     * @param array  $tab_info  Información del tab
     * @param object $module    Instancia del módulo
     */
    private function render_tab_content_dynamic($tab_id, $tab_info, $module) {
        $contenido = $tab_info['content'];
        $module_id = str_replace('_', '-', $this->current_module);

        // Priorizar renderizadores reales de creación del módulo para evitar
        // caer en shortcodes genéricos de listado en acciones tipo "crear".
        if ($this->is_create_action($tab_id)) {
            $create_output = $this->resolve_module_create_content($module);
            if ($create_output !== null && trim($create_output) !== '') {
                echo $create_output;
                return;
            }
        }

        // Tipo 1: Shortcode directo con corchetes [shortcode]
        if (is_string($contenido) && strpos($contenido, '[') === 0) {
            // Extraer nombre del shortcode
            preg_match('/\[([a-zA-Z0-9_-]+)/', $contenido, $matches);
            $shortcode_name = $matches[1] ?? '';

            // Solo ejecutar si el shortcode existe
            if ($shortcode_name && shortcode_exists($shortcode_name)) {
                $output = do_shortcode($contenido);
                if (!empty(trim($output)) && $output !== $contenido) {
                    echo $output;
                    return;
                }
            }

            if ($shortcode_name && $module) {
                $module_prefix = str_replace('-', '_', $module_id) . '_';
                if (strpos($shortcode_name, $module_prefix) === 0) {
                    $shortcode_suffix = substr($shortcode_name, strlen($module_prefix));
                    $shortcode_method = 'shortcode_' . $shortcode_suffix;

                    if (method_exists($module, $shortcode_method)) {
                        $output = $module->{$shortcode_method}([]);
                        if (is_string($output) && trim($output) !== '') {
                            echo $output;
                            return;
                        }
                    }
                }
            }

            // Fallback si shortcode no existe o no produce salida
            $this->render_tab_fallback($tab_id, $module_id);
            return;
        }

        // Tipo 2: Shortcode con prefijo shortcode:nombre
        if (is_string($contenido) && strpos($contenido, 'shortcode:') === 0) {
            $shortcode_name = str_replace('shortcode:', '', $contenido);

            // Resolver variantes comunes para evitar fallos por prefijos/aliases
            // y por diferencias guion/guion_bajo entre módulos.
            $base_candidates = [
                $shortcode_name,
                str_replace('-', '_', $shortcode_name),
                str_replace('_', '-', $shortcode_name),
            ];

            $shortcode_candidates = [];
            foreach ($base_candidates as $candidate) {
                if (!is_string($candidate) || $candidate === '') {
                    continue;
                }

                $shortcode_candidates[] = $candidate;
                if (strpos($candidate, 'flavor_') !== 0) {
                    $shortcode_candidates[] = 'flavor_' . $candidate;
                }
            }

            foreach (array_unique($shortcode_candidates) as $candidate) {
                if (!shortcode_exists($candidate)) {
                    continue;
                }

                $output = do_shortcode('[' . $candidate . ']');
                if (!empty(trim($output))) {
                    echo $output;
                    return;
                }
            }

            // Intentar resolver con metodos del modulo cuando el shortcode
            // configurado no esta registrado (o devuelve vacio).
            if ($module) {
                $normalized_shortcode = str_replace('-', '_', (string) $shortcode_name);
                $normalized_tab = str_replace('-', '_', (string) $tab_id);
                $module_prefix = str_replace('-', '_', (string) $module_id) . '_';

                $method_candidates = [
                    'shortcode_' . $normalized_tab,
                    'shortcode_' . $normalized_shortcode,
                ];

                $shortcode_without_prefix = preg_replace('/^flavor_/', '', $normalized_shortcode);
                if (strpos((string) $shortcode_without_prefix, $module_prefix) === 0) {
                    $suffix = substr((string) $shortcode_without_prefix, strlen($module_prefix));
                    if ($suffix !== '') {
                        $method_candidates[] = 'shortcode_' . $suffix;
                    }
                }

                foreach (array_unique($method_candidates) as $method_name) {
                    if (!method_exists($module, $method_name)) {
                        continue;
                    }

                    $output = $module->{$method_name}([]);
                    if (is_string($output) && trim($output) !== '') {
                        echo $output;
                        return;
                    }
                }
            }

            // Fallback: usar Archive Renderer
            $this->render_tab_fallback($tab_id, $module_id);
            return;
        }

        // Tipo 3: Template
        if (is_string($contenido) && strpos($contenido, 'template:') === 0) {
            $template_name = str_replace('template:', '', $contenido);
            $this->render_tab_template($template_name, $tab_id, $module_id, $module);
            return;
        }

        // Tipo 4: Prefijo callback:nombre_metodo del modulo
        if (is_string($contenido) && strpos($contenido, 'callback:') === 0) {
            $method_name = str_replace('callback:', '', $contenido);

            if ($module && method_exists($module, $method_name)) {
                $output = $module->{$method_name}(get_current_user_id());
                if (is_string($output) && $output !== '') {
                    echo $output;
                }
                return;
            }

            $this->render_tab_fallback($tab_id, $module_id);
            return;
        }

        // Tipo 5: Callable (closure o función)
        if (is_callable($contenido)) {
            call_user_func($contenido, $tab_id, $module, $this);
            return;
        }

        // Tipo 6: Nombre de método del módulo
        if (is_string($contenido) && $module && method_exists($module, $contenido)) {
            $output = $module->{$contenido}(get_current_user_id());
            if (is_string($output) && $output !== '') {
                echo $output;
            }
            return;
        }

        // Tipo 7: String directo (HTML)
        if (is_string($contenido)) {
            echo wp_kses_post($contenido);
            return;
        }

        // Fallback: mensaje vacío
        echo '<p class="fmd-empty">' . esc_html__('Todavía no hay contenido en este espacio.', 'flavor-chat-ia') . '</p>';
    }

    /**
     * Resuelve el contenido real de creación para un módulo.
     *
     * Muchos módulos registran shortcodes comunes "modulo_crear" que renderizan
     * listados genéricos; este método prioriza callbacks nativos de creación.
     *
     * @param object|null $module
     * @return string|null
     */
    private function resolve_module_create_content($module): ?string {
        if (!is_object($module)) {
            return null;
        }

        $preferred_methods = [
            'shortcode_crear',
            'shortcode_formulario',
            'shortcode_nuevo',
        ];

        foreach ($preferred_methods as $method_name) {
            if (!method_exists($module, $method_name)) {
                continue;
            }

            $output = $module->{$method_name}([]);
            if (is_string($output) && trim($output) !== '') {
                return $output;
            }
        }

        // Fallback: si existe un unico shortcode de creacion especifico.
        $methods = get_class_methods($module) ?: [];
        $create_methods = array_values(array_filter($methods, static function($method) {
            return strpos((string) $method, 'shortcode_crear_') === 0;
        }));

        if (count($create_methods) === 1) {
            $method_name = $create_methods[0];
            $output = $module->{$method_name}([]);
            if (is_string($output) && trim($output) !== '') {
                return $output;
            }
        }

        return null;
    }

    /**
     * Carga un template para un tab
     *
     * @param string $template_name Nombre del archivo de template
     * @param string $tab_id       ID del tab
     * @param string $module_id    ID del módulo
     * @param object $module       Instancia del módulo
     */
    private function render_tab_template($template_name, $tab_id, $module_id, $module) {
        $module_slug = str_replace('_', '-', $module_id);

        // Buscar template en orden de prioridad (tema > plugin)
        // Incluye carpeta /tabs/, raíz del módulo, y template genérico
        $paths = [
            // Tema hijo/padre - carpeta tabs
            get_stylesheet_directory() . "/flavor/{$module_slug}/tabs/{$template_name}",
            get_template_directory() . "/flavor/{$module_slug}/tabs/{$template_name}",
            // Plugin - tabs y plantillas frontend primero
            FLAVOR_CHAT_IA_PATH . "templates/frontend/{$module_slug}/tabs/{$template_name}",
            FLAVOR_CHAT_IA_PATH . "includes/modules/{$module_slug}/frontend/tabs/{$template_name}",
            FLAVOR_CHAT_IA_PATH . "includes/modules/{$module_slug}/templates/{$template_name}",
            FLAVOR_CHAT_IA_PATH . "templates/frontend/{$module_slug}/{$template_name}",
            FLAVOR_CHAT_IA_PATH . "includes/modules/{$module_slug}/frontend/{$template_name}",
            // Vistas legacy al final para no colar pantallas de gestión en el portal
            FLAVOR_CHAT_IA_PATH . "includes/modules/{$module_slug}/views/tabs/{$template_name}",
            FLAVOR_CHAT_IA_PATH . "includes/modules/{$module_slug}/views/{$template_name}",
            // Template genérico (fallback para todos los módulos)
            FLAVOR_CHAT_IA_PATH . "templates/frontend/{$template_name}",
        ];

        // Variables disponibles en el template
        $tab_data = [
            'tab_id'    => $tab_id,
            'module_id' => $module_id,
            'module'    => $module,
            'user_id'   => get_current_user_id(),
            'atributos' => [], // Para compatibilidad con templates de shortcode
        ];

        // Cargar datos específicos del módulo si tiene método get_tab_data()
        if ($module && method_exists($module, 'get_tab_data')) {
            $module_data = $module->get_tab_data($tab_id, $tab_data['user_id']);
            if (is_array($module_data)) {
                $tab_data = array_merge($tab_data, $module_data);
            }
        }

        // Fallback: cargar datos para módulos específicos que no tienen get_tab_data()
        $tab_data = $this->load_module_tab_data($module_slug, $tab_id, $tab_data);

        foreach ($paths as $path) {
            if (file_exists($path)) {
                extract($tab_data);
                include $path;
                return;
            }
        }

        // FALLBACK DINÁMICO: Usar Archive Renderer si no hay template físico
        if (!class_exists('Flavor_Archive_Renderer')) {
            require_once FLAVOR_CHAT_IA_PATH . 'includes/class-archive-renderer.php';
        }

        $renderer = new Flavor_Archive_Renderer();

        // Determinar tipo de renderizado basado en nombre del template
        if (in_array($template_name, ['archive.php', 'listado.php', 'catalogo.php', 'grid.php'])) {
            echo $renderer->render_auto($module_slug);
            return;
        }

        if (in_array($template_name, ['single.php', 'detalle.php', 'ver.php'])) {
            $item_id = isset($_GET['id']) ? intval($_GET['id']) : 0;
            echo $renderer->render_single_auto($module_slug, $item_id);
            return;
        }

        // Templates tipo mis-* (filtrados por usuario)
        if (strpos($template_name, 'mis-') === 0 || strpos($template_name, 'mis_') === 0) {
            echo $renderer->render_auto($module_slug, ['user_id' => get_current_user_id()]);
            return;
        }

        // Intentar renderizar con Archive Renderer como último recurso
        echo $renderer->render_auto($module_slug);
    }

    /**
     * Renderiza contenido de fallback para tabs sin shortcode/template válido
     *
     * @param string $tab_id    ID del tab
     * @param string $module_id ID del módulo
     */
    private function render_tab_fallback($tab_id, $module_id) {
        $module_slug = str_replace('_', '-', $module_id);
        $module_slug_underscore = str_replace('-', '_', $module_slug);
        $tab_id_underscore = str_replace('-', '_', $tab_id);

        // PRIMERO: Intentar método render específico de esta clase (ej: render_tab_socios_beneficios)
        $method_name = 'render_tab_' . $module_slug_underscore . '_' . $tab_id_underscore;
        if (method_exists($this, $method_name)) {
            $this->$method_name();
            return;
        }

        if (!class_exists('Flavor_Archive_Renderer')) {
            require_once FLAVOR_CHAT_IA_PATH . 'includes/class-archive-renderer.php';
        }

        $renderer = new Flavor_Archive_Renderer();

        // Determinar configuración basada en el tab_id
        $filter_config = [];

        // Tabs tipo "mis-*" filtran por usuario actual
        if (strpos($tab_id, 'mis-') === 0 || strpos($tab_id, 'mis_') === 0) {
            $filter_config['user_id'] = get_current_user_id();
        }

        // Tabs de creación/formularios muestran mensaje
        $form_tabs = ['nueva', 'nuevo', 'crear', 'registrar', 'publicar', 'subir', 'formulario'];
        foreach ($form_tabs as $form_tab) {
            if (strpos($tab_id, $form_tab) !== false) {
                ?>
                <div class="fmd-form-placeholder">
                    <div class="fmd-placeholder-icon">
                        <span class="dashicons dashicons-plus-alt"></span>
                    </div>
                    <h3><?php esc_html_e('Crear nuevo', 'flavor-chat-ia'); ?></h3>
                    <p><?php esc_html_e('Esta funcionalidad estará disponible próximamente.', 'flavor-chat-ia'); ?></p>
                </div>
                <style>
                .fmd-form-placeholder { text-align: center; padding: 3rem; background: var(--fmd-bg-secondary, #f8f9fa); border-radius: 12px; }
                .fmd-placeholder-icon { font-size: 48px; color: var(--fmd-primary, #007bff); margin-bottom: 1rem; }
                .fmd-placeholder-icon .dashicons { font-size: 48px; width: 48px; height: 48px; }
                </style>
                <?php
                return;
            }
        }

        // Fallback genérico: usar Archive Renderer
        echo $renderer->render_auto($module_slug, $filter_config);
    }

    /**
     * Carga datos específicos del módulo para templates de tabs
     *
     * Este método proporciona las variables necesarias para cada template
     * cuando el módulo no tiene su propio método get_tab_data().
     *
     * @param string $module_slug Slug del módulo (con guiones)
     * @param string $tab_id      ID de la pestaña
     * @param array  $tab_data    Datos base del tab
     * @return array Datos extendidos para el template
     */
    private function load_module_tab_data($module_slug, $tab_id, $tab_data) {
        global $wpdb;

        $user_id = $tab_data['user_id'] ?? get_current_user_id();

        switch ($module_slug) {
            case 'banco-tiempo':
                if ($tab_id === 'reputacion') {
                    $tab_data['reputacion'] = [
                        'nombre' => '',
                        'avatar' => '',
                        'nivel' => 1,
                        'estado_verificacion' => 'pendiente',
                        'puntos_confianza' => 0,
                        'rating_promedio' => 0,
                        'total_intercambios_completados' => 0,
                        'total_horas_dadas' => 0,
                        'total_horas_recibidas' => 0,
                        'rating_puntualidad' => 0,
                        'rating_calidad' => 0,
                        'rating_comunicacion' => 0,
                        'fecha_primer_intercambio' => null,
                        'badges' => [],
                    ];
                    $tab_data['badges_info'] = [];

                    if ($user_id && class_exists('Flavor_BT_Conciencia_Features')) {
                        $features = Flavor_BT_Conciencia_Features::get_instance();
                        $reputacion = $features->obtener_reputacion((int) $user_id);
                        if (is_array($reputacion)) {
                            $tab_data['reputacion'] = array_merge($tab_data['reputacion'], $reputacion);
                            $badges_info = [];
                            foreach (($reputacion['badges'] ?? []) as $badge_id) {
                                if (isset(Flavor_BT_Conciencia_Features::BADGES[$badge_id])) {
                                    $badges_info[] = Flavor_BT_Conciencia_Features::BADGES[$badge_id];
                                }
                            }
                            $tab_data['badges_info'] = $badges_info;
                        }
                    } elseif ($user_id) {
                        $usuario = get_userdata($user_id);
                        if ($usuario) {
                            $tab_data['reputacion']['nombre'] = $usuario->display_name;
                            $tab_data['reputacion']['avatar'] = get_avatar_url($user_id, ['size' => 96]);
                        }
                    }
                }
                break;

            case 'presupuestos-participativos':
                // Nombres correctos de tablas según frontend controller
                $tabla_procesos = $wpdb->prefix . 'flavor_pp_procesos';
                $tabla_propuestas = $wpdb->prefix . 'flavor_pp_propuestas';
                $tabla_votos = $wpdb->prefix . 'flavor_pp_votos';
                $tabla_categorias = $wpdb->prefix . 'flavor_pp_categorias';

                // Obtener proceso activo (en votación o propuestas)
                $proceso = $wpdb->get_row(
                    "SELECT * FROM {$tabla_procesos}
                     WHERE estado IN ('votacion', 'propuestas')
                     ORDER BY fecha_inicio DESC
                     LIMIT 1",
                    ARRAY_A
                );

                if (!$proceso) {
                    // Si no hay activo, obtener el más reciente
                    $proceso = $wpdb->get_row(
                        "SELECT * FROM {$tabla_procesos}
                         ORDER BY fecha_inicio DESC
                         LIMIT 1",
                        ARRAY_A
                    );
                }

                $proceso_id = $proceso['id'] ?? 0;
                $fase = $proceso['estado'] ?? 'cerrado';

                // Datos comunes - usar 'edicion' como alias para compatibilidad con templates
                $tab_data['edicion'] = $proceso;
                $tab_data['proceso'] = $proceso;
                $tab_data['fase'] = $fase;
                $tab_data['identificador_usuario'] = $user_id;

                // Obtener categorías de la BD
                $categorias_bd = $wpdb->get_results(
                    "SELECT id, nombre, slug FROM {$tabla_categorias} ORDER BY nombre",
                    ARRAY_A
                );
                $categorias = [];
                foreach ($categorias_bd as $cat) {
                    $categorias[$cat['slug'] ?? $cat['id']] = $cat['nombre'];
                }
                if (empty($categorias)) {
                    // Fallback si no hay categorías en BD
                    $categorias = [
                        'infraestructura' => __('Infraestructura', 'flavor-chat-ia'),
                        'medioambiente'   => __('Medio Ambiente', 'flavor-chat-ia'),
                        'cultura'         => __('Cultura', 'flavor-chat-ia'),
                        'social'          => __('Social', 'flavor-chat-ia'),
                        'otro'            => __('Otro', 'flavor-chat-ia'),
                    ];
                }
                $tab_data['categorias'] = $categorias;

                switch ($tab_id) {
                    case 'votar':
                    case 'votacion':
                    case 'votaciones':
                    case 'interfaz-votacion':
                        // Propuestas en fase de votación
                        $proyectos = [];
                        if ($proceso_id) {
                            $proyectos = $wpdb->get_results($wpdb->prepare(
                                "SELECT p.*, COALESCE(p.votos_total, 0) as votos_recibidos
                                 FROM {$tabla_propuestas} p
                                 WHERE p.proceso_id = %d
                                   AND p.estado = 'en_votacion'
                                 ORDER BY votos_recibidos DESC, p.titulo ASC",
                                $proceso_id
                            ), ARRAY_A);
                        }

                        // Votos del usuario actual
                        $votos_usuario_rows = [];
                        if ($user_id && $proceso_id) {
                            $votos_usuario_rows = $wpdb->get_col($wpdb->prepare(
                                "SELECT propuesta_id FROM {$tabla_votos}
                                 WHERE usuario_id = %d AND proceso_id = %d",
                                $user_id,
                                $proceso_id
                            ));
                        }

                        // Configuración de votos
                        $votos_maximos = intval($proceso['votos_por_ciudadano'] ?? 3);
                        $votos_usados = count($votos_usuario_rows);

                        $tab_data['proyectos'] = $proyectos;
                        $tab_data['votos_usuario'] = $votos_usuario_rows;
                        $tab_data['votos_maximos'] = $votos_maximos;
                        $tab_data['votos_restantes'] = max(0, $votos_maximos - $votos_usados);
                        $tab_data['edicion'] = $proceso;
                        $tab_data['identificador_usuario'] = $user_id;
                        break;

                    case 'resultados':
                        // Ranking de propuestas por votos
                        $proyectos_ranking = [];
                        if ($proceso_id) {
                            $proyectos_ranking = $wpdb->get_results($wpdb->prepare(
                                "SELECT p.*, COALESCE(p.votos_total, 0) as total_votos
                                 FROM {$tabla_propuestas} p
                                 WHERE p.proceso_id = %d
                                   AND p.estado NOT IN ('borrador', 'rechazada')
                                 ORDER BY total_votos DESC, p.titulo ASC",
                                $proceso_id
                            ), ARRAY_A);
                        }

                        // Total de votantes únicos
                        $total_votantes = 0;
                        if ($proceso_id) {
                            $total_votantes = intval($wpdb->get_var($wpdb->prepare(
                                "SELECT COUNT(DISTINCT usuario_id) FROM {$tabla_votos}
                                 WHERE proceso_id = %d",
                                $proceso_id
                            )));
                        }

                        // Total de propuestas
                        $total_proyectos = count($proyectos_ranking);

                        $tab_data['proyectos_ranking'] = $proyectos_ranking;
                        $tab_data['total_votantes'] = $total_votantes;
                        $tab_data['total_proyectos'] = $total_proyectos;
                        $tab_data['edicion'] = $proceso;
                        break;

                    case 'proponer':
                    case 'formulario-propuesta':
                        // Datos para el formulario de propuesta
                        $tab_data['presupuesto_minimo'] = floatval($proceso['presupuesto_minimo'] ?? 100);
                        $tab_data['presupuesto_maximo'] = floatval($proceso['presupuesto_maximo'] ?? $proceso['presupuesto_total'] ?? 50000);
                        $tab_data['fase_actual'] = $fase;
                        $tab_data['puede_proponer'] = ($fase === 'propuestas');
                        $tab_data['proceso_id'] = $proceso_id;
                        $tab_data['proceso'] = $proceso;
                        $tab_data['edicion'] = $proceso; // Alias para compatibilidad
                        break;

                    case 'proyectos':
                    case 'listado':
                    case 'listado-proyectos':
                        // Todas las propuestas del proceso
                        $proyectos = [];
                        if ($proceso_id) {
                            $proyectos = $wpdb->get_results($wpdb->prepare(
                                "SELECT p.*, COALESCE(p.votos_total, 0) as votos_recibidos
                                 FROM {$tabla_propuestas} p
                                 WHERE p.proceso_id = %d
                                   AND p.estado NOT IN ('borrador', 'rechazada')
                                 ORDER BY p.created_at DESC",
                                $proceso_id
                            ), ARRAY_A);
                        }
                        $tab_data['proyectos'] = $proyectos;

                        // Votos del usuario actual para mostrar info de votación
                        $votos_usuario_ids = [];
                        if ($user_id && $proceso_id) {
                            $votos_usuario_ids = $wpdb->get_col($wpdb->prepare(
                                "SELECT propuesta_id FROM {$tabla_votos}
                                 WHERE usuario_id = %d AND proceso_id = %d",
                                $user_id,
                                $proceso_id
                            ));
                        }
                        $tab_data['votos_usuario'] = $votos_usuario_ids;
                        $tab_data['votos_maximos'] = intval($proceso['votos_por_ciudadano'] ?? 3);
                        $tab_data['identificador_usuario'] = $user_id;
                        break;

                    case 'fases':
                    case 'seguimiento':
                    case 'dashboard':
                        // Estadísticas para el dashboard
                        $stats = [
                            'total_proyectos' => 0,
                            'en_votacion' => 0,
                            'aprobadas' => 0,
                            'en_ejecucion' => 0,
                            'ejecutadas' => 0,
                            'mis_propuestas' => 0,
                        ];

                        if ($proceso_id) {
                            $stats['total_proyectos'] = intval($wpdb->get_var($wpdb->prepare(
                                "SELECT COUNT(*) FROM {$tabla_propuestas} WHERE proceso_id = %d AND estado != 'borrador'",
                                $proceso_id
                            )));
                            $stats['en_votacion'] = intval($wpdb->get_var($wpdb->prepare(
                                "SELECT COUNT(*) FROM {$tabla_propuestas} WHERE proceso_id = %d AND estado = 'en_votacion'",
                                $proceso_id
                            )));
                            $stats['aprobadas'] = intval($wpdb->get_var($wpdb->prepare(
                                "SELECT COUNT(*) FROM {$tabla_propuestas} WHERE proceso_id = %d AND estado = 'aprobada'",
                                $proceso_id
                            )));
                            $stats['en_ejecucion'] = intval($wpdb->get_var($wpdb->prepare(
                                "SELECT COUNT(*) FROM {$tabla_propuestas} WHERE proceso_id = %d AND estado = 'en_ejecucion'",
                                $proceso_id
                            )));
                            $stats['ejecutadas'] = intval($wpdb->get_var($wpdb->prepare(
                                "SELECT COUNT(*) FROM {$tabla_propuestas} WHERE proceso_id = %d AND estado = 'ejecutada'",
                                $proceso_id
                            )));
                            if ($user_id) {
                                $stats['mis_propuestas'] = intval($wpdb->get_var($wpdb->prepare(
                                    "SELECT COUNT(*) FROM {$tabla_propuestas} WHERE proceso_id = %d AND usuario_id = %d",
                                    $proceso_id,
                                    $user_id
                                )));
                            }
                        }

                        // Propuestas aprobadas/en ejecución para seguimiento
                        $proyectos_seleccionados = [];
                        if ($proceso_id) {
                            $proyectos_seleccionados = $wpdb->get_results($wpdb->prepare(
                                "SELECT * FROM {$tabla_propuestas}
                                 WHERE proceso_id = %d
                                   AND estado IN ('aprobada', 'en_ejecucion', 'ejecutada')
                                 ORDER BY FIELD(estado, 'en_ejecucion', 'aprobada', 'ejecutada'), titulo ASC",
                                $proceso_id
                            ), ARRAY_A);
                        }

                        $tab_data['stats'] = $stats;
                        $tab_data['proyectos_seleccionados'] = $proyectos_seleccionados;
                        $tab_data['fases'] = [
                            'propuestas' => __('Recepción de propuestas', 'flavor-chat-ia'),
                            'evaluacion' => __('Evaluación técnica', 'flavor-chat-ia'),
                            'votacion'   => __('Votación ciudadana', 'flavor-chat-ia'),
                            'ejecucion'  => __('Ejecución de proyectos', 'flavor-chat-ia'),
                            'cerrado'    => __('Proceso cerrado', 'flavor-chat-ia'),
                        ];
                        break;

                    case 'mis-propuestas':
                        // Propuestas del usuario actual
                        $propuestas_usuario = [];
                        if ($user_id && $proceso_id) {
                            $propuestas_usuario = $wpdb->get_results($wpdb->prepare(
                                "SELECT p.*, COALESCE(p.votos_total, 0) as votos_recibidos,
                                        p.created_at as fecha_creacion
                                 FROM {$tabla_propuestas} p
                                 WHERE p.usuario_id = %d
                                   AND p.proceso_id = %d
                                 ORDER BY p.created_at DESC",
                                $user_id,
                                $proceso_id
                            ), ARRAY_A);
                        }
                        $tab_data['propuestas'] = $propuestas_usuario;
                        $tab_data['mis_propuestas'] = $propuestas_usuario; // Alias
                        break;
                }
                break;

            // Otros módulos pueden añadirse aquí siguiendo el mismo patrón
            default:
                // Para módulos sin datos específicos, intentar cargar datos genéricos
                break;
        }

        return $tab_data;
    }

    /**
     * Sistema legacy de renderizado de tabs
     *
     * @param string $tab_id    ID del tab
     * @param array  $tab_info  Información del tab
     * @param object $module    Instancia del módulo
     */
    private function render_tab_content_legacy($tab_id, $tab_info, $module) {
        // Si es una tab de integración con source_module, usar ese módulo
        if (!empty($tab_info['is_integration']) && !empty($tab_info['source_module'])) {
            $source_module = $tab_info['source_module'];
            $contextual_shortcode = $this->get_contextual_integration_shortcode($source_module);
            ?>
            <div class="fmd-tab-integration" data-source-module="<?php echo esc_attr($source_module); ?>">
                <?php
                if ($contextual_shortcode !== null) {
                    do_action('flavor_rendering_tab_integrado', $source_module, $this->current_module, $this->current_item_id);
                    $this->ensure_integration_assets_loaded($source_module);
                    echo do_shortcode($contextual_shortcode);
                    ?>
                    </div>
                    <?php
                    return;
                }

                // Usar el shortcode unificado [flavor] con el módulo fuente
                echo do_shortcode('[flavor module="' . esc_attr($source_module) . '" view="listado" header="no" limit="12"]');
                ?>
            </div>
            <?php
            return;
        }

        switch ($tab_id) {
            case 'listado':
            case 'todos':
            case 'todas':
                $this->render_tab_listado();
                break;

            case 'actividad':
                $this->render_module_activity();
                break;

            case 'mensajes':
                $this->render_module_messages();
                break;

            case 'calendario':
                $this->render_module_calendar();
                break;

            case 'mapa':
                $this->render_tab_mapa();
                break;

            case 'mis-reservas':
            case 'mis-pedidos':
            case 'mis-reportes':
            case 'inscripciones':
                $this->render_tab_mis_elementos($tab_id);
                break;

            case 'pedidos':
            case 'productos':
            case 'productores':
            case 'espacios':
            case 'proximos':
            case 'ciclos':
                $this->render_tab_shortcode($tab_id);
                break;

            case 'crear':
                if ($this->current_module === 'comunidades') {
                    echo do_shortcode('[comunidades_crear]');
                } else {
                    $this->render_tab_fallback($tab_id, $this->current_module);
                }
                break;

            // === BANCO DE TIEMPO - Tabs específicos ===
            case 'mi-saldo':
            case 'servicios':
            case 'intercambios':
                $this->render_tab_banco_tiempo($tab_id);
                break;

            // === FICHAJE EMPLEADOS - Tabs específicos ===
            case 'fichaje-panel':
                $this->render_tab_fichaje_panel();
                break;

            case 'fichaje-historial':
                $this->render_tab_fichaje_historial();
                break;

            case 'fichaje-resumen':
                $this->render_tab_fichaje_resumen();
                break;

            // === SOCIOS - Tabs específicos ===
            case 'mi-membresia':
                $this->render_tab_socios_membresia();
                break;

            case 'cuotas':
                $this->render_tab_socios_cuotas();
                break;

            case 'beneficios':
                $this->render_tab_socios_beneficios();
                break;

            case 'carnet':
                $this->render_tab_socios_carnet();
                break;

            case 'historial':
                $this->render_tab_socios_historial();
                break;

            case 'directorio':
                $this->render_tab_socios_directorio();
                break;

            // === FOROS - Tabs específicos ===
            case 'temas-recientes':
                $this->render_tab_foros_temas_recientes();
                break;

            case 'mis-hilos':
                $this->render_tab_foros_mis_hilos();
                break;

            case 'nuevo-tema':
                $this->render_tab_foros_nuevo_tema();
                break;

            case 'categorias':
                if ($this->current_module === 'foros') {
                    $this->render_tab_foros_categorias();
                } else {
                    // Fallback para otros módulos con tab de categorías
                    echo do_shortcode('[flavor module="' . esc_attr(str_replace('_', '-', $this->current_module)) . '" view="categorias" header="no"]');
                }
                break;

            // === BARES - Tabs específicos ===
            case 'reservar':
                if ($this->current_module === 'bares') {
                    echo do_shortcode('[bares_reservar]');
                }
                break;

            default:
                // Fallback genérico - usar shortcode unificado [flavor]
                $module_id = str_replace('_', '-', $this->current_module);
                ?>
                <div class="fmd-tab-generic">
                    <?php
                    // Usar shortcode unificado [flavor] como fallback
                    echo do_shortcode('[flavor module="' . esc_attr($module_id) . '" view="listado" header="no" limit="12"]');
                    ?>
                </div>
                <?php
        }
    }

    /**
     * Devuelve un shortcode contextual para integraciones que dependen de una entidad actual.
     *
     * `null` significa seguir con el flujo genérico; una cadena devuelve el shortcode a renderizar.
     *
     * @param string $source_module
     * @return string|null
     */
    private function get_contextual_integration_shortcode($source_module) {
        $source_module = str_replace('_', '-', sanitize_key((string) $source_module));

        if ($source_module === 'chat-interno') {
            return '[chat_interno_conversaciones]';
        }

        $entity_type = $this->get_contextual_integration_entity_type();
        $entity_id = absint($this->current_item_id);

        if ($source_module === 'chat-grupos') {
            if (!$entity_type) {
                return null;
            }

            if ($entity_id <= 0) {
                $overview_shortcode = $this->get_contextual_integration_overview_shortcode($source_module);
                if ($overview_shortcode) {
                    return $overview_shortcode;
                }

                return $this->get_empty_state_shortcode(
                    __('Chat contextual', 'flavor-chat-ia'),
                    __('Accede a una comunidad, evento o elemento concreto para ver su chat asociado.', 'flavor-chat-ia'),
                    '💬'
                );
            }

            return sprintf(
                '[flavor_chat_grupo_integrado entidad="%s" entidad_id="%d"]',
                esc_attr($entity_type),
                $entity_id
            );
        }

        if ($source_module === 'multimedia') {
            if (!$entity_type) {
                return null;
            }

            if ($entity_id <= 0) {
                $overview_shortcode = $this->get_contextual_integration_overview_shortcode($source_module);
                if ($overview_shortcode) {
                    return $overview_shortcode;
                }

                return $this->get_empty_state_shortcode(
                    __('Galería contextual', 'flavor-chat-ia'),
                    __('Accede a una comunidad, evento o elemento concreto para ver su contenido multimedia asociado.', 'flavor-chat-ia'),
                    '🖼️'
                );
            }

            return sprintf(
                '[flavor_multimedia_galeria entidad="%s" entidad_id="%d"]',
                esc_attr($entity_type),
                $entity_id
            );
        }

        if ($source_module === 'recetas') {
            $overview_shortcode = $this->get_contextual_integration_overview_shortcode($source_module);
            if ($overview_shortcode) {
                return $overview_shortcode;
            }

            return null;
        }

        if ($source_module === 'red-social') {
            if (!$entity_type) {
                return null;
            }

            if ($entity_id <= 0) {
                $overview_shortcode = $this->get_contextual_integration_overview_shortcode($source_module);
                if ($overview_shortcode) {
                    return $overview_shortcode;
                }

                return $this->get_empty_state_shortcode(
                    __('Feed contextual', 'flavor-chat-ia'),
                    __('Accede a una comunidad, evento o elemento concreto para ver su actividad social asociada.', 'flavor-chat-ia'),
                    '📰'
                );
            }

            return sprintf(
                '[flavor_social_feed entidad="%s" entidad_id="%d"]',
                esc_attr($entity_type),
                $entity_id
            );
        }

        if ($source_module !== 'foros') {
            return null;
        }

        if (!$entity_type) {
            return null;
        }

        if ($entity_id > 0) {
            return sprintf(
                '[flavor_foros_integrado entidad="%s" entidad_id="%d"]',
                esc_attr($entity_type),
                $entity_id
            );
        }

        return $this->get_empty_state_shortcode(
            __('Foro contextual', 'flavor-chat-ia'),
            __('Accede a una comunidad, evento o elemento concreto para ver su foro asociado.', 'flavor-chat-ia'),
            '💬'
        );
    }

    /**
     * Devuelve un fallback útil para tabs de integración cuando estamos en la raíz
     * del módulo y todavía no existe un entity_id contextual.
     *
     * @param string $source_module
     * @return string|null
     */
    private function get_contextual_integration_overview_shortcode($source_module) {
        $source_module = str_replace('_', '-', sanitize_key((string) $source_module));
        $current_module = str_replace('_', '-', sanitize_key((string) $this->current_module));

        if (!in_array($current_module, ['comunidades', 'grupos-consumo'], true)) {
            return null;
        }

        if ($source_module === 'chat-grupos') {
            return '[chat_grupos_mis_grupos]';
        }

        if ($source_module === 'multimedia') {
            return '[flavor_multimedia_galeria limit="12"]';
        }

        if ($source_module === 'recetas') {
            return '[flavor module="recetas" view="listado" header="no" limit="12"]';
        }

        if ($source_module === 'red-social') {
            return is_user_logged_in() ? '[rs_feed]' : '[rs_explorar]';
        }

        return null;
    }

    /**
     * Intenta cargar e imprimir assets de integraciones dinámicas que no están
     * presentes en el contenido original del post.
     *
     * @param string $source_module
     * @return void
     */
    private function ensure_integration_assets_loaded($source_module) {
        $source_module = str_replace('_', '-', sanitize_key((string) $source_module));

        if ($source_module === 'chat-interno') {
            wp_enqueue_style(
                'flavor-chat-interno',
                FLAVOR_CHAT_IA_URL . 'includes/modules/chat-interno/assets/css/chat-interno.css',
                [],
                FLAVOR_CHAT_IA_VERSION
            );

            wp_enqueue_script(
                'flavor-chat-interno',
                FLAVOR_CHAT_IA_URL . 'includes/modules/chat-interno/assets/js/chat-interno.js',
                ['jquery'],
                FLAVOR_CHAT_IA_VERSION,
                true
            );

            wp_localize_script('flavor-chat-interno', 'flavorChatInterno', [
                'ajaxurl' => admin_url('admin-ajax.php'),
                'resturl' => rest_url('flavor/v1/chat-interno/'),
                'nonce' => wp_create_nonce('flavor_chat_interno'),
                'user_id' => get_current_user_id(),
                'user_name' => wp_get_current_user()->display_name,
                'user_avatar' => get_avatar_url(get_current_user_id(), ['size' => 48]),
                'polling_interval' => 3000,
                'typing_timeout' => 3000,
                'max_file_size' => 25 * 1024 * 1024,
                'allowed_types' => [
                    'image/jpeg',
                    'image/png',
                    'image/gif',
                    'image/webp',
                    'application/pdf',
                    'application/msword',
                    'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
                    'application/vnd.ms-excel',
                    'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                    'audio/mpeg',
                    'audio/wav',
                    'audio/ogg',
                    'audio/webm',
                ],
                'strings' => [
                    'escribiendo' => __('escribiendo...', 'flavor-chat-ia'),
                    'tu' => __('Tu', 'flavor-chat-ia'),
                    'ahora' => __('ahora', 'flavor-chat-ia'),
                    'ayer' => __('ayer', 'flavor-chat-ia'),
                    'mensaje_eliminado' => __('Mensaje eliminado', 'flavor-chat-ia'),
                    'mensaje_editado' => __('editado', 'flavor-chat-ia'),
                    'sin_mensajes' => __('No hay mensajes aun. Inicia la conversacion.', 'flavor-chat-ia'),
                    'cargando' => __('Cargando...', 'flavor-chat-ia'),
                    'error' => __('Error al procesar la solicitud', 'flavor-chat-ia'),
                    'usuario_bloqueado' => __('Has bloqueado a este usuario', 'flavor-chat-ia'),
                    'bloqueado_por' => __('Este usuario te ha bloqueado', 'flavor-chat-ia'),
                    'online' => __('En linea', 'flavor-chat-ia'),
                    'offline' => __('Desconectado', 'flavor-chat-ia'),
                    'visto' => __('Visto', 'flavor-chat-ia'),
                    'enviado' => __('Enviado', 'flavor-chat-ia'),
                    'archivo_grande' => __('El archivo es demasiado grande', 'flavor-chat-ia'),
                    'tipo_no_permitido' => __('Tipo de archivo no permitido', 'flavor-chat-ia'),
                    'confirmar_eliminar' => __('Eliminar este mensaje?', 'flavor-chat-ia'),
                    'confirmar_bloquear' => __('Bloquear a este usuario?', 'flavor-chat-ia'),
                    'nuevo_mensaje' => __('Nuevo mensaje', 'flavor-chat-ia'),
                ],
            ]);

            wp_print_styles('flavor-chat-interno');
            wp_print_scripts('flavor-chat-interno');
            return;
        }

        if ($source_module === 'chat-grupos') {
            wp_enqueue_style(
                'flavor-chat-grupos',
                FLAVOR_CHAT_IA_URL . 'includes/modules/chat-grupos/assets/css/chat-grupos.css',
                [],
                FLAVOR_CHAT_IA_VERSION
            );

            wp_enqueue_script(
                'flavor-chat-grupos',
                FLAVOR_CHAT_IA_URL . 'includes/modules/chat-grupos/assets/js/chat-grupos.js',
                ['jquery'],
                FLAVOR_CHAT_IA_VERSION,
                true
            );

            wp_localize_script('flavor-chat-grupos', 'flavorChatGrupos', [
                'ajaxurl' => admin_url('admin-ajax.php'),
                'resturl' => rest_url('flavor/v1/chat-grupos/'),
                'nonce' => wp_create_nonce('flavor_chat_grupos'),
                'user_id' => get_current_user_id(),
                'user_name' => wp_get_current_user()->display_name,
                'user_avatar' => get_avatar_url(get_current_user_id(), ['size' => 48]),
                'polling_interval' => 3000,
                'typing_timeout' => 3000,
                'strings' => [
                    'escribiendo' => __('escribiendo...', 'flavor-chat-ia'),
                    'tu' => __('Tú', 'flavor-chat-ia'),
                    'ahora' => __('ahora', 'flavor-chat-ia'),
                    'ayer' => __('ayer', 'flavor-chat-ia'),
                    'mensaje_eliminado' => __('Mensaje eliminado', 'flavor-chat-ia'),
                    'mensaje_editado' => __('editado', 'flavor-chat-ia'),
                    'sin_mensajes' => __('No hay mensajes aún. ¡Sé el primero en escribir!', 'flavor-chat-ia'),
                    'cargando' => __('Cargando...', 'flavor-chat-ia'),
                    'error' => __('Error al procesar la solicitud', 'flavor-chat-ia'),
                ],
            ]);

            wp_print_styles('flavor-chat-grupos');
            wp_print_scripts('flavor-chat-grupos');
            return;
        }

        if ($source_module === 'multimedia') {
            wp_enqueue_style(
                'flavor-multimedia-frontend',
                FLAVOR_CHAT_IA_URL . 'includes/modules/multimedia/assets/css/multimedia-frontend.css',
                [],
                FLAVOR_CHAT_IA_VERSION
            );

            wp_enqueue_script(
                'flavor-multimedia-frontend',
                FLAVOR_CHAT_IA_URL . 'includes/modules/multimedia/assets/js/multimedia-frontend.js',
                ['jquery'],
                FLAVOR_CHAT_IA_VERSION,
                true
            );

            $multimedia_config = [
                'ajaxurl' => admin_url('admin-ajax.php'),
                'ajaxUrl' => admin_url('admin-ajax.php'),
                'resturl' => rest_url('flavor/v1/multimedia/'),
                'restUrl' => rest_url('flavor/v1/multimedia/'),
                'nonce' => wp_create_nonce('flavor_multimedia_nonce'),
                'user_id' => get_current_user_id(),
                'userId' => get_current_user_id(),
                'maxUploadSize' => wp_max_upload_size(),
                'allowedTypes' => ['image/jpeg', 'image/png', 'image/gif', 'image/webp', 'video/mp4', 'audio/mpeg'],
                'strings' => [
                    'loading' => __('Cargando...', 'flavor-chat-ia'),
                    'subiendo' => __('Subiendo...', 'flavor-chat-ia'),
                    'subido' => __('Archivo subido correctamente', 'flavor-chat-ia'),
                    'error' => __('Error al procesar', 'flavor-chat-ia'),
                    'no_results' => __('No se encontraron resultados', 'flavor-chat-ia'),
                    'confirm_delete' => __('¿Eliminar este archivo?', 'flavor-chat-ia'),
                    'uploading' => __('Subiendo...', 'flavor-chat-ia'),
                    'upload_success' => __('Archivo subido correctamente', 'flavor-chat-ia'),
                    'upload_error' => __('Error al subir archivo', 'flavor-chat-ia'),
                    'eliminado' => __('Archivo eliminado', 'flavor-chat-ia'),
                    'sin_archivos' => __('No hay archivos', 'flavor-chat-ia'),
                    'arrastra_aqui' => __('Arrastra archivos aquí o haz clic', 'flavor-chat-ia'),
                    'archivo_grande' => __('El archivo es demasiado grande', 'flavor-chat-ia'),
                    'tipo_no_permitido' => __('Tipo de archivo no permitido', 'flavor-chat-ia'),
                ],
            ];

            wp_localize_script('flavor-multimedia-frontend', 'flavorMultimediaConfig', $multimedia_config);
            wp_localize_script('flavor-multimedia-frontend', 'flavorMultimedia', $multimedia_config);

            wp_print_styles('flavor-multimedia-frontend');
            wp_print_scripts('flavor-multimedia-frontend');
            return;
        }

        if ($source_module === 'red-social') {
            wp_enqueue_style(
                'flavor-red-social',
                FLAVOR_CHAT_IA_URL . 'includes/modules/red-social/assets/css/red-social.css',
                [],
                FLAVOR_CHAT_IA_VERSION
            );

            wp_enqueue_script(
                'flavor-red-social',
                FLAVOR_CHAT_IA_URL . 'includes/modules/red-social/assets/js/red-social.js',
                ['jquery'],
                FLAVOR_CHAT_IA_VERSION,
                true
            );

            wp_localize_script('flavor-red-social', 'flavorRedSocial', [
                'ajaxUrl' => admin_url('admin-ajax.php'),
                'nonce' => wp_create_nonce('rs_nonce'),
                'userId' => get_current_user_id(),
                'maxCaracteres' => 5000,
                'maxImagenes' => 10,
            ]);

            wp_print_styles('flavor-red-social');
            wp_print_scripts('flavor-red-social');
        }
    }

    /**
     * Mapea el módulo actual al tipo de entidad usado por foros integrados.
     *
     * @return string
     */
    private function get_foros_integration_entity_type() {
        return $this->get_contextual_integration_entity_type();
    }

    /**
     * Mapea el módulo actual al tipo de entidad usado por integraciones contextuales.
     *
     * @return string
     */
    private function get_contextual_integration_entity_type() {
        $module_slug = str_replace('-', '_', sanitize_key((string) $this->current_module));

        $map = [
            'comunidades' => 'comunidad',
            'eventos' => 'evento',
            'grupos_consumo' => 'grupo_consumo',
            'cursos' => 'curso',
            'colectivos' => 'colectivo',
            'circulos_cuidados' => 'circulo',
            'banco_tiempo' => 'servicio_bt',
            'huertos_urbanos' => 'huerto',
        ];

        return $map[$module_slug] ?? '';
    }

    /**
     * Genera un shortcode de estado vacío reutilizable.
     *
     * @param string $title
     * @param string $message
     * @param string $icon
     * @return string
     */
    private function get_empty_state_shortcode($title, $message, $icon = 'ℹ️') {
        return sprintf(
            '<div class="flavor-empty-state bg-gray-50 rounded-xl p-8 text-center"><span class="text-5xl mb-4 block">%s</span><h3 class="text-lg font-semibold text-gray-900 mb-2">%s</h3><p class="text-gray-500 mb-4">%s</p></div>',
            esc_html($icon),
            esc_html($title),
            esc_html($message)
        );
    }

    /**
     * Renderiza tab de listado usando Archive Renderer dinámico
     */
    private function render_tab_listado() {
        $module_slug = str_replace('_', '-', $this->current_module);

        // Marketplace usa un frontend controller más reciente que el archive renderer legacy.
        if ($module_slug === 'marketplace') {
            $archivo_controller = FLAVOR_CHAT_IA_PATH . 'includes/modules/marketplace/frontend/class-marketplace-frontend-controller.php';
            if (!class_exists('Flavor_Marketplace_Frontend_Controller') && file_exists($archivo_controller)) {
                require_once $archivo_controller;
            }
            if (class_exists('Flavor_Marketplace_Frontend_Controller')) {
                echo Flavor_Marketplace_Frontend_Controller::get_instance()->shortcode_catalogo([
                    'limite' => 12,
                    'mostrar_filtros' => 'si',
                ]);
                return;
            }
        }

        // Usar Archive Renderer dinámico
        if (!class_exists('Flavor_Archive_Renderer')) {
            require_once FLAVOR_CHAT_IA_PATH . 'includes/class-archive-renderer.php';
        }

        $renderer = new Flavor_Archive_Renderer();
        echo $renderer->render_auto($module_slug, [
            'per_page' => 12,
            'show_header' => false, // El header ya está en el dashboard
        ]);
    }

    /**
     * Renderiza tab de mapa
     */
    private function render_tab_mapa() {
        $module_id = str_replace('_', '-', $this->current_module);

        // Mapeo de módulo a shortcode de mapa real
        $mapas = [
            'grupos-consumo'       => '[gc_productores_cercanos]',
            'huertos-urbanos'      => '[mapa_huertos]',
            'parkings'             => '[flavor_mapa_parkings]',
            'incidencias'          => '[incidencias_mapa]',
            'compostaje'           => '[mapa_composteras]',
            'reciclaje'            => '[reciclaje_puntos_cercanos]',
            'comunidades'          => '[flavor_network_map]',
            'espacios-comunes'     => '[espacios_listado vista="mapa"]',
            'bicicletas-compartidas' => '[flavor_module_listing module="bicicletas-compartidas" vista="mapa"]',
            'eventos'              => '[flavor_module_listing module="eventos" vista="mapa"]',
            'bares'                => '[flavor_module_listing module="bares" vista="mapa"]',
            'ayuda-vecinal'        => '[flavor_module_listing module="ayuda-vecinal" vista="mapa"]',
        ];
        ?>
        <div class="fmd-panel-content fmd-map-container">
            <?php
            $shortcode = $mapas[$module_id] ?? '[flavor_module_listing module="' . esc_attr($module_id) . '" vista="mapa"]';
            echo do_shortcode($shortcode);
            ?>
        </div>
        <?php
    }

    /**
     * Renderiza tab de "mis elementos"
     */
    private function render_tab_mis_elementos($tab_id) {
        $usuario_id = get_current_user_id();
        $module_id = str_replace('_', '-', $this->current_module);
        ?>
        <div class="fmd-panel-content">
            <?php
            // Mapeo de tab_id a shortcode real por módulo
            $shortcodes_por_modulo = [
                'reservas' => [
                    'mis-reservas' => '[espacios_mis_reservas]',
                ],
                'espacios-comunes' => [
                    'mis-reservas' => '[espacios_mis_reservas]',
                ],
                'grupos-consumo' => [
                    'mis-pedidos' => '[gc_mi_pedido]',
                ],
                'incidencias' => [
                    'mis-reportes' => '[incidencias_mis_incidencias]',
                ],
                'eventos' => [
                    'inscripciones' => '[flavor_eventos_acciones]',
                ],
                'talleres' => [
                    'inscripciones' => '[mis_inscripciones_talleres]',
                ],
                'cursos' => [
                    'mis-cursos' => '[cursos_mis_cursos]',
                ],
                'biblioteca' => [
                    'mis-prestamos' => '[biblioteca_mis_prestamos]',
                ],
                'carpooling' => [
                    'mis-viajes' => '[carpooling_mis_viajes]',
                    'mis-reservas' => '[carpooling_mis_reservas]',
                ],
                'parkings' => [
                    'reservas' => '[flavor_mis_reservas_parking]',
                ],
            ];

            // Buscar shortcode específico para este módulo y tab
            $shortcode = $shortcodes_por_modulo[$module_id][$tab_id] ?? null;

            // Fallback genérico
            if (!$shortcode) {
                $shortcode = '[flavor_module_listing module="' . esc_attr($module_id) . '" vista="mis" limit="10"]';
            }

            echo do_shortcode($shortcode);
            ?>
        </div>
        <?php
    }

    /**
     * Renderiza tab genérico con shortcode
     */
    private function render_tab_shortcode($tab_id) {
        $module_id = str_replace('_', '-', $this->current_module);
        ?>
        <div class="fmd-panel-content">
            <?php
            // Mapeo de tab_id a shortcode específico por módulo
            $shortcodes_por_modulo = [
                'grupos-consumo' => [
                    'pedidos'     => '[gc_historial]',
                    'productos'   => '[gc_productos]',
                    'productores' => '[gc_productores_cercanos]',
                    'ciclos'      => '[gc_ciclo_actual]',
                ],
                'eventos' => [
                    'proximos'    => '[flavor_module_listing module="eventos" limit="6"]',
                    'calendario'  => '[flavor_module_listing module="eventos" vista="calendario"]',
                ],
                'espacios-comunes' => [
                    'disponibles' => '[espacios_listado]',
                ],
                'reservas' => [
                    'espacios'    => '[espacios_listado]',
                ],
                'talleres' => [
                    'proximos'    => '[proximos_talleres]',
                ],
                'cursos' => [
                    'catalogo'    => '[cursos_catalogo]',
                ],
                'biblioteca' => [
                    'catalogo'    => '[biblioteca_catalogo]',
                    'novedades'   => '[biblioteca_catalogo orden="recientes"]',
                ],
                'participacion' => [
                    'propuestas'  => '[propuestas_activas]',
                    'votaciones'  => '[votacion_activa]',
                ],
                'podcast' => [
                    'episodios'   => '[podcast_lista_episodios]',
                    'series'      => '[podcast_series]',
                ],
                'radio' => [
                    'directo'     => '[flavor_radio_player]',
                    'programacion'=> '[flavor_radio_programacion]',
                ],
                'chat-grupos' => [
                    'explorar'    => '[flavor_grupos_explorar]',
                ],
                'red-social' => [
                    'feed'        => '[rs_feed]',
                    'perfil'      => '[rs_perfil]',
                ],
            ];

            // Buscar shortcode específico
            $shortcode = $shortcodes_por_modulo[$module_id][$tab_id] ?? null;

            // Fallback genérico
            if (!$shortcode) {
                $shortcode = '[flavor_module_listing module="' . esc_attr($module_id) . '" limit="12"]';
            }

            echo do_shortcode($shortcode);
            ?>
        </div>
        <?php
    }

    /**
     * Renderiza tabs específicos de Banco de Tiempo
     *
     * @param string $tab_id ID del tab (mi-saldo, servicios, intercambios)
     */
    private function render_tab_banco_tiempo($tab_id) {
        $shortcodes = [
            'servicios' => '[banco_tiempo_servicios mostrar_propios="0"]',
            'mi-saldo' => '[banco_tiempo_mi_saldo]',
            'intercambios' => '[banco_tiempo_mis_intercambios]',
            'ranking' => '[banco_tiempo_ranking]',
            'ofrecer' => '[banco_tiempo_ofrecer]',
            'buscar' => '[banco_tiempo_servicios mostrar_propios="0"]',
        ];

        if (isset($shortcodes[$tab_id])) {
            echo do_shortcode($shortcodes[$tab_id]);
            return;
        }

        if ($tab_id === 'reputacion') {
            $template_path = FLAVOR_CHAT_IA_PATH . 'includes/modules/banco-tiempo/templates/mi-reputacion.php';
            if (file_exists($template_path)) {
                $tab_data = $this->load_module_tab_data('banco-tiempo', 'reputacion', [
                    'user_id' => get_current_user_id(),
                ]);
                $reputacion = $tab_data['reputacion'] ?? [];
                $badges_info = $tab_data['badges_info'] ?? [];
                include $template_path;
                return;
            }
        }

        echo '<div class="fmd-panel-content">';
        echo do_shortcode('[flavor_module_listing module="banco-tiempo" vista="' . esc_attr($tab_id) . '" limit="12"]');
        echo '</div>';
    }

    /**
     * Renderiza el panel principal de fichaje
     */
    private function render_tab_fichaje_panel() {
        if (!is_user_logged_in()) {
            echo '<div class="fmd-login-required">';
            echo '<p>' . esc_html__('Debes iniciar sesión para fichar.', 'flavor-chat-ia') . '</p>';
            echo '</div>';
            return;
        }

        $user_id = get_current_user_id();
        global $wpdb;
        $tabla_fichajes = $wpdb->prefix . 'flavor_fichajes';

        // Obtener estado actual del empleado
        $ultimo_fichaje = null;
        $fichajes_hoy = [];
        $horas_trabajadas = 0;

        if (Flavor_Chat_Helpers::tabla_existe($tabla_fichajes)) {
            // Último fichaje del usuario
            $ultimo_fichaje = $wpdb->get_row($wpdb->prepare(
                "SELECT * FROM {$tabla_fichajes} WHERE user_id = %d ORDER BY fecha DESC, hora DESC LIMIT 1",
                $user_id
            ));

            // Fichajes de hoy
            $fichajes_hoy = $wpdb->get_results($wpdb->prepare(
                "SELECT * FROM {$tabla_fichajes} WHERE user_id = %d AND DATE(fecha) = CURDATE() ORDER BY hora ASC",
                $user_id
            ), ARRAY_A);

            // Calcular horas trabajadas hoy
            $entrada_actual = null;
            foreach ($fichajes_hoy as $fichaje) {
                if ($fichaje['tipo'] === 'entrada') {
                    $entrada_actual = strtotime($fichaje['hora']);
                } elseif ($fichaje['tipo'] === 'salida' && $entrada_actual) {
                    $horas_trabajadas += (strtotime($fichaje['hora']) - $entrada_actual) / 3600;
                    $entrada_actual = null;
                }
            }
            // Si hay entrada sin salida, calcular hasta ahora
            if ($entrada_actual) {
                $horas_trabajadas += (current_time('timestamp') - strtotime(date('Y-m-d') . ' ' . date('H:i:s', $entrada_actual))) / 3600;
            }
        }

        // Determinar estado actual
        $estado_actual = 'sin_fichar';
        if ($ultimo_fichaje) {
            switch ($ultimo_fichaje->tipo) {
                case 'entrada':
                    $estado_actual = 'trabajando';
                    break;
                case 'salida':
                    $estado_actual = 'fuera';
                    break;
                case 'pausa_inicio':
                    $estado_actual = 'en_pausa';
                    break;
                case 'pausa_fin':
                    $estado_actual = 'trabajando';
                    break;
            }
        }

        $estado_labels = [
            'sin_fichar' => __('Sin fichar', 'flavor-chat-ia'),
            'trabajando' => __('Trabajando', 'flavor-chat-ia'),
            'en_pausa' => __('En pausa', 'flavor-chat-ia'),
            'fuera' => __('Jornada finalizada', 'flavor-chat-ia'),
        ];

        $estado_colores = [
            'sin_fichar' => 'neutral',
            'trabajando' => 'success',
            'en_pausa' => 'warning',
            'fuera' => 'info',
        ];
        ?>
        <div class="fmd-fichaje-panel">
            <!-- Reloj y Estado -->
            <div class="fmd-fichaje-header">
                <div class="fmd-fichaje-reloj">
                    <span class="fmd-reloj-hora"><?php echo esc_html(current_time('H:i')); ?></span>
                    <span class="fmd-reloj-fecha"><?php echo esc_html(date_i18n('l, j \d\e F')); ?></span>
                </div>

                <div class="fmd-estado-badge fmd-estado-<?php echo esc_attr($estado_colores[$estado_actual]); ?>">
                    <span class="fmd-estado-dot"></span>
                    <span class="fmd-estado-label"><?php echo esc_html($estado_labels[$estado_actual]); ?></span>
                </div>
            </div>

            <!-- Botones de Fichaje -->
            <div class="fmd-fichaje-acciones">
                <?php if ($estado_actual === 'fuera' || $estado_actual === 'sin_fichar'): ?>
                    <button type="button" class="fmd-btn fmd-btn-lg fmd-btn-success fmd-fichaje-btn" data-action="entrada">
                        <span class="dashicons dashicons-yes-alt"></span>
                        <?php esc_html_e('Fichar Entrada', 'flavor-chat-ia'); ?>
                    </button>
                <?php elseif ($estado_actual === 'trabajando'): ?>
                    <div class="fmd-fichaje-btns-grid">
                        <button type="button" class="fmd-btn fmd-btn-warning fmd-fichaje-btn" data-action="pausa">
                            <span class="dashicons dashicons-coffee"></span>
                            <?php esc_html_e('Pausa', 'flavor-chat-ia'); ?>
                        </button>
                        <button type="button" class="fmd-btn fmd-btn-lg fmd-btn-danger fmd-fichaje-btn" data-action="salida">
                            <span class="dashicons dashicons-migrate"></span>
                            <?php esc_html_e('Fichar Salida', 'flavor-chat-ia'); ?>
                        </button>
                    </div>
                <?php elseif ($estado_actual === 'en_pausa'): ?>
                    <button type="button" class="fmd-btn fmd-btn-lg fmd-btn-primary fmd-fichaje-btn" data-action="reanudar">
                        <span class="dashicons dashicons-controls-play"></span>
                        <?php esc_html_e('Reanudar', 'flavor-chat-ia'); ?>
                    </button>
                <?php endif; ?>
            </div>

            <!-- Resumen del día -->
            <?php if (!empty($fichajes_hoy)): ?>
            <div class="fmd-fichaje-resumen-dia">
                <h4><?php esc_html_e('Hoy', 'flavor-chat-ia'); ?></h4>
                <div class="fmd-stats-row">
                    <div class="fmd-stat-mini">
                        <span class="fmd-stat-valor"><?php echo count($fichajes_hoy); ?></span>
                        <span class="fmd-stat-label"><?php esc_html_e('Fichajes', 'flavor-chat-ia'); ?></span>
                    </div>
                    <div class="fmd-stat-mini">
                        <span class="fmd-stat-valor"><?php echo number_format($horas_trabajadas, 1); ?>h</span>
                        <span class="fmd-stat-label"><?php esc_html_e('Trabajadas', 'flavor-chat-ia'); ?></span>
                    </div>
                </div>

                <div class="fmd-fichaje-timeline">
                    <?php
                    $tipo_iconos = [
                        'entrada' => 'dashicons-arrow-right-alt',
                        'salida' => 'dashicons-arrow-left-alt',
                        'pausa_inicio' => 'dashicons-coffee',
                        'pausa_fin' => 'dashicons-controls-play',
                    ];
                    $tipo_labels = [
                        'entrada' => __('Entrada', 'flavor-chat-ia'),
                        'salida' => __('Salida', 'flavor-chat-ia'),
                        'pausa_inicio' => __('Inicio pausa', 'flavor-chat-ia'),
                        'pausa_fin' => __('Fin pausa', 'flavor-chat-ia'),
                    ];
                    foreach ($fichajes_hoy as $fichaje):
                    ?>
                    <div class="fmd-timeline-item fmd-tipo-<?php echo esc_attr($fichaje['tipo']); ?>">
                        <span class="dashicons <?php echo esc_attr($tipo_iconos[$fichaje['tipo']] ?? 'dashicons-clock'); ?>"></span>
                        <span class="fmd-timeline-hora"><?php echo esc_html(substr($fichaje['hora'], 0, 5)); ?></span>
                        <span class="fmd-timeline-tipo"><?php echo esc_html($tipo_labels[$fichaje['tipo']] ?? $fichaje['tipo']); ?></span>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
            <?php else: ?>
            <div class="fmd-fichaje-vacio">
                <span class="dashicons dashicons-clock"></span>
                <p><?php esc_html_e('No has fichado hoy todavía.', 'flavor-chat-ia'); ?></p>
            </div>
            <?php endif; ?>
        </div>

        <style>
        .fmd-fichaje-panel { max-width: 480px; margin: 0 auto; }
        .fmd-fichaje-header { text-align: center; padding: 1.5rem; background: var(--fmd-bg-secondary, #f8f9fa); border-radius: 12px; margin-bottom: 1.5rem; }
        .fmd-fichaje-reloj { margin-bottom: 1rem; }
        .fmd-reloj-hora { display: block; font-size: 3rem; font-weight: 700; line-height: 1; }
        .fmd-reloj-fecha { display: block; font-size: 0.95rem; color: var(--fmd-text-muted, #6c757d); text-transform: capitalize; margin-top: 0.25rem; }
        .fmd-estado-badge { display: inline-flex; align-items: center; gap: 0.5rem; padding: 0.5rem 1rem; border-radius: 20px; font-size: 0.9rem; font-weight: 500; }
        .fmd-estado-badge.fmd-estado-success { background: #d4edda; color: #155724; }
        .fmd-estado-badge.fmd-estado-warning { background: #fff3cd; color: #856404; }
        .fmd-estado-badge.fmd-estado-info { background: #d1ecf1; color: #0c5460; }
        .fmd-estado-badge.fmd-estado-neutral { background: #e9ecef; color: #495057; }
        .fmd-estado-dot { width: 8px; height: 8px; border-radius: 50%; background: currentColor; animation: pulse 2s infinite; }
        @keyframes pulse { 0%, 100% { opacity: 1; } 50% { opacity: 0.5; } }
        .fmd-fichaje-acciones { text-align: center; margin-bottom: 1.5rem; }
        .fmd-fichaje-btns-grid { display: flex; gap: 1rem; justify-content: center; flex-wrap: wrap; }
        .fmd-btn-lg { padding: 1rem 2rem !important; font-size: 1.1rem !important; }
        .fmd-fichaje-resumen-dia { background: var(--fmd-bg-secondary, #f8f9fa); border-radius: 12px; padding: 1rem; }
        .fmd-fichaje-resumen-dia h4 { margin: 0 0 1rem; font-size: 1rem; }
        .fmd-stats-row { display: flex; gap: 1rem; margin-bottom: 1rem; }
        .fmd-stat-mini { flex: 1; text-align: center; background: white; padding: 0.75rem; border-radius: 8px; }
        .fmd-stat-mini .fmd-stat-valor { display: block; font-size: 1.5rem; font-weight: 700; }
        .fmd-stat-mini .fmd-stat-label { font-size: 0.8rem; color: var(--fmd-text-muted, #6c757d); }
        .fmd-fichaje-timeline { display: flex; flex-direction: column; gap: 0.5rem; }
        .fmd-timeline-item { display: flex; align-items: center; gap: 0.5rem; padding: 0.5rem; background: white; border-radius: 6px; font-size: 0.9rem; }
        .fmd-timeline-item .dashicons { font-size: 16px; width: 16px; height: 16px; }
        .fmd-tipo-entrada .dashicons { color: #28a745; }
        .fmd-tipo-salida .dashicons { color: #dc3545; }
        .fmd-tipo-pausa_inicio .dashicons, .fmd-tipo-pausa_fin .dashicons { color: #ffc107; }
        .fmd-timeline-hora { font-weight: 600; }
        .fmd-timeline-tipo { color: var(--fmd-text-muted, #6c757d); }
        .fmd-fichaje-vacio { text-align: center; padding: 2rem; color: var(--fmd-text-muted, #6c757d); }
        .fmd-fichaje-vacio .dashicons { font-size: 48px; width: 48px; height: 48px; opacity: 0.5; }
        </style>
        <?php
    }

    /**
     * Renderiza el historial de fichajes
     */
    private function render_tab_fichaje_historial() {
        if (!is_user_logged_in()) {
            echo '<div class="fmd-login-required">';
            echo '<p>' . esc_html__('Debes iniciar sesión para ver tu historial.', 'flavor-chat-ia') . '</p>';
            echo '</div>';
            return;
        }

        $user_id = get_current_user_id();
        global $wpdb;
        $tabla_fichajes = $wpdb->prefix . 'flavor_fichajes';

        $fichajes = [];
        $periodo = isset($_GET['periodo']) ? sanitize_text_field($_GET['periodo']) : 'semana';

        if (Flavor_Chat_Helpers::tabla_existe($tabla_fichajes)) {
            $where_fecha = '';
            switch ($periodo) {
                case 'hoy':
                    $where_fecha = "AND DATE(fecha) = CURDATE()";
                    break;
                case 'semana':
                    $where_fecha = "AND YEARWEEK(fecha) = YEARWEEK(NOW())";
                    break;
                case 'mes':
                default:
                    $where_fecha = "AND MONTH(fecha) = MONTH(NOW()) AND YEAR(fecha) = YEAR(NOW())";
                    break;
            }

            $fichajes = $wpdb->get_results($wpdb->prepare(
                "SELECT * FROM {$tabla_fichajes} WHERE user_id = %d {$where_fecha} ORDER BY fecha DESC, hora DESC",
                $user_id
            ), ARRAY_A);
        }

        $periodo_labels = [
            'hoy' => __('Hoy', 'flavor-chat-ia'),
            'semana' => __('Esta semana', 'flavor-chat-ia'),
            'mes' => __('Este mes', 'flavor-chat-ia'),
        ];

        $tipo_labels = [
            'entrada' => __('Entrada', 'flavor-chat-ia'),
            'salida' => __('Salida', 'flavor-chat-ia'),
            'pausa_inicio' => __('Inicio pausa', 'flavor-chat-ia'),
            'pausa_fin' => __('Fin pausa', 'flavor-chat-ia'),
        ];
        ?>
        <div class="fmd-fichaje-historial">
            <div class="fmd-historial-header">
                <h3><?php esc_html_e('Mis Fichajes', 'flavor-chat-ia'); ?></h3>
                <div class="fmd-filtros">
                    <select id="fmd-filtro-periodo" class="fmd-select" onchange="location.href='?periodo=' + this.value">
                        <?php foreach ($periodo_labels as $key => $label): ?>
                        <option value="<?php echo esc_attr($key); ?>" <?php selected($periodo, $key); ?>>
                            <?php echo esc_html($label); ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>

            <?php if (empty($fichajes)): ?>
            <div class="fmd-empty-state">
                <span class="dashicons dashicons-clock"></span>
                <p><?php esc_html_e('No hay fichajes en este periodo.', 'flavor-chat-ia'); ?></p>
            </div>
            <?php else: ?>
            <div class="fmd-fichajes-lista">
                <?php
                // Agrupar por fecha
                $fichajes_agrupados = [];
                foreach ($fichajes as $fichaje) {
                    $fecha = $fichaje['fecha'];
                    if (!isset($fichajes_agrupados[$fecha])) {
                        $fichajes_agrupados[$fecha] = [];
                    }
                    $fichajes_agrupados[$fecha][] = $fichaje;
                }

                foreach ($fichajes_agrupados as $fecha => $fichajes_dia):
                ?>
                <div class="fmd-dia-card">
                    <div class="fmd-dia-header">
                        <span class="fmd-dia-fecha"><?php echo esc_html(date_i18n('l, j F', strtotime($fecha))); ?></span>
                    </div>
                    <div class="fmd-dia-registros">
                        <?php foreach ($fichajes_dia as $fichaje): ?>
                        <div class="fmd-registro fmd-tipo-<?php echo esc_attr($fichaje['tipo']); ?>">
                            <span class="fmd-registro-hora"><?php echo esc_html(substr($fichaje['hora'], 0, 5)); ?></span>
                            <span class="fmd-registro-tipo"><?php echo esc_html($tipo_labels[$fichaje['tipo']] ?? $fichaje['tipo']); ?></span>
                            <?php if (!empty($fichaje['notas'])): ?>
                            <span class="fmd-registro-notas"><?php echo esc_html($fichaje['notas']); ?></span>
                            <?php endif; ?>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>
        </div>

        <style>
        .fmd-fichaje-historial { max-width: 600px; margin: 0 auto; }
        .fmd-historial-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 1rem; }
        .fmd-historial-header h3 { margin: 0; }
        .fmd-dia-card { background: var(--fmd-bg-secondary, #f8f9fa); border-radius: 12px; margin-bottom: 1rem; overflow: hidden; }
        .fmd-dia-header { background: var(--fmd-primary, #007bff); color: white; padding: 0.75rem 1rem; font-weight: 500; text-transform: capitalize; }
        .fmd-dia-registros { padding: 0.5rem; }
        .fmd-registro { display: flex; align-items: center; gap: 0.75rem; padding: 0.75rem; background: white; border-radius: 8px; margin-bottom: 0.25rem; }
        .fmd-registro:last-child { margin-bottom: 0; }
        .fmd-registro-hora { font-weight: 600; font-size: 1.1rem; min-width: 50px; }
        .fmd-registro-tipo { flex: 1; }
        .fmd-registro-notas { color: var(--fmd-text-muted, #6c757d); font-size: 0.85rem; }
        .fmd-tipo-entrada { border-left: 3px solid #28a745; }
        .fmd-tipo-salida { border-left: 3px solid #dc3545; }
        .fmd-tipo-pausa_inicio, .fmd-tipo-pausa_fin { border-left: 3px solid #ffc107; }
        </style>
        <?php
    }

    /**
     * Renderiza el resumen mensual de fichajes
     */
    private function render_tab_fichaje_resumen() {
        if (!is_user_logged_in()) {
            echo '<div class="fmd-login-required">';
            echo '<p>' . esc_html__('Debes iniciar sesión para ver tu resumen.', 'flavor-chat-ia') . '</p>';
            echo '</div>';
            return;
        }

        $user_id = get_current_user_id();
        global $wpdb;
        $tabla_fichajes = $wpdb->prefix . 'flavor_fichajes';

        $mes = isset($_GET['mes']) ? intval($_GET['mes']) : intval(date('m'));
        $anio = isset($_GET['anio']) ? intval($_GET['anio']) : intval(date('Y'));

        $resumen = [
            'total_horas' => 0,
            'dias_trabajados' => 0,
            'promedio_horas_diarias' => 0,
            'detalle_dias' => [],
        ];

        if (Flavor_Chat_Helpers::tabla_existe($tabla_fichajes)) {
            // Obtener fichajes del mes
            $fichajes = $wpdb->get_results($wpdb->prepare(
                "SELECT * FROM {$tabla_fichajes}
                 WHERE user_id = %d AND MONTH(fecha) = %d AND YEAR(fecha) = %d
                 ORDER BY fecha ASC, hora ASC",
                $user_id, $mes, $anio
            ), ARRAY_A);

            // Calcular horas por día
            $horas_por_dia = [];
            $entrada_actual = null;

            foreach ($fichajes as $fichaje) {
                $fecha = $fichaje['fecha'];
                if (!isset($horas_por_dia[$fecha])) {
                    $horas_por_dia[$fecha] = ['horas' => 0, 'fichajes' => 0];
                }
                $horas_por_dia[$fecha]['fichajes']++;

                if ($fichaje['tipo'] === 'entrada') {
                    $entrada_actual = strtotime($fichaje['fecha'] . ' ' . $fichaje['hora']);
                } elseif ($fichaje['tipo'] === 'salida' && $entrada_actual) {
                    $salida = strtotime($fichaje['fecha'] . ' ' . $fichaje['hora']);
                    $horas_por_dia[$fecha]['horas'] += ($salida - $entrada_actual) / 3600;
                    $entrada_actual = null;
                }
            }

            foreach ($horas_por_dia as $fecha => $datos) {
                $resumen['detalle_dias'][] = [
                    'fecha' => $fecha,
                    'horas' => round($datos['horas'], 2),
                    'fichajes' => $datos['fichajes'],
                ];
                $resumen['total_horas'] += $datos['horas'];
            }

            $resumen['dias_trabajados'] = count($horas_por_dia);
            $resumen['promedio_horas_diarias'] = $resumen['dias_trabajados'] > 0
                ? $resumen['total_horas'] / $resumen['dias_trabajados']
                : 0;
        }

        $meses = [
            1 => __('Enero', 'flavor-chat-ia'), 2 => __('Febrero', 'flavor-chat-ia'),
            3 => __('Marzo', 'flavor-chat-ia'), 4 => __('Abril', 'flavor-chat-ia'),
            5 => __('Mayo', 'flavor-chat-ia'), 6 => __('Junio', 'flavor-chat-ia'),
            7 => __('Julio', 'flavor-chat-ia'), 8 => __('Agosto', 'flavor-chat-ia'),
            9 => __('Septiembre', 'flavor-chat-ia'), 10 => __('Octubre', 'flavor-chat-ia'),
            11 => __('Noviembre', 'flavor-chat-ia'), 12 => __('Diciembre', 'flavor-chat-ia'),
        ];
        ?>
        <div class="fmd-fichaje-resumen">
            <div class="fmd-resumen-header">
                <h3><?php esc_html_e('Resumen de Horas', 'flavor-chat-ia'); ?></h3>
                <div class="fmd-periodo-selector">
                    <select id="fmd-mes" class="fmd-select" onchange="location.href='?mes=' + this.value + '&anio=' + document.getElementById('fmd-anio').value">
                        <?php foreach ($meses as $num => $nombre): ?>
                        <option value="<?php echo esc_attr($num); ?>" <?php selected($mes, $num); ?>>
                            <?php echo esc_html($nombre); ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                    <select id="fmd-anio" class="fmd-select" onchange="location.href='?mes=' + document.getElementById('fmd-mes').value + '&anio=' + this.value">
                        <?php for ($a = date('Y'); $a >= date('Y') - 2; $a--): ?>
                        <option value="<?php echo esc_attr($a); ?>" <?php selected($anio, $a); ?>>
                            <?php echo esc_html($a); ?>
                        </option>
                        <?php endfor; ?>
                    </select>
                </div>
            </div>

            <!-- Cards de estadísticas -->
            <div class="fmd-resumen-stats">
                <div class="fmd-stat-card">
                    <div class="fmd-stat-valor"><?php echo esc_html(number_format($resumen['total_horas'], 1)); ?></div>
                    <div class="fmd-stat-label"><?php esc_html_e('Horas totales', 'flavor-chat-ia'); ?></div>
                </div>
                <div class="fmd-stat-card">
                    <div class="fmd-stat-valor"><?php echo esc_html($resumen['dias_trabajados']); ?></div>
                    <div class="fmd-stat-label"><?php esc_html_e('Días trabajados', 'flavor-chat-ia'); ?></div>
                </div>
                <div class="fmd-stat-card">
                    <div class="fmd-stat-valor"><?php echo esc_html(number_format($resumen['promedio_horas_diarias'], 1)); ?></div>
                    <div class="fmd-stat-label"><?php esc_html_e('Promedio diario', 'flavor-chat-ia'); ?></div>
                </div>
            </div>

            <?php if (!empty($resumen['detalle_dias'])): ?>
            <!-- Gráfico de barras -->
            <div class="fmd-grafico">
                <h4><?php esc_html_e('Horas por día', 'flavor-chat-ia'); ?></h4>
                <div class="fmd-grafico-barras">
                    <?php
                    $max_horas = max(array_column($resumen['detalle_dias'], 'horas'));
                    $max_horas = max($max_horas, 8);
                    foreach ($resumen['detalle_dias'] as $dia):
                        $porcentaje = ($dia['horas'] / $max_horas) * 100;
                        $color = $dia['horas'] >= 8 ? '#28a745' : ($dia['horas'] >= 4 ? '#ffc107' : '#dc3545');
                    ?>
                    <div class="fmd-barra-container" title="<?php echo esc_attr(date_i18n('l, j F', strtotime($dia['fecha'])) . ': ' . $dia['horas'] . 'h'); ?>">
                        <div class="fmd-barra" style="height: <?php echo esc_attr($porcentaje); ?>%; background: <?php echo esc_attr($color); ?>;">
                            <span class="fmd-barra-valor"><?php echo esc_html(number_format($dia['horas'], 1)); ?></span>
                        </div>
                        <span class="fmd-barra-label"><?php echo esc_html(date('d', strtotime($dia['fecha']))); ?></span>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>

            <!-- Detalle por día -->
            <div class="fmd-detalle-dias">
                <h4><?php esc_html_e('Detalle por día', 'flavor-chat-ia'); ?></h4>
                <div class="fmd-table-responsive">
                    <table class="fmd-tabla">
                        <thead>
                            <tr>
                                <th><?php esc_html_e('Fecha', 'flavor-chat-ia'); ?></th>
                                <th><?php esc_html_e('Horas', 'flavor-chat-ia'); ?></th>
                                <th><?php esc_html_e('Fichajes', 'flavor-chat-ia'); ?></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($resumen['detalle_dias'] as $dia): ?>
                            <tr>
                                <td><?php echo esc_html(date_i18n('l, j F', strtotime($dia['fecha']))); ?></td>
                                <td>
                                    <span class="fmd-horas-badge <?php echo $dia['horas'] >= 8 ? 'fmd-horas-completas' : 'fmd-horas-parciales'; ?>">
                                        <?php echo esc_html(number_format($dia['horas'], 2)); ?>h
                                    </span>
                                </td>
                                <td><?php echo esc_html($dia['fichajes']); ?></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
            <?php else: ?>
            <div class="fmd-empty-state">
                <span class="dashicons dashicons-calendar-alt"></span>
                <p><?php esc_html_e('No hay registros para este periodo.', 'flavor-chat-ia'); ?></p>
            </div>
            <?php endif; ?>
        </div>

        <style>
        .fmd-fichaje-resumen { max-width: 800px; margin: 0 auto; }
        .fmd-resumen-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 1.5rem; flex-wrap: wrap; gap: 1rem; }
        .fmd-resumen-header h3 { margin: 0; }
        .fmd-periodo-selector { display: flex; gap: 0.5rem; }
        .fmd-resumen-stats { display: grid; grid-template-columns: repeat(auto-fit, minmax(150px, 1fr)); gap: 1rem; margin-bottom: 2rem; }
        .fmd-stat-card { background: var(--fmd-bg-secondary, #f8f9fa); border-radius: 12px; padding: 1.5rem; text-align: center; }
        .fmd-stat-card .fmd-stat-valor { font-size: 2.5rem; font-weight: 700; color: var(--fmd-primary, #007bff); }
        .fmd-stat-card .fmd-stat-label { font-size: 0.9rem; color: var(--fmd-text-muted, #6c757d); margin-top: 0.25rem; }
        .fmd-grafico { background: var(--fmd-bg-secondary, #f8f9fa); border-radius: 12px; padding: 1.5rem; margin-bottom: 2rem; }
        .fmd-grafico h4 { margin: 0 0 1rem; }
        .fmd-grafico-barras { display: flex; align-items: flex-end; gap: 4px; height: 150px; overflow-x: auto; padding-bottom: 1.5rem; }
        .fmd-barra-container { flex: 1; min-width: 24px; max-width: 40px; display: flex; flex-direction: column; align-items: center; height: 100%; }
        .fmd-barra { width: 100%; border-radius: 4px 4px 0 0; display: flex; align-items: flex-start; justify-content: center; min-height: 2px; transition: height 0.3s ease; }
        .fmd-barra-valor { font-size: 0.7rem; font-weight: 600; color: white; padding-top: 2px; white-space: nowrap; }
        .fmd-barra-label { font-size: 0.75rem; color: var(--fmd-text-muted, #6c757d); margin-top: auto; }
        .fmd-detalle-dias h4 { margin: 0 0 1rem; }
        .fmd-horas-badge { display: inline-block; padding: 0.25rem 0.5rem; border-radius: 4px; font-weight: 600; }
        .fmd-horas-completas { background: #d4edda; color: #155724; }
        .fmd-horas-parciales { background: #fff3cd; color: #856404; }
        </style>
        <?php
    }

    /**
     * Renderiza el tab de membresía de socios
     */
    private function render_tab_socios_membresia() {
        if (!is_user_logged_in()) {
            echo '<div class="fmd-login-required">';
            echo '<p>' . esc_html__('Debes iniciar sesión para ver tu membresía.', 'flavor-chat-ia') . '</p>';
            echo '</div>';
            return;
        }

        $user_id = get_current_user_id();
        $wp_user = get_userdata($user_id);
        global $wpdb;
        $tabla_socios = $wpdb->prefix . 'flavor_socios';
        $tabla_tipos = $wpdb->prefix . 'flavor_socios_tipos';

        $socio = null;
        $tipo_socio = null;

        if (Flavor_Chat_Helpers::tabla_existe($tabla_socios)) {
            $socio = $wpdb->get_row($wpdb->prepare(
                "SELECT * FROM {$tabla_socios} WHERE usuario_id = %d",
                $user_id
            ));

            // Completar datos del usuario de WordPress si no están en la tabla
            if ($socio) {
                if (empty($socio->nombre)) {
                    $socio->nombre = $wp_user->first_name ?: $wp_user->display_name;
                }
                if (empty($socio->apellidos)) {
                    $socio->apellidos = $wp_user->last_name ?: '';
                }
                if (empty($socio->email)) {
                    $socio->email = $wp_user->user_email;
                }
            }

            if ($socio && Flavor_Chat_Helpers::tabla_existe($tabla_tipos)) {
                $tipo_socio = $wpdb->get_row($wpdb->prepare(
                    "SELECT * FROM {$tabla_tipos} WHERE slug = %s",
                    $socio->tipo_socio
                ));
            }
        }

        ?>
        <div class="fmd-panel-content">
            <?php if (!$socio): ?>
                <div class="fmd-empty-state">
                    <span class="dashicons dashicons-id"></span>
                    <h3><?php esc_html_e('¡Únete como miembro!', 'flavor-chat-ia'); ?></h3>
                    <p><?php esc_html_e('Accede a beneficios exclusivos, descuentos y participa activamente.', 'flavor-chat-ia'); ?></p>
                    <a href="<?php echo esc_url(home_url('/mi-portal/socios/unirse/')); ?>" class="fmd-btn fmd-btn-primary">
                        <?php esc_html_e('Hacerme miembro', 'flavor-chat-ia'); ?>
                    </a>
                </div>
            <?php else: ?>
                <div class="fmd-membresia-card" style="background: linear-gradient(135deg, <?php echo esc_attr($tipo_socio->color ?? '#7c3aed'); ?> 0%, <?php echo esc_attr($tipo_socio->color ?? '#7c3aed'); ?>dd 100%);">
                    <div class="fmd-membresia-header">
                        <span class="dashicons dashicons-<?php echo esc_attr($tipo_socio->icono ?? 'id'); ?>"></span>
                        <span class="fmd-membresia-tipo"><?php echo esc_html($tipo_socio->nombre ?? ucfirst($socio->tipo_socio)); ?></span>
                    </div>
                    <div class="fmd-membresia-numero">
                        <?php esc_html_e('Socio Nº', 'flavor-chat-ia'); ?> <?php echo esc_html($socio->numero_socio); ?>
                    </div>
                </div>

                <div class="fmd-info-grid">
                    <div class="fmd-info-item">
                        <span class="fmd-info-label"><?php esc_html_e('Nombre', 'flavor-chat-ia'); ?></span>
                        <span class="fmd-info-value"><?php echo esc_html(trim($socio->nombre . ' ' . ($socio->apellidos ?? ''))); ?></span>
                    </div>
                    <div class="fmd-info-item">
                        <span class="fmd-info-label"><?php esc_html_e('Email', 'flavor-chat-ia'); ?></span>
                        <span class="fmd-info-value"><?php echo esc_html($socio->email); ?></span>
                    </div>
                    <div class="fmd-info-item">
                        <span class="fmd-info-label"><?php esc_html_e('Fecha de Alta', 'flavor-chat-ia'); ?></span>
                        <span class="fmd-info-value"><?php echo esc_html(date_i18n('d/m/Y', strtotime($socio->fecha_alta))); ?></span>
                    </div>
                    <div class="fmd-info-item">
                        <span class="fmd-info-label"><?php esc_html_e('Estado', 'flavor-chat-ia'); ?></span>
                        <span class="fmd-badge fmd-badge-<?php echo $socio->estado === 'activo' ? 'success' : 'warning'; ?>">
                            <?php echo esc_html(ucfirst($socio->estado)); ?>
                        </span>
                    </div>
                    <div class="fmd-info-item">
                        <span class="fmd-info-label"><?php esc_html_e('Cuota', 'flavor-chat-ia'); ?></span>
                        <span class="fmd-info-value">
                            <?php
                            $cuota = $socio->cuota_importe ?? $socio->cuota_mensual ?? 0;
                            if ($cuota > 0) {
                                echo esc_html(number_format_i18n($cuota, 2) . ' €/' . ($socio->cuota_tipo ?? 'mensual'));
                            } else {
                                esc_html_e('Gratuita', 'flavor-chat-ia');
                            }
                            ?>
                        </span>
                    </div>
                </div>

                <?php if (!empty($tipo_socio->beneficios)): ?>
                    <div class="fmd-beneficios">
                        <h4><?php esc_html_e('Tus beneficios', 'flavor-chat-ia'); ?></h4>
                        <ul>
                            <?php foreach (explode(',', $tipo_socio->beneficios) as $beneficio): ?>
                                <li><span class="dashicons dashicons-yes-alt"></span> <?php echo esc_html(trim($beneficio)); ?></li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                <?php endif; ?>
            <?php endif; ?>
        </div>
        <?php
    }

    /**
     * Renderiza el tab de cuotas de socios
     */
    private function render_tab_socios_cuotas() {
        if (!is_user_logged_in()) {
            echo '<div class="fmd-login-required">';
            echo '<p>' . esc_html__('Debes iniciar sesión para ver tus cuotas.', 'flavor-chat-ia') . '</p>';
            echo '</div>';
            return;
        }

        $user_id = get_current_user_id();
        global $wpdb;
        $tabla_socios = $wpdb->prefix . 'flavor_socios';
        $tabla_cuotas = $wpdb->prefix . 'flavor_socios_cuotas';

        $socio = null;
        $cuotas = [];

        if (Flavor_Chat_Helpers::tabla_existe($tabla_socios)) {
            $socio = $wpdb->get_row($wpdb->prepare(
                "SELECT id FROM {$tabla_socios} WHERE usuario_id = %d",
                $user_id
            ));

            if ($socio && Flavor_Chat_Helpers::tabla_existe($tabla_cuotas)) {
                $cuotas = $wpdb->get_results($wpdb->prepare(
                    "SELECT * FROM {$tabla_cuotas} WHERE socio_id = %d ORDER BY fecha_vencimiento DESC LIMIT 24",
                    $socio->id
                ));
            }
        }

        $estados_colores = [
            'pendiente' => 'warning',
            'pagada'    => 'success',
            'vencida'   => 'danger',
            'cancelada' => 'secondary',
        ];

        ?>
        <div class="fmd-panel-content">
            <?php if (!$socio): ?>
                <div class="fmd-empty-state">
                    <span class="dashicons dashicons-id"></span>
                    <p><?php esc_html_e('No eres miembro todavía.', 'flavor-chat-ia'); ?></p>
                </div>
            <?php elseif (empty($cuotas)): ?>
                <div class="fmd-empty-state">
                    <span class="dashicons dashicons-money-alt"></span>
                    <p><?php esc_html_e('No tienes cuotas registradas.', 'flavor-chat-ia'); ?></p>
                </div>
            <?php else: ?>
                <div class="fmd-table-responsive">
                    <table class="fmd-table">
                        <thead>
                            <tr>
                                <th><?php esc_html_e('Concepto', 'flavor-chat-ia'); ?></th>
                                <th><?php esc_html_e('Período', 'flavor-chat-ia'); ?></th>
                                <th><?php esc_html_e('Importe', 'flavor-chat-ia'); ?></th>
                                <th><?php esc_html_e('Vencimiento', 'flavor-chat-ia'); ?></th>
                                <th><?php esc_html_e('Estado', 'flavor-chat-ia'); ?></th>
                                <th><?php esc_html_e('Acciones', 'flavor-chat-ia'); ?></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($cuotas as $cuota): ?>
                                <tr>
                                    <td><?php echo esc_html($cuota->concepto ?? 'Cuota'); ?></td>
                                    <td><?php echo esc_html($cuota->periodo); ?></td>
                                    <td><strong><?php echo esc_html(number_format_i18n($cuota->importe, 2)); ?> €</strong></td>
                                    <td><?php echo esc_html(date_i18n('d/m/Y', strtotime($cuota->fecha_vencimiento ?? $cuota->fecha_cargo ?? ''))); ?></td>
                                    <td>
                                        <span class="fmd-badge fmd-badge-<?php echo esc_attr($estados_colores[$cuota->estado] ?? 'secondary'); ?>">
                                            <?php echo esc_html(ucfirst($cuota->estado)); ?>
                                        </span>
                                    </td>
                                    <td>
                                        <?php if ($cuota->estado === 'pendiente' || $cuota->estado === 'vencida'): ?>
                                            <a href="<?php echo esc_url(add_query_arg('cuota_id', $cuota->id, home_url('/mi-portal/socios/pagar-cuota/'))); ?>" class="fmd-btn fmd-btn-sm fmd-btn-primary">
                                                <?php esc_html_e('Pagar', 'flavor-chat-ia'); ?>
                                            </a>
                                        <?php elseif (!empty($cuota->factura_url)): ?>
                                            <a href="<?php echo esc_url($cuota->factura_url); ?>" class="fmd-btn fmd-btn-sm fmd-btn-outline" target="_blank">
                                                <?php esc_html_e('Factura', 'flavor-chat-ia'); ?>
                                            </a>
                                        <?php else: ?>
                                            <span class="fmd-text-muted">—</span>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>
        <?php
    }

    /**
     * Renderiza el tab de directorio de socios
     */
    private function render_tab_socios_directorio() {
        global $wpdb;
        $tabla_socios = $wpdb->prefix . 'flavor_socios';

        if (!Flavor_Chat_Helpers::tabla_existe($tabla_socios)) {
            echo '<div class="fmd-empty-state">';
            echo '<p>' . esc_html__('El directorio de miembros no está disponible.', 'flavor-chat-ia') . '</p>';
            echo '</div>';
            return;
        }

        // Obtener socios con datos del usuario de WordPress
        $socios = $wpdb->get_results(
            "SELECT s.numero_socio, s.tipo_socio, s.fecha_alta, s.usuario_id,
                    u.ID as user_id, u.display_name, u.user_email
             FROM {$tabla_socios} s
             LEFT JOIN {$wpdb->users} u ON s.usuario_id = u.ID
             WHERE s.estado = 'activo'
             ORDER BY u.display_name ASC
             LIMIT 50"
        );

        // Completar datos
        foreach ($socios as $socio) {
            $socio->nombre = $socio->display_name ?: 'Miembro';
            $socio->apellidos = '';
        }

        ?>
        <div class="fmd-panel-content">
            <?php if (empty($socios)): ?>
                <div class="fmd-empty-state">
                    <span class="dashicons dashicons-groups"></span>
                    <p><?php esc_html_e('No hay miembros registrados.', 'flavor-chat-ia'); ?></p>
                </div>
            <?php else: ?>
                <div class="fmd-directorio-grid">
                    <?php foreach ($socios as $socio):
                        $avatar_url = $socio->user_id ? get_avatar_url($socio->user_id, ['size' => 64]) : '';
                    ?>
                        <div class="fmd-socio-card">
                            <div class="fmd-socio-avatar">
                                <?php if ($avatar_url): ?>
                                    <img src="<?php echo esc_url($avatar_url); ?>" alt="">
                                <?php else: ?>
                                    <span class="dashicons dashicons-admin-users"></span>
                                <?php endif; ?>
                            </div>
                            <div class="fmd-socio-info">
                                <span class="fmd-socio-nombre"><?php echo esc_html(trim($socio->nombre . ' ' . ($socio->apellidos ?? ''))); ?></span>
                                <span class="fmd-socio-numero"><?php echo esc_html($socio->numero_socio); ?></span>
                                <span class="fmd-socio-tipo"><?php echo esc_html(ucfirst($socio->tipo_socio)); ?></span>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
        <?php
    }

    /**
     * Renderiza el tab de beneficios de socio
     */
    private function render_tab_socios_beneficios() {
        if (!is_user_logged_in()) {
            echo '<div class="fmd-login-required">';
            echo '<p>' . esc_html__('Debes iniciar sesión para ver los beneficios.', 'flavor-chat-ia') . '</p>';
            echo '</div>';
            return;
        }

        $user_id = get_current_user_id();
        global $wpdb;
        $tabla_socios = $wpdb->prefix . 'flavor_socios';
        $tabla_tipos = $wpdb->prefix . 'flavor_socios_tipos';

        $socio = null;
        $tipo_socio = null;
        $todos_tipos = [];

        if (Flavor_Chat_Helpers::tabla_existe($tabla_socios)) {
            $socio = $wpdb->get_row($wpdb->prepare(
                "SELECT * FROM {$tabla_socios} WHERE usuario_id = %d",
                $user_id
            ));
        }

        if (Flavor_Chat_Helpers::tabla_existe($tabla_tipos)) {
            if ($socio) {
                $tipo_socio = $wpdb->get_row($wpdb->prepare(
                    "SELECT * FROM {$tabla_tipos} WHERE slug = %s",
                    $socio->tipo_socio
                ));
            }
            $todos_tipos = $wpdb->get_results("SELECT * FROM {$tabla_tipos} WHERE activo = 1 ORDER BY orden ASC");
        }

        ?>
        <div class="fmd-panel-content">
            <?php if ($socio && $tipo_socio): ?>
                <div class="fmd-beneficios-header" style="background: <?php echo esc_attr($tipo_socio->color ?? '#7c3aed'); ?>; color: white; padding: 20px; border-radius: 8px; margin-bottom: 20px;">
                    <h3 style="margin: 0; color: white;">
                        <span class="dashicons dashicons-<?php echo esc_attr($tipo_socio->icono ?? 'awards'); ?>"></span>
                        <?php echo esc_html($tipo_socio->nombre); ?>
                    </h3>
                    <p style="margin: 10px 0 0; opacity: 0.9;"><?php echo esc_html($tipo_socio->descripcion ?? ''); ?></p>
                </div>

                <h4><?php esc_html_e('Tus beneficios incluidos', 'flavor-chat-ia'); ?></h4>
                <?php if (!empty($tipo_socio->beneficios)): ?>
                    <ul class="fmd-beneficios-lista">
                        <?php foreach (explode(',', $tipo_socio->beneficios) as $beneficio): ?>
                            <li>
                                <span class="dashicons dashicons-yes-alt" style="color: #10b981;"></span>
                                <?php echo esc_html(trim($beneficio)); ?>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                <?php else: ?>
                    <p><?php esc_html_e('Contacta con administración para conocer tus beneficios.', 'flavor-chat-ia'); ?></p>
                <?php endif; ?>

            <?php else: ?>
                <h3><?php esc_html_e('Tipos de membresía disponibles', 'flavor-chat-ia'); ?></h3>
                <?php if (empty($todos_tipos)): ?>
                    <p><?php esc_html_e('No hay tipos de membresía configurados.', 'flavor-chat-ia'); ?></p>
                <?php else: ?>
                    <div class="fmd-tipos-grid" style="display: grid; grid-template-columns: repeat(auto-fill, minmax(280px, 1fr)); gap: 20px;">
                        <?php foreach ($todos_tipos as $tipo): ?>
                            <div class="fmd-tipo-card" style="border: 2px solid <?php echo esc_attr($tipo->color ?? '#e5e7eb'); ?>; border-radius: 12px; padding: 20px;">
                                <div style="display: flex; align-items: center; gap: 10px; margin-bottom: 15px;">
                                    <span class="dashicons dashicons-<?php echo esc_attr($tipo->icono ?? 'id'); ?>" style="color: <?php echo esc_attr($tipo->color ?? '#7c3aed'); ?>; font-size: 24px;"></span>
                                    <h4 style="margin: 0;"><?php echo esc_html($tipo->nombre); ?></h4>
                                </div>
                                <p style="color: #6b7280; font-size: 14px;"><?php echo esc_html($tipo->descripcion ?? ''); ?></p>
                                <div style="margin: 15px 0; padding: 15px; background: #f9fafb; border-radius: 8px;">
                                    <?php if ($tipo->es_gratuito): ?>
                                        <span style="font-size: 24px; font-weight: bold; color: #10b981;"><?php esc_html_e('Gratuito', 'flavor-chat-ia'); ?></span>
                                    <?php else: ?>
                                        <span style="font-size: 24px; font-weight: bold;"><?php echo esc_html(number_format_i18n($tipo->cuota_mensual ?? 0, 2)); ?> €</span>
                                        <span style="color: #6b7280;">/<?php esc_html_e('mes', 'flavor-chat-ia'); ?></span>
                                    <?php endif; ?>
                                </div>
                                <?php if (!empty($tipo->beneficios)): ?>
                                    <ul style="list-style: none; padding: 0; margin: 0;">
                                        <?php foreach (explode(',', $tipo->beneficios) as $beneficio): ?>
                                            <li style="padding: 5px 0; display: flex; align-items: center; gap: 8px;">
                                                <span class="dashicons dashicons-yes" style="color: #10b981;"></span>
                                                <?php echo esc_html(trim($beneficio)); ?>
                                            </li>
                                        <?php endforeach; ?>
                                    </ul>
                                <?php endif; ?>
                            </div>
                        <?php endforeach; ?>
                    </div>
                    <div style="text-align: center; margin-top: 30px;">
                        <a href="<?php echo esc_url(home_url('/mi-portal/socios/unirse/')); ?>" class="fmd-btn fmd-btn-primary">
                            <?php esc_html_e('Hacerme miembro', 'flavor-chat-ia'); ?>
                        </a>
                    </div>
                <?php endif; ?>
            <?php endif; ?>
        </div>
        <?php
    }

    /**
     * Renderiza el carnet digital de socio
     */
    private function render_tab_socios_carnet() {
        if (!is_user_logged_in()) {
            echo '<div class="fmd-login-required">';
            echo '<p>' . esc_html__('Debes iniciar sesión para ver tu carnet.', 'flavor-chat-ia') . '</p>';
            echo '</div>';
            return;
        }

        $user_id = get_current_user_id();
        $wp_user = get_userdata($user_id);
        global $wpdb;
        $tabla_socios = $wpdb->prefix . 'flavor_socios';
        $tabla_tipos = $wpdb->prefix . 'flavor_socios_tipos';

        $socio = null;
        $tipo_socio = null;

        if (Flavor_Chat_Helpers::tabla_existe($tabla_socios)) {
            $socio = $wpdb->get_row($wpdb->prepare(
                "SELECT * FROM {$tabla_socios} WHERE usuario_id = %d",
                $user_id
            ));
            if ($socio) {
                if (empty($socio->nombre)) {
                    $socio->nombre = $wp_user->first_name ?: $wp_user->display_name;
                }
                if (empty($socio->apellidos)) {
                    $socio->apellidos = $wp_user->last_name ?: '';
                }
            }
            if ($socio && Flavor_Chat_Helpers::tabla_existe($tabla_tipos)) {
                $tipo_socio = $wpdb->get_row($wpdb->prepare(
                    "SELECT * FROM {$tabla_tipos} WHERE slug = %s",
                    $socio->tipo_socio
                ));
            }
        }

        ?>
        <div class="fmd-panel-content">
            <?php if (!$socio): ?>
                <div class="fmd-empty-state">
                    <span class="dashicons dashicons-id"></span>
                    <h3><?php esc_html_e('No tienes carnet de miembro', 'flavor-chat-ia'); ?></h3>
                    <p><?php esc_html_e('Hazte miembro para obtener tu carnet digital.', 'flavor-chat-ia'); ?></p>
                    <a href="<?php echo esc_url(home_url('/mi-portal/socios/unirse/')); ?>" class="fmd-btn fmd-btn-primary">
                        <?php esc_html_e('Hacerme miembro', 'flavor-chat-ia'); ?>
                    </a>
                </div>
            <?php elseif ($socio->estado !== 'activo'): ?>
                <div class="fmd-empty-state">
                    <span class="dashicons dashicons-warning"></span>
                    <h3><?php esc_html_e('Carnet no disponible', 'flavor-chat-ia'); ?></h3>
                    <p><?php esc_html_e('Tu membresía no está activa. Contacta con administración.', 'flavor-chat-ia'); ?></p>
                </div>
            <?php else:
                $color = $tipo_socio->color ?? '#7c3aed';
                $avatar_url = get_avatar_url($user_id, ['size' => 120]);
            ?>
                <div class="fmd-carnet-container" style="max-width: 400px; margin: 0 auto;">
                    <div class="fmd-carnet" style="background: linear-gradient(135deg, <?php echo esc_attr($color); ?> 0%, <?php echo esc_attr($color); ?>cc 100%); color: white; border-radius: 16px; padding: 30px; box-shadow: 0 10px 40px rgba(0,0,0,0.2);">
                        <div style="display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 20px;">
                            <div>
                                <div style="font-size: 12px; opacity: 0.8; text-transform: uppercase; letter-spacing: 1px;"><?php esc_html_e('Carnet de Socio', 'flavor-chat-ia'); ?></div>
                                <div style="font-size: 24px; font-weight: bold; margin-top: 5px;"><?php echo esc_html($tipo_socio->nombre ?? ucfirst($socio->tipo_socio)); ?></div>
                            </div>
                            <span class="dashicons dashicons-<?php echo esc_attr($tipo_socio->icono ?? 'id'); ?>" style="font-size: 32px; width: 32px; height: 32px;"></span>
                        </div>

                        <div style="display: flex; gap: 20px; align-items: center; margin: 25px 0;">
                            <?php if ($avatar_url): ?>
                                <img src="<?php echo esc_url($avatar_url); ?>" alt="" style="width: 80px; height: 80px; border-radius: 50%; border: 3px solid rgba(255,255,255,0.5);">
                            <?php endif; ?>
                            <div>
                                <div style="font-size: 20px; font-weight: bold;"><?php echo esc_html(trim($socio->nombre . ' ' . ($socio->apellidos ?? ''))); ?></div>
                                <div style="font-size: 28px; font-weight: bold; opacity: 0.9; margin-top: 5px;"><?php echo esc_html($socio->numero_socio); ?></div>
                            </div>
                        </div>

                        <div style="display: flex; justify-content: space-between; padding-top: 15px; border-top: 1px solid rgba(255,255,255,0.2); font-size: 12px;">
                            <div>
                                <div style="opacity: 0.7;"><?php esc_html_e('Miembro desde', 'flavor-chat-ia'); ?></div>
                                <div style="font-weight: bold;"><?php echo esc_html(date_i18n('M Y', strtotime($socio->fecha_alta))); ?></div>
                            </div>
                            <div style="text-align: right;">
                                <div style="opacity: 0.7;"><?php esc_html_e('Estado', 'flavor-chat-ia'); ?></div>
                                <div style="font-weight: bold;"><?php esc_html_e('ACTIVO', 'flavor-chat-ia'); ?></div>
                            </div>
                        </div>
                    </div>

                    <div style="text-align: center; margin-top: 20px;">
                        <button onclick="window.print();" class="fmd-btn fmd-btn-outline">
                            <span class="dashicons dashicons-printer"></span>
                            <?php esc_html_e('Imprimir carnet', 'flavor-chat-ia'); ?>
                        </button>
                    </div>
                </div>
            <?php endif; ?>
        </div>
        <?php
    }

    /**
     * Renderiza el historial de membresía
     */
    private function render_tab_socios_historial() {
        if (!is_user_logged_in()) {
            echo '<div class="fmd-login-required">';
            echo '<p>' . esc_html__('Debes iniciar sesión para ver tu historial.', 'flavor-chat-ia') . '</p>';
            echo '</div>';
            return;
        }

        $user_id = get_current_user_id();
        global $wpdb;
        $tabla_socios = $wpdb->prefix . 'flavor_socios';
        $tabla_cuotas = $wpdb->prefix . 'flavor_socios_cuotas';

        $socio = null;
        $historial = [];
        $estadisticas = [];

        if (Flavor_Chat_Helpers::tabla_existe($tabla_socios)) {
            $socio = $wpdb->get_row($wpdb->prepare(
                "SELECT * FROM {$tabla_socios} WHERE usuario_id = %d",
                $user_id
            ));

            if ($socio && Flavor_Chat_Helpers::tabla_existe($tabla_cuotas)) {
                // Obtener todas las cuotas pagadas
                $cuotas_pagadas = $wpdb->get_results($wpdb->prepare(
                    "SELECT * FROM {$tabla_cuotas} WHERE socio_id = %d AND estado = 'pagada' ORDER BY fecha_pago DESC",
                    $socio->id
                ));

                foreach ($cuotas_pagadas as $cuota) {
                    $historial[] = [
                        'fecha' => $cuota->fecha_pago,
                        'tipo' => 'pago',
                        'descripcion' => sprintf(__('Pago de %s', 'flavor-chat-ia'), $cuota->concepto),
                        'importe' => $cuota->importe,
                        'icono' => 'money-alt',
                        'color' => '#10b981'
                    ];
                }

                // Estadísticas
                $total_pagado = $wpdb->get_var($wpdb->prepare(
                    "SELECT SUM(importe) FROM {$tabla_cuotas} WHERE socio_id = %d AND estado = 'pagada'",
                    $socio->id
                ));
                $cuotas_count = count($cuotas_pagadas);
                $antiguedad_dias = floor((time() - strtotime($socio->fecha_alta)) / 86400);
                $antiguedad_meses = floor($antiguedad_dias / 30);

                $estadisticas = [
                    ['label' => __('Total aportado', 'flavor-chat-ia'), 'value' => number_format_i18n($total_pagado ?? 0, 2) . ' €', 'icon' => 'money-alt'],
                    ['label' => __('Cuotas pagadas', 'flavor-chat-ia'), 'value' => $cuotas_count, 'icon' => 'yes-alt'],
                    ['label' => __('Antigüedad', 'flavor-chat-ia'), 'value' => sprintf(_n('%d mes', '%d meses', $antiguedad_meses, 'flavor-chat-ia'), $antiguedad_meses), 'icon' => 'calendar-alt'],
                ];
            }
        }

        // Ordenar historial por fecha
        usort($historial, function($a, $b) {
            return strtotime($b['fecha']) - strtotime($a['fecha']);
        });

        ?>
        <div class="fmd-panel-content">
            <?php if (!$socio): ?>
                <div class="fmd-empty-state">
                    <span class="dashicons dashicons-backup"></span>
                    <p><?php esc_html_e('No eres miembro todavía.', 'flavor-chat-ia'); ?></p>
                </div>
            <?php else: ?>
                <?php if (!empty($estadisticas)): ?>
                    <div class="fmd-stats-grid" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(150px, 1fr)); gap: 15px; margin-bottom: 30px;">
                        <?php foreach ($estadisticas as $stat): ?>
                            <div style="background: #f9fafb; padding: 20px; border-radius: 12px; text-align: center;">
                                <span class="dashicons dashicons-<?php echo esc_attr($stat['icon']); ?>" style="font-size: 24px; color: #7c3aed;"></span>
                                <div style="font-size: 24px; font-weight: bold; margin: 10px 0;"><?php echo esc_html($stat['value']); ?></div>
                                <div style="color: #6b7280; font-size: 14px;"><?php echo esc_html($stat['label']); ?></div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>

                <h4><?php esc_html_e('Historial de pagos', 'flavor-chat-ia'); ?></h4>
                <?php if (empty($historial)): ?>
                    <p style="color: #6b7280;"><?php esc_html_e('No hay movimientos registrados.', 'flavor-chat-ia'); ?></p>
                <?php else: ?>
                    <div class="fmd-historial-timeline">
                        <?php foreach (array_slice($historial, 0, 20) as $item): ?>
                            <div style="display: flex; gap: 15px; padding: 15px 0; border-bottom: 1px solid #e5e7eb;">
                                <div style="width: 40px; height: 40px; background: <?php echo esc_attr($item['color']); ?>20; border-radius: 50%; display: flex; align-items: center; justify-content: center;">
                                    <span class="dashicons dashicons-<?php echo esc_attr($item['icono']); ?>" style="color: <?php echo esc_attr($item['color']); ?>;"></span>
                                </div>
                                <div style="flex: 1;">
                                    <div style="font-weight: 500;"><?php echo esc_html($item['descripcion']); ?></div>
                                    <div style="color: #6b7280; font-size: 13px;"><?php echo esc_html(date_i18n('d M Y, H:i', strtotime($item['fecha']))); ?></div>
                                </div>
                                <?php if (!empty($item['importe'])): ?>
                                    <div style="font-weight: bold; color: #10b981;">+<?php echo esc_html(number_format_i18n($item['importe'], 2)); ?> €</div>
                                <?php endif; ?>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            <?php endif; ?>
        </div>
        <?php
    }

    // =========================================================
    // FOROS - Renderizado de tabs
    // =========================================================

    /**
     * Renderiza tab de temas recientes del foro
     */
    private function render_tab_foros_temas_recientes() {
        global $wpdb;

        $tabla_hilos = $wpdb->prefix . 'flavor_foros_hilos';
        $tabla_foros = $wpdb->prefix . 'flavor_foros';
        $tabla_respuestas = $wpdb->prefix . 'flavor_foros_respuestas';

        // Verificar si la tabla existe
        if (!Flavor_Chat_Helpers::tabla_existe($tabla_hilos)) {
            ?>
            <div class="fmd-empty-state">
                <span class="dashicons dashicons-format-chat"></span>
                <p><?php esc_html_e('El módulo de foros no está configurado correctamente.', 'flavor-chat-ia'); ?></p>
            </div>
            <?php
            return;
        }

        // Obtener hilos recientes
        $hilos_recientes = $wpdb->get_results(
            "SELECT h.*, f.nombre AS nombre_foro, f.icono AS icono_foro,
                    u.display_name AS nombre_autor,
                    (SELECT COUNT(*) FROM {$tabla_respuestas} r WHERE r.hilo_id = h.id AND r.estado = 'visible') AS total_respuestas
             FROM {$tabla_hilos} h
             LEFT JOIN {$tabla_foros} f ON f.id = h.foro_id
             LEFT JOIN {$wpdb->users} u ON u.ID = h.autor_id
             WHERE h.estado != 'eliminado'
             ORDER BY h.es_fijado DESC, h.ultima_actividad DESC
             LIMIT 20"
        );
        ?>
        <div class="fmd-panel-content">
            <?php if (empty($hilos_recientes)): ?>
                <div class="fmd-empty-state">
                    <span class="dashicons dashicons-format-chat"></span>
                    <p><?php esc_html_e('No hay temas de discusión aún.', 'flavor-chat-ia'); ?></p>
                    <p class="fmd-empty-cta"><?php esc_html_e('¡Sé el primero en iniciar una conversación!', 'flavor-chat-ia'); ?></p>
                </div>
            <?php else: ?>
                <div class="fmd-hilos-lista">
                    <?php foreach ($hilos_recientes as $hilo): ?>
                        <?php
                        $url_hilo = home_url('/mi-portal/foros/tema/' . $hilo->id . '/');
                        $tiempo_transcurrido = human_time_diff(strtotime($hilo->ultima_actividad), current_time('timestamp'));
                        $clases_hilo = 'fmd-hilo-item';
                        if ($hilo->es_fijado) {
                            $clases_hilo .= ' fmd-hilo-fijado';
                        }
                        if ($hilo->estado === 'cerrado') {
                            $clases_hilo .= ' fmd-hilo-cerrado';
                        }
                        ?>
                        <article class="<?php echo esc_attr($clases_hilo); ?>">
                            <div class="fmd-hilo-avatar">
                                <?php echo get_avatar($hilo->autor_id, 48); ?>
                            </div>
                            <div class="fmd-hilo-contenido">
                                <h4 class="fmd-hilo-titulo">
                                    <?php if ($hilo->es_fijado): ?>
                                        <span class="dashicons dashicons-admin-post" title="<?php esc_attr_e('Fijado', 'flavor-chat-ia'); ?>" style="color: var(--module-color);"></span>
                                    <?php endif; ?>
                                    <?php if ($hilo->estado === 'cerrado'): ?>
                                        <span class="dashicons dashicons-lock" title="<?php esc_attr_e('Cerrado', 'flavor-chat-ia'); ?>" style="color: #ef4444;"></span>
                                    <?php endif; ?>
                                    <a href="<?php echo esc_url($url_hilo); ?>"><?php echo esc_html($hilo->titulo); ?></a>
                                </h4>
                                <div class="fmd-hilo-meta">
                                    <span class="fmd-hilo-autor">
                                        <?php echo esc_html($hilo->nombre_autor ?: __('Anónimo', 'flavor-chat-ia')); ?>
                                    </span>
                                    <span class="fmd-hilo-separador">·</span>
                                    <span class="fmd-hilo-categoria" title="<?php esc_attr_e('Categoría', 'flavor-chat-ia'); ?>">
                                        <?php echo esc_html($hilo->icono_foro ?: '💬'); ?>
                                        <?php echo esc_html($hilo->nombre_foro ?: __('General', 'flavor-chat-ia')); ?>
                                    </span>
                                    <span class="fmd-hilo-separador">·</span>
                                    <span class="fmd-hilo-tiempo" title="<?php echo esc_attr(date_i18n('d/m/Y H:i', strtotime($hilo->ultima_actividad))); ?>">
                                        <?php echo sprintf(__('hace %s', 'flavor-chat-ia'), esc_html($tiempo_transcurrido)); ?>
                                    </span>
                                </div>
                            </div>
                            <div class="fmd-hilo-stats">
                                <div class="fmd-hilo-stat" title="<?php esc_attr_e('Respuestas', 'flavor-chat-ia'); ?>">
                                    <span class="dashicons dashicons-admin-comments"></span>
                                    <span><?php echo intval($hilo->total_respuestas); ?></span>
                                </div>
                                <div class="fmd-hilo-stat" title="<?php esc_attr_e('Vistas', 'flavor-chat-ia'); ?>">
                                    <span class="dashicons dashicons-visibility"></span>
                                    <span><?php echo intval($hilo->vistas); ?></span>
                                </div>
                            </div>
                        </article>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>

        <style>
        .fmd-hilos-lista { display: flex; flex-direction: column; gap: 1px; background: #e5e7eb; border-radius: 12px; overflow: hidden; }
        .fmd-hilo-item { display: flex; gap: 16px; padding: 16px; background: #fff; align-items: flex-start; transition: background 0.2s; }
        .fmd-hilo-item:hover { background: #f9fafb; }
        .fmd-hilo-fijado { background: #fef3c7; border-left: 3px solid #f59e0b; }
        .fmd-hilo-fijado:hover { background: #fef9c3; }
        .fmd-hilo-cerrado { opacity: 0.7; }
        .fmd-hilo-avatar img { border-radius: 50%; width: 48px; height: 48px; }
        .fmd-hilo-contenido { flex: 1; min-width: 0; }
        .fmd-hilo-titulo { margin: 0 0 6px; font-size: 1rem; font-weight: 600; display: flex; align-items: center; gap: 6px; }
        .fmd-hilo-titulo a { color: inherit; text-decoration: none; }
        .fmd-hilo-titulo a:hover { color: var(--module-color, #3b82f6); }
        .fmd-hilo-titulo .dashicons { font-size: 16px; width: 16px; height: 16px; }
        .fmd-hilo-meta { display: flex; flex-wrap: wrap; gap: 4px; font-size: 0.8125rem; color: #6b7280; }
        .fmd-hilo-separador { color: #d1d5db; }
        .fmd-hilo-categoria { display: inline-flex; align-items: center; gap: 4px; }
        .fmd-hilo-stats { display: flex; gap: 16px; }
        .fmd-hilo-stat { display: flex; align-items: center; gap: 4px; font-size: 0.875rem; color: #6b7280; }
        .fmd-hilo-stat .dashicons { font-size: 16px; width: 16px; height: 16px; }
        @media (max-width: 640px) {
            .fmd-hilo-item { flex-wrap: wrap; }
            .fmd-hilo-avatar { display: none; }
            .fmd-hilo-stats { width: 100%; justify-content: flex-start; margin-top: 8px; }
        }
        </style>
        <?php
    }

    /**
     * Renderiza tab de hilos del usuario actual
     */
    private function render_tab_foros_mis_hilos() {
        if (!is_user_logged_in()) {
            ?>
            <div class="fmd-login-required">
                <span class="dashicons dashicons-lock"></span>
                <p><?php esc_html_e('Debes iniciar sesión para ver tus hilos.', 'flavor-chat-ia'); ?></p>
                <a href="<?php echo esc_url(wp_login_url($this->get_current_request_url())); ?>" class="fmd-btn fmd-btn-primary">
                    <?php esc_html_e('Iniciar sesión', 'flavor-chat-ia'); ?>
                </a>
            </div>
            <?php
            return;
        }

        global $wpdb;
        $usuario_id_actual = get_current_user_id();

        $tabla_hilos = $wpdb->prefix . 'flavor_foros_hilos';
        $tabla_foros = $wpdb->prefix . 'flavor_foros';
        $tabla_respuestas = $wpdb->prefix . 'flavor_foros_respuestas';

        // Obtener hilos del usuario
        $mis_hilos = $wpdb->get_results($wpdb->prepare(
            "SELECT h.*, f.nombre AS nombre_foro, f.icono AS icono_foro,
                    (SELECT COUNT(*) FROM {$tabla_respuestas} r WHERE r.hilo_id = h.id AND r.estado = 'visible') AS total_respuestas
             FROM {$tabla_hilos} h
             LEFT JOIN {$tabla_foros} f ON f.id = h.foro_id
             WHERE h.autor_id = %d AND h.estado != 'eliminado'
             ORDER BY h.ultima_actividad DESC
             LIMIT 50",
            $usuario_id_actual
        ));

        // Estadísticas del usuario
        $total_hilos_usuario = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM {$tabla_hilos} WHERE autor_id = %d AND estado != 'eliminado'",
            $usuario_id_actual
        ));
        $total_respuestas_usuario = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM {$tabla_respuestas} WHERE autor_id = %d AND estado = 'visible'",
            $usuario_id_actual
        ));
        ?>
        <div class="fmd-panel-content">
            <!-- Estadísticas del usuario -->
            <div class="fmd-user-stats" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(120px, 1fr)); gap: 16px; margin-bottom: 24px;">
                <div class="fmd-stat-card">
                    <span class="dashicons dashicons-format-chat" style="color: var(--module-color);"></span>
                    <span class="fmd-stat-value"><?php echo intval($total_hilos_usuario); ?></span>
                    <span class="fmd-stat-label"><?php esc_html_e('Temas creados', 'flavor-chat-ia'); ?></span>
                </div>
                <div class="fmd-stat-card">
                    <span class="dashicons dashicons-admin-comments" style="color: #10b981;"></span>
                    <span class="fmd-stat-value"><?php echo intval($total_respuestas_usuario); ?></span>
                    <span class="fmd-stat-label"><?php esc_html_e('Respuestas', 'flavor-chat-ia'); ?></span>
                </div>
            </div>

            <?php if (empty($mis_hilos)): ?>
                <div class="fmd-empty-state">
                    <span class="dashicons dashicons-admin-comments"></span>
                    <p><?php esc_html_e('No has creado ningún tema todavía.', 'flavor-chat-ia'); ?></p>
                    <p class="fmd-empty-cta"><?php esc_html_e('¡Empieza una nueva discusión!', 'flavor-chat-ia'); ?></p>
                </div>
            <?php else: ?>
                <h4 style="margin: 0 0 16px; font-size: 1rem;"><?php esc_html_e('Tus temas', 'flavor-chat-ia'); ?></h4>
                <div class="fmd-hilos-lista">
                    <?php foreach ($mis_hilos as $hilo): ?>
                        <?php
                        $url_hilo = home_url('/mi-portal/foros/tema/' . $hilo->id . '/');
                        $tiempo_transcurrido = human_time_diff(strtotime($hilo->ultima_actividad), current_time('timestamp'));
                        $estado_badge = '';
                        switch ($hilo->estado) {
                            case 'abierto':
                                $estado_badge = '<span class="fmd-badge fmd-badge-success">' . __('Abierto', 'flavor-chat-ia') . '</span>';
                                break;
                            case 'cerrado':
                                $estado_badge = '<span class="fmd-badge fmd-badge-warning">' . __('Cerrado', 'flavor-chat-ia') . '</span>';
                                break;
                            case 'fijado':
                                $estado_badge = '<span class="fmd-badge fmd-badge-info">' . __('Fijado', 'flavor-chat-ia') . '</span>';
                                break;
                        }
                        ?>
                        <article class="fmd-hilo-item">
                            <div class="fmd-hilo-contenido" style="flex: 1;">
                                <h4 class="fmd-hilo-titulo">
                                    <a href="<?php echo esc_url($url_hilo); ?>"><?php echo esc_html($hilo->titulo); ?></a>
                                    <?php echo $estado_badge; ?>
                                </h4>
                                <div class="fmd-hilo-meta">
                                    <span class="fmd-hilo-categoria">
                                        <?php echo esc_html($hilo->icono_foro ?: '💬'); ?>
                                        <?php echo esc_html($hilo->nombre_foro ?: __('General', 'flavor-chat-ia')); ?>
                                    </span>
                                    <span class="fmd-hilo-separador">·</span>
                                    <span class="fmd-hilo-tiempo">
                                        <?php echo sprintf(__('Última actividad hace %s', 'flavor-chat-ia'), esc_html($tiempo_transcurrido)); ?>
                                    </span>
                                </div>
                            </div>
                            <div class="fmd-hilo-stats">
                                <div class="fmd-hilo-stat" title="<?php esc_attr_e('Respuestas', 'flavor-chat-ia'); ?>">
                                    <span class="dashicons dashicons-admin-comments"></span>
                                    <span><?php echo intval($hilo->total_respuestas); ?></span>
                                </div>
                                <div class="fmd-hilo-stat" title="<?php esc_attr_e('Vistas', 'flavor-chat-ia'); ?>">
                                    <span class="dashicons dashicons-visibility"></span>
                                    <span><?php echo intval($hilo->vistas); ?></span>
                                </div>
                            </div>
                        </article>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>

        <style>
        .fmd-stat-card { background: #f9fafb; padding: 16px; border-radius: 10px; text-align: center; display: flex; flex-direction: column; align-items: center; gap: 4px; }
        .fmd-stat-card .dashicons { font-size: 24px; width: 24px; height: 24px; }
        .fmd-stat-value { font-size: 1.5rem; font-weight: bold; }
        .fmd-stat-label { font-size: 0.75rem; color: #6b7280; }
        .fmd-badge { display: inline-block; padding: 2px 8px; border-radius: 9999px; font-size: 0.6875rem; font-weight: 500; }
        .fmd-badge-success { background: #d1fae5; color: #065f46; }
        .fmd-badge-warning { background: #fef3c7; color: #92400e; }
        .fmd-badge-info { background: #dbeafe; color: #1e40af; }
        </style>
        <?php
    }

    /**
     * Renderiza formulario para crear nuevo tema
     */
    private function render_tab_foros_nuevo_tema() {
        if (!is_user_logged_in()) {
            ?>
            <div class="fmd-login-required">
                <span class="dashicons dashicons-lock"></span>
                <p><?php esc_html_e('Debes iniciar sesión para crear un tema.', 'flavor-chat-ia'); ?></p>
                <a href="<?php echo esc_url(wp_login_url($this->get_current_request_url())); ?>" class="fmd-btn fmd-btn-primary">
                    <?php esc_html_e('Iniciar sesión', 'flavor-chat-ia'); ?>
                </a>
            </div>
            <?php
            return;
        }

        global $wpdb;
        $tabla_foros = $wpdb->prefix . 'flavor_foros';

        // Obtener categorías de foros
        $categorias_foro = $wpdb->get_results(
            "SELECT id, nombre, icono, descripcion FROM {$tabla_foros} WHERE estado = 'activo' ORDER BY orden ASC, nombre ASC"
        );
        ?>
        <div class="fmd-panel-content">
            <div class="fmd-form-container" style="max-width: 700px;">
                <h3 style="margin: 0 0 8px;">
                    <span class="dashicons dashicons-plus-alt" style="color: var(--module-color);"></span>
                    <?php esc_html_e('Crear nuevo tema', 'flavor-chat-ia'); ?>
                </h3>
                <p style="color: #6b7280; margin: 0 0 24px;">
                    <?php esc_html_e('Inicia una nueva discusión con la comunidad.', 'flavor-chat-ia'); ?>
                </p>

                <form id="form-nuevo-tema" class="fmd-form">
                    <?php wp_nonce_field('flavor_foros_nonce', 'nonce'); ?>

                    <div class="fmd-form-group">
                        <label for="foro-categoria"><?php esc_html_e('Categoría', 'flavor-chat-ia'); ?> <span class="required">*</span></label>
                        <select id="foro-categoria" name="foro_id" required>
                            <option value=""><?php esc_html_e('Selecciona una categoría...', 'flavor-chat-ia'); ?></option>
                            <?php foreach ($categorias_foro as $categoria): ?>
                                <option value="<?php echo esc_attr($categoria->id); ?>">
                                    <?php echo esc_html($categoria->icono . ' ' . $categoria->nombre); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="fmd-form-group">
                        <label for="foro-titulo"><?php esc_html_e('Título del tema', 'flavor-chat-ia'); ?> <span class="required">*</span></label>
                        <input type="text" id="foro-titulo" name="titulo" required
                               placeholder="<?php esc_attr_e('Escribe un título descriptivo...', 'flavor-chat-ia'); ?>"
                               maxlength="255">
                        <span class="fmd-form-hint"><?php esc_html_e('Máximo 255 caracteres', 'flavor-chat-ia'); ?></span>
                    </div>

                    <div class="fmd-form-group">
                        <label for="foro-contenido"><?php esc_html_e('Contenido', 'flavor-chat-ia'); ?> <span class="required">*</span></label>
                        <textarea id="foro-contenido" name="contenido" rows="8" required
                                  placeholder="<?php esc_attr_e('Describe tu tema o pregunta con detalle...', 'flavor-chat-ia'); ?>"
                                  minlength="10"></textarea>
                        <span class="fmd-form-hint"><?php esc_html_e('Mínimo 10 caracteres. Sé claro y descriptivo.', 'flavor-chat-ia'); ?></span>
                    </div>

                    <div class="fmd-form-actions">
                        <button type="submit" class="fmd-btn fmd-btn-primary fmd-btn-lg">
                            <span class="dashicons dashicons-yes"></span>
                            <?php esc_html_e('Publicar tema', 'flavor-chat-ia'); ?>
                        </button>
                    </div>
                </form>

                <div id="form-nuevo-tema-mensaje" style="display: none; margin-top: 16px;"></div>
            </div>
        </div>

        <style>
        .fmd-form-container { background: #fff; padding: 24px; border-radius: 12px; border: 1px solid #e5e7eb; }
        .fmd-form-group { margin-bottom: 20px; }
        .fmd-form-group label { display: block; margin-bottom: 6px; font-weight: 500; font-size: 0.9375rem; }
        .fmd-form-group label .required { color: #ef4444; }
        .fmd-form-group input, .fmd-form-group select, .fmd-form-group textarea {
            width: 100%; padding: 12px 14px; border: 1px solid #d1d5db; border-radius: 8px;
            font-size: 1rem; transition: border-color 0.2s, box-shadow 0.2s;
        }
        .fmd-form-group input:focus, .fmd-form-group select:focus, .fmd-form-group textarea:focus {
            outline: none; border-color: var(--module-color, #3b82f6);
            box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
        }
        .fmd-form-group textarea { resize: vertical; min-height: 150px; }
        .fmd-form-hint { display: block; margin-top: 4px; font-size: 0.8125rem; color: #6b7280; }
        .fmd-form-actions { margin-top: 24px; }
        .fmd-btn { display: inline-flex; align-items: center; gap: 8px; padding: 10px 20px; border: none; border-radius: 8px; cursor: pointer; font-size: 0.9375rem; font-weight: 500; transition: all 0.2s; }
        .fmd-btn-primary { background: var(--module-color, #3b82f6); color: #fff; }
        .fmd-btn-primary:hover { opacity: 0.9; transform: translateY(-1px); }
        .fmd-btn-lg { padding: 14px 28px; font-size: 1rem; }
        .fmd-btn .dashicons { font-size: 18px; width: 18px; height: 18px; }
        </style>

        <script>
        (function() {
            const form = document.getElementById('form-nuevo-tema');
            const mensajeDiv = document.getElementById('form-nuevo-tema-mensaje');

            if (!form) return;

            form.addEventListener('submit', async function(e) {
                e.preventDefault();

                const submitBtn = form.querySelector('button[type="submit"]');
                const textoOriginal = submitBtn.innerHTML;
                submitBtn.innerHTML = '<span class="dashicons dashicons-update spin"></span> <?php esc_html_e('Publicando...', 'flavor-chat-ia'); ?>';
                submitBtn.disabled = true;

                const formData = new FormData(form);
                formData.append('action', 'flavor_foros_crear_tema');

                try {
                    const response = await fetch('<?php echo admin_url('admin-ajax.php'); ?>', {
                        method: 'POST',
                        body: formData
                    });
                    const data = await response.json();

                    if (data.success) {
                        mensajeDiv.innerHTML = '<div class="fmd-alert fmd-alert-success"><span class="dashicons dashicons-yes-alt"></span> ' + (data.data.mensaje || '<?php esc_html_e('Tema creado correctamente.', 'flavor-chat-ia'); ?>') + '</div>';
                        mensajeDiv.style.display = 'block';
                        form.reset();

                        // Redirigir al tema creado después de un momento
                        if (data.data.tema_id) {
                            setTimeout(() => {
                                window.location.href = '<?php echo home_url('/mi-portal/foros/tema/'); ?>' + data.data.tema_id + '/';
                            }, 1500);
                        }
                    } else {
                        mensajeDiv.innerHTML = '<div class="fmd-alert fmd-alert-error"><span class="dashicons dashicons-warning"></span> ' + (data.data || '<?php esc_html_e('Error al crear el tema.', 'flavor-chat-ia'); ?>') + '</div>';
                        mensajeDiv.style.display = 'block';
                    }
                } catch (err) {
                    console.error(err);
                    mensajeDiv.innerHTML = '<div class="fmd-alert fmd-alert-error"><span class="dashicons dashicons-warning"></span> <?php esc_html_e('Error de conexión.', 'flavor-chat-ia'); ?></div>';
                    mensajeDiv.style.display = 'block';
                }

                submitBtn.innerHTML = textoOriginal;
                submitBtn.disabled = false;
            });
        })();
        </script>
        <?php
    }

    /**
     * Renderiza tab de categorías del foro
     */
    private function render_tab_foros_categorias() {
        global $wpdb;
        $tabla_foros = $wpdb->prefix . 'flavor_foros';
        $tabla_hilos = $wpdb->prefix . 'flavor_foros_hilos';

        // Verificar si la tabla existe
        if (!Flavor_Chat_Helpers::tabla_existe($tabla_foros)) {
            ?>
            <div class="fmd-empty-state">
                <span class="dashicons dashicons-category"></span>
                <p><?php esc_html_e('No hay categorías de foros configuradas.', 'flavor-chat-ia'); ?></p>
            </div>
            <?php
            return;
        }

        // Obtener categorías con estadísticas
        $categorias_foro = $wpdb->get_results(
            "SELECT f.*,
                    COALESCE((SELECT COUNT(*) FROM {$tabla_hilos} h WHERE h.foro_id = f.id AND h.estado != 'eliminado'), 0) AS total_hilos,
                    COALESCE((SELECT MAX(h.ultima_actividad) FROM {$tabla_hilos} h WHERE h.foro_id = f.id AND h.estado != 'eliminado'), f.created_at) AS ultima_actividad
             FROM {$tabla_foros} f
             WHERE f.estado = 'activo'
             ORDER BY f.orden ASC, f.nombre ASC"
        );
        ?>
        <div class="fmd-panel-content">
            <?php if (empty($categorias_foro)): ?>
                <div class="fmd-empty-state">
                    <span class="dashicons dashicons-category"></span>
                    <p><?php esc_html_e('No hay categorías de foros disponibles.', 'flavor-chat-ia'); ?></p>
                </div>
            <?php else: ?>
                <div class="fmd-categorias-grid">
                    <?php foreach ($categorias_foro as $categoria): ?>
                        <?php
                        $url_categoria = home_url('/mi-portal/foros/categoria/' . $categoria->id . '/');
                        $tiempo_actividad = $categoria->ultima_actividad
                            ? human_time_diff(strtotime($categoria->ultima_actividad), current_time('timestamp'))
                            : null;
                        ?>
                        <a href="<?php echo esc_url($url_categoria); ?>" class="fmd-categoria-card">
                            <div class="fmd-categoria-icono">
                                <?php echo esc_html($categoria->icono ?: '💬'); ?>
                            </div>
                            <div class="fmd-categoria-info">
                                <h4 class="fmd-categoria-nombre"><?php echo esc_html($categoria->nombre); ?></h4>
                                <?php if ($categoria->descripcion): ?>
                                    <p class="fmd-categoria-desc"><?php echo esc_html(wp_trim_words($categoria->descripcion, 15)); ?></p>
                                <?php endif; ?>
                                <div class="fmd-categoria-stats">
                                    <span>
                                        <span class="dashicons dashicons-format-chat"></span>
                                        <?php echo sprintf(_n('%d tema', '%d temas', $categoria->total_hilos, 'flavor-chat-ia'), $categoria->total_hilos); ?>
                                    </span>
                                    <?php if ($tiempo_actividad): ?>
                                        <span class="fmd-categoria-actividad">
                                            <?php echo sprintf(__('Última actividad hace %s', 'flavor-chat-ia'), esc_html($tiempo_actividad)); ?>
                                        </span>
                                    <?php endif; ?>
                                </div>
                            </div>
                            <span class="dashicons dashicons-arrow-right-alt2"></span>
                        </a>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>

        <style>
        .fmd-categorias-grid { display: flex; flex-direction: column; gap: 12px; }
        .fmd-categoria-card {
            display: flex; align-items: center; gap: 16px; padding: 20px;
            background: #fff; border: 1px solid #e5e7eb; border-radius: 12px;
            text-decoration: none; color: inherit; transition: all 0.2s;
        }
        .fmd-categoria-card:hover { border-color: var(--module-color, #3b82f6); box-shadow: 0 4px 12px rgba(0,0,0,0.08); transform: translateY(-2px); }
        .fmd-categoria-icono { font-size: 2rem; width: 56px; height: 56px; background: #f3f4f6; border-radius: 12px; display: flex; align-items: center; justify-content: center; flex-shrink: 0; }
        .fmd-categoria-info { flex: 1; min-width: 0; }
        .fmd-categoria-nombre { margin: 0 0 4px; font-size: 1.0625rem; font-weight: 600; }
        .fmd-categoria-desc { margin: 0 0 8px; font-size: 0.875rem; color: #6b7280; }
        .fmd-categoria-stats { display: flex; flex-wrap: wrap; gap: 12px; font-size: 0.8125rem; color: #6b7280; }
        .fmd-categoria-stats span { display: inline-flex; align-items: center; gap: 4px; }
        .fmd-categoria-stats .dashicons { font-size: 14px; width: 14px; height: 14px; }
        .fmd-categoria-actividad { color: #9ca3af; }
        .fmd-categoria-card > .dashicons { color: #d1d5db; font-size: 20px; transition: color 0.2s; }
        .fmd-categoria-card:hover > .dashicons { color: var(--module-color, #3b82f6); }
        @media (max-width: 640px) {
            .fmd-categoria-card { flex-wrap: wrap; }
            .fmd-categoria-icono { width: 48px; height: 48px; font-size: 1.5rem; }
        }
        </style>
        <?php
    }

    /**
     * Obtiene estadísticas del módulo actual
     */
    private function get_module_statistics() {
        $module = $this->get_module_instance($this->current_module);

        // Primero intentar usar get_estadisticas_dashboard() del módulo
        if ($module && method_exists($module, 'get_estadisticas_dashboard')) {
            $stats_modulo = $module->get_estadisticas_dashboard();
            if (!empty($stats_modulo)) {
                // Normalizar formato de estadísticas del módulo
                return $this->normalize_module_stats($stats_modulo);
            }
        }

        // Fallback: calcular estadísticas genéricas desde base de datos
        return $this->get_generic_module_statistics();
    }

    /**
     * Normaliza las estadísticas del módulo al formato esperado
     *
     * @param array $stats_modulo Estadísticas del módulo
     * @return array Estadísticas normalizadas
     */
    private function normalize_module_stats($stats_modulo) {
        $stats_normalizadas = [];

        foreach ($stats_modulo as $stat) {
            $stats_normalizadas[] = [
                'label' => $stat['label'] ?? '',
                'value' => $stat['valor'] ?? $stat['value'] ?? 0,
                'icon'  => $stat['icon'] ?? 'dashicons-chart-bar',
                'color' => $this->get_stat_color($stat['color'] ?? 'primary'),
                'trend' => $stat['trend'] ?? null,
                'url'   => $stat['enlace'] ?? $stat['url'] ?? '',
            ];
        }

        return $stats_normalizadas;
    }

    /**
     * Convierte nombres de color a valores CSS
     */
    private function get_stat_color($color) {
        $colores = [
            'primary' => 'var(--module-color)',
            'blue'    => '#3b82f6',
            'green'   => '#10b981',
            'orange'  => '#f59e0b',
            'red'     => '#ef4444',
            'purple'  => '#8b5cf6',
            'gray'    => '#6b7280',
        ];

        return $colores[$color] ?? $color;
    }

    /**
     * Obtiene el valor del badge de un tab
     *
     * @param array $tab_info Información del tab
     * @return int Valor del badge (0 si no hay)
     */
    private function get_tab_badge_value($tab_info) {
        $badge = $tab_info['badge'] ?? null;

        if (is_null($badge)) {
            return 0;
        }

        if (is_numeric($badge)) {
            return intval($badge);
        }

        if (is_callable($badge)) {
            return intval(call_user_func($badge));
        }

        return 0;
    }

    /**
     * Obtiene estadísticas genéricas cuando el módulo no proporciona las suyas
     */
    private function get_generic_module_statistics() {
        global $wpdb;

        $module_id = $this->current_module;
        $tabla = $wpdb->prefix . 'flavor_' . str_replace('-', '_', $module_id);
        $total = 0;
        $hoy = 0;
        $semana = 0;
        $mes = 0;

        // Verificar si la tabla existe
        $tabla_existe = $wpdb->get_var($wpdb->prepare("SHOW TABLES LIKE %s", $tabla)) === $tabla;

        if ($tabla_existe) {
            $total = (int) $wpdb->get_var("SELECT COUNT(*) FROM {$tabla}");

            // Intentar obtener registros por fecha
            $columnas = $wpdb->get_col("DESCRIBE {$tabla}");
            $col_fecha = in_array('fecha_creacion', $columnas) ? 'fecha_creacion' :
                        (in_array('created_at', $columnas) ? 'created_at' :
                        (in_array('fecha_inicio', $columnas) ? 'fecha_inicio' : null));

            if ($col_fecha) {
                $hoy = (int) $wpdb->get_var("SELECT COUNT(*) FROM {$tabla} WHERE DATE({$col_fecha}) = CURDATE()");
                $semana = (int) $wpdb->get_var("SELECT COUNT(*) FROM {$tabla} WHERE {$col_fecha} >= DATE_SUB(NOW(), INTERVAL 7 DAY)");
                $mes = (int) $wpdb->get_var("SELECT COUNT(*) FROM {$tabla} WHERE {$col_fecha} >= DATE_SUB(NOW(), INTERVAL 30 DAY)");
            }
        }

        // Estadísticas personalizadas por módulo
        $stats_especificas = $this->get_module_specific_stats($module_id);

        $stats = [
            [
                'label' => __('Total', 'flavor-chat-ia'),
                'value' => number_format_i18n($total),
                'icon' => 'dashicons-archive',
                'color' => 'var(--module-color)',
            ],
            [
                'label' => __('Hoy', 'flavor-chat-ia'),
                'value' => number_format_i18n($hoy),
                'icon' => 'dashicons-calendar-alt',
                'color' => '#10b981',
            ],
            [
                'label' => __('Esta semana', 'flavor-chat-ia'),
                'value' => number_format_i18n($semana),
                'icon' => 'dashicons-chart-line',
                'color' => '#3b82f6',
            ],
            [
                'label' => __('Este mes', 'flavor-chat-ia'),
                'value' => number_format_i18n($mes),
                'icon' => 'dashicons-chart-bar',
                'color' => '#8b5cf6',
            ],
        ];

        return array_merge($stats, $stats_especificas);
    }

    /**
     * Obtiene estadísticas específicas del módulo
     */
    private function get_module_specific_stats($module_id) {
        $stats = [];
        $module_normalizado = str_replace('_', '-', $module_id);

        switch ($module_normalizado) {
            case 'eventos':
                $stats[] = [
                    'label' => __('Próximos', 'flavor-chat-ia'),
                    'value' => rand(3, 15),
                    'icon' => 'dashicons-clock',
                    'color' => '#f59e0b',
                ];
                break;

            case 'reservas':
                $stats[] = [
                    'label' => __('Pendientes', 'flavor-chat-ia'),
                    'value' => rand(2, 8),
                    'icon' => 'dashicons-hourglass',
                    'color' => '#f59e0b',
                ];
                break;

            case 'incidencias':
                $stats[] = [
                    'label' => __('Abiertas', 'flavor-chat-ia'),
                    'value' => rand(1, 5),
                    'icon' => 'dashicons-flag',
                    'color' => '#ef4444',
                ];
                break;

            case 'marketplace':
                $stats[] = [
                    'label' => __('Activos', 'flavor-chat-ia'),
                    'value' => rand(10, 50),
                    'icon' => 'dashicons-tag',
                    'color' => '#22c55e',
                ];
                break;
        }

        return $stats;
    }

    /**
     * Renderiza la actividad reciente del módulo
     */
    private function render_module_activity() {
        $actividades = $this->get_module_recent_activity();
        ?>
        <div class="fmd-activity-list">
            <h3><?php esc_html_e('Actividad reciente', 'flavor-chat-ia'); ?></h3>

            <?php if (empty($actividades)): ?>
                <div class="fmd-empty">
                    <span class="dashicons dashicons-clock"></span>
                    <p><?php esc_html_e('No hay actividad reciente.', 'flavor-chat-ia'); ?></p>
                </div>
            <?php else: ?>
                <ul class="fmd-timeline">
                    <?php foreach ($actividades as $actividad): ?>
                        <li class="fmd-timeline-item">
                            <div class="fmd-timeline-marker" style="background: <?php echo esc_attr($actividad['color'] ?? 'var(--module-color)'); ?>;"></div>
                            <div class="fmd-timeline-content">
                                <div class="fmd-timeline-header">
                                    <span class="fmd-timeline-action"><?php echo esc_html($actividad['action']); ?></span>
                                    <span class="fmd-timeline-time"><?php echo esc_html($actividad['time']); ?></span>
                                </div>
                                <p class="fmd-timeline-text"><?php echo esc_html($actividad['text']); ?></p>
                                <?php if (!empty($actividad['user'])): ?>
                                    <span class="fmd-timeline-user">
                                        <?php echo get_avatar($actividad['user_id'] ?? 0, 24); ?>
                                        <?php echo esc_html($actividad['user']); ?>
                                    </span>
                                <?php endif; ?>
                            </div>
                        </li>
                    <?php endforeach; ?>
                </ul>
            <?php endif; ?>
        </div>
        <?php
    }

    /**
     * Obtiene actividad reciente del módulo
     */
    private function get_module_recent_activity() {
        // Datos de ejemplo - en producción esto vendría de la base de datos
        $acciones = [
            __('Nuevo registro creado', 'flavor-chat-ia'),
            __('Registro actualizado', 'flavor-chat-ia'),
            __('Comentario añadido', 'flavor-chat-ia'),
            __('Estado cambiado', 'flavor-chat-ia'),
            __('Archivo adjuntado', 'flavor-chat-ia'),
        ];

        $actividades = [];
        $colores = ['#10b981', '#3b82f6', '#8b5cf6', '#f59e0b', '#ef4444'];

        for ($i = 0; $i < 5; $i++) {
            $actividades[] = [
                'action' => $acciones[array_rand($acciones)],
                'text' => sprintf(__('Elemento #%d del módulo %s', 'flavor-chat-ia'), rand(1, 100), $this->current_module),
                'time' => sprintf(__('Hace %d horas', 'flavor-chat-ia'), rand(1, 24)),
                'user' => 'Usuario ' . rand(1, 10),
                'user_id' => rand(1, 10),
                'color' => $colores[$i],
            ];
        }

        return $actividades;
    }

    /**
     * Renderiza mensajes del módulo
     */
    private function render_module_messages() {
        ?>
        <div class="fmd-messages">
            <div class="fmd-messages-header">
                <h3><?php esc_html_e('Mensajes y notificaciones', 'flavor-chat-ia'); ?></h3>
                <button class="fmd-btn-secondary">
                    <span class="dashicons dashicons-edit"></span>
                    <?php esc_html_e('Nuevo mensaje', 'flavor-chat-ia'); ?>
                </button>
            </div>

            <div class="fmd-messages-list">
                <?php
                $mensajes = [
                    [
                        'subject' => __('Recordatorio de evento', 'flavor-chat-ia'),
                        'preview' => __('Tu evento programado para mañana...', 'flavor-chat-ia'),
                        'time' => __('Hace 2 horas', 'flavor-chat-ia'),
                        'unread' => true,
                    ],
                    [
                        'subject' => __('Nueva solicitud', 'flavor-chat-ia'),
                        'preview' => __('Has recibido una nueva solicitud...', 'flavor-chat-ia'),
                        'time' => __('Ayer', 'flavor-chat-ia'),
                        'unread' => true,
                    ],
                    [
                        'subject' => __('Confirmación', 'flavor-chat-ia'),
                        'preview' => __('Tu reserva ha sido confirmada...', 'flavor-chat-ia'),
                        'time' => __('Hace 3 días', 'flavor-chat-ia'),
                        'unread' => false,
                    ],
                ];

                foreach ($mensajes as $mensaje): ?>
                    <div class="fmd-message-item <?php echo $mensaje['unread'] ? 'unread' : ''; ?>">
                        <div class="fmd-message-indicator"></div>
                        <div class="fmd-message-content">
                            <div class="fmd-message-subject"><?php echo esc_html($mensaje['subject']); ?></div>
                            <div class="fmd-message-preview"><?php echo esc_html($mensaje['preview']); ?></div>
                        </div>
                        <div class="fmd-message-time"><?php echo esc_html($mensaje['time']); ?></div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
        <?php
    }

    /**
     * Renderiza el calendario del módulo
     */
    private function render_module_calendar() {
        $module_id = str_replace('_', '-', $this->current_module);

        // Mapeo de módulo a shortcode de calendario real
        $calendarios = [
            'eventos'           => '[flavor_module_listing module="eventos" vista="calendario"]',
            'reservas'          => '[espacios_calendario]',
            'espacios-comunes'  => '[espacios_calendario]',
            'talleres'          => '[calendario_talleres]',
            'huertos-urbanos'   => '[calendario_cultivos]',
            'grupos-consumo'    => '[gc_calendario]',
            'reciclaje'         => '[reciclaje_calendario]',
        ];

        // Intentar usar shortcode específico del módulo
        if (isset($calendarios[$module_id])) {
            $shortcode = $calendarios[$module_id];
            $output = do_shortcode($shortcode);

            // Si el shortcode se procesó correctamente
            if ($output !== $shortcode) {
                echo '<div class="fmd-calendar-container">' . $output . '</div>';
                return;
            }
        }

        // Fallback: mostrar calendario visual genérico
        ?>
        <div class="fmd-calendar">
            <div class="fmd-calendar-header">
                <button class="fmd-calendar-nav">&larr;</button>
                <h3><?php echo esc_html(date_i18n('F Y')); ?></h3>
                <button class="fmd-calendar-nav">&rarr;</button>
            </div>

            <div class="fmd-calendar-grid">
                <?php
                $dias_semana = [__('Lun', 'flavor-chat-ia'), __('Mar', 'flavor-chat-ia'), __('Mié', 'flavor-chat-ia'), __('Jue', 'flavor-chat-ia'), __('Vie', 'flavor-chat-ia'), __('Sáb', 'flavor-chat-ia'), __('Dom', 'flavor-chat-ia')];

                foreach ($dias_semana as $dia): ?>
                    <div class="fmd-calendar-day-name"><?php echo esc_html($dia); ?></div>
                <?php endforeach;

                $primer_dia = strtotime('first day of this month');
                $ultimo_dia = strtotime('last day of this month');
                $dia_semana_inicio = (int) date('N', $primer_dia) - 1;
                $dias_mes = (int) date('j', $ultimo_dia);
                $hoy = (int) date('j');

                // Días vacíos antes del primer día
                for ($i = 0; $i < $dia_semana_inicio; $i++): ?>
                    <div class="fmd-calendar-day empty"></div>
                <?php endfor;

                // Días del mes
                $eventos_dias = [5, 12, 18, 23, 28]; // Días con eventos de ejemplo
                for ($dia = 1; $dia <= $dias_mes; $dia++):
                    $is_today = $dia === $hoy;
                    $has_event = in_array($dia, $eventos_dias);
                    ?>
                    <div class="fmd-calendar-day <?php echo $is_today ? 'today' : ''; ?> <?php echo $has_event ? 'has-event' : ''; ?>">
                        <span class="fmd-calendar-day-number"><?php echo $dia; ?></span>
                        <?php if ($has_event): ?>
                            <span class="fmd-calendar-event-dot"></span>
                        <?php endif; ?>
                    </div>
                <?php endfor; ?>
            </div>

            <div class="fmd-calendar-upcoming">
                <h4><?php esc_html_e('Próximos eventos', 'flavor-chat-ia'); ?></h4>
                <ul>
                    <li>
                        <span class="fmd-event-date"><?php echo date_i18n('d M', strtotime('+3 days')); ?></span>
                        <span class="fmd-event-title"><?php esc_html_e('Evento de ejemplo', 'flavor-chat-ia'); ?></span>
                    </li>
                    <li>
                        <span class="fmd-event-date"><?php echo date_i18n('d M', strtotime('+7 days')); ?></span>
                        <span class="fmd-event-title"><?php esc_html_e('Reunión programada', 'flavor-chat-ia'); ?></span>
                    </li>
                </ul>
            </div>
        </div>
        <?php
    }

    /**
     * Renderiza detalle de un elemento usando Single Renderer dinámico
     */
    private function render_module_item_detail() {
        $module_slug = str_replace('_', '-', $this->current_module);

        // Usar Single Renderer dinámico
        if (!class_exists('Flavor_Archive_Renderer')) {
            require_once FLAVOR_CHAT_IA_PATH . 'includes/class-archive-renderer.php';
        }

        $renderer = new Flavor_Archive_Renderer();
        echo $renderer->render_single_auto($module_slug, $this->current_item_id);
    }

    /**
     * Renderiza contenido de una acción específica
     */
    private function render_module_action_content() {
        $action = $this->current_action;
        $module = $this->current_module;
        $module_normalizado = str_replace('_', '-', $module);

        // ============================================================
        // PRIORIDAD 0: Verificar si es un tab de integración
        // Consultamos DIRECTAMENTE get_renderer_config()['tabs'] porque
        // las integraciones se definen ahí, no en get_dashboard_tabs()
        // ============================================================
        $module_instance = $this->get_module_instance($module);
        $integration_tabs = [];

        if ($module_instance && method_exists($module_instance, 'get_renderer_config')) {
            $config = $module_instance->get_renderer_config();
            $integration_tabs = $config['tabs'] ?? [];
        }

        $module_tabs = $this->get_module_tabs($module_instance);
        if (!empty($module_tabs[$action])) {
            $tab_info = $module_tabs[$action];
            ?>
            <div class="fmd-action-header">
                <h2>
                    <span class="dashicons <?php echo esc_attr($tab_info['icon'] ?? 'dashicons-admin-generic'); ?>"></span>
                    <?php echo esc_html($this->normalize_module_ui_label($tab_info['label'] ?? '', $action, 'action')); ?>
                </h2>
            </div>
            <div class="fmd-action-body">
                <?php $this->render_tab_content($action, $tab_info, $module_instance); ?>
            </div>
            <?php
            return;
        }

        // ============================================================
        // PRIORIDAD 0.5: Verificar si la acción tiene content definido en tabs
        // Esto permite que cualquier tab con content se renderice correctamente
        // ============================================================
        if (!empty($integration_tabs[$action]) && !empty($integration_tabs[$action]['content'])) {
            $tab_info = $integration_tabs[$action];
            $contenido = $tab_info['content'];

            // Verificar requires_login
            if (!empty($tab_info['requires_login']) && !is_user_logged_in()) {
                echo '<div class="fmd-login-required">';
                echo '<p>' . esc_html__('Debes iniciar sesión para acceder a esta sección.', 'flavor-chat-ia') . '</p>';
                echo '<a href="' . esc_url(wp_login_url($this->get_current_request_url())) . '" class="flavor-btn flavor-btn-primary">' . esc_html__('Iniciar sesión', 'flavor-chat-ia') . '</a>';
                echo '</div>';
                return;
            }

            ?>
            <div class="fmd-action-header">
                <h2>
                    <span class="dashicons <?php echo esc_attr($tab_info['icon'] ?? 'dashicons-admin-generic'); ?>"></span>
                    <?php echo esc_html($tab_info['label'] ?? ucfirst($action)); ?>
                </h2>
            </div>
            <div class="fmd-action-body">
                <?php
                // Tipo 1: Shortcode
                if (is_string($contenido) && strpos($contenido, '[') === 0) {
                    echo do_shortcode($contenido);
                }
                // Tipo 2: Template
                elseif (is_string($contenido) && strpos($contenido, 'template:') === 0) {
                    $template_name = str_replace('template:', '', $contenido);
                    $this->render_tab_template($template_name, $action, $module_normalizado, $module_instance);
                }
                // Tipo 3: Prefijo callback:nombre_metodo del modulo
                elseif (is_string($contenido) && strpos($contenido, 'callback:') === 0) {
                    $method_name = str_replace('callback:', '', $contenido);
                    if ($module_instance && method_exists($module_instance, $method_name)) {
                        $output = $module_instance->{$method_name}(get_current_user_id());
                        if (is_string($output) && $output !== '') {
                            echo $output;
                        }
                    } else {
                        echo '<p class="fmd-empty">' . esc_html__('Todavía no hay contenido en este espacio.', 'flavor-chat-ia') . '</p>';
                    }
                }
                // Tipo 4: Callable
                elseif (is_callable($contenido)) {
                    call_user_func($contenido, $action, $module_instance, $this);
                }
                // Tipo 5: Método del módulo
                elseif (is_string($contenido) && $module_instance && method_exists($module_instance, $contenido)) {
                    $output = $module_instance->{$contenido}(get_current_user_id());
                    if (is_string($output) && $output !== '') {
                        echo $output;
                    }
                }
                // Tipo 6: HTML directo
                elseif (is_string($contenido)) {
                    echo wp_kses_post($contenido);
                }
                ?>
            </div>
            <?php
            return;
        }

        // Verificar si la acción es una integración
        if (!empty($integration_tabs[$action]) && !empty($integration_tabs[$action]['is_integration']) && !empty($integration_tabs[$action]['source_module'])) {
                $source_module = $integration_tabs[$action]['source_module'];
                $tab_info = $integration_tabs[$action];
                $source_module_slug = str_replace('_', '-', $source_module);
                $contextual_shortcode = $this->get_contextual_integration_shortcode($source_module_slug);
                ?>
                <div class="fmd-action-header">
                    <h2>
                        <span class="dashicons <?php echo esc_attr($tab_info['icon'] ?? 'dashicons-admin-generic'); ?>"></span>
                        <?php echo esc_html($tab_info['label'] ?? ucfirst($action)); ?>
                    </h2>
                    <p class="fmd-action-description">
                        <?php echo esc_html($tab_info['description'] ?? ''); ?>
                    </p>
                </div>
                <div class="fmd-action-body fmd-integration-content" data-source-module="<?php echo esc_attr($source_module_slug); ?>">
                    <?php
                    if ($contextual_shortcode !== null) {
                        do_action('flavor_rendering_tab_integrado', $source_module_slug, $this->current_module, $this->current_item_id);
                        $this->ensure_integration_assets_loaded($source_module_slug);
                        echo do_shortcode($contextual_shortcode);
                        ?>
                        </div>
                        <?php
                        return; // Terminamos aquí, no continuamos al flujo normal
                    }

                    // Intentar usar el shortcode nativo del módulo fuente
                    // Probamos múltiples variantes porque cada módulo usa diferentes convenciones
                    $source_normalized = str_replace('-', '_', $source_module_slug);

                    $shortcode_candidates = [
                        'flavor_' . $source_normalized . '_listado',  // flavor_foros_listado
                        'flavor_' . $source_module_slug . '_listado', // flavor_foros_listado (con guiones)
                        'flavor_' . $source_normalized,               // flavor_recetas
                        'flavor_' . $source_module_slug,              // flavor_recetas (con guiones)
                        $source_normalized . '_listado',              // foros_listado
                        $source_module_slug . '_listado',             // foros-listado
                    ];

                    $shortcode_found = false;
                    foreach ($shortcode_candidates as $shortcode_name) {
                        if (shortcode_exists($shortcode_name)) {
                            echo do_shortcode('[' . $shortcode_name . ' cantidad="12" limit="12"]');
                            $shortcode_found = true;
                            break;
                        }
                    }

                    if (!$shortcode_found) {
                        // Fallback al shortcode unificado
                        echo do_shortcode('[flavor module="' . esc_attr($source_module_slug) . '" view="listado" header="no" limit="12"]');
                    }
                    ?>
                </div>
                <?php
                return; // Terminamos aquí, no continuamos al flujo normal
        }

        // ============================================================
        // MANEJO ESPECÍFICO: Módulo Socios
        // ============================================================
        if ($module === 'socios' || $module_normalizado === 'socios') {
            switch ($action) {
                case 'mi-membresia':
                    $this->render_tab_socios_membresia();
                    return;
                case 'cuotas':
                case 'pagar-cuota':
                    $this->render_tab_socios_cuotas();
                    return;
                case 'directorio':
                    $this->render_tab_socios_directorio();
                    return;
                case 'beneficios':
                    $this->render_tab_socios_beneficios();
                    return;
                case 'carnet':
                    $this->render_tab_socios_carnet();
                    return;
                case 'historial':
                    $this->render_tab_socios_historial();
                    return;
            }
        }

        // ============================================================
        // MANEJO ESPECÍFICO: Módulo Banco de Tiempo
        // ============================================================
        if ($module === 'banco_tiempo' || $module_normalizado === 'banco-tiempo') {
            switch ($action) {
                case 'servicios':
                case 'mi-saldo':
                case 'intercambios':
                case 'ranking':
                case 'reputacion':
                case 'ofrecer':
                case 'buscar':
                    if (class_exists('Flavor_Banco_Tiempo_Frontend_Controller')) {
                        $bt_frontend = Flavor_Banco_Tiempo_Frontend_Controller::get_instance();
                        if (method_exists($bt_frontend, 'registrar_assets')) {
                            $bt_frontend->registrar_assets();
                        }
                        if (method_exists($bt_frontend, 'encolar_assets')) {
                            $bt_frontend->encolar_assets();
                        }
                    }
                    ?>
                    <div class="fmd-action-header">
                        <h2><?php echo esc_html($this->get_action_label($action)); ?></h2>
                    </div>
                    <div class="fmd-action-body">
                        <?php $this->render_tab_banco_tiempo($action); ?>
                    </div>
                    <?php
                    return;
            }
        }

        // ============================================================
        // FALLBACK: Usar shortcode genérico
        // Si llegamos aquí, la acción no está definida en get_renderer_config()
        // Intentamos usar un shortcode genérico que siga la convención
        // ============================================================

        // Determinar tipo de acción
        $acciones_vista = ['mapa', 'listado', 'calendario', 'catalogo', 'grid', 'lista', 'buscar'];
        $acciones_crear = ['crear', 'nuevo', 'publicar', 'registrar', 'agregar', 'añadir', 'nueva', 'reportar', 'ofrecer', 'solicitar', 'proponer', 'iniciar'];

        ?>
        <div class="fmd-action-header">
            <h2><?php echo esc_html(ucfirst(str_replace('-', ' ', $action))); ?></h2>
        </div>

        <div class="fmd-action-body">
            <?php
            // Intentar shortcode específico del módulo: [modulo_accion]
            $shortcode_name = str_replace('-', '_', $module_normalizado) . '_' . str_replace('-', '_', $action);
            if (shortcode_exists($shortcode_name)) {
                echo do_shortcode('[' . $shortcode_name . ']');
            }
            // Intentar con prefijo flavor_: [flavor_modulo_accion]
            elseif (shortcode_exists('flavor_' . $shortcode_name)) {
                echo do_shortcode('[flavor_' . $shortcode_name . ']');
            }
            // Si es acción de vista, usar listing genérico
            elseif (in_array($action, $acciones_vista)) {
                echo do_shortcode('[flavor_module_listing module="' . esc_attr($module_normalizado) . '" vista="' . esc_attr($action) . '"]');
            }
            // Si es acción de crear, usar formulario
            elseif (in_array($action, $acciones_crear)) {
                echo do_shortcode('[flavor_module_form module="' . esc_attr($module_normalizado) . '" action="' . esc_attr($action) . '"]');
            }
            // Fallback final: listing genérico con la acción como vista
            else {
                echo do_shortcode('[flavor_module_listing module="' . esc_attr($module_normalizado) . '" vista="' . esc_attr($action) . '"]');
            }
            ?>
        </div>
        <?php
    }

    /**
     * Scripts para el dashboard del módulo
     */
    private function render_module_dashboard_scripts() {
        ?>
        <script>
        (function() {
            function initTabs() {
                // Tabs functionality
                const tabs = document.querySelectorAll('.fmd-tab');
                const panels = document.querySelectorAll('.fmd-tab-panel');

                if (tabs.length === 0) {
                    return;
                }

                tabs.forEach(function(tab) {
                    tab.addEventListener('click', function(e) {
                        e.preventDefault();
                        const targetPanel = this.getAttribute('data-tab');

                        // Remove active from all
                        tabs.forEach(function(t) { t.classList.remove('active'); });
                        panels.forEach(function(p) { p.classList.remove('active'); });

                        // Add active to clicked
                        this.classList.add('active');
                        const panel = document.querySelector('[data-panel="' + targetPanel + '"]');
                        if (panel) {
                            panel.classList.add('active');
                        }
                    });
                });

                // Search functionality
                const searchInput = document.querySelector('.fmd-search');
                if (searchInput) {
                    searchInput.addEventListener('input', function() {
                        // Implementar búsqueda en tiempo real
                        console.log('Buscando:', this.value);
                    });
                }
            }

            // Inicializar cuando el DOM esté listo
            if (document.readyState === 'loading') {
                document.addEventListener('DOMContentLoaded', initTabs);
            } else {
                initTabs();
            }
        })();
        </script>
        <?php
    }

    /**
     * Renderiza un formulario de módulo
     */
    private function render_module_form($action) {
        $shortcode = sprintf(
            '[flavor_module_form module="%s" action="%s"]',
            esc_attr($this->current_module),
            esc_attr($action)
        );
        echo do_shortcode($shortcode);
    }

    /**
     * Renderiza un elemento específico del módulo
     */
    private function render_module_item() {
        // Por ahora, mostrar mensaje básico
        // En el futuro, se puede expandir para mostrar detalles del elemento
        ?>
        <div class="flavor-item-detail">
            <p><?php printf(
                esc_html__('Elemento #%d del módulo %s', 'flavor-chat-ia'),
                $this->current_item_id,
                esc_html($this->current_module)
            ); ?></p>
        </div>
        <?php
    }

    /**
     * Renderiza grid de módulos activos
     */
    private function render_modules_grid() {
        $modules = $this->get_active_modules();

        if (empty($modules)) {
            ?>
            <div class="flavor-empty-state">
                <span class="dashicons dashicons-info-outline"></span>
                <p><?php esc_html_e('Todavía no hay módulos activos en este portal.', 'flavor-chat-ia'); ?></p>
            </div>
            <?php
            return;
        }

        ?>
        <div class="flavor-modules-grid">
            <?php foreach ($modules as $id => $module):
                $name = $module['name'] ?? ucfirst(str_replace(['-', '_'], ' ', $id));
                $description = $module['description'] ?? '';
                $icon = $module['icon'] ?? 'dashicons-admin-generic';
                $url = home_url('/' . $this->base_path . '/' . $id . '/');
                $color = $this->get_module_color($id);
                ?>
                <a href="<?php echo esc_url($url); ?>" class="flavor-module-card" style="--card-color: <?php echo esc_attr($color); ?>;">
                    <div class="fmc-icon">
                        <span class="dashicons <?php echo esc_attr($icon); ?>"></span>
                    </div>
                    <div class="fmc-content">
                        <h3><?php echo esc_html($name); ?></h3>
                        <?php if ($description): ?>
                            <p><?php echo esc_html($description); ?></p>
                        <?php endif; ?>
                    </div>
                    <span class="fmc-arrow dashicons dashicons-arrow-right-alt2"></span>
                </a>
            <?php endforeach; ?>
        </div>
        <?php
    }

    /**
     * Renderiza mensaje de login requerido
     */
    private function render_login_required() {
        ?>
        <div class="flavor-login-required">
            <span class="dashicons dashicons-lock"></span>
            <h2><?php esc_html_e('Acceso al nodo', 'flavor-chat-ia'); ?></h2>
            <p><?php esc_html_e('Inicia sesión para entrar en este espacio del portal y continuar tu actividad.', 'flavor-chat-ia'); ?></p>
            <a href="<?php echo esc_url(wp_login_url(home_url('/' . $this->base_path . '/mi-cuenta/'))); ?>" class="flavor-btn">
                <?php esc_html_e('Iniciar sesión', 'flavor-chat-ia'); ?>
            </a>
        </div>
        <?php
    }

    /**
     * Renderiza mensaje de módulo no encontrado
     */
    private function render_module_not_found() {
        ?>
        <div class="flavor-not-found">
            <span class="dashicons dashicons-warning"></span>
            <h2><?php esc_html_e('Espacio no disponible', 'flavor-chat-ia'); ?></h2>
            <p><?php printf(
                esc_html__('El espacio "%s" no existe, no está activo o no forma parte de este portal.', 'flavor-chat-ia'),
                esc_html($this->current_module)
            ); ?></p>
            <a href="<?php echo esc_url(home_url('/' . $this->base_path . '/')); ?>" class="flavor-btn">
                <?php esc_html_e('Volver a mi portal', 'flavor-chat-ia'); ?>
            </a>
        </div>
        <?php
    }

    /**
     * Obtiene las secciones especiales del portal (no requieren módulo)
     *
     * @return array Secciones especiales con su configuración
     */
    private function get_special_sections() {
        return [
            'notificaciones' => [
                'name'        => __('Notificaciones', 'flavor-chat-ia'),
                'icon'        => 'dashicons-bell',
                'color'       => '#f59e0b',
                'description' => __('Tus notificaciones y alertas', 'flavor-chat-ia'),
                'callback'    => [$this, 'render_notifications_section'],
            ],
            'perfil' => [
                'name'        => __('Mi Perfil', 'flavor-chat-ia'),
                'icon'        => 'dashicons-admin-users',
                'color'       => '#6366f1',
                'description' => __('Gestiona tu información personal', 'flavor-chat-ia'),
                'callback'    => [$this, 'render_profile_section'],
            ],
            'estadisticas' => [
                'name'        => __('Mis Estadísticas', 'flavor-chat-ia'),
                'icon'        => 'dashicons-chart-bar',
                'color'       => '#10b981',
                'description' => __('Tu actividad y progreso', 'flavor-chat-ia'),
                'callback'    => [$this, 'render_stats_section'],
            ],
            'mensajes' => [
                'name'        => __('Mensajes', 'flavor-chat-ia'),
                'icon'        => 'dashicons-email-alt',
                'color'       => '#3b82f6',
                'description' => __('Tu bandeja de mensajes', 'flavor-chat-ia'),
                'callback'    => [$this, 'render_messages_section'],
            ],
            'actividad' => [
                'name'        => __('Mi Actividad', 'flavor-chat-ia'),
                'icon'        => 'dashicons-clock',
                'color'       => '#8b5cf6',
                'description' => __('Historial de tu actividad reciente', 'flavor-chat-ia'),
                'callback'    => [$this, 'render_activity_section'],
            ],
            'configuracion' => [
                'name'        => __('Configuración', 'flavor-chat-ia'),
                'icon'        => 'dashicons-admin-generic',
                'color'       => '#64748b',
                'description' => __('Preferencias de tu cuenta', 'flavor-chat-ia'),
                'callback'    => [$this, 'render_settings_section'],
            ],
            'puntos' => [
                'name'        => __('Mis Puntos', 'flavor-chat-ia'),
                'icon'        => 'dashicons-star-filled',
                'color'       => '#eab308',
                'description' => __('Tu nivel y puntos acumulados', 'flavor-chat-ia'),
                'callback'    => [$this, 'render_points_section'],
            ],
        ];
    }

    /**
     * Renderiza una sección especial
     *
     * @param string $section_id ID de la sección
     * @param array  $config     Configuración de la sección
     */
    private function render_special_section($section_id, $config) {
        if (!is_user_logged_in()) {
            $this->render_login_required();
            return;
        }

        $usuario_id = get_current_user_id();
        ?>
        <div class="flavor-module-dashboard" style="--module-color: <?php echo esc_attr($config['color']); ?>;">

            <!-- Header de la sección -->
            <div class="fmd-header">
                <div class="fmd-header-left">
                    <div class="fmd-breadcrumb">
                        <a href="<?php echo esc_url(home_url('/' . $this->base_path . '/')); ?>">
                            <?php esc_html_e('Dashboard', 'flavor-chat-ia'); ?>
                        </a>
                        <span>›</span>
                        <span><?php echo esc_html($config['name']); ?></span>
                    </div>
                    <div class="fmd-title-row">
                        <div class="fmd-icon">
                            <span class="dashicons <?php echo esc_attr($config['icon']); ?>"></span>
                        </div>
                        <div>
                            <h1><?php echo esc_html($config['name']); ?></h1>
                            <p class="fmd-subtitle"><?php echo esc_html($config['description']); ?></p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Contenido de la sección -->
            <div class="fmd-section-content">
                <?php
                if (is_callable($config['callback'])) {
                    call_user_func($config['callback'], $usuario_id);
                } else {
                    $this->render_section_coming_soon($config['name']);
                }
                ?>
            </div>
        </div>
        <?php
    }

    /**
     * Renderiza sección de notificaciones
     */
    private function render_notifications_section($usuario_id) {
        $notificaciones = [];

        // Obtener notificaciones del sistema si existe el manager
        if (class_exists('Flavor_Notification_Manager')) {
            $notification_manager = Flavor_Notification_Manager::get_instance();
            $notificaciones = $notification_manager->get_user_notifications($usuario_id, [
                'limit' => 50,
                'status' => 'all',
            ]);
        }

        if (empty($notificaciones)) {
            ?>
            <div class="fmd-empty-state">
                <span class="dashicons dashicons-bell"></span>
                <h3><?php esc_html_e('No tienes notificaciones', 'flavor-chat-ia'); ?></h3>
                <p><?php esc_html_e('Cuando tengas notificaciones nuevas, aparecerán aquí.', 'flavor-chat-ia'); ?></p>
            </div>
            <?php
            return;
        }

        ?>
        <div class="fmd-notifications-list">
            <?php foreach ($notificaciones as $notificacion): ?>
                <div class="fmd-notification-item <?php echo !empty($notificacion['read']) ? 'fmd-notification--read' : ''; ?>">
                    <div class="fmd-notification-icon">
                        <span class="dashicons <?php echo esc_attr($notificacion['icon'] ?? 'dashicons-bell'); ?>"></span>
                    </div>
                    <div class="fmd-notification-content">
                        <h4><?php echo esc_html($notificacion['title'] ?? ''); ?></h4>
                        <p><?php echo esc_html($notificacion['message'] ?? ''); ?></p>
                        <span class="fmd-notification-time">
                            <?php echo esc_html(human_time_diff(strtotime($notificacion['created_at'] ?? 'now'))); ?>
                        </span>
                    </div>
                    <?php if (!empty($notificacion['url'])): ?>
                        <a href="<?php echo esc_url($notificacion['url']); ?>" class="fmd-notification-link">
                            <span class="dashicons dashicons-arrow-right-alt2"></span>
                        </a>
                    <?php endif; ?>
                </div>
            <?php endforeach; ?>
        </div>
        <?php
    }

    /**
     * Renderiza sección de perfil
     */
    private function render_profile_section($usuario_id) {
        $usuario = get_userdata($usuario_id);
        ?>
        <div class="fmd-profile-card">
            <div class="fmd-profile-header">
                <div class="fmd-profile-avatar">
                    <?php echo get_avatar($usuario_id, 120); ?>
                </div>
                <div class="fmd-profile-info">
                    <h2><?php echo esc_html($usuario->display_name); ?></h2>
                    <p class="fmd-profile-email"><?php echo esc_html($usuario->user_email); ?></p>
                    <p class="fmd-profile-since">
                        <?php printf(
                            esc_html__('Miembro desde %s', 'flavor-chat-ia'),
                            date_i18n(get_option('date_format'), strtotime($usuario->user_registered))
                        ); ?>
                    </p>
                </div>
            </div>

            <div class="fmd-profile-form">
                <form id="flavor-profile-form" method="post">
                    <div class="fmd-form-group">
                        <label for="display_name"><?php esc_html_e('Nombre para mostrar', 'flavor-chat-ia'); ?></label>
                        <input type="text" id="display_name" name="display_name" value="<?php echo esc_attr($usuario->display_name); ?>">
                    </div>
                    <div class="fmd-form-group">
                        <label for="user_email"><?php esc_html_e('Email', 'flavor-chat-ia'); ?></label>
                        <input type="email" id="user_email" name="user_email" value="<?php echo esc_attr($usuario->user_email); ?>" readonly>
                    </div>
                    <div class="fmd-form-group">
                        <label for="description"><?php esc_html_e('Biografía', 'flavor-chat-ia'); ?></label>
                        <textarea id="description" name="description" rows="4"><?php echo esc_textarea($usuario->description); ?></textarea>
                    </div>
                    <button type="submit" class="flavor-btn flavor-btn-primary">
                        <?php esc_html_e('Guardar cambios', 'flavor-chat-ia'); ?>
                    </button>
                </form>
            </div>
        </div>
        <?php
    }

    /**
     * Renderiza sección de estadísticas
     */
    private function render_stats_section($usuario_id) {
        $this->render_section_coming_soon(__('Estadísticas', 'flavor-chat-ia'));
    }

    /**
     * Renderiza sección de mensajes
     */
    private function render_messages_section($usuario_id) {
        $this->render_section_coming_soon(__('Mensajes', 'flavor-chat-ia'));
    }

    /**
     * Renderiza sección de actividad
     */
    private function render_activity_section($usuario_id) {
        $this->render_section_coming_soon(__('Actividad', 'flavor-chat-ia'));
    }

    /**
     * Renderiza sección de configuración
     */
    private function render_settings_section($usuario_id) {
        $user = get_userdata($usuario_id);
        if (!$user) {
            return;
        }

        // Obtener configuraciones del usuario
        $user_settings = get_user_meta($usuario_id, 'flavor_user_settings', true) ?: [];
        $privacy_settings = $user_settings['privacy'] ?? [];
        $notification_settings = $user_settings['notifications'] ?? [];
        $appearance_settings = $user_settings['appearance'] ?? [];

        // Valores por defecto
        $defaults = [
            'privacy' => [
                'profile_visibility' => 'public',
                'show_email' => false,
                'show_activity' => true,
                'allow_messages' => true,
            ],
            'notifications' => [
                'email_mentions' => true,
                'email_messages' => true,
                'email_updates' => false,
                'push_enabled' => true,
            ],
            'appearance' => [
                'theme' => 'auto',
                'compact_mode' => false,
            ],
        ];

        $privacy = array_merge($defaults['privacy'], $privacy_settings);
        $notifications = array_merge($defaults['notifications'], $notification_settings);
        $appearance = array_merge($defaults['appearance'], $appearance_settings);

        // Tab actual
        $current_tab = isset($_GET['tab']) ? sanitize_key($_GET['tab']) : 'perfil';
        $base_url = home_url('/' . $this->base_path . '/configuracion/');
        ?>
        <div class="fmd-settings-container" x-data="flavorSettings()" x-init="init()">
            <!-- Tabs de navegación -->
            <nav class="fmd-settings-nav">
                <a href="<?php echo esc_url(add_query_arg('tab', 'perfil', $base_url)); ?>"
                   class="fmd-settings-tab <?php echo $current_tab === 'perfil' ? 'active' : ''; ?>">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/>
                        <circle cx="12" cy="7" r="4"/>
                    </svg>
                    <span><?php esc_html_e('Perfil', 'flavor-chat-ia'); ?></span>
                </a>
                <a href="<?php echo esc_url(add_query_arg('tab', 'cuenta', $base_url)); ?>"
                   class="fmd-settings-tab <?php echo $current_tab === 'cuenta' ? 'active' : ''; ?>">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <rect x="3" y="11" width="18" height="11" rx="2" ry="2"/>
                        <path d="M7 11V7a5 5 0 0 1 10 0v4"/>
                    </svg>
                    <span><?php esc_html_e('Cuenta', 'flavor-chat-ia'); ?></span>
                </a>
                <a href="<?php echo esc_url(add_query_arg('tab', 'notificaciones', $base_url)); ?>"
                   class="fmd-settings-tab <?php echo $current_tab === 'notificaciones' ? 'active' : ''; ?>">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M18 8A6 6 0 0 0 6 8c0 7-3 9-3 9h18s-3-2-3-9"/>
                        <path d="M13.73 21a2 2 0 0 1-3.46 0"/>
                    </svg>
                    <span><?php esc_html_e('Notificaciones', 'flavor-chat-ia'); ?></span>
                </a>
                <a href="<?php echo esc_url(add_query_arg('tab', 'privacidad', $base_url)); ?>"
                   class="fmd-settings-tab <?php echo $current_tab === 'privacidad' ? 'active' : ''; ?>">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/>
                    </svg>
                    <span><?php esc_html_e('Privacidad', 'flavor-chat-ia'); ?></span>
                </a>
                <a href="<?php echo esc_url(add_query_arg('tab', 'apariencia', $base_url)); ?>"
                   class="fmd-settings-tab <?php echo $current_tab === 'apariencia' ? 'active' : ''; ?>">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <circle cx="12" cy="12" r="5"/>
                        <line x1="12" y1="1" x2="12" y2="3"/>
                        <line x1="12" y1="21" x2="12" y2="23"/>
                        <line x1="4.22" y1="4.22" x2="5.64" y2="5.64"/>
                        <line x1="18.36" y1="18.36" x2="19.78" y2="19.78"/>
                        <line x1="1" y1="12" x2="3" y2="12"/>
                        <line x1="21" y1="12" x2="23" y2="12"/>
                        <line x1="4.22" y1="19.78" x2="5.64" y2="18.36"/>
                        <line x1="18.36" y1="5.64" x2="19.78" y2="4.22"/>
                    </svg>
                    <span><?php esc_html_e('Apariencia', 'flavor-chat-ia'); ?></span>
                </a>
            </nav>

            <!-- Contenido del tab -->
            <div class="fmd-settings-content">
                <?php
                switch ($current_tab) {
                    case 'cuenta':
                        $this->render_settings_account_tab($user);
                        break;
                    case 'notificaciones':
                        $this->render_settings_notifications_tab($usuario_id, $notifications);
                        break;
                    case 'privacidad':
                        $this->render_settings_privacy_tab($usuario_id, $privacy);
                        break;
                    case 'apariencia':
                        $this->render_settings_appearance_tab($usuario_id, $appearance);
                        break;
                    default:
                        $this->render_settings_profile_tab($user);
                        break;
                }
                ?>
            </div>

            <!-- Toast de notificación -->
            <div class="fmd-toast" x-show="showToast" x-transition x-cloak
                 :class="{ 'fmd-toast--success': toastType === 'success', 'fmd-toast--error': toastType === 'error' }">
                <span x-text="toastMessage"></span>
            </div>
        </div>

        <style>
            .fmd-settings-container {
                max-width: 900px;
                margin: 0 auto;
            }
            .fmd-settings-nav {
                display: flex;
                gap: 4px;
                background: var(--flavor-bg-secondary, #f3f4f6);
                padding: 6px;
                border-radius: 12px;
                margin-bottom: 24px;
                overflow-x: auto;
            }
            .fmd-settings-tab {
                display: flex;
                align-items: center;
                gap: 8px;
                padding: 10px 16px;
                border-radius: 8px;
                color: var(--flavor-text-secondary, #6b7280);
                text-decoration: none;
                font-size: 14px;
                font-weight: 500;
                white-space: nowrap;
                transition: all 0.2s;
            }
            .fmd-settings-tab:hover {
                background: var(--flavor-bg-tertiary, #e5e7eb);
                color: var(--flavor-text-primary, #1f2937);
            }
            .fmd-settings-tab.active {
                background: white;
                color: var(--flavor-primary, #3b82f6);
                box-shadow: 0 1px 3px rgba(0,0,0,0.1);
            }
            .fmd-settings-tab svg {
                flex-shrink: 0;
            }
            .fmd-settings-content {
                background: white;
                border-radius: 16px;
                padding: 32px;
                box-shadow: 0 1px 3px rgba(0,0,0,0.1);
            }
            .fmd-settings-section {
                margin-bottom: 32px;
            }
            .fmd-settings-section:last-child {
                margin-bottom: 0;
            }
            .fmd-settings-section-title {
                font-size: 18px;
                font-weight: 600;
                color: var(--flavor-text-primary, #1f2937);
                margin: 0 0 16px;
                padding-bottom: 12px;
                border-bottom: 1px solid var(--flavor-border, #e5e7eb);
            }
            .fmd-form-group {
                margin-bottom: 20px;
            }
            .fmd-form-label {
                display: block;
                font-size: 14px;
                font-weight: 500;
                color: var(--flavor-text-primary, #374151);
                margin-bottom: 6px;
            }
            .fmd-form-hint {
                font-size: 13px;
                color: var(--flavor-text-secondary, #6b7280);
                margin-top: 4px;
            }
            .fmd-form-input {
                width: 100%;
                padding: 10px 14px;
                border: 1px solid var(--flavor-border, #d1d5db);
                border-radius: 8px;
                font-size: 15px;
                transition: border-color 0.2s, box-shadow 0.2s;
            }
            .fmd-form-input:focus {
                outline: none;
                border-color: var(--flavor-primary, #3b82f6);
                box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
            }
            .fmd-form-textarea {
                min-height: 100px;
                resize: vertical;
            }
            .fmd-form-row {
                display: grid;
                grid-template-columns: repeat(2, 1fr);
                gap: 20px;
            }
            @media (max-width: 640px) {
                .fmd-form-row {
                    grid-template-columns: 1fr;
                }
            }
            .fmd-avatar-upload {
                display: flex;
                align-items: center;
                gap: 20px;
                margin-bottom: 24px;
            }
            .fmd-avatar-preview {
                width: 96px;
                height: 96px;
                border-radius: 50%;
                object-fit: cover;
                border: 3px solid var(--flavor-border, #e5e7eb);
            }
            .fmd-avatar-actions {
                display: flex;
                flex-direction: column;
                gap: 8px;
            }
            .fmd-btn {
                display: inline-flex;
                align-items: center;
                justify-content: center;
                gap: 8px;
                padding: 10px 20px;
                border-radius: 8px;
                font-size: 14px;
                font-weight: 500;
                cursor: pointer;
                transition: all 0.2s;
                border: none;
            }
            .fmd-btn-primary {
                background: var(--flavor-primary, #3b82f6);
                color: white;
            }
            .fmd-btn-primary:hover {
                background: var(--flavor-primary-dark, #2563eb);
            }
            .fmd-btn-secondary {
                background: var(--flavor-bg-secondary, #f3f4f6);
                color: var(--flavor-text-primary, #374151);
            }
            .fmd-btn-secondary:hover {
                background: var(--flavor-bg-tertiary, #e5e7eb);
            }
            .fmd-btn-danger {
                background: #fef2f2;
                color: #dc2626;
            }
            .fmd-btn-danger:hover {
                background: #fee2e2;
            }
            .fmd-toggle-group {
                display: flex;
                flex-direction: column;
                gap: 16px;
            }
            .fmd-toggle-item {
                display: flex;
                align-items: center;
                justify-content: space-between;
                padding: 16px;
                background: var(--flavor-bg-secondary, #f9fafb);
                border-radius: 10px;
            }
            .fmd-toggle-info {
                flex: 1;
            }
            .fmd-toggle-title {
                font-size: 15px;
                font-weight: 500;
                color: var(--flavor-text-primary, #1f2937);
                margin: 0 0 4px;
            }
            .fmd-toggle-desc {
                font-size: 13px;
                color: var(--flavor-text-secondary, #6b7280);
                margin: 0;
            }
            .fmd-toggle-switch {
                position: relative;
                width: 48px;
                height: 26px;
                background: #d1d5db;
                border-radius: 13px;
                cursor: pointer;
                transition: background 0.2s;
                flex-shrink: 0;
            }
            .fmd-toggle-switch.active {
                background: var(--flavor-primary, #3b82f6);
            }
            .fmd-toggle-switch::after {
                content: '';
                position: absolute;
                top: 3px;
                left: 3px;
                width: 20px;
                height: 20px;
                background: white;
                border-radius: 50%;
                transition: transform 0.2s;
                box-shadow: 0 1px 3px rgba(0,0,0,0.2);
            }
            .fmd-toggle-switch.active::after {
                transform: translateX(22px);
            }
            .fmd-select-group {
                display: flex;
                flex-direction: column;
                gap: 12px;
            }
            .fmd-select-item {
                display: flex;
                align-items: center;
                gap: 12px;
                padding: 16px;
                background: var(--flavor-bg-secondary, #f9fafb);
                border: 2px solid transparent;
                border-radius: 10px;
                cursor: pointer;
                transition: all 0.2s;
            }
            .fmd-select-item:hover {
                border-color: var(--flavor-border, #d1d5db);
            }
            .fmd-select-item.active {
                border-color: var(--flavor-primary, #3b82f6);
                background: rgba(59, 130, 246, 0.05);
            }
            .fmd-select-radio {
                width: 20px;
                height: 20px;
                border: 2px solid #d1d5db;
                border-radius: 50%;
                display: flex;
                align-items: center;
                justify-content: center;
                flex-shrink: 0;
            }
            .fmd-select-item.active .fmd-select-radio {
                border-color: var(--flavor-primary, #3b82f6);
            }
            .fmd-select-item.active .fmd-select-radio::after {
                content: '';
                width: 10px;
                height: 10px;
                background: var(--flavor-primary, #3b82f6);
                border-radius: 50%;
            }
            .fmd-select-label {
                font-size: 15px;
                font-weight: 500;
                color: var(--flavor-text-primary, #1f2937);
            }
            .fmd-theme-preview {
                display: flex;
                gap: 16px;
                margin-top: 20px;
            }
            .fmd-theme-card {
                flex: 1;
                padding: 20px;
                border-radius: 12px;
                border: 2px solid transparent;
                cursor: pointer;
                transition: all 0.2s;
            }
            .fmd-theme-card:hover {
                border-color: var(--flavor-border, #d1d5db);
            }
            .fmd-theme-card.active {
                border-color: var(--flavor-primary, #3b82f6);
            }
            .fmd-theme-card--light {
                background: #f9fafb;
            }
            .fmd-theme-card--dark {
                background: #1f2937;
            }
            .fmd-theme-card--auto {
                background: linear-gradient(135deg, #f9fafb 50%, #1f2937 50%);
            }
            .fmd-theme-name {
                text-align: center;
                font-size: 14px;
                font-weight: 500;
                margin-top: 12px;
            }
            .fmd-theme-card--dark .fmd-theme-name {
                color: white;
            }
            .fmd-form-actions {
                display: flex;
                gap: 12px;
                margin-top: 24px;
                padding-top: 24px;
                border-top: 1px solid var(--flavor-border, #e5e7eb);
            }
            .fmd-toast {
                position: fixed;
                bottom: 24px;
                right: 24px;
                padding: 14px 20px;
                border-radius: 10px;
                font-size: 14px;
                font-weight: 500;
                box-shadow: 0 4px 12px rgba(0,0,0,0.15);
                z-index: 1000;
            }
            .fmd-toast--success {
                background: #10b981;
                color: white;
            }
            .fmd-toast--error {
                background: #ef4444;
                color: white;
            }
            .fmd-danger-zone {
                margin-top: 40px;
                padding: 24px;
                background: #fef2f2;
                border: 1px solid #fecaca;
                border-radius: 12px;
            }
            .fmd-danger-zone h3 {
                color: #dc2626;
                margin: 0 0 12px;
                font-size: 16px;
            }
            .fmd-danger-zone p {
                color: #7f1d1d;
                margin: 0 0 16px;
                font-size: 14px;
            }
            @media (max-width: 768px) {
                .fmd-settings-nav {
                    flex-wrap: nowrap;
                    -webkit-overflow-scrolling: touch;
                }
                .fmd-settings-tab span {
                    display: none;
                }
                .fmd-settings-tab.active span {
                    display: inline;
                }
                .fmd-settings-content {
                    padding: 20px;
                }
            }
        </style>

        <script>
        function flavorSettings() {
            return {
                showToast: false,
                toastMessage: '',
                toastType: 'success',
                saving: false,
                avatarFile: null,

                init() {
                    // Aplicar tema guardado al cargar
                    this.applyTheme();
                },

                showNotification(message, type = 'success') {
                    this.toastMessage = message;
                    this.toastType = type;
                    this.showToast = true;
                    setTimeout(() => { this.showToast = false; }, 3000);
                },

                async saveSettings(form, section) {
                    if (this.saving) return;
                    this.saving = true;

                    const formData = new FormData(form);
                    formData.append('action', 'flavor_save_user_settings');
                    formData.append('section', section);
                    formData.append('nonce', '<?php echo wp_create_nonce('flavor_save_settings'); ?>');

                    try {
                        const response = await fetch('<?php echo admin_url('admin-ajax.php'); ?>', {
                            method: 'POST',
                            body: formData
                        });
                        const data = await response.json();

                        if (data.success) {
                            this.showNotification('<?php echo esc_js(__('Cambios guardados correctamente', 'flavor-chat-ia')); ?>');

                            // Si es apariencia, aplicar tema inmediatamente
                            if (section === 'appearance') {
                                const themeInput = form.querySelector('input[name="theme"]:checked');
                                if (themeInput) {
                                    this.setTheme(themeInput.value);
                                }
                            }

                            // Si guardamos avatar, actualizar preview
                            if (data.data && data.data.avatar_url) {
                                const preview = document.getElementById('avatar-preview');
                                if (preview) {
                                    preview.src = data.data.avatar_url;
                                }
                            }
                        } else {
                            this.showNotification(data.data || '<?php echo esc_js(__('Error al guardar', 'flavor-chat-ia')); ?>', 'error');
                        }
                    } catch (error) {
                        console.error('Settings save error:', error);
                        this.showNotification('<?php echo esc_js(__('Error de conexión', 'flavor-chat-ia')); ?>', 'error');
                    }

                    this.saving = false;
                },

                setTheme(theme) {
                    localStorage.setItem('flavor_theme', theme);
                    this.applyTheme();
                },

                applyTheme() {
                    const savedTheme = localStorage.getItem('flavor_theme') || 'auto';
                    let effectiveTheme = savedTheme;

                    if (savedTheme === 'auto') {
                        effectiveTheme = window.matchMedia('(prefers-color-scheme: dark)').matches ? 'dark' : 'light';
                    }

                    document.documentElement.setAttribute('data-theme', effectiveTheme);
                    document.body.classList.toggle('dark-mode', effectiveTheme === 'dark');
                },

                previewAvatar(input) {
                    if (input.files && input.files[0]) {
                        const reader = new FileReader();
                        reader.onload = (e) => {
                            document.getElementById('avatar-preview').src = e.target.result;
                        };
                        reader.readAsDataURL(input.files[0]);
                    }
                },

                async deleteAccount() {
                    if (!confirm('<?php echo esc_js(__('¿Estás seguro de que quieres eliminar tu cuenta? Esta acción no se puede deshacer.', 'flavor-chat-ia')); ?>')) {
                        return;
                    }

                    const formData = new FormData();
                    formData.append('action', 'flavor_delete_user_account');
                    formData.append('nonce', '<?php echo wp_create_nonce('flavor_delete_account'); ?>');

                    try {
                        const response = await fetch('<?php echo admin_url('admin-ajax.php'); ?>', {
                            method: 'POST',
                            body: formData
                        });
                        const data = await response.json();

                        if (data.success) {
                            this.showNotification('<?php echo esc_js(__('Cuenta eliminada. Redirigiendo...', 'flavor-chat-ia')); ?>');
                            setTimeout(() => {
                                window.location.href = '<?php echo home_url(); ?>';
                            }, 2000);
                        } else {
                            this.showNotification(data.data || '<?php echo esc_js(__('Error al eliminar la cuenta', 'flavor-chat-ia')); ?>', 'error');
                        }
                    } catch (error) {
                        this.showNotification('<?php echo esc_js(__('Error de conexión', 'flavor-chat-ia')); ?>', 'error');
                    }
                }
            };
        }

        // Escuchar cambios de tema del sistema
        window.matchMedia('(prefers-color-scheme: dark)').addEventListener('change', () => {
            const savedTheme = localStorage.getItem('flavor_theme');
            if (savedTheme === 'auto') {
                const effectiveTheme = window.matchMedia('(prefers-color-scheme: dark)').matches ? 'dark' : 'light';
                document.documentElement.setAttribute('data-theme', effectiveTheme);
                document.body.classList.toggle('dark-mode', effectiveTheme === 'dark');
            }
        });
        </script>
        <?php
    }

    /**
     * Tab de perfil en configuración
     */
    private function render_settings_profile_tab($user) {
        $avatar_url = get_avatar_url($user->ID, ['size' => 192]);
        $bio = get_user_meta($user->ID, 'description', true);
        $location = get_user_meta($user->ID, 'flavor_location', true);
        $website = $user->user_url;
        ?>
        <form @submit.prevent="saveSettings($el, 'profile')">
            <div class="fmd-settings-section">
                <h3 class="fmd-settings-section-title"><?php esc_html_e('Foto de perfil', 'flavor-chat-ia'); ?></h3>
                <div class="fmd-avatar-upload">
                    <img src="<?php echo esc_url($avatar_url); ?>" alt="" class="fmd-avatar-preview" id="avatar-preview">
                    <div class="fmd-avatar-actions">
                        <button type="button" class="fmd-btn fmd-btn-secondary" onclick="document.getElementById('avatar-input').click()">
                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/>
                                <polyline points="17 8 12 3 7 8"/>
                                <line x1="12" y1="3" x2="12" y2="15"/>
                            </svg>
                            <?php esc_html_e('Subir foto', 'flavor-chat-ia'); ?>
                        </button>
                        <input type="file" id="avatar-input" name="avatar" accept="image/*" style="display:none"
                               @change="previewAvatar($el)">
                        <p class="fmd-form-hint"><?php esc_html_e('JPG, PNG o GIF. Máximo 2MB.', 'flavor-chat-ia'); ?></p>
                    </div>
                </div>
            </div>

            <div class="fmd-settings-section">
                <h3 class="fmd-settings-section-title"><?php esc_html_e('Información personal', 'flavor-chat-ia'); ?></h3>

                <div class="fmd-form-row">
                    <div class="fmd-form-group">
                        <label class="fmd-form-label"><?php esc_html_e('Nombre', 'flavor-chat-ia'); ?></label>
                        <input type="text" name="first_name" class="fmd-form-input"
                               value="<?php echo esc_attr($user->first_name); ?>">
                    </div>
                    <div class="fmd-form-group">
                        <label class="fmd-form-label"><?php esc_html_e('Apellidos', 'flavor-chat-ia'); ?></label>
                        <input type="text" name="last_name" class="fmd-form-input"
                               value="<?php echo esc_attr($user->last_name); ?>">
                    </div>
                </div>

                <div class="fmd-form-group">
                    <label class="fmd-form-label"><?php esc_html_e('Nombre público', 'flavor-chat-ia'); ?></label>
                    <input type="text" name="display_name" class="fmd-form-input"
                           value="<?php echo esc_attr($user->display_name); ?>">
                    <p class="fmd-form-hint"><?php esc_html_e('Este nombre se mostrará públicamente', 'flavor-chat-ia'); ?></p>
                </div>

                <div class="fmd-form-group">
                    <label class="fmd-form-label"><?php esc_html_e('Biografía', 'flavor-chat-ia'); ?></label>
                    <textarea name="description" class="fmd-form-input fmd-form-textarea"
                              placeholder="<?php esc_attr_e('Cuéntanos algo sobre ti...', 'flavor-chat-ia'); ?>"><?php echo esc_textarea($bio); ?></textarea>
                </div>

                <div class="fmd-form-row">
                    <div class="fmd-form-group">
                        <label class="fmd-form-label"><?php esc_html_e('Ubicación', 'flavor-chat-ia'); ?></label>
                        <input type="text" name="location" class="fmd-form-input"
                               value="<?php echo esc_attr($location); ?>"
                               placeholder="<?php esc_attr_e('Ej: Madrid, España', 'flavor-chat-ia'); ?>">
                    </div>
                    <div class="fmd-form-group">
                        <label class="fmd-form-label"><?php esc_html_e('Sitio web', 'flavor-chat-ia'); ?></label>
                        <input type="url" name="user_url" class="fmd-form-input"
                               value="<?php echo esc_attr($website); ?>"
                               placeholder="https://">
                    </div>
                </div>
            </div>

            <div class="fmd-form-actions">
                <button type="submit" class="fmd-btn fmd-btn-primary" :disabled="saving">
                    <span x-show="!saving"><?php esc_html_e('Guardar cambios', 'flavor-chat-ia'); ?></span>
                    <span x-show="saving"><?php esc_html_e('Guardando...', 'flavor-chat-ia'); ?></span>
                </button>
            </div>
        </form>
        <?php
    }

    /**
     * Tab de cuenta en configuración
     */
    private function render_settings_account_tab($user) {
        ?>
        <form @submit.prevent="saveSettings($el, 'account')">
            <div class="fmd-settings-section">
                <h3 class="fmd-settings-section-title"><?php esc_html_e('Correo electrónico', 'flavor-chat-ia'); ?></h3>
                <div class="fmd-form-group">
                    <label class="fmd-form-label"><?php esc_html_e('Email actual', 'flavor-chat-ia'); ?></label>
                    <input type="email" name="email" class="fmd-form-input"
                           value="<?php echo esc_attr($user->user_email); ?>">
                    <p class="fmd-form-hint"><?php esc_html_e('Si cambias tu email, recibirás un mensaje de confirmación', 'flavor-chat-ia'); ?></p>
                </div>
            </div>

            <div class="fmd-settings-section">
                <h3 class="fmd-settings-section-title"><?php esc_html_e('Cambiar contraseña', 'flavor-chat-ia'); ?></h3>
                <div class="fmd-form-group">
                    <label class="fmd-form-label"><?php esc_html_e('Contraseña actual', 'flavor-chat-ia'); ?></label>
                    <input type="password" name="current_password" class="fmd-form-input" autocomplete="current-password">
                </div>
                <div class="fmd-form-row">
                    <div class="fmd-form-group">
                        <label class="fmd-form-label"><?php esc_html_e('Nueva contraseña', 'flavor-chat-ia'); ?></label>
                        <input type="password" name="new_password" class="fmd-form-input" autocomplete="new-password">
                    </div>
                    <div class="fmd-form-group">
                        <label class="fmd-form-label"><?php esc_html_e('Confirmar contraseña', 'flavor-chat-ia'); ?></label>
                        <input type="password" name="confirm_password" class="fmd-form-input" autocomplete="new-password">
                    </div>
                </div>
                <p class="fmd-form-hint"><?php esc_html_e('Deja en blanco si no quieres cambiar la contraseña', 'flavor-chat-ia'); ?></p>
            </div>

            <div class="fmd-form-actions">
                <button type="submit" class="fmd-btn fmd-btn-primary" :disabled="saving">
                    <span x-show="!saving"><?php esc_html_e('Guardar cambios', 'flavor-chat-ia'); ?></span>
                    <span x-show="saving"><?php esc_html_e('Guardando...', 'flavor-chat-ia'); ?></span>
                </button>
            </div>
        </form>

        <div class="fmd-danger-zone">
            <h3><?php esc_html_e('Zona de peligro', 'flavor-chat-ia'); ?></h3>
            <p><?php esc_html_e('Una vez eliminada tu cuenta, no hay vuelta atrás. Por favor, asegúrate.', 'flavor-chat-ia'); ?></p>
            <button type="button" class="fmd-btn fmd-btn-danger" @click="deleteAccount()">
                <?php esc_html_e('Eliminar mi cuenta', 'flavor-chat-ia'); ?>
            </button>
        </div>
        <?php
    }

    /**
     * Tab de notificaciones en configuración
     */
    private function render_settings_notifications_tab($usuario_id, $settings) {
        ?>
        <form @submit.prevent="saveSettings($el, 'notifications')">
            <div class="fmd-settings-section">
                <h3 class="fmd-settings-section-title"><?php esc_html_e('Notificaciones por email', 'flavor-chat-ia'); ?></h3>
                <div class="fmd-toggle-group">
                    <div class="fmd-toggle-item">
                        <div class="fmd-toggle-info">
                            <p class="fmd-toggle-title"><?php esc_html_e('Menciones y respuestas', 'flavor-chat-ia'); ?></p>
                            <p class="fmd-toggle-desc"><?php esc_html_e('Recibe un email cuando alguien te mencione o responda', 'flavor-chat-ia'); ?></p>
                        </div>
                        <label class="fmd-toggle-switch <?php echo $settings['email_mentions'] ? 'active' : ''; ?>">
                            <input type="checkbox" name="email_mentions" value="1" <?php checked($settings['email_mentions']); ?> hidden
                                   onchange="this.parentElement.classList.toggle('active')">
                        </label>
                    </div>
                    <div class="fmd-toggle-item">
                        <div class="fmd-toggle-info">
                            <p class="fmd-toggle-title"><?php esc_html_e('Mensajes directos', 'flavor-chat-ia'); ?></p>
                            <p class="fmd-toggle-desc"><?php esc_html_e('Recibe un email cuando recibas un mensaje privado', 'flavor-chat-ia'); ?></p>
                        </div>
                        <label class="fmd-toggle-switch <?php echo $settings['email_messages'] ? 'active' : ''; ?>">
                            <input type="checkbox" name="email_messages" value="1" <?php checked($settings['email_messages']); ?> hidden
                                   onchange="this.parentElement.classList.toggle('active')">
                        </label>
                    </div>
                    <div class="fmd-toggle-item">
                        <div class="fmd-toggle-info">
                            <p class="fmd-toggle-title"><?php esc_html_e('Novedades y actualizaciones', 'flavor-chat-ia'); ?></p>
                            <p class="fmd-toggle-desc"><?php esc_html_e('Recibe información sobre nuevas funcionalidades', 'flavor-chat-ia'); ?></p>
                        </div>
                        <label class="fmd-toggle-switch <?php echo $settings['email_updates'] ? 'active' : ''; ?>">
                            <input type="checkbox" name="email_updates" value="1" <?php checked($settings['email_updates']); ?> hidden
                                   onchange="this.parentElement.classList.toggle('active')">
                        </label>
                    </div>
                </div>
            </div>

            <div class="fmd-settings-section">
                <h3 class="fmd-settings-section-title"><?php esc_html_e('Notificaciones push', 'flavor-chat-ia'); ?></h3>
                <div class="fmd-toggle-group">
                    <div class="fmd-toggle-item">
                        <div class="fmd-toggle-info">
                            <p class="fmd-toggle-title"><?php esc_html_e('Notificaciones en el navegador', 'flavor-chat-ia'); ?></p>
                            <p class="fmd-toggle-desc"><?php esc_html_e('Recibe notificaciones en tiempo real en tu navegador', 'flavor-chat-ia'); ?></p>
                        </div>
                        <label class="fmd-toggle-switch <?php echo $settings['push_enabled'] ? 'active' : ''; ?>">
                            <input type="checkbox" name="push_enabled" value="1" <?php checked($settings['push_enabled']); ?> hidden
                                   onchange="this.parentElement.classList.toggle('active')">
                        </label>
                    </div>
                </div>
            </div>

            <div class="fmd-form-actions">
                <button type="submit" class="fmd-btn fmd-btn-primary" :disabled="saving">
                    <span x-show="!saving"><?php esc_html_e('Guardar preferencias', 'flavor-chat-ia'); ?></span>
                    <span x-show="saving"><?php esc_html_e('Guardando...', 'flavor-chat-ia'); ?></span>
                </button>
            </div>
        </form>
        <?php
    }

    /**
     * Tab de privacidad en configuración
     */
    private function render_settings_privacy_tab($usuario_id, $settings) {
        ?>
        <form @submit.prevent="saveSettings($el, 'privacy')">
            <div class="fmd-settings-section">
                <h3 class="fmd-settings-section-title"><?php esc_html_e('Visibilidad del perfil', 'flavor-chat-ia'); ?></h3>
                <div class="fmd-select-group">
                    <label class="fmd-select-item <?php echo $settings['profile_visibility'] === 'public' ? 'active' : ''; ?>"
                           onclick="this.querySelector('input').checked = true; document.querySelectorAll('.fmd-select-item').forEach(el => el.classList.remove('active')); this.classList.add('active');">
                        <span class="fmd-select-radio"></span>
                        <input type="radio" name="profile_visibility" value="public" <?php checked($settings['profile_visibility'], 'public'); ?> hidden>
                        <div>
                            <span class="fmd-select-label"><?php esc_html_e('Público', 'flavor-chat-ia'); ?></span>
                            <p class="fmd-toggle-desc"><?php esc_html_e('Cualquiera puede ver tu perfil', 'flavor-chat-ia'); ?></p>
                        </div>
                    </label>
                    <label class="fmd-select-item <?php echo $settings['profile_visibility'] === 'members' ? 'active' : ''; ?>"
                           onclick="this.querySelector('input').checked = true; document.querySelectorAll('.fmd-select-item').forEach(el => el.classList.remove('active')); this.classList.add('active');">
                        <span class="fmd-select-radio"></span>
                        <input type="radio" name="profile_visibility" value="members" <?php checked($settings['profile_visibility'], 'members'); ?> hidden>
                        <div>
                            <span class="fmd-select-label"><?php esc_html_e('Solo miembros', 'flavor-chat-ia'); ?></span>
                            <p class="fmd-toggle-desc"><?php esc_html_e('Solo usuarios registrados pueden ver tu perfil', 'flavor-chat-ia'); ?></p>
                        </div>
                    </label>
                    <label class="fmd-select-item <?php echo $settings['profile_visibility'] === 'private' ? 'active' : ''; ?>"
                           onclick="this.querySelector('input').checked = true; document.querySelectorAll('.fmd-select-item').forEach(el => el.classList.remove('active')); this.classList.add('active');">
                        <span class="fmd-select-radio"></span>
                        <input type="radio" name="profile_visibility" value="private" <?php checked($settings['profile_visibility'], 'private'); ?> hidden>
                        <div>
                            <span class="fmd-select-label"><?php esc_html_e('Privado', 'flavor-chat-ia'); ?></span>
                            <p class="fmd-toggle-desc"><?php esc_html_e('Solo tú puedes ver tu perfil completo', 'flavor-chat-ia'); ?></p>
                        </div>
                    </label>
                </div>
            </div>

            <div class="fmd-settings-section">
                <h3 class="fmd-settings-section-title"><?php esc_html_e('Opciones de privacidad', 'flavor-chat-ia'); ?></h3>
                <div class="fmd-toggle-group">
                    <div class="fmd-toggle-item">
                        <div class="fmd-toggle-info">
                            <p class="fmd-toggle-title"><?php esc_html_e('Mostrar email públicamente', 'flavor-chat-ia'); ?></p>
                            <p class="fmd-toggle-desc"><?php esc_html_e('Permitir que otros usuarios vean tu email', 'flavor-chat-ia'); ?></p>
                        </div>
                        <label class="fmd-toggle-switch <?php echo $settings['show_email'] ? 'active' : ''; ?>">
                            <input type="checkbox" name="show_email" value="1" <?php checked($settings['show_email']); ?> hidden
                                   onchange="this.parentElement.classList.toggle('active')">
                        </label>
                    </div>
                    <div class="fmd-toggle-item">
                        <div class="fmd-toggle-info">
                            <p class="fmd-toggle-title"><?php esc_html_e('Mostrar mi actividad', 'flavor-chat-ia'); ?></p>
                            <p class="fmd-toggle-desc"><?php esc_html_e('Mostrar tu actividad reciente en tu perfil', 'flavor-chat-ia'); ?></p>
                        </div>
                        <label class="fmd-toggle-switch <?php echo $settings['show_activity'] ? 'active' : ''; ?>">
                            <input type="checkbox" name="show_activity" value="1" <?php checked($settings['show_activity']); ?> hidden
                                   onchange="this.parentElement.classList.toggle('active')">
                        </label>
                    </div>
                    <div class="fmd-toggle-item">
                        <div class="fmd-toggle-info">
                            <p class="fmd-toggle-title"><?php esc_html_e('Permitir mensajes directos', 'flavor-chat-ia'); ?></p>
                            <p class="fmd-toggle-desc"><?php esc_html_e('Otros usuarios pueden enviarte mensajes privados', 'flavor-chat-ia'); ?></p>
                        </div>
                        <label class="fmd-toggle-switch <?php echo $settings['allow_messages'] ? 'active' : ''; ?>">
                            <input type="checkbox" name="allow_messages" value="1" <?php checked($settings['allow_messages']); ?> hidden
                                   onchange="this.parentElement.classList.toggle('active')">
                        </label>
                    </div>
                </div>
            </div>

            <div class="fmd-form-actions">
                <button type="submit" class="fmd-btn fmd-btn-primary" :disabled="saving">
                    <span x-show="!saving"><?php esc_html_e('Guardar preferencias', 'flavor-chat-ia'); ?></span>
                    <span x-show="saving"><?php esc_html_e('Guardando...', 'flavor-chat-ia'); ?></span>
                </button>
            </div>
        </form>
        <?php
    }

    /**
     * Tab de apariencia en configuración
     */
    private function render_settings_appearance_tab($usuario_id, $settings) {
        ?>
        <form @submit.prevent="saveSettings($el, 'appearance')">
            <div class="fmd-settings-section">
                <h3 class="fmd-settings-section-title"><?php esc_html_e('Tema de la interfaz', 'flavor-chat-ia'); ?></h3>
                <div class="fmd-theme-preview">
                    <label class="fmd-theme-card fmd-theme-card--light <?php echo $settings['theme'] === 'light' ? 'active' : ''; ?>"
                           onclick="document.querySelectorAll('.fmd-theme-card').forEach(el => el.classList.remove('active')); this.classList.add('active'); this.querySelector('input').checked = true;">
                        <input type="radio" name="theme" value="light" <?php checked($settings['theme'], 'light'); ?> hidden>
                        <svg width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="#374151" stroke-width="1.5" style="margin:0 auto;display:block;">
                            <circle cx="12" cy="12" r="5"/>
                            <line x1="12" y1="1" x2="12" y2="3"/>
                            <line x1="12" y1="21" x2="12" y2="23"/>
                            <line x1="4.22" y1="4.22" x2="5.64" y2="5.64"/>
                            <line x1="18.36" y1="18.36" x2="19.78" y2="19.78"/>
                            <line x1="1" y1="12" x2="3" y2="12"/>
                            <line x1="21" y1="12" x2="23" y2="12"/>
                            <line x1="4.22" y1="19.78" x2="5.64" y2="18.36"/>
                            <line x1="18.36" y1="5.64" x2="19.78" y2="4.22"/>
                        </svg>
                        <p class="fmd-theme-name"><?php esc_html_e('Claro', 'flavor-chat-ia'); ?></p>
                    </label>
                    <label class="fmd-theme-card fmd-theme-card--dark <?php echo $settings['theme'] === 'dark' ? 'active' : ''; ?>"
                           onclick="document.querySelectorAll('.fmd-theme-card').forEach(el => el.classList.remove('active')); this.classList.add('active'); this.querySelector('input').checked = true;">
                        <input type="radio" name="theme" value="dark" <?php checked($settings['theme'], 'dark'); ?> hidden>
                        <svg width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="white" stroke-width="1.5" style="margin:0 auto;display:block;">
                            <path d="M21 12.79A9 9 0 1 1 11.21 3 7 7 0 0 0 21 12.79z"/>
                        </svg>
                        <p class="fmd-theme-name"><?php esc_html_e('Oscuro', 'flavor-chat-ia'); ?></p>
                    </label>
                    <label class="fmd-theme-card fmd-theme-card--auto <?php echo $settings['theme'] === 'auto' ? 'active' : ''; ?>"
                           onclick="document.querySelectorAll('.fmd-theme-card').forEach(el => el.classList.remove('active')); this.classList.add('active'); this.querySelector('input').checked = true;">
                        <input type="radio" name="theme" value="auto" <?php checked($settings['theme'], 'auto'); ?> hidden>
                        <svg width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="#6b7280" stroke-width="1.5" style="margin:0 auto;display:block;">
                            <rect x="2" y="3" width="20" height="14" rx="2"/>
                            <line x1="8" y1="21" x2="16" y2="21"/>
                            <line x1="12" y1="17" x2="12" y2="21"/>
                        </svg>
                        <p class="fmd-theme-name"><?php esc_html_e('Automático', 'flavor-chat-ia'); ?></p>
                    </label>
                </div>
                <p class="fmd-form-hint" style="margin-top:16px;text-align:center;">
                    <?php esc_html_e('El tema automático sigue la preferencia de tu sistema operativo', 'flavor-chat-ia'); ?>
                </p>
            </div>

            <div class="fmd-settings-section">
                <h3 class="fmd-settings-section-title"><?php esc_html_e('Preferencias de visualización', 'flavor-chat-ia'); ?></h3>
                <div class="fmd-toggle-group">
                    <div class="fmd-toggle-item">
                        <div class="fmd-toggle-info">
                            <p class="fmd-toggle-title"><?php esc_html_e('Modo compacto', 'flavor-chat-ia'); ?></p>
                            <p class="fmd-toggle-desc"><?php esc_html_e('Reduce el espaciado para mostrar más contenido', 'flavor-chat-ia'); ?></p>
                        </div>
                        <label class="fmd-toggle-switch <?php echo $settings['compact_mode'] ? 'active' : ''; ?>">
                            <input type="checkbox" name="compact_mode" value="1" <?php checked($settings['compact_mode']); ?> hidden
                                   onchange="this.parentElement.classList.toggle('active')">
                        </label>
                    </div>
                </div>
            </div>

            <div class="fmd-form-actions">
                <button type="submit" class="fmd-btn fmd-btn-primary" :disabled="saving">
                    <span x-show="!saving"><?php esc_html_e('Guardar preferencias', 'flavor-chat-ia'); ?></span>
                    <span x-show="saving"><?php esc_html_e('Guardando...', 'flavor-chat-ia'); ?></span>
                </button>
            </div>
        </form>
        <?php
    }

    /**
     * Renderiza sección de puntos
     */
    private function render_points_section($usuario_id) {
        $puntos = get_user_meta($usuario_id, 'flavor_points', true) ?: 0;
        $nivel = get_user_meta($usuario_id, 'flavor_level', true) ?: 1;
        ?>
        <div class="fmd-points-card">
            <div class="fmd-points-summary">
                <div class="fmd-points-value">
                    <span class="dashicons dashicons-star-filled"></span>
                    <span class="fmd-points-number"><?php echo number_format_i18n($puntos); ?></span>
                    <span class="fmd-points-label"><?php esc_html_e('puntos', 'flavor-chat-ia'); ?></span>
                </div>
                <div class="fmd-level-badge">
                    <?php printf(esc_html__('Nivel %d', 'flavor-chat-ia'), $nivel); ?>
                </div>
            </div>
        </div>
        <?php
    }

    /**
     * Renderiza mensaje de "próximamente"
     */
    private function render_section_coming_soon($section_name) {
        ?>
        <div class="fmd-coming-soon">
            <span class="dashicons dashicons-clock"></span>
            <h3><?php esc_html_e('Próximamente', 'flavor-chat-ia'); ?></h3>
            <p><?php printf(
                esc_html__('La sección de %s estará disponible pronto.', 'flavor-chat-ia'),
                esc_html($section_name)
            ); ?></p>
            <a href="<?php echo esc_url(home_url('/' . $this->base_path . '/')); ?>" class="flavor-btn">
                <?php esc_html_e('Volver al dashboard', 'flavor-chat-ia'); ?>
            </a>
        </div>
        <?php
    }

    /**
     * Renderiza el footer de fallback (cuando no hay sistema de layouts)
     */
    private function render_footer_fallback() {
        $app_config = get_option('flavor_apps_config', []);
        ?>
        <footer class="flavor-app-footer">
            <?php if (is_active_sidebar('footer-1') || is_active_sidebar('footer-2') || is_active_sidebar('footer-3')): ?>
            <div class="faf-widgets">
                <div class="faf-widgets-container">
                    <?php if (is_active_sidebar('footer-1')): ?>
                    <div class="faf-widget-area">
                        <?php dynamic_sidebar('footer-1'); ?>
                    </div>
                    <?php endif; ?>

                    <?php if (is_active_sidebar('footer-2')): ?>
                    <div class="faf-widget-area">
                        <?php dynamic_sidebar('footer-2'); ?>
                    </div>
                    <?php endif; ?>

                    <?php if (is_active_sidebar('footer-3')): ?>
                    <div class="faf-widget-area">
                        <?php dynamic_sidebar('footer-3'); ?>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
            <?php endif; ?>

            <div class="faf-bottom">
                <div class="faf-bottom-container">
                    <div class="faf-copyright">
                        <p>&copy; <?php echo date('Y'); ?> <?php bloginfo('name'); ?>. <?php esc_html_e('Todos los derechos reservados.', 'flavor-chat-ia'); ?></p>
                    </div>

                    <?php
                    // Menú de footer si existe
                    $footer_menu_args = [
                        'theme_location' => 'footer',
                        'container' => 'nav',
                        'container_class' => 'faf-menu',
                        'menu_class' => 'faf-menu-list',
                        'fallback_cb' => false,
                        'depth' => 1,
                    ];

                    // Intentar ubicación alternativa si 'footer' no existe
                    if (!has_nav_menu('footer')) {
                        $footer_menu_args['theme_location'] = 'footer-menu';
                    }

                    wp_nav_menu($footer_menu_args);
                    ?>

                    <?php
                    // Enlaces de política de privacidad y términos
                    $politica_privacidad = get_privacy_policy_url();
                    $terminos = get_page_by_path('terminos-y-condiciones');
                    if ($politica_privacidad || $terminos): ?>
                    <div class="faf-legal">
                        <?php if ($politica_privacidad): ?>
                        <a href="<?php echo esc_url($politica_privacidad); ?>">
                            <?php esc_html_e('Política de Privacidad', 'flavor-chat-ia'); ?>
                        </a>
                        <?php endif; ?>

                        <?php if ($terminos): ?>
                        <a href="<?php echo esc_url(get_permalink($terminos)); ?>">
                            <?php esc_html_e('Términos y Condiciones', 'flavor-chat-ia'); ?>
                        </a>
                        <?php endif; ?>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </footer>
        <?php
    }

    /**
     * Obtiene los módulos activos
     */
    private function get_active_modules() {
        if (!class_exists('Flavor_Chat_Module_Loader')) {
            return [];
        }

        $loader = Flavor_Chat_Module_Loader::get_instance();
        $modules = $loader->get_loaded_modules();
        $result = [];

        // Mi Red Social: interfaz unificada de módulos sociales (destacado al inicio)
        $result['mi-red'] = [
            'name' => __('Mi Red', 'flavor-chat-ia'),
            'description' => __('Tu red social unificada', 'flavor-chat-ia'),
            'icon' => 'dashicons-share-alt',
        ];

        foreach ($modules as $id => $instance) {
            $result[$id] = [
                'name' => $instance->name ?? ucfirst(str_replace(['-', '_'], ' ', $id)),
                'description' => $instance->description ?? '',
                'icon' => $this->get_module_icon($id),
            ];
        }

        return $result;
    }

    /**
     * Obtiene la instancia de un módulo
     */
    private function get_module_instance($module_id) {
        if (!class_exists('Flavor_Chat_Module_Loader')) {
            return null;
        }

        $loader = Flavor_Chat_Module_Loader::get_instance();

        // Intentar con guiones bajos
        $instance = $loader->get_module(str_replace('-', '_', $module_id));
        if ($instance) return $instance;

        // Intentar con guiones
        $instance = $loader->get_module(str_replace('_', '-', $module_id));
        return $instance;
    }

    /**
     * Determina si una acción debe mostrar un formulario de creación
     */
    private function is_create_action($action) {
        // Lista de acciones que muestran formulario de creación
        $acciones_creacion = [
            'crear',
            'nuevo',
            'nueva',
            'proponer',
            'reportar',
            'publicar',
            'solicitar',
            'registrar',
            'añadir',
            'agregar',
            'inscribir',
            'reservar',
            'suscribir',
            'fichar',
        ];

        return in_array($action, $acciones_creacion, true);
    }

    /**
     * Determina si la acción actual ya tiene un renderer real definido por tabs.
     *
     * Evita desviar acciones como "publicar" al CRUD genérico cuando el módulo
     * expone un callback o contenido específico para esa ruta.
     *
     * @param object|null $module
     * @return bool
     */
    private function current_action_uses_module_tab_renderer($module): bool {
        $action = (string) $this->current_action;
        if ($action === '') {
            return false;
        }

        $tabs = $this->get_module_tabs($module);
        return !empty($tabs[$action]);
    }

    /**
     * Obtiene las acciones legacy de un módulo.
     *
     * Esta matriz existe como red de seguridad para módulos sin tabs
     * modernas completas. El sidebar principal ya no debe depender de
     * ella como fuente primaria cuando el módulo expone configuración viva.
     */
    private function get_legacy_module_actions($module_id) {
        $acciones_por_modulo = [
            // ═══════════════════════════════════════════════════════════════
            // Eventos y Actividades (con integraciones)
            // ═══════════════════════════════════════════════════════════════
            'eventos' => [
                'proximos-eventos' => ['label' => __('Próximos eventos', 'flavor-chat-ia'), 'icon' => 'dashicons-calendar'],
                'mis-inscripciones' => ['label' => __('Mis inscripciones', 'flavor-chat-ia'), 'icon' => 'dashicons-tickets-alt', 'requires_login' => true],
                'calendario' => ['label' => __('Calendario', 'flavor-chat-ia'), 'icon' => 'dashicons-calendar'],
                'crear-evento' => ['label' => __('Crear evento', 'flavor-chat-ia'), 'icon' => 'dashicons-plus-alt', 'cap' => 'edit_posts'],
                // Integraciones
                'multimedia' => ['label' => __('Fotos', 'flavor-chat-ia'), 'icon' => 'dashicons-format-gallery'],
                'comentarios' => ['label' => __('Comentarios', 'flavor-chat-ia'), 'icon' => 'dashicons-admin-comments'],
            ],
            'talleres' => [
                'crear' => ['label' => __('Crear', 'flavor-chat-ia'), 'icon' => 'dashicons-plus-alt'],
                'mis-talleres' => ['label' => __('Mis talleres', 'flavor-chat-ia'), 'icon' => 'dashicons-welcome-learn-more'],
                'inscripciones' => ['label' => __('Inscripciones', 'flavor-chat-ia'), 'icon' => 'dashicons-groups'],
                'calendario' => ['label' => __('Calendario', 'flavor-chat-ia'), 'icon' => 'dashicons-calendar'],
                // Integraciones
                'materiales' => ['label' => __('Materiales', 'flavor-chat-ia'), 'icon' => 'dashicons-media-document'],
                'multimedia' => ['label' => __('Multimedia', 'flavor-chat-ia'), 'icon' => 'dashicons-format-gallery'],
                'foro' => ['label' => __('Foro', 'flavor-chat-ia'), 'icon' => 'dashicons-admin-comments'],
            ],
            'cursos' => [
                'crear' => ['label' => __('Crear', 'flavor-chat-ia'), 'icon' => 'dashicons-plus-alt'],
                'mis-cursos' => ['label' => __('Mis cursos', 'flavor-chat-ia'), 'icon' => 'dashicons-book-alt'],
                'progreso' => ['label' => __('Mi progreso', 'flavor-chat-ia'), 'icon' => 'dashicons-chart-line'],
                'certificados' => ['label' => __('Certificados', 'flavor-chat-ia'), 'icon' => 'dashicons-awards'],
                // Integraciones
                'lecciones' => ['label' => __('Lecciones', 'flavor-chat-ia'), 'icon' => 'dashicons-video-alt3'],
                'materiales' => ['label' => __('Materiales', 'flavor-chat-ia'), 'icon' => 'dashicons-media-document'],
                'foro' => ['label' => __('Foro', 'flavor-chat-ia'), 'icon' => 'dashicons-admin-comments'],
            ],

            // ═══════════════════════════════════════════════════════════════
            // Reservas y Espacios (con integraciones)
            // ═══════════════════════════════════════════════════════════════
            'reservas' => [
                'recursos' => ['label' => __('Recursos disponibles', 'flavor-chat-ia'), 'icon' => 'dashicons-admin-home'],
                'mis-reservas' => ['label' => __('Mis reservas', 'flavor-chat-ia'), 'icon' => 'dashicons-calendar-alt'],
                'calendario' => ['label' => __('Calendario', 'flavor-chat-ia'), 'icon' => 'dashicons-calendar-alt'],
                'nueva-reserva' => ['label' => __('Hacer reserva', 'flavor-chat-ia'), 'icon' => 'dashicons-plus-alt'],
            ],
            'espacios-comunes' => [
                'reservar' => ['label' => __('Reservar', 'flavor-chat-ia'), 'icon' => 'dashicons-plus-alt'],
                'mis-reservas' => ['label' => __('Mis reservas', 'flavor-chat-ia'), 'icon' => 'dashicons-calendar'],
                'espacios' => ['label' => __('Espacios', 'flavor-chat-ia'), 'icon' => 'dashicons-building'],
                'calendario' => ['label' => __('Calendario', 'flavor-chat-ia'), 'icon' => 'dashicons-calendar-alt'],
                'normas' => ['label' => __('Normas', 'flavor-chat-ia'), 'icon' => 'dashicons-clipboard'],
                'foro' => ['label' => __('Foro', 'flavor-chat-ia'), 'icon' => 'dashicons-admin-comments'],
            ],
            'huertos-urbanos' => [
                'mi-parcela' => ['label' => __('Mi parcela', 'flavor-chat-ia'), 'icon' => 'dashicons-carrot'],
                'solicitar' => ['label' => __('Solicitar', 'flavor-chat-ia'), 'icon' => 'dashicons-plus-alt'],
                'calendario' => ['label' => __('Calendario', 'flavor-chat-ia'), 'icon' => 'dashicons-calendar'],
                'mapa' => ['label' => __('Mapa', 'flavor-chat-ia'), 'icon' => 'dashicons-location-alt'],
                'banco-semillas' => ['label' => __('Banco semillas', 'flavor-chat-ia'), 'icon' => 'dashicons-archive'],
                'foro' => ['label' => __('Foro', 'flavor-chat-ia'), 'icon' => 'dashicons-admin-comments'],
                'recetas' => ['label' => __('Recetas', 'flavor-chat-ia'), 'icon' => 'dashicons-carrot'],
            ],

            // ═══════════════════════════════════════════════════════════════
            // Movilidad (con integraciones)
            // ═══════════════════════════════════════════════════════════════
            'bicicletas-compartidas' => [
                'alquilar' => ['label' => __('Alquilar', 'flavor-chat-ia'), 'icon' => 'dashicons-plus-alt'],
                'mis-alquileres' => ['label' => __('Mis alquileres', 'flavor-chat-ia'), 'icon' => 'dashicons-list-view'],
                'estaciones' => ['label' => __('Estaciones', 'flavor-chat-ia'), 'icon' => 'dashicons-location'],
                'mapa' => ['label' => __('Mapa', 'flavor-chat-ia'), 'icon' => 'dashicons-location-alt'],
                'incidencias' => ['label' => __('Incidencias', 'flavor-chat-ia'), 'icon' => 'dashicons-warning'],
                'estadisticas' => ['label' => __('Estadísticas', 'flavor-chat-ia'), 'icon' => 'dashicons-chart-bar'],
            ],
            'carpooling' => [
                'ofrecer' => ['label' => __('Ofrecer', 'flavor-chat-ia'), 'icon' => 'dashicons-plus-alt'],
                'buscar' => ['label' => __('Buscar', 'flavor-chat-ia'), 'icon' => 'dashicons-search'],
                'mis-viajes' => ['label' => __('Mis viajes', 'flavor-chat-ia'), 'icon' => 'dashicons-car'],
                'rutas-frecuentes' => ['label' => __('Rutas frecuentes', 'flavor-chat-ia'), 'icon' => 'dashicons-randomize'],
                'valoraciones' => ['label' => __('Valoraciones', 'flavor-chat-ia'), 'icon' => 'dashicons-star-filled'],
                'mensajes' => ['label' => __('Mensajes', 'flavor-chat-ia'), 'icon' => 'dashicons-email-alt'],
            ],
            'parkings' => [
                'mapa' => ['label' => __('Mapa', 'flavor-chat-ia'), 'icon' => 'dashicons-location'],
                'listado' => ['label' => __('Listado', 'flavor-chat-ia'), 'icon' => 'dashicons-list-view'],
                'reservar' => ['label' => __('Reservar', 'flavor-chat-ia'), 'icon' => 'dashicons-calendar-alt', 'requires_login' => true],
                'mis-reservas' => ['label' => __('Mis reservas', 'flavor-chat-ia'), 'icon' => 'dashicons-admin-users', 'requires_login' => true],
            ],

            // ═══════════════════════════════════════════════════════════════
            // Comunidad y Social (con integraciones de otros módulos)
            // ═══════════════════════════════════════════════════════════════
            'comunidades' => [
                'crear' => ['label' => __('Nueva comunidad', 'flavor-chat-ia'), 'icon' => 'dashicons-plus-alt'],
                'mis-comunidades' => ['label' => __('Mis espacios', 'flavor-chat-ia'), 'icon' => 'dashicons-groups'],
                'explorar' => ['label' => __('Explorar', 'flavor-chat-ia'), 'icon' => 'dashicons-search'],
                // Integraciones con otros módulos
                'foros' => ['label' => __('Foros', 'flavor-chat-ia'), 'icon' => 'dashicons-admin-comments'],
                'multimedia' => ['label' => __('Multimedia', 'flavor-chat-ia'), 'icon' => 'dashicons-format-gallery'],
                'eventos' => ['label' => __('Encuentros', 'flavor-chat-ia'), 'icon' => 'dashicons-calendar-alt'],
                'anuncios' => ['label' => __('Avisos', 'flavor-chat-ia'), 'icon' => 'dashicons-megaphone'],
                'recursos' => ['label' => __('Recursos compartidos', 'flavor-chat-ia'), 'icon' => 'dashicons-media-document'],
            ],
            'colectivos' => [
                'crear' => ['label' => __('Crear', 'flavor-chat-ia'), 'icon' => 'dashicons-plus-alt'],
                'mis-colectivos' => ['label' => __('Mis colectivos', 'flavor-chat-ia'), 'icon' => 'dashicons-networking'],
                // Integraciones con otros módulos
                'proyectos' => ['label' => __('Proyectos', 'flavor-chat-ia'), 'icon' => 'dashicons-portfolio'],
                'asambleas' => ['label' => __('Asambleas', 'flavor-chat-ia'), 'icon' => 'dashicons-groups'],
                'multimedia' => ['label' => __('Multimedia', 'flavor-chat-ia'), 'icon' => 'dashicons-format-gallery'],
                'eventos' => ['label' => __('Eventos', 'flavor-chat-ia'), 'icon' => 'dashicons-calendar-alt'],
                'documentos' => ['label' => __('Documentos', 'flavor-chat-ia'), 'icon' => 'dashicons-media-document'],
            ],
            'foros' => [
                'nuevo-tema' => ['label' => __('Nuevo tema', 'flavor-chat-ia'), 'icon' => 'dashicons-edit'],
                'mis-temas' => ['label' => __('Mis temas', 'flavor-chat-ia'), 'icon' => 'dashicons-admin-comments'],
                'categorias' => ['label' => __('Categorías', 'flavor-chat-ia'), 'icon' => 'dashicons-category'],
            ],
            'red-social' => [
                'feed' => ['label' => __('Feed', 'flavor-chat-ia'), 'icon' => 'dashicons-rss'],
                'mi-perfil' => ['label' => __('Mi perfil', 'flavor-chat-ia'), 'icon' => 'dashicons-admin-users'],
                'explorar' => ['label' => __('Explorar', 'flavor-chat-ia'), 'icon' => 'dashicons-search'],
                'amigos' => ['label' => __('Amigos', 'flavor-chat-ia'), 'icon' => 'dashicons-groups'],
                'mensajes' => ['label' => __('Mensajes', 'flavor-chat-ia'), 'icon' => 'dashicons-email-alt'],
                'historias' => ['label' => __('Historias', 'flavor-chat-ia'), 'icon' => 'dashicons-format-video'],
            ],

            // ═══════════════════════════════════════════════════════════════
            // Incidencias y Participación (con integraciones)
            // ═══════════════════════════════════════════════════════════════
            'incidencias' => [
                'reportar' => ['label' => __('Reportar', 'flavor-chat-ia'), 'icon' => 'dashicons-flag'],
                'mis-reportes' => ['label' => __('Mis reportes', 'flavor-chat-ia'), 'icon' => 'dashicons-list-view'],
                'mapa' => ['label' => __('Mapa', 'flavor-chat-ia'), 'icon' => 'dashicons-location-alt'],
                'estadisticas' => ['label' => __('Estadísticas', 'flavor-chat-ia'), 'icon' => 'dashicons-chart-bar'],
                'categorias' => ['label' => __('Categorías', 'flavor-chat-ia'), 'icon' => 'dashicons-category'],
            ],
            'participacion' => [
                'propuestas' => ['label' => __('Iniciativas', 'flavor-chat-ia'), 'icon' => 'dashicons-lightbulb'],
                'votaciones' => ['label' => __('Decisiones', 'flavor-chat-ia'), 'icon' => 'dashicons-thumbs-up'],
                'resultados' => ['label' => __('Acuerdos', 'flavor-chat-ia'), 'icon' => 'dashicons-chart-bar'],
                'debates' => ['label' => __('Conversaciones', 'flavor-chat-ia'), 'icon' => 'dashicons-admin-comments'],
                'reuniones' => ['label' => __('Encuentros', 'flavor-chat-ia'), 'icon' => 'dashicons-groups'],
            ],
            'presupuestos-participativos' => [
                'proponer' => ['label' => __('Proponer', 'flavor-chat-ia'), 'icon' => 'dashicons-lightbulb'],
                'mis-propuestas' => ['label' => __('Mis propuestas', 'flavor-chat-ia'), 'icon' => 'dashicons-list-view'],
                'votacion' => ['label' => __('Votación', 'flavor-chat-ia'), 'icon' => 'dashicons-chart-pie'],
                'proyectos' => ['label' => __('Proyectos', 'flavor-chat-ia'), 'icon' => 'dashicons-portfolio'],
                'seguimiento' => ['label' => __('Seguimiento', 'flavor-chat-ia'), 'icon' => 'dashicons-visibility'],
                'historico' => ['label' => __('Histórico', 'flavor-chat-ia'), 'icon' => 'dashicons-backup'],
            ],

            // ═══════════════════════════════════════════════════════════════
            // Comercio y Economía (con integraciones)
            // ═══════════════════════════════════════════════════════════════
            'marketplace' => [
                'publicar' => ['label' => __('Publicar', 'flavor-chat-ia'), 'icon' => 'dashicons-plus-alt'],
                'mis-anuncios' => ['label' => __('Mis anuncios', 'flavor-chat-ia'), 'icon' => 'dashicons-archive'],
                'favoritos' => ['label' => __('Favoritos', 'flavor-chat-ia'), 'icon' => 'dashicons-heart'],
                'mensajes' => ['label' => __('Mensajes', 'flavor-chat-ia'), 'icon' => 'dashicons-email-alt'],
                'categorias' => ['label' => __('Categorías', 'flavor-chat-ia'), 'icon' => 'dashicons-category'],
            ],
            'grupos-consumo' => [
                'grupos' => ['label' => __('Grupos', 'flavor-chat-ia'), 'icon' => 'dashicons-groups'],
                'productos' => ['label' => __('Productos', 'flavor-chat-ia'), 'icon' => 'dashicons-products'],
                'productores' => ['label' => __('Productores', 'flavor-chat-ia'), 'icon' => 'dashicons-store'],
                'mi-cesta' => ['label' => __('Mi cesta', 'flavor-chat-ia'), 'icon' => 'dashicons-cart', 'requires_login' => true],
                'mi-pedido' => ['label' => __('Pedido actual', 'flavor-chat-ia'), 'icon' => 'dashicons-list-view', 'requires_login' => true],
                'mis-pedidos' => ['label' => __('Historial', 'flavor-chat-ia'), 'icon' => 'dashicons-clipboard', 'requires_login' => true],
                'ciclos' => ['label' => __('Ciclos', 'flavor-chat-ia'), 'icon' => 'dashicons-calendar-alt', 'requires_login' => true],
                'suscripciones' => ['label' => __('Suscripciones', 'flavor-chat-ia'), 'icon' => 'dashicons-heart', 'requires_login' => true, 'hidden_nav' => true],
                'unirme' => ['label' => __('Unirme', 'flavor-chat-ia'), 'icon' => 'dashicons-plus-alt', 'requires_login' => true, 'hidden_nav' => true],
                // Integraciones
                'foro' => ['label' => __('Foro', 'flavor-chat-ia'), 'icon' => 'dashicons-admin-comments'],
                'recetas' => ['label' => __('Recetas', 'flavor-chat-ia'), 'icon' => 'dashicons-carrot'],
            ],
            'banco-tiempo' => [
                'servicios' => ['label' => __('Servicios', 'flavor-chat-ia'), 'icon' => 'dashicons-admin-users'],
                'mi-saldo' => ['label' => __('Mi Saldo', 'flavor-chat-ia'), 'icon' => 'dashicons-clock'],
                'intercambios' => ['label' => __('Intercambios', 'flavor-chat-ia'), 'icon' => 'dashicons-randomize'],
                'ranking' => ['label' => __('Ranking', 'flavor-chat-ia'), 'icon' => 'dashicons-awards'],
                'reputacion' => ['label' => __('Mi Reputación', 'flavor-chat-ia'), 'icon' => 'dashicons-star-filled'],
                'mensajes' => ['label' => __('Mensajes', 'flavor-chat-ia'), 'icon' => 'dashicons-email-alt'],
                'foro' => ['label' => __('Foro', 'flavor-chat-ia'), 'icon' => 'dashicons-admin-comments'],
                'chat' => ['label' => __('Chat', 'flavor-chat-ia'), 'icon' => 'dashicons-format-chat'],
                'multimedia' => ['label' => __('Multimedia', 'flavor-chat-ia'), 'icon' => 'dashicons-format-gallery'],
                'red-social' => ['label' => __('Red social', 'flavor-chat-ia'), 'icon' => 'dashicons-share'],
                'ofrecer' => ['label' => __('Ofrecer', 'flavor-chat-ia'), 'icon' => 'dashicons-plus-alt'],
                'buscar' => ['label' => __('Buscar', 'flavor-chat-ia'), 'icon' => 'dashicons-search'],
            ],
            'economia-don' => [
                'ofrecer' => ['label' => __('Ofrecer don', 'flavor-chat-ia'), 'icon' => 'dashicons-heart'],
                'buscar' => ['label' => __('Buscar', 'flavor-chat-ia'), 'icon' => 'dashicons-search'],
                'mis-dones' => ['label' => __('Mis dones', 'flavor-chat-ia'), 'icon' => 'dashicons-gift'],
                'recibidos' => ['label' => __('Recibidos', 'flavor-chat-ia'), 'icon' => 'dashicons-download'],
                'mapa' => ['label' => __('Mapa', 'flavor-chat-ia'), 'icon' => 'dashicons-location-alt'],
            ],
            'economia-suficiencia' => [
                'recursos' => ['label' => __('Recursos', 'flavor-chat-ia'), 'icon' => 'dashicons-book'],
                'mi-huella' => ['label' => __('Mi huella', 'flavor-chat-ia'), 'icon' => 'dashicons-chart-area'],
                'comunidad' => ['label' => __('Comunidad', 'flavor-chat-ia'), 'icon' => 'dashicons-groups'],
                'retos' => ['label' => __('Retos', 'flavor-chat-ia'), 'icon' => 'dashicons-awards'],
                'biblioteca' => ['label' => __('Biblioteca', 'flavor-chat-ia'), 'icon' => 'dashicons-book-alt'],
            ],
            'energia-comunitaria' => [
                'comunidades' => ['label' => __('Red energética', 'flavor-chat-ia'), 'icon' => 'dashicons-groups'],
                'instalaciones' => ['label' => __('Infraestructura', 'flavor-chat-ia'), 'icon' => 'dashicons-admin-tools'],
                'participantes' => ['label' => __('Comunidad energética', 'flavor-chat-ia'), 'icon' => 'dashicons-admin-users', 'requires_login' => true],
                'registrar-lectura' => ['label' => __('Registrar producción', 'flavor-chat-ia'), 'icon' => 'dashicons-chart-line', 'requires_login' => true],
                'mantenimiento' => ['label' => __('Cuidados técnicos', 'flavor-chat-ia'), 'icon' => 'dashicons-hammer'],
                'balance' => ['label' => __('Balance energético', 'flavor-chat-ia'), 'icon' => 'dashicons-chart-pie'],
                'cierres' => ['label' => __('Cierres y repartos', 'flavor-chat-ia'), 'icon' => 'dashicons-archive', 'requires_login' => true],
                'proyectos' => ['label' => __('Proyectos', 'flavor-chat-ia'), 'icon' => 'dashicons-portfolio'],
            ],

            // ═══════════════════════════════════════════════════════════════
            // Biblioteca y Multimedia (con integraciones)
            // ═══════════════════════════════════════════════════════════════
            'biblioteca' => [
                'catalogo' => ['label' => __('Catálogo', 'flavor-chat-ia'), 'icon' => 'dashicons-book-alt'],
                'mis-prestamos' => ['label' => __('Mis préstamos', 'flavor-chat-ia'), 'icon' => 'dashicons-book'],
                'novedades' => ['label' => __('Novedades', 'flavor-chat-ia'), 'icon' => 'dashicons-star-filled'],
                'resenas' => ['label' => __('Reseñas', 'flavor-chat-ia'), 'icon' => 'dashicons-admin-comments'],
                'clubes' => ['label' => __('Clubes', 'flavor-chat-ia'), 'icon' => 'dashicons-groups'],
            ],
            'multimedia' => [
                'galeria' => ['label' => __('Galería', 'flavor-chat-ia'), 'icon' => 'dashicons-format-gallery'],
                'albumes' => ['label' => __('Álbumes', 'flavor-chat-ia'), 'icon' => 'dashicons-images-alt2'],
                'mi-galeria' => ['label' => __('Mi galería', 'flavor-chat-ia'), 'icon' => 'dashicons-admin-media'],
                'subir' => ['label' => __('Subir', 'flavor-chat-ia'), 'icon' => 'dashicons-cloud-upload'],
            ],
            'podcast' => [
                'episodios' => ['label' => __('Episodios', 'flavor-chat-ia'), 'icon' => 'dashicons-playlist-audio'],
                'programas' => ['label' => __('Programas', 'flavor-chat-ia'), 'icon' => 'dashicons-microphone'],
                'mis-suscripciones' => ['label' => __('Mis suscripciones', 'flavor-chat-ia'), 'icon' => 'dashicons-rss', 'requires_login' => true],
                'favoritos' => ['label' => __('Favoritos', 'flavor-chat-ia'), 'icon' => 'dashicons-heart', 'requires_login' => true],
            ],
            'radio' => [
                'en-vivo' => ['label' => __('En vivo', 'flavor-chat-ia'), 'icon' => 'dashicons-controls-volumeon'],
                'programacion' => ['label' => __('Programación', 'flavor-chat-ia'), 'icon' => 'dashicons-calendar-alt'],
                'programas' => ['label' => __('Programas', 'flavor-chat-ia'), 'icon' => 'dashicons-playlist-audio'],
                'mis-programas' => ['label' => __('Mis programas', 'flavor-chat-ia'), 'icon' => 'dashicons-admin-users', 'requires_login' => true],
            ],
            'recetas' => [
                'crear' => ['label' => __('Crear', 'flavor-chat-ia'), 'icon' => 'dashicons-plus-alt'],
                'mis-recetas' => ['label' => __('Mis recetas', 'flavor-chat-ia'), 'icon' => 'dashicons-carrot'],
                'favoritas' => ['label' => __('Favoritas', 'flavor-chat-ia'), 'icon' => 'dashicons-heart'],
                'categorias' => ['label' => __('Categorías', 'flavor-chat-ia'), 'icon' => 'dashicons-category'],
                'ingredientes' => ['label' => __('Ingredientes', 'flavor-chat-ia'), 'icon' => 'dashicons-list-view'],
                'temporada' => ['label' => __('De temporada', 'flavor-chat-ia'), 'icon' => 'dashicons-calendar'],
            ],

            // ═══════════════════════════════════════════════════════════════
            // Ayuda y Cuidados (con integraciones)
            // ═══════════════════════════════════════════════════════════════
            'ayuda-vecinal' => [
                'ofrecer' => ['label' => __('Ofrecer ayuda', 'flavor-chat-ia'), 'icon' => 'dashicons-heart'],
                'solicitar' => ['label' => __('Pedir ayuda', 'flavor-chat-ia'), 'icon' => 'dashicons-plus-alt', 'requires_login' => true],
                'mis-ayudas' => ['label' => __('Mis ayudas', 'flavor-chat-ia'), 'icon' => 'dashicons-admin-users', 'requires_login' => true],
                'voluntarios' => ['label' => __('Voluntarios', 'flavor-chat-ia'), 'icon' => 'dashicons-groups'],
                'mapa' => ['label' => __('Mapa', 'flavor-chat-ia'), 'icon' => 'dashicons-location-alt'],
                'estadisticas' => ['label' => __('Estadísticas', 'flavor-chat-ia'), 'icon' => 'dashicons-chart-bar'],
            ],
            'circulos-cuidados' => [
                'circulos' => ['label' => __('Círculos', 'flavor-chat-ia'), 'icon' => 'dashicons-groups'],
                'necesidades' => ['label' => __('Necesidades', 'flavor-chat-ia'), 'icon' => 'dashicons-sos'],
                'unirse' => ['label' => __('Unirse', 'flavor-chat-ia'), 'icon' => 'dashicons-plus-alt', 'requires_login' => true],
                'mis-circulos' => ['label' => __('Mis círculos', 'flavor-chat-ia'), 'icon' => 'dashicons-admin-users', 'requires_login' => true],
                'registrar-cuidado' => ['label' => __('Registrar cuidado', 'flavor-chat-ia'), 'icon' => 'dashicons-edit', 'requires_login' => true],
            ],
            'justicia-restaurativa' => [
                'informacion' => ['label' => __('Información', 'flavor-chat-ia'), 'icon' => 'dashicons-info'],
                'mis-procesos' => ['label' => __('Mis procesos', 'flavor-chat-ia'), 'icon' => 'dashicons-shield'],
                'mediadores' => ['label' => __('Mediadores', 'flavor-chat-ia'), 'icon' => 'dashicons-businessman'],
                'solicitar' => ['label' => __('Solicitar proceso', 'flavor-chat-ia'), 'icon' => 'dashicons-plus-alt', 'requires_login' => true],
            ],

            // ═══════════════════════════════════════════════════════════════
            // Ecología (con integraciones)
            // ═══════════════════════════════════════════════════════════════
            'compostaje' => [
                'puntos' => ['label' => __('Composteras', 'flavor-chat-ia'), 'icon' => 'dashicons-carrot'],
                'registrar' => ['label' => __('Registrar aporte', 'flavor-chat-ia'), 'icon' => 'dashicons-plus-alt'],
                'mis-aportes' => ['label' => __('Mis aportes', 'flavor-chat-ia'), 'icon' => 'dashicons-chart-bar'],
                'turnos' => ['label' => __('Turnos', 'flavor-chat-ia'), 'icon' => 'dashicons-calendar-alt'],
                'ranking' => ['label' => __('Ranking', 'flavor-chat-ia'), 'icon' => 'dashicons-awards'],
            ],
            'reciclaje' => [
                'mapa' => ['label' => __('Mapa', 'flavor-chat-ia'), 'icon' => 'dashicons-location-alt'],
                'puntos' => ['label' => __('Puntos', 'flavor-chat-ia'), 'icon' => 'dashicons-admin-site-alt3'],
                'mi-impacto' => ['label' => __('Mi impacto', 'flavor-chat-ia'), 'icon' => 'dashicons-chart-line'],
                'guia' => ['label' => __('Guía', 'flavor-chat-ia'), 'icon' => 'dashicons-book'],
            ],
            'huella-ecologica' => [
                'calculadora' => ['label' => __('Calculadora', 'flavor-chat-ia'), 'icon' => 'dashicons-chart-bar'],
                'mi-huella' => ['label' => __('Mi huella', 'flavor-chat-ia'), 'icon' => 'dashicons-palmtree'],
                'proyectos' => ['label' => __('Proyectos', 'flavor-chat-ia'), 'icon' => 'dashicons-groups'],
                'retos' => ['label' => __('Retos', 'flavor-chat-ia'), 'icon' => 'dashicons-awards'],
                'comunidad' => ['label' => __('Comunidad', 'flavor-chat-ia'), 'icon' => 'dashicons-networking'],
                'recursos' => ['label' => __('Recursos', 'flavor-chat-ia'), 'icon' => 'dashicons-book'],
            ],
            'biodiversidad-local' => [
                'registrar' => ['label' => __('Registrar', 'flavor-chat-ia'), 'icon' => 'dashicons-plus-alt'],
                'mis-avistamientos' => ['label' => __('Mis avistamientos', 'flavor-chat-ia'), 'icon' => 'dashicons-visibility'],
                'catalogo' => ['label' => __('Catálogo', 'flavor-chat-ia'), 'icon' => 'dashicons-carrot'],
                'mapa' => ['label' => __('Mapa', 'flavor-chat-ia'), 'icon' => 'dashicons-location-alt'],
                'galeria' => ['label' => __('Galería', 'flavor-chat-ia'), 'icon' => 'dashicons-format-gallery'],
                'proyectos' => ['label' => __('Proyectos', 'flavor-chat-ia'), 'icon' => 'dashicons-portfolio'],
            ],

            // ═══════════════════════════════════════════════════════════════
            // Cultura y Saberes (con integraciones)
            // ═══════════════════════════════════════════════════════════════
            'saberes-ancestrales' => [
                'catalogo' => ['label' => __('Catálogo', 'flavor-chat-ia'), 'icon' => 'dashicons-portfolio'],
                'guardianes' => ['label' => __('Guardianes', 'flavor-chat-ia'), 'icon' => 'dashicons-admin-users'],
                'talleres' => ['label' => __('Talleres', 'flavor-chat-ia'), 'icon' => 'dashicons-welcome-learn-more'],
                'documentar' => ['label' => __('Documentar', 'flavor-chat-ia'), 'icon' => 'dashicons-plus-alt', 'requires_login' => true],
                'aprender' => ['label' => __('Aprender', 'flavor-chat-ia'), 'icon' => 'dashicons-book', 'requires_login' => true],
            ],

            // ═══════════════════════════════════════════════════════════════
            // Trámites y Administración (con integraciones)
            // ═══════════════════════════════════════════════════════════════
            'tramites' => [
                'catalogo' => ['label' => __('Catálogo', 'flavor-chat-ia'), 'icon' => 'dashicons-portfolio'],
                'iniciar' => ['label' => __('Iniciar trámite', 'flavor-chat-ia'), 'icon' => 'dashicons-plus-alt', 'requires_login' => true],
                'mis-tramites' => ['label' => __('Mis trámites', 'flavor-chat-ia'), 'icon' => 'dashicons-admin-users', 'requires_login' => true],
                'seguimiento' => ['label' => __('Seguimiento', 'flavor-chat-ia'), 'icon' => 'dashicons-search'],
            ],
            'avisos-municipales' => [
                'todos' => ['label' => __('Todos', 'flavor-chat-ia'), 'icon' => 'dashicons-megaphone'],
                'urgentes' => ['label' => __('Urgentes', 'flavor-chat-ia'), 'icon' => 'dashicons-warning'],
                'no-leidos' => ['label' => __('Sin leer', 'flavor-chat-ia'), 'icon' => 'dashicons-email'],
                'suscripcion' => ['label' => __('Suscripción', 'flavor-chat-ia'), 'icon' => 'dashicons-bell'],
            ],
            'transparencia' => [
                'documentos' => ['label' => __('Memoria abierta', 'flavor-chat-ia'), 'icon' => 'dashicons-media-document'],
                'presupuestos' => ['label' => __('Recursos comunes', 'flavor-chat-ia'), 'icon' => 'dashicons-chart-pie'],
                'solicitar' => ['label' => __('Pedir información', 'flavor-chat-ia'), 'icon' => 'dashicons-plus-alt', 'requires_login' => true],
                'mis-solicitudes' => ['label' => __('Mis consultas', 'flavor-chat-ia'), 'icon' => 'dashicons-admin-users', 'requires_login' => true],
            ],
            'seguimiento-denuncias' => [
                'listado' => ['label' => __('Mis denuncias', 'flavor-chat-ia'), 'icon' => 'dashicons-list-view'],
                'nueva' => ['label' => __('Nueva', 'flavor-chat-ia'), 'icon' => 'dashicons-flag'],
                'alertas' => ['label' => __('Alertas', 'flavor-chat-ia'), 'icon' => 'dashicons-bell'],
                'archivadas' => ['label' => __('Archivadas', 'flavor-chat-ia'), 'icon' => 'dashicons-archive'],
            ],
            'documentacion-legal' => [
                'listado' => ['label' => __('Documentos', 'flavor-chat-ia'), 'icon' => 'dashicons-media-document'],
                'leyes' => ['label' => __('Leyes', 'flavor-chat-ia'), 'icon' => 'dashicons-clipboard'],
                'modelos' => ['label' => __('Modelos', 'flavor-chat-ia'), 'icon' => 'dashicons-media-document'],
                'sentencias' => ['label' => __('Sentencias', 'flavor-chat-ia'), 'icon' => 'dashicons-hammer'],
                'favoritos' => ['label' => __('Favoritos', 'flavor-chat-ia'), 'icon' => 'dashicons-star-filled'],
            ],

            // ═══════════════════════════════════════════════════════════════
            // Campañas y Mapeo (con integraciones)
            // ═══════════════════════════════════════════════════════════════
            'campanias' => [
                'listado' => ['label' => __('Campañas', 'flavor-chat-ia'), 'icon' => 'dashicons-megaphone'],
                'mis-campanias' => ['label' => __('Mis campañas', 'flavor-chat-ia'), 'icon' => 'dashicons-list-view'],
                'firmar' => ['label' => __('Firmar', 'flavor-chat-ia'), 'icon' => 'dashicons-edit'],
                'acciones' => ['label' => __('Acciones', 'flavor-chat-ia'), 'icon' => 'dashicons-flag'],
            ],
            'mapa-actores' => [
                'listado' => ['label' => __('Actores', 'flavor-chat-ia'), 'icon' => 'dashicons-building'],
                'grafo' => ['label' => __('Grafo', 'flavor-chat-ia'), 'icon' => 'dashicons-networking'],
                'por-tipo' => ['label' => __('Por tipo', 'flavor-chat-ia'), 'icon' => 'dashicons-chart-bar'],
                'relaciones' => ['label' => __('Relaciones', 'flavor-chat-ia'), 'icon' => 'dashicons-randomize'],
            ],

            // ═══════════════════════════════════════════════════════════════
            // Empleo y Trabajo (con integraciones)
            // ═══════════════════════════════════════════════════════════════
            'trabajo-digno' => [
                'ofertas' => ['label' => __('Ofertas', 'flavor-chat-ia'), 'icon' => 'dashicons-clipboard'],
                'emprendimientos' => ['label' => __('Emprendimientos', 'flavor-chat-ia'), 'icon' => 'dashicons-lightbulb'],
                'publicar' => ['label' => __('Publicar oferta', 'flavor-chat-ia'), 'icon' => 'dashicons-plus-alt', 'requires_login' => true],
                'mis-postulaciones' => ['label' => __('Mis postulaciones', 'flavor-chat-ia'), 'icon' => 'dashicons-admin-users', 'requires_login' => true],
                'mi-cv' => ['label' => __('Mi CV', 'flavor-chat-ia'), 'icon' => 'dashicons-id', 'requires_login' => true],
            ],
            'fichaje-empleados' => [
                'fichar' => ['label' => __('Fichar', 'flavor-chat-ia'), 'icon' => 'dashicons-clock', 'requires_login' => true],
                'mis-fichajes' => ['label' => __('Mis fichajes', 'flavor-chat-ia'), 'icon' => 'dashicons-list-view', 'requires_login' => true],
                'calendario' => ['label' => __('Calendario', 'flavor-chat-ia'), 'icon' => 'dashicons-calendar', 'requires_login' => true],
                'informes' => ['label' => __('Informes', 'flavor-chat-ia'), 'icon' => 'dashicons-chart-bar', 'requires_login' => true],
                'vacaciones' => ['label' => __('Vacaciones', 'flavor-chat-ia'), 'icon' => 'dashicons-palmtree', 'requires_login' => true],
            ],

            // ═══════════════════════════════════════════════════════════════
            // Socios y Membresías (con integraciones)
            // ═══════════════════════════════════════════════════════════════
            'socios' => [
                'mi-membresia' => ['label' => __('Mi membresía', 'flavor-chat-ia'), 'icon' => 'dashicons-id', 'requires_login' => true],
                'pagar-cuota' => ['label' => __('Pagar cuota', 'flavor-chat-ia'), 'icon' => 'dashicons-money-alt', 'requires_login' => true],
                'beneficios' => ['label' => __('Beneficios', 'flavor-chat-ia'), 'icon' => 'dashicons-awards', 'requires_login' => true],
                'carnet' => ['label' => __('Carnet', 'flavor-chat-ia'), 'icon' => 'dashicons-id-alt', 'requires_login' => true],
                'historial' => ['label' => __('Historial', 'flavor-chat-ia'), 'icon' => 'dashicons-backup', 'requires_login' => true],
                'directorio' => ['label' => __('Directorio', 'flavor-chat-ia'), 'icon' => 'dashicons-groups'],
            ],

            // ═══════════════════════════════════════════════════════════════
            // Servicios Locales (con integraciones)
            // ═══════════════════════════════════════════════════════════════
            'bares' => [
                'carta' => ['label' => __('Carta', 'flavor-chat-ia'), 'icon' => 'dashicons-food'],
                'reservar' => ['label' => __('Reservar', 'flavor-chat-ia'), 'icon' => 'dashicons-calendar', 'requires_login' => true],
                'mis-reservas' => ['label' => __('Mis reservas', 'flavor-chat-ia'), 'icon' => 'dashicons-list-view', 'requires_login' => true],
                'eventos' => ['label' => __('Eventos', 'flavor-chat-ia'), 'icon' => 'dashicons-calendar-alt'],
                'opiniones' => ['label' => __('Opiniones', 'flavor-chat-ia'), 'icon' => 'dashicons-star-filled'],
                'promociones' => ['label' => __('Promociones', 'flavor-chat-ia'), 'icon' => 'dashicons-megaphone'],
            ],
        ];

        $modulo_normalizado = str_replace('_', '-', $module_id);
        return $acciones_por_modulo[$modulo_normalizado] ?? [];
    }

    /**
     * Obtiene el icono de un módulo
     */
    private function get_module_icon($module_id) {
        $iconos = [
            // Eventos y Actividades
            'eventos' => 'dashicons-calendar-alt',
            'talleres' => 'dashicons-welcome-learn-more',
            'cursos' => 'dashicons-book-alt',
            // Reservas y Espacios
            'reservas' => 'dashicons-calendar',
            'espacios-comunes' => 'dashicons-building',
            'huertos-urbanos' => 'dashicons-carrot',
            // Movilidad
            'bicicletas-compartidas' => 'dashicons-dashboard',
            'carpooling' => 'dashicons-car',
            'parkings' => 'dashicons-location-alt',
            // Comunidad y Social
            'comunidades' => 'dashicons-groups',
            'colectivos' => 'dashicons-networking',
            'foros' => 'dashicons-admin-comments',
            'red-social' => 'dashicons-share',
            // Incidencias y Participación
            'incidencias' => 'dashicons-warning',
            'participacion' => 'dashicons-megaphone',
            'presupuestos-participativos' => 'dashicons-chart-pie',
            // Comercio y Economía
            'marketplace' => 'dashicons-cart',
            'grupos-consumo' => 'dashicons-store',
            'banco-tiempo' => 'dashicons-clock',
            'economia-don' => 'dashicons-heart',
            'economia-suficiencia' => 'dashicons-chart-area',
            'energia-comunitaria' => 'dashicons-lightbulb',
            // Biblioteca y Multimedia
            'biblioteca' => 'dashicons-book',
            'multimedia' => 'dashicons-format-gallery',
            'podcast' => 'dashicons-microphone',
            'radio' => 'dashicons-format-audio',
            'recetas' => 'dashicons-carrot',
            // Ayuda y Cuidados
            'ayuda-vecinal' => 'dashicons-sos',
            'circulos-cuidados' => 'dashicons-heart',
            'justicia-restaurativa' => 'dashicons-shield',
            // Ecología
            'compostaje' => 'dashicons-carrot',
            'reciclaje' => 'dashicons-update',
            'huella-ecologica' => 'dashicons-palmtree',
            'biodiversidad-local' => 'dashicons-carrot',
            // Cultura y Saberes
            'saberes-ancestrales' => 'dashicons-book-alt',
            // Trámites y Administración
            'tramites' => 'dashicons-clipboard',
            'avisos-municipales' => 'dashicons-bell',
            'transparencia' => 'dashicons-visibility',
            'seguimiento-denuncias' => 'dashicons-flag',
            'documentacion-legal' => 'dashicons-media-document',
            // Campañas y Mapeo
            'campanias' => 'dashicons-megaphone',
            'mapa-actores' => 'dashicons-location-alt',
            // Empleo y Trabajo
            'trabajo-digno' => 'dashicons-businessman',
            'fichaje-empleados' => 'dashicons-clock',
            // Socios y Membresías
            'socios' => 'dashicons-id',
            // Servicios Locales
            'bares' => 'dashicons-food',
            // Sistema
            'sello-conciencia' => 'dashicons-heart',
        ];

        $id_normalizado = str_replace('_', '-', $module_id);
        return $iconos[$id_normalizado] ?? 'dashicons-admin-generic';
    }

    /**
     * Obtiene el color de un módulo
     *
     * Prioridad:
     * 1. Color configurado en Design Settings
     * 2. Color por defecto del módulo (hardcoded)
     */
    private function get_module_color($module_id) {
        // Normalizar ID (guiones bajos a guiones)
        $id_normalizado = str_replace('_', '-', $module_id);
        $id_para_settings = str_replace('-', '_', $module_id);

        // 1. Buscar en Design Settings
        $design_settings = get_option('flavor_design_settings', []);
        $settings_key = 'module_color_' . $id_para_settings;

        if (!empty($design_settings[$settings_key])) {
            return $design_settings[$settings_key];
        }

        // 2. Colores por defecto (fallback)
        $colores_default = [
            // Eventos y Actividades
            'eventos' => '#4f46e5',
            'talleres' => '#7c3aed',
            'cursos' => '#2563eb',
            // Reservas y Espacios
            'reservas' => '#0891b2',
            'espacios-comunes' => '#6366f1',
            'huertos-urbanos' => '#16a34a',
            // Movilidad
            'bicicletas-compartidas' => '#0284c7',
            'carpooling' => '#7c3aed',
            'parkings' => '#64748b',
            // Comunidad y Social
            'comunidades' => '#0d9488',
            'colectivos' => '#059669',
            'foros' => '#0891b2',
            'red-social' => '#8b5cf6',
            // Incidencias y Participación
            'incidencias' => '#dc2626',
            'participacion' => '#8b5cf6',
            'presupuestos-participativos' => '#06b6d4',
            // Comercio y Economía
            'marketplace' => '#ea580c',
            'grupos-consumo' => '#22c55e',
            'banco-tiempo' => '#f59e0b',
            'economia-don' => '#ec4899',
            'economia-suficiencia' => '#10b981',
            'energia-comunitaria' => '#f59e0b',
            // Biblioteca y Multimedia
            'biblioteca' => '#65a30d',
            'multimedia' => '#a855f7',
            'podcast' => '#db2777',
            'radio' => '#e11d48',
            'recetas' => '#f97316',
            // Ayuda y Cuidados
            'ayuda-vecinal' => '#ef4444',
            'circulos-cuidados' => '#f43f5e',
            'justicia-restaurativa' => '#8b5cf6',
            // Ecología
            'compostaje' => '#84cc16',
            'reciclaje' => '#22c55e',
            'huella-ecologica' => '#10b981',
            'biodiversidad-local' => '#14b8a6',
            // Cultura y Saberes
            'saberes-ancestrales' => '#a16207',
            // Trámites y Administración
            'tramites' => '#3b82f6',
            'avisos-municipales' => '#f59e0b',
            'transparencia' => '#0ea5e9',
            'seguimiento-denuncias' => '#dc2626',
            'documentacion-legal' => '#6366f1',
            // Campañas y Mapeo
            'campanias' => '#f97316',
            'mapa-actores' => '#14b8a6',
            // Empleo y Trabajo
            'trabajo-digno' => '#0284c7',
            'fichaje-empleados' => '#64748b',
            // Socios y Membresías
            'socios' => '#7c3aed',
            // Servicios Locales
            'bares' => '#ca8a04',
            // Sistema
            'sello-conciencia' => '#9333ea',
        ];

        return $colores_default[$id_normalizado] ?? '#6b7280';
    }

    /**
     * Obtiene el título de la página
     */
    private function get_page_title() {
        $section = get_query_var('flavor_section', '');

        if ($section === 'mi-cuenta') {
            return __('Mi Cuenta', 'flavor-chat-ia');
        }

        if (!empty($this->current_module)) {
            $module = $this->get_module_instance($this->current_module);
            $name = $module ? ($module->name ?? '') : '';
            if (empty($name)) {
                $name = ucfirst(str_replace(['-', '_'], ' ', $this->current_module));
            }
            return $name;
        }

        return __('Dashboard', 'flavor-chat-ia');
    }

    /**
     * Filtra el título de la página
     */
    public function filter_page_title($title) {
        if (get_query_var('flavor_app')) {
            $title['title'] = $this->get_page_title();
        }
        return $title;
    }

    /**
     * Shortcode [flavor_app]
     */
    public function render_app($atts) {
        $atts = shortcode_atts([
            'section' => '',
            'module' => '',
        ], $atts);

        $this->disable_shortcode_unautop();
        ob_start();

        if (!empty($atts['module'])) {
            $this->current_module = sanitize_key($atts['module']);
            $this->render_module_content();
        } elseif (!empty($atts['section'])) {
            $this->render_section($atts['section']);
        } else {
            $this->render_dashboard();
        }

        return ob_get_clean();
    }

    /**
     * Flush rewrite rules
     */
    public function flush_rules() {
        $this->add_rewrite_rules();
        flush_rewrite_rules();
    }

    /**
     * Estilos inline
     */
    private function get_inline_styles() {
        return '
        :root {
            /* Heredar de variables del Theme Manager, con fallbacks */
            --fap-primary: var(--flavor-primary, #4f46e5);
            --fap-primary-dark: var(--flavor-primary-dark, #4338ca);
            --fap-bg: var(--flavor-bg, #f8fafc);
            --fap-surface: var(--flavor-bg, #ffffff);
            --fap-text: var(--flavor-text, #111827);
            --fap-text-muted: var(--flavor-text-muted, #6b7280);
            --fap-border: var(--flavor-border, #e5e7eb);
            --fap-radius: var(--flavor-radius, 12px);
            --fap-shadow: var(--flavor-shadow, 0 1px 3px rgba(0,0,0,0.1));
        }

        body.flavor-app-page {
            margin: 0;
            padding: 0;
            background: var(--fap-bg);
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
            color: var(--fap-text);
        }

        .flavor-app-container {
            min-height: 100vh;
            display: flex;
            flex-direction: column;
        }

        /* Header */
        .flavor-app-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 0 24px;
            height: 64px;
            background: var(--fap-surface);
            border-bottom: 1px solid var(--fap-border);
            position: sticky;
            top: 0;
            z-index: 100;
        }

        .fah-logo {
            text-decoration: none;
            display: flex;
            align-items: center;
        }

        .fah-logo-img {
            height: 40px;
            width: auto;
            max-width: 180px;
            object-fit: contain;
        }

        .fah-site-name {
            font-size: 1.25rem;
            font-weight: 700;
            color: var(--fap-text);
        }

        .fah-nav {
            display: flex;
            gap: 4px;
        }

        .fah-nav-item {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 8px 16px;
            color: var(--fap-text-muted);
            text-decoration: none;
            border-radius: 8px;
            font-size: 0.9375rem;
            font-weight: 500;
            transition: all 0.2s;
        }

        .fah-nav-item:hover {
            background: var(--fap-bg);
            color: var(--fap-text);
        }

        .fah-nav-item.active {
            background: var(--fap-primary);
            color: white;
        }

        .fah-user {
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .fah-user img {
            width: 32px;
            height: 32px;
            border-radius: 50%;
        }

        .fah-user-name {
            font-size: 0.875rem;
            font-weight: 500;
        }

        .fah-logout {
            color: var(--fap-text-muted);
            padding: 4px;
        }

        .fah-logout:hover {
            color: #dc2626;
        }

        .fah-login-btn {
            padding: 8px 20px;
            background: var(--fap-primary);
            color: white;
            border-radius: 8px;
            text-decoration: none;
            font-weight: 500;
        }

        /* Layout */
        .flavor-app-layout {
            display: flex;
            flex: 1;
            max-width: 1400px;
            width: 100%;
            margin: 0 auto;
            padding: 24px;
            gap: 24px;
        }

        /* Sidebar */
        .flavor-app-sidebar {
            width: 240px;
            flex-shrink: 0;
        }

        .fas-nav {
            display: flex;
            flex-direction: column;
            gap: 4px;
            background: var(--fap-surface);
            padding: 12px;
            border-radius: var(--fap-radius);
            box-shadow: var(--fap-shadow);
        }

        .fas-nav-item {
            display: flex;
            align-items: center;
            gap: 10px;
            padding: 10px 14px;
            color: var(--fap-text-muted);
            text-decoration: none;
            border-radius: 8px;
            font-size: 0.9375rem;
            transition: all 0.2s;
        }

        .fas-nav-item:hover {
            background: var(--fap-bg);
            color: var(--fap-text);
            text-decoration: underline;
        }

        .fas-nav-item.active {
            background: rgba(79, 70, 229, 0.1);
            color: var(--fap-primary);
            font-weight: 500;
        }

        /* Separador del sidebar */
        .fas-nav-separator {
            height: 1px;
            background: var(--fap-border, #e5e7eb);
            margin: 8px 0;
        }

        /* Enlace de administración */
        .fas-nav-item--admin {
            color: var(--fap-text-muted);
            font-size: 0.875rem;
            opacity: 0.85;
        }

        .fas-nav-item--admin:hover {
            background: rgba(245, 158, 11, 0.1);
            color: #d97706;
        }

        .fas-nav-item--admin .dashicons {
            font-size: 1rem;
            width: 1rem;
            height: 1rem;
        }

        /* Main */
        .flavor-app-main {
            flex: 1;
            min-width: 0;
        }

        /* Headers */
        .flavor-dashboard-header,
        .flavor-module-header {
            margin-bottom: 24px;
        }

        .flavor-dashboard-header h1,
        .flavor-module-header h1 {
            font-size: 1.875rem;
            font-weight: 700;
            margin: 0 0 8px;
        }

        .flavor-dashboard-header p {
            color: var(--fap-text-muted);
            margin: 0;
        }

        .fmh-breadcrumb {
            display: flex;
            align-items: center;
            gap: 8px;
            font-size: 0.875rem;
            color: var(--fap-text-muted);
            margin-bottom: 8px;
        }

        .fmh-breadcrumb a {
            color: var(--fap-primary);
            text-decoration: none;
        }

        .fmh-breadcrumb a:hover {
            text-decoration: underline;
        }

        /* Modules Grid */
        .flavor-modules-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
            gap: 16px;
        }

        .flavor-module-card {
            display: flex;
            align-items: center;
            gap: 16px;
            padding: 20px;
            background: var(--fap-surface);
            border-radius: var(--fap-radius);
            box-shadow: var(--fap-shadow);
            text-decoration: none;
            color: var(--fap-text);
            transition: all 0.2s;
        }

        .flavor-module-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 24px rgba(0,0,0,0.12);
        }

        .fmc-icon {
            display: flex;
            align-items: center;
            justify-content: center;
            width: 48px;
            height: 48px;
            background: var(--card-color, var(--fap-primary));
            border-radius: 12px;
            flex-shrink: 0;
        }

        .fmc-icon .dashicons {
            color: white;
            font-size: 24px;
            width: 24px;
            height: 24px;
        }

        .fmc-content {
            flex: 1;
            min-width: 0;
        }

        .fmc-content h3 {
            font-size: 1rem;
            font-weight: 600;
            margin: 0 0 4px;
        }

        .fmc-content p {
            font-size: 0.875rem;
            color: var(--fap-text-muted);
            margin: 0;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        .fmc-arrow {
            color: var(--fap-text-muted);
        }

        /* Empty/Error states */
        .flavor-empty-state,
        .flavor-login-required,
        .flavor-not-found {
            text-align: center;
            padding: 60px 20px;
            background: var(--fap-surface);
            border-radius: var(--fap-radius);
        }

        .flavor-empty-state .dashicons,
        .flavor-login-required .dashicons,
        .flavor-not-found .dashicons {
            font-size: 48px;
            width: 48px;
            height: 48px;
            color: var(--fap-text-muted);
            margin-bottom: 16px;
        }

        .flavor-login-required h2,
        .flavor-not-found h2 {
            font-size: 1.5rem;
            margin: 0 0 8px;
        }

        .flavor-btn {
            display: inline-block;
            padding: 12px 24px;
            background: var(--fap-primary);
            color: white;
            border-radius: 8px;
            text-decoration: none;
            font-weight: 500;
            margin-top: 16px;
        }

        .flavor-btn:hover {
            background: var(--fap-primary-dark);
        }

        /* Footer */
        .flavor-app-footer {
            padding: 24px;
            text-align: center;
            color: var(--fap-text-muted);
            font-size: 0.875rem;
            border-top: 1px solid var(--fap-border);
            margin-top: auto;
        }

        /* Module Dashboard */
        .flavor-module-dashboard {
            --module-color: #4f46e5;
        }

        .fmd-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            gap: 24px;
            margin-bottom: 20px;
            padding: 22px 24px;
            background: var(--fap-surface);
            border-radius: var(--fap-radius);
            box-shadow: var(--fap-shadow);
            border: 1px solid color-mix(in srgb, var(--module-color) 10%, var(--fap-border));
        }

        .fmd-header-left {
            min-width: 0;
            flex: 1;
        }

        .fmd-header-actions {
            display: flex;
            align-items: flex-start;
            justify-content: flex-end;
            flex-wrap: wrap;
            gap: 10px;
        }

        .fmd-breadcrumb {
            display: flex;
            align-items: center;
            gap: 8px;
            font-size: 0.8125rem;
            color: var(--fap-text-muted);
            margin-bottom: 10px;
            line-height: 1.4;
        }

        .fmd-breadcrumb a {
            color: var(--module-color);
            text-decoration: none;
        }

        .fmd-title-row {
            display: flex;
            align-items: flex-start;
            gap: 14px;
        }

        .fmd-eyebrow {
            display: inline-flex;
            align-items: center;
            margin-bottom: 8px;
            padding: 5px 10px;
            border-radius: 999px;
            background: color-mix(in srgb, var(--module-color) 12%, white);
            color: var(--module-color);
            font-size: 0.6875rem;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .fmd-icon {
            display: flex;
            align-items: center;
            justify-content: center;
            width: 52px;
            height: 52px;
            background: linear-gradient(145deg, var(--module-color), color-mix(in srgb, var(--module-color) 70%, #111827));
            border-radius: 16px;
            box-shadow: 0 12px 24px color-mix(in srgb, var(--module-color) 18%, transparent);
            flex-shrink: 0;
        }

        .fmd-icon .dashicons {
            font-size: 24px;
            width: 24px;
            height: 24px;
            color: white;
        }

        .fmd-header h1 {
            font-size: 1.625rem;
            font-weight: 700;
            margin: 0;
            line-height: 1.15;
        }

        .fmd-subtitle {
            max-width: 62ch;
            font-size: 0.9375rem;
            line-height: 1.55;
            color: var(--fap-text-muted);
            margin: 6px 0 0;
        }

        .fmd-primary-btn {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 12px 24px;
            background: var(--flavor-dashboard-btn-primary, var(--module-color)) !important;
            color: var(--flavor-dashboard-btn-text, white) !important;
            border-radius: 10px;
            text-decoration: none !important;
            font-weight: 600;
            transition: all 0.2s;
        }

        .fmd-primary-btn:hover {
            filter: brightness(1.1);
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(0,0,0,0.15);
            color: var(--flavor-dashboard-btn-text, white) !important;
        }

        /* Stats Grid */
        .fmd-stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 16px;
            margin-bottom: 24px;
        }

        .fmd-stat-card {
            display: flex;
            align-items: center;
            gap: 16px;
            padding: 20px;
            background: var(--fap-surface);
            border-radius: var(--fap-radius);
            box-shadow: var(--fap-shadow);
        }

        .fmd-stat-icon {
            display: flex;
            align-items: center;
            justify-content: center;
            width: 48px;
            height: 48px;
            border-radius: 12px;
            flex-shrink: 0;
        }

        .fmd-stat-icon .dashicons {
            font-size: 24px;
            width: 24px;
            height: 24px;
            color: white;
        }

        .fmd-stat-content {
            flex: 1;
        }

        .fmd-stat-value {
            display: block;
            font-size: 1.5rem;
            font-weight: 700;
            line-height: 1.2;
        }

        .fmd-stat-label {
            display: block;
            font-size: 0.875rem;
            color: var(--fap-text-muted);
        }

        .fmd-stat-trend {
            display: flex;
            align-items: center;
            gap: 2px;
            font-size: 0.8125rem;
            font-weight: 600;
            padding: 4px 8px;
            border-radius: 20px;
        }

        .fmd-stat-trend.positive {
            background: #d1fae5;
            color: #059669;
        }

        .fmd-stat-trend.negative {
            background: #fee2e2;
            color: #dc2626;
        }

        .fmd-stat-trend .dashicons {
            font-size: 14px;
            width: 14px;
            height: 14px;
        }

        /* Widgets específicos del módulo */
        .fmd-module-widgets {
            margin-bottom: 24px;
        }

        .fmd-widgets-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 20px;
        }

        .fmd-widget {
            background: var(--fap-surface);
            border-radius: var(--fap-radius);
            box-shadow: var(--fap-shadow);
            overflow: hidden;
        }

        .fmd-widget--small {
            grid-column: span 1;
        }

        .fmd-widget--medium {
            grid-column: span 1;
        }

        .fmd-widget--large {
            grid-column: span 2;
        }

        @media (max-width: 768px) {
            .fmd-widget--large {
                grid-column: span 1;
            }
        }

        .fmd-widget-header {
            display: flex;
            align-items: center;
            gap: 10px;
            padding: 16px 20px;
            border-bottom: 1px solid var(--fap-border);
            background: var(--fap-bg);
        }

        .fmd-widget-header .dashicons {
            font-size: 20px;
            width: 20px;
            height: 20px;
            color: var(--module-color);
        }

        .fmd-widget-header h4 {
            margin: 0;
            font-size: 1rem;
            font-weight: 600;
            color: var(--fap-text);
        }

        .fmd-widget-content {
            padding: 20px;
        }

        .fmd-widget-link {
            display: flex;
            text-decoration: none;
            color: inherit;
            transition: background 0.2s;
        }
        .fmd-widget-link:hover {
            background: rgba(0,0,0,0.02);
        }
        .fmd-widget-link .fmd-widget-header {
            flex: 1;
            border-bottom: 1px solid var(--fap-border);
        }
        .fmd-widget-arrow {
            margin-left: auto;
            opacity: 0.4;
            transition: opacity 0.2s, transform 0.2s;
        }
        .fmd-widget-link:hover .fmd-widget-arrow {
            opacity: 1;
            transform: translateX(3px);
        }

        .fmd-widget-footer {
            display: flex;
            gap: 8px;
            padding: 12px 20px;
            border-top: 1px solid var(--fap-border);
            background: var(--fap-bg);
        }

        .fmd-widget-btn {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 8px 14px;
            font-size: 0.85rem;
            font-weight: 500;
            text-decoration: none;
            border-radius: 6px;
            transition: all 0.2s;
            background: var(--fap-surface);
            color: var(--fap-text);
            border: 1px solid var(--fap-border);
        }
        .fmd-widget-btn:hover {
            background: var(--fap-hover);
            border-color: var(--module-color);
            color: var(--module-color);
        }
        .fmd-widget-btn .dashicons {
            font-size: 16px;
            width: 16px;
            height: 16px;
        }
        .fmd-widget-btn--primary {
            background: var(--flavor-dashboard-btn-primary, var(--module-color)) !important;
            color: var(--flavor-dashboard-btn-text, white) !important;
            border-color: var(--flavor-dashboard-btn-primary, var(--module-color)) !important;
        }
        .fmd-widget-btn--primary:hover {
            filter: brightness(1.1);
            color: var(--flavor-dashboard-btn-text, white) !important;
        }

        /* Widget summary styles (resúmenes breves) */
        .fmd-widget-summary {
            display: flex;
            flex-direction: column;
            gap: 12px;
        }
        .fmd-widget-summary--empty {
            text-align: center;
            padding: 10px 0;
        }
        .fmd-widget-summary--empty .dashicons {
            font-size: 32px;
            width: 32px;
            height: 32px;
            color: var(--fap-text-muted);
            display: block;
            margin: 0 auto 8px;
        }
        .fmd-widget-summary--empty p {
            margin: 0 0 8px;
            color: var(--fap-text-muted);
        }
        .fmd-widget-summary--success .fmd-summary-status {
            display: flex;
            align-items: center;
            gap: 8px;
        }
        .fmd-status-dot {
            width: 10px;
            height: 10px;
            border-radius: 50%;
            background: var(--fap-text-muted);
        }
        .fmd-status-dot--success {
            background: #22c55e;
        }
        .fmd-status-dot--info {
            background: #3b82f6;
        }
        .fmd-status-dot--warning {
            background: #f59e0b;
        }
        .fmd-widget-summary--info .fmd-summary-status {
            display: flex;
            align-items: center;
            gap: 8px;
        }
        .fmd-widget-summary--warning .fmd-summary-status {
            display: flex;
            align-items: center;
            gap: 8px;
        }
        .fmd-summary-detail--warning {
            color: #f59e0b;
        }
        .fmd-summary-detail--warning .dashicons {
            color: #f59e0b;
        }
        .fmd-summary-detail {
            margin: 0;
            font-size: 0.875rem;
            color: var(--fap-text-muted);
            display: flex;
            align-items: center;
            gap: 6px;
        }
        .fmd-summary-detail .dashicons {
            font-size: 14px;
            width: 14px;
            height: 14px;
        }
        .fmd-summary-stat {
            display: flex;
            align-items: baseline;
            gap: 8px;
        }
        .fmd-stat-number {
            font-size: 1.5rem;
            font-weight: 700;
            color: var(--fap-text);
        }
        .fmd-summary-stat--primary .fmd-stat-number {
            color: var(--module-color, #3b82f6);
        }
        .fmd-summary-stat--highlight .fmd-stat-number {
            color: #22c55e;
        }
        .fmd-stat-label {
            font-size: 0.875rem;
            color: var(--fap-text-muted);
        }
        .fmd-widget-empty {
            margin: 0;
            padding: 10px 0;
            text-align: center;
            color: var(--fap-text-muted);
            font-size: 0.9rem;
        }
        .fmd-link-action {
            display: inline-flex;
            align-items: center;
            gap: 4px;
            font-size: 0.85rem;
            color: var(--module-color, #3b82f6);
            text-decoration: none;
        }
        .fmd-link-action:hover {
            text-decoration: underline;
        }

        /* Secciones especiales */
        .fmd-section-content {
            padding: 24px 0;
        }

        .fmd-empty-state,
        .fmd-coming-soon {
            text-align: center;
            padding: 60px 20px;
            background: var(--fap-surface);
            border-radius: var(--fap-radius);
        }

        .fmd-empty-state .dashicons,
        .fmd-coming-soon .dashicons {
            font-size: 48px;
            width: 48px;
            height: 48px;
            color: var(--fap-text-muted);
            margin-bottom: 16px;
        }

        .fmd-empty-state h3,
        .fmd-coming-soon h3 {
            font-size: 1.25rem;
            margin: 0 0 8px;
            color: var(--fap-text);
        }

        .fmd-empty-state p,
        .fmd-coming-soon p {
            color: var(--fap-text-muted);
            margin: 0 0 20px;
        }

        /* Notificaciones */
        .fmd-notifications-list {
            display: flex;
            flex-direction: column;
            gap: 12px;
        }

        .fmd-notification-item {
            display: flex;
            align-items: flex-start;
            gap: 16px;
            padding: 16px 20px;
            background: var(--fap-surface);
            border-radius: var(--fap-radius);
            box-shadow: var(--fap-shadow);
        }

        .fmd-notification-item.fmd-notification--read {
            opacity: 0.7;
        }

        .fmd-notification-icon {
            flex-shrink: 0;
            width: 40px;
            height: 40px;
            display: flex;
            align-items: center;
            justify-content: center;
            background: var(--fap-bg);
            border-radius: 50%;
        }

        .fmd-notification-icon .dashicons {
            font-size: 20px;
            color: var(--fap-primary);
        }

        .fmd-notification-content {
            flex: 1;
        }

        .fmd-notification-content h4 {
            margin: 0 0 4px;
            font-size: 0.9375rem;
            font-weight: 600;
        }

        .fmd-notification-content p {
            margin: 0 0 8px;
            font-size: 0.875rem;
            color: var(--fap-text-muted);
        }

        .fmd-notification-time {
            font-size: 0.75rem;
            color: var(--fap-text-muted);
        }

        .fmd-notification-link {
            flex-shrink: 0;
            display: flex;
            align-items: center;
            justify-content: center;
            width: 36px;
            height: 36px;
            color: var(--fap-text-muted);
            text-decoration: none;
            border-radius: 50%;
            transition: all 0.2s;
        }

        .fmd-notification-link:hover {
            background: var(--fap-bg);
            color: var(--fap-primary);
        }

        /* Perfil */
        .fmd-profile-card {
            background: var(--fap-surface);
            border-radius: var(--fap-radius);
            box-shadow: var(--fap-shadow);
            overflow: hidden;
        }

        .fmd-profile-header {
            display: flex;
            align-items: center;
            gap: 24px;
            padding: 32px;
            background: linear-gradient(135deg, var(--fap-primary), var(--fap-primary-dark));
            color: white;
        }

        .fmd-profile-avatar img {
            width: 120px;
            height: 120px;
            border-radius: 50%;
            border: 4px solid rgba(255,255,255,0.3);
        }

        .fmd-profile-info h2 {
            margin: 0 0 8px;
            font-size: 1.5rem;
        }

        .fmd-profile-email,
        .fmd-profile-since {
            opacity: 0.9;
            margin: 4px 0;
        }

        .fmd-profile-form {
            padding: 32px;
        }

        .fmd-form-group {
            margin-bottom: 20px;
        }

        .fmd-form-group label {
            display: block;
            margin-bottom: 8px;
            font-weight: 500;
            color: var(--fap-text);
        }

        .fmd-form-group input,
        .fmd-form-group textarea {
            width: 100%;
            padding: 12px 16px;
            border: 1px solid var(--fap-border);
            border-radius: 8px;
            font-size: 0.9375rem;
            transition: border-color 0.2s;
        }

        .fmd-form-group input:focus,
        .fmd-form-group textarea:focus {
            outline: none;
            border-color: var(--fap-primary);
        }

        .fmd-form-group input[readonly] {
            background: var(--fap-bg);
            cursor: not-allowed;
        }

        /* Puntos */
        .fmd-points-card {
            background: var(--fap-surface);
            border-radius: var(--fap-radius);
            box-shadow: var(--fap-shadow);
            padding: 40px;
        }

        .fmd-points-summary {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 40px;
            flex-wrap: wrap;
        }

        .fmd-points-value {
            display: flex;
            align-items: center;
            gap: 16px;
        }

        .fmd-points-value .dashicons {
            font-size: 48px;
            width: 48px;
            height: 48px;
            color: #eab308;
        }

        .fmd-points-number {
            font-size: 3rem;
            font-weight: 700;
            color: var(--fap-text);
        }

        .fmd-points-label {
            font-size: 1.125rem;
            color: var(--fap-text-muted);
        }

        .fmd-level-badge {
            display: inline-flex;
            align-items: center;
            padding: 12px 24px;
            background: linear-gradient(135deg, #fbbf24, #f59e0b);
            color: white;
            font-weight: 600;
            font-size: 1rem;
            border-radius: 30px;
        }

        /* Tabs */
        .fmd-tabs {
            background: var(--fap-surface);
            border-radius: var(--fap-radius);
            box-shadow: var(--fap-shadow);
            overflow: hidden;
            border: 1px solid var(--fap-border);
        }

        .fmd-tabs-nav {
            display: flex;
            align-items: center;
            gap: 8px;
            border-bottom: 1px solid var(--fap-border);
            padding: 14px 16px 12px;
            overflow-x: auto;
            scrollbar-width: thin;
            background:
                linear-gradient(180deg, color-mix(in srgb, var(--module-color) 4%, white), transparent 120%);
        }

        .fmd-tab {
            display: flex;
            align-items: center;
            gap: 8px;
            padding: 10px 14px;
            background: transparent;
            border: 1px solid transparent;
            border-radius: 999px;
            font-size: 0.875rem;
            font-weight: 600;
            color: var(--fap-text-muted);
            cursor: pointer;
            white-space: nowrap;
            transition: all 0.2s ease;
        }

        .fmd-tab:hover {
            color: var(--fap-text);
            background: color-mix(in srgb, var(--module-color) 8%, white);
        }

        .fmd-tab.active {
            color: var(--module-color);
            border-color: color-mix(in srgb, var(--module-color) 24%, transparent);
            background: color-mix(in srgb, var(--module-color) 12%, white);
            box-shadow: inset 0 0 0 1px color-mix(in srgb, var(--module-color) 10%, transparent);
        }

        .fmd-tab .dashicons {
            font-size: 16px;
            width: 16px;
            height: 16px;
        }

        .fmd-tab-panels {
            padding: 26px 24px 24px;
        }

        .fmd-tab-panel {
            display: none;
        }

        .fmd-tab-panel.active {
            display: block;
        }

        /* Tab Separator - Network Modules */
        .fmd-tabs-separator {
            display: flex;
            align-items: center;
            margin: 0 2px;
            padding: 8px 0;
            flex-shrink: 0;
        }

        .fmd-tabs-separator::before {
            content: "";
            width: 1px;
            height: 28px;
            background: var(--fap-border);
        }

        /* Integration Tabs */
        .fmd-tab--integration {
            opacity: 0.92;
            background: color-mix(in srgb, var(--fap-bg) 70%, white);
        }

        .fmd-tab--integration:hover {
            opacity: 1;
        }

        .fmd-tab--integration.active {
            opacity: 1;
        }

        /* Tab Badge */
        .fmd-tab-badge {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            min-width: 18px;
            height: 18px;
            padding: 0 5px;
            font-size: 0.6875rem;
            font-weight: 600;
            line-height: 1;
            color: #fff;
            background: var(--module-color, #3b82f6);
            border-radius: 9px;
            margin-left: 4px;
        }

        .fmd-tab.active .fmd-tab-badge {
            background: var(--module-color, #3b82f6);
        }

        .fmd-tab--integration .fmd-tab-badge {
            background: var(--fap-text-muted);
        }

        .fmd-tab--integration.active .fmd-tab-badge {
            background: var(--module-color, #3b82f6);
        }

        .fmd-panel-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
        }

        .fmd-panel-header h3 {
            font-size: 1.125rem;
            font-weight: 600;
            margin: 0;
        }

        .fmd-filters {
            display: flex;
            gap: 12px;
        }

        .fmd-search {
            padding: 10px 16px;
            border: 1px solid var(--fap-border);
            border-radius: 8px;
            font-size: 0.9375rem;
            width: 200px;
        }

        .fmd-search:focus {
            outline: none;
            border-color: var(--module-color);
            box-shadow: 0 0 0 3px rgba(79, 70, 229, 0.1);
        }

        .fmd-filter-select {
            padding: 10px 16px;
            border: 1px solid var(--fap-border);
            border-radius: 8px;
            font-size: 0.9375rem;
            background: white;
        }

        /* Activity Timeline */
        .fmd-activity-list h3 {
            font-size: 1.125rem;
            font-weight: 600;
            margin: 0 0 20px;
        }

        .fmd-timeline {
            list-style: none;
            padding: 0;
            margin: 0;
        }

        .fmd-timeline-item {
            display: flex;
            gap: 16px;
            padding: 16px 0;
            border-bottom: 1px solid var(--fap-border);
        }

        .fmd-timeline-item:last-child {
            border-bottom: none;
        }

        .fmd-timeline-marker {
            width: 12px;
            height: 12px;
            border-radius: 50%;
            flex-shrink: 0;
            margin-top: 4px;
        }

        .fmd-timeline-content {
            flex: 1;
        }

        .fmd-timeline-header {
            display: flex;
            justify-content: space-between;
            margin-bottom: 4px;
        }

        .fmd-timeline-action {
            font-weight: 600;
        }

        .fmd-timeline-time {
            font-size: 0.8125rem;
            color: var(--fap-text-muted);
        }

        .fmd-timeline-text {
            font-size: 0.9375rem;
            color: var(--fap-text-muted);
            margin: 0 0 8px;
        }

        .fmd-timeline-user {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            font-size: 0.8125rem;
            color: var(--fap-text-muted);
        }

        .fmd-timeline-user img {
            width: 24px;
            height: 24px;
            border-radius: 50%;
        }

        /* Messages */
        .fmd-messages-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
        }

        .fmd-messages-header h3 {
            font-size: 1.125rem;
            font-weight: 600;
            margin: 0;
        }

        .fmd-btn-secondary {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 10px 16px;
            background: var(--fap-bg);
            border: 1px solid var(--fap-border);
            border-radius: 8px;
            font-size: 0.875rem;
            font-weight: 500;
            color: var(--fap-text);
            cursor: pointer;
            transition: all 0.2s;
        }

        .fmd-btn-secondary:hover {
            background: var(--fap-surface);
            border-color: var(--module-color);
        }

        .fmd-message-item {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 16px;
            border-radius: 10px;
            margin-bottom: 8px;
            cursor: pointer;
            transition: background 0.2s;
        }

        .fmd-message-item:hover {
            background: var(--fap-bg);
        }

        .fmd-message-item.unread {
            background: rgba(79, 70, 229, 0.05);
        }

        .fmd-message-indicator {
            width: 8px;
            height: 8px;
            border-radius: 50%;
            background: transparent;
            flex-shrink: 0;
        }

        .fmd-message-item.unread .fmd-message-indicator {
            background: var(--module-color);
        }

        .fmd-message-content {
            flex: 1;
            min-width: 0;
        }

        .fmd-message-subject {
            font-weight: 600;
            margin-bottom: 2px;
        }

        .fmd-message-preview {
            font-size: 0.875rem;
            color: var(--fap-text-muted);
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        .fmd-message-time {
            font-size: 0.75rem;
            color: var(--fap-text-muted);
            flex-shrink: 0;
        }

        /* Calendar */
        .fmd-calendar-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
        }

        .fmd-calendar-header h3 {
            font-size: 1.125rem;
            font-weight: 600;
            margin: 0;
        }

        .fmd-calendar-nav {
            width: 36px;
            height: 36px;
            border: 1px solid var(--fap-border);
            border-radius: 8px;
            background: white;
            cursor: pointer;
            font-size: 1rem;
        }

        .fmd-calendar-grid {
            display: grid;
            grid-template-columns: repeat(7, 1fr);
            gap: 4px;
            margin-bottom: 24px;
        }

        .fmd-calendar-day-name {
            text-align: center;
            font-size: 0.75rem;
            font-weight: 600;
            color: var(--fap-text-muted);
            padding: 8px;
        }

        .fmd-calendar-day {
            aspect-ratio: 1;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            border-radius: 8px;
            cursor: pointer;
            position: relative;
            transition: all 0.2s;
        }

        .fmd-calendar-day:hover:not(.empty) {
            background: var(--fap-bg);
        }

        .fmd-calendar-day.today {
            background: var(--module-color);
            color: white;
        }

        .fmd-calendar-day.has-event .fmd-calendar-event-dot {
            position: absolute;
            bottom: 4px;
            width: 6px;
            height: 6px;
            background: var(--module-color);
            border-radius: 50%;
        }

        .fmd-calendar-day.today .fmd-calendar-event-dot {
            background: white;
        }

        .fmd-calendar-upcoming h4 {
            font-size: 0.9375rem;
            font-weight: 600;
            margin: 0 0 12px;
        }

        .fmd-calendar-upcoming ul {
            list-style: none;
            padding: 0;
            margin: 0;
        }

        .fmd-calendar-upcoming li {
            display: flex;
            gap: 12px;
            padding: 10px 0;
            border-bottom: 1px solid var(--fap-border);
        }

        .fmd-event-date {
            font-size: 0.8125rem;
            font-weight: 600;
            color: var(--module-color);
            width: 50px;
        }

        .fmd-event-title {
            font-size: 0.9375rem;
        }

        /* Empty state */
        .fmd-empty {
            text-align: center;
            padding: 40px 20px;
            color: var(--fap-text-muted);
        }

        .fmd-empty .dashicons {
            font-size: 32px;
            width: 32px;
            height: 32px;
            margin-bottom: 12px;
            opacity: 0.5;
        }

        /* Item Detail */
        .fmd-item-detail {
            background: var(--fap-surface);
            border-radius: var(--fap-radius);
            padding: 24px;
        }

        .fmd-item-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 24px;
            padding-bottom: 24px;
            border-bottom: 1px solid var(--fap-border);
        }

        .fmd-item-header h2 {
            margin: 0;
        }

        .fmd-item-actions {
            display: flex;
            gap: 8px;
        }

        .fmd-btn-danger {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 10px 16px;
            background: #fee2e2;
            border: none;
            border-radius: 8px;
            font-size: 0.875rem;
            font-weight: 500;
            color: #dc2626;
            cursor: pointer;
        }

        .fmd-btn-danger:hover {
            background: #fecaca;
        }

        /* Responsive */
        @media (max-width: 1024px) {
            .fah-center {
                display: none;
            }

            .fmd-stats-grid {
                grid-template-columns: repeat(2, 1fr);
            }
        }

        @media (max-width: 768px) {
            .flavor-app-layout {
                flex-direction: column;
                padding: 16px;
            }

            .flavor-app-sidebar {
                width: 100%;
            }

            .fas-nav {
                flex-direction: row;
                overflow-x: auto;
                padding: 8px;
            }

            .fas-nav-item {
                white-space: nowrap;
            }

            .flavor-modules-grid {
                grid-template-columns: 1fr;
            }

            .flavor-app-header {
                padding: 0 16px;
            }

            .fah-user-name {
                display: none;
            }

            .fmd-header {
                flex-direction: column;
                gap: 14px;
                padding: 18px 16px;
            }

            .fmd-title-row {
                gap: 12px;
            }

            .fmd-header-actions {
                width: 100%;
                justify-content: flex-start;
            }

            .fmd-header h1 {
                font-size: 1.375rem;
            }

            .fmd-subtitle {
                font-size: 0.875rem;
            }

            .fmd-stats-grid {
                grid-template-columns: 1fr;
            }

            .fmd-panel-header {
                flex-direction: column;
                gap: 12px;
                align-items: stretch;
            }

            .fmd-filters {
                flex-direction: column;
            }

            .fmd-search {
                width: 100%;
            }

            .fmd-tabs-nav {
                gap: 6px;
                padding: 10px 10px 8px;
            }

            .fmd-tab {
                padding: 10px 12px;
                font-size: 0.8125rem;
            }

            .fmd-tab span:not(.dashicons) {
                display: none;
            }

            .fmd-tab-panels {
                padding: 20px 16px 16px;
            }
        }

        /* WordPress Menu Integration */
        .fah-nav-wp .fah-wp-menu {
            display: flex;
            align-items: center;
            gap: 4px;
            list-style: none;
            margin: 0;
            padding: 0;
        }

        .fah-nav-wp .fah-wp-menu li {
            position: relative;
        }

        .fah-nav-wp .fah-wp-menu > li > a {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 8px 16px;
            color: var(--fap-text-muted);
            text-decoration: none;
            border-radius: 8px;
            font-size: 0.9375rem;
            font-weight: 500;
            transition: all 0.2s;
        }

        .fah-nav-wp .fah-wp-menu > li > a:hover {
            background: var(--fap-bg);
            color: var(--fap-text);
        }

        .fah-nav-wp .fah-wp-menu > li.current-menu-item > a,
        .fah-nav-wp .fah-wp-menu > li.current_page_item > a {
            background: var(--fap-primary);
            color: white;
        }

        /* Submenus */
        .fah-nav-wp .fah-wp-menu .sub-menu {
            display: none;
            position: absolute;
            top: 100%;
            left: 0;
            min-width: 200px;
            background: var(--fap-surface);
            border-radius: var(--fap-radius);
            box-shadow: 0 4px 12px rgba(0,0,0,0.15);
            padding: 8px;
            list-style: none;
            margin: 0;
            z-index: 100;
        }

        .fah-nav-wp .fah-wp-menu li:hover > .sub-menu {
            display: block;
        }

        .fah-nav-wp .fah-wp-menu .sub-menu a {
            display: block;
            padding: 10px 14px;
            color: var(--fap-text);
            text-decoration: none;
            border-radius: 6px;
            font-size: 0.875rem;
        }

        .fah-nav-wp .fah-wp-menu .sub-menu a:hover {
            background: var(--fap-bg);
        }

        /* Footer Widgets */
        .faf-widgets {
            background: var(--fap-bg);
            padding: 48px 24px;
            border-top: 1px solid var(--fap-border);
        }

        .faf-widgets-container {
            max-width: 1200px;
            margin: 0 auto;
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 32px;
        }

        .faf-widget-area h3,
        .faf-widget-area .widget-title {
            font-size: 1rem;
            font-weight: 600;
            margin: 0 0 16px;
            color: var(--fap-text);
        }

        .faf-widget-area ul {
            list-style: none;
            padding: 0;
            margin: 0;
        }

        .faf-widget-area ul li {
            margin-bottom: 8px;
        }

        .faf-widget-area ul li a {
            color: var(--fap-text-muted);
            text-decoration: none;
            font-size: 0.9375rem;
        }

        .faf-widget-area ul li a:hover {
            color: var(--fap-primary);
        }

        /* Footer Bottom */
        .faf-bottom {
            padding: 24px;
            border-top: 1px solid var(--fap-border);
        }

        .faf-bottom-container {
            max-width: 1200px;
            margin: 0 auto;
            display: flex;
            flex-wrap: wrap;
            align-items: center;
            justify-content: space-between;
            gap: 16px;
        }

        .faf-copyright {
            color: var(--fap-text-muted);
            font-size: 0.875rem;
        }

        .faf-copyright p {
            margin: 0;
        }

        .faf-menu .faf-menu-list {
            display: flex;
            gap: 24px;
            list-style: none;
            padding: 0;
            margin: 0;
        }

        .faf-menu .faf-menu-list a {
            color: var(--fap-text-muted);
            text-decoration: none;
            font-size: 0.875rem;
        }

        .faf-menu .faf-menu-list a:hover {
            color: var(--fap-primary);
        }

        .faf-legal {
            display: flex;
            gap: 16px;
        }

        .faf-legal a {
            color: var(--fap-text-muted);
            text-decoration: none;
            font-size: 0.8125rem;
        }

        .faf-legal a:hover {
            color: var(--fap-primary);
        }

        .fah-dashboard-link {
            display: flex;
            align-items: center;
            justify-content: center;
            width: 32px;
            height: 32px;
            border-radius: 6px;
            color: var(--fap-text-muted);
            transition: all 0.2s;
        }

        .fah-dashboard-link:hover {
            background: var(--fap-bg);
            color: var(--fap-primary);
        }

        @media (max-width: 768px) {
            .faf-widgets-container {
                grid-template-columns: 1fr;
            }

            .faf-bottom-container {
                flex-direction: column;
                text-align: center;
            }

            .faf-menu .faf-menu-list {
                flex-wrap: wrap;
                justify-content: center;
            }

            .faf-legal {
                justify-content: center;
            }

            .fah-nav-wp {
                display: none;
            }
        }
        ';
    }

    /**
     * AJAX: Guardar configuración de usuario
     */
    public function ajax_save_user_settings() {
        // Verificar nonce
        if (!wp_verify_nonce($_POST['nonce'] ?? '', 'flavor_save_settings')) {
            wp_send_json_error(__('Sesión expirada. Recarga la página.', 'flavor-chat-ia'));
        }

        // Verificar usuario logueado
        if (!is_user_logged_in()) {
            wp_send_json_error(__('Debes iniciar sesión.', 'flavor-chat-ia'));
        }

        $usuario_id = get_current_user_id();
        $section = sanitize_key($_POST['section'] ?? 'profile');

        $response_data = [];

        switch ($section) {
            case 'profile':
                $avatar_url = $this->save_profile_settings($usuario_id, $_POST);
                if ($avatar_url) {
                    $response_data['avatar_url'] = $avatar_url;
                }
                break;

            case 'account':
                $result = $this->save_account_settings($usuario_id, $_POST);
                if (is_wp_error($result)) {
                    wp_send_json_error($result->get_error_message());
                }
                break;

            case 'notifications':
                $this->save_notification_settings($usuario_id, $_POST);
                break;

            case 'privacy':
                $this->save_privacy_settings($usuario_id, $_POST);
                break;

            case 'appearance':
                $this->save_appearance_settings($usuario_id, $_POST);
                break;
        }

        wp_send_json_success($response_data);
    }

    /**
     * Guardar configuración de perfil
     * @return string|null URL del avatar si se subió
     */
    private function save_profile_settings($usuario_id, $data) {
        // Actualizar datos de usuario
        $userdata = [
            'ID' => $usuario_id,
            'first_name' => sanitize_text_field($data['first_name'] ?? ''),
            'last_name' => sanitize_text_field($data['last_name'] ?? ''),
            'display_name' => sanitize_text_field($data['display_name'] ?? ''),
            'description' => sanitize_textarea_field($data['description'] ?? ''),
            'user_url' => esc_url_raw($data['user_url'] ?? ''),
        ];

        wp_update_user($userdata);

        // Guardar ubicación como meta
        update_user_meta($usuario_id, 'flavor_location', sanitize_text_field($data['location'] ?? ''));

        // Manejar avatar si se subió
        $avatar_url = null;
        if (!empty($_FILES['avatar']['tmp_name']) && !$_FILES['avatar']['error']) {
            $avatar_url = $this->handle_avatar_upload($usuario_id);
        }

        return $avatar_url;
    }

    /**
     * Guardar configuración de cuenta
     */
    private function save_account_settings($usuario_id, $data) {
        $user = get_userdata($usuario_id);

        // Cambiar email si es diferente
        if (!empty($data['email']) && $data['email'] !== $user->user_email) {
            if (!is_email($data['email'])) {
                return new WP_Error('invalid_email', __('Email no válido.', 'flavor-chat-ia'));
            }
            if (email_exists($data['email'])) {
                return new WP_Error('email_exists', __('Este email ya está en uso.', 'flavor-chat-ia'));
            }

            wp_update_user([
                'ID' => $usuario_id,
                'user_email' => sanitize_email($data['email']),
            ]);
        }

        // Cambiar contraseña si se proporcionó
        if (!empty($data['new_password'])) {
            // Verificar contraseña actual
            if (empty($data['current_password']) || !wp_check_password($data['current_password'], $user->user_pass, $usuario_id)) {
                return new WP_Error('wrong_password', __('La contraseña actual no es correcta.', 'flavor-chat-ia'));
            }

            // Verificar que coinciden
            if ($data['new_password'] !== $data['confirm_password']) {
                return new WP_Error('password_mismatch', __('Las contraseñas no coinciden.', 'flavor-chat-ia'));
            }

            // Verificar longitud mínima
            if (strlen($data['new_password']) < 8) {
                return new WP_Error('password_short', __('La contraseña debe tener al menos 8 caracteres.', 'flavor-chat-ia'));
            }

            wp_set_password($data['new_password'], $usuario_id);
        }

        return true;
    }

    /**
     * Guardar configuración de notificaciones
     */
    private function save_notification_settings($usuario_id, $data) {
        $settings = get_user_meta($usuario_id, 'flavor_user_settings', true) ?: [];

        $settings['notifications'] = [
            'email_mentions' => !empty($data['email_mentions']),
            'email_messages' => !empty($data['email_messages']),
            'email_updates' => !empty($data['email_updates']),
            'push_enabled' => !empty($data['push_enabled']),
        ];

        update_user_meta($usuario_id, 'flavor_user_settings', $settings);
    }

    /**
     * Guardar configuración de privacidad
     */
    private function save_privacy_settings($usuario_id, $data) {
        $settings = get_user_meta($usuario_id, 'flavor_user_settings', true) ?: [];

        $settings['privacy'] = [
            'profile_visibility' => sanitize_key($data['profile_visibility'] ?? 'public'),
            'show_email' => !empty($data['show_email']),
            'show_activity' => !empty($data['show_activity']),
            'allow_messages' => !empty($data['allow_messages']),
        ];

        update_user_meta($usuario_id, 'flavor_user_settings', $settings);
    }

    /**
     * Guardar configuración de apariencia
     */
    private function save_appearance_settings($usuario_id, $data) {
        $settings = get_user_meta($usuario_id, 'flavor_user_settings', true) ?: [];

        $settings['appearance'] = [
            'theme' => sanitize_key($data['theme'] ?? 'auto'),
            'compact_mode' => !empty($data['compact_mode']),
        ];

        update_user_meta($usuario_id, 'flavor_user_settings', $settings);

        // También guardar en cookie/localStorage para aplicación inmediata
        // El JS del frontend se encargará de aplicar el tema
    }

    /**
     * Manejar subida de avatar
     * @return string|null URL del avatar subido
     */
    private function handle_avatar_upload($usuario_id) {
        if (!function_exists('wp_handle_upload')) {
            require_once ABSPATH . 'wp-admin/includes/file.php';
        }

        // Validar tipo de archivo
        $allowed_types = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
        if (!in_array($_FILES['avatar']['type'], $allowed_types)) {
            return null;
        }

        // Validar tamaño (máximo 2MB)
        if ($_FILES['avatar']['size'] > 2 * 1024 * 1024) {
            return null;
        }

        $upload = wp_handle_upload($_FILES['avatar'], ['test_form' => false]);

        if (!empty($upload['error'])) {
            return null;
        }

        // Eliminar avatar anterior si existe
        $old_avatar = get_user_meta($usuario_id, 'flavor_custom_avatar', true);
        if ($old_avatar) {
            // Intentar eliminar el archivo antiguo
            $old_file = str_replace(wp_upload_dir()['baseurl'], wp_upload_dir()['basedir'], $old_avatar);
            if (file_exists($old_file)) {
                @unlink($old_file);
            }
        }

        // Guardar URL del avatar como meta del usuario
        update_user_meta($usuario_id, 'flavor_custom_avatar', $upload['url']);

        // Crear tamaños adicionales si es necesario
        if (function_exists('wp_create_image_subsizes')) {
            $metadata = wp_create_image_subsizes($upload['file'], 0);
        }

        return $upload['url'];
    }

    /**
     * AJAX: Eliminar cuenta de usuario
     */
    public function ajax_delete_user_account() {
        // Verificar nonce
        if (!wp_verify_nonce($_POST['nonce'] ?? '', 'flavor_delete_account')) {
            wp_send_json_error(__('Sesión expirada. Recarga la página.', 'flavor-chat-ia'));
        }

        // Verificar usuario logueado
        if (!is_user_logged_in()) {
            wp_send_json_error(__('Debes iniciar sesión.', 'flavor-chat-ia'));
        }

        $usuario_id = get_current_user_id();

        // No permitir eliminar administradores
        if (user_can($usuario_id, 'manage_options')) {
            wp_send_json_error(__('Los administradores no pueden eliminar su cuenta desde aquí.', 'flavor-chat-ia'));
        }

        // Requiere el archivo necesario
        require_once ABSPATH . 'wp-admin/includes/user.php';

        // Eliminar el usuario (sus posts se asignan al admin ID 1)
        $result = wp_delete_user($usuario_id, 1);

        if ($result) {
            // Cerrar sesión
            wp_logout();
            wp_send_json_success();
        } else {
            wp_send_json_error(__('No se pudo eliminar la cuenta.', 'flavor-chat-ia'));
        }
    }
}

/**
 * Walker personalizado para el menú de WordPress en páginas dinámicas
 */
class Flavor_Dynamic_Menu_Walker extends Walker_Nav_Menu {

    /**
     * Inicia el elemento de lista
     */
    public function start_el(&$output, $item, $depth = 0, $args = null, $id = 0) {
        $classes = empty($item->classes) ? [] : (array) $item->classes;
        $classes[] = 'menu-item-' . $item->ID;

        if ($depth === 0) {
            $classes[] = 'fah-menu-item';
        }

        $class_names = join(' ', apply_filters('nav_menu_css_class', array_filter($classes), $item, $args, $depth));
        $class_names = $class_names ? ' class="' . esc_attr($class_names) . '"' : '';

        $id_attr = apply_filters('nav_menu_item_id', 'menu-item-' . $item->ID, $item, $args, $depth);
        $id_attr = $id_attr ? ' id="' . esc_attr($id_attr) . '"' : '';

        $output .= '<li' . $id_attr . $class_names . '>';

        $atts = [];
        $atts['title']  = !empty($item->attr_title) ? $item->attr_title : '';
        $atts['target'] = !empty($item->target) ? $item->target : '';
        $atts['rel']    = !empty($item->xfn) ? $item->xfn : '';
        $atts['href']   = !empty($item->url) ? $item->url : '';

        $atts = apply_filters('nav_menu_link_attributes', $atts, $item, $args, $depth);

        $attributes = '';
        foreach ($atts as $attr => $value) {
            if (!empty($value)) {
                $value = ('href' === $attr) ? esc_url($value) : esc_attr($value);
                $attributes .= ' ' . $attr . '="' . $value . '"';
            }
        }

        $title = apply_filters('the_title', $item->title, $item->ID);
        $title = apply_filters('nav_menu_item_title', $title, $item, $args, $depth);

        $item_output = $args->before ?? '';
        $item_output .= '<a' . $attributes . '>';
        $item_output .= ($args->link_before ?? '') . $title . ($args->link_after ?? '');
        $item_output .= '</a>';
        $item_output .= $args->after ?? '';

        $output .= apply_filters('walker_nav_menu_start_el', $item_output, $item, $depth, $args);
    }
}

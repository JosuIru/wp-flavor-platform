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

        // Cargar assets
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

        // CSS global del portal (incluye variables de Design Settings)
        if (file_exists(FLAVOR_CHAT_IA_PATH . 'assets/css/portal.css')) {
            wp_enqueue_style(
                'flavor-portal',
                FLAVOR_CHAT_IA_URL . 'assets/css/portal.css',
                [],
                FLAVOR_CHAT_IA_VERSION
            );
        }

        // CSS del dashboard
        if (file_exists(FLAVOR_CHAT_IA_PATH . 'assets/css/dashboard-vb-widgets.css')) {
            wp_enqueue_style(
                'flavor-dynamic-pages',
                FLAVOR_CHAT_IA_URL . 'assets/css/dashboard-vb-widgets.css',
                ['flavor-portal'],
                FLAVOR_CHAT_IA_VERSION
            );
        }

        // CSS del módulo específico (si existe)
        $module = $this->current_module ?? '';
        if ($module) {
            // Convertir module_id (guión_bajo) a slug de directorio (guión)
            $module_dir = str_replace('_', '-', $module);

            // Paths posibles para el CSS del módulo
            $module_css_paths = [
                // Con formato de directorio (guiones)
                FLAVOR_CHAT_IA_PATH . "includes/modules/{$module_dir}/assets/frontend.css",
                FLAVOR_CHAT_IA_PATH . "includes/modules/{$module_dir}/assets/{$module_dir}-frontend.css",
                FLAVOR_CHAT_IA_PATH . "includes/modules/{$module_dir}/assets/gc-frontend.css",
                FLAVOR_CHAT_IA_PATH . "includes/modules/{$module_dir}/assets/gc-catalogo.css",
                // Con formato de ID (guiones bajos) como fallback
                FLAVOR_CHAT_IA_PATH . "includes/modules/{$module}/assets/frontend.css",
                FLAVOR_CHAT_IA_PATH . "includes/modules/{$module}/assets/{$module}-frontend.css",
            ];

            foreach ($module_css_paths as $css_path) {
                if (file_exists($css_path)) {
                    $css_handle = 'flavor-module-' . basename($css_path, '.css');
                    wp_enqueue_style(
                        $css_handle,
                        str_replace(FLAVOR_CHAT_IA_PATH, FLAVOR_CHAT_IA_URL, $css_path),
                        ['flavor-portal'],
                        FLAVOR_CHAT_IA_VERSION
                    );
                }
            }
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
                FLAVOR_CHAT_IA_PATH . "includes/modules/{$module_dir}/assets/frontend.js",
                FLAVOR_CHAT_IA_PATH . "includes/modules/{$module_dir}/assets/{$module_dir}-frontend.js",
                FLAVOR_CHAT_IA_PATH . "includes/modules/{$module_dir}/assets/gc-frontend.js",
                // Con formato de ID (guiones bajos) como fallback
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

                    // Pasar configuración al script
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
            }
        }
    }

    /**
     * Renderiza la página completa
     */
    private function render_page($section = '') {
        // Headers
        status_header(200);
        nocache_headers();

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

            <?php wp_footer(); ?>
        </body>
        </html>
        <?php
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
        <header class="flavor-app-header">
            <div class="fah-left">
                <a href="<?php echo esc_url(home_url('/')); ?>" class="fah-logo">
                    <?php if (has_custom_logo()): ?>
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

        $actions = $this->get_module_actions($this->current_module);
        ?>
        <aside class="flavor-app-sidebar">
            <nav class="fas-nav">
                <a href="<?php echo esc_url(home_url('/' . $this->base_path . '/' . $this->current_module . '/')); ?>"
                   class="fas-nav-item <?php echo $this->current_action === 'index' ? 'active' : ''; ?>">
                    <span class="dashicons dashicons-list-view"></span>
                    <?php esc_html_e('Ver todos', 'flavor-chat-ia'); ?>
                </a>

                <?php foreach ($actions as $action_id => $action): ?>
                    <a href="<?php echo esc_url(home_url('/' . $this->base_path . '/' . $this->current_module . '/' . $action_id . '/')); ?>"
                       class="fas-nav-item <?php echo $this->current_action === $action_id ? 'active' : ''; ?>">
                        <span class="dashicons <?php echo esc_attr($action['icon'] ?? 'dashicons-admin-generic'); ?>"></span>
                        <?php echo esc_html($action['label'] ?? ucfirst($action_id)); ?>
                    </a>
                <?php endforeach; ?>
            </nav>
        </aside>
        <?php
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
        ?>
        <div class="flavor-dashboard-header">
            <h1><?php esc_html_e('Dashboard', 'flavor-chat-ia'); ?></h1>
            <p><?php esc_html_e('Resumen de todos tus módulos activos', 'flavor-chat-ia'); ?></p>
        </div>

        <?php
        // Usar el shortcode del dashboard unificado si existe
        if (shortcode_exists('flavor_unified_dashboard')) {
            echo do_shortcode('[flavor_unified_dashboard]');
        } else {
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
            <p><?php esc_html_e('Dashboard de usuario no disponible.', 'flavor-chat-ia'); ?></p>
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
            echo '<p>' . esc_html__('Mi Red Social no está disponible.', 'flavor-chat-ia') . '</p>';
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

        ?>
        <div class="flavor-module-dashboard" style="--module-color: <?php echo esc_attr($module_color); ?>;">

            <!-- Header del módulo -->
            <div class="fmd-header">
                <div class="fmd-header-left">
                    <div class="fmd-breadcrumb">
                        <a href="<?php echo esc_url(home_url('/' . $this->base_path . '/')); ?>">
                            <?php esc_html_e('Dashboard', 'flavor-chat-ia'); ?>
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
                            <h1><?php echo esc_html($module_name); ?></h1>
                            <p class="fmd-subtitle"><?php echo esc_html($module->description ?? ''); ?></p>
                        </div>
                    </div>
                </div>
                <div class="fmd-header-actions">
                    <?php $this->render_module_quick_actions(); ?>
                </div>
            </div>

            <?php if ($this->current_action === 'index' || empty($this->current_action)): ?>

                <!-- Estadísticas del módulo -->
                <div class="fmd-stats-grid">
                    <?php $this->render_module_stats(); ?>
                </div>

                <!-- Widgets específicos del módulo -->
                <?php $this->render_module_specific_widgets($module); ?>

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
                $tiene_integraciones = !empty($tabs_integracion);
                ?>
                <div class="fmd-tabs <?php echo $tiene_integraciones ? 'has-integrations' : ''; ?>">
                    <nav class="fmd-tabs-nav">
                        <?php
                        $is_first = true;
                        foreach ($tabs_base as $tab_id => $tab_info):
                            $badge = $this->get_tab_badge_value($tab_info);
                        ?>
                            <button class="fmd-tab <?php echo $is_first ? 'active' : ''; ?>" data-tab="<?php echo esc_attr($tab_id); ?>">
                                <span class="dashicons <?php echo esc_attr($tab_info['icon']); ?>"></span>
                                <?php echo esc_html($tab_info['label']); ?>
                                <?php if ($badge > 0): ?>
                                    <span class="fmd-tab-badge"><?php echo esc_html($badge); ?></span>
                                <?php endif; ?>
                            </button>
                        <?php
                            $is_first = false;
                        endforeach;

                        // Tabs de integración (módulos de red)
                        if ($tiene_integraciones):
                        ?>
                        <span class="fmd-tabs-separator" title="<?php esc_attr_e('Módulos de red', 'flavor-chat-ia'); ?>"></span>
                        <?php
                        foreach ($tabs_integracion as $tab_id => $tab_info):
                            $badge = $this->get_tab_badge_value($tab_info);
                        ?>
                            <button class="fmd-tab fmd-tab--integration" data-tab="<?php echo esc_attr($tab_id); ?>" data-source="<?php echo esc_attr($tab_info['source_module'] ?? ''); ?>">
                                <span class="dashicons <?php echo esc_attr($tab_info['icon']); ?>"></span>
                                <?php echo esc_html($tab_info['label']); ?>
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
                        foreach ($tabs as $tab_id => $tab_info):
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

            <?php elseif ($this->is_create_action($this->current_action)): ?>
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
                            <p><?php esc_html_e('El sistema de formularios está cargando. Por favor, recarga la página.', 'flavor-chat-ia'); ?></p>
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
                            <p><?php esc_html_e('El sistema de formularios está cargando. Por favor, recarga la página.', 'flavor-chat-ia'); ?></p>
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
     * Renderiza acciones rápidas del módulo
     */
    private function render_module_quick_actions() {
        $actions = $this->get_module_actions($this->current_module);
        $primary_action = array_key_first($actions);

        if ($primary_action && isset($actions[$primary_action])): ?>
            <a href="<?php echo esc_url(home_url('/' . $this->base_path . '/' . $this->current_module . '/' . $primary_action . '/')); ?>"
               class="fmd-primary-btn">
                <span class="dashicons <?php echo esc_attr($actions[$primary_action]['icon'] ?? 'dashicons-plus-alt'); ?>"></span>
                <?php echo esc_html($actions[$primary_action]['label']); ?>
            </a>
        <?php endif;
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
                                <a href="<?php echo esc_url($base_url . $action_key . '/'); ?>" class="fmd-widget-btn">
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
            // Widget: Ciclo actual (resumen) + Mi Pedido (estado)
            // Tabs: Catálogo completo, Pedidos, Productores, Ciclos
            'grupos-consumo' => [
                ['title' => __('Ciclo Actual', 'flavor-chat-ia'), 'icon' => 'dashicons-update', 'size' => 'medium', 'shortcode' => '[gc_ciclo_actual]', 'action' => 'ciclo'],
                ['title' => __('Mi Pedido', 'flavor-chat-ia'), 'icon' => 'dashicons-cart', 'size' => 'large', 'shortcode' => '[gc_mi_pedido]', 'action' => 'mi-pedido'],
            ],

            // === EVENTOS ===
            // Widget: Resumen personal | Tabs: Listados completos
            'eventos' => [
                ['title' => __('Próximo Evento', 'flavor-chat-ia'), 'icon' => 'dashicons-calendar', 'size' => 'medium', 'shortcode' => '[eventos_proximo]', 'action' => 'proximos'],
                ['title' => __('Mis Inscripciones', 'flavor-chat-ia'), 'icon' => 'dashicons-tickets-alt', 'size' => 'medium', 'shortcode' => '[eventos_mis_inscripciones limite="3"]', 'action' => 'inscripciones'],
            ],

            // === RESERVAS ===
            // Widget: Próxima reserva | Tabs: Listados y calendario
            'reservas' => [
                ['title' => __('Próxima Reserva', 'flavor-chat-ia'), 'icon' => 'dashicons-calendar-alt', 'size' => 'medium', 'shortcode' => '[reservas_proxima]', 'action' => 'mis-reservas'],
                ['title' => __('Calendario', 'flavor-chat-ia'), 'icon' => 'dashicons-calendar', 'size' => 'large', 'shortcode' => '[reservas_calendario_mini]', 'action' => 'calendario'],
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
                ['title' => __('Préstamos Activos', 'flavor-chat-ia'), 'icon' => 'dashicons-book', 'size' => 'medium', 'shortcode' => '[biblioteca_prestamos_activos]', 'action' => 'mis-prestamos'],
                ['title' => __('Novedades', 'flavor-chat-ia'), 'icon' => 'dashicons-star-filled', 'size' => 'large', 'shortcode' => '[biblioteca_novedades limit="4"]', 'action' => 'novedades'],
            ],

            // === MARKETPLACE ===
            // Widget: Estadísticas | Tabs: Listados
            'marketplace' => [
                ['title' => __('Mis Estadísticas', 'flavor-chat-ia'), 'icon' => 'dashicons-chart-bar', 'size' => 'medium', 'shortcode' => '[marketplace_mis_stats]', 'action' => 'mis-anuncios'],
                ['title' => __('Destacados', 'flavor-chat-ia'), 'icon' => 'dashicons-star-filled', 'size' => 'large', 'shortcode' => '[marketplace_destacados limit="4"]', 'action' => 'listado'],
            ],

            // === INCIDENCIAS ===
            // Widget: Resumen estado | Tabs: Listados y mapa
            'incidencias' => [
                ['title' => __('Estado Incidencias', 'flavor-chat-ia'), 'icon' => 'dashicons-chart-pie', 'size' => 'medium', 'shortcode' => '[incidencias_resumen_estado]', 'action' => 'mis-reportes'],
                ['title' => __('Últimas Reportadas', 'flavor-chat-ia'), 'icon' => 'dashicons-flag', 'size' => 'large', 'shortcode' => '[incidencias_recientes limite="4"]', 'action' => 'listado'],
            ],

            // === BANCO DE TIEMPO ===
            // Widget: Mi saldo y estadísticas | Tabs: Listados
            'banco-tiempo' => [
                ['title' => __('Mi Saldo', 'flavor-chat-ia'), 'icon' => 'dashicons-clock', 'size' => 'medium', 'shortcode' => '[banco_tiempo_mi_saldo]', 'action' => 'mi-saldo'],
                ['title' => __('Últimos Intercambios', 'flavor-chat-ia'), 'icon' => 'dashicons-randomize', 'size' => 'large', 'shortcode' => '[banco_tiempo_ultimos_intercambios limite="4"]', 'action' => 'intercambios'],
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
                ['title' => __('Mi Actividad', 'flavor-chat-ia'), 'icon' => 'dashicons-groups', 'size' => 'medium', 'shortcode' => '[colectivos_mi_actividad]', 'action' => 'mis-colectivos'],
                ['title' => __('Destacados', 'flavor-chat-ia'), 'icon' => 'dashicons-star-filled', 'size' => 'large', 'shortcode' => '[colectivos_destacados limit="4"]', 'action' => 'listado'],
            ],

            // === COMUNIDADES ===
            // Widget: Mi comunidad | Tabs: Directorio y mapa
            'comunidades' => [
                ['title' => __('Mi Comunidad', 'flavor-chat-ia'), 'icon' => 'dashicons-admin-multisite', 'size' => 'medium', 'shortcode' => '[mi_comunidad_resumen]', 'action' => 'mis-comunidades'],
                ['title' => __('Actividad Reciente', 'flavor-chat-ia'), 'icon' => 'dashicons-rss', 'size' => 'large', 'shortcode' => '[comunidades_actividad limit="5"]', 'action' => 'tablon'],
            ],

            // === SOCIOS ===
            // Widget: Estado membresía | Tabs: Directorio
            'socios' => [
                ['title' => __('Mi Carnet', 'flavor-chat-ia'), 'icon' => 'dashicons-id', 'size' => 'large', 'shortcode' => '[socios_mi_carnet]', 'action' => 'mi-membresia'],
            ],

            // === FOROS ===
            // Widget: Actividad reciente | Tabs: Discusiones
            'foros' => [
                ['title' => __('Actividad Reciente', 'flavor-chat-ia'), 'icon' => 'dashicons-format-chat', 'size' => 'large', 'shortcode' => '[foros_actividad_reciente limit="5"]', 'action' => 'actividad'],
            ],

            // === CHAT GRUPOS ===
            // Widget: Mensajes sin leer | Tabs: Grupos
            'chat-grupos' => [
                ['title' => __('Mensajes Nuevos', 'flavor-chat-ia'), 'icon' => 'dashicons-email-alt', 'size' => 'medium', 'shortcode' => '[chat_grupos_sin_leer]', 'action' => 'mis-grupos'],
                ['title' => __('Grupos Activos', 'flavor-chat-ia'), 'icon' => 'dashicons-groups', 'size' => 'large', 'shortcode' => '[chat_grupos_activos limit="4"]', 'action' => 'mis-grupos'],
            ],

            // === CHAT INTERNO ===
            // Widget: Mensajes sin leer | Tabs: Bandeja
            'chat-interno' => [
                ['title' => __('Sin Leer', 'flavor-chat-ia'), 'icon' => 'dashicons-email', 'size' => 'large', 'shortcode' => '[chat_mensajes_sin_leer]', 'action' => 'bandeja'],
            ],

            // === RED SOCIAL ===
            // Widget: Notificaciones | Tabs: Feed
            'red-social' => [
                ['title' => __('Notificaciones', 'flavor-chat-ia'), 'icon' => 'dashicons-bell', 'size' => 'medium', 'shortcode' => '[rs_notificaciones]', 'action' => 'notificaciones'],
                ['title' => __('Mi Actividad', 'flavor-chat-ia'), 'icon' => 'dashicons-admin-users', 'size' => 'medium', 'shortcode' => '[rs_mi_actividad]', 'action' => 'mi-perfil'],
            ],

            // === PARTICIPACIÓN ===
            // Widget: Votaciones activas | Tabs: Propuestas
            'participacion' => [
                ['title' => __('Votaciones Activas', 'flavor-chat-ia'), 'icon' => 'dashicons-thumbs-up', 'size' => 'medium', 'shortcode' => '[votaciones_activas]', 'action' => 'votaciones'],
                ['title' => __('Mis Propuestas', 'flavor-chat-ia'), 'icon' => 'dashicons-clipboard', 'size' => 'medium', 'shortcode' => '[mis_propuestas_resumen]', 'action' => 'propuestas'],
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
                ['title' => __('Pendientes', 'flavor-chat-ia'), 'icon' => 'dashicons-clipboard', 'size' => 'medium', 'shortcode' => '[tramites_pendientes]', 'action' => 'mis-expedientes'],
                ['title' => __('Más Solicitados', 'flavor-chat-ia'), 'icon' => 'dashicons-chart-bar', 'size' => 'large', 'shortcode' => '[tramites_populares limit="4"]', 'action' => 'catalogo'],
            ],

            // === TRANSPARENCIA ===
            // Widget: Resumen presupuesto | Tabs: Portal completo
            'transparencia' => [
                ['title' => __('Presupuesto', 'flavor-chat-ia'), 'icon' => 'dashicons-chart-pie', 'size' => 'large', 'shortcode' => '[transparencia_presupuesto_resumen]', 'action' => 'presupuesto'],
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
     * Obtiene el label legible de una acción
     *
     * @param string $action Slug de la acción
     * @return string Label legible
     */
    private function get_action_label($action) {
        $labels = [
            'mi-pedido'      => __('Mi Pedido', 'flavor-chat-ia'),
            'mi-cesta'       => __('Mi Cesta', 'flavor-chat-ia'),
            'productos'      => __('Productos', 'flavor-chat-ia'),
            'productores'    => __('Productores', 'flavor-chat-ia'),
            'ciclos'         => __('Ciclos', 'flavor-chat-ia'),
            'mapa'           => __('Mapa', 'flavor-chat-ia'),
            'calendario'     => __('Calendario', 'flavor-chat-ia'),
            'listado'        => __('Listado', 'flavor-chat-ia'),
            'crear'          => __('Crear', 'flavor-chat-ia'),
            'nuevo'          => __('Nuevo', 'flavor-chat-ia'),
            'nueva'          => __('Nueva', 'flavor-chat-ia'),
            'editar'         => __('Editar', 'flavor-chat-ia'),
            'ver'            => __('Detalle', 'flavor-chat-ia'),
            'mis-reservas'   => __('Mis Reservas', 'flavor-chat-ia'),
            'mis-pedidos'    => __('Mis Pedidos', 'flavor-chat-ia'),
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
     * Obtiene los tabs del módulo
     *
     * @param object $module Instancia del módulo
     * @return array Tabs del módulo
     */
    private function get_module_tabs($module) {
        // Si el módulo tiene método get_dashboard_tabs(), usarlo
        if ($module && method_exists($module, 'get_dashboard_tabs')) {
            $tabs_modulo = $module->get_dashboard_tabs();
            if (!empty($tabs_modulo)) {
                return $tabs_modulo;
            }
        }

        // Tabs específicos por módulo
        $module_id = str_replace('_', '-', $this->current_module);

        // ============================================================
        // TABS POR MÓDULO: Complementan los widgets con vistas completas
        // Widgets = Resumen rápido | Tabs = Contenido completo organizado
        // ============================================================
        $tabs_config = [
            // === GRUPOS DE CONSUMO ===
            // Widgets: Mi Pedido (resumen), Productos (destacados)
            // Tabs: Navegación completa del ciclo de consumo
            'grupos-consumo' => [
                'productos'   => ['label' => __('Catálogo', 'flavor-chat-ia'), 'icon' => 'dashicons-products'],
                'pedidos'     => ['label' => __('Mis Pedidos', 'flavor-chat-ia'), 'icon' => 'dashicons-cart'],
                'productores' => ['label' => __('Productores', 'flavor-chat-ia'), 'icon' => 'dashicons-groups'],
                'ciclos'      => ['label' => __('Ciclos', 'flavor-chat-ia'), 'icon' => 'dashicons-calendar-alt'],
                // Integraciones
                'foro'        => ['label' => __('Foro', 'flavor-chat-ia'), 'icon' => 'dashicons-admin-comments', 'is_integration' => true, 'source_module' => 'foros'],
                'recetas'     => ['label' => __('Recetas', 'flavor-chat-ia'), 'icon' => 'dashicons-carrot', 'is_integration' => true, 'source_module' => 'recetas'],
                'trueques'    => ['label' => __('Trueques', 'flavor-chat-ia'), 'icon' => 'dashicons-randomize', 'is_integration' => true],
            ],

            // === EVENTOS ===
            // Widgets: Inscripciones (resumen), Próximos (destacados)
            // Tabs: Vistas alternativas del contenido
            'eventos' => [
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
                'recursos'     => ['label' => __('Recursos', 'flavor-chat-ia'), 'icon' => 'dashicons-admin-home'],
                'mis-reservas' => ['label' => __('Mis Reservas', 'flavor-chat-ia'), 'icon' => 'dashicons-calendar-alt'],
                'calendario'   => ['label' => __('Calendario', 'flavor-chat-ia'), 'icon' => 'dashicons-calendar'],
            ],

            // === ESPACIOS COMUNES ===
            'espacios-comunes' => [
                'espacios'     => ['label' => __('Espacios', 'flavor-chat-ia'), 'icon' => 'dashicons-admin-home'],
                'mis-reservas' => ['label' => __('Mis Reservas', 'flavor-chat-ia'), 'icon' => 'dashicons-calendar-alt'],
                'calendario'   => ['label' => __('Calendario', 'flavor-chat-ia'), 'icon' => 'dashicons-calendar'],
                // Integraciones
                'normas'       => ['label' => __('Normas', 'flavor-chat-ia'), 'icon' => 'dashicons-clipboard', 'is_integration' => true],
                'foro'         => ['label' => __('Foro', 'flavor-chat-ia'), 'icon' => 'dashicons-admin-comments', 'is_integration' => true, 'source_module' => 'foros'],
            ],

            // === HUERTOS URBANOS ===
            'huertos-urbanos' => [
                'listado'  => ['label' => __('Huertos', 'flavor-chat-ia'), 'icon' => 'dashicons-admin-site-alt3'],
                'mi-parcela' => ['label' => __('Mi Parcela', 'flavor-chat-ia'), 'icon' => 'dashicons-admin-home'],
                'mapa'     => ['label' => __('Mapa', 'flavor-chat-ia'), 'icon' => 'dashicons-location'],
                'calendario' => ['label' => __('Calendario', 'flavor-chat-ia'), 'icon' => 'dashicons-calendar'],
                // Integraciones
                'banco-semillas' => ['label' => __('Banco Semillas', 'flavor-chat-ia'), 'icon' => 'dashicons-archive', 'is_integration' => true],
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
                'clubes-lectura' => ['label' => __('Clubes', 'flavor-chat-ia'), 'icon' => 'dashicons-groups', 'is_integration' => true],
            ],

            // === MARKETPLACE ===
            'marketplace' => [
                'listado'      => ['label' => __('Anuncios', 'flavor-chat-ia'), 'icon' => 'dashicons-megaphone'],
                'mis-anuncios' => ['label' => __('Mis Anuncios', 'flavor-chat-ia'), 'icon' => 'dashicons-welcome-write-blog'],
                'categorias'   => ['label' => __('Categorías', 'flavor-chat-ia'), 'icon' => 'dashicons-category'],
                // Integraciones
                'favoritos'    => ['label' => __('Favoritos', 'flavor-chat-ia'), 'icon' => 'dashicons-heart', 'is_integration' => true],
                'mensajes'     => ['label' => __('Mensajes', 'flavor-chat-ia'), 'icon' => 'dashicons-email-alt', 'is_integration' => true, 'source_module' => 'chat-interno'],
            ],

            // === INCIDENCIAS ===
            // Widgets: Mis Incidencias (resumen), Mapa (vista rápida)
            // Tabs: Listado completo con filtros, mapa interactivo, estadísticas
            'incidencias' => [
                'listado'      => ['label' => __('Todas', 'flavor-chat-ia'), 'icon' => 'dashicons-list-view'],
                'mis-reportes' => ['label' => __('Mis Reportes', 'flavor-chat-ia'), 'icon' => 'dashicons-flag'],
                'mapa'         => ['label' => __('Mapa', 'flavor-chat-ia'), 'icon' => 'dashicons-location-alt'],
                // Integraciones
                'estadisticas' => ['label' => __('Estadísticas', 'flavor-chat-ia'), 'icon' => 'dashicons-chart-bar', 'is_integration' => true],
                'categorias'   => ['label' => __('Categorías', 'flavor-chat-ia'), 'icon' => 'dashicons-category', 'is_integration' => true],
            ],

            // === BANCO DE TIEMPO ===
            'banco-tiempo' => [
                'servicios'    => ['label' => __('Servicios', 'flavor-chat-ia'), 'icon' => 'dashicons-admin-users'],
                'mi-saldo'     => ['label' => __('Mi Saldo', 'flavor-chat-ia'), 'icon' => 'dashicons-clock'],
                'intercambios' => ['label' => __('Intercambios', 'flavor-chat-ia'), 'icon' => 'dashicons-randomize'],
                'ranking'      => ['label' => __('Ranking', 'flavor-chat-ia'), 'icon' => 'dashicons-awards'],
                // Integraciones
                'valoraciones' => ['label' => __('Valoraciones', 'flavor-chat-ia'), 'icon' => 'dashicons-star-filled', 'is_integration' => true],
                'mensajes'     => ['label' => __('Mensajes', 'flavor-chat-ia'), 'icon' => 'dashicons-email-alt', 'is_integration' => true, 'source_module' => 'chat-interno'],
            ],

            // === BICICLETAS COMPARTIDAS ===
            'bicicletas-compartidas' => [
                'disponibles'   => ['label' => __('Disponibles', 'flavor-chat-ia'), 'icon' => 'dashicons-admin-site'],
                'mis-prestamos' => ['label' => __('Mis Préstamos', 'flavor-chat-ia'), 'icon' => 'dashicons-dashboard'],
                'mapa'          => ['label' => __('Mapa', 'flavor-chat-ia'), 'icon' => 'dashicons-location'],
                // Integraciones
                'estaciones'    => ['label' => __('Estaciones', 'flavor-chat-ia'), 'icon' => 'dashicons-location-alt', 'is_integration' => true],
                'incidencias'   => ['label' => __('Incidencias', 'flavor-chat-ia'), 'icon' => 'dashicons-warning', 'is_integration' => true, 'source_module' => 'incidencias'],
                'estadisticas'  => ['label' => __('Estadísticas', 'flavor-chat-ia'), 'icon' => 'dashicons-chart-bar', 'is_integration' => true],
            ],

            // === PARKINGS ===
            'parkings' => [
                'disponibilidad' => ['label' => __('Disponibilidad', 'flavor-chat-ia'), 'icon' => 'dashicons-visibility'],
                'mis-reservas'   => ['label' => __('Mis Reservas', 'flavor-chat-ia'), 'icon' => 'dashicons-calendar-alt'],
                'mapa'           => ['label' => __('Mapa', 'flavor-chat-ia'), 'icon' => 'dashicons-location'],
                // Integraciones
                'tarifas'        => ['label' => __('Tarifas', 'flavor-chat-ia'), 'icon' => 'dashicons-money-alt', 'is_integration' => true],
                'ocupacion'      => ['label' => __('Ocupación', 'flavor-chat-ia'), 'icon' => 'dashicons-chart-line', 'is_integration' => true],
            ],

            // === CARPOOLING ===
            'carpooling' => [
                'buscar'     => ['label' => __('Buscar Viaje', 'flavor-chat-ia'), 'icon' => 'dashicons-search'],
                'mis-viajes' => ['label' => __('Mis Viajes', 'flavor-chat-ia'), 'icon' => 'dashicons-car'],
                'ofrecer'    => ['label' => __('Ofrecer', 'flavor-chat-ia'), 'icon' => 'dashicons-plus-alt'],
                // Integraciones
                'rutas'      => ['label' => __('Rutas Frecuentes', 'flavor-chat-ia'), 'icon' => 'dashicons-randomize', 'is_integration' => true],
                'valoraciones' => ['label' => __('Valoraciones', 'flavor-chat-ia'), 'icon' => 'dashicons-star-filled', 'is_integration' => true],
                'mensajes'   => ['label' => __('Mensajes', 'flavor-chat-ia'), 'icon' => 'dashicons-email-alt', 'is_integration' => true, 'source_module' => 'chat-interno'],
            ],

            // === RECICLAJE ===
            'reciclaje' => [
                'puntos-cercanos' => ['label' => __('Puntos', 'flavor-chat-ia'), 'icon' => 'dashicons-location'],
                'mis-puntos'      => ['label' => __('Mi Impacto', 'flavor-chat-ia'), 'icon' => 'dashicons-chart-bar'],
                'guia'            => ['label' => __('Guía', 'flavor-chat-ia'), 'icon' => 'dashicons-info'],
                // Integraciones
                'ranking'         => ['label' => __('Ranking', 'flavor-chat-ia'), 'icon' => 'dashicons-awards', 'is_integration' => true],
                'recompensas'     => ['label' => __('Recompensas', 'flavor-chat-ia'), 'icon' => 'dashicons-star-filled', 'is_integration' => true],
                'calendario'      => ['label' => __('Calendario', 'flavor-chat-ia'), 'icon' => 'dashicons-calendar', 'is_integration' => true],
            ],

            // === COMPOSTAJE ===
            'compostaje' => [
                'mapa'             => ['label' => __('Composteras', 'flavor-chat-ia'), 'icon' => 'dashicons-location-alt'],
                'mis-aportaciones' => ['label' => __('Mis Aportaciones', 'flavor-chat-ia'), 'icon' => 'dashicons-admin-site-alt3'],
                'estadisticas'     => ['label' => __('Estadísticas', 'flavor-chat-ia'), 'icon' => 'dashicons-chart-area'],
                // Integraciones
                'comunidad'        => ['label' => __('Comunidad', 'flavor-chat-ia'), 'icon' => 'dashicons-groups', 'is_integration' => true],
                'ranking'          => ['label' => __('Ranking', 'flavor-chat-ia'), 'icon' => 'dashicons-awards', 'is_integration' => true],
                'guias'            => ['label' => __('Guías', 'flavor-chat-ia'), 'icon' => 'dashicons-book', 'is_integration' => true],
            ],

            // === BARES / COMERCIOS ===
            'bares' => [
                'listado' => ['label' => __('Directorio', 'flavor-chat-ia'), 'icon' => 'dashicons-store'],
                'mapa'    => ['label' => __('Mapa', 'flavor-chat-ia'), 'icon' => 'dashicons-location'],
                // Integraciones
                'eventos'     => ['label' => __('Eventos', 'flavor-chat-ia'), 'icon' => 'dashicons-calendar-alt', 'is_integration' => true, 'source_module' => 'eventos'],
                'opiniones'   => ['label' => __('Opiniones', 'flavor-chat-ia'), 'icon' => 'dashicons-star-filled', 'is_integration' => true],
                'promociones' => ['label' => __('Promociones', 'flavor-chat-ia'), 'icon' => 'dashicons-megaphone', 'is_integration' => true],
            ],

            // === CURSOS ===
            'cursos' => [
                'catalogo'   => ['label' => __('Catálogo', 'flavor-chat-ia'), 'icon' => 'dashicons-welcome-learn-more'],
                'mis-cursos' => ['label' => __('Mis Cursos', 'flavor-chat-ia'), 'icon' => 'dashicons-awards'],
                'calendario' => ['label' => __('Calendario', 'flavor-chat-ia'), 'icon' => 'dashicons-calendar'],
                // Integraciones
                'materiales' => ['label' => __('Materiales', 'flavor-chat-ia'), 'icon' => 'dashicons-media-document', 'is_integration' => true],
                'multimedia' => ['label' => __('Videos', 'flavor-chat-ia'), 'icon' => 'dashicons-video-alt3', 'is_integration' => true, 'source_module' => 'multimedia'],
                'foro'       => ['label' => __('Foro', 'flavor-chat-ia'), 'icon' => 'dashicons-admin-comments', 'is_integration' => true, 'source_module' => 'foros'],
            ],

            // === TALLERES ===
            'talleres' => [
                'proximos'      => ['label' => __('Próximos', 'flavor-chat-ia'), 'icon' => 'dashicons-calendar'],
                'inscripciones' => ['label' => __('Inscripciones', 'flavor-chat-ia'), 'icon' => 'dashicons-tickets-alt'],
                'calendario'    => ['label' => __('Calendario', 'flavor-chat-ia'), 'icon' => 'dashicons-calendar-alt'],
                // Integraciones
                'materiales'    => ['label' => __('Materiales', 'flavor-chat-ia'), 'icon' => 'dashicons-media-document', 'is_integration' => true],
                'multimedia'    => ['label' => __('Multimedia', 'flavor-chat-ia'), 'icon' => 'dashicons-format-gallery', 'is_integration' => true, 'source_module' => 'multimedia'],
                'foro'          => ['label' => __('Foro', 'flavor-chat-ia'), 'icon' => 'dashicons-admin-comments', 'is_integration' => true, 'source_module' => 'foros'],
            ],

            // === COLECTIVOS ===
            'colectivos' => [
                'listado'        => ['label' => __('Colectivos', 'flavor-chat-ia'), 'icon' => 'dashicons-groups'],
                'mis-colectivos' => ['label' => __('Mis Colectivos', 'flavor-chat-ia'), 'icon' => 'dashicons-admin-users'],
                // Integraciones
                'proyectos'      => ['label' => __('Proyectos', 'flavor-chat-ia'), 'icon' => 'dashicons-portfolio', 'is_integration' => true],
                'asambleas'      => ['label' => __('Asambleas', 'flavor-chat-ia'), 'icon' => 'dashicons-groups', 'is_integration' => true],
                'multimedia'     => ['label' => __('Multimedia', 'flavor-chat-ia'), 'icon' => 'dashicons-format-gallery', 'is_integration' => true, 'source_module' => 'multimedia'],
                'eventos'        => ['label' => __('Eventos', 'flavor-chat-ia'), 'icon' => 'dashicons-calendar-alt', 'is_integration' => true, 'source_module' => 'eventos'],
                'documentos'     => ['label' => __('Documentos', 'flavor-chat-ia'), 'icon' => 'dashicons-media-document', 'is_integration' => true],
            ],

            // === COMUNIDADES ===
            'comunidades' => [
                'directorio' => ['label' => __('Directorio', 'flavor-chat-ia'), 'icon' => 'dashicons-networking'],
                'mapa'       => ['label' => __('Mapa', 'flavor-chat-ia'), 'icon' => 'dashicons-location'],
                'tablon'     => ['label' => __('Tablón', 'flavor-chat-ia'), 'icon' => 'dashicons-megaphone'],
                // Integraciones
                'foros'      => ['label' => __('Foros', 'flavor-chat-ia'), 'icon' => 'dashicons-admin-comments', 'is_integration' => true, 'source_module' => 'foros'],
                'multimedia' => ['label' => __('Multimedia', 'flavor-chat-ia'), 'icon' => 'dashicons-format-gallery', 'is_integration' => true, 'source_module' => 'multimedia'],
                'eventos'    => ['label' => __('Eventos', 'flavor-chat-ia'), 'icon' => 'dashicons-calendar-alt', 'is_integration' => true, 'source_module' => 'eventos'],
                'anuncios'   => ['label' => __('Anuncios', 'flavor-chat-ia'), 'icon' => 'dashicons-megaphone', 'is_integration' => true, 'source_module' => 'marketplace'],
                'recursos'   => ['label' => __('Recursos', 'flavor-chat-ia'), 'icon' => 'dashicons-media-document', 'is_integration' => true],
            ],

            // === SOCIOS ===
            'socios' => [
                'mi-membresia' => ['label' => __('Mi Membresía', 'flavor-chat-ia'), 'icon' => 'dashicons-id-alt'],
                'directorio'   => ['label' => __('Directorio', 'flavor-chat-ia'), 'icon' => 'dashicons-groups'],
                // Integraciones
                'beneficios'   => ['label' => __('Beneficios', 'flavor-chat-ia'), 'icon' => 'dashicons-awards', 'is_integration' => true],
                'eventos'      => ['label' => __('Eventos', 'flavor-chat-ia'), 'icon' => 'dashicons-calendar-alt', 'is_integration' => true, 'source_module' => 'eventos'],
                'historial'    => ['label' => __('Historial', 'flavor-chat-ia'), 'icon' => 'dashicons-backup', 'is_integration' => true],
            ],

            // === FOROS ===
            'foros' => [
                'listado'   => ['label' => __('Discusiones', 'flavor-chat-ia'), 'icon' => 'dashicons-format-chat'],
                'categorias'=> ['label' => __('Categorías', 'flavor-chat-ia'), 'icon' => 'dashicons-category'],
                'actividad' => ['label' => __('Actividad', 'flavor-chat-ia'), 'icon' => 'dashicons-bell'],
                // Integraciones
                'comunidades' => ['label' => __('Comunidades', 'flavor-chat-ia'), 'icon' => 'dashicons-groups', 'is_integration' => true, 'source_module' => 'comunidades'],
                'colectivos'  => ['label' => __('Colectivos', 'flavor-chat-ia'), 'icon' => 'dashicons-networking', 'is_integration' => true, 'source_module' => 'colectivos'],
            ],

            // === CHAT GRUPOS ===
            'chat-grupos' => [
                'mis-grupos' => ['label' => __('Mis Grupos', 'flavor-chat-ia'), 'icon' => 'dashicons-groups'],
                'explorar'   => ['label' => __('Explorar', 'flavor-chat-ia'), 'icon' => 'dashicons-search'],
            ],

            // === RED SOCIAL ===
            'red-social' => [
                'feed'      => ['label' => __('Feed', 'flavor-chat-ia'), 'icon' => 'dashicons-rss'],
                'mi-perfil' => ['label' => __('Mi Perfil', 'flavor-chat-ia'), 'icon' => 'dashicons-admin-users'],
                'explorar'  => ['label' => __('Explorar', 'flavor-chat-ia'), 'icon' => 'dashicons-search'],
                // Integraciones
                'amigos'    => ['label' => __('Amigos', 'flavor-chat-ia'), 'icon' => 'dashicons-groups', 'is_integration' => true],
                'mensajes'  => ['label' => __('Mensajes', 'flavor-chat-ia'), 'icon' => 'dashicons-email-alt', 'is_integration' => true, 'source_module' => 'chat-interno'],
                'historias' => ['label' => __('Historias', 'flavor-chat-ia'), 'icon' => 'dashicons-format-video', 'is_integration' => true],
            ],

            // === PARTICIPACIÓN ===
            'participacion' => [
                'propuestas' => ['label' => __('Propuestas', 'flavor-chat-ia'), 'icon' => 'dashicons-lightbulb'],
                'votaciones' => ['label' => __('Votaciones', 'flavor-chat-ia'), 'icon' => 'dashicons-thumbs-up'],
                'resultados' => ['label' => __('Resultados', 'flavor-chat-ia'), 'icon' => 'dashicons-chart-bar'],
                // Integraciones
                'debates'    => ['label' => __('Debates', 'flavor-chat-ia'), 'icon' => 'dashicons-admin-comments', 'is_integration' => true, 'source_module' => 'foros'],
                'reuniones'  => ['label' => __('Reuniones', 'flavor-chat-ia'), 'icon' => 'dashicons-groups', 'is_integration' => true, 'source_module' => 'eventos'],
            ],

            // === PRESUPUESTOS PARTICIPATIVOS ===
            'presupuestos-participativos' => [
                'proyectos'  => ['label' => __('Proyectos', 'flavor-chat-ia'), 'icon' => 'dashicons-portfolio'],
                'votaciones' => ['label' => __('Votaciones', 'flavor-chat-ia'), 'icon' => 'dashicons-thumbs-up'],
                'fases'      => ['label' => __('Fases', 'flavor-chat-ia'), 'icon' => 'dashicons-chart-line'],
                // Integraciones
                'seguimiento' => ['label' => __('Seguimiento', 'flavor-chat-ia'), 'icon' => 'dashicons-visibility', 'is_integration' => true],
                'transparencia' => ['label' => __('Transparencia', 'flavor-chat-ia'), 'icon' => 'dashicons-clipboard', 'is_integration' => true, 'source_module' => 'transparencia'],
            ],

            // === AVISOS MUNICIPALES ===
            'avisos-municipales' => [
                'activos'   => ['label' => __('Activos', 'flavor-chat-ia'), 'icon' => 'dashicons-megaphone'],
                'urgentes'  => ['label' => __('Urgentes', 'flavor-chat-ia'), 'icon' => 'dashicons-warning'],
                'historial' => ['label' => __('Historial', 'flavor-chat-ia'), 'icon' => 'dashicons-backup'],
                // Integraciones
                'suscripciones' => ['label' => __('Suscripciones', 'flavor-chat-ia'), 'icon' => 'dashicons-email', 'is_integration' => true],
                'categorias'    => ['label' => __('Categorías', 'flavor-chat-ia'), 'icon' => 'dashicons-category', 'is_integration' => true],
            ],

            // === AYUDA VECINAL ===
            'ayuda-vecinal' => [
                'solicitudes' => ['label' => __('Solicitudes', 'flavor-chat-ia'), 'icon' => 'dashicons-heart'],
                'ofrecer'     => ['label' => __('Ofrecer Ayuda', 'flavor-chat-ia'), 'icon' => 'dashicons-plus-alt'],
                'mapa'        => ['label' => __('Mapa', 'flavor-chat-ia'), 'icon' => 'dashicons-location'],
                // Integraciones
                'voluntarios' => ['label' => __('Voluntarios', 'flavor-chat-ia'), 'icon' => 'dashicons-groups', 'is_integration' => true],
                'estadisticas' => ['label' => __('Estadísticas', 'flavor-chat-ia'), 'icon' => 'dashicons-chart-bar', 'is_integration' => true],
            ],

            // === TRÁMITES ===
            'tramites' => [
                'catalogo'       => ['label' => __('Catálogo', 'flavor-chat-ia'), 'icon' => 'dashicons-media-document'],
                'mis-expedientes'=> ['label' => __('Mis Expedientes', 'flavor-chat-ia'), 'icon' => 'dashicons-portfolio'],
                // Integraciones
                'citas'          => ['label' => __('Citas', 'flavor-chat-ia'), 'icon' => 'dashicons-calendar', 'is_integration' => true],
                'documentos'     => ['label' => __('Documentos', 'flavor-chat-ia'), 'icon' => 'dashicons-media-default', 'is_integration' => true],
            ],

            // === TRANSPARENCIA ===
            'transparencia' => [
                'portal'      => ['label' => __('Portal', 'flavor-chat-ia'), 'icon' => 'dashicons-visibility'],
                'presupuesto' => ['label' => __('Presupuesto', 'flavor-chat-ia'), 'icon' => 'dashicons-chart-pie'],
                'actas'       => ['label' => __('Actas', 'flavor-chat-ia'), 'icon' => 'dashicons-media-document'],
                // Integraciones
                'contratos'   => ['label' => __('Contratos', 'flavor-chat-ia'), 'icon' => 'dashicons-portfolio', 'is_integration' => true],
                'indicadores' => ['label' => __('Indicadores', 'flavor-chat-ia'), 'icon' => 'dashicons-chart-bar', 'is_integration' => true],
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
                'suscripciones' => ['label' => __('Suscripciones', 'flavor-chat-ia'), 'icon' => 'dashicons-rss', 'is_integration' => true],
                'estadisticas' => ['label' => __('Estadísticas', 'flavor-chat-ia'), 'icon' => 'dashicons-chart-bar', 'is_integration' => true],
            ],

            // === RADIO ===
            'radio' => [
                'en-directo'   => ['label' => __('En Directo', 'flavor-chat-ia'), 'icon' => 'dashicons-controls-volumeon'],
                'programacion' => ['label' => __('Programación', 'flavor-chat-ia'), 'icon' => 'dashicons-calendar-alt'],
                'podcasts'     => ['label' => __('Podcasts', 'flavor-chat-ia'), 'icon' => 'dashicons-microphone'],
                // Integraciones
                'archivo'      => ['label' => __('Archivo', 'flavor-chat-ia'), 'icon' => 'dashicons-archive', 'is_integration' => true],
                'chat'         => ['label' => __('Chat', 'flavor-chat-ia'), 'icon' => 'dashicons-admin-comments', 'is_integration' => true],
                'colaboradores' => ['label' => __('Colaboradores', 'flavor-chat-ia'), 'icon' => 'dashicons-groups', 'is_integration' => true],
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
                'proyectos'   => ['label' => __('Proyectos', 'flavor-chat-ia'), 'icon' => 'dashicons-portfolio', 'is_integration' => true],
                'comunidad'   => ['label' => __('Comunidad', 'flavor-chat-ia'), 'icon' => 'dashicons-networking', 'is_integration' => true],
                'retos'       => ['label' => __('Retos', 'flavor-chat-ia'), 'icon' => 'dashicons-flag', 'is_integration' => true],
            ],

            // === SABERES ANCESTRALES ===
            'saberes-ancestrales' => [
                'catalogo'  => ['label' => __('Catálogo', 'flavor-chat-ia'), 'icon' => 'dashicons-book-alt'],
                'compartir' => ['label' => __('Compartir', 'flavor-chat-ia'), 'icon' => 'dashicons-share'],
                'talleres'  => ['label' => __('Talleres', 'flavor-chat-ia'), 'icon' => 'dashicons-welcome-learn-more'],
                // Integraciones
                'maestros'  => ['label' => __('Maestros', 'flavor-chat-ia'), 'icon' => 'dashicons-businessman', 'is_integration' => true],
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
                'mapa'      => ['label' => __('Mapa', 'flavor-chat-ia'), 'icon' => 'dashicons-location-alt', 'is_integration' => true],
                'foro'      => ['label' => __('Foro', 'flavor-chat-ia'), 'icon' => 'dashicons-admin-comments', 'is_integration' => true, 'source_module' => 'foros'],
            ],

            // === ECONOMÍA DE SUFICIENCIA ===
            'economia-suficiencia' => [
                'introduccion' => ['label' => __('Introducción', 'flavor-chat-ia'), 'icon' => 'dashicons-info'],
                'mi-camino'    => ['label' => __('Mi Camino', 'flavor-chat-ia'), 'icon' => 'dashicons-admin-users'],
                'biblioteca'   => ['label' => __('Biblioteca', 'flavor-chat-ia'), 'icon' => 'dashicons-book-alt'],
                // Integraciones
                'retos'        => ['label' => __('Retos', 'flavor-chat-ia'), 'icon' => 'dashicons-awards', 'is_integration' => true],
                'comunidad'    => ['label' => __('Comunidad', 'flavor-chat-ia'), 'icon' => 'dashicons-groups', 'is_integration' => true],
                'huella'       => ['label' => __('Mi Huella', 'flavor-chat-ia'), 'icon' => 'dashicons-chart-area', 'is_integration' => true, 'source_module' => 'huella-ecologica'],
            ],

            // === TRABAJO DIGNO ===
            'trabajo-digno' => [
                'ofertas'    => ['label' => __('Ofertas', 'flavor-chat-ia'), 'icon' => 'dashicons-businessman'],
                'mi-perfil'  => ['label' => __('Mi Perfil', 'flavor-chat-ia'), 'icon' => 'dashicons-admin-users'],
                'formacion'  => ['label' => __('Formación', 'flavor-chat-ia'), 'icon' => 'dashicons-welcome-learn-more'],
                // Integraciones
                'emprendimientos' => ['label' => __('Emprendimientos', 'flavor-chat-ia'), 'icon' => 'dashicons-store', 'is_integration' => true],
                'alertas'    => ['label' => __('Alertas', 'flavor-chat-ia'), 'icon' => 'dashicons-bell', 'is_integration' => true],
                'cursos'     => ['label' => __('Cursos', 'flavor-chat-ia'), 'icon' => 'dashicons-book-alt', 'is_integration' => true, 'source_module' => 'cursos'],
            ],

            // === CÍRCULOS DE CUIDADOS ===
            'circulos-cuidados' => [
                'circulos'     => ['label' => __('Círculos', 'flavor-chat-ia'), 'icon' => 'dashicons-groups'],
                'mis-cuidados' => ['label' => __('Mis Cuidados', 'flavor-chat-ia'), 'icon' => 'dashicons-heart'],
                'necesidades'  => ['label' => __('Necesidades', 'flavor-chat-ia'), 'icon' => 'dashicons-sos'],
                // Integraciones
                'calendario'   => ['label' => __('Calendario', 'flavor-chat-ia'), 'icon' => 'dashicons-calendar', 'is_integration' => true],
                'recursos'     => ['label' => __('Recursos', 'flavor-chat-ia'), 'icon' => 'dashicons-media-document', 'is_integration' => true],
                'foro'         => ['label' => __('Foro', 'flavor-chat-ia'), 'icon' => 'dashicons-admin-comments', 'is_integration' => true, 'source_module' => 'foros'],
            ],

            // === JUSTICIA RESTAURATIVA ===
            'justicia-restaurativa' => [
                'informacion'  => ['label' => __('Información', 'flavor-chat-ia'), 'icon' => 'dashicons-info'],
                'mis-procesos' => ['label' => __('Mis Procesos', 'flavor-chat-ia'), 'icon' => 'dashicons-portfolio'],
                'mediadores'   => ['label' => __('Mediadores', 'flavor-chat-ia'), 'icon' => 'dashicons-groups'],
                // Integraciones
                'recursos'     => ['label' => __('Recursos', 'flavor-chat-ia'), 'icon' => 'dashicons-book', 'is_integration' => true],
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
                'proyectos' => ['label' => __('Proyectos', 'flavor-chat-ia'), 'icon' => 'dashicons-portfolio', 'is_integration' => true],
                'foro'      => ['label' => __('Foro', 'flavor-chat-ia'), 'icon' => 'dashicons-admin-comments', 'is_integration' => true, 'source_module' => 'foros'],
            ],

            // === RECETAS ===
            'recetas' => [
                'listado'     => ['label' => __('Recetas', 'flavor-chat-ia'), 'icon' => 'dashicons-carrot'],
                'mis-recetas' => ['label' => __('Mis Recetas', 'flavor-chat-ia'), 'icon' => 'dashicons-welcome-write-blog'],
                'categorias'  => ['label' => __('Categorías', 'flavor-chat-ia'), 'icon' => 'dashicons-category'],
                // Integraciones
                'ingredientes' => ['label' => __('Ingredientes', 'flavor-chat-ia'), 'icon' => 'dashicons-list-view', 'is_integration' => true],
                'temporada'    => ['label' => __('De temporada', 'flavor-chat-ia'), 'icon' => 'dashicons-calendar', 'is_integration' => true],
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

        // Tipo 1: Shortcode
        if (is_string($contenido) && strpos($contenido, '[') === 0) {
            echo do_shortcode($contenido);
            return;
        }

        // Tipo 2: Template
        if (is_string($contenido) && strpos($contenido, 'template:') === 0) {
            $template_name = str_replace('template:', '', $contenido);
            $this->render_tab_template($template_name, $tab_id, $module_id, $module);
            return;
        }

        // Tipo 3: Callable (closure o función)
        if (is_callable($contenido)) {
            call_user_func($contenido, $tab_id, $module, $this);
            return;
        }

        // Tipo 4: Nombre de método del módulo
        if (is_string($contenido) && $module && method_exists($module, $contenido)) {
            $module->{$contenido}(get_current_user_id());
            return;
        }

        // Tipo 5: String directo (HTML)
        if (is_string($contenido)) {
            echo wp_kses_post($contenido);
            return;
        }

        // Fallback: mensaje vacío
        echo '<p class="fmd-empty">' . esc_html__('No hay contenido disponible', 'flavor-chat-ia') . '</p>';
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
        $paths = [
            get_stylesheet_directory() . "/flavor/{$module_slug}/tabs/{$template_name}",
            get_template_directory() . "/flavor/{$module_slug}/tabs/{$template_name}",
            FLAVOR_CHAT_IA_PATH . "templates/frontend/{$module_slug}/tabs/{$template_name}",
            FLAVOR_CHAT_IA_PATH . "includes/modules/{$module_slug}/views/tabs/{$template_name}",
            FLAVOR_CHAT_IA_PATH . "includes/modules/{$module_slug}/frontend/tabs/{$template_name}",
        ];

        // Variables disponibles en el template
        $tab_data = [
            'tab_id'    => $tab_id,
            'module_id' => $module_id,
            'module'    => $module,
            'user_id'   => get_current_user_id(),
        ];

        foreach ($paths as $path) {
            if (file_exists($path)) {
                extract($tab_data);
                include $path;
                return;
            }
        }

        // Template no encontrado
        echo '<div class="fmd-error">';
        echo '<p>' . sprintf(
            esc_html__('Template "%s" no encontrado para el módulo %s', 'flavor-chat-ia'),
            esc_html($template_name),
            esc_html($module_slug)
        ) . '</p>';
        echo '</div>';
    }

    /**
     * Sistema legacy de renderizado de tabs
     *
     * @param string $tab_id    ID del tab
     * @param array  $tab_info  Información del tab
     * @param object $module    Instancia del módulo
     */
    private function render_tab_content_legacy($tab_id, $tab_info, $module) {
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

            // === BANCO DE TIEMPO - Tabs específicos ===
            case 'mi-saldo':
            case 'servicios':
            case 'intercambios':
                $this->render_tab_banco_tiempo($tab_id);
                break;

            default:
                // Fallback genérico - usar module_listing con el tab como filtro
                $module_id = str_replace('_', '-', $this->current_module);
                ?>
                <div class="fmd-tab-generic">
                    <div class="fmd-panel-header">
                        <h3><?php echo esc_html($tab_info['label']); ?></h3>
                    </div>
                    <div class="fmd-panel-content">
                        <?php
                        // Usar flavor_module_listing como fallback seguro
                        $shortcode = '[flavor_module_listing module="' . esc_attr($module_id) . '" vista="' . esc_attr($tab_id) . '" limit="12"]';
                        echo do_shortcode($shortcode);
                        ?>
                    </div>
                </div>
                <?php
        }
    }

    /**
     * Renderiza tab de listado
     */
    private function render_tab_listado() {
        ?>
        <div class="fmd-panel-header">
            <h3><?php esc_html_e('Todos los elementos', 'flavor-chat-ia'); ?></h3>
            <div class="fmd-filters">
                <input type="search" placeholder="<?php esc_attr_e('Buscar...', 'flavor-chat-ia'); ?>" class="fmd-search">
                <select class="fmd-filter-select">
                    <option value=""><?php esc_html_e('Todos', 'flavor-chat-ia'); ?></option>
                    <option value="recientes"><?php esc_html_e('Recientes', 'flavor-chat-ia'); ?></option>
                    <option value="antiguos"><?php esc_html_e('Más antiguos', 'flavor-chat-ia'); ?></option>
                </select>
            </div>
        </div>
        <div class="fmd-panel-content">
            <?php
            $module_id = str_replace('_', '-', $this->current_module);

            if (class_exists('Flavor_Dynamic_CRUD')) {
                $crud = Flavor_Dynamic_CRUD::get_instance();
                echo $crud->render_list($this->current_module, [
                    'limite' => 12,
                    'solo_mios' => true,
                    'mostrar_crear' => true,
                    'mostrar_filtros' => true,
                ]);
            } else {
                // Usar flavor_module_listing como fallback seguro
                $shortcode = '[flavor_module_listing module="' . esc_attr($module_id) . '" limit="12"]';
                echo do_shortcode($shortcode);
            }
            ?>
        </div>
        <?php
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
        // Mapeo de tab_id a nombre de template
        $templates = [
            'mi-saldo'     => 'mi-saldo.php',
            'servicios'    => 'servicios.php',
            'intercambios' => 'intercambios.php',
        ];

        $template_file = $templates[$tab_id] ?? null;

        if (!$template_file) {
            echo '<p>' . esc_html__('Tab no encontrado.', 'flavor-chat-ia') . '</p>';
            return;
        }

        // Buscar template en el directorio de templates del módulo
        $template_path = FLAVOR_CHAT_IA_PATH . 'includes/modules/banco-tiempo/templates/' . $template_file;

        if (file_exists($template_path)) {
            include $template_path;
        } else {
            // Fallback: intentar con views (para compatibilidad)
            $view_path = FLAVOR_CHAT_IA_PATH . 'includes/modules/banco-tiempo/views/' . $template_file;

            if (file_exists($view_path)) {
                // Agregar wrapper para estilos frontend
                echo '<div class="fmd-banco-tiempo-view">';
                include $view_path;
                echo '</div>';
            } else {
                // Si no existe ni template ni view, usar shortcode genérico
                ?>
                <div class="fmd-panel-content">
                    <?php echo do_shortcode('[flavor_module_listing module="banco-tiempo" vista="' . esc_attr($tab_id) . '" limit="12"]'); ?>
                </div>
                <?php
            }
        }
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
     * Renderiza detalle de un elemento
     */
    private function render_module_item_detail() {
        ?>
        <div class="fmd-item-detail">
            <div class="fmd-item-header">
                <h2><?php printf(esc_html__('Detalle #%d', 'flavor-chat-ia'), $this->current_item_id); ?></h2>
                <div class="fmd-item-actions">
                    <button class="fmd-btn-secondary">
                        <span class="dashicons dashicons-edit"></span>
                        <?php esc_html_e('Editar', 'flavor-chat-ia'); ?>
                    </button>
                    <button class="fmd-btn-danger">
                        <span class="dashicons dashicons-trash"></span>
                        <?php esc_html_e('Eliminar', 'flavor-chat-ia'); ?>
                    </button>
                </div>
            </div>

            <div class="fmd-item-body">
                <p><?php esc_html_e('Contenido del elemento...', 'flavor-chat-ia'); ?></p>
            </div>
        </div>
        <?php
    }

    /**
     * Renderiza contenido de una acción específica
     */
    private function render_module_action_content() {
        $action = $this->current_action;
        $module = $this->current_module;
        $module_normalizado = str_replace('_', '-', $module);

        // Acciones que son vistas (listados, mapas, calendarios) - NO formularios
        $acciones_vista = ['mapa', 'listado', 'calendario', 'catalogo', 'grid', 'lista', 'buscar', 'productos', 'productores'];

        // Acciones que son páginas personalizadas del módulo
        $acciones_personalizadas = [
            'mis-reservas', 'mis-prestamos', 'mis-inscripciones', 'mis-anuncios',
            'mis-cursos', 'mis-viajes', 'mis-incidencias', 'mis-pedidos', 'historial',
            'mi-pedido', 'mi-cesta', 'ciclos', 'suscripciones', 'cestas'
        ];

        // Shortcodes específicos por módulo y acción
        $shortcodes_especificos = [
            'grupos-consumo' => [
                'productos'     => '[gc_productos]',
                'mi-pedido'     => '[gc_mi_pedido]',
                'mi-cesta'      => '[gc_mi_cesta]',
                'mis-pedidos'   => '[gc_historial]',
                'productores'   => '[gc_productores_cercanos]',
                'ciclos'        => '[gc_ciclo_actual]',
                'suscripciones' => '[gc_suscripciones]',
                'cestas'        => '[gc_suscripciones]',
                'unirme'        => '[gc_grupos_lista]',
                'panel'         => '[gc_panel]',
            ],
            'banco-tiempo' => [
                'mi-saldo'        => '[banco_tiempo_mi_saldo]',
                'mis-intercambios' => '[banco_tiempo_mis_intercambios]',
                'servicios'       => '[banco_tiempo_servicios]',
                'mi-reputacion'   => '[banco_tiempo_ranking]',
                'ofrecer'         => '[banco_tiempo_ofrecer]',
            ],
            'eventos' => [
                'listado'          => '[eventos_listado]',
                'mis-inscripciones' => '[eventos_mis_inscripciones]',
                'calendario'       => '[eventos_calendario]',
            ],
            'reservas' => [
                'mis-reservas' => '[reservas_mis_reservas]',
                'recursos'     => '[reservas_recursos]',
                'calendario'   => '[reservas_calendario]',
                'nueva'        => '[reservas_formulario]',
            ],
            'espacios-comunes' => [
                'listado'      => '[espacios_listado]',
                'mis-reservas' => '[espacios_mis_reservas]',
                'calendario'   => '[espacios_calendario]',
                'reservar'     => '[espacios_reservar]',
            ],
            'huertos-urbanos' => [
                'mi-parcela'   => '[mi_parcela]',
                'calendario'   => '[calendario_cultivos]',
                'listado'      => '[lista_huertos]',
                'mapa'         => '[mapa_huertos]',
                'intercambios' => '[intercambios_huertos]',
            ],
            'biblioteca' => [
                'mis-prestamos' => '[biblioteca_mis_prestamos]',
                'catalogo'      => '[biblioteca_catalogo]',
                'mis-libros'    => '[biblioteca_mis_libros]',
            ],
            'marketplace' => [
                'listado'      => '[marketplace_listado]',
                'mis-anuncios' => '[marketplace_mis_anuncios]',
                'publicar'     => '[marketplace_formulario]',
            ],
            'incidencias' => [
                'mis-reportes' => '[incidencias_mis_reportes]',
                'listado'      => '[incidencias_listado]',
                'mapa'         => '[incidencias_mapa]',
                'reportar'     => '[incidencias_reportar]',
            ],
            'talleres' => [
                'catalogo'         => '[talleres_catalogo]',
                'mis-inscripciones' => '[talleres_mis_inscripciones]',
                'calendario'       => '[talleres_calendario]',
                'proponer'         => '[talleres_proponer]',
            ],
            'cursos' => [
                'mis-cursos' => '[cursos_mis_inscripciones]',
                'catalogo'   => '[cursos_catalogo]',
                'aula'       => '[cursos_aula]',
            ],
            'carpooling' => [
                'mis-viajes'   => '[carpooling_mis_viajes]',
                'buscar'       => '[carpooling_buscar]',
                'publicar'     => '[carpooling_publicar]',
                'mis-reservas' => '[carpooling_mis_reservas]',
            ],
            'reciclaje' => [
                'mis-puntos'      => '[reciclaje_mis_puntos]',
                'puntos-cercanos' => '[reciclaje_puntos_cercanos]',
                'ranking'         => '[reciclaje_ranking]',
                'guia'            => '[reciclaje_guia]',
                'recompensas'     => '[reciclaje_recompensas]',
            ],
            'compostaje' => [
                'mis-aportaciones' => '[mis_aportaciones]',
                'estadisticas'     => '[estadisticas_compostaje]',
                'mapa'             => '[mapa_composteras]',
                'registrar'        => '[registrar_aportacion]',
                'ranking'          => '[ranking_compostaje]',
            ],
            'bicicletas-compartidas' => [
                'mis-prestamos' => '[bicicletas-compartidas_mis-prestamos]',
                'disponibles'   => '[flavor_module_listing module="bicicletas-compartidas"]',
                'acciones'      => '[flavor_bicicletas_compartidas_acciones]',
            ],
            'parkings' => [
                'disponibilidad' => '[flavor_disponibilidad_parking]',
                'mis-reservas'   => '[flavor_mis_reservas_parking]',
                'mapa'           => '[flavor_mapa_parkings]',
                'ocupacion'      => '[flavor_ocupacion_tiempo_real]',
                'solicitar'      => '[flavor_solicitar_plaza]',
            ],
            'bares' => [
                'listado' => '[flavor_module_listing module="bares" limit="12"]',
            ],
            'colectivos' => [
                'mis-colectivos' => '[flavor_module_listing module="colectivos" vista="mis"]',
                'listado'        => '[flavor_module_listing module="colectivos" limit="12"]',
            ],
            'comunidades' => [
                'directorio' => '[flavor_network_directory]',
                'mapa'       => '[flavor_network_map]',
                'tablon'     => '[flavor_network_board]',
            ],
            'socios' => [
                'mi-membresia' => '[flavor_module_listing module="socios" vista="mi"]',
                'directorio'   => '[flavor_module_listing module="socios"]',
            ],
            'foros' => [
                'listado' => '[flavor_module_listing module="foros" limit="10"]',
            ],
            'chat-grupos' => [
                'mis-grupos' => '[flavor_grupos_lista]',
                'explorar'   => '[flavor_grupos_explorar]',
                'crear'      => '[flavor_grupos_crear]',
            ],
            'chat-interno' => [
                'bandeja' => '[flavor_chat_inbox]',
                'nuevo'   => '[flavor_iniciar_chat]',
            ],
            'red-social' => [
                'perfil'    => '[rs_perfil]',
                'feed'      => '[rs_feed]',
                'explorar'  => '[rs_explorar]',
                'historias' => '[rs_historias]',
            ],
            'participacion' => [
                'propuestas'  => '[propuestas_activas]',
                'votaciones'  => '[votacion_activa]',
                'crear'       => '[crear_propuesta]',
                'resultados'  => '[resultados_participacion]',
            ],
            'presupuestos-participativos' => [
                'presupuesto' => '[presupuesto_participativo]',
                'fases'       => '[fases_participacion]',
            ],
            'avisos-municipales' => [
                'activos'     => '[avisos_activos]',
                'urgentes'    => '[avisos_urgentes]',
                'suscribirse' => '[suscribirse_avisos]',
                'historial'   => '[historial_avisos]',
            ],
            'ayuda-vecinal' => [
                'solicitudes' => '[flavor_module_listing module="ayuda-vecinal"]',
            ],
            'tramites' => [
                'mis-expedientes' => '[mis_expedientes]',
                'catalogo'        => '[catalogo_tramites]',
                'iniciar'         => '[iniciar_tramite]',
            ],
            'transparencia' => [
                'portal'      => '[transparencia_portal]',
                'presupuesto' => '[transparencia_presupuesto_actual]',
                'gastos'      => '[transparencia_ultimos_gastos]',
                'actas'       => '[transparencia_actas]',
                'indicadores' => '[transparencia_indicadores]',
            ],
            'fichaje-empleados' => [
                'panel' => '[flavor_fichaje_empleados_acciones]',
            ],
            'multimedia' => [
                'mi-galeria' => '[flavor_mi_galeria]',
                'galeria'    => '[flavor_galeria]',
                'albumes'    => '[flavor_albumes]',
                'subir'      => '[flavor_subir_multimedia]',
            ],
            'podcast' => [
                'episodios'   => '[podcast_lista_episodios]',
                'reproductor' => '[podcast_player]',
                'series'      => '[podcast_series]',
                'suscribirse' => '[podcast_suscribirse]',
            ],
            'radio' => [
                'directo'      => '[flavor_radio_player]',
                'programacion' => '[flavor_radio_programacion]',
                'chat'         => '[flavor_radio_chat]',
                'dedicatorias' => '[flavor_radio_dedicatorias]',
            ],
            'facturas' => [
                'mis-facturas' => '[flavor_mis_facturas]',
                'historial'    => '[flavor_historial_pagos]',
            ],
            'trading-ia' => [
                'dashboard'  => '[trading_ia_dashboard]',
                'portfolio'  => '[trading_ia_portfolio]',
                'mercado'    => '[trading_ia_mercado]',
                'historial'  => '[trading_ia_historial]',
            ],
            'advertising' => [
                'dashboard' => '[flavor_ads_dashboard]',
                'crear'     => '[flavor_ads_crear]',
                'ingresos'  => '[flavor_ads_ingresos]',
            ],
            'email-marketing' => [
                'suscripcion'  => '[flavor_suscripcion_newsletter]',
                'preferencias' => '[flavor_preferencias_email]',
                'archivo'      => '[flavor_archivo_newsletters]',
            ],
            'woocommerce' => [
                'mis-pedidos' => '[woocommerce_my_account]',
                'productos'   => '[products limit="12" columns="4"]',
            ],
            'dex-solana' => [
                'dashboard'  => '[flavor_module_listing module="dex-solana" vista="dashboard"]',
                'portfolio'  => '[flavor_module_listing module="dex-solana" vista="portfolio"]',
                'pools'      => '[flavor_module_listing module="dex-solana" vista="pools"]',
                'swap'       => '[flavor_module_listing module="dex-solana" vista="swap"]',
                'historial'  => '[flavor_module_listing module="dex-solana" vista="historial"]',
            ],
            'empresarial' => [
                'directorio'  => '[flavor_module_listing module="empresarial" limit="12"]',
                'servicios'   => '[flavor_module_listing module="empresarial" vista="servicios"]',
                'categorias'  => '[flavor_module_listing module="empresarial" vista="categorias"]',
                'buscar'      => '[flavor_module_listing module="empresarial" vista="buscar"]',
            ],
            'clientes' => [
                'listado'      => '[flavor_module_listing module="clientes" limit="12"]',
                'fichas'       => '[flavor_module_listing module="clientes" vista="fichas"]',
                'estadisticas' => '[flavor_module_listing module="clientes" vista="estadisticas"]',
                'nuevo'        => '[flavor_module_form module="clientes" action="crear"]',
            ],
            'huella-ecologica' => [
                'calculadora'   => '[flavor_huella_calculadora]',
                'mis-registros' => '[flavor_huella_mis_registros]',
                'logros'        => '[flavor_huella_logros]',
                'comunidad'     => '[flavor_huella_comunidad]',
                'proyectos'     => '[flavor_huella_proyectos]',
            ],
            'saberes-ancestrales' => [
                'catalogo'  => '[flavor_saberes_catalogo]',
                'compartir' => '[flavor_saberes_compartir]',
                'talleres'  => '[flavor_saberes_talleres]',
            ],
            'economia-don' => [
                'listado'   => '[flavor_don_listado]',
                'mis-dones' => '[flavor_don_mis_dones]',
                'muro'      => '[flavor_don_muro_gratitud]',
                'ofrecer'   => '[flavor_don_ofrecer]',
            ],
            'economia-suficiencia' => [
                'intro'       => '[flavor_suficiencia_intro]',
                'mi-camino'   => '[flavor_suficiencia_mi_camino]',
                'evaluacion'  => '[flavor_suficiencia_evaluacion]',
                'compromisos' => '[flavor_suficiencia_compromisos]',
                'biblioteca'  => '[flavor_suficiencia_biblioteca]',
            ],
            'trabajo-digno' => [
                'ofertas'        => '[flavor_trabajo_ofertas]',
                'mi-perfil'      => '[flavor_trabajo_mi_perfil]',
                'formacion'      => '[flavor_trabajo_formacion]',
                'emprendimientos' => '[flavor_trabajo_emprendimientos]',
                'publicar'       => '[flavor_trabajo_publicar]',
            ],
            'circulos-cuidados' => [
                'listado'      => '[flavor_circulos_listado]',
                'mis-cuidados' => '[flavor_circulos_mis_cuidados]',
                'necesidades'  => '[flavor_circulos_necesidades]',
            ],
            'justicia-restaurativa' => [
                'info'         => '[flavor_justicia_info]',
                'mis-procesos' => '[flavor_justicia_mis_procesos]',
                'mediadores'   => '[flavor_justicia_mediadores]',
                'solicitar'    => '[flavor_justicia_solicitar]',
            ],
            'sello-conciencia' => [
                'badge'    => '[flavor_sello_badge]',
                'premisas' => '[flavor_sello_premisas]',
            ],
        ];

        ?>
        <div class="fmd-action-header">
            <h2><?php echo esc_html(ucfirst(str_replace('-', ' ', $action))); ?></h2>
        </div>

        <div class="fmd-action-body">
            <?php
            // Primero verificar si hay un shortcode específico para este módulo y acción
            if (isset($shortcodes_especificos[$module_normalizado][$action])) {
                echo do_shortcode($shortcodes_especificos[$module_normalizado][$action]);
            } elseif (in_array($action, $acciones_vista)) {
                // Usar shortcode de listado/vista
                $shortcode = sprintf('[flavor_module_listing module="%s" vista="%s"]', esc_attr($module_normalizado), esc_attr($action));
                echo do_shortcode($shortcode);
            } elseif (in_array($action, $acciones_personalizadas)) {
                // Usar shortcode específico del módulo (ej: bicicletas-compartidas_mis-prestamos)
                $shortcode_especifico = sprintf('[%s_%s]', $module_normalizado, $action);
                $output = do_shortcode($shortcode_especifico);

                // Si el shortcode no existe, intentar con el genérico
                if (strpos($output, $shortcode_especifico) !== false) {
                    $shortcode = sprintf('[flavor_module_listing module="%s" vista="%s"]', esc_attr($module_normalizado), esc_attr($action));
                    echo do_shortcode($shortcode);
                } else {
                    echo $output;
                }
            } else {
                // Asumir que es un formulario
                $shortcode = sprintf('[flavor_module_form module="%s" action="%s"]', esc_attr($module_normalizado), esc_attr($action));
                echo do_shortcode($shortcode);
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
                <p><?php esc_html_e('No hay módulos activos.', 'flavor-chat-ia'); ?></p>
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
            <h2><?php esc_html_e('Acceso restringido', 'flavor-chat-ia'); ?></h2>
            <p><?php esc_html_e('Debes iniciar sesión para acceder a esta sección.', 'flavor-chat-ia'); ?></p>
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
            <h2><?php esc_html_e('Módulo no encontrado', 'flavor-chat-ia'); ?></h2>
            <p><?php printf(
                esc_html__('El módulo "%s" no existe o no está activo.', 'flavor-chat-ia'),
                esc_html($this->current_module)
            ); ?></p>
            <a href="<?php echo esc_url(home_url('/' . $this->base_path . '/')); ?>" class="flavor-btn">
                <?php esc_html_e('Volver al dashboard', 'flavor-chat-ia'); ?>
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
        $this->render_section_coming_soon(__('Configuración', 'flavor-chat-ia'));
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
     * Obtiene las acciones de un módulo
     */
    private function get_module_actions($module_id) {
        $acciones_por_modulo = [
            // ═══════════════════════════════════════════════════════════════
            // Eventos y Actividades (con integraciones)
            // ═══════════════════════════════════════════════════════════════
            'eventos' => [
                'crear' => ['label' => __('Crear', 'flavor-chat-ia'), 'icon' => 'dashicons-plus-alt'],
                'mis-eventos' => ['label' => __('Mis eventos', 'flavor-chat-ia'), 'icon' => 'dashicons-calendar-alt'],
                'calendario' => ['label' => __('Calendario', 'flavor-chat-ia'), 'icon' => 'dashicons-calendar'],
                'asistencias' => ['label' => __('Asistencias', 'flavor-chat-ia'), 'icon' => 'dashicons-groups'],
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
                'nueva' => ['label' => __('Nueva', 'flavor-chat-ia'), 'icon' => 'dashicons-plus-alt'],
                'mis-reservas' => ['label' => __('Mis reservas', 'flavor-chat-ia'), 'icon' => 'dashicons-calendar'],
                'calendario' => ['label' => __('Calendario', 'flavor-chat-ia'), 'icon' => 'dashicons-calendar-alt'],
                'recursos' => ['label' => __('Recursos', 'flavor-chat-ia'), 'icon' => 'dashicons-building'],
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
                'reservar' => ['label' => __('Reservar', 'flavor-chat-ia'), 'icon' => 'dashicons-plus-alt'],
                'mis-reservas' => ['label' => __('Mis reservas', 'flavor-chat-ia'), 'icon' => 'dashicons-calendar'],
                'mapa' => ['label' => __('Mapa', 'flavor-chat-ia'), 'icon' => 'dashicons-location-alt'],
                'disponibilidad' => ['label' => __('Disponibilidad', 'flavor-chat-ia'), 'icon' => 'dashicons-visibility'],
                'tarifas' => ['label' => __('Tarifas', 'flavor-chat-ia'), 'icon' => 'dashicons-money-alt'],
            ],

            // ═══════════════════════════════════════════════════════════════
            // Comunidad y Social (con integraciones de otros módulos)
            // ═══════════════════════════════════════════════════════════════
            'comunidades' => [
                'crear' => ['label' => __('Crear', 'flavor-chat-ia'), 'icon' => 'dashicons-plus-alt'],
                'mis-comunidades' => ['label' => __('Mis comunidades', 'flavor-chat-ia'), 'icon' => 'dashicons-groups'],
                'explorar' => ['label' => __('Explorar', 'flavor-chat-ia'), 'icon' => 'dashicons-search'],
                // Integraciones con otros módulos
                'foros' => ['label' => __('Foros', 'flavor-chat-ia'), 'icon' => 'dashicons-admin-comments'],
                'multimedia' => ['label' => __('Multimedia', 'flavor-chat-ia'), 'icon' => 'dashicons-format-gallery'],
                'eventos' => ['label' => __('Eventos', 'flavor-chat-ia'), 'icon' => 'dashicons-calendar-alt'],
                'anuncios' => ['label' => __('Anuncios', 'flavor-chat-ia'), 'icon' => 'dashicons-megaphone'],
                'recursos' => ['label' => __('Recursos', 'flavor-chat-ia'), 'icon' => 'dashicons-media-document'],
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
                'amigos' => ['label' => __('Amigos', 'flavor-chat-ia'), 'icon' => 'dashicons-groups'],
                'mensajes' => ['label' => __('Mensajes', 'flavor-chat-ia'), 'icon' => 'dashicons-email-alt'],
                'notificaciones' => ['label' => __('Notificaciones', 'flavor-chat-ia'), 'icon' => 'dashicons-bell'],
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
                'proponer' => ['label' => __('Proponer', 'flavor-chat-ia'), 'icon' => 'dashicons-lightbulb'],
                'mis-propuestas' => ['label' => __('Mis propuestas', 'flavor-chat-ia'), 'icon' => 'dashicons-list-view'],
                'votaciones' => ['label' => __('Votaciones', 'flavor-chat-ia'), 'icon' => 'dashicons-yes'],
                'debates' => ['label' => __('Debates', 'flavor-chat-ia'), 'icon' => 'dashicons-admin-comments'],
                'resultados' => ['label' => __('Resultados', 'flavor-chat-ia'), 'icon' => 'dashicons-chart-pie'],
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
                'productos' => ['label' => __('Catálogo', 'flavor-chat-ia'), 'icon' => 'dashicons-products'],
                'mi-cesta' => ['label' => __('Mi cesta', 'flavor-chat-ia'), 'icon' => 'dashicons-cart'],
                'mi-pedido' => ['label' => __('Mi pedido', 'flavor-chat-ia'), 'icon' => 'dashicons-clipboard'],
                'mis-pedidos' => ['label' => __('Mis pedidos', 'flavor-chat-ia'), 'icon' => 'dashicons-list-view'],
                'suscripciones' => ['label' => __('Cestas', 'flavor-chat-ia'), 'icon' => 'dashicons-archive'],
                'unirme' => ['label' => __('Unirme', 'flavor-chat-ia'), 'icon' => 'dashicons-plus-alt'],
                // Integraciones
                'productores' => ['label' => __('Productores', 'flavor-chat-ia'), 'icon' => 'dashicons-businessperson'],
                'foro' => ['label' => __('Foro', 'flavor-chat-ia'), 'icon' => 'dashicons-admin-comments'],
                'recetas' => ['label' => __('Recetas', 'flavor-chat-ia'), 'icon' => 'dashicons-carrot'],
            ],
            'banco-tiempo' => [
                'ofrecer' => ['label' => __('Ofrecer', 'flavor-chat-ia'), 'icon' => 'dashicons-plus-alt'],
                'buscar' => ['label' => __('Buscar', 'flavor-chat-ia'), 'icon' => 'dashicons-search'],
                'mis-servicios' => ['label' => __('Mis servicios', 'flavor-chat-ia'), 'icon' => 'dashicons-clock'],
                'mi-balance' => ['label' => __('Mi balance', 'flavor-chat-ia'), 'icon' => 'dashicons-chart-bar'],
                'intercambios' => ['label' => __('Intercambios', 'flavor-chat-ia'), 'icon' => 'dashicons-randomize'],
                'ranking' => ['label' => __('Ranking', 'flavor-chat-ia'), 'icon' => 'dashicons-awards'],
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

            // ═══════════════════════════════════════════════════════════════
            // Biblioteca y Multimedia (con integraciones)
            // ═══════════════════════════════════════════════════════════════
            'biblioteca' => [
                'solicitar' => ['label' => __('Solicitar', 'flavor-chat-ia'), 'icon' => 'dashicons-book'],
                'mis-prestamos' => ['label' => __('Mis préstamos', 'flavor-chat-ia'), 'icon' => 'dashicons-list-view'],
                'catalogo' => ['label' => __('Catálogo', 'flavor-chat-ia'), 'icon' => 'dashicons-search'],
                'reservas' => ['label' => __('Reservas', 'flavor-chat-ia'), 'icon' => 'dashicons-calendar'],
                'novedades' => ['label' => __('Novedades', 'flavor-chat-ia'), 'icon' => 'dashicons-star-filled'],
                'resenas' => ['label' => __('Reseñas', 'flavor-chat-ia'), 'icon' => 'dashicons-admin-comments'],
            ],
            'multimedia' => [
                'subir' => ['label' => __('Subir', 'flavor-chat-ia'), 'icon' => 'dashicons-upload'],
                'mis-archivos' => ['label' => __('Mis archivos', 'flavor-chat-ia'), 'icon' => 'dashicons-media-archive'],
                'galeria' => ['label' => __('Galería', 'flavor-chat-ia'), 'icon' => 'dashicons-format-gallery'],
                'videos' => ['label' => __('Videos', 'flavor-chat-ia'), 'icon' => 'dashicons-video-alt3'],
                'albumes' => ['label' => __('Álbumes', 'flavor-chat-ia'), 'icon' => 'dashicons-images-alt2'],
            ],
            'podcast' => [
                'subir' => ['label' => __('Subir', 'flavor-chat-ia'), 'icon' => 'dashicons-upload'],
                'mis-podcasts' => ['label' => __('Mis podcasts', 'flavor-chat-ia'), 'icon' => 'dashicons-microphone'],
                'episodios' => ['label' => __('Episodios', 'flavor-chat-ia'), 'icon' => 'dashicons-playlist-audio'],
                'suscripciones' => ['label' => __('Suscripciones', 'flavor-chat-ia'), 'icon' => 'dashicons-rss'],
                'estadisticas' => ['label' => __('Estadísticas', 'flavor-chat-ia'), 'icon' => 'dashicons-chart-bar'],
            ],
            'radio' => [
                'programacion' => ['label' => __('Programación', 'flavor-chat-ia'), 'icon' => 'dashicons-calendar-alt'],
                'en-vivo' => ['label' => __('En vivo', 'flavor-chat-ia'), 'icon' => 'dashicons-controls-volumeon'],
                'programas' => ['label' => __('Programas', 'flavor-chat-ia'), 'icon' => 'dashicons-playlist-audio'],
                'archivo' => ['label' => __('Archivo', 'flavor-chat-ia'), 'icon' => 'dashicons-media-archive'],
                'colaborar' => ['label' => __('Colaborar', 'flavor-chat-ia'), 'icon' => 'dashicons-groups'],
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
                'solicitar' => ['label' => __('Solicitar', 'flavor-chat-ia'), 'icon' => 'dashicons-sos'],
                'ofrecer' => ['label' => __('Ofrecer', 'flavor-chat-ia'), 'icon' => 'dashicons-heart'],
                'mis-solicitudes' => ['label' => __('Mis solicitudes', 'flavor-chat-ia'), 'icon' => 'dashicons-list-view'],
                'voluntarios' => ['label' => __('Voluntarios', 'flavor-chat-ia'), 'icon' => 'dashicons-groups'],
                'mapa' => ['label' => __('Mapa', 'flavor-chat-ia'), 'icon' => 'dashicons-location-alt'],
                'estadisticas' => ['label' => __('Estadísticas', 'flavor-chat-ia'), 'icon' => 'dashicons-chart-bar'],
            ],
            'circulos-cuidados' => [
                'crear' => ['label' => __('Crear', 'flavor-chat-ia'), 'icon' => 'dashicons-plus-alt'],
                'mis-circulos' => ['label' => __('Mis círculos', 'flavor-chat-ia'), 'icon' => 'dashicons-heart'],
                'necesidades' => ['label' => __('Necesidades', 'flavor-chat-ia'), 'icon' => 'dashicons-sos'],
                'calendario' => ['label' => __('Calendario', 'flavor-chat-ia'), 'icon' => 'dashicons-calendar'],
                'recursos' => ['label' => __('Recursos', 'flavor-chat-ia'), 'icon' => 'dashicons-media-document'],
                'foro' => ['label' => __('Foro', 'flavor-chat-ia'), 'icon' => 'dashicons-admin-comments'],
            ],
            'justicia-restaurativa' => [
                'iniciar' => ['label' => __('Iniciar', 'flavor-chat-ia'), 'icon' => 'dashicons-plus-alt'],
                'mis-procesos' => ['label' => __('Mis procesos', 'flavor-chat-ia'), 'icon' => 'dashicons-shield'],
                'mediadores' => ['label' => __('Mediadores', 'flavor-chat-ia'), 'icon' => 'dashicons-businessman'],
                'recursos' => ['label' => __('Recursos', 'flavor-chat-ia'), 'icon' => 'dashicons-book'],
                'formacion' => ['label' => __('Formación', 'flavor-chat-ia'), 'icon' => 'dashicons-welcome-learn-more'],
            ],

            // ═══════════════════════════════════════════════════════════════
            // Ecología (con integraciones)
            // ═══════════════════════════════════════════════════════════════
            'compostaje' => [
                'registrar' => ['label' => __('Registrar', 'flavor-chat-ia'), 'icon' => 'dashicons-plus-alt'],
                'mis-aportes' => ['label' => __('Mis aportes', 'flavor-chat-ia'), 'icon' => 'dashicons-chart-bar'],
                'composteras' => ['label' => __('Composteras', 'flavor-chat-ia'), 'icon' => 'dashicons-location'],
                'estadisticas' => ['label' => __('Estadísticas', 'flavor-chat-ia'), 'icon' => 'dashicons-chart-area'],
                'comunidad' => ['label' => __('Comunidad', 'flavor-chat-ia'), 'icon' => 'dashicons-groups'],
                'guias' => ['label' => __('Guías', 'flavor-chat-ia'), 'icon' => 'dashicons-book'],
            ],
            'reciclaje' => [
                'registrar' => ['label' => __('Registrar', 'flavor-chat-ia'), 'icon' => 'dashicons-plus-alt'],
                'mis-registros' => ['label' => __('Mis registros', 'flavor-chat-ia'), 'icon' => 'dashicons-chart-bar'],
                'puntos' => ['label' => __('Puntos limpios', 'flavor-chat-ia'), 'icon' => 'dashicons-location'],
                'ranking' => ['label' => __('Ranking', 'flavor-chat-ia'), 'icon' => 'dashicons-awards'],
                'guias' => ['label' => __('Guías', 'flavor-chat-ia'), 'icon' => 'dashicons-book'],
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
                'aportar' => ['label' => __('Aportar', 'flavor-chat-ia'), 'icon' => 'dashicons-plus-alt'],
                'talleres' => ['label' => __('Talleres', 'flavor-chat-ia'), 'icon' => 'dashicons-welcome-learn-more'],
                'catalogo' => ['label' => __('Catálogo', 'flavor-chat-ia'), 'icon' => 'dashicons-book-alt'],
                'maestros' => ['label' => __('Maestros', 'flavor-chat-ia'), 'icon' => 'dashicons-businessperson'],
                'multimedia' => ['label' => __('Multimedia', 'flavor-chat-ia'), 'icon' => 'dashicons-format-gallery'],
                'foro' => ['label' => __('Foro', 'flavor-chat-ia'), 'icon' => 'dashicons-admin-comments'],
                'recetas' => ['label' => __('Recetas', 'flavor-chat-ia'), 'icon' => 'dashicons-carrot'],
            ],

            // ═══════════════════════════════════════════════════════════════
            // Trámites y Administración (con integraciones)
            // ═══════════════════════════════════════════════════════════════
            'tramites' => [
                'nuevo' => ['label' => __('Nuevo', 'flavor-chat-ia'), 'icon' => 'dashicons-plus-alt'],
                'mis-tramites' => ['label' => __('Mis trámites', 'flavor-chat-ia'), 'icon' => 'dashicons-clipboard'],
                'catalogo' => ['label' => __('Catálogo', 'flavor-chat-ia'), 'icon' => 'dashicons-list-view'],
                'citas' => ['label' => __('Citas', 'flavor-chat-ia'), 'icon' => 'dashicons-calendar'],
                'documentos' => ['label' => __('Documentos', 'flavor-chat-ia'), 'icon' => 'dashicons-media-document'],
            ],
            'avisos-municipales' => [
                'recientes' => ['label' => __('Recientes', 'flavor-chat-ia'), 'icon' => 'dashicons-bell'],
                'suscripciones' => ['label' => __('Suscripciones', 'flavor-chat-ia'), 'icon' => 'dashicons-email'],
                'categorias' => ['label' => __('Categorías', 'flavor-chat-ia'), 'icon' => 'dashicons-category'],
                'archivo' => ['label' => __('Archivo', 'flavor-chat-ia'), 'icon' => 'dashicons-archive'],
            ],
            'transparencia' => [
                'documentos' => ['label' => __('Documentos', 'flavor-chat-ia'), 'icon' => 'dashicons-media-document'],
                'presupuestos' => ['label' => __('Presupuestos', 'flavor-chat-ia'), 'icon' => 'dashicons-chart-pie'],
                'contratos' => ['label' => __('Contratos', 'flavor-chat-ia'), 'icon' => 'dashicons-portfolio'],
                'indicadores' => ['label' => __('Indicadores', 'flavor-chat-ia'), 'icon' => 'dashicons-chart-bar'],
                'solicitar' => ['label' => __('Solicitar info', 'flavor-chat-ia'), 'icon' => 'dashicons-plus-alt'],
            ],
            'seguimiento-denuncias' => [
                'nueva' => ['label' => __('Nueva', 'flavor-chat-ia'), 'icon' => 'dashicons-flag'],
                'mis-denuncias' => ['label' => __('Mis denuncias', 'flavor-chat-ia'), 'icon' => 'dashicons-list-view'],
                'anonimas' => ['label' => __('Anónimas', 'flavor-chat-ia'), 'icon' => 'dashicons-hidden'],
                'seguimiento' => ['label' => __('Seguimiento', 'flavor-chat-ia'), 'icon' => 'dashicons-visibility'],
            ],
            'documentacion-legal' => [
                'buscar' => ['label' => __('Buscar', 'flavor-chat-ia'), 'icon' => 'dashicons-search'],
                'mis-guardados' => ['label' => __('Guardados', 'flavor-chat-ia'), 'icon' => 'dashicons-heart'],
                'categorias' => ['label' => __('Categorías', 'flavor-chat-ia'), 'icon' => 'dashicons-category'],
                'modelos' => ['label' => __('Modelos', 'flavor-chat-ia'), 'icon' => 'dashicons-media-document'],
                'leyes' => ['label' => __('Leyes', 'flavor-chat-ia'), 'icon' => 'dashicons-clipboard'],
            ],

            // ═══════════════════════════════════════════════════════════════
            // Campañas y Mapeo (con integraciones)
            // ═══════════════════════════════════════════════════════════════
            'campanias' => [
                'crear' => ['label' => __('Crear', 'flavor-chat-ia'), 'icon' => 'dashicons-megaphone'],
                'mis-campanias' => ['label' => __('Mis campañas', 'flavor-chat-ia'), 'icon' => 'dashicons-list-view'],
                'participando' => ['label' => __('Participando', 'flavor-chat-ia'), 'icon' => 'dashicons-groups'],
                'acciones' => ['label' => __('Acciones', 'flavor-chat-ia'), 'icon' => 'dashicons-flag'],
                'recursos' => ['label' => __('Recursos', 'flavor-chat-ia'), 'icon' => 'dashicons-media-document'],
                'multimedia' => ['label' => __('Multimedia', 'flavor-chat-ia'), 'icon' => 'dashicons-format-gallery'],
            ],
            'mapa-actores' => [
                'registrar' => ['label' => __('Registrar', 'flavor-chat-ia'), 'icon' => 'dashicons-plus-alt'],
                'mapa' => ['label' => __('Mapa', 'flavor-chat-ia'), 'icon' => 'dashicons-location-alt'],
                'red' => ['label' => __('Red', 'flavor-chat-ia'), 'icon' => 'dashicons-networking'],
                'directorio' => ['label' => __('Directorio', 'flavor-chat-ia'), 'icon' => 'dashicons-list-view'],
                'categorias' => ['label' => __('Categorías', 'flavor-chat-ia'), 'icon' => 'dashicons-category'],
                'colaboraciones' => ['label' => __('Colaboraciones', 'flavor-chat-ia'), 'icon' => 'dashicons-groups'],
            ],

            // ═══════════════════════════════════════════════════════════════
            // Empleo y Trabajo (con integraciones)
            // ═══════════════════════════════════════════════════════════════
            'trabajo-digno' => [
                'ofertas' => ['label' => __('Ofertas', 'flavor-chat-ia'), 'icon' => 'dashicons-businessman'],
                'publicar' => ['label' => __('Publicar', 'flavor-chat-ia'), 'icon' => 'dashicons-plus-alt'],
                'mis-aplicaciones' => ['label' => __('Aplicaciones', 'flavor-chat-ia'), 'icon' => 'dashicons-portfolio'],
                'mi-cv' => ['label' => __('Mi CV', 'flavor-chat-ia'), 'icon' => 'dashicons-media-document'],
                'formacion' => ['label' => __('Formación', 'flavor-chat-ia'), 'icon' => 'dashicons-welcome-learn-more'],
                'alertas' => ['label' => __('Alertas', 'flavor-chat-ia'), 'icon' => 'dashicons-bell'],
            ],
            'fichaje-empleados' => [
                'fichar' => ['label' => __('Fichar', 'flavor-chat-ia'), 'icon' => 'dashicons-clock'],
                'mis-fichajes' => ['label' => __('Mis fichajes', 'flavor-chat-ia'), 'icon' => 'dashicons-list-view'],
                'calendario' => ['label' => __('Calendario', 'flavor-chat-ia'), 'icon' => 'dashicons-calendar'],
                'informes' => ['label' => __('Informes', 'flavor-chat-ia'), 'icon' => 'dashicons-chart-bar'],
                'vacaciones' => ['label' => __('Vacaciones', 'flavor-chat-ia'), 'icon' => 'dashicons-palmtree'],
            ],

            // ═══════════════════════════════════════════════════════════════
            // Socios y Membresías (con integraciones)
            // ═══════════════════════════════════════════════════════════════
            'socios' => [
                'mi-membresia' => ['label' => __('Mi membresía', 'flavor-chat-ia'), 'icon' => 'dashicons-id'],
                'pagar-cuota' => ['label' => __('Pagar cuota', 'flavor-chat-ia'), 'icon' => 'dashicons-money-alt'],
                'beneficios' => ['label' => __('Beneficios', 'flavor-chat-ia'), 'icon' => 'dashicons-awards'],
                'carnet' => ['label' => __('Carnet', 'flavor-chat-ia'), 'icon' => 'dashicons-id-alt'],
                'historial' => ['label' => __('Historial', 'flavor-chat-ia'), 'icon' => 'dashicons-backup'],
                'directorio' => ['label' => __('Directorio', 'flavor-chat-ia'), 'icon' => 'dashicons-groups'],
            ],

            // ═══════════════════════════════════════════════════════════════
            // Servicios Locales (con integraciones)
            // ═══════════════════════════════════════════════════════════════
            'bares' => [
                'carta' => ['label' => __('Carta', 'flavor-chat-ia'), 'icon' => 'dashicons-food'],
                'reservar' => ['label' => __('Reservar', 'flavor-chat-ia'), 'icon' => 'dashicons-calendar'],
                'mis-reservas' => ['label' => __('Mis reservas', 'flavor-chat-ia'), 'icon' => 'dashicons-list-view'],
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
     */
    private function get_module_color($module_id) {
        $colores = [
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

        $id_normalizado = str_replace('_', '-', $module_id);
        return $colores[$id_normalizado] ?? '#6b7280';
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
            --fap-primary: #4f46e5;
            --fap-primary-dark: #4338ca;
            --fap-bg: #f8fafc;
            --fap-surface: #ffffff;
            --fap-text: #111827;
            --fap-text-muted: #6b7280;
            --fap-border: #e5e7eb;
            --fap-radius: 12px;
            --fap-shadow: 0 1px 3px rgba(0,0,0,0.1);
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
            margin-bottom: 24px;
            padding: 24px;
            background: var(--fap-surface);
            border-radius: var(--fap-radius);
            box-shadow: var(--fap-shadow);
        }

        .fmd-breadcrumb {
            display: flex;
            align-items: center;
            gap: 8px;
            font-size: 0.875rem;
            color: var(--fap-text-muted);
            margin-bottom: 12px;
        }

        .fmd-breadcrumb a {
            color: var(--module-color);
            text-decoration: none;
        }

        .fmd-title-row {
            display: flex;
            align-items: center;
            gap: 16px;
        }

        .fmd-icon {
            display: flex;
            align-items: center;
            justify-content: center;
            width: 56px;
            height: 56px;
            background: var(--module-color);
            border-radius: 14px;
        }

        .fmd-icon .dashicons {
            font-size: 28px;
            width: 28px;
            height: 28px;
            color: white;
        }

        .fmd-header h1 {
            font-size: 1.75rem;
            font-weight: 700;
            margin: 0;
        }

        .fmd-subtitle {
            font-size: 0.9375rem;
            color: var(--fap-text-muted);
            margin: 4px 0 0;
        }

        .fmd-primary-btn {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 12px 24px;
            background: var(--module-color);
            color: white;
            border-radius: 10px;
            text-decoration: none;
            font-weight: 600;
            transition: all 0.2s;
        }

        .fmd-primary-btn:hover {
            filter: brightness(1.1);
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(0,0,0,0.15);
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
            background: var(--module-color);
            color: white;
            border-color: var(--module-color);
        }
        .fmd-widget-btn--primary:hover {
            filter: brightness(1.1);
            color: white;
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
        }

        .fmd-tabs-nav {
            display: flex;
            border-bottom: 1px solid var(--fap-border);
            padding: 0 16px;
            overflow-x: auto;
        }

        .fmd-tab {
            display: flex;
            align-items: center;
            gap: 8px;
            padding: 16px 20px;
            background: none;
            border: none;
            border-bottom: 2px solid transparent;
            font-size: 0.9375rem;
            font-weight: 500;
            color: var(--fap-text-muted);
            cursor: pointer;
            white-space: nowrap;
            transition: all 0.2s;
        }

        .fmd-tab:hover {
            color: var(--fap-text);
        }

        .fmd-tab.active {
            color: var(--module-color);
            border-bottom-color: var(--module-color);
        }

        .fmd-tab .dashicons {
            font-size: 18px;
            width: 18px;
            height: 18px;
        }

        .fmd-tab-panels {
            padding: 24px;
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
            margin: 0 8px;
            padding: 12px 0;
        }

        .fmd-tabs-separator::before {
            content: "";
            width: 1px;
            height: 24px;
            background: var(--fap-border);
        }

        /* Integration Tabs */
        .fmd-tab--integration {
            opacity: 0.85;
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
            padding: 0 6px;
            font-size: 0.75rem;
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
                gap: 16px;
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
                padding: 0;
            }

            .fmd-tab {
                padding: 12px 16px;
                font-size: 0.8125rem;
            }

            .fmd-tab span:not(.dashicons) {
                display: none;
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

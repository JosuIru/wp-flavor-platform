<?php
/**
 * Clase base para controladores frontend
 *
 * @package FlavorPlatform
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Clase abstracta base para controladores frontend de módulos
 */
abstract class Flavor_Frontend_Controller_Base {

    /**
     * Slug del módulo (ej: 'espacios-comunes', 'ayuda-vecinal')
     *
     * @var string
     */
    protected $slug = '';

    /**
     * Nombre del módulo para mostrar
     *
     * @var string
     */
    protected $nombre = '';

    /**
     * Icono del módulo (emoji o dashicon)
     *
     * @var string
     */
    protected $icono = '📦';

    /**
     * Color primario del módulo (clase Tailwind)
     *
     * @var string
     */
    protected $color_primario = 'blue';

    /**
     * Instancia de la clase
     *
     * @var self
     */
    protected static $instances = [];

    /**
     * Constructor
     */
    public function __construct() {
        $this->init_hooks();
    }

    /**
     * Obtiene la instancia singleton
     *
     * @return static
     */
    public static function get_instance() {
        $class = get_called_class();
        if (!isset(self::$instances[$class])) {
            self::$instances[$class] = new $class();
        }
        return self::$instances[$class];
    }

    /**
     * Inicializa los hooks de WordPress
     */
    protected function init_hooks() {
        // Registrar rewrite rules
        add_action('init', [$this, 'register_rewrite_rules'], 10);
        add_filter('query_vars', [$this, 'register_query_vars']);

        // Interceptar requests
        add_action('template_redirect', [$this, 'handle_request']);

        // Registrar endpoints REST
        add_action('rest_api_init', [$this, 'register_rest_routes']);

        // AJAX handlers
        add_action('wp_ajax_flavor_' . str_replace('-', '_', $this->slug) . '_action', [$this, 'handle_ajax']);
        add_action('wp_ajax_nopriv_flavor_' . str_replace('-', '_', $this->slug) . '_action', [$this, 'handle_ajax']);
    }

    /**
     * Registra las rewrite rules
     */
    public function register_rewrite_rules() {
        // Archive: /modulo/
        add_rewrite_rule(
            '^' . $this->slug . '/?$',
            'index.php?flavor_module=' . $this->slug . '&flavor_view=archive',
            'top'
        );

        // Search: /modulo/buscar/
        add_rewrite_rule(
            '^' . $this->slug . '/buscar/?$',
            'index.php?flavor_module=' . $this->slug . '&flavor_view=search',
            'top'
        );

        // Single: /modulo/item-slug/ o /modulo/123/
        add_rewrite_rule(
            '^' . $this->slug . '/([^/]+)/?$',
            'index.php?flavor_module=' . $this->slug . '&flavor_view=single&flavor_item=$matches[1]',
            'top'
        );

        // Acciones: /modulo/accion/nombre-accion/
        add_rewrite_rule(
            '^' . $this->slug . '/accion/([^/]+)/?$',
            'index.php?flavor_module=' . $this->slug . '&flavor_view=action&flavor_action=$matches[1]',
            'top'
        );
    }

    /**
     * Registra las query vars
     *
     * @param array $vars Variables de query existentes
     * @return array
     */
    public function register_query_vars($vars) {
        $vars[] = 'flavor_module';
        $vars[] = 'flavor_view';
        $vars[] = 'flavor_item';
        $vars[] = 'flavor_action';
        return $vars;
    }

    /**
     * Maneja la request y renderiza la vista apropiada
     */
    public function handle_request() {
        $module = get_query_var('flavor_module');

        if ($module !== $this->slug) {
            return;
        }

        $view = get_query_var('flavor_view', 'archive');
        $item = get_query_var('flavor_item', '');
        $action = get_query_var('flavor_action', '');

        // Configurar headers
        status_header(200);
        nocache_headers();

        // Cargar datos según la vista
        switch ($view) {
            case 'archive':
                $this->render_archive();
                break;

            case 'single':
                $this->render_single($item);
                break;

            case 'search':
                $this->render_search();
                break;

            case 'action':
                $this->handle_action($action);
                break;

            default:
                $this->render_404();
                break;
        }

        exit;
    }

    /**
     * Renderiza la vista de archivo (listado)
     */
    protected function render_archive() {
        $datos = $this->get_archive_data();
        $this->render_template('archive', $datos);
    }

    /**
     * Renderiza la vista de detalle
     *
     * @param string $item ID o slug del item
     */
    protected function render_single($item) {
        $datos = $this->get_single_data($item);

        if (empty($datos)) {
            $this->render_404();
            return;
        }

        $this->render_template('single', $datos);
    }

    /**
     * Renderiza la vista de búsqueda
     */
    protected function render_search() {
        $query = isset($_GET['q']) ? sanitize_text_field($_GET['q']) : '';
        $datos = $this->get_search_data($query);
        $datos['query'] = $query;
        $this->render_template('search', $datos);
    }

    /**
     * Maneja acciones específicas (formularios, etc.)
     *
     * @param string $action Nombre de la acción
     */
    protected function handle_action($action) {
        // Verificar nonce si es POST
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            if (!wp_verify_nonce($_POST['_wpnonce'] ?? '', 'flavor_' . $this->slug . '_' . $action)) {
                wp_die(__('Acción no permitida', FLAVOR_PLATFORM_TEXT_DOMAIN));
            }
        }

        $metodo = 'action_' . str_replace('-', '_', $action);
        if (method_exists($this, $metodo)) {
            $this->$metodo();
        } else {
            $this->render_404();
        }
    }

    /**
     * Renderiza página 404
     */
    protected function render_404() {
        status_header(404);
        $this->render_template('404', [
            'mensaje' => __('Página no encontrada', FLAVOR_PLATFORM_TEXT_DOMAIN),
        ]);
    }

    /**
     * Renderiza una plantilla con datos
     *
     * @param string $template Nombre de la plantilla (sin extensión)
     * @param array $datos Datos para la plantilla
     */
    protected function render_template($template, $datos = []) {
        // Datos comunes para todas las plantillas
        $datos_comunes = [
            'modulo_slug' => $this->slug,
            'modulo_nombre' => $this->nombre,
            'modulo_icono' => $this->icono,
            'modulo_color' => $this->color_primario,
            'base_url' => home_url('/' . $this->slug . '/'),
            'is_logged_in' => is_user_logged_in(),
            'current_user' => wp_get_current_user(),
        ];

        $datos = array_merge($datos_comunes, $datos);

        // Extraer variables para la plantilla
        extract($datos);

        // Ruta de la plantilla
        $template_path = FLAVOR_PLATFORM_PATH . 'templates/frontend/' . $this->slug . '/' . $template . '.php';

        // Buscar plantilla en el tema primero (permite override)
        $theme_template = locate_template('flavor-chat-ia/' . $this->slug . '/' . $template . '.php');
        if ($theme_template) {
            $template_path = $theme_template;
        }

        // Cargar el layout wrapper
        $this->render_layout_start($datos);

        // Incluir la plantilla específica
        if (file_exists($template_path)) {
            include $template_path;
        } else {
            echo '<div class="p-8 text-center text-red-500">';
            echo '<p>Plantilla no encontrada: ' . esc_html($template) . '</p>';
            echo '</div>';
        }

        // Cerrar el layout
        $this->render_layout_end($datos);
    }

    /**
     * Renderiza el inicio del layout HTML
     *
     * @param array $datos Datos de la página
     */
    protected function render_layout_start($datos) {
        $titulo_pagina = $datos['titulo_pagina'] ?? $this->nombre;
        ?>
<!DOCTYPE html>
<html <?php language_attributes(); ?>>
<head>
    <meta charset="<?php bloginfo('charset'); ?>">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo esc_html($titulo_pagina . ' - ' . get_bloginfo('name')); ?></title>

    <!-- Tailwind CSS -->
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    fontFamily: {
                        sans: ['Inter', 'system-ui', 'sans-serif'],
                    }
                }
            }
        }
    </script>

    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">

    <?php wp_head(); ?>

    <style>
        body {
            font-family: 'Inter', system-ui, sans-serif;
        }
        .line-clamp-2 {
            display: -webkit-box;
            -webkit-line-clamp: 2;
            -webkit-box-orient: vertical;
            overflow: hidden;
        }
        .line-clamp-3 {
            display: -webkit-box;
            -webkit-line-clamp: 3;
            -webkit-box-orient: vertical;
            overflow: hidden;
        }
    </style>
</head>
<body class="bg-gray-50 min-h-screen">
    <!-- Navegación superior -->
    <nav class="bg-white shadow-sm border-b border-gray-100 sticky top-0 z-50">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex justify-between h-16">
                <div class="flex items-center gap-4">
                    <a href="<?php echo esc_url(home_url('/')); ?>" class="text-gray-600 hover:text-gray-900">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"/>
                        </svg>
                    </a>
                    <span class="text-gray-300">|</span>
                    <a href="<?php echo esc_url($datos['base_url']); ?>" class="flex items-center gap-2 font-semibold text-gray-900">
                        <span><?php echo esc_html($this->icono); ?></span>
                        <span><?php echo esc_html($this->nombre); ?></span>
                    </a>
                </div>

                <div class="flex items-center gap-4">
                    <?php if (is_user_logged_in()): ?>
                    <span class="text-sm text-gray-600">
                        <?php echo esc_html(wp_get_current_user()->display_name); ?>
                    </span>
                    <?php else: ?>
                    <a href="<?php echo esc_url(wp_login_url()); ?>" class="text-sm text-gray-600 hover:text-gray-900">
                        Iniciar sesión
                    </a>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </nav>

    <!-- Contenido principal -->
    <main class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
        <?php
    }

    /**
     * Renderiza el final del layout HTML
     *
     * @param array $datos Datos de la página
     */
    protected function render_layout_end($datos) {
        ?>
    </main>

    <!-- Footer -->
    <footer class="bg-white border-t border-gray-100 mt-12">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
            <div class="text-center text-gray-500 text-sm">
                &copy; <?php echo date('Y'); ?> <?php bloginfo('name'); ?>. Todos los derechos reservados.
            </div>
        </div>
    </footer>

    <!-- Scripts del módulo -->
    <script>
        // Namespace del módulo
        window.flavor<?php echo esc_js(str_replace('-', '', ucwords($this->slug, '-'))); ?> = {
            ajaxUrl: '<?php echo esc_url(admin_url('admin-ajax.php')); ?>',
            nonce: '<?php echo wp_create_nonce('flavor_' . $this->slug); ?>',
            moduleSlug: '<?php echo esc_js($this->slug); ?>',

            // Método genérico para llamadas AJAX
            ajax: function(action, data) {
                data = data || {};
                data.action = 'flavor_<?php echo esc_js(str_replace('-', '_', $this->slug)); ?>_action';
                data.sub_action = action;
                data._wpnonce = this.nonce;

                return fetch(this.ajaxUrl, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: new URLSearchParams(data)
                }).then(response => response.json());
            },

            // Toast notifications
            toast: function(message, type) {
                type = type || 'info';
                var colors = {
                    'success': 'bg-green-500',
                    'error': 'bg-red-500',
                    'warning': 'bg-yellow-500',
                    'info': 'bg-blue-500'
                };

                var toast = document.createElement('div');
                toast.className = 'fixed bottom-4 right-4 ' + colors[type] + ' text-white px-6 py-3 rounded-xl shadow-lg z-50 animate-fade-in';
                toast.textContent = message;
                document.body.appendChild(toast);

                setTimeout(function() {
                    toast.remove();
                }, 3000);
            }
        };
    </script>

    <?php wp_footer(); ?>
</body>
</html>
        <?php
    }

    /**
     * Registra rutas REST API
     */
    public function register_rest_routes() {
        $namespace = FLAVOR_PLATFORM_REST_NAMESPACE;

        // GET /modulo/
        flavor_register_rest_route($namespace, '/' . $this->slug, [
            'methods' => 'GET',
            'callback' => [$this, 'rest_get_items'],
            'permission_callback' => [$this, 'public_permission_check'],
        ]);

        // GET /modulo/{id}
        flavor_register_rest_route($namespace, '/' . $this->slug . '/(?P<id>[\d]+)', [
            'methods' => 'GET',
            'callback' => [$this, 'rest_get_item'],
            'permission_callback' => [$this, 'public_permission_check'],
        ]);

        // POST /modulo/ (crear)
        flavor_register_rest_route($namespace, '/' . $this->slug, [
            'methods' => 'POST',
            'callback' => [$this, 'rest_create_item'],
            'permission_callback' => [$this, 'rest_permission_check'],
        ]);

        // Rutas adicionales específicas del módulo
        $this->register_custom_rest_routes($namespace);
    }

    /**
     * Registra rutas REST personalizadas (para sobrescribir en hijos)
     *
     * @param string $namespace Namespace de la API
     */
    protected function register_custom_rest_routes($namespace) {
        // Sobrescribir en clases hijas
    }

    /**
     * Verifica permisos para endpoints REST
     *
     * @return bool
     */
    public function rest_permission_check() {
        return is_user_logged_in();
    }

    /**
     * Endpoint REST: obtener listado
     *
     * @param WP_REST_Request $request
     * @return WP_REST_Response
     */
    public function rest_get_items($request) {
        $datos = $this->get_archive_data();
        return new WP_REST_Response($datos, 200);
    }

    /**
     * Endpoint REST: obtener item
     *
     * @param WP_REST_Request $request
     * @return WP_REST_Response
     */
    public function rest_get_item($request) {
        $id = $request->get_param('id');
        $datos = $this->get_single_data($id);

        if (empty($datos)) {
            return new WP_REST_Response(['error' => __('No encontrado', FLAVOR_PLATFORM_TEXT_DOMAIN)], 404);
        }

        return new WP_REST_Response($datos, 200);
    }

    /**
     * Endpoint REST: crear item
     *
     * @param WP_REST_Request $request
     * @return WP_REST_Response
     */
    public function rest_create_item($request) {
        // Sobrescribir en clases hijas
        return new WP_REST_Response(['error' => __('No implementado', FLAVOR_PLATFORM_TEXT_DOMAIN)], 501);
    }

    /**
     * Maneja peticiones AJAX
     */
    public function handle_ajax() {
        // Verificar nonce
        if (!wp_verify_nonce($_POST['_wpnonce'] ?? '', 'flavor_' . $this->slug)) {
            wp_send_json_error(['message' => __('Nonce inválido', FLAVOR_PLATFORM_TEXT_DOMAIN)]);
        }

        $sub_action = sanitize_text_field($_POST['sub_action'] ?? '');
        $metodo = 'ajax_' . str_replace('-', '_', $sub_action);

        if (method_exists($this, $metodo)) {
            $resultado = $this->$metodo($_POST);
            wp_send_json_success($resultado);
        } else {
            wp_send_json_error(['message' => __('Acción no encontrada', FLAVOR_PLATFORM_TEXT_DOMAIN)]);
        }
    }

    /**
     * Obtiene datos para la vista de archivo
     * Debe ser implementado por las clases hijas
     *
     * @return array
     */
    abstract protected function get_archive_data();

    /**
     * Obtiene datos para la vista de detalle
     * Debe ser implementado por las clases hijas
     *
     * @param string $item_id ID o slug del item
     * @return array
     */
    abstract protected function get_single_data($item_id);

    /**
     * Obtiene datos para la vista de búsqueda
     * Debe ser implementado por las clases hijas
     *
     * @param string $query Término de búsqueda
     * @return array
     */
    abstract protected function get_search_data($query);

    /**
     * Helper: Obtener paginación
     *
     * @param int $total Total de items
     * @param int $per_page Items por página
     * @param int $current_page Página actual
     * @return array
     */
    protected function get_pagination($total, $per_page = 12, $current_page = 1) {
        $total_paginas = ceil($total / $per_page);

        return [
            'total' => $total,
            'per_page' => $per_page,
            'current_page' => $current_page,
            'total_pages' => $total_paginas,
            'has_prev' => $current_page > 1,
            'has_next' => $current_page < $total_paginas,
            'prev_url' => $current_page > 1 ? add_query_arg('pag', $current_page - 1) : null,
            'next_url' => $current_page < $total_paginas ? add_query_arg('pag', $current_page + 1) : null,
        ];
    }

    /**
     * Helper: Obtener filtros de la URL
     *
     * @param array $filtros_permitidos Lista de filtros permitidos
     * @return array
     */
    protected function get_filters_from_url($filtros_permitidos = []) {
        $filtros = [];

        foreach ($filtros_permitidos as $filtro) {
            if (isset($_GET[$filtro])) {
                $valor = $_GET[$filtro];
                if (is_array($valor)) {
                    $filtros[$filtro] = array_map('sanitize_text_field', $valor);
                } else {
                    $filtros[$filtro] = sanitize_text_field($valor);
                }
            }
        }

        return $filtros;
    }

    public function public_permission_check($request) {
        $method = strtoupper($request->get_method());
        $tipo = in_array($method, ['POST', 'PUT', 'DELETE'], true) ? 'post' : 'get';
        return Flavor_API_Rate_Limiter::check_rate_limit($tipo);
    }
}

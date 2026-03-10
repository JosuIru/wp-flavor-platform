<?php
/**
 * Registrador Automático de Páginas de Dashboard de Módulos
 *
 * Detecta módulos activos que tienen vistas de admin (views/dashboard.php)
 * y los registra automáticamente como páginas de WordPress admin.
 *
 * Las páginas se registran de forma "hidden" (sin menú visible) porque
 * la navegación se gestiona a través del Flavor Shell.
 *
 * @package FlavorPlatform
 * @subpackage Admin
 * @since 3.3.1
 */

if (!defined('ABSPATH')) {
    exit;
}

class Flavor_Module_Dashboards_Registrar {

    /**
     * Instancia singleton
     *
     * @var Flavor_Module_Dashboards_Registrar|null
     */
    private static $instance = null;

    /**
     * Cache de módulos con dashboards
     *
     * @var array
     */
    private $modules_with_dashboards = [];

    /**
     * Mapping de slug de página a configuración de módulo
     *
     * @var array
     */
    private $page_configs = [];

    /**
     * Obtiene la instancia singleton
     *
     * @return Flavor_Module_Dashboards_Registrar
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
        // Detectar módulos con dashboards
        add_action('init', [$this, 'detectar_modulos_con_dashboards'], 15);

        // Registrar páginas de admin
        add_action('admin_menu', [$this, 'registrar_paginas_admin'], 50);

        // Cargar estilos para dashboards de módulos
        add_action('admin_enqueue_scripts', [$this, 'enqueue_dashboard_styles']);
    }

    /**
     * Detecta módulos activos que tienen vistas de dashboard
     */
    public function detectar_modulos_con_dashboards() {
        // Obtener módulos activos
        $modulos_activos = $this->get_active_modules();

        // Directorio base de módulos
        $modules_dir = FLAVOR_CHAT_IA_PATH . 'includes/modules/';

        foreach ($modulos_activos as $module_id) {
            // Normalizar ID (guiones bajos a guiones)
            $module_dir_name = str_replace('_', '-', $module_id);

            // Buscar vistas de dashboard
            $possible_paths = [
                $modules_dir . $module_dir_name . '/views/dashboard.php',
                $modules_dir . $module_id . '/views/dashboard.php',
            ];

            foreach ($possible_paths as $dashboard_path) {
                if (file_exists($dashboard_path)) {
                    $this->modules_with_dashboards[$module_id] = [
                        'module_id' => $module_id,
                        'dashboard_path' => $dashboard_path,
                        'views_dir' => dirname($dashboard_path),
                        'slug' => $this->get_dashboard_slug($module_id),
                        'title' => $this->get_module_title($module_id),
                        'icon' => $this->get_module_icon($module_id),
                    ];
                    break;
                }
            }
        }
    }

    /**
     * Obtiene el slug de dashboard para un módulo
     *
     * @param string $module_id ID del módulo
     * @return string Slug de la página
     */
    private function get_dashboard_slug($module_id) {
        // Mapping de slugs canónico - sincronizado con trait-module-admin-pages.php
        static $slugs_mapping = null;

        if ($slugs_mapping === null) {
            $slugs_mapping = [
                // Administración
                'advertising' => 'advertising-dashboard',

                // Servicios públicos
                'avisos_municipales' => 'avisos-dashboard',
                'presupuestos_participativos' => 'presupuestos-dashboard',
                'transparencia' => 'transparencia-dashboard',
                'seguimiento_denuncias' => 'denuncias-dashboard',
                'documentacion_legal' => 'documentos-dashboard',
                'tramites' => 'tramites-dashboard',
                'incidencias' => 'incidencias-dashboard',
                'participacion' => 'participacion-dashboard',

                // Comunidad
                'socios' => 'socios-dashboard',
                'colectivos' => 'flavor-colectivos-dashboard',
                'comunidades' => 'comunidades-dashboard',
                'foros' => 'foros-dashboard',
                'red_social' => 'flavor-red-social-dashboard',
                'mapa_actores' => 'actores-dashboard',

                // Economía
                'grupos_consumo' => 'gc-dashboard',
                'marketplace' => 'marketplace-dashboard',
                'banco_tiempo' => 'banco-tiempo-dashboard',
                'economia_don' => 'economia-don-dashboard',
                'economia_suficiencia' => 'suficiencia-dashboard',

                // Actividades
                'eventos' => 'eventos-dashboard',
                'cursos' => 'cursos-dashboard',
                'talleres' => 'talleres-dashboard',
                'reservas' => 'reservas-dashboard',

                // Recursos
                'huertos_urbanos' => 'huertos-dashboard',
                'espacios_comunes' => 'espacios-dashboard',
                'biblioteca' => 'biblioteca-dashboard',
                'carpooling' => 'carpooling-dashboard',
                'fichaje_empleados' => 'fichaje-dashboard',
                'parkings' => 'parkings-dashboard',
                'bares' => 'bares-dashboard',
                'recetas' => 'recetas-dashboard',

                // Sostenibilidad
                'reciclaje' => 'reciclaje-dashboard',
                'compostaje' => 'compostaje-dashboard',
                'energia_comunitaria' => 'flavor-energia-dashboard',
                'bicicletas_compartidas' => 'bicicletas-dashboard',
                'biodiversidad_local' => 'biodiversidad-dashboard',
                'huella_ecologica' => 'huella-ecologica-dashboard',
                'saberes_ancestrales' => 'saberes-dashboard',
                'circulos_cuidados' => 'circulos-cuidados-dashboard',
                'trabajo_digno' => 'trabajo-digno-dashboard',
                'justicia_restaurativa' => 'justicia-restaurativa-dashboard',

                // Comunicación
                'multimedia' => 'multimedia-dashboard',
                'radio' => 'flavor-radio-dashboard',
                'podcast' => 'podcast-dashboard',
                'campanias' => 'campanias-dashboard',
                'email_marketing' => 'email-marketing-dashboard',
                'encuestas' => 'encuestas-dashboard',

                // Chat
                'chat_estados' => 'chat-estados-dashboard',
                'chat_grupos' => 'chat-grupos-dashboard',
                'chat_interno' => 'chat-interno-dashboard',

                // Negocios
                'clientes' => 'clientes-dashboard',
                'facturas' => 'facturas-dashboard',
                'empresarial' => 'empresarial-dashboard',
                'woocommerce' => 'flavor-woocommerce-dashboard',
                'crowdfunding' => 'crowdfunding-dashboard',
                'themacle' => 'themacle-dashboard',
                'trading_ia' => 'trading-ia-dashboard',
                'dex_solana' => 'dex-solana-dashboard',

                // Social
                'ayuda_vecinal' => 'ayuda-dashboard',
                'sello_conciencia' => 'sello-conciencia-dashboard',

                // Especiales
                'kulturaka' => 'kulturaka-dashboard',
            ];
        }

        if (isset($slugs_mapping[$module_id])) {
            return $slugs_mapping[$module_id];
        }

        // Fallback: generar slug basado en el ID
        $normalized_id = str_replace('_', '-', $module_id);
        return $normalized_id . '-dashboard';
    }

    /**
     * Obtiene el título de un módulo
     *
     * @param string $module_id ID del módulo
     * @return string Título legible
     */
    private function get_module_title($module_id) {
        $titles = [
            'socios' => __('Gestión de Socios', 'flavor-chat-ia'),
            'eventos' => __('Eventos', 'flavor-chat-ia'),
            'reservas' => __('Reservas', 'flavor-chat-ia'),
            'tramites' => __('Trámites', 'flavor-chat-ia'),
            'incidencias' => __('Incidencias', 'flavor-chat-ia'),
            'participacion' => __('Participación', 'flavor-chat-ia'),
            'foros' => __('Foros', 'flavor-chat-ia'),
            'comunidades' => __('Comunidades', 'flavor-chat-ia'),
            'colectivos' => __('Colectivos', 'flavor-chat-ia'),
            'marketplace' => __('Marketplace', 'flavor-chat-ia'),
            'banco_tiempo' => __('Banco de Tiempo', 'flavor-chat-ia'),
            'economia_don' => __('Economía del Don', 'flavor-chat-ia'),
            'grupos_consumo' => __('Grupos de Consumo', 'flavor-chat-ia'),
            'huertos_urbanos' => __('Huertos Urbanos', 'flavor-chat-ia'),
            'espacios_comunes' => __('Espacios Comunes', 'flavor-chat-ia'),
            'biblioteca' => __('Biblioteca', 'flavor-chat-ia'),
            'carpooling' => __('Carpooling', 'flavor-chat-ia'),
            'bicicletas_compartidas' => __('Bicicletas Compartidas', 'flavor-chat-ia'),
            'reciclaje' => __('Reciclaje', 'flavor-chat-ia'),
            'compostaje' => __('Compostaje', 'flavor-chat-ia'),
            'energia_comunitaria' => __('Energía Comunitaria', 'flavor-chat-ia'),
            'radio' => __('Radio', 'flavor-chat-ia'),
            'podcast' => __('Podcast', 'flavor-chat-ia'),
            'multimedia' => __('Multimedia', 'flavor-chat-ia'),
            'chat_grupos' => __('Chat de Grupos', 'flavor-chat-ia'),
            'chat_interno' => __('Chat Interno', 'flavor-chat-ia'),
            'red_social' => __('Red Social', 'flavor-chat-ia'),
            'cursos' => __('Cursos', 'flavor-chat-ia'),
            'talleres' => __('Talleres', 'flavor-chat-ia'),
            'avisos_municipales' => __('Avisos Municipales', 'flavor-chat-ia'),
            'transparencia' => __('Transparencia', 'flavor-chat-ia'),
            'presupuestos_participativos' => __('Presupuestos Participativos', 'flavor-chat-ia'),
        ];

        if (isset($titles[$module_id])) {
            return $titles[$module_id];
        }

        // Fallback: convertir ID a título legible
        return ucwords(str_replace(['_', '-'], ' ', $module_id));
    }

    /**
     * Obtiene el icono de un módulo
     *
     * @param string $module_id ID del módulo
     * @return string Clase de dashicon
     */
    private function get_module_icon($module_id) {
        $icons = [
            'socios' => 'dashicons-groups',
            'eventos' => 'dashicons-calendar-alt',
            'reservas' => 'dashicons-calendar',
            'tramites' => 'dashicons-clipboard',
            'incidencias' => 'dashicons-warning',
            'participacion' => 'dashicons-megaphone',
            'foros' => 'dashicons-format-chat',
            'comunidades' => 'dashicons-networking',
            'colectivos' => 'dashicons-groups',
            'marketplace' => 'dashicons-store',
            'banco_tiempo' => 'dashicons-clock',
            'economia_don' => 'dashicons-heart',
            'grupos_consumo' => 'dashicons-cart',
            'huertos_urbanos' => 'dashicons-carrot',
            'espacios_comunes' => 'dashicons-building',
            'biblioteca' => 'dashicons-book',
            'carpooling' => 'dashicons-car',
            'bicicletas_compartidas' => 'dashicons-performance',
            'reciclaje' => 'dashicons-update',
            'compostaje' => 'dashicons-palmtree',
            'energia_comunitaria' => 'dashicons-lightbulb',
            'radio' => 'dashicons-microphone',
            'podcast' => 'dashicons-playlist-audio',
            'multimedia' => 'dashicons-format-gallery',
            'chat_grupos' => 'dashicons-format-chat',
            'chat_interno' => 'dashicons-email-alt',
            'red_social' => 'dashicons-share',
            'cursos' => 'dashicons-welcome-learn-more',
            'talleres' => 'dashicons-hammer',
            'avisos_municipales' => 'dashicons-megaphone',
            'transparencia' => 'dashicons-visibility',
            'presupuestos_participativos' => 'dashicons-money-alt',
        ];

        return $icons[$module_id] ?? 'dashicons-admin-generic';
    }

    /**
     * Registra las páginas de admin para los módulos detectados
     */
    public function registrar_paginas_admin() {
        foreach ($this->modules_with_dashboards as $module_id => $config) {
            // Registrar página principal de dashboard
            $this->registrar_pagina_dashboard($config);

            // Registrar subpáginas si existen
            $this->registrar_subpaginas($config);
        }
    }

    /**
     * Registra la página de dashboard principal de un módulo
     *
     * @param array $config Configuración del módulo
     */
    private function registrar_pagina_dashboard($config) {
        $slug = $config['slug'];

        // Guardar configuración para uso en callback
        $this->page_configs[$slug] = $config;

        // Registrar como página oculta (sin entrada de menú visible)
        // La navegación se maneja a través del Flavor Shell
        add_submenu_page(
            null, // Parent null = página oculta
            $config['title'],
            $config['title'],
            'manage_options',
            $slug,
            [$this, 'render_dashboard_page']
        );
    }

    /**
     * Registra subpáginas adicionales de un módulo
     *
     * @param array $config Configuración del módulo
     */
    private function registrar_subpaginas($config) {
        $views_dir = $config['views_dir'];

        // Buscar otras vistas PHP además del dashboard
        $view_files = glob($views_dir . '/*.php');

        foreach ($view_files as $view_file) {
            $filename = basename($view_file, '.php');

            // Ignorar el dashboard (ya registrado) y archivos internos
            if ($filename === 'dashboard' || strpos($filename, '_') === 0) {
                continue;
            }

            $subpage_slug = str_replace('_', '-', $config['module_id']) . '-' . $filename;

            $this->page_configs[$subpage_slug] = [
                'module_id' => $config['module_id'],
                'dashboard_path' => $view_file,
                'views_dir' => $views_dir,
                'slug' => $subpage_slug,
                'title' => $config['title'] . ' - ' . ucwords(str_replace('-', ' ', $filename)),
                'icon' => $config['icon'],
                'is_subpage' => true,
                'parent_slug' => $config['slug'],
            ];

            add_submenu_page(
                null,
                $this->page_configs[$subpage_slug]['title'],
                $this->page_configs[$subpage_slug]['title'],
                'manage_options',
                $subpage_slug,
                [$this, 'render_dashboard_page']
            );
        }
    }

    /**
     * Renderiza una página de dashboard
     */
    public function render_dashboard_page() {
        $current_page = isset($_GET['page']) ? sanitize_text_field($_GET['page']) : '';

        if (!isset($this->page_configs[$current_page])) {
            echo '<div class="wrap"><h1>' . esc_html__('Página no encontrada', 'flavor-chat-ia') . '</h1></div>';
            return;
        }

        $config = $this->page_configs[$current_page];

        // Verificar que el archivo existe
        if (!file_exists($config['dashboard_path'])) {
            echo '<div class="wrap"><h1>' . esc_html__('Vista no disponible', 'flavor-chat-ia') . '</h1>';
            echo '<p>' . esc_html__('El archivo de vista no existe.', 'flavor-chat-ia') . '</p></div>';
            return;
        }

        // Preparar variables para la vista
        $module_id = $config['module_id'];
        $module_title = $config['title'];
        $module_icon = $config['icon'];

        ?>
        <div class="wrap flavor-module-dashboard" data-module="<?php echo esc_attr($module_id); ?>">
            <?php
            // Incluir la vista
            include $config['dashboard_path'];
            ?>
        </div>
        <?php
    }

    /**
     * Encola estilos para dashboards de módulos
     *
     * @param string $hook Hook de la página actual
     */
    public function enqueue_dashboard_styles($hook) {
        $current_page = isset($_GET['page']) ? sanitize_text_field($_GET['page']) : '';

        // Verificar si es una página de dashboard de módulo
        if (!isset($this->page_configs[$current_page])) {
            return;
        }

        // Estilos base para dashboards de módulos
        wp_enqueue_style(
            'flavor-module-dashboard',
            FLAVOR_CHAT_IA_URL . 'assets/css/layouts/dashboard-module-components.css',
            [],
            FLAVOR_CHAT_IA_VERSION
        );

        // Chart.js para gráficos
        wp_enqueue_script('chart-js', 'https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js', [], '4.4.0', true);
    }

    /**
     * Obtiene los módulos activos
     *
     * @return array Lista de IDs de módulos activos
     */
    private function get_active_modules() {
        $settings = get_option('flavor_chat_ia_settings', []);
        $modulos_activos = $settings['active_modules'] ?? [];

        // También buscar en opción legacy
        $modulos_legacy = get_option('flavor_active_modules', []);
        if (!empty($modulos_legacy)) {
            $modulos_activos = array_unique(array_merge($modulos_activos, $modulos_legacy));
        }

        return $modulos_activos;
    }

    /**
     * Obtiene los módulos con dashboards detectados
     *
     * @return array
     */
    public function get_modules_with_dashboards() {
        return $this->modules_with_dashboards;
    }

    /**
     * Verifica si un módulo tiene dashboard registrado
     *
     * @param string $module_id ID del módulo
     * @return bool
     */
    public function has_dashboard($module_id) {
        return isset($this->modules_with_dashboards[$module_id]);
    }

    /**
     * Obtiene la URL del dashboard de un módulo
     *
     * @param string $module_id ID del módulo
     * @return string|null URL o null si no existe
     */
    public function get_dashboard_url($module_id) {
        if (!$this->has_dashboard($module_id)) {
            return null;
        }

        return admin_url('admin.php?page=' . $this->modules_with_dashboards[$module_id]['slug']);
    }
}

// Inicializar el registrador
add_action('plugins_loaded', function() {
    if (is_admin()) {
        Flavor_Module_Dashboards_Registrar::get_instance();
    }
}, 20);

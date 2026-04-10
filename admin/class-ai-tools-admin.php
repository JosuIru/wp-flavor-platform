<?php
/**
 * UI para Herramientas de IA
 *
 * Controlador principal que maneja todas las interfaces de usuario
 * para las funcionalidades de IA del plugin:
 * - Module Chatbot (widget flotante)
 * - AI Content Generator (inline)
 * - Auto Reply Suggester (inline)
 * - Weekly Reports (página dedicada)
 * - Data Analyzer (página dedicada)
 * - Setup Assistant (widget)
 * - Content Translator (inline)
 * - Demo Data Generator (solo WP_DEBUG)
 *
 * @package FlavorPlatform
 * @since 3.3.2
 */

if (!defined('ABSPATH')) {
    exit;
}

class Flavor_AI_Tools_Admin {

    /**
     * Instancia singleton
     */
    private static $instance = null;

    /**
     * Slug del menú principal
     */
    const MENU_SLUG = 'flavor-ai-tools';

    /**
     * Capability requerida
     */
    const REQUIRED_CAP = 'edit_posts';

    /**
     * Módulo actual detectado
     */
    private $current_module = '';

    /**
     * Obtiene la instancia singleton
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

        add_action('admin_menu', [$this, 'register_menu'], 15);
        add_action('admin_enqueue_scripts', [$this, 'enqueue_assets']);
        add_action('admin_footer', [$this, 'render_chatbot_widget']);
        add_action('admin_footer', [$this, 'render_content_generator_modal']);
    }

    /**
     * Registra el menú de Herramientas IA
     */
    public function register_menu() {
        // Submenú bajo Flavor
        add_submenu_page(
            FLAVOR_PLATFORM_TEXT_DOMAIN,
            __('Herramientas IA', FLAVOR_PLATFORM_TEXT_DOMAIN),
            __('Herramientas IA', FLAVOR_PLATFORM_TEXT_DOMAIN),
            self::REQUIRED_CAP,
            self::MENU_SLUG,
            [$this, 'render_hub_page']
        );

        // Página de Reportes Semanales
        add_submenu_page(
            self::MENU_SLUG,
            __('Reportes Semanales', FLAVOR_PLATFORM_TEXT_DOMAIN),
            __('Reportes', FLAVOR_PLATFORM_TEXT_DOMAIN),
            'manage_options',
            'flavor-ai-weekly-reports',
            [$this, 'render_weekly_reports_page']
        );

        // Página de Analizador de Datos
        add_submenu_page(
            self::MENU_SLUG,
            __('Analizador de Datos', FLAVOR_PLATFORM_TEXT_DOMAIN),
            __('Analizador', FLAVOR_PLATFORM_TEXT_DOMAIN),
            'manage_options',
            'flavor-ai-data-analyzer',
            [$this, 'render_data_analyzer_page']
        );

        // Página de Demo Generator (solo en modo debug)
        if (defined('WP_DEBUG') && WP_DEBUG) {
            add_submenu_page(
                self::MENU_SLUG,
                __('Generador Demo', FLAVOR_PLATFORM_TEXT_DOMAIN),
                __('Datos Demo', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'manage_options',
                'flavor-ai-demo-generator',
                [$this, 'render_demo_generator_page']
            );
        }
    }

    /**
     * Encola assets CSS y JS
     */
    public function enqueue_assets($hook) {
        // Detectar si estamos en una página Flavor
        $is_flavor_page = $this->is_flavor_page();

        // CSS siempre en páginas Flavor
        if ($is_flavor_page) {
            wp_enqueue_style(
                'flavor-ai-tools',
                FLAVOR_PLATFORM_URL . 'admin/css/ai-tools.css',
                [],
                FLAVOR_PLATFORM_VERSION
            );
        }

        // JS común para todas las herramientas
        if ($is_flavor_page) {
            wp_enqueue_script(
                'flavor-ai-tools',
                FLAVOR_PLATFORM_URL . 'admin/js/ai-tools.js',
                ['jquery'],
                FLAVOR_PLATFORM_VERSION,
                true
            );

            // Chatbot flotante
            wp_enqueue_script(
                'flavor-module-chatbot',
                FLAVOR_PLATFORM_URL . 'admin/js/module-chatbot.js',
                ['jquery', 'flavor-ai-tools'],
                FLAVOR_PLATFORM_VERSION,
                true
            );

            // Generador de contenido inline
            wp_enqueue_script(
                'flavor-ai-content-generator',
                FLAVOR_PLATFORM_URL . 'admin/js/ai-content-generator.js',
                ['jquery', 'flavor-ai-tools'],
                FLAVOR_PLATFORM_VERSION,
                true
            );

            // Traductor inline
            wp_enqueue_script(
                'flavor-ai-translator',
                FLAVOR_PLATFORM_URL . 'admin/js/ai-translator.js',
                ['jquery', 'flavor-ai-tools'],
                FLAVOR_PLATFORM_VERSION,
                true
            );

            // Datos localizados
            wp_localize_script('flavor-ai-tools', 'flavorAITools', $this->get_localized_data());
        }

        // Reply suggester solo en páginas de incidencias
        if ($this->is_incidencias_detail_page()) {
            wp_enqueue_script(
                'flavor-ai-reply-suggester',
                FLAVOR_PLATFORM_URL . 'admin/js/ai-reply-suggester.js',
                ['jquery', 'flavor-ai-tools'],
                FLAVOR_PLATFORM_VERSION,
                true
            );
        }

        // Páginas dedicadas de herramientas IA
        if (strpos($hook, 'flavor-ai-') !== false) {
            // Chart.js para gráficos
            wp_enqueue_script(
                'chartjs',
                'https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js',
                [],
                '4.4.1',
                true
            );
        }
    }

    /**
     * Obtiene datos localizados para JS
     */
    private function get_localized_data() {
        $this->detect_current_module();

        return [
            'ajaxUrl' => admin_url('admin-ajax.php'),
            'nonces' => [
                'chat' => wp_create_nonce('flavor_module_chat'),
                'content' => wp_create_nonce('flavor_ai_content'),
                'reply' => wp_create_nonce('flavor_reply_suggestions'),
                'translate' => wp_create_nonce('flavor_content_translate'),
                'report' => wp_create_nonce('flavor_weekly_report'),
                'analyzer' => wp_create_nonce('flavor_data_analyzer'),
            ],
            'currentModule' => $this->current_module,
            'moduleContext' => $this->get_module_context(),
            'isConfigured' => $this->is_ai_configured(),
            'i18n' => [
                'chatTitle' => __('Asistente IA', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'chatPlaceholder' => __('Escribe tu pregunta...', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'sendMessage' => __('Enviar', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'generating' => __('Generando...', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'error' => __('Error al procesar', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'copySuccess' => __('Copiado al portapapeles', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'insertContent' => __('Insertar contenido', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'regenerate' => __('Regenerar', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'close' => __('Cerrar', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'generateWithAI' => __('Generar con IA', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'translateTo' => __('Traducir a', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'suggestReply' => __('Sugerir respuesta', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'useSuggestion' => __('Usar esta sugerencia', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'aiNotConfigured' => __('La IA no está configurada. Ve a Flavor Platform > Asistente IA > Configuración para activarla.', FLAVOR_PLATFORM_TEXT_DOMAIN),
            ],
        ];
    }

    /**
     * Detecta el módulo actual basándose en la URL
     */
    private function detect_current_module() {
        $page = isset($_GET['page']) ? sanitize_text_field($_GET['page']) : '';

        $module_patterns = [
            'socios' => ['socios-', 'miembros'],
            'eventos' => ['eventos-'],
            'reservas' => ['reservas-'],
            'cursos' => ['cursos-'],
            'talleres' => ['talleres-'],
            'grupos_consumo' => ['gc-', 'grupos-consumo'],
            'incidencias' => ['incidencias-'],
            'tramites' => ['tramites-'],
            'marketplace' => ['marketplace-'],
            'foros' => ['foros-'],
            'participacion' => ['participacion-', 'presupuestos-'],
            'transparencia' => ['transparencia-'],
            'biblioteca' => ['biblioteca-'],
            'huertos' => ['huertos-'],
            'espacios' => ['espacios-'],
            'reciclaje' => ['reciclaje-'],
            'compostaje' => ['compostaje-'],
            'comunidades' => ['comunidades-'],
            'colectivos' => ['colectivos-'],
            'banco_tiempo' => ['banco-tiempo-'],
            'email_marketing' => ['email-marketing-'],
            'campanias' => ['campanias-'],
        ];

        foreach ($module_patterns as $module_key => $patterns) {
            foreach ($patterns as $pattern) {
                if (strpos($page, $pattern) !== false) {
                    $this->current_module = $module_key;
                    return;
                }
            }
        }

        // Módulo genérico si estamos en Flavor pero no detectamos módulo específico
        if ($this->is_flavor_page()) {
            $this->current_module = 'general';
        }
    }

    /**
     * Obtiene el contexto del módulo actual
     */
    private function get_module_context() {
        if (empty($this->current_module) || $this->current_module === 'general') {
            return [
                'name' => __('Flavor Platform', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'description' => __('Plataforma de gestión comunitaria', FLAVOR_PLATFORM_TEXT_DOMAIN),
            ];
        }

        // Intentar obtener contexto del chatbot de módulo
        if (class_exists('Flavor_Module_Chatbot')) {
            $chatbot = Flavor_Module_Chatbot::get_instance();
            $context = $chatbot->get_module_context($this->current_module);
            if ($context) {
                return $context;
            }
        }

        return [
            'name' => ucfirst(str_replace('_', ' ', $this->current_module)),
            'description' => '',
        ];
    }

    /**
     * Verifica si la IA está configurada
     */
    private function is_ai_configured() {
        if (class_exists('Flavor_Engine_Manager')) {
            $manager = Flavor_Engine_Manager::get_instance();
            $engine = $manager->get_active_engine();
            return $engine && $engine->is_configured();
        }
        return false;
    }

    /**
     * Verifica si estamos en una página Flavor
     */
    private function is_flavor_page() {
        $page = isset($_GET['page']) ? sanitize_text_field($_GET['page']) : '';

        $flavor_prefixes = [
            'flavor-', 'gc-', 'socios', 'eventos', 'reservas', 'cursos',
            'talleres', 'marketplace', 'incidencias', 'tramites', 'foros',
            'participacion', 'transparencia', 'biblioteca', 'huertos',
            'espacios', 'reciclaje', 'compostaje', 'comunidades', 'colectivos',
            'banco-tiempo', 'email-marketing', 'campanias', 'carpooling',
            'multimedia', 'podcast', 'radio', 'biodiversidad', 'actores',
        ];

        foreach ($flavor_prefixes as $prefix) {
            if (strpos($page, $prefix) === 0) {
                return true;
            }
        }

        return false;
    }

    /**
     * Verifica si estamos en página de detalle de incidencias
     */
    private function is_incidencias_detail_page() {
        $page = isset($_GET['page']) ? sanitize_text_field($_GET['page']) : '';
        $action = isset($_GET['action']) ? sanitize_text_field($_GET['action']) : '';

        return strpos($page, 'incidencias') !== false &&
               ($action === 'view' || $action === 'edit' || isset($_GET['id']));
    }

    /**
     * Renderiza el widget del chatbot flotante
     */
    public function render_chatbot_widget() {
        if (!$this->is_flavor_page()) {
            return;
        }

        // No mostrar si el chatbot de módulo no está disponible
        if (!class_exists('Flavor_Module_Chatbot')) {
            return;
        }

        $this->detect_current_module();
        $module_context = $this->get_module_context();
        $is_configured = $this->is_ai_configured();

        include FLAVOR_PLATFORM_PATH . 'admin/views/ai-tools/chatbot-widget.php';
    }

    /**
     * Renderiza el modal del generador de contenido
     */
    public function render_content_generator_modal() {
        if (!$this->is_flavor_page()) {
            return;
        }

        include FLAVOR_PLATFORM_PATH . 'admin/views/ai-tools/content-generator-modal.php';
    }

    /**
     * Renderiza la página Hub de Herramientas IA
     */
    public function render_hub_page() {
        if (!current_user_can(self::REQUIRED_CAP)) {
            wp_die(__('No tienes permisos para acceder a esta página.', FLAVOR_PLATFORM_TEXT_DOMAIN));
        }

        include FLAVOR_PLATFORM_PATH . 'admin/views/ai-tools/ai-tools-hub.php';
    }

    /**
     * Renderiza la página de Reportes Semanales
     */
    public function render_weekly_reports_page() {
        if (!current_user_can('manage_options')) {
            wp_die(__('No tienes permisos para acceder a esta página.', FLAVOR_PLATFORM_TEXT_DOMAIN));
        }

        include FLAVOR_PLATFORM_PATH . 'admin/views/ai-tools/weekly-reports.php';
    }

    /**
     * Renderiza la página del Analizador de Datos
     */
    public function render_data_analyzer_page() {
        if (!current_user_can('manage_options')) {
            wp_die(__('No tienes permisos para acceder a esta página.', FLAVOR_PLATFORM_TEXT_DOMAIN));
        }

        include FLAVOR_PLATFORM_PATH . 'admin/views/ai-tools/data-analyzer.php';
    }

    /**
     * Renderiza la página del Generador de Datos Demo
     */
    public function render_demo_generator_page() {
        if (!current_user_can('manage_options') || !WP_DEBUG) {
            wp_die(__('No tienes permisos para acceder a esta página.', FLAVOR_PLATFORM_TEXT_DOMAIN));
        }

        include FLAVOR_PLATFORM_PATH . 'admin/views/ai-tools/demo-generator.php';
    }

    /**
     * Obtiene las herramientas disponibles para el hub
     */
    public function get_available_tools() {
        $is_configured = $this->is_ai_configured();

        $tools = [
            'chatbot' => [
                'id' => 'chatbot',
                'name' => __('Asistente de Módulos', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'description' => __('Chatbot contextual que te ayuda con cada módulo. Aparece como widget flotante en todas las páginas.', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'icon' => 'dashicons-format-chat',
                'status' => $is_configured ? 'active' : 'disabled',
                'type' => 'widget',
            ],
            'content_generator' => [
                'id' => 'content_generator',
                'name' => __('Generador de Contenido', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'description' => __('Genera descripciones, emails, posts y más con IA. Disponible en editores de texto.', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'icon' => 'dashicons-edit',
                'status' => $is_configured ? 'active' : 'disabled',
                'type' => 'inline',
            ],
            'reply_suggester' => [
                'id' => 'reply_suggester',
                'name' => __('Sugeridor de Respuestas', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'description' => __('Sugiere respuestas automáticas para incidencias y tickets basándose en el contexto.', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'icon' => 'dashicons-testimonial',
                'status' => $is_configured ? 'active' : 'disabled',
                'type' => 'inline',
            ],
            'translator' => [
                'id' => 'translator',
                'name' => __('Traductor de Contenido', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'description' => __('Traduce contenido entre idiomas directamente en los campos de texto.', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'icon' => 'dashicons-translation',
                'status' => $is_configured ? 'active' : 'disabled',
                'type' => 'inline',
            ],
            'weekly_reports' => [
                'id' => 'weekly_reports',
                'name' => __('Reportes Semanales', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'description' => __('Genera resúmenes ejecutivos semanales con métricas, tendencias y recomendaciones.', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'icon' => 'dashicons-chart-area',
                'status' => $is_configured ? 'active' : 'disabled',
                'type' => 'page',
                'url' => admin_url('admin.php?page=flavor-ai-weekly-reports'),
            ],
            'data_analyzer' => [
                'id' => 'data_analyzer',
                'name' => __('Analizador de Datos', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'description' => __('Analiza datos de módulos y genera insights con IA.', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'icon' => 'dashicons-chart-pie',
                'status' => $is_configured ? 'active' : 'disabled',
                'type' => 'page',
                'url' => admin_url('admin.php?page=flavor-ai-data-analyzer'),
            ],
            'setup_assistant' => [
                'id' => 'setup_assistant',
                'name' => __('Asistente de Configuración', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'description' => __('Ayuda a configurar módulos nuevos con guías paso a paso.', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'icon' => 'dashicons-admin-generic',
                'status' => $is_configured ? 'active' : 'disabled',
                'type' => 'widget',
            ],
        ];

        // Añadir generador de datos demo solo en debug
        if (defined('WP_DEBUG') && WP_DEBUG) {
            $tools['demo_generator'] = [
                'id' => 'demo_generator',
                'name' => __('Generador de Datos Demo', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'description' => __('Genera datos de prueba para módulos. Solo disponible en modo desarrollo.', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'icon' => 'dashicons-database-import',
                'status' => 'active',
                'type' => 'page',
                'url' => admin_url('admin.php?page=flavor-ai-demo-generator'),
            ];
        }

        return apply_filters('flavor_ai_tools_available', $tools);
    }
}

// Inicializar
add_action('plugins_loaded', function() {
    Flavor_AI_Tools_Admin::get_instance();
}, 20);

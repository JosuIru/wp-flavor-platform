<?php
/**
 * Módulo de Sector Empresarial
 *
 * @package FlavorChatIA
 */

if (!defined('ABSPATH')) {
    exit;
}

class Flavor_Chat_Empresarial_Module extends Flavor_Chat_Module_Base {

    use Flavor_Module_Admin_Pages_Trait;
    use Flavor_Module_Notifications_Trait;

    protected $module_id = 'empresarial';
    protected $module_name = 'Sector Empresarial';
    protected $module_description = 'Componentes profesionales para empresas: héroes corporativos, servicios, equipo, testimonios, estadísticas y más';
    protected $module_icon = 'dashicons-building';

    /**
     * Constructor
     */
    public function __construct() {
        // Mapear propiedades del módulo al formato base
        $this->id = $this->module_id;
        $this->name = $this->module_name;
        $this->description = $this->module_description;

        parent::__construct();
    }

    /**
     * Verificar si el módulo puede activarse
     */
    public function can_activate() {
        return true; // Sin requisitos especiales
    }

    /**
     * Activar módulo
     */
    public function activate() {
        $this->create_tables();
        return true;
    }

    /**
     * Desactivar módulo
     */
    public function deactivate() {
        return true;
    }

    /**
     * Verifica si el módulo está activo
     */
    public function is_active() {
        return $this->can_activate();
    }

    /**
     * Obtener componentes web del módulo
     */
    public function get_web_components() {
        return [
            'empresarial_hero' => [
                'label' => __('Hero Corporativo', 'flavor-chat-ia'),
                'description' => __('Hero profesional para empresas con diseño elegante', 'flavor-chat-ia'),
                'category' => 'empresarial',
                'icon' => 'dashicons-building',
                'fields' => [
                    'titulo' => [
                        'type' => 'text',
                        'label' => __('Título', 'flavor-chat-ia'),
                        'default' => 'Soluciones Empresariales de Calidad'
                    ],
                    'subtitulo' => [
                        'type' => 'textarea',
                        'label' => __('Subtítulo', 'flavor-chat-ia'),
                        'default' => 'Potencia tu negocio con nuestros servicios profesionales y tecnología de vanguardia'
                    ],
                    'texto_boton_principal' => [
                        'type' => 'text',
                        'label' => __('Texto Botón Principal', 'flavor-chat-ia'),
                        'default' => 'Solicitar Demo'
                    ],
                    'url_boton_principal' => [
                        'type' => 'url',
                        'label' => __('URL Botón Principal', 'flavor-chat-ia'),
                        'default' => '#contacto'
                    ],
                    'texto_boton_secundario' => [
                        'type' => 'text',
                        'label' => __('Texto Botón Secundario', 'flavor-chat-ia'),
                        'default' => 'Ver Servicios'
                    ],
                    'url_boton_secundario' => [
                        'type' => 'url',
                        'label' => __('URL Botón Secundario', 'flavor-chat-ia'),
                        'default' => '#servicios'
                    ],
                    'imagen_fondo' => [
                        'type' => 'image',
                        'label' => __('Imagen de Fondo', 'flavor-chat-ia'),
                        'default' => ''
                    ],
                    'mostrar_video' => [
                        'type' => 'toggle',
                        'label' => __('Mostrar Video', 'flavor-chat-ia'),
                        'default' => false
                    ],
                    'url_video' => [
                        'type' => 'url',
                        'label' => __('URL Video (YouTube/Vimeo)', 'flavor-chat-ia'),
                        'default' => ''
                    ]
                ],
                'template' => 'empresarial/hero'
            ],
            'empresarial_servicios' => [
                'label' => __('Grid de Servicios', 'flavor-chat-ia'),
                'description' => __('Muestra servicios o soluciones en un grid profesional', 'flavor-chat-ia'),
                'category' => 'empresarial',
                'icon' => 'dashicons-grid-view',
                'fields' => [
                    'titulo_seccion' => [
                        'type' => 'text',
                        'label' => __('Título de Sección', 'flavor-chat-ia'),
                        'default' => 'Nuestros Servicios'
                    ],
                    'descripcion_seccion' => [
                        'type' => 'textarea',
                        'label' => __('Descripción de Sección', 'flavor-chat-ia'),
                        'default' => 'Soluciones integrales diseñadas para hacer crecer tu negocio'
                    ],
                    'columnas' => [
                        'type' => 'select',
                        'label' => __('Columnas', 'flavor-chat-ia'),
                        'options' => ['2', '3', '4'],
                        'default' => '3'
                    ],
                    'estilo' => [
                        'type' => 'select',
                        'label' => __('Estilo', 'flavor-chat-ia'),
                        'options' => ['cards', 'minimal', 'bordered'],
                        'default' => 'cards'
                    ],
                    'fuente_datos' => [
                        'type' => 'data_source',
                        'label' => __('Fuente de datos', 'flavor-chat-ia'),
                        'post_types' => ['post'],
                        'items_field' => 'items',
                        'default' => 'manual',
                    ],
                    'items' => [
                        'type' => 'repeater',
                        'label' => __('Servicios', 'flavor-chat-ia'),
                        'fields' => [
                            'titulo' => ['type' => 'text', 'label' => __('Título', 'flavor-chat-ia'), 'default' => ''],
                            'descripcion' => ['type' => 'textarea', 'label' => __('Descripción', 'flavor-chat-ia'), 'default' => ''],
                            'icono' => ['type' => 'text', 'label' => __('Icono (clase dashicons)', 'flavor-chat-ia'), 'default' => ''],
                        ],
                        'default' => [],
                        'max_items' => 12,
                    ],
                ],
                'template' => 'empresarial/servicios-grid'
            ],
            'empresarial_equipo' => [
                'label' => __('Equipo / Staff', 'flavor-chat-ia'),
                'description' => __('Muestra los miembros del equipo con fotos y roles', 'flavor-chat-ia'),
                'category' => 'empresarial',
                'icon' => 'dashicons-groups',
                'fields' => [
                    'titulo_seccion' => [
                        'type' => 'text',
                        'label' => __('Título de Sección', 'flavor-chat-ia'),
                        'default' => 'Nuestro Equipo'
                    ],
                    'descripcion_seccion' => [
                        'type' => 'textarea',
                        'label' => __('Descripción de Sección', 'flavor-chat-ia'),
                        'default' => 'Profesionales comprometidos con tu éxito'
                    ],
                    'layout' => [
                        'type' => 'select',
                        'label' => __('Layout', 'flavor-chat-ia'),
                        'options' => ['grid', 'slider', 'list'],
                        'default' => 'grid'
                    ],
                    'columnas' => [
                        'type' => 'select',
                        'label' => __('Columnas (Grid)', 'flavor-chat-ia'),
                        'options' => ['2', '3', '4'],
                        'default' => '4'
                    ],
                    'mostrar_redes_sociales' => [
                        'type' => 'toggle',
                        'label' => __('Mostrar Redes Sociales', 'flavor-chat-ia'),
                        'default' => true
                    ],
                    'items' => [
                        'type' => 'repeater',
                        'label' => __('Miembros del equipo', 'flavor-chat-ia'),
                        'fields' => [
                            'nombre' => ['type' => 'text', 'label' => __('Nombre', 'flavor-chat-ia'), 'default' => ''],
                            'puesto' => ['type' => 'text', 'label' => __('Puesto', 'flavor-chat-ia'), 'default' => ''],
                            'bio' => ['type' => 'textarea', 'label' => __('Biografía', 'flavor-chat-ia'), 'default' => ''],
                            'foto' => ['type' => 'image', 'label' => __('Foto', 'flavor-chat-ia'), 'default' => ''],
                            'linkedin' => ['type' => 'url', 'label' => __('LinkedIn URL', 'flavor-chat-ia'), 'default' => ''],
                            'twitter' => ['type' => 'url', 'label' => __('Twitter URL', 'flavor-chat-ia'), 'default' => ''],
                            'email' => ['type' => 'text', 'label' => __('Email', 'flavor-chat-ia'), 'default' => ''],
                        ],
                        'default' => [],
                        'max_items' => 12,
                    ],
                ],
                'template' => 'empresarial/equipo'
            ],
            'empresarial_testimonios' => [
                'label' => __('Testimonios', 'flavor-chat-ia'),
                'description' => __('Muestra testimonios de clientes satisfechos', 'flavor-chat-ia'),
                'category' => 'empresarial',
                'icon' => 'dashicons-format-quote',
                'fields' => [
                    'titulo_seccion' => [
                        'type' => 'text',
                        'label' => __('Título de Sección', 'flavor-chat-ia'),
                        'default' => 'Lo Que Dicen Nuestros Clientes'
                    ],
                    'layout' => [
                        'type' => 'select',
                        'label' => __('Layout', 'flavor-chat-ia'),
                        'options' => ['carousel', 'grid', 'masonry'],
                        'default' => 'carousel'
                    ],
                    'mostrar_foto' => [
                        'type' => 'toggle',
                        'label' => __('Mostrar Foto del Cliente', 'flavor-chat-ia'),
                        'default' => true
                    ],
                    'mostrar_empresa' => [
                        'type' => 'toggle',
                        'label' => __('Mostrar Empresa', 'flavor-chat-ia'),
                        'default' => true
                    ],
                    'items' => [
                        'type' => 'repeater',
                        'label' => __('Testimonios', 'flavor-chat-ia'),
                        'fields' => [
                            'nombre' => ['type' => 'text', 'label' => __('Nombre', 'flavor-chat-ia'), 'default' => ''],
                            'puesto' => ['type' => 'text', 'label' => __('Puesto', 'flavor-chat-ia'), 'default' => ''],
                            'empresa' => ['type' => 'text', 'label' => __('Empresa', 'flavor-chat-ia'), 'default' => ''],
                            'testimonio' => ['type' => 'textarea', 'label' => __('Testimonio', 'flavor-chat-ia'), 'default' => ''],
                            'rating' => ['type' => 'number', 'label' => __('Rating (1-5)', 'flavor-chat-ia'), 'default' => 5],
                        ],
                        'default' => [],
                        'max_items' => 12,
                    ],
                ],
                'template' => 'empresarial/testimonios'
            ],
            'empresarial_stats' => [
                'label' => __('Estadísticas / Métricas', 'flavor-chat-ia'),
                'description' => __('Muestra números y logros importantes de la empresa', 'flavor-chat-ia'),
                'category' => 'empresarial',
                'icon' => 'dashicons-chart-bar',
                'fields' => [
                    'titulo_seccion' => [
                        'type' => 'text',
                        'label' => __('Título de Sección', 'flavor-chat-ia'),
                        'default' => 'Resultados que Hablan por Sí Solos'
                    ],
                    'estilo' => [
                        'type' => 'select',
                        'label' => __('Estilo', 'flavor-chat-ia'),
                        'options' => ['minimal', 'cards', 'highlighted'],
                        'default' => 'highlighted'
                    ],
                    'items' => [
                        'type' => 'repeater',
                        'label' => __('Estadísticas', 'flavor-chat-ia'),
                        'fields' => [
                            'numero' => ['type' => 'text', 'label' => __('Número / Cifra', 'flavor-chat-ia'), 'default' => ''],
                            'texto' => ['type' => 'text', 'label' => __('Texto descriptivo', 'flavor-chat-ia'), 'default' => ''],
                        ],
                        'default' => [],
                        'max_items' => 8,
                    ],
                ],
                'template' => 'empresarial/stats'
            ],
            'empresarial_contacto' => [
                'label' => __('Formulario de Contacto', 'flavor-chat-ia'),
                'description' => __('Formulario profesional de contacto para empresas', 'flavor-chat-ia'),
                'category' => 'empresarial',
                'icon' => 'dashicons-email',
                'fields' => [
                    'titulo_seccion' => [
                        'type' => 'text',
                        'label' => __('Título de Sección', 'flavor-chat-ia'),
                        'default' => 'Contacta con Nosotros'
                    ],
                    'descripcion_seccion' => [
                        'type' => 'textarea',
                        'label' => __('Descripción de Sección', 'flavor-chat-ia'),
                        'default' => 'Estamos aquí para ayudarte. Envíanos tu consulta y te responderemos pronto.'
                    ],
                    'layout' => [
                        'type' => 'select',
                        'label' => __('Layout', 'flavor-chat-ia'),
                        'options' => ['simple', 'con_mapa', 'dos_columnas'],
                        'default' => 'dos_columnas'
                    ],
                    'email_destino' => [
                        'type' => 'text',
                        'label' => __('Email de Destino', 'flavor-chat-ia'),
                        'default' => get_option('admin_email')
                    ],
                    'mostrar_telefono' => [
                        'type' => 'toggle',
                        'label' => __('Mostrar Teléfono', 'flavor-chat-ia'),
                        'default' => true
                    ],
                    'telefono' => [
                        'type' => 'text',
                        'label' => __('Teléfono', 'flavor-chat-ia'),
                        'default' => '+34 900 000 000'
                    ],
                    'mostrar_direccion' => [
                        'type' => 'toggle',
                        'label' => __('Mostrar Dirección', 'flavor-chat-ia'),
                        'default' => true
                    ],
                    'direccion' => [
                        'type' => 'textarea',
                        'label' => __('Dirección', 'flavor-chat-ia'),
                        'default' => 'Calle Principal 123, 28001 Madrid, España'
                    ]
                ],
                'template' => 'empresarial/contacto'
            ],
            'empresarial_pricing' => [
                'label' => __('Tabla de Precios', 'flavor-chat-ia'),
                'description' => __('Muestra planes y precios de forma clara y atractiva', 'flavor-chat-ia'),
                'category' => 'empresarial',
                'icon' => 'dashicons-money-alt',
                'fields' => [
                    'titulo_seccion' => [
                        'type' => 'text',
                        'label' => __('Título de Sección', 'flavor-chat-ia'),
                        'default' => 'Planes y Precios'
                    ],
                    'descripcion_seccion' => [
                        'type' => 'textarea',
                        'label' => __('Descripción de Sección', 'flavor-chat-ia'),
                        'default' => 'Elige el plan perfecto para tu negocio'
                    ],
                    'periodo' => [
                        'type' => 'select',
                        'label' => __('Periodo de Facturación', 'flavor-chat-ia'),
                        'options' => ['mensual', 'anual', 'ambos'],
                        'default' => 'mensual'
                    ],
                    'items' => [
                        'type' => 'repeater',
                        'label' => __('Planes', 'flavor-chat-ia'),
                        'fields' => [
                            'nombre' => ['type' => 'text', 'label' => __('Nombre del plan', 'flavor-chat-ia'), 'default' => ''],
                            'descripcion' => ['type' => 'textarea', 'label' => __('Descripción', 'flavor-chat-ia'), 'default' => ''],
                            'precio_mensual' => ['type' => 'text', 'label' => __('Precio mensual', 'flavor-chat-ia'), 'default' => '0'],
                            'precio_anual' => ['type' => 'text', 'label' => __('Precio anual', 'flavor-chat-ia'), 'default' => '0'],
                            'caracteristicas' => ['type' => 'textarea', 'label' => __('Características (una por línea)', 'flavor-chat-ia'), 'default' => ''],
                            'destacar' => ['type' => 'toggle', 'label' => __('Destacar este plan', 'flavor-chat-ia'), 'default' => false],
                            'badge' => ['type' => 'text', 'label' => __('Badge (ej: Más Popular)', 'flavor-chat-ia'), 'default' => ''],
                        ],
                        'default' => [],
                        'max_items' => 6,
                    ],
                ],
                'template' => 'empresarial/pricing'
            ],
            'empresarial_portfolio' => [
                'label' => __('Portfolio / Casos de Éxito', 'flavor-chat-ia'),
                'description' => __('Muestra proyectos o casos de éxito de la empresa', 'flavor-chat-ia'),
                'category' => 'empresarial',
                'icon' => 'dashicons-portfolio',
                'fields' => [
                    'titulo_seccion' => [
                        'type' => 'text',
                        'label' => __('Título de Sección', 'flavor-chat-ia'),
                        'default' => 'Nuestros Casos de Éxito'
                    ],
                    'descripcion_seccion' => [
                        'type' => 'textarea',
                        'label' => __('Descripción de Sección', 'flavor-chat-ia'),
                        'default' => 'Proyectos que transformaron negocios'
                    ],
                    'layout' => [
                        'type' => 'select',
                        'label' => __('Layout', 'flavor-chat-ia'),
                        'options' => ['grid', 'masonry', 'carousel'],
                        'default' => 'masonry'
                    ],
                    'columnas' => [
                        'type' => 'select',
                        'label' => __('Columnas', 'flavor-chat-ia'),
                        'options' => ['2', '3', '4'],
                        'default' => '3'
                    ],
                    'mostrar_filtros' => [
                        'type' => 'toggle',
                        'label' => __('Mostrar Filtros por Categoría', 'flavor-chat-ia'),
                        'default' => true
                    ],
                    'fuente_datos' => [
                        'type' => 'data_source',
                        'label' => __('Fuente de datos', 'flavor-chat-ia'),
                        'post_types' => ['post'],
                        'items_field' => 'items',
                        'default' => 'manual',
                    ],
                    'items' => [
                        'type' => 'repeater',
                        'label' => __('Proyectos', 'flavor-chat-ia'),
                        'fields' => [
                            'titulo' => ['type' => 'text', 'label' => __('Título', 'flavor-chat-ia'), 'default' => ''],
                            'cliente' => ['type' => 'text', 'label' => __('Cliente', 'flavor-chat-ia'), 'default' => ''],
                            'categoria' => ['type' => 'text', 'label' => __('Categoría', 'flavor-chat-ia'), 'default' => ''],
                            'descripcion' => ['type' => 'textarea', 'label' => __('Descripción', 'flavor-chat-ia'), 'default' => ''],
                            'resultados' => ['type' => 'text', 'label' => __('Resultados destacados', 'flavor-chat-ia'), 'default' => ''],
                            'imagen' => ['type' => 'image', 'label' => __('Imagen', 'flavor-chat-ia'), 'default' => ''],
                        ],
                        'default' => [],
                        'max_items' => 12,
                    ],
                ],
                'template' => 'empresarial/portfolio'
            ]
        ];
    }

    /**
     * Inicializar el módulo
     */
    public function init() {
        add_action('init', [$this, 'maybe_create_pages']);
        add_action('init', [$this, 'maybe_create_tables']);
        add_action('init', [$this, 'register_shortcodes']);
        add_action('wp_ajax_empresarial_contacto', [$this, 'ajax_contacto_form']);
        add_action('wp_ajax_nopriv_empresarial_contacto', [$this, 'ajax_contacto_form']);
        add_action('rest_api_init', [$this, 'register_rest_routes']);
        add_action('wp_enqueue_scripts', [$this, 'enqueue_frontend_assets']);

        // Registrar en el panel de administración unificado
        $this->registrar_en_panel_unificado();
    }

    /**
     * Registrar shortcodes del módulo
     */
    public function register_shortcodes() {
        add_shortcode('empresarial_servicios', [$this, 'shortcode_servicios']);
        add_shortcode('empresarial_equipo', [$this, 'shortcode_equipo']);
        add_shortcode('empresarial_testimonios', [$this, 'shortcode_testimonios']);
        add_shortcode('empresarial_contacto', [$this, 'shortcode_contacto']);
        add_shortcode('empresarial_portfolio', [$this, 'shortcode_portfolio']);
    }

    /**
     * Encolar assets frontend
     */
    public function enqueue_frontend_assets() {
        if (!$this->should_load_assets()) {
            return;
        }

        $plugin_url = plugin_dir_url(dirname(dirname(dirname(__FILE__))));
        $version = defined('FLAVOR_CHAT_VERSION') ? FLAVOR_CHAT_VERSION : '1.0.0';

        wp_enqueue_style(
            'flavor-empresarial',
            $plugin_url . 'includes/modules/empresarial/assets/css/empresarial.css',
            [],
            $version
        );

        wp_enqueue_script(
            'flavor-empresarial',
            $plugin_url . 'includes/modules/empresarial/assets/js/empresarial.js',
            ['jquery'],
            $version,
            true
        );

        wp_localize_script('flavor-empresarial', 'flavorEmpresarialConfig', [
            'ajaxUrl' => admin_url('admin-ajax.php'),
            'nonce'   => wp_create_nonce('empresarial_contacto_nonce'),
            'strings' => [
                'enviando'    => __('Enviando...', 'flavor-chat-ia'),
                'enviar'      => __('Enviar mensaje', 'flavor-chat-ia'),
                'error'       => __('Error al enviar el mensaje', 'flavor-chat-ia'),
                'camposRequeridos' => __('Por favor, completa todos los campos obligatorios', 'flavor-chat-ia'),
            ],
        ]);
    }

    /**
     * Verifica si se deben cargar los assets
     *
     * @return bool
     */
    private function should_load_assets() {
        global $post;

        if (!$post) {
            return false;
        }

        // Cargar en páginas del módulo empresarial
        if (strpos($post->post_name, 'empresarial') !== false) {
            return true;
        }

        // Cargar si hay shortcodes del módulo
        $shortcodes_empresarial = ['empresarial_servicios', 'empresarial_equipo', 'empresarial_testimonios', 'empresarial_contacto', 'empresarial_portfolio'];
        foreach ($shortcodes_empresarial as $shortcode) {
            if (has_shortcode($post->post_content, $shortcode)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Shortcode: Servicios
     *
     * @param array $atts Atributos del shortcode
     * @return string
     */
    public function shortcode_servicios($atts) {
        $atts = shortcode_atts([
            'titulo'      => __('Nuestros Servicios', 'flavor-chat-ia'),
            'descripcion' => __('Soluciones integrales diseñadas para hacer crecer tu negocio', 'flavor-chat-ia'),
            'columnas'    => 3,
            'estilo'      => 'cards',
        ], $atts, 'empresarial_servicios');

        ob_start();
        include dirname(__FILE__) . '/templates/servicios.php';
        return ob_get_clean();
    }

    /**
     * Shortcode: Equipo
     *
     * @param array $atts Atributos del shortcode
     * @return string
     */
    public function shortcode_equipo($atts) {
        $atts = shortcode_atts([
            'titulo'      => __('Nuestro Equipo', 'flavor-chat-ia'),
            'descripcion' => __('Profesionales comprometidos con tu éxito', 'flavor-chat-ia'),
            'layout'      => 'grid',
            'columnas'    => 4,
        ], $atts, 'empresarial_equipo');

        ob_start();
        include dirname(__FILE__) . '/templates/equipo.php';
        return ob_get_clean();
    }

    /**
     * Shortcode: Testimonios
     *
     * @param array $atts Atributos del shortcode
     * @return string
     */
    public function shortcode_testimonios($atts) {
        $atts = shortcode_atts([
            'titulo' => __('Lo Que Dicen Nuestros Clientes', 'flavor-chat-ia'),
            'layout' => 'carousel',
        ], $atts, 'empresarial_testimonios');

        ob_start();
        include dirname(__FILE__) . '/templates/testimonios.php';
        return ob_get_clean();
    }

    /**
     * Shortcode: Formulario de Contacto
     *
     * @param array $atts Atributos del shortcode
     * @return string
     */
    public function shortcode_contacto($atts) {
        $atts = shortcode_atts([
            'titulo'      => __('Contacta con Nosotros', 'flavor-chat-ia'),
            'descripcion' => __('Estamos aquí para ayudarte. Envíanos tu consulta y te responderemos pronto.', 'flavor-chat-ia'),
            'layout'      => 'dos_columnas',
            'mostrar_info' => true,
        ], $atts, 'empresarial_contacto');

        ob_start();
        include dirname(__FILE__) . '/templates/contacto.php';
        return ob_get_clean();
    }

    /**
     * Shortcode: Portfolio / Casos de Éxito
     *
     * @param array $atts Atributos del shortcode
     * @return string
     */
    public function shortcode_portfolio($atts) {
        $atts = shortcode_atts([
            'titulo'      => __('Nuestros Casos de Éxito', 'flavor-chat-ia'),
            'descripcion' => __('Proyectos que transformaron negocios', 'flavor-chat-ia'),
            'layout'      => 'masonry',
            'columnas'    => 3,
            'limite'      => 6,
        ], $atts, 'empresarial_portfolio');

        ob_start();
        include dirname(__FILE__) . '/templates/portfolio.php';
        return ob_get_clean();
    }

    /**
     * Registrar rutas REST API
     */
    public function register_rest_routes() {
        $namespace = 'flavor/v1';

        // GET /flavor/v1/empresas - Listar empresas
        register_rest_route($namespace, '/empresas', [
            'methods'             => \WP_REST_Server::READABLE,
            'callback'            => [$this, 'api_listar_empresas'],
            'permission_callback' => [$this, 'api_check_read_permission'],
            'args'                => [
                'estado' => [
                    'type'              => 'string',
                    'sanitize_callback' => 'sanitize_text_field',
                    'enum'              => ['activa', 'inactiva', 'pendiente'],
                ],
                'categoria' => [
                    'type'              => 'string',
                    'sanitize_callback' => 'sanitize_text_field',
                ],
                'per_page' => [
                    'type'              => 'integer',
                    'default'           => 20,
                    'sanitize_callback' => 'absint',
                    'minimum'           => 1,
                    'maximum'           => 100,
                ],
                'page' => [
                    'type'              => 'integer',
                    'default'           => 1,
                    'sanitize_callback' => 'absint',
                    'minimum'           => 1,
                ],
            ],
        ]);

        // GET /flavor/v1/empresas/{id} - Obtener una empresa
        register_rest_route($namespace, '/empresas/(?P<id>\d+)', [
            'methods'             => \WP_REST_Server::READABLE,
            'callback'            => [$this, 'api_obtener_empresa'],
            'permission_callback' => [$this, 'api_check_read_permission'],
            'args'                => [
                'id' => [
                    'type'              => 'integer',
                    'required'          => true,
                    'sanitize_callback' => 'absint',
                    'validate_callback' => function($param) {
                        return is_numeric($param) && $param > 0;
                    },
                ],
            ],
        ]);

        // GET /flavor/v1/empresas/{id}/servicios - Servicios de la empresa
        register_rest_route($namespace, '/empresas/(?P<id>\d+)/servicios', [
            'methods'             => \WP_REST_Server::READABLE,
            'callback'            => [$this, 'api_obtener_servicios_empresa'],
            'permission_callback' => [$this, 'api_check_read_permission'],
            'args'                => [
                'id' => [
                    'type'              => 'integer',
                    'required'          => true,
                    'sanitize_callback' => 'absint',
                    'validate_callback' => function($param) {
                        return is_numeric($param) && $param > 0;
                    },
                ],
            ],
        ]);

        // GET /flavor/v1/empresas/categorias - Categorías de empresas
        register_rest_route($namespace, '/empresas/categorias', [
            'methods'             => \WP_REST_Server::READABLE,
            'callback'            => [$this, 'api_obtener_categorias'],
            'permission_callback' => '__return_true',
        ]);

        // GET /flavor/v1/empresas/buscar - Buscar empresas
        register_rest_route($namespace, '/empresas/buscar', [
            'methods'             => \WP_REST_Server::READABLE,
            'callback'            => [$this, 'api_buscar_empresas'],
            'permission_callback' => '__return_true',
            'args'                => [
                'termino' => [
                    'type'              => 'string',
                    'required'          => true,
                    'sanitize_callback' => 'sanitize_text_field',
                    'validate_callback' => function($param) {
                        return !empty($param) && strlen($param) >= 2;
                    },
                ],
                'categoria' => [
                    'type'              => 'string',
                    'sanitize_callback' => 'sanitize_text_field',
                ],
                'per_page' => [
                    'type'              => 'integer',
                    'default'           => 20,
                    'sanitize_callback' => 'absint',
                    'minimum'           => 1,
                    'maximum'           => 100,
                ],
                'page' => [
                    'type'              => 'integer',
                    'default'           => 1,
                    'sanitize_callback' => 'absint',
                    'minimum'           => 1,
                ],
            ],
        ]);
    }

    /**
     * Verificar permisos de lectura para la API
     *
     * @param \WP_REST_Request $request Objeto de solicitud REST
     * @return bool
     */
    public function api_check_read_permission($request) {
        // Lectura pública por defecto, o restringida según configuración
        $configuracion_api = $this->get_rest_config();
        if (!empty($configuracion_api['require_auth'])) {
            return is_user_logged_in();
        }
        return true;
    }

    /**
     * API: Listar empresas
     *
     * @param \WP_REST_Request $request Objeto de solicitud REST
     * @return \WP_REST_Response
     */
    public function api_listar_empresas($request) {
        global $wpdb;
        $tabla_proyectos = $wpdb->prefix . 'flavor_empresarial_proyectos';

        $estado_filtro     = $request->get_param('estado');
        $categoria_filtro  = $request->get_param('categoria');
        $limite_resultados = $request->get_param('per_page') ?: 20;
        $pagina_actual     = $request->get_param('page') ?: 1;
        $offset_consulta   = ($pagina_actual - 1) * $limite_resultados;

        $condiciones_where = '1=1';
        $valores_parametros = [];

        if (!empty($estado_filtro)) {
            $condiciones_where .= ' AND estado = %s';
            $valores_parametros[] = $estado_filtro;
        }

        // Obtener empresas únicas desde proyectos
        $consulta_total = "SELECT COUNT(DISTINCT cliente_nombre) FROM $tabla_proyectos WHERE $condiciones_where";
        if (!empty($valores_parametros)) {
            $total_empresas = (int) $wpdb->get_var($wpdb->prepare($consulta_total, $valores_parametros));
        } else {
            $total_empresas = (int) $wpdb->get_var($consulta_total);
        }

        $consulta_empresas = "SELECT
                                cliente_nombre as nombre,
                                cliente_email as email,
                                COUNT(*) as total_proyectos,
                                SUM(presupuesto) as presupuesto_total,
                                MAX(created_at) as ultimo_proyecto
                              FROM $tabla_proyectos
                              WHERE $condiciones_where
                              GROUP BY cliente_nombre, cliente_email
                              ORDER BY ultimo_proyecto DESC
                              LIMIT %d OFFSET %d";

        $parametros_completos = array_merge($valores_parametros, [$limite_resultados, $offset_consulta]);
        $lista_empresas = $wpdb->get_results($wpdb->prepare($consulta_empresas, $parametros_completos), ARRAY_A);

        // Formatear datos de respuesta
        $empresas_formateadas = [];
        foreach ($lista_empresas ?: [] as $indice => $empresa) {
            $empresas_formateadas[] = [
                'id'               => $indice + 1 + $offset_consulta,
                'nombre'           => $empresa['nombre'],
                'email'            => $empresa['email'],
                'total_proyectos'  => (int) $empresa['total_proyectos'],
                'presupuesto_total' => $this->format_price((float) $empresa['presupuesto_total']),
                'ultimo_proyecto'  => $empresa['ultimo_proyecto'],
            ];
        }

        $total_paginas = ceil($total_empresas / $limite_resultados);

        return new \WP_REST_Response([
            'success' => true,
            'data'    => $empresas_formateadas,
            'meta'    => [
                'total'       => $total_empresas,
                'page'        => $pagina_actual,
                'per_page'    => $limite_resultados,
                'total_pages' => $total_paginas,
            ],
        ], 200);
    }

    /**
     * API: Obtener una empresa por ID
     *
     * @param \WP_REST_Request $request Objeto de solicitud REST
     * @return \WP_REST_Response
     */
    public function api_obtener_empresa($request) {
        global $wpdb;
        $tabla_proyectos = $wpdb->prefix . 'flavor_empresarial_proyectos';

        $empresa_id = $request->get_param('id');

        // Obtener empresas únicas ordenadas por último proyecto
        $lista_empresas = $wpdb->get_results(
            "SELECT
                cliente_nombre as nombre,
                cliente_email as email,
                COUNT(*) as total_proyectos,
                SUM(presupuesto) as presupuesto_total,
                SUM(CASE WHEN estado = 'completado' THEN presupuesto ELSE 0 END) as presupuesto_completado,
                SUM(CASE WHEN estado IN ('aprobado', 'en_curso') THEN presupuesto ELSE 0 END) as presupuesto_activo,
                MAX(created_at) as ultimo_proyecto,
                MIN(created_at) as primer_proyecto
             FROM $tabla_proyectos
             GROUP BY cliente_nombre, cliente_email
             ORDER BY ultimo_proyecto DESC",
            ARRAY_A
        );

        $indice_empresa = $empresa_id - 1;
        if (!isset($lista_empresas[$indice_empresa])) {
            return new \WP_REST_Response([
                'success' => false,
                'message' => __('Empresa no encontrada.', 'flavor-chat-ia'),
            ], 404);
        }

        $datos_empresa = $lista_empresas[$indice_empresa];

        // Obtener proyectos de esta empresa
        $proyectos_empresa = $wpdb->get_results(
            $wpdb->prepare(
                "SELECT id, titulo, estado, presupuesto, progreso, fecha_inicio, fecha_entrega, created_at
                 FROM $tabla_proyectos
                 WHERE cliente_nombre = %s
                 ORDER BY created_at DESC",
                $datos_empresa['nombre']
            ),
            ARRAY_A
        );

        // Calcular estadísticas
        $estadisticas_empresa = [
            'proyectos_activos'    => 0,
            'proyectos_completados' => 0,
            'proyectos_pendientes' => 0,
            'progreso_promedio'    => 0,
        ];

        $suma_progreso = 0;
        $cantidad_activos = 0;

        foreach ($proyectos_empresa ?: [] as $proyecto) {
            if (in_array($proyecto['estado'], ['aprobado', 'en_curso'], true)) {
                $estadisticas_empresa['proyectos_activos']++;
                $suma_progreso += (int) $proyecto['progreso'];
                $cantidad_activos++;
            } elseif ($proyecto['estado'] === 'completado') {
                $estadisticas_empresa['proyectos_completados']++;
            } elseif ($proyecto['estado'] === 'propuesta') {
                $estadisticas_empresa['proyectos_pendientes']++;
            }
        }

        if ($cantidad_activos > 0) {
            $estadisticas_empresa['progreso_promedio'] = round($suma_progreso / $cantidad_activos, 1);
        }

        return new \WP_REST_Response([
            'success' => true,
            'data'    => [
                'id'                     => $empresa_id,
                'nombre'                 => $datos_empresa['nombre'],
                'email'                  => $datos_empresa['email'],
                'total_proyectos'        => (int) $datos_empresa['total_proyectos'],
                'presupuesto_total'      => $this->format_price((float) $datos_empresa['presupuesto_total']),
                'presupuesto_completado' => $this->format_price((float) $datos_empresa['presupuesto_completado']),
                'presupuesto_activo'     => $this->format_price((float) $datos_empresa['presupuesto_activo']),
                'primer_proyecto'        => $datos_empresa['primer_proyecto'],
                'ultimo_proyecto'        => $datos_empresa['ultimo_proyecto'],
                'estadisticas'           => $estadisticas_empresa,
                'proyectos'              => $proyectos_empresa ?: [],
            ],
        ], 200);
    }

    /**
     * API: Obtener servicios de una empresa
     *
     * @param \WP_REST_Request $request Objeto de solicitud REST
     * @return \WP_REST_Response
     */
    public function api_obtener_servicios_empresa($request) {
        global $wpdb;
        $tabla_proyectos = $wpdb->prefix . 'flavor_empresarial_proyectos';

        $empresa_id = $request->get_param('id');

        // Obtener empresas únicas ordenadas por último proyecto
        $lista_empresas = $wpdb->get_results(
            "SELECT cliente_nombre as nombre FROM $tabla_proyectos GROUP BY cliente_nombre ORDER BY MAX(created_at) DESC",
            ARRAY_A
        );

        $indice_empresa = $empresa_id - 1;
        if (!isset($lista_empresas[$indice_empresa])) {
            return new \WP_REST_Response([
                'success' => false,
                'message' => __('Empresa no encontrada.', 'flavor-chat-ia'),
            ], 404);
        }

        $nombre_empresa = $lista_empresas[$indice_empresa]['nombre'];

        // Obtener proyectos como "servicios contratados"
        $servicios_empresa = $wpdb->get_results(
            $wpdb->prepare(
                "SELECT
                    id,
                    titulo as nombre,
                    descripcion,
                    estado,
                    presupuesto,
                    progreso,
                    fecha_inicio,
                    fecha_entrega,
                    created_at
                 FROM $tabla_proyectos
                 WHERE cliente_nombre = %s
                 ORDER BY created_at DESC",
                $nombre_empresa
            ),
            ARRAY_A
        );

        // Formatear servicios
        $servicios_formateados = [];
        foreach ($servicios_empresa ?: [] as $servicio) {
            $dias_restantes = null;
            if (!empty($servicio['fecha_entrega'])) {
                $fecha_entrega_obj = date_create($servicio['fecha_entrega']);
                $fecha_hoy_obj     = date_create(current_time('Y-m-d'));
                if ($fecha_entrega_obj && $fecha_hoy_obj) {
                    $diferencia_fechas = date_diff($fecha_hoy_obj, $fecha_entrega_obj);
                    $dias_restantes = (int) $diferencia_fechas->format('%R%a');
                }
            }

            $servicios_formateados[] = [
                'id'              => (int) $servicio['id'],
                'nombre'          => $servicio['nombre'],
                'descripcion'     => $servicio['descripcion'],
                'estado'          => $servicio['estado'],
                'presupuesto'     => $this->format_price((float) $servicio['presupuesto']),
                'progreso'        => (int) $servicio['progreso'],
                'fecha_inicio'    => $servicio['fecha_inicio'],
                'fecha_entrega'   => $servicio['fecha_entrega'],
                'dias_restantes'  => $dias_restantes,
                'created_at'      => $servicio['created_at'],
            ];
        }

        return new \WP_REST_Response([
            'success' => true,
            'data'    => [
                'empresa_id'   => $empresa_id,
                'empresa'      => $nombre_empresa,
                'servicios'    => $servicios_formateados,
                'total'        => count($servicios_formateados),
            ],
        ], 200);
    }

    /**
     * API: Obtener categorías de empresas
     *
     * @param \WP_REST_Request $request Objeto de solicitud REST
     * @return \WP_REST_Response
     */
    public function api_obtener_categorias($request) {
        global $wpdb;
        $tabla_proyectos = $wpdb->prefix . 'flavor_empresarial_proyectos';

        // Obtener estadísticas por estado de proyecto como "categorías"
        $categorias_estado = $wpdb->get_results(
            "SELECT
                estado as nombre,
                COUNT(DISTINCT cliente_nombre) as total_empresas,
                COUNT(*) as total_proyectos,
                SUM(presupuesto) as presupuesto_total
             FROM $tabla_proyectos
             GROUP BY estado
             ORDER BY total_empresas DESC",
            ARRAY_A
        );

        // Formatear categorías
        $categorias_formateadas = [];
        $etiquetas_estado = [
            'propuesta'  => __('En Propuesta', 'flavor-chat-ia'),
            'aprobado'   => __('Aprobados', 'flavor-chat-ia'),
            'en_curso'   => __('En Curso', 'flavor-chat-ia'),
            'completado' => __('Completados', 'flavor-chat-ia'),
            'cancelado'  => __('Cancelados', 'flavor-chat-ia'),
        ];

        foreach ($categorias_estado ?: [] as $categoria) {
            $categorias_formateadas[] = [
                'slug'             => $categoria['nombre'],
                'nombre'           => $etiquetas_estado[$categoria['nombre']] ?? ucfirst($categoria['nombre']),
                'total_empresas'   => (int) $categoria['total_empresas'],
                'total_proyectos'  => (int) $categoria['total_proyectos'],
                'presupuesto_total' => $this->format_price((float) $categoria['presupuesto_total']),
            ];
        }

        return new \WP_REST_Response([
            'success' => true,
            'data'    => $categorias_formateadas,
        ], 200);
    }

    /**
     * API: Buscar empresas
     *
     * @param \WP_REST_Request $request Objeto de solicitud REST
     * @return \WP_REST_Response
     */
    public function api_buscar_empresas($request) {
        global $wpdb;
        $tabla_proyectos = $wpdb->prefix . 'flavor_empresarial_proyectos';

        $termino_busqueda  = $request->get_param('termino');
        $categoria_filtro  = $request->get_param('categoria');
        $limite_resultados = $request->get_param('per_page') ?: 20;
        $pagina_actual     = $request->get_param('page') ?: 1;
        $offset_consulta   = ($pagina_actual - 1) * $limite_resultados;

        $patron_busqueda = '%' . $wpdb->esc_like($termino_busqueda) . '%';

        $condiciones_where = "(cliente_nombre LIKE %s OR cliente_email LIKE %s OR titulo LIKE %s OR descripcion LIKE %s)";
        $valores_parametros = [$patron_busqueda, $patron_busqueda, $patron_busqueda, $patron_busqueda];

        if (!empty($categoria_filtro)) {
            $condiciones_where .= ' AND estado = %s';
            $valores_parametros[] = $categoria_filtro;
        }

        // Contar resultados únicos por empresa
        $consulta_total = "SELECT COUNT(DISTINCT cliente_nombre) FROM $tabla_proyectos WHERE $condiciones_where";
        $total_resultados = (int) $wpdb->get_var($wpdb->prepare($consulta_total, $valores_parametros));

        // Buscar empresas
        $consulta_busqueda = "SELECT
                                cliente_nombre as nombre,
                                cliente_email as email,
                                COUNT(*) as total_proyectos,
                                SUM(presupuesto) as presupuesto_total,
                                MAX(created_at) as ultimo_proyecto
                              FROM $tabla_proyectos
                              WHERE $condiciones_where
                              GROUP BY cliente_nombre, cliente_email
                              ORDER BY ultimo_proyecto DESC
                              LIMIT %d OFFSET %d";

        $parametros_completos = array_merge($valores_parametros, [$limite_resultados, $offset_consulta]);
        $empresas_encontradas = $wpdb->get_results($wpdb->prepare($consulta_busqueda, $parametros_completos), ARRAY_A);

        // Formatear resultados
        $resultados_formateados = [];
        foreach ($empresas_encontradas ?: [] as $indice => $empresa) {
            $resultados_formateados[] = [
                'id'               => $indice + 1 + $offset_consulta,
                'nombre'           => $empresa['nombre'],
                'email'            => $empresa['email'],
                'total_proyectos'  => (int) $empresa['total_proyectos'],
                'presupuesto_total' => $this->format_price((float) $empresa['presupuesto_total']),
                'ultimo_proyecto'  => $empresa['ultimo_proyecto'],
            ];
        }

        $total_paginas = ceil($total_resultados / $limite_resultados);

        return new \WP_REST_Response([
            'success' => true,
            'data'    => $resultados_formateados,
            'meta'    => [
                'termino'     => $termino_busqueda,
                'total'       => $total_resultados,
                'page'        => $pagina_actual,
                'per_page'    => $limite_resultados,
                'total_pages' => $total_paginas,
            ],
        ], 200);
    }

    /**
     * Configuración para el panel de administración unificado
     *
     * @return array
     */
    protected function get_admin_config() {
        return [
            'id'         => $this->module_id,
            'label'      => $this->module_name,
            'icon'       => 'dashicons-building',
            'capability' => 'manage_options',
            'categoria'  => 'economia',
            'paginas'    => [
                [
                    'slug'     => 'flavor-empresarial-dashboard',
                    'titulo'   => __('Dashboard', 'flavor-chat-ia'),
                    'callback' => [$this, 'render_admin_dashboard'],
                    'badge'    => [$this, 'contar_contactos_nuevos'],
                ],
                [
                    'slug'     => 'flavor-empresarial-empresas',
                    'titulo'   => __('Empresas', 'flavor-chat-ia'),
                    'callback' => [$this, 'render_admin_empresas'],
                ],
                [
                    'slug'     => 'flavor-empresarial-contratos',
                    'titulo'   => __('Contratos', 'flavor-chat-ia'),
                    'callback' => [$this, 'render_admin_contratos'],
                ],
            ],
            'dashboard_widget' => [$this, 'render_dashboard_widget'],
            'estadisticas'     => [$this, 'get_estadisticas_resumen'],
        ];
    }

    /**
     * Cuenta los contactos nuevos para mostrar en el badge
     *
     * @return int
     */
    public function contar_contactos_nuevos() {
        // Verificar que el módulo esté activo
        if (!$this->can_activate()) {
            return 0;
        }

        global $wpdb;
        $tabla_contactos = $wpdb->prefix . 'flavor_empresarial_contactos';

        if (!Flavor_Chat_Helpers::tabla_existe($tabla_contactos)) {
            return 0;
        }

        return (int) $wpdb->get_var("SELECT COUNT(*) FROM $tabla_contactos WHERE estado = 'nuevo'");
    }

    /**
     * Renderiza el dashboard del módulo empresarial
     */
    public function render_admin_dashboard() {
        $estadisticas = $this->action_estadisticas([]);

        $this->render_page_header(
            __('Dashboard Empresarial', 'flavor-chat-ia'),
            [
                [
                    'label' => __('Nuevo Proyecto', 'flavor-chat-ia'),
                    'url'   => $this->admin_page_url('flavor-empresarial-contratos') . '&action=nuevo',
                    'class' => 'button-primary',
                ],
            ]
        );

        include dirname(__FILE__) . '/views/admin-dashboard.php';
    }

    /**
     * Renderiza la página de gestión de empresas/clientes
     */
    public function render_admin_empresas() {
        $this->render_page_header(
            __('Gestión de Empresas', 'flavor-chat-ia'),
            [
                [
                    'label' => __('Nueva Empresa', 'flavor-chat-ia'),
                    'url'   => $this->admin_page_url('flavor-empresarial-empresas') . '&action=nueva',
                    'class' => 'button-primary',
                ],
            ]
        );

        include dirname(__FILE__) . '/views/admin-empresas.php';
    }

    /**
     * Renderiza la página de gestión de contratos/proyectos
     */
    public function render_admin_contratos() {
        $this->render_page_header(
            __('Gestión de Contratos', 'flavor-chat-ia'),
            [
                [
                    'label' => __('Nuevo Contrato', 'flavor-chat-ia'),
                    'url'   => $this->admin_page_url('flavor-empresarial-contratos') . '&action=nuevo',
                    'class' => 'button-primary',
                ],
            ]
        );

        include dirname(__FILE__) . '/views/admin-contratos.php';
    }

    /**
     * Renderiza el widget del dashboard principal de WordPress
     */
    public function render_dashboard_widget() {
        $estadisticas = $this->action_estadisticas([]);

        if (!$estadisticas['success']) {
            echo '<p>' . esc_html__('No se pudieron cargar las estadísticas.', 'flavor-chat-ia') . '</p>';
            return;
        }

        $datos_estadisticas = $estadisticas['estadisticas'];
        ?>
        <div class="flavor-empresarial-widget">
            <div class="widget-stats-grid">
                <div class="stat-item">
                    <span class="stat-number"><?php echo esc_html($datos_estadisticas['contactos']['nuevos']); ?></span>
                    <span class="stat-label"><?php esc_html_e('Contactos Nuevos', 'flavor-chat-ia'); ?></span>
                </div>
                <div class="stat-item">
                    <span class="stat-number"><?php echo esc_html($datos_estadisticas['proyectos']['activos']); ?></span>
                    <span class="stat-label"><?php esc_html_e('Proyectos Activos', 'flavor-chat-ia'); ?></span>
                </div>
                <div class="stat-item">
                    <span class="stat-number"><?php echo esc_html($datos_estadisticas['financiero']['presupuesto_activo_fmt']); ?></span>
                    <span class="stat-label"><?php esc_html_e('Presupuesto Activo', 'flavor-chat-ia'); ?></span>
                </div>
            </div>
            <p class="widget-footer">
                <a href="<?php echo esc_url($this->admin_page_url('flavor-empresarial-dashboard')); ?>">
                    <?php esc_html_e('Ver Dashboard Completo', 'flavor-chat-ia'); ?> &rarr;
                </a>
            </p>
        </div>
        <?php
    }

    /**
     * Obtiene un resumen de estadísticas para el panel unificado
     *
     * @return array
     */
    public function get_estadisticas_resumen() {
        $estadisticas = $this->action_estadisticas([]);

        if (!$estadisticas['success']) {
            return [];
        }

        $datos_estadisticas = $estadisticas['estadisticas'];

        return [
            'contactos_nuevos'    => $datos_estadisticas['contactos']['nuevos'],
            'contactos_pendientes' => $datos_estadisticas['contactos']['pendientes'],
            'proyectos_activos'   => $datos_estadisticas['proyectos']['activos'],
            'proyectos_vencidos'  => $datos_estadisticas['proyectos']['vencidos'],
            'presupuesto_activo'  => $datos_estadisticas['financiero']['presupuesto_activo_fmt'],
        ];
    }

    // =========================================================================
    // TABLAS DE BASE DE DATOS
    // =========================================================================

    /**
     * Crea las tablas si no existen
     */
    public function maybe_create_tables() {
        global $wpdb;
        $tabla_contactos = $wpdb->prefix . 'flavor_empresarial_contactos';

        if (!Flavor_Chat_Helpers::tabla_existe($tabla_contactos)) {
            $this->create_tables();
        }
    }

    /**
     * Crea las tablas necesarias para el módulo empresarial
     */
    private function create_tables() {
        global $wpdb;
        $charset_collate = $wpdb->get_charset_collate();

        $tabla_contactos = $wpdb->prefix . 'flavor_empresarial_contactos';
        $tabla_proyectos = $wpdb->prefix . 'flavor_empresarial_proyectos';

        $sql_contactos = "CREATE TABLE IF NOT EXISTS $tabla_contactos (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            nombre varchar(200) NOT NULL,
            email varchar(200) NOT NULL,
            telefono varchar(50) DEFAULT NULL,
            empresa varchar(200) DEFAULT NULL,
            asunto varchar(255) DEFAULT NULL,
            mensaje text NOT NULL,
            origen varchar(100) DEFAULT 'web',
            estado enum('nuevo','leido','respondido','archivado') DEFAULT 'nuevo',
            asignado_a bigint(20) unsigned DEFAULT NULL,
            notas_internas text DEFAULT NULL,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY estado (estado),
            KEY email (email),
            KEY created_at (created_at),
            KEY asignado_a (asignado_a)
        ) $charset_collate;";

        $sql_proyectos = "CREATE TABLE IF NOT EXISTS $tabla_proyectos (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            cliente_nombre varchar(200) NOT NULL,
            cliente_email varchar(200) DEFAULT NULL,
            titulo varchar(255) NOT NULL,
            descripcion text DEFAULT NULL,
            estado enum('propuesta','aprobado','en_curso','completado','cancelado') DEFAULT 'propuesta',
            presupuesto decimal(12,2) DEFAULT 0.00,
            fecha_inicio date DEFAULT NULL,
            fecha_entrega date DEFAULT NULL,
            responsable_id bigint(20) unsigned DEFAULT NULL,
            progreso int(11) DEFAULT 0,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY estado (estado),
            KEY cliente_email (cliente_email),
            KEY responsable_id (responsable_id),
            KEY fecha_entrega (fecha_entrega)
        ) $charset_collate;";

        require_once ABSPATH . 'wp-admin/includes/upgrade.php';
        dbDelta($sql_contactos);
        dbDelta($sql_proyectos);
    }

    // =========================================================================
    // AJAX - FORMULARIO DE CONTACTO FRONTEND
    // =========================================================================

    /**
     * Procesa el envío del formulario de contacto desde el frontend
     */
    public function ajax_contacto_form() {
        // Verificar nonce de seguridad
        if (!check_ajax_referer('empresarial_contacto_nonce', 'nonce', false)) {
            wp_send_json_error([
                'message' => __('Error de seguridad. Recarga la página e intenta de nuevo.', 'flavor-chat-ia'),
            ]);
        }

        // Sanitizar datos del formulario
        $nombre_contacto  = sanitize_text_field(wp_unslash($_POST['nombre'] ?? ''));
        $email_contacto   = sanitize_email(wp_unslash($_POST['email'] ?? ''));
        $telefono_contacto = sanitize_text_field(wp_unslash($_POST['telefono'] ?? ''));
        $empresa_contacto = sanitize_text_field(wp_unslash($_POST['empresa'] ?? ''));
        $asunto_contacto  = sanitize_text_field(wp_unslash($_POST['asunto'] ?? ''));
        $mensaje_contacto = sanitize_textarea_field(wp_unslash($_POST['mensaje'] ?? ''));
        $origen_contacto  = sanitize_text_field(wp_unslash($_POST['origen'] ?? 'web'));

        // Validar campos obligatorios
        if (empty($nombre_contacto) || empty($email_contacto) || empty($mensaje_contacto)) {
            wp_send_json_error([
                'message' => __('Por favor, completa los campos obligatorios: nombre, email y mensaje.', 'flavor-chat-ia'),
            ]);
        }

        if (!is_email($email_contacto)) {
            wp_send_json_error([
                'message' => __('El email proporcionado no es válido.', 'flavor-chat-ia'),
            ]);
        }

        // Validar origen permitido
        $origenes_permitidos = ['web', 'landing', 'popup'];
        if (!in_array($origen_contacto, $origenes_permitidos, true)) {
            $origen_contacto = 'web';
        }

        // Insertar en base de datos
        global $wpdb;
        $tabla_contactos = $wpdb->prefix . 'flavor_empresarial_contactos';

        $resultado_insercion = $wpdb->insert(
            $tabla_contactos,
            [
                'nombre'     => $nombre_contacto,
                'email'      => $email_contacto,
                'telefono'   => $telefono_contacto,
                'empresa'    => $empresa_contacto,
                'asunto'     => $asunto_contacto,
                'mensaje'    => $mensaje_contacto,
                'origen'     => $origen_contacto,
                'estado'     => 'nuevo',
                'created_at' => current_time('mysql'),
                'updated_at' => current_time('mysql'),
            ],
            ['%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s']
        );

        if ($resultado_insercion === false) {
            wp_send_json_error([
                'message' => __('Error al guardar el mensaje. Inténtalo de nuevo más tarde.', 'flavor-chat-ia'),
            ]);
        }

        // Notificar por email al administrador
        $email_admin = get_option('admin_email');
        $asunto_email = sprintf(
            __('[%s] Nuevo contacto empresarial: %s', 'flavor-chat-ia'),
            get_bloginfo('name'),
            $asunto_contacto ?: __('Sin asunto', 'flavor-chat-ia')
        );
        $cuerpo_email = sprintf(
            __("Nuevo mensaje de contacto recibido:\n\nNombre: %s\nEmail: %s\nTeléfono: %s\nEmpresa: %s\nAsunto: %s\n\nMensaje:\n%s\n\nOrigen: %s", 'flavor-chat-ia'),
            $nombre_contacto,
            $email_contacto,
            $telefono_contacto ?: '-',
            $empresa_contacto ?: '-',
            $asunto_contacto ?: '-',
            $mensaje_contacto,
            $origen_contacto
        );
        wp_mail($email_admin, $asunto_email, $cuerpo_email);

        wp_send_json_success([
            'message'    => __('Mensaje enviado correctamente. Te responderemos lo antes posible.', 'flavor-chat-ia'),
            'contacto_id' => $wpdb->insert_id,
        ]);
    }

    /**
     * Obtener acciones del módulo
     */
    public function get_actions() {
        return [
            'listar_contactos' => [
                'description' => 'Listar los mensajes de contacto recibidos',
                'params' => ['estado', 'limite', 'pagina'],
            ],
            'ver_contacto' => [
                'description' => 'Ver detalle de un mensaje de contacto',
                'params' => ['contacto_id'],
            ],
            'responder_contacto' => [
                'description' => 'Marcar un contacto como respondido y añadir notas',
                'params' => ['contacto_id', 'notas'],
            ],
            'crear_proyecto' => [
                'description' => 'Crear un nuevo proyecto empresarial',
                'params' => ['titulo', 'cliente_nombre', 'cliente_email', 'descripcion', 'presupuesto', 'fecha_inicio', 'fecha_entrega'],
            ],
            'listar_proyectos' => [
                'description' => 'Listar proyectos empresariales',
                'params' => ['estado', 'limite', 'pagina'],
            ],
            'ver_proyecto' => [
                'description' => 'Ver detalle de un proyecto',
                'params' => ['proyecto_id'],
            ],
            'actualizar_proyecto' => [
                'description' => 'Actualizar estado o progreso de un proyecto',
                'params' => ['proyecto_id', 'estado', 'progreso', 'descripcion'],
            ],
            'estadisticas' => [
                'description' => 'Obtener estadísticas del panel empresarial',
                'params' => [],
            ],
            'buscar' => [
                'description' => 'Buscar en contactos y proyectos',
                'params' => ['termino', 'tipo', 'limite'],
            ],
        ];
    }

    /**
     * Ejecutar acción del módulo
     */
    public function execute_action($action, $data = []) {
        $nombre_metodo_accion = 'action_' . $action;

        if (method_exists($this, $nombre_metodo_accion)) {
            return $this->$nombre_metodo_accion($data);
        }

        return [
            'success' => false,
            'error' => sprintf(__('Acción no implementada: %s', 'flavor-chat-ia'), $action),
        ];
    }

    // =========================================================================
    // IMPLEMENTACIONES DE ACCIONES
    // =========================================================================

    /**
     * Acción: Listar contactos recibidos
     */
    private function action_listar_contactos($parametros) {
        global $wpdb;
        $tabla_contactos = $wpdb->prefix . 'flavor_empresarial_contactos';

        $estado_filtro     = sanitize_text_field($parametros['estado'] ?? '');
        $limite_resultados = absint($parametros['limite'] ?? 20);
        $pagina_actual     = max(1, absint($parametros['pagina'] ?? 1));
        $offset_consulta   = ($pagina_actual - 1) * $limite_resultados;

        $condiciones_where = '1=1';
        $valores_parametros = [];

        if (!empty($estado_filtro)) {
            $estados_validos = ['nuevo', 'leido', 'respondido', 'archivado'];
            if (in_array($estado_filtro, $estados_validos, true)) {
                $condiciones_where .= ' AND estado = %s';
                $valores_parametros[] = $estado_filtro;
            }
        }

        // Contar total
        $consulta_total = "SELECT COUNT(*) FROM $tabla_contactos WHERE $condiciones_where";
        if (!empty($valores_parametros)) {
            $total_contactos = (int) $wpdb->get_var($wpdb->prepare($consulta_total, $valores_parametros));
        } else {
            $total_contactos = (int) $wpdb->get_var($consulta_total);
        }

        // Obtener resultados
        $consulta_contactos = "SELECT id, nombre, email, empresa, asunto, origen, estado, created_at
                               FROM $tabla_contactos
                               WHERE $condiciones_where
                               ORDER BY created_at DESC
                               LIMIT %d OFFSET %d";

        $parametros_completos = array_merge($valores_parametros, [$limite_resultados, $offset_consulta]);
        $lista_contactos = $wpdb->get_results($wpdb->prepare($consulta_contactos, $parametros_completos), ARRAY_A);

        return [
            'success'    => true,
            'total'      => $total_contactos,
            'pagina'     => $pagina_actual,
            'por_pagina' => $limite_resultados,
            'contactos'  => $lista_contactos ?: [],
            'mensaje'    => sprintf(__('Se encontraron %d contactos.', 'flavor-chat-ia'), $total_contactos),
        ];
    }

    /**
     * Acción: Ver detalle de un contacto
     */
    private function action_ver_contacto($parametros) {
        global $wpdb;
        $tabla_contactos = $wpdb->prefix . 'flavor_empresarial_contactos';

        $contacto_id = absint($parametros['contacto_id'] ?? 0);
        if (!$contacto_id) {
            return [
                'success' => false,
                'error'   => __('ID de contacto no válido.', 'flavor-chat-ia'),
            ];
        }

        $detalle_contacto = $wpdb->get_row(
            $wpdb->prepare("SELECT * FROM $tabla_contactos WHERE id = %d", $contacto_id),
            ARRAY_A
        );

        if (!$detalle_contacto) {
            return [
                'success' => false,
                'error'   => __('Contacto no encontrado.', 'flavor-chat-ia'),
            ];
        }

        // Marcar como leído si estaba como nuevo
        if ($detalle_contacto['estado'] === 'nuevo') {
            $wpdb->update(
                $tabla_contactos,
                ['estado' => 'leido', 'updated_at' => current_time('mysql')],
                ['id' => $contacto_id],
                ['%s', '%s'],
                ['%d']
            );
            $detalle_contacto['estado'] = 'leido';
        }

        // Información del usuario asignado
        $nombre_asignado = '';
        if (!empty($detalle_contacto['asignado_a'])) {
            $usuario_asignado = get_userdata($detalle_contacto['asignado_a']);
            if ($usuario_asignado) {
                $nombre_asignado = $usuario_asignado->display_name;
            }
        }
        $detalle_contacto['nombre_asignado'] = $nombre_asignado;

        return [
            'success'  => true,
            'contacto' => $detalle_contacto,
        ];
    }

    /**
     * Acción: Marcar contacto como respondido
     */
    private function action_responder_contacto($parametros) {
        global $wpdb;
        $tabla_contactos = $wpdb->prefix . 'flavor_empresarial_contactos';

        $contacto_id    = absint($parametros['contacto_id'] ?? 0);
        $notas_respuesta = sanitize_textarea_field($parametros['notas'] ?? '');

        if (!$contacto_id) {
            return [
                'success' => false,
                'error'   => __('ID de contacto no válido.', 'flavor-chat-ia'),
            ];
        }

        $contacto_existente = $wpdb->get_row(
            $wpdb->prepare("SELECT id, estado FROM $tabla_contactos WHERE id = %d", $contacto_id),
            ARRAY_A
        );

        if (!$contacto_existente) {
            return [
                'success' => false,
                'error'   => __('Contacto no encontrado.', 'flavor-chat-ia'),
            ];
        }

        $datos_actualizacion = [
            'estado'     => 'respondido',
            'updated_at' => current_time('mysql'),
        ];
        $formatos_actualizacion = ['%s', '%s'];

        if (!empty($notas_respuesta)) {
            $datos_actualizacion['notas_internas'] = $notas_respuesta;
            $formatos_actualizacion[] = '%s';
        }

        // Asignar al usuario actual si no tiene asignado
        if (is_user_logged_in()) {
            $datos_actualizacion['asignado_a'] = get_current_user_id();
            $formatos_actualizacion[] = '%d';
        }

        $wpdb->update(
            $tabla_contactos,
            $datos_actualizacion,
            ['id' => $contacto_id],
            $formatos_actualizacion,
            ['%d']
        );

        return [
            'success' => true,
            'mensaje' => __('Contacto marcado como respondido.', 'flavor-chat-ia'),
        ];
    }

    /**
     * Acción: Crear un nuevo proyecto
     */
    private function action_crear_proyecto($parametros) {
        global $wpdb;
        $tabla_proyectos = $wpdb->prefix . 'flavor_empresarial_proyectos';

        $titulo_proyecto       = sanitize_text_field($parametros['titulo'] ?? '');
        $nombre_cliente        = sanitize_text_field($parametros['cliente_nombre'] ?? '');
        $email_cliente         = sanitize_email($parametros['cliente_email'] ?? '');
        $descripcion_proyecto  = sanitize_textarea_field($parametros['descripcion'] ?? '');
        $presupuesto_proyecto  = floatval($parametros['presupuesto'] ?? 0);
        $fecha_inicio_proyecto = sanitize_text_field($parametros['fecha_inicio'] ?? '');
        $fecha_entrega_proyecto = sanitize_text_field($parametros['fecha_entrega'] ?? '');

        // Validaciones
        if (empty($titulo_proyecto)) {
            return [
                'success' => false,
                'error'   => __('El título del proyecto es obligatorio.', 'flavor-chat-ia'),
            ];
        }

        if (empty($nombre_cliente)) {
            return [
                'success' => false,
                'error'   => __('El nombre del cliente es obligatorio.', 'flavor-chat-ia'),
            ];
        }

        // Validar formato de fechas
        $fecha_inicio_valida  = null;
        $fecha_entrega_valida = null;

        if (!empty($fecha_inicio_proyecto)) {
            $fecha_inicio_parseada = date_create($fecha_inicio_proyecto);
            if ($fecha_inicio_parseada) {
                $fecha_inicio_valida = date_format($fecha_inicio_parseada, 'Y-m-d');
            }
        }

        if (!empty($fecha_entrega_proyecto)) {
            $fecha_entrega_parseada = date_create($fecha_entrega_proyecto);
            if ($fecha_entrega_parseada) {
                $fecha_entrega_valida = date_format($fecha_entrega_parseada, 'Y-m-d');
            }
        }

        $identificador_responsable = is_user_logged_in() ? get_current_user_id() : null;

        $resultado_insercion = $wpdb->insert(
            $tabla_proyectos,
            [
                'titulo'         => $titulo_proyecto,
                'cliente_nombre' => $nombre_cliente,
                'cliente_email'  => $email_cliente,
                'descripcion'    => $descripcion_proyecto,
                'estado'         => 'propuesta',
                'presupuesto'    => $presupuesto_proyecto,
                'fecha_inicio'   => $fecha_inicio_valida,
                'fecha_entrega'  => $fecha_entrega_valida,
                'responsable_id' => $identificador_responsable,
                'progreso'       => 0,
                'created_at'     => current_time('mysql'),
                'updated_at'     => current_time('mysql'),
            ],
            ['%s', '%s', '%s', '%s', '%s', '%f', '%s', '%s', '%d', '%d', '%s', '%s']
        );

        if ($resultado_insercion === false) {
            return [
                'success' => false,
                'error'   => __('Error al crear el proyecto. Inténtalo de nuevo.', 'flavor-chat-ia'),
            ];
        }

        return [
            'success'     => true,
            'proyecto_id' => $wpdb->insert_id,
            'mensaje'     => sprintf(__('Proyecto "%s" creado correctamente.', 'flavor-chat-ia'), $titulo_proyecto),
        ];
    }

    /**
     * Acción: Listar proyectos
     */
    private function action_listar_proyectos($parametros) {
        global $wpdb;
        $tabla_proyectos = $wpdb->prefix . 'flavor_empresarial_proyectos';

        $estado_filtro     = sanitize_text_field($parametros['estado'] ?? '');
        $limite_resultados = absint($parametros['limite'] ?? 20);
        $pagina_actual     = max(1, absint($parametros['pagina'] ?? 1));
        $offset_consulta   = ($pagina_actual - 1) * $limite_resultados;

        $condiciones_where = '1=1';
        $valores_parametros = [];

        if (!empty($estado_filtro)) {
            $estados_validos_proyecto = ['propuesta', 'aprobado', 'en_curso', 'completado', 'cancelado'];
            if (in_array($estado_filtro, $estados_validos_proyecto, true)) {
                $condiciones_where .= ' AND estado = %s';
                $valores_parametros[] = $estado_filtro;
            }
        }

        // Contar total
        $consulta_total = "SELECT COUNT(*) FROM $tabla_proyectos WHERE $condiciones_where";
        if (!empty($valores_parametros)) {
            $total_proyectos = (int) $wpdb->get_var($wpdb->prepare($consulta_total, $valores_parametros));
        } else {
            $total_proyectos = (int) $wpdb->get_var($consulta_total);
        }

        // Obtener resultados
        $consulta_proyectos = "SELECT id, titulo, cliente_nombre, estado, presupuesto, progreso, fecha_inicio, fecha_entrega, created_at
                               FROM $tabla_proyectos
                               WHERE $condiciones_where
                               ORDER BY created_at DESC
                               LIMIT %d OFFSET %d";

        $parametros_completos = array_merge($valores_parametros, [$limite_resultados, $offset_consulta]);
        $lista_proyectos = $wpdb->get_results($wpdb->prepare($consulta_proyectos, $parametros_completos), ARRAY_A);

        return [
            'success'    => true,
            'total'      => $total_proyectos,
            'pagina'     => $pagina_actual,
            'por_pagina' => $limite_resultados,
            'proyectos'  => $lista_proyectos ?: [],
            'mensaje'    => sprintf(__('Se encontraron %d proyectos.', 'flavor-chat-ia'), $total_proyectos),
        ];
    }

    /**
     * Acción: Ver detalle de un proyecto
     */
    private function action_ver_proyecto($parametros) {
        global $wpdb;
        $tabla_proyectos = $wpdb->prefix . 'flavor_empresarial_proyectos';

        $proyecto_id = absint($parametros['proyecto_id'] ?? 0);
        if (!$proyecto_id) {
            return [
                'success' => false,
                'error'   => __('ID de proyecto no válido.', 'flavor-chat-ia'),
            ];
        }

        $detalle_proyecto = $wpdb->get_row(
            $wpdb->prepare("SELECT * FROM $tabla_proyectos WHERE id = %d", $proyecto_id),
            ARRAY_A
        );

        if (!$detalle_proyecto) {
            return [
                'success' => false,
                'error'   => __('Proyecto no encontrado.', 'flavor-chat-ia'),
            ];
        }

        // Información del responsable
        $nombre_responsable = '';
        if (!empty($detalle_proyecto['responsable_id'])) {
            $usuario_responsable = get_userdata($detalle_proyecto['responsable_id']);
            if ($usuario_responsable) {
                $nombre_responsable = $usuario_responsable->display_name;
            }
        }
        $detalle_proyecto['nombre_responsable'] = $nombre_responsable;

        // Calcular días restantes hasta entrega
        $dias_restantes_entrega = null;
        if (!empty($detalle_proyecto['fecha_entrega'])) {
            $fecha_entrega_obj = date_create($detalle_proyecto['fecha_entrega']);
            $fecha_hoy_obj     = date_create(current_time('Y-m-d'));
            if ($fecha_entrega_obj && $fecha_hoy_obj) {
                $diferencia_fechas = date_diff($fecha_hoy_obj, $fecha_entrega_obj);
                $dias_restantes_entrega = (int) $diferencia_fechas->format('%R%a');
            }
        }
        $detalle_proyecto['dias_restantes'] = $dias_restantes_entrega;

        return [
            'success'  => true,
            'proyecto' => $detalle_proyecto,
        ];
    }

    /**
     * Acción: Actualizar estado o progreso de un proyecto
     */
    private function action_actualizar_proyecto($parametros) {
        global $wpdb;
        $tabla_proyectos = $wpdb->prefix . 'flavor_empresarial_proyectos';

        $proyecto_id = absint($parametros['proyecto_id'] ?? 0);
        if (!$proyecto_id) {
            return [
                'success' => false,
                'error'   => __('ID de proyecto no válido.', 'flavor-chat-ia'),
            ];
        }

        $proyecto_existente = $wpdb->get_row(
            $wpdb->prepare("SELECT id, estado FROM $tabla_proyectos WHERE id = %d", $proyecto_id),
            ARRAY_A
        );

        if (!$proyecto_existente) {
            return [
                'success' => false,
                'error'   => __('Proyecto no encontrado.', 'flavor-chat-ia'),
            ];
        }

        $datos_actualizacion = [
            'updated_at' => current_time('mysql'),
        ];
        $formatos_actualizacion = ['%s'];

        // Actualizar estado si se proporciona
        $nuevo_estado = sanitize_text_field($parametros['estado'] ?? '');
        if (!empty($nuevo_estado)) {
            $estados_validos_proyecto = ['propuesta', 'aprobado', 'en_curso', 'completado', 'cancelado'];
            if (in_array($nuevo_estado, $estados_validos_proyecto, true)) {
                $datos_actualizacion['estado'] = $nuevo_estado;
                $formatos_actualizacion[] = '%s';

                // Si se completa, poner progreso al 100%
                if ($nuevo_estado === 'completado') {
                    $datos_actualizacion['progreso'] = 100;
                    $formatos_actualizacion[] = '%d';
                }
            }
        }

        // Actualizar progreso si se proporciona
        if (isset($parametros['progreso'])) {
            $nuevo_progreso = min(100, max(0, absint($parametros['progreso'])));
            $datos_actualizacion['progreso'] = $nuevo_progreso;
            $formatos_actualizacion[] = '%d';
        }

        // Actualizar descripción si se proporciona
        $nueva_descripcion = sanitize_textarea_field($parametros['descripcion'] ?? '');
        if (!empty($nueva_descripcion)) {
            $datos_actualizacion['descripcion'] = $nueva_descripcion;
            $formatos_actualizacion[] = '%s';
        }

        $wpdb->update(
            $tabla_proyectos,
            $datos_actualizacion,
            ['id' => $proyecto_id],
            $formatos_actualizacion,
            ['%d']
        );

        return [
            'success' => true,
            'mensaje' => __('Proyecto actualizado correctamente.', 'flavor-chat-ia'),
        ];
    }

    /**
     * Acción: Obtener estadísticas del panel empresarial
     */
    private function action_estadisticas($parametros) {
        global $wpdb;
        $tabla_contactos = $wpdb->prefix . 'flavor_empresarial_contactos';
        $tabla_proyectos = $wpdb->prefix . 'flavor_empresarial_proyectos';

        // -- Estadísticas de contactos --
        $total_contactos           = (int) $wpdb->get_var("SELECT COUNT(*) FROM $tabla_contactos");
        $contactos_nuevos          = (int) $wpdb->get_var("SELECT COUNT(*) FROM $tabla_contactos WHERE estado = 'nuevo'");
        $contactos_pendientes      = (int) $wpdb->get_var("SELECT COUNT(*) FROM $tabla_contactos WHERE estado IN ('nuevo', 'leido')");
        $contactos_respondidos     = (int) $wpdb->get_var("SELECT COUNT(*) FROM $tabla_contactos WHERE estado = 'respondido'");

        // Contactos de los últimos 30 días
        $fecha_hace_30_dias = date('Y-m-d H:i:s', strtotime('-30 days'));
        $contactos_ultimo_mes = (int) $wpdb->get_var(
            $wpdb->prepare("SELECT COUNT(*) FROM $tabla_contactos WHERE created_at >= %s", $fecha_hace_30_dias)
        );

        // Contactos de los últimos 7 días
        $fecha_hace_7_dias = date('Y-m-d H:i:s', strtotime('-7 days'));
        $contactos_ultima_semana = (int) $wpdb->get_var(
            $wpdb->prepare("SELECT COUNT(*) FROM $tabla_contactos WHERE created_at >= %s", $fecha_hace_7_dias)
        );

        // -- Estadísticas de proyectos --
        $total_proyectos       = (int) $wpdb->get_var("SELECT COUNT(*) FROM $tabla_proyectos");
        $proyectos_activos     = (int) $wpdb->get_var("SELECT COUNT(*) FROM $tabla_proyectos WHERE estado IN ('aprobado', 'en_curso')");
        $proyectos_completados = (int) $wpdb->get_var("SELECT COUNT(*) FROM $tabla_proyectos WHERE estado = 'completado'");
        $proyectos_propuesta   = (int) $wpdb->get_var("SELECT COUNT(*) FROM $tabla_proyectos WHERE estado = 'propuesta'");
        $proyectos_cancelados  = (int) $wpdb->get_var("SELECT COUNT(*) FROM $tabla_proyectos WHERE estado = 'cancelado'");

        // Presupuesto total y por estado
        $presupuesto_total = (float) $wpdb->get_var("SELECT COALESCE(SUM(presupuesto), 0) FROM $tabla_proyectos");
        $presupuesto_activo = (float) $wpdb->get_var("SELECT COALESCE(SUM(presupuesto), 0) FROM $tabla_proyectos WHERE estado IN ('aprobado', 'en_curso')");
        $presupuesto_completado = (float) $wpdb->get_var("SELECT COALESCE(SUM(presupuesto), 0) FROM $tabla_proyectos WHERE estado = 'completado'");

        // Progreso promedio de proyectos activos
        $progreso_promedio = (float) $wpdb->get_var("SELECT COALESCE(AVG(progreso), 0) FROM $tabla_proyectos WHERE estado IN ('aprobado', 'en_curso')");

        // Proyectos con fecha de entrega vencida
        $proyectos_vencidos = (int) $wpdb->get_var(
            $wpdb->prepare(
                "SELECT COUNT(*) FROM $tabla_proyectos WHERE estado IN ('aprobado', 'en_curso') AND fecha_entrega IS NOT NULL AND fecha_entrega < %s",
                current_time('Y-m-d')
            )
        );

        // Orígenes de contactos (distribución)
        $distribucion_origenes = $wpdb->get_results(
            "SELECT origen, COUNT(*) as cantidad FROM $tabla_contactos GROUP BY origen ORDER BY cantidad DESC",
            ARRAY_A
        );

        return [
            'success' => true,
            'estadisticas' => [
                'contactos' => [
                    'total'          => $total_contactos,
                    'nuevos'         => $contactos_nuevos,
                    'pendientes'     => $contactos_pendientes,
                    'respondidos'    => $contactos_respondidos,
                    'ultimo_mes'     => $contactos_ultimo_mes,
                    'ultima_semana'  => $contactos_ultima_semana,
                    'origenes'       => $distribucion_origenes ?: [],
                ],
                'proyectos' => [
                    'total'              => $total_proyectos,
                    'activos'            => $proyectos_activos,
                    'completados'        => $proyectos_completados,
                    'propuestas'         => $proyectos_propuesta,
                    'cancelados'         => $proyectos_cancelados,
                    'vencidos'           => $proyectos_vencidos,
                    'progreso_promedio'  => round($progreso_promedio, 1),
                ],
                'financiero' => [
                    'presupuesto_total'      => $presupuesto_total,
                    'presupuesto_activo'     => $presupuesto_activo,
                    'presupuesto_completado' => $presupuesto_completado,
                    'presupuesto_total_fmt'      => $this->format_price($presupuesto_total),
                    'presupuesto_activo_fmt'     => $this->format_price($presupuesto_activo),
                    'presupuesto_completado_fmt' => $this->format_price($presupuesto_completado),
                ],
            ],
            'mensaje' => __('Estadísticas del módulo empresarial.', 'flavor-chat-ia'),
        ];
    }

    /**
     * Acción: Buscar en contactos y proyectos
     */
    private function action_buscar($parametros) {
        global $wpdb;

        $termino_busqueda  = sanitize_text_field($parametros['termino'] ?? '');
        $tipo_busqueda     = sanitize_text_field($parametros['tipo'] ?? 'todos');
        $limite_resultados = absint($parametros['limite'] ?? 10);

        if (empty($termino_busqueda)) {
            return [
                'success' => false,
                'error'   => __('Debes proporcionar un término de búsqueda.', 'flavor-chat-ia'),
            ];
        }

        $patron_busqueda    = '%' . $wpdb->esc_like($termino_busqueda) . '%';
        $contactos_encontrados = [];
        $proyectos_encontrados = [];

        // Buscar en contactos
        if (in_array($tipo_busqueda, ['todos', 'contactos'], true)) {
            $tabla_contactos = $wpdb->prefix . 'flavor_empresarial_contactos';
            $contactos_encontrados = $wpdb->get_results(
                $wpdb->prepare(
                    "SELECT id, nombre, email, empresa, asunto, estado, created_at
                     FROM $tabla_contactos
                     WHERE nombre LIKE %s OR email LIKE %s OR empresa LIKE %s OR asunto LIKE %s OR mensaje LIKE %s
                     ORDER BY created_at DESC
                     LIMIT %d",
                    $patron_busqueda, $patron_busqueda, $patron_busqueda, $patron_busqueda, $patron_busqueda,
                    $limite_resultados
                ),
                ARRAY_A
            ) ?: [];
        }

        // Buscar en proyectos
        if (in_array($tipo_busqueda, ['todos', 'proyectos'], true)) {
            $tabla_proyectos = $wpdb->prefix . 'flavor_empresarial_proyectos';
            $proyectos_encontrados = $wpdb->get_results(
                $wpdb->prepare(
                    "SELECT id, titulo, cliente_nombre, cliente_email, estado, presupuesto, progreso, created_at
                     FROM $tabla_proyectos
                     WHERE titulo LIKE %s OR cliente_nombre LIKE %s OR cliente_email LIKE %s OR descripcion LIKE %s
                     ORDER BY created_at DESC
                     LIMIT %d",
                    $patron_busqueda, $patron_busqueda, $patron_busqueda, $patron_busqueda,
                    $limite_resultados
                ),
                ARRAY_A
            ) ?: [];
        }

        $total_resultados = count($contactos_encontrados) + count($proyectos_encontrados);

        return [
            'success'    => true,
            'termino'    => $termino_busqueda,
            'total'      => $total_resultados,
            'contactos'  => $contactos_encontrados,
            'proyectos'  => $proyectos_encontrados,
            'mensaje'    => sprintf(
                __('Se encontraron %d resultados para "%s".', 'flavor-chat-ia'),
                $total_resultados,
                $termino_busqueda
            ),
        ];
    }

    /**
     * Obtener configuración REST API
     */
    public function get_rest_config() {
        return [
            'enabled' => true,
        ];
    }

    /**
     * Obtener settings del módulo
     */
    public function get_module_settings() {
        return [];
    }

    /**
     * Obtener definiciones de herramientas para IA
     */
    public function get_tool_definitions() {
        return [
            [
                'name' => 'empresarial_contactos',
                'description' => 'Lista y busca los mensajes de contacto empresariales recibidos. Permite filtrar por estado (nuevo, leido, respondido, archivado) y buscar por nombre, email, empresa o asunto.',
                'input_schema' => [
                    'type' => 'object',
                    'properties' => [
                        'accion' => [
                            'type' => 'string',
                            'description' => 'Acción a realizar: listar, ver, responder, buscar',
                            'enum' => ['listar', 'ver', 'responder', 'buscar'],
                        ],
                        'contacto_id' => [
                            'type' => 'integer',
                            'description' => 'ID del contacto (para ver o responder)',
                        ],
                        'estado' => [
                            'type' => 'string',
                            'description' => 'Filtrar por estado: nuevo, leido, respondido, archivado',
                            'enum' => ['nuevo', 'leido', 'respondido', 'archivado'],
                        ],
                        'termino' => [
                            'type' => 'string',
                            'description' => 'Término de búsqueda para buscar contactos',
                        ],
                        'notas' => [
                            'type' => 'string',
                            'description' => 'Notas internas al responder un contacto',
                        ],
                        'limite' => [
                            'type' => 'integer',
                            'description' => 'Número máximo de resultados',
                            'default' => 20,
                        ],
                        'pagina' => [
                            'type' => 'integer',
                            'description' => 'Página de resultados',
                            'default' => 1,
                        ],
                    ],
                    'required' => ['accion'],
                ],
            ],
            [
                'name' => 'empresarial_proyectos',
                'description' => 'Gestiona proyectos empresariales. Permite crear, listar, ver detalles y actualizar estado/progreso de proyectos con sus clientes, presupuestos y fechas.',
                'input_schema' => [
                    'type' => 'object',
                    'properties' => [
                        'accion' => [
                            'type' => 'string',
                            'description' => 'Acción a realizar: listar, ver, crear, actualizar, buscar',
                            'enum' => ['listar', 'ver', 'crear', 'actualizar', 'buscar'],
                        ],
                        'proyecto_id' => [
                            'type' => 'integer',
                            'description' => 'ID del proyecto (para ver o actualizar)',
                        ],
                        'titulo' => [
                            'type' => 'string',
                            'description' => 'Título del proyecto (para crear)',
                        ],
                        'cliente_nombre' => [
                            'type' => 'string',
                            'description' => 'Nombre del cliente (para crear)',
                        ],
                        'cliente_email' => [
                            'type' => 'string',
                            'description' => 'Email del cliente (para crear)',
                        ],
                        'descripcion' => [
                            'type' => 'string',
                            'description' => 'Descripción del proyecto',
                        ],
                        'presupuesto' => [
                            'type' => 'number',
                            'description' => 'Presupuesto del proyecto en euros',
                        ],
                        'fecha_inicio' => [
                            'type' => 'string',
                            'description' => 'Fecha de inicio (formato YYYY-MM-DD)',
                        ],
                        'fecha_entrega' => [
                            'type' => 'string',
                            'description' => 'Fecha de entrega (formato YYYY-MM-DD)',
                        ],
                        'estado' => [
                            'type' => 'string',
                            'description' => 'Estado del proyecto: propuesta, aprobado, en_curso, completado, cancelado',
                            'enum' => ['propuesta', 'aprobado', 'en_curso', 'completado', 'cancelado'],
                        ],
                        'progreso' => [
                            'type' => 'integer',
                            'description' => 'Porcentaje de progreso (0-100)',
                        ],
                        'termino' => [
                            'type' => 'string',
                            'description' => 'Término de búsqueda',
                        ],
                        'limite' => [
                            'type' => 'integer',
                            'description' => 'Número máximo de resultados',
                            'default' => 20,
                        ],
                        'pagina' => [
                            'type' => 'integer',
                            'description' => 'Página de resultados',
                            'default' => 1,
                        ],
                    ],
                    'required' => ['accion'],
                ],
            ],
            [
                'name' => 'empresarial_estadisticas',
                'description' => 'Obtiene estadísticas del módulo empresarial: total de contactos, contactos pendientes, proyectos activos, presupuestos, progreso medio, etc. Útil para dashboards y resúmenes ejecutivos.',
                'input_schema' => [
                    'type' => 'object',
                    'properties' => new \stdClass(), // Sin parámetros requeridos
                    'required' => [],
                ],
            ],
        ];
    }

    /**
     * Obtener base de conocimiento para IA
     */
    public function get_knowledge_base() {
        return <<<'KNOWLEDGE'
**Modulo Empresarial - Guia de Uso**

Este modulo gestiona la actividad empresarial: contactos comerciales, proyectos y estadisticas de negocio.

**Contactos Comerciales:**
- Los visitantes pueden enviar formularios de contacto desde la web, landings o popups.
- Cada contacto tiene: nombre, email, telefono, empresa, asunto, mensaje, origen y estado.
- Estados de contacto: nuevo (sin leer), leido (visto), respondido (contestado), archivado.
- Se puede asignar un responsable a cada contacto y anadir notas internas.

**Proyectos:**
- Se pueden crear proyectos vinculados a clientes con presupuesto y fechas.
- Estados de proyecto: propuesta, aprobado, en_curso, completado, cancelado.
- Cada proyecto tiene progreso (0-100%), responsable, fecha de inicio y fecha de entrega.
- Al completar un proyecto, el progreso se establece automaticamente al 100%.

**Comandos disponibles:**
- "listar contactos": muestra los mensajes de contacto recibidos
- "ver contacto [ID]": muestra el detalle de un contacto
- "responder contacto [ID]": marca un contacto como respondido
- "crear proyecto": crea un nuevo proyecto empresarial
- "listar proyectos": muestra los proyectos
- "ver proyecto [ID]": muestra el detalle de un proyecto
- "actualizar proyecto [ID]": actualiza estado o progreso
- "estadisticas": muestra el dashboard con metricas de negocio
- "buscar [termino]": busca en contactos y proyectos

**Estadisticas disponibles:**
- Total y desglose de contactos por estado
- Contactos recibidos en la ultima semana y mes
- Distribucion de origenes de contacto
- Proyectos activos, completados y pendientes
- Presupuestos totales, activos y completados
- Progreso promedio de proyectos activos
- Proyectos con fecha de entrega vencida
KNOWLEDGE;
    }

    /**
     * Formatea un precio para mostrar
     *
     * @param float $precio Precio a formatear
     * @return string Precio formateado
     */
    protected function format_price($precio) {
        return number_format($precio, 2, ',', '.') . ' €';
    }

    /**
     * Crea páginas frontend automáticamente
     */
    public function maybe_create_pages() {
        if (!class_exists('Flavor_Page_Creator')) {
            return;
        }

        // En admin: refrescar páginas del módulo
        if (is_admin()) {
            Flavor_Page_Creator::refresh_module_pages('empresarial');
            return;
        }

        // En frontend: crear páginas si no existen
        $pagina = get_page_by_path('empresarial');
        if (!$pagina && !get_option('flavor_empresarial_pages_created')) {
            Flavor_Page_Creator::create_pages_for_modules(['empresarial']);
            update_option('flavor_empresarial_pages_created', 1, false);
        }
    }

    /**
     * Define las páginas del módulo para V3
     */
    public function get_pages_definition() {
        return [
            [
                'title' => __('Empresarial', 'flavor-chat-ia'),
                'slug' => 'empresarial',
                'content' => '<h1>' . __('Portal Empresarial', 'flavor-chat-ia') . '</h1>
<p>' . __('Soluciones empresariales para tu negocio', 'flavor-chat-ia') . '</p>

[flavor_module_listing module="empresarial" action="dashboard" columnas="3" limite="12"]',
                'parent' => 0,
            ],
            [
                'title' => __('Servicios', 'flavor-chat-ia'),
                'slug' => 'servicios-empresariales',
                'content' => '<h1>' . __('Servicios', 'flavor-chat-ia') . '</h1>
<p>' . __('Conoce nuestros servicios empresariales', 'flavor-chat-ia') . '</p>

[flavor_module_listing module="empresarial" action="servicios"]',
                'parent' => 'empresarial',
            ],
            [
                'title' => __('Contacto', 'flavor-chat-ia'),
                'slug' => 'contacto-empresarial',
                'content' => '<h1>' . __('Contacto Empresarial', 'flavor-chat-ia') . '</h1>
<p>' . __('Ponte en contacto con nosotros', 'flavor-chat-ia') . '</p>

[flavor_module_form module="empresarial" action="contacto"]',
                'parent' => 'empresarial',
            ],
            [
                'title' => __('Casos de Éxito', 'flavor-chat-ia'),
                'slug' => 'casos-exito',
                'content' => '<h1>' . __('Casos de Éxito', 'flavor-chat-ia') . '</h1>
<p>' . __('Descubre nuestros casos de éxito', 'flavor-chat-ia') . '</p>

[flavor_module_listing module="empresarial" action="casos_exito"]',
                'parent' => 'empresarial',
            ],
        ];
    }

    /**
     * Obtener shortcodes del módulo
     *
     * @return array
     */
    public function get_shortcodes() {
        return [
            'empresarial_servicios' => [
                'label'       => __('Servicios Empresariales', 'flavor-chat-ia'),
                'description' => __('Muestra los servicios de la empresa en un grid', 'flavor-chat-ia'),
                'callback'    => [$this, 'shortcode_servicios'],
                'atts'        => [
                    'titulo'      => __('Nuestros Servicios', 'flavor-chat-ia'),
                    'descripcion' => __('Soluciones integrales diseñadas para hacer crecer tu negocio', 'flavor-chat-ia'),
                    'columnas'    => 3,
                    'estilo'      => 'cards',
                ],
            ],
            'empresarial_equipo' => [
                'label'       => __('Equipo', 'flavor-chat-ia'),
                'description' => __('Muestra los miembros del equipo', 'flavor-chat-ia'),
                'callback'    => [$this, 'shortcode_equipo'],
                'atts'        => [
                    'titulo'      => __('Nuestro Equipo', 'flavor-chat-ia'),
                    'descripcion' => __('Profesionales comprometidos con tu éxito', 'flavor-chat-ia'),
                    'layout'      => 'grid',
                    'columnas'    => 4,
                ],
            ],
            'empresarial_testimonios' => [
                'label'       => __('Testimonios', 'flavor-chat-ia'),
                'description' => __('Muestra testimonios de clientes', 'flavor-chat-ia'),
                'callback'    => [$this, 'shortcode_testimonios'],
                'atts'        => [
                    'titulo' => __('Lo Que Dicen Nuestros Clientes', 'flavor-chat-ia'),
                    'layout' => 'carousel',
                ],
            ],
            'empresarial_contacto' => [
                'label'       => __('Formulario de Contacto', 'flavor-chat-ia'),
                'description' => __('Formulario profesional de contacto', 'flavor-chat-ia'),
                'callback'    => [$this, 'shortcode_contacto'],
                'atts'        => [
                    'titulo'       => __('Contacta con Nosotros', 'flavor-chat-ia'),
                    'descripcion'  => __('Estamos aquí para ayudarte', 'flavor-chat-ia'),
                    'layout'       => 'dos_columnas',
                    'mostrar_info' => true,
                ],
            ],
            'empresarial_portfolio' => [
                'label'       => __('Portfolio / Casos de Éxito', 'flavor-chat-ia'),
                'description' => __('Muestra proyectos completados', 'flavor-chat-ia'),
                'callback'    => [$this, 'shortcode_portfolio'],
                'atts'        => [
                    'titulo'      => __('Nuestros Casos de Éxito', 'flavor-chat-ia'),
                    'descripcion' => __('Proyectos que transformaron negocios', 'flavor-chat-ia'),
                    'layout'      => 'masonry',
                    'columnas'    => 3,
                    'limite'      => 6,
                ],
            ],
        ];
    }

    /**
     * Obtener configuración del formulario de contacto
     *
     * @return array
     */
    public function get_form_config() {
        return [
            'contacto' => [
                'titulo'      => __('Formulario de Contacto Empresarial', 'flavor-chat-ia'),
                'descripcion' => __('Formulario para que los visitantes puedan contactar con la empresa', 'flavor-chat-ia'),
                'campos'      => [
                    'nombre' => [
                        'type'        => 'text',
                        'label'       => __('Nombre', 'flavor-chat-ia'),
                        'required'    => true,
                        'placeholder' => __('Tu nombre', 'flavor-chat-ia'),
                    ],
                    'email' => [
                        'type'        => 'email',
                        'label'       => __('Email', 'flavor-chat-ia'),
                        'required'    => true,
                        'placeholder' => __('tu@email.com', 'flavor-chat-ia'),
                    ],
                    'telefono' => [
                        'type'        => 'tel',
                        'label'       => __('Teléfono', 'flavor-chat-ia'),
                        'required'    => false,
                        'placeholder' => __('Tu teléfono', 'flavor-chat-ia'),
                    ],
                    'empresa' => [
                        'type'        => 'text',
                        'label'       => __('Empresa', 'flavor-chat-ia'),
                        'required'    => false,
                        'placeholder' => __('Nombre de tu empresa', 'flavor-chat-ia'),
                    ],
                    'asunto' => [
                        'type'        => 'text',
                        'label'       => __('Asunto', 'flavor-chat-ia'),
                        'required'    => false,
                        'placeholder' => __('¿En qué podemos ayudarte?', 'flavor-chat-ia'),
                    ],
                    'mensaje' => [
                        'type'        => 'textarea',
                        'label'       => __('Mensaje', 'flavor-chat-ia'),
                        'required'    => true,
                        'placeholder' => __('Cuéntanos más sobre tu consulta...', 'flavor-chat-ia'),
                        'rows'        => 5,
                    ],
                ],
                'submit_text' => __('Enviar mensaje', 'flavor-chat-ia'),
                'success_msg' => __('Mensaje enviado correctamente. Te responderemos pronto.', 'flavor-chat-ia'),
                'action'      => 'empresarial_contacto',
            ],
        ];
    }
}

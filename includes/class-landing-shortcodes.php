<?php
/**
 * Manejador de Shortcodes para Landings
 *
 * Registra y procesa los shortcodes para renderizar templates de landing pages
 *
 * @package FlavorPlatform
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Clase para manejar shortcodes de landing pages
 */
class Flavor_Landing_Shortcodes {

    /**
     * Instancia singleton
     */
    private static $instance = null;

    /**
     * Mapeo de módulos a templates
     */
    private $template_map = [];

    /**
     * Mapeo de perfiles de app a módulos de landing
     */
    private $profile_to_module_map = [
        'grupo_consumo'        => 'grupos-consumo',
        'banco_tiempo'         => 'banco-tiempo',
        'comunidad'            => 'comunidades',
        'ayuntamiento'         => 'ayuntamiento',
        'barrio'               => 'ayuda-vecinal',
        'smart_village'        => 'ayuda-vecinal',
        'coworking'            => 'espacios-comunes',
        'marketplace'          => 'marketplace',
        'tienda'               => 'tienda',
        'restaurante'          => 'restaurante',
        'hosteleria'           => 'restaurante',
        'academia'             => 'cursos',
        'radio_comunitaria'    => 'podcast',
        'cooperativa'          => 'comunidades',
        'reciclaje_comunitario'=> 'reciclaje',
        'club_deportivo'       => 'eventos',
        'ong'                  => 'comunidades',
    ];

    /**
     * Obtiene la instancia singleton
     *
     * @return Flavor_Landing_Shortcodes
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
        $this->register_shortcodes();
    }

    /**
     * Obtiene el mapeo de módulos a templates (lazy loading)
     *
     * @return array
     */
    private function get_template_map() {
        if (!empty($this->template_map)) {
            return $this->template_map;
        }

        $this->template_map = [
            // Módulos con templates específicas
            'grupos-consumo' => [
                'sections' => [
                    ['template' => '_generic-hero', 'vars' => $this->get_grupos_consumo_hero()],
                    ['template' => '_generic-features', 'vars' => $this->get_grupos_consumo_como_funciona() + ['id_seccion' => 'como-funciona']],
                    ['template' => '_gc-grupos-activos', 'vars' => ['id_seccion' => 'grupos']],
                    ['template' => '_gc-productos-destacados', 'vars' => ['id_seccion' => 'productos']],
                    ['template' => '_gc-ciclo-actual', 'vars' => ['id_seccion' => 'ciclo']],
                    ['template' => '_generic-cta', 'vars' => $this->get_grupos_consumo_cta() + ['id_seccion' => 'unirse']],
                ],
            ],
            'banco-tiempo' => [
                'sections' => [
                    ['template' => '_generic-hero', 'vars' => $this->get_banco_tiempo_hero()],
                    ['template' => '_generic-grid', 'vars' => $this->get_banco_tiempo_servicios() + ['id_seccion' => 'servicios']],
                    ['template' => '_generic-cta', 'vars' => $this->get_banco_tiempo_cta() + ['id_seccion' => 'registro']],
                ],
            ],
            'ayuntamiento' => [
                'sections' => [
                    ['template' => '_generic-hero', 'vars' => $this->get_ayuntamiento_hero()],
                    ['template' => 'ayuntamiento-servicios', 'vars' => ['id_seccion' => 'servicios']],
                ],
            ],
            'comunidades' => [
                'sections' => [
                    ['template' => '_generic-hero', 'vars' => $this->get_comunidades_hero()],
                    ['template' => 'comunidad-eventos', 'vars' => ['id_seccion' => 'eventos']],
                ],
            ],
            'espacios-comunes' => [
                'sections' => [
                    ['template' => '_generic-hero', 'vars' => $this->get_espacios_hero()],
                    ['template' => 'coworking-espacios', 'vars' => ['id_seccion' => 'reservar']],
                ],
            ],
            'ayuda-vecinal' => [
                'sections' => [
                    ['template' => '_generic-hero', 'vars' => $this->get_ayuda_vecinal_hero()],
                    ['template' => 'barrio-servicios', 'vars' => $this->get_ayuda_vecinal_servicios() + ['id_seccion' => 'solicitudes']],
                    ['template' => '_generic-cta', 'vars' => $this->get_ayuda_vecinal_cta() + ['id_seccion' => 'nueva-solicitud']],
                ],
            ],
            'huertos-urbanos' => [
                'sections' => [
                    ['template' => '_generic-hero', 'vars' => $this->get_huertos_hero()],
                    ['template' => '_generic-grid', 'vars' => $this->get_huertos_parcelas() + ['id_seccion' => 'parcelas']],
                    ['template' => '_generic-mapa', 'vars' => $this->get_huertos_mapa() + ['id_seccion' => 'mapa']],
                ],
            ],
            'biblioteca' => [
                'sections' => [
                    ['template' => '_generic-hero', 'vars' => $this->get_biblioteca_hero()],
                    ['template' => '_generic-grid', 'vars' => $this->get_biblioteca_libros() + ['id_seccion' => 'catalogo']],
                ],
            ],
            'cursos' => [
                'sections' => [
                    ['template' => '_generic-hero', 'vars' => $this->get_cursos_hero()],
                    ['template' => '_generic-grid', 'vars' => $this->get_cursos_listado() + ['id_seccion' => 'cursos']],
                ],
            ],
            'eventos' => [
                'sections' => [
                    ['template' => '_generic-hero', 'vars' => $this->get_eventos_hero()],
                    ['template' => 'comunidad-eventos', 'vars' => ['id_seccion' => 'calendario']],
                ],
            ],
            'marketplace' => [
                'sections' => [
                    ['template' => '_generic-hero', 'vars' => $this->get_marketplace_hero()],
                    ['template' => 'marketplace-anuncios', 'vars' => ['id_seccion' => 'publicar']],
                ],
            ],
            'incidencias' => [
                'sections' => [
                    ['template' => '_generic-hero', 'vars' => $this->get_incidencias_hero()],
                    ['template' => '_generic-grid', 'vars' => $this->get_incidencias_tipos() + ['id_seccion' => 'reportar']],
                    ['template' => '_generic-cta', 'vars' => $this->get_incidencias_cta() + ['id_seccion' => 'nueva']],
                ],
            ],
            'bicicletas' => [
                'sections' => [
                    ['template' => '_generic-hero', 'vars' => $this->get_bicicletas_hero()],
                    ['template' => '_generic-grid', 'vars' => $this->get_bicicletas_estaciones() + ['id_seccion' => 'estaciones']],
                    ['template' => '_generic-mapa', 'vars' => $this->get_bicicletas_mapa() + ['id_seccion' => 'mapa-estaciones']],
                ],
            ],
            'reciclaje' => [
                'sections' => [
                    ['template' => '_generic-hero', 'vars' => $this->get_reciclaje_hero()],
                    ['template' => '_generic-grid', 'vars' => $this->get_reciclaje_puntos() + ['id_seccion' => 'puntos']],
                    ['template' => '_generic-mapa', 'vars' => $this->get_reciclaje_mapa() + ['id_seccion' => 'mapa-puntos']],
                ],
            ],

            // =========================================================
            // SECTORES EMPRESARIALES
            // =========================================================

            'restaurante' => [
                'sections' => [
                    ['template' => 'restaurante-landing', 'vars' => $this->get_restaurante_config()],
                    ['template' => 'restaurante-menu', 'vars' => $this->get_restaurante_menu_config()],
                    ['template' => 'restaurante-reservas', 'vars' => $this->get_restaurante_reservas_config()],
                ],
            ],
            'peluqueria' => [
                'sections' => [
                    ['template' => 'peluqueria-landing', 'vars' => $this->get_peluqueria_config()],
                ],
            ],
            'gimnasio' => [
                'sections' => [
                    ['template' => 'gimnasio-landing', 'vars' => $this->get_gimnasio_config()],
                ],
            ],
            'clinica' => [
                'sections' => [
                    ['template' => 'clinica-landing', 'vars' => $this->get_clinica_config()],
                ],
            ],
            'hotel' => [
                'sections' => [
                    ['template' => 'hotel-landing', 'vars' => $this->get_hotel_config()],
                ],
            ],
            'inmobiliaria' => [
                'sections' => [
                    ['template' => 'inmobiliaria-landing', 'vars' => $this->get_inmobiliaria_config()],
                ],
            ],
            'tienda' => [
                'sections' => [
                    ['template' => '_generic-hero', 'vars' => $this->get_tienda_hero()],
                    ['template' => 'tienda-productos', 'vars' => $this->get_tienda_productos() + ['id_seccion' => 'productos']],
                    ['template' => '_generic-cta', 'vars' => $this->get_tienda_cta() + ['id_seccion' => 'comprar']],
                ],
            ],
            'podcast' => [
                'sections' => [
                    ['template' => '_generic-hero', 'vars' => $this->get_podcast_hero()],
                    ['template' => '_generic-grid', 'vars' => $this->get_podcast_episodios() + ['id_seccion' => 'episodios']],
                ],
            ],
        ];

        return $this->template_map;
    }

    /**
     * Obtiene el mapeo público de templates (para editor visual)
     *
     * @return array
     */
    public function get_template_map_public() {
        return $this->get_template_map();
    }

    /**
     * Registra los shortcodes
     */
    private function register_shortcodes() {
        $shortcodes = [
            'flavor_landing' => 'render_landing',
            'flavor_section' => 'render_section',
            'flavor_grupos_consumo' => 'render_grupos_consumo',
            'flavor_banco_tiempo' => 'render_banco_tiempo',
        ];

        foreach ($shortcodes as $tag => $method) {
            if (!shortcode_exists($tag)) {
                add_shortcode($tag, [$this, $method]);
            }
        }
    }

    /**
     * Renderiza una landing page completa
     *
     * @param array $atts Atributos del shortcode
     * @return string
     */
    public function render_landing($atts) {
        $atts = shortcode_atts([
            'module' => '',
            'color' => '',
        ], $atts, 'flavor_landing');

        $module = sanitize_text_field($atts['module']);
        $color = sanitize_hex_color($atts['color']);

        // Si no se especifica módulo, detectar automáticamente del perfil activo
        if (empty($module)) {
            $module = $this->get_module_from_active_profile();
        }

        if (empty($module)) {
            return $this->render_error(__('Módulo no especificado y no se pudo detectar del perfil activo', FLAVOR_PLATFORM_TEXT_DOMAIN));
        }

        $template_map = $this->get_template_map();

        if (!isset($template_map[$module])) {
            return $this->render_error(sprintf(__('Módulo "%s" no encontrado', FLAVOR_PLATFORM_TEXT_DOMAIN), $module));
        }

        $config = $template_map[$module];
        $output = '<div class="flavor-landing flavor-landing-' . esc_attr($module) . '">';

        // Aplicar estilos globales de la landing
        $output .= $this->get_landing_styles($color);

        foreach ($config['sections'] as $section) {
            $template_file = $section['template'];
            $template_vars = $section['vars'] ?? [];

            // Aplicar color primario si se especificó
            if (!empty($color)) {
                $template_vars['color_primario'] = $color;
            }

            $output .= $this->load_template($template_file, $template_vars);
        }

        $output .= '</div>';

        return $output;
    }

    /**
     * Alias shortcode: [flavor_grupos_consumo]
     *
     * Renderiza la landing de Grupos de Consumo.
     *
     * @param array $atts Atributos del shortcode (opcional)
     * @return string
     */
    public function render_grupos_consumo($atts) {
        $atts = shortcode_atts([
            'color' => '',
        ], $atts, 'flavor_grupos_consumo');

        return $this->render_landing([
            'module' => 'grupos-consumo',
            'color' => $atts['color'],
        ]);
    }

    /**
     * Alias shortcode: [flavor_banco_tiempo]
     *
     * Renderiza la landing de Banco de Tiempo.
     *
     * @param array $atts Atributos del shortcode (opcional)
     * @return string
     */
    public function render_banco_tiempo($atts) {
        $atts = shortcode_atts([
            'color' => '',
        ], $atts, 'flavor_banco_tiempo');

        return $this->render_landing([
            'module' => 'banco-tiempo',
            'color' => $atts['color'],
        ]);
    }

    /**
     * Renderiza una sección individual
     *
     * @param array $atts Atributos del shortcode
     * @return string
     */
    public function render_section($atts) {
        $atts = shortcode_atts([
            'template' => '',
            'title' => '',
            'subtitle' => '',
            'color' => '',
        ], $atts, 'flavor_section');

        $template = sanitize_text_field($atts['template']);

        if (empty($template)) {
            return $this->render_error(__('Template no especificado', FLAVOR_PLATFORM_TEXT_DOMAIN));
        }

        $vars = [
            'titulo' => $atts['title'],
            'subtitulo' => $atts['subtitle'],
        ];

        if (!empty($atts['color'])) {
            $vars['color_primario'] = sanitize_hex_color($atts['color']);
        }

        return $this->load_template($template, $vars);
    }

    /**
     * Carga un template con variables
     *
     * @param string $template_name Nombre del template
     * @param array $vars Variables a pasar al template
     * @return string
     */
    private function load_template($template_name, $vars = []) {
        // Buscar en múltiples ubicaciones
        $posibles_rutas = [
            FLAVOR_PLATFORM_PATH . 'templates/components/landings/' . $template_name . '.php',
            FLAVOR_PLATFORM_PATH . 'templates/frontend/landing/' . $template_name . '.php',
            get_stylesheet_directory() . '/flavor/landing/' . $template_name . '.php',
        ];

        $template_path = null;
        foreach ($posibles_rutas as $ruta) {
            if (file_exists($ruta)) {
                $template_path = $ruta;
                break;
            }
        }

        if (!$template_path) {
            return $this->render_error(sprintf(__('Template "%s" no encontrado', FLAVOR_PLATFORM_TEXT_DOMAIN), $template_name));
        }

        // Extraer variables para el template
        extract($vars);

        ob_start();
        include $template_path;
        return ob_get_clean();
    }

    /**
     * Renderiza un mensaje de error
     *
     * @param string $message
     * @return string
     */
    private function render_error($message) {
        if (current_user_can('manage_options')) {
            return '<div class="flavor-landing-error" style="padding: 20px; background: #fee2e2; border: 1px solid #ef4444; border-radius: 8px; color: #b91c1c; margin: 20px 0;">'
                . '<strong>Error:</strong> ' . esc_html($message)
                . '</div>';
        }
        return '';
    }

    /**
     * Estilos globales para las landing pages
     *
     * @param string $color Color primario (opcional, si vacío usa el tema activo)
     * @return string
     */
    private function get_landing_styles($color = '') {
        // Si no se pasa color, usar el del tema activo
        if (empty($color)) {
            $primary_color = $this->get_theme_primary_color();
        } else {
            $primary_color = $color;
        }

        return '<style>
            .flavor-landing {
                --flavor-primary: ' . esc_attr($primary_color) . ';
                font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Oxygen-Sans, Ubuntu, Cantarell, "Helvetica Neue", sans-serif;
            }
            .flavor-landing .flavor-container {
                max-width: 1200px;
                margin: 0 auto;
                padding: 0 1.5rem;
            }
            .flavor-landing a {
                text-decoration: none;
            }
        </style>';
    }

    /**
     * Obtiene el color primario del tema activo
     *
     * @return string Color hexadecimal
     */
    private function get_theme_primary_color() {
        // Intentar obtener del Theme Manager
        if (class_exists('Flavor_Theme_Manager')) {
            $theme_manager = Flavor_Theme_Manager::get_instance();
            $active_theme_id = get_option('flavor_active_theme', 'default');
            $theme = $theme_manager->get_theme($active_theme_id);

            if ($theme && !empty($theme['variables']['--flavor-primary'])) {
                return $theme['variables']['--flavor-primary'];
            }
        }

        // Fallback al valor por defecto
        return '#3b82f6';
    }

    /**
     * Obtiene el módulo de landing correspondiente al perfil activo
     *
     * @return string Nombre del módulo de landing o vacío si no hay mapeo
     */
    private function get_module_from_active_profile() {
        // Obtener el perfil activo desde la configuración
        $configuracion = flavor_get_main_settings();
        $perfil_activo = $configuracion['app_profile'] ?? 'personalizado';

        // Buscar en el mapeo
        if (isset($this->profile_to_module_map[$perfil_activo])) {
            return $this->profile_to_module_map[$perfil_activo];
        }

        // Si el perfil es 'personalizado', intentar detectar del primer módulo activo
        if ($perfil_activo === 'personalizado') {
            $modulos_activos = $configuracion['active_modules'] ?? [];

            // Mapeo inverso: de módulos activos a landing modules
            $modulo_a_landing = [
                'grupos_consumo'    => 'grupos-consumo',
                'banco_tiempo'      => 'banco-tiempo',
                'comunidades'       => 'comunidades',
                'ayuntamiento'      => 'ayuntamiento',
                'ayuda_vecinal'     => 'ayuda-vecinal',
                'espacios_comunes'  => 'espacios-comunes',
                'marketplace'       => 'marketplace',
                'woocommerce'       => 'tienda',
                'cursos'            => 'cursos',
                'eventos'           => 'eventos',
                'reciclaje'         => 'reciclaje',
                'incidencias'       => 'incidencias',
                'bicicletas_compartidas' => 'bicicletas',
                'huertos_urbanos'   => 'huertos-urbanos',
                'biblioteca'        => 'biblioteca',
            ];

            foreach ($modulos_activos as $modulo) {
                if (isset($modulo_a_landing[$modulo])) {
                    return $modulo_a_landing[$modulo];
                }
            }
        }

        return '';
    }

    // =========================================================
    // CONFIGURACIONES DE MÓDULOS
    // =========================================================

    private function get_grupos_consumo_hero() {
        return [
            'titulo' => __('Grupos de Consumo', FLAVOR_PLATFORM_TEXT_DOMAIN),
            'subtitulo' => __('Consume local, apoya a productores cercanos y forma parte de una comunidad sostenible', FLAVOR_PLATFORM_TEXT_DOMAIN),
            'color_primario' => '#84cc16',
            'imagen' => '',
            'cta_texto' => __('Ver Grupos', FLAVOR_PLATFORM_TEXT_DOMAIN),
            'cta_url' => home_url('/mi-portal/grupos-consumo/'),
            'cta_secundario_texto' => __('Ver Productos', FLAVOR_PLATFORM_TEXT_DOMAIN),
            'cta_secundario_url' => home_url('/mi-portal/grupos-consumo/productos/'),
        ];
    }

    private function get_grupos_consumo_como_funciona() {
        return [
            'titulo' => __('¿Cómo funciona?', FLAVOR_PLATFORM_TEXT_DOMAIN),
            'subtitulo' => __('En 4 sencillos pasos puedes empezar a consumir productos locales', FLAVOR_PLATFORM_TEXT_DOMAIN),
            'items' => [
                [
                    'titulo' => __('1. Únete a un grupo', FLAVOR_PLATFORM_TEXT_DOMAIN),
                    'icono' => 'groups',
                    'descripcion' => __('Encuentra un grupo cerca de ti y solicita unirte', FLAVOR_PLATFORM_TEXT_DOMAIN),
                ],
                [
                    'titulo' => __('2. Explora productos', FLAVOR_PLATFORM_TEXT_DOMAIN),
                    'icono' => 'carrot',
                    'descripcion' => __('Descubre productos frescos de productores locales', FLAVOR_PLATFORM_TEXT_DOMAIN),
                ],
                [
                    'titulo' => __('3. Haz tu pedido', FLAVOR_PLATFORM_TEXT_DOMAIN),
                    'icono' => 'cart',
                    'descripcion' => __('Añade productos a tu cesta durante el ciclo de pedidos', FLAVOR_PLATFORM_TEXT_DOMAIN),
                ],
                [
                    'titulo' => __('4. Recoge tu cesta', FLAVOR_PLATFORM_TEXT_DOMAIN),
                    'icono' => 'location',
                    'descripcion' => __('Recoge tu pedido en el punto de entrega acordado', FLAVOR_PLATFORM_TEXT_DOMAIN),
                ],
            ],
            'color_primario' => '#84cc16',
        ];
    }

    private function get_grupos_consumo_productos() {
        return [
            'titulo' => __('Productos de Temporada', FLAVOR_PLATFORM_TEXT_DOMAIN),
            'color_primario' => '#84cc16',
        ];
    }

    private function get_grupos_consumo_cta() {
        return [
            'titulo' => __('¿Listo para consumir local?', FLAVOR_PLATFORM_TEXT_DOMAIN),
            'descripcion' => __('Únete a un grupo de consumo y empieza a disfrutar de productos frescos, de temporada y de productores cercanos', FLAVOR_PLATFORM_TEXT_DOMAIN),
            'boton_texto' => __('Unirme a un grupo', FLAVOR_PLATFORM_TEXT_DOMAIN),
            'boton_url' => home_url('/mi-portal/grupos-consumo/unirme/'),
            'boton_secundario_texto' => __('Ver catálogo', FLAVOR_PLATFORM_TEXT_DOMAIN),
            'boton_secundario_url' => home_url('/mi-portal/grupos-consumo/productos/'),
            'color_primario' => '#84cc16',
        ];
    }

    private function get_banco_tiempo_hero() {
        return [
            'titulo' => __('Banco de Tiempo', FLAVOR_PLATFORM_TEXT_DOMAIN),
            'subtitulo' => __('Intercambia servicios con tus vecinos. Tu tiempo vale tanto como el de cualquiera.', FLAVOR_PLATFORM_TEXT_DOMAIN),
            'color_primario' => '#8b5cf6',
            'imagen' => '',
            'cta_texto' => __('Ver Servicios', FLAVOR_PLATFORM_TEXT_DOMAIN),
            'cta_url' => '#servicios',
        ];
    }

    private function get_banco_tiempo_servicios() {
        return [
            'titulo' => __('Servicios Disponibles', FLAVOR_PLATFORM_TEXT_DOMAIN),
            'items' => [
                ['titulo' => __('Clases de idiomas', FLAVOR_PLATFORM_TEXT_DOMAIN), 'icono' => 'translation', 'descripcion' => __('Aprende con nativos del barrio', FLAVOR_PLATFORM_TEXT_DOMAIN)],
                ['titulo' => __('Reparaciones', FLAVOR_PLATFORM_TEXT_DOMAIN), 'icono' => 'admin-tools', 'descripcion' => __('Ayuda con bricolaje y hogar', FLAVOR_PLATFORM_TEXT_DOMAIN)],
                ['titulo' => __('Cuidado de niños', FLAVOR_PLATFORM_TEXT_DOMAIN), 'icono' => 'heart', 'descripcion' => __('Canguro entre vecinos', FLAVOR_PLATFORM_TEXT_DOMAIN)],
                ['titulo' => __('Informática', FLAVOR_PLATFORM_TEXT_DOMAIN), 'icono' => 'laptop', 'descripcion' => __('Ayuda con tecnología', FLAVOR_PLATFORM_TEXT_DOMAIN)],
            ],
            'color_primario' => '#8b5cf6',
        ];
    }

    private function get_banco_tiempo_cta() {
        return [
            'titulo' => __('Empieza a intercambiar tiempo', FLAVOR_PLATFORM_TEXT_DOMAIN),
            'descripcion' => __('Ofrece lo que sabes hacer y aprende de los demás', FLAVOR_PLATFORM_TEXT_DOMAIN),
            'boton_texto' => __('Crear cuenta', FLAVOR_PLATFORM_TEXT_DOMAIN),
            'boton_url' => wp_registration_url(),
            'color_primario' => '#8b5cf6',
        ];
    }

    private function get_ayuntamiento_hero() {
        return [
            'titulo' => __('Servicios Municipales', FLAVOR_PLATFORM_TEXT_DOMAIN),
            'subtitulo' => __('Tu ayuntamiento digital. Trámites, información y servicios a un clic.', FLAVOR_PLATFORM_TEXT_DOMAIN),
            'color_primario' => '#1d4ed8',
            'imagen' => '',
            'cta_texto' => __('Ver Servicios', FLAVOR_PLATFORM_TEXT_DOMAIN),
            'cta_url' => '#servicios',
        ];
    }

    private function get_comunidades_hero() {
        return [
            'titulo' => __('Comunidad', FLAVOR_PLATFORM_TEXT_DOMAIN),
            'subtitulo' => __('Conecta con tu comunidad, participa en eventos y conoce a tus vecinos', FLAVOR_PLATFORM_TEXT_DOMAIN),
            'color_primario' => '#f43f5e',
            'imagen' => '',
            'cta_texto' => __('Ver Eventos', FLAVOR_PLATFORM_TEXT_DOMAIN),
            'cta_url' => '#eventos',
        ];
    }

    private function get_espacios_hero() {
        return [
            'titulo' => __('Espacios Comunes', FLAVOR_PLATFORM_TEXT_DOMAIN),
            'subtitulo' => __('Reserva salas, espacios de trabajo y áreas comunitarias', FLAVOR_PLATFORM_TEXT_DOMAIN),
            'color_primario' => '#06b6d4',
            'imagen' => '',
            'cta_texto' => __('Reservar', FLAVOR_PLATFORM_TEXT_DOMAIN),
            'cta_url' => '#reservar',
        ];
    }

    private function get_ayuda_vecinal_hero() {
        return [
            'titulo' => __('Ayuda Vecinal', FLAVOR_PLATFORM_TEXT_DOMAIN),
            'subtitulo' => __('Pide ayuda o échale una mano a quien lo necesite. Juntos somos más fuertes.', FLAVOR_PLATFORM_TEXT_DOMAIN),
            'color_primario' => '#f97316',
            'imagen' => '',
            'cta_texto' => __('Ver Solicitudes', FLAVOR_PLATFORM_TEXT_DOMAIN),
            'cta_url' => '#solicitudes',
        ];
    }

    private function get_ayuda_vecinal_servicios() {
        return [
            'titulo' => __('Tipos de Ayuda', FLAVOR_PLATFORM_TEXT_DOMAIN),
            'color_primario' => '#f97316',
        ];
    }

    private function get_ayuda_vecinal_cta() {
        return [
            'titulo' => __('¿Necesitas ayuda?', FLAVOR_PLATFORM_TEXT_DOMAIN),
            'descripcion' => __('Publica tu solicitud y deja que la comunidad te eche una mano', FLAVOR_PLATFORM_TEXT_DOMAIN),
            'boton_texto' => __('Pedir Ayuda', FLAVOR_PLATFORM_TEXT_DOMAIN),
            'boton_url' => wp_registration_url(),
            'color_primario' => '#f97316',
        ];
    }

    private function get_huertos_hero() {
        return [
            'titulo' => __('Huertos Urbanos', FLAVOR_PLATFORM_TEXT_DOMAIN),
            'subtitulo' => __('Cultiva tus propios alimentos en la ciudad', FLAVOR_PLATFORM_TEXT_DOMAIN),
            'color_primario' => '#22c55e',
            'imagen' => '',
            'cta_texto' => __('Ver Parcelas', FLAVOR_PLATFORM_TEXT_DOMAIN),
            'cta_url' => '#parcelas',
        ];
    }

    private function get_huertos_parcelas() {
        return [
            'titulo' => __('Parcelas Disponibles', FLAVOR_PLATFORM_TEXT_DOMAIN),
            'items' => [
                ['titulo' => __('Parcela A1', FLAVOR_PLATFORM_TEXT_DOMAIN), 'icono' => 'location-alt', 'descripcion' => __('15m² - Zona Norte', FLAVOR_PLATFORM_TEXT_DOMAIN)],
                ['titulo' => __('Parcela B3', FLAVOR_PLATFORM_TEXT_DOMAIN), 'icono' => 'location-alt', 'descripcion' => __('20m² - Zona Sur', FLAVOR_PLATFORM_TEXT_DOMAIN)],
                ['titulo' => __('Parcela C2', FLAVOR_PLATFORM_TEXT_DOMAIN), 'icono' => 'location-alt', 'descripcion' => __('10m² - Zona Este', FLAVOR_PLATFORM_TEXT_DOMAIN)],
            ],
            'color_primario' => '#22c55e',
        ];
    }

    private function get_huertos_mapa() {
        return [
            'titulo' => __('Localización', FLAVOR_PLATFORM_TEXT_DOMAIN),
            'color_primario' => '#22c55e',
        ];
    }

    private function get_biblioteca_hero() {
        return [
            'titulo' => __('Biblioteca Comunitaria', FLAVOR_PLATFORM_TEXT_DOMAIN),
            'subtitulo' => __('Comparte y descubre libros con tus vecinos', FLAVOR_PLATFORM_TEXT_DOMAIN),
            'color_primario' => '#6366f1',
            'imagen' => '',
            'cta_texto' => __('Ver Catálogo', FLAVOR_PLATFORM_TEXT_DOMAIN),
            'cta_url' => '#catalogo',
        ];
    }

    private function get_biblioteca_libros() {
        return [
            'titulo' => __('Últimas Novedades', FLAVOR_PLATFORM_TEXT_DOMAIN),
            'items' => [
                ['titulo' => __('Cien años de soledad', FLAVOR_PLATFORM_TEXT_DOMAIN), 'icono' => 'book', 'descripcion' => __('Gabriel García Márquez', FLAVOR_PLATFORM_TEXT_DOMAIN)],
                ['titulo' => __('1984', FLAVOR_PLATFORM_TEXT_DOMAIN), 'icono' => 'book', 'descripcion' => __('George Orwell', FLAVOR_PLATFORM_TEXT_DOMAIN)],
                ['titulo' => __('El principito', FLAVOR_PLATFORM_TEXT_DOMAIN), 'icono' => 'book', 'descripcion' => __('Antoine de Saint-Exupéry', FLAVOR_PLATFORM_TEXT_DOMAIN)],
            ],
            'color_primario' => '#6366f1',
        ];
    }

    private function get_cursos_hero() {
        return [
            'titulo' => __('Cursos y Talleres', FLAVOR_PLATFORM_TEXT_DOMAIN),
            'subtitulo' => __('Aprende algo nuevo con los talleres de tu comunidad', FLAVOR_PLATFORM_TEXT_DOMAIN),
            'color_primario' => '#a855f7',
            'imagen' => '',
            'cta_texto' => __('Ver Cursos', FLAVOR_PLATFORM_TEXT_DOMAIN),
            'cta_url' => '#cursos',
        ];
    }

    private function get_cursos_listado() {
        return [
            'titulo' => __('Próximos Cursos', FLAVOR_PLATFORM_TEXT_DOMAIN),
            'items' => [
                ['titulo' => __('Huerto en casa', FLAVOR_PLATFORM_TEXT_DOMAIN), 'icono' => 'carrot', 'descripcion' => __('Sábado 10:00', FLAVOR_PLATFORM_TEXT_DOMAIN)],
                ['titulo' => __('Fotografía básica', FLAVOR_PLATFORM_TEXT_DOMAIN), 'icono' => 'camera', 'descripcion' => __('Domingo 11:00', FLAVOR_PLATFORM_TEXT_DOMAIN)],
                ['titulo' => __('Yoga para principiantes', FLAVOR_PLATFORM_TEXT_DOMAIN), 'icono' => 'heart', 'descripcion' => __('Lunes 18:00', FLAVOR_PLATFORM_TEXT_DOMAIN)],
            ],
            'color_primario' => '#a855f7',
        ];
    }

    private function get_eventos_hero() {
        return [
            'titulo' => __('Eventos', FLAVOR_PLATFORM_TEXT_DOMAIN),
            'subtitulo' => __('No te pierdas nada de lo que pasa en tu comunidad', FLAVOR_PLATFORM_TEXT_DOMAIN),
            'color_primario' => '#3b82f6',
            'imagen' => '',
            'cta_texto' => __('Ver Calendario', FLAVOR_PLATFORM_TEXT_DOMAIN),
            'cta_url' => '#calendario',
        ];
    }

    private function get_marketplace_hero() {
        return [
            'titulo' => __('Mercadillo Vecinal', FLAVOR_PLATFORM_TEXT_DOMAIN),
            'subtitulo' => __('Compra, vende e intercambia con tus vecinos', FLAVOR_PLATFORM_TEXT_DOMAIN),
            'color_primario' => '#f59e0b',
            'imagen' => '',
            'cta_texto' => __('Publicar Anuncio', FLAVOR_PLATFORM_TEXT_DOMAIN),
            'cta_url' => '#publicar',
        ];
    }

    private function get_incidencias_hero() {
        return [
            'titulo' => __('Incidencias', FLAVOR_PLATFORM_TEXT_DOMAIN),
            'subtitulo' => __('Reporta problemas en tu barrio y ayuda a mejorar la comunidad', FLAVOR_PLATFORM_TEXT_DOMAIN),
            'color_primario' => '#e11d48',
            'imagen' => '',
            'cta_texto' => __('Reportar', FLAVOR_PLATFORM_TEXT_DOMAIN),
            'cta_url' => '#reportar',
        ];
    }

    private function get_incidencias_tipos() {
        return [
            'titulo' => __('Tipos de Incidencias', FLAVOR_PLATFORM_TEXT_DOMAIN),
            'items' => [
                ['titulo' => __('Alumbrado', FLAVOR_PLATFORM_TEXT_DOMAIN), 'icono' => 'lightbulb', 'descripcion' => __('Farolas y luz pública', FLAVOR_PLATFORM_TEXT_DOMAIN)],
                ['titulo' => __('Limpieza', FLAVOR_PLATFORM_TEXT_DOMAIN), 'icono' => 'trash', 'descripcion' => __('Basura y residuos', FLAVOR_PLATFORM_TEXT_DOMAIN)],
                ['titulo' => __('Vías públicas', FLAVOR_PLATFORM_TEXT_DOMAIN), 'icono' => 'location', 'descripcion' => __('Baches y aceras', FLAVOR_PLATFORM_TEXT_DOMAIN)],
                ['titulo' => __('Ruidos', FLAVOR_PLATFORM_TEXT_DOMAIN), 'icono' => 'megaphone', 'descripcion' => __('Contaminación acústica', FLAVOR_PLATFORM_TEXT_DOMAIN)],
            ],
            'color_primario' => '#e11d48',
        ];
    }

    private function get_incidencias_cta() {
        return [
            'titulo' => __('¿Ves algo que no funciona?', FLAVOR_PLATFORM_TEXT_DOMAIN),
            'descripcion' => __('Reporta la incidencia y haremos seguimiento hasta que se resuelva', FLAVOR_PLATFORM_TEXT_DOMAIN),
            'boton_texto' => __('Nueva Incidencia', FLAVOR_PLATFORM_TEXT_DOMAIN),
            'boton_url' => wp_registration_url(),
            'color_primario' => '#e11d48',
        ];
    }

    private function get_bicicletas_hero() {
        return [
            'titulo' => __('Bicicletas Compartidas', FLAVOR_PLATFORM_TEXT_DOMAIN),
            'subtitulo' => __('Muévete de forma sostenible por tu ciudad', FLAVOR_PLATFORM_TEXT_DOMAIN),
            'color_primario' => '#a3e635',
            'imagen' => '',
            'cta_texto' => __('Ver Estaciones', FLAVOR_PLATFORM_TEXT_DOMAIN),
            'cta_url' => '#estaciones',
        ];
    }

    private function get_bicicletas_estaciones() {
        return [
            'titulo' => __('Estaciones Cercanas', FLAVOR_PLATFORM_TEXT_DOMAIN),
            'items' => [
                ['titulo' => __('Plaza Mayor', FLAVOR_PLATFORM_TEXT_DOMAIN), 'icono' => 'location-alt', 'descripcion' => __('8 bicis disponibles', FLAVOR_PLATFORM_TEXT_DOMAIN)],
                ['titulo' => __('Estación de tren', FLAVOR_PLATFORM_TEXT_DOMAIN), 'icono' => 'location-alt', 'descripcion' => __('5 bicis disponibles', FLAVOR_PLATFORM_TEXT_DOMAIN)],
                ['titulo' => __('Parque Central', FLAVOR_PLATFORM_TEXT_DOMAIN), 'icono' => 'location-alt', 'descripcion' => __('12 bicis disponibles', FLAVOR_PLATFORM_TEXT_DOMAIN)],
            ],
            'color_primario' => '#a3e635',
        ];
    }

    private function get_bicicletas_mapa() {
        return [
            'titulo' => __('Mapa de Estaciones', FLAVOR_PLATFORM_TEXT_DOMAIN),
            'color_primario' => '#a3e635',
        ];
    }

    private function get_reciclaje_hero() {
        return [
            'titulo' => __('Puntos de Reciclaje', FLAVOR_PLATFORM_TEXT_DOMAIN),
            'subtitulo' => __('Encuentra dónde reciclar cada tipo de residuo', FLAVOR_PLATFORM_TEXT_DOMAIN),
            'color_primario' => '#10b981',
            'imagen' => '',
            'cta_texto' => __('Ver Puntos', FLAVOR_PLATFORM_TEXT_DOMAIN),
            'cta_url' => '#puntos',
        ];
    }

    private function get_reciclaje_puntos() {
        return [
            'titulo' => __('Puntos de Recogida', FLAVOR_PLATFORM_TEXT_DOMAIN),
            'items' => [
                ['titulo' => __('Contenedores', FLAVOR_PLATFORM_TEXT_DOMAIN), 'icono' => 'archive', 'descripcion' => __('Vidrio, papel, plástico', FLAVOR_PLATFORM_TEXT_DOMAIN)],
                ['titulo' => __('Punto Limpio', FLAVOR_PLATFORM_TEXT_DOMAIN), 'icono' => 'trash', 'descripcion' => __('Residuos especiales', FLAVOR_PLATFORM_TEXT_DOMAIN)],
                ['titulo' => __('Textil', FLAVOR_PLATFORM_TEXT_DOMAIN), 'icono' => 'universal-access', 'descripcion' => __('Ropa y calzado', FLAVOR_PLATFORM_TEXT_DOMAIN)],
            ],
            'color_primario' => '#10b981',
        ];
    }

    private function get_reciclaje_mapa() {
        return [
            'titulo' => __('Localiza tu punto más cercano', FLAVOR_PLATFORM_TEXT_DOMAIN),
            'color_primario' => '#10b981',
        ];
    }

    // =========================================================
    // CONFIGURACIONES SECTORES EMPRESARIALES
    // =========================================================

    private function get_restaurante_config() {
        return [
            'nombre' => __('Restaurante La Buena Mesa', FLAVOR_PLATFORM_TEXT_DOMAIN),
            'eslogan' => __('Cocina tradicional con un toque moderno', FLAVOR_PLATFORM_TEXT_DOMAIN),
            'telefono' => '912 345 678',
            'direccion' => __('Calle Mayor, 45 - Centro', FLAVOR_PLATFORM_TEXT_DOMAIN),
            'color_primario' => '#b91c1c',
            'horario' => __('Lun-Dom: 13:00-16:00 / 20:00-23:30', FLAVOR_PLATFORM_TEXT_DOMAIN),
        ];
    }

    private function get_restaurante_menu_config() {
        return [
            'color_primario' => '#b91c1c',
        ];
    }

    private function get_restaurante_reservas_config() {
        return [
            'color_primario' => '#b91c1c',
        ];
    }

    private function get_peluqueria_config() {
        return [
            'nombre' => __('Salón Belleza & Estilo', FLAVOR_PLATFORM_TEXT_DOMAIN),
            'eslogan' => __('Tu imagen, nuestra pasión', FLAVOR_PLATFORM_TEXT_DOMAIN),
            'telefono' => '912 345 678',
            'color_primario' => '#be185d',
        ];
    }

    private function get_gimnasio_config() {
        return [
            'nombre' => __('Fitness Center', FLAVOR_PLATFORM_TEXT_DOMAIN),
            'eslogan' => __('Transforma tu cuerpo, cambia tu vida', FLAVOR_PLATFORM_TEXT_DOMAIN),
            'telefono' => '912 345 678',
            'color_primario' => '#ea580c',
        ];
    }

    private function get_clinica_config() {
        return [
            'nombre' => __('Clínica Salud Integral', FLAVOR_PLATFORM_TEXT_DOMAIN),
            'eslogan' => __('Tu salud en las mejores manos', FLAVOR_PLATFORM_TEXT_DOMAIN),
            'telefono' => '912 345 678',
            'color_primario' => '#0891b2',
        ];
    }

    private function get_hotel_config() {
        return [
            'nombre' => __('Hotel Boutique', FLAVOR_PLATFORM_TEXT_DOMAIN),
            'eslogan' => __('Tu hogar lejos de casa', FLAVOR_PLATFORM_TEXT_DOMAIN),
            'telefono' => '912 345 678',
            'direccion' => __('Avenida Principal, 123', FLAVOR_PLATFORM_TEXT_DOMAIN),
            'color_primario' => '#7c3aed',
            'estrellas' => 4,
        ];
    }

    private function get_inmobiliaria_config() {
        return [
            'nombre' => __('Inmobiliaria Premium', FLAVOR_PLATFORM_TEXT_DOMAIN),
            'eslogan' => __('Encuentra tu hogar perfecto', FLAVOR_PLATFORM_TEXT_DOMAIN),
            'telefono' => '912 345 678',
            'color_primario' => '#059669',
        ];
    }

    // =========================================================
    // TIENDA ONLINE
    // =========================================================

    private function get_tienda_hero() {
        return [
            'titulo' => __('Nuestra Tienda', FLAVOR_PLATFORM_TEXT_DOMAIN),
            'subtitulo' => __('Descubre nuestros productos y ofertas especiales', FLAVOR_PLATFORM_TEXT_DOMAIN),
            'color_primario' => '#00a0d2',
            'imagen' => '',
            'cta_texto' => __('Ver Productos', FLAVOR_PLATFORM_TEXT_DOMAIN),
            'cta_url' => '#productos',
        ];
    }

    private function get_tienda_productos() {
        return [
            'titulo' => __('Productos Destacados', FLAVOR_PLATFORM_TEXT_DOMAIN),
            'color_primario' => '#00a0d2',
        ];
    }

    private function get_tienda_cta() {
        return [
            'titulo' => __('¿Primera compra?', FLAVOR_PLATFORM_TEXT_DOMAIN),
            'descripcion' => __('Regístrate y obtén un descuento en tu primer pedido', FLAVOR_PLATFORM_TEXT_DOMAIN),
            'boton_texto' => __('Registrarse', FLAVOR_PLATFORM_TEXT_DOMAIN),
            'boton_url' => wp_registration_url(),
            'color_primario' => '#00a0d2',
        ];
    }

    // =========================================================
    // PODCAST / RADIO
    // =========================================================

    private function get_podcast_hero() {
        return [
            'titulo' => __('Podcast Comunitario', FLAVOR_PLATFORM_TEXT_DOMAIN),
            'subtitulo' => __('Escucha las voces de nuestra comunidad', FLAVOR_PLATFORM_TEXT_DOMAIN),
            'color_primario' => '#dc2626',
            'imagen' => '',
            'cta_texto' => __('Últimos Episodios', FLAVOR_PLATFORM_TEXT_DOMAIN),
            'cta_url' => '#episodios',
        ];
    }

    private function get_podcast_episodios() {
        return [
            'titulo' => __('Últimos Episodios', FLAVOR_PLATFORM_TEXT_DOMAIN),
            'items' => [
                ['titulo' => __('Historias del barrio', FLAVOR_PLATFORM_TEXT_DOMAIN), 'icono' => 'microphone', 'descripcion' => __('45 min', FLAVOR_PLATFORM_TEXT_DOMAIN)],
                ['titulo' => __('Entrevista: Comercio local', FLAVOR_PLATFORM_TEXT_DOMAIN), 'icono' => 'microphone', 'descripcion' => __('30 min', FLAVOR_PLATFORM_TEXT_DOMAIN)],
                ['titulo' => __('Cultura y tradiciones', FLAVOR_PLATFORM_TEXT_DOMAIN), 'icono' => 'microphone', 'descripcion' => __('38 min', FLAVOR_PLATFORM_TEXT_DOMAIN)],
            ],
            'color_primario' => '#dc2626',
        ];
    }
}

// Inicializar el singleton para registrar los shortcodes
Flavor_Landing_Shortcodes::get_instance();

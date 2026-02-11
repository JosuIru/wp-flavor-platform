<?php
/**
 * Manejador de Shortcodes para Landings
 *
 * Registra y procesa los shortcodes para renderizar templates de landing pages
 *
 * @package FlavorChatIA
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
        add_shortcode('flavor_landing', [$this, 'render_landing']);
        add_shortcode('flavor_section', [$this, 'render_section']);
        add_shortcode('flavor_grupos_consumo', [$this, 'render_grupos_consumo']);
        add_shortcode('flavor_banco_tiempo', [$this, 'render_banco_tiempo']);
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
            return $this->render_error(__('Módulo no especificado y no se pudo detectar del perfil activo', 'flavor-chat-ia'));
        }

        $template_map = $this->get_template_map();

        if (!isset($template_map[$module])) {
            return $this->render_error(sprintf(__('Módulo "%s" no encontrado', 'flavor-chat-ia'), $module));
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
            return $this->render_error(__('Template no especificado', 'flavor-chat-ia'));
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
            FLAVOR_CHAT_IA_PATH . 'templates/components/landings/' . $template_name . '.php',
            FLAVOR_CHAT_IA_PATH . 'templates/frontend/landing/' . $template_name . '.php',
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
            return $this->render_error(sprintf(__('Template "%s" no encontrado', 'flavor-chat-ia'), $template_name));
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
        $configuracion = get_option('flavor_chat_ia_settings', []);
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
            'titulo' => __('Grupos de Consumo', 'flavor-chat-ia'),
            'subtitulo' => __('Consume local, apoya a productores cercanos y forma parte de una comunidad sostenible', 'flavor-chat-ia'),
            'color_primario' => '#84cc16',
            'imagen' => '',
            'cta_texto' => __('Ver Grupos', 'flavor-chat-ia'),
            'cta_url' => home_url('/grupos-consumo/'),
            'cta_secundario_texto' => __('Ver Productos', 'flavor-chat-ia'),
            'cta_secundario_url' => home_url('/grupos-consumo/productos/'),
        ];
    }

    private function get_grupos_consumo_como_funciona() {
        return [
            'titulo' => __('¿Cómo funciona?', 'flavor-chat-ia'),
            'subtitulo' => __('En 4 sencillos pasos puedes empezar a consumir productos locales', 'flavor-chat-ia'),
            'items' => [
                [
                    'titulo' => __('1. Únete a un grupo', 'flavor-chat-ia'),
                    'icono' => 'groups',
                    'descripcion' => __('Encuentra un grupo cerca de ti y solicita unirte', 'flavor-chat-ia'),
                ],
                [
                    'titulo' => __('2. Explora productos', 'flavor-chat-ia'),
                    'icono' => 'carrot',
                    'descripcion' => __('Descubre productos frescos de productores locales', 'flavor-chat-ia'),
                ],
                [
                    'titulo' => __('3. Haz tu pedido', 'flavor-chat-ia'),
                    'icono' => 'cart',
                    'descripcion' => __('Añade productos a tu cesta durante el ciclo de pedidos', 'flavor-chat-ia'),
                ],
                [
                    'titulo' => __('4. Recoge tu cesta', 'flavor-chat-ia'),
                    'icono' => 'location',
                    'descripcion' => __('Recoge tu pedido en el punto de entrega acordado', 'flavor-chat-ia'),
                ],
            ],
            'color_primario' => '#84cc16',
        ];
    }

    private function get_grupos_consumo_productos() {
        return [
            'titulo' => __('Productos de Temporada', 'flavor-chat-ia'),
            'color_primario' => '#84cc16',
        ];
    }

    private function get_grupos_consumo_cta() {
        return [
            'titulo' => __('¿Listo para consumir local?', 'flavor-chat-ia'),
            'descripcion' => __('Únete a un grupo de consumo y empieza a disfrutar de productos frescos, de temporada y de productores cercanos', 'flavor-chat-ia'),
            'boton_texto' => __('Unirme a un grupo', 'flavor-chat-ia'),
            'boton_url' => home_url('/grupos-consumo/unirme/'),
            'boton_secundario_texto' => __('Ver catálogo', 'flavor-chat-ia'),
            'boton_secundario_url' => home_url('/grupos-consumo/productos/'),
            'color_primario' => '#84cc16',
        ];
    }

    private function get_banco_tiempo_hero() {
        return [
            'titulo' => __('Banco de Tiempo', 'flavor-chat-ia'),
            'subtitulo' => __('Intercambia servicios con tus vecinos. Tu tiempo vale tanto como el de cualquiera.', 'flavor-chat-ia'),
            'color_primario' => '#8b5cf6',
            'imagen' => '',
            'cta_texto' => __('Ver Servicios', 'flavor-chat-ia'),
            'cta_url' => '#servicios',
        ];
    }

    private function get_banco_tiempo_servicios() {
        return [
            'titulo' => __('Servicios Disponibles', 'flavor-chat-ia'),
            'items' => [
                ['titulo' => __('Clases de idiomas', 'flavor-chat-ia'), 'icono' => 'translation', 'descripcion' => __('Aprende con nativos del barrio', 'flavor-chat-ia')],
                ['titulo' => __('Reparaciones', 'flavor-chat-ia'), 'icono' => 'admin-tools', 'descripcion' => __('Ayuda con bricolaje y hogar', 'flavor-chat-ia')],
                ['titulo' => __('Cuidado de niños', 'flavor-chat-ia'), 'icono' => 'heart', 'descripcion' => __('Canguro entre vecinos', 'flavor-chat-ia')],
                ['titulo' => __('Informática', 'flavor-chat-ia'), 'icono' => 'laptop', 'descripcion' => __('Ayuda con tecnología', 'flavor-chat-ia')],
            ],
            'color_primario' => '#8b5cf6',
        ];
    }

    private function get_banco_tiempo_cta() {
        return [
            'titulo' => __('Empieza a intercambiar tiempo', 'flavor-chat-ia'),
            'descripcion' => __('Ofrece lo que sabes hacer y aprende de los demás', 'flavor-chat-ia'),
            'boton_texto' => __('Crear cuenta', 'flavor-chat-ia'),
            'boton_url' => wp_registration_url(),
            'color_primario' => '#8b5cf6',
        ];
    }

    private function get_ayuntamiento_hero() {
        return [
            'titulo' => __('Servicios Municipales', 'flavor-chat-ia'),
            'subtitulo' => __('Tu ayuntamiento digital. Trámites, información y servicios a un clic.', 'flavor-chat-ia'),
            'color_primario' => '#1d4ed8',
            'imagen' => '',
            'cta_texto' => __('Ver Servicios', 'flavor-chat-ia'),
            'cta_url' => '#servicios',
        ];
    }

    private function get_comunidades_hero() {
        return [
            'titulo' => __('Comunidad', 'flavor-chat-ia'),
            'subtitulo' => __('Conecta con tu comunidad, participa en eventos y conoce a tus vecinos', 'flavor-chat-ia'),
            'color_primario' => '#f43f5e',
            'imagen' => '',
            'cta_texto' => __('Ver Eventos', 'flavor-chat-ia'),
            'cta_url' => '#eventos',
        ];
    }

    private function get_espacios_hero() {
        return [
            'titulo' => __('Espacios Comunes', 'flavor-chat-ia'),
            'subtitulo' => __('Reserva salas, espacios de trabajo y áreas comunitarias', 'flavor-chat-ia'),
            'color_primario' => '#06b6d4',
            'imagen' => '',
            'cta_texto' => __('Reservar', 'flavor-chat-ia'),
            'cta_url' => '#reservar',
        ];
    }

    private function get_ayuda_vecinal_hero() {
        return [
            'titulo' => __('Ayuda Vecinal', 'flavor-chat-ia'),
            'subtitulo' => __('Pide ayuda o échale una mano a quien lo necesite. Juntos somos más fuertes.', 'flavor-chat-ia'),
            'color_primario' => '#f97316',
            'imagen' => '',
            'cta_texto' => __('Ver Solicitudes', 'flavor-chat-ia'),
            'cta_url' => '#solicitudes',
        ];
    }

    private function get_ayuda_vecinal_servicios() {
        return [
            'titulo' => __('Tipos de Ayuda', 'flavor-chat-ia'),
            'color_primario' => '#f97316',
        ];
    }

    private function get_ayuda_vecinal_cta() {
        return [
            'titulo' => __('¿Necesitas ayuda?', 'flavor-chat-ia'),
            'descripcion' => __('Publica tu solicitud y deja que la comunidad te eche una mano', 'flavor-chat-ia'),
            'boton_texto' => __('Pedir Ayuda', 'flavor-chat-ia'),
            'boton_url' => wp_registration_url(),
            'color_primario' => '#f97316',
        ];
    }

    private function get_huertos_hero() {
        return [
            'titulo' => __('Huertos Urbanos', 'flavor-chat-ia'),
            'subtitulo' => __('Cultiva tus propios alimentos en la ciudad', 'flavor-chat-ia'),
            'color_primario' => '#22c55e',
            'imagen' => '',
            'cta_texto' => __('Ver Parcelas', 'flavor-chat-ia'),
            'cta_url' => '#parcelas',
        ];
    }

    private function get_huertos_parcelas() {
        return [
            'titulo' => __('Parcelas Disponibles', 'flavor-chat-ia'),
            'items' => [
                ['titulo' => __('Parcela A1', 'flavor-chat-ia'), 'icono' => 'location-alt', 'descripcion' => __('15m² - Zona Norte', 'flavor-chat-ia')],
                ['titulo' => __('Parcela B3', 'flavor-chat-ia'), 'icono' => 'location-alt', 'descripcion' => __('20m² - Zona Sur', 'flavor-chat-ia')],
                ['titulo' => __('Parcela C2', 'flavor-chat-ia'), 'icono' => 'location-alt', 'descripcion' => __('10m² - Zona Este', 'flavor-chat-ia')],
            ],
            'color_primario' => '#22c55e',
        ];
    }

    private function get_huertos_mapa() {
        return [
            'titulo' => __('Localización', 'flavor-chat-ia'),
            'color_primario' => '#22c55e',
        ];
    }

    private function get_biblioteca_hero() {
        return [
            'titulo' => __('Biblioteca Comunitaria', 'flavor-chat-ia'),
            'subtitulo' => __('Comparte y descubre libros con tus vecinos', 'flavor-chat-ia'),
            'color_primario' => '#6366f1',
            'imagen' => '',
            'cta_texto' => __('Ver Catálogo', 'flavor-chat-ia'),
            'cta_url' => '#catalogo',
        ];
    }

    private function get_biblioteca_libros() {
        return [
            'titulo' => __('Últimas Novedades', 'flavor-chat-ia'),
            'items' => [
                ['titulo' => __('Cien años de soledad', 'flavor-chat-ia'), 'icono' => 'book', 'descripcion' => __('Gabriel García Márquez', 'flavor-chat-ia')],
                ['titulo' => __('1984', 'flavor-chat-ia'), 'icono' => 'book', 'descripcion' => __('George Orwell', 'flavor-chat-ia')],
                ['titulo' => __('El principito', 'flavor-chat-ia'), 'icono' => 'book', 'descripcion' => __('Antoine de Saint-Exupéry', 'flavor-chat-ia')],
            ],
            'color_primario' => '#6366f1',
        ];
    }

    private function get_cursos_hero() {
        return [
            'titulo' => __('Cursos y Talleres', 'flavor-chat-ia'),
            'subtitulo' => __('Aprende algo nuevo con los talleres de tu comunidad', 'flavor-chat-ia'),
            'color_primario' => '#a855f7',
            'imagen' => '',
            'cta_texto' => __('Ver Cursos', 'flavor-chat-ia'),
            'cta_url' => '#cursos',
        ];
    }

    private function get_cursos_listado() {
        return [
            'titulo' => __('Próximos Cursos', 'flavor-chat-ia'),
            'items' => [
                ['titulo' => __('Huerto en casa', 'flavor-chat-ia'), 'icono' => 'carrot', 'descripcion' => __('Sábado 10:00', 'flavor-chat-ia')],
                ['titulo' => __('Fotografía básica', 'flavor-chat-ia'), 'icono' => 'camera', 'descripcion' => __('Domingo 11:00', 'flavor-chat-ia')],
                ['titulo' => __('Yoga para principiantes', 'flavor-chat-ia'), 'icono' => 'heart', 'descripcion' => __('Lunes 18:00', 'flavor-chat-ia')],
            ],
            'color_primario' => '#a855f7',
        ];
    }

    private function get_eventos_hero() {
        return [
            'titulo' => __('Eventos', 'flavor-chat-ia'),
            'subtitulo' => __('No te pierdas nada de lo que pasa en tu comunidad', 'flavor-chat-ia'),
            'color_primario' => '#3b82f6',
            'imagen' => '',
            'cta_texto' => __('Ver Calendario', 'flavor-chat-ia'),
            'cta_url' => '#calendario',
        ];
    }

    private function get_marketplace_hero() {
        return [
            'titulo' => __('Mercadillo Vecinal', 'flavor-chat-ia'),
            'subtitulo' => __('Compra, vende e intercambia con tus vecinos', 'flavor-chat-ia'),
            'color_primario' => '#f59e0b',
            'imagen' => '',
            'cta_texto' => __('Publicar Anuncio', 'flavor-chat-ia'),
            'cta_url' => '#publicar',
        ];
    }

    private function get_incidencias_hero() {
        return [
            'titulo' => __('Incidencias', 'flavor-chat-ia'),
            'subtitulo' => __('Reporta problemas en tu barrio y ayuda a mejorar la comunidad', 'flavor-chat-ia'),
            'color_primario' => '#e11d48',
            'imagen' => '',
            'cta_texto' => __('Reportar', 'flavor-chat-ia'),
            'cta_url' => '#reportar',
        ];
    }

    private function get_incidencias_tipos() {
        return [
            'titulo' => __('Tipos de Incidencias', 'flavor-chat-ia'),
            'items' => [
                ['titulo' => __('Alumbrado', 'flavor-chat-ia'), 'icono' => 'lightbulb', 'descripcion' => __('Farolas y luz pública', 'flavor-chat-ia')],
                ['titulo' => __('Limpieza', 'flavor-chat-ia'), 'icono' => 'trash', 'descripcion' => __('Basura y residuos', 'flavor-chat-ia')],
                ['titulo' => __('Vías públicas', 'flavor-chat-ia'), 'icono' => 'location', 'descripcion' => __('Baches y aceras', 'flavor-chat-ia')],
                ['titulo' => __('Ruidos', 'flavor-chat-ia'), 'icono' => 'megaphone', 'descripcion' => __('Contaminación acústica', 'flavor-chat-ia')],
            ],
            'color_primario' => '#e11d48',
        ];
    }

    private function get_incidencias_cta() {
        return [
            'titulo' => __('¿Ves algo que no funciona?', 'flavor-chat-ia'),
            'descripcion' => __('Reporta la incidencia y haremos seguimiento hasta que se resuelva', 'flavor-chat-ia'),
            'boton_texto' => __('Nueva Incidencia', 'flavor-chat-ia'),
            'boton_url' => wp_registration_url(),
            'color_primario' => '#e11d48',
        ];
    }

    private function get_bicicletas_hero() {
        return [
            'titulo' => __('Bicicletas Compartidas', 'flavor-chat-ia'),
            'subtitulo' => __('Muévete de forma sostenible por tu ciudad', 'flavor-chat-ia'),
            'color_primario' => '#a3e635',
            'imagen' => '',
            'cta_texto' => __('Ver Estaciones', 'flavor-chat-ia'),
            'cta_url' => '#estaciones',
        ];
    }

    private function get_bicicletas_estaciones() {
        return [
            'titulo' => __('Estaciones Cercanas', 'flavor-chat-ia'),
            'items' => [
                ['titulo' => __('Plaza Mayor', 'flavor-chat-ia'), 'icono' => 'location-alt', 'descripcion' => __('8 bicis disponibles', 'flavor-chat-ia')],
                ['titulo' => __('Estación de tren', 'flavor-chat-ia'), 'icono' => 'location-alt', 'descripcion' => __('5 bicis disponibles', 'flavor-chat-ia')],
                ['titulo' => __('Parque Central', 'flavor-chat-ia'), 'icono' => 'location-alt', 'descripcion' => __('12 bicis disponibles', 'flavor-chat-ia')],
            ],
            'color_primario' => '#a3e635',
        ];
    }

    private function get_bicicletas_mapa() {
        return [
            'titulo' => __('Mapa de Estaciones', 'flavor-chat-ia'),
            'color_primario' => '#a3e635',
        ];
    }

    private function get_reciclaje_hero() {
        return [
            'titulo' => __('Puntos de Reciclaje', 'flavor-chat-ia'),
            'subtitulo' => __('Encuentra dónde reciclar cada tipo de residuo', 'flavor-chat-ia'),
            'color_primario' => '#10b981',
            'imagen' => '',
            'cta_texto' => __('Ver Puntos', 'flavor-chat-ia'),
            'cta_url' => '#puntos',
        ];
    }

    private function get_reciclaje_puntos() {
        return [
            'titulo' => __('Puntos de Recogida', 'flavor-chat-ia'),
            'items' => [
                ['titulo' => __('Contenedores', 'flavor-chat-ia'), 'icono' => 'archive', 'descripcion' => __('Vidrio, papel, plástico', 'flavor-chat-ia')],
                ['titulo' => __('Punto Limpio', 'flavor-chat-ia'), 'icono' => 'trash', 'descripcion' => __('Residuos especiales', 'flavor-chat-ia')],
                ['titulo' => __('Textil', 'flavor-chat-ia'), 'icono' => 'universal-access', 'descripcion' => __('Ropa y calzado', 'flavor-chat-ia')],
            ],
            'color_primario' => '#10b981',
        ];
    }

    private function get_reciclaje_mapa() {
        return [
            'titulo' => __('Localiza tu punto más cercano', 'flavor-chat-ia'),
            'color_primario' => '#10b981',
        ];
    }

    // =========================================================
    // CONFIGURACIONES SECTORES EMPRESARIALES
    // =========================================================

    private function get_restaurante_config() {
        return [
            'nombre' => __('Restaurante La Buena Mesa', 'flavor-chat-ia'),
            'eslogan' => __('Cocina tradicional con un toque moderno', 'flavor-chat-ia'),
            'telefono' => '912 345 678',
            'direccion' => __('Calle Mayor, 45 - Centro', 'flavor-chat-ia'),
            'color_primario' => '#b91c1c',
            'horario' => __('Lun-Dom: 13:00-16:00 / 20:00-23:30', 'flavor-chat-ia'),
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
            'nombre' => __('Salón Belleza & Estilo', 'flavor-chat-ia'),
            'eslogan' => __('Tu imagen, nuestra pasión', 'flavor-chat-ia'),
            'telefono' => '912 345 678',
            'color_primario' => '#be185d',
        ];
    }

    private function get_gimnasio_config() {
        return [
            'nombre' => __('Fitness Center', 'flavor-chat-ia'),
            'eslogan' => __('Transforma tu cuerpo, cambia tu vida', 'flavor-chat-ia'),
            'telefono' => '912 345 678',
            'color_primario' => '#ea580c',
        ];
    }

    private function get_clinica_config() {
        return [
            'nombre' => __('Clínica Salud Integral', 'flavor-chat-ia'),
            'eslogan' => __('Tu salud en las mejores manos', 'flavor-chat-ia'),
            'telefono' => '912 345 678',
            'color_primario' => '#0891b2',
        ];
    }

    private function get_hotel_config() {
        return [
            'nombre' => __('Hotel Boutique', 'flavor-chat-ia'),
            'eslogan' => __('Tu hogar lejos de casa', 'flavor-chat-ia'),
            'telefono' => '912 345 678',
            'direccion' => __('Avenida Principal, 123', 'flavor-chat-ia'),
            'color_primario' => '#7c3aed',
            'estrellas' => 4,
        ];
    }

    private function get_inmobiliaria_config() {
        return [
            'nombre' => __('Inmobiliaria Premium', 'flavor-chat-ia'),
            'eslogan' => __('Encuentra tu hogar perfecto', 'flavor-chat-ia'),
            'telefono' => '912 345 678',
            'color_primario' => '#059669',
        ];
    }

    // =========================================================
    // TIENDA ONLINE
    // =========================================================

    private function get_tienda_hero() {
        return [
            'titulo' => __('Nuestra Tienda', 'flavor-chat-ia'),
            'subtitulo' => __('Descubre nuestros productos y ofertas especiales', 'flavor-chat-ia'),
            'color_primario' => '#00a0d2',
            'imagen' => '',
            'cta_texto' => __('Ver Productos', 'flavor-chat-ia'),
            'cta_url' => '#productos',
        ];
    }

    private function get_tienda_productos() {
        return [
            'titulo' => __('Productos Destacados', 'flavor-chat-ia'),
            'color_primario' => '#00a0d2',
        ];
    }

    private function get_tienda_cta() {
        return [
            'titulo' => __('¿Primera compra?', 'flavor-chat-ia'),
            'descripcion' => __('Regístrate y obtén un descuento en tu primer pedido', 'flavor-chat-ia'),
            'boton_texto' => __('Registrarse', 'flavor-chat-ia'),
            'boton_url' => wp_registration_url(),
            'color_primario' => '#00a0d2',
        ];
    }

    // =========================================================
    // PODCAST / RADIO
    // =========================================================

    private function get_podcast_hero() {
        return [
            'titulo' => __('Podcast Comunitario', 'flavor-chat-ia'),
            'subtitulo' => __('Escucha las voces de nuestra comunidad', 'flavor-chat-ia'),
            'color_primario' => '#dc2626',
            'imagen' => '',
            'cta_texto' => __('Últimos Episodios', 'flavor-chat-ia'),
            'cta_url' => '#episodios',
        ];
    }

    private function get_podcast_episodios() {
        return [
            'titulo' => __('Últimos Episodios', 'flavor-chat-ia'),
            'items' => [
                ['titulo' => __('Historias del barrio', 'flavor-chat-ia'), 'icono' => 'microphone', 'descripcion' => __('45 min', 'flavor-chat-ia')],
                ['titulo' => __('Entrevista: Comercio local', 'flavor-chat-ia'), 'icono' => 'microphone', 'descripcion' => __('30 min', 'flavor-chat-ia')],
                ['titulo' => __('Cultura y tradiciones', 'flavor-chat-ia'), 'icono' => 'microphone', 'descripcion' => __('38 min', 'flavor-chat-ia')],
            ],
            'color_primario' => '#dc2626',
        ];
    }
}

// Inicializar el singleton para registrar los shortcodes
Flavor_Landing_Shortcodes::get_instance();

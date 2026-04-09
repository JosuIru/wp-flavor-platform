<?php
/**
 * Módulo Themacle - Componentes web universales
 *
 * Registra componentes genéricos reutilizables basados en la librería
 * Themacle de Figma. Estos componentes se adaptan visualmente al tema
 * activo mediante CSS custom properties.
 *
 * @package FlavorChatIA
 */

if (!defined('ABSPATH')) {
    exit;
}

class Flavor_Chat_Themacle_Module extends Flavor_Chat_Module_Base {

    use Flavor_Module_Admin_Pages_Trait;
    use Flavor_Module_Notifications_Trait;

    /**
     * Constructor
     */
    public function __construct() {
        $this->id = 'themacle';
        $this->name = 'Themacle Web Components'; // Translation loaded on init
        $this->description = 'Componentes web universales reutilizables para construir cualquier tipo de web'; // Translation loaded on init
        parent::__construct();
        $this->cargar_frontend_controller();
    }

    /**
     * Siempre puede activarse (sin dependencias externas)
     */
    public function can_activate() {
        return true;
    }

    /**
     * Sin error de activación
     */
    public function get_activation_error() {
        return '';
    }

    
    /**
     * Verifica si el módulo está activo
     */
    public function is_active() {
        return $this->can_activate();
    }

/**
     * Inicializar hooks del módulo
     */
    public function init() {
        // Registrar REST API
        add_action('rest_api_init', [$this, 'register_rest_routes']);
        add_action('init', [$this, 'register_shortcodes']);

        // Registrar en Panel Unificado de Gestión
        $this->registrar_en_panel_unificado();
    }

    /**
     * Registra shortcodes frontend mínimos para el renderer del portal.
     *
     * @return void
     */
    public function register_shortcodes() {
        add_shortcode('themacle_mis_temas', [$this, 'shortcode_mis_temas']);
        add_shortcode('themacle_formulario', [$this, 'shortcode_formulario']);
    }

    /**
     * Registrar rutas REST API
     */
    public function register_rest_routes() {
        $namespace = 'flavor/v1';

        // Listar todos los componentes web
        register_rest_route($namespace, '/themacle/componentes', [
            'methods' => 'GET',
            'callback' => [$this, 'api_listar_componentes'],
            'permission_callback' => [$this, 'check_component_library_access'],
            'args' => [
                'categoria' => [
                    'type' => 'string',
                    'description' => 'Filtrar por categoría (hero, content, listings, features, cta, navigation)',
                ],
            ],
        ]);

        // Obtener un componente específico
        register_rest_route($namespace, '/themacle/componentes/(?P<id>[a-z0-9_]+)', [
            'methods' => 'GET',
            'callback' => [$this, 'api_obtener_componente'],
            'permission_callback' => [$this, 'check_component_library_access'],
            'args' => [
                'id' => [
                    'required' => true,
                    'type' => 'string',
                    'description' => 'Identificador del componente (ej: hero_fullscreen)',
                ],
            ],
        ]);

        // Listar categorías disponibles
        register_rest_route($namespace, '/themacle/categorias', [
            'methods' => 'GET',
            'callback' => [$this, 'api_listar_categorias'],
            'permission_callback' => [$this, 'check_component_library_access'],
        ]);

        // Obtener estadísticas del módulo
        register_rest_route($namespace, '/themacle/estadisticas', [
            'methods' => 'GET',
            'callback' => [$this, 'api_obtener_estadisticas'],
            'permission_callback' => [$this, 'check_component_library_access'],
        ]);
    }

    /**
     * Restringe la librería interna de componentes a usuarios con edición.
     *
     * @return bool
     */
    public function check_component_library_access() {
        return current_user_can('edit_posts');
    }

    // =========================================================================
    // Métodos API REST
    // =========================================================================

    /**
     * API: Listar componentes web
     */
    public function api_listar_componentes($request) {
        $categoria_filtro = $request->get_param('categoria');
        $componentes_web = $this->get_web_components();
        $lista_componentes = [];

        foreach ($componentes_web as $identificador_componente => $datos_componente) {
            // Filtrar por categoría si se especifica
            if ($categoria_filtro && ($datos_componente['category'] ?? '') !== $categoria_filtro) {
                continue;
            }

            $lista_componentes[] = [
                'id' => $identificador_componente,
                'label' => $datos_componente['label'],
                'description' => $datos_componente['description'],
                'category' => $datos_componente['category'] ?? 'otros',
                'icon' => $datos_componente['icon'] ?? 'dashicons-admin-generic',
                'template' => $datos_componente['template'] ?? '',
                'fields' => array_keys($datos_componente['fields'] ?? []),
            ];
        }

        return new WP_REST_Response([
            'success' => true,
            'total' => count($lista_componentes),
            'componentes' => $lista_componentes,
        ], 200);
    }

    /**
     * API: Obtener un componente específico
     */
    public function api_obtener_componente($request) {
        $identificador_componente = sanitize_text_field($request->get_param('id'));
        $componentes_web = $this->get_web_components();

        if (!isset($componentes_web[$identificador_componente])) {
            return new WP_REST_Response([
                'success' => false,
                'error' => __('Componente no encontrado', 'flavor-platform'),
            ], 404);
        }

        $datos_componente = $componentes_web[$identificador_componente];

        return new WP_REST_Response([
            'success' => true,
            'componente' => [
                'id' => $identificador_componente,
                'label' => $datos_componente['label'],
                'description' => $datos_componente['description'],
                'category' => $datos_componente['category'] ?? 'otros',
                'icon' => $datos_componente['icon'] ?? 'dashicons-admin-generic',
                'template' => $datos_componente['template'] ?? '',
                'preview' => $datos_componente['preview'] ?? '',
                'fields' => $datos_componente['fields'] ?? [],
            ],
        ], 200);
    }

    /**
     * API: Listar categorías de componentes
     */
    public function api_listar_categorias($request) {
        $componentes_web = $this->get_web_components();
        $categorias_componentes = [];

        foreach ($componentes_web as $datos_componente) {
            $categoria = $datos_componente['category'] ?? 'otros';
            if (!isset($categorias_componentes[$categoria])) {
                $categorias_componentes[$categoria] = 0;
            }
            $categorias_componentes[$categoria]++;
        }

        $nombres_categorias = [
            'hero' => __('Heroes', 'flavor-platform'),
            'content' => __('Contenido', 'flavor-platform'),
            'listings' => __('Listados', 'flavor-platform'),
            'features' => __('Características', 'flavor-platform'),
            'cta' => __('CTA', 'flavor-platform'),
            'navigation' => __('Navegación', 'flavor-platform'),
            'otros' => __('Otros', 'flavor-platform'),
        ];

        $categorias_resultado = [];
        foreach ($categorias_componentes as $slug_categoria => $total_componentes) {
            $categorias_resultado[] = [
                'slug' => $slug_categoria,
                'nombre' => $nombres_categorias[$slug_categoria] ?? ucfirst($slug_categoria),
                'total_componentes' => $total_componentes,
            ];
        }

        return new WP_REST_Response([
            'success' => true,
            'total' => count($categorias_resultado),
            'categorias' => $categorias_resultado,
        ], 200);
    }

    /**
     * API: Obtener estadísticas del módulo
     */
    public function api_obtener_estadisticas($request) {
        $componentes_web = $this->get_web_components();
        $total_componentes = count($componentes_web);

        $categorias_componentes = [];
        foreach ($componentes_web as $datos_componente) {
            $categoria = $datos_componente['category'] ?? 'otros';
            if (!isset($categorias_componentes[$categoria])) {
                $categorias_componentes[$categoria] = 0;
            }
            $categorias_componentes[$categoria]++;
        }

        return new WP_REST_Response([
            'success' => true,
            'estadisticas' => [
                'total_componentes' => $total_componentes,
                'total_categorias' => count($categorias_componentes),
                'componentes_por_categoria' => $categorias_componentes,
            ],
        ], 200);
    }

    /**
     * Configuración para el Panel Unificado de Gestión
     *
     * @return array Configuración del módulo
     */
    protected function get_admin_config() {
        return [
            'id' => 'themacle',
            'label' => __('Themacle', 'flavor-platform'),
            'icon' => 'dashicons-admin-customizer',
            'capability' => 'manage_options',
            'categoria' => 'recursos',
            'paginas' => [
                [
                    'slug' => 'themacle-dashboard',
                    'titulo' => __('Dashboard', 'flavor-platform'),
                    'callback' => [$this, 'render_admin_dashboard'],
                ],
                [
                    'slug' => 'themacle-temas',
                    'titulo' => __('Temas', 'flavor-platform'),
                    'callback' => [$this, 'render_admin_temas'],
                ],
                [
                    'slug' => 'themacle-config',
                    'titulo' => __('Configuración', 'flavor-platform'),
                    'callback' => [$this, 'render_admin_config'],
                ],
            ],
            'estadisticas' => [$this, 'get_estadisticas_dashboard'],
        ];
    }

    /**
     * Estadísticas para el dashboard unificado
     *
     * @return array
     */
    public function get_estadisticas_dashboard() {
        $componentes_web = $this->get_web_components();
        $total_componentes = count($componentes_web);

        $categorias_componentes = [];
        foreach ($componentes_web as $componente) {
            $categoria = $componente['category'] ?? 'otros';
            if (!isset($categorias_componentes[$categoria])) {
                $categorias_componentes[$categoria] = 0;
            }
            $categorias_componentes[$categoria]++;
        }

        return [
            [
                'icon' => 'dashicons-layout',
                'valor' => $total_componentes,
                'label' => __('Componentes disponibles', 'flavor-platform'),
                'color' => 'blue',
                'enlace' => admin_url('admin.php?page=themacle-dashboard'),
            ],
            [
                'icon' => 'dashicons-category',
                'valor' => count($categorias_componentes),
                'label' => __('Categorías', 'flavor-platform'),
                'color' => 'purple',
                'enlace' => admin_url('admin.php?page=themacle-dashboard'),
            ],
        ];
    }

    /**
     * Renderiza el dashboard de Themacle
     */
    public function render_admin_dashboard() {
        // Renderizar el dashboard completo desde el archivo de vista
        $dashboard_view_path = dirname(__FILE__) . '/views/dashboard.php';
        if (file_exists($dashboard_view_path)) {
            include $dashboard_view_path;
        } else {
            echo '<div class="wrap flavor-modulo-page">';
            $this->render_page_header(__('Dashboard de Themacle', 'flavor-platform'));
            echo '<p>' . __('Panel de control del módulo de themacle.', 'flavor-platform') . '</p>';
            echo '</div>';
        }
    }
    public function render_admin_temas() {
        echo '<div class="wrap flavor-modulo-page">';
        $this->render_page_header(__('Gestión de Temas', 'flavor-platform'));

        echo '<p>' . __('Los componentes Themacle se adaptan automáticamente al tema visual activo del sitio mediante CSS custom properties.', 'flavor-platform') . '</p>';

        echo '<div class="card">';
        echo '<h3>' . __('Tema Activo', 'flavor-platform') . '</h3>';
        echo '<p>' . __('El tema visual actual define los colores, tipografías y espaciados que utilizan todos los componentes.', 'flavor-platform') . '</p>';
        echo '</div>';

        echo '<div class="card">';
        echo '<h3>' . __('Personalización', 'flavor-platform') . '</h3>';
        echo '<p>' . __('Puedes personalizar las variables CSS para adaptar los componentes a tu marca.', 'flavor-platform') . '</p>';
        echo '</div>';

        echo '</div>';
    }

    /**
     * Renderiza la configuración del módulo
     */
    public function render_admin_config() {
        echo '<div class="wrap flavor-modulo-page">';
        $this->render_page_header(__('Configuración de Themacle', 'flavor-platform'));

        echo '<form method="post" action="">';
        echo '<table class="form-table">';

        echo '<tr><th scope="row"><label for="componentes_activos">' . __('Componentes Activos', 'flavor-platform') . '</label></th>';
        echo '<td><p class="description">' . __('Todos los componentes están activos por defecto. Puedes desactivar componentes específicos si no los necesitas.', 'flavor-platform') . '</p></td></tr>';

        echo '<tr><th scope="row"><label for="cache_templates">' . __('Cache de Templates', 'flavor-platform') . '</label></th>';
        echo '<td><input type="checkbox" name="cache_templates" id="cache_templates" checked />';
        echo '<p class="description">' . __('Cachear los templates de componentes para mejorar el rendimiento.', 'flavor-platform') . '</p></td></tr>';

        echo '</table>';
        echo '<p class="submit"><input type="submit" name="guardar_config" class="button-primary" value="' . __('Guardar Configuración', 'flavor-platform') . '" /></p>';
        echo '</form>';
        echo '</div>';
    }

    /**
     * Acciones disponibles para el chat IA
     */
    public function get_actions() {
        return [
            'listar_componentes_web' => [
                'description' => 'Listar los componentes web Themacle disponibles',
                'params' => [],
            ],
        ];
    }

    /**
     * Ejecutar acción
     */
    public function execute_action($nombre_accion, $parametros) {
        $aliases = [
            'listar' => 'listar_componentes_web',
            'listado' => 'listar_componentes_web',
            'mis_items' => 'listar_componentes_web',
            'mis-temas' => 'listar_componentes_web',
            'crear' => 'formulario',
            'nuevo' => 'formulario',
            'formulario' => 'formulario',
        ];

        $nombre_accion = $aliases[$nombre_accion] ?? $nombre_accion;

        if ($nombre_accion === 'listar_componentes_web') {
            return $this->action_listar_componentes_web();
        }

        if ($nombre_accion === 'formulario') {
            return [
                'success' => true,
                'html' => $this->shortcode_formulario(),
            ];
        }

        return ['success' => false, 'error' => "Acción no encontrada: {$nombre_accion}"];
    }

    /**
     * Shortcode: listado de componentes disponibles.
     *
     * @return string
     */
    public function shortcode_mis_temas() {
        $resultado = $this->action_listar_componentes_web();
        $componentes = $resultado['componentes'] ?? [];

        if (empty($componentes)) {
            return '<div class="flavor-alert flavor-alert-info">' .
                __('No hay componentes Themacle disponibles todavía.', 'flavor-platform') .
                '</div>';
        }

        ob_start();
        ?>
        <div class="flavor-themacle-grid">
            <?php foreach ($componentes as $componente): ?>
                <article class="flavor-themacle-card">
                    <h3><?php echo esc_html($componente['label']); ?></h3>
                    <p><?php echo esc_html($componente['description']); ?></p>
                    <div class="flavor-themacle-meta">
                        <span class="flavor-badge flavor-badge-info"><?php echo esc_html($componente['category']); ?></span>
                        <code><?php echo esc_html($componente['id']); ?></code>
                    </div>
                </article>
            <?php endforeach; ?>
        </div>
        <?php
        return ob_get_clean();
    }

    /**
     * Shortcode: ayuda de uso para insertar componentes Themacle.
     *
     * @return string
     */
    public function shortcode_formulario() {
        return '<div class="flavor-panel flavor-panel-info">' .
            '<h3>' . esc_html__('Insertar componente Themacle', 'flavor-platform') . '</h3>' .
            '<p>' . esc_html__('Themacle actúa como librería de componentes reutilizables. Selecciona un componente desde la API o el panel de administración y úsalo en tus páginas o layouts.', 'flavor-platform') . '</p>' .
            '<p><code>/wp-json/flavor/v1/themacle/componentes</code></p>' .
            '</div>';
    }

    /**
     * Acción: listar componentes web disponibles
     */
    private function action_listar_componentes_web() {
        $componentes_web = $this->get_web_components();
        $lista_componentes = [];

        foreach ($componentes_web as $identificador_componente => $datos_componente) {
            $lista_componentes[] = [
                'id' => 'themacle_' . $identificador_componente,
                'label' => $datos_componente['label'],
                'category' => $datos_componente['category'],
                'description' => $datos_componente['description'],
            ];
        }

        return [
            'success' => true,
            'componentes' => $lista_componentes,
            'total' => count($lista_componentes),
        ];
    }

    /**
     * Definiciones de tools para Claude
     */
    public function get_tool_definitions() {
        return [
            [
                'name' => 'listar_componentes_web',
                'description' => 'Lista los componentes web Themacle disponibles para construir páginas',
                'input_schema' => [
                    'type' => 'object',
                    'properties' => new stdClass(),
                    'required' => [],
                ],
            ],
        ];
    }

    /**
     * Base de conocimiento para el system prompt
     */
    public function get_knowledge_base() {
        $base_conocimiento = "Módulo Themacle: Componentes web universales para construir cualquier tipo de página web.\n\n";
        $base_conocimiento .= "COMPONENTES DISPONIBLES:\n";
        $base_conocimiento .= "- Hero Fullscreen: Imagen/video de fondo con título, subtítulo y CTA\n";
        $base_conocimiento .= "- Hero Split: Diseño 50/50 con imagen y texto (invertible)\n";
        $base_conocimiento .= "- Hero Slider: Carrusel de slides con navegación\n";
        $base_conocimiento .= "- Card Grid: Grid de tarjetas configurable (2-4 columnas)\n";
        $base_conocimiento .= "- Text Media: Bloque de texto con imagen lateral\n";
        $base_conocimiento .= "- Feature Grid: Grid de características con iconos\n";
        $base_conocimiento .= "- CTA Banner: Llamada a la acción con fondo\n";
        $base_conocimiento .= "- Newsletter: Suscripción por email\n";
        $base_conocimiento .= "- Filters Bar: Barra de filtros por taxonomía\n";
        $base_conocimiento .= "- Gallery: Galería de imágenes en grid\n";
        $base_conocimiento .= "- Map Section: Mapa con información de contacto\n";
        $base_conocimiento .= "- Accordion: Lista desplegable FAQ\n";
        $base_conocimiento .= "- Highlights: Sección de destacados\n";
        $base_conocimiento .= "- Related Items: Grid de elementos relacionados\n";
        $base_conocimiento .= "- Post Content: Contenido de artículo/post single\n";
        $base_conocimiento .= "- Pagination: Navegación entre páginas\n\n";
        $base_conocimiento .= "Todos los componentes se adaptan automáticamente al tema activo del sitio.\n";

        return $base_conocimiento;
    }

    /**
     * FAQs del módulo
     */
    public function get_faqs() {
        return [
            [
                'pregunta' => '¿Cómo añado componentes Themacle a mi página?',
                'respuesta' => 'Ve al Page Builder, haz clic en "Añadir Componente" y busca los componentes en las categorías Hero, Contenido, Listados, etc.',
            ],
            [
                'pregunta' => '¿Puedo cambiar el estilo visual de los componentes?',
                'respuesta' => 'Sí, los componentes se adaptan automáticamente al tema activo. Cambia el tema desde Ajustes > Temas para ver un estilo diferente.',
            ],
        ];
    }

    /**
     * Componentes web universales del módulo Themacle
     *
     * @return array
     */
    public function get_web_components() {
        return [
            // ─── HEROES ───────────────────────────────────────
            'hero_fullscreen' => [
                'label' => __('Hero Fullscreen', 'flavor-platform'),
                'description' => __('Sección hero a pantalla completa con imagen/video de fondo', 'flavor-platform'),
                'category' => 'hero',
                'icon' => 'dashicons-cover-image',
                'fields' => [
                    'titulo' => [
                        'type' => 'text',
                        'label' => __('Título', 'flavor-platform'),
                        'default' => __('Bienvenido a nuestra web', 'flavor-platform'),
                    ],
                    'subtitulo' => [
                        'type' => 'textarea',
                        'label' => __('Subtítulo', 'flavor-platform'),
                        'default' => '',
                    ],
                    'imagen_fondo' => [
                        'type' => 'image',
                        'label' => __('Imagen de fondo', 'flavor-platform'),
                        'default' => '',
                    ],
                    'video_fondo' => [
                        'type' => 'url',
                        'label' => __('URL del vídeo de fondo', 'flavor-platform'),
                        'default' => '',
                    ],
                    'texto_cta' => [
                        'type' => 'text',
                        'label' => __('Texto del botón CTA', 'flavor-platform'),
                        'default' => __('Saber más', 'flavor-platform'),
                    ],
                    'url_cta' => [
                        'type' => 'url',
                        'label' => __('URL del botón CTA', 'flavor-platform'),
                        'default' => '#',
                    ],
                    'overlay_color' => [
                        'type' => 'color',
                        'label' => __('Color del overlay', 'flavor-platform'),
                        'default' => '#000000',
                    ],
                    'overlay_opacidad' => [
                        'type' => 'number',
                        'label' => __('Opacidad del overlay (0-100)', 'flavor-platform'),
                        'default' => 50,
                    ],
                ],
                'template' => 'themacle/hero-fullscreen',
                'preview' => '',
            ],

            'hero_split' => [
                'label' => __('Hero Split', 'flavor-platform'),
                'description' => __('Hero con diseño 50/50: imagen y texto lado a lado', 'flavor-platform'),
                'category' => 'hero',
                'icon' => 'dashicons-columns',
                'fields' => [
                    'titulo' => [
                        'type' => 'text',
                        'label' => __('Título', 'flavor-platform'),
                        'default' => __('Tu título aquí', 'flavor-platform'),
                    ],
                    'subtitulo' => [
                        'type' => 'textarea',
                        'label' => __('Subtítulo', 'flavor-platform'),
                        'default' => '',
                    ],
                    'texto_cta' => [
                        'type' => 'text',
                        'label' => __('Texto del botón', 'flavor-platform'),
                        'default' => __('Empezar', 'flavor-platform'),
                    ],
                    'url_cta' => [
                        'type' => 'url',
                        'label' => __('URL del botón', 'flavor-platform'),
                        'default' => '#',
                    ],
                    'imagen' => [
                        'type' => 'image',
                        'label' => __('Imagen', 'flavor-platform'),
                        'default' => '',
                    ],
                    'invertir' => [
                        'type' => 'toggle',
                        'label' => __('Invertir orden (imagen a la izquierda)', 'flavor-platform'),
                        'default' => false,
                    ],
                    'color_fondo' => [
                        'type' => 'color',
                        'label' => __('Color de fondo', 'flavor-platform'),
                        'default' => '#ffffff',
                    ],
                ],
                'template' => 'themacle/hero-split',
                'preview' => '',
            ],

            'hero_slider' => [
                'label' => __('Hero Slider', 'flavor-platform'),
                'description' => __('Carrusel de slides con navegación por bullets', 'flavor-platform'),
                'category' => 'hero',
                'icon' => 'dashicons-slides',
                'fields' => [
                    'slides' => [
                        'type' => 'repeater',
                        'label' => __('Slides', 'flavor-platform'),
                        'fields' => [
                            'titulo' => ['type' => 'text', 'label' => __('Título', 'flavor-platform'), 'default' => ''],
                            'subtitulo' => ['type' => 'textarea', 'label' => __('Subtítulo', 'flavor-platform'), 'default' => ''],
                            'imagen' => ['type' => 'image', 'label' => __('Imagen de fondo', 'flavor-platform'), 'default' => ''],
                            'url_cta' => ['type' => 'url', 'label' => __('URL del botón', 'flavor-platform'), 'default' => '#'],
                            'texto_cta' => ['type' => 'text', 'label' => __('Texto del botón', 'flavor-platform'), 'default' => ''],
                        ],
                        'default' => [],
                        'max_items' => 10,
                    ],
                    'autoplay' => [
                        'type' => 'toggle',
                        'label' => __('Autoplay', 'flavor-platform'),
                        'default' => true,
                    ],
                    'intervalo' => [
                        'type' => 'number',
                        'label' => __('Intervalo en milisegundos', 'flavor-platform'),
                        'default' => 5000,
                    ],
                ],
                'template' => 'themacle/hero-slider',
                'preview' => '',
            ],

            // ─── CONTENIDO ────────────────────────────────────
            'text_media' => [
                'label' => __('Texto + Media', 'flavor-platform'),
                'description' => __('Bloque de texto con imagen lado a lado', 'flavor-platform'),
                'category' => 'content',
                'icon' => 'dashicons-align-left',
                'fields' => [
                    'titulo' => [
                        'type' => 'text',
                        'label' => __('Título', 'flavor-platform'),
                        'default' => '',
                    ],
                    'contenido' => [
                        'type' => 'textarea',
                        'label' => __('Contenido', 'flavor-platform'),
                        'default' => '',
                    ],
                    'imagen' => [
                        'type' => 'image',
                        'label' => __('Imagen', 'flavor-platform'),
                        'default' => '',
                    ],
                    'invertir' => [
                        'type' => 'toggle',
                        'label' => __('Invertir orden', 'flavor-platform'),
                        'default' => false,
                    ],
                    'estilo' => [
                        'type' => 'select',
                        'label' => __('Estilo', 'flavor-platform'),
                        'options' => ['simple', 'overlay'],
                        'default' => 'simple',
                    ],
                ],
                'template' => 'themacle/text-media',
                'preview' => '',
            ],

            'gallery' => [
                'label' => __('Galería', 'flavor-platform'),
                'description' => __('Galería de imágenes en formato grid', 'flavor-platform'),
                'category' => 'content',
                'icon' => 'dashicons-format-gallery',
                'fields' => [
                    'titulo' => [
                        'type' => 'text',
                        'label' => __('Título', 'flavor-platform'),
                        'default' => '',
                    ],
                    'columnas' => [
                        'type' => 'select',
                        'label' => __('Columnas', 'flavor-platform'),
                        'options' => [2, 3, 4],
                        'default' => 3,
                    ],
                    'imagenes' => [
                        'type' => 'repeater',
                        'label' => __('Imágenes', 'flavor-platform'),
                        'fields' => [
                            'imagen' => ['type' => 'image', 'label' => __('Imagen', 'flavor-platform'), 'default' => ''],
                            'titulo' => ['type' => 'text', 'label' => __('Título', 'flavor-platform'), 'default' => ''],
                        ],
                        'default' => [],
                        'max_items' => 24,
                    ],
                ],
                'template' => 'themacle/gallery',
                'preview' => '',
            ],

            'accordion' => [
                'label' => __('Acordeón / FAQ', 'flavor-platform'),
                'description' => __('Lista desplegable de preguntas y respuestas', 'flavor-platform'),
                'category' => 'content',
                'icon' => 'dashicons-list-view',
                'fields' => [
                    'titulo' => [
                        'type' => 'text',
                        'label' => __('Título', 'flavor-platform'),
                        'default' => __('Preguntas Frecuentes', 'flavor-platform'),
                    ],
                    'items' => [
                        'type' => 'repeater',
                        'label' => __('Preguntas', 'flavor-platform'),
                        'fields' => [
                            'pregunta' => ['type' => 'text', 'label' => __('Pregunta', 'flavor-platform'), 'default' => ''],
                            'respuesta' => ['type' => 'textarea', 'label' => __('Respuesta', 'flavor-platform'), 'default' => ''],
                        ],
                        'default' => [],
                        'max_items' => 20,
                    ],
                ],
                'template' => 'themacle/accordion',
                'preview' => '',
            ],

            'map_section' => [
                'label' => __('Mapa + Contacto', 'flavor-platform'),
                'description' => __('Sección con mapa embebido e información de contacto', 'flavor-platform'),
                'category' => 'content',
                'icon' => 'dashicons-location-alt',
                'fields' => [
                    'titulo' => [
                        'type' => 'text',
                        'label' => __('Título', 'flavor-platform'),
                        'default' => __('Dónde Encontrarnos', 'flavor-platform'),
                    ],
                    'direccion' => [
                        'type' => 'text',
                        'label' => __('Dirección', 'flavor-platform'),
                        'default' => '',
                    ],
                    'telefono' => [
                        'type' => 'text',
                        'label' => __('Teléfono', 'flavor-platform'),
                        'default' => '',
                    ],
                    'email' => [
                        'type' => 'email',
                        'label' => __('Email', 'flavor-platform'),
                        'default' => '',
                    ],
                    'horario' => [
                        'type' => 'textarea',
                        'label' => __('Horario', 'flavor-platform'),
                        'default' => '',
                    ],
                    'mostrar_formulario' => [
                        'type' => 'toggle',
                        'label' => __('Mostrar formulario de contacto', 'flavor-platform'),
                        'default' => false,
                    ],
                ],
                'template' => 'themacle/map-section',
                'preview' => '',
            ],

            'post_content' => [
                'label' => __('Contenido de Post', 'flavor-platform'),
                'description' => __('Contenido de artículo o entrada individual', 'flavor-platform'),
                'category' => 'content',
                'icon' => 'dashicons-media-text',
                'fields' => [
                    'mostrar_imagen_destacada' => [
                        'type' => 'toggle',
                        'label' => __('Mostrar imagen destacada', 'flavor-platform'),
                        'default' => true,
                    ],
                    'mostrar_fecha' => [
                        'type' => 'toggle',
                        'label' => __('Mostrar fecha', 'flavor-platform'),
                        'default' => true,
                    ],
                    'mostrar_autor' => [
                        'type' => 'toggle',
                        'label' => __('Mostrar autor', 'flavor-platform'),
                        'default' => true,
                    ],
                    'mostrar_compartir' => [
                        'type' => 'toggle',
                        'label' => __('Mostrar botones de compartir', 'flavor-platform'),
                        'default' => true,
                    ],
                ],
                'template' => 'themacle/post-content',
                'preview' => '',
            ],

            // ─── LISTADOS ─────────────────────────────────────
            'card_grid' => [
                'label' => __('Grid de Tarjetas', 'flavor-platform'),
                'description' => __('Grid configurable de tarjetas con imagen, título y descripción', 'flavor-platform'),
                'category' => 'listings',
                'icon' => 'dashicons-grid-view',
                'fields' => [
                    'titulo' => [
                        'type' => 'text',
                        'label' => __('Título de sección', 'flavor-platform'),
                        'default' => '',
                    ],
                    'columnas' => [
                        'type' => 'select',
                        'label' => __('Columnas', 'flavor-platform'),
                        'options' => [2, 3, 4],
                        'default' => 3,
                    ],
                    'estilo_card' => [
                        'type' => 'select',
                        'label' => __('Estilo de tarjeta', 'flavor-platform'),
                        'options' => ['shadow', 'border', 'flat'],
                        'default' => 'shadow',
                    ],
                    'fuente_datos' => [
                        'type' => 'data_source',
                        'label' => __('Fuente de datos', 'flavor-platform'),
                        'post_types' => [],
                        'items_field' => 'items',
                        'default' => 'manual',
                    ],
                    'items' => [
                        'type' => 'repeater',
                        'label' => __('Tarjetas', 'flavor-platform'),
                        'fields' => [
                            'imagen' => ['type' => 'image', 'label' => __('Imagen', 'flavor-platform'), 'default' => ''],
                            'titulo' => ['type' => 'text', 'label' => __('Título', 'flavor-platform'), 'default' => ''],
                            'descripcion' => ['type' => 'textarea', 'label' => __('Descripción', 'flavor-platform'), 'default' => ''],
                            'url' => ['type' => 'url', 'label' => __('URL', 'flavor-platform'), 'default' => '#'],
                        ],
                        'default' => [],
                        'max_items' => 12,
                    ],
                ],
                'template' => 'themacle/card-grid',
                'preview' => '',
            ],

            'related_items' => [
                'label' => __('Items Relacionados', 'flavor-platform'),
                'description' => __('Grid de elementos relacionados con datos dinámicos', 'flavor-platform'),
                'category' => 'listings',
                'icon' => 'dashicons-networking',
                'fields' => [
                    'titulo' => [
                        'type' => 'text',
                        'label' => __('Título', 'flavor-platform'),
                        'default' => __('Relacionados', 'flavor-platform'),
                    ],
                    'columnas' => [
                        'type' => 'select',
                        'label' => __('Columnas', 'flavor-platform'),
                        'options' => [2, 3, 4],
                        'default' => 3,
                    ],
                    'fuente_datos' => [
                        'type' => 'data_source',
                        'label' => __('Fuente de datos', 'flavor-platform'),
                        'post_types' => [],
                        'items_field' => '',
                        'default' => 'manual',
                    ],
                ],
                'template' => 'themacle/related-items',
                'preview' => '',
            ],

            // ─── CARACTERÍSTICAS ──────────────────────────────
            'feature_grid' => [
                'label' => __('Grid de Características', 'flavor-platform'),
                'description' => __('Grid de iconos o imágenes con título y descripción', 'flavor-platform'),
                'category' => 'features',
                'icon' => 'dashicons-star-filled',
                'fields' => [
                    'titulo' => [
                        'type' => 'text',
                        'label' => __('Título', 'flavor-platform'),
                        'default' => '',
                    ],
                    'subtitulo' => [
                        'type' => 'textarea',
                        'label' => __('Subtítulo', 'flavor-platform'),
                        'default' => '',
                    ],
                    'columnas' => [
                        'type' => 'select',
                        'label' => __('Columnas', 'flavor-platform'),
                        'options' => [2, 3, 4],
                        'default' => 3,
                    ],
                    'items' => [
                        'type' => 'repeater',
                        'label' => __('Características', 'flavor-platform'),
                        'fields' => [
                            'icono' => ['type' => 'text', 'label' => __('Icono (dashicons)', 'flavor-platform'), 'default' => 'dashicons-star-filled'],
                            'titulo' => ['type' => 'text', 'label' => __('Título', 'flavor-platform'), 'default' => ''],
                            'descripcion' => ['type' => 'textarea', 'label' => __('Descripción', 'flavor-platform'), 'default' => ''],
                        ],
                        'default' => [],
                        'max_items' => 12,
                    ],
                ],
                'template' => 'themacle/feature-grid',
                'preview' => '',
            ],

            'highlights' => [
                'label' => __('Destacados', 'flavor-platform'),
                'description' => __('Sección de elementos destacados con iconos o imágenes', 'flavor-platform'),
                'category' => 'features',
                'icon' => 'dashicons-awards',
                'fields' => [
                    'titulo' => [
                        'type' => 'text',
                        'label' => __('Título', 'flavor-platform'),
                        'default' => '',
                    ],
                    'items' => [
                        'type' => 'repeater',
                        'label' => __('Destacados', 'flavor-platform'),
                        'fields' => [
                            'imagen' => ['type' => 'image', 'label' => __('Imagen/Icono', 'flavor-platform'), 'default' => ''],
                            'titulo' => ['type' => 'text', 'label' => __('Título', 'flavor-platform'), 'default' => ''],
                            'descripcion' => ['type' => 'textarea', 'label' => __('Descripción', 'flavor-platform'), 'default' => ''],
                            'url' => ['type' => 'url', 'label' => __('URL', 'flavor-platform'), 'default' => ''],
                        ],
                        'default' => [],
                        'max_items' => 8,
                    ],
                    'estilo' => [
                        'type' => 'select',
                        'label' => __('Estilo', 'flavor-platform'),
                        'options' => ['cards', 'icons', 'minimal'],
                        'default' => 'cards',
                    ],
                ],
                'template' => 'themacle/highlights',
                'preview' => '',
            ],

            // ─── CTA ──────────────────────────────────────────
            'cta_banner' => [
                'label' => __('Banner CTA', 'flavor-platform'),
                'description' => __('Llamada a la acción con fondo de color o imagen', 'flavor-platform'),
                'category' => 'cta',
                'icon' => 'dashicons-megaphone',
                'fields' => [
                    'titulo' => [
                        'type' => 'text',
                        'label' => __('Título', 'flavor-platform'),
                        'default' => '',
                    ],
                    'subtitulo' => [
                        'type' => 'textarea',
                        'label' => __('Subtítulo', 'flavor-platform'),
                        'default' => '',
                    ],
                    'texto_cta' => [
                        'type' => 'text',
                        'label' => __('Texto del botón', 'flavor-platform'),
                        'default' => __('Contactar', 'flavor-platform'),
                    ],
                    'url_cta' => [
                        'type' => 'url',
                        'label' => __('URL del botón', 'flavor-platform'),
                        'default' => '#',
                    ],
                    'imagen_fondo' => [
                        'type' => 'image',
                        'label' => __('Imagen de fondo', 'flavor-platform'),
                        'default' => '',
                    ],
                    'color_fondo' => [
                        'type' => 'color',
                        'label' => __('Color de fondo', 'flavor-platform'),
                        'default' => '',
                    ],
                ],
                'template' => 'themacle/cta-banner',
                'preview' => '',
            ],

            'newsletter' => [
                'label' => __('Newsletter', 'flavor-platform'),
                'description' => __('Formulario de suscripción por email', 'flavor-platform'),
                'category' => 'cta',
                'icon' => 'dashicons-email-alt',
                'fields' => [
                    'titulo' => [
                        'type' => 'text',
                        'label' => __('Título', 'flavor-platform'),
                        'default' => __('Suscríbete', 'flavor-platform'),
                    ],
                    'subtitulo' => [
                        'type' => 'textarea',
                        'label' => __('Subtítulo', 'flavor-platform'),
                        'default' => '',
                    ],
                    'texto_placeholder' => [
                        'type' => 'text',
                        'label' => __('Placeholder del campo', 'flavor-platform'),
                        'default' => __('Tu email', 'flavor-platform'),
                    ],
                    'texto_boton' => [
                        'type' => 'text',
                        'label' => __('Texto del botón', 'flavor-platform'),
                        'default' => __('Suscribirme', 'flavor-platform'),
                    ],
                    'color_fondo' => [
                        'type' => 'color',
                        'label' => __('Color de fondo', 'flavor-platform'),
                        'default' => '',
                    ],
                ],
                'template' => 'themacle/newsletter',
                'preview' => '',
            ],

            // ─── NAVEGACIÓN ───────────────────────────────────
            'filters_bar' => [
                'label' => __('Barra de Filtros', 'flavor-platform'),
                'description' => __('Barra de filtros por taxonomía con diferentes estilos', 'flavor-platform'),
                'category' => 'navigation',
                'icon' => 'dashicons-filter',
                'fields' => [
                    'taxonomia' => [
                        'type' => 'text',
                        'label' => __('Taxonomía (slug)', 'flavor-platform'),
                        'default' => 'category',
                    ],
                    'estilo' => [
                        'type' => 'select',
                        'label' => __('Estilo', 'flavor-platform'),
                        'options' => ['underline', 'pills', 'dropdown'],
                        'default' => 'pills',
                    ],
                ],
                'template' => 'themacle/filters-bar',
                'preview' => '',
            ],

            'pagination' => [
                'label' => __('Paginación', 'flavor-platform'),
                'description' => __('Navegación numérica entre páginas', 'flavor-platform'),
                'category' => 'navigation',
                'icon' => 'dashicons-controls-forward',
                'fields' => [
                    'estilo' => [
                        'type' => 'select',
                        'label' => __('Estilo', 'flavor-platform'),
                        'options' => ['numbers', 'simple', 'load-more'],
                        'default' => 'numbers',
                    ],
                ],
                'template' => 'themacle/pagination',
                'preview' => '',
            ],
        ];
    }

    /**
     * Define las páginas del módulo para V3
     */
    public function get_pages_definition() {
        return [
            [
                'title' => __('Themacle', 'flavor-platform'),
                'slug' => 'themacle',
                'content' => '<h1>' . __('Themacle', 'flavor-platform') . '</h1>
<p>' . __('Gestión de contenido temático', 'flavor-platform') . '</p>

[flavor_module_listing module="themacle" action="dashboard" columnas="3" limite="12"]',
                'parent' => 0,
            ],
            [
                'title' => __('Temas', 'flavor-platform'),
                'slug' => 'temas-themacle',
                'content' => '<h1>' . __('Temas', 'flavor-platform') . '</h1>
<p>' . __('Explora los temas disponibles', 'flavor-platform') . '</p>

[flavor_module_listing module="themacle" action="temas"]',
                'parent' => 'themacle',
            ],
            [
                'title' => __('Crear Tema', 'flavor-platform'),
                'slug' => 'crear-tema',
                'content' => '<h1>' . __('Crear Tema', 'flavor-platform') . '</h1>
<p>' . __('Crea un nuevo tema', 'flavor-platform') . '</p>

[flavor_module_form module="themacle" action="crear_tema"]',
                'parent' => 'themacle',
            ],
            [
                'title' => __('Mis Temas', 'flavor-platform'),
                'slug' => 'mis-temas-themacle',
                'content' => '<h1>' . __('Mis Temas', 'flavor-platform') . '</h1>
<p>' . __('Gestiona tus temas creados', 'flavor-platform') . '</p>

[flavor_module_dashboard module="themacle" action="mis_temas"]',
                'parent' => 'themacle',
            ],
        ];
    }

    /**
     * Configuración para el Module Renderer
     *
     * @return array
     */
    public static function get_renderer_config(): array {
        return [
            'module'   => 'themacle',
            'title'    => __('Themacle', 'flavor-platform'),
            'subtitle' => __('Gestión de contenido temático', 'flavor-platform'),
            'icon'     => '🎨',
            'color'    => 'primary', // Usa variable CSS --flavor-primary del tema

            'database' => [
                'table'       => 'flavor_themacle',
                'primary_key' => 'id',
            ],

            'fields' => [
                'titulo'      => ['type' => 'text', 'label' => __('Título', 'flavor-platform'), 'required' => true],
                'descripcion' => ['type' => 'textarea', 'label' => __('Descripción', 'flavor-platform')],
                'categoria'   => ['type' => 'select', 'label' => __('Categoría', 'flavor-platform')],
                'imagen'      => ['type' => 'file', 'label' => __('Imagen', 'flavor-platform')],
                'contenido'   => ['type' => 'editor', 'label' => __('Contenido', 'flavor-platform')],
                'etiquetas'   => ['type' => 'tags', 'label' => __('Etiquetas', 'flavor-platform')],
            ],

            'estados' => [
                'borrador'   => ['label' => __('Borrador', 'flavor-platform'), 'color' => 'gray', 'icon' => '📝'],
                'publicado'  => ['label' => __('Publicado', 'flavor-platform'), 'color' => 'green', 'icon' => '✅'],
                'destacado'  => ['label' => __('Destacado', 'flavor-platform'), 'color' => 'yellow', 'icon' => '⭐'],
                'archivado'  => ['label' => __('Archivado', 'flavor-platform'), 'color' => 'gray', 'icon' => '📁'],
            ],

            'stats' => [
                [
                    'key'   => 'total_temas',
                    'label' => __('Temas', 'flavor-platform'),
                    'icon'  => '🎨',
                    'color' => 'purple',
                    'query' => "SELECT COUNT(*) FROM {prefix}flavor_themacle WHERE estado = 'publicado'",
                ],
                [
                    'key'   => 'mis_temas',
                    'label' => __('Mis temas', 'flavor-platform'),
                    'icon'  => '👤',
                    'color' => 'blue',
                    'query' => "SELECT COUNT(*) FROM {prefix}flavor_themacle WHERE user_id = {user_id}",
                ],
                [
                    'key'   => 'vistas',
                    'label' => __('Vistas', 'flavor-platform'),
                    'icon'  => '👁️',
                    'color' => 'green',
                    'query' => "SELECT SUM(vistas) FROM {prefix}flavor_themacle WHERE user_id = {user_id}",
                ],
            ],

            'card' => [
                'layout'      => 'vertical',
                'image_field' => 'imagen',
                'title_field' => 'titulo',
                'meta_fields' => ['categoria', 'created_at'],
                'badge_field' => 'categoria',
                'show_author' => true,
            ],

            'tabs' => [
                'listado' => [
                    'label'   => __('Temas', 'flavor-platform'),
                    'icon'    => '🎨',
                    'content' => 'template:themacle/_listado.php',
                ],
                'mis-temas' => [
                    'label'   => __('Mis temas', 'flavor-platform'),
                    'icon'    => '👤',
                    'content' => 'shortcode:themacle_mis_temas',
                ],
                'crear' => [
                    'label'   => __('Crear', 'flavor-platform'),
                    'icon'    => '➕',
                    'content' => 'shortcode:themacle_formulario',
                ],
            ],

            'archive' => [
                'columns'       => 3,
                'per_page'      => 12,
                'order_by'      => 'created_at',
                'order'         => 'DESC',
                'filterable_by' => ['categoria', 'etiquetas'],
            ],

            'dashboard' => [
                'widgets' => [
                    'temas_recientes'  => ['type' => 'list', 'title' => __('Temas recientes', 'flavor-platform')],
                    'mis_temas'        => ['type' => 'list', 'title' => __('Mis temas', 'flavor-platform')],
                ],
                'actions' => [
                    'nuevo_tema' => [
                        'label' => __('Crear tema', 'flavor-platform'),
                        'icon'  => '➕',
                        'modal' => 'themacle-nuevo',
                    ],
                ],
            ],

            'features' => [
                'has_archive'    => true,
                'has_single'     => true,
                'has_dashboard'  => true,
                'has_search'     => true,
                'has_categories' => true,
                'has_tags'       => true,
                'has_comments'   => true,
            ],
        ];
    }

    /**
     * Cargar frontend controller
     */
    private function cargar_frontend_controller() {
        $archivo_controller = dirname(__FILE__) . '/frontend/class-themacle-frontend-controller.php';
        if (file_exists($archivo_controller)) {
            require_once $archivo_controller;
            Flavor_Themacle_Frontend_Controller::get_instance();
        }
    }

}

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

    /**
     * Constructor
     */
    public function __construct() {
        $this->id = 'themacle';
        $this->name = 'Themacle Web Components'; // Translation loaded on init
        $this->description = 'Componentes web universales reutilizables para construir cualquier tipo de web'; // Translation loaded on init
        parent::__construct();
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

        // Registrar en Panel Unificado de Gestión
        $this->registrar_en_panel_unificado();
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
            'permission_callback' => '__return_true',
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
            'permission_callback' => '__return_true',
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
            'permission_callback' => '__return_true',
        ]);

        // Obtener estadísticas del módulo
        register_rest_route($namespace, '/themacle/estadisticas', [
            'methods' => 'GET',
            'callback' => [$this, 'api_obtener_estadisticas'],
            'permission_callback' => '__return_true',
        ]);
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
                'error' => __('Componente no encontrado', 'flavor-chat-ia'),
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
            'hero' => __('Heroes', 'flavor-chat-ia'),
            'content' => __('Contenido', 'flavor-chat-ia'),
            'listings' => __('Listados', 'flavor-chat-ia'),
            'features' => __('Características', 'flavor-chat-ia'),
            'cta' => __('CTA', 'flavor-chat-ia'),
            'navigation' => __('Navegación', 'flavor-chat-ia'),
            'otros' => __('Otros', 'flavor-chat-ia'),
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
            'label' => __('Themacle', 'flavor-chat-ia'),
            'icon' => 'dashicons-admin-customizer',
            'capability' => 'manage_options',
            'categoria' => 'recursos',
            'paginas' => [
                [
                    'slug' => 'themacle-dashboard',
                    'titulo' => __('Dashboard', 'flavor-chat-ia'),
                    'callback' => [$this, 'render_admin_dashboard'],
                ],
                [
                    'slug' => 'themacle-temas',
                    'titulo' => __('Temas', 'flavor-chat-ia'),
                    'callback' => [$this, 'render_admin_temas'],
                ],
                [
                    'slug' => 'themacle-config',
                    'titulo' => __('Configuración', 'flavor-chat-ia'),
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
                'label' => __('Componentes disponibles', 'flavor-chat-ia'),
                'color' => 'blue',
                'enlace' => admin_url('admin.php?page=themacle-dashboard'),
            ],
            [
                'icon' => 'dashicons-category',
                'valor' => count($categorias_componentes),
                'label' => __('Categorías', 'flavor-chat-ia'),
                'color' => 'purple',
                'enlace' => admin_url('admin.php?page=themacle-dashboard'),
            ],
        ];
    }

    /**
     * Renderiza el dashboard de Themacle
     */
    public function render_admin_dashboard() {
        echo '<div class="wrap flavor-modulo-page">';
        $this->render_page_header(__('Dashboard de Themacle', 'flavor-chat-ia'), [
            ['label' => __('Ver Documentación', 'flavor-chat-ia'), 'url' => '#', 'class' => 'button-secondary'],
        ]);

        $componentes_web = $this->get_web_components();
        $categorias_agrupadas = [];
        foreach ($componentes_web as $identificador_componente => $datos_componente) {
            $categoria = $datos_componente['category'] ?? 'otros';
            if (!isset($categorias_agrupadas[$categoria])) {
                $categorias_agrupadas[$categoria] = [];
            }
            $categorias_agrupadas[$categoria][$identificador_componente] = $datos_componente;
        }

        // Estadísticas rápidas
        echo '<div class="flavor-stats-grid">';
        echo '<div class="flavor-stat-card"><span class="stat-number">' . count($componentes_web) . '</span><span class="stat-label">' . __('Total Componentes', 'flavor-chat-ia') . '</span></div>';
        echo '<div class="flavor-stat-card"><span class="stat-number">' . count($categorias_agrupadas) . '</span><span class="stat-label">' . __('Categorías', 'flavor-chat-ia') . '</span></div>';
        echo '</div>';

        // Listado de componentes por categoría
        echo '<h2>' . __('Componentes Web Disponibles', 'flavor-chat-ia') . '</h2>';

        $nombres_categorias = [
            'hero' => __('Heroes', 'flavor-chat-ia'),
            'content' => __('Contenido', 'flavor-chat-ia'),
            'listings' => __('Listados', 'flavor-chat-ia'),
            'features' => __('Características', 'flavor-chat-ia'),
            'cta' => __('CTA', 'flavor-chat-ia'),
            'navigation' => __('Navegación', 'flavor-chat-ia'),
            'otros' => __('Otros', 'flavor-chat-ia'),
        ];

        foreach ($categorias_agrupadas as $categoria_slug => $lista_componentes) {
            $nombre_categoria = $nombres_categorias[$categoria_slug] ?? ucfirst($categoria_slug);
            echo '<h3>' . esc_html($nombre_categoria) . ' (' . count($lista_componentes) . ')</h3>';
            echo '<table class="wp-list-table widefat fixed striped">';
            echo '<thead><tr><th>' . __('Componente', 'flavor-chat-ia') . '</th><th>' . __('Descripción', 'flavor-chat-ia') . '</th><th>' . __('Template', 'flavor-chat-ia') . '</th></tr></thead>';
            echo '<tbody>';
            foreach ($lista_componentes as $identificador => $datos_componente_item) {
                echo '<tr>';
                echo '<td><span class="dashicons ' . esc_attr($datos_componente_item['icon'] ?? 'dashicons-admin-generic') . '"></span> <strong>' . esc_html($datos_componente_item['label']) . '</strong></td>';
                echo '<td>' . esc_html($datos_componente_item['description']) . '</td>';
                echo '<td><code>' . esc_html($datos_componente_item['template'] ?? '-') . '</code></td>';
                echo '</tr>';
            }
            echo '</tbody></table>';
        }

        echo '</div>';
    }

    /**
     * Renderiza la página de gestión de temas
     */
    public function render_admin_temas() {
        echo '<div class="wrap flavor-modulo-page">';
        $this->render_page_header(__('Gestión de Temas', 'flavor-chat-ia'));

        echo '<p>' . __('Los componentes Themacle se adaptan automáticamente al tema visual activo del sitio mediante CSS custom properties.', 'flavor-chat-ia') . '</p>';

        echo '<div class="card">';
        echo '<h3>' . __('Tema Activo', 'flavor-chat-ia') . '</h3>';
        echo '<p>' . __('El tema visual actual define los colores, tipografías y espaciados que utilizan todos los componentes.', 'flavor-chat-ia') . '</p>';
        echo '</div>';

        echo '<div class="card">';
        echo '<h3>' . __('Personalización', 'flavor-chat-ia') . '</h3>';
        echo '<p>' . __('Puedes personalizar las variables CSS para adaptar los componentes a tu marca.', 'flavor-chat-ia') . '</p>';
        echo '</div>';

        echo '</div>';
    }

    /**
     * Renderiza la configuración del módulo
     */
    public function render_admin_config() {
        echo '<div class="wrap flavor-modulo-page">';
        $this->render_page_header(__('Configuración de Themacle', 'flavor-chat-ia'));

        echo '<form method="post" action="">';
        echo '<table class="form-table">';

        echo '<tr><th scope="row"><label for="componentes_activos">' . __('Componentes Activos', 'flavor-chat-ia') . '</label></th>';
        echo '<td><p class="description">' . __('Todos los componentes están activos por defecto. Puedes desactivar componentes específicos si no los necesitas.', 'flavor-chat-ia') . '</p></td></tr>';

        echo '<tr><th scope="row"><label for="cache_templates">' . __('Cache de Templates', 'flavor-chat-ia') . '</label></th>';
        echo '<td><input type="checkbox" name="cache_templates" id="cache_templates" checked />';
        echo '<p class="description">' . __('Cachear los templates de componentes para mejorar el rendimiento.', 'flavor-chat-ia') . '</p></td></tr>';

        echo '</table>';
        echo '<p class="submit"><input type="submit" name="guardar_config" class="button-primary" value="' . __('Guardar Configuración', 'flavor-chat-ia') . '" /></p>';
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
        if ($nombre_accion === 'listar_componentes_web') {
            return $this->action_listar_componentes_web();
        }

        return ['success' => false, 'error' => "Acción no encontrada: {$nombre_accion}"];
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
                'label' => __('Hero Fullscreen', 'flavor-chat-ia'),
                'description' => __('Sección hero a pantalla completa con imagen/video de fondo', 'flavor-chat-ia'),
                'category' => 'hero',
                'icon' => 'dashicons-cover-image',
                'fields' => [
                    'titulo' => [
                        'type' => 'text',
                        'label' => __('Título', 'flavor-chat-ia'),
                        'default' => __('Bienvenido a nuestra web', 'flavor-chat-ia'),
                    ],
                    'subtitulo' => [
                        'type' => 'textarea',
                        'label' => __('Subtítulo', 'flavor-chat-ia'),
                        'default' => '',
                    ],
                    'imagen_fondo' => [
                        'type' => 'image',
                        'label' => __('Imagen de fondo', 'flavor-chat-ia'),
                        'default' => '',
                    ],
                    'video_fondo' => [
                        'type' => 'url',
                        'label' => __('URL del vídeo de fondo', 'flavor-chat-ia'),
                        'default' => '',
                    ],
                    'texto_cta' => [
                        'type' => 'text',
                        'label' => __('Texto del botón CTA', 'flavor-chat-ia'),
                        'default' => __('Saber más', 'flavor-chat-ia'),
                    ],
                    'url_cta' => [
                        'type' => 'url',
                        'label' => __('URL del botón CTA', 'flavor-chat-ia'),
                        'default' => '#',
                    ],
                    'overlay_color' => [
                        'type' => 'color',
                        'label' => __('Color del overlay', 'flavor-chat-ia'),
                        'default' => '#000000',
                    ],
                    'overlay_opacidad' => [
                        'type' => 'number',
                        'label' => __('Opacidad del overlay (0-100)', 'flavor-chat-ia'),
                        'default' => 50,
                    ],
                ],
                'template' => 'themacle/hero-fullscreen',
                'preview' => '',
            ],

            'hero_split' => [
                'label' => __('Hero Split', 'flavor-chat-ia'),
                'description' => __('Hero con diseño 50/50: imagen y texto lado a lado', 'flavor-chat-ia'),
                'category' => 'hero',
                'icon' => 'dashicons-columns',
                'fields' => [
                    'titulo' => [
                        'type' => 'text',
                        'label' => __('Título', 'flavor-chat-ia'),
                        'default' => __('Tu título aquí', 'flavor-chat-ia'),
                    ],
                    'subtitulo' => [
                        'type' => 'textarea',
                        'label' => __('Subtítulo', 'flavor-chat-ia'),
                        'default' => '',
                    ],
                    'texto_cta' => [
                        'type' => 'text',
                        'label' => __('Texto del botón', 'flavor-chat-ia'),
                        'default' => __('Empezar', 'flavor-chat-ia'),
                    ],
                    'url_cta' => [
                        'type' => 'url',
                        'label' => __('URL del botón', 'flavor-chat-ia'),
                        'default' => '#',
                    ],
                    'imagen' => [
                        'type' => 'image',
                        'label' => __('Imagen', 'flavor-chat-ia'),
                        'default' => '',
                    ],
                    'invertir' => [
                        'type' => 'toggle',
                        'label' => __('Invertir orden (imagen a la izquierda)', 'flavor-chat-ia'),
                        'default' => false,
                    ],
                    'color_fondo' => [
                        'type' => 'color',
                        'label' => __('Color de fondo', 'flavor-chat-ia'),
                        'default' => '#ffffff',
                    ],
                ],
                'template' => 'themacle/hero-split',
                'preview' => '',
            ],

            'hero_slider' => [
                'label' => __('Hero Slider', 'flavor-chat-ia'),
                'description' => __('Carrusel de slides con navegación por bullets', 'flavor-chat-ia'),
                'category' => 'hero',
                'icon' => 'dashicons-slides',
                'fields' => [
                    'slides' => [
                        'type' => 'repeater',
                        'label' => __('Slides', 'flavor-chat-ia'),
                        'fields' => [
                            'titulo' => ['type' => 'text', 'label' => __('Título', 'flavor-chat-ia'), 'default' => ''],
                            'subtitulo' => ['type' => 'textarea', 'label' => __('Subtítulo', 'flavor-chat-ia'), 'default' => ''],
                            'imagen' => ['type' => 'image', 'label' => __('Imagen de fondo', 'flavor-chat-ia'), 'default' => ''],
                            'url_cta' => ['type' => 'url', 'label' => __('URL del botón', 'flavor-chat-ia'), 'default' => '#'],
                            'texto_cta' => ['type' => 'text', 'label' => __('Texto del botón', 'flavor-chat-ia'), 'default' => ''],
                        ],
                        'default' => [],
                        'max_items' => 10,
                    ],
                    'autoplay' => [
                        'type' => 'toggle',
                        'label' => __('Autoplay', 'flavor-chat-ia'),
                        'default' => true,
                    ],
                    'intervalo' => [
                        'type' => 'number',
                        'label' => __('Intervalo en milisegundos', 'flavor-chat-ia'),
                        'default' => 5000,
                    ],
                ],
                'template' => 'themacle/hero-slider',
                'preview' => '',
            ],

            // ─── CONTENIDO ────────────────────────────────────
            'text_media' => [
                'label' => __('Texto + Media', 'flavor-chat-ia'),
                'description' => __('Bloque de texto con imagen lado a lado', 'flavor-chat-ia'),
                'category' => 'content',
                'icon' => 'dashicons-align-left',
                'fields' => [
                    'titulo' => [
                        'type' => 'text',
                        'label' => __('Título', 'flavor-chat-ia'),
                        'default' => '',
                    ],
                    'contenido' => [
                        'type' => 'textarea',
                        'label' => __('Contenido', 'flavor-chat-ia'),
                        'default' => '',
                    ],
                    'imagen' => [
                        'type' => 'image',
                        'label' => __('Imagen', 'flavor-chat-ia'),
                        'default' => '',
                    ],
                    'invertir' => [
                        'type' => 'toggle',
                        'label' => __('Invertir orden', 'flavor-chat-ia'),
                        'default' => false,
                    ],
                    'estilo' => [
                        'type' => 'select',
                        'label' => __('Estilo', 'flavor-chat-ia'),
                        'options' => ['simple', 'overlay'],
                        'default' => 'simple',
                    ],
                ],
                'template' => 'themacle/text-media',
                'preview' => '',
            ],

            'gallery' => [
                'label' => __('Galería', 'flavor-chat-ia'),
                'description' => __('Galería de imágenes en formato grid', 'flavor-chat-ia'),
                'category' => 'content',
                'icon' => 'dashicons-format-gallery',
                'fields' => [
                    'titulo' => [
                        'type' => 'text',
                        'label' => __('Título', 'flavor-chat-ia'),
                        'default' => '',
                    ],
                    'columnas' => [
                        'type' => 'select',
                        'label' => __('Columnas', 'flavor-chat-ia'),
                        'options' => [2, 3, 4],
                        'default' => 3,
                    ],
                    'imagenes' => [
                        'type' => 'repeater',
                        'label' => __('Imágenes', 'flavor-chat-ia'),
                        'fields' => [
                            'imagen' => ['type' => 'image', 'label' => __('Imagen', 'flavor-chat-ia'), 'default' => ''],
                            'titulo' => ['type' => 'text', 'label' => __('Título', 'flavor-chat-ia'), 'default' => ''],
                        ],
                        'default' => [],
                        'max_items' => 24,
                    ],
                ],
                'template' => 'themacle/gallery',
                'preview' => '',
            ],

            'accordion' => [
                'label' => __('Acordeón / FAQ', 'flavor-chat-ia'),
                'description' => __('Lista desplegable de preguntas y respuestas', 'flavor-chat-ia'),
                'category' => 'content',
                'icon' => 'dashicons-list-view',
                'fields' => [
                    'titulo' => [
                        'type' => 'text',
                        'label' => __('Título', 'flavor-chat-ia'),
                        'default' => __('Preguntas Frecuentes', 'flavor-chat-ia'),
                    ],
                    'items' => [
                        'type' => 'repeater',
                        'label' => __('Preguntas', 'flavor-chat-ia'),
                        'fields' => [
                            'pregunta' => ['type' => 'text', 'label' => __('Pregunta', 'flavor-chat-ia'), 'default' => ''],
                            'respuesta' => ['type' => 'textarea', 'label' => __('Respuesta', 'flavor-chat-ia'), 'default' => ''],
                        ],
                        'default' => [],
                        'max_items' => 20,
                    ],
                ],
                'template' => 'themacle/accordion',
                'preview' => '',
            ],

            'map_section' => [
                'label' => __('Mapa + Contacto', 'flavor-chat-ia'),
                'description' => __('Sección con mapa embebido e información de contacto', 'flavor-chat-ia'),
                'category' => 'content',
                'icon' => 'dashicons-location-alt',
                'fields' => [
                    'titulo' => [
                        'type' => 'text',
                        'label' => __('Título', 'flavor-chat-ia'),
                        'default' => __('Dónde Encontrarnos', 'flavor-chat-ia'),
                    ],
                    'direccion' => [
                        'type' => 'text',
                        'label' => __('Dirección', 'flavor-chat-ia'),
                        'default' => '',
                    ],
                    'telefono' => [
                        'type' => 'text',
                        'label' => __('Teléfono', 'flavor-chat-ia'),
                        'default' => '',
                    ],
                    'email' => [
                        'type' => 'email',
                        'label' => __('Email', 'flavor-chat-ia'),
                        'default' => '',
                    ],
                    'horario' => [
                        'type' => 'textarea',
                        'label' => __('Horario', 'flavor-chat-ia'),
                        'default' => '',
                    ],
                    'mostrar_formulario' => [
                        'type' => 'toggle',
                        'label' => __('Mostrar formulario de contacto', 'flavor-chat-ia'),
                        'default' => false,
                    ],
                ],
                'template' => 'themacle/map-section',
                'preview' => '',
            ],

            'post_content' => [
                'label' => __('Contenido de Post', 'flavor-chat-ia'),
                'description' => __('Contenido de artículo o entrada individual', 'flavor-chat-ia'),
                'category' => 'content',
                'icon' => 'dashicons-media-text',
                'fields' => [
                    'mostrar_imagen_destacada' => [
                        'type' => 'toggle',
                        'label' => __('Mostrar imagen destacada', 'flavor-chat-ia'),
                        'default' => true,
                    ],
                    'mostrar_fecha' => [
                        'type' => 'toggle',
                        'label' => __('Mostrar fecha', 'flavor-chat-ia'),
                        'default' => true,
                    ],
                    'mostrar_autor' => [
                        'type' => 'toggle',
                        'label' => __('Mostrar autor', 'flavor-chat-ia'),
                        'default' => true,
                    ],
                    'mostrar_compartir' => [
                        'type' => 'toggle',
                        'label' => __('Mostrar botones de compartir', 'flavor-chat-ia'),
                        'default' => true,
                    ],
                ],
                'template' => 'themacle/post-content',
                'preview' => '',
            ],

            // ─── LISTADOS ─────────────────────────────────────
            'card_grid' => [
                'label' => __('Grid de Tarjetas', 'flavor-chat-ia'),
                'description' => __('Grid configurable de tarjetas con imagen, título y descripción', 'flavor-chat-ia'),
                'category' => 'listings',
                'icon' => 'dashicons-grid-view',
                'fields' => [
                    'titulo' => [
                        'type' => 'text',
                        'label' => __('Título de sección', 'flavor-chat-ia'),
                        'default' => '',
                    ],
                    'columnas' => [
                        'type' => 'select',
                        'label' => __('Columnas', 'flavor-chat-ia'),
                        'options' => [2, 3, 4],
                        'default' => 3,
                    ],
                    'estilo_card' => [
                        'type' => 'select',
                        'label' => __('Estilo de tarjeta', 'flavor-chat-ia'),
                        'options' => ['shadow', 'border', 'flat'],
                        'default' => 'shadow',
                    ],
                    'fuente_datos' => [
                        'type' => 'data_source',
                        'label' => __('Fuente de datos', 'flavor-chat-ia'),
                        'post_types' => [],
                        'items_field' => 'items',
                        'default' => 'manual',
                    ],
                    'items' => [
                        'type' => 'repeater',
                        'label' => __('Tarjetas', 'flavor-chat-ia'),
                        'fields' => [
                            'imagen' => ['type' => 'image', 'label' => __('Imagen', 'flavor-chat-ia'), 'default' => ''],
                            'titulo' => ['type' => 'text', 'label' => __('Título', 'flavor-chat-ia'), 'default' => ''],
                            'descripcion' => ['type' => 'textarea', 'label' => __('Descripción', 'flavor-chat-ia'), 'default' => ''],
                            'url' => ['type' => 'url', 'label' => __('URL', 'flavor-chat-ia'), 'default' => '#'],
                        ],
                        'default' => [],
                        'max_items' => 12,
                    ],
                ],
                'template' => 'themacle/card-grid',
                'preview' => '',
            ],

            'related_items' => [
                'label' => __('Items Relacionados', 'flavor-chat-ia'),
                'description' => __('Grid de elementos relacionados con datos dinámicos', 'flavor-chat-ia'),
                'category' => 'listings',
                'icon' => 'dashicons-networking',
                'fields' => [
                    'titulo' => [
                        'type' => 'text',
                        'label' => __('Título', 'flavor-chat-ia'),
                        'default' => __('Relacionados', 'flavor-chat-ia'),
                    ],
                    'columnas' => [
                        'type' => 'select',
                        'label' => __('Columnas', 'flavor-chat-ia'),
                        'options' => [2, 3, 4],
                        'default' => 3,
                    ],
                    'fuente_datos' => [
                        'type' => 'data_source',
                        'label' => __('Fuente de datos', 'flavor-chat-ia'),
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
                'label' => __('Grid de Características', 'flavor-chat-ia'),
                'description' => __('Grid de iconos o imágenes con título y descripción', 'flavor-chat-ia'),
                'category' => 'features',
                'icon' => 'dashicons-star-filled',
                'fields' => [
                    'titulo' => [
                        'type' => 'text',
                        'label' => __('Título', 'flavor-chat-ia'),
                        'default' => '',
                    ],
                    'subtitulo' => [
                        'type' => 'textarea',
                        'label' => __('Subtítulo', 'flavor-chat-ia'),
                        'default' => '',
                    ],
                    'columnas' => [
                        'type' => 'select',
                        'label' => __('Columnas', 'flavor-chat-ia'),
                        'options' => [2, 3, 4],
                        'default' => 3,
                    ],
                    'items' => [
                        'type' => 'repeater',
                        'label' => __('Características', 'flavor-chat-ia'),
                        'fields' => [
                            'icono' => ['type' => 'text', 'label' => __('Icono (dashicons)', 'flavor-chat-ia'), 'default' => 'dashicons-star-filled'],
                            'titulo' => ['type' => 'text', 'label' => __('Título', 'flavor-chat-ia'), 'default' => ''],
                            'descripcion' => ['type' => 'textarea', 'label' => __('Descripción', 'flavor-chat-ia'), 'default' => ''],
                        ],
                        'default' => [],
                        'max_items' => 12,
                    ],
                ],
                'template' => 'themacle/feature-grid',
                'preview' => '',
            ],

            'highlights' => [
                'label' => __('Destacados', 'flavor-chat-ia'),
                'description' => __('Sección de elementos destacados con iconos o imágenes', 'flavor-chat-ia'),
                'category' => 'features',
                'icon' => 'dashicons-awards',
                'fields' => [
                    'titulo' => [
                        'type' => 'text',
                        'label' => __('Título', 'flavor-chat-ia'),
                        'default' => '',
                    ],
                    'items' => [
                        'type' => 'repeater',
                        'label' => __('Destacados', 'flavor-chat-ia'),
                        'fields' => [
                            'imagen' => ['type' => 'image', 'label' => __('Imagen/Icono', 'flavor-chat-ia'), 'default' => ''],
                            'titulo' => ['type' => 'text', 'label' => __('Título', 'flavor-chat-ia'), 'default' => ''],
                            'descripcion' => ['type' => 'textarea', 'label' => __('Descripción', 'flavor-chat-ia'), 'default' => ''],
                            'url' => ['type' => 'url', 'label' => __('URL', 'flavor-chat-ia'), 'default' => ''],
                        ],
                        'default' => [],
                        'max_items' => 8,
                    ],
                    'estilo' => [
                        'type' => 'select',
                        'label' => __('Estilo', 'flavor-chat-ia'),
                        'options' => ['cards', 'icons', 'minimal'],
                        'default' => 'cards',
                    ],
                ],
                'template' => 'themacle/highlights',
                'preview' => '',
            ],

            // ─── CTA ──────────────────────────────────────────
            'cta_banner' => [
                'label' => __('Banner CTA', 'flavor-chat-ia'),
                'description' => __('Llamada a la acción con fondo de color o imagen', 'flavor-chat-ia'),
                'category' => 'cta',
                'icon' => 'dashicons-megaphone',
                'fields' => [
                    'titulo' => [
                        'type' => 'text',
                        'label' => __('Título', 'flavor-chat-ia'),
                        'default' => '',
                    ],
                    'subtitulo' => [
                        'type' => 'textarea',
                        'label' => __('Subtítulo', 'flavor-chat-ia'),
                        'default' => '',
                    ],
                    'texto_cta' => [
                        'type' => 'text',
                        'label' => __('Texto del botón', 'flavor-chat-ia'),
                        'default' => __('Contactar', 'flavor-chat-ia'),
                    ],
                    'url_cta' => [
                        'type' => 'url',
                        'label' => __('URL del botón', 'flavor-chat-ia'),
                        'default' => '#',
                    ],
                    'imagen_fondo' => [
                        'type' => 'image',
                        'label' => __('Imagen de fondo', 'flavor-chat-ia'),
                        'default' => '',
                    ],
                    'color_fondo' => [
                        'type' => 'color',
                        'label' => __('Color de fondo', 'flavor-chat-ia'),
                        'default' => '',
                    ],
                ],
                'template' => 'themacle/cta-banner',
                'preview' => '',
            ],

            'newsletter' => [
                'label' => __('Newsletter', 'flavor-chat-ia'),
                'description' => __('Formulario de suscripción por email', 'flavor-chat-ia'),
                'category' => 'cta',
                'icon' => 'dashicons-email-alt',
                'fields' => [
                    'titulo' => [
                        'type' => 'text',
                        'label' => __('Título', 'flavor-chat-ia'),
                        'default' => __('Suscríbete', 'flavor-chat-ia'),
                    ],
                    'subtitulo' => [
                        'type' => 'textarea',
                        'label' => __('Subtítulo', 'flavor-chat-ia'),
                        'default' => '',
                    ],
                    'texto_placeholder' => [
                        'type' => 'text',
                        'label' => __('Placeholder del campo', 'flavor-chat-ia'),
                        'default' => __('Tu email', 'flavor-chat-ia'),
                    ],
                    'texto_boton' => [
                        'type' => 'text',
                        'label' => __('Texto del botón', 'flavor-chat-ia'),
                        'default' => __('Suscribirme', 'flavor-chat-ia'),
                    ],
                    'color_fondo' => [
                        'type' => 'color',
                        'label' => __('Color de fondo', 'flavor-chat-ia'),
                        'default' => '',
                    ],
                ],
                'template' => 'themacle/newsletter',
                'preview' => '',
            ],

            // ─── NAVEGACIÓN ───────────────────────────────────
            'filters_bar' => [
                'label' => __('Barra de Filtros', 'flavor-chat-ia'),
                'description' => __('Barra de filtros por taxonomía con diferentes estilos', 'flavor-chat-ia'),
                'category' => 'navigation',
                'icon' => 'dashicons-filter',
                'fields' => [
                    'taxonomia' => [
                        'type' => 'text',
                        'label' => __('Taxonomía (slug)', 'flavor-chat-ia'),
                        'default' => 'category',
                    ],
                    'estilo' => [
                        'type' => 'select',
                        'label' => __('Estilo', 'flavor-chat-ia'),
                        'options' => ['underline', 'pills', 'dropdown'],
                        'default' => 'pills',
                    ],
                ],
                'template' => 'themacle/filters-bar',
                'preview' => '',
            ],

            'pagination' => [
                'label' => __('Paginación', 'flavor-chat-ia'),
                'description' => __('Navegación numérica entre páginas', 'flavor-chat-ia'),
                'category' => 'navigation',
                'icon' => 'dashicons-controls-forward',
                'fields' => [
                    'estilo' => [
                        'type' => 'select',
                        'label' => __('Estilo', 'flavor-chat-ia'),
                        'options' => ['numbers', 'simple', 'load-more'],
                        'default' => 'numbers',
                    ],
                ],
                'template' => 'themacle/pagination',
                'preview' => '',
            ],
        ];
    }
}

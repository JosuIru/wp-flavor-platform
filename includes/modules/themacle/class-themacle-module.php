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

    /**
     * Constructor
     */
    public function __construct() {
        $this->id = 'themacle';
        $this->name = __('Themacle Web Components', 'flavor-chat-ia');
        $this->description = __('Componentes web universales reutilizables para construir cualquier tipo de web', 'flavor-chat-ia');
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
     * Inicializar hooks del módulo
     */
    public function init() {
        // No requiere hooks especiales - solo registra componentes web
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

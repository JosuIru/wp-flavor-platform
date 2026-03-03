<?php
/**
 * Editor Visual de Landing Pages
 *
 * Proporciona un editor drag & drop para crear landing pages
 *
 * @package FlavorChatIA
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Clase principal del editor de landing pages
 */
class Flavor_Landing_Editor {

    /**
     * Instancia singleton
     */
    private static $instance = null;

    /**
     * Secciones disponibles
     */
    private $available_sections = [];

    /**
     * Meta key para guardar la estructura
     */
    const META_KEY = '_flavor_landing_structure';

    /**
     * Meta key para el historial
     */
    const HISTORY_META_KEY = '_flavor_landing_history';

    /**
     * Obtiene la instancia singleton
     *
     * @return Flavor_Landing_Editor
     */
    public static function get_instance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Constructor
     */
    private function __construct() {
        $this->init_sections();
        $this->init_hooks();
    }

    /**
     * Inicializa hooks
     */
    private function init_hooks() {
        add_action('admin_enqueue_scripts', [$this, 'enqueue_assets']);
        add_action('add_meta_boxes', [$this, 'add_meta_box']);
        add_action('wp_ajax_flavor_landing_save', [$this, 'ajax_save_structure']);
        add_action('wp_ajax_flavor_landing_load', [$this, 'ajax_load_structure']);
        add_action('wp_ajax_flavor_landing_preview', [$this, 'ajax_render_preview']);
        add_action('wp_ajax_flavor_landing_get_sections', [$this, 'ajax_get_sections']);
        add_action('wp_ajax_flavor_landing_autosave', [$this, 'ajax_autosave']);
        add_action('wp_ajax_flavor_landing_undo', [$this, 'ajax_undo']);
        add_action('wp_ajax_flavor_landing_get_templates', [$this, 'ajax_get_templates']);
        // NOTA: El menú se registra centralizadamente en class-admin-menu-manager.php
        // add_action('admin_menu', [$this, 'add_editor_page']);

        // Shortcode para renderizar desde estructura guardada
        add_shortcode('flavor_landing_visual', [$this, 'render_from_shortcode']);

        // Redirigir edición de flavor_landing al Landing Editor dedicado
        add_action('admin_init', [$this, 'redirect_landing_edit']);
    }

    /**
     * Redirige la edición de flavor_landing al Landing Editor dedicado
     * NOTA: Deshabilitado - VBP (Visual Builder Pro) es ahora el editor principal
     */
    public function redirect_landing_edit() {
        // VBP maneja la redirección ahora
        return;
    }

    /**
     * Inicializa las secciones disponibles
     */
    private function init_sections() {
        $this->available_sections = [
            'hero' => [
                'label' => __('Hero', 'flavor-chat-ia'),
                'description' => __('Sección principal con título, subtítulo y llamada a la acción', 'flavor-chat-ia'),
                'icon' => 'dashicons-cover-image',
                'variants' => [
                    'centrado' => __('Centrado', 'flavor-chat-ia'),
                    'split' => __('Dividido', 'flavor-chat-ia'),
                    'con_video' => __('Con Video', 'flavor-chat-ia'),
                    'minimalista' => __('Minimalista', 'flavor-chat-ia'),
                    'con_imagen_fondo' => __('Imagen de Fondo', 'flavor-chat-ia'),
                ],
                'fields' => [
                    'titulo' => [
                        'type' => 'text',
                        'label' => __('Título', 'flavor-chat-ia'),
                        'default' => __('Tu título principal aquí', 'flavor-chat-ia'),
                    ],
                    'subtitulo' => [
                        'type' => 'textarea',
                        'label' => __('Subtítulo', 'flavor-chat-ia'),
                        'default' => __('Describe brevemente tu propuesta de valor', 'flavor-chat-ia'),
                    ],
                    'imagen' => [
                        'type' => 'image',
                        'label' => __('Imagen', 'flavor-chat-ia'),
                        'default' => '',
                    ],
                    'video_url' => [
                        'type' => 'url',
                        'label' => __('URL del Video', 'flavor-chat-ia'),
                        'default' => '',
                        'condition' => ['variant' => 'con_video'],
                    ],
                    'cta_texto' => [
                        'type' => 'text',
                        'label' => __('Texto del Botón', 'flavor-chat-ia'),
                        'default' => __('Comenzar', 'flavor-chat-ia'),
                    ],
                    'cta_url' => [
                        'type' => 'url',
                        'label' => __('URL del Botón', 'flavor-chat-ia'),
                        'default' => '#',
                    ],
                    'cta_secundario_texto' => [
                        'type' => 'text',
                        'label' => __('Botón Secundario', 'flavor-chat-ia'),
                        'default' => '',
                    ],
                    'cta_secundario_url' => [
                        'type' => 'url',
                        'label' => __('URL Secundaria', 'flavor-chat-ia'),
                        'default' => '',
                    ],
                    'color_fondo' => [
                        'type' => 'color',
                        'label' => __('Color de Fondo', 'flavor-chat-ia'),
                        'default' => '#f8fafc',
                    ],
                    'color_texto' => [
                        'type' => 'color',
                        'label' => __('Color de Texto', 'flavor-chat-ia'),
                        'default' => '#1e293b',
                    ],
                ],
            ],
            'features' => [
                'label' => __('Características', 'flavor-chat-ia'),
                'description' => __('Lista de características o beneficios con iconos', 'flavor-chat-ia'),
                'icon' => 'dashicons-star-filled',
                'variants' => [
                    'grid_3' => __('3 Columnas', 'flavor-chat-ia'),
                    'grid_4' => __('4 Columnas', 'flavor-chat-ia'),
                    'lista' => __('Lista Vertical', 'flavor-chat-ia'),
                    'iconos_grandes' => __('Iconos Grandes', 'flavor-chat-ia'),
                    'alternado' => __('Alternado', 'flavor-chat-ia'),
                ],
                'fields' => [
                    'titulo' => [
                        'type' => 'text',
                        'label' => __('Título de Sección', 'flavor-chat-ia'),
                        'default' => __('Nuestras Características', 'flavor-chat-ia'),
                    ],
                    'subtitulo' => [
                        'type' => 'textarea',
                        'label' => __('Subtítulo', 'flavor-chat-ia'),
                        'default' => '',
                    ],
                    'items' => [
                        'type' => 'repeater',
                        'label' => __('Características', 'flavor-chat-ia'),
                        'fields' => [
                            'icono' => ['type' => 'icon', 'label' => __('Icono', 'flavor-chat-ia')],
                            'titulo' => ['type' => 'text', 'label' => __('Título', 'flavor-chat-ia')],
                            'descripcion' => ['type' => 'textarea', 'label' => __('Descripción', 'flavor-chat-ia')],
                        ],
                        'default' => [
                            ['icono' => 'star-filled', 'titulo' => __('Característica 1', 'flavor-chat-ia'), 'descripcion' => __('Descripción breve', 'flavor-chat-ia')],
                            ['icono' => 'heart', 'titulo' => __('Característica 2', 'flavor-chat-ia'), 'descripcion' => __('Descripción breve', 'flavor-chat-ia')],
                            ['icono' => 'lightbulb', 'titulo' => __('Característica 3', 'flavor-chat-ia'), 'descripcion' => __('Descripción breve', 'flavor-chat-ia')],
                        ],
                    ],
                    'color_fondo' => [
                        'type' => 'color',
                        'label' => __('Color de Fondo', 'flavor-chat-ia'),
                        'default' => '#ffffff',
                    ],
                ],
            ],
            'testimonios' => [
                'label' => __('Testimonios', 'flavor-chat-ia'),
                'description' => __('Opiniones y reseñas de clientes', 'flavor-chat-ia'),
                'icon' => 'dashicons-format-quote',
                'variants' => [
                    'carrusel' => __('Carrusel', 'flavor-chat-ia'),
                    'grid' => __('Grid', 'flavor-chat-ia'),
                    'destacado' => __('Destacado', 'flavor-chat-ia'),
                    'simple' => __('Simple', 'flavor-chat-ia'),
                ],
                'fields' => [
                    'titulo' => [
                        'type' => 'text',
                        'label' => __('Título', 'flavor-chat-ia'),
                        'default' => __('Lo que dicen nuestros clientes', 'flavor-chat-ia'),
                    ],
                    'items' => [
                        'type' => 'repeater',
                        'label' => __('Testimonios', 'flavor-chat-ia'),
                        'fields' => [
                            'texto' => ['type' => 'textarea', 'label' => __('Testimonio', 'flavor-chat-ia')],
                            'nombre' => ['type' => 'text', 'label' => __('Nombre', 'flavor-chat-ia')],
                            'cargo' => ['type' => 'text', 'label' => __('Cargo/Empresa', 'flavor-chat-ia')],
                            'imagen' => ['type' => 'image', 'label' => __('Foto', 'flavor-chat-ia')],
                            'valoracion' => ['type' => 'number', 'label' => __('Valoración (1-5)', 'flavor-chat-ia'), 'min' => 1, 'max' => 5],
                        ],
                        'default' => [
                            ['texto' => __('Excelente servicio, muy recomendable.', 'flavor-chat-ia'), 'nombre' => 'María García', 'cargo' => 'CEO, Empresa', 'valoracion' => 5],
                        ],
                    ],
                    'color_fondo' => [
                        'type' => 'color',
                        'label' => __('Color de Fondo', 'flavor-chat-ia'),
                        'default' => '#f1f5f9',
                    ],
                ],
            ],
            'pricing' => [
                'label' => __('Precios', 'flavor-chat-ia'),
                'description' => __('Tabla de planes y precios', 'flavor-chat-ia'),
                'icon' => 'dashicons-money-alt',
                'variants' => [
                    'columnas' => __('Columnas', 'flavor-chat-ia'),
                    'destacado_central' => __('Destacado Central', 'flavor-chat-ia'),
                    'horizontal' => __('Horizontal', 'flavor-chat-ia'),
                    'comparativa' => __('Comparativa', 'flavor-chat-ia'),
                ],
                'fields' => [
                    'titulo' => [
                        'type' => 'text',
                        'label' => __('Título', 'flavor-chat-ia'),
                        'default' => __('Nuestros Planes', 'flavor-chat-ia'),
                    ],
                    'subtitulo' => [
                        'type' => 'textarea',
                        'label' => __('Subtítulo', 'flavor-chat-ia'),
                        'default' => __('Elige el plan que mejor se adapte a ti', 'flavor-chat-ia'),
                    ],
                    'items' => [
                        'type' => 'repeater',
                        'label' => __('Planes', 'flavor-chat-ia'),
                        'fields' => [
                            'nombre' => ['type' => 'text', 'label' => __('Nombre del Plan', 'flavor-chat-ia')],
                            'precio' => ['type' => 'text', 'label' => __('Precio', 'flavor-chat-ia')],
                            'periodo' => ['type' => 'text', 'label' => __('Periodo', 'flavor-chat-ia')],
                            'descripcion' => ['type' => 'textarea', 'label' => __('Descripción', 'flavor-chat-ia')],
                            'caracteristicas' => ['type' => 'textarea', 'label' => __('Características (una por línea)', 'flavor-chat-ia')],
                            'destacado' => ['type' => 'checkbox', 'label' => __('Destacar', 'flavor-chat-ia')],
                            'cta_texto' => ['type' => 'text', 'label' => __('Texto Botón', 'flavor-chat-ia')],
                            'cta_url' => ['type' => 'url', 'label' => __('URL Botón', 'flavor-chat-ia')],
                        ],
                        'default' => [
                            ['nombre' => 'Básico', 'precio' => '9€', 'periodo' => '/mes', 'caracteristicas' => "Característica 1\nCaracterística 2", 'cta_texto' => 'Elegir'],
                            ['nombre' => 'Pro', 'precio' => '29€', 'periodo' => '/mes', 'destacado' => true, 'caracteristicas' => "Todo lo anterior\nCaracterística extra", 'cta_texto' => 'Elegir'],
                            ['nombre' => 'Enterprise', 'precio' => '99€', 'periodo' => '/mes', 'caracteristicas' => "Todo lo anterior\nSoporte premium", 'cta_texto' => 'Contactar'],
                        ],
                    ],
                    'color_fondo' => [
                        'type' => 'color',
                        'label' => __('Color de Fondo', 'flavor-chat-ia'),
                        'default' => '#ffffff',
                    ],
                ],
            ],
            'cta' => [
                'label' => __('Llamada a la Acción', 'flavor-chat-ia'),
                'description' => __('Sección para convertir visitantes', 'flavor-chat-ia'),
                'icon' => 'dashicons-megaphone',
                'variants' => [
                    'centrado' => __('Centrado', 'flavor-chat-ia'),
                    'con_imagen' => __('Con Imagen', 'flavor-chat-ia'),
                    'banner' => __('Banner', 'flavor-chat-ia'),
                    'formulario' => __('Con Formulario', 'flavor-chat-ia'),
                ],
                'fields' => [
                    'titulo' => [
                        'type' => 'text',
                        'label' => __('Título', 'flavor-chat-ia'),
                        'default' => __('¿Listo para empezar?', 'flavor-chat-ia'),
                    ],
                    'descripcion' => [
                        'type' => 'textarea',
                        'label' => __('Descripción', 'flavor-chat-ia'),
                        'default' => __('Únete a miles de usuarios satisfechos', 'flavor-chat-ia'),
                    ],
                    'imagen' => [
                        'type' => 'image',
                        'label' => __('Imagen', 'flavor-chat-ia'),
                        'default' => '',
                    ],
                    'boton_texto' => [
                        'type' => 'text',
                        'label' => __('Texto del Botón', 'flavor-chat-ia'),
                        'default' => __('Comenzar Ahora', 'flavor-chat-ia'),
                    ],
                    'boton_url' => [
                        'type' => 'url',
                        'label' => __('URL del Botón', 'flavor-chat-ia'),
                        'default' => '#',
                    ],
                    'boton_secundario_texto' => [
                        'type' => 'text',
                        'label' => __('Botón Secundario', 'flavor-chat-ia'),
                        'default' => '',
                    ],
                    'boton_secundario_url' => [
                        'type' => 'url',
                        'label' => __('URL Secundaria', 'flavor-chat-ia'),
                        'default' => '',
                    ],
                    'color_fondo' => [
                        'type' => 'color',
                        'label' => __('Color de Fondo', 'flavor-chat-ia'),
                        'default' => '#3b82f6',
                    ],
                    'color_texto' => [
                        'type' => 'color',
                        'label' => __('Color de Texto', 'flavor-chat-ia'),
                        'default' => '#ffffff',
                    ],
                ],
            ],
            'faq' => [
                'label' => __('Preguntas Frecuentes', 'flavor-chat-ia'),
                'description' => __('Acordeón de preguntas y respuestas', 'flavor-chat-ia'),
                'icon' => 'dashicons-editor-help',
                'variants' => [
                    'acordeon' => __('Acordeón', 'flavor-chat-ia'),
                    'dos_columnas' => __('Dos Columnas', 'flavor-chat-ia'),
                    'con_categorias' => __('Con Categorías', 'flavor-chat-ia'),
                    'simple' => __('Simple', 'flavor-chat-ia'),
                ],
                'fields' => [
                    'titulo' => [
                        'type' => 'text',
                        'label' => __('Título', 'flavor-chat-ia'),
                        'default' => __('Preguntas Frecuentes', 'flavor-chat-ia'),
                    ],
                    'subtitulo' => [
                        'type' => 'textarea',
                        'label' => __('Subtítulo', 'flavor-chat-ia'),
                        'default' => '',
                    ],
                    'items' => [
                        'type' => 'repeater',
                        'label' => __('Preguntas', 'flavor-chat-ia'),
                        'fields' => [
                            'pregunta' => ['type' => 'text', 'label' => __('Pregunta', 'flavor-chat-ia')],
                            'respuesta' => ['type' => 'textarea', 'label' => __('Respuesta', 'flavor-chat-ia')],
                        ],
                        'default' => [
                            ['pregunta' => __('¿Cómo funciona?', 'flavor-chat-ia'), 'respuesta' => __('Es muy sencillo...', 'flavor-chat-ia')],
                            ['pregunta' => __('¿Cuánto cuesta?', 'flavor-chat-ia'), 'respuesta' => __('Tenemos varios planes...', 'flavor-chat-ia')],
                        ],
                    ],
                    'color_fondo' => [
                        'type' => 'color',
                        'label' => __('Color de Fondo', 'flavor-chat-ia'),
                        'default' => '#f8fafc',
                    ],
                ],
            ],
            'contacto' => [
                'label' => __('Contacto', 'flavor-chat-ia'),
                'description' => __('Formulario y datos de contacto', 'flavor-chat-ia'),
                'icon' => 'dashicons-email-alt',
                'variants' => [
                    'formulario' => __('Solo Formulario', 'flavor-chat-ia'),
                    'con_mapa' => __('Con Mapa', 'flavor-chat-ia'),
                    'con_info' => __('Con Información', 'flavor-chat-ia'),
                    'split' => __('Dividido', 'flavor-chat-ia'),
                ],
                'fields' => [
                    'titulo' => [
                        'type' => 'text',
                        'label' => __('Título', 'flavor-chat-ia'),
                        'default' => __('Contáctanos', 'flavor-chat-ia'),
                    ],
                    'subtitulo' => [
                        'type' => 'textarea',
                        'label' => __('Subtítulo', 'flavor-chat-ia'),
                        'default' => __('Estamos aquí para ayudarte', 'flavor-chat-ia'),
                    ],
                    'email' => [
                        'type' => 'email',
                        'label' => __('Email', 'flavor-chat-ia'),
                        'default' => get_option('admin_email'),
                    ],
                    'telefono' => [
                        'type' => 'text',
                        'label' => __('Teléfono', 'flavor-chat-ia'),
                        'default' => '',
                    ],
                    'direccion' => [
                        'type' => 'textarea',
                        'label' => __('Dirección', 'flavor-chat-ia'),
                        'default' => '',
                    ],
                    'mapa_embed' => [
                        'type' => 'textarea',
                        'label' => __('Código Embed del Mapa', 'flavor-chat-ia'),
                        'default' => '',
                    ],
                    'mostrar_formulario' => [
                        'type' => 'checkbox',
                        'label' => __('Mostrar Formulario', 'flavor-chat-ia'),
                        'default' => true,
                    ],
                    'color_fondo' => [
                        'type' => 'color',
                        'label' => __('Color de Fondo', 'flavor-chat-ia'),
                        'default' => '#ffffff',
                    ],
                ],
            ],
            'galeria' => [
                'label' => __('Galería', 'flavor-chat-ia'),
                'description' => __('Galería de imágenes o portfolio', 'flavor-chat-ia'),
                'icon' => 'dashicons-format-gallery',
                'variants' => [
                    'grid' => __('Grid', 'flavor-chat-ia'),
                    'masonry' => __('Masonry', 'flavor-chat-ia'),
                    'carrusel' => __('Carrusel', 'flavor-chat-ia'),
                    'lightbox' => __('Con Lightbox', 'flavor-chat-ia'),
                ],
                'fields' => [
                    'titulo' => [
                        'type' => 'text',
                        'label' => __('Título', 'flavor-chat-ia'),
                        'default' => __('Nuestra Galería', 'flavor-chat-ia'),
                    ],
                    'subtitulo' => [
                        'type' => 'textarea',
                        'label' => __('Subtítulo', 'flavor-chat-ia'),
                        'default' => '',
                    ],
                    'imagenes' => [
                        'type' => 'gallery',
                        'label' => __('Imágenes', 'flavor-chat-ia'),
                        'default' => [],
                    ],
                    'columnas' => [
                        'type' => 'select',
                        'label' => __('Columnas', 'flavor-chat-ia'),
                        'options' => ['2' => '2', '3' => '3', '4' => '4', '5' => '5'],
                        'default' => '3',
                    ],
                    'color_fondo' => [
                        'type' => 'color',
                        'label' => __('Color de Fondo', 'flavor-chat-ia'),
                        'default' => '#f8fafc',
                    ],
                ],
            ],
            'stats' => [
                'label' => __('Estadísticas', 'flavor-chat-ia'),
                'description' => __('Números y métricas destacadas', 'flavor-chat-ia'),
                'icon' => 'dashicons-chart-bar',
                'variants' => [
                    'horizontal' => __('Horizontal', 'flavor-chat-ia'),
                    'con_iconos' => __('Con Iconos', 'flavor-chat-ia'),
                    'animado' => __('Animado', 'flavor-chat-ia'),
                    'tarjetas' => __('Tarjetas', 'flavor-chat-ia'),
                ],
                'fields' => [
                    'titulo' => [
                        'type' => 'text',
                        'label' => __('Título', 'flavor-chat-ia'),
                        'default' => '',
                    ],
                    'items' => [
                        'type' => 'repeater',
                        'label' => __('Estadísticas', 'flavor-chat-ia'),
                        'fields' => [
                            'numero' => ['type' => 'text', 'label' => __('Número', 'flavor-chat-ia')],
                            'sufijo' => ['type' => 'text', 'label' => __('Sufijo (+, %, etc.)', 'flavor-chat-ia')],
                            'etiqueta' => ['type' => 'text', 'label' => __('Etiqueta', 'flavor-chat-ia')],
                            'icono' => ['type' => 'icon', 'label' => __('Icono', 'flavor-chat-ia')],
                        ],
                        'default' => [
                            ['numero' => '1000', 'sufijo' => '+', 'etiqueta' => __('Clientes', 'flavor-chat-ia')],
                            ['numero' => '50', 'sufijo' => '+', 'etiqueta' => __('Proyectos', 'flavor-chat-ia')],
                            ['numero' => '99', 'sufijo' => '%', 'etiqueta' => __('Satisfacción', 'flavor-chat-ia')],
                            ['numero' => '24', 'sufijo' => '/7', 'etiqueta' => __('Soporte', 'flavor-chat-ia')],
                        ],
                    ],
                    'color_fondo' => [
                        'type' => 'color',
                        'label' => __('Color de Fondo', 'flavor-chat-ia'),
                        'default' => '#1e293b',
                    ],
                    'color_texto' => [
                        'type' => 'color',
                        'label' => __('Color de Texto', 'flavor-chat-ia'),
                        'default' => '#ffffff',
                    ],
                ],
            ],
            'equipo' => [
                'label' => __('Equipo', 'flavor-chat-ia'),
                'description' => __('Miembros del equipo', 'flavor-chat-ia'),
                'icon' => 'dashicons-groups',
                'variants' => [
                    'grid' => __('Grid', 'flavor-chat-ia'),
                    'carrusel' => __('Carrusel', 'flavor-chat-ia'),
                    'con_redes' => __('Con Redes Sociales', 'flavor-chat-ia'),
                    'compacto' => __('Compacto', 'flavor-chat-ia'),
                ],
                'fields' => [
                    'titulo' => [
                        'type' => 'text',
                        'label' => __('Título', 'flavor-chat-ia'),
                        'default' => __('Nuestro Equipo', 'flavor-chat-ia'),
                    ],
                    'subtitulo' => [
                        'type' => 'textarea',
                        'label' => __('Subtítulo', 'flavor-chat-ia'),
                        'default' => '',
                    ],
                    'items' => [
                        'type' => 'repeater',
                        'label' => __('Miembros', 'flavor-chat-ia'),
                        'fields' => [
                            'nombre' => ['type' => 'text', 'label' => __('Nombre', 'flavor-chat-ia')],
                            'cargo' => ['type' => 'text', 'label' => __('Cargo', 'flavor-chat-ia')],
                            'imagen' => ['type' => 'image', 'label' => __('Foto', 'flavor-chat-ia')],
                            'bio' => ['type' => 'textarea', 'label' => __('Biografía', 'flavor-chat-ia')],
                            'linkedin' => ['type' => 'url', 'label' => 'LinkedIn'],
                            'twitter' => ['type' => 'url', 'label' => 'Twitter'],
                        ],
                        'default' => [
                            ['nombre' => 'Juan Pérez', 'cargo' => 'CEO', 'bio' => ''],
                            ['nombre' => 'Ana García', 'cargo' => 'CTO', 'bio' => ''],
                        ],
                    ],
                    'color_fondo' => [
                        'type' => 'color',
                        'label' => __('Color de Fondo', 'flavor-chat-ia'),
                        'default' => '#ffffff',
                    ],
                ],
            ],
            'logos' => [
                'label' => __('Logos de Clientes', 'flavor-chat-ia'),
                'description' => __('Carousel de logos de clientes o partners', 'flavor-chat-ia'),
                'icon' => 'dashicons-awards',
                'variants' => [
                    'estatico' => __('Estático', 'flavor-chat-ia'),
                    'carrusel' => __('Carrusel', 'flavor-chat-ia'),
                    'scroll_infinito' => __('Scroll Infinito', 'flavor-chat-ia'),
                ],
                'fields' => [
                    'titulo' => [
                        'type' => 'text',
                        'label' => __('Título', 'flavor-chat-ia'),
                        'default' => __('Confían en nosotros', 'flavor-chat-ia'),
                    ],
                    'logos' => [
                        'type' => 'gallery',
                        'label' => __('Logos', 'flavor-chat-ia'),
                        'default' => [],
                    ],
                    'color_fondo' => [
                        'type' => 'color',
                        'label' => __('Color de Fondo', 'flavor-chat-ia'),
                        'default' => '#f8fafc',
                    ],
                ],
            ],
            'video' => [
                'label' => __('Video', 'flavor-chat-ia'),
                'description' => __('Sección de video destacado', 'flavor-chat-ia'),
                'icon' => 'dashicons-video-alt3',
                'variants' => [
                    'centrado' => __('Centrado', 'flavor-chat-ia'),
                    'fullwidth' => __('Ancho Completo', 'flavor-chat-ia'),
                    'con_texto' => __('Con Texto', 'flavor-chat-ia'),
                ],
                'fields' => [
                    'titulo' => [
                        'type' => 'text',
                        'label' => __('Título', 'flavor-chat-ia'),
                        'default' => '',
                    ],
                    'video_url' => [
                        'type' => 'url',
                        'label' => __('URL del Video (YouTube/Vimeo)', 'flavor-chat-ia'),
                        'default' => '',
                    ],
                    'poster' => [
                        'type' => 'image',
                        'label' => __('Imagen de Portada', 'flavor-chat-ia'),
                        'default' => '',
                    ],
                    'descripcion' => [
                        'type' => 'textarea',
                        'label' => __('Descripción', 'flavor-chat-ia'),
                        'default' => '',
                    ],
                    'color_fondo' => [
                        'type' => 'color',
                        'label' => __('Color de Fondo', 'flavor-chat-ia'),
                        'default' => '#1e293b',
                    ],
                ],
            ],
            'texto' => [
                'label' => __('Texto Enriquecido', 'flavor-chat-ia'),
                'description' => __('Bloque de texto con formato', 'flavor-chat-ia'),
                'icon' => 'dashicons-editor-paragraph',
                'variants' => [
                    'simple' => __('Simple', 'flavor-chat-ia'),
                    'dos_columnas' => __('Dos Columnas', 'flavor-chat-ia'),
                    'con_imagen' => __('Con Imagen', 'flavor-chat-ia'),
                ],
                'fields' => [
                    'titulo' => [
                        'type' => 'text',
                        'label' => __('Título', 'flavor-chat-ia'),
                        'default' => '',
                    ],
                    'contenido' => [
                        'type' => 'wysiwyg',
                        'label' => __('Contenido', 'flavor-chat-ia'),
                        'default' => '',
                    ],
                    'imagen' => [
                        'type' => 'image',
                        'label' => __('Imagen', 'flavor-chat-ia'),
                        'default' => '',
                    ],
                    'imagen_posicion' => [
                        'type' => 'select',
                        'label' => __('Posición Imagen', 'flavor-chat-ia'),
                        'options' => ['left' => __('Izquierda', 'flavor-chat-ia'), 'right' => __('Derecha', 'flavor-chat-ia')],
                        'default' => 'right',
                    ],
                    'color_fondo' => [
                        'type' => 'color',
                        'label' => __('Color de Fondo', 'flavor-chat-ia'),
                        'default' => '#ffffff',
                    ],
                ],
            ],
            'separador' => [
                'label' => __('Separador', 'flavor-chat-ia'),
                'description' => __('Espacio o línea divisoria', 'flavor-chat-ia'),
                'icon' => 'dashicons-minus',
                'variants' => [
                    'espacio' => __('Espacio', 'flavor-chat-ia'),
                    'linea' => __('Línea', 'flavor-chat-ia'),
                    'onda' => __('Onda', 'flavor-chat-ia'),
                    'diagonal' => __('Diagonal', 'flavor-chat-ia'),
                ],
                'fields' => [
                    'altura' => [
                        'type' => 'number',
                        'label' => __('Altura (px)', 'flavor-chat-ia'),
                        'default' => 60,
                    ],
                    'color' => [
                        'type' => 'color',
                        'label' => __('Color', 'flavor-chat-ia'),
                        'default' => '#e2e8f0',
                    ],
                    'color_fondo' => [
                        'type' => 'color',
                        'label' => __('Color de Fondo', 'flavor-chat-ia'),
                        'default' => 'transparent',
                    ],
                ],
            ],
            'landing_module' => [
                'label' => __('Landing de Módulo', 'flavor-chat-ia'),
                'description' => __('Inserta una landing completa basada en templates del módulo', 'flavor-chat-ia'),
                'icon' => 'dashicons-screenoptions',
                'variants' => [
                    'default' => __('Default', 'flavor-chat-ia'),
                ],
                'fields' => [
                    'modulo' => [
                        'type' => 'select',
                        'label' => __('Módulo', 'flavor-chat-ia'),
                        'options' => $this->get_landing_module_options(),
                        'default' => '',
                    ],
                    'variables' => [
                        'type' => 'repeater',
                        'label' => __('Variables', 'flavor-chat-ia'),
                        'fields' => [
                            'key' => [
                                'type' => 'text',
                                'label' => __('Clave', 'flavor-chat-ia'),
                            ],
                            'value' => [
                                'type' => 'text',
                                'label' => __('Valor', 'flavor-chat-ia'),
                            ],
                        ],
                    ],
                    'color_primario' => [
                        'type' => 'color',
                        'label' => __('Color Primario', 'flavor-chat-ia'),
                        'default' => '',
                    ],
                ],
            ],
            'landing_template' => [
                'label' => __('Sección de Template', 'flavor-chat-ia'),
                'description' => __('Inserta una sección desde templates de landings', 'flavor-chat-ia'),
                'icon' => 'dashicons-layout',
                'variants' => [
                    'default' => __('Default', 'flavor-chat-ia'),
                ],
                'fields' => [
                    'template' => [
                        'type' => 'select',
                        'label' => __('Template', 'flavor-chat-ia'),
                        'options' => $this->get_landing_template_options(),
                        'default' => '',
                    ],
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
                    'variables' => [
                        'type' => 'repeater',
                        'label' => __('Variables', 'flavor-chat-ia'),
                        'fields' => [
                            'key' => [
                                'type' => 'text',
                                'label' => __('Clave', 'flavor-chat-ia'),
                            ],
                            'value' => [
                                'type' => 'text',
                                'label' => __('Valor', 'flavor-chat-ia'),
                            ],
                        ],
                    ],
                    'color_primario' => [
                        'type' => 'color',
                        'label' => __('Color Primario', 'flavor-chat-ia'),
                        'default' => '',
                    ],
                ],
            ],
            'shortcode_block' => [
                'label' => __('Shortcode', 'flavor-chat-ia'),
                'description' => __('Renderiza un shortcode o conjunto de shortcodes', 'flavor-chat-ia'),
                'icon' => 'dashicons-editor-code',
                'variants' => [
                    'default' => __('Default', 'flavor-chat-ia'),
                ],
                'fields' => [
                    'shortcode' => [
                        'type' => 'textarea',
                        'label' => __('Shortcode', 'flavor-chat-ia'),
                        'default' => '',
                    ],
                ],
            ],
        ];

        // Permitir extensión de secciones
        $this->available_sections = apply_filters('flavor_landing_editor_sections', $this->available_sections);
    }

    /**
     * Obtiene mapeo de plantillas de landing desde el sistema de shortcodes
     *
     * @return array
     */
    private function get_landing_template_map() {
        if (class_exists('Flavor_Landing_Shortcodes')) {
            return Flavor_Landing_Shortcodes::get_instance()->get_template_map_public();
        }
        return [];
    }

    /**
     * Opciones de módulos para landings
     *
     * @return array
     */
    private function get_landing_module_options() {
        $map = $this->get_landing_template_map();
        $options = [
            '' => __('Selecciona módulo', 'flavor-chat-ia'),
        ];
        foreach ($map as $module_id => $config) {
            $label = ucwords(str_replace(['-', '_'], ' ', $module_id));
            $options[$module_id] = $label;
        }
        return $options;
    }

    /**
     * Opciones de templates de secciones
     *
     * @return array
     */
    private function get_landing_template_options() {
        $map = $this->get_landing_template_map();
        $options = [
            '' => __('Selecciona template', 'flavor-chat-ia'),
        ];
        foreach ($map as $module_id => $config) {
            $module_label = ucwords(str_replace(['-', '_'], ' ', $module_id));
            $sections = $config['sections'] ?? [];
            foreach ($sections as $section) {
                $template = $section['template'] ?? '';
                if (!$template) {
                    continue;
                }
                if (!isset($options[$template])) {
                    $template_label = ucwords(str_replace(['-', '_'], ' ', ltrim($template, '_')));
                    $options[$template] = $template_label . ' (' . $module_label . ')';
                }
            }
        }
        return $options;
    }

    /**
     * Añade página del editor al menú
     */
    public function add_editor_page() {
        add_submenu_page(
            null, // Sin padre, acceso directo por URL
            __('Editor de Landing', 'flavor-chat-ia'),
            __('Editor de Landing', 'flavor-chat-ia'),
            'edit_pages',
            'flavor-landing-editor',
            [$this, 'render_editor_page']
        );
    }

    /**
     * Renderiza la página del editor
     */
    public function render_editor_page() {
        $post_id = isset($_GET['post_id']) ? intval($_GET['post_id']) : 0;

        // Si no hay post_id, mostrar listado de landings disponibles
        if (!$post_id) {
            $this->render_landing_selector();
            return;
        }

        $post = get_post($post_id);
        if (!$post || !current_user_can('edit_post', $post_id)) {
            wp_die(__('No tienes permisos para editar esta página', 'flavor-chat-ia'));
        }

        include FLAVOR_CHAT_IA_PATH . 'admin/views/landing-editor.php';
    }

    /**
     * Muestra selector de landings cuando no se proporciona post_id
     */
    private function render_landing_selector() {
        // Buscar páginas que usen el template de landing o tengan secciones
        $landings = get_posts([
            'post_type' => 'page',
            'posts_per_page' => -1,
            'post_status' => ['publish', 'draft', 'pending'],
            'meta_query' => [
                'relation' => 'OR',
                [
                    'key' => '_flavor_landing_sections',
                    'compare' => 'EXISTS',
                ],
                [
                    'key' => '_wp_page_template',
                    'value' => ['template-landing.php', 'templates/landing.php'],
                    'compare' => 'IN',
                ],
            ],
            'orderby' => 'modified',
            'order' => 'DESC',
        ]);

        ?>
        <div class="wrap">
            <h1 class="wp-heading-inline">
                <span class="dashicons dashicons-welcome-widgets-menus" style="font-size: 30px; margin-right: 10px;"></span>
                <?php esc_html_e('Editor de Landing Pages', 'flavor-chat-ia'); ?>
            </h1>
            <a href="<?php echo esc_url(admin_url('post-new.php?post_type=page')); ?>" class="page-title-action">
                <?php esc_html_e('Crear nueva página', 'flavor-chat-ia'); ?>
            </a>
            <hr class="wp-header-end">

            <?php if (empty($landings)): ?>
                <div class="notice notice-info" style="margin-top: 20px;">
                    <p>
                        <strong><?php esc_html_e('No hay landing pages configuradas todavía.', 'flavor-chat-ia'); ?></strong>
                    </p>
                    <p>
                        <?php esc_html_e('Para crear una landing page:', 'flavor-chat-ia'); ?>
                    </p>
                    <ol>
                        <li><?php esc_html_e('Crea una nueva página en WordPress', 'flavor-chat-ia'); ?></li>
                        <li><?php esc_html_e('En la barra lateral, haz clic en "Editar con Landing Editor"', 'flavor-chat-ia'); ?></li>
                        <li><?php esc_html_e('Añade secciones y personaliza el contenido', 'flavor-chat-ia'); ?></li>
                    </ol>
                    <p>
                        <a href="<?php echo esc_url(admin_url('post-new.php?post_type=page')); ?>" class="button button-primary">
                            <?php esc_html_e('Crear nueva página', 'flavor-chat-ia'); ?>
                        </a>
                    </p>
                </div>
            <?php else: ?>
                <p style="margin: 20px 0;">
                    <?php esc_html_e('Selecciona una landing page para editarla:', 'flavor-chat-ia'); ?>
                </p>

                <div class="flavor-landing-grid" style="display: grid; grid-template-columns: repeat(auto-fill, minmax(300px, 1fr)); gap: 20px; margin-top: 20px;">
                    <?php foreach ($landings as $landing):
                        $sections = get_post_meta($landing->ID, '_flavor_landing_sections', true);
                        $section_count = is_array($sections) ? count($sections) : 0;
                        $thumbnail = get_the_post_thumbnail_url($landing->ID, 'medium');
                    ?>
                        <div class="flavor-landing-card" style="background: #fff; border: 1px solid #c3c4c7; border-radius: 4px; overflow: hidden; transition: box-shadow 0.2s;">
                            <?php if ($thumbnail): ?>
                                <div style="height: 150px; background: url('<?php echo esc_url($thumbnail); ?>') center/cover no-repeat;"></div>
                            <?php else: ?>
                                <div style="height: 150px; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); display: flex; align-items: center; justify-content: center;">
                                    <span class="dashicons dashicons-welcome-widgets-menus" style="font-size: 50px; color: rgba(255,255,255,0.5);"></span>
                                </div>
                            <?php endif; ?>
                            <div style="padding: 15px;">
                                <h3 style="margin: 0 0 10px 0; font-size: 16px;">
                                    <?php echo esc_html($landing->post_title ?: __('(Sin título)', 'flavor-chat-ia')); ?>
                                </h3>
                                <p style="margin: 0 0 10px 0; color: #646970; font-size: 13px;">
                                    <?php
                                    printf(
                                        esc_html__('%d secciones • %s', 'flavor-chat-ia'),
                                        $section_count,
                                        get_post_status_object($landing->post_status)->label
                                    );
                                    ?>
                                </p>
                                <div style="display: flex; gap: 10px;">
                                    <a href="<?php echo esc_url(admin_url('admin.php?page=flavor-landing-editor&post_id=' . $landing->ID)); ?>"
                                       class="button button-primary" style="flex: 1; text-align: center;">
                                        <?php esc_html_e('Editar', 'flavor-chat-ia'); ?>
                                    </a>
                                    <a href="<?php echo esc_url(get_permalink($landing->ID)); ?>"
                                       class="button" target="_blank" title="<?php esc_attr_e('Ver página', 'flavor-chat-ia'); ?>">
                                        <span class="dashicons dashicons-external" style="line-height: 28px;"></span>
                                    </a>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
        <style>
            .flavor-landing-card:hover {
                box-shadow: 0 2px 8px rgba(0,0,0,0.15);
            }
        </style>
        <?php
    }

    /**
     * Encola assets del editor
     *
     * @param string $hook
     */
    public function enqueue_assets($hook) {
        // Solo cargar en páginas relevantes
        $is_editor_page = isset($_GET['page']) && $_GET['page'] === 'flavor-landing-editor';
        $is_post_edit = in_array($hook, ['post.php', 'post-new.php']);

        if (!$is_editor_page && !$is_post_edit) {
            return;
        }

        // Estilos - usar filemtime para cache busting en desarrollo
        $css_version = FLAVOR_CHAT_IA_VERSION . '.' . filemtime(FLAVOR_CHAT_IA_PATH . 'admin/css/landing-editor.css');
        wp_enqueue_style(
            'flavor-landing-editor',
            FLAVOR_CHAT_IA_URL . 'admin/css/landing-editor.css',
            ['wp-color-picker'],
            $css_version
        );

        // Scripts
        wp_enqueue_media();
        wp_enqueue_script('wp-color-picker');

        $js_version = FLAVOR_CHAT_IA_VERSION . '.' . filemtime(FLAVOR_CHAT_IA_PATH . 'admin/js/landing-editor.js');
        wp_enqueue_script(
            'flavor-landing-editor',
            FLAVOR_CHAT_IA_URL . 'admin/js/landing-editor.js',
            ['jquery', 'wp-color-picker'],
            $js_version,
            true
        );

        wp_localize_script('flavor-landing-editor', 'flavorLandingEditor', [
            'ajaxUrl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('flavor_landing_editor'),
            'sections' => $this->available_sections,
            'previewUrl' => admin_url('admin-ajax.php?action=flavor_landing_preview'),
            'postId' => isset($_GET['post']) ? intval($_GET['post']) : (isset($_GET['post_id']) ? intval($_GET['post_id']) : 0),
            'i18n' => [
                'confirmDelete' => __('¿Eliminar esta sección?', 'flavor-chat-ia'),
                'saving' => __('Guardando...', 'flavor-chat-ia'),
                'saved' => __('Guardado', 'flavor-chat-ia'),
                'error' => __('Error al guardar', 'flavor-chat-ia'),
                'selectImage' => __('Seleccionar imagen', 'flavor-chat-ia'),
                'selectImages' => __('Seleccionar imágenes', 'flavor-chat-ia'),
                'useImage' => __('Usar imagen', 'flavor-chat-ia'),
                'addItem' => __('Añadir elemento', 'flavor-chat-ia'),
                'removeItem' => __('Eliminar', 'flavor-chat-ia'),
                'duplicate' => __('Duplicar', 'flavor-chat-ia'),
                'moveUp' => __('Mover arriba', 'flavor-chat-ia'),
                'moveDown' => __('Mover abajo', 'flavor-chat-ia'),
                'settings' => __('Configuración', 'flavor-chat-ia'),
                'undone' => __('Cambio deshecho', 'flavor-chat-ia'),
                'redone' => __('Cambio rehecho', 'flavor-chat-ia'),
                'noHistory' => __('No hay más cambios para deshacer', 'flavor-chat-ia'),
                'noRedo' => __('No hay más cambios para rehacer', 'flavor-chat-ia'),
                // Plantillas
                'templates' => __('Plantillas', 'flavor-chat-ia'),
                'allTemplates' => __('Todas', 'flavor-chat-ia'),
                'business' => __('Negocio', 'flavor-chat-ia'),
                'portfolio' => __('Portfolio', 'flavor-chat-ia'),
                'app' => __('App', 'flavor-chat-ia'),
                'services' => __('Servicios', 'flavor-chat-ia'),
                'blankTemplate' => __('Página en Blanco', 'flavor-chat-ia'),
                'startFromScratch' => __('Empieza desde cero', 'flavor-chat-ia'),
                'templateLoaded' => __('Plantilla cargada', 'flavor-chat-ia'),
                'confirmClearContent' => __('¿Estás seguro de que quieres empezar con una página en blanco? Se eliminará todo el contenido actual.', 'flavor-chat-ia'),
                'confirmReplaceContent' => __('¿Reemplazar el contenido actual con esta plantilla?', 'flavor-chat-ia'),
                'unsavedChanges' => __('Tienes cambios sin guardar. ¿Seguro que quieres salir?', 'flavor-chat-ia'),
            ],
            'icons' => $this->get_available_icons(),
        ]);
    }

    /**
     * Obtiene iconos disponibles (Dashicons)
     *
     * @return array
     */
    private function get_available_icons() {
        return [
            'admin-appearance', 'admin-collapse', 'admin-comments', 'admin-generic',
            'admin-home', 'admin-links', 'admin-media', 'admin-network', 'admin-page',
            'admin-plugins', 'admin-post', 'admin-settings', 'admin-site', 'admin-tools',
            'admin-users', 'album', 'analytics', 'archive', 'arrow-down', 'arrow-left',
            'arrow-right', 'arrow-up', 'awards', 'backup', 'book', 'building', 'businessman',
            'calendar', 'calendar-alt', 'camera', 'carrot', 'cart', 'category', 'chart-area',
            'chart-bar', 'chart-line', 'chart-pie', 'clipboard', 'clock', 'cloud', 'code-standards',
            'coffee', 'cover-image', 'dashboard', 'desktop', 'dismiss', 'download', 'edit',
            'editor-help', 'email', 'email-alt', 'facebook', 'feedback', 'filter', 'flag',
            'format-audio', 'format-gallery', 'format-image', 'format-quote', 'format-video',
            'groups', 'heart', 'hidden', 'id', 'id-alt', 'image-filter', 'info', 'instagram',
            'laptop', 'layout', 'lightbulb', 'list-view', 'location', 'location-alt', 'lock',
            'media-archive', 'megaphone', 'menu', 'microphone', 'migrate', 'minus', 'money',
            'money-alt', 'nametag', 'no', 'no-alt', 'palmtree', 'paperclip', 'performance',
            'phone', 'pinterest', 'playlist-audio', 'playlist-video', 'plus', 'plus-alt',
            'portfolio', 'pressthis', 'products', 'randomize', 'redo', 'rest-api', 'rss',
            'schedule', 'screenoptions', 'search', 'share', 'shield', 'slides', 'smartphone',
            'smiley', 'sort', 'sos', 'star-empty', 'star-filled', 'star-half', 'sticky',
            'store', 'superhero', 'table-col-after', 'table-col-before', 'table-row-after',
            'table-row-before', 'tablet', 'tag', 'tagcloud', 'testimonial', 'text', 'thumbs-down',
            'thumbs-up', 'tickets', 'translation', 'trash', 'twitter', 'undo', 'universal-access',
            'update', 'upload', 'vault', 'video-alt', 'video-alt2', 'video-alt3', 'visibility',
            'warning', 'welcome-add-page', 'welcome-learn-more', 'welcome-widgets-menus',
            'wordpress', 'yes', 'yes-alt',
        ];
    }

    /**
     * Añade metabox al editor de páginas
     */
    public function add_meta_box() {
        add_meta_box(
            'flavor_landing_editor_metabox',
            __('Editor Visual de Landing', 'flavor-chat-ia'),
            [$this, 'render_meta_box'],
            'page',
            'side',
            'high'
        );
    }

    /**
     * Renderiza el metabox
     *
     * @param WP_Post $post
     */
    public function render_meta_box($post) {
        $has_shortcode = has_shortcode($post->post_content, 'flavor_landing') ||
                         has_shortcode($post->post_content, 'flavor_landing_visual');
        $structure = $this->get_landing_structure($post->ID);

        ?>
        <div class="flavor-landing-metabox">
            <?php if ($has_shortcode || !empty($structure)): ?>
                <a href="<?php echo esc_url(admin_url('admin.php?page=flavor-landing-editor&post_id=' . $post->ID)); ?>"
                   class="button button-primary button-large"
                   style="width: 100%; text-align: center; margin-bottom: 10px;">
                    <span class="dashicons dashicons-edit" style="margin-top: 4px;"></span>
                    <?php _e('Editar con Editor Visual', 'flavor-chat-ia'); ?>
                </a>
                <?php if (!empty($structure)): ?>
                    <p class="description">
                        <?php printf(__('%d secciones configuradas', 'flavor-chat-ia'), count($structure['sections'] ?? [])); ?>
                    </p>
                <?php endif; ?>
            <?php else: ?>
                <p class="description"><?php _e('Esta página no tiene una landing configurada.', 'flavor-chat-ia'); ?></p>
                <a href="<?php echo esc_url(admin_url('admin.php?page=flavor-landing-editor&post_id=' . $post->ID)); ?>"
                   class="button"
                   style="width: 100%; text-align: center;">
                    <span class="dashicons dashicons-plus" style="margin-top: 4px;"></span>
                    <?php _e('Crear Landing Visual', 'flavor-chat-ia'); ?>
                </a>
            <?php endif; ?>
        </div>
        <?php
    }

    /**
     * Obtiene secciones disponibles para un perfil
     *
     * @param string $profile Perfil de app
     * @return array
     */
    public function get_available_sections($profile = '') {
        $sections = $this->available_sections;

        // Filtrar secciones según perfil si es necesario
        if (!empty($profile)) {
            $sections = apply_filters('flavor_landing_sections_for_profile', $sections, $profile);
        }

        $category_map = [
            'hero' => ['base', __('Base', 'flavor-chat-ia')],
            'features' => ['contenido', __('Contenido', 'flavor-chat-ia')],
            'testimonios' => ['social', __('Social', 'flavor-chat-ia')],
            'pricing' => ['conversion', __('Conversion', 'flavor-chat-ia')],
            'cta' => ['conversion', __('Conversion', 'flavor-chat-ia')],
            'faq' => ['contenido', __('Contenido', 'flavor-chat-ia')],
            'contacto' => ['conversion', __('Conversion', 'flavor-chat-ia')],
            'galeria' => ['multimedia', __('Multimedia', 'flavor-chat-ia')],
            'stats' => ['contenido', __('Contenido', 'flavor-chat-ia')],
            'equipo' => ['social', __('Social', 'flavor-chat-ia')],
            'logos' => ['empresa', __('Empresa', 'flavor-chat-ia')],
            'video' => ['multimedia', __('Multimedia', 'flavor-chat-ia')],
            'texto' => ['contenido', __('Contenido', 'flavor-chat-ia')],
            'separador' => ['base', __('Base', 'flavor-chat-ia')],
            'landing_module' => ['modulos', __('Modulos', 'flavor-chat-ia')],
            'landing_template' => ['modulos', __('Modulos', 'flavor-chat-ia')],
        ];
        $default_category = ['general', __('General', 'flavor-chat-ia')];

        foreach ($sections as $key => &$section) {
            if (empty($section['category'])) {
                $section['category'] = $category_map[$key][0] ?? $default_category[0];
            }
            if (empty($section['category_label'])) {
                $section['category_label'] = $category_map[$key][1] ?? $default_category[1];
            }
        }
        unset($section);

        return $sections;
    }

    /**
     * Guarda estructura de landing
     *
     * @param int $post_id
     * @param array $sections
     * @return bool
     */
    public function save_landing_structure($post_id, $sections) {
        if (!current_user_can('edit_post', $post_id)) {
            return false;
        }

        $structure = [
            'version' => '1.0',
            'updated_at' => current_time('mysql'),
            'updated_by' => get_current_user_id(),
            'sections' => $sections,
            'settings' => [
                'color_primario' => isset($_POST['color_primario']) ? sanitize_hex_color($_POST['color_primario']) : '#3b82f6',
            ],
        ];

        // Guardar historial antes de actualizar
        $this->save_to_history($post_id);

        // Guardar estructura
        update_post_meta($post_id, self::META_KEY, $structure);

        // Actualizar contenido del post con shortcode si no existe
        $post = get_post($post_id);
        if ($post && !has_shortcode($post->post_content, 'flavor_landing_visual')) {
            $new_content = $post->post_content;
            if (!has_shortcode($new_content, 'flavor_landing')) {
                $new_content = '[flavor_landing_visual]' . "\n" . $new_content;
                wp_update_post([
                    'ID' => $post_id,
                    'post_content' => $new_content,
                ]);
            }
        }

        return true;
    }

    /**
     * Guarda estado actual en historial
     *
     * @param int $post_id
     */
    private function save_to_history($post_id) {
        $current = get_post_meta($post_id, self::META_KEY, true);
        if (empty($current)) {
            return;
        }

        $history = get_post_meta($post_id, self::HISTORY_META_KEY, true);
        if (!is_array($history)) {
            $history = [];
        }

        // Añadir al historial
        array_unshift($history, $current);

        // Mantener solo últimos 20 estados
        $history = array_slice($history, 0, 20);

        update_post_meta($post_id, self::HISTORY_META_KEY, $history);
    }

    /**
     * Obtiene estructura guardada
     *
     * @param int $post_id
     * @return array|null
     */
    public function get_landing_structure($post_id) {
        $structure = get_post_meta($post_id, self::META_KEY, true);

        if (empty($structure)) {
            return null;
        }

        return $structure;
    }

    /**
     * Renderiza landing desde estructura
     *
     * @param array $structure
     * @return string
     */
    public function render_from_structure($structure) {
        if (empty($structure) || empty($structure['sections'])) {
            return '';
        }

        $output = '<div class="flavor-landing flavor-landing-visual">';

        // Estilos personalizados
        $primary_color = $structure['settings']['color_primario'] ?? '#3b82f6';
        $output .= $this->get_landing_styles($primary_color);

        // Renderizar cada sección
        foreach ($structure['sections'] as $section_data) {
            $output .= $this->render_section($section_data);
        }

        $output .= '</div>';

        return $output;
    }

    /**
     * Renderiza una sección individual
     *
     * @param array $section_data
     * @return string
     */
    private function render_section($section_data) {
        $section_type = $section_data['type'] ?? '';
        $variant = $section_data['variant'] ?? 'default';
        $data = $section_data['data'] ?? [];
        $design = $section_data['design'] ?? [];
        $advanced = $section_data['advanced'] ?? [];
        $section_id = $section_data['id'] ?? uniqid('section-');

        if (!isset($this->available_sections[$section_type])) {
            return '';
        }

        // Generar atributos de sección
        $wrapper_attrs = $this->build_section_wrapper_attrs($section_id, $section_type, $variant, $design, $advanced);
        $wrapper_styles = $this->build_section_wrapper_styles($design);

        // Buscar template específico
        $template_path = FLAVOR_CHAT_IA_PATH . 'templates/landing-sections/' . $section_type . '.php';

        if (file_exists($template_path)) {
            ob_start();
            extract([
                'data' => $data,
                'variant' => $variant,
                'section_id' => $section_id,
                'design' => $design,
                'advanced' => $advanced,
                'wrapper_attrs' => $wrapper_attrs,
                'wrapper_styles' => $wrapper_styles,
            ]);
            include $template_path;
            return ob_get_clean();
        }

        // Renderizado por defecto según tipo
        return $this->render_default_section($section_type, $variant, $data, $section_id, $design, $advanced);
    }

    /**
     * Construye atributos del wrapper de sección
     *
     * @param string $section_id
     * @param string $section_type
     * @param string $variant
     * @param array $design
     * @param array $advanced
     * @return string
     */
    private function build_section_wrapper_attrs($section_id, $section_type, $variant, $design, $advanced) {
        $classes = [
            'flavor-section',
            'flavor-' . $section_type,
            'flavor-' . $section_type . '--' . $variant,
        ];

        // Clases CSS personalizadas
        if (!empty($advanced['css_class'])) {
            $custom_classes = explode(' ', sanitize_text_field($advanced['css_class']));
            $classes = array_merge($classes, $custom_classes);
        }

        // Animación
        if (!empty($advanced['animation']) && $advanced['animation'] !== 'none') {
            $classes[] = 'flavor-animate';
            $classes[] = 'flavor-animate--' . sanitize_html_class($advanced['animation']);
        }

        // Visibilidad por dispositivo
        if (isset($advanced['visibility_desktop']) && !$advanced['visibility_desktop']) {
            $classes[] = 'flavor-hide-desktop';
        }
        if (isset($advanced['visibility_tablet']) && !$advanced['visibility_tablet']) {
            $classes[] = 'flavor-hide-tablet';
        }
        if (isset($advanced['visibility_mobile']) && !$advanced['visibility_mobile']) {
            $classes[] = 'flavor-hide-mobile';
        }

        // Full width (forza ancho completo de la seccion)
        if (!empty($advanced['fullwidth'])) {
            $classes[] = 'is-fullwidth';
        }

        $attrs = 'class="' . esc_attr(implode(' ', $classes)) . '"';

        // ID personalizado
        if (!empty($advanced['css_id'])) {
            $attrs .= ' id="' . esc_attr(sanitize_html_class($advanced['css_id'])) . '"';
        } else {
            $attrs .= ' id="' . esc_attr($section_id) . '"';
        }

        // Data attribute para animaciones
        if (!empty($advanced['animation']) && $advanced['animation'] !== 'none') {
            $attrs .= ' data-animation="' . esc_attr($advanced['animation']) . '"';
        }

        if (!empty($advanced['fullwidth'])) {
            $attrs .= ' data-fullwidth="1"';
        }

        return $attrs;
    }

    /**
     * Construye estilos inline del wrapper de sección
     *
     * @param array $design
     * @return string
     */
    private function build_section_wrapper_styles($design) {
        $styles = [];

        // Padding
        if (!empty($design['padding_top'])) {
            $styles[] = 'padding-top: ' . intval($design['padding_top']) . 'px';
        }
        if (!empty($design['padding_bottom'])) {
            $styles[] = 'padding-bottom: ' . intval($design['padding_bottom']) . 'px';
        }
        if (!empty($design['padding_left'])) {
            $styles[] = 'padding-left: ' . intval($design['padding_left']) . 'px';
        }
        if (!empty($design['padding_right'])) {
            $styles[] = 'padding-right: ' . intval($design['padding_right']) . 'px';
        }

        // Margen
        if (!empty($design['margin_top'])) {
            $styles[] = 'margin-top: ' . intval($design['margin_top']) . 'px';
        }
        if (!empty($design['margin_bottom'])) {
            $styles[] = 'margin-bottom: ' . intval($design['margin_bottom']) . 'px';
        }

        // Border radius
        if (!empty($design['border_radius'])) {
            $styles[] = 'border-radius: ' . intval($design['border_radius']) . 'px';
        }

        // Ancho máximo
        if (!empty($design['max_width']) && $design['max_width'] !== 'none') {
            $styles[] = '--section-max-width: ' . esc_attr($design['max_width']);
        }

        // Alineación de texto
        if (!empty($design['text_align'])) {
            $styles[] = 'text-align: ' . sanitize_html_class($design['text_align']);
        }

        if (empty($styles)) {
            return '';
        }

        return 'style="' . esc_attr(implode('; ', $styles)) . '"';
    }

    /**
     * Renderizado por defecto de secciones
     *
     * @param string $type
     * @param string $variant
     * @param array $data
     * @param string $section_id
     * @param array $design
     * @param array $advanced
     * @return string
     */
    private function render_default_section($type, $variant, $data, $section_id, $design = [], $advanced = []) {
        $bg_color = $data['color_fondo'] ?? '#ffffff';
        $text_color = $data['color_texto'] ?? '#1e293b';

        // Construir atributos y estilos del wrapper
        $wrapper_attrs = $this->build_section_wrapper_attrs($section_id, $type, $variant, $design, $advanced);
        $wrapper_styles = $this->build_section_wrapper_styles($design);

        // Combinar estilos base con estilos personalizados
        $base_styles = sprintf('background-color: %s; color: %s;', esc_attr($bg_color), esc_attr($text_color));
        if (!empty($wrapper_styles)) {
            // Extraer solo el contenido del style
            $custom_styles = str_replace(['style="', '"'], '', $wrapper_styles);
            $base_styles .= ' ' . $custom_styles;
        }

        $output = sprintf(
            '<section %s style="%s">',
            $wrapper_attrs,
            esc_attr($base_styles)
        );
        $output .= '<div class="flavor-container">';

        switch ($type) {
            case 'landing_module':
                $output .= $this->render_landing_module_section($data);
                break;
            case 'landing_template':
                $output .= $this->render_landing_template_section($data);
                break;
            case 'hero':
                $output .= $this->render_hero_section($variant, $data);
                break;
            case 'features':
                $output .= $this->render_features_section($variant, $data);
                break;
            case 'testimonios':
                $output .= $this->render_testimonios_section($variant, $data);
                break;
            case 'pricing':
                $output .= $this->render_pricing_section($variant, $data);
                break;
            case 'cta':
                $output .= $this->render_cta_section($variant, $data);
                break;
            case 'faq':
                $output .= $this->render_faq_section($variant, $data);
                break;
            case 'contacto':
                $output .= $this->render_contacto_section($variant, $data);
                break;
            case 'galeria':
                $output .= $this->render_galeria_section($variant, $data);
                break;
            case 'stats':
                $output .= $this->render_stats_section($variant, $data);
                break;
            case 'equipo':
                $output .= $this->render_equipo_section($variant, $data);
                break;
            case 'logos':
                $output .= $this->render_logos_section($variant, $data);
                break;
            case 'video':
                $output .= $this->render_video_section($variant, $data);
                break;
            case 'texto':
                $output .= $this->render_texto_section($variant, $data);
                break;
            case 'separador':
                $output .= $this->render_separador_section($variant, $data);
                break;
            default:
                $output .= $this->render_generic_section($data);
        }

        $output .= '</div></section>';

        return $output;
    }

    /**
     * Renderiza una landing completa de módulo
     *
     * @param array $data
     * @return string
     */
    private function render_landing_module_section($data) {
        $module = sanitize_text_field($data['modulo'] ?? '');
        if (empty($module)) {
            return '<div class="flavor-landing-error">' . esc_html__('Selecciona un módulo', 'flavor-chat-ia') . '</div>';
        }

        $color = sanitize_hex_color($data['color_primario'] ?? '');
        $shortcode = '[flavor_landing module="' . esc_attr($module) . '"';
        if (!empty($color)) {
            $shortcode .= ' color="' . esc_attr($color) . '"';
        }

        $variables = isset($data['variables']) && is_array($data['variables']) ? $data['variables'] : [];
        $reserved = ['module', 'modulo', 'color'];
        foreach ($variables as $row) {
            $key = sanitize_key($row['key'] ?? '');
            $value = sanitize_text_field($row['value'] ?? '');
            if (empty($key) || in_array($key, $reserved, true)) {
                continue;
            }
            $shortcode .= ' ' . esc_attr($key) . '="' . esc_attr($value) . '"';
        }

        $shortcode .= ']';

        return do_shortcode($shortcode);
    }

    /**
     * Renderiza una sección desde template de landings
     *
     * @param array $data
     * @return string
     */
    private function render_landing_template_section($data) {
        $template = sanitize_text_field($data['template'] ?? '');
        if (empty($template)) {
            return '<div class="flavor-landing-error">' . esc_html__('Selecciona un template', 'flavor-chat-ia') . '</div>';
        }

        $title = sanitize_text_field($data['titulo'] ?? '');
        $subtitle = sanitize_textarea_field($data['subtitulo'] ?? '');
        $color = sanitize_hex_color($data['color_primario'] ?? '');

        $shortcode = '[flavor_section template="' . esc_attr($template) . '"';
        if (!empty($title)) {
            $shortcode .= ' title="' . esc_attr($title) . '"';
        }
        if (!empty($subtitle)) {
            $shortcode .= ' subtitle="' . esc_attr($subtitle) . '"';
        }
        if (!empty($color)) {
            $shortcode .= ' color="' . esc_attr($color) . '"';
        }

        $variables = isset($data['variables']) && is_array($data['variables']) ? $data['variables'] : [];
        $reserved = ['template', 'title', 'subtitle', 'color'];
        foreach ($variables as $row) {
            $key = sanitize_key($row['key'] ?? '');
            $value = sanitize_text_field($row['value'] ?? '');
            if (empty($key) || in_array($key, $reserved, true)) {
                continue;
            }
            $shortcode .= ' ' . esc_attr($key) . '="' . esc_attr($value) . '"';
        }

        $shortcode .= ']';

        return do_shortcode($shortcode);
    }

    /**
     * Renderiza sección Hero
     */
    private function render_hero_section($variant, $data) {
        $titulo = $data['titulo'] ?? '';
        $subtitulo = $data['subtitulo'] ?? '';
        $imagen = $data['imagen'] ?? '';
        $cta_texto = $data['cta_texto'] ?? '';
        $cta_url = $data['cta_url'] ?? '#';
        $cta_sec_texto = $data['cta_secundario_texto'] ?? '';
        $cta_sec_url = $data['cta_secundario_url'] ?? '';

        $class_variant = 'flavor-hero--' . $variant;

        $output = '<div class="flavor-hero ' . esc_attr($class_variant) . '">';

        if ($variant === 'split' && $imagen) {
            $output .= '<div class="flavor-hero__content">';
        }

        $output .= '<div class="flavor-hero__text">';
        if ($titulo) {
            $output .= '<h1 class="flavor-hero__title">' . esc_html($titulo) . '</h1>';
        }
        if ($subtitulo) {
            $output .= '<p class="flavor-hero__subtitle">' . esc_html($subtitulo) . '</p>';
        }
        if ($cta_texto) {
            $output .= '<div class="flavor-hero__buttons">';
            $output .= '<a href="' . esc_url($cta_url) . '" class="flavor-btn flavor-btn--primary">' . esc_html($cta_texto) . '</a>';
            if ($cta_sec_texto) {
                $output .= '<a href="' . esc_url($cta_sec_url) . '" class="flavor-btn flavor-btn--secondary">' . esc_html($cta_sec_texto) . '</a>';
            }
            $output .= '</div>';
        }
        $output .= '</div>';

        if ($imagen) {
            $output .= '<div class="flavor-hero__image">';
            $output .= '<img src="' . esc_url($imagen) . '" alt="' . esc_attr($titulo) . '">';
            $output .= '</div>';
        }

        if ($variant === 'split' && $imagen) {
            $output .= '</div>';
        }

        $output .= '</div>';

        return $output;
    }

    /**
     * Renderiza sección Features
     */
    private function render_features_section($variant, $data) {
        $titulo = $data['titulo'] ?? '';
        $subtitulo = $data['subtitulo'] ?? '';
        $items = $data['items'] ?? [];

        $columns = $variant === 'grid_4' ? 4 : ($variant === 'lista' ? 1 : 3);

        $output = '<div class="flavor-features">';

        if ($titulo) {
            $output .= '<div class="flavor-section__header">';
            $output .= '<h2 class="flavor-section__title">' . esc_html($titulo) . '</h2>';
            if ($subtitulo) {
                $output .= '<p class="flavor-section__subtitle">' . esc_html($subtitulo) . '</p>';
            }
            $output .= '</div>';
        }

        $output .= '<div class="flavor-features__grid flavor-grid--' . intval($columns) . '">';
        foreach ($items as $item) {
            $output .= '<div class="flavor-feature-card">';
            if (!empty($item['icono'])) {
                $output .= '<div class="flavor-feature-card__icon"><span class="dashicons dashicons-' . esc_attr($item['icono']) . '"></span></div>';
            }
            if (!empty($item['titulo'])) {
                $output .= '<h3 class="flavor-feature-card__title">' . esc_html($item['titulo']) . '</h3>';
            }
            if (!empty($item['descripcion'])) {
                $output .= '<p class="flavor-feature-card__description">' . esc_html($item['descripcion']) . '</p>';
            }
            $output .= '</div>';
        }
        $output .= '</div>';

        $output .= '</div>';

        return $output;
    }

    /**
     * Renderiza sección Testimonios
     */
    private function render_testimonios_section($variant, $data) {
        $titulo = $data['titulo'] ?? '';
        $items = $data['items'] ?? [];

        $output = '<div class="flavor-testimonios flavor-testimonios--' . esc_attr($variant) . '">';

        if ($titulo) {
            $output .= '<h2 class="flavor-section__title">' . esc_html($titulo) . '</h2>';
        }

        $output .= '<div class="flavor-testimonios__grid">';
        foreach ($items as $item) {
            $output .= '<div class="flavor-testimonio-card">';
            if (!empty($item['valoracion'])) {
                $output .= '<div class="flavor-testimonio-card__stars">';
                for ($i = 0; $i < intval($item['valoracion']); $i++) {
                    $output .= '<span class="dashicons dashicons-star-filled"></span>';
                }
                $output .= '</div>';
            }
            if (!empty($item['texto'])) {
                $output .= '<blockquote class="flavor-testimonio-card__quote">"' . esc_html($item['texto']) . '"</blockquote>';
            }
            $output .= '<div class="flavor-testimonio-card__author">';
            if (!empty($item['imagen'])) {
                $output .= '<img src="' . esc_url($item['imagen']) . '" alt="' . esc_attr($item['nombre'] ?? '') . '" class="flavor-testimonio-card__avatar">';
            }
            $output .= '<div class="flavor-testimonio-card__info">';
            if (!empty($item['nombre'])) {
                $output .= '<strong>' . esc_html($item['nombre']) . '</strong>';
            }
            if (!empty($item['cargo'])) {
                $output .= '<span>' . esc_html($item['cargo']) . '</span>';
            }
            $output .= '</div></div>';
            $output .= '</div>';
        }
        $output .= '</div>';

        $output .= '</div>';

        return $output;
    }

    /**
     * Renderiza sección Pricing
     */
    private function render_pricing_section($variant, $data) {
        $titulo = $data['titulo'] ?? '';
        $subtitulo = $data['subtitulo'] ?? '';
        $items = $data['items'] ?? [];

        $output = '<div class="flavor-pricing">';

        if ($titulo) {
            $output .= '<div class="flavor-section__header">';
            $output .= '<h2 class="flavor-section__title">' . esc_html($titulo) . '</h2>';
            if ($subtitulo) {
                $output .= '<p class="flavor-section__subtitle">' . esc_html($subtitulo) . '</p>';
            }
            $output .= '</div>';
        }

        $output .= '<div class="flavor-pricing__grid">';
        foreach ($items as $item) {
            $destacado = !empty($item['destacado']) ? ' flavor-pricing-card--featured' : '';
            $output .= '<div class="flavor-pricing-card' . $destacado . '">';
            if (!empty($item['nombre'])) {
                $output .= '<h3 class="flavor-pricing-card__name">' . esc_html($item['nombre']) . '</h3>';
            }
            $output .= '<div class="flavor-pricing-card__price">';
            $output .= '<span class="flavor-pricing-card__amount">' . esc_html($item['precio'] ?? '') . '</span>';
            $output .= '<span class="flavor-pricing-card__period">' . esc_html($item['periodo'] ?? '') . '</span>';
            $output .= '</div>';
            if (!empty($item['descripcion'])) {
                $output .= '<p class="flavor-pricing-card__description">' . esc_html($item['descripcion']) . '</p>';
            }
            if (!empty($item['caracteristicas'])) {
                $features = explode("\n", $item['caracteristicas']);
                $output .= '<ul class="flavor-pricing-card__features">';
                foreach ($features as $feature) {
                    $feature = trim($feature);
                    if ($feature) {
                        $output .= '<li><span class="dashicons dashicons-yes"></span> ' . esc_html($feature) . '</li>';
                    }
                }
                $output .= '</ul>';
            }
            if (!empty($item['cta_texto'])) {
                $output .= '<a href="' . esc_url($item['cta_url'] ?? '#') . '" class="flavor-btn flavor-btn--primary">' . esc_html($item['cta_texto']) . '</a>';
            }
            $output .= '</div>';
        }
        $output .= '</div>';

        $output .= '</div>';

        return $output;
    }

    /**
     * Renderiza sección CTA
     */
    private function render_cta_section($variant, $data) {
        $titulo = $data['titulo'] ?? '';
        $descripcion = $data['descripcion'] ?? '';
        $imagen = $data['imagen'] ?? '';
        $boton_texto = $data['boton_texto'] ?? '';
        $boton_url = $data['boton_url'] ?? '#';

        $output = '<div class="flavor-cta flavor-cta--' . esc_attr($variant) . '">';

        if ($variant === 'con_imagen' && $imagen) {
            $output .= '<div class="flavor-cta__image">';
            $output .= '<img src="' . esc_url($imagen) . '" alt="">';
            $output .= '</div>';
        }

        $output .= '<div class="flavor-cta__content">';
        if ($titulo) {
            $output .= '<h2 class="flavor-cta__title">' . esc_html($titulo) . '</h2>';
        }
        if ($descripcion) {
            $output .= '<p class="flavor-cta__description">' . esc_html($descripcion) . '</p>';
        }
        if ($boton_texto) {
            $output .= '<div class="flavor-cta__buttons">';
            $output .= '<a href="' . esc_url($boton_url) . '" class="flavor-btn flavor-btn--primary flavor-btn--large">' . esc_html($boton_texto) . '</a>';
            if (!empty($data['boton_secundario_texto'])) {
                $output .= '<a href="' . esc_url($data['boton_secundario_url'] ?? '#') . '" class="flavor-btn flavor-btn--secondary">' . esc_html($data['boton_secundario_texto']) . '</a>';
            }
            $output .= '</div>';
        }
        $output .= '</div>';

        $output .= '</div>';

        return $output;
    }

    /**
     * Renderiza sección FAQ
     */
    private function render_faq_section($variant, $data) {
        $titulo = $data['titulo'] ?? '';
        $subtitulo = $data['subtitulo'] ?? '';
        $items = $data['items'] ?? [];

        $output = '<div class="flavor-faq flavor-faq--' . esc_attr($variant) . '">';

        if ($titulo) {
            $output .= '<div class="flavor-section__header">';
            $output .= '<h2 class="flavor-section__title">' . esc_html($titulo) . '</h2>';
            if ($subtitulo) {
                $output .= '<p class="flavor-section__subtitle">' . esc_html($subtitulo) . '</p>';
            }
            $output .= '</div>';
        }

        $output .= '<div class="flavor-faq__list">';
        foreach ($items as $index => $item) {
            $output .= '<div class="flavor-faq__item">';
            $output .= '<button class="flavor-faq__question" aria-expanded="false" aria-controls="faq-' . $index . '">';
            $output .= '<span>' . esc_html($item['pregunta'] ?? '') . '</span>';
            $output .= '<span class="dashicons dashicons-arrow-down-alt2"></span>';
            $output .= '</button>';
            $output .= '<div id="faq-' . $index . '" class="flavor-faq__answer" hidden>';
            $output .= '<p>' . esc_html($item['respuesta'] ?? '') . '</p>';
            $output .= '</div>';
            $output .= '</div>';
        }
        $output .= '</div>';

        $output .= '</div>';

        return $output;
    }

    /**
     * Renderiza sección Contacto
     */
    private function render_contacto_section($variant, $data) {
        $titulo = $data['titulo'] ?? '';
        $subtitulo = $data['subtitulo'] ?? '';
        $email = $data['email'] ?? '';
        $telefono = $data['telefono'] ?? '';
        $direccion = $data['direccion'] ?? '';
        $mostrar_form = $data['mostrar_formulario'] ?? true;

        $output = '<div class="flavor-contacto flavor-contacto--' . esc_attr($variant) . '">';

        if ($titulo) {
            $output .= '<div class="flavor-section__header">';
            $output .= '<h2 class="flavor-section__title">' . esc_html($titulo) . '</h2>';
            if ($subtitulo) {
                $output .= '<p class="flavor-section__subtitle">' . esc_html($subtitulo) . '</p>';
            }
            $output .= '</div>';
        }

        $output .= '<div class="flavor-contacto__grid">';

        // Info de contacto
        $output .= '<div class="flavor-contacto__info">';
        if ($email) {
            $output .= '<div class="flavor-contacto__item">';
            $output .= '<span class="dashicons dashicons-email"></span>';
            $output .= '<a href="mailto:' . esc_attr($email) . '">' . esc_html($email) . '</a>';
            $output .= '</div>';
        }
        if ($telefono) {
            $output .= '<div class="flavor-contacto__item">';
            $output .= '<span class="dashicons dashicons-phone"></span>';
            $output .= '<a href="tel:' . esc_attr(preg_replace('/\s+/', '', $telefono)) . '">' . esc_html($telefono) . '</a>';
            $output .= '</div>';
        }
        if ($direccion) {
            $output .= '<div class="flavor-contacto__item">';
            $output .= '<span class="dashicons dashicons-location"></span>';
            $output .= '<span>' . esc_html($direccion) . '</span>';
            $output .= '</div>';
        }
        if (!empty($data['mapa_embed'])) {
            $output .= '<div class="flavor-contacto__map">' . $data['mapa_embed'] . '</div>';
        }
        $output .= '</div>';

        // Formulario
        if ($mostrar_form) {
            $output .= '<div class="flavor-contacto__form">';
            $output .= '<form class="flavor-form" method="post">';
            $output .= '<div class="flavor-form__field">';
            $output .= '<label for="contact-name">' . __('Nombre', 'flavor-chat-ia') . '</label>';
            $output .= '<input type="text" id="contact-name" name="name" required>';
            $output .= '</div>';
            $output .= '<div class="flavor-form__field">';
            $output .= '<label for="contact-email">' . __('Email', 'flavor-chat-ia') . '</label>';
            $output .= '<input type="email" id="contact-email" name="email" required>';
            $output .= '</div>';
            $output .= '<div class="flavor-form__field">';
            $output .= '<label for="contact-message">' . __('Mensaje', 'flavor-chat-ia') . '</label>';
            $output .= '<textarea id="contact-message" name="message" rows="4" required></textarea>';
            $output .= '</div>';
            $output .= '<button type="submit" class="flavor-btn flavor-btn--primary">' . __('Enviar', 'flavor-chat-ia') . '</button>';
            $output .= '</form>';
            $output .= '</div>';
        }

        $output .= '</div>';
        $output .= '</div>';

        return $output;
    }

    /**
     * Renderiza sección Galería
     */
    private function render_galeria_section($variant, $data) {
        $titulo = $data['titulo'] ?? '';
        $subtitulo = $data['subtitulo'] ?? '';
        $imagenes = $data['imagenes'] ?? [];
        $columnas = intval($data['columnas'] ?? 3);

        $output = '<div class="flavor-galeria flavor-galeria--' . esc_attr($variant) . '">';

        if ($titulo) {
            $output .= '<div class="flavor-section__header">';
            $output .= '<h2 class="flavor-section__title">' . esc_html($titulo) . '</h2>';
            if ($subtitulo) {
                $output .= '<p class="flavor-section__subtitle">' . esc_html($subtitulo) . '</p>';
            }
            $output .= '</div>';
        }

        $output .= '<div class="flavor-galeria__grid flavor-grid--' . $columnas . '">';
        foreach ($imagenes as $imagen) {
            $img_url = is_array($imagen) ? ($imagen['url'] ?? '') : $imagen;
            if ($img_url) {
                $output .= '<div class="flavor-galeria__item">';
                $output .= '<img src="' . esc_url($img_url) . '" alt="" loading="lazy">';
                $output .= '</div>';
            }
        }
        $output .= '</div>';

        $output .= '</div>';

        return $output;
    }

    /**
     * Renderiza sección Stats
     */
    private function render_stats_section($variant, $data) {
        $titulo = $data['titulo'] ?? '';
        $items = $data['items'] ?? [];

        $output = '<div class="flavor-stats flavor-stats--' . esc_attr($variant) . '">';

        if ($titulo) {
            $output .= '<h2 class="flavor-section__title">' . esc_html($titulo) . '</h2>';
        }

        $output .= '<div class="flavor-stats__grid">';
        foreach ($items as $item) {
            $output .= '<div class="flavor-stat-card">';
            if (!empty($item['icono'])) {
                $output .= '<div class="flavor-stat-card__icon"><span class="dashicons dashicons-' . esc_attr($item['icono']) . '"></span></div>';
            }
            $output .= '<div class="flavor-stat-card__number">';
            $output .= '<span class="flavor-stat-card__value" data-value="' . esc_attr($item['numero'] ?? '0') . '">' . esc_html($item['numero'] ?? '') . '</span>';
            if (!empty($item['sufijo'])) {
                $output .= '<span class="flavor-stat-card__suffix">' . esc_html($item['sufijo']) . '</span>';
            }
            $output .= '</div>';
            if (!empty($item['etiqueta'])) {
                $output .= '<div class="flavor-stat-card__label">' . esc_html($item['etiqueta']) . '</div>';
            }
            $output .= '</div>';
        }
        $output .= '</div>';

        $output .= '</div>';

        return $output;
    }

    /**
     * Renderiza sección Equipo
     */
    private function render_equipo_section($variant, $data) {
        $titulo = $data['titulo'] ?? '';
        $subtitulo = $data['subtitulo'] ?? '';
        $items = $data['items'] ?? [];

        $output = '<div class="flavor-equipo flavor-equipo--' . esc_attr($variant) . '">';

        if ($titulo) {
            $output .= '<div class="flavor-section__header">';
            $output .= '<h2 class="flavor-section__title">' . esc_html($titulo) . '</h2>';
            if ($subtitulo) {
                $output .= '<p class="flavor-section__subtitle">' . esc_html($subtitulo) . '</p>';
            }
            $output .= '</div>';
        }

        $output .= '<div class="flavor-equipo__grid">';
        foreach ($items as $item) {
            $output .= '<div class="flavor-miembro-card">';
            if (!empty($item['imagen'])) {
                $output .= '<div class="flavor-miembro-card__image">';
                $output .= '<img src="' . esc_url($item['imagen']) . '" alt="' . esc_attr($item['nombre'] ?? '') . '">';
                $output .= '</div>';
            }
            $output .= '<div class="flavor-miembro-card__info">';
            if (!empty($item['nombre'])) {
                $output .= '<h3 class="flavor-miembro-card__name">' . esc_html($item['nombre']) . '</h3>';
            }
            if (!empty($item['cargo'])) {
                $output .= '<p class="flavor-miembro-card__role">' . esc_html($item['cargo']) . '</p>';
            }
            if (!empty($item['bio'])) {
                $output .= '<p class="flavor-miembro-card__bio">' . esc_html($item['bio']) . '</p>';
            }
            if (!empty($item['linkedin']) || !empty($item['twitter'])) {
                $output .= '<div class="flavor-miembro-card__social">';
                if (!empty($item['linkedin'])) {
                    $output .= '<a href="' . esc_url($item['linkedin']) . '" target="_blank" rel="noopener"><span class="dashicons dashicons-linkedin"></span></a>';
                }
                if (!empty($item['twitter'])) {
                    $output .= '<a href="' . esc_url($item['twitter']) . '" target="_blank" rel="noopener"><span class="dashicons dashicons-twitter"></span></a>';
                }
                $output .= '</div>';
            }
            $output .= '</div>';
            $output .= '</div>';
        }
        $output .= '</div>';

        $output .= '</div>';

        return $output;
    }

    /**
     * Renderiza sección Logos
     */
    private function render_logos_section($variant, $data) {
        $titulo = $data['titulo'] ?? '';
        $logos = $data['logos'] ?? [];

        $output = '<div class="flavor-logos flavor-logos--' . esc_attr($variant) . '">';

        if ($titulo) {
            $output .= '<h2 class="flavor-section__title">' . esc_html($titulo) . '</h2>';
        }

        $output .= '<div class="flavor-logos__grid">';
        foreach ($logos as $logo) {
            $logo_url = is_array($logo) ? ($logo['url'] ?? '') : $logo;
            if ($logo_url) {
                $output .= '<div class="flavor-logos__item">';
                $output .= '<img src="' . esc_url($logo_url) . '" alt="" loading="lazy">';
                $output .= '</div>';
            }
        }
        $output .= '</div>';

        $output .= '</div>';

        return $output;
    }

    /**
     * Renderiza sección Video
     */
    private function render_video_section($variant, $data) {
        $titulo = $data['titulo'] ?? '';
        $video_url = $data['video_url'] ?? '';
        $poster = $data['poster'] ?? '';
        $descripcion = $data['descripcion'] ?? '';

        $output = '<div class="flavor-video flavor-video--' . esc_attr($variant) . '">';

        if ($titulo) {
            $output .= '<h2 class="flavor-section__title">' . esc_html($titulo) . '</h2>';
        }

        if ($video_url) {
            // Detectar tipo de video (YouTube, Vimeo, etc.)
            $embed_url = $this->get_video_embed_url($video_url);
            $output .= '<div class="flavor-video__player">';
            if ($embed_url) {
                $output .= '<iframe src="' . esc_url($embed_url) . '" frameborder="0" allowfullscreen loading="lazy"></iframe>';
            }
            $output .= '</div>';
        }

        if ($descripcion) {
            $output .= '<p class="flavor-video__description">' . esc_html($descripcion) . '</p>';
        }

        $output .= '</div>';

        return $output;
    }

    /**
     * Renderiza sección Texto
     */
    private function render_texto_section($variant, $data) {
        $titulo = $data['titulo'] ?? '';
        $contenido = $data['contenido'] ?? '';
        $imagen = $data['imagen'] ?? '';
        $imagen_pos = $data['imagen_posicion'] ?? 'right';

        $output = '<div class="flavor-texto flavor-texto--' . esc_attr($variant) . '">';

        if ($variant === 'con_imagen' && $imagen) {
            $output .= '<div class="flavor-texto__grid flavor-texto--image-' . esc_attr($imagen_pos) . '">';
        }

        $output .= '<div class="flavor-texto__content">';
        if ($titulo) {
            $output .= '<h2>' . esc_html($titulo) . '</h2>';
        }
        if ($contenido) {
            $output .= wpautop(wp_kses_post($contenido));
        }
        $output .= '</div>';

        if ($variant === 'con_imagen' && $imagen) {
            $output .= '<div class="flavor-texto__image">';
            $output .= '<img src="' . esc_url($imagen) . '" alt="">';
            $output .= '</div>';
            $output .= '</div>';
        }

        $output .= '</div>';

        return $output;
    }

    /**
     * Renderiza sección Separador
     */
    private function render_separador_section($variant, $data) {
        $altura = intval($data['altura'] ?? 60);
        $color = $data['color'] ?? '#e2e8f0';

        $output = '<div class="flavor-separador flavor-separador--' . esc_attr($variant) . '" style="height: ' . $altura . 'px;">';

        if ($variant === 'linea') {
            $output .= '<hr style="border-color: ' . esc_attr($color) . ';">';
        } elseif ($variant === 'onda') {
            $output .= '<svg viewBox="0 0 1200 120" preserveAspectRatio="none" style="fill: ' . esc_attr($color) . ';">
                <path d="M321.39,56.44c58-10.79,114.16-30.13,172-41.86,82.39-16.72,168.19-17.73,250.45-.39C823.78,31,906.67,72,985.66,92.83c70.05,18.48,146.53,26.09,214.34,3V0H0V27.35A600.21,600.21,0,0,0,321.39,56.44Z"></path>
            </svg>';
        } elseif ($variant === 'diagonal') {
            $output .= '<svg viewBox="0 0 1200 120" preserveAspectRatio="none" style="fill: ' . esc_attr($color) . ';">
                <polygon points="1200 0 1200 120 0 120"></polygon>
            </svg>';
        }

        $output .= '</div>';

        return $output;
    }

    /**
     * Renderiza sección genérica
     */
    private function render_generic_section($data) {
        $output = '';

        if (!empty($data['titulo'])) {
            $output .= '<h2 class="flavor-section__title">' . esc_html($data['titulo']) . '</h2>';
        }
        if (!empty($data['subtitulo'])) {
            $output .= '<p class="flavor-section__subtitle">' . esc_html($data['subtitulo']) . '</p>';
        }
        if (!empty($data['contenido'])) {
            $output .= '<div class="flavor-section__content">' . wpautop(wp_kses_post($data['contenido'])) . '</div>';
        }

        return $output;
    }

    /**
     * Obtiene URL de embed para videos
     *
     * @param string $url
     * @return string|null
     */
    private function get_video_embed_url($url) {
        // YouTube
        if (preg_match('/(?:youtube\.com\/(?:watch\?v=|embed\/)|youtu\.be\/)([a-zA-Z0-9_-]+)/', $url, $matches)) {
            return 'https://www.youtube.com/embed/' . $matches[1];
        }

        // Vimeo
        if (preg_match('/vimeo\.com\/(?:video\/)?(\d+)/', $url, $matches)) {
            return 'https://player.vimeo.com/video/' . $matches[1];
        }

        return null;
    }

    /**
     * Estilos globales de la landing
     *
     * @param string $primary_color
     * @return string
     */
    private function get_landing_styles($primary_color = '#3b82f6') {
        return '<style>
            .flavor-landing-visual {
                --flavor-primary: ' . esc_attr($primary_color) . ';
                --flavor-primary-light: ' . esc_attr($this->adjust_brightness($primary_color, 20)) . ';
                --flavor-primary-dark: ' . esc_attr($this->adjust_brightness($primary_color, -20)) . ';
            }
        </style>';
    }

    /**
     * Ajusta el brillo de un color
     *
     * @param string $hex
     * @param int $steps
     * @return string
     */
    private function adjust_brightness($hex, $steps) {
        $hex = ltrim($hex, '#');
        $r = hexdec(substr($hex, 0, 2));
        $g = hexdec(substr($hex, 2, 2));
        $b = hexdec(substr($hex, 4, 2));

        $r = max(0, min(255, $r + $steps));
        $g = max(0, min(255, $g + $steps));
        $b = max(0, min(255, $b + $steps));

        return sprintf('#%02x%02x%02x', $r, $g, $b);
    }

    /**
     * Shortcode para renderizar desde estructura visual
     *
     * @param array $atts
     * @return string
     */
    public function render_from_shortcode($atts) {
        $atts = shortcode_atts([
            'id' => 0,
        ], $atts, 'flavor_landing_visual');

        $post_id = $atts['id'] ? intval($atts['id']) : get_the_ID();
        $structure = $this->get_landing_structure($post_id);

        if (empty($structure)) {
            if (current_user_can('edit_post', $post_id)) {
                return '<div class="flavor-landing-notice" style="padding: 40px; text-align: center; background: #f8fafc; border-radius: 8px;">
                    <p>' . __('Esta landing aún no tiene contenido.', 'flavor-chat-ia') . '</p>
                    <a href="' . esc_url(admin_url('admin.php?page=flavor-landing-editor&post_id=' . $post_id)) . '" class="button button-primary">' . __('Crear con Editor Visual', 'flavor-chat-ia') . '</a>
                </div>';
            }
            return '';
        }

        // Encolar estilos frontend
        $this->enqueue_frontend_styles();

        return $this->render_from_structure($structure);
    }

    /**
     * Encola estilos y scripts del frontend
     */
    private function enqueue_frontend_styles() {
        if (!wp_style_is('flavor-landing-frontend', 'enqueued')) {
            // Swiper CSS
            wp_enqueue_style(
                'swiper',
                'https://cdn.jsdelivr.net/npm/swiper@11/swiper-bundle.min.css',
                [],
                '11.0.0'
            );

            wp_enqueue_style(
                'flavor-landing-frontend',
                FLAVOR_CHAT_IA_URL . 'assets/css/landing-frontend.css',
                ['swiper'],
                FLAVOR_CHAT_IA_VERSION
            );
            wp_enqueue_style('dashicons');
        }

        if (!wp_script_is('flavor-landing-frontend', 'enqueued')) {
            // Swiper JS
            wp_enqueue_script(
                'swiper',
                'https://cdn.jsdelivr.net/npm/swiper@11/swiper-bundle.min.js',
                [],
                '11.0.0',
                true
            );

            wp_enqueue_script(
                'flavor-landing-frontend',
                FLAVOR_CHAT_IA_URL . 'assets/js/landing-frontend.js',
                ['swiper'],
                FLAVOR_CHAT_IA_VERSION,
                true
            );
        }
    }

    // =========================================================
    // AJAX HANDLERS
    // =========================================================

    /**
     * AJAX: Guardar estructura
     */
    public function ajax_save_structure() {
        check_ajax_referer('flavor_landing_editor', 'nonce');

        $post_id = intval($_POST['post_id'] ?? 0);
        $sections = json_decode(stripslashes($_POST['sections'] ?? '[]'), true);

        if (!$post_id || !current_user_can('edit_post', $post_id)) {
            wp_send_json_error(['message' => __('Permisos insuficientes', 'flavor-chat-ia')]);
        }

        $result = $this->save_landing_structure($post_id, $sections);

        if ($result) {
            wp_send_json_success(['message' => __('Guardado correctamente', 'flavor-chat-ia')]);
        } else {
            wp_send_json_error(['message' => __('Error al guardar', 'flavor-chat-ia')]);
        }
    }

    /**
     * AJAX: Cargar estructura
     */
    public function ajax_load_structure() {
        check_ajax_referer('flavor_landing_editor', 'nonce');

        $post_id = intval($_POST['post_id'] ?? 0);

        if (!$post_id || !current_user_can('edit_post', $post_id)) {
            wp_send_json_error(['message' => __('Permisos insuficientes', 'flavor-chat-ia')]);
        }

        $structure = $this->get_landing_structure($post_id);

        wp_send_json_success([
            'structure' => $structure,
            'sections' => $this->available_sections,
        ]);
    }

    /**
     * AJAX: Renderizar preview
     */
    public function ajax_render_preview() {
        check_ajax_referer('flavor_landing_editor', 'nonce');

        $sections = json_decode(stripslashes($_POST['sections'] ?? '[]'), true);

        $structure = [
            'sections' => $sections,
            'settings' => [
                'color_primario' => sanitize_hex_color($_POST['color_primario'] ?? '#3b82f6'),
            ],
        ];

        $html = $this->render_from_structure($structure);

        // Añadir estilos base
        $styles = file_get_contents(FLAVOR_CHAT_IA_PATH . 'assets/css/landing-frontend.css');

        // Estilos del tema activo
        $theme_links = '';
        $stylesheet_uri = get_stylesheet_uri();
        if (!empty($stylesheet_uri)) {
            $theme_links .= '<link rel="stylesheet" href="' . esc_url($stylesheet_uri) . '">';
        }
        $template_uri = trailingslashit(get_template_directory_uri()) . 'style.css';
        if (!empty($template_uri) && $template_uri !== $stylesheet_uri) {
            $theme_links .= '<link rel="stylesheet" href="' . esc_url($template_uri) . '">';
        }

        // Enqueue y cargar estilos registrados en el frontend
        ob_start();
        do_action('wp_enqueue_scripts');
        wp_print_styles();
        $enqueued_styles = ob_get_clean();

        $output = '<!DOCTYPE html>
        <html>
        <head>
            <meta charset="UTF-8">
            <meta name="viewport" content="width=device-width, initial-scale=1.0">
            <link rel="stylesheet" href="' . includes_url('css/dashicons.min.css') . '">
            ' . $theme_links . '
            ' . $enqueued_styles . '
            <style>' . $styles . '</style>
        </head>
        <body style="margin: 0; padding: 0;">
            ' . $html . '
            <script>
                // FAQ toggle
                document.querySelectorAll(".flavor-faq__question").forEach(function(btn) {
                    btn.addEventListener("click", function() {
                        var expanded = this.getAttribute("aria-expanded") === "true";
                        this.setAttribute("aria-expanded", !expanded);
                        var answer = document.getElementById(this.getAttribute("aria-controls"));
                        if (answer) answer.hidden = expanded;
                    });
                });

                // Animaciones con Intersection Observer
                if ("IntersectionObserver" in window) {
                    var animationObserver = new IntersectionObserver(function(entries) {
                        entries.forEach(function(entry) {
                            if (entry.isIntersecting) {
                                entry.target.classList.add("flavor-animated");
                                animationObserver.unobserve(entry.target);
                            }
                        });
                    }, { threshold: 0.1, rootMargin: "0px 0px -50px 0px" });

                    document.querySelectorAll(".flavor-animate").forEach(function(el) {
                        animationObserver.observe(el);
                    });
                } else {
                    // Fallback: mostrar todo sin animación
                    document.querySelectorAll(".flavor-animate").forEach(function(el) {
                        el.classList.add("flavor-animated");
                    });
                }
            </script>
        </body>
        </html>';

        echo $output;
        wp_die();
    }

    /**
     * AJAX: Obtener secciones
     */
    public function ajax_get_sections() {
        check_ajax_referer('flavor_landing_editor', 'nonce');

        $profile = sanitize_text_field($_POST['profile'] ?? '');
        $sections = $this->get_available_sections($profile);

        wp_send_json_success(['sections' => $sections]);
    }

    /**
     * AJAX: Autosave
     */
    public function ajax_autosave() {
        check_ajax_referer('flavor_landing_editor', 'nonce');

        $post_id = intval($_POST['post_id'] ?? 0);
        $sections = json_decode(stripslashes($_POST['sections'] ?? '[]'), true);

        if (!$post_id || !current_user_can('edit_post', $post_id)) {
            wp_send_json_error(['message' => __('Permisos insuficientes', 'flavor-chat-ia')]);
        }

        // Guardar sin añadir al historial (es autosave)
        $structure = [
            'version' => '1.0',
            'updated_at' => current_time('mysql'),
            'updated_by' => get_current_user_id(),
            'sections' => $sections,
            'settings' => [
                'color_primario' => sanitize_hex_color($_POST['color_primario'] ?? '#3b82f6'),
            ],
            'is_autosave' => true,
        ];

        update_post_meta($post_id, self::META_KEY . '_autosave', $structure);

        wp_send_json_success(['message' => __('Autoguardado', 'flavor-chat-ia')]);
    }

    /**
     * AJAX: Deshacer último cambio
     */
    public function ajax_undo() {
        check_ajax_referer('flavor_landing_editor', 'nonce');

        $post_id = intval($_POST['post_id'] ?? 0);

        if (!$post_id || !current_user_can('edit_post', $post_id)) {
            wp_send_json_error(['message' => __('Permisos insuficientes', 'flavor-chat-ia')]);
        }

        $history = get_post_meta($post_id, self::HISTORY_META_KEY, true);

        if (empty($history) || !is_array($history)) {
            wp_send_json_error(['message' => __('No hay historial disponible', 'flavor-chat-ia')]);
        }

        // Obtener último estado del historial
        $previous_state = array_shift($history);

        // Guardar estado actual antes de restaurar
        $current = get_post_meta($post_id, self::META_KEY, true);

        // Restaurar estado anterior
        update_post_meta($post_id, self::META_KEY, $previous_state);
        update_post_meta($post_id, self::HISTORY_META_KEY, $history);

        wp_send_json_success([
            'message' => __('Cambio deshecho', 'flavor-chat-ia'),
            'structure' => $previous_state,
        ]);
    }

    /**
     * AJAX: Obtener plantillas predefinidas
     */
    public function ajax_get_templates() {
        check_ajax_referer('flavor_landing_editor', 'nonce');

        $templates = $this->get_landing_templates();

        wp_send_json_success([
            'templates' => $templates,
        ]);
    }

    /**
     * Obtiene las plantillas predefinidas desde el archivo JSON
     *
     * @return array
     */
    public function get_landing_templates() {
        $templates_file = FLAVOR_CHAT_IA_PATH . 'includes/editor/landing-templates.json';

        if (!file_exists($templates_file)) {
            return [];
        }

        $json_content = file_get_contents($templates_file);
        $data = json_decode($json_content, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            flavor_log_error( 'Error parsing templates JSON - ' . json_last_error_msg(), 'LandingEditor' );
            return [];
        }

        $templates = $data['templates'] ?? [];

        // Permitir que plugins/temas añadan más plantillas
        $templates = apply_filters('flavor_landing_templates', $templates);

        return $templates;
    }
}

// Inicializar
Flavor_Landing_Editor::get_instance();

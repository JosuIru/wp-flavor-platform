<?php
/**
 * Visual Builder - Todos los Componentes Unificados
 *
 * Registra TODOS los componentes y secciones del Visual Builder:
 * - Secciones de Landing Pages (hero, features, testimonios, etc.)
 * - Componentes Themacle (si el módulo está activo)
 * - Componentes básicos (texto, imagen, botón, etc.)
 *
 * @package FlavorChatIA
 * @subpackage VisualBuilder
 * @since 4.0.0
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Clase que unifica todos los componentes del Visual Builder
 */
class Flavor_VB_All_Components {

    /**
     * Instancia singleton
     *
     * @var Flavor_VB_All_Components|null
     */
    private static $instancia = null;

    /**
     * Componentes Themacle disponibles
     *
     * @var array
     */
    private $themacle_components = [];

    /**
     * Obtener instancia singleton
     *
     * @return Flavor_VB_All_Components
     */
    public static function get_instance() {
        if (self::$instancia === null) {
            self::$instancia = new self();
        }
        return self::$instancia;
    }

    /**
     * Constructor privado
     */
    private function __construct() {
        $this->init_hooks();
        $this->cargar_componentes_themacle();
    }

    /**
     * Inicializar hooks
     */
    private function init_hooks() {
        add_action('flavor_vb_register_sections', [$this, 'registrar_secciones_landing'], 10);
        add_action('flavor_vb_register_components', [$this, 'registrar_componentes_basicos'], 10);
        add_action('flavor_vb_register_components', [$this, 'registrar_componentes_themacle'], 20);
    }

    /**
     * Cargar componentes de Themacle si está disponible
     */
    private function cargar_componentes_themacle() {
        if (class_exists('Flavor_Chat_Themacle_Module')) {
            $themacle = new Flavor_Chat_Themacle_Module();
            if ($themacle->is_active()) {
                $this->themacle_components = $themacle->get_web_components();
            }
        }
    }

    // =========================================================================
    // SECCIONES DE LANDING PAGES
    // =========================================================================

    /**
     * Registrar todas las secciones de landing
     *
     * @param Flavor_Visual_Builder $builder
     */
    public function registrar_secciones_landing($builder) {
        // Hero Sections
        $builder->register_section('hero', [
            'label'       => __('Hero', 'flavor-chat-ia'),
            'description' => __('Sección principal con título y CTA', 'flavor-chat-ia'),
            'icon'        => 'dashicons-welcome-widgets-menus',
            'category'    => 'landing',
            'variants'    => ['fullscreen', 'split', 'centered', 'video', 'slider'],
            'fields'      => $this->get_campos_hero(),
            'render_callback' => [$this, 'render_hero'],
        ]);

        // Features
        $builder->register_section('features', [
            'label'       => __('Características', 'flavor-chat-ia'),
            'description' => __('Grid de características con iconos', 'flavor-chat-ia'),
            'icon'        => 'dashicons-screenoptions',
            'category'    => 'landing',
            'variants'    => ['grid', 'list', 'alternating', 'cards'],
            'fields'      => $this->get_campos_features(),
            'render_callback' => [$this, 'render_features'],
        ]);

        // Testimonios
        $builder->register_section('testimonios', [
            'label'       => __('Testimonios', 'flavor-chat-ia'),
            'description' => __('Testimonios de clientes', 'flavor-chat-ia'),
            'icon'        => 'dashicons-format-quote',
            'category'    => 'landing',
            'variants'    => ['carousel', 'grid', 'single', 'masonry'],
            'fields'      => $this->get_campos_testimonios(),
            'render_callback' => [$this, 'render_testimonios'],
        ]);

        // Pricing / Precios
        $builder->register_section('pricing', [
            'label'       => __('Precios', 'flavor-chat-ia'),
            'description' => __('Tabla de precios y planes', 'flavor-chat-ia'),
            'icon'        => 'dashicons-money-alt',
            'category'    => 'landing',
            'variants'    => ['cards', 'table', 'toggle', 'comparison'],
            'fields'      => $this->get_campos_pricing(),
            'render_callback' => [$this, 'render_pricing'],
        ]);

        // CTA (Call to Action)
        $builder->register_section('cta', [
            'label'       => __('Call to Action', 'flavor-chat-ia'),
            'description' => __('Sección de llamada a la acción', 'flavor-chat-ia'),
            'icon'        => 'dashicons-megaphone',
            'category'    => 'landing',
            'variants'    => ['simple', 'banner', 'split', 'gradient'],
            'fields'      => $this->get_campos_cta(),
            'render_callback' => [$this, 'render_cta'],
        ]);

        // FAQ
        $builder->register_section('faq', [
            'label'       => __('FAQ', 'flavor-chat-ia'),
            'description' => __('Preguntas frecuentes', 'flavor-chat-ia'),
            'icon'        => 'dashicons-editor-help',
            'category'    => 'landing',
            'variants'    => ['accordion', 'two-columns', 'categories'],
            'fields'      => $this->get_campos_faq(),
            'render_callback' => [$this, 'render_faq'],
        ]);

        // Contacto
        $builder->register_section('contacto', [
            'label'       => __('Contacto', 'flavor-chat-ia'),
            'description' => __('Formulario e información de contacto', 'flavor-chat-ia'),
            'icon'        => 'dashicons-email-alt',
            'category'    => 'landing',
            'variants'    => ['form', 'split', 'map', 'cards'],
            'fields'      => $this->get_campos_contacto(),
            'render_callback' => [$this, 'render_contacto'],
        ]);

        // Galería
        $builder->register_section('galeria', [
            'label'       => __('Galería', 'flavor-chat-ia'),
            'description' => __('Galería de imágenes', 'flavor-chat-ia'),
            'icon'        => 'dashicons-format-gallery',
            'category'    => 'landing',
            'variants'    => ['grid', 'masonry', 'carousel', 'lightbox'],
            'fields'      => $this->get_campos_galeria(),
            'render_callback' => [$this, 'render_galeria'],
        ]);

        // Estadísticas / Números
        $builder->register_section('stats', [
            'label'       => __('Estadísticas', 'flavor-chat-ia'),
            'description' => __('Números y métricas destacadas', 'flavor-chat-ia'),
            'icon'        => 'dashicons-chart-bar',
            'category'    => 'landing',
            'variants'    => ['counters', 'cards', 'horizontal'],
            'fields'      => $this->get_campos_stats(),
            'render_callback' => [$this, 'render_stats'],
        ]);

        // Equipo
        $builder->register_section('equipo', [
            'label'       => __('Equipo', 'flavor-chat-ia'),
            'description' => __('Miembros del equipo', 'flavor-chat-ia'),
            'icon'        => 'dashicons-groups',
            'category'    => 'landing',
            'variants'    => ['grid', 'carousel', 'cards'],
            'fields'      => $this->get_campos_equipo(),
            'render_callback' => [$this, 'render_equipo'],
        ]);

        // Logos / Partners
        $builder->register_section('logos', [
            'label'       => __('Logos / Partners', 'flavor-chat-ia'),
            'description' => __('Logos de partners o clientes', 'flavor-chat-ia'),
            'icon'        => 'dashicons-awards',
            'category'    => 'landing',
            'variants'    => ['grid', 'carousel', 'grayscale'],
            'fields'      => $this->get_campos_logos(),
            'render_callback' => [$this, 'render_logos'],
        ]);

        // Video
        $builder->register_section('video', [
            'label'       => __('Video', 'flavor-chat-ia'),
            'description' => __('Sección con video destacado', 'flavor-chat-ia'),
            'icon'        => 'dashicons-video-alt3',
            'category'    => 'landing',
            'variants'    => ['fullwidth', 'modal', 'split'],
            'fields'      => $this->get_campos_video(),
            'render_callback' => [$this, 'render_video'],
        ]);

        // Texto + Media
        $builder->register_section('texto-media', [
            'label'       => __('Texto + Media', 'flavor-chat-ia'),
            'description' => __('Texto con imagen o video lateral', 'flavor-chat-ia'),
            'icon'        => 'dashicons-align-pull-left',
            'category'    => 'landing',
            'variants'    => ['image-left', 'image-right', 'video'],
            'fields'      => $this->get_campos_texto_media(),
            'render_callback' => [$this, 'render_texto_media'],
        ]);

        // Separador
        $builder->register_section('separador', [
            'label'       => __('Separador', 'flavor-chat-ia'),
            'description' => __('Separador visual entre secciones', 'flavor-chat-ia'),
            'icon'        => 'dashicons-minus',
            'category'    => 'landing',
            'variants'    => ['line', 'wave', 'angle', 'dots'],
            'fields'      => $this->get_campos_separador(),
            'render_callback' => [$this, 'render_separador'],
        ]);

        // Newsletter
        $builder->register_section('newsletter', [
            'label'       => __('Newsletter', 'flavor-chat-ia'),
            'description' => __('Formulario de suscripción', 'flavor-chat-ia'),
            'icon'        => 'dashicons-email',
            'category'    => 'landing',
            'variants'    => ['simple', 'banner', 'popup'],
            'fields'      => $this->get_campos_newsletter(),
            'render_callback' => [$this, 'render_newsletter'],
        ]);

        // Portfolio / Proyectos
        $builder->register_section('portfolio', [
            'label'       => __('Portfolio', 'flavor-chat-ia'),
            'description' => __('Galería de proyectos o trabajos', 'flavor-chat-ia'),
            'icon'        => 'dashicons-portfolio',
            'category'    => 'landing',
            'variants'    => ['grid', 'masonry', 'filterable'],
            'fields'      => $this->get_campos_portfolio(),
            'render_callback' => [$this, 'render_portfolio'],
        ]);

        // Blog / Noticias
        $builder->register_section('blog', [
            'label'       => __('Blog / Noticias', 'flavor-chat-ia'),
            'description' => __('Últimas entradas del blog', 'flavor-chat-ia'),
            'icon'        => 'dashicons-admin-post',
            'category'    => 'landing',
            'variants'    => ['grid', 'list', 'featured'],
            'fields'      => $this->get_campos_blog(),
            'render_callback' => [$this, 'render_blog'],
        ]);

        // Timeline
        $builder->register_section('timeline', [
            'label'       => __('Timeline', 'flavor-chat-ia'),
            'description' => __('Línea de tiempo con eventos', 'flavor-chat-ia'),
            'icon'        => 'dashicons-backup',
            'category'    => 'landing',
            'variants'    => ['vertical', 'horizontal'],
            'fields'      => $this->get_campos_timeline(),
            'render_callback' => [$this, 'render_timeline'],
        ]);
    }

    // =========================================================================
    // COMPONENTES BÁSICOS
    // =========================================================================

    /**
     * Registrar componentes básicos
     *
     * @param Flavor_Visual_Builder $builder
     */
    public function registrar_componentes_basicos($builder) {
        // Encabezado
        $builder->register_component('heading', [
            'label'       => __('Encabezado', 'flavor-chat-ia'),
            'description' => __('Título o encabezado', 'flavor-chat-ia'),
            'icon'        => 'dashicons-editor-bold',
            'category'    => 'basic',
            'fields'      => [
                'texto' => ['type' => 'text', 'label' => __('Texto', 'flavor-chat-ia')],
                'nivel' => ['type' => 'select', 'label' => __('Nivel', 'flavor-chat-ia'), 'options' => ['h1' => 'H1', 'h2' => 'H2', 'h3' => 'H3', 'h4' => 'H4', 'h5' => 'H5', 'h6' => 'H6']],
                'alineacion' => ['type' => 'select', 'label' => __('Alineación', 'flavor-chat-ia'), 'options' => ['left' => 'Izquierda', 'center' => 'Centro', 'right' => 'Derecha']],
            ],
            'render_callback' => [$this, 'render_heading'],
        ]);

        // Texto / Párrafo
        $builder->register_component('text', [
            'label'       => __('Texto', 'flavor-chat-ia'),
            'description' => __('Bloque de texto enriquecido', 'flavor-chat-ia'),
            'icon'        => 'dashicons-editor-paragraph',
            'category'    => 'basic',
            'fields'      => [
                'contenido' => ['type' => 'wysiwyg', 'label' => __('Contenido', 'flavor-chat-ia')],
            ],
            'render_callback' => [$this, 'render_text'],
        ]);

        // Imagen
        $builder->register_component('image', [
            'label'       => __('Imagen', 'flavor-chat-ia'),
            'description' => __('Imagen con opciones de tamaño', 'flavor-chat-ia'),
            'icon'        => 'dashicons-format-image',
            'category'    => 'basic',
            'fields'      => [
                'imagen_id' => ['type' => 'image', 'label' => __('Imagen', 'flavor-chat-ia')],
                'alt' => ['type' => 'text', 'label' => __('Texto alternativo', 'flavor-chat-ia')],
                'tamaño' => ['type' => 'select', 'label' => __('Tamaño', 'flavor-chat-ia'), 'options' => ['thumbnail' => 'Miniatura', 'medium' => 'Mediano', 'large' => 'Grande', 'full' => 'Original']],
                'enlace' => ['type' => 'url', 'label' => __('Enlace', 'flavor-chat-ia')],
            ],
            'render_callback' => [$this, 'render_image'],
        ]);

        // Botón
        $builder->register_component('button', [
            'label'       => __('Botón', 'flavor-chat-ia'),
            'description' => __('Botón de acción', 'flavor-chat-ia'),
            'icon'        => 'dashicons-button',
            'category'    => 'basic',
            'fields'      => [
                'texto' => ['type' => 'text', 'label' => __('Texto', 'flavor-chat-ia')],
                'enlace' => ['type' => 'url', 'label' => __('Enlace', 'flavor-chat-ia')],
                'estilo' => ['type' => 'select', 'label' => __('Estilo', 'flavor-chat-ia'), 'options' => ['primary' => 'Primario', 'secondary' => 'Secundario', 'outline' => 'Outline', 'ghost' => 'Ghost']],
                'tamaño' => ['type' => 'select', 'label' => __('Tamaño', 'flavor-chat-ia'), 'options' => ['sm' => 'Pequeño', 'md' => 'Mediano', 'lg' => 'Grande']],
                'nueva_ventana' => ['type' => 'toggle', 'label' => __('Abrir en nueva ventana', 'flavor-chat-ia')],
            ],
            'render_callback' => [$this, 'render_button'],
        ]);

        // Espaciador
        $builder->register_component('spacer', [
            'label'       => __('Espaciador', 'flavor-chat-ia'),
            'description' => __('Espacio vertical', 'flavor-chat-ia'),
            'icon'        => 'dashicons-editor-expand',
            'category'    => 'basic',
            'fields'      => [
                'altura' => ['type' => 'number', 'label' => __('Altura (px)', 'flavor-chat-ia'), 'default' => 50],
            ],
            'render_callback' => [$this, 'render_spacer'],
        ]);

        // Divisor
        $builder->register_component('divider', [
            'label'       => __('Divisor', 'flavor-chat-ia'),
            'description' => __('Línea divisora', 'flavor-chat-ia'),
            'icon'        => 'dashicons-minus',
            'category'    => 'basic',
            'fields'      => [
                'estilo' => ['type' => 'select', 'label' => __('Estilo', 'flavor-chat-ia'), 'options' => ['solid' => 'Sólido', 'dashed' => 'Punteado', 'dotted' => 'Puntos', 'double' => 'Doble']],
                'ancho' => ['type' => 'select', 'label' => __('Ancho', 'flavor-chat-ia'), 'options' => ['full' => 'Completo', '75' => '75%', '50' => '50%', '25' => '25%']],
                'color' => ['type' => 'color', 'label' => __('Color', 'flavor-chat-ia'), 'default' => '#e5e7eb'],
            ],
            'render_callback' => [$this, 'render_divider'],
        ]);

        // HTML Personalizado
        $builder->register_component('html', [
            'label'       => __('HTML', 'flavor-chat-ia'),
            'description' => __('Código HTML personalizado', 'flavor-chat-ia'),
            'icon'        => 'dashicons-editor-code',
            'category'    => 'basic',
            'fields'      => [
                'codigo' => ['type' => 'code', 'label' => __('Código HTML', 'flavor-chat-ia')],
            ],
            'render_callback' => [$this, 'render_html'],
        ]);

        // Shortcode
        $builder->register_component('shortcode', [
            'label'       => __('Shortcode', 'flavor-chat-ia'),
            'description' => __('Insertar un shortcode', 'flavor-chat-ia'),
            'icon'        => 'dashicons-shortcode',
            'category'    => 'basic',
            'fields'      => [
                'shortcode' => ['type' => 'text', 'label' => __('Shortcode', 'flavor-chat-ia')],
            ],
            'render_callback' => [$this, 'render_shortcode'],
        ]);

        // Icono
        $builder->register_component('icon', [
            'label'       => __('Icono', 'flavor-chat-ia'),
            'description' => __('Icono con estilo', 'flavor-chat-ia'),
            'icon'        => 'dashicons-star-empty',
            'category'    => 'basic',
            'fields'      => [
                'icono' => ['type' => 'icon', 'label' => __('Icono', 'flavor-chat-ia')],
                'tamaño' => ['type' => 'number', 'label' => __('Tamaño (px)', 'flavor-chat-ia'), 'default' => 48],
                'color' => ['type' => 'color', 'label' => __('Color', 'flavor-chat-ia')],
            ],
            'render_callback' => [$this, 'render_icon'],
        ]);

        // Columnas
        $builder->register_component('columns', [
            'label'       => __('Columnas', 'flavor-chat-ia'),
            'description' => __('Layout de columnas flexible', 'flavor-chat-ia'),
            'icon'        => 'dashicons-columns',
            'category'    => 'layout',
            'fields'      => [
                'columnas' => ['type' => 'select', 'label' => __('Columnas', 'flavor-chat-ia'), 'options' => ['2' => '2 columnas', '3' => '3 columnas', '4' => '4 columnas']],
                'gap' => ['type' => 'number', 'label' => __('Espaciado (px)', 'flavor-chat-ia'), 'default' => 20],
            ],
            'render_callback' => [$this, 'render_columns'],
        ]);

        // Contenedor
        $builder->register_component('container', [
            'label'       => __('Contenedor', 'flavor-chat-ia'),
            'description' => __('Contenedor con ancho máximo', 'flavor-chat-ia'),
            'icon'        => 'dashicons-align-center',
            'category'    => 'layout',
            'fields'      => [
                'ancho_max' => ['type' => 'select', 'label' => __('Ancho máximo', 'flavor-chat-ia'), 'options' => ['sm' => 'Pequeño (640px)', 'md' => 'Mediano (768px)', 'lg' => 'Grande (1024px)', 'xl' => 'Extra (1280px)', 'full' => 'Completo']],
                'padding' => ['type' => 'number', 'label' => __('Padding (px)', 'flavor-chat-ia'), 'default' => 20],
            ],
            'render_callback' => [$this, 'render_container'],
        ]);
    }

    // =========================================================================
    // COMPONENTES THEMACLE
    // =========================================================================

    /**
     * Registrar componentes de Themacle
     *
     * @param Flavor_Visual_Builder $builder
     */
    public function registrar_componentes_themacle($builder) {
        if (empty($this->themacle_components)) {
            return;
        }

        foreach ($this->themacle_components as $componente_id => $config) {
            $builder->register_component('themacle-' . $componente_id, [
                'label'       => $config['label'] ?? ucfirst(str_replace('_', ' ', $componente_id)),
                'description' => $config['description'] ?? '',
                'icon'        => $config['icon'] ?? 'dashicons-admin-generic',
                'category'    => 'themacle',
                'fields'      => $config['fields'] ?? [],
                'render_callback' => function($data) use ($componente_id, $config) {
                    return $this->render_themacle_component($componente_id, $config, $data);
                },
            ]);
        }
    }

    /**
     * Renderizar componente Themacle
     *
     * @param string $componente_id ID del componente
     * @param array  $config        Configuración
     * @param array  $data          Datos
     * @return string HTML
     */
    private function render_themacle_component($componente_id, $config, $data) {
        $template = $config['template'] ?? '';

        if (empty($template)) {
            return '<!-- Themacle component: ' . esc_html($componente_id) . ' -->';
        }

        // Buscar template
        $rutas_posibles = [
            get_stylesheet_directory() . '/flavor-templates/components/' . $template . '.php',
            get_template_directory() . '/flavor-templates/components/' . $template . '.php',
            FLAVOR_CHAT_IA_PATH . 'templates/components/' . $template . '.php',
        ];

        foreach ($rutas_posibles as $ruta) {
            if (file_exists($ruta)) {
                ob_start();
                extract($data);
                include $ruta;
                return ob_get_clean();
            }
        }

        return '<!-- Template not found: ' . esc_html($template) . ' -->';
    }

    // =========================================================================
    // CAMPOS PARA SECCIONES
    // =========================================================================

    private function get_campos_hero() {
        return [
            'titulo' => ['type' => 'text', 'label' => __('Título', 'flavor-chat-ia')],
            'subtitulo' => ['type' => 'text', 'label' => __('Subtítulo', 'flavor-chat-ia')],
            'descripcion' => ['type' => 'textarea', 'label' => __('Descripción', 'flavor-chat-ia')],
            'imagen_fondo' => ['type' => 'image', 'label' => __('Imagen de fondo', 'flavor-chat-ia')],
            'video_fondo' => ['type' => 'url', 'label' => __('Video de fondo (URL)', 'flavor-chat-ia')],
            'overlay_color' => ['type' => 'color', 'label' => __('Color overlay', 'flavor-chat-ia'), 'default' => 'rgba(0,0,0,0.5)'],
            'boton_texto' => ['type' => 'text', 'label' => __('Texto del botón', 'flavor-chat-ia')],
            'boton_url' => ['type' => 'url', 'label' => __('URL del botón', 'flavor-chat-ia')],
            'boton_secundario_texto' => ['type' => 'text', 'label' => __('Texto botón secundario', 'flavor-chat-ia')],
            'boton_secundario_url' => ['type' => 'url', 'label' => __('URL botón secundario', 'flavor-chat-ia')],
            'altura' => ['type' => 'select', 'label' => __('Altura', 'flavor-chat-ia'), 'options' => ['auto' => 'Auto', 'screen' => 'Pantalla completa', '75vh' => '75%', '50vh' => '50%'], 'default' => 'screen'],
            'alineacion' => ['type' => 'select', 'label' => __('Alineación', 'flavor-chat-ia'), 'options' => ['left' => 'Izquierda', 'center' => 'Centro', 'right' => 'Derecha'], 'default' => 'center'],
        ];
    }

    private function get_campos_features() {
        return [
            'titulo_seccion' => ['type' => 'text', 'label' => __('Título de la sección', 'flavor-chat-ia')],
            'subtitulo_seccion' => ['type' => 'text', 'label' => __('Subtítulo', 'flavor-chat-ia')],
            'columnas' => ['type' => 'select', 'label' => __('Columnas', 'flavor-chat-ia'), 'options' => ['2' => '2', '3' => '3', '4' => '4'], 'default' => '3'],
            'items' => ['type' => 'repeater', 'label' => __('Características', 'flavor-chat-ia'), 'fields' => [
                'icono' => ['type' => 'icon', 'label' => __('Icono', 'flavor-chat-ia')],
                'titulo' => ['type' => 'text', 'label' => __('Título', 'flavor-chat-ia')],
                'descripcion' => ['type' => 'textarea', 'label' => __('Descripción', 'flavor-chat-ia')],
                'enlace' => ['type' => 'url', 'label' => __('Enlace', 'flavor-chat-ia')],
            ]],
        ];
    }

    private function get_campos_testimonios() {
        return [
            'titulo_seccion' => ['type' => 'text', 'label' => __('Título', 'flavor-chat-ia')],
            'items' => ['type' => 'repeater', 'label' => __('Testimonios', 'flavor-chat-ia'), 'fields' => [
                'texto' => ['type' => 'textarea', 'label' => __('Testimonio', 'flavor-chat-ia')],
                'autor' => ['type' => 'text', 'label' => __('Nombre', 'flavor-chat-ia')],
                'cargo' => ['type' => 'text', 'label' => __('Cargo / Empresa', 'flavor-chat-ia')],
                'foto' => ['type' => 'image', 'label' => __('Foto', 'flavor-chat-ia')],
                'rating' => ['type' => 'select', 'label' => __('Valoración', 'flavor-chat-ia'), 'options' => ['5' => '5 estrellas', '4' => '4 estrellas', '3' => '3 estrellas']],
            ]],
            'autoplay' => ['type' => 'toggle', 'label' => __('Autoplay (carousel)', 'flavor-chat-ia'), 'default' => true],
        ];
    }

    private function get_campos_pricing() {
        return [
            'titulo_seccion' => ['type' => 'text', 'label' => __('Título', 'flavor-chat-ia')],
            'subtitulo_seccion' => ['type' => 'text', 'label' => __('Subtítulo', 'flavor-chat-ia')],
            'mostrar_toggle' => ['type' => 'toggle', 'label' => __('Mostrar toggle mensual/anual', 'flavor-chat-ia')],
            'items' => ['type' => 'repeater', 'label' => __('Planes', 'flavor-chat-ia'), 'fields' => [
                'nombre' => ['type' => 'text', 'label' => __('Nombre del plan', 'flavor-chat-ia')],
                'precio' => ['type' => 'text', 'label' => __('Precio', 'flavor-chat-ia')],
                'precio_anual' => ['type' => 'text', 'label' => __('Precio anual', 'flavor-chat-ia')],
                'periodo' => ['type' => 'text', 'label' => __('Período', 'flavor-chat-ia'), 'default' => '/mes'],
                'descripcion' => ['type' => 'text', 'label' => __('Descripción', 'flavor-chat-ia')],
                'caracteristicas' => ['type' => 'textarea', 'label' => __('Características (una por línea)', 'flavor-chat-ia')],
                'destacado' => ['type' => 'toggle', 'label' => __('Destacado', 'flavor-chat-ia')],
                'boton_texto' => ['type' => 'text', 'label' => __('Texto del botón', 'flavor-chat-ia')],
                'boton_url' => ['type' => 'url', 'label' => __('URL del botón', 'flavor-chat-ia')],
            ]],
        ];
    }

    private function get_campos_cta() {
        return [
            'titulo' => ['type' => 'text', 'label' => __('Título', 'flavor-chat-ia')],
            'descripcion' => ['type' => 'textarea', 'label' => __('Descripción', 'flavor-chat-ia')],
            'boton_texto' => ['type' => 'text', 'label' => __('Texto del botón', 'flavor-chat-ia')],
            'boton_url' => ['type' => 'url', 'label' => __('URL del botón', 'flavor-chat-ia')],
            'boton_secundario_texto' => ['type' => 'text', 'label' => __('Botón secundario', 'flavor-chat-ia')],
            'boton_secundario_url' => ['type' => 'url', 'label' => __('URL secundaria', 'flavor-chat-ia')],
            'imagen_fondo' => ['type' => 'image', 'label' => __('Imagen de fondo', 'flavor-chat-ia')],
            'color_fondo' => ['type' => 'color', 'label' => __('Color de fondo', 'flavor-chat-ia')],
        ];
    }

    private function get_campos_faq() {
        return [
            'titulo_seccion' => ['type' => 'text', 'label' => __('Título', 'flavor-chat-ia')],
            'subtitulo_seccion' => ['type' => 'text', 'label' => __('Subtítulo', 'flavor-chat-ia')],
            'items' => ['type' => 'repeater', 'label' => __('Preguntas', 'flavor-chat-ia'), 'fields' => [
                'pregunta' => ['type' => 'text', 'label' => __('Pregunta', 'flavor-chat-ia')],
                'respuesta' => ['type' => 'wysiwyg', 'label' => __('Respuesta', 'flavor-chat-ia')],
                'categoria' => ['type' => 'text', 'label' => __('Categoría', 'flavor-chat-ia')],
            ]],
            'abrir_primero' => ['type' => 'toggle', 'label' => __('Abrir primera pregunta', 'flavor-chat-ia'), 'default' => true],
        ];
    }

    private function get_campos_contacto() {
        return [
            'titulo_seccion' => ['type' => 'text', 'label' => __('Título', 'flavor-chat-ia')],
            'descripcion' => ['type' => 'textarea', 'label' => __('Descripción', 'flavor-chat-ia')],
            'email' => ['type' => 'email', 'label' => __('Email de contacto', 'flavor-chat-ia')],
            'telefono' => ['type' => 'text', 'label' => __('Teléfono', 'flavor-chat-ia')],
            'direccion' => ['type' => 'textarea', 'label' => __('Dirección', 'flavor-chat-ia')],
            'horario' => ['type' => 'textarea', 'label' => __('Horario', 'flavor-chat-ia')],
            'mapa_lat' => ['type' => 'text', 'label' => __('Latitud del mapa', 'flavor-chat-ia')],
            'mapa_lng' => ['type' => 'text', 'label' => __('Longitud del mapa', 'flavor-chat-ia')],
            'form_shortcode' => ['type' => 'text', 'label' => __('Shortcode del formulario', 'flavor-chat-ia')],
            'redes_sociales' => ['type' => 'repeater', 'label' => __('Redes sociales', 'flavor-chat-ia'), 'fields' => [
                'red' => ['type' => 'select', 'label' => __('Red', 'flavor-chat-ia'), 'options' => ['facebook' => 'Facebook', 'twitter' => 'Twitter/X', 'instagram' => 'Instagram', 'linkedin' => 'LinkedIn', 'youtube' => 'YouTube']],
                'url' => ['type' => 'url', 'label' => __('URL', 'flavor-chat-ia')],
            ]],
        ];
    }

    private function get_campos_galeria() {
        return [
            'titulo_seccion' => ['type' => 'text', 'label' => __('Título', 'flavor-chat-ia')],
            'imagenes' => ['type' => 'gallery', 'label' => __('Imágenes', 'flavor-chat-ia')],
            'columnas' => ['type' => 'select', 'label' => __('Columnas', 'flavor-chat-ia'), 'options' => ['2' => '2', '3' => '3', '4' => '4', '5' => '5'], 'default' => '4'],
            'gap' => ['type' => 'number', 'label' => __('Espaciado (px)', 'flavor-chat-ia'), 'default' => 10],
            'lightbox' => ['type' => 'toggle', 'label' => __('Activar lightbox', 'flavor-chat-ia'), 'default' => true],
        ];
    }

    private function get_campos_stats() {
        return [
            'titulo_seccion' => ['type' => 'text', 'label' => __('Título', 'flavor-chat-ia')],
            'items' => ['type' => 'repeater', 'label' => __('Estadísticas', 'flavor-chat-ia'), 'fields' => [
                'numero' => ['type' => 'text', 'label' => __('Número', 'flavor-chat-ia')],
                'sufijo' => ['type' => 'text', 'label' => __('Sufijo (+, %, etc)', 'flavor-chat-ia')],
                'label' => ['type' => 'text', 'label' => __('Etiqueta', 'flavor-chat-ia')],
                'icono' => ['type' => 'icon', 'label' => __('Icono', 'flavor-chat-ia')],
            ]],
            'animar' => ['type' => 'toggle', 'label' => __('Animar números', 'flavor-chat-ia'), 'default' => true],
            'color_fondo' => ['type' => 'color', 'label' => __('Color de fondo', 'flavor-chat-ia')],
        ];
    }

    private function get_campos_equipo() {
        return [
            'titulo_seccion' => ['type' => 'text', 'label' => __('Título', 'flavor-chat-ia')],
            'subtitulo_seccion' => ['type' => 'text', 'label' => __('Subtítulo', 'flavor-chat-ia')],
            'columnas' => ['type' => 'select', 'label' => __('Columnas', 'flavor-chat-ia'), 'options' => ['3' => '3', '4' => '4', '5' => '5'], 'default' => '4'],
            'items' => ['type' => 'repeater', 'label' => __('Miembros', 'flavor-chat-ia'), 'fields' => [
                'foto' => ['type' => 'image', 'label' => __('Foto', 'flavor-chat-ia')],
                'nombre' => ['type' => 'text', 'label' => __('Nombre', 'flavor-chat-ia')],
                'cargo' => ['type' => 'text', 'label' => __('Cargo', 'flavor-chat-ia')],
                'bio' => ['type' => 'textarea', 'label' => __('Biografía', 'flavor-chat-ia')],
                'linkedin' => ['type' => 'url', 'label' => __('LinkedIn', 'flavor-chat-ia')],
                'twitter' => ['type' => 'url', 'label' => __('Twitter/X', 'flavor-chat-ia')],
                'email' => ['type' => 'email', 'label' => __('Email', 'flavor-chat-ia')],
            ]],
        ];
    }

    private function get_campos_logos() {
        return [
            'titulo_seccion' => ['type' => 'text', 'label' => __('Título', 'flavor-chat-ia')],
            'logos' => ['type' => 'gallery', 'label' => __('Logos', 'flavor-chat-ia')],
            'columnas' => ['type' => 'select', 'label' => __('Columnas', 'flavor-chat-ia'), 'options' => ['4' => '4', '5' => '5', '6' => '6', '8' => '8'], 'default' => '6'],
            'escala_grises' => ['type' => 'toggle', 'label' => __('Escala de grises', 'flavor-chat-ia'), 'default' => true],
            'carousel' => ['type' => 'toggle', 'label' => __('Carousel automático', 'flavor-chat-ia')],
        ];
    }

    private function get_campos_video() {
        return [
            'titulo_seccion' => ['type' => 'text', 'label' => __('Título', 'flavor-chat-ia')],
            'descripcion' => ['type' => 'textarea', 'label' => __('Descripción', 'flavor-chat-ia')],
            'video_url' => ['type' => 'url', 'label' => __('URL del video (YouTube/Vimeo)', 'flavor-chat-ia')],
            'video_archivo' => ['type' => 'file', 'label' => __('Video archivo (MP4)', 'flavor-chat-ia')],
            'imagen_preview' => ['type' => 'image', 'label' => __('Imagen de preview', 'flavor-chat-ia')],
            'autoplay' => ['type' => 'toggle', 'label' => __('Autoplay', 'flavor-chat-ia')],
            'loop' => ['type' => 'toggle', 'label' => __('Loop', 'flavor-chat-ia')],
            'muted' => ['type' => 'toggle', 'label' => __('Sin sonido', 'flavor-chat-ia')],
        ];
    }

    private function get_campos_texto_media() {
        return [
            'titulo' => ['type' => 'text', 'label' => __('Título', 'flavor-chat-ia')],
            'subtitulo' => ['type' => 'text', 'label' => __('Subtítulo', 'flavor-chat-ia')],
            'contenido' => ['type' => 'wysiwyg', 'label' => __('Contenido', 'flavor-chat-ia')],
            'imagen' => ['type' => 'image', 'label' => __('Imagen', 'flavor-chat-ia')],
            'video_url' => ['type' => 'url', 'label' => __('URL de video (alternativa)', 'flavor-chat-ia')],
            'invertir' => ['type' => 'toggle', 'label' => __('Imagen a la derecha', 'flavor-chat-ia')],
            'boton_texto' => ['type' => 'text', 'label' => __('Texto del botón', 'flavor-chat-ia')],
            'boton_url' => ['type' => 'url', 'label' => __('URL del botón', 'flavor-chat-ia')],
        ];
    }

    private function get_campos_separador() {
        return [
            'tipo' => ['type' => 'select', 'label' => __('Tipo', 'flavor-chat-ia'), 'options' => [
                'line' => __('Línea', 'flavor-chat-ia'),
                'wave' => __('Ola', 'flavor-chat-ia'),
                'angle' => __('Ángulo', 'flavor-chat-ia'),
                'curve' => __('Curva', 'flavor-chat-ia'),
                'triangle' => __('Triángulo', 'flavor-chat-ia'),
            ]],
            'color' => ['type' => 'color', 'label' => __('Color', 'flavor-chat-ia'), 'default' => '#ffffff'],
            'altura' => ['type' => 'number', 'label' => __('Altura (px)', 'flavor-chat-ia'), 'default' => 100],
            'invertir' => ['type' => 'toggle', 'label' => __('Invertir', 'flavor-chat-ia')],
        ];
    }

    private function get_campos_newsletter() {
        return [
            'titulo' => ['type' => 'text', 'label' => __('Título', 'flavor-chat-ia')],
            'descripcion' => ['type' => 'textarea', 'label' => __('Descripción', 'flavor-chat-ia')],
            'placeholder' => ['type' => 'text', 'label' => __('Placeholder', 'flavor-chat-ia'), 'default' => 'Tu email'],
            'boton_texto' => ['type' => 'text', 'label' => __('Texto del botón', 'flavor-chat-ia'), 'default' => 'Suscribirse'],
            'accion_form' => ['type' => 'url', 'label' => __('URL del formulario (Mailchimp, etc)', 'flavor-chat-ia')],
            'color_fondo' => ['type' => 'color', 'label' => __('Color de fondo', 'flavor-chat-ia')],
        ];
    }

    private function get_campos_portfolio() {
        return [
            'titulo_seccion' => ['type' => 'text', 'label' => __('Título', 'flavor-chat-ia')],
            'columnas' => ['type' => 'select', 'label' => __('Columnas', 'flavor-chat-ia'), 'options' => ['2' => '2', '3' => '3', '4' => '4'], 'default' => '3'],
            'mostrar_filtros' => ['type' => 'toggle', 'label' => __('Mostrar filtros', 'flavor-chat-ia')],
            'items' => ['type' => 'repeater', 'label' => __('Proyectos', 'flavor-chat-ia'), 'fields' => [
                'imagen' => ['type' => 'image', 'label' => __('Imagen', 'flavor-chat-ia')],
                'titulo' => ['type' => 'text', 'label' => __('Título', 'flavor-chat-ia')],
                'categoria' => ['type' => 'text', 'label' => __('Categoría', 'flavor-chat-ia')],
                'descripcion' => ['type' => 'textarea', 'label' => __('Descripción', 'flavor-chat-ia')],
                'enlace' => ['type' => 'url', 'label' => __('Enlace', 'flavor-chat-ia')],
            ]],
        ];
    }

    private function get_campos_blog() {
        return [
            'titulo_seccion' => ['type' => 'text', 'label' => __('Título', 'flavor-chat-ia')],
            'cantidad' => ['type' => 'number', 'label' => __('Cantidad de posts', 'flavor-chat-ia'), 'default' => 3],
            'columnas' => ['type' => 'select', 'label' => __('Columnas', 'flavor-chat-ia'), 'options' => ['2' => '2', '3' => '3', '4' => '4'], 'default' => '3'],
            'categoria' => ['type' => 'select', 'label' => __('Categoría', 'flavor-chat-ia'), 'options' => []], // Se llena dinámicamente
            'mostrar_fecha' => ['type' => 'toggle', 'label' => __('Mostrar fecha', 'flavor-chat-ia'), 'default' => true],
            'mostrar_autor' => ['type' => 'toggle', 'label' => __('Mostrar autor', 'flavor-chat-ia'), 'default' => true],
            'mostrar_extracto' => ['type' => 'toggle', 'label' => __('Mostrar extracto', 'flavor-chat-ia'), 'default' => true],
        ];
    }

    private function get_campos_timeline() {
        return [
            'titulo_seccion' => ['type' => 'text', 'label' => __('Título', 'flavor-chat-ia')],
            'items' => ['type' => 'repeater', 'label' => __('Eventos', 'flavor-chat-ia'), 'fields' => [
                'fecha' => ['type' => 'text', 'label' => __('Fecha', 'flavor-chat-ia')],
                'titulo' => ['type' => 'text', 'label' => __('Título', 'flavor-chat-ia')],
                'descripcion' => ['type' => 'textarea', 'label' => __('Descripción', 'flavor-chat-ia')],
                'icono' => ['type' => 'icon', 'label' => __('Icono', 'flavor-chat-ia')],
                'imagen' => ['type' => 'image', 'label' => __('Imagen', 'flavor-chat-ia')],
            ]],
            'color_linea' => ['type' => 'color', 'label' => __('Color de línea', 'flavor-chat-ia')],
        ];
    }

    // =========================================================================
    // MÉTODOS DE RENDERIZADO
    // =========================================================================

    /**
     * Render Hero Section
     */
    public function render_hero($data, $variant = 'fullscreen') {
        $titulo = $data['titulo'] ?? '';
        $subtitulo = $data['subtitulo'] ?? '';
        $descripcion = $data['descripcion'] ?? '';
        $imagen_fondo = $data['imagen_fondo'] ?? '';
        $video_fondo = $data['video_fondo'] ?? '';
        $overlay = $data['overlay_color'] ?? 'rgba(0,0,0,0.5)';
        $boton_texto = $data['boton_texto'] ?? '';
        $boton_url = $data['boton_url'] ?? '#';
        $boton2_texto = $data['boton_secundario_texto'] ?? '';
        $boton2_url = $data['boton_secundario_url'] ?? '#';
        $altura = $data['altura'] ?? 'screen';
        $alineacion = $data['alineacion'] ?? 'center';

        $imagen_url = $imagen_fondo ? wp_get_attachment_image_url($imagen_fondo, 'full') : '';

        $clase_altura = [
            'screen' => 'min-h-screen',
            '75vh' => 'min-h-[75vh]',
            '50vh' => 'min-h-[50vh]',
            'auto' => 'py-20',
        ][$altura] ?? 'min-h-screen';

        $clase_alineacion = [
            'left' => 'text-left items-start',
            'center' => 'text-center items-center',
            'right' => 'text-right items-end',
        ][$alineacion] ?? 'text-center items-center';

        ob_start();
        ?>
        <section class="fvb-section fvb-hero fvb-hero--<?php echo esc_attr($variant); ?> <?php echo esc_attr($clase_altura); ?> relative flex <?php echo esc_attr($clase_alineacion); ?> justify-center"
                 style="<?php if ($imagen_url) : ?>background-image: url('<?php echo esc_url($imagen_url); ?>'); background-size: cover; background-position: center;<?php endif; ?>">

            <?php if ($video_fondo) : ?>
                <video class="absolute inset-0 w-full h-full object-cover z-0" autoplay muted loop playsinline>
                    <source src="<?php echo esc_url($video_fondo); ?>" type="video/mp4">
                </video>
            <?php endif; ?>

            <div class="absolute inset-0 z-10" style="background-color: <?php echo esc_attr($overlay); ?>;"></div>

            <div class="fvb-hero__content relative z-20 max-w-4xl mx-auto px-4 py-12">
                <?php if ($subtitulo) : ?>
                    <p class="fvb-hero__subtitle text-lg md:text-xl text-white/80 mb-4 font-medium"><?php echo esc_html($subtitulo); ?></p>
                <?php endif; ?>

                <?php if ($titulo) : ?>
                    <h1 class="fvb-hero__title text-4xl md:text-5xl lg:text-6xl font-bold text-white mb-6"><?php echo wp_kses_post($titulo); ?></h1>
                <?php endif; ?>

                <?php if ($descripcion) : ?>
                    <p class="fvb-hero__description text-lg md:text-xl text-white/90 mb-8 max-w-2xl <?php echo $alineacion === 'center' ? 'mx-auto' : ''; ?>"><?php echo wp_kses_post($descripcion); ?></p>
                <?php endif; ?>

                <?php if ($boton_texto || $boton2_texto) : ?>
                    <div class="fvb-hero__buttons flex flex-wrap gap-4 <?php echo $alineacion === 'center' ? 'justify-center' : ($alineacion === 'right' ? 'justify-end' : 'justify-start'); ?>">
                        <?php if ($boton_texto) : ?>
                            <a href="<?php echo esc_url($boton_url); ?>" class="fvb-btn fvb-btn--primary px-8 py-3 bg-white text-gray-900 rounded-lg font-semibold hover:bg-gray-100 transition">
                                <?php echo esc_html($boton_texto); ?>
                            </a>
                        <?php endif; ?>

                        <?php if ($boton2_texto) : ?>
                            <a href="<?php echo esc_url($boton2_url); ?>" class="fvb-btn fvb-btn--secondary px-8 py-3 border-2 border-white text-white rounded-lg font-semibold hover:bg-white/10 transition">
                                <?php echo esc_html($boton2_texto); ?>
                            </a>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>
            </div>
        </section>
        <?php
        return ob_get_clean();
    }

    /**
     * Render Features Section
     */
    public function render_features($data, $variant = 'grid') {
        $titulo = $data['titulo_seccion'] ?? '';
        $subtitulo = $data['subtitulo_seccion'] ?? '';
        $items = $data['items'] ?? [];
        $columnas = $data['columnas'] ?? '3';

        ob_start();
        ?>
        <section class="fvb-section fvb-features fvb-features--<?php echo esc_attr($variant); ?> py-16 md:py-24">
            <div class="fvb-container max-w-7xl mx-auto px-4">
                <?php if ($titulo || $subtitulo) : ?>
                    <div class="fvb-section__header text-center mb-12">
                        <?php if ($subtitulo) : ?>
                            <p class="text-primary-600 font-semibold mb-2"><?php echo esc_html($subtitulo); ?></p>
                        <?php endif; ?>
                        <?php if ($titulo) : ?>
                            <h2 class="text-3xl md:text-4xl font-bold text-gray-900"><?php echo wp_kses_post($titulo); ?></h2>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>

                <?php if (!empty($items)) : ?>
                    <div class="fvb-features__grid grid gap-8 md:grid-cols-<?php echo esc_attr($columnas); ?>">
                        <?php foreach ($items as $item) : ?>
                            <div class="fvb-feature-card p-6 bg-white rounded-xl shadow-sm hover:shadow-lg transition">
                                <?php if (!empty($item['icono'])) : ?>
                                    <div class="fvb-feature-card__icon w-12 h-12 bg-primary-100 text-primary-600 rounded-lg flex items-center justify-center mb-4">
                                        <span class="dashicons <?php echo esc_attr($item['icono']); ?> text-2xl"></span>
                                    </div>
                                <?php endif; ?>

                                <?php if (!empty($item['titulo'])) : ?>
                                    <h3 class="fvb-feature-card__title text-xl font-semibold text-gray-900 mb-2"><?php echo esc_html($item['titulo']); ?></h3>
                                <?php endif; ?>

                                <?php if (!empty($item['descripcion'])) : ?>
                                    <p class="fvb-feature-card__description text-gray-600"><?php echo wp_kses_post($item['descripcion']); ?></p>
                                <?php endif; ?>

                                <?php if (!empty($item['enlace'])) : ?>
                                    <a href="<?php echo esc_url($item['enlace']); ?>" class="fvb-feature-card__link inline-flex items-center mt-4 text-primary-600 font-medium hover:underline">
                                        <?php esc_html_e('Saber más', 'flavor-chat-ia'); ?>
                                        <span class="dashicons dashicons-arrow-right-alt2 ml-1"></span>
                                    </a>
                                <?php endif; ?>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </section>
        <?php
        return ob_get_clean();
    }

    /**
     * Render CTA Section
     */
    public function render_cta($data, $variant = 'simple') {
        $titulo = $data['titulo'] ?? '';
        $descripcion = $data['descripcion'] ?? '';
        $boton_texto = $data['boton_texto'] ?? '';
        $boton_url = $data['boton_url'] ?? '#';
        $boton2_texto = $data['boton_secundario_texto'] ?? '';
        $boton2_url = $data['boton_secundario_url'] ?? '#';
        $color_fondo = $data['color_fondo'] ?? '';
        $imagen_fondo = $data['imagen_fondo'] ?? '';

        $imagen_url = $imagen_fondo ? wp_get_attachment_image_url($imagen_fondo, 'full') : '';

        ob_start();
        ?>
        <section class="fvb-section fvb-cta fvb-cta--<?php echo esc_attr($variant); ?> py-16 md:py-24 relative"
                 style="<?php if ($color_fondo) : ?>background-color: <?php echo esc_attr($color_fondo); ?>;<?php endif; ?> <?php if ($imagen_url) : ?>background-image: url('<?php echo esc_url($imagen_url); ?>'); background-size: cover; background-position: center;<?php endif; ?>">

            <?php if ($imagen_url) : ?>
                <div class="absolute inset-0 bg-black/50"></div>
            <?php endif; ?>

            <div class="fvb-container max-w-4xl mx-auto px-4 text-center relative z-10">
                <?php if ($titulo) : ?>
                    <h2 class="text-3xl md:text-4xl font-bold <?php echo $imagen_url ? 'text-white' : 'text-gray-900'; ?> mb-4"><?php echo wp_kses_post($titulo); ?></h2>
                <?php endif; ?>

                <?php if ($descripcion) : ?>
                    <p class="text-lg <?php echo $imagen_url ? 'text-white/90' : 'text-gray-600'; ?> mb-8"><?php echo wp_kses_post($descripcion); ?></p>
                <?php endif; ?>

                <?php if ($boton_texto || $boton2_texto) : ?>
                    <div class="fvb-cta__buttons flex flex-wrap gap-4 justify-center">
                        <?php if ($boton_texto) : ?>
                            <a href="<?php echo esc_url($boton_url); ?>" class="fvb-btn fvb-btn--primary px-8 py-3 bg-primary-600 text-white rounded-lg font-semibold hover:bg-primary-700 transition">
                                <?php echo esc_html($boton_texto); ?>
                            </a>
                        <?php endif; ?>

                        <?php if ($boton2_texto) : ?>
                            <a href="<?php echo esc_url($boton2_url); ?>" class="fvb-btn fvb-btn--secondary px-8 py-3 border-2 <?php echo $imagen_url ? 'border-white text-white hover:bg-white/10' : 'border-gray-300 text-gray-700 hover:bg-gray-50'; ?> rounded-lg font-semibold transition">
                                <?php echo esc_html($boton2_texto); ?>
                            </a>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>
            </div>
        </section>
        <?php
        return ob_get_clean();
    }

    /**
     * Render FAQ Section
     */
    public function render_faq($data, $variant = 'accordion') {
        $titulo = $data['titulo_seccion'] ?? '';
        $subtitulo = $data['subtitulo_seccion'] ?? '';
        $items = $data['items'] ?? [];
        $abrir_primero = $data['abrir_primero'] ?? true;

        ob_start();
        ?>
        <section class="fvb-section fvb-faq fvb-faq--<?php echo esc_attr($variant); ?> py-16 md:py-24 bg-gray-50">
            <div class="fvb-container max-w-4xl mx-auto px-4">
                <?php if ($titulo || $subtitulo) : ?>
                    <div class="fvb-section__header text-center mb-12">
                        <?php if ($subtitulo) : ?>
                            <p class="text-primary-600 font-semibold mb-2"><?php echo esc_html($subtitulo); ?></p>
                        <?php endif; ?>
                        <?php if ($titulo) : ?>
                            <h2 class="text-3xl md:text-4xl font-bold text-gray-900"><?php echo wp_kses_post($titulo); ?></h2>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>

                <?php if (!empty($items)) : ?>
                    <div class="fvb-faq__list space-y-4" x-data="{open: <?php echo $abrir_primero ? '0' : 'null'; ?>}">
                        <?php foreach ($items as $indice => $item) : ?>
                            <div class="fvb-faq__item bg-white rounded-lg shadow-sm">
                                <button @click="open = open === <?php echo $indice; ?> ? null : <?php echo $indice; ?>"
                                        class="fvb-faq__question w-full flex justify-between items-center p-6 text-left font-semibold text-gray-900 hover:text-primary-600">
                                    <span><?php echo esc_html($item['pregunta'] ?? ''); ?></span>
                                    <span class="dashicons" :class="open === <?php echo $indice; ?> ? 'dashicons-minus' : 'dashicons-plus'"></span>
                                </button>
                                <div x-show="open === <?php echo $indice; ?>"
                                     x-collapse
                                     class="fvb-faq__answer px-6 pb-6 text-gray-600">
                                    <?php echo wp_kses_post($item['respuesta'] ?? ''); ?>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </section>
        <?php
        return ob_get_clean();
    }

    /**
     * Render componentes básicos
     */
    public function render_heading($data) {
        $texto = $data['texto'] ?? '';
        $nivel = $data['nivel'] ?? 'h2';
        $alineacion = $data['alineacion'] ?? 'left';

        $clase_alineacion = "text-{$alineacion}";

        return sprintf(
            '<%1$s class="fvb-heading %2$s">%3$s</%1$s>',
            esc_attr($nivel),
            esc_attr($clase_alineacion),
            wp_kses_post($texto)
        );
    }

    public function render_text($data) {
        $contenido = $data['contenido'] ?? '';
        return '<div class="fvb-text prose max-w-none">' . wp_kses_post($contenido) . '</div>';
    }

    public function render_image($data) {
        $imagen_id = $data['imagen_id'] ?? 0;
        $alt = $data['alt'] ?? '';
        $tamano = $data['tamaño'] ?? 'large';
        $enlace = $data['enlace'] ?? '';

        if (!$imagen_id) {
            return '';
        }

        $imagen = wp_get_attachment_image($imagen_id, $tamano, false, ['alt' => $alt, 'class' => 'fvb-image rounded-lg']);

        if ($enlace) {
            return sprintf('<a href="%s" class="fvb-image-link">%s</a>', esc_url($enlace), $imagen);
        }

        return '<div class="fvb-image-wrapper">' . $imagen . '</div>';
    }

    public function render_button($data) {
        $texto = $data['texto'] ?? __('Botón', 'flavor-chat-ia');
        $enlace = $data['enlace'] ?? '#';
        $estilo = $data['estilo'] ?? 'primary';
        $tamano = $data['tamaño'] ?? 'md';
        $nueva_ventana = $data['nueva_ventana'] ?? false;

        $clases_estilo = [
            'primary' => 'bg-primary-600 text-white hover:bg-primary-700',
            'secondary' => 'bg-gray-200 text-gray-800 hover:bg-gray-300',
            'outline' => 'border-2 border-primary-600 text-primary-600 hover:bg-primary-50',
            'ghost' => 'text-primary-600 hover:bg-primary-50',
        ];

        $clases_tamano = [
            'sm' => 'px-4 py-2 text-sm',
            'md' => 'px-6 py-3',
            'lg' => 'px-8 py-4 text-lg',
        ];

        $target = $nueva_ventana ? ' target="_blank" rel="noopener noreferrer"' : '';

        return sprintf(
            '<a href="%s" class="fvb-button inline-flex items-center justify-center rounded-lg font-semibold transition %s %s"%s>%s</a>',
            esc_url($enlace),
            $clases_estilo[$estilo] ?? $clases_estilo['primary'],
            $clases_tamano[$tamano] ?? $clases_tamano['md'],
            $target,
            esc_html($texto)
        );
    }

    public function render_spacer($data) {
        $altura = $data['altura'] ?? 50;
        return sprintf('<div class="fvb-spacer" style="height: %dpx;"></div>', intval($altura));
    }

    public function render_divider($data) {
        $estilo = $data['estilo'] ?? 'solid';
        $ancho = $data['ancho'] ?? 'full';
        $color = $data['color'] ?? '#e5e7eb';

        $clase_ancho = [
            'full' => 'w-full',
            '75' => 'w-3/4 mx-auto',
            '50' => 'w-1/2 mx-auto',
            '25' => 'w-1/4 mx-auto',
        ][$ancho] ?? 'w-full';

        return sprintf(
            '<hr class="fvb-divider %s border-t-2" style="border-style: %s; border-color: %s;">',
            esc_attr($clase_ancho),
            esc_attr($estilo),
            esc_attr($color)
        );
    }

    public function render_html($data) {
        $codigo = $data['codigo'] ?? '';
        return '<div class="fvb-html">' . $codigo . '</div>';
    }

    public function render_shortcode($data) {
        $shortcode = $data['shortcode'] ?? '';
        return '<div class="fvb-shortcode">' . do_shortcode($shortcode) . '</div>';
    }

    public function render_icon($data) {
        $icono = $data['icono'] ?? 'dashicons-star-filled';
        $tamano = $data['tamaño'] ?? 48;
        $color = $data['color'] ?? 'currentColor';

        return sprintf(
            '<span class="fvb-icon dashicons %s" style="font-size: %dpx; width: %dpx; height: %dpx; color: %s;"></span>',
            esc_attr($icono),
            intval($tamano),
            intval($tamano),
            intval($tamano),
            esc_attr($color)
        );
    }

    // Métodos stub para secciones restantes (se pueden expandir según necesidad)
    public function render_testimonios($data, $variant) { return $this->render_section_placeholder('testimonios', $data, $variant); }
    public function render_pricing($data, $variant) { return $this->render_section_placeholder('pricing', $data, $variant); }
    public function render_contacto($data, $variant) { return $this->render_section_placeholder('contacto', $data, $variant); }
    public function render_galeria($data, $variant) { return $this->render_section_placeholder('galeria', $data, $variant); }
    public function render_stats($data, $variant) { return $this->render_section_placeholder('stats', $data, $variant); }
    public function render_equipo($data, $variant) { return $this->render_section_placeholder('equipo', $data, $variant); }
    public function render_logos($data, $variant) { return $this->render_section_placeholder('logos', $data, $variant); }
    public function render_video($data, $variant) { return $this->render_section_placeholder('video', $data, $variant); }
    public function render_texto_media($data, $variant) { return $this->render_section_placeholder('texto-media', $data, $variant); }
    public function render_separador($data, $variant) { return $this->render_section_placeholder('separador', $data, $variant); }
    public function render_newsletter($data, $variant) { return $this->render_section_placeholder('newsletter', $data, $variant); }
    public function render_portfolio($data, $variant) { return $this->render_section_placeholder('portfolio', $data, $variant); }
    public function render_blog($data, $variant) { return $this->render_section_placeholder('blog', $data, $variant); }
    public function render_timeline($data, $variant) { return $this->render_section_placeholder('timeline', $data, $variant); }
    public function render_columns($data) { return '<!-- columns -->'; }
    public function render_container($data) { return '<!-- container -->'; }

    /**
     * Placeholder para secciones no implementadas completamente
     */
    private function render_section_placeholder($tipo, $data, $variant) {
        $titulo = $data['titulo_seccion'] ?? $data['titulo'] ?? '';

        ob_start();
        ?>
        <section class="fvb-section fvb-<?php echo esc_attr($tipo); ?> fvb-<?php echo esc_attr($tipo); ?>--<?php echo esc_attr($variant); ?> py-16">
            <div class="fvb-container max-w-7xl mx-auto px-4">
                <?php if ($titulo) : ?>
                    <h2 class="text-3xl font-bold text-center mb-8"><?php echo wp_kses_post($titulo); ?></h2>
                <?php endif; ?>
                <div class="fvb-section__content">
                    <!-- Contenido de <?php echo esc_html($tipo); ?> -->
                </div>
            </div>
        </section>
        <?php
        return ob_get_clean();
    }
}

// Inicializar
add_action('plugins_loaded', function() {
    Flavor_VB_All_Components::get_instance();
}, 15);

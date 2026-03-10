<?php
/**
 * Generador de Apps/Webs con IA
 *
 * Analiza requisitos del usuario y genera estructura de sitio
 * con páginas VBP, módulos y configuración de dashboard.
 *
 * @package Flavor_Chat_IA
 * @since 3.2.0
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class Flavor_App_Generator {

    /**
     * Instancia singleton
     *
     * @var Flavor_App_Generator|null
     */
    private static $instance = null;

    /**
     * Motor de IA
     *
     * @var object
     */
    private $ai_engine;

    /**
     * Mapeo de casos de uso a módulos
     *
     * @var array
     */
    private $casos_uso_modulos = [];

    /**
     * Templates de páginas disponibles
     *
     * @var array
     */
    private $page_templates = [];

    /**
     * Mapeo de tipos de comunidad a temas predefinidos
     *
     * @var array
     */
    private $temas_por_tipo = [];

    /**
     * Temas disponibles con su información
     *
     * @var array
     */
    private $temas_disponibles = [];

    /**
     * Obtener instancia singleton
     *
     * @return Flavor_App_Generator
     */
    public static function get_instance() {
        if ( null === self::$instance ) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Constructor
     */
    private function __construct() {
        $this->setup_casos_uso_modulos();
        $this->setup_page_templates();
        $this->setup_temas_por_tipo();
        $this->init_hooks();
    }

    /**
     * Inicializar hooks
     */
    private function init_hooks() {
        add_action( 'wp_ajax_flavor_app_generator_analyze', [ $this, 'ajax_analyze' ] );
        add_action( 'wp_ajax_flavor_app_generator_generate', [ $this, 'ajax_generate' ] );
        add_action( 'wp_ajax_flavor_app_generator_preview', [ $this, 'ajax_preview' ] );
    }

    /**
     * Configurar mapeo de casos de uso a módulos
     */
    private function setup_casos_uso_modulos() {
        $this->casos_uso_modulos = [
            // Gestión de personas
            'socios' => [
                'keywords' => [ 'socios', 'miembros', 'membresía', 'cuotas', 'asociados', 'afiliados' ],
                'modulo' => 'socios',
                'descripcion' => 'Gestión de socios y cuotas',
                'paginas_sugeridas' => [ 'mis-datos', 'pagar-cuota' ],
            ],
            'empleados' => [
                'keywords' => [ 'empleados', 'trabajadores', 'fichaje', 'horarios', 'turnos', 'nóminas' ],
                'modulo' => 'fichaje-empleados',
                'descripcion' => 'Control de fichaje y horarios',
                'paginas_sugeridas' => [ 'fichar', 'mis-fichajes' ],
            ],

            // Eventos y actividades
            'eventos' => [
                'keywords' => [ 'eventos', 'actividades', 'agenda', 'calendario', 'inscripciones' ],
                'modulo' => 'eventos',
                'descripcion' => 'Gestión de eventos e inscripciones',
                'paginas_sugeridas' => [ 'eventos', 'mis-inscripciones' ],
            ],
            'cursos' => [
                'keywords' => [ 'cursos', 'formación', 'talleres', 'clases', 'aprendizaje' ],
                'modulo' => 'cursos',
                'descripcion' => 'Gestión de cursos y formación',
                'paginas_sugeridas' => [ 'cursos', 'mis-cursos' ],
            ],
            'talleres' => [
                'keywords' => [ 'talleres', 'workshops', 'manualidades', 'creatividad' ],
                'modulo' => 'talleres',
                'descripcion' => 'Organización de talleres',
                'paginas_sugeridas' => [ 'talleres', 'inscribirse' ],
            ],

            // Reservas y espacios
            'reservas' => [
                'keywords' => [ 'reservas', 'reservar', 'disponibilidad', 'booking', 'citas' ],
                'modulo' => 'reservas',
                'descripcion' => 'Sistema de reservas',
                'paginas_sugeridas' => [ 'reservar', 'mis-reservas' ],
            ],
            'espacios' => [
                'keywords' => [ 'espacios', 'salas', 'instalaciones', 'local', 'alquiler' ],
                'modulo' => 'espacios-comunes',
                'descripcion' => 'Gestión de espacios comunes',
                'paginas_sugeridas' => [ 'espacios', 'reservar-espacio' ],
            ],

            // Comunicación
            'foros' => [
                'keywords' => [ 'foros', 'debates', 'discusiones', 'comunidad', 'opiniones' ],
                'modulo' => 'foros',
                'descripcion' => 'Foros de discusión',
                'paginas_sugeridas' => [ 'foros', 'nuevo-tema' ],
            ],
            'radio' => [
                'keywords' => [ 'radio', 'podcast', 'audio', 'emisora', 'programas' ],
                'modulo' => 'radio',
                'descripcion' => 'Radio comunitaria',
                'paginas_sugeridas' => [ 'radio', 'programacion' ],
            ],

            // Economía
            'marketplace' => [
                'keywords' => [ 'marketplace', 'compraventa', 'anuncios', 'segunda mano', 'clasificados' ],
                'modulo' => 'marketplace',
                'descripcion' => 'Mercadillo de compraventa',
                'paginas_sugeridas' => [ 'anuncios', 'publicar-anuncio' ],
            ],
            'banco_tiempo' => [
                'keywords' => [ 'banco tiempo', 'intercambio', 'servicios', 'trueque', 'ayuda mutua' ],
                'modulo' => 'banco-tiempo',
                'descripcion' => 'Banco de tiempo',
                'paginas_sugeridas' => [ 'servicios', 'ofrecer-servicio' ],
            ],

            // Participación
            'participacion' => [
                'keywords' => [ 'participación', 'votaciones', 'propuestas', 'democracia', 'decidir' ],
                'modulo' => 'participacion',
                'descripcion' => 'Participación ciudadana',
                'paginas_sugeridas' => [ 'propuestas', 'votaciones' ],
            ],
            'presupuestos' => [
                'keywords' => [ 'presupuestos participativos', 'presupuesto', 'asignación', 'proyectos' ],
                'modulo' => 'presupuestos-participativos',
                'descripcion' => 'Presupuestos participativos',
                'paginas_sugeridas' => [ 'presupuestos', 'votar-proyecto' ],
            ],

            // Sostenibilidad
            'huertos' => [
                'keywords' => [ 'huertos', 'huerto urbano', 'parcelas', 'agricultura', 'cultivo' ],
                'modulo' => 'huertos-urbanos',
                'descripcion' => 'Gestión de huertos urbanos',
                'paginas_sugeridas' => [ 'huertos', 'mi-parcela' ],
            ],
            'compostaje' => [
                'keywords' => [ 'compostaje', 'compost', 'residuos', 'orgánicos', 'reciclaje' ],
                'modulo' => 'compostaje',
                'descripcion' => 'Sistema de compostaje',
                'paginas_sugeridas' => [ 'compostaje', 'aportar' ],
            ],
            'bicicletas' => [
                'keywords' => [ 'bicicletas', 'bici', 'préstamo', 'movilidad', 'compartir' ],
                'modulo' => 'bicicletas-compartidas',
                'descripcion' => 'Bicicletas compartidas',
                'paginas_sugeridas' => [ 'bicicletas', 'reservar-bici' ],
            ],
            'carpooling' => [
                'keywords' => [ 'carpooling', 'compartir coche', 'viajes', 'transporte' ],
                'modulo' => 'carpooling',
                'descripcion' => 'Compartir coche',
                'paginas_sugeridas' => [ 'viajes', 'ofrecer-viaje' ],
            ],

            // Servicios
            'incidencias' => [
                'keywords' => [ 'incidencias', 'averías', 'problemas', 'reportar', 'mantenimiento' ],
                'modulo' => 'incidencias',
                'descripcion' => 'Gestión de incidencias',
                'paginas_sugeridas' => [ 'reportar', 'mis-incidencias' ],
            ],
            'tramites' => [
                'keywords' => [ 'trámites', 'gestiones', 'solicitudes', 'documentos', 'expedientes' ],
                'modulo' => 'tramites',
                'descripcion' => 'Gestión de trámites',
                'paginas_sugeridas' => [ 'tramites', 'mis-tramites' ],
            ],

            // Grupos
            'colectivos' => [
                'keywords' => [ 'colectivos', 'grupos', 'comisiones', 'equipos', 'asociaciones' ],
                'modulo' => 'colectivos',
                'descripcion' => 'Gestión de colectivos',
                'paginas_sugeridas' => [ 'colectivos', 'mi-colectivo' ],
            ],
            'comunidades' => [
                'keywords' => [ 'comunidades', 'vecinos', 'barrio', 'urbanización' ],
                'modulo' => 'comunidades',
                'descripcion' => 'Comunidades de vecinos',
                'paginas_sugeridas' => [ 'comunidad', 'tablón' ],
            ],

            // Red Social y Colectivos Sociales (pack completo)
            'red_social' => [
                'keywords' => [ 'red social', 'social', 'perfiles', 'seguidores', 'publicaciones', 'muro' ],
                'modulo' => 'red-social',
                'descripcion' => 'Red social comunitaria',
                'paginas_sugeridas' => [ 'mi-red', 'mi-perfil', 'actividad' ],
            ],
            'encuestas' => [
                'keywords' => [ 'encuestas', 'sondeos', 'opinión', 'consultas' ],
                'modulo' => 'participacion',
                'descripcion' => 'Encuestas y sondeos',
                'paginas_sugeridas' => [ 'encuestas', 'votaciones' ],
            ],
            'recursos_compartidos' => [
                'keywords' => [ 'recursos', 'compartir', 'documentos', 'archivos', 'materiales' ],
                'modulo' => 'biblioteca',
                'descripcion' => 'Recursos compartidos',
                'paginas_sugeridas' => [ 'recursos', 'subir-recurso' ],
            ],
            'colectivos_sociales' => [
                'keywords' => [ 'colectivo social', 'movimiento', 'activismo', 'organización social', 'ong', 'asociación' ],
                'modulos' => [ 'colectivos', 'foros', 'red-social', 'participacion', 'eventos', 'biblioteca' ],
                'descripcion' => 'Plataforma completa para colectivos sociales',
                'paginas_sugeridas' => [
                    'inicio', 'mi-red', 'foros', 'grupos', 'eventos',
                    'encuestas', 'recursos', 'mi-perfil', 'actividad'
                ],
                'es_pack' => true,
            ],

            // Biblioteca
            'biblioteca' => [
                'keywords' => [ 'biblioteca', 'libros', 'préstamos', 'lectura', 'catálogo' ],
                'modulo' => 'biblioteca',
                'descripcion' => 'Biblioteca comunitaria',
                'paginas_sugeridas' => [ 'catalogo', 'mis-prestamos' ],
            ],
        ];
    }

    /**
     * Configurar templates de páginas
     */
    private function setup_page_templates() {
        $this->page_templates = [
            'home' => [
                'nombre' => 'Página de inicio',
                'bloques' => [
                    [ 'type' => 'hero', 'variant' => 'centered' ],
                    [ 'type' => 'features', 'variant' => 'grid' ],
                    [ 'type' => 'cta', 'variant' => 'simple' ],
                ],
            ],
            'dashboard_miembro' => [
                'nombre' => 'Portal del miembro',
                'bloques' => [
                    // Usa el shortcode del portal existente, no crea elementos VBP
                ],
                'shortcode' => '[flavor_mi_portal]',
            ],
            'listado' => [
                'nombre' => 'Página de listado',
                'bloques' => [
                    [ 'type' => 'heading' ],
                    [ 'type' => 'text' ],
                    [ 'type' => 'columns', 'data' => [ 'columnas' => 3 ] ],
                ],
            ],
            'formulario' => [
                'nombre' => 'Página con formulario',
                'bloques' => [
                    [ 'type' => 'heading' ],
                    [ 'type' => 'text' ],
                    [ 'type' => 'form' ],
                ],
            ],
            'contacto' => [
                'nombre' => 'Página de contacto',
                'bloques' => [
                    [ 'type' => 'heading', 'data' => [ 'texto' => 'Contacto' ] ],
                    [ 'type' => 'columns', 'data' => [ 'columnas' => 2 ] ],
                ],
            ],
            'sobre_nosotros' => [
                'nombre' => 'Sobre nosotros',
                'bloques' => [
                    [ 'type' => 'hero', 'variant' => 'minimal' ],
                    [ 'type' => 'text' ],
                    [ 'type' => 'team', 'variant' => 'grid' ],
                ],
            ],
        ];
    }

    /**
     * Configurar mapeo de tipos de comunidad a temas predefinidos
     */
    private function setup_temas_por_tipo() {
        // Obtener temas del Theme Manager si está disponible
        $this->temas_disponibles = $this->cargar_temas_desde_theme_manager();

        // Mapeo de tipos de comunidad a temas recomendados (ordenados por prioridad)
        $this->temas_por_tipo = [
            'colectivo_social' => [ 'comunidad-viva', 'democracia-universal', 'denendako' ],
            'vecinal'          => [ 'pueblo-vivo', 'ecos-comunitarios', 'denendako' ],
            'deportiva'        => [ 'comunidad-viva', 'forest-green', 'ecos-comunitarios' ],
            'cultural'         => [ 'kulturaka', 'campi', 'escena-familiar' ],
            'educativa'        => [ 'academia-espiral', 'comunidad-viva', 'themacle' ],
            'empresarial'      => [ 'naarq', 'corporate', 'minimal' ],
            'ecologica'        => [ 'zunbeltz', 'grupos-consumo', 'mercado-espiral' ],
            'consumo'          => [ 'grupos-consumo', 'mercado-espiral', 'zunbeltz' ],
            'cuidados'         => [ 'red-cuidados', 'comunidad-viva', 'escena-familiar' ],
            'gastronomia'      => [ 'jantoki', 'mercado-espiral', 'grupos-consumo' ],
            'finanzas'         => [ 'spiral-bank', 'corporate', 'minimal' ],
            'medios'           => [ 'flujo', 'themacle-dark', 'dark-mode' ],
            'otra'             => [ 'default', 'comunidad-viva', 'denendako' ],
        ];
    }

    /**
     * Cargar temas desde el Theme Manager
     *
     * @return array
     */
    private function cargar_temas_desde_theme_manager() {
        // Iconos por categoría/tipo de tema
        $iconos_por_categoria = [
            'general'      => 'dashicons-admin-appearance',
            'alimentacion' => 'dashicons-carrot',
            'cultura'      => 'dashicons-tickets-alt',
            'inmobiliaria' => 'dashicons-building',
            'infantil'     => 'dashicons-smiley',
            'educacion'    => 'dashicons-welcome-learn-more',
            'finanzas'     => 'dashicons-money-alt',
            'salud'        => 'dashicons-heart',
            'tecnologia'   => 'dashicons-laptop',
            'deporte'      => 'dashicons-awards',
        ];

        // Iconos específicos por tema
        $iconos_especificos = [
            'zunbeltz'             => 'dashicons-palmtree',
            'comunidad-viva'       => 'dashicons-groups',
            'grupos-consumo'       => 'dashicons-carrot',
            'jantoki'              => 'dashicons-food',
            'mercado-espiral'      => 'dashicons-store',
            'spiral-bank'          => 'dashicons-money-alt',
            'red-cuidados'         => 'dashicons-heart',
            'academia-espiral'     => 'dashicons-welcome-learn-more',
            'democracia-universal' => 'dashicons-megaphone',
            'flujo'                => 'dashicons-video-alt3',
            'kulturaka'            => 'dashicons-tickets-alt',
            'pueblo-vivo'          => 'dashicons-admin-home',
            'ecos-comunitarios'    => 'dashicons-admin-multisite',
            'escena-familiar'      => 'dashicons-smiley',
            'dark-mode'            => 'dashicons-visibility',
            'minimal'              => 'dashicons-editor-removeformatting',
            'corporate'            => 'dashicons-businessman',
            'forest-green'         => 'dashicons-palmtree',
            'ocean-blue'           => 'dashicons-cloud',
            'sunset-orange'        => 'dashicons-format-image',
        ];

        // Si el Theme Manager está disponible, obtener temas de ahí
        if ( class_exists( 'Flavor_Theme_Manager' ) ) {
            $theme_manager = Flavor_Theme_Manager::get_instance();
            if ( method_exists( $theme_manager, 'get_all_themes' ) ) {
                $temas_raw = $theme_manager->get_all_themes();
            } else {
                // Fallback: intentar acceder a la propiedad themes
                $temas_raw = $this->obtener_temas_reflection( $theme_manager );
            }

            if ( ! empty( $temas_raw ) ) {
                $temas_formateados = [];
                foreach ( $temas_raw as $tema_id => $tema ) {
                    // Extraer color primario de las variables
                    $color = '#3b82f6';
                    if ( isset( $tema['variables']['--flavor-primary'] ) ) {
                        $color = $tema['variables']['--flavor-primary'];
                    }

                    // Determinar icono
                    $categoria = $tema['category'] ?? 'general';
                    $icon = $iconos_especificos[ $tema_id ]
                        ?? $iconos_por_categoria[ $categoria ]
                        ?? 'dashicons-admin-appearance';

                    $temas_formateados[ $tema_id ] = [
                        'label' => $tema['name'] ?? ucfirst( $tema_id ),
                        'desc'  => $tema['ideal_for'] ?? $tema['description'] ?? '',
                        'color' => $color,
                        'icon'  => $icon,
                    ];
                }
                return $temas_formateados;
            }
        }

        // Fallback: lista hardcodeada si no hay Theme Manager
        return [
            'default'              => [ 'label' => 'Default', 'desc' => 'Tema por defecto', 'color' => '#3b82f6', 'icon' => 'dashicons-admin-appearance' ],
            'dark-mode'            => [ 'label' => 'Dark Mode', 'desc' => 'Tema oscuro', 'color' => '#60a5fa', 'icon' => 'dashicons-visibility' ],
            'minimal'              => [ 'label' => 'Minimal', 'desc' => 'Diseño minimalista', 'color' => '#171717', 'icon' => 'dashicons-editor-removeformatting' ],
            'zunbeltz'             => [ 'label' => 'Zunbeltz', 'desc' => 'Comunidad Ecológica', 'color' => '#2D5F2E', 'icon' => 'dashicons-palmtree' ],
            'comunidad-viva'       => [ 'label' => 'Comunidad Viva', 'desc' => 'Red Social Cooperativa', 'color' => '#4f46e5', 'icon' => 'dashicons-groups' ],
            'grupos-consumo'       => [ 'label' => 'Grupos de Consumo', 'desc' => 'App Grupo de Consumo', 'color' => '#4a7c59', 'icon' => 'dashicons-carrot' ],
            'campi'                => [ 'label' => 'Campi', 'desc' => 'Espai Cultural & Teatre', 'color' => '#1a1b3a', 'icon' => 'dashicons-tickets-alt' ],
            'kulturaka'            => [ 'label' => 'Kulturaka', 'desc' => 'Cultura Cooperativa', 'color' => '#e63946', 'icon' => 'dashicons-tickets-alt' ],
            'academia-espiral'     => [ 'label' => 'Academia Espiral', 'desc' => 'Educación P2P', 'color' => '#d97706', 'icon' => 'dashicons-welcome-learn-more' ],
            'naarq'                => [ 'label' => 'Naarq', 'desc' => 'Estudi d\'Arquitectura', 'color' => '#1a1a1a', 'icon' => 'dashicons-building' ],
            'denendako'            => [ 'label' => 'Denendako', 'desc' => 'Herri Sarea', 'color' => '#333333', 'icon' => 'dashicons-networking' ],
            'pueblo-vivo'          => [ 'label' => 'Pueblo Vivo', 'desc' => 'Revitalización Rural', 'color' => '#c2703a', 'icon' => 'dashicons-admin-home' ],
            'ecos-comunitarios'    => [ 'label' => 'Ecos Comunitarios', 'desc' => 'Espacios Compartidos', 'color' => '#0891b2', 'icon' => 'dashicons-admin-multisite' ],
            'mercado-espiral'      => [ 'label' => 'Mercado Espiral', 'desc' => 'Marketplace km0', 'color' => '#2e7d32', 'icon' => 'dashicons-store' ],
            'red-cuidados'         => [ 'label' => 'Red de Cuidados', 'desc' => 'Apoyo Mutuo Comunitario', 'color' => '#ec4899', 'icon' => 'dashicons-heart' ],
            'democracia-universal' => [ 'label' => 'Democracia Universal', 'desc' => 'Gobernanza Participativa', 'color' => '#8b5cf6', 'icon' => 'dashicons-megaphone' ],
            'jantoki'              => [ 'label' => 'Jantoki', 'desc' => 'Restaurante Cooperativo', 'color' => '#8b5a2b', 'icon' => 'dashicons-food' ],
            'spiral-bank'          => [ 'label' => 'Spiral Bank', 'desc' => 'Banca Cooperativa', 'color' => '#764ba2', 'icon' => 'dashicons-money-alt' ],
            'flujo'                => [ 'label' => 'FLUJO', 'desc' => 'Red de Vídeo', 'color' => '#166534', 'icon' => 'dashicons-video-alt3' ],
            'escena-familiar'      => [ 'label' => 'Escena Familiar', 'desc' => 'Teatro Familiar', 'color' => '#7c3aed', 'icon' => 'dashicons-smiley' ],
        ];
    }

    /**
     * Obtener temas via Reflection (fallback)
     *
     * @param object $theme_manager
     * @return array
     */
    private function obtener_temas_reflection( $theme_manager ) {
        try {
            $reflection = new ReflectionClass( $theme_manager );
            $property = $reflection->getProperty( 'themes' );
            $property->setAccessible( true );
            return $property->getValue( $theme_manager );
        } catch ( Exception $e ) {
            return [];
        }
    }

    /**
     * Obtener temas recomendados para un tipo de comunidad
     *
     * @param string $tipo_comunidad Tipo de comunidad.
     * @return array Temas recomendados con información completa.
     */
    public function get_temas_recomendados( $tipo_comunidad ) {
        $temas_ids = $this->temas_por_tipo[ $tipo_comunidad ] ?? $this->temas_por_tipo['otra'];
        $temas_info = [];

        foreach ( $temas_ids as $tema_id ) {
            if ( isset( $this->temas_disponibles[ $tema_id ] ) ) {
                $temas_info[ $tema_id ] = $this->temas_disponibles[ $tema_id ];
            }
        }

        return $temas_info;
    }

    /**
     * Obtener todos los temas disponibles
     *
     * @return array
     */
    public function get_temas_disponibles() {
        return $this->temas_disponibles;
    }

    /**
     * Obtener motor de IA
     *
     * @return object|null
     */
    private function get_ai_engine() {
        if ( $this->ai_engine ) {
            return $this->ai_engine;
        }

        if ( class_exists( 'Flavor_Engine_Manager' ) ) {
            $manager = Flavor_Engine_Manager::get_instance();
            // Usar get_active_engine() con contexto backend (admin)
            $this->ai_engine = $manager->get_active_engine( 'backend' );
        }

        return $this->ai_engine;
    }

    /**
     * Analizar requisitos del proyecto
     *
     * @param string $descripcion Descripción del proyecto.
     * @param array  $documentos  Documentos adjuntos (opcional).
     * @param array  $imagenes    Imágenes de referencia (opcional).
     * @return array Análisis estructurado.
     */
    public function analizar_requisitos( $descripcion, $documentos = [], $imagenes = [] ) {
        $engine = $this->get_ai_engine();

        // Si no hay motor de IA configurado, usar análisis local
        if ( ! $engine || ! $engine->is_configured() ) {
            return $this->analizar_requisitos_local( $descripcion );
        }

        $system_prompt = $this->construir_system_prompt();
        $user_message = $this->construir_prompt_analisis( $descripcion, $documentos );

        try {
            // Formato de mensajes para la API
            $messages = [
                [
                    'role' => 'user',
                    'content' => $user_message,
                ],
            ];

            $resultado = $engine->send_message( $messages, $system_prompt );

            if ( ! empty( $resultado['success'] ) && ! empty( $resultado['response'] ) ) {
                return $this->parsear_respuesta_ia( $resultado['response'] );
            }

            // Si la IA falló, usar análisis local
            return $this->analizar_requisitos_local( $descripcion );

        } catch ( Exception $e ) {
            // Fallback a análisis local
            return $this->analizar_requisitos_local( $descripcion );
        }
    }

    /**
     * Construir system prompt para la IA
     *
     * @return string
     */
    private function construir_system_prompt() {
        return 'Eres un experto en diseño de plataformas comunitarias y webs. Analiza descripciones de proyectos y proporciona estructuras recomendadas en formato JSON. Solo responde con JSON válido, sin texto adicional.';
    }

    /**
     * Construir prompt para análisis con IA
     *
     * @param string $descripcion Descripción del proyecto.
     * @param array  $documentos  Documentos.
     * @return string
     */
    private function construir_prompt_analisis( $descripcion, $documentos = [] ) {
        $modulos_disponibles = array_keys( $this->casos_uso_modulos );

        $prompt = <<<PROMPT
Eres un experto en diseño de plataformas comunitarias y webs. Analiza la siguiente descripción de proyecto y proporciona una estructura recomendada.

## DESCRIPCIÓN DEL PROYECTO:
{$descripcion}

## MÓDULOS DISPONIBLES:
Los siguientes módulos están disponibles para activar:
- socios: Gestión de membresías y cuotas
- eventos: Calendario y gestión de eventos
- reservas: Sistema de reservas de recursos
- foros: Foros de discusión comunitarios
- marketplace: Compraventa entre miembros
- banco-tiempo: Intercambio de servicios
- participacion: Votaciones y propuestas
- huertos-urbanos: Gestión de parcelas
- biblioteca: Préstamo de libros
- incidencias: Reporte de problemas
- colectivos: Grupos de trabajo
- cursos: Formación y talleres
- radio: Radio comunitaria
- bicicletas-compartidas: Préstamo de bicis
- carpooling: Compartir coche
- tramites: Gestión de expedientes
- compostaje: Sistema de compostaje
- espacios-comunes: Reserva de salas
- comunidades: Comunidades de vecinos

## RESPONDE EN FORMATO JSON:
{
    "nombre_proyecto": "Nombre sugerido para el proyecto",
    "tipo_comunidad": "vecinal|deportiva|cultural|educativa|empresarial|otra",
    "descripcion_corta": "Descripción de 1-2 frases",
    "modulos_recomendados": ["modulo1", "modulo2"],
    "modulos_opcionales": ["modulo3"],
    "paginas_sugeridas": [
        {"slug": "inicio", "titulo": "Inicio", "template": "home", "descripcion": "Página principal"},
        {"slug": "mi-panel", "titulo": "Mi Panel", "template": "dashboard_miembro", "descripcion": "Dashboard del usuario"}
    ],
    "colores_sugeridos": {
        "primario": "#3b82f6",
        "secundario": "#8b5cf6",
        "acento": "#10b981"
    },
    "funcionalidades_clave": ["func1", "func2", "func3"],
    "publico_objetivo": "Descripción del público objetivo"
}

Solo responde con el JSON, sin texto adicional.
PROMPT;

        return $prompt;
    }

    /**
     * Parsear respuesta de IA
     *
     * @param string $respuesta Respuesta de la IA.
     * @return array
     */
    private function parsear_respuesta_ia( $respuesta ) {
        // Extraer JSON de la respuesta
        $json_match = preg_match( '/\{[\s\S]*\}/', $respuesta, $matches );

        if ( $json_match && ! empty( $matches[0] ) ) {
            $datos = json_decode( $matches[0], true );

            if ( json_last_error() === JSON_ERROR_NONE ) {
                return $this->validar_propuesta( $datos );
            }
        }

        // Si no se puede parsear, retornar error
        return [
            'error' => true,
            'mensaje' => 'No se pudo analizar la respuesta de la IA',
            'respuesta_raw' => $respuesta,
        ];
    }

    /**
     * Análisis local sin IA (fallback)
     *
     * @param string $descripcion Descripción del proyecto.
     * @return array
     */
    private function analizar_requisitos_local( $descripcion ) {
        $descripcion_lower = mb_strtolower( $descripcion );
        $modulos_detectados = [];
        $modulos_lista = [];
        $paginas_sugeridas = [];

        // Detectar módulos por keywords
        foreach ( $this->casos_uso_modulos as $caso => $config ) {
            foreach ( $config['keywords'] as $keyword ) {
                if ( strpos( $descripcion_lower, $keyword ) !== false ) {
                    $modulos_detectados[ $caso ] = $config;

                    // Extraer módulos (puede ser uno o varios)
                    if ( ! empty( $config['modulos'] ) && is_array( $config['modulos'] ) ) {
                        foreach ( $config['modulos'] as $modulo ) {
                            if ( ! in_array( $modulo, $modulos_lista, true ) ) {
                                $modulos_lista[] = $modulo;
                            }
                        }
                    } elseif ( ! empty( $config['modulo'] ) ) {
                        if ( ! in_array( $config['modulo'], $modulos_lista, true ) ) {
                            $modulos_lista[] = $config['modulo'];
                        }
                    }
                    break;
                }
            }
        }

        // Generar páginas sugeridas
        $paginas_sugeridas[] = [
            'slug' => 'inicio',
            'titulo' => 'Inicio',
            'template' => 'home',
            'descripcion' => 'Página principal del sitio',
        ];

        $paginas_sugeridas[] = [
            'slug' => 'mi-portal',
            'titulo' => 'Mi Portal',
            'template' => 'dashboard_miembro',
            'descripcion' => 'Portal personal del usuario',
        ];

        $slugs_agregados = [ 'inicio', 'mi-portal' ];
        foreach ( $modulos_detectados as $caso => $config ) {
            foreach ( $config['paginas_sugeridas'] as $pagina ) {
                // Evitar duplicados
                if ( in_array( $pagina, $slugs_agregados, true ) ) {
                    continue;
                }
                $slugs_agregados[] = $pagina;

                $paginas_sugeridas[] = [
                    'slug' => $pagina,
                    'titulo' => ucfirst( str_replace( '-', ' ', $pagina ) ),
                    'template' => 'listado',
                    'descripcion' => 'Página de ' . $config['descripcion'],
                    'modulo' => $config['modulo'] ?? '',
                ];
            }
        }

        // Detectar tipo de comunidad
        $tipo = 'otra';
        if ( preg_match( '/colectivo|ong|movimiento|activis|social/i', $descripcion ) ) {
            $tipo = 'colectivo_social';
        } elseif ( preg_match( '/ecolog|sostenib|verde|huerto|compost/i', $descripcion ) ) {
            $tipo = 'ecologica';
        } elseif ( preg_match( '/consumo|cooperativa|producto|km0|local/i', $descripcion ) ) {
            $tipo = 'consumo';
        } elseif ( preg_match( '/cuidado|apoyo|mutual|ayuda/i', $descripcion ) ) {
            $tipo = 'cuidados';
        } elseif ( preg_match( '/vecin|barrio|comunidad|urbanizaci/i', $descripcion ) ) {
            $tipo = 'vecinal';
        } elseif ( preg_match( '/deport|club|equipo|entrena/i', $descripcion ) ) {
            $tipo = 'deportiva';
        } elseif ( preg_match( '/cultur|arte|música|teatro/i', $descripcion ) ) {
            $tipo = 'cultural';
        } elseif ( preg_match( '/educa|escuela|formaci|aprend/i', $descripcion ) ) {
            $tipo = 'educativa';
        } elseif ( preg_match( '/empresa|negocio|cowork|profesional/i', $descripcion ) ) {
            $tipo = 'empresarial';
        }

        // Nombre según tipo
        $nombres_por_tipo = [
            'colectivo_social' => 'Mi Colectivo',
            'vecinal'          => 'Mi Comunidad',
            'deportiva'        => 'Mi Club',
            'cultural'         => 'Mi Asociación Cultural',
            'educativa'        => 'Mi Centro',
            'empresarial'      => 'Mi Espacio',
            'ecologica'        => 'Mi Iniciativa Verde',
            'consumo'          => 'Mi Grupo de Consumo',
            'cuidados'         => 'Mi Red de Cuidados',
        ];

        // Obtener temas recomendados para este tipo
        $temas_recomendados = $this->get_temas_recomendados( $tipo );
        $tema_principal = ! empty( $temas_recomendados ) ? array_key_first( $temas_recomendados ) : 'comunidad-viva';
        $tema_info = $temas_recomendados[ $tema_principal ] ?? [];

        // Los colores se derivan del tema seleccionado
        $color_tema = $tema_info['color'] ?? '#4f46e5';
        $colores_sugeridos = [
            'primario'   => $color_tema,
            'secundario' => $this->ajustar_luminosidad( $color_tema, 20 ),
            'acento'     => $this->color_complementario( $color_tema ),
        ];

        // Obtener todos los temas (recomendados + resto)
        $todos_los_temas = $this->get_temas_disponibles();

        return [
            'nombre_proyecto'       => $nombres_por_tipo[ $tipo ] ?? 'Mi Comunidad',
            'tipo_comunidad'        => $tipo,
            'descripcion_corta'     => substr( $descripcion, 0, 150 ),
            'modulos_recomendados'  => array_values( array_unique( $modulos_lista ) ),
            'modulos_opcionales'    => [],
            'paginas_sugeridas'     => $paginas_sugeridas,
            'colores_sugeridos'     => $colores_sugeridos,
            'tema_recomendado'      => $tema_principal,
            'temas_recomendados'    => $temas_recomendados,
            'todos_los_temas'       => $todos_los_temas,
            'funcionalidades_clave' => array_column( array_values( $modulos_detectados ), 'descripcion' ),
            'publico_objetivo'      => 'Miembros de la comunidad',
            'analisis_local'        => true,
        ];
    }

    /**
     * Ajustar luminosidad de un color hex
     *
     * @param string $hex    Color hexadecimal.
     * @param int    $amount Cantidad de ajuste (-100 a 100).
     * @return string
     */
    private function ajustar_luminosidad( $hex, $amount ) {
        $hex = ltrim( $hex, '#' );

        $r = hexdec( substr( $hex, 0, 2 ) );
        $g = hexdec( substr( $hex, 2, 2 ) );
        $b = hexdec( substr( $hex, 4, 2 ) );

        $r = max( 0, min( 255, $r + $amount ) );
        $g = max( 0, min( 255, $g + $amount ) );
        $b = max( 0, min( 255, $b + $amount ) );

        return sprintf( '#%02x%02x%02x', $r, $g, $b );
    }

    /**
     * Obtener color complementario
     *
     * @param string $hex Color hexadecimal.
     * @return string
     */
    private function color_complementario( $hex ) {
        $hex = ltrim( $hex, '#' );

        $r = hexdec( substr( $hex, 0, 2 ) );
        $g = hexdec( substr( $hex, 2, 2 ) );
        $b = hexdec( substr( $hex, 4, 2 ) );

        // Rotar hue para obtener complementario
        $max = max( $r, $g, $b );
        $min = min( $r, $g, $b );

        // Usar un acento dorado/naranja como fallback universal
        return '#f59e0b';
    }

    /**
     * Validar y completar propuesta
     *
     * @param array $datos Datos de la propuesta.
     * @return array
     */
    private function validar_propuesta( $datos ) {
        // Asegurar campos requeridos
        $defaults = [
            'nombre_proyecto' => 'Mi Proyecto',
            'tipo_comunidad' => 'otra',
            'descripcion_corta' => '',
            'modulos_recomendados' => [],
            'modulos_opcionales' => [],
            'paginas_sugeridas' => [],
            'colores_sugeridos' => [
                'primario' => '#3b82f6',
                'secundario' => '#8b5cf6',
                'acento' => '#10b981',
            ],
            'funcionalidades_clave' => [],
            'publico_objetivo' => '',
        ];

        $datos = wp_parse_args( $datos, $defaults );

        // Validar módulos (que existan)
        $modulos_validos = array_keys( $this->casos_uso_modulos );
        $datos['modulos_recomendados'] = array_filter(
            $datos['modulos_recomendados'],
            function( $m ) use ( $modulos_validos ) {
                // Buscar por nombre de módulo
                foreach ( $this->casos_uso_modulos as $caso => $config ) {
                    if ( $config['modulo'] === $m || $caso === $m ) {
                        return true;
                    }
                }
                return false;
            }
        );

        // Asegurar página de inicio y dashboard
        $tiene_inicio = false;
        $tiene_dashboard = false;

        foreach ( $datos['paginas_sugeridas'] as $pagina ) {
            if ( $pagina['slug'] === 'inicio' || $pagina['template'] === 'home' ) {
                $tiene_inicio = true;
            }
            if ( $pagina['template'] === 'dashboard_miembro' ) {
                $tiene_dashboard = true;
            }
        }

        if ( ! $tiene_inicio ) {
            array_unshift( $datos['paginas_sugeridas'], [
                'slug' => 'inicio',
                'titulo' => 'Inicio',
                'template' => 'home',
                'descripcion' => 'Página principal',
            ] );
        }

        if ( ! $tiene_dashboard ) {
            $datos['paginas_sugeridas'][] = [
                'slug' => 'mi-panel',
                'titulo' => 'Mi Panel',
                'template' => 'dashboard_miembro',
                'descripcion' => 'Dashboard del usuario',
            ];
        }

        return $datos;
    }

    /**
     * Generar estructura del sitio
     *
     * @param array $propuesta Propuesta aprobada.
     * @return array Resultado de la generación.
     */
    public function generar_estructura( $propuesta ) {
        $resultados = [
            'paginas_creadas' => [],
            'modulos_activados' => [],
            'configuracion_aplicada' => [],
            'errores' => [],
        ];

        // 1. Activar módulos
        foreach ( $propuesta['modulos_recomendados'] as $modulo ) {
            $resultado = $this->activar_modulo( $modulo );
            if ( $resultado['success'] ) {
                $resultados['modulos_activados'][] = $modulo;
            } else {
                $resultados['errores'][] = "Error activando módulo {$modulo}: " . $resultado['error'];
            }
        }

        // 2. Crear páginas
        foreach ( $propuesta['paginas_sugeridas'] as $pagina ) {
            $resultado = $this->crear_pagina( $pagina, $propuesta );
            if ( $resultado['success'] ) {
                $resultados['paginas_creadas'][] = [
                    'titulo' => $pagina['titulo'],
                    'url' => $resultado['url'],
                    'edit_url' => $resultado['edit_url'],
                ];
            } else {
                $resultados['errores'][] = "Error creando página {$pagina['titulo']}: " . $resultado['error'];
            }
        }

        // 3. Aplicar tema de diseño
        if ( ! empty( $propuesta['tema_recomendado'] ) ) {
            $this->aplicar_tema( $propuesta['tema_recomendado'] );
            $tema_info = $this->temas_disponibles[ $propuesta['tema_recomendado'] ] ?? [];
            $resultados['configuracion_aplicada'][] = 'Tema: ' . ( $tema_info['label'] ?? $propuesta['tema_recomendado'] );
        } elseif ( ! empty( $propuesta['colores_sugeridos'] ) ) {
            // Fallback: aplicar solo colores si no hay tema
            $this->aplicar_design_settings( $propuesta['colores_sugeridos'] );
            $resultados['configuracion_aplicada'][] = 'Colores del sitio';
        }

        // 4. Configurar nombre del sitio
        if ( ! empty( $propuesta['nombre_proyecto'] ) ) {
            update_option( 'blogname', $propuesta['nombre_proyecto'] );
            $resultados['configuracion_aplicada'][] = 'Nombre del sitio';
        }

        return $resultados;
    }

    /**
     * Activar un módulo o pack de módulos
     *
     * @param string $modulo_id ID del módulo o pack.
     * @return array
     */
    private function activar_modulo( $modulo_id ) {
        $modulos_a_activar = [];

        // Buscar si es un caso de uso mapeado
        foreach ( $this->casos_uso_modulos as $caso => $config ) {
            if ( $caso === $modulo_id || ( isset( $config['modulo'] ) && $config['modulo'] === $modulo_id ) ) {
                // Si es un pack con múltiples módulos
                if ( ! empty( $config['modulos'] ) && is_array( $config['modulos'] ) ) {
                    $modulos_a_activar = $config['modulos'];
                } elseif ( ! empty( $config['modulo'] ) ) {
                    $modulos_a_activar = [ $config['modulo'] ];
                }
                break;
            }
        }

        // Si no se encontró mapeo, usar el ID directamente
        if ( empty( $modulos_a_activar ) ) {
            $modulos_a_activar = [ $modulo_id ];
        }

        // Obtener módulos activos de la opción preferida
        $configuracion = get_option( 'flavor_chat_ia_settings', [] );
        $modulos_activos = $configuracion['active_modules'] ?? [];

        if ( ! is_array( $modulos_activos ) ) {
            $modulos_activos = [];
        }

        // Activar todos los módulos del pack
        $activados = 0;
        foreach ( $modulos_a_activar as $modulo ) {
            if ( ! in_array( $modulo, $modulos_activos, true ) ) {
                $modulos_activos[] = $modulo;
                $activados++;
            }
        }

        if ( $activados > 0 ) {
            // Sincronizar con ambas opciones para compatibilidad
            $configuracion['active_modules'] = $modulos_activos;
            update_option( 'flavor_chat_ia_settings', $configuracion );
            update_option( 'flavor_active_modules', $modulos_activos );
        }

        return [
            'success' => true,
            'activados' => $activados,
            'modulos' => $modulos_a_activar,
        ];
    }

    /**
     * Crear página con VBP
     *
     * Crea páginas normales de WordPress (no flavor_landing) para que
     * estén accesibles directamente en la raíz del sitio (ej: /mi-panel)
     * en lugar de bajo el prefijo /landing/.
     *
     * @param array $pagina Datos de la página.
     * @param array $propuesta Propuesta completa.
     * @return array
     */
    private function crear_pagina( $pagina, $propuesta ) {
        $template = $this->page_templates[ $pagina['template'] ] ?? $this->page_templates['listado'];

        // Verificar si ya existe una página con este slug
        $pagina_existente = get_page_by_path( $pagina['slug'] );
        if ( $pagina_existente ) {
            // La página ya existe, devolver su información
            return [
                'success' => true,
                'post_id' => $pagina_existente->ID,
                'url' => get_permalink( $pagina_existente->ID ),
                'edit_url' => admin_url( 'post.php?post=' . $pagina_existente->ID . '&action=edit' ),
                'ya_existia' => true,
            ];
        }

        // Preparar bloques VBP
        $elementos = [];
        foreach ( $template['bloques'] as $bloque ) {
            $elemento = [
                'id' => 'el_' . wp_generate_uuid4(),
                'type' => $bloque['type'],
                'variant' => $bloque['variant'] ?? 'default',
                'visible' => true,
                'locked' => false,
                'data' => $bloque['data'] ?? [],
                'styles' => [],
                'children' => [],
            ];

            // Personalizar según contexto
            if ( $bloque['type'] === 'hero' ) {
                $elemento['data']['titulo'] = $propuesta['nombre_proyecto'];
                $elemento['data']['subtitulo'] = $propuesta['descripcion_corta'];
                $elemento['data']['boton_texto'] = 'Comenzar';
                $elemento['data']['boton_url'] = '/mi-portal';
            }

            if ( $bloque['type'] === 'heading' && empty( $elemento['data']['texto'] ) ) {
                $elemento['data']['texto'] = $pagina['titulo'];
            }

            $elementos[] = $elemento;
        }

        // Generar contenido: usar shortcode del template si existe, o shortcode del módulo
        $contenido = '';
        if ( ! empty( $template['shortcode'] ) ) {
            // El template define su propio shortcode (ej: dashboard_miembro usa [flavor_mi_portal])
            $contenido = $template['shortcode'];
        } elseif ( ! empty( $pagina['modulo'] ) ) {
            $contenido = '[flavor_module module="' . esc_attr( $pagina['modulo'] ) . '"]';
        }

        // Crear página normal de WordPress (accesible en raíz del sitio)
        $post_data = [
            'post_title'   => $pagina['titulo'],
            'post_name'    => $pagina['slug'],
            'post_status'  => 'publish',
            'post_type'    => 'page',
            'post_content' => $contenido,
        ];

        $post_id = wp_insert_post( $post_data );

        if ( is_wp_error( $post_id ) ) {
            return [
                'success' => false,
                'error' => $post_id->get_error_message(),
            ];
        }

        // Guardar datos VBP para que se pueda editar con el editor visual
        $vbp_data = [
            'elements' => $elementos,
            'settings' => [
                'backgroundColor' => '#ffffff',
                'pageWidth' => 1200,
            ],
        ];

        update_post_meta( $post_id, '_flavor_vbp_data', $vbp_data );

        // Marcar que esta página usa VBP
        update_post_meta( $post_id, '_flavor_uses_vbp', true );

        return [
            'success' => true,
            'post_id' => $post_id,
            'url' => get_permalink( $post_id ),
            'edit_url' => admin_url( 'post.php?post=' . $post_id . '&action=edit' ),
        ];
    }

    /**
     * Aplicar tema predefinido
     *
     * Usa el Theme Manager para aplicar todos los estilos del tema:
     * - Variables CSS (colores, tipografía, espaciados, sombras, bordes)
     * - Estas variables afectan menú, body, footer y todos los componentes
     *
     * @param string $tema_id ID del tema a aplicar.
     * @return bool
     */
    private function aplicar_tema( $tema_id ) {
        // Usar el Theme Manager para aplicar el tema completo
        if ( class_exists( 'Flavor_Theme_Manager' ) ) {
            $theme_manager = Flavor_Theme_Manager::get_instance();

            // Método principal: set_active_theme
            if ( method_exists( $theme_manager, 'set_active_theme' ) ) {
                $resultado = $theme_manager->set_active_theme( $tema_id );
                if ( $resultado ) {
                    // Trigger acción para que otros componentes actualicen
                    do_action( 'flavor_app_generator_theme_applied', $tema_id );
                    return true;
                }
            }
        }

        // Fallback: guardar tema activo y aplicar colores básicos
        if ( isset( $this->temas_disponibles[ $tema_id ] ) ) {
            $tema = $this->temas_disponibles[ $tema_id ];

            // Guardar tema activo
            update_option( 'flavor_active_theme', $tema_id );

            // Limpiar personalizaciones anteriores
            delete_option( 'flavor_theme_customizations' );

            // Aplicar colores básicos como fallback
            $this->aplicar_design_settings( [
                'primario'   => $tema['color'],
                'secundario' => $this->ajustar_luminosidad( $tema['color'], 20 ),
                'acento'     => '#f59e0b',
            ] );

            return true;
        }

        return false;
    }

    /**
     * Aplicar configuración de diseño
     *
     * @param array $colores Colores a aplicar.
     */
    private function aplicar_design_settings( $colores ) {
        $settings = get_option( 'flavor_design_settings', [] );

        if ( ! empty( $colores['primario'] ) ) {
            $settings['primary_color'] = $colores['primario'];
        }
        if ( ! empty( $colores['secundario'] ) ) {
            $settings['secondary_color'] = $colores['secundario'];
        }
        if ( ! empty( $colores['acento'] ) ) {
            $settings['accent_color'] = $colores['acento'];
        }

        update_option( 'flavor_design_settings', $settings );
    }

    /**
     * AJAX: Analizar proyecto
     */
    public function ajax_analyze() {
        check_ajax_referer( 'flavor_app_generator', 'nonce' );

        if ( ! current_user_can( 'manage_options' ) ) {
            wp_send_json_error( [ 'message' => 'Sin permisos' ] );
        }

        $descripcion = sanitize_textarea_field( $_POST['descripcion'] ?? '' );

        if ( empty( $descripcion ) ) {
            wp_send_json_error( [ 'message' => 'La descripción es requerida' ] );
        }

        $analisis = $this->analizar_requisitos( $descripcion );

        wp_send_json_success( $analisis );
    }

    /**
     * AJAX: Generar estructura
     */
    public function ajax_generate() {
        check_ajax_referer( 'flavor_app_generator', 'nonce' );

        if ( ! current_user_can( 'manage_options' ) ) {
            wp_send_json_error( [ 'message' => 'Sin permisos' ] );
        }

        $propuesta = json_decode( stripslashes( $_POST['propuesta'] ?? '{}' ), true );

        if ( empty( $propuesta ) ) {
            wp_send_json_error( [ 'message' => 'Propuesta inválida' ] );
        }

        $resultado = $this->generar_estructura( $propuesta );

        wp_send_json_success( $resultado );
    }

    /**
     * AJAX: Preview de estructura
     */
    public function ajax_preview() {
        check_ajax_referer( 'flavor_app_generator', 'nonce' );

        if ( ! current_user_can( 'manage_options' ) ) {
            wp_send_json_error( [ 'message' => 'Sin permisos' ] );
        }

        $propuesta = json_decode( stripslashes( $_POST['propuesta'] ?? '{}' ), true );

        // Generar preview HTML
        $preview = $this->generar_preview_html( $propuesta );

        wp_send_json_success( [ 'preview' => $preview ] );
    }

    /**
     * Generar HTML de preview
     *
     * @param array $propuesta Propuesta.
     * @return string
     */
    private function generar_preview_html( $propuesta ) {
        ob_start();
        ?>
        <div class="app-generator-preview">
            <h3><?php echo esc_html( $propuesta['nombre_proyecto'] ?? 'Mi Proyecto' ); ?></h3>
            <p class="preview-tipo"><?php echo esc_html( ucfirst( $propuesta['tipo_comunidad'] ?? 'Comunidad' ) ); ?></p>

            <div class="preview-section">
                <h4>Módulos a activar</h4>
                <ul>
                    <?php foreach ( $propuesta['modulos_recomendados'] ?? [] as $modulo ) : ?>
                        <li><?php echo esc_html( $modulo ); ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>

            <div class="preview-section">
                <h4>Páginas a crear</h4>
                <ul>
                    <?php foreach ( $propuesta['paginas_sugeridas'] ?? [] as $pagina ) : ?>
                        <li>
                            <strong><?php echo esc_html( $pagina['titulo'] ); ?></strong>
                            <span class="slug">/<?php echo esc_html( $pagina['slug'] ); ?></span>
                        </li>
                    <?php endforeach; ?>
                </ul>
            </div>

            <div class="preview-section">
                <h4>Colores</h4>
                <div class="preview-colors">
                    <?php foreach ( $propuesta['colores_sugeridos'] ?? [] as $nombre => $color ) : ?>
                        <div class="color-swatch" style="background: <?php echo esc_attr( $color ); ?>">
                            <?php echo esc_html( $nombre ); ?>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
        <?php
        return ob_get_clean();
    }

    /**
     * Obtener casos de uso disponibles
     *
     * @return array
     */
    public function get_casos_uso() {
        return $this->casos_uso_modulos;
    }

    /**
     * Obtener templates de páginas
     *
     * @return array
     */
    public function get_page_templates() {
        return $this->page_templates;
    }
}

// Inicializar
Flavor_App_Generator::get_instance();

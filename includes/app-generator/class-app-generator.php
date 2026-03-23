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
            // ========== PACKS EMPRESARIALES ==========
            'empresa_pyme' => [
                'keywords' => [ 'empresa', 'pyme', 'negocio', 'startup', 'coworking', 'oficina', 'compañía', 'corporativo', 'comercial' ],
                'modulos' => [ 'clientes', 'fichaje-empleados', 'facturas', 'reservas', 'incidencias', 'foros', 'avisos-municipales' ],
                'descripcion' => 'Plataforma completa para empresas y PYMEs',
                'paginas_sugeridas' => [
                    'inicio', 'mi-portal', 'fichar', 'clientes', 'facturas',
                    'reservar-sala', 'directorio', 'comunicados', 'soporte'
                ],
                'es_pack' => true,
            ],

            // Gestión de personas
            'socios' => [
                'keywords' => [ 'socios', 'miembros', 'membresía', 'cuotas', 'asociados', 'afiliados' ],
                'modulo' => 'socios',
                'descripcion' => 'Gestión de miembros y cuotas',
                'paginas_sugeridas' => [ 'mis-datos', 'pagar-cuota' ],
            ],
            'empleados' => [
                'keywords' => [ 'empleados', 'trabajadores', 'fichaje', 'horarios', 'turnos', 'nóminas', 'asistencia', 'control horario', 'jornada', 'plantilla', 'rrhh', 'recursos humanos', 'portal empleado' ],
                'modulo' => 'fichaje-empleados',
                'descripcion' => 'Control de fichaje y asistencia de empleados',
                'paginas_sugeridas' => [ 'fichar', 'mis-fichajes', 'mi-portal' ],
            ],
            'clientes' => [
                'keywords' => [ 'clientes', 'crm', 'contactos', 'comercial', 'ventas', 'leads', 'oportunidades', 'cartera', 'base de datos clientes' ],
                'modulo' => 'clientes',
                'descripcion' => 'CRM básico para gestión de clientes',
                'paginas_sugeridas' => [ 'clientes', 'nuevo-cliente', 'seguimiento' ],
            ],
            'facturacion' => [
                'keywords' => [ 'facturas', 'facturación', 'presupuestos', 'cobros', 'pagos', 'contabilidad', 'albaranes', 'recibos', 'tesorería' ],
                'modulo' => 'facturas',
                'descripcion' => 'Sistema de facturación y presupuestos',
                'paginas_sugeridas' => [ 'facturas', 'nueva-factura', 'presupuestos' ],
            ],
            'directorio' => [
                'keywords' => [ 'directorio', 'organigrama', 'equipo', 'quien es quien', 'contactos internos', 'departamentos' ],
                'modulo' => 'mapa-actores',
                'descripcion' => 'Directorio de empleados y organigrama',
                'paginas_sugeridas' => [ 'directorio', 'equipo', 'departamentos' ],
            ],
            'comunicacion_interna' => [
                'keywords' => [ 'comunicación interna', 'anuncios', 'comunicados', 'noticias internas', 'tablón', 'intranet', 'avisos' ],
                'modulo' => 'avisos-municipales',
                'descripcion' => 'Tablón de anuncios y comunicación interna',
                'paginas_sugeridas' => [ 'comunicados', 'anuncios', 'noticias' ],
            ],
            'documentos' => [
                'keywords' => [ 'documentos', 'archivos', 'nóminas', 'contratos', 'repositorio', 'gestión documental', 'expedientes' ],
                'modulo' => 'biblioteca',
                'descripcion' => 'Repositorio de documentos y nóminas',
                'paginas_sugeridas' => [ 'documentos', 'mis-documentos', 'nominas' ],
            ],
            'proyectos' => [
                'keywords' => [ 'proyectos', 'tareas', 'gestión proyectos', 'seguimiento', 'hitos', 'planificación' ],
                'modulo' => 'colectivos',
                'descripcion' => 'Gestión de proyectos y equipos de trabajo',
                'paginas_sugeridas' => [ 'proyectos', 'mis-tareas', 'equipo' ],
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
                'keywords' => [ 'reservas', 'reservar', 'disponibilidad', 'booking', 'citas', 'salas', 'salas de reuniones', 'sala reunión', 'equipamiento', 'recursos' ],
                'modulo' => 'reservas',
                'descripcion' => 'Sistema de reservas de salas y recursos',
                'paginas_sugeridas' => [ 'reservar', 'mis-reservas', 'disponibilidad' ],
            ],
            'espacios' => [
                'keywords' => [ 'espacios', 'espacios comunes', 'instalaciones', 'local', 'alquiler' ],
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
            'empresarial'      => [ 'corporate', 'pyme', 'naarq', 'startup', 'consultoria', 'minimal' ],
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
            'corporate'            => [ 'label' => 'Corporate', 'desc' => 'Empresas y Startups', 'color' => '#1e40af', 'icon' => 'dashicons-businessman' ],
            'pyme'                 => [ 'label' => 'PYME', 'desc' => 'Pequeña y Mediana Empresa', 'color' => '#0369a1', 'icon' => 'dashicons-store' ],
            'startup'              => [ 'label' => 'Startup', 'desc' => 'Startup Tecnológica', 'color' => '#7c3aed', 'icon' => 'dashicons-lightbulb' ],
            'consultoria'          => [ 'label' => 'Consultoría', 'desc' => 'Servicios Profesionales', 'color' => '#0f766e', 'icon' => 'dashicons-chart-line' ],
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
        $ia_status = [
            'disponible' => false,
            'motor' => null,
            'configurado' => false,
            'usado' => false,
            'error' => null,
        ];

        // Diagnóstico del motor
        if ( $engine ) {
            $ia_status['disponible'] = true;
            $ia_status['motor'] = method_exists( $engine, 'get_id' ) ? $engine->get_id() : 'desconocido';
            $ia_status['configurado'] = $engine->is_configured();
        }

        // Si no hay motor de IA configurado, usar análisis local
        if ( ! $engine || ! $engine->is_configured() ) {
            $ia_status['error'] = ! $engine ? 'Motor no disponible' : 'Motor no configurado (falta API key)';
            $resultado = $this->analizar_requisitos_local( $descripcion );
            $resultado['ia_status'] = $ia_status;
            return $resultado;
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
                $ia_status['usado'] = true;
                $parsed = $this->parsear_respuesta_ia( $resultado['response'] );
                $parsed['ia_status'] = $ia_status;
                return $parsed;
            }

            // Si la IA falló, usar análisis local
            $ia_status['error'] = $resultado['error'] ?? 'La IA no devolvió respuesta válida';
            $resultado_local = $this->analizar_requisitos_local( $descripcion );
            $resultado_local['ia_status'] = $ia_status;
            return $resultado_local;

        } catch ( Exception $e ) {
            // Fallback a análisis local
            $ia_status['error'] = $e->getMessage();
            $resultado_local = $this->analizar_requisitos_local( $descripcion );
            $resultado_local['ia_status'] = $ia_status;
            return $resultado_local;
        }
    }

    /**
     * Construir system prompt para la IA
     *
     * @return string
     */
    private function construir_system_prompt() {
        $prompt = "Eres un experto arquitecto de plataformas con Flavor Platform.
Tu rol es analizar las necesidades del usuario y proponer TODOS los módulos que necesiten.

REGLAS CRÍTICAS:
1. Lee TODA la descripción del usuario cuidadosamente
2. Por cada necesidad mencionada, busca el módulo correspondiente en el catálogo
3. NO limites la cantidad de módulos - si el usuario necesita 8 módulos, propón 8
4. Para EMPRESAS/PYMEs típicamente necesitan: clientes, fichaje-empleados, facturas, reservas, avisos-municipales, biblioteca, foros, incidencias
5. Analiza cada frase de la descripción y mapea a módulos

EJEMPLO - Si el usuario dice:
'Empresa con 30 empleados. Necesitamos fichaje, clientes, facturas, reserva salas, portal empleado, directorio, comunicación interna, proyectos'

Debes proponer MÍNIMO estos módulos:
- fichaje-empleados (fichaje y asistencia)
- clientes (CRM)
- facturas (facturación y presupuestos)
- reservas (salas de reuniones)
- biblioteca (portal con documentos y nóminas)
- mapa-actores (directorio empleados)
- avisos-municipales (comunicación interna)
- colectivos (proyectos y equipos)
- foros (comunicación)

SIEMPRE responde SOLO con JSON válido, sin explicaciones.";

        return $prompt;
    }

    /**
     * Construir prompt para análisis con IA
     *
     * @param string $descripcion Descripción del proyecto.
     * @param array  $documentos  Documentos.
     * @return string
     */
    private function construir_prompt_analisis( $descripcion, $documentos = [] ) {
        // Obtener catálogo completo de módulos
        $modulos_texto = $this->get_modulos_para_prompt();

        // Obtener addons disponibles
        $addons_texto = $this->get_addons_para_prompt();

        // Obtener perfiles disponibles
        $perfiles_texto = $this->get_perfiles_para_prompt();

        // Obtener temas disponibles
        $temas_texto = $this->get_temas_para_prompt();

        $prompt = <<<PROMPT
Analiza la siguiente descripción de proyecto y proporciona una estructura recomendada usando los módulos de Flavor Platform.

## DESCRIPCIÓN DEL PROYECTO:
{$descripcion}

## CATÁLOGO COMPLETO DE MÓDULOS DISPONIBLES:
{$modulos_texto}

## ADDONS DISPONIBLES:
{$addons_texto}

## PERFILES DE APLICACIÓN:
{$perfiles_texto}

## TEMAS VISUALES DISPONIBLES:
{$temas_texto}

## INSTRUCCIONES:
1. Analiza qué necesita el usuario según su descripción
2. Selecciona los módulos más relevantes (no todos, solo los necesarios)
3. Sugiere un perfil de aplicación adecuado
4. Recomienda un tema visual que encaje con el tipo de organización
5. Propón páginas útiles basadas en los módulos seleccionados

## RESPONDE EN FORMATO JSON:
{
    "nombre_proyecto": "Nombre sugerido para el proyecto",
    "tipo_comunidad": "colectivo_social|vecinal|deportiva|cultural|educativa|empresarial|ecologica|consumo|cuidados|gastronomia|finanzas|medios|otra",
    "perfil_recomendado": "asociacion|ayuntamiento|cooperativa|empresa|comunidad|educativo",
    "descripcion_corta": "Descripción de 1-2 frases",
    "modulos_recomendados": ["modulo1", "modulo2"],
    "modulos_opcionales": ["modulo3"],
    "paginas_sugeridas": [
        {"slug": "inicio", "titulo": "Inicio", "template": "home", "descripcion": "Página principal"},
        {"slug": "mi-panel", "titulo": "Mi Panel", "template": "dashboard_miembro", "descripcion": "Dashboard del usuario"}
    ],
    "tema_recomendado": "id-del-tema",
    "colores_sugeridos": {
        "primario": "#3b82f6",
        "secundario": "#8b5cf6",
        "acento": "#10b981"
    },
    "funcionalidades_clave": ["func1", "func2", "func3"],
    "publico_objetivo": "Descripción del público objetivo",
    "addons_sugeridos": ["addon1"]
}

Solo responde con el JSON, sin texto adicional.
PROMPT;

        return $prompt;
    }

    /**
     * Obtener lista de módulos formateada para el prompt
     *
     * @return string
     */
    private function get_modulos_para_prompt() {
        $catalogo = $this->get_modules_catalog();
        $lineas = [];

        $categorias = [
            'gestion' => 'GESTIÓN DE MIEMBROS',
            'actividades' => 'ACTIVIDADES Y EVENTOS',
            'espacios' => 'ESPACIOS Y RESERVAS',
            'comunicacion' => 'COMUNICACIÓN',
            'participacion' => 'PARTICIPACIÓN CIUDADANA',
            'economia' => 'ECONOMÍA Y FINANZAS',
            'sostenibilidad' => 'SOSTENIBILIDAD',
            'contenidos' => 'CONTENIDOS Y MULTIMEDIA',
        ];

        foreach ( $categorias as $cat_id => $cat_nombre ) {
            $modulos_cat = array_filter( $catalogo, fn($m) => ($m['categoria'] ?? '') === $cat_id );
            if ( ! empty( $modulos_cat ) ) {
                $lineas[] = "\n### {$cat_nombre}:";
                foreach ( $modulos_cat as $id => $mod ) {
                    $lineas[] = "- {$id}: {$mod['descripcion']}";
                }
            }
        }

        return implode( "\n", $lineas );
    }

    /**
     * Obtener lista de addons formateada para el prompt
     *
     * @return string
     */
    private function get_addons_para_prompt() {
        $addons = [
            'flavor-admin-assistant' => 'Asistente IA avanzado con atajos y comandos para administradores',
            'flavor-web-builder-pro' => 'Constructor visual de páginas con bloques personalizados',
            'flavor-network-communities' => 'Red de comunidades conectadas (multisite)',
            'flavor-restaurant-ordering' => 'Sistema de pedidos y reservas para restaurantes',
            'flavor-demo-orchestrator' => 'Generador de datos de demostración para pruebas',
        ];

        $lineas = [];
        $addons_dir = FLAVOR_CHAT_IA_PATH . 'addons/';

        foreach ( $addons as $slug => $desc ) {
            $instalado = is_dir( $addons_dir . $slug ) ? '(instalado)' : '(disponible)';
            $lineas[] = "- {$slug}: {$desc} {$instalado}";
        }

        return implode( "\n", $lineas );
    }

    /**
     * Obtener lista de perfiles formateada para el prompt
     *
     * @return string
     */
    private function get_perfiles_para_prompt() {
        $perfiles = [
            'asociacion' => [
                'nombre' => 'Asociación/Colectivo',
                'ideal' => 'asociaciones vecinales, culturales, deportivas, ONGs',
                'modulos' => 'socios, eventos, foros, reservas, encuestas',
            ],
            'ayuntamiento' => [
                'nombre' => 'Ayuntamiento/Institución',
                'ideal' => 'ayuntamientos, administraciones públicas, instituciones',
                'modulos' => 'avisos-municipales, tramites, participacion, transparencia, incidencias',
            ],
            'cooperativa' => [
                'nombre' => 'Cooperativa',
                'ideal' => 'cooperativas de trabajo, consumo, vivienda o servicios',
                'modulos' => 'socios, grupos-consumo, transparencia, encuestas, foros',
            ],
            'empresa' => [
                'nombre' => 'Empresa/PYME',
                'ideal' => 'pequeñas y medianas empresas, startups, coworkings',
                'modulos' => 'clientes, facturas, fichaje-empleados, reservas, incidencias',
            ],
            'comunidad' => [
                'nombre' => 'Comunidad de Vecinos',
                'ideal' => 'comunidades de propietarios, urbanizaciones',
                'modulos' => 'comunidades, incidencias, reservas, foros, encuestas',
            ],
            'educativo' => [
                'nombre' => 'Centro Educativo',
                'ideal' => 'escuelas, academias, centros de formación',
                'modulos' => 'cursos, talleres, biblioteca, eventos, foros',
            ],
        ];

        $lineas = [];
        foreach ( $perfiles as $id => $info ) {
            $lineas[] = "- {$id} ({$info['nombre']}): Ideal para {$info['ideal']}. Módulos típicos: {$info['modulos']}";
        }

        return implode( "\n", $lineas );
    }

    /**
     * Obtener lista de temas formateada para el prompt
     *
     * @return string
     */
    private function get_temas_para_prompt() {
        $temas = $this->get_temas_disponibles();
        $lineas = [];

        // Agrupar por tipo de uso
        $grupos = [
            'Comunidades y Colectivos' => ['comunidad-viva', 'denendako', 'pueblo-vivo', 'ecos-comunitarios', 'democracia-universal'],
            'Ecología y Sostenibilidad' => ['zunbeltz', 'grupos-consumo', 'mercado-espiral', 'forest-green'],
            'Cultura y Educación' => ['kulturaka', 'campi', 'academia-espiral', 'escena-familiar'],
            'Empresas y Negocios' => ['corporate', 'pyme', 'startup', 'consultoria', 'naarq', 'minimal'],
            'Servicios Especializados' => ['jantoki', 'spiral-bank', 'red-cuidados', 'flujo'],
            'Generales' => ['default', 'dark-mode', 'ocean-blue', 'sunset-orange'],
        ];

        foreach ( $grupos as $grupo_nombre => $tema_ids ) {
            $lineas[] = "\n### {$grupo_nombre}:";
            foreach ( $tema_ids as $tema_id ) {
                if ( isset( $temas[$tema_id] ) ) {
                    $tema = $temas[$tema_id];
                    $lineas[] = "- {$tema_id}: {$tema['label']} - {$tema['desc']}";
                }
            }
        }

        return implode( "\n", $lineas );
    }

    /**
     * Obtener catálogo completo de módulos
     * Versión extendida con todos los módulos disponibles
     *
     * @return array
     */
    private function get_modules_catalog() {
        return [
            // Gestión de Miembros
            'socios' => [
                'nombre' => 'Socios/Miembros',
                'descripcion' => 'Gestión completa de miembros, cuotas, carnets digitales y directorio',
                'categoria' => 'gestion',
            ],
            'clientes' => [
                'nombre' => 'Clientes',
                'descripcion' => 'CRM básico para gestionar clientes y relaciones comerciales',
                'categoria' => 'gestion',
            ],
            'comunidades' => [
                'nombre' => 'Comunidades',
                'descripcion' => 'Gestión de comunidades de vecinos y propietarios',
                'categoria' => 'gestion',
            ],
            'colectivos' => [
                'nombre' => 'Colectivos',
                'descripcion' => 'Grupos de trabajo, comisiones y equipos internos',
                'categoria' => 'gestion',
            ],
            'incidencias' => [
                'nombre' => 'Incidencias',
                'descripcion' => 'Sistema de tickets de soporte, averías y mantenimiento',
                'categoria' => 'gestion',
            ],
            'tramites' => [
                'nombre' => 'Trámites',
                'descripcion' => 'Gestión de trámites, solicitudes y expedientes',
                'categoria' => 'gestion',
            ],
            'fichaje-empleados' => [
                'nombre' => 'Fichaje',
                'descripcion' => 'Control de horarios y fichaje de empleados',
                'categoria' => 'gestion',
            ],
            'ayuda-vecinal' => [
                'nombre' => 'Ayuda Vecinal',
                'descripcion' => 'Red de ayuda mutua entre vecinos',
                'categoria' => 'gestion',
            ],
            'seguimiento-denuncias' => [
                'nombre' => 'Seguimiento Denuncias',
                'descripcion' => 'Sistema de seguimiento de denuncias ciudadanas',
                'categoria' => 'gestion',
            ],

            // Actividades y Eventos
            'eventos' => [
                'nombre' => 'Eventos',
                'descripcion' => 'Calendario de eventos con inscripciones y gestión de asistentes',
                'categoria' => 'actividades',
            ],
            'cursos' => [
                'nombre' => 'Cursos',
                'descripcion' => 'Formación online con lecciones, matrículas y certificados',
                'categoria' => 'actividades',
            ],
            'talleres' => [
                'nombre' => 'Talleres',
                'descripcion' => 'Talleres presenciales con inscripciones y materiales',
                'categoria' => 'actividades',
            ],
            'campanias' => [
                'nombre' => 'Campañas',
                'descripcion' => 'Campañas de recogida de firmas y sensibilización',
                'categoria' => 'actividades',
            ],

            // Espacios y Reservas
            'reservas' => [
                'nombre' => 'Reservas',
                'descripcion' => 'Sistema de reservas de recursos, salas y equipamiento',
                'categoria' => 'espacios',
            ],
            'espacios-comunes' => [
                'nombre' => 'Espacios Comunes',
                'descripcion' => 'Gestión de espacios compartidos con calendario',
                'categoria' => 'espacios',
            ],
            'parkings' => [
                'nombre' => 'Parkings',
                'descripcion' => 'Gestión de plazas de parking y rotación',
                'categoria' => 'espacios',
            ],

            // Comunicación
            'foros' => [
                'nombre' => 'Foros',
                'descripcion' => 'Foros de discusión por categorías y temas',
                'categoria' => 'comunicacion',
            ],
            'chat-interno' => [
                'nombre' => 'Chat Interno',
                'descripcion' => 'Mensajería privada entre miembros',
                'categoria' => 'comunicacion',
            ],
            'chat-grupos' => [
                'nombre' => 'Chat de Grupos',
                'descripcion' => 'Salas de chat grupales por colectivo o tema',
                'categoria' => 'comunicacion',
            ],
            'avisos-municipales' => [
                'nombre' => 'Avisos/Tablón',
                'descripcion' => 'Tablón de anuncios y avisos oficiales',
                'categoria' => 'comunicacion',
            ],
            'email-marketing' => [
                'nombre' => 'Email Marketing',
                'descripcion' => 'Newsletters, listas de correo y automatizaciones',
                'categoria' => 'comunicacion',
            ],
            'red-social' => [
                'nombre' => 'Red Social',
                'descripcion' => 'Red social interna con publicaciones, likes y seguimiento',
                'categoria' => 'comunicacion',
            ],

            // Participación
            'encuestas' => [
                'nombre' => 'Encuestas',
                'descripcion' => 'Encuestas y votaciones con resultados en tiempo real',
                'categoria' => 'participacion',
            ],
            'participacion' => [
                'nombre' => 'Participación',
                'descripcion' => 'Propuestas ciudadanas y debates participativos',
                'categoria' => 'participacion',
            ],
            'presupuestos-participativos' => [
                'nombre' => 'Presupuestos Participativos',
                'descripcion' => 'Votación de proyectos con asignación de presupuesto',
                'categoria' => 'participacion',
            ],
            'transparencia' => [
                'nombre' => 'Transparencia',
                'descripcion' => 'Portal de transparencia con presupuestos, actas y contratos',
                'categoria' => 'participacion',
            ],
            'justicia-restaurativa' => [
                'nombre' => 'Justicia Restaurativa',
                'descripcion' => 'Mediación y resolución de conflictos comunitarios',
                'categoria' => 'participacion',
            ],

            // Economía
            'marketplace' => [
                'nombre' => 'Marketplace',
                'descripcion' => 'Tienda de productos y servicios entre miembros',
                'categoria' => 'economia',
            ],
            'grupos-consumo' => [
                'nombre' => 'Grupos de Consumo',
                'descripcion' => 'Pedidos colectivos a productores locales',
                'categoria' => 'economia',
            ],
            'banco-tiempo' => [
                'nombre' => 'Banco de Tiempo',
                'descripcion' => 'Intercambio de servicios y habilidades por tiempo',
                'categoria' => 'economia',
            ],
            'crowdfunding' => [
                'nombre' => 'Crowdfunding',
                'descripcion' => 'Financiación colectiva de proyectos',
                'categoria' => 'economia',
            ],
            'facturas' => [
                'nombre' => 'Facturación',
                'descripcion' => 'Emisión de facturas y gestión de cobros',
                'categoria' => 'economia',
            ],
            'economia-don' => [
                'nombre' => 'Economía del Don',
                'descripcion' => 'Sistema de regalos y donaciones entre miembros',
                'categoria' => 'economia',
            ],
            'trabajo-digno' => [
                'nombre' => 'Trabajo Digno',
                'descripcion' => 'Bolsa de trabajo con ofertas y demandas laborales',
                'categoria' => 'economia',
            ],

            // Sostenibilidad
            'huertos-urbanos' => [
                'nombre' => 'Huertos Urbanos',
                'descripcion' => 'Gestión de parcelas y huertos comunitarios',
                'categoria' => 'sostenibilidad',
            ],
            'compostaje' => [
                'nombre' => 'Compostaje',
                'descripcion' => 'Puntos de compostaje comunitario',
                'categoria' => 'sostenibilidad',
            ],
            'bicicletas-compartidas' => [
                'nombre' => 'Bicicletas',
                'descripcion' => 'Sistema de préstamo de bicicletas',
                'categoria' => 'sostenibilidad',
            ],
            'carpooling' => [
                'nombre' => 'Carpooling',
                'descripcion' => 'Compartir coche para trayectos',
                'categoria' => 'sostenibilidad',
            ],
            'reciclaje' => [
                'nombre' => 'Reciclaje',
                'descripcion' => 'Puntos de reciclaje y gamificación ecológica',
                'categoria' => 'sostenibilidad',
            ],
            'huella-ecologica' => [
                'nombre' => 'Huella Ecológica',
                'descripcion' => 'Calculadora y seguimiento de huella de carbono',
                'categoria' => 'sostenibilidad',
            ],
            'biodiversidad-local' => [
                'nombre' => 'Biodiversidad Local',
                'descripcion' => 'Catálogo de fauna y flora local con avistamientos',
                'categoria' => 'sostenibilidad',
            ],
            'energia-comunitaria' => [
                'nombre' => 'Energía Comunitaria',
                'descripcion' => 'Gestión de comunidades energéticas',
                'categoria' => 'sostenibilidad',
            ],

            // Contenidos
            'biblioteca' => [
                'nombre' => 'Biblioteca',
                'descripcion' => 'Catálogo de libros con préstamos y reservas',
                'categoria' => 'contenidos',
            ],
            'multimedia' => [
                'nombre' => 'Multimedia',
                'descripcion' => 'Galería de fotos, vídeos y documentos',
                'categoria' => 'contenidos',
            ],
            'podcast' => [
                'nombre' => 'Podcast',
                'descripcion' => 'Publicación y gestión de podcasts',
                'categoria' => 'contenidos',
            ],
            'radio' => [
                'nombre' => 'Radio',
                'descripcion' => 'Radio comunitaria con programación',
                'categoria' => 'contenidos',
            ],
            'recetas' => [
                'nombre' => 'Recetas',
                'descripcion' => 'Recetario colaborativo de la comunidad',
                'categoria' => 'contenidos',
            ],
            'saberes-ancestrales' => [
                'nombre' => 'Saberes Ancestrales',
                'descripcion' => 'Documentación de conocimientos tradicionales',
                'categoria' => 'contenidos',
            ],
            'mapa-actores' => [
                'nombre' => 'Mapa de Actores',
                'descripcion' => 'Directorio geolocalizado de actores y recursos',
                'categoria' => 'contenidos',
            ],
            'kulturaka' => [
                'nombre' => 'Kulturaka',
                'descripcion' => 'Agenda cultural con artistas y espacios',
                'categoria' => 'contenidos',
            ],
            'documentacion-legal' => [
                'nombre' => 'Documentación Legal',
                'descripcion' => 'Repositorio de documentos legales y estatutos',
                'categoria' => 'contenidos',
            ],
        ];
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
        $es_pack_aplicado = false;

        // PASO 1: Detectar primero los PACKS (tienen prioridad)
        foreach ( $this->casos_uso_modulos as $caso => $config ) {
            if ( empty( $config['es_pack'] ) ) {
                continue;
            }
            foreach ( $config['keywords'] as $keyword ) {
                if ( strpos( $descripcion_lower, $keyword ) !== false ) {
                    $modulos_detectados[ $caso ] = $config;
                    $es_pack_aplicado = true;

                    // Añadir todos los módulos del pack
                    if ( ! empty( $config['modulos'] ) && is_array( $config['modulos'] ) ) {
                        foreach ( $config['modulos'] as $modulo ) {
                            if ( ! in_array( $modulo, $modulos_lista, true ) ) {
                                $modulos_lista[] = $modulo;
                            }
                        }
                    }
                    break 2; // Salir de ambos bucles, un pack es suficiente
                }
            }
        }

        // PASO 2: Detectar módulos individuales por keywords
        foreach ( $this->casos_uso_modulos as $caso => $config ) {
            // Si ya es un pack detectado, saltar
            if ( ! empty( $config['es_pack'] ) ) {
                continue;
            }

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

        // Detectar tipo de comunidad (empresarial tiene prioridad si se detectan keywords empresariales)
        $tipo = 'otra';

        // Detectar primero empresarial (más específico)
        if ( preg_match( '/empresa|pyme|negocio|cowork|profesional|startup|empleado|fichaje|factura|crm|cliente|comercial|corporativo|oficina|rrhh|nómina|plantilla/i', $descripcion ) ) {
            $tipo = 'empresarial';
        } elseif ( preg_match( '/colectivo|ong|movimiento|activis|social/i', $descripcion ) ) {
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
        }

        // Nombre según tipo
        $nombres_por_tipo = [
            'colectivo_social' => 'Mi Colectivo',
            'vecinal'          => 'Mi Comunidad',
            'deportiva'        => 'Mi Club',
            'cultural'         => 'Mi Asociación Cultural',
            'educativa'        => 'Mi Centro',
            'empresarial'      => 'Mi Empresa',
            'ecologica'        => 'Mi Iniciativa Verde',
            'consumo'          => 'Mi Grupo de Consumo',
            'cuidados'         => 'Mi Red de Cuidados',
        ];

        // Para tipo empresarial, asegurar perfil recomendado
        $perfil_recomendado = 'asociacion';
        if ( $tipo === 'empresarial' ) {
            $perfil_recomendado = 'empresa';
        } elseif ( $tipo === 'educativa' ) {
            $perfil_recomendado = 'educativo';
        } elseif ( $tipo === 'vecinal' ) {
            $perfil_recomendado = 'comunidad';
        }

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

        // Público objetivo según tipo
        $publicos = [
            'empresarial' => 'Empleados y equipo de la empresa',
            'colectivo_social' => 'Miembros del colectivo y activistas',
            'vecinal' => 'Vecinos y residentes de la comunidad',
            'educativa' => 'Alumnos, profesores y personal educativo',
        ];

        return [
            'nombre_proyecto'       => $nombres_por_tipo[ $tipo ] ?? 'Mi Comunidad',
            'tipo_comunidad'        => $tipo,
            'perfil_recomendado'    => $perfil_recomendado,
            'descripcion_corta'     => substr( $descripcion, 0, 150 ),
            'modulos_recomendados'  => array_values( array_unique( $modulos_lista ) ),
            'modulos_opcionales'    => [],
            'paginas_sugeridas'     => $paginas_sugeridas,
            'colores_sugeridos'     => $colores_sugeridos,
            'tema_recomendado'      => $tema_principal,
            'temas_recomendados'    => $temas_recomendados,
            'todos_los_temas'       => $todos_los_temas,
            'funcionalidades_clave' => array_column( array_values( $modulos_detectados ), 'descripcion' ),
            'publico_objetivo'      => $publicos[ $tipo ] ?? 'Miembros de la organización',
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

        // Validar módulos contra el catálogo completo (no solo casos_uso)
        $catalogo_modulos = array_keys( $this->get_modules_catalog() );

        // También incluir módulos de casos_uso
        foreach ( $this->casos_uso_modulos as $caso => $config ) {
            if ( ! empty( $config['modulo'] ) && ! in_array( $config['modulo'], $catalogo_modulos, true ) ) {
                $catalogo_modulos[] = $config['modulo'];
            }
            if ( ! empty( $config['modulos'] ) && is_array( $config['modulos'] ) ) {
                foreach ( $config['modulos'] as $mod ) {
                    if ( ! in_array( $mod, $catalogo_modulos, true ) ) {
                        $catalogo_modulos[] = $mod;
                    }
                }
            }
        }

        // Filtrar solo módulos que existen en el catálogo
        $datos['modulos_recomendados'] = array_values( array_filter(
            $datos['modulos_recomendados'],
            function( $m ) use ( $catalogo_modulos ) {
                return in_array( $m, $catalogo_modulos, true );
            }
        ) );

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
            // Solo escribir a flavor_chat_ia_settings (fuente única)
            $configuracion['active_modules'] = $modulos_activos;
            update_option( 'flavor_chat_ia_settings', $configuracion );
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

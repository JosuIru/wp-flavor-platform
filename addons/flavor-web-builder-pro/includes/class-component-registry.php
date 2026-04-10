<?php
/**
 * Component Registry - Sistema central de componentes web
 *
 * @package FlavorPlatform
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Registro y gestión de componentes web flexibles
 */
class Flavor_Component_Registry {

    /**
     * Instancia singleton
     */
    private static $instance = null;

    /**
     * Componentes registrados
     */
    private $components = [];

    /**
     * Categorías de componentes
     */
    private $categories = [];

    /**
     * Flag para saber si ya se cargaron los componentes de módulos
     */
    private $modules_loaded = false;

    /**
     * Flag para saber si los componentes unificados fueron registrados
     */
    private $componentes_unificados_registrados = false;

    /**
     * Mapa de alias: IDs viejos → componente unificado + preset
     */
    private $alias_mapa_componentes = [
        'agencia_hero' => ['target' => 'unified_hero', 'preset' => 'agencia'],
        'agencia_portfolio' => ['target' => 'unified_galeria', 'preset' => 'agencia_portfolio'],
        'albumes' => ['target' => 'unified_galeria', 'preset' => 'albumes'],
        'ayuda_vecinal_categorias' => ['target' => 'unified_navegacion', 'preset' => 'ayuda_vecinal'],
        'ayuda_vecinal_cta' => ['target' => 'unified_cta', 'preset' => 'ayuda_vecinal'],
        'ayuda_vecinal_hero' => ['target' => 'unified_hero', 'preset' => 'ayuda_vecinal'],
        'ayuda_vecinal_ofertas' => ['target' => 'unified_grid', 'preset' => 'ayuda_vecinal_ofertas'],
        'ayuda_vecinal_solicitudes' => ['target' => 'unified_listing', 'preset' => 'ayuda_vecinal_solicitudes'],
        'ayuntamiento_hero' => ['target' => 'unified_hero', 'preset' => 'ayuntamiento'],
        'ayuntamiento_noticias' => ['target' => 'unified_contenido', 'preset' => 'ayuntamiento_noticias'],
        'ayuntamiento_tramites' => ['target' => 'unified_grid', 'preset' => 'ayuntamiento_tramites'],
        'banco_tiempo_categorias' => ['target' => 'unified_navegacion', 'preset' => 'banco_tiempo'],
        'banco_tiempo_como_funciona' => ['target' => 'unified_proceso', 'preset' => 'banco_tiempo'],
        'banco_tiempo_cta_unirse' => ['target' => 'unified_cta', 'preset' => 'banco_tiempo_unirse'],
        'banco_tiempo_estadisticas' => ['target' => 'unified_stats', 'preset' => 'banco_tiempo'],
        'banco_tiempo_hero' => ['target' => 'unified_hero', 'preset' => 'banco_tiempo'],
        'banco_tiempo_servicios' => ['target' => 'unified_features', 'preset' => 'banco_tiempo_servicios'],
        'biblioteca_buscador' => ['target' => 'unified_navegacion', 'preset' => 'biblioteca_buscador'],
        'biblioteca_como_funciona' => ['target' => 'unified_proceso', 'preset' => 'biblioteca'],
        'biblioteca_cta_donar' => ['target' => 'unified_cta', 'preset' => 'biblioteca_donar'],
        'biblioteca_hero' => ['target' => 'unified_hero', 'preset' => 'biblioteca'],
        'biblioteca_libros_grid' => ['target' => 'unified_grid', 'preset' => 'biblioteca_libros'],
        'bicicletas_compartidas_como_funciona' => ['target' => 'unified_proceso', 'preset' => 'bicicletas_compartidas'],
        'bicicletas_compartidas_hero' => ['target' => 'unified_hero', 'preset' => 'bicicletas_compartidas'],
        'bicicletas_compartidas_mapa' => ['target' => 'unified_mapa', 'preset' => 'bicicletas_compartidas'],
        'bicicletas_compartidas_tarifas' => ['target' => 'unified_pricing', 'preset' => 'bicicletas_compartidas'],
        'bicicletas_hero' => ['target' => 'unified_hero', 'preset' => 'bicicletas'],
        'carousel_destacado' => ['target' => 'unified_galeria', 'preset' => 'carousel_destacado'],
        'carpooling_como_funciona' => ['target' => 'unified_proceso', 'preset' => 'carpooling'],
        'carpooling_cta_conductor' => ['target' => 'unified_cta', 'preset' => 'carpooling_conductor'],
        'carpooling_hero' => ['target' => 'unified_hero', 'preset' => 'carpooling'],
        'carpooling_viajes_grid' => ['target' => 'unified_grid', 'preset' => 'carpooling_viajes'],
        'categorias_talleres' => ['target' => 'unified_navegacion', 'preset' => 'talleres'],
        'chat_grupos_grid' => ['target' => 'unified_grid', 'preset' => 'chat_grupos'],
        'chat_grupos_hero_landing' => ['target' => 'unified_hero', 'preset' => 'chat_grupos'],
        'chat_interno_features' => ['target' => 'unified_features', 'preset' => 'chat_interno'],
        'chat_interno_hero_landing' => ['target' => 'unified_hero', 'preset' => 'chat_interno'],
        'comunidades_actividad_reciente' => ['target' => 'unified_contenido', 'preset' => 'comunidades_actividad'],
        'comunidades_cta_crear' => ['target' => 'unified_cta', 'preset' => 'comunidades_crear'],
        'comunidades_estadisticas' => ['target' => 'unified_stats', 'preset' => 'comunidades'],
        'comunidades_hero' => ['target' => 'unified_hero', 'preset' => 'comunidades'],
        'comunidades_listado' => ['target' => 'unified_listing', 'preset' => 'comunidades'],
        'comunidades_mapa' => ['target' => 'unified_mapa', 'preset' => 'comunidades'],
        'cta_propietario' => ['target' => 'unified_cta', 'preset' => 'propietario'],
        'cursos_categorias' => ['target' => 'unified_navegacion', 'preset' => 'cursos'],
        'cursos_cta_instructor' => ['target' => 'unified_cta', 'preset' => 'cursos_instructor'],
        'cursos_grid' => ['target' => 'unified_grid', 'preset' => 'cursos'],
        'cursos_hero' => ['target' => 'unified_hero', 'preset' => 'cursos'],
        'dex_solana_cta' => ['target' => 'unified_cta', 'preset' => 'dex_solana'],
        'dex_solana_features' => ['target' => 'unified_features', 'preset' => 'dex_solana'],
        'dex_solana_hero' => ['target' => 'unified_hero', 'preset' => 'dex_solana'],
        'empresarial_contacto' => ['target' => 'unified_contacto', 'preset' => 'empresarial'],
        'empresarial_equipo' => ['target' => 'unified_equipo', 'preset' => 'empresarial'],
        'empresarial_hero' => ['target' => 'unified_hero', 'preset' => 'empresarial'],
        'empresarial_portfolio' => ['target' => 'unified_galeria', 'preset' => 'empresarial_portfolio'],
        'empresarial_pricing' => ['target' => 'unified_pricing', 'preset' => 'empresarial'],
        'empresarial_servicios' => ['target' => 'unified_features', 'preset' => 'empresarial_servicios'],
        'empresarial_stats' => ['target' => 'unified_stats', 'preset' => 'empresarial'],
        'empresarial_testimonios' => ['target' => 'unified_testimonios', 'preset' => 'empresarial'],
        'espacios_comunes_calendario' => ['target' => 'unified_calendario', 'preset' => 'espacios_comunes'],
        'espacios_comunes_como_reservar' => ['target' => 'unified_proceso', 'preset' => 'espacios_comunes'],
        'espacios_comunes_hero' => ['target' => 'unified_hero', 'preset' => 'espacios_comunes'],
        'espacios_comunes_listado' => ['target' => 'unified_listing', 'preset' => 'espacios_comunes'],
        'facturas_features' => ['target' => 'unified_features', 'preset' => 'facturas'],
        'facturas_hero' => ['target' => 'unified_hero', 'preset' => 'facturas'],
        'fichaje_features' => ['target' => 'unified_features', 'preset' => 'fichaje'],
        'fichaje_hero' => ['target' => 'unified_hero', 'preset' => 'fichaje'],
        'galeria_grid' => ['target' => 'unified_galeria', 'preset' => 'galeria'],
        'grupos_consumo_como_funciona' => ['target' => 'unified_proceso', 'preset' => 'grupos_consumo'],
        'grupos_consumo_cta_unirse' => ['target' => 'unified_cta', 'preset' => 'grupos_consumo_unirse'],
        'grupos_consumo_hero' => ['target' => 'unified_hero', 'preset' => 'grupos_consumo'],
        'grupos_consumo_listado' => ['target' => 'unified_listing', 'preset' => 'grupos_consumo'],
        'grupos_consumo_productores' => ['target' => 'unified_grid', 'preset' => 'grupos_consumo_productores'],
        'grupos_consumo_proximo_pedido' => ['target' => 'unified_contenido', 'preset' => 'grupos_consumo_proximo_pedido'],
        'guia_compostaje' => ['target' => 'unified_contenido', 'preset' => 'guia_compostaje'],
        'hero_compostaje' => ['target' => 'unified_hero', 'preset' => 'compostaje'],
        'hero_multimedia' => ['target' => 'unified_hero', 'preset' => 'multimedia'],
        'hero_parkings' => ['target' => 'unified_hero', 'preset' => 'parkings'],
        'hero_talleres' => ['target' => 'unified_hero', 'preset' => 'talleres'],
        'huertos_urbanos_beneficios' => ['target' => 'unified_features', 'preset' => 'huertos_urbanos_beneficios'],
        'huertos_urbanos_cta' => ['target' => 'unified_cta', 'preset' => 'huertos_urbanos'],
        'huertos_urbanos_hero' => ['target' => 'unified_hero', 'preset' => 'huertos_urbanos'],
        'huertos_urbanos_mapa' => ['target' => 'unified_mapa', 'preset' => 'huertos_urbanos'],
        'huertos_urbanos_parcelas' => ['target' => 'unified_grid', 'preset' => 'huertos_urbanos_parcelas'],
        'incidencias_categorias' => ['target' => 'unified_navegacion', 'preset' => 'incidencias'],
        'incidencias_cta_reportar' => ['target' => 'unified_cta', 'preset' => 'incidencias_reportar'],
        'incidencias_estadisticas' => ['target' => 'unified_stats', 'preset' => 'incidencias'],
        'incidencias_grid' => ['target' => 'unified_grid', 'preset' => 'incidencias'],
        'incidencias_hero' => ['target' => 'unified_hero', 'preset' => 'incidencias'],
        'incidencias_mapa' => ['target' => 'unified_mapa', 'preset' => 'incidencias'],
        'mapa_composteras' => ['target' => 'unified_mapa', 'preset' => 'composteras'],
        'marketplace_categorias' => ['target' => 'unified_navegacion', 'preset' => 'marketplace'],
        'marketplace_cta' => ['target' => 'unified_cta', 'preset' => 'marketplace'],
        'marketplace_hero' => ['target' => 'unified_hero', 'preset' => 'marketplace'],
        'marketplace_productos_grid' => ['target' => 'unified_grid', 'preset' => 'marketplace_productos'],
        'ofimatica_apps' => ['target' => 'unified_features', 'preset' => 'ofimatica_apps'],
        'ofimatica_features' => ['target' => 'unified_features', 'preset' => 'ofimatica'],
        'ofimatica_hero' => ['target' => 'unified_hero', 'preset' => 'ofimatica'],
        'parkings_grid' => ['target' => 'unified_grid', 'preset' => 'parkings'],
        'participacion_como_participar' => ['target' => 'unified_proceso', 'preset' => 'participacion'],
        'participacion_cta' => ['target' => 'unified_cta', 'preset' => 'participacion'],
        'participacion_hero' => ['target' => 'unified_hero', 'preset' => 'participacion'],
        'participacion_propuestas_grid' => ['target' => 'unified_grid', 'preset' => 'participacion_propuestas'],
        'podcast_categorias' => ['target' => 'unified_navegacion', 'preset' => 'podcast'],
        'podcast_cta_participar' => ['target' => 'unified_cta', 'preset' => 'podcast_participar'],
        'podcast_episodios_grid' => ['target' => 'unified_grid', 'preset' => 'podcast_episodios'],
        'podcast_hero' => ['target' => 'unified_hero', 'preset' => 'podcast'],
        'podcast_presentadores' => ['target' => 'unified_equipo', 'preset' => 'podcast_presentadores'],
        'podcast_suscribir' => ['target' => 'unified_newsletter', 'preset' => 'podcast_suscribir'],
        'podcast_ultimo_episodio' => ['target' => 'unified_contenido', 'preset' => 'podcast_ultimo_episodio'],
        'presupuestos_hero' => ['target' => 'unified_hero', 'preset' => 'presupuestos'],
        'presupuestos_proceso' => ['target' => 'unified_proceso', 'preset' => 'presupuestos'],
        'presupuestos_proyectos_grid' => ['target' => 'unified_grid', 'preset' => 'presupuestos_proyectos'],
        'presupuestos_resultados' => ['target' => 'unified_stats', 'preset' => 'presupuestos_resultados'],
        'proceso_compostaje' => ['target' => 'unified_proceso', 'preset' => 'compostaje'],
        'radio_archivo' => ['target' => 'unified_contenido', 'preset' => 'radio_archivo'],
        'radio_cta_colaborar' => ['target' => 'unified_cta', 'preset' => 'radio_colaborar'],
        'radio_hero' => ['target' => 'unified_hero', 'preset' => 'radio'],
        'radio_locutores' => ['target' => 'unified_equipo', 'preset' => 'radio_locutores'],
        'radio_player_en_vivo' => ['target' => 'unified_contenido', 'preset' => 'radio_player_en_vivo'],
        'radio_programacion' => ['target' => 'unified_contenido', 'preset' => 'radio_programacion'],
        'radio_programas' => ['target' => 'unified_contenido', 'preset' => 'radio_programas'],
        'reciclaje_consejos' => ['target' => 'unified_contenido', 'preset' => 'reciclaje_consejos'],
        'reciclaje_estadisticas' => ['target' => 'unified_stats', 'preset' => 'reciclaje'],
        'reciclaje_guia' => ['target' => 'unified_contenido', 'preset' => 'reciclaje_guia'],
        'reciclaje_hero' => ['target' => 'unified_hero', 'preset' => 'reciclaje'],
        'reciclaje_puntos_mapa' => ['target' => 'unified_mapa', 'preset' => 'reciclaje_puntos'],
        'saas_features' => ['target' => 'unified_features', 'preset' => 'saas'],
        'saas_hero' => ['target' => 'unified_hero', 'preset' => 'saas'],
        'socios_beneficios' => ['target' => 'unified_features', 'preset' => 'socios_beneficios'],
        'socios_cta' => ['target' => 'unified_cta', 'preset' => 'socios'],
        'socios_hero' => ['target' => 'unified_hero', 'preset' => 'socios'],
        'socios_planes_grid' => ['target' => 'unified_pricing', 'preset' => 'socios_planes'],
        'talleres_grid' => ['target' => 'unified_grid', 'preset' => 'talleres'],
        'tienda_local_buscador' => ['target' => 'unified_navegacion', 'preset' => 'tienda_local_buscador'],
        'tienda_local_categorias' => ['target' => 'unified_navegacion', 'preset' => 'tienda_local'],
        'tienda_local_cta_registrar' => ['target' => 'unified_cta', 'preset' => 'tienda_local_registrar'],
        'tienda_local_destacados' => ['target' => 'unified_grid', 'preset' => 'tienda_local_destacados'],
        'tienda_local_hero' => ['target' => 'unified_hero', 'preset' => 'tienda_local'],
        'tienda_local_mapa' => ['target' => 'unified_mapa', 'preset' => 'tienda_local'],
        'tienda_local_ofertas' => ['target' => 'unified_grid', 'preset' => 'tienda_local_ofertas'],
        'trading_ia_features' => ['target' => 'unified_features', 'preset' => 'trading_ia'],
        'trading_ia_hero' => ['target' => 'unified_hero', 'preset' => 'trading_ia'],
        'trading_ia_stats' => ['target' => 'unified_stats', 'preset' => 'trading_ia'],
        'tramites_como_funciona' => ['target' => 'unified_proceso', 'preset' => 'tramites'],
        'tramites_cta' => ['target' => 'unified_cta', 'preset' => 'tramites'],
        'tramites_grid' => ['target' => 'unified_grid', 'preset' => 'tramites'],
        'tramites_hero' => ['target' => 'unified_hero', 'preset' => 'tramites'],
        'transparencia_datos_grid' => ['target' => 'unified_grid', 'preset' => 'transparencia_datos'],
        'transparencia_documentos' => ['target' => 'unified_contenido', 'preset' => 'transparencia_documentos'],
        'transparencia_hero' => ['target' => 'unified_hero', 'preset' => 'transparencia'],
        'transparencia_indicadores' => ['target' => 'unified_stats', 'preset' => 'transparencia_indicadores'],
        'woocommerce_categorias' => ['target' => 'unified_navegacion', 'preset' => 'woocommerce'],
        'woocommerce_cta' => ['target' => 'unified_cta', 'preset' => 'woocommerce'],
        'woocommerce_hero' => ['target' => 'unified_hero', 'preset' => 'woocommerce'],
        'woocommerce_productos_grid' => ['target' => 'unified_grid', 'preset' => 'woocommerce_productos'],
    ];

    /**
     * Obtener instancia singleton
     */
    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Constructor privado
     */
    private function __construct() {
        $this->init();
    }

    /**
     * Inicializar registro
     */
    private function init() {
        // Categorías predefinidas
        $this->categories = [
            'hero' => __('Secciones Hero', FLAVOR_PLATFORM_TEXT_DOMAIN),
            'content' => __('Contenido', FLAVOR_PLATFORM_TEXT_DOMAIN),
            'forms' => __('Formularios', FLAVOR_PLATFORM_TEXT_DOMAIN),
            'listings' => __('Listados', FLAVOR_PLATFORM_TEXT_DOMAIN),
            'cards' => __('Tarjetas', FLAVOR_PLATFORM_TEXT_DOMAIN),
            'navigation' => __('Navegación', FLAVOR_PLATFORM_TEXT_DOMAIN),
            'features' => __('Características', FLAVOR_PLATFORM_TEXT_DOMAIN),
            'testimonials' => __('Testimonios', FLAVOR_PLATFORM_TEXT_DOMAIN),
            'cta' => __('Llamadas a la acción', FLAVOR_PLATFORM_TEXT_DOMAIN),
            'footer' => __('Footer', FLAVOR_PLATFORM_TEXT_DOMAIN),
        ];

        // Cargar componentes de módulos
        add_action('init', [$this, 'load_module_components'], 20);
    }

    /**
     * Cargar componentes de todos los módulos (activos Y registrados)
     *
     * IMPORTANTE: Los componentes web son solo templates visuales,
     * no requieren que el módulo esté completamente activado
     */
    public function load_module_components() {
        // Evitar cargar múltiples veces
        if ($this->modules_loaded) {
            return;
        }

        $module_loader = Flavor_Platform_Module_Loader::get_instance();

        // Primero intentar con módulos cargados (activos)
        $active_modules = $module_loader->get_loaded_modules();
        $total_components = 0;

        foreach ($active_modules as $module) {
            if (method_exists($module, 'get_web_components')) {
                $components = $module->get_web_components();
                if (is_array($components)) {
                    foreach ($components as $component_id => $component_data) {
                        $this->register_component(
                            $module->get_id() . '_' . $component_id,
                            $component_data,
                            $module->get_id()
                        );
                        $total_components++;
                    }
                }
            }
        }

        // Si no hay módulos activos, cargar componentes de TODOS los módulos registrados
        // (incluso los no activados, porque los componentes web son solo visuales)
        if ($total_components === 0) {
            $this->load_components_from_all_modules();
        }

        // Siempre cargar componentes de módulos frontend (landings)
        $this->load_frontend_landing_components();

        // Registrar componentes unificados y marcar los viejos como deprecated
        $this->registrar_componentes_unificados();
        $this->marcar_componentes_deprecated();

        $this->modules_loaded = true;
    }

    /**
     * Cargar componentes de landings frontend
     * No sobreescribe componentes ya registrados por módulos activos
     */
    private function load_frontend_landing_components() {
        $landing_components = $this->get_frontend_landing_definitions();

        foreach ($landing_components as $component_id => $component_data) {
            // No sobreescribir si ya existe un componente registrado por un módulo real
            if (isset($this->components[$component_id]) && !empty($this->components[$component_id]['module_id']) && $this->components[$component_id]['module_id'] !== 'frontend-landings') {
                continue;
            }
            $this->register_component($component_id, $component_data, 'frontend-landings');
        }
    }

    /**
     * Definiciones de componentes para landings frontend
     */
    private function get_frontend_landing_definitions() {
        return [
            // GRUPOS DE CONSUMO
            'grupos_consumo_hero' => [
                'label' => __('Hero Grupos de Consumo', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'description' => __('Sección hero con información sobre grupos de consumo', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'category' => 'hero',
                'icon' => 'dashicons-carrot',
                'fields' => [
                    'titulo' => [
                        'type' => 'text',
                        'label' => __('Título', FLAVOR_PLATFORM_TEXT_DOMAIN),
                        'default' => __('Grupos de Consumo', FLAVOR_PLATFORM_TEXT_DOMAIN),
                    ],
                    'subtitulo' => [
                        'type' => 'textarea',
                        'label' => __('Subtítulo', FLAVOR_PLATFORM_TEXT_DOMAIN),
                        'default' => __('Consume local, apoya a productores cercanos', FLAVOR_PLATFORM_TEXT_DOMAIN),
                    ],
                    'imagen_fondo' => [
                        'type' => 'image',
                        'label' => __('Imagen de fondo', FLAVOR_PLATFORM_TEXT_DOMAIN),
                        'default' => '',
                    ],
                    'mostrar_buscador' => [
                        'type' => 'toggle',
                        'label' => __('Mostrar buscador', FLAVOR_PLATFORM_TEXT_DOMAIN),
                        'default' => true,
                    ],
                    'color_primario' => [
                        'type' => 'color',
                        'label' => __('Color primario', FLAVOR_PLATFORM_TEXT_DOMAIN),
                        'default' => '#84cc16',
                    ],
                ],
                'template' => 'landings/grupos-consumo-hero',
            ],
            'grupos_consumo_listado' => [
                'label' => __('Listado Grupos de Consumo', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'description' => __('Grid de grupos de consumo disponibles', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'category' => 'listings',
                'icon' => 'dashicons-grid-view',
                'fields' => [
                    'titulo' => [
                        'type' => 'text',
                        'label' => __('Título', FLAVOR_PLATFORM_TEXT_DOMAIN),
                        'default' => __('Grupos Disponibles', FLAVOR_PLATFORM_TEXT_DOMAIN),
                    ],
                    'columnas' => [
                        'type' => 'select',
                        'label' => __('Columnas', FLAVOR_PLATFORM_TEXT_DOMAIN),
                        'options' => [2, 3, 4],
                        'default' => 3,
                    ],
                    'limite' => [
                        'type' => 'number',
                        'label' => __('Número máximo', FLAVOR_PLATFORM_TEXT_DOMAIN),
                        'default' => 6,
                    ],
                ],
                'template' => 'landings/grupos-consumo-listado',
            ],

            // BANCO DE TIEMPO
            'banco_tiempo_hero' => [
                'label' => __('Hero Banco de Tiempo', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'description' => __('Sección hero del banco de tiempo', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'category' => 'hero',
                'icon' => 'dashicons-clock',
                'fields' => [
                    'titulo' => [
                        'type' => 'text',
                        'label' => __('Título', FLAVOR_PLATFORM_TEXT_DOMAIN),
                        'default' => __('Banco de Tiempo', FLAVOR_PLATFORM_TEXT_DOMAIN),
                    ],
                    'subtitulo' => [
                        'type' => 'textarea',
                        'label' => __('Subtítulo', FLAVOR_PLATFORM_TEXT_DOMAIN),
                        'default' => __('Intercambia habilidades con tu comunidad', FLAVOR_PLATFORM_TEXT_DOMAIN),
                    ],
                    'imagen_fondo' => [
                        'type' => 'image',
                        'label' => __('Imagen de fondo', FLAVOR_PLATFORM_TEXT_DOMAIN),
                        'default' => '',
                    ],
                    'color_primario' => [
                        'type' => 'color',
                        'label' => __('Color primario', FLAVOR_PLATFORM_TEXT_DOMAIN),
                        'default' => '#8b5cf6',
                    ],
                ],
                'template' => 'landings/banco-tiempo-hero',
            ],
            'banco_tiempo_servicios' => [
                'label' => __('Servicios Banco de Tiempo', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'description' => __('Grid de servicios ofrecidos y demandados', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'category' => 'listings',
                'icon' => 'dashicons-screenoptions',
                'fields' => [
                    'titulo' => [
                        'type' => 'text',
                        'label' => __('Título', FLAVOR_PLATFORM_TEXT_DOMAIN),
                        'default' => __('Servicios Disponibles', FLAVOR_PLATFORM_TEXT_DOMAIN),
                    ],
                    'tipo' => [
                        'type' => 'select',
                        'label' => __('Tipo de servicios', FLAVOR_PLATFORM_TEXT_DOMAIN),
                        'options' => ['todos', 'ofertas', 'demandas'],
                        'default' => 'todos',
                    ],
                    'limite' => [
                        'type' => 'number',
                        'label' => __('Número máximo', FLAVOR_PLATFORM_TEXT_DOMAIN),
                        'default' => 8,
                    ],
                ],
                'template' => 'landings/banco-tiempo-servicios',
            ],

            // AYUNTAMIENTO
            'ayuntamiento_hero' => [
                'label' => __('Hero Ayuntamiento', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'description' => __('Sección hero del portal ciudadano', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'category' => 'hero',
                'icon' => 'dashicons-building',
                'fields' => [
                    'titulo' => [
                        'type' => 'text',
                        'label' => __('Título', FLAVOR_PLATFORM_TEXT_DOMAIN),
                        'default' => __('Portal Ciudadano', FLAVOR_PLATFORM_TEXT_DOMAIN),
                    ],
                    'subtitulo' => [
                        'type' => 'textarea',
                        'label' => __('Subtítulo', FLAVOR_PLATFORM_TEXT_DOMAIN),
                        'default' => __('Tu ayuntamiento a un clic', FLAVOR_PLATFORM_TEXT_DOMAIN),
                    ],
                    'mostrar_buscador' => [
                        'type' => 'toggle',
                        'label' => __('Mostrar buscador', FLAVOR_PLATFORM_TEXT_DOMAIN),
                        'default' => true,
                    ],
                    'color_primario' => [
                        'type' => 'color',
                        'label' => __('Color primario', FLAVOR_PLATFORM_TEXT_DOMAIN),
                        'default' => '#1d4ed8',
                    ],
                ],
                'template' => 'landings/ayuntamiento-hero',
            ],
            'ayuntamiento_tramites' => [
                'label' => __('Trámites Destacados', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'description' => __('Listado de trámites más solicitados', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'category' => 'listings',
                'icon' => 'dashicons-clipboard',
                'fields' => [
                    'titulo' => [
                        'type' => 'text',
                        'label' => __('Título', FLAVOR_PLATFORM_TEXT_DOMAIN),
                        'default' => __('Trámites más solicitados', FLAVOR_PLATFORM_TEXT_DOMAIN),
                    ],
                    'limite' => [
                        'type' => 'number',
                        'label' => __('Número máximo', FLAVOR_PLATFORM_TEXT_DOMAIN),
                        'default' => 6,
                    ],
                ],
                'template' => 'landings/ayuntamiento-tramites',
            ],
            'ayuntamiento_noticias' => [
                'label' => __('Noticias Ayuntamiento', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'description' => __('Últimas noticias municipales', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'category' => 'content',
                'icon' => 'dashicons-megaphone',
                'fields' => [
                    'titulo' => [
                        'type' => 'text',
                        'label' => __('Título', FLAVOR_PLATFORM_TEXT_DOMAIN),
                        'default' => __('Últimas Noticias', FLAVOR_PLATFORM_TEXT_DOMAIN),
                    ],
                    'limite' => [
                        'type' => 'number',
                        'label' => __('Número máximo', FLAVOR_PLATFORM_TEXT_DOMAIN),
                        'default' => 4,
                    ],
                ],
                'template' => 'landings/ayuntamiento-noticias',
            ],

            // COMUNIDADES
            'comunidades_hero' => [
                'label' => __('Hero Comunidades', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'description' => __('Sección hero de comunidades', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'category' => 'hero',
                'icon' => 'dashicons-groups',
                'fields' => [
                    'titulo' => [
                        'type' => 'text',
                        'label' => __('Título', FLAVOR_PLATFORM_TEXT_DOMAIN),
                        'default' => __('Comunidades', FLAVOR_PLATFORM_TEXT_DOMAIN),
                    ],
                    'subtitulo' => [
                        'type' => 'textarea',
                        'label' => __('Subtítulo', FLAVOR_PLATFORM_TEXT_DOMAIN),
                        'default' => __('Conecta con tu vecindario', FLAVOR_PLATFORM_TEXT_DOMAIN),
                    ],
                    'color_primario' => [
                        'type' => 'color',
                        'label' => __('Color primario', FLAVOR_PLATFORM_TEXT_DOMAIN),
                        'default' => '#f43f5e',
                    ],
                ],
                'template' => 'landings/comunidades-hero',
            ],
            'comunidades_listado' => [
                'label' => __('Listado Comunidades', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'description' => __('Grid de comunidades disponibles', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'category' => 'listings',
                'icon' => 'dashicons-networking',
                'fields' => [
                    'titulo' => [
                        'type' => 'text',
                        'label' => __('Título', FLAVOR_PLATFORM_TEXT_DOMAIN),
                        'default' => __('Comunidades Activas', FLAVOR_PLATFORM_TEXT_DOMAIN),
                    ],
                    'tipo' => [
                        'type' => 'select',
                        'label' => __('Tipo', FLAVOR_PLATFORM_TEXT_DOMAIN),
                        'options' => ['todas', 'vecinales', 'deportivas', 'culturales'],
                        'default' => 'todas',
                    ],
                    'limite' => [
                        'type' => 'number',
                        'label' => __('Número máximo', FLAVOR_PLATFORM_TEXT_DOMAIN),
                        'default' => 6,
                    ],
                ],
                'template' => 'landings/comunidades-listado',
            ],

            // ESPACIOS COMUNES
            'espacios_comunes_hero' => [
                'label' => __('Hero Espacios Comunes', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'description' => __('Sección hero de reserva de espacios', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'category' => 'hero',
                'icon' => 'dashicons-admin-multisite',
                'fields' => [
                    'titulo' => [
                        'type' => 'text',
                        'label' => __('Título', FLAVOR_PLATFORM_TEXT_DOMAIN),
                        'default' => __('Espacios Comunes', FLAVOR_PLATFORM_TEXT_DOMAIN),
                    ],
                    'subtitulo' => [
                        'type' => 'textarea',
                        'label' => __('Subtítulo', FLAVOR_PLATFORM_TEXT_DOMAIN),
                        'default' => __('Reserva salas y espacios para tus actividades', FLAVOR_PLATFORM_TEXT_DOMAIN),
                    ],
                    'color_primario' => [
                        'type' => 'color',
                        'label' => __('Color primario', FLAVOR_PLATFORM_TEXT_DOMAIN),
                        'default' => '#06b6d4',
                    ],
                ],
                'template' => 'landings/espacios-comunes-hero',
            ],

            // HUERTOS URBANOS
            'huertos_urbanos_hero' => [
                'label' => __('Hero Huertos Urbanos', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'description' => __('Sección hero de huertos urbanos', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'category' => 'hero',
                'icon' => 'dashicons-palmtree',
                'fields' => [
                    'titulo' => [
                        'type' => 'text',
                        'label' => __('Título', FLAVOR_PLATFORM_TEXT_DOMAIN),
                        'default' => __('Huertos Urbanos', FLAVOR_PLATFORM_TEXT_DOMAIN),
                    ],
                    'subtitulo' => [
                        'type' => 'textarea',
                        'label' => __('Subtítulo', FLAVOR_PLATFORM_TEXT_DOMAIN),
                        'default' => __('Cultiva tu propio huerto en la ciudad', FLAVOR_PLATFORM_TEXT_DOMAIN),
                    ],
                    'color_primario' => [
                        'type' => 'color',
                        'label' => __('Color primario', FLAVOR_PLATFORM_TEXT_DOMAIN),
                        'default' => '#22c55e',
                    ],
                ],
                'template' => 'landings/huertos-urbanos-hero',
            ],

            // BIBLIOTECA
            'biblioteca_hero' => [
                'label' => __('Hero Biblioteca', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'description' => __('Sección hero de biblioteca comunitaria', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'category' => 'hero',
                'icon' => 'dashicons-book',
                'fields' => [
                    'titulo' => [
                        'type' => 'text',
                        'label' => __('Título', FLAVOR_PLATFORM_TEXT_DOMAIN),
                        'default' => __('Biblioteca Comunitaria', FLAVOR_PLATFORM_TEXT_DOMAIN),
                    ],
                    'subtitulo' => [
                        'type' => 'textarea',
                        'label' => __('Subtítulo', FLAVOR_PLATFORM_TEXT_DOMAIN),
                        'default' => __('Comparte y descubre libros con tus vecinos', FLAVOR_PLATFORM_TEXT_DOMAIN),
                    ],
                    'color_primario' => [
                        'type' => 'color',
                        'label' => __('Color primario', FLAVOR_PLATFORM_TEXT_DOMAIN),
                        'default' => '#6366f1',
                    ],
                ],
                'template' => 'landings/biblioteca-hero',
            ],

            // CURSOS
            'cursos_hero' => [
                'label' => __('Hero Cursos', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'description' => __('Sección hero de cursos y talleres', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'category' => 'hero',
                'icon' => 'dashicons-welcome-learn-more',
                'fields' => [
                    'titulo' => [
                        'type' => 'text',
                        'label' => __('Título', FLAVOR_PLATFORM_TEXT_DOMAIN),
                        'default' => __('Cursos y Talleres', FLAVOR_PLATFORM_TEXT_DOMAIN),
                    ],
                    'subtitulo' => [
                        'type' => 'textarea',
                        'label' => __('Subtítulo', FLAVOR_PLATFORM_TEXT_DOMAIN),
                        'default' => __('Aprende nuevas habilidades con tu comunidad', FLAVOR_PLATFORM_TEXT_DOMAIN),
                    ],
                    'color_primario' => [
                        'type' => 'color',
                        'label' => __('Color primario', FLAVOR_PLATFORM_TEXT_DOMAIN),
                        'default' => '#a855f7',
                    ],
                ],
                'template' => 'landings/cursos-hero',
            ],

            // INCIDENCIAS
            'incidencias_hero' => [
                'label' => __('Hero Incidencias', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'description' => __('Sección hero para reportar incidencias', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'category' => 'hero',
                'icon' => 'dashicons-warning',
                'fields' => [
                    'titulo' => [
                        'type' => 'text',
                        'label' => __('Título', FLAVOR_PLATFORM_TEXT_DOMAIN),
                        'default' => __('Reportar Incidencias', FLAVOR_PLATFORM_TEXT_DOMAIN),
                    ],
                    'subtitulo' => [
                        'type' => 'textarea',
                        'label' => __('Subtítulo', FLAVOR_PLATFORM_TEXT_DOMAIN),
                        'default' => __('Ayúdanos a mejorar tu barrio reportando problemas', FLAVOR_PLATFORM_TEXT_DOMAIN),
                    ],
                    'color_primario' => [
                        'type' => 'color',
                        'label' => __('Color primario', FLAVOR_PLATFORM_TEXT_DOMAIN),
                        'default' => '#e11d48',
                    ],
                ],
                'template' => 'landings/incidencias-hero',
            ],

            // TIENDA LOCAL
            'tienda_local_hero' => [
                'label' => __('Hero Tienda Local', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'description' => __('Sección hero de comercios locales', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'category' => 'hero',
                'icon' => 'dashicons-store',
                'fields' => [
                    'titulo' => [
                        'type' => 'text',
                        'label' => __('Título', FLAVOR_PLATFORM_TEXT_DOMAIN),
                        'default' => __('Comercios Locales', FLAVOR_PLATFORM_TEXT_DOMAIN),
                    ],
                    'subtitulo' => [
                        'type' => 'textarea',
                        'label' => __('Subtítulo', FLAVOR_PLATFORM_TEXT_DOMAIN),
                        'default' => __('Apoya el comercio de tu barrio', FLAVOR_PLATFORM_TEXT_DOMAIN),
                    ],
                    'color_primario' => [
                        'type' => 'color',
                        'label' => __('Color primario', FLAVOR_PLATFORM_TEXT_DOMAIN),
                        'default' => '#f59e0b',
                    ],
                ],
                'template' => 'landings/tienda-local-hero',
            ],

            // RECICLAJE
            'reciclaje_hero' => [
                'label' => __('Hero Reciclaje', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'description' => __('Sección hero de puntos de reciclaje', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'category' => 'hero',
                'icon' => 'dashicons-update',
                'fields' => [
                    'titulo' => [
                        'type' => 'text',
                        'label' => __('Título', FLAVOR_PLATFORM_TEXT_DOMAIN),
                        'default' => __('Reciclaje', FLAVOR_PLATFORM_TEXT_DOMAIN),
                    ],
                    'subtitulo' => [
                        'type' => 'textarea',
                        'label' => __('Subtítulo', FLAVOR_PLATFORM_TEXT_DOMAIN),
                        'default' => __('Reduce, reutiliza, recicla', FLAVOR_PLATFORM_TEXT_DOMAIN),
                    ],
                    'color_primario' => [
                        'type' => 'color',
                        'label' => __('Color primario', FLAVOR_PLATFORM_TEXT_DOMAIN),
                        'default' => '#10b981',
                    ],
                ],
                'template' => 'landings/reciclaje-hero',
            ],

            // BICICLETAS
            'bicicletas_hero' => [
                'label' => __('Hero Bicicletas', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'description' => __('Sección hero de préstamo de bicicletas', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'category' => 'hero',
                'icon' => 'dashicons-location-alt',
                'fields' => [
                    'titulo' => [
                        'type' => 'text',
                        'label' => __('Título', FLAVOR_PLATFORM_TEXT_DOMAIN),
                        'default' => __('Bicicletas Compartidas', FLAVOR_PLATFORM_TEXT_DOMAIN),
                    ],
                    'subtitulo' => [
                        'type' => 'textarea',
                        'label' => __('Subtítulo', FLAVOR_PLATFORM_TEXT_DOMAIN),
                        'default' => __('Muévete de forma sostenible', FLAVOR_PLATFORM_TEXT_DOMAIN),
                    ],
                    'color_primario' => [
                        'type' => 'color',
                        'label' => __('Color primario', FLAVOR_PLATFORM_TEXT_DOMAIN),
                        'default' => '#a3e635',
                    ],
                ],
                'template' => 'landings/bicicletas-hero',
            ],

            // PODCAST
            'podcast_hero' => [
                'label' => __('Hero Podcast', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'description' => __('Sección hero de podcast comunitario', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'category' => 'hero',
                'icon' => 'dashicons-microphone',
                'fields' => [
                    'titulo' => [
                        'type' => 'text',
                        'label' => __('Título', FLAVOR_PLATFORM_TEXT_DOMAIN),
                        'default' => __('Nuestro Podcast', FLAVOR_PLATFORM_TEXT_DOMAIN),
                    ],
                    'subtitulo' => [
                        'type' => 'textarea',
                        'label' => __('Subtítulo', FLAVOR_PLATFORM_TEXT_DOMAIN),
                        'default' => __('Voces de la comunidad', FLAVOR_PLATFORM_TEXT_DOMAIN),
                    ],
                    'color_primario' => [
                        'type' => 'color',
                        'label' => __('Color primario', FLAVOR_PLATFORM_TEXT_DOMAIN),
                        'default' => '#14b8a6',
                    ],
                ],
                'template' => 'landings/podcast-hero',
            ],

            // RADIO
            'radio_hero' => [
                'label' => __('Hero Radio', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'description' => __('Sección hero de radio comunitaria', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'category' => 'hero',
                'icon' => 'dashicons-controls-volumeon',
                'fields' => [
                    'titulo' => [
                        'type' => 'text',
                        'label' => __('Título', FLAVOR_PLATFORM_TEXT_DOMAIN),
                        'default' => __('Radio Comunitaria', FLAVOR_PLATFORM_TEXT_DOMAIN),
                    ],
                    'subtitulo' => [
                        'type' => 'textarea',
                        'label' => __('Subtítulo', FLAVOR_PLATFORM_TEXT_DOMAIN),
                        'default' => __('La voz de tu barrio', FLAVOR_PLATFORM_TEXT_DOMAIN),
                    ],
                    'color_primario' => [
                        'type' => 'color',
                        'label' => __('Color primario', FLAVOR_PLATFORM_TEXT_DOMAIN),
                        'default' => '#ef4444',
                    ],
                ],
                'template' => 'landings/radio-hero',
            ],

            // AYUDA VECINAL
            'ayuda_vecinal_hero' => [
                'label' => __('Hero Ayuda Vecinal', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'description' => __('Sección hero de red de ayuda mutua', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'category' => 'hero',
                'icon' => 'dashicons-heart',
                'fields' => [
                    'titulo' => [
                        'type' => 'text',
                        'label' => __('Título', FLAVOR_PLATFORM_TEXT_DOMAIN),
                        'default' => __('Ayuda Vecinal', FLAVOR_PLATFORM_TEXT_DOMAIN),
                    ],
                    'subtitulo' => [
                        'type' => 'textarea',
                        'label' => __('Subtítulo', FLAVOR_PLATFORM_TEXT_DOMAIN),
                        'default' => __('Vecinos que ayudan a vecinos', FLAVOR_PLATFORM_TEXT_DOMAIN),
                    ],
                    'color_primario' => [
                        'type' => 'color',
                        'label' => __('Color primario', FLAVOR_PLATFORM_TEXT_DOMAIN),
                        'default' => '#f97316',
                    ],
                ],
                'template' => 'landings/ayuda-vecinal-hero',
            ],

            // EMPRESARIAL - HERO
            'empresarial_hero' => [
                'label' => __('Hero Empresarial', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'description' => __('Sección hero para páginas corporativas', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'category' => 'hero',
                'icon' => 'dashicons-building',
                'fields' => [
                    'titulo' => [
                        'type' => 'text',
                        'label' => __('Título', FLAVOR_PLATFORM_TEXT_DOMAIN),
                        'default' => __('Soluciones Empresariales', FLAVOR_PLATFORM_TEXT_DOMAIN),
                    ],
                    'subtitulo' => [
                        'type' => 'textarea',
                        'label' => __('Subtítulo', FLAVOR_PLATFORM_TEXT_DOMAIN),
                        'default' => __('Potencia tu negocio con tecnología de vanguardia', FLAVOR_PLATFORM_TEXT_DOMAIN),
                    ],
                    'texto_boton_principal' => [
                        'type' => 'text',
                        'label' => __('Texto botón principal', FLAVOR_PLATFORM_TEXT_DOMAIN),
                        'default' => __('Solicitar Demo', FLAVOR_PLATFORM_TEXT_DOMAIN),
                    ],
                    'url_boton_principal' => [
                        'type' => 'url',
                        'label' => __('URL botón principal', FLAVOR_PLATFORM_TEXT_DOMAIN),
                        'default' => '#contacto',
                    ],
                    'color_primario' => [
                        'type' => 'color',
                        'label' => __('Color primario', FLAVOR_PLATFORM_TEXT_DOMAIN),
                        'default' => '#1e40af',
                    ],
                ],
                'template' => 'landings/empresarial-hero',
            ],
            'empresarial_servicios' => [
                'label' => __('Servicios Empresariales', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'description' => __('Grid de servicios para empresas', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'category' => 'listings',
                'icon' => 'dashicons-grid-view',
                'fields' => [
                    'titulo' => [
                        'type' => 'text',
                        'label' => __('Título', FLAVOR_PLATFORM_TEXT_DOMAIN),
                        'default' => __('Nuestros Servicios', FLAVOR_PLATFORM_TEXT_DOMAIN),
                    ],
                    'columnas' => [
                        'type' => 'select',
                        'label' => __('Columnas', FLAVOR_PLATFORM_TEXT_DOMAIN),
                        'options' => [2, 3, 4],
                        'default' => 3,
                    ],
                ],
                'template' => 'landings/empresarial-servicios',
            ],
            'empresarial_stats' => [
                'label' => __('Estadísticas Empresariales', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'description' => __('Sección de métricas y logros', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'category' => 'content',
                'icon' => 'dashicons-chart-bar',
                'fields' => [
                    'titulo' => [
                        'type' => 'text',
                        'label' => __('Título', FLAVOR_PLATFORM_TEXT_DOMAIN),
                        'default' => __('Nuestros Resultados', FLAVOR_PLATFORM_TEXT_DOMAIN),
                    ],
                ],
                'template' => 'landings/empresarial-stats',
            ],
            'empresarial_testimonios' => [
                'label' => __('Testimonios Empresariales', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'description' => __('Testimonios de clientes', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'category' => 'testimonials',
                'icon' => 'dashicons-format-quote',
                'fields' => [
                    'titulo' => [
                        'type' => 'text',
                        'label' => __('Título', FLAVOR_PLATFORM_TEXT_DOMAIN),
                        'default' => __('Lo que dicen nuestros clientes', FLAVOR_PLATFORM_TEXT_DOMAIN),
                    ],
                ],
                'template' => 'landings/empresarial-testimonios',
            ],
            'empresarial_contacto' => [
                'label' => __('Contacto Empresarial', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'description' => __('Formulario de contacto empresarial', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'category' => 'forms',
                'icon' => 'dashicons-email',
                'fields' => [
                    'titulo' => [
                        'type' => 'text',
                        'label' => __('Título', FLAVOR_PLATFORM_TEXT_DOMAIN),
                        'default' => __('Contacta con nosotros', FLAVOR_PLATFORM_TEXT_DOMAIN),
                    ],
                ],
                'template' => 'landings/empresarial-contacto',
            ],
            'empresarial_pricing' => [
                'label' => __('Tabla de Precios', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'description' => __('Planes y precios para servicios', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'category' => 'content',
                'icon' => 'dashicons-money-alt',
                'fields' => [
                    'titulo' => [
                        'type' => 'text',
                        'label' => __('Título', FLAVOR_PLATFORM_TEXT_DOMAIN),
                        'default' => __('Planes y Precios', FLAVOR_PLATFORM_TEXT_DOMAIN),
                    ],
                ],
                'template' => 'landings/empresarial-pricing',
            ],
            'empresarial_equipo' => [
                'label' => __('Equipo', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'description' => __('Presentación del equipo', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'category' => 'content',
                'icon' => 'dashicons-groups',
                'fields' => [
                    'titulo' => [
                        'type' => 'text',
                        'label' => __('Título', FLAVOR_PLATFORM_TEXT_DOMAIN),
                        'default' => __('Nuestro Equipo', FLAVOR_PLATFORM_TEXT_DOMAIN),
                    ],
                ],
                'template' => 'landings/empresarial-equipo',
            ],

            // OFIMÁTICA
            'ofimatica_hero' => [
                'label' => __('Hero Ofimática', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'description' => __('Hero para suite de ofimática', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'category' => 'hero',
                'icon' => 'dashicons-media-document',
                'fields' => [
                    'titulo' => [
                        'type' => 'text',
                        'label' => __('Título', FLAVOR_PLATFORM_TEXT_DOMAIN),
                        'default' => __('Suite de Productividad', FLAVOR_PLATFORM_TEXT_DOMAIN),
                    ],
                    'subtitulo' => [
                        'type' => 'textarea',
                        'label' => __('Subtítulo', FLAVOR_PLATFORM_TEXT_DOMAIN),
                        'default' => __('Documentos, hojas de cálculo y presentaciones en la nube', FLAVOR_PLATFORM_TEXT_DOMAIN),
                    ],
                    'color_primario' => [
                        'type' => 'color',
                        'label' => __('Color primario', FLAVOR_PLATFORM_TEXT_DOMAIN),
                        'default' => '#0284c7',
                    ],
                ],
                'template' => 'landings/ofimatica-hero',
            ],
            'ofimatica_apps' => [
                'label' => __('Apps Ofimática', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'description' => __('Grid de aplicaciones de productividad', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'category' => 'listings',
                'icon' => 'dashicons-screenoptions',
                'fields' => [
                    'titulo' => [
                        'type' => 'text',
                        'label' => __('Título', FLAVOR_PLATFORM_TEXT_DOMAIN),
                        'default' => __('Nuestras Aplicaciones', FLAVOR_PLATFORM_TEXT_DOMAIN),
                    ],
                ],
                'template' => 'landings/ofimatica-apps',
            ],
            'ofimatica_features' => [
                'label' => __('Características Ofimática', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'description' => __('Características de la suite', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'category' => 'features',
                'icon' => 'dashicons-yes-alt',
                'fields' => [
                    'titulo' => [
                        'type' => 'text',
                        'label' => __('Título', FLAVOR_PLATFORM_TEXT_DOMAIN),
                        'default' => __('Características', FLAVOR_PLATFORM_TEXT_DOMAIN),
                    ],
                ],
                'template' => 'landings/ofimatica-features',
            ],

            // SAAS
            'saas_hero' => [
                'label' => __('Hero SaaS', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'description' => __('Hero para productos SaaS', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'category' => 'hero',
                'icon' => 'dashicons-cloud',
                'fields' => [
                    'titulo' => [
                        'type' => 'text',
                        'label' => __('Título', FLAVOR_PLATFORM_TEXT_DOMAIN),
                        'default' => __('Software en la Nube', FLAVOR_PLATFORM_TEXT_DOMAIN),
                    ],
                    'subtitulo' => [
                        'type' => 'textarea',
                        'label' => __('Subtítulo', FLAVOR_PLATFORM_TEXT_DOMAIN),
                        'default' => __('Accede desde cualquier lugar, en cualquier momento', FLAVOR_PLATFORM_TEXT_DOMAIN),
                    ],
                    'color_primario' => [
                        'type' => 'color',
                        'label' => __('Color primario', FLAVOR_PLATFORM_TEXT_DOMAIN),
                        'default' => '#7c3aed',
                    ],
                ],
                'template' => 'landings/saas-hero',
            ],
            'saas_features' => [
                'label' => __('Features SaaS', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'description' => __('Características del producto SaaS', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'category' => 'features',
                'icon' => 'dashicons-admin-plugins',
                'fields' => [
                    'titulo' => [
                        'type' => 'text',
                        'label' => __('Título', FLAVOR_PLATFORM_TEXT_DOMAIN),
                        'default' => __('Funcionalidades', FLAVOR_PLATFORM_TEXT_DOMAIN),
                    ],
                ],
                'template' => 'landings/saas-features',
            ],

            // AGENCIA
            'agencia_hero' => [
                'label' => __('Hero Agencia', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'description' => __('Hero para agencias y consultoras', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'category' => 'hero',
                'icon' => 'dashicons-portfolio',
                'fields' => [
                    'titulo' => [
                        'type' => 'text',
                        'label' => __('Título', FLAVOR_PLATFORM_TEXT_DOMAIN),
                        'default' => __('Agencia Creativa', FLAVOR_PLATFORM_TEXT_DOMAIN),
                    ],
                    'subtitulo' => [
                        'type' => 'textarea',
                        'label' => __('Subtítulo', FLAVOR_PLATFORM_TEXT_DOMAIN),
                        'default' => __('Diseño, desarrollo y estrategia digital', FLAVOR_PLATFORM_TEXT_DOMAIN),
                    ],
                    'color_primario' => [
                        'type' => 'color',
                        'label' => __('Color primario', FLAVOR_PLATFORM_TEXT_DOMAIN),
                        'default' => '#ec4899',
                    ],
                ],
                'template' => 'landings/agencia-hero',
            ],
            'agencia_portfolio' => [
                'label' => __('Portfolio Agencia', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'description' => __('Galería de trabajos realizados', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'category' => 'listings',
                'icon' => 'dashicons-images-alt2',
                'fields' => [
                    'titulo' => [
                        'type' => 'text',
                        'label' => __('Título', FLAVOR_PLATFORM_TEXT_DOMAIN),
                        'default' => __('Nuestros Proyectos', FLAVOR_PLATFORM_TEXT_DOMAIN),
                    ],
                ],
                'template' => 'landings/agencia-portfolio',
            ],

            // =====================================================
            // COMPONENTES ADICIONALES PARA TEMPLATES
            // =====================================================

            // CARPOOLING
            'carpooling_hero' => [
                'label' => __('Hero Carpooling', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'description' => __('Sección hero para compartir viajes', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'category' => 'hero',
                'icon' => 'dashicons-car',
                'fields' => [
                    'titulo' => [
                        'type' => 'text',
                        'label' => __('Título', FLAVOR_PLATFORM_TEXT_DOMAIN),
                        'default' => __('Comparte tu Viaje', FLAVOR_PLATFORM_TEXT_DOMAIN),
                    ],
                    'subtitulo' => [
                        'type' => 'textarea',
                        'label' => __('Subtítulo', FLAVOR_PLATFORM_TEXT_DOMAIN),
                        'default' => __('Ahorra dinero y reduce tu huella de carbono', FLAVOR_PLATFORM_TEXT_DOMAIN),
                    ],
                    'imagen_fondo' => [
                        'type' => 'image',
                        'label' => __('Imagen de fondo', FLAVOR_PLATFORM_TEXT_DOMAIN),
                        'default' => '',
                    ],
                    'mostrar_buscador' => [
                        'type' => 'toggle',
                        'label' => __('Mostrar buscador', FLAVOR_PLATFORM_TEXT_DOMAIN),
                        'default' => true,
                    ],
                    'color_primario' => [
                        'type' => 'color',
                        'label' => __('Color primario', FLAVOR_PLATFORM_TEXT_DOMAIN),
                        'default' => '#3b82f6',
                    ],
                ],
                'template' => 'landings/carpooling-hero',
            ],
            'carpooling_viajes_grid' => [
                'label' => __('Grid Viajes Carpooling', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'description' => __('Listado de viajes disponibles', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'category' => 'listings',
                'icon' => 'dashicons-grid-view',
                'fields' => [
                    'titulo' => [
                        'type' => 'text',
                        'label' => __('Título', FLAVOR_PLATFORM_TEXT_DOMAIN),
                        'default' => __('Viajes Disponibles', FLAVOR_PLATFORM_TEXT_DOMAIN),
                    ],
                    'columnas' => [
                        'type' => 'select',
                        'label' => __('Columnas', FLAVOR_PLATFORM_TEXT_DOMAIN),
                        'options' => [2, 3, 4],
                        'default' => 3,
                    ],
                    'limite' => [
                        'type' => 'number',
                        'label' => __('Número máximo', FLAVOR_PLATFORM_TEXT_DOMAIN),
                        'default' => 9,
                    ],
                ],
                'template' => 'landings/carpooling-viajes-grid',
            ],
            'carpooling_como_funciona' => [
                'label' => __('Cómo Funciona Carpooling', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'description' => __('Pasos para usar el carpooling', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'category' => 'content',
                'icon' => 'dashicons-info',
                'fields' => [
                    'titulo' => [
                        'type' => 'text',
                        'label' => __('Título', FLAVOR_PLATFORM_TEXT_DOMAIN),
                        'default' => __('Cómo Funciona', FLAVOR_PLATFORM_TEXT_DOMAIN),
                    ],
                ],
                'template' => 'landings/carpooling-como-funciona',
            ],
            'carpooling_cta_conductor' => [
                'label' => __('CTA Conductor Carpooling', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'description' => __('Llamada a la acción para conductores', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'category' => 'cta',
                'icon' => 'dashicons-megaphone',
                'fields' => [
                    'titulo' => [
                        'type' => 'text',
                        'label' => __('Título', FLAVOR_PLATFORM_TEXT_DOMAIN),
                        'default' => __('¿Tienes un viaje programado?', FLAVOR_PLATFORM_TEXT_DOMAIN),
                    ],
                    'descripcion' => [
                        'type' => 'textarea',
                        'label' => __('Descripción', FLAVOR_PLATFORM_TEXT_DOMAIN),
                        'default' => __('Publica tu ruta y comparte gastos', FLAVOR_PLATFORM_TEXT_DOMAIN),
                    ],
                    'boton_texto' => [
                        'type' => 'text',
                        'label' => __('Texto del botón', FLAVOR_PLATFORM_TEXT_DOMAIN),
                        'default' => __('Publicar Viaje', FLAVOR_PLATFORM_TEXT_DOMAIN),
                    ],
                    'boton_url' => [
                        'type' => 'url',
                        'label' => __('URL del botón', FLAVOR_PLATFORM_TEXT_DOMAIN),
                        'default' => '#',
                    ],
                    'color_fondo' => [
                        'type' => 'color',
                        'label' => __('Color de fondo', FLAVOR_PLATFORM_TEXT_DOMAIN),
                        'default' => '#3b82f6',
                    ],
                ],
                'template' => 'landings/carpooling-cta-conductor',
            ],

            // BICICLETAS COMPARTIDAS
            'bicicletas_compartidas_hero' => [
                'label' => __('Hero Bicicletas Compartidas', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'description' => __('Sección hero de bicicletas compartidas', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'category' => 'hero',
                'icon' => 'dashicons-location-alt',
                'fields' => [
                    'titulo' => [
                        'type' => 'text',
                        'label' => __('Título', FLAVOR_PLATFORM_TEXT_DOMAIN),
                        'default' => __('Bicicletas Compartidas', FLAVOR_PLATFORM_TEXT_DOMAIN),
                    ],
                    'subtitulo' => [
                        'type' => 'textarea',
                        'label' => __('Subtítulo', FLAVOR_PLATFORM_TEXT_DOMAIN),
                        'default' => __('Movilidad sostenible y saludable', FLAVOR_PLATFORM_TEXT_DOMAIN),
                    ],
                    'color_primario' => [
                        'type' => 'color',
                        'label' => __('Color primario', FLAVOR_PLATFORM_TEXT_DOMAIN),
                        'default' => '#a3e635',
                    ],
                ],
                'template' => 'landings/bicicletas-compartidas-hero',
            ],
            'bicicletas_compartidas_mapa' => [
                'label' => __('Mapa Bicicletas', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'description' => __('Mapa de estaciones de bicicletas', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'category' => 'content',
                'icon' => 'dashicons-location',
                'fields' => [
                    'titulo' => [
                        'type' => 'text',
                        'label' => __('Título', FLAVOR_PLATFORM_TEXT_DOMAIN),
                        'default' => __('Encuentra tu Estación', FLAVOR_PLATFORM_TEXT_DOMAIN),
                    ],
                    'altura_mapa' => [
                        'type' => 'number',
                        'label' => __('Altura del mapa (px)', FLAVOR_PLATFORM_TEXT_DOMAIN),
                        'default' => 400,
                    ],
                ],
                'template' => 'landings/bicicletas-compartidas-mapa',
            ],
            'bicicletas_compartidas_como_funciona' => [
                'label' => __('Cómo Funciona Bicicletas', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'description' => __('Pasos para usar las bicicletas', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'category' => 'content',
                'icon' => 'dashicons-info',
                'fields' => [
                    'titulo' => [
                        'type' => 'text',
                        'label' => __('Título', FLAVOR_PLATFORM_TEXT_DOMAIN),
                        'default' => __('¿Cómo funciona?', FLAVOR_PLATFORM_TEXT_DOMAIN),
                    ],
                ],
                'template' => 'landings/bicicletas-compartidas-como-funciona',
            ],
            'bicicletas_compartidas_tarifas' => [
                'label' => __('Tarifas Bicicletas', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'description' => __('Tabla de tarifas del servicio', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'category' => 'content',
                'icon' => 'dashicons-money-alt',
                'fields' => [
                    'titulo' => [
                        'type' => 'text',
                        'label' => __('Título', FLAVOR_PLATFORM_TEXT_DOMAIN),
                        'default' => __('Tarifas', FLAVOR_PLATFORM_TEXT_DOMAIN),
                    ],
                ],
                'template' => 'landings/bicicletas-compartidas-tarifas',
            ],

            // PARKINGS
            'hero_parkings' => [
                'label' => __('Hero Parkings', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'description' => __('Sección hero de parkings compartidos', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'category' => 'hero',
                'icon' => 'dashicons-admin-multisite',
                'fields' => [
                    'titulo' => [
                        'type' => 'text',
                        'label' => __('Título', FLAVOR_PLATFORM_TEXT_DOMAIN),
                        'default' => __('Parkings Compartidos', FLAVOR_PLATFORM_TEXT_DOMAIN),
                    ],
                    'subtitulo' => [
                        'type' => 'textarea',
                        'label' => __('Subtítulo', FLAVOR_PLATFORM_TEXT_DOMAIN),
                        'default' => __('Alquila o comparte tu plaza de parking', FLAVOR_PLATFORM_TEXT_DOMAIN),
                    ],
                    'imagen_fondo' => [
                        'type' => 'image',
                        'label' => __('Imagen de fondo', FLAVOR_PLATFORM_TEXT_DOMAIN),
                        'default' => '',
                    ],
                    'mostrar_buscador' => [
                        'type' => 'toggle',
                        'label' => __('Mostrar buscador', FLAVOR_PLATFORM_TEXT_DOMAIN),
                        'default' => true,
                    ],
                ],
                'template' => 'landings/parkings-hero',
            ],
            'parkings_grid' => [
                'label' => __('Grid Parkings', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'description' => __('Listado de plazas disponibles', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'category' => 'listings',
                'icon' => 'dashicons-grid-view',
                'fields' => [
                    'titulo' => [
                        'type' => 'text',
                        'label' => __('Título', FLAVOR_PLATFORM_TEXT_DOMAIN),
                        'default' => __('Plazas Disponibles', FLAVOR_PLATFORM_TEXT_DOMAIN),
                    ],
                    'columnas' => [
                        'type' => 'select',
                        'label' => __('Columnas', FLAVOR_PLATFORM_TEXT_DOMAIN),
                        'options' => [2, 3, 4],
                        'default' => 3,
                    ],
                    'limite' => [
                        'type' => 'number',
                        'label' => __('Número máximo', FLAVOR_PLATFORM_TEXT_DOMAIN),
                        'default' => 9,
                    ],
                ],
                'template' => 'landings/parkings-grid',
            ],
            'cta_propietario' => [
                'label' => __('CTA Propietario Parking', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'description' => __('Llamada a la acción para propietarios', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'category' => 'cta',
                'icon' => 'dashicons-megaphone',
                'fields' => [
                    'titulo' => [
                        'type' => 'text',
                        'label' => __('Título', FLAVOR_PLATFORM_TEXT_DOMAIN),
                        'default' => __('¿Tienes una Plaza Libre?', FLAVOR_PLATFORM_TEXT_DOMAIN),
                    ],
                    'descripcion' => [
                        'type' => 'textarea',
                        'label' => __('Descripción', FLAVOR_PLATFORM_TEXT_DOMAIN),
                        'default' => __('Genera ingresos extras compartiendo tu parking', FLAVOR_PLATFORM_TEXT_DOMAIN),
                    ],
                    'boton_texto' => [
                        'type' => 'text',
                        'label' => __('Texto del botón', FLAVOR_PLATFORM_TEXT_DOMAIN),
                        'default' => __('Publicar mi Plaza', FLAVOR_PLATFORM_TEXT_DOMAIN),
                    ],
                    'boton_url' => [
                        'type' => 'url',
                        'label' => __('URL del botón', FLAVOR_PLATFORM_TEXT_DOMAIN),
                        'default' => '#',
                    ],
                    'color_fondo' => [
                        'type' => 'color',
                        'label' => __('Color de fondo', FLAVOR_PLATFORM_TEXT_DOMAIN),
                        'default' => '#10b981',
                    ],
                ],
                'template' => 'landings/cta-propietario',
            ],

            // CURSOS ADICIONALES
            'cursos_categorias' => [
                'label' => __('Categorías Cursos', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'description' => __('Grid de categorías de cursos', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'category' => 'listings',
                'icon' => 'dashicons-category',
                'fields' => [
                    'titulo' => [
                        'type' => 'text',
                        'label' => __('Título', FLAVOR_PLATFORM_TEXT_DOMAIN),
                        'default' => __('Explora por Categoría', FLAVOR_PLATFORM_TEXT_DOMAIN),
                    ],
                ],
                'template' => 'landings/cursos-categorias',
            ],
            'cursos_grid' => [
                'label' => __('Grid Cursos', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'description' => __('Listado de cursos disponibles', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'category' => 'listings',
                'icon' => 'dashicons-grid-view',
                'fields' => [
                    'titulo' => [
                        'type' => 'text',
                        'label' => __('Título', FLAVOR_PLATFORM_TEXT_DOMAIN),
                        'default' => __('Cursos Disponibles', FLAVOR_PLATFORM_TEXT_DOMAIN),
                    ],
                    'columnas' => [
                        'type' => 'select',
                        'label' => __('Columnas', FLAVOR_PLATFORM_TEXT_DOMAIN),
                        'options' => [2, 3, 4],
                        'default' => 3,
                    ],
                    'limite' => [
                        'type' => 'number',
                        'label' => __('Número máximo', FLAVOR_PLATFORM_TEXT_DOMAIN),
                        'default' => 6,
                    ],
                ],
                'template' => 'landings/cursos-grid',
            ],
            'cursos_cta_instructor' => [
                'label' => __('CTA Instructor Cursos', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'description' => __('Llamada a la acción para instructores', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'category' => 'cta',
                'icon' => 'dashicons-megaphone',
                'fields' => [
                    'titulo' => [
                        'type' => 'text',
                        'label' => __('Título', FLAVOR_PLATFORM_TEXT_DOMAIN),
                        'default' => __('¿Quieres impartir un curso?', FLAVOR_PLATFORM_TEXT_DOMAIN),
                    ],
                    'descripcion' => [
                        'type' => 'textarea',
                        'label' => __('Descripción', FLAVOR_PLATFORM_TEXT_DOMAIN),
                        'default' => __('Comparte tu conocimiento con la comunidad', FLAVOR_PLATFORM_TEXT_DOMAIN),
                    ],
                    'boton_texto' => [
                        'type' => 'text',
                        'label' => __('Texto del botón', FLAVOR_PLATFORM_TEXT_DOMAIN),
                        'default' => __('Proponer Curso', FLAVOR_PLATFORM_TEXT_DOMAIN),
                    ],
                ],
                'template' => 'landings/cursos-cta-instructor',
            ],

            // BIBLIOTECA ADICIONALES
            'biblioteca_buscador' => [
                'label' => __('Buscador Biblioteca', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'description' => __('Buscador de libros', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'category' => 'forms',
                'icon' => 'dashicons-search',
                'fields' => [
                    'titulo' => [
                        'type' => 'text',
                        'label' => __('Título', FLAVOR_PLATFORM_TEXT_DOMAIN),
                        'default' => __('Buscar Libros', FLAVOR_PLATFORM_TEXT_DOMAIN),
                    ],
                ],
                'template' => 'landings/biblioteca-buscador',
            ],
            'biblioteca_libros_grid' => [
                'label' => __('Grid Libros', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'description' => __('Listado de libros disponibles', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'category' => 'listings',
                'icon' => 'dashicons-book',
                'fields' => [
                    'titulo' => [
                        'type' => 'text',
                        'label' => __('Título', FLAVOR_PLATFORM_TEXT_DOMAIN),
                        'default' => __('Libros Disponibles', FLAVOR_PLATFORM_TEXT_DOMAIN),
                    ],
                    'columnas' => [
                        'type' => 'select',
                        'label' => __('Columnas', FLAVOR_PLATFORM_TEXT_DOMAIN),
                        'options' => [2, 3, 4],
                        'default' => 4,
                    ],
                    'limite' => [
                        'type' => 'number',
                        'label' => __('Número máximo', FLAVOR_PLATFORM_TEXT_DOMAIN),
                        'default' => 8,
                    ],
                ],
                'template' => 'landings/biblioteca-libros-grid',
            ],
            'biblioteca_como_funciona' => [
                'label' => __('Cómo Funciona Biblioteca', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'description' => __('Pasos para usar la biblioteca', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'category' => 'content',
                'icon' => 'dashicons-info',
                'fields' => [
                    'titulo' => [
                        'type' => 'text',
                        'label' => __('Título', FLAVOR_PLATFORM_TEXT_DOMAIN),
                        'default' => __('¿Cómo funciona?', FLAVOR_PLATFORM_TEXT_DOMAIN),
                    ],
                ],
                'template' => 'landings/biblioteca-como-funciona',
            ],
            'biblioteca_cta_donar' => [
                'label' => __('CTA Donar Libro', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'description' => __('Llamada a la acción para donar libros', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'category' => 'cta',
                'icon' => 'dashicons-heart',
                'fields' => [
                    'titulo' => [
                        'type' => 'text',
                        'label' => __('Título', FLAVOR_PLATFORM_TEXT_DOMAIN),
                        'default' => __('¿Tienes libros que ya no lees?', FLAVOR_PLATFORM_TEXT_DOMAIN),
                    ],
                    'descripcion' => [
                        'type' => 'textarea',
                        'label' => __('Descripción', FLAVOR_PLATFORM_TEXT_DOMAIN),
                        'default' => __('Dónalos a la biblioteca', FLAVOR_PLATFORM_TEXT_DOMAIN),
                    ],
                    'boton_texto' => [
                        'type' => 'text',
                        'label' => __('Texto del botón', FLAVOR_PLATFORM_TEXT_DOMAIN),
                        'default' => __('Donar Libro', FLAVOR_PLATFORM_TEXT_DOMAIN),
                    ],
                ],
                'template' => 'landings/biblioteca-cta-donar',
            ],

            // TALLERES
            'hero_talleres' => [
                'label' => __('Hero Talleres', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'description' => __('Sección hero de talleres prácticos', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'category' => 'hero',
                'icon' => 'dashicons-hammer',
                'fields' => [
                    'titulo' => [
                        'type' => 'text',
                        'label' => __('Título', FLAVOR_PLATFORM_TEXT_DOMAIN),
                        'default' => __('Talleres Prácticos', FLAVOR_PLATFORM_TEXT_DOMAIN),
                    ],
                    'subtitulo' => [
                        'type' => 'textarea',
                        'label' => __('Subtítulo', FLAVOR_PLATFORM_TEXT_DOMAIN),
                        'default' => __('Aprende nuevas habilidades', FLAVOR_PLATFORM_TEXT_DOMAIN),
                    ],
                    'imagen_fondo' => [
                        'type' => 'image',
                        'label' => __('Imagen de fondo', FLAVOR_PLATFORM_TEXT_DOMAIN),
                        'default' => '',
                    ],
                    'mostrar_buscador' => [
                        'type' => 'toggle',
                        'label' => __('Mostrar buscador', FLAVOR_PLATFORM_TEXT_DOMAIN),
                        'default' => true,
                    ],
                ],
                'template' => 'landings/talleres-hero',
            ],
            'talleres_grid' => [
                'label' => __('Grid Talleres', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'description' => __('Listado de talleres', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'category' => 'listings',
                'icon' => 'dashicons-grid-view',
                'fields' => [
                    'titulo' => [
                        'type' => 'text',
                        'label' => __('Título', FLAVOR_PLATFORM_TEXT_DOMAIN),
                        'default' => __('Próximos Talleres', FLAVOR_PLATFORM_TEXT_DOMAIN),
                    ],
                    'columnas' => [
                        'type' => 'select',
                        'label' => __('Columnas', FLAVOR_PLATFORM_TEXT_DOMAIN),
                        'options' => [2, 3, 4],
                        'default' => 3,
                    ],
                    'limite' => [
                        'type' => 'number',
                        'label' => __('Número máximo', FLAVOR_PLATFORM_TEXT_DOMAIN),
                        'default' => 9,
                    ],
                ],
                'template' => 'landings/talleres-grid',
            ],
            'categorias_talleres' => [
                'label' => __('Categorías Talleres', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'description' => __('Grid de categorías de talleres', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'category' => 'listings',
                'icon' => 'dashicons-category',
                'fields' => [
                    'titulo' => [
                        'type' => 'text',
                        'label' => __('Título', FLAVOR_PLATFORM_TEXT_DOMAIN),
                        'default' => __('Explora por Categoría', FLAVOR_PLATFORM_TEXT_DOMAIN),
                    ],
                    'estilo' => [
                        'type' => 'select',
                        'label' => __('Estilo', FLAVOR_PLATFORM_TEXT_DOMAIN),
                        'options' => ['grid', 'list', 'carousel'],
                        'default' => 'grid',
                    ],
                ],
                'template' => 'landings/talleres-categorias',
            ],

            // HUERTOS URBANOS ADICIONALES
            'huertos_urbanos_mapa' => [
                'label' => __('Mapa Huertos', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'description' => __('Mapa de ubicación de huertos', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'category' => 'content',
                'icon' => 'dashicons-location',
                'fields' => [
                    'titulo' => [
                        'type' => 'text',
                        'label' => __('Título', FLAVOR_PLATFORM_TEXT_DOMAIN),
                        'default' => __('Encuentra tu Huerto', FLAVOR_PLATFORM_TEXT_DOMAIN),
                    ],
                    'altura_mapa' => [
                        'type' => 'number',
                        'label' => __('Altura del mapa (px)', FLAVOR_PLATFORM_TEXT_DOMAIN),
                        'default' => 400,
                    ],
                ],
                'template' => 'landings/huertos-urbanos-mapa',
            ],
            'huertos_urbanos_parcelas' => [
                'label' => __('Parcelas Huertos', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'description' => __('Listado de parcelas disponibles', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'category' => 'listings',
                'icon' => 'dashicons-grid-view',
                'fields' => [
                    'titulo' => [
                        'type' => 'text',
                        'label' => __('Título', FLAVOR_PLATFORM_TEXT_DOMAIN),
                        'default' => __('Parcelas Disponibles', FLAVOR_PLATFORM_TEXT_DOMAIN),
                    ],
                    'columnas' => [
                        'type' => 'select',
                        'label' => __('Columnas', FLAVOR_PLATFORM_TEXT_DOMAIN),
                        'options' => [2, 3, 4],
                        'default' => 3,
                    ],
                    'limite' => [
                        'type' => 'number',
                        'label' => __('Número máximo', FLAVOR_PLATFORM_TEXT_DOMAIN),
                        'default' => 6,
                    ],
                ],
                'template' => 'landings/huertos-urbanos-parcelas',
            ],
            'huertos_urbanos_beneficios' => [
                'label' => __('Beneficios Huertos', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'description' => __('Lista de beneficios de tener un huerto', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'category' => 'content',
                'icon' => 'dashicons-heart',
                'fields' => [
                    'titulo' => [
                        'type' => 'text',
                        'label' => __('Título', FLAVOR_PLATFORM_TEXT_DOMAIN),
                        'default' => __('Beneficios de Tener un Huerto', FLAVOR_PLATFORM_TEXT_DOMAIN),
                    ],
                ],
                'template' => 'landings/huertos-urbanos-beneficios',
            ],
            'huertos_urbanos_cta' => [
                'label' => __('CTA Huertos', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'description' => __('Llamada a la acción para solicitar parcela', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'category' => 'cta',
                'icon' => 'dashicons-megaphone',
                'fields' => [
                    'titulo' => [
                        'type' => 'text',
                        'label' => __('Título', FLAVOR_PLATFORM_TEXT_DOMAIN),
                        'default' => __('¿Quieres tu propia parcela?', FLAVOR_PLATFORM_TEXT_DOMAIN),
                    ],
                    'descripcion' => [
                        'type' => 'textarea',
                        'label' => __('Descripción', FLAVOR_PLATFORM_TEXT_DOMAIN),
                        'default' => __('Solicita tu huerto y empieza a cultivar', FLAVOR_PLATFORM_TEXT_DOMAIN),
                    ],
                    'boton_texto' => [
                        'type' => 'text',
                        'label' => __('Texto del botón', FLAVOR_PLATFORM_TEXT_DOMAIN),
                        'default' => __('Solicitar Parcela', FLAVOR_PLATFORM_TEXT_DOMAIN),
                    ],
                ],
                'template' => 'landings/huertos-urbanos-cta',
            ],

            // RECICLAJE ADICIONALES
            'reciclaje_puntos_mapa' => [
                'label' => __('Mapa Puntos Reciclaje', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'description' => __('Mapa de puntos de reciclaje', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'category' => 'content',
                'icon' => 'dashicons-location',
                'fields' => [
                    'titulo' => [
                        'type' => 'text',
                        'label' => __('Título', FLAVOR_PLATFORM_TEXT_DOMAIN),
                        'default' => __('Puntos de Reciclaje', FLAVOR_PLATFORM_TEXT_DOMAIN),
                    ],
                    'altura_mapa' => [
                        'type' => 'number',
                        'label' => __('Altura del mapa (px)', FLAVOR_PLATFORM_TEXT_DOMAIN),
                        'default' => 400,
                    ],
                ],
                'template' => 'landings/reciclaje-puntos-mapa',
            ],
            'reciclaje_guia' => [
                'label' => __('Guía Reciclaje', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'description' => __('Guía de cómo reciclar', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'category' => 'content',
                'icon' => 'dashicons-book-alt',
                'fields' => [
                    'titulo' => [
                        'type' => 'text',
                        'label' => __('Título', FLAVOR_PLATFORM_TEXT_DOMAIN),
                        'default' => __('Guía de Reciclaje', FLAVOR_PLATFORM_TEXT_DOMAIN),
                    ],
                ],
                'template' => 'landings/reciclaje-guia',
            ],
            'reciclaje_estadisticas' => [
                'label' => __('Estadísticas Reciclaje', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'description' => __('Impacto del reciclaje', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'category' => 'content',
                'icon' => 'dashicons-chart-bar',
                'fields' => [
                    'titulo' => [
                        'type' => 'text',
                        'label' => __('Título', FLAVOR_PLATFORM_TEXT_DOMAIN),
                        'default' => __('Nuestro Impacto', FLAVOR_PLATFORM_TEXT_DOMAIN),
                    ],
                ],
                'template' => 'landings/reciclaje-estadisticas',
            ],
            'reciclaje_consejos' => [
                'label' => __('Consejos Reciclaje', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'description' => __('Consejos para reciclar mejor', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'category' => 'content',
                'icon' => 'dashicons-lightbulb',
                'fields' => [
                    'titulo' => [
                        'type' => 'text',
                        'label' => __('Título', FLAVOR_PLATFORM_TEXT_DOMAIN),
                        'default' => __('Consejos para Reciclar Mejor', FLAVOR_PLATFORM_TEXT_DOMAIN),
                    ],
                ],
                'template' => 'landings/reciclaje-consejos',
            ],

            // COMPOSTAJE
            'hero_compostaje' => [
                'label' => __('Hero Compostaje', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'description' => __('Sección hero de compostaje', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'category' => 'hero',
                'icon' => 'dashicons-carrot',
                'fields' => [
                    'titulo' => [
                        'type' => 'text',
                        'label' => __('Título', FLAVOR_PLATFORM_TEXT_DOMAIN),
                        'default' => __('Compostaje Comunitario', FLAVOR_PLATFORM_TEXT_DOMAIN),
                    ],
                    'subtitulo' => [
                        'type' => 'textarea',
                        'label' => __('Subtítulo', FLAVOR_PLATFORM_TEXT_DOMAIN),
                        'default' => __('Convierte residuos en abono natural', FLAVOR_PLATFORM_TEXT_DOMAIN),
                    ],
                    'imagen_fondo' => [
                        'type' => 'image',
                        'label' => __('Imagen de fondo', FLAVOR_PLATFORM_TEXT_DOMAIN),
                        'default' => '',
                    ],
                    'mostrar_impacto' => [
                        'type' => 'toggle',
                        'label' => __('Mostrar impacto', FLAVOR_PLATFORM_TEXT_DOMAIN),
                        'default' => true,
                    ],
                ],
                'template' => 'landings/compostaje-hero',
            ],
            'mapa_composteras' => [
                'label' => __('Mapa Composteras', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'description' => __('Mapa de ubicación de composteras', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'category' => 'content',
                'icon' => 'dashicons-location',
                'fields' => [
                    'titulo' => [
                        'type' => 'text',
                        'label' => __('Título', FLAVOR_PLATFORM_TEXT_DOMAIN),
                        'default' => __('Encuentra tu Compostera', FLAVOR_PLATFORM_TEXT_DOMAIN),
                    ],
                    'altura_mapa' => [
                        'type' => 'number',
                        'label' => __('Altura del mapa (px)', FLAVOR_PLATFORM_TEXT_DOMAIN),
                        'default' => 500,
                    ],
                    'mostrar_estado' => [
                        'type' => 'toggle',
                        'label' => __('Mostrar estado', FLAVOR_PLATFORM_TEXT_DOMAIN),
                        'default' => true,
                    ],
                ],
                'template' => 'landings/compostaje-mapa',
            ],
            'guia_compostaje' => [
                'label' => __('Guía Compostaje', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'description' => __('Qué compostar y qué no', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'category' => 'content',
                'icon' => 'dashicons-book-alt',
                'fields' => [
                    'titulo' => [
                        'type' => 'text',
                        'label' => __('Título', FLAVOR_PLATFORM_TEXT_DOMAIN),
                        'default' => __('Qué Compostar', FLAVOR_PLATFORM_TEXT_DOMAIN),
                    ],
                    'estilo' => [
                        'type' => 'select',
                        'label' => __('Estilo', FLAVOR_PLATFORM_TEXT_DOMAIN),
                        'options' => ['tarjetas', 'lista', 'iconos'],
                        'default' => 'tarjetas',
                    ],
                ],
                'template' => 'landings/compostaje-guia',
            ],
            'proceso_compostaje' => [
                'label' => __('Proceso Compostaje', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'description' => __('Fases del proceso de compostaje', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'category' => 'content',
                'icon' => 'dashicons-image-rotate',
                'fields' => [
                    'titulo' => [
                        'type' => 'text',
                        'label' => __('Título', FLAVOR_PLATFORM_TEXT_DOMAIN),
                        'default' => __('Cómo Funciona', FLAVOR_PLATFORM_TEXT_DOMAIN),
                    ],
                    'mostrar_fases' => [
                        'type' => 'toggle',
                        'label' => __('Mostrar fases', FLAVOR_PLATFORM_TEXT_DOMAIN),
                        'default' => true,
                    ],
                ],
                'template' => 'landings/compostaje-proceso',
            ],

            // ESPACIOS COMUNES ADICIONALES
            'espacios_comunes_listado' => [
                'label' => __('Listado Espacios', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'description' => __('Grid de espacios disponibles', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'category' => 'listings',
                'icon' => 'dashicons-grid-view',
                'fields' => [
                    'titulo' => [
                        'type' => 'text',
                        'label' => __('Título', FLAVOR_PLATFORM_TEXT_DOMAIN),
                        'default' => __('Espacios Disponibles', FLAVOR_PLATFORM_TEXT_DOMAIN),
                    ],
                    'columnas' => [
                        'type' => 'select',
                        'label' => __('Columnas', FLAVOR_PLATFORM_TEXT_DOMAIN),
                        'options' => [2, 3, 4],
                        'default' => 3,
                    ],
                    'limite' => [
                        'type' => 'number',
                        'label' => __('Número máximo', FLAVOR_PLATFORM_TEXT_DOMAIN),
                        'default' => 6,
                    ],
                ],
                'template' => 'landings/espacios-comunes-listado',
            ],
            'espacios_comunes_calendario' => [
                'label' => __('Calendario Espacios', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'description' => __('Calendario de disponibilidad', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'category' => 'content',
                'icon' => 'dashicons-calendar-alt',
                'fields' => [
                    'titulo' => [
                        'type' => 'text',
                        'label' => __('Título', FLAVOR_PLATFORM_TEXT_DOMAIN),
                        'default' => __('Disponibilidad', FLAVOR_PLATFORM_TEXT_DOMAIN),
                    ],
                ],
                'template' => 'landings/espacios-comunes-calendario',
            ],
            'espacios_comunes_como_reservar' => [
                'label' => __('Cómo Reservar Espacios', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'description' => __('Pasos para reservar un espacio', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'category' => 'content',
                'icon' => 'dashicons-info',
                'fields' => [
                    'titulo' => [
                        'type' => 'text',
                        'label' => __('Título', FLAVOR_PLATFORM_TEXT_DOMAIN),
                        'default' => __('¿Cómo reservar?', FLAVOR_PLATFORM_TEXT_DOMAIN),
                    ],
                ],
                'template' => 'landings/espacios-comunes-como-reservar',
            ],

            // AYUDA VECINAL ADICIONALES
            'ayuda_vecinal_solicitudes' => [
                'label' => __('Solicitudes Ayuda', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'description' => __('Listado de solicitudes de ayuda', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'category' => 'listings',
                'icon' => 'dashicons-sos',
                'fields' => [
                    'titulo' => [
                        'type' => 'text',
                        'label' => __('Título', FLAVOR_PLATFORM_TEXT_DOMAIN),
                        'default' => __('Solicitudes de Ayuda', FLAVOR_PLATFORM_TEXT_DOMAIN),
                    ],
                    'columnas' => [
                        'type' => 'select',
                        'label' => __('Columnas', FLAVOR_PLATFORM_TEXT_DOMAIN),
                        'options' => [2, 3],
                        'default' => 2,
                    ],
                    'limite' => [
                        'type' => 'number',
                        'label' => __('Número máximo', FLAVOR_PLATFORM_TEXT_DOMAIN),
                        'default' => 6,
                    ],
                ],
                'template' => 'landings/ayuda-vecinal-solicitudes',
            ],
            'ayuda_vecinal_ofertas' => [
                'label' => __('Ofertas Ayuda', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'description' => __('Vecinos que ofrecen ayuda', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'category' => 'listings',
                'icon' => 'dashicons-heart',
                'fields' => [
                    'titulo' => [
                        'type' => 'text',
                        'label' => __('Título', FLAVOR_PLATFORM_TEXT_DOMAIN),
                        'default' => __('Vecinos que Ofrecen Ayuda', FLAVOR_PLATFORM_TEXT_DOMAIN),
                    ],
                    'columnas' => [
                        'type' => 'select',
                        'label' => __('Columnas', FLAVOR_PLATFORM_TEXT_DOMAIN),
                        'options' => [2, 3, 4],
                        'default' => 3,
                    ],
                    'limite' => [
                        'type' => 'number',
                        'label' => __('Número máximo', FLAVOR_PLATFORM_TEXT_DOMAIN),
                        'default' => 6,
                    ],
                ],
                'template' => 'landings/ayuda-vecinal-ofertas',
            ],
            'ayuda_vecinal_categorias' => [
                'label' => __('Categorías Ayuda', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'description' => __('Tipos de ayuda disponibles', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'category' => 'listings',
                'icon' => 'dashicons-category',
                'fields' => [
                    'titulo' => [
                        'type' => 'text',
                        'label' => __('Título', FLAVOR_PLATFORM_TEXT_DOMAIN),
                        'default' => __('Tipos de Ayuda', FLAVOR_PLATFORM_TEXT_DOMAIN),
                    ],
                ],
                'template' => 'landings/ayuda-vecinal-categorias',
            ],
            'ayuda_vecinal_cta' => [
                'label' => __('CTA Ayuda Vecinal', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'description' => __('Llamada a la acción para ayuda vecinal', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'category' => 'cta',
                'icon' => 'dashicons-megaphone',
                'fields' => [
                    'titulo' => [
                        'type' => 'text',
                        'label' => __('Título', FLAVOR_PLATFORM_TEXT_DOMAIN),
                        'default' => __('¿Necesitas ayuda o quieres ayudar?', FLAVOR_PLATFORM_TEXT_DOMAIN),
                    ],
                    'boton_texto' => [
                        'type' => 'text',
                        'label' => __('Texto del botón', FLAVOR_PLATFORM_TEXT_DOMAIN),
                        'default' => __('Publicar Solicitud', FLAVOR_PLATFORM_TEXT_DOMAIN),
                    ],
                ],
                'template' => 'landings/ayuda-vecinal-cta',
            ],

            // GRUPOS CONSUMO ADICIONALES
            'grupos_consumo_productores' => [
                'label' => __('Productores', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'description' => __('Listado de productores locales', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'category' => 'listings',
                'icon' => 'dashicons-groups',
                'fields' => [
                    'titulo' => [
                        'type' => 'text',
                        'label' => __('Título', FLAVOR_PLATFORM_TEXT_DOMAIN),
                        'default' => __('Nuestros Productores', FLAVOR_PLATFORM_TEXT_DOMAIN),
                    ],
                    'limite' => [
                        'type' => 'number',
                        'label' => __('Número máximo', FLAVOR_PLATFORM_TEXT_DOMAIN),
                        'default' => 6,
                    ],
                    'mostrar_ubicacion' => [
                        'type' => 'toggle',
                        'label' => __('Mostrar ubicación', FLAVOR_PLATFORM_TEXT_DOMAIN),
                        'default' => true,
                    ],
                ],
                'template' => 'landings/grupos-consumo-productores',
            ],
            'grupos_consumo_como_funciona' => [
                'label' => __('Cómo Funciona Grupos', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'description' => __('Pasos para participar', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'category' => 'content',
                'icon' => 'dashicons-info',
                'fields' => [
                    'titulo' => [
                        'type' => 'text',
                        'label' => __('Título', FLAVOR_PLATFORM_TEXT_DOMAIN),
                        'default' => __('¿Cómo Funciona?', FLAVOR_PLATFORM_TEXT_DOMAIN),
                    ],
                ],
                'template' => 'landings/grupos-consumo-como-funciona',
            ],
            'grupos_consumo_proximo_pedido' => [
                'label' => __('Próximo Pedido', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'description' => __('Información del próximo pedido', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'category' => 'content',
                'icon' => 'dashicons-calendar',
                'fields' => [
                    'titulo' => [
                        'type' => 'text',
                        'label' => __('Título', FLAVOR_PLATFORM_TEXT_DOMAIN),
                        'default' => __('Próximo Pedido', FLAVOR_PLATFORM_TEXT_DOMAIN),
                    ],
                    'mostrar_cuenta_atras' => [
                        'type' => 'toggle',
                        'label' => __('Mostrar cuenta atrás', FLAVOR_PLATFORM_TEXT_DOMAIN),
                        'default' => true,
                    ],
                ],
                'template' => 'landings/grupos-consumo-proximo-pedido',
            ],
            'grupos_consumo_cta_unirse' => [
                'label' => __('CTA Unirse Grupo', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'description' => __('Llamada a la acción para unirse', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'category' => 'cta',
                'icon' => 'dashicons-megaphone',
                'fields' => [
                    'titulo' => [
                        'type' => 'text',
                        'label' => __('Título', FLAVOR_PLATFORM_TEXT_DOMAIN),
                        'default' => __('¿Quieres unirte?', FLAVOR_PLATFORM_TEXT_DOMAIN),
                    ],
                    'subtitulo' => [
                        'type' => 'textarea',
                        'label' => __('Subtítulo', FLAVOR_PLATFORM_TEXT_DOMAIN),
                        'default' => __('Forma parte de un grupo de consumo', FLAVOR_PLATFORM_TEXT_DOMAIN),
                    ],
                    'texto_boton' => [
                        'type' => 'text',
                        'label' => __('Texto del botón', FLAVOR_PLATFORM_TEXT_DOMAIN),
                        'default' => __('Unirse a un Grupo', FLAVOR_PLATFORM_TEXT_DOMAIN),
                    ],
                    'url_boton' => [
                        'type' => 'url',
                        'label' => __('URL del botón', FLAVOR_PLATFORM_TEXT_DOMAIN),
                        'default' => '#',
                    ],
                ],
                'template' => 'landings/grupos-consumo-cta-unirse',
            ],

            // BANCO TIEMPO ADICIONALES
            'banco_tiempo_categorias' => [
                'label' => __('Categorías Banco Tiempo', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'description' => __('Categorías de servicios', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'category' => 'listings',
                'icon' => 'dashicons-category',
                'fields' => [
                    'titulo' => [
                        'type' => 'text',
                        'label' => __('Título', FLAVOR_PLATFORM_TEXT_DOMAIN),
                        'default' => __('Categorías de Servicios', FLAVOR_PLATFORM_TEXT_DOMAIN),
                    ],
                ],
                'template' => 'landings/banco-tiempo-categorias',
            ],
            'banco_tiempo_como_funciona' => [
                'label' => __('Cómo Funciona Banco Tiempo', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'description' => __('Pasos para usar el banco de tiempo', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'category' => 'content',
                'icon' => 'dashicons-info',
                'fields' => [
                    'titulo' => [
                        'type' => 'text',
                        'label' => __('Título', FLAVOR_PLATFORM_TEXT_DOMAIN),
                        'default' => __('¿Cómo Funciona?', FLAVOR_PLATFORM_TEXT_DOMAIN),
                    ],
                ],
                'template' => 'landings/banco-tiempo-como-funciona',
            ],
            'banco_tiempo_estadisticas' => [
                'label' => __('Estadísticas Banco Tiempo', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'description' => __('Estadísticas de la comunidad', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'category' => 'content',
                'icon' => 'dashicons-chart-bar',
                'fields' => [
                    'titulo' => [
                        'type' => 'text',
                        'label' => __('Título', FLAVOR_PLATFORM_TEXT_DOMAIN),
                        'default' => __('Nuestra Comunidad', FLAVOR_PLATFORM_TEXT_DOMAIN),
                    ],
                    'mostrar_usuarios' => [
                        'type' => 'toggle',
                        'label' => __('Mostrar usuarios', FLAVOR_PLATFORM_TEXT_DOMAIN),
                        'default' => true,
                    ],
                    'mostrar_horas_intercambiadas' => [
                        'type' => 'toggle',
                        'label' => __('Mostrar horas', FLAVOR_PLATFORM_TEXT_DOMAIN),
                        'default' => true,
                    ],
                    'mostrar_servicios' => [
                        'type' => 'toggle',
                        'label' => __('Mostrar servicios', FLAVOR_PLATFORM_TEXT_DOMAIN),
                        'default' => true,
                    ],
                ],
                'template' => 'landings/banco-tiempo-estadisticas',
            ],
            'banco_tiempo_cta_unirse' => [
                'label' => __('CTA Unirse Banco Tiempo', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'description' => __('Llamada a la acción para unirse', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'category' => 'cta',
                'icon' => 'dashicons-megaphone',
                'fields' => [
                    'titulo' => [
                        'type' => 'text',
                        'label' => __('Título', FLAVOR_PLATFORM_TEXT_DOMAIN),
                        'default' => __('¿Tienes habilidades que compartir?', FLAVOR_PLATFORM_TEXT_DOMAIN),
                    ],
                    'subtitulo' => [
                        'type' => 'textarea',
                        'label' => __('Subtítulo', FLAVOR_PLATFORM_TEXT_DOMAIN),
                        'default' => __('Únete al banco de tiempo', FLAVOR_PLATFORM_TEXT_DOMAIN),
                    ],
                    'texto_boton' => [
                        'type' => 'text',
                        'label' => __('Texto del botón', FLAVOR_PLATFORM_TEXT_DOMAIN),
                        'default' => __('Registrarme', FLAVOR_PLATFORM_TEXT_DOMAIN),
                    ],
                    'url_boton' => [
                        'type' => 'url',
                        'label' => __('URL del botón', FLAVOR_PLATFORM_TEXT_DOMAIN),
                        'default' => '#',
                    ],
                ],
                'template' => 'landings/banco-tiempo-cta-unirse',
            ],

            // COMUNIDADES ADICIONALES
            'comunidades_mapa' => [
                'label' => __('Mapa Comunidades', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'description' => __('Mapa de comunidades', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'category' => 'content',
                'icon' => 'dashicons-location',
                'fields' => [
                    'titulo' => [
                        'type' => 'text',
                        'label' => __('Título', FLAVOR_PLATFORM_TEXT_DOMAIN),
                        'default' => __('Encuentra tu Comunidad', FLAVOR_PLATFORM_TEXT_DOMAIN),
                    ],
                    'altura_mapa' => [
                        'type' => 'number',
                        'label' => __('Altura del mapa (px)', FLAVOR_PLATFORM_TEXT_DOMAIN),
                        'default' => 400,
                    ],
                    'mostrar_mi_ubicacion' => [
                        'type' => 'toggle',
                        'label' => __('Mostrar mi ubicación', FLAVOR_PLATFORM_TEXT_DOMAIN),
                        'default' => true,
                    ],
                ],
                'template' => 'landings/comunidades-mapa',
            ],
            'comunidades_actividad_reciente' => [
                'label' => __('Actividad Reciente', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'description' => __('Actividad reciente de las comunidades', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'category' => 'content',
                'icon' => 'dashicons-clock',
                'fields' => [
                    'titulo' => [
                        'type' => 'text',
                        'label' => __('Título', FLAVOR_PLATFORM_TEXT_DOMAIN),
                        'default' => __('Actividad Reciente', FLAVOR_PLATFORM_TEXT_DOMAIN),
                    ],
                    'limite' => [
                        'type' => 'number',
                        'label' => __('Número máximo', FLAVOR_PLATFORM_TEXT_DOMAIN),
                        'default' => 10,
                    ],
                ],
                'template' => 'landings/comunidades-actividad-reciente',
            ],
            'comunidades_estadisticas' => [
                'label' => __('Estadísticas Comunidades', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'description' => __('Estadísticas de las comunidades', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'category' => 'content',
                'icon' => 'dashicons-chart-bar',
                'fields' => [
                    'titulo' => [
                        'type' => 'text',
                        'label' => __('Título', FLAVOR_PLATFORM_TEXT_DOMAIN),
                        'default' => __('En Números', FLAVOR_PLATFORM_TEXT_DOMAIN),
                    ],
                    'mostrar_comunidades' => [
                        'type' => 'toggle',
                        'label' => __('Mostrar comunidades', FLAVOR_PLATFORM_TEXT_DOMAIN),
                        'default' => true,
                    ],
                    'mostrar_vecinos' => [
                        'type' => 'toggle',
                        'label' => __('Mostrar vecinos', FLAVOR_PLATFORM_TEXT_DOMAIN),
                        'default' => true,
                    ],
                    'mostrar_eventos' => [
                        'type' => 'toggle',
                        'label' => __('Mostrar eventos', FLAVOR_PLATFORM_TEXT_DOMAIN),
                        'default' => true,
                    ],
                ],
                'template' => 'landings/comunidades-estadisticas',
            ],
            'comunidades_cta_crear' => [
                'label' => __('CTA Crear Comunidad', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'description' => __('Llamada a la acción para crear comunidad', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'category' => 'cta',
                'icon' => 'dashicons-megaphone',
                'fields' => [
                    'titulo' => [
                        'type' => 'text',
                        'label' => __('Título', FLAVOR_PLATFORM_TEXT_DOMAIN),
                        'default' => __('¿No encuentras tu comunidad?', FLAVOR_PLATFORM_TEXT_DOMAIN),
                    ],
                    'subtitulo' => [
                        'type' => 'textarea',
                        'label' => __('Subtítulo', FLAVOR_PLATFORM_TEXT_DOMAIN),
                        'default' => __('Crea una nueva comunidad', FLAVOR_PLATFORM_TEXT_DOMAIN),
                    ],
                    'texto_boton' => [
                        'type' => 'text',
                        'label' => __('Texto del botón', FLAVOR_PLATFORM_TEXT_DOMAIN),
                        'default' => __('Crear Comunidad', FLAVOR_PLATFORM_TEXT_DOMAIN),
                    ],
                    'url_boton' => [
                        'type' => 'url',
                        'label' => __('URL del botón', FLAVOR_PLATFORM_TEXT_DOMAIN),
                        'default' => '#',
                    ],
                ],
                'template' => 'landings/comunidades-cta-crear',
            ],

            // INCIDENCIAS ADICIONALES
            'incidencias_mapa' => [
                'label' => __('Mapa Incidencias', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'description' => __('Mapa de incidencias reportadas', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'category' => 'content',
                'icon' => 'dashicons-location',
                'fields' => [
                    'titulo' => [
                        'type' => 'text',
                        'label' => __('Título', FLAVOR_PLATFORM_TEXT_DOMAIN),
                        'default' => __('Mapa de Incidencias', FLAVOR_PLATFORM_TEXT_DOMAIN),
                    ],
                    'descripcion' => [
                        'type' => 'textarea',
                        'label' => __('Descripción', FLAVOR_PLATFORM_TEXT_DOMAIN),
                        'default' => __('Visualiza las incidencias reportadas', FLAVOR_PLATFORM_TEXT_DOMAIN),
                    ],
                    'zoom_inicial' => [
                        'type' => 'number',
                        'label' => __('Zoom inicial', FLAVOR_PLATFORM_TEXT_DOMAIN),
                        'default' => 14,
                    ],
                    'mostrar_filtros' => [
                        'type' => 'toggle',
                        'label' => __('Mostrar filtros', FLAVOR_PLATFORM_TEXT_DOMAIN),
                        'default' => true,
                    ],
                ],
                'template' => 'landings/incidencias-mapa',
            ],
            'incidencias_categorias' => [
                'label' => __('Categorías Incidencias', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'description' => __('Tipos de incidencias', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'category' => 'listings',
                'icon' => 'dashicons-category',
                'fields' => [
                    'titulo' => [
                        'type' => 'text',
                        'label' => __('Título', FLAVOR_PLATFORM_TEXT_DOMAIN),
                        'default' => __('Tipos de Incidencias', FLAVOR_PLATFORM_TEXT_DOMAIN),
                    ],
                ],
                'template' => 'landings/incidencias-categorias',
            ],
            'incidencias_grid' => [
                'label' => __('Grid Incidencias', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'description' => __('Listado de incidencias', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'category' => 'listings',
                'icon' => 'dashicons-grid-view',
                'fields' => [
                    'titulo' => [
                        'type' => 'text',
                        'label' => __('Título', FLAVOR_PLATFORM_TEXT_DOMAIN),
                        'default' => __('Últimas Incidencias', FLAVOR_PLATFORM_TEXT_DOMAIN),
                    ],
                    'limite' => [
                        'type' => 'number',
                        'label' => __('Número máximo', FLAVOR_PLATFORM_TEXT_DOMAIN),
                        'default' => 6,
                    ],
                    'mostrar_estado' => [
                        'type' => 'toggle',
                        'label' => __('Mostrar estado', FLAVOR_PLATFORM_TEXT_DOMAIN),
                        'default' => true,
                    ],
                ],
                'template' => 'landings/incidencias-grid',
            ],
            'incidencias_estadisticas' => [
                'label' => __('Estadísticas Incidencias', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'description' => __('Estadísticas de incidencias', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'category' => 'content',
                'icon' => 'dashicons-chart-bar',
                'fields' => [
                    'titulo' => [
                        'type' => 'text',
                        'label' => __('Título', FLAVOR_PLATFORM_TEXT_DOMAIN),
                        'default' => __('Estadísticas', FLAVOR_PLATFORM_TEXT_DOMAIN),
                    ],
                    'mostrar_resueltas' => [
                        'type' => 'toggle',
                        'label' => __('Mostrar resueltas', FLAVOR_PLATFORM_TEXT_DOMAIN),
                        'default' => true,
                    ],
                    'mostrar_pendientes' => [
                        'type' => 'toggle',
                        'label' => __('Mostrar pendientes', FLAVOR_PLATFORM_TEXT_DOMAIN),
                        'default' => true,
                    ],
                    'mostrar_tiempo_medio' => [
                        'type' => 'toggle',
                        'label' => __('Mostrar tiempo medio', FLAVOR_PLATFORM_TEXT_DOMAIN),
                        'default' => true,
                    ],
                ],
                'template' => 'landings/incidencias-estadisticas',
            ],
            'incidencias_cta_reportar' => [
                'label' => __('CTA Reportar Incidencia', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'description' => __('Llamada a la acción para reportar', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'category' => 'cta',
                'icon' => 'dashicons-megaphone',
                'fields' => [
                    'titulo' => [
                        'type' => 'text',
                        'label' => __('Título', FLAVOR_PLATFORM_TEXT_DOMAIN),
                        'default' => __('¿Has visto algún problema?', FLAVOR_PLATFORM_TEXT_DOMAIN),
                    ],
                    'subtitulo' => [
                        'type' => 'textarea',
                        'label' => __('Subtítulo', FLAVOR_PLATFORM_TEXT_DOMAIN),
                        'default' => __('Reporta incidencias en tu barrio', FLAVOR_PLATFORM_TEXT_DOMAIN),
                    ],
                    'texto_boton' => [
                        'type' => 'text',
                        'label' => __('Texto del botón', FLAVOR_PLATFORM_TEXT_DOMAIN),
                        'default' => __('Reportar Incidencia', FLAVOR_PLATFORM_TEXT_DOMAIN),
                    ],
                    'url_boton' => [
                        'type' => 'url',
                        'label' => __('URL del botón', FLAVOR_PLATFORM_TEXT_DOMAIN),
                        'default' => '#',
                    ],
                ],
                'template' => 'landings/incidencias-cta-reportar',
            ],

            // TIENDA LOCAL ADICIONALES
            'tienda_local_buscador' => [
                'label' => __('Buscador Tiendas', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'description' => __('Buscador de comercios', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'category' => 'forms',
                'icon' => 'dashicons-search',
                'fields' => [
                    'placeholder' => [
                        'type' => 'text',
                        'label' => __('Placeholder', FLAVOR_PLATFORM_TEXT_DOMAIN),
                        'default' => __('Busca comercios, productos...', FLAVOR_PLATFORM_TEXT_DOMAIN),
                    ],
                    'mostrar_filtros' => [
                        'type' => 'toggle',
                        'label' => __('Mostrar filtros', FLAVOR_PLATFORM_TEXT_DOMAIN),
                        'default' => true,
                    ],
                ],
                'template' => 'landings/tienda-local-buscador',
            ],
            'tienda_local_categorias' => [
                'label' => __('Categorías Tiendas', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'description' => __('Categorías de comercios', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'category' => 'listings',
                'icon' => 'dashicons-category',
                'fields' => [
                    'titulo' => [
                        'type' => 'text',
                        'label' => __('Título', FLAVOR_PLATFORM_TEXT_DOMAIN),
                        'default' => __('Explora por Categoría', FLAVOR_PLATFORM_TEXT_DOMAIN),
                    ],
                    'mostrar_iconos' => [
                        'type' => 'toggle',
                        'label' => __('Mostrar iconos', FLAVOR_PLATFORM_TEXT_DOMAIN),
                        'default' => true,
                    ],
                ],
                'template' => 'landings/tienda-local-categorias',
            ],
            'tienda_local_destacados' => [
                'label' => __('Comercios Destacados', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'description' => __('Listado de comercios destacados', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'category' => 'listings',
                'icon' => 'dashicons-star-filled',
                'fields' => [
                    'titulo' => [
                        'type' => 'text',
                        'label' => __('Título', FLAVOR_PLATFORM_TEXT_DOMAIN),
                        'default' => __('Comercios Destacados', FLAVOR_PLATFORM_TEXT_DOMAIN),
                    ],
                    'limite' => [
                        'type' => 'number',
                        'label' => __('Número máximo', FLAVOR_PLATFORM_TEXT_DOMAIN),
                        'default' => 6,
                    ],
                    'mostrar_valoraciones' => [
                        'type' => 'toggle',
                        'label' => __('Mostrar valoraciones', FLAVOR_PLATFORM_TEXT_DOMAIN),
                        'default' => true,
                    ],
                ],
                'template' => 'landings/tienda-local-destacados',
            ],
            'tienda_local_mapa' => [
                'label' => __('Mapa Tiendas', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'description' => __('Mapa de comercios', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'category' => 'content',
                'icon' => 'dashicons-location',
                'fields' => [
                    'titulo' => [
                        'type' => 'text',
                        'label' => __('Título', FLAVOR_PLATFORM_TEXT_DOMAIN),
                        'default' => __('Encuentra Comercios Cerca de Ti', FLAVOR_PLATFORM_TEXT_DOMAIN),
                    ],
                    'zoom_inicial' => [
                        'type' => 'number',
                        'label' => __('Zoom inicial', FLAVOR_PLATFORM_TEXT_DOMAIN),
                        'default' => 15,
                    ],
                    'mostrar_mi_ubicacion' => [
                        'type' => 'toggle',
                        'label' => __('Mostrar mi ubicación', FLAVOR_PLATFORM_TEXT_DOMAIN),
                        'default' => true,
                    ],
                ],
                'template' => 'landings/tienda-local-mapa',
            ],
            'tienda_local_ofertas' => [
                'label' => __('Ofertas Tiendas', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'description' => __('Ofertas y promociones', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'category' => 'listings',
                'icon' => 'dashicons-tag',
                'fields' => [
                    'titulo' => [
                        'type' => 'text',
                        'label' => __('Título', FLAVOR_PLATFORM_TEXT_DOMAIN),
                        'default' => __('Ofertas y Promociones', FLAVOR_PLATFORM_TEXT_DOMAIN),
                    ],
                    'limite' => [
                        'type' => 'number',
                        'label' => __('Número máximo', FLAVOR_PLATFORM_TEXT_DOMAIN),
                        'default' => 4,
                    ],
                ],
                'template' => 'landings/tienda-local-ofertas',
            ],
            'tienda_local_cta_registrar' => [
                'label' => __('CTA Registrar Comercio', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'description' => __('Llamada a la acción para registrar comercio', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'category' => 'cta',
                'icon' => 'dashicons-megaphone',
                'fields' => [
                    'titulo' => [
                        'type' => 'text',
                        'label' => __('Título', FLAVOR_PLATFORM_TEXT_DOMAIN),
                        'default' => __('¿Tienes un comercio local?', FLAVOR_PLATFORM_TEXT_DOMAIN),
                    ],
                    'subtitulo' => [
                        'type' => 'textarea',
                        'label' => __('Subtítulo', FLAVOR_PLATFORM_TEXT_DOMAIN),
                        'default' => __('Registra tu negocio', FLAVOR_PLATFORM_TEXT_DOMAIN),
                    ],
                    'texto_boton' => [
                        'type' => 'text',
                        'label' => __('Texto del botón', FLAVOR_PLATFORM_TEXT_DOMAIN),
                        'default' => __('Registrar mi Comercio', FLAVOR_PLATFORM_TEXT_DOMAIN),
                    ],
                    'url_boton' => [
                        'type' => 'url',
                        'label' => __('URL del botón', FLAVOR_PLATFORM_TEXT_DOMAIN),
                        'default' => '#',
                    ],
                ],
                'template' => 'landings/tienda-local-cta-registrar',
            ],

            // PODCAST ADICIONALES
            'podcast_ultimo_episodio' => [
                'label' => __('Último Episodio', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'description' => __('Destacado del último episodio', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'category' => 'content',
                'icon' => 'dashicons-microphone',
                'fields' => [
                    'titulo' => [
                        'type' => 'text',
                        'label' => __('Título', FLAVOR_PLATFORM_TEXT_DOMAIN),
                        'default' => __('Último Episodio', FLAVOR_PLATFORM_TEXT_DOMAIN),
                    ],
                    'mostrar_player' => [
                        'type' => 'toggle',
                        'label' => __('Mostrar reproductor', FLAVOR_PLATFORM_TEXT_DOMAIN),
                        'default' => true,
                    ],
                    'mostrar_descripcion' => [
                        'type' => 'toggle',
                        'label' => __('Mostrar descripción', FLAVOR_PLATFORM_TEXT_DOMAIN),
                        'default' => true,
                    ],
                ],
                'template' => 'landings/podcast-ultimo-episodio',
            ],
            'podcast_episodios_grid' => [
                'label' => __('Grid Episodios', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'description' => __('Listado de episodios', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'category' => 'listings',
                'icon' => 'dashicons-grid-view',
                'fields' => [
                    'titulo' => [
                        'type' => 'text',
                        'label' => __('Título', FLAVOR_PLATFORM_TEXT_DOMAIN),
                        'default' => __('Todos los Episodios', FLAVOR_PLATFORM_TEXT_DOMAIN),
                    ],
                    'limite' => [
                        'type' => 'number',
                        'label' => __('Número máximo', FLAVOR_PLATFORM_TEXT_DOMAIN),
                        'default' => 9,
                    ],
                    'columnas' => [
                        'type' => 'select',
                        'label' => __('Columnas', FLAVOR_PLATFORM_TEXT_DOMAIN),
                        'options' => [2, 3, 4],
                        'default' => 3,
                    ],
                    'mostrar_duracion' => [
                        'type' => 'toggle',
                        'label' => __('Mostrar duración', FLAVOR_PLATFORM_TEXT_DOMAIN),
                        'default' => true,
                    ],
                ],
                'template' => 'landings/podcast-episodios-grid',
            ],
            'podcast_categorias' => [
                'label' => __('Categorías Podcast', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'description' => __('Temáticas del podcast', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'category' => 'listings',
                'icon' => 'dashicons-category',
                'fields' => [
                    'titulo' => [
                        'type' => 'text',
                        'label' => __('Título', FLAVOR_PLATFORM_TEXT_DOMAIN),
                        'default' => __('Temáticas', FLAVOR_PLATFORM_TEXT_DOMAIN),
                    ],
                ],
                'template' => 'landings/podcast-categorias',
            ],
            'podcast_presentadores' => [
                'label' => __('Presentadores Podcast', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'description' => __('Equipo del podcast', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'category' => 'content',
                'icon' => 'dashicons-groups',
                'fields' => [
                    'titulo' => [
                        'type' => 'text',
                        'label' => __('Título', FLAVOR_PLATFORM_TEXT_DOMAIN),
                        'default' => __('Nuestros Presentadores', FLAVOR_PLATFORM_TEXT_DOMAIN),
                    ],
                    'limite' => [
                        'type' => 'number',
                        'label' => __('Número máximo', FLAVOR_PLATFORM_TEXT_DOMAIN),
                        'default' => 4,
                    ],
                    'mostrar_bio' => [
                        'type' => 'toggle',
                        'label' => __('Mostrar biografía', FLAVOR_PLATFORM_TEXT_DOMAIN),
                        'default' => true,
                    ],
                ],
                'template' => 'landings/podcast-presentadores',
            ],
            'podcast_suscribir' => [
                'label' => __('Suscribir Podcast', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'description' => __('Enlaces de suscripción', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'category' => 'cta',
                'icon' => 'dashicons-rss',
                'fields' => [
                    'titulo' => [
                        'type' => 'text',
                        'label' => __('Título', FLAVOR_PLATFORM_TEXT_DOMAIN),
                        'default' => __('Suscríbete', FLAVOR_PLATFORM_TEXT_DOMAIN),
                    ],
                    'subtitulo' => [
                        'type' => 'textarea',
                        'label' => __('Subtítulo', FLAVOR_PLATFORM_TEXT_DOMAIN),
                        'default' => __('Escúchanos en tu plataforma favorita', FLAVOR_PLATFORM_TEXT_DOMAIN),
                    ],
                ],
                'template' => 'landings/podcast-suscribir',
            ],
            'podcast_cta_participar' => [
                'label' => __('CTA Participar Podcast', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'description' => __('Llamada a la acción para participar', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'category' => 'cta',
                'icon' => 'dashicons-megaphone',
                'fields' => [
                    'titulo' => [
                        'type' => 'text',
                        'label' => __('Título', FLAVOR_PLATFORM_TEXT_DOMAIN),
                        'default' => __('¿Quieres participar?', FLAVOR_PLATFORM_TEXT_DOMAIN),
                    ],
                    'subtitulo' => [
                        'type' => 'textarea',
                        'label' => __('Subtítulo', FLAVOR_PLATFORM_TEXT_DOMAIN),
                        'default' => __('Envíanos tus sugerencias', FLAVOR_PLATFORM_TEXT_DOMAIN),
                    ],
                    'texto_boton' => [
                        'type' => 'text',
                        'label' => __('Texto del botón', FLAVOR_PLATFORM_TEXT_DOMAIN),
                        'default' => __('Contactar', FLAVOR_PLATFORM_TEXT_DOMAIN),
                    ],
                    'url_boton' => [
                        'type' => 'url',
                        'label' => __('URL del botón', FLAVOR_PLATFORM_TEXT_DOMAIN),
                        'default' => '#',
                    ],
                ],
                'template' => 'landings/podcast-cta-participar',
            ],

            // RADIO ADICIONALES
            'radio_player_en_vivo' => [
                'label' => __('Player Radio en Vivo', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'description' => __('Reproductor de radio en vivo', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'category' => 'content',
                'icon' => 'dashicons-controls-volumeon',
                'fields' => [
                    'titulo' => [
                        'type' => 'text',
                        'label' => __('Título', FLAVOR_PLATFORM_TEXT_DOMAIN),
                        'default' => __('Escucha en Vivo', FLAVOR_PLATFORM_TEXT_DOMAIN),
                    ],
                    'mostrar_programa_actual' => [
                        'type' => 'toggle',
                        'label' => __('Mostrar programa actual', FLAVOR_PLATFORM_TEXT_DOMAIN),
                        'default' => true,
                    ],
                    'mostrar_siguiente' => [
                        'type' => 'toggle',
                        'label' => __('Mostrar siguiente', FLAVOR_PLATFORM_TEXT_DOMAIN),
                        'default' => true,
                    ],
                    'url_stream' => [
                        'type' => 'url',
                        'label' => __('URL del stream', FLAVOR_PLATFORM_TEXT_DOMAIN),
                        'default' => '',
                    ],
                ],
                'template' => 'landings/radio-player-en-vivo',
            ],
            'radio_programacion' => [
                'label' => __('Programación Radio', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'description' => __('Parrilla de programación', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'category' => 'content',
                'icon' => 'dashicons-calendar-alt',
                'fields' => [
                    'titulo' => [
                        'type' => 'text',
                        'label' => __('Título', FLAVOR_PLATFORM_TEXT_DOMAIN),
                        'default' => __('Programación Semanal', FLAVOR_PLATFORM_TEXT_DOMAIN),
                    ],
                    'formato_hora' => [
                        'type' => 'select',
                        'label' => __('Formato hora', FLAVOR_PLATFORM_TEXT_DOMAIN),
                        'options' => ['12h', '24h'],
                        'default' => '24h',
                    ],
                ],
                'template' => 'landings/radio-programacion',
            ],
            'radio_programas' => [
                'label' => __('Programas Radio', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'description' => __('Listado de programas', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'category' => 'listings',
                'icon' => 'dashicons-playlist-audio',
                'fields' => [
                    'titulo' => [
                        'type' => 'text',
                        'label' => __('Título', FLAVOR_PLATFORM_TEXT_DOMAIN),
                        'default' => __('Nuestros Programas', FLAVOR_PLATFORM_TEXT_DOMAIN),
                    ],
                    'limite' => [
                        'type' => 'number',
                        'label' => __('Número máximo', FLAVOR_PLATFORM_TEXT_DOMAIN),
                        'default' => 6,
                    ],
                    'mostrar_horario' => [
                        'type' => 'toggle',
                        'label' => __('Mostrar horario', FLAVOR_PLATFORM_TEXT_DOMAIN),
                        'default' => true,
                    ],
                    'mostrar_descripcion' => [
                        'type' => 'toggle',
                        'label' => __('Mostrar descripción', FLAVOR_PLATFORM_TEXT_DOMAIN),
                        'default' => true,
                    ],
                ],
                'template' => 'landings/radio-programas',
            ],
            'radio_locutores' => [
                'label' => __('Locutores Radio', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'description' => __('Equipo de locutores', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'category' => 'content',
                'icon' => 'dashicons-groups',
                'fields' => [
                    'titulo' => [
                        'type' => 'text',
                        'label' => __('Título', FLAVOR_PLATFORM_TEXT_DOMAIN),
                        'default' => __('El Equipo', FLAVOR_PLATFORM_TEXT_DOMAIN),
                    ],
                    'limite' => [
                        'type' => 'number',
                        'label' => __('Número máximo', FLAVOR_PLATFORM_TEXT_DOMAIN),
                        'default' => 8,
                    ],
                    'mostrar_programa' => [
                        'type' => 'toggle',
                        'label' => __('Mostrar programa', FLAVOR_PLATFORM_TEXT_DOMAIN),
                        'default' => true,
                    ],
                ],
                'template' => 'landings/radio-locutores',
            ],
            'radio_archivo' => [
                'label' => __('Archivo Radio', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'description' => __('Programas anteriores', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'category' => 'listings',
                'icon' => 'dashicons-media-audio',
                'fields' => [
                    'titulo' => [
                        'type' => 'text',
                        'label' => __('Título', FLAVOR_PLATFORM_TEXT_DOMAIN),
                        'default' => __('Programas Anteriores', FLAVOR_PLATFORM_TEXT_DOMAIN),
                    ],
                    'limite' => [
                        'type' => 'number',
                        'label' => __('Número máximo', FLAVOR_PLATFORM_TEXT_DOMAIN),
                        'default' => 6,
                    ],
                    'mostrar_player' => [
                        'type' => 'toggle',
                        'label' => __('Mostrar reproductor', FLAVOR_PLATFORM_TEXT_DOMAIN),
                        'default' => true,
                    ],
                ],
                'template' => 'landings/radio-archivo',
            ],
            'radio_cta_colaborar' => [
                'label' => __('CTA Colaborar Radio', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'description' => __('Llamada a la acción para colaborar', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'category' => 'cta',
                'icon' => 'dashicons-megaphone',
                'fields' => [
                    'titulo' => [
                        'type' => 'text',
                        'label' => __('Título', FLAVOR_PLATFORM_TEXT_DOMAIN),
                        'default' => __('¿Quieres colaborar?', FLAVOR_PLATFORM_TEXT_DOMAIN),
                    ],
                    'subtitulo' => [
                        'type' => 'textarea',
                        'label' => __('Subtítulo', FLAVOR_PLATFORM_TEXT_DOMAIN),
                        'default' => __('Únete a la radio comunitaria', FLAVOR_PLATFORM_TEXT_DOMAIN),
                    ],
                    'texto_boton' => [
                        'type' => 'text',
                        'label' => __('Texto del botón', FLAVOR_PLATFORM_TEXT_DOMAIN),
                        'default' => __('Quiero Participar', FLAVOR_PLATFORM_TEXT_DOMAIN),
                    ],
                    'url_boton' => [
                        'type' => 'url',
                        'label' => __('URL del botón', FLAVOR_PLATFORM_TEXT_DOMAIN),
                        'default' => '#',
                    ],
                ],
                'template' => 'landings/radio-cta-colaborar',
            ],

            // MULTIMEDIA
            'hero_multimedia' => [
                'label' => __('Hero Multimedia', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'description' => __('Sección hero de galería multimedia', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'category' => 'hero',
                'icon' => 'dashicons-format-gallery',
                'fields' => [
                    'titulo' => [
                        'type' => 'text',
                        'label' => __('Título', FLAVOR_PLATFORM_TEXT_DOMAIN),
                        'default' => __('Galería Comunitaria', FLAVOR_PLATFORM_TEXT_DOMAIN),
                    ],
                    'subtitulo' => [
                        'type' => 'textarea',
                        'label' => __('Subtítulo', FLAVOR_PLATFORM_TEXT_DOMAIN),
                        'default' => __('Momentos y recuerdos de nuestra comunidad', FLAVOR_PLATFORM_TEXT_DOMAIN),
                    ],
                    'imagen_fondo' => [
                        'type' => 'image',
                        'label' => __('Imagen de fondo', FLAVOR_PLATFORM_TEXT_DOMAIN),
                        'default' => '',
                    ],
                    'mostrar_contador' => [
                        'type' => 'toggle',
                        'label' => __('Mostrar contador', FLAVOR_PLATFORM_TEXT_DOMAIN),
                        'default' => true,
                    ],
                ],
                'template' => 'landings/multimedia-hero',
            ],
            'carousel_destacado' => [
                'label' => __('Carousel Destacado', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'description' => __('Carousel de imágenes destacadas', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'category' => 'content',
                'icon' => 'dashicons-images-alt',
                'fields' => [
                    'titulo' => [
                        'type' => 'text',
                        'label' => __('Título', FLAVOR_PLATFORM_TEXT_DOMAIN),
                        'default' => __('Momentos Destacados', FLAVOR_PLATFORM_TEXT_DOMAIN),
                    ],
                    'autoplay' => [
                        'type' => 'toggle',
                        'label' => __('Autoplay', FLAVOR_PLATFORM_TEXT_DOMAIN),
                        'default' => true,
                    ],
                    'intervalo_segundos' => [
                        'type' => 'number',
                        'label' => __('Intervalo (segundos)', FLAVOR_PLATFORM_TEXT_DOMAIN),
                        'default' => 5,
                    ],
                ],
                'template' => 'landings/multimedia-carousel',
            ],
            'galeria_grid' => [
                'label' => __('Galería Grid', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'description' => __('Grid de fotos', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'category' => 'listings',
                'icon' => 'dashicons-format-gallery',
                'fields' => [
                    'titulo' => [
                        'type' => 'text',
                        'label' => __('Título', FLAVOR_PLATFORM_TEXT_DOMAIN),
                        'default' => __('Galería de Fotos', FLAVOR_PLATFORM_TEXT_DOMAIN),
                    ],
                    'columnas' => [
                        'type' => 'select',
                        'label' => __('Columnas', FLAVOR_PLATFORM_TEXT_DOMAIN),
                        'options' => [2, 3, 4, 5],
                        'default' => 4,
                    ],
                    'limite' => [
                        'type' => 'number',
                        'label' => __('Número máximo', FLAVOR_PLATFORM_TEXT_DOMAIN),
                        'default' => 12,
                    ],
                ],
                'template' => 'landings/multimedia-galeria-grid',
            ],
            'albumes' => [
                'label' => __('Álbumes', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'description' => __('Listado de álbumes', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'category' => 'listings',
                'icon' => 'dashicons-images-alt2',
                'fields' => [
                    'titulo' => [
                        'type' => 'text',
                        'label' => __('Título', FLAVOR_PLATFORM_TEXT_DOMAIN),
                        'default' => __('Álbumes de la Comunidad', FLAVOR_PLATFORM_TEXT_DOMAIN),
                    ],
                    'columnas' => [
                        'type' => 'select',
                        'label' => __('Columnas', FLAVOR_PLATFORM_TEXT_DOMAIN),
                        'options' => [2, 3, 4],
                        'default' => 3,
                    ],
                    'limite' => [
                        'type' => 'number',
                        'label' => __('Número máximo', FLAVOR_PLATFORM_TEXT_DOMAIN),
                        'default' => 9,
                    ],
                ],
                'template' => 'landings/multimedia-albumes',
            ],

            // EMPRESARIAL ADICIONALES
            'empresarial_portfolio' => [
                'label' => __('Portfolio Empresarial', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'description' => __('Galería de proyectos y casos de éxito', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'category' => 'listings',
                'icon' => 'dashicons-portfolio',
                'fields' => [
                    'titulo_seccion' => [
                        'type' => 'text',
                        'label' => __('Título', FLAVOR_PLATFORM_TEXT_DOMAIN),
                        'default' => __('Casos de Éxito', FLAVOR_PLATFORM_TEXT_DOMAIN),
                    ],
                    'descripcion_seccion' => [
                        'type' => 'textarea',
                        'label' => __('Descripción', FLAVOR_PLATFORM_TEXT_DOMAIN),
                        'default' => __('Proyectos que transformaron negocios', FLAVOR_PLATFORM_TEXT_DOMAIN),
                    ],
                    'layout' => [
                        'type' => 'select',
                        'label' => __('Layout', FLAVOR_PLATFORM_TEXT_DOMAIN),
                        'options' => ['grid', 'masonry', 'carousel'],
                        'default' => 'masonry',
                    ],
                    'columnas' => [
                        'type' => 'select',
                        'label' => __('Columnas', FLAVOR_PLATFORM_TEXT_DOMAIN),
                        'options' => ['2', '3', '4'],
                        'default' => '3',
                    ],
                    'numero_proyectos' => [
                        'type' => 'number',
                        'label' => __('Número de proyectos', FLAVOR_PLATFORM_TEXT_DOMAIN),
                        'default' => 6,
                    ],
                    'mostrar_filtros' => [
                        'type' => 'toggle',
                        'label' => __('Mostrar filtros', FLAVOR_PLATFORM_TEXT_DOMAIN),
                        'default' => true,
                    ],
                ],
                'template' => 'landings/empresarial-portfolio',
            ],

            // ========================================
            // PARTICIPACIÓN CIUDADANA
            // ========================================
            'participacion_hero' => [
                'label' => __('Hero Participación Ciudadana', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'description' => __('Sección hero para participación ciudadana', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'category' => 'hero',
                'icon' => 'dashicons-megaphone',
                'fields' => [
                    'titulo' => ['type' => 'text', 'label' => __('Título', FLAVOR_PLATFORM_TEXT_DOMAIN), 'default' => __('Participa en tu Comunidad', FLAVOR_PLATFORM_TEXT_DOMAIN)],
                    'subtitulo' => ['type' => 'textarea', 'label' => __('Subtítulo', FLAVOR_PLATFORM_TEXT_DOMAIN), 'default' => __('Tu voz importa. Propón, debate y vota', FLAVOR_PLATFORM_TEXT_DOMAIN)],
                    'imagen_fondo' => ['type' => 'image', 'label' => __('Imagen de fondo', FLAVOR_PLATFORM_TEXT_DOMAIN), 'default' => ''],
                    'color_primario' => ['type' => 'color', 'label' => __('Color primario', FLAVOR_PLATFORM_TEXT_DOMAIN), 'default' => '#f59e0b'],
                ],
                'template' => 'participacion/hero',
            ],
            'participacion_propuestas_grid' => [
                'label' => __('Grid de Propuestas', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'description' => __('Listado de propuestas ciudadanas', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'category' => 'listings',
                'icon' => 'dashicons-editor-ul',
                'fields' => [
                    'titulo' => ['type' => 'text', 'label' => __('Título', FLAVOR_PLATFORM_TEXT_DOMAIN), 'default' => __('Propuestas Ciudadanas', FLAVOR_PLATFORM_TEXT_DOMAIN)],
                    'columnas' => ['type' => 'select', 'label' => __('Columnas', FLAVOR_PLATFORM_TEXT_DOMAIN), 'options' => [2, 3], 'default' => 3],
                    'limite' => ['type' => 'number', 'label' => __('Número máximo', FLAVOR_PLATFORM_TEXT_DOMAIN), 'default' => 6],
                ],
                'template' => 'participacion/propuestas-grid',
            ],
            'participacion_como_participar' => [
                'label' => __('Cómo Participar', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'description' => __('Sección de proceso de participación', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'category' => 'features',
                'icon' => 'dashicons-lightbulb',
                'fields' => [
                    'titulo' => ['type' => 'text', 'label' => __('Título', FLAVOR_PLATFORM_TEXT_DOMAIN), 'default' => __('¿Cómo participar?', FLAVOR_PLATFORM_TEXT_DOMAIN)],
                ],
                'template' => 'participacion/como-participar',
            ],
            'participacion_cta' => [
                'label' => __('CTA Propuesta', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'description' => __('Llamada a la acción para enviar propuesta', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'category' => 'cta',
                'icon' => 'dashicons-admin-comments',
                'fields' => [
                    'titulo' => ['type' => 'text', 'label' => __('Título', FLAVOR_PLATFORM_TEXT_DOMAIN), 'default' => __('Tu voz importa', FLAVOR_PLATFORM_TEXT_DOMAIN)],
                    'url_boton' => ['type' => 'url', 'label' => __('URL del botón', FLAVOR_PLATFORM_TEXT_DOMAIN), 'default' => '#'],
                ],
                'template' => 'participacion/cta-propuesta',
            ],

            // ========================================
            // PRESUPUESTOS PARTICIPATIVOS
            // ========================================
            'presupuestos_hero' => [
                'label' => __('Hero Presupuestos Participativos', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'description' => __('Sección hero para presupuestos participativos', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'category' => 'hero',
                'icon' => 'dashicons-chart-pie',
                'fields' => [
                    'titulo' => ['type' => 'text', 'label' => __('Título', FLAVOR_PLATFORM_TEXT_DOMAIN), 'default' => __('Presupuestos Participativos', FLAVOR_PLATFORM_TEXT_DOMAIN)],
                    'subtitulo' => ['type' => 'textarea', 'label' => __('Subtítulo', FLAVOR_PLATFORM_TEXT_DOMAIN), 'default' => __('Decide en qué se invierte el dinero público', FLAVOR_PLATFORM_TEXT_DOMAIN)],
                    'imagen_fondo' => ['type' => 'image', 'label' => __('Imagen de fondo', FLAVOR_PLATFORM_TEXT_DOMAIN), 'default' => ''],
                    'color_primario' => ['type' => 'color', 'label' => __('Color primario', FLAVOR_PLATFORM_TEXT_DOMAIN), 'default' => '#eab308'],
                ],
                'template' => 'presupuestos-participativos/hero',
            ],
            'presupuestos_proyectos_grid' => [
                'label' => __('Grid de Proyectos', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'description' => __('Listado de proyectos presupuestarios', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'category' => 'listings',
                'icon' => 'dashicons-grid-view',
                'fields' => [
                    'titulo' => ['type' => 'text', 'label' => __('Título', FLAVOR_PLATFORM_TEXT_DOMAIN), 'default' => __('Proyectos Propuestos', FLAVOR_PLATFORM_TEXT_DOMAIN)],
                    'limite' => ['type' => 'number', 'label' => __('Número máximo', FLAVOR_PLATFORM_TEXT_DOMAIN), 'default' => 6],
                ],
                'template' => 'presupuestos-participativos/proyectos-grid',
            ],
            'presupuestos_proceso' => [
                'label' => __('Proceso de Votación', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'description' => __('Timeline del proceso de presupuestos', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'category' => 'features',
                'icon' => 'dashicons-clock',
                'fields' => [
                    'titulo' => ['type' => 'text', 'label' => __('Título', FLAVOR_PLATFORM_TEXT_DOMAIN), 'default' => __('Proceso', FLAVOR_PLATFORM_TEXT_DOMAIN)],
                ],
                'template' => 'presupuestos-participativos/proceso-votacion',
            ],
            'presupuestos_resultados' => [
                'label' => __('Resultados', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'description' => __('Dashboard de resultados de presupuestos', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'category' => 'content',
                'icon' => 'dashicons-chart-bar',
                'fields' => [
                    'titulo' => ['type' => 'text', 'label' => __('Título', FLAVOR_PLATFORM_TEXT_DOMAIN), 'default' => __('Resultados', FLAVOR_PLATFORM_TEXT_DOMAIN)],
                ],
                'template' => 'presupuestos-participativos/resultados',
            ],

            // ========================================
            // TRANSPARENCIA
            // ========================================
            'transparencia_hero' => [
                'label' => __('Hero Transparencia', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'description' => __('Portal de transparencia', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'category' => 'hero',
                'icon' => 'dashicons-visibility',
                'fields' => [
                    'titulo' => ['type' => 'text', 'label' => __('Título', FLAVOR_PLATFORM_TEXT_DOMAIN), 'default' => __('Portal de Transparencia', FLAVOR_PLATFORM_TEXT_DOMAIN)],
                    'subtitulo' => ['type' => 'textarea', 'label' => __('Subtítulo', FLAVOR_PLATFORM_TEXT_DOMAIN), 'default' => __('Información pública accesible para todos', FLAVOR_PLATFORM_TEXT_DOMAIN)],
                    'imagen_fondo' => ['type' => 'image', 'label' => __('Imagen de fondo', FLAVOR_PLATFORM_TEXT_DOMAIN), 'default' => ''],
                    'color_primario' => ['type' => 'color', 'label' => __('Color primario', FLAVOR_PLATFORM_TEXT_DOMAIN), 'default' => '#14b8a6'],
                ],
                'template' => 'transparencia/hero',
            ],
            'transparencia_datos_grid' => [
                'label' => __('Grid de Datos Abiertos', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'description' => __('Categorías de datos abiertos', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'category' => 'listings',
                'icon' => 'dashicons-media-spreadsheet',
                'fields' => [
                    'titulo' => ['type' => 'text', 'label' => __('Título', FLAVOR_PLATFORM_TEXT_DOMAIN), 'default' => __('Datos Abiertos', FLAVOR_PLATFORM_TEXT_DOMAIN)],
                ],
                'template' => 'transparencia/datos-grid',
            ],
            'transparencia_indicadores' => [
                'label' => __('Indicadores', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'description' => __('Dashboard de indicadores clave', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'category' => 'content',
                'icon' => 'dashicons-performance',
                'fields' => [
                    'titulo' => ['type' => 'text', 'label' => __('Título', FLAVOR_PLATFORM_TEXT_DOMAIN), 'default' => __('Indicadores Clave', FLAVOR_PLATFORM_TEXT_DOMAIN)],
                ],
                'template' => 'transparencia/indicadores',
            ],
            'transparencia_documentos' => [
                'label' => __('Documentos', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'description' => __('Repositorio de documentos públicos', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'category' => 'listings',
                'icon' => 'dashicons-media-document',
                'fields' => [
                    'titulo' => ['type' => 'text', 'label' => __('Título', FLAVOR_PLATFORM_TEXT_DOMAIN), 'default' => __('Documentos Públicos', FLAVOR_PLATFORM_TEXT_DOMAIN)],
                ],
                'template' => 'transparencia/documentos',
            ],

            // ========================================
            // TRÁMITES
            // ========================================
            'tramites_hero' => [
                'label' => __('Hero Trámites', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'description' => __('Sección hero para trámites online', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'category' => 'hero',
                'icon' => 'dashicons-clipboard',
                'fields' => [
                    'titulo' => ['type' => 'text', 'label' => __('Título', FLAVOR_PLATFORM_TEXT_DOMAIN), 'default' => __('Trámites Online', FLAVOR_PLATFORM_TEXT_DOMAIN)],
                    'subtitulo' => ['type' => 'textarea', 'label' => __('Subtítulo', FLAVOR_PLATFORM_TEXT_DOMAIN), 'default' => __('Realiza tus gestiones sin salir de casa', FLAVOR_PLATFORM_TEXT_DOMAIN)],
                    'imagen_fondo' => ['type' => 'image', 'label' => __('Imagen de fondo', FLAVOR_PLATFORM_TEXT_DOMAIN), 'default' => ''],
                    'color_primario' => ['type' => 'color', 'label' => __('Color primario', FLAVOR_PLATFORM_TEXT_DOMAIN), 'default' => '#f97316'],
                ],
                'template' => 'tramites/hero',
            ],
            'tramites_grid' => [
                'label' => __('Grid de Trámites', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'description' => __('Listado de trámites disponibles', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'category' => 'listings',
                'icon' => 'dashicons-editor-ul',
                'fields' => [
                    'titulo' => ['type' => 'text', 'label' => __('Título', FLAVOR_PLATFORM_TEXT_DOMAIN), 'default' => __('Trámites Disponibles', FLAVOR_PLATFORM_TEXT_DOMAIN)],
                    'limite' => ['type' => 'number', 'label' => __('Número máximo', FLAVOR_PLATFORM_TEXT_DOMAIN), 'default' => 8],
                ],
                'template' => 'tramites/tramites-grid',
            ],
            'tramites_como_funciona' => [
                'label' => __('Cómo Funciona - Trámites', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'description' => __('Proceso de trámites online', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'category' => 'features',
                'icon' => 'dashicons-info',
                'fields' => [
                    'titulo' => ['type' => 'text', 'label' => __('Título', FLAVOR_PLATFORM_TEXT_DOMAIN), 'default' => __('¿Cómo funciona?', FLAVOR_PLATFORM_TEXT_DOMAIN)],
                ],
                'template' => 'tramites/como-funciona',
            ],
            'tramites_cta' => [
                'label' => __('CTA Trámites', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'description' => __('Llamada a la acción para iniciar trámite', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'category' => 'cta',
                'icon' => 'dashicons-yes-alt',
                'fields' => [
                    'titulo' => ['type' => 'text', 'label' => __('Título', FLAVOR_PLATFORM_TEXT_DOMAIN), 'default' => __('Empieza tu trámite ahora', FLAVOR_PLATFORM_TEXT_DOMAIN)],
                    'url_boton' => ['type' => 'url', 'label' => __('URL del botón', FLAVOR_PLATFORM_TEXT_DOMAIN), 'default' => '#'],
                ],
                'template' => 'tramites/cta-solicitar',
            ],

            // ========================================
            // SOCIOS / MEMBRESÍAS
            // ========================================
            'socios_hero' => [
                'label' => __('Hero Miembros', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'description' => __('Sección hero para membresías', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'category' => 'hero',
                'icon' => 'dashicons-groups',
                'fields' => [
                    'titulo' => ['type' => 'text', 'label' => __('Título', FLAVOR_PLATFORM_TEXT_DOMAIN), 'default' => __('Hazte Miembro', FLAVOR_PLATFORM_TEXT_DOMAIN)],
                    'subtitulo' => ['type' => 'textarea', 'label' => __('Subtítulo', FLAVOR_PLATFORM_TEXT_DOMAIN), 'default' => __('Únete a nuestra comunidad', FLAVOR_PLATFORM_TEXT_DOMAIN)],
                    'imagen_fondo' => ['type' => 'image', 'label' => __('Imagen de fondo', FLAVOR_PLATFORM_TEXT_DOMAIN), 'default' => ''],
                    'color_primario' => ['type' => 'color', 'label' => __('Color primario', FLAVOR_PLATFORM_TEXT_DOMAIN), 'default' => '#f43f5e'],
                ],
                'template' => 'socios/hero',
            ],
            'socios_planes_grid' => [
                'label' => __('Planes de Miembros', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'description' => __('Grid de planes de membresía', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'category' => 'listings',
                'icon' => 'dashicons-money-alt',
                'fields' => [
                    'titulo' => ['type' => 'text', 'label' => __('Título', FLAVOR_PLATFORM_TEXT_DOMAIN), 'default' => __('Elige tu Plan', FLAVOR_PLATFORM_TEXT_DOMAIN)],
                ],
                'template' => 'socios/planes-grid',
            ],
            'socios_beneficios' => [
                'label' => __('Beneficios de Miembros', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'description' => __('Lista de beneficios de membresía', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'category' => 'features',
                'icon' => 'dashicons-star-filled',
                'fields' => [
                    'titulo' => ['type' => 'text', 'label' => __('Título', FLAVOR_PLATFORM_TEXT_DOMAIN), 'default' => __('Beneficios Exclusivos', FLAVOR_PLATFORM_TEXT_DOMAIN)],
                ],
                'template' => 'socios/beneficios',
            ],
            'socios_cta' => [
                'label' => __('CTA Miembros', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'description' => __('Llamada a la acción para unirse', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'category' => 'cta',
                'icon' => 'dashicons-plus-alt',
                'fields' => [
                    'titulo' => ['type' => 'text', 'label' => __('Título', FLAVOR_PLATFORM_TEXT_DOMAIN), 'default' => __('No te quedes fuera', FLAVOR_PLATFORM_TEXT_DOMAIN)],
                    'url_boton' => ['type' => 'url', 'label' => __('URL del botón', FLAVOR_PLATFORM_TEXT_DOMAIN), 'default' => '#'],
                ],
                'template' => 'socios/cta-unirse',
            ],

            // ========================================
            // MARKETPLACE
            // ========================================
            'marketplace_hero' => [
                'label' => __('Hero Marketplace', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'description' => __('Sección hero para marketplace local', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'category' => 'hero',
                'icon' => 'dashicons-cart',
                'fields' => [
                    'titulo' => ['type' => 'text', 'label' => __('Título', FLAVOR_PLATFORM_TEXT_DOMAIN), 'default' => __('Marketplace Local', FLAVOR_PLATFORM_TEXT_DOMAIN)],
                    'subtitulo' => ['type' => 'textarea', 'label' => __('Subtítulo', FLAVOR_PLATFORM_TEXT_DOMAIN), 'default' => __('Compra, vende e intercambia en tu barrio', FLAVOR_PLATFORM_TEXT_DOMAIN)],
                    'imagen_fondo' => ['type' => 'image', 'label' => __('Imagen de fondo', FLAVOR_PLATFORM_TEXT_DOMAIN), 'default' => ''],
                    'mostrar_buscador' => ['type' => 'toggle', 'label' => __('Mostrar buscador', FLAVOR_PLATFORM_TEXT_DOMAIN), 'default' => true],
                    'color_primario' => ['type' => 'color', 'label' => __('Color primario', FLAVOR_PLATFORM_TEXT_DOMAIN), 'default' => '#84cc16'],
                ],
                'template' => 'marketplace/hero',
            ],
            'marketplace_productos_grid' => [
                'label' => __('Grid de Productos', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'description' => __('Listado de productos del marketplace', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'category' => 'listings',
                'icon' => 'dashicons-grid-view',
                'fields' => [
                    'titulo' => ['type' => 'text', 'label' => __('Título', FLAVOR_PLATFORM_TEXT_DOMAIN), 'default' => __('Productos Destacados', FLAVOR_PLATFORM_TEXT_DOMAIN)],
                    'columnas' => ['type' => 'select', 'label' => __('Columnas', FLAVOR_PLATFORM_TEXT_DOMAIN), 'options' => [2, 3, 4], 'default' => 3],
                    'limite' => ['type' => 'number', 'label' => __('Número máximo', FLAVOR_PLATFORM_TEXT_DOMAIN), 'default' => 6],
                ],
                'template' => 'marketplace/productos-grid',
            ],
            'marketplace_categorias' => [
                'label' => __('Categorías Marketplace', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'description' => __('Navegador de categorías del marketplace', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'category' => 'navigation',
                'icon' => 'dashicons-tag',
                'fields' => [
                    'titulo' => ['type' => 'text', 'label' => __('Título', FLAVOR_PLATFORM_TEXT_DOMAIN), 'default' => __('Explora por Categoría', FLAVOR_PLATFORM_TEXT_DOMAIN)],
                ],
                'template' => 'marketplace/categorias',
            ],
            'marketplace_cta' => [
                'label' => __('CTA Marketplace', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'description' => __('Llamada a la acción para vender', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'category' => 'cta',
                'icon' => 'dashicons-megaphone',
                'fields' => [
                    'titulo' => ['type' => 'text', 'label' => __('Título', FLAVOR_PLATFORM_TEXT_DOMAIN), 'default' => __('¿Tienes algo que ya no necesitas?', FLAVOR_PLATFORM_TEXT_DOMAIN)],
                    'url_boton' => ['type' => 'url', 'label' => __('URL del botón', FLAVOR_PLATFORM_TEXT_DOMAIN), 'default' => '#'],
                ],
                'template' => 'marketplace/cta-vender',
            ],

            // ========================================
            // FACTURAS
            // ========================================
            'facturas_hero' => [
                'label' => __('Hero Facturas', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'description' => __('Gestión de facturación', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'category' => 'hero',
                'icon' => 'dashicons-media-text',
                'fields' => [
                    'titulo' => ['type' => 'text', 'label' => __('Título', FLAVOR_PLATFORM_TEXT_DOMAIN), 'default' => __('Gestión de Facturas', FLAVOR_PLATFORM_TEXT_DOMAIN)],
                    'subtitulo' => ['type' => 'textarea', 'label' => __('Subtítulo', FLAVOR_PLATFORM_TEXT_DOMAIN), 'default' => __('Crea, envía y gestiona tus facturas fácilmente', FLAVOR_PLATFORM_TEXT_DOMAIN)],
                    'color_primario' => ['type' => 'color', 'label' => __('Color primario', FLAVOR_PLATFORM_TEXT_DOMAIN), 'default' => '#14b8a6'],
                ],
                'template' => 'facturas/hero',
            ],
            'facturas_features' => [
                'label' => __('Características Facturas', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'description' => __('Funcionalidades del sistema de facturación', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'category' => 'features',
                'icon' => 'dashicons-yes',
                'fields' => [
                    'titulo' => ['type' => 'text', 'label' => __('Título', FLAVOR_PLATFORM_TEXT_DOMAIN), 'default' => __('Funcionalidades', FLAVOR_PLATFORM_TEXT_DOMAIN)],
                ],
                'template' => 'facturas/features',
            ],

            // ========================================
            // FICHAJE EMPLEADOS
            // ========================================
            'fichaje_hero' => [
                'label' => __('Hero Fichaje', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'description' => __('Control de fichaje de empleados', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'category' => 'hero',
                'icon' => 'dashicons-clock',
                'fields' => [
                    'titulo' => ['type' => 'text', 'label' => __('Título', FLAVOR_PLATFORM_TEXT_DOMAIN), 'default' => __('Control de Fichaje', FLAVOR_PLATFORM_TEXT_DOMAIN)],
                    'subtitulo' => ['type' => 'textarea', 'label' => __('Subtítulo', FLAVOR_PLATFORM_TEXT_DOMAIN), 'default' => __('Gestiona horarios y asistencia de tu equipo', FLAVOR_PLATFORM_TEXT_DOMAIN)],
                    'color_primario' => ['type' => 'color', 'label' => __('Color primario', FLAVOR_PLATFORM_TEXT_DOMAIN), 'default' => '#64748b'],
                ],
                'template' => 'fichaje-empleados/hero',
            ],
            'fichaje_features' => [
                'label' => __('Características Fichaje', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'description' => __('Funcionalidades del control de fichaje', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'category' => 'features',
                'icon' => 'dashicons-list-view',
                'fields' => [
                    'titulo' => ['type' => 'text', 'label' => __('Título', FLAVOR_PLATFORM_TEXT_DOMAIN), 'default' => __('Funcionalidades', FLAVOR_PLATFORM_TEXT_DOMAIN)],
                ],
                'template' => 'fichaje-empleados/features',
            ],

            // ========================================
            // TRADING IA
            // ========================================
            'trading_ia_hero' => [
                'label' => __('Hero Trading IA', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'description' => __('Trading con inteligencia artificial', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'category' => 'hero',
                'icon' => 'dashicons-chart-line',
                'fields' => [
                    'titulo' => ['type' => 'text', 'label' => __('Título', FLAVOR_PLATFORM_TEXT_DOMAIN), 'default' => __('Trading con IA', FLAVOR_PLATFORM_TEXT_DOMAIN)],
                    'subtitulo' => ['type' => 'textarea', 'label' => __('Subtítulo', FLAVOR_PLATFORM_TEXT_DOMAIN), 'default' => __('Análisis predictivo impulsado por IA', FLAVOR_PLATFORM_TEXT_DOMAIN)],
                    'color_primario' => ['type' => 'color', 'label' => __('Color primario', FLAVOR_PLATFORM_TEXT_DOMAIN), 'default' => '#06b6d4'],
                ],
                'template' => 'trading-ia/hero',
            ],
            'trading_ia_features' => [
                'label' => __('Características Trading IA', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'description' => __('Funcionalidades del trading con IA', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'category' => 'features',
                'icon' => 'dashicons-performance',
                'fields' => [
                    'titulo' => ['type' => 'text', 'label' => __('Título', FLAVOR_PLATFORM_TEXT_DOMAIN), 'default' => __('Funcionalidades', FLAVOR_PLATFORM_TEXT_DOMAIN)],
                ],
                'template' => 'trading-ia/features',
            ],
            'trading_ia_stats' => [
                'label' => __('Estadísticas Trading', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'description' => __('Dashboard de estadísticas de trading', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'category' => 'content',
                'icon' => 'dashicons-chart-bar',
                'fields' => [
                    'titulo' => ['type' => 'text', 'label' => __('Título', FLAVOR_PLATFORM_TEXT_DOMAIN), 'default' => __('Rendimiento', FLAVOR_PLATFORM_TEXT_DOMAIN)],
                ],
                'template' => 'trading-ia/stats',
            ],

            // ========================================
            // DEX SOLANA
            // ========================================
            'dex_solana_hero' => [
                'label' => __('Hero DEX Solana', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'description' => __('Exchange descentralizado en Solana', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'category' => 'hero',
                'icon' => 'dashicons-randomize',
                'fields' => [
                    'titulo' => ['type' => 'text', 'label' => __('Título', FLAVOR_PLATFORM_TEXT_DOMAIN), 'default' => __('DEX en Solana', FLAVOR_PLATFORM_TEXT_DOMAIN)],
                    'subtitulo' => ['type' => 'textarea', 'label' => __('Subtítulo', FLAVOR_PLATFORM_TEXT_DOMAIN), 'default' => __('Intercambia tokens de forma descentralizada', FLAVOR_PLATFORM_TEXT_DOMAIN)],
                    'color_primario' => ['type' => 'color', 'label' => __('Color primario', FLAVOR_PLATFORM_TEXT_DOMAIN), 'default' => '#8b5cf6'],
                ],
                'template' => 'dex-solana/hero',
            ],
            'dex_solana_features' => [
                'label' => __('Características DEX', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'description' => __('Funcionalidades del DEX', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'category' => 'features',
                'icon' => 'dashicons-shield',
                'fields' => [
                    'titulo' => ['type' => 'text', 'label' => __('Título', FLAVOR_PLATFORM_TEXT_DOMAIN), 'default' => __('Funcionalidades', FLAVOR_PLATFORM_TEXT_DOMAIN)],
                ],
                'template' => 'dex-solana/features',
            ],
            'dex_solana_cta' => [
                'label' => __('CTA Conectar Wallet', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'description' => __('Llamada a la acción para conectar wallet', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'category' => 'cta',
                'icon' => 'dashicons-admin-links',
                'fields' => [
                    'titulo' => ['type' => 'text', 'label' => __('Título', FLAVOR_PLATFORM_TEXT_DOMAIN), 'default' => __('Conecta tu Wallet', FLAVOR_PLATFORM_TEXT_DOMAIN)],
                ],
                'template' => 'dex-solana/cta-conectar',
            ],

            // ========================================
            // WOOCOMMERCE
            // ========================================
            'woocommerce_hero' => [
                'label' => __('Hero WooCommerce', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'description' => __('Sección hero para tienda online', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'category' => 'hero',
                'icon' => 'dashicons-store',
                'fields' => [
                    'titulo' => ['type' => 'text', 'label' => __('Título', FLAVOR_PLATFORM_TEXT_DOMAIN), 'default' => __('Tu Tienda Online', FLAVOR_PLATFORM_TEXT_DOMAIN)],
                    'subtitulo' => ['type' => 'textarea', 'label' => __('Subtítulo', FLAVOR_PLATFORM_TEXT_DOMAIN), 'default' => __('Crea y gestiona tu tienda con WooCommerce', FLAVOR_PLATFORM_TEXT_DOMAIN)],
                    'imagen_fondo' => ['type' => 'image', 'label' => __('Imagen de fondo', FLAVOR_PLATFORM_TEXT_DOMAIN), 'default' => ''],
                    'color_primario' => ['type' => 'color', 'label' => __('Color primario', FLAVOR_PLATFORM_TEXT_DOMAIN), 'default' => '#7c3aed'],
                ],
                'template' => 'woocommerce/hero',
            ],
            'woocommerce_productos_grid' => [
                'label' => __('Grid Productos WooCommerce', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'description' => __('Listado de productos WooCommerce', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'category' => 'listings',
                'icon' => 'dashicons-products',
                'fields' => [
                    'titulo' => ['type' => 'text', 'label' => __('Título', FLAVOR_PLATFORM_TEXT_DOMAIN), 'default' => __('Productos Destacados', FLAVOR_PLATFORM_TEXT_DOMAIN)],
                    'columnas' => ['type' => 'select', 'label' => __('Columnas', FLAVOR_PLATFORM_TEXT_DOMAIN), 'options' => [2, 3, 4], 'default' => 3],
                    'limite' => ['type' => 'number', 'label' => __('Número máximo', FLAVOR_PLATFORM_TEXT_DOMAIN), 'default' => 6],
                ],
                'template' => 'woocommerce/productos-grid',
            ],
            'woocommerce_categorias' => [
                'label' => __('Categorías WooCommerce', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'description' => __('Navegador de categorías de tienda', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'category' => 'navigation',
                'icon' => 'dashicons-category',
                'fields' => [
                    'titulo' => ['type' => 'text', 'label' => __('Título', FLAVOR_PLATFORM_TEXT_DOMAIN), 'default' => __('Categorías', FLAVOR_PLATFORM_TEXT_DOMAIN)],
                ],
                'template' => 'woocommerce/categorias',
            ],
            'woocommerce_cta' => [
                'label' => __('CTA Tienda', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'description' => __('Llamada a la acción para la tienda', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'category' => 'cta',
                'icon' => 'dashicons-cart',
                'fields' => [
                    'titulo' => ['type' => 'text', 'label' => __('Título', FLAVOR_PLATFORM_TEXT_DOMAIN), 'default' => __('Descubre nuestro catálogo', FLAVOR_PLATFORM_TEXT_DOMAIN)],
                    'url_boton' => ['type' => 'url', 'label' => __('URL del botón', FLAVOR_PLATFORM_TEXT_DOMAIN), 'default' => '/tienda'],
                ],
                'template' => 'woocommerce/cta-comprar',
            ],

            // ========================================
            // CHAT GRUPOS (componentes landing)
            // ========================================
            'chat_grupos_hero_landing' => [
                'label' => __('Hero Chat Grupos', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'description' => __('Sección hero para grupos de chat', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'category' => 'hero',
                'icon' => 'dashicons-format-chat',
                'fields' => [
                    'titulo' => ['type' => 'text', 'label' => __('Título', FLAVOR_PLATFORM_TEXT_DOMAIN), 'default' => __('Grupos de Chat', FLAVOR_PLATFORM_TEXT_DOMAIN)],
                    'subtitulo' => ['type' => 'textarea', 'label' => __('Subtítulo', FLAVOR_PLATFORM_TEXT_DOMAIN), 'default' => __('Conecta con personas que comparten tus intereses', FLAVOR_PLATFORM_TEXT_DOMAIN)],
                    'color_primario' => ['type' => 'color', 'label' => __('Color primario', FLAVOR_PLATFORM_TEXT_DOMAIN), 'default' => '#ec4899'],
                ],
                'template' => 'chat-grupos/hero',
            ],
            'chat_grupos_grid' => [
                'label' => __('Grid de Grupos', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'description' => __('Listado de grupos de chat', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'category' => 'listings',
                'icon' => 'dashicons-groups',
                'fields' => [
                    'titulo' => ['type' => 'text', 'label' => __('Título', FLAVOR_PLATFORM_TEXT_DOMAIN), 'default' => __('Grupos Activos', FLAVOR_PLATFORM_TEXT_DOMAIN)],
                    'limite' => ['type' => 'number', 'label' => __('Número máximo', FLAVOR_PLATFORM_TEXT_DOMAIN), 'default' => 6],
                ],
                'template' => 'chat-grupos/grupos-grid',
            ],

            // ========================================
            // CHAT INTERNO (componentes landing)
            // ========================================
            'chat_interno_hero_landing' => [
                'label' => __('Hero Chat Interno', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'description' => __('Sección hero para mensajería interna', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'category' => 'hero',
                'icon' => 'dashicons-email-alt',
                'fields' => [
                    'titulo' => ['type' => 'text', 'label' => __('Título', FLAVOR_PLATFORM_TEXT_DOMAIN), 'default' => __('Mensajería Interna', FLAVOR_PLATFORM_TEXT_DOMAIN)],
                    'subtitulo' => ['type' => 'textarea', 'label' => __('Subtítulo', FLAVOR_PLATFORM_TEXT_DOMAIN), 'default' => __('Comunicación segura con tu comunidad', FLAVOR_PLATFORM_TEXT_DOMAIN)],
                    'color_primario' => ['type' => 'color', 'label' => __('Color primario', FLAVOR_PLATFORM_TEXT_DOMAIN), 'default' => '#f43f5e'],
                ],
                'template' => 'chat-interno/hero',
            ],
            'chat_interno_features' => [
                'label' => __('Características Chat Interno', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'description' => __('Funcionalidades de la mensajería', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'category' => 'features',
                'icon' => 'dashicons-shield-alt',
                'fields' => [
                    'titulo' => ['type' => 'text', 'label' => __('Título', FLAVOR_PLATFORM_TEXT_DOMAIN), 'default' => __('Funcionalidades', FLAVOR_PLATFORM_TEXT_DOMAIN)],
                ],
                'template' => 'chat-interno/features',
            ],
        ];
    }

    /**
     * Cargar componentes web de TODOS los módulos registrados
     * (incluso si no están activados)
     */
    private function load_components_from_all_modules() {
        $modules_dir = dirname(dirname(__FILE__)) . '/modules';

        // Lista de módulos con componentes web
        $modules_with_components = [
            'carpooling' => flavor_get_runtime_class_name('Flavor_Chat_Carpooling_Module'),
            'cursos' => flavor_get_runtime_class_name('Flavor_Chat_Cursos_Module'),
            'biblioteca' => flavor_get_runtime_class_name('Flavor_Chat_Biblioteca_Module'),
            'talleres' => flavor_get_runtime_class_name('Flavor_Chat_Talleres_Module'),
            'huertos-urbanos' => flavor_get_runtime_class_name('Flavor_Chat_Huertos_Urbanos_Module'),
            'espacios-comunes' => flavor_get_runtime_class_name('Flavor_Chat_Espacios_Comunes_Module'),
            'bicicletas-compartidas' => flavor_get_runtime_class_name('Flavor_Chat_Bicicletas_Compartidas_Module'),
            'parkings' => flavor_get_runtime_class_name('Flavor_Chat_Parkings_Module'),
            'reciclaje' => flavor_get_runtime_class_name('Flavor_Chat_Reciclaje_Module'),
            'compostaje' => flavor_get_runtime_class_name('Flavor_Chat_Compostaje_Module'),
            'ayuda-vecinal' => flavor_get_runtime_class_name('Flavor_Chat_Ayuda_Vecinal_Module'),
            'podcast' => flavor_get_runtime_class_name('Flavor_Chat_Podcast_Module'),
            'radio' => flavor_get_runtime_class_name('Flavor_Chat_Radio_Module'),
            'red-social' => flavor_get_runtime_class_name('Flavor_Chat_Red_Social_Module'),
            'multimedia' => flavor_get_runtime_class_name('Flavor_Chat_Multimedia_Module'),
            'chat-grupos' => flavor_get_runtime_class_name('Flavor_Chat_Chat_Grupos_Module'),
            'chat-interno' => flavor_get_runtime_class_name('Flavor_Chat_Chat_Interno_Module'),
            'empresarial' => flavor_get_runtime_class_name('Flavor_Chat_Empresarial_Module'),
            'themacle' => flavor_get_runtime_class_name('Flavor_Chat_Themacle_Module'),
        ];

        foreach ($modules_with_components as $module_id => $class_name) {
            $module_file = $modules_dir . '/' . $module_id . '/class-' . $module_id . '-module.php';

            if (file_exists($module_file)) {
                require_once $module_file;

                if (class_exists($class_name)) {
                    // Instanciar temporalmente solo para obtener componentes
                    $temp_module = new $class_name();

                    if (method_exists($temp_module, 'get_web_components')) {
                        $components = $temp_module->get_web_components();

                        if (is_array($components)) {
                            foreach ($components as $component_id => $component_data) {
                                $this->register_component(
                                    $module_id . '_' . $component_id,
                                    $component_data,
                                    $module_id
                                );
                            }
                        }
                    }
                }
            }
        }
    }

    /**
     * Registrar los 20 componentes unificados con variantes y presets
     */
    private function registrar_componentes_unificados() {
        if ($this->componentes_unificados_registrados) {
            return;
        }

        $componentes_unificados = $this->get_definiciones_componentes_unificados();

        foreach ($componentes_unificados as $componente_id => $definicion_componente) {
            $this->register_component($componente_id, $definicion_componente, 'unified');
        }

        $this->componentes_unificados_registrados = true;
    }

    /**
     * Marcar componentes legacy como deprecated
     * Los que tienen un alias en el mapa se marcan deprecated
     */
    private function marcar_componentes_deprecated() {
        foreach ($this->alias_mapa_componentes as $id_legacy => $datos_alias) {
            if (isset($this->components[$id_legacy])) {
                $this->components[$id_legacy]['deprecated'] = true;
            }
        }
    }

    /**
     * Definiciones de los 20 componentes unificados
     */
    private function get_definiciones_componentes_unificados() {
        return [
            'unified_hero' => [
                'label' => __('Hero', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'description' => __('Sección hero con múltiples variantes visuales', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'category' => 'hero',
                'icon' => 'dashicons-cover-image',
                'variants' => [
                    'centrado' => ['label' => __('Centrado', FLAVOR_PLATFORM_TEXT_DOMAIN), 'description' => __('Título y subtítulo centrados con fondo', FLAVOR_PLATFORM_TEXT_DOMAIN)],
                    'split_izquierda' => ['label' => __('Split Izquierda', FLAVOR_PLATFORM_TEXT_DOMAIN), 'description' => __('Texto izquierda, imagen derecha', FLAVOR_PLATFORM_TEXT_DOMAIN)],
                    'split_derecha' => ['label' => __('Split Derecha', FLAVOR_PLATFORM_TEXT_DOMAIN), 'description' => __('Imagen izquierda, texto derecha', FLAVOR_PLATFORM_TEXT_DOMAIN)],
                    'con_buscador' => ['label' => __('Con Buscador', FLAVOR_PLATFORM_TEXT_DOMAIN), 'description' => __('Hero con barra de búsqueda prominente', FLAVOR_PLATFORM_TEXT_DOMAIN)],
                    'con_estadisticas' => ['label' => __('Con Estadísticas', FLAVOR_PLATFORM_TEXT_DOMAIN), 'description' => __('Hero con contadores de estadísticas', FLAVOR_PLATFORM_TEXT_DOMAIN)],
                    'minimalista' => ['label' => __('Minimalista', FLAVOR_PLATFORM_TEXT_DOMAIN), 'description' => __('Hero limpio y simple', FLAVOR_PLATFORM_TEXT_DOMAIN)],
                    'con_video' => ['label' => __('Con Video', FLAVOR_PLATFORM_TEXT_DOMAIN), 'description' => __('Hero con video embebido', FLAVOR_PLATFORM_TEXT_DOMAIN)],
                    'con_tarjetas' => ['label' => __('Con Tarjetas', FLAVOR_PLATFORM_TEXT_DOMAIN), 'description' => __('Hero con tarjetas flotantes', FLAVOR_PLATFORM_TEXT_DOMAIN)],
                ],
                'presets' => [
                    'carpooling' => ['label' => __('Carpooling', FLAVOR_PLATFORM_TEXT_DOMAIN), 'icon' => 'dashicons-car', 'values' => ['variante' => 'con_buscador', 'titulo' => __('Comparte Viaje', FLAVOR_PLATFORM_TEXT_DOMAIN), 'color_primario' => '#3b82f6']],
                    'banco_tiempo' => ['label' => __('Banco de Tiempo', FLAVOR_PLATFORM_TEXT_DOMAIN), 'icon' => 'dashicons-clock', 'values' => ['variante' => 'centrado', 'titulo' => __('Banco de Tiempo', FLAVOR_PLATFORM_TEXT_DOMAIN), 'color_primario' => '#8b5cf6']],
                    'marketplace' => ['label' => __('Marketplace', FLAVOR_PLATFORM_TEXT_DOMAIN), 'icon' => 'dashicons-store', 'values' => ['variante' => 'con_buscador', 'titulo' => __('Marketplace', FLAVOR_PLATFORM_TEXT_DOMAIN), 'color_primario' => '#f59e0b']],
                    'empresarial' => ['label' => __('Empresarial', FLAVOR_PLATFORM_TEXT_DOMAIN), 'icon' => 'dashicons-building', 'values' => ['variante' => 'split_izquierda', 'titulo' => __('Tu Empresa', FLAVOR_PLATFORM_TEXT_DOMAIN), 'color_primario' => '#1d4ed8']],
                ],
                'fields' => [
                    'variante' => ['type' => 'variant_selector', 'label' => __('Variante visual', FLAVOR_PLATFORM_TEXT_DOMAIN), 'default' => 'centrado'],
                    'titulo' => ['type' => 'text', 'label' => __('Título', FLAVOR_PLATFORM_TEXT_DOMAIN), 'default' => __('Título Principal', FLAVOR_PLATFORM_TEXT_DOMAIN)],
                    'subtitulo' => ['type' => 'textarea', 'label' => __('Subtítulo', FLAVOR_PLATFORM_TEXT_DOMAIN), 'default' => ''],
                    'imagen_fondo' => ['type' => 'image', 'label' => __('Imagen de fondo', FLAVOR_PLATFORM_TEXT_DOMAIN), 'default' => ''],
                    'color_primario' => ['type' => 'color', 'label' => __('Color primario', FLAVOR_PLATFORM_TEXT_DOMAIN), 'default' => '#3b82f6'],
                    'texto_boton' => ['type' => 'text', 'label' => __('Texto del botón', FLAVOR_PLATFORM_TEXT_DOMAIN), 'default' => ''],
                    'url_boton' => ['type' => 'url', 'label' => __('URL del botón', FLAVOR_PLATFORM_TEXT_DOMAIN), 'default' => '#'],
                    'texto_boton_secundario' => ['type' => 'text', 'label' => __('Botón secundario', FLAVOR_PLATFORM_TEXT_DOMAIN), 'default' => ''],
                    'url_boton_secundario' => ['type' => 'url', 'label' => __('URL botón secundario', FLAVOR_PLATFORM_TEXT_DOMAIN), 'default' => '#'],
                    'imagen_lateral' => ['type' => 'image', 'label' => __('Imagen lateral', FLAVOR_PLATFORM_TEXT_DOMAIN), 'default' => '', 'show_when' => ['variante' => ['split_izquierda', 'split_derecha']]],
                    'mostrar_buscador' => ['type' => 'toggle', 'label' => __('Mostrar buscador', FLAVOR_PLATFORM_TEXT_DOMAIN), 'default' => false, 'show_when' => ['variante' => ['con_buscador']]],
                    'placeholder_buscador' => ['type' => 'text', 'label' => __('Placeholder buscador', FLAVOR_PLATFORM_TEXT_DOMAIN), 'default' => __('Buscar...', FLAVOR_PLATFORM_TEXT_DOMAIN), 'show_when' => ['mostrar_buscador' => true]],
                    'mostrar_estadisticas' => ['type' => 'toggle', 'label' => __('Mostrar estadísticas', FLAVOR_PLATFORM_TEXT_DOMAIN), 'default' => false, 'show_when' => ['variante' => ['con_estadisticas']]],
                    'estadisticas' => ['type' => 'repeater', 'label' => __('Estadísticas', FLAVOR_PLATFORM_TEXT_DOMAIN), 'show_when' => ['mostrar_estadisticas' => true], 'max_items' => 4, 'fields' => [
                        'valor' => ['type' => 'text', 'label' => __('Valor', FLAVOR_PLATFORM_TEXT_DOMAIN), 'default' => '100+'],
                        'etiqueta' => ['type' => 'text', 'label' => __('Etiqueta', FLAVOR_PLATFORM_TEXT_DOMAIN), 'default' => __('Usuarios', FLAVOR_PLATFORM_TEXT_DOMAIN)],
                    ]],
                    'url_video' => ['type' => 'url', 'label' => __('URL del video', FLAVOR_PLATFORM_TEXT_DOMAIN), 'default' => '', 'show_when' => ['variante' => ['con_video']]],
                    'overlay_oscuro' => ['type' => 'toggle', 'label' => __('Overlay oscuro', FLAVOR_PLATFORM_TEXT_DOMAIN), 'default' => true],
                ],
                'template' => 'unified/hero',
            ],

            'unified_cta' => [
                'label' => __('Llamada a la Acción', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'description' => __('Sección CTA con múltiples layouts', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'category' => 'cta',
                'icon' => 'dashicons-megaphone',
                'variants' => [
                    'banner_horizontal' => ['label' => __('Banner Horizontal', FLAVOR_PLATFORM_TEXT_DOMAIN), 'description' => __('Banner con texto y botón en fila', FLAVOR_PLATFORM_TEXT_DOMAIN)],
                    'banner_centrado' => ['label' => __('Banner Centrado', FLAVOR_PLATFORM_TEXT_DOMAIN), 'description' => __('Banner centrado con botón', FLAVOR_PLATFORM_TEXT_DOMAIN)],
                    'card_con_imagen' => ['label' => __('Card con Imagen', FLAVOR_PLATFORM_TEXT_DOMAIN), 'description' => __('Tarjeta con imagen lateral', FLAVOR_PLATFORM_TEXT_DOMAIN)],
                    'flotante' => ['label' => __('Flotante', FLAVOR_PLATFORM_TEXT_DOMAIN), 'description' => __('Barra CTA fija en la parte inferior', FLAVOR_PLATFORM_TEXT_DOMAIN)],
                    'minimalista' => ['label' => __('Minimalista', FLAVOR_PLATFORM_TEXT_DOMAIN), 'description' => __('CTA simple inline', FLAVOR_PLATFORM_TEXT_DOMAIN)],
                ],
                'presets' => [],
                'fields' => [
                    'variante' => ['type' => 'variant_selector', 'label' => __('Variante visual', FLAVOR_PLATFORM_TEXT_DOMAIN), 'default' => 'banner_horizontal'],
                    'titulo' => ['type' => 'text', 'label' => __('Título', FLAVOR_PLATFORM_TEXT_DOMAIN), 'default' => ''],
                    'descripcion' => ['type' => 'textarea', 'label' => __('Descripción', FLAVOR_PLATFORM_TEXT_DOMAIN), 'default' => ''],
                    'texto_boton' => ['type' => 'text', 'label' => __('Texto del botón', FLAVOR_PLATFORM_TEXT_DOMAIN), 'default' => __('Empezar', FLAVOR_PLATFORM_TEXT_DOMAIN)],
                    'url_boton' => ['type' => 'url', 'label' => __('URL del botón', FLAVOR_PLATFORM_TEXT_DOMAIN), 'default' => '#'],
                    'color_primario' => ['type' => 'color', 'label' => __('Color primario', FLAVOR_PLATFORM_TEXT_DOMAIN), 'default' => '#3b82f6'],
                    'color_fondo' => ['type' => 'color', 'label' => __('Color de fondo', FLAVOR_PLATFORM_TEXT_DOMAIN), 'default' => ''],
                    'imagen' => ['type' => 'image', 'label' => __('Imagen', FLAVOR_PLATFORM_TEXT_DOMAIN), 'default' => '', 'show_when' => ['variante' => ['card_con_imagen']]],
                    'texto_boton_secundario' => ['type' => 'text', 'label' => __('Botón secundario', FLAVOR_PLATFORM_TEXT_DOMAIN), 'default' => ''],
                    'url_boton_secundario' => ['type' => 'url', 'label' => __('URL secundario', FLAVOR_PLATFORM_TEXT_DOMAIN), 'default' => '#'],
                ],
                'template' => 'unified/cta',
            ],

            'unified_features' => [
                'label' => __('Características', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'description' => __('Sección de características o servicios', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'category' => 'features',
                'icon' => 'dashicons-star-filled',
                'variants' => [
                    'grid_iconos' => ['label' => __('Grid con Iconos', FLAVOR_PLATFORM_TEXT_DOMAIN), 'description' => __('Grid de tarjetas con icono', FLAVOR_PLATFORM_TEXT_DOMAIN)],
                    'lista_alternada' => ['label' => __('Lista Alternada', FLAVOR_PLATFORM_TEXT_DOMAIN), 'description' => __('Features en zigzag izquierda/derecha', FLAVOR_PLATFORM_TEXT_DOMAIN)],
                    'tabs' => ['label' => __('Pestañas', FLAVOR_PLATFORM_TEXT_DOMAIN), 'description' => __('Contenido en pestañas', FLAVOR_PLATFORM_TEXT_DOMAIN)],
                    'acordeon' => ['label' => __('Acordeón', FLAVOR_PLATFORM_TEXT_DOMAIN), 'description' => __('Features desplegables', FLAVOR_PLATFORM_TEXT_DOMAIN)],
                ],
                'presets' => [],
                'fields' => [
                    'variante' => ['type' => 'variant_selector', 'label' => __('Variante', FLAVOR_PLATFORM_TEXT_DOMAIN), 'default' => 'grid_iconos'],
                    'titulo' => ['type' => 'text', 'label' => __('Título', FLAVOR_PLATFORM_TEXT_DOMAIN), 'default' => ''],
                    'subtitulo' => ['type' => 'textarea', 'label' => __('Subtítulo', FLAVOR_PLATFORM_TEXT_DOMAIN), 'default' => ''],
                    'color_primario' => ['type' => 'color', 'label' => __('Color primario', FLAVOR_PLATFORM_TEXT_DOMAIN), 'default' => '#3b82f6'],
                    'columnas' => ['type' => 'select', 'label' => __('Columnas', FLAVOR_PLATFORM_TEXT_DOMAIN), 'options' => [2, 3, 4], 'default' => 3],
                    'items' => ['type' => 'repeater', 'label' => __('Características', FLAVOR_PLATFORM_TEXT_DOMAIN), 'max_items' => 12, 'fields' => [
                        'titulo' => ['type' => 'text', 'label' => __('Título', FLAVOR_PLATFORM_TEXT_DOMAIN), 'default' => ''],
                        'descripcion' => ['type' => 'textarea', 'label' => __('Descripción', FLAVOR_PLATFORM_TEXT_DOMAIN), 'default' => ''],
                        'icono' => ['type' => 'text', 'label' => __('Icono (dashicons)', FLAVOR_PLATFORM_TEXT_DOMAIN), 'default' => 'dashicons-star-filled'],
                    ]],
                ],
                'template' => 'unified/features',
            ],

            'unified_grid' => [
                'label' => __('Grid', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'description' => __('Grid de tarjetas con múltiples layouts', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'category' => 'cards',
                'icon' => 'dashicons-grid-view',
                'variants' => [
                    'cards_imagen' => ['label' => __('Cards con Imagen', FLAVOR_PLATFORM_TEXT_DOMAIN), 'description' => __('Tarjetas con imagen superior', FLAVOR_PLATFORM_TEXT_DOMAIN)],
                    'cards_icono' => ['label' => __('Cards con Icono', FLAVOR_PLATFORM_TEXT_DOMAIN), 'description' => __('Tarjetas con icono', FLAVOR_PLATFORM_TEXT_DOMAIN)],
                    'lista_compacta' => ['label' => __('Lista Compacta', FLAVOR_PLATFORM_TEXT_DOMAIN), 'description' => __('Lista vertical compacta', FLAVOR_PLATFORM_TEXT_DOMAIN)],
                    'masonry' => ['label' => __('Masonry', FLAVOR_PLATFORM_TEXT_DOMAIN), 'description' => __('Layout masonry asimétrico', FLAVOR_PLATFORM_TEXT_DOMAIN)],
                ],
                'presets' => [],
                'fields' => [
                    'variante' => ['type' => 'variant_selector', 'label' => __('Variante', FLAVOR_PLATFORM_TEXT_DOMAIN), 'default' => 'cards_imagen'],
                    'titulo' => ['type' => 'text', 'label' => __('Título', FLAVOR_PLATFORM_TEXT_DOMAIN), 'default' => ''],
                    'subtitulo' => ['type' => 'textarea', 'label' => __('Subtítulo', FLAVOR_PLATFORM_TEXT_DOMAIN), 'default' => ''],
                    'color_primario' => ['type' => 'color', 'label' => __('Color primario', FLAVOR_PLATFORM_TEXT_DOMAIN), 'default' => '#3b82f6'],
                    'columnas' => ['type' => 'select', 'label' => __('Columnas', FLAVOR_PLATFORM_TEXT_DOMAIN), 'options' => [2, 3, 4], 'default' => 3],
                    'limite' => ['type' => 'number', 'label' => __('Límite', FLAVOR_PLATFORM_TEXT_DOMAIN), 'default' => 6],
                    'items' => ['type' => 'repeater', 'label' => __('Items', FLAVOR_PLATFORM_TEXT_DOMAIN), 'max_items' => 12, 'fields' => [
                        'titulo' => ['type' => 'text', 'label' => __('Título', FLAVOR_PLATFORM_TEXT_DOMAIN), 'default' => ''],
                        'descripcion' => ['type' => 'textarea', 'label' => __('Descripción', FLAVOR_PLATFORM_TEXT_DOMAIN), 'default' => ''],
                        'imagen' => ['type' => 'image', 'label' => __('Imagen', FLAVOR_PLATFORM_TEXT_DOMAIN), 'default' => ''],
                        'url' => ['type' => 'url', 'label' => __('URL', FLAVOR_PLATFORM_TEXT_DOMAIN), 'default' => '#'],
                        'icono' => ['type' => 'text', 'label' => __('Icono', FLAVOR_PLATFORM_TEXT_DOMAIN), 'default' => ''],
                    ]],
                ],
                'template' => 'unified/grid',
            ],

            'unified_listing' => [
                'label' => __('Listado con Filtros', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'description' => __('Listado filtrable con múltiples vistas', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'category' => 'listings',
                'icon' => 'dashicons-list-view',
                'variants' => [
                    'grid_filtrable' => ['label' => __('Grid Filtrable', FLAVOR_PLATFORM_TEXT_DOMAIN), 'description' => __('Grid con botones de filtro', FLAVOR_PLATFORM_TEXT_DOMAIN)],
                    'tabla' => ['label' => __('Tabla', FLAVOR_PLATFORM_TEXT_DOMAIN), 'description' => __('Vista de tabla responsiva', FLAVOR_PLATFORM_TEXT_DOMAIN)],
                    'mapa_y_lista' => ['label' => __('Mapa y Lista', FLAVOR_PLATFORM_TEXT_DOMAIN), 'description' => __('Mapa con listado lateral', FLAVOR_PLATFORM_TEXT_DOMAIN)],
                ],
                'presets' => [],
                'fields' => [
                    'variante' => ['type' => 'variant_selector', 'label' => __('Variante', FLAVOR_PLATFORM_TEXT_DOMAIN), 'default' => 'grid_filtrable'],
                    'titulo' => ['type' => 'text', 'label' => __('Título', FLAVOR_PLATFORM_TEXT_DOMAIN), 'default' => ''],
                    'subtitulo' => ['type' => 'textarea', 'label' => __('Subtítulo', FLAVOR_PLATFORM_TEXT_DOMAIN), 'default' => ''],
                    'color_primario' => ['type' => 'color', 'label' => __('Color primario', FLAVOR_PLATFORM_TEXT_DOMAIN), 'default' => '#3b82f6'],
                    'columnas' => ['type' => 'select', 'label' => __('Columnas', FLAVOR_PLATFORM_TEXT_DOMAIN), 'options' => [2, 3, 4], 'default' => 3],
                    'limite' => ['type' => 'number', 'label' => __('Límite', FLAVOR_PLATFORM_TEXT_DOMAIN), 'default' => 6],
                    'mostrar_filtros' => ['type' => 'toggle', 'label' => __('Mostrar filtros', FLAVOR_PLATFORM_TEXT_DOMAIN), 'default' => true],
                    'mostrar_buscador' => ['type' => 'toggle', 'label' => __('Mostrar buscador', FLAVOR_PLATFORM_TEXT_DOMAIN), 'default' => false],
                    'items' => ['type' => 'repeater', 'label' => __('Items', FLAVOR_PLATFORM_TEXT_DOMAIN), 'max_items' => 20, 'fields' => [
                        'titulo' => ['type' => 'text', 'label' => __('Título', FLAVOR_PLATFORM_TEXT_DOMAIN), 'default' => ''],
                        'descripcion' => ['type' => 'textarea', 'label' => __('Descripción', FLAVOR_PLATFORM_TEXT_DOMAIN), 'default' => ''],
                        'imagen' => ['type' => 'image', 'label' => __('Imagen', FLAVOR_PLATFORM_TEXT_DOMAIN), 'default' => ''],
                        'url' => ['type' => 'url', 'label' => __('URL', FLAVOR_PLATFORM_TEXT_DOMAIN), 'default' => '#'],
                    ]],
                ],
                'template' => 'unified/listing',
            ],

            'unified_stats' => [
                'label' => __('Estadísticas', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'description' => __('Contadores y estadísticas', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'category' => 'content',
                'icon' => 'dashicons-chart-bar',
                'variants' => [
                    'counters_horizontal' => ['label' => __('Horizontal', FLAVOR_PLATFORM_TEXT_DOMAIN), 'description' => __('Contadores en fila', FLAVOR_PLATFORM_TEXT_DOMAIN)],
                    'counters_grid' => ['label' => __('Grid', FLAVOR_PLATFORM_TEXT_DOMAIN), 'description' => __('Contadores en cuadrícula', FLAVOR_PLATFORM_TEXT_DOMAIN)],
                    'con_iconos' => ['label' => __('Con Iconos', FLAVOR_PLATFORM_TEXT_DOMAIN), 'description' => __('Contadores con iconos destacados', FLAVOR_PLATFORM_TEXT_DOMAIN)],
                ],
                'presets' => [],
                'fields' => [
                    'variante' => ['type' => 'variant_selector', 'label' => __('Variante', FLAVOR_PLATFORM_TEXT_DOMAIN), 'default' => 'counters_horizontal'],
                    'titulo' => ['type' => 'text', 'label' => __('Título', FLAVOR_PLATFORM_TEXT_DOMAIN), 'default' => ''],
                    'subtitulo' => ['type' => 'textarea', 'label' => __('Subtítulo', FLAVOR_PLATFORM_TEXT_DOMAIN), 'default' => ''],
                    'color_primario' => ['type' => 'color', 'label' => __('Color primario', FLAVOR_PLATFORM_TEXT_DOMAIN), 'default' => '#3b82f6'],
                    'color_fondo' => ['type' => 'color', 'label' => __('Color de fondo', FLAVOR_PLATFORM_TEXT_DOMAIN), 'default' => ''],
                    'items' => ['type' => 'repeater', 'label' => __('Estadísticas', FLAVOR_PLATFORM_TEXT_DOMAIN), 'max_items' => 6, 'fields' => [
                        'valor' => ['type' => 'text', 'label' => __('Valor', FLAVOR_PLATFORM_TEXT_DOMAIN), 'default' => '0'],
                        'etiqueta' => ['type' => 'text', 'label' => __('Etiqueta', FLAVOR_PLATFORM_TEXT_DOMAIN), 'default' => ''],
                        'icono' => ['type' => 'text', 'label' => __('Icono', FLAVOR_PLATFORM_TEXT_DOMAIN), 'default' => ''],
                    ]],
                ],
                'template' => 'unified/stats',
            ],

            'unified_proceso' => [
                'label' => __('Proceso / Cómo Funciona', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'description' => __('Pasos de un proceso o guía', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'category' => 'content',
                'icon' => 'dashicons-editor-ol',
                'variants' => [
                    'pasos_horizontal' => ['label' => __('Pasos Horizontal', FLAVOR_PLATFORM_TEXT_DOMAIN), 'description' => __('Pasos numerados en fila', FLAVOR_PLATFORM_TEXT_DOMAIN)],
                    'pasos_vertical' => ['label' => __('Pasos Vertical', FLAVOR_PLATFORM_TEXT_DOMAIN), 'description' => __('Pasos en columna vertical', FLAVOR_PLATFORM_TEXT_DOMAIN)],
                    'timeline' => ['label' => __('Timeline', FLAVOR_PLATFORM_TEXT_DOMAIN), 'description' => __('Línea temporal alternada', FLAVOR_PLATFORM_TEXT_DOMAIN)],
                ],
                'presets' => [],
                'fields' => [
                    'variante' => ['type' => 'variant_selector', 'label' => __('Variante', FLAVOR_PLATFORM_TEXT_DOMAIN), 'default' => 'pasos_horizontal'],
                    'titulo' => ['type' => 'text', 'label' => __('Título', FLAVOR_PLATFORM_TEXT_DOMAIN), 'default' => __('Cómo Funciona', FLAVOR_PLATFORM_TEXT_DOMAIN)],
                    'subtitulo' => ['type' => 'textarea', 'label' => __('Subtítulo', FLAVOR_PLATFORM_TEXT_DOMAIN), 'default' => ''],
                    'color_primario' => ['type' => 'color', 'label' => __('Color primario', FLAVOR_PLATFORM_TEXT_DOMAIN), 'default' => '#3b82f6'],
                    'items' => ['type' => 'repeater', 'label' => __('Pasos', FLAVOR_PLATFORM_TEXT_DOMAIN), 'max_items' => 8, 'fields' => [
                        'titulo' => ['type' => 'text', 'label' => __('Título', FLAVOR_PLATFORM_TEXT_DOMAIN), 'default' => ''],
                        'descripcion' => ['type' => 'textarea', 'label' => __('Descripción', FLAVOR_PLATFORM_TEXT_DOMAIN), 'default' => ''],
                        'icono' => ['type' => 'text', 'label' => __('Icono', FLAVOR_PLATFORM_TEXT_DOMAIN), 'default' => ''],
                    ]],
                ],
                'template' => 'unified/proceso',
            ],

            'unified_pricing' => [
                'label' => __('Precios', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'description' => __('Tablas de precios y planes', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'category' => 'content',
                'icon' => 'dashicons-money-alt',
                'variants' => [
                    'columnas' => ['label' => __('Columnas', FLAVOR_PLATFORM_TEXT_DOMAIN), 'description' => __('Planes lado a lado', FLAVOR_PLATFORM_TEXT_DOMAIN)],
                    'toggle_plan' => ['label' => __('Toggle Mensual/Anual', FLAVOR_PLATFORM_TEXT_DOMAIN), 'description' => __('Con alternador de período', FLAVOR_PLATFORM_TEXT_DOMAIN)],
                    'comparativa' => ['label' => __('Comparativa', FLAVOR_PLATFORM_TEXT_DOMAIN), 'description' => __('Tabla comparativa de features', FLAVOR_PLATFORM_TEXT_DOMAIN)],
                ],
                'presets' => [],
                'fields' => [
                    'variante' => ['type' => 'variant_selector', 'label' => __('Variante', FLAVOR_PLATFORM_TEXT_DOMAIN), 'default' => 'columnas'],
                    'titulo' => ['type' => 'text', 'label' => __('Título', FLAVOR_PLATFORM_TEXT_DOMAIN), 'default' => __('Planes y Precios', FLAVOR_PLATFORM_TEXT_DOMAIN)],
                    'subtitulo' => ['type' => 'textarea', 'label' => __('Subtítulo', FLAVOR_PLATFORM_TEXT_DOMAIN), 'default' => ''],
                    'color_primario' => ['type' => 'color', 'label' => __('Color primario', FLAVOR_PLATFORM_TEXT_DOMAIN), 'default' => '#3b82f6'],
                    'items' => ['type' => 'repeater', 'label' => __('Planes', FLAVOR_PLATFORM_TEXT_DOMAIN), 'max_items' => 5, 'fields' => [
                        'nombre' => ['type' => 'text', 'label' => __('Nombre', FLAVOR_PLATFORM_TEXT_DOMAIN), 'default' => ''],
                        'precio' => ['type' => 'text', 'label' => __('Precio', FLAVOR_PLATFORM_TEXT_DOMAIN), 'default' => '0'],
                        'periodo' => ['type' => 'text', 'label' => __('Período', FLAVOR_PLATFORM_TEXT_DOMAIN), 'default' => '/mes'],
                        'caracteristicas' => ['type' => 'textarea', 'label' => __('Características (una por línea)', FLAVOR_PLATFORM_TEXT_DOMAIN), 'default' => ''],
                        'destacado' => ['type' => 'toggle', 'label' => __('Destacado', FLAVOR_PLATFORM_TEXT_DOMAIN), 'default' => false],
                        'texto_boton' => ['type' => 'text', 'label' => __('Texto botón', FLAVOR_PLATFORM_TEXT_DOMAIN), 'default' => __('Elegir Plan', FLAVOR_PLATFORM_TEXT_DOMAIN)],
                        'url_boton' => ['type' => 'url', 'label' => __('URL botón', FLAVOR_PLATFORM_TEXT_DOMAIN), 'default' => '#'],
                    ]],
                ],
                'template' => 'unified/pricing',
            ],

            'unified_equipo' => [
                'label' => __('Equipo', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'description' => __('Miembros del equipo', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'category' => 'content',
                'icon' => 'dashicons-groups',
                'variants' => [
                    'grid_tarjetas' => ['label' => __('Grid Tarjetas', FLAVOR_PLATFORM_TEXT_DOMAIN), 'description' => __('Grid de tarjetas de miembros', FLAVOR_PLATFORM_TEXT_DOMAIN)],
                    'carrusel' => ['label' => __('Carrusel', FLAVOR_PLATFORM_TEXT_DOMAIN), 'description' => __('Carrusel horizontal', FLAVOR_PLATFORM_TEXT_DOMAIN)],
                    'lista' => ['label' => __('Lista', FLAVOR_PLATFORM_TEXT_DOMAIN), 'description' => __('Lista con fotos grandes', FLAVOR_PLATFORM_TEXT_DOMAIN)],
                ],
                'presets' => [],
                'fields' => [
                    'variante' => ['type' => 'variant_selector', 'label' => __('Variante', FLAVOR_PLATFORM_TEXT_DOMAIN), 'default' => 'grid_tarjetas'],
                    'titulo' => ['type' => 'text', 'label' => __('Título', FLAVOR_PLATFORM_TEXT_DOMAIN), 'default' => __('Nuestro Equipo', FLAVOR_PLATFORM_TEXT_DOMAIN)],
                    'subtitulo' => ['type' => 'textarea', 'label' => __('Subtítulo', FLAVOR_PLATFORM_TEXT_DOMAIN), 'default' => ''],
                    'color_primario' => ['type' => 'color', 'label' => __('Color primario', FLAVOR_PLATFORM_TEXT_DOMAIN), 'default' => '#3b82f6'],
                    'items' => ['type' => 'repeater', 'label' => __('Miembros', FLAVOR_PLATFORM_TEXT_DOMAIN), 'max_items' => 12, 'fields' => [
                        'nombre' => ['type' => 'text', 'label' => __('Nombre', FLAVOR_PLATFORM_TEXT_DOMAIN), 'default' => ''],
                        'cargo' => ['type' => 'text', 'label' => __('Cargo', FLAVOR_PLATFORM_TEXT_DOMAIN), 'default' => ''],
                        'foto' => ['type' => 'image', 'label' => __('Foto', FLAVOR_PLATFORM_TEXT_DOMAIN), 'default' => ''],
                        'bio' => ['type' => 'textarea', 'label' => __('Bio', FLAVOR_PLATFORM_TEXT_DOMAIN), 'default' => ''],
                    ]],
                ],
                'template' => 'unified/equipo',
            ],

            'unified_mapa' => [
                'label' => __('Mapa', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'description' => __('Mapa con ubicaciones', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'category' => 'content',
                'icon' => 'dashicons-location-alt',
                'variants' => [
                    'embed_simple' => ['label' => __('Embed Simple', FLAVOR_PLATFORM_TEXT_DOMAIN), 'description' => __('Mapa embebido básico', FLAVOR_PLATFORM_TEXT_DOMAIN)],
                    'con_marcadores' => ['label' => __('Con Marcadores', FLAVOR_PLATFORM_TEXT_DOMAIN), 'description' => __('Mapa con lista de ubicaciones', FLAVOR_PLATFORM_TEXT_DOMAIN)],
                    'con_sidebar' => ['label' => __('Con Sidebar', FLAVOR_PLATFORM_TEXT_DOMAIN), 'description' => __('Mapa con panel lateral', FLAVOR_PLATFORM_TEXT_DOMAIN)],
                ],
                'presets' => [],
                'fields' => [
                    'variante' => ['type' => 'variant_selector', 'label' => __('Variante', FLAVOR_PLATFORM_TEXT_DOMAIN), 'default' => 'embed_simple'],
                    'titulo' => ['type' => 'text', 'label' => __('Título', FLAVOR_PLATFORM_TEXT_DOMAIN), 'default' => ''],
                    'subtitulo' => ['type' => 'textarea', 'label' => __('Subtítulo', FLAVOR_PLATFORM_TEXT_DOMAIN), 'default' => ''],
                    'color_primario' => ['type' => 'color', 'label' => __('Color primario', FLAVOR_PLATFORM_TEXT_DOMAIN), 'default' => '#3b82f6'],
                    'direccion' => ['type' => 'text', 'label' => __('Dirección', FLAVOR_PLATFORM_TEXT_DOMAIN), 'default' => ''],
                    'latitud' => ['type' => 'text', 'label' => __('Latitud', FLAVOR_PLATFORM_TEXT_DOMAIN), 'default' => ''],
                    'longitud' => ['type' => 'text', 'label' => __('Longitud', FLAVOR_PLATFORM_TEXT_DOMAIN), 'default' => ''],
                    'zoom' => ['type' => 'number', 'label' => __('Zoom', FLAVOR_PLATFORM_TEXT_DOMAIN), 'default' => 14],
                    'altura' => ['type' => 'text', 'label' => __('Altura', FLAVOR_PLATFORM_TEXT_DOMAIN), 'default' => '400px'],
                ],
                'template' => 'unified/mapa',
            ],

            'unified_testimonios' => [
                'label' => __('Testimonios', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'description' => __('Testimonios y opiniones', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'category' => 'testimonials',
                'icon' => 'dashicons-format-quote',
                'variants' => [
                    'carrusel' => ['label' => __('Carrusel', FLAVOR_PLATFORM_TEXT_DOMAIN), 'description' => __('Carrusel de testimonios', FLAVOR_PLATFORM_TEXT_DOMAIN)],
                    'grid' => ['label' => __('Grid', FLAVOR_PLATFORM_TEXT_DOMAIN), 'description' => __('Grid de tarjetas', FLAVOR_PLATFORM_TEXT_DOMAIN)],
                    'quotes' => ['label' => __('Citas', FLAVOR_PLATFORM_TEXT_DOMAIN), 'description' => __('Citas grandes destacadas', FLAVOR_PLATFORM_TEXT_DOMAIN)],
                ],
                'presets' => [],
                'fields' => [
                    'variante' => ['type' => 'variant_selector', 'label' => __('Variante', FLAVOR_PLATFORM_TEXT_DOMAIN), 'default' => 'carrusel'],
                    'titulo' => ['type' => 'text', 'label' => __('Título', FLAVOR_PLATFORM_TEXT_DOMAIN), 'default' => __('Testimonios', FLAVOR_PLATFORM_TEXT_DOMAIN)],
                    'subtitulo' => ['type' => 'textarea', 'label' => __('Subtítulo', FLAVOR_PLATFORM_TEXT_DOMAIN), 'default' => ''],
                    'color_primario' => ['type' => 'color', 'label' => __('Color primario', FLAVOR_PLATFORM_TEXT_DOMAIN), 'default' => '#3b82f6'],
                    'items' => ['type' => 'repeater', 'label' => __('Testimonios', FLAVOR_PLATFORM_TEXT_DOMAIN), 'max_items' => 10, 'fields' => [
                        'nombre' => ['type' => 'text', 'label' => __('Nombre', FLAVOR_PLATFORM_TEXT_DOMAIN), 'default' => ''],
                        'cargo' => ['type' => 'text', 'label' => __('Cargo', FLAVOR_PLATFORM_TEXT_DOMAIN), 'default' => ''],
                        'texto' => ['type' => 'textarea', 'label' => __('Texto', FLAVOR_PLATFORM_TEXT_DOMAIN), 'default' => ''],
                        'foto' => ['type' => 'image', 'label' => __('Foto', FLAVOR_PLATFORM_TEXT_DOMAIN), 'default' => ''],
                    ]],
                ],
                'template' => 'unified/testimonios',
            ],

            'unified_faq' => [
                'label' => __('Preguntas Frecuentes', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'description' => __('Sección de FAQ', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'category' => 'content',
                'icon' => 'dashicons-editor-help',
                'variants' => [
                    'acordeon' => ['label' => __('Acordeón', FLAVOR_PLATFORM_TEXT_DOMAIN), 'description' => __('FAQ colapsable', FLAVOR_PLATFORM_TEXT_DOMAIN)],
                    'dos_columnas' => ['label' => __('Dos Columnas', FLAVOR_PLATFORM_TEXT_DOMAIN), 'description' => __('FAQ en dos columnas', FLAVOR_PLATFORM_TEXT_DOMAIN)],
                    'con_buscador' => ['label' => __('Con Buscador', FLAVOR_PLATFORM_TEXT_DOMAIN), 'description' => __('FAQ con barra de búsqueda', FLAVOR_PLATFORM_TEXT_DOMAIN)],
                ],
                'presets' => [],
                'fields' => [
                    'variante' => ['type' => 'variant_selector', 'label' => __('Variante', FLAVOR_PLATFORM_TEXT_DOMAIN), 'default' => 'acordeon'],
                    'titulo' => ['type' => 'text', 'label' => __('Título', FLAVOR_PLATFORM_TEXT_DOMAIN), 'default' => __('Preguntas Frecuentes', FLAVOR_PLATFORM_TEXT_DOMAIN)],
                    'subtitulo' => ['type' => 'textarea', 'label' => __('Subtítulo', FLAVOR_PLATFORM_TEXT_DOMAIN), 'default' => ''],
                    'color_primario' => ['type' => 'color', 'label' => __('Color primario', FLAVOR_PLATFORM_TEXT_DOMAIN), 'default' => '#3b82f6'],
                    'mostrar_buscador' => ['type' => 'toggle', 'label' => __('Mostrar buscador', FLAVOR_PLATFORM_TEXT_DOMAIN), 'default' => false, 'show_when' => ['variante' => ['con_buscador']]],
                    'items' => ['type' => 'repeater', 'label' => __('Preguntas', FLAVOR_PLATFORM_TEXT_DOMAIN), 'max_items' => 20, 'fields' => [
                        'pregunta' => ['type' => 'text', 'label' => __('Pregunta', FLAVOR_PLATFORM_TEXT_DOMAIN), 'default' => ''],
                        'respuesta' => ['type' => 'textarea', 'label' => __('Respuesta', FLAVOR_PLATFORM_TEXT_DOMAIN), 'default' => ''],
                    ]],
                ],
                'template' => 'unified/faq',
            ],

            'unified_contacto' => [
                'label' => __('Contacto', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'description' => __('Formulario de contacto', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'category' => 'forms',
                'icon' => 'dashicons-email-alt',
                'variants' => [
                    'formulario_simple' => ['label' => __('Simple', FLAVOR_PLATFORM_TEXT_DOMAIN), 'description' => __('Formulario de contacto simple', FLAVOR_PLATFORM_TEXT_DOMAIN)],
                    'split_con_mapa' => ['label' => __('Con Mapa', FLAVOR_PLATFORM_TEXT_DOMAIN), 'description' => __('Formulario con mapa lateral', FLAVOR_PLATFORM_TEXT_DOMAIN)],
                    'con_info' => ['label' => __('Con Info', FLAVOR_PLATFORM_TEXT_DOMAIN), 'description' => __('Formulario con info de contacto', FLAVOR_PLATFORM_TEXT_DOMAIN)],
                ],
                'presets' => [],
                'fields' => [
                    'variante' => ['type' => 'variant_selector', 'label' => __('Variante', FLAVOR_PLATFORM_TEXT_DOMAIN), 'default' => 'formulario_simple'],
                    'titulo' => ['type' => 'text', 'label' => __('Título', FLAVOR_PLATFORM_TEXT_DOMAIN), 'default' => __('Contacto', FLAVOR_PLATFORM_TEXT_DOMAIN)],
                    'subtitulo' => ['type' => 'textarea', 'label' => __('Subtítulo', FLAVOR_PLATFORM_TEXT_DOMAIN), 'default' => ''],
                    'color_primario' => ['type' => 'color', 'label' => __('Color primario', FLAVOR_PLATFORM_TEXT_DOMAIN), 'default' => '#3b82f6'],
                    'email_destino' => ['type' => 'text', 'label' => __('Email destino', FLAVOR_PLATFORM_TEXT_DOMAIN), 'default' => ''],
                    'mostrar_telefono' => ['type' => 'toggle', 'label' => __('Mostrar teléfono', FLAVOR_PLATFORM_TEXT_DOMAIN), 'default' => false, 'show_when' => ['variante' => ['con_info']]],
                    'telefono' => ['type' => 'text', 'label' => __('Teléfono', FLAVOR_PLATFORM_TEXT_DOMAIN), 'default' => '', 'show_when' => ['mostrar_telefono' => true]],
                    'mostrar_direccion' => ['type' => 'toggle', 'label' => __('Mostrar dirección', FLAVOR_PLATFORM_TEXT_DOMAIN), 'default' => false, 'show_when' => ['variante' => ['con_info', 'split_con_mapa']]],
                    'direccion' => ['type' => 'text', 'label' => __('Dirección', FLAVOR_PLATFORM_TEXT_DOMAIN), 'default' => '', 'show_when' => ['mostrar_direccion' => true]],
                ],
                'template' => 'unified/contacto',
            ],

            'unified_galeria' => [
                'label' => __('Galería', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'description' => __('Galería de imágenes o portfolio', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'category' => 'content',
                'icon' => 'dashicons-format-gallery',
                'variants' => [
                    'grid_masonry' => ['label' => __('Masonry', FLAVOR_PLATFORM_TEXT_DOMAIN), 'description' => __('Grid masonry asimétrico', FLAVOR_PLATFORM_TEXT_DOMAIN)],
                    'carrusel' => ['label' => __('Carrusel', FLAVOR_PLATFORM_TEXT_DOMAIN), 'description' => __('Carrusel horizontal', FLAVOR_PLATFORM_TEXT_DOMAIN)],
                    'lightbox' => ['label' => __('Lightbox', FLAVOR_PLATFORM_TEXT_DOMAIN), 'description' => __('Grid con lightbox al hacer click', FLAVOR_PLATFORM_TEXT_DOMAIN)],
                ],
                'presets' => [],
                'fields' => [
                    'variante' => ['type' => 'variant_selector', 'label' => __('Variante', FLAVOR_PLATFORM_TEXT_DOMAIN), 'default' => 'grid_masonry'],
                    'titulo' => ['type' => 'text', 'label' => __('Título', FLAVOR_PLATFORM_TEXT_DOMAIN), 'default' => ''],
                    'subtitulo' => ['type' => 'textarea', 'label' => __('Subtítulo', FLAVOR_PLATFORM_TEXT_DOMAIN), 'default' => ''],
                    'color_primario' => ['type' => 'color', 'label' => __('Color primario', FLAVOR_PLATFORM_TEXT_DOMAIN), 'default' => '#3b82f6'],
                    'columnas' => ['type' => 'select', 'label' => __('Columnas', FLAVOR_PLATFORM_TEXT_DOMAIN), 'options' => [2, 3, 4], 'default' => 3],
                    'items' => ['type' => 'repeater', 'label' => __('Imágenes', FLAVOR_PLATFORM_TEXT_DOMAIN), 'max_items' => 20, 'fields' => [
                        'imagen' => ['type' => 'image', 'label' => __('Imagen', FLAVOR_PLATFORM_TEXT_DOMAIN), 'default' => ''],
                        'titulo' => ['type' => 'text', 'label' => __('Título', FLAVOR_PLATFORM_TEXT_DOMAIN), 'default' => ''],
                        'descripcion' => ['type' => 'textarea', 'label' => __('Descripción', FLAVOR_PLATFORM_TEXT_DOMAIN), 'default' => ''],
                    ]],
                ],
                'template' => 'unified/galeria',
            ],

            'unified_calendario' => [
                'label' => __('Calendario', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'description' => __('Calendario de eventos', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'category' => 'content',
                'icon' => 'dashicons-calendar-alt',
                'variants' => [
                    'mensual' => ['label' => __('Mensual', FLAVOR_PLATFORM_TEXT_DOMAIN), 'description' => __('Vista mensual cuadrícula', FLAVOR_PLATFORM_TEXT_DOMAIN)],
                    'lista_eventos' => ['label' => __('Lista', FLAVOR_PLATFORM_TEXT_DOMAIN), 'description' => __('Lista cronológica de eventos', FLAVOR_PLATFORM_TEXT_DOMAIN)],
                    'agenda' => ['label' => __('Agenda', FLAVOR_PLATFORM_TEXT_DOMAIN), 'description' => __('Vista de agenda diaria', FLAVOR_PLATFORM_TEXT_DOMAIN)],
                ],
                'presets' => [],
                'fields' => [
                    'variante' => ['type' => 'variant_selector', 'label' => __('Variante', FLAVOR_PLATFORM_TEXT_DOMAIN), 'default' => 'mensual'],
                    'titulo' => ['type' => 'text', 'label' => __('Título', FLAVOR_PLATFORM_TEXT_DOMAIN), 'default' => __('Calendario', FLAVOR_PLATFORM_TEXT_DOMAIN)],
                    'subtitulo' => ['type' => 'textarea', 'label' => __('Subtítulo', FLAVOR_PLATFORM_TEXT_DOMAIN), 'default' => ''],
                    'color_primario' => ['type' => 'color', 'label' => __('Color primario', FLAVOR_PLATFORM_TEXT_DOMAIN), 'default' => '#3b82f6'],
                    'eventos' => ['type' => 'repeater', 'label' => __('Eventos', FLAVOR_PLATFORM_TEXT_DOMAIN), 'max_items' => 20, 'fields' => [
                        'titulo' => ['type' => 'text', 'label' => __('Título', FLAVOR_PLATFORM_TEXT_DOMAIN), 'default' => ''],
                        'fecha' => ['type' => 'text', 'label' => __('Fecha', FLAVOR_PLATFORM_TEXT_DOMAIN), 'default' => ''],
                        'descripcion' => ['type' => 'textarea', 'label' => __('Descripción', FLAVOR_PLATFORM_TEXT_DOMAIN), 'default' => ''],
                        'lugar' => ['type' => 'text', 'label' => __('Lugar', FLAVOR_PLATFORM_TEXT_DOMAIN), 'default' => ''],
                    ]],
                ],
                'template' => 'unified/calendario',
            ],

            'unified_newsletter' => [
                'label' => __('Newsletter', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'description' => __('Formulario de suscripción', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'category' => 'forms',
                'icon' => 'dashicons-email',
                'variants' => [
                    'inline' => ['label' => __('Inline', FLAVOR_PLATFORM_TEXT_DOMAIN), 'description' => __('Formulario horizontal inline', FLAVOR_PLATFORM_TEXT_DOMAIN)],
                    'card_centrada' => ['label' => __('Card Centrada', FLAVOR_PLATFORM_TEXT_DOMAIN), 'description' => __('Tarjeta centrada con formulario', FLAVOR_PLATFORM_TEXT_DOMAIN)],
                    'con_beneficios' => ['label' => __('Con Beneficios', FLAVOR_PLATFORM_TEXT_DOMAIN), 'description' => __('Formulario con lista de beneficios', FLAVOR_PLATFORM_TEXT_DOMAIN)],
                ],
                'presets' => [],
                'fields' => [
                    'variante' => ['type' => 'variant_selector', 'label' => __('Variante', FLAVOR_PLATFORM_TEXT_DOMAIN), 'default' => 'inline'],
                    'titulo' => ['type' => 'text', 'label' => __('Título', FLAVOR_PLATFORM_TEXT_DOMAIN), 'default' => __('Suscríbete', FLAVOR_PLATFORM_TEXT_DOMAIN)],
                    'subtitulo' => ['type' => 'textarea', 'label' => __('Subtítulo', FLAVOR_PLATFORM_TEXT_DOMAIN), 'default' => ''],
                    'color_primario' => ['type' => 'color', 'label' => __('Color primario', FLAVOR_PLATFORM_TEXT_DOMAIN), 'default' => '#3b82f6'],
                    'texto_boton' => ['type' => 'text', 'label' => __('Texto del botón', FLAVOR_PLATFORM_TEXT_DOMAIN), 'default' => __('Suscribirse', FLAVOR_PLATFORM_TEXT_DOMAIN)],
                    'placeholder' => ['type' => 'text', 'label' => __('Placeholder', FLAVOR_PLATFORM_TEXT_DOMAIN), 'default' => __('Tu email', FLAVOR_PLATFORM_TEXT_DOMAIN)],
                    'beneficios' => ['type' => 'repeater', 'label' => __('Beneficios', FLAVOR_PLATFORM_TEXT_DOMAIN), 'max_items' => 5, 'show_when' => ['variante' => ['con_beneficios']], 'fields' => [
                        'texto' => ['type' => 'text', 'label' => __('Beneficio', FLAVOR_PLATFORM_TEXT_DOMAIN), 'default' => ''],
                    ]],
                ],
                'template' => 'unified/newsletter',
            ],

            'unified_contenido' => [
                'label' => __('Contenido', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'description' => __('Bloque de contenido genérico', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'category' => 'content',
                'icon' => 'dashicons-text-page',
                'variants' => [
                    'texto_simple' => ['label' => __('Texto Simple', FLAVOR_PLATFORM_TEXT_DOMAIN), 'description' => __('Bloque de texto con título', FLAVOR_PLATFORM_TEXT_DOMAIN)],
                    'texto_con_imagen' => ['label' => __('Con Imagen', FLAVOR_PLATFORM_TEXT_DOMAIN), 'description' => __('Texto con imagen lateral', FLAVOR_PLATFORM_TEXT_DOMAIN)],
                    'dos_columnas' => ['label' => __('Dos Columnas', FLAVOR_PLATFORM_TEXT_DOMAIN), 'description' => __('Texto en dos columnas', FLAVOR_PLATFORM_TEXT_DOMAIN)],
                    'video' => ['label' => __('Video', FLAVOR_PLATFORM_TEXT_DOMAIN), 'description' => __('Video embebido con texto', FLAVOR_PLATFORM_TEXT_DOMAIN)],
                ],
                'presets' => [],
                'fields' => [
                    'variante' => ['type' => 'variant_selector', 'label' => __('Variante', FLAVOR_PLATFORM_TEXT_DOMAIN), 'default' => 'texto_simple'],
                    'titulo' => ['type' => 'text', 'label' => __('Título', FLAVOR_PLATFORM_TEXT_DOMAIN), 'default' => ''],
                    'subtitulo' => ['type' => 'textarea', 'label' => __('Subtítulo', FLAVOR_PLATFORM_TEXT_DOMAIN), 'default' => ''],
                    'contenido' => ['type' => 'textarea', 'label' => __('Contenido', FLAVOR_PLATFORM_TEXT_DOMAIN), 'default' => ''],
                    'color_primario' => ['type' => 'color', 'label' => __('Color primario', FLAVOR_PLATFORM_TEXT_DOMAIN), 'default' => '#3b82f6'],
                    'imagen' => ['type' => 'image', 'label' => __('Imagen', FLAVOR_PLATFORM_TEXT_DOMAIN), 'default' => '', 'show_when' => ['variante' => ['texto_con_imagen']]],
                    'posicion_imagen' => ['type' => 'select', 'label' => __('Posición imagen', FLAVOR_PLATFORM_TEXT_DOMAIN), 'options' => ['izquierda', 'derecha'], 'default' => 'derecha', 'show_when' => ['variante' => ['texto_con_imagen']]],
                    'url_video' => ['type' => 'url', 'label' => __('URL del video', FLAVOR_PLATFORM_TEXT_DOMAIN), 'default' => '', 'show_when' => ['variante' => ['video']]],
                ],
                'template' => 'unified/contenido',
            ],

            'unified_navegacion' => [
                'label' => __('Navegación', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'description' => __('Navegación por categorías o filtros', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'category' => 'navigation',
                'icon' => 'dashicons-menu-alt3',
                'variants' => [
                    'tabs_horizontal' => ['label' => __('Tabs', FLAVOR_PLATFORM_TEXT_DOMAIN), 'description' => __('Pestañas horizontales', FLAVOR_PLATFORM_TEXT_DOMAIN)],
                    'pills' => ['label' => __('Pills', FLAVOR_PLATFORM_TEXT_DOMAIN), 'description' => __('Botones tipo pill', FLAVOR_PLATFORM_TEXT_DOMAIN)],
                    'sidebar_filtros' => ['label' => __('Sidebar', FLAVOR_PLATFORM_TEXT_DOMAIN), 'description' => __('Filtros en sidebar vertical', FLAVOR_PLATFORM_TEXT_DOMAIN)],
                ],
                'presets' => [],
                'fields' => [
                    'variante' => ['type' => 'variant_selector', 'label' => __('Variante', FLAVOR_PLATFORM_TEXT_DOMAIN), 'default' => 'tabs_horizontal'],
                    'titulo' => ['type' => 'text', 'label' => __('Título', FLAVOR_PLATFORM_TEXT_DOMAIN), 'default' => ''],
                    'color_primario' => ['type' => 'color', 'label' => __('Color primario', FLAVOR_PLATFORM_TEXT_DOMAIN), 'default' => '#3b82f6'],
                    'items' => ['type' => 'repeater', 'label' => __('Elementos', FLAVOR_PLATFORM_TEXT_DOMAIN), 'max_items' => 10, 'fields' => [
                        'label' => ['type' => 'text', 'label' => __('Texto', FLAVOR_PLATFORM_TEXT_DOMAIN), 'default' => ''],
                        'url' => ['type' => 'url', 'label' => __('URL', FLAVOR_PLATFORM_TEXT_DOMAIN), 'default' => '#'],
                        'icono' => ['type' => 'text', 'label' => __('Icono', FLAVOR_PLATFORM_TEXT_DOMAIN), 'default' => ''],
                    ]],
                ],
                'template' => 'unified/navegacion',
            ],

            'unified_form' => [
                'label' => __('Formulario', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'description' => __('Formulario genérico personalizable', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'category' => 'forms',
                'icon' => 'dashicons-feedback',
                'variants' => [
                    'simple' => ['label' => __('Simple', FLAVOR_PLATFORM_TEXT_DOMAIN), 'description' => __('Formulario de un paso', FLAVOR_PLATFORM_TEXT_DOMAIN)],
                    'multi_paso' => ['label' => __('Multi-Paso', FLAVOR_PLATFORM_TEXT_DOMAIN), 'description' => __('Formulario wizard', FLAVOR_PLATFORM_TEXT_DOMAIN)],
                ],
                'presets' => [],
                'fields' => [
                    'variante' => ['type' => 'variant_selector', 'label' => __('Variante', FLAVOR_PLATFORM_TEXT_DOMAIN), 'default' => 'simple'],
                    'titulo' => ['type' => 'text', 'label' => __('Título', FLAVOR_PLATFORM_TEXT_DOMAIN), 'default' => ''],
                    'subtitulo' => ['type' => 'textarea', 'label' => __('Subtítulo', FLAVOR_PLATFORM_TEXT_DOMAIN), 'default' => ''],
                    'color_primario' => ['type' => 'color', 'label' => __('Color primario', FLAVOR_PLATFORM_TEXT_DOMAIN), 'default' => '#3b82f6'],
                    'texto_boton' => ['type' => 'text', 'label' => __('Texto del botón', FLAVOR_PLATFORM_TEXT_DOMAIN), 'default' => __('Enviar', FLAVOR_PLATFORM_TEXT_DOMAIN)],
                    'campos' => ['type' => 'repeater', 'label' => __('Campos', FLAVOR_PLATFORM_TEXT_DOMAIN), 'max_items' => 15, 'fields' => [
                        'nombre' => ['type' => 'text', 'label' => __('Nombre del campo', FLAVOR_PLATFORM_TEXT_DOMAIN), 'default' => ''],
                        'tipo' => ['type' => 'select', 'label' => __('Tipo', FLAVOR_PLATFORM_TEXT_DOMAIN), 'options' => ['text', 'email', 'textarea', 'select', 'number', 'tel'], 'default' => 'text'],
                        'label' => ['type' => 'text', 'label' => __('Etiqueta', FLAVOR_PLATFORM_TEXT_DOMAIN), 'default' => ''],
                        'requerido' => ['type' => 'toggle', 'label' => __('Requerido', FLAVOR_PLATFORM_TEXT_DOMAIN), 'default' => false],
                    ]],
                ],
                'template' => 'unified/form',
            ],

            'unified_footer' => [
                'label' => __('Footer', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'description' => __('Pie de página', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'category' => 'footer',
                'icon' => 'dashicons-minus',
                'variants' => [
                    'simple' => ['label' => __('Simple', FLAVOR_PLATFORM_TEXT_DOMAIN), 'description' => __('Footer simple con copyright', FLAVOR_PLATFORM_TEXT_DOMAIN)],
                    'multi_columna' => ['label' => __('Multi-Columna', FLAVOR_PLATFORM_TEXT_DOMAIN), 'description' => __('Footer con columnas de enlaces', FLAVOR_PLATFORM_TEXT_DOMAIN)],
                ],
                'presets' => [],
                'fields' => [
                    'variante' => ['type' => 'variant_selector', 'label' => __('Variante', FLAVOR_PLATFORM_TEXT_DOMAIN), 'default' => 'simple'],
                    'texto_copyright' => ['type' => 'text', 'label' => __('Copyright', FLAVOR_PLATFORM_TEXT_DOMAIN), 'default' => ''],
                    'color_fondo' => ['type' => 'color', 'label' => __('Color de fondo', FLAVOR_PLATFORM_TEXT_DOMAIN), 'default' => '#1f2937'],
                    'color_texto' => ['type' => 'color', 'label' => __('Color de texto', FLAVOR_PLATFORM_TEXT_DOMAIN), 'default' => '#ffffff'],
                    'logo' => ['type' => 'image', 'label' => __('Logo', FLAVOR_PLATFORM_TEXT_DOMAIN), 'default' => ''],
                    'redes_sociales' => ['type' => 'repeater', 'label' => __('Redes Sociales', FLAVOR_PLATFORM_TEXT_DOMAIN), 'max_items' => 6, 'fields' => [
                        'nombre' => ['type' => 'text', 'label' => __('Red social', FLAVOR_PLATFORM_TEXT_DOMAIN), 'default' => ''],
                        'url' => ['type' => 'url', 'label' => __('URL', FLAVOR_PLATFORM_TEXT_DOMAIN), 'default' => '#'],
                    ]],
                    'columnas' => ['type' => 'repeater', 'label' => __('Columnas', FLAVOR_PLATFORM_TEXT_DOMAIN), 'max_items' => 4, 'show_when' => ['variante' => ['multi_columna']], 'fields' => [
                        'titulo' => ['type' => 'text', 'label' => __('Título columna', FLAVOR_PLATFORM_TEXT_DOMAIN), 'default' => ''],
                        'enlaces' => ['type' => 'textarea', 'label' => __('Enlaces (uno por línea: texto|url)', FLAVOR_PLATFORM_TEXT_DOMAIN), 'default' => ''],
                    ]],
                ],
                'template' => 'unified/footer',
            ],
        ];
    }

    /**
     * Registrar un componente
     *
     * @param string $id ID único del componente
     * @param array $data Datos del componente
     * @param string $module_id ID del módulo propietario
     */
    public function register_component($id, $data, $module_id = '') {
        $defaults = [
            'label' => '',
            'description' => '',
            'category' => 'content',
            'icon' => 'dashicons-layout',
            'fields' => [],
            'template' => '',
            'preview' => '',
            'variants' => [],
            'presets' => [],
            'deprecated' => false,
            'supports' => [
                'align' => true,
                'spacing' => true,
                'background' => true,
            ],
            'module_id' => $module_id,
        ];

        $this->components[$id] = wp_parse_args($data, $defaults);
    }

    /**
     * Obtener todos los componentes registrados
     */
    public function get_components() {
        if (!$this->modules_loaded) {
            $this->load_module_components();
        }
        return $this->components;
    }

    /**
     * Obtener solo componentes unificados (no deprecated)
     */
    public function get_unified_components() {
        if (!$this->modules_loaded) {
            $this->load_module_components();
        }
        return array_filter($this->components, function($componente) {
            return !empty($componente['variants']) && empty($componente['deprecated']);
        });
    }

    /**
     * Obtener componente por ID, resolviendo aliases si es necesario
     */
    public function get_component($id) {
        // Asegurar que los componentes estén cargados
        if (!$this->modules_loaded) {
            $this->load_module_components();
        }
        // Primero buscar directamente
        if (isset($this->components[$id])) {
            return $this->components[$id];
        }

        // Intentar resolver alias
        $alias_resuelto = $this->resolve_alias($id);
        if ($alias_resuelto) {
            $componente = $this->components[$alias_resuelto['target']] ?? null;
            if ($componente) {
                $componente['_alias_from'] = $id;
                $componente['_alias_preset'] = $alias_resuelto['preset'];
                return $componente;
            }
        }

        return null;
    }

    /**
     * Resolver un alias de componente
     *
     * @param string $id ID del componente a resolver
     * @return array|null ['target' => 'unified_xxx', 'preset' => 'preset_name'] o null
     */
    public function resolve_alias($id) {
        return $this->alias_mapa_componentes[$id] ?? null;
    }

    /**
     * Verificar si un ID es un alias de componente legacy
     */
    public function es_alias($id) {
        return isset($this->alias_mapa_componentes[$id]);
    }

    /**
     * Obtener presets de un componente unificado
     *
     * @param string $component_id ID del componente unificado
     * @return array Presets disponibles
     */
    public function get_presets($component_id) {
        $componente = $this->components[$component_id] ?? null;
        if (!$componente || empty($componente['presets'])) {
            return [];
        }
        return $componente['presets'];
    }

    /**
     * Obtener variantes de un componente unificado
     *
     * @param string $component_id ID del componente unificado
     * @return array Variantes disponibles
     */
    public function get_variants($component_id) {
        $componente = $this->components[$component_id] ?? null;
        if (!$componente || empty($componente['variants'])) {
            return [];
        }
        return $componente['variants'];
    }

    /**
     * Obtener componentes por categoría
     *
     * @param string $category Categoría a filtrar
     * @param bool $solo_unificados Si true (default), solo devuelve componentes unificados (con variants)
     * @return array Componentes filtrados
     */
    public function get_components_by_category($category, $solo_unificados = true) {
        // Asegurar que los componentes estén cargados
        if (!$this->modules_loaded) {
            $this->load_module_components();
        }
        return array_filter($this->components, function($componente) use ($category, $solo_unificados) {
            // Filtrar por categoría
            if ($componente['category'] !== $category) {
                return false;
            }
            // Si solo queremos unificados, verificar que tenga variants
            if ($solo_unificados && empty($componente['variants'])) {
                return false;
            }
            return true;
        });
    }

    /**
     * Obtener componentes por módulo
     */
    public function get_components_by_module($module_id) {
        return array_filter($this->components, function($component) use ($module_id) {
            return $component['module_id'] === $module_id;
        });
    }

    /**
     * Obtener lista de módulos con componentes registrados
     */
    public function get_modules_with_components() {
        $modules_map = [];
        foreach ($this->components as $component) {
            $mod_id = $component['module_id'] ?? '';
            if (!empty($mod_id) && !isset($modules_map[$mod_id])) {
                $modules_map[$mod_id] = $this->format_module_label($mod_id);
            }
        }
        asort($modules_map);
        return $modules_map;
    }

    /**
     * Formatear ID de módulo como etiqueta legible
     */
    private function format_module_label($module_id) {
        $labels = [
            'frontend-landings' => __('Landings Generales', FLAVOR_PLATFORM_TEXT_DOMAIN),
            'bicicletas_compartidas' => __('Bicicletas Compartidas', FLAVOR_PLATFORM_TEXT_DOMAIN),
            'colectivos' => __('Colectivos', FLAVOR_PLATFORM_TEXT_DOMAIN),
            'foros' => __('Foros', FLAVOR_PLATFORM_TEXT_DOMAIN),
            'clientes' => __('Clientes', FLAVOR_PLATFORM_TEXT_DOMAIN),
            'comunidades' => __('Comunidades', FLAVOR_PLATFORM_TEXT_DOMAIN),
            'bares' => __('Bares', FLAVOR_PLATFORM_TEXT_DOMAIN),
            'trading_ia' => __('Trading IA', FLAVOR_PLATFORM_TEXT_DOMAIN),
            'dex_solana' => __('DEX Solana', FLAVOR_PLATFORM_TEXT_DOMAIN),
            'themacle' => __('Themacle', FLAVOR_PLATFORM_TEXT_DOMAIN),
            'reservas' => __('Reservas', FLAVOR_PLATFORM_TEXT_DOMAIN),
            'eventos' => __('Eventos', FLAVOR_PLATFORM_TEXT_DOMAIN),
            'donaciones' => __('Donaciones', FLAVOR_PLATFORM_TEXT_DOMAIN),
            'inventario' => __('Inventario', FLAVOR_PLATFORM_TEXT_DOMAIN),
            'encuestas' => __('Encuestas', FLAVOR_PLATFORM_TEXT_DOMAIN),
            'voluntariado' => __('Voluntariado', FLAVOR_PLATFORM_TEXT_DOMAIN),
            'marketplace' => __('Marketplace', FLAVOR_PLATFORM_TEXT_DOMAIN),
            'formacion' => __('Formación', FLAVOR_PLATFORM_TEXT_DOMAIN),
            'proyectos' => __('Proyectos', FLAVOR_PLATFORM_TEXT_DOMAIN),
            'red_apoyo_mutuo' => __('Red Apoyo Mutuo', FLAVOR_PLATFORM_TEXT_DOMAIN),
            'agenda' => __('Agenda', FLAVOR_PLATFORM_TEXT_DOMAIN),
            'biblioteca' => __('Biblioteca', FLAVOR_PLATFORM_TEXT_DOMAIN),
            'huertos_comunitarios' => __('Huertos Comunitarios', FLAVOR_PLATFORM_TEXT_DOMAIN),
            'banco_tiempo' => __('Banco de Tiempo', FLAVOR_PLATFORM_TEXT_DOMAIN),
            'moneda_local' => __('Moneda Local', FLAVOR_PLATFORM_TEXT_DOMAIN),
            'energia_comunitaria' => __('Energía Comunitaria', FLAVOR_PLATFORM_TEXT_DOMAIN),
            'reciclaje' => __('Reciclaje', FLAVOR_PLATFORM_TEXT_DOMAIN),
            'transporte_compartido' => __('Transporte Compartido', FLAVOR_PLATFORM_TEXT_DOMAIN),
            'cuidados' => __('Cuidados', FLAVOR_PLATFORM_TEXT_DOMAIN),
            'vivienda_compartida' => __('Vivienda Compartida', FLAVOR_PLATFORM_TEXT_DOMAIN),
            'grupos_consumo' => __('Grupos de Consumo', FLAVOR_PLATFORM_TEXT_DOMAIN),
        ];
        return $labels[$module_id] ?? ucwords(str_replace(['_', '-'], ' ', $module_id));
    }

    /**
     * Obtener categorías
     */
    public function get_categories() {
        return $this->categories;
    }

    /**
     * Renderizar un componente
     *
     * @param string $component_id ID del componente
     * @param array $data Datos para el componente
     * @param array $settings Configuración adicional
     */
    public function render_component($component_id, $data = [], $settings = []) {
        $component = $this->get_component($component_id);

        if (!$component) {
            return '';
        }

        // Validar y sanitizar datos
        $validated_data = $this->validate_component_data($component, $data);

        // Aplicar configuración
        $settings = wp_parse_args($settings, [
            'align' => '',
            'spacing' => [
                'margin' => ['top' => 0, 'bottom' => 0],
                'padding' => ['top' => 0, 'bottom' => 0],
            ],
            'background' => [
                'color' => '',
                'image' => '',
            ],
        ]);

        // Generar clases CSS
        $css_classes = $this->generate_css_classes($component, $settings);

        // Renderizar template
        ob_start();

        // Variables disponibles en el template
        extract($validated_data);
        $component_settings = $settings;
        $component_classes = $css_classes;

        $template_path = $this->get_template_path($component['template']);

        if (file_exists($template_path)) {
            include $template_path;
        } else {
            echo '<!-- Template not found: ' . esc_html($component['template']) . ' -->';
        }

        return ob_get_clean();
    }

    /**
     * Validar datos del componente
     */
    private function validate_component_data($component, $data) {
        $validated = [];

        foreach ($component['fields'] as $field_name => $field_config) {
            $value = $data[$field_name] ?? ($field_config['default'] ?? '');

            switch ($field_config['type']) {
                case 'text':
                    $validated[$field_name] = sanitize_text_field($value);
                    break;
                case 'textarea':
                    $validated[$field_name] = sanitize_textarea_field($value);
                    break;
                case 'wysiwyg':
                    $validated[$field_name] = wp_kses_post($value);
                    break;
                case 'url':
                    $validated[$field_name] = esc_url_raw($value);
                    break;
                case 'email':
                    $validated[$field_name] = sanitize_email($value);
                    break;
                case 'number':
                    $validated[$field_name] = absint($value);
                    break;
                case 'toggle':
                case 'checkbox':
                    $validated[$field_name] = (bool) $value;
                    break;
                case 'select':
                case 'radio':
                    $options = $field_config['options'] ?? [];
                    if (in_array($value, $options)) {
                        $validated[$field_name] = $value;
                    } else {
                        $validated[$field_name] = $field_config['default'] ?? '';
                    }
                    break;
                case 'image':
                    $validated[$field_name] = absint($value); // Attachment ID
                    break;
                case 'color':
                    $validated[$field_name] = sanitize_hex_color($value);
                    break;
                case 'repeater':
                    // Validate repeater items: array of objects with sub-fields
                    if (is_array($value)) {
                        $sub_fields = $field_config['fields'] ?? [];
                        $validated_items = [];
                        foreach ($value as $item) {
                            if (!is_array($item)) continue;
                            $validated_item = [];
                            foreach ($sub_fields as $sub_name => $sub_config) {
                                $sub_value = $item[$sub_name] ?? ($sub_config['default'] ?? '');
                                $sub_type = $sub_config['type'] ?? 'text';
                                switch ($sub_type) {
                                    case 'text':
                                        $validated_item[$sub_name] = sanitize_text_field($sub_value);
                                        break;
                                    case 'textarea':
                                        $validated_item[$sub_name] = sanitize_textarea_field($sub_value);
                                        break;
                                    case 'number':
                                        $validated_item[$sub_name] = is_numeric($sub_value) ? $sub_value : 0;
                                        break;
                                    case 'url':
                                        $validated_item[$sub_name] = esc_url_raw($sub_value);
                                        break;
                                    case 'toggle':
                                    case 'checkbox':
                                        $validated_item[$sub_name] = (bool) $sub_value;
                                        break;
                                    case 'image':
                                        $validated_item[$sub_name] = absint($sub_value);
                                        break;
                                    default:
                                        $validated_item[$sub_name] = sanitize_text_field($sub_value);
                                }
                            }
                            $validated_items[] = $validated_item;
                        }
                        $validated[$field_name] = $validated_items;
                    } else {
                        $validated[$field_name] = [];
                    }
                    break;
                case 'data_source':
                    // Data source stores source config as array or string
                    if (is_array($value)) {
                        $validated[$field_name] = [
                            'source' => sanitize_text_field($value['source'] ?? 'manual'),
                            'limite' => absint($value['limite'] ?? 6),
                            'orden'  => sanitize_text_field($value['orden'] ?? 'date_desc'),
                        ];
                    } else {
                        $validated[$field_name] = sanitize_text_field($value);
                    }
                    break;
                default:
                    $validated[$field_name] = $value;
            }
        }

        return $validated;
    }

    /**
     * Generar clases CSS para el componente
     */
    private function generate_css_classes($component, $settings) {
        $classes = ['flavor-component'];

        // Alineación
        if (!empty($settings['align'])) {
            $classes[] = 'align-' . $settings['align'];
        }

        // Spacing classes (Tailwind)
        $spacing = $settings['spacing'];

        if (!empty($spacing['margin']['top'])) {
            $classes[] = 'mt-' . $spacing['margin']['top'];
        }
        if (!empty($spacing['margin']['bottom'])) {
            $classes[] = 'mb-' . $spacing['margin']['bottom'];
        }
        if (!empty($spacing['padding']['top'])) {
            $classes[] = 'pt-' . $spacing['padding']['top'];
        }
        if (!empty($spacing['padding']['bottom'])) {
            $classes[] = 'pb-' . $spacing['padding']['bottom'];
        }

        return implode(' ', $classes);
    }

    /**
     * Obtener ruta del template
     */
    private function get_template_path($template) {
        $plugin_dir = dirname(dirname(__FILE__));

        // Buscar en tema primero (para personalización)
        $theme_path = get_stylesheet_directory() . '/flavor-components/' . $template . '.php';
        if (file_exists($theme_path)) {
            return $theme_path;
        }

        // Buscar en plugin
        $plugin_path = $plugin_dir . '/templates/components/' . $template . '.php';
        return $plugin_path;
    }

    /**
     * Obtener schema de campos para admin
     */
    public function get_component_fields_schema($component_id) {
        $component = $this->get_component($component_id);

        if (!$component) {
            return [];
        }

        return $component['fields'];
    }

    /**
     * Obtener tipos de campos de un componente
     *
     * Devuelve un array asociativo [nombre_campo => tipo]
     * Util para que el renderer sepa que campos son repeater/data_source
     *
     * @param string $component_id ID del componente
     * @return array Mapa de nombre_campo => tipo
     */
    public function get_component_field_types($component_id) {
        $component = $this->get_component($component_id);

        if (!$component || empty($component['fields'])) {
            return [];
        }

        $tipos_campo = [];
        foreach ($component['fields'] as $nombre_campo => $definicion_campo) {
            $tipos_campo[$nombre_campo] = $definicion_campo['type'] ?? 'text';
        }

        return $tipos_campo;
    }
}

// Inicializar registro
Flavor_Component_Registry::get_instance();

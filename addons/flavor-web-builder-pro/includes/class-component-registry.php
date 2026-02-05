<?php
/**
 * Component Registry - Sistema central de componentes web
 *
 * @package FlavorChatIA
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
            'hero' => __('Secciones Hero', 'flavor-chat-ia'),
            'content' => __('Contenido', 'flavor-chat-ia'),
            'forms' => __('Formularios', 'flavor-chat-ia'),
            'listings' => __('Listados', 'flavor-chat-ia'),
            'cards' => __('Tarjetas', 'flavor-chat-ia'),
            'navigation' => __('Navegación', 'flavor-chat-ia'),
            'features' => __('Características', 'flavor-chat-ia'),
            'testimonials' => __('Testimonios', 'flavor-chat-ia'),
            'cta' => __('Llamadas a la acción', 'flavor-chat-ia'),
            'footer' => __('Footer', 'flavor-chat-ia'),
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

        $module_loader = Flavor_Chat_Module_Loader::get_instance();

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
                'label' => __('Hero Grupos de Consumo', 'flavor-chat-ia'),
                'description' => __('Sección hero con información sobre grupos de consumo', 'flavor-chat-ia'),
                'category' => 'hero',
                'icon' => 'dashicons-carrot',
                'fields' => [
                    'titulo' => [
                        'type' => 'text',
                        'label' => __('Título', 'flavor-chat-ia'),
                        'default' => __('Grupos de Consumo', 'flavor-chat-ia'),
                    ],
                    'subtitulo' => [
                        'type' => 'textarea',
                        'label' => __('Subtítulo', 'flavor-chat-ia'),
                        'default' => __('Consume local, apoya a productores cercanos', 'flavor-chat-ia'),
                    ],
                    'imagen_fondo' => [
                        'type' => 'image',
                        'label' => __('Imagen de fondo', 'flavor-chat-ia'),
                        'default' => '',
                    ],
                    'mostrar_buscador' => [
                        'type' => 'toggle',
                        'label' => __('Mostrar buscador', 'flavor-chat-ia'),
                        'default' => true,
                    ],
                    'color_primario' => [
                        'type' => 'color',
                        'label' => __('Color primario', 'flavor-chat-ia'),
                        'default' => '#84cc16',
                    ],
                ],
                'template' => 'landings/grupos-consumo-hero',
            ],
            'grupos_consumo_listado' => [
                'label' => __('Listado Grupos de Consumo', 'flavor-chat-ia'),
                'description' => __('Grid de grupos de consumo disponibles', 'flavor-chat-ia'),
                'category' => 'listings',
                'icon' => 'dashicons-grid-view',
                'fields' => [
                    'titulo' => [
                        'type' => 'text',
                        'label' => __('Título', 'flavor-chat-ia'),
                        'default' => __('Grupos Disponibles', 'flavor-chat-ia'),
                    ],
                    'columnas' => [
                        'type' => 'select',
                        'label' => __('Columnas', 'flavor-chat-ia'),
                        'options' => [2, 3, 4],
                        'default' => 3,
                    ],
                    'limite' => [
                        'type' => 'number',
                        'label' => __('Número máximo', 'flavor-chat-ia'),
                        'default' => 6,
                    ],
                ],
                'template' => 'landings/grupos-consumo-listado',
            ],

            // BANCO DE TIEMPO
            'banco_tiempo_hero' => [
                'label' => __('Hero Banco de Tiempo', 'flavor-chat-ia'),
                'description' => __('Sección hero del banco de tiempo', 'flavor-chat-ia'),
                'category' => 'hero',
                'icon' => 'dashicons-clock',
                'fields' => [
                    'titulo' => [
                        'type' => 'text',
                        'label' => __('Título', 'flavor-chat-ia'),
                        'default' => __('Banco de Tiempo', 'flavor-chat-ia'),
                    ],
                    'subtitulo' => [
                        'type' => 'textarea',
                        'label' => __('Subtítulo', 'flavor-chat-ia'),
                        'default' => __('Intercambia habilidades con tu comunidad', 'flavor-chat-ia'),
                    ],
                    'imagen_fondo' => [
                        'type' => 'image',
                        'label' => __('Imagen de fondo', 'flavor-chat-ia'),
                        'default' => '',
                    ],
                    'color_primario' => [
                        'type' => 'color',
                        'label' => __('Color primario', 'flavor-chat-ia'),
                        'default' => '#8b5cf6',
                    ],
                ],
                'template' => 'landings/banco-tiempo-hero',
            ],
            'banco_tiempo_servicios' => [
                'label' => __('Servicios Banco de Tiempo', 'flavor-chat-ia'),
                'description' => __('Grid de servicios ofrecidos y demandados', 'flavor-chat-ia'),
                'category' => 'listings',
                'icon' => 'dashicons-screenoptions',
                'fields' => [
                    'titulo' => [
                        'type' => 'text',
                        'label' => __('Título', 'flavor-chat-ia'),
                        'default' => __('Servicios Disponibles', 'flavor-chat-ia'),
                    ],
                    'tipo' => [
                        'type' => 'select',
                        'label' => __('Tipo de servicios', 'flavor-chat-ia'),
                        'options' => ['todos', 'ofertas', 'demandas'],
                        'default' => 'todos',
                    ],
                    'limite' => [
                        'type' => 'number',
                        'label' => __('Número máximo', 'flavor-chat-ia'),
                        'default' => 8,
                    ],
                ],
                'template' => 'landings/banco-tiempo-servicios',
            ],

            // AYUNTAMIENTO
            'ayuntamiento_hero' => [
                'label' => __('Hero Ayuntamiento', 'flavor-chat-ia'),
                'description' => __('Sección hero del portal ciudadano', 'flavor-chat-ia'),
                'category' => 'hero',
                'icon' => 'dashicons-building',
                'fields' => [
                    'titulo' => [
                        'type' => 'text',
                        'label' => __('Título', 'flavor-chat-ia'),
                        'default' => __('Portal Ciudadano', 'flavor-chat-ia'),
                    ],
                    'subtitulo' => [
                        'type' => 'textarea',
                        'label' => __('Subtítulo', 'flavor-chat-ia'),
                        'default' => __('Tu ayuntamiento a un clic', 'flavor-chat-ia'),
                    ],
                    'mostrar_buscador' => [
                        'type' => 'toggle',
                        'label' => __('Mostrar buscador', 'flavor-chat-ia'),
                        'default' => true,
                    ],
                    'color_primario' => [
                        'type' => 'color',
                        'label' => __('Color primario', 'flavor-chat-ia'),
                        'default' => '#1d4ed8',
                    ],
                ],
                'template' => 'landings/ayuntamiento-hero',
            ],
            'ayuntamiento_tramites' => [
                'label' => __('Trámites Destacados', 'flavor-chat-ia'),
                'description' => __('Listado de trámites más solicitados', 'flavor-chat-ia'),
                'category' => 'listings',
                'icon' => 'dashicons-clipboard',
                'fields' => [
                    'titulo' => [
                        'type' => 'text',
                        'label' => __('Título', 'flavor-chat-ia'),
                        'default' => __('Trámites más solicitados', 'flavor-chat-ia'),
                    ],
                    'limite' => [
                        'type' => 'number',
                        'label' => __('Número máximo', 'flavor-chat-ia'),
                        'default' => 6,
                    ],
                ],
                'template' => 'landings/ayuntamiento-tramites',
            ],
            'ayuntamiento_noticias' => [
                'label' => __('Noticias Ayuntamiento', 'flavor-chat-ia'),
                'description' => __('Últimas noticias municipales', 'flavor-chat-ia'),
                'category' => 'content',
                'icon' => 'dashicons-megaphone',
                'fields' => [
                    'titulo' => [
                        'type' => 'text',
                        'label' => __('Título', 'flavor-chat-ia'),
                        'default' => __('Últimas Noticias', 'flavor-chat-ia'),
                    ],
                    'limite' => [
                        'type' => 'number',
                        'label' => __('Número máximo', 'flavor-chat-ia'),
                        'default' => 4,
                    ],
                ],
                'template' => 'landings/ayuntamiento-noticias',
            ],

            // COMUNIDADES
            'comunidades_hero' => [
                'label' => __('Hero Comunidades', 'flavor-chat-ia'),
                'description' => __('Sección hero de comunidades', 'flavor-chat-ia'),
                'category' => 'hero',
                'icon' => 'dashicons-groups',
                'fields' => [
                    'titulo' => [
                        'type' => 'text',
                        'label' => __('Título', 'flavor-chat-ia'),
                        'default' => __('Comunidades', 'flavor-chat-ia'),
                    ],
                    'subtitulo' => [
                        'type' => 'textarea',
                        'label' => __('Subtítulo', 'flavor-chat-ia'),
                        'default' => __('Conecta con tu vecindario', 'flavor-chat-ia'),
                    ],
                    'color_primario' => [
                        'type' => 'color',
                        'label' => __('Color primario', 'flavor-chat-ia'),
                        'default' => '#f43f5e',
                    ],
                ],
                'template' => 'landings/comunidades-hero',
            ],
            'comunidades_listado' => [
                'label' => __('Listado Comunidades', 'flavor-chat-ia'),
                'description' => __('Grid de comunidades disponibles', 'flavor-chat-ia'),
                'category' => 'listings',
                'icon' => 'dashicons-networking',
                'fields' => [
                    'titulo' => [
                        'type' => 'text',
                        'label' => __('Título', 'flavor-chat-ia'),
                        'default' => __('Comunidades Activas', 'flavor-chat-ia'),
                    ],
                    'tipo' => [
                        'type' => 'select',
                        'label' => __('Tipo', 'flavor-chat-ia'),
                        'options' => ['todas', 'vecinales', 'deportivas', 'culturales'],
                        'default' => 'todas',
                    ],
                    'limite' => [
                        'type' => 'number',
                        'label' => __('Número máximo', 'flavor-chat-ia'),
                        'default' => 6,
                    ],
                ],
                'template' => 'landings/comunidades-listado',
            ],

            // ESPACIOS COMUNES
            'espacios_comunes_hero' => [
                'label' => __('Hero Espacios Comunes', 'flavor-chat-ia'),
                'description' => __('Sección hero de reserva de espacios', 'flavor-chat-ia'),
                'category' => 'hero',
                'icon' => 'dashicons-admin-multisite',
                'fields' => [
                    'titulo' => [
                        'type' => 'text',
                        'label' => __('Título', 'flavor-chat-ia'),
                        'default' => __('Espacios Comunes', 'flavor-chat-ia'),
                    ],
                    'subtitulo' => [
                        'type' => 'textarea',
                        'label' => __('Subtítulo', 'flavor-chat-ia'),
                        'default' => __('Reserva salas y espacios para tus actividades', 'flavor-chat-ia'),
                    ],
                    'color_primario' => [
                        'type' => 'color',
                        'label' => __('Color primario', 'flavor-chat-ia'),
                        'default' => '#06b6d4',
                    ],
                ],
                'template' => 'landings/espacios-comunes-hero',
            ],

            // HUERTOS URBANOS
            'huertos_urbanos_hero' => [
                'label' => __('Hero Huertos Urbanos', 'flavor-chat-ia'),
                'description' => __('Sección hero de huertos urbanos', 'flavor-chat-ia'),
                'category' => 'hero',
                'icon' => 'dashicons-palmtree',
                'fields' => [
                    'titulo' => [
                        'type' => 'text',
                        'label' => __('Título', 'flavor-chat-ia'),
                        'default' => __('Huertos Urbanos', 'flavor-chat-ia'),
                    ],
                    'subtitulo' => [
                        'type' => 'textarea',
                        'label' => __('Subtítulo', 'flavor-chat-ia'),
                        'default' => __('Cultiva tu propio huerto en la ciudad', 'flavor-chat-ia'),
                    ],
                    'color_primario' => [
                        'type' => 'color',
                        'label' => __('Color primario', 'flavor-chat-ia'),
                        'default' => '#22c55e',
                    ],
                ],
                'template' => 'landings/huertos-urbanos-hero',
            ],

            // BIBLIOTECA
            'biblioteca_hero' => [
                'label' => __('Hero Biblioteca', 'flavor-chat-ia'),
                'description' => __('Sección hero de biblioteca comunitaria', 'flavor-chat-ia'),
                'category' => 'hero',
                'icon' => 'dashicons-book',
                'fields' => [
                    'titulo' => [
                        'type' => 'text',
                        'label' => __('Título', 'flavor-chat-ia'),
                        'default' => __('Biblioteca Comunitaria', 'flavor-chat-ia'),
                    ],
                    'subtitulo' => [
                        'type' => 'textarea',
                        'label' => __('Subtítulo', 'flavor-chat-ia'),
                        'default' => __('Comparte y descubre libros con tus vecinos', 'flavor-chat-ia'),
                    ],
                    'color_primario' => [
                        'type' => 'color',
                        'label' => __('Color primario', 'flavor-chat-ia'),
                        'default' => '#6366f1',
                    ],
                ],
                'template' => 'landings/biblioteca-hero',
            ],

            // CURSOS
            'cursos_hero' => [
                'label' => __('Hero Cursos', 'flavor-chat-ia'),
                'description' => __('Sección hero de cursos y talleres', 'flavor-chat-ia'),
                'category' => 'hero',
                'icon' => 'dashicons-welcome-learn-more',
                'fields' => [
                    'titulo' => [
                        'type' => 'text',
                        'label' => __('Título', 'flavor-chat-ia'),
                        'default' => __('Cursos y Talleres', 'flavor-chat-ia'),
                    ],
                    'subtitulo' => [
                        'type' => 'textarea',
                        'label' => __('Subtítulo', 'flavor-chat-ia'),
                        'default' => __('Aprende nuevas habilidades con tu comunidad', 'flavor-chat-ia'),
                    ],
                    'color_primario' => [
                        'type' => 'color',
                        'label' => __('Color primario', 'flavor-chat-ia'),
                        'default' => '#a855f7',
                    ],
                ],
                'template' => 'landings/cursos-hero',
            ],

            // INCIDENCIAS
            'incidencias_hero' => [
                'label' => __('Hero Incidencias', 'flavor-chat-ia'),
                'description' => __('Sección hero para reportar incidencias', 'flavor-chat-ia'),
                'category' => 'hero',
                'icon' => 'dashicons-warning',
                'fields' => [
                    'titulo' => [
                        'type' => 'text',
                        'label' => __('Título', 'flavor-chat-ia'),
                        'default' => __('Reportar Incidencias', 'flavor-chat-ia'),
                    ],
                    'subtitulo' => [
                        'type' => 'textarea',
                        'label' => __('Subtítulo', 'flavor-chat-ia'),
                        'default' => __('Ayúdanos a mejorar tu barrio reportando problemas', 'flavor-chat-ia'),
                    ],
                    'color_primario' => [
                        'type' => 'color',
                        'label' => __('Color primario', 'flavor-chat-ia'),
                        'default' => '#e11d48',
                    ],
                ],
                'template' => 'landings/incidencias-hero',
            ],

            // TIENDA LOCAL
            'tienda_local_hero' => [
                'label' => __('Hero Tienda Local', 'flavor-chat-ia'),
                'description' => __('Sección hero de comercios locales', 'flavor-chat-ia'),
                'category' => 'hero',
                'icon' => 'dashicons-store',
                'fields' => [
                    'titulo' => [
                        'type' => 'text',
                        'label' => __('Título', 'flavor-chat-ia'),
                        'default' => __('Comercios Locales', 'flavor-chat-ia'),
                    ],
                    'subtitulo' => [
                        'type' => 'textarea',
                        'label' => __('Subtítulo', 'flavor-chat-ia'),
                        'default' => __('Apoya el comercio de tu barrio', 'flavor-chat-ia'),
                    ],
                    'color_primario' => [
                        'type' => 'color',
                        'label' => __('Color primario', 'flavor-chat-ia'),
                        'default' => '#f59e0b',
                    ],
                ],
                'template' => 'landings/tienda-local-hero',
            ],

            // RECICLAJE
            'reciclaje_hero' => [
                'label' => __('Hero Reciclaje', 'flavor-chat-ia'),
                'description' => __('Sección hero de puntos de reciclaje', 'flavor-chat-ia'),
                'category' => 'hero',
                'icon' => 'dashicons-update',
                'fields' => [
                    'titulo' => [
                        'type' => 'text',
                        'label' => __('Título', 'flavor-chat-ia'),
                        'default' => __('Reciclaje', 'flavor-chat-ia'),
                    ],
                    'subtitulo' => [
                        'type' => 'textarea',
                        'label' => __('Subtítulo', 'flavor-chat-ia'),
                        'default' => __('Reduce, reutiliza, recicla', 'flavor-chat-ia'),
                    ],
                    'color_primario' => [
                        'type' => 'color',
                        'label' => __('Color primario', 'flavor-chat-ia'),
                        'default' => '#10b981',
                    ],
                ],
                'template' => 'landings/reciclaje-hero',
            ],

            // BICICLETAS
            'bicicletas_hero' => [
                'label' => __('Hero Bicicletas', 'flavor-chat-ia'),
                'description' => __('Sección hero de préstamo de bicicletas', 'flavor-chat-ia'),
                'category' => 'hero',
                'icon' => 'dashicons-location-alt',
                'fields' => [
                    'titulo' => [
                        'type' => 'text',
                        'label' => __('Título', 'flavor-chat-ia'),
                        'default' => __('Bicicletas Compartidas', 'flavor-chat-ia'),
                    ],
                    'subtitulo' => [
                        'type' => 'textarea',
                        'label' => __('Subtítulo', 'flavor-chat-ia'),
                        'default' => __('Muévete de forma sostenible', 'flavor-chat-ia'),
                    ],
                    'color_primario' => [
                        'type' => 'color',
                        'label' => __('Color primario', 'flavor-chat-ia'),
                        'default' => '#a3e635',
                    ],
                ],
                'template' => 'landings/bicicletas-hero',
            ],

            // PODCAST
            'podcast_hero' => [
                'label' => __('Hero Podcast', 'flavor-chat-ia'),
                'description' => __('Sección hero de podcast comunitario', 'flavor-chat-ia'),
                'category' => 'hero',
                'icon' => 'dashicons-microphone',
                'fields' => [
                    'titulo' => [
                        'type' => 'text',
                        'label' => __('Título', 'flavor-chat-ia'),
                        'default' => __('Nuestro Podcast', 'flavor-chat-ia'),
                    ],
                    'subtitulo' => [
                        'type' => 'textarea',
                        'label' => __('Subtítulo', 'flavor-chat-ia'),
                        'default' => __('Voces de la comunidad', 'flavor-chat-ia'),
                    ],
                    'color_primario' => [
                        'type' => 'color',
                        'label' => __('Color primario', 'flavor-chat-ia'),
                        'default' => '#14b8a6',
                    ],
                ],
                'template' => 'landings/podcast-hero',
            ],

            // RADIO
            'radio_hero' => [
                'label' => __('Hero Radio', 'flavor-chat-ia'),
                'description' => __('Sección hero de radio comunitaria', 'flavor-chat-ia'),
                'category' => 'hero',
                'icon' => 'dashicons-controls-volumeon',
                'fields' => [
                    'titulo' => [
                        'type' => 'text',
                        'label' => __('Título', 'flavor-chat-ia'),
                        'default' => __('Radio Comunitaria', 'flavor-chat-ia'),
                    ],
                    'subtitulo' => [
                        'type' => 'textarea',
                        'label' => __('Subtítulo', 'flavor-chat-ia'),
                        'default' => __('La voz de tu barrio', 'flavor-chat-ia'),
                    ],
                    'color_primario' => [
                        'type' => 'color',
                        'label' => __('Color primario', 'flavor-chat-ia'),
                        'default' => '#ef4444',
                    ],
                ],
                'template' => 'landings/radio-hero',
            ],

            // AYUDA VECINAL
            'ayuda_vecinal_hero' => [
                'label' => __('Hero Ayuda Vecinal', 'flavor-chat-ia'),
                'description' => __('Sección hero de red de ayuda mutua', 'flavor-chat-ia'),
                'category' => 'hero',
                'icon' => 'dashicons-heart',
                'fields' => [
                    'titulo' => [
                        'type' => 'text',
                        'label' => __('Título', 'flavor-chat-ia'),
                        'default' => __('Ayuda Vecinal', 'flavor-chat-ia'),
                    ],
                    'subtitulo' => [
                        'type' => 'textarea',
                        'label' => __('Subtítulo', 'flavor-chat-ia'),
                        'default' => __('Vecinos que ayudan a vecinos', 'flavor-chat-ia'),
                    ],
                    'color_primario' => [
                        'type' => 'color',
                        'label' => __('Color primario', 'flavor-chat-ia'),
                        'default' => '#f97316',
                    ],
                ],
                'template' => 'landings/ayuda-vecinal-hero',
            ],

            // EMPRESARIAL - HERO
            'empresarial_hero' => [
                'label' => __('Hero Empresarial', 'flavor-chat-ia'),
                'description' => __('Sección hero para páginas corporativas', 'flavor-chat-ia'),
                'category' => 'hero',
                'icon' => 'dashicons-building',
                'fields' => [
                    'titulo' => [
                        'type' => 'text',
                        'label' => __('Título', 'flavor-chat-ia'),
                        'default' => __('Soluciones Empresariales', 'flavor-chat-ia'),
                    ],
                    'subtitulo' => [
                        'type' => 'textarea',
                        'label' => __('Subtítulo', 'flavor-chat-ia'),
                        'default' => __('Potencia tu negocio con tecnología de vanguardia', 'flavor-chat-ia'),
                    ],
                    'texto_boton_principal' => [
                        'type' => 'text',
                        'label' => __('Texto botón principal', 'flavor-chat-ia'),
                        'default' => __('Solicitar Demo', 'flavor-chat-ia'),
                    ],
                    'url_boton_principal' => [
                        'type' => 'url',
                        'label' => __('URL botón principal', 'flavor-chat-ia'),
                        'default' => '#contacto',
                    ],
                    'color_primario' => [
                        'type' => 'color',
                        'label' => __('Color primario', 'flavor-chat-ia'),
                        'default' => '#1e40af',
                    ],
                ],
                'template' => 'landings/empresarial-hero',
            ],
            'empresarial_servicios' => [
                'label' => __('Servicios Empresariales', 'flavor-chat-ia'),
                'description' => __('Grid de servicios para empresas', 'flavor-chat-ia'),
                'category' => 'listings',
                'icon' => 'dashicons-grid-view',
                'fields' => [
                    'titulo' => [
                        'type' => 'text',
                        'label' => __('Título', 'flavor-chat-ia'),
                        'default' => __('Nuestros Servicios', 'flavor-chat-ia'),
                    ],
                    'columnas' => [
                        'type' => 'select',
                        'label' => __('Columnas', 'flavor-chat-ia'),
                        'options' => [2, 3, 4],
                        'default' => 3,
                    ],
                ],
                'template' => 'landings/empresarial-servicios',
            ],
            'empresarial_stats' => [
                'label' => __('Estadísticas Empresariales', 'flavor-chat-ia'),
                'description' => __('Sección de métricas y logros', 'flavor-chat-ia'),
                'category' => 'content',
                'icon' => 'dashicons-chart-bar',
                'fields' => [
                    'titulo' => [
                        'type' => 'text',
                        'label' => __('Título', 'flavor-chat-ia'),
                        'default' => __('Nuestros Resultados', 'flavor-chat-ia'),
                    ],
                ],
                'template' => 'landings/empresarial-stats',
            ],
            'empresarial_testimonios' => [
                'label' => __('Testimonios Empresariales', 'flavor-chat-ia'),
                'description' => __('Testimonios de clientes', 'flavor-chat-ia'),
                'category' => 'testimonials',
                'icon' => 'dashicons-format-quote',
                'fields' => [
                    'titulo' => [
                        'type' => 'text',
                        'label' => __('Título', 'flavor-chat-ia'),
                        'default' => __('Lo que dicen nuestros clientes', 'flavor-chat-ia'),
                    ],
                ],
                'template' => 'landings/empresarial-testimonios',
            ],
            'empresarial_contacto' => [
                'label' => __('Contacto Empresarial', 'flavor-chat-ia'),
                'description' => __('Formulario de contacto empresarial', 'flavor-chat-ia'),
                'category' => 'forms',
                'icon' => 'dashicons-email',
                'fields' => [
                    'titulo' => [
                        'type' => 'text',
                        'label' => __('Título', 'flavor-chat-ia'),
                        'default' => __('Contacta con nosotros', 'flavor-chat-ia'),
                    ],
                ],
                'template' => 'landings/empresarial-contacto',
            ],
            'empresarial_pricing' => [
                'label' => __('Tabla de Precios', 'flavor-chat-ia'),
                'description' => __('Planes y precios para servicios', 'flavor-chat-ia'),
                'category' => 'content',
                'icon' => 'dashicons-money-alt',
                'fields' => [
                    'titulo' => [
                        'type' => 'text',
                        'label' => __('Título', 'flavor-chat-ia'),
                        'default' => __('Planes y Precios', 'flavor-chat-ia'),
                    ],
                ],
                'template' => 'landings/empresarial-pricing',
            ],
            'empresarial_equipo' => [
                'label' => __('Equipo', 'flavor-chat-ia'),
                'description' => __('Presentación del equipo', 'flavor-chat-ia'),
                'category' => 'content',
                'icon' => 'dashicons-groups',
                'fields' => [
                    'titulo' => [
                        'type' => 'text',
                        'label' => __('Título', 'flavor-chat-ia'),
                        'default' => __('Nuestro Equipo', 'flavor-chat-ia'),
                    ],
                ],
                'template' => 'landings/empresarial-equipo',
            ],

            // OFIMÁTICA
            'ofimatica_hero' => [
                'label' => __('Hero Ofimática', 'flavor-chat-ia'),
                'description' => __('Hero para suite de ofimática', 'flavor-chat-ia'),
                'category' => 'hero',
                'icon' => 'dashicons-media-document',
                'fields' => [
                    'titulo' => [
                        'type' => 'text',
                        'label' => __('Título', 'flavor-chat-ia'),
                        'default' => __('Suite de Productividad', 'flavor-chat-ia'),
                    ],
                    'subtitulo' => [
                        'type' => 'textarea',
                        'label' => __('Subtítulo', 'flavor-chat-ia'),
                        'default' => __('Documentos, hojas de cálculo y presentaciones en la nube', 'flavor-chat-ia'),
                    ],
                    'color_primario' => [
                        'type' => 'color',
                        'label' => __('Color primario', 'flavor-chat-ia'),
                        'default' => '#0284c7',
                    ],
                ],
                'template' => 'landings/ofimatica-hero',
            ],
            'ofimatica_apps' => [
                'label' => __('Apps Ofimática', 'flavor-chat-ia'),
                'description' => __('Grid de aplicaciones de productividad', 'flavor-chat-ia'),
                'category' => 'listings',
                'icon' => 'dashicons-screenoptions',
                'fields' => [
                    'titulo' => [
                        'type' => 'text',
                        'label' => __('Título', 'flavor-chat-ia'),
                        'default' => __('Nuestras Aplicaciones', 'flavor-chat-ia'),
                    ],
                ],
                'template' => 'landings/ofimatica-apps',
            ],
            'ofimatica_features' => [
                'label' => __('Características Ofimática', 'flavor-chat-ia'),
                'description' => __('Características de la suite', 'flavor-chat-ia'),
                'category' => 'features',
                'icon' => 'dashicons-yes-alt',
                'fields' => [
                    'titulo' => [
                        'type' => 'text',
                        'label' => __('Título', 'flavor-chat-ia'),
                        'default' => __('Características', 'flavor-chat-ia'),
                    ],
                ],
                'template' => 'landings/ofimatica-features',
            ],

            // SAAS
            'saas_hero' => [
                'label' => __('Hero SaaS', 'flavor-chat-ia'),
                'description' => __('Hero para productos SaaS', 'flavor-chat-ia'),
                'category' => 'hero',
                'icon' => 'dashicons-cloud',
                'fields' => [
                    'titulo' => [
                        'type' => 'text',
                        'label' => __('Título', 'flavor-chat-ia'),
                        'default' => __('Software en la Nube', 'flavor-chat-ia'),
                    ],
                    'subtitulo' => [
                        'type' => 'textarea',
                        'label' => __('Subtítulo', 'flavor-chat-ia'),
                        'default' => __('Accede desde cualquier lugar, en cualquier momento', 'flavor-chat-ia'),
                    ],
                    'color_primario' => [
                        'type' => 'color',
                        'label' => __('Color primario', 'flavor-chat-ia'),
                        'default' => '#7c3aed',
                    ],
                ],
                'template' => 'landings/saas-hero',
            ],
            'saas_features' => [
                'label' => __('Features SaaS', 'flavor-chat-ia'),
                'description' => __('Características del producto SaaS', 'flavor-chat-ia'),
                'category' => 'features',
                'icon' => 'dashicons-admin-plugins',
                'fields' => [
                    'titulo' => [
                        'type' => 'text',
                        'label' => __('Título', 'flavor-chat-ia'),
                        'default' => __('Funcionalidades', 'flavor-chat-ia'),
                    ],
                ],
                'template' => 'landings/saas-features',
            ],

            // AGENCIA
            'agencia_hero' => [
                'label' => __('Hero Agencia', 'flavor-chat-ia'),
                'description' => __('Hero para agencias y consultoras', 'flavor-chat-ia'),
                'category' => 'hero',
                'icon' => 'dashicons-portfolio',
                'fields' => [
                    'titulo' => [
                        'type' => 'text',
                        'label' => __('Título', 'flavor-chat-ia'),
                        'default' => __('Agencia Creativa', 'flavor-chat-ia'),
                    ],
                    'subtitulo' => [
                        'type' => 'textarea',
                        'label' => __('Subtítulo', 'flavor-chat-ia'),
                        'default' => __('Diseño, desarrollo y estrategia digital', 'flavor-chat-ia'),
                    ],
                    'color_primario' => [
                        'type' => 'color',
                        'label' => __('Color primario', 'flavor-chat-ia'),
                        'default' => '#ec4899',
                    ],
                ],
                'template' => 'landings/agencia-hero',
            ],
            'agencia_portfolio' => [
                'label' => __('Portfolio Agencia', 'flavor-chat-ia'),
                'description' => __('Galería de trabajos realizados', 'flavor-chat-ia'),
                'category' => 'listings',
                'icon' => 'dashicons-images-alt2',
                'fields' => [
                    'titulo' => [
                        'type' => 'text',
                        'label' => __('Título', 'flavor-chat-ia'),
                        'default' => __('Nuestros Proyectos', 'flavor-chat-ia'),
                    ],
                ],
                'template' => 'landings/agencia-portfolio',
            ],

            // =====================================================
            // COMPONENTES ADICIONALES PARA TEMPLATES
            // =====================================================

            // CARPOOLING
            'carpooling_hero' => [
                'label' => __('Hero Carpooling', 'flavor-chat-ia'),
                'description' => __('Sección hero para compartir viajes', 'flavor-chat-ia'),
                'category' => 'hero',
                'icon' => 'dashicons-car',
                'fields' => [
                    'titulo' => [
                        'type' => 'text',
                        'label' => __('Título', 'flavor-chat-ia'),
                        'default' => __('Comparte tu Viaje', 'flavor-chat-ia'),
                    ],
                    'subtitulo' => [
                        'type' => 'textarea',
                        'label' => __('Subtítulo', 'flavor-chat-ia'),
                        'default' => __('Ahorra dinero y reduce tu huella de carbono', 'flavor-chat-ia'),
                    ],
                    'imagen_fondo' => [
                        'type' => 'image',
                        'label' => __('Imagen de fondo', 'flavor-chat-ia'),
                        'default' => '',
                    ],
                    'mostrar_buscador' => [
                        'type' => 'toggle',
                        'label' => __('Mostrar buscador', 'flavor-chat-ia'),
                        'default' => true,
                    ],
                    'color_primario' => [
                        'type' => 'color',
                        'label' => __('Color primario', 'flavor-chat-ia'),
                        'default' => '#3b82f6',
                    ],
                ],
                'template' => 'landings/carpooling-hero',
            ],
            'carpooling_viajes_grid' => [
                'label' => __('Grid Viajes Carpooling', 'flavor-chat-ia'),
                'description' => __('Listado de viajes disponibles', 'flavor-chat-ia'),
                'category' => 'listings',
                'icon' => 'dashicons-grid-view',
                'fields' => [
                    'titulo' => [
                        'type' => 'text',
                        'label' => __('Título', 'flavor-chat-ia'),
                        'default' => __('Viajes Disponibles', 'flavor-chat-ia'),
                    ],
                    'columnas' => [
                        'type' => 'select',
                        'label' => __('Columnas', 'flavor-chat-ia'),
                        'options' => [2, 3, 4],
                        'default' => 3,
                    ],
                    'limite' => [
                        'type' => 'number',
                        'label' => __('Número máximo', 'flavor-chat-ia'),
                        'default' => 9,
                    ],
                ],
                'template' => 'landings/carpooling-viajes-grid',
            ],
            'carpooling_como_funciona' => [
                'label' => __('Cómo Funciona Carpooling', 'flavor-chat-ia'),
                'description' => __('Pasos para usar el carpooling', 'flavor-chat-ia'),
                'category' => 'content',
                'icon' => 'dashicons-info',
                'fields' => [
                    'titulo' => [
                        'type' => 'text',
                        'label' => __('Título', 'flavor-chat-ia'),
                        'default' => __('Cómo Funciona', 'flavor-chat-ia'),
                    ],
                ],
                'template' => 'landings/carpooling-como-funciona',
            ],
            'carpooling_cta_conductor' => [
                'label' => __('CTA Conductor Carpooling', 'flavor-chat-ia'),
                'description' => __('Llamada a la acción para conductores', 'flavor-chat-ia'),
                'category' => 'cta',
                'icon' => 'dashicons-megaphone',
                'fields' => [
                    'titulo' => [
                        'type' => 'text',
                        'label' => __('Título', 'flavor-chat-ia'),
                        'default' => __('¿Tienes un viaje programado?', 'flavor-chat-ia'),
                    ],
                    'descripcion' => [
                        'type' => 'textarea',
                        'label' => __('Descripción', 'flavor-chat-ia'),
                        'default' => __('Publica tu ruta y comparte gastos', 'flavor-chat-ia'),
                    ],
                    'boton_texto' => [
                        'type' => 'text',
                        'label' => __('Texto del botón', 'flavor-chat-ia'),
                        'default' => __('Publicar Viaje', 'flavor-chat-ia'),
                    ],
                    'boton_url' => [
                        'type' => 'url',
                        'label' => __('URL del botón', 'flavor-chat-ia'),
                        'default' => '#',
                    ],
                    'color_fondo' => [
                        'type' => 'color',
                        'label' => __('Color de fondo', 'flavor-chat-ia'),
                        'default' => '#3b82f6',
                    ],
                ],
                'template' => 'landings/carpooling-cta-conductor',
            ],

            // BICICLETAS COMPARTIDAS
            'bicicletas_compartidas_hero' => [
                'label' => __('Hero Bicicletas Compartidas', 'flavor-chat-ia'),
                'description' => __('Sección hero de bicicletas compartidas', 'flavor-chat-ia'),
                'category' => 'hero',
                'icon' => 'dashicons-location-alt',
                'fields' => [
                    'titulo' => [
                        'type' => 'text',
                        'label' => __('Título', 'flavor-chat-ia'),
                        'default' => __('Bicicletas Compartidas', 'flavor-chat-ia'),
                    ],
                    'subtitulo' => [
                        'type' => 'textarea',
                        'label' => __('Subtítulo', 'flavor-chat-ia'),
                        'default' => __('Movilidad sostenible y saludable', 'flavor-chat-ia'),
                    ],
                    'color_primario' => [
                        'type' => 'color',
                        'label' => __('Color primario', 'flavor-chat-ia'),
                        'default' => '#a3e635',
                    ],
                ],
                'template' => 'landings/bicicletas-compartidas-hero',
            ],
            'bicicletas_compartidas_mapa' => [
                'label' => __('Mapa Bicicletas', 'flavor-chat-ia'),
                'description' => __('Mapa de estaciones de bicicletas', 'flavor-chat-ia'),
                'category' => 'content',
                'icon' => 'dashicons-location',
                'fields' => [
                    'titulo' => [
                        'type' => 'text',
                        'label' => __('Título', 'flavor-chat-ia'),
                        'default' => __('Encuentra tu Estación', 'flavor-chat-ia'),
                    ],
                    'altura_mapa' => [
                        'type' => 'number',
                        'label' => __('Altura del mapa (px)', 'flavor-chat-ia'),
                        'default' => 400,
                    ],
                ],
                'template' => 'landings/bicicletas-compartidas-mapa',
            ],
            'bicicletas_compartidas_como_funciona' => [
                'label' => __('Cómo Funciona Bicicletas', 'flavor-chat-ia'),
                'description' => __('Pasos para usar las bicicletas', 'flavor-chat-ia'),
                'category' => 'content',
                'icon' => 'dashicons-info',
                'fields' => [
                    'titulo' => [
                        'type' => 'text',
                        'label' => __('Título', 'flavor-chat-ia'),
                        'default' => __('¿Cómo funciona?', 'flavor-chat-ia'),
                    ],
                ],
                'template' => 'landings/bicicletas-compartidas-como-funciona',
            ],
            'bicicletas_compartidas_tarifas' => [
                'label' => __('Tarifas Bicicletas', 'flavor-chat-ia'),
                'description' => __('Tabla de tarifas del servicio', 'flavor-chat-ia'),
                'category' => 'content',
                'icon' => 'dashicons-money-alt',
                'fields' => [
                    'titulo' => [
                        'type' => 'text',
                        'label' => __('Título', 'flavor-chat-ia'),
                        'default' => __('Tarifas', 'flavor-chat-ia'),
                    ],
                ],
                'template' => 'landings/bicicletas-compartidas-tarifas',
            ],

            // PARKINGS
            'hero_parkings' => [
                'label' => __('Hero Parkings', 'flavor-chat-ia'),
                'description' => __('Sección hero de parkings compartidos', 'flavor-chat-ia'),
                'category' => 'hero',
                'icon' => 'dashicons-admin-multisite',
                'fields' => [
                    'titulo' => [
                        'type' => 'text',
                        'label' => __('Título', 'flavor-chat-ia'),
                        'default' => __('Parkings Compartidos', 'flavor-chat-ia'),
                    ],
                    'subtitulo' => [
                        'type' => 'textarea',
                        'label' => __('Subtítulo', 'flavor-chat-ia'),
                        'default' => __('Alquila o comparte tu plaza de parking', 'flavor-chat-ia'),
                    ],
                    'imagen_fondo' => [
                        'type' => 'image',
                        'label' => __('Imagen de fondo', 'flavor-chat-ia'),
                        'default' => '',
                    ],
                    'mostrar_buscador' => [
                        'type' => 'toggle',
                        'label' => __('Mostrar buscador', 'flavor-chat-ia'),
                        'default' => true,
                    ],
                ],
                'template' => 'landings/parkings-hero',
            ],
            'parkings_grid' => [
                'label' => __('Grid Parkings', 'flavor-chat-ia'),
                'description' => __('Listado de plazas disponibles', 'flavor-chat-ia'),
                'category' => 'listings',
                'icon' => 'dashicons-grid-view',
                'fields' => [
                    'titulo' => [
                        'type' => 'text',
                        'label' => __('Título', 'flavor-chat-ia'),
                        'default' => __('Plazas Disponibles', 'flavor-chat-ia'),
                    ],
                    'columnas' => [
                        'type' => 'select',
                        'label' => __('Columnas', 'flavor-chat-ia'),
                        'options' => [2, 3, 4],
                        'default' => 3,
                    ],
                    'limite' => [
                        'type' => 'number',
                        'label' => __('Número máximo', 'flavor-chat-ia'),
                        'default' => 9,
                    ],
                ],
                'template' => 'landings/parkings-grid',
            ],
            'cta_propietario' => [
                'label' => __('CTA Propietario Parking', 'flavor-chat-ia'),
                'description' => __('Llamada a la acción para propietarios', 'flavor-chat-ia'),
                'category' => 'cta',
                'icon' => 'dashicons-megaphone',
                'fields' => [
                    'titulo' => [
                        'type' => 'text',
                        'label' => __('Título', 'flavor-chat-ia'),
                        'default' => __('¿Tienes una Plaza Libre?', 'flavor-chat-ia'),
                    ],
                    'descripcion' => [
                        'type' => 'textarea',
                        'label' => __('Descripción', 'flavor-chat-ia'),
                        'default' => __('Genera ingresos extras compartiendo tu parking', 'flavor-chat-ia'),
                    ],
                    'boton_texto' => [
                        'type' => 'text',
                        'label' => __('Texto del botón', 'flavor-chat-ia'),
                        'default' => __('Publicar mi Plaza', 'flavor-chat-ia'),
                    ],
                    'boton_url' => [
                        'type' => 'url',
                        'label' => __('URL del botón', 'flavor-chat-ia'),
                        'default' => '#',
                    ],
                    'color_fondo' => [
                        'type' => 'color',
                        'label' => __('Color de fondo', 'flavor-chat-ia'),
                        'default' => '#10b981',
                    ],
                ],
                'template' => 'landings/cta-propietario',
            ],

            // CURSOS ADICIONALES
            'cursos_categorias' => [
                'label' => __('Categorías Cursos', 'flavor-chat-ia'),
                'description' => __('Grid de categorías de cursos', 'flavor-chat-ia'),
                'category' => 'listings',
                'icon' => 'dashicons-category',
                'fields' => [
                    'titulo' => [
                        'type' => 'text',
                        'label' => __('Título', 'flavor-chat-ia'),
                        'default' => __('Explora por Categoría', 'flavor-chat-ia'),
                    ],
                ],
                'template' => 'landings/cursos-categorias',
            ],
            'cursos_grid' => [
                'label' => __('Grid Cursos', 'flavor-chat-ia'),
                'description' => __('Listado de cursos disponibles', 'flavor-chat-ia'),
                'category' => 'listings',
                'icon' => 'dashicons-grid-view',
                'fields' => [
                    'titulo' => [
                        'type' => 'text',
                        'label' => __('Título', 'flavor-chat-ia'),
                        'default' => __('Cursos Disponibles', 'flavor-chat-ia'),
                    ],
                    'columnas' => [
                        'type' => 'select',
                        'label' => __('Columnas', 'flavor-chat-ia'),
                        'options' => [2, 3, 4],
                        'default' => 3,
                    ],
                    'limite' => [
                        'type' => 'number',
                        'label' => __('Número máximo', 'flavor-chat-ia'),
                        'default' => 6,
                    ],
                ],
                'template' => 'landings/cursos-grid',
            ],
            'cursos_cta_instructor' => [
                'label' => __('CTA Instructor Cursos', 'flavor-chat-ia'),
                'description' => __('Llamada a la acción para instructores', 'flavor-chat-ia'),
                'category' => 'cta',
                'icon' => 'dashicons-megaphone',
                'fields' => [
                    'titulo' => [
                        'type' => 'text',
                        'label' => __('Título', 'flavor-chat-ia'),
                        'default' => __('¿Quieres impartir un curso?', 'flavor-chat-ia'),
                    ],
                    'descripcion' => [
                        'type' => 'textarea',
                        'label' => __('Descripción', 'flavor-chat-ia'),
                        'default' => __('Comparte tu conocimiento con la comunidad', 'flavor-chat-ia'),
                    ],
                    'boton_texto' => [
                        'type' => 'text',
                        'label' => __('Texto del botón', 'flavor-chat-ia'),
                        'default' => __('Proponer Curso', 'flavor-chat-ia'),
                    ],
                ],
                'template' => 'landings/cursos-cta-instructor',
            ],

            // BIBLIOTECA ADICIONALES
            'biblioteca_buscador' => [
                'label' => __('Buscador Biblioteca', 'flavor-chat-ia'),
                'description' => __('Buscador de libros', 'flavor-chat-ia'),
                'category' => 'forms',
                'icon' => 'dashicons-search',
                'fields' => [
                    'titulo' => [
                        'type' => 'text',
                        'label' => __('Título', 'flavor-chat-ia'),
                        'default' => __('Buscar Libros', 'flavor-chat-ia'),
                    ],
                ],
                'template' => 'landings/biblioteca-buscador',
            ],
            'biblioteca_libros_grid' => [
                'label' => __('Grid Libros', 'flavor-chat-ia'),
                'description' => __('Listado de libros disponibles', 'flavor-chat-ia'),
                'category' => 'listings',
                'icon' => 'dashicons-book',
                'fields' => [
                    'titulo' => [
                        'type' => 'text',
                        'label' => __('Título', 'flavor-chat-ia'),
                        'default' => __('Libros Disponibles', 'flavor-chat-ia'),
                    ],
                    'columnas' => [
                        'type' => 'select',
                        'label' => __('Columnas', 'flavor-chat-ia'),
                        'options' => [2, 3, 4],
                        'default' => 4,
                    ],
                    'limite' => [
                        'type' => 'number',
                        'label' => __('Número máximo', 'flavor-chat-ia'),
                        'default' => 8,
                    ],
                ],
                'template' => 'landings/biblioteca-libros-grid',
            ],
            'biblioteca_como_funciona' => [
                'label' => __('Cómo Funciona Biblioteca', 'flavor-chat-ia'),
                'description' => __('Pasos para usar la biblioteca', 'flavor-chat-ia'),
                'category' => 'content',
                'icon' => 'dashicons-info',
                'fields' => [
                    'titulo' => [
                        'type' => 'text',
                        'label' => __('Título', 'flavor-chat-ia'),
                        'default' => __('¿Cómo funciona?', 'flavor-chat-ia'),
                    ],
                ],
                'template' => 'landings/biblioteca-como-funciona',
            ],
            'biblioteca_cta_donar' => [
                'label' => __('CTA Donar Libro', 'flavor-chat-ia'),
                'description' => __('Llamada a la acción para donar libros', 'flavor-chat-ia'),
                'category' => 'cta',
                'icon' => 'dashicons-heart',
                'fields' => [
                    'titulo' => [
                        'type' => 'text',
                        'label' => __('Título', 'flavor-chat-ia'),
                        'default' => __('¿Tienes libros que ya no lees?', 'flavor-chat-ia'),
                    ],
                    'descripcion' => [
                        'type' => 'textarea',
                        'label' => __('Descripción', 'flavor-chat-ia'),
                        'default' => __('Dónalos a la biblioteca', 'flavor-chat-ia'),
                    ],
                    'boton_texto' => [
                        'type' => 'text',
                        'label' => __('Texto del botón', 'flavor-chat-ia'),
                        'default' => __('Donar Libro', 'flavor-chat-ia'),
                    ],
                ],
                'template' => 'landings/biblioteca-cta-donar',
            ],

            // TALLERES
            'hero_talleres' => [
                'label' => __('Hero Talleres', 'flavor-chat-ia'),
                'description' => __('Sección hero de talleres prácticos', 'flavor-chat-ia'),
                'category' => 'hero',
                'icon' => 'dashicons-hammer',
                'fields' => [
                    'titulo' => [
                        'type' => 'text',
                        'label' => __('Título', 'flavor-chat-ia'),
                        'default' => __('Talleres Prácticos', 'flavor-chat-ia'),
                    ],
                    'subtitulo' => [
                        'type' => 'textarea',
                        'label' => __('Subtítulo', 'flavor-chat-ia'),
                        'default' => __('Aprende nuevas habilidades', 'flavor-chat-ia'),
                    ],
                    'imagen_fondo' => [
                        'type' => 'image',
                        'label' => __('Imagen de fondo', 'flavor-chat-ia'),
                        'default' => '',
                    ],
                    'mostrar_buscador' => [
                        'type' => 'toggle',
                        'label' => __('Mostrar buscador', 'flavor-chat-ia'),
                        'default' => true,
                    ],
                ],
                'template' => 'landings/talleres-hero',
            ],
            'talleres_grid' => [
                'label' => __('Grid Talleres', 'flavor-chat-ia'),
                'description' => __('Listado de talleres', 'flavor-chat-ia'),
                'category' => 'listings',
                'icon' => 'dashicons-grid-view',
                'fields' => [
                    'titulo' => [
                        'type' => 'text',
                        'label' => __('Título', 'flavor-chat-ia'),
                        'default' => __('Próximos Talleres', 'flavor-chat-ia'),
                    ],
                    'columnas' => [
                        'type' => 'select',
                        'label' => __('Columnas', 'flavor-chat-ia'),
                        'options' => [2, 3, 4],
                        'default' => 3,
                    ],
                    'limite' => [
                        'type' => 'number',
                        'label' => __('Número máximo', 'flavor-chat-ia'),
                        'default' => 9,
                    ],
                ],
                'template' => 'landings/talleres-grid',
            ],
            'categorias_talleres' => [
                'label' => __('Categorías Talleres', 'flavor-chat-ia'),
                'description' => __('Grid de categorías de talleres', 'flavor-chat-ia'),
                'category' => 'listings',
                'icon' => 'dashicons-category',
                'fields' => [
                    'titulo' => [
                        'type' => 'text',
                        'label' => __('Título', 'flavor-chat-ia'),
                        'default' => __('Explora por Categoría', 'flavor-chat-ia'),
                    ],
                    'estilo' => [
                        'type' => 'select',
                        'label' => __('Estilo', 'flavor-chat-ia'),
                        'options' => ['grid', 'list', 'carousel'],
                        'default' => 'grid',
                    ],
                ],
                'template' => 'landings/talleres-categorias',
            ],

            // HUERTOS URBANOS ADICIONALES
            'huertos_urbanos_mapa' => [
                'label' => __('Mapa Huertos', 'flavor-chat-ia'),
                'description' => __('Mapa de ubicación de huertos', 'flavor-chat-ia'),
                'category' => 'content',
                'icon' => 'dashicons-location',
                'fields' => [
                    'titulo' => [
                        'type' => 'text',
                        'label' => __('Título', 'flavor-chat-ia'),
                        'default' => __('Encuentra tu Huerto', 'flavor-chat-ia'),
                    ],
                    'altura_mapa' => [
                        'type' => 'number',
                        'label' => __('Altura del mapa (px)', 'flavor-chat-ia'),
                        'default' => 400,
                    ],
                ],
                'template' => 'landings/huertos-urbanos-mapa',
            ],
            'huertos_urbanos_parcelas' => [
                'label' => __('Parcelas Huertos', 'flavor-chat-ia'),
                'description' => __('Listado de parcelas disponibles', 'flavor-chat-ia'),
                'category' => 'listings',
                'icon' => 'dashicons-grid-view',
                'fields' => [
                    'titulo' => [
                        'type' => 'text',
                        'label' => __('Título', 'flavor-chat-ia'),
                        'default' => __('Parcelas Disponibles', 'flavor-chat-ia'),
                    ],
                    'columnas' => [
                        'type' => 'select',
                        'label' => __('Columnas', 'flavor-chat-ia'),
                        'options' => [2, 3, 4],
                        'default' => 3,
                    ],
                    'limite' => [
                        'type' => 'number',
                        'label' => __('Número máximo', 'flavor-chat-ia'),
                        'default' => 6,
                    ],
                ],
                'template' => 'landings/huertos-urbanos-parcelas',
            ],
            'huertos_urbanos_beneficios' => [
                'label' => __('Beneficios Huertos', 'flavor-chat-ia'),
                'description' => __('Lista de beneficios de tener un huerto', 'flavor-chat-ia'),
                'category' => 'content',
                'icon' => 'dashicons-heart',
                'fields' => [
                    'titulo' => [
                        'type' => 'text',
                        'label' => __('Título', 'flavor-chat-ia'),
                        'default' => __('Beneficios de Tener un Huerto', 'flavor-chat-ia'),
                    ],
                ],
                'template' => 'landings/huertos-urbanos-beneficios',
            ],
            'huertos_urbanos_cta' => [
                'label' => __('CTA Huertos', 'flavor-chat-ia'),
                'description' => __('Llamada a la acción para solicitar parcela', 'flavor-chat-ia'),
                'category' => 'cta',
                'icon' => 'dashicons-megaphone',
                'fields' => [
                    'titulo' => [
                        'type' => 'text',
                        'label' => __('Título', 'flavor-chat-ia'),
                        'default' => __('¿Quieres tu propia parcela?', 'flavor-chat-ia'),
                    ],
                    'descripcion' => [
                        'type' => 'textarea',
                        'label' => __('Descripción', 'flavor-chat-ia'),
                        'default' => __('Solicita tu huerto y empieza a cultivar', 'flavor-chat-ia'),
                    ],
                    'boton_texto' => [
                        'type' => 'text',
                        'label' => __('Texto del botón', 'flavor-chat-ia'),
                        'default' => __('Solicitar Parcela', 'flavor-chat-ia'),
                    ],
                ],
                'template' => 'landings/huertos-urbanos-cta',
            ],

            // RECICLAJE ADICIONALES
            'reciclaje_puntos_mapa' => [
                'label' => __('Mapa Puntos Reciclaje', 'flavor-chat-ia'),
                'description' => __('Mapa de puntos de reciclaje', 'flavor-chat-ia'),
                'category' => 'content',
                'icon' => 'dashicons-location',
                'fields' => [
                    'titulo' => [
                        'type' => 'text',
                        'label' => __('Título', 'flavor-chat-ia'),
                        'default' => __('Puntos de Reciclaje', 'flavor-chat-ia'),
                    ],
                    'altura_mapa' => [
                        'type' => 'number',
                        'label' => __('Altura del mapa (px)', 'flavor-chat-ia'),
                        'default' => 400,
                    ],
                ],
                'template' => 'landings/reciclaje-puntos-mapa',
            ],
            'reciclaje_guia' => [
                'label' => __('Guía Reciclaje', 'flavor-chat-ia'),
                'description' => __('Guía de cómo reciclar', 'flavor-chat-ia'),
                'category' => 'content',
                'icon' => 'dashicons-book-alt',
                'fields' => [
                    'titulo' => [
                        'type' => 'text',
                        'label' => __('Título', 'flavor-chat-ia'),
                        'default' => __('Guía de Reciclaje', 'flavor-chat-ia'),
                    ],
                ],
                'template' => 'landings/reciclaje-guia',
            ],
            'reciclaje_estadisticas' => [
                'label' => __('Estadísticas Reciclaje', 'flavor-chat-ia'),
                'description' => __('Impacto del reciclaje', 'flavor-chat-ia'),
                'category' => 'content',
                'icon' => 'dashicons-chart-bar',
                'fields' => [
                    'titulo' => [
                        'type' => 'text',
                        'label' => __('Título', 'flavor-chat-ia'),
                        'default' => __('Nuestro Impacto', 'flavor-chat-ia'),
                    ],
                ],
                'template' => 'landings/reciclaje-estadisticas',
            ],
            'reciclaje_consejos' => [
                'label' => __('Consejos Reciclaje', 'flavor-chat-ia'),
                'description' => __('Consejos para reciclar mejor', 'flavor-chat-ia'),
                'category' => 'content',
                'icon' => 'dashicons-lightbulb',
                'fields' => [
                    'titulo' => [
                        'type' => 'text',
                        'label' => __('Título', 'flavor-chat-ia'),
                        'default' => __('Consejos para Reciclar Mejor', 'flavor-chat-ia'),
                    ],
                ],
                'template' => 'landings/reciclaje-consejos',
            ],

            // COMPOSTAJE
            'hero_compostaje' => [
                'label' => __('Hero Compostaje', 'flavor-chat-ia'),
                'description' => __('Sección hero de compostaje', 'flavor-chat-ia'),
                'category' => 'hero',
                'icon' => 'dashicons-carrot',
                'fields' => [
                    'titulo' => [
                        'type' => 'text',
                        'label' => __('Título', 'flavor-chat-ia'),
                        'default' => __('Compostaje Comunitario', 'flavor-chat-ia'),
                    ],
                    'subtitulo' => [
                        'type' => 'textarea',
                        'label' => __('Subtítulo', 'flavor-chat-ia'),
                        'default' => __('Convierte residuos en abono natural', 'flavor-chat-ia'),
                    ],
                    'imagen_fondo' => [
                        'type' => 'image',
                        'label' => __('Imagen de fondo', 'flavor-chat-ia'),
                        'default' => '',
                    ],
                    'mostrar_impacto' => [
                        'type' => 'toggle',
                        'label' => __('Mostrar impacto', 'flavor-chat-ia'),
                        'default' => true,
                    ],
                ],
                'template' => 'landings/compostaje-hero',
            ],
            'mapa_composteras' => [
                'label' => __('Mapa Composteras', 'flavor-chat-ia'),
                'description' => __('Mapa de ubicación de composteras', 'flavor-chat-ia'),
                'category' => 'content',
                'icon' => 'dashicons-location',
                'fields' => [
                    'titulo' => [
                        'type' => 'text',
                        'label' => __('Título', 'flavor-chat-ia'),
                        'default' => __('Encuentra tu Compostera', 'flavor-chat-ia'),
                    ],
                    'altura_mapa' => [
                        'type' => 'number',
                        'label' => __('Altura del mapa (px)', 'flavor-chat-ia'),
                        'default' => 500,
                    ],
                    'mostrar_estado' => [
                        'type' => 'toggle',
                        'label' => __('Mostrar estado', 'flavor-chat-ia'),
                        'default' => true,
                    ],
                ],
                'template' => 'landings/compostaje-mapa',
            ],
            'guia_compostaje' => [
                'label' => __('Guía Compostaje', 'flavor-chat-ia'),
                'description' => __('Qué compostar y qué no', 'flavor-chat-ia'),
                'category' => 'content',
                'icon' => 'dashicons-book-alt',
                'fields' => [
                    'titulo' => [
                        'type' => 'text',
                        'label' => __('Título', 'flavor-chat-ia'),
                        'default' => __('Qué Compostar', 'flavor-chat-ia'),
                    ],
                    'estilo' => [
                        'type' => 'select',
                        'label' => __('Estilo', 'flavor-chat-ia'),
                        'options' => ['tarjetas', 'lista', 'iconos'],
                        'default' => 'tarjetas',
                    ],
                ],
                'template' => 'landings/compostaje-guia',
            ],
            'proceso_compostaje' => [
                'label' => __('Proceso Compostaje', 'flavor-chat-ia'),
                'description' => __('Fases del proceso de compostaje', 'flavor-chat-ia'),
                'category' => 'content',
                'icon' => 'dashicons-image-rotate',
                'fields' => [
                    'titulo' => [
                        'type' => 'text',
                        'label' => __('Título', 'flavor-chat-ia'),
                        'default' => __('Cómo Funciona', 'flavor-chat-ia'),
                    ],
                    'mostrar_fases' => [
                        'type' => 'toggle',
                        'label' => __('Mostrar fases', 'flavor-chat-ia'),
                        'default' => true,
                    ],
                ],
                'template' => 'landings/compostaje-proceso',
            ],

            // ESPACIOS COMUNES ADICIONALES
            'espacios_comunes_listado' => [
                'label' => __('Listado Espacios', 'flavor-chat-ia'),
                'description' => __('Grid de espacios disponibles', 'flavor-chat-ia'),
                'category' => 'listings',
                'icon' => 'dashicons-grid-view',
                'fields' => [
                    'titulo' => [
                        'type' => 'text',
                        'label' => __('Título', 'flavor-chat-ia'),
                        'default' => __('Espacios Disponibles', 'flavor-chat-ia'),
                    ],
                    'columnas' => [
                        'type' => 'select',
                        'label' => __('Columnas', 'flavor-chat-ia'),
                        'options' => [2, 3, 4],
                        'default' => 3,
                    ],
                    'limite' => [
                        'type' => 'number',
                        'label' => __('Número máximo', 'flavor-chat-ia'),
                        'default' => 6,
                    ],
                ],
                'template' => 'landings/espacios-comunes-listado',
            ],
            'espacios_comunes_calendario' => [
                'label' => __('Calendario Espacios', 'flavor-chat-ia'),
                'description' => __('Calendario de disponibilidad', 'flavor-chat-ia'),
                'category' => 'content',
                'icon' => 'dashicons-calendar-alt',
                'fields' => [
                    'titulo' => [
                        'type' => 'text',
                        'label' => __('Título', 'flavor-chat-ia'),
                        'default' => __('Disponibilidad', 'flavor-chat-ia'),
                    ],
                ],
                'template' => 'landings/espacios-comunes-calendario',
            ],
            'espacios_comunes_como_reservar' => [
                'label' => __('Cómo Reservar Espacios', 'flavor-chat-ia'),
                'description' => __('Pasos para reservar un espacio', 'flavor-chat-ia'),
                'category' => 'content',
                'icon' => 'dashicons-info',
                'fields' => [
                    'titulo' => [
                        'type' => 'text',
                        'label' => __('Título', 'flavor-chat-ia'),
                        'default' => __('¿Cómo reservar?', 'flavor-chat-ia'),
                    ],
                ],
                'template' => 'landings/espacios-comunes-como-reservar',
            ],

            // AYUDA VECINAL ADICIONALES
            'ayuda_vecinal_solicitudes' => [
                'label' => __('Solicitudes Ayuda', 'flavor-chat-ia'),
                'description' => __('Listado de solicitudes de ayuda', 'flavor-chat-ia'),
                'category' => 'listings',
                'icon' => 'dashicons-sos',
                'fields' => [
                    'titulo' => [
                        'type' => 'text',
                        'label' => __('Título', 'flavor-chat-ia'),
                        'default' => __('Solicitudes de Ayuda', 'flavor-chat-ia'),
                    ],
                    'columnas' => [
                        'type' => 'select',
                        'label' => __('Columnas', 'flavor-chat-ia'),
                        'options' => [2, 3],
                        'default' => 2,
                    ],
                    'limite' => [
                        'type' => 'number',
                        'label' => __('Número máximo', 'flavor-chat-ia'),
                        'default' => 6,
                    ],
                ],
                'template' => 'landings/ayuda-vecinal-solicitudes',
            ],
            'ayuda_vecinal_ofertas' => [
                'label' => __('Ofertas Ayuda', 'flavor-chat-ia'),
                'description' => __('Vecinos que ofrecen ayuda', 'flavor-chat-ia'),
                'category' => 'listings',
                'icon' => 'dashicons-heart',
                'fields' => [
                    'titulo' => [
                        'type' => 'text',
                        'label' => __('Título', 'flavor-chat-ia'),
                        'default' => __('Vecinos que Ofrecen Ayuda', 'flavor-chat-ia'),
                    ],
                    'columnas' => [
                        'type' => 'select',
                        'label' => __('Columnas', 'flavor-chat-ia'),
                        'options' => [2, 3, 4],
                        'default' => 3,
                    ],
                    'limite' => [
                        'type' => 'number',
                        'label' => __('Número máximo', 'flavor-chat-ia'),
                        'default' => 6,
                    ],
                ],
                'template' => 'landings/ayuda-vecinal-ofertas',
            ],
            'ayuda_vecinal_categorias' => [
                'label' => __('Categorías Ayuda', 'flavor-chat-ia'),
                'description' => __('Tipos de ayuda disponibles', 'flavor-chat-ia'),
                'category' => 'listings',
                'icon' => 'dashicons-category',
                'fields' => [
                    'titulo' => [
                        'type' => 'text',
                        'label' => __('Título', 'flavor-chat-ia'),
                        'default' => __('Tipos de Ayuda', 'flavor-chat-ia'),
                    ],
                ],
                'template' => 'landings/ayuda-vecinal-categorias',
            ],
            'ayuda_vecinal_cta' => [
                'label' => __('CTA Ayuda Vecinal', 'flavor-chat-ia'),
                'description' => __('Llamada a la acción para ayuda vecinal', 'flavor-chat-ia'),
                'category' => 'cta',
                'icon' => 'dashicons-megaphone',
                'fields' => [
                    'titulo' => [
                        'type' => 'text',
                        'label' => __('Título', 'flavor-chat-ia'),
                        'default' => __('¿Necesitas ayuda o quieres ayudar?', 'flavor-chat-ia'),
                    ],
                    'boton_texto' => [
                        'type' => 'text',
                        'label' => __('Texto del botón', 'flavor-chat-ia'),
                        'default' => __('Publicar Solicitud', 'flavor-chat-ia'),
                    ],
                ],
                'template' => 'landings/ayuda-vecinal-cta',
            ],

            // GRUPOS CONSUMO ADICIONALES
            'grupos_consumo_productores' => [
                'label' => __('Productores', 'flavor-chat-ia'),
                'description' => __('Listado de productores locales', 'flavor-chat-ia'),
                'category' => 'listings',
                'icon' => 'dashicons-groups',
                'fields' => [
                    'titulo' => [
                        'type' => 'text',
                        'label' => __('Título', 'flavor-chat-ia'),
                        'default' => __('Nuestros Productores', 'flavor-chat-ia'),
                    ],
                    'limite' => [
                        'type' => 'number',
                        'label' => __('Número máximo', 'flavor-chat-ia'),
                        'default' => 6,
                    ],
                    'mostrar_ubicacion' => [
                        'type' => 'toggle',
                        'label' => __('Mostrar ubicación', 'flavor-chat-ia'),
                        'default' => true,
                    ],
                ],
                'template' => 'landings/grupos-consumo-productores',
            ],
            'grupos_consumo_como_funciona' => [
                'label' => __('Cómo Funciona Grupos', 'flavor-chat-ia'),
                'description' => __('Pasos para participar', 'flavor-chat-ia'),
                'category' => 'content',
                'icon' => 'dashicons-info',
                'fields' => [
                    'titulo' => [
                        'type' => 'text',
                        'label' => __('Título', 'flavor-chat-ia'),
                        'default' => __('¿Cómo Funciona?', 'flavor-chat-ia'),
                    ],
                ],
                'template' => 'landings/grupos-consumo-como-funciona',
            ],
            'grupos_consumo_proximo_pedido' => [
                'label' => __('Próximo Pedido', 'flavor-chat-ia'),
                'description' => __('Información del próximo pedido', 'flavor-chat-ia'),
                'category' => 'content',
                'icon' => 'dashicons-calendar',
                'fields' => [
                    'titulo' => [
                        'type' => 'text',
                        'label' => __('Título', 'flavor-chat-ia'),
                        'default' => __('Próximo Pedido', 'flavor-chat-ia'),
                    ],
                    'mostrar_cuenta_atras' => [
                        'type' => 'toggle',
                        'label' => __('Mostrar cuenta atrás', 'flavor-chat-ia'),
                        'default' => true,
                    ],
                ],
                'template' => 'landings/grupos-consumo-proximo-pedido',
            ],
            'grupos_consumo_cta_unirse' => [
                'label' => __('CTA Unirse Grupo', 'flavor-chat-ia'),
                'description' => __('Llamada a la acción para unirse', 'flavor-chat-ia'),
                'category' => 'cta',
                'icon' => 'dashicons-megaphone',
                'fields' => [
                    'titulo' => [
                        'type' => 'text',
                        'label' => __('Título', 'flavor-chat-ia'),
                        'default' => __('¿Quieres unirte?', 'flavor-chat-ia'),
                    ],
                    'subtitulo' => [
                        'type' => 'textarea',
                        'label' => __('Subtítulo', 'flavor-chat-ia'),
                        'default' => __('Forma parte de un grupo de consumo', 'flavor-chat-ia'),
                    ],
                    'texto_boton' => [
                        'type' => 'text',
                        'label' => __('Texto del botón', 'flavor-chat-ia'),
                        'default' => __('Unirse a un Grupo', 'flavor-chat-ia'),
                    ],
                    'url_boton' => [
                        'type' => 'url',
                        'label' => __('URL del botón', 'flavor-chat-ia'),
                        'default' => '#',
                    ],
                ],
                'template' => 'landings/grupos-consumo-cta-unirse',
            ],

            // BANCO TIEMPO ADICIONALES
            'banco_tiempo_categorias' => [
                'label' => __('Categorías Banco Tiempo', 'flavor-chat-ia'),
                'description' => __('Categorías de servicios', 'flavor-chat-ia'),
                'category' => 'listings',
                'icon' => 'dashicons-category',
                'fields' => [
                    'titulo' => [
                        'type' => 'text',
                        'label' => __('Título', 'flavor-chat-ia'),
                        'default' => __('Categorías de Servicios', 'flavor-chat-ia'),
                    ],
                ],
                'template' => 'landings/banco-tiempo-categorias',
            ],
            'banco_tiempo_como_funciona' => [
                'label' => __('Cómo Funciona Banco Tiempo', 'flavor-chat-ia'),
                'description' => __('Pasos para usar el banco de tiempo', 'flavor-chat-ia'),
                'category' => 'content',
                'icon' => 'dashicons-info',
                'fields' => [
                    'titulo' => [
                        'type' => 'text',
                        'label' => __('Título', 'flavor-chat-ia'),
                        'default' => __('¿Cómo Funciona?', 'flavor-chat-ia'),
                    ],
                ],
                'template' => 'landings/banco-tiempo-como-funciona',
            ],
            'banco_tiempo_estadisticas' => [
                'label' => __('Estadísticas Banco Tiempo', 'flavor-chat-ia'),
                'description' => __('Estadísticas de la comunidad', 'flavor-chat-ia'),
                'category' => 'content',
                'icon' => 'dashicons-chart-bar',
                'fields' => [
                    'titulo' => [
                        'type' => 'text',
                        'label' => __('Título', 'flavor-chat-ia'),
                        'default' => __('Nuestra Comunidad', 'flavor-chat-ia'),
                    ],
                    'mostrar_usuarios' => [
                        'type' => 'toggle',
                        'label' => __('Mostrar usuarios', 'flavor-chat-ia'),
                        'default' => true,
                    ],
                    'mostrar_horas_intercambiadas' => [
                        'type' => 'toggle',
                        'label' => __('Mostrar horas', 'flavor-chat-ia'),
                        'default' => true,
                    ],
                    'mostrar_servicios' => [
                        'type' => 'toggle',
                        'label' => __('Mostrar servicios', 'flavor-chat-ia'),
                        'default' => true,
                    ],
                ],
                'template' => 'landings/banco-tiempo-estadisticas',
            ],
            'banco_tiempo_cta_unirse' => [
                'label' => __('CTA Unirse Banco Tiempo', 'flavor-chat-ia'),
                'description' => __('Llamada a la acción para unirse', 'flavor-chat-ia'),
                'category' => 'cta',
                'icon' => 'dashicons-megaphone',
                'fields' => [
                    'titulo' => [
                        'type' => 'text',
                        'label' => __('Título', 'flavor-chat-ia'),
                        'default' => __('¿Tienes habilidades que compartir?', 'flavor-chat-ia'),
                    ],
                    'subtitulo' => [
                        'type' => 'textarea',
                        'label' => __('Subtítulo', 'flavor-chat-ia'),
                        'default' => __('Únete al banco de tiempo', 'flavor-chat-ia'),
                    ],
                    'texto_boton' => [
                        'type' => 'text',
                        'label' => __('Texto del botón', 'flavor-chat-ia'),
                        'default' => __('Registrarme', 'flavor-chat-ia'),
                    ],
                    'url_boton' => [
                        'type' => 'url',
                        'label' => __('URL del botón', 'flavor-chat-ia'),
                        'default' => '#',
                    ],
                ],
                'template' => 'landings/banco-tiempo-cta-unirse',
            ],

            // COMUNIDADES ADICIONALES
            'comunidades_mapa' => [
                'label' => __('Mapa Comunidades', 'flavor-chat-ia'),
                'description' => __('Mapa de comunidades', 'flavor-chat-ia'),
                'category' => 'content',
                'icon' => 'dashicons-location',
                'fields' => [
                    'titulo' => [
                        'type' => 'text',
                        'label' => __('Título', 'flavor-chat-ia'),
                        'default' => __('Encuentra tu Comunidad', 'flavor-chat-ia'),
                    ],
                    'altura_mapa' => [
                        'type' => 'number',
                        'label' => __('Altura del mapa (px)', 'flavor-chat-ia'),
                        'default' => 400,
                    ],
                    'mostrar_mi_ubicacion' => [
                        'type' => 'toggle',
                        'label' => __('Mostrar mi ubicación', 'flavor-chat-ia'),
                        'default' => true,
                    ],
                ],
                'template' => 'landings/comunidades-mapa',
            ],
            'comunidades_actividad_reciente' => [
                'label' => __('Actividad Reciente', 'flavor-chat-ia'),
                'description' => __('Actividad reciente de las comunidades', 'flavor-chat-ia'),
                'category' => 'content',
                'icon' => 'dashicons-clock',
                'fields' => [
                    'titulo' => [
                        'type' => 'text',
                        'label' => __('Título', 'flavor-chat-ia'),
                        'default' => __('Actividad Reciente', 'flavor-chat-ia'),
                    ],
                    'limite' => [
                        'type' => 'number',
                        'label' => __('Número máximo', 'flavor-chat-ia'),
                        'default' => 10,
                    ],
                ],
                'template' => 'landings/comunidades-actividad-reciente',
            ],
            'comunidades_estadisticas' => [
                'label' => __('Estadísticas Comunidades', 'flavor-chat-ia'),
                'description' => __('Estadísticas de las comunidades', 'flavor-chat-ia'),
                'category' => 'content',
                'icon' => 'dashicons-chart-bar',
                'fields' => [
                    'titulo' => [
                        'type' => 'text',
                        'label' => __('Título', 'flavor-chat-ia'),
                        'default' => __('En Números', 'flavor-chat-ia'),
                    ],
                    'mostrar_comunidades' => [
                        'type' => 'toggle',
                        'label' => __('Mostrar comunidades', 'flavor-chat-ia'),
                        'default' => true,
                    ],
                    'mostrar_vecinos' => [
                        'type' => 'toggle',
                        'label' => __('Mostrar vecinos', 'flavor-chat-ia'),
                        'default' => true,
                    ],
                    'mostrar_eventos' => [
                        'type' => 'toggle',
                        'label' => __('Mostrar eventos', 'flavor-chat-ia'),
                        'default' => true,
                    ],
                ],
                'template' => 'landings/comunidades-estadisticas',
            ],
            'comunidades_cta_crear' => [
                'label' => __('CTA Crear Comunidad', 'flavor-chat-ia'),
                'description' => __('Llamada a la acción para crear comunidad', 'flavor-chat-ia'),
                'category' => 'cta',
                'icon' => 'dashicons-megaphone',
                'fields' => [
                    'titulo' => [
                        'type' => 'text',
                        'label' => __('Título', 'flavor-chat-ia'),
                        'default' => __('¿No encuentras tu comunidad?', 'flavor-chat-ia'),
                    ],
                    'subtitulo' => [
                        'type' => 'textarea',
                        'label' => __('Subtítulo', 'flavor-chat-ia'),
                        'default' => __('Crea una nueva comunidad', 'flavor-chat-ia'),
                    ],
                    'texto_boton' => [
                        'type' => 'text',
                        'label' => __('Texto del botón', 'flavor-chat-ia'),
                        'default' => __('Crear Comunidad', 'flavor-chat-ia'),
                    ],
                    'url_boton' => [
                        'type' => 'url',
                        'label' => __('URL del botón', 'flavor-chat-ia'),
                        'default' => '#',
                    ],
                ],
                'template' => 'landings/comunidades-cta-crear',
            ],

            // INCIDENCIAS ADICIONALES
            'incidencias_mapa' => [
                'label' => __('Mapa Incidencias', 'flavor-chat-ia'),
                'description' => __('Mapa de incidencias reportadas', 'flavor-chat-ia'),
                'category' => 'content',
                'icon' => 'dashicons-location',
                'fields' => [
                    'titulo' => [
                        'type' => 'text',
                        'label' => __('Título', 'flavor-chat-ia'),
                        'default' => __('Mapa de Incidencias', 'flavor-chat-ia'),
                    ],
                    'descripcion' => [
                        'type' => 'textarea',
                        'label' => __('Descripción', 'flavor-chat-ia'),
                        'default' => __('Visualiza las incidencias reportadas', 'flavor-chat-ia'),
                    ],
                    'zoom_inicial' => [
                        'type' => 'number',
                        'label' => __('Zoom inicial', 'flavor-chat-ia'),
                        'default' => 14,
                    ],
                    'mostrar_filtros' => [
                        'type' => 'toggle',
                        'label' => __('Mostrar filtros', 'flavor-chat-ia'),
                        'default' => true,
                    ],
                ],
                'template' => 'landings/incidencias-mapa',
            ],
            'incidencias_categorias' => [
                'label' => __('Categorías Incidencias', 'flavor-chat-ia'),
                'description' => __('Tipos de incidencias', 'flavor-chat-ia'),
                'category' => 'listings',
                'icon' => 'dashicons-category',
                'fields' => [
                    'titulo' => [
                        'type' => 'text',
                        'label' => __('Título', 'flavor-chat-ia'),
                        'default' => __('Tipos de Incidencias', 'flavor-chat-ia'),
                    ],
                ],
                'template' => 'landings/incidencias-categorias',
            ],
            'incidencias_grid' => [
                'label' => __('Grid Incidencias', 'flavor-chat-ia'),
                'description' => __('Listado de incidencias', 'flavor-chat-ia'),
                'category' => 'listings',
                'icon' => 'dashicons-grid-view',
                'fields' => [
                    'titulo' => [
                        'type' => 'text',
                        'label' => __('Título', 'flavor-chat-ia'),
                        'default' => __('Últimas Incidencias', 'flavor-chat-ia'),
                    ],
                    'limite' => [
                        'type' => 'number',
                        'label' => __('Número máximo', 'flavor-chat-ia'),
                        'default' => 6,
                    ],
                    'mostrar_estado' => [
                        'type' => 'toggle',
                        'label' => __('Mostrar estado', 'flavor-chat-ia'),
                        'default' => true,
                    ],
                ],
                'template' => 'landings/incidencias-grid',
            ],
            'incidencias_estadisticas' => [
                'label' => __('Estadísticas Incidencias', 'flavor-chat-ia'),
                'description' => __('Estadísticas de incidencias', 'flavor-chat-ia'),
                'category' => 'content',
                'icon' => 'dashicons-chart-bar',
                'fields' => [
                    'titulo' => [
                        'type' => 'text',
                        'label' => __('Título', 'flavor-chat-ia'),
                        'default' => __('Estadísticas', 'flavor-chat-ia'),
                    ],
                    'mostrar_resueltas' => [
                        'type' => 'toggle',
                        'label' => __('Mostrar resueltas', 'flavor-chat-ia'),
                        'default' => true,
                    ],
                    'mostrar_pendientes' => [
                        'type' => 'toggle',
                        'label' => __('Mostrar pendientes', 'flavor-chat-ia'),
                        'default' => true,
                    ],
                    'mostrar_tiempo_medio' => [
                        'type' => 'toggle',
                        'label' => __('Mostrar tiempo medio', 'flavor-chat-ia'),
                        'default' => true,
                    ],
                ],
                'template' => 'landings/incidencias-estadisticas',
            ],
            'incidencias_cta_reportar' => [
                'label' => __('CTA Reportar Incidencia', 'flavor-chat-ia'),
                'description' => __('Llamada a la acción para reportar', 'flavor-chat-ia'),
                'category' => 'cta',
                'icon' => 'dashicons-megaphone',
                'fields' => [
                    'titulo' => [
                        'type' => 'text',
                        'label' => __('Título', 'flavor-chat-ia'),
                        'default' => __('¿Has visto algún problema?', 'flavor-chat-ia'),
                    ],
                    'subtitulo' => [
                        'type' => 'textarea',
                        'label' => __('Subtítulo', 'flavor-chat-ia'),
                        'default' => __('Reporta incidencias en tu barrio', 'flavor-chat-ia'),
                    ],
                    'texto_boton' => [
                        'type' => 'text',
                        'label' => __('Texto del botón', 'flavor-chat-ia'),
                        'default' => __('Reportar Incidencia', 'flavor-chat-ia'),
                    ],
                    'url_boton' => [
                        'type' => 'url',
                        'label' => __('URL del botón', 'flavor-chat-ia'),
                        'default' => '#',
                    ],
                ],
                'template' => 'landings/incidencias-cta-reportar',
            ],

            // TIENDA LOCAL ADICIONALES
            'tienda_local_buscador' => [
                'label' => __('Buscador Tiendas', 'flavor-chat-ia'),
                'description' => __('Buscador de comercios', 'flavor-chat-ia'),
                'category' => 'forms',
                'icon' => 'dashicons-search',
                'fields' => [
                    'placeholder' => [
                        'type' => 'text',
                        'label' => __('Placeholder', 'flavor-chat-ia'),
                        'default' => __('Busca comercios, productos...', 'flavor-chat-ia'),
                    ],
                    'mostrar_filtros' => [
                        'type' => 'toggle',
                        'label' => __('Mostrar filtros', 'flavor-chat-ia'),
                        'default' => true,
                    ],
                ],
                'template' => 'landings/tienda-local-buscador',
            ],
            'tienda_local_categorias' => [
                'label' => __('Categorías Tiendas', 'flavor-chat-ia'),
                'description' => __('Categorías de comercios', 'flavor-chat-ia'),
                'category' => 'listings',
                'icon' => 'dashicons-category',
                'fields' => [
                    'titulo' => [
                        'type' => 'text',
                        'label' => __('Título', 'flavor-chat-ia'),
                        'default' => __('Explora por Categoría', 'flavor-chat-ia'),
                    ],
                    'mostrar_iconos' => [
                        'type' => 'toggle',
                        'label' => __('Mostrar iconos', 'flavor-chat-ia'),
                        'default' => true,
                    ],
                ],
                'template' => 'landings/tienda-local-categorias',
            ],
            'tienda_local_destacados' => [
                'label' => __('Comercios Destacados', 'flavor-chat-ia'),
                'description' => __('Listado de comercios destacados', 'flavor-chat-ia'),
                'category' => 'listings',
                'icon' => 'dashicons-star-filled',
                'fields' => [
                    'titulo' => [
                        'type' => 'text',
                        'label' => __('Título', 'flavor-chat-ia'),
                        'default' => __('Comercios Destacados', 'flavor-chat-ia'),
                    ],
                    'limite' => [
                        'type' => 'number',
                        'label' => __('Número máximo', 'flavor-chat-ia'),
                        'default' => 6,
                    ],
                    'mostrar_valoraciones' => [
                        'type' => 'toggle',
                        'label' => __('Mostrar valoraciones', 'flavor-chat-ia'),
                        'default' => true,
                    ],
                ],
                'template' => 'landings/tienda-local-destacados',
            ],
            'tienda_local_mapa' => [
                'label' => __('Mapa Tiendas', 'flavor-chat-ia'),
                'description' => __('Mapa de comercios', 'flavor-chat-ia'),
                'category' => 'content',
                'icon' => 'dashicons-location',
                'fields' => [
                    'titulo' => [
                        'type' => 'text',
                        'label' => __('Título', 'flavor-chat-ia'),
                        'default' => __('Encuentra Comercios Cerca de Ti', 'flavor-chat-ia'),
                    ],
                    'zoom_inicial' => [
                        'type' => 'number',
                        'label' => __('Zoom inicial', 'flavor-chat-ia'),
                        'default' => 15,
                    ],
                    'mostrar_mi_ubicacion' => [
                        'type' => 'toggle',
                        'label' => __('Mostrar mi ubicación', 'flavor-chat-ia'),
                        'default' => true,
                    ],
                ],
                'template' => 'landings/tienda-local-mapa',
            ],
            'tienda_local_ofertas' => [
                'label' => __('Ofertas Tiendas', 'flavor-chat-ia'),
                'description' => __('Ofertas y promociones', 'flavor-chat-ia'),
                'category' => 'listings',
                'icon' => 'dashicons-tag',
                'fields' => [
                    'titulo' => [
                        'type' => 'text',
                        'label' => __('Título', 'flavor-chat-ia'),
                        'default' => __('Ofertas y Promociones', 'flavor-chat-ia'),
                    ],
                    'limite' => [
                        'type' => 'number',
                        'label' => __('Número máximo', 'flavor-chat-ia'),
                        'default' => 4,
                    ],
                ],
                'template' => 'landings/tienda-local-ofertas',
            ],
            'tienda_local_cta_registrar' => [
                'label' => __('CTA Registrar Comercio', 'flavor-chat-ia'),
                'description' => __('Llamada a la acción para registrar comercio', 'flavor-chat-ia'),
                'category' => 'cta',
                'icon' => 'dashicons-megaphone',
                'fields' => [
                    'titulo' => [
                        'type' => 'text',
                        'label' => __('Título', 'flavor-chat-ia'),
                        'default' => __('¿Tienes un comercio local?', 'flavor-chat-ia'),
                    ],
                    'subtitulo' => [
                        'type' => 'textarea',
                        'label' => __('Subtítulo', 'flavor-chat-ia'),
                        'default' => __('Registra tu negocio', 'flavor-chat-ia'),
                    ],
                    'texto_boton' => [
                        'type' => 'text',
                        'label' => __('Texto del botón', 'flavor-chat-ia'),
                        'default' => __('Registrar mi Comercio', 'flavor-chat-ia'),
                    ],
                    'url_boton' => [
                        'type' => 'url',
                        'label' => __('URL del botón', 'flavor-chat-ia'),
                        'default' => '#',
                    ],
                ],
                'template' => 'landings/tienda-local-cta-registrar',
            ],

            // PODCAST ADICIONALES
            'podcast_ultimo_episodio' => [
                'label' => __('Último Episodio', 'flavor-chat-ia'),
                'description' => __('Destacado del último episodio', 'flavor-chat-ia'),
                'category' => 'content',
                'icon' => 'dashicons-microphone',
                'fields' => [
                    'titulo' => [
                        'type' => 'text',
                        'label' => __('Título', 'flavor-chat-ia'),
                        'default' => __('Último Episodio', 'flavor-chat-ia'),
                    ],
                    'mostrar_player' => [
                        'type' => 'toggle',
                        'label' => __('Mostrar reproductor', 'flavor-chat-ia'),
                        'default' => true,
                    ],
                    'mostrar_descripcion' => [
                        'type' => 'toggle',
                        'label' => __('Mostrar descripción', 'flavor-chat-ia'),
                        'default' => true,
                    ],
                ],
                'template' => 'landings/podcast-ultimo-episodio',
            ],
            'podcast_episodios_grid' => [
                'label' => __('Grid Episodios', 'flavor-chat-ia'),
                'description' => __('Listado de episodios', 'flavor-chat-ia'),
                'category' => 'listings',
                'icon' => 'dashicons-grid-view',
                'fields' => [
                    'titulo' => [
                        'type' => 'text',
                        'label' => __('Título', 'flavor-chat-ia'),
                        'default' => __('Todos los Episodios', 'flavor-chat-ia'),
                    ],
                    'limite' => [
                        'type' => 'number',
                        'label' => __('Número máximo', 'flavor-chat-ia'),
                        'default' => 9,
                    ],
                    'columnas' => [
                        'type' => 'select',
                        'label' => __('Columnas', 'flavor-chat-ia'),
                        'options' => [2, 3, 4],
                        'default' => 3,
                    ],
                    'mostrar_duracion' => [
                        'type' => 'toggle',
                        'label' => __('Mostrar duración', 'flavor-chat-ia'),
                        'default' => true,
                    ],
                ],
                'template' => 'landings/podcast-episodios-grid',
            ],
            'podcast_categorias' => [
                'label' => __('Categorías Podcast', 'flavor-chat-ia'),
                'description' => __('Temáticas del podcast', 'flavor-chat-ia'),
                'category' => 'listings',
                'icon' => 'dashicons-category',
                'fields' => [
                    'titulo' => [
                        'type' => 'text',
                        'label' => __('Título', 'flavor-chat-ia'),
                        'default' => __('Temáticas', 'flavor-chat-ia'),
                    ],
                ],
                'template' => 'landings/podcast-categorias',
            ],
            'podcast_presentadores' => [
                'label' => __('Presentadores Podcast', 'flavor-chat-ia'),
                'description' => __('Equipo del podcast', 'flavor-chat-ia'),
                'category' => 'content',
                'icon' => 'dashicons-groups',
                'fields' => [
                    'titulo' => [
                        'type' => 'text',
                        'label' => __('Título', 'flavor-chat-ia'),
                        'default' => __('Nuestros Presentadores', 'flavor-chat-ia'),
                    ],
                    'limite' => [
                        'type' => 'number',
                        'label' => __('Número máximo', 'flavor-chat-ia'),
                        'default' => 4,
                    ],
                    'mostrar_bio' => [
                        'type' => 'toggle',
                        'label' => __('Mostrar biografía', 'flavor-chat-ia'),
                        'default' => true,
                    ],
                ],
                'template' => 'landings/podcast-presentadores',
            ],
            'podcast_suscribir' => [
                'label' => __('Suscribir Podcast', 'flavor-chat-ia'),
                'description' => __('Enlaces de suscripción', 'flavor-chat-ia'),
                'category' => 'cta',
                'icon' => 'dashicons-rss',
                'fields' => [
                    'titulo' => [
                        'type' => 'text',
                        'label' => __('Título', 'flavor-chat-ia'),
                        'default' => __('Suscríbete', 'flavor-chat-ia'),
                    ],
                    'subtitulo' => [
                        'type' => 'textarea',
                        'label' => __('Subtítulo', 'flavor-chat-ia'),
                        'default' => __('Escúchanos en tu plataforma favorita', 'flavor-chat-ia'),
                    ],
                ],
                'template' => 'landings/podcast-suscribir',
            ],
            'podcast_cta_participar' => [
                'label' => __('CTA Participar Podcast', 'flavor-chat-ia'),
                'description' => __('Llamada a la acción para participar', 'flavor-chat-ia'),
                'category' => 'cta',
                'icon' => 'dashicons-megaphone',
                'fields' => [
                    'titulo' => [
                        'type' => 'text',
                        'label' => __('Título', 'flavor-chat-ia'),
                        'default' => __('¿Quieres participar?', 'flavor-chat-ia'),
                    ],
                    'subtitulo' => [
                        'type' => 'textarea',
                        'label' => __('Subtítulo', 'flavor-chat-ia'),
                        'default' => __('Envíanos tus sugerencias', 'flavor-chat-ia'),
                    ],
                    'texto_boton' => [
                        'type' => 'text',
                        'label' => __('Texto del botón', 'flavor-chat-ia'),
                        'default' => __('Contactar', 'flavor-chat-ia'),
                    ],
                    'url_boton' => [
                        'type' => 'url',
                        'label' => __('URL del botón', 'flavor-chat-ia'),
                        'default' => '#',
                    ],
                ],
                'template' => 'landings/podcast-cta-participar',
            ],

            // RADIO ADICIONALES
            'radio_player_en_vivo' => [
                'label' => __('Player Radio en Vivo', 'flavor-chat-ia'),
                'description' => __('Reproductor de radio en vivo', 'flavor-chat-ia'),
                'category' => 'content',
                'icon' => 'dashicons-controls-volumeon',
                'fields' => [
                    'titulo' => [
                        'type' => 'text',
                        'label' => __('Título', 'flavor-chat-ia'),
                        'default' => __('Escucha en Vivo', 'flavor-chat-ia'),
                    ],
                    'mostrar_programa_actual' => [
                        'type' => 'toggle',
                        'label' => __('Mostrar programa actual', 'flavor-chat-ia'),
                        'default' => true,
                    ],
                    'mostrar_siguiente' => [
                        'type' => 'toggle',
                        'label' => __('Mostrar siguiente', 'flavor-chat-ia'),
                        'default' => true,
                    ],
                    'url_stream' => [
                        'type' => 'url',
                        'label' => __('URL del stream', 'flavor-chat-ia'),
                        'default' => '',
                    ],
                ],
                'template' => 'landings/radio-player-en-vivo',
            ],
            'radio_programacion' => [
                'label' => __('Programación Radio', 'flavor-chat-ia'),
                'description' => __('Parrilla de programación', 'flavor-chat-ia'),
                'category' => 'content',
                'icon' => 'dashicons-calendar-alt',
                'fields' => [
                    'titulo' => [
                        'type' => 'text',
                        'label' => __('Título', 'flavor-chat-ia'),
                        'default' => __('Programación Semanal', 'flavor-chat-ia'),
                    ],
                    'formato_hora' => [
                        'type' => 'select',
                        'label' => __('Formato hora', 'flavor-chat-ia'),
                        'options' => ['12h', '24h'],
                        'default' => '24h',
                    ],
                ],
                'template' => 'landings/radio-programacion',
            ],
            'radio_programas' => [
                'label' => __('Programas Radio', 'flavor-chat-ia'),
                'description' => __('Listado de programas', 'flavor-chat-ia'),
                'category' => 'listings',
                'icon' => 'dashicons-playlist-audio',
                'fields' => [
                    'titulo' => [
                        'type' => 'text',
                        'label' => __('Título', 'flavor-chat-ia'),
                        'default' => __('Nuestros Programas', 'flavor-chat-ia'),
                    ],
                    'limite' => [
                        'type' => 'number',
                        'label' => __('Número máximo', 'flavor-chat-ia'),
                        'default' => 6,
                    ],
                    'mostrar_horario' => [
                        'type' => 'toggle',
                        'label' => __('Mostrar horario', 'flavor-chat-ia'),
                        'default' => true,
                    ],
                    'mostrar_descripcion' => [
                        'type' => 'toggle',
                        'label' => __('Mostrar descripción', 'flavor-chat-ia'),
                        'default' => true,
                    ],
                ],
                'template' => 'landings/radio-programas',
            ],
            'radio_locutores' => [
                'label' => __('Locutores Radio', 'flavor-chat-ia'),
                'description' => __('Equipo de locutores', 'flavor-chat-ia'),
                'category' => 'content',
                'icon' => 'dashicons-groups',
                'fields' => [
                    'titulo' => [
                        'type' => 'text',
                        'label' => __('Título', 'flavor-chat-ia'),
                        'default' => __('El Equipo', 'flavor-chat-ia'),
                    ],
                    'limite' => [
                        'type' => 'number',
                        'label' => __('Número máximo', 'flavor-chat-ia'),
                        'default' => 8,
                    ],
                    'mostrar_programa' => [
                        'type' => 'toggle',
                        'label' => __('Mostrar programa', 'flavor-chat-ia'),
                        'default' => true,
                    ],
                ],
                'template' => 'landings/radio-locutores',
            ],
            'radio_archivo' => [
                'label' => __('Archivo Radio', 'flavor-chat-ia'),
                'description' => __('Programas anteriores', 'flavor-chat-ia'),
                'category' => 'listings',
                'icon' => 'dashicons-media-audio',
                'fields' => [
                    'titulo' => [
                        'type' => 'text',
                        'label' => __('Título', 'flavor-chat-ia'),
                        'default' => __('Programas Anteriores', 'flavor-chat-ia'),
                    ],
                    'limite' => [
                        'type' => 'number',
                        'label' => __('Número máximo', 'flavor-chat-ia'),
                        'default' => 6,
                    ],
                    'mostrar_player' => [
                        'type' => 'toggle',
                        'label' => __('Mostrar reproductor', 'flavor-chat-ia'),
                        'default' => true,
                    ],
                ],
                'template' => 'landings/radio-archivo',
            ],
            'radio_cta_colaborar' => [
                'label' => __('CTA Colaborar Radio', 'flavor-chat-ia'),
                'description' => __('Llamada a la acción para colaborar', 'flavor-chat-ia'),
                'category' => 'cta',
                'icon' => 'dashicons-megaphone',
                'fields' => [
                    'titulo' => [
                        'type' => 'text',
                        'label' => __('Título', 'flavor-chat-ia'),
                        'default' => __('¿Quieres colaborar?', 'flavor-chat-ia'),
                    ],
                    'subtitulo' => [
                        'type' => 'textarea',
                        'label' => __('Subtítulo', 'flavor-chat-ia'),
                        'default' => __('Únete a la radio comunitaria', 'flavor-chat-ia'),
                    ],
                    'texto_boton' => [
                        'type' => 'text',
                        'label' => __('Texto del botón', 'flavor-chat-ia'),
                        'default' => __('Quiero Participar', 'flavor-chat-ia'),
                    ],
                    'url_boton' => [
                        'type' => 'url',
                        'label' => __('URL del botón', 'flavor-chat-ia'),
                        'default' => '#',
                    ],
                ],
                'template' => 'landings/radio-cta-colaborar',
            ],

            // MULTIMEDIA
            'hero_multimedia' => [
                'label' => __('Hero Multimedia', 'flavor-chat-ia'),
                'description' => __('Sección hero de galería multimedia', 'flavor-chat-ia'),
                'category' => 'hero',
                'icon' => 'dashicons-format-gallery',
                'fields' => [
                    'titulo' => [
                        'type' => 'text',
                        'label' => __('Título', 'flavor-chat-ia'),
                        'default' => __('Galería Comunitaria', 'flavor-chat-ia'),
                    ],
                    'subtitulo' => [
                        'type' => 'textarea',
                        'label' => __('Subtítulo', 'flavor-chat-ia'),
                        'default' => __('Momentos y recuerdos de nuestra comunidad', 'flavor-chat-ia'),
                    ],
                    'imagen_fondo' => [
                        'type' => 'image',
                        'label' => __('Imagen de fondo', 'flavor-chat-ia'),
                        'default' => '',
                    ],
                    'mostrar_contador' => [
                        'type' => 'toggle',
                        'label' => __('Mostrar contador', 'flavor-chat-ia'),
                        'default' => true,
                    ],
                ],
                'template' => 'landings/multimedia-hero',
            ],
            'carousel_destacado' => [
                'label' => __('Carousel Destacado', 'flavor-chat-ia'),
                'description' => __('Carousel de imágenes destacadas', 'flavor-chat-ia'),
                'category' => 'content',
                'icon' => 'dashicons-images-alt',
                'fields' => [
                    'titulo' => [
                        'type' => 'text',
                        'label' => __('Título', 'flavor-chat-ia'),
                        'default' => __('Momentos Destacados', 'flavor-chat-ia'),
                    ],
                    'autoplay' => [
                        'type' => 'toggle',
                        'label' => __('Autoplay', 'flavor-chat-ia'),
                        'default' => true,
                    ],
                    'intervalo_segundos' => [
                        'type' => 'number',
                        'label' => __('Intervalo (segundos)', 'flavor-chat-ia'),
                        'default' => 5,
                    ],
                ],
                'template' => 'landings/multimedia-carousel',
            ],
            'galeria_grid' => [
                'label' => __('Galería Grid', 'flavor-chat-ia'),
                'description' => __('Grid de fotos', 'flavor-chat-ia'),
                'category' => 'listings',
                'icon' => 'dashicons-format-gallery',
                'fields' => [
                    'titulo' => [
                        'type' => 'text',
                        'label' => __('Título', 'flavor-chat-ia'),
                        'default' => __('Galería de Fotos', 'flavor-chat-ia'),
                    ],
                    'columnas' => [
                        'type' => 'select',
                        'label' => __('Columnas', 'flavor-chat-ia'),
                        'options' => [2, 3, 4, 5],
                        'default' => 4,
                    ],
                    'limite' => [
                        'type' => 'number',
                        'label' => __('Número máximo', 'flavor-chat-ia'),
                        'default' => 12,
                    ],
                ],
                'template' => 'landings/multimedia-galeria-grid',
            ],
            'albumes' => [
                'label' => __('Álbumes', 'flavor-chat-ia'),
                'description' => __('Listado de álbumes', 'flavor-chat-ia'),
                'category' => 'listings',
                'icon' => 'dashicons-images-alt2',
                'fields' => [
                    'titulo' => [
                        'type' => 'text',
                        'label' => __('Título', 'flavor-chat-ia'),
                        'default' => __('Álbumes de la Comunidad', 'flavor-chat-ia'),
                    ],
                    'columnas' => [
                        'type' => 'select',
                        'label' => __('Columnas', 'flavor-chat-ia'),
                        'options' => [2, 3, 4],
                        'default' => 3,
                    ],
                    'limite' => [
                        'type' => 'number',
                        'label' => __('Número máximo', 'flavor-chat-ia'),
                        'default' => 9,
                    ],
                ],
                'template' => 'landings/multimedia-albumes',
            ],

            // EMPRESARIAL ADICIONALES
            'empresarial_portfolio' => [
                'label' => __('Portfolio Empresarial', 'flavor-chat-ia'),
                'description' => __('Galería de proyectos y casos de éxito', 'flavor-chat-ia'),
                'category' => 'listings',
                'icon' => 'dashicons-portfolio',
                'fields' => [
                    'titulo_seccion' => [
                        'type' => 'text',
                        'label' => __('Título', 'flavor-chat-ia'),
                        'default' => __('Casos de Éxito', 'flavor-chat-ia'),
                    ],
                    'descripcion_seccion' => [
                        'type' => 'textarea',
                        'label' => __('Descripción', 'flavor-chat-ia'),
                        'default' => __('Proyectos que transformaron negocios', 'flavor-chat-ia'),
                    ],
                    'layout' => [
                        'type' => 'select',
                        'label' => __('Layout', 'flavor-chat-ia'),
                        'options' => ['grid', 'masonry', 'carousel'],
                        'default' => 'masonry',
                    ],
                    'columnas' => [
                        'type' => 'select',
                        'label' => __('Columnas', 'flavor-chat-ia'),
                        'options' => ['2', '3', '4'],
                        'default' => '3',
                    ],
                    'numero_proyectos' => [
                        'type' => 'number',
                        'label' => __('Número de proyectos', 'flavor-chat-ia'),
                        'default' => 6,
                    ],
                    'mostrar_filtros' => [
                        'type' => 'toggle',
                        'label' => __('Mostrar filtros', 'flavor-chat-ia'),
                        'default' => true,
                    ],
                ],
                'template' => 'landings/empresarial-portfolio',
            ],

            // ========================================
            // PARTICIPACIÓN CIUDADANA
            // ========================================
            'participacion_hero' => [
                'label' => __('Hero Participación Ciudadana', 'flavor-chat-ia'),
                'description' => __('Sección hero para participación ciudadana', 'flavor-chat-ia'),
                'category' => 'hero',
                'icon' => 'dashicons-megaphone',
                'fields' => [
                    'titulo' => ['type' => 'text', 'label' => __('Título', 'flavor-chat-ia'), 'default' => __('Participa en tu Comunidad', 'flavor-chat-ia')],
                    'subtitulo' => ['type' => 'textarea', 'label' => __('Subtítulo', 'flavor-chat-ia'), 'default' => __('Tu voz importa. Propón, debate y vota', 'flavor-chat-ia')],
                    'imagen_fondo' => ['type' => 'image', 'label' => __('Imagen de fondo', 'flavor-chat-ia'), 'default' => ''],
                    'color_primario' => ['type' => 'color', 'label' => __('Color primario', 'flavor-chat-ia'), 'default' => '#f59e0b'],
                ],
                'template' => 'participacion/hero',
            ],
            'participacion_propuestas_grid' => [
                'label' => __('Grid de Propuestas', 'flavor-chat-ia'),
                'description' => __('Listado de propuestas ciudadanas', 'flavor-chat-ia'),
                'category' => 'listings',
                'icon' => 'dashicons-editor-ul',
                'fields' => [
                    'titulo' => ['type' => 'text', 'label' => __('Título', 'flavor-chat-ia'), 'default' => __('Propuestas Ciudadanas', 'flavor-chat-ia')],
                    'columnas' => ['type' => 'select', 'label' => __('Columnas', 'flavor-chat-ia'), 'options' => [2, 3], 'default' => 3],
                    'limite' => ['type' => 'number', 'label' => __('Número máximo', 'flavor-chat-ia'), 'default' => 6],
                ],
                'template' => 'participacion/propuestas-grid',
            ],
            'participacion_como_participar' => [
                'label' => __('Cómo Participar', 'flavor-chat-ia'),
                'description' => __('Sección de proceso de participación', 'flavor-chat-ia'),
                'category' => 'features',
                'icon' => 'dashicons-lightbulb',
                'fields' => [
                    'titulo' => ['type' => 'text', 'label' => __('Título', 'flavor-chat-ia'), 'default' => __('¿Cómo participar?', 'flavor-chat-ia')],
                ],
                'template' => 'participacion/como-participar',
            ],
            'participacion_cta' => [
                'label' => __('CTA Propuesta', 'flavor-chat-ia'),
                'description' => __('Llamada a la acción para enviar propuesta', 'flavor-chat-ia'),
                'category' => 'cta',
                'icon' => 'dashicons-admin-comments',
                'fields' => [
                    'titulo' => ['type' => 'text', 'label' => __('Título', 'flavor-chat-ia'), 'default' => __('Tu voz importa', 'flavor-chat-ia')],
                    'url_boton' => ['type' => 'url', 'label' => __('URL del botón', 'flavor-chat-ia'), 'default' => '#'],
                ],
                'template' => 'participacion/cta-propuesta',
            ],

            // ========================================
            // PRESUPUESTOS PARTICIPATIVOS
            // ========================================
            'presupuestos_hero' => [
                'label' => __('Hero Presupuestos Participativos', 'flavor-chat-ia'),
                'description' => __('Sección hero para presupuestos participativos', 'flavor-chat-ia'),
                'category' => 'hero',
                'icon' => 'dashicons-chart-pie',
                'fields' => [
                    'titulo' => ['type' => 'text', 'label' => __('Título', 'flavor-chat-ia'), 'default' => __('Presupuestos Participativos', 'flavor-chat-ia')],
                    'subtitulo' => ['type' => 'textarea', 'label' => __('Subtítulo', 'flavor-chat-ia'), 'default' => __('Decide en qué se invierte el dinero público', 'flavor-chat-ia')],
                    'imagen_fondo' => ['type' => 'image', 'label' => __('Imagen de fondo', 'flavor-chat-ia'), 'default' => ''],
                    'color_primario' => ['type' => 'color', 'label' => __('Color primario', 'flavor-chat-ia'), 'default' => '#eab308'],
                ],
                'template' => 'presupuestos-participativos/hero',
            ],
            'presupuestos_proyectos_grid' => [
                'label' => __('Grid de Proyectos', 'flavor-chat-ia'),
                'description' => __('Listado de proyectos presupuestarios', 'flavor-chat-ia'),
                'category' => 'listings',
                'icon' => 'dashicons-grid-view',
                'fields' => [
                    'titulo' => ['type' => 'text', 'label' => __('Título', 'flavor-chat-ia'), 'default' => __('Proyectos Propuestos', 'flavor-chat-ia')],
                    'limite' => ['type' => 'number', 'label' => __('Número máximo', 'flavor-chat-ia'), 'default' => 6],
                ],
                'template' => 'presupuestos-participativos/proyectos-grid',
            ],
            'presupuestos_proceso' => [
                'label' => __('Proceso de Votación', 'flavor-chat-ia'),
                'description' => __('Timeline del proceso de presupuestos', 'flavor-chat-ia'),
                'category' => 'features',
                'icon' => 'dashicons-clock',
                'fields' => [
                    'titulo' => ['type' => 'text', 'label' => __('Título', 'flavor-chat-ia'), 'default' => __('Proceso', 'flavor-chat-ia')],
                ],
                'template' => 'presupuestos-participativos/proceso-votacion',
            ],
            'presupuestos_resultados' => [
                'label' => __('Resultados', 'flavor-chat-ia'),
                'description' => __('Dashboard de resultados de presupuestos', 'flavor-chat-ia'),
                'category' => 'content',
                'icon' => 'dashicons-chart-bar',
                'fields' => [
                    'titulo' => ['type' => 'text', 'label' => __('Título', 'flavor-chat-ia'), 'default' => __('Resultados', 'flavor-chat-ia')],
                ],
                'template' => 'presupuestos-participativos/resultados',
            ],

            // ========================================
            // TRANSPARENCIA
            // ========================================
            'transparencia_hero' => [
                'label' => __('Hero Transparencia', 'flavor-chat-ia'),
                'description' => __('Portal de transparencia', 'flavor-chat-ia'),
                'category' => 'hero',
                'icon' => 'dashicons-visibility',
                'fields' => [
                    'titulo' => ['type' => 'text', 'label' => __('Título', 'flavor-chat-ia'), 'default' => __('Portal de Transparencia', 'flavor-chat-ia')],
                    'subtitulo' => ['type' => 'textarea', 'label' => __('Subtítulo', 'flavor-chat-ia'), 'default' => __('Información pública accesible para todos', 'flavor-chat-ia')],
                    'imagen_fondo' => ['type' => 'image', 'label' => __('Imagen de fondo', 'flavor-chat-ia'), 'default' => ''],
                    'color_primario' => ['type' => 'color', 'label' => __('Color primario', 'flavor-chat-ia'), 'default' => '#14b8a6'],
                ],
                'template' => 'transparencia/hero',
            ],
            'transparencia_datos_grid' => [
                'label' => __('Grid de Datos Abiertos', 'flavor-chat-ia'),
                'description' => __('Categorías de datos abiertos', 'flavor-chat-ia'),
                'category' => 'listings',
                'icon' => 'dashicons-media-spreadsheet',
                'fields' => [
                    'titulo' => ['type' => 'text', 'label' => __('Título', 'flavor-chat-ia'), 'default' => __('Datos Abiertos', 'flavor-chat-ia')],
                ],
                'template' => 'transparencia/datos-grid',
            ],
            'transparencia_indicadores' => [
                'label' => __('Indicadores', 'flavor-chat-ia'),
                'description' => __('Dashboard de indicadores clave', 'flavor-chat-ia'),
                'category' => 'content',
                'icon' => 'dashicons-performance',
                'fields' => [
                    'titulo' => ['type' => 'text', 'label' => __('Título', 'flavor-chat-ia'), 'default' => __('Indicadores Clave', 'flavor-chat-ia')],
                ],
                'template' => 'transparencia/indicadores',
            ],
            'transparencia_documentos' => [
                'label' => __('Documentos', 'flavor-chat-ia'),
                'description' => __('Repositorio de documentos públicos', 'flavor-chat-ia'),
                'category' => 'listings',
                'icon' => 'dashicons-media-document',
                'fields' => [
                    'titulo' => ['type' => 'text', 'label' => __('Título', 'flavor-chat-ia'), 'default' => __('Documentos Públicos', 'flavor-chat-ia')],
                ],
                'template' => 'transparencia/documentos',
            ],

            // ========================================
            // TRÁMITES
            // ========================================
            'tramites_hero' => [
                'label' => __('Hero Trámites', 'flavor-chat-ia'),
                'description' => __('Sección hero para trámites online', 'flavor-chat-ia'),
                'category' => 'hero',
                'icon' => 'dashicons-clipboard',
                'fields' => [
                    'titulo' => ['type' => 'text', 'label' => __('Título', 'flavor-chat-ia'), 'default' => __('Trámites Online', 'flavor-chat-ia')],
                    'subtitulo' => ['type' => 'textarea', 'label' => __('Subtítulo', 'flavor-chat-ia'), 'default' => __('Realiza tus gestiones sin salir de casa', 'flavor-chat-ia')],
                    'imagen_fondo' => ['type' => 'image', 'label' => __('Imagen de fondo', 'flavor-chat-ia'), 'default' => ''],
                    'color_primario' => ['type' => 'color', 'label' => __('Color primario', 'flavor-chat-ia'), 'default' => '#f97316'],
                ],
                'template' => 'tramites/hero',
            ],
            'tramites_grid' => [
                'label' => __('Grid de Trámites', 'flavor-chat-ia'),
                'description' => __('Listado de trámites disponibles', 'flavor-chat-ia'),
                'category' => 'listings',
                'icon' => 'dashicons-editor-ul',
                'fields' => [
                    'titulo' => ['type' => 'text', 'label' => __('Título', 'flavor-chat-ia'), 'default' => __('Trámites Disponibles', 'flavor-chat-ia')],
                    'limite' => ['type' => 'number', 'label' => __('Número máximo', 'flavor-chat-ia'), 'default' => 8],
                ],
                'template' => 'tramites/tramites-grid',
            ],
            'tramites_como_funciona' => [
                'label' => __('Cómo Funciona - Trámites', 'flavor-chat-ia'),
                'description' => __('Proceso de trámites online', 'flavor-chat-ia'),
                'category' => 'features',
                'icon' => 'dashicons-info',
                'fields' => [
                    'titulo' => ['type' => 'text', 'label' => __('Título', 'flavor-chat-ia'), 'default' => __('¿Cómo funciona?', 'flavor-chat-ia')],
                ],
                'template' => 'tramites/como-funciona',
            ],
            'tramites_cta' => [
                'label' => __('CTA Trámites', 'flavor-chat-ia'),
                'description' => __('Llamada a la acción para iniciar trámite', 'flavor-chat-ia'),
                'category' => 'cta',
                'icon' => 'dashicons-yes-alt',
                'fields' => [
                    'titulo' => ['type' => 'text', 'label' => __('Título', 'flavor-chat-ia'), 'default' => __('Empieza tu trámite ahora', 'flavor-chat-ia')],
                    'url_boton' => ['type' => 'url', 'label' => __('URL del botón', 'flavor-chat-ia'), 'default' => '#'],
                ],
                'template' => 'tramites/cta-solicitar',
            ],

            // ========================================
            // SOCIOS / MEMBRESÍAS
            // ========================================
            'socios_hero' => [
                'label' => __('Hero Socios', 'flavor-chat-ia'),
                'description' => __('Sección hero para membresías', 'flavor-chat-ia'),
                'category' => 'hero',
                'icon' => 'dashicons-groups',
                'fields' => [
                    'titulo' => ['type' => 'text', 'label' => __('Título', 'flavor-chat-ia'), 'default' => __('Hazte Socio', 'flavor-chat-ia')],
                    'subtitulo' => ['type' => 'textarea', 'label' => __('Subtítulo', 'flavor-chat-ia'), 'default' => __('Únete a nuestra comunidad', 'flavor-chat-ia')],
                    'imagen_fondo' => ['type' => 'image', 'label' => __('Imagen de fondo', 'flavor-chat-ia'), 'default' => ''],
                    'color_primario' => ['type' => 'color', 'label' => __('Color primario', 'flavor-chat-ia'), 'default' => '#f43f5e'],
                ],
                'template' => 'socios/hero',
            ],
            'socios_planes_grid' => [
                'label' => __('Planes de Socios', 'flavor-chat-ia'),
                'description' => __('Grid de planes de membresía', 'flavor-chat-ia'),
                'category' => 'listings',
                'icon' => 'dashicons-money-alt',
                'fields' => [
                    'titulo' => ['type' => 'text', 'label' => __('Título', 'flavor-chat-ia'), 'default' => __('Elige tu Plan', 'flavor-chat-ia')],
                ],
                'template' => 'socios/planes-grid',
            ],
            'socios_beneficios' => [
                'label' => __('Beneficios de Socios', 'flavor-chat-ia'),
                'description' => __('Lista de beneficios de membresía', 'flavor-chat-ia'),
                'category' => 'features',
                'icon' => 'dashicons-star-filled',
                'fields' => [
                    'titulo' => ['type' => 'text', 'label' => __('Título', 'flavor-chat-ia'), 'default' => __('Beneficios Exclusivos', 'flavor-chat-ia')],
                ],
                'template' => 'socios/beneficios',
            ],
            'socios_cta' => [
                'label' => __('CTA Socios', 'flavor-chat-ia'),
                'description' => __('Llamada a la acción para unirse', 'flavor-chat-ia'),
                'category' => 'cta',
                'icon' => 'dashicons-plus-alt',
                'fields' => [
                    'titulo' => ['type' => 'text', 'label' => __('Título', 'flavor-chat-ia'), 'default' => __('No te quedes fuera', 'flavor-chat-ia')],
                    'url_boton' => ['type' => 'url', 'label' => __('URL del botón', 'flavor-chat-ia'), 'default' => '#'],
                ],
                'template' => 'socios/cta-unirse',
            ],

            // ========================================
            // MARKETPLACE
            // ========================================
            'marketplace_hero' => [
                'label' => __('Hero Marketplace', 'flavor-chat-ia'),
                'description' => __('Sección hero para marketplace local', 'flavor-chat-ia'),
                'category' => 'hero',
                'icon' => 'dashicons-cart',
                'fields' => [
                    'titulo' => ['type' => 'text', 'label' => __('Título', 'flavor-chat-ia'), 'default' => __('Marketplace Local', 'flavor-chat-ia')],
                    'subtitulo' => ['type' => 'textarea', 'label' => __('Subtítulo', 'flavor-chat-ia'), 'default' => __('Compra, vende e intercambia en tu barrio', 'flavor-chat-ia')],
                    'imagen_fondo' => ['type' => 'image', 'label' => __('Imagen de fondo', 'flavor-chat-ia'), 'default' => ''],
                    'mostrar_buscador' => ['type' => 'toggle', 'label' => __('Mostrar buscador', 'flavor-chat-ia'), 'default' => true],
                    'color_primario' => ['type' => 'color', 'label' => __('Color primario', 'flavor-chat-ia'), 'default' => '#84cc16'],
                ],
                'template' => 'marketplace/hero',
            ],
            'marketplace_productos_grid' => [
                'label' => __('Grid de Productos', 'flavor-chat-ia'),
                'description' => __('Listado de productos del marketplace', 'flavor-chat-ia'),
                'category' => 'listings',
                'icon' => 'dashicons-grid-view',
                'fields' => [
                    'titulo' => ['type' => 'text', 'label' => __('Título', 'flavor-chat-ia'), 'default' => __('Productos Destacados', 'flavor-chat-ia')],
                    'columnas' => ['type' => 'select', 'label' => __('Columnas', 'flavor-chat-ia'), 'options' => [2, 3, 4], 'default' => 3],
                    'limite' => ['type' => 'number', 'label' => __('Número máximo', 'flavor-chat-ia'), 'default' => 6],
                ],
                'template' => 'marketplace/productos-grid',
            ],
            'marketplace_categorias' => [
                'label' => __('Categorías Marketplace', 'flavor-chat-ia'),
                'description' => __('Navegador de categorías del marketplace', 'flavor-chat-ia'),
                'category' => 'navigation',
                'icon' => 'dashicons-tag',
                'fields' => [
                    'titulo' => ['type' => 'text', 'label' => __('Título', 'flavor-chat-ia'), 'default' => __('Explora por Categoría', 'flavor-chat-ia')],
                ],
                'template' => 'marketplace/categorias',
            ],
            'marketplace_cta' => [
                'label' => __('CTA Marketplace', 'flavor-chat-ia'),
                'description' => __('Llamada a la acción para vender', 'flavor-chat-ia'),
                'category' => 'cta',
                'icon' => 'dashicons-megaphone',
                'fields' => [
                    'titulo' => ['type' => 'text', 'label' => __('Título', 'flavor-chat-ia'), 'default' => __('¿Tienes algo que ya no necesitas?', 'flavor-chat-ia')],
                    'url_boton' => ['type' => 'url', 'label' => __('URL del botón', 'flavor-chat-ia'), 'default' => '#'],
                ],
                'template' => 'marketplace/cta-vender',
            ],

            // ========================================
            // FACTURAS
            // ========================================
            'facturas_hero' => [
                'label' => __('Hero Facturas', 'flavor-chat-ia'),
                'description' => __('Gestión de facturación', 'flavor-chat-ia'),
                'category' => 'hero',
                'icon' => 'dashicons-media-text',
                'fields' => [
                    'titulo' => ['type' => 'text', 'label' => __('Título', 'flavor-chat-ia'), 'default' => __('Gestión de Facturas', 'flavor-chat-ia')],
                    'subtitulo' => ['type' => 'textarea', 'label' => __('Subtítulo', 'flavor-chat-ia'), 'default' => __('Crea, envía y gestiona tus facturas fácilmente', 'flavor-chat-ia')],
                    'color_primario' => ['type' => 'color', 'label' => __('Color primario', 'flavor-chat-ia'), 'default' => '#14b8a6'],
                ],
                'template' => 'facturas/hero',
            ],
            'facturas_features' => [
                'label' => __('Características Facturas', 'flavor-chat-ia'),
                'description' => __('Funcionalidades del sistema de facturación', 'flavor-chat-ia'),
                'category' => 'features',
                'icon' => 'dashicons-yes',
                'fields' => [
                    'titulo' => ['type' => 'text', 'label' => __('Título', 'flavor-chat-ia'), 'default' => __('Funcionalidades', 'flavor-chat-ia')],
                ],
                'template' => 'facturas/features',
            ],

            // ========================================
            // FICHAJE EMPLEADOS
            // ========================================
            'fichaje_hero' => [
                'label' => __('Hero Fichaje', 'flavor-chat-ia'),
                'description' => __('Control de fichaje de empleados', 'flavor-chat-ia'),
                'category' => 'hero',
                'icon' => 'dashicons-clock',
                'fields' => [
                    'titulo' => ['type' => 'text', 'label' => __('Título', 'flavor-chat-ia'), 'default' => __('Control de Fichaje', 'flavor-chat-ia')],
                    'subtitulo' => ['type' => 'textarea', 'label' => __('Subtítulo', 'flavor-chat-ia'), 'default' => __('Gestiona horarios y asistencia de tu equipo', 'flavor-chat-ia')],
                    'color_primario' => ['type' => 'color', 'label' => __('Color primario', 'flavor-chat-ia'), 'default' => '#64748b'],
                ],
                'template' => 'fichaje-empleados/hero',
            ],
            'fichaje_features' => [
                'label' => __('Características Fichaje', 'flavor-chat-ia'),
                'description' => __('Funcionalidades del control de fichaje', 'flavor-chat-ia'),
                'category' => 'features',
                'icon' => 'dashicons-list-view',
                'fields' => [
                    'titulo' => ['type' => 'text', 'label' => __('Título', 'flavor-chat-ia'), 'default' => __('Funcionalidades', 'flavor-chat-ia')],
                ],
                'template' => 'fichaje-empleados/features',
            ],

            // ========================================
            // TRADING IA
            // ========================================
            'trading_ia_hero' => [
                'label' => __('Hero Trading IA', 'flavor-chat-ia'),
                'description' => __('Trading con inteligencia artificial', 'flavor-chat-ia'),
                'category' => 'hero',
                'icon' => 'dashicons-chart-line',
                'fields' => [
                    'titulo' => ['type' => 'text', 'label' => __('Título', 'flavor-chat-ia'), 'default' => __('Trading con IA', 'flavor-chat-ia')],
                    'subtitulo' => ['type' => 'textarea', 'label' => __('Subtítulo', 'flavor-chat-ia'), 'default' => __('Análisis predictivo impulsado por IA', 'flavor-chat-ia')],
                    'color_primario' => ['type' => 'color', 'label' => __('Color primario', 'flavor-chat-ia'), 'default' => '#06b6d4'],
                ],
                'template' => 'trading-ia/hero',
            ],
            'trading_ia_features' => [
                'label' => __('Características Trading IA', 'flavor-chat-ia'),
                'description' => __('Funcionalidades del trading con IA', 'flavor-chat-ia'),
                'category' => 'features',
                'icon' => 'dashicons-performance',
                'fields' => [
                    'titulo' => ['type' => 'text', 'label' => __('Título', 'flavor-chat-ia'), 'default' => __('Funcionalidades', 'flavor-chat-ia')],
                ],
                'template' => 'trading-ia/features',
            ],
            'trading_ia_stats' => [
                'label' => __('Estadísticas Trading', 'flavor-chat-ia'),
                'description' => __('Dashboard de estadísticas de trading', 'flavor-chat-ia'),
                'category' => 'content',
                'icon' => 'dashicons-chart-bar',
                'fields' => [
                    'titulo' => ['type' => 'text', 'label' => __('Título', 'flavor-chat-ia'), 'default' => __('Rendimiento', 'flavor-chat-ia')],
                ],
                'template' => 'trading-ia/stats',
            ],

            // ========================================
            // DEX SOLANA
            // ========================================
            'dex_solana_hero' => [
                'label' => __('Hero DEX Solana', 'flavor-chat-ia'),
                'description' => __('Exchange descentralizado en Solana', 'flavor-chat-ia'),
                'category' => 'hero',
                'icon' => 'dashicons-randomize',
                'fields' => [
                    'titulo' => ['type' => 'text', 'label' => __('Título', 'flavor-chat-ia'), 'default' => __('DEX en Solana', 'flavor-chat-ia')],
                    'subtitulo' => ['type' => 'textarea', 'label' => __('Subtítulo', 'flavor-chat-ia'), 'default' => __('Intercambia tokens de forma descentralizada', 'flavor-chat-ia')],
                    'color_primario' => ['type' => 'color', 'label' => __('Color primario', 'flavor-chat-ia'), 'default' => '#8b5cf6'],
                ],
                'template' => 'dex-solana/hero',
            ],
            'dex_solana_features' => [
                'label' => __('Características DEX', 'flavor-chat-ia'),
                'description' => __('Funcionalidades del DEX', 'flavor-chat-ia'),
                'category' => 'features',
                'icon' => 'dashicons-shield',
                'fields' => [
                    'titulo' => ['type' => 'text', 'label' => __('Título', 'flavor-chat-ia'), 'default' => __('Funcionalidades', 'flavor-chat-ia')],
                ],
                'template' => 'dex-solana/features',
            ],
            'dex_solana_cta' => [
                'label' => __('CTA Conectar Wallet', 'flavor-chat-ia'),
                'description' => __('Llamada a la acción para conectar wallet', 'flavor-chat-ia'),
                'category' => 'cta',
                'icon' => 'dashicons-admin-links',
                'fields' => [
                    'titulo' => ['type' => 'text', 'label' => __('Título', 'flavor-chat-ia'), 'default' => __('Conecta tu Wallet', 'flavor-chat-ia')],
                ],
                'template' => 'dex-solana/cta-conectar',
            ],

            // ========================================
            // WOOCOMMERCE
            // ========================================
            'woocommerce_hero' => [
                'label' => __('Hero WooCommerce', 'flavor-chat-ia'),
                'description' => __('Sección hero para tienda online', 'flavor-chat-ia'),
                'category' => 'hero',
                'icon' => 'dashicons-store',
                'fields' => [
                    'titulo' => ['type' => 'text', 'label' => __('Título', 'flavor-chat-ia'), 'default' => __('Tu Tienda Online', 'flavor-chat-ia')],
                    'subtitulo' => ['type' => 'textarea', 'label' => __('Subtítulo', 'flavor-chat-ia'), 'default' => __('Crea y gestiona tu tienda con WooCommerce', 'flavor-chat-ia')],
                    'imagen_fondo' => ['type' => 'image', 'label' => __('Imagen de fondo', 'flavor-chat-ia'), 'default' => ''],
                    'color_primario' => ['type' => 'color', 'label' => __('Color primario', 'flavor-chat-ia'), 'default' => '#7c3aed'],
                ],
                'template' => 'woocommerce/hero',
            ],
            'woocommerce_productos_grid' => [
                'label' => __('Grid Productos WooCommerce', 'flavor-chat-ia'),
                'description' => __('Listado de productos WooCommerce', 'flavor-chat-ia'),
                'category' => 'listings',
                'icon' => 'dashicons-products',
                'fields' => [
                    'titulo' => ['type' => 'text', 'label' => __('Título', 'flavor-chat-ia'), 'default' => __('Productos Destacados', 'flavor-chat-ia')],
                    'columnas' => ['type' => 'select', 'label' => __('Columnas', 'flavor-chat-ia'), 'options' => [2, 3, 4], 'default' => 3],
                    'limite' => ['type' => 'number', 'label' => __('Número máximo', 'flavor-chat-ia'), 'default' => 6],
                ],
                'template' => 'woocommerce/productos-grid',
            ],
            'woocommerce_categorias' => [
                'label' => __('Categorías WooCommerce', 'flavor-chat-ia'),
                'description' => __('Navegador de categorías de tienda', 'flavor-chat-ia'),
                'category' => 'navigation',
                'icon' => 'dashicons-category',
                'fields' => [
                    'titulo' => ['type' => 'text', 'label' => __('Título', 'flavor-chat-ia'), 'default' => __('Categorías', 'flavor-chat-ia')],
                ],
                'template' => 'woocommerce/categorias',
            ],
            'woocommerce_cta' => [
                'label' => __('CTA Tienda', 'flavor-chat-ia'),
                'description' => __('Llamada a la acción para la tienda', 'flavor-chat-ia'),
                'category' => 'cta',
                'icon' => 'dashicons-cart',
                'fields' => [
                    'titulo' => ['type' => 'text', 'label' => __('Título', 'flavor-chat-ia'), 'default' => __('Descubre nuestro catálogo', 'flavor-chat-ia')],
                    'url_boton' => ['type' => 'url', 'label' => __('URL del botón', 'flavor-chat-ia'), 'default' => '/tienda'],
                ],
                'template' => 'woocommerce/cta-comprar',
            ],

            // ========================================
            // CHAT GRUPOS (componentes landing)
            // ========================================
            'chat_grupos_hero_landing' => [
                'label' => __('Hero Chat Grupos', 'flavor-chat-ia'),
                'description' => __('Sección hero para grupos de chat', 'flavor-chat-ia'),
                'category' => 'hero',
                'icon' => 'dashicons-format-chat',
                'fields' => [
                    'titulo' => ['type' => 'text', 'label' => __('Título', 'flavor-chat-ia'), 'default' => __('Grupos de Chat', 'flavor-chat-ia')],
                    'subtitulo' => ['type' => 'textarea', 'label' => __('Subtítulo', 'flavor-chat-ia'), 'default' => __('Conecta con personas que comparten tus intereses', 'flavor-chat-ia')],
                    'color_primario' => ['type' => 'color', 'label' => __('Color primario', 'flavor-chat-ia'), 'default' => '#ec4899'],
                ],
                'template' => 'chat-grupos/hero',
            ],
            'chat_grupos_grid' => [
                'label' => __('Grid de Grupos', 'flavor-chat-ia'),
                'description' => __('Listado de grupos de chat', 'flavor-chat-ia'),
                'category' => 'listings',
                'icon' => 'dashicons-groups',
                'fields' => [
                    'titulo' => ['type' => 'text', 'label' => __('Título', 'flavor-chat-ia'), 'default' => __('Grupos Activos', 'flavor-chat-ia')],
                    'limite' => ['type' => 'number', 'label' => __('Número máximo', 'flavor-chat-ia'), 'default' => 6],
                ],
                'template' => 'chat-grupos/grupos-grid',
            ],

            // ========================================
            // CHAT INTERNO (componentes landing)
            // ========================================
            'chat_interno_hero_landing' => [
                'label' => __('Hero Chat Interno', 'flavor-chat-ia'),
                'description' => __('Sección hero para mensajería interna', 'flavor-chat-ia'),
                'category' => 'hero',
                'icon' => 'dashicons-email-alt',
                'fields' => [
                    'titulo' => ['type' => 'text', 'label' => __('Título', 'flavor-chat-ia'), 'default' => __('Mensajería Interna', 'flavor-chat-ia')],
                    'subtitulo' => ['type' => 'textarea', 'label' => __('Subtítulo', 'flavor-chat-ia'), 'default' => __('Comunicación segura con tu comunidad', 'flavor-chat-ia')],
                    'color_primario' => ['type' => 'color', 'label' => __('Color primario', 'flavor-chat-ia'), 'default' => '#f43f5e'],
                ],
                'template' => 'chat-interno/hero',
            ],
            'chat_interno_features' => [
                'label' => __('Características Chat Interno', 'flavor-chat-ia'),
                'description' => __('Funcionalidades de la mensajería', 'flavor-chat-ia'),
                'category' => 'features',
                'icon' => 'dashicons-shield-alt',
                'fields' => [
                    'titulo' => ['type' => 'text', 'label' => __('Título', 'flavor-chat-ia'), 'default' => __('Funcionalidades', 'flavor-chat-ia')],
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
            'carpooling' => 'Flavor_Chat_Carpooling_Module',
            'cursos' => 'Flavor_Chat_Cursos_Module',
            'biblioteca' => 'Flavor_Chat_Biblioteca_Module',
            'talleres' => 'Flavor_Chat_Talleres_Module',
            'huertos-urbanos' => 'Flavor_Chat_Huertos_Urbanos_Module',
            'espacios-comunes' => 'Flavor_Chat_Espacios_Comunes_Module',
            'bicicletas-compartidas' => 'Flavor_Chat_Bicicletas_Compartidas_Module',
            'parkings' => 'Flavor_Chat_Parkings_Module',
            'reciclaje' => 'Flavor_Chat_Reciclaje_Module',
            'compostaje' => 'Flavor_Chat_Compostaje_Module',
            'ayuda-vecinal' => 'Flavor_Chat_Ayuda_Vecinal_Module',
            'podcast' => 'Flavor_Chat_Podcast_Module',
            'radio' => 'Flavor_Chat_Radio_Module',
            'red-social' => 'Flavor_Chat_Red_Social_Module',
            'multimedia' => 'Flavor_Chat_Multimedia_Module',
            'chat-grupos' => 'Flavor_Chat_Chat_Grupos_Module',
            'chat-interno' => 'Flavor_Chat_Chat_Interno_Module',
            'empresarial' => 'Flavor_Chat_Empresarial_Module',
            'themacle' => 'Flavor_Chat_Themacle_Module',
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
                'label' => __('Hero', 'flavor-chat-ia'),
                'description' => __('Sección hero con múltiples variantes visuales', 'flavor-chat-ia'),
                'category' => 'hero',
                'icon' => 'dashicons-cover-image',
                'variants' => [
                    'centrado' => ['label' => __('Centrado', 'flavor-chat-ia'), 'description' => __('Título y subtítulo centrados con fondo', 'flavor-chat-ia')],
                    'split_izquierda' => ['label' => __('Split Izquierda', 'flavor-chat-ia'), 'description' => __('Texto izquierda, imagen derecha', 'flavor-chat-ia')],
                    'split_derecha' => ['label' => __('Split Derecha', 'flavor-chat-ia'), 'description' => __('Imagen izquierda, texto derecha', 'flavor-chat-ia')],
                    'con_buscador' => ['label' => __('Con Buscador', 'flavor-chat-ia'), 'description' => __('Hero con barra de búsqueda prominente', 'flavor-chat-ia')],
                    'con_estadisticas' => ['label' => __('Con Estadísticas', 'flavor-chat-ia'), 'description' => __('Hero con contadores de estadísticas', 'flavor-chat-ia')],
                    'minimalista' => ['label' => __('Minimalista', 'flavor-chat-ia'), 'description' => __('Hero limpio y simple', 'flavor-chat-ia')],
                    'con_video' => ['label' => __('Con Video', 'flavor-chat-ia'), 'description' => __('Hero con video embebido', 'flavor-chat-ia')],
                    'con_tarjetas' => ['label' => __('Con Tarjetas', 'flavor-chat-ia'), 'description' => __('Hero con tarjetas flotantes', 'flavor-chat-ia')],
                ],
                'presets' => [
                    'carpooling' => ['label' => __('Carpooling', 'flavor-chat-ia'), 'icon' => 'dashicons-car', 'values' => ['variante' => 'con_buscador', 'titulo' => __('Comparte Viaje', 'flavor-chat-ia'), 'color_primario' => '#3b82f6']],
                    'banco_tiempo' => ['label' => __('Banco de Tiempo', 'flavor-chat-ia'), 'icon' => 'dashicons-clock', 'values' => ['variante' => 'centrado', 'titulo' => __('Banco de Tiempo', 'flavor-chat-ia'), 'color_primario' => '#8b5cf6']],
                    'marketplace' => ['label' => __('Marketplace', 'flavor-chat-ia'), 'icon' => 'dashicons-store', 'values' => ['variante' => 'con_buscador', 'titulo' => __('Marketplace', 'flavor-chat-ia'), 'color_primario' => '#f59e0b']],
                    'empresarial' => ['label' => __('Empresarial', 'flavor-chat-ia'), 'icon' => 'dashicons-building', 'values' => ['variante' => 'split_izquierda', 'titulo' => __('Tu Empresa', 'flavor-chat-ia'), 'color_primario' => '#1d4ed8']],
                ],
                'fields' => [
                    'variante' => ['type' => 'variant_selector', 'label' => __('Variante visual', 'flavor-chat-ia'), 'default' => 'centrado'],
                    'titulo' => ['type' => 'text', 'label' => __('Título', 'flavor-chat-ia'), 'default' => __('Título Principal', 'flavor-chat-ia')],
                    'subtitulo' => ['type' => 'textarea', 'label' => __('Subtítulo', 'flavor-chat-ia'), 'default' => ''],
                    'imagen_fondo' => ['type' => 'image', 'label' => __('Imagen de fondo', 'flavor-chat-ia'), 'default' => ''],
                    'color_primario' => ['type' => 'color', 'label' => __('Color primario', 'flavor-chat-ia'), 'default' => '#3b82f6'],
                    'texto_boton' => ['type' => 'text', 'label' => __('Texto del botón', 'flavor-chat-ia'), 'default' => ''],
                    'url_boton' => ['type' => 'url', 'label' => __('URL del botón', 'flavor-chat-ia'), 'default' => '#'],
                    'texto_boton_secundario' => ['type' => 'text', 'label' => __('Botón secundario', 'flavor-chat-ia'), 'default' => ''],
                    'url_boton_secundario' => ['type' => 'url', 'label' => __('URL botón secundario', 'flavor-chat-ia'), 'default' => '#'],
                    'imagen_lateral' => ['type' => 'image', 'label' => __('Imagen lateral', 'flavor-chat-ia'), 'default' => '', 'show_when' => ['variante' => ['split_izquierda', 'split_derecha']]],
                    'mostrar_buscador' => ['type' => 'toggle', 'label' => __('Mostrar buscador', 'flavor-chat-ia'), 'default' => false, 'show_when' => ['variante' => ['con_buscador']]],
                    'placeholder_buscador' => ['type' => 'text', 'label' => __('Placeholder buscador', 'flavor-chat-ia'), 'default' => __('Buscar...', 'flavor-chat-ia'), 'show_when' => ['mostrar_buscador' => true]],
                    'mostrar_estadisticas' => ['type' => 'toggle', 'label' => __('Mostrar estadísticas', 'flavor-chat-ia'), 'default' => false, 'show_when' => ['variante' => ['con_estadisticas']]],
                    'estadisticas' => ['type' => 'repeater', 'label' => __('Estadísticas', 'flavor-chat-ia'), 'show_when' => ['mostrar_estadisticas' => true], 'max_items' => 4, 'fields' => [
                        'valor' => ['type' => 'text', 'label' => __('Valor', 'flavor-chat-ia'), 'default' => '100+'],
                        'etiqueta' => ['type' => 'text', 'label' => __('Etiqueta', 'flavor-chat-ia'), 'default' => __('Usuarios', 'flavor-chat-ia')],
                    ]],
                    'url_video' => ['type' => 'url', 'label' => __('URL del video', 'flavor-chat-ia'), 'default' => '', 'show_when' => ['variante' => ['con_video']]],
                    'overlay_oscuro' => ['type' => 'toggle', 'label' => __('Overlay oscuro', 'flavor-chat-ia'), 'default' => true],
                ],
                'template' => 'unified/hero',
            ],

            'unified_cta' => [
                'label' => __('Llamada a la Acción', 'flavor-chat-ia'),
                'description' => __('Sección CTA con múltiples layouts', 'flavor-chat-ia'),
                'category' => 'cta',
                'icon' => 'dashicons-megaphone',
                'variants' => [
                    'banner_horizontal' => ['label' => __('Banner Horizontal', 'flavor-chat-ia'), 'description' => __('Banner con texto y botón en fila', 'flavor-chat-ia')],
                    'banner_centrado' => ['label' => __('Banner Centrado', 'flavor-chat-ia'), 'description' => __('Banner centrado con botón', 'flavor-chat-ia')],
                    'card_con_imagen' => ['label' => __('Card con Imagen', 'flavor-chat-ia'), 'description' => __('Tarjeta con imagen lateral', 'flavor-chat-ia')],
                    'flotante' => ['label' => __('Flotante', 'flavor-chat-ia'), 'description' => __('Barra CTA fija en la parte inferior', 'flavor-chat-ia')],
                    'minimalista' => ['label' => __('Minimalista', 'flavor-chat-ia'), 'description' => __('CTA simple inline', 'flavor-chat-ia')],
                ],
                'presets' => [],
                'fields' => [
                    'variante' => ['type' => 'variant_selector', 'label' => __('Variante visual', 'flavor-chat-ia'), 'default' => 'banner_horizontal'],
                    'titulo' => ['type' => 'text', 'label' => __('Título', 'flavor-chat-ia'), 'default' => ''],
                    'descripcion' => ['type' => 'textarea', 'label' => __('Descripción', 'flavor-chat-ia'), 'default' => ''],
                    'texto_boton' => ['type' => 'text', 'label' => __('Texto del botón', 'flavor-chat-ia'), 'default' => __('Empezar', 'flavor-chat-ia')],
                    'url_boton' => ['type' => 'url', 'label' => __('URL del botón', 'flavor-chat-ia'), 'default' => '#'],
                    'color_primario' => ['type' => 'color', 'label' => __('Color primario', 'flavor-chat-ia'), 'default' => '#3b82f6'],
                    'color_fondo' => ['type' => 'color', 'label' => __('Color de fondo', 'flavor-chat-ia'), 'default' => ''],
                    'imagen' => ['type' => 'image', 'label' => __('Imagen', 'flavor-chat-ia'), 'default' => '', 'show_when' => ['variante' => ['card_con_imagen']]],
                    'texto_boton_secundario' => ['type' => 'text', 'label' => __('Botón secundario', 'flavor-chat-ia'), 'default' => ''],
                    'url_boton_secundario' => ['type' => 'url', 'label' => __('URL secundario', 'flavor-chat-ia'), 'default' => '#'],
                ],
                'template' => 'unified/cta',
            ],

            'unified_features' => [
                'label' => __('Características', 'flavor-chat-ia'),
                'description' => __('Sección de características o servicios', 'flavor-chat-ia'),
                'category' => 'features',
                'icon' => 'dashicons-star-filled',
                'variants' => [
                    'grid_iconos' => ['label' => __('Grid con Iconos', 'flavor-chat-ia'), 'description' => __('Grid de tarjetas con icono', 'flavor-chat-ia')],
                    'lista_alternada' => ['label' => __('Lista Alternada', 'flavor-chat-ia'), 'description' => __('Features en zigzag izquierda/derecha', 'flavor-chat-ia')],
                    'tabs' => ['label' => __('Pestañas', 'flavor-chat-ia'), 'description' => __('Contenido en pestañas', 'flavor-chat-ia')],
                    'acordeon' => ['label' => __('Acordeón', 'flavor-chat-ia'), 'description' => __('Features desplegables', 'flavor-chat-ia')],
                ],
                'presets' => [],
                'fields' => [
                    'variante' => ['type' => 'variant_selector', 'label' => __('Variante', 'flavor-chat-ia'), 'default' => 'grid_iconos'],
                    'titulo' => ['type' => 'text', 'label' => __('Título', 'flavor-chat-ia'), 'default' => ''],
                    'subtitulo' => ['type' => 'textarea', 'label' => __('Subtítulo', 'flavor-chat-ia'), 'default' => ''],
                    'color_primario' => ['type' => 'color', 'label' => __('Color primario', 'flavor-chat-ia'), 'default' => '#3b82f6'],
                    'columnas' => ['type' => 'select', 'label' => __('Columnas', 'flavor-chat-ia'), 'options' => [2, 3, 4], 'default' => 3],
                    'items' => ['type' => 'repeater', 'label' => __('Características', 'flavor-chat-ia'), 'max_items' => 12, 'fields' => [
                        'titulo' => ['type' => 'text', 'label' => __('Título', 'flavor-chat-ia'), 'default' => ''],
                        'descripcion' => ['type' => 'textarea', 'label' => __('Descripción', 'flavor-chat-ia'), 'default' => ''],
                        'icono' => ['type' => 'text', 'label' => __('Icono (dashicons)', 'flavor-chat-ia'), 'default' => 'dashicons-star-filled'],
                    ]],
                ],
                'template' => 'unified/features',
            ],

            'unified_grid' => [
                'label' => __('Grid', 'flavor-chat-ia'),
                'description' => __('Grid de tarjetas con múltiples layouts', 'flavor-chat-ia'),
                'category' => 'cards',
                'icon' => 'dashicons-grid-view',
                'variants' => [
                    'cards_imagen' => ['label' => __('Cards con Imagen', 'flavor-chat-ia'), 'description' => __('Tarjetas con imagen superior', 'flavor-chat-ia')],
                    'cards_icono' => ['label' => __('Cards con Icono', 'flavor-chat-ia'), 'description' => __('Tarjetas con icono', 'flavor-chat-ia')],
                    'lista_compacta' => ['label' => __('Lista Compacta', 'flavor-chat-ia'), 'description' => __('Lista vertical compacta', 'flavor-chat-ia')],
                    'masonry' => ['label' => __('Masonry', 'flavor-chat-ia'), 'description' => __('Layout masonry asimétrico', 'flavor-chat-ia')],
                ],
                'presets' => [],
                'fields' => [
                    'variante' => ['type' => 'variant_selector', 'label' => __('Variante', 'flavor-chat-ia'), 'default' => 'cards_imagen'],
                    'titulo' => ['type' => 'text', 'label' => __('Título', 'flavor-chat-ia'), 'default' => ''],
                    'subtitulo' => ['type' => 'textarea', 'label' => __('Subtítulo', 'flavor-chat-ia'), 'default' => ''],
                    'color_primario' => ['type' => 'color', 'label' => __('Color primario', 'flavor-chat-ia'), 'default' => '#3b82f6'],
                    'columnas' => ['type' => 'select', 'label' => __('Columnas', 'flavor-chat-ia'), 'options' => [2, 3, 4], 'default' => 3],
                    'limite' => ['type' => 'number', 'label' => __('Límite', 'flavor-chat-ia'), 'default' => 6],
                    'items' => ['type' => 'repeater', 'label' => __('Items', 'flavor-chat-ia'), 'max_items' => 12, 'fields' => [
                        'titulo' => ['type' => 'text', 'label' => __('Título', 'flavor-chat-ia'), 'default' => ''],
                        'descripcion' => ['type' => 'textarea', 'label' => __('Descripción', 'flavor-chat-ia'), 'default' => ''],
                        'imagen' => ['type' => 'image', 'label' => __('Imagen', 'flavor-chat-ia'), 'default' => ''],
                        'url' => ['type' => 'url', 'label' => __('URL', 'flavor-chat-ia'), 'default' => '#'],
                        'icono' => ['type' => 'text', 'label' => __('Icono', 'flavor-chat-ia'), 'default' => ''],
                    ]],
                ],
                'template' => 'unified/grid',
            ],

            'unified_listing' => [
                'label' => __('Listado con Filtros', 'flavor-chat-ia'),
                'description' => __('Listado filtrable con múltiples vistas', 'flavor-chat-ia'),
                'category' => 'listings',
                'icon' => 'dashicons-list-view',
                'variants' => [
                    'grid_filtrable' => ['label' => __('Grid Filtrable', 'flavor-chat-ia'), 'description' => __('Grid con botones de filtro', 'flavor-chat-ia')],
                    'tabla' => ['label' => __('Tabla', 'flavor-chat-ia'), 'description' => __('Vista de tabla responsiva', 'flavor-chat-ia')],
                    'mapa_y_lista' => ['label' => __('Mapa y Lista', 'flavor-chat-ia'), 'description' => __('Mapa con listado lateral', 'flavor-chat-ia')],
                ],
                'presets' => [],
                'fields' => [
                    'variante' => ['type' => 'variant_selector', 'label' => __('Variante', 'flavor-chat-ia'), 'default' => 'grid_filtrable'],
                    'titulo' => ['type' => 'text', 'label' => __('Título', 'flavor-chat-ia'), 'default' => ''],
                    'subtitulo' => ['type' => 'textarea', 'label' => __('Subtítulo', 'flavor-chat-ia'), 'default' => ''],
                    'color_primario' => ['type' => 'color', 'label' => __('Color primario', 'flavor-chat-ia'), 'default' => '#3b82f6'],
                    'columnas' => ['type' => 'select', 'label' => __('Columnas', 'flavor-chat-ia'), 'options' => [2, 3, 4], 'default' => 3],
                    'limite' => ['type' => 'number', 'label' => __('Límite', 'flavor-chat-ia'), 'default' => 6],
                    'mostrar_filtros' => ['type' => 'toggle', 'label' => __('Mostrar filtros', 'flavor-chat-ia'), 'default' => true],
                    'mostrar_buscador' => ['type' => 'toggle', 'label' => __('Mostrar buscador', 'flavor-chat-ia'), 'default' => false],
                    'items' => ['type' => 'repeater', 'label' => __('Items', 'flavor-chat-ia'), 'max_items' => 20, 'fields' => [
                        'titulo' => ['type' => 'text', 'label' => __('Título', 'flavor-chat-ia'), 'default' => ''],
                        'descripcion' => ['type' => 'textarea', 'label' => __('Descripción', 'flavor-chat-ia'), 'default' => ''],
                        'imagen' => ['type' => 'image', 'label' => __('Imagen', 'flavor-chat-ia'), 'default' => ''],
                        'url' => ['type' => 'url', 'label' => __('URL', 'flavor-chat-ia'), 'default' => '#'],
                    ]],
                ],
                'template' => 'unified/listing',
            ],

            'unified_stats' => [
                'label' => __('Estadísticas', 'flavor-chat-ia'),
                'description' => __('Contadores y estadísticas', 'flavor-chat-ia'),
                'category' => 'content',
                'icon' => 'dashicons-chart-bar',
                'variants' => [
                    'counters_horizontal' => ['label' => __('Horizontal', 'flavor-chat-ia'), 'description' => __('Contadores en fila', 'flavor-chat-ia')],
                    'counters_grid' => ['label' => __('Grid', 'flavor-chat-ia'), 'description' => __('Contadores en cuadrícula', 'flavor-chat-ia')],
                    'con_iconos' => ['label' => __('Con Iconos', 'flavor-chat-ia'), 'description' => __('Contadores con iconos destacados', 'flavor-chat-ia')],
                ],
                'presets' => [],
                'fields' => [
                    'variante' => ['type' => 'variant_selector', 'label' => __('Variante', 'flavor-chat-ia'), 'default' => 'counters_horizontal'],
                    'titulo' => ['type' => 'text', 'label' => __('Título', 'flavor-chat-ia'), 'default' => ''],
                    'subtitulo' => ['type' => 'textarea', 'label' => __('Subtítulo', 'flavor-chat-ia'), 'default' => ''],
                    'color_primario' => ['type' => 'color', 'label' => __('Color primario', 'flavor-chat-ia'), 'default' => '#3b82f6'],
                    'color_fondo' => ['type' => 'color', 'label' => __('Color de fondo', 'flavor-chat-ia'), 'default' => ''],
                    'items' => ['type' => 'repeater', 'label' => __('Estadísticas', 'flavor-chat-ia'), 'max_items' => 6, 'fields' => [
                        'valor' => ['type' => 'text', 'label' => __('Valor', 'flavor-chat-ia'), 'default' => '0'],
                        'etiqueta' => ['type' => 'text', 'label' => __('Etiqueta', 'flavor-chat-ia'), 'default' => ''],
                        'icono' => ['type' => 'text', 'label' => __('Icono', 'flavor-chat-ia'), 'default' => ''],
                    ]],
                ],
                'template' => 'unified/stats',
            ],

            'unified_proceso' => [
                'label' => __('Proceso / Cómo Funciona', 'flavor-chat-ia'),
                'description' => __('Pasos de un proceso o guía', 'flavor-chat-ia'),
                'category' => 'content',
                'icon' => 'dashicons-editor-ol',
                'variants' => [
                    'pasos_horizontal' => ['label' => __('Pasos Horizontal', 'flavor-chat-ia'), 'description' => __('Pasos numerados en fila', 'flavor-chat-ia')],
                    'pasos_vertical' => ['label' => __('Pasos Vertical', 'flavor-chat-ia'), 'description' => __('Pasos en columna vertical', 'flavor-chat-ia')],
                    'timeline' => ['label' => __('Timeline', 'flavor-chat-ia'), 'description' => __('Línea temporal alternada', 'flavor-chat-ia')],
                ],
                'presets' => [],
                'fields' => [
                    'variante' => ['type' => 'variant_selector', 'label' => __('Variante', 'flavor-chat-ia'), 'default' => 'pasos_horizontal'],
                    'titulo' => ['type' => 'text', 'label' => __('Título', 'flavor-chat-ia'), 'default' => __('Cómo Funciona', 'flavor-chat-ia')],
                    'subtitulo' => ['type' => 'textarea', 'label' => __('Subtítulo', 'flavor-chat-ia'), 'default' => ''],
                    'color_primario' => ['type' => 'color', 'label' => __('Color primario', 'flavor-chat-ia'), 'default' => '#3b82f6'],
                    'items' => ['type' => 'repeater', 'label' => __('Pasos', 'flavor-chat-ia'), 'max_items' => 8, 'fields' => [
                        'titulo' => ['type' => 'text', 'label' => __('Título', 'flavor-chat-ia'), 'default' => ''],
                        'descripcion' => ['type' => 'textarea', 'label' => __('Descripción', 'flavor-chat-ia'), 'default' => ''],
                        'icono' => ['type' => 'text', 'label' => __('Icono', 'flavor-chat-ia'), 'default' => ''],
                    ]],
                ],
                'template' => 'unified/proceso',
            ],

            'unified_pricing' => [
                'label' => __('Precios', 'flavor-chat-ia'),
                'description' => __('Tablas de precios y planes', 'flavor-chat-ia'),
                'category' => 'content',
                'icon' => 'dashicons-money-alt',
                'variants' => [
                    'columnas' => ['label' => __('Columnas', 'flavor-chat-ia'), 'description' => __('Planes lado a lado', 'flavor-chat-ia')],
                    'toggle_plan' => ['label' => __('Toggle Mensual/Anual', 'flavor-chat-ia'), 'description' => __('Con alternador de período', 'flavor-chat-ia')],
                    'comparativa' => ['label' => __('Comparativa', 'flavor-chat-ia'), 'description' => __('Tabla comparativa de features', 'flavor-chat-ia')],
                ],
                'presets' => [],
                'fields' => [
                    'variante' => ['type' => 'variant_selector', 'label' => __('Variante', 'flavor-chat-ia'), 'default' => 'columnas'],
                    'titulo' => ['type' => 'text', 'label' => __('Título', 'flavor-chat-ia'), 'default' => __('Planes y Precios', 'flavor-chat-ia')],
                    'subtitulo' => ['type' => 'textarea', 'label' => __('Subtítulo', 'flavor-chat-ia'), 'default' => ''],
                    'color_primario' => ['type' => 'color', 'label' => __('Color primario', 'flavor-chat-ia'), 'default' => '#3b82f6'],
                    'items' => ['type' => 'repeater', 'label' => __('Planes', 'flavor-chat-ia'), 'max_items' => 5, 'fields' => [
                        'nombre' => ['type' => 'text', 'label' => __('Nombre', 'flavor-chat-ia'), 'default' => ''],
                        'precio' => ['type' => 'text', 'label' => __('Precio', 'flavor-chat-ia'), 'default' => '0'],
                        'periodo' => ['type' => 'text', 'label' => __('Período', 'flavor-chat-ia'), 'default' => '/mes'],
                        'caracteristicas' => ['type' => 'textarea', 'label' => __('Características (una por línea)', 'flavor-chat-ia'), 'default' => ''],
                        'destacado' => ['type' => 'toggle', 'label' => __('Destacado', 'flavor-chat-ia'), 'default' => false],
                        'texto_boton' => ['type' => 'text', 'label' => __('Texto botón', 'flavor-chat-ia'), 'default' => __('Elegir Plan', 'flavor-chat-ia')],
                        'url_boton' => ['type' => 'url', 'label' => __('URL botón', 'flavor-chat-ia'), 'default' => '#'],
                    ]],
                ],
                'template' => 'unified/pricing',
            ],

            'unified_equipo' => [
                'label' => __('Equipo', 'flavor-chat-ia'),
                'description' => __('Miembros del equipo', 'flavor-chat-ia'),
                'category' => 'content',
                'icon' => 'dashicons-groups',
                'variants' => [
                    'grid_tarjetas' => ['label' => __('Grid Tarjetas', 'flavor-chat-ia'), 'description' => __('Grid de tarjetas de miembros', 'flavor-chat-ia')],
                    'carrusel' => ['label' => __('Carrusel', 'flavor-chat-ia'), 'description' => __('Carrusel horizontal', 'flavor-chat-ia')],
                    'lista' => ['label' => __('Lista', 'flavor-chat-ia'), 'description' => __('Lista con fotos grandes', 'flavor-chat-ia')],
                ],
                'presets' => [],
                'fields' => [
                    'variante' => ['type' => 'variant_selector', 'label' => __('Variante', 'flavor-chat-ia'), 'default' => 'grid_tarjetas'],
                    'titulo' => ['type' => 'text', 'label' => __('Título', 'flavor-chat-ia'), 'default' => __('Nuestro Equipo', 'flavor-chat-ia')],
                    'subtitulo' => ['type' => 'textarea', 'label' => __('Subtítulo', 'flavor-chat-ia'), 'default' => ''],
                    'color_primario' => ['type' => 'color', 'label' => __('Color primario', 'flavor-chat-ia'), 'default' => '#3b82f6'],
                    'items' => ['type' => 'repeater', 'label' => __('Miembros', 'flavor-chat-ia'), 'max_items' => 12, 'fields' => [
                        'nombre' => ['type' => 'text', 'label' => __('Nombre', 'flavor-chat-ia'), 'default' => ''],
                        'cargo' => ['type' => 'text', 'label' => __('Cargo', 'flavor-chat-ia'), 'default' => ''],
                        'foto' => ['type' => 'image', 'label' => __('Foto', 'flavor-chat-ia'), 'default' => ''],
                        'bio' => ['type' => 'textarea', 'label' => __('Bio', 'flavor-chat-ia'), 'default' => ''],
                    ]],
                ],
                'template' => 'unified/equipo',
            ],

            'unified_mapa' => [
                'label' => __('Mapa', 'flavor-chat-ia'),
                'description' => __('Mapa con ubicaciones', 'flavor-chat-ia'),
                'category' => 'content',
                'icon' => 'dashicons-location-alt',
                'variants' => [
                    'embed_simple' => ['label' => __('Embed Simple', 'flavor-chat-ia'), 'description' => __('Mapa embebido básico', 'flavor-chat-ia')],
                    'con_marcadores' => ['label' => __('Con Marcadores', 'flavor-chat-ia'), 'description' => __('Mapa con lista de ubicaciones', 'flavor-chat-ia')],
                    'con_sidebar' => ['label' => __('Con Sidebar', 'flavor-chat-ia'), 'description' => __('Mapa con panel lateral', 'flavor-chat-ia')],
                ],
                'presets' => [],
                'fields' => [
                    'variante' => ['type' => 'variant_selector', 'label' => __('Variante', 'flavor-chat-ia'), 'default' => 'embed_simple'],
                    'titulo' => ['type' => 'text', 'label' => __('Título', 'flavor-chat-ia'), 'default' => ''],
                    'subtitulo' => ['type' => 'textarea', 'label' => __('Subtítulo', 'flavor-chat-ia'), 'default' => ''],
                    'color_primario' => ['type' => 'color', 'label' => __('Color primario', 'flavor-chat-ia'), 'default' => '#3b82f6'],
                    'direccion' => ['type' => 'text', 'label' => __('Dirección', 'flavor-chat-ia'), 'default' => ''],
                    'latitud' => ['type' => 'text', 'label' => __('Latitud', 'flavor-chat-ia'), 'default' => ''],
                    'longitud' => ['type' => 'text', 'label' => __('Longitud', 'flavor-chat-ia'), 'default' => ''],
                    'zoom' => ['type' => 'number', 'label' => __('Zoom', 'flavor-chat-ia'), 'default' => 14],
                    'altura' => ['type' => 'text', 'label' => __('Altura', 'flavor-chat-ia'), 'default' => '400px'],
                ],
                'template' => 'unified/mapa',
            ],

            'unified_testimonios' => [
                'label' => __('Testimonios', 'flavor-chat-ia'),
                'description' => __('Testimonios y opiniones', 'flavor-chat-ia'),
                'category' => 'testimonials',
                'icon' => 'dashicons-format-quote',
                'variants' => [
                    'carrusel' => ['label' => __('Carrusel', 'flavor-chat-ia'), 'description' => __('Carrusel de testimonios', 'flavor-chat-ia')],
                    'grid' => ['label' => __('Grid', 'flavor-chat-ia'), 'description' => __('Grid de tarjetas', 'flavor-chat-ia')],
                    'quotes' => ['label' => __('Citas', 'flavor-chat-ia'), 'description' => __('Citas grandes destacadas', 'flavor-chat-ia')],
                ],
                'presets' => [],
                'fields' => [
                    'variante' => ['type' => 'variant_selector', 'label' => __('Variante', 'flavor-chat-ia'), 'default' => 'carrusel'],
                    'titulo' => ['type' => 'text', 'label' => __('Título', 'flavor-chat-ia'), 'default' => __('Testimonios', 'flavor-chat-ia')],
                    'subtitulo' => ['type' => 'textarea', 'label' => __('Subtítulo', 'flavor-chat-ia'), 'default' => ''],
                    'color_primario' => ['type' => 'color', 'label' => __('Color primario', 'flavor-chat-ia'), 'default' => '#3b82f6'],
                    'items' => ['type' => 'repeater', 'label' => __('Testimonios', 'flavor-chat-ia'), 'max_items' => 10, 'fields' => [
                        'nombre' => ['type' => 'text', 'label' => __('Nombre', 'flavor-chat-ia'), 'default' => ''],
                        'cargo' => ['type' => 'text', 'label' => __('Cargo', 'flavor-chat-ia'), 'default' => ''],
                        'texto' => ['type' => 'textarea', 'label' => __('Texto', 'flavor-chat-ia'), 'default' => ''],
                        'foto' => ['type' => 'image', 'label' => __('Foto', 'flavor-chat-ia'), 'default' => ''],
                    ]],
                ],
                'template' => 'unified/testimonios',
            ],

            'unified_faq' => [
                'label' => __('Preguntas Frecuentes', 'flavor-chat-ia'),
                'description' => __('Sección de FAQ', 'flavor-chat-ia'),
                'category' => 'content',
                'icon' => 'dashicons-editor-help',
                'variants' => [
                    'acordeon' => ['label' => __('Acordeón', 'flavor-chat-ia'), 'description' => __('FAQ colapsable', 'flavor-chat-ia')],
                    'dos_columnas' => ['label' => __('Dos Columnas', 'flavor-chat-ia'), 'description' => __('FAQ en dos columnas', 'flavor-chat-ia')],
                    'con_buscador' => ['label' => __('Con Buscador', 'flavor-chat-ia'), 'description' => __('FAQ con barra de búsqueda', 'flavor-chat-ia')],
                ],
                'presets' => [],
                'fields' => [
                    'variante' => ['type' => 'variant_selector', 'label' => __('Variante', 'flavor-chat-ia'), 'default' => 'acordeon'],
                    'titulo' => ['type' => 'text', 'label' => __('Título', 'flavor-chat-ia'), 'default' => __('Preguntas Frecuentes', 'flavor-chat-ia')],
                    'subtitulo' => ['type' => 'textarea', 'label' => __('Subtítulo', 'flavor-chat-ia'), 'default' => ''],
                    'color_primario' => ['type' => 'color', 'label' => __('Color primario', 'flavor-chat-ia'), 'default' => '#3b82f6'],
                    'mostrar_buscador' => ['type' => 'toggle', 'label' => __('Mostrar buscador', 'flavor-chat-ia'), 'default' => false, 'show_when' => ['variante' => ['con_buscador']]],
                    'items' => ['type' => 'repeater', 'label' => __('Preguntas', 'flavor-chat-ia'), 'max_items' => 20, 'fields' => [
                        'pregunta' => ['type' => 'text', 'label' => __('Pregunta', 'flavor-chat-ia'), 'default' => ''],
                        'respuesta' => ['type' => 'textarea', 'label' => __('Respuesta', 'flavor-chat-ia'), 'default' => ''],
                    ]],
                ],
                'template' => 'unified/faq',
            ],

            'unified_contacto' => [
                'label' => __('Contacto', 'flavor-chat-ia'),
                'description' => __('Formulario de contacto', 'flavor-chat-ia'),
                'category' => 'forms',
                'icon' => 'dashicons-email-alt',
                'variants' => [
                    'formulario_simple' => ['label' => __('Simple', 'flavor-chat-ia'), 'description' => __('Formulario de contacto simple', 'flavor-chat-ia')],
                    'split_con_mapa' => ['label' => __('Con Mapa', 'flavor-chat-ia'), 'description' => __('Formulario con mapa lateral', 'flavor-chat-ia')],
                    'con_info' => ['label' => __('Con Info', 'flavor-chat-ia'), 'description' => __('Formulario con info de contacto', 'flavor-chat-ia')],
                ],
                'presets' => [],
                'fields' => [
                    'variante' => ['type' => 'variant_selector', 'label' => __('Variante', 'flavor-chat-ia'), 'default' => 'formulario_simple'],
                    'titulo' => ['type' => 'text', 'label' => __('Título', 'flavor-chat-ia'), 'default' => __('Contacto', 'flavor-chat-ia')],
                    'subtitulo' => ['type' => 'textarea', 'label' => __('Subtítulo', 'flavor-chat-ia'), 'default' => ''],
                    'color_primario' => ['type' => 'color', 'label' => __('Color primario', 'flavor-chat-ia'), 'default' => '#3b82f6'],
                    'email_destino' => ['type' => 'text', 'label' => __('Email destino', 'flavor-chat-ia'), 'default' => ''],
                    'mostrar_telefono' => ['type' => 'toggle', 'label' => __('Mostrar teléfono', 'flavor-chat-ia'), 'default' => false, 'show_when' => ['variante' => ['con_info']]],
                    'telefono' => ['type' => 'text', 'label' => __('Teléfono', 'flavor-chat-ia'), 'default' => '', 'show_when' => ['mostrar_telefono' => true]],
                    'mostrar_direccion' => ['type' => 'toggle', 'label' => __('Mostrar dirección', 'flavor-chat-ia'), 'default' => false, 'show_when' => ['variante' => ['con_info', 'split_con_mapa']]],
                    'direccion' => ['type' => 'text', 'label' => __('Dirección', 'flavor-chat-ia'), 'default' => '', 'show_when' => ['mostrar_direccion' => true]],
                ],
                'template' => 'unified/contacto',
            ],

            'unified_galeria' => [
                'label' => __('Galería', 'flavor-chat-ia'),
                'description' => __('Galería de imágenes o portfolio', 'flavor-chat-ia'),
                'category' => 'content',
                'icon' => 'dashicons-format-gallery',
                'variants' => [
                    'grid_masonry' => ['label' => __('Masonry', 'flavor-chat-ia'), 'description' => __('Grid masonry asimétrico', 'flavor-chat-ia')],
                    'carrusel' => ['label' => __('Carrusel', 'flavor-chat-ia'), 'description' => __('Carrusel horizontal', 'flavor-chat-ia')],
                    'lightbox' => ['label' => __('Lightbox', 'flavor-chat-ia'), 'description' => __('Grid con lightbox al hacer click', 'flavor-chat-ia')],
                ],
                'presets' => [],
                'fields' => [
                    'variante' => ['type' => 'variant_selector', 'label' => __('Variante', 'flavor-chat-ia'), 'default' => 'grid_masonry'],
                    'titulo' => ['type' => 'text', 'label' => __('Título', 'flavor-chat-ia'), 'default' => ''],
                    'subtitulo' => ['type' => 'textarea', 'label' => __('Subtítulo', 'flavor-chat-ia'), 'default' => ''],
                    'color_primario' => ['type' => 'color', 'label' => __('Color primario', 'flavor-chat-ia'), 'default' => '#3b82f6'],
                    'columnas' => ['type' => 'select', 'label' => __('Columnas', 'flavor-chat-ia'), 'options' => [2, 3, 4], 'default' => 3],
                    'items' => ['type' => 'repeater', 'label' => __('Imágenes', 'flavor-chat-ia'), 'max_items' => 20, 'fields' => [
                        'imagen' => ['type' => 'image', 'label' => __('Imagen', 'flavor-chat-ia'), 'default' => ''],
                        'titulo' => ['type' => 'text', 'label' => __('Título', 'flavor-chat-ia'), 'default' => ''],
                        'descripcion' => ['type' => 'textarea', 'label' => __('Descripción', 'flavor-chat-ia'), 'default' => ''],
                    ]],
                ],
                'template' => 'unified/galeria',
            ],

            'unified_calendario' => [
                'label' => __('Calendario', 'flavor-chat-ia'),
                'description' => __('Calendario de eventos', 'flavor-chat-ia'),
                'category' => 'content',
                'icon' => 'dashicons-calendar-alt',
                'variants' => [
                    'mensual' => ['label' => __('Mensual', 'flavor-chat-ia'), 'description' => __('Vista mensual cuadrícula', 'flavor-chat-ia')],
                    'lista_eventos' => ['label' => __('Lista', 'flavor-chat-ia'), 'description' => __('Lista cronológica de eventos', 'flavor-chat-ia')],
                    'agenda' => ['label' => __('Agenda', 'flavor-chat-ia'), 'description' => __('Vista de agenda diaria', 'flavor-chat-ia')],
                ],
                'presets' => [],
                'fields' => [
                    'variante' => ['type' => 'variant_selector', 'label' => __('Variante', 'flavor-chat-ia'), 'default' => 'mensual'],
                    'titulo' => ['type' => 'text', 'label' => __('Título', 'flavor-chat-ia'), 'default' => __('Calendario', 'flavor-chat-ia')],
                    'subtitulo' => ['type' => 'textarea', 'label' => __('Subtítulo', 'flavor-chat-ia'), 'default' => ''],
                    'color_primario' => ['type' => 'color', 'label' => __('Color primario', 'flavor-chat-ia'), 'default' => '#3b82f6'],
                    'eventos' => ['type' => 'repeater', 'label' => __('Eventos', 'flavor-chat-ia'), 'max_items' => 20, 'fields' => [
                        'titulo' => ['type' => 'text', 'label' => __('Título', 'flavor-chat-ia'), 'default' => ''],
                        'fecha' => ['type' => 'text', 'label' => __('Fecha', 'flavor-chat-ia'), 'default' => ''],
                        'descripcion' => ['type' => 'textarea', 'label' => __('Descripción', 'flavor-chat-ia'), 'default' => ''],
                        'lugar' => ['type' => 'text', 'label' => __('Lugar', 'flavor-chat-ia'), 'default' => ''],
                    ]],
                ],
                'template' => 'unified/calendario',
            ],

            'unified_newsletter' => [
                'label' => __('Newsletter', 'flavor-chat-ia'),
                'description' => __('Formulario de suscripción', 'flavor-chat-ia'),
                'category' => 'forms',
                'icon' => 'dashicons-email',
                'variants' => [
                    'inline' => ['label' => __('Inline', 'flavor-chat-ia'), 'description' => __('Formulario horizontal inline', 'flavor-chat-ia')],
                    'card_centrada' => ['label' => __('Card Centrada', 'flavor-chat-ia'), 'description' => __('Tarjeta centrada con formulario', 'flavor-chat-ia')],
                    'con_beneficios' => ['label' => __('Con Beneficios', 'flavor-chat-ia'), 'description' => __('Formulario con lista de beneficios', 'flavor-chat-ia')],
                ],
                'presets' => [],
                'fields' => [
                    'variante' => ['type' => 'variant_selector', 'label' => __('Variante', 'flavor-chat-ia'), 'default' => 'inline'],
                    'titulo' => ['type' => 'text', 'label' => __('Título', 'flavor-chat-ia'), 'default' => __('Suscríbete', 'flavor-chat-ia')],
                    'subtitulo' => ['type' => 'textarea', 'label' => __('Subtítulo', 'flavor-chat-ia'), 'default' => ''],
                    'color_primario' => ['type' => 'color', 'label' => __('Color primario', 'flavor-chat-ia'), 'default' => '#3b82f6'],
                    'texto_boton' => ['type' => 'text', 'label' => __('Texto del botón', 'flavor-chat-ia'), 'default' => __('Suscribirse', 'flavor-chat-ia')],
                    'placeholder' => ['type' => 'text', 'label' => __('Placeholder', 'flavor-chat-ia'), 'default' => __('Tu email', 'flavor-chat-ia')],
                    'beneficios' => ['type' => 'repeater', 'label' => __('Beneficios', 'flavor-chat-ia'), 'max_items' => 5, 'show_when' => ['variante' => ['con_beneficios']], 'fields' => [
                        'texto' => ['type' => 'text', 'label' => __('Beneficio', 'flavor-chat-ia'), 'default' => ''],
                    ]],
                ],
                'template' => 'unified/newsletter',
            ],

            'unified_contenido' => [
                'label' => __('Contenido', 'flavor-chat-ia'),
                'description' => __('Bloque de contenido genérico', 'flavor-chat-ia'),
                'category' => 'content',
                'icon' => 'dashicons-text-page',
                'variants' => [
                    'texto_simple' => ['label' => __('Texto Simple', 'flavor-chat-ia'), 'description' => __('Bloque de texto con título', 'flavor-chat-ia')],
                    'texto_con_imagen' => ['label' => __('Con Imagen', 'flavor-chat-ia'), 'description' => __('Texto con imagen lateral', 'flavor-chat-ia')],
                    'dos_columnas' => ['label' => __('Dos Columnas', 'flavor-chat-ia'), 'description' => __('Texto en dos columnas', 'flavor-chat-ia')],
                    'video' => ['label' => __('Video', 'flavor-chat-ia'), 'description' => __('Video embebido con texto', 'flavor-chat-ia')],
                ],
                'presets' => [],
                'fields' => [
                    'variante' => ['type' => 'variant_selector', 'label' => __('Variante', 'flavor-chat-ia'), 'default' => 'texto_simple'],
                    'titulo' => ['type' => 'text', 'label' => __('Título', 'flavor-chat-ia'), 'default' => ''],
                    'subtitulo' => ['type' => 'textarea', 'label' => __('Subtítulo', 'flavor-chat-ia'), 'default' => ''],
                    'contenido' => ['type' => 'textarea', 'label' => __('Contenido', 'flavor-chat-ia'), 'default' => ''],
                    'color_primario' => ['type' => 'color', 'label' => __('Color primario', 'flavor-chat-ia'), 'default' => '#3b82f6'],
                    'imagen' => ['type' => 'image', 'label' => __('Imagen', 'flavor-chat-ia'), 'default' => '', 'show_when' => ['variante' => ['texto_con_imagen']]],
                    'posicion_imagen' => ['type' => 'select', 'label' => __('Posición imagen', 'flavor-chat-ia'), 'options' => ['izquierda', 'derecha'], 'default' => 'derecha', 'show_when' => ['variante' => ['texto_con_imagen']]],
                    'url_video' => ['type' => 'url', 'label' => __('URL del video', 'flavor-chat-ia'), 'default' => '', 'show_when' => ['variante' => ['video']]],
                ],
                'template' => 'unified/contenido',
            ],

            'unified_navegacion' => [
                'label' => __('Navegación', 'flavor-chat-ia'),
                'description' => __('Navegación por categorías o filtros', 'flavor-chat-ia'),
                'category' => 'navigation',
                'icon' => 'dashicons-menu-alt3',
                'variants' => [
                    'tabs_horizontal' => ['label' => __('Tabs', 'flavor-chat-ia'), 'description' => __('Pestañas horizontales', 'flavor-chat-ia')],
                    'pills' => ['label' => __('Pills', 'flavor-chat-ia'), 'description' => __('Botones tipo pill', 'flavor-chat-ia')],
                    'sidebar_filtros' => ['label' => __('Sidebar', 'flavor-chat-ia'), 'description' => __('Filtros en sidebar vertical', 'flavor-chat-ia')],
                ],
                'presets' => [],
                'fields' => [
                    'variante' => ['type' => 'variant_selector', 'label' => __('Variante', 'flavor-chat-ia'), 'default' => 'tabs_horizontal'],
                    'titulo' => ['type' => 'text', 'label' => __('Título', 'flavor-chat-ia'), 'default' => ''],
                    'color_primario' => ['type' => 'color', 'label' => __('Color primario', 'flavor-chat-ia'), 'default' => '#3b82f6'],
                    'items' => ['type' => 'repeater', 'label' => __('Elementos', 'flavor-chat-ia'), 'max_items' => 10, 'fields' => [
                        'label' => ['type' => 'text', 'label' => __('Texto', 'flavor-chat-ia'), 'default' => ''],
                        'url' => ['type' => 'url', 'label' => __('URL', 'flavor-chat-ia'), 'default' => '#'],
                        'icono' => ['type' => 'text', 'label' => __('Icono', 'flavor-chat-ia'), 'default' => ''],
                    ]],
                ],
                'template' => 'unified/navegacion',
            ],

            'unified_form' => [
                'label' => __('Formulario', 'flavor-chat-ia'),
                'description' => __('Formulario genérico personalizable', 'flavor-chat-ia'),
                'category' => 'forms',
                'icon' => 'dashicons-feedback',
                'variants' => [
                    'simple' => ['label' => __('Simple', 'flavor-chat-ia'), 'description' => __('Formulario de un paso', 'flavor-chat-ia')],
                    'multi_paso' => ['label' => __('Multi-Paso', 'flavor-chat-ia'), 'description' => __('Formulario wizard', 'flavor-chat-ia')],
                ],
                'presets' => [],
                'fields' => [
                    'variante' => ['type' => 'variant_selector', 'label' => __('Variante', 'flavor-chat-ia'), 'default' => 'simple'],
                    'titulo' => ['type' => 'text', 'label' => __('Título', 'flavor-chat-ia'), 'default' => ''],
                    'subtitulo' => ['type' => 'textarea', 'label' => __('Subtítulo', 'flavor-chat-ia'), 'default' => ''],
                    'color_primario' => ['type' => 'color', 'label' => __('Color primario', 'flavor-chat-ia'), 'default' => '#3b82f6'],
                    'texto_boton' => ['type' => 'text', 'label' => __('Texto del botón', 'flavor-chat-ia'), 'default' => __('Enviar', 'flavor-chat-ia')],
                    'campos' => ['type' => 'repeater', 'label' => __('Campos', 'flavor-chat-ia'), 'max_items' => 15, 'fields' => [
                        'nombre' => ['type' => 'text', 'label' => __('Nombre del campo', 'flavor-chat-ia'), 'default' => ''],
                        'tipo' => ['type' => 'select', 'label' => __('Tipo', 'flavor-chat-ia'), 'options' => ['text', 'email', 'textarea', 'select', 'number', 'tel'], 'default' => 'text'],
                        'label' => ['type' => 'text', 'label' => __('Etiqueta', 'flavor-chat-ia'), 'default' => ''],
                        'requerido' => ['type' => 'toggle', 'label' => __('Requerido', 'flavor-chat-ia'), 'default' => false],
                    ]],
                ],
                'template' => 'unified/form',
            ],

            'unified_footer' => [
                'label' => __('Footer', 'flavor-chat-ia'),
                'description' => __('Pie de página', 'flavor-chat-ia'),
                'category' => 'footer',
                'icon' => 'dashicons-minus',
                'variants' => [
                    'simple' => ['label' => __('Simple', 'flavor-chat-ia'), 'description' => __('Footer simple con copyright', 'flavor-chat-ia')],
                    'multi_columna' => ['label' => __('Multi-Columna', 'flavor-chat-ia'), 'description' => __('Footer con columnas de enlaces', 'flavor-chat-ia')],
                ],
                'presets' => [],
                'fields' => [
                    'variante' => ['type' => 'variant_selector', 'label' => __('Variante', 'flavor-chat-ia'), 'default' => 'simple'],
                    'texto_copyright' => ['type' => 'text', 'label' => __('Copyright', 'flavor-chat-ia'), 'default' => ''],
                    'color_fondo' => ['type' => 'color', 'label' => __('Color de fondo', 'flavor-chat-ia'), 'default' => '#1f2937'],
                    'color_texto' => ['type' => 'color', 'label' => __('Color de texto', 'flavor-chat-ia'), 'default' => '#ffffff'],
                    'logo' => ['type' => 'image', 'label' => __('Logo', 'flavor-chat-ia'), 'default' => ''],
                    'redes_sociales' => ['type' => 'repeater', 'label' => __('Redes Sociales', 'flavor-chat-ia'), 'max_items' => 6, 'fields' => [
                        'nombre' => ['type' => 'text', 'label' => __('Red social', 'flavor-chat-ia'), 'default' => ''],
                        'url' => ['type' => 'url', 'label' => __('URL', 'flavor-chat-ia'), 'default' => '#'],
                    ]],
                    'columnas' => ['type' => 'repeater', 'label' => __('Columnas', 'flavor-chat-ia'), 'max_items' => 4, 'show_when' => ['variante' => ['multi_columna']], 'fields' => [
                        'titulo' => ['type' => 'text', 'label' => __('Título columna', 'flavor-chat-ia'), 'default' => ''],
                        'enlaces' => ['type' => 'textarea', 'label' => __('Enlaces (uno por línea: texto|url)', 'flavor-chat-ia'), 'default' => ''],
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
            'frontend-landings' => __('Landings Generales', 'flavor-chat-ia'),
            'bicicletas_compartidas' => __('Bicicletas Compartidas', 'flavor-chat-ia'),
            'colectivos' => __('Colectivos', 'flavor-chat-ia'),
            'foros' => __('Foros', 'flavor-chat-ia'),
            'clientes' => __('Clientes', 'flavor-chat-ia'),
            'comunidades' => __('Comunidades', 'flavor-chat-ia'),
            'bares' => __('Bares', 'flavor-chat-ia'),
            'trading_ia' => __('Trading IA', 'flavor-chat-ia'),
            'dex_solana' => __('DEX Solana', 'flavor-chat-ia'),
            'themacle' => __('Themacle', 'flavor-chat-ia'),
            'reservas' => __('Reservas', 'flavor-chat-ia'),
            'eventos' => __('Eventos', 'flavor-chat-ia'),
            'donaciones' => __('Donaciones', 'flavor-chat-ia'),
            'inventario' => __('Inventario', 'flavor-chat-ia'),
            'encuestas' => __('Encuestas', 'flavor-chat-ia'),
            'voluntariado' => __('Voluntariado', 'flavor-chat-ia'),
            'marketplace' => __('Marketplace', 'flavor-chat-ia'),
            'formacion' => __('Formación', 'flavor-chat-ia'),
            'proyectos' => __('Proyectos', 'flavor-chat-ia'),
            'red_apoyo_mutuo' => __('Red Apoyo Mutuo', 'flavor-chat-ia'),
            'agenda' => __('Agenda', 'flavor-chat-ia'),
            'biblioteca' => __('Biblioteca', 'flavor-chat-ia'),
            'huertos_comunitarios' => __('Huertos Comunitarios', 'flavor-chat-ia'),
            'banco_tiempo' => __('Banco de Tiempo', 'flavor-chat-ia'),
            'moneda_local' => __('Moneda Local', 'flavor-chat-ia'),
            'energia_comunitaria' => __('Energía Comunitaria', 'flavor-chat-ia'),
            'reciclaje' => __('Reciclaje', 'flavor-chat-ia'),
            'transporte_compartido' => __('Transporte Compartido', 'flavor-chat-ia'),
            'cuidados' => __('Cuidados', 'flavor-chat-ia'),
            'vivienda_compartida' => __('Vivienda Compartida', 'flavor-chat-ia'),
            'grupos_consumo' => __('Grupos de Consumo', 'flavor-chat-ia'),
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

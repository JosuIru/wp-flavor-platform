<?php
/**
 * API de Layouts para Apps Nativas
 *
 * Expone los layouts del Page Builder via REST API para que
 * las aplicaciones móviles puedan renderizarlos nativamente.
 *
 * @package Flavor_Chat_IA
 * @subpackage App_Integration
 */

if (!defined('ABSPATH')) {
    exit;
}

class Flavor_App_Layouts_API {

    /**
     * Instancia singleton
     */
    private static $instance = null;

    /**
     * Namespace de la API
     */
    const API_NAMESPACE = 'flavor-app/v1';

    /**
     * Versión del schema de componentes
     */
    const SCHEMA_VERSION = '1.0.0';

    /**
     * Mapeo de componentes web a nativos
     */
    private $component_mapping = [];

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
     * Constructor
     */
    private function __construct() {
        $this->init_component_mapping();
        add_action('rest_api_init', [$this, 'register_routes']);
    }

    /**
     * Inicializar mapeo de componentes web a nativos
     */
    private function init_component_mapping() {
        $this->component_mapping = [
            // Heroes
            'hero' => [
                'native_type' => 'hero_banner',
                'supports' => ['image', 'video', 'gradient'],
                'fields_map' => [
                    'titulo' => 'title',
                    'subtitulo' => 'subtitle',
                    'imagen_fondo' => 'background_image',
                    'texto_boton_principal' => 'primary_button_text',
                    'url_boton_principal' => 'primary_button_url',
                    'texto_boton_secundario' => 'secondary_button_text',
                    'url_boton_secundario' => 'secondary_button_url',
                ],
            ],

            // Grids
            'grid' => [
                'native_type' => 'item_grid',
                'supports' => ['lazy_loading', 'pagination', 'pull_refresh'],
                'fields_map' => [
                    'titulo' => 'title',
                    'columnas' => 'columns',
                    'limite' => 'limit',
                    'mostrar_filtros' => 'show_filters',
                ],
            ],

            // Listas
            'lista' => [
                'native_type' => 'item_list',
                'supports' => ['lazy_loading', 'swipe_actions', 'sections'],
                'fields_map' => [
                    'titulo' => 'title',
                    'limite' => 'limit',
                    'mostrar_iconos' => 'show_icons',
                ],
            ],

            // Mapas
            'mapa' => [
                'native_type' => 'map_view',
                'supports' => ['markers', 'clusters', 'directions', 'user_location'],
                'fields_map' => [
                    'titulo' => 'title',
                    'altura_mapa' => 'map_height',
                    'zoom_inicial' => 'initial_zoom',
                    'mostrar_mi_ubicacion' => 'show_user_location',
                ],
            ],

            // Cards
            'card' => [
                'native_type' => 'info_card',
                'supports' => ['image', 'actions', 'expandable'],
                'fields_map' => [
                    'titulo' => 'title',
                    'descripcion' => 'description',
                    'imagen' => 'image',
                    'icono' => 'icon',
                ],
            ],

            // Estadísticas
            'estadisticas' => [
                'native_type' => 'stats_row',
                'supports' => ['animation', 'icons'],
                'fields_map' => [
                    'titulo' => 'title',
                    'items' => 'stats_items',
                ],
            ],

            // CTA (Call to Action)
            'cta' => [
                'native_type' => 'action_banner',
                'supports' => ['gradient', 'icon'],
                'fields_map' => [
                    'titulo' => 'title',
                    'subtitulo' => 'subtitle',
                    'descripcion' => 'description',
                    'texto_boton' => 'button_text',
                    'url_boton' => 'button_url',
                    'boton_texto' => 'button_text',
                    'boton_url' => 'button_url',
                ],
            ],

            // Categorías
            'categorias' => [
                'native_type' => 'category_chips',
                'supports' => ['icons', 'badges', 'horizontal_scroll'],
                'fields_map' => [
                    'titulo' => 'title',
                    'categorias' => 'categories',
                    'mostrar_iconos' => 'show_icons',
                ],
            ],

            // Calendario
            'calendario' => [
                'native_type' => 'calendar_view',
                'supports' => ['month_view', 'week_view', 'day_view', 'events'],
                'fields_map' => [
                    'titulo' => 'title',
                    'vista_inicial' => 'initial_view',
                ],
            ],

            // Formularios
            'formulario' => [
                'native_type' => 'form_view',
                'supports' => ['validation', 'file_upload', 'signature'],
                'fields_map' => [
                    'titulo' => 'title',
                    'campos' => 'fields',
                    'texto_enviar' => 'submit_text',
                ],
            ],

            // Buscador
            'buscador' => [
                'native_type' => 'search_bar',
                'supports' => ['suggestions', 'voice', 'filters'],
                'fields_map' => [
                    'placeholder' => 'placeholder',
                    'mostrar_filtros' => 'show_filters',
                ],
            ],

            // Tabs/Pestañas
            'tabs' => [
                'native_type' => 'tab_view',
                'supports' => ['swipe', 'badges', 'icons'],
                'fields_map' => [
                    'tabs' => 'tabs',
                    'tab_activa' => 'active_tab',
                ],
            ],

            // Carrusel
            'carousel' => [
                'native_type' => 'carousel_view',
                'supports' => ['autoplay', 'indicators', 'infinite'],
                'fields_map' => [
                    'titulo' => 'title',
                    'items' => 'items',
                    'autoplay' => 'autoplay',
                    'intervalo_segundos' => 'interval_seconds',
                ],
            ],

            // Galería
            'galeria' => [
                'native_type' => 'photo_gallery',
                'supports' => ['grid', 'masonry', 'lightbox', 'zoom'],
                'fields_map' => [
                    'titulo' => 'title',
                    'columnas' => 'columns',
                    'limite' => 'limit',
                ],
            ],

            // Player de audio/video
            'player' => [
                'native_type' => 'media_player',
                'supports' => ['audio', 'video', 'streaming', 'background_play'],
                'fields_map' => [
                    'titulo' => 'title',
                    'url_stream' => 'stream_url',
                    'tipo' => 'media_type',
                ],
            ],

            // Timeline/Proceso
            'proceso' => [
                'native_type' => 'timeline_view',
                'supports' => ['vertical', 'horizontal', 'icons'],
                'fields_map' => [
                    'titulo' => 'title',
                    'pasos' => 'steps',
                ],
            ],

            // Testimonios
            'testimonios' => [
                'native_type' => 'testimonial_carousel',
                'supports' => ['avatar', 'rating', 'autoplay'],
                'fields_map' => [
                    'titulo' => 'title',
                    'limite' => 'limit',
                ],
            ],

            // Perfil/Usuario
            'perfil' => [
                'native_type' => 'profile_header',
                'supports' => ['avatar', 'cover', 'stats', 'actions'],
                'fields_map' => [
                    'mostrar_avatar' => 'show_avatar',
                    'mostrar_estadisticas' => 'show_stats',
                ],
            ],

            // Notificaciones/Alertas
            'alerta' => [
                'native_type' => 'alert_banner',
                'supports' => ['dismissible', 'action', 'icon'],
                'fields_map' => [
                    'titulo' => 'title',
                    'mensaje' => 'message',
                    'tipo' => 'alert_type',
                ],
            ],

            // Acordeón/FAQ
            'acordeon' => [
                'native_type' => 'expandable_list',
                'supports' => ['single_expand', 'icons', 'search'],
                'fields_map' => [
                    'titulo' => 'title',
                    'items' => 'items',
                ],
            ],

            // Pricing/Tarifas
            'tarifas' => [
                'native_type' => 'pricing_cards',
                'supports' => ['comparison', 'highlighted', 'toggle'],
                'fields_map' => [
                    'titulo' => 'title',
                    'planes' => 'plans',
                ],
            ],

            // Contador/Timer
            'contador' => [
                'native_type' => 'countdown_timer',
                'supports' => ['days', 'hours', 'minutes', 'seconds'],
                'fields_map' => [
                    'titulo' => 'title',
                    'fecha_fin' => 'end_date',
                ],
            ],

            // Equipo/Personas
            'equipo' => [
                'native_type' => 'team_grid',
                'supports' => ['avatar', 'social_links', 'modal'],
                'fields_map' => [
                    'titulo' => 'title',
                    'limite' => 'limit',
                    'mostrar_cargo' => 'show_role',
                ],
            ],
        ];

        // Permitir que los módulos extiendan el mapeo
        $this->component_mapping = apply_filters('flavor_app_component_mapping', $this->component_mapping);
    }

    /**
     * Registrar rutas de la API
     */
    public function register_routes() {
        // Schema de componentes
        register_rest_route(self::API_NAMESPACE, '/layouts/schema', [
            'methods' => WP_REST_Server::READABLE,
            'callback' => [$this, 'get_component_schema'],
            'permission_callback' => '__return_true',
        ]);

        // Plantillas disponibles
        register_rest_route(self::API_NAMESPACE, '/layouts/templates', [
            'methods' => WP_REST_Server::READABLE,
            'callback' => [$this, 'get_templates'],
            'permission_callback' => '__return_true',
            'args' => [
                'sector' => [
                    'type' => 'string',
                    'required' => false,
                ],
            ],
        ]);

        // Obtener plantilla específica
        register_rest_route(self::API_NAMESPACE, '/layouts/templates/(?P<template_id>[a-z0-9_-]+)', [
            'methods' => WP_REST_Server::READABLE,
            'callback' => [$this, 'get_template'],
            'permission_callback' => '__return_true',
        ]);

        // Layout de una landing publicada
        register_rest_route(self::API_NAMESPACE, '/layouts/landing/(?P<id>\d+)', [
            'methods' => WP_REST_Server::READABLE,
            'callback' => [$this, 'get_landing_layout'],
            'permission_callback' => '__return_true',
        ]);

        // Layout por slug
        register_rest_route(self::API_NAMESPACE, '/layouts/landing/slug/(?P<slug>[a-z0-9-]+)', [
            'methods' => WP_REST_Server::READABLE,
            'callback' => [$this, 'get_landing_by_slug'],
            'permission_callback' => '__return_true',
        ]);

        // Layouts de un módulo específico
        register_rest_route(self::API_NAMESPACE, '/layouts/module/(?P<module_id>[a-z0-9_-]+)', [
            'methods' => WP_REST_Server::READABLE,
            'callback' => [$this, 'get_module_layouts'],
            'permission_callback' => '__return_true',
        ]);

        // Componentes disponibles
        register_rest_route(self::API_NAMESPACE, '/layouts/components', [
            'methods' => WP_REST_Server::READABLE,
            'callback' => [$this, 'get_available_components'],
            'permission_callback' => '__return_true',
            'args' => [
                'category' => [
                    'type' => 'string',
                    'required' => false,
                ],
            ],
        ]);

        // Datos dinámicos de un componente
        register_rest_route(self::API_NAMESPACE, '/layouts/component-data/(?P<component_id>[a-z0-9_-]+)', [
            'methods' => WP_REST_Server::READABLE,
            'callback' => [$this, 'get_component_data'],
            'permission_callback' => '__return_true',
            'args' => [
                'context' => [
                    'type' => 'object',
                    'required' => false,
                ],
            ],
        ]);

        // Generar layout con IA
        register_rest_route(self::API_NAMESPACE, '/layouts/generate', [
            'methods' => WP_REST_Server::CREATABLE,
            'callback' => [$this, 'generate_layout_with_ai'],
            'permission_callback' => [$this, 'check_api_permission'],
            'args' => [
                'description' => [
                    'type' => 'string',
                    'required' => true,
                ],
                'sector' => [
                    'type' => 'string',
                    'required' => false,
                ],
            ],
        ]);
    }

    /**
     * Verificar permisos de API
     */
    public function check_api_permission($request) {
        // Verificar token de API si está configurado
        $api_token = $request->get_header('X-Flavor-Token');
        if ($api_token) {
            $valid_tokens = get_option('flavor_apps_tokens', []);
            return in_array($api_token, array_column($valid_tokens, 'token'));
        }

        // Para endpoints públicos, permitir acceso
        return true;
    }

    /**
     * Obtener schema de componentes para apps
     */
    public function get_component_schema($request) {
        $schema = [
            'version' => self::SCHEMA_VERSION,
            'native_types' => $this->get_native_types_definition(),
            'component_mapping' => $this->component_mapping,
            'settings_schema' => $this->get_settings_schema(),
        ];

        return rest_ensure_response($schema);
    }

    /**
     * Definición de tipos nativos
     */
    private function get_native_types_definition() {
        return [
            'hero_banner' => [
                'description' => 'Banner principal con imagen/video de fondo',
                'android_component' => 'CollapsingToolbarLayout + ImageView',
                'ios_component' => 'UIView con parallax',
                'flutter_component' => 'SliverAppBar',
                'required_fields' => ['title'],
                'optional_fields' => ['subtitle', 'background_image', 'primary_button_text', 'primary_button_url'],
            ],
            'item_grid' => [
                'description' => 'Grid de items con soporte de scroll infinito',
                'android_component' => 'RecyclerView con GridLayoutManager',
                'ios_component' => 'UICollectionView',
                'flutter_component' => 'GridView.builder',
                'required_fields' => ['title'],
                'optional_fields' => ['columns', 'limit', 'show_filters', 'data_endpoint'],
            ],
            'item_list' => [
                'description' => 'Lista vertical de items',
                'android_component' => 'RecyclerView con LinearLayoutManager',
                'ios_component' => 'UITableView',
                'flutter_component' => 'ListView.builder',
                'required_fields' => ['title'],
                'optional_fields' => ['limit', 'show_icons', 'data_endpoint'],
            ],
            'map_view' => [
                'description' => 'Mapa interactivo con marcadores',
                'android_component' => 'Google Maps / Mapbox',
                'ios_component' => 'MapKit / Google Maps',
                'flutter_component' => 'google_maps_flutter',
                'required_fields' => [],
                'optional_fields' => ['initial_zoom', 'show_user_location', 'markers_endpoint'],
            ],
            'info_card' => [
                'description' => 'Tarjeta de información',
                'android_component' => 'MaterialCardView',
                'ios_component' => 'UIView con sombra',
                'flutter_component' => 'Card',
                'required_fields' => ['title'],
                'optional_fields' => ['description', 'image', 'icon', 'actions'],
            ],
            'stats_row' => [
                'description' => 'Fila de estadísticas con iconos',
                'android_component' => 'LinearLayout horizontal',
                'ios_component' => 'UIStackView',
                'flutter_component' => 'Row',
                'required_fields' => ['stats_items'],
                'optional_fields' => ['title', 'animated'],
            ],
            'action_banner' => [
                'description' => 'Banner con llamada a la acción',
                'android_component' => 'ConstraintLayout con Button',
                'ios_component' => 'UIView con UIButton',
                'flutter_component' => 'Container con ElevatedButton',
                'required_fields' => ['title', 'button_text'],
                'optional_fields' => ['subtitle', 'description', 'button_url', 'background_color'],
            ],
            'category_chips' => [
                'description' => 'Chips de categorías horizontales',
                'android_component' => 'HorizontalScrollView con ChipGroup',
                'ios_component' => 'UICollectionView horizontal',
                'flutter_component' => 'Wrap con Chip',
                'required_fields' => ['categories'],
                'optional_fields' => ['title', 'show_icons', 'multiselect'],
            ],
            'calendar_view' => [
                'description' => 'Vista de calendario',
                'android_component' => 'MaterialCalendarView',
                'ios_component' => 'FSCalendar',
                'flutter_component' => 'TableCalendar',
                'required_fields' => [],
                'optional_fields' => ['initial_view', 'events_endpoint'],
            ],
            'form_view' => [
                'description' => 'Formulario dinámico',
                'android_component' => 'ScrollView con TextInputLayout',
                'ios_component' => 'UITableView con celdas de input',
                'flutter_component' => 'Form con TextFormField',
                'required_fields' => ['fields'],
                'optional_fields' => ['title', 'submit_text', 'submit_endpoint'],
            ],
            'search_bar' => [
                'description' => 'Barra de búsqueda',
                'android_component' => 'SearchView',
                'ios_component' => 'UISearchBar',
                'flutter_component' => 'TextField con decoración',
                'required_fields' => [],
                'optional_fields' => ['placeholder', 'show_filters', 'search_endpoint'],
            ],
            'tab_view' => [
                'description' => 'Vista con pestañas',
                'android_component' => 'TabLayout con ViewPager2',
                'ios_component' => 'UISegmentedControl + UIPageViewController',
                'flutter_component' => 'TabBar con TabBarView',
                'required_fields' => ['tabs'],
                'optional_fields' => ['active_tab', 'swipeable'],
            ],
            'carousel_view' => [
                'description' => 'Carrusel de elementos',
                'android_component' => 'ViewPager2',
                'ios_component' => 'UICollectionView con paginación',
                'flutter_component' => 'PageView',
                'required_fields' => ['items'],
                'optional_fields' => ['autoplay', 'interval_seconds', 'indicators'],
            ],
            'photo_gallery' => [
                'description' => 'Galería de fotos con lightbox',
                'android_component' => 'RecyclerView + PhotoView',
                'ios_component' => 'UICollectionView + lightbox',
                'flutter_component' => 'GridView + photo_view',
                'required_fields' => [],
                'optional_fields' => ['columns', 'limit', 'images_endpoint'],
            ],
            'media_player' => [
                'description' => 'Reproductor de audio/video',
                'android_component' => 'ExoPlayer',
                'ios_component' => 'AVPlayer',
                'flutter_component' => 'video_player / audioplayers',
                'required_fields' => ['stream_url'],
                'optional_fields' => ['title', 'media_type', 'thumbnail'],
            ],
            'timeline_view' => [
                'description' => 'Línea de tiempo/proceso',
                'android_component' => 'RecyclerView con TimelineView',
                'ios_component' => 'UITableView con línea',
                'flutter_component' => 'timeline_tile',
                'required_fields' => ['steps'],
                'optional_fields' => ['title', 'current_step'],
            ],
            'testimonial_carousel' => [
                'description' => 'Carrusel de testimonios',
                'android_component' => 'ViewPager2 con card layout',
                'ios_component' => 'UICollectionView',
                'flutter_component' => 'PageView con Card',
                'required_fields' => [],
                'optional_fields' => ['title', 'limit', 'data_endpoint'],
            ],
            'profile_header' => [
                'description' => 'Cabecera de perfil de usuario',
                'android_component' => 'CoordinatorLayout con perfil',
                'ios_component' => 'UIView con avatar',
                'flutter_component' => 'Column con CircleAvatar',
                'required_fields' => [],
                'optional_fields' => ['show_avatar', 'show_stats', 'show_cover'],
            ],
            'alert_banner' => [
                'description' => 'Banner de alerta/notificación',
                'android_component' => 'Snackbar / Banner',
                'ios_component' => 'UIView animado',
                'flutter_component' => 'MaterialBanner',
                'required_fields' => ['message'],
                'optional_fields' => ['title', 'alert_type', 'dismissible', 'action'],
            ],
            'expandable_list' => [
                'description' => 'Lista expandible (acordeón)',
                'android_component' => 'ExpandableListView',
                'ios_component' => 'UITableView con secciones',
                'flutter_component' => 'ExpansionPanelList',
                'required_fields' => ['items'],
                'optional_fields' => ['title', 'single_expand'],
            ],
            'pricing_cards' => [
                'description' => 'Tarjetas de precios/planes',
                'android_component' => 'HorizontalScrollView con Cards',
                'ios_component' => 'UICollectionView horizontal',
                'flutter_component' => 'ListView.builder horizontal',
                'required_fields' => ['plans'],
                'optional_fields' => ['title', 'highlighted_plan'],
            ],
            'countdown_timer' => [
                'description' => 'Contador regresivo',
                'android_component' => 'CountDownTimer con TextViews',
                'ios_component' => 'Timer con UILabels',
                'flutter_component' => 'flutter_countdown_timer',
                'required_fields' => ['end_date'],
                'optional_fields' => ['title', 'format'],
            ],
            'team_grid' => [
                'description' => 'Grid de miembros del equipo',
                'android_component' => 'RecyclerView con GridLayoutManager',
                'ios_component' => 'UICollectionView',
                'flutter_component' => 'GridView con CircleAvatar',
                'required_fields' => [],
                'optional_fields' => ['title', 'limit', 'show_role', 'data_endpoint'],
            ],
        ];
    }

    /**
     * Schema de settings para componentes
     */
    private function get_settings_schema() {
        return [
            'padding' => [
                'type' => 'enum',
                'values' => ['none', 'small', 'medium', 'large'],
                'default' => 'medium',
            ],
            'margin' => [
                'type' => 'enum',
                'values' => ['none', 'small', 'medium', 'large'],
                'default' => 'none',
            ],
            'background' => [
                'type' => 'enum',
                'values' => ['white', 'gray', 'primary', 'secondary', 'transparent', 'gradient'],
                'default' => 'white',
            ],
            'corner_radius' => [
                'type' => 'enum',
                'values' => ['none', 'small', 'medium', 'large', 'full'],
                'default' => 'none',
            ],
            'shadow' => [
                'type' => 'enum',
                'values' => ['none', 'small', 'medium', 'large'],
                'default' => 'none',
            ],
            'animation' => [
                'type' => 'enum',
                'values' => ['none', 'fade', 'slide', 'scale'],
                'default' => 'none',
            ],
        ];
    }

    /**
     * Obtener plantillas disponibles
     */
    public function get_templates($request) {
        $sector_filter = $request->get_param('sector');

        $page_builder = Flavor_Page_Builder::get_instance();
        $reflection = new ReflectionClass($page_builder);
        $method = $reflection->getMethod('get_template_library');
        $method->setAccessible(true);
        $templates = $method->invoke($page_builder);

        $result = [];

        foreach ($templates as $sector_id => $sector_data) {
            if ($sector_filter && $sector_id !== $sector_filter) {
                continue;
            }

            $sector_templates = [];
            foreach ($sector_data['templates'] as $template_id => $template) {
                $sector_templates[] = [
                    'id' => $template_id,
                    'name' => $template['name'],
                    'description' => $template['description'],
                    'icon' => $template['icon'],
                    'components_count' => count($template['layout']),
                    'preview_url' => $template['preview'] ?? null,
                ];
            }

            $result[] = [
                'sector_id' => $sector_id,
                'sector_name' => $sector_data['label'],
                'templates' => $sector_templates,
            ];
        }

        return rest_ensure_response([
            'success' => true,
            'schema_version' => self::SCHEMA_VERSION,
            'sectors' => $result,
        ]);
    }

    /**
     * Obtener plantilla específica con layout nativo
     */
    public function get_template($request) {
        $template_id = $request->get_param('template_id');

        $page_builder = Flavor_Page_Builder::get_instance();
        $reflection = new ReflectionClass($page_builder);
        $method = $reflection->getMethod('get_template_library');
        $method->setAccessible(true);
        $templates = $method->invoke($page_builder);

        // Buscar la plantilla en todos los sectores
        $found_template = null;
        $found_sector = null;

        foreach ($templates as $sector_id => $sector_data) {
            if (isset($sector_data['templates'][$template_id])) {
                $found_template = $sector_data['templates'][$template_id];
                $found_sector = [
                    'id' => $sector_id,
                    'name' => $sector_data['label'],
                ];
                break;
            }
        }

        if (!$found_template) {
            return new WP_Error('template_not_found', 'Plantilla no encontrada', ['status' => 404]);
        }

        // Convertir layout a formato nativo
        $native_layout = $this->convert_layout_to_native($found_template['layout']);

        return rest_ensure_response([
            'success' => true,
            'schema_version' => self::SCHEMA_VERSION,
            'template' => [
                'id' => $template_id,
                'name' => $found_template['name'],
                'description' => $found_template['description'],
                'icon' => $found_template['icon'],
                'sector' => $found_sector,
                'layout' => $native_layout,
            ],
        ]);
    }

    /**
     * Obtener layout de una landing publicada
     */
    public function get_landing_layout($request) {
        $post_id = $request->get_param('id');

        $post = get_post($post_id);
        if (!$post || $post->post_type !== 'flavor_landing' || $post->post_status !== 'publish') {
            return new WP_Error('landing_not_found', 'Landing no encontrada', ['status' => 404]);
        }

        $layout = get_post_meta($post_id, '_flavor_page_layout', true);
        if (!is_array($layout)) {
            $layout = [];
        }

        $native_layout = $this->convert_layout_to_native($layout);

        return rest_ensure_response([
            'success' => true,
            'schema_version' => self::SCHEMA_VERSION,
            'landing' => [
                'id' => $post_id,
                'title' => $post->post_title,
                'slug' => $post->post_name,
                'modified' => $post->post_modified_gmt,
                'layout' => $native_layout,
            ],
        ]);
    }

    /**
     * Obtener landing por slug
     */
    public function get_landing_by_slug($request) {
        $slug = $request->get_param('slug');

        $posts = get_posts([
            'post_type' => 'flavor_landing',
            'post_status' => 'publish',
            'name' => $slug,
            'posts_per_page' => 1,
        ]);

        if (empty($posts)) {
            return new WP_Error('landing_not_found', 'Landing no encontrada', ['status' => 404]);
        }

        $post = $posts[0];
        $layout = get_post_meta($post->ID, '_flavor_page_layout', true);
        if (!is_array($layout)) {
            $layout = [];
        }

        $native_layout = $this->convert_layout_to_native($layout);

        return rest_ensure_response([
            'success' => true,
            'schema_version' => self::SCHEMA_VERSION,
            'landing' => [
                'id' => $post->ID,
                'title' => $post->post_title,
                'slug' => $post->post_name,
                'modified' => $post->post_modified_gmt,
                'layout' => $native_layout,
            ],
        ]);
    }

    /**
     * Obtener layouts de un módulo
     */
    public function get_module_layouts($request) {
        $module_id = $request->get_param('module_id');

        // Obtener componentes web del módulo
        $module_components = apply_filters("flavor_module_{$module_id}_web_components", []);

        if (empty($module_components)) {
            // Intentar cargar el módulo directamente
            $module_class = 'Flavor_' . str_replace('-', '_', ucwords($module_id, '-')) . '_Module';
            if (class_exists($module_class) && method_exists($module_class, 'get_instance')) {
                $module_instance = call_user_func([$module_class, 'get_instance']);
                if (method_exists($module_instance, 'get_web_components')) {
                    $module_components = $module_instance->get_web_components();
                }
            }
        }

        // Convertir componentes a formato nativo
        $native_components = [];
        foreach ($module_components as $component_id => $component) {
            $native_components[] = $this->convert_component_to_native($component_id, $component);
        }

        return rest_ensure_response([
            'success' => true,
            'schema_version' => self::SCHEMA_VERSION,
            'module' => [
                'id' => $module_id,
                'components' => $native_components,
            ],
        ]);
    }

    /**
     * Obtener componentes disponibles
     */
    public function get_available_components($request) {
        $category_filter = $request->get_param('category');

        $registry = Flavor_Component_Registry::get_instance();
        $all_components = $registry->get_components();
        $categories = $registry->get_categories();

        $result = [];

        foreach ($categories as $category_id => $category_name) {
            if ($category_filter && $category_id !== $category_filter) {
                continue;
            }

            $category_components = [];
            foreach ($all_components as $component_id => $component) {
                if (($component['category'] ?? 'general') !== $category_id) {
                    continue;
                }

                $native_info = $this->get_native_info_for_component($component_id);

                $category_components[] = [
                    'id' => $component_id,
                    'label' => $component['label'],
                    'description' => $component['description'] ?? '',
                    'icon' => $component['icon'],
                    'native_type' => $native_info['native_type'],
                    'supports' => $native_info['supports'],
                    'fields' => $this->convert_fields_to_native($component['fields'] ?? []),
                ];
            }

            if (!empty($category_components)) {
                $result[] = [
                    'category_id' => $category_id,
                    'category_name' => $category_name,
                    'components' => $category_components,
                ];
            }
        }

        return rest_ensure_response([
            'success' => true,
            'schema_version' => self::SCHEMA_VERSION,
            'categories' => $result,
        ]);
    }

    /**
     * Obtener datos dinámicos de un componente
     */
    public function get_component_data($request) {
        $component_id = $request->get_param('component_id');
        $context = $request->get_param('context') ?? [];

        // Permitir que los módulos proporcionen datos
        $data = apply_filters("flavor_app_component_data_{$component_id}", [], $context);

        if (empty($data)) {
            // Intentar obtener datos genéricos basados en el tipo de componente
            $data = $this->get_generic_component_data($component_id, $context);
        }

        return rest_ensure_response([
            'success' => true,
            'component_id' => $component_id,
            'data' => $data,
            'cache_ttl' => 300, // 5 minutos de cache recomendado
        ]);
    }

    /**
     * Generar layout con IA
     */
    public function generate_layout_with_ai($request) {
        $description = $request->get_param('description');
        $sector = $request->get_param('sector');

        if (!class_exists('Flavor_AI_Template_Assistant')) {
            return new WP_Error('ai_not_available', 'Asistente IA no disponible', ['status' => 503]);
        }

        $assistant = Flavor_AI_Template_Assistant::get_instance();

        // Simular request AJAX
        $_POST['description'] = $description;
        $_POST['nonce'] = wp_create_nonce('flavor_ai_template_assistant');

        ob_start();
        $assistant->ajax_suggest_template();
        $response = ob_get_clean();

        $response_data = json_decode($response, true);

        if (!$response_data || !$response_data['success']) {
            return new WP_Error('ai_error', $response_data['data']['message'] ?? 'Error generando layout', ['status' => 500]);
        }

        // Convertir a formato nativo
        $template = $response_data['data']['template'] ?? null;
        if ($template && isset($template['layout'])) {
            $template['layout'] = $this->convert_layout_to_native($template['layout']);
        }

        return rest_ensure_response([
            'success' => true,
            'schema_version' => self::SCHEMA_VERSION,
            'generated' => [
                'description' => $description,
                'sector' => $sector,
                'template' => $template,
                'ai_message' => $response_data['data']['message'] ?? '',
            ],
        ]);
    }

    /**
     * Convertir layout completo a formato nativo
     */
    private function convert_layout_to_native($layout) {
        $native_layout = [];

        foreach ($layout as $index => $component) {
            $native_component = $this->convert_component_to_native(
                $component['component_id'],
                $component
            );
            $native_component['order'] = $index;
            $native_layout[] = $native_component;
        }

        return $native_layout;
    }

    /**
     * Convertir un componente a formato nativo
     */
    private function convert_component_to_native($component_id, $component) {
        $native_info = $this->get_native_info_for_component($component_id);

        // Mapear campos de datos
        $native_data = [];
        $original_data = $component['data'] ?? [];
        $fields_map = $native_info['fields_map'] ?? [];

        foreach ($original_data as $key => $value) {
            $native_key = $fields_map[$key] ?? $key;
            $native_data[$native_key] = $value;
        }

        // Convertir settings
        $settings = $component['settings'] ?? [];
        $native_settings = $this->convert_settings_to_native($settings);

        // Añadir endpoint de datos si es un componente que requiere datos dinámicos
        $data_endpoint = $this->get_data_endpoint_for_component($component_id);

        return [
            'component_id' => $component_id,
            'native_type' => $native_info['native_type'],
            'supports' => $native_info['supports'],
            'data' => $native_data,
            'settings' => $native_settings,
            'data_endpoint' => $data_endpoint,
        ];
    }

    /**
     * Obtener información nativa para un componente
     */
    private function get_native_info_for_component($component_id) {
        // Buscar mapeo directo
        foreach ($this->component_mapping as $pattern => $mapping) {
            if (strpos($component_id, $pattern) !== false) {
                return $mapping;
            }
        }

        // Intentar detectar el tipo por el nombre
        $type_patterns = [
            'hero' => 'hero',
            'grid' => 'grid',
            'lista' => 'lista',
            'listado' => 'lista',
            'mapa' => 'mapa',
            'estadisticas' => 'estadisticas',
            'cta' => 'cta',
            'categorias' => 'categorias',
            'calendario' => 'calendario',
            'formulario' => 'formulario',
            'buscador' => 'buscador',
            'carousel' => 'carousel',
            'galeria' => 'galeria',
            'player' => 'player',
            'proceso' => 'proceso',
            'como_funciona' => 'proceso',
            'testimonios' => 'testimonios',
            'perfil' => 'perfil',
            'tarifas' => 'tarifas',
            'equipo' => 'equipo',
            'locutores' => 'equipo',
            'presentadores' => 'equipo',
        ];

        foreach ($type_patterns as $pattern => $type) {
            if (strpos($component_id, $pattern) !== false) {
                return $this->component_mapping[$type] ?? $this->get_default_native_info();
            }
        }

        return $this->get_default_native_info();
    }

    /**
     * Info nativa por defecto
     */
    private function get_default_native_info() {
        return [
            'native_type' => 'info_card',
            'supports' => ['basic'],
            'fields_map' => [
                'titulo' => 'title',
                'subtitulo' => 'subtitle',
                'descripcion' => 'description',
            ],
        ];
    }

    /**
     * Convertir settings a formato nativo
     */
    private function convert_settings_to_native($settings) {
        $native_settings = [];

        // Mapeo de valores de padding/margin
        $spacing_map = [
            'none' => 0,
            'small' => 8,
            'medium' => 16,
            'large' => 24,
            'xlarge' => 32,
        ];

        if (isset($settings['padding'])) {
            $native_settings['padding'] = $spacing_map[$settings['padding']] ?? 16;
        }

        if (isset($settings['margin'])) {
            $native_settings['margin'] = $spacing_map[$settings['margin']] ?? 0;
        }

        if (isset($settings['background'])) {
            $native_settings['background'] = $settings['background'];
        }

        return $native_settings;
    }

    /**
     * Convertir campos a formato nativo
     */
    private function convert_fields_to_native($fields) {
        $native_fields = [];

        foreach ($fields as $field_id => $field) {
            $native_fields[$field_id] = [
                'type' => $this->map_field_type($field['type'] ?? 'text'),
                'label' => $field['label'] ?? $field_id,
                'required' => $field['required'] ?? false,
                'default' => $field['default'] ?? null,
            ];

            if (isset($field['options'])) {
                $native_fields[$field_id]['options'] = $field['options'];
            }
        }

        return $native_fields;
    }

    /**
     * Mapear tipo de campo
     */
    private function map_field_type($type) {
        $type_map = [
            'text' => 'string',
            'textarea' => 'text',
            'number' => 'integer',
            'select' => 'enum',
            'checkbox' => 'boolean',
            'image' => 'image_url',
            'color' => 'color',
            'url' => 'url',
            'date' => 'date',
            'time' => 'time',
            'datetime' => 'datetime',
        ];

        return $type_map[$type] ?? 'string';
    }

    /**
     * Obtener endpoint de datos para un componente
     */
    private function get_data_endpoint_for_component($component_id) {
        // Componentes que necesitan datos dinámicos
        $dynamic_components = [
            'grid' => '/flavor-app/v1/layouts/component-data/',
            'lista' => '/flavor-app/v1/layouts/component-data/',
            'listado' => '/flavor-app/v1/layouts/component-data/',
            'mapa' => '/flavor-app/v1/layouts/component-data/',
            'calendario' => '/flavor-app/v1/layouts/component-data/',
            'galeria' => '/flavor-app/v1/layouts/component-data/',
            'testimonios' => '/flavor-app/v1/layouts/component-data/',
            'equipo' => '/flavor-app/v1/layouts/component-data/',
            'estadisticas' => '/flavor-app/v1/layouts/component-data/',
        ];

        foreach ($dynamic_components as $pattern => $base_endpoint) {
            if (strpos($component_id, $pattern) !== false) {
                return rest_url($base_endpoint . $component_id);
            }
        }

        return null;
    }

    /**
     * Obtener datos genéricos de componente
     */
    private function get_generic_component_data($component_id, $context) {
        // Implementar lógica genérica para obtener datos
        // Esto puede ser extendido por cada módulo

        $data = [
            'items' => [],
            'total' => 0,
            'page' => 1,
            'per_page' => 10,
        ];

        return apply_filters('flavor_app_generic_component_data', $data, $component_id, $context);
    }
}

// Inicializar
Flavor_App_Layouts_API::get_instance();
